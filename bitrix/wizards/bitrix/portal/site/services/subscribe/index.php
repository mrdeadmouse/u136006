<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if(!CModule::IncludeModule("subscribe"))
	return;

if (!WIZARD_IS_RERUN)
{
	COption::SetOptionString("subscribe", "posting_charset", (LANGUAGE_ID == "ru"? "Windows-1251,": "")."ISO-8859-1,UTF-8");
	COption::SetOptionString("subscribe", "subscribe_section", WIZARD_SITE_DIR."services/");
	COption::SetOptionString("subscribe", "posting_use_editor", "Y");
	COption::SetOptionString("subscribe", "attach_images", "Y");

	$APPLICATION->SetGroupRight("subscribe", WIZARD_PORTAL_ADMINISTRATION_GROUP, "W");
}

//Copy template
CopyDirFiles(
	WIZARD_SERVICE_ABSOLUTE_PATH."/templates/".LANGUAGE_ID."/",
	$_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/php_interface/subscribe/templates/",
	$rewrite = false,
	$recursive = true
);

$officialIBlockID = "";
if (CModule::IncludeModule("iblock"))
{
	$dbIBlock = CIBlock::GetList(Array(), Array("CODE" => "official_news"));
	if ($arIBlock = $dbIBlock->Fetch())
		$officialIBlockID = $arIBlock["ID"];
}

CWizardUtil::ReplaceMacros(
	$_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/php_interface/subscribe/templates/official/template.php",
	Array(
		"IBLOCK_ID" => $officialIBlockID,
		"SITE_ID" => WIZARD_SITE_ID,
	)
);

$rsRubric = CRubric::GetList(Array(), Array("NAME" => GetMessage("SUBSCRIBE_OFFICIAL_INFORMATION"), "LID" => WIZARD_SITE_ID));
if(!$rsRubric->Fetch())
{
	$arFields = Array(
		"ACTIVE"	=> "Y",
		"NAME"		=> GetMessage("SUBSCRIBE_OFFICIAL_INFORMATION"),
		"SORT"		=> 100,
		"DESCRIPTION"	=> "",
		"LID"		=> WIZARD_SITE_ID,
		"AUTO"		=> "Y",
		"DAYS_OF_MONTH"	=> "",
		"DAYS_OF_WEEK"	=> "7",  //Sunday
		"TIMES_OF_DAY"	=> "05:00",
		"TEMPLATE"	=> substr(BX_PERSONAL_ROOT, 1)."/php_interface/subscribe/templates/official",
		"VISIBLE"	=> "Y",
		"FROM_FIELD"	=> COption::GetOptionString("main", "email_from", "admin@".$_SERVER["SERVER_NAME"]),
		"LAST_EXECUTED"	=> ConvertTimeStamp(false, "FULL"), // now
	);
	$obRubric = new CRubric;
	$ID = $obRubric->Add($arFields);
}

$rsRubric = CRubric::GetList(Array(), Array("NAME" => GetMessage("SUBSCRIBE_NEWS_LIFE"), "LID" => WIZARD_SITE_ID));
if(!$rsRubric->Fetch())
{
	$arFields = Array(
		"ACTIVE"	=> "Y",
		"NAME"		=> GetMessage("SUBSCRIBE_NEWS_LIFE"),
		"SORT"		=> 200,
		"DESCRIPTION"	=> "",
		"LID"		=> WIZARD_SITE_ID,
		"AUTO"		=> "N",
	);

	$obRubric = new CRubric;
	$ID = $obRubric->Add($arFields);
	if($ID)
	{
		$arFields = Array(
			"FROM_FIELD"	=> COption::GetOptionString("main", "email_from", "admin@".$_SERVER["SERVER_NAME"]),
			"TO_FIELD"	=> COption::GetOptionString("main", "email_from", "admin@".$_SERVER["SERVER_NAME"]),
			"EMAIL_FILTER"	=> "%%",
			"SUBJECT"	=> GetMessage("SUBSCRIBE_POSTING_SUBJECT"),
			"BODY_TYPE"	=> "html",
			"BODY"		=> GetMessage("SUBSCRIBE_POSTING_BODY"),
			"DIRECT_SEND"	=> "Y",
			"CHARSET"	=> LANG_CHARSET,
			"SUBSCR_FORMAT"	=> "text",
			"RUB_ID"	=> Array($ID),
			"STATUS"	=> "D", //Draft
		);
		$obPosting = new CPosting();
		$obPosting->Add($arFields);
	}
}
?>