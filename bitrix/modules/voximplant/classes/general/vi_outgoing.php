<?
IncludeModuleLangFile(__FILE__);

use Bitrix\Main\Type as FieldType;
use Bitrix\Main\Entity\Query;
use Bitrix\Voximplant as VI;

class CVoxImplantOutgoing
{
	public static function Init($params)
	{
		$callAdd = true;
		if ($params['CALL_ID_TMP'])
		{
			$res = VI\CallTable::getList(Array(
				'filter' => Array('=CALL_ID' => $params['CALL_ID_TMP']),
			));
			if ($call = $res->fetch())
			{
				$result = VI\CallTable::update($call['ID'], Array(
					'CONFIG_ID' => $params['CONFIG_ID'],
					'CALL_ID' => $params['CALL_ID'],
					'CRM' => $params['CRM'],
					'USER_ID' => $params['USER_ID'],
					'CALLER_ID' => $params['PHONE_NUMBER'],
					'STATUS' => VI\CallTable::STATUS_CONNECTING,
					'ACCESS_URL' => $params['ACCESS_URL'],
				));
				if ($result)
				{
					$callAdd = false;
				}
			}
		}
		if ($callAdd)
		{
			VI\CallTable::add(Array(
				'CONFIG_ID' => $params['CONFIG_ID'],
				'CALL_ID' => $params['CALL_ID'],
				'CRM' => $params['CRM'],
				'USER_ID' => $params['USER_ID'],
				'CALLER_ID' => $params['PHONE_NUMBER'],
				'STATUS' => VI\CallTable::STATUS_CONNECTING,
				'ACCESS_URL' => $params['ACCESS_URL'],
				'DATE_CREATE' => new FieldType\DateTime(),
			));
		}

		if ($params['CRM'] == 'Y')
		{
			CVoxImplantCrmHelper::AddCall(Array(
				'CALL_ID' => $params['CALL_ID'],
				'PHONE_NUMBER' => $params['PHONE_NUMBER'],
				'INCOMING' => CVoxImplantMain::CALL_OUTGOING,
				'USER_ID' => $params['USER_ID'],
				'DATE_CREATE' => new FieldType\DateTime()
			));

			$crmData = CVoxImplantCrmHelper::GetDataForPopup($params['CALL_ID'], $params['PHONE_NUMBER'], $params['USER_ID']);
		}
		else
		{
			$crmData = Array();
		}

		self::SendPullEvent(Array(
			'COMMAND' => 'outgoing',
			'USER_ID' => $params['USER_ID'],
			'CALL_ID' => $params['CALL_ID'],
			'CALL_ID_TMP' => $params['CALL_ID_TMP'],
			'CALL_DEVICE' => $params['CALL_DEVICE'],
			'PHONE_NUMBER' => $params['PHONE_NUMBER'],
			'EXTERNAL' => $params['CALL_ID_TMP']? true: false,
			'CRM' => $crmData,
		));

		return true;
	}

	public static function GetLinkConfig()
	{
		$portalUrl = '';
		if (CVoxImplantHttp::GetPortalType() == CVoxImplantHttp::TYPE_BITRIX24)
			$portalUrl = CVoxImplantHttp::GetServerAddress().'/settings/info_receiver.php?b24_action=phone&b24_direct=y';
		else
			$portalUrl = CVoxImplantHttp::GetServerAddress().'/services/telephony/info_receiver.php?b24_direct=y';

		return Array(
			'PORTAL_MODE' => 'LINK',
			'PORTAL_URL' => $portalUrl,
			'PORTAL_SIGN' => CVoxImplantHttp::GetPortalSign(),
			'SEARCH_ID' => CVoxImplantPhone::GetLinkNumber(),
			'PHONE_NAME' => CVoxImplantPhone::GetLinkNumber(), // TODO add "+" in next version
			'RECORDING' => CVoxImplantConfig::GetLinkCallRecord()? 'Y': 'N',
			'CRM' => CVoxImplantConfig::GetLinkCheckCrm()? 'Y': 'N',
			'MELODY_HOLD' => CVoxImplantConfig::GetMelody('MELODY_HOLD'),
		);
	}

