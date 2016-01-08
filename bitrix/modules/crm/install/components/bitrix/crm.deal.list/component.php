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
if ($CCrmPerms->HavePerm('DEAL', BX_CRM_PERM_NONE, 'READ'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$CCrmDeal = new CCrmDeal(false);
$CCrmBizProc = new CCrmBizProc('DEAL');

$userID = CCrmSecurityHelper::GetCurrentUserID();
$isAdmin = CCrmPerms::IsAdmin();

$arResult['CURRENT_USER_ID'] = CCrmSecurityHelper::GetCurrentUserID();
$arParams['PATH_TO_DEAL_LIST'] = CrmCheckPath('PATH_TO_DEAL_LIST', $arParams['PATH_TO_DEAL_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_DEAL_SHOW'] = CrmCheckPath('PATH_TO_DEAL_SHOW', $arParams['PATH_TO_DEAL_SHOW'], $APPLICATION->GetCurPage().'?deal_id=#deal_id#&show');
$arParams['PATH_TO_DEAL_EDIT'] = CrmCheckPath('PATH_TO_DEAL_EDIT', $arParams['PATH_TO_DEAL_EDIT'], $APPLICATION->GetCurPage().'?deal_id=#deal_id#&edit');
$arParams['PATH_TO_DEAL_WIDGET'] = CrmCheckPath('PATH_TO_DEAL_WIDGET', $arParams['PATH_TO_DEAL_WIDGET'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_QUOTE_EDIT'] = CrmCheckPath('PATH_TO_QUOTE_EDIT', $arParams['PATH_TO_QUOTE_EDIT'], $APPLICATION->GetCurPage().'?quote_id=#quote_id#&edit');
$arParams['PATH_TO_INVOICE_EDIT'] = CrmCheckPath('PATH_TO_INVOICE_EDIT', $arParams['PATH_TO_INVOICE_EDIT'], $APPLICATION->GetCurPage().'?invoice_id=#invoice_id#&edit');
$arParams['PATH_TO_COMPANY_SHOW'] = CrmCheckPath('PATH_TO_COMPANY_SHOW', $arParams['PATH_TO_COMPANY_SHOW'], $APPLICATION->GetCurPage().'?company_id=#company_id#&show');
$arParams['PATH_TO_CONTACT_SHOW'] = CrmCheckPath('PATH_TO_CONTACT_SHOW', $arParams['PATH_TO_CONTACT_SHOW'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&show');
$arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath('PATH_TO_USER_PROFILE', $arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');
$arParams['PATH_TO_USER_BP'] = CrmCheckPath('PATH_TO_USER_BP', $arParams['PATH_TO_USER_BP'], '/company/personal/bizproc/');
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

$arResult['IS_AJAX_CALL'] = isset($_REQUEST['bxajaxid']) || isset($_REQUEST['AJAX_CALL']);
$arResult['SESSION_ID'] = bitrix_sessid();
$arResult['NAVIGATION_CONTEXT_ID'] = isset($arParams['NAVIGATION_CONTEXT_ID']) ? $arParams['NAVIGATION_CONTEXT_ID'] : '';

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

$enableWidgetFilter = !$bInternal && isset($_REQUEST['WG']) && strtoupper($_REQUEST['WG']) === 'Y';
if($enableWidgetFilter)
{
	$dataSourceFilter = null;

	$dataSourceName = isset($_REQUEST['DS']) ? $_REQUEST['DS'] : '';
	if($dataSourceName !== '')
	{
		$dataSource = null;
		try
		{
			$dataSource = Bitrix\Crm\Widget\Data\DataSourceFactory::create(array('name' => $dataSourceName), $userID, true);
		}
		catch(Bitrix\Main\NotSupportedException $e)
		{
		}

		$dataSourceFilter = $dataSource ? $dataSource->prepareEntityListFilter($_REQUEST) : null;
	}

	if(is_array($dataSourceFilter) && !empty($dataSourceFilter))
	{
		$arFilter = $dataSourceFilter;
	}
	else
	{
		$enableWidgetFilter = false;
	}
}

$arResult['IS_EXTERNAL_FILTER'] = $enableWidgetFilter;

$sExportType = '';
if (!empty($_REQUEST['type']))
{
	$sExportType = strtolower(trim($_REQUEST['type']));
	if (!in_array($sExportType, array('csv', 'excel')))
		$sExportType = '';
}
if (!empty($sExportType) && $CCrmPerms->HavePerm('DEAL', BX_CRM_PERM_NONE, 'EXPORT'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$isInExportMode = $sExportType !== '';

$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmDeal::$sUFEntityID);

$arResult['GRID_ID'] = 'CRM_DEAL_LIST_V12'.($bInternal && !empty($arParams['GRID_ID_SUFFIX']) ? '_'.$arParams['GRID_ID_SUFFIX'] : '');
$arResult['TYPE_LIST'] = CCrmStatus::GetStatusListEx('DEAL_TYPE');
$arResult['STAGE_LIST'] = CCrmStatus::GetStatusListEx('DEAL_STAGE');
/*$arResult['STATE_LIST'] = CCrmStatus::GetStatusListEx('DEAL_STATE');*/
// Please, uncomment if required
//$arResult['CURRENCY_LIST'] = CCrmCurrencyHelper::PrepareListItems();
$arResult['EVENT_LIST'] = CCrmStatus::GetStatusListEx('EVENT_TYPE');
$arResult['CLOSED_LIST'] = array('Y' => GetMessage('MAIN_YES'), 'N' => GetMessage('MAIN_NO'));
$arResult['FILTER'] = array();
$arResult['FILTER2LOGIC'] = array();
$arResult['FILTER_PRESETS'] = array();
$arResult['PERMS']['ADD']    = !$CCrmPerms->HavePerm('DEAL', BX_CRM_PERM_NONE, 'ADD');
$arResult['PERMS']['WRITE']  = !$CCrmPerms->HavePerm('DEAL', BX_CRM_PERM_NONE, 'WRITE');
$arResult['PERMS']['DELETE'] = !$CCrmPerms->HavePerm('DEAL', BX_CRM_PERM_NONE, 'DELETE');

$arResult['AJAX_MODE'] = isset($arParams['AJAX_MODE']) ? $arParams['AJAX_MODE'] : ($arResult['INTERNAL'] ? 'N' : 'Y');
$arResult['AJAX_ID'] = isset($arParams['AJAX_ID']) ? $arParams['AJAX_ID'] : '';
$arResult['AJAX_OPTION_JUMP'] = isset($arParams['AJAX_OPTION_JUMP']) ? $arParams['AJAX_OPTION_JUMP'] : 'N';
$arResult['AJAX_OPTION_HISTORY'] = isset($arParams['AJAX_OPTION_HISTORY']) ? $arParams['AJAX_OPTION_HISTORY'] : 'N';

$arExternalSales = $arResult['EXTERNAL_SALES'] = CCrmExternalSaleHelper::PrepareListItems();

if (!$bInternal)
{
	$arResult['FILTER2LOGIC'] = array('TITLE', 'COMMENTS');

	ob_start();
	$GLOBALS['APPLICATION']->IncludeComponent('bitrix:crm.entity.selector',
		'',
		array(
			'ENTITY_TYPE' => 'CONTACT',
			'INPUT_NAME' => 'CONTACT_ID',
			'INPUT_VALUE' => isset($_REQUEST['CONTACT_ID']) ? intval($_REQUEST['CONTACT_ID']) : '',
			'FORM_NAME' => $arResult['GRID_ID'],
			'MULTIPLE' => 'N',
			'FILTER' => true
		),
		false,
		array('HIDE_ICONS' => 'Y')
	);
	$sValContact = ob_get_contents();
	ob_end_clean();

	ob_start();
	$GLOBALS['APPLICATION']->IncludeComponent('bitrix:crm.entity.selector',
		'',
		array(
			'ENTITY_TYPE' => 'COMPANY',
			'INPUT_NAME' => 'COMPANY_ID',
			'INPUT_VALUE' => isset($_REQUEST['COMPANY_ID']) ? intval($_REQUEST['COMPANY_ID']) : '',
			'FORM_NAME' => $arResult['GRID_ID'],
			'MULTIPLE' => 'N',
			'FILTER' => true
		),
		false,
		array('HIDE_ICONS' => 'Y')
	);
	$sValCompany = ob_get_contents();
	ob_end_clean();

	$originatorID = isset($_REQUEST['ORIGINATOR_ID']) ? $_REQUEST['ORIGINATOR_ID'] : '';
	ob_start();
	?>
	<select name="ORIGINATOR_ID">
		<option value=""><?= GetMessage("CRM_COLUMN_ALL") ?></option>
		<option value="__INTERNAL" <?= $originatorID === '__INTERNAL' ? 'selected' : ''?>><?= GetMessage("CRM_INTERNAL") ?></option>
		<?
		foreach($arExternalSales as $k => $v)
		{
			$k = strval($k);
			?><option value="<?= htmlspecialcharsbx($k) ?>"<?= ($originatorID === $k) ? " selected" : "" ?>><?= htmlspecialcharsbx($v) ?></option><?
		}
		?>
	</select>
	<?
	$sValOriginator = ob_get_contents();
	ob_end_clean();

	$arResult['FILTER'] = array(
		array('id' => 'ID', 'name' => GetMessage('CRM_COLUMN_ID')),
		array('id' => 'TITLE', 'name' => GetMessage('CRM_COLUMN_TITLE'), 'default' => true),
		array('id' => 'ASSIGNED_BY_ID',  'name' => GetMessage('CRM_COLUMN_ASSIGNED_BY'), 'default' => true, 'enable_settings' => true, 'type' => 'user'),
		array('id' => 'OPPORTUNITY', 'name' => GetMessage('CRM_COLUMN_OPPORTUNITY'), 'default' => true, 'type' => 'number'),
		array('id' => 'CURRENCY_ID', 'name' => GetMessage('CRM_COLUMN_CURRENCY_ID'), 'default' => true, 'type' => 'list', 'items' => array('' => '') + CCrmCurrencyHelper::PrepareListItems()),
		array('id' => 'STAGE_ID', 'params' => array('multiple' => 'Y'), 'name' => GetMessage('CRM_COLUMN_STAGE_ID'), 'default' => true, 'type' => 'list', 'items' => CCrmStatus::GetStatusList('DEAL_STAGE'), 'default' => true),
		array('id' => 'PROBABILITY', 'name' => GetMessage('CRM_COLUMN_PROBABILITY'), 'default' => true, 'type' => 'number'),
		array('id' => 'BEGINDATE', 'name' => GetMessage('CRM_COLUMN_BEGINDATE'), 'default' => true, 'type' => 'date'),
		array('id' => 'CLOSEDATE', 'name' => GetMessage('CRM_COLUMN_CLOSEDATE'), 'default' => true, 'type' => 'date'),
		array('id' => 'CONTACT_ID', 'name' => GetMessage('CRM_COLUMN_CONTACT_LIST'), 'type' => 'custom', 'value' => $sValContact),
		array('id' => 'CONTACT_FULL_NAME', 'name' => GetMessage('CRM_COLUMN_CONTACT_FULL_NAME')),
		array('id' => 'COMPANY_ID', 'name' => GetMessage('CRM_COLUMN_COMPANY_LIST'), 'type' => 'custom', 'value' => $sValCompany),
		array('id' => 'COMPANY_TITLE', 'name' => GetMessage('CRM_COLUMN_COMPANY_TITLE')),
		array('id' => 'COMMENTS', 'name' => GetMessage('CRM_COLUMN_COMMENTS')),
		array('id' => 'TYPE_ID', 'params' => array('multiple' => 'Y'), 'name' => GetMessage('CRM_COLUMN_TYPE_ID'),  'type' => 'list', 'items' => CCrmStatus::GetStatusList('DEAL_TYPE')),
		array('id' => 'CLOSED', 'name' => GetMessage('CRM_COLUMN_CLOSED'), 'type' => 'list', 'items' => array('' => '', 'Y' => GetMessage('MAIN_YES'), 'N' => GetMessage('MAIN_NO'))),
		/*array('id' => 'STATE_ID', 'name' => GetMessage('CRM_COLUMN_STATE_ID'), 'type' => 'list', 'items' => array('' => '') + CCrmStatus::GetStatusList('DEAL_STATE')),        */
		array('id' => 'DATE_CREATE', 'name' => GetMessage('CRM_COLUMN_DATE_CREATE'), 'type' => 'date'),
		array('id' => 'CREATED_BY_ID',  'name' => GetMessage('CRM_COLUMN_CREATED_BY'), 'default' => false, 'enable_settings' => true, 'type' => 'user'),
		array('id' => 'DATE_MODIFY', 'name' => GetMessage('CRM_COLUMN_DATE_MODIFY'), 'type' => 'date'),
		array('id' => 'MODIFY_BY_ID',  'name' => GetMessage('CRM_COLUMN_MODIFY_BY'), 'default' => false, 'enable_settings' => true, 'type' => 'user'),
		array('id' => 'EVENT_DATE', 'name' => GetMessage('CRM_COLUMN_EVENT_DATE_FILTER'), 'type' => 'date'),
		array('id' => 'EVENT_ID', 'name' => GetMessage('CRM_COLUMN_EVENT_ID_FILTER'), 'type' => 'list', 'items' => array('' => '') + CCrmStatus::GetStatusList('EVENT_TYPE')),
		array('id' => 'PRODUCT_ROW_PRODUCT_ID', 'name' => GetMessage('CRM_COLUMN_PRODUCT'), 'enable_settings' => false),
		array('id' => 'ORIGINATOR_ID', 'name' => GetMessage('CRM_COLUMN_BINDING'), 'type' => 'custom', 'value' => $sValOriginator),
	);

	$CCrmUserType->ListAddFilterFields($arResult['FILTER'], $arResult['FILTER2LOGIC'], $arResult['GRID_ID']);

	$currentUserID = $arResult['CURRENT_USER_ID'];
	$currentUserName = CCrmViewHelper::GetFormattedUserName($currentUserID, $arParams['NAME_TEMPLATE']);
	$arResult['FILTER_PRESETS'] = array(
		'filter_new' => array('name' => GetMessage('CRM_PRESET_NEW'), 'fields' => array('STAGE_ID' => array('selNEW' => 'NEW'))),
		'filter_my' => array('name' => GetMessage('CRM_PRESET_MY'), 'fields' => array( 'ASSIGNED_BY_ID_name' => $currentUserName, 'ASSIGNED_BY_ID' => $currentUserID))
		//'filter_change_today' => array('name' => GetMessage('CRM_PRESET_CHANGE_TODAY'), 'fields' => array('DATE_MODIFY_datesel' => 'today')),
		//'filter_change_yesterday' => array('name' => GetMessage('CRM_PRESET_CHANGE_YESTERDAY'), 'fields' => array('DATE_MODIFY_datesel' => 'yesterday')),
		//'filter_change_my' => array('name' => GetMessage('CRM_PRESET_CHANGE_MY'), 'fields' => array( 'MODIFY_BY_ID_name' => $currentUserName, 'MODIFY_BY_ID' => $currentUserID))
	);
}

$arResult['~STAGE_LIST_WRITE']= CCrmStatus::GetStatusList('DEAL_STAGE');
$arResult['STAGE_LIST_WRITE'] = array();
foreach ($arResult['~STAGE_LIST_WRITE'] as $sStatusId => $sStatusTitle)
{
	if ($CCrmPerms->GetPermType('DEAL', 'WRITE', array('STAGE_ID'.$sStatusId)) > BX_CRM_PERM_NONE)
		$arResult['STAGE_LIST_WRITE'][$sStatusId] = $sStatusTitle;
}

$arResult['HEADERS'] = array(
	array('id' => 'ID', 'name' => GetMessage('CRM_COLUMN_ID'), 'sort' => 'id', 'default' => false, 'editable' => false, 'type' => 'int', 'class' => 'minimal'),
	array('id' => 'DEAL_SUMMARY', 'name' => GetMessage('CRM_COLUMN_DEAL'), 'sort' => 'title', 'default' => true, 'editable' => false),
	array('id' => 'STAGE_ID', 'name' => GetMessage('CRM_COLUMN_STAGE_ID'), 'sort' => 'stage_sort', 'default' => true, 'editable' => array('items' => $arResult['STAGE_LIST_WRITE']), 'type' => 'list')
);

// Dont display activities in INTERNAL mode.
if(!$bInternal)
{
	$arResult['HEADERS'][] = array('id' => 'ACTIVITY_ID', 'name' => GetMessage('CRM_COLUMN_ACTIVITY'), 'sort' => 'nearest_activity', 'default' => true);
}

$arResult['HEADERS'] = array_merge(
	$arResult['HEADERS'],
	array(
		array('id' => 'DEAL_CLIENT', 'name' => GetMessage('CRM_COLUMN_CLIENT'), 'sort' => 'deal_client', 'default' => true, 'editable' => false),
		array('id' => 'PROBABILITY', 'name' => GetMessage('CRM_COLUMN_PROBABILITY'), 'sort' => 'probability', 'default' => false, 'editable' => true, 'align' => 'right'),
		array('id' => 'SUM', 'name' => GetMessage('CRM_COLUMN_SUM'), 'sort' => 'opportunity_account', 'default' => true, 'editable' => false, 'align' => 'right'),
		array('id' => 'ASSIGNED_BY', 'name' => GetMessage('CRM_COLUMN_ASSIGNED_BY'), 'sort' => 'assigned_by', 'default' => true, 'editable' => false, 'class' => 'username'),
		array('id' => 'ORIGINATOR_ID', 'name' => GetMessage('CRM_COLUMN_BINDING'), 'sort' => false, 'default' => false, 'editable' => array('items' => $arExternalSales), 'type' => 'list'),

		array('id' => 'TITLE', 'name' => GetMessage('CRM_COLUMN_TITLE'), 'sort' => 'title', 'default' => false, 'editable' => true),
		array('id' => 'TYPE_ID', 'name' => GetMessage('CRM_COLUMN_TYPE_ID'), 'sort' => 'type_id', 'default' => false, 'editable' => array('items' => CCrmStatus::GetStatusList('DEAL_TYPE')), 'type' => 'list'),
		array('id' => 'OPPORTUNITY', 'name' => GetMessage('CRM_COLUMN_OPPORTUNITY'), 'sort' => 'opportunity', 'default' => false, 'editable' => true, 'align' => 'right'),
		array('id' => 'CURRENCY_ID', 'name' => GetMessage('CRM_COLUMN_CURRENCY_ID'), 'sort' => 'currency_id', 'default' => false, 'editable' => array('items' => CCrmCurrencyHelper::PrepareListItems()), 'type' => 'list'),
		array('id' => 'COMPANY_ID', 'name' => GetMessage('CRM_COLUMN_COMPANY_ID'), 'sort' => 'company_id', 'default' => false, 'editable' => false),
		array('id' => 'CONTACT_ID', 'name' => GetMessage('CRM_COLUMN_CONTACT_ID'), 'sort' => 'contact_full_name', 'default' => false, 'editable' => false),

		array('id' => 'CLOSED', 'name' => GetMessage('CRM_COLUMN_CLOSED'), 'sort' => 'closed', 'align' => 'center', 'default' => false, 'editable' => array('items' => array('' => '', 'Y' => GetMessage('MAIN_YES'), 'N' => GetMessage('MAIN_NO'))), 'type' => 'list'),
		array('id' => 'DATE_CREATE', 'name' => GetMessage('CRM_COLUMN_DATE_CREATE'), 'sort' => 'date_create', 'default' => false, 'class' => 'date'),
		array('id' => 'CREATED_BY', 'name' => GetMessage('CRM_COLUMN_CREATED_BY'), 'sort' => 'created_by', 'default' => false, 'editable' => false, 'class' => 'username'),
		array('id' => 'DATE_MODIFY', 'name' => GetMessage('CRM_COLUMN_DATE_MODIFY'), 'sort' => 'date_modify', 'default' => false, 'class' => 'date'),
		array('id' => 'MODIFY_BY', 'name' => GetMessage('CRM_COLUMN_MODIFY_BY'), 'sort' => 'modify_by', 'default' => false, 'editable' => false, 'class' => 'username'),
		array('id' => 'BEGINDATE', 'name' => GetMessage('CRM_COLUMN_BEGINDATE'), 'sort' => 'begindate', 'default' => false, 'editable' => true, 'type' => 'date', 'class' => 'date'),
		array('id' => 'CLOSEDATE', 'name' => GetMessage('CRM_COLUMN_CLOSEDATE'), 'sort' => 'closedate', 'default' => false, 'editable' => true, 'type' => 'date'),
		array('id' => 'PRODUCT_ID', 'name' => GetMessage('CRM_COLUMN_PRODUCT_ID'), 'sort' => false, 'default' => $isInExportMode, 'editable' => false, 'type' => 'list'),
		array('id' => 'COMMENTS', 'name' => GetMessage('CRM_COLUMN_COMMENTS'), 'sort' => false /*because of MSSQL*/, 'default' => false, 'editable' => false),
		array('id' => 'EVENT_DATE', 'name' => GetMessage('CRM_COLUMN_EVENT_DATE'), 'sort' => 'event_date', 'default' => false),
		array('id' => 'EVENT_ID', 'name' => GetMessage('CRM_COLUMN_EVENT_ID'), 'sort' => 'event_id', 'default' => false, 'editable' => array('items' => CCrmStatus::GetStatusList('EVENT_TYPE')), 'type' => 'list'),
		array('id' => 'EVENT_DESCRIPTION', 'name' => GetMessage('CRM_COLUMN_EVENT_DESCRIPTION'), 'sort' => false, 'default' => false, 'editable' => false),
		//	array('id' => 'STATE_ID', 'name' => GetMessage('CRM_COLUMN_STATE_ID'), 'sort' => 'state_id', 'default' => false, 'editable' => array('items' => CCrmStatus::GetStatusList('DEAL_STATE')), 'type' => 'list'),
	)
);

$CCrmUserType->ListAddHeaders($arResult['HEADERS']);
if (IsModuleInstalled('bizproc'))
{
	$arBPData = CBPDocument::GetWorkflowTemplatesForDocumentType(array('crm', 'CCrmDocumentDeal', 'DEAL'));
	$arDocumentStates = CBPDocument::GetDocumentStates(
		array('crm', 'CCrmDocumentDeal', 'DEAL'),
		null
	);
	foreach ($arBPData as $arBP)
	{
		if (!CBPDocument::CanUserOperateDocumentType(
			CBPCanUserOperateOperation::StartWorkflow,
			$userID,
			array('crm', 'CCrmDocumentDeal', 'DEAL'),
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

if (intval($arParams['DEAL_COUNT']) <= 0)
	$arParams['DEAL_COUNT'] = 20;

$arNavParams = array(
	'nPageSize' => $arParams['DEAL_COUNT']
);

$arNavigation = CDBResult::GetNavParams($arNavParams);
$CGridOptions = new CCrmGridOptions($arResult['GRID_ID'], $arResult['FILTER_PRESETS']);
$arNavParams = $CGridOptions->GetNavParams($arNavParams);
$arNavParams['bShowAll'] = false;

if(!$enableWidgetFilter)
{
	$arFilter += $CGridOptions->GetFilter($arResult['FILTER']);
}

if(isset($arFilter['CLOSEDATE_datesel']) && $arFilter['CLOSEDATE_datesel'] === 'days' && isset($arFilter['CLOSEDATE_from']))
{
	//Issue #58007 - limit max CLOSEDATE
	$arFilter['CLOSEDATE_to'] = ConvertTimeStamp(strtotime(date("Y-m-d", time())));
}

$USER_FIELD_MANAGER->AdminListAddFilter(CCrmDeal::$sUFEntityID, $arFilter);

// converts data from filter
if (isset($arFilter['FIND_list']) && !empty($arFilter['FIND']))
{
	if ($arFilter['FIND_list'] == 't_n_ln')
	{
		$arFilter['TITLE'] = $arFilter['FIND'];
		$arFilter['NAME'] = $arFilter['FIND'];
		$arFilter['LAST_NAME'] = $arFilter['FIND'];
		$arFilter['LOGIC'] = 'OR';
	}
	else
		$arFilter[strtoupper($arFilter['FIND_list'])] = $arFilter['FIND'];
	unset($arFilter['FIND_list'], $arFilter['FIND']);
}

CCrmEntityHelper::PrepareMultiFieldFilter($arFilter);
$arImmutableFilters = array('FM', 'ID', 'ASSIGNED_BY_ID', 'CURRENCY_ID', 'CONTACT_ID', 'COMPANY_ID', 'CREATED_BY_ID', 'MODIFY_BY_ID', 'PRODUCT_ROW_PRODUCT_ID');
foreach ($arFilter as $k => $v)
{
	if(in_array($k, $arImmutableFilters, true))
	{
		continue;
	}

	$arMatch = array();

	if(in_array($k, array('PRODUCT_ID', 'TYPE_ID', 'STAGE_ID', 'COMPANY_ID', 'CONTACT_ID')))
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
	elseif (strpos($k, 'UF_') !== 0 && strpos($k, '__') !== 0 && $k != 'LOGIC')
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

				$obRes = CCrmDeal::GetListEx(array(), $arFilterDel, false, false, array('ID'));
				while($arDeal = $obRes->Fetch())
				{
					$ID = $arDeal['ID'];
					$arEntityAttr = $CCrmPerms->GetEntityAttr('DEAL', array($ID));
					if (!$CCrmPerms->CheckEnityAccess('DEAL', 'DELETE', $arEntityAttr[$ID]))
					{
						continue ;
					}

					$DB->StartTransaction();

					if ($CCrmBizProc->Delete($ID, $arEntityAttr[$ID])
						&& $CCrmDeal->Delete($ID, array('PROCESS_BIZPROC' => false)))
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
					$arEntityAttr = $CCrmPerms->GetEntityAttr('DEAL', array($ID));
					if (!$CCrmPerms->CheckEnityAccess('DEAL', 'WRITE', $arEntityAttr[$ID]))
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

						if($CCrmDeal->Update($ID, $arUpdateData))
						{
							$DB->Commit();

							$arErrors = array();
							CCrmBizProcHelper::AutoStartWorkflows(
								CCrmOwnerType::Deal,
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
					$arTaskID[] = 'D_'.$ID;
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
						'back_url' => urlencode($arParams['PATH_TO_DEAL_LIST'])
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
		elseif ($actionData['NAME'] == 'set_stage')
		{
			if(isset($actionData['STAGE_ID']) && $actionData['STAGE_ID'] != '') // Fix for issue #26628
			{
				$arIDs = array();
				if ($actionData['ALL_ROWS'])
				{
					$arActionFilter = $arFilter;
					$arActionFilter['CHECK_PERMISSIONS'] = 'N'; // Ignore 'WRITE' permission - we will check it before update.

					$dbRes = CCrmDeal::GetListEx(array(), $arActionFilter, false, false, array('ID'));
					while($arDeal = $dbRes->Fetch())
					{
						$arIDs[] = $arDeal['ID'];
					}
				}
				elseif (isset($actionData['ID']) && is_array($actionData['ID']))
				{
					$arIDs = $actionData['ID'];
				}

				$arEntityAttr = $CCrmPerms->GetEntityAttr('DEAL', $arIDs);
				foreach($arIDs as $ID)
				{
					if (!$CCrmPerms->CheckEnityAccess('DEAL', 'WRITE', $arEntityAttr[$ID]))
					{
						continue;
					}

					$DB->StartTransaction();

					$arUpdateData = array(
						'STAGE_ID' => $actionData['STAGE_ID']
					);

					if($CCrmDeal->Update($ID, $arUpdateData, true, true, array('DISABLE_USER_FIELD_CHECK' => true)))
					{
						$DB->Commit();

						$arErrors = array();
						CCrmBizProcHelper::AutoStartWorkflows(
							CCrmOwnerType::Deal,
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
					$dbRes = CCrmDeal::GetListEx(array(), $arActionFilter, false, false, array('ID'));
					while($arDeal = $dbRes->Fetch())
					{
						$arIDs[] = $arDeal['ID'];
					}
				}
				elseif (isset($actionData['ID']) && is_array($actionData['ID']))
				{
					$arIDs = $actionData['ID'];
				}

				$arEntityAttr = $CCrmPerms->GetEntityAttr('DEAL', $arIDs);

				foreach($arIDs as $ID)
				{
					if (!$CCrmPerms->CheckEnityAccess('DEAL', 'WRITE', $arEntityAttr[$ID]))
					{
						continue;
					}

					$DB->StartTransaction();

					$arUpdateData = array(
						'ASSIGNED_BY_ID' => $actionData['ASSIGNED_BY_ID']
					);

					if($CCrmDeal->Update($ID, $arUpdateData, true, true, array('DISABLE_USER_FIELD_CHECK' => true)))
					{
						$DB->Commit();

						$arErrors = array();
						CCrmBizProcHelper::AutoStartWorkflows(
							CCrmOwnerType::Deal,
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

							$dbRes = CCrmDeal::GetListEx(
								array(),
								$arActionFilter,
								false,
								false,
								array('ID', 'OPENED')
							);

							while($arDeal = $dbRes->Fetch())
							{
								if(isset($arDeal['OPENED']) && $arDeal['OPENED'] === $isOpened)
								{
									continue;
								}

								$arIDs[] = $arDeal['ID'];
							}
						}
						elseif (isset($actionData['ID']) && is_array($actionData['ID']))
						{
							$dbRes = CCrmDeal::GetListEx(
								array(),
								array(
									'@ID'=> $actionData['ID'],
									'CHECK_PERMISSIONS' => 'N'
								),
								false,
								false,
								array('ID', 'OPENED')
							);

							while($arDeal = $dbRes->Fetch())
							{
								if(isset($arDeal['OPENED']) && $arDeal['OPENED'] === $isOpened)
								{
									continue;
								}

								$arIDs[] = $arDeal['ID'];
							}
						}

						$arEntityAttr = $CCrmPerms->GetEntityAttr('DEAL', $arIDs);
						foreach($arIDs as $ID)
						{
							if (!$CCrmPerms->CheckEnityAccess('DEAL', 'WRITE', $arEntityAttr[$ID]))
							{
								continue;
							}

							$DB->StartTransaction();
							$arUpdateData = array('OPENED' => $isOpened);
							if($CCrmDeal->Update($ID, $arUpdateData, true, true, array('DISABLE_USER_FIELD_CHECK' => true)))
							{
								$DB->Commit();

								CCrmBizProcHelper::AutoStartWorkflows(
									CCrmOwnerType::Deal,
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
			LocalRedirect($arParams['PATH_TO_DEAL_LIST']);
		}
	}
	else//if ($actionData['METHOD'] == 'GET')
	{
		if ($actionData['NAME'] == 'delete' && isset($actionData['ID']))
		{
			$ID = intval($actionData['ID']);

			$arEntityAttr = $CCrmPerms->GetEntityAttr('DEAL', array($ID));
			$attr = $arEntityAttr[$ID];

			if($CCrmPerms->CheckEnityAccess('DEAL', 'DELETE', $attr))
			{
				$DB->StartTransaction();

				if($CCrmBizProc->Delete($ID, $attr)
					&& $CCrmDeal->Delete($ID, array('PROCESS_BIZPROC' => false)))
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
			LocalRedirect($bInternal ? '?'.$arParams['FORM_ID'].'_active_tab=tab_deal' : $arParams['PATH_TO_DEAL_LIST']);
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
	'sort' => array('nearest_activity' => 'asc'),
	'vars' => array('by' => 'by', 'order' => 'order')
));
$arResult['SORT'] = !empty($arSort) ? $arSort : $_arSort['sort'];
$arResult['SORT_VARS'] = $_arSort['vars'];

// Remove column for deleted UF
$arSelect = $CGridOptions->GetVisibleColumns();

if ($CCrmUserType->NormalizeFields($arSelect))
	$CGridOptions->SetVisibleColumns($arSelect);

$arResult['ENABLE_BIZPROC'] = IsModuleInstalled('bizproc');
$arResult['ENABLE_TASK'] = IsModuleInstalled('tasks');
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

	//Disable bizproc fields processing
	$arResult['ENABLE_BIZPROC'] = false;
}
else
{
	if($arResult['ENABLE_BIZPROC'])
	{
		//Check if bizproc fields selected
		$hasBizprocFields = false;
		foreach($arSelect as &$fieldName)
		{
			if(substr($fieldName, 0, 8) === 'BIZPROC_')
			{
				$hasBizprocFields = true;
				break;
			}
		}
		$arResult['ENABLE_BIZPROC'] = $hasBizprocFields;
	}
	unset($fieldName);
}

$arSelectedHeaders = $arSelect;

if(!in_array('TITLE', $arSelect, true))
{
	//Is required for activities management
	$arSelect[] = 'TITLE';
}

if(in_array('CREATED_BY', $arSelect, true))
{
	$arSelect[] = 'CREATED_BY_LOGIN';
	$arSelect[] = 'CREATED_BY_NAME';
	$arSelect[] = 'CREATED_BY_LAST_NAME';
	$arSelect[] = 'CREATED_BY_SECOND_NAME';
}

if(in_array('MODIFY_BY', $arSelect, true))
{
	$arSelect[] = 'MODIFY_BY_LOGIN';
	$arSelect[] = 'MODIFY_BY_NAME';
	$arSelect[] = 'MODIFY_BY_LAST_NAME';
	$arSelect[] = 'MODIFY_BY_SECOND_NAME';
}

if(in_array('DEAL_SUMMARY', $arSelect, true))
{
	//$arSelect[] = 'TITLE';
	$arSelect[] = 'TYPE_ID';
}

if(in_array('ACTIVITY_ID', $arSelect, true))
{
	$arSelect[] = 'ACTIVITY_TIME';
	$arSelect[] = 'ACTIVITY_SUBJECT';
	$arSelect[] = 'C_ACTIVITY_ID';
	$arSelect[] = 'C_ACTIVITY_TIME';
	$arSelect[] = 'C_ACTIVITY_SUBJECT';
	$arSelect[] = 'C_ACTIVITY_RESP_ID';
	$arSelect[] = 'C_ACTIVITY_RESP_LOGIN';
	$arSelect[] = 'C_ACTIVITY_RESP_NAME';
	$arSelect[] = 'C_ACTIVITY_RESP_LAST_NAME';
	$arSelect[] = 'C_ACTIVITY_RESP_SECOND_NAME';
}

if(in_array('SUM', $arSelect, true))
{
	$arSelect[] = 'OPPORTUNITY';
	$arSelect[] = 'CURRENCY_ID';
}

if(in_array('DEAL_CLIENT', $arSelect, true))
{
	$arSelect[] = 'CONTACT_ID';
	$arSelect[] = 'COMPANY_ID';
	$arSelect[] = 'COMPANY_TITLE';
	$arSelect[] = 'CONTACT_NAME';
	$arSelect[] = 'CONTACT_SECOND_NAME';
	$arSelect[] = 'CONTACT_LAST_NAME';
}
else
{
	if(in_array('CONTACT_ID', $arSelect, true))
	{
		$arSelect[] = 'CONTACT_NAME';
		$arSelect[] = 'CONTACT_SECOND_NAME';
		$arSelect[] = 'CONTACT_LAST_NAME';
	}
	if(in_array('COMPANY_ID', $arSelect, true))
	{
		$arSelect[] = 'COMPANY_TITLE';
	}
}

// Always need to remove the menu items
if (!in_array('STAGE_ID', $arSelect))
	$arSelect[] = 'STAGE_ID';

// For bizproc
if (!in_array('ASSIGNED_BY', $arSelect))
	$arSelect[] = 'ASSIGNED_BY';

// For preparing user html
if (!in_array('ASSIGNED_BY_LOGIN', $arSelect))
	$arSelect[] = 'ASSIGNED_BY_LOGIN';

if (!in_array('ASSIGNED_BY_NAME', $arSelect))
	$arSelect[] = 'ASSIGNED_BY_NAME';

if (!in_array('ASSIGNED_BY_LAST_NAME', $arSelect))
	$arSelect[] = 'ASSIGNED_BY_LAST_NAME';

if (!in_array('ASSIGNED_BY_SECOND_NAME', $arSelect))
	$arSelect[] = 'ASSIGNED_BY_SECOND_NAME';

// ID must present in select
if(!in_array('ID', $arSelect))
{
	$arSelect[] = 'ID';
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
			'DEAL_SUMMARY' => array(
				'TITLE',
				'TYPE_ID'
			),
			'DEAL_CLIENT' => array(
				'CONTACT_ID',
				'COMPANY_ID'
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
	$arSelect = array(
		'DATE_CREATE', 'TITLE', 'STAGE_ID', 'TYPE_ID',
		'OPPORTUNITY', 'CURRENCY_ID', 'COMMENTS',
		'CONTACT_ID', 'CONTACT_NAME', 'CONTACT_SECOND_NAME',
		'CONTACT_LAST_NAME', 'COMPANY_ID', 'COMPANY_TITLE'
	);
	$nTopCount = $arParams['DEAL_COUNT'];
}

if($nTopCount > 0)
{
	$arNavParams['nTopCount'] = $nTopCount;
}

if ($isInExportMode)
	$arFilter['PERMISSION'] = 'EXPORT';

// HACK: Make custom sort for ASSIGNED_BY field
$arSort = $arResult['SORT'];
if(isset($arSort['assigned_by']))
{
	$arSort['assigned_by_last_name'] = $arSort['assigned_by'];
	$arSort['assigned_by_name'] = $arSort['assigned_by'];
	$arSort['assigned_by_login'] = $arSort['assigned_by'];
	unset($arSort['assigned_by']);
}

$arOptions = array('FIELD_OPTIONS' => array('ADDITIONAL_FIELDS' => array()));
if(in_array('ACTIVITY_ID', $arSelect, true))
{
	$arOptions['FIELD_OPTIONS']['ADDITIONAL_FIELDS'][] = 'ACTIVITY';
}
if(isset($arSort['stage_sort']))
{
	$arOptions['FIELD_OPTIONS']['ADDITIONAL_FIELDS'][] = 'STAGE_SORT';
}

if(isset($arSort['contact_full_name']))
{
	$arSort['contact_last_name'] = $arSort['contact_full_name'];
	$arSort['contact_name'] = $arSort['contact_full_name'];
	unset($arSort['contact_full_name']);
}
if(isset($arSort['deal_client']))
{
	$arSort['contact_last_name'] = $arSort['deal_client'];
	$arSort['contact_name'] = $arSort['deal_client'];
	$arSort['company_title'] = $arSort['deal_client'];
	unset($arSort['deal_client']);
}

if(isset($arParams['IS_EXTERNAL_CONTEXT']))
{
	$arOptions['IS_EXTERNAL_CONTEXT'] = $arParams['IS_EXTERNAL_CONTEXT'];
}

//FIELD_OPTIONS
$arSelect = array_unique($arSelect, SORT_STRING);

$arResult['DEAL'] = array();
$arResult['DEAL_ID'] = array();
$arResult['DEAL_UF'] = array();

if(!isset($arSort['nearest_activity']))
{
	$dbResult = CCrmDeal::GetListEx($arSort, $arFilter, false, (!$isInExportMode ? $arNavParams : false), $arSelect, $arOptions);

	$arResult['ROWS_COUNT'] = $dbResult->SelectedRowsCount();
	$arResult['DB_LIST'] = $dbResult;
	$arResult['DB_FILTER'] = $arFilter;

	while($arDeal = $dbResult->GetNext())
	{
		$arResult['DEAL'][$arDeal['ID']] = $arDeal;
		$arResult['DEAL_ID'][$arDeal['ID']] = $arDeal['ID'];
		$arResult['DEAL_UF'][$arDeal['ID']] = array();
	}
}
else
{
	$navDbResult = CCrmActivity::GetEntityList(
		CCrmOwnerType::Deal,
		$userID,
		$arSort['nearest_activity'],
		$arFilter,
		(!$isInExportMode ? $arNavParams : false)
	);

	$arResult['ROWS_COUNT'] = $navDbResult->SelectedRowsCount();
	$arResult['DB_LIST'] = $navDbResult;
	$arResult['DB_FILTER'] = $arFilter;

	while($arDeal = $navDbResult->Fetch())
	{
		$arResult['DEAL'][$arDeal['ID']] = $arDeal;
		$arResult['DEAL_ID'][$arDeal['ID']] = $arDeal['ID'];
		$arResult['DEAL_UF'][$arDeal['ID']] = array();
	}

	$arFilter['@ID'] = array_keys($arResult['DEAL']);
	$dbResult = CCrmDeal::GetListEx($arSort, $arFilter, false, false, $arSelect, $arOptions);

	while($arDeal = $dbResult->GetNext())
	{
		$arResult['DEAL'][$arDeal['ID']] = $arDeal;
	}
}

$now = time() + CTimeZone::GetOffset();
foreach($arResult['DEAL'] as &$arDeal)
{
	$arDeal['CLOSEDATE'] = !empty($arDeal['CLOSEDATE']) ? CCrmComponentHelper::TrimDateTimeString(ConvertTimeStamp(MakeTimeStamp($arDeal['CLOSEDATE']), 'SHORT', SITE_ID)) : '';
	$arDeal['BEGINDATE'] = !empty($arDeal['BEGINDATE']) ? CCrmComponentHelper::TrimDateTimeString(ConvertTimeStamp(MakeTimeStamp($arDeal['BEGINDATE']), 'SHORT', SITE_ID)) : '';
	$arDeal['EVENT_DATE'] = !empty($arDeal['EVENT_DATE']) ? CCrmComponentHelper::TrimDateTimeString(ConvertTimeStamp(MakeTimeStamp($arDeal['EVENT_DATE']), 'SHORT', SITE_ID)) : '';
	$arDeal['~CLOSEDATE'] = $arDeal['CLOSEDATE'];
	$arDeal['~BEGINDATE'] = $arDeal['BEGINDATE'];
	$arDeal['~EVENT_DATE'] = $arDeal['EVENT_DATE'];

	$currencyID =  isset($arDeal['~CURRENCY_ID']) ? $arDeal['~CURRENCY_ID'] : CCrmCurrency::GetBaseCurrencyID();
	$arDeal['~CURRENCY_ID'] = $currencyID;
	$arDeal['CURRENCY_ID'] = htmlspecialcharsbx($currencyID);

	$arDeal['FORMATTED_OPPORTUNITY'] = CCrmCurrency::MoneyToString($arDeal['~OPPORTUNITY'], $arDeal['~CURRENCY_ID']);

	$entityID = $arDeal['ID'];

	$arDeal['PATH_TO_DEAL_SHOW'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_SHOW'],
		array(
			'deal_id' => $entityID
		)
	);
	$arDeal['PATH_TO_DEAL_EDIT'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_EDIT'],
		array(
			'deal_id' => $entityID
		)
	);
	$arDeal['PATH_TO_DEAL_COPY'] =  CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_EDIT'],
		array(
			'deal_id' => $entityID
		)),
		array('copy' => 1)
	);
	$arDeal['PATH_TO_DEAL_DELETE'] =  CHTTP::urlAddParams(
		$bInternal ? $APPLICATION->GetCurPage() : $arParams['PATH_TO_DEAL_LIST'],
		array('action_'.$arResult['GRID_ID'] => 'delete', 'ID' => $entityID, 'sessid' => $arResult['SESSION_ID'])
	);

	$contactID = isset($arDeal['~CONTACT_ID']) ? intval($arDeal['~CONTACT_ID']) : 0;
	$arDeal['PATH_TO_CONTACT_SHOW'] = $contactID <= 0 ? ''
		: CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_SHOW'], array('contact_id' => $contactID));

	$arDeal['~CONTACT_FORMATTED_NAME'] = $contactID <= 0 ? ''
		: CUser::FormatName(
			\Bitrix\Crm\Format\PersonNameFormatter::getFormat(),
			array(
				'LOGIN' => '',
				'NAME' => isset($arDeal['~CONTACT_NAME']) ? $arDeal['~CONTACT_NAME'] : '',
				'LAST_NAME' => isset($arDeal['~CONTACT_LAST_NAME']) ? $arDeal['~CONTACT_LAST_NAME'] : '',
				'SECOND_NAME' => isset($arDeal['~CONTACT_SECOND_NAME']) ? $arDeal['~CONTACT_SECOND_NAME'] : ''
			),
			false, false
		);
	$arDeal['CONTACT_FORMATTED_NAME'] = htmlspecialcharsbx($arDeal['~CONTACT_FORMATTED_NAME']);

	$arDeal['~CONTACT_FULL_NAME'] = CCrmContact::GetFullName(
		array(
			'NAME' => isset($arDeal['CONTACT_NAME']) ? $arDeal['CONTACT_NAME'] : '',
			'LAST_NAME' => isset($arDeal['CONTACT_LAST_NAME']) ? $arDeal['CONTACT_LAST_NAME'] : '',
			'SECOND_NAME' => isset($arDeal['CONTACT_SECOND_NAME']) ? $arDeal['CONTACT_SECOND_NAME'] : ''
		),
		false
	);
	$arDeal['CONTACT_FULL_NAME'] = htmlspecialcharsbx($arDeal['~CONTACT_FULL_NAME']);

	$companyID = isset($arDeal['~COMPANY_ID']) ? intval($arDeal['~COMPANY_ID']) : 0;
	$arDeal['PATH_TO_COMPANY_SHOW'] = $companyID <= 0 ? ''
		: CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_SHOW'], array('company_id' => $companyID));

	$arDeal['PATH_TO_USER_PROFILE'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_PROFILE'],
		array(
			'user_id' => $arDeal['ASSIGNED_BY']
		)
	);
	$arDeal['PATH_TO_USER_BP'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_BP'],
		array(
			'user_id' => $userID
		)
	);

	$arDeal['PATH_TO_USER_CREATOR'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_PROFILE'],
		array(
			'user_id' => $arDeal['CREATED_BY']
		)
	);

	$arDeal['PATH_TO_USER_MODIFIER'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_PROFILE'],
		array(
			'user_id' => $arDeal['MODIFY_BY']
		)
	);

	$arDeal['CREATED_BY_FORMATTED_NAME'] = CUser::FormatName(
		$arParams['NAME_TEMPLATE'],
		array(
			'LOGIN' => $arDeal['CREATED_BY_LOGIN'],
			'NAME' => $arDeal['CREATED_BY_NAME'],
			'LAST_NAME' => $arDeal['CREATED_BY_LAST_NAME'],
			'SECOND_NAME' => $arDeal['CREATED_BY_SECOND_NAME']
		),
		true, false
	);

	$arDeal['MODIFY_BY_FORMATTED_NAME'] = CUser::FormatName(
		$arParams['NAME_TEMPLATE'],
		array(
			'LOGIN' => $arDeal['MODIFY_BY_LOGIN'],
			'NAME' => $arDeal['MODIFY_BY_NAME'],
			'LAST_NAME' => $arDeal['MODIFY_BY_LAST_NAME'],
			'SECOND_NAME' => $arDeal['MODIFY_BY_SECOND_NAME']
		),
		true, false
	);

	$typeID = isset($arDeal['TYPE_ID']) ? $arDeal['TYPE_ID'] : '';
	$arDeal['DEAL_TYPE_NAME'] = isset($arResult['TYPE_LIST'][$typeID]) ? $arResult['TYPE_LIST'][$typeID] : $typeID;

	$stageID = isset($arDeal['STAGE_ID']) ? $arDeal['STAGE_ID'] : '';
	$arDeal['DEAL_STAGE_NAME'] = isset($arResult['STAGE_LIST'][$stageID]) ? $arResult['STAGE_LIST'][$stageID] : $stageID;

	if(isset($arDeal['~ACTIVITY_TIME']))
	{
		$time = MakeTimeStamp($arDeal['~ACTIVITY_TIME']);
		$arDeal['~ACTIVITY_EXPIRED'] = $time <= $now;
		$arDeal['~ACTIVITY_IS_CURRENT_DAY'] = $arDeal['~ACTIVITY_EXPIRED'] || CCrmActivity::IsCurrentDay($time);
	}

	$originatorID = isset($arDeal['~ORIGINATOR_ID']) ? $arDeal['~ORIGINATOR_ID'] : '';
	if($originatorID !== '')
	{
		$arDeal['~ORIGINATOR_NAME'] = isset($arResult['EXTERNAL_SALES'][$originatorID])
			? $arResult['EXTERNAL_SALES'][$originatorID] : '';

		$arDeal['ORIGINATOR_NAME'] = htmlspecialcharsbx($arDeal['~ORIGINATOR_NAME']);
	}

	if ($arResult['ENABLE_TASK'])
	{
		$arDeal['PATH_TO_TASK_EDIT'] = CHTTP::urlAddParams(
			CComponentEngine::MakePathFromTemplate(COption::GetOptionString('tasks', 'paths_task_user_edit', ''),
				array(
					'task_id' => 0,
					'user_id' => $userID
				)
			),
			array(
				'UF_CRM_TASK' => 'D_'.$entityID,
				'TITLE' => urlencode(GetMessage('CRM_TASK_TITLE_PREFIX')),
				'TAGS' => urlencode(GetMessage('CRM_TASK_TAG')),
				'back_url' => urlencode($arParams['PATH_TO_DEAL_LIST'])
			)
		);
	}

	if (IsModuleInstalled('sale'))
	{
		$arDeal['PATH_TO_QUOTE_ADD'] =
			CHTTP::urlAddParams(CComponentEngine::makePathFromTemplate(
					$arParams['PATH_TO_QUOTE_EDIT'], array('quote_id' => 0)),
				array('deal_id' => $entityID)
			);
		$arDeal['PATH_TO_INVOICE_ADD'] =
			CHTTP::urlAddParams(CComponentEngine::makePathFromTemplate(
				$arParams['PATH_TO_INVOICE_EDIT'], array('invoice_id' => 0)),
				array('deal' => $entityID)
			);
	}

	if ($arResult['ENABLE_BIZPROC'])
	{
		$arDeal['BIZPROC_STATUS'] = '';
		$arDeal['BIZPROC_STATUS_HINT'] = '';

		$arDocumentStates = CBPDocument::GetDocumentStates(
			array('crm', 'CCrmDocumentDeal', 'DEAL'),
			array('crm', 'CCrmDocumentDeal', 'DEAL_'.$entityID)
		);

		$arDeal['PATH_TO_BIZPROC_LIST'] =  CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_SHOW'],
			array(
				'deal_id' => $entityID
			)),
			array('CRM_DEAL_SHOW_V12_active_tab' => 'tab_bizproc')
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
				$arDeal[$paramName] = $docTtl;
			}
			else
			{
				$arDeal[$paramName] = '<a href="'.htmlspecialcharsbx($arDeal['PATH_TO_BIZPROC_LIST']).'">'.htmlspecialcharsbx($docTtl).'</a>';

				$docID = $arDocState['ID'];
				$taskQty = CCrmBizProcHelper::GetUserWorkflowTaskCount(array($docID), $userID);
				if($taskQty > 0)
				{
					$totalTaskQty += $taskQty;
				}

				$arDeal['BIZPROC_STATUS'] = $taskQty > 0 ? 'attention' : 'inprogress';
				$arDeal['BIZPROC_STATUS_HINT'] =
					'<div class=\'bizproc-item-title\'>'.
						htmlspecialcharsbx($docTemplateName !== '' ? $docTemplateName : GetMessage('CRM_BPLIST')).
						': <span class=\'bizproc-item-title bizproc-state-title\'><a href=\''.$arDeal['PATH_TO_BIZPROC_LIST'].'\'>'.
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
					$arDeal[$paramName] = $docTtl;
				}
				else
				{
					$arDeal[$paramName] = '<a href="'.htmlspecialcharsbx($arDeal['PATH_TO_BIZPROC_LIST']).'">'.htmlspecialcharsbx($docTtl).'</a>';

					$docID = $arDocState['ID'];
					//TODO: wait for bizproc bugs will be fixed and replace serial call of CCrmBizProcHelper::GetUserWorkflowTaskCount on single call
					$taskQty = CCrmBizProcHelper::GetUserWorkflowTaskCount(array($docID), $userID);
					if($taskQty === 0)
					{
						continue;
					}

					if ($arDeal['BIZPROC_STATUS'] !== 'attention')
					{
						$arDeal['BIZPROC_STATUS'] = 'attention';
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
				$arDeal['BIZPROC_STATUS_HINT'] =
					'<span class=\'bizproc-item-title\'>'.GetMessage('CRM_BP_R_P').': <a href=\''.$arDeal['PATH_TO_BIZPROC_LIST'].'\' title=\''.GetMessage('CRM_BP_R_P_TITLE').'\'>'.$docStatesQty.'</a></span>'.
					($totalTaskQty === 0
						? ''
						: '<br /><span class=\'bizproc-item-title\'>'.GetMessage('CRM_TASKS').': <a href=\''.$arDeal['PATH_TO_USER_BP'].'\' title=\''.GetMessage('CRM_TASKS_TITLE').'\'>'.$totalTaskQty.($totalTaskQty > 5 ? '+' : '').'</a></span>');
			}
		}
	}

	$arDeal['ASSIGNED_BY_ID'] = $arDeal['~ASSIGNED_BY_ID'] = intval($arDeal['ASSIGNED_BY']);
	$arDeal['ASSIGNED_BY'] = CUser::FormatName(
		$arParams['NAME_TEMPLATE'],
		array(
			'LOGIN' => $arDeal['ASSIGNED_BY_LOGIN'],
			'NAME' => $arDeal['ASSIGNED_BY_NAME'],
			'LAST_NAME' => $arDeal['ASSIGNED_BY_LAST_NAME'],
			'SECOND_NAME' => $arDeal['ASSIGNED_BY_SECOND_NAME']
		),
		true, false
	);

	$arResult['DEAL'][$entityID] = $arDeal;
	$arResult['DEAL_UF'][$entityID] = array();
	$arResult['DEAL_ID'][$entityID] = $entityID;
}
unset($arDeal);

$CCrmUserType->ListAddEnumFieldsValue(
	$arResult,
	$arResult['DEAL'],
	$arResult['DEAL_UF'],
	($isInExportMode ? ', ' : '<br />'),
	$isInExportMode,
	array(
		'FILE_URL_TEMPLATE' =>
			'/bitrix/components/bitrix/crm.deal.show/show_file.php?ownerId=#owner_id#&fieldName=#field_name#&fileId=#file_id#'
	)
);

$arResult['ENABLE_TOOLBAR'] = isset($arParams['ENABLE_TOOLBAR']) ? $arParams['ENABLE_TOOLBAR'] : false;
if($arResult['ENABLE_TOOLBAR'])
{
	$arResult['PATH_TO_DEAL_ADD'] = CComponentEngine::MakePathFromTemplate(
		$arParams['PATH_TO_DEAL_EDIT'],
		array('deal_id' => 0)
	);

	$addParams = array();

	if($bInternal && isset($arParams['INTERNAL_CONTEXT']) && is_array($arParams['INTERNAL_CONTEXT']))
	{
		$internalContext = $arParams['INTERNAL_CONTEXT'];
		if(isset($internalContext['CONTACT_ID']))
		{
			$addParams['contact_id'] = $internalContext['CONTACT_ID'];
		}
		if(isset($internalContext['COMPANY_ID']))
		{
			$addParams['company_id'] = $internalContext['COMPANY_ID'];
		}
	}

	if(!empty($addParams))
	{
		$arResult['PATH_TO_DEAL_ADD'] = CHTTP::urlAddParams(
			$arResult['PATH_TO_DEAL_ADD'],
			$addParams
		);
	}
}

if (isset($arResult['DEAL_ID']) && !empty($arResult['DEAL_ID']))
{
	// try to load product rows
	$arProductRows = CCrmDeal::LoadProductRows(array_keys($arResult['DEAL_ID']));
	foreach($arProductRows as $arProductRow)
	{
		$ownerID = $arProductRow['OWNER_ID'];
		if(!isset($arResult['DEAL'][$ownerID]))
		{
			continue;
		}

		$arEntity = &$arResult['DEAL'][$ownerID];
		if(!isset($arEntity['PRODUCT_ROWS']))
		{
			$arEntity['PRODUCT_ROWS'] = array();
		}
		$arEntity['PRODUCT_ROWS'][] = $arProductRow;
	}

	// checkig access for operation
	$arDealAttr = CCrmPerms::GetEntityAttr('DEAL', $arResult['DEAL_ID']);
	foreach ($arResult['DEAL_ID'] as $iDealId)
	{
		$arResult['DEAL'][$iDealId]['EDIT'] = $CCrmPerms->CheckEnityAccess('DEAL', 'WRITE', $arDealAttr[$iDealId]);
		$arResult['DEAL'][$iDealId]['DELETE'] = $CCrmPerms->CheckEnityAccess('DEAL', 'DELETE', $arDealAttr[$iDealId]);

		$arResult['DEAL'][$iDealId]['BIZPROC_LIST'] = array();
		foreach ($arBPData as $arBP)
		{
			if (!CBPDocument::CanUserOperateDocument(
				CBPCanUserOperateOperation::StartWorkflow,
				$userID,
				array('crm', 'CCrmDocumentDeal', 'DEAL_'.$arResult['DEAL'][$iDealId]['ID']),
				array(
					'UserGroups' => $CCrmBizProc->arCurrentUserGroups,
					'DocumentStates' => $arDocumentStates,
					'WorkflowTemplateId' => $arBP['ID'],
					'CreatedBy' => $arResult['DEAL'][$iDealId]['ASSIGNED_BY'],
					'UserIsAdmin' => $isAdmin,
					'CRMEntityAttr' =>  $arDealAttr[$iDealId]
				)
			))
			{
				continue;
			}

			$arBP['PATH_TO_BIZPROC_START'] = CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_SHOW'],
				array(
					'deal_id' => $arResult['DEAL'][$iDealId]['ID']
				)),
				array(
					'workflow_template_id' => $arBP['ID'], 'bizproc_start' => 1,  'sessid' => $arResult['SESSION_ID'],
					'CRM_DEAL_SHOW_V12_active_tab' => 'tab_bizproc', 'back_url' => $arParams['PATH_TO_DEAL_LIST'])
			);
			$arResult['DEAL'][$iDealId]['BIZPROC_LIST'][] = $arBP;
		}
	}
}

if (!$isInExportMode)
{
	$arResult['NEED_FOR_REBUILD_DEAL_ATTRS'] = $arResult['NEED_FOR_REBUILD_DEAL_STATISTICS'] = false;
	if(!$bInternal && CCrmPerms::IsAdmin())
	{
		if(COption::GetOptionString('crm', '~CRM_REBUILD_DEAL_ATTR', 'N') === 'Y')
		{
			$arResult['PATH_TO_PRM_LIST'] = CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_perm_list'));
			$arResult['NEED_FOR_REBUILD_DEAL_ATTRS'] = true;
		}
	}

	$this->IncludeComponentTemplate();
	include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.deal/include/nav.php');
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
		Header('Content-Disposition: attachment;filename=deals.csv');
	}
	elseif($sExportType === 'excel')
	{
		Header('Content-Type: application/vnd.ms-excel');
		Header('Content-Disposition: attachment;filename=deals.xls');
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
