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
$arResult['RUBRIC'] = array('ENABLED' => false);

$enablePaging = $arResult['ENABLE_PAGING'] = isset($_REQUEST['PAGING']) && strtoupper($_REQUEST['PAGING']) === 'Y';
$enableSearch = $arResult['ENABLE_SEARCH'] = isset($_REQUEST['SEARCH']) && strtoupper($_REQUEST['SEARCH']) === 'Y';
if($enableSearch)
{
	// decode encodeURIComponent params
	CUtil::JSPostUnescape();
}

$contactID = $arResult['CONTACT_ID'] = isset($_REQUEST['contact_id']) ? intval($_REQUEST['contact_id']) : 0;
$companyID = $arResult['COMPANY_ID'] = isset($_REQUEST['company_id']) ? intval($_REQUEST['company_id']) : 0;
$arResult['SHOW_SEARCH_PANEL'] = $contactID <= 0 && $companyID <= 0;

$currentUserID = $arResult['USER_ID'] = intval(CCrmSecurityHelper::GetCurrentUserID());
$arParams['DEAL_SHOW_URL_TEMPLATE'] =  isset($arParams['DEAL_SHOW_URL_TEMPLATE']) ? $arParams['DEAL_SHOW_URL_TEMPLATE'] : '';
$arParams['DEAL_EDIT_URL_TEMPLATE'] =  isset($arParams['DEAL_EDIT_URL_TEMPLATE']) ? $arParams['DEAL_EDIT_URL_TEMPLATE'] : '';
$arParams['COMPANY_SHOW_URL_TEMPLATE'] = isset($arParams['COMPANY_SHOW_URL_TEMPLATE']) ? $arParams['COMPANY_SHOW_URL_TEMPLATE'] : '';
$arParams['CONTACT_SHOW_URL_TEMPLATE'] = isset($arParams['CONTACT_SHOW_URL_TEMPLATE']) ? $arParams['CONTACT_SHOW_URL_TEMPLATE'] : '';
$arParams['USER_PROFILE_URL_TEMPLATE'] = isset($arParams['USER_PROFILE_URL_TEMPLATE']) ? $arParams['USER_PROFILE_URL_TEMPLATE'] : '';
$arParams['NAME_TEMPLATE'] = isset($arParams['NAME_TEMPLATE']) ? str_replace(array('#NOBR#', '#/NOBR#'), array('', ''), $arParams['NAME_TEMPLATE']) : CSite::GetNameFormat(false);

$arParams['UID'] = isset($arParams['UID']) ? $arParams['UID'] : '';
if(!isset($arParams['UID']) || $arParams['UID'] === '')
{
	$arParams['UID'] = 'mobile_crm_deal_list';
}
$arResult['UID'] = $arParams['UID'];

$arParams['PULL_TAG'] = $arResult['PULL_TAG'] = isset($arParams['PULL_TAG']) ? $arParams['PULL_TAG'] : 'CRM_DEAL_CHANGE';
$arParams['PULL_UPDATE_CMD'] = $arResult['PULL_UPDATE_CMD'] = isset($arParams['PULL_UPDATE_CMD']) ? $arParams['PULL_UPDATE_CMD'] : 'crm_deal_update';
$arParams['PULL_DELETE_CMD'] = $arResult['PULL_DELETE_CMD'] = isset($arParams['PULL_DELETE_CMD']) ? $arParams['PULL_DELETE_CMD'] : 'crm_deal_delete';

$mode = isset($arParams['MODE']) ? $arParams['MODE'] : '';
if($mode === '' && isset($_REQUEST['mode']))
{
	$mode = $_REQUEST['mode'];
}
$mode = strtoupper(trim($mode));
$arResult['MODE'] = $arParams['MODE'] = $mode;

$contextID = isset($arParams['CONTEXT_ID']) ? $arParams['CONTEXT_ID'] : '';
if($contextID === '' && isset($_REQUEST['context_id']))
{
	$contextID = $_REQUEST['context_id'];
}
$arResult['CONTEXT_ID'] = $arParams['CONTEXT_ID'] = $contextID;

$arResult['FILTER'] = array(
	array('id' => 'TITLE'),
	array('id' => 'CLOSED'),
	array('id' => 'STAGE_ID'),
	array('id' => 'STAGE_SORT'),
	array('id' => 'ASSIGNED_BY_ID')
);

