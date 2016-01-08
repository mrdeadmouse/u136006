<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

CModule::IncludeModule('fileman');

if (!CModule::IncludeModule('catalog'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED_CATALOG'));
	return;
}
if (!CModule::IncludeModule('sale'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED_SALE'));
	return;
}

$CCrmInvoice = new CCrmInvoice();
if ($CCrmInvoice->cPerms->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'WRITE')
	&& $CCrmInvoice->cPerms->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'ADD'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arParams['PATH_TO_INVOICE_LIST'] = CrmCheckPath('PATH_TO_INVOICE_LIST', $arParams['PATH_TO_INVOICE_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_INVOICE_SHOW'] = CrmCheckPath('PATH_TO_INVOICE_SHOW', $arParams['PATH_TO_INVOICE_SHOW'], $APPLICATION->GetCurPage().'?invoice_id=#invoice_id#&show');
$arParams['PATH_TO_INVOICE_EDIT'] = CrmCheckPath('PATH_TO_INVOICE_EDIT', $arParams['PATH_TO_INVOICE_EDIT'], $APPLICATION->GetCurPage().'?invoice_id=#invoice_id#&edit');
$arParams['PATH_TO_PRODUCT_EDIT'] = CrmCheckPath('PATH_TO_PRODUCT_EDIT', $arParams['PATH_TO_PRODUCT_EDIT'], $APPLICATION->GetCurPage().'?product_id=#product_id#&edit');
$arParams['PATH_TO_PRODUCT_SHOW'] = CrmCheckPath('PATH_TO_PRODUCT_SHOW', $arParams['PATH_TO_PRODUCT_SHOW'], $APPLICATION->GetCurPage().'?product_id=#product_id#&show');
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

$bInternal = false;
if (isset($arParams['INTERNAL_FILTER']) && !empty($arParams['INTERNAL_FILTER']))
	$bInternal = true;
$arResult['INTERNAL'] = $bInternal;

global $DB, $USER, $USER_FIELD_MANAGER;

$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmInvoice::$sUFEntityID);

$bEdit = false;
$bCopy = false;
$bVarsFromForm = false;
$arParams['ELEMENT_ID'] = (int) $arParams['ELEMENT_ID'];
if (!empty($arParams['ELEMENT_ID']))
	$bEdit = true;
if (!empty($_REQUEST['copy']))
{
	$bCopy = true;
	$bEdit = false;
}

$bCreateFromQuote = $bCreateFromDeal = $bCreateFromCompany = $bCreateFromContact = false;
$quoteId = $dealId = $companyId = $contactId = 0;
$arQuoteClientFields = array();
if (intval($_REQUEST['quote']) > 0)
{
	$bCreateFromQuote = true;
	$quoteId = intval($_REQUEST['quote']);
}
elseif (intval($_REQUEST['deal']) > 0)
{
	$bCreateFromDeal = true;
	$dealId = intval($_REQUEST['deal']);
}
elseif (intval($_REQUEST['company']) > 0)
{
	$bCreateFromCompany = true;
	$companyId = intval($_REQUEST['company']);
}
if (intval($_REQUEST['contact']) > 0)
{
	$bCreateFromContact = true;
	$contactId = intval($_REQUEST['contact']);
}

$bCreateFrom = ($bCreateFromQuote || $bCreateFromDeal || $bCreateFromCompany || $bCreateFromContact);

$bConvert = isset($arParams['CONVERT']) && $arParams['CONVERT'];

$bTaxMode = CCrmTax::isTaxMode();

if (($bEdit || $bCopy) && !empty($arResult['ELEMENT']['CURRENCY']))
	$currencyID = $arResult['ELEMENT']['CURRENCY'];
else
	$currencyID = CCrmInvoice::GetCurrencyID();

if ($bEdit || $bCopy)
{
	$arFilter = array(
		'ID' => $arParams['ELEMENT_ID'],
		'PERMISSION' => 'WRITE'
	);
	$obFields = CCrmInvoice::GetList(array(), $arFilter);
	$arFields = $obFields->GetNext();
	if ($arFields === false)
	{
		$bEdit = false;
		$bCopy = false;
	}
	else
		$arEntityAttr = $CCrmInvoice->cPerms->GetEntityAttr('INVOICE', array($arParams['ELEMENT_ID']));

	//HACK: MSSQL returns '.00' for zero value
	if(isset($arFields['~PRICE']))
	{
		$arFields['~PRICE'] = $arFields['PRICE'] = floatval($arFields['~PRICE']);
	}
}
else
{
	$arFields = array(
		'ID' => 0,
		'DATE_INSERT' => ConvertTimeStamp(time(), 'FULL', SITE_ID)
	);

	if ($bCreateFromQuote)
	{
		$arFields['UF_QUOTE_ID'] = $quoteId;
		$arQuote = CCrmQuote::GetByID($quoteId);
		$arQuoteProducts = CCrmQuote::LoadProductRows($quoteId);
		if (is_array($arQuote) && count($arQuote) > 0)
		{
			if ($bTaxMode && isset($arQuote['LOCATION_ID']))
			{
				$arFields['~PR_LOCATION'] = $arQuote['LOCATION_ID'];
				$arFields['PR_LOCATION'] = htmlspecialcharsbx($arQuote['LOCATION_ID']);
			}
			if (isset($arQuote['TITLE']))
			{
				$arFields['~ORDER_TOPIC'] = $arQuote['TITLE'];
				$arFields['ORDER_TOPIC'] = htmlspecialcharsbx($arQuote['TITLE']);
			}
			if (isset($arQuote['COMPANY_ID']))
			{
				$arFields['~UF_COMPANY_ID'] = $arQuote['COMPANY_ID'];
				$arFields['UF_COMPANY_ID'] = htmlspecialcharsbx($arQuote['COMPANY_ID']);
			}
			if (isset($arQuote['CONTACT_ID']))
			{
				$arFields['~UF_CONTACT_ID'] = $arQuote['CONTACT_ID'];
				$arFields['UF_CONTACT_ID'] = htmlspecialcharsbx($arQuote['CONTACT_ID']);
			}
			if (isset($arQuote['DEAL_ID']))
			{
				$arFields['~UF_DEAL_ID'] = $arQuote['DEAL_ID'];
				$arFields['UF_DEAL_ID'] = htmlspecialcharsbx($arQuote['DEAL_ID']);
			}
			if (isset($arQuote['ASSIGNED_BY_ID']))
			{
				$arFields['~RESPONSIBLE_ID'] = $arQuote['ASSIGNED_BY_ID'];
				$arFields['RESPONSIBLE_ID'] = htmlspecialcharsbx($arQuote['ASSIGNED_BY_ID']);
			}
			if (isset($arQuote['COMMENTS']))
			{
				$arFields['~COMMENTS'] = $arQuote['COMMENTS'];
				$arFields['COMMENTS'] = htmlspecialcharsbx($arQuote['COMMENTS']);
			}
			foreach (CCrmQuote::GetClientFields() as $k)
				$arQuoteClientFields[$k] = isset($arQuote[$k]) ? $arQuote[$k] : '';
			unset($k);
			if (is_array($arQuoteProducts) && count($arQuoteProducts) > 0)
			{
				$quoteCurrencyID =
					(empty($arQuote['CURRENCY_ID']) || !CCrmCurrency::IsExists($arQuote['CURRENCY_ID'])) ?
						CCrmCurrency::GetBaseCurrencyID() :
						$arQuote['CURRENCY_ID'];
				$freshRows = CCrmInvoice::ProductRows2BasketItems($arQuoteProducts, $quoteCurrencyID, $currencyID);
				if (count($freshRows) > 0)
				{
					$arFields['PRODUCT_ROWS']= $arResult['PRODUCT_ROWS'] = $freshRows;
				}
				unset($freshRows);
			}
			unset($arQuoteProducts);
		}
		unset($arQuote, $arQuoteProducts);

		// read product row settings
		$productRowSettings = array();
		$arQuoteProductRowSettings = CCrmProductRow::LoadSettings(CCrmQuote::OWNER_TYPE, $quoteId);
		if (is_array($arQuoteProductRowSettings))
		{
			$productRowSettings['ENABLE_DISCOUNT'] = isset($arQuoteProductRowSettings['ENABLE_DISCOUNT']) ? $arQuoteProductRowSettings['ENABLE_DISCOUNT'] : false;
			$productRowSettings['ENABLE_TAX'] = isset($arQuoteProductRowSettings['ENABLE_TAX']) ? $arQuoteProductRowSettings['ENABLE_TAX'] : false;
		}
		unset($arQuoteProductRowSettings);
	}
	elseif ($bCreateFromDeal)
	{
		$arFields['UF_DEAL_ID'] = $dealId;
		$arDeal = CCrmDeal::GetByID($dealId);
		$arDealProducts = CCrmDeal::LoadProductRows($dealId);
		if (is_array($arDeal) && count($arDeal) > 0)
		{
			if (isset($arDeal['TITLE']))
			{
				$arFields['~ORDER_TOPIC'] = $arDeal['TITLE'];
				$arFields['ORDER_TOPIC'] = htmlspecialcharsbx($arDeal['TITLE']);
			}
			if (isset($arDeal['COMPANY_ID']))
			{
				$arFields['~UF_COMPANY_ID'] = $arDeal['COMPANY_ID'];
				$arFields['UF_COMPANY_ID'] = htmlspecialcharsbx($arDeal['COMPANY_ID']);
			}
			if (isset($arDeal['CONTACT_ID']))
			{
				$arFields['~UF_CONTACT_ID'] = $arDeal['CONTACT_ID'];
				$arFields['UF_CONTACT_ID'] = htmlspecialcharsbx($arDeal['CONTACT_ID']);
			}
			if (isset($arDeal['ASSIGNED_BY_ID']))
			{
				$arFields['~RESPONSIBLE_ID'] = $arDeal['ASSIGNED_BY_ID'];
				$arFields['RESPONSIBLE_ID'] = htmlspecialcharsbx($arDeal['ASSIGNED_BY_ID']);
			}
			if (isset($arDeal['COMMENTS']))
			{
				$arFields['~COMMENTS'] = $arDeal['COMMENTS'];
				$arFields['COMMENTS'] = htmlspecialcharsbx($arDeal['COMMENTS']);
			}
			if (is_array($arDealProducts) && count($arDealProducts) > 0)
			{
				$dealCurrencyID =
					(empty($arDeal['CURRENCY_ID']) || !CCrmCurrency::IsExists($arDeal['CURRENCY_ID'])) ?
						CCrmCurrency::GetBaseCurrencyID() :
						$arDeal['CURRENCY_ID'];
				$freshRows = CCrmInvoice::ProductRows2BasketItems($arDealProducts, $dealCurrencyID, $currencyID);
				if (count($freshRows) > 0)
				{
					$arFields['PRODUCT_ROWS']= $arResult['PRODUCT_ROWS'] = $freshRows;
				}
				unset($freshRows);
			}
			unset($arDealProducts);
		}
		unset($arDeal, $arDealProducts);

		// read product row settings
		$productRowSettings = array();
		$arDealProductRowSettings = CCrmProductRow::LoadSettings('D', $dealId);
		if (is_array($arDealProductRowSettings))
		{
			$productRowSettings['ENABLE_DISCOUNT'] = isset($arDealProductRowSettings['ENABLE_DISCOUNT']) ? $arDealProductRowSettings['ENABLE_DISCOUNT'] : false;
			$productRowSettings['ENABLE_TAX'] = isset($arDealProductRowSettings['ENABLE_TAX']) ? $arDealProductRowSettings['ENABLE_TAX'] : false;
		}
		unset($arDealProductRowSettings);
	}
	elseif ($bCreateFromCompany)
		$arFields['UF_COMPANY_ID'] = $companyId;
	elseif ($bCreateFromContact)
		$arFields['UF_CONTACT_ID'] = $contactId;
}


if (($bEdit && !$CCrmInvoice->cPerms->CheckEnityAccess('INVOICE', 'WRITE', $arEntityAttr[$arParams['ELEMENT_ID']]) ||
	(!$bEdit && $CCrmInvoice->cPerms->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'ADD'))))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$isExternal = $bEdit && isset($arFields['ORIGINATOR_ID']) && isset($arFields['ORIGIN_ID']) && intval($arFields['ORIGINATOR_ID']) > 0 && intval($arFields['ORIGIN_ID']) > 0;

$bProcessPost = $_SERVER['REQUEST_METHOD'] == 'POST' && check_bitrix_sessid();
if ($bProcessPost)
{
	$bAjaxSubmit = (isset($_POST['invoiceSubmitAjax']) && $_POST['invoiceSubmitAjax'] === 'Y') ? true : false;
	$bMakePayerInfo = (isset($_POST['invoiceMakePayerInfo']) && $_POST['invoiceMakePayerInfo'] === 'Y') ? true : false;
}

// Determine person type
$personTypeId = 0;
$arPersonTypes = CCrmPaySystem::getPersonTypeIDs();
if (isset($arPersonTypes['COMPANY']) && isset($arPersonTypes['CONTACT']))
{
	if ($bProcessPost)
	{
		$info = $CCrmInvoice::__GetCompanyAndContactFromPost($_POST);
		if ($info['COMPANY'] > 0) $personTypeId = $arPersonTypes['COMPANY'];
		elseif ($info['CONTACT'] > 0) $personTypeId = $arPersonTypes['CONTACT'];
		unset($info);
	}
	else
	{
		if (intval($arFields['UF_COMPANY_ID']) > 0) $personTypeId = $arPersonTypes['COMPANY'];
		elseif (intval($arFields['UF_CONTACT_ID']) > 0) $personTypeId = $arPersonTypes['CONTACT'];
	}
}

// Get invoice properties
$arInvoiceProperties = array();
if ($bEdit || $bCopy || $bProcessPost || $bCreateFromQuote || $bCreateFromDeal || $bCreateFromCompany || $bCreateFromContact)
{
	$tmpArProps = $CCrmInvoice->GetProperties($arParams['ELEMENT_ID'], $personTypeId);
	if ($tmpArProps !== false)
	{
		$arInvoiceProperties = $tmpArProps;
		if ($bTaxMode && !isset($arFields['PR_LOCATION']) && isset($arInvoiceProperties['PR_LOCATION']))
			$arFields['PR_LOCATION'] = $arInvoiceProperties['PR_LOCATION']['VALUE'];
	}
	unset($tmpArProps);
}

$bVatMode = CCrmTax::isVatMode();

if (isset($arFields['~COMMENTS']))
{
	$arFields['COMMENTS'] = htmlspecialcharsbx($arFields['~COMMENTS']);
}
if (isset($arFields['~USER_DESCRIPTION']))
{
	$arFields['USER_DESCRIPTION'] = htmlspecialcharsbx($arFields['~USER_DESCRIPTION']);
}

$arResult['ELEMENT'] = $arFields;
unset($arFields);

$arResult['FORM_ID'] = !empty($arParams['FORM_ID']) ? $arParams['FORM_ID'] : 'CRM_INVOICE_EDIT_V12';
$arResult['GRID_ID'] = 'CRM_INVOICE_LIST_V12';
$arResult['AJAX_SUBMIT_FUNCTION'] = ((isset($arResult['FORM_ID']) && !empty($arResult['FORM_ID'])) ? $arResult['FORM_ID'] : 'crm_invoice_form').'_ajax_submit';
$arResult['FORM_CUSTOM_HTML'] = '';

// status sort array for js
$arResult['STATUS_SORT'] = array();
$arInvoiceStatuses = CCrmStatusInvoice::GetStatus('INVOICE_STATUS');
foreach ($arInvoiceStatuses as $statusId => $statusInfo)
{
	$arResult['STATUS_SORT'][$statusId] = $statusInfo['SORT'];
}
unset($arInvoiceStatuses);

// id of a payer information field (needed for update via ajax)
$arResult['PAYER_INFO_FIELD_ID'] = $payerInfoFieldId = 'PAYER_INFO';
$arResult['PAYER_INFO_EDIT_LINK_ID'] = 'PAYER_INFO_EDIT_LINK';
$arResult['PAY_SYSTEMS_LIST_ID'] = $paySystemFieldId = 'PAY_SYSTEM_ID';
$arResult['INVOICE_PROPS_DIV_ID'] = $invoicePropsDivId = 'INVOICE_PROPS_BLOCK';

$productDataFieldName = 'INVOICE_PRODUCT_DATA';

$arResult['INVOICE_REFERER'] = '';
if ($bProcessPost && !$bAjaxSubmit && !empty($_POST['INVOICE_REFERER']))
{
	$arResult['INVOICE_REFERER'] = strval($_POST['INVOICE_REFERER']);
}
else if ($bCreateFrom && !empty($GLOBALS['_SERVER']['HTTP_REFERER']))
{
	$arResult['INVOICE_REFERER'] = strval($_SERVER['HTTP_REFERER']);
}
if ($bCreateFrom && !empty($arResult['INVOICE_REFERER']))
{
	$arResult['FORM_CUSTOM_HTML'] =
		'<input type="hidden" name="INVOICE_REFERER" value="'.htmlspecialcharsbx($arResult['INVOICE_REFERER']).'" />'.
		PHP_EOL.$arResult['FORM_CUSTOM_HTML'];
}

if($bConvert)
{
	$bVarsFromForm = true;
}
else
{
	if ($bProcessPost)
	{
		$bVarsFromForm = true;
		if (isset($_POST['save']) || isset($_POST['saveAndView']) || isset($_POST['saveAndAdd']) || isset($_POST['apply']) || $bAjaxSubmit)
		{
			//Check entities access -->
			$quoteID = isset($_POST['UF_QUOTE_ID']) ? intval($_POST['UF_QUOTE_ID']) : 0;
			if($quoteID > 0 && !CCrmQuote::CheckReadPermission($quoteID))
			{
				$quoteID = 0;
			}

			$dealID = isset($_POST['UF_DEAL_ID']) ? intval($_POST['UF_DEAL_ID']) : 0;
			if($dealID > 0 && !CCrmDeal::CheckReadPermission($dealID))
			{
				$dealID = 0;
			}

			$info = CCrmInvoice::__GetCompanyAndContactFromPost($_POST);
			$companyID = $info['COMPANY'];
			if($companyID > 0 && !CCrmCompany::CheckReadPermission($companyID))
			{
				$companyID = 0;
			}

			$contactID = $info['CONTACT'];
			if($contactID > 0 && !CCrmContact::CheckReadPermission($contactID))
			{
				$contactID = 0;
			}
			unset($info);
			//<-- Check entities access

			$comments = trim($_POST['COMMENTS']);
			$bSanitizeComments = ($comments !== '' && strpos($comments, '<'));
			$userDescription = trim($_POST['USER_DESCRIPTION']);
			$bSanitizeUserDescription = ($userDescription !== '' && strpos($userDescription, '<'));
			if($bSanitizeComments || $bSanitizeUserDescription)
			{
				$sanitizer = new CBXSanitizer();
				$sanitizer->ApplyDoubleEncode(false);
				$sanitizer->SetLevel(CBXSanitizer::SECURE_LEVEL_MIDDLE);
				//Crutch for for Chrome line break behaviour in HTML editor.
				$sanitizer->AddTags(array('div' => array()));
				if ($bSanitizeComments)
					$comments = $sanitizer->SanitizeHtml($comments);
				if ($bSanitizeUserDescription)
					$userDescription = $sanitizer->SanitizeHtml($userDescription);
				unset($sanitizer);
			}
			unset($bSanitizeComments, $bSanitizeUserDescription);

			$dateInsert = ConvertTimeStamp(time(), 'FULL', SITE_ID);
			if ($bEdit && isset($arResult['ELEMENT']['DATE_INSERT']))
				$dateInsert = $arResult['ELEMENT']['DATE_INSERT'];

			$arFields = array(
				'ORDER_TOPIC' => trim($_POST['ORDER_TOPIC']),
				'STATUS_ID' => trim($_POST['STATUS_ID']),
				'DATE_INSERT' => $dateInsert,
				'DATE_BILL' => isset($_POST['DATE_BILL']) ? trim($_POST['DATE_BILL']) : null,
				'PAY_VOUCHER_DATE' => isset($_POST['PAY_VOUCHER_DATE']) ? trim($_POST['PAY_VOUCHER_DATE']) : null,
				'DATE_PAY_BEFORE' => trim($_POST['DATE_PAY_BEFORE']),
				'RESPONSIBLE_ID' => intval($_POST['RESPONSIBLE_ID']),
				'COMMENTS' => $comments,
				'USER_DESCRIPTION' => $userDescription,
				'UF_QUOTE_ID' => $quoteID,
				'UF_DEAL_ID' => $dealID,
				'UF_COMPANY_ID' => $companyID,
				'UF_CONTACT_ID' => $contactID
			);
			unset($dateInsert);

			if ($bTaxMode)
			{
				$arFields['PR_LOCATION'] = $_POST['LOC_CITY'];
			}
			if ($bEdit)
				$arFields['ACCOUNT_NUMBER'] = trim($_POST['ACCOUNT_NUMBER']);
			$bStatusSuccess = CCrmStatusInvoice::isStatusSuccess($arFields['STATUS_ID']);
			if ($bStatusSuccess)
				$bStatusFailed = false;
			else
				$bStatusFailed = CCrmStatusInvoice::isStatusFailed($arFields['STATUS_ID']);
			if ($bStatusSuccess)
			{
				$arFields['PAY_VOUCHER_NUM'] = isset($_POST['PAY_VOUCHER_NUM']) ? substr(trim($_POST['PAY_VOUCHER_NUM']), 0, 20) : '';
				$arFields['DATE_MARKED'] = $statusParams['PAY_VOUCHER_DATE'] = isset($_POST['PAY_VOUCHER_DATE']) ? trim($_POST['PAY_VOUCHER_DATE']) : null;
				$arFields['REASON_MARKED'] = isset($_POST['REASON_MARKED_SUCCESS']) ? substr(trim($_POST['REASON_MARKED_SUCCESS']), 0, 255) : '';
			}
			elseif ($bStatusFailed)
			{
				$arFields['DATE_MARKED'] = isset($_REQUEST['DATE_MARKED']) ? trim($_POST['DATE_MARKED']) : null;
				$arFields['REASON_MARKED'] = isset($_REQUEST['REASON_MARKED']) ? substr(trim($_REQUEST['REASON_MARKED']), 0, 255) : '';
			}

			$processProductRows = array_key_exists($productDataFieldName, $_POST);
			$arProduct = array();
			if($processProductRows)
			{
				$prodJson = isset($_POST[$productDataFieldName]) ? strval($_POST[$productDataFieldName]) : '';
				$arProduct = strlen($prodJson) > 0 ? CUtil::JsObjectToPhp($prodJson) : array();
			}

			// sort product rows
			/*$arSort = array();
			foreach ($arProduct as $row)
				$arSort[] = isset($row['SORT']) ? intval($row['SORT']) : 0;
			unset($row);
			array_multisort($arSort, SORT_ASC, SORT_NUMERIC, $arProduct);
			unset($arSort);*/

			$arProduct = CCrmInvoice::ProductRows2BasketItems($arProduct);
			$arResult['PRODUCT_ROWS'] = $arFields['PRODUCT_ROWS'] = $arProduct;

			// Product row settings
			$productRowSettings = array();
			$productRowSettingsFieldName = $productDataFieldName.'_SETTINGS';
			if(array_key_exists($productRowSettingsFieldName, $_POST))
			{
				$settingsJson = isset($_POST[$productRowSettingsFieldName]) ? strval($_POST[$productRowSettingsFieldName]) : '';
				$arSettings = strlen($settingsJson) > 0 ? CUtil::JsObjectToPhp($settingsJson) : array();
				if(is_array($arSettings))
				{
					$productRowSettings['ENABLE_DISCOUNT'] = isset($arSettings['ENABLE_DISCOUNT']) ? $arSettings['ENABLE_DISCOUNT'] === 'Y' : false;
					$productRowSettings['ENABLE_TAX'] = isset($arSettings['ENABLE_TAX']) ? $arSettings['ENABLE_TAX'] === 'Y' : false;
				}
			}
			unset($productRowSettingsFieldName, $settingsJson, $arSettings);

			// set person type field
			$arFields['PERSON_TYPE_ID'] = $personTypeId;

			// set pay system field
			$arFields['PAY_SYSTEM_ID'] = intval($_POST['PAY_SYSTEM_ID']);

			// <editor-fold defaultstate="collapsed" desc="Process invoice properties ...">
			$tmpArInvoicePropertiesValues = $CCrmInvoice->ParsePropertiesValuesFromPost($personTypeId, $_POST, $arInvoiceProperties);
			if (isset($tmpArInvoicePropertiesValues['PROPS_VALUES']) && isset($tmpArInvoicePropertiesValues['PROPS_INDEXES']))
			{
				$arFields['INVOICE_PROPERTIES'] = $tmpArInvoicePropertiesValues['PROPS_VALUES'];
				foreach ($tmpArInvoicePropertiesValues['PROPS_INDEXES'] as $propertyName => $propertyIndex)
					if (!isset($arFields[$propertyName]))
						$arFields[$propertyName] = $tmpArInvoicePropertiesValues['PROPS_VALUES'][$propertyIndex];
			}
			unset($tmpArInvoicePropertiesValues);
			// </editor-fold>

			$USER_FIELD_MANAGER->EditFormAddFields(CCrmInvoice::GetUserFieldEntityID(), $arFields);

			if (!$CCrmInvoice->CheckFields($arFields, $bEdit ? $arResult['ELEMENT']['ID'] : false, $bStatusSuccess, $bStatusFailed))
			{
				if (!empty($CCrmInvoice->LAST_ERROR))
					$arResult['ERROR_MESSAGE'] .= $CCrmInvoice->LAST_ERROR;
				else
					$arResult['ERROR_MESSAGE'] .= GetMessage('UNKNOWN_ERROR');
			}

			if ($bAjaxSubmit)
			{
				// make payer information
				$strPayerInfo = '';
				$arPaySystemsListItems = array();
				$companyId = $contactId = 0;
				if ($bMakePayerInfo)
				{
					// payer information
					$companyId = intval($arFields['UF_COMPANY_ID']);
					$contactId = intval($arFields['UF_CONTACT_ID']);
					CCrmInvoice::__RewritePayerInfo($companyId, $contactId, $arInvoiceProperties);
					$invoicePropsHtmlInputs = CCrmInvoice::__MakePropsHtmlInputs($arInvoiceProperties);
					$strPayerInfo = CCrmInvoice::__MakePayerInfoString($arInvoiceProperties);

					// pay systems
					$arPaySystemsListItems = CCrmPaySystem::GetPaySystemsListItems($personTypeId);
				}

				// recalculate Invoice
				$arFields['ID'] = $bEdit ? $arResult['ELEMENT']['ID'] : 0;
				$arRecalculated = $CCrmInvoice->Recalculate($arFields);

				// product rows to remove
				$arRemoveItems = array();
				if (is_array($arProduct) && count($arProduct) > 0)
				{
					$arRemoveItems = array_keys($arProduct);
					if (is_array($arRecalculated['BASKET_ITEMS']))
					{
						$arKeptItems = array();
						foreach ($arRecalculated['BASKET_ITEMS'] as $row)
							$arKeptItems[] = intval($row['TABLE_ROW_ID']);
						$arRemoveItems = array_values(array_diff($arRemoveItems, $arKeptItems));
						unset($arKeptItems, $row);
					}
				}

				// response
				$arResponse = array(
					'REMOVE_ITEMS' => $arRemoveItems,
					'TAX_VALUE' => isset($arRecalculated['TAX_VALUE']) ? $arRecalculated['TAX_VALUE'] : 0.00,
					'PRICE' => isset($arRecalculated['PRICE']) ? $arRecalculated['PRICE'] : 0.00,
				);
				$totalDiscount = 0.0;
				foreach($arProduct as $row)
				{
					if (isset($row['DISCOUNT_PRICE']))
						$totalDiscount += $row['DISCOUNT_PRICE'];
				}
				unset($row);
				$totalSum = isset($arRecalculated['PRICE']) ? round(doubleval($arRecalculated['PRICE']), 2) : 1.0;
				$totalTax = isset($arRecalculated['TAX_VALUE']) ? round(doubleval($arRecalculated['TAX_VALUE']), 2) : 0.0;
				$totalBeforeTax = round($totalSum - $totalTax, 2);
				$totalBeforeDiscount = round($totalBeforeTax + $totalDiscount, 2);
				$arResponse['TOTALS'] = array(
					'TOTAL_SUM' => $totalSum,
					'TOTAL_TAX' => $totalTax,
					'TOTAL_BEFORE_TAX' => $totalBeforeTax,
					'TOTAL_BEFORE_DISCOUNT' => $totalBeforeDiscount,
					'TOTAL_DISCOUNT' => $totalDiscount
				);
				unset($arRemoveItems, $totalSum, $totalTax, $totalBeforeTax, $totalBeforeDiscount, $totalDiscount);

				$arResponseTaxList = array();
				if ($bVatMode)
				{
					// gather vat rates
					$arVatRates = array();
					if (is_array($arRecalculated['BASKET_ITEMS']))
					{
						$basketItems = &$arRecalculated['BASKET_ITEMS'];
						foreach ($basketItems as $row)
							$arVatRates[$row['TABLE_ROW_ID']] = $row['VAT_RATE'];
						unset($basketItems, $row);
					}
					if (count($arVatRates) > 0)
						$arResponse['VAT_RATES'] = $arVatRates;
					unset($arVatRates);

					// tax list
					$arResponseTaxList[] = array(
						'TAX_NAME' => GetMessage('CRM_PRODUCT_TOTAL_TAX'),
						'TAX_VALUE' => CCrmCurrency::MoneyToString(
							isset($arRecalculated['TAX_VALUE']) ? $arRecalculated['TAX_VALUE'] : 0.00, $currencyID
						)
					);
				}
				else
				{
					// gather tax values
					$arTaxList = array();
					if (is_array($arRecalculated['TAX_LIST']))
					{
						$arTaxes = &$arRecalculated['TAX_LIST'];
						foreach ($arTaxes as $row)
							$arTaxList[] = array(
								'IS_IN_PRICE' => $row['~IS_IN_PRICE'],
								'TAX_NAME' => $row['~NAME'],
								'IS_PERCENT' => $row['~IS_PERCENT'],
								'VALUE' => $row['~VALUE'],
								'VALUE_MONEY' => $row['VALUE_MONEY']
							);
						unset($arTaxes, $row);
					}
					if (count($arTaxList) > 0)
					{
						$arResponse['TAX_VALUE'] = 0.00;
						foreach ($arTaxList as $taxInfo)
						{
							$arResponseTaxList[] = array(
								'TAX_NAME' => sprintf(
									"%s%s%s",
									($taxInfo["IS_IN_PRICE"] == "Y") ? GetMessage('CRM_PRODUCT_TAX_INCLUDING')." " : "",
									$taxInfo["TAX_NAME"],
									(/*$vat <= 0 &&*/ $taxInfo["IS_PERCENT"] == "Y")
										? sprintf(' (%s%%)', roundEx($taxInfo["VALUE"], SALE_VALUE_PRECISION))
										: ""
								),
								'TAX_VALUE' => CCrmCurrency::MoneyToString(
									$taxInfo['VALUE_MONEY'], $currencyID
								)
							);
							$arResponse['TAX_VALUE'] += round(doubleval($taxInfo['VALUE_MONEY']), 2);
						}
					}
					else
					{
						$arResponseTaxList[] = array(
							'TAX_NAME' => GetMessage('CRM_PRODUCT_TOTAL_TAX'/*($bVatMode) ? 'CRM_PRODUCT_VAT_VALUE' : 'CRM_PRODUCT_TAX_VALUE'*/),
							'TAX_VALUE' => CCrmCurrency::MoneyToString(0.0, $currencyID)
						);
					}
					unset($arTaxList);
				}
				$arResponse['TAX_LIST'] = $arResponseTaxList;
				$arResponse['VAT_MODE'] = $bVatMode;
				unset($arResponseTaxList);

				if ($bMakePayerInfo && $personTypeId > 0)
				{
					$arResponse['PAYER_INFO_TEXT'] = $strPayerInfo;
					$arResponse['INVOICE_PROPS_HTML_INPUTS'] = $invoicePropsHtmlInputs;

					// pay system
					$paySystemValue = intval($arFields['PAY_SYSTEM_ID']);
					$arPaySystemValues = array_keys($arPaySystemsListItems);
					if (!in_array($paySystemValue, $arPaySystemValues))
					{
						if (count($arPaySystemValues) === 0)
							$paySystemValue = 0;
						else
							$paySystemValue = $arPaySystemValues[0];
					}
					$arPaySystemsListData = array();
					foreach ($arPaySystemsListItems as $k => $v)
						$arPaySystemsListData[] = array('value' => $k, 'text' => $v);
					$arResponse['PAY_SYSTEMS_LIST'] = array(
						'items' => $arPaySystemsListData,
						'value' => $paySystemValue
					);
					unset($paySystemValue, $arPaySystemValues, $arPaySystemsListData);
				}

				$GLOBALS['APPLICATION']->RestartBuffer();
				?>
				<script type="text/javascript">
					var response = null;
					response = <?=CUtil::PhpToJSObject($arResponse)?>;
					top.<?=CUtil::JSEscape($arResult['FORM_ID'].'_ajax_response')?> = response;
				</script>
				<?php
				exit;
			}
			
			if (empty($arResult['ERROR_MESSAGE']))
			{
				$DB->StartTransaction();

				$bSuccess = false;
				if ($bEdit)
				{
					$bSuccess = $CCrmInvoice->Update($arResult['ELEMENT']['ID'], $arFields, array('REGISTER_SONET_EVENT' => true, 'UPDATE_SEARCH' => true));
				}
				else
				{
					$recalculate = false;
					$ID = $CCrmInvoice->Add($arFields, $recalculate, SITE_ID, array('REGISTER_SONET_EVENT' => true, 'UPDATE_SEARCH' => true));
					$bSuccess = (intval($ID) > 0) ? true : false;
					if($bSuccess)
					{
						$arResult['ELEMENT']['ID'] = $ID;
					}
				}

				if ($bSuccess)
				{
					// Save settings
					if(is_array($productRowSettings) && count($productRowSettings) > 0)
					{
						$arSettings = CCrmProductRow::LoadSettings('I', $arResult['ELEMENT']['ID']);
						foreach ($productRowSettings as $k => $v)
							$arSettings[$k] = $v;
						CCrmProductRow::SaveSettings('I', $arResult['ELEMENT']['ID'], $arSettings);
					}
					unset($arSettings);
				}

				// link contact to company
				if($bSuccess)
				{
					if($arFields['UF_CONTACT_ID'] > 0 && $arFields['UF_COMPANY_ID'] > 0)
					{
						$CrmContact = new CCrmContact();
						$dbRes = CCrmContact::GetList(array(), array('ID' => $arFields['UF_CONTACT_ID']), array('COMPANY_ID'));
						$arContactInfo = $dbRes->Fetch();
						if ($arContactInfo && intval($arContactInfo['COMPANY_ID']) <= 0)
						{
							$arContactFields = array(
								'COMPANY_ID' => $arFields['UF_COMPANY_ID']
							);

							$bSuccess = $CrmContact->Update(
								$arFields['UF_CONTACT_ID'],
								$arContactFields,
								false,
								true,
								array('DISABLE_USER_FIELD_CHECK' => true)
							);

							if(!$bSuccess)
							{
								$arResult['ERROR_MESSAGE'] = !empty($arFields['RESULT_MESSAGE']) ? $arFields['RESULT_MESSAGE'] : GetMessage('UNKNOWN_ERROR');
							}
						}
						unset($arContactInfo, $dbRes, $CrmContact);
					}
				}

				if($bSuccess)
				{
					$DB->Commit();
				}
				else
				{
					$DB->Rollback();

					$errCode = 0;
					$errMsg = '';
					$ex = $APPLICATION->GetException();
					if ($ex)
					{
						$errCode = $ex->GetID();
						$APPLICATION->ResetException();
						if (!empty($errCode))
							$errMsg = GetMessage('CRM_ERR_SAVE_INVOICE_'.$errCode);
						if ($errMsg == '')
							$errMsg = $ex->GetString();
					}
					$arResult['ERROR_MESSAGE'] = (!empty($errMsg) ? $errMsg : GetMessage('UNKNOWN_ERROR'))."<br />\n";
					unset($errCode, $errMsg);
				}
			}

			$ID = isset($arResult['ELEMENT']['ID']) ? $arResult['ELEMENT']['ID'] : 0;

			if (!empty($arResult['ERROR_MESSAGE']))
			{
				ShowError($arResult['ERROR_MESSAGE']);
				$arResult['ELEMENT'] = CCrmComponentHelper::PrepareEntityFields(
					array_merge(array('ID' => $ID), $arFields),
					array(
						'ORDER_TOPIC' => array('TYPE' => 'string'),
						'STATUS_ID' => array('TYPE' => 'int'),
						'DATE_INSERT' => array('TYPE' => 'datetime'),
						'DATE_BILL' => array('TYPE' => 'date'),
						'DATE_PAY_BEFORE' => array('TYPE' => 'date'),
						'RESPONSIBLE_ID' => array('TYPE' => 'int'),
						'COMMENTS' => array('TYPE' => 'string'),
						'USER_DESCRIPTION' => array('TYPE' => 'string'),
						'ACCOUNT_NUMBER' => array('TYPE' => 'string'),
						'UF_QUOTE_ID' => array('TYPE' => 'int'),
						'UF_DEAL_ID' => array('TYPE' => 'int'),
						'UF_COMPANY_ID' => array('TYPE' => 'int'),
						'UF_CONTACT_ID' => array('TYPE' => 'int'),
						'PAY_VOUCHER_NUM' => array('TYPE' => 'string'),
						'PAY_VOUCHER_DATE' => array('TYPE' => 'datetime'),
						'REASON_MARKED' => array('TYPE' => 'string'),
						'DATE_MARKED' => array('TYPE' => 'datetime')
					)
				);
			}
			else
			{
				if (isset($_POST['apply']))
				{
					if (CCrmInvoice::CheckUpdatePermission($ID))
					{
						LocalRedirect(
							CComponentEngine::MakePathFromTemplate(
								$arParams['PATH_TO_INVOICE_EDIT'],
								array('invoice_id' => $ID)
							)
						);
					}
				}
				elseif (isset($_POST['saveAndAdd']))
				{
					LocalRedirect(
						CComponentEngine::MakePathFromTemplate(
							$arParams['PATH_TO_INVOICE_EDIT'],
							array('invoice_id' => 0)
						)
					);
				}
				elseif (isset($_POST['saveAndView']))
				{
					if(CCrmInvoice::CheckReadPermission($ID))
					{
						LocalRedirect(
							empty($arResult['INVOICE_REFERER']) ?
								CComponentEngine::MakePathFromTemplate(
									$arParams['PATH_TO_INVOICE_SHOW'],
									array('invoice_id' => $ID)
								)
								:
								$arResult['INVOICE_REFERER']
						);
					}
				}

				// save
				LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_INVOICE_LIST'], array()));
			}
		}
	}
	elseif (isset($_GET['delete']) && check_bitrix_sessid())
	{
		if ($bEdit)
		{
			$arResult['ERROR_MESSAGE'] = '';
			if (!$CCrmInvoice->cPerms->CheckEnityAccess('INVOICE', 'DELETE', $arEntityAttr[$arParams['ELEMENT_ID']]))
				$arResult['ERROR_MESSAGE'] .= GetMessage('CRM_PERMISSION_DENIED').'<br />';
			if (empty($arResult['ERROR_MESSAGE']) && !$CCrmInvoice->Delete($arResult['ELEMENT']['ID']))
				$arResult['ERROR_MESSAGE'] = GetMessage('CRM_DELETE_ERROR');
			if (empty($arResult['ERROR_MESSAGE']))
				LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_INVOICE_LIST']));
			else
				ShowError($arResult['ERROR_MESSAGE']);
			return;
		}
		else
		{
			ShowError(GetMessage('CRM_DELETE_ERROR'));
			return;
		}
	}
}

//$bStatusSuccess = CCrmStatusInvoice::isStatusSuccess($arResult['ELEMENT']['STATUS_ID']);
//$bStatusFailed = CCrmStatusInvoice::isStatusFailed($arResult['ELEMENT']['STATUS_ID']);

$arResult['BACK_URL'] = !empty($arResult['INVOICE_REFERER']) ? $arResult['INVOICE_REFERER'] : $arParams['PATH_TO_INVOICE_LIST'];
$arResult['STATUS_LIST'] = array();
$arResult['~STATUS_LIST'] = CCrmStatus::GetStatusList('INVOICE_STATUS');
foreach ($arResult['~STATUS_LIST'] as $sStatusId => $sStatusTitle)
{
	if ($CCrmInvoice->cPerms->GetPermType('INVOICE', $bEdit ? 'WRITE' : 'ADD', array('STATUS_ID'.$sStatusId)) > BX_CRM_PERM_NONE)
		$arResult['STATUS_LIST'][$sStatusId] = $sStatusTitle;
}
$arResult['CURRENCY_LIST'] = CCrmCurrencyHelper::PrepareListItems();

//$arResult['EVENT_LIST'] = CCrmStatus::GetStatusList('EVENT_TYPE');
$arResult['EDIT'] = $bEdit;

$arResult['FIELDS'] = array();

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_invoice_info',
	'name' => GetMessage('CRM_SECTION_INVOICE_INFO'),
	'type' => 'section'
);


