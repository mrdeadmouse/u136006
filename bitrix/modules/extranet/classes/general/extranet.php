<?
class CExtranet
{
	function IsExtranetSite($site_id = SITE_ID)
	{
		if (!$site_id)
			$site_id = SITE_ID;

		if ($site_id == COption::GetOptionString("extranet", "extranet_site"))
			return true;

		return false;
	}


	function GetExtranetSiteID()
	{
		$extranet_site_id = COption::GetOptionString("extranet", "extranet_site");
		if (strlen($extranet_site_id) > 0)
		{
			if(CSite::GetArrayByID($extranet_site_id))
				return $extranet_site_id;
		}
		return false;
	}

	function GetExtranetUserGroupID()
	{
		$extranet_group_id = COption::GetOptionInt("extranet", "extranet_group");
		if (intval($extranet_group_id) > 0)
		{
			return intval($extranet_group_id);
		}
		return false;
	}

	function OnUserLogout($ID)
	{
		unset($_SESSION["aExtranetUser_".$ID]);
	}

	function IsIntranetUser($site = SITE_ID)
	{
		global $USER;

		if (is_object($USER) && $USER->GetID() > 0)
		{
			if (isset($_SESSION["aExtranetUser_".$USER->GetID()][$site]))
				return $_SESSION["aExtranetUser_".$USER->GetID()][$site];

			if (
				$USER->IsAdmin()
				|| (CModule::IncludeModule("socialnetwork") && CSocNetUser::IsCurrentUserModuleAdmin($site))
			)
			{
				$_SESSION["aExtranetUser_".$USER->GetID()][$site] = true;
				return true;
			}

			if ($USER->IsAuthorized())
			{
				$rsUser = CUser::GetList(
					$o = "ID",
					$b,
					array(
						"ID_EQUAL_EXACT" => $USER->GetID(),
					),
					array(
						"FIELDS" => array("ID"),
						"SELECT" => array("UF_DEPARTMENT"),
					)
				);
				if ($arUser = $rsUser->Fetch())
				{
					if (intval($arUser["UF_DEPARTMENT"][0]) > 0)
					{
						$_SESSION["aExtranetUser_".$USER->GetID()][$site] = true;
						return true;
					}
				}
			}
		}

		return false;
	}

	function IsExtranetUser() // deprecated
	{
		global $USER;

		if (is_object($USER) && $USER->IsAuthorized())
			if (in_array(CExtranet::GetExtranetUserGroupID(), $USER->GetUserGroupArray()))
					return true;

		return false;
	}

	function IsExtranetSocNetGroup($groupID)
	{
		if (!CModule::IncludeModule("socialnetwork"))
			return false;

		$extranet_site_id = CExtranet::GetExtranetSiteID();

		$rsGroupSite = CSocNetGroup::GetSite($groupID);
		while($arGroupSite = $rsGroupSite->Fetch())
			$arGroupSites[] = $arGroupSite["LID"];

		if (in_array($extranet_site_id, $arGroupSites))
			return true;
		else
			return false;
	}

	function IsExtranetAdmin()
	{
		global $USER;

		if (is_object($USER) && $USER->IsAdmin())
			return true;

		if (is_object($USER) && !$USER->IsAuthorized())
			return false;

		static $isExtAdmin = 'no';
		if($isExtAdmin === 'no')
		{
			$arGroups = $USER->GetUserGroupArray();
			$iExtGroups = CExtranet::GetExtranetUserGroupID();

			$arSubGroups = CGroup::GetSubordinateGroups($arGroups);
			if (in_array($iExtGroups, $arSubGroups))
			{
				$isExtAdmin = true;
				return true;
			}

			if (CModule::IncludeModule("socialnetwork") && CSocNetUser::IsCurrentUserModuleAdmin())
			{
				$isExtAdmin = true;
				return true;
			}

			$isExtAdmin = false;
			return false;
		}
		else
			return $isExtAdmin;

		return false;
	}

