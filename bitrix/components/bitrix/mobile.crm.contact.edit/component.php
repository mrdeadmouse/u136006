<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

$entityID = $arParams['ENTITY_ID'] = isset($arParams['ENTITY_ID']) ? intval($arParams['ENTITY_ID']) : 0;
if($entityID <= 0 && isset($_GET['contact_id']))
{
	$entityID = $arParams['ENTITY_ID'] = intval($_GET['contact_id']);
}
$arResult['ENTITY_ID'] = $entityID;

$userPerms = CCrmPerms::GetCurrentUserPermissions();
if (!($entityID > 0 ? CCrmContact::CheckUpdatePermission($entityID, $userPerms) : CCrmContact::CheckCreatePermission($userPerms)))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

global $APPLICATION;

//$arParams['ACTIVITY_LIST_URL_TEMPLATE'] = isset($arParams['ACTIVITY_LIST_URL_TEMPLATE']) ? $arParams['ACTIVITY_LIST_URL_TEMPLATE'] : '';
//$arParams['COMMUNICATION_LIST_URL_TEMPLATE'] = isset($arParams['COMMUNICATION_LIST_URL_TEMPLATE']) ? $arParams['COMMUNICATION_LIST_URL_TEMPLATE'] : '';
//$arParams['EVENT_LIST_URL_TEMPLATE'] = isset($arParams['EVENT_LIST_URL_TEMPLATE']) ? $arParams['EVENT_LIST_URL_TEMPLATE'] : '';
//$arParams['DEAL_LIST_URL_TEMPLATE'] = isset($arParams['DEAL_LIST_URL_TEMPLATE']) ? $arParams['DEAL_LIST_URL_TEMPLATE'] : '';
//$arParams['USER_PROFILE_URL_TEMPLATE'] = isset($arParams['USER_PROFILE_URL_TEMPLATE']) ? $arParams['USER_PROFILE_URL_TEMPLATE'] : '';
$arParams['CONTACT_SHOW_URL_TEMPLATE'] =  isset($arParams['CONTACT_SHOW_URL_TEMPLATE']) ? $arParams['CONTACT_SHOW_URL_TEMPLATE'] : '';
$arParams['CONTACT_EDIT_URL_TEMPLATE'] =  isset($arParams['CONTACT_EDIT_URL_TEMPLATE']) ? $arParams['CONTACT_EDIT_URL_TEMPLATE'] : '';
$arParams['USER_PROFILE_URL_TEMPLATE'] = isset($arParams['USER_PROFILE_URL_TEMPLATE']) ? $arParams['USER_PROFILE_URL_TEMPLATE'] : '';
$arParams['STATUS_SELECTOR_URL_TEMPLATE'] = isset($arParams['STATUS_SELECTOR_URL_TEMPLATE']) ? $arParams['STATUS_SELECTOR_URL_TEMPLATE'] : '';
$arParams['COMPANY_SELECTOR_URL_TEMPLATE'] = isset($arParams['COMPANY_SELECTOR_URL_TEMPLATE']) ? $arParams['COMPANY_SELECTOR_URL_TEMPLATE'] : '';
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array('#NOBR#','#/NOBR#'), array('', ''), $arParams['NAME_TEMPLATE']);

$uid = isset($arParams['UID']) ? $arParams['UID'] : '';
if($uid === '')
{
	$uid = 'mobile_crm_contact_edit';
}
$arResult['UID'] = $arParams['UID'] = $uid;
$arResult['USER_ID'] = CCrmSecurityHelper::GetCurrentUserID();

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

// ENABLE_COMPANY -->
if(isset($arParams['ENABLE_COMPANY']))
{
	$arResult['ENABLE_COMPANY'] = (bool)$arParams['ENABLE_COMPANY'];
}
else
{
	$arResult['ENABLE_COMPANY'] = !isset($_REQUEST['enable_company']) || $_REQUEST['enable_company'] !== 'N';
}
//<-- ENABLE_COMPANY

$companyID = $arParams['COMPANY_ID'] = isset($arParams['COMPANY_ID']) ? intval($arParams['ENTITY_ID']) : 0;
if($companyID <= 0 && isset($_REQUEST['company_id']))
{
	$companyID = $arParams['COMPANY_ID'] = intval($_REQUEST['company_id']);
}

$arResult['CONTACT_TYPE'] = CCrmStatus::GetStatusList('CONTACT_TYPE');