$finalStageID = CCrmDeal::GetFinalStageID();
$finalStageSort = CCrmDeal::GetFinalStageSort();
$arResult['FILTER_PRESETS'] = array(
	//'filter_new' => array('name' => GetMessage('M_CRM_DEAL_LIST_PRESET_NEW'), 'fields' => array('STAGE_ID' => array('NEW'))),
	//'filter_my' => array('name' => GetMessage('M_CRM_DEAL_LIST_PRESET_MY'), 'fields' => array('ASSIGNED_BY_ID' => $currentUserID))
	'filter_my_not_completed' => array('name' => GetMessage('M_CRM_DEAL_LIST_PRESET_MY_NOT_COMPLETED'), 'fields' => array('ASSIGNED_BY_ID' => $currentUserID, 'CLOSED' => 'N')),
	'filter_not_completed' => array('name' => GetMessage('M_CRM_DEAL_LIST_PRESET_NOT_COMPLETED'), 'fields' => array('CLOSED' => 'N')),
	'filter_completed' => array('name' => GetMessage('M_CRM_DEAL_LIST_PRESET_COMPLETED'), 'fields' => array('CLOSED' => 'Y')),
	'filter_won' => array('name' => GetMessage('M_CRM_DEAL_LIST_PRESET_WON'), 'fields' => array('STAGE_ID' => $finalStageID)),
	'filter_failed' => array('name' => GetMessage('M_CRM_DEAL_LIST_PRESET_FAILED'), 'fields' => array('STAGE_SORT_from' => $finalStageSort))
);

$itemPerPage = isset($arParams['ITEM_PER_PAGE']) ? intval($arParams['ITEM_PER_PAGE']) : 0;
if($itemPerPage <= 0)
{
	$itemPerPage = 20;
}
$arParams['ITEM_PER_PAGE'] = $itemPerPage;

$sort = array('DATE_CREATE' => 'ASC');
$filter = array();
$navParams = array(
	'nPageSize' => $itemPerPage,
	'iNumPage' => $enablePaging ? false : 1,
	'bShowAll' => false
);
$select = array(
	'ID', 'TITLE', 'STAGE_ID', 'PROBABILITY', 'OPPORTUNITY', 'CURRENCY_ID',
	'ASSIGNED_BY_ID', 'ASSIGNED_BY_LOGIN', 'ASSIGNED_BY_NAME', 'ASSIGNED_BY_SECOND_NAME', 'ASSIGNED_BY_LAST_NAME',
	'CONTACT_ID', 'CONTACT_NAME', 'CONTACT_SECOND_NAME', 'CONTACT_LAST_NAME', 'CONTACT_POST', 'CONTACT_PHOTO',
	'COMPANY_ID', 'COMPANY_TITLE', 'COMMENTS',
	'DATE_CREATE', 'DATE_MODIFY'
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
				: GetMessage('M_CRM_DEAL_LIST_FILTER_CUSTOM');
		}
		else
		{
			$arResult['GRID_FILTER_NAME'] = GetMessage('M_CRM_DEAL_LIST_FILTER_CUSTOM');
		}

		if(isset($filter['TITLE']))
		{
			$filter['%TITLE'] = $filter['TITLE'];
			unset($filter['TITLE']);
		}


		$filterByStageSort = false;
		if(isset($filter['CLOSED']))
		{
			//HACK: temporary skip CLOSE flag
			if($filter['CLOSED'] === 'Y')
			{
				$filter['>=STAGE_SORT'] = $finalStageSort;
			}
			else
			{
				$filter['<STAGE_SORT'] = $finalStageSort;
			}
			$filterByStageSort = true;
			unset($filter['CLOSED']);
		}
		if(isset($filter['STAGE_SORT_to']))
		{
			$filter['<STAGE_SORT'] = $filter['STAGE_SORT_to'];
			unset($filter['STAGE_SORT_to']);
			$filterByStageSort = true;
		}

		if(isset($filter['STAGE_SORT_from']))
		{
			$filter['>STAGE_SORT'] = $filter['STAGE_SORT_from'];
			unset($filter['STAGE_SORT_from']);
			$filterByStageSort = true;
		}

		if($filterByStageSort)
		{
			$arOptions['FIELD_OPTIONS'] = array(
				'ADDITIONAL_FIELDS' => array('STAGE_SORT')
			);
		}
	}
}