	function ExtranetRedirect()
	{
		global $USER, $APPLICATION;

		$curPage = $APPLICATION->GetCurPage();

		if(
			(!defined("ADMIN_SECTION") || ADMIN_SECTION !== true)
			&& (!defined("EXTRANET_NO_REDIRECT") || EXTRANET_NO_REDIRECT !== true)
			&& (strpos($curPage, "/bitrix/") !== 0)
			&& (strpos($curPage, "/upload/") !== 0)
			&& (strpos($curPage, "/oauth/") !== 0)
			&& (strpos($curPage, "/desktop_app/") !== 0)
			&& (strpos($curPage, "/docs/pub/") !== 0)
			&& (strpos($curPage, "/extranet/confirm/") !== 0)
			&& (strpos($curPage, "/mobile/ajax.php") !== 0)
			&& (!CExtranet::IsExtranetSite())
		):
			if (
				strlen(CExtranet::GetExtranetSiteID()) > 0
				&& $USER->IsAuthorized()
				&& !$USER->IsAdmin()
				&& !CExtranet::IsIntranetUser()
			):
				$rsSites = CSite::GetByID(CExtranet::GetExtranetSiteID());
				if (
					($arExtranetSite = $rsSites->Fetch())
					&& ($arExtranetSite["ACTIVE"] != "N")
				):
					$URLToRedirect = (strlen($arExtranetSite["SERVER_NAME"]) > 0 ? (CMain::IsHTTPS() ? "https" : "http")."://".$arExtranetSite["SERVER_NAME"] : "").$arExtranetSite["DIR"];
					LocalRedirect($URLToRedirect, true);
				endif;
			endif;
		endif;
	}

	function GetMyGroupsUsers($site, $bGadget = false)
	{
		global $USER, $obUsersCache;

		if (strlen($site) < 0)
		{
			return array();
		}

		$arUsersInMyGroups = $obUsersCache->Get($site, $bGadget);

		if (is_array($arUsersInMyGroups))
		{
			return $arUsersInMyGroups;
		}

		$arUsersInMyGroups = array();
		$arUserSocNetGroups = array();

		if (
			(
				!CExtranet::IsExtranetAdmin() 
				|| $bGadget
			) 
			&& CModule::IncludeModule("socialnetwork")
		)
		{
			$dbUsersInGroup = CSocNetUserToGroup::GetList(
				array(),
				array(
					"USER_ID" => $USER->GetID(),
					"<=ROLE" => SONET_ROLES_USER,
					"GROUP_SITE_ID" => $site,
					"GROUP_ACTIVE" => "Y"
				),
				false,
				false,
				array("ID", "GROUP_ID")
			);

			if ($dbUsersInGroup)
			{
				while ($arUserInGroup = $dbUsersInGroup->GetNext())
				{
					$arUserSocNetGroups[] = $arUserInGroup["GROUP_ID"];
				}
			}

			if (count($arUserSocNetGroups) > 0)
			{
				$dbUsersInGroup = CSocNetUserToGroup::GetList(
					array(),
					array(
						"GROUP_ID" => $arUserSocNetGroups,
						"<=ROLE" => SONET_ROLES_USER,
						"USER_ACTIVE" => "Y"
					),
					false,
					false,
					array("ID", "USER_ID")
				);

				if ($dbUsersInGroup)
				{
					while ($arUserInGroup = $dbUsersInGroup->GetNext())
					{
						$arUsersInMyGroups[] = $arUserInGroup["USER_ID"];
					}
				}
			}
		}
		else
		{
			$dbUsers = CUser::GetList(
				($by="id"),
				($order="asc"),
				array(
					"ACTIVE" => "Y",
					"GROUPS_ID" => array(CExtranet::GetExtranetUserGroupID())
				)
			);

			if ($dbUsers)
			{
				while ($arUser = $dbUsers->GetNext())
				{
					$arUsersInMyGroups[] = $arUser["ID"];
				}
			}
		}

		if (count($arUsersInMyGroups) > 0)
		{
			$arUsersInMyGroups = array_unique($arUsersInMyGroups);
		}

		$obUsersCache->Set($site, $bGadget, $arUsersInMyGroups);

		return $arUsersInMyGroups;
	}

