<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Crm\Integration\StorageType;

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

// 'Fileman' module always installed
CModule::IncludeModule('fileman');

$CCrmQuote = new CCrmQuote();
if ($CCrmQuote->cPerms->HavePerm('QUOTE', BX_CRM_PERM_NONE, 'READ'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

CUtil::InitJSCore(array('ajax', 'tooltip'));

$arResult['EDITABLE_FIELDS'] = array();
$arResult['ELEMENT_ID'] = $arParams['ELEMENT_ID'] = isset($arParams['ELEMENT_ID']) ? intval($arParams['ELEMENT_ID']) : 0;
$arResult['CAN_EDIT'] = CCrmQuote::CheckUpdatePermission($arResult['ELEMENT_ID'], $CCrmQuote->cPerms);
$arResult['PREFIX'] = isset($arParams['~PREFIX']) ? $arParams['~PREFIX'] : 'crm_quote_show';

$arParams['PATH_TO_QUOTE_LIST'] = CrmCheckPath('PATH_TO_QUOTE_LIST', $arParams['PATH_TO_QUOTE_LIST'], $APPLICATION->GetCurPage());
$arResult['PATH_TO_QUOTE_SHOW'] = $arParams['PATH_TO_QUOTE_SHOW'] = CrmCheckPath('PATH_TO_QUOTE_SHOW', $arParams['PATH_TO_QUOTE_SHOW'], $APPLICATION->GetCurPage().'?quote_id=#quote_id#&show');
$arParams['PATH_TO_QUOTE_EDIT'] = CrmCheckPath('PATH_TO_QUOTE_EDIT', $arParams['PATH_TO_QUOTE_EDIT'], $APPLICATION->GetCurPage().'?quote_id=#quote_id#&edit');
$arParams['PATH_TO_QUOTE_PAYMENT'] = CrmCheckPath('PATH_TO_QUOTE_PAYMENT', $arParams['PATH_TO_QUOTE_PAYMENT'], $APPLICATION->GetCurPage().'?quote_id=#quote_id#&payment');
$arParams['PATH_TO_DEAL_SHOW'] = CrmCheckPath('PATH_TO_DEAL_SHOW', $arParams['PATH_TO_DEAL_SHOW'], $APPLICATION->GetCurPage().'?deal_id=#deal_id#&show');
$arParams['PATH_TO_DEAL_EDIT'] = CrmCheckPath('PATH_TO_DEAL_EDIT', $arParams['PATH_TO_DEAL_EDIT'], $APPLICATION->GetCurPage().'?deal_id=#deal_id#&edit');
$arParams['PATH_TO_CONTACT_SHOW'] = CrmCheckPath('PATH_TO_CONTACT_SHOW', $arParams['PATH_TO_CONTACT_SHOW'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&show');
$arParams['PATH_TO_CONTACT_EDIT'] = CrmCheckPath('PATH_TO_CONTACT_EDIT', $arParams['PATH_TO_CONTACT_EDIT'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&edit');
$arParams['PATH_TO_COMPANY_SHOW'] = CrmCheckPath('PATH_TO_COMPANY_SHOW', $arParams['PATH_TO_COMPANY_SHOW'], $APPLICATION->GetCurPage().'?company_id=#company_id#&show');
$arParams['PATH_TO_COMPANY_EDIT'] = CrmCheckPath('PATH_TO_COMPANY_EDIT', $arParams['PATH_TO_COMPANY_EDIT'], $APPLICATION->GetCurPage().'?company_id=#company_id#&edit');
$arParams['PATH_TO_LEAD_SHOW'] = CrmCheckPath('PATH_TO_LEAD_SHOW', $arParams['PATH_TO_LEAD_SHOW'], $APPLICATION->GetCurPage().'?lead_id=#lead_id#&show');
$arParams['PATH_TO_LEAD_EDIT'] = CrmCheckPath('PATH_TO_LEAD_EDIT', $arParams['PATH_TO_LEAD_EDIT'], $APPLICATION->GetCurPage().'?lead_id=#lead_id#&edit');
$arParams['PATH_TO_LEAD_CONVERT'] = CrmCheckPath('PATH_TO_LEAD_CONVERT', $arParams['PATH_TO_LEAD_CONVERT'], $APPLICATION->GetCurPage().'?lead_id=#lead_id#&convert');
$arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath('PATH_TO_USER_PROFILE', $arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');
$arParams['PATH_TO_PRODUCT_EDIT'] = CrmCheckPath('PATH_TO_PRODUCT_EDIT', $arParams['PATH_TO_PRODUCT_EDIT'], $APPLICATION->GetCurPage().'?product_id=#product_id#&edit');
$arParams['PATH_TO_PRODUCT_SHOW'] = CrmCheckPath('PATH_TO_PRODUCT_SHOW', $arParams['PATH_TO_PRODUCT_SHOW'], $APPLICATION->GetCurPage().'?product_id=#product_id#&show');
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

$bTaxMode = CCrmTax::isTaxMode();

global $USER_FIELD_MANAGER;
$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmQuote::$sUFEntityID);

$obFields = CCrmQuote::GetList(
	array(),
	array(
	'ID' => $arParams['ELEMENT_ID']
	)
);
$arFields = $obFields->GetNext();

$arFields['CONTACT_FM'] = array();
if(isset($arFields['CONTACT_ID']) && intval($arFields['CONTACT_ID']) > 0)
{
	$dbResMultiFields = CCrmFieldMulti::GetList(
		array('ID' => 'asc'),
		array('ENTITY_ID' => 'CONTACT', 'ELEMENT_ID' => $arFields['CONTACT_ID'])
	);
	while($arMultiFields = $dbResMultiFields->Fetch())
	{
		$arFields['CONTACT_FM'][$arMultiFields['TYPE_ID']][$arMultiFields['ID']] = array('VALUE' => $arMultiFields['VALUE'], 'VALUE_TYPE' => $arMultiFields['VALUE_TYPE']);
	}
}

$arFields['COMPANY_FM'] = array();
if(isset($arFields['COMPANY_ID']) && intval($arFields['COMPANY_ID']) > 0)
{
	$dbResMultiFields = CCrmFieldMulti::GetList(
		array('ID' => 'asc'),
		array('ENTITY_ID' => 'COMPANY', 'ELEMENT_ID' => $arFields['COMPANY_ID'])
	);
	while($arMultiFields = $dbResMultiFields->Fetch())
	{
		$arFields['COMPANY_FM'][$arMultiFields['TYPE_ID']][$arMultiFields['ID']] = array('VALUE' => $arMultiFields['VALUE'], 'VALUE_TYPE' => $arMultiFields['VALUE_TYPE']);
	}
}

$arResult['STATUS_LIST'] = CCrmStatus::GetStatusListEx('QUOTE_STATUS');
$arResult['CURRENCY_LIST'] = CCrmCurrencyHelper::PrepareListItems();
$arResult['EVENT_LIST'] = CCrmStatus::GetStatusListEx('EVENT_TYPE');

$arFields['~STATUS_TEXT'] = isset($arFields['STATUS_ID'])
	&& isset($arResult['STATUS_LIST'][$arFields['STATUS_ID']])
	? $arResult['STATUS_LIST'][$arFields['STATUS_ID']] : '';

$arFields['STATUS_TEXT'] = htmlspecialcharsbx($arFields['~STATUS_TEXT']);

$arContactType = CCrmStatus::GetStatusListEx('CONTACT_TYPE');
$arFields['CONTACT_TYPE_TEXT'] = isset($arFields['CONTACT_TYPE_ID'])
	&& isset($arContactType[$arFields['CONTACT_TYPE_ID']])
	? $arContactType[$arFields['CONTACT_TYPE_ID']] : '';

$arContactSource = CCrmStatus::GetStatusListEx('SOURCE');
$arFields['CONTACT_SOURCE_TEXT'] = isset($arFields['CONTACT_SOURCE_ID'])
	&& isset($arContactSource[$arFields['CONTACT_SOURCE_ID']])
	? $arContactSource[$arFields['CONTACT_SOURCE_ID']] : '';

$arFields['~CONTACT_FORMATTED_NAME'] = CUser::FormatName(
	\Bitrix\Crm\Format\PersonNameFormatter::getFormat(),
	array(
		'LOGIN' => '',
		'NAME' => isset($arFields['~CONTACT_NAME']) ? $arFields['~CONTACT_NAME'] : '',
		'LAST_NAME' => isset($arFields['~CONTACT_LAST_NAME']) ? $arFields['~CONTACT_LAST_NAME'] : '',
		'SECOND_NAME' => isset($arFields['~CONTACT_SECOND_NAME']) ? $arFields['~CONTACT_SECOND_NAME'] : ''
	),
	false,
	false
);

$arFields['CONTACT_FORMATTED_NAME'] = htmlspecialcharsbx($arFields['~CONTACT_FORMATTED_NAME']);

$arCompanyIndustry = CCrmStatus::GetStatusListEx('INDUSTRY');
$arFields['COMPANY_INDUSTRY_TEXT'] = isset($arFields['COMPANY_INDUSTRY'])
	&& isset($arCompanyIndustry[$arFields['COMPANY_INDUSTRY']])
	? $arCompanyIndustry[$arFields['COMPANY_INDUSTRY']] : '';

$arCompanyEmployees = CCrmStatus::GetStatusListEx('EMPLOYEES');
$arFields['COMPANY_EMPLOYEES_TEXT'] = isset($arFields['COMPANY_EMPLOYEES'])
	&& isset($arCompanyEmployees[$arFields['COMPANY_EMPLOYEES']])
	? $arCompanyEmployees[$arFields['COMPANY_EMPLOYEES']] : '';

$arCompanyType = CCrmStatus::GetStatusListEx('COMPANY_TYPE');
$arFields['COMPANY_TYPE_TEXT'] = isset($arFields['COMPANY_TYPE'])
	&& isset($arCompanyType[$arFields['COMPANY_TYPE']])
	? $arCompanyType[$arFields['COMPANY_TYPE']] : '';

$companyLogoID = isset($arFields['~COMPANY_LOGO']) ? intval($arFields['~COMPANY_LOGO']) : 0;
if($companyLogoID <= 0)
{
	$arFields['COMPANY_LOGO_HTML'] = '';
}
else
{
	$arPhoto = CFile::ResizeImageGet(
		$companyLogoID,
		array('width' => 50, 'height' => 50),
		BX_RESIZE_IMAGE_PROPORTIONAL,
		false
	);
	$arFields['COMPANY_LOGO_HTML'] = CFile::ShowImage($arPhoto['src'], 50, 50, 'border=0');
}

$fullNameFormat = $arParams['NAME_TEMPLATE'];

$arFields['~ASSIGNED_BY_FORMATTED_NAME'] = intval($arFields['~ASSIGNED_BY_ID']) > 0
	? CUser::FormatName(
		$fullNameFormat,
		array(
			'LOGIN' => $arFields['~ASSIGNED_BY_LOGIN'],
			'NAME' => $arFields['~ASSIGNED_BY_NAME'],
			'LAST_NAME' => $arFields['~ASSIGNED_BY_LAST_NAME'],
			'SECOND_NAME' => $arFields['~ASSIGNED_BY_SECOND_NAME']
		),
		true, false
	) : GetMessage('RESPONSIBLE_NOT_ASSIGNED');

$arFields['ASSIGNED_BY_FORMATTED_NAME'] = htmlspecialcharsbx($arFields['~ASSIGNED_BY_FORMATTED_NAME']);

$arFields['~CREATED_BY_FORMATTED_NAME'] = CUser::FormatName($fullNameFormat,
	array(
		'LOGIN' => $arFields['~CREATED_BY_LOGIN'],
		'NAME' => $arFields['~CREATED_BY_NAME'],
		'LAST_NAME' => $arFields['~CREATED_BY_LAST_NAME'],
		'SECOND_NAME' => $arFields['~CREATED_BY_SECOND_NAME']
	),
	true, false
);

$arFields['CREATED_BY_FORMATTED_NAME'] = htmlspecialcharsbx($arFields['~CREATED_BY_FORMATTED_NAME']);
$arFields['PATH_TO_USER_CREATOR'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_PROFILE'],
	array(
		'user_id' => $arFields['ASSIGNED_BY']
	)
);
$arFields['~MODIFY_BY_FORMATTED_NAME'] = CUser::FormatName($fullNameFormat,
	array(
		'LOGIN' => $arFields['~MODIFY_BY_LOGIN'],
		'NAME' => $arFields['~MODIFY_BY_NAME'],
		'LAST_NAME' => $arFields['~MODIFY_BY_LAST_NAME'],
		'SECOND_NAME' => $arFields['~MODIFY_BY_SECOND_NAME']
	),
	true, false
);

$arFields['MODIFY_BY_FORMATTED_NAME'] = htmlspecialcharsbx($arFields['~MODIFY_BY_FORMATTED_NAME']);
$arFields['PATH_TO_USER_MODIFIER'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_PROFILE'],
	array(
		'user_id' => $arFields['MODIFY_BY']
	)
);
$arFields['CLIENT_INFO'] = htmlspecialcharsbx(CCrmQuote::MakeClientInfoString($arFields));

// storage type
$storageTypeId = isset($arFields['STORAGE_TYPE_ID'])
	? (int)$arFields['STORAGE_TYPE_ID'] : CCrmQuoteStorageType::Undefined;
if($storageTypeId === CCrmQuoteStorageType::Undefined
	|| !CCrmQuoteStorageType::IsDefined($storageTypeId))
{
	$storageTypeId = CCrmQuote::GetDefaultStorageTypeID();
}
$arFields['STORAGE_TYPE_ID'] = $arFields['~STORAGE_TYPE_ID'] = $storageTypeId;
$arResult['ENABLE_DISK'] = $storageTypeId === StorageType::Disk;
$arResult['ENABLE_WEBDAV'] = $storageTypeId === StorageType::WebDav;
unset($storageTypeId);

CCrmQuote::PrepareStorageElementIDs($arFields);

$arResult['ELEMENT'] = $arFields;
unset($arFields);

if (empty($arResult['ELEMENT']['ID']))
{
	LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_QUOTE_LIST'], array()));
}
$contactID = isset($arResult['ELEMENT']['CONTACT_ID']) ? intval($arResult['ELEMENT']['CONTACT_ID']) : 0;
$companyID = isset($arResult['ELEMENT']['COMPANY_ID']) ? intval($arResult['ELEMENT']['COMPANY_ID']) : 0;
$currentUserPermissions =  CCrmPerms::GetCurrentUserPermissions();
$arResult['ERROR_MESSAGE'] = '';

if (intval($_REQUEST["SYNC_ORDER_ID"]) > 0)
{
	$imp = new CCrmExternalSaleImport($arResult['ELEMENT']["ORIGINATOR_ID"]);
	if ($imp->IsInitialized())
	{
		$r = $imp->GetOrderData($arResult['ELEMENT']["ORIGIN_ID"], false);
		if ($r != CCrmExternalSaleImport::SyncStatusError)
		{
			LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_QUOTE_SHOW'], array('quote_id' => $arResult['ELEMENT']['ID'])));
		}
		else
		{
			$arErrors = $imp->GetErrors();
			foreach ($arErrors as $err)
				$arResult['ERROR_MESSAGE'] .= $err[1]."<br />";
		}
	}
}

$arResult['FORM_ID'] = 'CRM_QUOTE_SHOW_V12'/*.($isExternal ? "_E" : "")*/;
$arResult['GRID_ID'] = 'CRM_QUOTE_LIST_V12'/*.($isExternal ? "_E" : "")*/;
$arResult['PRODUCT_ROW_TAB_ID'] = 'tab_product_rows';
$arResult['BACK_URL'] = $arParams['PATH_TO_QUOTE_LIST'];

$leadID = isset($arResult['ELEMENT']['LEAD_ID']) ? intval($arResult['ELEMENT']['LEAD_ID']) : 0;
$arResult['PATH_TO_LEAD_SHOW'] = $leadID > 0
	? CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_SHOW'], array('lead_id' => $leadID))
	: '';
if ($leadID)
{
	$arResult['ELEMENT']['~LEAD_TITLE'] = CCrmOwnerType::GetCaption(CCrmOwnerType::Lead, $leadID, false);
	$arResult['ELEMENT']['LEAD_TITLE'] = htmlspecialcharsbx($arResult['ELEMENT']['~LEAD_TITLE']);
}

$dealID = isset($arResult['ELEMENT']['DEAL_ID']) ? intval($arResult['ELEMENT']['DEAL_ID']) : 0;
$arResult['PATH_TO_DEAL_SHOW'] = $dealID > 0
	? CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_SHOW'], array('deal_id' => $dealID))
	: '';
if ($dealID)
{
	$arResult['ELEMENT']['~DEAL_TITLE'] = CCrmOwnerType::GetCaption(CCrmOwnerType::Deal, $dealID, false);
	$arResult['ELEMENT']['DEAL_TITLE'] = htmlspecialcharsbx($arResult['ELEMENT']['~DEAL_TITLE']);
}

$companyID = isset($arResult['ELEMENT']['COMPANY_ID']) ? intval($arResult['ELEMENT']['COMPANY_ID']) : 0;
$arResult['PATH_TO_COMPANY_SHOW'] = $companyID > 0
	? CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_SHOW'], array('company_id' => $companyID))
	: '';

$contactID = isset($arResult['ELEMENT']['CONTACT_ID']) ? intval($arResult['ELEMENT']['CONTACT_ID']) : 0;
$arResult['PATH_TO_CONTACT_SHOW'] = $contactID > 0
	? CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_SHOW'], array('contact_id' => $contactID))
	: '';

$clientEmail = isset($arResult['ELEMENT']['CLIENT_EMAIL']) ? $arResult['ELEMENT']['CLIENT_EMAIL'] : '';
if($clientEmail !== '' && ($companyID > 0 || $contactID > 0))
{
	$clientCaption = isset($arResult['ELEMENT']['CLIENT_CONTACT']) ? $arResult['ELEMENT']['CLIENT_CONTACT'] : '';
	if($clientCaption === '')
	{
		$clientCaption = isset($arResult['ELEMENT']['CLIENT_TITLE']) ? $arResult['ELEMENT']['CLIENT_TITLE'] : '';
	}

	$comm = array(
		'TITLE' => $clientCaption,
		'TYPE' => 'EMAIL',
		'VALUE' => $clientEmail
	);

	if($contactID > 0)
	{
		$comm['ENTITY_ID'] = $contactID;
		$comm['ENTITY_TYPE'] = CCrmOwnerType::ContactName;
	}
	else
	{
		$comm['ENTITY_ID'] = $companyID;
		$comm['ENTITY_TYPE'] = CCrmOwnerType::CompanyName;
	}

	$arResult['EMAIL_COMMUNICATIONS'] = array($comm);
}
else
{
	$arResult['EMAIL_COMMUNICATIONS'] = array();
}

$arResult['EMAIL_TITLE'] = isset($arResult['ELEMENT']['TITLE'])
	? $arResult['ELEMENT']['TITLE']
	: $arResult['ELEMENT']['ID'];

$enableInstantEdit = $arResult['ENABLE_INSTANT_EDIT'] = $arResult['CAN_EDIT'];
$arResult['FIELDS'] = array();

$readOnlyMode = !$enableInstantEdit/* || $isExternal*/;

$arResult['FIELDS']['tab_1'] = array();

// QUOTE SECTION -->
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_quote_info',
	'name' => GetMessage('CRM_SECTION_QUOTE'),
	'type' => 'section',
	'isTactile' => true
);

// QUOTE_NUMBER -->
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'QUOTE_NUMBER',
	'name' => GetMessage('CRM_QUOTE_FIELD_QUOTE_NUMBER'),
	'params' => array('size' => 50),
	'value' => $arResult['ELEMENT']['~QUOTE_NUMBER'],
	'type' => 'label',
	'isTactile' => true
);
// <-- QUOTE_NUMBER

// TITLE -->
if($enableInstantEdit)
{
	$arResult['EDITABLE_FIELDS'][] = 'TITLE';
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'TITLE',
	'name' => GetMessage('CRM_QUOTE_FIELD_TITLE_QUOTE'),
	'params' => array('size' => 50),
	'value' => isset($arResult['ELEMENT']['~TITLE']) ? $arResult['ELEMENT']['~TITLE'] : '',
	'type' => 'label',
	'isTactile' => true
);
// <-- TITLE

// STATUS -->
if($enableInstantEdit)
{
	$arResult['EDITABLE_FIELDS'][] = 'STATUS_ID';
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'STATUS_ID',
	'name' => GetMessage('CRM_QUOTE_FIELD_STATUS_ID'),
	'type' => 'label',
	'value' => $arResult['ELEMENT']['~STATUS_TEXT'],
	'isTactile' => true
);
// <-- STATUS

$currencyID = CCrmCurrency::GetBaseCurrencyID();
if(isset($arResult['ELEMENT']['CURRENCY_ID']) && $arResult['ELEMENT']['CURRENCY_ID'] !== '')
{
	$currencyID = $arResult['ELEMENT']['CURRENCY_ID'];
}

// CURRENCY -->
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'CURRENCY_ID',
	'name' => GetMessage('CRM_QUOTE_FIELD_CURRENCY_ID'),
	'params' => array('size' => 50),
	'type' => 'label',
	'value' => isset($arResult['CURRENCY_LIST'][$currencyID]) ? $arResult['CURRENCY_LIST'][$currencyID] : $currencyID,
	'isTactile' => true
);
// <-- CURRENCY

// OPPORTUNITY -->
if($enableInstantEdit)
{
	$arResult['EDITABLE_FIELDS'][] = 'OPPORTUNITY';
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'OPPORTUNITY',
	'name' => GetMessage('CRM_QUOTE_FIELD_OPPORTUNITY'),
	'type' => 'label',
	'params' => array('size' => 50),
	'value' => isset($arResult['ELEMENT']['OPPORTUNITY']) ? CCrmCurrency::MoneyToString($arResult['ELEMENT']['OPPORTUNITY'], $currencyID, '#') : '',
	'isTactile' => true
);
// <-- OPPORTUNITY

// ASSIGNED_BY_ID -->
if($enableInstantEdit)
{
	$arResult['EDITABLE_FIELDS'][] = 'ASSIGNED_BY_ID';
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'ASSIGNED_BY_ID',
	'name' => GetMessage('CRM_QUOTE_FIELD_ASSIGNED_BY_ID'),
	'type' => 'custom',
	'value' => CCrmViewHelper::PrepareFormResponsible($arResult['ELEMENT']['~ASSIGNED_BY_ID'], $arParams['NAME_TEMPLATE'], $arParams['PATH_TO_USER_PROFILE']),
	'isTactile' => true
);
// <-- ASSIGNED_BY_ID

// BEGINDATE -->
if($enableInstantEdit)
{
	$arResult['EDITABLE_FIELDS'][] = 'BEGINDATE';
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'BEGINDATE',
	'name' => GetMessage('CRM_QUOTE_FIELD_BEGINDATE'),
	'params' => array('size' => 20),
	'type' => 'label',
	'value' => !empty($arResult['ELEMENT']['~BEGINDATE']) ? CCrmComponentHelper::TrimDateTimeString(ConvertTimeStamp(MakeTimeStamp($arResult['ELEMENT']['~BEGINDATE']), 'SHORT', SITE_ID)) : '',
	'isTactile' => true
);
//<-- BEGINDATE

// CLOSEDATE -->
if($enableInstantEdit)
{
	$arResult['EDITABLE_FIELDS'][] = 'CLOSEDATE';
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'CLOSEDATE',
	'name' => GetMessage('CRM_QUOTE_FIELD_CLOSEDATE'),
	'params' => array('size' => 20),
	'type' => 'label',
	'value' => !empty($arResult['ELEMENT']['~CLOSEDATE']) ? CCrmComponentHelper::TrimDateTimeString(ConvertTimeStamp(MakeTimeStamp($arResult['ELEMENT']['~CLOSEDATE']), 'SHORT', SITE_ID)) : '',
	'isTactile' => true
);
//<-- CLOSEDATE

// LEAD_ID -->
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'LEAD_ID',
	'name' => GetMessage('CRM_QUOTE_FIELD_LEAD_ID'),
	'value' => isset($arResult['ELEMENT']['LEAD_TITLE'])
		? ($CCrmQuote->cPerms->HavePerm('LEAD', BX_CRM_PERM_NONE, 'READ')
			? $arResult['ELEMENT']['LEAD_TITLE'] :
			'<a href="'.$arResult['PATH_TO_LEAD_SHOW'].'" id="balloon_'.$arResult['GRID_ID'].'_L_'.$arResult['ELEMENT']['LEAD_ID'].'">'.$arResult['ELEMENT']['LEAD_TITLE'].'</a>'.
			'<script type="text/javascript">BX.tooltip("LEAD_'.$arResult['ELEMENT']['~LEAD_ID'].'", "balloon_'.$arResult['GRID_ID'].'_L_'.$arResult['ELEMENT']['LEAD_ID'].'", "/bitrix/components/bitrix/crm.lead.show/card.ajax.php", "crm_balloon_lead", true);</script>'
		) : '',
	'type' => 'custom',
	'isTactile' => true
);
//<-- LEAD_ID

// DEAL_ID -->
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'DEAL_ID',
	'name' => GetMessage('CRM_QUOTE_FIELD_DEAL_ID'),
	'value' => isset($arResult['ELEMENT']['DEAL_TITLE'])
		? ($CCrmQuote->cPerms->HavePerm('DEAL', BX_CRM_PERM_NONE, 'READ')
			? $arResult['ELEMENT']['DEAL_TITLE'] :
			'<a href="'.$arResult['PATH_TO_DEAL_SHOW'].'" id="balloon_'.$arResult['GRID_ID'].'_D_'.$arResult['ELEMENT']['DEAL_ID'].'">'.$arResult['ELEMENT']['DEAL_TITLE'].'</a>'.
				'<script type="text/javascript">BX.tooltip("DEAL_'.$arResult['ELEMENT']['~DEAL_ID'].'", "balloon_'.$arResult['GRID_ID'].'_D_'.$arResult['ELEMENT']['DEAL_ID'].'", "/bitrix/components/bitrix/crm.deal.show/card.ajax.php", "crm_balloon_deal", true);</script>'
		) : '',
	'type' => 'custom',
	'isTactile' => true
);
//<-- DEAL_ID

// OPENED -->
if($enableInstantEdit)
{
	$arResult['EDITABLE_FIELDS'][] = 'OPENED';
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'OPENED',
	'name' => GetMessage('CRM_QUOTE_FIELD_OPENED'),
	'type' => 'label',
	'params' => array(),
	'value' => $arResult['ELEMENT']['~OPENED'] == 'Y' ? GetMessage('MAIN_YES') : GetMessage('MAIN_NO'),
	'isTactile' => true
);
// <-- OPENED
//<-- QUOTE SECTION

// CONTACT INFO SECTION -->
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_contact_info',
	'name' => GetMessage('CRM_SECTION_CLIENT_INFO'),
	'type' => 'section',
	'isTactile' => true
);

