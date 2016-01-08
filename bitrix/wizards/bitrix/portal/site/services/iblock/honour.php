<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if(!CModule::IncludeModule("iblock"))
	return;

$iblockXMLFile = WIZARD_SERVICE_RELATIVE_PATH."/xml/".LANGUAGE_ID."/honour.xml";
$iblockCode = "honour"; 
$iblockType = "structure";

$rsIBlock = CIBlock::GetList(array(), array("CODE" => $iblockCode, "TYPE" => $iblockType));
$iblockID = false; 
if ($arIBlock = $rsIBlock->Fetch())
{
	$iblockID = $arIBlock["ID"]; 
}
if($iblockID == false)
{
	$iblockID = WizardServices::ImportIBlockFromXML(
		$iblockXMLFile,
		"honour",
		$iblockType,
		WIZARD_SITE_ID,
		$permissions = Array(
			"1" => "X",
			"2" => "R",
			WIZARD_PORTAL_ADMINISTRATION_GROUP => "X",
			WIZARD_PERSONNEL_DEPARTMENT_GROUP => "X",
		)
	);
	
	if ($iblockID < 1)
		return;
	
	$userPropertyID = 0;
	$properties = CIBlockProperty::GetList(Array(), Array("ACTIVE"=>"Y", "IBLOCK_ID" => $iblockID, "CODE" => "USER"));
	if ($arProperty = $properties->Fetch())
		$userPropertyID = $arProperty["ID"];
	
	$aFormOptions = array("tabs"=>"edit1--#--".GetMessage("HONOR_FORM_1")."--,--PROPERTY_".$userPropertyID."--#--".GetMessage("HONOR_FORM_2")."--,--NAME--#--*".GetMessage("HONOR_FORM_3")."--,--edit1_csection1--#----".GetMessage("HONOR_FORM_4")."--,--ACTIVE_FROM--#--".GetMessage("HONOR_FORM_5")."--,--ACTIVE_TO--#--".GetMessage("HONOR_FORM_6")."--,--PREVIEW_TEXT--#--".GetMessage("HONOR_FORM_7")."--;--");
	WizardServices::SetIBlockFormSettings($iblockID, $aFormOptions);

	//IBlock fields
	$iblock = new CIBlock;
	$arFields = Array(
		"ACTIVE" => "Y",
		"FIELDS" => array ( 'IBLOCK_SECTION' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), 'ACTIVE' => array ( 'IS_REQUIRED' => 'Y', 'DEFAULT_VALUE' => 'Y', ), 'ACTIVE_FROM' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), 'ACTIVE_TO' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), 'SORT' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), 'NAME' => array ( 'IS_REQUIRED' => 'Y', 'DEFAULT_VALUE' => '', ), 'PREVIEW_PICTURE' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => array ( 'FROM_DETAIL' => 'N', 'SCALE' => 'N', 'WIDTH' => '', 'HEIGHT' => '', 'IGNORE_ERRORS' => 'N', ), ), 'PREVIEW_TEXT_TYPE' => array ( 'IS_REQUIRED' => 'Y', 'DEFAULT_VALUE' => 'text', ), 'PREVIEW_TEXT' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), 'DETAIL_PICTURE' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => array ( 'SCALE' => 'N', 'WIDTH' => '', 'HEIGHT' => '', 'IGNORE_ERRORS' => 'N', ), ), 'DETAIL_TEXT_TYPE' => array ( 'IS_REQUIRED' => 'Y', 'DEFAULT_VALUE' => 'text', ), 'DETAIL_TEXT' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), 'XML_ID' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), 'CODE' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), 'TAGS' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), ),
		"CODE" => $iblockCode, 
		"XML_ID" => $iblockCode,
	);
	
	$iblock->Update($iblockID, $arFields);	
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


CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/_index.php", array("HONOUR_IBLOCK_ID" => $iblockID));
CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/desktop.php", array("HONOUR_IBLOCK_ID" => $iblockID));
CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/company/leaders.php", array("HONOUR_IBLOCK_ID" => $iblockID));
?>
