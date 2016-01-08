<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if (!CModule::IncludeModule('catalog'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED_CATALOG'));
	return;
}
if (!CModule::IncludeModule('sale'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED_SALE'));
	return;
}

global $USER_FIELD_MANAGER, $USER, $APPLICATION, $DB;

$APPLICATION->AddHeadScript('/bitrix/js/crm/instant_editor.js');
$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');
$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");
if(SITE_TEMPLATE_ID === 'bitrix24')
{
	$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/bitrix24/crm-entity-show.css");
}

$CCrmPerms = CCrmPerms::GetCurrentUserPermissions();
if ($CCrmPerms->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'READ'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$CCrmInvoice = new CCrmInvoice(false);

$arResult['CURRENT_USER_ID'] = CCrmSecurityHelper::GetCurrentUserID();
$arResult['INTERNAL_ADD_BTN_TITLE'] = empty($arParams['INTERNAL_ADD_BTN_TITLE']) ? GetMessage('CRM_INVOICE_INTERNAL_ADD_BTN_TITLE') : $arParams['INTERNAL_ADD_BTN_TITLE'];
$arParams['PATH_TO_INVOICE_LIST'] = CrmCheckPath('PATH_TO_INVOICE_LIST', $arParams['PATH_TO_INVOICE_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_INVOICE_SHOW'] = CrmCheckPath('PATH_TO_INVOICE_SHOW', $arParams['PATH_TO_INVOICE_SHOW'], $APPLICATION->GetCurPage().'?invoice_id=#invoice_id#&show');
$arParams['PATH_TO_INVOICE_PAYMENT'] = CrmCheckPath('PATH_TO_INVOICE_PAYMENT', $arParams['PATH_TO_INVOICE_PAYMENT'], $APPLICATION->GetCurPage().'?invoice_id=#invoice_id#&payment');
$arParams['PATH_TO_INVOICE_EDIT'] = CrmCheckPath('PATH_TO_INVOICE_EDIT', $arParams['PATH_TO_INVOICE_EDIT'], $APPLICATION->GetCurPage().'?invoice_id=#invoice_id#&edit');
$arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath('PATH_TO_USER_PROFILE', $arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

$arResult['IS_AJAX_CALL'] = isset($_REQUEST['bxajaxid']) || isset($_REQUEST['AJAX_CALL']);
$arResult['ENABLE_TOOLBAR'] = ($arParams['ENABLE_TOOLBAR'] === 'Y') ? 'Y' : 'N';

CUtil::InitJSCore(array('ajax', 'tooltip'));

$arResult['GADGET'] = 'N';
if (isset($arParams['GADGET_ID']) && strlen($arParams['GADGET_ID']) > 0)
{
	$arResult['GADGET'] = 'Y';
	$arResult['GADGET_ID'] = $arParams['GADGET_ID'];
}

$currentUserID = intval($arResult['CURRENT_USER_ID']);
$arFilter = $arSort = array();
$bInternal = false;
$arResult['FORM_ID'] = isset($arParams['FORM_ID']) ? $arParams['FORM_ID'] : '';
$arResult['TAB_ID'] = isset($arParams['TAB_ID']) ? $arParams['TAB_ID'] : '';
if (!empty($arParams['INTERNAL_FILTER']) || $arResult['GADGET'] == 'Y')
	$bInternal = true;
$arResult['INTERNAL'] = $bInternal;
if (!empty($arParams['INTERNAL_FILTER']) && is_array($arParams['INTERNAL_FILTER']))
{
	if(empty($arParams['GRID_ID_SUFFIX']))
	{
		$arParams['GRID_ID_SUFFIX'] = $this->GetParent() !== null ? strtoupper($this->GetParent()->GetName()) : '';
	}

	$arFilter = $arParams['INTERNAL_FILTER'];
}

if (!empty($arParams['INTERNAL_SORT']) && is_array($arParams['INTERNAL_SORT']))
	$arSort = $arParams['INTERNAL_SORT'];

$sExportType = '';
if (!empty($_REQUEST['type']))
{
	$sExportType = strtolower(trim($_REQUEST['type']));
	if (!in_array($sExportType, array('csv', 'excel')))
		$sExportType = '';
}
if (!empty($sExportType) && $CCrmPerms->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'EXPORT'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmInvoice::$sUFEntityID);

$arResult['GRID_ID'] = 'CRM_INVOICE_LIST_V12'.($bInternal && !empty($arParams['GRID_ID_SUFFIX']) ? '_'.$arParams['GRID_ID_SUFFIX'] : '');

$arResult['STATUS_LIST'] = CCrmStatus::GetStatusListEx('INVOICE_STATUS');
$arResult['CURRENCY_LIST'] = CCrmCurrencyHelper::PrepareListItems();

$arResult['PERSON_TYPES'] = CCrmPaySystem::getPersonTypesList();
$arPaySystems = array();
foreach (array_keys($arResult['PERSON_TYPES']) as $personTypeId)
	$arPaySystems[$personTypeId] = CCrmPaySystem::GetPaySystemsListItems($personTypeId);
$arResult['PAY_SYSTEMS_LIST'] = $arPaySystems;
unset($personTypeId, $arPaySystems);

$arResult['EVENT_LIST'] = CCrmStatus::GetStatusListEx('EVENT_TYPE');
$arResult['CLOSED_LIST'] = array('Y' => GetMessage('MAIN_YES'), 'N' => GetMessage('MAIN_NO'));
$arResult['FILTER'] = array();
$arResult['FILTER2LOGIC'] = array();
$arResult['FILTER_PRESETS'] = array();
$arResult['PERMS']['ADD']    = !$CCrmPerms->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'ADD');
$arResult['PERMS']['WRITE']  = !$CCrmPerms->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'WRITE');
$arResult['PERMS']['DELETE'] = !$CCrmPerms->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'DELETE');

$arResult['AJAX_MODE'] = isset($arParams['AJAX_MODE']) ? $arParams['AJAX_MODE'] : ($arResult['INTERNAL'] ? 'N' : 'Y');
$arResult['AJAX_ID'] = isset($arParams['AJAX_ID']) ? $arParams['AJAX_ID'] : '';
$arResult['AJAX_OPTION_JUMP'] = isset($arParams['AJAX_OPTION_JUMP']) ? $arParams['AJAX_OPTION_JUMP'] : 'N';
$arResult['AJAX_OPTION_HISTORY'] = isset($arParams['AJAX_OPTION_HISTORY']) ? $arParams['AJAX_OPTION_HISTORY'] : 'N';

if (!$bInternal)
{
	ob_start();
	$GLOBALS['APPLICATION']->IncludeComponent('bitrix:crm.entity.selector',
		'',
		array(
			'ENTITY_TYPE' => Array('DEAL', 'QUOTE', 'COMPANY', 'CONTACT'),
			'INPUT_NAME' => 'ENTITIES_LINKS',
			'INPUT_VALUE' =>  isset($_REQUEST["ENTITIES_LINKS"]) ? $_REQUEST["ENTITIES_LINKS"] : '',
			'FORM_NAME' => $arResult['GRID_ID'],
			'MULTIPLE' => 'N',
			'FILTER' => true
		),
		false,
		array('HIDE_ICONS' => 'Y')
	);
	$entitiesLinksFilterHtml = ob_get_contents();
	ob_end_clean();

	$arResult['FILTER'] = array(
		array('id' => 'ID', 'name' => GetMessage('CRM_COLUMN_ID')),
		array('id' => 'ACCOUNT_NUMBER', 'name' => GetMessage('CRM_COLUMN_ACCOUNT_NUMBER')),
		array('id' => 'ORDER_TOPIC', 'name' => GetMessage('CRM_COLUMN_ORDER_TOPIC'), 'default' => true, 'type' => 'text'),
		array(
			'id' => 'STATUS_ID', 'name' => GetMessage('CRM_COLUMN_STATUS_ID'),
			'params' => array('multiple' => 'Y'), 'default' => true, 'type' => 'list',
			'items' => CCrmStatus::GetStatusList('INVOICE_STATUS')
		),
		array('id' => 'PRICE', 'name' => GetMessage('CRM_COLUMN_PRICE'), 'type' => 'number'),
		array('id' => 'DATE_PAY_BEFORE', 'name' => GetMessage('CRM_COLUMN_DATE_PAY_BEFORE'), 'type' => 'date'),
		array('id' => 'DATE_INSERT', 'name' => GetMessage('CRM_COLUMN_DATE_INSERT'), 'type' => 'date'),
		array('id' => 'RESPONSIBLE_ID',  'name' => GetMessage('CRM_COLUMN_RESPONSIBLE'), 'default' => true, 'enable_settings' => true, 'type' => 'user'),

		// entities
		array('id' => 'ENTITIES_LINKS', 'name' => GetMessage('CRM_COLUMN_ENTITIES_LINKS'), 'default' => true, 'type' => 'custom', 'value' => $entitiesLinksFilterHtml),
	);

	$CCrmUserType->ListAddFilterFields($arResult['FILTER'], $arResult['FILTER2LOGIC'], $arResult['GRID_ID']);

	$currentUserName = CCrmViewHelper::GetFormattedUserName($currentUserID, $arParams['NAME_TEMPLATE']);
	$filterValuesNeutral = $filterValuesSuccess = $filterValuesFailed = array();
	foreach (CCrmStatusInvoice::getStatusIds('neutral') as $val)
		$filterValuesNeutral['sel'.$val] = $val;
	foreach (CCrmStatusInvoice::getStatusIds('success') as $val)
		$filterValuesSuccess['sel'.$val] = $val;
	$arResult['FILTER_PRESETS'] = array(
		'filter_neutral' => array('name' => GetMessage('CRM_PRESET_NEUTRAL'), 'fields' => array('STATUS_ID' => $filterValuesNeutral, 'RESPONSIBLE_ID_name' => $currentUserName, 'RESPONSIBLE_ID' => $currentUserID/*, $arResult['GRID_ID'].'_RESPONSIBLE_ID_SEARCH' => $currentUserName*/)/*, 'filter_rows' => array('STATUS_ID', 'RESPONSIBLE_ID')*/),
		'filter_success' => array('name' => GetMessage('CRM_PRESET_SUCCESS'), 'fields' => array('STATUS_ID' => $filterValuesSuccess, 'RESPONSIBLE_ID_name' => $currentUserName, 'RESPONSIBLE_ID' => $currentUserID/*, $arResult['GRID_ID'].'_RESPONSIBLE_ID_SEARCH' => $currentUserName*/)/*, 'filter_rows' => array('STATUS_ID', 'RESPONSIBLE_ID')*/)
	);
}

$arResult['HEADERS'] = array(
	array('id' => 'ACCOUNT_NUMBER', 'name' => GetMessage('CRM_COLUMN_ACCOUNT_NUMBER'), 'sort' => 'account_number', 'default' => true, 'editable' => false),
	array('id' => 'ORDER_TOPIC', 'name' => GetMessage('CRM_COLUMN_ORDER_TOPIC'), 'sort' => 'order_topic', 'default' => true, 'editable' => true),
	array('id' => 'STATUS_ID', 'name' => GetMessage('CRM_COLUMN_STATUS_ID'), 'sort' => 'status_id', 'default' => true, 'editable' => false/*=> array('items' => CCrmStatus::GetStatusList('INVOICE_STATUS')), 'type' => 'list'*/),
	array('id' => 'PRICE', 'name' => GetMessage('CRM_COLUMN_PRICE'), 'sort' => 'price', 'default' => true, 'editable' => false, 'align' => 'right', 'type' => 'number'),
	array('id' => 'ENTITIES_LINKS', 'name' => GetMessage('CRM_COLUMN_ENTITIES_LINKS'), 'default' => true, 'editable' => false),
	array('id' => 'DATE_PAY_BEFORE', 'name' => GetMessage('CRM_COLUMN_DATE_PAY_BEFORE'), 'sort' => 'date_pay_before', 'default' => true, 'editable' => false),
	array('id' => 'DATE_INSERT', 'name' => GetMessage('CRM_COLUMN_DATE_INSERT'), 'sort' => 'date_insert', 'default' => true, 'editable' => false),
	array('id' => 'RESPONSIBLE_ID', 'name' => GetMessage('CRM_COLUMN_RESPONSIBLE'), 'sort' => 'responsible', 'default' => true, 'editable' => false),

	// advanced fields
	array('id' => 'ID', 'name' => GetMessage('CRM_COLUMN_ID'), 'sort' => 'id', 'default' => false, 'editable' => false, 'type' => 'int', 'align' => 'right'),
	array('id' => 'COMMENTS', 'name' => GetMessage('CRM_COLUMN_COMMENTS'), 'sort' => 'comments', 'default' => false, 'editable' => false),
	array('id' => 'CURRENCY', 'name' => GetMessage('CRM_COLUMN_CURRENCY'), 'sort' => 'currency', 'default' => false, 'editable' => false),
	array('id' => 'DATE_BILL', 'name' => GetMessage('CRM_COLUMN_DATE_BILL'), 'sort' => 'date_bill', 'default' => false, 'editable' => false),
	array('id' => 'DATE_MARKED', 'name' => GetMessage('CRM_COLUMN_DATE_MARKED'), 'sort' => 'date_marked', 'default' => false, 'editable' => false),
	array('id' => 'DATE_STATUS', 'name' => GetMessage('CRM_COLUMN_DATE_STATUS'), 'sort' => 'date_status', 'default' => false, 'editable' => false),
	array('id' => 'DATE_UPDATE', 'name' => GetMessage('CRM_COLUMN_DATE_UPDATE'), 'sort' => 'date_update', 'default' => false, 'editable' => false),
	array('id' => 'PAY_SYSTEM_ID', 'name' => GetMessage('CRM_COLUMN_PAY_SYSTEM_ID'), 'sort' => 'pay_system_id', 'default' => false, 'editable' => false),
	array('id' => 'PAY_VOUCHER_DATE', 'name' => GetMessage('CRM_COLUMN_PAY_VOUCHER_DATE'), 'sort' => 'pay_voucher_date', 'default' => false, 'editable' => false),
	array('id' => 'PAY_VOUCHER_NUM', 'name' => GetMessage('CRM_COLUMN_PAY_VOUCHER_NUM'), 'sort' => 'pay_voucher_num', 'default' => false, 'editable' => false),
	array('id' => 'PERSON_TYPE_ID', 'name' => GetMessage('CRM_COLUMN_PERSON_TYPE_ID'), 'sort' => 'person_type_id', 'default' => false, 'editable' => false),
	array('id' => 'REASON_MARKED', 'name' => GetMessage('CRM_COLUMN_REASON_MARKED'), 'sort' => 'reason_marked', 'default' => false, 'editable' => false),
	array('id' => 'TAX_VALUE', 'name' => GetMessage('CRM_COLUMN_TAX_VALUE'), 'sort' => 'tax_value', 'default' => false, 'editable' => false, 'align' => 'right', 'type' => 'number'),
	array('id' => 'USER_DESCRIPTION', 'name' => GetMessage('CRM_COLUMN_USER_DESCRIPTION'), 'sort' => 'user_description', 'default' => false, 'editable' => false)
);
$CCrmUserType->ListAddHeaders($arResult['HEADERS']);

// Try to extract user action data
// We have to extract them before call of CGridOptions::GetFilter() or the custom filter will be corrupted.
// <editor-fold defaultstate="collapsed" desc="Try to extract user action data ...">
$actionData = array(
	'METHOD' => $_SERVER['REQUEST_METHOD'],
	'ACTIVE' => false
);
if(check_bitrix_sessid())
{
	$postAction = 'action_button_'.$arResult['GRID_ID'];
	$getAction = 'action_'.$arResult['GRID_ID'];
	if ($actionData['METHOD'] == 'POST' && isset($_POST[$postAction]))
	{
		$actionData['ACTIVE'] = true;

		$actionData['NAME'] = $_POST[$postAction];
		unset($_POST[$postAction], $_REQUEST[$postAction]);

		$allRows = 'action_all_rows_'.$arResult['GRID_ID'];
		$actionData['ALL_ROWS'] = false;
		if(isset($_POST[$allRows]))
		{
			$actionData['ALL_ROWS'] = $_POST[$allRows] == 'Y';
			unset($_POST[$allRows], $_REQUEST[$allRows]);
		}

		if(isset($_POST['ID']))
		{
			$actionData['ID'] = $_POST['ID'];
			unset($_POST['ID'], $_REQUEST['ID']);
		}

		if(isset($_POST['FIELDS']))
		{
			$actionData['FIELDS'] = $_POST['FIELDS'];
			unset($_POST['FIELDS'], $_REQUEST['FIELDS']);
		}

		if(isset($_POST['ACTION_STAGE_ID']))
		{
			$actionData['STAGE_ID'] = trim($_POST['ACTION_STAGE_ID']);
			unset($_POST['ACTION_STAGE_ID'], $_REQUEST['ACTION_STAGE_ID']);
		}

		if(isset($_POST['ACTION_ASSIGNED_BY_ID']))
		{
			$assignedByID = 0;
			if(!is_array($_POST['ACTION_ASSIGNED_BY_ID']))
			{
				$assignedByID = intval($_POST['ACTION_ASSIGNED_BY_ID']);
			}
			elseif(count($_POST['ACTION_ASSIGNED_BY_ID']) > 0)
			{
				$assignedByID = intval($_POST['ACTION_ASSIGNED_BY_ID'][0]);
			}

			$actionData['ASSIGNED_BY_ID'] = $assignedByID;
			unset($_POST['ACTION_ASSIGNED_BY_ID'], $_REQUEST['ACTION_ASSIGNED_BY_ID']);
		}

		$actionData['AJAX_CALL'] = false;
		if(isset($_POST['AJAX_CALL']))
		{
			$actionData['AJAX_CALL']  = true;
			// Must be transfered to main.interface.grid
			//unset($_POST['AJAX_CALL'], $_REQUEST['AJAX_CALL']);
		}
	}
	elseif ($actionData['METHOD'] == 'GET' && isset($_GET[$getAction]))
	{
		$actionData['ACTIVE'] = true;

		$actionData['NAME'] = $_GET[$getAction];
		unset($_GET[$getAction], $_REQUEST[$getAction]);

		if(isset($_GET['ID']))
		{
			$actionData['ID'] = $_GET['ID'];
			unset($_GET['ID'], $_REQUEST['ID']);
		}

		$actionData['AJAX_CALL'] = false;
		if(isset($_GET['AJAX_CALL']))
		{
			$actionData['AJAX_CALL']  = true;
			// Must be transfered to main.interface.grid
			//unset($_GET['AJAX_CALL'], $_REQUEST['AJAX_CALL']);
		}
	}
}
// </editor-fold>

if (intval($arParams['INVOICE_COUNT']) <= 0)
	$arParams['INVOICE_COUNT'] = 20;

// HACK: for clear filter by RESPONSIBLE_ID
if($_SERVER['REQUEST_METHOD'] === 'GET')
{
	if(isset($_REQUEST['RESPONSIBLE_ID_name']) && $_REQUEST['RESPONSIBLE_ID_name'] === '')
	{
		$_REQUEST['RESPONSIBLE_ID'] = $_GET['RESPONSIBLE_ID'] = array();
	}
}

$arNavParams = array(
	'nPageSize' => $arParams['INVOICE_COUNT']
);

$arNavigation = CDBResult::GetNavParams($arNavParams);
$CGridOptions = new CCrmGridOptions($arResult['GRID_ID'], $arResult['FILTER_PRESETS']);
$arNavParams = $CGridOptions->GetNavParams($arNavParams);
$arNavParams['bShowAll'] = false;

$arFilter += $CGridOptions->GetFilter($arResult['FILTER']);

$USER_FIELD_MANAGER->AdminListAddFilter(CCrmInvoice::$sUFEntityID, $arFilter);

foreach ($arFilter as $k => $v)
{
	$arMatch = array();

	if (preg_match('/(.*)_from$/i'.BX_UTF_PCRE_MODIFIER, $k, $arMatch))
	{
		if(strlen($v) > 0)
		{
			$arFilter['>='.$arMatch[1]] = $v;
		}
		unset($arFilter[$k]);
	}
	elseif (preg_match('/(.*)_to$/i'.BX_UTF_PCRE_MODIFIER, $k, $arMatch))
	{
		if(strlen($v) > 0)
		{
			if (($arMatch[1] == 'DATE_PAY_BEFORE' || $arMatch[1] == 'DATE_INSERT') && !preg_match('/\d{1,2}:\d{1,2}(:\d{1,2})?$/'.BX_UTF_PCRE_MODIFIER, $v))
			{
				$v .=  ' 23:59:59';
			}
			$arFilter['<='.$arMatch[1]] = $v;
		}
		unset($arFilter[$k]);
	}
	elseif ($k === 'ORDER_TOPIC')
	{
		$arFilter['~ORDER_TOPIC'] = "%$v%";
		unset($arFilter['ORDER_TOPIC']);
	}
	elseif ($k === 'ACCOUNT_NUMBER')
	{
		$arFilter['~ACCOUNT_NUMBER'] = "%$v%";
		unset($arFilter['ACCOUNT_NUMBER']);
	}
	elseif ($k === 'ENTITIES_LINKS')
	{
		$ownerData =explode('_', $v);
		if(count($ownerData) > 1)
		{
			$ownerTypeName = CCrmOwnerType::ResolveName(CCrmOwnerType::ResolveID($ownerData[0]));
			$ownerID = intval($ownerData[1]);
			if(!empty($ownerTypeName) && $ownerID > 0)
			{
				$arFilter['UF_'.$ownerTypeName.'_ID'] = $ownerID;
			}
		}
		unset($arFilter[$k]);
	}
	elseif (in_array($k, $arResult['FILTER2LOGIC']))
	{
		// Bugfix #26956 - skip empty values in logical filter
		$v = trim($v);
		if($v !== '')
		{
			$arFilter['%'.$k] = $v;
		}
		unset($arFilter[$k]);
	}
}

// POST & GET actions processing -->
if($actionData['ACTIVE'])
{
	if ($actionData['METHOD'] == 'POST')
	{
		if($actionData['NAME'] == 'delete')
		{
			if ((isset($actionData['ID']) && is_array($actionData['ID'])) || $actionData['ALL_ROWS'])
			{
				$arFilterDel = array();
				if (!$actionData['ALL_ROWS'])
				{
					$arFilterDel = array('ID' => $actionData['ID']);
				}
				else
				{
					// Fix for issue #26628
					$arFilterDel += $arFilter;
				}

				$obRes = CCrmInvoice::GetList(array(), $arFilterDel, false, false, array('ID'));
				while($arInvoice = $obRes->Fetch())
				{
					$ID = $arInvoice['ID'];
					$arEntityAttr = $CCrmPerms->GetEntityAttr('INVOICE', array($ID));
					if (!$CCrmPerms->CheckEnityAccess('INVOICE', 'DELETE', $arEntityAttr[$ID]))
					{
						continue ;
					}

					$DB->StartTransaction();

					if ($CCrmInvoice->Delete($ID))
					{
						$DB->Commit();
					}
					else
					{
						$DB->Rollback();
					}
				}
			}
		}
		elseif($actionData['NAME'] == 'edit')
		{
			if(isset($actionData['FIELDS']) && is_array($actionData['FIELDS']))
			{
				foreach($actionData['FIELDS'] as $ID => $arSrcData)
				{
					$arEntityAttr = $CCrmPerms->GetEntityAttr('INVOICE', array($ID));
					if (!$CCrmPerms->CheckEnityAccess('INVOICE', 'WRITE', $arEntityAttr[$ID]))
					{
						continue ;
					}

					$arUpdateData = array();
					reset($arResult['HEADERS']);
					foreach ($arResult['HEADERS'] as $arHead)
					{
						if (isset($arHead['editable']) && $arHead['editable'] == true && isset($arSrcData[$arHead['id']]))
						{
							$arUpdateData[$arHead['id']] = $arSrcData[$arHead['id']];
						}
					}
					if (!empty($arUpdateData))
					{
						if ($CCrmInvoice->CheckFieldsUpdate($arUpdateData))
						{
							$DB->StartTransaction();

							if($CCrmInvoice->Update($ID, $arUpdateData, array('REGISTER_SONET_EVENT' => true, 'UPDATE_SEARCH' => true)))
								$DB->Commit();
							else
								$DB->Rollback();
						}
					}
				}
			}
		}

		if (!$actionData['AJAX_CALL'])
		{
			LocalRedirect($arParams['PATH_TO_INVOICE_LIST']);
		}
	}
	else//if ($actionData['METHOD'] == 'GET')
	{
		if ($actionData['NAME'] == 'delete' && isset($actionData['ID']))
		{
			$ID = intval($actionData['ID']);

			$arEntityAttr = $CCrmPerms->GetEntityAttr('INVOICE', array($ID));
			$attr = $arEntityAttr[$ID];

			if($CCrmPerms->CheckEnityAccess('INVOICE', 'DELETE', $attr))
			{
				$DB->StartTransaction();

				if($CCrmInvoice->Delete($ID))
				{
					$DB->Commit();
				}
				else
				{
					$DB->Rollback();
				}
			}
		}

		if (!$actionData['AJAX_CALL'])
		{
			LocalRedirect($bInternal ? '?'.$arParams['FORM_ID'].'_active_tab=tab_invoice' : $arParams['PATH_TO_INVOICE_LIST']);
		}
	}
}
// <-- POST & GET actions processing

if (!$bInternal && isset($_REQUEST['clear_filter']) && $_REQUEST['clear_filter'] == 'Y')
{
	$urlParams = array();
	foreach($arResult['FILTER'] as $id => $arFilter)
	{
		if ($arFilter['type'] == 'user')
		{
			$urlParams[] = $arFilter['id'];
			$urlParams[] = $arFilter['id'].'_name';
		}
		else
		{
			$urlParams[] = $arFilter['id'];
		}
	}
	$urlParams[] = 'clear_filter';
	$CGridOptions->GetFilter(array());
	LocalRedirect($APPLICATION->GetCurPageParam("", $urlParams));
}

$_arSort = $CGridOptions->GetSorting(array(
	'sort' => array('date_pay_before' => 'asc'),
	'vars' => array('by' => 'by', 'order' => 'order')
));
$arResult['SORT'] = !empty($arSort) ? $arSort : $_arSort['sort'];
$arResult['SORT_VARS'] = $_arSort['vars'];

// Remove column for deleted UF
$arSelect = $CGridOptions->GetVisibleColumns();

if ($CCrmUserType->NormalizeFields($arSelect))
	$CGridOptions->SetVisibleColumns($arSelect);

// Fill in default values if empty
if (empty($arSelect))
{
	foreach ($arResult['HEADERS'] as $arHeader)
	{
		if ($arHeader['default'])
		{
			$arSelect[] = $arHeader['id'];
		}
	}
}

$arSelectedHeaders = $arSelect;

// For preparing user html
if (!in_array('RESPONSIBLE_LOGIN', $arSelect))
	$arSelect[] = 'RESPONSIBLE_LOGIN';

if (!in_array('RESPONSIBLE_NAME', $arSelect))
	$arSelect[] = 'RESPONSIBLE_NAME';

if (!in_array('RESPONSIBLE_LAST_NAME', $arSelect))
	$arSelect[] = 'RESPONSIBLE_LAST_NAME';

if (!in_array('RESPONSIBLE_SECOND_NAME', $arSelect))
	$arSelect[] = 'RESPONSIBLE_SECOND_NAME';

// PAY_SYSTEM_ID require PERSON_TYPE_ID
if (in_array('PAY_SYSTEM_ID', $arSelect))
	$arSelect[] = 'PERSON_TYPE_ID';

// ID must present in select
if(!in_array('ID', $arSelect))
{
	$arSelect[] = 'ID';
}

if ($sExportType != '')
{
	$arResult['SELECTED_HEADERS'] = $arSelectedHeaders;
	$arFilter['PERMISSION'] = 'EXPORT';
}

// HACK: Make custom sort for RESPONSIBLE field
$arSort = $arResult['SORT'];
if(isset($arSort['responsible']))
{
	$arSort['responsible_last_name'] = $arSort['responsible'];
	$arSort['responsible_name'] = $arSort['responsible'];
	$arSort['responsible_login'] = $arSort['responsible'];
	unset($arSort['responsible']);
}

$arSelect[] = 'CURRENCY';

$arSelect[] = 'UF_DEAL_ID';
$arSelect[] = 'UF_COMPANY_ID';
$arSelect[] = 'UF_CONTACT_ID';
$arSelect[] = 'UF_QUOTE_ID';

// fields for status change dialog
$arSelect[] = 'PAY_VOUCHER_DATE';
$arSelect[] = 'PAY_VOUCHER_NUM';
$arSelect[] = 'DATE_MARKED';
$arSelect[] = 'REASON_MARKED';

$arOptions = array();
if (isset($arSort['date_pay_before']))
	$arOptions['NULLS_LAST'] = true;

$arSelect = array_unique($arSelect, SORT_STRING);
$obRes = CCrmInvoice::GetList($arSort, $arFilter, false, ($sExportType == '' ? $arNavParams : false), $arSelect, $arOptions);
if ($arResult['GADGET'] != 'Y' && $sExportType == '')
{
	$obRes->NavStart($arNavParams['nPageSize'], false);
}

$arResult['INVOICE'] = array();
$arResult['INVOICE_ID'] = array();
$arResult['INVOICE_UF'] = array();
$now = time() + CTimeZone::GetOffset();
$currencyID = $CCrmInvoice::GetCurrencyID();

$totalPaidCurrencyId = ($arParams['SUM_PAID_CURRENCY'] != '') ? $arParams['SUM_PAID_CURRENCY'] : CCrmCurrency::getInvoiceDefault();
$totalPaidNumber = 0;
$totalPaidSum = 0;
$arContactList = array();
$arCompanyList = array();
$arDealList = array();
$arQuoteList = array();

while($arInvoice = $obRes->GetNext())
{
	$entityID = $arInvoice['ID'];

	// urls for row actions
	$arInvoice['PATH_TO_INVOICE_SHOW'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_INVOICE_SHOW'],
		array(
			'invoice_id' => $entityID
		)
	);
	$arInvoice['PATH_TO_INVOICE_PAYMENT'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_INVOICE_PAYMENT'],
		array(
			'invoice_id' => $entityID
		)
	);
	$arInvoice['PATH_TO_INVOICE_EDIT'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_INVOICE_EDIT'],
		array(
			'invoice_id' => $entityID
		)
	);
	$arInvoice['PATH_TO_USER_PROFILE'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_PROFILE'],
		array(
			'user_id' => $arInvoice['RESPONSIBLE_ID']
		)
	);
	$arInvoice['PATH_TO_INVOICE_COPY'] =  CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_INVOICE_EDIT'],
		array(
			'invoice_id' => $entityID
		)),
		array('copy' => 1)
	);
	$arInvoice['PATH_TO_INVOICE_DELETE'] =  CHTTP::urlAddParams(
		$bInternal ? $APPLICATION->GetCurPage() : $arParams['PATH_TO_INVOICE_LIST'],
		array('action_'.$arResult['GRID_ID'] => 'delete', 'ID' => $entityID, 'sessid' => bitrix_sessid())
	);

	if (empty($arInvoice['~CURRENCY']))
	{
		$arInvoice['~CURRENCY'] = $currencyID;
		$arInvoice['CURRENCY'] = htmlspecialcharsbx($currencyID);
	}

	$arInvoice['FORMATTED_PRICE'] = "<nobr>".CCrmCurrency::MoneyToString($arInvoice['~PRICE'], $arInvoice['~CURRENCY']).'</nobr>';
	$arInvoice['FORMATTED_TAX_VALUE'] = "<nobr>".CCrmCurrency::MoneyToString($arInvoice['~TAX_VALUE'], $arInvoice['~CURRENCY']).'</nobr>';


	$isStatusNeutral = false;
	$isStatusSuccess = CCrmStatusInvoice::isStatusSuccess($arInvoice['~STATUS_ID']);
	if (!$isStatusSuccess)
		$isStatusNeutral = CCrmStatusInvoice::isStatusNeutral($arInvoice['~STATUS_ID']);

	// calculate paid sum
	if ($isStatusSuccess)
	{
		$totalPaidNumber++;
		$totalPaidSum += CCrmCurrency::ConvertMoney($arInvoice['~PRICE'], $arInvoice['~CURRENCY'], $totalPaidCurrencyId);
	}

	// color coding
	$arInvoice['INVOICE_EXPIRED_FLAG'] = false;
	$arInvoice['INVOICE_IN_COUNTER_FLAG'] = false;
	if ($isStatusNeutral && !empty($arInvoice['DATE_PAY_BEFORE']))
	{
		$tsDatePayBefore = MakeTimeStamp($arInvoice['DATE_PAY_BEFORE']);
		$tsNow = time() + CTimeZone::GetOffset();
		$tsMax = mktime(00, 00, 00, date('m',$tsNow), date('d',$tsNow), date('Y',$tsNow));

		if ($tsDatePayBefore < $tsMax)
			$arInvoice['INVOICE_EXPIRED_FLAG'] = true;

		if ($currentUserID > 0 && $currentUserID === intval($arInvoice['RESPONSIBLE_ID']))
		{
			if ($tsDatePayBefore <= $tsMax)
				$arInvoice['INVOICE_IN_COUNTER_FLAG'] = true;
		}
		unset($tsDatePayBefore, $tsNow, $tsMax);
	}

	$arInvoice['RESPONSIBLE'] = intval($arInvoice['RESPONSIBLE_ID']) > 0
		? CUser::FormatName(
			$arParams['NAME_TEMPLATE'],
			array(
				'LOGIN' => $arInvoice['RESPONSIBLE_LOGIN'],
				'NAME' => $arInvoice['RESPONSIBLE_NAME'],
				'LAST_NAME' => $arInvoice['RESPONSIBLE_LAST_NAME'],
				'SECOND_NAME' => $arInvoice['RESPONSIBLE_SECOND_NAME']
			),
			true, false
		) : GetMessage('CRM_RESPONSIBLE_NOT_ASSIGNED');

	$arResult['INVOICE'][$entityID] = $arInvoice;
	$arResult['INVOICE_UF'][$entityID] = array();
	$arResult['INVOICE_ID'][$entityID] = $entityID;

	// index
	if (isset($arInvoice['UF_CONTACT_ID']) && intval($arInvoice['UF_CONTACT_ID']) > 0)
	{
		if (!isset($arContactList[$arInvoice['UF_CONTACT_ID']]) || !is_array($arContactList[$arInvoice['UF_CONTACT_ID']]))
			$arContactList[$arInvoice['UF_CONTACT_ID']] = array();
		$arContactList[$arInvoice['UF_CONTACT_ID']][] = $entityID;
	}
	if (isset($arInvoice['UF_COMPANY_ID']) && intval($arInvoice['UF_COMPANY_ID']) > 0)
	{
		if (!isset($arCompanyList[$arInvoice['UF_COMPANY_ID']]) || !is_array($arCompanyList[$arInvoice['UF_COMPANY_ID']]))
			$arCompanyList[$arInvoice['UF_COMPANY_ID']] = array();
		$arCompanyList[$arInvoice['UF_COMPANY_ID']][] = $entityID;
	}
	if (isset($arInvoice['UF_DEAL_ID']) && intval($arInvoice['UF_DEAL_ID']) > 0)
	{
		if (!isset($arDealList[$arInvoice['UF_DEAL_ID']]) || !is_array($arDealList[$arInvoice['UF_DEAL_ID']]))
			$arDealList[$arInvoice['UF_DEAL_ID']] = array();
		$arDealList[$arInvoice['UF_DEAL_ID']][] = $entityID;
	}
	if (isset($arInvoice['UF_QUOTE_ID']) && intval($arInvoice['UF_QUOTE_ID']) > 0)
	{
		if (!isset($arQuoteList[$arInvoice['UF_QUOTE_ID']]) || !is_array($arQuoteList[$arInvoice['UF_QUOTE_ID']]))
			$arQuoteList[$arInvoice['UF_QUOTE_ID']] = array();
		$arQuoteList[$arInvoice['UF_QUOTE_ID']][] = $entityID;
	}
}
if (count($arContactList) > 0)
{
	$dbRes = CCrmContact::GetList(array(), array('ID' => array_keys($arContactList)), array('ID', 'LOGIN', 'NAME', 'LAST_NAME', 'SECOND_NAME'));
	if ($dbRes)
	{
		$arContact = array();
		$contactFormattedName = '';
		while ($arContact = $dbRes->Fetch())
		{
			if (isset($arContactList[$arContact['ID']])
				&& is_array($arContactList[$arContact['ID']])
				&& count($arContactList[$arContact['ID']]) > 0)
			{
				foreach ($arContactList[$arContact['ID']] as $invoiceId)
				{
					$arResult['INVOICE'][$invoiceId]['CONTACT_FORMATTED_NAME'] = $contactFormattedName = CUser::FormatName(
						\Bitrix\Crm\Format\PersonNameFormatter::getFormat(),
						array(
							'LOGIN' => $arContact['LOGIN'],
							'NAME' => $arContact['NAME'],
							'LAST_NAME' => $arContact['LAST_NAME'],
							'SECOND_NAME' => $arContact['SECOND_NAME']
						)
					);
					$arResult['INVOICE'][$invoiceId]['CONTACT_LINK_HTML'] = CCrmViewHelper::PrepareEntityBaloonHtml(
						array(
							'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
							'ENTITY_ID' => $arContact['ID'],
							'PREFIX' => uniqid("crm_contact_link_"),
							'TITLE' => $contactFormattedName,
							'CLASS_NAME' => ''
						)
					);
				}
				unset($invoiceId);
			}
		}
		unset($arContact, $contactFormattedName);
	}
	unset($dbRes);
}
if (count($arCompanyList) > 0)
{
	$dbRes = CCrmCompany::GetList(array(), array('ID' => array_keys($arCompanyList)), array('TITLE'));
	if ($dbRes)
	{
		$arCompany = array();
		while ($arCompany = $dbRes->Fetch())
		{
			if (isset($arCompanyList[$arCompany['ID']])
				&& is_array($arCompanyList[$arCompany['ID']])
				&& count($arCompanyList[$arCompany['ID']]) > 0)
			{
				foreach ($arCompanyList[$arCompany['ID']] as $invoiceId)
				{
					$arResult['INVOICE'][$invoiceId]['COMPANY_TITLE'] = $arCompany['TITLE'];
					$arResult['INVOICE'][$invoiceId]['COMPANY_LINK_HTML'] = CCrmViewHelper::PrepareEntityBaloonHtml(
						array(
							'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
							'ENTITY_ID' => $arCompany['ID'],
							'PREFIX' => uniqid("crm_company_link_"),
							'TITLE' => $arCompany['TITLE'],
							'CLASS_NAME' => ''
						)
					);
				}
				unset($invoiceId);
			}
		}
		unset($arCompany);
	}
	unset($dbRes);
}
if (count($arDealList) > 0)
{
	$dbRes = CCrmDeal::GetList(array(), array('ID' => array_keys($arDealList)), array('TITLE'));
	if ($dbRes)
	{
		$arDeal = array();
		while ($arDeal = $dbRes->Fetch())
		{
			if (isset($arDealList[$arDeal['ID']])
				&& is_array($arDealList[$arDeal['ID']])
				&& count($arDealList[$arDeal['ID']]) > 0)
			{
				foreach ($arDealList[$arDeal['ID']] as $invoiceId)
				{
					$arResult['INVOICE'][$invoiceId]['DEAL_TITLE'] = $arDeal['TITLE'];
					$arResult['INVOICE'][$invoiceId]['DEAL_LINK_HTML'] = CCrmViewHelper::PrepareEntityBaloonHtml(
						array(
							'ENTITY_TYPE_ID' => CCrmOwnerType::Deal,
							'ENTITY_ID' => $arDeal['ID'],
							'PREFIX' => uniqid("crm_deal_link_"),
							'TITLE' => $arDeal['TITLE'],
							'CLASS_NAME' => ''
						)
					);
				}
				unset($invoiceId);
			}
		}
		unset($arDeal);
	}
	unset($dbRes);
}
if (count($arQuoteList) > 0)
{
	$quoteTitle = '';
	$dbRes = CCrmQuote::GetList(array(), array('ID' => array_keys($arQuoteList)), false, false, array('QUOTE_NUMBER', 'TITLE'));
	if ($dbRes)
	{
		$arQuote = array();
		while ($arQuote = $dbRes->Fetch())
		{
			if (isset($arQuoteList[$arQuote['ID']])
				&& is_array($arQuoteList[$arQuote['ID']])
				&& count($arQuoteList[$arQuote['ID']]) > 0)
			{
				foreach ($arQuoteList[$arQuote['ID']] as $invoiceId)
				{
					$quoteTitle = empty($arRes['TITLE']) ? $arRes['QUOTE_NUMBER'] : $arRes['QUOTE_NUMBER'].' - '.$arRes['TITLE'];
					$quoteTitle = empty($quoteTitle) ? '' : str_replace(array(';', ','), ' ', $quoteTitle);
					$arResult['INVOICE'][$invoiceId]['QUOTE_TITLE'] = $quoteTitle;
					$arResult['INVOICE'][$invoiceId]['QUOTE_LINK_HTML'] = CCrmViewHelper::PrepareEntityBaloonHtml(
						array(
							'ENTITY_TYPE_ID' => CCrmOwnerType::Quote,
							'ENTITY_ID' => $arQuote['ID'],
							'PREFIX' => uniqid("crm_quote_link_"),
							'TITLE' => $quoteTitle,
							'CLASS_NAME' => ''
						)
					);
				}
				unset($invoiceId);
			}
		}
		unset($arQuote);
	}
	unset($quoteTitle, $dbRes);
}
unset($arContactList, $arCompanyList, $arDealList, $arQuoteList, $arInvoice);
foreach ($arResult['INVOICE'] as $entityID => &$arInvoice)
{
	$arInvoice['FORMATTED_ENTITIES_LINKS'] =
		'<div class="crm-info-links-wrapper">'.
		"\t".'<div class="crm-info-contact-wrapper">'.
		(isset($arInvoice['CONTACT_LINK_HTML']) ? htmlspecialchars_decode($arInvoice['CONTACT_LINK_HTML']) : '').'</div>'.
		"\t".'<div class="crm-info-company-wrapper">'.
		(isset($arInvoice['COMPANY_LINK_HTML']) ? $arInvoice['COMPANY_LINK_HTML'] : '').'</div>'.
		"\t".'<div class="crm-info-deal-wrapper">'.
		(isset($arInvoice['DEAL_LINK_HTML']) ? $arInvoice['DEAL_LINK_HTML'] : '').'</div>'.
		"\t".'<div class="crm-info-quote-wrapper">'.
		(isset($arInvoice['QUOTE_LINK_HTML']) ? $arInvoice['QUOTE_LINK_HTML'] : '').'</div>'.
		'</div>';

	if (array_key_exists('CONTACT_LINK_HTML', $arInvoice))
		unset($arInvoice['CONTACT_LINK_HTML']);
	if (array_key_exists('COMPANY_LINK_HTML', $arInvoice))
		unset($arInvoice['COMPANY_LINK_HTML']);
	if (array_key_exists('DEAL_LINK_HTML', $arInvoice))
		unset($arInvoice['DEAL_LINK_HTML']);
	if (array_key_exists('QUOTE_LINK_HTML', $arInvoice))
		unset($arInvoice['QUOTE_LINK_HTML']);
}
unset($arInvoice);

