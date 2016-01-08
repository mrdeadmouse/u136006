<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
global $APPLICATION;
$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');
$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");
if(SITE_TEMPLATE_ID === 'bitrix24')
{
	$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/bitrix24/crm-entity-show.css");
}
CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/interface_grid.js');

if($arResult['ENABLE_CONTROL_PANEL'])
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.control_panel',
		'',
		array(
			'ID' => 'MY_ACTIVITY',
			'ACTIVE_ITEM_ID' => 'MY_ACTIVITY',
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
$gridManagerID = $arResult['UID'].'_MANAGER';
$gridManagerCfg = array(
	'ownerType' => 'ACTIVITY',
	'gridId' => $arResult['UID'],
	'formName' => "form_{$arResult['UID']}",
	'allRowsCheckBoxId' => "actallrows_{$arResult['UID']}",
	'filterFields' => array()
);

for ($i=0, $ic = sizeof($arResult['FILTER']); $i < $ic; $i++)
{
	$filterField = $arResult['FILTER'][$i];
	$filterID = $filterField['id'];
	$filterType = $filterField['type'];
	$enable_settings = $filterField['enable_settings'];

	if ($filterType !== 'user')
	{
		continue;
	}

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
			'ID' => "{$filterID}_SEARCH",
			'SEARCH_INPUT_ID' => "{$filterID}_NAME",
			'SEARCH_INPUT_NAME' => "{$filterID}_name",
			'DATA_INPUT_ID' => $filterID,
			'DATA_INPUT_NAME' => $filterID,
			'COMPONENT_NAME' => "{$filterID}_SEARCH",
			'SITE_ID' => SITE_ID,
			'NAME_FORMAT' => $arParams['NAME_TEMPLATE'],
			'USER' => array('ID' => $userID, 'NAME' => $userName),
			'DELAY' => 100
		)
	);
	$arResult['FILTER'][$i]['value'] = ob_get_clean();
	$arResult['FILTER'][$i]['type'] = 'custom';

	$filterFieldInfo = array(
		'typeName' => 'USER',
		'id' => $filterID,
		'params' => array(
			'data' => array(
				'paramName' => $filterID,
				'elementId' => $filterID
			),
			'search' => array(
				'paramName' => "{$filterID}_name",
				'elementId' => "{$filterID}_NAME"
			)
		)
	);

	if($enable_settings)
	{
		ob_start();
		CCrmViewHelper::RenderUserCustomSearch(
			array(
				'ID' => "FILTER_SETTINGS_{$filterID}_SEARCH",
				'SEARCH_INPUT_ID' => "FILTER_SETTINGS_{$filterID}_NAME",
				'SEARCH_INPUT_NAME' => "{$filterID}_name",
				'DATA_INPUT_ID' => "FILTER_SETTINGS_{$filterID}",
				'DATA_INPUT_NAME' => $filterID,
				'COMPONENT_NAME' => "FILTER_SETTINGS_{$filterID}_SEARCH",
				'SITE_ID' => SITE_ID,
				'NAME_FORMAT' => $arParams['NAME_TEMPLATE'],
				'USER' => array('ID' => $userID, 'NAME' => $userName),
				'ZINDEX' => 4000,
				'DELAY' => 100
			)
		);
		$arResult['FILTER'][$i]['settingsHtml'] = ob_get_clean();

		$filterFieldInfo['params']['data']['settingsElementId'] = "FILTER_SETTINGS_{$filterID}";
		$filterFieldInfo['params']['search']['settingsElementId'] = "FILTER_SETTINGS_{$filterID}_NAME";
	}
	$gridManagerCfg['filterFields'][] = $filterFieldInfo;
}

$arResult['PREFIX'] = isset($arResult['PREFIX']) ? strval($arResult['PREFIX']) : 'activity_list';
$gridEditorID = $arResult['PREFIX'].'_crm_activity_grid_editor';
$editorItems = array();
$isEditable = !$arResult['READ_ONLY'];

$arResult['GRID_ID'] = $arResult['UID'];
$arResult['GRID_DATA'] = array();

