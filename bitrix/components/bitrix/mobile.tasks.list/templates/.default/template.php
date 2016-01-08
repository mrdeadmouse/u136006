<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if ( ! class_exists('CTasksMobileTasksListNS') )
{
	class CTasksMobileTasksListNS extends CTasksMobileTasksListNsAbstract
	{
		protected static function renderOnclick($bShow, $arTask, $nameTemplate, 
			$changesCountSinceLastView, $userHrefTemplate, $taskViewHrefTemplate,
			$userId, $dateTimeFormat, $groupName, $snmrouterPath, $gear)
		{
			ob_start();

			if ( ! in_array($gear, array('2', 'D', 'OD'), true) )
			{
				?>
				return((function(){
					try
					{
						app.openNewPage('<?php
							echo str_replace(
								array('#TASK_ID#', '#TASK_ID#'),
								(int) $arTask['ID'],
								$taskViewHrefTemplate . '&t=' . time()
							);						
						?>');
					}
					catch(e)
					{
					}

					return(false);
				})());
				<?php
			}
			else
			{
				?>
				return((function(){
					try
					{
						app.openNewPage(
							'<?php echo $snmrouterPath . '&t=' . time(); ?>',
							MBTasks.CPT.List.tasksBaseDataCache['t' + <?php echo (int) $arTask['ID']; ?>]
						);
					}
					catch(e)
					{
					}

					return(false);
				})());
				<?php
			}

			return(ob_get_clean());
		}


		public static function renderTaskItem ($bShow, $arTask, $nameTemplate, 
			$changesCountSinceLastView, $userHrefTemplate, $taskViewHrefTemplate,
			$userId, $dateTimeFormat, $groupName, $snmrouterPath, $gear)
		{
			if ( ! $bShow )
				ob_start();

			$clsPriority = '';	// This is default for normal priority
			if ($arTask['PRIORITY'] == CTasks::PRIORITY_LOW)
				$clsPriority = 'task-list-priority-low';
			elseif ($arTask['PRIORITY'] == CTasks::PRIORITY_HIGH)
				$clsPriority = 'task-list-priority-high';

			$clsStatus = '';
			if ($arTask['STATUS'] == CTasks::METASTATE_EXPIRED)
				$clsStatus = 'task-list-status-overdue';
			elseif (
				($arTask['STATUS'] == CTasks::STATE_SUPPOSEDLY_COMPLETED)
				|| ($arTask['STATUS'] == CTasks::STATE_DECLINED)
			)
			{
				$clsStatus = 'task-list-status-waiting';
			}
			elseif (
				($arTask['STATUS'] == CTasks::STATE_NEW) 
				|| ($arTask['STATUS'] == CTasks::METASTATE_VIRGIN_NEW)
			)
			{
				$clsStatus = 'task-list-status-new';
			}
			elseif (
				($arTask['STATUS'] == CTasks::STATE_COMPLETED)
			)
			{
				$clsStatus = 'task-list-status-completed';
			}

			$onclick = self::renderOnclick($bShow, $arTask, $nameTemplate, 
				$changesCountSinceLastView, $userHrefTemplate, 
				$taskViewHrefTemplate, $userId, $dateTimeFormat, 
				$groupName, $snmrouterPath, $gear);

			$idPrfx = "task-item-" . (int) $arTask['ID'];

			?>
			<div id="task-list-block-<?php echo $arTask['ID']; ?>" 
				class="task-list-block <?php echo $clsPriority; ?>" 
				onclick="<?php echo $onclick; ?>">
				<div id="<?php echo $idPrfx; ?>-title" 
					class="task-list-block-title">
					<?php echo htmlspecialcharsbx($arTask['TITLE']); ?>
				</div>
				<div class="task-list-block-names"
					><a id="<?php echo $idPrfx; ?>-originator"
						class="task-list-block-names-link" 
						href="<?php 
							echo 'javascript:void(0);';
							/*
							echo str_replace(
								array('#USER_ID#', '#user_id#'),
								(int) $arTask['CREATED_BY'],
								$userHrefTemplate
							);
							*/
						?>"><?php
							echo htmlspecialcharsbx($arTask['META::ORIGINATOR_FORMATTED_NAME']);
					?></a><span class="task-list-separate"></span
					><a id="<?php echo $idPrfx; ?>-responsible"
						class="task-list-block-names-link"
						href="<?php
							echo 'javascript:void(0);';
							/*
							echo str_replace(
								array('#USER_ID#', '#user_id#'),
								(int) $arTask['RESPONSIBLE_ID'],
								$userHrefTemplate
							);
							*/
						?>" ><?php
							echo htmlspecialcharsbx($arTask['META::RESPONSIBLE_FORMATTED_NAME']);
					?></a
				></div>
				<div id="<?php echo $idPrfx; ?>-deadline" 
					class="task-list-deadline"><?php
					if (MakeTimeStamp($arTask['DEADLINE']) > 86400)
						echo htmlspecialcharsbx($arTask['META:DEADLINE_FORMATTED']);
					else
						echo '&nbsp;';
				?></div>
				<div class="task-list-priority"></div>
				<div id="task-list-block-status-<?php echo $arTask['ID']; ?>" 
					class="task-list-status <?php echo $clsStatus; ?>"></div>
				<?php
				if (count($arTask['FILES']))
				{
					?>
					<div class="task-list-attached-files"></div>
					<?php
				}
				?>
				<div class="task-list-arrow"></div>
			</div>
			<?php

			if ( ! $bShow )
				return (ob_get_clean());
		}


		public static function renderGroupItem ($bShow, $arGroup, $pathTemplate, $userId)
		{
			$path = str_replace(
				array('#GROUP_ID#', '#group_id#'),
				(int) $arGroup['ID'],
				$pathTemplate
			);
			$path = str_replace(
				array('#USER_ID#', '#user_id#'),
				(int) $userId,
				$path
			);

			$clsStatus = '';

			if (in_array((int) CTasks::METASTATE_EXPIRED, $arGroup['META:TASKS_STATUSES'], true))
				$clsStatus .= ' task-label-red';		// overdued
			
			if (
				in_array(
					(int) CTasks::STATE_SUPPOSEDLY_COMPLETED, 
					$arGroup['META:TASKS_STATUSES'], 
					true
				)
				|| in_array(
					(int) CTasks::STATE_DECLINED, 
					$arGroup['META:TASKS_STATUSES'], 
					true
				)
			)
			{
				$clsStatus .= ' task-label-orange';	// waiting
			}

			if (
				in_array(
					(int) CTasks::STATE_NEW, 
					$arGroup['META:TASKS_STATUSES'], 
					true
				)
				|| in_array(
					(int) CTasks::METASTATE_VIRGIN_NEW, 
					$arGroup['META:TASKS_STATUSES'], 
					true
				)
			)
			{
				$clsStatus .= ' task-label-green';	// new
			}

			if (strlen($clsStatus))
				$clsStatus .= ' task-folder-block-big';	// it needs, when statuses marked

			$elemId = 'tasks_list_group_id-' . (int) $arGroup['ID'];

			?>
			<div id="<?php echo $elemId; ?>" 
				class="task-folder-block  <?php echo $clsStatus; ?>"
				onclick="app.openNewPage('<?php echo CUtil::JSEscape(htmlspecialcharsbx($path)); ?>');"
				>
				<div class="task-folder-left"></div>
				<div class="task-folder-corner"></div>
				<div class="task-folder-right"></div>
				<div class="task-folder-repeat"></div>
				<div class="task-folder-header"></div>
				<div class="task-folder-right-repeat"></div>
				<div class="task-folder-text-wrap"><span class="task-folder-text"><?php
					echo htmlspecialcharsbx($arGroup['NAME']);
				?></span></div>
				<div class="task-folder-index-wrap"><span class="task-folder-index"><?php
					echo (int) $arGroup['META:TASKS_IN_GROUP'];
				?></span></div>
				<div class="task-folder-arrow"></div>
				<div class="task-folder-label label-red"></div>
				<div class="task-folder-label label-orange"></div>
				<div class="task-folder-label label-green"></div>
			</div>
			<?php
		}


		public static function getLiveFeedGroupHref ($groupId)
		{
			return (SITE_DIR.'mobile/log/?group_id=' . (int) $groupId);
		}
	}
}

$bIsGroupsShowed = (
	CTasksMobileTasksListNS::IsGroupListMode()	// group list mode enabled
	&& ($arParams['GROUP_ID'] === false)		// and group is not selected
);

if ($bIsGroupsShowed)
	$GLOBALS['APPLICATION']->SetPageProperty('BodyClass', 'tasks-folders-page');
else
	$GLOBALS['APPLICATION']->SetPageProperty('BodyClass', 'task-list-page');
?>
<script>
app.pullDown({
	enable:   true,
	pulltext: '<?php echo GetMessageJS('MB_TASKS_TASKS_LIST_PULLDOWN_PULL'); ?>',
	downtext: '<?php echo GetMessageJS('MB_TASKS_TASKS_LIST_PULLDOWN_DOWN'); ?>',
	loadtext: '<?php echo GetMessageJS('MB_TASKS_TASKS_LIST_PULLDOWN_LOADING'); ?>',
	action: 'RELOAD',
	callback: function()
	{
		app.reload();
	}
});

BX.message({
	MB_TASKS_TASKS_LIST_MENU_GOTO_FILTER: 
		'<?php echo GetMessageJS('MB_TASKS_TASKS_LIST_MENU_GOTO_FILTER'); ?>',
	MB_TASKS_TASKS_LIST_MENU_CREATE_NEW_TASK: 
		'<?php echo GetMessageJS('MB_TASKS_TASKS_LIST_MENU_CREATE_NEW_TASK'); ?>',
	PATH_TO_TASKS_FILTER: 
		'<?php echo CUtil::JSEscape($arParams['PATH_TO_TASKS_FILTER']); ?>',
	PATH_TO_TASKS_EDIT: 
		'<?php echo CUtil::JSEscape($arParams['PATH_TO_TASKS_EDIT']); ?>',
	PATH_TO_TASKS_CREATE: 
		'<?php echo CUtil::JSEscape(str_replace(
			array('#TASK_ID#', '#task_id#'),
			0,	// create new task
			$arParams['PATH_TO_TASKS_EDIT']
		));
	?>',
	PATH_TEMPLATE_TO_USER_PROFILE: 
		'<?php echo CUtil::JSEscape($arParams['PATH_TEMPLATE_TO_USER_PROFILE']); ?>'
});

