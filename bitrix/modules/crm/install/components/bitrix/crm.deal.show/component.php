<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

// 'Fileman' module always installed
CModule::IncludeModule('fileman');

$CCrmDeal = new CCrmDeal();
if ($CCrmDeal->cPerms->HavePerm('DEAL', BX_CRM_PERM_NONE, 'READ'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

CUtil::InitJSCore(array('ajax', 'tooltip'));

$arResult['EDITABLE_FIELDS'] = array();
$arResult['ELEMENT_ID'] = $arParams['ELEMENT_ID'] = isset($arParams['ELEMENT_ID']) ? intval($arParams['ELEMENT_ID']) : 0;
$arResult['CAN_EDIT'] = CCrmDeal::CheckUpdatePermission($arResult['ELEMENT_ID'], $CCrmDeal->cPerms);

$arParams['PATH_TO_DEAL_LIST'] = CrmCheckPath('PATH_TO_DEAL_LIST', $arParams['PATH_TO_DEAL_LIST'], $APPLICATION->GetCurPage());
$arResult['PATH_TO_DEAL_SHOW'] = $arParams['PATH_TO_DEAL_SHOW'] = CrmCheckPath('PATH_TO_DEAL_SHOW', $arParams['PATH_TO_DEAL_SHOW'], $APPLICATION->GetCurPage().'?deal_id=#deal_id#&show');
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

global $USER_FIELD_MANAGER;
$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmDeal::$sUFEntityID);

$obFields = CCrmDeal::GetListEx(
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

$arResult['STAGE_LIST'] = CCrmStatus::GetStatusListEx('DEAL_STAGE');
$arResult['CURRENCY_LIST'] = CCrmCurrencyHelper::PrepareListItems();
$arResult['STATE_LIST'] = CCrmStatus::GetStatusListEx('DEAL_STATE');
$arResult['TYPE_LIST'] = CCrmStatus::GetStatusListEx('DEAL_TYPE');
$arResult['EVENT_LIST'] = CCrmStatus::GetStatusListEx('EVENT_TYPE');
//$arResult['PRODUCT_ROWS'] = CCrmDeal::LoadProductRows($arParams['ELEMENT_ID']);

$arFields['TYPE_TEXT'] = isset($arFields['TYPE_ID'])
	&& isset($arResult['TYPE_LIST'][$arFields['TYPE_ID']])
	? $arResult['TYPE_LIST'][$arFields['TYPE_ID']] : '';

$arFields['~STAGE_TEXT'] = isset($arFields['STAGE_ID'])
	&& isset($arResult['STAGE_LIST'][$arFields['STAGE_ID']])
	? $arResult['STAGE_LIST'][$arFields['STAGE_ID']] : '';

$arFields['STAGE_TEXT'] = htmlspecialcharsbx($arFields['~STAGE_TEXT']);

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

$arFields['ORIGIN_ID'] = isset($arFields['ORIGIN_ID']) ? intval($arFields['ORIGIN_ID']) : 0;
$arFields['ORIGINATOR_ID'] = isset($arFields['ORIGINATOR_ID']) ? intval($arFields['ORIGINATOR_ID']) : 0;

$arResult['ELEMENT'] = $arFields;
unset($arFields);

if (empty($arResult['ELEMENT']['ID']))
{
	LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_LIST'], array()));
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
			LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_SHOW'], array('deal_id' => $arResult['ELEMENT']['ID'])));
		}
		else
		{
			$arErrors = $imp->GetErrors();
			foreach ($arErrors as $err)
				$arResult['ERROR_MESSAGE'] .= $err[1]."<br />";
		}
	}
}

$isExternal = $arResult['IS_EXTERNAL'] = $arResult['ELEMENT']['ORIGINATOR_ID'] > 0 && $arResult['ELEMENT']['ORIGIN_ID'] > 0;

$arResult['FORM_ID'] = 'CRM_DEAL_SHOW_V12'.($isExternal ? "_E" : "");
$arResult['GRID_ID'] = 'CRM_DEAL_LIST_V12'.($isExternal ? "_E" : "");
$arResult['PRODUCT_ROW_TAB_ID'] = 'tab_product_rows';
$arResult['BACK_URL'] = $arParams['PATH_TO_DEAL_LIST'];

$arResult['PATH_TO_COMPANY_SHOW'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_SHOW'],
	array(
		'company_id' => $arResult['ELEMENT']['COMPANY_ID']
	)
);
$arResult['PATH_TO_CONTACT_SHOW'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_SHOW'],
	array(
		'contact_id' => $arResult['ELEMENT']['CONTACT_ID']
	)
);
$enableInstantEdit = $arResult['ENABLE_INSTANT_EDIT'] = $arResult['CAN_EDIT'];
$arResult['FIELDS'] = array();

$readOnlyMode = !$enableInstantEdit || $isExternal;

$arResult['FIELDS']['tab_1'] = array();