$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'ACCOUNT_NUMBER',
	'name' => GetMessage('CRM_FIELD_ACCOUNT_NUMBER'),
	'params' => array('size' => 100),
	'value' => isset($arResult['ELEMENT']['~ACCOUNT_NUMBER']) ? $arResult['ELEMENT']['~ACCOUNT_NUMBER'] : '',
	'type' => 'text',
	'required' => $bEdit,
	'visible' => $bEdit
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'ORDER_TOPIC',
	'name' => GetMessage('CRM_FIELD_ORDER_TOPIC'),
	'params' => array('size' => 255),
	'value' => isset($arResult['ELEMENT']['~ORDER_TOPIC']) ? $arResult['ELEMENT']['~ORDER_TOPIC'] : '',
	'type' => 'text',
	'required' => true
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'STATUS_ID',
	'name' => GetMessage('CRM_FIELD_STATUS_ID'),
	'items' => $arResult['STATUS_LIST'],
	'params' => array('sale_order_marker' => 'Y'),
	'type' => 'list',
	'value' => (isset($arResult['ELEMENT']['STATUS_ID']) ? $arResult['ELEMENT']['STATUS_ID'] : ''),
	'required' => true
);

// status dependent fields
// <editor-fold defaultstate="collapsed" desc="status dependent fields ...">
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'PAY_VOUCHER_DATE',
	'name' => GetMessage('CRM_FIELD_PAY_VOUCHER_DATE'),
	'params' => array('class' => 'bx-crm-dialog-input bx-crm-dialog-input-date', 'sale_order_marker' => 'Y'),
	'type' => 'date_short',
	'value' => !empty($arResult['ELEMENT']['PAY_VOUCHER_DATE']) ? ConvertTimeStamp(MakeTimeStamp($arResult['ELEMENT']['PAY_VOUCHER_DATE']), 'SHORT', SITE_ID) : '' //ConvertTimeStamp(time()+5*24*3600, 'SHORT', SITE_ID)
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'PAY_VOUCHER_NUM',
	'name' => GetMessage('CRM_FIELD_PAY_VOUCHER_NUM'),
	'params' => array('size' => 20),
	'value' => isset($arResult['ELEMENT']['~PAY_VOUCHER_NUM']) ? $arResult['ELEMENT']['~PAY_VOUCHER_NUM'] : '',
	'type' => 'text'
);

