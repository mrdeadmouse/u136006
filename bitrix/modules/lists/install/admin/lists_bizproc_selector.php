<?
define("MODULE_ID", "lists");
define("ENTITY", "BizprocDocument");

$fp = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/bizproc/admin/bizproc_selector.php";
if(file_exists($fp))
	require($fp);