<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$bBulkMode = false;
if (isset($arParams['JUST_SHOW_BULK_TEMPLATE']) && ($arParams['JUST_SHOW_BULK_TEMPLATE'] === 'Y'))
	$bBulkMode = true;

?>
<script type="text/javascript">
	<?php
	if ( ! $bBulkMode )
	{
		?>
		app.pullDown({
			enable:   true,
			pulltext: '<?php echo GetMessageJS('MB_TASKS_TASK_DETAIL_PULLDOWN_PULL'); ?>',
			downtext: '<?php echo GetMessageJS('MB_TASKS_TASK_DETAIL_PULLDOWN_DOWN'); ?>',
			loadtext: '<?php echo GetMessageJS('MB_TASKS_TASK_DETAIL_PULLDOWN_LOADING'); ?>',
			action:   'RELOAD'
			//,callback: function(){ app.reload(); }
		});
		<?php
	}
	?>

	if ( ! window.MBTasks )
	{
		MBTasks = {
			lastTimeUIApplicationDidBecomeActiveNotification: 0,
			sessid:'<?php echo bitrix_sessid(); ?>',
			site:  '<?php echo CUtil::JSEscape(SITE_ID); ?>',
			lang:  '<?php echo CUtil::JSEscape(LANGUAGE_ID); ?>',
			userId: <?php echo (int) $arParams['USER_ID']; ?>,
			user_path_template: '<?php echo CUtil::JSEscape($arParams['PATH_TEMPLATE_TO_USER_PROFILE']); ?>',
			task_edit_path_template: '<?php echo CUtil::JSEscape($arParams['PATH_TO_TASKS_EDIT']); ?>',
			snmRouterAjaxUrl: '<?php echo CUtil::JSEscape($arParams['PATH_TO_SNM_ROUTER_AJAX']); ?>'
		};
	}

	if ( ! window.MBTasks.CPT )
		MBTasks.CPT = {};

	if ( ! window.MBTasks.CPT.view )
	{
		MBTasks.CPT.view = {
			LoadingImage: BX.create(
				'img', { props: { src: '/bitrix/templates/mobile_app/images/tasks/loader_small.gif' } }
			),
			arParams: {
				DATE_TIME_FORMAT: '<?php
					echo CUtil::JSEscape(htmlspecialcharsbx($arParams['DATE_TIME_FORMAT']));
				?>',
				PATH_TEMPLATE_TO_USER_PROFILE: '<?php
					echo CUtil::JSEscape(htmlspecialcharsbx($arParams['PATH_TEMPLATE_TO_USER_PROFILE']));
				?>',
				PATH_TO_FORUM_SMILE: '<?php
					echo CUtil::JSEscape(htmlspecialcharsbx($arParams['PATH_TO_FORUM_SMILE']));
				?>',
				AVA_WIDTH: '<?php
					echo CUtil::JSEscape(htmlspecialcharsbx($arParams['AVATAR_SIZE']['width']));
				?>',
				AVA_HEIGHT: '<?php
					echo CUtil::JSEscape(htmlspecialcharsbx($arParams['AVATAR_SIZE']['height']));
				?>'
			},
			dialogKey: '<?php echo CUtil::JSEscape(uniqid() . uniqid() . microtime(true)); ?>',
			UUIDs_processed: []	// list of events' UUIDs that was processed
		}
	}

	BX.message({
		MB_TASKS_TASK_DETAIL_PRIORITY_LOW:
			'<?php echo GetMessageJS('MB_TASKS_TASK_DETAIL_PRIORITY_LOW'); ?>',
		MB_TASKS_TASK_DETAIL_PRIORITY_AVERAGE:
			'<?php echo GetMessageJS('MB_TASKS_TASK_DETAIL_PRIORITY_AVERAGE'); ?>',
		MB_TASKS_TASK_DETAIL_PRIORITY_HIGH:
			'<?php echo GetMessageJS('MB_TASKS_TASK_DETAIL_PRIORITY_HIGH'); ?>',
		MB_TASKS_TASK_DETAIL_BTN_EDIT:
			'<?php echo GetMessageJS('MB_TASKS_TASK_DETAIL_BTN_EDIT'); ?>',
		MB_TASKS_TASK_DETAIL_BTN_REMOVE:
			'<?php echo GetMessageJS('MB_TASKS_TASK_DETAIL_BTN_REMOVE'); ?>',
		MB_TASKS_TASK_DETAIL_BTN_ACCEPT_TASK:
			'<?php echo GetMessageJS('MB_TASKS_TASK_DETAIL_BTN_ACCEPT_TASK'); ?>',
		MB_TASKS_TASK_DETAIL_BTN_START_TASK:
			'<?php echo GetMessageJS('MB_TASKS_TASK_DETAIL_BTN_START_TASK'); ?>',
		MB_TASKS_TASK_DETAIL_BTN_DECLINE_TASK:
			'<?php echo GetMessageJS('MB_TASKS_TASK_DETAIL_BTN_DECLINE_TASK'); ?>',
		MB_TASKS_TASK_DETAIL_BTN_RENEW_TASK:
			'<?php echo GetMessageJS('MB_TASKS_TASK_DETAIL_BTN_RENEW_TASK'); ?>',
		MB_TASKS_TASK_DETAIL_BTN_CLOSE_TASK:
			'<?php echo GetMessageJS('MB_TASKS_TASK_DETAIL_BTN_CLOSE_TASK'); ?>',
		MB_TASKS_TASK_DETAIL_BTN_PAUSE_TASK:
			'<?php echo GetMessageJS('MB_TASKS_TASK_DETAIL_BTN_PAUSE_TASK'); ?>',
		MB_TASKS_TASK_DETAIL_BTN_APPROVE_TASK:
			'<?php echo GetMessageJS('MB_TASKS_TASK_DETAIL_BTN_APPROVE_TASK'); ?>',
		MB_TASKS_TASK_DETAIL_BTN_REDO_TASK:
			'<?php echo GetMessageJS('MB_TASKS_TASK_DETAIL_BTN_REDO_TASK'); ?>',
		MB_TASKS_TASK_DETAIL_BTN_DELEGATE_TASK:
			'<?php echo GetMessageJS('MB_TASKS_TASK_DETAIL_BTN_DELEGATE_TASK'); ?>',
		MB_TASKS_TASK_DETAIL_TASK_WAS_REMOVED:
			'<?php echo GetMessageJS('MB_TASKS_TASK_DETAIL_TASK_WAS_REMOVED'); ?>',
		MB_TASKS_TASK_DETAIL_TASK_WAS_REMOVED_OR_NOT_ENOUGH_RIGHTS:
			'<?php echo GetMessageJS('MB_TASKS_TASK_DETAIL_TASK_WAS_REMOVED_OR_NOT_ENOUGH_RIGHTS'); ?>',
		MB_TASKS_TASK_DETAIL_NOTIFICATION_EXPIRED:
			'<?php echo GetMessageJS('MB_TASKS_TASK_DETAIL_NOTIFICATION_EXPIRED'); ?>',
		MB_TASKS_TASK_DETAIL_NOTIFICATION_STATE_SUPPOSEDLY_COMPLETED:
			'<?php echo GetMessageJS('MB_TASKS_TASK_DETAIL_NOTIFICATION_STATE_SUPPOSEDLY_COMPLETED'); ?>',
		MB_TASKS_TASK_DETAIL_USER_SELECTOR_BTN_CANCEL:
			'<?php echo GetMessageJS('MB_TASKS_TASK_DETAIL_USER_SELECTOR_BTN_CANCEL'); ?>',
		MB_TASKS_TASK_DETAIL_CONFIRM_REMOVE:
			'<?php echo GetMessageJS('MB_TASKS_TASK_DETAIL_CONFIRM_REMOVE'); ?>',
		MB_TASKS_TASK_DETAIL_SITE_ID:
			'<?php echo CUtil::JSEscape(htmlspecialcharsbx(SITE_DIR)); ?>'
	});

	ReadyDevice(
		function(){
			__MBTasks__mobile_tasks_view_init();

			<?php
			if ( ! $bBulkMode )
			{
				?>
				var task_id = <?php echo (int) $arResult['TASK']['ID']; ?>;

				var allowed_actions = [
					<?php
					$arTmp = array();
					if (is_array($arResult['TASK']['META::ALLOWED_ACTIONS']))
					{
						foreach ($arResult['TASK']['META::ALLOWED_ACTIONS'] as $value)
						{
							$arTmp[] = '{'
								. 'public_name: '
								. "'" . CUtil::JSEscape(htmlspecialcharsbx($value['public_name'])) . "'"
								. ', system_name: '
								. "'" . CUtil::JSEscape(htmlspecialcharsbx($value['system_name'])) . "'"
								. '}';
						}
						echo implode(',', $arTmp);
					}
					unset ($arTmp);
					?>
				];

				app.menuCreate({
					items: MBTasks.CPT.view.createMenu(task_id, allowed_actions)
				});

				app.addButtons({menuButton: {
					type:    'context-menu',
					style:   'custom',
					callback: function()
					{
						app.menuShow();
					}
				}});
				<?php
			}
			?>
		}
	);
