<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

$entityID = $arParams['ENTITY_ID'] = isset($arParams['ENTITY_ID']) ? intval($arParams['ENTITY_ID']) : 0;
if($entityID < 0)
{
	$entityID = 0;
}

if($entityID === 0 && isset($_REQUEST['invoice_id']))
{
	$entityID = $arParams['ENTITY_ID'] = intval($_REQUEST['invoice_id']);
}
$arResult['ENTITY_ID'] = $entityID;

$userPerms = CCrmPerms::GetCurrentUserPermissions();
if (!($entityID !== 0
	? CCrmAuthorizationHelper::CheckUpdatePermission(CCrmOwnerType::InvoiceName, $entityID, $userPerms)
	: CCrmAuthorizationHelper::CheckCreatePermission(CCrmOwnerType::InvoiceName, $userPerms)))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

global $APPLICATION;

$arParams['INVOICE_SHOW_URL_TEMPLATE'] =  isset($arParams['INVOICE_SHOW_URL_TEMPLATE']) ? $arParams['INVOICE_SHOW_URL_TEMPLATE'] : '';
$arParams['INVOICE_EDIT_URL_TEMPLATE'] =  isset($arParams['INVOICE_EDIT_URL_TEMPLATE']) ? $arParams['INVOICE_EDIT_URL_TEMPLATE'] : '';
//$arParams['CURRENCY_SELECTOR_URL_TEMPLATE'] = isset($arParams['CURRENCY_SELECTOR_URL_TEMPLATE']) ? $arParams['CURRENCY_SELECTOR_URL_TEMPLATE'] : '';
$arParams['REQUISITE_EDIT_URL_TEMPLATE'] = isset($arParams['REQUISITE_EDIT_URL_TEMPLATE']) ? $arParams['REQUISITE_EDIT_URL_TEMPLATE'] : '';
$arParams['PRODUCT_ROW_EDIT_URL_TEMPLATE'] = isset($arParams['PRODUCT_ROW_EDIT_URL_TEMPLATE']) ? $arParams['PRODUCT_ROW_EDIT_URL_TEMPLATE'] : '';
$arParams['PRODUCT_SELECTOR_URL_TEMPLATE'] = isset($arParams['PRODUCT_SELECTOR_URL_TEMPLATE']) ? $arParams['PRODUCT_SELECTOR_URL_TEMPLATE'] : '';
$arParams['CLIENT_SELECTOR_URL_TEMPLATE'] = isset($arParams['CLIENT_SELECTOR_URL_TEMPLATE']) ? $arParams['CLIENT_SELECTOR_URL_TEMPLATE'] : '';
$arParams['DEAL_SELECTOR_URL_TEMPLATE'] = isset($arParams['DEAL_SELECTOR_URL_TEMPLATE']) ? $arParams['DEAL_SELECTOR_URL_TEMPLATE'] : '';
$arParams['INVOICE_STATUS_SELECTOR_URL_TEMPLATE'] = isset($arParams['INVOICE_STATUS_SELECTOR_URL_TEMPLATE']) ? $arParams['INVOICE_STATUS_SELECTOR_URL_TEMPLATE'] : '';
$arParams['PAY_SYSTEM_SELECTOR_URL_TEMPLATE'] = isset($arParams['PAY_SYSTEM_SELECTOR_URL_TEMPLATE']) ? $arParams['PAY_SYSTEM_SELECTOR_URL_TEMPLATE'] : '';
$arParams['LOCATION_SELECTOR_URL_TEMPLATE'] = isset($arParams['LOCATION_SELECTOR_URL_TEMPLATE']) ? $arParams['LOCATION_SELECTOR_URL_TEMPLATE'] : '';
$arParams['USER_PROFILE_URL_TEMPLATE'] = isset($arParams['USER_PROFILE_URL_TEMPLATE']) ? $arParams['USER_PROFILE_URL_TEMPLATE'] : '';
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array('#NOBR#','#/NOBR#'), array('', ''), $arParams['NAME_TEMPLATE']);

