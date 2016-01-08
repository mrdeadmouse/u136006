<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$cmpId = RandString();
?>
<?if (!$arResult['noWrap']):?>
<div class="bp-livefeed-wrapper">
<?endif;?>
<div class="pb-popup-mobile" id="<?=$cmpId?>_wf_livefeed">
	<div class="bp-post bp-lent">
		<div class="bp-short-process-inner">
			<?$APPLICATION->IncludeComponent(
				"bitrix:bizproc.workflow.faces",
				"",
				array(
					"WORKFLOW_ID" => $arParams["WORKFLOW_ID"]
				),
				$component
			);
			?>
		</div>
		<span id="<?=$cmpId?>_user_status_yes" class="bp-status-ready" style="display: none">
			<span><?=GetMessage('BPATL_USER_STATUS_YES')?></span>
		</span>
		<span id="<?=$cmpId?>_user_status_no" class="bp-status-cancel" style="display: none">
			<span><?=GetMessage('BPATL_USER_STATUS_NO')?></span>
		</span>
		<span id="<?=$cmpId?>_user_status_ok" class="bp-status-ready" style="display: none">
			<span><?=GetMessage('BPATL_USER_STATUS_OK')?></span>
		</span>
		<span id="<?=$cmpId?>_wf_status" class="bp-status" style="display: none">
			<span class="bp-status-inner"><span><?=$arResult["WORKFLOW_STATE_INFO"]['STATE_TITLE']?></span></span>
		</span>
			<?foreach ($arResult['TASKS']['RUNNING'] as $task):?>
				<div id="<?=$cmpId?>_task_buttons_<?=$task['ID']?>" class="bp-btn-panel" style="display: none">
			<span class="bp-btn-panel-inner">
			<? if ($task['IS_INLINE'] == 'Y'):
				foreach ($task['BUTTONS'] as $control):
					$class = $control['TARGET_USER_STATUS'] == CBPTaskUserStatus::No? 'decline' : 'accept';
					$props = CUtil::PhpToJSObject(array(
						'TASK_ID' => $task['ID'],
						$control['NAME'] => $control['VALUE'],
						'sessid' => bitrix_sessid()
					));
					?>
					<a href="javascript:void(0)" onclick="return function(){
							app.showPopupLoader();
							BX.ajax({
								method: 'POST',
								dataType: 'json',
								url: '/mobile/?mobile_action=bp_do_task',
								data: <?=$props?>,
								onsuccess: function()
								{
									BX.ajax({
										'method': 'POST',
										'dataType': 'html',
										'url': '/mobile/?mobile_action=bp_livefeed_action',
										'data':  {WORKFLOW_ID: '<?=$arParams['WORKFLOW_ID']?>'},
										'onsuccess': function(HTML)
										{
											app.hidePopupLoader();
											BX('<?=$cmpId?>_wf_livefeed').parentNode.innerHTML = HTML;
										},
										onfailure: function()
										{
											app.hidePopupLoader();
											alert('request error :(');
										}
									});
									setTimeout(function(){
										app.onCustomEvent('bpTaskComplete', {taskId: <?=(int)$task['ID']?>});
									}, 500);
								},
								onfailure: function()
								{
									app.hidePopupLoader();
									alert('request error :(');
								}
							});
							return false;
						}()" class="webform-small-button bp-small-button webform-small-button-<?=$class?>">
						<span class="bp-button-icon"></span>
						<span class="bp-button-text"><?=$control['TEXT']?></span>
					</a>
				<?endforeach;
			else:?>
				<a href="/mobile/bp/detail.php?task_id=<?=$task['ID']?>" class="webform-small-button bp-small-button webform-small-button-blue">
					<span class="bp-button-text"><?=GetMessage("BPATL_BEGIN")?></span>
				</a>
			<?endif?>
			</span>
				</div>
			<?endforeach;?>

		<?foreach ($arResult['TASKS']['RUNNING'] as $task):?>
			<div id="<?=$cmpId?>_task_block_<?=$task['ID']?>" class="bp-task-block" style="display: none">
				<span class="bp-task-block-title"><?=GetMessage("BPATL_TASK_TITLE")?>: </span>
				<?=$task['NAME']?>
				<? if ($task['DESCRIPTION']):?>
					<br/>
						<?=nl2br($task['DESCRIPTION'])?>
				<?endif?>
			</div>
		<?endforeach;?>
		<?
		$jsTasks = array('RUNNING' => array(), 'COMPLETED' => array());
		foreach ($arResult['TASKS']['RUNNING'] as $task)
		{
			$jsTask = array(
				'ID' => $task['ID'],
				'USERS' => array()
			);
			foreach ($task['USERS'] as $u)
			{
				$jsTask['USERS'][] = array(
					'USER_ID' => $u['USER_ID'],
					'STATUS' => $u['STATUS']
				);
			}
			$jsTasks['RUNNING'][] = $jsTask;
		}
		?>
		<script>
			BX.ready(function() {
				var cmpId = '<?=$cmpId?>',
					tasks = <?=CUtil::PhpToJSObject($jsTasks)?>,
					userId = '<?=$arResult['USER_ID']?>',
					statusWaiting = '<?=CBPTaskUserStatus::Waiting?>',
					statusYes = '<?=CBPTaskUserStatus::Yes?>',
					statusNo = '<?=CBPTaskUserStatus::No?>',
					statusOk = '<?=CBPTaskUserStatus::Ok?>',
					userStatus = false,
					taskId = false;

				if (BX.message('USER_ID'))
					userId = BX.message('USER_ID');

				var getUserFromTask = function (task, userId)
				{
					for (var i = 0, l = task.USERS.length; i < l; ++i)
					{
						if (task.USERS[i]['USER_ID'] == userId)
							return task.USERS[i];
					}
					return null;
				};

				if (tasks['RUNNING'].length)
				{
					for (var i = 0, l = tasks.RUNNING.length; i < l; ++i)
					{
						var task = tasks.RUNNING[i];
						var user = getUserFromTask(task, userId);
						if (user)
						{
							if (user.STATUS > statusWaiting)
								userStatus = user.STATUS;
							else
							{
								userStatus = false;
								taskId = task.ID;
								BX(cmpId+'_task_buttons_'+task.ID).style.display = '';
								BX(cmpId+'_task_block_'+task.ID).style.display = '';
								break;
							}
						}
					}
				}
				if (userStatus !== false)
				{
					switch (userStatus)
					{
						case statusYes:
							BX(cmpId+'_user_status_yes').style.display = '';
							break;
						case statusNo:
							BX(cmpId+'_user_status_no').style.display = '';
							break;
						default:
							BX(cmpId+'_user_status_ok').style.display = '';
							break;
					}
				}
				BX(cmpId+'_wf_status').style.display = (userStatus || taskId)? 'none' : '';

				BX.addCustomEvent('bpTaskComplete', function(params) {
					if (tasks['RUNNING'].length)
					{
						for (var i = 0, l = tasks.RUNNING.length; i < l; ++i)
						{
							if (params.taskId == tasks.RUNNING[i].ID)
							{
								BX.ajax({
									'method': 'POST',
									'dataType': 'html',
									'url': '/mobile/?mobile_action=bp_livefeed_action',
									'data':  {WORKFLOW_ID: '<?=$arParams['WORKFLOW_ID']?>'},
									'onsuccess': function(HTML)
									{
										BX('<?=$cmpId?>_wf_livefeed').parentNode.innerHTML = HTML;
									}
								});
								break;
							}
						}
					}
				});
			});
		</script>
	</div>
</div>
<?if (!$arResult['noWrap']):?>
	</div>
<?endif;?>