// DEAL SECTION -->
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_deal_info',
	'name' => GetMessage('CRM_SECTION_DEAL'),
	'type' => 'section',
	'isTactile' => true
);


// TITLE -->
// TITLE is displayed in summary panel. The field is added for COMPATIBILITY ONLY
if($enableInstantEdit)
{
	$arResult['EDITABLE_FIELDS'][] = 'TITLE';
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'TITLE',
	'name' => GetMessage('CRM_FIELD_TITLE_DEAL'),
	'params' => array('size' => 50),
	'value' => isset($arResult['ELEMENT']['~TITLE']) ? $arResult['ELEMENT']['~TITLE'] : '',
	'type' => 'label',
	'isTactile' => true
);
// <-- TITLE

// STAGE -->
// STAGE is displayed in summary panel. The field is added for COMPATIBILITY ONLY
if($enableInstantEdit)
{
	$arResult['EDITABLE_FIELDS'][] = 'STAGE_ID';
}

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'STAGE_ID',
	'name' => GetMessage('CRM_FIELD_STAGE_ID'),
	'type' => 'label',
	'value' => $arResult['ELEMENT']['~STAGE_TEXT'],
	'isTactile' => true
);
// <-- STAGE

// CURRENCY -->
$currencyID = CCrmCurrency::GetBaseCurrencyID();
if(isset($arResult['ELEMENT']['CURRENCY_ID']) && $arResult['ELEMENT']['CURRENCY_ID'] !== '')
{
	$currencyID = $arResult['ELEMENT']['CURRENCY_ID'];
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'CURRENCY_ID',
	'name' => GetMessage('CRM_FIELD_CURRENCY_ID'),
	'type' => 'label',
	'value' => CCrmCurrency::GetCurrencyName($currencyID),
	'isTactile' => true
);
// <-- CURRENCY

// OPPORTUNITY -->
// OPPORTUNITY is displayed in sidebar. The field is added for COMPATIBILITY ONLY
if($enableInstantEdit && !$isExternal)
{
	$arResult['EDITABLE_FIELDS'][] = 'OPPORTUNITY';
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'OPPORTUNITY',
	'name' => GetMessage('CRM_FIELD_OPPORTUNITY'),
	'type' => 'label',
	'params' => array('size' => 50),
	'value' => isset($arResult['ELEMENT']['OPPORTUNITY']) ? CCrmCurrency::MoneyToString($arResult['ELEMENT']['OPPORTUNITY'], $currencyID, '#') : '',
	'isTactile' => true
);
// <-- OPPORTUNITY

// PROBABILITY -->
// PROBABILITY is displayed in sidebar. The field is added for COMPATIBILITY ONLY
if($enableInstantEdit && !$isExternal)
{
	$arResult['EDITABLE_FIELDS'][] = 'PROBABILITY';
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'PROBABILITY',
	'name' => GetMessage('CRM_FIELD_PROBABILITY'),
	'type' => 'label',
	'params' => array('size' => 50),
	'value' => isset($arResult['ELEMENT']['~PROBABILITY']) ? $arResult['ELEMENT']['~PROBABILITY'] : '',
	'isTactile' => true
);
// <-- PROBABILITY

// ASSIGNED_BY_ID is displayed in sidebar. The field is added for COMPATIBILITY ONLY
if($enableInstantEdit)
{
	$arResult['EDITABLE_FIELDS'][] = 'ASSIGNED_BY_ID';
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'ASSIGNED_BY_ID',
	'name' => GetMessage('CRM_FIELD_ASSIGNED_BY_ID'),
	'type' => 'custom',
	'value' => CCrmViewHelper::PrepareFormResponsible($arResult['ELEMENT']['~ASSIGNED_BY_ID'], $arParams['NAME_TEMPLATE'], $arParams['PATH_TO_USER_PROFILE']),
	'isTactile' => true
);
// <-- ASSIGNED_BY_ID

if($enableInstantEdit)
{
	$arResult['EDITABLE_FIELDS'][] = 'BEGINDATE';
}

$arResult['FIELDS']['tab_details'][] = array(
	'id' => 'BEGINDATE',
	'name' => GetMessage('CRM_FIELD_BEGINDATE'),
	'params' => array('size' => 20),
	'type' => 'label',
	'value' => !empty($arResult['ELEMENT']['BEGINDATE']) ? CCrmComponentHelper::TrimDateTimeString(ConvertTimeStamp(MakeTimeStamp($arResult['ELEMENT']['BEGINDATE']), 'SHORT', SITE_ID)) : '',
	'isTactile' => true
);

if($enableInstantEdit)
{
	$arResult['EDITABLE_FIELDS'][] = 'CLOSEDATE';
}

