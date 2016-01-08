<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

$userPerms = CCrmPerms::GetCurrentUserPermissions();
if ($userPerms->HavePerm('DEAL', BX_CRM_PERM_NONE, 'READ'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

global $APPLICATION;

$arParams['LEAD_SHOW_URL_TEMPLATE'] =  isset($arParams['LEAD_SHOW_URL_TEMPLATE']) ? $arParams['LEAD_SHOW_URL_TEMPLATE'] : '';
$arParams['LEAD_EDIT_URL_TEMPLATE'] =  isset($arParams['LEAD_EDIT_URL_TEMPLATE']) ? $arParams['LEAD_EDIT_URL_TEMPLATE'] : '';
$arParams['ACTIVITY_LIST_URL_TEMPLATE'] =  isset($arParams['ACTIVITY_LIST_URL_TEMPLATE']) ? $arParams['ACTIVITY_LIST_URL_TEMPLATE'] : '';
$arParams['ACTIVITY_EDIT_URL_TEMPLATE'] =  isset($arParams['ACTIVITY_EDIT_URL_TEMPLATE']) ? $arParams['ACTIVITY_EDIT_URL_TEMPLATE'] : '';
$arParams['COMMUNICATION_LIST_URL_TEMPLATE'] =  isset($arParams['COMMUNICATION_LIST_URL_TEMPLATE']) ? $arParams['COMMUNICATION_LIST_URL_TEMPLATE'] : '';
$arParams['EVENT_LIST_URL_TEMPLATE'] =  isset($arParams['EVENT_LIST_URL_TEMPLATE']) ? $arParams['EVENT_LIST_URL_TEMPLATE'] : '';
$arParams['PRODUCT_ROW_LIST_URL_TEMPLATE'] =  isset($arParams['PRODUCT_ROW_LIST_URL_TEMPLATE']) ? $arParams['PRODUCT_ROW_LIST_URL_TEMPLATE'] : '';
$arParams['LEAD_STATUS_SELECTOR_URL_TEMPLATE'] = isset($arParams['LEAD_STATUS_SELECTOR_URL_TEMPLATE']) ? $arParams['LEAD_STATUS_SELECTOR_URL_TEMPLATE'] : '';
$arParams['USER_PROFILE_URL_TEMPLATE'] = isset($arParams['USER_PROFILE_URL_TEMPLATE']) ? $arParams['USER_PROFILE_URL_TEMPLATE'] : '';

$entityID = $arParams['ENTITY_ID'] = isset($arParams['ENTITY_ID']) ? intval($arParams['ENTITY_ID']) : 0;
if($entityID <= 0 && isset($_GET['lead_id']))
{
	$entityID = $arParams['ENTITY_ID'] = intval($_GET['lead_id']);
}
$arResult['ENTITY_ID'] = $entityID;

$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array('#NOBR#','#/NOBR#'), array('', ''), $arParams['NAME_TEMPLATE']);

$arResult['USER_ID'] = intval(CCrmSecurityHelper::GetCurrentUserID());
$uid = isset($arParams['UID']) ? $arParams['UID'] : '';
if($uid === '')
{
	$uid = 'mobile_crm_lead_view';
}
$uid = $arResult['UID'] = $arParams['UID'];

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

$serviceURLTemplate = ($arParams["SERVICE_URL_TEMPLATE"]
	? $arParams["SERVICE_URL_TEMPLATE"]
	: '#SITE_DIR#bitrix/components/bitrix/mobile.crm.lead.edit/ajax.php?site_id=#SITE#&sessid=#SID#'
);

$arResult['SERVICE_URL'] = CComponentEngine::makePathFromTemplate(
	$serviceURLTemplate,
	array('SID' => bitrix_sessid())
);

$dbFields = CCrmLead::GetListEx(array(), array('ID' => $entityID));
$arFields = $dbFields->GetNext();

if(!$arFields)
{
	ShowError(GetMessage('CRM_LEAD_VIEW_NOT_FOUND', array('#ID#' => $arParams['ENTITY_ID'])));
	return;
}

$arResult['PERMISSIONS'] = array(
	'EDIT' => CCrmLead::CheckUpdatePermission($entityID, $userPerms) && $arFields['STATUS_ID'] !== 'CONVERTED',
	'DELETE' => CCrmLead::CheckDeletePermission($entityID, $userPerms)
);

$arResult['STATUS_LIST'] = CCrmStatus::GetStatusList('STATUS');
$arResult['SOURCE_LIST'] = CCrmStatus::GetStatusList('SOURCE');
$arResult['CURRENCY_LIST'] = CCrmCurrencyHelper::PrepareListItems();

CCrmMobileHelper::PrepareLeadItem(
	$arFields,
	$arParams,
	array(
		'STATUS_LIST' => $arResult['STATUS_LIST'],
		'SOURCE_LIST' => $arResult['SOURCE_LIST']
	)
);

$arFields['FM'] = array();
$dbMultiFields = CCrmFieldMulti::GetList(
	array('ID' => 'asc'),
	array('ENTITY_ID' => 'LEAD', 'ELEMENT_ID' => $entityID)
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
		'ENTITY_TYPE_ID' => CCrmOwnerType::Lead,
		'ENTITY_ID' => $entityID,
		'FM' => $arFields['FM'],
	)
);