/*
document.addEventListener("DOMContentLoaded", function() {
	
	if (false)
	{
		BX.bind(BX('tasks-all-items'), "click", function() { BX('asdfsdfgdsf').innerHTML = ++MBTaskscounter;});
	}
	else
	{
		var container = BX('tasks-all-items').childNodes[1];
c onsole.log(container.childNodes.length);
		for (var i = 0, length = container.childNodes.length; i < length; i++)
		{
			var div = container.childNodes[i];
c onsole.log(div.nodeName.toLowerCase());
			if (div.nodeName.toLowerCase() != "div")
				continue;



			new FastButton(
				div,
				function(event) {
					BX('asdfsdfgdsf').innerHTML = ++MBTaskscounter;

					
					/*
					var lockState = MBTasks.CPT.List.AcquireLockOpenNewPage();

					if (lockState === 'lock_acquired')
					{
						event.target.onclick();

						MBTasks.CPT.List.ReleaseLockOpenNewPage();			
					}* /
					
				}
			);
		}
	}
}, false);
*/

if ( ! window.MBTasks )
{
	MBTasks = {
		lastTimeUIApplicationDidBecomeActiveNotification: 0,
		userId: <?php echo (int) $arParams['USER_ID']; ?>,
		sessid: '<?php echo bitrix_sessid(); ?>',
		site:   '<?php echo CUtil::JSEscape(SITE_ID); ?>',
		siteDir: '<?=CUtil::JSEscape(SITE_DIR)?>',
		lang:   '<?php echo CUtil::JSEscape(LANGUAGE_ID); ?>',
		DATE_TIME_FORMAT:
			'<?php
				echo CUtil::JSEscape(htmlspecialcharsbx($arParams['DATE_TIME_FORMAT']));
		?>',
		gear: '<?php echo CUtil::JSEscape(htmlspecialcharsbx($arParams['GEAR']));?>'
	};
}