// CONTACT_ID -->
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'CONTACT_ID',
	'name' => GetMessage('CRM_QUOTE_FIELD_CONTACT_TITLE'),
	'value' => isset($arResult['ELEMENT']['CONTACT_FULL_NAME'])
		? ($CCrmQuote->cPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'READ')
			? $arResult['ELEMENT']['CONTACT_FULL_NAME'] :
			'<a href="'.$arResult['PATH_TO_CONTACT_SHOW'].'" id="balloon_'.$arResult['GRID_ID'].'_C_'.$arResult['ELEMENT']['CONTACT_ID'].'">'.$arResult['ELEMENT']['CONTACT_FULL_NAME'].'</a>'.
				'<script type="text/javascript">BX.tooltip("CONTACT_'.$arResult['ELEMENT']['~CONTACT_ID'].'", "balloon_'.$arResult['GRID_ID'].'_C_'.$arResult['ELEMENT']['CONTACT_ID'].'", "/bitrix/components/bitrix/crm.contact.show/card.ajax.php", "crm_balloon_contact", true);</script>'
		) : '',
	'type' => 'custom',
	'isTactile' => true
);
//<-- CONTACT_ID

// COMAPANY_ID -->
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'COMPANY_ID',
	'name' => GetMessage('CRM_QUOTE_FIELD_COMPANY_TITLE'),
	'value' => isset($arResult['ELEMENT']['COMPANY_TITLE'])
		? ($CCrmQuote->cPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE, 'READ')
			? $arResult['ELEMENT']['COMPANY_TITLE'] :
			'<a href="'.$arResult['PATH_TO_COMPANY_SHOW'].'" id="balloon_'.$arResult['GRID_ID'].'_CO_'.$arResult['ELEMENT']['COMPANY_ID'].'">'.$arResult['ELEMENT']['COMPANY_TITLE'].'</a>'.
				'<script type="text/javascript">BX.tooltip("COMPANY_'.$arResult['ELEMENT']['~COMPANY_ID'].'", "balloon_'.$arResult['GRID_ID'].'_CO_'.$arResult['ELEMENT']['COMPANY_ID'].'", "/bitrix/components/bitrix/crm.company.show/card.ajax.php", "crm_balloon_company", true);</script>'
		) : '',
	'type' => 'custom',
	'isTactile' => true
);
//<-- COMAPANY_ID

