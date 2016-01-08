<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

$userPerms = CCrmPerms::GetCurrentUserPermissions();
if ($userPerms->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'READ'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

global $APPLICATION;

$currentUserID = $arResult['USER_ID'] = intval(CCrmSecurityHelper::GetCurrentUserID());
$enablePaging = $arResult['ENABLE_PAGING'] = isset($_REQUEST['PAGING']) && strtoupper($_REQUEST['PAGING']) === 'Y';

$needle = '';
$enableSearch = $arResult['ENABLE_SEARCH'] = isset($_REQUEST['SEARCH']) && strtoupper($_REQUEST['SEARCH']) === 'Y';
if($enableSearch)
{
	// decode encodeURIComponent params
	CUtil::JSPostUnescape();
	$needle = isset($_REQUEST['NEEDLE']) ? $_REQUEST['NEEDLE'] : '';
}

$arResult['SHOW_SEARCH_PANEL'] = true;

$arParams['INVOICE_SHOW_URL_TEMPLATE'] =  isset($arParams['INVOICE_SHOW_URL_TEMPLATE']) ? $arParams['INVOICE_SHOW_URL_TEMPLATE'] : '';
$arParams['INVOICE_EDIT_URL_TEMPLATE'] =  isset($arParams['INVOICE_EDIT_URL_TEMPLATE']) ? $arParams['INVOICE_EDIT_URL_TEMPLATE'] : '';
$arParams['USER_PROFILE_URL_TEMPLATE'] = isset($arParams['USER_PROFILE_URL_TEMPLATE']) ? $arParams['USER_PROFILE_URL_TEMPLATE'] : '';
$arParams['NAME_TEMPLATE'] = isset($arParams['NAME_TEMPLATE']) ? str_replace(array('#NOBR#', '#/NOBR#'), array('', ''), $arParams['NAME_TEMPLATE']) : CSite::GetNameFormat(false);

$arParams['UID'] = isset($arParams['UID']) ? $arParams['UID'] : '';
if(!isset($arParams['UID']) || $arParams['UID'] === '')
{
	$arParams['UID'] = 'mobile_crm_invoice_list';
}
$arResult['UID'] = $arParams['UID'];

$contextID = isset($arParams['CONTEXT_ID']) ? $arParams['CONTEXT_ID'] : '';
if($contextID === '' && isset($_REQUEST['context_id']))
{
	$contextID = $_REQUEST['context_id'];
}
$arResult['CONTEXT_ID'] = $arParams['CONTEXT_ID'] = $contextID;

$arResult['FILTER'] = array(
	array('id' => 'ACCOUNT_NUMBER'),
	array('id' => 'ORDER_TOPIC'),
	array('id' => 'STATUS_ID'),
	array('id' => 'RESPONSIBLE_ID')
);

$arResult['FILTER_PRESETS'] = array(
	'filter_my_unpaid' => array(
		'name' => GetMessage('M_CRM_INVOICE_LIST_PRESET_MY_UNPAID'),
		'fields' => array('RESPONSIBLE_ID' => $currentUserID, 'STATUS_ID'=> CCrmStatusInvoice::getStatusIds('neutral'))
	),
	'filter_my_paid' => array(
		'name' => GetMessage('M_CRM_INVOICE_LIST_PRESET_MY_PAID'),
		'fields' => array('RESPONSIBLE_ID' => $currentUserID, 'STATUS_ID'=> CCrmStatusInvoice::getStatusIds('success'))
	)
);

$itemPerPage = isset($arParams['ITEM_PER_PAGE']) ? intval($arParams['ITEM_PER_PAGE']) : 0;
if($itemPerPage <= 0)
{
	$itemPerPage = 20;
}
$arParams['ITEM_PER_PAGE'] = $itemPerPage;

$sort = array('ID' => 'ASC');
$filter = array();
$navParams = array(
	'nPageSize' => $itemPerPage,
	'iNumPage' => $enablePaging ? false : 1,
	'bShowAll' => false
);
$select = array(
	'ID', 'ACCOUNT_NUMBER',
	'UF_DEAL_ID', 'UF_COMPANY_ID', 'UF_CONTACT_ID',
	'PRICE', 'CURRENCY', 'STATUS_ID', 'ORDER_TOPIC',
	'RESPONSIBLE_ID', 'RESPONSIBLE_LOGIN', 'RESPONSIBLE_NAME',
	'RESPONSIBLE_LAST_NAME', 'RESPONSIBLE_SECOND_NAME'
);

$arOptions = array();

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
		$arResult['GRID_FILTER_NAME'] = GetMessage('M_CRM_INVOICE_LIST_FILTER_NONE');
	}
	else
	{
		if(isset($filter['GRID_FILTER_APPLIED']) && $filter['GRID_FILTER_APPLIED'] && isset($filter['GRID_FILTER_ID']))
		{
			$filterID = $filter['GRID_FILTER_ID'];
			$arResult['GRID_FILTER_ID'] = $filterID;

			$arResult['GRID_FILTER_NAME'] = isset($arResult['FILTER_PRESETS'][$filterID])
				? $arResult['FILTER_PRESETS'][$filterID]['name']
				: GetMessage('M_CRM_INVOICE_LIST_FILTER_CUSTOM');
		}
		else
		{
			$arResult['GRID_FILTER_NAME'] = GetMessage('M_CRM_INVOICE_LIST_FILTER_CUSTOM');
		}

		if(isset($filter['ORDER_TOPIC']))
		{
			$filter['~ORDER_TOPIC'] = "%{$filter['ORDER_TOPIC']}%";
			unset($filter['ORDER_TOPIC']);
		}

		if(isset($filter['ACCOUNT_NUMBER']))
		{
			$filter['~ACCOUNT_NUMBER'] = "%{$filter['ACCOUNT_NUMBER']}%";
			unset($filter['ACCOUNT_NUMBER']);
		}
	}
}

