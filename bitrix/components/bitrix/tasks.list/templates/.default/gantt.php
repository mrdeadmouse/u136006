<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/template.php');

CUtil::InitJSCore(array('popup', 'tooltip'));

$GLOBALS['APPLICATION']->AddHeadScript("/bitrix/components/bitrix/tasks.list/templates/.default/script.js");
$GLOBALS['APPLICATION']->AddHeadScript("/bitrix/components/bitrix/tasks.list/templates/.default/gantt-view.js");
$GLOBALS['APPLICATION']->AddHeadScript("/bitrix/js/tasks/task-popups.js");
$GLOBALS['APPLICATION']->AddHeadScript("/bitrix/js/tasks/gantt.js");
$GLOBALS['APPLICATION']->AddHeadScript("/bitrix/js/tasks/task-iframe-popup.js");

$GLOBALS['APPLICATION']->SetAdditionalCSS("/bitrix/js/intranet/intranet-common.css");
$GLOBALS['APPLICATION']->SetAdditionalCSS("/bitrix/js/main/core/css/core_popup.css");
$GLOBALS['APPLICATION']->SetAdditionalCSS("/bitrix/js/tasks/css/tasks.css");
$GLOBALS['APPLICATION']->SetAdditionalCSS("/bitrix/js/main/core/css/core_tags.css");

$GLOBALS['APPLICATION']->SetPageProperty("BodyClass", "page-one-column");

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
	"PATH_TO_TASKS_TASK" => $arParams["PATH_TO_TASKS_TASK"],
	"PATH_TO_USER_PROFILE" => $arParams["PATH_TO_USER_PROFILE"]
);

