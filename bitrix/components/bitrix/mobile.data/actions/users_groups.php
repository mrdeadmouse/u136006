<?if(!Defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

//cache data

global $USER,$APPLICATION;
$cache = new CPHPCache();
$cache_time = 3600*24*365;
$detailurl = $_REQUEST["detail_url"];
$cache_path = '/mobile_cache/'.$action;
$data = array();
$action = $_REQUEST["mobile_action"];
if (in_array($action, array("get_user_list", "get_usergroup_list")))
{
	$withTags = ($_REQUEST["tags"] == "N" ? "N" : "Y");
	$nameTemplate = CSite::GetNameFormat();
	$cache_id = "mobileAction|get_users|".$GLOBALS["USER"]->GetID()."|".$detailurl."|".$withTags."|".LANGUAGE_ID."|". $nameTemplate;
	if ($cache->InitCache($cache_time, $cache_id, $cache_path))
	{
		$cachedData = $cache->GetVars();
		$data = $cachedData["DATA"];
		$tableType = $cachedData["TYPE"];
	}
	else
	{
		$GLOBALS["CACHE_MANAGER"]->StartTagCache($cache_path);
		$GLOBALS["CACHE_MANAGER"]->RegisterTag("sonet_user2group_U".$GLOBALS["USER"]->GetID());
		$GLOBALS["CACHE_MANAGER"]->RegisterTag("USER_CARD");

		$tmpData = array(
			"NAME" => GetMessage("MD_EMPLOYEES_ALL"),
			"ID" => 0,
			"OUTSECTION" => true,
			"bubble_background_color" => "#A7F264",
			"bubble_text_color" => "#54901E",
		);

		if (SITE_CHARSET != "utf-8")
		{
			$tmpData = $APPLICATION->ConvertCharsetArray($tmpData, SITE_CHARSET, "utf-8");
		}

		$data = Array(
			$tmpData
		);
		
		if (
			!CModule::IncludeModule('extranet') 
			|| CExtranet::IsIntranetUser()
		)
		{
			$filter = array(
				"ACTIVE" => "Y",
				"!UF_DEPARTMENT" => false
			);
		}
		else
		{
			$filter = array(
				"ACTIVE" => "Y"
			);

			$arUserID = CExtranet::GetMyGroupsUsersSimple(SITE_ID);
			if (!empty($arUserID))
			{
				$filter["ID"] = implode('|', $arUserID);
			}
			else
			{
				$filter = false;
			}
		}

		if ($filter)
		{
			$arParams = Array("FIELDS" => Array("NAME", "ID", "PERSONAL_PHOTO", "LAST_NAME", "WORK_POSITION", "LOGIN"));
			if($withTags == "Y")
			{
				$iblockId = COption::GetOptionInt('intranet', 'iblock_structure', 0);
				$arDepartaments = Array();
				$arSectionFilter = array(
					'IBLOCK_ID' => $iblockId,
				);
				CModule::IncludeModule("iblock");
				$dbRes = CIBlockSection::GetList(
					array('LEFT_MARGIN' => 'DESC'),
					$arSectionFilter,
					false,
					array('ID', 'NAME')
				);

				while ($arRes = $dbRes->Fetch())
				{
					$arDepartaments[$arRes["ID"]] = trim($arRes["NAME"]);
				}
				$arParams["SELECT"] = Array("UF_DEPARTMENT");
			}

			$dbUsers = CUser::GetList(
				($by = array("last_name"=>"asc", "name"=>"asc")),
				($order = false),
				$filter,
				$arParams
			);
			while($userData = $dbUsers->Fetch())
			{
				if (intval($userData["PERSONAL_PHOTO"]) > 0)
				{
					$arImage = CFile::ResizeImageGet(
						$userData["PERSONAL_PHOTO"],
						array("width" => 64, "height" => 64),
						BX_RESIZE_IMAGE_EXACT,
						false,
						false,
						true
					);
					$img_src = $arImage["src"];
				}
				else
				{
					$img_src = false;
				}

				$tmpData = Array(
					"NAME" => CUser::FormatName("#LAST_NAME# #NAME#", $userData, true, false),
					"ID" => $userData["ID"],
					"IMAGE" => $img_src,
					"URL" => $detailurl.$userData["ID"],
					"TAGS"=> "",
					'WORK_POSITION' => $userData['WORK_POSITION'],
					'WORK_DEPARTMENTS' => array()
				);

				$arUserDepartments = array();
				if (!empty($userData['UF_DEPARTMENT']))
				{
					foreach ($userData['UF_DEPARTMENT'] as $departmentId)
					{
						$arUserDepartments[] = $arDepartaments[$departmentId];
					}
				}

				if (empty($arUserDepartments)) // extranet
				{
					$tmpData["bubble_background_color"] = "#FFEC91";
					$tmpData["bubble_text_color"] = "#B54827";
				}
				else
				{
					$tmpData["bubble_background_color"] = "#BCEDFC";
					$tmpData["bubble_text_color"] = "#1F6AB5";
				}

				if($withTags == "Y")
				{
					if (empty($arUserDepartments))
					{
						$arUserDepartments[] = GetMessage("MD_EXTRANET");
					}

					$tmpTags = array_merge(
						array(trim($userData['WORK_POSITION'])),
						$arUserDepartments
					);

					$tmpData["TAGS"] = implode(",", $tmpTags);
					$tmpData['WORK_DEPARTMENTS'] = $arUserDepartments;
				}

				if (SITE_CHARSET != "utf-8")
				{
					$tmpData = $APPLICATION->ConvertCharsetArray($tmpData, SITE_CHARSET, "utf-8");
				}
				$data[] = $tmpData;
			}
		}

		$GLOBALS["CACHE_MANAGER"]->EndTagCache();

		$tableType = "a_users";

		if ($cache->StartDataCache())
			$cache->EndDataCache(
				array(
					"DATA" => $data,
					"TYPE" => $tableType
				)
			);
	}
	$tableTitle = GetMessage("MD_EMPLOYEES_TITLE");
	$tableData = AddTableData($tableData, $data, $tableTitle, $tableType);
}

if (in_array($action, array("get_group_list", "get_usergroup_list")))
{
	$cache_id = "mobileAction|get_groups|".$GLOBALS["USER"]->GetID()."|".$detailurl;
	if ($cache->InitCache($cache_time, $cache_id, $cache_path))
	{
		$cachedData = $cache->GetVars();
		$data = $cachedData["DATA"];
		$tableType = $cachedData["TYPE"];
	}
	else
	{
		if(CModule::IncludeModule("socialnetwork"))
		{
			$GLOBALS["CACHE_MANAGER"]->StartTagCache($cache_path);
			$GLOBALS["CACHE_MANAGER"]->RegisterTag("sonet_user2group_U".$GLOBALS["USER"]->GetID());
			$GLOBALS["CACHE_MANAGER"]->RegisterTag("sonet_group");

			$data = Array();

			$arSonetGroups = CSocNetLogDestination::GetSocnetGroup(
				array(
					"features" => array("blog", array("premoderate_post", "moderate_post", "write_post", "full_post")),
					"THUMBNAIL_SIZE_WIDTH" => 64,
					"THUMBNAIL_SIZE_HEIGHT" => 64
				)
			);

			foreach($arSonetGroups as $arSocnetGroup)
			{
				$tmpData = Array(
					"NAME" => htmlspecialcharsback($arSocnetGroup["name"]),
					"ID" => $arSocnetGroup["entityId"],
					"IMAGE" => $arSocnetGroup["avatar"],
					"bubble_background_color" => "#FFD5D5",
					"bubble_text_color" => "#B54827",
				);

				if (ToUpper(SITE_CHARSET) != "UTF-8")
				{
					$tmpData = $APPLICATION->ConvertCharsetArray($tmpData, SITE_CHARSET, "utf-8");
				}
				$data[] = $tmpData;
			}

			$GLOBALS["CACHE_MANAGER"]->EndTagCache();

			$tableType = "b_groups";

			if ($cache->StartDataCache())
			{
				$cache->EndDataCache(
					array(
						"DATA" => $data,
						"TYPE" => $tableType
					)
				);
			}
		}
	}
	$tableTitle = GetMessage("MD_GROUPS_TITLE");

	if (count($data) > 0)
		$tableData = AddTableData($tableData, $data, $tableTitle, $tableType);
}

$data = $tableData;

return $data;
?>