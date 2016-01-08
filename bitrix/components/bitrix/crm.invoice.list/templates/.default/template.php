<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
global $APPLICATION;
CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/interface_grid.js');

if($arResult['NEED_FOR_REBUILD_INVOICE_ATTRS']):
	?><div id="rebuildInvoiceAttrsMsg" class="crm-view-message">
		<?=GetMessage('CRM_INVOICE_REBUILD_ACCESS_ATTRS', array('#ID#' => 'rebuildInvoiceAttrsLink', '#URL#' => $arResult['PATH_TO_PRM_LIST']))?>
	</div><?
endif;

$currentUserID = $arResult['CURRENT_USER_ID'];
$isInternal = $arResult['INTERNAL'];
$enableToolbar = ($arResult['ENABLE_TOOLBAR'] === 'Y') ? true : false;

$gridManagerID = $arResult['GRID_ID'].'_MANAGER';
$gridManagerCfg = array(
	'ownerType' => 'INVOICE',
	'gridId' => $arResult['GRID_ID'],
	'formName' => "form_{$arResult['GRID_ID']}",
	'allRowsCheckBoxId' => "actallrows_{$arResult['GRID_ID']}",
);
$prefix = $arResult['GRID_ID'];
?>
<script type="text/javascript">
function crm_invoice_delete_grid(title, message, btnTitle, path)
{
	var d;
	d = new BX.CDialog({
		title: title,
		head: '',
		content: message,
		resizable: false,
		draggable: true,
		height: 70,
		width: 300
	});

	var _BTN = [
		{
			title: btnTitle,
			id: 'crmOk',
			'action': function ()
			{
				window.location.href = path;
				BX.WindowManager.Get().Close();
			}
		},
		BX.CDialog.btnCancel
	];
	d.ClearButtons();
	d.SetButtons(_BTN);
	d.Show();
}
BX.ready(
	function()
	{
		if (BX('actallrows_<?=$arResult['GRID_ID']?>')) {
			BX.bind(BX('actallrows_<?=$arResult['GRID_ID']?>'), 'click', function () {
				var el_t = BX.findParent(this, {tagName : 'table'});
				var el_s = BX.findChild(el_t, {tagName : 'select'}, true, false);
				for (i = 0; i < el_s.options.length; i++)
				{
					if (el_s.options[i].value == 'tasks' || el_s.options[i].value == 'calendar')
						el_s.options[i].disabled = this.checked;
				}
				if (this.checked && (el_s.options[el_s.selectedIndex].value == 'tasks' || el_s.options[el_s.selectedIndex].value == 'calendar'))
					el_s.selectedIndex = 0;
			});
		}
	}
);
</script>
<?
echo CCrmViewHelper::RenderInvoiceStatusSettings();
for ($i=0, $ic=sizeof($arResult['FILTER']); $i < $ic; $i++)
{
	$filterField = $arResult['FILTER'][$i];
	$filterID = $filterField['id'];
	$filterType = $filterField['type'];
	$enable_settings = $filterField['enable_settings'];

	if($filterID === 'PRODUCT_ROW_PRODUCT_ID')
	{
		$productID = isset($arResult['DB_FILTER'][$filterID])
			? $arResult['DB_FILTER'][$filterID] : 0;

		ob_start();
		$APPLICATION->IncludeComponent('bitrix:crm.entity.selector',
			'',
			array(
				'ENTITY_TYPE' => 'PRODUCT',
				'INPUT_NAME' => $filterID,
				'INPUT_VALUE' => $productID,
				'FORM_NAME' => $arResult['GRID_ID'],
				'MULTIPLE' => 'N',
				'FILTER' => true
			),
			false,
			array('HIDE_ICONS' => 'Y')
		);
		$val = ob_get_contents();
		ob_end_clean();

		$arResult['FILTER'][$i]['type'] = 'custom';
		$arResult['FILTER'][$i]['value'] = $val;

		continue;
	}

	if ($filterType !== 'user')
	{
		continue;
	}

	$userID = isset($arResult['DB_FILTER'][$filterID])
		? (intval(is_array($arResult['DB_FILTER'][$filterID])
			? $arResult['DB_FILTER'][$filterID][0]
			: $arResult['DB_FILTER'][$filterID]))
		: 0;
	$userName = $userID > 0 ? CCrmViewHelper::GetFormattedUserName($userID) : '';

	ob_start();
	CCrmViewHelper::RenderUserCustomSearch(
		array(
			'ID' => "{$prefix}_{$filterID}_SEARCH",
			'SEARCH_INPUT_ID' => "{$prefix}_{$filterID}_NAME",
			'SEARCH_INPUT_NAME' => "{$filterID}_name",
			'DATA_INPUT_ID' => "{$prefix}_{$filterID}",
			'DATA_INPUT_NAME' => $filterID,
			'COMPONENT_NAME' => "{$prefix}_{$filterID}_SEARCH",
			'SITE_ID' => SITE_ID,
			'NAME_FORMAT' => $arParams['NAME_TEMPLATE'],
			'USER' => array('ID' => $userID, 'NAME' => $userName),
			'DELAY' => 100
		)
	);
	$val = ob_get_clean();

	$arResult['FILTER'][$i]['type'] = 'custom';
	$arResult['FILTER'][$i]['value'] = $val;

	$filterFieldInfo = array(
		'typeName' => 'USER',
		'id' => $filterID,
		'params' => array(
			'data' => array(
				'paramName' => "{$filterID}",
				'elementId' => "{$prefix}_{$filterID}"
			),
			'search' => array(
				'paramName' => "{$filterID}_name",
				'elementId' => "{$prefix}_{$filterID}_NAME"
			)
		)
	);

	if($enable_settings)
	{
		ob_start();
		CCrmViewHelper::RenderUserCustomSearch(
			array(
				'ID' => "FILTER_SETTINGS_{$prefix}_{$filterID}_SEARCH",
				'SEARCH_INPUT_ID' => "FILTER_SETTINGS_{$prefix}_{$filterID}_NAME",
				'SEARCH_INPUT_NAME' => "{$filterID}_name",
				'DATA_INPUT_ID' => "FILTER_SETTINGS_{$prefix}_{$filterID}",
				'DATA_INPUT_NAME' => $filterID,
				'COMPONENT_NAME' => "FILTER_SETTINGS_{$prefix}_{$filterID}_SEARCH",
				'SITE_ID' => SITE_ID,
				'NAME_FORMAT' => $arParams['NAME_TEMPLATE'],
				'USER' => array('ID' => $userID, 'NAME' => $userName),
				'ZINDEX' => 4000,
				'DELAY' => 100
			)
		);
		$arResult['FILTER'][$i]['settingsHtml'] = ob_get_clean();

		$filterFieldInfo['params']['data']['settingsElementId'] = "FILTER_SETTINGS_{$prefix}_{$filterID}";
		$filterFieldInfo['params']['search']['settingsElementId'] = "FILTER_SETTINGS_{$prefix}_{$filterID}_NAME";
	}

	$gridManagerCfg['filterFields'][] = $filterFieldInfo;
}

