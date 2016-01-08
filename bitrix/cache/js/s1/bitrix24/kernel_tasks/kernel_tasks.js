; /* /bitrix/js/tasks/cjstask.js?145227747817750*/
; /* /bitrix/js/tasks/task-quick-popups.min.js?145227747818988*/
; /* /bitrix/js/tasks/task-iframe-popup.js?145227747828206*/
; /* /bitrix/js/tasks/core_planner_handler.min.js?145227747812430*/

; /* Start:"a:4:{s:4:"full";s:43:"/bitrix/js/tasks/cjstask.js?145227747817750";s:6:"source";s:27:"/bitrix/js/tasks/cjstask.js";s:3:"min";s:0:"";s:3:"map";s:0:"";}"*/
(function() {

if (BX.CJSTask)
	return;

BX.CJSTask = {
	ajaxUrl    : '/bitrix/components/bitrix/tasks.iframe.popup/ajax.php?SITE_ID=' + BX.message('SITE_ID'),
	sequenceId : 0,
	timers     : {}
};


BX.CJSTask.getMessagePlural = function(n, msgId)
{
	var pluralForm, langId;

	langId = BX.message('LANGUAGE_ID');
	n = parseInt(n);

	if (n < 0)
		n = (-1) * n;

	if (langId)
	{
		switch (langId)
		{
			case 'de':
			case 'en':
				pluralForm = ((n !== 1) ? 1 : 0);
			break;

			case 'ru':
			case 'ua':
				pluralForm = ( ((n%10 === 1) && (n%100 !== 11)) ? 0 : (((n%10 >= 2) && (n%10 <= 4) && ((n%100 < 10) || (n%100 >= 20))) ? 1 : 2) );
			break;

			default:
				pluralForm = 1;
			break;
		}
	}
	else
		pluralForm = 1;

	return (BX.message(msgId + '_PLURAL_' + pluralForm));
};


BX.CJSTask.createItem = function(newTaskData, params)
{
	var params = params || null;
	var columnsIds = null;

	if (params.columnsIds)
		columnsIds = params.columnsIds;

	var postData = {
		sessid : BX.message('bitrix_sessid'),
		batch  : [
			{
				operation : 'CTaskItem::add()',
				taskData  :  newTaskData
			},
			{
				operation : 'CTaskItem::getTaskData()',
				taskData  : {
					ID : '#RC#$arOperationsResults#-1#justCreatedTaskId'
				}
			},
			{
				operation : 'CTaskItem::getAllowedTaskActions()',
				taskData  : {
					ID : '#RC#$arOperationsResults#-1#returnValue#ID'
				}
			},
			{
				operation : 'NOOP'
			},
			{
				operation : 'CTaskItem::getAllowedTaskActionsAsStrings()',
				taskData  : {
					ID : '#RC#$arOperationsResults#-3#returnValue#ID'
				}
			},
			{
				operation : 'tasksRenderJSON() && tasksRenderListItem()',
				taskData  : {
					ID : '#RC#$arOperationsResults#-4#returnValue#ID'
				},
				columnsIds : columnsIds
			}
		]
	};

	BX.ajax({
		method      : 'POST',
		dataType    : 'json',
		url         :  BX.CJSTask.ajaxUrl,
		data        :  postData,
		processData :  true,
		onsuccess   : (function(params) {
			var callbackOnSuccess = false;
			var callbackOnFailure = false;

			if (params)
			{
				if (params.callback)
					callbackOnSuccess = params.callback;

				if (params.callbackOnFailure)
					callbackOnFailure = params.callbackOnFailure;
			}

			return function(reply) {
				if ((reply.status === 'success') && (!!callbackOnSuccess))
				{
					var precachedData = {
						taskData                    : reply['data'][1]['returnValue'],
						allowedTaskActions          : reply['data'][2]['returnValue'],
						allowedTaskActionsAsStrings : reply['data'][4]['returnValue']
					}

					var oTask = new BX.CJSTask.Item(
						reply['data'][1]['returnValue']['ID'],
						precachedData
					);

					var legacyDataFormat = BX.parseJSON(reply['data'][5]['returnValue']['tasksRenderJSON']);
					var legacyHtmlTaskItem = reply['data'][5]['returnValue']['tasksRenderListItem'];

					callbackOnSuccess(oTask, precachedData, legacyDataFormat, legacyHtmlTaskItem);
				}
				else if ((reply.status !== 'success') && (!!callbackOnFailure))
				{
					var errMessages = [];
					var errorsCount = 0;

					if (
						(reply.repliesCount > 0)
						&& reply.data[reply.repliesCount - 1].hasOwnProperty('errors')
					)
					{
						errorsCount = reply.data[reply.repliesCount - 1].errors.length;

						for (var i = 0; i < errorsCount; i++)
							errMessages.push(reply.data[reply.repliesCount - 1].errors[i]['text']);
					}

					callbackOnFailure({
						rawReply    : reply,
						status      : reply.status,
						errMessages : errMessages
					});
				}
			}
		})(params)
	});
};


BX.CJSTask.Item = function(taskId, precachedData)
{
	if ( ! taskId )
		throw ('taskId must be set');

	if ( ! (taskId >= 1) )
		throw ('taskId must be >= 1');

	this.taskId = taskId;
	this.cachedData = {
		taskData                    : false,
		allowedTaskActions          : false,
		allowedTaskActionsAsStrings : false
	};

	if (precachedData)
	{
		if (precachedData.taskData)
			this.cachedData.taskData = precachedData.taskData;

		if (precachedData.allowedTaskActions)
			this.cachedData.allowedTaskActions = precachedData.allowedTaskActions;

		if (precachedData.allowedTaskActionsAsStrings)
			this.cachedData.allowedTaskActionsAsStrings = precachedData.allowedTaskActionsAsStrings;
	}


	this.getCachedData = function()
	{
		return (this.cachedData);
	};


	this.refreshCache = function(params)
	{
		var params = params || null;

		var postData = {
			sessid : BX.message('bitrix_sessid'),
			batch  : [
				{
					operation : 'CTaskItem::getTaskData()',
					taskData  : {
						ID : this.taskId
					}
				},
				{
					operation : 'CTaskItem::getAllowedTaskActions()',
					taskData  : {
						ID : this.taskId
					}
				},
				{
					operation : 'CTaskItem::getAllowedTaskActionsAsStrings()',
					taskData  : {
						ID : this.taskId
					}
				}
			]
		};

		BX.ajax({
			method      : 'POST',
			dataType    : 'json',
			url         :  BX.CJSTask.ajaxUrl,
			data        :  postData,
			processData :  true,
			onsuccess   : (function(params, objTask) {
				var callback = false;

				if (params && params.callback)
					callback = params.callback;

				return function(reply) {
					objTask.cachedData = {
						taskData                    : reply['data'][0]['returnValue'],
						allowedTaskActions          : reply['data'][1]['returnValue'],
						allowedTaskActionsAsStrings : reply['data'][2]['returnValue']
					}

					if (!!callback)
						callback(objTask.cachedData);
				}
			})(params, this)
		});
	};


	this.complete = function(params)
	{
		var postData = {
			sessid : BX.message('bitrix_sessid'),
			batch  : [
				{
					operation : 'CTaskItem::complete()',
					taskData  : {
						ID : this.taskId
					}
				},
				{
					operation : 'CTaskItem::getTaskData()',
					taskData  : {
						ID : this.taskId
					}
				},
				{
					operation : 'CTaskItem::getAllowedTaskActions()',
					taskData  : {
						ID : '#RC#$arOperationsResults#-1#returnValue#ID'
					}
				},
				{
					operation : 'CTaskItem::getAllowedTaskActionsAsStrings()',
					taskData  : {
						ID : '#RC#$arOperationsResults#-2#returnValue#ID'
					}
				}
			]
		};

		BX.ajax({
			method      : 'POST',
			dataType    : 'json',
			url         :  BX.CJSTask.ajaxUrl,
			data        :  postData,
			processData :  true,
			onsuccess   : (function(params) {
				var callbackOnSuccess = false;
				var callbackOnFailure = false;

				if (params)
				{
					if (params.callbackOnSuccess)
						callbackOnSuccess = params.callbackOnSuccess;

					if (params.callbackOnFailure)
						callbackOnFailure = params.callbackOnFailure;
				}

				return function(reply) {
					if ((reply.status === 'success') && (!!callbackOnSuccess))
					{
						var precachedData = {
							taskData                    : reply['data'][1]['returnValue'],
							allowedTaskActions          : reply['data'][2]['returnValue'],
							allowedTaskActionsAsStrings : reply['data'][3]['returnValue']
						}

						var oTask = new BX.CJSTask.Item(
							reply['data'][1]['returnValue']['ID'],
							precachedData
						);

						callbackOnSuccess(oTask);
					}
					else if ((reply.status !== 'success') && (!!callbackOnFailure))
					{
						var errMessages = [];
						var errorsCount = 0;

						if (
							(reply.repliesCount > 0)
							&& reply.data[reply.repliesCount - 1].hasOwnProperty('errors')
						)
						{
							errorsCount = reply.data[reply.repliesCount - 1].errors.length;

							for (var i = 0; i < errorsCount; i++)
								errMessages.push(reply.data[reply.repliesCount - 1].errors[i]['text']);
						}

						callbackOnFailure({
							rawReply    : reply,
							status      : reply.status,
							errMessages : errMessages
						});
					}
				}
			})(params)
		});
	};


	this.startExecutionOrRenewAndStart = function(params)
	{
		var postData = {
			sessid : BX.message('bitrix_sessid'),
			batch  : [
				{
					operation : 'CTaskItem::startExecutionOrRenewAndStart',
					taskData  : {
						ID : this.taskId
					}
				},
				{
					operation : 'CTaskItem::getTaskData()',
					taskData  : {
						ID : this.taskId
					}
				},
				{
					operation : 'CTaskItem::getAllowedTaskActions()',
					taskData  : {
						ID : '#RC#$arOperationsResults#-1#returnValue#ID'
					}
				},
				{
					operation : 'CTaskItem::getAllowedTaskActionsAsStrings()',
					taskData  : {
						ID : '#RC#$arOperationsResults#-2#returnValue#ID'
					}
				}
			]
		};

		BX.ajax({
			method      : 'POST',
			dataType    : 'json',
			url         :  BX.CJSTask.ajaxUrl,
			data        :  postData,
			processData :  true,
			onsuccess   : (function(params) {
				var callbackOnSuccess = false;
				var callbackOnFailure = false;

				if (params)
				{
					if (params.callbackOnSuccess)
						callbackOnSuccess = params.callbackOnSuccess;

					if (params.callbackOnFailure)
						callbackOnFailure = params.callbackOnFailure;
				}

				return function(reply) {
					if ((reply.status === 'success') && (!!callbackOnSuccess))
					{
						var precachedData = {
							taskData                    : reply['data'][1]['returnValue'],
							allowedTaskActions          : reply['data'][2]['returnValue'],
							allowedTaskActionsAsStrings : reply['data'][3]['returnValue']
						}

						var oTask = new BX.CJSTask.Item(
							reply['data'][1]['returnValue']['ID'],
							precachedData
						);

						callbackOnSuccess(oTask);
					}
					else if ((reply.status !== 'success') && (!!callbackOnFailure))
					{
						var errMessages = [];
						var errorsCount = 0;

						if (
							(reply.repliesCount > 0)
							&& reply.data[reply.repliesCount - 1].hasOwnProperty('errors')
						)
						{
							errorsCount = reply.data[reply.repliesCount - 1].errors.length;

							for (var i = 0; i < errorsCount; i++)
								errMessages.push(reply.data[reply.repliesCount - 1].errors[i]['text']);
						}

						callbackOnFailure({
							rawReply    : reply,
							status      : reply.status,
							errMessages : errMessages
						});
					}
				}
			})(params)
		});
	};


	/**
	 * data is array with elements MINUTES, COMMENT_TEXT
	 */
	this.addElapsedTime = function(data, callbacks)
	{
		var elapsedTimeData = {
			TASK_ID      : this.taskId,
			MINUTES      : data.MINUTES,
			COMMENT_TEXT : data.COMMENT_TEXT
		};

		var batchId = BX.CJSTask.batchOperations(
			[
				{
					operation       : 'CTaskItem::addElapsedTime()',
					elapsedTimeData :  elapsedTimeData
				}
			],
			callbacks
		);

		return (batchId);
	};


	this.checklistAddItem = function(title, callbacks)
	{
		var arFields = {
			TITLE : title
		};

		var batchId = BX.CJSTask.batchOperations(
			[
				{
					operation     : 'CTaskCheckListItem::add()',
					checklistData :  arFields,
					taskId        :  this.taskId
				}
			],
			callbacks
		);

		return (batchId);
	};


	this.checklistRename = function(id, newTitle, callbacks)
	{
		var arFields = {
			TITLE : newTitle
		};

		var batchId = BX.CJSTask.batchOperations(
			[
				{
					operation     : 'CTaskCheckListItem::update()',
					checklistData :  arFields,
					itemId        :  id,
					taskId        :  this.taskId
				}
			],
			callbacks
		);

		return (batchId);
	};


	this.checklistComplete = function(id, callbacks)
	{
		var batchId = BX.CJSTask.batchOperations(
			[
				{
					operation : 'CTaskCheckListItem::complete()',
					itemId    :  id,
					taskId    :  this.taskId
				}
			],
			callbacks
		);

		return (batchId);
	};


	this.checklistRenew = function(id, callbacks)
	{
		var batchId = BX.CJSTask.batchOperations(
			[
				{
					operation : 'CTaskCheckListItem::renew()',
					itemId    :  id,
					taskId    :  this.taskId
				}
			],
			callbacks
		);

		return (batchId);
	};


	this.checklistDelete = function(id, callbacks)
	{
		var batchId = BX.CJSTask.batchOperations(
			[
				{
					operation : 'CTaskCheckListItem::delete()',
					itemId    :  id,
					taskId    :  this.taskId
				}
			],
			callbacks
		);

		return (batchId);
	};


	this.checklistMoveAfterItem = function(id, insertAfterItemId, callbacks)
	{
		var batchId = BX.CJSTask.batchOperations(
			[
				{
					operation         : 'CTaskCheckListItem::moveAfterItem()',
					itemId            :  id,
					taskId            :  this.taskId,
					insertAfterItemId :  insertAfterItemId
				}
			],
			callbacks
		);

		return (batchId);
	};


	this.stopWatch = function(callbacks)
	{
		var batchId = BX.CJSTask.batchOperations(
			[
				{
					operation : 'CTaskItem::stopWatch()',
					taskData  : {
						ID : this.taskId
					}
				}
			],
			callbacks
		);

		return (batchId);
	};


	this.startWatch = function(callbacks)
	{
		var batchId = BX.CJSTask.batchOperations(
			[
				{
					operation : 'CTaskItem::startWatch()',
					taskData  : {
						ID : this.taskId
					}
				}
			],
			callbacks
		);

		return (batchId);
	};
};


BX.CJSTask.TimerManager = function(taskId)
{
	if ( ! taskId )
		throw ('taskId must be set');

	if ( ! (taskId >= 1) )
		throw ('taskId must be >= 1');

	this.taskId = taskId;

	this.__private = {
		startOrStop : function(operation, taskId, callbacks)
		{
			var batchId = BX.CJSTask.batchOperations(
				[
					{
						operation : operation,
						taskData  : {
							ID : taskId
						}
					},
					{
						operation : 'CTaskItem::getTaskData()',
						taskData  : {
							ID : '#RC#$arOperationsResults#-1#requestedTaskId'
						}
					},
					{
						operation : 'CTaskTimerManager::getLastTimer()'
					}
				],
				callbacks
			);

			return (batchId);
		}
	};


	this.start = function(callbacks)
	{
		var batchId = this.__private.startOrStop('CTaskTimerManager::start()', this.taskId, callbacks);
		return (batchId);
	};


	this.stop = function(callbacks)
	{
		var batchId = this.__private.startOrStop('CTaskTimerManager::stop()', this.taskId, callbacks);
		return (batchId);
	};
};


BX.CJSTask.setTimerCallback = function(timerCodeName, callback, milliseconds)
{
	if (BX.CJSTask[timerCodeName])
	{
		window.clearInterval(BX.CJSTask[timerCodeName]);
		BX.CJSTask[timerCodeName] = null;
	}

	if (callback !== null)
		BX.CJSTask[timerCodeName] = window.setInterval(callback, milliseconds);
};


BX.CJSTask.formatUsersNames = function(arUsersIds, params)
{
	var params = params || null;

	var userId = null;
	var batch  = [];

	for (var key in arUsersIds)
	{
		userId = arUsersIds[key];

		batch.push({
			operation : 'CUser::FormatName()',
			userData  :  { ID : userId }
		});
	}

	var postData = {
		sessid : BX.message('bitrix_sessid'),
		batch  : batch
	};

	BX.ajax({
		method      : 'POST',
		dataType    : 'json',
		url         :  BX.CJSTask.ajaxUrl,
		data        :  postData,
		processData :  true,
		onsuccess   : (function(params) {
			var callback = false;

			if (params && params.callback)
				callback = params.callback;

			return function(reply) {
				if (!!callback)
				{
					var replyItem = null;
					var result = {};
					var repliesCount = reply['repliesCount'];

					for (var i = 0; i < repliesCount; i++)
					{
						replyItem = reply['data'][i];
						result['u' + replyItem['requestedUserId']] = replyItem['returnValue'];
					}

					callback(result);
				}
			}
		})(params)
	});
}


BX.CJSTask.getGroupsData = function(arGroupsIds, params)
{
	var params = params || null;

	var groupId = null;
	var batch   = [];

	for (var key in arGroupsIds)
	{
		groupId = arGroupsIds[key];

		batch.push({
			operation : 'CSocNetGroup::GetByID()',
			groupData  :  { ID : groupId }
		});
	}

	var postData = {
		sessid : BX.message('bitrix_sessid'),
		batch  : batch
	};

	BX.ajax({
		method      : 'POST',
		dataType    : 'json',
		url         :  BX.CJSTask.ajaxUrl,
		data        :  postData,
		processData :  true,
		onsuccess   : (function(params) {
			var callback = false;

			if (params && params.callback)
				callback = params.callback;

			return function(reply) {
				if (!!callback)
				{
					var replyItem = null;
					var result = {};
					var repliesCount = reply['repliesCount'];

					for (var i = 0; i < repliesCount; i++)
					{
						replyItem = reply['data'][i];
						result[replyItem['requestedGroupId']] = replyItem['returnValue'];
					}

					callback(result);
				}
			}
		})(params)
	});
}


BX.CJSTask.batchOperations = function(batch, callbacks, sync)
{
	var callbacks = callbacks || null;
	var sync = sync || false;
	var batchId   = 'batch_sequence_No_' + (++BX.CJSTask.sequenceId);

	var postData = {
		sessid  : BX.message('bitrix_sessid'),
		batch   : batch,
		batchId : batchId
	};

	BX.ajax({
		method      : 'POST',
		dataType    : 'json',
		url         :  BX.CJSTask.ajaxUrl,
		data        :  postData,
		async       :  !sync,
		processData :  true,
		onsuccess   : (function(callbacks) {
			var callbackOnSuccess = false;
			var callbackOnFailure = false;

			if (callbacks)
			{
				if (callbacks.callbackOnSuccess)
					callbackOnSuccess = callbacks.callbackOnSuccess;

				if (callbacks.callbackOnFailure)
					callbackOnFailure = callbacks.callbackOnFailure;
			}

			return function(reply) {
				if ((reply.status === 'success') && (!!callbackOnSuccess))
				{
					callbackOnSuccess({
						rawReply : reply,
						status   : reply.status
					});
				}
				else if ((reply.status !== 'success') && (!!callbackOnFailure))
				{
					var errMessages = [];
					var errorsCount = 0;

					if (
						(reply.repliesCount > 0)
						&& reply.data[reply.repliesCount - 1].hasOwnProperty('errors')
					)
					{
						errorsCount = reply.data[reply.repliesCount - 1].errors.length;

						for (var i = 0; i < errorsCount; i++)
							errMessages.push(reply.data[reply.repliesCount - 1].errors[i]['text']);
					}

					callbackOnFailure({
						rawReply    : reply,
						status      : reply.status,
						errMessages : errMessages
					});
				}
			}
		})(callbacks)
	});

	return (batchId);
}

})();

