<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if(file_exists(WIZARD_ABSOLUTE_PATH."/site/public/".LANGUAGE_ID."/saas_tmp"))
{
	CopyDirFiles(
		WIZARD_ABSOLUTE_PATH."/site/public/".LANGUAGE_ID."/saas_tmp",
		WIZARD_SITE_PATH,
		$rewrite = false,
		$recursive = true,
		$delete_after_copy = true,
		$exclude = "bitrix"
	);

	unlink(WIZARD_ABSOLUTE_PATH."/site/public/".LANGUAGE_ID."/saas_tmp");
}
if(!(WIZARD_SITE_ID == 's1' && !WIZARD_NEW_2011 && WIZARD_FIRST_INSTAL !== "Y") || WIZARD_B24_TO_CP)
{
	if (WIZARD_INSTALL_DEMO_DATA || WIZARD_FIRST_INSTAL !== "Y" || WIZARD_B24_TO_CP)
	{
		CopyDirFiles(
			WIZARD_ABSOLUTE_PATH."/site/public/",
			WIZARD_SITE_PATH,
			$rewrite = true,
			$recursive = true,
			$delete_after_copy = false,
			$exclude = "bitrix"
		);
		if (WIZARD_B24_TO_CP)
		{
			if (file_exists(WIZARD_SITE_PATH."settings/"))
				DeleteDirFilesEx(WIZARD_SITE_DIR."settings/");
			if (file_exists(WIZARD_SITE_PATH."marketplace/"))
				DeleteDirFilesEx(WIZARD_SITE_DIR."marketplace/");
			if (file_exists(WIZARD_SITE_PATH."bitrix/templates/login"))
				DeleteDirFilesEx(WIZARD_SITE_DIR."bitrix/templates/login");
			if (file_exists(WIZARD_SITE_PATH."company/meeting"))
				DeleteDirFilesEx(WIZARD_SITE_DIR."company/meeting");	
		}
	}
}

if (WIZARD_SITE_ID == 's1' && !WIZARD_NEW_2011)
	CopyDirFiles(WIZARD_ABSOLUTE_PATH."/site/public/.department.menu_ext.php", WIZARD_SITE_PATH."/.department.menu_ext.php", false);

$dateTimeFormat = (LANGUAGE_ID == "en") ? "F j, Y h:i a" : ((LANGUAGE_ID == "de") ? "j. F Y H:i:s" : "d.m.Y H:i:s");
$dateFormat = (LANGUAGE_ID == "en") ? "F j, Y" : ((LANGUAGE_ID == "de") ? "j. F Y" : "d.m.Y");
$dateFormatNoYear = (LANGUAGE_ID == "en") ? "F j" : ((LANGUAGE_ID == "de") ? "j. F" : "d.m");
CWizardUtil::ReplaceMacrosRecursive(WIZARD_SITE_PATH, Array("SITE_DIR" => WIZARD_SITE_DIR, "DATE_TIME_FORMAT" => $dateTimeFormat, "DATE_FORMAT" => $dateFormat, "DATE_FORMAT_NO_YEAR" => $dateFormatNoYear));
CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/desktop.php", Array("SITE_ID" => WIZARD_SITE_ID));
CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/_index.php", Array("SITE_ID" => WIZARD_SITE_ID));