$APPLICATION->IncludeComponent(
	"bitrix:tasks.iframe.popup",
	".default",
	array(
		"ON_BEFORE_HIDE" => "onBeforeHide",
		"ON_AFTER_HIDE" => "onAfterHide",
		"ON_BEFORE_SHOW" => "onBeforeShow",
		"ON_AFTER_SHOW" => "onAfterShow",
		"ON_TASK_ADDED" => "onPopupTaskAdded",
		'ON_TASK_ADDED_MULTIPLE' => 'onPopupTaskAdded',
		"ON_TASK_CHANGED" => "onPopupTaskChanged",
		"ON_TASK_DELETED" => "onPopupTaskDeleted"
	),
	null,
	array("HIDE_ICONS" => "Y")
);
?>
<script type="text/javascript">
BX.message({
	TASKS_GANTT_CHART_TITLE : "<?php echo GetMessage("TASKS_TITLE")?>",
	TASKS_GANTT_EMPTY_DATE : "<?php echo GetMessage("TASKS_QUICK_INFO_EMPTY_DATE")?>",
	TASKS_GANTT_DATE_START : "<?php echo GetMessage("TASKS_DATE_START")?>",
	TASKS_GANTT_DATE_END : "<?php echo GetMessage("TASKS_DATE_END")?>",
	TASKS_GANTT_DEADLINE : "<?php echo GetMessage("TASKS_QUICK_DEADLINE")?>",
	TASKS_GANTT_MONTH_JAN : "<?php echo GetMessage("TASKS_GANTT_MONTH_JAN")?>",
	TASKS_GANTT_MONTH_FEB : "<?php echo GetMessage("TASKS_GANTT_MONTH_FEB")?>",
	TASKS_GANTT_MONTH_MAR : "<?php echo GetMessage("TASKS_GANTT_MONTH_MAR")?>",
	TASKS_GANTT_MONTH_APR : "<?php echo GetMessage("TASKS_GANTT_MONTH_APR")?>",
	TASKS_GANTT_MONTH_MAY : "<?php echo GetMessage("TASKS_GANTT_MONTH_MAY")?>",
	TASKS_GANTT_MONTH_JUN : "<?php echo GetMessage("TASKS_GANTT_MONTH_JUN")?>",
	TASKS_GANTT_MONTH_JUL : "<?php echo GetMessage("TASKS_GANTT_MONTH_JUL")?>",
	TASKS_GANTT_MONTH_AUG : "<?php echo GetMessage("TASKS_GANTT_MONTH_AUG")?>",
	TASKS_GANTT_MONTH_SEP : "<?php echo GetMessage("TASKS_GANTT_MONTH_SEP")?>",
	TASKS_GANTT_MONTH_OCT : "<?php echo GetMessage("TASKS_GANTT_MONTH_OCT")?>",
	TASKS_GANTT_MONTH_NOV : "<?php echo GetMessage("TASKS_GANTT_MONTH_NOV")?>",
	TASKS_GANTT_MONTH_DEC : "<?php echo GetMessage("TASKS_GANTT_MONTH_DEC")?>",
	TASKS_TASK_TITLE_LABEL : "<?php echo GetMessage("TASKS_TASK_TITLE_LABEL")?>",
	TASKS_RESPONSIBLE : "<?php echo GetMessage("TASKS_RESPONSIBLE")?>",
	TASKS_DIRECTOR : "<?php echo GetMessage("TASKS_CREATOR")?>",
	TASKS_DATE_CREATED : "<?php echo GetMessage("TASKS_FILTER_CREAT_DATE")?>",
	TASKS_DATE_DEADLINE : "<?php echo GetMessage("TASKS_QUICK_DEADLINE")?>",
	TASKS_DATE_START : "<?php echo GetMessage("TASKS_DATE_START")?>",
	TASKS_DATE_END : "<?php echo GetMessage("TASKS_DATE_END")?>",
	TASKS_DATE_STARTED : "<?php echo GetMessage("TASKS_DATE_STARTED")?>",
	TASKS_DATE_COMPLETED : "<?php echo GetMessage("TASKS_DATE_COMPLETED")?>",
	TASKS_STATUS : "<?php echo GetMessage("TASKS_STATUS")?>",
	TASKS_STATUS_IN_PROGRESS : "<?php echo GetMessage("TASKS_STATUS_IN_PROGRESS")?>",
	TASKS_STATUS_ACCEPTED : "<?php echo GetMessage("TASKS_STATUS_ACCEPTED")?>",
	TASKS_STATUS_COMPLETED : "<?php echo GetMessage("TASKS_STATUS_COMPLETED")?>",
	TASKS_STATUS_DELAYED : "<?php echo GetMessage("TASKS_STATUS_DELAYED")?>",
	TASKS_STATUS_NEW : "<?php echo GetMessage("TASKS_STATUS_NEW")?>",
	TASKS_STATUS_OVERDUE : "<?php echo GetMessage("TASKS_STATUS_OVERDUE")?>",
	TASKS_STATUS_WAITING : "<?php echo GetMessage("TASKS_STATUS_WAITING")?>",
	TASKS_STATUS_DECLINED : "<?php echo GetMessage("TASKS_STATUS_DECLINED")?>",
	TASKS_PRIORITY : "<?php echo GetMessage("TASKS_PRIORITY")?>",
	TASKS_PRIORITY_0 : "<?php echo GetMessage("TASKS_PRIORITY_0")?>",
	TASKS_PRIORITY_1 : "<?php echo GetMessage("TASKS_PRIORITY_1")?>",
	TASKS_PRIORITY_2 : "<?php echo GetMessage("TASKS_PRIORITY_2")?>",
	TASKS_QUICK_INFO_DETAILS : "<?php echo GetMessage("TASKS_QUICK_INFO_DETAILS")?>",
	TASKS_QUICK_INFO_EMPTY_DATE : "<?php echo GetMessage("TASKS_QUICK_INFO_EMPTY_DATE")?>",
	TASKS_FILES: "<?php echo GetMessage("TASKS_TASK_FILES")?>",
	TASKS_PATH_TO_USER_PROFILE : "<?php echo CUtil::JSEscape($arParams["PATH_TO_USER_PROFILE"])?>",
	TASKS_PATH_TO_TASK : "<?php echo CUtil::JSEscape($arParams["PATH_TO_TASKS_TASK"])?>",
	TASKS_LEGEND_TITLE_1: "<?php echo GetMessage("TASKS_LEGEND_TITLE_1")?>",
	TASKS_LEGEND_CONTENT_1: "<?php echo CUtil::JSEscape(GetMessage("TASKS_LEGEND_CONTENT_1"))?>",
	TASKS_LEGEND_TITLE_2: "<?php echo GetMessage("TASKS_LEGEND_TITLE_2")?>",
	TASKS_LEGEND_CONTENT_2: "<?php echo CUtil::JSEscape(GetMessage("TASKS_LEGEND_CONTENT_2"))?>",
	TASKS_LEGEND_TITLE_3: "<?php echo GetMessage("TASKS_LEGEND_TITLE_3")?>",
	TASKS_LEGEND_CONTENT_3: "<?php echo CUtil::JSEscape(GetMessage("TASKS_LEGEND_CONTENT_3"))?>",
	TASKS_LEGEND_TITLE_4: "<?php echo GetMessage("TASKS_LEGEND_TITLE_4")?>",
	TASKS_LEGEND_CONTENT_4: "<?php echo CUtil::JSEscape(GetMessage("TASKS_LEGEND_CONTENT_4"))?>",
	TASKS_LEGEND_TITLE_5: "<?php echo GetMessage("TASKS_LEGEND_TITLE_5")?>",
	TASKS_LEGEND_CONTENT_5: "<?php echo CUtil::JSEscape(GetMessage("TASKS_LEGEND_CONTENT_5"))?>",
	TASKS_LEGEND_PREV: "<?php echo GetMessage("TASKS_LEGEND_PREV")?>",
	TASKS_LEGEND_NEXT: "<?php echo GetMessage("TASKS_LEGEND_NEXT")?>",
	TASKS_LEGEND_CLASSNAME: "<?php echo GetMessage("TASKS_LEGEND_CLASSNAME")?>"
});
	
	var TaskGanttFilterPopup = {
		popup : null,

		init : function(bindElement)
		{
			if (this.popup != null)
				return;

			this.popup = new BX.PopupWindow("task-gantt-filter", bindElement, {
				content : BX("task-gantt-filter"),
				offsetLeft : -263 + bindElement.offsetWidth - 10,
				offsetTop : 3,
				className : "task-filter-popup-window",
				zIndex: -2,
				events: {
					onPopupClose: function(popupWindow) {
						if (tasksTagsPopUp != null)
						{
							tasksTagsPopUp.popupWindow.close();
						}
					}
				}
			});
			
			BX.bind(BX("task-gantt-filter"), "click", BX.delegate(this.onFilterSwitch, this));
		},

		show : function(bindElement)
		{
			if (!this.popup)
				this.init(bindElement);

			if (BX.hasClass(bindElement, "task-title-button-filter-pressed"))
			{
				this.popup.close();
				BX.removeClass(bindElement, "task-title-button-filter-pressed");
				this.adjustGanttHeight();
			}
			else
			{
				this.popup.show();
				BX.addClass(bindElement, "task-title-button-filter-pressed");
				this.adjustGanttHeight();
			}
		},
		
		adjustGanttHeight : function()
		{
			var ganttContainer = BX("gantt-container", true);
			var ganttHeight = ganttContainer.offsetHeight - (parseInt(ganttContainer.style.paddingBottom) || 0);
			var filterHeight = this.popup ? this.popup.popupContainer.offsetHeight : 0;

			if (filterHeight > ganttHeight)
				BX("gantt-container", true).style.paddingBottom = filterHeight - ganttHeight + "px";
			else
				BX("gantt-container", true).style.paddingBottom = "0px";
				
		},
		
		onFilterSwitch : function(event)
		{
			event = event || window.event;	
			var target = event.target || event.srcElement;
			if (BX.hasClass(target, "task-filter-mode-selected"))
				this.adjustGanttHeight();
		}
	};
	
	var arFilter = <?php echo CUtil::PhpToJSObject($arResult["FILTER"])?>;
	var arOrder = <?php echo CUtil::PhpToJSObject($arResult["ORDER"])?>;
	var tasksListAjaxUrl = "/bitrix/components/bitrix/tasks.list/ajax.php?SITE_ID=<?php echo SITE_ID?><?php echo $arResult["TASK_TYPE"] == "group" ? "&GROUP_ID=".$arParams["GROUP_ID"] : ""?>";
	var ajaxUrl = tasksListAjaxUrl;
	var tasksIFrameList = <?php echo CUtil::PhpToJSObject(array_keys($arResult["TASKS"]))?>;
	var ganttChart;

	BX.ready(function() {
		ganttChart = new BX.GanttChart(
			BX("gantt-container"),
			<?php $ts = time() + CTimeZone::GetOffset(); ?>
			new Date(
				<?php echo (int) date("Y", $ts); ?>, 
				<?php echo (int) (date("n", $ts) - 1); ?>, 
				<?php echo (int) date("j", $ts); ?>, 
				<?php echo (int) date("G", $ts); ?>, 
				<?php echo (int) date("i", $ts); ?>, 
				<?php echo (int) date("s", $ts); ?>
			),
			{
				datetimeFormat : BX.message("FORMAT_DATETIME"),
				dateFormat : BX.message("FORMAT_DATE"),
				userProfileUrl : "<?php echo CUtil::JSEscape($arParams["PATH_TO_USER_PROFILE"])?>",
				<?php $options =  CUserOptions::GetOption("tasks", "gantt", array("gutter_offset" => 300));?>
				gutterOffset : <?php echo intval($options["gutter_offset"])?>,

				events : {
					onGutterResize : function(gutterOffset) {
						BX.userOptions.save('tasks', 'gantt', 'gutter_offset', gutterOffset);
					},
					onProjectOpen : function(project) {
						BX.userOptions.save('tasks', 'opened_projects', project.id, project.opened);
						TaskGanttFilterPopup.adjustGanttHeight();
					},
					onTaskOpen : function(task) {

						if (task.opened && task.hasChildren && task.childTasks.length == 0)
						{
							var data = {
								sessid : BX.message("bitrix_sessid"),
								id : task.id,
								filter: arFilter,
								order: arOrder,
								path_to_user: BX.message("TASKS_PATH_TO_USER_PROFILE"),
								path_to_task: BX.message("TASKS_PATH_TO_TASK"),
								type: "json",
								bGannt : true,
								mode : "load"
							};
							
							var GanttObject = this;
							
							BX.ajax({
								"method": "POST",
								"dataType": "json",
								"url": tasksListAjaxUrl,
								"data":  data,
								"processData" : true,
								"onsuccess": (function() {
									var func = function(data) {
										for(var i = 0, count = data.length; i < count; i++)
										{
											__RenewMenuItems(data[i]);
										}
										GanttObject.addTasksFromJSON(data);
									}
										
									return func;
								})()
							});

						}
						
					},
					onTaskChange : function(updatedTasks) {
						function FormatDate(date) {
							var str = BX.message("FORMAT_DATETIME");
							str = str.replace(/YYYY/ig, date.getFullYear());
							str = str.replace(/MM/ig, this.Number(date.getMonth()+1));
							str = str.replace(/DD/ig, this.Number(date.getDate()));
							str = str.replace(/HH/ig, this.Number(date.getHours()));
							str = str.replace(/MI/ig, this.Number(date.getMinutes()));
							str = str.replace(/SS/ig, this.Number(date.getSeconds()));
							
							return str;
						}
						for (var i = 0; i < updatedTasks.length; i++)
						{
							if (updatedTasks[i].changes.length) {
								if (BX.util.in_array("dateDeadline", updatedTasks[i].changes)) {
									var data = {
										mode : "deadline",
										sessid : BX.message("bitrix_sessid"),
										id : updatedTasks[i].task.id,
										deadline : FormatDate(updatedTasks[i].dateDeadline)
									};
								} else {
									var data = {
										mode : "plan_dates",
										sessid : BX.message("bitrix_sessid"),
										id : updatedTasks[i].task.id
									};
									if (BX.util.in_array("dateStart", updatedTasks[i].changes)) {
										data.start_date = FormatDate(updatedTasks[i].dateStart);
									}
									if (BX.util.in_array("dateEnd", updatedTasks[i].changes)) {
										data.end_date = FormatDate(updatedTasks[i].dateEnd);
									}
								}
								BX.ajax.post(tasksListAjaxUrl, data);
							}
						}
					}
				}
			}
		);

		var projects = [
			<?php $i = 0?>
			<?php foreach($arResult["GROUPS"] as $arGroup):?>
				<?php $i++?>
				{ id : <?php echo $arGroup["ID"]?>, name : "<?php echo CUtil::JSEscape($arGroup["~NAME"])?>", opened : <?php echo $arGroup["EXPANDED"] ? "true" : "false"?>}<?php if ($i != sizeof($arResult["GROUPS"])):?>,<?php endif?>
			<?php endforeach?>
		];
		ganttChart.addProjectsFromJSON(projects);

		ganttChart.treeMode = <?php
			if ($arResult['VIEW_STATE']['SUBMODES']['VIEW_SUBMODE_WITH_SUBTASKS']['SELECTED'] === 'Y')
				echo 'true';
			else
				echo 'false';
		?>;

		var tasks = [
			<?php
			$i = 0;
			foreach($arResult["TASKS"] as $arTask)
			{
				$i++;
				tasksRenderJSON(
					$arTask, $arResult["CHILDREN_COUNT"]["PARENT_".$arTask["ID"]], 
					$arPaths, false, true, false, $arParams["NAME_TEMPLATE"]
				);

				if ($i != sizeof($arResult["TASKS"]))
				{
					?>,<?php
				}
			}
			?>
		];
		
		for(var i = 0, count = tasks.length; i < count; i++)
		{
			__RenewMenuItems(tasks[i]);
		}

		ganttChart.addTasksFromJSON(tasks);

		ganttChart.draw();

		<?php /* if (!CUserOptions::GetOption("tasks", "legend_shown", false)&& $arParams["HIDE_VIEWS"] != "Y"):?>
			<?php CUserOptions::SetOption("tasks", "legend_shown", true)?>
			ShowLegendPopup(BX("task-title-button-legend"));
		<?php endif */ ?>
	});
