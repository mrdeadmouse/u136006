<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

$userPerms = CCrmPerms::GetCurrentUserPermissions();
if ($userPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE, 'READ'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

global $APPLICATION;

$arParams['COMPANY_SHOW_URL_TEMPLATE'] =  isset($arParams['COMPANY_SHOW_URL_TEMPLATE']) ? $arParams['COMPANY_SHOW_URL_TEMPLATE'] : '';
$arParams['COMPANY_EDIT_URL_TEMPLATE'] =  isset($arParams['COMPANY_EDIT_URL_TEMPLATE']) ? $arParams['COMPANY_EDIT_URL_TEMPLATE'] : '';
$arParams['ACTIVITY_LIST_URL_TEMPLATE'] =  isset($arParams['ACTIVITY_LIST_URL_TEMPLATE']) ? $arParams['ACTIVITY_LIST_URL_TEMPLATE'] : '';
$arParams['ACTIVITY_EDIT_URL_TEMPLATE'] =  isset($arParams['ACTIVITY_EDIT_URL_TEMPLATE']) ? $arParams['ACTIVITY_EDIT_URL_TEMPLATE'] : '';
$arParams['INVOICE_EDIT_URL_TEMPLATE'] =  isset($arParams['INVOICE_EDIT_URL_TEMPLATE']) ? $arParams['INVOICE_EDIT_URL_TEMPLATE'] : '';
$arParams['COMMUNICATION_LIST_URL_TEMPLATE'] =  isset($arParams['COMMUNICATION_LIST_URL_TEMPLATE']) ? $arParams['COMMUNICATION_LIST_URL_TEMPLATE'] : '';
$arParams['EVENT_LIST_URL_TEMPLATE'] =  isset($arParams['EVENT_LIST_URL_TEMPLATE']) ? $arParams['EVENT_LIST_URL_TEMPLATE'] : '';
$arParams['CONTACT_LIST_URL_TEMPLATE'] =  isset($arParams['CONTACT_LIST_URL_TEMPLATE']) ? $arParams['CONTACT_LIST_URL_TEMPLATE'] : '';
$arParams['DEAL_LIST_URL_TEMPLATE'] =  isset($arParams['DEAL_LIST_URL_TEMPLATE']) ? $arParams['DEAL_LIST_URL_TEMPLATE'] : '';
$arParams['USER_PROFILE_URL_TEMPLATE'] = isset($arParams['USER_PROFILE_URL_TEMPLATE']) ? $arParams['USER_PROFILE_URL_TEMPLATE'] : '';

$entityID = $arParams['ENTITY_ID'] = isset($arParams['ENTITY_ID']) ? intval($arParams['ENTITY_ID']) : 0;
if($entityID <= 0 && isset($_GET['company_id']))
{
	$entityID = $arParams['ENTITY_ID'] = intval($_GET['company_id']);
}
$arResult['ENTITY_ID'] = $entityID;

$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array('#NOBR#','#/NOBR#'), array('', ''), $arParams['NAME_TEMPLATE']);

$arResult['USER_ID'] = intval(CCrmSecurityHelper::GetCurrentUserID());
$arParams['UID'] = isset($arParams['UID']) ? $arParams['UID'] : '';
if(!isset($arParams['UID']) || $arParams['UID'] === '')
{
	$arParams['UID'] = 'mobile_crm_company_view';
}
$arResult['UID'] = $arParams['UID'];
$serviceURLTemplate = ($arParams["SERVICE_URL_TEMPLATE"]
	? $arParams["SERVICE_URL_TEMPLATE"]
	: '#SITE_DIR#bitrix/components/bitrix/mobile.crm.company.edit/ajax.php?site_id=#SITE#&sessid=#SID#'
);
$arResult['SERVICE_URL'] = CComponentEngine::makePathFromTemplate(
	$serviceURLTemplate,
	array('SID' => bitrix_sessid())
);
$arResult['PERMISSIONS'] = array(
	'EDIT' => CCrmCompany::CheckUpdatePermission($entityID, $userPerms),
	'DELETE' => CCrmCompany::CheckDeletePermission($entityID, $userPerms)
);

$dbFields = CCrmCompany::GetListEx(array(), array('ID' => $entityID));
$arFields = $dbFields->GetNext();

if(!$arFields)
{
	ShowError(GetMessage('CRM_COMPANY_VIEW_NOT_FOUND', array('#ID#' => $arParams['ENTITY_ID'])));
	return;
}

$arResult['COMPANY_TYPE_LIST'] = CCrmStatus::GetStatusList('COMPANY_TYPE');
$arResult['EMPLOYEES_LIST'] = CCrmStatus::GetStatusList('EMPLOYEES');
$arResult['INDUSTRY_LIST'] = CCrmStatus::GetStatusList('INDUSTRY');

CCrmMobileHelper::PrepareCompanyItem(
	$arFields,
	$arParams,
	array(
		'COMPANY_TYPE' => $arResult['COMPANY_TYPE_LIST'],
		'INDUSTRY' => $arResult['INDUSTRY_LIST'],
		'EMPLOYEES_LIST' => $arResult['EMPLOYEES_LIST']
	)
);

