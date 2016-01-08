<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

$entityID = $arParams['ENTITY_ID'] = isset($arParams['ENTITY_ID']) ? intval($arParams['ENTITY_ID']) : 0;
if($entityID <= 0 && isset($_REQUEST['deal_id']))
{
	$entityID = $arParams['ENTITY_ID'] = intval($_REQUEST['deal_id']);
}
$arResult['ENTITY_ID'] = $entityID;

$userPerms = CCrmPerms::GetCurrentUserPermissions();
if (!($entityID > 0 ? CCrmDeal::CheckUpdatePermission($entityID, $userPerms) : CCrmDeal::CheckCreatePermission($userPerms)))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

global $APPLICATION;

$arParams['DEAL_SHOW_URL_TEMPLATE'] =  isset($arParams['DEAL_SHOW_URL_TEMPLATE']) ? $arParams['DEAL_SHOW_URL_TEMPLATE'] : '';
$arParams['DEAL_EDIT_URL_TEMPLATE'] =  isset($arParams['DEAL_EDIT_URL_TEMPLATE']) ? $arParams['DEAL_EDIT_URL_TEMPLATE'] : '';
$arParams['USER_PROFILE_URL_TEMPLATE'] = isset($arParams['USER_PROFILE_URL_TEMPLATE']) ? $arParams['USER_PROFILE_URL_TEMPLATE'] : '';
$arParams['STATUS_SELECTOR_URL_TEMPLATE'] = isset($arParams['STATUS_SELECTOR_URL_TEMPLATE']) ? $arParams['STATUS_SELECTOR_URL_TEMPLATE'] : '';
$arParams['CURRENCY_SELECTOR_URL_TEMPLATE'] = isset($arParams['CURRENCY_SELECTOR_URL_TEMPLATE']) ? $arParams['CURRENCY_SELECTOR_URL_TEMPLATE'] : '';
$arParams['PRODUCT_ROW_EDIT_URL_TEMPLATE'] = isset($arParams['PRODUCT_ROW_EDIT_URL_TEMPLATE']) ? $arParams['PRODUCT_ROW_EDIT_URL_TEMPLATE'] : '';
$arParams['PRODUCT_SELECTOR_URL_TEMPLATE'] = isset($arParams['PRODUCT_SELECTOR_URL_TEMPLATE']) ? $arParams['PRODUCT_SELECTOR_URL_TEMPLATE'] : '';
$arParams['CLIENT_SELECTOR_URL_TEMPLATE'] = isset($arParams['CLIENT_SELECTOR_URL_TEMPLATE']) ? $arParams['CLIENT_SELECTOR_URL_TEMPLATE'] : '';
$arParams['DEAL_STAGE_SELECTOR_URL_TEMPLATE'] = isset($arParams['DEAL_STAGE_SELECTOR_URL_TEMPLATE']) ? $arParams['DEAL_STAGE_SELECTOR_URL_TEMPLATE'] : '';
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array('#NOBR#','#/NOBR#'), array('', ''), $arParams['NAME_TEMPLATE']);

