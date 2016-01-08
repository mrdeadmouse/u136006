<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$url = substr($_SERVER['REQUEST_URI'], strlen('/bitrix/tools/ws_tasks/'));
$matches = array();
if (preg_match_all("/[\w]+.aspx\?ID=([\d]+)(.*)/i", $url, $matches))
{
	if (COption::GetOptionString("intranet", "use_tasks_2_0", "N") != "Y")
	{
		if (($ID = intval($matches[1][0])) && CModule::IncludeModule('iblock'))
		{
			$dbRes = CIBlockElement::GetByID($ID);
			if ($obElement = $dbRes->GetNextElement())
			{
				$arFields = $obElement->GetFields();

				$obSection = CIBLockSection::GetByID($arFields['IBLOCK_SECTION_ID']);
				$arSection = $obSection->Fetch();

				if ($arSection['XML_ID'] == 'users_tasks')
				{
					$arProperty = $obElement->GetProperty('TaskAssignedTo');

					$url = str_replace(
						array('#USER_ID#', '#TASK_ID#'),
						array($arProperty['VALUE'], $ID),
						COption::GetOptionString('intranet', 'path_task_user_entry', '/company/personal/user/#USER_ID#/tasks/task/view/#TASK_ID#/')
					);
				}
				else
				{
					while (!$arSection['XML_ID'])
					{
						if ($arSection['IBLOCK_SECTION_ID'] <= 0)
							break;

						$obSection = CIBLockSection::GetByID($arSection['IBLOCK_SECTION_ID']);
						$arSection = $obSection->Fetch();
					}

					if ($arSection['XML_ID'])
					{
						$url = str_replace(
							array('#GROUP_ID#', '#TASK_ID#'),
							array($arSection['XML_ID'], $ID),
							COption::GetOptionString('intranet', 'path_task_group_entry', '/workgroups/group/#GROUP_ID#/tasks/task/view/#TASK_ID#/')
						);
					}
					else
					{
						$url = str_replace(
							array('#USER_ID#'),
							array(intval($url)),
							COption::GetOptionString('intranet', 'path_task_user', '/company/personal/user/#USER_ID#/tasks/')
						);
					}
				}
			}
		}
	}
	else
	{
		if (($ID = intval($matches[1][0])) && CModule::IncludeModule('tasks'))
		{
			$dbRes = CTasks::GetByID($ID);
			if ($arTask = $dbRes->Fetch())
			{
				if (intval($arTask["GROUP_ID"]) > 0)
				{
					$url = str_replace(
						array('#GROUP_ID#', '#TASK_ID#'),
						array($arTask["GROUP_ID"], $ID),
						COption::GetOptionString('intranet', 'path_task_group_entry', '/workgroups/group/#GROUP_ID#/tasks/task/view/#TASK_ID#/')
					);
				}
				else
				{
					$url = str_replace(
						array('#USER_ID#', '#TASK_ID#'),
						array($arTask["RESPONSIBLE_ID"], $ID),
						COption::GetOptionString('intranet', 'path_task_user_entry', '/company/personal/user/#USER_ID#/tasks/task/view/#TASK_ID#/')
					);
				}
			}
		}
	}
}
else
{
	$url = str_replace(
		array('#USER_ID#'),
		array(intval($url)),
		COption::GetOptionString('intranet', 'path_task_user', '/company/personal/user/#USER_ID#/tasks/')
	);
}

$url = str_replace('.php/', '.php', $url);
if (substr($url, 0, 1) != '/') $url = '/'.$url;

LocalRedirect($url);
die();
?>