$arResult['FIELDS']['tab_details'][] = array(
	'id' => 'CLOSEDATE',
	'name' => GetMessage('CRM_FIELD_CLOSEDATE'),
	'params' => array('size' => 20),
	'type' => 'label',
	'value' => !empty($arResult['ELEMENT']['CLOSEDATE']) ? CCrmComponentHelper::TrimDateTimeString(ConvertTimeStamp(MakeTimeStamp($arResult['ELEMENT']['CLOSEDATE']), 'SHORT', SITE_ID)) : '',
	'isTactile' => true
);

// TYPE -->
if($enableInstantEdit)
{
	$arResult['EDITABLE_FIELDS'][] = 'TYPE_ID';
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'TYPE_ID',
	'name' => GetMessage('CRM_FIELD_TYPE_ID'),
	'type' => 'label',
	'items' => $arResult['TYPE_LIST'],
	'value' => $arResult['ELEMENT']['TYPE_TEXT'],
	'isTactile' => true
);
// <-- TYPE

// CLOSED -->
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'CLOSED',
	'name' => GetMessage('CRM_FIELD_CLOSED'),
	'type' => 'label',
	'value' => (isset($arResult['ELEMENT']['CLOSED']) ? ($arResult['ELEMENT']['CLOSED'] == 'Y' ? GetMessage('MAIN_YES') : GetMessage('MAIN_NO')) : GetMessage('MAIN_NO')),
	'isTactile' => true
);
// <-- CLOSED

// OPENED -->
// OPENED is displayed in sidebar. The field is added for COMPATIBILITY ONLY
if($enableInstantEdit)
{
	$arResult['EDITABLE_FIELDS'][] = 'OPENED';
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'OPENED',
	'name' => GetMessage('CRM_FIELD_OPENED'),
	'type' => 'label',
	'params' => array(),
	'value' => $arResult['ELEMENT']['OPENED'] == 'Y' ? GetMessage('MAIN_YES') : GetMessage('MAIN_NO'),
	'isTactile' => true
);
// <-- OPENED

// COMMENTS -->
if($enableInstantEdit)
{
	$arResult['EDITABLE_FIELDS'][] = 'COMMENTS';
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'COMMENTS',
	'name' => GetMessage('CRM_FIELD_COMMENTS'),
	'type' => 'custom',
	'value' => isset($arResult['ELEMENT']['~COMMENTS']) ? $arResult['ELEMENT']['~COMMENTS'] : '',
	'params' => array(),
	'isTactile' => true
);
// <-- COMMENTS


// <-- DEAL SECTION

// CONTACT INFO SECTION -->
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_contact_info',
	'name' => GetMessage('CRM_SECTION_CONTACT_INFO'),
	'type' => 'section',
	'isTactile' => true
);

// CONTACT TITLE
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'CONTACT_ID',
	'name' => GetMessage('CRM_FIELD_CONTACT_TITLE'),
	'value' => isset($arResult['ELEMENT']['CONTACT_FULL_NAME'])
		? ($CCrmDeal->cPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'READ')
			? $arResult['ELEMENT']['CONTACT_FULL_NAME'] :
			'<a href="'.$arResult['PATH_TO_CONTACT_SHOW'].'" id="balloon_'.$arResult['GRID_ID'].'_C_'.$arResult['ELEMENT']['CONTACT_ID'].'">'.$arResult['ELEMENT']['CONTACT_FULL_NAME'].'</a>'.
				'<script type="text/javascript">BX.tooltip("CONTACT_'.$arResult['ELEMENT']['~CONTACT_ID'].'", "balloon_'.$arResult['GRID_ID'].'_C_'.$arResult['ELEMENT']['CONTACT_ID'].'", "/bitrix/components/bitrix/crm.contact.show/card.ajax.php", "crm_balloon_contact", true);</script>'
		) : '',
	'type' => 'custom',
	'isTactile' => true
);

// COMPANY TITLE
$companyField = array(
	'id' => 'COMPANY_ID',
	'name' => GetMessage('CRM_FIELD_COMPANY_TITLE'),
	'value' => isset($arResult['ELEMENT']['COMPANY_TITLE'])
		? ($CCrmDeal->cPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE, 'READ')
			? $arResult['ELEMENT']['COMPANY_TITLE'] :
			'<a href="'.$arResult['PATH_TO_COMPANY_SHOW'].'" id="balloon_'.$arResult['GRID_ID'].'_CO_'.$arResult['ELEMENT']['COMPANY_ID'].'">'.$arResult['ELEMENT']['COMPANY_TITLE'].'</a>'.
				'<script type="text/javascript">BX.tooltip("COMPANY_'.$arResult['ELEMENT']['~COMPANY_ID'].'", "balloon_'.$arResult['GRID_ID'].'_CO_'.$arResult['ELEMENT']['COMPANY_ID'].'", "/bitrix/components/bitrix/crm.company.show/card.ajax.php", "crm_balloon_company", true);</script>'
		) : '',
	'type' => 'custom',
	'isTactile' => true
);
$arResult['FIELDS']['tab_1'][] = $arResult['COMPANY_FIELD'] = $companyField;
// <-- CONTACT INFO SECTION

