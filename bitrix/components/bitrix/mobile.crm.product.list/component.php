<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if(!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

global $APPLICATION;

$userPerms = CCrmPerms::GetCurrentUserPermissions();
if (!$userPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$activeSectionID = $arParams['SECTION_ID'] = isset($arParams['SECTION_ID']) ? intval($arParams['SECTION_ID']) : 0;
if(isset($_GET['SECTION_ID']))
{
	$activeSectionID = intval($_GET['SECTION_ID']);
}
$arResult['SECTION_ID'] = $activeSectionID;

//$arParams['PRODUCT_SECTION_URL_TEMPLATE'] = isset($arParams['PRODUCT_SECTION_URL_TEMPLATE']) ? $arParams['PRODUCT_SECTION_URL_TEMPLATE'] : '';
$activeTab = $arParams['ACTIVE_TAB'] = isset($arParams['ACTIVE_TAB']) ? strtoupper($arParams['ACTIVE_TAB']) : 'PRODUCT';
if(!in_array($activeTab, array('PRODUCT', 'SECTION'), true))
{
	$activeTab = 'PRODUCT';
}
$arResult['ACTIVE_TAB'] = $activeTab;

$currencyID = $arParams['CURRENCY_ID'] = isset($arParams['CURRENCY_ID']) ? $arParams['CURRENCY_ID'] : '';
if(isset($_GET['currency_id']))
{
	$currencyID = $_GET['currency_id'];
}
$arResult['CURRENCY_ID'] = $currencyID;

$listMode = $arParams['LIST_MODE'] = isset($arParams['LIST_MODE']) ? strtoupper($arParams['LIST_MODE']) : '';
if(isset($_GET['list_mode']))
{
	$listMode = strtoupper($_GET['list_mode']);
}
$arResult['LIST_MODE'] = $listMode;

$enablePaging = $arResult['ENABLE_PAGING'] = isset($_GET['PAGING']) && strtoupper($_GET['PAGING']) === 'Y';
$enableSearch = $arResult['ENABLE_SEARCH'] = isset($_GET['SEARCH']) && strtoupper($_GET['SEARCH']) === 'Y';
if($enableSearch)
{
	// decode encodeURIComponent params
	CUtil::JSPostUnescape();
}

$catalogID = isset($arParams['CATALOG_ID']) ? intval($arParams['CATALOG_ID']) : 0;
if($catalogID <= 0)
{
	$catalogID = CCrmCatalog::EnsureDefaultExists();
}
$arResult['CATALOG_ID'] = $catalogID;

// SECTIONS -->
CModule::IncludeModule('iblock');
$dbSections = CIBlockSection::GetList(
	array('left_margin' => 'asc'),
	array(
		'IBLOCK_ID' => $catalogID,
		//'SECTION_ID' => $activeSectionID,
		'GLOBAL_ACTIVE' => 'Y',
		'CHECK_PERMISSIONS' => 'N'
	),
	false,
	array('ID', 'NAME'),
	false
);

$arResult['ALL_SECTIONS'] = array();
while($section = $dbSections->GetNext())
{
	$sectionID = $section['ID'] = intval($section['ID']);
	$arResult['ALL_SECTIONS'][$sectionID] = &$section;
	unset($section);
}

$dbSections = CIBlockSection::GetList(
	array('left_margin' => 'asc'),
	array(
		'IBLOCK_ID' => $catalogID,
		'SECTION_ID' => $activeSectionID,
		'GLOBAL_ACTIVE' => 'Y',
		'CHECK_PERMISSIONS' => 'N'
	),
	false,
	array('ID', 'NAME'),
	false
);
$arResult['SECTIONS'] = array();
while($section = $dbSections->GetNext())
{
	$sectionID = $section['ID'] = intval($section['ID']);
	$arResult['SECTIONS'][$sectionID] = &$section;
	unset($section);
}

if($activeSectionID > 0 && isset($arResult['ALL_SECTIONS'][$activeSectionID]))
{
	$arResult['ACTIVE_SECTION'] = $arResult['ALL_SECTIONS'][$activeSectionID];
}
//<-- SECTIONS

$arResult['SHOW_SEARCH_PANEL'] = true;
$currentUserID = $arResult['USER_ID'] = intval(CCrmSecurityHelper::GetCurrentUserID());

$arParams['UID'] = isset($arParams['UID']) ? $arParams['UID'] : '';
if(!isset($arParams['UID']) || $arParams['UID'] === '')
{
	$arParams['UID'] = 'mobile_crm_product_list';
}
$arResult['UID'] = $arParams['UID'];

$sort = array('LAST_NAME' => 'ASC', 'NAME' => 'ASC');
$select = array('ID', 'NAME', 'PRICE', 'CURRENCY_ID', 'SECTION_ID');
$filter = array('CATALOG_ID' => $catalogID);
if($activeSectionID > 0)
{
	$filter['SECTION_ID'] = $activeSectionID;
}

$itemPerPage = isset($arParams['ITEM_PER_PAGE']) ? intval($arParams['ITEM_PER_PAGE']) : 0;
if($itemPerPage <= 0)
{
	$itemPerPage = 20;
}
$arParams['ITEM_PER_PAGE'] = $itemPerPage;

$navParams = array(
	'nPageSize' => $itemPerPage,
	'iNumPage' => $enablePaging ? false : 1,
	'bShowAll' => false
);
$navigation = CDBResult::GetNavParams($navParams);
$CGridOptions = new CCrmGridOptions($arResult['UID']);
$navParams = $CGridOptions->GetNavParams($navParams);
$navParams['bShowAll'] = !$enablePaging;

if($enableSearch)
{
	$filter += $CGridOptions->GetFilter(array(array('id' => 'NAME')));
	if(!empty($filter))
	{
		if(isset($filter['NAME']))
		{
			$filter['%NAME'] = $filter['NAME'];
			unset($filter['NAME']);
		}
	}
	else
	{
		$enableSearch = $arResult['ENABLE_SEARCH'] = false;
	}
}

$arResult['PRODUCTS'] = array();

$arPricesSelect = $arVatsSelect = array();
$select = CCrmProduct::DistributeProductSelect($select, $arPricesSelect, $arVatsSelect);
$dbProducts = CCrmProduct::GetList($sort, $filter, $select, $navParams);
$dbProducts->NavStart($navParams['nPageSize'], false);

$arResult['PAGE_NAVNUM'] = intval($dbProducts->NavNum); // pager index
$arResult['PAGE_NUMBER'] = intval($dbProducts->NavPageNomer); // current page index
$arResult['PAGE_NAVCOUNT'] = intval($dbProducts->NavPageCount); // page count
$arResult['PAGER_PARAM'] = "PAGEN_{$arResult['PAGE_NAVNUM']}";
$arResult['PAGE_NEXT_NUMBER'] = $arResult['PAGE_NUMBER'] + 1;

$productParams = array(
	'CURRENCY_ID' => $currencyID,
	'SECTIONS' => &$arResult['ALL_SECTIONS']
);

$arProducts = $arProductId = array();
while ($product = $dbProducts->GetNext())
{
	foreach ($arPricesSelect as $fieldName)
		$product['~'.$fieldName] = $product[$fieldName] = null;
	foreach ($arVatsSelect as $fieldName)
		$product['~'.$fieldName] = $product[$fieldName] = null;
	$arProductId[] = $product['ID'];
	$arProducts[$product['ID']] = $product;
}
CCrmProduct::ObtainPricesVats($arProducts, $arProductId, $arPricesSelect, $arVatsSelect);
unset($arProductId, $arPricesSelect, $arVatsSelect);

foreach ($arProducts as &$product)
{
	CCrmMobileHelper::PrepareProductItem($product, $productParams);
	$arResult['PRODUCTS'][] = $product;
}
unset($arProducts);

$arResult['PRODUCT_SECTION_URL_TEMPLATE'] = $APPLICATION->GetCurPageParam(
	"AJAX_CALL=Y&FORMAT=json&SECTION_ID=#section_id#",
	array('AJAX_CALL', 'FORMAT', 'SECTION_ID', 'SEARCH', 'PAGING', $arResult['PAGER_PARAM'])
);

$productSectionParams = array(
	'PRODUCT_SECTION_URL_TEMPLATE' => $arResult['PRODUCT_SECTION_URL_TEMPLATE']
);
foreach($arResult['SECTIONS'] as $sectionID => &$section)
{
	CCrmMobileHelper::PrepareProductSectionItem($section, $productSectionParams);
}
unset($section);

//NEXT_PAGE_URL, SEARCH_PAGE_URL, SERVICE_URL -->
if($arResult['PAGE_NEXT_NUMBER'] > $arResult['PAGE_NAVCOUNT'])
{
	$arResult['NEXT_PAGE_URL'] = '';
}
elseif($activeSectionID > 0)
{
	$arResult['NEXT_PAGE_URL'] = $APPLICATION->GetCurPageParam(
		'AJAX_CALL=Y&PAGING=Y&FORMAT=json&SECTION_ID='.$activeSectionID.'&'.$arResult['PAGER_PARAM'].'='.$arResult['PAGE_NEXT_NUMBER'],
		array('AJAX_CALL', 'PAGING', 'FORMAT', 'SEARCH', 'SECTION_ID', $arResult['PAGER_PARAM'])
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
	'AJAX_CALL=Y&SEARCH=Y&FORMAT=json',
	array('AJAX_CALL', 'SEARCH', 'FORMAT')
);

$serviceURLTemplate = ($arParams["SERVICE_URL_TEMPLATE"]
	? $arParams["SERVICE_URL_TEMPLATE"]
	: '#SITE_DIR#bitrix/components/bitrix/mobile.crm.product.list/ajax.php?site_id=#SITE#&sessid=#SID#'
);

$arResult['SERVICE_URL'] = CComponentEngine::MakePathFromTemplate(
	$serviceURLTemplate,
	array('SID' => bitrix_sessid())
);
//<-- NEXT_PAGE_URL, SEARCH_PAGE_URL, SERVICE_URL

$format = isset($_REQUEST['FORMAT']) ? strtolower($_REQUEST['FORMAT']) : '';
// Only JSON format is supported
if($format !== '' && $format !== 'json')
{
	$format = '';
}
$this->IncludeComponentTemplate($format);
