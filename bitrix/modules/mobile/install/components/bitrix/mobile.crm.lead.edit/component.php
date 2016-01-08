<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

$entityID = $arParams['ENTITY_ID'] = isset($arParams['ENTITY_ID']) ? intval($arParams['ENTITY_ID']) : 0;
if($entityID <= 0 && isset($_REQUEST['lead_id']))
{
	$entityID = $arParams['ENTITY_ID'] = intval($_REQUEST['lead_id']);
}
$arResult['ENTITY_ID'] = $entityID;

$userPerms = CCrmPerms::GetCurrentUserPermissions();
if (!($entityID > 0 ? CCrmLead::CheckUpdatePermission($entityID, $userPerms) : CCrmLead::CheckCreatePermission($userPerms)))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

global $APPLICATION;

$arParams['LEAD_SHOW_URL_TEMPLATE'] =  isset($arParams['LEAD_SHOW_URL_TEMPLATE']) ? $arParams['LEAD_SHOW_URL_TEMPLATE'] : '';
$arParams['LEAD_EDIT_URL_TEMPLATE'] =  isset($arParams['LEAD_EDIT_URL_TEMPLATE']) ? $arParams['LEAD_EDIT_URL_TEMPLATE'] : '';
$arParams['USER_PROFILE_URL_TEMPLATE'] = isset($arParams['USER_PROFILE_URL_TEMPLATE']) ? $arParams['USER_PROFILE_URL_TEMPLATE'] : '';
$arParams['STATUS_SELECTOR_URL_TEMPLATE'] = isset($arParams['STATUS_SELECTOR_URL_TEMPLATE']) ? $arParams['STATUS_SELECTOR_URL_TEMPLATE'] : '';
$arParams['CURRENCY_SELECTOR_URL_TEMPLATE'] = isset($arParams['CURRENCY_SELECTOR_URL_TEMPLATE']) ? $arParams['CURRENCY_SELECTOR_URL_TEMPLATE'] : '';
$arParams['PRODUCT_ROW_EDIT_URL_TEMPLATE'] = isset($arParams['PRODUCT_ROW_EDIT_URL_TEMPLATE']) ? $arParams['PRODUCT_ROW_EDIT_URL_TEMPLATE'] : '';
$arParams['PRODUCT_SELECTOR_URL_TEMPLATE'] = isset($arParams['PRODUCT_SELECTOR_URL_TEMPLATE']) ? $arParams['PRODUCT_SELECTOR_URL_TEMPLATE'] : '';
$arParams['LEAD_STATUS_SELECTOR_URL_TEMPLATE'] = isset($arParams['LEAD_STATUS_SELECTOR_URL_TEMPLATE']) ? $arParams['LEAD_STATUS_SELECTOR_URL_TEMPLATE'] : '';
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array('#NOBR#','#/NOBR#'), array('', ''), $arParams['NAME_TEMPLATE']);