/* End */
;
; /* Start:"a:4:{s:4:"full";s:57:"/bitrix/js/tasks/task-quick-popups.min.js?145227747818988";s:6:"source";s:37:"/bitrix/js/tasks/task-quick-popups.js";s:3:"min";s:41:"/bitrix/js/tasks/task-quick-popups.min.js";s:3:"map";s:41:"/bitrix/js/tasks/task-quick-popups.map.js";}"*/
(function(){if(!BX.Tasks)BX.Tasks={};if(BX.Tasks.lwPopup)return;BX.Tasks.lwPopup={ajaxUrl:"/bitrix/components/bitrix/tasks.list/ajax.php",onTaskAdded:null,onTaskAddedMultiple:null,loggedInUserId:null,loggedInUserFormattedName:null,garbageAreaId:"garbageAreaId_id",functions:{},functionsCount:0,firstRunDone:false,createForm:{objPopup:null,objTemplate:null,callbacks:{onAfterPopupCreated:null,onBeforePopupShow:null,onAfterPopupShow:null,onAfterEditorInited:null,onPopupClose:null}},anyForm:[],anyFormsCount:0,registerForm:function(e){e=e||{callbacks:{}};var t=this.anyFormsCount++;this.anyForm[t]={formIndex:t,objPopup:null,objTemplate:null,callbacks:e.callbacks};return this.anyForm[t]},__runAnyFormCallback:function(e,t,a){a=a||[];if(!this.anyForm[e])throw Error("Form with index "+e+" not exists");if(BX.Tasks.lwPopup.anyForm[e].callbacks.hasOwnProperty(t)&&BX.Tasks.lwPopup.anyForm[e].callbacks[t]!==null){BX.Tasks.lwPopup.anyForm[e].callbacks[t].apply(BX.Tasks.lwPopup.anyForm[e].objTemplate,a)}},showForm:function(e,t){t=typeof t!=="undefined"?t:{};if(!this.anyForm[e])throw Error("Form with index "+e+" not exists");var a=this.anyForm[e];BX.Tasks.lwPopup.__firstRun();var n=false;if(a.objPopup===null){this.buildForm(e,t);n=true}this.__runAnyFormCallback(e,"onBeforePopupShow",[t,{isPopupJustCreated:n}]);a.objPopup.show()},buildForm:function(e,t,a){var n=-110;t=typeof t!=="undefined"?t:{};if(typeof a!=="undefined")n=a;if(!this.anyForm[e])throw Error("Form with index "+e+" not exists");var s=this.anyForm[e];BX.Tasks.lwPopup.__firstRun();s.objPopup=new BX.PopupWindow("bx-tasks-quick-popup-anyForm-"+e,null,{zIndex:n,autoHide:false,buttons:s.objTemplate.prepareButtons(),closeByEsc:false,overlay:true,draggable:true,bindOnResize:false,titleBar:s.objTemplate.prepareTitleBar(),closeIcon:{right:"12px",top:"10px"},events:{onPopupClose:function(){BX.Tasks.lwPopup.__runAnyFormCallback(e,"onPopupClose",[])},onPopupFirstShow:function(){BX.Tasks.lwPopup.__runAnyFormCallback(e,"onPopupFirstShow",[])},onPopupShow:function(){BX.Tasks.lwPopup.__runAnyFormCallback(e,"onPopupShow",[])},onAfterPopupShow:function(){BX.Tasks.lwPopup.__runAnyFormCallback(e,"onAfterPopupShow",[])}},content:s.objTemplate.prepareContent(t)});this.__runAnyFormCallback(e,"onAfterPopupCreated",[t])},__runCreateFormCallback:function(e,t){t=t||[];if(BX.Tasks.lwPopup.createForm.callbacks.hasOwnProperty(e)&&BX.Tasks.lwPopup.createForm.callbacks[e]!==null){BX.Tasks.lwPopup.createForm.callbacks[e].apply(BX.Tasks.lwPopup.createForm.objTemplate,t)}},showCreateForm:function(e){e=typeof e!=="undefined"?e:{};BX.Tasks.lwPopup.__firstRun();if(!e.RESPONSIBLE_ID){e.RESPONSIBLE_ID=BX.Tasks.lwPopup.loggedInUserId;e["META:RESPONSIBLE_FORMATTED_NAME"]=BX.Tasks.lwPopup.loggedInUserFormattedName}else if(e.RESPONSIBLE_ID==BX.Tasks.lwPopup.loggedInUserId&&!e.hasOwnProperty("META:RESPONSIBLE_FORMATTED_NAME")){e["META:RESPONSIBLE_FORMATTED_NAME"]=BX.Tasks.lwPopup.loggedInUserFormattedName}var t=false;if(BX.Tasks.lwPopup.createForm.objPopup===null){BX.Tasks.lwPopup.createForm.objPopup=new BX.PopupWindow("bx-tasks-quick-popup-create-new-task",null,{zIndex:-110,autoHide:false,buttons:BX.Tasks.lwPopup.createForm.objTemplate.prepareButtons(),closeByEsc:false,overlay:true,draggable:true,bindOnResize:false,titleBar:BX.Tasks.lwPopup.createForm.objTemplate.prepareTitleBar(),closeIcon:{right:"12px",top:"10px"},events:{onPopupClose:function(){BX.Tasks.lwPopup.__runCreateFormCallback("onPopupClose",[])},onPopupFirstShow:function(){},onPopupShow:function(){},onAfterPopupShow:function(){if(BX("bx-panel")&&parseInt(BX.Tasks.lwPopup.createForm.objPopup.popupContainer.style.top)<147){BX.Tasks.lwPopup.createForm.objPopup.popupContainer.style.top=147+"px"}BX.Tasks.lwPopup.__runCreateFormCallback("onAfterPopupShow",[])}},content:BX.Tasks.lwPopup.createForm.objTemplate.prepareContent(e)});BX.Tasks.lwPopup.__runCreateFormCallback("onAfterPopupCreated",[e]);t=true}BX.Tasks.lwPopup.__runCreateFormCallback("onBeforePopupShow",[e,{isPopupJustCreated:t}]);BX.Tasks.lwPopup.createForm.objPopup.show()},_createTask:function(e){e=e||{};var t=false;var a=null;var n=null;var s=null;var o={};if(e.hasOwnProperty("taskData"))o=e.taskData;if(e.hasOwnProperty("onceMore"))t=e.onceMore;if(e.hasOwnProperty("columnsIds"))s=e.columnsIds;if(e.hasOwnProperty("callbackOnSuccess"))a=e.callbackOnSuccess;if(e.hasOwnProperty("callbackOnFailure"))n=e.callbackOnFailure;if(!o.hasOwnProperty("TITLE"))o.TITLE="";if(!o.hasOwnProperty("RESPONSIBLE_ID"))o.RESPONSIBLE_ID=this.loggedInUserId;BX.CJSTask.createItem(o,{columnsIds:s,callback:function(e,t){return function(a,n,s,o){var r={oTask:a,taskData:n.taskData,allowedTaskActions:n.allowedTaskActions,allowedTaskActionsAsStrings:n.allowedTaskActionsAsStrings,params:{onceMore:e}};if(t)t(r);if(BX.Tasks.lwPopup.onTaskAdded&&e===false)BX.Tasks.lwPopup.onTaskAdded(s,null,null,r,o);else if(BX.Tasks.lwPopup.onTaskAddedMultiple&&e===true)BX.Tasks.lwPopup.onTaskAddedMultiple(s,null,null,r,o)}}(t,a),callbackOnFailure:function(e){return function(t){if(e)e(t)}}(n)})},__initSelectors:function(e){var t=e.length;var a=false;BX.Tasks.lwPopup.__firstRun();for(var n=0;n<t;n++){if(e[n]["requestedObject"]==="intranet.user.selector.new"){a=true;break}}var s=null;if(a){var o=BX.Tasks.lwPopup.functionsCount++;BX.Tasks.lwPopup.functions["f"+o]=function(){};s=BX.Tasks.lwPopup.garbageAreaId+"__userSelectors_"+o+"_loadedHtml";BX(BX.Tasks.lwPopup.garbageAreaId).appendChild(BX.create("DIV",{props:{id:s}}))}var r={sessid:BX.message("bitrix_sessid"),requestsCount:t};var i=[];var l=[];for(var n=0;n<t;n++){if(e[n]["requestedObject"]==="intranet.user.selector.new")i[n]=this.__prepareUserSelectorsData(e[n]);else if(e[n]["requestedObject"]==="socialnetwork.group.selector")i[n]=this.__prepareGroupsSelectorsData(e[n]);else if(e[n]["requestedObject"]==="LHEditor")i[n]=this.__prepareLheData(e[n]);else if(e[n]["requestedObject"]==="system.field.edit::CRM"){i[n]=this.__prepareUserFieldData(e[n]);for(var u in i[n]["postData"])r[u]=i[n]["postData"][u]}else if(e[n]["requestedObject"]==="system.field.edit::WEBDAV"){i[n]=this.__prepareUserFieldDataWebdav(e[n]);for(var u in i[n]["postData"])r[u]=i[n]["postData"][u]}r["data_"+n]=i[n]["ajaxParams"];l[n]=i[n]["object"]}BX.ajax({method:"POST",dataType:"html",url:"/bitrix/components/bitrix/tasks.iframe.popup/ajax_loader.php?SITE_ID="+BX.message("SITE_ID"),data:r,processData:true,autoAuth:true,onsuccess:function(e,t,a){return function(n){if(a)BX(t).innerHTML=n;var s=e.length;for(var o=0;o<s;o++){if(e[o].hasOwnProperty("onLoadedViaAjax"))e[o].onLoadedViaAjax()}}}(l,s,a)});return l},__prepareUserFieldData:function(e){var t=BX.Tasks.lwPopup.functionsCount++;var a="OBJ_TASKS_CONTAINER_NAME_ID_"+t;var n="OBJ_TASKS_CONTAINER_DATA_ID_"+t;var s={requestedObject:"system.field.edit::CRM",userFieldName:e["userFieldName"],taskId:e["taskId"],nameContainerId:a,dataContainerId:n,values:e["value"]};var o=[];o.push.apply(o,e["value"]);BX.Tasks.lwPopup.functions["f"+t]={allParams:e,ajaxParams:s,ready:false,available:null,timeoutId:null,valuesBuffer:o,nameContainerId:a,dataContainerId:n,onLoadedViaAjax:function(){if(BX(this.nameContainerId))this.available=true;else this.available=false;if(!this.available)return false;var e=BX(this.nameContainerId).innerHTML;BX.remove(BX(this.nameContainerId));this.allParams.callbackOnRedraw(e,this.dataContainerId);this.ready=true},getValue:function(){var e=[];if(this.ready===true){var t=document.getElementsByName("UF_CRM_TASK[]");if(t){var a=t.length;for(var n=0;n<a;n++)e.push(t[n].value)}}else{e=this.valuesBuffer}return e},setValue:function(e){if(this.valuesBuffer.length===e.length){var t=this.valuesBuffer.slice().sort().join(";");var a=e.slice().sort().join(";");if(t===a)return}this.valuesBuffer=[];this.valuesBuffer.push.apply(this.valuesBuffer,e);this.__delayedSetContent(30)},__delayedSetContent:function(e){if(this.available===false)return false;if(this.ready===false){if(this.timeoutId!==null)window.clearTimeout(this.timeoutId);this.timeoutId=window.setTimeout(function(){var a=e+100;if(e<30)a=30;else if(e>500)a=500;BX.Tasks.lwPopup.functions["f"+t].__delayedSetContent(a)},e)}else{if(BX(this.nameContainerId))BX.remove(BX(this.nameContainerId));if(BX(this.dataContainerId))BX.remove(BX(this.dataContainerId));var a="";var n=this.valuesBuffer.length;for(var s=0;s<n;s++)a=a+"&UF_CRM_TASK[]="+this.valuesBuffer[s];var o={sessid:BX.message("bitrix_sessid"),requestsCount:1,data_0:this.ajaxParams};BX.ajax({method:"POST",dataType:"html",url:"/bitrix/components/bitrix/tasks.iframe.popup/ajax_loader.php?SITE_ID="+BX.message("SITE_ID")+a,data:o,processData:true,autoAuth:true,onsuccess:function(e){return function(t){BX(BX.Tasks.lwPopup.garbageAreaId).appendChild(BX.create("div",{html:t}));e.ready=true;var a=BX(e.nameContainerId).innerHTML;BX.remove(BX(e.nameContainerId));e.allParams.callbackOnRedraw(a,e.dataContainerId)}}(this)})}}};var r={object:BX.Tasks.lwPopup.functions["f"+t],ajaxParams:s,postData:{UF_CRM_TASK:e["value"]}};return r},__prepareUserFieldDataWebdav:function(e){var t=BX.Tasks.lwPopup.functionsCount++;var a="OBJ_TASKS_CONTAINER_NAME_ID_"+t;var n="OBJ_TASKS_CONTAINER_DATA_ID_"+t;var s={requestedObject:"system.field.edit::WEBDAV",userFieldName:e["userFieldName"],taskId:e["taskId"],nameContainerId:a,dataContainerId:n,values:e["value"]};var o=[];o.push.apply(o,e["value"]);BX.Tasks.lwPopup.functions["f"+t]={allParams:e,ajaxParams:s,ready:false,available:null,timeoutId:null,valuesBuffer:o,nameContainerId:a,dataContainerId:n,onLoadedViaAjax:function(){if(BX(this.nameContainerId))this.available=true;else this.available=false;if(!this.available)return false;var e=BX(this.nameContainerId).innerHTML;BX.remove(BX(this.nameContainerId));this.allParams.callbackOnRedraw(e,this.dataContainerId);this.ready=true},getValue:function(){var e=[];if(this.ready===true){var t=document.getElementsByName("UF_TASK_WEBDAV_FILES[]");if(t){var a=t.length;for(var n=0;n<a;n++)e.push(t[n].value)}}else{e=this.valuesBuffer}return e},setValue:function(e){if(this.valuesBuffer.length===e.length){var t=this.valuesBuffer.slice().sort().join(";");var a=e.slice().sort().join(";");if(t===a)return}this.valuesBuffer=[];this.valuesBuffer.push.apply(this.valuesBuffer,e);this.__delayedSetContent(30)},__delayedSetContent:function(e){if(this.available===false)return false;if(this.ready===false){if(this.timeoutId!==null)window.clearTimeout(this.timeoutId);this.timeoutId=window.setTimeout(function(){var a=e+100;if(e<30)a=30;else if(e>500)a=500;BX.Tasks.lwPopup.functions["f"+t].__delayedSetContent(a)},e)}else{if(BX(this.nameContainerId))BX.remove(BX(this.nameContainerId));if(BX(this.dataContainerId))BX.remove(BX(this.dataContainerId));var a="";var n=this.valuesBuffer.length;for(var s=0;s<n;s++)a=a+"&UF_TASK_WEBDAV_FILES[]="+this.valuesBuffer[s];var o={sessid:BX.message("bitrix_sessid"),requestsCount:1,data_0:this.ajaxParams};BX.ajax({method:"POST",dataType:"html",url:"/bitrix/components/bitrix/tasks.iframe.popup/ajax_loader.php?SITE_ID="+BX.message("SITE_ID")+a,data:o,processData:true,autoAuth:true,onsuccess:function(e){return function(t){BX(BX.Tasks.lwPopup.garbageAreaId).appendChild(BX.create("div",{html:t}));e.ready=true;var a=BX(e.nameContainerId).innerHTML;BX.remove(BX(e.nameContainerId));e.allParams.callbackOnRedraw(a,e.dataContainerId)}}(this)})}}};var r={object:BX.Tasks.lwPopup.functions["f"+t],ajaxParams:s,postData:{UF_TASK_WEBDAV_FILES:e["value"]}};return r},__prepareLheData:function(e){var t=BX.Tasks.lwPopup.functionsCount++;var a="OBJ_TASKS_LHEDITOR_NS_"+t;var n="OBJ_TASKS_ELEMENT_ID_NS_"+t;var s="OBJ_TASKS_INPUT_ID_NS_"+t;BX.Tasks.lwPopup.functions["f"+t]={allParams:e,jsObjectName:a,elementId:n,editor:null,inputId:s,content:"",getContent:function(){if(this.editor!==null){this.editor.SaveContent();return this.editor.GetContent()}else{if(BX(this.inputId))return BX(this.inputId).value;else return""}},setContent:function(e){this["content"]=e;this.__delayedSetContent(30)},__delayedSetContent:function(e){if(this.editor===null){window.setTimeout(function(){var a=e+100;if(e<30)a=30;else if(e>500)a=500;BX.Tasks.lwPopup.functions["f"+t].__delayedSetContent(a)},e)}else{if(BX.type.isString(this["content"])){this.editor.SetContent(this["content"]);if(this["content"].length==0)this.editor.ResizeSceleton(false,200);if(BX.browser.IsChrome()||BX.browser.IsIE11()||BX.browser.IsIE()){var a=BX("lwPopup-task-title");if(BX.type.isElementNode(a)){this.editor.Focus(false);a.focus()}}}}}};BX.addCustomEvent(window,"OnEditorInitedAfter",function(e,t){var a=false;return function(n){if(!a&&n.id==e.elementId){e.editor=n;var s=BX(t);s.innerHTML="";s.appendChild(n.dom.cont);a=true;setTimeout(function(){n.CheckAndReInit();n.SetContent(e["content"]);if(BX.browser.IsChrome()||BX.browser.IsIE11()||BX.browser.IsIE()){var t=BX("lwPopup-task-title");if(BX.type.isElementNode(t)){n.Focus(false);t.focus()}}},500);BX.Tasks.lwPopup.__runCreateFormCallback("onAfterEditorInited",[])}}}(BX.Tasks.lwPopup.functions["f"+t],e.attachTo));var o={object:BX.Tasks.lwPopup.functions["f"+t],ajaxParams:{requestedObject:"LHEditor",jsObjectName:a,elementId:n,inputId:s}};return o},__prepareGroupsSelectorsData:function(e){var t=BX.Tasks.lwPopup.functionsCount++;var a="OBJ_TASKS_GROUP_SELECTOR_NS_"+t;BX.Tasks.lwPopup.functions["f"+t]={allParams:e,jsObjectName:a,bindElement:e.bindElement,onLoadedViaAjax:function(){BX.bind(BX(this.bindElement),"click",function(e){return function(t){if(!t)t=window.event;var a=window[e.jsObjectName];if(a){a.popupWindow.params.zIndex=1400;a.show()}BX.PreventDefault(t)}}(this));if(this.allParams.onLoadedViaAjax)this.allParams.onLoadedViaAjax(this.jsObjectName)},setSelected:function(e){if(!window[this.jsObjectName])return;if(e.id==0){var t=null;if(window[this.jsObjectName].selected[0]){t=window[this.jsObjectName].selected[0];window[this.jsObjectName].deselect(t.id)}}else window[this.jsObjectName].select(e)},deselect:function(e){window[this.jsObjectName].deselect(e)}};var n="FUNC_TASKS_GROUP_SELECTOR_NS_"+t;window[n]=function(e){return function(t){if(e)e(t)}}(e.callbackOnSelect);var s={object:BX.Tasks.lwPopup.functions["f"+t],ajaxParams:{requestedObject:"socialnetwork.group.selector",jsObjectName:a,bindElement:e.bindElement,onSelectFuncName:n}};return s},__prepareUserSelectorsData:function(e){var t=null;var a=null;var n=0;if(e.hasOwnProperty("userInputId"))t=e.userInputId;if(e.hasOwnProperty("bindClickTo"))a=e.bindClickTo;else a=t;var s=e.callbackOnSelect;var o=e.selectedUsersIds;var r=e.anchorId;var l=e["multiple"];var u=BX.Tasks.lwPopup.functionsCount++;var p="OBJ_TASKS_USER_SELECTOR_NS_"+u;if(e.GROUP_ID_FOR_SITE)n=e.GROUP_ID_FOR_SITE;BX.Tasks.lwPopup.functions["f"+u]={allParams:e,multiple:l,popupId:p+"_popupId",bindClickTo:a,userInputId:t,anchorId:r,userPopupWindow:null,nsObjectName:p,onLoadedViaAjax:function(){var e=this;if(this.userInputId){BX.bind(BX(this.userInputId),"focus",function(t){e.showUserSelector(t)});if(BX(this.bindClickTo)){BX.bind(BX(this.bindClickTo),"click",function(t){if(!t)t=window.event;BX(e.userInputId).focus();BX.PreventDefault(t)})}}if(this.allParams.onLoadedViaAjax)this.allParams.onLoadedViaAjax();if(this.allParams.onReady){(function(e,t){var a=function(t,n,s){if(typeof window[e]==="undefined"){if(n>0){window.setTimeout(function(){a(t,n-t,s)},t)}}else{s(window[e])}};a(100,15e3,t)})("O_"+this.nsObjectName,this.allParams.onReady)}},onPopupClose:function(e){var t=window["O_"+e.nsObjectName];var a=t.arSelected.pop();if(a){t.arSelected.push(a);t.searchInput.value=a.name}},setSelectedUsers:function(e,t){var t=t||1;if(t>100)return;if(!window["O_"+this.nsObjectName]){window.setTimeout(function(e,t,a){return function(){e.setSelectedUsers(a,t+1)}}(this,t,e),50);return}var a=window["O_"+this.nsObjectName];a.setSelected(e)},showUserSelector:function(e){if(!e)e=window.event;if(this.userPopupWindow!==null&&this.userPopupWindow.popupContainer.style.display=="block"){return}var t=BX(this.anchorId);var a=null;var n=this;if(this["multiple"]==="Y"){a=[new BX.PopupWindowButton({text:this.allParams.btnSelectText,className:"popup-window-button-accept",events:{click:function(e){n.btnSelectClick(e);n.userPopupWindow.close()}}}),new BX.PopupWindowButtonLink({text:this.allParams.btnCancelText,className:"popup-window-button-link-cancel",events:{click:function(e){if(!e)e=window.event;n.userPopupWindow.close();if(e)BX.PreventDefault(e)}}})]}this.userPopupWindow=BX.PopupWindowManager.create(this.popupId,t,{offsetTop:1,autoHide:true,closeByEsc:true,content:BX(this.nsObjectName+"_selector_content"),buttons:a});if(this["multiple"]==="N"){BX.addCustomEvent(this.userPopupWindow,"onPopupClose",function(){n.onPopupClose(n)})}else{BX.addCustomEvent(this.userPopupWindow,"onAfterPopupShow",function(e){setTimeout(function(){window["O_"+n.nsObjectName].searchInput.focus()},100)})}this.userPopupWindow.show();BX(this.userPopupWindow.uniquePopupId).style.zIndex=1400;BX.focus(t);BX.PreventDefault(e)}};if(l==="N"){BX.Tasks.lwPopup.functions["f"+u].onUserSelect=function(e){var t=BX.Tasks.lwPopup.functions["f"+u];return function(a){if(t.userPopupWindow)t.userPopupWindow.close();e(a)}}(s);BX.Tasks.lwPopup.functions["f"+u].btnSelectClick=function(){}}else{BX.Tasks.lwPopup.functions["f"+u].onUserSelect=function(){};BX.Tasks.lwPopup.functions["f"+u].btnSelectClick=function(e){return function(t){if(!t)t=window.event;var a=window["O_"+this.nsObjectName].arSelected;var n=a.length;var s=[];for(i=0;i<n;i++){if(a[i])s.push(a[i])}e(s)}}(s)}var d={requestedObject:"intranet.user.selector.new",multiple:l,namespace:p,inputId:t,onSelectFunctionName:"BX.Tasks.lwPopup.functions.f"+u+".onUserSelect",GROUP_ID_FOR_SITE:n,selectedUsersIds:o};if(e.callbackOnChange){BX.Tasks.lwPopup.functions["f"+u].onUsersChange=e.callbackOnChange;d.onChangeFunctionName="BX.Tasks.lwPopup.functions.f"+u+".onUsersChange"}var c={object:BX.Tasks.lwPopup.functions["f"+u],ajaxParams:d};return c},_getDefaultTimeForInput:function(e){if(BX.type.isDomNode(e)){var t=BX.data(e,"default-time");if(typeof t!="undefined"){var a=t.toString().split(":");t={h:+a[0],m:+a[1],s:+a[2]}}else{t={h:19,m:0,s:0}}}return t},_showCalendar:function(e,t,a){if(typeof a==="undefined")var a={};var n=true;if(a.hasOwnProperty("bTime"))n=a.bTime;var s=false;if(a.hasOwnProperty("bHideTime"))s=a.bHideTime;var o=null;if(a.hasOwnProperty("callback_after"))o=a.callback_after;var r=new Date;if(!!t.value)var i=t.value;else{var l=this._getDefaultTimeForInput(t);var i=BX.date.convertToUTC(new Date(r.getFullYear(),r.getMonth(),r.getDate(),l.h,l.m,l.s))}BX.calendar({node:e,field:t,bTime:n,value:i,bHideTime:s,currentTime:Math.round(r/1e3)-r.getTimezoneOffset()*60,callback_after:o})},__firstRun:function(){if(BX.Tasks.lwPopup.firstRunDone)return;BX.Tasks.lwPopup.firstRunDone=true;var e=document.getElementsByTagName("body")[0];if(!BX(BX.Tasks.lwPopup.garbageAreaId)){e.appendChild(BX.create("DIV",{props:{id:BX.Tasks.lwPopup.garbageAreaId}}))}}}})();
/* End */
;
; /* Start:"a:4:{s:4:"full";s:53:"/bitrix/js/tasks/task-iframe-popup.js?145227747828206";s:6:"source";s:37:"/bitrix/js/tasks/task-iframe-popup.js";s:3:"min";s:0:"";s:3:"map";s:0:"";}"*/
(function (window) {
	var resizeInterval, lastSrc;
	var lastheight = 0;

	BX.TasksIFramePopup = {
		create : function(params)
		{
			if (!window.top.BX.TasksIFrameInst)
				window.top.BX.TasksIFrameInst = new TasksIFramePopup(params);

			if (params.events)
			{
				for (var eventName in params.events)
					BX.addCustomEvent(window.top.BX.TasksIFrameInst, eventName, params.events[eventName]);
			}

			return window.top.BX.TasksIFrameInst;
		}
	};

	var TasksIFramePopup = function(params) {

		this.inited = false;
		this.pathToEdit = "";
		this.pathToView = "";
		this.iframeWidth = 900;
		this.iframeHeight = 400;
		this.topBottomMargin = 15;
		this.leftRightMargin = 50;
		this.tasksList = [];
		this.currentURL = window.location.href;
		this.currentTaskId = 0;
		this.lastAction = null;
		this.loading = false;
		this.isEditMode = false;
		this.prevIframeSrc = '';
		this.descriptionBuffered = null;

		if (params)
		{
			if (params.pathToEdit)
			{
				this.pathToEdit = params.pathToEdit + (params.pathToEdit.indexOf("?") == -1 ? "?" : "&") + "IFRAME=Y";
			}
			if (params.pathToView)
			{
				this.pathToView = params.pathToView + (params.pathToView.indexOf("?") == -1 ? "?" : "&") + "IFRAME=Y";
			}
			if (params.tasksList)
			{
				for(var i = 0, count = params.tasksList.length; i < count; i++)
				{
					this.tasksList[i] = parseInt(params.tasksList[i]);
				}
			}
		}
	};


	TasksIFramePopup.prototype.init = function() {

		if (this.inited)
			return;

		this.inited = true;

		this.header = BX.create("div", {
			props : {className : "popup-window-titlebar"},
			html : '<table width="877" border="0" cellspacing="0" cellpadding="0"><tbody><tr><td align="left">&nbsp;</td><td width="13" style="padding-top: 2px;"><div class="tasks-iframe-close-icon">&nbsp;</div></td></tr></tbody></table>',
			style : {
				background : "#e8e8e8",
				height : "20px",
				padding : "5px 0px 5px 15px",
				borderRadius : "4px 4px 0px 0px"
			}
		});
		this.title = this.header.firstChild.tBodies[0].rows[0].cells[0];
		this.closeIcon = this.header.firstChild.tBodies[0].rows[0].cells[1].firstChild;
		this.closeIcon.onclick = BX.proxy(this.close, this);
		this.iframe = BX.create("iframe", {
			props : {
				scrolling : "no",
				frameBorder : "0"
			},
			style : {
				width : this.iframeWidth + "px",
				height : this.iframeHeight + "px",
				overflow : "hidden",
				border : "1px solid #fff",
				borderTop : "0px",
				borderRadius : "4px"
			}
		});
		this.prevTaskLink = BX.create("a", {props : {href : "javascript: void(0)", className : "tasks-popup-prev-slide"}, html : "<span></span>"});
		this.closeLink = BX.create("a", {props : {href : "javascript: void(0)", className : "tasks-popup-close"}, html : "<span></span>"});
		this.nextTaskLink = BX.create("a", {props : {href : "javascript: void(0)", className : "tasks-popup-next-slide"}, html : "<span></span>"});

		// Set nav
		this.prevTaskLink.onclick = BX.proxy(this.previous, this);
		this.nextTaskLink.onclick = BX.proxy(this.next, this);
		this.closeLink.onclick = BX.proxy(this.close, this);

		this.table = BX.create("table", {
			props : {className : "tasks-popup-main-table"},
			style : {
				top : this.topBottomMargin + "px"
			},
			children : [
				BX.create("tbody", {
					children : [
						BX.create("tr", {
							children : [
								this.prevTaskArea = BX.create("td", {
									props : {className : "tasks-popup-prev-slide-wrap"},
									children : [this.prevTaskLink]
								}),
								BX.create("td", {
									props : {
										id : 'tasks-crazy-heavy-cpu-usage-item',
										className : "tasks-popup-main-block-wrap tasks-popup-main-block-wrap-bg"
									},
									children : [
										BX.create("div", {
											props : {className : "tasks-popup-main-block-inner"},
											children : [this.header, this.iframe]
										})
									]
								}),
								this.nextTaskArea = BX.create("td", {
									props : {className : "tasks-popup-next-slide-wrap"},
									children : [this.closeLink, this.nextTaskLink]
								})
							]
						})
					]
				})
			]
		});

		this.overlay = document.body.appendChild(BX.create("div", {
			props : {
				className : "tasks-fixed-overlay"
			},
			children : [
				BX.create("div", {props : {className : "bx-task-dialog-overlay"}}),
				this.table
			]
		}));

		this.__adjustControls();

		BX.bind(window.top, "resize", BX.proxy(this.__onWindowResize, this));
	};

	TasksIFramePopup.prototype.view = function(taskId, tasksList) {
		this.init();
		if (tasksList)
		{
			this.currentList = [];
			for(var i = 0, count = tasksList.length; i < count; i++)
			{
				this.currentList[i] = parseInt(tasksList[i]);
			}
		}
		else
		{
			this.currentList = null;
		}
		BX.adjust(this.title, {text: BX.message("TASKS_TASK_NUM").replace("#TASK_NUM#", taskId)});
		this.currentTaskId = taskId;
		this.lastAction = "view";
		var isViewMode = true;
		this.load(this.pathToView.replace("#task_id#", taskId), isViewMode);
		this.show();
	};

	TasksIFramePopup.prototype.edit = function(taskId) {
		this.init();
		BX.adjust(this.title, {text: BX.message("TASKS_TITLE_EDIT_TASK").replace("#TASK_ID#", taskId)});
		this.currentTaskId = taskId;
		this.lastAction = "edit";
		this.load(this.pathToEdit.replace("#task_id#", taskId));
		this.show();
	};

	TasksIFramePopup.prototype.add = function(params) {
		this.init();
		BX.adjust(this.title, {text: BX.message("TASKS_TITLE_CREATE_TASK")});
		this.currentTaskId = 0;
		this.lastAction = "add";
		var url = this.pathToEdit.replace("#task_id#", 0) + '&UTF8encoded=1';
		this.descriptionBuffered = null;
		for(var name in params)
		{
			if ((name === 'DESCRIPTION') && (params[name].length > 1000))
				this.descriptionBuffered = params[name];
			else
				url += "&" + name + "=" + encodeURIComponent(params[name]);
		}

		this.load(url);
		this.show();
	};

	TasksIFramePopup.prototype.show = function() {
		BX.onCustomEvent(this, "onBeforeShow", []);
		this.overlay.style.display = "block";
		BX.addClass(document.body, "tasks-body-overlay");
		this.closeLink.style.display = 'none';		// This is first part of hack for Chrome due to bug http://jabber.bx/view.php?id=39643
		this.__onWindowResize();
		this.closeLink.style.display = 'block';		// This is last part of hack, I don't know how is it works, but it is.
		BX.bind(this.iframe.contentDocument ? this.iframe.contentDocument : this.iframe.contentWindow.document, "keypress", BX.proxy(this.__onKeyPress, this));
		BX.onCustomEvent(this, "onAfterShow", []);
	};

	TasksIFramePopup.prototype.close = function() {
		BX.onCustomEvent(this, "onBeforeHide", []);
		this.overlay.style.display = "none";
		BX.removeClass(document.body, "tasks-body-overlay");
		BX.unbind(this.iframe.contentDocument ? this.iframe.contentDocument : this.iframe.contentWindow.document, "keypress", BX.proxy(this.__onKeyPress, this));
		BX('tasks-crazy-heavy-cpu-usage-item').className = 'tasks-popup-main-block-wrap tasks-popup-main-block-wrap-bg';
		BX.onCustomEvent(this, "onAfterHide", []);
		/*if(history.replaceState)
		{
			history.replaceState({}, '', this.currentURL);
		}*/
	};

	TasksIFramePopup.prototype.previous = function() {
		var list = this.currentList ? this.currentList : this.tasksList;
		if (this.currentTaskId && list.length > 1)
		{
			var currentIndex = this.__indexOf(this.currentTaskId, list);
			if (currentIndex != -1)
			{
				if (currentIndex == 0)
				{
					var previousIndex = list.length - 1;
				}
				else
				{
					var previousIndex = currentIndex - 1;
				}

				this.view(list[previousIndex], list);
			}
		}
	};

	TasksIFramePopup.prototype.next = function() {
		var list = this.currentList ? this.currentList : this.tasksList;
		if (this.currentTaskId && list.length > 1)
		{
			var currentIndex = this.__indexOf(this.currentTaskId, list);
			if (currentIndex != -1)
			{
				if (currentIndex == list.length - 1)
				{
					var nextIndex = 0;
				}
				else
				{
					var nextIndex = currentIndex + 1;
				}

				this.view(list[nextIndex], list);
			}
		}
	};

	TasksIFramePopup.prototype.load = function(url, isViewMode)
	{
		this.isEditMode = true;
		if (isViewMode === true)
			this.isEditMode = false;

		var loc = this.iframe.contentWindow ? this.iframe.contentWindow.location : "";
		/*if(history.replaceState)
		{
			history.replaceState({}, '', url.replace("?IFRAME=Y", "").replace("&IFRAME=Y", ""))
		}*/

		this.__onUnload();
		this.iframe.src = url;
	};

	TasksIFramePopup.prototype.isOpened = function() {
		this.init();
		return this.overlay.style.display == "block";
	};

	TasksIFramePopup.prototype.isEmpty = function() {
		this.init();
		return this.iframe.contentWindow.location == "about:blank";
	};

	TasksIFramePopup.prototype.isLeftClick = function(event) {
		if (!event.which && event.button !== undefined)
		{
			if (event.button & 1)
				event.which = 1;
			else if (event.button & 4)
				event.which = 2;
			else if (event.button & 2)
				event.which = 3;
			else
				event.which = 0;
		}

		return event.which == 1 || (event.which == 0 && BX.browser.IsIE());
	};

	TasksIFramePopup.prototype.onTaskLoaded = function() {
		this.__onLoad();
	};

	TasksIFramePopup.prototype.onTaskAdded = function(task, action, params, newDataPack, legacyHtmlTaskItem) {
		this.tasksList.push(task.id);
		BX.onCustomEvent(this, "onTaskAdded", [task, action, params, newDataPack, legacyHtmlTaskItem]);
	};

	TasksIFramePopup.prototype.onTaskChanged = function(task, action, params, newDataPack, legacyHtmlTaskItem) {
		BX.onCustomEvent(this, "onTaskChanged", [task, action, params, newDataPack, legacyHtmlTaskItem]);
	};

	TasksIFramePopup.prototype.onTaskDeleted = function(taskId) {
		BX.onCustomEvent(this, "onTaskDeleted", [taskId]);
	};

	TasksIFramePopup.prototype.__onKeyPress = function(e) {
		if (!e) e = window.event;
		if(e.keyCode == 27)
		{
			// var params = {
			// 	canClose : true
			// };

			// BX.onCustomEvent(this, "onBeforeCloseByEscape", [params]);


			//if (params.canClose)

			if (
				(this.lastAction === 'view')
				|| confirm(BX.message('TASKS_CONFIRM_CLOSE_CREATE_DIALOG'))
			)
			{
				this.close();
			}
		}
	};

	TasksIFramePopup.prototype.__indexOf = function(needle, haystack) {
		for(var i = 0, count = haystack.length; i < count; i++) {
			if (needle == haystack[i])
			{
				return i;
			}
		}

		return -1;
	};

	TasksIFramePopup.prototype.__onMouseMove = function(e)
	{
		if (!e)
			e = this.iframe.contentWindow.event;

		var innerDoc = (this.iframe.contentDocument) ? this.iframe.contentDocument : this.iframe.contentWindow.document;

		if (innerDoc && innerDoc.body)
		{
			innerDoc.body.onbeforeunload = BX.proxy(this.__onUnload, this);

			if (this.iframe.contentDocument)
				this.iframe.contentDocument.body.onbeforeunload = BX.proxy(this.__onBeforeUnload, this);

			innerDoc.body.onunload = BX.proxy(this.__onUnload, this);

			var eTarget = e.target || e.srcElement;
			if (eTarget)
			{
				eTargetA = false;
				if (eTarget && eTarget.tagName == "SPAN")
				{
					var oTmp = BX.findParent(eTarget);
					if ((oTmp !== null) && (oTmp.tagName == 'A'))
						eTargetA = oTmp;
				}
				else
					eTargetA = eTarget;

				if (eTargetA.tagName == "A" && eTargetA.href)
				{
					if (eTargetA.href.substr(0, 11) == "javascript:")
					{
						innerDoc.body.onbeforeunload = null;
						innerDoc.body.onunload = null;
					}
					else if (
						(eTargetA.href.indexOf("IFRAME=Y") == -1) 
						&& (eTargetA.href.indexOf("/show_file.php?fid=") == -1)
						&& (eTargetA.target !== '_blank')
					)
					{
						eTargetA.target = "_top";
					}
				}
			}
		}
	};

	TasksIFramePopup.prototype.__onLoad = function() {
		if (!this.isEmpty())
		{
			var innerDoc = (this.iframe.contentDocument) ? this.iframe.contentDocument : this.iframe.contentWindow.document;

			if (innerDoc && innerDoc.body)
			{
				if (BX('tasks-crazy-heavy-cpu-usage-item'))
					BX('tasks-crazy-heavy-cpu-usage-item').className = 'tasks-popup-main-block-wrap';

				this.loading = false;

				innerDoc.body.onmousemove = BX.proxy(this.__onMouseMove, this);

				if (!innerDoc.getElementById("task-reminder-link"))
				{
					window.top.location = innerDoc.location.href.replace("?IFRAME=Y", "").replace("&IFRAME=Y", "").replace("&CALLBACK=CHANGED", "").replace("&CALLBACK=ADDED", "");
				}
				lastSrc = this.iframe.contentWindow.location.href;
				BX.bind(innerDoc, "keyup", BX.proxy(this.__onKeyPress, this));
				this.iframe.style.height = innerDoc.getElementById("tasks-content-outer").offsetHeight + "px";
				this.iframe.style.visibility = "visible";
				this.iframe.contentWindow.focus();

				this.__onWindowResize();
			}

			if (resizeInterval)
				clearInterval(resizeInterval);

			resizeInterval = setInterval(BX.proxy(this.__onContentResize, this), 300);
		}
	};

	TasksIFramePopup.prototype.__onBeforeUnload = function(e)
	{
	};

	TasksIFramePopup.prototype.__onUnload = function(e) {
		if (!e) e = window.event;
		if (!this.loading)
		{
			this.loading = true;
			this.iframe.style.visibility = "hidden";
			clearInterval(resizeInterval);
		}
	};

	TasksIFramePopup.prototype.__onContentResize = function(){
		if (this.isOpened())
		{
			var innerDoc = (this.iframe.contentDocument) ? this.iframe.contentDocument : this.iframe.contentWindow.document;
			if (innerDoc && innerDoc.body)
			{
				var mainContainerHeight = innerDoc.getElementById("tasks-content-outer");
				if (mainContainerHeight)
				{
					var iframeScrollHeight = this.__getWindowScrollHeight(innerDoc);
					var innerSize = BX.GetWindowInnerSize(innerDoc);

					var realHeight = 0;
					if (iframeScrollHeight > innerSize.innerHeight)
						realHeight = iframeScrollHeight - 1;
					else
						realHeight = mainContainerHeight.offsetHeight;//innerDoc.documentElement.scrollHeight;//this.heightDiv ? this.heightDiv.scrollTop + 15 : 0;

					var loc = this.iframe.contentWindow ? this.iframe.contentWindow.location : '';

					if (loc.toString)
						loc = loc.toString();

					if (
						(realHeight != lastheight)
						|| (this.prevIframeSrc != loc)
					)
					{
						lastheight = realHeight;
						this.prevIframeSrc = loc;
						this.iframe.style.height = realHeight + "px";
						this.__onWindowResize();
					}
				}
			}
		}
	};

	TasksIFramePopup.prototype.__getWindowScrollHeight = function(pDoc)
	{
		var height;
		if (!pDoc)
			pDoc = document;

		if ( (pDoc.compatMode && pDoc.compatMode == "CSS1Compat") && !BX.browser.IsSafari())
		{
			height = pDoc.documentElement.scrollHeight;
		}
		else
		{
			if (pDoc.body.scrollHeight > pDoc.body.offsetHeight)
				height = pDoc.body.scrollHeight;
			else
				height = pDoc.body.offsetHeight;
		}
		return height;
	};

	TasksIFramePopup.prototype.__onWindowResize = function(){
		var size = BX.GetWindowInnerSize();
		this.overlay.style.height = size.innerHeight + "px";
		this.overlay.style.width = size.innerWidth + "px";
		var scroll = BX.GetWindowScrollPos();
		this.overlay.style.top = scroll.scrollTop + "px";
		if (BX.browser.IsIE() && !BX.browser.IsIE9())
		{
			this.table.style.width = (size.innerWidth - 20) + "px";
		}
		this.overlay.firstChild.style.height = Math.max(this.iframe.offsetHeight + this.topBottomMargin * 2 + 31, this.overlay.clientHeight) + "px";
		this.overlay.firstChild.style.width = Math.max(1024, this.overlay.clientWidth) + "px";

		this.prevTaskArea.style.width = Math.max(0, Math.max(1024, this.overlay.clientWidth) / 2) + "px";
		this.nextTaskArea.style.width = this.prevTaskArea.style.width;

		this.__adjustControls();
	};

	TasksIFramePopup.prototype.__adjustControls = function(){
		if (this.lastAction != "view" || ((!this.currentList || this.currentList.length <= 1 || this.__indexOf(this.currentTaskId, this.currentList) == -1) && (this.tasksList.length <= 1 || this.__indexOf(this.currentTaskId, this.tasksList) == -1)))
		{
			this.nextTaskLink.style.display = this.prevTaskLink.style.display = "none";
		}
		else
		{
			if(!BX.browser.IsDoctype() && BX.browser.IsIE())
			{
				this.nextTaskLink.style.height = this.prevTaskLink.style.height = document.documentElement.offsetHeight + "px";
				this.prevTaskLink.style.width = (this.prevTaskLink.parentNode.clientWidth - 1) + 'px';
				this.nextTaskLink.style.width = (this.nextTaskLink.parentNode.clientWidth - 1) + 'px';
			}
			else
			{
				this.nextTaskLink.style.height = this.prevTaskLink.style.height = document.documentElement.clientHeight + "px";
				this.prevTaskLink.style.width = this.prevTaskLink.parentNode.clientWidth + 'px';
				this.nextTaskLink.style.width = this.nextTaskLink.parentNode.clientWidth + 'px';
			}
			this.prevTaskLink.firstChild.style.left = (this.prevTaskLink.parentNode.clientWidth * 4 / 10) + 'px';
			this.nextTaskLink.firstChild.style.right = (this.nextTaskLink.parentNode.clientWidth * 4 / 10) + 'px';
			this.nextTaskLink.style.display = this.prevTaskLink.style.display = "";
		}
		this.closeLink.style.width = this.closeLink.parentNode.clientWidth + 'px';
	};
})(window);


