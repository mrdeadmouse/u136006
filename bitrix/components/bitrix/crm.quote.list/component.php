<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

/*---bizproc---if (IsModuleInstalled('bizproc'))
{
	if (!CModule::IncludeModule('bizproc'))
	{
		ShowError(GetMessage('BIZPROC_MODULE_NOT_INSTALLED'));
		return;
	}
}*/

global $USER_FIELD_MANAGER, $USER, $APPLICATION, $DB;
$CCrmPerms = CCrmPerms::GetCurrentUserPermissions();
if ($CCrmPerms->HavePerm('QUOTE', BX_CRM_PERM_NONE, 'READ'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$CCrmQuote = new CCrmQuote(false);
/*---bizproc---$CCrmBizProc = new CCrmBizProc('QUOTE');*/

$userID = CCrmSecurityHelper::GetCurrentUserID();
$isAdmin = CCrmPerms::IsAdmin();

$arResult['CURRENT_USER_ID'] = CCrmSecurityHelper::GetCurrentUserID();
$arParams['PATH_TO_QUOTE_LIST'] = CrmCheckPath('PATH_TO_QUOTE_LIST', $arParams['PATH_TO_QUOTE_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_QUOTE_SHOW'] = CrmCheckPath('PATH_TO_QUOTE_SHOW', $arParams['PATH_TO_QUOTE_SHOW'], $APPLICATION->GetCurPage().'?quote_id=#quote_id#&show');
$arParams['PATH_TO_QUOTE_EDIT'] = CrmCheckPath('PATH_TO_QUOTE_EDIT', $arParams['PATH_TO_QUOTE_EDIT'], $APPLICATION->GetCurPage().'?quote_id=#quote_id#&edit');
$arParams['PATH_TO_INVOICE_EDIT'] = CrmCheckPath('PATH_TO_INVOICE_EDIT', $arParams['PATH_TO_INVOICE_EDIT'], $APPLICATION->GetCurPage().'?invoice_id=#invoice_id#&edit');
$arParams['PATH_TO_COMPANY_SHOW'] = CrmCheckPath('PATH_TO_COMPANY_SHOW', $arParams['PATH_TO_COMPANY_SHOW'], $APPLICATION->GetCurPage().'?company_id=#company_id#&show');
$arParams['PATH_TO_LEAD_SHOW'] = CrmCheckPath('PATH_TO_LEAD_SHOW', $arParams['PATH_TO_LEAD_SHOW'], $APPLICATION->GetCurPage().'?lead_id=#lead_id#&show');
$arParams['PATH_TO_DEAL_SHOW'] = CrmCheckPath('PATH_TO_DEAL_SHOW', $arParams['PATH_TO_DEAL_SHOW'], $APPLICATION->GetCurPage().'?deal_id=#deal_id#&show');
$arParams['PATH_TO_CONTACT_SHOW'] = CrmCheckPath('PATH_TO_CONTACT_SHOW', $arParams['PATH_TO_CONTACT_SHOW'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&show');
$arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath('PATH_TO_USER_PROFILE', $arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');
/*---bizproc---$arParams['PATH_TO_USER_BP'] = CrmCheckPath('PATH_TO_USER_BP', $arParams['PATH_TO_USER_BP'], '/company/personal/bizproc/');*/
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

$arResult['IS_AJAX_CALL'] = isset($_REQUEST['bxajaxid']) || isset($_REQUEST['AJAX_CALL']);

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
if (!empty($sExportType) && $CCrmPerms->HavePerm('QUOTE', BX_CRM_PERM_NONE, 'EXPORT'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$isInExportMode = $sExportType !== '';

$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmQuote::$sUFEntityID);

$arResult['GRID_ID'] = 'CRM_QUOTE_LIST_V12'.($bInternal && !empty($arParams['GRID_ID_SUFFIX']) ? '_'.$arParams['GRID_ID_SUFFIX'] : '');
/*$arResult['TYPE_LIST'] = CCrmStatus::GetStatusListEx('QUOTE_TYPE');*/
$arResult['STATUS_LIST'] = CCrmStatus::GetStatusListEx('QUOTE_STATUS');
/*$arResult['STATE_LIST'] = CCrmStatus::GetStatusListEx('QUOTE_STATE');*/
// Please, uncomment if required
//$arResult['CURRENCY_LIST'] = CCrmCurrencyHelper::PrepareListItems();
/*$arResult['EVENT_LIST'] = CCrmStatus::GetStatusListEx('EVENT_TYPE');*/
$arResult['CLOSED_LIST'] = array('Y' => GetMessage('MAIN_YES'), 'N' => GetMessage('MAIN_NO'));
$arResult['FILTER'] = array();
$arResult['FILTER2LOGIC'] = array();
$arResult['FILTER_PRESETS'] = array();
$arResult['PERMS']['ADD']    = !$CCrmPerms->HavePerm('QUOTE', BX_CRM_PERM_NONE, 'ADD');
$arResult['PERMS']['WRITE']  = !$CCrmPerms->HavePerm('QUOTE', BX_CRM_PERM_NONE, 'WRITE');
$arResult['PERMS']['DELETE'] = !$CCrmPerms->HavePerm('QUOTE', BX_CRM_PERM_NONE, 'DELETE');

$arResult['AJAX_MODE'] = isset($arParams['AJAX_MODE']) ? $arParams['AJAX_MODE'] : ($arResult['INTERNAL'] ? 'N' : 'Y');
$arResult['AJAX_ID'] = isset($arParams['AJAX_ID']) ? $arParams['AJAX_ID'] : '';
$arResult['AJAX_OPTION_JUMP'] = isset($arParams['AJAX_OPTION_JUMP']) ? $arParams['AJAX_OPTION_JUMP'] : 'N';
$arResult['AJAX_OPTION_HISTORY'] = isset($arParams['AJAX_OPTION_HISTORY']) ? $arParams['AJAX_OPTION_HISTORY'] : 'N';

$currentUserID = $arResult['CURRENT_USER_ID'];

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

	ob_start();
	$GLOBALS['APPLICATION']->IncludeComponent('bitrix:crm.entity.selector',
		'',
		array(
			'ENTITY_TYPE' => 'LEAD',
			'INPUT_NAME' => 'LEAD_ID',
			'INPUT_VALUE' => isset($_REQUEST['LEAD_ID']) ? intval($_REQUEST['LEAD_ID']) : '',
			'FORM_NAME' => $arResult['GRID_ID'],
			'MULTIPLE' => 'N',
			'FILTER' => true
		),
		false,
		array('HIDE_ICONS' => 'Y')
	);
	$sValLead = ob_get_contents();
	ob_end_clean();

	ob_start();
	$GLOBALS['APPLICATION']->IncludeComponent('bitrix:crm.entity.selector',
		'',
		array(
			'ENTITY_TYPE' => 'DEAL',
			'INPUT_NAME' => 'DEAL_ID',
			'INPUT_VALUE' => isset($_REQUEST['DEAL_ID']) ? intval($_REQUEST['DEAL_ID']) : '',
			'FORM_NAME' => $arResult['GRID_ID'],
			'MULTIPLE' => 'N',
			'FILTER' => true
		),
		false,
		array('HIDE_ICONS' => 'Y')
	);
	$sValDeal = ob_get_contents();
	ob_end_clean();

	ob_start();
	$GLOBALS['APPLICATION']->IncludeComponent('bitrix:crm.entity.selector',
		'',
		array(
			'ENTITY_TYPE' => Array('LEAD', 'DEAL', 'COMPANY', 'CONTACT'),
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

	/*$originatorID = isset($_REQUEST['ORIGINATOR_ID']) ? $_REQUEST['ORIGINATOR_ID'] : '';
	ob_start();*/
	if (false):	?>
	<!--<select name="ORIGINATOR_ID">
		<option value=""><?/*= GetMessage("CRM_COLUMN_ALL") */?></option>
		<option value="__INTERNAL" <?/*= $originatorID === '__INTERNAL' ? 'selected' : ''*/?>><?/*= GetMessage("CRM_INTERNAL") */?></option>
		<?/*
		foreach($arExternalSales as $k => $v)
		{
			$k = strval($k);
			*/?><option value="<?/*= htmlspecialcharsbx($k) */?>"<?/*= ($originatorID === $k) ? " selected" : "" */?>><?/*= htmlspecialcharsbx($v) */?></option><?/*
		}
		*/?>
	</select>-->
	<? endif;
	/*$sValOriginator = ob_get_contents();
	ob_end_clean();*/

	$arResult['FILTER'] = array(
		array('id' => 'ID', 'name' => GetMessage('CRM_COLUMN_ID')),
		array('id' => 'QUOTE_NUMBER', 'name' => GetMessage('CRM_COLUMN_QUOTE_NUMBER')),
		array('id' => 'TITLE', 'name' => GetMessage('CRM_COLUMN_TITLE'), 'default' => true),
		array('id' => 'ASSIGNED_BY_ID',  'name' => GetMessage('CRM_COLUMN_ASSIGNED_BY'), 'default' => true, 'enable_settings' => true, 'type' => 'user'),
		array('id' => 'OPPORTUNITY', 'name' => GetMessage('CRM_COLUMN_OPPORTUNITY'), 'default' => true, 'type' => 'number'),
		array('id' => 'CURRENCY_ID', 'name' => GetMessage('CRM_COLUMN_CURRENCY_ID'), 'default' => true, 'type' => 'list', 'items' => array('' => '') + CCrmCurrencyHelper::PrepareListItems()),
		array('id' => 'STATUS_ID', 'params' => array('multiple' => 'Y'), 'name' => GetMessage('CRM_COLUMN_STATUS_ID'), 'default' => true, 'type' => 'list', 'items' => CCrmStatus::GetStatusList('QUOTE_STATUS'), 'default' => true),
		/*array('id' => 'PROBABILITY', 'name' => GetMessage('CRM_COLUMN_PROBABILITY'), 'default' => true, 'type' => 'number'),*/
		array('id' => 'BEGINDATE', 'name' => GetMessage('CRM_COLUMN_BEGINDATE'), 'default' => true, 'type' => 'date'),
		array('id' => 'CLOSEDATE', 'name' => GetMessage('CRM_COLUMN_CLOSEDATE'), 'default' => true, 'type' => 'date'),
		array('id' => 'CONTACT_ID', 'name' => GetMessage('CRM_COLUMN_CONTACT_LIST'), 'type' => 'custom', 'value' => $sValContact),
		array('id' => 'CONTACT_FULL_NAME', 'name' => GetMessage('CRM_COLUMN_CONTACT_FULL_NAME')),
		array('id' => 'COMPANY_ID', 'name' => GetMessage('CRM_COLUMN_COMPANY_LIST'), 'type' => 'custom', 'value' => $sValCompany),
		array('id' => 'LEAD_ID', 'name' => GetMessage('CRM_COLUMN_LEAD_LIST'), 'type' => 'custom', 'value' => $sValLead),
		array('id' => 'DEAL_ID', 'name' => GetMessage('CRM_COLUMN_DEAL_LIST'), 'type' => 'custom', 'value' => $sValDeal),
		array('id' => 'COMPANY_TITLE', 'name' => GetMessage('CRM_COLUMN_COMPANY_TITLE')),
		array('id' => 'COMMENTS', 'name' => GetMessage('CRM_COLUMN_COMMENTS')),
		/*array('id' => 'TYPE_ID', 'params' => array('multiple' => 'Y'), 'name' => GetMessage('CRM_COLUMN_TYPE_ID'),  'type' => 'list', 'items' => CCrmStatus::GetStatusList('QUOTE_TYPE')),*/
		array('id' => 'CLOSED', 'name' => GetMessage('CRM_COLUMN_CLOSED'), 'type' => 'list', 'items' => array('' => '', 'Y' => GetMessage('MAIN_YES'), 'N' => GetMessage('MAIN_NO'))),
		/*array('id' => 'STATE_ID', 'name' => GetMessage('CRM_COLUMN_STATE_ID'), 'type' => 'list', 'items' => array('' => '') + CCrmStatus::GetStatusList('QUOTE_STATE')),        */
		array('id' => 'DATE_CREATE', 'name' => GetMessage('CRM_COLUMN_DATE_CREATE'), 'type' => 'date'),
		array('id' => 'CREATED_BY_ID',  'name' => GetMessage('CRM_COLUMN_CREATED_BY'), 'default' => false, 'enable_settings' => true, 'type' => 'user'),
		array('id' => 'DATE_MODIFY', 'name' => GetMessage('CRM_COLUMN_DATE_MODIFY'), 'type' => 'date'),
		array('id' => 'MODIFY_BY_ID',  'name' => GetMessage('CRM_COLUMN_MODIFY_BY'), 'default' => false, 'enable_settings' => true, 'type' => 'user'),
		/*array('id' => 'EVENT_DATE', 'name' => GetMessage('CRM_COLUMN_EVENT_DATE_FILTER'), 'type' => 'date'),*/
		/*array('id' => 'EVENT_ID', 'name' => GetMessage('CRM_COLUMN_EVENT_ID_FILTER'), 'type' => 'list', 'items' => array('' => '') + CCrmStatus::GetStatusList('EVENT_TYPE')),*/
		array('id' => 'PRODUCT_ROW_PRODUCT_ID', 'name' => GetMessage('CRM_COLUMN_PRODUCT'), 'enable_settings' => false),
		/*array('id' => 'ORIGINATOR_ID', 'name' => GetMessage('CRM_COLUMN_BINDING'), 'type' => 'custom', 'value' => $sValOriginator),*/
		array('id' => 'ENTITIES_LINKS', 'name' => GetMessage('CRM_COLUMN_ENTITIES_LINKS'), 'default' => true, 'type' => 'custom', 'value' => $entitiesLinksFilterHtml)
	);

	$CCrmUserType->ListAddFilterFields($arResult['FILTER'], $arResult['FILTER2LOGIC'], $arResult['GRID_ID']);

	$currentUserName = CCrmViewHelper::GetFormattedUserName($currentUserID, $arParams['NAME_TEMPLATE']);
	$arResult['FILTER_PRESETS'] = array(
		'filter_new' => array('name' => GetMessage('CRM_PRESET_NEW'), 'fields' => array('STATUS_ID' => array('selDRAFT' => 'DRAFT'))),
		'filter_my' => array('name' => GetMessage('CRM_PRESET_MY'), 'fields' => array( 'ASSIGNED_BY_ID_name' => $currentUserName, 'ASSIGNED_BY_ID' => $currentUserID))
		//'filter_change_today' => array('name' => GetMessage('CRM_PRESET_CHANGE_TODAY'), 'fields' => array('DATE_MODIFY_datesel' => 'today')),
		//'filter_change_yesterday' => array('name' => GetMessage('CRM_PRESET_CHANGE_YESTERDAY'), 'fields' => array('DATE_MODIFY_datesel' => 'yesterday')),
		//'filter_change_my' => array('name' => GetMessage('CRM_PRESET_CHANGE_MY'), 'fields' => array( 'MODIFY_BY_ID_name' => $currentUserName, 'MODIFY_BY_ID' => $currentUserID))
	);
}

$arResult['~STATUS_LIST_WRITE']= CCrmStatus::GetStatusList('QUOTE_STATUS');
$arResult['STATUS_LIST_WRITE'] = array();
foreach ($arResult['~STATUS_LIST_WRITE'] as $sStatusId => $sStatusTitle)
{
	if ($CCrmPerms->GetPermType('QUOTE', 'WRITE', array('STATUS_ID'.$sStatusId)) > BX_CRM_PERM_NONE)
		$arResult['STATUS_LIST_WRITE'][$sStatusId] = $sStatusTitle;
}

$arResult['HEADERS'] = array();

// Dont display activities in INTERNAL mode.
/*if(!$bInternal)
{
	$arResult['HEADERS'][] = array('id' => 'ACTIVITY_ID', 'name' => GetMessage('CRM_COLUMN_ACTIVITY'), 'sort' => 'nearest_activity', 'default' => true);
}*/

$arResult['HEADERS'] = array_merge(
	$arResult['HEADERS'],
	array(
		// default fields
		array('id' => 'QUOTE_SUMMARY', 'name' => GetMessage('CRM_COLUMN_QUOTE'), 'sort' => 'quote_summary', 'default' => true, 'editable' => false),
		array('id' => 'STATUS_ID', 'name' => GetMessage('CRM_COLUMN_STATUS_ID'), 'sort' => 'status_sort', 'default' => true, 'editable' => array('items' => $arResult['STATUS_LIST_WRITE']), 'type' => 'list'),
		array('id' => 'SUM', 'name' => GetMessage('CRM_COLUMN_SUM'), 'sort' => 'opportunity_account', 'default' => true, 'editable' => false, 'align' => 'right'),
		array('id' => 'ENTITIES_LINKS', 'name' => GetMessage('CRM_COLUMN_ENTITIES_LINKS'), 'default' => true, 'editable' => false),
		array('id' => 'CLOSEDATE', 'name' => GetMessage('CRM_COLUMN_CLOSEDATE'), 'sort' => 'closedate', 'default' => true, 'editable' => true, 'type' => 'date'),
		array('id' => 'BEGINDATE', 'name' => GetMessage('CRM_COLUMN_BEGINDATE'), 'sort' => 'begindate', 'default' => true, 'editable' => true, 'type' => 'date'),
		array('id' => 'ASSIGNED_BY', 'name' => GetMessage('CRM_COLUMN_ASSIGNED_BY'), 'sort' => 'assigned_by', 'default' => true, 'editable' => false),

		array('id' => 'ID', 'name' => GetMessage('CRM_COLUMN_ID'), 'sort' => 'id', 'default' => false, 'editable' => false, 'type' => 'int'),
		array('id' => 'QUOTE_NUMBER', 'name' => GetMessage('CRM_COLUMN_QUOTE_NUMBER'), 'sort' => 'quote_number', 'default' => false, 'editable' => false),
		array('id' => 'TITLE', 'name' => GetMessage('CRM_COLUMN_TITLE'), 'sort' => 'title', 'default' => false, 'editable' => true),
		/*array('id' => 'PROBABILITY', 'name' => GetMessage('CRM_COLUMN_PROBABILITY'), 'sort' => 'probability', 'default' => false, 'editable' => true, 'align' => 'right'),*/
		/*array('id' => 'ORIGINATOR_ID', 'name' => GetMessage('CRM_COLUMN_BINDING'), 'sort' => false, 'default' => false, 'editable' => array('items' => $arExternalSales), 'type' => 'list'),*/
		/*array('id' => 'TYPE_ID', 'name' => GetMessage('CRM_COLUMN_TYPE_ID'), 'sort' => 'type_id', 'default' => false, 'editable' => array('items' => CCrmStatus::GetStatusList('QUOTE_TYPE')), 'type' => 'list'),*/
		array('id' => 'QUOTE_CLIENT', 'name' => GetMessage('CRM_COLUMN_CLIENT'), 'sort' => 'quote_client', 'default' => false, 'editable' => false),
		array('id' => 'OPPORTUNITY', 'name' => GetMessage('CRM_COLUMN_OPPORTUNITY'), 'sort' => 'opportunity', 'default' => false, 'editable' => true, 'align' => 'right'),
		array('id' => 'CURRENCY_ID', 'name' => GetMessage('CRM_COLUMN_CURRENCY_ID'), 'sort' => 'currency_id', 'default' => false, 'editable' => array('items' => CCrmCurrencyHelper::PrepareListItems()), 'type' => 'list'),
		array('id' => 'CONTACT_ID', 'name' => GetMessage('CRM_COLUMN_CONTACT_ID'), 'sort' => 'contact_full_name', 'default' => false, 'editable' => false),
		array('id' => 'COMPANY_ID', 'name' => GetMessage('CRM_COLUMN_COMPANY_ID'), 'sort' => 'company_id', 'default' => false, 'editable' => false),
		array('id' => 'LEAD_ID', 'name' => GetMessage('CRM_COLUMN_LEAD_ID'), 'sort' => 'lead_id', 'default' => false, 'editable' => false),
		array('id' => 'DEAL_ID', 'name' => GetMessage('CRM_COLUMN_DEAL_ID'), 'sort' => 'deal_id', 'default' => false, 'editable' => false),
		array('id' => 'CLOSED', 'name' => GetMessage('CRM_COLUMN_CLOSED'), 'sort' => 'closed', 'align' => 'center', 'default' => false, 'editable' => array('items' => array('' => '', 'Y' => GetMessage('MAIN_YES'), 'N' => GetMessage('MAIN_NO'))), 'type' => 'list'),
		array('id' => 'DATE_CREATE', 'name' => GetMessage('CRM_COLUMN_DATE_CREATE'), 'sort' => 'date_create', 'default' => false),
		array('id' => 'CREATED_BY', 'name' => GetMessage('CRM_COLUMN_CREATED_BY'), 'sort' => 'created_by', 'default' => false, 'editable' => false),
		array('id' => 'DATE_MODIFY', 'name' => GetMessage('CRM_COLUMN_DATE_MODIFY'), 'sort' => 'date_modify', 'default' => false),
		array('id' => 'MODIFY_BY', 'name' => GetMessage('CRM_COLUMN_MODIFY_BY'), 'sort' => 'modify_by', 'default' => false, 'editable' => false),
		array('id' => 'PRODUCT_ID', 'name' => GetMessage('CRM_COLUMN_PRODUCT_ID'), 'sort' => false, 'default' => $isInExportMode, 'editable' => false, 'type' => 'list'),
		array('id' => 'COMMENTS', 'name' => GetMessage('CRM_COLUMN_COMMENTS'), 'sort' => false /*because of MSSQL*/, 'default' => false, 'editable' => false)
		/*array('id' => 'EVENT_DATE', 'name' => GetMessage('CRM_COLUMN_EVENT_DATE'), 'sort' => 'event_date', 'default' => false),
		array('id' => 'EVENT_ID', 'name' => GetMessage('CRM_COLUMN_EVENT_ID'), 'sort' => 'event_id', 'default' => false, 'editable' => array('items' => CCrmStatus::GetStatusList('EVENT_TYPE')), 'type' => 'list'),
		array('id' => 'EVENT_DESCRIPTION', 'name' => GetMessage('CRM_COLUMN_EVENT_DESCRIPTION'), 'sort' => false, 'default' => false, 'editable' => false),*/
		//	array('id' => 'STATE_ID', 'name' => GetMessage('CRM_COLUMN_STATE_ID'), 'sort' => 'state_id', 'default' => false, 'editable' => array('items' => CCrmStatus::GetStatusList('QUOTE_STATE')), 'type' => 'list'),
	)
);

$CCrmUserType->ListAddHeaders($arResult['HEADERS']);
/*---bizproc---if (IsModuleInstalled('bizproc'))
{
	$arBPData = CBPDocument::GetWorkflowTemplatesForDocumentType(array('crm', 'CCrmDocumentQuote', 'QUOTE'));
	$arDocumentStates = CBPDocument::GetDocumentStates(
		array('crm', 'CCrmDocumentQuote', 'QUOTE'),
		null
	);
	foreach ($arBPData as $arBP)
	{
		if (!CBPDocument::CanUserOperateDocumentType(
			CBPCanUserOperateOperation::StartWorkflow,
			$userID,
			array('crm', 'CCrmDocumentQuote', 'QUOTE'),
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
}*/

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

if (intval($arParams['QUOTE_COUNT']) <= 0)
	$arParams['QUOTE_COUNT'] = 20;

$arNavParams = array(
	'nPageSize' => $arParams['QUOTE_COUNT']
);

$arNavigation = CDBResult::GetNavParams($arNavParams);
$CGridOptions = new CCrmGridOptions($arResult['GRID_ID'], $arResult['FILTER_PRESETS']);
$arNavParams = $CGridOptions->GetNavParams($arNavParams);
$arNavParams['bShowAll'] = false;

$arFilter += $CGridOptions->GetFilter($arResult['FILTER']);
$USER_FIELD_MANAGER->AdminListAddFilter(CCrmQuote::$sUFEntityID, $arFilter);

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
$arImmutableFilters = array('FM', 'ID', 'ASSIGNED_BY_ID', 'CURRENCY_ID', 'CONTACT_ID', 'COMPANY_ID', 'LEAD_ID', 'DEAL_ID', 'CREATED_BY_ID', 'MODIFY_BY_ID', 'PRODUCT_ROW_PRODUCT_ID');
foreach ($arFilter as $k => $v)
{
	if(in_array($k, $arImmutableFilters, true))
	{
		continue;
	}

	$arMatch = array();

	if(in_array($k, array('PRODUCT_ID', /*'TYPE_ID', */'STATUS_ID', 'COMPANY_ID', 'LEAD_ID', 'DEAL_ID', 'CONTACT_ID')))
	{
		// Bugfix #23121 - to suppress comparison by LIKE
		$arFilter['='.$k] = $v;
		unset($arFilter[$k]);
	}
	/*elseif($k === 'ORIGINATOR_ID')
	{
		// HACK: build filter by internal entities
		$arFilter['=ORIGINATOR_ID'] = $v !== '__INTERNAL' ? $v : null;
		unset($arFilter[$k]);
	}*/
	elseif ($k === 'ENTITIES_LINKS')
	{
		$ownerData =explode('_', $v);
		if(count($ownerData) > 1)
		{
			$ownerTypeName = CCrmOwnerType::ResolveName(CCrmOwnerType::ResolveID($ownerData[0]));
			$ownerID = intval($ownerData[1]);
			if(!empty($ownerTypeName) && $ownerID > 0)
				$arFilter[$ownerTypeName.'_ID'] = $ownerID;
		}
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
	elseif (strpos($k, 'UF_') !== 0 && $k != 'LOGIC')
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

				$obRes = CCrmQuote::GetList(array(), $arFilterDel, false, false, array('ID'));
				while($arQuote = $obRes->Fetch())
				{
					$ID = $arQuote['ID'];
					$arEntityAttr = $CCrmPerms->GetEntityAttr('QUOTE', array($ID));
					if (!$CCrmPerms->CheckEnityAccess('QUOTE', 'DELETE', $arEntityAttr[$ID]))
					{
						continue ;
					}

					$DB->StartTransaction();

					if (/*---bizproc---$CCrmBizProc->Delete($ID, $arEntityAttr[$ID])
						&& */$CCrmQuote->Delete($ID))
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
					$arEntityAttr = $CCrmPerms->GetEntityAttr('QUOTE', array($ID));
					if (!$CCrmPerms->CheckEnityAccess('QUOTE', 'WRITE', $arEntityAttr[$ID]))
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

						if($CCrmQuote->Update($ID, $arUpdateData, true, true, array('DISABLE_USER_FIELD_CHECK' => true)))
						{
							$DB->Commit();

							/*---bizproc---$arErrors = array();
							CCrmBizProcHelper::AutoStartWorkflows(
								CCrmOwnerType::Quote,
								$ID,
								CCrmBizProcEventType::Edit,
								$arErrors
							);*/
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
						'back_url' => urlencode($arParams['PATH_TO_QUOTE_LIST'])
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

					$dbRes = CCrmQuote::GetList(array(), $arActionFilter, false, false, array('ID'));
					while($arQuote = $dbRes->Fetch())
					{
						$arIDs[] = $arQuote['ID'];
					}
				}
				elseif (isset($actionData['ID']) && is_array($actionData['ID']))
				{
					$arIDs = $actionData['ID'];
				}

				$arEntityAttr = $CCrmPerms->GetEntityAttr('QUOTE', $arIDs);
				foreach($arIDs as $ID)
				{
					if (!$CCrmPerms->CheckEnityAccess('QUOTE', 'WRITE', $arEntityAttr[$ID]))
					{
						continue;
					}

					$DB->StartTransaction();

					$arUpdateData = array(
						'STATUS_ID' => $actionData['STATUS_ID']
					);

					if($CCrmQuote->Update($ID, $arUpdateData, true, true, array('DISABLE_USER_FIELD_CHECK' => true)))
					{
						$DB->Commit();

						/*---bizproc---$arErrors = array();
						CCrmBizProcHelper::AutoStartWorkflows(
							CCrmOwnerType::Quote,
							$ID,
							CCrmBizProcEventType::Edit,
							$arErrors
						);*/
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
					$dbRes = CCrmQuote::GetList(array(), $arActionFilter, false, false, array('ID'));
					while($arQuote = $dbRes->Fetch())
					{
						$arIDs[] = $arQuote['ID'];
					}
				}
				elseif (isset($actionData['ID']) && is_array($actionData['ID']))
				{
					$arIDs = $actionData['ID'];
				}

				$arEntityAttr = $CCrmPerms->GetEntityAttr('QUOTE', $arIDs);

				foreach($arIDs as $ID)
				{
					if (!$CCrmPerms->CheckEnityAccess('QUOTE', 'WRITE', $arEntityAttr[$ID]))
					{
						continue;
					}

					$DB->StartTransaction();

					$arUpdateData = array(
						'ASSIGNED_BY_ID' => $actionData['ASSIGNED_BY_ID']
					);

					if($CCrmQuote->Update($ID, $arUpdateData, true, true, array('DISABLE_USER_FIELD_CHECK' => true)))
					{
						$DB->Commit();

						/*---bizproc---$arErrors = array();
						CCrmBizProcHelper::AutoStartWorkflows(
							CCrmOwnerType::Quote,
							$ID,
							CCrmBizProcEventType::Edit,
							$arErrors
						);*/
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

							$dbRes = CCrmQuote::GetList(
								array(),
								$arActionFilter,
								false,
								false,
								array('ID', 'OPENED')
							);

							while($arQuote = $dbRes->Fetch())
							{
								if(isset($arQuote['OPENED']) && $arQuote['OPENED'] === $isOpened)
								{
									continue;
								}

								$arIDs[] = $arQuote['ID'];
							}
						}
						elseif (isset($actionData['ID']) && is_array($actionData['ID']))
						{
							$dbRes = CCrmQuote::GetList(
								array(),
								array(
									'@ID'=> $actionData['ID'],
									'CHECK_PERMISSIONS' => 'N'
								),
								false,
								false,
								array('ID', 'OPENED')
							);

							while($arQuote = $dbRes->Fetch())
							{
								if(isset($arQuote['OPENED']) && $arQuote['OPENED'] === $isOpened)
								{
									continue;
								}

								$arIDs[] = $arQuote['ID'];
							}
						}

						$arEntityAttr = $CCrmPerms->GetEntityAttr('QUOTE', $arIDs);
						foreach($arIDs as $ID)
						{
							if (!$CCrmPerms->CheckEnityAccess('QUOTE', 'WRITE', $arEntityAttr[$ID]))
							{
								continue;
							}

							$DB->StartTransaction();
							$arUpdateData = array('OPENED' => $isOpened);
							if($CCrmQuote->Update($ID, $arUpdateData, true, true, array('DISABLE_USER_FIELD_CHECK' => true)))
							{
								$DB->Commit();

								/*---bizproc---CCrmBizProcHelper::AutoStartWorkflows(
									CCrmOwnerType::Quote,
									$ID,
									CCrmBizProcEventType::Edit,
									$arErrors
								);*/
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
			LocalRedirect($arParams['PATH_TO_QUOTE_LIST']);
		}
	}
	else//if ($actionData['METHOD'] == 'GET')
	{
		if ($actionData['NAME'] == 'delete' && isset($actionData['ID']))
		{
			$ID = intval($actionData['ID']);

			$arEntityAttr = $CCrmPerms->GetEntityAttr('QUOTE', array($ID));
			$attr = $arEntityAttr[$ID];

			if($CCrmPerms->CheckEnityAccess('QUOTE', 'DELETE', $attr))
			{
				$DB->StartTransaction();

				if(/*---bizproc---$CCrmBizProc->Delete($ID, $attr)
					&& */$CCrmQuote->Delete($ID))
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
			LocalRedirect($bInternal ? '?'.$arParams['FORM_ID'].'_active_tab=tab_quote' : $arParams['PATH_TO_QUOTE_LIST']);
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
	'sort' => array('closedate' => 'asc'),
	'vars' => array('by' => 'by', 'order' => 'order')
));
$arResult['SORT'] = !empty($arSort) ? $arSort : $_arSort['sort'];
$arResult['SORT_VARS'] = $_arSort['vars'];

// Remove column for deleted UF
$arSelect = $CGridOptions->GetVisibleColumns();

if ($CCrmUserType->NormalizeFields($arSelect))
	$CGridOptions->SetVisibleColumns($arSelect);

/*---bizproc---$arResult['ENABLE_BIZPROC'] = IsModuleInstalled('bizproc');*/
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
	/*---bizproc---$arResult['ENABLE_BIZPROC'] = false;*/
}
/*---bizproc---else
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
}*/

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

if(in_array('QUOTE_SUMMARY', $arSelect, true))
{
	$arSelect[] = 'QUOTE_NUMBER';
	$arSelect[] = 'TITLE';
}

/*if(in_array('ACTIVITY_ID', $arSelect, true))
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
}*/

if(in_array('SUM', $arSelect, true))
{
	$arSelect[] = 'OPPORTUNITY';
	$arSelect[] = 'CURRENCY_ID';
}

if (in_array('ENTITIES_LINKS', $arSelect, true))
{
	$arSelect[] = 'CONTACT_ID';
	$arSelect[] = 'COMPANY_TITLE';
	$arSelect[] = 'COMPANY_ID';
	$arSelect[] = 'CONTACT_NAME';
	$arSelect[] = 'CONTACT_SECOND_NAME';
	$arSelect[] = 'CONTACT_LAST_NAME';
	$arSelect[] = 'LEAD_ID';
	$arSelect[] = 'LEAD_TITLE';
	$arSelect[] = 'DEAL_ID';
	$arSelect[] = 'DEAL_TITLE';
}
else if(in_array('QUOTE_CLIENT', $arSelect, true))
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
	if(in_array('LEAD_ID', $arSelect, true))
	{
		$arSelect[] = 'LEAD_TITLE';
	}
	if(in_array('DEAL_ID', $arSelect, true))
	{
		$arSelect[] = 'DEAL_TITLE';
	}
}

// Always need to remove the menu items
if (!in_array('STATUS_ID', $arSelect))
	$arSelect[] = 'STATUS_ID';

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
			'QUOTE_SUMMARY' => array(
				'QUOTE_NUMBER',
				'TITLE'
			),
			'QUOTE_CLIENT' => array(
				'CONTACT_ID',
				'COMPANY_ID'
			),
			'SUM' => array(
				'OPPORTUNITY',
				'CURRENCY_ID'
			)/*,
			'ACTIVITY_ID' => array()*/
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
		'DATE_CREATE', 'TITLE', 'STATUS_ID',/* 'TYPE_ID',*/
		'OPPORTUNITY', 'CURRENCY_ID', 'COMMENTS',
		'CONTACT_ID', 'CONTACT_NAME', 'CONTACT_SECOND_NAME',
		'CONTACT_LAST_NAME', 'COMPANY_ID', 'COMPANY_TITLE',
		'LEAD_ID', 'LEAD_TITLE', 'DEAL_ID', 'DEAL_TITLE'
	);
	$nTopCount = $arParams['QUOTE_COUNT'];
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
/*if(isset($arSort['nearest_activity']))
{
	$activitySort = $arSort['nearest_activity'];
	$arSort['activity_time'] = $activitySort;
	$arSort['c_activity_time'] = $activitySort;
	unset($arSort['nearest_activity']);
}*/
$arOptions = array('FIELD_OPTIONS' => array('ADDITIONAL_FIELDS' => array()));
/*if(in_array('ACTIVITY_ID', $arSelect, true))
{
	$arOptions['FIELD_OPTIONS']['ADDITIONAL_FIELDS'][] = 'ACTIVITY';
}*/
if(isset($arSort['status_sort']))
{
	$arOptions['FIELD_OPTIONS']['ADDITIONAL_FIELDS'][] = 'STATUS_SORT';
}
if(isset($arSort['closedate']))
{
	$arOptions['NULLS_LAST'] = true;
}
if(isset($arSort['contact_full_name']))
{
	$arSort['contact_last_name'] = $arSort['contact_full_name'];
	$arSort['contact_name'] = $arSort['contact_full_name'];
	unset($arSort['contact_full_name']);
}
if(isset($arSort['quote_client']))
{
	$arSort['contact_last_name'] = $arSort['quote_client'];
	$arSort['contact_name'] = $arSort['quote_client'];
	$arSort['company_title'] = $arSort['quote_client'];
	unset($arSort['quote_client']);
}
if(isset($arSort['quote_summary']))
{
	$arSort['quote_number'] = $arSort['quote_summary'];
	$arSort['title'] = $arSort['quote_summary'];
	unset($arSort['quote_summary']);
}
if(isset($arParams['IS_EXTERNAL_CONTEXT']))
{
	$arOptions['IS_EXTERNAL_CONTEXT'] = $arParams['IS_EXTERNAL_CONTEXT'];
}

//FIELD_OPTIONS
$arSelect = array_unique($arSelect, SORT_STRING);
$obRes = CCrmQuote::GetList($arSort, $arFilter, false, (!$isInExportMode ? $arNavParams : false), $arSelect, $arOptions);

$arResult['QUOTE'] = array();
$arResult['QUOTE_ID'] = array();
$arResult['QUOTE_UF'] = array();
$now = time() + CTimeZone::GetOffset();

while($arQuote = $obRes->GetNext())
{
	$arQuote['CLOSEDATE'] = !empty($arQuote['CLOSEDATE']) ? CCrmComponentHelper::TrimDateTimeString(ConvertTimeStamp(MakeTimeStamp($arQuote['CLOSEDATE']), 'SHORT', SITE_ID)) : '';
	$arQuote['BEGINDATE'] = !empty($arQuote['BEGINDATE']) ? CCrmComponentHelper::TrimDateTimeString(ConvertTimeStamp(MakeTimeStamp($arQuote['BEGINDATE']), 'SHORT', SITE_ID)) : '';
	/*$arQuote['EVENT_DATE'] = !empty($arQuote['EVENT_DATE']) ? CCrmComponentHelper::TrimDateTimeString(ConvertTimeStamp(MakeTimeStamp($arQuote['EVENT_DATE']), 'SHORT', SITE_ID)) : '';*/
	$arQuote['~CLOSEDATE'] = $arQuote['CLOSEDATE'];
	$arQuote['~BEGINDATE'] = $arQuote['BEGINDATE'];
	/*$arQuote['~EVENT_DATE'] = $arQuote['EVENT_DATE'];*/

	$currencyID =  isset($arQuote['~CURRENCY_ID']) ? $arQuote['~CURRENCY_ID'] : CCrmCurrency::GetBaseCurrencyID();
	$arQuote['~CURRENCY_ID'] = $currencyID;
	$arQuote['CURRENCY_ID'] = htmlspecialcharsbx($currencyID);

	$arQuote['FORMATTED_OPPORTUNITY'] = CCrmCurrency::MoneyToString($arQuote['~OPPORTUNITY'], $arQuote['~CURRENCY_ID']);

	$entityID = $arQuote['ID'];

	$arQuote['PATH_TO_QUOTE_SHOW'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_QUOTE_SHOW'],
		array(
			'quote_id' => $entityID
		)
	);
	$arQuote['PATH_TO_QUOTE_EDIT'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_QUOTE_EDIT'],
		array(
			'quote_id' => $entityID
		)
	);
	$arQuote['PATH_TO_QUOTE_COPY'] =  CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_QUOTE_EDIT'],
		array(
			'quote_id' => $entityID
		)),
		array('copy' => 1)
	);
	$arQuote['PATH_TO_QUOTE_DELETE'] =  CHTTP::urlAddParams(
		$bInternal ? $APPLICATION->GetCurPage() : $arParams['PATH_TO_QUOTE_LIST'],
		array('action_'.$arResult['GRID_ID'] => 'delete', 'ID' => $entityID, 'sessid' => bitrix_sessid())
	);

	$contactID = isset($arQuote['~CONTACT_ID']) ? intval($arQuote['~CONTACT_ID']) : 0;
	$arQuote['PATH_TO_CONTACT_SHOW'] = $contactID <= 0 ? ''
		: CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_SHOW'], array('contact_id' => $contactID));

	$arQuote['~CONTACT_FORMATTED_NAME'] = $contactID <= 0 ? ''
		: CUser::FormatName(
			\Bitrix\Crm\Format\PersonNameFormatter::getFormat(),
			array(
				'LOGIN' => '',
				'NAME' => isset($arQuote['~CONTACT_NAME']) ? $arQuote['~CONTACT_NAME'] : '',
				'LAST_NAME' => isset($arQuote['~CONTACT_LAST_NAME']) ? $arQuote['~CONTACT_LAST_NAME'] : '',
				'SECOND_NAME' => isset($arQuote['~CONTACT_SECOND_NAME']) ? $arQuote['~CONTACT_SECOND_NAME'] : ''
			),
			false, false
		);
	$arQuote['CONTACT_FORMATTED_NAME'] = htmlspecialcharsbx($arQuote['~CONTACT_FORMATTED_NAME']);

	$arQuote['~CONTACT_FULL_NAME'] = CCrmContact::GetFullName(
		array(
			'NAME' => isset($arQuote['CONTACT_NAME']) ? $arQuote['CONTACT_NAME'] : '',
			'LAST_NAME' => isset($arQuote['CONTACT_LAST_NAME']) ? $arQuote['CONTACT_LAST_NAME'] : '',
			'SECOND_NAME' => isset($arQuote['CONTACT_SECOND_NAME']) ? $arQuote['CONTACT_SECOND_NAME'] : ''
		),
		false
	);
	$arQuote['CONTACT_FULL_NAME'] = htmlspecialcharsbx($arQuote['~CONTACT_FULL_NAME']);

	$companyID = isset($arQuote['~COMPANY_ID']) ? intval($arQuote['~COMPANY_ID']) : 0;
	$arQuote['PATH_TO_COMPANY_SHOW'] = $companyID <= 0 ? ''
		: CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_SHOW'], array('company_id' => $companyID));

	$leadID = isset($arQuote['~LEAD_ID']) ? intval($arQuote['~LEAD_ID']) : 0;
	$arQuote['PATH_TO_LEAD_SHOW'] = $leadID <= 0 ? ''
		: CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_SHOW'], array('lead_id' => $leadID));

	$dealID = isset($arQuote['~DEAL_ID']) ? intval($arQuote['~DEAL_ID']) : 0;
	$arQuote['PATH_TO_DEAL_SHOW'] = $dealID <= 0 ? ''
		: CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_SHOW'], array('deal_id' => $dealID));

	$arQuote['PATH_TO_USER_PROFILE'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_PROFILE'],
		array(
			'user_id' => $arQuote['ASSIGNED_BY']
		)
	);
	$arQuote['PATH_TO_USER_BP'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_BP'],
		array(
			'user_id' => $userID
		)
	);

	$arQuote['PATH_TO_USER_CREATOR'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_PROFILE'],
		array(
			'user_id' => $arQuote['CREATED_BY']
		)
	);

	$arQuote['PATH_TO_USER_MODIFIER'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_PROFILE'],
		array(
			'user_id' => $arQuote['MODIFY_BY']
		)
	);

	$arQuote['CREATED_BY_FORMATTED_NAME'] = CUser::FormatName(
		$arParams['NAME_TEMPLATE'],
		array(
			'LOGIN' => $arQuote['CREATED_BY_LOGIN'],
			'NAME' => $arQuote['CREATED_BY_NAME'],
			'LAST_NAME' => $arQuote['CREATED_BY_LAST_NAME'],
			'SECOND_NAME' => $arQuote['CREATED_BY_SECOND_NAME']
		),
		true, false
	);

	$arQuote['MODIFY_BY_FORMATTED_NAME'] = CUser::FormatName(
		$arParams['NAME_TEMPLATE'],
		array(
			'LOGIN' => $arQuote['MODIFY_BY_LOGIN'],
			'NAME' => $arQuote['MODIFY_BY_NAME'],
			'LAST_NAME' => $arQuote['MODIFY_BY_LAST_NAME'],
			'SECOND_NAME' => $arQuote['MODIFY_BY_SECOND_NAME']
		),
		true, false
	);

	/*$typeID = isset($arQuote['TYPE_ID']) ? $arQuote['TYPE_ID'] : '';
	$arQuote['QUOTE_TYPE_NAME'] = isset($arResult['TYPE_LIST'][$typeID]) ? $arResult['TYPE_LIST'][$typeID] : $typeID;*/

	$statusID = isset($arQuote['STATUS_ID']) ? $arQuote['STATUS_ID'] : '';
	$arQuote['QUOTE_STATUS_NAME'] = isset($arResult['STATUS_LIST'][$statusID]) ? $arResult['STATUS_LIST'][$statusID] : $statusID;

	/*if(isset($arQuote['~ACTIVITY_TIME']))
	{
		$time = MakeTimeStamp($arQuote['~ACTIVITY_TIME']);
		$arQuote['~ACTIVITY_EXPIRED'] = $time <= $now;
		$arQuote['~ACTIVITY_IS_CURRENT_DAY'] = $arQuote['~ACTIVITY_EXPIRED'] || CCrmActivity::IsCurrentDay($time);
	}*/

	/*$originatorID = isset($arQuote['~ORIGINATOR_ID']) ? $arQuote['~ORIGINATOR_ID'] : '';
	if($originatorID !== '')
	{
		$arQuote['~ORIGINATOR_NAME'] = isset($arResult['EXTERNAL_SALES'][$originatorID])
			? $arResult['EXTERNAL_SALES'][$originatorID] : '';

		$arQuote['ORIGINATOR_NAME'] = htmlspecialcharsbx($arQuote['~ORIGINATOR_NAME']);
	}*/

	if ($arResult['ENABLE_TASK'])
	{
		$arQuote['PATH_TO_TASK_EDIT'] = CHTTP::urlAddParams(
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
				'back_url' => urlencode($arParams['PATH_TO_QUOTE_LIST'])
			)
		);
	}

	if (IsModuleInstalled('sale'))
	{
		$arQuote['PATH_TO_INVOICE_ADD'] =
			CHTTP::urlAddParams(CComponentEngine::makePathFromTemplate(
				$arParams['PATH_TO_INVOICE_EDIT'], array('invoice_id' => 0)),
				array('quote' => $entityID)
			);
	}

	/*---bizproc---if ($arResult['ENABLE_BIZPROC'])
	{
		$arQuote['BIZPROC_STATUS'] = '';
		$arQuote['BIZPROC_STATUS_HINT'] = '';

		$arDocumentStates = CBPDocument::GetDocumentStates(
			array('crm', 'CCrmDocumentQuote', 'QUOTE'),
			array('crm', 'CCrmDocumentQuote', 'QUOTE_'.$entityID)
		);

		$arQuote['PATH_TO_BIZPROC_LIST'] =  CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_QUOTE_SHOW'],
			array(
				'quote_id' => $entityID
			)),
			array('CRM_QUOTE_SHOW_V12_active_tab' => 'tab_bizproc')
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
				$arQuote[$paramName] = $docTtl;
			}
			else
			{
				$arQuote[$paramName] = '<a href="'.htmlspecialcharsbx($arQuote['PATH_TO_BIZPROC_LIST']).'">'.htmlspecialcharsbx($docTtl).'</a>';

				$docID = $arDocState['ID'];
				$taskQty = CCrmBizProcHelper::GetUserWorkflowTaskCount(array($docID), $userID);
				if($taskQty > 0)
				{
					$totalTaskQty += $taskQty;
				}

				$arQuote['BIZPROC_STATUS'] = $taskQty > 0 ? 'attention' : 'inprogress';
				$arQuote['BIZPROC_STATUS_HINT'] =
					'<div class=\'bizproc-item-title\'>'.
						htmlspecialcharsbx($docTemplateName !== '' ? $docTemplateName : GetMessage('CRM_BPLIST')).
						': <span class=\'bizproc-item-title bizproc-state-title\'><a href=\''.$arQuote['PATH_TO_BIZPROC_LIST'].'\'>'.
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
					$arQuote[$paramName] = $docTtl;
				}
				else
				{
					$arQuote[$paramName] = '<a href="'.htmlspecialcharsbx($arQuote['PATH_TO_BIZPROC_LIST']).'">'.htmlspecialcharsbx($docTtl).'</a>';

					$docID = $arDocState['ID'];
					//TODO: wait for bizproc bugs will be fixed and replace serial call of CCrmBizProcHelper::GetUserWorkflowTaskCount on single call
					$taskQty = CCrmBizProcHelper::GetUserWorkflowTaskCount(array($docID), $userID);
					if($taskQty === 0)
					{
						continue;
					}

					if ($arQuote['BIZPROC_STATUS'] !== 'attention')
					{
						$arQuote['BIZPROC_STATUS'] = 'attention';
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
				$arQuote['BIZPROC_STATUS_HINT'] =
					'<span class=\'bizproc-item-title\'>'.GetMessage('CRM_BP_R_P').': <a href=\''.$arQuote['PATH_TO_BIZPROC_LIST'].'\' title=\''.GetMessage('CRM_BP_R_P_TITLE').'\'>'.$docStatesQty.'</a></span>'.
					($totalTaskQty === 0
						? ''
						: '<br /><span class=\'bizproc-item-title\'>'.GetMessage('CRM_TASKS').': <a href=\''.$arQuote['PATH_TO_USER_BP'].'\' title=\''.GetMessage('CRM_TASKS_TITLE').'\'>'.$totalTaskQty.($totalTaskQty > 5 ? '+' : '').'</a></span>');
			}
		}
	}*/

	$arQuote['ASSIGNED_BY_ID'] = $arQuote['~ASSIGNED_BY_ID'] = intval($arQuote['ASSIGNED_BY']);
	$arQuote['ASSIGNED_BY'] = CUser::FormatName(
		$arParams['NAME_TEMPLATE'],
		array(
			'LOGIN' => $arQuote['ASSIGNED_BY_LOGIN'],
			'NAME' => $arQuote['ASSIGNED_BY_NAME'],
			'LAST_NAME' => $arQuote['ASSIGNED_BY_LAST_NAME'],
			'SECOND_NAME' => $arQuote['ASSIGNED_BY_SECOND_NAME']
		),
		true, false
	);

	if (isset($arQuote['CONTACT_ID']) && intval($arQuote['CONTACT_ID']) > 0)
	{
		$arQuote['CONTACT_FORMATTED_NAME'] = CUser::FormatName(
			\Bitrix\Crm\Format\PersonNameFormatter::getFormat(),
			array(
				'LOGIN' => '['.$arQuote['CONTACT_ID'].']',
				'NAME' => $arQuote['~CONTACT_NAME'],
				'LAST_NAME' => $arQuote['~CONTACT_LAST_NAME'],
				'SECOND_NAME' => $arQuote['~CONTACT_SECOND_NAME']
			)
		);
		$arQuote['CONTACT_LINK_HTML'] = CCrmViewHelper::PrepareEntityBaloonHtml(
			array(
				'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
				'ENTITY_ID' => $arQuote['CONTACT_ID'],
				'PREFIX' => uniqid("crm_contact_link_"),
				'TITLE' => $arQuote['CONTACT_FORMATTED_NAME'],
				'CLASS_NAME' => ''
			)
		);
	}
	if (isset($arQuote['COMPANY_ID']) && intval($arQuote['COMPANY_ID']) > 0)
	{
		$arQuote['COMPANY_LINK_HTML'] = CCrmViewHelper::PrepareEntityBaloonHtml(
			array(
				'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
				'ENTITY_ID' => $arQuote['COMPANY_ID'],
				'PREFIX' => uniqid("crm_company_link_"),
				'TITLE' => $arQuote['~COMPANY_TITLE'],
				'CLASS_NAME' => ''
			)
		);
	}
	if (isset($arQuote['LEAD_ID']) && intval($arQuote['LEAD_ID']) > 0)
	{
		$arQuote['LEAD_LINK_HTML'] = CCrmViewHelper::PrepareEntityBaloonHtml(
			array(
				'ENTITY_TYPE_ID' => CCrmOwnerType::Lead,
				'ENTITY_ID' => $arQuote['LEAD_ID'],
				'PREFIX' => uniqid("crm_lead_link_"),
				'TITLE' => $arQuote['~LEAD_TITLE'],
				'CLASS_NAME' => ''
			)
		);
	}
	if (isset($arQuote['DEAL_ID']) && intval($arQuote['DEAL_ID']) > 0)
	{
		$arQuote['DEAL_LINK_HTML'] = CCrmViewHelper::PrepareEntityBaloonHtml(
			array(
				'ENTITY_TYPE_ID' => CCrmOwnerType::Deal,
				'ENTITY_ID' => $arQuote['DEAL_ID'],
				'PREFIX' => uniqid("crm_deal_link_"),
				'TITLE' => $arQuote['~DEAL_TITLE'],
				'CLASS_NAME' => ''
			)
		);
	}
	$arQuote['FORMATTED_ENTITIES_LINKS'] =
		'<div class="crm-info-links-wrapper">'.PHP_EOL.
		"\t".'<div class="crm-info-contact-wrapper">'.
			(isset($arQuote['CONTACT_LINK_HTML']) ?
				htmlspecialchars_decode($arQuote['CONTACT_LINK_HTML']) : '').'</div>'.PHP_EOL.
		"\t".'<div class="crm-info-company-wrapper">'.
			(isset($arQuote['COMPANY_LINK_HTML']) ? $arQuote['COMPANY_LINK_HTML'] : '').'</div>'.PHP_EOL.
		"\t".'<div class="crm-info-lead-wrapper">'.
			(isset($arQuote['LEAD_LINK_HTML']) ? $arQuote['LEAD_LINK_HTML'] : '').'</div>'.PHP_EOL.
		"\t".'<div class="crm-info-deal-wrapper">'.
			(isset($arQuote['DEAL_LINK_HTML']) ? $arQuote['DEAL_LINK_HTML'] : '').'</div>'.PHP_EOL.
		'</div>'.PHP_EOL;

	// color coding
	$arQuote['EXPIRED_FLAG'] = false;
	$arQuote['IN_COUNTER_FLAG'] = false;
	if (!empty($arQuote['CLOSEDATE']))
	{
		$tsCloseDate = MakeTimeStamp($arQuote['CLOSEDATE']);
		$tsNow = time() + CTimeZone::GetOffset();
		$tsMax = mktime(0, 0, 0, date('m',$tsNow), date('d',$tsNow), date('Y',$tsNow));

		$counterData = array(
			'CURRENT_USER_ID' => $currentUserID,
			'ENTITY' => $arQuote
		);
		$bReckoned = CCrmUserCounter::IsReckoned(CCrmUserCounter::CurrentQuoteActivies, $counterData);
		if ($bReckoned)
		{
			$arQuote['IN_COUNTER_FLAG'] = true;
			if ($tsCloseDate < $tsMax)
				$arQuote['EXPIRED_FLAG'] = true;
		}
		unset($tsCloseDate, $tsNow, $counterData);
	}

	$arResult['QUOTE'][$entityID] = $arQuote;
	$arResult['QUOTE_UF'][$entityID] = array();
	$arResult['QUOTE_ID'][$entityID] = $entityID;
}
$arResult['ROWS_COUNT'] = $obRes->SelectedRowsCount();
$arResult['DB_LIST'] = $obRes;
$arResult['DB_FILTER'] = $arFilter;

$CCrmUserType->ListAddEnumFieldsValue(
	$arResult,
	$arResult['QUOTE'],
	$arResult['QUOTE_UF'],
	($isInExportMode ? ', ' : '<br />'),
	$isInExportMode,
	array(
		'FILE_URL_TEMPLATE' =>
			'/bitrix/components/bitrix/crm.quote.show/show_file.php?ownerId=#owner_id#&fieldName=#field_name#&fileId=#file_id#'
	)
);

$arResult['ENABLE_TOOLBAR'] = isset($arParams['ENABLE_TOOLBAR']) ? $arParams['ENABLE_TOOLBAR'] : false;
if($arResult['ENABLE_TOOLBAR'])
{
	$arResult['PATH_TO_QUOTE_ADD'] = CComponentEngine::MakePathFromTemplate(
		$arParams['PATH_TO_QUOTE_EDIT'],
		array('quote_id' => 0)
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
		if(isset($internalContext['LEAD_ID']))
		{
			$addParams['lead_id'] = $internalContext['LEAD_ID'];
		}
		if(isset($internalContext['DEAL_ID']))
		{
			$addParams['deal_id'] = $internalContext['DEAL_ID'];
		}
	}

	if(!empty($addParams))
	{
		$arResult['PATH_TO_QUOTE_ADD'] = CHTTP::urlAddParams(
			$arResult['PATH_TO_QUOTE_ADD'],
			$addParams
		);
	}
}

if (isset($arResult['QUOTE_ID']) && !empty($arResult['QUOTE_ID']))
{
	// try to load product rows
	$arProductRows = CCrmQuote::LoadProductRows(array_keys($arResult['QUOTE_ID']));
	foreach($arProductRows as $arProductRow)
	{
		$ownerID = $arProductRow['OWNER_ID'];
		if(!isset($arResult['QUOTE'][$ownerID]))
		{
			continue;
		}

		$arEntity = &$arResult['QUOTE'][$ownerID];
		if(!isset($arEntity['PRODUCT_ROWS']))
		{
			$arEntity['PRODUCT_ROWS'] = array();
		}
		$arEntity['PRODUCT_ROWS'][] = $arProductRow;
	}

	// checkig access for operation
	$arQuoteAttr = CCrmPerms::GetEntityAttr('QUOTE', $arResult['QUOTE_ID']);
	foreach ($arResult['QUOTE_ID'] as $iQuoteId)
	{
		$arResult['QUOTE'][$iQuoteId]['EDIT'] = $CCrmPerms->CheckEnityAccess('QUOTE', 'WRITE', $arQuoteAttr[$iQuoteId]);
		$arResult['QUOTE'][$iQuoteId]['DELETE'] = $CCrmPerms->CheckEnityAccess('QUOTE', 'DELETE', $arQuoteAttr[$iQuoteId]);

		/*---bizproc---$arResult['QUOTE'][$iQuoteId]['BIZPROC_LIST'] = array();
		foreach ($arBPData as $arBP)
		{
			if (!CBPDocument::CanUserOperateDocument(
				CBPCanUserOperateOperation::StartWorkflow,
				$userID,
				array('crm', 'CCrmDocumentQuote', 'QUOTE_'.$arResult['QUOTE'][$iQuoteId]['ID']),
				array(
					'UserGroups' => $CCrmBizProc->arCurrentUserGroups,
					'DocumentStates' => $arDocumentStates,
					'WorkflowTemplateId' => $arBP['ID'],
					'CreatedBy' => $arResult['QUOTE'][$iQuoteId]['ASSIGNED_BY'],
					'UserIsAdmin' => $isAdmin,
					'CRMEntityAttr' =>  $arQuoteAttr[$iQuoteId]
				)
			))
			{
				continue;
			}

			$arBP['PATH_TO_BIZPROC_START'] = CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_QUOTE_SHOW'],
				array(
					'quote_id' => $arResult['QUOTE'][$iQuoteId]['ID']
				)),
				array(
					'workflow_template_id' => $arBP['ID'], 'bizproc_start' => 1,  'sessid' => bitrix_sessid(),
					'CRM_QUOTE_SHOW_V12_active_tab' => 'tab_bizproc', 'backurl' => $arParams['PATH_TO_QUOTE_LIST'])
			);
			$arResult['QUOTE'][$iQuoteId]['BIZPROC_LIST'][] = $arBP;
		}*/
	}
}

if (!$isInExportMode)
{
	$arResult['NEED_FOR_REBUILD_QUOTE_ATTRS'] = false;
	if(!$bInternal && CCrmPerms::IsAdmin() && COption::GetOptionString('crm', '~CRM_REBUILD_QUOTE_ATTR', 'N') === 'Y')
	{
		$arResult['PATH_TO_PRM_LIST'] = CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_perm_list'));
		$arResult['NEED_FOR_REBUILD_QUOTE_ATTRS'] = true;
	}

	$this->IncludeComponentTemplate();
	include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.quote/include/nav.php');
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
		Header('Content-Disposition: attachment;filename=quotes.csv');
	}
	elseif($sExportType === 'excel')
	{
		Header('Content-Type: application/vnd.ms-excel');
		Header('Content-Disposition: attachment;filename=quotes.xls');
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