// LOCATION_ID -->
if ($bTaxMode)
{
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'LOCATION_ID',
		'name' => GetMessage('CRM_QUOTE_FIELD_LOCATION_ID'),
		'params' => array('size' => 50),
		'type' => 'label',
		'value' => isset($arResult['ELEMENT']['LOCATION_ID']) ? CCrmLocations::getLocationString($arResult['ELEMENT']['LOCATION_ID']) : '',
		'isTactile' => true
	);
}
//<-- LOCATION_ID

// Client fields
foreach (CCrmQuote::GetClientFields() as $fieldName)
{
	if($fieldName === 'CLIENT_TPA_ID' && LANGUAGE_ID !== 'ru')
	{
		continue;
	}

	$clientField = array(
		'id' => $fieldName,
		'name' => GetMessage('CRM_QUOTE_FIELD_'.$fieldName),
		'params' => array('size' => 255),
		'value' => isset($arResult['ELEMENT']['~'.$fieldName]) ? $arResult['ELEMENT']['~'.$fieldName] : '',
		'type' => 'label',
		'isTactile' => true
	);

	if($fieldName === 'CLIENT_CONTACT')
	{
		$clientField['visible'] = isset($arPersonTypes['COMPANY']) && $arResult['ELEMENT']['PERSON_TYPE_ID'] == $arPersonTypes['COMPANY'];
	}

	$arResult['FIELDS']['tab_1'][] = $clientField;
}
unset($bHideClientContact, $clientField, $fieldName);