	public static function GetConfigByUserId($userId)
	{
		$userId = intval($userId);
		if ($userId > 0)
		{
			$viUser = new CVoxImplantUser();
			$userInfo = $viUser->GetUserInfo($userId);
			if ($userInfo['user_backphone'] == '')
			{
				$userInfo['user_backphone'] = CVoxImplantConfig::LINK_BASE_NUMBER;
			}
		}
		else
		{
			$userInfo = Array();
			$userInfo['user_backphone'] = CVoxImplantConfig::GetPortalNumber();
			$userInfo['user_extranet'] = false;
		}

		if ($userInfo['user_extranet'])
		{
			$result = Array('error' => Array('code' => 'EXTRANAET', 'msg' => 'Extranet user can not use telephony'));
		}
		else if ($userInfo['user_backphone'] == CVoxImplantPhone::GetLinkNumber() || $userInfo['user_backphone'] == CVoxImplantConfig::LINK_BASE_NUMBER)
		{
			$result = self::GetLinkConfig();
		}
		else
		{
			$result = CVoxImplantConfig::GetConfigBySearchId($userInfo['user_backphone']);
			if (isset($result['ERROR']) && strlen($result['ERROR']) > 0)
			{
				$result = self::GetLinkConfig();
			}
		}

		$result['USER_ID'] = $userId;

		return $result;
	}

	public static function SendPullEvent($params)
	{
		// TODO check params

		if (!CModule::IncludeModule('pull') || !CPullOptions::GetQueueServerStatus() || $params['USER_ID'] <= 0)
			return false;

		$config = Array();
		if ($params['COMMAND'] == 'outgoing')
		{
			$config = Array(
				"callId" => $params['CALL_ID'],
				"callIdTmp" => $params['CALL_ID_TMP']? $params['CALL_ID_TMP']: '',
				"callDevice" => $params['CALL_DEVICE'] == 'PHONE'? 'PHONE': 'WEBRTC',
				"phoneNumber" => $params['PHONE_NUMBER'],
				"external" => $params['EXTERNAL']? true: false,
				"CRM" => $params['CRM']? $params['CRM']: Array(),
			);
		}
		else if ($params['COMMAND'] == 'timeout')
		{
			$config = Array(
				"callId" => $params['CALL_ID'],
				"failedCode" => intval($params['FAILED_CODE']),
			);
		}
		CPullStack::AddByUser($params['USER_ID'],
			Array(
				'module_id' => 'voximplant',
				'command' => $params['COMMAND'],
				'params' => $config
			)
		);

		return true;
	}

	public static function StartCall($userId, $phoneNumber)
	{
		$phoneNormalized = CVoxImplantPhone::Normalize($phoneNumber);

		$userId = intval($userId);
		if ($userId <= 0 || !$phoneNormalized)
			return false;

		$call = VI\CallTable::add(Array(
			'CALL_ID' => 'temp.'.md5($userId.$phoneNumber).time(),
			'USER_ID' => $userId,
			'CALLER_ID' => $phoneNormalized,
			'STATUS' => VI\CallTable::STATUS_CONNECTING,
			'DATE_CREATE' => new FieldType\DateTime(),
		));

		$viHttp = new CVoxImplantHttp();
		$result = $viHttp->StartOutgoingCall($userId, $phoneNumber);

		VI\CallTable::update($call->GetId(), Array(
			'CALL_ID' => $result->call_id,
			'ACCESS_URL' => $result->access_url,
			'DATE_CREATE' => new FieldType\DateTime(),
		));

		self::SendPullEvent(Array(
			'COMMAND' => 'outgoing',
			'USER_ID' => $userId,
			'PHONE_NUMBER' => $phoneNormalized,
			'CALL_ID' => $result->call_id,
			'CALL_DEVICE' => 'PHONE',
			'EXTERNAL' => true,
		));

		return $result? true: false;
	}
}
?>
