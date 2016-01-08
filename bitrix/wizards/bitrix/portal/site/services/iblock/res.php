<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if(!CModule::IncludeModule("iblock"))
	return;

$iblockXMLFile = WIZARD_SERVICE_RELATIVE_PATH."/xml/".LANGUAGE_ID."/res.xml";
$iblockCode = "meeting_rooms_".WIZARD_SITE_ID; 
$iblockType = "events";

$rsIBlock = CIBlock::GetList(array(), array("XML_ID" => $iblockCode, "TYPE" => $iblockType));
$iblockID = false; 
if ($arIBlock = $rsIBlock->Fetch())
{
	$iblockID = $arIBlock["ID"]; 
}
if($iblockID == false && WIZARD_SITE_ID == "s1")
{
	$rsIBlock = CIBlock::GetList(array(), array("CODE" => "meeting_rooms", "TYPE" => $iblockType));
	if ($arIBlock = $rsIBlock->Fetch())
	{
		$iblockID = $arIBlock["ID"]; 
	}
}
if($iblockID == false)
{
	$iblockID = WizardServices::ImportIBlockFromXML(
		$iblockXMLFile,
		"meeting_rooms_temp",
		$iblockType,
		WIZARD_SITE_ID,
		$permissions = Array(
			"1" => "X",
			"2" => "R",
			WIZARD_PORTAL_ADMINISTRATION_GROUP => "X",
			WIZARD_PERSONNEL_DEPARTMENT_GROUP => "W",
			WIZARD_DIRECTION_GROUP => "W",
		)
	);
	
	if ($iblockID < 1)
		return;
		
	WizardServices::CreateSectionProperty($iblockID, "UF__CAL_COL", $arFieldColorName);
	WizardServices::CreateSectionProperty($iblockID, "UF__CAL_EXP", $arFieldExportName);
	
	$ibSection = new CIBlockSection;
	$arColor = Array("#DDBFEB", "#CEE669", "#98AEF6");
	$dbSection = CIBlockSection::GetList(Array(), Array("ACTIVE" => "Y", "IBLOCK_ID" => $iblockID));
	$i = 0;
	while ($arSection = $dbSection->Fetch())
	{
		$color = (isset($arColor[$i]) ? $arColor[$i] : $arColor[0]);
		$ibSection->Update($arSection["ID"], Array("ACTIVE" => "Y", "UF__CAL_COL" => $color, "UF__CAL_EXP" => "all"));
		$i++;
	}
	
	$arAF = array(
		"UF_FLOOR" => array(
			"NAME" => GetMessage("INAF_F_FLOOR"),
			"TYPE" => "integer",
		),
		"UF_PLACE" => array(
			"NAME" => GetMessage("INAF_F_PLACE"),
			"TYPE" => "integer",
		),
		"UF_PHONE" => array(
			"NAME" => GetMessage("INAF_F_PHONE"),
			"TYPE" => "string",
		),
	);
	
	$arUserFields = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("IBLOCK_".$iblockID."_SECTION", 0, LANGUAGE_ID);
	
	$arKeys = Array_Keys($arAF);
	foreach ($arKeys as $key)
	{
		if (!Array_Key_Exists($key, $arUserFields))
		{
			$arFields = Array(
				"ENTITY_ID" => "IBLOCK_".$iblockID."_SECTION",
				"FIELD_NAME" => $key,
				"USER_TYPE_ID" => $arAF[$key]["TYPE"],
			);
	
			$obUserField = new CUserTypeEntity;
			$obUserField->Add($arFields);
		}
	}
	
	
	$iblockSectionObject = new CIBlockSection;
	
	$dbrs = CIBlockSection::GetList(Array(), Array("IBLOCK_ID"=>$iblockID));
	
	$ars = $dbrs->Fetch();
	$iblockSectionObject->Update($ars["ID"], array("UF_FLOOR" => 1, "UF_PLACE" => 350, "UF_PHONE" => "12-34-56"));
	
	$ars = $dbrs->Fetch();
	$iblockSectionObject->Update($ars["ID"], array("UF_FLOOR" => 3, "UF_PLACE" => 10, "UF_PHONE" => "34-56-78"));
	
	$ars = $dbrs->Fetch();
	$iblockSectionObject->Update($ars["ID"], array("UF_FLOOR" => 2, "UF_PLACE" => 50, "UF_PHONE" => "56-78-90"));
	
	
	$iblockElementObject = new CIBlockElement;
	
	$dbrs = CIBlockElement::GetList(Array(), Array("IBLOCK_ID"=>$iblockID));
	
	$ars = $dbrs->Fetch();
	$date_from = MkTime(10, 0, 0, Date("n"), Date("j"), Date("Y"));
	$date_to = MkTime(14, 0, 0, Date("n"), Date("j"), Date("Y"));
	$iblockElementObject->Update($ars["ID"], array("ACTIVE_FROM" => ConvertTimeStamp($date_from, "FULL"), "ACTIVE_TO" => ConvertTimeStamp($date_to, "FULL")));
	
	$ars = $dbrs->Fetch();
	$date_from = MkTime(15, 0, 0, Date("n"), Date("j") + 1, Date("Y"));
	$date_to = MkTime(18, 0, 0, Date("n"), Date("j") + 1, Date("Y"));
	$iblockElementObject->Update($ars["ID"], array("ACTIVE_FROM" => ConvertTimeStamp($date_from, "FULL"), "ACTIVE_TO" => ConvertTimeStamp($date_to, "FULL")));
	
	$ars = $dbrs->Fetch();
	$date_from = MkTime(15, 0, 0, Date("n"), Date("j"), Date("Y"));
	$date_to = MkTime(17, 0, 0, Date("n"), Date("j"), Date("Y"));
	$iblockElementObject->Update($ars["ID"], array("ACTIVE_FROM" => ConvertTimeStamp($date_from, "FULL"), "ACTIVE_TO" => ConvertTimeStamp($date_to, "FULL")));
	
	$ars = $dbrs->Fetch();
	$date_from = MkTime(10, 0, 0, Date("n"), Date("j") + 1, Date("Y"));
	$date_to = MkTime(12, 0, 0, Date("n"), Date("j") + 1, Date("Y"));
	$iblockElementObject->Update($ars["ID"], array("ACTIVE_FROM" => ConvertTimeStamp($date_from, "FULL"), "ACTIVE_TO" => ConvertTimeStamp($date_to, "FULL")));

	//IBlock fields
	$iblock = new CIBlock;
	$arFields = Array(
		"CODE" => $iblockCode, 
		"XML_ID" => $iblockCode,
	);
	
	$iblock->Update($iblockID, $arFields);
	
	if (!COption::GetOptionString("calendar", "rm_iblock_type") && !COption::GetOptionString("calendar", "rm_iblock_id"))
	{
		COption::SetOptionString("calendar", "rm_iblock_type", $iblockType);
		COption::SetOptionString("calendar", "rm_iblock_id", $iblockID);
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


CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/services/index.php", Array("CALENDAR_RES_IBLOCK_ID" => $iblockID));
CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/services/res_c.php", Array("CALENDAR_RES_IBLOCK_ID" => $iblockID));
CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/company/personal.php", Array("CALENDAR_RES_IBLOCK_ID" => $iblockID));
CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/workgroups/index.php", Array("CALENDAR_RES_IBLOCK_ID" => $iblockID));
?>
