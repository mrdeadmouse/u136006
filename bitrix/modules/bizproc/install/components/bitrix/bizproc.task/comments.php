<?php
/**
 * Comments sandbox (iframe), for compatibility with Live Feed and BP task popup
 */
define("STOP_STATISTICS", true);
global $APPLICATION;
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if (!$GLOBALS["USER"]->IsAuthorized() || !check_bitrix_sessid() || !CModule::IncludeModule("bizproc"))
	die;

$taskId = isset($_REQUEST['TASK_ID'])? (int)$_REQUEST['TASK_ID'] : 0;
$userId = isset($_REQUEST['USER_ID'])? (int)$_REQUEST['USER_ID'] : 0;
if (!$userId)
	$userId = $GLOBALS['USER']->getId();

if ($userId != $GLOBALS["USER"]->getId())
{
	if (!CBPHelper::checkUserSubordination($GLOBALS["USER"]->GetID(), $userId))
	{
		die;
	}
}

$task = null;

if ($taskId > 0)
{
	$dbTask = CBPTaskService::GetList(
		array(),
		array("ID" => $taskId, "USER_ID" => $userId),
		false,
		false,
		array("ID", "WORKFLOW_ID")
	);
	$task = $dbTask->fetch();
}

if (!$task)
{
	die;
}

$APPLICATION->RestartBuffer();
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?=LANGUAGE_ID?>" lang="<?=LANGUAGE_ID?>">
<head><?php
	$APPLICATION->ShowHead();
	$APPLICATION->AddHeadString('
				<style>
				body {background: #F8FAFB !important;}
				.feed-comments-block {margin: 0;}
				</style>
			', false, true);
	?></head>
<body>
	<div id="wrapper">
	<?php
		// A < E < I < M < Q < U < Y
		// A - NO ACCESS, E - READ, I - ANSWER
		// M - NEW TOPIC
		// Q - MODERATE, U - EDIT, Y - FULL_ACCESS
		$APPLICATION->IncludeComponent("bitrix:forum.comments", "bitrix24", array(
			"FORUM_ID" => CBPHelper::getForumId(),
			"ENTITY_TYPE" => "WF",
			"ENTITY_ID" => CBPStateService::getWorkflowIntegerId($task['WORKFLOW_ID']),
			"ENTITY_XML_ID" => "WF_".$task['WORKFLOW_ID'],
			"PERMISSION" => "Y",
			"URL_TEMPLATES_PROFILE_VIEW" => "/company/personal/user/#user_id#/",
			"SHOW_RATING" => "Y",
			"SHOW_LINK_TO_MESSAGE" => "N",
			"BIND_VIEWER" => "Y"
		),
			false,
			array('HIDE_ICONS' => 'Y')
		);
	?>
	</div>
</body>
</html><?
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');
die();