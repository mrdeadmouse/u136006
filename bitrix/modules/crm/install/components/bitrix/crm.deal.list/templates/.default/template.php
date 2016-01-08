<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
global $APPLICATION;
CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/activity.js');
CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/interface_grid.js');

if($arResult['NEED_FOR_REBUILD_DEAL_ATTRS']):
	?><div id="rebuildDealAttrsMsg" class="crm-view-message">
		<?=GetMessage('CRM_DEAL_REBUILD_ACCESS_ATTRS', array('#ID#' => 'rebuildDealAttrsLink', '#URL#' => $arResult['PATH_TO_PRM_LIST']))?>
	</div><?
endif;
if($arResult['NEED_FOR_REBUILD_DEAL_STATISTICS']):
	?><div id="rebuildDealStatsMsg" class="crm-view-message">
		<?=GetMessage('CRM_DEAL_REBUILD_STATISTICS', array('#ID#' => 'rebuildDealStatsLink', '#URL#' => '#'))?>
	</div><?
endif;

$currentUserID = $arResult['CURRENT_USER_ID'];
$isInternal = $arResult['INTERNAL'];
$activityEditorID = '';
if(!$isInternal):
	$activityEditorID = "{$arResult['GRID_ID']}_activity_editor";
	$APPLICATION->IncludeComponent(
		'bitrix:crm.activity.editor',
		'',
		array(
			'EDITOR_ID' => $activityEditorID,
			'PREFIX' => $arResult['GRID_ID'],
			'OWNER_TYPE' => 'DEAL',
			'OWNER_ID' => 0,
			'READ_ONLY' => false,
			'ENABLE_UI' => false,
			'ENABLE_TOOLBAR' => false
		),
		null,
		array('HIDE_ICONS' => 'Y')
	);
endif;