$arResult['GRID_DATA'] = array();
$arColumns = array();
foreach ($arResult['HEADERS'] as $arHead)
	$arColumns[$arHead['id']] = false;

$arInvoiceStatusInfoValues = array();

foreach($arResult['INVOICE'] as $sKey =>  $arInvoice)
{
	$arInvoiceStatusInfoValues[$arInvoice['~ID']] = array(
		'PAY_VOUCHER_DATE' => ($arInvoice['~PAY_VOUCHER_DATE'] != '') ? FormatDate('SHORT', MakeTimeStamp($arInvoice['~PAY_VOUCHER_DATE'])) : '',
		'PAY_VOUCHER_NUM' => ($arInvoice['~PAY_VOUCHER_NUM'] != '') ? $arInvoice['~PAY_VOUCHER_NUM'] : '',
		'DATE_MARKED' => ($arInvoice['~DATE_MARKED'] != '') ? FormatDate('SHORT', MakeTimeStamp($arInvoice['~DATE_MARKED'])) : '',
		'REASON_MARKED' => ($arInvoice['~REASON_MARKED'] != '') ? $arInvoice['~REASON_MARKED'] : ''
	);

	$arActions = array();
	$arActions[] =  array(
		'ICONCLASS' => 'view',
		'TITLE' => GetMessage('CRM_INVOICE_SHOW_TITLE'),
		'TEXT' => GetMessage('CRM_INVOICE_SHOW'),
		'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arInvoice['PATH_TO_INVOICE_SHOW'])."');",
		'DEFAULT' => true
	);
	$arActions[] = array(
		'ICONCLASS' => 'print',
		'TITLE' => GetMessage('CRM_INVOICE_PAYMENT_HTML_TITLE'),
		'TEXT' => GetMessage('CRM_INVOICE_PAYMENT_HTML'),
		'ONCLICK' => "jsUtils.OpenWindow('".CUtil::JSEscape(CHTTP::urlAddParams(
			$arInvoice['PATH_TO_INVOICE_PAYMENT'],
			array('PRINT' => 'Y', 'ncc' => '1')
		))."', 960, 600);"
	);

	if (is_callable(array('CSalePdf', 'isPdfAvailable')) && CSalePdf::isPdfAvailable())
	{
		$arActions[] = array(
			'ICONCLASS' => 'view',
			'TITLE' => GetMessage('CRM_INVOICE_PAYMENT_PDF_TITLE'),
			'TEXT' => GetMessage('CRM_INVOICE_PAYMENT_PDF'),
			'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape(CHTTP::urlAddParams(
				$arInvoice['PATH_TO_INVOICE_PAYMENT'],
				array('pdf' => 1, 'DOWNLOAD' => 'Y', 'ncc' => '1')
			))."');"
		);
	}

	if ($arInvoice['EDIT']):
		$arActions[] =  array(
			'ICONCLASS' => 'edit',
			'TITLE' => GetMessage('CRM_INVOICE_EDIT_TITLE'),
			'TEXT' => GetMessage('CRM_INVOICE_EDIT'),
			'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arInvoice['PATH_TO_INVOICE_EDIT'])."');"
		);
		$arActions[] =  array(
			'ICONCLASS' => 'copy',
			'TITLE' => GetMessage('CRM_INVOICE_COPY_TITLE'),
			'TEXT' => GetMessage('CRM_INVOICE_COPY'),
			'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arInvoice['PATH_TO_INVOICE_COPY'])."');"
		);
	endif;

	if ($arInvoice['DELETE'] && !$arResult['INTERNAL']):
		$arActions[] = array('SEPARATOR' => true);
		$arActions[] =  array(
			'ICONCLASS' => 'delete',
			'TITLE' => GetMessage('CRM_INVOICE_DELETE_TITLE'),
			'TEXT' => GetMessage('CRM_INVOICE_DELETE'),
			'ONCLICK' => "crm_invoice_delete_grid('".CUtil::JSEscape(GetMessage('CRM_INVOICE_DELETE_TITLE'))."', '".CUtil::JSEscape(GetMessage('CRM_INVOICE_DELETE_CONFIRM'))."', '".CUtil::JSEscape(GetMessage('CRM_INVOICE_DELETE'))."', '".CUtil::JSEscape($arInvoice['PATH_TO_INVOICE_DELETE'])."')"
		);
	endif;

	$resultRow = array(
		'id' => $arInvoice['ID'],
		'actions' => $arActions,
		'data' => $arInvoice,
		'editable' => !$arInvoice['EDIT'] ? ($arResult['INTERNAL'] ? 'N' : $arColumns) : 'Y',
		'columns' => array(
			'ACCOUNT_NUMBER' => '<a target="_self" href="'.$arInvoice['PATH_TO_INVOICE_SHOW'].'">'.$arInvoice['ACCOUNT_NUMBER'].'</a>',
			'STATUS_ID' => CCrmViewHelper::RenderInvoiceStatusControl(
				array(
					'PREFIX' => "{$arResult['GRID_ID']}_PROGRESS_BAR_",
					'ENTITY_ID' => $arInvoice['~ID'],
					'CURRENT_ID' => $arInvoice['~STATUS_ID'],
					'SERVICE_URL' => '/bitrix/components/bitrix/crm.invoice.list/list.ajax.php'
				)
			),
			'RESPONSIBLE_ID' => $arInvoice['~RESPONSIBLE_ID'] > 0 ?
				'<a href="'.$arInvoice['PATH_TO_USER_PROFILE'].'" id="balloon_'.$arResult['GRID_ID'].'_'.$arInvoice['ID'].'">'.$arInvoice['RESPONSIBLE'].'</a>'.
					'<script type="text/javascript">BX.tooltip('.$arInvoice['~RESPONSIBLE_ID'].', "balloon_'.$arResult['GRID_ID'].'_'.$arInvoice['ID'].'", "");</script>'
				: '',
			'DATE_PAY_BEFORE' => ($arInvoice['DATE_PAY_BEFORE'] == "") ? '&nbsp' : '<nobr>'.FormatDate('SHORT', MakeTimeStamp($arInvoice['DATE_PAY_BEFORE'])).'</nobr>',
			'DATE_INSERT' => ($arInvoice['DATE_INSERT'] == "") ? '&nbsp' : '<nobr>'.FormatDate('SHORT', MakeTimeStamp($arInvoice['DATE_INSERT'])).'</nobr>',
			'DATE_BILL' => ($arInvoice['DATE_BILL'] == "") ? '&nbsp' : '<nobr>'.FormatDate('SHORT', MakeTimeStamp($arInvoice['DATE_BILL'])).'</nobr>',
			'DATE_MARKED' => ($arInvoice['DATE_MARKED'] == "") ? '&nbsp' : '<nobr>'.FormatDate('SHORT', MakeTimeStamp($arInvoice['DATE_MARKED'])).'</nobr>',
			'DATE_STATUS' => ($arInvoice['DATE_STATUS'] == "") ? '&nbsp' : '<nobr>'.FormatDate('SHORT', MakeTimeStamp($arInvoice['DATE_STATUS'])).'</nobr>',
			'DATE_UPDATE' => ($arInvoice['DATE_UPDATE'] == "") ? '&nbsp' : '<nobr>'.FormatDate('SHORT', MakeTimeStamp($arInvoice['DATE_UPDATE'])).'</nobr>',
			'PAY_VOUCHER_DATE' => ($arInvoice['PAY_VOUCHER_DATE'] == "") ? '&nbsp' : '<nobr>'.FormatDate('SHORT', MakeTimeStamp($arInvoice['PAY_VOUCHER_DATE'])).'</nobr>',
			'PRICE' => $arInvoice['FORMATTED_PRICE'],
			'TAX_VALUE' => $arInvoice['FORMATTED_TAX_VALUE'],
			'CURRENCY' => htmlspecialcharsbx(CCrmCurrency::GetCurrencyName($arInvoice['CURRENCY'])),
			'ENTITIES_LINKS' => $arInvoice['FORMATTED_ENTITIES_LINKS'],
			'PERSON_TYPE_ID' => trim($arResult['PERSON_TYPES'][$arInvoice['PERSON_TYPE_ID']]),
			'PAY_SYSTEM_ID' => trim($arResult['PAY_SYSTEMS_LIST'][$arInvoice['PERSON_TYPE_ID']][$arInvoice['PAY_SYSTEM_ID']]),
			'COMMENTS' => $arInvoice['~COMMENTS'],
			'USER_DESCRIPTION' => $arInvoice['~USER_DESCRIPTION']
		) + $arResult['INVOICE_UF'][$sKey]
	);
	if ($arInvoice['INVOICE_IN_COUNTER_FLAG'] === true)
	{
		if ($resultRow['columnClasses']['DATE_PAY_BEFORE'] != '')
			$resultRow['columnClasses']['DATE_PAY_BEFORE'] .= ' ';
		else
			$resultRow['columnClasses']['DATE_PAY_BEFORE'] = '';
		$resultRow['columnClasses']['DATE_PAY_BEFORE'] .= 'crm-list-invoice-today';
	}
	if ($arInvoice['INVOICE_EXPIRED_FLAG'] === true)
	{
		if ($resultRow['columnClasses']['DATE_PAY_BEFORE'] != '')
			$resultRow['columnClasses']['DATE_PAY_BEFORE'] .= ' ';
		else
			$resultRow['columnClasses']['DATE_PAY_BEFORE'] = '';
		$resultRow['columnClasses']['DATE_PAY_BEFORE'] .= 'crm-list-invoice-time-expired';
	}

	$arResult['GRID_DATA'][] = &$resultRow;
	unset($resultRow);
}
$APPLICATION->IncludeComponent('bitrix:main.user.link',
	'',
	array(
		'AJAX_ONLY' => 'Y',
	),
	false,
	array('HIDE_ICONS' => 'Y')
);