$CCrmUserType->ListAddEnumFieldsValue(
	$arResult,
	$arResult['INVOICE'],
	$arResult['INVOICE_UF'],
	(($sExportType != '') ? ', ' : '<br />'),
	($sExportType != ''),
	array(
		'FILE_URL_TEMPLATE' =>
			'/bitrix/components/bitrix/crm.invoice.show/show_file.php?ownerId=#owner_id#&fieldName=#field_name#&fileId=#file_id#'
	)
);

$arResult['TOOLBAR_LABEL_TEXT'] = GetMessage('CRM_INVOICE_LIST_TB_LABEL_TEXT', array('#num#' => $totalPaidNumber, '#sum#' => CCrmCurrency::MoneyToString(round($totalPaidSum, 2), $totalPaidCurrencyId)));

$arResult['ROWS_COUNT'] = $obRes->SelectedRowsCount();
$arResult['DB_LIST'] = $obRes;
$arResult['DB_FILTER'] = $arFilter;


if (isset($arResult['INVOICE_ID']) && !empty($arResult['INVOICE_ID']))
{
	// try to load product rows
	$arProductRows = array();

	// checkig access for operation
	$arInvoiceAttr = CCrmPerms::GetEntityAttr('INVOICE', $arResult['INVOICE_ID']);
	foreach ($arResult['INVOICE_ID'] as $iInvoiceId)
	{
		$arResult['INVOICE'][$iInvoiceId]['EDIT'] = $CCrmPerms->CheckEnityAccess('INVOICE', 'WRITE', $arInvoiceAttr[$iInvoiceId]);
		$arResult['INVOICE'][$iInvoiceId]['DELETE'] = $CCrmPerms->CheckEnityAccess('INVOICE', 'DELETE', $arInvoiceAttr[$iInvoiceId]);
	}
}