	/**
	* Returns array of IDs of the users who belong to current user's socialnetwork groups
	* In comparison with CExtranet::GetMyGroupsUsers it doesn't check if the user is Extranet admin
	* and returns the same result for admin and user
	* This function was added because of the modified extranet users visibility logic
	* @param string $extranetSite - extranet SITE_ID (usually CExtranet::GetExtranetSiteID())
	* @return array IDs of the users in the groups
	*/
	function GetMyGroupsUsersSimple($extranetSite)
	{
		global $USER, $obUsersCache;

		if (strlen($extranetSite) < 0)
		{
			return array();
		}

		$arUsersInMyGroups = $obUsersCache->Get($extranetSite);

		if (is_array($arUsersInMyGroups))
		{
			return $arUsersInMyGroups;
		}

		$arUsersInMyGroups = array();
		$arUserSocNetGroups = array();

		if (CModule::IncludeModule("socialnetwork"))
		{
			$dbUsersInGroup = CSocNetUserToGroup::GetList(
				array(),
				array(
					"USER_ID" => $USER->GetID(),
					"<=ROLE" => SONET_ROLES_USER,
					"GROUP_SITE_ID" => $extranetSite,
					"GROUP_ACTIVE" => "Y"
				),
				false,
				false,
				array("ID", "GROUP_ID")
			);

			if ($dbUsersInGroup)
			{
				while ($arUserInGroup = $dbUsersInGroup->GetNext())
				{
					$arUserSocNetGroups[] = $arUserInGroup["GROUP_ID"];
				}
			}

			if (count($arUserSocNetGroups) > 0)
			{
				$dbUsersInGroup = CSocNetUserToGroup::GetList(
					array(),
					array(
						"GROUP_ID" => $arUserSocNetGroups,
						"<=ROLE" => SONET_ROLES_USER,
						"USER_ACTIVE" => "Y"
					),
					false,
					false,
					array("ID", "USER_ID")
				);

				if ($dbUsersInGroup)
				{
					while ($arUserInGroup = $dbUsersInGroup->GetNext())
					{
						$arUsersInMyGroups[] = $arUserInGroup["USER_ID"];
					}
				}
			}
		}

		if (count($arUsersInMyGroups) > 0)
		{
			$arUsersInMyGroups = array_unique($arUsersInMyGroups);
		}

		$obUsersCache->Set($extranetSite, false, $arUsersInMyGroups);

		return $arUsersInMyGroups;
	}

	function GetMyGroupsUsersFull($site, $bNotCurrent = false, $bGadget = false)
	{

		global $USER;

		$arUsersInMyGroups = array();

		$arUsersInMyGroupsID = CExtranet::GetMyGroupsUsers($site, $bGadget);

		if (count($arUsersInMyGroupsID) > 0)
		{
			$strUsersInMyGroupsID = "(".implode(" | ", $arUsersInMyGroupsID).")";
			if ($bNotCurrent)
				$strUsersInMyGroupsID .= " ~".$USER->GetID();

			$arFilter = Array("ID"=>$strUsersInMyGroupsID);

			$rsUsers = CUser::GetList(($by="ID"), ($order="asc"), $arFilter, array("SELECT"=>array("UF_*")));

			while($arUser = $rsUsers->GetNext())
				$arUsersInMyGroups[] = $arUser;

			return $arUsersInMyGroups;
		}
		else
			return array();

	}

	function GetExtranetGroupUsers($full = false)
	{
		$arExtranetGroupUsers = array();

		$arFilter = Array("GROUPS_ID"=>array(CExtranet::GetExtranetUserGroupID()));

		$rsUsers = CUser::GetList(($by="ID"), ($order="asc"), $arFilter);
		while($arUser = $rsUsers->GetNext())
		{
			if ($full)
				$arExtranetGroupUsers[] = $arUser;
			else
				$arExtranetGroupUsers[] = $arUser["ID"];
		}

		return $arExtranetGroupUsers;
	}

	function GetPublicUsers($full = false)
	{
		global $USER;

		$arPublicUsers = array();
		$arFilter = Array(
			COption::GetOptionString("extranet", "extranet_public_uf_code", "UF_PUBLIC") => "1", 
			"ID" => "~".$USER->GetID(), 
			"!UF_DEPARTMENT" => false, 
			"GROUPS_ID" => array(CExtranet::GetExtranetUserGroupID())
		);

		$rsUsers = CUser::GetList(($by="ID"), ($order="asc"), $arFilter);
		while($arUser = $rsUsers->GetNext())
		{
			if ($full)
				$arPublicUsers[] = $arUser;
			else
				$arPublicUsers[] = $arUser["ID"];
		}

		return $arPublicUsers;
	}

