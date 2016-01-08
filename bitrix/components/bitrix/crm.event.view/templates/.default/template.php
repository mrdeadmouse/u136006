<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
global $APPLICATION, $USER;
$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');
$APPLICATION->AddHeadScript('/bitrix/js/crm/interface_grid.js');

if($arResult['ENABLE_CONTROL_PANEL'])
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.control_panel',
		'',
		array(
			'ID' => 'EVENT_LIST',
			'ACTIVE_ITEM_ID' => '',
			'PATH_TO_COMPANY_LIST' => isset($arParams['PATH_TO_COMPANY_LIST']) ? $arParams['PATH_TO_COMPANY_LIST'] : '',
			'PATH_TO_COMPANY_EDIT' => isset($arParams['PATH_TO_COMPANY_EDIT']) ? $arParams['PATH_TO_COMPANY_EDIT'] : '',
			'PATH_TO_CONTACT_LIST' => isset($arParams['PATH_TO_CONTACT_LIST']) ? $arParams['PATH_TO_CONTACT_LIST'] : '',
			'PATH_TO_CONTACT_EDIT' => isset($arParams['PATH_TO_CONTACT_EDIT']) ? $arParams['PATH_TO_CONTACT_EDIT'] : '',
			'PATH_TO_DEAL_LIST' => isset($arParams['PATH_TO_DEAL_LIST']) ? $arParams['PATH_TO_DEAL_LIST'] : '',
			'PATH_TO_DEAL_EDIT' => isset($arParams['PATH_TO_DEAL_EDIT']) ? $arParams['PATH_TO_DEAL_EDIT'] : '',
			'PATH_TO_LEAD_LIST' => isset($arParams['PATH_TO_LEAD_LIST']) ? $arParams['PATH_TO_LEAD_LIST'] : '',
			'PATH_TO_LEAD_EDIT' => isset($arParams['PATH_TO_LEAD_EDIT']) ? $arParams['PATH_TO_LEAD_EDIT'] : '',
			'PATH_TO_QUOTE_LIST' => isset($arResult['PATH_TO_QUOTE_LIST']) ? $arResult['PATH_TO_QUOTE_LIST'] : '',
			'PATH_TO_QUOTE_EDIT' => isset($arResult['PATH_TO_QUOTE_EDIT']) ? $arResult['PATH_TO_QUOTE_EDIT'] : '',
			'PATH_TO_INVOICE_LIST' => isset($arResult['PATH_TO_INVOICE_LIST']) ? $arResult['PATH_TO_INVOICE_LIST'] : '',
			'PATH_TO_INVOICE_EDIT' => isset($arResult['PATH_TO_INVOICE_EDIT']) ? $arResult['PATH_TO_INVOICE_EDIT'] : '',
			'PATH_TO_REPORT_LIST' => isset($arParams['PATH_TO_REPORT_LIST']) ? $arParams['PATH_TO_REPORT_LIST'] : '',
			'PATH_TO_DEAL_FUNNEL' => isset($arParams['PATH_TO_DEAL_FUNNEL']) ? $arParams['PATH_TO_DEAL_FUNNEL'] : '',
			'PATH_TO_EVENT_LIST' => isset($arParams['PATH_TO_EVENT_LIST']) ? $arParams['PATH_TO_EVENT_LIST'] : '',
			'PATH_TO_PRODUCT_LIST' => isset($arParams['PATH_TO_PRODUCT_LIST']) ? $arParams['PATH_TO_PRODUCT_LIST'] : ''
		),
		$component
	);
}

