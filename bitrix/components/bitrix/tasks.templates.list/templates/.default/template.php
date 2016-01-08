<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

CUtil::InitJSCore(array('popup', 'tooltip'));

$GLOBALS['APPLICATION']->AddHeadScript("/bitrix/components/bitrix/tasks.templates.list/templates/.default/script.js");
$GLOBALS['APPLICATION']->AddHeadScript("/bitrix/js/tasks/task-popups.js");
$GLOBALS['APPLICATION']->AddHeadScript("/bitrix/components/bitrix/tasks.list/templates/.default/table-view.js");

$GLOBALS['APPLICATION']->SetAdditionalCSS("/bitrix/js/intranet/intranet-common.css");
$GLOBALS['APPLICATION']->SetAdditionalCSS("/bitrix/js/main/core/css/core_popup.css");
$GLOBALS['APPLICATION']->SetAdditionalCSS("/bitrix/js/tasks/css/tasks.css");

$GLOBALS["APPLICATION"]->IncludeComponent(
	'bitrix:main.calendar',
	'',
	array(
		'SILENT' => 'Y',
	),
	null,
	array('HIDE_ICONS' => 'Y')
);

$arPaths = array(
	"PATH_TO_TEMPLATES_TEMPLATE" => $arParams["PATH_TO_TEMPLATES_TEMPLATE"],
	"PATH_TO_USER_PROFILE" => $arParams["PATH_TO_USER_PROFILE"],
	"PATH_TO_TASKS_TASK" => $arParams["PATH_TO_TASKS_TASK"]	
);
?>
<script type="text/javascript">
var ajaxUrl = "/bitrix/components/bitrix/tasks.templates.list/ajax.php?SITE_ID=<?php echo SITE_ID?>";
</script>
<?php $APPLICATION->ShowViewContent("task_menu"); ?>