// ADDITIONAL SECTION -->
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_additional',
	'name' => GetMessage('CRM_SECTION_ADDITIONAL'),
	'type' => 'section',
	'isTactile' => true
);

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
	'name' => GetMessage('CRM_FIELD_CREATED_BY_ID'),
	'type' => 'custom',
	'value' => $sVal
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'DATE_CREATE',
	'name' => GetMessage('CRM_FIELD_DATE_CREATE'),
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
		'name' => GetMessage('CRM_FIELD_MODIFY_BY_ID'),
		'type' => 'custom',
		'value' => $sVal
	);
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'DATE_MODIFY',
		'name' => GetMessage('CRM_FIELD_DATE_MODIFY'),
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
			"/bitrix/components/bitrix/crm.deal.show/show_file.php?ownerId=#owner_id#&fieldName=#field_name#&fileId=#file_id#",
		'IS_TACTILE' => true
	)
);

// <-- ADDITIONAL SECTION

$arResult['FIELDS']['tab_details'][] = array(
	'id' => 'section_details',
	'name' => GetMessage('CRM_SECTION_DETAILS'),
	'type' => 'section'
);

// WEB-STORE SECTION -->
$enableWebStore = true;
$strEditOrderHtml = '';
if($isExternal)
{
	$dbSalesList = CCrmExternalSale::GetList(
		array("NAME" => "ASC", "SERVER" => "ASC"),
		array("ID" => $arResult['ELEMENT']['ORIGINATOR_ID'])
	);
	if ($arSale = $dbSalesList->GetNext())
		$strEditOrderHtml .= ($arSale["NAME"] != "" ? $arSale["NAME"] : $arSale["SERVER"]);
}
else
{
	$dbSalesList = CCrmExternalSale::GetList(
		array(),
		array("ACTIVE" => "Y")
	);

	$enableWebStore = $dbSalesList->Fetch() !== false;
}

if($enableWebStore)
{
	$arResult['FIELDS']['tab_details'][] = array(
		'id' => 'section_web_store',
		'name' => GetMessage('CRM_SECTION_WEB_STORE'),
		'type' => 'section'
	);

	$arResult['FIELDS']['tab_details'][] = array(
		'id' => 'SALE_ORDER',
		'name' => GetMessage('CRM_FIELD_SALE_ORDER1'),
		'type' => 'custom',
		'value' => isset($strEditOrderHtml[0]) ? $strEditOrderHtml : htmlspecialcharsbx(GetMessage('MAIN_NO'))
	);
}
// <-- WEB-STORE SECTION

if($enableWebStore)
{
	$strAdditionalInfoHtml = '';
	if ($isExternal &&  isset($arResult['ELEMENT']['ADDITIONAL_INFO']))
	{
		$arAdditionalInfo = unserialize($arResult['ELEMENT']['~ADDITIONAL_INFO']);
		if (is_array($arAdditionalInfo) && count($arAdditionalInfo) > 0)
		{
			foreach ($arAdditionalInfo as $k => $v)
			{
				$msgID =  'CRM_SALE_'.$k;
				$k1 = HasMessage($msgID) ? GetMessage($msgID) : $k;
				if (is_bool($v))
					$v = $v ? GetMessage('CRM_SALE_YES') : GetMessage('CRM_SALE_NO');
				$strAdditionalInfoHtml .= '<span>'.htmlspecialcharsbx($k1).'</span>: <span>'.htmlspecialcharsbx($v).'</span><br/>';
			}
		}
	}

	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'ADDITIONAL_INFO',
		'name' => GetMessage('CRM_FIELD_ADDITIONAL_INFO'),
		'type' => 'custom',
		'value' => isset($strAdditionalInfoHtml[0]) ? $strAdditionalInfoHtml : htmlspecialcharsbx(GetMessage('MAIN_NO'))
	);
}

// PRODUCT ROW SECTION -->
$arResult['FIELDS'][$arResult['PRODUCT_ROW_TAB_ID']][] = array(
	'id' => 'section_product_rows',
	'name' => GetMessage('CRM_SECTION_PRODUCT_ROWS'),
	'type' => 'section'
);
$APPLICATION->AddHeadScript($this->GetPath().'/sale.js');

$sProductsHtml = '<script type="text/javascript">var extSaleGetRemoteFormLocal = {"PRINT":"'.GetMessage("CRM_EXT_SALE_DEJ_PRINT").'","SAVE":"'.GetMessage("CRM_EXT_SALE_DEJ_SAVE").'","ORDER":"'.GetMessage("CRM_EXT_SALE_DEJ_ORDER").'","CLOSE":"'.GetMessage("CRM_EXT_SALE_DEJ_CLOSE").'"};</script>';

