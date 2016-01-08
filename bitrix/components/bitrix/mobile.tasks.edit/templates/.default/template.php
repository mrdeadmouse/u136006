<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$GLOBALS['APPLICATION']->SetPageProperty('BodyClass', 'task-form-page');

if ($arResult['TASK']['ID'] === 0)
	$btnSaveName = 'MB_TASKS_TASK_EDIT_BTN_CREATE';
else
	$btnSaveName = 'MB_TASKS_TASK_EDIT_BTN_SAVE';

?>
<script>
	app.pullDown({
		enable:   true,
		pulltext: '<?php echo GetMessageJS('MB_TASKS_TASK_EDIT_PULLDOWN_PULL'); ?>',
		downtext: '<?php echo GetMessageJS('MB_TASKS_TASK_EDIT_PULLDOWN_DOWN'); ?>',
		loadtext: '<?php echo GetMessageJS('MB_TASKS_TASK_EDIT_PULLDOWN_LOADING'); ?>',
		callback: function()
		{
			app.reload();
		}
	});

	BX.message({
		MB_TASKS_TASK_EDIT_NO_TITLE: '<?php
			echo GetMessageJS('MB_TASKS_TASK_EDIT_NO_TITLE');
		?>',
		MB_TASKS_TASK_EDIT_BTN_REMOVE: '<?php
			echo GetMessageJS('MB_TASKS_TASK_EDIT_BTN_REMOVE');
		?>'
	})

	app.enableScroll(true);

	if ( ! window.MBTasks )
	{
		MBTasks = {
			lastTimeUIApplicationDidBecomeActiveNotification: 0,
			sessid:'<?php echo bitrix_sessid(); ?>',
			site:  '<?php echo CUtil::JSEscape(SITE_ID); ?>',
			lang:  '<?php echo CUtil::JSEscape(LANGUAGE_ID); ?>',
			userId: <?php echo (int) $arParams['USER_ID']; ?>
		};
	}

	if ( ! window.MBTasks.CPT )
		MBTasks.CPT = {};

	if ( ! window.MBTasks.CPT.edit )
	{
		MBTasks.CPT.edit = {
			LoadingImage: BX.create(
				'img', { props: { src: '/bitrix/templates/mobile_app/images/tasks/loader_small.gif' } }
			),
			arParams: {
				'DATE_TIME_FORMAT': '<?php
					echo CUtil::JSEscape(htmlspecialcharsbx($arParams['DATE_TIME_FORMAT']));
				?>'
			},
			userPathTemplate: '<?php
				echo CUtil::JSEscape($arParams['PATH_TEMPLATE_TO_USER_PROFILE']);
			?>'
		}

		MBTasks.CPT.edit.dialogKey = '<?php echo CUtil::JSEscape($_GET['dialogKey']); ?>';

		<?php
		$arData = array(
			'accomplices' => $arResult['TASK']['ACCOMPLICES'],
			'auditors' => $arResult['TASK']['AUDITORS']
		);

		$arUserData = array();
		foreach ($arData as $jsVarName => $arData)
		{
			$arUsersJson = array();

			if (count($arData))
			{
				$rsUsersList = CUser::GetList(
					$b = 'LOGIN', 
					$o = 'ASC', 
					array('ID' => implode('|', $arData))
				);

				while($arUserData = $rsUsersList->GetNext())
				{
					ob_start();
					?>
					u<?php echo (int) $arUserData['ID']; ?>: {
						name: '<?php
							echo CUtil::JSEscape(CUser::FormatName(
								$arParams['NAME_TEMPLATE'], 
								array(
									'NAME'        => $arUserData['NAME'], 
									'LAST_NAME'   => $arUserData['LAST_NAME'], 
									'SECOND_NAME' => $arUserData['SECOND_NAME'], 
									'LOGIN'       => $arUserData['LOGIN']
								),
								true,
								true	// use htmlspecialcharsbx
							)); ?>',
						work_position: '<?php
							echo CUtil::JSEscape(htmlspecialcharsbx(
								$arUserData['WORK_POSITION']
							)); ?>',
						type: '<?php echo $jsVarName; ?>',
						user_id: <?php echo (int) $arUserData['ID']; ?>,
						node: null
					}
					<?php
					$arUsersJson[] = ob_get_clean();
				}
			}
			?>
			MBTasks.CPT.edit.<?php echo $jsVarName; ?> = {
				<?php echo implode(',', $arUsersJson); ?>
			};

			<?php
		}
		unset($arData, $arUsersJson, $arUserData);
		?>
	}

