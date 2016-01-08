<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2014 Bitrix
 */

namespace Bitrix\Main\Analytics;
use Bitrix\Catalog\CatalogViewedProductTable;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\UserTable;
use Bitrix\Sale\OrderTable;

if (!Loader::includeModule('catalog'))
	return;

/**
 * @package bitrix
 * @subpackage main
 */
class Catalog
{
	protected static $cookieLogName = 'RCM_PRODUCT_LOG';

	// basket (catalog:OnBasketAdd)
	public static function catchCatalogBasket($id, $arFields)
	{
		// exclude empty cookie
		if (!static::getBxUserId())
		{
			return;
		}

		if (!isset($arFields['MODULE']) || $arFields['MODULE'] != 'catalog')
		{
			// catalog items only
			return;
		}

		global $APPLICATION;

		// alter b_sale_basket - add recommendation, update it here
		if (!static::isOn())
		{
			return;
		}

		// get product id by offer id
		$productInfo = \CCatalogSKU::GetProductInfo($arFields['PRODUCT_ID']);
		$iblockId = 0;

		if (!empty($productInfo['ID']))
		{
			$realProductId = $productInfo['ID'];
			$iblockId = $productInfo['IBLOCK_ID'];
		}
		else
		{
			$realProductId = $arFields['PRODUCT_ID'];

			// get iblock id
			$element = \Bitrix\Iblock\ElementTable::getRow(array(
				'select' => array('IBLOCK_ID'),
				'filter' => array('=ID' => $realProductId)
			));

			if (!empty($element))
			{
				$iblockId = $element['IBLOCK_ID'];
			}
		}

		// select site user id & recommendation id
		$siteUserId = 0;
		$recommendationId = '';

		// first, try to find in cookies
		$recommendationCookie = $APPLICATION->get_cookie(static::getCookieLogName());

		if (!empty($recommendationCookie))
		{
			$recommendations = static::decodeProductLog($recommendationCookie);

			if (is_array($recommendations) && isset($recommendations[$realProductId]))
			{
				$recommendationId = $recommendations[$realProductId][0];
			}
		}

		if (empty($recommendationId))
		{
			// ok then, lets see in views history
			//if(\COption::GetOptionString("sale", "encode_fuser_id", "N") == "Y")
			if (!is_numeric($arFields['FUSER_ID']))
			{
				$filter = array('CODE' => $arFields['FUSER_ID']);
			}
			else
			{
				$filter = array('ID' => $arFields['FUSER_ID']);
			}

			$result = \CSaleUser::getList($filter);

			if (!empty($result))
			{
				$siteUserId = $result['USER_ID'];

				// select recommendation id
				$fuser = $result['ID'];

				$viewResult = CatalogViewedProductTable::getList(array(
					'select' => array('RECOMMENDATION'),
					'filter' => array(
						'=FUSER_ID' => $fuser,
						'=PRODUCT_ID' => $arFields['PRODUCT_ID']
					),
					'order' => array('DATE_VISIT' => 'DESC')
				))->fetch();

				if (!empty($viewResult['RECOMMENDATION']))
				{
					$recommendationId = $viewResult['RECOMMENDATION'];
				}
			}
		}

		// prepare data
		$data = array(
			'product_id' => $realProductId,
			'iblock_id' => $iblockId,
			'user_id' => $siteUserId,
			'bx_user_id' => static::getBxUserId(),
			'domain' => Context::getCurrent()->getServer()->getHttpHost(),
			'recommendation' => $recommendationId,
			'date' => date(DATE_ISO8601)
		);

		// debug info
		global $USER;

		$data['real_user_id'] = $USER->getId() ?: 0;
		$data['is_admin'] = (int) $USER->IsAdmin();
		$data['admin_section'] = (int) (defined('ADMIN_SECTION') && ADMIN_SECTION);
		$data['admin_panel'] = (int) \CTopPanel::shouldShowPanel();

		// try to guess unnatural baskets
		$data['artificial_basket'] = (int) (
			($data['user_id'] > 0 && $data['user_id'] != $data['real_user_id'])
			||  $data['is_admin'] || $data['admin_section'] || $data['admin_panel']
		);

		// save
		CounterDataTable::add(array(
			'TYPE' => 'basket',
			'DATA' => $data
		));

		// update basket with recommendation id
		if (!empty($recommendationId))
		{
			$conn = Application::getConnection();
			$helper = $conn->getSqlHelper();

			$conn->query(
				"UPDATE ".$helper->quote('b_sale_basket')
				." SET RECOMMENDATION='".$helper->forSql($recommendationId)."' WHERE ID=".(int) $id
			);
		}
	}

