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