$uid = isset($arParams['UID']) ? $arParams['UID'] : '';
if($uid === '')
{
	$uid = 'mobile_crm_invoice_edit';
}
$arResult['UID'] = $arParams['UID'] = $uid;
$userID = $arResult['USER_ID'] = CCrmSecurityHelper::GetCurrentUserID();

// CONTEXT_ID -->
$contextID = isset($arParams['CONTEXT_ID']) ? $arParams['CONTEXT_ID'] : '';
if($contextID === '' && isset($_REQUEST['context_id']))
{
	$contextID = $_REQUEST['context_id'];
}
if($contextID === '')
{
	$contextID = "{$uid}_{$entityID}";
}
$arResult['CONTEXT_ID'] = $arParams['CONTEXT_ID'] = $contextID;
//<-- CONTEXT_ID

$arResult['STATUS_LIST'] = CCrmStatus::GetStatus('INVOICE_STATUS');

$personTypes = $arResult['PERSON_TYPES'] = CCrmPaySystem::getPersonTypeIDs();

// '' - NO TAXES
// 'VAT' - VAT ONLY
// 'EXT' - EXTENDED MODE WITH CUSTOM TAXES
$taxMode = $arResult['TAX_MODE'] = CCrmTax::isVatMode() ? 'VAT' :  (CCrmTax::isTaxMode() ? 'EXT' : '');
$companyID = 0;
$contactID = 0;
$dealID = 0;
$dealCurrencyID = '';
$personTypeID = 0;
$paySystemID = 0;
$properties = array();
$currencyID = CCrmInvoice::GetCurrencyID();