if ( ! window.MBTasks.CPT )
	MBTasks.CPT = {};

(function(){
	if (window.MBTasks.CPT.List)
		return;

	MBTasks.CPT.List = {
		lockOpenNewPage: 0,
		FILTER_ID: '<?php echo CUtil::JSEscape(htmlspecialcharsbx($arResult['FILTER_ID'])); ?>',
		snmrouterPath: '<?php
			echo CUtil::JSEscape(htmlspecialcharsbx(
				str_replace(
					array('#USER_ID#', '#user_id#'),
					(int) $arParams['USER_ID'],
					$arParams['PATH_TO_SNM_ROUTER']
				) . '&t=' . time()
			)); ?>',
		taskViewHrefTemplate: '<?php
			echo CUtil::JSEscape(htmlspecialcharsbx(
				$arParams['PATH_TO_TASKS_TASK']
			));?>',
		UUIDs_processed: [],	// list of events' UUIDs that was processed
		tasksListGroupsShowed: <?php
			if ($bIsGroupsShowed)
				echo 'true';
			else
				echo 'false';
		?>,
		groupSelected: <?php
			if ($arParams['GROUP_ID'] === false)
				echo 'false';
			else
				echo (int) $arParams['GROUP_ID'];
		?>,
		tasksBaseDataCache: {
			<?php
			if (
				( ! $bIsGroupsShowed ) 
				&& is_array($arResult['TASKS']) 
				&& count($arResult['TASKS'])
			)
			{
				$arItems = array();
				foreach ($arResult['TASKS'] as $key => $arTask)
				{
					$groupName = '';

					if (
						isset($arResult['GROUPS'][$arTask['GROUP_ID']]['NAME'])
						&& (strlen($arResult['GROUPS'][$arTask['GROUP_ID']]['NAME']) > 0)
					)
					{
						$groupName = $arResult['GROUPS'][$arTask['GROUP_ID']]['NAME'];
					}
					ob_start();

					echo 't' . (int) $arTask['ID'];
					?>: {
							params_emitter: 'tasks_list',
							gear: '<?php
								echo CUtil::JSEscape(
									$gear);
							?>',
							matrix: 'view',
							task_id: <?php
								echo (int) $arTask['ID'];
							?>,
							title: '<?php
								echo CUtil::JSEscape(
									$arTask['TITLE']);
							?>',
							description: '<?php
								echo CUtil::JSEscape(
									$arTask['DESCRIPTION']);
							?>',
							responsible_id: <?php
								echo (int) $arTask['RESPONSIBLE_ID'];
							?>,
							responsible_formatted_name: '<?php
								echo CUtil::JSEscape(
									(string) $arTask['META::RESPONSIBLE_FORMATTED_NAME']);
							?>',
							responsible_work_position: '<?php
								echo CUtil::JSEscape(
									(string) $arTask['RESPONSIBLE_WORK_POSITION']);
							?>',
							responsible_photo_src: '<?php
								echo CUtil::JSEscape(
									(string) $arTask['META::RESPONSIBLE_PHOTO_SRC']);
							?>',
							originator_id: <?php
								echo (int) $arTask['CREATED_BY'];
							?>,
							originator_formatted_name: '<?php
								echo CUtil::JSEscape(
									(string) $arTask['META::ORIGINATOR_FORMATTED_NAME']);
							?>',
							originator_work_position: '<?php
								echo CUtil::JSEscape(
									(string) $arTask['CREATED_BY_WORK_POSITION']);
							?>',
							originator_photo_src: '<?php
								echo CUtil::JSEscape(
									(string) $arTask['META::ORIGINATOR_PHOTO_SRC']);
							?>',
							priority: <?php
								echo (int) $arTask['PRIORITY'];
							?>,
							status_id: <?php
								echo (int) $arTask['STATUS'];
							?>,
							status_formatted_name: '<?php
								echo CUtil::JSEscape(
									$arTask['META::STATUS_FORMATTED_NAME']);
							?>',
							group_id: <?php
								echo (int) $arTask['GROUP_ID'];
							?>,
							group_name: '<?php
								echo CUtil::JSEscape(
									$groupName);
							?>',
							deadline: '<?php
								echo CUtil::JSEscape(
									$arTask['DEADLINE']);
							?>',
							deadline_formatted: '<?php
								echo CUtil::JSEscape(
									$arTask['META:DEADLINE_FORMATTED']);
							?>',
							comments_count: <?php
								echo (int) $arTask['COMMENTS_COUNT'];
							?>
						}
					<?php
					$arItems[] = ob_get_clean();
				}

				echo implode(', ', $arItems);
			}
			?>
		}
	};

	BX.ready(function(){
		__MBTasksCPTListInit();
	});
})();
</script>
<?php