	function GetIntranetUsers()
	{
		static $CACHE = false;

		if (!$CACHE)
		{
			$arIntranetUsers = array();

			$ttl = (defined("BX_COMP_MANAGED_CACHE") ? 2592000 : 600);
			$cache_id = 'users';
			$obCache = new CPHPCache;
			$cache_dir = '/bitrix/extranet/';

			if($obCache->InitCache($ttl, $cache_id, $cache_dir))
			{
				$tmpVal = $obCache->GetVars();
				$arIntranetUsers = $tmpVal['USERS'];
				unset($tmpVal);
			}
			else
			{
				global $CACHE_MANAGER;

				if (defined("BX_COMP_MANAGED_CACHE"))
				{
					$CACHE_MANAGER->StartTagCache($cache_dir);
					$CACHE_MANAGER->RegisterTag('intranet_users');
				}

				$rsUsers = CUser::GetList(
					($by="ID"), 
					($order="asc"), 
					Array(
						"!UF_DEPARTMENT" => false
					),
					array(
						"FIELDS" => array("ID"),
						"SELECT" => array("UF_DEPARTMENT"),
					)
				);

				while($arUser = $rsUsers->Fetch())
				{
					$arIntranetUsers[] = $arUser["ID"];
				}
				
				if (defined("BX_COMP_MANAGED_CACHE"))
				{
					$CACHE_MANAGER->EndTagCache();
				}

				if($obCache->StartDataCache())
				{
					$obCache->EndDataCache(array(
						'USERS' => $arIntranetUsers,
					));
				}
			}

			$CACHE = $arIntranetUsers;
		}
		else
		{
			$arIntranetUsers = $CACHE;
		}

		return $arIntranetUsers;
	}

	function IsProfileViewable($arUser, $site_id = false)
	{
		global $USER;

		// if current user is admin
		if (CExtranet::IsExtranetAdmin())
			return true;

		// if extranet site is not set
		if (!CExtranet::GetExtranetSiteID())
			return true;

		// if current user is not authorized
		if (!$USER->IsAuthorized())
			return false;

		// if intranet and current user is not employee
		if (!CExtranet::IsExtranetSite($site_id) && !CExtranet::IsIntranetUser())
				return false;

		// if intranet and profile user is not employee
		if (!CExtranet::IsExtranetSite($site_id))
		{
			if (CExtranet::IsIntranetUser() && intval($arUser["UF_DEPARTMENT"]) > 0)
				return true;
			else
			{
				$arUsersInMyGroupsID = CExtranet::GetMyGroupsUsers(CExtranet::GetExtranetSiteID());
				if (!in_array($arUser["ID"], $arUsersInMyGroupsID) && ($arUser["ID"] != $USER->GetID()))
					return false;
			}
		}

		if (CExtranet::IsExtranetSite($site_id) && $arUser[COption::GetOptionString("extranet", "extranet_public_uf_code", "UF_PUBLIC")] != 1)
		{
			$arUsersInMyGroupsID = CExtranet::GetMyGroupsUsers(SITE_ID);
			if (!in_array($arUser["ID"], $arUsersInMyGroupsID) && ($arUser["ID"] != $USER->GetID()))
				return false;
		}

		return true;
	}

	function IsProfileViewableByID($user_id, $site_id = false)
	{
		global $USER;

		if (
			CExtranet::IsExtranetAdmin()
			||
			(
				IsModuleInstalled("bitrix24")
				&& CSocNetUser::IsCurrentUserModuleAdmin(SITE_ID, false)
			)
		)
			return true;

		if (IntVal($user_id) > 0 && strlen(CExtranet::GetExtranetSiteID()) > 0)
		{
			$dbUser = CUser::GetByID($user_id);
			$arUser = $dbUser->Fetch();

			if (!CExtranet::IsProfileViewable($arUser, $site_id))
				return false;
		}
		return true;
	}

