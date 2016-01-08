<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if(!CModule::IncludeModule("iblock"))
	return;

$iblockXMLFile = WIZARD_SERVICE_RELATIVE_PATH."/xml/".LANGUAGE_ID."/directors_files.xml";
$iblockCode = "directors_files_".WIZARD_SITE_ID; 
$iblockType = "library";
	
$rsIBlock = CIBlock::GetList(array(), array("XML_ID" => $iblockCode, "TYPE" => $iblockType));
$iblockID = false; 
if ($arIBlock = $rsIBlock->Fetch())
{
	$iblockID = $arIBlock["ID"]; 
}
if($iblockID == false && WIZARD_SITE_ID == "s1")
{
	$rsIBlock = CIBlock::GetList(array(), array("CODE" => "directors_files", "TYPE" => $iblockType));
	if ($arIBlock = $rsIBlock->Fetch())
	{
		$iblockID = $arIBlock["ID"]; 
	}
}
if($iblockID == false)
{
	$iblockID = WizardServices::ImportIBlockFromXML(
		$iblockXMLFile,
		"directors_files_temp",
		$iblockType,
		WIZARD_SITE_ID,
		$permissions = Array(
			"1" => "X",
			"2" => "D",
			WIZARD_PORTAL_ADMINISTRATION_GROUP => "X",
			WIZARD_DIRECTION_GROUP => "W",
		)
	);
	
	if ($iblockID < 1)
		return;
	
	$arProperties = Array("FILE");
	foreach ($arProperties as $propertyName)
	{
		${$propertyName."_PROPERTY_ID"} = 0;
		$properties = CIBlockProperty::GetList(Array(), Array("ACTIVE"=>"Y", "IBLOCK_ID" => $iblockID, "CODE" => $propertyName));
		if ($arProperty = $properties->Fetch())
			${$propertyName."_PROPERTY_ID"} = $arProperty["ID"];
	}
	
	WizardServices::SetIBlockFormSettings($iblockID, Array ( 'tabs' => GetMessage("W_IB_DIRECTORS_FILES_TAB1").$FILE_PROPERTY_ID.GetMessage("W_IB_DIRECTORS_FILES_TAB2"), ));
	
	
	//IBlock fields
	$iblock = new CIBlock;
	$arFields = Array(
		"ACTIVE" => "Y",
		"BIZPROC"=> "N",
		"WORKFLOW" => "N",
		"FIELDS" => array ( 'IBLOCK_SECTION' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), 'ACTIVE' => array ( 'IS_REQUIRED' => 'Y', 'DEFAULT_VALUE' => 'Y', ), 'ACTIVE_FROM' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), 'ACTIVE_TO' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), 'SORT' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), 'NAME' => array ( 'IS_REQUIRED' => 'Y', 'DEFAULT_VALUE' => '', ), 'PREVIEW_PICTURE' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => array ( 'FROM_DETAIL' => 'N', 'SCALE' => 'N', 'WIDTH' => '', 'HEIGHT' => '', 'IGNORE_ERRORS' => 'N', ), ), 'PREVIEW_TEXT_TYPE' => array ( 'IS_REQUIRED' => 'Y', 'DEFAULT_VALUE' => 'text', ), 'PREVIEW_TEXT' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), 'DETAIL_PICTURE' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => array ( 'SCALE' => 'N', 'WIDTH' => '', 'HEIGHT' => '', 'IGNORE_ERRORS' => 'N', ), ), 'DETAIL_TEXT_TYPE' => array ( 'IS_REQUIRED' => 'Y', 'DEFAULT_VALUE' => 'text', ), 'DETAIL_TEXT' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), 'XML_ID' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), 'CODE' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), 'TAGS' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), ),
		"CODE" => $iblockCode, 
		"XML_ID" => $iblockCode,
		"RIGHTS_MODE" => "E",
		"GROUP_ID" => CIBlock::GetGroupPermissions($iblockID),
	);
	
	$iblock->Update($iblockID, $arFields);
	
	$element = new CIBlockElement;
	$dbElement = CIBlockElement::GetList(Array(), Array("IBLOCK_ID" => $iblockID));
	while($arElement = $dbElement->Fetch())
		$element->Update($arElement["ID"], Array("MODIFIED_BY" => 1, "CREATED_BY" => 1));
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

/*
if(CModule::IncludeModule("bizproc"))
	CBPDocument::AddDefaultWorkflowTemplates(array("webdav", "CIBlockDocumentWebdav", "iblock_".$iblockID));
*/

CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/docs/manage/index.php", Array("DIRECTORS_FILES_IBLOCK_ID" => $iblockID));
CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/docs/index.php", Array("DIRECTORS_FILES_IBLOCK_ID" => $iblockID));
CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/m/docs/index.php", Array("DIRECTORS_FILES_IBLOCK_ID" => $iblockID));
?>
