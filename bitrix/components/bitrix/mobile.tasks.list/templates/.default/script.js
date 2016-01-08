function __MBTasksCPTListInit()
{
	MBTasks.CPT.List.onPullHandler = function(data)
	{
		var argsCheck = !!(
			data 
			&& data.module_id
			&& (data.module_id === 'tasks')
			&& data.command
			&& (
				(data.command === 'task_add')
				|| (data.command === 'task_update')
				|| (data.command === 'task_remove')
			)
			&& data.params
			&& data.params.TASK_ID
			&& data.params.event_GUID
		);

		if ( ! argsCheck )
			return;

		// Skip already processed events
		if (MBTasks.CPT.List.UUIDs_processed.indexOf(data.params.event_GUID) != -1)
			return;

		var tsGotPull = new Date().valueOf() * 0.001;
		var bDirtyDataOnPage = false;
		var delayRefreshForVisiblePage = 100;
		var delayRefreshForInvisiblePage = 500;

		/*
			// If some task was updated
			if ((data.command === 'task_update') || (data.command === 'task_remove'))
			{
				// We must refresh data on page
				if (
					// if we display list of groups
					MBTasks.CPT.List.tasksListGroupsShowed

					// or if task with this TASK_ID exists (or was exists) on our page
					|| (MBTasks.CPT.List.tasksBaseDataCache.hasOwnProperty('t' + data.params.TASK_ID))

					// or if task will appear on page (cause of group changed)
					|| (
						(parseInt(data.params.AFTER.GROUP_ID) !== parseInt(data.params.BEFORE.GROUP_ID))	// group changed
						&& (MBTasks.CPT.List.groupSelected === parseInt(data.params.AFTER.GROUP_ID)))	// tasks from new group is displayed now
				)
				{
					bDirtyDataOnPage = true;
				}
			}
			else if (data.command === 'task_add')	// if some task was changed
			{
				// We need refresh data on page, 
				if (
					// if list of groups displayed
					MBTasks.CPT.List.tasksListGroupsShowed

					// or list of tasks from all groups showed
					// TODO: kogda budet gotova podgruzka zadach skrollingom, otmenit' refresh stranicy dlja jetogo sluchaja
					// i vyvodit' knopku, chto mol dostupny ewe zadachi ili chto-to v jetom rode.
					|| (
						( ! MBTasks.CPT.List.tasksListGroupsShowed )
						&& (MBTasks.CPT.List.groupSelected === false)
					)

					// or list of tasks from some group showed and new task have same group
					|| (MBTasks.CPT.List.groupSelected === parseInt(data.params.AFTER.GROUP_ID))
				)
				{
					bDirtyDataOnPage = true;
				}
			}

			if ( ! bDirtyDataOnPage )
				return;		// Nothing to do
		*/

		MBTasks.CPT.List.ProcessChangedData(data.command, data.params.TASK_ID);

		/*
			// If page is invisible - refresh it.
			// But if page is visible - we must additionally check,
			// how long time ago UIApplicationDidBecomeActiveNotification appears.
			app.checkOpenStatus({
				'callback': function(data)
				{
					if (data.status !== 'visible')
					{
						window.setTimeout(
							function() { MBTasks.CPT.List.RefreshPage(data); },
							delayRefreshForInvisiblePage
						);
					}
					else
					{
						// We must delay check of UIApplicationDidBecomeActiveNotification,
						// because there is no warranty, that this event appears before
						// current pull. It can appears some later.
						window.setTimeout(
							function()
							{
								MBTasks.CPT.List.UpdateTaskListDelayed(data, tsGotPull);
							},
							delayRefreshForVisiblePage
						);
					}
				}
			});
		*/
	};


	MBTasks.CPT.List.ProcessChangedDataCallback = function(data, changedTaskId)
	{
		var baseCacheId = 't' + changedTaskId;
		var htmlBlockId = 'task-list-block-' + changedTaskId;

		if ( ! data.baseData )
			return;

		if ( ! data.filter_check_result )
			return;

		var taskGroupId = 0;
		if (data.baseData.group_id > 0)
			taskGroupId = parseInt(data.baseData.group_id);

		if (data.filter_check_result != 'match filter')
		{
			// remove task, if it is on page
			MBTasks.CPT.List.RemoveTaskFromList(data.task_id);
		}
		else if (
			// if some group selected - filter by group
			(MBTasks.CPT.List.groupSelected !== false)
			&& (MBTasks.CPT.List.groupSelected != taskGroupId)
		)
		{
			// remove task, cause not in selected group
			MBTasks.CPT.List.RemoveTaskFromList(data.task_id);
		}
		else
		{
			// Insert/update data in cache
			var baseData = data.baseData;
			var bTaskWasOnPage = false;

			try
			{
				if ( ! MBTasks.CPT.List.tasksBaseDataCache.hasOwnProperty(baseCacheId) )
				{
					MBTasks.CPT.List.tasksBaseDataCache[baseCacheId] =
					{
						params_emitter: 'tasks_list',
						matrix:         'view',
						gear:            MBTasks.gear,
						task_id:         parseInt(data.task_id)
					}

					bTaskWasOnPage = false;
				}
				else
					bTaskWasOnPage = true;

				for (var k in baseData)
					MBTasks.CPT.List.tasksBaseDataCache[baseCacheId][k] = baseData[k];
			}
			catch (e)
			{
				//alert('Exception-132/pull-lst');
			}

			// If task already on page - it should be redrawed
			if (bTaskWasOnPage)
				MBTasks.CPT.List.ReRenderTaskItem(data.task_id);
			else
			{
				// Add task to page
				var node = MBTasks.CPT.List.RenderNewTaskItem(data.task_id);

				MBTasks.CPT.List.InsertAsFirstChild(
					BX('tasks-all-items-tasks'),
					node
				);

				var length = 0;
				for(var i in MBTasks.CPT.List.tasksBaseDataCache)
				{
					length++;
					break;
				}

				if (length > 0)
					BX('tasks-list-no-task-notificator').style.display = 'none';
			}
		}
	}


	MBTasks.CPT.List.ProcessChangedData = function(command, changedTaskId)
	{
		// Groups showed, do nothing
		if (MBTasks.CPT.List.tasksListGroupsShowed)
			return;

		if (command === 'task_remove')
		{
			MBTasks.CPT.List.RemoveTaskFromList(changedTaskId);
		}
		else if ((command === 'task_update') || (command === 'task_add'))
		{
			MBTasks.CPT.List.loadBaseTaskData(
				changedTaskId,
				(function(changedTaskId){
					return function(data)
					{
						MBTasks.CPT.List.ProcessChangedDataCallback(data, changedTaskId);
					}
				})(changedTaskId)
			);
		}
	}


	MBTasks.CPT.List.InsertAsFirstChild = function(destination, newNode)
	{
		if (destination.firstChild)
			destination.insertBefore(newNode, destination.firstChild);
		else
			destination.appendChild(newNode);
	}


	MBTasks.CPT.List.RenderNewTaskItem = function(taskId)
	{
		var baseCacheId = 't' + taskId;

		if ( ! MBTasks.CPT.List.tasksBaseDataCache.hasOwnProperty(baseCacheId) )
			return;

		var taskData = MBTasks.CPT.List.tasksBaseDataCache[baseCacheId];
		var idPrfx = 'task-item-' + taskId;
		var clsPriority = '';	// default, for normal priority

		if (taskData.priority == 0)		// CTasks::PRIORITY_LOW
			clsPriority = 'task-list-priority-low';
		else if (taskData.priority == 2)		// CTasks::PRIORITY_HIGH
			clsPriority = 'task-list-priority-high';

		var clsStatus = '';
		if (taskData.status_id == -1)	// CTasks::METASTATE_EXPIRED
			clsStatus = 'task-list-status-overdue';
		else if (
			(taskData.status_id == 4)	// CTasks::STATE_SUPPOSEDLY_COMPLETED
			|| (taskData.status_id == 7)	// CTasks::STATE_DECLINED
		)
		{
			clsStatus = 'task-list-status-waiting';
		}
		else if (
			(taskData.status_id == 1)	// CTasks::STATE_NEW
			|| (taskData.status_id == -2)	// CTasks::METASTATE_VIRGIN_NEW
		)
		{
			clsStatus = 'task-list-status-new';
		}

		if ((MBTasks.gear == '2') || (MBTasks.gear == 'D') || (MBTasks.gear == 'OD'))
		{
			var onclick = (function(taskData){
				return (
					function(){
						try
						{
							app.openNewPage(
								MBTasks.CPT.List.snmrouterPath,
								taskData
							);
						}
						catch(e)
						{
						}

						return(false);
					}
				);
			})(taskData);
		}
		else
		{
			var onclick = (function(taskId){
				return (
					function(){
						try
						{
							app.openNewPage(
								MBTasks.CPT.List.taskViewHrefTemplate
									.replace('#TASK_ID#', taskId)
							);
						}
						catch(e)
						{
						}

						return(false);
					}
				);
			})(taskId);
		}

		var node = BX.create('DIV', {
			props: {
				id: 'task-list-block-' + taskId,
				className: 'task-list-block ' + clsPriority
			},
			events: {
				click: onclick
			},
			children: [
				BX.create('DIV', {
					props: {
						id: idPrfx + '-title',
						className: 'task-list-block-title'
					},
					html: BX.util.htmlspecialchars(taskData.title)
				}),
				BX.create('DIV', {
					props: {
						className: 'task-list-block-names'
					},
					children: [
						BX.create('A', {
							props: {
								id: idPrfx + '-originator',
								className: 'task-list-block-names-link'
							},
							html: BX.util.htmlspecialchars(taskData.originator_formatted_name)
						}),
						BX.create('SPAN', {
							props: {
								className: 'task-list-separate'
							}
						}),
						BX.create('A', {
							props: {
								id: idPrfx + '-responsible',
								className: 'task-list-block-names-link'
							},
							html: BX.util.htmlspecialchars(taskData.responsible_formatted_name)
						})
					]
				}),
				BX.create('DIV', {
					props: {
						id: idPrfx + '-deadline',
						className: 'task-list-deadline'
					},
					html: BX.util.htmlspecialchars(taskData.deadline_formatted)
				}),
				BX.create('DIV', {
					props: {
						className: 'task-list-priority'
					}
				}),
				BX.create('DIV', {
					props: {
						id: 'task-list-block-status-' + taskId,
						className: 'task-list-status ' + clsStatus
					}
				}),
				BX.create('DIV', {
					props: {
						className: 'task-list-arrow'
					}
				})
			]
		});

		return (node);
	};


	MBTasks.CPT.List.RemoveTaskFromList = function(taskId)
	{
		var baseCacheId = 't' + taskId;
		var htmlBlockId = 'task-list-block-' + taskId;

		if ( ! MBTasks.CPT.List.tasksBaseDataCache.hasOwnProperty(baseCacheId) )
			return;

		// remove from cache
		delete MBTasks.CPT.List.tasksBaseDataCache[baseCacheId];

		// remove from screen
		if (BX(htmlBlockId))
		{
			try
			{
				var divHeight = parseInt(BX(htmlBlockId).offsetHeight)
					- parseInt(window.getComputedStyle(BX(htmlBlockId), null).getPropertyValue('padding-top'))
					- parseInt(window.getComputedStyle(BX(htmlBlockId), null).getPropertyValue('padding-bottom'))
				;

				MBTasks.CPT.List.sexyRemove(
					htmlBlockId, 1, 0.33, 
					divHeight, 
					divHeight / 3, 10
				);
			}
			catch(e)
			{
				//alert('Exception-110/pull-lst');
			}
		}
	};


	MBTasks.CPT.List.loadBaseTaskData = function(taskId, callback)
	{

		var snmRouterAjaxUrl = MBTasks.siteDir+'mobile/?mobile_action=task_router';
		var postData = {
			sessid:    MBTasks.sessid,
			site:      MBTasks.site,
			lang:      MBTasks.lang,
			subject:  'BASE',	// only base data
			task_id:   taskId,
			user_id:   MBTasks.userId,
			action:   'get_task_data',
			CHECK_FILTER_ID: MBTasks.CPT.List.FILTER_ID,
			DATE_TIME_FORMAT: MBTasks.DATE_TIME_FORMAT,
			PATH_TEMPLATE_TO_USER_PROFILE: '',
			PATH_TO_FORUM_SMILE: '',
			AVA_WIDTH: 0,
			AVA_HEIGHT: 0
		};

		function onFailure()
		{
			callback(false);
		}

		function onSuccess(data, callback)
		{
			if (data && ((data == '{"status":"failed"}') || (data.status == 'failed')))
			{
				app.BasicAuth({
					success: 
						(function(postData, callback){
							return (function(auth_data) {
								MBTasks.sessid = auth_data.sessid_md5;
								BX.ajax({
									timeout:   30,
									method:   'POST',
									dataType: 'json',
									url:       snmRouterAjaxUrl,
									data:      postData,
									onsuccess: function(reply){
										try { onSuccess(reply, callback); }
										catch(e) {
											//alert('Exception-1q! ' + e.name);
										}
									},
									onfailure: function(){
										try	{ onFailure(callback); }
										catch(e) {
											//alert('Exception-2q! ' + e.name);
										}
									}
								});
							});
						})(postData, callback)
					,
					failure: 
						(function(callback){
							return (function() { 
								try	{ onFailure(callback); }
								catch(e) {
									//alert('Exception-3q! ' + e.name);
								}
							});
						})(callback)
				});
			}
			else if (data && data.task_id)	// Do job
				callback(data);
			else
				callback(false);
		}

		BX.ajax({
			timeout:   30,
			method:   'POST',
			dataType: 'json',
			data:      postData,
			url:       snmRouterAjaxUrl,
			onsuccess: 
				(function(postData, callback){
					return (function(data)
					{
						try { onSuccess(data, callback); }
						catch(e) {
							//alert('Exception-4aq! ' + e.name);
						}
					});
				})(postData, callback),
			onfailure: 
				(function(postData, callback){
					return (function()
					{
						try
						{
							onFailure(callback);
						}
						catch(e)
						{
							//alert('Exception-5q! ' + e.name);
						}
					});
				})(postData, callback)
		});
	}


	MBTasks.CPT.List.sexyRemove = function(htmlBlockId, opacity, opacity_step, height, height_step, lag)
	{
		if (opacity <= 0)
		{
			BX.remove(BX(htmlBlockId));

			var length = 0;
			for(var i in MBTasks.CPT.List.tasksBaseDataCache)
			{
				length++;
				break;
			}

			if (length == 0)
				BX('tasks-list-no-task-notificator').style.display = 'block';
		}
		else
		{
			BX(htmlBlockId).style.opacity = opacity.toString();

			if (height >= 0)
			{
				//BX(htmlBlockId).style.minHeight = height;
				BX(htmlBlockId).style.height = height + 'px';
			}

			window.setTimeout(
				(function(htmlBlockId, opacity, opacity_step, height, height_step, lag){
					return (function(){
						MBTasks.CPT.List.sexyRemove(
							htmlBlockId, opacity, opacity_step, height, height_step, lag);
					});
				})(htmlBlockId, opacity - opacity_step, opacity_step, parseInt(height - height_step), height_step, lag),
				lag
			);
		}
	}


	MBTasks.CPT.List.onTasksFilterChangeHandler = function(data)
	{
		// ignore incorrect events
		if ( ! (
			(data.module_id) 
			&& (data.module_id === 'tasks')
			&& (data.emitter)
			&& (data.emitter === 'tasks filter')
			&& (data.user_id == MBTasks.userId)
		))
			return;

		// add filter presetd id to url
		var url = document.location.href;

		var urlparts = url.split('?');   // prefer to use l.search if you have a location/link object
		if (urlparts.length >= 2)
		{
			var prefix = encodeURIComponent('SWITCH_TO_FILTER_PRESET_ID') + '=';
			var pars = urlparts[1].split(/[&;]/g);

			for (var i= pars.length; i-->0;)               // reverse iteration as may be destructive
				if (pars[i].lastIndexOf(prefix, 0)!==-1)   // idiom for string.startsWith
					pars.splice(i, 1);

			url = urlparts[0]+'?'+pars.join('&');
		}

		url += (url.split('?')[1] ? '&':'?') + 'SWITCH_TO_FILTER_PRESET_ID=' + data.filter_preset_id;
		app.reload({url: url});
	};


	// This function will check, when UIApplicationDidBecomeActiveNotification appears
	// If it was just ~1 second before pull got => than we refresh tasks list.
	// Else show notification, that data on page is out of date.
	// This is for visible page. If page is invisible - than refresh it.
	MBTasks.CPT.List.UpdateTaskListDelayed = function(data, tsGotPull)
	{
		if (Math.abs(tsGotPull - MBTasks.lastTimeUIApplicationDidBecomeActiveNotification) <= 1)
			MBTasks.CPT.List.RefreshPage();		// Page is just shown, so we can refresh it
	};


	MBTasks.CPT.List.RefreshPage = function(data)
	{
		app.reload();
	};


	MBTasks.CPT.List.ReRenderTaskItem = function(task_id)
	{
		if ( ! MBTasks.CPT.List.tasksBaseDataCache.hasOwnProperty('t' + task_id) )
			return;

		var taskData = MBTasks.CPT.List.tasksBaseDataCache['t' + task_id];

		var idPrfx = 'task-item-' + task_id;
		BX(idPrfx + '-title').innerHTML = BX.util.htmlspecialchars(taskData.title);
		BX(idPrfx + '-originator').innerHTML = BX.util.htmlspecialchars(taskData.originator_formatted_name);
		/*
		BX(idPrfx + '-originator').href = BX.message('PATH_TEMPLATE_TO_USER_PROFILE')
			.replace('#USER_ID#', taskData.originator_id)
			.replace('#user_id#', taskData.originator_id);
		*/
		BX(idPrfx + '-responsible').innerHTML = BX.util.htmlspecialchars(taskData.responsible_formatted_name);
		/*
		BX(idPrfx + '-responsible').href = BX.message('PATH_TEMPLATE_TO_USER_PROFILE')
			.replace('#USER_ID#', taskData.responsible_id)
			.replace('#user_id#', taskData.responsible_id);
		*/

		if (taskData.deadline_formatted.length > 0)
			BX(idPrfx + '-deadline').innerHTML = BX.util.htmlspecialchars(taskData.deadline_formatted);
		else
			BX(idPrfx + '-deadline').innerHTML = ' ';

		// Priority
		var priorityClass = '';

		if (taskData.priority == 0)	// CTasks::PRIORITY_LOW
			priorityClass = 'task-list-priority-low';
		if (taskData.priority == 2)	// CTasks::PRIORITY_HIGH
			priorityClass = 'task-list-priority-high';

		BX('task-list-block-' + task_id).className = 'task-list-block ' + priorityClass;

		// Status
		var statusClass = '';

		switch (parseInt(taskData.status_id))
		{
			case -1:	// CTasks::METASTATE_EXPIRED
				statusClass = 'task-list-status-overdue';
			break;

			case 4:		// CTasks::STATE_SUPPOSEDLY_COMPLETED
			case 7:		// CTasks::STATE_DECLINED
				statusClass = 'task-list-status-waiting';
			break;

			case -1:	// CTasks::METASTATE_VIRGIN_NEW
			case 1:		// CTasks::STATE_NEW
				statusClass = 'task-list-status-new';
			break;

			default:
			break;
		}

		BX('task-list-block-status-' + task_id).className = 'task-list-status ' + statusClass;
	}


	MBTasks.CPT.List.AcquireLockOpenNewPage = function()
	{
		if (MBTasks.CPT.List.lockOpenNewPage < 0)
			MBTasks.CPT.List.lockOpenNewPage = 0;

		if (MBTasks.CPT.List.lockOpenNewPage > 0)
			return('busy');

		var lockState = ++MBTasks.CPT.List.lockOpenNewPage;
		
		if (lockState === 1)
			return('lock_acquired');
		else if (lockState > 1)
		{
			--MBTasks.CPT.List.lockOpenNewPage;
			return('try_again');
		}
	}


	MBTasks.CPT.List.ReleaseLockOpenNewPage = function()
	{
		window.setTimeout(
			function()
			{
				try
				{
					app.checkOpenStatus({
						callback: function(t){
							if (t && t.status !== 'visible')
							{
								--MBTasks.CPT.List.lockOpenNewPage;
							}
							else
							{
								MBTasks.CPT.List.ReleaseLockOpenNewPage();
							}
						}
					});							
				}
				catch(e)
				{
					//alert('WTF?');
				}
			},
			100
		);
	}

	/*
	BX.addCustomEvent(
		'onHidePageAfter', 
		function() {
			window.setTimeout(
				function()
				{
					try
					{
						MBTasks.CPT.List.lockOpenNewPage = 0;
						alert(MBTasks.CPT.List.lockOpenNewPage);
					}
					catch(e)
					{
					}
				},
				300
			);
		}
	);
	*/

	BX.addCustomEvent(
		'onTaskActionBeforePerfome', 
		function(datum)
		{
			MBTasks.CPT.List.UUIDs_processed.push(datum.UUID);
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
				return;

			var data = datum.data;

			var baseCacheId = 't' + data.task_id;
			var htmlBlockId = 'task-list-block-' + data.task_id;

			if (datum.action === 'remove')
			{
				if (datum.rc !== 'executed')
					return;

				MBTasks.CPT.List.RemoveTaskFromList(data.task_id);
			}
			else if (datum.action === 'edit')
			{
				if (MBTasks.CPT.List.tasksBaseDataCache.hasOwnProperty(baseCacheId))
				{
					// Update data in cache
					for (var key in data.baseData)
					{
						//	alert(key + ': ' + data.baseData[key]);
						if (data.baseData.hasOwnProperty(key))
							MBTasks.CPT.List.tasksBaseDataCache[baseCacheId][key] = data.baseData[key];
					}

					MBTasks.CPT.List.ReRenderTaskItem(data.task_id);
				}

				// Do ajax-request, which actualize data to accord filter settings
				window.setTimeout(
					(function(task_id){
						return function()
						{
							MBTasks.CPT.List.ProcessChangedData(
								'task_update',
								task_id
							);
						}
					})(data.task_id)					
					, 1500
				);
			}
			else
			{
				if (MBTasks.CPT.List.tasksBaseDataCache.hasOwnProperty(baseCacheId))
				{
					if (data.baseData)
					{
						// update status in cache
						MBTasks.CPT.List.tasksBaseDataCache[baseCacheId].status_id = data.baseData.status_id;
						MBTasks.CPT.List.tasksBaseDataCache[baseCacheId].status_formatted_name = data.baseData.status_formatted_name;

						// re-render tasks
						if (BX(htmlBlockId))
						{
							var newStatusName = '';

							// Change status
							if (MBTasks.CPT.List.tasksBaseDataCache[baseCacheId].status_id == -1)		// CTasks::METASTATE_EXPIRED
								newStatusName = 'task-list-status-overdue';
							else if (
								(MBTasks.CPT.List.tasksBaseDataCache[baseCacheId].status_id == 4)		// CTasks::STATE_SUPPOSEDLY_COMPLETED
								|| (MBTasks.CPT.List.tasksBaseDataCache[baseCacheId].status_id == 7)	// CTasks::STATE_DECLINED
							)
							{
								newStatusName = 'task-list-status-waiting';
							}
							else if (
								(MBTasks.CPT.List.tasksBaseDataCache[baseCacheId].status_id == -2)		// CTasks::METASTATE_VIRGIN_NEW
								|| (MBTasks.CPT.List.tasksBaseDataCache[baseCacheId].status_id == 1)	// CTasks::STATE_NEW
							)
							{
								newStatusName = 'task-list-status-new';
							}

							try
							{
								BX('task-list-block-status-' + data.task_id).className = 'task-list-status ' + newStatusName;
							}
							catch (e)
							{
								//alert('Exception 295-mtl');
							}
						}
					}
				}

				// Do ajax-request, which actualize data to accord filter settings
				window.setTimeout(
					(function(task_id){
						return function()
						{
							MBTasks.CPT.List.ProcessChangedData(
								'task_update',
								task_id
							);
						}
					})(data.task_id)					
					, 1500
				);
			}
		}
	);

	app.menuCreate({
		items: [
			{
				name: BX.message('MB_TASKS_TASKS_LIST_MENU_GOTO_FILTER'),
				url: BX.message('PATH_TO_TASKS_FILTER'),
				arrowFlag: true,
				icon: 'settings'
			},
			{
				name: BX.message('MB_TASKS_TASKS_LIST_MENU_CREATE_NEW_TASK'),
				icon: 'add',
				arrowFlag: false,
				action: function() {
					var groupId = 0;
					if (MBTasks.CPT.List.groupSelected > 0)
						groupId = parseInt(MBTasks.CPT.List.groupSelected);

					app.showModalDialog({
						url: BX.message('PATH_TO_TASKS_CREATE') 
							+ '&dialogKey='
							+ '&offerGroupId=' + groupId
							+ '&t=' + new Date().getTime()
					});
					//app.openNewPage(BX.message('PATH_TO_TASKS_CREATE'));
				}
			}
		]
	});


	app.addButtons({
		menuButton: 
		{
			type:     'context-menu',
			style:    'custom',
			callback: function()
			{
				app.menuShow();
			}
		}
	});


	BX.addCustomEvent(
		'onTasksFilterChange', 
		MBTasks.CPT.List.onTasksFilterChangeHandler
	);

	BX.addCustomEvent(
		'onTaskEditPerfomed', 
		function(datum)
		{
			if (datum.rc < 1)
				return;

			var task_id = datum.rc;

			MBTasks.CPT.List.ProcessChangedData('task_update', task_id);

			/*
			if (MBTasks.CPT.List.tasksBaseDataCache.hasOwnProperty('t' + task_id))
				app.reload();
			*/
		}
	);

	BX.addCustomEvent(
		'UIApplicationDidBecomeActiveNotification',
		function(data)
		{
			MBTasks.lastTimeUIApplicationDidBecomeActiveNotification = new Date().valueOf() * 0.001;
		}
	);

	app.onCustomEvent(
		'onPullExtendWatch',
		{
			id: 'TASKS_GENERAL_' + MBTasks.userId
		}
	);

	BX.addCustomEvent(
		'onPull',
		MBTasks.CPT.List.onPullHandler
	);
}