if (WIZARD_INSTALL_DEMO_DATA || WIZARD_B24_TO_CP)
{
	$arUrlRewrite = array();
	if (file_exists(WIZARD_SITE_ROOT_PATH."/urlrewrite.php"))
	{
		include(WIZARD_SITE_ROOT_PATH."/urlrewrite.php");
	}

	$arNewUrlRewrite = array(
		array(
			"CONDITION"	=>	"#^".WIZARD_SITE_DIR."company/gallery/#",
			"RULE"	=>	"",
			"ID"	=>	"bitrix:photogallery_user",
			"PATH"	=>	WIZARD_SITE_DIR."company/gallery/index.php",
		),
		array(
			"CONDITION"	=>	"#^".WIZARD_SITE_DIR."company/personal/#",
			"RULE"	=>	"",
			"ID"	=>	"bitrix:socialnetwork_user",
			"PATH"	=>	WIZARD_SITE_DIR."company/personal.php",
		),
		/*array(
			"CONDITION"	=>	"#^".WIZARD_SITE_DIR."community/forum/#",
			"RULE"	=>	"",
			"ID"	=>	"bitrix:forum",
			"PATH"	=>	WIZARD_SITE_DIR."community/forum.php",
		),*/
		array(
			"CONDITION"	=>	"#^".WIZARD_SITE_DIR."about/gallery/#",
			"RULE"	=>	"",
			"ID"	=>	"bitrix:photogallery",
			"PATH"	=>	WIZARD_SITE_DIR."about/gallery/index.php",
		),
		array(
			"CONDITION"	=>	"#^".WIZARD_SITE_DIR."workgroups/#",
			"RULE"	=>	"",
			"ID"	=>	"bitrix:socialnetwork_group",
			"PATH"	=>	WIZARD_SITE_DIR."workgroups/index.php",
		),
		array(
			"CONDITION"	=>	"#^".WIZARD_SITE_DIR."services/lists/#",
			"RULE"	=>	"",
			"ID"	=>	"bitrix:lists",
			"PATH"	=>	WIZARD_SITE_DIR."services/lists/index.php",
		),
		array(
			"CONDITION"	=>	"#^".WIZARD_SITE_DIR."services/faq/#",
			"RULE"	=>	"",
			"ID"	=>	"bitrix:support.faq",
			"PATH"	=>	WIZARD_SITE_DIR."services/faq/index.php",
		),
		array(
			"CONDITION"	=>	"#^".WIZARD_SITE_DIR."services/bp/#",
			"RULE"	=>	"",
			"ID"	=>	"bitrix:bizproc.wizards",
			"PATH"	=>	WIZARD_SITE_DIR."services/bp/index.php",
		),
		array(
			"CONDITION" => "#^".WIZARD_SITE_DIR."services/idea/#",
			"RULE" => "",
			"ID" => "bitrix:idea",
			"PATH" => WIZARD_SITE_DIR."services/idea/index.php"
		),
		array(
			"CONDITION" => "#^".WIZARD_SITE_DIR."tasks/getfile/(\\d+)/(\\d+)/([^/]+)#",
			"RULE" => "taskid=$1&fileid=$2&filename=$3",
			"ID" => "bitrix:tasks_tools_getfile",
			"PATH" => WIZARD_SITE_DIR."tasks/getfile.php",
		),
		array(
			"CONDITION" => "#^".WIZARD_SITE_DIR."docs/pub/#",
			"RULE" => "",
			"ID" => "bitrix:disk.external.link",
			"PATH" => WIZARD_SITE_DIR."docs/pub/extlinks.php"
		),

		array(
			"CONDITION"	=>	"#^".WIZARD_SITE_DIR."/docs/all#",
			"RULE"	=>	"",
			"ID"	=>	"bitrix:disk.aggregator",
			"PATH"	=>	WIZARD_SITE_DIR."docs/index.php",
		),
		array(
			"CONDITION" => "#^".WIZARD_SITE_DIR."docs/sale/#",
			"RULE" => "",
			"ID" => "bitrix:disk.common",
			"PATH" => WIZARD_SITE_DIR."docs/sale/index.php",
		),
		array(
			"CONDITION" => "#^".WIZARD_SITE_DIR."docs/shared#",
			"RULE" => "",
			"ID" => "bitrix:disk.common",
			"PATH" => WIZARD_SITE_DIR."docs/shared/index.php",
		),
		array(
			"CONDITION" => "#^".WIZARD_SITE_DIR."docs/manage/#",
			"RULE" => "",
			"ID" => "bitrix:disk.common",
			"PATH" => WIZARD_SITE_DIR."docs/manage/index.php",
		),
		array(
			'CONDITION' => '#^'.WIZARD_SITE_DIR.'services/processes/#',
			'RULE' => '',
			'ID' => 'bitrix:lists',
			'PATH' => WIZARD_SITE_DIR.'/services/processes/index.php'
		),
	);
	foreach ($arNewUrlRewrite as $arUrl)
	{
		if (!in_array($arUrl, $arUrlRewrite))
		{
			CUrlRewriter::Add($arUrl);
		}
	}
}
?>