if ($sExportType == '')
{
	$arResult['NEED_FOR_REBUILD_INVOICE_ATTRS'] = false;
	if(!$bInternal && CCrmPerms::IsAdmin() && COption::GetOptionString('crm', '~CRM_REBUILD_INVOICE_ATTR', 'N') === 'Y')
	{
		$arResult['PATH_TO_PRM_LIST'] = CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_perm_list'));
		$arResult['NEED_FOR_REBUILD_INVOICE_ATTRS'] = true;
	}

	$this->IncludeComponentTemplate();
	include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.invoice/include/nav.php');
	return $arResult['ROWS_COUNT'];
}
else
{
	$APPLICATION->RestartBuffer();
	// hack. any '.default' customized template should contain 'excel' page
	$this->__templateName = '.default';

	if($sExportType === 'carddav')
	{
		Header('Content-Type: text/vcard');
	}
	elseif($sExportType === 'csv')
	{
		Header('Content-Type: text/csv');
		Header('Content-Disposition: attachment;filename=invoices.csv');
	}
	elseif($sExportType === 'excel')
	{
		Header('Content-Type: application/vnd.ms-excel');
		Header('Content-Disposition: attachment;filename=invoices.xls');
	}
	Header('Content-Type: application/octet-stream');
	Header('Content-Transfer-Encoding: binary');

	// add UTF-8 BOM marker
	if (defined('BX_UTF') && BX_UTF)
		echo chr(239).chr(187).chr(191);

	$this->IncludeComponentTemplate($sExportType);

	die();
}
?>