<div class="task-list">
	<div class="task-list-left-corner"></div>
	<div class="task-list-right-corner"></div>
	<table class="task-list-table task-list-table-templates<?=($arParams['SHOW_GROUP_ACTIONS'] !== 'N' ? '' : ' task-list-table-templates-no-gop')?>" cellspacing="0" id="task-list-table">

		<thead>
			<tr>
				<th style="width: 31px;">
					<div style="width: 31px;">
						<input id="task_list_group_action_select_all_on_page" class="task-list-inp" type="checkbox" />
					</div>
				</th>

				<th style="width: auto;" class="<?php if(is_array($arResult["ORDER"]) && key($arResult["ORDER"]) == "TITLE"):?> task-column-selected task-column-order-by-<?php echo (current($arResult["ORDER"]) == "ASC" ? "asc" : "desc")?><?php endif?>" onclick="SortTable('<?php echo $APPLICATION->GetCurPageParam("SORTF=TITLE&SORTD=".(current($arResult["ORDER"]) == "ASC" && key($arResult["ORDER"]) == "TITLE" ? "DESC" : "ASC"), array("SORTF", "SORTD"));?>', event)">
					<?/*<div class="task-head-drag-btn"><span class="task-head-drag-btn-inner"></span></div>*/?>
					<div class="task-head-cell">
						<span class="task-head-cell-sort-order"></span>
						<span class="task-head-cell-title"><?php echo GetMessage("TASKS_TEMPLATE_TITLE")?></span>
					</div>
				</th>

				<?/*
				<th class="task-deadline-column<?php if(is_array($arResult["ORDER"]) && key($arResult["ORDER"]) == "DEADLINE"):?> task-column-selected task-column-order-by-<?php echo (current($arResult["ORDER"]) == "ASC" ? "asc" : "desc")?><?php endif?>" onclick="SortTable('<?php echo $APPLICATION->GetCurPageParam("SORTF=DEADLINE&SORTD=".(current($arResult["ORDER"]) == "ASC" && key($arResult["ORDER"]) == "DEADLINE" ? "DESC" : "ASC"), array("SORTF", "SORTD"));?>', event)">
					<div class="task-head-drag-btn"><span class="task-head-drag-btn-inner"></span></div>
					<div class="task-head-cell"><span class="task-head-cell-sort-order"></span><span class="task-head-cell-title"><?php echo GetMessage("TASKS_DEADLINE")?></span></div></th>
				*/?>

				<th class="task-responsible-column<?php if(is_array($arResult["ORDER"]) && key($arResult["ORDER"]) == "RESPONSIBLE_LAST_NAME"):?> task-column-selected task-column-order-by-<?php echo (current($arResult["ORDER"]) == "ASC" ? "asc" : "desc")?><?php endif?>" onclick="SortTable('<?php echo $APPLICATION->GetCurPageParam("SORTF=RESPONSIBLE_LAST_NAME&SORTD=".(current($arResult["ORDER"]) == "ASC" && key($arResult["ORDER"]) == "RESPONSIBLE_LAST_NAME" ? "DESC" : "ASC"), array("SORTF", "SORTD"));?>', event)">
					<?/*<div class="task-head-drag-btn"><span class="task-head-drag-btn-inner"></span></div>*/?>
					<div class="task-head-cell"><span class="task-head-cell-sort-order"></span><span class="task-head-cell-title"><?php echo GetMessage("TASKS_RESPONSIBLE")?></span></div></th>
				<th  class="task-director-column<?php if(is_array($arResult["ORDER"]) && key($arResult["ORDER"]) == "CREATED_BY"):?> task-column-selected task-column-order-by-<?php echo (current($arResult["ORDER"]) == "ASC" ? "asc" : "desc")?><?php endif?>"  onclick="SortTable('<?php echo $APPLICATION->GetCurPageParam("SORTF=CREATED_BY&SORTD=".(current($arResult["ORDER"]) == "ASC" && key($arResult["ORDER"]) == "CREATED_BY" ? "DESC" : "ASC"), array("SORTF", "SORTD"));?>', event)">
					<?/*<div class="task-head-drag-btn"><span class="task-head-drag-btn-inner"></span></div>*/?>
					<div class="task-head-cell"><span class="task-head-cell-sort-order"></span><span class="task-head-cell-title"><?php echo GetMessage("TASKS_CREATOR")?></span></div></th>

				<?/*
				<th class="task-grade-column">&nbsp;</th>
				*/?>
				<th class="task-complete-column">&nbsp;</th>

			</tr>
		</thead>
		<tbody>
			<?php if (sizeof($arResult["TEMPLATES"]) > 0):?>

				<?
				$arPaths['PATH_TO_TASKS_TASK_ADD_BY_TEMPLATE'] = $arPaths['PATH_TO_TASKS_TASK'];
				?>

				<?foreach($arResult["TEMPLATES"] as $key => $template):?>
					<?
						// hacks
						$template['ALLOWED_ACTIONS'] = $template['META:ALLOWED_ACTIONS'] = array();
						$template['STATUS'] = CTasks::STATE_PENDING;
						$arPaths['PATH_TO_TASKS_TASK'] = str_replace('#template_id#', '#task_id#', $arPaths['PATH_TO_TEMPLATES_TEMPLATE']);

						if($template['TPARAM_TYPE'] == CTaskTemplates::TYPE_FOR_NEW_USER)
							$template['RESPONSIBLE_ID'] = false;

						$APPLICATION->IncludeComponent(
							'bitrix:tasks.list.items',
							'.default',
							array(
								"PATHS"         => $arPaths,
								"PLAIN"         => false,
								"DEFER"         => false,
								"SITE_ID"       => SITE_ID,
								"TASK_ADDED"    => false,
								"PATH_TO_GROUP" => '',//$arParams['PATH_TO_GROUP'],
								"IFRAME"        => 'N',
								"NAME_TEMPLATE" => '', //$arParams["NAME_TEMPLATE"],
								"COLUMNS_IDS"       => array(
									CTaskColumnList::COLUMN_TITLE,
									//CTaskColumnList::COLUMN_DEADLINE,
									CTaskColumnList::COLUMN_RESPONSIBLE,
									CTaskColumnList::COLUMN_ORIGINATOR,
									CTaskColumnList::SYS_COLUMN_EMPTY,
								),
								'DATA_COLLECTION' => array(
									array(
										"CHILDREN_COUNT"   => $template['TEMPLATE_CHILDREN_COUNT'], //isset($arResult["CHILDREN_COUNT"]["PARENT_".$task["ID"]]) ? $arResult["CHILDREN_COUNT"]["PARENT_".$task["ID"]] : 0,
										"DEPTH"            => 0,
										"UPDATES_COUNT"    => 0, //isset($arResult["UPDATES_COUNT"][$task["ID"]]) ? $arResult["UPDATES_COUNT"][$task["ID"]] : 0,
										"PROJECT_EXPANDED" => false, //$projectExpanded,
										'ALLOWED_ACTIONS'  => array(),
										"TASK"             => $template
									)
								),

								// new params
								"SYSTEM_COLUMN_IDS" => array(CTaskColumnList::SYS_COLUMN_CHECKBOX),
								"SHOW_QUICK_INFORMERS" => false,

								"OPEN_TASK_IN_POPUP" => false,
								"CUSTOM_ACTIONS_CALLBACK" => 'templatesGetListItemActions', // use with caution

							), null, array("HIDE_ICONS" => "Y")
						);
					?>
				<?endforeach?>

			<?php endif?>
				<tr id="task-list-no-tasks" <?if(count($arResult["TEMPLATES"]) > 0):?>style="display:none;"<?endif?>><td class="task-new-item-column" colspan="5" style="text-align: center"><?php echo GetMessage("TASKS_NO_TEMPLATES"); ?></td></tr>
				<tr style="display:none;"><td colspan="5">&nbsp;</td></tr>
		</tbody>
	</table>

	<?//group actions begin?>
	<?if($arParams['SHOW_GROUP_ACTIONS'] !== 'N'):?>

		<div class="task-table-footer-wrap">
		<form action="<?=POST_FORM_ACTION_URI?>" id="task-list-group-operations">
			<div class="task-table-footer">
				<input type="checkbox" <?//onclick="active_btn(this)"?> id="task_list_group_action_all" class="task-table-foot-checkbox" title="<?=GetMessage('TASKS_LIST_TOOLTIP_FOR_ALL_TEMPLATE_ITEMS')?>"><label class="task-table-foot-label" for="id-1"><?=GetMessage('TASKS_LIST_GROUP_ACTION_FOR_ALL')?>
				</label><?/*<span class="task-table-footer-btn task-btn-edit"></span><span class="task-table-footer-btn task-btn-del"></span>*/?>
				<select id="task-list-group-action-selector" class="bx24-dropdown task-table-select" onchange="tasksListNS.onActionSelect(this);"/>
					<?/*<option value="noaction"></option>*/?>
					<option value="remove"><?php
						echo htmlspecialcharsbx(GetMessage('TASKS_DELETE_TEMPLATE'));
					?></option>
				</select>
				</span><span class="task-table-footer-inp-wrap task-table-footer-inp-del" style="display:none;">
					<input id="task-list-group-action-group-selector" autocomplete="off" type="text" class="bx24-field">
					<span id="task-list-group-action-group-delete" class="task-table-inp-del-icon"></span>
				</span>

				<span class="task-table-footer-inp-wrap">
					<input id="task-list-group-action-user-selector" 
						type="text" 
						autocomplete="off" class="task-table-footer-inp bx24-field" 
						style="display:none;" />
				</span>

				<span class="task-table-footer-inp-wrap">
					<input id="task-list-group-action-days_count-selector" 
						onchange="tasksListNS.onGroupActionDaysChanged();" 
						onkeyup="tasksListNS.onGroupActionDaysChanged();" 
						type="text" autocomplete="off" class="task-table-footer-inp bx24-field" 
						style="width: 40px; display:none;" maxlenght="4" />
				</span>

				<input id="task-list-group-action-value" type="hidden" value="" />

				<span id="task-list-group-action-submit" class="webform-small-button webform-small-button-transparent task-table-btn" onclick="
						(function(){
							var e = BX('task-list-group-action-selector');
							var subAction = e.options[e.selectedIndex].value;
							var confirmText;

							if (BX.hasClass(BX('task-list-group-action-submit'), 'task-noclass-disabled'))
								return;

							if (subAction === 'noaction')
								return;

							if (subAction === 'remove')
							{
								if (BX('task_list_group_action_all').checked)
									confirmText = BX.message('TASKS_LIST_CONFIRM_REMOVE_FOR_ALL_ITEMS');
								else
									confirmText = BX.message('TASKS_LIST_CONFIRM_REMOVE_FOR_SELECTED_ITEMS');

								if ( ! confirm(confirmText) )
									return;
							}

							BX.addClass(BX('task-list-group-action-submit'), 'task-noclass-disabled');

							tasksListNS.submitGroupAction(BX('task-list-group-action-submit'), subAction, {'controller_id': 'tasks.templates.list'}, BX('task-list-group-operations').action);
						})();
					">

					<span class="webform-small-button-left"></span>
					<span class="webform-small-button-text"><?=htmlspecialcharsbx(GetMessage('TASKS_LIST_SUBMIT'))?></span>
					<span class="webform-small-button-right"></span>
				</span>
			</div>
		</form>

	<?endif?>

