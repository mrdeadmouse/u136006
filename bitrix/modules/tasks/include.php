<?
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/tasks/lang.php");

global $DBType;

require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/tasks/tools.php");

CModule::IncludeModule("iblock");

CModule::AddAutoloadClasses(
	'tasks',
	array(
		'CTasks'                 => 'classes/general/task.php',
		'CTaskMembers'           => 'classes/general/taskmembers.php',
		'CTaskTags'              => 'classes/general/tasktags.php',
		'CTaskFiles'             => 'classes/general/taskfiles.php',
		'CTaskDependence'        => 'classes/general/taskdependence.php',
		'CTaskTemplates'         => 'classes/general/tasktemplates.php',
		'CTaskSync'              => 'classes/general/tasksync.php',
		'CTaskReport'            => 'classes/general/taskreport.php',
		'CTasksWebService'       => 'classes/general/taskwebservice.php',
		'CTaskLog'               => 'classes/general/tasklog.php',
		'CTaskNotifications'     => 'classes/general/tasknotifications.php',
		'CTaskElapsedTime'       => 'classes/general/taskelapsed.php',
		'CTaskReminders'         => 'classes/general/taskreminders.php',
		'CTasksReportHelper'     => 'classes/general/tasks_report_helper.php',
		'CTasksNotifySchema'     => 'classes/general/tasks_notify_schema.php',
		'CTasksPullSchema'       => 'classes/general/tasks_notify_schema.php',
		'CTaskComments'          => 'classes/general/taskcomments.php',
		'CTaskFilterCtrl'        => 'classes/general/taskfilterctrl.php',
		'CTaskAssert'            => 'classes/general/taskassert.php',
		'CTaskItemInterface'     => 'classes/general/taskitem.php',
		'CTaskItem'              => 'classes/general/taskitem.php',
		'CTaskPlannerMaintance'  => 'classes/general/taskplannermaintance.php',
		'CTasksRarelyTools'      => 'classes/general/taskrarelytools.php',
		'CTasksTools'            => 'classes/general/tasktools.php',
		'CTaskSubItemAbstract'   => 'classes/general/subtaskitemabstract.php',
		'CTaskCheckListItem'     => 'classes/general/checklistitem.php',
		'CTaskElapsedItem'       => 'classes/general/elapseditem.php',
		'CTaskCommentItem'       => 'classes/general/commentitem.php',
		'CTaskRestService'       => 'classes/general/restservice.php',
		'CTaskListCtrl'          => 'classes/general/tasklistctrl.php',
		'CTaskListState'         => 'classes/general/taskliststate.php',
		'CTaskIntranetTools'     => 'classes/general/intranettools.php',
		'CTaskTimerCore'         => 'classes/general/timercore.php',
		'CTaskTimerManager'      => 'classes/general/timermanager.php',
		'CTaskCountersProcessor' => 'classes/general/countersprocessor.php',
		'CTaskCountersQueue'     => 'classes/general/countersprocessor.php',
		'CTaskCountersProcessorInstaller'   => 'classes/general/countersprocessorinstaller.php',
		'CTaskCountersProcessorHomeostasis' => 'classes/general/countersprocessorhomeostasis.php',
		'CTaskCountersNotifier'             => 'classes/general/countersnotifier.php',
		'CTaskColumnList'                   => 'classes/general/columnmanager.php',
		'CTaskColumnContext'                => 'classes/general/columnmanager.php',
		'CTaskColumnManager'                => 'classes/general/columnmanager.php',
		'CTaskColumnPresetManager'          => 'classes/general/columnmanager.php',
		'Bitrix\Tasks\TaskTable'            => 'lib/task.php',
		'Bitrix\Tasks\ElapsedTimeTable'     => 'lib/elapsedtime.php',
		'Bitrix\Tasks\MemberTable'          => 'lib/member.php',
		'Bitrix\Tasks\TagTable'             => 'lib/tag.php',
		'\Bitrix\Tasks\TaskTable'           => 'lib/task.php',
		'\Bitrix\Tasks\ElapsedTimeTable'    => 'lib/elapsedtime.php',
		'\Bitrix\Tasks\MemberTable'         => 'lib/member.php',
		'\Bitrix\Tasks\TagTable'            => 'lib/tag.php',

		'Bitrix\Tasks\DB\Helper'			=> "lib/db/".ToLower($DBType)."/helper.php",
		'\Bitrix\Tasks\DB\Helper'			=> "lib/db/".ToLower($DBType)."/helper.php",

		'\Bitrix\Tasks\DB\Tree\Exception'						=> "lib/db/tree/exception.php",
		'\Bitrix\Tasks\DB\Tree\NodeNotFoundException'			=> "lib/db/tree/exception.php",
		'\Bitrix\Tasks\DB\Tree\TargetNodeNotFoundException'		=> "lib/db/tree/exception.php",
		'\Bitrix\Tasks\DB\Tree\ParentNodeNotFoundException'		=> "lib/db/tree/exception.php",
		'\Bitrix\Tasks\DB\Tree\LinkExistsException'				=> "lib/db/tree/exception.php",
	)
);

////////////////////////
// assets

CJSCore::RegisterExt(
	'CJSTask',
	array(
		'js'  => '/bitrix/js/tasks/cjstask.js',
		'rel' =>  array('ajax', 'json')
	)
);
CJSCore::RegisterExt(
	'taskQuickPopups',
	array(
		'js'  => '/bitrix/js/tasks/task-quick-popups.js',
		'rel' =>  array('popup', 'ajax', 'json', 'CJSTask')
	)
);
/*
// un-comment later when do redesign
CJSCore::RegisterExt(
	'tasks.ui.widget',
	array(
		'js'  => '/bitrix/js/tasks/ui/widget.js',
		'rel' =>  array('ajax')
	)
);
CJSCore::RegisterExt(
	'tasks.ui.treeSelector',
	array(
		'js'  => '/bitrix/js/tasks/ui/treeselector.js',
		'rel' =>  array('ui.widget')
	)
);
*/

$GLOBALS["APPLICATION"]->AddJSKernelInfo(
	'tasks',
	array(
		'/bitrix/js/tasks/cjstask.js', '/bitrix/js/tasks/core_planner_handler.js',
		'/bitrix/js/tasks/task-iframe-popup.js', '/bitrix/js/tasks/task-quick-popups.js'
	)
);

$GLOBALS["APPLICATION"]->AddCSSKernelInfo('tasks', array('/bitrix/js/tasks/css/tasks.css', '/bitrix/js/tasks/css/core_planner_handler.css'));

//CTaskAssert::enableLogging();
