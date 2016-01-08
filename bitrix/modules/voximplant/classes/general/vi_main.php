<?
IncludeModuleLangFile(__FILE__);

use Bitrix\Main\Type as FieldType;
use Bitrix\Voximplant as VI;
use Bitrix\Im as IM;

class CVoxImplantMain
{
	const CALL_OUTGOING = 1;
	const CALL_INCOMING = 2;
	const CALL_INCOMING_REDIRECT = 3;

	private $userId = 0;
	private $error = null;

	function __construct($userId)
	{
		$this->userId = intval($userId);
		if ($this->userId <= 0)
		{
			$this->error = new CVoxImplantError(__METHOD__, 'USER_ID', 'USER ID is not correct');
		}
		else
		{
			$this->error = new CVoxImplantError(null, '', '');
		}
	}

	public function Enable($number = '')
	{
		$enable = !IsModuleInstalled('extranet') || CModule::IncludeModule('extranet') && CExtranet::IsIntranetUser();
		if ($enable && strlen($number) > 0)
		{
			if (!CVoxImplantPhone::Normalize($number))
				$enable = false;
		}
		return $enable;
	}

	public function ClearUserInfo()
	{
		$ViUser = new CVoxImplantUser();
		$ViUser->ClearUserInfo($this->userId);
	}

	public function ClearAccountInfo()
	{
		$ViAccount = new CVoxImplantAccount();
		$ViAccount->ClearAccountInfo();
	}

	public function	GetDialogInfo($phone, $chatTitle = '', $getPhoto = true)
	{
		$phoneNormalize = CVoxImplantPhone::Normalize($phone);
		if (!$phoneNormalize)
		{
			$phoneNormalize = preg_replace("/[^0-9\#\*]/i", "", $phone);
		}
		$phone = $phoneNormalize;

		$hrPhoto = Array();

		$openChat = true;
		$result = VI\PhoneTable::getList(Array(
			'select' => Array('USER_ID', 'PHONE_MNEMONIC'),
			'filter' => Array('=PHONE_NUMBER' => $phone)
		));

		$userId = false;
		while ($row = $result->fetch())
		{
			if (!$userId && $row['PHONE_MNEMONIC'] != 'WORK_PHONE' )
			{
				$userId = $row['USER_ID'];
				$openChat = false;
			}
			else if (!$userId && $row['PHONE_MNEMONIC'] == 'WORK_PHONE' )
			{
				$openChat = true;
			}
		}

		if ($userId == $this->userId)
		{
			$openChat = true;
		}

		$dialogId = 0;
		if (CModule::IncludeModule('im'))
		{
			if (CVoxImplantConfig::GetChatAction() == CVoxImplantConfig::INTERFACE_CHAT_NONE)
			{
			}
			else if ($openChat)
			{
				$entityId = $phone;
				if (CVoxImplantConfig::GetChatAction() == CVoxImplantConfig::INTERFACE_CHAT_APPEND)
				{
					$entityId = 'UNIFY_CALL_CHAT';
					$chatTitle = GetMessage('VI_CALL_CHAT_UNIFY');
				}
				$result = IM\ChatTable::getList(Array(
					'select' => Array('ID', 'AVATAR'),
					'filter' => Array('=ENTITY_TYPE' => 'CALL', '=ENTITY_ID' => $entityId, '=AUTHOR_ID' => $this->userId)
				));

				if ($row = $result->fetch())
				{
					$dialogId = 'chat'.$row['ID'];
					$avatarId = $row['AVATAR'];
				}
				else
				{
					$CIMChat = new CIMChat($this->userId);
					$chatId = $CIMChat->Add(Array(
						'TITLE' => $chatTitle != ''? $chatTitle: $phone,
						'USERS' => false,
						'CALL_NUMBER' => $entityId == 'UNIFY_CALL_CHAT'? '': $entityId,
						'ENTITY_TYPE' => 'CALL',
						'ENTITY_ID' => $entityId,
					));
					if ($chatId)
					{
						$dialogId = 'chat'.$chatId;
						$avatarId = $CIMChat->lastAvatarId;
					}
				}
				if ($getPhoto && intval($avatarId) > 0)
				{
					$arPhotoHrTmp = CFile::ResizeImageGet(
						$avatarId,
						array('width' => 200, 'height' => 200),
						BX_RESIZE_IMAGE_EXACT,
						false,
						false,
						true
					);
					$hrPhoto[$dialogId] = empty($arPhotoHrTmp['src'])? '/bitrix/js/im/images/hidef-avatar-v2.png': $arPhotoHrTmp['src'];
				}
			}
			else if ($userId)
			{
				if ($getPhoto)
				{
					$userData = CIMContactList::GetUserData(Array('ID' => $userId, 'DEPARTMENT' => 'N', 'HR_PHOTO' => 'Y'));
					$hrPhoto = $userData['hrphoto'];
				}
				$dialogId = $userId;
			}
		}

		if (!$dialogId)
		{
			$this->error = new CVoxImplantError(__METHOD__, 'ERROR_NEW_CHAT', GetMessage('VI_ERROR_NEW_CHAT'));
			return false;
		}

		//foreach(GetModuleEvents("voximplant", "OnGetDialogInfo", true) as $arEvent)
		//	ExecuteModuleEventEx($arEvent, array('USER_ID' => $this->userId, 'DIALOG_ID' => $dialogId));

		return Array(
			'DIALOG_ID' => $dialogId,
			'HR_PHOTO' => $hrPhoto
		);
	}

