<?
define("STOP_STATISTICS", true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$APPLICATION->ShowAjaxHead();
$APPLICATION->IncludeComponent("bitrix:bizproc.task",
	'.default',
	array(
		'TASK_ID' => isset($_REQUEST['TASK_ID'])? (int)$_REQUEST['TASK_ID'] : 0,
		'USER_ID' => isset($_REQUEST['USER_ID'])? (int)$_REQUEST['USER_ID'] : 0,
		'POPUP' => 'Y',
		'IFRAME' => isset($_REQUEST['IFRAME']) && $_REQUEST['IFRAME'] == 'Y' ? 'Y' : 'N'
	)
);