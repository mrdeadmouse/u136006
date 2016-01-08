<?
IncludeModuleLangFile(__FILE__);

use Bitrix\Voximplant as VI;

class CVoxImplantConfig
{
	const MODE_LINK = 'LINK';
	const MODE_RENT = 'RENT';
	const MODE_SIP = 'SIP';

	const INTERFACE_CHAT_ADD = 'ADD';
	const INTERFACE_CHAT_APPEND = 'APPEND';
	const INTERFACE_CHAT_NONE = 'NONE';

	const CRM_CREATE_NONE = 'none';
	const CRM_CREATE_LEAD = 'lead';

	const LINK_BASE_NUMBER = 'LINK_BASE_NUMBER';

	public static function SetPortalNumber($number)
	{
		$numbers = self::GetPortalNumbers();
		if (!isset($numbers[$number]))
		{
			return false;
		}
		COption::SetOptionString("voximplant", "portal_number", $number);

		return true;
	}

	public static function GetPortalNumber()
	{
		return COption::GetOptionString("voximplant", "portal_number");
	}

	public static function SetPortalNumberByConfigId($configId)
	{
		$configId = intval($configId);
		if ($configId <= 0)
			return false;

		$orm = VI\ConfigTable::getList(Array(
			'filter'=>Array(
				'=ID' => $configId
			)
		));
		$element = $orm->fetch();
		if (!$element)
			return false;

		COption::SetOptionString("voximplant", "portal_number", $element['SEARCH_ID']);

		return true;
	}


	public static function GetPortalNumbers()
	{
		$result = Array();

		$res = VI\ConfigTable::getList();
		while ($row = $res->fetch())
		{
			if (strlen($row['PHONE_NAME']) <= 0)
			{
				$row['PHONE_NAME'] = substr($row['SEARCH_ID'], 0, 3) == 'reg'? GetMessage('VI_CONFIG_SIP_CLOUD_DEF'): GetMessage('VI_CONFIG_SIP_OFFICE_DEF');
				$row['PHONE_NAME'] = str_replace('#ID#', $row['ID'], $row['PHONE_NAME']);
			}
			$result[$row['SEARCH_ID']] = htmlspecialcharsbx($row['PHONE_NAME']);
		}

		$linkNumber = CVoxImplantPhone::GetLinkNumber();
		$result['LINK_BASE_NUMBER'] = $linkNumber == ''? GetMessage('VI_CONFIG_LINK_DEF'): '+'.$linkNumber;

		return $result;
	}

	public static function GetModeStatus($mode)
	{
		if (!in_array($mode, Array(self::MODE_LINK, self::MODE_RENT, self::MODE_SIP)))
			return false;

		if ($mode == self::MODE_SIP)
		{
			return COption::GetOptionString("main", "~PARAM_PHONE_SIP", 'N') == 'Y';
		}

		return COption::GetOptionString("voximplant", "mode_".strtolower($mode));
	}

	public static function SetModeStatus($mode, $enable)
	{
		if (!in_array($mode, Array(self::MODE_LINK, self::MODE_RENT, self::MODE_SIP)))
			return false;

		if ($mode == self::MODE_SIP)
		{
			COption::SetOptionString("main", "~PARAM_PHONE_SIP", $enable? 'Y': 'N');
		}
		else
		{
			COption::SetOptionString("voximplant", "mode_".strtolower($mode), $enable? true: false);
		}

		return true;
	}

	public static function GetChatAction()
	{
		return COption::GetOptionString("voximplant", "interface_chat_action");
	}

	public static function SetChatAction($action)
	{
		if (!in_array($action, Array(self::INTERFACE_CHAT_ADD, self::INTERFACE_CHAT_APPEND, self::INTERFACE_CHAT_NONE)))
			return false;

		COption::SetOptionString("voximplant", "interface_chat_action", $action);

		return true;
	}

	public static function GetLinkCallRecord()
	{
		return COption::GetOptionInt("voximplant", "link_call_record");
	}

	public static function SetLinkCallRecord($active)
	{
		$active = $active? true: false;

		return COption::SetOptionInt("voximplant", "link_call_record", $active);
	}

	public static function GetLinkCheckCrm()
	{
		return COption::GetOptionInt("voximplant", "link_check_crm");
	}

