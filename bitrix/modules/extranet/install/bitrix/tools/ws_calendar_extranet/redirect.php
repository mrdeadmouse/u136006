<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php"); 

if (!CModule::IncludeModule('extranet'))
	die();

$url = substr($_SERVER['REQUEST_URI'], strlen('/bitrix/tools/ws_calendar_extranet/'));
$url = preg_replace("/[\w]+.aspx\?ID=([\d]+)(.*)/i", '?EVENT_ID=$1', $url);
$url = str_replace('.php/', '.php', $url);

LocalRedirect('/'.$url);
die();
?>