</script>

<?php

$GLOBALS['APPLICATION']->SetPageProperty('BodyClass', 'task-card-page');

if ($bBulkMode)
{
}
else
{
	?>
	<script type="text/javascript">
		MBTasks.residentTaskId = <?php echo (int) $arResult['TASK']['ID']; ?>;
		BX.addCustomEvent(
			'onTasksDataChange', 
			function(data)
			{
				// ignore incorrect events
				if ( ! (
					(data.module_id) 
					&& (data.module_id === 'tasks')
					&& (data.emitter)
					&& (data.emitter !== 'tasks component mobile.tasks.detail')
				))
					return;

				// actualize data on page
				app.reload();
			}
		);

		BX.addCustomEvent(
			'onTaskEditPerfomed', 
			function(datum)
			{
				app.reload();
			}
		);

		BX.addCustomEvent(
			'onTaskActionBeforePerfome', 
			function(datum)
			{
				MBTasks.CPT.view.UUIDs_processed.push(datum.UUID);
			}
		);

		BX.addCustomEvent(
			'onTaskActionPerfomed', 
			function(datum)
			{
				// ignore incorrect events
				if ( ! (
					(datum.module_id) 
					&& (datum.module_id === 'tasks')
					&& (datum.action)
				))
				{
					return;
				}

				var data = datum.data;

				if (datum.action === 'remove')
				{
					if (datum.rc !== 'executed')
						return;

					var phrase = BX.message('MB_TASKS_TASK_DETAIL_TASK_WAS_REMOVED');

					document.getElementById('tasks-detail-card-container').innerHTML 
						= phrase.replace('#TASK_ID#', data.task_id);

					app.closeController({drop: true});
				}
				else if (datum.action === 'edit')
				{
					app.reload();
				}
				else
				{
					// Render new data
					if (data.commentsData)
						MBTasks.CPT.view.renderCommentsData(data.commentsData);

					if (data.detailsData)
						MBTasks.CPT.view.renderDetailData(data.detailsData);
				}
			}
		);
	</script>
	<?php
}
?>
<div id="tasks-detail-card-container-over">
<div class="task-block tasks-magic-Z" id="tasks-detail-card-container" onclick=""
style="position:relative;">
	<div class="task-block-title" id="tasks-view-title"><?php
		if (isset($arResult['TASK']['TITLE']))
			echo htmlspecialcharsbx($arResult['TASK']['TITLE']);
	?></div>
	<div class="task-block-description" id="tasks-view-description"><?php
		if (isset($arResult['TASK']['DESCRIPTION']))
			echo $arResult['TASK']['DESCRIPTION'];
	?></div>
	<div class="task-card-director-responsible">
		<div class="lenta-info-block info-block-blue">
			<div class="lenta-info-block-l">
				<div class="lenta-info-block-l-text"><?php echo GetMessage('MB_TASKS_TASK_DETAIL_ORIGINATOR'); ?></div>
				<div class="lenta-info-block-l-text"><?php echo GetMessage('MB_TASKS_TASK_DETAIL_RESPONSIBLE'); ?></div>
			</div>
			<div class="lenta-info-block-r">
				<div class="lenta-info-block-data">
					<?php
						$bOriginatorExists = false;
						if (isset($arResult['TASK']['CREATED_BY']))
							$bOriginatorExists = true;
					?>
					<div id="tasks-view-originator-avatar" 
						class="lenta-info-avatar avatar"
						><?php
						if (strlen($arResult['TASK']['META::ORIGINATOR_PHOTO_SRC']))
						{
							echo ' <script>BX(\'tasks-view-originator-avatar\').style.backgroundImage = "url(\'' 
								. CUtil::JSEscape($arResult['TASK']['META::ORIGINATOR_PHOTO_SRC']) 
								. '\')"</script>';
						}
						?></div>
					<div class="lenta-info-name">
						<a id="tasks-view-originator" href="<?php
							if ($bOriginatorExists)
							{
								echo str_replace(
									array('#USER_ID#', '#user_id#'),
									(int) $arResult['TASK']['CREATED_BY'],
									$arParams['PATH_TEMPLATE_TO_USER_PROFILE']
								);
							}
						?>" class="lenta-info-name-text"><?php
							if ($bOriginatorExists)
							{
								echo htmlspecialcharsbx(
									$arResult['TASK']['META::ORIGINATOR_FORMATTED_NAME']
								);
							}
						?></a>
						<div id="tasks-view-originator-work-position" class="lenta-info-name-description"><?php
							if ($bOriginatorExists)
							{
								echo htmlspecialcharsbx(
									(string) $arResult['TASK']['CREATED_BY_WORK_POSITION']
								);
							}
						?></div>
					</div>
				</div>
				<div class="lenta-info-block-data">
					<?php
						$bResponsibleExists = false;
						if (isset($arResult['TASK']['RESPONSIBLE_ID']))
							$bResponsibleExists = true;
					?>
					<div id="tasks-view-responsible-avatar" 
						class="lenta-info-avatar avatar"
						><?php
						if (strlen($arResult['TASK']['META::RESPONSIBLE_PHOTO_SRC']))
						{
							echo ' <script>BX(\'tasks-view-responsible-avatar\').style.backgroundImage = "url(\'' 
								. CUtil::JSEscape($arResult['TASK']['META::RESPONSIBLE_PHOTO_SRC']) 
								. '\')"</script>';
						}
						?></div>
					<div class="lenta-info-name">
						<a id="tasks-view-responsible" href="<?php
							if ($bResponsibleExists)
							{
								echo str_replace(
									array('#USER_ID#', '#user_id#'),
									(int) $arResult['TASK']['RESPONSIBLE_ID'],
									$arParams['PATH_TEMPLATE_TO_USER_PROFILE']
								);
							}
						?>" class="lenta-info-name-text"><?php
							if ($bResponsibleExists)
							{
								echo htmlspecialcharsbx(
									$arResult['TASK']['META::RESPONSIBLE_FORMATTED_NAME']
								);
							}
						?></a>
						<div id="tasks-view-responsible-work-position" class="lenta-info-name-description"><?php
							if ($bOriginatorExists)
							{
								echo htmlspecialcharsbx(
									(string) $arResult['TASK']['RESPONSIBLE_WORK_POSITION']
								);
							}
						?></div>
					</div>
				</div>
			</div>
		</div>
	</div>


	<div class="task-card-info">
		<div id="task-card-info-notification" 
			class="task-card-info-right"
		></div>
		<div class="task-card-info-row">
			<div class="task-card-info-left"><?php
				echo GetMessage('MB_TASKS_TASK_DETAIL_STATUS');
			?></div>
			<div id="tasks-view-status" class="task-card-info-right"><?php
				if (isset($arResult['TASK']['REAL_STATUS']))
				{
					$statusMsg = '';

					switch ($arResult['TASK']['REAL_STATUS'])
					{
						case CTasks::STATE_NEW:
							$statusMsg = GetMessage('MB_TASKS_TASK_DETAIL_STATUS_NEW');
						break;

						case CTasks::STATE_PENDING:
							$statusMsg = GetMessage('MB_TASKS_TASK_DETAIL_STATUS_ACCEPTED');
						break;

						case CTasks::STATE_IN_PROGRESS:
							$statusMsg = GetMessage('MB_TASKS_TASK_DETAIL_STATUS_IN_PROGRESS');
						break;

						case CTasks::STATE_SUPPOSEDLY_COMPLETED:
							$statusMsg = GetMessage('MB_TASKS_TASK_DETAIL_STATUS_WAITING');
						break;

						case CTasks::STATE_COMPLETED:
							$statusMsg = GetMessage('MB_TASKS_TASK_DETAIL_STATUS_COMPLETED');
						break;

						case CTasks::STATE_DEFERRED:
							$statusMsg = GetMessage('MB_TASKS_TASK_DETAIL_STATUS_DELAYED');
						break;

						case CTasks::STATE_DECLINED:
							$statusMsg = GetMessage('MB_TASKS_TASK_DETAIL_STATUS_DECLINED');
						break;

						default:
							$statusMsg = htmlspecialcharsbx($arResult['TASK']['REAL_STATUS']);
						break;
					}

					echo $statusMsg;
				}
			?></div>
		</div>
		<div class="task-card-info-row">
			<div class="task-card-info-left"><?php
				echo GetMessage('MB_TASKS_TASK_DETAIL_PRIORITY');
			?></div>
			<div id="tasks-view-priority" 
				class="task-card-info-right task-card-info-right-green"><?php
					if (isset($arResult['TASK']['PRIORITY']))
					{
						$color = 'black';
						$msg = '';
						switch ($arResult['TASK']['PRIORITY'])
						{
							case CTasks::PRIORITY_LOW:
								$color = 'grey';
								$msg = GetMessage('MB_TASKS_TASK_DETAIL_PRIORITY_LOW');
							break;

							case CTasks::PRIORITY_AVERAGE:
								$color = 'green';
								$msg = GetMessage('MB_TASKS_TASK_DETAIL_PRIORITY_AVERAGE');
							break;

							case CTasks::PRIORITY_HIGH:
								$color = 'red';
								$msg = GetMessage('MB_TASKS_TASK_DETAIL_PRIORITY_HIGH');
							break;

							default:
								$color = 'blue';
								$msg = htmlspecialcharsbx($arResult['TASK']['PRIORITY']);
							break;
						}
						echo '<span style="color:' . $color . '">' . $msg . '</span>';
					}
			?></div>
		</div>
		<div id="tasks-view-groupContainer" class="task-card-info-row"
			<?php
				$bTaskGroupExists = false;
				if (
					isset($arResult['TASK']['META:GROUP_NAME']) 
					&& ($arResult['TASK']['META:GROUP_NAME'])
				)
				{
					$bTaskGroupExists = true;
				}

				if ( ! $bTaskGroupExists )
					echo 'style="display:none;"'
			?>
			>
			<div class="task-card-info-left"><?php
				echo GetMessage('MB_TASKS_TASK_DETAIL_GROUP');
			?></div>
			<div id="tasks-view-groupName" class="task-card-info-right"><?php
				if ($bTaskGroupExists)
					echo $arResult['TASK']['META:GROUP_NAME'];
			?></div>
		</div>
		<div id="tasks-view-deadlineBlock" class="task-card-info-row"
			<?php
				$bTaskDeadlineExists = false;
				if (
					isset($arResult['TASK']['META:FORMATTED_DATA']['DATETIME_SEXY']) 
					&& $arResult['TASK']['META:FORMATTED_DATA']['DATETIME_SEXY']
				)
				{
					$bTaskDeadlineExists = true;
				}

				if ( ! $bTaskDeadlineExists )
					echo ' style="display:none;" ';
			?>
			>
			<div class="task-card-info-left"><?php
				echo GetMessage('MB_TASKS_TASK_DETAIL_DEADLINE');
			?></div>
			<div id="tasks-view-deadlineValue" 
				class="task-card-info-right task-card-info-right-red"><?php
					if ($bTaskDeadlineExists)
						echo $arResult['TASK']['META:FORMATTED_DATA']['DATETIME_SEXY'];
			?></div>
		</div>
	</div>



				<?php
				/*
					if (isset($arResult['TASK']['META:SOME_USERS_EXTRA_DATA'][(int) $arResult['TASK']['CREATED_BY']]['META:AVATAR_SRC']))
					{
						echo ' style="background:url(\'' 
							. htmlspecialcharsbx($arResult['TASK']['META:SOME_USERS_EXTRA_DATA'][(int) $arResult['TASK']['CREATED_BY']]['META:AVATAR_SRC'])
							. '\') no-repeat; background-size: 29px 29px;"';
					}

					if (isset($arResult['TASK']['META:SOME_USERS_EXTRA_DATA'][(int) $arResult['TASK']['RESPONSIBLE_ID']]['META:AVATAR_SRC']))
					{
						echo ' style="background:url(\'' 
							. htmlspecialcharsbx($arResult['TASK']['META:SOME_USERS_EXTRA_DATA'][(int) $arResult['TASK']['RESPONSIBLE_ID']]['META:AVATAR_SRC'])
							. '\') no-repeat; background-size: 29px 29px;"';
					}
				*/
					?>

	<?php
	$bFilesExists = false;
	if (
		(isset($arResult['TASK']['FILES']) || isset($arResult['TASK']['FORUM_FILES']))
		&& ($arResult['TASK']['FILES'] || $arResult['TASK']['FORUM_FILES'])
	)
	{
		$bFilesExists = true;
	}
	?>

	<div id="tasks-view-filesList" class="task-card-info task-card-info-file"
		<?php
			if ( ! $bFilesExists )
				echo ' style="display:none;" ';
		?>
		>
		<?php
			if ($bFilesExists)
			{
				$arData = array(
					'/bitrix/components/bitrix/tasks.task.detail/show_file.php?fid=' 
						=> $arResult['TASK']['FILES'],
					'/bitrix/components/bitrix/forum.interface/show_file.php?fid=' 
						=> $arResult['TASK']['FORUM_FILES']
				);

				foreach ($arData as $href => $arFiles)
				{
					if ( ! is_array($arFiles) )
						continue;

					foreach ($arFiles as $key => $arFile)
					{
						?>
						<div class="post-item-attached-file">
							<span style="display:none;">&nbsp;</span><a href='<?php 
								echo $href . $arFile['ID']; ?>' target='_blank'><?php 
								echo htmlspecialcharsbx($arFile['ORIGINAL_NAME']); 
							?></a><span> (<?php
								echo htmlspecialcharsbx($arFile['META::SIZE_FORMATTED']);
							?>)</span>
						</div>
						<?php
					}
				}
				unset ($arData);
			}
		?>
	</div>

	<?php
		$bAccomplicesExists = false;
		if (
			isset($arResult['TASK']['ACCOMPLICES']) 
			&& count($arResult['TASK']['ACCOMPLICES'])
		)
		{
			$bAccomplicesExists = true;
		}

		$bAuditorsExists = false;
		if (
			isset($arResult['TASK']['AUDITORS']) 
			&& count($arResult['TASK']['AUDITORS'])
		)
		{
			$bAuditorsExists = true;
		}
	?>


	<div class="task-card-info" id="tasks-view-membersBlock" 
		<?php
			if (
				( ! $bAccomplicesExists )
				&& ( ! $bAuditorsExists )
			)
			{
				echo ' style="display:none;" ';
			}
		?>
		>
		<div id="tasks-view-accomplicesBlock" class="task-card-info-row"
			<?php
				if ( ! $bAccomplicesExists )
					echo ' style="display:none;" ';
			?>
			>
			<div class="task-card-info-left"><?php
				echo GetMessage('MB_TASKS_TASK_DETAIL_ACCOMPLICES') . ': '; 
			?></div>
			<div id="tasks-view-accomplicesList" 
				class="task-card-info-right task-card-info-right-blue"
				>
				<?php
					if ($bAccomplicesExists)
					{
						$arTmpHtmlChunks = array();
						if (is_array($arResult['TASK']['ACCOMPLICES']))
						{
							foreach ($arResult['TASK']['ACCOMPLICES'] as $memberId)
							{
								$pathToUserPage = str_replace(
									array('#USER_ID#', '#user_id#'),
									(int) $memberId,
									$arParams['PATH_TEMPLATE_TO_USER_PROFILE']
								);

								$userName = $arResult['TASK']['META:SOME_USERS_EXTRA_DATA'][$memberId]['META:NAME_FORMATTED'];

								$arTmpHtmlChunks[] = '<a href="' . $pathToUserPage 
									. '" class="task-card-link">'
									. htmlspecialcharsbx($userName)
									. '</a>';
							}
						}
						echo implode(', ', $arTmpHtmlChunks);
					}
				?>
			</div>
		</div>
		<div id="tasks-view-auditorsBlock" 
			class="task-card-info-row"
			<?php
				if ( ! $bAuditorsExists )
					echo ' style="display:none;" ';
			?>
			>
			<div class="task-card-info-left"><?php
				echo GetMessage('MB_TASKS_TASK_DETAIL_AUDITORS') . ': ';
			?></div>
			<div id="tasks-view-auditorsList" 
				class="task-card-info-right task-card-info-right-blue"
				>
				<?php
					if ($bAuditorsExists)
					{
						$arTmpHtmlChunks = array();
						if (is_array($arResult['TASK']['AUDITORS']))
						{
							foreach ($arResult['TASK']['AUDITORS'] as $memberId)
							{
								$pathToUserPage = str_replace(
									array('#USER_ID#', '#user_id#'),
									(int) $memberId,
									$arParams['PATH_TEMPLATE_TO_USER_PROFILE']
								);

								$userName = $arResult['TASK']['META:SOME_USERS_EXTRA_DATA'][$memberId]['META:NAME_FORMATTED'];

								$arTmpHtmlChunks[] = '<a href="' . $pathToUserPage 
									. '" class="task-card-link">'
									. htmlspecialcharsbx($userName)
									. '</a>';
							}
							echo implode(', ', $arTmpHtmlChunks);
						}
					}
				?>
			</div>
		</div>
	</div>

	<?php 
	/*
	<div>
		<input type="button" onclick="MBTasks.pageOpened();" value="MBTasks.pageOpened();"><br><br>
		<span onclick="MBTasks.pageOpened();">MBTasks.pageOpened();</span><br><br>
		<a href="javascript:void(0);"
			onclick="
			app.showModalDialog({
				url: '<?php
					echo str_replace(
						'#TASK_ID#',
						(int) $arResult['TASK']['ID'],
						$arParams['PATH_TO_TASKS_EDIT']
					);
				?>'
			});">Edit task</a>
	</div>
	<hr>
	*/

