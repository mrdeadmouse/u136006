<?
class CDavAccount
{
	private static $accountsCache = array("groups" => array(), "users" => array());
	private static $accountsCacheMap = array();

	public static function GetAccountByName($name)
	{
		if (strlen($name) <= 0)
			throw new Exception("name");

		$arResult = null;

		if (!strncasecmp("group-", $name, 6) && CModule::IncludeModule("socialnetwork"))
		{
			$groupId = intval(substr($name, 6));

			if (array_key_exists($groupId, self::$accountsCache["groups"]))
				return self::$accountsCache["groups"][$groupId];

			$dbGroup = CSocNetGroup::GetList(array(), array("ID" => $groupId, "ACTIVE" => "Y"), false, false, array("ID", "SITE_ID", "NAME", "OWNER_ID", "OWNER_EMAIL"));
			if ($arGroup = $dbGroup->Fetch())
			{
				$arResult = self::ExtractAccountFromGroup($arGroup);
				self::$accountsCache["groups"][$arGroup["ID"]] = $arResult;
			}

			return $arResult;
		}

		if (array_key_exists($name, self::$accountsCacheMap))
			return self::$accountsCache["users"][self::$accountsCacheMap[$name]];

		$dbUsers = CUser::GetList(($by = "ID"), ($order = "desc"), array("LOGIN_EQUAL_EXACT" => $name, "ACTIVE" => "Y"));
		if ($arUser = $dbUsers->Fetch())
		{
			$arResult = self::ExtractAccountFromUser($arUser);
			self::$accountsCache["users"][$arUser["ID"]] = $arResult;
			self::$accountsCacheMap[$name] = $arUser["ID"];
		}

		return $arResult;
	}

	private static function ExtractAccountFromGroup($arGroup)
	{
		return array(
			"ID" => $arGroup["ID"],
			"TYPE" => "group",
			"CODE" => "group-".$arGroup["ID"],
			"SITE_ID" => $arGroup["SITE_ID"],
			"NAME" => $arGroup["NAME"],
			"EMAIL" => $arUser["OWNER_EMAIL"],
		);
	}

	private static function ExtractAccountFromUser($arUser)
	{
		return array(
			"ID" => $arUser["ID"],
			"TYPE" => "user",
			"CODE" => $arUser["LOGIN"],
			"NAME" => self::FormatUserName($arUser),
			"EMAIL" => $arUser["EMAIL"],
			"FIRST_NAME" => $arUser["NAME"],
			"LAST_NAME" => $arUser["LAST_NAME"],
		);
	}

	private static function FormatUserName($arUser)
	{
		$r = $arUser["NAME"];
		if (strlen($r) > 0 && strlen($arUser["LAST_NAME"]) > 0)
			$r .= " ";
		$r .= $arUser["LAST_NAME"];

		if (strlen($r) <= 0)
			$r = $arUser["LOGIN"];

		return $r;
	}

	public static function GetAccountById($account)
	{
		if (!is_array($account) || count($account) != 2)
			throw new Exception("account");

		$arResult = null;

		if ($account[0] == "group")
		{
			if (CModule::IncludeModule("socialnetwork"))
			{
				if (array_key_exists($account[1], self::$accountsCache["groups"]))
					return self::$accountsCache["groups"][$account[1]];

				$dbGroup = CSocNetGroup::GetList(array(), array("ID" => $account[1], "ACTIVE" => "Y"));
				if ($arGroup = $dbGroup->Fetch())
				{
					$arResult = self::ExtractAccountFromGroup($arGroup);
					self::$accountsCache["groups"][$arGroup["ID"]] = $arResult;
				}

				return $arResult;
			}
		}

		if (array_key_exists($account[1], self::$accountsCache["users"]))
			return self::$accountsCache["users"][$account[1]];

		//$dbUsers = CUser::GetList(($by = "ID"), ($order = "desc"), array("ID_EQUAL_EXACT" => $account[1], "ACTIVE" => "Y"));
		$dbUsers = CUser::GetById($account[1]);
		if ($arUser = $dbUsers->Fetch())
		{
			$arResult = self::ExtractAccountFromUser($arUser);
			self::$accountsCache["users"][$arUser["ID"]] = $arResult;
			self::$accountsCacheMap[$arUser["LOGIN"]] = $arUser["ID"];
		}

		return $arResult;
	}

