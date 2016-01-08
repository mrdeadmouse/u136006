<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if(!CCrmPerms::IsAccessEnabled())
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}
use Bitrix\Crm\Settings\DealSettings;

// Preparing of URL templates -->
$arParams['PATH_TO_COMPANY_LIST'] = (isset($arParams['PATH_TO_COMPANY_LIST']) && $arParams['PATH_TO_COMPANY_LIST'] !== '') ? $arParams['PATH_TO_COMPANY_LIST'] : '#SITE_DIR#crm/company/';
$arParams['PATH_TO_COMPANY_EDIT'] = (isset($arParams['PATH_TO_COMPANY_EDIT']) && $arParams['PATH_TO_COMPANY_EDIT'] !== '') ? $arParams['PATH_TO_COMPANY_EDIT'] : '#SITE_DIR#crm/company/edit/#company_id#/';
$arParams['PATH_TO_CONTACT_LIST'] = (isset($arParams['PATH_TO_CONTACT_LIST']) && $arParams['PATH_TO_CONTACT_LIST'] !== '') ? $arParams['PATH_TO_CONTACT_LIST'] : '#SITE_DIR#crm/contact/';
$arParams['PATH_TO_CONTACT_EDIT'] = (isset($arParams['PATH_TO_CONTACT_EDIT']) && $arParams['PATH_TO_CONTACT_EDIT'] !== '') ? $arParams['PATH_TO_CONTACT_EDIT'] : '#SITE_DIR#crm/contact/edit/#contact_id#/';
$arParams['PATH_TO_DEAL_LIST'] = (isset($arParams['PATH_TO_DEAL_LIST']) && $arParams['PATH_TO_DEAL_LIST'] !== '') ? $arParams['PATH_TO_DEAL_LIST'] : '#SITE_DIR#crm/deal/list/';
$arParams['PATH_TO_DEAL_EDIT'] = (isset($arParams['PATH_TO_DEAL_EDIT']) && $arParams['PATH_TO_DEAL_EDIT'] !== '') ? $arParams['PATH_TO_DEAL_EDIT'] : '#SITE_DIR#crm/deal/edit/#deal_id#/';
$arParams['PATH_TO_DEAL_WIDGET'] = (isset($arParams['PATH_TO_DEAL_WIDGET']) && $arParams['PATH_TO_DEAL_WIDGET'] !== '') ? $arParams['PATH_TO_DEAL_WIDGET'] : '#SITE_DIR#crm/deal/widget/';
$arParams['PATH_TO_LEAD_LIST'] = (isset($arParams['PATH_TO_LEAD_LIST']) && $arParams['PATH_TO_LEAD_LIST'] !== '') ? $arParams['PATH_TO_LEAD_LIST'] : '#SITE_DIR#crm/lead/';
$arParams['PATH_TO_LEAD_EDIT'] = (isset($arParams['PATH_TO_LEAD_EDIT']) && $arParams['PATH_TO_LEAD_EDIT'] !== '') ? $arParams['PATH_TO_LEAD_EDIT'] : '#SITE_DIR#crm/lead/edit/#lead_id#/';
$arParams['PATH_TO_QUOTE_LIST'] = (isset($arParams['PATH_TO_QUOTE_LIST']) && $arParams['PATH_TO_QUOTE_LIST'] !== '') ? $arParams['PATH_TO_QUOTE_LIST'] : '#SITE_DIR#crm/quote/';
$arParams['PATH_TO_QUOTE_EDIT'] = (isset($arParams['PATH_TO_QUOTE_EDIT']) && $arParams['PATH_TO_QUOTE_EDIT'] !== '') ? $arParams['PATH_TO_QUOTE_EDIT'] : '#SITE_DIR#crm/quote/edit/#quote_id#/';
$arParams['PATH_TO_INVOICE_LIST'] = (isset($arParams['PATH_TO_INVOICE_LIST']) && $arParams['PATH_TO_INVOICE_LIST'] !== '') ? $arParams['PATH_TO_INVOICE_LIST'] : '#SITE_DIR#crm/invoice/';
$arParams['PATH_TO_INVOICE_EDIT'] = (isset($arParams['PATH_TO_INVOICE_EDIT']) && $arParams['PATH_TO_INVOICE_EDIT'] !== '') ? $arParams['PATH_TO_INVOICE_EDIT'] : '#SITE_DIR#crm/invoice/edit/#invoice_id#/';
$arParams['PATH_TO_REPORT_LIST'] = (isset($arParams['PATH_TO_REPORT_LIST']) && $arParams['PATH_TO_REPORT_LIST'] !== '') ? $arParams['PATH_TO_REPORT_LIST'] : '#SITE_DIR#crm/reports/report/';
$arParams['PATH_TO_DEAL_FUNNEL'] = (isset($arParams['PATH_TO_DEAL_FUNNEL']) && $arParams['PATH_TO_DEAL_FUNNEL'] !== '') ? $arParams['PATH_TO_DEAL_FUNNEL'] : '#SITE_DIR#crm/reports/';
$arParams['PATH_TO_EVENT_LIST'] = (isset($arParams['PATH_TO_EVENT_LIST']) && $arParams['PATH_TO_EVENT_LIST'] !== '') ? $arParams['PATH_TO_EVENT_LIST'] : '#SITE_DIR#crm/events/';
$arParams['PATH_TO_PRODUCT_LIST'] = (isset($arParams['PATH_TO_PRODUCT_LIST']) && $arParams['PATH_TO_PRODUCT_LIST'] !== '') ? $arParams['PATH_TO_PRODUCT_LIST'] : '#SITE_DIR#crm/product/';
$arParams['PATH_TO_SETTINGS'] = (isset($arParams['PATH_TO_SETTINGS']) && $arParams['PATH_TO_SETTINGS'] !== '') ? $arParams['PATH_TO_SETTINGS'] : '#SITE_DIR#crm/configs/';
$arParams['PATH_TO_SEARCH_PAGE'] = (isset($arParams['PATH_TO_SEARCH_PAGE']) && $arParams['PATH_TO_SEARCH_PAGE'] !== '') ? $arParams['PATH_TO_SEARCH_PAGE'] : '#SITE_DIR#search/index.php?where=crm';