$clsStatus = '';

if (CModule::IncludeModule('tasks'))
{
	if ($arResult['TASK']['STATUS'] == CTasks::METASTATE_EXPIRED)
		$clsStatus = 'task-list-status-overdue';
	elseif (
		($arResult['TASK']['STATUS'] == CTasks::STATE_SUPPOSEDLY_COMPLETED)
		|| ($arResult['TASK']['STATUS'] == CTasks::STATE_DECLINED)
	)
	{
		$clsStatus = 'task-list-status-waiting';
	}
	elseif (
		($arResult['TASK']['STATUS'] == CTasks::STATE_NEW) 
		|| ($arResult['TASK']['STATUS'] == CTasks::METASTATE_VIRGIN_NEW)
	)
	{
		$clsStatus = 'task-list-status-new';
	}
}
?>
<div id="tasks-view-status-corner" class="task-view-status <?php echo $clsStatus; ?>"></div>
</div><?php // end of tasks-detail-card-container ?>
<div id="tasks-view-loader-area" style="text-align:center;">
</div>

<?php
$templateName = 'bottom';

if ($bBulkMode)
	$templateName = 'bottom_bulk';

$arRes = $APPLICATION->IncludeComponent(
	'bitrix:mobile.tasks.topic.reviews',
	$templateName,
	array(
		'TASK'                       => $arResult['TASK'],
		//'CACHE_TYPE'                 => $arParams['CACHE_TYPE'],
		//'CACHE_TIME'                 => $arParams['CACHE_TIME'],
		'PATH_TO_SNM_ROUTER_AJAX' => $arParams["PATH_TO_SNM_ROUTER_AJAX"],
		'PATH_TO_SMILE'              => $arParams['PATH_TO_FORUM_SMILE'],
		'FORUM_ID'                   => $arResult['FORUM_ID'],
		'TASK_ID'                    => $arResult['TASK']['ID'],
		'USER_ID'                    => $arParams['USER_ID'],
		'SHOW_RATING'                => 'Y',
		'RATING_TYPE'                => 'like',
		'URL_TEMPLATES_PROFILE_VIEW' => $arParams['PATH_TEMPLATE_TO_USER_PROFILE'],
		'PAGE_NAVIGATION_TEMPLATE'   => 'arrows',
		"NAME_TEMPLATE"              => $arParams["NAME_TEMPLATE"],
		'TASK_LAST_VIEWED_DATE'      => $arResult['LAST_VIEWED_DATE'],
		'AVATAR_SIZE'                => $arParams['AVATAR_SIZE'],
		'DATE_TIME_FORMAT'           => $arParams['DATE_TIME_FORMAT'],
		'SHOW_TEMPLATE'              => 'Y',
		'JUST_SHOW_BULK_TEMPLATE'    => $bBulkMode ? 'Y' : 'N'
	)
);

?>
	<div class="task-comments-block task-comments-block-selected tasks-magic-Z" 
	id="task-comments-block" onclick=""
	>
		<?php
		echo $arRes['HTML']['COMMENTS'];
		?>
	</div>
</div>
<?php
echo $arRes['HTML']['SEND_BTN'];