$arFields = null;
if($entityID === 0)
{
	$arResult['MODE'] = 'CREATE';
	$now = time() + CTimeZone::GetOffset();
	$nowDate = ConvertTimeStamp($now, 'SHORT', SITE_ID);
	$arFields = array(
		'~CURRENCY' => $currencyID,
		'CURRENCY' => htmlspecialcharsbx($currencyID)
	);

	$arFields['~STATUS_ID'] = !empty($arResult['STATUS_LIST']) ? array_shift(array_keys($arResult['STATUS_LIST'])) : '';
	$arFields['STATUS_ID'] = htmlspecialcharsbx($arFields['~STATUS_ID']);

	$arFields['DATE_PAY_BEFORE_STAMP'] = 0;
	$arFields['~DATE_PAY_BEFORE'] = $arFields['DATE_PAY_BEFORE'] = '';

	$arFields['DATE_BILL_STAMP'] = $now;
	$arFields['~DATE_BILL'] = $arFields['~DATE_INSERT'] = $nowDate;
	$arFields['DATE_BILL'] = $arFields['DATE_INSERT'] = htmlspecialcharsbx($nowDate);

	if(isset($_REQUEST['deal_id']))
	{
		$dealID = max(intval($_REQUEST['deal_id']), 0);
	}
	if($dealID > 0)
	{
		$arFields['~UF_DEAL_ID'] = $arFields['UF_DEAL_ID'] = $dealID;

		$dbDeal = CCrmDeal::GetListEx(
			array(),
			array('=ID' => $dealID),
			false,
			false,
			array('ID', 'COMPANY_ID', 'CONTACT_ID', 'CURRENCY_ID', 'ASSIGNED_BY_ID', 'TITLE', 'COMMENTS')
		);

		$deal = $dbDeal ? $dbDeal->Fetch() : null;
		if(is_array($deal))
		{
			$companyID = $arFields['~UF_COMPANY_ID'] = $arFields['UF_COMPANY_ID'] = isset($deal['COMPANY_ID']) ? intval($deal['COMPANY_ID']) : 0;
			$contactID = $arFields['~UF_CONTACT_ID'] = $arFields['UF_CONTACT_ID'] = isset($deal['CONTACT_ID']) ? intval($deal['CONTACT_ID']) : 0;
			$dealCurrencyID = isset($deal['CURRENCY_ID']) ? $deal['CURRENCY_ID'] : CCrmCurrency::GetBaseCurrencyID();

			$arFields['~ORDER_TOPIC'] = isset($deal['TITLE']) ? $deal['TITLE'] : '';
			$arFields['ORDER_TOPIC'] = htmlspecialcharsbx($arFields['~ORDER_TOPIC']);

			$arFields['~USER_DESCRIPTION'] = isset($deal['COMMENTS']) ? $deal['COMMENTS'] : '';
			$arFields['USER_DESCRIPTION'] = htmlspecialcharsbx($arFields['~USER_DESCRIPTION']);

			$arFields['~RESPONSIBLE_ID'] = $arFields['RESPONSIBLE_ID'] = isset($deal['ASSIGNED_BY_ID']) ? intval($deal['ASSIGNED_BY_ID']) : 0;
		}
	}
	else
	{
		if(isset($_REQUEST['company_id']))
		{
			$companyID = max(intval($_REQUEST['company_id']), 0);
		}
		if($companyID > 0)
		{
			$arFields['UF_COMPANY_ID'] = $companyID;
		}

		if(isset($_REQUEST['contact_id']))
		{
			$contactID = max(intval($_REQUEST['contact_id']), 0);
		}
		if($contactID > 0)
		{
			$arFields['~UF_CONTACT_ID'] = $arFields['UF_CONTACT_ID'] = $contactID;
		}
	}

	if(!(isset($arFields['~RESPONSIBLE_ID']) && $arFields['~RESPONSIBLE_ID'] > 0))
	{
		$arFields['~RESPONSIBLE_ID'] = $arFields['RESPONSIBLE_ID'] = $userID;
	}

	if($arFields['~RESPONSIBLE_ID'] > 0)
	{
		$dbUser = CUser::GetList(
			($by='id'),
			($order='asc'),
			array('ID'=> $arFields['~RESPONSIBLE_ID']),
			array(
				'FIELDS'=> array(
					'ID',
					'LOGIN',
					'EMAIL',
					'NAME',
					'LAST_NAME',
					'SECOND_NAME'
				)
			)
		);
		$user = $dbUser->Fetch();
		if($user)
		{
			$arFields['~RESPONSIBLE_BY_LOGIN'] = $user['LOGIN'];
			$arFields['RESPONSIBLE_BY_LOGIN'] = htmlspecialcharsbx($user['LOGIN']);

			$arFields['~RESPONSIBLE_NAME'] = $user['NAME'];
			$arFields['RESPONSIBLE_NAME'] = htmlspecialcharsbx($user['NAME']);

			$arFields['~RESPONSIBLE_LAST_NAME'] = $user['LAST_NAME'];
			$arFields['RESPONSIBLE_LAST_NAME'] = htmlspecialcharsbx($user['LAST_NAME']);

			$arFields['~RESPONSIBLE_SECOND_NAME'] = $user['SECOND_NAME'];
			$arFields['RESPONSIBLE_SECOND_NAME'] = htmlspecialcharsbx($user['SECOND_NAME']);
		}
	}
}
else
{
	$arResult['MODE'] = 'UPDATE';
	$dbFields = CCrmInvoice::GetList(
		array(),
		array(
			'ID' => $entityID,
			'PERMISSION' => 'WRITE'
		)
	);

	$arFields = $dbFields->GetNext();
	if(!$arFields)
	{
		ShowError(GetMessage('CRM_INVOICE_EDIT_NOT_FOUND', array('#ID#' => $arParams['ENTITY_ID'])));
		return;
	}

	$arFields['DATE_BILL_STAMP'] = isset($arFields['~DATE_BILL']) ? MakeTimeStamp($arFields['~DATE_BILL']) : 0;
	$arFields['DATE_PAY_BEFORE_STAMP'] = isset($arFields['~DATE_PAY_BEFORE']) ? MakeTimeStamp($arFields['~DATE_PAY_BEFORE']) : 0;

	if(isset($arFields['CURRENCY']))
	{
		$currencyID = $arFields['CURRENCY'];
	}
	//HACK: MSSQL returns '.00' for zero value
	if(isset($arFields['~PRICE']))
	{
		$arFields['~PRICE'] = $arFields['PRICE'] = floatval($arFields['~PRICE']);
	}

	if(isset($arFields['UF_COMPANY_ID']))
	{
		$companyID = intval($arFields['UF_COMPANY_ID']);
	}

	if(isset($arFields['UF_CONTACT_ID']))
	{
		$contactID = intval($arFields['UF_CONTACT_ID']);
	}

	if(isset($arFields['UF_DEAL_ID']))
	{
		$dealID = intval($arFields['UF_DEAL_ID']);
	}
}