	public function	SendChatMessage($dialogId, $incomingType, $message)
	{
		if (strlen($message) <= 0 || strlen($dialogId) <= 0)
			return false;

		if (CVoxImplantConfig::GetChatAction() == CVoxImplantConfig::INTERFACE_CHAT_NONE)
			return false;

		if (!CModule::IncludeModule('im'))
			return false;

		// TODO CHECK NULL USER BEFORE SEND

		$chatId = 0;
		if (substr($dialogId, 0, 4) == 'chat')
		{
			$chatId = intval(substr($dialogId, 4));

			CIMChat::AddMessage(Array(
				"FROM_USER_ID" => ($incomingType == CVoxImplantMain::CALL_OUTGOING? $this->userId: 0),
				"TO_CHAT_ID" => $chatId,
				"MESSAGE" => $message,
				"SYSTEM" => 'Y',
			));
		}
		else if (intval($dialogId) > 0)
		{
			CIMMessage::Add(Array(
				"FROM_USER_ID" => ($incomingType == CVoxImplantMain::CALL_OUTGOING? $this->userId: intval($dialogId)),
				"TO_USER_ID" => intval($dialogId),
				"MESSAGE" => $message,
				"SYSTEM" => 'Y',
			));
		}

		return true;
	}

	public function GetAuthorizeInfo($updateInfo = false)
	{
		$ViAccount = new CVoxImplantAccount();
		if ($updateInfo)
			$ViAccount->UpdateAccountInfo();

		$ViUser = new CVoxImplantUser();
		$userInfo = $ViUser->GetUserInfo($this->userId);
		if (!$userInfo)
		{
			$this->error = new CVoxImplantError(__METHOD__, $ViUser->GetError()->code, GetMessage('VI_GET_USER_INFO', Array('#CODE#' => $ViUser->GetError()->code)));
			return false;
		}

		$userData = CIMContactList::GetUserData(Array('ID' => $this->userId, 'DEPARTMENT' => 'N', 'HR_PHOTO' => 'Y'));

		return Array(
			'SERVER' => str_replace('voximplant.com', 'bitrixphone.com', $userInfo['call_server']),
			'LOGIN' => $userInfo['user_login'],
			'CALLERID' => $userInfo['user_backphone'],
			'HR_PHOTO' => $userData['hrphoto']
		);
	}

	public function GetOneTimeKey($key)
	{
		$ViAccount = new CVoxImplantAccount();
		$accountName = $ViAccount->GetAccountName();
		if (!$accountName)
		{
			$this->error = new CVoxImplantError(__METHOD__, $ViAccount->GetError()->code, GetMessage('VI_GET_ACCOUNT_INFO', Array('#CODE#' => $ViAccount->GetError()->code)));
			return false;
		}

		$ViUser = new CVoxImplantUser();
		$userInfo = $ViUser->GetUserInfo($this->userId);
		if (!$userInfo)
		{
			$this->error = new CVoxImplantError(__METHOD__, $ViUser->GetError()->code, GetMessage('VI_GET_USER_INFO', Array('#CODE#' => $ViUser->GetError()->code)));
			return false;
		}

		return md5($key."|".md5($userInfo['user_login'].":voximplant.com:".$userInfo['user_password']));
	}

