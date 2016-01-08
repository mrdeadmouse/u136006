<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if(!CModule::IncludeModule("iblock"))
	return;

$iblockXMLFile = WIZARD_SERVICE_RELATIVE_PATH."/xml/".LANGUAGE_ID."/official_news.xml";
$iblockCode = "official_news_".WIZARD_SITE_ID; 
$iblockType = "news";

$rsIBlock = CIBlock::GetList(array(), array("XML_ID" => $iblockCode, "TYPE" => $iblockType));
$iblockID = false; 
if ($arIBlock = $rsIBlock->Fetch())
{
	$iblockID = $arIBlock["ID"]; 
}
if($iblockID == false && WIZARD_SITE_ID == "s1")
{
	$rsIBlock = CIBlock::GetList(array(), array("CODE" => "official_news", "TYPE" => $iblockType));
	if ($arIBlock = $rsIBlock->Fetch())
	{
		$iblockID = $arIBlock["ID"]; 
	}
}
if($iblockID == false)
{
	$iblockID = WizardServices::ImportIBlockFromXML(
		$iblockXMLFile,
		"official_news_temp",
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
	
	$typePropertyID = 0;
	$properties = CIBlockProperty::GetList(Array(), Array("ACTIVE"=>"Y", "IBLOCK_ID" => $iblockID, "CODE" => "DOC_TYPE"));
	if ($arProperty = $properties->Fetch())
		$typePropertyID = $arProperty["ID"];
	
	WizardServices::SetIBlockFormSettings($iblockID, Array ( 'tabs' => GetMessage("W_IB_OFFICIAL_NEWS_TAB1").$typePropertyID.GetMessage("W_IB_OFFICIAL_NEWS_TAB2"), ));
	
	//IBlock fields
	$iblock = new CIBlock;
	$arFields = Array(
		"ACTIVE" => "Y",
		"FIELDS" => array(
			'LOG_SECTION_ADD'=>array('IS_REQUIRED' => "Y"),
			'LOG_SECTION_EDIT'=>array('IS_REQUIRED' => "Y"),
			'LOG_SECTION_DELETE'=>array('IS_REQUIRED' => "Y"),
			'LOG_ELEMENT_ADD'=>array('IS_REQUIRED' => "Y"),
			'LOG_ELEMENT_EDIT'=>array('IS_REQUIRED' => "Y"),
			'LOG_ELEMENT_DELETE'=>array('IS_REQUIRED' => "Y"),
			'IBLOCK_SECTION' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ),
			'ACTIVE' => array ( 'IS_REQUIRED' => 'Y', 'DEFAULT_VALUE' => 'Y', ),
			'ACTIVE_FROM' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '=today', ),
			'ACTIVE_TO' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ),
			'SORT' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ),
			'NAME' => array ( 'IS_REQUIRED' => 'Y', 'DEFAULT_VALUE' => '', ),
			'PREVIEW_PICTURE' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => array (
				'FROM_DETAIL' => 'N', 'SCALE' => 'N', 'WIDTH' => 50, 'HEIGHT' => 50, 'IGNORE_ERRORS' => 'N', ),
			),
			'PREVIEW_TEXT_TYPE' => array ( 'IS_REQUIRED' => 'Y', 'DEFAULT_VALUE' => 'text', ),
			'PREVIEW_TEXT' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ),
			'DETAIL_PICTURE' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => array (
				'SCALE' => 'Y', 'WIDTH' => 300, 'HEIGHT' => 300, 'IGNORE_ERRORS' => 'Y', ),
			),
			'DETAIL_TEXT_TYPE' => array ( 'IS_REQUIRED' => 'Y', 'DEFAULT_VALUE' => 'html', ),
			'DETAIL_TEXT' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ),
			'XML_ID' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ),
			'CODE' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ),
			'TAGS' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ),
		),
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

CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/_index.php", Array("OFFICIAL_NEWS_IBLOCK_ID" => $iblockID));
CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/desktop.php", Array("OFFICIAL_NEWS_IBLOCK_ID" => $iblockID));
CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/about/index.php", Array("OFFICIAL_NEWS_IBLOCK_ID" => $iblockID));
CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/about/official.php", Array("OFFICIAL_NEWS_IBLOCK_ID" => $iblockID));
CWizardUtil::ReplaceMacrosRecursive(WIZARD_SITE_PATH."/m/", Array("OFFICIAL_NEWS_IBLOCK_ID" => $iblockID));
CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/services/event_list.php", Array("OFFICIAL_NEWS_IBLOCK_ID" => $iblockID));

$val = COption::GetOptionString("intranet", "sonet_log_news_iblock", "", WIZARD_SITE_ID);
if (strlen($val) > 0)
{
	$arVal = unserialize($val);
	if (!is_array($arVal) || count($arVal) <= 0)
		$arVal = array();
}
else
	$arVal = array();
if (!in_array("official_news_".WIZARD_SITE_ID, $arVal))
	$arVal[] = "official_news_".WIZARD_SITE_ID;

$val = serialize($arVal);
COption::SetOptionString("intranet", "sonet_log_news_iblock", $val, false, WIZARD_SITE_ID);

?>
