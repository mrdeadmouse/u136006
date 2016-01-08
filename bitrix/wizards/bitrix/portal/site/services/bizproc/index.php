<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if(!CModule::IncludeModule("bizproc") || !CModule::IncludeModule("iblock"))
	return;

$langTmp = "";
$dbSite = CSite::GetById(WIZARD_SITE_ID);
if ($arSite = $dbSite->Fetch())
	$langTmp = $arSite["LANGUAGE_ID"];

$iblockType = "bizproc_iblockx";

$ib = new CIBlockType;
$arFields = Array(
	"ID" => $iblockType,
	"LANG" => array($langTmp => array("NAME" => GetMessage("BIZPROC_DEMO_TYPE_TITLE")))
);
$ib->Add($arFields);

$runtime = CBPRuntime::GetRuntime();
$runtime->StartRuntime();
$arResult["DocumentService"] = $runtime->GetService("DocumentService");

$iblockCode = "bizproc1_".WIZARD_SITE_ID; 

$rsIBlock = CIBlock::GetList(array(), array("XML_ID" => $iblockCode, "IBLOCK_TYPE_ID" => $iblockType));
$iblockID = false; 
if ($arIBlock = $rsIBlock->Fetch())
{
	$iblockID = $arIBlock["ID"]; 
}
if($iblockID == false && WIZARD_SITE_ID == "s1")
{
	$rsIBlock = CIBlock::GetList(array(), array("CODE" => "bizproc1", "TYPE" => $iblockType));
	$iblockID = false; 
	if ($arIBlock = $rsIBlock->Fetch())
	{
		$iblockID = $arIBlock["ID"]; 
	}
}
if($iblockID == false)
{

	$ib = new CIBlock();
	$arFields = array(
		"IBLOCK_TYPE_ID" => $iblockType,
		"CODE" => $iblockCode,
		"XML_ID" => $iblockCode,
		"LID" => WIZARD_SITE_ID,
		"NAME" => GetMessage("BIZPROC_DEMO_TRIP"),
		"ACTIVE" => 'Y',
		"SORT" => 100,
		"INDEX_ELEMENT" => "N",
		"PICTURE" => array(
			"name" => "business_trip.jpg",
			"size" => filesize(WIZARD_ABSOLUTE_PATH.'/site/services/bizproc/image/1.jpg'),
			"tmp_name" => WIZARD_ABSOLUTE_PATH.'/site/services/bizproc/image/1.jpg',
			"type" => 'image/jpeg'
		),
		"DESCRIPTION" => "v2:a:3:{s:11:\"DESCRIPTION\";s:0:\"\";s:17:\"FILTERABLE_FIELDS\";a:6:{i:0;s:10:\"CREATED_BY\";i:1;s:11:\"ACTIVE_FROM\";i:2;s:9:\"ACTIVE_TO\";i:3;s:4:\"NAME\";i:4;s:13:\"PROPERTY_CITY\";i:5;s:16:\"PROPERTY_COUNTRY\";}s:14:\"VISIBLE_FIELDS\";a:12:{i:0;s:21:\"MODIFIED_BY_PRINTABLE\";i:1;s:11:\"DATE_CREATE\";i:2;s:20:\"CREATED_BY_PRINTABLE\";i:3;s:11:\"ACTIVE_FROM\";i:4;s:9:\"ACTIVE_TO\";i:5;s:4:\"NAME\";i:6;s:12:\"PREVIEW_TEXT\";i:7;s:13:\"PROPERTY_CITY\";i:8;s:16:\"PROPERTY_tickets\";i:9;s:16:\"PROPERTY_COUNTRY\";i:10;s:22:\"PROPERTY_date_end_real\";i:11;s:26:\"PROPERTY_expenditures_real\";}}",
		"DESCRIPTION_TYPE" => 'text',
		"WORKFLOW" => 'N',
		"BIZPROC" => 'Y',
		"VERSION" => 1,
		"ELEMENT_ADD" => GetMessage("BIZPROC_DEMO_TRIP_ADD"),
		"GROUP_ID" => array(2 => "R"),
	);
	$iblockId = $ib->Add($arFields);
	
	include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/bizproc/templates_bp/business_trips.php");
	
	$arVariables = $bpTemplateObject->GetVariables();
	$arVariables["ParameterOpRead"]["Default"] = array(1, "author");
	$arVariables["ParameterOpCreate"]["Default"] = array(1, "author");
	$arVariables["ParameterOpAdmin"]["Default"] = array(1);
	$arVariables["ParameterBoss"]["Default"] = array(1);
	$arVariables["ParameterBookkeeper"]["Default"] = array(1);
	$arVariables["ParameterForm1"]["Default"] = "/upload/".LANGUAGE_ID."/form1.doc";
	$arVariables["ParameterForm2"]["Default"] = "/upload/".LANGUAGE_ID."/form2.doc";
	
	$arFieldsT = array(
		"DOCUMENT_TYPE" => array("bizproc", "CBPVirtualDocument", "type_".$iblockId),
		"AUTO_EXECUTE" => CBPDocumentEventType::Create,
		"NAME" => GetMessage("BIZPROC_DEMO_TRIP"),
		"DESCRIPTION" => "",
		"TEMPLATE" => $bpTemplateObject->GetTemplate(),
		"PARAMETERS" => $bpTemplateObject->GetParameters(),
		"VARIABLES" => $arVariables,
		"USER_ID" => $GLOBALS["USER"]->GetID(),
		"ACTIVE" => 'Y',
	);
	CBPWorkflowTemplateLoader::Add($arFieldsT);
	
	$arDocumentFields = $bpTemplateObject->GetDocumentFields();
	if ($arDocumentFields && is_array($arDocumentFields) && count($arDocumentFields) > 0)
	{
		foreach ($arDocumentFields as $f)
		{
			$arResult["DocumentService"]->AddDocumentField(
				array("bizproc", "CBPVirtualDocument", "type_".$iblockId),
				$f
			);
		}
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

$iblockCode = "bizproc2_".WIZARD_SITE_ID; 

$rsIBlock = CIBlock::GetList(array(), array("XML_ID" => $iblockCode, "IBLOCK_TYPE_ID" => $iblockType));
$iblockID = false; 
if ($arIBlock = $rsIBlock->Fetch())
{
	$iblockID = $arIBlock["ID"]; 
}
if($iblockID == false && WIZARD_SITE_ID == "s1")
{
	$rsIBlock = CIBlock::GetList(array(), array("CODE" => "bizproc2", "TYPE" => $iblockType));
	$iblockID = false; 
	if ($arIBlock = $rsIBlock->Fetch())
	{
		$iblockID = $arIBlock["ID"]; 
	}
}
if($iblockID == false)
{
	$ib = new CIBlock();
	$arFields = array(
		"IBLOCK_TYPE_ID" => $iblockType,
		"CODE" => $iblockCode,
		"XML_ID" => $iblockCode,
		"LID" => WIZARD_SITE_ID,
		"NAME" => GetMessage("BIZPROC_DEMO_REST"),
		"ACTIVE" => 'Y',
		"INDEX_ELEMENT" => "N",
		"SORT" => 100,
		"PICTURE" => array(
			"name" => "rest.jpg",
			"size" => filesize(WIZARD_ABSOLUTE_PATH.'/site/services/bizproc/image/2.jpg'),
			"tmp_name" => WIZARD_ABSOLUTE_PATH.'/site/services/bizproc/image/2.jpg',
			"type" => 'image/jpeg'
		),
		"DESCRIPTION" => "v2:a:3:{s:11:\"DESCRIPTION\";s:0:\"\";s:17:\"FILTERABLE_FIELDS\";a:4:{i:0;s:10:\"CREATED_BY\";i:1;s:11:\"ACTIVE_FROM\";i:2;s:9:\"ACTIVE_TO\";i:3;s:4:\"NAME\";}s:14:\"VISIBLE_FIELDS\";a:4:{i:0;s:20:\"CREATED_BY_PRINTABLE\";i:1;s:11:\"ACTIVE_FROM\";i:2;s:9:\"ACTIVE_TO\";i:3;s:4:\"NAME\";}}",
		"DESCRIPTION_TYPE" => 'text',
		"WORKFLOW" => 'N',
		"BIZPROC" => 'Y',
		"VERSION" => 1,
		"ELEMENT_ADD" => GetMessage("BIZPROC_DEMO_REST_ADD"),
		"GROUP_ID" => array(2 => "R"),
	);
	$iblockId = $ib->Add($arFields);
	
	include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/bizproc/templates_bp/vacation.php");
	
	$arVariables = $bpTemplateObject->GetVariables();
	$arVariables["ParameterOpRead"]["Default"] = array(1, "author");
	$arVariables["ParameterOpCreate"]["Default"] = array(1, "author");
	$arVariables["ParameterOpAdmin"]["Default"] = array(1);
	$arVariables["ParameterBoss"]["Default"] = array(1);
	$arVariables["ParameterBookkeeper"]["Default"] = array(1);
	
	$arFieldsT = array(
		"DOCUMENT_TYPE" => array("bizproc", "CBPVirtualDocument", "type_".$iblockId),
		"AUTO_EXECUTE" => CBPDocumentEventType::Create,
		"NAME" => GetMessage("BIZPROC_DEMO_REST"),
		"DESCRIPTION" => "",
		"TEMPLATE" => $bpTemplateObject->GetTemplate(),
		"PARAMETERS" => $bpTemplateObject->GetParameters(),
		"VARIABLES" => $arVariables,
		"USER_ID" => $GLOBALS["USER"]->GetID(),
		"ACTIVE" => 'Y',
	);
	CBPWorkflowTemplateLoader::Add($arFieldsT);
	
	$arDocumentFields = $bpTemplateObject->GetDocumentFields();
	if ($arDocumentFields && is_array($arDocumentFields) && count($arDocumentFields) > 0)
	{
		foreach ($arDocumentFields as $f)
		{
			$arResult["DocumentService"]->AddDocumentField(
				array("bizproc", "CBPVirtualDocument", "type_".$iblockId),
				$f
			);
		}
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

CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/services/bp/index.php", array());

$doInst = $wizard->GetVar("install_acct_list");
if ((is_null($doInst) || $doInst != "N") && CModule::IncludeModule("lists") && file_exists($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/lists/install/bizproc/templates/acct.php'))
{
/*	$rsIBlock = CIBlock::GetList(array(), array("XML_ID" => "bx_lists_bp_acct", "IBLOCK_TYPE_ID" => "lists"));
	$iblockID = false; 
	if ($arIBlock = $rsIBlock->Fetch())
	{
		$iblockID = $arIBlock["ID"]; 
		if (WIZARD_INSTALL_DEMO_DATA)
		{
			CIBlock::Delete($arIBlock["ID"]); 
			$iblockID = false; 
		}
	}

	if ($iblockID === false)
	{*/
		$ob = new CIBlock;

		$arFieldsL = array(
			"NAME" => GetMessage("BIZPROC_DEMO1_ACCTS"),
			"IBLOCK_TYPE_ID" => "lists",
			"SORT" => 500,
			"WORKFLOW" => "N",
			"ELEMENTS_NAME" => GetMessage("BIZPROC_DEMO1_ELEMENTS_NAME"),
			"ELEMENT_NAME" => GetMessage("BIZPROC_DEMO1_ELEMENT_NAME"),
			"ELEMENT_ADD" => GetMessage("BIZPROC_DEMO1_ELEMENT_ADD"),
			"ELEMENT_EDIT" => GetMessage("BIZPROC_DEMO1_ELEMENT_EDIT"),
			"ELEMENT_DELETE" => GetMessage("BIZPROC_DEMO1_ELEMENT_DELETE"),
			"BIZPROC" => "Y",
			/*"XML_ID" => "bx_lists_bp_acct",*/
			"SITE_ID" => array(WIZARD_SITE_ID),
			"RIGHTS_MODE" => "E",
			"RIGHTS" => array()
		);

		$resL = $ob->Add($arFieldsL);

		if ($resL)
		{
			$obList = new CList($resL);
			$obList->Save();

			$arFields = false;
			$documentType = array("iblock", "CIBlockDocument", "iblock_".$resL);
			include($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/lists/install/bizproc/templates/acct.php');
			if (is_array($arFields))
			{
				$arFields["DOCUMENT_TYPE"] = array("iblock", "CIBlockDocument", "iblock_".$resL);
				$arFields["SYSTEM_CODE"] = "acct.php";
				if (is_object($GLOBALS['USER']))
					$arFields["USER_ID"] = $GLOBALS['USER']->GetID();
				$arFields["MODIFIER_USER"] = new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser);
				try
				{
					CBPWorkflowTemplateLoader::Add($arFields);
				}
				catch (Exception $e)
				{
				}
			}
		}
	/*}*/
}
?>