<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if(!CModule::IncludeModule("iblock"))
	return;

$iblockXMLFile = WIZARD_SERVICE_RELATIVE_PATH."/xml/".LANGUAGE_ID."/departments.xml";
$iblockCode = "departments"; 
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
		"departments", 
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
	
	$arProperty = Array(
		'ENTITY_ID' => 'USER',
		'FIELD_NAME' => 'UF_DEPARTMENT',
		'USER_TYPE_ID' => 'iblock_section',
		'XML_ID' => '',
		'SORT' => 1,
		'MULTIPLE' => 'Y',
		'MANDATORY' => 'N',
		'SHOW_FILTER' => 'I',
		'SHOW_IN_LIST' => 'Y',
		'EDIT_IN_LIST' => 'Y',
		'IS_SEARCHABLE' => 'Y',
		'SETTINGS' => array(
			'DISPLAY' => 'LIST',
			'LIST_HEIGHT' => '8',
			'IBLOCK_ID' => $iblockID,
			'ACTIVE_FILTER' => 'Y'
		)
	);
	$dbRes = CUserTypeEntity::GetList(Array(), Array("ENTITY_ID" => $arProperty["ENTITY_ID"], "FIELD_NAME" => $arProperty["FIELD_NAME"]));
	if ($dbRes->Fetch())
		return;
	
	$arLabelNames = Array();
	$rsLanguage = CLanguage::GetList($by, $order, array());
	while($arLanguage = $rsLanguage->Fetch())
	{
		$languageID = $arLanguage["LID"];
		WizardServices::IncludeServiceLang("property_names.php", $languageID);
		$arLabelNames[$languageID] = GetMessage($arProperty["FIELD_NAME"]);
	}
	
	$arProperty["EDIT_FORM_LABEL"] = $arLabelNames;
	$arProperty["LIST_COLUMN_LABEL"] = $arLabelNames;
	$arProperty["LIST_FILTER_LABEL"] = $arLabelNames;
	
	$userType = new CUserTypeEntity();
	$success = (bool)$userType->Add($arProperty);
	
	//edit form customization
	WizardServices::SetUserOption("form", "form_section_".$iblockID, Array(
		"tabs"=>"edit1--#--".GetMessage("iblock_dep_dep")."--,--ID--#--  ID--,--DATE_CREATE--#--  ".GetMessage("iblock_dep_created")."--,--TIMESTAMP_X--#--  ".GetMessage("iblock_dep_changed")."--,--NAME--#--*".GetMessage("iblock_dep_name")."--,--IBLOCK_SECTION_ID--#--  ".GetMessage("iblock_dep_parent")."--,--UF_HEAD--#--  ".GetMessage("iblock_dep_chief")."--,--PICTURE--#--  ".GetMessage("iblock_dep_pict")."--,--DESCRIPTION--#--  ".GetMessage("iblock_dep_desc")."--;--edit2--#--".GetMessage("iblock_dep_addit")."--,--ACTIVE--#--  ".GetMessage("iblock_dep_act")."--,--SORT--#--  ".GetMessage("iblock_dep_sort")."--,--CODE--#--  ".GetMessage("iblock_dep_code")."--,--DETAIL_PICTURE--#--  ".GetMessage("iblock_dep_det_pict")."--,--edit2_csection1--#----".GetMessage("iblock_dep_userprop")."--,--USER_FIELDS_ADD--#--  ".GetMessage("iblock_dep_userprop_add")."--;--"
	), $common = true);
			
	//IBlock fields
		$iblock = new CIBlock;
		$arFields = Array(
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
?>