// FILES -->
$arResult['FILES_FIELD_CONTAINER_ID'] = $arResult['FORM_ID'].'_FILES_CONTAINER';
$sVal = '<div id="'.$arResult['FILES_FIELD_CONTAINER_ID'].'"></div>';
if ($arResult['ENABLE_WEBDAV'] || $arResult['ENABLE_DISK'])
{
	$sVal = '<div id="'.$arResult['FILES_FIELD_CONTAINER_ID'].'" class="bx-crm-dialog-activity-webdav-container"></div>';
}
else
{
	ob_start();
	$APPLICATION->IncludeComponent(
		'bitrix:main.file.input',
		'',
		array(
			'MODULE_ID' => 'crm',
			'MAX_FILE_SIZE' => 20971520,
			'ALLOW_UPLOAD' => 'N',
			'CONTROL_ID' => $arResult['PREFIX'].'_uploader',
			'INPUT_NAME' => $arResult['PREFIX'].'_saved_file',
			'INPUT_NAME_UNSAVED' => $arResult['PREFIX'].'_new_file',
			'INPUT_VALUE' => $arResult['ELEMENT']['STORAGE_ELEMENT_IDS']
		),
		null,
		array('HIDE_ICONS' => 'Y')
	);
	$sVal = ob_get_contents();
	ob_end_clean();
	$sVal = '<div id="'.$arResult['FILES_FIELD_CONTAINER_ID'].'" class="bx-crm-dialog-activity-webdav-container">'.
		'<div id="'.$arResult['PREFIX'].'_upload_container"'.
		($arResult['ENABLE_WEBDAV'] ? ' style="display:none;"' : '').'>'.$sVal.'</div></div>';
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'FILES',
	'name' => GetMessage('CRM_QUOTE_FIELD_FILES'),
	'type' => 'custom',
	'value' => $sVal,
	'params' => array(),
	'isTactile' => true
);
$arResult['FILES_FILED_VALUE'] = $sVal;
// <-- FILES