if ($isExternal)
{
	$sProductsHtml .= '<input type="button" value="'.GetMessage("CRM_EXT_SALE_CD_EDIT").'" onclick="ExtSaleGetRemoteForm('.$arResult['ELEMENT']['ORIGINATOR_ID'].', \'EDIT\', '.$arResult['ELEMENT']['ORIGIN_ID'].')">
	<input type="button" value="'.GetMessage("CRM_EXT_SALE_CD_VIEW").'" onclick="ExtSaleGetRemoteForm('.$arResult['ELEMENT']['ORIGINATOR_ID'].', \'VIEW\', '.$arResult['ELEMENT']['ORIGIN_ID'].')">
	<input type="button" value="'.GetMessage("CRM_EXT_SALE_CD_PRINT").'" onclick="ExtSaleGetRemoteForm('.$arResult['ELEMENT']['ORIGINATOR_ID'].', \'PRINT\', '.$arResult['ELEMENT']['ORIGIN_ID'].')"><br /><br />';
}

$arResult['PRODUCT_ROW_EDITOR_ID'] = 'deal_'.strval($arParams['ELEMENT_ID']).'_product_editor';
if($arParams['ELEMENT_ID'] > 0)
{
	$bTaxMode = CCrmTax::isTaxMode();

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
			'OWNER_TYPE' => 'D',
			'PERMISSION_TYPE' => $enableInstantEdit && !$isExternal ? 'WRITE' : 'READ',
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
	'name' => GetMessage('CRM_FIELD_PRODUCT_ROWS'),
	'colspan' => true,
	'type' => 'custom',
	'value' => $sProductsHtml
);
// <-- PRODUCT ROW SECTION

// LIVE FEED SECTION -->
$arResult['FIELDS']['tab_live_feed'][] = array(
	'id' => 'section_live_feed',
	'name' => GetMessage('CRM_SECTION_LIVE_FEED'),
	'type' => 'section'
);

$liveFeedHtml = '';
if($arParams['ELEMENT_ID'] > 0)
{
	if(CCrmLiveFeedComponent::needToProcessRequest($_SERVER['REQUEST_METHOD'], $_REQUEST))
	{
		ob_start();
		$APPLICATION->IncludeComponent('bitrix:crm.entity.livefeed',
			'',
			array(
				'DATE_TIME_FORMAT' => (LANGUAGE_ID=='en'?"j F Y g:i a":(LANGUAGE_ID=='de' ? "j. F Y, G:i" : "j F Y G:i")),
				'CAN_EDIT' => $arResult['CAN_EDIT'],
				'ENTITY_TYPE_ID' => CCrmOwnerType::Deal,
				'ENTITY_ID' => $arParams['ELEMENT_ID'],
				'FORM_ID' => $arResult['FORM_ID'],
				'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE']
			),
			null,
			array('HIDE_ICONS' => 'Y')
		);
		$liveFeedHtml = ob_get_contents();
		ob_end_clean();
		$arResult['ENABLE_LIVE_FEED_LAZY_LOAD'] = false;
	}
	else
	{
		$liveFeedContainerID = $arResult['LIVE_FEED_CONTAINER_ID'] = $arResult['FORM_ID'].'_live_feed_wrapper';
		$liveFeedHtml = '<div id="'.htmlspecialcharsbx($liveFeedContainerID).'"></div>';
		$arResult['ENABLE_LIVE_FEED_LAZY_LOAD'] = true;
	}
}

$arResult['FIELDS']['tab_live_feed'][] = array(
	'id' => 'LIVE_FEED',
	'name' => GetMessage('CRM_FIELD_LIVE_FEED'),
	'colspan' => true,
	'type' => 'custom',
	'value' => $liveFeedHtml
);
// <-- LIVE FEED SECTION

$arResult['FIELDS']['tab_activity'][] = array(
	'id' => 'section_activity_grid',
	'name' => GetMessage('CRM_SECTION_ACTIVITY_MAIN'),
	'type' => 'section'
);

$arResult['FIELDS']['tab_activity'][] = array(
	'id' => 'DEAL_ACTIVITY_GRID',
	'name' => GetMessage('CRM_FIELD_DEAL_ACTIVITY'),
	'colspan' => true,
	'type' => 'crm_activity_list',
	'componentData' => array(
		'template' => 'grid',
		'enableLazyLoad' => true,
		'params' => array(
			'BINDINGS' => array(array('TYPE_NAME' => 'DEAL', 'ID' => $arParams['ELEMENT_ID'])),
			'PREFIX' => 'DEAL_ACTIONS_GRID',
			'PERMISSION_TYPE' => 'WRITE',
			'ENABLE_NAVIGATION' => 'Y',
			'FORM_TYPE' => 'show',
			'FORM_ID' => $arResult['FORM_ID'],
			'TAB_ID' => 'tab_activity',
			'USE_QUICK_FILTER' => 'Y'
		)
	)
);
$formTabKey = $arResult['FORM_ID'].'_active_tab';
$currentFormTabID = $_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET[$formTabKey]) ? $_GET[$formTabKey] : '';