$arResult['ELEMENT']['REASON_MARKED_SUCCESS'] = $arResult['ELEMENT']['~REASON_MARKED_SUCCESS'] = '';
if ($arResult['ELEMENT']['~STATUS_ID'] != '' && CCrmStatusInvoice::isStatusSuccess($arResult['ELEMENT']['~STATUS_ID']))
{
	$arResult['ELEMENT']['~REASON_MARKED_SUCCESS'] = $arResult['ELEMENT']['~REASON_MARKED'];
	$arResult['ELEMENT']['REASON_MARKED_SUCCESS'] = htmlspecialcharsbx($arResult['ELEMENT']['~REASON_MARKED']);
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'REASON_MARKED_SUCCESS',
	'name' => GetMessage('CRM_FIELD_REASON_MARKED_SUCCESS'),
	'value' => isset($arResult['ELEMENT']['~REASON_MARKED_SUCCESS']) ? $arResult['ELEMENT']['~REASON_MARKED_SUCCESS'] : '',
	'type' => 'textarea'
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'DATE_MARKED',
	'name' => GetMessage('CRM_FIELD_DATE_MARKED'),
	'params' => array('class' => 'bx-crm-dialog-input bx-crm-dialog-input-date', 'sale_order_marker' => 'Y'),
	'type' => 'date_short',
	'value' => !empty($arResult['ELEMENT']['DATE_MARKED']) ? ConvertTimeStamp(MakeTimeStamp($arResult['ELEMENT']['DATE_MARKED']), 'SHORT', SITE_ID) : '' //ConvertTimeStamp(time()+5*24*3600, 'SHORT', SITE_ID)
);

