<?
define("STOP_STATISTICS", true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$result = array('SUCCESS' => true);
$user = $GLOBALS["USER"];

if (!CModule::IncludeModule("bizproc") || !CModule::IncludeModule("iblock"))
	$result['SUCCESS'] = false;

if ($_SERVER["REQUEST_METHOD"] != "POST" || !$user->IsAuthorized() || !check_bitrix_sessid())
{
	$result['SUCCESS'] = false;
	$result['ERROR'] = 'Access denied.';
}

if ($result['SUCCESS'])
{
	$taskId = (int)$_REQUEST['TASK_ID'];
	$task = false;

	if ($taskId > 0)
	{
		$dbTask = CBPTaskService::GetList(
			array(),
			array("ID" => $taskId, "USER_ID" => $user->getId(), 'USER_STATUS' => CBPTaskUserStatus::Waiting),
			false,
			false,
			array("ID", "WORKFLOW_ID", "ACTIVITY", "ACTIVITY_NAME", "MODIFIED", "OVERDUE_DATE", "NAME", "DESCRIPTION", "PARAMETERS")
		);
		$task = $dbTask->fetch();
	}

	if (!$task)
	{
		$result['SUCCESS'] = false;
		$result['ERROR'] = 'Task not found.';
	}
	else
	{
		$task["PARAMETERS"]["DOCUMENT_ID"] = CBPStateService::GetStateDocumentId($task['WORKFLOW_ID']);
		$task["MODULE_ID"] = $task["PARAMETERS"]["DOCUMENT_ID"][0];
		$task["ENTITY"] = $task["PARAMETERS"]["DOCUMENT_ID"][1];
		$task["DOCUMENT_ID"] = $task["PARAMETERS"]["DOCUMENT_ID"][2];

		$arErrorsTmp = array();

		$formData = $_REQUEST + $_FILES;
		if (SITE_CHARSET != "utf-8" && !empty($_SERVER['HTTP_BX_AJAX']))
			CUtil::decodeURIComponent($formData);

		if (!CBPDocument::PostTaskForm($task, $user->getId(), $formData, $arErrorsTmp))
		{
			$arError = array();
			foreach ($arErrorsTmp as $e)
				$arError[] = array(
					"id" => "bad_task",
					"text" => $e["message"]);
			$e = new CAdminException($arError);
			$result['ERROR'] = HTMLToTxt($e->GetString());
		}
	}
}

echo CUtil::PhpToJSObject($result);