if ($contactID > 0 && CCrmContact::CheckReadPermission($contactID, $currentUserPermissions))
{
	$arResult['FIELDS']['tab_contact'][] = array(
		'id' => 'DEAL_CONTACTS',
		'name' => GetMessage('CRM_FIELD_DEAL_CONTACTS'),
		'colspan' => true,
		'type' => 'crm_contact_list',
		'componentData' => array(
			'template' => '',
			'enableLazyLoad' => true,
			'params' => array(
				'CONTACT_COUNT' => '20',
				'PATH_TO_CONTACT_SHOW' => $arParams['PATH_TO_CONTACT_SHOW'],
				'PATH_TO_CONTACT_EDIT' => $arParams['PATH_TO_CONTACT_EDIT'],
				'PATH_TO_DEAL_EDIT' => $arParams['PATH_TO_DEAL_EDIT'],
				'INTERNAL_FILTER' => array('ID' => $contactID),
				'GRID_ID_SUFFIX' => 'DEAL_SHOW',
				'FORM_ID' => $arResult['FORM_ID'],
				'TAB_ID' => 'tab_contact'
			)
		)
	);
}

if ($companyID > 0 && CCrmCompany::CheckReadPermission($companyID, $currentUserPermissions))
{
	$arResult['FIELDS']['tab_company'][] = array(
		'id' => 'DEAL_COMPANY',
		'name' => GetMessage('CRM_FIELD_DEAL_COMPANY'),
		'colspan' => true,
		'type' => 'crm_company_list',
		'componentData' => array(
			'template' => '',
			'enableLazyLoad' => true,
			'params' => array(
				'COMPANY_COUNT' => '20',
				'PATH_TO_COMPANY_SHOW' => $arParams['PATH_TO_COMPANY_SHOW'],
				'PATH_TO_COMPANY_EDIT' => $arParams['PATH_TO_COMPANY_EDIT'],
				'PATH_TO_CONTACT_EDIT' => $arParams['PATH_TO_CONTACT_EDIT'],
				'PATH_TO_DEAL_EDIT' => $arParams['PATH_TO_DEAL_EDIT'],
				'INTERNAL_FILTER' => array('ID' => $companyID),
				'GRID_ID_SUFFIX' => 'DEAL_SHOW',
				'FORM_ID' => $arResult['FORM_ID'],
				'TAB_ID' => 'tab_company',
				'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
			)
		)
	);
}

if (!$CCrmDeal->cPerms->HavePerm('QUOTE', BX_CRM_PERM_NONE, 'READ'))
{
	$arResult['FIELDS']['tab_quote'][] = array(
		'id' => 'DEAL_QUOTE',
		'name' => GetMessage('CRM_FIELD_DEAL_QUOTE'),
		'colspan' => true,
		'type' => 'crm_quote_list',
		'componentData' => array(
			'template' => '',
			'enableLazyLoad' => true,
			'params' => array(
				'QUOTE_COUNT' => '20',
				'PATH_TO_QUOTE_SHOW' => $arResult['PATH_TO_QUOTE_SHOW'],
				'PATH_TO_QUOTE_EDIT' => $arResult['PATH_TO_QUOTE_EDIT'],
				'INTERNAL_FILTER' => array('DEAL_ID' => $arResult['ELEMENT']['ID']),
				'INTERNAL_CONTEXT' => array('DEAL_ID' => $arResult['ELEMENT']['ID']),
				'GRID_ID_SUFFIX' => 'DEAL_SHOW',
				'FORM_ID' => $arResult['FORM_ID'],
				'TAB_ID' => 'tab_quote',
				'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
				'ENABLE_TOOLBAR' => true
			)
		)
	);
}

if (!$CCrmDeal->cPerms->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'READ'))
{
	$arResult['FIELDS']['tab_invoice'][] = array(
		'id' => 'DEAL_INVOICE',
		'name' => GetMessage('CRM_FIELD_DEAL_INVOICE'),
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
				'PATH_TO_DEAL_EDIT' => $arParams['PATH_TO_DEAL_EDIT'],
				'PATH_TO_INVOICE_EDIT' => $arParams['PATH_TO_INVOICE_EDIT'],
				'PATH_TO_INVOICE_PAYMENT' => $arParams['PATH_TO_INVOICE_PAYMENT'],
				'INTERNAL_FILTER' => array('UF_DEAL_ID' => $arResult['ELEMENT']['ID']),
				'SUM_PAID_CURRENCY' => $currencyID,
				'GRID_ID_SUFFIX' => 'DEAL_SHOW',
				'FORM_ID' => $arResult['FORM_ID'],
				'TAB_ID' => 'tab_invoice',
				'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
				'ENABLE_TOOLBAR' => 'Y',
				'INTERNAL_ADD_BTN_TITLE' => GetMessage('CRM_DEAL_ADD_INVOICE_TITLE')
			)
		)
	);
}