$arResult['CURRENCY_ID'] = $currencyID;

// PERSON_TYPE_ID -->
if($companyID > 0 && isset($personTypes['COMPANY']))
{
	$personTypeID = $personTypes['COMPANY'];
}
elseif($contactID > 0 && isset($personTypes['CONTACT']))
{
	$personTypeID = $personTypes['CONTACT'];
}
$arResult['PERSON_TYPE_ID'] = $personTypeID;
$arResult['PAYER_INFO_FORMAT'] = $personTypeID > 0 ? CCrmMobileHelper::PrepareInvoiceClientInfoFormat($personTypeID) : '';
//<-- PERSON_TYPE_ID

// PAY_SYSTEM -->
$arResult['PAY_SYSTEMS'] = CCrmPaySystem::GetPaySystemsListItems($personTypeID);
if($entityID > 0)
{
	$paySystemID = isset($arFields['~PAY_SYSTEM_ID']) ? intval($arFields['~PAY_SYSTEM_ID']) : 0;
}
elseif(!empty($arResult['PAY_SYSTEMS']))
{
	$paySystemID = $arFields['~PAY_SYSTEM_ID'] = $arFields['PAY_SYSTEM_ID'] = array_shift(array_keys($arResult['PAY_SYSTEMS']));
}
//<-- PAY_SYSTEM

// INVOICE_PROPERTIES -->
$arFields['INVOICE_PROPERTIES'] = array();
$properties = CCrmInvoice::GetProperties($entityID, $personTypeID);
if(!is_array($properties))
{
	$properties = array();
}
if($entityID === 0)
{
	CCrmInvoice::__RewritePayerInfo($companyID, $contactID, $properties);
}
$arFields['INVOICE_PROPERTIES'] = $properties;
$arResult['PAYER_REQUISITES'] =  $personTypeID > 0 ? CCrmMobileHelper::PrepareInvoiceClientRequisites($personTypeID, $properties) : array();
//<-- INVOICE_PROPERTIES

CCrmMobileHelper::PrepareInvoiceItem(
	$arFields,
	$arParams,
	array(
		'PAY_SYSTEMS' => $arResult['PAY_SYSTEMS'],
		'INVOICE_PROPERTIES' => $properties
	),
	array('ENABLE_MULTI_FIELDS' => true, 'ENABLE_PAYER_INFO' => true, 'ENABLE_LOCATION' => true)
);

$arResult['ENABLE_LOCATION'] = $taxMode === 'EXT';

