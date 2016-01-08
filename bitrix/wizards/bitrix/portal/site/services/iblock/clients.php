<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if(!CModule::IncludeModule("iblock"))
	return;

$iblockXMLFile = WIZARD_SERVICE_RELATIVE_PATH."/xml/".LANGUAGE_ID."/clients.xml";
$iblockCode = "clients_".WIZARD_SITE_ID; 
$iblockType = "lists";
	
$rsIBlock = CIBlock::GetList(array(), array("XML_ID" => $iblockCode, "TYPE" => $iblockType));
$iblockID = false; 
if ($arIBlock = $rsIBlock->Fetch())
{
	$iblockID = $arIBlock["ID"]; 
}
if($iblockID == false && WIZARD_SITE_ID == "s1")
{
	$rsIBlock = CIBlock::GetList(array(), array("CODE" => "clients", "TYPE" => $iblockType));
	if ($arIBlock = $rsIBlock->Fetch())
	{
		$iblockID = $arIBlock["ID"]; 
	}
}
if($iblockID == false)
{
	$iblockID = WizardServices::ImportIBlockFromXML(
		$iblockXMLFile,
		"clients_temp",
		$iblockType,
		WIZARD_SITE_ID,
		$permissions = Array(
			"1" => "X",
			WIZARD_PORTAL_ADMINISTRATION_GROUP => "X",
		)
	);
	
	if ($iblockID < 1)
		return;
	
	WizardServices::SetIBlockFormSettings($iblockID, Array ( 'tabs' => GetMessage("W_IB_CLIENTS_TAB1", array(
		"PROPERTY_PERSON" => "PROPERTY_".$PERSON_PROPERTY_ID,
		"PROPERTY_PHONE" => "PROPERTY_".$PHONE_PROPERTY_ID,
	))));
		
	$arProperties = Array("PERSON", "PHONE");
	foreach ($arProperties as $propertyName)
	{
		${$propertyName."_PROPERTY_ID"} = 0;
		$properties = CIBlockProperty::GetList(Array(), Array("ACTIVE"=>"Y", "IBLOCK_ID" => $iblockID, "CODE" => $propertyName));
		if ($arProperty = $properties->Fetch())
			${$propertyName."_PROPERTY_ID"} = $arProperty["ID"];
	}
	
	$obIBlock = new CIBlock;
	$obIBlock->Update($iblockID, array("BIZPROC"=>"Y", "CODE" => $iblockCode, "XML_ID" => $iblockCode));
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
