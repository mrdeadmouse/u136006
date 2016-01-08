<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if(!CModule::IncludeModule("iblock"))
	return;

$iblockXMLFile = WIZARD_SERVICE_RELATIVE_PATH."/xml/".LANGUAGE_ID."/group_files.xml";
$iblockCode = "group_files_".WIZARD_SITE_ID; 
$iblockType = "library";
	
$rsIBlock = CIBlock::GetList(array(), array("XML_ID" => $iblockCode, "TYPE" => $iblockType));
$iblockID = false; 
if ($arIBlock = $rsIBlock->Fetch())
{
	$iblockID = $arIBlock["ID"]; 
}
if($iblockID == false && WIZARD_SITE_ID == "s1")
{
	$rsIBlock = CIBlock::GetList(array(), array("CODE" => "group_files", "TYPE" => $iblockType));
	if ($arIBlock = $rsIBlock->Fetch())
	{
		$iblockID = $arIBlock["ID"]; 
	}
}
if($iblockID == false)
{
	$iblockID = WizardServices::ImportIBlockFromXML(
		$iblockXMLFile, 
		"group_files_temp", 
		$iblockType, 
		WIZARD_SITE_ID, 
		$permissions = Array(
			"1" => "X",
			"2" => "R",
			WIZARD_PORTAL_ADMINISTRATION_GROUP => "X",
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
	
	WizardServices::SetIBlockFormSettings($iblockID, Array ( 'tabs' => GetMessage("W_IB_GROUP_FILES_TAB1").$FILE_PROPERTY_ID.GetMessage("W_IB_GROUP_FILES_TAB2"), ));
	
	//IBlock fields
	$iblock = new CIBlock;
	$arFields = Array(
		"ACTIVE" => "Y",
		"BIZPROC"=> "Y",
		"WORKFLOW" => "N",
		"FIELDS" => array ( 'IBLOCK_SECTION' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), 'ACTIVE' => array ( 'IS_REQUIRED' => 'Y', 'DEFAULT_VALUE' => 'Y', ), 'ACTIVE_FROM' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), 'ACTIVE_TO' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), 'SORT' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), 'NAME' => array ( 'IS_REQUIRED' => 'Y', 'DEFAULT_VALUE' => '', ), 'PREVIEW_PICTURE' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => array ( 'FROM_DETAIL' => 'N', 'SCALE' => 'N', 'WIDTH' => '', 'HEIGHT' => '', 'IGNORE_ERRORS' => 'N', ), ), 'PREVIEW_TEXT_TYPE' => array ( 'IS_REQUIRED' => 'Y', 'DEFAULT_VALUE' => 'text', ), 'PREVIEW_TEXT' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), 'DETAIL_PICTURE' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => array ( 'SCALE' => 'N', 'WIDTH' => '', 'HEIGHT' => '', 'IGNORE_ERRORS' => 'N', ), ), 'DETAIL_TEXT_TYPE' => array ( 'IS_REQUIRED' => 'Y', 'DEFAULT_VALUE' => 'text', ), 'DETAIL_TEXT' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), 'XML_ID' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), 'CODE' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), 'TAGS' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), ),
		"CODE" => $iblockCode, 
		"XML_ID" => $iblockCode, 
	);
	
	$iblock->Update($iblockID, $arFields);


	$arFields = Array(
		"ENTITY_ID" => "IBLOCK_".$iblockID."_SECTION",
		"FIELD_NAME" => "UF_USE_BP",
		"USER_TYPE_ID" => "string",
		"MULTIPLE" => "N",
		"MANDATORY" => "N", 
		"SETTINGS" => array("DEFAULT_VALUE" => "Y"));
	$arFieldName = array();
	$rsLanguage = CLanguage::GetList($by, $order, array());
	while($arLanguage = $rsLanguage->Fetch()):
		if (LANGUAGE_ID == $arLanguage["LID"])
			$arFieldName[$arLanguage["LID"]] = GetMessage("W_IB_UF_USE_BP");
		else
		{
			$file = WIZARD_SERVICE_RELATIVE_PATH."/lang/".$arLanguage["LID"]."/group_files.php"; 
			$tmp_mess = __IncludeLang($file, true);
			$arFieldName[$arLanguage["LID"]] = $tmp_mess["W_IB_UF_USE_BP"]; 
		}
		if (empty($arFieldName[$arLanguage["LID"]]))
			$arFieldName[$arLanguage["LID"]] = "Use bizprocess in library";
	endwhile;
	$arFields["EDIT_FORM_LABEL"] = $arFieldName;
	$obUserField  = new CUserTypeEntity;
	$obUserField->Add($arFields);
	$GLOBALS["USER_FIELD_MANAGER"]->arFieldsCache = array();
	
	$db_res = CIBlockSection::GetList(array(), array("IBLOCK_ID" => $iblockID, "SECTION_ID" => 0)); 
	if ($db_res && $res = $db_res->Fetch())
	{
		$bs = new CIBlockSection(); 
		do
		{
			$arFields = Array(
				"IBLOCK_ID" => $iblockID,
				"UF_USE_BP" => "N");
			$GLOBALS["UF_USE_BP"] = $arFields["UF_USE_BP"];
			$GLOBALS["USER_FIELD_MANAGER"]->EditFormAddFields("IBLOCK_".$iblockID."_SECTION", $arFields);
			
			$bs->Update($res["ID"], $arFields);
		} while ($res = $db_res->Fetch());
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
?>