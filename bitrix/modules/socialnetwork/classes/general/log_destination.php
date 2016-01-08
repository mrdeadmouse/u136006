<?
$GLOBALS["SOCNET_LOG_DESTINATION"] = Array();
class CSocNetLogDestination
{
	public static function GetLastUser()
	{
		global $USER;

		$userId = intval($USER->GetID());

		if(!isset($GLOBALS["SOCNET_LOG_DESTINATION"]["GetLastUser"][$userId]))
		{
			$arLastSelected = CUserOptions::GetOption("socialnetwork", "log_destination", array());
			if (
				is_array($arLastSelected)
				&& strlen($arLastSelected['users']) > 0
				&& $arLastSelected['users'] != '"{}"'
			)
			{
				$arLastSelected = array_reverse(CUtil::JsObjectToPhp($arLastSelected['users']));
			}
			else
				$arLastSelected = array();

			if (is_array($arLastSelected))
			{
				if (!isset($arLastSelected[$userId]))
					$arLastSelected['U'.$userId] = 'U'.$userId;
			}
			else
			{
				$arLastSelected['U'.$userId] = 'U'.$userId;
			}

			$count = 0;
			$arUsers = Array();
			foreach ($arLastSelected as $userId)
			{
				if ($count < 5)
					$count++;
				else
					break;

				$arUsers[$userId] = $userId;
			}
			$GLOBALS["SOCNET_LOG_DESTINATION"]["GetLastUser"][$userId] = array_reverse($arUsers);
		}

		return $GLOBALS["SOCNET_LOG_DESTINATION"]["GetLastUser"][$userId];
	}

	public static function GetLastSocnetGroup()
	{
		$arLastSelected = CUserOptions::GetOption("socialnetwork", "log_destination", array());
		if (
			is_array($arLastSelected)
			&& strlen($arLastSelected['sonetgroups']) > 0
			&& $arLastSelected['sonetgroups'] != '"{}"'
		)
		{
			$arLastSelected = array_reverse(CUtil::JsObjectToPhp($arLastSelected['sonetgroups']));
		}
		else
		{
			$arLastSelected = array();
		}

		$count = 0;
		$arSocnetGroups = Array();
		foreach ($arLastSelected as $sgId)
		{
			if ($count <= 4)
				$count++;
			else
				break;

			$arSocnetGroups[$sgId] = $sgId;
		}
		return array_reverse($arSocnetGroups);
	}

	public static function GetLastDepartment()
	{
		$arLastSelected = CUserOptions::GetOption("socialnetwork", "log_destination", array());
		if (
			is_array($arLastSelected)
			&& strlen($arLastSelected['department']) > 0
			&& $arLastSelected['department'] != '"{}"'
		)
		{
			$arLastSelected = array_reverse(CUtil::JsObjectToPhp($arLastSelected['department']));
		}
		else
			$arLastSelected = array();

		$count = 0;
		$arDepartment = Array();
		foreach ($arLastSelected as $depId)
		{
			if ($count < 4)
				$count++;
			else
				break;

			$arDepartment[$depId] = $depId;
		}

		return array_reverse($arDepartment);
	}