if($enableToolbar)
{
	$entityId = 0;
	$entityType = '';
	if (is_array($arParams['INTERNAL_FILTER']))
	{
		$internalFilter = $arParams['INTERNAL_FILTER'];
		if (isset($internalFilter['UF_QUOTE_ID']))
		{
			$entityId = intval($internalFilter['UF_QUOTE_ID']);
			$entityType = 'quote';
		}
		elseif (isset($internalFilter['UF_DEAL_ID']))
		{
			$entityId = intval($internalFilter['UF_DEAL_ID']);
			$entityType = 'deal';
		}
		elseif (isset($internalFilter['UF_COMPANY_ID']))
		{
			$entityId = intval($internalFilter['UF_COMPANY_ID']);
			$entityType = 'company';
		}
		elseif (isset($internalFilter['UF_CONTACT_ID']))
		{
			$entityId = intval($internalFilter['UF_CONTACT_ID']);
			$entityType = 'contact';
		}
		unset($internalFilter);
	}
	$toolbarButtons[0] = array(
		'TEXT' => GetMessage('CRM_INVOICE_LIST_ADD_SHORT'),
		'TITLE' => $arResult['INTERNAL_ADD_BTN_TITLE'],
		'ICON' => 'btn-new crm-invoice-command-add-invoice'
	);
	if ($entityId > 0)
	{
		$toolbarButtons[0]['LINK'] = CComponentEngine::makePathFromTemplate(
			$arParams['PATH_TO_INVOICE_EDIT'],
			array('invoice_id' => 0)
		)."?$entityType=$entityId";
	}
	$toolbarButtons[] = array(
		'LABEL' => true,
		'TEXT' => $arResult['TOOLBAR_LABEL_TEXT']
	);

	$APPLICATION->IncludeComponent(
		'bitrix:crm.interface.toolbar',
		'',
		array(
			'TOOLBAR_ID' => $arResult['GRID_ID'].'_inv_tb',
			'BUTTONS' => $toolbarButtons
		),
		$component,
		array('HIDE_ICONS' => 'Y')
	);
}

