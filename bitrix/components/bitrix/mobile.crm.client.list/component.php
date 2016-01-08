<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if(!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

$entityTypes = isset($arParams['ENTITY_TYPES']) ? $arParams['ENTITY_TYPES'] : array();
if(empty($entityTypes) && isset($_REQUEST['entityTypes']) && is_array($_REQUEST['entityTypes']))
{
	$entityTypes = $_REQUEST['entityTypes'];
}
if(empty($entityTypes))
{
	$entityTypes = array(
		CCrmOwnerType::ContactName,
		CCrmOwnerType::CompanyName
	);
}

$effectiveEntityTypes = array();
$userPerms = CCrmPerms::GetCurrentUserPermissions();
foreach($entityTypes as $entityTypeName)
{
	$entityTypeName = strtoupper($entityTypeName);
	if(CCrmAuthorizationHelper::CheckReadPermission($entityTypeName, 0, $userPerms))
	{
		$effectiveEntityTypes[] = $entityTypeName;
	}
}

if(empty($effectiveEntityTypes))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arParams['ENABLE_CREATION'] = $arResult['ENABLE_CREATION'] = isset($arParams['ENABLE_CREATION']) ? (bool)$arParams['ENABLE_CREATION'] : true;
$arParams['COMPANY_EDIT_URL_TEMPLATE'] = isset($arParams['COMPANY_EDIT_URL_TEMPLATE']) ? $arParams['COMPANY_EDIT_URL_TEMPLATE'] : '';
$arParams['CONTACT_EDIT_URL_TEMPLATE'] = isset($arParams['CONTACT_EDIT_URL_TEMPLATE']) ? $arParams['CONTACT_EDIT_URL_TEMPLATE'] : '';
$arParams['NAME_TEMPLATE'] = isset($arParams['NAME_TEMPLATE']) ? str_replace(array('#NOBR#', '#/NOBR#'), array('', ''), $arParams['NAME_TEMPLATE']) : CSite::GetNameFormat(false);

$selectedEntityType = isset($arParams['SELECTED_ENTITY_TYPE']) ? $arParams['SELECTED_ENTITY_TYPE'] : '';
if($selectedEntityType === '' || !in_array($effectiveEntityTypes, $entityTypes, true))
{
	$selectedEntityType = $effectiveEntityTypes[0];
}
$arResult['SELECTED_ENTITY_TYPE'] = $arParams['SELECTED_ENTITY_TYPE'] = $selectedEntityType;

global $APPLICATION;

$itemPerPage = isset($arParams['ITEM_PER_PAGE']) ? intval($arParams['ITEM_PER_PAGE']) : 0;
if($itemPerPage <= 0)
{
	$itemPerPage = 20;
}
$arParams['ITEM_PER_PAGE'] = $itemPerPage;

$enablePaging = $arResult['ENABLE_PAGING'] = isset($_GET['PAGING']) && strtoupper($_GET['PAGING']) === 'Y';
$enableSearch = $arResult['ENABLE_SEARCH'] = isset($_GET['SEARCH']) && strtoupper($_GET['SEARCH']) === 'Y';
if($enableSearch)
{
	// decode encodeURIComponent params
	CUtil::JSPostUnescape();
}

$contextID = isset($arParams['CONTEXT_ID']) ? $arParams['CONTEXT_ID'] : '';
if($contextID === '' && isset($_REQUEST['context_id']))
{
	$contextID = $_REQUEST['context_id'];
}
$arResult['CONTEXT_ID'] = $arParams['CONTEXT_ID'] = $contextID;

$scope = isset($arParams['SCOPE']) ? $arParams['SCOPE'] : '';
if($scope === '' && isset($_REQUEST['scope']))
{
	$scope = $_REQUEST['scope'];
}
$arResult['SCOPE'] = $arParams['SCOPE'] = $scope;

$uid = isset($arParams['UID']) ? $arParams['UID'] : '';
if(!isset($arParams['UID']) || $arParams['UID'] === '')
{
	$uid = 'mobile_crm_client_list';
}
else
{
	$uid = str_replace(array('#SCOPE#', '#CONTEXT_ID#'), array($scope, $contextID), $uid);
}

$arResult['UID'] = $arParams['UID'] = $uid;

$arResult['EFFECTIVE_ENTITY_TYPES'] = $effectiveEntityTypes;
$arResult['ENTITY_DATA'] = array();

$contactSort = array('LAST_NAME' => 'ASC', 'NAME' => 'ASC', 'SECOND_NAME' => 'ASC');
$companySort = array('TITLE' => 'ASC');

$contactSelect = array('ID', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'POST', 'PHOTO', 'COMPANY_ID', 'COMPANY_TITLE');
$companySelect = array('ID', 'TITLE', 'LOGO');

// CONTACT -->
if(!in_array(CCrmOwnerType::ContactName, $effectiveEntityTypes, true))
{
	//HACK: For $dbRes->NavNum correction
	$dbRes = new CDBResult();
	$dbRes->InitNavStartVars();
}
else
{
	$dataUid = $uid.'_'.strtolower(CCrmOwnerType::ContactName);
	$arResult['ENTITY_DATA'][CCrmOwnerType::ContactName] = array(
		'UID' => $uid.'_'.strtolower(CCrmOwnerType::ContactName),
		'ITEMS' => array()
	);

	$sort = $contactSort;
	$select = $contactSelect;
	$filter = array();
	$filterFields = array(
		array('id' => 'FULL_NAME'),
		array('id' => 'COMPANY_ID')
	);

	$navParams = array(
		'nPageSize' => $itemPerPage,
		'iNumPage' => $enablePaging ? false : 1,
		'bShowAll' => false
	);

	$navigation = CDBResult::GetNavParams($navParams);
	$CGridOptions = new CCrmGridOptions($dataUid);
	$navParams = $CGridOptions->GetNavParams($navParams);
	$navParams['bShowAll'] = false;

	if($enableSearch)
	{
		$filter += $CGridOptions->GetFilter($filterFields);
		if(!empty($filter))
		{
			if(isset($filter['FULL_NAME']))
			{
				$filter['%FULL_NAME'] = $filter['FULL_NAME'];
				unset($filter['FULL_NAME']);
			}
		}
		else
		{
			$enableSearch = $arResult['ENABLE_SEARCH'] = false;
		}
	}

	$arResult['ENTITY_DATA'][CCrmOwnerType::ContactName]['FILTER'] = &$filterFields;
	unset($filterFields);

	$dbRes = CCrmContact::GetListEx($sort, $filter, false, $navParams, $select);
	$dbRes->NavStart($navParams['nPageSize'], false);

	while($item = $dbRes->GetNext())
	{
		CCrmMobileHelper::PrepareContactItem($item, $arParams);

		$arResult['ENTITY_DATA'][CCrmOwnerType::ContactName]['ITEMS'][] = &$item;
		unset($item);
	}

	$navigationData = array(
		'PAGE_NAVNUM' => intval($dbRes->NavNum),
		'PAGE_NUMBER' => intval($dbRes->NavPageNomer),
		'PAGE_NAVCOUNT' => intval($dbRes->NavPageCount)
	);

	$navigationData['PAGER_PARAM'] = "PAGEN_{$navigationData['PAGE_NAVNUM']}";
	$navigationData['PAGE_NEXT_NUMBER'] = $navigationData['PAGE_NUMBER'] + 1;

	if($navigationData['PAGE_NEXT_NUMBER'] > $navigationData['PAGE_NAVCOUNT'])
	{
		$navigationData['NEXT_PAGE_URL'] = '';
	}
	else
	{
		$navigationData['NEXT_PAGE_URL'] = $APPLICATION->GetCurPageParam(
			'AJAX_CALL=Y&PAGING=Y&FORMAT=json&entityTypes[]='.strtolower(CCrmOwnerType::ContactName).'&SEARCH='.($enableSearch ? 'Y' : 'N').'&'.$navigationData['PAGER_PARAM'].'='.$navigationData['PAGE_NEXT_NUMBER'],
			array('AJAX_CALL', 'PAGING', 'FORMAT', 'SEARCH', 'entityTypes', $navigationData['PAGER_PARAM'])
		);
	}

	$navigationData['SEARCH_PAGE_URL'] = $APPLICATION->GetCurPageParam(
		'AJAX_CALL=Y&SEARCH=Y&FORMAT=json&apply_filter=Y&save=Y&entityTypes[]='.strtolower(CCrmOwnerType::ContactName),
		array('AJAX_CALL', 'SEARCH', 'FORMAT', 'entityTypes', 'save', 'apply_filter', 'clear_filter')
	);

	$arResult['ENTITY_DATA'][CCrmOwnerType::ContactName]['NAVIGATION'] = &$navigationData;
	unset($navigationData);

	$arResult['ENTITY_DATA'][CCrmOwnerType::ContactName]['RELOAD_URL'] = $APPLICATION->GetCurPageParam(
		'AJAX_CALL=Y&FORMAT=json&entityTypes[]='.strtolower(CCrmOwnerType::ContactName),
		array('AJAX_CALL', 'SEARCH', 'FORMAT', 'entityTypes', 'save')
	);

	if($arParams['ENABLE_CREATION'])
	{
		$arResult['ENTITY_DATA'][CCrmOwnerType::ContactName]['CREATE_URL'] = $arParams['CONTACT_EDIT_URL_TEMPLATE'] !== ''
			? CComponentEngine::MakePathFromTemplate(
				$arParams['CONTACT_EDIT_URL_TEMPLATE'],
				array(
					'contact_id' => 0,
					'context_id' => $contextID
				)
			) : '';
	}
}
//<-- CONTACT
// COMPANY -->
if(!in_array(CCrmOwnerType::CompanyName, $effectiveEntityTypes, true))
{
	//HACK: For $dbRes->NavNum correction
	$dbRes = new CDBResult();
	$dbRes->InitNavStartVars();
}
else
{
	$dataUid = $uid.'_'.strtolower(CCrmOwnerType::CompanyName);
	$arResult['ENTITY_DATA'][CCrmOwnerType::CompanyName] = array(
		'UID' => $dataUid,
		'ITEMS' => array()
	);

	$sort = $companySort;
	$select = $companySelect;
	$filter = array();
	$filterFields = array(
		array('id' => 'ID'),
		array('id' => 'TITLE'),
		array('id' => 'CONTACT_ID')
	);

	$navParams = array(
		'nPageSize' => $itemPerPage,
		'iNumPage' => $enablePaging ? false : 1,
		'bShowAll' => false
	);

	$navigation = CDBResult::GetNavParams($navParams);
	$CGridOptions = new CCrmGridOptions($dataUid);
	$navParams = $CGridOptions->GetNavParams($navParams);
	$navParams['bShowAll'] = false;

	if($enableSearch)
	{
		$filter += $CGridOptions->GetFilter($filterFields);

		if(!empty($filter))
		{
			if(isset($filter['CONTACT_ID']))
			{
				//HACK: Geting contact company.
				$dbRes = CCrmContact::GetListEx($contactSort, array('=ID' => $filter['CONTACT_ID']), false, false, array('COMPANY_ID'));
				$contact = $item = $dbRes->Fetch();
				if(is_array($contact))
				{
					$filter['=ID'] = $contact['COMPANY_ID'];
				}
				unset($filter['CONTACT_ID']);
			}

			if(isset($filter['TITLE']))
			{
				$filter['%TITLE'] = $filter['TITLE'];
				unset($filter['TITLE']);
			}
		}
		else
		{
			$enableSearch = $arResult['ENABLE_SEARCH'] = false;
		}
	}

	$arResult['ENTITY_DATA'][CCrmOwnerType::CompanyName]['FILTER'] = &$filterFields;
	unset($filterFields);

	$dbRes = CCrmCompany::GetListEx($sort, $filter, false, $navParams, $select);
	$dbRes->NavStart($navParams['nPageSize'], false);

	$navigationData = array(
		'PAGE_NAVNUM' => intval($dbRes->NavNum),
		'PAGE_NUMBER' => intval($dbRes->NavPageNomer),
		'PAGE_NAVCOUNT' => intval($dbRes->NavPageCount)
	);

	while($item = $dbRes->GetNext())
	{
		CCrmMobileHelper::PrepareCompanyItem($item, $arParams);
		$arResult['ENTITY_DATA'][CCrmOwnerType::CompanyName]['ITEMS'][] = $item;
		unset($item);
	}

	$navigationData['PAGER_PARAM'] = "PAGEN_{$navigationData['PAGE_NAVNUM']}";
	$navigationData['PAGE_NEXT_NUMBER'] = $navigationData['PAGE_NUMBER'] + 1;

	if($navigationData['PAGE_NEXT_NUMBER'] > $navigationData['PAGE_NAVCOUNT'])
	{
		$navigationData['NEXT_PAGE_URL'] = '';
	}
	else
	{
		$navigationData['NEXT_PAGE_URL'] = $APPLICATION->GetCurPageParam(
			'AJAX_CALL=Y&PAGING=Y&FORMAT=json&entityTypes[]='.strtolower(CCrmOwnerType::CompanyName).'&SEARCH='.($enableSearch ? 'Y' : 'N').'&'.$navigationData['PAGER_PARAM'].'='.$navigationData['PAGE_NEXT_NUMBER'],
			array('AJAX_CALL', 'PAGING', 'FORMAT', 'SEARCH', 'entityTypes', $navigationData['PAGER_PARAM'])
		);
	}

	$navigationData['SEARCH_PAGE_URL'] = $APPLICATION->GetCurPageParam(
		'AJAX_CALL=Y&SEARCH=Y&FORMAT=json&apply_filter=Y&save=Y&entityTypes[]='.strtolower(CCrmOwnerType::CompanyName),
		array('AJAX_CALL', 'SEARCH', 'FORMAT', 'entityTypes', 'save', 'apply_filter', 'clear_filter')
	);

	$arResult['ENTITY_DATA'][CCrmOwnerType::CompanyName]['NAVIGATION'] = &$navigationData;
	unset($navigationData);

	$arResult['ENTITY_DATA'][CCrmOwnerType::CompanyName]['RELOAD_URL'] = $APPLICATION->GetCurPageParam(
		'AJAX_CALL=Y&FORMAT=json&entityTypes[]='.strtolower(CCrmOwnerType::CompanyName),
		array('AJAX_CALL', 'SEARCH', 'FORMAT', 'entityTypes', 'save')
	);

	if($arParams['ENABLE_CREATION'])
	{
		$arResult['ENTITY_DATA'][CCrmOwnerType::CompanyName]['CREATE_URL'] = $arParams['COMPANY_EDIT_URL_TEMPLATE'] !== ''
			? CComponentEngine::MakePathFromTemplate(
				$arParams['COMPANY_EDIT_URL_TEMPLATE'],
				array(
					'company_id' => 0,
					'context_id' => $contextID
				)
			) : '';
	}
}
//<-- COMPANY
$format = isset($_REQUEST['FORMAT']) ? strtolower($_REQUEST['FORMAT']) : '';
// Only JSON format is supported
if($format !== '' && $format !== 'json')
{
	$format = '';
}
$this->IncludeComponentTemplate($format);