	public static function GetStucture($arParams = Array())
	{
		$bIntranetEnable = false;
		if(IsModuleInstalled('intranet') && IsModuleInstalled('iblock'))
			$bIntranetEnable = true;

		$result = array(
			"department" => array(),
			"department_relation" => array(),
			"department_relation_head" => array(),
		);
		
		if (
			isset($arParams["DEPARTMENT_ID"])
			&& intval($arParams["DEPARTMENT_ID"]) > 0
		)
		{
			$department_id = intval($arParams["DEPARTMENT_ID"]);
		}

		if($bIntranetEnable)
		{
			if (!(CModule::IncludeModule('extranet') && !CExtranet::IsIntranetUser()))
			{
				if(($iblock_id = COption::GetOptionInt('intranet', 'iblock_structure', 0)) > 0)
				{
					global $CACHE_MANAGER;

					if(defined("BX_COMP_MANAGED_CACHE"))
						$ttl = 2592000;
					else
						$ttl = 600;

					$cache_id = 'sonet_structure_new4_'.$iblock_id.(intval($department_id) > 0 ? "_".$department_id : "");
					$obCache = new CPHPCache;
					$cache_dir = '/sonet/structure';

					if($obCache->InitCache($ttl, $cache_id, $cache_dir))
					{
						$result = $obCache->GetVars();
					}
					else
					{
						CModule::IncludeModule('iblock');

						if(defined("BX_COMP_MANAGED_CACHE"))
						{
							$CACHE_MANAGER->StartTagCache($cache_dir);
						}

						$arFilter = array(
							"IBLOCK_ID" => $iblock_id,
							"ACTIVE" => "Y"
						);

						if (intval($department_id) > 0)
						{
							$rsSectionDepartment = CIBlockSection::GetList(
								array(), 
								array(
									"ID" => intval($department_id)
								),
								false, 
								array("ID", "LEFT_MARGIN", "RIGHT_MARGIN")
							);

							if ($arSectionDepartment = $rsSectionDepartment->Fetch())
							{
								$arFilter[">=LEFT_MARGIN"] = $arSectionDepartment["LEFT_MARGIN"];
								$arFilter["<=RIGHT_MARGIN"] = $arSectionDepartment["RIGHT_MARGIN"];
							}
						}

						$dbRes = CIBlockSection::GetList(
							array("left_margin"=>"asc"),
							$arFilter,
							false,
							array("ID", "IBLOCK_SECTION_ID", "NAME")
						);
						while ($ar = $dbRes->Fetch())
						{
							$result["department"]['DR'.$ar['ID']] = array(
								'id' => 'DR'.$ar['ID'],
								'entityId' => $ar["ID"],
								'name' => htmlspecialcharsbx($ar['NAME']),
								'parent' => 'DR'.intval($ar['IBLOCK_SECTION_ID']),
							);
						}
						if(defined("BX_COMP_MANAGED_CACHE"))
						{
							$CACHE_MANAGER->RegisterTag('iblock_id_'.$iblock_id);
							$CACHE_MANAGER->EndTagCache();
						}

						if($obCache->StartDataCache())
						{
							$obCache->EndDataCache($result);
						}
					}
				}
			}
		}

		if (
			!empty( $result["department"]) 
			&& !isset($arParams["LAZY_LOAD"])
		)
		{
			$result["department_relation"] = self::GetTreeList('DR'.(intval($department_id) > 0 ? $department_id : 0), $result["department"], true);
			if (intval($arParams["HEAD_DEPT"]) > 0)
			{
				$result["department_relation_head"] = self::GetTreeList('DR'.intval($arParams["HEAD_DEPT"]), $result["department"], true);
			}
		}

		return $result;
	}