</div>

<br />
<?php echo $arResult["NAV_STRING"]?>
<?php

$arComponentParams = array(
	'SHOW_TASK_LIST_MODES'   => 'N',
	'SHOW_HELP_ICON'         => 'N',
	'SHOW_SEARCH_FIELD'      => 'N',
	'SHOW_TEMPLATES_TOOLBAR' => 'N',
	'SHOW_QUICK_TASK_ADD'    => 'N',
	'SHOW_ADD_TASK_BUTTON'   => 'N',
	'SHOW_FILTER_BUTTON'     => 'N',
	'PATH_TO_PROJECTS'       => CComponentEngine::MakePathFromTemplate(
		$arParams['PATH_TO_USER_TASKS_PROJECTS_OVERVIEW'],
		array('user_id' => $arParams['USER_ID'])
	),
	'CUSTOM_ELEMENTS' => array(
		'ADD_BUTTON' => array(
			'name'    =>  isset($arParams['BASE_TEMPLATE_ID']) ? GetMessage('TASKS_ADD_SUB_TEMPLATE') : GetMessage('TASKS_ADD_TEMPLATE'),
			'onclick' =>  null,
			'url'     =>  CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TEMPLATES_TEMPLATE"], array("template_id" => 0, "action" => "edit")).(isset($arParams['BASE_TEMPLATE_ID']) ? '?BASE_TEMPLATE='.intval($arParams['BASE_TEMPLATE_ID']) : '')
		),
		'BACK_BUTTON' => array(
			'name'    =>  isset($arParams['BASE_TEMPLATE_ID']) ? GetMessage('TASKS_ADD_BACK_TO_TEMPLATE_LIST') : GetMessage('TASKS_ADD_BACK_TO_TASKS_LIST'),
			'onclick' =>  null,
			'url'     =>  CComponentEngine::MakePathFromTemplate((isset($arParams['BASE_TEMPLATE_ID']) ? $arParams["PATH_TO_TASKS_TEMPLATES"] : $arParams["PATH_TO_TASKS"]), array())
		)
	)
);