$APPLICATION->IncludeComponent(
	'bitrix:crm.interface.grid',
	'',
	array(
		'GRID_ID' => $arResult['GRID_ID'],
		'HEADERS' => $arResult['HEADERS'],
		'SORT' => $arResult['SORT'],
		'SORT_VARS' => $arResult['SORT_VARS'],
		'ROWS' => $arResult['GRID_DATA'],
		'FOOTER' => array(array('title' => GetMessage('CRM_ALL'), 'value' => $arResult['ROWS_COUNT'])),
		'EDITABLE' =>  !$arResult['PERMS']['WRITE'] || $arResult['INTERNAL'] ? 'N' : 'Y',
		'ACTIONS' => array(
			'delete' => $arResult['PERMS']['DELETE']/*,
			'custom_html' => $actionHtml,
			'list' => $arActionList*/
		),
		'ACTION_ALL_ROWS' => true,
		'NAV_OBJECT' => $arResult['DB_LIST'],
		'FORM_ID' => $arResult['FORM_ID'],
		'TAB_ID' => $arResult['TAB_ID'],
		'AJAX_MODE' => $arResult['AJAX_MODE'],
		'AJAX_ID' => $arResult['AJAX_ID'],
		'AJAX_OPTION_JUMP' => $arResult['AJAX_OPTION_JUMP'],
		'AJAX_OPTION_HISTORY' => $arResult['AJAX_OPTION_HISTORY'],
		'AJAX_LOADER' => isset($arParams['AJAX_LOADER']) ? $arParams['AJAX_LOADER'] : null,
		'FILTER' => $arResult['FILTER'],
		'FILTER_PRESETS' => $arResult['FILTER_PRESETS'],
		'MANAGER' => array(
			'ID' => $gridManagerID,
			'CONFIG' => $gridManagerCfg
		)
	),
	$component
);

?><script type="text/javascript">
	BX.ready(function(){
		if (typeof(BX.CrmInvoiceStatusManager) === 'function')
		{
			BX.CrmInvoiceStatusManager.statusInfoValues = <?= CUtil::PhpToJSObject($arInvoiceStatusInfoValues) ?>;
		}
	});
</script>
<?if($arResult['NEED_FOR_REBUILD_INVOICE_ATTRS']):?>
<script type="text/javascript">
	BX.ready(
		function()
		{
			var link = BX("rebuildInvoiceAttrsLink");
			if(link)
			{
				BX.bind(
					link,
					"click",
					function(e)
					{
						var msg = BX("rebuildInvoiceAttrsMsg");
						if(msg)
						{
							msg.style.display = "none";
						}
					}
				);
			}
		}
	);
</script>
<?endif;?>