if ($arResult['ELEMENT']['~STATUS_ID'] != '' && !CCrmStatusInvoice::isStatusFailed($arResult['ELEMENT']['~STATUS_ID']))
	$arResult['ELEMENT']['REASON_MARKED'] = $arResult['ELEMENT']['~REASON_MARKED'] = '';
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'REASON_MARKED',
	'name' => GetMessage('CRM_FIELD_REASON_MARKED'),
	'value' => isset($arResult['ELEMENT']['~REASON_MARKED']) ? $arResult['ELEMENT']['~REASON_MARKED'] : '',
	'type' => 'textarea'
);
// </editor-fold>

//	if ($bEdit)
//	{
//		$arResult['FIELDS']['tab_1'][] = array(
//			'id' => 'PAYED',
//			'name' => GetMessage('CRM_FIELD_PAYED'),
//			'type' => 'checkbox',
//			'value' => ((isset($arResult['ELEMENT']['PAYED']) && $arResult['ELEMENT']['PAYED'] === 'Y') ? 'Y' : 'N')
//		);
//	}

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'DATE_BILL',
	'name' => GetMessage('CRM_FIELD_DATE_BILL'),
	'params' => array('sale_order_marker' => 'Y'),
	'type' => 'date_link',
	'value' => !empty($arResult['ELEMENT']['DATE_BILL']) ? ConvertTimeStamp(MakeTimeStamp($arResult['ELEMENT']['DATE_BILL']), 'SHORT', SITE_ID) : ConvertTimeStamp(time() + CTimeZone::GetOffset(), 'SHORT', SITE_ID)
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'DATE_PAY_BEFORE',
	'name' => GetMessage('CRM_FIELD_DATE_PAY_BEFORE'),
	'params' => array('class' => 'bx-crm-dialog-input bx-crm-dialog-input-date', 'sale_order_marker' => 'Y'),
	'type' => 'date_short',
	'value' => !empty($arResult['ELEMENT']['DATE_PAY_BEFORE']) ? ConvertTimeStamp(MakeTimeStamp($arResult['ELEMENT']['DATE_PAY_BEFORE']), 'SHORT', SITE_ID) : '' //ConvertTimeStamp(time()+5*24*3600, 'SHORT', SITE_ID)
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'RESPONSIBLE_ID',
	'componentParams' => array(
		'NAME' => 'crm_invoice_edit_resonsible',
		'INPUT_NAME' => 'RESPONSIBLE_ID',
		'SEARCH_INPUT_NAME' => 'RESPONSIBLE_NAME',
		'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
	),
	'name' => GetMessage('CRM_FIELD_RESPONSIBLE_ID'),
	'type' => 'intranet_user_search',
	'value' => isset($arResult['ELEMENT']['RESPONSIBLE_ID']) ? $arResult['ELEMENT']['RESPONSIBLE_ID'] : $USER->GetID()
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'CURRENCY_ID',
	'name' => GetMessage('CRM_FIELD_CURRENCY_ID'),
	'type' => 'label',
	'params' => array('size' => 50),
	'value' => htmlspecialcharsbx(isset($arResult['CURRENCY_LIST'][$currencyID]) ? $arResult['CURRENCY_LIST'][$currencyID] : $currencyID)
);