$gridManagerID = $arResult['GRID_ID'].'_MANAGER';
$gridManagerCfg = array(
	'ownerType' => 'DEAL',
	'gridId' => $arResult['GRID_ID'],
	'formName' => "form_{$arResult['GRID_ID']}",
	'allRowsCheckBoxId' => "actallrows_{$arResult['GRID_ID']}",
	'activityEditorId' => $activityEditorID,
	'serviceUrl' => '/bitrix/components/bitrix/crm.activity.editor/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
	'filterFields' => array()
);
$prefix = $arResult['GRID_ID'];
?>
<script type="text/javascript">
function crm_deal_delete_grid(title, message, btnTitle, path)
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
	echo CCrmViewHelper::RenderDealStageSettings();
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
	foreach($arResult['DEAL'] as $sKey =>  $arDeal)
	{
		$jsTitle = isset($arDeal['~TITLE']) ? CUtil::JSEscape($arDeal['~TITLE']) : '';
		$jsShowUrl = isset($arDeal['PATH_TO_DEAL_SHOW']) ? CUtil::JSEscape($arDeal['PATH_TO_DEAL_SHOW']) : '';

		$arActivityMenuItems = array();
		$arActions = array();
		$arActions[] =  array(
			'ICONCLASS' => 'view',
			'TITLE' => GetMessage('CRM_DEAL_SHOW_TITLE'),
			'TEXT' => GetMessage('CRM_DEAL_SHOW'),
			'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arDeal['PATH_TO_DEAL_SHOW'])."');",
			'DEFAULT' => true
		);
		if ($arDeal['EDIT']):
			$arActions[] =  array(
				'ICONCLASS' => 'edit',
				'TITLE' => GetMessage('CRM_DEAL_EDIT_TITLE'),
				'TEXT' => GetMessage('CRM_DEAL_EDIT'),
				'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arDeal['PATH_TO_DEAL_EDIT'])."');"
			);
			$arActions[] =  array(
				'ICONCLASS' => 'copy',
				'TITLE' => GetMessage('CRM_DEAL_COPY_TITLE'),
				'TEXT' => GetMessage('CRM_DEAL_COPY'),
				'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arDeal['PATH_TO_DEAL_COPY'])."');"
			);
		endif;

		if(!$isInternal):
			$arActions[] = array('SEPARATOR' => true);

			$arActions[] = $arActivityMenuItems[] = array(
				'ICONCLASS' => 'event',
				'TITLE' => GetMessage('CRM_DEAL_EVENT_TITLE'),
				'TEXT' => GetMessage('CRM_DEAL_EVENT'),
				'ONCLICK' => "javascript:(new BX.CDialog({'content_url':'/bitrix/components/bitrix/crm.event.add/box.php?FORM_TYPE=LIST&ENTITY_TYPE=DEAL&ENTITY_ID=".$arDeal['ID']."', 'width':'498', 'height':'275', 'resizable':false })).Show();"
			);

			if ($arDeal['EDIT'] && IsModuleInstalled('tasks')):
				$arActions[] = $arActivityMenuItems[] = array(
					'ICONCLASS' => 'task',
					'TITLE' => GetMessage('CRM_DEAL_TASK_TITLE'),
					'TEXT' => GetMessage('CRM_DEAL_TASK'),
					'ONCLICK' => 'BX.CrmInterfaceGridManager.addTask("'.CUtil::JSEscape($gridManagerID).'", { "ownerID":'.$arDeal['ID'].' })'
				);
			endif;

			if ($arDeal['EDIT'] && IsModuleInstalled(CRM_MODULE_CALENDAR_ID)):
				$arActions[] = $arActivityMenuItems[] = array(
					'ICONCLASS' => 'calendar',
					'TITLE' => GetMessage('CRM_DEAL_ADD_CALL_TITLE'),
					'TEXT' => GetMessage('CRM_DEAL_ADD_CALL'),
					'ONCLICK' => 'BX.CrmInterfaceGridManager.addCall("'.CUtil::JSEscape($gridManagerID).'", { "ownerID":'.$arDeal['ID'].', "ownerTitle":"'.$jsTitle.'", "ownerUrl":"'.$jsShowUrl.'" })'
				);

				$arActions[] = $arActivityMenuItems[] = array(
					'ICONCLASS' => 'calendar',
					'TITLE' => GetMessage('CRM_DEAL_ADD_MEETING_TITLE'),
					'TEXT' => GetMessage('CRM_DEAL_ADD_MEETING'),
					'ONCLICK' => 'BX.CrmInterfaceGridManager.addMeeting("'.CUtil::JSEscape($gridManagerID).'", { "ownerID":'.$arDeal['ID'].', "ownerTitle":"'.$jsTitle.'", "ownerUrl":"'.$jsShowUrl.'" })'
				);

			endif;

			if ($arDeal['EDIT'] && IsModuleInstalled('sale')):
				$arActions[] = array(
					'ICONCLASS' => 'quote',
					'TITLE' => GetMessage('CRM_DEAL_ADD_QUOTE_TITLE'),
					'TEXT' => GetMessage('CRM_DEAL_ADD_QUOTE'),
					'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arDeal['PATH_TO_QUOTE_ADD'])."');"
				);
				$arActions[] = array(
					'ICONCLASS' => 'invoice',
					'TITLE' => GetMessage('CRM_DEAL_ADD_INVOICE_TITLE'),
					'TEXT' => GetMessage('CRM_DEAL_ADD_INVOICE'),
					'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arDeal['PATH_TO_INVOICE_ADD'])."');"
				);
			endif;
		endif;
		if ($arDeal['EDIT'] && IsModuleInstalled('bizproc')) :
			$arActions[] = array('SEPARATOR' => true);
			if(isset($arContact['PATH_TO_BIZPROC_LIST']) && $arContact['PATH_TO_BIZPROC_LIST'] !== '')
				$arActions[] =  array(
					'ICONCLASS' => 'bizproc',
					'TITLE' => GetMessage('CRM_DEAL_BIZPROC_TITLE'),
					'TEXT' => GetMessage('CRM_DEAL_BIZPROC'),
					'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arDeal['PATH_TO_BIZPROC_LIST'])."');"
				);
			if (!empty($arDeal['BIZPROC_LIST'])):
				$arBizprocList = array();
				foreach ($arDeal['BIZPROC_LIST'] as $arBizproc) :
					$arBizprocList[] = array(
						'ICONCLASS' => 'bizproc',
						'TITLE' => $arBizproc['DESCRIPTION'],
						'TEXT' => $arBizproc['NAME'],
						'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arBizproc['PATH_TO_BIZPROC_START'])."');"
					);
				endforeach;
				$arActions[] =  array(
					'ICONCLASS' => 'bizproc',
					'TITLE' => GetMessage('CRM_DEAL_BIZPROC_LIST_TITLE'),
					'TEXT' => GetMessage('CRM_DEAL_BIZPROC_LIST'),
					'MENU' => $arBizprocList
				);
			endif;
		endif;
		if ($arDeal['DELETE'] && !$arResult['INTERNAL']):
			$arActions[] = array('SEPARATOR' => true);
			$arActions[] =  array(
				'ICONCLASS' => 'delete',
				'TITLE' => GetMessage('CRM_DEAL_DELETE_TITLE'),
				'TEXT' => GetMessage('CRM_DEAL_DELETE'),
				'ONCLICK' => "crm_deal_delete_grid('".CUtil::JSEscape(GetMessage('CRM_DEAL_DELETE_TITLE'))."', '".CUtil::JSEscape(GetMessage('CRM_DEAL_DELETE_CONFIRM'))."', '".CUtil::JSEscape(GetMessage('CRM_DEAL_DELETE'))."', '".CUtil::JSEscape($arDeal['PATH_TO_DEAL_DELETE'])."')"
			);
		endif;

		$contactID = isset($arDeal['~CONTACT_ID']) ? intval($arDeal['~CONTACT_ID']) : 0;
		$companyID = isset($arDeal['~COMPANY_ID']) ? intval($arDeal['~COMPANY_ID']) : 0;

		$resultItem = array(
			'id' => $arDeal['ID'],
			'actions' => $arActions,
			'data' => $arDeal,
			'editable' => !$arDeal['EDIT'] ? ($arResult['INTERNAL'] ? 'N' : $arColumns) : 'Y',
			'columns' => array(
				'DEAL_SUMMARY' => CCrmViewHelper::RenderInfo($arDeal['PATH_TO_DEAL_SHOW'], isset($arDeal['TITLE']) ? $arDeal['TITLE'] : ('['.$arDeal['ID'].']'), $arDeal['DEAL_TYPE_NAME'], '_self'),
				'DEAL_CLIENT' => $contactID > 0
					? CCrmViewHelper::PrepareClientInfo(
						array(
							'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
							'ENTITY_ID' => $contactID,
							'TITLE' => isset($arDeal['~CONTACT_FORMATTED_NAME']) ? $arDeal['~CONTACT_FORMATTED_NAME'] : ('['.$contactID.']'),
							'PREFIX' => "DEAL_{$arDeal['~ID']}",
							'DESCRIPTION' => isset($arDeal['~COMPANY_TITLE']) ? $arDeal['~COMPANY_TITLE'] : ''
						)
					) : ($companyID > 0
						? CCrmViewHelper::PrepareClientInfo(
							array(
								'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
								'ENTITY_ID' => $companyID,
								'TITLE' => isset($arDeal['~COMPANY_TITLE']) ? $arDeal['~COMPANY_TITLE'] : ('['.$companyID.']'),
								'PREFIX' => "DEAL_{$arDeal['~ID']}"
							),
							$arDeal['PATH_TO_COMPANY_SHOW'], isset($arDeal['COMPANY_TITLE']) ? $arDeal['COMPANY_TITLE'] : '', ''
						) : ''),
				'COMPANY_ID' => $companyID > 0
					? CCrmViewHelper::PrepareClientInfo(
						array(
							'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
							'ENTITY_ID' => $companyID,
							'TITLE' => isset($arDeal['~COMPANY_TITLE']) ? $arDeal['~COMPANY_TITLE'] : ('['.$companyID.']'),
							'PREFIX' => "DEAL_{$arDeal['~ID']}"
						),
						$arDeal['PATH_TO_COMPANY_SHOW'], isset($arDeal['COMPANY_TITLE']) ? $arDeal['COMPANY_TITLE'] : '', ''
					) : '',
				'CONTACT_ID' => $contactID > 0
					? CCrmViewHelper::PrepareClientInfo(
						array(
							'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
							'ENTITY_ID' => $contactID,
							'TITLE' => isset($arDeal['~CONTACT_FORMATTED_NAME']) ? $arDeal['~CONTACT_FORMATTED_NAME'] : ('['.$contactID.']'),
							'PREFIX' => "DEAL_{$arDeal['~ID']}"
						)
					) : '',
				'TITLE' => '<a target="_self" href="'.$arDeal['PATH_TO_DEAL_SHOW'].'"
					class="'.($arDeal['BIZPROC_STATUS'] != '' ? 'bizproc bizproc_status_'.$arDeal['BIZPROC_STATUS'] : '').'"
					'.($arDeal['BIZPROC_STATUS_HINT'] != '' ? 'onmouseover="BX.hint(this, \''.CUtil::JSEscape($arDeal['BIZPROC_STATUS_HINT']).'\');"' : '').'>'.$arDeal['TITLE'].'</a>',
				'CLOSED' => $arDeal['CLOSED'] == 'Y' ? GetMessage('MAIN_YES') : GetMessage('MAIN_NO'),
				'ASSIGNED_BY' => $arDeal['~ASSIGNED_BY'] > 0 ?
					'<a href="'.$arDeal['PATH_TO_USER_PROFILE'].'" id="balloon_'.$arResult['GRID_ID'].'_'.$arDeal['ID'].'">'.$arDeal['ASSIGNED_BY'].'</a>'.
					'<script type="text/javascript">BX.tooltip('.$arDeal['~ASSIGNED_BY'].', "balloon_'.$arResult['GRID_ID'].'_'.$arDeal['ID'].'", "");</script>'
					: '',
				'COMMENTS' => htmlspecialcharsback($arDeal['COMMENTS']),
				'SUM' => '<nobr>'.$arDeal['FORMATTED_OPPORTUNITY'].'</nobr>',
				'OPPORTUNITY' => '<nobr>'.$arDeal['OPPORTUNITY'].'</nobr>',
				'PROBABILITY' => intval($arDeal['PROBABILITY']).'%',
				'DATE_CREATE' => '<nobr>'.FormatDate('SHORT', MakeTimeStamp($arDeal['DATE_CREATE'])).'</nobr>',
				'DATE_MODIFY' => '<nobr>'.FormatDate('SHORT', MakeTimeStamp($arDeal['DATE_MODIFY'])).'</nobr>',
				'TYPE_ID' => isset($arResult['TYPE_LIST'][$arDeal['TYPE_ID']]) ? $arResult['TYPE_LIST'][$arDeal['TYPE_ID']] : $arDeal['TYPE_ID'],
				'EVENT_ID' => isset($arResult['EVENT_LIST'][$arDeal['EVENT_ID']]) ? $arResult['EVENT_LIST'][$arDeal['EVENT_ID']] : $arDeal['EVENT_ID'],
				'CURRENCY_ID' => CCrmCurrency::GetCurrencyName($arDeal['CURRENCY_ID']),
				'PRODUCT_ID' => isset($arDeal['PRODUCT_ROWS']) ? htmlspecialcharsbx(CCrmProductRow::RowsToString($arDeal['PRODUCT_ROWS'])) : '',
				'STATE_ID' => isset($arResult['STATE_LIST'][$arDeal['STATE_ID']]) ? $arResult['STATE_LIST'][$arDeal['STATE_ID']] : $arDeal['STATE_ID'],
				//'STAGE_ID' => $arDeal['DEAL_STAGE_NAME'],
				'STAGE_ID' => CCrmViewHelper::RenderDealStageControl(
					array(
						'PREFIX' => "{$arResult['GRID_ID']}_PROGRESS_BAR_",
						'ENTITY_ID' => $arDeal['~ID'],
						'CURRENT_ID' => $arDeal['~STAGE_ID'],
						'SERVICE_URL' => '/bitrix/components/bitrix/crm.deal.list/list.ajax.php',
						'READ_ONLY' => !(isset($arDeal['EDIT']) && $arDeal['EDIT'] === true)
					)
				),
				'ORIGINATOR_ID' => isset($arDeal['ORIGINATOR_NAME']) ? $arDeal['ORIGINATOR_NAME'] : '',
				'CREATED_BY' => $arDeal['~CREATED_BY'] > 0 ?
					'<a href="'.$arDeal['PATH_TO_USER_CREATOR'].'" id="balloon_'.$arResult['GRID_ID'].'_'.$arDeal['ID'].'">'.$arDeal['CREATED_BY_FORMATTED_NAME'].'</a>'.
						'<script type="text/javascript">BX.tooltip('.$arDeal['~CREATED_BY'].', "balloon_'.$arResult['GRID_ID'].'_'.$arDeal['ID'].'", "");</script>'
					: '',
				'MODIFY_BY' => $arDeal['~MODIFY_BY'] > 0 ?
					'<a href="'.$arDeal['PATH_TO_USER_MODIFIER'].'" id="balloon_'.$arResult['GRID_ID'].'_'.$arDeal['ID'].'">'.$arDeal['MODIFY_BY_FORMATTED_NAME'].'</a>'.
						'<script type="text/javascript">BX.tooltip('.$arDeal['~MODIFY_BY'].', "balloon_'.$arResult['GRID_ID'].'_'.$arDeal['ID'].'", "");</script>'
					: ''
			) + $arResult['DEAL_UF'][$sKey]
		);

		$userActivityID = isset($arDeal['~ACTIVITY_ID']) ? intval($arDeal['~ACTIVITY_ID']) : 0;
		$commonActivityID = isset($arDeal['~C_ACTIVITY_ID']) ? intval($arDeal['~C_ACTIVITY_ID']) : 0;
		if($userActivityID > 0)
		{
			$resultItem['columns']['ACTIVITY_ID'] = CCrmViewHelper::RenderNearestActivity(
				array(
					'ENTITY_TYPE_NAME' => CCrmOwnerType::ResolveName(CCrmOwnerType::Deal),
					'ENTITY_ID' => $arDeal['~ID'],
					'ENTITY_RESPONSIBLE_ID' => $arDeal['~ASSIGNED_BY'],
					'GRID_MANAGER_ID' => $gridManagerID,
					'ACTIVITY_ID' => $userActivityID,
					'ACTIVITY_SUBJECT' => isset($arDeal['~ACTIVITY_SUBJECT']) ? $arDeal['~ACTIVITY_SUBJECT'] : '',
					'ACTIVITY_TIME' => isset($arDeal['~ACTIVITY_TIME']) ? $arDeal['~ACTIVITY_TIME'] : '',
					'ACTIVITY_EXPIRED' => isset($arDeal['~ACTIVITY_EXPIRED']) ? $arDeal['~ACTIVITY_EXPIRED'] : '',
					'ALLOW_EDIT' => $arDeal['EDIT'],
					'MENU_ITEMS' => $arActivityMenuItems,
				)
			);

			$counterData = array(
				'CURRENT_USER_ID' => $currentUserID,
				'ENTITY' => $arDeal,
				'ACTIVITY' => array(
					'RESPONSIBLE_ID' => $currentUserID,
					'TIME' => isset($arDeal['~ACTIVITY_TIME']) ? $arDeal['~ACTIVITY_TIME'] : '',
					'IS_CURRENT_DAY' => isset($arDeal['~ACTIVITY_IS_CURRENT_DAY']) ? $arDeal['~ACTIVITY_IS_CURRENT_DAY'] : false
				)
			);

			if(CCrmUserCounter::IsReckoned(CCrmUserCounter::CurrentDealActivies, $counterData))
			{
				$resultItem['columnClasses'] = array('ACTIVITY_ID' => 'crm-list-deal-today');
			}
		}
		elseif($commonActivityID > 0)
		{
			$resultItem['columns']['ACTIVITY_ID'] = CCrmViewHelper::RenderNearestActivity(
				array(
					'ENTITY_TYPE_NAME' => CCrmOwnerType::ResolveName(CCrmOwnerType::Deal),
					'ENTITY_ID' => $arDeal['~ID'],
					'ENTITY_RESPONSIBLE_ID' => $arDeal['~ASSIGNED_BY'],
					'GRID_MANAGER_ID' => $gridManagerID,
					'ACTIVITY_ID' => $commonActivityID,
					'ACTIVITY_SUBJECT' => isset($arDeal['~C_ACTIVITY_SUBJECT']) ? $arDeal['~C_ACTIVITY_SUBJECT'] : '',
					'ACTIVITY_TIME' => isset($arDeal['~C_ACTIVITY_TIME']) ? $arDeal['~C_ACTIVITY_TIME'] : '',
					'ACTIVITY_RESPONSIBLE_ID' => isset($arDeal['~C_ACTIVITY_RESP_ID']) ? intval($arDeal['~C_ACTIVITY_RESP_ID']) : 0,
					'ACTIVITY_RESPONSIBLE_LOGIN' => isset($arDeal['~C_ACTIVITY_RESP_LOGIN']) ? $arDeal['~C_ACTIVITY_RESP_LOGIN'] : '',
					'ACTIVITY_RESPONSIBLE_NAME' => isset($arDeal['~C_ACTIVITY_RESP_NAME']) ? $arDeal['~C_ACTIVITY_RESP_NAME'] : '',
					'ACTIVITY_RESPONSIBLE_LAST_NAME' => isset($arDeal['~C_ACTIVITY_RESP_LAST_NAME']) ? $arDeal['~C_ACTIVITY_RESP_LAST_NAME'] : '',
					'ACTIVITY_RESPONSIBLE_SECOND_NAME' => isset($arDeal['~C_ACTIVITY_RESP_SECOND_NAME']) ? $arDeal['~C_ACTIVITY_RESP_SECOND_NAME'] : '',
					'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
					'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],
					'ALLOW_EDIT' => $arDeal['EDIT'],
					'MENU_ITEMS' => $arActivityMenuItems
				)
			);
		}
		else
		{
			$resultItem['columns']['ACTIVITY_ID'] = CCrmViewHelper::RenderNearestActivity(
				array(
					'ENTITY_TYPE_NAME' => CCrmOwnerType::ResolveName(CCrmOwnerType::Deal),
					'ENTITY_ID' => $arDeal['~ID'],
					'ENTITY_RESPONSIBLE_ID' => $arDeal['~ASSIGNED_BY'],
					'GRID_MANAGER_ID' => $gridManagerID,
					'ALLOW_EDIT' => $arDeal['EDIT'],
					'MENU_ITEMS' => $arActivityMenuItems
				)
			);

			$counterData = array(
				'CURRENT_USER_ID' => $currentUserID,
				'ENTITY' => $arDeal
			);

			if(CCrmUserCounter::IsReckoned(CCrmUserCounter::CurrentDealActivies, $counterData))
			{
				$resultItem['columnClasses'] = array('ACTIVITY_ID' => 'crm-list-enitity-action-need');
			}
		}

		$arResult['GRID_DATA'][] = &$resultItem;
		unset($resultItem);
	}
	$APPLICATION->IncludeComponent('bitrix:main.user.link',
		'',
		array(
			'AJAX_ONLY' => 'Y',
		),
		false,
		array('HIDE_ICONS' => 'Y')
	);