	public static function CallStart($callId, $userId, $callDevice = 'WEBRTC', $external = false)
	{
		// TODO check $callId, $userId
		$res = Bitrix\Voximplant\CallTable::getList(Array(
			'select' => Array('ID', 'CALL_ID', 'CALLER_ID', 'DATE_CREATE', 'CRM_LEAD'),
			'filter' => Array('=CALL_ID' => $callId),
		));
		if ($call = $res->fetch())
		{
			$crmData = false;
			if ($call['CRM_LEAD'] > 0)
			{
				CVoxImplantCrmHelper::UpdateLead($call['CRM_LEAD'], Array('ASSIGNED_BY_ID' => $userId));
				$crmData = CVoxImplantCrmHelper::GetDataForPopup($call['CALL_ID'], $call['CALLER_ID'], $userId);
			}

			Bitrix\Voximplant\CallTable::update($call['ID'], Array('USER_ID'=> $userId, 'STATUS' => Bitrix\Voximplant\CallTable::STATUS_CONNECTED));

			foreach(GetModuleEvents("voximplant", "onCallStart", true) as $arEvent)
			{
				ExecuteModuleEventEx($arEvent, Array(Array(
					'CALL_ID' => $call['CALL_ID'],
					'USER_ID' => $userId,
				)));
			}

			self::SendPullEvent(Array(
				'COMMAND' => 'start',
				'USER_ID' => $userId,
				'CALL_ID' => $callId,
				'CALL_DEVICE' => $callDevice,
				'EXTERNAL' => $external? true: false,
				'CRM' => $crmData,
			));
		}
	}

	public static function CallHold($callId, $result = false)
	{
		$res = VI\CallTable::getList(Array(
			'select' => Array('ID', 'CALL_ID', 'CALLER_ID', 'USER_ID', 'TRANSFER_USER_ID', 'ACCESS_URL'),
			'filter' => Array('=CALL_ID' => $callId),
		));
		$call = $res->fetch();
		if (!$call)
			return false;

		$answer['COMMAND'] = $result? 'hold': 'unhold';
		$answer['OPERATOR_ID'] = $call['USER_ID'];

		$http = new \Bitrix\Main\Web\HttpClient();
		$http->waitResponse(false);
		$http->post($call['ACCESS_URL'], json_encode($answer));

		self::SendPullEvent(Array(
			'COMMAND' => $result? 'hold': 'unhold',
			'USER_ID' => $call['USER_ID'],
			'CALL_ID' => $call['CALL_ID']
		));

		return true;
	}