// CONTENT -->
if($enableInstantEdit)
{
	$arResult['EDITABLE_FIELDS'][] = 'CONTENT';
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'CONTENT',
	'name' => GetMessage('CRM_QUOTE_FIELD_CONTENT'),
	'type' => 'custom',
	'value' => isset($arResult['ELEMENT']['~CONTENT']) ? $arResult['ELEMENT']['~CONTENT'] : '',
	'params' => array(),
	'isTactile' => true
);
// <-- CONTENT

// TERMS -->
if($enableInstantEdit)
{
	$arResult['EDITABLE_FIELDS'][] = 'TERMS';
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'TERMS',
	'name' => GetMessage('CRM_QUOTE_FIELD_TERMS'),
	'type' => 'custom',
	'value' => isset($arResult['ELEMENT']['~TERMS']) ? $arResult['ELEMENT']['~TERMS'] : '',
	'params' => array(),
	'isTactile' => true
);
// <-- TERMS

// CLOSED -->
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'CLOSED',
	'name' => GetMessage('CRM_QUOTE_FIELD_CLOSED'),
	'type' => 'label',
	'value' => (isset($arResult['ELEMENT']['CLOSED']) ? ($arResult['ELEMENT']['CLOSED'] == 'Y' ? GetMessage('MAIN_YES') : GetMessage('MAIN_NO')) : GetMessage('MAIN_NO')),
	'isTactile' => true
);
// <-- CLOSED