//Setup default filter name ('NONE') if it is not assigned
if(!isset($arResult['GRID_FILTER_NAME']) || $arResult['GRID_FILTER_NAME'] === '')
{
	$arResult['GRID_FILTER_NAME'] = GetMessage('M_CRM_INVOICE_LIST_FILTER_NONE');
}

$arResult['ITEMS'] = array();
$dbRes = CCrmInvoice::GetList($sort, $filter, false, $navParams, $select, array());
if(!is_object($dbRes))
{
	$arResult['NEXT_PAGE_URL'] = '';
}
else
{
	$dbRes->NavStart($navParams['nPageSize'], false);

	$arResult['PAGE_NAVNUM'] = intval($dbRes->NavNum); // pager index
	$arResult['PAGE_NUMBER'] = intval($dbRes->NavPageNomer); // current page index
	$arResult['PAGE_NAVCOUNT'] = intval($dbRes->NavPageCount); // page count
	$arResult['PAGER_PARAM'] = "PAGEN_{$arResult['PAGE_NAVNUM']}";
	$arResult['PAGE_NEXT_NUMBER'] = $arResult['PAGE_NUMBER'] + 1;

	$enums = array();
	while($item = $dbRes->GetNext())
	{
		CCrmMobileHelper::PrepareInvoiceItem($item, $arParams, $enums);
		$arResult['ITEMS'][] = &$item;
		unset($item);
	}

	//NEXT_PAGE_URL, SEARCH_PAGE_URL, SERVICE_URL -->
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
}

$arResult['SEARCH_PAGE_URL'] = $APPLICATION->GetCurPageParam(
	'AJAX_CALL=Y&SEARCH=Y&FORMAT=json&apply_filter=Y&save=Y',
	array('AJAX_CALL', 'SEARCH', 'FORMAT', 'save', 'apply_filter', 'clear_filter')
);
$arResult['SERVICE_URL'] = ($arParams["SERVICE_URL"]
	? $arParams["SERVICE_URL"]
	: SITE_DIR.'bitrix/components/bitrix/mobile.crm.invoice.edit/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get()
);
//<-- NEXT_PAGE_URL, SEARCH_PAGE_URL, SERVICE_URL

$arResult['PERMISSIONS'] = array(
	'CREATE' => !$userPerms->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'ADD')
);

$arResult['CREATE_URL'] = $arParams['INVOICE_EDIT_URL_TEMPLATE'] !== ''
	? CComponentEngine::makePathFromTemplate(
		$arParams['INVOICE_EDIT_URL_TEMPLATE'],
		array('invoice_id' => 0)
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