	public static function SetLinkCheckCrm($active)
	{
		$active = $active? true: false;

		return COption::SetOptionInt("voximplant", "link_check_crm", $active);
	}

	public static function GetDefaultMelodies($lang = 'EN')
	{
		if ($lang !== false)
		{
			$lang = strtoupper($lang);
			if (!in_array($lang, array('EN', 'DE', 'RU')))
				$lang = 'EN';
		}
		else
		{
			$lang = '#LANG_ID#';
		}

		return array(
			"MELODY_WELCOME" => "http://dl.bitrix24.com/vi/".$lang."01.mp3",
			"MELODY_WAIT" => "http://dl.bitrix24.com/vi/MELODY.mp3",
			"MELODY_HOLD" => "http://dl.bitrix24.com/vi/MELODY.mp3",
			"MELODY_VOICEMAIL" => "http://dl.bitrix24.com/vi/".$lang."03.mp3",
			"WORKTIME_DAYOFF_MELODY" => "http://dl.bitrix24.com/vi/".$lang."03.mp3",
		);
	}

	public static function GetMelody($name, $lang = 'EN', $fileId = 0)
	{
		$fileId = intval($fileId);

		$result = '';
		if ($fileId > 0)
		{
			$res = CFile::GetFileArray($fileId);
			if ($res && $res['MODULE_ID'] == 'voximplant')
			{
				if (substr($res['SRC'], 0, 4) == 'http' || substr($res['SRC'], 0, 2) == '//')
				{
					$result = $res['SRC'];
				}
				else
				{
					$result = CVoxImplantHttp::GetServerAddress().$res['SRC'];
				}
			}
		}

		if ($result == '')
		{
			$default = CVoxImplantConfig::GetDefaultMelodies($lang);
			$result = isset($default[$name])? $default[$name]: '';
		}

		return $result;
	}

