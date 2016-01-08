<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

$userPerms = CCrmPerms::GetCurrentUserPermissions();
if ($userPerms->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'READ'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

global $APPLICATION;

$arParams['INVOICE_SHOW_URL_TEMPLATE'] =  isset($arParams['INVOICE_SHOW_URL_TEMPLATE']) ? $arParams['INVOICE_SHOW_URL_TEMPLATE'] : '';
$arParams['INVOICE_EDIT_URL_TEMPLATE'] =  isset($arParams['INVOICE_EDIT_URL_TEMPLATE']) ? $arParams['INVOICE_EDIT_URL_TEMPLATE'] : '';
$arParams['ACTIVITY_LIST_URL_TEMPLATE'] =  isset($arParams['ACTIVITY_LIST_URL_TEMPLATE']) ? $arParams['ACTIVITY_LIST_URL_TEMPLATE'] : '';
$arParams['ACTIVITY_EDIT_URL_TEMPLATE'] =  isset($arParams['ACTIVITY_EDIT_URL_TEMPLATE']) ? $arParams['ACTIVITY_EDIT_URL_TEMPLATE'] : '';
$arParams['COMMUNICATION_LIST_URL_TEMPLATE'] =  isset($arParams['COMMUNICATION_LIST_URL_TEMPLATE']) ? $arParams['COMMUNICATION_LIST_URL_TEMPLATE'] : '';
$arParams['EVENT_LIST_URL_TEMPLATE'] =  isset($arParams['EVENT_LIST_URL_TEMPLATE']) ? $arParams['EVENT_LIST_URL_TEMPLATE'] : '';
$arParams['PRODUCT_ROW_LIST_URL_TEMPLATE'] =  isset($arParams['PRODUCT_ROW_LIST_URL_TEMPLATE']) ? $arParams['PRODUCT_ROW_LIST_URL_TEMPLATE'] : '';
$arParams['COMPANY_SHOW_URL_TEMPLATE'] = isset($arParams['COMPANY_SHOW_URL_TEMPLATE']) ? $arParams['COMPANY_SHOW_URL_TEMPLATE'] : '';
$arParams['CONTACT_SHOW_URL_TEMPLATE'] = isset($arParams['CONTACT_SHOW_URL_TEMPLATE']) ? $arParams['CONTACT_SHOW_URL_TEMPLATE'] : '';
$arParams['INVOICE_STATUS_SELECTOR_URL_TEMPLATE'] = isset($arParams['INVOICE_STATUS_SELECTOR_URL_TEMPLATE']) ? $arParams['INVOICE_STATUS_SELECTOR_URL_TEMPLATE'] : '';
$arParams['USER_PROFILE_URL_TEMPLATE'] = isset($arParams['USER_PROFILE_URL_TEMPLATE']) ? $arParams['USER_PROFILE_URL_TEMPLATE'] : '';

$entityID = $arParams['ENTITY_ID'] = isset($arParams['ENTITY_ID']) ? intval($arParams['ENTITY_ID']) : 0;
if($entityID <= 0 && isset($_REQUEST['invoice_id']))
{
	$entityID = $arParams['ENTITY_ID'] = intval($_REQUEST['invoice_id']);
}
$arResult['ENTITY_ID'] = $entityID;

$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array('#NOBR#','#/NOBR#'), array('', ''), $arParams['NAME_TEMPLATE']);

$arResult['USER_ID'] = CCrmSecurityHelper::GetCurrentUserID();
$uid = isset($arParams['UID']) ? $arParams['UID'] : '';
if($uid === '')
{
	$uid = 'mobile_crm_invoice_view';
}
$uid = $arResult['UID'] = $arParams['UID'];
$arResult['STATUS_LIST'] = CCrmStatus::GetStatusList('INVOICE_STATUS');
//$arResult['TYPE_LIST'] = CCrmStatus::GetStatusList('DEAL_TYPE');
$arResult['CURRENCY_LIST'] = CCrmCurrencyHelper::PrepareListItems();
$serviceURLTemplate = ($arParams["SERVICE_URL_TEMPLATE"]
	? $arParams["SERVICE_URL_TEMPLATE"]
	: '#SITE_DIR#bitrix/components/bitrix/mobile.crm.invoice.edit/ajax.php?site_id=#SITE#&sessid=#SID#'
);
$arResult['SERVICE_URL'] = CComponentEngine::makePathFromTemplate(
	$serviceURLTemplate,
	array('SID' => bitrix_sessid())
);

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

$dbFields = CCrmInvoice::GetList(array(), array('ID' => $entityID));
$arFields = $dbFields->GetNext();

if(!$arFields)
{
	ShowError(GetMessage('CRM_INVOICE_VIEW_NOT_FOUND', array('#ID#' => $arParams['ENTITY_ID'])));
	return;
}

$arResult['PERMISSIONS'] = array(
	'EDIT' => !$userPerms->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'WRITE'),
	'DELETE' => !$userPerms->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'DELETE')
);

CCrmMobileHelper::PrepareInvoiceItem(
	$arFields,
	$arParams,
	array(),
	array('ENABLE_MULTI_FIELDS' => true, 'ENABLE_PAYER_INFO' => true)
);