// PRODUCT_ROWS, TAX_INFOS, SUM_BRUTTO, SUM_NETTO -->
$arResult['PRODUCT_ROWS'] = array();
if($entityID > 0)
{
	$rows = CCrmInvoice::GetProductRows($entityID);
	foreach($rows as &$row)
	{
		$price = isset($row['PRICE']) ? round(doubleval($row['PRICE']), 2) : 0.0;
		$qty = isset($row['QUANTITY']) ? intval($row['QUANTITY']) : 0;
		$sum = $item['SUM'] = $price * $qty;

		$item = array(
			'ID' => isset($row['ID']) ? intval($row['ID']) : 0,
			'PRODUCT_ID' => isset($row['PRODUCT_ID']) ? intval($row['PRODUCT_ID']) : 0,
			'PRODUCT_NAME' => isset($row['PRODUCT_NAME']) ? $row['PRODUCT_NAME'] : '',
			'PRICE' => $price,
			'QUANTITY' => $qty,
			'SUM' => $sum,
			'VAT_RATE' => isset($row['VAT_RATE']) ? round(doubleval($row['VAT_RATE']) * 100, 2) : 0.0
		);
		$item['FORMATTED_PRICE'] = CCrmCurrency::MoneyToString($price, $currencyID);
		$item['FORMATTED_SUM'] = CCrmCurrency::MoneyToString($sum, $currencyID);
		$item['CURRENCY_ID'] = $currencyID;

		$arResult['PRODUCT_ROWS'][] = &$item;
		unset($item);
	}
	unset($row);

	$taxList = CCrmInvoice::getTaxList($entityID);
	$taxInfo = CCrmMobileHelper::PrepareInvoiceTaxInfo($taxList, true);

	$arResult['TAX_INFOS'] = $taxInfo['ITEMS'];

	$sum = $arFields['~PRICE'];
	$arResult['SUM_BRUTTO'] = $sum;
	$arResult['SUM_NETTO'] = $sum - $taxInfo['SUM_EXCLUDED_FROM_PRICE'];
}
elseif($dealID > 0)
{
	$recalculateData = array(
		'ID' => 0,
		'PAY_SYSTEM_ID' => $paySystemID,
		'PERSON_TYPE_ID' => $personTypeID,
		'INVOICE_PROPERTIES' => array(),
		'PRODUCT_ROWS' => array()
	);

	foreach($properties as $propertyKey => &$propertyData)
	{
		$propertyFields = isset($propertyData['FIELDS']) ? $propertyData['FIELDS'] : null;
		$propertyID = is_array($propertyFields) && isset($propertyFields['ID']) ? $propertyFields['ID'] : '';
		if($propertyID === '')
		{
			continue;
		}
		$recalculateData['INVOICE_PROPERTIES'][$propertyID] = isset($propertyData['VALUE']) ? $propertyData['VALUE'] : '';
	}
	unset($propertyData);

	$rows = CCrmDeal::LoadProductRows($dealID);
	$rowQty = count($rows);
	for($i = 0; $i < $rowQty; $i++)
	{
		$row = $rows[$i];
		$price = isset($row['PRICE']) ? round(doubleval($row['PRICE']), 2) : 0.0;
		if($dealCurrencyID !== $currencyID)
		{
			$price = CCrmCurrency::ConvertMoney($price, $dealCurrencyID, $currencyID);
		}

		$qty = isset($row['QUANTITY']) ? intval($row['QUANTITY']) : 0;
		$sum = $item['SUM'] = $price * $qty;

		$item = array(
			'ID' => 0,
			'PRODUCT_ID' => isset($row['PRODUCT_ID']) ? intval($row['PRODUCT_ID']) : 0,
			'PRODUCT_NAME' => isset($row['PRODUCT_NAME']) ? $row['PRODUCT_NAME'] : '',
			'PRICE' => $price,
			'QUANTITY' => $qty,
			'SUM' => $sum,
			'IDX' => $i // Save initial row's order (can be changed after recalculation)
		);
		$item['FORMATTED_PRICE'] = CCrmCurrency::MoneyToString($price, $currencyID);
		$item['FORMATTED_SUM'] = CCrmCurrency::MoneyToString($sum, $currencyID);
		$item['CURRENCY_ID'] = $currencyID;

		$recalculateData['PRODUCT_ROWS'][] = &$item;
		unset($item);
	}

	$orderData = CCrmInvoice::QuickRecalculate($recalculateData);
	if(empty($orderData))
	{
		$arResult['TAX_INFOS'] = array();
		$arResult['SUM_BRUTTO'] = $arResult['SUM_NETTO'] = 0.0;
	}
	else
	{
		$orderCurrencyID = isset($orderData['CURRENCY']) ? $orderData['CURRENCY'] : '';
		if($orderCurrencyID !== '' && $orderCurrencyID !== $currencyID)
		{
			ShowError('ERROR: CURRENCIES ARE ARE MISMATCHED');
			return;
		}

		$sumBrutto = $arResult['SUM_BRUTTO'] = isset($orderData['PRICE']) ? $orderData['PRICE'] : 0.0;
		$taxSum = isset($orderData['TAX_VALUE']) ? $orderData['TAX_VALUE'] : 0.0;
		$sumNetto = $arResult['SUM_NETTO'] = $sumBrutto - $taxSum;

		if(isset($orderData['USE_VAT']) && $orderData['USE_VAT'] && $taxMode !== 'VAT')
		{
			ShowError('ERROR: TAX MODES ARE ARE MISMATCHED');
			return;
		}

		$taxList = isset($orderData['TAX_LIST']) && is_array($orderData['TAX_LIST']) ? $orderData['TAX_LIST'] : array();
		$taxInfo = CCrmMobileHelper::PrepareInvoiceTaxInfo($taxList, false);
		$arResult['TAX_INFOS'] = $taxInfo['ITEMS'];

		$isVATMode = $taxMode === 'VAT';
		$VATName = $isVATMode && isset($taxList[0]) && isset($taxList[0]['NAME']) ? $taxList[0]['NAME'] : '';

		$cartItems = isset($orderData['BASKET_ITEMS']) && isset($orderData['BASKET_ITEMS']) ? $orderData['BASKET_ITEMS'] : array();
		// Recover initial row's order
		sortByColumn($cartItems, 'IDX');
		foreach($cartItems as &$cartItem)
		{
			$productID = isset($cartItem['PRODUCT_ID']) ? intval($cartItem['PRODUCT_ID']) : 0;
			if($productID <= 0)
			{
				continue;
			}

			$productName = isset($cartItem['NAME']) ? $cartItem['NAME'] : '';
			if($productName === '')
			{
				$dbProduct = CCrmProduct::GetList(array(), array('ID' => $productID), array('NAME'));
				$product = $dbProduct ? $dbProduct->Fetch() : null;
				$productName = is_array($product) && isset($product['NAME']) ? $product['NAME'] : '';
			}

			if($productName === '')
			{
				continue;
			}

			$price = isset($cartItem['PRICE']) ? $cartItem['PRICE'] : 0.0;
			$qty = isset($cartItem['QUANTITY']) ? $cartItem['QUANTITY'] : 0;
			$sum = $price * $qty;

			$row = array(
				'PRODUCT_ID' => $productID,
				'PRODUCT_NAME' => $productName,
				'CURRENCY_ID' => isset($cartItem['CURRENCY']) ? $cartItem['CURRENCY'] : '',
				'PRICE' => $price,
				'QUANTITY' => $qty,
				'SUM' => $sum,
				'FORMATTED_PRICE' => CCrmCurrency::MoneyToString($price, $currencyID),
				'FORMATTED_SUM' => CCrmCurrency::MoneyToString($sum, $currencyID)
			);

			if($isVATMode)
			{
				// Custom processing for VAT mode
				$rate = isset($cartItem['VAT_RATE']) ? round(doubleval($cartItem['VAT_RATE']) * 100, 2) : 0.0;
				if($rate > 0)
				{
					$row['TAX_INFOS'] = array(
						array('NAME' => $VATName, 'RATE'=> $rate, 'FORMATTED_RATE' => "{$rate}%")
					);
				}
			}

			$arResult['PRODUCT_ROWS'][] = &$row;
			unset($row);
		}
		unset($cartItem);
	}
}
else
{
	$arResult['TAX_INFOS'] = array();
	$arResult['SUM_BRUTTO'] = $arResult['SUM_NETTO'] = 0.0;
}
//<-- PRODUCT_ROWS, TAX_INFOS, SUM_BRUTTO, SUM_NETTO