/*
	MBTasks.CPT.edit.dialogKey = false;

	app.getPageParams({
		callback: function(pageParams)
		{
			MBTasks.CPT.edit.dialogKey = pageParams.dialogKey;
		}
	});
*/

	function __MB_TASKS_TASK_EDIT_renderMember(memberData)
	{
		/*
			<div class="task-form-participant-row">
				<div class="fl-delete-right-btn-wrap" data-removable-btn="true">
					<?php
						echo GetMessage('MB_TASKS_TASK_EDIT_BTN_REMOVE');
					?>
					<div class="fl-delete-right-btn-block">
						<div class="fl-delete-right-btn"><?php
							echo GetMessage('MB_TASKS_TASK_EDIT_BTN_REMOVE');
						?></div>
					</div>
				</div>
				<div class="task-form-participant-row-name">
					<a class="task-form-participant-row-link"></a>
				</div>
				<div class="task-form-participant-row-post"></div>
				<div class="task-form-participant-btn" 
				data-removable-icon="true"><i 
				class="task-form-participant-btn-i"></i></div>
			</div>
		*/

		var node = BX.create('DIV', {
			props: { className: 'task-form-participant-row'}
		});

		var children = [
			BX.create('DIV', {
				props: { className: 'fl-delete-right-btn-wrap'},
				children: [
					BX.create('SPAN', {
						text: BX.message('MB_TASKS_TASK_EDIT_BTN_REMOVE')
					}),
					BX.create('DIV', {
						props: { className: 'fl-delete-right-btn-block'},
						events: {
							click: (function(node, memberData){
								return (function(){
									node.classList.toggle('fl-block-close');

									window.setTimeout(
										function(){
											var parNode = node.parentNode.childNodes;
											BX.remove(node);

											var key = 'u' + memberData.user_id;

											if (memberData.type == 'accomplices')
												delete MBTasks.CPT.edit.accomplices[key];
											else
												delete MBTasks.CPT.edit.auditors[key];
										},
										10		// 275
									);
								});
							})(node, memberData)
						},
						children: [
							BX.create('DIV', {
								props: { className: 'fl-delete-right-btn'},
								html: BX.message('MB_TASKS_TASK_EDIT_BTN_REMOVE')
							})
						]
					})
				]
			}),
			BX.create('DIV', {
				props: { className: 'task-form-participant-row-name'},
				children: [
					BX.create('A', {
						props: { className: 'task-form-participant-row-link'},
						text: memberData.name
					})
				]
			}),
			BX.create('DIV', {
				props: { className: 'task-form-participant-row-post'},
				html: memberData.work_position
			}),
			BX.create('DIV', {
				props: { className: 'task-form-participant-btn'},
				children: [
					BX.create('I', {
						props: { className: 'task-form-participant-btn-i'}
					})
				]
			})
		];

		for (var key in children)
			node.appendChild(children[key]);

		return (node);
	}


	function __MB_TASKS_TASK_EDIT_selectGroup(data)
	{
		var selectedGroup = 0;
		if (BX('GROUP_ID') && (BX('GROUP_ID').value > 0))
			selectedGroup = parseInt(BX('GROUP_ID').value);

		app.openTable({
			callback: function(data)
			{
				if ( ! (data && data.b_groups && data.b_groups[0]) )
					return;

				//var user_path_template = '<?php echo CUtil::JSEscape($arParams['PATH_TEMPLATE_TO_USER_PROFILE']); ?>';

				var group = data.b_groups[0];
				var group_id = group['ID'].toString();

				//var user_path = user_path_template.replace('#USER_ID#', user_id);
				//var user_path = user_path.replace('#user_id#', user_id);

				//if (BX('tasks-group-link'))
					//BX('tasks-group-link').href = user_path;

				if (BX('tasks-group-name'))
					BX('tasks-group-name').innerHTML = group['NAME'];

				if (BX('task-group-avatar'))
					BX('task-group-avatar').style.backgroundImage = 'url(\'' + group['IMAGE'] + '\')';

				BX('GROUP_ID').value = group_id;
			},
			url: '<?=SITE_DIR?>/mobile/?mobile_action=task_get_group&USER_ID=<?php echo $arParams['USER_ID']; ?>&action=get_group_list_where_user_is_member&lang=<?php echo LANGUAGE_ID; ?>&sessid=<?php echo bitrix_sessid(); ?>',
			markmode: true,
			multiple: false,
			return_full_mode: true,
			//user_all: true,
			showtitle: true,
			modal: true,
			selected: {b_groups:[selectedGroup]},
			alphabet_index: true,
			//outsection: false,
			okname: '<?php echo CUtil::JSEscape(GetMessage('MB_TASKS_TASK_EDIT_BTN_SELECT')); ?>',
			cancelname: '<?php echo CUtil::JSEscape(GetMessage('MB_TASKS_TASK_EDIT_BTN_CANCEL')); ?>'
		});
	}


	function __MB_TASKS_TASK_EDIT_showUserSelector(callback, bMultiple)
	{
		app.openTable({
			callback: function(data)
			{
				callback(data);
			},
			url: '<?=SITE_DIR?>mobile/index.php?mobile_action=get_user_list',
			markmode: true,
			multiple: bMultiple,
			return_full_mode: true,
			skipSpecialChars : true,
			modal: true,
			alphabet_index: true,
			outsection: false,
			okname: '<?php echo CUtil::JSEscape(GetMessage('MB_TASKS_TASK_EDIT_BTN_SELECT')); ?>',
			cancelname: '<?php echo CUtil::JSEscape(GetMessage('MB_TASKS_TASK_EDIT_BTN_CANCEL')); ?>'
		});
	}
	

	function __MB_TASKS_TASK_EDIT_selectResponsible(data)
	{
		var bMultiple = false;
		__MB_TASKS_TASK_EDIT_showUserSelector(function(data){
			if ( ! (data && data.a_users && data.a_users[0]) )
				return;

			var user = data.a_users[0];
			var user_id = user['ID'].toString();

			if (BX('task-responsible-name'))
				BX('task-responsible-name').innerHTML = BX.util.htmlspecialchars(user['NAME']);

			if (BX('task-responsible-avatar'))
				BX('task-responsible-avatar').style.backgroundImage = 'url(\'' + user['IMAGE'] + '\')';

			BX('RESPONSIBLE_ID').value = user_id;
		}, bMultiple);
	}

	
	function __MB_TASKS_TASK_EDIT_addAccomplices()
	{
		var bMultiple = true;
		__MB_TASKS_TASK_EDIT_showUserSelector(function(data){
			if ( ! (data && data.a_users && data.a_users.length) )
				return;

			var work_position = '';

			for (var key in data.a_users)
			{
				var user = data.a_users[key];
				var user_id = user['ID'].toString();
				var dataKey = 'u' + user_id;

				// Skip, if user already in list
				if (MBTasks.CPT.edit.accomplices.hasOwnProperty(dataKey))
					continue;

				if (typeof(user['WORK_POSITION']) === 'string')
					work_position = user['WORK_POSITION'];

				if (user['WORK_DEPARTMENTS'].length > 0)
				{
					if (work_position.length > 0)
						work_position = work_position + ', ';

					work_position = work_position + user['WORK_DEPARTMENTS'].join(', ');
				}

				MBTasks.CPT.edit.accomplices[dataKey] = {
					name: user['NAME'],
					work_position: work_position,
					type: 'accomplices',
					user_id: parseInt(user['ID']),
					node: null
				};

				var node = __MB_TASKS_TASK_EDIT_renderMember(MBTasks.CPT.edit.accomplices[dataKey]);
				MBTasks.CPT.edit.accomplices[dataKey].node = node;
				BX('tasks-accomplices-block').appendChild(node);
			}
		}, bMultiple);
	}


	function __MB_TASKS_TASK_EDIT_addAuditors()
	{
		var bMultiple = true;
		__MB_TASKS_TASK_EDIT_showUserSelector(function(data){
			if ( ! (data && data.a_users && data.a_users.length) )
				return;

			var work_position = '';

			for (var key in data.a_users)
			{
				var user = data.a_users[key];
				var user_id = user['ID'].toString();
				var dataKey = 'u' + user_id;

				// Skip, if user already in list
				if (MBTasks.CPT.edit.auditors.hasOwnProperty(dataKey))
					continue;

				if (typeof(user['WORK_POSITION']) === 'string')
					work_position = user['WORK_POSITION'];

				if (user['WORK_DEPARTMENTS'].length > 0)
				{
					if (work_position.length > 0)
						work_position = work_position + ', ';

					work_position = work_position + user['WORK_DEPARTMENTS'].join(', ');
				}

				MBTasks.CPT.edit.auditors[dataKey] = {
					name: user['NAME'],
					work_position: work_position,
					type: 'auditors',
					user_id: parseInt(user['ID']),
					node: null
				};

				var node = __MB_TASKS_TASK_EDIT_renderMember(MBTasks.CPT.edit.auditors[dataKey]);
				MBTasks.CPT.edit.auditors[dataKey].node = node;
				BX('tasks-auditors-block').appendChild(node);
			}
		}, bMultiple);
	}


	MBTasks.CPT.edit.onFailure = function()
	{
	}


	MBTasks.CPT.edit.onSuccess = function(datum, postData, ajaxUrl)
	{
		if ( ! datum )
			return;

		if (
			(datum == '{"status":"failed"}') 
			|| (
				datum.status
				&& (datum.status == 'failed')
			)
		)
		{
			app.BasicAuth({
				success: (function(postData, ajaxUrl){
					return function(auth_data)
					{
						MBTasks.sessid = auth_data.sessid_md5;
						BX.ajax({
							timeout:   30,
							method:   'POST',
							dataType: 'json',
							url:       ajaxUrl,
							data:      postData,
							onsuccess: function(reply){
								try { MBTasks.CPT.edit.onSuccess(reply, postData, ajaxUrl); }
								catch(e) {
									//alert('Exception-1z! ' + e.name);
								}
							},
							onfailure: function(){
								try	{ MBTasks.CPT.edit.onFailure(); }
								catch(e) {
									//alert('Exception-2z! ' + e.name);
								}
							}
						});
					}
				})(postData, ajaxUrl),
				failure: function() { 
					try	{ MBTasks.CPT.edit.onFailure(); }
					catch(e) {
						//alert('Exception-3z! ' + e.name);
					}
				}
			});
		}
		else if (datum && datum.action_done && datum.rc)	// Do job
		{
			var eventData = {
				module_id: 'tasks',
				emitter:   'tasks component mobile.tasks.edit',
				action:     datum.action_done,
				rc:         datum.rc
			}

			app.onCustomEvent(
				'onTaskEditPerfomed',
				eventData
			);

		}

		app.hidePopupLoader();
		app.closeModalDialog();
		//app.closeController({drop: true});
	}


	function __MB_TASKS_TASK_EDIT_saveTask(ynBBCode)
	{
		if (BX('TITLE').value.length == 0)
		{
			app.alert({
				text   : BX.message('MB_TASKS_TASK_EDIT_NO_TITLE'),
				button : "OK"
			});
			return;
		}

		app.removeButtons({
			position: 'right'
		});

		var PRIORITY = BX('PRIORITY');

		var accomplices = [];
		var auditors    = [];

		for (dataKey in MBTasks.CPT.edit.accomplices)
			accomplices.push(parseInt(MBTasks.CPT.edit.accomplices[dataKey].user_id))

		for (dataKey in MBTasks.CPT.edit.auditors)
			auditors.push(parseInt(MBTasks.CPT.edit.auditors[dataKey].user_id))

		if (accomplices.length == 0)
			accomplices = -1;	// no accomplices

		if (auditors.length == 0)
			auditors = -1;	// no auditors

		var taskData = {
			TASK_ID:        BX('TASK_ID').value,
			CREATED_BY:     BX('CREATED_BY').value,
			RESPONSIBLE_ID: BX('RESPONSIBLE_ID').value,
			GROUP_ID:       BX('GROUP_ID').value,
			DESCRIPTION:   ((ynBBCode === 'Y') ? BX('DESCRIPTION').value : BX('DESCRIPTION').value.replace(/\r?\n|\r/g, '<br>')),
			TITLE:          BX('TITLE').value,
			DEADLINE:       BX('DEADLINE').innerHTML,
			PRIORITY:       PRIORITY.options[PRIORITY.selectedIndex].value,
			ACCOMPLICES:    accomplices,
			AUDITORS:       auditors
		};

		if (BX('TASK_ID').value > 0)
		{
			app.onCustomEvent(
				'onTaskSaveBefore',
				{
					module_id: 'tasks',
					dialogKey:  MBTasks.CPT.edit.dialogKey,
					delayFire:  557,
					taskData:   taskData
				}
			);

			//app.closeController({drop: true});
			app.closeModalDialog();

			return;
		}
		else
		{
			// UUID generation
			var UUID = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(
				/[xy]/g, 
				function(c) {
					var r = Math.random()*16|0, v = c == 'x' ? r : (r&0x3|0x8);
					return v.toString(16);
				}
			);

			// Prevent duplicate processing of events caused by push&pull
			app.onCustomEvent(
				'onTaskActionBeforePerfome',
				{UUID: UUID}
			);

			BX.onCustomEvent(
				'onTaskActionBeforePerfome',
				[{UUID: UUID}]
			);

			taskData['META::EVENT_GUID'] = UUID;
		}

		app.showPopupLoader();

		var postData = {
			'sessid':      MBTasks.sessid,
			'site':        MBTasks.site,
			'lang':        MBTasks.lang,
			'user_id':     MBTasks.userId,
			'action':     'save task',
			'tasksData':   taskData,
			'DATE_TIME_FORMAT': MBTasks.CPT.edit.arParams.DATE_TIME_FORMAT
		};

		var ajaxUrl = "<?echo $arParams["PATH_TO_SNM_ROUTER_AJAX"]?>";

		BX.ajax({
			timeout: 30,
			method: 'POST',
			dataType: 'json',
			data: postData,
			url: ajaxUrl,
			onsuccess: (function(postData, ajaxUrl){
				return function(data)
				{
					try
					{ 
						MBTasks.CPT.edit.onSuccess(data, postData, ajaxUrl);
					}
					catch(e) {
						//alert('Exception-4bz! ' + e.name);
					}
				}
			})(postData, ajaxUrl),
			onfailure: function()
			{
				try
				{
					MBTasks.CPT.edit.onFailure();
				}
				catch(e)
				{
					//alert('Exception-5z! ' + e.name);
				}
			}
		});
	}

	MBTasks.CPT.edit.initMenu = function()
	{
		app.addButtons({
			backButton: 
			{
				type:     'right_text',
				style:    'custom',
				position: 'left',
				name:     '<?php echo GetMessageJS('MB_TASKS_TASK_EDIT_BTN_CANCEL'); ?>',
				callback: function()
				{
					app.closeModalDialog();
				}
			},
			saveButton: 
			{
				type:     'right_text',
				style:    'custom',
				name:     '<?php echo GetMessageJS($btnSaveName); ?>',
				callback: function()
				{
					BX('TITLE').blur();
					BX('DESCRIPTION').blur();
					__MB_TASKS_TASK_EDIT_saveTask('<?php echo $arResult['TASK']['DESCRIPTION_IN_BBCODE']; ?>');
				}
			}
		});
	}

	MBTasks.CPT.edit.pageOpened = function()
	{
		MBTasks.CPT.edit.initMenu();
	}

	MBTasks.CPT.edit.pageOpened();

	BX.addCustomEvent(
		'onOpenPageBefore', 
		function() { MBTasks.CPT.edit.pageOpened() }
	);

	BX.ready(function(){
		//parNode.style.display = "none";
		for (var key in MBTasks.CPT.edit.accomplices)
		{
			var node = __MB_TASKS_TASK_EDIT_renderMember(MBTasks.CPT.edit.accomplices[key]);
			MBTasks.CPT.edit.accomplices[key].node = node;
			BX('tasks-accomplices-block').appendChild(node);
		}

		for (var key in MBTasks.CPT.edit.auditors)
		{
			var node = __MB_TASKS_TASK_EDIT_renderMember(MBTasks.CPT.edit.auditors[key]);
			MBTasks.CPT.edit.auditors[key].node = node;
			BX('tasks-auditors-block').appendChild(node);
		}
	});