if (IsModuleInstalled('bizproc') && CModule::IncludeModule('bizproc'))
{
	//HACK: main.interface.grid may override current tab
	if($_SERVER['REQUEST_METHOD'] === 'GET' && $currentFormTabID !== '')
	{
		$_GET[$formTabKey] = $currentFormTabID;
	}

	$arResult['FIELDS']['tab_bizproc'][] = array(
		'id' => 'section_bizproc',
		'name' => GetMessage('CRM_SECTION_BIZPROC_MAIN'),
		'type' => 'section'
	);

	$arResult['BIZPROC'] = 'Y';

	$activeTab = isset($_REQUEST[$formTabKey]) ? $_REQUEST[$formTabKey] : '';
	$bizprocTask = isset($_REQUEST['bizproc_task']) ? $_REQUEST['bizproc_task'] : '';
	$bizprocIndex = isset($_REQUEST['bizproc_index']) ? intval($_REQUEST['bizproc_index']) : 0;
	$bizprocAction = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

	if ($bizprocTask !== '')
	{
		ob_start();
		$APPLICATION->IncludeComponent(
			'bitrix:bizproc.task',
			'',
			Array(
				'TASK_ID' => (int)$_REQUEST['bizproc_task'],
				'USER_ID' => 0,
				'WORKFLOW_ID' => '',
				'DOCUMENT_URL' =>  CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_SHOW'],
					array(
						'deal_id' => $arResult['ELEMENT']['ID']
					)
				),
				'SET_TITLE' => 'Y',
				'SET_NAV_CHAIN' => 'Y'
			),
			'',
			array('HIDE_ICONS' => 'Y')
		);
		$sVal = ob_get_contents();
		ob_end_clean();
		$arResult['FIELDS']['tab_bizproc'][] = array(
			'id' => 'DEAL_BIZPROC',
			'name' => GetMessage('CRM_FIELD_DEAL_BIZPROC'),
			'colspan' => true,
			'type' => 'custom',
			'value' => $sVal
		);
	}
	elseif (isset($_REQUEST['bizproc_log']) && strlen($_REQUEST['bizproc_log']) > 0)
	{
		ob_start();
		$APPLICATION->IncludeComponent('bitrix:bizproc.log',
			'',
			Array(
				'MODULE_ID' => 'crm',
				'ENTITY' => 'CCrmDocumentDeal',
				'DOCUMENT_TYPE' => 'DEAL',
				'COMPONENT_VERSION' => 2,
				'DOCUMENT_ID' => 'DEAL_'.$arResult['ELEMENT']['ID'],
				'ID' => $_REQUEST['bizproc_log'],
				'SET_TITLE'	=>	'Y',
				'INLINE_MODE' => 'Y',
				'AJAX_MODE' => 'N',
				'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
			),
			'',
			array("HIDE_ICONS" => "Y")
		);
		$sVal = ob_get_contents();
		ob_end_clean();
		$arResult['FIELDS']['tab_bizproc'][] = array(
			'id' => 'DEAL_BIZPROC',
			'name' => GetMessage('CRM_FIELD_DEAL_BIZPROC'),
			'colspan' => true,
			'type' => 'custom',
			'value' => $sVal
		);
	}
	elseif (isset($_REQUEST['bizproc_start']) && strlen($_REQUEST['bizproc_start']) > 0)
	{
		ob_start();
		$APPLICATION->IncludeComponent('bitrix:bizproc.workflow.start',
			'',
			Array(
				'MODULE_ID' => 'crm',
				'ENTITY' => 'CCrmDocumentDeal',
				'DOCUMENT_TYPE' => 'DEAL',
				'DOCUMENT_ID' => 'DEAL_'.$arResult['ELEMENT']['ID'],
				'TEMPLATE_ID' => $_REQUEST['workflow_template_id'],
				'SET_TITLE'	=>	'Y'
			),
			'',
			array('HIDE_ICONS' => 'Y')
		);
		$sVal = ob_get_contents();
		ob_end_clean();
		$arResult['FIELDS']['tab_bizproc'][] = array(
			'id' => 'DEAL_BIZPROC',
			'name' => GetMessage('CRM_FIELD_DEAL_BIZPROC'),
			'colspan' => true,
			'type' => 'custom',
			'value' => $sVal
		);
	}
	else
	{
		if(!($activeTab === 'tab_bizproc' || $bizprocIndex > 0 || $bizprocAction !== ''))
		{
			$bizprocContainerID = $arResult['BIZPROC_CONTAINER_ID'] = $arResult['FORM_ID'].'_bp_wrapper';
			$arResult['ENABLE_BIZPROC_LAZY_LOADING'] = true;
			$arResult['FIELDS']['tab_bizproc'][] = array(
				'id' => 'DEAL_BIZPROC',
				'name' => GetMessage('CRM_FIELD_DEAL_BIZPROC'),
				'colspan' => true,
				'type' => 'custom',
				'value' => '<div id="'.htmlspecialcharsbx($bizprocContainerID).'"></div>'
			);
		}
		else
		{
			ob_start();
			$APPLICATION->IncludeComponent('bitrix:bizproc.document',
				'',
				Array(
					'MODULE_ID' => 'crm',
					'ENTITY' => 'CCrmDocumentDeal',
					'DOCUMENT_TYPE' => 'DEAL',
					'DOCUMENT_ID' => 'DEAL_'.$arResult['ELEMENT']['ID'],
					'TASK_EDIT_URL' => CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_SHOW'],
							array(
								'deal_id' => $arResult['ELEMENT']['ID']
							)),
						array('bizproc_task' => '#ID#', $formTabKey => 'tab_bizproc')
					),
					'WORKFLOW_LOG_URL' => CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_SHOW'],
							array(
								'deal_id' => $arResult['ELEMENT']['ID']
							)),
						array('bizproc_log' => '#ID#', $formTabKey => 'tab_bizproc')
					),
					'WORKFLOW_START_URL' => CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_SHOW'],
							array(
								'deal_id' => $arResult['ELEMENT']['ID']
							)),
						array('bizproc_start' => 1, $formTabKey => 'tab_bizproc')
					),
					'back_url' => CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_SHOW'],
							array(
								'deal_id' => $arResult['ELEMENT']['ID']
							)),
						array($formTabKey => 'tab_bizproc')
					),
					'SET_TITLE'	=>	'Y'
				),
				'',
				array('HIDE_ICONS' => 'Y')
			);
			$sVal = ob_get_contents();
			ob_end_clean();
			$arResult['FIELDS']['tab_bizproc'][] = array(
				'id' => 'DEAL_BIZPROC',
				'name' => GetMessage('CRM_FIELD_DEAL_BIZPROC'),
				'colspan' => true,
				'type' => 'custom',
				'value' => $sVal
			);
		}
	}
}