// ADDITIONAL SECTION -->
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_additional',
	'name' => GetMessage('CRM_SECTION_ADDITIONAL'),
	'type' => 'section',
	'isTactile' => true
);

// COMMENTS -->
if($enableInstantEdit)
{
	$arResult['EDITABLE_FIELDS'][] = 'COMMENTS';
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'COMMENTS',
	'name' => GetMessage('CRM_QUOTE_FIELD_COMMENTS'),
	'type' => 'custom',
	'value' => isset($arResult['ELEMENT']['~COMMENTS']) ? $arResult['ELEMENT']['~COMMENTS'] : '',
	'params' => array(),
	'isTactile' => true
);
// <-- COMMENTS

ob_start();
$APPLICATION->IncludeComponent('bitrix:main.user.link',
	'',
	array(
		'ID' => $arResult['ELEMENT']['CREATED_BY'],
		'HTML_ID' => 'crm_created_by',
		'USE_THUMBNAIL_LIST' => 'Y',
		'SHOW_YEAR' => 'M',
		'CACHE_TYPE' => 'A',
		'CACHE_TIME' => '3600',
		'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
		'SHOW_LOGIN' => 'Y',
	),
	false,
	array('HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT'=>'Y')
);
$sVal = ob_get_contents();
ob_end_clean();
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'CREATED_BY_ID',
	'name' => GetMessage('CRM_QUOTE_FIELD_CREATED_BY_ID'),
	'type' => 'custom',
	'value' => $sVal
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'DATE_CREATE',
	'name' => GetMessage('CRM_QUOTE_FIELD_DATE_CREATE'),
	'params' => array('size' => 50),
	'type' => 'label',
	'value' => isset($arResult['ELEMENT']['DATE_CREATE']) ? FormatDate('x', MakeTimeStamp($arResult['ELEMENT']['DATE_CREATE']), (time() + CTimeZone::GetOffset())) : ''
);

