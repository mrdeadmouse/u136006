<?php
class CCrmSaleHelper
{
	public static function Calculate($productRows, $currencyID, $personTypeID, $enableSaleDiscount = false, $siteId = SITE_ID, $arOptions = array())
	{
		if(!CModule::IncludeModule('sale'))
		{
			return array('err'=> '1');
		}

		$saleUserId = intval(CSaleUser::GetAnonymousUserID());
		if ($saleUserId <= 0)
		{
			return array('err'=> '2');
		}

		if(!is_array($productRows) && empty($productRows))
		{
			return array('err'=> '3');
		}

		$bTaxMode = CCrmTax::isTaxMode();
		if ($bTaxMode)
		{
			foreach ($productRows as &$productRow)
			{
				$productRow['TAX_RATE'] = 0.0;
				$productRow['TAX_INCLUDED'] = 'N';
			}
			unset($productRow);
		}

		$cartItems = self::PrepareShoppingCartItems($productRows, $currencyID, $siteId);
		foreach ($cartItems as &$item) // tmp hack not to update basket quantity data from catalog
		{
			$item['ID_TMP'] = $item['ID'];
			unset($item['ID']);
		}
		unset($item);

		$errors = array();
		$cartItems = CSaleBasket::DoGetUserShoppingCart($siteId, $saleUserId, $cartItems, $errors, array(), 0, true);

		foreach ($cartItems as &$item)
		{
			$item['ID'] = $item['ID_TMP'];
			unset($item['ID_TMP']);
		}
		unset($item);

		$personTypeID = intval($personTypeID);
		if($personTypeID <= 0)
		{
			$personTypes = CCrmPaySystem::getPersonTypeIDs();
			if (isset($personTypes['CONTACT']))
			{
				$personTypeID = intval($personTypes['CONTACT']);
			}
		}

		if ($personTypeID <= 0)
		{
			return array('err'=> '4');
		}

		$orderPropsValues = array();
		$paySystemId = 0;
		if (is_array($arOptions) && !empty($arOptions))
		{
			if (isset($arOptions['LOCATION_ID']) && CCrmTax::isTaxMode())
			{
				$locationPropertyID = self::getLocationPropertyId($personTypeID);
				if ($locationPropertyID !== false)
					$orderPropsValues[$locationPropertyID] = $arOptions['LOCATION_ID'];
			}
			if (isset($arOptions['PAY_SYSTEM_ID']))
				$paySystemId = intval($arOptions['PAY_SYSTEM_ID']);
		}
		$warnings = array();

		$options = array('CURRENCY' => $currencyID);
		if(!$enableSaleDiscount)
		{
			$options['CART_FIX'] = 'Y';
		}

		return CSaleOrder::DoCalculateOrder(
			$siteId,
			$saleUserId,
			$cartItems,
			$personTypeID,
			$orderPropsValues,
			0,
			$paySystemId,
			$options,
			$errors,
			$warnings
		);
	}
	private static function PrepareShoppingCartItems(&$productRows, $currencyID, $siteId)
	{
		$items = array();
		foreach($productRows as $k => &$v)
		{
			$item = array();
			$item['PRODUCT_ID'] = isset($v['PRODUCT_ID']) ? intval($v['PRODUCT_ID']) : 0;

			$isCustomized = isset($v['CUSTOMIZED']) && $v['CUSTOMIZED'] === 'Y';
			if($item['PRODUCT_ID'] > 0 && !$isCustomized)
			{
				$item['MODULE'] = 'catalog';
				$item['PRODUCT_PROVIDER_CLASS'] = 'CCatalogProductProvider';
			}
			else
			{
				$item['MODULE'] = $item['PRODUCT_PROVIDER_CLASS'] = '';
			}

			if($isCustomized)
			{
				$item['CUSTOM_PRICE'] = 'Y';
			}

			$item['TABLE_ROW_ID'] = $k;

			$item['QUANTITY'] = isset($v['QUANTITY']) ? doubleval($v['QUANTITY']) : 0;
			$item['QUANTITY_DEFAULT'] = $item['QUANTITY'];

			$item['PRICE'] = isset($v['PRICE']) ? doubleval($v['PRICE']) : 0.0;
			$item['PRICE_DEFAULT'] = $item['PRICE'];

			$item['CURRENCY'] = $currencyID;
			
			// discount info
			$item['CRM_PR_FIELDS'] = array();
			$item['CRM_PR_FIELDS']['DISCOUNT_TYPE_ID'] = isset($v['DISCOUNT_TYPE_ID']) ?
				intval($v['DISCOUNT_TYPE_ID']) : \Bitrix\Crm\Discount::PERCENTAGE;
			$item['CRM_PR_FIELDS']['DISCOUNT_RATE'] = isset($v['DISCOUNT_RATE']) ?
				round(doubleval($v['DISCOUNT_RATE']), 2) : 0.0;
			$item['CRM_PR_FIELDS']['DISCOUNT_SUM'] = isset($v['DISCOUNT_SUM']) ?
				round(doubleval($v['DISCOUNT_SUM']), 2) : 0.0;
			
			// tax info
			$taxRate = isset($v['TAX_RATE']) ? round(doubleval($v['TAX_RATE']), 2) : 0.0;
			$allowLDTax = CCrmTax::isTaxMode();
			if ($allowLDTax)
			{
				$item['CRM_PR_FIELDS']['TAX_RATE'] = 0.0;
				$item['CRM_PR_FIELDS']['TAX_INCLUDED'] = 'N';
			}
			else
			{
				$item['CRM_PR_FIELDS']['TAX_RATE'] = $taxRate;
				$item['CRM_PR_FIELDS']['TAX_INCLUDED'] =
					(isset($v['TAX_INCLUDED']) && $v['TAX_INCLUDED'] === 'Y') ? 'Y' : 'N';
			}

			// price netto, price brutto
			$exclusivePrice =
				($taxRate !== 0.0) ? CCrmProductRow::CalculateExclusivePrice($item['PRICE'], $taxRate) : $item['PRICE'];
			if($item['CRM_PR_FIELDS']['DISCOUNT_TYPE_ID'] === \Bitrix\Crm\Discount::MONETARY)
			{
				$priceNetto = $exclusivePrice + $item['CRM_PR_FIELDS']['DISCOUNT_SUM'];
				$item['CRM_PR_FIELDS']['PRICE_NETTO'] = round($priceNetto, 2);
			}
			else
			{
				$discoutRate = $item['CRM_PR_FIELDS']['DISCOUNT_RATE'];
				$discoutSum = $discoutRate < 100
					? \Bitrix\Crm\Discount::calculateDiscountByDiscountPrice($exclusivePrice, $discoutRate)
					: $item['CRM_PR_FIELDS']['DISCOUNT_SUM'];
				$priceNetto = $exclusivePrice + $discoutSum;
				$item['CRM_PR_FIELDS']['PRICE_NETTO'] = round($priceNetto, 2);
			}
			if ($item['CRM_PR_FIELDS']['DISCOUNT_SUM'] === 0.0)
			{
				$item['CRM_PR_FIELDS']['PRICE_BRUTTO'] = $item['PRICE'];
			}
			else
			{
				$item['CRM_PR_FIELDS']['PRICE_BRUTTO'] = round(
					CCrmProductRow::CalculateInclusivePrice($priceNetto, $item['CRM_PR_FIELDS']['TAX_RATE']), 2);
			}

			if(isset($v['VAT_RATE']))
			{
				$item['VAT_RATE'] = $v['VAT_RATE'];
			}
			elseif(isset($v['TAX_RATE']))
			{
				$item['VAT_RATE'] = $v['TAX_RATE'] / 100;
			}

			if(isset($v['MEASURE_CODE']))
			{
				$item['MEASURE_CODE'] = $v['MEASURE_CODE'];
			}

			if(isset($v['MEASURE_NAME']))
			{
				$item['MEASURE_NAME'] = $v['MEASURE_NAME'];
			}

			$item['NAME'] = isset($v['NAME']) ? $v['NAME'] : (isset($v['PRODUCT_NAME']) ? $v['PRODUCT_NAME'] : '');
			$item['LID'] = $siteId;
			$item['CAN_BUY'] = 'Y';

			$items[] = &$item;
			unset($item);
		}
		unset($v);

		return $items;
	}
	private static function getLocationPropertyId($personTypeId)
	{
		if(!CModule::IncludeModule('sale'))
		{
			return false;
		}

		$locationPropertyId = null;
		$dbOrderProps = CSaleOrderProps::GetList(
			array('SORT' => 'ASC'),
			array(
				'PERSON_TYPE_ID' => $personTypeId,
				'ACTIVE' => 'Y',
				'TYPE' => 'LOCATION',
				'IS_LOCATION' => 'Y',
				'IS_LOCATION4TAX' => 'Y'
			),
			false,
			false,
			array('ID', 'NAME', 'TYPE', 'IS_LOCATION', 'IS_LOCATION4TAX', 'REQUIED', 'SORT', 'CODE', 'DEFAULT_VALUE')
		);
		if ($arOrderProp = $dbOrderProps->Fetch())
			$locationPropertyId = $arOrderProp['ID'];
		else
			return false;
		$locationPropertyId = intval($locationPropertyId);
		if ($locationPropertyId <= 0)
			return false;
		return $locationPropertyId;
	}

}