// DEAL LINK
if (CCrmDeal::CheckReadPermission())
{
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'UF_DEAL_ID',
		'name' => GetMessage('CRM_FIELD_UF_DEAL_ID'),
		'type' => 'crm_entity_selector',
		'componentParams' => array(
			'ENTITY_TYPE' => 'DEAL',
			'INPUT_NAME' => 'UF_DEAL_ID',
			'NEW_INPUT_NAME' => '',
			'INPUT_VALUE' => isset($arResult['ELEMENT']['UF_DEAL_ID']) ? $arResult['ELEMENT']['UF_DEAL_ID'] : '',
			'FORM_NAME' => $arResult['FORM_ID'],
			'MULTIPLE' => 'N',
			'NAME_TEMPLATE' => \Bitrix\Crm\Format\PersonNameFormatter::getFormat()
		)
	);
}

// QUOTE LINK
if (CCrmQuote::CheckReadPermission())
{
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'UF_QUOTE_ID',
		'name' => GetMessage('CRM_FIELD_UF_QUOTE_ID'),
		'type' => 'crm_entity_selector',
		'componentParams' => array(
			'ENTITY_TYPE' => 'QUOTE',
			'INPUT_NAME' => 'UF_QUOTE_ID',
			'NEW_INPUT_NAME' => '',
			'INPUT_VALUE' => isset($arResult['ELEMENT']['UF_QUOTE_ID']) ? $arResult['ELEMENT']['UF_QUOTE_ID'] : '',
			'FORM_NAME' => $arResult['FORM_ID'],
			'MULTIPLE' => 'N',
			'NAME_TEMPLATE' => \Bitrix\Crm\Format\PersonNameFormatter::getFormat()
		)
	);
}

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_invoice_payer',
	'name' => GetMessage('CRM_SECTION_INVOICE_PAYER'),
	'type' => 'section'
);