	// order detailed info (OnOrderSave)
	public static function catchCatalogOrder($orderId, $arFields, $arOrder, $isNew)
	{
		if (!static::isOn())
		{
			return;
		}

		if (!$isNew)
		{
			// only new orders
			return;
		}

		$data = static::getOrderInfo($orderId);

		// catalog items only
		if (empty($data['products']))
		{
			return;
		}

		// add bxuid
		$data['bx_user_id'] = static::getBxUserId();

		if (empty($data['bx_user_id']) && !empty($data['user_id']))
		{
			$orderUser = UserTable::getRow(array(
				'select' => array('BX_USER_ID'),
				'filter' => array('=ID' => $data['user_id'])
			));

			if (!empty($orderUser) && !empty($orderUser['BX_USER_ID']))
			{
				$data['bx_user_id'] = $orderUser['BX_USER_ID'];
			}
		}

		// add general info
		$data['paid'] = '0';
		$data['domain'] = Context::getCurrent()->getServer()->getHttpHost();
		$data['date'] = date(DATE_ISO8601);

		// add debug info
		global $USER;

		$data['real_user_id'] = $USER->getId() ?: 0;
		$data['cookie_size'] = count($_COOKIE);
		$data['is_admin'] = (int) $USER->IsAdmin();
		$data['admin_section'] = (int) (defined('ADMIN_SECTION') && ADMIN_SECTION);
		$data['admin_panel'] = (int) \CTopPanel::shouldShowPanel();

		// try to guess unnatural orders
		$data['artificial_order'] = (int) (
			($data['user_id'] != $data['real_user_id']) || !$data['cookie_size']
			||  $data['is_admin'] || $data['admin_section'] || $data['admin_panel']
		);

		CounterDataTable::add(array(
			'TYPE' => 'order',
			'DATA' => $data
		));

		// set bxuid to the order
		if (!empty($data['bx_user_id']))
		{
			// if sale version is fresh enough
			if (OrderTable::getEntity()->hasField('BX_USER_ID'))
			{
				OrderTable::update($data['order_id'], array('BX_USER_ID' => $data['bx_user_id']));
			}
		}
	}

	// order payment (OnSalePayOrder)
	public static function catchCatalogOrderPayment($orderId, $value)
	{
		if (!static::isOn())
		{
			return;
		}

		if ($value == 'Y')
		{
			$data = static::getOrderInfo($orderId);

			// catalog items only
			if (empty($data['products']))
			{
				return;
			}

			// add bxuid
			$data['bx_user_id'] = static::getBxUserId();

			if (empty($data['bx_user_id']) && OrderTable::getEntity()->hasField('BX_USER_ID'))
			{
				$order = OrderTable::getRow(array(
					'select' => array('BX_USER_ID'),
					'filter' => array('=ID' => $orderId)
				));

				if (!empty($order) && !empty($order['BX_USER_ID']))
				{
					$data['bx_user_id'] = $order['BX_USER_ID'];
				}
			}

			// add general info
			$data['paid'] = '1';
			$data['domain'] = Context::getCurrent()->getServer()->getHttpHost();
			$data['date'] = date(DATE_ISO8601);

			CounterDataTable::add(array(
				'TYPE' => 'order_pay',
				'DATA' => $data
			));
		}
	}