	public static function GetExtranetUser()
	{
		global $USER;

		$userId = intval($USER->GetID());

		if(!isset($GLOBALS["SOCNET_LOG_DESTINATION"]["GetExtranetUser"][$userId]))
		{
			$arExtParams = Array("FIELDS" => Array("ID", "LAST_NAME", "NAME", "SECOND_NAME", "LOGIN", "PERSONAL_PHOTO", "WORK_POSITION", "PERSONAL_PROFESSION", "IS_ONLINE"));
			$arFilter = Array();
			if (CModule::IncludeModule('extranet') && !CExtranet::IsIntranetUser())
			{
				$arSelect = Array($userId);
				$rsGroups = CSocNetUserToGroup::GetList(
					array("GROUP_NAME" => "ASC"),
					array(
						"USER_ID" => $userId,
						"<=ROLE" => SONET_ROLES_USER,
						"GROUP_SITE_ID" => SITE_ID,
						"GROUP_ACTIVE" => "Y",
						"!GROUP_CLOSED" => "Y"
					),
					false,
					array("nTopCount" => 500),
					array("ID", "GROUP_ID")
				);
				while($arGroup = $rsGroups->Fetch())
				{
					$arGroupTmp = array(
						"id" => $arGroup["GROUP_ID"],
						"entityId" => $arGroup["GROUP_ID"]
					);
					$arSocnetGroups[$arGroup["GROUP_ID"]] = $arGroupTmp;
				}

				if (count($arSocnetGroups) > 0)
				{
					$arUserSocNetGroups = Array();
					foreach ($arSocnetGroups as $groupId => $ar)
						$arUserSocNetGroups[] = $groupId;

					$dbUsersInGroup = CSocNetUserToGroup::GetList(
						array(),
						array(
							"GROUP_ID" => $arUserSocNetGroups,
							"<=ROLE" => SONET_ROLES_USER,
							"USER_ACTIVE" => "Y"
						),
						false,
						false,
						array("ID", "USER_ID", "GROUP_ID")
					);
					while ($ar = $dbUsersInGroup->GetNext(true, false))
						$arSelect[] = intval($ar["USER_ID"]);
				}
				$arFilter['ID'] = implode('|', $arSelect);
			}

			$arUsers = Array();
			$dbUsers = CUser::GetList(($sort_by = Array('last_name'=>'asc', 'IS_ONLINE'=>'desc')), ($dummy=''), $arFilter, $arExtParams);
			while ($arUser = $dbUsers->GetNext())
			{
				$sName = trim(CUser::FormatName(CSite::GetNameFormat(), $arUser, true, false));

				if (empty($sName))
					$sName = $arUser["~LOGIN"];

				$arFileTmp = CFile::ResizeImageGet(
					$arUser["PERSONAL_PHOTO"],
					array('width' => 32, 'height' => 32),
					BX_RESIZE_IMAGE_EXACT,
					false
				);

				$arUsers['U'.$arUser["ID"]] = Array(
					'id' => 'U'.$arUser["ID"],
					'entityId' => $arUser["ID"],
					'name' => $sName,
					'avatar' => empty($arFileTmp['src'])? '': $arFileTmp['src'],
					'desc' => $arUser['WORK_POSITION'] ? $arUser['WORK_POSITION'] : ($arUser['PERSONAL_PROFESSION']?$arUser['PERSONAL_PROFESSION']:'&nbsp;'),
				);
			}
			$GLOBALS["SOCNET_LOG_DESTINATION"]["GetExtranetUser"][$userId] = $arUsers;
		}
		return $GLOBALS["SOCNET_LOG_DESTINATION"]["GetExtranetUser"][$userId];
	}