	public static function GetAccountsList($type, $arOrder = array(), $arFilter = array())
	{
		$arResult = array();

		if ($type == "group")
		{
			if (CModule::IncludeModule("socialnetwork"))
			{
				$arFilter = array_merge($arFilter, array("ACTIVE" => "Y", "VISIBLE" => "Y"));

				$dbGroup = CSocNetGroup::GetList($arOrder, $arFilter);
				if ($arGroup = $dbGroup->Fetch())
					$arResult[] = self::ExtractAccountFromGroup($arGroup);

				return $arResult;
			}
		}

		$arFilter = array_merge($arFilter, array("ACTIVE" => "Y"));

		$dbUsers = CUser::GetList($by, $order, $arFilter);
		if ($arUser = $dbUsers->Fetch())
			$arResult[] = self::ExtractAccountFromUser($arUser);

		return $arResult;
	}



	private static function GetAddressbookExtranetUserFilter($siteId, $arFilter = array())
	{
		if (CModule::IncludeModule('extranet') && (CExtranet::IsExtranetSite($siteId) || !CExtranet::IsIntranetUser($siteId)))
		{
			if (!CExtranet::IsExtranetAdmin())
			{
				$arIDs = array_merge(CExtranet::GetMyGroupsUsers($siteId), CExtranet::GetPublicUsers());

				if (array_key_exists("ID", $arFilter))
				{
					$arIDs1 = $arFilter["ID"];
					if (!is_array($arIDs1))
						$arIDs1 = explode("|", $arIDs1);

					$arIDs = array_intersect($arIDs1, $arIDs);
				}

				if (count($arIDs) > 0)
					$arFilter['ID'] = implode('|', array_unique($arIDs));
				else
					$arFilter['ID'] = 0;
			}
		}
		else
		{
			$arFilter['!UF_DEPARTMENT'] = false;
		}

		return $arFilter;
	}

	public static function GetAddressbookModificationLabel($collectionId)
	{
		list($siteId) = $collectionId;

		$arFilter = self::GetAddressbookExtranetUserFilter($siteId);
		$arParams = array(
				'FIELDS' => array('TIMESTAMP_X'),
				'NAV_PARAMS' => array('nTopCount' => 1)
			);
		$dbUsers = CUser::GetList(($by = "TIMESTAMP_X"), ($order = "DESC"), $arFilter, $arParams);
		if ($arUser = $dbUsers->Fetch())
			return $arUser["TIMESTAMP_X"];
		return "";
	}

	public static function GetAddressbookContactsList($collectionId, $arFilter = array())
	{
		list($siteId) = $collectionId;

		$arFilter = self::GetAddressbookExtranetUserFilter($siteId, $arFilter);
		$arFilter["ACTIVE"] = "Y";
		$arResult = array();

		$canCache = false;
		if(count($arFilter) == 2 && $arFilter['!UF_DEPARTMENT'] === false)
		{
			$canCache = true;

			$obDavCache = new CPHPCache;
			$cache_id = 'kp_dav_address_book';
			$cache_dir = '/dav/address_book';

			if(defined("BX_COMP_MANAGED_CACHE"))
				$cache_ttl = 2592000;
			else
				$cache_ttl = 1200;

			if($obDavCache->InitCache($cache_ttl, $cache_id, $cache_dir))
				return $obDavCache->GetVars();
		}

		$arSelect = array(
				'FIELDS' => array(
					'ID', 'LOGIN', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'EMAIL', 'PERSONAL_BIRTHDAY', 'PERSONAL_PHOTO',
					'WORK_PHONE', 'PERSONAL_MOBILE', 'PERSONAL_PHONE', 'WORK_COMPANY', 'WORK_POSITION',
					'WORK_WWW', 'PERSONAL_WWW', 'PERSONAL_STREET', 'PERSONAL_CITY', 'PERSONAL_STATE',
					'PERSONAL_ZIP', 'PERSONAL_COUNTRY', 'WORK_STREET', 'WORK_CITY', 'WORK_STATE',
					'WORK_ZIP', 'WORK_COUNTRY', 'TIMESTAMP_X')
			);

		$dbUsers = CUser::GetList(($by = "ID"), ($order = "asc"), $arFilter, $arSelect);
		while ($arUser = $dbUsers->GetNext(true, false))
			$arResult[] = $arUser;

		if($canCache)
		{
			if(defined("BX_COMP_MANAGED_CACHE"))
			{
				global $CACHE_MANAGER;
				$CACHE_MANAGER->StartTagCache($cache_dir);
				$CACHE_MANAGER->RegisterTag("USER_CARD");
				$CACHE_MANAGER->EndTagCache();
			}

			if($obDavCache->StartDataCache())
				$obDavCache->EndDataCache($arResult);
		}

		return $arResult;
	}
}
?>