	protected static function getOrderInfo($orderId)
	{
		// order itself
		$order = \CSaleOrder::getById($orderId);

		// buyer info
		$siteUserId = $order['USER_ID'];

		$phone = '';
		$email = '';

		$result = \CSaleOrderPropsValue::GetList(array(), array("ORDER_ID" => $orderId));
		while ($row = $result->fetch())
		{
			if (empty($phone) && stripos($row['CODE'], 'PHONE') !== false)
			{
				$stPhone = static::normalizePhoneNumber($row['VALUE']);

				if (!empty($stPhone))
				{
					$phone = sha1($stPhone);
				}
			}

			if (empty($email) && stripos($row['CODE'], 'EMAIL') !== false)
			{
				if (!empty($row['VALUE']))
				{
					$email = sha1($row['VALUE']);
				}
			}
		}

		// products info
		$products = array();

		$result = \CSaleBasket::getList(
			array(), $arFilter = array('ORDER_ID' => $orderId, 'MODULE' => 'catalog'), false, false,
			array('PRODUCT_ID', 'RECOMMENDATION', 'QUANTITY', 'PRICE', 'CURRENCY')
		);

		while ($row = $result->fetch())
		{
			$productInfo = \CCatalogSKU::GetProductInfo($row['PRODUCT_ID']);
			$iblockId = 0;

			if (!empty($productInfo['ID']))
			{
				$realProductId = $productInfo['ID'];
				$iblockId = $productInfo['IBLOCK_ID'];
			}
			else
			{
				$realProductId = $row['PRODUCT_ID'];

				// get iblock id
				$element = \Bitrix\Iblock\ElementTable::getRow(array(
					'select' => array('IBLOCK_ID'),
					'filter' => array('=ID' => $realProductId)
				));

				if (!empty($element))
				{
					$iblockId = $element['IBLOCK_ID'];
				}
			}

			$products[] = array(
				'product_id' => $realProductId,
				'iblock_id' => $iblockId,
				'quantity' => $row['QUANTITY'],
				'price' => $row['PRICE'],
				'currency' => $row['CURRENCY'],
				'recommendation' => $row['RECOMMENDATION']
			);
		}

		// all together
		$data = array(
			'order_id' => $orderId,
			'user_id' => $siteUserId,
			'phone' => $phone,
			'email' => $email,
			'products' => $products,
			'price' => $order['PRICE'],
			'currency' => $order['CURRENCY']
		);

		return $data;
	}

	protected function getBxUserId()
	{
		return $_COOKIE['BX_USER_ID'];
	}

	public static function normalizePhoneNumber($phone)
	{
		$phone = preg_replace('/[^\d]/', '', $phone);

		$cleanPhone = \NormalizePhone($phone, 6);

		if (strlen($cleanPhone) == 10)
		{
			$cleanPhone = '7'.$cleanPhone;
		}

		return $cleanPhone;
	}

	public static function isOn()
	{
		return SiteSpeed::isLicenseAccepted()
			&& Option::get("main", "gather_catalog_stat", "Y") === "Y"
			&& defined("LICENSE_KEY") && LICENSE_KEY !== "DEMO"
		;
	}

