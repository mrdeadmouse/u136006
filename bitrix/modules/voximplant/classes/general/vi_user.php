<?
IncludeModuleLangFile(__FILE__);

class CVoxImplantUser
{
	private $user_name = null;
	private $user_password = null;
	private $error = null;

	const MODE_USER = 'USER';
	const MODE_PHONE = 'PHONE';
	const MODE_SIP = 'SIP';

	function __construct()
	{
		$this->error = new CVoxImplantError(null, '', '');
	}

	public function GetUser($userId, $getPhoneAccess = false, $skipUpdateAccount = false)
	{
		$userId = intval($userId);
		if ($userId <= 0)
			return false;

		$ViHttp = new CVoxImplantHttp();
		$result = $ViHttp->GetUser($userId, $getPhoneAccess);
		if (!$result || $ViHttp->GetError()->error)
		{
			$this->error = new CVoxImplantError(__METHOD__, $ViHttp->GetError()->code, $ViHttp->GetError()->msg);
			return false;
		}

		if (!$skipUpdateAccount)
		{
			$ViAccount = new CVoxImplantAccount();
			$ViAccount->SetAccountName($result->account_name);
			$ViAccount->SetAccountBalance($result->account_balance);
			$ViAccount->SetAccountCurrency($result->account_currency);
		}

		return $result;
	}

	public function GetUsers($userId = Array(), $getOneUser = false, $skipUpdateAccount = false)
	{
		if (!is_array($userId))
			$userId = Array($userId);

		foreach($userId as $key => $value)
			$userId[$key] = intval($value);

		$ViHttp = new CVoxImplantHttp();
		$result = $ViHttp->GetUsers($userId, !$getOneUser);
		if (!$result || $ViHttp->GetError()->error)
		{
			$this->error = new CVoxImplantError(__METHOD__, $ViHttp->GetError()->code, $ViHttp->GetError()->msg);
			return false;
		}

		if (!$skipUpdateAccount)
		{
			$ViAccount = new CVoxImplantAccount();
			$ViAccount->SetAccountName($result->account_name);
			$ViAccount->SetAccountBalance($result->account_balance);
			$ViAccount->SetAccountCurrency($result->account_currency);
		}

		return $result;
	}

	public function UpdateUserPassword($userId, $mode = self::MODE_USER, $password = false)
	{
		if ($password)
		{
			preg_match("/^[\\x20-\\x7e]{3,32}$/D", $password, $matches);
			if (empty($matches))
			{
				$this->error = new CVoxImplantError(__METHOD__, 'PASSWORD_INCORRECT', GetMessage('VI_USER_PASS_ERROR'));
				return false;
			}
		}

		$ViHttp = new CVoxImplantHttp();
		$result = $ViHttp->UpdateUserPassword($userId, $mode, $password);
		if (!$result || $ViHttp->GetError()->error)
		{
			if ($ViHttp->GetError()->code == 'USER_NOT_FOUND')
			{
				$this->ClearUserInfo($userId);
			}

			$this->error = new CVoxImplantError(__METHOD__, $ViHttp->GetError()->code, $ViHttp->GetError()->msg);
			return false;
		}

		global $USER_FIELD_MANAGER;
		if ($mode == self::MODE_USER)
		{
			$USER_FIELD_MANAGER->Update("USER", $userId, Array('UF_VI_PASSWORD' => $result->PASSWORD));
		}
		else if ($mode == self::MODE_PHONE)
		{
			$USER_FIELD_MANAGER->Update("USER", $userId, Array('UF_VI_PHONE_PASSWORD' => $result->PASSWORD));
		}

		return Array('PASSWORD' => $result->PASSWORD);
	}

	public static function GetCallByPhone($userId)
	{
		$userId = intval($userId);
		if(!$userId)
			return false;

		if (!self::GetPhoneActive($userId))
			return false;

		return CUserOptions::GetOption('voximplant', 'call_by_phone', true, $userId);
	}

	public function SetCallByPhone($userId, $active = true)
	{
		$userId = intval($userId);
		if(!$userId)
			return false;

		if ($active)
		{
			$arUserInfo = $this->GetUserInfo($userId);
			if (!$arUserInfo['phone_enable'])
			{
				$this->error = new CVoxImplantError(__METHOD__, 'PHONE_NOT_CONNECTED', 'Phone is not connected');
				return false;
			}
		}

		CUserOptions::SetOption('voximplant', 'call_by_phone', ($active? true: false), false, $userId);

		return true;
	}