$dateTimeOptions = array('TIME_FORMAT' => '<span class="crm-activity-time">#TIME#</span>');
foreach($arResult['ITEMS'] as &$item)
{
	// Preparing of grid row -->
	$openViewJS = "BX.CrmActivityEditor.items['{$gridEditorID}'].openActivityDialog(BX.CrmDialogMode.view, {$item['ID']}, {});";
	$arActions = array(
		array(
			'ICONCLASS' => 'view',
			'TITLE' => GetMessage('CRM_ACTION_SHOW'),
			'TEXT' => GetMessage('CRM_ACTION_SHOW'),
			'ONCLICK' => $openViewJS,
			'DEFAULT' => true
		)
	);

	$itemTypeID = intval($item['TYPE_ID']);

	if($isEditable)
	{
		if($item['CAN_EDIT'] && ($itemTypeID === CCrmActivityType::Call || $itemTypeID === CCrmActivityType::Meeting))
		{
			$arActions[] = array(
				'ICONCLASS' => 'edit',
				'TITLE' => GetMessage('CRM_ACTION_EDIT'),
				'TEXT' => GetMessage('CRM_ACTION_EDIT'),
				'ONCLICK' => "BX.CrmActivityEditor.items['{$gridEditorID}'].openActivityDialog(BX.CrmDialogMode.edit, {$item['ID']}, {});",
			);
		}

		if($item['CAN_COMPLETE'] && $itemTypeID !== CCrmActivityType::Email) //Email is always COMPLETED
		{
			if(isset($item['COMPLETED'])
				&& $item['COMPLETED'] === 'Y')
			{
				$arActions[] = array(
					'ICONCLASS' => 'edit',
					'TITLE' => GetMessage('CRM_ACTION_MARK_AS_NOT_COMPLETED'),
					'TEXT' => GetMessage('CRM_ACTION_MARK_AS_NOT_COMPLETED'),
					'ONCLICK' => "BX.CrmActivityEditor.items['{$gridEditorID}'].setActivityCompleted({$item['ID']}, false);",
				);
			}
			else
			{
				$arActions[] = array(
					'ICONCLASS' => 'edit',
					'TITLE' => GetMessage('CRM_ACTION_MARK_AS_COMPLETED'),
					'TEXT' => GetMessage('CRM_ACTION_MARK_AS_COMPLETED'),
					'ONCLICK' => "BX.CrmActivityEditor.items['{$gridEditorID}'].setActivityCompleted({$item['ID']}, true);",
				);
			}
		}

		if($item['CAN_DELETE'])
		{
			$arActions[] = array(
				'ICONCLASS' => 'delete',
				'TITLE' => GetMessage('CRM_ACTION_DELETE'),
				'TEXT' => GetMessage('CRM_ACTION_DELETE'),
				'ONCLICK' => "BX.CrmActivityEditor.items['{$gridEditorID}'].deleteActivity({$item['ID']}, false);",
			);
		}
	}

	$typeID = isset($item['~TYPE_ID']) ? intval($item['~TYPE_ID']) : CCrmActivityType::Undefined;
	$direction = isset($item['~DIRECTION']) ? intval($item['~DIRECTION']) : CCrmActivityDirection::Undefined;
	$typeClassName = '';
	$typeTitle = '';
	if($typeID === CCrmActivityType::Meeting):
		$typeClassName = 'crm-activity-meeting';
		$typeTitle = GetMessage('CRM_ACTION_TYPE_MEETING');
	elseif($typeID === CCrmActivityType::Call):
		if($direction === CCrmActivityDirection::Outgoing):
			$typeClassName = 'crm-activity-call-outgoing';
			$typeTitle = GetMessage('CRM_ACTION_TYPE_CALL_OUTGOING');
		else:
			$typeClassName = 'crm-activity-call-incoming';
			$typeTitle = GetMessage('CRM_ACTION_TYPE_CALL_INCOMING');
		endif;
	elseif($typeID === CCrmActivityType::Email):
		if($direction === CCrmActivityDirection::Outgoing):
			$typeClassName = 'crm-activity-email-outgoing';
			$typeTitle = GetMessage('CRM_ACTION_TYPE_EMAIL_OUTGOING');
		else:
			$typeClassName = 'crm-activity-email-incoming';
			$typeTitle = GetMessage('CRM_ACTION_TYPE_EMAIL_INCOMING');
		endif;
	elseif($typeID === CCrmActivityType::Task):
		$typeClassName = 'crm-activity-task';
		$typeTitle = GetMessage('CRM_ACTION_TYPE_TASK');
	endif;

	$subject = isset($item['~SUBJECT']) ? $item['~SUBJECT'] : '';
	if($subject !== '')
	{
		$typeTitle = "{$typeTitle}. {$subject}";
	}

	$typeTitle = htmlspecialcharsbx($typeTitle);

	$subjectHtml = '<div title="'.$typeTitle.'" class="crm-activity-info '.$typeClassName.'"><a alt="'.$typeTitle.'" class="crm-activity-subject" href="#" onclick="'.htmlspecialcharsbx($openViewJS).' return false;">'.(isset($item['SUBJECT']) ? $item['SUBJECT'] : '').'</a>';

	$priority = isset($item['~PRIORITY']) ? intval($item['~PRIORITY']) : CCrmActivityPriority::None;
	if($priority === CCrmActivityPriority::High)
	{
		$subjectHtml .= '<div class="crm-activity-important" title="'.htmlspecialcharsbx(GetMessage('CRM_ACTION_IMPORTANT')).'"></div>';
	}
	$subjectHtml .= '</div>';

	$completed = isset($item['~COMPLETED']) ? strtoupper($item['~COMPLETED']) : 'N';
	if($completed === 'Y'):
		$completedClassName = 'crm-activity-completed';
		$completedTitle = GetMessage('CRM_ACTION_COMPLETED');
		$completedOnClick = 'return false;';
	else:
		$completedClassName = 'crm-activity-not-completed';
		$completedTitle = GetMessage($item['CAN_COMPLETE'] ? 'CRM_ACTION_CLICK_TO_COMPLETE' : 'CRM_ACTION_NOT_COMPLETED');
		$completedOnClick = $item['CAN_COMPLETE'] ? 'BX.CrmActivityEditor.items[\''.$gridEditorID.'\'].setActivityCompleted('.$item['ID'].', true); return false;' : 'return false;';
	endif;

	$completedHtml = '<a class="'.$completedClassName.'" title="'.$completedTitle.'" alt="'.$completedTitle.'" href="#" onclick="'.$completedOnClick.'"></a>';
	$descriptionHtml = isset($item['DESCRIPTION_HTML']) ? $item['DESCRIPTION_HTML'] : '';

	$enableDescriptionCut = isset($item['ENABLE_DESCRIPTION_CUT']) ? $item['ENABLE_DESCRIPTION_CUT'] : false;
	if($enableDescriptionCut && strlen($descriptionHtml) > 64)
	{
		$descriptionHtml = substr($descriptionHtml, 0, 64).'<a href="#" onclick="BX.CrmInterfaceGridManager.expandEllipsis(this); return false;">...</a><span class="bx-crm-text-cut-on">'.substr($descriptionHtml, 64).'</span>';
	}

	$arRowData =
		array(
			'id' => $item['~ID'],
			'actions' => $arActions,
			'data' => $item,
			'editable' => $isEditable,
			'columnClasses' => array('COMPLETED' => 'bx-minimal'),
			'columns' => array(
				'SUBJECT'=> $subjectHtml,
				'RESPONSIBLE_FULL_NAME' => $item['~RESPONSIBLE_FULL_NAME'] !== '' ?
					'<a href="'.htmlspecialcharsbx($item['PATH_TO_RESPONSIBLE']).'" id="balloon_'.$arResult['GRID_ID'].'_'.$item['ID'].'">'.htmlspecialcharsbx($item['~RESPONSIBLE_FULL_NAME']).'</a>'.
						'<script type="text/javascript">BX.tooltip('.$item['RESPONSIBLE_ID'].', "balloon_'.$arResult['GRID_ID'].'_'.$item['ID'].'", "");</script>'
					: '',
				'CREATED' => '<span class="crm-activity-date-time">'.FormatDate('SHORT', MakeTimeStamp($item['~CREATED'])).'</span>',
				'START_TIME' => isset($item['~START_TIME']) && $item['~START_TIME'] !== '' ? '<span class="crm-activity-date-time">'.CCrmComponentHelper::TrimDateTimeString(FormatDate('FULL', MakeTimeStamp($item['~START_TIME'])), $dateTimeOptions).'</span>' : '',
				'END_TIME' => isset($item['~END_TIME']) && $item['~END_TIME'] !== '' ? '<span class="crm-activity-date-time">'.CCrmComponentHelper::TrimDateTimeString(FormatDate('FULL', MakeTimeStamp($item['~END_TIME'])), $dateTimeOptions).'</span>' : '',
				'DEADLINE' => isset($item['~DEADLINE']) && $item['~DEADLINE'] !== '' ? '<span class="crm-activity-date-time">'.CCrmComponentHelper::TrimDateTimeString(FormatDate('FULL', MakeTimeStamp($item['~DEADLINE'])), $dateTimeOptions).'</span>' : '',
				'COMPLETED' => $completedHtml,
				'DESCRIPTION' => $descriptionHtml
				)
		);

	$ownerTypeID = isset($item['OWNER_TYPE_ID']) ? intval($item['OWNER_TYPE_ID']) : 0;
	$ownerID = isset($item['OWNER_ID']) ? intval($item['OWNER_ID']) : 0;

	if($ownerTypeID > 0 && $ownerID > 0)
	{
		$showPath = '';
		$title = '';
		if($ownerTypeID === CCrmOwnerType::Lead)
		{
			$showPath = CComponentEngine::MakePathFromTemplate(
				COption::GetOptionString('crm', 'path_to_lead_show'),
				array('lead_id' => $ownerID)
			);
		}
		elseif($ownerTypeID === CCrmOwnerType::Deal)
		{
			$showPath = CComponentEngine::MakePathFromTemplate(
				COption::GetOptionString('crm', 'path_to_deal_show'),
				array('deal_id' => $ownerID)
			);
		}

		$title = CCrmOwnerType::GetCaption($ownerTypeID, $ownerID);
		if($showPath !== '' && $title !== '')
		{
			$arRowData['columns']['REFERENCE'] = '<a target="_blank" href="'.htmlspecialcharsbx($showPath).'">'.htmlspecialcharsbx($title).'</a>';
		}
	}

	$commLoaded = isset($item['COMMUNICATIONS_LOADED']) ? $item['COMMUNICATIONS_LOADED'] : true;
	$communications = $commLoaded && isset($item['COMMUNICATIONS']) ? $item['COMMUNICATIONS'] : array();

	$arSelect = $arResult['SELECTED_FIELDS'];
	if($arResult['DISPLAY_CLIENT'] && (empty($arSelect) || in_array('CLIENT', $arSelect, true)))
	{
		$columnHtml = '';
		$clientInfo = isset($item['CLIENT_INFO']) ? $item['CLIENT_INFO'] : null;
		if(is_array($clientInfo))
		{
			$columnHtml= CCrmViewHelper::PrepareEntityBaloonHtml(
				array(
					'ENTITY_TYPE_ID' => $clientInfo['ENTITY_TYPE_ID'],
					'ENTITY_ID' => $clientInfo['ENTITY_ID'],
					'PREFIX' => "{$arResult['UID']}_{$item['~ID']}_CLIENT",
					'TITLE' => isset($clientInfo['TITLE']) ? $clientInfo['TITLE'] : '',
					'SHOW_URL' => isset($clientInfo['SHOW_URL']) ? $clientInfo['SHOW_URL'] : ''
				)
			);
		}
		$arRowData['columns']['CLIENT'] = $columnHtml;
	}

	$arResult['GRID_DATA'][] = $arRowData;
	// <-- Preparing grig row

	// Preparing activity editor item -->
	$commData = array();
	if(!empty($communications))
	{
		foreach($communications as &$arComm)
		{
			CCrmActivity::PrepareCommunicationInfo($arComm);
			$commData[] = array(
				'id' => $arComm['ID'],
				'type' => $arComm['TYPE'],
				'value' => $arComm['VALUE'],
				'entityId' => $arComm['ENTITY_ID'],
				'entityType' => CCrmOwnerType::ResolveName($arComm['ENTITY_TYPE_ID']),
				'entityTitle' => $arComm['TITLE'],
				'entityUrl' => CCrmOwnerType::GetShowUrl($arComm['ENTITY_TYPE_ID'], $arComm['ENTITY_ID'])
			);
		}
		unset($arComm);
	}

	$responsibleID = isset($item['~RESPONSIBLE_ID']) ? intval($item['~RESPONSIBLE_ID']) : 0;
	$responsibleUrl = isset($item['PATH_TO_RESPONSIBLE']) ? $item['PATH_TO_RESPONSIBLE'] : '';
	if($responsibleUrl === '')
	{
		$responsibleUrl = CComponentEngine::MakePathFromTemplate(
			$arResult['PATH_TO_USER_PROFILE'],
			array('user_id' => $responsibleID)
		);
	}

	$editorItem = array(
		'ID' => $item['~ID'],
		'typeID' => $item['~TYPE_ID'],
		'subject' => $item['~SUBJECT'],
		'description' => isset($item['DESCRIPTION_RAW']) ? $item['DESCRIPTION_RAW'] : '',
		'descriptionHtml' => isset($item['DESCRIPTION_HTML']) ? $item['DESCRIPTION_HTML'] : '',
		'direction' => intval($item['~DIRECTION']),
		'location' => $item['~LOCATION'],
		'start' => isset($item['~START_TIME']) ? ConvertTimeStamp(MakeTimeStamp($item['~START_TIME']), 'FULL', SITE_ID) : '',
		'end' => isset($item['~END_TIME']) ? ConvertTimeStamp(MakeTimeStamp($item['~END_TIME']), 'FULL', SITE_ID) : '',
		'deadline' => isset($item['~DEADLINE']) ? ConvertTimeStamp(MakeTimeStamp($item['~DEADLINE']), 'FULL', SITE_ID) : '',
		'completed' => $item['~COMPLETED'] == 'Y',
		'notifyType' => intval($item['~NOTIFY_TYPE']),
		'notifyValue' => intval($item['~NOTIFY_VALUE']),
		'priority' => intval($item['~PRIORITY']),
		'responsibleID' => $responsibleID,
		'responsibleName' => isset($item['~RESPONSIBLE_FULL_NAME'][0]) ? $item['~RESPONSIBLE_FULL_NAME'] : GetMessage('CRM_UNDEFINED_VALUE'),
		'responsibleUrl' =>  $responsibleUrl,
		'storageTypeID' => intval($item['STORAGE_TYPE_ID']),
		'files' => $item['FILES'],
		'webdavelements' => $item['WEBDAV_ELEMENTS'],
		'diskfiles' => $item['DISK_FILES'],
		'associatedEntityID' => isset($item['~ASSOCIATED_ENTITY_ID']) ? intval($item['~ASSOCIATED_ENTITY_ID']) : 0
	);

	if(!$commLoaded)
	{
		$editorItem['communicationsLoaded'] = false;
	}
	else
	{
		$editorItem['communicationsLoaded'] = true;
		$editorItem['communications'] = $commData;
	}

	if(isset($item['OWNER_TYPE_ID']) && isset($item['OWNER_ID']))
	{
		$editorItem['ownerType'] = CCrmOwnerType::ResolveName($item['OWNER_TYPE_ID']);
		$editorItem['ownerID'] = $item['OWNER_ID'];
		$editorItem['ownerTitle'] = CCrmOwnerType::GetCaption($item['OWNER_TYPE_ID'], $item['OWNER_ID']);
		$editorItem['ownerUrl'] = CCrmOwnerType::GetShowUrl($item['OWNER_TYPE_ID'], $item['OWNER_ID']);
	}

	$editorItems[] = $editorItem;
	// <-- Preparing activity editor item
}
unset($item);