// FORMATTED_SUM_NETTO, FORMATTED_SUM_BRUTTO -->
$arResult['FORMATTED_SUM_NETTO'] = CCrmCurrency::MoneyToString($arResult['SUM_NETTO'], $currencyID);
$arResult['FORMATTED_SUM_BRUTTO'] = CCrmCurrency::MoneyToString($arResult['SUM_BRUTTO'], $currencyID);
//<-- FORMATTED_SUM_NETTO, FORMATTED_SUM_BRUTTO

$arResult['ENTITY'] = $arFields;
unset($arFields);

$sid = bitrix_sessid();
$serviceURLTemplate = ($arParams["SERVICE_URL_TEMPLATE"]
	? $arParams["SERVICE_URL_TEMPLATE"]
	: '#SITE_DIR#bitrix/components/bitrix/mobile.crm.invoice.edit/ajax.php?site_id=#SITE#&sessid=#SID#'
);
$arResult['SERVICE_URL'] = CComponentEngine::makePathFromTemplate(
	$serviceURLTemplate,
	array('SID' => $sid)
);

$arResult['DEAL_SELECTOR_URL'] = CComponentEngine::makePathFromTemplate(
	$arParams['DEAL_SELECTOR_URL_TEMPLATE'],
	array('context_id' => $contextID)
);

