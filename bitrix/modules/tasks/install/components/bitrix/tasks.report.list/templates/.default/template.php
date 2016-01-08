<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<?
$arComponentParams = array(
	'USER_ID'                => $arResult['USER_ID'],
	'GROUP_ID'               =>  0,
	'SHOW_TASK_LIST_MODES'   => 'N',
	'SHOW_HELP_ICON'         => 'N',
	'SHOW_SEARCH_FIELD'      => 'N',
	'SHOW_TEMPLATES_TOOLBAR' => 'N',
	'SHOW_QUICK_TASK_ADD'    => 'N',
	'SHOW_ADD_TASK_BUTTON'   => 'N',
	'SHOW_FILTER_BUTTON'     => 'N',
	'SHOW_SECTIONS_BAR'      => 'Y',
	'SHOW_FILTER_BAR'        => 'N',
	'SHOW_COUNTERS_BAR'      => 'N',
	'SHOW_SECTION_PROJECTS'  => 'Y',
	'SHOW_SECTION_MANAGE'    => 'A',	// auto
	'SHOW_SECTION_COUNTERS'  => 'Y',
	'MARK_ACTIVE_ROLE'       => 'N',
	'MARK_SECTION_PROJECTS'  => 'N',
	'MARK_SECTION_REPORTS'   => 'Y',
	'SECTION_URL_PREFIX'     =>  CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS"], array())
);

if ($arParams['GROUP_ID'])
	$arComponentParams['GROUP_ID'] = $arParams['GROUP_ID'];

if ($arResult['USER_ID'] > 0)
{
	$arComponentParams['PATH_TO_PROJECTS'] = CComponentEngine::MakePathFromTemplate(
		$arParams['PATH_TO_USER_TASKS_PROJECTS_OVERVIEW'],
		array('user_id' => $arResult['USER_ID'])
	);
}

$APPLICATION->IncludeComponent(
	'bitrix:tasks.list.controls',
	((defined('SITE_TEMPLATE_ID') && (SITE_TEMPLATE_ID === 'bitrix24')) ? 'bitrix24' : '.default'),
	$arComponentParams,
	null,
	array('HIDE_ICONS' => 'Y')
);

$APPLICATION->IncludeComponent(
	"bitrix:report.list",
	"",
	array(
		"USER_ID" => $arResult["USER_ID"],
		"GROUP_ID" => $arParams["GROUP_ID"],
		"PATH_TO_REPORT_LIST" => $arParams["PATH_TO_TASKS_REPORT"],
		"PATH_TO_REPORT_CONSTRUCT" => $arParams["PATH_TO_TASKS_REPORT_CONSTRUCT"],
		"PATH_TO_REPORT_VIEW" => $arParams["PATH_TO_TASKS_REPORT_VIEW"],
		"REPORT_HELPER_CLASS" => "CTasksReportHelper"
	),
	false
);

?>

<?php $this->SetViewTarget("sidebar_tools_1", 100);?>
<div class="sidebar-block task-filter task-filter-report">
	<b class="r2"></b><b class="r1"></b><b class="r0"></b>
	<div class="sidebar-block-inner">
		<ul class="task-filter-items">
			<li class="task-filter-item task-filter-item-selected">
				<a href="<?php echo CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS_REPORT"], array());?>" class="task-filter-item-link"><span
					class="task-filter-item-left"></span><span class="task-filter-item-text"><?php echo GetMessage("TASKS_REPORT_REPORTS")?></span></a>
			</li>
			<li class="task-filter-item">
				<a href="<?php echo CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS"], array());?>" class="task-filter-item-link"><span
					class="task-filter-item-left"></span><span class="task-filter-item-text"><?php echo GetMessage("TASKS_REPORT_TASKS")?></span></a>
			</li>
		</ul>
	</div>
	<i class="r0"></i><i class="r1"></i><i class="r2"></i>
</div>
<?php $this->EndViewTarget();?>

