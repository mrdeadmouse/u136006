<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$environmentCheck = isset($GLOBALS['APPLICATION']) 
	&& is_object($GLOBALS['APPLICATION'])
	&& isset($GLOBALS['USER']) 
	&& is_object($GLOBALS['USER'])
	&& isset($arParams)
	&& is_array($arParams)
	&& CModule::IncludeModule('tasks')
	&& CModule::IncludeModule('mobileapp');

if ( ! $environmentCheck )
	return (false);

unset ($environmentCheck);

// Init params
$routePage = 'matrix';	// default route page

if (
	isset($_GET['routePage']) 
	&& ($_GET['routePage'] !== '__ROUTE_PAGE__')
)
{
	$routePage = (string) $_GET['routePage'];
}

// Limit cache mode at level:
// 'L' - don't use snmrouter cache, 
// '2' - route base task data, 
// 'D' - cache detail task data, 
// 'OD' - cache comments
$gear = 'OD';

if (!isset($arParams['PREFIX_FOR_PATH_TO_SNM_ROUTER']))
	$arParams['PREFIX_FOR_PATH_TO_SNM_ROUTER'] = SITE_DIR.'mobile/tasks/snmrouter/';

$snmRouterPath = $arParams['PREFIX_FOR_PATH_TO_SNM_ROUTER'];

$arComputedParams = array(
	'PATH_TO_SNM_ROUTER'        => $snmRouterPath . '?routePage=__ROUTE_PAGE__&USER_ID=#USER_ID#',
	'PATH_TO_SNM_ROUTER_AJAX'   => $arParams["PATH_TO_SNM_ROUTER_AJAX"],
	'PATH_TO_USER_TASKS'        => $snmRouterPath . '?routePage=list&USER_ID=#USER_ID#',		// Path to tasks list
	'PATH_TO_USER_TASKS_TASK'   => $snmRouterPath . '?routePage=view&USER_ID=#USER_ID#&TASK_ID=#TASK_ID#',		// Path to view tasks
	'PATH_TO_USER_TASKS_EDIT'   => $snmRouterPath . '?routePage=edit&USER_ID=#USER_ID#&TASK_ID=#TASK_ID#',		// Path to edit tasks
	'PATH_TO_USER_TASKS_FILTER' => $snmRouterPath . '?routePage=filter&USER_ID=#USER_ID#',		// Path to filter
	'GEAR'                      => $gear,
	'DATE_TIME_FORMAT'          => CDatabase::DateFormatToPHP(FORMAT_DATETIME)
);

foreach ($arComputedParams as $k => $v)
	$arParams[$k] = $v;

if (isset($_GET['USER_ID']) && ($_GET['USER_ID'] !== '#USER_ID#'))
	$arParams['USER_ID'] = (int) $_GET['USER_ID'];

if (isset($_GET['TASK_ID']) && ($_GET['TASK_ID'] !== '#TASK_ID#'))
	$arParams['TASK_ID'] = (int) $_GET['TASK_ID'];

if (isset($_GET['GROUP_ID']) && ($_GET['GROUP_ID'] !== '#GROUP_ID#'))
	$arParams['GROUP_ID'] = (int) $_GET['GROUP_ID'];

try
{
	switch ($routePage)
	{
		case 'matrix':
		break;

		case 'view':
			if (( ! isset($_GET['TASK_ID']) ) || ($_GET['TASK_ID'] === '#TASK_ID#'))
				$arParams['JUST_SHOW_BULK_TEMPLATE'] = 'Y';

			$APPLICATION->IncludeComponent(
				'bitrix:mobile.tasks.detail', 
				'.default', 
				$arParams, 
				false
			);

			return;
		break;

		case 'edit':
			$APPLICATION->IncludeComponent(
				'bitrix:mobile.tasks.edit', 
				'.default', 
				$arParams, 
				false
			);

			return;
		break;

		case 'list':
			// 'SELECT_FULL_DATA_EXCEPT_FILES', 'SELECT_ONLY_FILES_COUNT', 'NONE'
			$arParams['OPTIMIZATION_MODE'] = 'NONE';

			$APPLICATION->IncludeComponent(
				'bitrix:mobile.tasks.list', 
				'.default', 
				$arParams, 
				false
			);

			return;
		break;

		case 'filter':
			$APPLICATION->IncludeComponent(
				'bitrix:mobile.tasks.filter', 
				'.default', 
				$arParams, 
				false
			);

			return;
		break;

		default:
			throw new Exception('FATAL: unknown routePage: ' . $routePage);
			return;
		break;
	}
}
catch (Exception $e)
{
	// Hide all exceptions
}