$arFields['FM'] = array();
$dbMultiFields = CCrmFieldMulti::GetList(
	array('ID' => 'asc'),
	array('ENTITY_ID' => 'COMPANY', 'ELEMENT_ID' => $entityID)
);
if($dbMultiFields)
{
	while($multiFields = $dbMultiFields->Fetch())
	{
		$arFields['FM'][$multiFields['TYPE_ID']][] = array('VALUE' => $multiFields['VALUE'], 'VALUE_TYPE' => $multiFields['VALUE_TYPE']);
	}
}

$arResult['CALLTO'] = CCrmMobileHelper::PrepareCalltoParams(
	array(
		'COMMUNICATION_LIST_URL_TEMPLATE' => $arParams['COMMUNICATION_LIST_URL_TEMPLATE'],
		'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
		'ENTITY_ID' => $entityID,
		'FM' => $arFields['FM'],
	)
);

$arResult['MAILTO'] = CCrmMobileHelper::PrepareMailtoParams(
	array(
		'COMMUNICATION_LIST_URL_TEMPLATE' => $arParams['COMMUNICATION_LIST_URL_TEMPLATE'],
		'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
		'ENTITY_ID' => $entityID,
		'FM' => $arFields['FM'],
	)
);

$arFields['ACTITITY_QUANTITY'] = CAllCrmActivity::GetCount(
	array(
		'BINDINGS' => array(
			array(
				'OWNER_TYPE_ID' => CCrmOwnerType::Company,
				'OWNER_ID' => $entityID
			)
		)
	)
);

$arFields['ACTIVITY_LIST_URL'] =  $arParams['ACTIVITY_LIST_URL_TEMPLATE'] !== ''
	? CComponentEngine::MakePathFromTemplate(
		$arParams['ACTIVITY_LIST_URL_TEMPLATE'],
		array('entity_type_id' => CCrmOwnerType::Company, 'entity_id' => $entityID)
	) : '';

$arFields['EVENT_LIST_URL'] =  $arParams['EVENT_LIST_URL_TEMPLATE'] !== ''
	? CComponentEngine::MakePathFromTemplate(
		$arParams['EVENT_LIST_URL_TEMPLATE'],
		array('entity_type_id' => CCrmOwnerType::Company, 'entity_id' => $entityID)
	) : '';

$arFields['DEAL_QUANTITY'] = CAllCrmDeal::GetCount(array('COMPANY_ID' => $entityID));

$arFields['DEAL_LIST_URL'] =  $arParams['DEAL_LIST_URL_TEMPLATE'] !== ''
	? CComponentEngine::MakePathFromTemplate(
		$arParams['DEAL_LIST_URL_TEMPLATE'],
		array('company_id' => $entityID)
	) : '';

$arFields['CONTACT_QUANTITY'] = CAllCrmContact::GetCount(array('COMPANY_ID' => $entityID));

$arFields['CONTACT_LIST_URL'] =  $arParams['CONTACT_LIST_URL_TEMPLATE'] !== ''
	? CComponentEngine::MakePathFromTemplate(
		$arParams['CONTACT_LIST_URL_TEMPLATE'],
		array('company_id' => $entityID)
	) : '';

$arFields['EDIT_URL'] = $arParams['COMPANY_EDIT_URL_TEMPLATE'] !== ''
	? CComponentEngine::MakePathFromTemplate(
		$arParams['COMPANY_EDIT_URL_TEMPLATE'],
		array('company_id' => $entityID)
	) : '';

$arFields['CALL_EDIT_URL'] =  $arParams['ACTIVITY_EDIT_URL_TEMPLATE'] !== ''
	? CComponentEngine::makePathFromTemplate(
		$arParams['ACTIVITY_EDIT_URL_TEMPLATE'],
		array('owner_type' => CCrmOwnerType::CompanyName, 'owner_id' => $entityID, 'type_id' => CCrmActivityType::Call)
	) : '';

$arFields['MEETING_EDIT_URL'] =  $arParams['ACTIVITY_EDIT_URL_TEMPLATE'] !== ''
	? CComponentEngine::makePathFromTemplate(
		$arParams['ACTIVITY_EDIT_URL_TEMPLATE'],
		array('owner_type' => CCrmOwnerType::CompanyName, 'owner_id' => $entityID, 'type_id' => CCrmActivityType::Meeting)
	) : '';

$arFields['EMAIL_EDIT_URL'] =  $arParams['ACTIVITY_EDIT_URL_TEMPLATE'] !== ''
	? CComponentEngine::makePathFromTemplate(
		$arParams['ACTIVITY_EDIT_URL_TEMPLATE'],
		array('owner_type' => CCrmOwnerType::CompanyName, 'owner_id' => $entityID, 'type_id' => CCrmActivityType::Email)
	) : '';

$arFields['INVOICE_EDIT_URL'] = $arParams['INVOICE_EDIT_URL_TEMPLATE'] !== ''
	? CComponentEngine::makePathFromTemplate(
		$arParams['INVOICE_EDIT_URL_TEMPLATE'],
		array(
			'contact_id' => '',
			'company_id' => $entityID,
			'deal_id' => ''
		)
	) : '';

$arResult['ENTITY'] = &$arFields;
unset($arFields);

$this->IncludeComponentTemplate();