(function(){
	if (BX.TasksTimerManager)
		return;

	BX.TasksTimerManager = {
		popup : null,
		onTimeManDataRecievedEventDetected : false
	};


	BX.TasksTimerManager.reLoadInitTimerDataFromServer = function()
	{
		var updated = true;

		// This will run onTimeManDataRecieved/onPlannerDataRecieved 
		// and after it init_timer_data event
		if (window.BXTIMEMAN)
			window.BXTIMEMAN.Update(true);
		else if (window.BXPLANNER && window.BXPLANNER.update)
			window.BXPLANNER.update();
		else
			updated = false;

		if (window.top !== window)
		{
			if (window.top.BXTIMEMAN)
				window.top.BXTIMEMAN.Update(true);
			else if (window.top.BXPLANNER && window.top.BXPLANNER.update)
				window.top.BXPLANNER.update();
		}

		return (updated);
	};


	BX.TasksTimerManager.start = function(taskId)
	{
		BX.CJSTask.batchOperations(
			[{
				operation : 'CTaskTimerManager::getLastTimer()'
			}],
			{
				callbackOnSuccess : (function(taskId){
					return function(data)
					{
						// some other task on timer?
						if (
							(data.rawReply.data[0].returnValue)
							&& (data.rawReply.data[0].returnValue.TASK_ID > 0)
							&& (data.rawReply.data[0].returnValue.TIMER_STARTED_AT > 0)
							&& (taskId != data.rawReply.data[0].returnValue.TASK_ID)
						)
						{
							BX.CJSTask.batchOperations(
								[{
									operation : 'CTaskItem::getTaskData()',
									taskData  : {
										ID : data.rawReply.data[0].returnValue.TASK_ID
									}
								}],
								{
									callbackOnSuccess : (function(taskId){
										return function(data)
										{
											if (
												(data.rawReply.data[0].returnValue.ID)
												&& (taskId != data.rawReply.data[0].returnValue.ID)
											)
											{
												BX.TasksTimerManager.__showConfirmPopup(
													data.rawReply.data[0].returnValue.ID,
													data.rawReply.data[0].returnValue.TITLE,
													(function(taskId){
														return function(bConfirmed)
														{
															if (bConfirmed)
																BX.TasksTimerManager.__doStart(taskId);
														}
													})(taskId)
												);
											}
										};
									})(taskId),
									callbackOnFailure : (function(taskId){
										return function(data)
										{
											// probably task not exists or not accessible
											BX.TasksTimerManager.__doStart(taskId);
										};
									})(taskId)
								},
								true	// sync
							);
						}
						else
							BX.TasksTimerManager.__doStart(taskId);
					}
				})(taskId)
			},
			true	// sync
		);
	};


	BX.TasksTimerManager.stop = function(taskId)
	{
		var oTaskTimer = new BX.CJSTask.TimerManager(taskId);

		oTaskTimer.stop({
			callbackOnSuccess : function(data)
			{
				if (data.status === 'success')
				{
					BX.onCustomEvent(
						window,
						'onTaskTimerChange',
						[{
							module           : 'tasks',
							action           : 'stop_timer',
							taskId           :  data.rawReply.data[0].requestedTaskId,
							taskData         :  data.rawReply.data[1].returnValue,
							timerData        :  data.rawReply.data[2].returnValue
						}]
					);
				}
			}
		});
	};


	BX.TasksTimerManager.__doStart = function(taskId)
	{
		var oTaskTimer = new BX.CJSTask.TimerManager(taskId);
		oTaskTimer.start({
			callbackOnSuccess : function(data)
			{
				if (data.status === 'success')
				{
					BX.onCustomEvent(
						window,
						'onTaskTimerChange',
						[{
							module    : 'tasks',
							action    : 'start_timer',
							taskId    :  data.rawReply.data[0].requestedTaskId,
							taskData  :  data.rawReply.data[1].returnValue,
							timerData :  data.rawReply.data[2].returnValue
						}]
					);
				}
			}
		});
	};


	BX.TasksTimerManager.__showConfirmPopup = function(taskId, taskName, callback)
	{
		if (this.popup)
		{
			this.popup.close();
			this.popup.destroy();
		}

		var message = BX.message('TASKS_TASK_CONFIRM_START_TIMER');
		message = message.replace('#TASK#', '"' + BX.util.htmlspecialchars(taskName) + '"')

		this.popup = new BX.PopupWindow(
			'task-confirm-stop-other-task',
			null,
			{
				zIndex : 22000,
				overlay : { opacity: 50 },
				titleBar : {
					content: BX.create(
						'span',
						{ html : BX.message('TASKS_TASK_CONFIRM_START_TIMER_TITLE') }
					)
				},
				content : '<div style="width: 400px; padding: 25px;">' 
					+ message + '</div>',
				autoHide   : false,
				closeByEsc : false,
				buttons : [
					new BX.PopupWindowButton({
						text: BX.message('TASKS_BTN_CONTINUE'),
						className: "popup-window-button-accept",
						events : {
							click : (function(callback){
								return function() {
									BX.TasksTimerManager.popup.close();
									callback(true);
								}
							})(callback)
						}
					}),
					new BX.PopupWindowButton({
						text: BX.message('TASKS_BTN_CANCEL'),
						events : {
							click : (function(callback){
								return function() {
									BX.TasksTimerManager.popup.close();
									callback(false);
								}
							})(callback)
						}
					})
				]
			}
		);
		this.popup.show();
	};


	BX.TasksTimerManager.refreshDaemon = new function()
	{
		this.data = null;


		this.onTick = function()
		{
			if (this.data !== null)
			{
				var JS_UNIX_TIMESTAMP = Math.round((new Date()).getTime() / 1000);
				this.data.TIMER.RUN_TIME = JS_UNIX_TIMESTAMP - this.data.TIMER.TIMER_STARTED_AT - this.data.UNIX_TIMESTAMP_DELTA;

				BX.onCustomEvent(
					window,
					'onTaskTimerChange',
					[{
						action : 'refresh_daemon_event',
						taskId : this.data.TIMER.TASK_ID,
						data   : this.data
					}]
				);
			}
		};

		BX.ready(
			(function(self){
				return function(){
					BX.CJSTask.setTimerCallback(
						'tasks_timer_refresh_daemon_event',
						(function(self){
							return function(){
								self.onTick();
							}
						})(self),
						1024
					);
				}
			})(this)
		);

		this.catchTimerChange = function(params)
		{
			if (params.module !== 'tasks')
				return;

			if (params.action === 'refresh_daemon_event')
			{
				return;
			}
			else if (params.action === 'stop_timer')
			{
				this.data = null;

				// This will transfer data through browsers tabs
				BX.TasksTimerManager.reLoadInitTimerDataFromServer();
			}
			else if (params.action === 'start_timer')
			{
				if (
					( ! (params.timerData && params.timerData.USER_ID) )
					|| (params.timerData.TASK_ID != params.taskData.ID)
				)
				{
					// We cannot work with this data
					this.data = null;
					return;
				}

				if (params.timerData.TIMER_STARTED_AT == 0)
				{
					// Task on pause
					this.data = null;
					return;
				}

				var UNIX_TIMESTAMP_DELTA = 0;
				var JS_UNIX_TIMESTAMP    = Math.round((new Date()).getTime() / 1000);
				var RUN_TIME             = parseInt(params.timerData.RUN_TIME);
				var TIME_SPENT_IN_LOGS   = parseInt(params.taskData.TIME_SPENT_IN_LOGS);
				var TIMER_STARTED_AT     = parseInt(params.timerData.TIMER_STARTED_AT);

				if (isNaN(RUN_TIME))
					RUN_TIME = 0;

				if (isNaN(TIME_SPENT_IN_LOGS))
					TIME_SPENT_IN_LOGS = 0;

				if (TIMER_STARTED_AT > 0)
					UNIX_TIMESTAMP_DELTA = JS_UNIX_TIMESTAMP - TIMER_STARTED_AT - RUN_TIME;

				this.data = {
					TIMER : {
						TASK_ID          : parseInt(params.timerData.TASK_ID),
						USER_ID          : parseInt(params.timerData.USER_ID),
						TIMER_STARTED_AT : TIMER_STARTED_AT,
						RUN_TIME         : RUN_TIME
					},
					TASK : {
						ID                  : params.taskData.ID,
						TITLE               : params.taskData.TITLE,
						TIME_SPENT_IN_LOGS  : TIME_SPENT_IN_LOGS,
						TIME_ESTIMATE       : parseInt(params.taskData.TIME_ESTIMATE),
						ALLOW_TIME_TRACKING : params.taskData.ALLOW_TIME_TRACKING
					},
					UNIX_TIMESTAMP_DELTA : UNIX_TIMESTAMP_DELTA
				};

				// This will transfer data through browsers tabs
				BX.TasksTimerManager.reLoadInitTimerDataFromServer();
			}
			else if (params.action === 'init_timer_data')
			{
				if (
					( ! (params.data.TIMER && params.data.TIMER.USER_ID) )
					|| (params.data.TIMER.TASK_ID != params.data.TASK.ID)
				)
				{
					// We cannot work with this data
					this.data = null;
					return;
				}

				if (params.data.TIMER.TIMER_STARTED_AT == 0)
				{
					// Task on pause
					this.data = null;
					return;
				}

				var UNIX_TIMESTAMP_DELTA = 0;
				var JS_UNIX_TIMESTAMP    = Math.round((new Date()).getTime() / 1000);
				var RUN_TIME             = parseInt(params.data.TIMER.RUN_TIME);
				var TIME_SPENT_IN_LOGS   = parseInt(params.data.TASK.TIME_SPENT_IN_LOGS);
				var TIMER_STARTED_AT     = parseInt(params.data.TIMER.TIMER_STARTED_AT);

				if (isNaN(RUN_TIME))
					RUN_TIME = 0;

				if (isNaN(TIME_SPENT_IN_LOGS))
					TIME_SPENT_IN_LOGS = 0;

				if (TIMER_STARTED_AT > 0)
					UNIX_TIMESTAMP_DELTA = JS_UNIX_TIMESTAMP - TIMER_STARTED_AT - RUN_TIME;

				this.data = {
					TIMER : {
						TASK_ID          : parseInt(params.data.TIMER.TASK_ID),
						USER_ID          : parseInt(params.data.TIMER.USER_ID),
						TIMER_STARTED_AT : TIMER_STARTED_AT,
						RUN_TIME         : RUN_TIME
					},
					TASK : {
						ID                  : params.data.TASK.ID,
						TITLE               : params.data.TASK.TITLE,
						TIME_SPENT_IN_LOGS  : TIME_SPENT_IN_LOGS,
						TIME_ESTIMATE       : parseInt(params.data.TASK.TIME_ESTIMATE),
						ALLOW_TIME_TRACKING : params.data.TASK.ALLOW_TIME_TRACKING
					},
					UNIX_TIMESTAMP_DELTA : UNIX_TIMESTAMP_DELTA
				};
			}
		};

		BX.addCustomEvent(
			window,
			'onTaskTimerChange',
			(function(self){
				return function(params){
					self.catchTimerChange(params);
				};
			})(this)
		);
	};

	BX.TasksTimerManager.onDataRecieved = function(PLANNER)
	{
		var RUN_TIME = 0;
		var reply = { TIMER : false, TASK : false };

		if ( ! PLANNER )
			return;

		if (PLANNER.TASKS_TIMER)
		{
			if (parseInt(PLANNER.TASKS_TIMER.TIMER_STARTED_AT) > 0)
				RUN_TIME = Math.round((new Date()).getTime() / 1000) - parseInt(PLANNER.TASKS_TIMER.TIMER_STARTED_AT);

			if (RUN_TIME < 0)
				RUN_TIME = 0;

			reply.TIMER = {
				TASK_ID          : PLANNER.TASKS_TIMER.TASK_ID,
				USER_ID          : PLANNER.TASKS_TIMER.USER_ID,
				TIMER_STARTED_AT : PLANNER.TASKS_TIMER.TIMER_STARTED_AT,
				RUN_TIME         : RUN_TIME
			};
		}

		if (PLANNER.TASK_ON_TIMER)
		{
			reply.TASK = {
				ID                  : PLANNER.TASK_ON_TIMER.ID,
				TITLE               : PLANNER.TASK_ON_TIMER.TITLE,
				STATUS              : PLANNER.TASK_ON_TIMER.STATUS,
				TIME_SPENT_IN_LOGS  : PLANNER.TASK_ON_TIMER.TIME_SPENT_IN_LOGS,
				TIME_ESTIMATE       : PLANNER.TASK_ON_TIMER.TIME_ESTIMATE,
				ALLOW_TIME_TRACKING : PLANNER.TASK_ON_TIMER.ALLOW_TIME_TRACKING
			};
		}

		BX.onCustomEvent(
			window,
			'onTaskTimerChange',
			[{
				action : 'init_timer_data',
				module : 'tasks',
				data   :  reply
			}]
		);
	};

	BX.addCustomEvent(
		window,
		'onTimeManDataRecieved',
		function(data){
			BX.TasksTimerManager.onTimeManDataRecievedEventDetected = true;
			if (data.PLANNER)
				BX.TasksTimerManager.onDataRecieved(data.PLANNER);
		}
	);

	BX.addCustomEvent(
		window,
		'onPlannerDataRecieved',
		function(obPlanner, data){
			if (BX.TasksTimerManager.onTimeManDataRecievedEventDetected === false)
				BX.TasksTimerManager.onDataRecieved(data);
		}
	);
})();