if (CModule::IncludeModule('pull'))
{
	//s oundex('CPullWatch::Add()');
	$rc = CPullWatch::Add($arParams['USER_ID'], 'TASKS_GENERAL_' . $arParams['USER_ID']);
	//s oundex($rc, 'TASKS_GENERAL_' . $arParams['USER_ID']);
}
?>
<div id="asdfsdfgdsf" class="task-title">
	<?php
	if ($arResult['FILTER_NAME'])
		echo htmlspecialcharsbx($arResult['FILTER_NAME']);

	// don't show button, when we are in group list mode and some group selected
	if (
		! (
			CTasksMobileTasksListNS::IsGroupListMode()
			&& ($arParams['GROUP_ID'] !== false)
		)
	)
	{
		$ynTmp = 'Y';
		if ($bIsGroupsShowed)
			$ynTmp = 'N';	

		$onclick = htmlspecialcharsbx(
			$GLOBALS['APPLICATION']->GetCurPageParam(
				'GROUP_LIST_MODE=' . $ynTmp, 
				array('GROUP_LIST_MODE'), 
				false
			)
		);
		?>
		<div class="task-title-folder-btn" 
			ontouchstart="BX.addClass(this, 'task-title-folder-btn-press');"
			ontouchend="BX.removeClass(this, 'task-title-folder-btn-press');"
			onclick="app.reload({url: '<?php echo $onclick; ?>'});"
			>
			<div class="task-title-folder-btn-icon"></div>
		</div>
		<?php
	}
	/*
	// No design ready for current group name
	if ($arResult['SELECTED_GROUP_NAME'] !== false)
	{
		if ($arResult['FILTER_NAME'])
			echo " - ";
		else
			echo "<br>\n";

		echo '&laquo;'
			. htmlspecialcharsbx($arResult['SELECTED_GROUP_NAME'])
			. '&raquo;';
	}
	*/
	?>
