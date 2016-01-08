<?
IncludeModuleLangFile(__FILE__);

class CBPViewHelper
{
	private static $cachedTasks = array();

	public static function RenderUserSearch($ID, $searchInputID, $dataInputID, $componentName, $siteID = '', $nameFormat = '', $delay = 0)
	{
		$ID = strval($ID);
		$searchInputID = strval($searchInputID);
		$dataInputID = strval($dataInputID);
		$componentName = strval($componentName);

		$siteID = strval($siteID);
		if($siteID === '')
		{
			$siteID = SITE_ID;
		}

		$nameFormat = strval($nameFormat);
		if($nameFormat === '')
		{
			$nameFormat = CSite::GetNameFormat(false);
		}

		$delay = intval($delay);
		if($delay < 0)
		{
			$delay = 0;
		}

		echo '<input type="text" id="', htmlspecialcharsbx($searchInputID) ,'" style="width:200px;"   >',
		'<input type="hidden" id="', htmlspecialcharsbx($dataInputID),'" name="', htmlspecialcharsbx($dataInputID),'" value="">';

		echo '<script type="text/javascript">',
		'BX.ready(function(){',
		'BX.CrmUserSearchPopup.deletePopup("', $ID, '");',
		'BX.CrmUserSearchPopup.create("', $ID, '", { searchInput: BX("', CUtil::JSEscape($searchInputID), '"), dataInput: BX("', CUtil::JSEscape($dataInputID),'"), componentName: "', CUtil::JSEscape($componentName),'", user: {} }, ', $delay,');',
		'});</script>';

		$GLOBALS['APPLICATION']->IncludeComponent(
			'bitrix:intranet.user.selector.new',
			'',
			array(
				'MULTIPLE' => 'N',
				'NAME' => $componentName,
				'INPUT_NAME' => $searchInputID,
				'SHOW_EXTRANET_USERS' => 'NONE',
				'POPUP' => 'Y',
				'SITE_ID' => $siteID,
				'NAME_TEMPLATE' => $nameFormat
			),
			null,
			array('HIDE_ICONS' => 'Y')
		);
	}

	public static function getWorkflowTasks($workflowId, $withUsers = false, $extendUserInfo = false)
	{
		$withUsers = $withUsers ? 1 : 0;
		$extendUserInfo = $extendUserInfo ? 1 : 0;

		if (!isset(self::$cachedTasks[$workflowId][$withUsers][$extendUserInfo]))
		{
			$tasks = array('COMPLETED' => array(), 'RUNNING' => array());
			$ids = array();
			$taskIterator = CBPTaskService::GetList(
				array('MODIFIED' => 'DESC'),
				array('WORKFLOW_ID' => $workflowId),
				false,
				false,
				array('ID', 'MODIFIED', 'NAME', 'DESCRIPTION', 'PARAMETERS', 'STATUS', 'IS_INLINE', 'ACTIVITY')
			);
			while ($task = $taskIterator->getNext())
			{
				$key = $task['STATUS'] == CBPTaskStatus::Running ? 'RUNNING' : 'COMPLETED';
				$tasks[$key][] = $task;
				$ids[] = $task['ID'];
			}
			if ($withUsers && sizeof($ids))
			{
				$taskUsers = \CBPTaskService::getTaskUsers($ids);
				self::joinUsersToTasks($tasks['COMPLETED'], $taskUsers, $extendUserInfo);
				$tasks['RUNNING_ALL_USERS'] = self::joinUsersToTasks($tasks['RUNNING'], $taskUsers, $extendUserInfo);
			}
			$tasks['COMPLETED_CNT'] = sizeof($tasks['COMPLETED']);
			$tasks['RUNNING_CNT'] = sizeof($tasks['RUNNING']);

			self::$cachedTasks[$workflowId][$withUsers][$extendUserInfo] = $tasks;
		}

		return self::$cachedTasks[$workflowId][$withUsers][$extendUserInfo];
	}

	protected static function joinUsersToTasks(&$tasks, &$taskUsers, $extendUserInfo = false)
	{
		$allUsers = array();
		foreach ($tasks as &$t)
		{
			$t['USERS'] = array();
			$t['USERS_CNT'] = 0;
			if (isset($taskUsers[$t['ID']]))
			{
				foreach ($taskUsers[$t['ID']] as $u)
				{
					if ($extendUserInfo)
					{
						if (empty($u['FULL_NAME']))
							$u['FULL_NAME'] = self::getUserFullName($u);
						if (empty($u['PHOTO_SRC']))
							$u['PHOTO_SRC'] = self::getUserPhotoSrc($u);
					}
					$t['USERS'][] = $u;
					$t['USERS_CNT'] = sizeof($t['USERS']);
					$allUsers[] = $u;
				}
			}
		}
		return $allUsers;
	}

	public static function getUserPhotoSrc(array $user)
	{
		if (empty($user['PERSONAL_PHOTO']))
			return '';
		$arFileTmp = \CFile::ResizeImageGet(
			$user["PERSONAL_PHOTO"],
			array('width' => 58, 'height' => 58),
			\BX_RESIZE_IMAGE_EXACT,
			false
		);
		return $arFileTmp['src'];
	}

	public static function getUserFullName(array $user)
	{
		return \CUser::FormatName(\CSite::GetNameFormat(false), $user, true, false);
	}
}