	public static function GetUsers($arParams = Array(), $bSelf = true)
	{
		global $USER;

		$userId = intval($USER->GetID());
		$bExtranet = false;

		$arFilter = Array('ACTIVE' => 'Y');

		if (
			IsModuleInstalled("intranet")
			|| COption::GetOptionString("main", "new_user_registration_email_confirmation", "N") == "Y"
		)
		{
			$arFilter["CONFIRM_CODE"] = false;
		}

		$arExtParams = Array("FIELDS" => Array("ID", "LAST_NAME", "NAME", "SECOND_NAME", "LOGIN", "PERSONAL_PHOTO", "WORK_POSITION", "PERSONAL_PROFESSION", "IS_ONLINE"));

		if (isset($arParams['id']))
		{
			if (empty($arParams['id']))
				$arFilter['ID'] = $userId;
			else
			{
				$arSelect = array($userId);
				foreach ($arParams['id'] as $value)
				{
					if(intval($value) > 0 && !in_array($value, $arSelect))
						$arSelect[] = intval($value);
				}
				sort($arSelect);
				$arFilter['ID'] = implode('|', $arSelect);
			}
		}
		elseif (isset($arParams['deportament_id']))
		{
			if (is_array($arParams['deportament_id']))
				$arFilter['UF_DEPARTMENT'] = $arParams['deportament_id'];
			else
			{
				if ($arParams['deportament_id'] == 'EX')
				{
					$bExtranet = true;
				}
				else
				{
					$arFilter['UF_DEPARTMENT'] = intval($arParams['deportament_id']);
				}
				
			}

			$arExtParams['SELECT'] = array('UF_DEPARTMENT');
		}

		$cacheTtl = 3153600;
		$cacheId = 'socnet_destination_getusers_'.md5(serialize($arFilter)).$bSelf.($bExtranet ? '_ex' : '');
		$cacheDir = '/socnet/dest/'.(
			isset($arParams['id']) 
			? 'user' 
			: (
				isset($arParams['deportament_id']) 
				? 'dept' 
				: 'all'
			)
		).'/';

		$obCache = new CPHPCache;
		if($obCache->InitCache($cacheTtl, $cacheId, $cacheDir))
		{
			$arUsers = $obCache->GetVars();
		}
		else
		{
			$obCache->StartDataCache();
			if(defined("BX_COMP_MANAGED_CACHE"))
				$GLOBALS["CACHE_MANAGER"]->StartTagCache($cacheDir);

			if (
				$bExtranet
				&& CModule::IncludeModule("extranet")
			)
			{
				$arUsers = Array();
				$arExtranetUsers = CExtranet::GetMyGroupsUsersFull(CExtranet::GetExtranetSiteID(), $bSelf);
				foreach($arExtranetUsers as $arUserTmp)
				{
					$sName = trim(CUser::FormatName(empty($arParams["NAME_TEMPLATE"]) ? CSite::GetNameFormat(false) : $arParams["NAME_TEMPLATE"], $arUserTmp, true, false));
					if (empty($sName))
					{
						$sName = $arUserTmp["~LOGIN"];
					}

					$arFileTmp = CFile::ResizeImageGet(
						$arUserTmp["PERSONAL_PHOTO"],
						array('width' => 32, 'height' => 32),
						BX_RESIZE_IMAGE_EXACT,
						false
					);

					$arUsers['U'.$arUserTmp["ID"]] = Array(
						'id' => 'U'.$arUserTmp["ID"],
						'entityId' => $arUserTmp["ID"],
						'name' => $sName,
						'avatar' => empty($arFileTmp['src'])? '': $arFileTmp['src'],
						'desc' => $arUserTmp['WORK_POSITION'] ? $arUserTmp['WORK_POSITION'] : ($arUserTmp['PERSONAL_PROFESSION'] ? $arUserTmp['PERSONAL_PROFESSION'] : '&nbsp;'),
					);
					if (defined("BX_COMP_MANAGED_CACHE"))
					{
						$GLOBALS["CACHE_MANAGER"]->RegisterTag("USER_NAME_".IntVal($arUserTmp["ID"]));
					}
				}
			}
			else
			{
				$arUsers = Array();
				$dbUsers = CUser::GetList(($sort_by = Array('last_name'=>'asc', 'IS_ONLINE'=>'desc')), ($dummy=''), $arFilter, $arExtParams);
				while ($arUser = $dbUsers->GetNext())
				{
					if (
						!$bSelf
						&& is_object($USER)
						&& $userId == $arUser["ID"]
					)
						continue;

					$sName = trim(CUser::FormatName(empty($arParams["NAME_TEMPLATE"]) ? CSite::GetNameFormat(false) : $arParams["NAME_TEMPLATE"], $arUser, true, false));

					if (empty($sName))
						$sName = $arUser["~LOGIN"];

					$arFileTmp = CFile::ResizeImageGet(
						$arUser["PERSONAL_PHOTO"],
						array('width' => 32, 'height' => 32),
						BX_RESIZE_IMAGE_EXACT,
						false
					);

					$arUsers['U'.$arUser["ID"]] = Array(
						'id' => 'U'.$arUser["ID"],
						'entityId' => $arUser["ID"],
						'name' => $sName,
						'avatar' => empty($arFileTmp['src'])? '': $arFileTmp['src'],
						'desc' => $arUser['WORK_POSITION'] ? $arUser['WORK_POSITION'] : ($arUser['PERSONAL_PROFESSION'] ? $arUser['PERSONAL_PROFESSION'] : '&nbsp;'),
						'isExtranet' => (isset($GLOBALS["arExtranetUserID"]) && is_array($GLOBALS["arExtranetUserID"]) && in_array($arUser["ID"], $GLOBALS["arExtranetUserID"]) ? "Y" : "N")
					);
					if (defined("BX_COMP_MANAGED_CACHE"))
						$GLOBALS["CACHE_MANAGER"]->RegisterTag("USER_NAME_".IntVal($arUser["ID"]));
				}
			}

			if (defined("BX_COMP_MANAGED_CACHE"))
			{
				$GLOBALS["CACHE_MANAGER"]->RegisterTag("USER_NAME");
				$GLOBALS["CACHE_MANAGER"]->EndTagCache();
			}

			$obCache->EndDataCache($arUsers);
		}

		return $arUsers;
	}

