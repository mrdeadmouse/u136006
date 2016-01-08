<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if (IsModuleInstalled('bizproc'))
{
	if (!CModule::IncludeModule('bizproc'))
	{
		ShowError(GetMessage('BIZPROC_MODULE_NOT_INSTALLED'));
		return;
	}
}

if(!CAllCrmInvoice::installExternalEntities())
	return;
if(!CCrmQuote::LocalComponentCausedUpdater())
	return;

if (!CModule::IncludeModule('currency'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED_CURRENCY'));
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

$CCrmPerms = CCrmPerms::GetCurrentUserPermissions();
if ($CCrmPerms->HavePerm('LEAD', BX_CRM_PERM_NONE, 'READ'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

use Bitrix\Crm\EntityAddress;
use Bitrix\Crm\Format\AddressSeparator;
use Bitrix\Crm\Format\LeadAddressFormatter;

$CCrmLead = new CCrmLead(false);
$CCrmBizProc = new CCrmBizProc('LEAD');

$userID = CCrmSecurityHelper::GetCurrentUserID();
$isAdmin = CCrmPerms::IsAdmin();

$arParams['PATH_TO_LEAD_LIST'] = CrmCheckPath('PATH_TO_LEAD_LIST', $arParams['PATH_TO_LEAD_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_LEAD_EDIT'] = CrmCheckPath('PATH_TO_LEAD_EDIT', $arParams['PATH_TO_LEAD_EDIT'], $APPLICATION->GetCurPage().'?lead_id=#lead_id#&edit');
$arParams['PATH_TO_LEAD_SHOW'] = CrmCheckPath('PATH_TO_LEAD_SHOW', $arParams['PATH_TO_LEAD_SHOW'], $APPLICATION->GetCurPage().'?lead_id=#lead_id#&show');
$arParams['PATH_TO_LEAD_CONVERT'] = CrmCheckPath('PATH_TO_LEAD_CONVERT', $arParams['PATH_TO_LEAD_CONVERT'], $APPLICATION->GetCurPage().'?lead_id=#lead_id#&convert');
$arParams['PATH_TO_QUOTE_EDIT'] = CrmCheckPath('PATH_TO_QUOTE_EDIT', $arParams['PATH_TO_QUOTE_EDIT'], $APPLICATION->GetCurPage().'?quote_id=#quote_id#&edit');
$arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath('PATH_TO_USER_PROFILE', $arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');
$arParams['PATH_TO_USER_BP'] = CrmCheckPath('PATH_TO_USER_BP', $arParams['PATH_TO_USER_BP'], '/company/personal/bizproc/');
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

$arResult['CURRENT_USER_ID'] = CCrmSecurityHelper::GetCurrentUserID();
$arResult['IS_AJAX_CALL'] = isset($_REQUEST['bxajaxid']) || isset($_REQUEST['AJAX_CALL']);
$arResult['SESSION_ID'] = bitrix_sessid();

$addressLabels = EntityAddress::getShortLabels();

//Show error message if required
if($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['error']))
{
	$errorID = strtolower($_GET['error']);
	if(preg_match('/^crm_err_/', $errorID) === 1)
	{
		if(!isset($_SESSION[$errorID]))
		{
			LocalRedirect(CHTTP::urlDeleteParams($arParams['PATH_TO_LEAD_LIST'], array('error')));
		}

		$arErrors = $_SESSION[$errorID];
		if(is_array($arErrors) && !empty($arErrors))
		{
			$errorHtml = '';
			foreach($arErrors as $error)
			{
				if($errorHtml !== '')
				{
					$errorHtml .= '<br />';
				}
				$errorHtml .= htmlspecialcharsbx($error);
			}
			$arResult['ERROR_HTML'] = $errorHtml;
		}
		unset($arErrors, $_SESSION[$errorID]);
	}
}

CUtil::InitJSCore(array('ajax', 'tooltip'));

$arResult['CONVERT'] = !$CCrmPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'READ');

$arResult['GADGET'] = 'N';
if (isset($arParams['GADGET_ID']) && strlen($arParams['GADGET_ID']) > 0)
{
	$arResult['GADGET'] = 'Y';
	$arResult['GADGET_ID'] = $arParams['GADGET_ID'];
}

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
if (!empty($sExportType) && $CCrmPerms->HavePerm('LEAD', BX_CRM_PERM_NONE, 'EXPORT'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$isInExportMode = $sExportType !== '';

$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmLead::$sUFEntityID);
$CCrmFieldMulti = new CCrmFieldMulti();

$arResult['GRID_ID'] = 'CRM_LEAD_LIST_V12'.($bInternal && !empty($arParams['GRID_ID_SUFFIX']) ? '_'.$arParams['GRID_ID_SUFFIX'] : '');
$arResult['STATUS_LIST'] = CCrmStatus::GetStatusListEx('STATUS');
$arResult['SOURCE_LIST'] = CCrmStatus::GetStatusListEx('SOURCE');
// Please, uncomment if required
//$arResult['CURRENCY_LIST'] = CCrmCurrencyHelper::PrepareListItems();
$arResult['FILTER'] = array();
$arResult['FILTER2LOGIC'] = array();
$arResult['FILTER_PRESETS'] = array();
$arResult['PERMS']['ADD']    = !$CCrmPerms->HavePerm('LEAD', BX_CRM_PERM_NONE, 'ADD');
$arResult['PERMS']['WRITE']  = !$CCrmPerms->HavePerm('LEAD', BX_CRM_PERM_NONE, 'WRITE');
$arResult['PERMS']['DELETE'] = !$CCrmPerms->HavePerm('LEAD', BX_CRM_PERM_NONE, 'DELETE');

$arResult['~STATUS_LIST_WRITE']= CCrmStatus::GetStatusList('STATUS');
$arResult['STATUS_LIST_WRITE'] = array();
unset($arResult['~STATUS_LIST_WRITE']['CONVERTED'], $arResult['~STATUS_LIST_EX']['CONVERTED']);
foreach ($arResult['~STATUS_LIST_WRITE'] as $sStatusId => $sStatusTitle)
{
	if ($CCrmPerms->GetPermType('LEAD', 'WRITE', array('STATUS_ID'.$sStatusId)) > BX_CRM_PERM_NONE)
		$arResult['STATUS_LIST_WRITE'][$sStatusId] = $sStatusTitle;
}

if (!$bInternal)
{
	$arResult['FILTER2LOGIC'] = array('TITLE', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'POST', 'COMMENTS', 'COMPANY_TITLE');

	$arResult['FILTER'] = array(
		array('id' => 'FIND', 'name' => GetMessage('CRM_COLUMN_FIND'), 'default' => true, 'type' => 'quick', 'items' => array(
			't_n_ln' => GetMessage('CRM_COLUMN_TITLE_NAME_LAST_NAME'),
			'email' => GetMessage('CRM_COLUMN_EMAIL'),
			'phone' => GetMessage('CRM_COLUMN_PHONE'))
		),
		array('id' => 'ID', 'name' => GetMessage('CRM_COLUMN_ID')),
		array('id' => 'TITLE', 'name' => GetMessage('CRM_COLUMN_TITLE'), 'default' => true),
		array('id' => 'SOURCE_ID', 'params' => array('multiple' => 'Y'), 'name' => GetMessage('CRM_COLUMN_SOURCE'), 'default' => true, 'type' => 'list', 'items' => CCrmStatus::GetStatusList('SOURCE')),
		array('id' => 'NAME', 'name' => GetMessage('CRM_COLUMN_NAME')),
		array('id' => 'LAST_NAME', 'name' => GetMessage('CRM_COLUMN_LAST_NAME')),
		array('id' => 'SECOND_NAME', 'name' => GetMessage('CRM_COLUMN_SECOND_NAME')),
		array('id' => 'BIRTHDATE', 'name' => GetMessage('CRM_COLUMN_BIRTHDATE'), 'type' => 'date'),
		array('id' => 'STATUS_ID', 'params' => array('multiple' => 'Y'), 'default' => true, 'name' => GetMessage('CRM_COLUMN_STATUS'), 'type' => 'list', 'items' => CCrmStatus::GetStatusList('STATUS')),
		array('id' => 'OPPORTUNITY', 'name' => GetMessage('CRM_COLUMN_OPPORTUNITY'), 'default' => true, 'type' => 'number'),
		array('id' => 'CURRENCY_ID', 'params' => array('multiple' => 'Y'), 'name' => GetMessage('CRM_COLUMN_CURRENCY_ID'), 'default' => true, 'type' => 'list', 'items' => CCrmCurrencyHelper::PrepareListItems()),
		array('id' => 'DATE_CREATE', 'default' => true, 'name' => GetMessage('CRM_COLUMN_DATE_CREATE'), 'type' => 'date'),
		array('id' => 'CREATED_BY_ID',  'name' => GetMessage('CRM_COLUMN_CREATED_BY'), 'default' => false, 'enable_settings' => false, 'type' => 'user'),
		array('id' => 'DATE_MODIFY', 'name' => GetMessage('CRM_COLUMN_DATE_MODIFY'), 'type' => 'date'),
		array('id' => 'MODIFY_BY_ID',  'name' => GetMessage('CRM_COLUMN_MODIFY_BY'), 'default' => false, 'enable_settings' => true, 'type' => 'user'),
		array('id' => 'ASSIGNED_BY_ID',  'name' => GetMessage('CRM_COLUMN_ASSIGNED_BY'), 'default' => true, 'enable_settings' => true, 'type' => 'user'),
		array('id' => 'PHONE', 'name' => GetMessage('CRM_COLUMN_PHONE')),
		array('id' => 'EMAIL', 'name' => GetMessage('CRM_COLUMN_EMAIL')),
		array('id' => 'WEB', 'name' => GetMessage('CRM_COLUMN_WEB')),
		array('id' => 'IM', 'name' => GetMessage('CRM_COLUMN_MESSENGER')),
		array('id' => 'COMPANY_TITLE', 'name' => GetMessage('CRM_COLUMN_COMPANY_TITLE')),
		array('id' => 'POST', 'name' => GetMessage('CRM_COLUMN_POST')),

		array('id' => 'ADDRESS', 'name' => $addressLabels['ADDRESS']),
		array('id' => 'ADDRESS_2', 'name' => $addressLabels['ADDRESS_2']),
		array('id' => 'ADDRESS_CITY', 'name' => $addressLabels['CITY']),
		array('id' => 'ADDRESS_REGION', 'name' => $addressLabels['REGION']),
		array('id' => 'ADDRESS_PROVINCE', 'name' => $addressLabels['PROVINCE']),
		array('id' => 'ADDRESS_POSTAL_CODE', 'name' => $addressLabels['POSTAL_CODE']),
		array('id' => 'ADDRESS_COUNTRY', 'name' => $addressLabels['COUNTRY']),

		array('id' => 'COMMENTS', 'name' => GetMessage('CRM_COLUMN_COMMENTS')),
		array('id' => 'STATUS_CONVERTED', 'name' => GetMessage('CRM_COLUMN_STATUS_CONVERTED'), 'type' => 'list', 'items' => array('' => '', 'Y' => GetMessage('MAIN_YES'), 'N' => GetMessage('MAIN_NO'))),
		array('id' => 'PRODUCT_ROW_PRODUCT_ID', 'name' => GetMessage('CRM_COLUMN_PRODUCT'), 'enable_settings' => false)
	);

	$CCrmUserType->ListAddFilterFields($arResult['FILTER'], $arResult['FILTER2LOGIC'], $arResult['GRID_ID']);

	$currentUserID = $arResult['CURRENT_USER_ID'];;
	$currentUserName = CCrmViewHelper::GetFormattedUserName($currentUserID, $arParams['NAME_TEMPLATE']);
	$arResult['FILTER_PRESETS'] = array(
		'filter_new' => array('name' => GetMessage('CRM_PRESET_NEW'), 'fields' => array('STATUS_ID' => array('selNEW' => 'NEW'))),
		'filter_my' => array('name' => GetMessage('CRM_PRESET_MY'), 'fields' => array( 'ASSIGNED_BY_ID_name' => $currentUserName, 'ASSIGNED_BY_ID' => $currentUserID)),
		//'filter_change_today' => array('name' => GetMessage('CRM_PRESET_CHANGE_TODAY'), 'fields' => array('DATE_MODIFY_datesel' => 'today')),
		//'filter_change_yesterday' => array('name' => GetMessage('CRM_PRESET_CHANGE_YESTERDAY'), 'fields' => array('DATE_MODIFY_datesel' => 'yesterday')),
		//'filter_change_my' => array('name' => GetMessage('CRM_PRESET_CHANGE_MY'), 'fields' =>array( 'MODIFY_BY_ID_name' => $currentUserName, 'MODIFY_BY_ID' => $currentUserID))
	);
}

// Headers initialization -->
$arResult['HEADERS'] = array(
	array('id' => 'ID', 'name' => GetMessage('CRM_COLUMN_ID'), 'sort' => 'id', 'default' => false, 'editable' => false, 'type' => 'int', 'class' => 'minimal'),
	array('id' => 'LEAD_SUMMARY', 'name' => GetMessage('CRM_COLUMN_LEAD'), 'sort' => 'title', 'default' => true, 'editable' => false),
	array('id' => 'STATUS_ID', 'name' => GetMessage('CRM_COLUMN_STATUS'), 'sort' => 'status_sort', 'default' => true, 'editable' => array('items' => $arResult['STATUS_LIST_WRITE']), 'type' => 'list'),
);

// Dont display activities in INTERNAL mode.
if(!$bInternal)
{
	$arResult['HEADERS'][] = array('id' => 'ACTIVITY_ID', 'name' => GetMessage('CRM_COLUMN_ACTIVITY'), 'sort' => 'nearest_activity', 'default' => true);
}

$arResult['HEADERS'] = array_merge(
	$arResult['HEADERS'],
	array(
		array('id' => 'LEAD_FORMATTED_NAME', 'name' => GetMessage('CRM_COLUMN_FULL_NAME'), 'sort' => 'last_name', 'default' => true, 'editable' => false),
		array('id' => 'TITLE', 'name' => GetMessage('CRM_COLUMN_TITLE'), 'sort' => 'title', 'default' => false, 'editable' => true),
		array('id' => 'NAME', 'name' => GetMessage('CRM_COLUMN_NAME'), 'sort' => 'name', 'default' => false, 'editable' => true, 'class' => 'username'),
		array('id' => 'SECOND_NAME', 'name' => GetMessage('CRM_COLUMN_SECOND_NAME'), 'sort' => 'second_name', 'default' => false, 'editable' => true, 'class' => 'username'),
		array('id' => 'LAST_NAME', 'name' => GetMessage('CRM_COLUMN_LAST_NAME'), 'sort' => 'last_name', 'default' => false, 'editable' => true, 'class' => 'username'),
		array('id' => 'BIRTHDATE', 'name' => GetMessage('CRM_COLUMN_BIRTHDATE'), 'sort' => 'BIRTHDATE', 'default' => false, 'editable' => true, 'type' => 'date'),
		array('id' => 'DATE_CREATE', 'name' => GetMessage('CRM_COLUMN_DATE_CREATE'), 'sort' => 'date_create', 'default' => true, 'editable' => false, 'class' => 'date'),
		array('id' => 'SOURCE_ID', 'name' => GetMessage('CRM_COLUMN_SOURCE'), 'sort' => 'source_id', 'default' => false, 'editable' => array('items' => CCrmStatus::GetStatusList('SOURCE')), 'type' => 'list')

	)
);

$CCrmFieldMulti->PrepareListHeaders($arResult['HEADERS']);
if($isInExportMode)
{
	$CCrmFieldMulti->ListAddHeaders($arResult['HEADERS']);
}

$arResult['HEADERS'] = array_merge($arResult['HEADERS'], array(
	array('id' => 'ASSIGNED_BY', 'name' => GetMessage('CRM_COLUMN_ASSIGNED_BY'), 'sort' => 'assigned_by', 'default' => true, 'editable' => false, 'class' => 'username'),
	array('id' => 'STATUS_DESCRIPTION', 'name' => GetMessage('CRM_COLUMN_STATUS_DESCRIPTION'), 'sort' => false /**because of MSSQL**/, 'default' => false, 'editable' => false),
	array('id' => 'SOURCE_DESCRIPTION', 'name' => GetMessage('CRM_COLUMN_SOURCE_DESCRIPTION'), 'sort' => false /**because of MSSQL**/, 'default' => false, 'editable' => false),
	array('id' => 'CREATED_BY', 'name' => GetMessage('CRM_COLUMN_CREATED_BY'), 'sort' => 'created_by', 'default' => false, 'editable' => false, 'class' => 'username'),
	array('id' => 'DATE_MODIFY', 'name' => GetMessage('CRM_COLUMN_DATE_MODIFY'), 'sort' => 'date_modify', 'default' => false, 'class' => 'date'),
	array('id' => 'MODIFY_BY', 'name' => GetMessage('CRM_COLUMN_MODIFY_BY'), 'sort' => 'modify_by', 'default' => false, 'editable' => false, 'class' => 'username'),
	array('id' => 'COMPANY_TITLE', 'name' => GetMessage('CRM_COLUMN_COMPANY_TITLE'), 'sort' => 'company_title', 'default' => false, 'editable' => true),
	array('id' => 'POST', 'name' => GetMessage('CRM_COLUMN_POST'), 'sort' => 'post', 'default' => false, 'editable' => true),

	array('id' => 'FULL_ADDRESS', 'name' => EntityAddress::getFullAddressLabel(), 'sort' => false, 'default' => false, 'editable' => false),
	array('id' => 'ADDRESS', 'name' => $addressLabels['ADDRESS'], 'sort' => 'address', 'default' => false, 'editable' => false),
	array('id' => 'ADDRESS_2', 'name' => $addressLabels['ADDRESS_2'], 'sort' => 'address_2', 'default' => false, 'editable' => false),
	array('id' => 'ADDRESS_CITY', 'name' => $addressLabels['CITY'], 'sort' => 'address_city', 'default' => false, 'editable' => false),
	array('id' => 'ADDRESS_REGION', 'name' => $addressLabels['REGION'], 'sort' => 'address_region', 'default' => false, 'editable' => false),
	array('id' => 'ADDRESS_PROVINCE', 'name' => $addressLabels['PROVINCE'], 'sort' => 'address_province', 'default' => false, 'editable' => false),
	array('id' => 'ADDRESS_POSTAL_CODE', 'name' => $addressLabels['POSTAL_CODE'], 'sort' => 'address_postal_code', 'default' => false, 'editable' => false),
	array('id' => 'ADDRESS_COUNTRY', 'name' => $addressLabels['COUNTRY'], 'sort' => 'address_country', 'default' => false, 'editable' => false),

	array('id' => 'COMMENTS', 'name' => GetMessage('CRM_COLUMN_COMMENTS'), 'sort' => false /**because of MSSQL**/, 'default' => false, 'editable' => false),
	array('id' => 'SUM', 'name' => GetMessage('CRM_COLUMN_SUM'), 'sort' => 'opportunity_account', 'default' => false, 'editable' => false, 'align' => 'right'),
	array('id' => 'OPPORTUNITY', 'name' => GetMessage('CRM_COLUMN_OPPORTUNITY_2'), 'sort' => 'opportunity', 'default' => false, 'editable' => true, 'align' => 'right'),
	array('id' => 'CURRENCY_ID', 'name' => GetMessage('CRM_COLUMN_CURRENCY_ID'), 'sort' => 'currency_id', 'default' => false, 'editable' => array('items' => CCrmCurrencyHelper::PrepareListItems()), 'type' => 'list'),
	array('id' => 'PRODUCT_ID', 'name' => GetMessage('CRM_COLUMN_PRODUCT_ID'), 'sort' => false, 'default' => $isInExportMode, 'editable' => false, 'type' => 'list')
));

$CCrmUserType->ListAddHeaders($arResult['HEADERS']);

$arBPData = array();
if (IsModuleInstalled('bizproc'))
{
	$arBPData = CBPDocument::GetWorkflowTemplatesForDocumentType(array('crm', 'CCrmDocumentLead', 'LEAD'));
	$arDocumentStates = CBPDocument::GetDocumentStates(
		array('crm', 'CCrmDocumentLead', 'LEAD'),
		null
	);
	foreach ($arBPData as $arBP)
	{
		if (!CBPDocument::CanUserOperateDocumentType(
			CBPCanUserOperateOperation::ViewWorkflow,
			$userID,
			array('crm', 'CCrmDocumentLead', 'LEAD'),
			array(
				'UserGroups' => $CCrmBizProc->arCurrentUserGroups,
				'DocumentStates' => $arDocumentStates,
				'WorkflowTemplateId' => $arBP['ID'],
				'UserIsAdmin' => $isAdmin,
				'CRMPermission' => $arResult['PERMS']['READ']
			)
		))
		{
			continue;
		}
		$arResult['HEADERS'][] = array('id' => 'BIZPROC_'.$arBP['ID'], 'name' => $arBP['NAME'], 'sort' => false, 'default' => false, 'editable' => false);
	}
}
// <-- Headers initialization

// Try to extract user action data -->
// We have to extract them before call of CGridOptions::GetFilter() or the custom filter will be corrupted.
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

		if(isset($_POST['ACTION_STATUS_ID']))
		{
			$actionData['STATUS_ID'] = trim($_POST['ACTION_STATUS_ID']);
			unset($_POST['ACTION_STATUS_ID'], $_REQUEST['ACTION_STATUS_ID']);
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

		if(isset($_POST['ACTION_OPENED']))
		{
			$actionData['OPENED'] = trim($_POST['ACTION_OPENED']);
			unset($_POST['ACTION_OPENED'], $_REQUEST['ACTION_OPENED']);
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
// <-- Try to extract user action data

// HACK: for clear filter by CREATED_BY_ID, MODIFY_BY_ID and ASSIGNED_BY_ID
if($_SERVER['REQUEST_METHOD'] === 'GET')
{
	if(isset($_REQUEST['CREATED_BY_ID_name']) && $_REQUEST['CREATED_BY_ID_name'] === '')
	{
		$_REQUEST['CREATED_BY_ID'] = $_GET['CREATED_BY_ID'] = array();
	}

	if(isset($_REQUEST['MODIFY_BY_ID_name']) && $_REQUEST['MODIFY_BY_ID_name'] === '')
	{
		$_REQUEST['MODIFY_BY_ID'] = $_GET['MODIFY_BY_ID'] = array();
	}

	if(isset($_REQUEST['ASSIGNED_BY_ID_name']) && $_REQUEST['ASSIGNED_BY_ID_name'] === '')
	{
		$_REQUEST['ASSIGNED_BY_ID'] = $_GET['ASSIGNED_BY_ID'] = array();
	}
}

if (intval($arParams['LEAD_COUNT']) <= 0)
	$arParams['LEAD_COUNT'] = 20;

$arNavParams = array(
	'nPageSize' => $arParams['LEAD_COUNT']
);

$arNavigation = CDBResult::GetNavParams($arNavParams);
$CGridOptions = new CCrmGridOptions($arResult['GRID_ID'], $arResult['FILTER_PRESETS']);
$arNavParams = $CGridOptions->GetNavParams($arNavParams);
$arNavParams['bShowAll'] = false;

$arGridFilter = $CGridOptions->GetFilter($arResult['FILTER']);
$arFilter += $arGridFilter;
$USER_FIELD_MANAGER->AdminListAddFilter(CCrmLead::$sUFEntityID, $arFilter);

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
			$urlParams[] = $arFilter['id'];
	}
	$urlParams[] = 'clear_filter';
	$CGridOptions->GetFilter(array());
	LocalRedirect($APPLICATION->GetCurPageParam('', $urlParams));
}

// converts data from filter
if (isset($arFilter['FIND_list']) && !empty($arFilter['FIND']))
{
	if ($arFilter['FIND_list'] == 't_n_ln')
	{
		$find = $arFilter['FIND'];
		$arFilter['__INNER_FILTER'] = array(
			'LOGIC' => 'OR',
			'%TITLE' => $find,
			'%NAME' => $find,
			'%LAST_NAME' => $find,
			'%COMPANY_TITLE' => $find
		);
	}
	else
		$arFilter[strtoupper($arFilter['FIND_list'])] = $arFilter['FIND'];
	unset($arFilter['FIND_list'], $arFilter['FIND']);
}

CCrmEntityHelper::PrepareMultiFieldFilter($arFilter);
$arImmutableFilters = array('FM', 'ID', 'CURRENCY_ID', 'ASSIGNED_BY_ID', 'CREATED_BY_ID', 'MODIFY_BY_ID', 'PRODUCT_ROW_PRODUCT_ID');
foreach ($arFilter as $k => $v)
{
	if(in_array($k, $arImmutableFilters, true))
	{
		continue;
	}

	$arMatch = array();

	if(in_array($k, array('PRODUCT_ID', 'STATUS_ID', 'SOURCE_ID', 'COMPANY_ID', 'CONTACT_ID')))
	{
		// Bugfix #23121 - to suppress comparison by LIKE
		$arFilter['='.$k] = $v;
		unset($arFilter[$k]);
	}
	elseif($k === 'ORIGINATOR_ID')
	{
		// HACK: build filter by internal entities
		$arFilter['=ORIGINATOR_ID'] = $v !== '__INTERNAL' ? $v : null;
		unset($arFilter[$k]);
	}
	if($k === 'ADDRESS'
		|| $k === 'ADDRESS_2'
		|| $k === 'ADDRESS_CITY'
		|| $k === 'ADDRESS_REGION'
		|| $k === 'ADDRESS_PROVINCE'
		|| $k === 'ADDRESS_POSTAL_CODE'
		|| $k === 'ADDRESS_COUNTRY')
	{
		$arFilter["=%{$k}"] = "{$v}%";
		unset($arFilter[$k]);
	}
	elseif (preg_match('/(.*)_from$/i'.BX_UTF_PCRE_MODIFIER, $k, $arMatch))
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
			if (($arMatch[1] == 'DATE_CREATE' || $arMatch[1] == 'DATE_MODIFY') && !preg_match('/\d{1,2}:\d{1,2}(:\d{1,2})?$/'.BX_UTF_PCRE_MODIFIER, $v))
			{
				$v .=  ' 23:59:59';
			}
			$arFilter['<='.$arMatch[1]] = $v;
		}
		unset($arFilter[$k]);
	}
	elseif (in_array($k, $arResult['FILTER2LOGIC']))
	{
		// Bugfix #26956 - skip empty values in logical filter
		$v = trim($v);
		if($v !== '')
		{
			$arFilter['?'.$k] = $v;
		}
		unset($arFilter[$k]);
	}
	elseif($k === 'STATUS_CONVERTED')
	{
		if($v !== '')
		{
			$arFilter[$v === 'N' ? '!@STATUS_ID' : '@STATUS_ID'] = array('JUNK', 'CONVERTED');
		}
		unset($arFilter['STATUS_CONVERTED']);
	}
	elseif (strpos($k, 'UF_') !== 0 && $k != 'LOGIC' && $k != '__INNER_FILTER')
	{
		$arFilter['%'.$k] = $v;
		unset($arFilter[$k]);
	}
}

// POST & GET actions processing -->
if($actionData['ACTIVE'])
{
	$arErrors = array();
	$arCurrentUserGroups = $USER->GetUserGroupArray();
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

				if (!$isAdmin)
				{
					$arFilterDel['!=STATUS_ID'] = 'CONVERTED';
				}

				$obRes = CCrmLead::GetListEx(array(), $arFilterDel, false, false, array('ID'));
				while($arLead = $obRes->Fetch())
				{
					$ID = $arLead['ID'];
					$arEntityAttr = $CCrmPerms->GetEntityAttr('LEAD', array($ID));
					if (!$CCrmPerms->CheckEnityAccess('LEAD', 'DELETE', $arEntityAttr[$ID]))
					{
						continue ;
					}

					$DB->StartTransaction();

					if ($CCrmBizProc->Delete($ID, $arEntityAttr[$ID])
						&& $CCrmLead->Delete($ID, array('CHECK_DEPENDENCIES' => true, 'PROCESS_BIZPROC' => false)))
					{
						$DB->Commit();
					}
					else
					{
						$arErrors[] = $CCrmLead->LAST_ERROR;
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
					$arEntityAttr = $CCrmPerms->GetEntityAttr('LEAD', array($ID));
					if (!$CCrmPerms->CheckEnityAccess('LEAD', 'WRITE', $arEntityAttr[$ID]))
					{
						continue ;
					}

					$dbLead = CCrmLead::GetListEx(
						array(),
						array('ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
						false,
						false,
						array('STATUS_ID'),
						array()
					);
					$arLead = $dbLead ? $dbLead->Fetch() : null;
					if(!is_array($arLead)
						|| (isset($arLead['STATUS_ID']) && $arLead['STATUS_ID'] === 'CONVERTED'))
					{
						continue;
					}

					$arUpdateData = array();
					reset($arResult['HEADERS']);
					foreach ($arResult['HEADERS'] as $arHead)
					{
						if (isset($arHead['editable'])
							&& (is_array($arHead['editable']) || $arHead['editable'] == true)
							&& isset($arSrcData[$arHead['id']]))
						{
							$arUpdateData[$arHead['id']] = $arSrcData[$arHead['id']];
						}
					}
					if (!empty($arUpdateData))
					{
						$DB->StartTransaction();

						if($CCrmLead->Update($ID, $arUpdateData))
						{
							$DB->Commit();

							CCrmBizProcHelper::AutoStartWorkflows(
								CCrmOwnerType::Lead,
								$ID,
								CCrmBizProcEventType::Edit,
								$arErrors
							);
						}
						else
						{
							$DB->Rollback();
						}
					}
				}
			}
		}
		elseif ($actionData['NAME'] == 'tasks')
		{
			if (isset($actionData['ID']) && is_array($actionData['ID']))
			{
				$arTaskID = array();
				foreach($actionData['ID'] as $ID)
				{
					$arTaskID[] = 'L_'.$ID;
				}

				$APPLICATION->RestartBuffer();

				$taskUrl = CHTTP::urlAddParams(
					CComponentEngine::MakePathFromTemplate(
						COption::GetOptionString('tasks', 'paths_task_user_edit', ''),
						array(
							'task_id' => 0,
							'user_id' => $userID
						)
					),
					array(
						'UF_CRM_TASK' => implode(';', $arTaskID),
						'TITLE' => urlencode(GetMessage('CRM_TASK_TITLE_PREFIX')),
						'TAGS' => urlencode(GetMessage('CRM_TASK_TAG')),
						'back_url' => urlencode($arParams['PATH_TO_LEAD_LIST'])
					)
				);
				if ($actionData['AJAX_CALL'])
				{
					echo '<script> parent.window.location = "'.CUtil::JSEscape($taskUrl).'";</script>';
					exit();
				}
				else
				{
					LocalRedirect($taskUrl);
				}
			}
		}
		elseif ($actionData['NAME'] == 'set_status')
		{
			if(isset($actionData['STATUS_ID']) && $actionData['STATUS_ID'] != '') // Fix for issue #26628
			{
				$arIDs = array();
				if ($actionData['ALL_ROWS'])
				{
					$arActionFilter = $arFilter;
					$arActionFilter['CHECK_PERMISSIONS'] = 'N'; // Ignore 'WRITE' permission - we will check it before update.

					$dbRes = CCrmLead::GetListEx(
						array(),
						$arActionFilter,
						false,
						false,
						array('ID', 'STATUS_ID')
					);

					while($arLead = $dbRes->Fetch())
					{
						// Skip leads in status 'CONVERTED'. 'CONVERTED' is system status and it can not be changed.
						if(isset($arLead['STATUS_ID']) && $arLead['STATUS_ID'] === 'CONVERTED')
						{
							continue;
						}

						$arIDs[] = $arLead['ID'];
					}
				}
				elseif (isset($actionData['ID']) && is_array($actionData['ID']))
				{
					$dbRes = CCrmLead::GetListEx(
						array(),
						array(
							'@ID'=> $actionData['ID'],
							'CHECK_PERMISSIONS' => 'N'
						),
						false,
						false,
						array('ID', 'STATUS_ID')
					);

					while($arLead = $dbRes->Fetch())
					{
						// Skip leads in status 'CONVERTED'. 'CONVERTED' is system status and it can not be changed.
						if(isset($arLead['STATUS_ID']) && $arLead['STATUS_ID'] === 'CONVERTED')
						{
							continue;
						}

						$arIDs[] = $arLead['ID'];
					}
				}

				$arEntityAttr = $CCrmPerms->GetEntityAttr('LEAD', $arIDs);
				foreach($arIDs as $ID)
				{
					if (!$CCrmPerms->CheckEnityAccess('LEAD', 'WRITE', $arEntityAttr[$ID]))
					{
						continue;
					}

					$DB->StartTransaction();

					$arUpdateData = array(
						'STATUS_ID' => $actionData['STATUS_ID']
					);

					if($CCrmLead->Update($ID, $arUpdateData, true, true, array('DISABLE_USER_FIELD_CHECK' => true)))
					{
						$DB->Commit();

						CCrmBizProcHelper::AutoStartWorkflows(
							CCrmOwnerType::Lead,
							$ID,
							CCrmBizProcEventType::Edit,
							$arErrors
						);
					}
					else
					{
						$DB->Rollback();
					}
				}
			}
		}
		elseif ($actionData['NAME'] == 'assign_to')
		{
			if(isset($actionData['ASSIGNED_BY_ID']))
			{
				$arIDs = array();
				if ($actionData['ALL_ROWS'])
				{
					$arActionFilter = $arFilter;
					$arActionFilter['CHECK_PERMISSIONS'] = 'N'; // Ignore 'WRITE' permission - we will check it before update.
					$dbRes = CCrmLead::GetListEx(array(), $arActionFilter, false, false, array('ID'));
					while($arLead = $dbRes->Fetch())
					{
						$arIDs[] = $arLead['ID'];
					}
				}
				elseif (isset($actionData['ID']) && is_array($actionData['ID']))
				{
					$arIDs = $actionData['ID'];
				}

				$arEntityAttr = $CCrmPerms->GetEntityAttr('LEAD', $arIDs);


				foreach($arIDs as $ID)
				{
					if (!$CCrmPerms->CheckEnityAccess('LEAD', 'WRITE', $arEntityAttr[$ID]))
					{
						continue;
					}

					$DB->StartTransaction();

					$arUpdateData = array(
						'ASSIGNED_BY_ID' => $actionData['ASSIGNED_BY_ID']
					);

					if($CCrmLead->Update($ID, $arUpdateData, true, true, array('DISABLE_USER_FIELD_CHECK' => true)))
					{
						$DB->Commit();

						CCrmBizProcHelper::AutoStartWorkflows(
							CCrmOwnerType::Lead,
							$ID,
							CCrmBizProcEventType::Edit,
							$arErrors
						);
					}
					else
					{
						$DB->Rollback();
					}
				}
			}
		}
		elseif ($actionData['NAME'] == 'mark_as_opened')
		{
			if(isset($actionData['OPENED']) && $actionData['OPENED'] != '')
			{
				$isOpened = strtoupper($actionData['OPENED']) === 'Y' ? 'Y' : 'N';
				$arIDs = array();
				if ($actionData['ALL_ROWS'])
				{
					$arActionFilter = $arFilter;
					$arActionFilter['CHECK_PERMISSIONS'] = 'N'; // Ignore 'WRITE' permission - we will check it before update.

					$dbRes = CCrmLead::GetListEx(
						array(),
						$arActionFilter,
						false,
						false,
						array('ID', 'OPENED')
					);

					while($arLead = $dbRes->Fetch())
					{
						if(isset($arLead['OPENED']) && $arLead['OPENED'] === $isOpened)
						{
							continue;
						}

						$arIDs[] = $arLead['ID'];
					}
				}
				elseif (isset($actionData['ID']) && is_array($actionData['ID']))
				{
					$dbRes = CCrmLead::GetListEx(
						array(),
						array(
							'@ID'=> $actionData['ID'],
							'CHECK_PERMISSIONS' => 'N'
						),
						false,
						false,
						array('ID', 'OPENED')
					);

					while($arLead = $dbRes->Fetch())
					{
						if(isset($arLead['OPENED']) && $arLead['OPENED'] === $isOpened)
						{
							continue;
						}

						$arIDs[] = $arLead['ID'];
					}
				}

				$arEntityAttr = $CCrmPerms->GetEntityAttr('LEAD', $arIDs);
				foreach($arIDs as $ID)
				{
					if (!$CCrmPerms->CheckEnityAccess('LEAD', 'WRITE', $arEntityAttr[$ID]))
					{
						continue;
					}

					$DB->StartTransaction();
					$arUpdateData = array('OPENED' => $isOpened);
					if($CCrmLead->Update($ID, $arUpdateData, true, true, array('DISABLE_USER_FIELD_CHECK' => true)))
					{
						$DB->Commit();

						CCrmBizProcHelper::AutoStartWorkflows(
							CCrmOwnerType::Lead,
							$ID,
							CCrmBizProcEventType::Edit,
							$arErrors
						);
					}
					else
					{
						$DB->Rollback();
					}
				}
			}
		}
		if (!$actionData['AJAX_CALL'])
		{
			$redirectUrl = $arParams['PATH_TO_LEAD_LIST'];
			if(!empty($arErrors))
			{
				$errorID = uniqid('crm_err_');
				$_SESSION[$errorID] = $arErrors;
				$redirectUrl = CHTTP::urlAddParams($redirectUrl, array('error' => $errorID));
			}
			LocalRedirect($redirectUrl);
		}
	}
	else//if ($actionData['METHOD'] == 'GET')
	{
		$arErrors = array();
		if ($actionData['NAME'] == 'delete' && isset($actionData['ID']))
		{
			$ID = intval($actionData['ID']);

			$arEntityAttr = $CCrmPerms->GetEntityAttr('LEAD', array($ID));
			$attr = $arEntityAttr[$ID];

			if($CCrmPerms->CheckEnityAccess('LEAD', 'DELETE', $attr))
			{
				$DB->StartTransaction();

				if($CCrmBizProc->Delete($ID, $attr)
					&& $CCrmLead->Delete($ID, array('CHECK_DEPENDENCIES' => true, 'PROCESS_BIZPROC' => false)))
				{
					$DB->Commit();
				}
				else
				{
					$arErrors[] = $CCrmLead->LAST_ERROR;
					$DB->Rollback();
				}
			}
		}

		if (!$actionData['AJAX_CALL'])
		{
			$redirectUrl = $bInternal ? '?'.$arParams['FORM_ID'].'_active_tab=tab_lead' : $arParams['PATH_TO_LEAD_LIST'];
			if(!empty($arErrors))
			{
				$errorID = uniqid('crm_err_');
				$_SESSION[$errorID] = $arErrors;
				$redirectUrl = CHTTP::urlAddParams($redirectUrl, array('error' => $errorID));
			}
			LocalRedirect($redirectUrl);
		}
	}
}
// <-- POST & GET actions processing

$_arSort = $CGridOptions->GetSorting(
	array(
		'sort' => array('nearest_activity' => 'asc'),
		'vars' => array('by' => 'by', 'order' => 'order')
	)
);

$arResult['SORT'] = !empty($arSort) ? $arSort : $_arSort['sort'];
$arResult['SORT_VARS'] = $_arSort['vars'];


$arSelect = $CGridOptions->GetVisibleColumns();

// Remove column for deleted UF
if ($CCrmUserType->NormalizeFields($arSelect))
	$CGridOptions->SetVisibleColumns($arSelect);

$arSelectMap = array_fill_keys($arSelect, true);

$arResult['ENABLE_BIZPROC'] = IsModuleInstalled('bizproc');
$arResult['ENABLE_TASK'] = IsModuleInstalled('tasks');
// Fill in default values if empty
if (empty($arSelectMap))
{
	foreach ($arResult['HEADERS'] as $arHeader)
	{
		if ($arHeader['default'])
		{
			$arSelectMap[$arHeader['id']] = true;
		}
	}

	//Disable bizproc fields processing
	$arResult['ENABLE_BIZPROC'] = false;
}
else
{
	if($arResult['ENABLE_BIZPROC'])
	{
		//Check if bizproc fields selected
		$hasBizprocFields = false;
		foreach($arSelectMap as $k => $v)
		{
			if(strncmp($k, 'BIZPROC_', 8) === 0)
			{
				$hasBizprocFields = true;
				break;
			}
		}
		$arResult['ENABLE_BIZPROC'] = $hasBizprocFields;
	}
	unset($fieldName);
}

$arSelectedHeaders = array_keys($arSelectMap);

if ($arResult['GADGET'] == 'Y')
{
	$arSelectMap['DATE_CREATE'] =
		$arSelectMap['TITLE'] =
		$arSelectMap['STATUS_ID'] =
		$arSelectMap['NAME'] =
		$arSelectMap['SECOND_NAME'] =
		$arSelectMap['LAST_NAME'] =
		$arSelectMap['POST'] =
		$arSelectMap['COMPANY_TITLE'] = true;
}
else
{
	if(isset($arSelectMap['LEAD_SUMMARY']))
	{
		$arSelectMap['TITLE'] =
			$arSelectMap['SOURCE_ID'] = true;
	}

	if(isset($arSelectMap['ACTIVITY_ID']))
	{
		$arSelectMap['ACTIVITY_TIME'] =
			$arSelectMap['ACTIVITY_SUBJECT'] =
			$arSelectMap['C_ACTIVITY_ID'] =
			$arSelectMap['C_ACTIVITY_TIME'] =
			$arSelectMap['C_ACTIVITY_SUBJECT'] =
			$arSelectMap['C_ACTIVITY_RESP_ID'] =
			$arSelectMap['C_ACTIVITY_RESP_LOGIN'] =
			$arSelectMap['C_ACTIVITY_RESP_NAME'] =
			$arSelectMap['C_ACTIVITY_RESP_LAST_NAME'] =
			$arSelectMap['C_ACTIVITY_RESP_SECOND_NAME'] = true;
	}

	if(isset($arSelectMap['LEAD_FORMATTED_NAME']))
	{
		$arSelectMap['NAME'] =
			$arSelectMap['SECOND_NAME'] =
			$arSelectMap['LAST_NAME'] = true;
	}

	if(isset($arSelectMap['CREATED_BY']))
	{
		$arSelectMap['CREATED_BY_LOGIN'] =
			$arSelectMap['CREATED_BY_NAME'] =
			$arSelectMap['CREATED_BY_LAST_NAME'] =
			$arSelectMap['CREATED_BY_SECOND_NAME'] = true;
	}

	if(isset($arSelectMap['MODIFY_BY']))
	{
		$arSelectMap['MODIFY_BY_LOGIN'] =
			$arSelectMap['MODIFY_BY_NAME'] =
			$arSelectMap['MODIFY_BY_LAST_NAME'] =
			$arSelectMap['MODIFY_BY_SECOND_NAME'] = true;
	}

	// Always need to remove the menu items
	if(!isset($arSelectMap['STATUS_ID']))
	{
		$arSelectMap['STATUS_ID'] = true;
	}

	// for bizproc
	if(!isset($arSelectMap['ASSIGNED_BY']))
	{
		$arSelectMap['ASSIGNED_BY'] = true;
	}

	// For preparing user html
	$arSelectMap['ASSIGNED_BY_LOGIN'] =
		$arSelectMap['ASSIGNED_BY_NAME'] =
		$arSelectMap['ASSIGNED_BY_LAST_NAME'] =
		$arSelectMap['ASSIGNED_BY_SECOND_NAME'] = true;

	if(isset($arSelectMap['SUM']))
	{
		$arSelectMap['OPPORTUNITY'] =
			$arSelectMap['CURRENCY_ID'] = true;
	}

	if(isset($arSelectMap['FULL_ADDRESS']))
	{
		$arSelectMap['ADDRESS'] =
			$arSelectMap['ADDRESS_2'] =
			$arSelectMap['ADDRESS_CITY'] =
			$arSelectMap['ADDRESS_POSTAL_CODE'] =
			$arSelectMap['ADDRESS_POSTAL_CODE'] =
			$arSelectMap['ADDRESS_REGION'] =
			$arSelectMap['ADDRESS_PROVINCE'] =
			$arSelectMap['ADDRESS_COUNTRY'] = true;
	}

	// ID must present in select
	if(!isset($arSelectMap['ID']))
	{
		$arSelectMap['ID'] = true;
	}
}


if ($isInExportMode)
{
	if(!in_array('PRODUCT_ID', $arSelectedHeaders))
	{
		$arSelectedHeaders[] = 'PRODUCT_ID';
	}

	CCrmComponentHelper::PrepareExportFieldsList(
		$arSelectedHeaders,
		array(
			'LEAD_SUMMARY' => array(
				'TITLE',
				'SOURCE_ID'
			),
			'LEAD_FORMATTED_NAME' => array(
				'NAME',
				'SECOND_NAME',
				'LAST_NAME'
			),
			'SUM' => array(
				'OPPORTUNITY',
				'CURRENCY_ID'
			),
			'ACTIVITY_ID' => array()
		)
	);

	if(!in_array('ID', $arSelectedHeaders))
	{
		$arSelectedHeaders[] = 'ID';
	}

	$arResult['SELECTED_HEADERS'] = $arSelectedHeaders;
}

$nTopCount = false;
if ($arResult['GADGET'] == 'Y')
{
	$nTopCount = $arParams['LEAD_COUNT'];
}

if($nTopCount > 0)
{
	$arNavParams['nTopCount'] = $nTopCount;
}

if ($isInExportMode)
{
	$arFilter['PERMISSION'] = 'EXPORT';
}

// HACK: Make custom sort for ASSIGNED_BY field
$arSort = $arResult['SORT'];
if(isset($arSort['assigned_by']))
{
	$assignedBySort = $arSort['assigned_by'];
	$arSort['assigned_by_last_name'] = $assignedBySort;
	$arSort['assigned_by_name'] = $assignedBySort;
	$arSort['assigned_by_login'] = $assignedBySort;
	unset($arSort['assigned_by']);
}

$arOptions = array('FIELD_OPTIONS' => array('ADDITIONAL_FIELDS' => array()));
if(isset($arSelectMap['ACTIVITY_ID']))
{
	$arOptions['FIELD_OPTIONS']['ADDITIONAL_FIELDS'][] = 'ACTIVITY';
}
if(isset($arSort['status_sort']))
{
	$arOptions['FIELD_OPTIONS']['ADDITIONAL_FIELDS'][] = 'STATUS_SORT';
}

$arSelect = array_unique(array_keys($arSelectMap), SORT_STRING);

if($arSort['date_create'])
{
	$arSort['id'] = $arSort['date_create'];
	unset($arSort['date_create']);
}

$arResult['LEAD'] = array();
$arResult['LEAD_ID'] = array();
$arResult['LEAD_UF'] = array();

if(isset($arSort['nearest_activity']))
{
	$navDbResult = CCrmActivity::GetEntityList(
		CCrmOwnerType::Lead,
		$userID,
		$arSort['nearest_activity'],
		$arFilter,
		(!$isInExportMode ? $arNavParams : false)
	);

	$arResult['ROWS_COUNT'] = $navDbResult->SelectedRowsCount();
	$arResult['DB_LIST'] = $navDbResult;
	$arResult['DB_FILTER'] = $arFilter;

	while($arLead = $navDbResult->Fetch())
	{
		$arResult['LEAD'][$arLead['ID']] = $arLead;
		$arResult['LEAD_ID'][$arLead['ID']] = $arLead['ID'];
		$arResult['LEAD_UF'][$arLead['ID']] = array();
	}

	$arFilter['@ID'] = array_keys($arResult['LEAD']);
	$dbResult = CCrmLead::GetListEx($arSort, $arFilter, false, false, $arSelect, $arOptions);

	while($arLead = $dbResult->GetNext())
	{
		$arResult['LEAD'][$arLead['ID']] = $arLead;
	}
}
else
{
	$addressSort = array();
	foreach($arSort as $k => $v)
	{
		if(strncmp($k, 'address', 7) === 0)
		{
			$addressSort[strtoupper($k)] = $v;
		}
	}

	if(!empty($addressSort))
	{
		$navDbResult = \Bitrix\Crm\LeadAddress::getEntityList(
			\Bitrix\Crm\EntityAddress::Primary,
			$addressSort,
			$arFilter,
			(!$isInExportMode ? $arNavParams : false)
		);

		$arResult['ROWS_COUNT'] = $navDbResult->SelectedRowsCount();
		$arResult['DB_LIST'] = $navDbResult;
		$arResult['DB_FILTER'] = $arFilter;

		while($arLead = $navDbResult->Fetch())
		{
			$arResult['LEAD'][$arLead['ID']] = $arLead;
			$arResult['LEAD_ID'][$arLead['ID']] = $arLead['ID'];
			$arResult['LEAD_UF'][$arLead['ID']] = array();
		}

		$arFilter['@ID'] = array_keys($arResult['LEAD']);
		$arSort['ID'] = array_shift(array_slice($addressSort, 0, 1));
		$dbResult = CCrmLead::GetListEx($arSort, $arFilter, false, false, $arSelect, $arOptions);

		while($arLead = $dbResult->GetNext())
		{
			$arResult['LEAD'][$arLead['ID']] = $arLead;
		}
	}
	else
	{
		$dbResult = CCrmLead::GetListEx($arSort, $arFilter, false, (!$isInExportMode ? $arNavParams : false), $arSelect, $arOptions);

		$arResult['ROWS_COUNT'] = $dbResult->SelectedRowsCount();
		$arResult['DB_LIST'] = $dbResult;
		$arResult['DB_FILTER'] = $arFilter;

		while($arLead = $dbResult->GetNext())
		{
			$arResult['LEAD'][$arLead['ID']] = $arLead;
			$arResult['LEAD_ID'][$arLead['ID']] = $arLead['ID'];
			$arResult['LEAD_UF'][$arLead['ID']] = array();
		}
	}
}

$now = time() + CTimeZone::GetOffset();
foreach($arResult['LEAD'] as &$arLead)
{
	if (!empty($arLead['WEB']) && strpos($arLead['WEB'], '://') === false)
		$arLead['WEB'] = 'http://'.$arLead['WEB'];

	$currencyID =  isset($arLead['CURRENCY_ID']) ? $arLead['CURRENCY_ID'] : CCrmCurrency::GetBaseCurrencyID();
	$arLead['FORMATTED_OPPORTUNITY'] = CCrmCurrency::MoneyToString($arLead['~OPPORTUNITY'], $currencyID);

	$statusID = isset($arLead['STATUS_ID']) ? $arLead['STATUS_ID'] : '';
	$arLead['LEAD_STATUS_NAME'] = isset($arResult['STATUS_LIST'][$statusID]) ? $arResult['STATUS_LIST'][$statusID] : $statusID;

	$sourceID = isset($arLead['SOURCE_ID']) ? $arLead['SOURCE_ID'] : '';
	$arLead['LEAD_SOURCE_NAME'] = isset($arResult['SOURCE_LIST'][$sourceID]) ? $arResult['SOURCE_LIST'][$sourceID] : $sourceID;

	$arLead['DELETE'] = $isAdmin || ($arLead['STATUS_ID'] != 'CONVERTED' && !$arResult['INTERNAL']);
	$arLead['EDIT'] =  !$arResult['INTERNAL'] || ($arLead['STATUS_ID'] != 'CONVERTED');

	$arLead['PATH_TO_LEAD_SHOW'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_SHOW'],
		array(
			'lead_id' => $arLead['ID']
		)
	);
	$arLead['PATH_TO_LEAD_EDIT'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_EDIT'],
		array(
			'lead_id' => $arLead['ID']
		)
	);
	$arLead['PATH_TO_LEAD_COPY'] =  CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_EDIT'],
		array(
			'lead_id' => $arLead['ID']
		)),
		array('copy' => 1)
	);
	$arLead['PATH_TO_LEAD_CONVERT'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_CONVERT'],
		array(
			'lead_id' => $arLead['ID']
		)
	);
	$arLead['PATH_TO_LEAD_DELETE'] =  CHTTP::urlAddParams(
		$bInternal ? $APPLICATION->GetCurPage() : $arParams['PATH_TO_LEAD_LIST'],
		array('action_'.$arResult['GRID_ID'] => 'delete', 'ID' => $arLead['ID'], 'sessid' => $arResult['SESSION_ID'])
	);

	$arLead['PATH_TO_USER_PROFILE'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_PROFILE'],
		array(
			'user_id' => $arLead['ASSIGNED_BY']
		)
	);
	$arLead['PATH_TO_USER_BP'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_BP'],
		array(
			'user_id' => $userID
		)
	);

	$arLead['PATH_TO_USER_CREATOR'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_PROFILE'],
		array(
			'user_id' => $arLead['CREATED_BY']
		)
	);

	$arLead['PATH_TO_USER_MODIFIER'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_PROFILE'],
		array(
			'user_id' => $arLead['MODIFY_BY']
		)
	);

	$arLead['CREATED_BY_FORMATTED_NAME'] = CUser::FormatName(
		$arParams['NAME_TEMPLATE'],
		array(
			'LOGIN' => $arLead['~CREATED_BY_LOGIN'],
			'NAME' => $arLead['~CREATED_BY_NAME'],
			'SECOND_NAME' => $arLead['~CREATED_BY_SECOND_NAME'],
			'LAST_NAME' => $arLead['~CREATED_BY_LAST_NAME']
		),
		true, false
	);

	$arLead['MODIFY_BY_FORMATTED_NAME'] = CUser::FormatName(
		$arParams['NAME_TEMPLATE'],
		array(
			'LOGIN' => $arLead['~MODIFY_BY_LOGIN'],
			'NAME' => $arLead['~MODIFY_BY_NAME'],
			'SECOND_NAME' => $arLead['~MODIFY_BY_SECOND_NAME'],
			'LAST_NAME' => $arLead['~MODIFY_BY_LAST_NAME']
		),
		true, false
	);

	$sourceID = isset($arLead['~SOURCE_ID']) ? $arLead['~SOURCE_ID'] : '';
	$arLead['LEAD_SOURCE_NAME'] = $sourceID !== '' ? (isset($arResult['SOURCE_LIST'][$sourceID]) ? $arResult['SOURCE_LIST'][$sourceID] : $sourceID) : '';
	$arLead['~LEAD_SOURCE_NAME'] = htmlspecialcharsback($arLead['~LEAD_SOURCE_NAME']);

	$arLead['~LEAD_FORMATTED_NAME'] = CUser::FormatName(
		\Bitrix\Crm\Format\PersonNameFormatter::getFormat(),
		array(
			'LOGIN' => '',
			'NAME' => isset($arLead['~NAME']) ? $arLead['~NAME'] : '',
			'SECOND_NAME' => isset($arLead['~SECOND_NAME']) ? $arLead['~SECOND_NAME'] : '',
			'LAST_NAME' => isset($arLead['~LAST_NAME']) ? $arLead['~LAST_NAME'] : ''
		),
		false, false
	);

	$arLead['LEAD_FORMATTED_NAME'] = htmlspecialcharsbx($arLead['~LEAD_FORMATTED_NAME']);

	if(isset($arLead['~ACTIVITY_TIME']))
	{
		$time = MakeTimeStamp($arLead['ACTIVITY_TIME']);
		$arLead['~ACTIVITY_EXPIRED'] = $time <= $now;
		$arLead['~ACTIVITY_IS_CURRENT_DAY'] = $arLead['~ACTIVITY_EXPIRED'] || CCrmActivity::IsCurrentDay($time);
	}

	if ($arResult['ENABLE_TASK'])
	{
		$arLead['PATH_TO_TASK_EDIT'] = CHTTP::urlAddParams(
			CComponentEngine::MakePathFromTemplate(COption::GetOptionString('tasks', 'paths_task_user_edit', ''),
				array(
					'task_id' => 0,
					'user_id' => $userID
				)
			),
			array(
				'UF_CRM_TASK' => 'L_'.$arLead['ID'],
				'TITLE' => urlencode(GetMessage('CRM_TASK_TITLE_PREFIX')),
				'TAGS' => urlencode(GetMessage('CRM_TASK_TAG')),
				'back_url' => urlencode($arParams['PATH_TO_LEAD_LIST'])
			)
		);
	}

	if (IsModuleInstalled('sale'))
	{
		$arLead['PATH_TO_QUOTE_ADD'] =
			CHTTP::urlAddParams(CComponentEngine::makePathFromTemplate(
				$arParams['PATH_TO_QUOTE_EDIT'], array('quote_id' => 0)),
				array('lead_id' => $arLead['ID'])
			);
	}

	if ($arResult['ENABLE_BIZPROC'])
	{
		$arLead['BIZPROC_STATUS'] = '';
		$arLead['BIZPROC_STATUS_HINT'] = '';

		$arDocumentStates = CBPDocument::GetDocumentStates(
			array('crm', 'CCrmDocumentLead', 'LEAD'),
			array('crm', 'CCrmDocumentLead', 'LEAD_'.$arLead['ID'])
		);

		$arLead['PATH_TO_BIZPROC_LIST'] =  CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_SHOW'],
			array(
				'lead_id' => $arLead['ID']
			)),
			array('CRM_LEAD_SHOW_V12_active_tab' => 'tab_bizproc')
		);

		$totalTaskQty = 0;
		$docStatesQty = count($arDocumentStates);
		if($docStatesQty === 1)
		{
			$arDocState = $arDocumentStates[array_shift(array_keys($arDocumentStates))];

			$docTemplateID = $arDocState['TEMPLATE_ID'];
			$paramName = "BIZPROC_{$docTemplateID}";
			$docTtl = isset($arDocState['STATE_TITLE']) ? $arDocState['STATE_TITLE'] : '';
			$docName = isset($arDocState['STATE_NAME']) ? $arDocState['STATE_NAME'] : '';
			$docTemplateName = isset($arDocState['TEMPLATE_NAME']) ? $arDocState['TEMPLATE_NAME'] : '';

			if($isInExportMode)
			{
				$arLead[$paramName] = $docTtl;
			}
			else
			{
				$arLead[$paramName] = '<a href="'.htmlspecialcharsbx($arLead['PATH_TO_BIZPROC_LIST']).'">'.htmlspecialcharsbx($docTtl).'</a>';

				$docID = $arDocState['ID'];
				$taskQty = CCrmBizProcHelper::GetUserWorkflowTaskCount(array($docID), $userID);
				if($taskQty > 0)
				{
					$totalTaskQty += $taskQty;
				}

				$arLead['BIZPROC_STATUS'] = $taskQty > 0 ? 'attention' : 'inprogress';
				$arLead['BIZPROC_STATUS_HINT'] =
					'<div class=\'bizproc-item-title\'>'.
						htmlspecialcharsbx($docTemplateName !== '' ? $docTemplateName : GetMessage('CRM_BPLIST')).
						': <span class=\'bizproc-item-title bizproc-state-title\'><a href=\''.$arLead['PATH_TO_BIZPROC_LIST'].'\'>'.
						htmlspecialcharsbx($docTtl !== '' ? $docTtl : $docName).'</a></span></div>';
			}
		}
		elseif($docStatesQty > 1)
		{
			foreach ($arDocumentStates as &$arDocState)
			{
				$docTemplateID = $arDocState['TEMPLATE_ID'];
				$paramName = "BIZPROC_{$docTemplateID}";
				$docTtl = isset($arDocState['STATE_TITLE']) ? $arDocState['STATE_TITLE'] : '';

				if($isInExportMode)
				{
					$arLead[$paramName] = $docTtl;
				}
				else
				{
					$arLead[$paramName] = '<a href="'.htmlspecialcharsbx($arLead['PATH_TO_BIZPROC_LIST']).'">'.htmlspecialcharsbx($docTtl).'</a>';

					$docID = $arDocState['ID'];
					//TODO: wait for bizproc bugs will be fixed and replace serial call of CCrmBizProcHelper::GetUserWorkflowTaskCount on single call
					$taskQty = CCrmBizProcHelper::GetUserWorkflowTaskCount(array($docID), $userID);
					if($taskQty === 0)
					{
						continue;
					}

					if ($arLead['BIZPROC_STATUS'] !== 'attention')
					{
						$arLead['BIZPROC_STATUS'] = 'attention';
					}

					$totalTaskQty += $taskQty;
					if($totalTaskQty > 5)
					{
						break;
					}
				}
			}
			unset($arDocState);

			if(!$isInExportMode)
			{
				$arLead['BIZPROC_STATUS_HINT'] =
					'<span class=\'bizproc-item-title\'>'.GetMessage('CRM_BP_R_P').': <a href=\''.$arLead['PATH_TO_BIZPROC_LIST'].'\' title=\''.GetMessage('CRM_BP_R_P_TITLE').'\'>'.$docStatesQty.'</a></span>'.
					($totalTaskQty === 0
						? ''
						: '<br /><span class=\'bizproc-item-title\'>'.GetMessage('CRM_TASKS').': <a href=\''.$arLead['PATH_TO_USER_BP'].'\' title=\''.GetMessage('CRM_TASKS_TITLE').'\'>'.$totalTaskQty.($totalTaskQty > 5 ? '+' : '').'</a></span>');
			}
		}
	}

	$arLead['ASSIGNED_BY_ID'] = $arLead['~ASSIGNED_BY_ID'] = intval($arLead['ASSIGNED_BY']);
	$arLead['ASSIGNED_BY'] = CUser::FormatName(
		$arParams['NAME_TEMPLATE'],
		array(
			'LOGIN' => $arLead['ASSIGNED_BY_LOGIN'],
			'NAME' => $arLead['ASSIGNED_BY_NAME'],
			'LAST_NAME' => $arLead['ASSIGNED_BY_LAST_NAME'],
			'SECOND_NAME' => $arLead['ASSIGNED_BY_SECOND_NAME']
		),
		true, false
	);

	if(isset($arSelectMap['FULL_ADDRESS']))
	{
		$arLead['FULL_ADDRESS'] = LeadAddressFormatter::format(
			$arLead,
			array('SEPARATOR' => AddressSeparator::HtmlLineBreak, 'NL2BR' => true)
		);
	}
}
unset($arLead);

