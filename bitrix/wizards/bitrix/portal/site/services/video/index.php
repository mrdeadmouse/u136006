<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if(!CModule::IncludeModule("iblock"))
	return;

if(!CModule::IncludeModule("video"))
	return;

//if(!WIZARD_IS_RERUN)
//{
	WizardServices::CopyFile(WIZARD_SERVICE_RELATIVE_PATH."/public/".LANGUAGE_ID."/video", WIZARD_SITE_DIR."services/video");
	$arMenuItem = Array(
		GetMessage("VMENUIT"),
		WIZARD_SITE_DIR."services/video/",
		Array(),
		Array(),
		""
	);

	WizardServices::AddMenuItem(WIZARD_SITE_DIR."services/.left.menu.php", $arMenuItem, WIZARD_SITE_ID, 11);

//}

$iblockCode = "video-meeting_".WIZARD_SITE_ID;
$iblockType = "events";

$dbIblockType = CIBlockType::GetList(Array(), Array("=ID" => $iblockType));
if(!$dbIblockType -> Fetch())
{
	$obBlocktype = new CIBlockType;
	$arFields = Array(
			"ID" => $iblockType,
			"SORT" => 500,
			"IN_RSS" => "N",
			"SECTIONS" => "Y"
		);
		
	$arFields["LANG"][LANGUAGE_ID] = Array("NAME" => GetMessage("VI_IBLOCK_NAME"));

	$res = $obBlocktype->Add($arFields);
}

$rsIBlock = CIBlock::GetList(array(), array("XML_ID" => $iblockCode, "TYPE" => $iblockType));
$iblockID = false; 
if ($arIBlock = $rsIBlock->Fetch())
{
	$iblockID = $arIBlock["ID"]; 
}
if($iblockID == false)
{
	$rsIBlock = CIBlock::GetList(array(), array("CODE" => "video-meeting", "TYPE" => $iblockType));
	if ($arIBlock = $rsIBlock->Fetch())
	{
		$iblockID = $arIBlock["ID"]; 
	}
}
if($iblockID == false)
{
	$iblockID = WizardServices::ImportIBlockFromXML(
		WIZARD_SERVICE_RELATIVE_PATH."/xml/lang_".LANGUAGE_ID."/res_video.xml",
		'video-meeting',
		$iblockType,
		WIZARD_SITE_ID,
		$permissions = Array(
			"1" => "X",
			"2" => "R",
			WIZARD_PORTAL_ADMINISTRATION_GROUP => "X",
		)
	);
	
	$iblock = new CIBlock;
	$arFields = Array(
		"CODE" => $iblockCode, 
		"XML_ID" => $iblockCode,
	);
	
	$iblock->Update($iblockID, $arFields);
	
	if (!COption::GetOptionString("calendar", "vr_iblock_id"))
	{
		COption::SetOptionString("calendar", "vr_iblock_id", $iblockID);
	}
}
else
{
	$arSites = array(); 
	$db_res = CIBlock::GetSite($iblockID);
	while ($res = $db_res->Fetch())
		$arSites[] = $res["LID"]; 
	if (!in_array(WIZARD_SITE_ID, $arSites))
	{
		$arSites[] = WIZARD_SITE_ID; 
		$iblock = new CIBlock;
		$iblock->Update($iblockID, array("LID" => $arSites));
	}
}	

CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/services/video/index.php", Array("CALENDAR_RES_VIDEO_IBLOCK_ID" => $iblockID));
CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/services/video/detail.php", Array("CALENDAR_RES_VIDEO_IBLOCK_ID" => $iblockID));
CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/company/personal.php", Array("CALENDAR_RES_VIDEO_IBLOCK_ID" => $iblockID));
CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/index_b24.php", Array("CALENDAR_RES_VIDEO_IBLOCK_ID" => $iblockID));
CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/workgroups/index.php", Array("CALENDAR_RES_VIDEO_IBLOCK_ID" => $iblockID));
?>