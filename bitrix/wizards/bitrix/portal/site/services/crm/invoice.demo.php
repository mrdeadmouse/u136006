<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

$arInvoices = array();

$dbProduct = CCrmProduct::GetList(array(), array(), array('*'), array('nTopCount' => 1));
if ($arProduct = $dbProduct->Fetch())
{
	CModule::IncludeModule("sale");
	$personTypes = CCrmPaySystem::getPersonTypeIDs();

	$arFields = Array(
		"ORDER_TOPIC" => GetMessage("CRM_DEMO_INVOCIE_1_SUBJ"),
		"STATUS_ID" => "P",
		"DATE_INSERT" => ConvertTimeStamp(time() + CTimeZone::GetOffset(), 'FULL'),
		"DATE_BILL" => ConvertTimeStamp(time() + CTimeZone::GetOffset(), 'SHORT'),
		"PAY_VOUCHER_DATE" => ConvertTimeStamp(time() + CTimeZone::GetOffset(), 'FULL'),
		"DATE_PAY_BEFORE" => ConvertTimeStamp(time()+ 8000009 + CTimeZone::GetOffset(), 'SHORT'),
		"RESPONSIBLE_ID" => 1,
	   /* "COMMENTS" => ,
		"USER_DESCRIPTION" => ,
		"UF_DEAL_ID" => ,
		"UF_COMPANY_ID" => ,*/
		"UF_CONTACT_ID" => $arContacts["45"]['ID'],
		"PAY_VOUCHER_NUM" => 456,
		"DATE_MARKED" => ConvertTimeStamp(time() + CTimeZone::GetOffset(), 'FULL'),
		//"REASON_MARKED" => "reason",
		"PRODUCT_ROWS" => Array(
			Array
			(
				"ID" => 0,
				"PRODUCT_ID" => $arProduct["ID"],
				"PRODUCT_NAME" => $arProduct["NAME"],
				"QUANTITY" => 1,
				"PRICE" => $arProduct["PRICE"] ,
			)
		),
		"PERSON_TYPE_ID" => $personTypes["CONTACT"],
		"INVOICE_PROPERTIES" => Array()
	);

	$paySystems = CCrmPaySystem::GetPaySystems($personTypes["CONTACT"]);
	foreach($paySystems as $id=>$paySystem)
		$arFields["PAY_SYSTEM_ID"] = $id;

	$arAllProps = CCrmInvoice::GetPropertiesInfo($personTypes["CONTACT"]);
	foreach($arAllProps as $arProps)
		foreach($arProps as $key => $prop)
		{
			if ($key == "FIO")
				$arFields["INVOICE_PROPERTIES"][$prop["ID"]] = $arContacts["45"]["LAST_NAME"];
		}
	$arInvoices[] = $arFields;

	$arFields = Array(
		"ORDER_TOPIC" => GetMessage("CRM_DEMO_INVOCIE_2_SUBJ"),
		"STATUS_ID" => "N",
		"DATE_INSERT" => ConvertTimeStamp(time() + CTimeZone::GetOffset(), 'FULL'),
		"DATE_BILL" => ConvertTimeStamp(time() + CTimeZone::GetOffset(), 'SHORT'),
		"PAY_VOUCHER_DATE" => ConvertTimeStamp(time() + CTimeZone::GetOffset(), 'FULL'),
		"DATE_PAY_BEFORE" => ConvertTimeStamp(time()+ 8000009 + CTimeZone::GetOffset(), 'SHORT'),
		"RESPONSIBLE_ID" => 1,
		"UF_COMPANY_ID" => $arCompany["38"]['ID'],
		"PRODUCT_ROWS" => Array(
			Array
			(
				"ID" => 0,
				"PRODUCT_ID" => $arProduct["ID"],
				"PRODUCT_NAME" => $arProduct["NAME"],
				"QUANTITY" => 1,
				"PRICE" => $arProduct["PRICE"] ,
			)
		),
		"PERSON_TYPE_ID" => $personTypes["COMPANY"],
		"INVOICE_PROPERTIES" => Array()
	);

	$paySystems = CCrmPaySystem::GetPaySystems($personTypes["COMPANY"]);
	foreach($paySystems as $id=>$paySystem)
		$arFields["PAY_SYSTEM_ID"] = $id;

	$arAllProps = CCrmInvoice::GetPropertiesInfo($personTypes["COMPANY"]);
	foreach($arAllProps as $arProps)
		foreach($arProps as $key => $prop)
		{
			if ($key == "COMPANY")
				$arFields["INVOICE_PROPERTIES"][$prop["ID"]] = $arCompany["38"]["TITLE"];
		}
	$arInvoices[] = $arFields;
}

?>