$arParams['PATH_TO_DEAL_INDEX'] = DealSettings::getDefaultListViewID() === DealSettings::VIEW_LIST
	? $arParams['PATH_TO_DEAL_LIST'] : $arParams['PATH_TO_DEAL_WIDGET'];

$navigationIndex = CUserOptions::GetOption('crm.navigation', 'index');
if(is_array($navigationIndex))
{
	foreach($navigationIndex as $k => $v)
	{
		$pageKey = 'PATH_TO_'.strtoupper("{$k}_{$v}");
		$arParams['PATH_TO_'.strtoupper($k).'_INDEX'] = $arParams['PATH_TO_'.strtoupper("{$k}_{$v}")];
	}
}
//<-- Preparing of URL templates

$arResult['ACTIVE_ITEM_ID'] = isset($arParams['ACTIVE_ITEM_ID']) ? $arParams['ACTIVE_ITEM_ID'] : '';
$arResult['ENABLE_SEARCH'] = isset($arParams['ENABLE_SEARCH']) && is_bool($arParams['ENABLE_SEARCH']) ? $arParams['ENABLE_SEARCH'] : true ;
$arResult['SEARCH_PAGE_URL'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_SEARCH_PAGE']);

$arResult['ID'] = isset($arParams['ID']) ? $arParams['ID'] : '';
if($arResult['ID'] === '')
{
	$arResult['ID'] = 'DEFAULT';
}

$isAdmin = CCrmPerms::IsAdmin();
$userPermissions = CCrmPerms::GetCurrentUserPermissions();

// Prepere standard items -->
$counter = new CCrmUserCounter(CCrmPerms::GetCurrentUserID(), CCrmUserCounter::CurrentActivies);
$stdItems = array(
	'STREAM' => array(
		'ID' => 'STREAM',
		'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_STREAM'),
		'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_STREAM_TITLE'),
		'URL' =>  CComponentEngine::MakePathFromTemplate(
			isset($arParams['PATH_TO_STREAM']) ? $arParams['PATH_TO_STREAM'] : '#SITE_DIR#crm/stream/'
		),
		//'COUNTER' => $counter->GetValue(),
		'ICON' => 'feed'
	),
	'MY_ACTIVITY' => array(
		'ID' => 'MY_ACTIVITY',
		'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_MY_ACTIVITY'),
		'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_MY_ACTIVITY_TITLE'),
		'URL' => CComponentEngine::MakePathFromTemplate(
			isset($arParams['PATH_TO_ACTIVITY_LIST']) ? $arParams['PATH_TO_ACTIVITY_LIST'] : '#SITE_DIR#crm/activity/'
		),
		'COUNTER' => $counter->GetValue(),
		'ICON' => 'activity'
	)
);