$CCrmUserType->ListAddEnumFieldsValue(
	$arResult,
	$arResult['LEAD'],
	$arResult['LEAD_UF'],
	($sExportType !== '' ? ', ' : '<br />'),
	$sExportType !== '',
	array(
		'FILE_URL_TEMPLATE' =>
			'/bitrix/components/bitrix/crm.lead.show/show_file.php?ownerId=#owner_id#&fieldName=#field_name#&fileId=#file_id#'
	)
);

if (isset($arResult['LEAD_ID']) && !empty($arResult['LEAD_ID']))
{
	// try to load product rows
	$arProductRows = CCrmLead::LoadProductRows(array_keys($arResult['LEAD_ID']));
	foreach($arProductRows as $arProductRow)
	{
		$ownerID = $arProductRow['OWNER_ID'];
		if(!isset($arResult['LEAD'][$ownerID]))
		{
			continue;
		}

		$arEntity = &$arResult['LEAD'][$ownerID];
		if(!isset($arEntity['PRODUCT_ROWS']))
		{
			$arEntity['PRODUCT_ROWS'] = array();
		}
		$arEntity['PRODUCT_ROWS'][] = $arProductRow;
	}

	// adding crm multi field to result array
	$arFmList = array();
	$res = CCrmFieldMulti::GetList(array('ID' => 'asc'), array('ENTITY_ID' => 'LEAD', 'ELEMENT_ID' => $arResult['LEAD_ID']));
	while($ar = $res->Fetch())
	{
		if ($sExportType == '')
			$arFmList[$ar['ELEMENT_ID']][$ar['COMPLEX_ID']][] = CCrmFieldMulti::GetTemplateByComplex($ar['COMPLEX_ID'], $ar['VALUE']);
		else
			$arFmList[$ar['ELEMENT_ID']][$ar['COMPLEX_ID']][] = $ar['VALUE'];
		$arResult['LEAD'][$ar['ELEMENT_ID']]['~'.$ar['COMPLEX_ID']][] = $ar['VALUE'];
	}

	foreach ($arFmList as $elementId => $arFM)
	{
		foreach ($arFM as $complexId => $arComplexName)
			$arResult['LEAD'][$elementId][$complexId] = implode(', ', $arComplexName);
	}

	// checkig access for operation
	$arLeadAttr = CCrmPerms::GetEntityAttr('LEAD', $arResult['LEAD_ID']);
	foreach ($arResult['LEAD_ID'] as $iLeadId)
	{
		if ($arResult['LEAD'][$iLeadId]['EDIT'])
			$arResult['LEAD'][$iLeadId]['EDIT'] = $CCrmPerms->CheckEnityAccess('LEAD', 'WRITE', $arLeadAttr[$iLeadId]);
		if ($arResult['LEAD'][$iLeadId]['DELETE'])
			$arResult['LEAD'][$iLeadId]['DELETE'] = $CCrmPerms->CheckEnityAccess('LEAD', 'DELETE', $arLeadAttr[$iLeadId]);

		$arResult['LEAD'][$iLeadId]['BIZPROC_LIST'] = array();
		foreach ($arBPData as $arBP)
		{
			if (!CBPDocument::CanUserOperateDocument(
				CBPCanUserOperateOperation::StartWorkflow,
				$userID,
				array('crm', 'CCrmDocumentLead', 'LEAD_'.$arResult['LEAD'][$iLeadId]['ID']),
				array(
					'UserGroups' => $CCrmBizProc->arCurrentUserGroups,
					'DocumentStates' => $arDocumentStates,
					'WorkflowTemplateId' => $arBP['ID'],
					'CreatedBy' => $arResult['LEAD'][$iLeadId]['~ASSIGNED_BY'],
					'UserIsAdmin' => $isAdmin,
					'CRMEntityAttr' =>  $arLeadAttr[$iLeadId]
				)
			))
			{
				continue;
			}

			$arBP['PATH_TO_BIZPROC_START'] = CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_SHOW'],
				array(
					'lead_id' => $arResult['LEAD'][$iLeadId]['ID']
				)),
				array(
					'workflow_template_id' => $arBP['ID'], 'bizproc_start' => 1,  'sessid' => $arResult['SESSION_ID'],
					'CRM_LEAD_SHOW_V12_active_tab' => 'tab_bizproc', 'back_url' => $arParams['PATH_TO_LEAD_LIST'])
			);
			$arResult['LEAD'][$iLeadId]['BIZPROC_LIST'][] = $arBP;
		}
	}
}

