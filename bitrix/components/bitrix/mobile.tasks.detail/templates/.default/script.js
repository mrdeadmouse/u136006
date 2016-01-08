function __MB_TASKS_TASK_DETAIL_scrollPageBottom()
{
	if (window.platform == "android")
	{
		window.scrollTo(0, document.documentElement.scrollHeight);
	}
	else
	{
		var objDiv = document.getElementById('tasks-detail-card-container');
		objDiv.scrollTop = objDiv.scrollHeight;
	}
}

function __MBTasks__mobile_tasks_view_init()
{
	MBTasks.CPT.view.hideBottomLoader = function()
	{
		BX('tasks-view-loader-area').innerHTML = '';;
	}


	// Clean data in view matrix
	MBTasks.CPT.view.resetToBulk = function()
	{
		if ( ! BX('tasks-view-title') )
			return;

		try
		{
			BX('tasks-view-title').innerHTML = '';
			BX('tasks-view-description').innerHTML = '';
			BX('tasks-view-originator').innerHTML = '';
			BX('tasks-view-responsible').innerHTML = '';
			BX('tasks-view-originator').href = 'javascript:void(0);';
			BX('tasks-view-responsible').href = 'javascript:void(0);';
			BX('tasks-view-originator-work-position').innerHTML = '';
			BX('tasks-view-responsible-work-position').innerHTML = '';
			BX('tasks-view-originator-avatar').style.backgroundImage = '';
			BX('tasks-view-responsible-avatar').style.backgroundImage = '';
			BX('tasks-view-status').innerHTML = '';
			BX('tasks-view-status-corner').className = 'task-view-status';
			BX('tasks-view-groupContainer').style.display = 'none';
			BX('tasks-view-groupName').innerHTML = '';
			BX('tasks-view-deadlineBlock').style.display = 'none';
			BX('tasks-view-deadlineValue').innerHTML = '';
			BX('tasks-view-priority').innerHTML = '';
			BX('task-card-info-notification').innerHTML = '';
			BX('task-card-info-notification').className = 'task-card-info-right';
			BX('tasks-view-filesList').innerHTML = '';
			BX('tasks-view-filesList').style.display = 'none';
			BX('tasks-view-accomplicesList').innerHTML = '';
			BX('tasks-view-accomplicesBlock').style.display = 'none';
			BX('tasks-view-auditorsList').innerHTML = '';
			BX('tasks-view-auditorsBlock').style.display = 'none';
			BX('tasks-view-membersBlock').style.display = 'none';
			BX('tasks-view-loader-area').appendChild(BX.clone(MBTasks.CPT.view.LoadingImage));

			if (BX('post-comment-hidden'))
			{
				BX('post-comment-hidden').style.display = 'none';
				BX('post-comment-hidden').innerHTML = '';
			}

			if (BX('post-comments-wrap'))
			{
				var container = BX('post-comments-wrap');
				var mxi = container.childNodes.length - 1;

				for (var i = mxi; i >= 0; i--)
				{
					var div = container.childNodes[i];

					if (div.nodeName.toLowerCase() != "div")
						continue;

					if (div.className !== 'post-comment-block')
						continue;

					BX.remove(div);
				}
			}

			if (BX('post-comment-more'))
				BX('post-comment-more').style.display = 'none';

			if (BX('tasks-already-loaded-on-page'))
				BX('tasks-already-loaded-on-page').value = '';

		}
		catch (e)
		{
			//alert('Error resetToBulk!');
		}

		try
		{
			// reset menu
			MBTasks.CPT.view.switchOffMenu();
		}
		catch (e)
		{
			//alert('Error resetToBulk!');
		}
	};


	// Render base data in view matrix
	MBTasks.CPT.view.renderBaseData = function(baseData)
	{
		if ( ! BX('tasks-view-title') )
			return;

		if ((!baseData) || (!baseData.task_id))
		{
			return;
		}

		if ((!!!baseData.title) || (baseData.title.length == 0))
		{
			var phrase = BX.message('MB_TASKS_TASK_DETAIL_TASK_WAS_REMOVED_OR_NOT_ENOUGH_RIGHTS');

			document.getElementById('tasks-view-title').innerHTML 
				= phrase.replace('#TASK_ID#', baseData.task_id);

			return;
		}

		var originatorLink  = MBTasks.user_path_template.replace('#USER_ID#', parseInt(baseData.originator_id));
		var responsibleLink = MBTasks.user_path_template.replace('#USER_ID#', parseInt(baseData.responsible_id));
		
		//alert(responsibleLink.replace('#USER_ID#', baseData.responsible_id));

		BX('tasks-view-title').innerHTML = BX.util.htmlspecialchars(baseData.title);
		BX('tasks-view-description').innerHTML = baseData.description;
		BX('tasks-view-originator').innerHTML = BX.util.htmlspecialchars(baseData.originator_formatted_name);
		BX('tasks-view-responsible').innerHTML = BX.util.htmlspecialchars(baseData.responsible_formatted_name);
		BX('tasks-view-originator-work-position').innerHTML = BX.util.htmlspecialchars(baseData.originator_work_position);
		BX('tasks-view-responsible-work-position').innerHTML = BX.util.htmlspecialchars(baseData.responsible_work_position);
		BX('tasks-view-originator').href = originatorLink;
		BX('tasks-view-responsible').href = responsibleLink;
		BX('tasks-view-status').innerHTML = BX.util.htmlspecialchars(baseData.status_formatted_name);

		// Status
		var newStatusName = '';

		// Change status
		if (baseData.status_id == -1)		// CTasks::METASTATE_EXPIRED
		{
			newStatusName = 'task-list-status-overdue';
			BX('task-card-info-notification').className = "task-card-info-right task-card-info-right-red";
			BX('task-card-info-notification').innerHTML = BX.message('MB_TASKS_TASK_DETAIL_NOTIFICATION_EXPIRED');
		}
		else if (
			(baseData.status_id == 4)		// CTasks::STATE_SUPPOSEDLY_COMPLETED
			|| (baseData.status_id == 7)	// CTasks::STATE_DECLINED
		)
		{
			newStatusName = 'task-list-status-waiting';
			if (baseData.status_id == 4)
			{
				BX('task-card-info-notification').className = "task-card-info-right task-card-info-right-orange";
				BX('task-card-info-notification').innerHTML = BX.message('MB_TASKS_TASK_DETAIL_NOTIFICATION_STATE_SUPPOSEDLY_COMPLETED');
			}
		}
		else if (
			(baseData.status_id == -2)		// CTasks::METASTATE_VIRGIN_NEW
			|| (baseData.status_id == 1)	// CTasks::STATE_NEW
		)
		{
			newStatusName = 'task-list-status-new';
		}

		BX('tasks-view-status-corner').className = 'task-view-status ' + newStatusName;

		try
		{
			if (baseData.originator_photo_src && baseData.originator_photo_src.length > 0)
				BX('tasks-view-originator-avatar').style.backgroundImage = "url('" + baseData.originator_photo_src + "')";
			else
				BX('tasks-view-originator-avatar').style.backgroundImage = '';
		}
		catch (e)
		{
			//alert('Excpetion-95/148-mbd-A');
			//alert(BX('tasks-view-originator-avatar'));
		}

		try
		{
			if (baseData.responsible_photo_src && baseData.responsible_photo_src.length > 0)
				BX('tasks-view-responsible-avatar').style.backgroundImage = "url('" + baseData.responsible_photo_src + "')";
			else
				BX('tasks-view-responsible-avatar').style.backgroundImage = '';
		}
		catch (e)
		{
			//alert('Excpetion-95/161-mbd-BB');
			//alert(BX('tasks-view-responsible-avatar'));
		}



		if (baseData.group_id > 0)
		{
			BX('tasks-view-groupContainer').style.display = 'block';
			BX('tasks-view-groupName').innerHTML = BX.util.htmlspecialchars(baseData.group_name);
		}
		else
		{
			BX('tasks-view-groupContainer').style.display = 'none';
			BX('tasks-view-groupName').innerHTML = '';
		}

		if (baseData.deadline_formatted.length > 0)
		{
			BX('tasks-view-deadlineBlock').style.display = 'block';
			BX('tasks-view-deadlineValue').innerHTML = BX.util.htmlspecialchars(baseData.deadline_formatted);
		}
		else
		{
			BX('tasks-view-deadlineBlock').style.display = 'none';
			BX('tasks-view-deadlineValue').innerHTML = '';
		}

		var color = 'black';
		var priorityAsText = 'unknown priority';
		switch (parseInt(baseData.priority))
		{
			case 0:		// CTasks::PRIORITY_LOW
				color = 'grey';
				priorityAsText = BX.message('MB_TASKS_TASK_DETAIL_PRIORITY_LOW');
			break;

			case 1:		// CTasks::PRIORITY_AVERAGE
				color = 'green';
				priorityAsText = BX.message('MB_TASKS_TASK_DETAIL_PRIORITY_AVERAGE');
			break;

			case 2:		// CTasks::PRIORITY_HIGH
				color = 'red';
				priorityAsText = BX.message('MB_TASKS_TASK_DETAIL_PRIORITY_HIGH');
			break;
		}

		BX('tasks-view-priority').innerHTML = '<span style="color:' + color + '">'
			+ priorityAsText + '</span>';
	}


	// Render detail data in view matrix
	MBTasks.CPT.view.renderDetailData = function(data)
	{
		if ( ! BX('tasks-view-filesList') )
			return;

		if ( ( ! data ) || ( ! data.task_id ) )
		{
			BX('tasks-view-filesList').innerHTML = 'error';
			BX('tasks-view-accomplicesList').innerHTML = 'error';
			BX('tasks-view-auditorsList').innerHTML = 'error';
			return false;
		}

		MBTasks.CPT.view.renderFilesList(data);

		if (data.accomplices)
		{
			MBTasks.CPT.view.renderMembersList(
				'tasks-view-accomplicesBlock', 
				'tasks-view-accomplicesList', 
				data.task_id, 
				data.accomplices
			);
		}

		if (data.auditors)
		{
			MBTasks.CPT.view.renderMembersList(
				'tasks-view-auditorsBlock', 
				'tasks-view-auditorsList', 
				data.task_id, 
				data.auditors
			);
		}

		if (
			(data.auditors && data.auditors.length)
			|| (data.accomplices && data.accomplices.length)
		)
		{
			BX('tasks-view-membersBlock').style.display = 'block';
		}
		else
			BX('tasks-view-membersBlock').style.display = 'none';

		if (data.actions)
		{
			var menuItems = MBTasks.CPT.view.createMenu(
				data.task_id, 
				data.actions
			);

			if (menuItems.length)
			{
				// Prepare menu items accord to allowed actions
				app.menuCreate({
					items: menuItems
				});

				app.addButtons({menuButton: {
					type:    'context-menu',
					style:   'custom',
					callback: function()
					{
						app.menuShow();
					}
				}});
			}
		}

		BX('tasks-view-loader-area').innerHTML = '';
	}


	// Called by renderDetailData().
	// Expects data.task_id and data.files
	MBTasks.CPT.view.renderFilesList = function(data)
	{
		var l = 0;

		if ( ( ! data.files ) && ( ! data.forum_files ) )
			return;

		if (MBTasks.residentTaskId != data.task_id)
			return;

		BX('tasks-view-filesList').innerHTML = '';

		var files = [];

		if (data.files)
		{
			l = l + data.files.length;
			files = data.files;
		}

		if (data.forum_files)
		{
			l = l + data.forum_files.length;

			for (var k in data.forum_files)
			{
				if (data.forum_files.hasOwnProperty(k))
				{
					data.forum_files[k]['fileLinkPref'] = '/bitrix/components/bitrix/forum.interface/show_file.php?fid=';
					files.push(data.forum_files[k]);
				}
			}
		}

		if (l == 0)
		{
			BX('tasks-view-filesList').style.display = 'none';
			return;
		}
		else
			BX('tasks-view-filesList').style.display = 'block';

		files.forEach(function(element, index, array){
			try
			{
				var fileLinkPref = '/bitrix/components/bitrix/tasks.task.detail/show_file.php?fid=';
				if (element.fileLinkPref)
					fileLinkPref = element.fileLinkPref;

				var fileLink = fileLinkPref + element.id;

				var fileNode = BX.create(
					'div',
					{
						props: {
							className: 'post-item-attached-file'
						},
						children: [
							BX.create(
								'span',
								{
									style: {
										display: 'none'
									},
									html: '&nbsp;'
								}
							),
							BX.create(
								'a',
								{
									attrs: {
										href: fileLink
									},
									text: BX.util.htmlspecialchars(element.name)
								}
							),
							BX.create(
								'span',
								{
									text: ' (' + element.size_formatted + ')'
								}
							)
						]
					}
				);

				// prevent render data, when new task resides page
				if (MBTasks.residentTaskId != data.task_id)
					return;

				BX('tasks-view-filesList').appendChild(fileNode);

				// Cleanup just rendered item, if new task begin resides page
				if (MBTasks.residentTaskId != data.task_id)
				{
					try { BX.remove(fileNode); } catch(e) {}

					return;
				}
			}
			catch (e)
			{
			}
		});
	}


	// Called by renderDetailData().
	// Expects data.task_id and data.accomplices
	MBTasks.CPT.view.renderMembersList = function(blockId, listId, task_id, members)
	{
		if ( ! members )
			return;

		if (MBTasks.residentTaskId != task_id)
			return;

		BX(listId).innerHTML = '';

		if (members.length == 0)
			BX(blockId).style.display = 'none';
		else
			BX(blockId).style.display = 'block';

		members.forEach(function(element, index, array){
			try
			{
				var userLink = MBTasks.user_path_template.replace('#USER_ID#', element.user_id);

				var userNode = BX.create(
					'a',
					{
						attrs: {
							href: userLink
						},
						text: element.name_formatted
					}
				);

				var span = BX.create(
					'span',
					{
						html: ' &nbsp; '
					}
				);

				// prevent render data, when new task resides page
				if (MBTasks.residentTaskId != task_id)
					return;

				BX(listId).appendChild(userNode);
				BX(listId).appendChild(span);

				// Cleanup just rendered item, if new task begin resides page
				if (MBTasks.residentTaskId != task_id)
				{
					try { BX.remove(userNode); BX(listId).appendChild(span); } catch(e) {}

					return;
				}
			}
			catch (e)
			{
			}
		});

		return (members.length > 0);
	}


	// Render comments data in view matrix
	MBTasks.CPT.view.renderCommentsData = function(data)
	{
		if (__MB_TASKS_TASK_TOPIC_REVIEWS_ShowComments)
			__MB_TASKS_TASK_TOPIC_REVIEWS_ShowComments(data);
	}


	MBTasks.CPT.view.switchOffMenu = function()
	{
		// Prepare menu items accord to allowed actions
		app.menuCreate({
			items: []
		});
	}


	MBTasks.CPT.view.perfomActionOnFailure = function()
	{
	}


	MBTasks.CPT.view.perfomActionOnSuccess = function(datum, postData)
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
				success: (function(postData){
					return function(auth_data)
					{
						MBTasks.sessid = auth_data.sessid_md5;
						BX.ajax({
							timeout:   30,
							method:   'POST',
							dataType: 'json',
							url:       MBTasks.snmRouterAjaxUrl,
							data:      postData,
							onsuccess: function(reply){
								try { MBTasks.CPT.view.perfomActionOnSuccess(reply, postData); }
								catch(e) {
									//alert('Exception-1! ' + e.name);
								}
							},
							onfailure: function(){
								try	{ MBTasks.CPT.view.perfomActionOnFailure(); }
								catch(e) {
									//alert('Exception-2! ' + e.name);
								}
							}
						});
					}
				})(postData),
				failure: function() { 
					try	{ MBTasks.CPT.view.perfomActionOnFailure(); }
					catch(e) { 
						//alert('Exception-3! ' + e.name);
					}
				}
			});
		}
		else if (datum && datum.data && datum.data.task_id)	// Do job
		{
			var eventData = {
				module_id: 'tasks',
				emitter:   'tasks component mobile.tasks.detail',
				task_id:    datum.data.task_id,
				action:     datum.action_perfomed,
				data:       datum.data,
				rc:         datum.rc
			}

			app.onCustomEvent(
				'onTaskActionPerfomed',
				eventData
			);

			BX.onCustomEvent(
				'onTaskActionPerfomed',
				[eventData]
			);
		}
	}


	MBTasks.CPT.view.perfomAction = function(task_id, action_system_name, datum)
	{
		MBTasks.CPT.view.switchOffMenu();

		app.showPopupLoader();

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

		var taskData = false;
		var subject  = false;

		if (action_system_name == 'edit')
		{
			taskData = datum.taskData;
			action_system_name = 'edit';
			subject = 'BASE_AND_DETAIL_AND_COMMENTS';
			taskData['META::EVENT_GUID'] = UUID;
		}
		else if (action_system_name == 'delegate')
		{
			taskData = datum.taskData;
			action_system_name = 'delegate';
			subject = 'BASE_AND_DETAIL_AND_COMMENTS';
			taskData['META::EVENT_GUID'] = UUID;
		}

		var postData = {
			sessid:      BX.bitrix_sessid(),
			site:        MBTasks.site,
			lang:        MBTasks.lang,
			task_id:     task_id,
			user_id:     MBTasks.userId,
			action:     'perfom_action',
			action_name: action_system_name,
			subject:     subject,
			taskData:    taskData,
			DATE_TIME_FORMAT: 
				MBTasks.CPT.view.arParams.DATE_TIME_FORMAT,
			PATH_TEMPLATE_TO_USER_PROFILE: 
				MBTasks.CPT.view.arParams.PATH_TEMPLATE_TO_USER_PROFILE,
			PATH_TO_FORUM_SMILE: 
				MBTasks.CPT.view.arParams.PATH_TO_FORUM_SMILE,
			AVA_WIDTH: 
				MBTasks.CPT.view.arParams.AVA_WIDTH,
			AVA_HEIGHT: 
				MBTasks.CPT.view.arParams.AVA_HEIGHT
		};

		BX.ajax({
			timeout: 30,
			method: 'POST',
			dataType: 'json',
			data: postData,
			url: MBTasks.snmRouterAjaxUrl,
			onsuccess: (function(postData){
				return function(data)
				{
					try
					{ 
						MBTasks.CPT.view.perfomActionOnSuccess(data, postData);
						app.hidePopupLoader();
					}
					catch(e) {
						//alert('Exception-4b! ' + e.name);
					}
				}
			})(postData),
			onfailure: function()
			{
				try
				{
					app.hidePopupLoader();
					MBTasks.CPT.view.perfomActionOnFailure();
				}
				catch(e)
				{
					//alert('Exception-5! ' + e.name);
				}
			}
		});
	}


	// Prepare list of menu items
	MBTasks.CPT.view.createMenu = function(task_id, allowed_actions)
	{
		var menu_items = [];

		allowed_actions.forEach(function(action, index, array){
			if (action.public_name == 'edit') {
				menu_items.push({
					name: BX.message('MB_TASKS_TASK_DETAIL_BTN_EDIT'),
					icon: 'edit',
					arrowFlag: false,
					action: (function(task_id){ 
						return function() {
							app.showModalDialog({
								url: MBTasks.task_edit_path_template.replace('#TASK_ID#', task_id + '&dialogKey=' + MBTasks.CPT.view.dialogKey + '&t=' + new Date().getTime())
							});
							/*
							app.openNewPage(
								MBTasks.task_edit_path_template.replace('#TASK_ID#', task_id + '&t=' + new Date().getTime()),
								{
									dialogKey: MBTasks.CPT.view.dialogKey
								}
							);
							*/
						}
					})(task_id)
				});
			}

			if (action.public_name == 'remove') {
				menu_items.push({
					name: BX.message('MB_TASKS_TASK_DETAIL_BTN_REMOVE'),
					icon: 'delete',
					action: (function(task_id, action_name){ 
						return function() {
							//if (confirm(BX.message('MB_TASKS_TASK_DETAIL_CONFIRM_REMOVE')))
							//	MBTasks.CPT.view.perfomAction(task_id, action_name);

							app.confirm({
								text     : BX.message('MB_TASKS_TASK_DETAIL_CONFIRM_REMOVE'),
								buttons  : ["OK", BX.message('MB_TASKS_TASK_DETAIL_USER_SELECTOR_BTN_CANCEL')],
								callback : (function(task_id, action_name){
									return function (btnNum)
									{
										if (btnNum == 1)
											MBTasks.CPT.view.perfomAction(task_id, action_name);
									}
								})(task_id, action_name)
							});
						}
					})(task_id, action.system_name)
				});
			}

			if (action.public_name == 'accept') {
				menu_items.push({
					name: BX.message('MB_TASKS_TASK_DETAIL_BTN_ACCEPT_TASK'),
					icon: 'check',
					action: (function(task_id, action_name){ 
						return function() {
							MBTasks.CPT.view.perfomAction(task_id, action_name);
						}
					})(task_id, action.system_name)
				});
			}

			if (action.public_name == 'start') {
				menu_items.push({
					name: BX.message('MB_TASKS_TASK_DETAIL_BTN_START_TASK'),
					icon: 'play',
					action: (function(task_id, action_name){ 
						return function() {
							MBTasks.CPT.view.perfomAction(task_id, action_name);
						}
					})(task_id, action.system_name)
				});
			}

			if (action.public_name == 'decline') {
				menu_items.push({
					name: BX.message('MB_TASKS_TASK_DETAIL_BTN_DECLINE_TASK'),
					icon: 'cancel',
					action: (function(task_id, action_name){ 
						return function() {
							MBTasks.CPT.view.perfomAction(task_id, action_name);
						}
					})(task_id, action.system_name)
				});
			}

			if (action.public_name == 'renew') {
				menu_items.push({
					name: BX.message('MB_TASKS_TASK_DETAIL_BTN_RENEW_TASK'),
					icon: 'reload',
					action: (function(task_id, action_name){ 
						return function() {
							MBTasks.CPT.view.perfomAction(task_id, action_name);
						}
					})(task_id, action.system_name)
				});
			}

			if (action.public_name == 'close') {
				menu_items.push({
					name: BX.message('MB_TASKS_TASK_DETAIL_BTN_CLOSE_TASK'),
					icon: 'finish',
					action: (function(task_id, action_name){ 
						return function() {
							MBTasks.CPT.view.perfomAction(task_id, action_name);
						}
					})(task_id, action.system_name)
				});
			}

			if (action.public_name == 'pause') {
				menu_items.push({
					name: BX.message('MB_TASKS_TASK_DETAIL_BTN_PAUSE_TASK'),
					icon: 'pause',
					action: (function(task_id, action_name){ 
						return function() {
							MBTasks.CPT.view.perfomAction(task_id, action_name);
						}
					})(task_id, action.system_name)
				});
			}

			if (action.public_name == 'approve') {
				menu_items.push({
					name: BX.message('MB_TASKS_TASK_DETAIL_BTN_APPROVE_TASK'),
					icon: 'checkbox',
					action: (function(task_id, action_name){ 
						return function() {
							MBTasks.CPT.view.perfomAction(task_id, action_name);
						}
					})(task_id, action.system_name)
				});
			}

			if (action.public_name == 'redo') {
				menu_items.push({
					name: BX.message('MB_TASKS_TASK_DETAIL_BTN_REDO_TASK'),
					action: (function(task_id, action_name){ 
						return function() {
							MBTasks.CPT.view.perfomAction(task_id, action_name);
						}
					})(task_id, action.system_name)
				});
			}

			if (action.public_name == 'delegate') {
				menu_items.push({
					name: BX.message('MB_TASKS_TASK_DETAIL_BTN_DELEGATE_TASK'),
					icon: 'finish',
					action: (function(task_id, action_name){ 
						return function() {
							app.openTable({
								callback: function(data)
								{
									if ( ! (data && data.a_users && data.a_users[0]) )
										return;

									var user = data.a_users[0];
									var user_id = user['ID'].toString();

									MBTasks.CPT.view.perfomAction(
										task_id,
										'delegate',
										{
											taskData:
											{
												TASK_ID: task_id,
												'META::DELEGATE_TO_USER': user_id
											}
										}
									);
								},
								url: (BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') : '/') + 'mobile/index.php?mobile_action=get_user_list',
								markmode: true,
								multiple: false,
								return_full_mode: true,
								modal: true,
								alphabet_index: true,
								outsection: false,
								cancelname: BX.message('MB_TASKS_TASK_DETAIL_USER_SELECTOR_BTN_CANCEL')
							});
						}
					})(task_id, action.system_name)
				});
			}
		});

		return (menu_items);
	}


	BX.addCustomEvent(
		'onTaskSaveBefore',
		function(datum)
		{
			if ( ! (
				datum 
				&& datum.module_id 
				&& datum.dialogKey 
				&& datum.taskData
				&& (datum.module_id == 'tasks')
				&& (datum.dialogKey == MBTasks.CPT.view.dialogKey)
			))
			{
				return;
			}

			if (datum.delayFire)
			{
				(function(datum){
					window.setTimeout(
						function(){
							MBTasks.CPT.view.perfomAction(datum.taskData.TASK_ID, 'edit', datum);
						},
						parseInt(datum.delayFire)
					);
				})(datum);
			}
			else
				MBTasks.CPT.view.perfomAction(datum.taskData.TASK_ID, 'edit', datum);
		}
	);
}