</script>
<?php $APPLICATION->ShowViewContent("task_menu"); ?>
<div id="gantt-container"></div>
<br />
<?php echo $arResult["NAV_STRING"]?>

<div id="task-gantt-filter" class="task-gantt-filter">
	<div class="task-filter<?php if ($arResult["ADV_FILTER"]["F_ADVANCED"] == "Y"):?> task-filter-advanced-mode<?php endif?>">

		<?php
			$name = $APPLICATION->IncludeComponent(
				"bitrix:tasks.filter.v2",
				".default",
				array(
					"ADV_FILTER" => $arResult["ADV_FILTER"],
					'USE_ROLE_FILTER' => 'N',
					"VIEW_TYPE" => $arResult["VIEW_TYPE"],
					"COMMON_FILTER" => $arResult["COMMON_FILTER"],
					"USER_ID" => $arParams["USER_ID"],
					"HIGHLIGHT_CURRENT" => $arResult["ADV_FILTER"]["F_ADVANCED"] == "Y" ? "N" : "Y",
					"ROLE_FILTER_SUFFIX" => $arResult["ROLE_FILTER_SUFFIX"],
					"PATH_TO_TASKS" => $arParams["PATH_TO_TASKS"],
					"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"]
				),
				null,
				array("HIDE_ICONS" => "Y")
			);
		?>

		<?php if ($arParams["USER_ID"] == $USER->GetID()):?>
			<div class="task-filter-extra-pages">
				<ul class="task-filter-items">
					<li class="task-filter-item">
						<a class="task-filter-item-link" href="<?php echo CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TEMPLATES"], array());?>"><span class="task-filter-item-left"></span><span class="task-filter-item-text"><?php echo GetMessage("TASKS_TEMPLATES")?></span><span class="task-filter-item-number"><?php echo CTaskTemplates::GetCount()?></span></a>
					</li>
					<li class="task-filter-item">
						<a class="task-filter-item-link" href="<?php echo CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_REPORTS"], array());?>"><span class="task-filter-item-left"></span><span class="task-filter-item-text"><?php echo GetMessage("TASKS_REPORTS")?></span></a>
					</li>
				</ul>
			</div>
		<?php endif?>

		<ul class="task-filter-extra-links">
			<li><i class="task-list-to-excel"></i><a href="<?php echo $APPLICATION->GetCurPageParam("EXCEL=Y", array("PAGEN_".$arResult["NAV_PARAMS"]["PAGEN"], "SHOWALL_".$arResult["NAV_PARAMS"]["PAGEN"], "VIEW", "F_TITLE", "F_RESPONSIBLE", "F_CREATED_BY", "F_ACCOMPLICE", "F_AUDITOR", "F_DATE_FROM", "F_DATE_TO", "F_TAGS", "F_STATUS", "F_SUBORDINATE", "F_ADVANCED"));?>"><?php echo GetMessage("TASKS_EXPORT_EXCEL")?></a></li>
			<li><i class="task-list-to-outlook"></i><a href="javascript:javascript:<?echo CIntranetUtils::GetStsSyncURL(array('LINK_URL' => '/'.$USER->GetID().'/'), 'tasks')?>"><?php echo GetMessage("TASKS_EXPORT_OUTLOOK")?></a></li>
		</ul>
	</div>