	public static function getProductIdsByOfferIds($offerIds)
	{
		if (empty($offerIds))
			return array();

		$bestList = array();
		$iblockGroup = array();
		$itemIterator = \Bitrix\Iblock\ElementTable::getList(array(
			'select' => array('ID', 'IBLOCK_ID'),
			'filter' => array('ID' => $offerIds, 'ACTIVE'=> 'Y')
		));
		while ($item = $itemIterator->fetch())
		{
			if (!isset($iblockGroup[$item['IBLOCK_ID']]))
				$iblockGroup[$item['IBLOCK_ID']] = array();
			$iblockGroup[$item['IBLOCK_ID']][] = $item['ID'];
			$bestList[$item['ID']] = array();
		}

		if (empty($iblockGroup))
			return array();

		$iblockSku = array();
		$iblockOffers = array();
		if (!empty($iblockGroup))
		{
			$iblockIterator = \Bitrix\Catalog\CatalogIblockTable::getList(array(
				'select' => array('IBLOCK_ID', 'PRODUCT_IBLOCK_ID', 'SKU_PROPERTY_ID', 'VERSION' => 'IBLOCK.VERSION'),
				'filter' => array('=IBLOCK_ID' => array_keys($iblockGroup), '!PRODUCT_IBLOCK_ID' => 0)
			));
			while ($iblock = $iblockIterator->fetch())
			{
				$iblock['IBLOCK_ID'] = (int)$iblock['IBLOCK_ID'];
				$iblock['PRODUCT_IBLOCK_ID'] = (int)$iblock['PRODUCT_IBLOCK_ID'];
				$iblock['SKU_PROPERTY_ID'] = (int)$iblock['SKU_PROPERTY_ID'];
				$iblock['VERSION'] = (int)$iblock['VERSION'];
				$iblockSku[$iblock['IBLOCK_ID']] = $iblock;
				$iblockOffers[$iblock['IBLOCK_ID']] = $iblockGroup[$iblock['IBLOCK_ID']];
			}
			unset($iblock, $iblockIterator);
		}
		if (empty($iblockOffers))
			return array();

		$offerLink = array();
		foreach ($iblockOffers as $iblockId => $items)
		{
			$skuProperty = 'PROPERTY_'.$iblockSku[$iblockId]['SKU_PROPERTY_ID'];
			$iblockFilter = array(
				'IBLOCK_ID' => $iblockId,
				'=ID' => $items
			);
			$iblockFields = array('ID', 'IBLOCK_ID', $skuProperty);
			$skuProperty .= '_VALUE';
			$offersIterator = \CIBlockElement::getList(
				array('ID' => 'ASC'),
				$iblockFilter,
				false,
				false,
				$iblockFields
			);

			while ($offer = $offersIterator->Fetch())
			{
				$productId = (int)$offer[$skuProperty];
				if ($productId <= 0)
				{
					unset($bestList[$offer['ID']]);
				}
				else
				{
					$bestList[$offer['ID']]['PARENT_ID'] = $productId;
					$bestList[$offer['ID']]['PARENT_IBLOCK'] = $iblockSku[$iblockId]['PRODUCT_IBLOCK_ID'];
					if (!isset($offerLink[$productId]))
						$offerLink[$productId] = array();
					$offerLink[$productId][] = $offer['ID'];
				}
			}
		}
		if (!empty($offerLink))
		{
			$productIterator = \Bitrix\Iblock\ElementTable::getList(array(
				'select' => array('ID'),
				'filter' => array('@ID' => array_keys($offerLink), 'ACTIVE' => 'N')
			));
			while ($product = $productIterator->fetch())
			{
				if (empty($offerLink[$product['ID']]))
					continue;
				foreach ($offerLink[$product['ID']] as $value)
				{
					unset($bestList[$value]);
				}
			}
		}

		if (empty($bestList))
			return array();

		$finalIds = array();
		$dublicate = array();
		foreach ($bestList as $id => $info)
		{
			if (empty($info))
			{
				if (!isset($dublicate[$id]))
					$finalIds[] = $id;
				$dublicate[$id] = true;
			}
			else
			{
				if (!isset($dublicate[$id]))
					$finalIds[] = $info['PARENT_ID'];
				$dublicate[$info['PARENT_ID']] = true;
			}
		}
		unset($id, $info, $dublicate);

		return $finalIds;
	}

	/**
	 * @param array $log
	 *
	 * @return string
	 */
	public static function encodeProductLog(array $log)
	{
		$value = array();

		foreach ($log as $itemId => $recommendation)
		{
			$rcmId = $recommendation[0];
			$rcmTime = $recommendation[1];

			$value[] = $itemId.'-'.$rcmId.'-'.$rcmTime;
		}

		return join('.', $value);
	}

	/**
	 * @param $log
	 *
	 * @return array
	 */
	public static function decodeProductLog($log)
	{
		$value = array();
		$tmp = explode('.', $log);

		foreach ($tmp as $tmpval)
		{
			$meta = explode('-', $tmpval);

			if (count($meta) > 2)
			{
				$itemId = $meta[0];
				$rcmId = $meta[1];
				$rcmTime = $meta[2];

				if ($itemId && $rcmId && $rcmTime)
				{
					$value[(int)$itemId] = array($rcmId, (int) $rcmTime);
				}
			}
		}

		return $value;
	}

	/**
	 * @return string
	 */
	public static function getCookieLogName()
	{
		return self::$cookieLogName;
	}
}
