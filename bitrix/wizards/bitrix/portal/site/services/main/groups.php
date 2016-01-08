<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();
/*
$arTask = Array(
	"NAME" => "editors_task",
	"MODULE_ID" => "main",
	"BINDING" => "module",
	"SYS" => "N",
	"LETTER" => "P",
);

$taskID = CTask::Add($arTask);
if (intval($taskID) > 0)
{
	CTask::SetOperations(
		$taskID,
		Array("edit_own_profile", "view_own_profile", "cache_control"),
		$bOpNames = true
	);
}
*/

if(WIZARD_FIRST_INSTAL !== "Y")
{
	$arGroups = Array();
	if(!WIZARD_IS_RERUN)
	{
		$arGroups = Array(
			Array(
				"ACTIVE" => "Y",
				"C_SORT" => 7,
				"NAME" => GetMessage("ADMIN_SECTION_GROUP_NAME"),
				"DESCRIPTION" => GetMessage("ADMIN_SECTION_GROUP_DESC"),
				"STRING_ID" => "ADMIN_SECTION",
				"TASKS_MODULE" => Array(),
				"TASKS_FILE" => Array(
					Array("fm_folder_access_read", "/bitrix/admin/"),
				),
			),
			Array(
				"ACTIVE" => "Y",
				"C_SORT" => 10,
				"NAME" => GetMessage("SUPPORT_GROUP_NAME"),
				"DESCRIPTION" => GetMessage("SUPPORT_GROUP_DESC"),
				"STRING_ID" => "SUPPORT",
				"TASKS_MODULE" => Array(),
				"TASKS_FILE" => Array(
					Array("fm_folder_access_read", "/bitrix/admin/"),
				),
			),
			Array(
				"ACTIVE" => "Y",
				"C_SORT" => 8,
				"NAME" => GetMessage("CREATE_GROUPS_GROUP_NAME"),
				"DESCRIPTION" => GetMessage("CREATE_GROUPS_GROUP_DESC"),
				"STRING_ID" => "CREATE_GROUPS",
				"TASKS_MODULE" => Array(),
				"TASKS_FILE" => Array(),
			),
			Array(
				"ACTIVE" => "Y",
				"C_SORT" => 4,
				"NAME" => WIZARD_SITE_NAME . ": " . GetMessage("PERSONNEL_DEPARTMENT_GROUP_NAME"),
				"DESCRIPTION" => GetMessage("PERSONNEL_DEPARTMENT_GROUP_DESC"),
				"STRING_ID" => "PERSONNEL_DEPARTMENT",
				"TASKS_MODULE" => Array("main_edit_subordinate_users"),
				"TASKS_FILE" => Array(
					Array("fm_folder_access_write", WIZARD_SITE_DIR."company/"),
					Array("fm_folder_access_write", WIZARD_SITE_DIR."about/"),
					Array("fm_folder_access_read", "/bitrix/admin/"),
				),
			),
			Array(
				"ACTIVE" => "Y",
				"C_SORT" => 5,
				"NAME" => WIZARD_SITE_NAME . ": " . GetMessage("DIRECTION_GROUP_NAME"),
				"DESCRIPTION" => GetMessage("DIRECTION_GROUP_DESC"),
				"STRING_ID" => "DIRECTION",
				"TASKS_MODULE" => Array(),
				"TASKS_FILE" => Array(
					Array("fm_folder_access_read", WIZARD_SITE_DIR."docs/sale/"),
					Array("fm_folder_access_read", WIZARD_SITE_DIR."docs/manage/"),
					Array("fm_folder_access_read", "/bitrix/admin/"),
				),
			),
			Array(
				"ACTIVE" => "Y",
				"C_SORT" => 9,
				"NAME" => WIZARD_SITE_NAME . ": " . GetMessage("MARKETING_AND_SALES_GROUP_NAME"),
				"DESCRIPTION" => GetMessage("MARKETING_AND_SALES_GROUP_DESC"),
				"STRING_ID" => "MARKETING_AND_SALES",
				"TASKS_MODULE" => Array(),
				"TASKS_FILE" => Array(
					Array("fm_folder_access_read", WIZARD_SITE_DIR."docs/sale/"),
				),
			),
		);
	}
	$arGroups[] = Array(
			"ACTIVE" => "Y",
			"C_SORT" => 3,
			"NAME" => WIZARD_SITE_NAME . ": " . GetMessage("EMPLOYEES_GROUP_NAME"),
			"DESCRIPTION" => GetMessage("EMPLOYEES_GROUP_DESC"),
			"STRING_ID" => "EMPLOYEES_".WIZARD_SITE_ID,
			"TASKS_MODULE" => Array("main_change_profile"),
			"TASKS_FILE" => Array(
			),
		);
	$arGroups[] = Array(
			"ACTIVE" => "Y",
			"C_SORT" => 6,
			"NAME" => WIZARD_SITE_NAME . ": " . GetMessage("PORTAL_ADMINISTRATION_GROUP_NAME"),
			"DESCRIPTION" => GetMessage("PORTAL_ADMINISTRATION_GROUP_DESC"),
			"STRING_ID" => "PORTAL_ADMINISTRATION_".WIZARD_SITE_ID,
			"TASKS_MODULE" => Array("main_edit_subordinate_users"),
			"TASKS_FILE" => Array(
				Array("fm_folder_access_full", WIZARD_SITE_DIR),
				Array("fm_folder_access_read", "/bitrix/admin/"),
			),
		);

	$SiteGroup = array();
	$SiteGroups = array();

	$dbResult = CGroup::GetList($by, $order, Array("STRING_ID" => "RATING_VOTE", "STRING_ID_EXACT_MATCH" => "Y"));
	if ($arExistsGroup = $dbResult->Fetch())
		$SiteGroups["RATING_VOTE"] = $arExistsGroup["ID"];
	$dbResult = CGroup::GetList($by, $order, Array("STRING_ID" => "RATING_VOTE_AUTHORITY", "STRING_ID_EXACT_MATCH" => "Y"));
	if ($arExistsGroup = $dbResult->Fetch())
		$SiteGroups["RATING_VOTE_AUTHORITY"] = $arExistsGroup["ID"];
	
	$group = new CGroup;
	foreach ($arGroups as $arGroup)
	{

		//Add Group
		$dbResult = CGroup::GetList($by, $order, Array("STRING_ID" => $arGroup["STRING_ID"], "STRING_ID_EXACT_MATCH" => "Y"));
		if ($arExistsGroup = $dbResult->Fetch())
			$groupID = $arExistsGroup["ID"];
		else
			$groupID = $group->Add($arGroup);

		if ($groupID <= 0)
			continue;

		$SiteGroup["STRING_ID"] = $arGroup["STRING_ID"];
		$SiteGroups[$arGroup["STRING_ID"]] = $groupID;

		//Set tasks binding to module
		$arTasksID = Array();
		foreach ($arGroup["TASKS_MODULE"] as $taskName)
		{
			$dbResult = CTask::GetList(Array(), Array("NAME" => $taskName));
			if ($arTask = $dbResult->Fetch())
				$arTasksID[] = $arTask["ID"];
		}

		if (!empty($arTasksID))
			CGroup::SetTasks($groupID, $arTasksID, true);

		//Set tasks binding to file
		foreach ($arGroup["TASKS_FILE"] as $arFile)
		{
			$taskName = $arFile[0];
			$filePath = $arFile[1];

			$dbResult = CTask::GetList(Array(), Array("NAME" => $taskName));
			if ($arTask = $dbResult->Fetch())
				WizardServices::SetFilePermission(Array(WIZARD_SITE_ID, $filePath), Array($groupID => "T_".$arTask["ID"]));
		}

		if ($arGroup["STRING_ID"] == "EMPLOYEES_".WIZARD_SITE_ID){
			WizardServices::SetFilePermission(Array(WIZARD_SITE_ID, WIZARD_SITE_DIR), Array("*" => 'D'));
			WizardServices::SetFilePermission(Array(WIZARD_SITE_ID, WIZARD_SITE_DIR), Array($groupID => 'R'));
		}

		if (WIZARD_IS_RERUN === false)
		{
			if ($arGroup["STRING_ID"] == "EMPLOYEES_".WIZARD_SITE_ID)
			{
				COption::SetOptionString("main", "new_user_registration_def_group", $groupID);

			}	
		}				
	}			 
	if(!WIZARD_IS_RERUN)
	{
		if (CModule::IncludeModule("learning"))
		{
			//learning rights
			$oAccess = CLearnAccess::GetInstance($USER->GetID());
			$perms = $oAccess->GetBasePermissions();

			CLearnAccess::ListAllPossibleRights();
			$arRights = CLearnAccess::ListAllPossibleRights();
			foreach($arRights as $id=>$right)
			{
				if ($right["name"] == "learning_lesson_access_manage_dual")
				{
					$taskId = $id;
					break;
				}
			}
			$perms["G".$SiteGroups["PORTAL_ADMINISTRATION_".WIZARD_SITE_ID]] = $taskId;
			$oAccess->SetBasePermissions($perms);
		}
	}
	WizardServices::SetFilePermission(Array(WIZARD_SITE_ID, WIZARD_SITE_DIR."upload/"), Array("*" => "R"));

	//admin security policy
	$z = CGroup::GetByID(1);
	if($res = $z->Fetch())
	{
		if($res["SECURITY_POLICY"] == "")
		{
			$group = new CGroup;
			$arGroupPolicy = array(
				"SESSION_TIMEOUT" => 15, //minutes
				"SESSION_IP_MASK" => "255.255.255.255",
				"MAX_STORE_NUM" => 1,
				"STORE_IP_MASK" => "255.255.255.255",
				"STORE_TIMEOUT" => 60*24*3, //minutes
				"CHECKWORD_TIMEOUT" => 60,  //minutes
				"PASSWORD_LENGTH" => 10,
				"PASSWORD_UPPERCASE" => "Y",
				"PASSWORD_LOWERCASE" => "Y",
				"PASSWORD_DIGITS" => "Y",
				"PASSWORD_PUNCTUATION" => "Y",
				"LOGIN_ATTEMPTS" => 3,
			);
			$arFields = array(
				"SECURITY_POLICY" => serialize($arGroupPolicy)
			);
			$group->Update(1, $arFields);
		}
	}
	if (!WIZARD_IS_RERUN === false)
	{
		$dbResult = CGroup::GetList($by, $order, Array("STRING_ID" => "EMPLOYEES_".WIZARD_SITE_ID, "STRING_ID_EXACT_MATCH" => "Y"));
		if ($arExistsGroup = $dbResult->Fetch())
			$groupID = $arExistsGroup["ID"];

		if($groupID && WIZARD_SITE_DEPARTAMENT){

			$rsIBlock = CIBlock::GetList(array(), array("CODE" => "departments", "TYPE" => "structure"));
			$iblockID = false;
			if ($arIBlock = $rsIBlock->Fetch())
			{
				$iblockID = $arIBlock["ID"];

				$arFilter["ID"] = WIZARD_SITE_DEPARTAMENT;
				$rsSections = CIBlockSection::GetList(array(), $arFilter);
				$arSection = $rsSections->GetNext();

				$arFilter = array (
					"LEFT_MARGIN" => $arSection["LEFT_MARGIN"],
					"RIGHT_MARGIN" => $arSection["RIGHT_MARGIN"],
					"BLOCK_ID" => $iblockID,
					'ACTIVE' => 'Y',
					'GLOBAL_ACTIVE' => 'Y',
				);

				$rsSections = CIBlockSection::GetList(array("left_margin"=>"asc"), $arFilter);
				$arSectionUsers = array();
				while($arSection = $rsSections->GetNext())
				{
					$arSectionUsers[] =  $arSection['ID'];

				}

				$rsUsers = CUser::GetList(($by="id"), ($order="asc"), array("UF_DEPARTMENT" => $arSectionUsers));
				while($arUsers = $rsUsers->Fetch()) {
					$arGroups = CUser::GetUserGroup($arUsers["ID"]);
					$arGroups[] = $groupID;
					CUser::SetUserGroup($arUsers["ID"], $arGroups);

				}
			}

			$dbResult = CGroup::GetList($by, $order, Array("STRING_ID" => "PERSONNEL_DEPARTMENT", "STRING_ID_EXACT_MATCH" => "Y"));
			if ($arExistsGroup = $dbResult->Fetch()){
				$groupID = $arExistsGroup["ID"];
				$arSubordinateGroups = CGroup::GetSubordinateGroups($groupID);
				$arSubordinateGroups[] = $SiteGroups["EMPLOYEES_".WIZARD_SITE_ID];
				$arSubordinateGroups[] = $SiteGroups["RATING_VOTE"];
				$arSubordinateGroups[] = $SiteGroups["RATING_VOTE_AUTHORITY"];
				CGroup::SetSubordinateGroups($groupID, $arSubordinateGroups);
			}

			CGroup::SetSubordinateGroups($SiteGroups["PORTAL_ADMINISTRATION_".WIZARD_SITE_ID], Array($SiteGroups["EMPLOYEES_".WIZARD_SITE_ID]));
		
		}

		/*$allowGuests = COption::GetOptionString("main", "wizard_allow_group", "N", WIZARD_SITE_ID);
		if($allowGuests == "Y")
		{
			$dbResult = CGroup::GetList($by, $order, Array("STRING_ID_EXACT_MATCH" => "Y"));
			while ($arExistsGroup = $dbResult->Fetch())
			{
				if($arExistsGroup["ID"] != 1 && $arExistsGroup["ID"] !=2)
				{
					if(!in_array($arExistsGroup["STRING_ID"], $SiteGroup["STRING_ID"]))
					{
						$allowGuests = COption::GetOptionString("main", "wizard_allow_group", "N", $site_id);
						WizardServices::SetFilePermission(Array(WIZARD_SITE_ID, WIZARD_SITE_DIR), Array($arExistsGroup["ID"] => "D"));
					}
				}
			}
		}  */
	}
}

?>