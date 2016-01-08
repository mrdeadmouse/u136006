<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

$userPerms = CCrmPerms::GetCurrentUserPermissions();
if ($userPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'READ'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

global $APPLICATION;

$enableSearch = $arResult['ENABLE_SEARCH'] = isset($_GET['SEARCH']) && strtoupper($_GET['SEARCH']) === 'Y';
$enablePaging = $arResult['ENABLE_PAGING'] = isset($_GET['PAGING']) && strtoupper($_GET['PAGING']) === 'Y';
if($enableSearch)
{
	// decode encodeURIComponent params
	CUtil::JSPostUnescape();
}

$currentUserID = $arResult['USER_ID'] = intval(CCrmSecurityHelper::GetCurrentUserID());
$arParams['COMPANY_EDIT_URL_TEMPLATE'] = isset($arParams['COMPANY_EDIT_URL_TEMPLATE']) ? $arParams['COMPANY_EDIT_URL_TEMPLATE'] : '';
$arParams['COMPANY_SHOW_URL_TEMPLATE'] = isset($arParams['COMPANY_SHOW_URL_TEMPLATE']) ? $arParams['COMPANY_SHOW_URL_TEMPLATE'] : '';
$arParams['USER_PROFILE_URL_TEMPLATE'] = isset($arParams['USER_PROFILE_URL_TEMPLATE']) ? $arParams['USER_PROFILE_URL_TEMPLATE'] : '';
$arParams['NAME_TEMPLATE'] = isset($arParams['NAME_TEMPLATE']) ? str_replace(array('#NOBR#', '#/NOBR#'), array('', ''), $arParams['NAME_TEMPLATE']) : CSite::GetNameFormat(false);

$arParams['UID'] = isset($arParams['UID']) ? $arParams['UID'] : '';
if(!isset($arParams['UID']) || $arParams['UID'] === '')
{
	$arParams['UID'] = 'mobile_crm_company_list';
}
$arResult['UID'] = $arParams['UID'];

$arResult['FILTER'] = array(
	array('id' => 'TITLE'),
	array('id' => 'MODIFY_BY_ID'),
	array('id' => 'ASSIGNED_BY_ID')
);

$arResult['FILTER_PRESETS'] = array(
		'filter_my' => array('name' => GetMessage('M_CRM_COMPANY_LIST_PRESET_MY'), 'fields' => array('ASSIGNED_BY_ID' => $currentUserID)),
		'filter_change_my' => array('name' => GetMessage('M_CRM_COMPANY_LIST_PRESET_CHANGE_MY'), 'fields' => array('MODIFY_BY_ID' => $currentUserID))
);

$itemPerPage = isset($arParams['ITEM_PER_PAGE']) ? intval($arParams['ITEM_PER_PAGE']) : 0;
if($itemPerPage <= 0)
{
	$itemPerPage = 20;
}
$arParams['ITEM_PER_PAGE'] = $itemPerPage;

$sort = array('TITLE' => 'ASC');
$filter = array();
$navParams = array(
	'nPageSize' => $itemPerPage,
	'iNumPage' => $enablePaging ? false : 1,
	'bShowAll' => false
);
$select = array(
	'ID',
	'TITLE', 'COMPANY_TYPE', 'INDUSTRY', 'LOGO',
	'ASSIGNED_BY_ID', 'ASSIGNED_BY_LOGIN', 'ASSIGNED_BY_NAME', 'ASSIGNED_BY_SECOND_NAME', 'ASSIGNED_BY_LAST_NAME',
	'COMMENTS'
);

$navigation = CDBResult::GetNavParams($navParams);
$CGridOptions = new CCrmGridOptions($arResult['UID']);
$navParams = $CGridOptions->GetNavParams($navParams);
$navParams['bShowAll'] = false;

$arResult['GRID_FILTER_ID'] = '';
$arResult['GRID_FILTER_NAME'] = '';

//$options = array();
if($enableSearch)
{
	$filter += $CGridOptions->GetFilter($arResult['FILTER']);
	if(empty($filter))
	{
		$enableSearch = $arResult['ENABLE_SEARCH'] = false;
	}
}

if($enableSearch)
{
	if(isset($filter['GRID_FILTER_APPLIED']) && $filter['GRID_FILTER_APPLIED'] && isset($filter['GRID_FILTER_ID']))
	{
		$filterID = $filter['GRID_FILTER_ID'];
		$arResult['GRID_FILTER_ID'] = $filterID;

		$arResult['GRID_FILTER_NAME'] = isset($arResult['FILTER_PRESETS'][$filterID])
			? $arResult['FILTER_PRESETS'][$filterID]['name']
			: GetMessage('M_CRM_COMPANY_LIST_FILTER_CUSTOM');
	}
	else
	{
		$arResult['GRID_FILTER_NAME'] = GetMessage('M_CRM_COMPANY_LIST_FILTER_CUSTOM');
	}

	if(isset($filter['TITLE']))
	{
		$filter['%TITLE'] = $filter['TITLE'];
		unset($filter['TITLE']);
	}
}
else
{
	$arResult['GRID_FILTER_NAME'] = GetMessage('M_CRM_COMPANY_LIST_FILTER_NONE');
}

$arResult['ITEMS'] = array();

$dbRes = CCrmCompany::GetListEx($sort, $filter, false, $navParams, $select);
$dbRes->NavStart($navParams['nPageSize'], false);

$arResult['PAGE_NAVNUM'] = intval($dbRes->NavNum); // pager index
$arResult['PAGE_NUMBER'] = intval($dbRes->NavPageNomer); // current page index
$arResult['PAGE_NAVCOUNT'] = intval($dbRes->NavPageCount); // page count
$arResult['PAGER_PARAM'] = "PAGEN_{$arResult['PAGE_NAVNUM']}";
$arResult['PAGE_NEXT_NUMBER'] = $arResult['PAGE_NUMBER'] + 1;

$arResult['COMPANY_TYPE_LIST'] = CCrmStatus::GetStatusList('COMPANY_TYPE');
//$arResult['EMPLOYEES_LIST'] = CCrmStatus::GetStatusList('EMPLOYEES');
$arResult['INDUSTRY_LIST'] = CCrmStatus::GetStatusList('INDUSTRY');

while($item = $dbRes->GetNext())
{
	CCrmMobileHelper::PrepareCompanyItem(
		$item,
		$arParams,
		array(
			'COMPANY_TYPE' => $arResult['COMPANY_TYPE_LIST'],
			'INDUSTRY' => $arResult['INDUSTRY_LIST']
		)
	);

	$arResult['ITEMS'][] = &$item;
	unset($item);
}

if($arResult['PAGE_NEXT_NUMBER'] > $arResult['PAGE_NAVCOUNT'])
{
	$arResult['NEXT_PAGE_URL'] = '';
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
$arResult['SERVICE_URL'] = SITE_DIR.'bitrix/components/bitrix/mobile.crm.company.list/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get();

$arResult['PERMISSIONS'] = array(
	'CREATE' => CCrmCompany::CheckCreatePermission()
);

$arResult['CREATE_URL'] = $arParams['COMPANY_EDIT_URL_TEMPLATE'] !== ''
	? CComponentEngine::MakePathFromTemplate(
		$arParams['COMPANY_EDIT_URL_TEMPLATE'],
		array('company_id' => 0)
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
