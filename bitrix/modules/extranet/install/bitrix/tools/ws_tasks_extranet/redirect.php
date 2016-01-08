<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php"); 

$url = substr($_SERVER['REQUEST_URI'], strlen('/bitrix/tools/ws_tasks_extranet/'));
$matches = array();
if (preg_match_all("/[\w]+.aspx\?ID=([\d]+)(.*)/i", $url, $matches))
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
					COption::GetOptionString('intranet', 'path_task_user_entry', '/extranet/personal/user/#USER_ID#/tasks/task/view/#TASK_ID#/', CExtranet::GetExtranetSiteID())
				);
			}
			else
			{
				$url = str_replace(
					array('#GROUP_ID#', '#TASK_ID#'),
					array($arSection['XML_ID'], $ID),
					COption::GetOptionString('intranet', 'path_task_group_entry', '/extranet/workgroups/group/#GROUP_ID#/tasks/task/view/#TASK_ID#/', CExtranet::GetExtranetSiteID())
				);
			}
		}
	}
}
else
{
	$url = str_replace(
		array('#USER_ID#'),
		array(intval($url)),
		COption::GetOptionString('intranet', 'path_task_user', '/extranet/personal/user/#USER_ID#/tasks/', CExtranet::GetExtranetSiteID())
	);
}

$url = str_replace('.php/', '.php', $url);
if (substr($url, 0, 1) != '/') $url = '/'.$url;

LocalRedirect($url);
die();
?>