</div>

<?php
if (!isset($arParams["HIDE_VIEWS"]) || $arParams["HIDE_VIEWS"] != "Y")
{
	$arComponentParams = $arParams;
	$arComponentParams['VIEW_TYPE'] = $arResult['VIEW_TYPE'];
	$arComponentParams['GROUP'] = $arResult['GROUP'];
	$arComponentParams['TEMPLATES'] = $arResult['TEMPLATES'];

	$filterName = '';
	if (strlen($arResult['SELECTED_PRESET_NAME']))
		$filterName .= ': ' . htmlspecialcharsbx($arResult['SELECTED_PRESET_NAME']);

	$arComponentParams['SELECTED_PRESET_NAME'] = $arResult['SELECTED_PRESET_NAME'];

	$arComponentParams['ADDITIONAL_HTML'] = '<span class="task-title-button-filter" 
		onclick="TaskGanttFilterPopup.show(this);">'
		. '<span class="task-title-button-filter-left"></span>'
		. '<span class="task-title-button-filter-text">'
			. GetMessage("TASKS_FILTER") . $filterName
		. '</span><span class="task-title-button-filter-right"></span></span>';

	$template = '.default';
	if (defined('SITE_TEMPLATE_ID') && (SITE_TEMPLATE_ID === 'bitrix24'))
		$template = 'bitrix24';

	$ynNotGroupList = 'Y';
	if ($arParams['GROUP_ID'] != 0)
		$ynNotGroupList = 'N';

	$arComponentParams = array_merge(
		$arComponentParams,
		array(
			'SHOW_TAB_PANEL'        => 'Y',
			'VIEW_COUNTERS'         =>  $arResult['VIEW_COUNTERS'],
			'SHOW_SECTIONS_BAR'     => 'Y',
			'SHOW_FILTER_BAR'       => 'Y',
			'SHOW_COUNTERS_BAR'     =>  $ynNotGroupList,
			'SHOW_SECTION_PROJECTS' =>  $ynNotGroupList,
			'SHOW_SECTION_MANAGE'   => 'A',
			'SHOW_SECTION_COUNTERS' =>  $ynNotGroupList,
			'MARK_ACTIVE_ROLE'      => 'Y'
		)
	);

	if ($arParams['USER_ID'] > 0)
	{
		$arComponentParams['PATH_TO_PROJECTS'] = CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_USER_TASKS_PROJECTS_OVERVIEW'],
			array('user_id' => $arParams['USER_ID'])
		);
	}

	$APPLICATION->IncludeComponent(
		'bitrix:tasks.list.controls',
		$template,
		$arComponentParams,
		null,
		array('HIDE_ICONS' => 'Y')
	);
}
