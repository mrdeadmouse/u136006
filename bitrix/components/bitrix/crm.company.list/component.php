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

global $USER_FIELD_MANAGER, $USER, $APPLICATION, $DB;

$CCrmPerms = CCrmPerms::GetCurrentUserPermissions();
if ($CCrmPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE, 'READ'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

use Bitrix\Crm\EntityAddress;
use Bitrix\Crm\Format\AddressSeparator;
use Bitrix\Crm\Format\CompanyAddressFormatter;

$CCrmCompany = new CCrmCompany();
$CCrmBizProc = new CCrmBizProc('COMPANY');

$userID = CCrmSecurityHelper::GetCurrentUserID();
$isAdmin = CCrmPerms::IsAdmin();

$arResult['CURRENT_USER_ID'] = CCrmSecurityHelper::GetCurrentUserID();
$arParams['PATH_TO_COMPANY_LIST'] = CrmCheckPath('PATH_TO_COMPANY_LIST', $arParams['PATH_TO_COMPANY_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_COMPANY_SHOW'] = CrmCheckPath('PATH_TO_COMPANY_SHOW', $arParams['PATH_TO_COMPANY_SHOW'], $APPLICATION->GetCurPage().'?company_id=#company_id#&show');
$arParams['PATH_TO_COMPANY_EDIT'] = CrmCheckPath('PATH_TO_COMPANY_EDIT', $arParams['PATH_TO_COMPANY_EDIT'], $APPLICATION->GetCurPage().'?company_id=#company_id#&edit');
$arParams['PATH_TO_DEAL_EDIT']    = CrmCheckPath('PATH_TO_DEAL_EDIT', $arParams['PATH_TO_DEAL_EDIT'], $APPLICATION->GetCurPage().'?deal_id=#deal_id#&edit');
$arParams['PATH_TO_QUOTE_EDIT'] = CrmCheckPath('PATH_TO_QUOTE_EDIT', $arParams['PATH_TO_QUOTE_EDIT'], $APPLICATION->GetCurPage().'?quote_id=#quote_id#&edit');
$arParams['PATH_TO_INVOICE_EDIT'] = CrmCheckPath('PATH_TO_INVOICE_EDIT', $arParams['PATH_TO_INVOICE_EDIT'], $APPLICATION->GetCurPage().'?invoice_id=#invoice_id#&edit');
$arParams['PATH_TO_CONTACT_EDIT'] = CrmCheckPath('PATH_TO_CONTACT_EDIT', $arParams['PATH_TO_CONTACT_EDIT'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&edit');
$arParams['PATH_TO_USER_BP'] = CrmCheckPath('PATH_TO_USER_BP', $arParams['PATH_TO_USER_BP'], '/company/personal/bizproc/');
$arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath('PATH_TO_USER_PROFILE', $arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

if(!isset($arParams['INTERNAL_CONTEXT']))
{
	$arParams['INTERNAL_CONTEXT'] = array();
}

$arResult['IS_AJAX_CALL'] = isset($_REQUEST['bxajaxid']) || isset($_REQUEST['AJAX_CALL']);
$arResult['SESSION_ID'] = bitrix_sessid();

CUtil::InitJSCore(array('ajax', 'tooltip'));

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
if (!empty($sExportType) && $CCrmCompany->cPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE, 'EXPORT'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$isInExportMode = $sExportType !== '';

$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmCompany::$sUFEntityID);
$CCrmFieldMulti = new CCrmFieldMulti();

$arResult['GRID_ID'] = 'CRM_COMPANY_LIST_V12'.($bInternal && !empty($arParams['GRID_ID_SUFFIX']) ? '_'.$arParams['GRID_ID_SUFFIX'] : '');
$arResult['COMPANY_TYPE_LIST'] = CCrmStatus::GetStatusListEx('COMPANY_TYPE');
$arResult['EMPLOYEES_LIST'] = CCrmStatus::GetStatusListEx('EMPLOYEES');
$arResult['INDUSTRY_LIST'] = CCrmStatus::GetStatusListEx('INDUSTRY');
$arResult['FILTER'] = array();
$arResult['FILTER2LOGIC'] = array();
$arResult['FILTER_PRESETS'] = array();

$arResult['AJAX_MODE'] = isset($arParams['AJAX_MODE']) ? $arParams['AJAX_MODE'] : ($arResult['INTERNAL'] ? 'N' : 'Y');
$arResult['AJAX_ID'] = isset($arParams['AJAX_ID']) ? $arParams['AJAX_ID'] : '';
$arResult['AJAX_OPTION_JUMP'] = isset($arParams['AJAX_OPTION_JUMP']) ? $arParams['AJAX_OPTION_JUMP'] : 'N';
$arResult['AJAX_OPTION_HISTORY'] = isset($arParams['AJAX_OPTION_HISTORY']) ? $arParams['AJAX_OPTION_HISTORY'] : 'N';

$addressLabels = EntityAddress::getShortLabels();
$regAddressLabels = EntityAddress::getShortLabels(EntityAddress::Registered);

if (!$bInternal)
{
	$arResult['FILTER2LOGIC'] = array('TITLE', 'BANKING_DETAILS', 'COMMENTS');

	$originatorID = isset($_REQUEST['ORIGINATOR_ID']) ? $_REQUEST['ORIGINATOR_ID'] : '';
	ob_start();
	?>
	<select name="ORIGINATOR_ID">
		<option value=""><?= GetMessage("CRM_COLUMN_ALL") ?></option>
		<option value="__INTERNAL" <?= $originatorID === '__INTERNAL' ? 'selected' : ''?>><?= GetMessage("CRM_INTERNAL") ?></option>
		<?
		$dbOriginatorsList = CCrmExternalSale::GetList(array("NAME" => "ASC", "SERVER" => "ASC"), array("ACTIVE" => "Y"));
		while ($arOriginator = $dbOriginatorsList->GetNext())
		{
			?><option value="<?= $arOriginator["ID"] ?>"<?= ($originatorID === $arOriginator["ID"]) ? " selected" : "" ?>><?= empty($arOriginator["NAME"]) ? $arOriginator["SERVER"] : $arOriginator["NAME"] ?></option><?
		}
		?>
	</select>
	<?
	$sValOriginator = ob_get_contents();
	ob_end_clean();

	$arResult['FILTER'] = array(
		array('id' => 'FIND', 'name' => GetMessage('CRM_COLUMN_FIND'), 'default' => 'Y', 'type' => 'quick', 'items' => array(
			'title' => GetMessage('CRM_COLUMN_TITLE'),
			'email' => GetMessage('CRM_COLUMN_EMAIL'),
			'phone' => GetMessage('CRM_COLUMN_PHONE'))
		),
		array('id' => 'ID', 'name' => GetMessage('CRM_COLUMN_ID')),
		array('id' => 'TITLE', 'name' => GetMessage('CRM_COLUMN_TITLE')),
		array('id' => 'PHONE', 'name' => GetMessage('CRM_COLUMN_PHONE')),
		array('id' => 'EMAIL', 'name' => GetMessage('CRM_COLUMN_EMAIL')),
		array('id' => 'WEB', 'name' => GetMessage('CRM_COLUMN_WEB')),
		array('id' => 'IM', 'name' => GetMessage('CRM_COLUMN_MESSENGER')),

		array('id' => 'ADDRESS', 'name' => $addressLabels['ADDRESS']),
		array('id' => 'ADDRESS_2', 'name' => $addressLabels['ADDRESS_2']),
		array('id' => 'ADDRESS_CITY', 'name' => $addressLabels['CITY']),
		array('id' => 'ADDRESS_REGION', 'name' => $addressLabels['REGION']),
		array('id' => 'ADDRESS_PROVINCE', 'name' => $addressLabels['PROVINCE']),
		array('id' => 'ADDRESS_POSTAL_CODE', 'name' => $addressLabels['POSTAL_CODE']),
		array('id' => 'ADDRESS_COUNTRY', 'name' => $addressLabels['COUNTRY']),

		array('id' => 'ADDRESS_LEGAL', 'name' => $regAddressLabels['ADDRESS']),
		array('id' => 'REG_ADDRESS_2', 'name' => $regAddressLabels['ADDRESS_2']),
		array('id' => 'REG_ADDRESS_CITY', 'name' => $regAddressLabels['CITY']),
		array('id' => 'REG_ADDRESS_REGION', 'name' => $regAddressLabels['REGION']),
		array('id' => 'REG_ADDRESS_PROVINCE', 'name' => $regAddressLabels['PROVINCE']),
		array('id' => 'REG_ADDRESS_POSTAL_CODE', 'name' => $regAddressLabels['POSTAL_CODE']),
		array('id' => 'REG_ADDRESS_COUNTRY', 'name' => $regAddressLabels['COUNTRY']),

		array('id' => 'BANKING_DETAILS', 'name' => GetMessage('CRM_COLUMN_BANKING_DETAILS')),
		array('id' => 'COMPANY_TYPE', 'params' => array('multiple' => 'Y'), 'name' => GetMessage('CRM_COLUMN_COMPANY_TYPE'), 'default' => 'Y', 'type' => 'list', 'items' => CCrmStatus::GetStatusList('COMPANY_TYPE')),
		array('id' => 'INDUSTRY', 'params' => array('multiple' => 'Y'), 'name' => GetMessage('CRM_COLUMN_INDUSTRY'), 'default' => 'Y', 'type' => 'list', 'items' => CCrmStatus::GetStatusList('INDUSTRY')),
		array('id' => 'REVENUE', 'name' => GetMessage('CRM_COLUMN_REVENUE')),
		array('id' => 'CURRENCY_ID', 'params' => array('multiple' => 'Y'), 'name' => GetMessage('CRM_COLUMN_CURRENCY_ID'), 'type' => 'list', 'items' => CCrmCurrencyHelper::PrepareListItems()),
		array('id' => 'EMPLOYEES', 'params' => array('multiple' => 'Y'), 'name' => GetMessage('CRM_COLUMN_EMPLOYEES'), 'default' => 'Y', 'type' => 'list', 'items' => CCrmStatus::GetStatusList('EMPLOYEES')),
		array('id' => 'COMMENTS', 'name' => GetMessage('CRM_COLUMN_COMMENTS')),
		array('id' => 'DATE_CREATE', 'name' => GetMessage('CRM_COLUMN_DATE_CREATE'), 'type' => 'date'),
		array('id' => 'CREATED_BY_ID',  'name' => GetMessage('CRM_COLUMN_CREATED_BY'), 'default' => false, 'enable_settings' => false, 'type' => 'user'),
		array('id' => 'DATE_MODIFY', 'name' => GetMessage('CRM_COLUMN_DATE_MODIFY'), 'default' => 'Y', 'type' => 'date'),
		array('id' => 'MODIFY_BY_ID',  'name' => GetMessage('CRM_COLUMN_MODIFY_BY'), 'default' => false, 'enable_settings' => true, 'type' => 'user'),
		array('id' => 'ASSIGNED_BY_ID',  'name' => GetMessage('CRM_COLUMN_ASSIGNED_BY'), 'default' => false, 'enable_settings' => true, 'type' => 'user'),
		array('id' => 'ORIGINATOR_ID', 'name' => GetMessage('CRM_COLUMN_BINDING'), 'type' => 'custom', 'value' => $sValOriginator),
	);

	$CCrmUserType->ListAddFilterFields($arResult['FILTER'], $arResult['FILTER2LOGIC'], $arResult['GRID_ID']);

	$currentUserID = $arResult['CURRENT_USER_ID'];
	$currentUserName = CCrmViewHelper::GetFormattedUserName($currentUserID, $arParams['NAME_TEMPLATE']);
	$arResult['FILTER_PRESETS'] = array(
		'filter_my' => array('name' => GetMessage('CRM_PRESET_MY'), 'fields' => array('ASSIGNED_BY_ID_name' => $currentUserName, 'ASSIGNED_BY_ID' => $currentUserID)),
		//'filter_change_today' => array('name' => GetMessage('CRM_PRESET_CHANGE_TODAY'), 'fields' => array('DATE_MODIFY_datesel' => 'today')),
		//'filter_change_yesterday' => array('name' => GetMessage('CRM_PRESET_CHANGE_YESTERDAY'), 'fields' => array('DATE_MODIFY_datesel' => 'yesterday')),
		'filter_change_my' => array('name' => GetMessage('CRM_PRESET_CHANGE_MY'), 'fields' => array('MODIFY_BY_ID_name' => $currentUserName, 'MODIFY_BY_ID' => $currentUserID))
	);
}

// Headers initialization -->
$arResult['HEADERS'] = array(
	array('id' => 'ID', 'name' => GetMessage('CRM_COLUMN_ID'), 'sort' => 'id', 'default' => false, 'editable' => false, 'type' => 'int', 'class' => 'minimal'),
	array('id' => 'COMPANY_SUMMARY', 'name' => GetMessage('CRM_COLUMN_COMPANY'), 'sort' => 'title', 'default' => true, 'editable' => false, 'enableDefaultSort' => false)
);

// Dont display activities in INTERNAL mode.
if(!$bInternal)
{
	$arResult['HEADERS'][] = array('id' => 'ACTIVITY_ID', 'name' => GetMessage('CRM_COLUMN_ACTIVITY'), 'sort' => 'nearest_activity', 'default' => true);
}

$arResult['HEADERS'] = array_merge(
	$arResult['HEADERS'],
	array(
		array('id' => 'LOGO', 'name' => GetMessage('CRM_COLUMN_LOGO'), 'sort' => false, 'default' => false, 'editable' => false),
		array('id' => 'TITLE', 'name' => GetMessage('CRM_COLUMN_TITLE'), 'sort' => 'title', 'default' => false, 'editable' => true),
		array('id' => 'COMPANY_TYPE', 'name' => GetMessage('CRM_COLUMN_COMPANY_TYPE'), 'sort' => 'company_type', 'default' => false, 'editable' => array('items' => CCrmStatus::GetStatusList('COMPANY_TYPE')), 'type' => 'list'),
		array('id' => 'EMPLOYEES', 'name' => GetMessage('CRM_COLUMN_EMPLOYEES'), 'sort' => 'employees', 'default' => false, 'editable' => array('items' => CCrmStatus::GetStatusList('EMPLOYEES')), 'type' => 'list')
	)
);

$CCrmFieldMulti->PrepareListHeaders($arResult['HEADERS']);
if($isInExportMode)
{
	$CCrmFieldMulti->ListAddHeaders($arResult['HEADERS']);
}

$arResult['HEADERS'] = array_merge($arResult['HEADERS'], array(
	array('id' => 'ASSIGNED_BY', 'name' => GetMessage('CRM_COLUMN_ASSIGNED_BY'), 'sort' => 'assigned_by', 'default' => true, 'editable' => false, 'class' => 'username'),

	array('id' => 'FULL_ADDRESS', 'name' => EntityAddress::getFullAddressLabel(), 'sort' => false, 'default' => false, 'editable' => false),
	array('id' => 'ADDRESS', 'name' => $addressLabels['ADDRESS'], 'sort' => 'address', 'default' => false, 'editable' => false),
	array('id' => 'ADDRESS_2', 'name' => $addressLabels['ADDRESS_2'], 'sort' => 'address_2', 'default' => false, 'editable' => false),
	array('id' => 'ADDRESS_CITY', 'name' => $addressLabels['CITY'], 'sort' => 'address_city', 'default' => false, 'editable' => false),
	array('id' => 'ADDRESS_REGION', 'name' => $addressLabels['REGION'], 'sort' => 'address_region', 'default' => false, 'editable' => false),
	array('id' => 'ADDRESS_PROVINCE', 'name' => $addressLabels['PROVINCE'], 'sort' => 'address_province', 'default' => false, 'editable' => false),
	array('id' => 'ADDRESS_POSTAL_CODE', 'name' => $addressLabels['POSTAL_CODE'], 'sort' => 'address_postal_code', 'default' => false, 'editable' => false),
	array('id' => 'ADDRESS_COUNTRY', 'name' => $addressLabels['COUNTRY'], 'sort' => 'address_country', 'default' => false, 'editable' => false),

	array('id' => 'FULL_REG_ADDRESS', 'name' => EntityAddress::getFullAddressLabel(EntityAddress::Registered), 'sort' => false, 'default' => false, 'editable' => false),
	//REG_ADDRESS = ADDRESS_LEGAL
	array('id' => 'ADDRESS_LEGAL', 'name' => $regAddressLabels['ADDRESS'], 'sort' => 'registered_address', 'default' => false, 'editable' => false),
	array('id' => 'REG_ADDRESS_2', 'name' => $regAddressLabels['ADDRESS_2'], 'sort' => 'registered_address_2', 'default' => false, 'editable' => false),
	array('id' => 'REG_ADDRESS_CITY', 'name' => $regAddressLabels['CITY'], 'sort' => 'registered_address_city', 'default' => false, 'editable' => false),
	array('id' => 'REG_ADDRESS_REGION', 'name' => $regAddressLabels['REGION'], 'sort' => 'registered_address_region', 'default' => false, 'editable' => false),
	array('id' => 'REG_ADDRESS_PROVINCE', 'name' => $regAddressLabels['PROVINCE'], 'sort' => 'registered_address_province', 'default' => false, 'editable' => false),
	array('id' => 'REG_ADDRESS_POSTAL_CODE', 'name' => $regAddressLabels['POSTAL_CODE'], 'sort' => 'registered_address_postal_code', 'default' => false, 'editable' => false),
	array('id' => 'REG_ADDRESS_COUNTRY', 'name' => $regAddressLabels['COUNTRY'], 'sort' => 'registered_address_country', 'default' => false, 'editable' => false),

	array('id' => 'BANKING_DETAILS', 'name' => GetMessage('CRM_COLUMN_BANKING_DETAILS'), 'sort' => false, 'default' => false, 'editable' => false),
	array('id' => 'INDUSTRY', 'name' => GetMessage('CRM_COLUMN_INDUSTRY'), 'sort' => 'industry', 'default' => false, 'editable' => array('items' => CCrmStatus::GetStatusList('INDUSTRY')), 'type' => 'list'),
	array('id' => 'REVENUE', 'name' => GetMessage('CRM_COLUMN_REVENUE'), 'sort' => 'revenue', 'default' => false, 'editable' => true),
	array('id' => 'CURRENCY_ID', 'name' => GetMessage('CRM_COLUMN_CURRENCY_ID'), 'sort' => 'currency_id', 'default' => false, 'editable' => array('items' => CCrmCurrencyHelper::PrepareListItems()), 'type' => 'list'),
	array('id' => 'COMMENTS', 'name' => GetMessage('CRM_COLUMN_COMMENTS'), 'sort' => false, 'default' => false, 'editable' => false),
	array('id' => 'CREATED_BY', 'name' => GetMessage('CRM_COLUMN_CREATED_BY'), 'sort' => 'created_by', 'default' => false, 'editable' => false, 'class' => 'username'),
	array('id' => 'DATE_CREATE', 'name' => GetMessage('CRM_COLUMN_DATE_CREATE'), 'sort' => 'date_create', 'default' => false, 'editable' => false, 'class' => 'date'),
	array('id' => 'MODIFY_BY', 'name' => GetMessage('CRM_COLUMN_MODIFY_BY'), 'sort' => 'modify_by', 'default' => false, 'editable' => false, 'class' => 'username'),
	array('id' => 'DATE_MODIFY', 'name' => GetMessage('CRM_COLUMN_DATE_MODIFY'), 'sort' => 'date_modify', 'default' => false, 'editable' => false, 'class' => 'date')
));
$CCrmUserType->ListAddHeaders($arResult['HEADERS']);
if (IsModuleInstalled('bizproc'))
{
	$arBPData = CBPDocument::GetWorkflowTemplatesForDocumentType(array('crm', 'CCrmDocumentCompany', 'COMPANY'));
	$arDocumentStates = CBPDocument::GetDocumentStates(
		array('crm', 'CCrmDocumentCompany', 'COMPANY'),
		null
	);
	foreach ($arBPData as $arBP)
	{
		if (!CBPDocument::CanUserOperateDocumentType(
			CBPCanUserOperateOperation::ViewWorkflow,
			$userID,
			array('crm', 'CCrmDocumentCompany', 'COMPANY'),
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

if (intval($arParams['COMPANY_COUNT']) <= 0)
	$arParams['COMPANY_COUNT'] = 20;

$arNavParams = array(
	'nPageSize' => $arParams['COMPANY_COUNT']
);

$arNavigation = CDBResult::GetNavParams($arNavParams);
$CGridOptions = new CCrmGridOptions($arResult['GRID_ID'], $arResult['FILTER_PRESETS']);
$arNavParams = $CGridOptions->GetNavParams($arNavParams);
$arNavParams['bShowAll'] = false;

$arFilter += $CGridOptions->GetFilter($arResult['FILTER']);

$USER_FIELD_MANAGER->AdminListAddFilter(CCrmCompany::$sUFEntityID, $arFilter);

// converts data from filter
if (isset($arFilter['FIND_list']) && !empty($arFilter['FIND']))
{
	$arFilter[strtoupper($arFilter['FIND_list'])] = $arFilter['FIND'];
	unset($arFilter['FIND_list'], $arFilter['FIND']);
}

CCrmEntityHelper::PrepareMultiFieldFilter($arFilter);
$arImmutableFilters = array(
	'FM', 'ID', 'CURRENCY_ID',
	'ASSIGNED_BY_ID', 'CREATED_BY_ID', 'MODIFY_BY_ID',
	'COMPANY_TYPE', 'INDUSTRY', 'EMPLOYEES'
);
foreach ($arFilter as $k => $v)
{
	if(in_array($k, $arImmutableFilters, true))
	{
		continue;
	}

	$arMatch = array();

	if($k === 'ORIGINATOR_ID')
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
		|| $k === 'ADDRESS_COUNTRY'
		|| $k === 'ADDRESS_LEGAL'
		|| $k === 'REG_ADDRESS_2'
		|| $k === 'REG_ADDRESS_CITY'
		|| $k === 'REG_ADDRESS_REGION'
		|| $k === 'REG_ADDRESS_PROVINCE'
		|| $k === 'REG_ADDRESS_POSTAL_CODE'
		|| $k === 'REG_ADDRESS_COUNTRY')
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
	elseif ($k != 'ID' && $k != 'LOGIC' && strpos($k, 'UF_') !== 0)
	{
		$arFilter['%'.$k] = $v;
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

				$obRes = CCrmCompany::GetListEx(array(), $arFilterDel, false, false, array('ID'));
				while($arCompany = $obRes->Fetch())
				{
					$ID = $arCompany['ID'];
					$arEntityAttr = $CCrmCompany->cPerms->GetEntityAttr('COMPANY', array($ID));
					if (!$CCrmCompany->cPerms->CheckEnityAccess('COMPANY', 'DELETE', $arEntityAttr[$ID]))
					{
						continue ;
					}

					$DB->StartTransaction();

					if ($CCrmBizProc->Delete($ID, $arEntityAttr[$ID])
						&& $CCrmCompany->Delete($ID, array('PROCESS_BIZPROC' => false)))
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
					$arEntityAttr = $CCrmCompany->cPerms->GetEntityAttr('COMPANY', array($ID));
					if (!$CCrmCompany->cPerms->CheckEnityAccess('COMPANY', 'WRITE', $arEntityAttr[$ID]))
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
						$DB->StartTransaction();

						if($CCrmCompany->Update($ID, $arUpdateData))
						{
							$DB->Commit();

							$arErrors = array();
							CCrmBizProcHelper::AutoStartWorkflows(
								CCrmOwnerType::Company,
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
					$arTaskID[] = 'CO_'.$ID;
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
						'back_url' => urlencode($arParams['PATH_TO_COMPANY_LIST'])
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
		elseif ($actionData['NAME'] == 'assign_to')
		{
			if(isset($actionData['ASSIGNED_BY_ID']))
			{
				$arIDs = array();
				if ($actionData['ALL_ROWS'])
				{
					$arActionFilter = $arFilter;
					$arActionFilter['CHECK_PERMISSIONS'] = 'N'; // Ignore 'WRITE' permission - we will check it before update.
					$dbRes = CCrmCompany::GetListEx(array(), $arActionFilter, false, false, array('ID'));
					while($arCompany = $dbRes->Fetch())
					{
						$arIDs[] = $arCompany['ID'];
					}
				}
				elseif (isset($actionData['ID']) && is_array($actionData['ID']))
				{
					$arIDs = $actionData['ID'];
				}

				$arEntityAttr = $CCrmPerms->GetEntityAttr('COMPANY', $arIDs);
				foreach($arIDs as $ID)
				{
					if (!$CCrmPerms->CheckEnityAccess('COMPANY', 'WRITE', $arEntityAttr[$ID]))
					{
						continue;
					}

					$DB->StartTransaction();

					$arUpdateData = array(
						'ASSIGNED_BY_ID' => $actionData['ASSIGNED_BY_ID']
					);

					if($CCrmCompany->Update($ID, $arUpdateData, true, true, array('DISABLE_USER_FIELD_CHECK' => true)))
					{
						$DB->Commit();

						$arErrors = array();
						CCrmBizProcHelper::AutoStartWorkflows(
							CCrmOwnerType::Company,
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

					$dbRes = CCrmCompany::GetListEx(
						array(),
						$arActionFilter,
						false,
						false,
						array('ID', 'OPENED')
					);

					while($arCompany = $dbRes->Fetch())
					{
						if(isset($arCompany['OPENED']) && $arCompany['OPENED'] === $isOpened)
						{
							continue;
						}

						$arIDs[] = $arCompany['ID'];
					}
				}
				elseif (isset($actionData['ID']) && is_array($actionData['ID']))
				{
					$dbRes = CCrmCompany::GetListEx(
						array(),
						array(
							'@ID'=> $actionData['ID'],
							'CHECK_PERMISSIONS' => 'N'
						),
						false,
						false,
						array('ID', 'OPENED')
					);

					while($arCompany = $dbRes->Fetch())
					{
						if(isset($arCompany['OPENED']) && $arCompany['OPENED'] === $isOpened)
						{
							continue;
						}

						$arIDs[] = $arCompany['ID'];
					}
				}

				$arEntityAttr = $CCrmPerms->GetEntityAttr('COMPANY', $arIDs);
				foreach($arIDs as $ID)
				{
					if (!$CCrmPerms->CheckEnityAccess('COMPANY', 'WRITE', $arEntityAttr[$ID]))
					{
						continue;
					}

					$DB->StartTransaction();
					$arUpdateData = array('OPENED' => $isOpened);
					if($CCrmCompany->Update($ID, $arUpdateData, true, true, array('DISABLE_USER_FIELD_CHECK' => true)))
					{
						$DB->Commit();

						CCrmBizProcHelper::AutoStartWorkflows(
							CCrmOwnerType::Company,
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
			LocalRedirect($arParams['PATH_TO_COMPANY_LIST']);
		}
	}
	else//if ($actionData['METHOD'] == 'GET')
	{
		if ($actionData['NAME'] == 'delete' && isset($actionData['ID']))
		{
			$ID = intval($actionData['ID']);

			$arEntityAttr = $CCrmCompany->cPerms->GetEntityAttr('COMPANY', array($ID));
			$attr = $arEntityAttr[$ID];

			if($CCrmCompany->cPerms->CheckEnityAccess('COMPANY', 'DELETE', $attr))
			{
				$DB->StartTransaction();

				if($CCrmBizProc->Delete($ID, $attr)
					&& $CCrmCompany->Delete($ID, array('PROCESS_BIZPROC' => false)))
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
			LocalRedirect($bInternal ? '?'.$arParams['FORM_ID'].'_active_tab=tab_company' : $arParams['PATH_TO_COMPANY_LIST']);
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
			$urlParams[] = $arFilter['id'];
	}
	$urlParams[] = 'clear_filter';
	$CGridOptions->GetFilter(array());
	LocalRedirect($APPLICATION->GetCurPageParam('', $urlParams));
}

$_arSort = $CGridOptions->GetSorting(array(
	'sort' => array('nearest_activity' => 'asc'),
	'vars' => array('by' => 'by', 'order' => 'order')
));

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
		$arSelectMap['COMPANY_TYPE'] = true;
}
else
{
	if(isset($arSelectMap['COMPANY_SUMMARY']))
	{
		$arSelectMap['LOGO'] =
			$arSelectMap['TITLE'] =
			$arSelectMap['COMPANY_TYPE'] = true;
	}

	if($arSelectMap['ASSIGNED_BY'])
	{
		$arSelectMap['ASSIGNED_BY_LOGIN'] =
			$arSelectMap['ASSIGNED_BY_NAME'] =
			$arSelectMap['ASSIGNED_BY_LAST_NAME'] =
			$arSelectMap['ASSIGNED_BY_SECOND_NAME'] = true;
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

	if(isset($arSelectMap['FULL_REG_ADDRESS']))
	{
		$arSelectMap['REG_ADDRESS'] =
			$arSelectMap['REG_ADDRESS_2'] =
			$arSelectMap['REG_ADDRESS_CITY'] =
			$arSelectMap['REG_ADDRESS_POSTAL_CODE'] =
			$arSelectMap['REG_ADDRESS_POSTAL_CODE'] =
			$arSelectMap['REG_ADDRESS_REGION'] =
			$arSelectMap['REG_ADDRESS_PROVINCE'] =
			$arSelectMap['REG_ADDRESS_COUNTRY'] = true;
	}

	// ID must present in select
	if(!isset($arSelectMap['ID']))
	{
		$arSelectMap['ID'] = true;
	}
}

if ($isInExportMode)
{
	CCrmComponentHelper::PrepareExportFieldsList(
		$arSelectedHeaders,
		array(
			'COMPANY_SUMMARY' => array(
				'LOGO',
				'TITLE',
				'COMPANY_TYPE'
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
	$nTopCount = $arParams['COMPANY_COUNT'];
}

if($nTopCount > 0 && !isset($arFilter['ID']))
{
	$arNavParams['nTopCount'] = $nTopCount;
}

if ($isInExportMode)
	$arFilter['PERMISSION'] = 'EXPORT';

// HACK: Make custom sort for ASSIGNED_BY
$arSort = $arResult['SORT'];
if(isset($arSort['assigned_by']))
{
	$arSort['assigned_by_last_name'] = $arSort['assigned_by'];
	$arSort['assigned_by_name'] = $arSort['assigned_by'];
	$arSort['assigned_by_login'] = $arSort['assigned_by'];
	unset($arSort['assigned_by']);
}

$arOptions = array('FIELD_OPTIONS' => array('ADDITIONAL_FIELDS' => array()));
if(isset($arSelectMap['ACTIVITY_ID']))
{
	$arOptions['FIELD_OPTIONS']['ADDITIONAL_FIELDS'][] = 'ACTIVITY';
}

if(isset($arParams['IS_EXTERNAL_CONTEXT']))
{
	$arOptions['IS_EXTERNAL_CONTEXT'] = $arParams['IS_EXTERNAL_CONTEXT'];
}

$arSelect = array_unique(array_keys($arSelectMap), SORT_STRING);

$arResult['COMPANY'] = array();
$arResult['COMPANY_ID'] = array();
$arResult['COMPANY_UF'] = array();

if(isset($arSort['nearest_activity']))
{
	$navDbResult = CCrmActivity::GetEntityList(
		CCrmOwnerType::Company,
		$userID,
		$arSort['nearest_activity'],
		$arFilter,
		(!$isInExportMode ? $arNavParams : false)
	);

	$arResult['ROWS_COUNT'] = $navDbResult->SelectedRowsCount();
	$arResult['DB_LIST'] = $navDbResult;
	$arResult['DB_FILTER'] = $arFilter;

	while($arCompany = $navDbResult->Fetch())
	{
		$arResult['COMPANY'][$arCompany['ID']] = $arCompany;
		$arResult['COMPANY_ID'][$arCompany['ID']] = $arCompany['ID'];
		$arResult['COMPANY_UF'][$arCompany['ID']] = array();
	}

	$arFilter['@ID'] = array_keys($arResult['COMPANY']);
	$dbResult = CCrmCompany::GetListEx($arSort, $arFilter, false, false, $arSelect, $arOptions);

	while($arCompany = $dbResult->GetNext())
	{
		$arResult['COMPANY'][$arCompany['ID']] = $arCompany;
	}
}
else
{
	$addressSort = array();
	$addressTypeID = \Bitrix\Crm\EntityAddress::Primary;
	foreach($arSort as $k => $v)
	{
		if(strncmp($k, 'address', 7) === 0)
		{
			$addressSort[strtoupper($k)] = $v;
		}
	}

	if(empty($addressSort))
	{
		$addressTypeID = \Bitrix\Crm\EntityAddress::Registered;
		foreach($arSort as $k => $v)
		{
			if(strncmp($k, 'registered_address', 18) === 0)
			{
				$addressSort[strtoupper($k)] = $v;
			}
		}
	}

	if(!empty($addressSort))
	{
		$navDbResult = \Bitrix\Crm\CompanyAddress::getEntityList(
			$addressTypeID,
			$addressSort,
			$arFilter,
			(!$isInExportMode ? $arNavParams : false)
		);

		$arResult['ROWS_COUNT'] = $navDbResult->SelectedRowsCount();
		$arResult['DB_LIST'] = $navDbResult;
		$arResult['DB_FILTER'] = $arFilter;

		while($arCompany = $navDbResult->Fetch())
		{
			$arResult['COMPANY'][$arCompany['ID']] = $arCompany;
			$arResult['COMPANY_ID'][$arCompany['ID']] = $arCompany['ID'];
			$arResult['COMPANY_UF'][$arCompany['ID']] = array();
		}

		$arFilter['@ID'] = array_keys($arResult['COMPANY']);
		$arSort['ID'] = array_shift(array_slice($addressSort, 0, 1));
		$dbResult = CCrmCompany::GetListEx($arSort, $arFilter, false, false, $arSelect, $arOptions);

		while($arCompany = $dbResult->GetNext())
		{
			$arResult['COMPANY'][$arCompany['ID']] = $arCompany;
		}
	}
	else
	{
		$dbResult = CCrmCompany::GetListEx($arSort, $arFilter, false, (!$isInExportMode ? $arNavParams : false), $arSelect, $arOptions);

		$arResult['ROWS_COUNT'] = $dbResult->SelectedRowsCount();
		$arResult['DB_LIST'] = $dbResult;
		$arResult['DB_FILTER'] = $arFilter;

		while($arCompany = $dbResult->GetNext())
		{
			$arResult['COMPANY'][$arCompany['ID']] = $arCompany;
			$arResult['COMPANY_ID'][$arCompany['ID']] = $arCompany['ID'];
			$arResult['COMPANY_UF'][$arCompany['ID']] = array();
		}
	}
}

$arResult['PERMS']['ADD']    = !$CCrmCompany->cPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE, 'ADD');
$arResult['PERMS']['WRITE']  = !$CCrmCompany->cPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE, 'WRITE');
$arResult['PERMS']['DELETE'] = !$CCrmCompany->cPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE, 'DELETE');

$bDeal = !$CCrmCompany->cPerms->HavePerm('DEAL', BX_CRM_PERM_NONE, 'ADD');
$arResult['PERM_DEAL'] = $bDeal;
$bQuote = !$CCrmCompany->cPerms->HavePerm('QUOTE', BX_CRM_PERM_NONE, 'ADD');
$arResult['PERM_QUOTE'] = $bQuote;
$bInvoice = !$CCrmCompany->cPerms->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'ADD');
$arResult['PERM_INVOICE'] = $bInvoice;
$bContact = !$CCrmCompany->cPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'ADD');
$arResult['PERM_CONTACT'] = $bContact;
$now = time() + CTimeZone::GetOffset();

foreach($arResult['COMPANY'] as &$arCompany)
{
	if (!empty($arCompany['LOGO']))
	{
		if ($isInExportMode)
		{
			if ($arFile = CFile::GetFileArray($arCompany['LOGO']))
			{
				$arCompany['LOGO'] = CHTTP::URN2URI($arFile['SRC']);
			}
		}
		else
		{
			$arFileTmp = CFile::ResizeImageGet(
				$arCompany['LOGO'],
				array('width' => 50, 'height' => 50),
				BX_RESIZE_IMAGE_PROPORTIONAL,
				false
			);
			$arCompany['LOGO'] = CFile::ShowImage($arFileTmp['src'], 50, 50, 'border=0');
		}
	}

	$typeID = isset($arCompany['COMPANY_TYPE']) ? $arCompany['COMPANY_TYPE'] : '';
	$arCompany['COMPANY_TYPE_NAME'] = isset($arResult['COMPANY_TYPE_LIST'][$typeID]) ? $arResult['COMPANY_TYPE_LIST'][$typeID] : $typeID;

	$arCompany['PATH_TO_COMPANY_SHOW'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_SHOW'],
		array(
			'company_id' => $arCompany['ID']
		)
	);
	$arCompany['PATH_TO_COMPANY_EDIT'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_EDIT'],
		array(
			'company_id' => $arCompany['ID']
		)
	);
	$arCompany['PATH_TO_COMPANY_COPY'] =  CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_EDIT'],
		array(
			'company_id' => $arCompany['ID']
		)),
		array('copy' => 1)
	);
	$arCompany['PATH_TO_COMPANY_DELETE'] =  CHTTP::urlAddParams(
		$bInternal ? $APPLICATION->GetCurPage() : $arParams['PATH_TO_COMPANY_LIST'],
		array('action_'.$arResult['GRID_ID'] => 'delete', 'ID' => $arCompany['ID'], 'sessid' => $arResult['SESSION_ID'])
	);
	$arCompany['PATH_TO_USER_PROFILE'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_PROFILE'],
		array(
			'user_id' => $arCompany['ASSIGNED_BY']
		)
	);
	$arCompany['PATH_TO_USER_CREATOR'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_PROFILE'],
		array(
			'user_id' => $arCompany['CREATED_BY']
		)
	);

	$arCompany['PATH_TO_USER_MODIFIER'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_PROFILE'],
		array(
			'user_id' => $arCompany['MODIFY_BY']
		)
	);

	$arCompany['CREATED_BY_FORMATTED_NAME'] = CUser::FormatName($arParams['NAME_TEMPLATE'],
		array(
			'LOGIN' => $arCompany['CREATED_BY_LOGIN'],
			'NAME' => $arCompany['CREATED_BY_NAME'],
			'LAST_NAME' => $arCompany['CREATED_BY_LAST_NAME'],
			'SECOND_NAME' => $arCompany['CREATED_BY_SECOND_NAME']
		),
		true, false
	);

	$arCompany['MODIFY_BY_FORMATTED_NAME'] = CUser::FormatName($arParams['NAME_TEMPLATE'],
		array(
			'LOGIN' => $arCompany['MODIFY_BY_LOGIN'],
			'NAME' => $arCompany['MODIFY_BY_NAME'],
			'LAST_NAME' => $arCompany['MODIFY_BY_LAST_NAME'],
			'SECOND_NAME' => $arCompany['MODIFY_BY_SECOND_NAME']
		),
		true, false
	);

	if ($bContact)
		$arCompany['PATH_TO_CONTACT_EDIT'] = CHTTP::urlAddParams(
			CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_CONTACT_EDIT'],
				array(
					'contact_id' => 0
				)
			),
			array('company_id' => $arCompany['ID'])
		);

	if ($bDeal)
	{
		$addParams = array('company_id' => $arCompany['ID']);
		if(isset($arParams['INTERNAL_CONTEXT']['CONTACT_ID']))
		{
			$addParams['contact_id'] = $arParams['INTERNAL_CONTEXT']['CONTACT_ID'];
		}
		$arCompany['PATH_TO_DEAL_EDIT'] = CHTTP::urlAddParams(
			CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_DEAL_EDIT'],
				array(
					'deal_id' => 0
				)
			),
			$addParams
		);
	}

	if(isset($arCompany['~ACTIVITY_TIME']))
	{
		$time = MakeTimeStamp($arCompany['~ACTIVITY_TIME']);
		$arCompany['~ACTIVITY_EXPIRED'] = $time <= $now;
		$arCompany['~ACTIVITY_IS_CURRENT_DAY'] = $arCompany['~ACTIVITY_EXPIRED'] || CCrmActivity::IsCurrentDay($time);
	}

	if ($arResult['ENABLE_TASK'])
	{
		$arCompany['PATH_TO_TASK_EDIT'] = CHTTP::urlAddParams(
			CComponentEngine::MakePathFromTemplate(COption::GetOptionString('tasks', 'paths_task_user_edit', ''),
				array(
					'task_id' => 0,
					'user_id' => $userID
				)
			),
			array(
				'UF_CRM_TASK' => 'CO_'.$arCompany['ID'],
				'TITLE' => urlencode(GetMessage('CRM_TASK_TITLE_PREFIX')),
				'TAGS' => urlencode(GetMessage('CRM_TASK_TAG')),
				'back_url' => urlencode($arParams['PATH_TO_COMPANY_LIST'])
			)
		);
	}

	if (IsModuleInstalled('sale'))
	{
		$arCompany['PATH_TO_QUOTE_ADD'] =
			CHTTP::urlAddParams(CComponentEngine::makePathFromTemplate(
					$arParams['PATH_TO_QUOTE_EDIT'], array('quote_id' => 0)),
				array('company_id' => $arCompany['ID'])
			);
		$arCompany['PATH_TO_INVOICE_ADD'] =
			CHTTP::urlAddParams(CComponentEngine::makePathFromTemplate(
					$arParams['PATH_TO_INVOICE_EDIT'], array('invoice_id' => 0)),
				array('company' => $arCompany['ID'])
			);
	}

	if ($arResult['ENABLE_BIZPROC'])
	{
		$arCompany['BIZPROC_STATUS'] = '';
		$arCompany['BIZPROC_STATUS_HINT'] = '';
		$arDocumentStates = CBPDocument::GetDocumentStates(
			array('crm', 'CCrmDocumentCompany', 'COMPANY'),
			array('crm', 'CCrmDocumentCompany', 'COMPANY_'.$arCompany['ID'])
		);

		$arCompany['PATH_TO_BIZPROC_LIST'] =  CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_SHOW'],
			array(
				'company_id' => $arCompany['ID']
			)),
			array('CRM_COMPANY_SHOW_V12_active_tab' => 'tab_bizproc')
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
				$arCompany[$paramName] = $docTtl;
			}
			else
			{
				$arCompany[$paramName] = '<a href="'.htmlspecialcharsbx($arCompany['PATH_TO_BIZPROC_LIST']).'">'.htmlspecialcharsbx($docTtl).'</a>';

				$docID = $arDocState['ID'];
				$taskQty = CCrmBizProcHelper::GetUserWorkflowTaskCount(array($docID), $userID);
				if($taskQty > 0)
				{
					$totalTaskQty += $taskQty;
				}

				$arCompany['BIZPROC_STATUS'] = $taskQty > 0 ? 'attention' : 'inprogress';
				$arCompany['BIZPROC_STATUS_HINT'] =
					'<div class=\'bizproc-item-title\'>'.
						htmlspecialcharsbx($docTemplateName !== '' ? $docTemplateName : GetMessage('CRM_BPLIST')).
						': <span class=\'bizproc-item-title bizproc-state-title\'><a href=\''.$arCompany['PATH_TO_BIZPROC_LIST'].'\'>'.
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
					$arCompany[$paramName] = $docTtl;
				}
				else
				{
					$arCompany[$paramName] = '<a href="'.htmlspecialcharsbx($arCompany['PATH_TO_BIZPROC_LIST']).'">'.htmlspecialcharsbx($docTtl).'</a>';

					$docID = $arDocState['ID'];
					//TODO: wait for bizproc bugs will be fixed and replace serial call of CCrmBizProcHelper::GetUserWorkflowTaskCount on single call
					$taskQty = CCrmBizProcHelper::GetUserWorkflowTaskCount(array($docID), $userID);
					if($taskQty === 0)
					{
						continue;
					}

					if ($arCompany['BIZPROC_STATUS'] !== 'attention')
					{
						$arCompany['BIZPROC_STATUS'] = 'attention';
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
				$arCompany['BIZPROC_STATUS_HINT'] =
					'<span class=\'bizproc-item-title\'>'.GetMessage('CRM_BP_R_P').': <a href=\''.$arCompany['PATH_TO_BIZPROC_LIST'].'\' title=\''.GetMessage('CRM_BP_R_P_TITLE').'\'>'.$docStatesQty.'</a></span>'.
					($totalTaskQty === 0
						? ''
						: '<br /><span class=\'bizproc-item-title\'>'.GetMessage('CRM_TASKS').': <a href=\''.$arCompany['PATH_TO_USER_BP'].'\' title=\''.GetMessage('CRM_TASKS_TITLE').'\'>'.$totalTaskQty.($totalTaskQty > 5 ? '+' : '').'</a></span>');
			}
		}
	}

	$arCompany['ASSIGNED_BY_ID'] = $arCompany['~ASSIGNED_BY_ID'] = intval($arCompany['ASSIGNED_BY']);
	$arCompany['ASSIGNED_BY'] = CUser::FormatName(
		$arParams['NAME_TEMPLATE'],
		array(
			'LOGIN' => $arCompany['ASSIGNED_BY_LOGIN'],
			'NAME' => $arCompany['ASSIGNED_BY_NAME'],
			'LAST_NAME' => $arCompany['ASSIGNED_BY_LAST_NAME'],
			'SECOND_NAME' => $arCompany['ASSIGNED_BY_SECOND_NAME']
		),
		true, false
	);

	if(isset($arSelectMap['FULL_ADDRESS']))
	{
		$arCompany['FULL_ADDRESS'] = CompanyAddressFormatter::format(
			$arCompany,
			array('SEPARATOR' => AddressSeparator::HtmlLineBreak, 'NL2BR' => true)
		);
	}

	if(isset($arSelectMap['FULL_REG_ADDRESS']))
	{
		$arCompany['FULL_REG_ADDRESS'] = CompanyAddressFormatter::format(
			$arCompany,
			array('SEPARATOR' => AddressSeparator::HtmlLineBreak, 'TYPE_ID' => EntityAddress::Registered, 'NL2BR' => true)
		);
	}

	$arResult['COMPANY'][$arCompany['ID']] = $arCompany;
	$arResult['COMPANY_UF'][$arCompany['ID']] = array();
	$arResult['COMPANY_ID'][$arCompany['ID']] = $arCompany['ID'];
}
unset($arCompany);

$CCrmUserType->ListAddEnumFieldsValue(
	$arResult,
	$arResult['COMPANY'],
	$arResult['COMPANY_UF'],
	($isInExportMode ? ', ' : '<br />'),
	$isInExportMode,
	array(
		'FILE_URL_TEMPLATE' =>
			'/bitrix/components/bitrix/crm.company.show/show_file.php?ownerId=#owner_id#&fieldName=#field_name#&fileId=#file_id#'
	)
);

// adding crm multi field to result array
if (isset($arResult['COMPANY_ID']) && !empty($arResult['COMPANY_ID']))
{
	$arFmList = array();
	$res = CCrmFieldMulti::GetList(array('ID' => 'asc'), array('ENTITY_ID' => 'COMPANY', 'ELEMENT_ID' => $arResult['COMPANY_ID']));
	while($ar = $res->Fetch())
	{
		if (!$isInExportMode)
			$arFmList[$ar['ELEMENT_ID']][$ar['COMPLEX_ID']][] = CCrmFieldMulti::GetTemplateByComplex($ar['COMPLEX_ID'], $ar['VALUE']);
		else
			$arFmList[$ar['ELEMENT_ID']][$ar['COMPLEX_ID']][] = $ar['VALUE'];
		$arResult['COMPANY'][$ar['ELEMENT_ID']]['~'.$ar['COMPLEX_ID']][] = $ar['VALUE'];
	}
	foreach ($arFmList as $elementId => $arFM)
		foreach ($arFM as $complexId => $arComplexName)
			$arResult['COMPANY'][$elementId][$complexId] = implode(', ', $arComplexName);

	// checkig access for operation
	$arCompanyAttr = CCrmPerms::GetEntityAttr('COMPANY', $arResult['COMPANY_ID']);
	foreach ($arResult['COMPANY_ID'] as $iCompanyId)
	{
		$arResult['COMPANY'][$iCompanyId]['EDIT'] = $CCrmCompany->cPerms->CheckEnityAccess('COMPANY', 'WRITE', $arCompanyAttr[$iCompanyId]);
		$arResult['COMPANY'][$iCompanyId]['DELETE'] = $CCrmCompany->cPerms->CheckEnityAccess('COMPANY', 'DELETE', $arCompanyAttr[$iCompanyId]);

		$arResult['COMPANY'][$iCompanyId]['BIZPROC_LIST'] = array();
		foreach ($arBPData as $arBP)
		{
			if (!CBPDocument::CanUserOperateDocument(
				CBPCanUserOperateOperation::StartWorkflow,
				$userID,
				array('crm', 'CCrmDocumentCompany', 'COMPANY_'.$arResult['COMPANY'][$iCompanyId]['ID']),
				array(
					'UserGroups' => $CCrmBizProc->arCurrentUserGroups,
					'DocumentStates' => $arDocumentStates,
					'WorkflowTemplateId' => $arBP['ID'],
					'CreatedBy' => $arResult['COMPANY'][$iCompanyId]['~ASSIGNED_BY'],
					'UserIsAdmin' => $isAdmin,
					'CRMEntityAttr' =>  $arCompanyAttr[$iCompanyId]
				)
			))
			{
				continue;
			}

			$arBP['PATH_TO_BIZPROC_START'] = CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_SHOW'],
				array(
					'company_id' => $arResult['COMPANY'][$iCompanyId]['ID']
				)),
				array(
					'workflow_template_id' => $arBP['ID'], 'bizproc_start' => 1,  'sessid' => $arResult['SESSION_ID'],
					'CRM_COMPANY_SHOW_V12_active_tab' => 'tab_bizproc', 'back_url' => $arParams['PATH_TO_COMPANY_LIST'])
			);
			$arResult['COMPANY'][$iCompanyId]['BIZPROC_LIST'][] = $arBP;
		}
	}
}

if (!$isInExportMode)
{
	$arResult['NEED_FOR_REBUILD_DUP_INDEX'] = false;
	$arResult['NEED_FOR_REBUILD_COMPANY_ATTRS'] = false;

	if(!$bInternal && CCrmPerms::IsAdmin())
	{
		if(COption::GetOptionString('crm', '~CRM_REBUILD_COMPANY_DUP_INDEX', 'N') === 'Y')
		{
			$arResult['NEED_FOR_REBUILD_DUP_INDEX'] = true;
		}

		if(COption::GetOptionString('crm', '~CRM_REBUILD_COMPANY_ATTR', 'N') === 'Y')
		{
			$arResult['PATH_TO_PRM_LIST'] = CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_perm_list'));
			$arResult['NEED_FOR_REBUILD_COMPANY_ATTRS'] = true;
		}
	}

	$this->IncludeComponentTemplate();
	include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.company/include/nav.php');
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
		Header('Content-Disposition: attachment;filename=companies.csv');
	}
	elseif($sExportType === 'excel')
	{
		Header('Content-Type: application/vnd.ms-excel');
		Header('Content-Disposition: attachment;filename=companies.xls');
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
