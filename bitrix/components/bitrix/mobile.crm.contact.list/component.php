<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if(!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

$userPerms = CCrmPerms::GetCurrentUserPermissions();
if($userPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'READ'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

global $APPLICATION;
$arResult['RUBRIC'] = array('ENABLED' => false);

$enablePaging = $arResult['ENABLE_PAGING'] = isset($_GET['PAGING']) && strtoupper($_GET['PAGING']) === 'Y';
$enableSearch = $arResult['ENABLE_SEARCH'] = isset($_GET['SEARCH']) && strtoupper($_GET['SEARCH']) === 'Y';
if($enableSearch)
{
	// decode encodeURIComponent params
	CUtil::JSPostUnescape();
}

$companyID = $arResult['COMPANY_ID'] = isset($_GET['company_id']) ? intval($_GET['company_id']) : 0;
$arResult['SHOW_SEARCH_PANEL'] = $companyID <= 0;

$currentUserID = $arResult['USER_ID'] = intval(CCrmSecurityHelper::GetCurrentUserID());
$arParams['CONTACT_EDIT_URL_TEMPLATE'] =  isset($arParams['CONTACT_EDIT_URL_TEMPLATE']) ? $arParams['CONTACT_EDIT_URL_TEMPLATE'] : '';
$arParams['CONTACT_SHOW_URL_TEMPLATE'] =  isset($arParams['CONTACT_SHOW_URL_TEMPLATE']) ? $arParams['CONTACT_SHOW_URL_TEMPLATE'] : '';
$arParams['COMPANY_SHOW_URL_TEMPLATE'] = isset($arParams['COMPANY_SHOW_URL_TEMPLATE']) ? $arParams['COMPANY_SHOW_URL_TEMPLATE'] : '';
$arParams['USER_PROFILE_URL_TEMPLATE'] = isset($arParams['USER_PROFILE_URL_TEMPLATE']) ? $arParams['USER_PROFILE_URL_TEMPLATE'] : '';
$arParams['NAME_TEMPLATE'] = isset($arParams['NAME_TEMPLATE']) ? str_replace(array('#NOBR#', '#/NOBR#'), array('', ''), $arParams['NAME_TEMPLATE']) : CSite::GetNameFormat(false);

$arParams['UID'] = isset($arParams['UID']) ? $arParams['UID'] : '';
if(!isset($arParams['UID']) || $arParams['UID'] === '')
{
	$arParams['UID'] = 'mobile_crm_contact_list';
}
$arResult['UID'] = $arParams['UID'];

/*
$arParams['PULL_TAG'] = $arResult['PULL_TAG'] = isset($arParams['PULL_TAG']) ? $arParams['PULL_TAG'] : 'CRM_CONTACT_CHANGE';
$arParams['PULL_UPDATE_CMD'] = $arResult['PULL_UPDATE_CMD'] = isset($arParams['PULL_UPDATE_CMD']) ? $arParams['PULL_UPDATE_CMD'] : 'crm_contact_update';
$arParams['PULL_DELETE_CMD'] = $arResult['PULL_DELETE_CMD'] = isset($arParams['PULL_DELETE_CMD']) ? $arParams['PULL_DELETE_CMD'] : 'crm_contact_delete';
*/

$arResult['FILTER'] = array(
	array('id' => 'NAME'),
	array('id' => 'LAST_NAME'),
	array('id' => 'FULL_NAME'),
	array('id' => 'MODIFY_BY_ID'),
	array('id' => 'ASSIGNED_BY_ID')
);

$arResult['FILTER_PRESETS'] = array(
		'filter_my' => array('name' => GetMessage('M_CRM_CONTACT_LIST_PRESET_MY'), 'fields' => array('ASSIGNED_BY_ID' => $currentUserID)),
		'filter_change_my' => array('name' => GetMessage('M_CRM_CONTACT_LIST_PRESET_CHANGE_MY'), 'fields' => array('MODIFY_BY_ID' => $currentUserID))
);

$itemPerPage = isset($arParams['ITEM_PER_PAGE']) ? intval($arParams['ITEM_PER_PAGE']) : 0;
if($itemPerPage <= 0)
{
	$itemPerPage = 20;
}
$arParams['ITEM_PER_PAGE'] = $itemPerPage;

$sort = array('LAST_NAME' => 'ASC', 'NAME' => 'ASC');
$filter = array();
$navParams = array(
	'nPageSize' => $itemPerPage,
	'iNumPage' => $enablePaging ? false : 1,
	'bShowAll' => false
);
$select = array(
	'ID',
	'NAME', 'SECOND_NAME', 'LAST_NAME', 'POST', 'PHOTO',
	'ASSIGNED_BY_ID', 'ASSIGNED_BY_LOGIN', 'ASSIGNED_BY_NAME', 'ASSIGNED_BY_SECOND_NAME', 'ASSIGNED_BY_LAST_NAME',
	'COMPANY_ID', 'COMPANY_TITLE', 'COMMENTS'
);

$navigation = CDBResult::GetNavParams($navParams);
$CGridOptions = new CCrmGridOptions($arResult['UID']);
$navParams = $CGridOptions->GetNavParams($navParams);
$navParams['bShowAll'] = false;

$arResult['GRID_FILTER_ID'] = '';
$arResult['GRID_FILTER_NAME'] = '';

if($enableSearch)
{
	$filter += $CGridOptions->GetFilter($arResult['FILTER']);
	if(empty($filter))
	{
		$enableSearch = $arResult['ENABLE_SEARCH'] = false;
		$arResult['GRID_FILTER_NAME'] = GetMessage('M_CRM_DEAL_LIST_FILTER_NONE');
	}
	else
	{
		if(isset($filter['GRID_FILTER_APPLIED']) && $filter['GRID_FILTER_APPLIED'] && isset($filter['GRID_FILTER_ID']))
		{
			$filterID = $filter['GRID_FILTER_ID'];
			$arResult['GRID_FILTER_ID'] = $filterID;

			$arResult['GRID_FILTER_NAME'] = isset($arResult['FILTER_PRESETS'][$filterID])
				? $arResult['FILTER_PRESETS'][$filterID]['name']
				: GetMessage('M_CRM_CONTACT_LIST_FILTER_CUSTOM');
		}
		else
		{
			$arResult['GRID_FILTER_NAME'] = GetMessage('M_CRM_CONTACT_LIST_FILTER_CUSTOM');
		}

		if(isset($filter['FULL_NAME']))
		{
			$filter['%FULL_NAME'] = $filter['FULL_NAME'];
			unset($filter['FULL_NAME']);
		}
	}
}

if($companyID > 0)
{
	$arResult['RUBRIC']['ENABLED'] = true;
	$arResult['RUBRIC']['TITLE'] = CCrmOwnerType::GetCaption(CCrmOwnerType::Company, $companyID);

	$filter['=COMPANY_ID'] = $companyID;
}

//Setup default filter name ('NONE') if it is not assigned
if(!isset($arResult['GRID_FILTER_NAME']) || $arResult['GRID_FILTER_NAME'] === '')
{
	$arResult['GRID_FILTER_NAME'] = GetMessage('M_CRM_CONTACT_LIST_FILTER_NONE');
}

$arResult['ITEMS'] = array();

$dbRes = CCrmContact::GetListEx($sort, $filter, false, $navParams, $select);
$dbRes->NavStart($navParams['nPageSize'], false);

$arResult['PAGE_NAVNUM'] = intval($dbRes->NavNum); // pager index
$arResult['PAGE_NUMBER'] = intval($dbRes->NavPageNomer); // current page index
$arResult['PAGE_NAVCOUNT'] = intval($dbRes->NavPageCount); // page count
$arResult['PAGER_PARAM'] = "PAGEN_{$arResult['PAGE_NAVNUM']}";
$arResult['PAGE_NEXT_NUMBER'] = $arResult['PAGE_NUMBER'] + 1;

while($item = $dbRes->GetNext())
{
	CCrmMobileHelper::PrepareContactItem($item, $arParams);

	$arResult['ITEMS'][] = &$item;
	unset($item);
}

//NEXT_PAGE_URL, SEARCH_PAGE_URL, SERVICE_URL -->
if($arResult['PAGE_NEXT_NUMBER'] > $arResult['PAGE_NAVCOUNT'])
{
	$arResult['NEXT_PAGE_URL'] = '';
}
elseif($companyID > 0)
{
	$arResult['NEXT_PAGE_URL'] = $APPLICATION->GetCurPageParam(
		'AJAX_CALL=Y&PAGING=Y&FORMAT=json&company_id='.$companyID.'&'.$arResult['PAGER_PARAM'].'='.$arResult['PAGE_NEXT_NUMBER'],
		array('AJAX_CALL', 'PAGING', 'FORMAT', 'SEARCH', 'company_id', $arResult['PAGER_PARAM'])
	);
}
else
{
	$arResult['NEXT_PAGE_URL'] = $APPLICATION->GetCurPageParam(
		'AJAX_CALL=Y&PAGING=Y&FORMAT=json&SEARCH='.($enableSearch ? 'Y' : 'N').'&'.$arResult['PAGER_PARAM'].'='.$arResult['PAGE_NEXT_NUMBER'],
		array('AJAX_CALL', 'PAGING', 'FORMAT', 'SEARCH', $arResult['PAGER_PARAM'])
	);
}

$arResult['SEARCH_PAGE_URL'] = $APPLICATION->GetCurPageParam(
	'AJAX_CALL=Y&SEARCH=Y&FORMAT=json&apply_filter=Y&save=Y',
	array('AJAX_CALL', 'SEARCH', 'FORMAT', 'save', 'apply_filter', 'clear_filter')
);

$serviceURLTemplate = ($arParams["SERVICE_URL_TEMPLATE"]
	? $arParams["SERVICE_URL_TEMPLATE"]
	: '#SITE_DIR#bitrix/components/bitrix/mobile.crm.contact.edit/ajax.php?site_id=#SITE#&sessid=#SID#'
);
$arResult['SERVICE_URL'] = CComponentEngine::makePathFromTemplate(
	$serviceURLTemplate,
	array('SID' => bitrix_sessid())
);

//<-- NEXT_PAGE_URL, SEARCH_PAGE_URL, SERVICE_URL

$arResult['PERMISSIONS'] = array(
	'CREATE' => CCrmContact::CheckCreatePermission()
);

$arResult['CREATE_URL'] = $arParams['CONTACT_EDIT_URL_TEMPLATE'] !== ''
	? CComponentEngine::makePathFromTemplate(
		$arParams['CONTACT_EDIT_URL_TEMPLATE'],
		array(
			'contact_id' => 0,
			'company_id' => $companyID
		)
	) : '';

$arResult['RELOAD_URL'] = $APPLICATION->GetCurPageParam(
	'AJAX_CALL=Y&FORMAT=json',
	array('AJAX_CALL', 'SEARCH', 'FORMAT', 'save', 'apply_filter', 'clear_filter')
);

$format = isset($_REQUEST['FORMAT']) ? strtolower($_REQUEST['FORMAT']) : '';
// Only JSON format is supported
if($format !== '' && $format !== 'json')
{
	$format = '';
}
$this->IncludeComponentTemplate($format);