$uid = isset($arParams['UID']) ? $arParams['UID'] : '';
if($uid === '')
{
	$uid = 'mobile_crm_lead_edit';
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

$arResult['STATUS_LIST'] = CCrmStatus::GetStatusList('STATUS');
$arResult['SOURCE_LIST'] = CCrmStatus::GetStatusList('SOURCE');

$arFields = null;
if($entityID <= 0)
{
	$arResult['MODE'] = 'CREATE';

	$arFields = array(
		'~CURRENCY_ID' => CCrmCurrency::GetBaseCurrencyID(),
		'CURRENCY_ID' => htmlspecialcharsbx(CCrmCurrency::GetBaseCurrencyID()),
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

	$statuses = array_keys($arResult['STATUS_LIST']);
	if(!empty($statuses))
	{
		$arFields['~STATUS_ID'] = $statuses[0];
		$arFields['STATUS_ID'] = htmlspecialcharsbx($arFields['~STATUS_ID']);
	}

	$sources = array_keys($arResult['SOURCE_LIST']);
	if(!empty($sources))
	{
		$arFields['~SOURCE_ID'] = $sources[0];
		$arFields['SOURCE_ID'] = htmlspecialcharsbx($arFields['~SOURCE_ID']);
	}

	CCrmMobileHelper::PrepareLeadItem(
		$arFields,
		$arParams,
		array(
			'STATUS_LIST' => $arResult['STATUS_LIST'],
			'SOURCE_LIST' => $arResult['SOURCE_LIST']
		)
	);
}
else
{
	$arResult['MODE'] = 'UPDATE';

	$dbFields = CCrmLead::GetListEx(array(), array('ID' => $entityID));
	$arFields = $dbFields->GetNext();

	if(!$arFields)
	{
		ShowError(GetMessage('CRM_LEAD_EDIT_NOT_FOUND', array('#ID#' => $arParams['ENTITY_ID'])));
		return;
	}

	$arFields['FM'] = array();
	$dbMultiFields = CCrmFieldMulti::GetList(
		array('ID' => 'asc'),
		array('ENTITY_ID' => 'LEAD', 'ELEMENT_ID' => $entityID)
	);

	if($dbMultiFields)
	{
		while($arMultiField = $dbMultiFields->Fetch())
		{
			$arFields['FM'][$arMultiField['TYPE_ID']][$arMultiField['ID']] =
				array('VALUE' => $arMultiField['VALUE'], 'VALUE_TYPE' => $arMultiField['VALUE_TYPE']);
		}
	}

	CCrmMobileHelper::PrepareLeadItem(
		$arFields,
		$arParams,
		array(
			'STATUS_LIST' => $arResult['STATUS_LIST'],
			'SOURCE_LIST' => $arResult['SOURCE_LIST']
		)
	);

}

$currencyID = $arFields['~CURRENCY_ID'];
$arResult['ENTITY'] = $arFields;
unset($arFields);

$arResult['PRODUCT_ROWS'] = $entityID > 0 ? CCrmProductRow::LoadRows('L', $entityID) : array();
foreach($arResult['PRODUCT_ROWS'] as &$productRow)
{
	$price = isset($productRow['PRICE']) ? doubleval($productRow['PRICE']) : 0.0;
	$qty = isset($productRow['QUANTITY']) ? doubleval($productRow['QUANTITY']) : 0;
	$sum = $productRow['SUM'] = $price * $qty;
	$productRow['FORMATTED_PRICE'] = CCrmCurrency::MoneyToString($price, $currencyID);
	$productRow['FORMATTED_SUM'] = CCrmCurrency::MoneyToString($sum, $currencyID);
	$productRow['CURRENCY_ID'] = $currencyID;

}
unset($productRow);

$sid = bitrix_sessid();
$serviceURLTemplate = ($arParams["SERVICE_URL_TEMPLATE"]
	? $arParams["SERVICE_URL_TEMPLATE"]
	: '#SITE_DIR#bitrix/components/bitrix/mobile.crm.lead.edit/ajax.php?site_id=#SITE#&sessid=#SID#'
);
$arResult['SERVICE_URL'] = CComponentEngine::makePathFromTemplate(
	$serviceURLTemplate,
	array('SID' => $sid)
);
$productRowServiceURLTemplate = ($arParams["PRODUCT_ROW_URL_TEMPLATE"]
	? $arParams["PRODUCT_ROW_URL_TEMPLATE"]
	: '#SITE_DIR#bitrix/components/bitrix/mobile.crm.product_row.edit/ajax.php?site_id=#SITE#&sessid=#SID#'
);
$arResult['PRODUCT_ROW_SERVICE_URL'] = CComponentEngine::makePathFromTemplate(
	$productRowServiceURLTemplate,
	array('SID' => $sid)
);

$arResult['STATUS_SELECTOR_URL'] = CComponentEngine::makePathFromTemplate(
	$arParams['STATUS_SELECTOR_URL_TEMPLATE'],
	array(
		'type_id' => 'STATUS',
		'context_id' => $contextID
	)
);

$arResult['SOURCE_SELECTOR_URL'] = CComponentEngine::makePathFromTemplate(
	$arParams['STATUS_SELECTOR_URL_TEMPLATE'],
	array(
		'type_id' => 'SOURCE',
		'context_id' => $contextID
	)
);

$arResult['CURRENCY_SELECTOR_URL'] = CComponentEngine::makePathFromTemplate(
	$arParams['CURRENCY_SELECTOR_URL_TEMPLATE'],
	array('context_id' => '')
);

$arResult['LEAD_STATUS_SELECTOR_URL'] = CComponentEngine::makePathFromTemplate(
	$arParams['LEAD_STATUS_SELECTOR_URL_TEMPLATE'],
	array('context_id' => '')
);

$arResult['PRODUCT_ROW_EDIT_URL'] = CComponentEngine::makePathFromTemplate(
	$arParams['PRODUCT_ROW_EDIT_URL_TEMPLATE'],
	array('context_id' => '')
);

$arResult['PRODUCT_SELECTOR_URL_TEMPLATE'] = CComponentEngine::makePathFromTemplate(
	$arParams['PRODUCT_SELECTOR_URL_TEMPLATE'],
	array()
);

$this->IncludeComponentTemplate();
