<?
if($INCLUDE_FROM_CACHE!='Y')return false;
$datecreate = '001452277651';
$dateexpire = '001452364051';
$ser_content = 'a:2:{s:7:"CONTENT";s:581:"<script type="text/javascript">

var taskIFramePopup;
BX.ready(function() {
	
	BX.Tasks.lwPopup.pathToView = "/company/personal/user/1/tasks/task/view/#task_id#/";
	BX.Tasks.lwPopup.pathToEdit = "/company/personal/user/1/tasks/task/edit/#task_id#/";

	
	taskIFramePopup	= BX.TasksIFramePopup.create({
		pathToView: "/company/personal/user/1/tasks/task/view/#task_id#/",
		pathToEdit: "/company/personal/user/1/tasks/task/edit/#task_id#/",
		events: {
															onTaskAdded: BX.DoNothing,
			onTaskChanged: BX.DoNothing,
			onTaskDeleted: BX.DoNothing		}
			});

	});
</script>";s:4:"VARS";a:3:{s:7:"SCRIPTS";a:5:{i:0;a:2:{s:2:"js";s:27:"/bitrix/js/tasks/cjstask.js";s:3:"rel";a:2:{i:0;s:4:"ajax";i:1;s:4:"json";}}i:1;a:2:{s:2:"js";s:37:"/bitrix/js/tasks/task-quick-popups.js";s:3:"rel";a:4:{i:0;s:5:"popup";i:1;s:4:"ajax";i:2;s:4:"json";i:3;s:7:"CJSTask";}}i:2;a:4:{s:2:"js";s:40:"/bitrix/js/tasks/core_planner_handler.js";s:3:"css";s:30:"/bitrix/js/tasks/css/tasks.css";s:4:"lang";s:54:"/bitrix/modules/tasks/lang/ru/core_planner_handler.php";s:3:"rel";a:2:{i:0;s:5:"popup";i:1;s:7:"tooltip";}}i:3;s:37:"/bitrix/js/tasks/task-iframe-popup.js";i:4;a:4:{s:2:"js";s:43:"/bitrix/js/calendar/core_planner_handler.js";s:3:"css";s:44:"/bitrix/js/calendar/core_planner_handler.css";s:4:"lang";s:57:"/bitrix/modules/calendar/lang/ru/core_planner_handler.php";s:3:"rel";a:2:{i:0;s:4:"date";i:1;s:5:"timer";}}}s:6:"STYLES";a:1:{i:0;s:30:"/bitrix/js/tasks/css/tasks.css";}s:4:"DATA";a:9:{s:13:"TASKS_ENABLED";b:1;s:5:"TASKS";a:0:{}s:11:"TASKS_COUNT";i:0;s:11:"TASKS_TIMER";b:0;s:13:"TASK_ON_TIMER";b:0;s:13:"MANDATORY_UFS";s:1:"N";s:16:"CALENDAR_ENABLED";b:1;s:6:"EVENTS";a:0:{}s:10:"EVENT_TIME";s:0:"";}}}';
return true;
?>