$isEditable = $arResult['PERMS']['WRITE'] && !$arResult['INTERNAL'];
$actionHtml = '';
if($isEditable)
{
	// Setup STAGE_ID -->
	$stages = '<div id="ACTION_STAGE_WRAPPER" style="display:none;"><select name="ACTION_STAGE_ID" size="1">';
	$stages .= '<option value="" title="'.GetMessage('CRM_STAGE_INIT').'" selected="selected">'.GetMessage('CRM_STAGE_INIT').'</option>';
	foreach($arResult['STAGE_LIST_WRITE'] as $id => $name):
		$name = htmlspecialcharsbx($name);
		$stages .= '<option value="'.$id.'" title="'.$name.'">'.$name.'</option>';
	endforeach;
	$stages .= '</select></div>';

	$actionHtml .= $stages;
	// Setup STAGE_ID -->

	// Setup ASSIGNED_BY_ID -->
	ob_start();
	CCrmViewHelper::RenderUserSearch(
		"{$prefix}_ACTION_ASSIGNED_BY",
		"ACTION_ASSIGNED_BY_SEARCH",
		"ACTION_ASSIGNED_BY_ID",
		"{$prefix}_ACTION_ASSIGNED_BY",
		SITE_ID,
		$arParams['~NAME_TEMPLATE'],
		500
	);
	$actionHtml .= '<div id="ACTION_ASSIGNED_BY_WRAPPER" style="display:none;">'.ob_get_clean().'</div>';
	// <-- Setup ASSIGNED_BY_ID

	// Setup OPENED -->
	$opened = '<div id="ACTION_OPENED_WRAPPER" style="display:none;"><select name="ACTION_OPENED" size="1">';
	$opened .= '<option value="Y">'.GetMessage("CRM_DEAL_MARK_AS_OPENED_YES").'</option>';
	$opened .= '<option value="N">'.GetMessage("CRM_DEAL_MARK_AS_OPENED_NO").'</option>';
	$opened .= '</select></div>';
	$actionHtml .= $opened;
	// Setup OPENED -->

	$actionHtml .= '
		<script type="text/javascript">
			BX.ready(
				function(){
				var select = BX.findChild(BX.findPreviousSibling(BX.findParent(BX("ACTION_ASSIGNED_BY_WRAPPER"), { "tagName":"td" })), { "tagName":"select" });
				BX.bind(
					select,
					"change",
					function(e){
						BX("ACTION_STAGE_WRAPPER").style.display = select.value === "set_stage" ? "" : "none";
						BX("ACTION_ASSIGNED_BY_WRAPPER").style.display = select.value === "assign_to" ? "" : "none";
						BX("ACTION_OPENED_WRAPPER").style.display = select.value === "mark_as_opened" ? "" : "none";
					}
				)
			}
		);
		</script>';
}

