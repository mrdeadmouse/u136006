<?
define("STOP_STATISTICS", true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if (COption::GetOptionString("intranet", "use_tasks_2_0", "N") == "Y")
{
	$APPLICATION->IncludeComponent(
		"bitrix:webservice.server",
		"",
		array(
			'WEBSERVICE_NAME' => 'bitrix.webservice.tasks',
			'WEBSERVICE_CLASS' => 'CTasksWebService',
			'WEBSERVICE_MODULE' => 'tasks',
		),
		null, array('HIDE_ICONS' => 'Y')
	);
}
else
{
	$APPLICATION->IncludeComponent(
		"bitrix:webservice.server",
		"",
		array(
			'WEBSERVICE_NAME' => 'bitrix.webservice.intranet.tasks',
			'WEBSERVICE_CLASS' => 'CIntranetTasksWS',
			'WEBSERVICE_MODULE' => 'intranet',
		),
		null, array('HIDE_ICONS' => 'Y')
	);
}

die();
?>