$gridManagerID = $managerID = $arResult['GRID_ID'].'_MANAGER';
$gridManagerCfg = array(
	'ownerType' => 'EVENT',
	'gridId' => $arResult['GRID_ID'],
	'formName' => "form_{$arResult['GRID_ID']}",
	'allRowsCheckBoxId' => "actallrows_{$arResult['GRID_ID']}",
	'activityEditorId' => '',
	'serviceUrl' => '',
	'filterFields' => array()
);
$prefix = $arResult['GRID_ID'];
for ($i=0, $ic=sizeof($arResult['FILTER']); $i < $ic; $i++)
{
	$filterID = $arResult['FILTER'][$i]['id'];
	if ($arResult['FILTER'][$i]['type'] === 'user')
	{
		$dbFilterID = $filterID;
		$filterFieldPrefix = $arResult['FILTER_FIELD_PREFIX'];
		if($filterFieldPrefix !== '')
		{
			$dbFilterID = substr($dbFilterID, strlen($filterFieldPrefix));
		}

		$userID = isset($arResult['DB_FILTER'][$dbFilterID])
			? (intval(is_array($arResult['DB_FILTER'][$dbFilterID])
				? $arResult['DB_FILTER'][$dbFilterID][0]
				: $arResult['DB_FILTER'][$dbFilterID]))
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

		$gridManagerCfg['filterFields'][] = $filterFieldInfo;
	}
}


	$arResult['GRID_DATA'] = array();
	foreach($arResult['EVENT'] as $arEvent)
	{
		$arEvent['FILE_HTML'] = "";
		if(!empty($arEvent['FILES']))
		{
			$arEvent['FILE_HTML'] = '<div class="event-detail-files"><label class="event-detail-files-title">'.GetMessage('CRM_EVENT_TABLE_FILES').':</label><div class="event-detail-files-list">';
				foreach($arEvent['FILES'] as $key=>$value)
					$arEvent['FILE_HTML'] .= '<div class="event-detail-file"><span class="event-detail-file-number">'.$key.'.</span><span class="event-detail-file-info"><a href="'.htmlspecialcharsbx($value['PATH']).'" target="_blank" class="event-detail-file-link">'.htmlspecialcharsbx($value['NAME']).'</a><span class="event-detail-file-size">('.htmlspecialcharsbx($value['SIZE']).')</span></span></div>';
			$arEvent['FILE_HTML'] .= '</div></div>';
		}

		$arActions = array();
		if (CCrmPerms::IsAdmin() || ($arEvent['CREATED_BY_ID'] == CCrmPerms::GetCurrentUserID() && $arEvent['EVENT_TYPE'] == 0))
		{
			$arActions[] =  array(
				'ICONCLASS' => 'delete',
				'TITLE' => GetMessage('CRM_EVENT_DELETE_TITLE'),
				'TEXT' => GetMessage('CRM_EVENT_DELETE'),
				'ONCLICK'=> "BX.CrmEventListManager.items[\"{$managerID}\"].deleteItem(\"{$arEvent['ID']}\")"
			);
		}

		$eventColor = '';
		if ($arEvent['EVENT_TYPE'] == '0')
			$eventColor = 'color: #208c0b';
		elseif ($arEvent['EVENT_TYPE'] == '2')
			$eventColor = 'color: #9c8000';
		$arColumns = array(
			'CREATED_BY_FULL_NAME' => $arEvent['CREATED_BY_FULL_NAME'] == ''? '' :
				'<a href="'.$arEvent['CREATED_BY_LINK'].'" id="balloon_'.$arResult['GRID_ID'].'_'.$arEvent['ID'].'">'.$arEvent['CREATED_BY_FULL_NAME'].'</a>'.
				'<script type="text/javascript">BX.tooltip('.$arEvent['CREATED_BY_ID'].', "balloon_'.$arResult['GRID_ID'].'_'.$arEvent['ID'].'", "");</script>',
			'EVENT_NAME' => '<span style="'.$eventColor.'">'.$arEvent['EVENT_NAME'].'</span>',
			'EVENT_DESC' => $arEvent['EVENT_DESC'].$arEvent['FILE_HTML'],
			'DATE_CREATE' => FormatDate('x', MakeTimeStamp($arEvent['DATE_CREATE']), (time() + CTimeZone::GetOffset()))
		);
		if ($arResult['EVENT_ENTITY_LINK'] == 'Y')
		{
			$arColumns['ENTITY_TYPE'] = !empty($arEvent['ENTITY_TYPE'])? GetMessage('CRM_EVENT_ENTITY_TYPE_'.$arEvent['ENTITY_TYPE']): '';
			$arColumns['ENTITY_TITLE'] = !empty($arEvent['ENTITY_TITLE'])?
				'<a href="'.$arEvent['ENTITY_LINK'].'" id="balloon_'.$arResult['GRID_ID'].'_I_'.$arEvent['ID'].'">'.$arEvent['ENTITY_TITLE'].'</a>'.
				'<script type="text/javascript">BX.tooltip("'.$arEvent['ENTITY_TYPE'].'_'.$arEvent['ENTITY_ID'].'", "balloon_'.$arResult['GRID_ID'].'_I_'.$arEvent['ID'].'", "/bitrix/components/bitrix/crm.'.strtolower($arEvent['ENTITY_TYPE']).'.show/card.ajax.php", "crm_balloon'.($arEvent['ENTITY_TYPE'] == 'LEAD' || $arEvent['ENTITY_TYPE'] == 'DEAL' || $arEvent['ENTITY_TYPE'] == 'QUOTE' ? '_no_photo': '_'.strtolower($arEvent['ENTITY_TYPE'])).'", true);</script>'
				: '';
		}
		else
		{
			unset($arEvent['ENTITY_TYPE']);
			unset($arEvent['ENTITY_TITLE']);
		}

		$arResult['GRID_DATA'][] = array(
			'id' => $arEvent['ID'],
			'data' => $arEvent,
			'actions' => $arActions,
			'editable' =>($USER->IsAdmin() || ($arEvent['CREATED_BY_ID'] == $USER->GetId() && $arEvent['EVENT_TYPE'] == 0))? true: false,
			'columns' => $arColumns
		);
	}
	$APPLICATION->IncludeComponent('bitrix:main.user.link',
		'',
		array(
			'AJAX_ONLY' => 'Y',
			'NAME_TEMPLATE' => $arParams["NAME_TEMPLATE"]
		),
		false,
		array('HIDE_ICONS' => 'Y')
	);

	if(!$arResult['INTERNAL'])
	{
		$APPLICATION->ShowViewContent('crm-grid-filter');
	}
	else
	{
		// Render toolbar in internal mode
		$toolbarButtons = array();
		if(isset($arResult['ENTITY_TYPE']) && $arResult['ENTITY_TYPE'] !== ''
			&& isset($arResult['ENTITY_ID']) && is_int($arResult['ENTITY_ID']) && $arResult['ENTITY_ID'] > 0)
		{
			$toolbarButtons[] = array(
				'TEXT' => GetMessage('CRM_EVENT_VIEW_ADD_SHORT'),
				'TITLE' => GetMessage('CRM_EVENT_VIEW_ADD'),
				'ONCLICK' => "BX.CrmEventListManager.items[\"{$managerID}\"].addItem()",
				'ICON' => 'btn-new'
			);
		}

		$toolbarButtons[] = array(
			'TEXT' => 'FILTER',
			'TEXT' => GetMessage('CRM_EVENT_VIEW_SHOW_FILTER_SHORT'),
			'TITLE' => GetMessage('CRM_EVENT_VIEW_SHOW_FILTER'),
			'ICON' => 'crm-filter-light-btn',
			'ALIGNMENT' => 'right',
			'ONCLICK' => "BX.InterfaceGridFilterPopup.toggle('{$arResult['GRID_ID']}', this)"
		);

		$APPLICATION->IncludeComponent(
			'bitrix:crm.interface.toolbar',
			'',
			array(
				'TOOLBAR_ID' => $toolbarID,
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
			'EDITABLE' => 'Y',
			'ACTIONS' => array(),
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
			'FILTER_TEMPLATE' => $arResult['INTERNAL'] ? 'popup' : '',
			'SHOW_FORM_TAG' => $arResult['INTERNAL'] && $arResult['INTERNAL_EDIT'] ? 'N' : 'Y',
			'MANAGER' => array(
				'ID' => $gridManagerID,
				'CONFIG' => $gridManagerCfg
			)
		),
		$component
	);

if ($arResult['EVENT_HINT_MESSAGE'] == 'Y' && COption::GetOptionString('crm', 'mail', '') != ''):
?>
<div class="crm_notice_message"><?=GetMessage('CRM_IMPORT_EVENT', Array('%EMAIL%' => COption::GetOptionString('crm', 'mail', '')));?></div>
<?endif;?>

<script type="text/javascript">
	BX.ready(
		function()
		{
			BX.CrmEventListManager.messages =
			{
				deletionConfirmationDlgTitle: "<?=GetMessageJS('CRM_EVENT_DELETE_TITLE')?>",
				deletionConfirmationDlgContent: "<?=GetMessageJS('CRM_EVENT_DELETE_CONFIRM')?>",
				deletionConfirmationDlgBtn: "<?=GetMessageJS('CRM_EVENT_DELETE')?>"
			};

			BX.CrmEventListManager.create("<?=CUtil::JSEscape($managerID)?>",
				{
					addItemUrl: "/bitrix/components/bitrix/crm.event.add/box.php",
					deleteItemUrl: "<?=CUtil::JSEscape($arResult['PATH_TO_EVENT_DELETE'])?>",
					entityTypeName: "<?=CUtil::JSEscape($arResult['ENTITY_TYPE'])?>",
					entityId: "<?=CUtil::JSEscape($arResult['ENTITY_ID'])?>",
					gridId: "<?=CUtil::JSEscape($arResult['GRID_ID'])?>",
					tabId: "<?=CUtil::JSEscape($arResult['TAB_ID'])?>",
					formId: "<?=CUtil::JSEscape($arResult['FORM_ID'])?>"
				}
			);
		}
	);
</script>