if ($arResult['ELEMENT']['DATE_CREATE'] != $arResult['ELEMENT']['DATE_MODIFY'])
{
	ob_start();
	$APPLICATION->IncludeComponent('bitrix:main.user.link',
		'',
		array(
			'ID' => $arResult['ELEMENT']['MODIFY_BY'],
			'HTML_ID' => 'crm_modify_by',
			'USE_THUMBNAIL_LIST' => 'Y',
			'SHOW_YEAR' => 'M',
			'CACHE_TYPE' => 'A',
			'CACHE_TIME' => '3600',
			'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
			'SHOW_LOGIN' => 'Y',
		),
		false,
		array('HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT'=>'Y')
	);
	$sVal = ob_get_contents();
	ob_end_clean();
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'MODIFY_BY_ID',
		'name' => GetMessage('CRM_QUOTE_FIELD_MODIFY_BY_ID'),
		'type' => 'custom',
		'value' => $sVal
	);
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'DATE_MODIFY',
		'name' => GetMessage('CRM_QUOTE_FIELD_DATE_MODIFY'),
		'params' => array('size' => 50),
		'type' => 'label',
		'value' => isset($arResult['ELEMENT']['DATE_MODIFY']) ? FormatDate('x', MakeTimeStamp($arResult['ELEMENT']['DATE_MODIFY']), (time() + CTimeZone::GetOffset())) : ''
	);
}

$arResult['USER_FIELD_COUNT'] = $CCrmUserType->AddFields(
	$arResult['FIELDS']['tab_1'],
	$arResult['ELEMENT']['ID'],
	$arResult['FORM_ID'],
	false,
	true,
	false,
	array(
		'FILE_URL_TEMPLATE' =>
			"/bitrix/components/bitrix/crm.quote.show/show_file.php?ownerId=#owner_id#&fieldName=#field_name#&fileId=#file_id#",
		'IS_TACTILE' => true
	)
);
// <-- ADDITIONAL SECTION

// PRODUCT ROW SECTION -->
$arResult['FIELDS'][$arResult['PRODUCT_ROW_TAB_ID']][] = array(
	'id' => 'section_product_rows',
	'name' => GetMessage('CRM_SECTION_PRODUCT_ROWS'),
	'type' => 'section'
);

$sProductsHtml = '';

$arResult['PRODUCT_ROW_EDITOR_ID'] = 'quote_'.strval($arParams['ELEMENT_ID']).'_product_editor';
if($arParams['ELEMENT_ID'] > 0)
{
	// Determine person type
	$arPersonTypes = CCrmPaySystem::getPersonTypeIDs();
	$personTypeId = 0;
	if (isset($arPersonTypes['COMPANY']) && isset($arPersonTypes['CONTACT']))
	{
		if (intval($arResult['ELEMENT']['COMPANY_ID']) > 0)
			$personTypeId = $arPersonTypes['COMPANY'];
		elseif (intval($arResult['ELEMENT']['CONTACT_ID']) > 0)
			$personTypeId = $arPersonTypes['CONTACT'];
	}

	ob_start();
	$APPLICATION->IncludeComponent('bitrix:crm.product_row.list',
		'',
		array(
			'ID' => $arResult['PRODUCT_ROW_EDITOR_ID'],
			'FORM_ID' => $arResult['FORM_ID'],
			'OWNER_ID' => $arParams['ELEMENT_ID'],
			'OWNER_TYPE' => CCrmQuote::OWNER_TYPE,
			'PERMISSION_TYPE' => $enableInstantEdit/* && !$isExternal*/ ? 'WRITE' : 'READ',
			'PRODUCT_ROWS' => isset($arResult['PRODUCT_ROWS']) ? $arResult['PRODUCT_ROWS'] : null,
			'PERSON_TYPE_ID' => $personTypeId,
			'CURRENCY_ID' => $currencyID,
			'LOCATION_ID' => $bTaxMode ? $arResult['ELEMENT']['LOCATION_ID'] : '',
			'TOTAL_SUM' => isset($arResult['ELEMENT']['OPPORTUNITY']) ? $arResult['ELEMENT']['OPPORTUNITY'] : null,
			'TOTAL_TAX' => isset($arResult['ELEMENT']['TAX_VALUE']) ? $arResult['ELEMENT']['TAX_VALUE'] : null,
			'PATH_TO_PRODUCT_EDIT' => $arParams['PATH_TO_PRODUCT_EDIT'],
			'PATH_TO_PRODUCT_SHOW' => $arParams['PATH_TO_PRODUCT_SHOW']
		),
		false,
		array('HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT'=>'Y')
	);
	$sProductsHtml .= ob_get_contents();
	ob_end_clean();
}