$arFields = null;
if($entityID <= 0)
{
	$arResult['MODE'] = 'CREATE';
	$arFields = array(
		'FM' => array()
	);

	$typeIDs = array_keys($arResult['CONTACT_TYPE']);
	if(!empty($typeIDs))
	{
		$arFields['~TYPE_ID'] = $typeIDs[0];
		$arFields['TYPE_ID'] = htmlspecialcharsbx($arFields['~TYPE_ID']);
	}

	if($companyID > 0)
	{
		$dbRes = CCrmCompany::GetListEx(array(), array('ID' => $companyID), false, false, array('TITLE'));
		$arRes = $dbRes ? $dbRes->Fetch() : null;
		if(is_array($arRes))
		{
			$arFields['~COMPANY_ID'] = $companyID;
			$arFields['~COMPANY_TITLE'] = $arRes['TITLE'];
			$arFields['COMPANY_TITLE'] = htmlspecialcharsbx($arRes['TITLE']);
		}
	}

	if($arResult['USER_ID'] > 0)
	{
		$dbUser = CUser::GetList(
			($by='id'),
			($order='asc'),
			array('ID'=> $arResult['USER_ID']),
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
			$arFields['~ASSIGNED_BY_ID'] = $arResult['USER_ID'];
			$arFields['~ASSIGNED_BY_LOGIN'] = $user['LOGIN'];
			$arFields['~ASSIGNED_BY_NAME'] = $user['NAME'];
			$arFields['~ASSIGNED_BY_LAST_NAME'] = $user['LAST_NAME'];
			$arFields['~ASSIGNED_BY_SECOND_NAME'] = $user['SECOND_NAME'];
		}
	}

	CCrmMobileHelper::PrepareContactItem(
		$arFields,
		$arParams,
		array(
			'CONTACT_TYPE' => $arResult['CONTACT_TYPE']
		)
	);
}
else
{
	$arResult['MODE'] = 'UPDATE';

	$dbFields = CCrmContact::GetListEx(array(), array('ID' => $entityID));
	$arFields = $dbFields->GetNext();

	if(!$arFields)
	{
		ShowError(GetMessage('CRM_CONTACT_EDIT_NOT_FOUND', array('#ID#' => $arParams['ENTITY_ID'])));
		return;
	}

	$arFields['FM'] = array();
	$dbMultiFields = CCrmFieldMulti::GetList(
		array('ID' => 'asc'),
		array('ENTITY_ID' => 'CONTACT', 'ELEMENT_ID' => $entityID)
	);

	if($dbMultiFields)
	{
		while($arMultiField = $dbMultiFields->Fetch())
		{
			$arFields['FM'][$arMultiField['TYPE_ID']][$arMultiField['ID']] =
				array('VALUE' => $arMultiField['VALUE'], 'VALUE_TYPE' => $arMultiField['VALUE_TYPE']);
		}
	}

	CCrmMobileHelper::PrepareContactItem(
		$arFields,
		$arParams,
		array(
			'CONTACT_TYPE' => $arResult['CONTACT_TYPE']
		)
	);
}

$arResult['ENTITY'] = $arFields;

$sid = bitrix_sessid();

$serviceURLTemplate = ($arParams["SERVICE_URL_TEMPLATE"]
	? $arParams["SERVICE_URL_TEMPLATE"]
	: '#SITE_DIR#bitrix/components/bitrix/mobile.crm.contact.edit/ajax.php?site_id=#SITE#&sessid=#SID#'
);
$arResult['SERVICE_URL'] = CComponentEngine::makePathFromTemplate(
	$serviceURLTemplate,
	array('SID' => $sid)
);

$arResult['UPLOAD_URL'] = CCrmUrlUtil::ToAbsoluteUrl(
	CComponentEngine::makePathFromTemplate(
		'#SITE_DIR#mobile/crm/contact/file.php?id=#ID#&sessid=#SID#',
		array('SID' => $sid, 'ID' => $entityID)
	)
);

$arResult['CONTACT_TYPE_SELECTOR_URL'] = CComponentEngine::makePathFromTemplate(
	$arParams['STATUS_SELECTOR_URL_TEMPLATE'],
	array(
		'type_id' => 'CONTACT_TYPE',
		'context_id' => $contextID
	)
);
$arResult['COMPANY_SELECTOR_URL'] = CComponentEngine::makePathFromTemplate(
	$arParams['COMPANY_SELECTOR_URL_TEMPLATE'],
	array('context_id' => $contextID)
);

$this->IncludeComponentTemplate();