$productRowUrlTemplate = ($arParams["PRODUCT_ROW_URL_TEMPLATE"]
	? $arParams["PRODUCT_ROW_URL_TEMPLATE"]
	: '#SITE_DIR#bitrix/components/bitrix/mobile.crm.product_row.edit/ajax.php?site_id=#SITE#&sessid=#SID#'
);
$arResult['PRODUCT_ROW_SERVICE_URL'] = CComponentEngine::makePathFromTemplate(
	$productRowUrlTemplate,
	array('SID' => $sid)
);

/*$arResult['CURRENCY_SELECTOR_URL'] = CComponentEngine::makePathFromTemplate(
	$arParams['CURRENCY_SELECTOR_URL_TEMPLATE'],
	array('context_id' => '')
);*/

$arResult['INVOICE_STATUS_SELECTOR_URL'] = CComponentEngine::makePathFromTemplate(
	$arParams['INVOICE_STATUS_SELECTOR_URL_TEMPLATE'],
	array('context_id' => '')
);

$arResult['PRODUCT_ROW_EDIT_URL'] = CComponentEngine::makePathFromTemplate(
	$arParams['PRODUCT_ROW_EDIT_URL_TEMPLATE'],
	array('context_id' => '')
);

$arResult['REQUISITE_EDIT_URL'] = CComponentEngine::makePathFromTemplate(
	$arParams['REQUISITE_EDIT_URL_TEMPLATE'],
	array('person_type_id' => 0, 'context_id' => '')
);

$arResult['PRODUCT_SELECTOR_URL_TEMPLATE'] = CComponentEngine::makePathFromTemplate(
	$arParams['PRODUCT_SELECTOR_URL_TEMPLATE'],
	array()
);

$arResult['CLIENT_SELECTOR_URL'] = CComponentEngine::makePathFromTemplate(
	$arParams['CLIENT_SELECTOR_URL_TEMPLATE'],
	array('context_id' => $contextID)
);

$arResult['PAY_SYSTEM_SELECTOR_URL'] = CComponentEngine::makePathFromTemplate(
	$arParams['PAY_SYSTEM_SELECTOR_URL_TEMPLATE'],
	array('person_type_id' => 0, 'context_id' => '')
);

$arResult['LOCATION_SELECTOR_URL'] = CComponentEngine::makePathFromTemplate(
	$arParams['LOCATION_SELECTOR_URL_TEMPLATE'],
	array('context_id' => '')
);

$this->IncludeComponentTemplate();