	public static function GetGratMedalUsers($arParams = Array())
	{
		global $USER;

		$userId = intval($USER->GetID());

		if(!isset($GLOBALS["SOCNET_LOG_DESTINATION"]["GetGratMedalUsers"][$userId]))
		{
			$arSubordinateDepts = array();

			if (CModule::IncludeModule("intranet"))
				$arSubordinateDepts = CIntranetUtils::GetSubordinateDepartments($userId, true);

			$arFilter = Array(
				"ACTIVE" => "Y",
				"!UF_DEPARTMENT" => false
			);

			$arExtParams = Array(
				"FIELDS" => Array("ID", "LAST_NAME", "NAME", "SECOND_NAME", "LOGIN", "PERSONAL_PHOTO", "WORK_POSITION", "PERSONAL_PROFESSION", "IS_ONLINE"),
				"SELECT" => Array("UF_DEPARTMENT")
			);

			if (isset($arParams["id"]))
			{
				if (empty($arParams["id"]))
					$arFilter["ID"] = $userId;
				else
				{
					$arSelect = array();
					foreach ($arParams["id"] as $value)
						$arSelect[] = intval($value);
					$arFilter["ID"] = implode("|", $arSelect);
				}
			}

			$arGratUsers = Array();
			$arMedalUsers = Array();

			$dbUsers = CUser::GetList(($sort_by = Array("last_name" => "asc", "IS_ONLINE" => "desc")), ($dummy=''), $arFilter, $arExtParams);
			while ($arUser = $dbUsers->GetNext())
			{
				$sName = trim(CUser::FormatName(empty($arParams["NAME_TEMPLATE"]) ? CSite::GetNameFormat(false) : $arParams["NAME_TEMPLATE"], $arUser));

				if (empty($sName))
					$sName = $arUser["~LOGIN"];

				$arFileTmp = CFile::ResizeImageGet(
					$arUser["PERSONAL_PHOTO"],
					array("width" => 32, "height" => 32),
					BX_RESIZE_IMAGE_EXACT,
					false
				);

				$arGratUsers['U'.$arUser["ID"]] = Array(
					"id" => "U".$arUser["ID"],
					"entityId" => $arUser["ID"],
					"name" => $sName,
					"avatar" => empty($arFileTmp["src"]) ? '' : $arFileTmp["src"],
					"desc" => $arUser["WORK_POSITION"] ? $arUser["WORK_POSITION"] : ($arUser["PERSONAL_PROFESSION"] ? $arUser["PERSONAL_PROFESSION"] : "&nbsp;"),
				);

				if (
					count($arSubordinateDepts) > 0
					&& count(array_intersect($arSubordinateDepts, $arUser["UF_DEPARTMENT"])) > 0
				)
					$arMedalUsers['U'.$arUser["ID"]] = $arGratUsers['U'.$arUser["ID"]];
			}
			$GLOBALS["SOCNET_LOG_DESTINATION"]["GetGratMedalUsers"][$userId] = array("GRAT" => $arGratUsers, "MEDAL" => $arMedalUsers);
		}

		return $GLOBALS["SOCNET_LOG_DESTINATION"]["GetGratMedalUsers"][$userId];
	}

	public function __percent_walk(&$val)
	{
		$val = str_replace('%', '', $val)."%";
	}