// CLIENT
if (CCrmContact::CheckReadPermission() && CCrmCompany::CheckReadPermission())
{
	$clientValue = '';
	if (intval($arResult['ELEMENT']['UF_COMPANY_ID']) > 0)
		$clientValue = 'CO_'.intval($arResult['ELEMENT']['UF_COMPANY_ID']);
	else if (intval($arResult['ELEMENT']['UF_CONTACT_ID']) > 0)
		$clientValue = 'C_'.intval($arResult['ELEMENT']['UF_CONTACT_ID']);
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'CLIENT_ID',
		'name' => GetMessage('CRM_FIELD_CLIENT_ID'),
		'type' => 'crm_client_selector',
		'componentParams' => array(
			'ENTITY_TYPE' => array('company', 'contact'),
			'INPUT_NAME' => 'CLIENT_ID',
			'NEW_INPUT_NAME' => 'CLIENT_ID_NEW',
			'INPUT_VALUE' => $clientValue,
			'FORM_ID' => $arResult['FORM_ID'],
			'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
		),
		'required' => true
	);
	unset($clientValue);
	$arResult['CLIENT_CREATE_ENTITIES_CONTAINER_ID'] = "{$arResult['FORM_ID']}_CREATE_ENTITIES_CLIENT_ID";
}

// CONTACT PERSON
if (CCrmContact::CheckReadPermission())
{
	$contactPersonValue = '';
	if (intval($arResult['ELEMENT']['UF_COMPANY_ID']) > 0)
		$contactPersonValue = intval($arResult['ELEMENT']['UF_CONTACT_ID']);
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'UF_CONTACT_ID',
		'name' => GetMessage('CRM_FIELD_CONTACT_PERSON_ID'),
		'type' => 'crm_entity_selector',
		'componentParams' => array(
			'ENTITY_TYPE' => 'CONTACT',
			'INPUT_NAME' => 'UF_CONTACT_ID',
			'NEW_INPUT_NAME' => 'UF_CONTACT_ID_NEW',
			'INPUT_VALUE' => $contactPersonValue,
			'FORM_ID' => $arResult['FORM_ID'],
			'MULTIPLE' => 'N',
			'NAME_TEMPLATE' => \Bitrix\Crm\Format\PersonNameFormatter::getFormat()
		),
		'persistent' => true
	);
	$arResult['CONTACT_PERSON_CONTAINER_ID'] = "{$arResult['FORM_ID']}_FIELD_CONTAINER_UF_CONTACT_ID";
	$arResult['CONTACT_PERSON_ENTITY_EDITOR_ID'] = "{$arResult['FORM_ID']}_UF_CONTACT_ID";
	unset($contactPersonValue);
}