</div>

<?php
if ($bIsGroupsShowed)
{
?>
<div id="tasks-all-items" class="tasks-folders-wrap">
<?php
}
else
{
?>
<div id="tasks-all-items" class="task-list-blocks-shadow">
	<div id="tasks-all-items-tasks" class="task-list-blocks-wrap">
<?php
}

	$arPaths = array(
		'PATH_TO_TASKS_TASK'   => $arParams['PATH_TO_TASKS_TASK'],
		'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE']
	);

	// if group list mode and not requested some group (cause if requested, than must be forced plain list mode)
	if ($bIsGroupsShowed)
	{
		$userTasksListPathTemplate = $arParams['PATH_TO_USER_TASKS'];

		if (strpos($userTasksListPathTemplate, '?') === false)
			$userTasksListPathTemplate .= '?';
		else
			$userTasksListPathTemplate .= '&';

		$userTasksListPathTemplate .= 'GROUP_ID=#GROUP_ID#';

		foreach ($arResult['GROUPS'] as $groupId => $arGroup)
		{
			CTasksMobileTasksListNS::renderGroupItem(
				true,			// $bShow, 
				$arGroup,
				$userTasksListPathTemplate,
				$arParams['USER_ID']
			);
		}

		unset($userTasksListPathTemplate);
	}
	else
	{
		$bNoTasks = true;

		// Render tasks witout groups
		if (is_array($arResult['TASKS']) && count($arResult['TASKS']))
		{
			$bNoTasks = false;

			foreach ($arResult['TASKS'] as $key => $arTask)
			{
				$changesCountSinceLastView = 0;
				if (isset($arResult['UPDATES_COUNT'][$arTask['ID']]))
					$changesCountSinceLastView = $arResult['UPDATES_COUNT'][$arTask['ID']];

				$groupName = '';

				if (
					isset($arResult['GROUPS'][$arTask['GROUP_ID']]['NAME'])
					&& (strlen($arResult['GROUPS'][$arTask['GROUP_ID']]['NAME']) > 0)
				)
				{
					$groupName = $arResult['GROUPS'][$arTask['GROUP_ID']]['NAME'];
				}

				$snmrouterPath = str_replace(
					array('#USER_ID#', '#user_id#'),
					(int) $arParams['USER_ID'],
					$arParams['PATH_TO_SNM_ROUTER']
				);

				CTasksMobileTasksListNS::renderTaskItem(
					true,			// $bShow, 
					$arTask, 
					$arParams['NAME_TEMPLATE'],
					$changesCountSinceLastView,
					$arParams['PATH_TEMPLATE_TO_USER_PROFILE'],
					$arParams['PATH_TO_TASKS_TASK'],	// $taskViewHrefTemplate
					$arParams['USER_ID'],				// $userId
					$arParams['DATE_TIME_FORMAT'],
					$groupName,
					$snmrouterPath,
					$arParams['GEAR']
				);
			}
		}

		?>
		<div id="tasks-list-no-task-notificator" 
			style="margin:10px; <?php if (!$bNoTasks) echo 'display:none'; ?>;">
			<?php echo GetMessage('MB_TASKS_TASKS_LIST_NO_TASKS'); ?>
		</div>
		<?php
	}

if ($bIsGroupsShowed)
{
?>
</div>
<?php
}
else
{
?>
	</div>
</div>
<?php
}
