<?php
use Bitrix\Disk\Document\DocumentController;
use Bitrix\Disk\Document\LocalDocumentController;

define("STOP_STATISTICS", true);
define("PUBLIC_AJAX_MODE", true);
define("NO_KEEP_STATISTIC", "Y");
define("NO_AGENT_STATISTIC","Y");
define("DisableEventsCheck", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if(!\Bitrix\Main\Loader::includeModule('disk'))
{
	die;
}

if(empty($_GET['document_action']) || empty($_GET['service']))
{
	die;
}

if(LocalDocumentController::isLocalService($_GET['service']))
{
	$docController = new LocalDocumentController;
	$docController
		->setActionName(empty($_GET['primaryAction'])? $_GET['document_action'] : $_GET['primaryAction'])
		->exec()
	;
}
else
{
	$docController = new DocumentController;
	$docController
		->setActionName($_GET['document_action'])
		->setDocumentHandlerName($_GET['service'])
		->exec()
	;
}

