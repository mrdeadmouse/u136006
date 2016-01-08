<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

$entityID = $arParams['ENTITY_ID'] = isset($arParams['ENTITY_ID']) ? intval($arParams['ENTITY_ID']) : 0;
if($entityID <= 0 && isset($_GET['company_id']))
{
	$entityID = $arParams['ENTITY_ID'] = intval($_GET['company_id']);
}
$arResult['ENTITY_ID'] = $entityID;

$userPerms = CCrmPerms::GetCurrentUserPermissions();
if (!($entityID > 0 ? CCrmCompany::CheckUpdatePermission($entityID, $userPerms) : CCrmCompany::CheckCreatePermission($userPerms)))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

global $APPLICATION;

$arParams['COMPANY_SHOW_URL_TEMPLATE'] =  isset($arParams['COMPANY_SHOW_URL_TEMPLATE']) ? $arParams['COMPANY_SHOW_URL_TEMPLATE'] : '';
$arParams['COMPANY_EDIT_URL_TEMPLATE'] =  isset($arParams['COMPANY_EDIT_URL_TEMPLATE']) ? $arParams['COMPANY_EDIT_URL_TEMPLATE'] : '';
$arParams['USER_PROFILE_URL_TEMPLATE'] = isset($arParams['USER_PROFILE_URL_TEMPLATE']) ? $arParams['USER_PROFILE_URL_TEMPLATE'] : '';
$arParams['STATUS_SELECTOR_URL_TEMPLATE'] = isset($arParams['STATUS_SELECTOR_URL_TEMPLATE']) ? $arParams['STATUS_SELECTOR_URL_TEMPLATE'] : '';
$arParams['CONTACT_SELECTOR_URL_TEMPLATE'] = isset($arParams['CONTACT_SELECTOR_URL_TEMPLATE']) ? $arParams['CONTACT_SELECTOR_URL_TEMPLATE'] : '';
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array('#NOBR#','#/NOBR#'), array('', ''), $arParams['NAME_TEMPLATE']);

$uid = isset($arParams['UID']) ? $arParams['UID'] : '';
if($uid === '')
{
	$uid = 'mobile_crm_company_edit';
}
$arResult['UID'] = $arParams['UID'] = $uid;
$arResult['USER_ID'] = CCrmSecurityHelper::GetCurrentUserID();

$arResult['COMPANY_TYPE'] = CCrmStatus::GetStatusList('COMPANY_TYPE');
$arResult['INDUSTRY'] = CCrmStatus::GetStatusList('INDUSTRY');
$arResult['EMPLOYEES'] = CCrmStatus::GetStatusList('EMPLOYEES');

$arFields = null;
if($entityID <= 0)
{
	$arResult['MODE'] = 'CREATE';
	$arFields = array(
		'FM' => array()
	);

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

	$types = array_keys($arResult['COMPANY_TYPE']);
	if(!empty($types))
	{
		$arFields['~COMPANY_TYPE'] = $types[0];
		$arFields['COMPANY_TYPE'] = htmlspecialcharsbx($arFields['~COMPANY_TYPE']);
	}

	$industries = array_keys($arResult['INDUSTRY']);
	if(!empty($industries))
	{
		$arFields['~INDUSTRY'] = $industries[0];
		$arFields['INDUSTRY'] = htmlspecialcharsbx($arFields['~INDUSTRY']);
	}

	$employees = array_keys($arResult['EMPLOYEES']);
	if(!empty($industries))
	{
		$arFields['~EMPLOYEES'] = $employees[0];
		$arFields['EMPLOYEES'] = htmlspecialcharsbx($arFields['~EMPLOYEES']);
	}

	CCrmMobileHelper::PrepareCompanyItem(
		$arFields,
		$arParams,
		array(
			'COMPANY_TYPE' => $arResult['COMPANY_TYPE'],
			'INDUSTRY' => $arResult['INDUSTRY'],
			'EMPLOYEES_LIST' => $arResult['EMPLOYEES']
		)
	);
}
else
{
	$arResult['MODE'] = 'UPDATE';

	$dbFields = CCrmCompany::GetListEx(array(), array('ID' => $entityID));
	$arFields = $dbFields->GetNext();

	if(!$arFields)
	{
		ShowError(GetMessage('CRM_COMPANY_EDIT_NOT_FOUND', array('#ID#' => $arParams['ENTITY_ID'])));
		return;
	}

	$arFields['FM'] = array();
	$dbMultiFields = CCrmFieldMulti::GetList(
		array('ID' => 'asc'),
		array('ENTITY_ID' => 'COMPANY', 'ELEMENT_ID' => $entityID)
	);

	if($dbMultiFields)
	{
		while($arMultiField = $dbMultiFields->Fetch())
		{
			$arFields['FM'][$arMultiField['TYPE_ID']][$arMultiField['ID']] =
				array('VALUE' => $arMultiField['VALUE'], 'VALUE_TYPE' => $arMultiField['VALUE_TYPE']);
		}
	}

	CCrmMobileHelper::PrepareCompanyItem(
		$arFields,
		$arParams,
		array(
			'COMPANY_TYPE' => $arResult['COMPANY_TYPE'],
			'INDUSTRY' => $arResult['INDUSTRY'],
			'EMPLOYEES_LIST' => $arResult['EMPLOYEES']
		)
	);
}

$arResult['ENTITY'] = $arFields;

$sid = bitrix_sessid();
$serviceURLTemplate = ($arParams["SERVICE_URL_TEMPLATE"]
	? $arParams["SERVICE_URL_TEMPLATE"]
	: '#SITE_DIR#bitrix/components/bitrix/mobile.crm.company.edit/ajax.php?site_id=#SITE#&sessid=#SID#'
);
$arResult['SERVICE_URL'] = CComponentEngine::makePathFromTemplate(
	$serviceURLTemplate,
	array('SID' => $sid)
);

$arResult['UPLOAD_URL'] = CCrmUrlUtil::ToAbsoluteUrl(
	CComponentEngine::makePathFromTemplate(
		'#SITE_DIR#mobile/crm/company/file.php?id=#ID#&sessid=#SID#',
		array('SID' => $sid, 'ID' => $entityID)
	)
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

// ENABLE_CONTACT -->
if(isset($arParams['ENABLE_CONTACT']))
{
	$arResult['ENABLE_CONTACT'] = (bool)$arParams['ENABLE_CONTACT'];
}
else
{
	$arResult['ENABLE_CONTACT'] = !isset($_REQUEST['enable_contact']) || $_REQUEST['enable_contact'] !== 'N';
}

if($arResult['ENABLE_CONTACT'] && $arResult['MODE'] !== 'CREATE')
{
	$arResult['ENABLE_CONTACT'] = false;
}

//<-- ENABLE_CONTACT

$arResult['COMPANY_TYPE_SELECTOR_URL'] = CComponentEngine::makePathFromTemplate(
	$arParams['STATUS_SELECTOR_URL_TEMPLATE'],
	array(
		'type_id' => 'COMPANY_TYPE',
		'context_id' => $contextID
	)
);
$arResult['COMPANY_INDUSTRY_SELECTOR_URL'] = CComponentEngine::makePathFromTemplate(
	$arParams['STATUS_SELECTOR_URL_TEMPLATE'],
	array(
		'type_id' => 'INDUSTRY',
		'context_id' => $contextID
	)
);
$arResult['CONTACT_SELECTOR_URL'] = CComponentEngine::makePathFromTemplate(
	$arParams['CONTACT_SELECTOR_URL_TEMPLATE'],
	array('context_id' => $contextID)
);

$this->IncludeComponentTemplate();