if($isAdmin || !$userPermissions->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'READ'))
{
	$counter = new CCrmUserCounter(CCrmPerms::GetCurrentUserID(), CCrmUserCounter::CurrentContactActivies);
	$stdItems['CONTACT'] = array(
		'ID' => 'CONTACT',
		'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_CONTACT'),
		'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_CONTACT_TITLE'),
		'URL' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_LIST']),
		'ICON' => 'contact',
		'COUNTER' => $counter->GetValue($arResult['ACTIVE_ITEM_ID'] === 'CONTACT'),
		'ACTIONS' => array(
			array(
				'ID' => 'CREATE',
				'URL' => CComponentEngine::MakePathFromTemplate(
					$arParams['PATH_TO_CONTACT_EDIT'],
					array('contact_id' => 0)
				)
			)
		)
	);
}

if($isAdmin || !$userPermissions->HavePerm('COMPANY', BX_CRM_PERM_NONE, 'READ'))
{
	$counter = new CCrmUserCounter(CCrmPerms::GetCurrentUserID(), CCrmUserCounter::CurrentCompanyActivies);
	$stdItems['COMPANY'] = array(
		'ID' => 'COMPANY',
		'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_COMPANY'),
		'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_COMPANY_TITLE'),
		'URL' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_LIST']),
		'ICON' => 'company',
		'COUNTER' => $counter->GetValue($arResult['ACTIVE_ITEM_ID'] === 'COMPANY'),
		'ACTIONS' => array(
			array(
				'ID' => 'CREATE',
				'URL' => CComponentEngine::MakePathFromTemplate(
					$arParams['PATH_TO_COMPANY_EDIT'],
					array('company_id' => 0)
				)
			)
		)
	);
}


if($isAdmin || !$userPermissions->HavePerm('DEAL', BX_CRM_PERM_NONE, 'READ'))
{
	$counter = new CCrmUserCounter(CCrmPerms::GetCurrentUserID(), CCrmUserCounter::CurrentDealActivies);
	$stdItems['DEAL'] = array(
		'ID' => 'DEAL',
		'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_DEAL'),
		'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_DEAL_TITLE'),
		'URL' => CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_DEAL_INDEX'] !== ''
				? $arParams['PATH_TO_DEAL_INDEX'] : $arParams['PATH_TO_DEAL_LIST']
		),
		'ICON' => 'deal',
		'COUNTER' => $counter->GetValue($arResult['ACTIVE_ITEM_ID'] === 'DEAL'),
		'ACTIONS' => array(
			array(
				'ID' => 'CREATE',
				'URL' =>  CComponentEngine::MakePathFromTemplate(
					$arParams['PATH_TO_DEAL_EDIT'],
					array('deal_id' => 0)
				)
			)
		)
	);
}

if($isAdmin || !$userPermissions->HavePerm('QUOTE', BX_CRM_PERM_NONE, 'READ'))
{
	$counter = new CCrmUserCounter(CCrmPerms::GetCurrentUserID(), CCrmUserCounter::CurrentQuoteActivies);
	$stdItems['QUOTE'] = array(
		'ID' => 'QUOTE',
		'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_QUOTE'),
		'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_QUOTE_TITLE'),
		'URL' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_QUOTE_LIST']),
		'ICON' => 'quote',
		'COUNTER' => $counter->GetValue($arResult['ACTIVE_ITEM_ID'] === 'QUOTE'),
		//'COUNTER' => $counterValue,
		'ACTIONS' => array(
			array(
				'ID' => 'CREATE',
				'URL' =>  CComponentEngine::MakePathFromTemplate(
						$arParams['PATH_TO_QUOTE_EDIT'],
						array('quote_id' => 0)
					)
			)
		)
	);
}

//if(IsModuleInstalled('sale'))
//{
	if($isAdmin || !$userPermissions->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'READ'))
	{
		$counterValue = CCrmInvoice::GetCounterValue();
		$stdItems['INVOICE'] = array(
			'ID' => 'INVOICE',
			'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_INVOICE'),
			'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_INVOICE_TITLE'),
			'URL' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_INVOICE_LIST']),
			'ICON' => 'invoice',
			//'COUNTER' => $counter->GetValue($arResult['ACTIVE_ITEM_ID'] === 'INVOICE'),
			'COUNTER' => $counterValue,
			'ACTIONS' => array(
				array(
					'ID' => 'CREATE',
					'URL' =>  CComponentEngine::MakePathFromTemplate(
						$arParams['PATH_TO_INVOICE_EDIT'],
						array('invoice_id' => 0)
					)
				)
			)
		);
	}