	public static function SendPullEvent($params)
	{
		if (!CModule::IncludeModule('pull') || !CPullOptions::GetQueueServerStatus() || $params['USER_ID'] <= 0)
			return false;

		$config = Array();
		if ($params['COMMAND'] == 'start')
		{
			$config = Array(
				"callId" => $params['CALL_ID'],
				"callDevice" => $params['CALL_DEVICE'] == 'PHONE'? 'PHONE': 'WEBRTC',
				"external" => $params['EXTERNAL']? true: false,
				"CRM" => $params['CRM']? $params['CRM']: false,
			);
		}
		else if ($params['COMMAND'] == 'hold' || $params['COMMAND'] == 'unhold')
		{
			$config = Array(
				"callId" => $params['CALL_ID'],
			);
		}
		else if ($params['COMMAND'] == 'timeout')
		{
			$config = Array(
				"callId" => $params['CALL_ID'],
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


	public static function CheckAccess()
	{
		global $USER;

		$result = false;
		if (IsModuleInstalled('bitrix24'))
		{
			if (is_object($USER) && intval($USER->GetID()) && $USER->CanDoOperation('bitrix24_config'))
			{
				$result = true;
			}
		}
		else
		{
			if (is_object($USER) && intval($USER->GetID()) && $USER->IsAdmin())
			{
				$result = true;
			}
		}

		return $result;
	}

	public static function GetTelephonyStatistic()
	{
		$arMonthlyStat = COption::GetOptionString("voximplant", "telephony_statistic", "");
		if ($arMonthlyStat)
		{
			$arMonthlyStat = unserialize($arMonthlyStat);
		}
		else
		{
			$arMonthlyStat = array();
		}

		$lastUncountedMonth = COption::GetOptionString("voximplant", "telephony_statistic_last_month", "");  //last month which wasn't counted
		if ($lastUncountedMonth)
		{
			$lastUncountedMonth = unserialize($lastUncountedMonth);
		}
		else
		{
			$lastUncountedMonth = Array();
		}

		$curLastMonth = array();
		$curLastMonth["MM"] = date("m");
		$curLastMonth["YYYY"] = date("Y");

		if (date("m") != $lastUncountedMonth["MM"] || date("Y") != $lastUncountedMonth["YYYY"])  //current month is not last month which wasn't counted
		{
			$firstDayCurMonth = ConvertTimeStamp(MakeTimeStamp("01.".date("m").".".date("Y"), "DD.MM.YYYY"));

			if (!empty($lastUncountedMonth))
			{
				$firstUncountedDay = ConvertTimeStamp(MakeTimeStamp("01.".$lastUncountedMonth["MM"].".".$lastUncountedMonth["YYYY"], "DD.MM.YYYY"));
				$arFilter = array(
					array(
						'LOGIC' => 'AND',
						'>CALL_START_DATE' => $firstUncountedDay,
						'<CALL_START_DATE' => $firstDayCurMonth
					)
				);
			}
			else
			{
				$arFilter = array(
					array(
						'LOGIC' => 'AND',
						'>CALL_START_DATE' => ConvertTimeStamp(MakeTimeStamp("04.02.2014", "DD.MM.YYYY")), // correct start date for counting statistics
						'<CALL_START_DATE' => $firstDayCurMonth
					)
				);
			}

			$parameters = array(
				'order' => array('CALL_START_DATE'=>'DESC'),
				'filter' => $arFilter,
				'select' => array('COST', 'COST_CURRENCY', 'CALL_DURATION', 'CALL_START_DATE'),
			);
			$dbStat = VI\StatisticTable::getList($parameters);

			$curPortalCurrency = "";

			while($arData = $dbStat->fetch())
			{
				$arData["COST_CURRENCY"] = ($arData["COST_CURRENCY"] == "RUR" ? "RUB" : $arData["COST_CURRENCY"]);

				if (!$curPortalCurrency)
					$curPortalCurrency = $arData["COST_CURRENCY"];

				$arDateParse = ParseDateTime($arData["CALL_START_DATE"]);
				$arMonthlyStat[$arDateParse["YYYY"]][$arDateParse["MM"]]["CALL_DURATION"] += $arData["CALL_DURATION"];

				$arMonthlyStat[$arDateParse["YYYY"]][$arDateParse["MM"]]["COST"] += $arData["COST"];
		//		$arMonthlyStat[$arDateParse["YYYY"]][$arDateParse["MM"]]["COST"] = number_format($arMonthlyStat[$arDateParse["YYYY"]][$arDateParse["MM"]]["COST"], 4);
				$arMonthlyStat[$arDateParse["YYYY"]][$arDateParse["MM"]]["COST_CURRENCY"] = $curPortalCurrency;
			}

			if (!empty($arMonthlyStat))
			{
				krsort ($arMonthlyStat);
				foreach($arMonthlyStat as $year => $arYear)
				{
					krsort ($arYear);
					$arMonthlyStat[$year] = $arYear;
				}

				COption::SetOptionString("voximplant", "telephony_statistic", serialize($arMonthlyStat));
				COption::SetOptionString("voximplant", "telephony_statistic_last_month", serialize($curLastMonth));
			}
		}

		return $arMonthlyStat;
	}

	public static function CountTelephonyStatisticAgent()
	{
		$arStat = self::GetTelephonyStatistic();

		return "CVoxImplantMain::CountTelephonyStatisticAgent();";
	}

	public static function GetPublicFolder()
	{
		return CVoxImplantHttp::GetPortalType() == CVoxImplantHttp::TYPE_BITRIX24? '/settings/telephony/': '/services/telephony/';
	}

	public function GetError()
	{
		return $this->error;
	}
}
?>