if($contactID > 0 || $companyID > 0)
{
	$arResult['RUBRIC']['ENABLED'] = true;

	if($contactID > 0)
	{
		$filter['=CONTACT_ID'] = $contactID;
		$arResult['RUBRIC']['TITLE'] = CCrmOwnerType::GetCaption(CCrmOwnerType::Contact, $contactID);
	}
	else//if($companyID > 0)
	{
		$filter['=COMPANY_ID'] = $companyID;
		$arResult['RUBRIC']['TITLE'] = CCrmOwnerType::GetCaption(CCrmOwnerType::Company, $companyID);
	}

	$arResult['RUBRIC']['FILTER_PRESETS'] = array('clear_filter', 'filter_not_completed', 'filter_completed');
}

//Setup default filter name ('NONE') if it is not assigned
if(!isset($arResult['GRID_FILTER_NAME']) || $arResult['GRID_FILTER_NAME'] === '')
{
	$arResult['GRID_FILTER_NAME'] = GetMessage('M_CRM_DEAL_LIST_FILTER_NONE');
}

$arResult['STAGE_LIST'] = CCrmStatus::GetStatusList('DEAL_STAGE');
$arResult['TYPE_LIST'] = CCrmStatus::GetStatusList('DEAL_TYPE');
$arResult['ITEMS'] = array();

$dbRes = CCrmDeal::GetListEx($sort, $filter, false, $navParams, $select, $arOptions);
$dbRes->NavStart($navParams['nPageSize'], false);

$arResult['PAGE_NAVNUM'] = intval($dbRes->NavNum); // pager index
$arResult['PAGE_NUMBER'] = intval($dbRes->NavPageNomer); // current page index
$arResult['PAGE_NAVCOUNT'] = intval($dbRes->NavPageCount); // page count
$arResult['PAGER_PARAM'] = "PAGEN_{$arResult['PAGE_NAVNUM']}";
$arResult['PAGE_NEXT_NUMBER'] = $arResult['PAGE_NUMBER'] + 1;
/*if($arResult['PAGE_NEXT_NUMBER'] > $arResult['PAGE_NAVCOUNT'])
{
	$arResult['PAGE_NEXT_NUMBER'] = 1;
}*/

$arEnums = array(
	'STAGE_LIST' => $arResult['STAGE_LIST'],
	'TYPE_LIST' => $arResult['TYPE_LIST']
);
while($item = $dbRes->GetNext())
{
	CCrmMobileHelper::PrepareDealItem($item, $arParams, $arEnums);
	$arResult['ITEMS'][] = &$item;
	unset($item);
}

//NEXT_PAGE_URL, SEARCH_PAGE_URL, SERVICE_URL -->
if($arResult['PAGE_NEXT_NUMBER'] > $arResult['PAGE_NAVCOUNT'])
{
	$arResult['NEXT_PAGE_URL'] = '';
}
elseif($contactID > 0)
{
	$arResult['NEXT_PAGE_URL'] = $APPLICATION->GetCurPageParam(
		'AJAX_CALL=Y&PAGING=Y&FORMAT=json&contact_id='.$contactID.'&'.$arResult['PAGER_PARAM'].'='.$arResult['PAGE_NEXT_NUMBER'],
		array('AJAX_CALL', 'PAGING', 'FORMAT', 'SEARCH', 'contact_id', 'company_id', $arResult['PAGER_PARAM'])
	);
}
elseif($companyID > 0)
{
	$arResult['NEXT_PAGE_URL'] = $APPLICATION->GetCurPageParam(
		'AJAX_CALL=Y&PAGING=Y&FORMAT=json&company_id='.$companyID.'&'.$arResult['PAGER_PARAM'].'='.$arResult['PAGE_NEXT_NUMBER'],
		array('AJAX_CALL', 'PAGING', 'FORMAT', 'SEARCH', 'contact_id', 'company_id', $arResult['PAGER_PARAM'])
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
$arResult['SERVICE_URL'] = ($arParams["SERVICE_URL"]
	? $arParams["SERVICE_URL"]
	:SITE_DIR.'bitrix/components/bitrix/mobile.crm.deal.list/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get()
);
//<-- NEXT_PAGE_URL, SEARCH_PAGE_URL, SERVICE_URL

$arResult['PERMISSIONS'] = array(
	'CREATE' => CCrmDeal::CheckCreatePermission()
);

$arResult['CREATE_URL'] = $arParams['DEAL_EDIT_URL_TEMPLATE'] !== ''
	? CComponentEngine::MakePathFromTemplate(
		$arParams['DEAL_EDIT_URL_TEMPLATE'],
		array(
			'deal_id' => 0,
			'company_id' => $companyID,
			'contact_id' => $contactID
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