	public static function SearchUsers($search, $nameTemplate = "", $bSelf = true, $bEmployeesOnly = false, $bExtranetOnly = false, $departmentId = false)
	{

		CUtil::JSPostUnescape();

		$arUsers = array();
		$search = trim($search);
		if (
			strlen($search) <= 0
			|| !GetFilterQuery("TEST", $search)
		)
		{
			return $arUsers;
		}

		$bIntranetEnable = IsModuleInstalled('intranet');
		$bExtranetEnable = CModule::IncludeModule('extranet');
		$bBitrix24Enable = IsModuleInstalled('bitrix24');
		$bExtranetUser = ($bExtranetEnable && !CExtranet::IsIntranetUser());
		$current_user_id = intval($GLOBALS["USER"]->GetID());

		if ($bExtranetEnable)
		{
			CSocNetTools::InitGlobalExtranetArrays();
		}

		$arSearchValue = preg_split('/\s+/', trim($search));
		array_walk($arSearchValue, array('CSocNetLogDestination', '__percent_walk'));
		$arFilter = array(
			array(
				'LOGIC' => 'OR',
				'%=NAME' => $arSearchValue,
				'%=LAST_NAME' => $arSearchValue,
				'%=EMAIL' => $search,
				'%=LOGIN' => $search,
			),
			'ACTIVE' => 'Y'
		);

		if (
			$bIntranetEnable
			|| COption::GetOptionString("main", "new_user_registration_email_confirmation", "N") == "Y"
		)
		{
			$arFilter["CONFIRM_CODE"] = false;
		}

		if (
			$bEmployeesOnly
			|| ($bBitrix24Enable && !$bExtranetEnable)
		)
		{
			$arFilter["!UF_DEPARTMENT"] = false;
		}
		elseif ($bExtranetOnly)
		{
			$arFilter["UF_DEPARTMENT"] = false;
		}

		if(
			$bIntranetEnable
			&& $bExtranetEnable
			&& (
				$bExtranetUser
				|| !$bEmployeesOnly
			)
		)
		{
			$arFilteredUserIDs = CExtranet::GetMyGroupsUsersSimple(CExtranet::GetExtranetSiteID());

			if ($bExtranetUser)
			{
				$arFilter["ID"] = array_merge(array($current_user_id), $arFilteredUserIDs);
			}
			else
			{
				$arFilter[] = array(
					'LOGIC' => 'OR',
					'!UF_DEPARTMENT' => false,
					'ID' => array_merge(array($current_user_id), $arFilteredUserIDs)
				);
			}
		}

		$arSelect = array("ID", "NAME", "LAST_NAME", "SECOND_NAME", "EMAIL", "LOGIN", "WORK_POSITION", "PERSONAL_PROFESSION", "PERSONAL_PHOTO", "PERSONAL_GENDER");
		if ($bIntranetEnable)
		{
			$arSelect[] = 'UF_DEPARTMENT';
		}

		if (intval($departmentId) > 0)
		{
			$acc = new CAccess;
		}

		$rsUser = \Bitrix\Main\UserTable::getList(array(
			'order' => array(
				'LAST_NAME' => 'ASC'
			),
			'filter' => $arFilter,
			'select' => $arSelect,
			'limit' => 20,
			'data_doubling' => false
		));

		while ($arUser = $rsUser->fetch())
		{
			if (
				!$bSelf
				&& $current_user_id == $arUser['ID']
			)
			{
				continue;
			}

			if (is_object($acc)) /* if (intval($departmentId) > 0) */
			{
				$acc->UpdateCodes(array("USER_ID" => $arUser["ID"]));

				$arUserGroupCode = CAccess::GetUserCodesArray(
					$arUser["ID"], 
					array("PROVIDER_ID" => "intranet")
				);

				if (!in_array("DR".intval($departmentId), $arUserGroupCode))
				{
					continue;
				}
			}

			$sName = CUser::FormatName(empty($nameTemplate) ? CSite::GetNameFormat(false) : $nameTemplate, $arUser, true, true);

			$arFileTmp = CFile::ResizeImageGet(
				$arUser["PERSONAL_PHOTO"],
				array('width' => 32, 'height' => 32),
				BX_RESIZE_IMAGE_EXACT,
				false
			);

			$arUsers['U'.$arUser["ID"]] = Array(
				'id' => 'U'.$arUser["ID"],
				'entityId' => $arUser["ID"],
				'name' => $sName,
				'avatar' => empty($arFileTmp['src'])? '': $arFileTmp['src'],
				'desc' => (
					$arUser['WORK_POSITION'] 
						? $arUser['WORK_POSITION'] 
						: (
							$arUser['PERSONAL_PROFESSION']
								? $arUser['PERSONAL_PROFESSION']
								: '&nbsp;'
						)
				),
				'isExtranet' => (isset($GLOBALS["arExtranetUserID"]) && is_array($GLOBALS["arExtranetUserID"]) && in_array($arUser["ID"], $GLOBALS["arExtranetUserID"]) ? "Y" : "N")
			);

			$checksum = md5(serialize($arUsers['U'.$arUser["ID"]]));
			$arUsers['U'.$arUser["ID"]]['checksum'] = $checksum;
		}

		return $arUsers;
	}