$arResult['FIELDS'][$arResult['PRODUCT_ROW_TAB_ID']][] = array(
	'id' => 'PRODUCT_ROWS',
	'name' => GetMessage('CRM_QUOTE_FIELD_PRODUCT_ROWS'),
	'colspan' => true,
	'type' => 'custom',
	'value' => $sProductsHtml
);
// <-- PRODUCT ROW SECTION

$formTabKey = $arResult['FORM_ID'].'_active_tab';
$currentFormTabID = $_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET[$formTabKey]) ? $_GET[$formTabKey] : '';

if (!$CCrmQuote->cPerms->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'READ'))
{
	$arResult['FIELDS']['tab_invoice'][] = array(
		'id' => 'QUOTE_INVOICE',
		'name' => GetMessage('CRM_QUOTE_FIELD_QUOTE_INVOICE'),
		'colspan' => true,
		'type' => 'crm_invoice_list',
		'componentData' => array(
			'template' => '',
			'enableLazyLoad' => true,
			'params' => array(
				'INVOICE_COUNT' => '20',
				'PATH_TO_COMPANY_SHOW' => $arParams['PATH_TO_COMPANY_SHOW'],
				'PATH_TO_COMPANY_EDIT' => $arParams['PATH_TO_COMPANY_EDIT'],
				'PATH_TO_CONTACT_EDIT' => $arParams['PATH_TO_CONTACT_EDIT'],
				'PATH_TO_QUOTE_EDIT' => $arParams['PATH_TO_QUOTE_EDIT'],
				'PATH_TO_INVOICE_EDIT' => $arParams['PATH_TO_INVOICE_EDIT'],
				'PATH_TO_INVOICE_PAYMENT' => $arParams['PATH_TO_INVOICE_PAYMENT'],
				'INTERNAL_FILTER' => array('UF_QUOTE_ID' => $arResult['ELEMENT']['ID']),
				'SUM_PAID_CURRENCY' => $currencyID,
				'GRID_ID_SUFFIX' => 'QUOTE_SHOW',
				'FORM_ID' => $arResult['FORM_ID'],
				'TAB_ID' => 'tab_invoice',
				'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
				'ENABLE_TOOLBAR' => 'Y',
				'INTERNAL_ADD_BTN_TITLE' => GetMessage('CRM_QUOTE_ADD_INVOICE_TITLE')
			)
		)
	);
}

$arResult['FIELDS']['tab_event'][] = array(
	'id' => 'section_event_grid',
	'name' => GetMessage('CRM_SECTION_EVENT_MAIN'),
	'type' => 'section'
);

ob_start();
$arResult['EVENT_COUNT'] = $APPLICATION->IncludeComponent(
	'bitrix:crm.event.view',
	'',
	array(
		'ENTITY_TYPE' => 'QUOTE',
		'ENTITY_ID' => $arResult['ELEMENT']['ID'],
		'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],
		'FORM_ID' => $arResult['FORM_ID'],
		'TAB_ID' => 'tab_event',
		'INTERNAL' => 'Y',
		'SHOW_INTERNAL_FILTER' => 'Y',
		'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
	),
	false
);
$sVal = ob_get_contents();
ob_end_clean();
$arResult['FIELDS']['tab_event'][] = array(
	'id' => 'QUOTE_EVENT',
	'name' => GetMessage('CRM_QUOTE_FIELD_QUOTE_EVENT'),
	'colspan' => true,
	'type' => 'custom',
	'value' => $sVal
);

$arResult['PRINT_URL'] = CHTTP::urlAddParams(
	CComponentEngine::MakePathFromTemplate(
		$arParams['PATH_TO_QUOTE_PAYMENT'],
		array('quote_id' => $arParams['ELEMENT_ID'])
	),
	array('PRINT' => 'Y', 'ncc' => '1')
);

$arResult['DOWNLOAD_PDF_URL'] = CHTTP::urlAddParams(
	CComponentEngine::MakePathFromTemplate(
		$arParams['PATH_TO_QUOTE_PAYMENT'],
		array('quote_id' => $arParams['ELEMENT_ID'])
	),
	array('pdf'=> '1', 'DOWNLOAD' => 'Y', 'ncc' => '1')
);

$arResult['CREATE_PDF_FILE_URL'] = "{$componentPath}/ajax.php";
$paySystems = CCrmPaySystem::GetPaySystems($arResult['ELEMENT']['PERSON_TYPE_ID']);
if(is_array($paySystems))
{
	$quotePaySystemInfos = array();
	foreach($paySystems as &$paySystem)
	{
		$file = isset($paySystem['~PSA_ACTION_FILE']) ? $paySystem['~PSA_ACTION_FILE'] : '';
		if(strpos($file, '/quote') !== false)
		{
			$quotePaySystemInfos[] = array(
				'ID' => intval($paySystem['~PSA_ID']),
				'NAME' => $paySystem['~PSA_NAME']
			);
		}
	}
	unset($paySystem);
}
$arResult['PRINT_TEMPLATES'] = $quotePaySystemInfos;

// HACK: for to prevent title overwrite after AJAX call.
if(isset($_REQUEST['bxajaxid']))
{
	$APPLICATION->SetTitle('');
}
$this->IncludeComponentTemplate();
include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.quote/include/nav.php');
?>