	public static function GetPhoneActive($userId)
	{
		return CUserOptions::GetOption('voximplant', 'phone_device_active', false, $userId);
	}
	public function SetPhoneActive($userId, $active = false)
	{
		$userId = intval($userId);
		if(!$userId)
			return false;

		CUserOptions::SetOption('voximplant', 'phone_device_active', ($active? true: false), false, $userId);

		global $USER, $CACHE_MANAGER;
		$USER->Update($userId, Array('UF_VI_PHONE' => $active? 'Y': 'N'));

		if ($active)
		{
			$arUserInfo = $this->GetUserInfo($userId);
			if (!$arUserInfo['phone_enable'])
			{
				$USER->Update($userId, Array('UF_VI_PHONE' => 'N'));
				$CACHE_MANAGER->ClearByTag("USER_NAME_".$userId);
				CUserOptions::SetOption('voximplant', 'phone_device_active', false, false, $userId);
				return false;
			}
		}

		$CACHE_MANAGER->ClearByTag("USER_NAME_".$userId);

		if (CModule::IncludeModule('pull') && CPullOptions::GetQueueServerStatus())
		{
			CPullStack::AddByUser($userId,
				Array(
					'module_id' => 'voximplant',
					'command' => 'phoneDeviceActive',
					'params' => Array('active' => $active? 'Y': 'N')
				)
			);
		}

		return true;
	}

	public function GetOnlineUsers()
	{
		$ViHttp = new CVoxImplantHttp();
		$result = $ViHttp->GetOnlineUsers();
		if (!$result || $ViHttp->GetError()->error)
		{
			$this->error = new CVoxImplantError(__METHOD__, $ViHttp->GetError()->code, $ViHttp->GetError()->msg);
			return false;
		}

		return $result->result;
	}

	public function ClearUserInfo($userId)
	{
		$userId = intval($userId);
		if ($userId <= 0)
		{
			$this->error = new CVoxImplantError(__METHOD__, 'USER_ID_NULL', 'UserId is not correct');
			return false;
		}

		global $USER;
		$USER->Update($userId, Array('UF_VI_PASSWORD' => '', 'UF_VI_PHONE_PASSWORD' => ''));

		return true;
	}

	public function SetUserPhone($userId, $number)
	{
		$userId = intval($userId);
		if ($userId <= 0)
		{
			$this->error = new CVoxImplantError(__METHOD__, 'USER_ID_NULL', 'UserId is not correct');
			return false;
		}
		if ($number != CVoxImplantConfig::LINK_BASE_NUMBER)
		{
			$numbers = CVoxImplantConfig::GetPortalNumbers();
			if (!isset($numbers[$number]))
			{
				$number = '';
			}
		}
		global $USER_FIELD_MANAGER;
		$USER_FIELD_MANAGER->Update("USER", $userId, Array('UF_VI_BACKPHONE' => $number));

		return true;
	}

	public function GetUserInfo($userId, $getPhoneAccess = false)
	{
		$userId = intval($userId);
		if ($userId <= 0)
		{
			$this->error = new CVoxImplantError(__METHOD__, 'USER_ID_NULL', 'UserId is not correct');
			return false;
		}

		$userPassword = '';
		$userBackphone = '';
		$phoneEnable = false;
		$phonePassword = '';

		$arExtParams = Array('FIELDS' => Array("ID"), 'SELECT' => Array('UF_VI_PASSWORD', 'UF_VI_BACKPHONE', 'UF_VI_PHONE', 'UF_VI_PHONE_PASSWORD', 'UF_PHONE_INNER', 'UF_DEPARTMENT'));
		$dbUsers = CUser::GetList(($sort_by = ''), ($dummy=''), Array('ID' => $userId), $arExtParams);
		if ($arUser = $dbUsers->Fetch())
		{
			if (strlen($arUser['UF_VI_PASSWORD']) > 0)
			{
				$userPassword = $arUser['UF_VI_PASSWORD'];
			}
			if (strlen($arUser['UF_VI_PHONE_PASSWORD']) > 0)
			{
				$phonePassword = $arUser['UF_VI_PHONE_PASSWORD'];
			}
			$userInnerPhone = $arUser['UF_PHONE_INNER'];
			$userBackphone = $arUser['UF_VI_BACKPHONE'];
			if ($arUser['UF_VI_PHONE'] == 'Y')
			{
				$phoneEnable = true;
				$getPhoneAccess = true;
			}
			$arUser['IS_EXTRANET'] = self::IsExtranet($arUser);
			unset($arUser['UF_DEPARTMENT']);
		}

		if ($userPassword == '' || $getPhoneAccess && $phonePassword == '')
		{
			$result = $this->GetUser($userId, $getPhoneAccess, true);
			if (!$result || $this->GetError()->error)
			{
				$this->error = new CVoxImplantError(__METHOD__, $this->GetError()->code, $this->GetError()->msg);
				return false;
			}

			$userPassword = $result->result->user_password;
			$phonePassword = $result->result->phone_password;

			global $USER_FIELD_MANAGER;
			$USER_FIELD_MANAGER->Update("USER", $userId, Array('UF_VI_PASSWORD' => $userPassword, 'UF_VI_PHONE_PASSWORD' => $phonePassword));
		}

		if ($userBackphone)
		{
			$portalPhones = CVoxImplantConfig::GetPortalNumbers();
			if (!isset($portalPhones[$userBackphone]))
			{
				$userBackphone = '';
			}
		}
		if ($userBackphone == '')
		{
			$userBackphone = CVoxImplantConfig::GetPortalNumber();
			if ($userBackphone == CVoxImplantConfig::LINK_BASE_NUMBER)
			{
				$userBackphone = '';
			}
		}

		$viAccount = new CVoxImplantAccount();

		return Array(
			'call_server' => str_replace('voximplant.com', 'bitrixphone.com', $viAccount->GetCallServer()),
			'user_login' => 'user'.$userId,
			'user_password' => $userPassword,
			'user_backphone' => $userBackphone,
			'user_innerphone' => $userInnerPhone,
			'phone_enable' => $phoneEnable,
			'phone_login' => $phonePassword? 'phone'.$userId: "",
			'phone_password' => $phonePassword,
			'user_extranet' => $arUser['IS_EXTRANET'],
		);
	}