if ($bTaxMode)
{
	// PAYER LOCATION
	$sLocationHtml = '';

	CModule::IncludeModule('sale');
	$locValue = isset($arResult['ELEMENT']['PR_LOCATION']) ? $arResult['ELEMENT']['PR_LOCATION'] : '';

	ob_start();

	CSaleLocation::proxySaleAjaxLocationsComponent(
		array(
			'AJAX_CALL' => 'N',
			'COUNTRY_INPUT_NAME' => 'LOC_COUNTRY',
			'REGION_INPUT_NAME' => 'LOC_REGION',
			'CITY_INPUT_NAME' => 'LOC_CITY',
			'CITY_OUT_LOCATION' => 'Y',
			'LOCATION_VALUE' => $locValue,
			'ORDER_PROPS_ID' => $arInvoiceProperties['FIELDS']['ID'],
			'ONCITYCHANGE' => 'BX.onCustomEvent(\'CrmProductRowSetLocation\', [\'LOC_CITY\']);',
			'SHOW_QUICK_CHOOSE' => 'N'/*,
			'SIZE1' => $arProperties['SIZE1']*/
		),
		array(
			"CODE" => "",
			"ID" => $locValue,
			"PROVIDE_LINK_BY" => "id",
			"JS_CALLBACK" => 'CrmProductRowSetLocation'
		),
		'popup',
		true,
		'locationpro-selector-wrapper'
	);

	$sLocationHtml = ob_get_contents();
	ob_end_clean();
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'LOCATION_ID',
		'name' => GetMessage('CRM_FIELD_LOCATION'),
		'type' => 'custom',
		'value' =>  $sLocationHtml.
			'<div>'.
				'<span class="bx-crm-edit-content-location-description">'.
				GetMessage('CRM_FIELD_LOCATION_DESCRIPTION').
				'</span>'.
			'</div>',
		'required' => true
	);
}

// Rewrite payer information (invoice properties) from contact or company
$companyId = intval($arResult['ELEMENT']['UF_COMPANY_ID']);
$contactId = intval($arResult['ELEMENT']['UF_CONTACT_ID']);
if (!$bEdit && !$bCopy && !$bCreateFromQuote && empty($arResult['ERROR_MESSAGE']))
	CCrmInvoice::__RewritePayerInfo($companyId, $contactId, $arInvoiceProperties);
if ($bCreateFromQuote && empty($arResult['ERROR_MESSAGE']))
{
	// Rewrite payer information from quote fields
	if ($companyId > 0)
	{
		foreach ($arInvoiceProperties as $propertyKey => $property)
		{
			if ($property['FIELDS']['PERSON_TYPE_ID'] == $personTypeId)
			{
				switch($property['FIELDS']['CODE'])
				{
					case 'COMPANY':
					case 'COMPANY_NAME':    // ua company name hack
						if (isset($arQuoteClientFields['CLIENT_TITLE']))
							$arInvoiceProperties[$propertyKey]['VALUE'] = $arQuoteClientFields['CLIENT_TITLE'];
						break;
					case 'CONTACT_PERSON':
						if (isset($arQuoteClientFields['CLIENT_CONTACT']))
							$arInvoiceProperties[$propertyKey]['VALUE'] = $arQuoteClientFields['CLIENT_CONTACT'];
						break;
					case 'COMPANY_ADR':
						if (isset($arQuoteClientFields['CLIENT_ADDR']))
							$arInvoiceProperties[$propertyKey]['VALUE'] = $arQuoteClientFields['CLIENT_ADDR'];
						break;
					case 'INN':
						if (isset($arQuoteClientFields['CLIENT_TP_ID']))
							$arInvoiceProperties[$propertyKey]['VALUE'] = $arQuoteClientFields['CLIENT_TP_ID'];
						break;
					case 'KPP':
						if (isset($arQuoteClientFields['CLIENT_TPA_ID']))
							$arInvoiceProperties[$propertyKey]['VALUE'] = $arQuoteClientFields['CLIENT_TPA_ID'];
						break;
					case 'EMAIL':
						if (isset($arQuoteClientFields['CLIENT_EMAIL']))
							$arInvoiceProperties[$propertyKey]['VALUE'] = $arQuoteClientFields['CLIENT_EMAIL'];
						break;
					case 'PHONE':
						if (isset($arQuoteClientFields['CLIENT_PHONE']))
							$arInvoiceProperties[$propertyKey]['VALUE'] = $arQuoteClientFields['CLIENT_PHONE'];
						break;
				}
			}
		}
	}
	else
	{
		foreach ($arInvoiceProperties as $propertyKey => $property)
		{
			if ($property['FIELDS']['PERSON_TYPE_ID'] == $personTypeId)
			{
				switch($property['FIELDS']['CODE'])
				{
					case 'FIO':
						if (isset($arQuoteClientFields['CLIENT_TITLE']))
							$arInvoiceProperties[$propertyKey]['VALUE'] = $arQuoteClientFields['CLIENT_TITLE'];
						break;
					case 'EMAIL':
						if (isset($arQuoteClientFields['CLIENT_EMAIL']))
							$arInvoiceProperties[$propertyKey]['VALUE'] = $arQuoteClientFields['CLIENT_EMAIL'];
						break;
					case 'PHONE':
						if (isset($arQuoteClientFields['CLIENT_PHONE']))
							$arInvoiceProperties[$propertyKey]['VALUE'] = $arQuoteClientFields['CLIENT_PHONE'];
						break;
					case 'ADDRESS':
						if (isset($arQuoteClientFields['CLIENT_ADDR']))
							$arInvoiceProperties[$propertyKey]['VALUE'] = $arQuoteClientFields['CLIENT_ADDR'];
						break;
				}
			}
		}
	}
}
unset($arQuoteClientFields);
$htmlInputs = CCrmInvoice::__MakePropsHtmlInputs($arInvoiceProperties);
// PAYER PROPERTIES
//foreach ($arInvoiceProperties as $propertyId => $property)
//{
//	if ($property['FIELDS']['CODE'] !== 'LOCATION')
//	{
//		$arResult['FIELDS']['tab_1'][] = array(
//			'id' => $propertyId,
//			'name' => $property['FIELDS']['NAME'],
//			'type' => ToLower($property['FIELDS']['TYPE']),
//			'value' => $property['VALUE']
//		);
//	}
//}

// ----------- Payer info dialog settings ----------->
$dlgSettings = array('personTypeId' => $personTypeId);
$arInvoicePropertiesInfo = CCrmInvoice::GetPropertiesInfo(0, true);
$index = 0;
foreach ($arInvoicePropertiesInfo as $person => $props)
{
	$index = 0;
	foreach ($props as $code => $fields)
	{
		if ($fields['TYPE'] === 'TEXT' || $fields['TYPE'] === 'TEXTAREA')
			$dlgSettings[$person][$index++] = array(
				'ID' => $fields['ID'],
				'CODE' => $fields['CODE'],
				'TYPE' => $fields['TYPE'],
				'NAME' => $fields['NAME'],
				'SIZE1' => $fields['SIZE1'],
				'SIZE2' => $fields['SIZE2'],
				'SORT' => $fields['SORT'],
				'PROPS_GROUP_ID' => $fields['PROPS_GROUP_ID'],
				'GROUP_NAME' => $fields['GROUP_NAME'],
				'GROUP_SORT' => $fields['GROUP_SORT']//,
				//'VALUE' => (intval($person) === intval($personTypeId)) ? $arInvoiceProperties['PR_INVOICE_'.$fields['ID']]['VALUE'] : ""
			);
	}
}
$dlgSettings['personTypes'] = $arPersonTypes;
$dlgSettings['FORM_ID'] = $arResult['FORM_ID'];
$dlgSettings['PAYER_INFO_EDIT_LINK_ID'] = $arResult['PAYER_INFO_EDIT_LINK_ID'];
$dlgSettings['INVOICE_PROPS_DIV_ID'] = $arResult['INVOICE_PROPS_DIV_ID'];
$dlgSettings['PAYER_INFO_FIELD_ID'] = $arResult['PAYER_INFO_FIELD_ID'];
$dlgSettings['messages'] = array(
	'TITLE' => GetMessage('CRM_INVOICE_PROPS_DLG_TITLE'),
	'SAVE' => GetMessage('CRM_INVOICE_PROPS_DLG_SAVE'),
	'CANCEL' => GetMessage('CRM_INVOICE_PROPS_DLG_CANCEL')
);
$arResult['INVOICE_PROPS_DLG_SETTINGS'] = $dlgSettings;
unset($arInvoicePropertiesInfo, $dlgSettings, $person, $props, $code, $v, $index);
// <----------- Payer info dialog settings -----------

