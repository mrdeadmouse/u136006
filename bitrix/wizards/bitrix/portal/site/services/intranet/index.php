<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if(!CModule::IncludeModule("intranet"))
	return;

$arCurPhpVer = Explode(".", PhpVersion());
if (IntVal($arCurPhpVer[0]) < 5)
	return;


COption::SetOptionString("intranet", "iblock_type", "structure");
COption::SetOptionString("intranet", "search_user_url", WIZARD_SITE_DIR."company/personal/user/#ID#/", false, WIZARD_SITE_ID);

COption::SetOptionString("intranet", "tz_transition", "Y");
COption::SetOptionString("intranet", "tz_transition_daylight", '<transitionRule month="3" day="su" weekdayOfMonth="last" /><transitionTime>2:0:0</transitionTime>');
COption::SetOptionString("intranet", "tz_transition_standard", '<transitionRule month="10" day="su" weekdayOfMonth="last" /><transitionTime>3:0:0</transitionTime>');

$arIblockCode = Array(
	"iblock_structure" => "departments",
	"iblock_absence" => "absence",
	"iblock_honour" => "honour",
	"iblock_state_history" => "state_history",
);

foreach ($arIblockCode as $option => $iblockCode)
{
	$rsIBlock = CIBlock::GetList(array(), array("CODE" => $iblockCode, "TYPE" => "structure"));
	if ($arIBlock = $rsIBlock->Fetch())
		COption::SetOptionString("intranet", $option ,$arIBlock["ID"]);
}

if ($structure_iblock_id = COption::GetOptionInt('intranet', 'iblock_structure', 0))
{
	$arLabels = array();
	$arBindingLabels = array();
	$dbRes = CLanguage::GetList($by = 'sort', $order = 'asc');
	while ($arRes = $dbRes->Fetch())
	{
		if (file_exists(dirname(__FILE__).'/'.$arRes['LID'].'/labels.php'))
			require(dirname(__FILE__).'/'.$arRes['LID'].'/labels.php');
	}

	$obUT = new CUserTypeEntity();
	$dbRes = $obUT->GetList(array('ID' => 'ASC'), array('ENTITY_ID' => 'IBLOCK_'.$structure_iblock_id.'_SECTION', 'FIELD_NAME' => 'UF_HEAD'));
	if (!$dbRes->Fetch())
	{
		$obUT->Add(array(
			'ENTITY_ID' => 'IBLOCK_'.$structure_iblock_id.'_SECTION',
			'FIELD_NAME' => 'UF_HEAD',
			'USER_TYPE_ID' => 'employee',
			'EDIT_FORM_LABEL' => $arLabels,
		));
	}
	
	// for hr-xml import
	$dbRes = $obUT->GetList(array('ID' => 'ASC'), array('ENTITY_ID' => 'USER', 'FIELD_NAME' => 'UF_WORK_BINDING'));
	if (!$dbRes->Fetch())
	{
		$obUT->Add(array(
			'ENTITY_ID' => 'USER',
			'FIELD_NAME' => 'UF_WORK_BINDING',
			'USER_TYPE_ID' => 'string',
			'SHOW_IN_LIST' => 'N',
			'EDIT_IN_LIST' => 'N',
			'EDIT_FORM_LABEL' => $arBindingLabels,
		));
	}
	if (file_exists(dirname(__FILE__).'/ru/index.php'))
	{
		require(dirname(__FILE__).'/ru/index.php');
		COption::SetOptionString("intranet", "import_PersonIDSchemeName", $_MESS['import_PersonIDSchemeName']);
	}


	//admin must be linked to company structure
	if(!WIZARD_IS_RERUN)
	{
		$res_sect = CIBlockSection::GetList(array(), array("IBLOCK_ID"=>$structure_iblock_id, "DEPTH_LEVEL"=>1));
		if($res_sect_arr = $res_sect->Fetch())
		{
			$admin = new CUser();
			$admin->Update(1, array("UF_DEPARTMENT"=>array($res_sect_arr["ID"])));
		}
	}
}

COption::SetOptionString("intranet", "iblock_type_calendar", "events");
$rsIBlock = CIBlock::GetList(array(), array("CODE" => "calendar_employees", "TYPE" => "events", "SITE_ID" => WIZARD_SITE_ID));
if ($arIBlock = $rsIBlock->Fetch())
	COption::SetOptionString("intranet", "iblock_calendar",  $arIBlock["ID"], false, WIZARD_SITE_ID);

COption::SetOptionString('intranet', 'iblock_type_tasks', 'services', false, WIZARD_SITE_ID);
$rsIBlock = CIBlock::GetList(array(), array("CODE" => "intranet_tasks", "TYPE" => "services", "SITE_ID" => WIZARD_SITE_ID));
if ($arIBlock = $rsIBlock->Fetch())
	COption::SetOptionString("intranet", "iblock_tasks",  $arIBlock["ID"], false, WIZARD_SITE_ID);

COption::SetOptionString('intranet', 'path_user', WIZARD_SITE_DIR.'company/personal/user/#USER_ID#/', false, WIZARD_SITE_ID);

COption::SetOptionString('intranet', 'path_task_user', WIZARD_SITE_DIR.'company/personal/user/#USER_ID#/tasks/', false, WIZARD_SITE_ID);
COption::SetOptionString('intranet', 'path_task_user_entry', WIZARD_SITE_DIR.'company/personal/user/#USER_ID#/tasks/task/view/#TASK_ID#/', false, WIZARD_SITE_ID);
COption::SetOptionString('intranet', 'path_task_group', WIZARD_SITE_DIR.'workgroups/group/#GROUP_ID#/tasks/', false, WIZARD_SITE_ID);
COption::SetOptionString('intranet', 'path_task_group_entry', WIZARD_SITE_DIR.'workgroups/group/#GROUP_ID#/tasks/task/view/#TASK_ID#/', false, WIZARD_SITE_ID);

if(CModule::IncludeModule("tasks"))
{
	COption::SetOptionString("intranet", "use_tasks_2_0", "Y");
}

if(CModule::IncludeModule("dav"))
{
	CAgent::AddAgent("CDavGroupdavClientCalendar::DataSync();", "dav", "N", 60);
	COption::SetOptionString("dav", "agent_calendar_caldav", "Y");

	if (LANGUAGE_ID == "de")
		COption::SetOptionString("dav", "timezone", "Europe/Berlin");
	elseif (LANGUAGE_ID == "en")
		COption::SetOptionString("dav", "timezone", "America/New_York");
	else
		COption::SetOptionString("dav", "timezone", "Europe/Moscow");

	CDavExchangeCalendar::InitUserEntity();
	CDavGroupdavClientCalendar::InitUserEntity();
}
?>