function __MBTasks__mobile_tasks_snmrouter_init()
{
	MBTasks.onPullHandler = function(data)
	{
		var argsCheck = !!(
			data 
			&& data.module_id
			&& (data.module_id === 'tasks')
			&& data.command
			&& (
				(data.command === 'task_update')
				|| (data.command === 'task_remove')
			)
			&& data.params
			&& data.params.TASK_ID
			&& data.params.event_GUID
		);

		if ( ! argsCheck )
			return;

		// Skip event, if this task isn't resident
		if (MBTasks.residentTaskId != data.params.TASK_ID)
			return;

		// Skip already processed events
		if (MBTasks.CPT.router.UUIDs_processed.indexOf(data.params.event_GUID) != -1)
			return;

		if (data.command === 'task_remove')
		{
			// Clean data from cache
			MBTasks.clearCacheForTask(data.params.TASK_ID);

			// Show 'task deleted' screen and back to
			if (MBTasks.residentTaskId == data.params.TASK_ID)
			{
				MBTasks.CPT.view.resetToBulk();
				MBTasks.CPT.view.hideBottomLoader();

				//MBTasks.CPT.router.switchToMatrix('removed-task')
				var phrase = BX.message('MB_TASKS_TASK_DETAIL_TASK_WAS_REMOVED_OR_NOT_ENOUGH_RIGHTS');

				document.getElementById('tasks-view-title').innerHTML 
					= phrase.replace('#TASK_ID#', data.params.TASK_ID);
			}

			return;
		}

		MBTasks.reloadPageAndCache(data.params.TASK_ID);
	};


	MBTasks.addCommentsToCache = function(task_id, commentsData)
	{
		var cacheCommentsKey = 'c' + task_id.toString();

		MBTasks.cache[cacheCommentsKey] = commentsData;
	}

	MBTasks.addDetailsToCache = function(task_id, detailsData)
	{
		var cacheDetailKey = 'd' + task_id.toString();

		MBTasks.cache[cacheDetailKey] = detailsData;
	}

	MBTasks.clearCacheForTask = function(task_id)
	{
		var cacheCommentsKey = 'c' + task_id.toString();
		var cacheDetailKey = 'd' + task_id.toString();

		if (MBTasks.cache.hasOwnProperty(cacheCommentsKey))
			delete MBTasks.cache[cacheCommentsKey];

		if (MBTasks.cache.hasOwnProperty(cacheDetailKey))
			delete MBTasks.cache[cacheDetailKey];
	}

	MBTasks.getBaseTaskData = function(callback_for_baseData)
	{
		// Try get data from params in openNewPage
		app.getPageParams({ callback: function(data) {
			if ( (data === false)
				|| ( ! data.task_id )
			)
			{
				data = false;
			}
			else
				data.dataSource = 'pageParams';

			callback_for_baseData(data);
		}});
	}

	// Get preloaded data, or data from L2 cache, or via ajax-request
	MBTasks.getExtendedTaskData = function(callback_for_detailData, callback_for_comments)
	{
		MBTasks.getBaseTaskData(
			function(baseData){
				MBTasks.getExtendedTaskData_step2(baseData, callback_for_detailData, callback_for_comments);
			}
		);
	}

	// Get preloaded data, or data from L2 cache, or via ajax-request
	MBTasks.getAllTaskDataWoCache = function(task_id, callback_for_baseData, callback_for_detailData, callback_for_comments)
	{
		// Clear cache L2
		MBTasks.clearCacheForTask(task_id);

		var baseData = {task_id: task_id};

		MBTasks.getBaseTaskData(
			function(baseData){
				MBTasks.getExtendedTaskData_step2(baseData, callback_for_detailData, callback_for_comments, true, callback_for_baseData);
			}
		);
	}

	// Loads data from L2 cache or via ajax-request
	MBTasks.getExtendedTaskData_step2 = function(baseData, callback_for_detailData, callback_for_comments, requestAllDataWoCache, callback_for_baseData)
	{
		if ( (baseData === false) || ( ! baseData.task_id ) )
		{
			callback_for_detailData(false);
			callback_for_comments(false);
			return;
		}

		var taskId = baseData.task_id.toString();
		var cacheDetailKey = 'd' + taskId;
		var cacheCommentsKey = 'c' + taskId;
		var needAjaxLoad = false;
		var subject = '';

		if (requestAllDataWoCache === true)
		{
			subject = 'BASE_AND_DETAIL_AND_COMMENTS';
			needAjaxLoad = true;

			var bDetailCacheHit = false;
			var bCommentsCacheHit = false;
		}
		else
		{
			var bDetailCacheEnabled = ((MBTasks.gear === 'D') || (MBTasks.gear === 'OD'));
			var bCommentCacheEnabled = (MBTasks.gear === 'OD');

			// Check that detail data in cache
			var bDetailCacheHit = bDetailCacheEnabled && MBTasks.cache.hasOwnProperty(cacheDetailKey);

			// Check that comments data in cache
			var bCommentsCacheHit = bCommentCacheEnabled && MBTasks.cache.hasOwnProperty(cacheCommentsKey);

			if ( ! (bDetailCacheHit && bCommentsCacheHit) )
				needAjaxLoad = true;

			if ( ( ! bDetailCacheHit ) && ( ! bCommentsCacheHit ) )
				subject = 'DETAIL_AND_COMMENTS';
			else if ( ! bDetailCacheHit )
				subject = 'DETAIL';
			else if ( ! bCommentsCacheHit )
				subject = 'COMMENTS';
		}

		// Start loading data via ajax
		if (needAjaxLoad)
		{
			var postData = {
				sessid:    MBTasks.sessid,
				site:      MBTasks.site,
				lang:      MBTasks.lang,
				subject:   subject,
				task_id:   taskId,
				user_id:   MBTasks.userId,
				action:   'get_task_data',
				DATE_TIME_FORMAT: 
					MBTasks.CPT.router.arParams.DATE_TIME_FORMAT,
				PATH_TEMPLATE_TO_USER_PROFILE: 
					MBTasks.CPT.view.arParams.PATH_TEMPLATE_TO_USER_PROFILE,
				PATH_TO_FORUM_SMILE: 
					MBTasks.CPT.view.arParams.PATH_TO_FORUM_SMILE,
				AVA_WIDTH: 
					MBTasks.CPT.view.arParams.AVA_WIDTH,
				AVA_HEIGHT: 
					MBTasks.CPT.view.arParams.AVA_HEIGHT
			};

			function onFailure()
			{
				if (requestAllDataWoCache)
					callback_for_baseData(false);

				if ( ! bDetailCacheHit )
					callback_for_detailData(false);

				if ( ! bCommentsCacheHit )
					callback_for_comments(false);
			}

			function onSuccess(data)
			{
				if (data && ((data == '{"status":"failed"}') || (data.status == 'failed')))
				{
					app.BasicAuth({
						success: BX.delegate(
							function(auth_data)
							{
								MBTasks.sessid = auth_data.sessid_md5;
								BX.ajax({
									timeout:   30,
									method:   'POST',
									dataType: 'json',
									url:       MBTasks.snmRouterAjaxUrl,
									data:      postData,
									onsuccess: function(reply){
										try { onSuccess(reply); }
										catch(e) {
											//alert('Exception-1! ' + e.name);
										}
									},
									onfailure: function(){
										try	{ onFailure(); }
										catch(e) {
											//alert('Exception-2! ' + e.name);
										}
									}
								});
							},
							this
						),
						failure: function() { 
							try	{ onFailure(); }
							catch(e) {
								//alert('Exception-3! ' + e.name);
							}
						}
					});
				}
				else if (data && data.task_id)	// Do job
				{
					// Register data in cache
					if (data.commentsData)
						MBTasks.addCommentsToCache(data.task_id, data.commentsData)

					if (data.detailsData)
						MBTasks.addDetailsToCache(data.task_id, data.detailsData)

					// Fire callbacks
					if (requestAllDataWoCache)
						callback_for_baseData(data.baseData);

					if (data.commentsData)
						callback_for_comments(data.commentsData);

					if (data.detailsData)
						callback_for_detailData(data.detailsData);
				}
				else
				{
					if (requestAllDataWoCache)
						callback_for_baseData(false);

					if ( ! bDetailCacheHit )
						callback_for_detailData(false);

					if ( ! bCommentsCacheHit )
						callback_for_comments(false);
				}
			}

			BX.ajax({
				timeout: 30,
				method: 'POST',
				dataType: 'json',
				data: postData,
				url: MBTasks.snmRouterAjaxUrl,
				onsuccess: 
					(function(
						bDetailCacheHit, bCommentsCacheHit, 
						requestAllDataWoCache, postData
					){
						return function(data)
						{
							try { onSuccess(data); }
							catch(e) {
								//alert('Exception-4a! ' + e.name);
							}
						}
					})(bDetailCacheHit, bCommentsCacheHit, 
					requestAllDataWoCache, postData
				),
				onfailure: (function(bDetailCacheHit, bCommentsCacheHit, requestAllDataWoCache, postData){
					return function()
					{
						try
						{
							onFailure();
						}
						catch(e)
						{
							//alert('Exception-5! ' + e.name);
						}
					}
				})(bDetailCacheHit, bCommentsCacheHit, requestAllDataWoCache, postData)
			});
		}

		// While ajax processed, show detail data and comments from cache (if available)
		if (bDetailCacheHit)
			callback_for_detailData(MBTasks.cache[cacheDetailKey]);

		if (bCommentsCacheHit)
			callback_for_comments(MBTasks.cache[cacheCommentsKey]);
	}

	MBTasks.CPT.router.switchToMatrix = function(matrixName)
	{
		var block = 'tasks-router-' + matrixName;

		MBTasks.selectedMatrix = matrixName;

		if (block != 'tasks-router-view')
		{
			/*
			There is problem in iOS 5
			// BX('tasks-router-view').style.display = 'none';
			BX('tasks-detail-card-container-over').style.display = 'none';
			BX('comment_send_form').style.display = 'none';
			*/
			BX('tasks-detail-card-container-over').style.opacity = 0;
			BX('comment_send_form').style.opacity = 0;
		}

		if (block != 'tasks-router-edit')
		{
			//BX('tasks-router-edit').style.display = 'none';
			BX('tasks-router-edit').style.opacity = 0;
		}

		if (block != 'tasks-router-removed-task')
		{
			//BX('tasks-router-removed-task').style.display = 'none';
			BX('tasks-router-removed-task').style.opacity = 0;
		}

		if (block == 'tasks-router-view')
		{
			//BX('tasks-router-view').style.display = 'block';
			BX('tasks-detail-card-container-over').style.opacity = 1;
			BX('comment_send_form').style.opacity = 1;
		}
		else
			BX(block).style.opacity = 1;
	}

	MBTasks.pageHided = function(){
		/*
		There is problem in iOS 5
		// BX('tasks-router-view').style.display = 'none';
		BX('tasks-detail-card-container-over').style.display = 'none';
		BX('comment_send_form').style.display = 'none';

		BX('tasks-router-removed-task').style.display = 'none';
		BX('tasks-router-edit').style.display = 'none';
		*/

		// BX('tasks-router-view').style.display = 'none';
		BX('tasks-detail-card-container-over').style.opacity = 0;
		BX('comment_send_form').style.opacity = 0;

		BX('tasks-router-removed-task').style.opacity = 0;
		BX('tasks-router-edit').style.opacity = 0;
	}

	MBTasks.pageOpened = function(baseDataChanged){
		MBTasks.getBaseTaskData(
			function(data)
			{
				if (
					(!data) || (!data.task_id) 
					|| (!data.params_emitter) 
					|| (data.params_emitter !== 'tasks_list')
				)
				{
					return false;
				}

				MBTasks.residentTaskId = data.task_id;
				MBTasks.selectedMatrix = data.matrix;

				MBTasks.gear = data.gear;

				var newDataHash = data.task_id;
				var bNeedHideLoaders = false;

				if (MBTasks.showedBaseDataHash === false)
				{
					// We just start page, we need hide loading screens after base data rendered
					bNeedHideLoaders = true;
				}

				if (newDataHash !== MBTasks.showedBaseDataHash)
					baseDataChanged = true;

				if ( ! baseDataChanged )
				{
					if (data.matrix === 'view')
					{
						/*
						There is problem in iOS 5
						// BX('tasks-router-view').style.display = 'block';
						BX('tasks-detail-card-container-over').style.display = 'block';
						BX('comment_send_form').style.display = 'block';
						*/
						BX('tasks-detail-card-container-over').style.opacity = 1;
						BX('comment_send_form').style.opacity = 1;
					}
					else if (data.matrix === 'edit')
					{
						//BX('tasks-router-edit').style.display = 'block';
						BX('tasks-router-edit').style.opacity = 1;
					}

					return;	// nothing to do
				}

				app.removeButtons({
					position: 'right'
				});

				if (data.matrix === 'view')
				{
					MBTasks.CPT.view.resetToBulk();		// clean data
					MBTasks.CPT.view.renderBaseData(data);
					/*
					There is problem in iOS 5
					// BX('tasks-router-view').style.display = 'block';
					BX('tasks-detail-card-container-over').style.display = 'block';
					BX('comment_send_form').style.display = 'block';
					*/
					BX('tasks-detail-card-container-over').style.opacity = 1;
					BX('comment_send_form').style.opacity = 1;
					BX('tasks-detail-card-container-over').scrollTop = 0;
				}
				else if (data.matrix === 'edit')
				{
					MBTasks.CPT.edit.resetToBulk();		// clean data
					MBTasks.CPT.edit.renderBaseData(data);
					//BX('tasks-router-edit').style.display = 'block';
					BX('tasks-router-edit').style.opacity = 1;
				}

				MBTasks.showedBaseDataHash = newDataHash;

				if (bNeedHideLoaders)
				{
					app.hideLoadingScreen();
					app.pullDownLoadingStop();
				}

				MBTasks.getExtendedTaskData(
					function(detailData)
					{
						MBTasks.CPT.view.renderDetailData(detailData);
					},
					function(commentsData)
					{
						MBTasks.CPT.view.renderCommentsData(commentsData);
					}
				);
			}
		);
	}

	MBTasks.reloadPageAndCache = function(task_id, params)
	{
		var params = params || {showPopupLoader: true};

		if (params.showPopupLoader)
			app.showPopupLoader();

		// Change params for page (reload base data)
		MBTasks.getAllTaskDataWoCache(
			task_id,
			function(baseData){
				app.getPageParams({ callback: function(prevData) {
					if ( ( ! prevData )
						|| ( ! prevData.task_id )
					)
					{
						return;
					}

					for (var key in baseData)
					{
						if (baseData.hasOwnProperty(key))
							prevData[key] = baseData[key];
					}

					prevData.status_id = baseData.status_id;
					prevData.status_formatted_name = baseData.status_formatted_name;

					app.changeCurPageParams({
						data: prevData,
						callback: function()
						{
							var baseDataChanged = true;
							MBTasks.pageOpened(baseDataChanged);
							app.pullDownLoadingStop();
							app.hidePopupLoader();
						}
					});
				}});	
			},
			function(){},
			function(detailData){}
		);
	}

	BX.addCustomEvent(
		'onTaskEditPerfomed', 
		function(datum)
		{
			if (datum.rc < 1)
				return;

			var task_id = datum.rc;
			MBTasks.reloadPageAndCache (task_id);
		}
	);

	BX.addCustomEvent(
		'onTaskActionBeforePerfome', 
		function(datum)
		{
			MBTasks.CPT.router.UUIDs_processed.push(datum.UUID);
		}
	);

	BX.addCustomEvent(
		'onTaskActionPerfomed', 
		function(datum)
		{
			// ignore incorrect events
			if ( ! (
				datum
				&& (datum.module_id) 
				&& (datum.module_id === 'tasks')
				&& (datum.action)
			))
				return;

			var data = datum.data;

			if (datum.action === 'remove')
			{
				if (datum.rc !== 'executed')
					return;

				// Clean data from cache
				MBTasks.clearCacheForTask(data.task_id);

				// Show 'task deleted' screen and back to
				if (MBTasks.residentTaskId == data.task_id)
				{
					//MBTasks.CPT.router.switchToMatrix('removed-task')
					var phrase = BX.message('MB_TASKS_TASK_DETAIL_TASK_WAS_REMOVED');

					document.getElementById('tasks-view-title').innerHTML 
						= phrase.replace('#TASK_ID#', data.task_id);

					app.closeController({drop: true}); // back somewhere
				}
			}
			else if ((datum.action === 'edit') || (datum.action === 'delegate'))
			{
				// Register data in cache
				if (data.commentsData)
					MBTasks.addCommentsToCache(data.task_id, data.commentsData)

				if (data.detailsData)
					MBTasks.addDetailsToCache(data.task_id, data.detailsData)

				// Change params for page
				var x = (function(baseData){
					app.getPageParams({ callback: function(prevData) {
						if ( ( ! prevData )
							|| ( ! prevData.task_id )
							|| (prevData.task_id != baseData.task_id)
						)
						{
							return;
						}

						for (var key in baseData)
						{
							if (baseData.hasOwnProperty(key))
								prevData[key] = baseData[key];
						}

						app.changeCurPageParams({
							data: prevData,
							callback: function()
							{
								var baseDataChanged = true;
								MBTasks.pageOpened(baseDataChanged);
							}
						});
					}});				
				})(data.baseData);
			}
			else
			{
				// Register data in cache
				if (data.commentsData)
					MBTasks.addCommentsToCache(data.task_id, data.commentsData)

				if (data.detailsData)
					MBTasks.addDetailsToCache(data.task_id, data.detailsData)

				if (MBTasks.residentTaskId == data.task_id)
				{
					// Change params for page
					var x = (function(baseData){
						app.getPageParams({ callback: function(prevData) {
							if ( ( ! prevData )
								|| ( ! prevData.task_id )
								|| (prevData.task_id != baseData.task_id)
							)
							{
								return;
							}

							prevData.status_id = baseData.status_id;
							prevData.status_formatted_name = baseData.status_formatted_name;

							app.changeCurPageParams({
								data: prevData,
								callback: function()
								{
									var baseDataChanged = true;
									MBTasks.pageOpened(baseDataChanged);
								}
							});
						}});				
					})(data.baseData);
				}
			}
		}
	);

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
}

BX.ready(function(){
	__MBTasks__mobile_tasks_snmrouter_init();

	BX.addCustomEvent(
		'onOpenPageBefore', 
		function() { MBTasks.pageOpened() }
	);

	BX.addCustomEvent(
		'onHidePageAfter', 
		function() { 
			MBTasks.pageHided()
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
		MBTasks.onPullHandler
	);
});

ReadyDevice(
	function()
	{
		MBTasks.pageOpened();
	}
)