if($arResult['NEED_FOR_CONVERTING_OF_CALENDAR_EVENTS'])
{
	?><div class="crm-view-message"><?= GetMessage('CRM_ACTION_CONVERTING_OF_CALENDAR_EVENTS', array('#URL_EXECUTE_CONVERTING#' => htmlspecialcharsbx($arResult['CAL_EVENT_CONV_EXEC_URL']), '#URL_SKIP_CONVERTING#' => htmlspecialcharsbx($arResult['CAL_EVENT_CONV_SKIP_URL']))) ?></div><?
}

if($arResult['NEED_FOR_CONVERTING_OF_TASKS'])
{
	?><div class="crm-view-message"><?= GetMessage('CRM_ACTION_CONVERTING_OF_TASKS', array('#URL_EXECUTE_CONVERTING#' => htmlspecialcharsbx($arResult['TASK_CONV_EXEC_URL']), '#URL_SKIP_CONVERTING#' => htmlspecialcharsbx($arResult['TASK_CONV_SKIP_URL']))) ?></div><?
}

$enableToolbar = $arResult['ENABLE_TOOLBAR'];
$toolbarID =  strtolower("{$gridEditorID}_toolbar");
$useQuickFilter = $arResult['USE_QUICK_FILTER'];

$APPLICATION->IncludeComponent(
	'bitrix:crm.activity.editor',
	'',
	array(
		'CONTAINER_ID' => '',
		'EDITOR_ID' => $gridEditorID,
		'EDITOR_TYPE' => 'MIXED',
		'PREFIX' => $arResult['PREFIX'],
		'OWNER_TYPE' => $arResult['OWNER_TYPE'],
		'OWNER_ID' => $arResult['OWNER_ID'],
		'READ_ONLY' => $arResult['READ_ONLY'],
		'ENABLE_UI' => false,
		'ENABLE_TASK_ADD' => $arResult['ENABLE_TASK_ADD'],
		'ENABLE_CALENDAR_EVENT_ADD' => $arResult['ENABLE_CALENDAR_EVENT_ADD'],
		'ENABLE_EMAIL_ADD' => $arResult['ENABLE_EMAIL_ADD'],
		'ENABLE_TOOLBAR' => $enableToolbar,
		'TOOLBAR_ID' => $toolbarID,
		'FORM_ID' => $arResult['FORM_ID'],
		'EDITOR_ITEMS' => $editorItems,
		'DISABLE_STORAGE_EDIT' => isset($arResult['DISABLE_STORAGE_EDIT']) && $arResult['DISABLE_STORAGE_EDIT']
	),
	null,
	array('HIDE_ICONS' => 'Y')
);
$arActionList = array();
if($isEditable)
{
	$arActionList['mark_as_completed'] = GetMessage('CRM_ACTION_MARK_AS_COMPLETED');
	$arActionList['mark_as_not_completed'] = GetMessage('CRM_ACTION_MARK_AS_NOT_COMPLETED');
}