$arResult['MAILTO'] = CCrmMobileHelper::PrepareMailtoParams(
	array(
		'COMMUNICATION_LIST_URL_TEMPLATE' => $arParams['COMMUNICATION_LIST_URL_TEMPLATE'],
		'ENTITY_TYPE_ID' => CCrmOwnerType::Lead,
		'ENTITY_ID' => $entityID,
		'FM' => $arFields['FM'],
	)
);

$arFields['PRODUCT_ROWS_QUANTITY'] = CAllCrmProductRow::GetRowQuantity(
	CCrmOwnerTypeAbbr::ResolveByTypeID(CCrmOwnerType::Lead),
	$entityID
);

$arFields['PRODUCT_ROWS_URL'] = $arParams['PRODUCT_ROW_LIST_URL_TEMPLATE'] !== ''
	? CComponentEngine::MakePathFromTemplate(
		$arParams['PRODUCT_ROW_LIST_URL_TEMPLATE'],
		array('entity_type_id' => CCrmOwnerType::Lead, 'entity_id' => $entityID)
	) : '';

$arFields['ACTITITY_QUANTITY'] = CAllCrmActivity::GetCount(
	array(
		'BINDINGS' => array(
			array(
				'OWNER_TYPE_ID' => CCrmOwnerType::Lead,
				'OWNER_ID' => $entityID
			)
		)
	)
);

$arFields['ACTIVITY_LIST_URL'] =  $arParams['ACTIVITY_LIST_URL_TEMPLATE'] !== ''
	? CComponentEngine::MakePathFromTemplate(
		$arParams['ACTIVITY_LIST_URL_TEMPLATE'],
		array('entity_type_id' => CCrmOwnerType::Lead, 'entity_id' => $entityID)
	) : '';

$arFields['EVENT_LIST_URL'] =  $arParams['EVENT_LIST_URL_TEMPLATE'] !== ''
	? CComponentEngine::MakePathFromTemplate(
		$arParams['EVENT_LIST_URL_TEMPLATE'],
		array('entity_type_id' => CCrmOwnerType::Lead, 'entity_id' => $entityID)
	) : '';

$arFields['EDIT_URL'] = $arParams['LEAD_EDIT_URL_TEMPLATE'] !== ''
	? CComponentEngine::MakePathFromTemplate(
		$arParams['LEAD_EDIT_URL_TEMPLATE'],
		array('lead_id' => $entityID)
	) : '';

$arFields['CALL_EDIT_URL'] =  $arParams['ACTIVITY_EDIT_URL_TEMPLATE'] !== ''
	? CComponentEngine::makePathFromTemplate(
		$arParams['ACTIVITY_EDIT_URL_TEMPLATE'],
		array('owner_type' => CCrmOwnerType::LeadName, 'owner_id' => $entityID, 'type_id' => CCrmActivityType::Call)
	) : '';

$arFields['MEETING_EDIT_URL'] =  $arParams['ACTIVITY_EDIT_URL_TEMPLATE'] !== ''
	? CComponentEngine::makePathFromTemplate(
		$arParams['ACTIVITY_EDIT_URL_TEMPLATE'],
		array('owner_type' => CCrmOwnerType::LeadName, 'owner_id' => $entityID, 'type_id' => CCrmActivityType::Meeting)
	) : '';

$arFields['EMAIL_EDIT_URL'] =  $arParams['ACTIVITY_EDIT_URL_TEMPLATE'] !== ''
	? CComponentEngine::makePathFromTemplate(
		$arParams['ACTIVITY_EDIT_URL_TEMPLATE'],
		array('owner_type' => CCrmOwnerType::LeadName, 'owner_id' => $entityID, 'type_id' => CCrmActivityType::Email)
	) : '';

$arResult['LEAD_STATUS_SELECTOR_URL'] = CComponentEngine::makePathFromTemplate(
	$arParams['LEAD_STATUS_SELECTOR_URL_TEMPLATE'],
	array('context_id' => '')
);

$arResult['LEAD_STATUS_SELECTOR_URL'] = CComponentEngine::makePathFromTemplate(
	$arParams['LEAD_STATUS_SELECTOR_URL_TEMPLATE'],
	array('context_id' => '')
);

$arResult['ENTITY'] = &$arFields;
unset($arFields);

$this->IncludeComponentTemplate();