	function ModifyGroupDefaultFeatures(&$arSocNetFeaturesSettings, $site_id = false)
	{
		if (CExtranet::IsExtranetSite($site_id))
		{
			$arSocNetFeaturesSettings["photo"]["operations"]["write"][SONET_ENTITY_GROUP] = SONET_ROLES_USER;
			$arSocNetFeaturesSettings["calendar"]["operations"]["write"][SONET_ENTITY_GROUP] = SONET_ROLES_USER;
			$arSocNetFeaturesSettings["files"]["operations"]["write_limited"][SONET_ENTITY_GROUP] = SONET_ROLES_USER;
			$arSocNetFeaturesSettings["blog"]["operations"]["write_post"][SONET_ENTITY_GROUP] = SONET_ROLES_USER;
		}
	}


	function OnBeforeSocNetGroupUpdateHandler($ID, $arFields)
	{
		global $bArchiveBeforeUpdate;

		if (!array_key_exists("CLOSED", $arFields))
			return true;

		if (!CModule::IncludeModule("socialnetwork"))
			return false;

		$arSocNetGroup = CSocNetGroup::GetByID($ID);
		if (!$arSocNetGroup)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_NO_GROUP"), "ERROR_NO_GROUP");
			return false;
		}
		else
		{
			if (CModule::IncludeModule('extranet'))
			{
				$ExtranetSiteID = CExtranet::GetExtranetSiteID();

				$rsGroupSite = CSocNetGroup::GetSite($ID);
				while($arGroupSite = $rsGroupSite->Fetch())
					$arGroupSites[] = $arGroupSite["LID"];

				if (!in_array($ExtranetSiteID, $arGroupSites))
					return true;
			}
			else
				return true;

			if ($arSocNetGroup["CLOSED"] == "Y")
				$bArchiveBeforeUpdate = true;
			else
				$bArchiveBeforeUpdate = false;

			return true;
		}
	}

	function OnSocNetGroupUpdateHandler($ID, $arFields)
	{
		global $bArchiveBeforeUpdate;

		if (!array_key_exists("CLOSED", $arFields))
			return true;

		if (intval($ID) <= 0)
			return false;

		if (!CModule::IncludeModule('socialnetwork'))
			return false;

		if (CModule::IncludeModule('extranet'))
		{
			$arSocNetGroup = CSocNetGroup::GetByID($ID);
			if (!$arSocNetGroup)
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_NO_GROUP"), "ERROR_NO_GROUP");
				return false;
			}
			else
			{
				$rsGroupSite = CSocNetGroup::GetSite($ID);
				while($arGroupSite = $rsGroupSite->Fetch())
					$arGroupSites[] = $arGroupSite["LID"];

				if (!in_array($ExtranetSiteID, $arGroupSites))
					return true;
			}
		}
		else
			return true;


		if ($arFields["CLOSED"] == "Y" && !$bArchiveBeforeUpdate)
			$bFromOpenToArchive = true;
		elseif ($arFields["CLOSED"] != "Y" && $bArchiveBeforeUpdate)
			$bFromArchiveToOpen = true;

		if ($bFromOpenToArchive || $bFromArchiveToOpen)
		{
			$arEmail = array();
			$dbRequests = CSocNetUserToGroup::GetList(
				array(),
				array(
					"GROUP_ID" => $ID,
					"<=ROLE" => SONET_ROLES_USER,
					"USER_ACTIVE" => "Y"
				),
				false,
				array(),
				array("ID", "USER_ID", "USER_NAME", "USER_LAST_NAME", "USER_EMAIL")
			);

			if ($dbRequests)
				while ($arRequests = $dbRequests->GetNext())
					$arEmail[] = array("NAME"=>$arRequests["USER_NAME"], "LAST_NAME"=>$arRequests["USER_LAST_NAME"], "EMAIL"=>$arRequests["USER_EMAIL"]);


		}

		if ($bFromOpenToArchive)
		{
			foreach($arEmail as $recipient)
			{
				$arEventFields = array(
					"WG_ID"			=> $ID,
					"WG_NAME"		=> $arFields["NAME"],
					"MEMBER_NAME"		=> $recipient["NAME"],
					"MEMBER_LAST_NAME"	=> $recipient["LAST_NAME"],
					"MEMBER_EMAIL"		=> $recipient["EMAIL"],
				);

				CEvent::Send("EXTRANET_WG_TO_ARCHIVE", SITE_ID, $arEventFields);
			}
		}

		if ($bFromArchiveToOpen)
		{
			foreach($arEmail as $recipient)
			{
				$arEventFields = array(
					"WG_ID"			=> $ID,
					"WG_NAME"		=> $arFields["NAME"],
					"MEMBER_NAME"		=> $recipient["NAME"],
					"MEMBER_LAST_NAME"	=> $recipient["LAST_NAME"],
					"MEMBER_EMAIL"		=> $recipient["EMAIL"],
				);

				CEvent::Send("EXTRANET_WG_FROM_ARCHIVE", SITE_ID, $arEventFields);
			}
		}

	}

	/*
	RegisterModuleDependences('socialnetwork', 'OnSocNetUserToGroupAdd', 'extranet', 'CExtranet', 'OnSocNetUserToGroupAdd');
	*/
	function OnSocNetUserToGroupAdd($ID, $arFields)
	{
		if(!defined("BX_COMP_MANAGED_CACHE"))
			return true;

		global $CACHE_MANAGER;

		if (
			array_key_exists("ROLE", $arFields)
			&& array_key_exists("GROUP_ID", $arFields)
			&& intval($arFields["GROUP_ID"]) > 0
			&& intval($arFields["USER_ID"]) > 0
		)
		{
			if (!CModule::IncludeModule('socialnetwork'))
				return false;

			$dbUsersInGroup = CSocNetUserToGroup::GetList(
				array(),
				array(
					"GROUP_ID" => $arFields["GROUP_ID"],
					"<=ROLE" => SONET_ROLES_USER,
				),
				false,
				false,
				array("ID", "USER_ID")
			);

			if ($dbUsersInGroup)
			{
				while ($arUserInGroup = $dbUsersInGroup->GetNext())
					$CACHE_MANAGER->ClearByTag("extranet_user_".$arUserInGroup["USER_ID"]);
			}

			$CACHE_MANAGER->ClearByTag("extranet_user_".$arFields["USER_ID"]);
		}

		return true;
	}

	/*
	RegisterModuleDependences('socialnetwork', 'OnSocNetUserToGroupUpdate', 'extranet', 'CExtranet', 'OnSocNetUserToGroupUpdate');
	*/
	function OnSocNetUserToGroupUpdate($ID, $arFields)
	{
		if(!defined("BX_COMP_MANAGED_CACHE"))
			return true;

		global $CACHE_MANAGER;

		if (
			array_key_exists("ROLE", $arFields)
			&& array_key_exists("GROUP_ID", $arFields)
			&& intval($arFields["GROUP_ID"]) > 0
			&& intval($arFields["USER_ID"]) > 0
		)
		{
			if (!CModule::IncludeModule('socialnetwork'))
				return false;

			$dbUsersInGroup = CSocNetUserToGroup::GetList(
				array(),
				array(
					"GROUP_ID" => $arFields["GROUP_ID"],
					"<=ROLE" => SONET_ROLES_USER,
				),
				false,
				false,
				array("ID", "USER_ID")
			);

			if ($dbUsersInGroup)
			{
				while ($arUserInGroup = $dbUsersInGroup->GetNext())
					$CACHE_MANAGER->ClearByTag("extranet_user_".$arUserInGroup["USER_ID"]);
			}

			$CACHE_MANAGER->ClearByTag("extranet_user_".$arFields["USER_ID"]);
		}

		return true;
	}

	/*
	RegisterModuleDependences('socialnetwork', 'OnSocNetUserToGroupDelete', 'extranet', 'CExtranet', 'OnSocNetUserToGroupDelete');
	*/
	function OnSocNetUserToGroupDelete($ID)
	{
		if(!defined("BX_COMP_MANAGED_CACHE"))
			return true;

		if (!CModule::IncludeModule('socialnetwork'))
			return false;

		global $CACHE_MANAGER;

		$arUser2Group = CSocNetUserToGroup::GetByID($ID);
		if (!$arUser2Group)
			return true;

		if (
			array_key_exists("GROUP_ID", $arUser2Group)
			&& array_key_exists("USER_ID", $arUser2Group)
			&& intval($arUser2Group["GROUP_ID"]) > 0
			&& intval($arUser2Group["USER_ID"]) > 0
		)
		{
			$dbUsersInGroup = CSocNetUserToGroup::GetList(
				array(),
				array(
					"GROUP_ID" => $arUser2Group["GROUP_ID"],
					"<=ROLE" => SONET_ROLES_USER,
				),
				false,
				false,
				array("ID", "USER_ID")
			);

			if ($dbUsersInGroup)
			{
				while ($arUserInGroup = $dbUsersInGroup->GetNext())
					$CACHE_MANAGER->ClearByTag("extranet_user_".$arUserInGroup["USER_ID"]);
			}

			$CACHE_MANAGER->ClearByTag("extranet_user_".$arUser2Group["USER_ID"]);
		}

		return true;
	}

	/*
	RegisterModuleDependences('main', 'OnUserDelete', 'extranet', 'CExtranet', 'OnUserDelete', 10);
	*/
	function OnUserDelete($ID)
	{
		if(!defined("BX_COMP_MANAGED_CACHE"))
			return true;

		global $CACHE_MANAGER;

		if (intval($ID) > 0)
		{
			if (!CModule::IncludeModule('socialnetwork'))
				return false;

			$dbUsersInGroup = CSocNetUserToGroup::GetList(
				array(),
				array(
					"USER_ID" => $ID,
					"<=ROLE" => SONET_ROLES_USER,
				),
				false,
				false,
				array("ID", "GROUP_ID")
			);

			if ($dbUsersInGroup)
			{
				while ($arUserInGroup = $dbUsersInGroup->GetNext())
					$arUserSocNetGroups[] = $arUserInGroup["GROUP_ID"];
			}

			if (count($arUserSocNetGroups) > 0)
			{
				$dbUsersInGroup = CSocNetUserToGroup::GetList(
					array(),
					array(
						"GROUP_ID" => $arUserSocNetGroups,
						"<=ROLE" => SONET_ROLES_USER,
					),
					false,
					false,
					array("ID", "USER_ID")
				);

				if ($dbUsersInGroup)
					while ($arUserInGroup = $dbUsersInGroup->GetNext())
						$CACHE_MANAGER->ClearByTag("extranet_user_".$arUserInGroup["USER_ID"]);
			}

			$CACHE_MANAGER->ClearByTag("extranet_user_".$ID);
		}

		return true;
	}

	/*
	RegisterModuleDependences('socialnetwork', 'OnSocNetGroupDelete', 'extranet', 'CExtranet', 'OnSocNetGroupDelete');
	*/
	function OnSocNetGroupDelete($ID)
	{
		if(!defined("BX_COMP_MANAGED_CACHE"))
			return true;

		global $CACHE_MANAGER;

		if (intval($ID) > 0)
		{
			if (!CModule::IncludeModule('socialnetwork'))
				return false;

			$dbUsersInGroup = CSocNetUserToGroup::GetList(
				array(),
				array(
					"GROUP_ID" => $ID,
					"<=ROLE" => SONET_ROLES_USER,
					),
					false,
					false,
					array("ID", "USER_ID")
			);

			if ($dbUsersInGroup)
				while ($arUserInGroup = $dbUsersInGroup->GetNext())
					$CACHE_MANAGER->ClearByTag("extranet_user_".$arUserInGroup["USER_ID"]);
		}

		return true;
	}

	/*
	RegisterModuleDependences('main', 'onBeforeUserAdd', 'extranet', 'CExtranet', 'ClearPublicUserCacheOnAddUpdate');
	RegisterModuleDependences('main', 'onBeforeUserUpdate', 'extranet', 'CExtranet', 'ClearPublicUserCacheOnAddUpdate');
	*/
	function ClearPublicUserCacheOnAddUpdate($arFields)
	{
		global $CACHE_MANAGER;

		if (intval($arFields["ID"]) > 0) // update
		{
			$dbRes = CUser::GetList(
				$by="id", $order="asc",
				array("ID_EQUAL_EXACT" => intval($arFields['ID'])),
				array('SELECT' => array('UF_PUBLIC'))
			);

			if ($arOldFields = $dbRes->Fetch())
			{
				if (
					isset($arFields['UF_PUBLIC'])
					&& $arOldFields['UF_PUBLIC'] != $arFields['UF_PUBLIC']
				)
					$CACHE_MANAGER->ClearByTag("extranet_public");
			}
		}
		else // add
		{
			if (isset($arFields['UF_PUBLIC']))
				$CACHE_MANAGER->ClearByTag("extranet_public");
		}

		return true;
	}


	/*
	RegisterModuleDependences('main', 'OnUserDelete', 'extranet', 'CExtranet', 'ClearPublicUserCacheOnDelete');
	*/
	function ClearPublicUserCacheOnDelete($ID)
	{
		global $CACHE_MANAGER;

		if (intval($ID) > 0)
		{
			$dbRes = CUser::GetList(
				$by="id", $order="asc",
				array("ID_EQUAL_EXACT" => intval($ID)),
				array('SELECT' => array('UF_PUBLIC'))
			);

			if ($arFields = $dbRes->Fetch())
			{
				if (
					array_key_exists("UF_PUBLIC", $arFields)
					&& $arFields["UF_PUBLIC"]
				)
					$CACHE_MANAGER->ClearByTag("extranet_public");
			}
		}

		return true;
	}

	function GetSitesByLogDestinations($arRights, $authorId = false)
	{
		static $extranet_site_id = null;
		static $default_site_id = null;

		static $arIntranetSiteID = null;
		static $arIntranetUserID = null;

		$arSiteID = array();
		if (!is_array($arRights))
		{
			return $arSiteID;
		}

		if (!$authorId)
		{
			$authorId = $GLOBALS["USER"]->GetID();
		}

		if ($extranet_site_id === null)
		{
			$extranet_site_id = CExtranet::GetExtranetSiteID();

			$arIntranetSiteID = array();
			$rsSite = CSite::GetList(
				$by="sort", 
				$order="desc", 
				array("ACTIVE" => "Y")
			);
			while ($arSite = $rsSite->Fetch())
			{
				if ($arSite["LID"] == $extranet_site_id)
				{
					continue;
				}
				$arIntranetSiteID[] = $arSite["LID"];
			}

			$default_site_id = CSite::GetDefSite();

			$arIntranetUserID = CExtranet::GetIntranetUsers();
		}

		$bIblockIncluded = CModule::IncludeModule("iblock");
		foreach($arRights as $right_tmp)
		{
			$ar = array_diff($arIntranetSiteID, $arSiteID);
			if (
				empty($ar)
				&& in_array($extranet_site_id, $arSiteID)
			)
			{
				break;
			}

			if (preg_match('/^U(\d+)$/', $right_tmp, $matches))
			{
				$arSiteID[] = (in_array($matches[1], $arIntranetUserID) ? $default_site_id : $extranet_site_id);
			}
			elseif (preg_match('/^SG(\d+)$/', $right_tmp, $matches))
			{
				$rsGroupSite = CSocNetGroup::GetSite($matches[1]);
				while($arGroupSite = $rsGroupSite->Fetch())
				{
					$arSiteID[] = $arGroupSite["LID"];
				}
			}
			elseif (preg_match('/^G2$/', $right_tmp, $matches))
			{
				$arSiteID = array_merge($arSiteID, $arIntranetSiteID);
			}
		}

		if (
			in_array(SITE_ID, $arIntranetSiteID)
			&& !in_array(SITE_ID, $arSiteID)
		)
		{
			$arSiteID[] = SITE_ID;
		}

		return array_unique($arSiteID);
	}
}

class CUsersInMyGroupsCache
{
	private $CACHE = array();

	function Get($site, $bGadget = false)
	{
		if (strlen($site) < 0)
		{
			return false;
		}

		if (
			array_key_exists($site."_".($bGadget ? "Y" : "N"), $this->CACHE) 
			&& is_array($this->CACHE[$site."_".($bGadget ? "Y" : "N")])
		)
		{
			return $this->CACHE[$site."_".($bGadget ? "Y" : "N")];
		}

		return false;
	}

	function Set($site, $bGadget = false, $arValue = array())
	{
		if (strlen($site) < 0)
		{
			return false;
		}

		if (!is_array($arValue))
		{
			return false;
		}

		$this->CACHE[$site."_".($bGadget ? "Y" : "N")] = $arValue;
	}
}
?>