if($enableToolbar)
{
	$toolbarButtons = array();
	if($isEditable && $arResult['OWNER_TYPE'] !== '' && $arResult['OWNER_ID'] !== '')
	{
		$toolbarButtons[] = array(
			'TEXT' => GetMessage('CRM_ACTIVITY_LIST_ADD_EVENT_SHORT'),
			'TITLE' => GetMessage('CRM_ACTIVITY_LIST_ADD_EVENT'),
			'ICON' => 'btn-new crm-activity-command-add-event'
		);
	}

	if($arResult['ENABLE_TASK_ADD'])
	{
		$toolbarButtons[] = array(
			'TEXT' => GetMessage('CRM_ACTIVITY_LIST_ADD_TASK_SHORT'),
			'TITLE' => GetMessage('CRM_ACTIVITY_LIST_ADD_TASK'),
			'ICON' => 'btn-new crm-activity-command-add-task'
		);
	}

	if($arResult['ENABLE_CALENDAR_EVENT_ADD'])
	{
		$toolbarButtons[] = array(
			'TEXT' => GetMessage('CRM_ACTIVITY_LIST_ADD_CALL_SHORT'),
			'TITLE' => GetMessage('CRM_ACTIVITY_LIST_ADD_CALL'),
			'ICON' => 'btn-new crm-activity-command-add-call'
		);

		$toolbarButtons[] = array(
			'TEXT' => GetMessage('CRM_ACTIVITY_LIST_ADD_MEETING_SHORT'),
			'TITLE' => GetMessage('CRM_ACTIVITY_LIST_ADD_MEETING'),
			'ICON' => 'btn-new crm-activity-command-add-meeting'
		);
	}

	if($arResult['ENABLE_EMAIL_ADD'])
	{
		$toolbarButtons[] = array(
			'TEXT' => GetMessage('CRM_ACTIVITY_LIST_ADD_EMAIL_SHORT'),
			'TITLE' => GetMessage('CRM_ACTIVITY_LIST_ADD_EMAIL'),
			'ICON' => 'btn-new crm-activity-command-add-email'
		);
	}

	if($useQuickFilter)
	{
		$toolbarButtons[] = array(
			'TEXT' => GetMessage('CRM_ACTIVITY_LIST_SHOW_FILTER_SHORT'),
			'TITLE' => GetMessage('CRM_ACTIVITY_LIST_SHOW_FILTER'),
			'ICON' => 'crm-filter-light-btn',
			'ALIGNMENT' => 'right',
			'ONCLICK' => "BX.InterfaceGridFilterPopup.toggle('{$arResult['UID']}', this)"
		);
	}

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
		'GRID_ID' => $arResult['UID'],
		'HEADERS' => $arResult['HEADERS'],
		'SORT' => $arResult['SORT'],
		'SORT_VARS' => $arResult['SORT_VARS'],
		'ROWS' => $arResult['GRID_DATA'],
		'FOOTER' => array(array('title' => GetMessage('CRM_ALL'), 'value' => $arResult['ROWS_COUNT'])),
		'EDITABLE' => $isEditable,
		'ACTIONS' => array(
			'delete' => $isEditable ? 'Y' : 'N',
			'list' => $arActionList
		),
		'ACTION_ALL_ROWS' => true,
		'NAV_OBJECT' => $arResult['DB_LIST'],
		'FORM_ID' => $arResult['FORM_ID'],
		'TAB_ID' => $arResult['TAB_ID'],
		'FORM_URI' => $arResult['FORM_URI'],
		'AJAX_MODE' => $arResult['AJAX_MODE'],
		'AJAX_ID' => $arResult['AJAX_ID'],
		'AJAX_OPTION_JUMP' => $arResult['AJAX_OPTION_JUMP'],
		'AJAX_OPTION_HISTORY' => $arResult['AJAX_OPTION_HISTORY'],
		'AJAX_LOADER' => isset($arParams['AJAX_LOADER']) ? $arParams['AJAX_LOADER'] : null,
		'FILTER' => $arResult['FILTER'],
		'FILTER_PRESETS' => $arResult['FILTER_PRESETS'],
		'FILTER_TEMPLATE' => $enableToolbar && $useQuickFilter ? 'popup' : '',
		'RENDER_FILTER_INTO_VIEW' => false,
		'MANAGER' => array(
			'ID' => $gridManagerID,
			'CONFIG' => $gridManagerCfg
		)
	),
	$component
);