$strPayerInfo = CCrmInvoice::__MakePayerInfoString($arInvoiceProperties);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => $payerInfoFieldId,
	'name' => GetMessage('CRM_FIELD_PAYER_INFO'),
	'type' => 'custom',
	'value' =>  '<div id="'.$payerInfoFieldId.'" class="bx-crm-edit-crm-entity-field">'.htmlspecialcharsbx($strPayerInfo).'</div>'.PHP_EOL.
				'<div id="'.$invoicePropsDivId.'" style="display: none;">'.$htmlInputs.'</div>'.PHP_EOL.
				'<div><span id="'.$arResult['PAYER_INFO_EDIT_LINK_ID'].'" class="bx-crm-edit-content-payer-info-edit-link">'.GetMessage('CRM_INVOICE_EDIT_PAYER_INFO').'</span></div>',
	'persistent' => true
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_pay_system',
	'name' => GetMessage('CRM_SECTION_PAY_SYSTEM'),
	'type' => 'section'
);

// pay system
$arResult['PAY_SYSTEM_ID_TITLE'] = GetMessage('CRM_FIELD_PAY_SYSTEM_ID_TITLE');
$arResult['FIELDS']['tab_1'][] = array(
	'id' => $paySystemFieldId,
	'name' => GetMessage('CRM_FIELD_PAY_SYSTEM_ID'),
	'params' => array('id' => 'PAY_SYSTEM_SELECT'),
	'items' => CCrmPaySystem::GetPaySystemsListItems($personTypeId),
	'type' => 'list',
	'value' => (isset($arResult['ELEMENT']['PAY_SYSTEM_ID']) ? $arResult['ELEMENT']['PAY_SYSTEM_ID'] : ''),
	'required' => true
);

// COMMENTS
// <editor-fold defaultstate="collapsed" desc="COMMENTS ...">
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_comments',
	'name' => GetMessage('CRM_SECTION_COMMENTS'),
	'type' => 'section'
);

//	$arResult['FIELDS']['tab_1'][] = array(
//		'id' => 'COMMENTS',
//		'name' => GetMessage('CRM_FIELD_COMMENTS'),
//		'params' => array('size' => 2000),
//		'value' => isset($arResult['ELEMENT']['COMMENTS']) ? $arResult['ELEMENT']['COMMENTS'] : '',
//		'type' => 'textarea'
//	);

ob_start();
$ar = array(
	'inputName' => 'COMMENTS',
	'inputId' => 'COMMENTS',
	'height' => '80',
	'content' => isset($arResult['ELEMENT']['~COMMENTS']) ? $arResult['ELEMENT']['~COMMENTS'] : '',
	'bUseFileDialogs' => false,
	'bFloatingToolbar' => false,
	'bArisingToolbar' => false,
	'bResizable' => true,
	'bSaveOnBlur' => true,
	'toolbarConfig' => array(
		'Bold', 'Italic', 'Underline', 'Strike',
		'BackColor', 'ForeColor',
		'CreateLink', 'DeleteLink',
		'InsertOrderedList', 'InsertUnorderedList', 'Outdent', 'Indent'
	)
);
$LHE = new CLightHTMLEditor;
$LHE->Show($ar);
$sVal = ob_get_contents();
ob_end_clean();
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'COMMENTS',
	'name' => GetMessage('CRM_FIELD_COMMENTS'),
	'required' => false,
	'params' => array(),
	'type' => 'vertical_container',
	'value' => $sVal
);

//	$arResult['FIELDS']['tab_1'][] = array(
//		'id' => 'USER_DESCRIPTION',
//		'name' => GetMessage('CRM_FIELD_USER_DESCRIPTION'),
//		'params' => array('size' => 250),
//		'value' => isset($arResult['ELEMENT']['USER_DESCRIPTION']) ? $arResult['ELEMENT']['USER_DESCRIPTION'] : '',
//		'type' => 'textarea'
//	);

ob_start();
$ar = array(
	'inputName' => 'USER_DESCRIPTION',
	'inputId' => 'USER_DESCRIPTION',
	'height' => '80',
	'content' => isset($arResult['ELEMENT']['~USER_DESCRIPTION']) ? $arResult['ELEMENT']['~USER_DESCRIPTION'] : '',
	'bUseFileDialogs' => false,
	'bFloatingToolbar' => false,
	'bArisingToolbar' => false,
	'bResizable' => true,
	'bSaveOnBlur' => true,
	'toolbarConfig' => array(
		'Bold', 'Italic', 'Underline', 'Strike',
		'BackColor', 'ForeColor',
		'CreateLink', 'DeleteLink',
		'InsertOrderedList', 'InsertUnorderedList', 'Outdent', 'Indent'
	)
);
$LHE = new CLightHTMLEditor;
$LHE->Show($ar);
$sVal = ob_get_contents();
ob_end_clean();
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'USER_DESCRIPTION',
	'name' => GetMessage('CRM_FIELD_USER_DESCRIPTION'),
	'required' => false,
	'params' => array(),
	'type' => 'vertical_container',
	'value' => $sVal
);
// </editor-fold>

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_invoice_spec',
	'name' => GetMessage('CRM_SECTION_PRODUCT_ROWS'),
	'type' => 'section',
	'required' => true
);

// Product rows
$arResult['PRODUCT_ROW_EDITOR_ID'] = ($arParams['ELEMENT_ID'] > 0 ? 'invoice_'.strval($arParams['ELEMENT_ID']) : 'new_invoice').'_product_editor';
$sProductsHtml = '';
$componentSettings = array(
	'ID' => $arResult['PRODUCT_ROW_EDITOR_ID'],
	'FORM_ID' => $arResult['FORM_ID'],
	'OWNER_ID' => $arParams['ELEMENT_ID'],
	'OWNER_TYPE' => 'I',
	'PERMISSION_TYPE' => $isExternal ? 'READ' : 'WRITE',
	'INIT_EDITABLE' => 'Y',
	'HIDE_MODE_BUTTON' => 'Y',
	'CURRENCY_ID' => $currencyID,
	'PERSON_TYPE_ID' => $personTypeId,
	'LOCATION_ID' => $bTaxMode ? $arResult['ELEMENT']['PR_LOCATION'] : '',
	'PRODUCT_ROWS' => isset($arResult['PRODUCT_ROWS']) ? $arResult['PRODUCT_ROWS'] : null,
	'PRODUCT_DATA_FIELD_NAME' => $productDataFieldName,
	'TOTAL_SUM' => isset($arResult['ELEMENT']['~PRICE']) ? $arResult['ELEMENT']['~PRICE'] : null,
	'TOTAL_TAX' => isset($arResult['ELEMENT']['~TAX_VALUE']) ? $arResult['ELEMENT']['~TAX_VALUE'] : null,
	'PATH_TO_PRODUCT_EDIT' => $arParams['PATH_TO_PRODUCT_EDIT'],
	'PATH_TO_PRODUCT_SHOW' => $arParams['PATH_TO_PRODUCT_SHOW'],
	'COPY_FLAG' => ($bCopy || $bCreateFromQuote || $bCreateFromDeal) ? 'Y' : 'N',
);
if (is_array($productRowSettings) && count($productRowSettings) > 0)
{
	if (isset($productRowSettings['ENABLE_DISCOUNT']))
		$componentSettings['ENABLE_DISCOUNT'] = $productRowSettings['ENABLE_DISCOUNT'] ? 'Y' : 'N';
	if (isset($productRowSettings['ENABLE_TAX']))
		$componentSettings['ENABLE_TAX'] = $productRowSettings['ENABLE_TAX'] ? 'Y' : 'N';
}
ob_start();
$APPLICATION->IncludeComponent('bitrix:crm.product_row.list',
	'',
	$componentSettings,
	false,
	array('HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT'=>'Y')
);
$sProductsHtml .= ob_get_contents();
ob_end_clean();
unset($componentSettings);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'INVOICE_PRODUCT_ROWS',
	'name' => GetMessage('CRM_FIELD_PRODUCT_ROWS'),
	'colspan' => true,
	'type' => 'custom',
	'value' => $sProductsHtml
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_additional',
	'name' => GetMessage('CRM_SECTION_ADDITIONAL'),
	'type' => 'section'
);

$icnt = count($arResult['FIELDS']['tab_1']);

$CCrmUserType->AddFields(
	$arResult['FIELDS']['tab_1'],
	$arResult['ELEMENT']['ID'],
	$arResult['FORM_ID'],
	$bConvert ? (isset($arParams['~VARS_FROM_FORM']) && $arParams['~VARS_FROM_FORM'] === true) : $bVarsFromForm,
	false,
	false,
	array(
		'FILE_URL_TEMPLATE' =>
			"/bitrix/components/bitrix/crm.invoice.show/show_file.php?ownerId=#owner_id#&fieldName=#field_name#&fileId=#file_id#"
	)
);

if (count($arResult['FIELDS']['tab_1']) == $icnt)
	unset($arResult['FIELDS']['tab_1'][$icnt - 1]);

if ($bCopy)
{
	$arParams['ELEMENT_ID'] = 0;
	$arFields['ID'] = 0;
	$arResult['ELEMENT']['ID'] = 0;
}

$this->IncludeComponentTemplate();

include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.invoice/include/nav.php');
?>
