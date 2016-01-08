<?
define('STOP_STATISTICS',    true);
define('NO_AGENT_CHECK',     true);
define('DisableEventsCheck', true);

define('BX_SECURITY_SHOW_MESSAGE', true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

CModule::IncludeModule('tasks');

__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

$SITE_ID = isset($_GET["SITE_ID"]) ? $_GET["SITE_ID"] : SITE_ID;

if ($_REQUEST['MODE'] == 'SEARCH')
{
	CUtil::JSPostUnescape();
	$APPLICATION->RestartBuffer();

	$search = $_REQUEST['SEARCH_STRING'];
	$arFilter = array("%TITLE" => $search);

	if (isset($_GET["FILTER"]))
		$arFilter = array_merge($arFilter, $_GET["FILTER"]);

	// Override CHECK_PERMISSIONS, if it was given in $_GET['FILTER']
	$arFilter['CHECK_PERMISSIONS'] = 'Y';

	$totalTasksToBeSelected = 10;

	// Firstly, get active tasks
	$arFilter['STATUS'] = array(CTasks::STATE_NEW, CTasks::STATE_PENDING, CTasks::STATE_IN_PROGRESS);

	$dbRes = CTasks::GetList(
		array('TITLE' => 'ASC'), 
		$arFilter,
		array('ID', 'TITLE', 'STATUS'),	// fields to be selected
		$totalTasksToBeSelected			// nPageTop
	);

	$arTasks = array();
	while ($arRes = $dbRes->fetch())
	{
		$arTasks[] = array(
			"ID" => $arRes["ID"],
			"TITLE" => $arRes["TITLE"],
			"STATUS" => $arRes["STATUS"]
		);
	}

	$tasksCount = count($arTasks);

	if (count($arTasks) < 10)
	{
		// Additionally, get not active tasks
		unset($arFilter['STATUS']);
		$arFilter['!STATUS'] = array(CTasks::STATE_NEW, CTasks::STATE_PENDING, CTasks::STATE_IN_PROGRESS);

		$dbRes = CTasks::GetList(
			array('TITLE' => 'ASC'),
			$arFilter,
			array('ID', 'TITLE', 'STATUS'),	// fields to be selected
			$totalTasksToBeSelected - $tasksCount  // nPageTop
		);

		while ($arRes = $dbRes->fetch())
		{
			$arTasks[] = array(
				"ID" => $arRes["ID"],
				"TITLE" => $arRes["TITLE"],
				"STATUS" => $arRes["STATUS"]
			);
		}
	}

	header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
	echo CUtil::PhpToJsObject($arTasks);

	CMain::FinalActions(); // to make events work on bitrix24
	die();
}