if ($sExportType == '')
{
	$arResult['NEED_FOR_REBUILD_DUP_INDEX'] = false;
	$arResult['NEED_FOR_REBUILD_LEAD_ATTRS'] = false;

	if(!$bInternal && CCrmPerms::IsAdmin())
	{
		if(COption::GetOptionString('crm', '~CRM_REBUILD_LEAD_DUP_INDEX', 'N') === 'Y')
		{
			$arResult['NEED_FOR_REBUILD_DUP_INDEX'] = true;
		}

		if(COption::GetOptionString('crm', '~CRM_REBUILD_LEAD_ATTR', 'N') === 'Y')
		{
			$arResult['PATH_TO_PRM_LIST'] = CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_perm_list'));
			$arResult['NEED_FOR_REBUILD_LEAD_ATTRS'] = true;
		}
	}

	$this->IncludeComponentTemplate();
	include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.lead/include/nav.php');
	return $arResult['ROWS_COUNT'];
}
else
{
	// need for replace id to value, not support in import
	//foreach ($arResult['LEAD'] as $_k => $v)
	//{
	//	if (isset($arResult['LEAD_UF'][$_k]))
	//		$arResult['LEAD'][$_k] = $arResult['LEAD_UF'][$_k] + $arResult['LEAD'][$_k];
	//}

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
		Header('Content-Disposition: attachment;filename=leads.csv');
	}
	elseif($sExportType === 'excel')
	{
		Header('Content-Type: application/vnd.ms-excel');
		Header('Content-Disposition: attachment;filename=leads.xls');
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