	public static function GetSocnetGroup($arParams = Array())
	{
		global $USER;

		$userId = intval($USER->GetID());

		$arSocnetGroups = array();
		$arSelect = Array();
		if (isset($arParams['id']))
		{
			if (empty($arParams['id']))
				return $arSocnetGroups;
			else
				foreach ($arParams['id'] as $value)
					$arSelect[] = intval($value);
		}
		
		if (
			isset($arParams['site_id'])
			&& strlen($arParams['site_id']) > 0
		)
		{
			$siteId = $arParams['site_id'];
		}
		else
		{
			$siteId = SITE_ID;		
		}

		$arFilter = array(
			"USER_ID" => $userId,
			"ID" => $arSelect,
			"<=ROLE" => SONET_ROLES_USER,
			"GROUP_SITE_ID" => $siteId,
			"GROUP_ACTIVE" => "Y"
		);

		if(isset($arParams['GROUP_CLOSED']))
		{
			$arFilter['GROUP_CLOSED'] = $arParams['GROUP_CLOSED'];
		}

		$arSocnetGroupsTmp = array();
		$rsGroups = CSocNetUserToGroup::GetList(
			array("GROUP_NAME" => "ASC"),
			$arFilter,
			false,
			array("nTopCount" => 500),
			array("ID", "GROUP_ID", "GROUP_NAME", "GROUP_DESCRIPTION", "GROUP_IMAGE_ID")
		);
		while($arGroup = $rsGroups->Fetch())
		{
			$arGroupTmp = array(
				"id" => $arGroup["GROUP_ID"],
				"entityId" => $arGroup["GROUP_ID"],
				"name" => htmlspecialcharsbx($arGroup["GROUP_NAME"]),
				"desc" => htmlspecialcharsbx($arGroup["GROUP_DESCRIPTION"])
			);
			if($arGroup["GROUP_IMAGE_ID"])
			{
				$imageFile = CFile::GetFileArray($arGroup["GROUP_IMAGE_ID"]);
				if ($imageFile !== false)
				{
					$arFileTmp = CFile::ResizeImageGet(
						$imageFile,
						array(
							"width" => (intval($arParams["THUMBNAIL_SIZE_WIDTH"]) > 0 ? $arParams["THUMBNAIL_SIZE_WIDTH"] : 30),
							"height" => (intval($arParams["THUMBNAIL_SIZE_HEIGHT"]) > 0 ? $arParams["THUMBNAIL_SIZE_HEIGHT"] : 30)
						),
						BX_RESIZE_IMAGE_PROPORTIONAL,
						false
					);
					$arGroupTmp["avatar"] = $arFileTmp["src"];
				}
			}
			$arSocnetGroupsTmp[$arGroupTmp['id']] = $arGroupTmp;
		}
		if (isset($arParams['features']) && !empty($arParams['features']))
			self::GetSocnetGroupFilteredByFeaturePerms($arSocnetGroupsTmp, $arParams['features']);

		foreach ($arSocnetGroupsTmp as $value)
		{
			$value['id'] = 'SG'.$value['id'];
			$arSocnetGroups[$value['id']] = $value;
		}

		return $arSocnetGroups;
	}

	public static function GetTreeList($id, $relation, $compat = false)
	{
		if ($compat)
		{
			$tmp = array();
			foreach($relation as $iid => $rel)
			{
				$p = $rel["parent"];
				if (!isset($tmp[$p]))
					$tmp[$p] = array();
				$tmp[$p][] = $iid;
			}
			$relation = $tmp;
		}

		$arRelations = Array();
		if (is_array($relation[$id]))
		{
			foreach ($relation[$id] as $relId)
			{
				$arItems = Array();
				if (isset($relation[$relId]) && !empty($relation[$relId]))
					$arItems = self::GetTreeList($relId, $relation);

				$arRelations[$relId] = Array('id'=>$relId, 'type' => 'category', 'items' => $arItems);
			}
		}

		return $arRelations;
	}