if(!$useQuickFilter):
?><script type="text/javascript">
	BX.ready(
			function()
			{
				var editor = BX.CrmActivityEditor.items['<?= CUtil::JSEscape($gridEditorID)?>'];
				editor.addActivityChangeHandler(
					function()
					{
						editor.setLocked(true);
						editor.setLockMessage("<?=GetMessageJS("CRM_ACTIVITY_LIST_WAIT_FOR_RELOAD")?>");
						editor.release();
						BX.CrmInterfaceGridManager.reloadGrid('<?= CUtil::JSEscape($arResult['GRID_ID'])?>');
					}
				);

				BX.addCustomEvent(
					window,
					"CrmGridFilterApply",
					function()
					{
						editor.setLocked(true);
						editor.setLockMessage("<?=GetMessageJS("CRM_ACTIVITY_LIST_WAIT_FOR_RELOAD")?>");
						editor.release();
					}
				);				
				
				//HACK: fix task popup overlay position & size
				BX.CrmActivityEditor.attachInterfaceGridReload();
			}
	);
</script><?
else:
?><script type="text/javascript">
	BX.ready(
			function()
			{
			<?
				$selectedFilterId = '';
				if($arResult['GRID_FILTER_INFO']['ID'] !== '')
					$selectedFilterId = $arResult['GRID_FILTER_INFO']['ID'];
				else
					$selectedFilterId = $arResult['GRID_FILTER_INFO']['IS_APPLIED'] || !empty($arResult['GRID_FILTER']) ? 'search_result' : strtolower($arResult['SHOW_MODE']);

				?>var selectedFilterId = '<?=CUtil::JSEscape($selectedFilterId)?>';
				var items =
						[
							{
								'value': 'search_result',
								'text': '<?= GetMessageJS('CRM_ACTION_QUICK_FILTER_SEARCH_RESULT') ?>',
								'enabled': false,
								'default': true
							}
						];

				<?$presetData = array();
				foreach($arResult['FILTER_PRESETS'] as $presetId => $preset)
				{
					$presetData[] = array(
						'value' => $presetId,
						'text' => $preset['name']
					);
				}?>
				var presets = <?= CUtil::PhpToJSObject($presetData)?>;
				for(var i = 0; i < presets.length; i++)
				{
					items.push(presets[i]);
				}
				items.push(
					{
						'value': 'all',
						'text': '<?= GetMessageJS('CRM_ACTION_QUICK_FILTER_ALL') ?>',
						'enabled': true,
						'default': false
					}
				);
				var editor = BX.CrmActivityEditor.items['<?= CUtil::JSEscape($gridEditorID)?>'];
				var quickFilterId = '<?= CUtil::JSEscape(strtolower($arResult['PREFIX']).'_crm_activity_grid_quick_filter') ?>';
				BX.CrmSelector.deleteItem(quickFilterId);
				var quickFilter = BX.CrmSelector.create(
					quickFilterId,
					{
						'container': BX('<?=CUtil::JSEscape($toolbarID)?>'),
						'selectedValue': selectedFilterId,
						'items': items,
						'layout': { 'position': 'first' }
					}
				);
				quickFilter.layout();
				quickFilter.addOnSelectListener(
					function(filter, item)
					{
						if(!item)
						{
							return;
						}
						var gridId = "<?=CUtil::JSEscape($arResult['GRID_ID'])?>";
						var v = item.getValue();
						if(typeof(BX.InterfaceGridFilter) !== "undefined")
						{
							// new style fillter
							var f = BX.InterfaceGridFilter.items[gridId + "_FILTER"];
							if(f)
							{
								if(v === 'all')
								{
									f.clear();
								}
								else
								{
									f.apply(v);
								}
							}
						}
						else
						{
							// old style fillter
							if(v === 'all')
							{
								BX.CrmInterfaceGridManager.clearFilter(gridId);
							}
							else
							{
								BX.CrmInterfaceGridManager.applyFilter(gridId, v);
							}
						}
					}
				);
				editor.addActivityChangeHandler(
					function()
					{
						var eventArgs = { cancel: false };
						BX.onCustomEvent("BeforeCrmActivityListReload", [eventArgs]);

						if(!eventArgs.cancel)
						{
							editor.setLocked(true);
							editor.setLockMessage("<?=GetMessageJS("CRM_ACTIVITY_LIST_WAIT_FOR_RELOAD")?>");
							editor.release();
							editor.removeActivityChangeHandler(this);
							BX.CrmInterfaceGridManager.reloadGrid("<?=CUtil::JSEscape($arResult['GRID_ID'])?>");
						}
					}
				);

				BX.addCustomEvent(
					window,
					"BXInterfaceGridApplyFilter",
					function()
					{
						editor.setLocked(true);
						editor.setLockMessage("<?=GetMessageJS("CRM_ACTIVITY_LIST_WAIT_FOR_RELOAD")?>");
						editor.release();
					}
				);					
				
				//HACK: fix task popup overlay position & size
				BX.CrmActivityEditor.attachInterfaceGridReload();
			}
	);
</script><?
endif;

$openViewItemId = isset($arResult['OPEN_VIEW_ITEM_ID']) ? $arResult['OPEN_VIEW_ITEM_ID'] : 0;
$openEditItemId = isset($arResult['OPEN_EDIT_ITEM_ID']) ? $arResult['OPEN_EDIT_ITEM_ID'] : 0;
if($openViewItemId > 0):
?><script type="text/javascript">
	BX.ready(
		function()
		{
			var editor = BX.CrmActivityEditor.items['<?=CUtil::JSEscape($gridEditorID)?>'];
			if(editor)
			{
				editor.viewActivity(<?=$openViewItemId?>);
			}
		}
	);
</script><?
elseif($openEditItemId > 0):
	?><script type="text/javascript">
		BX.ready(
			function()
			{
				var editor = BX.CrmActivityEditor.items['<?=CUtil::JSEscape($gridEditorID)?>'];
				if(editor)
				{
					editor.editActivity(<?=$openEditItemId?>);
				}
			}
		);
	</script><?
endif;