/* End */
;
; /* Start:"a:4:{s:4:"full";s:60:"/bitrix/js/tasks/core_planner_handler.min.js?145227747812430";s:6:"source";s:40:"/bitrix/js/tasks/core_planner_handler.js";s:3:"min";s:44:"/bitrix/js/tasks/core_planner_handler.min.js";s:3:"map";s:44:"/bitrix/js/tasks/core_planner_handler.map.js";}"*/
(function(){if(!!window.BX.CTasksPlannerHandler)return;var t=window.BX,e={"-1":"overdue","-2":"new",1:"new",2:"accepted",3:"in-progress",4:"waiting",5:"completed",6:"delayed",7:"declined"},s=null;t.addTaskToPlanner=function(t){s.addTask({id:t})};t.CTasksPlannerHandler=function(){this.TASKS=null;this.TASKS_LIST=null;this.ADDITIONAL={};this.MANDATORY_UFS=null;this.TASK_CHANGES={add:[],remove:[]};this.TASK_CHANGES_TIMEOUT=null;this.TASKS_WND=null;this.DATA_TASKS=null;this.PLANNER=null;this.taskTimerSwitch=false;this.timerTaskId=0;this.onTimeManDataRecievedEventDetected=false;t.addCustomEvent("onPlannerDataRecieved",t.proxy(this.draw,this));t.addCustomEvent("onTaskTimerChange",t.proxy(this.onTaskTimerChange,this))};t.CTasksPlannerHandler.prototype.formatTime=function(t,e){var s=Math.floor(t/3600);var a=Math.floor(t/60)%60;var i=null;var r=(s<10?"0":"")+s.toString()+(a<10?":0":":")+a.toString();if(e){i=t%60;r=r+(i<10?":0":":")+i.toString()}return r};t.CTasksPlannerHandler.prototype.draw=function(e,s){if(typeof s.MANDATORY_UFS!=="undefined")this.MANDATORY_UFS=s.MANDATORY_UFS;if(!s.TASKS_ENABLED)return;this.PLANNER=e;if(null==this.TASKS){this.TASKS=t.create("DIV");this.TASKS.appendChild(t.create("DIV",{props:{className:"tm-popup-section tm-popup-section-tasks"},children:[t.create("SPAN",{props:{className:"tm-popup-section-text"},text:t.message("JS_CORE_PL_TASKS")}),t.create("span",{props:{className:"tm-popup-section-right-link"},events:{click:t.proxy(this.showTasks,this)},text:t.message("JS_CORE_PL_TASKS_CHOOSE")})]}));this.TASKS.appendChild(t.create("DIV",{props:{className:"tm-popup-tasks"},children:[this.TASKS_LIST=t.create("div",{props:{className:"tm-task-list"}}),this.drawTasksForm(t.proxy(this.addTask,this))]}))}else{t.cleanNode(this.TASKS_LIST)}if(s.TASKS&&s.TASKS.length>0){var a=null;var i="";var r=[];var n=0;var o=0;var T="";var l=null;t.removeClass(this.TASKS,"tm-popup-tasks-empty");for(var d=0,S=s.TASKS.length;d<S;d++){l=s.TASKS[d].STATUS==4||s.TASKS[d].STATUS==5;if(l)i=" tm-task-item-done";else i="";r=[];r.push(t.create("input",{props:{className:"tm-task-checkbox",type:"checkbox",checked:l},events:{click:function(e){return function(){var s=new t.CJSTask.Item(e.ID);if(this.checked){s.complete({callbackOnSuccess:function(){if(t.TasksTimerManager)t.TasksTimerManager.reLoadInitTimerDataFromServer()}})}else{s.startExecutionOrRenewAndStart({callbackOnSuccess:function(){if(t.TasksTimerManager)t.TasksTimerManager.reLoadInitTimerDataFromServer()}})}}}(s.TASKS[d])}}));if(s.TASKS[d].TIME_SPENT_IN_LOGS>0||s.TASKS[d].TIME_ESTIMATE>0){n=parseInt(s.TASKS[d].TIME_SPENT_IN_LOGS);o=parseInt(s.TASKS[d].TIME_ESTIMATE);if(isNaN(n))n=0;if(isNaN(o))o=0;T=this.formatTime(n,true);if(o>0)T=T+" / "+this.formatTime(o)}else T="";r.push(t.create("a",{attrs:{href:"javascript:void(0)"},props:{className:"tm-task-name"+(T===""?" tm-task-no-timer":"")},text:s.TASKS[d].TITLE,events:{click:t.proxy(this.showTask,this)}}));if(T!==""){r.push(t.create("SPAN",{props:{className:"tm-task-time",id:"tm-task-time-"+s.TASKS[d].ID},text:T}))}r.push(t.create("SPAN",{props:{className:"tm-task-item-menu"},events:{click:function(e,s,a){return function(){var i=[];if(s&&s.TASK_ID==e.ID&&s.TIMER_STARTED_AT>0){i.push({text:t.message("JS_CORE_PL_TASKS_STOP_TIMER"),className:"menu-popup-item-hold",onclick:function(s){t.TasksTimerManager.stop(e.ID);this.popupWindow.close()}})}else{if(e.ALLOW_TIME_TRACKING==="Y"){i.push({text:t.message("JS_CORE_PL_TASKS_START_TIMER"),className:"menu-popup-item-begin",onclick:function(s){t.TasksTimerManager.start(e.ID);this.popupWindow.close()}})}}i.push({text:t.message("JS_CORE_PL_TASKS_MENU_REMOVE_FROM_PLAN"),className:"menu-popup-item-decline",onclick:function(t){a.removeTask(t,e.ID);this.popupWindow.close()}});t.PopupMenu.destroy("task-tm-item-entry-menu-"+e.ID);menu=t.PopupMenu.show("task-tm-item-entry-menu-"+e.ID,this,i,{autoHide:true,offsetTop:4,events:{onPopupClose:function(t){}}})}}(s.TASKS[d],s.TASKS_TIMER,this)}}));var p=this.TASKS_LIST.appendChild(t.create("div",{props:{id:"tm-task-item-"+s.TASKS[d].ID,className:"tm-task-item "+i,bx_task_id:s.TASKS[d].ID},children:r}));if(s.TASK_LAST_ID&&s.TASKS[d].ID==s.TASK_LAST_ID){a=p}}if(a){setTimeout(t.delegate(function(){if(a.offsetTop<this.TASKS_LIST.scrollTop||a.offsetTop+a.offsetHeight>this.TASKS_LIST.scrollTop+this.TASKS_LIST.offsetHeight){this.TASKS_LIST.scrollTop=a.offsetTop-parseInt(this.TASKS_LIST.offsetHeight/2)}},this),10)}}else{t.addClass(this.TASKS,"tm-popup-tasks-empty")}this.DATA_TASKS=t.clone(s.TASKS);e.addBlock(this.TASKS,200);e.addAdditional(this.drawAdditional())};t.CTasksPlannerHandler.prototype.drawAdditional=function(){if(!this.TASK_ADDITIONAL){this.ADDITIONAL.TASK_TEXT=t.create("SPAN",{props:{className:"tm-info-bar-text-inner"}});this.ADDITIONAL.TASK_TIMER=t.create("SPAN",{props:{className:"tm-info-bar-time"}});this.TASK_ADDITIONAL=t.create("DIV",{props:{className:"tm-info-bar"},children:[t.create("SPAN",{props:{title:t.message("JS_CORE_PL_TASKS_START_TIMER"),className:"tm-info-bar-btn tm-info-bar-btn-play"},events:{click:t.proxy(this.timerStart,this)}}),t.create("SPAN",{props:{title:t.message("JS_CORE_PL_TASKS_STOP_TIMER"),className:"tm-info-bar-btn tm-info-bar-btn-pause"},events:{click:t.proxy(this.timerStop,this)}}),t.create("SPAN",{props:{title:t.message("JS_CORE_PL_TASKS_FINISH"),className:"tm-info-bar-btn tm-info-bar-btn-flag"},events:{click:t.proxy(this.timerFinish,this)}}),this.ADDITIONAL.TASK_TIMER,t.create("SPAN",{props:{className:"tm-info-bar-text"},children:[this.ADDITIONAL.TASK_TEXT]})]});t.hide(this.TASK_ADDITIONAL)}return this.TASK_ADDITIONAL};t.CTasksPlannerHandler.prototype.timerStart=function(){if(this.timerTaskId>0){t.TasksTimerManager.start(this.timerTaskId)}};t.CTasksPlannerHandler.prototype.timerStop=function(){if(this.timerTaskId>0){t.TasksTimerManager.stop(this.timerTaskId)}};t.CTasksPlannerHandler.prototype.timerFinish=function(){if(this.timerTaskId>0){var e=new t.CJSTask.Item(this.timerTaskId);e.complete({callbackOnSuccess:function(){if(t.TasksTimerManager)t.TasksTimerManager.reLoadInitTimerDataFromServer()}})}};t.CTasksPlannerHandler.prototype.onTaskTimerChange=function(e){if(e.action==="refresh_daemon_event"){this.timerTaskId=e.taskId;if(this.PLANNER&&!!this.PLANNER.WND&&this.PLANNER.WND.isShown()&&e.taskId>0){var s=this.drawAdditional();if(!!this.taskTimerSwitch){s.style.display="";this.taskTimerSwitch=false}var a=parseInt(e.data.TIMER.RUN_TIME||0)+parseInt(e.data.TASK.TIME_SPENT_IN_LOGS||0),i=parseInt(e.data.TASK.TIME_ESTIMATE||0);if(i>0&&a>i){t.addClass(s,"tm-info-bar-overdue")}else{t.removeClass(s,"tm-info-bar-overdue")}var r="";r+=this.formatTime(a,true);if(i>0){r+=" / "+this.formatTime(i)}this.ADDITIONAL.TASK_TIMER.innerHTML=r;this.ADDITIONAL.TASK_TEXT.innerHTML=t.util.htmlspecialchars(e.data.TASK.TITLE);var n=t("tm-task-time-"+this.timerTaskId);if(n)n.innerHTML=r}}else if(e.action==="start_timer"){if(this.isClosed(e.taskData)){t.addClass(this.drawAdditional(),"tm-info-bar-closed")}else{t.removeClass(this.drawAdditional(),"tm-info-bar-closed")}this.timerTaskId=e.taskData.ID;this.taskTimerSwitch=true;t.addClass(this.drawAdditional(),"tm-info-bar-active");t.removeClass(this.drawAdditional(),"tm-info-bar-pause")}else if(e.action==="stop_timer"){this.timerTaskId=e.taskData.ID;if(this.isClosed(e.taskData)){t.hide(this.drawAdditional())}else{t.addClass(this.drawAdditional(),"tm-info-bar-pause");t.removeClass(this.drawAdditional(),"tm-info-bar-active")}}else if(e.action==="init_timer_data"){if(e.data.TIMER&&e.data.TASK.ID>0&&e.data.TIMER.TASK_ID==e.data.TASK.ID){this.timerTaskId=e.data.TASK.ID;if(this.isClosed(e.data.TASK)){t.addClass(this.drawAdditional(),"tm-info-bar-closed")}else{t.removeClass(this.drawAdditional(),"tm-info-bar-closed")}if(e.data.TIMER.TIMER_STARTED_AT==0){if(this.isClosed(e.data.TASK)){t.hide(this.drawAdditional())}else{this.taskTimerSwitch=true;t.addClass(this.drawAdditional(),"tm-info-bar-pause");t.removeClass(this.drawAdditional(),"tm-info-bar-active")}}else{this.taskTimerSwitch=true;t.addClass(this.drawAdditional(),"tm-info-bar-active");t.removeClass(this.drawAdditional(),"tm-info-bar-pause")}}else{t.hide(this.drawAdditional())}this.onTaskTimerChange({action:"refresh_daemon_event",taskId:+e.data.TASK.ID,data:e.data})}};t.CTasksPlannerHandler.prototype.isClosed=function(t){return t.STATUS==5||t.STATUS==4};t.CTasksPlannerHandler.prototype.addTask=function(e){if(!!this.TASKS_LIST){this.TASKS_LIST.appendChild(t.create("LI",{props:{className:"tm-popup-task"},text:e.name}));t.removeClass(this.TASKS,"tm-popup-tasks-empty")}var s={action:"add"};if(typeof e.id!="undefined")s.id=e.id;if(typeof e.name!="undefined")s.name=e.name;this.query(s)};t.CTasksPlannerHandler.prototype.removeTask=function(e,s){this.query({action:"remove",id:s});t.cleanNode(t("tm-task-item-"+s),true);if(!this.TASKS_LIST.firstChild){t.addClass(this.TASKS,"tm-popup-tasks-empty")}};t.CTasksPlannerHandler.prototype.showTasks=function(){if(!this.TASKS_WND){this.TASKS_WND=new t.CTasksPlannerSelector({node:t.proxy_context,onselect:t.proxy(this.addTask,this)})}else{this.TASKS_WND.setNode(t.proxy_context)}this.TASKS_WND.Show()};t.CTasksPlannerHandler.prototype.showTask=function(e){var s=t.proxy_context.parentNode.bx_task_id,a=this.DATA_TASKS,i=[];if(a.length>0){for(var r=0;r<a.length;r++){i.push(a[r].ID)}taskIFramePopup.tasksList=i;taskIFramePopup.view(s)}return false};t.CTasksPlannerHandler.prototype.drawTasksForm=function(e){var s=null;var a=null;var i=null;if(this.MANDATORY_UFS!=="Y"){s=t.delegate(function(s,i){a.value=t.util.trim(a.value);if(a.value&&a.value!=t.message("JS_CORE_PL_TASKS_ADD")){e({name:a.value});if(!i){t.addClass(a.parentNode,"tm-popup-task-form-disabled");a.value=t.message("JS_CORE_PL_TASKS_ADD")}else{a.value=""}}return t.PreventDefault(s)},this);var a=t.create("INPUT",{props:{type:"text",className:"tm-popup-task-form-textbox",value:t.message("JS_CORE_PL_TASKS_ADD")},events:{keypress:function(t){return t.keyCode==13?s(t,true):true},blur:function(){if(this.value==""){t.addClass(this.parentNode,"tm-popup-task-form-disabled");this.value=t.message("JS_CORE_PL_TASKS_ADD")}},focus:function(){t.removeClass(this.parentNode,"tm-popup-task-form-disabled");if(this.value==t.message("JS_CORE_PL_TASKS_ADD"))this.value=""}}});t.focusEvents(a);i=[a,t.create("SPAN",{props:{className:"tm-popup-task-form-submit"},events:{click:s}})]}else{i=[t.create("A",{text:t.message("JS_CORE_PL_TASKS_CREATE"),attrs:{href:"javascript:void(0)"},events:{click:function(){window["taskIFramePopup"].add({ADD_TO_TIMEMAN:"Y"})}}})]}return t.create("DIV",{props:{className:"tm-popup-task-form tm-popup-task-form-disabled"},children:i})};t.CTasksPlannerHandler.prototype.query=function(e,s){if(this.TASK_CHANGES_TIMEOUT){clearTimeout(this.TASK_CHANGES_TIMEOUT)}if(typeof e=="object"){if(!!e.id){this.TASK_CHANGES[e.action].push(e.id)}if(e.action=="add"){if(!e.id){this.TASK_CHANGES.name=e.name}this.query()}else{this.TASK_CHANGES_TIMEOUT=setTimeout(t.proxy(this.query,this),1e3)}}else{if(!!this.PLANNER){this.DATA_TASKS=[];this.PLANNER.query("task",this.TASK_CHANGES)}else{window.top.BX.CPlanner.query("task",this.TASK_CHANGES)}this.TASK_CHANGES={add:[],remove:[]}}};t.CTasksPlannerSelector=function(e){this.params=e;this.isReady=false;this.WND=t.PopupWindowManager.create("planner_tasks_selector_"+parseInt(Math.random()*1e4),this.params.node,{autoHide:true,closeByEsc:true,content:this.content=t.create("DIV"),buttons:[new t.PopupWindowButtonLink({text:t.message("JS_CORE_WINDOW_CLOSE"),className:"popup-window-button-link-cancel",events:{click:function(e){this.popupWindow.close();return t.PreventDefault(e)}}})]})};t.CTasksPlannerSelector.prototype.Show=function(){if(!this.isReady){var e=parseInt(Math.random()*1e4);window["PLANNER_ADD_TASK_"+e]=t.proxy(this.setValue,this);return t.ajax.get("/bitrix/tools/tasks_planner.php",{action:"list",suffix:e,sessid:t.bitrix_sessid(),site_id:t.message("SITE_ID")},t.proxy(this.Ready,this))}return this.WND.show()};t.CTasksPlannerSelector.prototype.Hide=function(){this.WND.close()};t.CTasksPlannerSelector.prototype.Ready=function(t){this.content.innerHTML=t;this.isReady=true;this.Show()};t.CTasksPlannerSelector.prototype.setValue=function(t){this.params.onselect(t);this.WND.close()};t.CTasksPlannerSelector.prototype.setNode=function(t){this.WND.setBindElement(t)};s=new t.CTasksPlannerHandler})();
/* End */
;
//# sourceMappingURL=kernel_tasks.map.js