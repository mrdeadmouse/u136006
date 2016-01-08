(function(window) {

if (BX.TaskPriorityPopup)
	return;

/*==================================================Generic Task List Popup========================================================*/
var TaskListPopup = function(popupId, popupClassName, title, items, params)
{
	this.items = [];
	this.popupId = popupId;
	this.popupClassName = popupClassName;
	if (BX.type.isArray(items))
	{
		for (var i = 0; i < items.length; i++)
		{
			if (typeof items[i]["id"] !== "undefined")
				this.items.push(items[i]);
		}
	}

	this.params = params || {};
	this.title = title;

	this.popupWindow = null;
	this.currentTask = null;
	this.tasksData = {};
	this.itemList = [];
};

TaskListPopup.prototype.show = function(taskId, bindElement, currentValues, params)
{
	if (this.popupWindow !== null)
		this.popupWindow.close();

	this.currentTask = this.__getTask(taskId, bindElement, currentValues, params);
	if (this.currentTask === null)
		return false;

	if (this.popupWindow == null)
		this.__createLayout();
	else
		this.popupWindow.setBindElement(this.currentTask.bindElement);

	this.__redrawList();

	this.popupWindow.show();
};

TaskListPopup.prototype.__createLayout = function()
{
	var items = [];
	for (var i = 0; i < this.items.length; i++)
	{
		var item = this.items[i];
		var domElement = BX.create(
			"a",
			{
				props : { className: "task-popup-list-item" },
				events : {
					click : BX.proxy(this.__onItemClick, {obj : this, listItem : item })
				},
				children : [
						BX.create("span", { props : { className : "task-popup-list-item-left" }}),
						BX.create("span", { props : { className : "task-popup-list-item-icon task-popup-"+ this.popupClassName + "-icon-" + item.className }}),
						BX.create("span", { props : { className : "task-popup-list-item-text" }, text : item.name }),
						BX.create("span", { props : { className : "task-popup-list-item-right" }})
				]
			}
		);

		this.itemList[item.id] = domElement;
		items.push(domElement);
	}

	this.popupWindow = BX.PopupWindowManager.create("bx-task-" + this.popupId + "-popup", this.currentTask.bindElement, {
			autoHide : true,
			offsetTop : 1,
			lightShadow : true,
			events : {
				onPopupClose : BX.proxy(this.__onPopupClose, this)
			},
			content : (this.popupWindowContent = BX.create(
				"div",
				{
					props : { className: "task-" + this.popupClassName + "-popup" },
					children : [
							BX.create("div", { props: { className: "task-" + this.popupClassName +"-popup-title" }, text : this.title } ),
							BX.create("div", { props : { className : "popup-window-hr" }, children : [ BX.create("i", {}) ]}),
							BX.create("div", { props: { className: "task-popup-list-list" }, children :  items } )
					]
				}
			))
	});
};

TaskListPopup.prototype.__redrawList = function()
{
	this.__selectItem(this.currentTask.listValue);
};

TaskListPopup.prototype.__selectItem = function(itemId)
{
	for (var id in this.itemList)
	{
		var item = this.itemList[id];
		if (itemId == id)
			BX.addClass(item, "task-popup-list-item-selected");
		else
			BX.removeClass(item, "task-popup-list-item-selected");
	}
};

TaskListPopup.prototype.__getTask = function(taskId, bindElement, currentValues, params)
{
	if (!BX.type.isNumber(taskId))
		return null;

	if (!this.tasksData[taskId])
	{
		this.tasksData[taskId] = {
			id : taskId,
			bindElement : bindElement,
			listItem : {},
			onPopupChange : params.events && params.events.onPopupChange && BX.type.isFunction(params.events.onPopupChange) ? params.events.onPopupChange : null,
			onPopupClose : params.events && params.events.onPopupClose && BX.type.isFunction(params.events.onPopupClose) ? params.events.onPopupClose : null
		};

		if (typeof(currentValues) == "object")
			for (var prop in currentValues)
				this.tasksData[taskId][prop] = currentValues[prop];
		else
			this.tasksData[taskId].listValue = currentValues;


		if (typeof(this.tasksData[taskId]["listValue"]) !== "undefined")
		{
			for (var i = 0; i < this.items.length; i++)
			{
				if (this.items[i].id === this.tasksData[taskId].listValue)
				{
					this.tasksData[taskId].listItem = this.items[i];
					break;
				}
			}
		}
	}
	return this.tasksData[taskId];
};

TaskListPopup.prototype.__onItemClick = function(event)
{
	this.obj.popupWindow.close();

	if (this.obj.currentTask.listValue != this.listItem.id)
	{
		this.obj.currentTask.listValue = this.listItem.id;
		this.obj.currentTask.listItem = this.listItem;
		if (this.obj.currentTask.onPopupChange)
			this.obj.currentTask.onPopupChange();
	}

	BX.PreventDefault(event);
};

TaskListPopup.prototype.__onPopupClose = function(popupWindow)
{
	if (this.currentTask.onPopupClose)
		this.currentTask.onPopupClose();
};

/* ===================================================Priority Popup ===============================================================*/
var TaskPriorityPopup = function()
{
	TaskPriorityPopup.superclass.constructor.apply(this, [
		"priority",
		"priority",
		BX.message("TASKS_PRIORITY"),
		[
			{ id : 0, name : BX.message("TASKS_PRIORITY_LOW"), className : "low" },
			{ id : 1, name : BX.message("TASKS_PRIORITY_MIDDLE"), className : "middle" },
			{ id : 2, name : BX.message("TASKS_PRIORITY_HIGH"), className : "high" }
		],
		{}
	]);
};
BX.extend(TaskPriorityPopup, TaskListPopup);

/* ===================================================Public Priority Popup Method====================================================*/
BX.TaskPriorityPopup = {

	popup : null,
	show : function(taskId, bindElement, currentValue, params)
	{
		if (this.popup == null)
			this.popup = new TaskPriorityPopup();
		this.popup.show(taskId, bindElement, currentValue, params);
	}
};


/*=======================================================Simple Grade Popup===========================================================*/
var TaskGradeSimplePopup = function(popupId)
{
	TaskGradeSimplePopup.superclass.constructor.apply(this, [
		popupId,
		"grade",
		BX.message("TASKS_MARK"),
		[
			{ id : "NULL", name : BX.message("TASKS_MARK_NONE"), className : "none" },
			{ id : "P", name : BX.message("TASKS_MARK_POSITIVE"), className : "plus" },
			{ id : "N", name : BX.message("TASKS_MARK_NEGATIVE"), className : "minus" }
		],
		{}
	]);
};

BX.extend(TaskGradeSimplePopup, TaskListPopup);


/*=======================================================Full Grade Popup===========================================================*/
var TaskGradePopup = function(popupId)
{
	TaskGradePopup.superclass.constructor.apply(this, arguments);
	this.reportCheckbox = null;
	this.selectedItem = null;
};

BX.extend(TaskGradePopup, TaskGradeSimplePopup);

TaskGradePopup.prototype.__createLayout = function()
{
	TaskGradePopup.superclass.__createLayout.apply(this, []);

	this.popupWindowContent.appendChild(BX.create("div", { props : { className : "popup-window-hr" }, children : [ BX.create("i", {}) ]}));
	this.popupWindowContent.appendChild(
		BX.create("div", { props: { className: "task-popup-list-report" },  children : [
			BX.create("form", { props : { className : "task-popup-list-report-form"}, children : [
				BX.create("label", { props : { forHtml : "task-grade-popup-report" }, children : [
					(this.reportCheckbox = BX.create("input", { props : { type : "checkbox", id : "task-grade-popup-report" } })),
					BX.create("span", { text: BX.message("TASKS_ADD_IN_REPORT") })
				]})
			]})
		]})
	);

	this.popupWindow.setButtons([
		new BX.PopupWindowButton({text : BX.message("TASKS_APPLY"), className : "popup-window-button-create", events : { click : BX.proxy(this.__onButtonClick, this) } })
	]);
};
TaskGradePopup.prototype.__redrawList = function()
{
	TaskGradePopup.superclass.__redrawList.apply(this, []);
	this.__setTaskReport(this.currentTask.report);
	this.selectedItem = null;
};

TaskGradePopup.prototype.__setTaskReport = function(report)
{
	this.reportCheckbox.checked = !!report;
};

TaskGradePopup.prototype.__onItemClick = function()
{
	this.obj.selectedItem = this.listItem;
	this.obj.__selectItem(this.listItem.id);
};

TaskGradePopup.prototype.__onButtonClick = function()
{
	this.popupWindow.close();

	if (this.reportCheckbox.checked != this.currentTask.report || (this.selectedItem != null && this.selectedItem.id !=  this.currentTask.listValue))
	{
		this.currentTask.report = this.reportCheckbox.checked;

		if (this.selectedItem != null)
		{
			this.currentTask.listValue = this.selectedItem.id;
			this.currentTask.listItem = this.selectedItem;
		}

		if (this.currentTask.onPopupChange)
			this.currentTask.onPopupChange();
	}
};

/* ================================================== Public Popup Method ======================================================================*/
BX.TaskGradePopup = {

	simplePopup : null,
	popup : null,

	show : function(taskId, bindElement, currentValues, params)
	{
		if (typeof(currentValues) != "object" || typeof(currentValues["report"]) == "undefined")
		{
			if (this.simplePopup == null)
				this.simplePopup = new TaskGradeSimplePopup("grade-simple");
			this.simplePopup.show(taskId, bindElement, currentValues, params);
		}
		else
		{
			if (this.popup == null)
				this.popup = new TaskGradePopup("grade");
			this.popup.show(taskId, bindElement, currentValues, params);
		}
	}
};

BX.TaskSpentHoursPopup = {

	popup : null,
	bindElement : null,
	hoursTextbox : null,
	currentValue : 0,
	params : {},

	show : function(bindElement, currentValue, params)
	{
		this.params = params || {};

		if (this.popup === null)
			this.__init(bindElement, currentValue);

		this.popup.show();
	},

	__init : function(bindElement, currentValue)
	{
		if (this.params.events)
		{
			for (var eventName in this.params.events)
				BX.addCustomEvent(this, eventName, this.params.events[eventName]);
		}
		
		var hours = parseInt(currentValue);
		this.currentValue = isNaN(hours) ? 0 : hours;

		this.popup = BX.PopupWindowManager.create("task-spent-hours-popup", bindElement, {
			content : BX.create("div", { props : { className: "task-detail-spent-hours-popup" }, children : [
				BX.create("span", { props : { className: "task-detail-spent-hours-label"}, text : BX.message("TASKS_DURATION") + ":" }),
				(this.hoursTextbox = BX.create("input", {
					props : {
						type : "text", className: "task-detail-spent-hours-textbox", value : this.currentValue },
						attrs: { "autocomplete" : "off" },
						events : {
							keyup : BX.proxy(this.__onKeyupTextbox, this )
						}
				} ))
			]}),
			buttons : [
				new BX.PopupWindowButton({
					text : BX.message("TASKS_OK"),
					className : "popup-window-button-create",
					events : { click : BX.proxy(this.__onOkButtonClick, this)}
				}),

				new BX.PopupWindowButtonLink({
					text : BX.message("TASKS_CANCEL"),
					className : "popup-window-button-link-cancel",
					events : { click : BX.proxy(this.__onCancelButtonClick, this) }
				})
			],

			offsetLeft : -70,
			events : {
				onAfterPopupShow : BX.proxy(this.__onAfterPopupShow, this),
				onPopupClose : BX.proxy(this.__onPopupClose, this)
			}
		});
	},

	__onOkButtonClick : function(event)
	{
		this.__applyChanges();
		this.popup.close();
		BX.PreventDefault(event);
	},

	__applyChanges : function()
	{
		var hours = parseInt(this.hoursTextbox.value);
		if (!isNaN(hours) && hours != this.currentValue)
		{
			this.currentValue = hours;
			BX.onCustomEvent(this, "onPopupChange", [this]);
		}
		else
			this.hoursTextbox.value = this.currentValue;
	},

	__onCancelButtonClick : function(event)
	{
		this.hoursTextbox.value = this.currentValue;
		this.popup.close();
		BX.PreventDefault(event);
	},

	__onAfterPopupShow : function()
	{
		BX.focus(this.hoursTextbox);
		this.hoursTextbox.select();
	},

	__onPopupClose : function()
	{
		BX.onCustomEvent(this, "onPopupClose", [this]);
	},

	__onKeyupTextbox : function(event)
	{
		event = event || window.event;
		var key = (event.keyCode ? event.keyCode : (event.which ? event.which : null));
		if (key == 13)
		{
			this.__applyChanges();
			this.popup.close();
		}
	}
};

BX.TaskMenuPopup = {

	tasksData : {},
	currentTask : null,

	show : function(taskId, bindElement, menuItems, params)
	{
		if (!taskId)
			return false;

		if (this.currentTask !== null && this.currentTask.popupWindow.isShown() /*&& this.currentTask.id != taskId*/)
			this.currentTask.popupWindow.close();

		if (bindElement.clientX)
		{
			BX.fixEventPageXY(bindElement);
			bindElement = { left : bindElement.pageX, top : bindElement.pageY, bottom : bindElement.pageY};
		}

		// we need reload prev menu data, because it can be not actual
		if (this.tasksData[taskId])
		{
			this.tasksData[taskId].popupWindow.close();
			this.tasksData[taskId].popupWindow.destroy();
			this.tasksData[taskId] = null;
		}

		this.tasksData[taskId] = new BX.TaskMenuPopupWindow(taskId, bindElement, menuItems, params);

		this.currentTask = this.tasksData[taskId];
		this.tasksData[taskId].bindElement = bindElement;
		this.currentTask.popupWindow.setBindElement(bindElement);

		setTimeout(BX.proxy(this._show, this), 0);
		//this.currentTask.popupWindow.show();
	},

	_show : function()
	{
		this.currentTask.popupWindow.show();
	}
};

BX.TaskMenuPopupWindow = function(taskId, bindElement, menuItems, params)
{
	this.id = taskId;
	this.bindElement = bindElement;
	this.menuItems = [];
	this.itemsContainer = null;

	if (menuItems && BX.type.isArray(menuItems))
	{
		for (var i = 0; i < menuItems.length; i++)
			this.__addMenuItem(menuItems[i], null);
	}

	this.params = params && typeof(params) == "object" ? params : {};
	this.popupWindow = this.__createPopup();
};

BX.TaskMenuPopupWindow.prototype.getMenuItem = function(itemId)
{
	for (var i = 0; i < this.menuItems.length; i++)
	{
		if (this.menuItems[i].id && this.menuItems[i].id == itemId)
			return this.menuItems[i];
	}

	return null;
};

BX.TaskMenuPopupWindow.prototype.getMenuItemPosition = function(itemId)
{
	if (itemId)
	{
		for (var i = 0; i < this.menuItems.length; i++)
			if (this.menuItems[i].id && this.menuItems[i].id == itemId)
				return i;
	}

	return -1;
};

BX.TaskMenuPopupWindow.prototype.__addMenuItem = function(menuItem, refItemId)
{
	if (!menuItem || !menuItem.text || !BX.type.isNotEmptyString(menuItem.text) || (menuItem.id && this.getMenuItem(menuItem.id) != null))
		return -1;

	menuItem.layout = { item : null, text : null, hr : null };

	var position = this.getMenuItemPosition(refItemId);
	if (position >= 0)
		this.menuItems = BX.util.insertIntoArray(this.menuItems, position, menuItem);
	else
	{
		this.menuItems.push(menuItem);
		position = this.menuItems.length - 1;
	}

	return position;
};

BX.TaskMenuPopupWindow.prototype.addMenuItem = function(menuItem, refItemId)
{
	var position = this.__addMenuItem(menuItem, refItemId);
	if (position < 0)
		return false;

	this.__createItem(menuItem, position);
	var refItem = this.getMenuItem(refItemId);
	if (refItem != null)
	{
		if (refItem.layout.hr == null)
		{
			refItem.layout.hr = BX.create("div", { props : { className : "popup-window-hr" }, children : [ BX.create("i", {}) ]});
			this.itemsContainer.insertBefore(refItem.layout.hr, refItem.layout.item);
		}

		if (menuItem.layout.hr != null)
			this.itemsContainer.insertBefore(menuItem.layout.hr, refItem.layout.hr);
		this.itemsContainer.insertBefore(menuItem.layout.item, refItem.layout.hr);
	}
	else
	{
		if (menuItem.layout.hr != null)
			this.itemsContainer.appendChild(menuItem.layout.hr);
		this.itemsContainer.appendChild(menuItem.layout.item);
	}
	return true;
};

BX.TaskMenuPopupWindow.prototype.removeMenuItem = function(itemId)
{
	var item = this.getMenuItem(itemId);
	if (!item)
		return;

	for (var position = 0; position < this.menuItems.length; position++)
	{
		if (this.menuItems[position] == item)
		{
			this.menuItems = BX.util.deleteFromArray(this.menuItems, position);
			break;
		}
   	}

	if (position == 0)
	{
		if (this.menuItems[0])
		{
			this.menuItems[0].layout.hr.parentNode.removeChild(this.menuItems[0].layout.hr);
			this.menuItems[0].layout.hr = null;
		}
	}
	else
		item.layout.hr.parentNode.removeChild(item.layout.hr);

	item.layout.item.parentNode.removeChild(item.layout.item);
	item.layout.item = null;
};

BX.TaskMenuPopupWindow.prototype.__createItem = function(item, position)
{
	if (position > 0)
		item.layout.hr = BX.create("div", { props : { className : "popup-window-hr" }, children : [ BX.create("i", {}) ]});

	item.layout.item = BX.create("a", {
		props : { className: "task-menu-popup-item" +  (BX.type.isNotEmptyString(item.className) ? " " + item.className : "")},
		attrs : { title : item.title ? item.title : ""},
		events : item.onclick && BX.type.isFunction(item.onclick) ? { click : BX.proxy(this.onItemClick, {obj : this, item : item }) } : null,
		children : [
			BX.create("span", { props : { className: "task-menu-popup-item-left"} }),
			BX.create("span", { props : { className: "task-menu-popup-item-icon"} }),
			(item.layout.text = BX.create("span", { props : { className: "task-menu-popup-item-text"}, html : item.text })),
			BX.create("span", { props : { className: "task-menu-popup-item-right"} })
		]
	});

	if (item.href)
		item.layout.item.href = item.href;

	return item;
};

BX.TaskMenuPopupWindow.prototype.__createPopup = function()
{
	var domItems = [];
	for (var i = 0; i < this.menuItems.length; i++)
	{
		this.__createItem(this.menuItems[i], i);
		if (this.menuItems[i].layout.hr != null)
			domItems.push(this.menuItems[i].layout.hr);
		domItems.push(this.menuItems[i].layout.item);
	}

	var popupWindow = new BX.PopupWindow("task-menu-popup-" + this.id, this.bindElement, {
		autoHide : this.params.autoHide ? this.params.autoHide : true,
		offsetTop : this.params.offsetTop ? this.params.offsetTop : 1,
		offsetLeft : this.params.offsetLeft ? this.params.offsetLeft : 0,
		lightShadow : this.params.lightShadow ? this.params.lightShadow : true,
		content : BX.create("div", { props : { className : "task-menu-popup" }, children: [
			(this.itemsContainer = BX.create("div", { props : { className : "task-menu-popup-items" }, children: domItems}))
		]})
	});

	if (this.params && this.params.events)
	{
		for (var eventName in this.params.events)
			BX.addCustomEvent(popupWindow, eventName, this.params.events[eventName]);
	}

	return popupWindow;
};

BX.TaskMenuPopupWindow.prototype.onItemClick = function(event)
{
	event = event || window.event;
	if (this.item.onclick && BX.type.isFunction(this.item.onclick))
		BX.proxy(this.item.onclick, this.obj)(event, this.item);
};


BX.TaskDeclinePopup =
{
	popup : null,
	textarea : null,
	params : {},

	show : function(bindElement, params)
	{
		this.params = params || {};

		if (this.popup === null)
			this.popup = this.__init(bindElement);

		this.textarea.value = "";
		this.popup.show();
		BX.focus(this.textarea);
	},

	__init : function(bindElement)
	{
		return BX.PopupWindowManager.create("task-decline-popup", bindElement, {

			offsetLeft : this.params.offsetLeft && BX.type.isNumber(this.params.offsetLeft) ? this.params.offsetLeft : 0,
			offsetTop : this.params.offsetTop && BX.type.isNumber(this.params.offsetTop) ? this.params.offsetTop : 0,
			buttons : [
				new BX.PopupWindowButton({
					text : BX.message("TASKS_DECLINE"),
					className : "popup-window-button-decline",
					events : { click : BX.proxy(this.__onOkButtonClick, this)}
				}),

				new BX.PopupWindowButtonLink({
					text : BX.message("TASKS_CANCEL"),
					className : "popup-window-button-link-cancel",
					events : { click : BX.proxy(this.__onCancelButtonClick, this) }
				})
			],

			content : BX.create("div", { props : { className : "task-decline-popup" }, children: [
				BX.create("div", { props : { className : "task-decline-popup-title" }, text : BX.message("TASKS_DECLINE_REASON") }),
				BX.create("div", { props : { className : "task-decline-popup-reason" }, children : [
					(this.textarea = BX.create("textarea", { props : { className : "task-decline-popup-reason-textarea" }}))
				]})
			]})
			
		});
	},

	__onOkButtonClick : function()
	{
		if (this.params.events && this.params.events.onPopupChange && BX.type.isFunction(this.params.events.onPopupChange))
			BX.proxy(this.params.events.onPopupChange, this)();

		this.__onClosePopup();
	},

	__onCancelButtonClick : function(event)
	{
		this.__onClosePopup();
		BX.PreventDefault(event);
	},

	__onClosePopup : function()
	{
		if (this.params.events && this.params.events.onPopupClose && BX.type.isFunction(this.params.events.onPopupClose))
			BX.proxy(this.params.events.onPopupClose, this)();

		this.popup.close();
	}

};

BX.TaskQuickInfo = {

	popupSettings : {},
	popup : null,
	task : null,
	layout : {
		taskId : null,
		name : null,
		responsible : null,
		director : null,
		status : null,

		files : null,
		priority : null,
		dateCreated : null,
		dateDeadline : null,
		dateStart : null,
		dateEnd : null,
		dateCompleted : null,
		dateCompletedCaption : null,
		dateStarted : null,
		dateStartedCaption : null,

		details : null
	},

	timeoutId : null,
	bindElement : null,
	

	show : function(bindElement, task, settings)
	{
		this.task = task;
		this.bindElement = bindElement;
		if (settings && typeof(settings) == "object")
			this.popupSettings = settings;

		if (this.timeoutId)
			clearTimeout(this.timeoutId);
		this.timeoutId = setTimeout(BX.proxy(this._show, this), 1000);
	},

	_show : function()
	{
		if (!this.bindElement)
			return;

		if (this.popup == null)
			this.popup = this.__createPopup();

		this.popup.setBindElement(this.bindElement);
		this.updatePopup(this.task);
		this.popup.show();

		//BX.unbindAll(this.popup.popupContainer);
		BX.bind(this.popup.popupContainer, "mouseover", BX.proxy(this.onPopupMouseOver, this));
		BX.bind(this.popup.popupContainer, "mouseout", BX.proxy(this.onPopupMouseOut, this));
	},

	updatePopup : function(task)
	{
		if (!this.popup)
			return;

		this.layout.taskId.innerHTML = task.id;
		this.layout.name.innerHTML = task.name;

		this.layout.responsible.innerHTML = task.responsible ? task.responsible : "";
		this.layout.responsible.href = this.__getUserProfileLink(task.responsibleId);
		this.layout.director.innerHTML = task.director ? task.director : "";
		this.layout.director.href = this.__getUserProfileLink(task.directorId);

		this.layout.status.className = "task-quick-info-field-value " + "task-quick-info-status-" + task.status;
		this.layout.status.innerHTML = BX.type.isNotEmptyString(task.status) ?
									   BX.message("TASKS_STATUS_" + task.status.toUpperCase().replace("-", "_")) : "";

		var files = "";
		if (task.files && BX.type.isArray(task.files))
		{
			for (var i = 0; i < task.files.length; i++)
			{
				var file = task.files[i];
				if (file && file.name && file.url)
				{
					files += '<span class="task-quick-info-files-item"><a href="'
						+ file.url + '" target="_blank" class="task-quick-info-files-name">' 
						+ BX.util.htmlspecialchars(file.name) 
						+ '</a>';

					if (file.size)
						files += ' <span class="task-quick-info-files-size">(' + file.size + ')</span>';

					files += '</span>';
				}

			}
		}
		this.layout.files.innerHTML = files;
		this.layout.files.parentNode.style.display = files == "" ? "none" : "block";

		this.layout.priority.className = "task-quick-info-field-value " + "task-quick-info-priority-" + task.priority;
		this.layout.priority.innerHTML = typeof(task.priority) != "undefined" ? BX.message("TASKS_PRIORITY_" + task.priority) : "";

		this.layout.dateCreated.innerHTML = this.formatDate(task.dateCreated);
		this.layout.dateStart.innerHTML = this.formatDate(task.dateStart);
		this.layout.dateEnd.innerHTML = this.formatDate(task.dateEnd);
		this.layout.dateDeadline.innerHTML = this.formatDate(task.dateDeadline);
		if (task.dateDeadline)
			BX.addClass(this.layout.dateDeadline, "task-quick-info-status-overdue");
		else
			BX.removeClass(this.layout.dateDeadline, "task-quick-info-status-overdue");

		if (task.dateStarted)
		{
			this.layout.dateStarted.innerHTML = this.formatDate(task.dateStarted);
			this.layout.dateStartedCaption.style.display = "block";
			this.layout.dateStarted.style.display = "block";
		}
		else
		{
			this.layout.dateStartedCaption.style.display = "none";
			this.layout.dateStarted.style.display = "none";
			BX.cleanNode(this.layout.dateStarted);
		}

		if (task.dateCompleted)
		{
			this.layout.dateCompleted.innerHTML = this.formatDate(task.dateCompleted);
			this.layout.dateCompletedCaption.style.display = "block";
			this.layout.dateCompleted.style.display = "block";
		}
		else
		{
			this.layout.dateCompletedCaption.style.display = "none";
			this.layout.dateCompleted.style.display = "none";
			BX.cleanNode(this.layout.dateCompleted);
		}

		this.layout.details.href = BX.type.isNotEmptyString(task.url) ? task.url : "";
	},

	__getUserProfileLink : function(userId)
	{
		if (this.popupSettings.userProfileUrl && BX.type.isNumber(userId) && userId > 0)
			return this.popupSettings.userProfileUrl.replace(/#user_id#/ig, userId);
		else
			return "";
	},

	__createPopup : function()
	{
		this.popupSettings.lightShadow = this.popupSettings.lightShadow ? this.popupSettings.lightShadow : true;
		this.popupSettings.autoHide = this.popupSettings.lightShadow ? this.popupSettings.lightShadow : true;
		this.popupSettings.angle = this.popupSettings.angle ? this.popupSettings.angle : true;

		this.popupSettings.content = BX.create("div", { props: { className: "task-quick-info" }, children : [
			BX.create("div", { props : { className: "task-quick-info-box task-quick-info-box-title" }, children: [
				BX.create("div", { props : { className: "task-quick-info-title-label"}, 
					children: [
						BX.create("span", { html: BX.message("TASKS_TASK_TITLE_LABEL") }),
						(this.layout.taskId = BX.create("span")),
						BX.create("span", { html: ':' })
					]}),
				(this.layout.name = BX.create("div", { props : { className: "task-quick-info-title" }}))
			]}),
			BX.create("div", { props : { className: "task-quick-info-box" }, children: [
				BX.create("table", { props : { className: "task-quick-info-layout", cellSpacing: 0 }, children : [
					BX.create("tbody", { children : [
						BX.create("tr", {  children : [
							BX.create("td", { props : { className: "task-quick-info-left-column" }, children : [
								BX.create("span", { props : { className: "task-quick-info-fields" }, children : [
									BX.create("span", { props : { className: "task-quick-info-field-name" }, html : BX.message("TASKS_RESPONSIBLE") + ":" }),
									BX.create("span", { props : { className: "task-quick-info-field-name" }, html : BX.message("TASKS_DIRECTOR") + ":" })
								]}),
								BX.create("span", { props : { className: "task-quick-info-values" }, children : [
									BX.create("span", { props : { className: "task-quick-info-field-value" }, children: [
										(this.layout.responsible = BX.create("a", { props : { className: "task-quick-info-user-link", href: "" }}))
									]}),
									BX.create("span", { props : { className: "task-quick-info-field-value" }, children: [
										(this.layout.director = BX.create("a", { props : { className: "task-quick-info-user-link", href: "" }}))
									]})
								]})
							]}),
							BX.create("td", { props : { className: "task-quick-info-right-column" }, children : [
								BX.create("span", { props : { className: "task-quick-info-fields" }, children : [
									BX.create("span", { props : { className: "task-quick-info-field-name" }, html : BX.message("TASKS_STATUS") + ":" }),
									BX.create("span", { props : { className: "task-quick-info-field-name" }, html : BX.message("TASKS_PRIORITY") + ":" })
								]}),
								BX.create("span", { props : { className: "task-quick-info-values" }, children : [
									(this.layout.status = BX.create("span", { props : { className: "task-quick-info-field-value" }})),
									(this.layout.priority = BX.create("span", { props : { className: "task-quick-info-field-value" }}))
								]})
							]})
						]})
					]})
				]})
			]}),
			BX.create("div", { props : { className: "task-quick-info-box task-quick-info-box-files" }, children: [
				BX.create("div", { props : { className: "task-quick-info-files-label" }, html : BX.message("TASKS_FILES") + ":" }),
				(this.layout.files = BX.create("div", { props : { className: "task-quick-info-files-items" }}))
			]}),
			BX.create("div", { props : { className: "task-quick-info-box" }, children: [
				BX.create("table", { props : { className: "task-quick-info-layout", cellSpacing: 0 }, children : [
					BX.create("tbody", { children : [
						BX.create("tr", {  children : [
							BX.create("td", { props : { className: "task-quick-info-left-column" }, children : [
								BX.create("span", { props : { className: "task-quick-info-fields" }, children : [
									BX.create("span", { props : { className: "task-quick-info-field-name" }, html : BX.message("TASKS_DATE_CREATED") + ":" }),
									BX.create("span", { props : { className: "task-quick-info-field-name" }, html : BX.message("TASKS_DATE_START") + ":" }),
									(this.layout.dateStartedCaption = BX.create("span", { props:{ className:"task-quick-info-field-name" }, html:BX.message("TASKS_DATE_STARTED") + ":" }))
								]}),
								BX.create("span", { props : { className: "task-quick-info-values" }, children : [
									(this.layout.dateCreated = BX.create("span", { props:{ className:"task-quick-info-field-value" }})),
									(this.layout.dateStart = BX.create("span", { props:{ className:"task-quick-info-field-value" }})),
									(this.layout.dateStarted = BX.create("span", { props:{ className:"task-quick-info-field-value" }}))
								]})
							]}),
							BX.create("td", { props : { className: "task-quick-info-right-column" }, children : [
								BX.create("span", { props : { className: "task-quick-info-fields" }, children : [
									BX.create("span", { props: { className:"task-quick-info-field-name" }, html: BX.message("TASKS_DATE_DEADLINE") + ":" }),
									BX.create("span", { props : { className: "task-quick-info-field-name" }, html: BX.message("TASKS_DATE_END") + ":" }),
									(this.layout.dateCompletedCaption = BX.create("span", { props:{ className:"task-quick-info-field-name" }, html:BX.message("TASKS_DATE_COMPLETED") + ":" }))
								]}),
								BX.create("span", { props : { className: "task-quick-info-values" }, children : [
									(this.layout.dateDeadline = BX.create("span", { props:{ className:"task-quick-info-field-value" }})),
									(this.layout.dateEnd = BX.create("span", { props:{ className:"task-quick-info-field-value" }})),
									(this.layout.dateCompleted = BX.create("span", { props:{ className:"task-quick-info-field-value" }}))
								]})
							]})
						]})
					 ]})
				 ]})
			]}),
			BX.create("div", { props : { className: "task-quick-info-box-bottom" }, children: [
				(this.layout.details = BX.create("a", {
					props: { className: "task-quick-info-detail-link", href: "" },
					//attrs: { "target" : "_blank" },
					html: BX.message("TASKS_QUICK_INFO_DETAILS"),
					events: {
						click:BX.proxy(this.onDetailClick, this)
					}
				}))
			]})
		]});

		var popup = new BX.PopupWindow("task-quick-info-popup", this.bindElement, this.popupSettings);
		BX.addCustomEvent(popup, "onPopupClose", BX.proxy(this.onPopupClose, this));
		return popup;
	},

	hide : function()
	{
		if (this.popup && this.popup.isShown())
		{
			if (this.timeoutId)
				clearTimeout(this.timeoutId);
			this.timeoutId = setTimeout(BX.proxy(this._hide, this), 300);
		}
		else
			this._hide();
	},

	_hide : function()
	{
		if (this.timeoutId)
			clearTimeout(this.timeoutId);

		this.bindElement = null;
		if (this.popup)
			this.popup.close();
	},

	onDetailClick : function(event)
	{
		event = event || window.event;
		if (this.popupSettings.onDetailClick && BX.type.isFunction(this.popupSettings.onDetailClick))
		{
			this.popupSettings.onDetailClick(event, this.popup, this);
			BX.PreventDefault(event);
		}
	},

	onPopupClose : function()
	{
		BX.unbindAll(this.popup.popupContainer);
	},

	onPopupMouseOver : function()
	{
		if (this.timeoutId)
		   clearTimeout(this.timeoutId);
	},

	onPopupMouseOut : function()
	{
		if (this.timeoutId)
		   clearTimeout(this.timeoutId);
		this.timeoutId = setTimeout(BX.proxy(this._hide, this), 300);
	},

	formatDate : function(date)
	{
		if (!date)
	   		return BX.message("TASKS_QUICK_INFO_EMPTY_DATE");

		var isUTC = this.popupSettings.dateInUTC ? !!this.popupSettings.dateInUTC : false;
		var year = isUTC ? date.getUTCFullYear().toString() : date.getFullYear().toString();
		var month = isUTC ? (date.getUTCMonth()+1).toString() : (date.getMonth()+1).toString();
		var day = isUTC ? date.getUTCDate().toString() : date.getDate().toString();
		var hours = isUTC ? date.getUTCHours() : date.getHours();
		var minutes = isUTC ? date.getUTCMinutes() : date.getMinutes();
		var seconds = isUTC ? date.getUTCSeconds() : date.getSeconds();

		hours = hours.toString();
		var minutes = isUTC ? date.getUTCMinutes().toString() : date.getMinutes().toString();
//	   	var format = (this.popupSettings.dateFormat ? this.popupSettings.dateFormat : BX.message('FORMAT_DATETIME'))
	   	var format = BX.message('FORMAT_DATETIME')
	   		.replace(/YYYY/g, "<span class=\"task-quick-info-date-year\">" + year.toString() + "</span>")
	   		.replace(/MMMM/g, BX.util.str_pad_left(month.toString(), 2, "0"))
	   		.replace(/MM/g, BX.util.str_pad_left(month.toString(), 2, "0"))
			.replace(/MI/g, BX.util.str_pad_left(minutes.toString(), 2, "0"))
	   		.replace(/M/g, BX.util.str_pad_left(month.toString(), 2, "0"))
	   		.replace(/DD/g, BX.util.str_pad_left(day.toString(), 2, "0"))
			.replace(/GG/g, BX.util.str_pad_left(hours.toString(), 2, '0'))
			.replace(/HH/g, BX.util.str_pad_left(hours.toString(), 2, '0'))
			.replace(/SS/g, BX.util.str_pad_left(seconds.toString(), 2, "0"));

		if (BX.isAmPmMode())
		{
			var amPm = 'am';
			if (hours > 12)
			{
				hours = hours - 12;
				amPm = 'pm';
			}
			else if (hours == 12)
			{
				amPm = 'pm';
			}

			format = format.replace(/TT/g, amPm.toUpperCase())
				.replace(/T/g, amPm)
				.replace(/G/g, BX.util.str_pad_left(hours.toString(), 2, '0'))
				.replace(/H/g, BX.util.str_pad_left(hours.toString(), 2, '0'));
		}

		format = format

//		if ((hours == 0 || (BX.isAmPmMode() && amPm == 'am' && hours == 12)) && minutes == 0)
			return format;
//		else
//	   		return format + "&nbsp;&nbsp;" + BX.util.str_pad_left(hours, 2, "0") + ":" + BX.util.str_pad_left(minutes, 2, "0") + (amPm != undefined ? amPm : '');
	}

};


var TaskLegendPopup = function(steps, bindELement, settings)
{
	this.currentStep = null;
	this.layout = {
		paging : null,
		previousButton : null,
		nextButton : null
	};

	this.steps = [];
	var content = [];
	var paging = [];
	if (BX.type.isArray(steps))
	{
		for (var i = 0; i < steps.length; i++)
		{
			var step = steps[i];
			if (!BX.type.isNotEmptyString(step.title) || !BX.type.isNotEmptyString(step.content))
				continue;

			var stepContent = BX.create("div", { props : { className : "task-legend-popup-step" },  children : [
				BX.create("div", { props:{ className: "task-legend-popup-title" }, html : step.title }),
				BX.create("div", { props:{ className: "task-legend-popup-content" }, html : step.content })
			]});
			var stepPage = BX.create("span", {
				props : { className : "task-legend-popup-page"},
				html : i+1,
				events : { click : BX.proxy(this.onPageClick, this)}
			});
			this.steps.push({ content : stepContent, page : stepPage });

			content.push(stepContent);
			paging.push(stepPage);
		}
	}

	this.popup = BX.PopupWindowManager.create("task-legend-popup", bindELement, {
		closeIcon : { top : "10px", right : "15px"},
		offsetLeft : -710,
		angle : { offset : 737 },
		offsetTop : 1,
		closeByEsc : true,
		content : BX.create("div", { props : { className : BX.message("TASKS_LEGEND_CLASSNAME") }, children : [
			BX.create("div", { props : { className : "task-legend-popup-contents" }, children : content }),
			BX.create("div", { props : { className : "task-legend-popup-navigation" }, children : [
				(this.layout.paging = BX.create("div", { props:{ className: "task-legend-popup-paging" }, children: paging })),
				BX.create("div", { props : { className : "task-legend-popup-buttons" }, children : [
					(this.layout.previousButton = BX.create("span", {
						props:{ className: "popup-window-button" },
						events : { click : BX.proxy(this.showPrevStep, this) },
						children:[
							BX.create("span", { props:{ className: "popup-window-button-left" }}),
							BX.create("span", { props:{ className: "popup-window-button-text" }, html: BX.message("TASKS_LEGEND_PREV") }),
							BX.create("span", { props:{ className: "popup-window-button-right" }})
						]
					})),
					(this.layout.nextButton = BX.create("span", {
						props:{ className:"popup-window-button" },
						events : { click : BX.proxy(this.showNextStep, this) },
						children:[
							BX.create("span", { props:{ className: "popup-window-button-left" }}),
							BX.create("span", { props:{ className: "popup-window-button-text" }, html: BX.message("TASKS_LEGEND_NEXT") }),
							BX.create("span", { props:{ className: "popup-window-button-right" }})
						]
					}))
				]})
			]})
		]})
	});

	this.showStepByNumber(0);
};


TaskLegendPopup.prototype.showStepByNumber = function(number)
{
	if (!this.steps[number] || this.currentStep == this.steps[number])
		return;

	if (this.currentStep != null)
	{
		this.currentStep.content.style.display = "none";
		BX.removeClass(this.currentStep.page, "task-legend-popup-page-selected");
	}

	this.steps[number].content.style.display = "block";
	BX.addClass(this.steps[number].page, "task-legend-popup-page-selected");

	this.currentStep = this.steps[number];
};

TaskLegendPopup.prototype.onPageClick = function(event)
{
	for (var i = 0; i < this.steps.length; i++)
	{
		if (this.steps[i].page == BX.proxy_context)
		{
			this.showStepByNumber(i);
			break;
		}
	}
};

TaskLegendPopup.prototype.showNextStep = function()
{
	var currentPosition = this.getStepPosition(this.currentStep);

	if (currentPosition + 1 > this.steps.length - 1)
		this.showStepByNumber(0);
	else
		this.showStepByNumber(currentPosition + 1);
};

TaskLegendPopup.prototype.showPrevStep = function()
{
	var currentPosition = this.getStepPosition(this.currentStep);
	if (currentPosition > 0)
		this.showStepByNumber(currentPosition - 1);
	else
		this.showStepByNumber(this.steps.length - 1);
};

TaskLegendPopup.prototype.getStepPosition = function(step)
{
	for (var i = 0; i < this.steps.length; i++)
	{
		if (this.steps[i] == step)
			return i;
	}

	return -1;
};

BX.TaskLegendPopup = {
	legend : null,
	show :  function(steps, bindElement, settings)
	{
		if (this.popup == null)
			this.legend = new TaskLegendPopup(steps, bindElement, settings);

		this.legend.popup.show();
	}
};

})(window);