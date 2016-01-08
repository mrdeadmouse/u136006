<?
if(!CModule::IncludeModule('rest'))
	return;

class CVoxImplantRestService extends IRestService
{
	public static function OnRestServiceBuildDescription()
	{
		return array(
			'telephony' => array(
				'voximplant.url.get' => array('CVoxImplantRestService', 'urlGet'),
				'voximplant.sip.get' => array('CVoxImplantRestService', 'sipGet'),
				'voximplant.sip.add' => array('CVoxImplantRestService', 'sipAdd'),
				'voximplant.sip.update' => array('CVoxImplantRestService', 'sipUpdate'),
				'voximplant.sip.delete' => array('CVoxImplantRestService', 'sipDelete'),
				'voximplant.sip.status' => array('CVoxImplantRestService', 'sipStatus'),
				'voximplant.sip.connector.status' => array('CVoxImplantRestService', 'sipConnectorStatus'),
				'voximplant.statistic.get' => array('CVoxImplantRestService', 'statisticGet'),
				'voximplant.line.outgoing.set' => array('CVoxImplantRestService', 'lineOutgoingSet'),
				'voximplant.line.outgoing.get' => array('CVoxImplantRestService', 'lineOutgoingGet'),
				'voximplant.line.outgoing.sip.set' => array('CVoxImplantRestService', 'lineOutgoingSipSet'),
				'voximplant.line.get' => array('CVoxImplantRestService', 'lineGet'),
				CRestUtil::EVENTS => array(
					'OnVoximplantCallInit' => array('voximplant', 'onCallInit'),
					'OnVoximplantCallStart' => array('voximplant', 'onCallStart'),
					'OnVoximplantCallEnd' => array('voximplant', 'onCallEnd', array(self, 'onCallEnd')),
				),
			),
		);
	}

	public static function urlGet()
	{
		if (!CVoxImplantMain::CheckAccess())
		{
			throw new \Bitrix\Rest\AccessException();
		}

		return Array(
			'detail_statistics' => CVoxImplantHttp::GetServerAddress().'/settings/telephony/detail.php',
			'buy_connector' => CVoxImplantHttp::GetServerAddress().'/settings/license_phone_sip.php',
			'edit_config' => CVoxImplantHttp::GetServerAddress().'/settings/telephony/edit.php?ID=#CONFIG_ID#',
		);
	}

