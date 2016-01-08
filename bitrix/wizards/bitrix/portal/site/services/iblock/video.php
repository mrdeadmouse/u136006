<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if(!CModule::IncludeModule("iblock"))
	return;

$iblockXMLFile = WIZARD_SERVICE_RELATIVE_PATH."/xml/".LANGUAGE_ID."/video.xml";
$iblockCode = "video_".WIZARD_SITE_ID; 
$iblockType = "services";

$rsIBlock = CIBlock::GetList(array(), array("XML_ID" => $iblockCode, "TYPE" => $iblockType));
$iblockID = false; 
if ($arIBlock = $rsIBlock->Fetch())
{
	$iblockID = $arIBlock["ID"]; 
}
if($iblockID == false && WIZARD_SITE_ID == "s1")
{
	$rsIBlock = CIBlock::GetList(array(), array("CODE" => "video", "TYPE" => $iblockType));
	if ($arIBlock = $rsIBlock->Fetch())
	{
		$iblockID = $arIBlock["ID"]; 
	}
}
if($iblockID == false)
{
	$iblockID = WizardServices::ImportIBlockFromXML(
		$iblockXMLFile,
		"video_temp",
		$iblockType,
		WIZARD_SITE_ID,
		$permissions = Array(
			"1" => "X",
			"2" => "R",
			WIZARD_PORTAL_ADMINISTRATION_GROUP => "X",
			WIZARD_PERSONNEL_DEPARTMENT_GROUP => "W",
		)
	);
	
	if ($iblockID < 1)
		return;
	
	$arProperties = Array("FILE", "DURATION");
	foreach ($arProperties as $propertyName)
	{
		${$propertyName."_PROPERTY_ID"} = 0;
		$properties = CIBlockProperty::GetList(Array(), Array("ACTIVE"=>"Y", "IBLOCK_ID" => $iblockID, "CODE" => $propertyName));
		if ($arProperty = $properties->Fetch())
			${$propertyName."_PROPERTY_ID"} = $arProperty["ID"];
	}
	
	$aFormOptions = array("tabs"=>"edit1--#--".GetMessage("VIDEO_FORM_1")."--,--NAME--#--*".GetMessage("VIDEO_FORM_2")."--,--PROPERTY_".$FILE_PROPERTY_ID."--#--".GetMessage("VIDEO_FORM_3")."--,--PROPERTY_".$DURATION_PROPERTY_ID."--#--".GetMessage("VIDEO_FORM_4")."--,--DETAIL_PICTURE--#--".GetMessage("VIDEO_FORM_5")."--,--PREVIEW_TEXT--#--".GetMessage("VIDEO_FORM_6")."--;--edit3--#--".GetMessage("VIDEO_FORM_7")."--,--SORT--#--".GetMessage("VIDEO_FORM_8")."--,--SECTIONS--#--".GetMessage("VIDEO_FORM_9")."--;--");
	WizardServices::SetIBlockFormSettings($iblockID, $aFormOptions);
	
	//IBlock fields
	$iblock = new CIBlock;
	$arFields = Array(
		"ACTIVE" => "Y",
		"FIELDS" => Array ( 'IBLOCK_SECTION' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), 'ACTIVE' => array ( 'IS_REQUIRED' => 'Y', 'DEFAULT_VALUE' => 'Y', ), 'ACTIVE_FROM' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), 'ACTIVE_TO' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), 'SORT' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), 'NAME' => array ( 'IS_REQUIRED' => 'Y', 'DEFAULT_VALUE' => '', ), 'PREVIEW_PICTURE' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => array ( 'FROM_DETAIL' => 'Y', 'SCALE' => 'Y', 'WIDTH' => 64, 'HEIGHT' => 48, 'IGNORE_ERRORS' => 'Y', ), ), 'PREVIEW_TEXT_TYPE' => array ( 'IS_REQUIRED' => 'Y', 'DEFAULT_VALUE' => 'text', ), 'PREVIEW_TEXT' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), 'DETAIL_PICTURE' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => array ( 'SCALE' => 'N', 'WIDTH' => '', 'HEIGHT' => '', 'IGNORE_ERRORS' => 'N', ), ), 'DETAIL_TEXT_TYPE' => array ( 'IS_REQUIRED' => 'Y', 'DEFAULT_VALUE' => 'text', ), 'DETAIL_TEXT' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), 'XML_ID' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), 'CODE' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), 'TAGS' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), ),
		"CODE" => $iblockCode, 
		"XML_ID" => $iblockCode,
	);
	
	$iblock->Update($iblockID, $arFields);	
	
	$arSectionCode = Array("index_page", "company_page");
	foreach ($arSectionCode as $sectionCode)
	{
		${$sectionCode."_section_id"} = 0;
		${$sectionCode."_element_id"} = 0;
	
		$dbSection = CIBlockSection::GetList(Array("SORT" => "ASC"), Array("ACTIVE" => "Y", "IBLOCK_ID" => $iblockID, "CODE" => $sectionCode));
		if ($arSection = $dbSection->Fetch())
		{
			${$sectionCode."_section_id"} = $arSection["ID"];
			$dbElement = CIBlockElement::GetList(Array("SORT" => "ASC"), Array("SECTION_ID" => $arSection["ID"]));
			if ($arElement = $dbElement->Fetch())
				${$sectionCode."_element_id"} = $arElement["ID"];
		}
	}
	
	CWizardUtil::ReplaceMacros(
		WIZARD_SITE_PATH."/about/media.php",
		Array(
			"VIDEO_IBLOCK_ID" => $iblockID,
			"VIDEO_SECTION_ID" => $company_page_section_id,
			"VIDEO_ELEMENT_ID" => $company_page_element_id,
			"VIDEO_PATH_TO_FILE_ID" => $FILE_PROPERTY_ID,
			"VIDEO_DURATION_ID" => $DURATION_PROPERTY_ID,
		)
	);
	
	CWizardUtil::ReplaceMacros(
		WIZARD_SITE_PATH."/_index.php",
		Array(
			"VIDEO_IBLOCK_ID" => $iblockID,
			"VIDEO_SECTION_ID" => $index_page_section_id,
			"VIDEO_ELEMENT_ID" => $index_page_element_id,
			"VIDEO_PATH_TO_FILE_ID" => $FILE_PROPERTY_ID,
			"VIDEO_DURATION_ID" => $DURATION_PROPERTY_ID,
		)
	);
	
	
	CWizardUtil::ReplaceMacros(
		WIZARD_SITE_PATH."/desktop.php",
		Array(
			"VIDEO_IBLOCK_ID" => $iblockID,
			"VIDEO_SECTION_ID" => $index_page_section_id,
			"VIDEO_ELEMENT_ID" => $index_page_element_id,
			"VIDEO_PATH_TO_FILE_ID" => $FILE_PROPERTY_ID,
			"VIDEO_DURATION_ID" => $DURATION_PROPERTY_ID,
		)
	);
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
?>