$arActionList = array();
if($isEditable)
{
	if (IsModuleInstalled('tasks'))
	{
		$arActionList['tasks'] = GetMessage('CRM_DEAL_TASK');
	}
	//if (IsModuleInstalled(CRM_MODULE_CALENDAR_ID))
	//	$arActionList['calendar'] = GetMessage('CRM_DEAL_CALENDAR');
	if($arResult['PERMS']['WRITE'])
	{
		$arActionList['set_stage'] = GetMessage('CRM_DEAL_SET_STAGE');
		$arActionList['assign_to'] = GetMessage('CRM_DEAL_ASSIGN_TO');
		$arActionList['mark_as_opened'] = GetMessage('CRM_DEAL_MARK_AS_OPENED');
	}
}

if($arResult['ENABLE_TOOLBAR'])
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.interface.toolbar',
		'',
		array(
			'TOOLBAR_ID' => strtolower($arResult['GRID_ID']).'_toolbar',
			'BUTTONS' => array(
				array(
					'TEXT' => GetMessage('CRM_DEAL_LIST_ADD_SHORT'),
					'TITLE' => GetMessage('CRM_DEAL_LIST_ADD'),
					'LINK' => $arResult['PATH_TO_DEAL_ADD'],
					'ICON' => 'btn-new'
				)
			)
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
		'EDITABLE' =>  $isEditable ? 'Y' : 'N',
		'ACTIONS' => array(
			'delete' => $arResult['PERMS']['DELETE'],
			'custom_html' => $actionHtml,
			'list' => $arActionList
		),
		'ACTION_ALL_ROWS' => true,
		'NAV_OBJECT' => $arResult['DB_LIST'],
		'FORM_ID' => $arResult['FORM_ID'],
		'TAB_ID' => $arResult['TAB_ID'],
		'AJAX_MODE' => $arResult['INTERNAL'] ? 'N' : 'Y',
		'AJAX_MODE' => $arResult['AJAX_MODE'],
		'AJAX_ID' => $arResult['AJAX_ID'],
		'AJAX_OPTION_JUMP' => $arResult['AJAX_OPTION_JUMP'],
		'AJAX_OPTION_HISTORY' => $arResult['AJAX_OPTION_HISTORY'],
		'AJAX_LOADER' => isset($arParams['AJAX_LOADER']) ? $arParams['AJAX_LOADER'] : null,
		'FILTER' => $arResult['FILTER'],
		'FILTER_PRESETS' => $arResult['FILTER_PRESETS'],
		'FILTER_TEMPLATE' => 'flat',
		'FILTER_NAVIGATION_BAR' => array(
			'ITEMS' => array(
				array('icon' => 'table', 'id' => 'list', 'active' => true, 'url' => $arParams['PATH_TO_DEAL_LIST']),
				array('icon' => 'chart', 'id' => 'widget', 'active' => false, 'url' => $arParams['PATH_TO_DEAL_WIDGET'])
			),
			'BINDING' => array(
				'category' => 'crm.navigation',
				'name' => 'index',
				'key' => strtolower($arResult['NAVIGATION_CONTEXT_ID'])
			)
		),
		'IS_EXTERNAL_FILTER' => $arResult['IS_EXTERNAL_FILTER'],
		'MANAGER' => array(
			'ID' => $gridManagerID,
			'CONFIG' => $gridManagerCfg
		)
	),
	$component
);
?>
<?if(!$isInternal):?>
<script type="text/javascript">
	BX.ready(
			function()
			{
				BX.CrmActivityEditor.items['<?= CUtil::JSEscape($activityEditorID)?>'].addActivityChangeHandler(
						function()
						{
							BX.CrmInterfaceGridManager.reloadGrid('<?= CUtil::JSEscape($arResult['GRID_ID'])?>');
						}
				);
			}
	);
</script>
<?endif;?>
<?if($arResult['NEED_FOR_REBUILD_DEAL_ATTRS']):?>
<script type="text/javascript">
	BX.ready(
		function()
		{
			var link = BX("rebuildDealAttrsLink");
			if(link)
			{
				BX.bind(
					link,
					"click",
					function(e)
					{
						var msg = BX("rebuildDealAttrsMsg");
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