if (isset($arParams['NAME_TEMPLATE']) && (strlen($arParams['NAME_TEMPLATE']) > 0))
{
	$arParams['NAME_TEMPLATE'] = str_replace(
		array('#NOBR#','#/NOBR#'), 
		'', 
		$arParams['NAME_TEMPLATE']
	);
}
else
	$arParams['NAME_TEMPLATE'] = CSite::GetNameFormat(false);

if (isset($arParams['USER_ID']))
{
	if (intval($arParams['USER_ID']) > 0)
		$arParams['USER_ID'] = (int) $arParams['USER_ID'];
}
else
	$arParams['USER_ID'] = (int) $GLOBALS['USER']->GetID();

// user paths
if ( ! isset($arParams['PATH_TO_USER_TASKS']) )
	$arParams['PATH_TO_USER_TASKS'] = '';

$arParams['PATH_TO_USER_TASKS'] = trim($arParams['PATH_TO_USER_TASKS']);	// Path to tasks list
if (strlen($arParams['PATH_TO_USER_TASKS']) <= 0)
	$arParams['PATH_TO_USER_TASKS'] = COption::GetOptionString('tasks', 'paths_task_user', null, SITE_ID);

if ( ! isset($arParams['PATH_TO_USER_TASKS_TASK']) )
	$arParams['PATH_TO_USER_TASKS_TASK'] = '';

$arParams['PATH_TO_USER_TASKS_TASK'] = trim($arParams['PATH_TO_USER_TASKS_TASK']);		// Path to tasks view
if (strlen($arParams['PATH_TO_USER_TASKS_TASK']) <= 0)
	$arParams['PATH_TO_USER_TASKS_TASK'] = COption::GetOptionString('tasks', 'paths_task_user_action', null, SITE_ID);

if ( ! isset($arParams['PATH_TEMPLATE_TO_USER_PROFILE']) )
	$arParams['PATH_TEMPLATE_TO_USER_PROFILE'] = '';

$arParams['PATH_TEMPLATE_TO_USER_PROFILE'] = trim($arParams['PATH_TEMPLATE_TO_USER_PROFILE']);

if (is_string($arParams['PATH_TO_USER_TASKS']) && is_int($arParams['USER_ID']))
{
	$arParams['PATH_TO_TASKS'] = str_replace(
		array('#user_id#', '#USER_ID#'), 
		$arParams['USER_ID'], 
		$arParams['PATH_TO_USER_TASKS']
	);
}

if (is_string($arParams['PATH_TO_USER_TASKS_TASK']) && is_int($arParams['USER_ID']))
{
	$arParams['PATH_TO_TASKS_TASK'] = str_replace(
		array('#user_id#', '#USER_ID#'), 
		$arParams['USER_ID'], 
		$arParams['PATH_TO_USER_TASKS_TASK']
	);
}

if (is_string($arParams['PATH_TO_USER_TASKS_EDIT']) && is_int($arParams['USER_ID']))
{
	$arParams['PATH_TO_TASKS_EDIT'] = str_replace(
		array('#user_id#', '#USER_ID#'), 
		$arParams['USER_ID'], 
		$arParams['PATH_TO_USER_TASKS_EDIT']
	);
}

$paramsCheck = is_int($arParams['USER_ID'])
	&& is_string($arParams['NAME_TEMPLATE'])
	&& (strlen($arParams['NAME_TEMPLATE']) > 0)
	&& is_string($arParams['DATE_TIME_FORMAT'])
	&& strlen($arParams['DATE_TIME_FORMAT'])
	&& is_string($arParams['PATH_TEMPLATE_TO_USER_PROFILE'])
	&& is_string($arParams['PATH_TO_USER_TASKS'])
	&& is_string($arParams['PATH_TO_USER_TASKS_TASK'])
	&& is_string($arParams['PATH_TO_TASKS'])
	&& is_string($arParams['PATH_TO_TASKS_EDIT'])
	&& is_string($arParams['PATH_TO_TASKS_TASK']);

if ( ! $paramsCheck )
	return (false);

unset ($paramsCheck);

$arResult = array();
$this->IncludeComponentTemplate();
return $arResult;