$APPLICATION->IncludeComponent(
	'bitrix:tasks.list.controls',
	((defined('SITE_TEMPLATE_ID') && (SITE_TEMPLATE_ID === 'bitrix24')) ? 'bitrix24' : '.default'),
	$arComponentParams,
	null,
	array('HIDE_ICONS' => 'Y')
);

//array_keys($arResult['KNOWN_COLUMNS'])
//$arResult['COLUMNS']
?>

<script>

<?//minimum js code required to launch task tree grid interface ...?>

BX.message({
	TASKS_PATH_TO_USER_PROFILE : '<?php echo CUtil::JSEscape($arParams['PATH_TO_USER_PROFILE'])?>',
	TASKS_PATH_TO_TASK : '<?php echo CUtil::JSEscape(str_replace('#template_id#', '#task_id#', $arParams['PATH_TO_TEMPLATES_TEMPLATE']))?>',
	TASKS_LIST_MENU_RESET_TO_DEFAULT_PRESET: '',
	TASKS_PATH_TO_TEMPLATES_TEMPLATE: '<?php echo CUtil::JSEscape($arParams['PATH_TO_TEMPLATES_TEMPLATE'])?>',

	TASKS_LIST_CONFIRM_ACTION_FOR_ALL_ITEMS : '<?php echo GetMessageJS('TASKS_LIST_CONFIRM_ACTION_FOR_ALL_TEMPLATE_ITEMS'); ?>',
	TASKS_LIST_CONFIRM_REMOVE_FOR_SELECTED_ITEMS : '<?php echo GetMessageJS('TASKS_LIST_CONFIRM_REMOVE_FOR_SELECTED_TEMPLATE_ITEMS'); ?>',
	TASKS_LIST_CONFIRM_REMOVE_FOR_ALL_ITEMS : '<?php echo GetMessageJS('TASKS_LIST_CONFIRM_REMOVE_FOR_ALL_TEMPLATE_ITEMS'); ?>',
});

var tasksListAjaxUrl = "/bitrix/components/bitrix/tasks.templates.list/ajax.php";
var ajaxUrl = tasksListAjaxUrl;

BX.ready(function(){

	var knownColumnsIds    = [<?php echo implode(', ', array()); ?>];
	var selectedColumnsIds = [];
	<?php
	$selectedColumns = array();
	foreach (array() as $columnId)
	{
		?>selectedColumnsIds.push(<?php echo (int) $columnId['ID']; ?>);<?php
	}
	?>

	tasksListNS.onReady(
		BX('task-list-table'),
		'task_list_group_action_ID',	// name for group actions checkboxes
		BX('task_list_group_action_select_all_on_page'),	// checkbox which selects all other checkboxes
		BX('task_list_group_action_all'),	// checkbox all items on all pages
		45<?php echo $minColumnWidth; ?>,
		29<?php echo $lastColumnMinWidth; ?>,
		1,
		null,
		knownColumnsIds,
		selectedColumnsIds,
		<?php echo CUtil::PhpToJSObject(array()); ?>,
		'<?php echo CUtil::JSEscape($USER->getFormattedName($bUseBreaks = false, $bHTMLSpec = false)); ?>'
	);

	var rows = BX('task-list-table').querySelectorAll('tr.task-list-item');
	for(var k = 0; k < rows.length; k++)
	{
		rows[k].style.display = 'table-row';
	}
});
</script>
<?/*<script>tasksListTemplateDefaultInit()</script>*/?>
<?/*<script>tasksListTemplateDefaultTableViewInit()</script>*/?>