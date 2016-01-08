<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
global $APPLICATION;
$APPLICATION->SetAdditionalCSS('/bitrix/themes/.default/crm-entity-show.css');
if(SITE_TEMPLATE_ID === 'bitrix24')
{
	$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/bitrix24/crm-entity-show.css");
}
CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/activity.js');
CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/interface_grid.js');

if($arResult['NEED_FOR_REBUILD_DUP_INDEX']):
	?><div id="rebuildContactDupIndexMsg" class="crm-view-message">
		<?=GetMessage('CRM_CONTACT_REBUILD_DUP_INDEX', array('#ID#' => 'rebuildContactDupIndexLink', '#URL#' => '#'))?>
	</div><?
endif;

if($arResult['NEED_FOR_REBUILD_CONTACT_ATTRS']):
	?><div id="rebuildContactAttrsMsg" class="crm-view-message">
		<?=GetMessage('CRM_CONTACT_REBUILD_ACCESS_ATTRS', array('#ID#' => 'rebuildContactAttrsLink', '#URL#' => $arResult['PATH_TO_PRM_LIST']))?>
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
			'OWNER_TYPE' => 'CONTACT',
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
	'ownerType' => 'CONTACT',
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
function crm_contact_delete_grid(title, message, btnTitle, path)
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
BX.ready(function() {
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
});
</script>
<?
	for ($i = 0, $ic = sizeof($arResult['FILTER']); $i < $ic; $i++)
	{
		$filterField = $arResult['FILTER'][$i];
		$filterID = $filterField['id'];
		$filterType = $filterField['type'];
		$enable_settings = $filterField['enable_settings'];

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

		$arResult["FILTER"][$i]["type"] = "custom";
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

	foreach($arResult['CONTACT'] as $sKey =>  $arContact)
	{
		$arActivityMenuItems = array();
		$arActions = array();
		$arActions[] =  array(
			'ICONCLASS' => 'view',
			'TITLE' => GetMessage('CRM_CONTACT_SHOW_TITLE'),
			'TEXT' => GetMessage('CRM_CONTACT_SHOW'),
			'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arContact['PATH_TO_CONTACT_SHOW'])."');",
			'DEFAULT' => true
		);
		if ($arContact['EDIT']):
			$arActions[] =  array(
				'ICONCLASS' => 'edit',
				'TITLE' => GetMessage('CRM_CONTACT_EDIT_TITLE'),
				'TEXT' => GetMessage('CRM_CONTACT_EDIT'),
				'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arContact['PATH_TO_CONTACT_EDIT'])."');"
			);
			$arActions[] =  array(
				'ICONCLASS' => 'copy',
				'TITLE' => GetMessage('CRM_CONTACT_COPY_TITLE'),
				'TEXT' => GetMessage('CRM_CONTACT_COPY'),
				'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arContact['PATH_TO_CONTACT_COPY'])."');"
			);
		endif;

		if(!$isInternal):
			$arActions[] = array('SEPARATOR' => true);

			$arActions[] = $arActivityMenuItems[] = array(
				'ICONCLASS' => 'event',
				'TITLE' => GetMessage('CRM_CONTACT_EVENT_TITLE'),
				'TEXT' => GetMessage('CRM_CONTACT_EVENT'),
				'ONCLICK' => "javascript:(new BX.CDialog({'content_url':'/bitrix/components/bitrix/crm.event.add/box.php?FORM_TYPE=LIST&ENTITY_TYPE=CONTACT&ENTITY_ID=".$arContact['ID']."', 'width':'498', 'height':'245', 'resizable':false })).Show();"
			);

			if ($arContact['EDIT'] && IsModuleInstalled('tasks')):
				$arActions[] = $arActivityMenuItems[] = array(
					'ICONCLASS' => 'task',
					'TITLE' => GetMessage('CRM_CONTACT_TASK_TITLE'),
					'TEXT' => GetMessage('CRM_CONTACT_TASK'),
					'ONCLICK' => 'BX.CrmInterfaceGridManager.addTask("'.CUtil::JSEscape($gridManagerID).'", { "ownerID":'.$arContact['ID'].' })'
				);
			endif;
			if ($arContact['EDIT'] && IsModuleInstalled('subscribe')):
				$arActions[] = $arActivityMenuItems[] = array(
					'ICONCLASS' => 'subscribe',
					'TITLE' => GetMessage('CRM_CONTACT_ADD_EMAIL_TITLE'),
					'TEXT' => GetMessage('CRM_CONTACT_ADD_EMAIL'),
					'ONCLICK' => 'BX.CrmInterfaceGridManager.addEmail("'.CUtil::JSEscape($gridManagerID).'", { "ownerID":'.$arContact['ID'].' })'
				);
			endif;
			if ($arContact['EDIT'] && IsModuleInstalled(CRM_MODULE_CALENDAR_ID)):
				$arActions[] = $arActivityMenuItems[] = array(
					'ICONCLASS' => 'calendar',
					'TITLE' => GetMessage('CRM_CONTACT_ADD_CALL_TITLE'),
					'TEXT' => GetMessage('CRM_CONTACT_ADD_CALL'),
					'ONCLICK' => 'BX.CrmInterfaceGridManager.addCall("'.CUtil::JSEscape($gridManagerID).'", { "ownerID":'.$arContact['ID'].' })'
				);

				$arActions[] = $arActivityMenuItems[] = array(
					'ICONCLASS' => 'calendar',
					'TITLE' => GetMessage('CRM_CONTACT_ADD_MEETING_TITLE'),
					'TEXT' => GetMessage('CRM_CONTACT_ADD_MEETING'),
					'ONCLICK' => 'BX.CrmInterfaceGridManager.addMeeting("'.CUtil::JSEscape($gridManagerID).'", { "ownerID":'.$arContact['ID'].' })'
				);
			endif;
		endif;

		if ($arContact['EDIT'] && IsModuleInstalled('bizproc')) :
			$arActions[] = array('SEPARATOR' => true);
			if(isset($arContact['PATH_TO_BIZPROC_LIST']) && $arContact['PATH_TO_BIZPROC_LIST'] !== '')
				$arActions[] =  array(
					'ICONCLASS' => 'bizproc',
					'TITLE' => GetMessage('CRM_CONTACT_BIZPROC_TITLE'),
					'TEXT' => GetMessage('CRM_CONTACT_BIZPROC'),
					'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arContact['PATH_TO_BIZPROC_LIST'])."');"
				);
			if (!empty($arContact['BIZPROC_LIST'])):
				$arBizprocList = array();
				foreach ($arContact['BIZPROC_LIST'] as $arBizproc) :
					$arBizprocList[] = array(
						'ICONCLASS' => 'bizproc',
						'TITLE' => $arBizproc['DESCRIPTION'],
						'TEXT' => $arBizproc['NAME'],
						'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arBizproc['PATH_TO_BIZPROC_START'])."');"
					);
				endforeach;
				$arActions[] =  array(
					'ICONCLASS' => 'bizproc',
					'TITLE' => GetMessage('CRM_CONTACT_BIZPROC_LIST_TITLE'),
					'TEXT' => GetMessage('CRM_CONTACT_BIZPROC_LIST'),
					'MENU' => $arBizprocList
				);
			endif;
		endif;
		if ($arResult['PERM_DEAL'])
		{
			$arActions[] = array('SEPARATOR' => true);
			$arActions[] =  array(
				'ICONCLASS' => 'deal',
				'TITLE' => GetMessage('CRM_CONTACT_DEAL_ADD_TITLE'),
				'TEXT' => GetMessage('CRM_CONTACT_DEAL_ADD'),
				'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arContact['PATH_TO_DEAL_EDIT'])."');"
			);
		}
		if ($arResult['PERM_QUOTE'] && IsModuleInstalled('sale')):
			$arActions[] = array(
				'ICONCLASS' => 'quote',
				'TITLE' => GetMessage('CRM_CONTACT_ADD_QUOTE_TITLE'),
				'TEXT' => GetMessage('CRM_CONTACT_ADD_QUOTE'),
				'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arContact['PATH_TO_QUOTE_ADD'])."');"
			);
		endif;
		if ($arResult['PERM_INVOICE'] && IsModuleInstalled('sale')):
			$arActions[] = array(
				'ICONCLASS' => 'invoice',
				'TITLE' => GetMessage('CRM_DEAL_ADD_INVOICE_TITLE'),
				'TEXT' => GetMessage('CRM_DEAL_ADD_INVOICE'),
				'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arContact['PATH_TO_INVOICE_ADD'])."');"
			);
		endif;
		if ($arContact['DELETE'] && !$arResult['INTERNAL']):
			$arActions[] = array('SEPARATOR' => true);
			$arActions[] =  array(
				'ICONCLASS' => 'delete',
				'TITLE' => GetMessage('CRM_CONTACT_DELETE_TITLE'),
				'TEXT' => GetMessage('CRM_CONTACT_DELETE'),
				'ONCLICK' => "crm_contact_delete_grid('".CUtil::JSEscape(GetMessage('CRM_CONTACT_DELETE_TITLE'))."', '".CUtil::JSEscape(GetMessage('CRM_CONTACT_DELETE_CONFIRM'))."', '".CUtil::JSEscape(GetMessage('CRM_CONTACT_DELETE'))."', '".CUtil::JSEscape($arContact['PATH_TO_CONTACT_DELETE'])."')"
			);
		endif;

		$_sBPHint = 'class="'.($arContact['BIZPROC_STATUS'] != '' ? 'bizproc bizproc_status_'.$arContact['BIZPROC_STATUS'] : '').'"
					'.($arContact['BIZPROC_STATUS_HINT'] != '' ? 'onmouseover="BX.hint(this, \''.CUtil::JSEscape($arContact['BIZPROC_STATUS_HINT']).'\');"' : '');

		$companyID = isset($arContact['~COMPANY_ID']) ? intval($arContact['~COMPANY_ID']) : 0;

		$resultItem = array(
			'id' => $arContact['ID'],
			'actions' => $arActions,
			'data' => $arContact,
			'editable' => !$arContact['EDIT'] ? ($arResult['INTERNAL'] ? 'N' : $arColumns) : 'Y',
			'columns' => array(
				'CONTACT_SUMMARY' => CCrmViewHelper::RenderClientSummary($arContact['PATH_TO_CONTACT_SHOW'], $arContact['CONTACT_FORMATTED_NAME'], $arContact['CONTACT_TYPE_NAME'], isset($arContact['PHOTO']) ? $arContact['PHOTO'] : ''),
				'CONTACT_COMPANY' => $companyID > 0
					? CCrmViewHelper::PrepareClientInfo(
						array(
							'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
							'ENTITY_ID' => $companyID,
							'TITLE' => isset($arContact['~COMPANY_TITLE']) ? $arContact['~COMPANY_TITLE'] : ('['.$companyID.']'),
							'PREFIX' => "CONTACT_{$arContact['~ID']}",
							'DESCRIPTION' => isset($arContact['~POST']) ? $arContact['~POST'] : ''
						)
					) : '',
				'ASSIGNED_BY' => $arContact['~ASSIGNED_BY'] > 0 ?
					'<a href="'.$arContact['PATH_TO_USER_PROFILE'].'" id="balloon_'.$arResult['GRID_ID'].'_'.$arContact['ID'].'">'.$arContact['ASSIGNED_BY'].'</a>'.
					'<script type="text/javascript">BX.tooltip('.$arContact['~ASSIGNED_BY'].', "balloon_'.$arResult['GRID_ID'].'_'.$arContact['ID'].'", "");</script>'
					: '',
				'ADDRESS' => nl2br($arContact['ADDRESS']),
				'COMMENTS' => htmlspecialcharsback($arContact['COMMENTS']),
				'COMPANY_ID' => isset($arContact['COMPANY_TITLE'])?
					'<a href="'.$arContact['PATH_TO_COMPANY_SHOW'].'" id="balloon_'.$arResult['GRID_ID'].'_CO_'.$arContact['ID'].'">'.$arContact['COMPANY_TITLE'].'</a>'.
					'<script type="text/javascript">BX.tooltip("COMPANY_'.$arContact['~COMPANY_ID'].'", "balloon_'.$arResult['GRID_ID'].'_CO_'.$arContact['ID'].'", "/bitrix/components/bitrix/crm.company.show/card.ajax.php", "crm_balloon_company", true);</script>'
					: '',
				'SOURCE_DESCRIPTION' => nl2br($arContact['SOURCE_DESCRIPTION']),
				'DATE_CREATE' => '<nobr>'.FormatDate('SHORT', MakeTimeStamp($arContact['DATE_CREATE'])).'</nobr>',
				'DATE_MODIFY' => '<nobr>'.FormatDate('SHORT', MakeTimeStamp($arContact['DATE_MODIFY'])).'</nobr>',
				'TYPE_ID' => isset($arResult['TYPE_LIST'][$arContact['TYPE_ID']]) ? $arResult['TYPE_LIST'][$arContact['TYPE_ID']] : $arContact['TYPE_ID'],
				'SOURCE_ID' => isset($arResult['SOURCE_LIST'][$arContact['SOURCE_ID']]) ? $arResult['SOURCE_LIST'][$arContact['SOURCE_ID']] : $arContact['SOURCE_ID'],
				'CREATED_BY' => $arContact['~CREATED_BY'] > 0 ?
					'<a href="'.$arContact['PATH_TO_USER_CREATOR'].'" id="balloon_'.$arResult['GRID_ID'].'_'.$arContact['ID'].'">'.$arContact['CREATED_BY_FORMATTED_NAME'].'</a>'.
						'<script type="text/javascript">BX.tooltip('.$arContact['~CREATED_BY'].', "balloon_'.$arResult['GRID_ID'].'_'.$arContact['ID'].'", "");</script>'
					: '',
				'MODIFY_BY' => $arContact['~MODIFY_BY'] > 0 ?
					'<a href="'.$arContact['PATH_TO_USER_MODIFIER'].'" id="balloon_'.$arResult['GRID_ID'].'_'.$arContact['ID'].'">'.$arContact['MODIFY_BY_FORMATTED_NAME'].'</a>'.
						'<script type="text/javascript">BX.tooltip('.$arContact['~MODIFY_BY'].', "balloon_'.$arResult['GRID_ID'].'_'.$arContact['ID'].'", "");</script>'
					: ''
			) + CCrmViewHelper::RenderListMultiFields($arContact, "CONTACT_{$arContact['ID']}_", array('ENABLE_SIP' => true, 'SIP_PARAMS' => array('ENTITY_TYPE' => 'CRM_'.CCrmOwnerType::ContactName, 'ENTITY_ID' => $arContact['ID']))) + $arResult['CONTACT_UF'][$sKey]
		);

		if(isset($arContact['~BIRTHDATE']))
		{
			$resultItem['columns']['BIRTHDATE'] = '<nobr>'.FormatDate('SHORT', MakeTimeStamp($arContact['~BIRTHDATE'])).'</nobr>';
		}

		$userActivityID = isset($arContact['~ACTIVITY_ID']) ? intval($arContact['~ACTIVITY_ID']) : 0;
		$commonActivityID = isset($arContact['~C_ACTIVITY_ID']) ? intval($arContact['~C_ACTIVITY_ID']) : 0;
		if($userActivityID > 0)
		{
			$resultItem['columns']['ACTIVITY_ID'] = CCrmViewHelper::RenderNearestActivity(
				array(
					'ENTITY_TYPE_NAME' => CCrmOwnerType::ResolveName(CCrmOwnerType::Contact),
					'ENTITY_ID' => $arContact['~ID'],
					'ENTITY_RESPONSIBLE_ID' => $arContact['~ASSIGNED_BY'],
					'GRID_MANAGER_ID' => $gridManagerID,
					'ACTIVITY_ID' => $userActivityID,
					'ACTIVITY_SUBJECT' => isset($arContact['~ACTIVITY_SUBJECT']) ? $arContact['~ACTIVITY_SUBJECT'] : '',
					'ACTIVITY_TIME' => isset($arContact['~ACTIVITY_TIME']) ? $arContact['~ACTIVITY_TIME'] : '',
					'ACTIVITY_EXPIRED' => isset($arContact['~ACTIVITY_EXPIRED']) ? $arContact['~ACTIVITY_EXPIRED'] : '',
					'ALLOW_EDIT' => $arContact['EDIT'],
					'MENU_ITEMS' => $arActivityMenuItems,
				)
			);

			$counterData = array(
				'CURRENT_USER_ID' => $currentUserID,
				'ENTITY' => $arContact,
				'ACTIVITY' => array(
					'RESPONSIBLE_ID' => $currentUserID,
					'TIME' => isset($arContact['~ACTIVITY_TIME']) ? $arContact['~ACTIVITY_TIME'] : '',
					'IS_CURRENT_DAY' => isset($arContact['~ACTIVITY_IS_CURRENT_DAY']) ? $arContact['~ACTIVITY_IS_CURRENT_DAY'] : false
				)
			);

			if(CCrmUserCounter::IsReckoned(CCrmUserCounter::CurrentContactActivies, $counterData))
			{
				$resultItem['columnClasses'] = array('ACTIVITY_ID' => 'crm-list-deal-today');
			}
		}
		elseif($commonActivityID > 0)
		{
			$resultItem['columns']['ACTIVITY_ID'] = CCrmViewHelper::RenderNearestActivity(
				array(
					'ENTITY_TYPE_NAME' => CCrmOwnerType::ResolveName(CCrmOwnerType::Contact),
					'ENTITY_ID' => $arContact['~ID'],
					'ENTITY_RESPONSIBLE_ID' => $arContact['~ASSIGNED_BY'],
					'GRID_MANAGER_ID' => $gridManagerID,
					'ACTIVITY_ID' => $commonActivityID,
					'ACTIVITY_SUBJECT' => isset($arContact['~C_ACTIVITY_SUBJECT']) ? $arContact['~C_ACTIVITY_SUBJECT'] : '',
					'ACTIVITY_TIME' => isset($arContact['~C_ACTIVITY_TIME']) ? $arContact['~C_ACTIVITY_TIME'] : '',
					'ACTIVITY_RESPONSIBLE_ID' => isset($arContact['~C_ACTIVITY_RESP_ID']) ? intval($arContact['~C_ACTIVITY_RESP_ID']) : 0,
					'ACTIVITY_RESPONSIBLE_LOGIN' => isset($arContact['~C_ACTIVITY_RESP_LOGIN']) ? $arContact['~C_ACTIVITY_RESP_LOGIN'] : '',
					'ACTIVITY_RESPONSIBLE_NAME' => isset($arContact['~C_ACTIVITY_RESP_NAME']) ? $arContact['~C_ACTIVITY_RESP_NAME'] : '',
					'ACTIVITY_RESPONSIBLE_LAST_NAME' => isset($arContact['~C_ACTIVITY_RESP_LAST_NAME']) ? $arContact['~C_ACTIVITY_RESP_LAST_NAME'] : '',
					'ACTIVITY_RESPONSIBLE_SECOND_NAME' => isset($arContact['~C_ACTIVITY_RESP_SECOND_NAME']) ? $arContact['~C_ACTIVITY_RESP_SECOND_NAME'] : '',
					'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
					'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],
					'ALLOW_EDIT' => $arContact['EDIT'],
					'MENU_ITEMS' => $arActivityMenuItems
				)
			);
		}
		else
		{
			$resultItem['columns']['ACTIVITY_ID'] = CCrmViewHelper::RenderNearestActivity(
				array(
					'ENTITY_TYPE_NAME' => CCrmOwnerType::ResolveName(CCrmOwnerType::Contact),
					'ENTITY_ID' => $arContact['~ID'],
					'ENTITY_RESPONSIBLE_ID' => $arContact['~ASSIGNED_BY'],
					'GRID_MANAGER_ID' => $gridManagerID,
					'ALLOW_EDIT' => $arContact['EDIT'],
					'MENU_ITEMS' => $arActivityMenuItems
				)
			);
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
		// Setup ASSIGNED_BY_ID -->
		ob_start();
		$prefix = $arResult['GRID_ID'];
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
		$opened .= '<option value="Y">'.GetMessage("CRM_CONTACT_MARK_AS_OPENED_YES").'</option>';
		$opened .= '<option value="N">'.GetMessage("CRM_CONTACT_MARK_AS_OPENED_NO").'</option>';
		$opened .= '</select></div>';
		$actionHtml .= $opened;
		// Setup OPENED -->

		// Setup EXPORT -->
		$actionHtml .= '<div id="ACTION_EXPORT_WRAPPER" style="display:none;"><select name="ACTION_EXPORT"><option value="Y">'.GetMessage('CRM_CONTACT_EXPORT_ON').'</option><option value="N">'.GetMessage('CRM_CONTACT_EXPORT_OFF').'</option><select></div>';
		// <-- Setup EXPORT

		$actionHtml .= '
		<script type="text/javascript">
			BX.ready(
				function(){
				var select = BX.findChild(BX.findPreviousSibling(BX.findParent(BX("ACTION_ASSIGNED_BY_WRAPPER"), { "tagName":"td" })), { "tagName":"select" });
				BX.bind(
					select,
					"change",
					function(e){
						BX("ACTION_ASSIGNED_BY_WRAPPER").style.display = select.value === "assign_to" ? "" : "none";
						BX("ACTION_EXPORT_WRAPPER").style.display = select.value === "export" ? "" : "none";
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
			$arActionList['tasks'] = GetMessage('CRM_CONTACT_TASK');
		}
		if (IsModuleInstalled('subscribe'))
		{
			$arActionList['subscribe'] = GetMessage('CRM_CONTACT_SUBSCRIBE');
		}
		//if (IsModuleInstalled(CRM_MODULE_CALENDAR_ID))
		//	$arActionList['calendar'] = GetMessage('CRM_CONTACT_CALENDAR');
		if($arResult['PERMS']['WRITE'])
		{
			$arActionList['assign_to'] = GetMessage('CRM_CONTACT_ASSIGN_TO');
			$arActionList['mark_as_opened'] = GetMessage('CRM_CONTACT_MARK_AS_OPENED');
			$arActionList['export'] = GetMessage('CRM_CONTACT_EXPORT');
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
						'TEXT' => GetMessage('CRM_CONTACT_LIST_ADD_SHORT'),
						'TITLE' => GetMessage('CRM_CONTACT_LIST_ADD'),
						'LINK' => $arResult['PATH_TO_CONTACT_ADD'],
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
			'EDITABLE' => $isEditable ? 'Y' : 'N',
			'ACTIONS' => array(
				'delete' => $arResult['PERMS']['DELETE'],
				'list' => $arActionList,
				'custom_html' => $actionHtml,
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
?>
<script type="text/javascript">
	BX.ready(
			function()
			{
				BX.CrmSipManager.getCurrent().setServiceUrl(
					"CRM_<?=CUtil::JSEscape(CCrmOwnerType::ContactName)?>",
					"/bitrix/components/bitrix/crm.contact.show/ajax.php?<?=bitrix_sessid_get()?>"
				);

				if(typeof(BX.CrmSipManager.messages) === 'undefined')
				{
					BX.CrmSipManager.messages =
					{
						"unknownRecipient": "<?= GetMessageJS('CRM_SIP_MGR_UNKNOWN_RECIPIENT')?>",
						"enableCallRecording": "<?= GetMessageJS('CRM_SIP_MGR_ENABLE_CALL_RECORDING')?>",
						"makeCall": "<?= GetMessageJS('CRM_SIP_MGR_MAKE_CALL')?>"
					};
				}
			}
	);
</script>

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
<?if($arResult['NEED_FOR_REBUILD_DUP_INDEX']):?>
<script type="text/javascript">
	BX.ready(
		function()
		{
			BX.CrmDuplicateManager.messages =
			{
				rebuildContactIndexDlgTitle: "<?=GetMessageJS('CRM_CONTACT_REBUILD_DUP_INDEX_DLG_TITLE')?>",
				rebuildContactIndexDlgSummary: "<?=GetMessageJS('CRM_CONTACT_REBUILD_DUP_INDEX_DLG_SUMMARY')?>"
			};
			BX.CrmLongRunningProcessDialog.messages =
			{
				startButton: "<?=GetMessageJS('CRM_CONTACT_LRP_DLG_BTN_START')?>",
				stopButton: "<?=GetMessageJS('CRM_CONTACT_LRP_DLG_BTN_STOP')?>",
				closeButton: "<?=GetMessageJS('CRM_CONTACT_LRP_DLG_BTN_CLOSE')?>",
				wait: "<?=GetMessageJS('CRM_CONTACT_LRP_DLG_WAIT')?>",
				requestError: "<?=GetMessageJS('CRM_CONTACT_LRP_DLG_REQUEST_ERR')?>"
			};

			var mgr = BX.CrmDuplicateManager.create("mgr", { entityTypeName: "<?=CUtil::JSEscape(CCrmOwnerType::ContactName)?>", serviceUrl: "<?=SITE_DIR?>bitrix/components/bitrix/crm.contact.list/list.ajax.php?&<?=bitrix_sessid_get()?>" });
			BX.addCustomEvent(
				mgr,
				'ON_CONTACT_INDEX_REBUILD_COMPLETE',
				function()
				{
					var msg = BX("rebuildContactDupIndexMsg");
					if(msg)
					{
						msg.style.display = "none";
					}
				}
			);

			var link = BX("rebuildContactDupIndexLink");
			if(link)
			{
				BX.bind(
					link,
					"click",
					function(e)
					{
						mgr.rebuildIndex();
						return BX.PreventDefault(e);
					}
				);
			}
		}
	);
</script>
<?endif;?>
<?if($arResult['NEED_FOR_REBUILD_CONTACT_ATTRS']):?>
<script type="text/javascript">
	BX.ready(
		function()
		{
			var link = BX("rebuildContactAttrsLink");
			if(link)
			{
				BX.bind(
					link,
					"click",
					function(e)
					{
						var msg = BX("rebuildContactAttrsMsg");
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