if (intval($arResult['ELEMENT']['LEAD_ID']) > 0 && !$CCrmDeal->cPerms->HavePerm('LEAD', BX_CRM_PERM_NONE, 'READ'))
{
	ob_start();
	$arResult['LEAD_COUNT'] = $APPLICATION->IncludeComponent(
		'bitrix:crm.lead.list',
		'',
		array(
			'LEAD_COUNT' => '20',
			'PATH_TO_LEAD_SHOW' => $arParams['PATH_TO_LEAD_SHOW'],
			'PATH_TO_LEAD_EDIT' => $arParams['PATH_TO_LEAD_EDIT'],
			'PATH_TO_LEAD_CONVERT' => $arParams['PATH_TO_LEAD_CONVERT'],
			'INTERNAL_FILTER' => array('ID' => $arResult['ELEMENT']['LEAD_ID']),
			'GRID_ID_SUFFIX' => 'DEAL_SHOW',
			'FORM_ID' => $arResult['FORM_ID'],
			'TAB_ID' => 'tab_lead'
		),
		false
	);
	$sVal = ob_get_contents();
	ob_end_clean();
	$arResult['FIELDS']['tab_lead'][] = array(
		'id' => 'DEAL_LEAD',
		'name' => GetMessage('CRM_FIELD_DEAL_LEAD'),
		'colspan' => true,
		'type' => 'custom',
		'value' => $sVal
	);
}

$arResult['FIELDS']['tab_event'][] = array(
	'id' => 'section_event_grid',
	'name' => GetMessage('CRM_SECTION_EVENT_MAIN'),
	'type' => 'section'
);

$arResult['FIELDS']['tab_event'][] = array(
	'id' => 'DEAL_EVENT',
	'name' => GetMessage('CRM_FIELD_DEAL_EVENT'),
	'colspan' => true,
	'type' => 'crm_event_view',
	'componentData' => array(
		'template' => '',
		'enableLazyLoad' => true,
		'contextId' => "DEAL_{$arResult['ELEMENT']['ID']}_EVENT",
		'params' => array(
			'AJAX_OPTION_ADDITIONAL' => "DEAL_{$arResult['ELEMENT']['ID']}_EVENT",
			'ENTITY_TYPE' => 'DEAL',
			'ENTITY_ID' => $arResult['ELEMENT']['ID'],
			'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],
			'FORM_ID' => $arResult['FORM_ID'],
			'TAB_ID' => 'tab_event',
			'INTERNAL' => 'Y',
			'SHOW_INTERNAL_FILTER' => 'Y',
			'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
		)
	)
);

$arResult['ACTION_URI'] = $arResult['POST_FORM_URI'] = POST_FORM_ACTION_URI;

// HACK: for to prevent title overwrite after AJAX call.
if(isset($_REQUEST['bxajaxid']))
{
	$APPLICATION->SetTitle('');
}
$this->IncludeComponentTemplate();
include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.deal/include/nav.php');
?>