	public static function GetList($params)
	{
		$query = new \Bitrix\Main\Entity\Query(\Bitrix\Main\UserTable::getEntity());
		$query->registerRuntimeField('', new \Bitrix\Main\Entity\ExpressionField('IS_ONLINE_CUSTOM', 'CASE WHEN LAST_ACTIVITY_DATE > '.self::GetLastActivityDateAgo().' THEN \'Y\' ELSE \'N\' END'));

		if (isset($params['select']))
		{
			$query->setSelect($params['select']);
		}
		else
		{
			$query->addSelect('ID')->addSelect('IS_ONLINE_CUSTOM');
		}

		if (isset($params['filter']))
		{
			$query->setFilter($params['filter']);
		}

		if (isset($params['order']))
		{
			$query->setOrder($params['order']);
		}

		return $query->exec();
	}

	public static function QueuedGetList($params)
	{
		$query = new \Bitrix\Main\Entity\Query(Bitrix\Voximplant\QueueTable::getEntity());
		$query->registerRuntimeField('', new \Bitrix\Main\Entity\ExpressionField('IS_ONLINE_CUSTOM', 'CASE WHEN %s > '.CVoxImplantUser::GetLastActivityDateAgo().' THEN \'Y\' ELSE \'N\' END', Array('USER.LAST_ACTIVITY_DATE')));

		if (isset($params['select']))
		{
			$query->setSelect($params['select']);
		}
		else
		{
			$query->addSelect('ID')->addSelect('IS_ONLINE_CUSTOM');
		}

		if (isset($params['filter']))
		{
			$query->setFilter($params['filter']);
		}

		if (isset($params['order']))
		{
			$query->setOrder($params['order']);
		}

		return $query->exec();
	}

	public static function GetLastActivityDateAgo()
	{
		$lastActivityDate = 180;
		if (IsModuleInstalled('bitrix24'))
			$lastActivityDate = 1440;

		return Bitrix\Main\Application::getConnection()->getSqlHelper()->addSecondsToDateTime('(-'.$lastActivityDate.')');
	}

	public static function GetActiveStatusByTimeman($userId)
	{
		if ($userId <= 0)
			return false;

		if (CModule::IncludeModule('timeman'))
		{
			$tmUser = new CTimeManUser($userId);
			$tmSettings = $tmUser->GetSettings(Array('UF_TIMEMAN'));
			if (!$tmSettings['UF_TIMEMAN'])
			{
				$result = true;
			}
			else
			{
				$tmUser->GetCurrentInfo(true); // need for reload cache

				if ($tmUser->State() == 'OPENED')
				{
					$result = true;
				}
				else
				{
					$result = false;
				}
			}
		}
		else
		{
			$result = true;
		}

		return $result;
	}

	public static function IsExtranet($arUser)
	{
		$result = false;
		if (IsModuleInstalled('extranet'))
		{
			if (array_key_exists('UF_DEPARTMENT', $arUser))
			{
				if ($arUser['UF_DEPARTMENT'] == "")
				{
					$result = true;
				}
				else if (is_array($arUser['UF_DEPARTMENT']) && empty($arUser['UF_DEPARTMENT']))
				{
					$result = true;
				}
				else if (is_array($arUser['UF_DEPARTMENT']) && count($arUser['UF_DEPARTMENT']) == 1 && $arUser['UF_DEPARTMENT'][0] == 0)
				{
					$result = true;
				}
			}
		}

		return $result;
	}

	public function GetError()
	{
		return $this->error;
	}
}
?>