	private static function GetSocnetGroupFilteredByFeaturePerms(&$arGroups, $arFeaturePerms)
	{
		$arGroupsIDs = array();
		foreach($arGroups as $value)
		{
			$arGroupsIDs[] = $value["id"];
		}

		if (sizeof($arGroupsIDs) > 0)
		{
			$feature = $arFeaturePerms[0];
			$operations = $arFeaturePerms[1];
			if (!is_array($operations))
				$operations = explode(",", $operations);
			$arGroupsPerms = array();
			foreach($operations as $operation)
			{
				$tmpOps = CSocNetFeaturesPerms::CurrentUserCanPerformOperation(SONET_ENTITY_GROUP, $arGroupsIDs, $feature, $operation);
				foreach($tmpOps as $key=>$val)
					if (!$arGroupsPerms[$key])
						$arGroupsPerms[$key] = $val;
			}
			$arGroupsActive = CSocNetFeatures::IsActiveFeature(SONET_ENTITY_GROUP, $arGroupsIDs, $arFeaturePerms[0]);
			foreach ($arGroups as $key=>$group)
				if (!$arGroupsActive[$group["id"]] || !$arGroupsPerms[$group["id"]])
					unset($arGroups[$key]);
		}
	}

	public static function GetDestinationUsers($arCodes, $bFetchUsers = false)
	{
		global $DB;
		$arUsers = array();

		$arCodes2 = array();
		if (!$bFetchUsers)
		{
			foreach($arCodes as $code)
			{
				if (substr($code, 0, 1) === 'U' && $code !== 'UA')
				{
					$id = intVal(substr($code, 1));
					if($id > 0)
					{
						$arUsers[] = $id;
						continue;
					}
				}

				if (substr($code, 0, 2) === 'SG')
					$arCodes2[] = $code.'_K';
				$arCodes2[] = $code;
			}
			$bUnique = count($arCodes2) > 0 && count($arUsers) > 0;
		}
		else
		{
			foreach($arCodes as $code)
			{
				if (substr($code, 0, 2) === 'SG')
					$arCodes2[] = $code.'_K';
				$arCodes2[] = $code;
			}
			$bUnique = false;
		}

		$obUserFieldsSql = new CUserTypeSQL();
		$obUserFieldsSql->SetEntity("USER", "USER_ID");
		$obUserFieldsSql->SetFilter(array(
			"!UF_DEPARTMENT" => false
		));

		$where = $obUserFieldsSql->GetFilter();
		$join = $obUserFieldsSql->GetJoin("UA.USER_ID");

		$strCodes = in_array('UA', $arCodes2) ? "'G2'" : "'".join("','", $arCodes2)."'";

		if ($bFetchUsers)
		{
			$strSql = "SELECT DISTINCT UA.USER_ID, U.LOGIN, U.NAME, U.LAST_NAME, U.SECOND_NAME, U.EMAIL, U.PERSONAL_PHOTO, U.WORK_POSITION ".
				"FROM b_user_access UA
				INNER JOIN b_user U ON (U.ID=UA.USER_ID)".
				$join.
				" WHERE ACCESS_CODE in (".$strCodes.") AND ".$where;
		}
		else
		{
			$strSql = "SELECT DISTINCT USER_ID ".
				"FROM b_user_access UA ".
				$join.
				" WHERE ACCESS_CODE in (".$strCodes.") AND ".$where;
		}

		$res = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		if ($bFetchUsers)
		{
			while($ar = $res->Fetch())
			{
				if ($ar > 0)
					$arUsers[] = $ar;
			}
		}
		else
		{
			while($ar = $res->Fetch())
			{
				if($ar['USER_ID'] > 0)
					$arUsers[] = $ar['USER_ID'];
			}
		}

		if ($bUnique)
			$arUsers = array_unique($arUsers);

		return $arUsers;
	}
}
?>