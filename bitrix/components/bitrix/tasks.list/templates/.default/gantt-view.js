var tasksListNS = {
	approveTask : function(taskId)
	{
		ganttChart.updateTask(taskId, {status: "completed", dateCompleted: new Date()});
		SetServerStatus(taskId, "approve");
	},
	disapproveTask : function(taskId)
	{
		ganttChart.updateTask(taskId, {status: "new", dateCompleted: null});
		SetServerStatus(taskId, "disapprove");
	}
}


function CloseTask(taskId)
{
	ganttChart.updateTask(taskId, {status: "completed", dateCompleted: new Date()});
	SetServerStatus(taskId, "close");
}

function StartTask(taskId)
{
	ganttChart.updateTask(taskId, {status: "in-progress", dateCompleted: null});
	SetServerStatus(taskId, "start");
}

function AcceptTask(taskId)
{
	ganttChart.updateTask(taskId, {status: "accepted", dateCompleted: null});
	SetServerStatus(taskId, "accept");
}

function PauseTask(taskId)
{
	ganttChart.updateTask(taskId, {status: "accepted", dateCompleted: null});
	SetServerStatus(taskId, "pause");
}

function RenewTask(taskId)
{
	ganttChart.updateTask(taskId, {status: "new", dateCompleted: null});
	SetServerStatus(taskId, "renew");
}

function DeferTask(taskId)
{
	ganttChart.updateTask(taskId, {status: "delayed"});
	SetServerStatus(taskId, "defer");
}

function DeclineTask(taskId)
{
	BX.TaskDeclinePopup.show(link, {
		offsetLeft : -100,
		taskId : this.id,
		events : {
			onPopupChange: function()
			{
				ganttChart.updateTask(taskId, {status: "declined"});
				SetServerStatus(taskId, "decline", {reason : this.textarea.value});
			}
		}
	})
}

function DeleteTask(taskId)
{
	var data = {
		mode : "delete",
		sessid : BX.message("bitrix_sessid"),
		id : taskId
	};

	BX.ajax({
		"method": "POST",
		"dataType": "html",
		"url": tasksListAjaxUrl,
		"data":  data,
		"processData" : false,
		"onsuccess": (function(taskId){
			return function(datum) {
				TASKS_table_view_onDeleteClick_onSuccess(taskId, datum);
			};
		})(taskId)
	});
}


function TASKS_table_view_onDeleteClick_onSuccess(taskId, data)
{
	if (data && data.length > 0)
	{
		// there is an error occured
	}
	else
	{
		ganttChart.removeTask(taskId);
		BX.onCustomEvent('onTaskListTaskDelete', [taskId]);
	}
}


function onPopupTaskChanged(task) {
	__RenewMenuItems(task);
	__InvalidateMenus([task.id, "c" + task.id]);
	
	if (task.parentTaskId)
	{
		parentTask = ganttChart.getTaskById(task.parentTaskId);
		if (parentTask)
		{
			if (parentTask.hasChildren)
			{
				parentTask.expand();
				ganttChart.updateTask(task.id, task);
			}
			else
			{
				ganttChart.updateTask(task.id, task);
				parentTask.expand();
			}
		}
		else
		{
			ganttChart.updateTask(task.id, task);
		}
		
	}
	else if(task.projectId && !ganttChart.getProjectById(task.projectId))
	{
		var project = ganttChart.addProjectFromJSON({
			id: task.projectId,
			name: task.projectName,
			opened: true
		});
		ganttChart.updateTask(task.id, task);
	}
	else
	{
		ganttChart.updateTask(task.id, task);
	}
}

function onPopupTaskAdded(task) {
	BX.onCustomEvent("onTaskListTaskAdd", [task]);

	__RenewMenuItems(task);
	
	if (task.parentTaskId)
	{
		parentTask = ganttChart.getTaskById(task.parentTaskId);
		if (parentTask)
		{
			if (parentTask.hasChildren)
			{
				parentTask.expand();
				ganttChart.addTaskFromJSON(task);
			}
			else
			{
				ganttChart.addTaskFromJSON(task);
				parentTask.expand();
			}
		}
		else
		{
			ganttChart.addTaskFromJSON(task);
		}
		
	}
	else if(task.projectId && !ganttChart.getProjectById(task.projectId))
	{
		var project = ganttChart.addProjectFromJSON({
			id: task.projectId,
			name: task.projectName,
			opened: true
		});
		ganttChart.addTaskFromJSON(task);
	}
	else
	{
		ganttChart.addTaskFromJSON(task);
	}
}

function onPopupTaskDeleted(taskId) {
	ganttChart.removeTask(taskId);
}

var lastScroll;
function onBeforeShow() {
	if (BX.browser.IsOpera())
	{
		lastScroll = ganttChart.layout.timeline.scrollLeft;
	}
}
function onAfterShow() {
	if (typeof(lastScroll) != "undefined" && BX.browser.IsOpera())
	{
		ganttChart.layout.timeline.scrollLeft = lastScroll;
	}
}
function onBeforeHide() {
	if (BX.browser.IsOpera())
	{
		lastScroll = ganttChart.layout.timeline.scrollLeft;
	}
}
function onAfterHide() {
	if (typeof(lastScroll) != "undefined" && BX.browser.IsOpera())
	{
		ganttChart.layout.timeline.scrollLeft = lastScroll;
	}
}

function __RenewMenuItems(task)
{
	quickInfoData[task.id] = BX.clone(task, true);
	task.menuItems = __FilterMenuByStatus(task);
}