</script>
<div id="tasks-edit-card-container" onclick="">
<?php
if ($arResult['TASK']['ID'] === 0)
{
	?>
	<div class="task-title"><?php
		echo GetMessage('MB_TASKS_TASK_EDIT_TITLE_NEW_TASK');
	?></div>
	<?php
}
else
{
	?>
	<div class="task-title"><?php
		echo str_replace(
			'#TASK_ID#',
			(int) $arResult['TASK']['ID'],
			GetMessage('MB_TASKS_TASK_EDIT_TITLE_EDIT_TASK')
		);
	?></div>
	<?php
}
?>
	<form method="post" onsubmit="return false<?//to prevent breaking of mobile app when submitting form?>">
		<?php echo bitrix_sessid_post(); ?>
		<input type="hidden" id="TASK_ID" name="TASK_ID" value="<?php echo (int) $arResult['TASK']['ID']; ?>">
		<input type="hidden" id="CREATED_BY" name="CREATED_BY" value="<?php echo (int) $arResult['TASK']['CREATED_BY']; ?>">
		<input type="hidden" name="RESPONSIBLE_ID" id="RESPONSIBLE_ID" value="<?php echo (int) $arResult['TASK']['RESPONSIBLE_ID']; ?>">
		<input type="hidden" name="GROUP_ID" id="GROUP_ID" value="<?php echo (int) $arResult['TASK']['GROUP_ID']; ?>">

		<div class="task-form-textar-wrap">
			<input type="text" id="TITLE" name="TITLE" class="task-form-input" 
				placeholder="<?php echo GetMessage('MB_TASKS_TASK_EDIT_PLACEHOLDER_FOR_TITLE'); ?>"
				value="<?php 
					echo htmlspecialcharsbx($arResult['TASK']['TITLE']); 
				?>"
			/>
			<textarea id="DESCRIPTION" class="task-form-textarea" 
				placeholder="<?php echo GetMessage('MB_TASKS_TASK_EDIT_PLACEHOLDER_FOR_DESCRIPTION'); ?>"
			><?php
				echo htmlspecialcharsbx(
					str_replace(
						array('<br>', '<br />', '<BR>', '<BR />'),
						"\r\n",
						$arResult['TASK']['DESCRIPTION']
					)					
				);
			?></textarea>
		</div>

		<div class="task-form-main-block">
			<div class="task-form-row" onclick="__MB_TASKS_TASK_EDIT_selectResponsible()">
				<?php
				/*
				<div id="task-responsible-avatar" class="avatar" 
					<?php
						if (isset($arResult['TASK']['META:SOME_USERS_EXTRA_DATA'][(int) $arResult['TASK']['RESPONSIBLE_ID']]['META:AVATAR_SRC']))
						{
							echo ' style="background:url(\'' 
								. htmlspecialcharsbx($arResult['TASK']['META:SOME_USERS_EXTRA_DATA'][(int) $arResult['TASK']['RESPONSIBLE_ID']]['META:AVATAR_SRC'])
								. '\') no-repeat; background-size: 29px 29px;"';
						}
						?>
				></div>
				*/
				?>
				<span class="task-form-row-left"><?php
					echo GetMessage('MB_TASKS_TASK_EDIT_RESPONSIBLE');
				?></span><span class="task-form-row-right"><a 
						href="javascript:void(0);"
						class="task-form-link"
						id="task-responsible-name"
					><?php
						echo CUser::FormatName(
							$arParams['NAME_TEMPLATE'], 
							array(
								'NAME'        => $arResult['TASK']['RESPONSIBLE_NAME'], 
								'LAST_NAME'   => $arResult['TASK']['RESPONSIBLE_LAST_NAME'], 
								'SECOND_NAME' => $arResult['TASK']['RESPONSIBLE_SECOND_NAME'], 
								'LOGIN'       => $arResult['TASK']['RESPONSIBLE_LOGIN']
								),
							true,
							true	// use htmlspecialcharsbx
						);
				?></a></span>
				<div class="task-form-arrow"></div>
			</div>
			<div class="task-form-row">
				<span class="task-form-row-left"><?php
					echo GetMessage('MB_TASKS_TASK_EDIT_DEADLINE');
				?></span><span 
						class="task-form-row-right"
					><span id="DEADLINE"
						onclick="app.showDatePicker({
							start_date: '<?php
								if ($arResult['TASK']['DEADLINE'])
								{
									echo CAllDatabase::FormatDate(
											$arResult['TASK']['DEADLINE'], 
											FORMAT_DATETIME,
											'YYYY-MM-DD HH:MI:SS'
										);
								}
								else
								{
									echo date('Y') . '-' . date('m') . '-' . date('d')
										. ' ' . date('H') . ':' . date('i') . ':00';
								}
							?>',
							format: 'y-M-d H:m:s',
							type: 'datetime',
							callback: function(res)
							{
								BX('DEADLINE').innerHTML = res;
							}
						});"><?php
						if ($arResult['TASK']['DEADLINE'])
						{
							echo CAllDatabase::FormatDate(
								$arResult['TASK']['DEADLINE'], 
								FORMAT_DATETIME,
								'YYYY-MM-DD HH:MI:SS'
							);
						}
						else
							echo GetMessage('MB_TASKS_TASK_EDIT_BTN_SELECT');
					?></span>
					</span>
			</div>
			<div class="task-form-row">
				<span class="task-form-row-left"><?php
					echo GetMessage('MB_TASKS_TASK_EDIT_PRIORITY');
				?></span><span class="task-form-row-right">
					<select id="PRIORITY" name="PRIORITY">
						<?php
						$arPriorities = array(
							CTasks::PRIORITY_LOW     => GetMessage('MB_TASKS_TASK_EDIT_PRIORITY_LOW'),
							CTasks::PRIORITY_AVERAGE => GetMessage('MB_TASKS_TASK_EDIT_PRIORITY_AVERAGE'),
							CTasks::PRIORITY_HIGH    => GetMessage('MB_TASKS_TASK_EDIT_PRIORITY_HIGH')
						);

						foreach ($arPriorities as $priorityId => $priorityName)
						{
							$selected = '';
							if ($arResult['TASK']['PRIORITY'] == $priorityId)
								$selected = ' selected="selected" ';

							echo '<option value="' . $priorityId . '" ' . $selected . '>' 
								. htmlspecialcharsbx($priorityName) . '</option>';
						}
						?>
					</select>
				</span>
			</div>
			<div class="task-form-row" onclick="__MB_TASKS_TASK_EDIT_selectGroup()">
				<span class="task-form-row-left"><?php
					echo GetMessage('MB_TASKS_TASK_EDIT_GROUP');
				?></span><span class="task-form-row-right" id="tasks-group-name"><?php
					if ($arResult['TASK']['META:GROUP_NAME'])
						echo htmlspecialcharsbx($arResult['TASK']['META:GROUP_NAME']);
					else
						echo GetMessage('MB_TASKS_TASK_EDIT_GROUP_NONE');
				?></span>
				<div class="task-form-arrow"></div>
			</div>
			<div id="fl-wrapper" class="task-form-participant">
				<div class="task-form-participant-title"><?php
					echo GetMessage('MB_TASKS_TASK_EDIT_ACCOMPLICES');
				?></div>
				<div class="task-form-participant-block">
					<div id="tasks-accomplices-block"></div>
					<div class="task-form-participant-row task-form-participant-row-new" 
						onclick="__MB_TASKS_TASK_EDIT_addAccomplices()">
						<?php echo GetMessage('MB_TASKS_TASK_EDIT_BTN_ADD_ACCOMPLICES'); ?>
						<div class="task-form-arrow"></div>
						<div class="task-form-participant-btn"><i></i></div>
					</div>
				</div>
				<div class="task-form-participant-title"><?php
					echo GetMessage('MB_TASKS_TASK_EDIT_AUDITORS');
				?></div>
				<div class="task-form-participant-block">
					<div id="tasks-auditors-block">
					</div>
					<div class="task-form-participant-row task-form-participant-row-new" 
						onclick="__MB_TASKS_TASK_EDIT_addAuditors()">
						<?php echo GetMessage('MB_TASKS_TASK_EDIT_BTN_ADD_AUDITORS'); ?>
						<div class="task-form-arrow"></div>
						<div class="task-form-participant-btn"><i></i></div>
					</div>
				</div>
			</div>
		</div>
	</form>
	<div style="width:100%; height:10px;">
	</div>
	<script type="text/javascript">
		var wrap = document.getElementById('fl-wrapper');

		wrap.addEventListener(/*'touchstart'*/'click', function(event){

			event.preventDefault();

			if (
				//(event.target.getAttribute('data-removable-icon') == 'true')
				//|| (event.target.getAttribute('data-removable-icon-second') == 'true')
				(event.target.className.indexOf('task-form-participant-btn') != -1)
				|| (event.target.className.indexOf('task-form-participant-btn-i') != -1)
			)
			{
				if (event.target.className.indexOf('task-form-participant-btn-i') != -1)
				{
					var childrenList = event.target.parentNode.parentNode.childNodes;
					event.target.classList.toggle('fl-delete-column');
				}
				else
				{
					var childrenList = event.target.parentNode.childNodes;
					event.target.childNodes[0].classList.toggle('fl-delete-column');
				}

				for(var i=0; i<childrenList.length; i++)
				{
					if(childrenList[i].nodeType != 3)
					{
						//if(childrenList[i].getAttribute('data-removable-btn'))
						if (childrenList[i].className.indexOf('fl-delete-right-btn-wrap') != -1)
						{
							childrenList[i].classList.toggle('fl-delete-btn-open');
						}
					}

				}
			}

		}, false);

	</script>
</div><?php // end of tasks-edit-card-container