//}

if($isAdmin || !$userPermissions->HavePerm('LEAD', BX_CRM_PERM_NONE, 'READ'))
{
	$counter = new CCrmUserCounter(CCrmPerms::GetCurrentUserID(), CCrmUserCounter::CurrentLeadActivies);
	$stdItems['LEAD'] = array(
		'ID' => 'LEAD',
		'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_LEAD'),
		'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_LEAD_TITLE'),
		'URL' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_LIST']),
		'ICON' => 'lead',
		'COUNTER' => $counter->GetValue($arResult['ACTIVE_ITEM_ID'] === 'LEAD'),
		'ACTIONS' => array(
			array(
				'ID' => 'CREATE',
				'URL' => CComponentEngine::MakePathFromTemplate(
					$arParams['PATH_TO_LEAD_EDIT'],
					array('lead_id' => 0)
				)
			)
		)
	);
}

if(IsModuleInstalled('report'))
{
	$stdItems['REPORT'] = array(
		'ID' => 'REPORT',
		'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_REPORT'),
		'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_REPORT'),
		'URL' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_REPORT_LIST']),
		'ICON' => 'report'
	);
}
if($isAdmin || !$userPermissions->HavePerm('DEAL', BX_CRM_PERM_NONE, 'READ'))
{
	$stdItems['DEAL_FUNNEL'] = array(
		'ID' => 'DEAL_FUNNEL',
		'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_FUNNEL'),
		'BRIEF_NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_FUNNEL_BRIEF'),
		'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_FUNNEL'),
		'URL' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_FUNNEL']),
		'ICON' => 'funnel'
	);
}

$stdItems['EVENT'] = array(
	'ID' => 'EVENT',
	'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_EVENT_2'),
	'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_EVENT_2'), //title
	'URL' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_EVENT_LIST']),
	'ICON' => 'event'
);

if($isAdmin || !$userPermissions->HavePerm('CONFIG', BX_CRM_PERM_NONE, 'READ'))
{
	$stdItems['CATALOGUE'] = array(
		'ID' => 'CATALOGUE',
		'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_CATALOGUE_2'),
		'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_CATALOGUE_2'), //title
		'URL' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_PRODUCT_LIST']),
		'ICON' => 'catalog'
	);
}

$stdItems['SETTINGS'] = array(
	'ID' => 'SETTINGS',
	'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_SETTINGS'),
	'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_SETTINGS'), //title
	'URL' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_SETTINGS']),
	'ICON' => 'settings'
);
// <-- Prepere standard items

$items = array();
$itemInfos = isset($arParams['ITEMS']) && is_array($arParams['ITEMS']) ? $arParams['ITEMS'] : array();
if(empty($itemInfos))
{
	$items = array_values($stdItems);
}
else
{
	foreach($itemInfos as &$itemInfo)
	{
		$itemID = isset($itemInfo['ID']) ? strtoupper($itemInfo['ID']) : '';
		if(isset($stdItems[$itemID]))
		{
			$item = $stdItems[$itemID];
			$items[] = $item;
		}
		else
		{
			$items[] = array(
				'ID' => $itemID,
				'NAME' => isset($itemInfo['NAME']) ? $itemInfo['NAME'] : $itemID,
				'URL' => isset($itemInfo['URL']) ? $itemInfo['URL'] : '',
				'COUNTER' => isset($itemInfo['COUNTER']) ? intval($itemInfo['COUNTER']) : 0,
				'ICON' => isset($itemInfo['ICON']) ? $itemInfo['ICON'] : ''
			);
		}
	}
	unset($itemInfo);
}


$events = GetModuleEvents('crm', 'OnAfterCrmControlPanelBuild');
while($event = $events->Fetch())
{
	ExecuteModuleEventEx($event, array(&$items));
}

$arResult['ITEMS'] = &$items;
unset($items);

$arResult['ADDITIONAL_ITEM'] = array(
	'ID' => 'MORE',
	'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_MORE'),
	'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_MORE_TITLE'),
	'ICON' => 'more'
);

$options = CUserOptions::GetOption('crm.control.panel', strtolower($arResult['ID']));
if(!$options)
{
	$options = array('fixed' => 'N');
}
$arResult['IS_FIXED'] = isset($options['fixed']) && $options['fixed'] === 'Y';

$this->IncludeComponentTemplate();