$accountNumber = isset($arFields['~ACCOUNT_NUMBER']) ? $arFields['~ACCOUNT_NUMBER'] : '';
if($accountNumber === '')
{
	$accountNumber = $arFields['~ID'];
}

$arResult['EMAIL_SUBJECT'] = GetMessage(
	'CRM_INVOICE_VIEW_EMAIL_SUBJECT', array('#NUMBER#' => $accountNumber)
);

$dealID = $arFields['~DEAL_ID'];
$contactID = $arFields['~CONTACT_ID'];
$companyID = $arFields['~COMPANY_ID'];
if($contactID <= 0)
{
	$arResult['CONTACT_CALLTO'] = $arFields['CONTACT_SHOW_URL'] = $arFields['CONTACT_EMAIL_EDIT_URL'] = '';
}
else
{
	$arResult['CONTACT_CALLTO'] = CCrmMobileHelper::PrepareCalltoParams(
		array(
			'COMMUNICATION_LIST_URL_TEMPLATE' => $arParams['COMMUNICATION_LIST_URL_TEMPLATE'],
			'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
			'ENTITY_ID' => $contactID,
			'FM' => $arFields['CONTACT_FM']
		)
	);

	$arFields['CONTACT_SHOW_URL'] = CComponentEngine::makePathFromTemplate(
		$arParams['CONTACT_SHOW_URL_TEMPLATE'], array('contact_id' => $contactID)
	);

	$emailOwnerTypeName = CCrmOwnerType::ContactName;
	$emailOwnerID = $contactID;
	if($dealID > 0)
	{
		$emailOwnerTypeName = CCrmOwnerType::DealName;
		$emailOwnerID = $dealID;
	}

	$arFields['CONTACT_EMAIL_EDIT_URL'] = CCrmUrlUtil::AddUrlParams(
		CComponentEngine::makePathFromTemplate(
			$arParams['ACTIVITY_EDIT_URL_TEMPLATE'],
			array('owner_type' => $emailOwnerTypeName, 'owner_id' => $emailOwnerID, 'type_id' => CCrmActivityType::Email)
		),
		array('comm[]' => strtolower(CCrmOwnerType::ContactName).'_'.$contactID)
	);
}

if($companyID <= 0)
{
	$arResult['COMPANY_CALLTO'] = $arFields['COMPANY_SHOW_URL'] = $arFields['COMPANY_EMAIL_EDIT_URL'] = '';
}
else
{
	$arResult['COMPANY_CALLTO'] = CCrmMobileHelper::PrepareCalltoParams(
		array(
			'COMMUNICATION_LIST_URL_TEMPLATE' => $arParams['COMMUNICATION_LIST_URL_TEMPLATE'],
			'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
			'ENTITY_ID' => $companyID,
			'FM' => $arFields['COMPANY_FM']
		)
	);

	$arFields['COMPANY_SHOW_URL'] = CComponentEngine::makePathFromTemplate(
		$arParams['COMPANY_SHOW_URL_TEMPLATE'], array('company_id' => $companyID)
	);

	$emailOwnerTypeName = CCrmOwnerType::ContactName;
	$emailOwnerID = $contactID;
	if($dealID > 0)
	{
		$emailOwnerTypeName = CCrmOwnerType::DealName;
		$emailOwnerID = $dealID;
	}

	$arFields['COMPANY_EMAIL_EDIT_URL'] = CCrmUrlUtil::AddUrlParams(
		CComponentEngine::makePathFromTemplate(
			$arParams['ACTIVITY_EDIT_URL_TEMPLATE'],
			array('owner_type' => $emailOwnerTypeName, 'owner_id' => $emailOwnerID, 'type_id' => CCrmActivityType::Email)
		),
		array('comm[]' => strtolower(CCrmOwnerType::CompanyName).'_'.$companyID)
	);
}

/*$arFields['PRODUCT_ROWS_QUANTITY'] = CAllCrmProductRow::GetRowQuantity(
	CCrmOwnerTypeAbbr::ResolveByTypeID(CCrmOwnerType::Invoice),
	$entityID
);*/

$arFields['PRODUCT_ROWS_URL'] = CComponentEngine::makePathFromTemplate(
	$arParams['PRODUCT_ROW_LIST_URL_TEMPLATE'],
	array('entity_type_id' => CCrmOwnerType::Invoice, 'entity_id' => $entityID)
);

$arFields['EVENT_LIST_URL'] = CComponentEngine::makePathFromTemplate(
	$arParams['EVENT_LIST_URL_TEMPLATE'],
	array('entity_type_id' => CCrmOwnerType::Invoice, 'entity_id' => $entityID)
);

$arResult['INVOICE_STATUS_SELECTOR_URL'] = CComponentEngine::makePathFromTemplate(
	$arParams['INVOICE_STATUS_SELECTOR_URL_TEMPLATE'],
	array('context_id' => '')
);

$arResult['ENTITY'] = &$arFields;
unset($arFields);

$this->IncludeComponentTemplate();