	public static function GetConfigBySearchId($searchId)
	{
		if (strlen($searchId) <= 0)
		{
			return Array('ERROR' => 'Config is`t found for undefined number');
		}

		$orm = VI\ConfigTable::getList(Array(
			'filter'=>Array(
				'=SEARCH_ID' => (string)$searchId
			)
		));
		$config = $orm->fetch();
		if (!$config)
		{
			$result = Array('ERROR' => 'Config is`t found for number: '.$searchId);
		}
		else
		{
			$result = $config;

			$result['PHONE_TITLE'] = $result['PHONE_NAME'];
			if ($result['PORTAL_MODE'] == self::MODE_SIP)
			{
				$viSip = new CVoxImplantSip();
				$sipResult = $viSip->Get($config["ID"]);

				$result['PHONE_NAME'] = preg_replace("/[^0-9\#\*]/i", "", $result['PHONE_NAME']);
				$result['PHONE_NAME'] = strlen($result['PHONE_NAME']) >= 4? $result['PHONE_NAME']: '';

				$result['SIP_SERVER'] = $sipResult? $sipResult['SERVER']: '';
				$result['SIP_LOGIN'] = $sipResult? $sipResult['LOGIN']: '';
				$result['SIP_PASSWORD'] = $sipResult? $sipResult['PASSWORD']: '';
			}

			if (strlen($result['FORWARD_NUMBER']) > 0)
			{
				$result["FORWARD_NUMBER"] = NormalizePhone($result['FORWARD_NUMBER'], 1);
			}

			if (strlen($result['WORKTIME_DAYOFF_NUMBER']) > 0)
			{
				$result["WORKTIME_DAYOFF_NUMBER"] = NormalizePhone($result['WORKTIME_DAYOFF_NUMBER'], 1);
			}
			// check work time
			$result['WORKTIME_SKIP_CALL'] = 'N';
			if ($config['WORKTIME_ENABLE'] == 'Y')
			{
				$timezone = (!empty($config["WORKTIME_TIMEZONE"])) ? new DateTimeZone($config["WORKTIME_TIMEZONE"]) : null;
				$numberDate = new Bitrix\Main\Type\DateTime(null, null, $timezone);

				if (!empty($config['WORKTIME_DAYOFF']))
				{
					$daysOff = explode(",", $config['WORKTIME_DAYOFF']);

					$allWeekDays = array('MO' => 1, 'TU' => 2, 'WE' => 3, 'TH' => 4, 'FR' => 5, 'SA' => 6, 'SU' => 7);
					$currentWeekDay = $numberDate->format('N');
					foreach($daysOff as $day)
					{
						if ($currentWeekDay == $allWeekDays[$day])
						{
							$result['WORKTIME_SKIP_CALL'] = "Y";
						}
					}
				}
				if ($result['WORKTIME_SKIP_CALL'] !== "Y" && !empty($config['WORKTIME_HOLIDAYS']))
				{
					$holidays = explode(",", $config['WORKTIME_HOLIDAYS']);
					$currentDay = $numberDate->format('d.m');

					foreach($holidays as $holiday)
					{
						if ($currentDay == $holiday)
						{
							$result['WORKTIME_SKIP_CALL'] = "Y";
						}
					}
				}
				if ($result['WORKTIME_SKIP_CALL'] !== "Y" && !empty($config['WORKTIME_FROM']) && !empty($config['WORKTIME_TO']))
				{
					$currentTime = $numberDate->format('G.i');

					if (!($currentTime >= $config['WORKTIME_FROM'] && $currentTime <= $config['WORKTIME_TO']))
					{
						$result['WORKTIME_SKIP_CALL'] = "Y";
					}
				}

				if ($result['WORKTIME_SKIP_CALL'] === "Y")
				{
					$result['WORKTIME_DAYOFF_MELODY'] =  CVoxImplantConfig::GetMelody('WORKTIME_DAYOFF_MELODY', $config['MELODY_LANG'], $config['WORKTIME_DAYOFF_MELODY']);
				}
			}

			if (CVoxImplantHttp::GetPortalType() == CVoxImplantHttp::TYPE_BITRIX24)
				$result['PORTAL_URL'] = CVoxImplantHttp::GetServerAddress().'/settings/info_receiver.php?b24_action=phone&b24_direct=y';
			else
				$result['PORTAL_URL'] = CVoxImplantHttp::GetServerAddress().'/services/telephony/info_receiver.php?b24_direct=y';

			$result['PORTAL_SIGN'] = CVoxImplantHttp::GetPortalSign();
			$result['MELODY_WELCOME'] = CVoxImplantConfig::GetMelody('MELODY_WELCOME', $config['MELODY_LANG'], $config['MELODY_WELCOME']);
			$result['MELODY_VOICEMAIL'] =  CVoxImplantConfig::GetMelody('MELODY_VOICEMAIL', $config['MELODY_LANG'], $config['MELODY_VOICEMAIL']);
			$result['MELODY_HOLD'] =  CVoxImplantConfig::GetMelody('MELODY_HOLD', $config['MELODY_LANG'], $config['MELODY_HOLD']);
			$result['MELODY_WAIT'] =  CVoxImplantConfig::GetMelody('MELODY_WAIT', $config['MELODY_LANG'], $config['MELODY_WAIT']);
		}

		return $result;
	}

	public static function DeleteConfigBySearchId($searchId)
	{
		if (strlen($searchId) <= 0)
		{
			return Array('ERROR' => 'Config is`t found for undefined number');
		}

		$orm = VI\ConfigTable::getList(Array(
			'filter'=>Array(
				'=SEARCH_ID' => (string)$searchId
			)
		));
		$config = $orm->fetch();
		if (!$config)
		{
			$result = Array('ERROR' => 'Config is`t found for number: '.$searchId);
		}
		else
		{
			$orm = VI\QueueTable::getList(Array(
				'filter'=>Array(
					'=CONFIG_ID' => $config["ID"]
				)
			));
			while ($row = $orm->fetch())
			{
				VI\QueueTable::delete($row['ID']);
			}

			VI\ConfigTable::delete($config["ID"]);

			$result = Array('RESULT'=> 'OK', 'ERROR' => '');
		}

		return $result;
	}

	public static function GetNoticeOldConfigOfficePbx()
	{
		$result = false;
		if (COption::GetOptionString("voximplant", "notice_old_config_office_pbx") == 'Y' && CVoxImplantMain::CheckAccess())
		{
			$result = true;
		}

		return $result;
	}

	public static function HideNoticeOldConfigOfficePbx()
	{
		$result = false;

		COption::SetOptionString("voximplant", "notice_old_config_office_pbx", 'N');

		return $result;
	}

}