	public static function sipGet($arParams, $nav, $server)
	{
		if (!CVoxImplantMain::CheckAccess())
		{
			throw new \Bitrix\Rest\AccessException();
		}

		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$sort = $arParams['SORT'];
		$order = $arParams['ORDER'];

		if(isset($arParams['FILTER']) && is_array($arParams['FILTER']))
		{
			$arFilter = array_change_key_case($arParams['FILTER'], CASE_UPPER);
		}
		else
		{
			$arFilter = array();
		}
		$arFilter['APP_ID'] = $server->getAppId();

		$arReturn = array();

		$dbResCnt = \Bitrix\Voximplant\SipTable::getList(array(
			'filter' => $arFilter,
			'select' => array("CNT" => new Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(1)')),
		));
		$arResCnt = $dbResCnt->fetch();
		if ($arResCnt && $arResCnt["CNT"] > 0)
		{
			$arNavParams = self::getNavData($nav, true);

			$arSort = array();
			if($sort && $order)
			{
				$arSort[$sort] = $order;
			}

			$dbRes = \Bitrix\Voximplant\SipTable::getList(array(
				'order' => $arSort,
				'select' => Array('*', 'TITLE'),
				'filter' => $arFilter,
				'limit' => $arNavParams['limit'],
				'offset' => $arNavParams['offset'],
			));

			$result = array();
			while($arData = $dbRes->fetch())
			{
				unset($arData['ID']);
				unset($arData['APP_ID']);
				if ($arData['TYPE'] == CVoxImplantSip::TYPE_CLOUD)
				{
					unset($arData['INCOMING_SERVER']);
					unset($arData['INCOMING_LOGIN']);
					unset($arData['INCOMING_PASSWORD']);
				}
				else
				{
					unset($arData['REG_ID']);
				}
				$result[] = $arData;
			}

			return self::setNavData(
				$result,
				array(
					"count" => $arResCnt['CNT'],
					"offset" => $arNavParams['offset']
				)
			);
		}

		return $arReturn;
	}

	public static function sipAdd($arParams, $nav, $server)
	{
		if (!CVoxImplantMain::CheckAccess())
		{
			throw new \Bitrix\Rest\AccessException();
		}
		$arParams = array_change_key_case($arParams, CASE_UPPER);

		if (!isset($arParams['TYPE']))
		{
			$arParams['TYPE'] = CVoxImplantSip::TYPE_CLOUD;
		}

		$viSip = new CVoxImplantSip();
		$configId = $viSip->Add(Array(
			'TYPE' => strtolower($arParams['TYPE']),
			'PHONE_NAME' => $arParams['TITLE'],
			'SERVER' => $arParams['SERVER'],
			'LOGIN' => $arParams['LOGIN'],
			'PASSWORD' => $arParams['PASSWORD'],
			'APP_ID' => $server->getAppId()
		));
		if (!$configId || $viSip->GetError()->error)
		{
			throw new Bitrix\Rest\RestException($viSip->GetError()->msg, $viSip->GetError()->code, CRestServer::STATUS_WRONG_REQUEST);
		}

		$result = $viSip->Get($configId, Array('WITH_TITLE' => true));
		unset($result['APP_ID']);
		unset($result['REG_STATUS']);

		return $result;
	}

	public static function sipUpdate($arParams, $nav, $server)
	{
		if (!CVoxImplantMain::CheckAccess())
		{
			throw new \Bitrix\Rest\AccessException();
		}
		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$dbResCnt = \Bitrix\Voximplant\SipTable::getList(array(
			'filter' => Array(
				'CONFIG_ID' => $arParams["CONFIG_ID"],
				'APP_ID' => $server->getAppId()
			),
			'select' => array("CNT" => new Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(1)')),
		));
		$arResCnt = $dbResCnt->fetch();
		if (!$arResCnt || $arResCnt["CNT"] <= 0)
		{
			throw new Bitrix\Rest\RestException("Specified CONFIG_ID is not found", Bitrix\Rest\RestException::ERROR_NOT_FOUND, CRestServer::STATUS_NOT_FOUND);
		}

		if (!isset($arParams['TYPE']))
		{
			$arParams['TYPE'] = CVoxImplantSip::TYPE_CLOUD;
		}

		$arUpdate = Array(
			'TYPE' => $arParams['TYPE'],
			'NEED_UPDATE' => "Y",
		);
		if (isset($arParams['TITLE']))
			$arUpdate['TITLE'] = $arParams['TITLE'];
		if (isset($arParams['SERVER']))
			$arUpdate['SERVER'] = $arParams['SERVER'];
		if (isset($arParams['LOGIN']))
			$arUpdate['LOGIN'] = $arParams['LOGIN'];
		if (isset($arParams['PASSWORD']))
			$arUpdate['PASSWORD'] = $arParams['PASSWORD'];

		if (count($arUpdate) == 2)
		{
			return 1;
		}

		$viSip = new CVoxImplantSip();
		$result = $viSip->Update($arParams["CONFIG_ID"], $arUpdate);
		if (!$result || $viSip->GetError()->error)
		{
			throw new Bitrix\Rest\RestException($viSip->GetError()->msg, $viSip->GetError()->code, CRestServer::STATUS_WRONG_REQUEST);
		}

		return 1;
	}

	public static function sipDelete($arParams, $nav, $server)
	{
		if (!CVoxImplantMain::CheckAccess())
		{
			throw new \Bitrix\Rest\AccessException();
		}
		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$dbResCnt = \Bitrix\Voximplant\SipTable::getList(array(
			'filter' => Array(
				'CONFIG_ID' => $arParams["CONFIG_ID"],
				'APP_ID' => $server->getAppId()
			),
			'select' => array("CNT" => new Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(1)')),
		));
		$arResCnt = $dbResCnt->fetch();
		if (!$arResCnt || $arResCnt["CNT"] <= 0)
		{
			throw new Bitrix\Rest\RestException("Specified CONFIG_ID is not found", Bitrix\Rest\RestException::ERROR_NOT_FOUND, CRestServer::STATUS_WRONG_REQUEST);
		}

		$viSip = new CVoxImplantSip();
		$result = $viSip->Delete($arParams['CONFIG_ID']);
		if (!$result || $viSip->GetError()->error)
		{
			throw new Bitrix\Rest\RestException($viSip->GetError()->msg, $viSip->GetError()->code, CRestServer::STATUS_WRONG_REQUEST);
		}

		return 1;
	}

	public static function sipStatus($arParams)
	{
		if (!CVoxImplantMain::CheckAccess())
		{
			throw new \Bitrix\Rest\AccessException();
		}
		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$viSip = new CVoxImplantSip();
		$result = $viSip->GetSipRegistrations($arParams['REG_ID']);

		if (!$result)
		{
			throw new Bitrix\Rest\RestException($viSip->GetError()->msg, $viSip->GetError()->code, CRestServer::STATUS_WRONG_REQUEST);
		}

		return Array(
			'REG_ID' => $result->reg_id,
			'LAST_UPDATED' => $result->last_updated,
			'ERROR_MESSAGE' => $result->error_message,
			'STATUS_CODE' => $result->status_code,
			'STATUS_RESULT' => $result->status_result,
		);
	}

	public static function sipConnectorStatus()
	{
		if (!CVoxImplantMain::CheckAccess())
		{
			throw new \Bitrix\Rest\AccessException();
		}
		$ViHttp = new CVoxImplantHttp();
		$info = $ViHttp->GetSipInfo();
		if (!$info || $ViHttp->GetError()->error)
		{
			throw new Bitrix\Rest\RestException($ViHttp->GetError()->msg, $ViHttp->GetError()->code, CRestServer::STATUS_WRONG_REQUEST);
		}

		$result = array(
			'FREE_MINUTES' => intval($info->FREE),
			'PAID' => $info->ACTIVE,
		);

		if ($info->ACTIVE)
		{
			$result['PAID_DATE_END'] = CRestUtil::ConvertDate($info->DATE_END);
		}

		return $result;
	}

	public static function statisticGet($arParams, $nav, $server)
	{
		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$sort = $arParams['SORT'];
		$order = $arParams['ORDER'];

		if(isset($arParams['FILTER']) && is_array($arParams['FILTER']))
		{
			$arFilter = array_change_key_case($arParams['FILTER'], CASE_UPPER);
		}
		else
		{
			$arFilter = array();
		}

		if (!CVoxImplantMain::CheckAccess())
		{
			$arFilter['USER_ID'] = $GLOBALS['USER']->GetID();
		}

		$arReturn = array();

		$dbResCnt = \Bitrix\Voximplant\StatisticTable::getList(array(
			'filter' => $arFilter,
			'select' => array("CNT" => new Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(1)')),
		));
		$arResCnt = $dbResCnt->fetch();
		if ($arResCnt && $arResCnt["CNT"] > 0)
		{
			$arNavParams = self::getNavData($nav, true);

			$arSort = array();
			if($sort && $order)
			{
				$arSort[$sort] = $order;
			}

			$dbRes = \Bitrix\Voximplant\StatisticTable::getList(array(
				'order' => $arSort,
				'filter' => $arFilter,
				'limit' => $arNavParams['limit'],
				'offset' => $arNavParams['offset'],
			));

			$result = array();
			while($arData = $dbRes->fetch())
			{
				unset($arData['ACCOUNT_ID']);
				unset($arData['APPLICATION_ID']);
				unset($arData['APPLICATION_NAME']);
				unset($arData['CALL_LOG']);
				unset($arData['CALL_RECORD_ID']);
				unset($arData['CALL_WEBDAV_ID']);
				unset($arData['CALL_STATUS']);
				unset($arData['CALL_DIRECTION']);
				$arData['CALL_TYPE'] = $arData['INCOMING'];
				unset($arData['INCOMING']);
				$arData['CALL_START_DATE'] = CRestUtil::ConvertDateTime($arData['CALL_START_DATE']);
				$result[] = $arData;
			}

			return self::setNavData(
				$result,
				array(
					"count" => $arResCnt['CNT'],
					"offset" => $arNavParams['offset']
				)
			);
		}

		return $arReturn;
	}

	public static function lineGet()
	{
		if (!CVoxImplantMain::CheckAccess())
		{
			throw new \Bitrix\Rest\AccessException();
		}
		return CVoxImplantConfig::GetPortalNumbers();
	}

	public static function lineOutgoingSipSet($arParams)
	{
		if (!CVoxImplantMain::CheckAccess())
		{
			throw new \Bitrix\Rest\AccessException();
		}
		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$result = CVoxImplantConfig::SetPortalNumberByConfigId($arParams['CONFIG_ID']);
		if (!$result)
		{
			throw new Bitrix\Rest\RestException('Specified CONFIG_ID is not found', Bitrix\Rest\RestException::ERROR_ARGUMENT, CRestServer::STATUS_WRONG_REQUEST);
		}

		return 1;
	}

	public static function lineOutgoingSet($arParams)
	{
		if (!CVoxImplantMain::CheckAccess())
		{
			throw new \Bitrix\Rest\AccessException();
		}
		$arParams = array_change_key_case($arParams, CASE_UPPER);

		CVoximplantConfig::SetPortalNumber($arParams['LINE_ID']);

		return 1;
	}

	public static function lineOutgoingGet()
	{
		if (!CVoxImplantMain::CheckAccess())
		{
			throw new \Bitrix\Rest\AccessException();
		}
		return CVoximplantConfig::GetPortalNumber();
	}

	public static function onCallEnd($arParams)
	{
		$arParams['CALL_START_DATE'] = CRestUtil::ConvertDateTime($arParams['CALL_START_DATE']);
		return $arParams;
	}
}
?>