$uid = isset($arParams['UID']) ? $arParams['UID'] : '';
if($uid === '')
{
	$uid = 'mobile_crm_deal_edit';
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

$arResult['STAGE_LIST'] = CCrmStatus::GetStatusList('DEAL_STAGE');
$arResult['TYPE_LIST'] = CCrmStatus::GetStatusList('DEAL_TYPE');

$arFields = null;
if($entityID <= 0)
{
	$arResult['MODE'] = 'CREATE';

	$arFields = array(
		'~CURRENCY_ID' => CCrmCurrency::GetBaseCurrencyID(),
		'CURRENCY_ID' => htmlspecialcharsbx(CCrmCurrency::GetBaseCurrencyID())
	);

	$contactID = $arResult['CONTACT_ID'] = isset($_REQUEST['contact_id']) ? intval($_REQUEST['contact_id']) : 0;
	if($contactID > 0)
	{
		$dbContact = CCrmContact::GetListEx(
			array(), array('=ID' => $contactID), false, false, array('NAME', 'LAST_NAME', 'SECOND_NAME', 'COMPANY_ID', 'COMPANY_TITLE')
		);

		$contact = $dbContact->Fetch();
		if(is_array($contact))
		{
			$arFields['~CONTACT_ID'] = $arFields['CONTACT_ID'] = $contactID;

			$arFields['~CONTACT_NAME'] = isset($contact['NAME']) ? $contact['NAME'] : '';
			$arFields['CONTACT_NAME'] = htmlspecialcharsbx($arFields['~CONTACT_NAME']);

			$arFields['~CONTACT_SECOND_NAME'] = isset($contact['SECOND_NAME']) ? $contact['SECOND_NAME'] : '';
			$arFields['CONTACT_SECOND_NAME'] = htmlspecialcharsbx($arFields['~CONTACT_SECOND_NAME']);

			$arFields['~CONTACT_LAST_NAME'] = isset($contact['LAST_NAME']) ? $contact['LAST_NAME'] : '';
			$arFields['CONTACT_LAST_NAME'] = htmlspecialcharsbx($arFields['~CONTACT_LAST_NAME']);


			$contactCompanyID = isset($contact['COMPANY_ID']) ? intval($contact['COMPANY_ID']) : 0;
			if($contactCompanyID > 0)
			{
				$arFields['~COMPANY_ID'] = $arFields['COMPANY_ID'] =$contactCompanyID;
				$arFields['~COMPANY_TITLE'] = isset($contact['COMPANY_TITLE']) ? $contact['COMPANY_TITLE'] : '';
				$arFields['COMPANY_TITLE'] = htmlspecialcharsbx($arFields['~COMPANY_TITLE']);
			}
		}
	}

	$companyID = $arResult['COMPANY_ID'] = isset($_REQUEST['company_id']) ? intval($_REQUEST['company_id']) : 0;
	if($companyID > 0)
	{
		$dbCompany = CCrmCompany::GetListEx(
			array(), array('=ID' => $companyID), false, false, array('TITLE')
		);

		$company = $dbCompany->Fetch();
		if(is_array($company))
		{
			$arFields['~COMPANY_ID'] = $arFields['COMPANY_ID'] = $companyID;
			$arFields['~COMPANY_TITLE'] = isset($company['TITLE']) ? $company['TITLE'] : '';
			$arFields['COMPANY_TITLE'] = htmlspecialcharsbx($arFields['~COMPANY_TITLE']);
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

	$types = array_keys($arResult['TYPE_LIST']);
	if(!empty($types))
	{
		$arFields['~TYPE_ID'] = $types[0];
		$arFields['TYPE_ID'] = htmlspecialcharsbx($arFields['~TYPE_ID']);
	}

	$stages = array_keys($arResult['STAGE_LIST']);
	if(!empty($stages))
	{
		$arFields['~STAGE_ID'] = $stages[0];
		$arFields['STAGE_ID'] = htmlspecialcharsbx($arFields['~STAGE_ID']);
	}

	CCrmMobileHelper::PrepareDealItem(
		$arFields,
		$arParams,
		array(
			'STAGE_LIST' => $arResult['STAGE_LIST'],
			'TYPE_LIST' => $arResult['TYPE_LIST']
		)
	);
}
else
{
	$arResult['MODE'] = 'UPDATE';

	$dbFields = CCrmDeal::GetListEx(array(), array('ID' => $entityID));
	$arFields = $dbFields->GetNext();

	if(!$arFields)
	{
		ShowError(GetMessage('CRM_DEAL_EDIT_NOT_FOUND', array('#ID#' => $arParams['ENTITY_ID'])));
		return;
	}

	CCrmMobileHelper::PrepareDealItem(
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

$arResult['PRODUCT_ROWS'] = $entityID > 0 ? CCrmProductRow::LoadRows('D', $entityID) : array();
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
	: '#SITE_DIR#bitrix/components/bitrix/mobile.crm.deal.edit/ajax.php?site_id=#SITE#&sessid=#SID#'
);

$arResult['SERVICE_URL'] = CComponentEngine::makePathFromTemplate(
	$serviceURLTemplate,
	array('SID' => $sid)
);

$productRowUrlTemplate = ($arParams["PRODUCT_ROW_URL_TEMPLATE"]
	? $arParams["PRODUCT_ROW_URL_TEMPLATE"]
	: '#SITE_DIR#bitrix/components/bitrix/mobile.crm.product_row.edit/ajax.php?site_id=#SITE#&sessid=#SID#'
);
$arResult['PRODUCT_ROW_SERVICE_URL'] = CComponentEngine::makePathFromTemplate(
	$productRowUrlTemplate,
	array('SID' => $sid)
);

$arResult['TYPE_SELECTOR_URL'] = CComponentEngine::makePathFromTemplate(
	$arParams['STATUS_SELECTOR_URL_TEMPLATE'],
	array(
		'type_id' => 'DEAL_TYPE',
		'context_id' => $contextID
	)
);

$arResult['CURRENCY_SELECTOR_URL'] = CComponentEngine::makePathFromTemplate(
	$arParams['CURRENCY_SELECTOR_URL_TEMPLATE'],
	array('context_id' => '')
);

$arResult['DEAL_STAGE_SELECTOR_URL'] = CComponentEngine::makePathFromTemplate(
	$arParams['DEAL_STAGE_SELECTOR_URL_TEMPLATE'],
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

$arResult['CLIENT_SELECTOR_URL'] = CComponentEngine::makePathFromTemplate(
	$arParams['CLIENT_SELECTOR_URL_TEMPLATE'],
	array('context_id' => $contextID)
);

$this->IncludeComponentTemplate();
