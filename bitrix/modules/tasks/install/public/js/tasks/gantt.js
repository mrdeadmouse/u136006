(function() {

if (BX.GanttChart)
	return;

/*==================================GanttChart==================================*/
BX.GanttChart = function(domNode, currentDatetime, settings)
{
	this.benchTime = new Date().getTime();

	this.settings = settings || {};

	this.dayInPixels = 24;
	this.hourInPixels = this.dayInPixels / 24;
	this.firstWeekDay = 1;
	this.autoScrollCoeff = BX.type.isNumber(settings.autoScrollCoeff) ? settings.autoScrollCoeff : 4;
	this.userProfileUrl = BX.type.isNotEmptyString(settings.userProfileUrl) ? settings.userProfileUrl : "";

	this.chartContainer = {
		element : BX.type.isDomNode(domNode) ? domNode : null,
		padding : this.dayInPixels,
		width : 0,
		pos : { left: 0, top: 0 },
		minPageX : 0,
		maxPageX : 0
	};
	this.adjustChartContainer();

	this.gutterOffset = this.normalizeGutterOffset(BX.type.isNumber(settings.gutterOffset) ? settings.gutterOffset : 300);
	this.dragClientX = 0;

	this.currentDatetime = BX.GanttChart.isDate(currentDatetime) ? BX.GanttChart.convertDateToUTC(currentDatetime) : BX.GanttChart.convertDateToUTC(new Date());
	this.currentDate = new Date(Date.UTC(this.currentDatetime.getUTCFullYear(), this.currentDatetime.getUTCMonth(), this.currentDatetime.getUTCDate(), 0, 0, 0, 0));

	var daysInViewport = Math.ceil(this.chartContainer.width / this.dayInPixels);
	this.minDate = new Date(Date.UTC(this.currentDatetime.getUTCFullYear(), this.currentDatetime.getUTCMonth(), this.currentDatetime.getUTCDate() - daysInViewport, 0, 0, 0, 0));
	this.minDate.setUTCMonth(this.minDate.getUTCMonth()-1, 1);
	this.minDate.setUTCMonth(this.minDate.getUTCMonth(), 1);
	this.maxDate = new Date(Date.UTC(this.currentDatetime.getUTCFullYear(), this.currentDatetime.getUTCMonth(), this.currentDatetime.getUTCDate() + daysInViewport, 0, 0, 0, 0));
	this.maxDate.setUTCMonth(this.maxDate.getUTCMonth()+1, 1);
	this.maxDate.setUTCDate(BX.GanttChart.getDaysInMonth(this.maxDate.getUTCMonth(), this.maxDate.getUTCFullYear())+1);

	this.tasks = {};
	this.projectsCnt = 0;
	this.projects = {
		0 : new GanttProject(this, 0, "Default Project")
	};

	this.treeMode = true;	// show tasks as tree or as plain

	//Dom layout
	this.layout = {
		root : null,
		list : null,
		tree : null,
		gutter : null,
		timelineInner : null,
		scalePrimary : null,
		scaleSecondary : null,
		timelineData : null,
		currentDay : null
	};

	this.datetimeFormat = BX.type.isNotEmptyString(settings.datetimeFormat) ? settings.datetimeFormat : "DD.MM.YYYY HH:MI:SS";
	this.dateFormat = BX.type.isNotEmptyString(settings.dateFormat) ? settings.dateFormat : "DD.MM.YYYY";

	this.tooltip = new GanttTooltip(this);
	this.allowRowHover = true;

	//Chart Events
	if (this.settings.events)
	{
		for (var eventName in this.settings.events)
			BX.addCustomEvent(this, eventName, this.settings.events[eventName]);
	}
	BX.bind(window, "resize", BX.proxy(this.onWindowResize, this));

	this.isIE = false;
	this.ieVersion = 0;
	/*@cc_on
	  this.isIE = true;
	  @if (@_jscript_version == 10)
		this.ieVersion = 10;
	  @elif (@_jscript_version == 9)
		this.ieVersion = 9;
	  @elif (@_jscript_version == 5.8)
		this.ieVersion = 8;
	  @elif (@_jscript_version == 5.7)
		this.ieVersion = 7;
	  @end

	@*/
};

BX.GanttChart.prototype.draw = function()
{
	if (this.chartContainer.element == null)
		return; 

	this.drawLayout();
	this.drawTasks();
	this.autoScroll();
};

BX.GanttChart.prototype.drawLayout = function()
{
	if (!this.chartContainer.element || this.layout.root != null)
		return;

	this.layout.root = BX.create("div", { props : {className: "task-gantt" + (this.ieVersion ? " task-gantt-ie" + this.ieVersion : "")}, style : { width : this.chartContainer.width + "px"}, children : [

		(this.layout.list = BX.create("div", {
			props : { className: "task-gantt-list"},
			style : { width : this.gutterOffset + "px" },
			children : [
				BX.create("div", { props : { className: "task-gantt-list-controls" }}),
				BX.create("div", { props : { className: "task-gantt-list-title" }, text : BX.message("TASKS_GANTT_CHART_TITLE") }),
				(this.layout.tree = BX.create("div", { props : { className: "task-gantt-items" }})),
				(this.layout.gutter = BX.create("div", { props : { className: "task-gantt-gutter" }, events : {
					mousedown : BX.proxy(this.onGutterMouseDown, this)
				}}))
			]
		})),

		(this.layout.timeline = BX.create("div", { props : { className: "task-gantt-timeline" },
			children : [
				(this.layout.timelineInner =  BX.create("div", { props : { className: "task-gantt-timeline-inner" },
					events : {
						mousedown : BX.proxy(this.onTimelineMouseDown, this)
					},
					children : [
						BX.create("div", { props : { className: "task-gantt-timeline-head" }, children : [
							(this.layout.scalePrimary =  BX.create("div", { props : { className: "task-gantt-scale-primary" }})),
							(this.layout.scaleSecondary = BX.create("div", { props : { className: "task-gantt-scale-secondary" }}))
						]}),
						(this.layout.timelineData = BX.create("div", { props : { className: "task-gantt-timeline-data" }})),
						(this.layout.currentDay = BX.create("div", { props : { className: "task-gantt-current-day" }, style : {
							left : this.getPixelsInPeriod(this.minDate, this.currentDate, true) - 1 + "px"
						}})),
						(this.tooltip.getLayout())
					]
				}))
			]
		}))
	]});

	this.setTimelineWidth();

	var timeline = this.createTimelineHead(this.minDate, this.maxDate);
	this.layout.scalePrimary.innerHTML = timeline.monthTimeline;
	this.layout.scaleSecondary.innerHTML = timeline.dayTimeline;
	this.chartContainer.element.appendChild(this.layout.root);
};

BX.GanttChart.prototype.drawTasks = function()
{
	if (this.layout.root == null)
		return;

	var taskTree = document.createDocumentFragment();
	var taskData = document.createDocumentFragment();

	var projects = this.getSortedProjects();
	for (var i = 0; i < projects.length; i++)
	{
		if (projects[i].id != 0)
		{
			taskTree.appendChild(projects[i].createItem());
			taskData.appendChild(projects[i].createBars());
		}

		this.drawTasksRecursive(projects[i].tasks, taskTree, taskData);
	}

	this.layout.tree.appendChild(taskTree);
	this.layout.timelineData.appendChild(taskData);

	this.adjustChartContainer();
};

BX.GanttChart.prototype.drawTasksRecursive = function(tasks, taskTree, taskData)
{
	for (var i = 0, length = tasks.length; i < length; i++)
	{
		taskTree.appendChild(tasks[i].createItem());
		taskData.appendChild(tasks[i].createBars());
		if (tasks[i].childTasks.length > 0)
			this.drawTasksRecursive(tasks[i].childTasks, taskTree, taskData);
	}
};

BX.GanttChart.prototype.drawCurrentDay = function()
{
	if (this.currentDate < this.minDate || this.currentDate > this.maxDate)
		return;

	this.layout.currentDay.style.left = this.getPixelsInPeriod(this.minDate, this.currentDate, true) - 1 + "px";
};

BX.GanttChart.prototype.setTimelineWidth = function()
{
	this.layout.timelineInner.style.width = this.getPixelsInPeriod(this.minDate, this.maxDate, true) - 1 + "px";

	if (this.minDate.getUTCDay() > 0)
		this.layout.timelineInner.style.backgroundPosition = "-" + this.minDate.getUTCDay() * this.dayInPixels + "px 0px";
	else
		this.layout.timelineInner.style.backgroundPosition = "0 0";
};

BX.GanttChart.prototype.createTimelineHead = function(startDate, endDate, sepPosition)
{
	var currentDate = new Date(startDate.getTime()); //clone date
	var monthTimeline = "", dayTimeline = "";
	while (currentDate <  endDate)
	{
		if (monthTimeline == "" || currentDate.getUTCDate() == 1)
		{
			var daysInMonth = BX.GanttChart.getDaysInMonth(currentDate.getUTCMonth(), currentDate.getUTCFullYear());
			if (monthTimeline == "") //First Month
			{
				if (startDate.getUTCMonth() == endDate.getUTCMonth() && startDate.getUTCFullYear() == endDate.getUTCFullYear())
					daysInMonth = endDate.getUTCDate() - startDate.getUTCDate();
				else
					daysInMonth = daysInMonth - startDate.getUTCDate() + 1;
			}
			else if (endDate.getUTCMonth() == currentDate.getUTCMonth() && endDate.getUTCFullYear() == currentDate.getUTCFullYear())
				daysInMonth = endDate.getUTCDate() - 1;

			if (monthTimeline != "")
				monthTimeline += '<span class="task-gantt-scale-month-sep"></span>';

			monthTimeline +=
				'<span class="task-gantt-scale-month" ' +
				'style="width:' + (daysInMonth * this.dayInPixels - 1) + 'px"><span class="task-gantt-scale-month-text">' +
				BX.GanttChart.getMonthName(currentDate.getUTCMonth()) + " " + currentDate.getUTCFullYear() + '</span></span>';
		}

		if (dayTimeline != "")
			dayTimeline += '<span class="task-gantt-scale-day-sep"></span>';
		dayTimeline += '<span class="task-gantt-scale-day' +
						(currentDate.getUTCDay() % 6 == 0 ? " task-gantt-scale-day-weekend" : "") +
						(this.currentDate.getTime() == currentDate.getTime() ? " task-gantt-scale-day-current" : "") + '">' +
						currentDate.getUTCDate() + '</span>';

		currentDate.setUTCDate(currentDate.getUTCDate() + 1);
	}

	if (dayTimeline != "" && sepPosition && (sepPosition == "left" || sepPosition == "both"))
	{
		monthTimeline = '<span class="task-gantt-scale-month-sep"></span>' + monthTimeline;
		dayTimeline = '<span class="task-gantt-scale-day-sep"></span>' + dayTimeline;
	}
	else if (dayTimeline != "" && sepPosition && (sepPosition == "right" || sepPosition == "both"))
	{
		monthTimeline += '<span class="task-gantt-scale-month-sep"></span>';
		dayTimeline += '<span class="task-gantt-scale-day-sep"></span>';
	}

	return { monthTimeline : monthTimeline, dayTimeline : dayTimeline };
};

BX.GanttChart.prototype.expandTimeline = function(startDate, endDate)
{
	this.expandTimelineLeft(startDate);
	this.expandTimelineRight(endDate);
};

BX.GanttChart.prototype.expandTimelineLeft = function(date)
{
	var offset = this.getPixelsInPeriod(date, this.minDate, true);
	var timeline = this.createTimelineHead(date, this.minDate, "right");

	this.minDate = date;

	var scrollLeft = this.layout.timeline.scrollLeft;
	this.setTimelineWidth();
	for	(var taskId in this.tasks)
		this.tasks[taskId].offsetBars(offset);

	this.drawCurrentDay();

	this.layout.timeline.scrollLeft = scrollLeft + offset;

	this.layout.scalePrimary.innerHTML = timeline.monthTimeline + this.layout.scalePrimary.innerHTML;
	this.layout.scaleSecondary.innerHTML = timeline.dayTimeline + this.layout.scaleSecondary.innerHTML;
};

BX.GanttChart.prototype.expandTimelineRight = function(date)
{
	var timeline = this.createTimelineHead(this.maxDate, date, "left");
	this.maxDate = date;

	var scrollLeft = this.layout.timeline.scrollLeft;
	this.setTimelineWidth();
	this.layout.scalePrimary.innerHTML += timeline.monthTimeline;
	this.layout.scaleSecondary.innerHTML += timeline.dayTimeline;
	this.layout.timeline.scrollLeft = scrollLeft;
};

BX.GanttChart.prototype.autoExpandTimeline = function(dates)
{
	if (!BX.type.isArray(dates))
		dates = [dates];

	for (var i = 0; i < dates.length; i++)
	{
		var date = dates[i];
		var dateInDays = BX.GanttChart.getDaysInPeriod(this.minDate, date);

		if (dateInDays <= BX.GanttChart.getDaysInMonth(this.minDate.getUTCMonth(), this.minDate.getUTCFullYear()))
		{
			var newMinDate = new Date(date < this.minDate ? date.getTime() : this.minDate.getTime());
			newMinDate.setUTCDate(1);
			newMinDate.setUTCHours(0, 0, 0, 0);
			newMinDate.setUTCMonth(newMinDate.getUTCMonth() - 2);
			this.expandTimelineLeft(newMinDate);
			continue;
		}

		var rightMaxOffset = BX.GanttChart.getDaysInPeriod(this.minDate, this.maxDate) -
							 BX.GanttChart.getDaysInMonth(this.maxDate.getUTCMonth(), this.maxDate.getUTCFullYear());

		if (dateInDays >= rightMaxOffset)
		{
			var newMaxDate = new Date(date > this.maxDate ? date.getTime() : this.maxDate.getTime());
			newMaxDate.setUTCDate(1);
			newMaxDate.setUTCHours(0, 0, 0, 0);
			newMaxDate.setUTCMonth(newMaxDate.getUTCMonth() + 2);
			newMaxDate.setUTCDate(BX.GanttChart.getDaysInMonth(newMaxDate.getUTCMonth(), newMaxDate.getUTCFullYear()) + 1);
			this.expandTimelineRight(newMaxDate);
		}
	}

};

BX.GanttChart.prototype.adjustChartContainer = function()
{
	if (this.chartContainer.element != null)
	{
		this.chartContainer.width = this.chartContainer.element.offsetWidth;
		this.chartContainer.pos = BX.pos(this.chartContainer.element);
		this.adjustChartContainerPadding();
	}
};

BX.GanttChart.prototype.adjustChartContainerPadding = function()
{
	if (this.chartContainer.element != null)
	{
		this.chartContainer.minPageX = this.chartContainer.pos.left + this.gutterOffset + this.chartContainer.padding;
		this.chartContainer.maxPageX = this.chartContainer.pos.left + this.chartContainer.width - this.chartContainer.padding;
	}
};

BX.GanttChart.prototype.scrollToDate = function(date)
{
	if (!BX.GanttChart.isDate(date) || date < this.minDate || date > this.maxDate)
		return;

	var scrollWidth = this.getPixelsInPeriod(this.minDate, this.maxDate, false);
	//this.profile("scrollWidth");

	//var offsetWidth = this.layout.timeline.offsetWidth;
	var offsetWidth = this.chartContainer.width - this.gutterOffset + "px";

	//this.profile("offsetWidth");
	var maxScrollLeft = scrollWidth - offsetWidth;

	var dateOffset = this.getPixelsInPeriod(this.minDate, date, true);
	this.layout.timeline.scrollLeft = dateOffset > maxScrollLeft ? maxScrollLeft : dateOffset;
	//this.profile("scrollLeft");
};

BX.GanttChart.prototype.autoScroll = function()
{
	var autoDate = new Date(this.currentDate.getTime());
	autoDate.setUTCDate(autoDate.getUTCDate() - Math.floor((this.chartContainer.width - this.gutterOffset) / this.autoScrollCoeff / this.dayInPixels));
	this.scrollToDate(autoDate);
};

BX.GanttChart.prototype.addProject = function(id, name)
{
	if (id && this.projects[id])
   		return this.projects[id];

	var project = this.__createProject(id, name);
	if (!project)
		return null;

	this.__addProject(project);

	if (this.layout.root != null)
		this.__addProjectDynamic(project);

	return project;
};

BX.GanttChart.prototype.addProjectFromJSON = function(projectJSON)
{
	if (!projectJSON || typeof(projectJSON) != "object")
		return null;

	if (projectJSON.id && this.projects[projectJSON.id])
   		return this.projects[projectJSON.id];

	var project = this.__createProject(projectJSON.id, projectJSON.name);
	if (project == null)
		return null;

	if (typeof(projectJSON.opened) != "undefined")
		project.opened = !!projectJSON.opened;

	if (BX.type.isArray(projectJSON.menuItems))
		project.menuItems = projectJSON.menuItems;

	this.__addProject(project);

	if (this.layout.root != null)
		this.__addProjectDynamic(project);

	return project;
};

BX.GanttChart.prototype.addProjectsFromJSON = function(arProjectJSON)
{
	if (BX.type.isArray(arProjectJSON))
		for (var i = 0; i < arProjectJSON.length; i++)
			this.addProjectFromJSON(arProjectJSON[i]);
};

BX.GanttChart.prototype.__createProject = function(id, name)
{
	if (!BX.type.isNumber(id) || !BX.type.isNotEmptyString(name))
		return null;

	return new GanttProject(this, id, name);
};

BX.GanttChart.prototype.__addProject = function(ganttProject)
{
	if (!ganttProject || typeof(ganttProject) != "object" || !(ganttProject instanceof GanttProject))
		return null;

	if (this.projects[ganttProject.id])
		return this.projects[ganttProject.id];

	this.projectsCnt += 100;
	ganttProject.sort = this.projectsCnt;
	this.projects[ganttProject.id] = ganttProject;
	
	return ganttProject;
};

BX.GanttChart.prototype.__addProjectDynamic = function(ganttProject)
{
	var item = ganttProject.createItem();
	var row = ganttProject.createBars();

	var projects = this.getSortedProjects();
	if (projects[1] && projects[1] != ganttProject)
	{
		ganttProject.sort = projects[1].sort - 5;
		this.layout.tree.insertBefore(item, projects[1].layout.item);
	   	this.layout.timelineData.insertBefore(row, projects[1].layout.row);
	}
	else
	{
		this.layout.tree.appendChild(item);
	   	this.layout.timelineData.appendChild(row);
	}
};

BX.GanttChart.prototype.addTask = function(id, name, status, dateCreated)
{
	if (id && this.tasks[id])
   		return this.tasks[id];

	var task = this.__createTask(id, name, status, dateCreated);
	if (!task)
		return null;

	task.setProject(0);
	this.__addTask(task);

	if (this.layout.root != null)
		this.__addTaskDynamic(task);

	return task;
};

BX.GanttChart.prototype.addTaskFromJSON = function(taskJSON)
{
	if (!taskJSON || typeof(taskJSON) != "object")
		return null;

	if (taskJSON.id && this.tasks[taskJSON.id])
   		return this.tasks[taskJSON.id];

	var task = this.__createTask(taskJSON.id, taskJSON.name, taskJSON.status, taskJSON.dateCreated);
	if (task == null)
		return null;

	task.setTaskFromJSON(taskJSON);
	task.setProject(taskJSON.projectId);
	this.__addTask(task);

	if (taskJSON.children && BX.type.isArray(taskJSON.children))
	{
		for (var i = 0; i < taskJSON.children.length; i++)
		{
			taskJSON.children[i].parentTask = task;
			this.addTaskFromJSON(taskJSON.children[i]);
		}
	}

	if (taskJSON.parentTask !== null && this.layout.root != null)
		this.__addTaskDynamic(task);

	return task;
};

BX.GanttChart.prototype.addTasksFromJSON = function(arTaskJSON)
{
	if (BX.type.isArray(arTaskJSON))
		for (var i = 0; i < arTaskJSON.length; i++)
			this.addTaskFromJSON(arTaskJSON[i]);
};

BX.GanttChart.prototype.__createTask = function(id, name, status, dateCreated)
{
	if (!BX.type.isNumber(id) || !BX.type.isNotEmptyString(name) || !BX.type.isNotEmptyString(status))
		return null;

	return new GanttTask(this, id, name, status, dateCreated);
};

BX.GanttChart.prototype.__addTask = function(ganttTask)
{
	if (!ganttTask || typeof(ganttTask) != "object" || !(ganttTask instanceof GanttTask))
		return null;

	if (this.tasks[ganttTask.id])
		return this.tasks[ganttTask.id];

	this.tasks[ganttTask.id] = ganttTask;

	var taskMinDate = ganttTask.getMinDate();
	var taskMaxDate = ganttTask.getMaxDate();

	if (this.layout.root == null)
	{
		if ((taskMinDate - this.minDate) < BX.GanttChart.getMsByDays(60))
	   	{
	   		this.minDate = taskMinDate;
	   		this.minDate.setUTCDate(1);
	   		this.minDate.setUTCMonth(this.minDate.getUTCMonth()-2);
	   		this.minDate.setUTCHours(0, 0, 0, 0);
	   	}

	   	if ( (this.maxDate - taskMaxDate) < BX.GanttChart.getMsByDays(60))
	   	{
	   		this.maxDate = taskMaxDate;
	   		this.maxDate.setUTCMonth(this.maxDate.getUTCMonth()+2, 1);
	   		this.maxDate.setUTCHours(0, 0, 0, 0);
	   		this.maxDate.setUTCDate(BX.GanttChart.getDaysInMonth(taskMaxDate.getUTCMonth(), taskMaxDate.getUTCFullYear()) + 1);
	   	}
	}
	else
		this.autoExpandTimeline([taskMinDate, taskMaxDate]);

	return ganttTask;
};

BX.GanttChart.prototype.__addTaskDynamic = function(ganttTask)
{
	var taskTree = document.createDocumentFragment();
   	var taskData = document.createDocumentFragment();

	//this.autoExpandTimeline([this.getMinDate(), this.getMaxDate()]);
	this.drawTasksRecursive([ganttTask], taskTree, taskData);

	var targetItem = ganttTask.parentTask != null ? ganttTask.parentTask.layout.item : ganttTask.project.layout.item;
	var targetRow = ganttTask.parentTask != null ? ganttTask.parentTask.layout.row : ganttTask.project.layout.row;

	if (!targetItem && this.layout.tree.firstChild)
	{
		this.layout.tree.insertBefore(taskTree, this.layout.tree.firstChild);
	   	this.layout.timelineData.insertBefore(taskData, this.layout.timelineData.firstChild);
	}
	else if (targetItem && targetItem.nextSibling)
	{
		this.layout.tree.insertBefore(taskTree, targetItem.nextSibling);
	   	this.layout.timelineData.insertBefore(taskData, targetRow.nextSibling);
	}
	else
	{
		this.layout.tree.appendChild(taskTree);
	   	this.layout.timelineData.appendChild(taskData);
	}

	if (ganttTask.parentTask != null)
		ganttTask.parentTask.redraw();
};

BX.GanttChart.prototype.removeTask = function(taskId)
{
	var task = this.getTaskById(taskId);
	if (!task)
		return;

	if (task.project != null)
		task.project.removeTask(task);

	this.__removeTaskChildren(task, task.childTasks, 1);

	if (task.parentTask != null)
	{
		var parentTask = task.parentTask;
		parentTask.removeChild(task);
		parentTask.redraw();
	}

	delete this.tasks[taskId];

	if (this.layout.root != null)
	{
		this.layout.tree.removeChild(task.layout.item);
		this.layout.timelineData.removeChild(task.layout.row);
	}

	BX.onCustomEvent(this, "onTaskDelete", [task]);
};

BX.GanttChart.prototype.__removeTaskChildren = function(deletedTask, tasks, depthLevel)
{
	if (!BX.type.isArray(tasks) || tasks.length < 1)
		return;

	depthLevel = BX.type.isNumber(depthLevel) && depthLevel > 0  ? depthLevel : 1;

	for (var i = 0, length = tasks.length; i < length; i++)
	{
		if (depthLevel == 1)
		{
			if (deletedTask.parentTask != null)
				deletedTask.parentTask.addChild(tasks[i]);
			else
			{
				tasks[i].depthLevel = depthLevel;
				tasks[i].parentTask = null;
				tasks[i].project.addTask(tasks[i]);
			}
		}
		else
			tasks[i].depthLevel = depthLevel;

		this.__removeTaskChildren(deletedTask, tasks[i].childTasks, (depthLevel == 1 && deletedTask.parentTask != null ? tasks[i].depthLevel : depthLevel) + 1);
		tasks[i].redraw();
	}
};

BX.GanttChart.prototype.updateTask = function(taskId, taskJSON)
{
	var task = this.getTaskById(taskId);
	if (!task)
		return false;

	//Reload page if project has been changed
	if (typeof(taskJSON.projectId) != "undefined" && taskJSON.projectId != task.projectId)
	{
		top.location.href = top.location.href;
		return;
	}

	//Parent Task has been changed
	if (typeof(taskJSON.parentTaskId) != "undefined" && taskJSON.parentTaskId != task.parentTaskId)
	{
		var newParentTask = this.getTaskById(taskJSON.parentTaskId);
		var oldParentTask = task.parentTask;

		if (newParentTask != null && newParentTask.project == task.project)
		{
			if (newParentTask.addChild(task))
			{
				newParentTask.redraw();
				newParentTask.expand();
				if (oldParentTask != null)
					oldParentTask.redraw();
				this.__moveTask(newParentTask, task);
			}
		}
		else if (task.depthLevel > 1)
		{
			var target = null;
			var currentTarget = task;
			while ((currentTarget = currentTarget.parentTask) != null)
				target = currentTarget;

			if (target != null)
				target = target.getPreviousTask();
			target = target != null ? target : task.project;

			if (oldParentTask != null)
				oldParentTask.removeChild(task);

			task.project.addTask(task);

			if (oldParentTask != null)
			   oldParentTask.redraw();

		   this.__moveTask(target, task);
		}

		delete taskJSON.parentTaskId;
	}

	task.setTaskFromJSON(taskJSON);
	task.redraw();

	return true;
};

BX.GanttChart.prototype.__moveTask = function(target, task)
{
	var item = this.layout.tree.removeChild(task.layout.item);
	var row = this.layout.timelineData.removeChild(task.layout.row);

	var targetItem = target && target.layout.item ? target.layout.item : null;
	var targetRow = target && target.layout.row ? target.layout.row : null;

	if (!targetItem && this.layout.tree.firstChild)
	{
		this.layout.tree.insertBefore(item, this.layout.tree.firstChild);
		this.layout.timelineData.insertBefore(row, this.layout.timelineData.firstChild);
	}
	else if (targetItem && targetItem.nextSibling)
	{
		this.layout.tree.insertBefore(item, targetItem.nextSibling);
		this.layout.timelineData.insertBefore(row, targetRow.nextSibling);
	}
	else
	{
		this.layout.tree.appendChild(item);
		this.layout.timelineData.appendChild(row);
	}

	task.depthLevel = task.parentTask != null ? task.parentTask.depthLevel + 1 : 1;
	task.redraw();

	for (var i = 0; i < task.childTasks.length; i++)
		this.__moveTask(task, task.childTasks[i]);
};

BX.GanttChart.prototype.getTaskById = function(taskId)
{
	if (this.tasks[taskId])
		return this.tasks[taskId];

	return null;
};

BX.GanttChart.prototype.getProjectById = function(projectId)
{
	if (this.projects[projectId])
		return this.projects[projectId];

	return null;
};

BX.GanttChart.prototype.getDefaultProject = function()
{
	return this.getProjectById(0);
};

BX.GanttChart.prototype.getSortedProjects = function()
{
	var projects = [];
	for (var projectId in this.projects)
		projects.push(this.projects[projectId]);

	return projects.sort(function(a,b) { return a.sort - b.sort });
};

BX.GanttChart.prototype.getPixelsInPeriod = function(startDate, endDate, skipUnit, correctionHours)
{
	return BX.GanttChart.getDaysInPeriod(startDate, endDate) * this.dayInPixels + (correctionHours ? correctionHours : 0) + (!!skipUnit ? 0 : "px");
};

BX.GanttChart.prototype.getDateFromPixels = function(pixels)
{
	var date = new Date(this.minDate.getTime());
	date.setUTCHours(date.getUTCHours() + pixels / this.hourInPixels);
	return date;
};

BX.GanttChart.prototype.getUTCHoursFromPixels = function(pixels)
{
	return pixels * this.hourInPixels;
};

BX.GanttChart.prototype.profile = function(title)
{
	if (typeof(console) != "undefined")
	{
		var currentTime = new Date().getTime();
		//c onsole.log(title + ": " + ((currentTime - this.benchTime) / 1000) + " sec. ");
		this.benchTime = new Date().getTime();
	}
};

BX.GanttChart.prototype.normalizeGutterOffset = function(offset)
{
	var minOffset = 7;
	var maxOffset = this.chartContainer.width - 100;
	return Math.min(Math.max(offset, minOffset), maxOffset > minOffset ? maxOffset : minOffset);
};

BX.GanttChart.prototype.setGutterOffset = function(offset)
{
	this.gutterOffset = this.normalizeGutterOffset(offset);
	this.layout.list.style.width = this.gutterOffset + "px";
	return this.gutterOffset;
};

/*==========Handlers==========*/
BX.GanttChart.prototype.onGutterMouseDown = function(event)
{
	event = event || window.event;
	if (!BX.GanttChart.isLeftClick(event))
		return;

	BX.bind(document, "mouseup", BX.proxy(this.onGutterMouseUp, this));
	BX.bind(document, "mousemove", BX.proxy(this.onGutterMouseMove, this));

	this.gutterClientX = event.clientX;
	this.allowRowHover = false;

	document.onmousedown = BX.False;
	document.body.onselectstart = BX.False;
	document.body.ondragstart = BX.False;
	document.body.style.MozUserSelect = "none";
	document.body.style.cursor = this.isIE && this.ieVersion < 9 ? "e-resize" : "ew-resize";
};

BX.GanttChart.prototype.onGutterMouseUp = function(event)
{
	event = event || window.event;

	BX.unbind(document, "mouseup", BX.proxy(this.onGutterMouseUp, this));
	BX.unbind(document, "mousemove", BX.proxy(this.onGutterMouseMove, this));

	this.allowRowHover = true;

	document.onmousedown = null;
	document.body.onselectstart = null;
	document.body.ondragstart = null;
	document.body.style.MozUserSelect = "";
	document.body.style.cursor = "default";

	BX.onCustomEvent(this, "onGutterResize", [this.gutterOffset]);
};

BX.GanttChart.prototype.onGutterMouseMove = function(event)
{
	event = event || window.event;

	this.setGutterOffset(this.gutterOffset + (event.clientX - this.gutterClientX));
	this.adjustChartContainerPadding();
	this.gutterClientX = event.clientX;
};

BX.GanttChart.prototype.onTimelineMouseDown = function(event)
{
	event = event || window.event;
	if (!BX.GanttChart.isLeftClick(event))
		return;

	//c onsole.log("onTimelineMouseDown");
	this.dragClientX = event.clientX;

	BX.TaskQuickInfo.hide();

	BX.GanttChart.startDrag(document.body, {
		mouseup : BX.proxy(this.onTimelineMouseUp, this),
		mousemove : BX.proxy(this.onTimelineMouseMove, this)
	});

	BX.PreventDefault(event);
};

BX.GanttChart.prototype.onTimelineMouseUp = function(event)
{
	event = event || window.event;
	//c onsole.log("onTimelineMouseUp");
	BX.GanttChart.stopDrag(document.body, {
		mouseup : BX.proxy(this.onTimelineMouseUp, this),
		mousemove : BX.proxy(this.onTimelineMouseMove, this)
	});

	this.dragClientX = 0;
};

BX.GanttChart.prototype.onTimelineMouseMove = function(event)
{
	event = event || window.event;
	//c onsole.log("onTimelineMouseMove");
	var scrollLeft = this.layout.timeline.scrollLeft + (this.dragClientX - event.clientX);
	this.layout.timeline.scrollLeft = scrollLeft < 0 ? 0 : scrollLeft;

	this.dragClientX = event.clientX;
};

BX.GanttChart.prototype.onWindowResize = function(event)
{
	if (this.layout.root != null)
	{
		var contWidth = this.chartContainer.width;
		this.adjustChartContainer();
		if (contWidth != this.chartContainer.width)
			this.layout.root.style.width = this.chartContainer.width + "px";

	/*  if (this.chartContainer.width < contWidth)
		{
			var offset = this.gutterOffset - (contWidth - this.chartContainer.width);
			this.layout.list.style.width = offset + "px";
		}*/
	}
};

/*========Static Methods=====*/
BX.GanttChart.getDaysInPeriod = function(startDate, endDate)
{
	return (endDate - startDate) / (1000 * 60 * 60 * 24);
};

BX.GanttChart.isDate = function(date)
{
	return date && Object.prototype.toString.call(date) == "[object Date]";
};

BX.GanttChart.getDaysInMonth = function(month, year)
{
	var daysInMonth = [31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
	if (month != 1 || (year % 4 == 0 && year % 100 != 0 || year % 400 == 0))
		return daysInMonth[month];
	else
		return 28;
};

BX.GanttChart.convertDateToUTC = function(date)
{
	if (!date)
		return null;
	return new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate(), date.getHours(), date.getMinutes(), date.getSeconds(), 0));
};

BX.GanttChart.convertDateFromUTC = function(date)
{
	if (!date)
		return null;
	return new Date(date.getUTCFullYear(), date.getUTCMonth(), date.getUTCDate(), date.getUTCHours(), date.getUTCMinutes(), date.getUTCSeconds(), 0);
};

BX.GanttChart.getMonthName = function(month)
{
	var months = [
		BX.message("TASKS_GANTT_MONTH_JAN"),
		BX.message("TASKS_GANTT_MONTH_FEB"),
		BX.message("TASKS_GANTT_MONTH_MAR"),
		BX.message("TASKS_GANTT_MONTH_APR"),
		BX.message("TASKS_GANTT_MONTH_MAY"),
		BX.message("TASKS_GANTT_MONTH_JUN"),
		BX.message("TASKS_GANTT_MONTH_JUL"),
		BX.message("TASKS_GANTT_MONTH_AUG"),
		BX.message("TASKS_GANTT_MONTH_SEP"),
		BX.message("TASKS_GANTT_MONTH_OCT"),
		BX.message("TASKS_GANTT_MONTH_NOV"),
		BX.message("TASKS_GANTT_MONTH_DEC")
	];
	return months[month];
};

BX.GanttChart.getMsByDays = function(days)
{
	return days * 1000 * 60 * 60 * 24;
};

BX.GanttChart.getMsByHours = function(hours)
{
	return hours * 1000 * 60 * 60;
};

BX.GanttChart.allowSelection = function(domElement)
{
	if (!BX.type.isDomNode(domElement))
		return;

	domElement.onselectstart = null;
	domElement.ondragstart = null;
	domElement.style.MozUserSelect = "";
};

BX.GanttChart.isLeftClick = function(event)
{
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

BX.GanttChart.denySelection = function(domElement)
{
	if (!BX.type.isDomNode(domElement))
		return;

	domElement.onselectstart = BX.False;
	domElement.ondragstart = BX.False;
	domElement.style.MozUserSelect = "none";
};

BX.GanttChart.startDrag = function(domElement, events, cursor)
{
	if (!domElement)
		return;

	if (events)
	{
		for (var eventId in events)
			BX.bind(document, eventId, events[eventId]);
	}

	BX.GanttChart.denySelection(domElement);
	domElement.style.cursor = BX.type.isString(cursor) ? cursor : this.isIE && this.ieVersion < 9 ? "e-resize" : "ew-resize";
};

BX.GanttChart.stopDrag = function(domElement, events, cursor)
{
	if (!domElement)
		return;

	if (events)
	{
		for (var eventId in events)
			BX.unbind(document, eventId, events[eventId]);
	}

	BX.GanttChart.allowSelection(domElement);

	domElement.style.cursor = BX.type.isString(cursor) ? cursor : "default";
};

/*==================================GanttProject==================================*/
var GanttProject = function(chart, id, name)
{
	this.chart = chart;
	this.id = id;
	this.name = name;
	this.tasks = [];
	this.menuItems = [];
	this.sort = 0;
	this.layout = {
		item : null,
		row : null
	};

	this.opened = true;
};

GanttProject.prototype.addTask = function(ganttTask)
{
	if (!ganttTask || typeof(ganttTask) != "object" || !(ganttTask instanceof GanttTask))
		return false;

	if (ganttTask.project != null && ganttTask.project != this)
		ganttTask.project.removeTask(ganttTask);

	ganttTask.project = this;
	ganttTask.projectId = this.id;

	if (ganttTask.parentTask == null)
	{
		ganttTask.depthLevel = 1;
		this.tasks.push(ganttTask);
	}

	return true;
};

GanttProject.prototype.removeTask = function(ganttTask)
{
	for (var i = 0; i < this.tasks.length; i++)
	{
		if (this.tasks[i] == ganttTask)
		{
			this.tasks = BX.util.deleteFromArray(this.tasks, i);
			break;
		}
	}
};

GanttProject.prototype.collapse = function()
{
	this.opened = false;
	BX.addClass(this.layout.item, "task-gantt-item-closed");
	for (var i = 0; i < this.tasks.length; i++)
	{
		this.tasks[i].hide();
		this.tasks[i].collapse(true);
	}

	BX.onCustomEvent(this.chart, "onProjectOpen", [this]);
};

GanttProject.prototype.expand = function()
{
	this.opened = true;
	BX.removeClass(this.layout.item, "task-gantt-item-closed");
	for (var i = 0; i < this.tasks.length; i++)
	{
		this.tasks[i].show();

		if (this.tasks[i].opened)
			this.tasks[i].expand();
	}

	BX.onCustomEvent(this.chart, "onProjectOpen", [this]);
};

GanttProject.prototype.createBars = function()
{
	this.layout.row = BX.create("div", {
		props : { className : "task-gantt-timeline-row", id : "task-gantt-timeline-row-p" + this.id },
		events : {
			mouseover : BX.proxy(this.onRowMouseOver, this),
			mouseout : BX.proxy(this.onRowMouseOut, this),
			contextmenu : BX.proxy(this.onRowContextMenu, this)
		}
	});
	return this.layout.row;
};

GanttProject.prototype.createItem = function()
{
	var itemClass = "task-gantt-item task-gantt-item-project";
	if (!this.opened)
		itemClass += " task-gantt-item-closed";

	this.layout.item = BX.create("div", {
		props : { className : itemClass, id : "task-gantt-item-p" + this.id },
		events : {
			mouseover : BX.proxy(this.onItemMouseOver, this),
			mouseout : BX.proxy(this.onItemMouseOut, this)
		},
		children : [
			BX.create("span", { props : { className : "task-gantt-item-folding"}, events : {
				click : BX.proxy(this.onFoldingClick, this)
			}}),

			BX.create("span", { props : { className: "task-gantt-item-name" }, text : this.name, events : {
				click : BX.proxy(this.onFoldingClick, this)
			}}),

			(this.layout.menu = this.menuItems.length > 0
				? BX.create("span", { props : { className: "task-gantt-item-menu"}, events : {
					click : BX.proxy(this.onItemMenuClick, this)
				}})
				: null
			)
		]
	});

	return this.layout.item;
};

GanttProject.prototype.onFoldingClick = function(event)
{
	if (this.opened)
		this.collapse();
	else
		this.expand();
};

GanttProject.prototype.onItemMenuClick = function(event)
{
	BX.TaskMenuPopup.show("p" + this.id, this.layout.menu, this.menuItems, {
		offsetLeft : 8,
		bindOptions : { forceBindPosition : true },
		events : { onPopupClose: BX.proxy(this.onItemMenuClose, this) },
		chart : this.chart,
		project : this
	});

	this.denyItemsHover();
	BX.addClass(this.layout.menu, "task-gantt-item-menu-selected");
};

GanttProject.prototype.onItemMenuClose = function(popupWindow, event)
{
	this.allowItemsHover(event);
	BX.removeClass(this.layout.menu, "task-gantt-item-menu-selected");
};

GanttProject.prototype.onItemMouseOver = function(event)
{
	if (!this.chart.allowRowHover)
		return;
	BX.addClass(this.layout.item, "task-gantt-item-menu-hover");
	BX.addClass(this.layout.row, "task-gantt-timeline-row-hover");
};

GanttProject.prototype.onItemMouseOut = function(event)
{
	if (!this.chart.allowRowHover)
		return;
	BX.removeClass(this.layout.item, "task-gantt-item-menu-hover");
	BX.removeClass(this.layout.row, "task-gantt-timeline-row-hover");
};

GanttProject.prototype.onRowMouseOver = function(event)
{
	if (!this.chart.allowRowHover)
		return;
	BX.addClass(this.layout.row, "task-gantt-timeline-row-hover");
};

GanttProject.prototype.onRowMouseOut = function(event)
{
	if (!this.chart.allowRowHover)
		return;

	BX.removeClass(this.layout.row, "task-gantt-timeline-row-hover");
};

GanttProject.prototype.onRowContextMenu = function(event)
{
	event = event || window.event;
	if (this.menuItems.length < 1 || event.ctrlKey)
		return;

	BX.TaskQuickInfo.hide();

	BX.TaskMenuPopup.show("p" + this.id, event, this.menuItems, {
   		offsetLeft : 1,
		autoHide : true,
		closeByEsc : true,
   		bindOptions : { forceBindPosition : true },
   		events : { onPopupClose: BX.proxy(this.onItemMenuClose, this) },
   		chart : this.chart,
   		project : this
   	});

	var target = event.target || event.srcElement;
	if (target && BX.hasClass(target, "task-gantt-timeline-row") && BX.type.isNotEmptyString(target.id))
	{
		var project = this.chart.getProjectById(target.id.substr("task-gantt-timeline-row-p".length));
		if (project != null)
		   	BX.addClass(project.layout.row, "task-gantt-timeline-row-hover");
	}

   	this.denyItemsHover();
	BX.PreventDefault(event);
};

GanttProject.prototype.denyItemsHover = function()
{
	this.chart.allowRowHover = false;
};

GanttProject.prototype.allowItemsHover = function(event)
{
	this.chart.allowRowHover = true;

	event = event || window.event || null;
	if (!event)
		return;

	var target = event.target || event.srcElement;
	if ( target != this.layout.row && target.parentNode !=  this.layout.row && target.parentNode.parentNode !=  this.layout.row &&
		 target != this.layout.item && target.parentNode !=  this.layout.item
	)
	{
		BX.removeClass(this.layout.item, "task-gantt-item-menu-hover");
		BX.removeClass(this.layout.row, "task-gantt-timeline-row-hover");
	}
	else if (target !=  this.layout.item && target.parentNode !=  this.layout.item)
		BX.removeClass(this.layout.item, "task-gantt-item-menu-hover");

};

/*==================================GanttTask==================================*/
var GanttTask = function(chart, id, name, status, dateCreated)
{
	this.chart = chart;
	this.id = id;
	this.name = name;
	this.setStatus(status);

	this.dateCreated = BX.GanttChart.isDate(dateCreated) ? BX.GanttChart.convertDateToUTC(dateCreated) : this.chart.currentDatetime;

	this.dateStart = null;
	this.dateStartMinutes = 0; //Real Date Start minutes before zeroing
	this.isRealDateStart = false;

	this.dateEnd = null;
	this.dateEndMinutes = 0; //Real Date End minutes before zeroing
	this.isRealDateEnd = false;

	this.__setDateStart(this.dateCreated, false, true);

	this.dateStarted = null;
	this.dateCompleted = null;
	this.dateDeadline = null;

	this.files = [];
	this.responsible = "";
	this.responsibleId = 0;
	this.director = "";
	this.directorId = 0;
	this.priority = 1;

	this.depthLevel = 1;

	this.parentTask = null;
	this.parentTaskId = 0;
	this.childTasks = [];
	this.hasChildren = false;

	this.projectId = 0;
	this.project = null;

	this.predecessors = [];
	this.menuItems = [];

	this.url = "";
	this.details = null;

	this.layout = {
		item : null,
		row : null,
		name : null,
		menu : null,
		planBar : null,
		realBar : null,
		deadlineBar : null,
		deadlineSlider : null,
		deadlineOverdue : null,
		realOverdue : null,
		completeFlag : null
	};

	this.opened = false;
	this.status = null;

	this.canEditDealine = true;
	this.canEditPlanDates = true;

	this.resizerOffset = 0;
	this.resizerChangePos = false;
	this.resizerPageX = 0;

	this.autoResizeIntID = null;
	this.autoResizeTimeout = 50;
	this.autoResizeCallback = null;
	this.autoResizeEvent = null;
};

GanttTask.prototype.addChild = function(ganttTask)
{
	if (!ganttTask || typeof(ganttTask) != "object" || !(ganttTask instanceof GanttTask) || ganttTask == this || ganttTask.parentTask == this)
		return false;

	var parentTask = this.parentTask;
	while (parentTask != null)
	{
		if (parentTask == ganttTask)
			return false;
		parentTask = parentTask.parentTask;
	}

	if (ganttTask.parentTask != null && ganttTask.parentTask != this)
		ganttTask.parentTask.removeChild(ganttTask);
	if (ganttTask.parentTask == null && ganttTask.project != null)
		ganttTask.project.removeTask(ganttTask);

	ganttTask.parentTask = this;
	ganttTask.parentTaskId = this.id;
	ganttTask.depthLevel = this.depthLevel + 1;
	ganttTask.setProject(this.projectId);

	this.childTasks.push(ganttTask);
	this.hasChildren = true;

	return true;
};

GanttTask.prototype.addChildren = function(arGanttTask)
{
	for (var i = 0; i < arGanttTask.length; i++)
		this.addChild(arGanttTask[i]);
};

GanttTask.prototype.removeChild = function(childTask)
{
	for (var i = 0; i < this.childTasks.length; i++)
	{
		if (this.childTasks[i] == childTask)
		{
			this.childTasks = BX.util.deleteFromArray(this.childTasks, i);
			childTask.depthLevel = 1;
			childTask.parentTask = null;
			childTask.parentTaskId = 0;
			break;
		}
	}

	this.hasChildren = this.childTasks.length > 0;
};

GanttTask.prototype.getNextTask = function()
{
	for (var i = 0; i < this.project.tasks.length; i++)
		if (this.project.tasks[i] == this)
			return this.project.tasks[i+1] ? this.project.tasks[i+1] : null;
};

GanttTask.prototype.getPreviousTask = function()
{
	for (var i = 0; i < this.project.tasks.length; i++)
		if (this.project.tasks[i] == this)
			return i > 0 && this.project.tasks[i-1] ? this.project.tasks[i-1] : null;
};

GanttTask.prototype.setTaskFromJSON = function(taskJSON)
{
	if (typeof(taskJSON) != "object")
		return null;

	this.setStatus(taskJSON.status);
	this.setDateCompleted(taskJSON.dateCompleted);
	this.setDateDeadline(taskJSON.dateDeadline);
	this.setDateStarted(taskJSON.dateStarted);

	if (BX.GanttChart.isDate(taskJSON.dateEnd) && taskJSON.dateEnd > this.dateStart)
	{
		this.setDateEnd(taskJSON.dateEnd);
		this.setDateStart(taskJSON.dateStart);
	}
	else
	{
		this.setDateStart(taskJSON.dateStart);
		this.setDateEnd(taskJSON.dateEnd);
	}

	this.setMenuItems(taskJSON.menuItems);
	this.setName(taskJSON.name);
	this.setUrl(taskJSON.url);
	this.setDetails(taskJSON.details);
	this.setFiles(taskJSON.files);

	if (typeof(taskJSON.opened) != "undefined")
		this.opened = !!taskJSON.opened;

	if (typeof(taskJSON.hasChildren) != "undefined")
		this.hasChildren = !!taskJSON.hasChildren;

	if (typeof(taskJSON.canEditDealine) != "undefined")
		this.canEditDealine = !!taskJSON.canEditDealine;

	if (typeof(taskJSON.canEditPlanDates) != "undefined")
		this.canEditPlanDates = !!taskJSON.canEditPlanDates;

	if (taskJSON.responsible)
		this.responsible = taskJSON.responsible;
	if (taskJSON.responsibleId)
		this.responsibleId = taskJSON.responsibleId;

	if (taskJSON.director)
		this.director = taskJSON.director;
	if (taskJSON.directorId)
		this.directorId = taskJSON.directorId;

	if (BX.type.isNumber(taskJSON.priority))
		this.priority = taskJSON.priority;

	if (taskJSON.parentTask != null)
	{
		taskJSON.parentTask.addChild(this);
		taskJSON.parentTask = null;
	}
	else if (taskJSON.parentTaskId)
	{
		var parentTask = this.chart.getTaskById(taskJSON.parentTaskId);
		if (parentTask)
			parentTask.addChild(this);
	}

	return this;
};

GanttTask.prototype.setDateStart = function(date)
{
	if (date === null)
	{
		this.isRealDateStart = false;
		return this.__setDateStart(this.dateCreated, false, true);
	}

	return this.__setDateStart(date, true, false);
};

GanttTask.prototype.__setDateStart = function(date, isRealStartDate, isUTC)
{
	if (!BX.GanttChart.isDate(date))
		return;

	isUTC = !!isUTC;
	if (!isUTC)
		date = BX.GanttChart.convertDateToUTC(date);

	if (this.isRealDateEnd && date > this.dateEnd)
		return;

	this.isRealDateStart = isRealStartDate;
	this.dateStart = new Date(date.getTime());
	if (this.isRealDateStart)
		this.dateStartMinutes = this.dateStart.getUTCMinutes();
	this.dateStart.setUTCMinutes(0, 0, 0);

	if (!this.isRealDateEnd)
	{
		if (this.status == "completed")
		{
			if (this.dateCompleted == null || this.dateCompleted <= this.dateStart || (this.dateCompleted - this.dateStart) < BX.GanttChart.getMsByHours(12))
			{
				this.dateEnd = new Date(this.dateStart.getTime());
				this.dateEnd.setUTCHours(this.dateStart.getUTCHours() + 12);
			}
			else
				this.dateEnd = this.dateCompleted;
		}
		else
		{
			this.dateEnd = new Date(this.chart.currentDatetime.getTime());
			this.dateEnd.setUTCHours(0, 0, 0, 0);
			this.dateEnd.setUTCDate(this.dateEnd.getUTCDate()+1);
			if (this.dateEnd <= this.dateStart || (this.dateEnd - this.dateStart) < BX.GanttChart.getMsByHours(12))
			{
				this.dateEnd = new Date(this.dateStart.getTime());
				this.dateEnd.setUTCHours(this.dateStart.getUTCHours() + 12);
			}
		}
	}
};

GanttTask.prototype.setDateEnd = function(date)
{
	if (date === null)
	{
		this.dateEnd = null;
		this.isRealDateEnd = false;
		this.__setDateStart(this.dateStart, this.isRealDateStart, true);
	}
	else if (BX.GanttChart.isDate(date))
	{
		date = BX.GanttChart.convertDateToUTC(date);
		if (date > this.dateStart)
		{
			this.dateEnd = new Date(date.getTime());
			this.isRealDateEnd = true;
			if (this.dateEnd.getUTCMinutes() != 0 || this.dateEnd.getUTCSeconds() != 0)
			{
				this.dateEndMinutes = this.dateEnd.getUTCMinutes();
				this.dateEnd.setUTCMinutes(0, 0, 0);
				this.dateEnd.setUTCHours(this.dateEnd.getUTCHours() + 1);
			}
		}
	}
};

GanttTask.prototype.setDateCompleted = function(date)
{
	if (date === null)
		this.dateCompleted = null;
	else if (BX.GanttChart.isDate(date))
	{
		this.dateCompleted = BX.GanttChart.convertDateToUTC(date);
		if (this.status == "completed")
		{
			if (!this.isRealDateEnd && this.dateCompleted <= this.dateStart)
			{
				this.dateEnd = new Date(this.dateStart.getTime());
				this.dateEnd.setUTCHours(this.dateStart.getUTCHours() + 12);
			}
			else
				this.dateEnd = this.dateCompleted;
		}
	}
};

GanttTask.prototype.setDateStarted = function(date)
{
	if (date === null)
		this.dateStarted = null;
	else if (BX.GanttChart.isDate(date))
		this.dateStarted = BX.GanttChart.convertDateToUTC(date);
};

GanttTask.prototype.setDateDeadline = function(date)
{
	if (date === null)
		this.dateDeadline = null;
	else if (BX.GanttChart.isDate(date))
		this.dateDeadline = BX.GanttChart.convertDateToUTC(date);
};

GanttTask.prototype.setName = function(name)
{
	if (BX.type.isNotEmptyString(name))
		this.name = name;
};

GanttTask.prototype.setUrl = function(url)
{
	if (BX.type.isNotEmptyString(url))
		this.url = url;
};

GanttTask.prototype.setFiles = function(files)
{
	if (!BX.type.isArray(files))
		return;

	this.files = [];
	for (var i = 0; i < files.length; i++)
	{
		var file = files[i];
		if (typeof(file) == "object" && BX.type.isNotEmptyString(file.name))
		{
			this.files.push({
				name : file.name,
				url : file.url ? file.url : "",
				size : file.size ? file.size : ""
			});
		}
	}
};

GanttTask.prototype.setDetails = function(callback)
{
	if (BX.type.isFunction(callback))
		this.details = callback;
};

GanttTask.prototype.setMenuItems = function(menuItems)
{
	if (BX.type.isArray(menuItems))
		this.menuItems = menuItems;
};

GanttTask.prototype.setStatus = function(status)
{
	if (!BX.type.isNotEmptyString(status))
		return;

	this.status = status;

	if (this.status == "completed")
	{
		this.canEditDealine = false;
		this.canEditPlanDates = false;
	}
};

GanttTask.prototype.allowEditDeadline = function()
{
	if (this.status != "completed")
		this.canEditDealine = true;

	return this.canEditDealine;
};

GanttTask.prototype.denyEditDeadline = function()
{
	this.canEditDealine = false;
};

GanttTask.prototype.allowEditPlanDates = function()
{
	if (this.status != "completed")
		this.canEditPlanDates = true;

	return this.canEditPlanDates;
};

GanttTask.prototype.denyEditPlanDates = function()
{
	this.canEditPlanDates = false;
};

GanttTask.prototype.setProject = function(projectId)
{
	var oldProject = this.project != null ? this.project : null;

	if (this.parentTask != null)
	{
		this.projectId = this.parentTask.projectId;
		this.project = this.parentTask.project;
	}
	else
	{
		var project = this.chart.getProjectById(BX.type.isNumber(projectId) ? projectId : 0) ;
		if (!project)
			project = this.chart.getDefaultProject();

		this.projectId = project.id;
		this.project = project;
	}

	if (oldProject != null && oldProject != this.project)
		oldProject.removeTask(this);

	this.project.addTask(this);
};

GanttTask.prototype.createItem = function()
{
	if (this.layout.item != null)
		return this.layout.item;

	this.layout.item = BX.create("div", {
		props : { id : "task-gantt-item-" + this.id },
		events : {
			mouseover : BX.proxy(this.onItemMouseOver, this),
			mouseout: BX.proxy(this.onItemMouseOut, this)
		},
		children : [
			BX.create("span", { props : { className : "task-gantt-item-folding"}, events : {
				click : BX.proxy(this.onFoldingClick, this)
			}}),

			(this.layout.name = BX.create("a", {
				props : { className: "task-gantt-item-name", href : ""}, events: {
				click : BX.proxy(this.onItemNameClick, this)
			}})),

			(this.layout.menu = BX.create("span", { props : { className: "task-gantt-item-menu"}, events : {
				click : BX.proxy(this.onItemMenuClick, this)
			}}))
		]
	});

	this.updateItem();

	return this.layout.item;
};

GanttTask.prototype.updateItem = function()
{
	if (this.layout.item == null)
		return null;

	this.layout.name.innerHTML = this.name;
	this.layout.name.href = this.url;

	var itemClass = "task-gantt-item task-gantt-item-depth-" + (this.projectId == 0 ? this.depthLevel-1 : this.depthLevel);

	var isTreeMode = true;
	if (ganttChart && ganttChart.hasOwnProperty('treeMode'))
		isTreeMode = ganttChart.treeMode;

	if (
		isTreeMode
		&& (this.childTasks.length > 0 || this.hasChildren)
	)
	{
		itemClass += " task-gantt-item-has-children";
	}

	if (this.isHidden())
		itemClass += " task-gantt-item-hidden";

	if (this.opened)
		itemClass += " task-gantt-item-opened";

	if (this.status)
		itemClass += " task-gantt-item-status-" + this.status;

	if (this.projectId == 0)
		itemClass += " task-gantt-item-empty-project";

	this.layout.item.className = itemClass;

	if (this.menuItems.length < 1)
		BX.addClass(this.layout.menu, "task-gantt-item-menu-empty");
	else
		BX.removeClass(this.layout.menu, "task-gantt-item-menu-empty");

	return this.layout.item;
};

GanttTask.prototype.createBars = function()
{
	if (this.layout.row != null)
		return this.layout.row;

	this.createRow();
	this.createPlanBars();
	this.createRealBar();
	this.createCompleteFlag();
	this.createDeadlineBars();

	return this.layout.row;
};

GanttTask.prototype.updateBars = function()
{
	if (this.layout.row == null)
		return null;

	this.updateRow();
	this.updatePlanBars();
	this.updateRealBar();
	this.updateCompleteFlag();
	this.updateDeadlineBars();

	return this.layout.row;
};

GanttTask.prototype.redraw = function()
{
	if (!this.layout.item || !this.layout.row)
		return;

	this.chart.autoExpandTimeline([this.getMinDate(), this.getMaxDate()]);
	this.updateItem();
	this.updateBars();
};

GanttTask.prototype.createRow = function()
{
	if (this.layout.row != null)
		return;

	this.layout.row = BX.create("div", {
		props : { id : "task-gantt-timeline-row-" + this.id },
		events : {
			mouseover : BX.proxy(this.onRowMouseOver, this),
			mouseout : BX.proxy(this.onRowMouseOut, this),
			dblclick : BX.proxy(this.onRowDoubleClick, this),
			contextmenu : BX.proxy(this.onRowContextMenu, this)
		}
	});

	this.updateRow();
};

GanttTask.prototype.updateRow = function()
{
	if (this.layout.row == null)
		return;

	var rowClass = "task-gantt-timeline-row";
	if (this.isHidden())
		rowClass += " task-gantt-item-hidden";
	if (this.status)
		rowClass += " task-gantt-item-status-" + this.status;
	this.layout.row.className = rowClass;
};

GanttTask.prototype.createPlanBars = function()
{
	if (this.layout.row == null)
		return;

	this.layout.planBar = BX.create("div", {
		   events : { mousedown :  BX.proxy(this.onPlanBarMouseDown, this) },
		   children : [
			   (this.layout.planBarStart = BX.create("div", { props : { className: "task-gantt-bar-plan-start" }, style : { zIndex : 0}, events : {
				   mousedown :  BX.proxy(this.onStartDateMouseDown, this)
			   }})),
			   (this.layout.planBarEnd = BX.create("div", { props : { className: "task-gantt-bar-plan-end" }, style : { zIndex : 0}, events : {
				   mousedown :  BX.proxy(this.onEndDateMouseDown, this)
			   }}))
		   ]
	   });

	this.layout.row.appendChild(this.layout.planBar);
	this.updatePlanBars();
};

GanttTask.prototype.updatePlanBars = function()
{
	if (this.layout.row == null)
		return;

	var isEndless = (!this.isRealDateEnd && this.status != "completed") ||
					(this.status == "completed" && this.dateCompleted != null && this.dateCompleted <= this.dateStart);
	this.layout.planBar.className = "task-gantt-bar-plan" +
					   (isEndless ? " task-gantt-bar-plan-endless" : "") +
					   (!this.canEditPlanDates ? " task-gantt-bar-plan-read-only" : "");
	this.resizePlanBar(this.dateStart, this.dateEnd);

};

GanttTask.prototype.createRealBar = function()
{
	if (this.layout.row == null || this.dateStarted == null)
		return;

	this.layout.realBar = BX.create("div", { props : { className : "task-gantt-bar-real" } });
	this.layout.row.appendChild(this.layout.realBar);

	this.updateRealBar();
};

GanttTask.prototype.updateRealBar = function()
{
	if (this.layout.row == null)
		return;

	if (this.dateStarted != null)
   	{
		if (this.layout.realBar == null)
			this.createRealBar();
		else
		{
			this.layout.realBar.style.left = this.chart.getPixelsInPeriod(this.chart.minDate, this.dateStarted);
			var dateRealBarEnd = this.dateCompleted != null ? this.dateCompleted : this.chart.currentDatetime;
			this.layout.realBar.style.width = dateRealBarEnd > this.dateStarted ?
											  this.chart.getPixelsInPeriod(this.dateStarted, dateRealBarEnd) :
											  "0px";
		}
	}
	else
	{
		if (this.layout.realBar != null)
			this.layout.row.removeChild(this.layout.realBar);

		this.layout.realBar = null;
	}
};

GanttTask.prototype.createCompleteFlag = function()
{
	if (this.layout.row == null || this.dateCompleted == null)
		return;

	this.layout.completeFlag = BX.create("div", { props : { className : "task-gantt-complete-flag" }});
	this.layout.row.appendChild(this.layout.completeFlag);

	this.updateCompleteFlag();
};

GanttTask.prototype.updateCompleteFlag = function()
{
	if (this.layout.row == null)
		return;

	if (this.dateCompleted != null)
	{
		if (this.layout.completeFlag == null)
			this.createCompleteFlag();
		else
			this.layout.completeFlag.style.left = this.chart.getPixelsInPeriod(this.chart.minDate, this.dateCompleted, true) - 8 + "px";
	}
	else
	{
		if (this.layout.completeFlag != null)
			this.layout.row.removeChild(this.layout.completeFlag);
		this.layout.completeFlag = null;
	}
};

GanttTask.prototype.createDeadlineBars = function()
{
	if (this.layout.row == null || this.dateDeadline == null)
		return;

	this.layout.deadlineSlider = BX.create("div", {
		props : { className: "task-gantt-deadline-slider" },
		events : { mousedown :  BX.proxy(this.onDeadlineMouseDown, this) }
	});

	this.layout.row.appendChild(this.layout.deadlineSlider);

	this.layout.deadlineBar = BX.create("div", { props : { className : "task-gantt-bar-deadline" }});
	this.layout.row.appendChild(this.layout.deadlineBar);

	this.layout.deadlineOverdue = BX.create("div", { props : { className: "task-gantt-bar-deadline-overdue" }});
	this.layout.row.appendChild(this.layout.deadlineOverdue);

	this.layout.realOverdue = BX.create("div", { props : { className : "task-gantt-bar-real-overdue" }});
	this.layout.row.appendChild(this.layout.realOverdue);

	this.updateDeadlineBars();
};

GanttTask.prototype.updateDeadlineBars = function()
{
	if (this.layout.row == null)
		return;

	if (this.dateDeadline != null)
	{
		if (this.layout.deadlineSlider == null)
			this.createDeadlineBars();
		else
		{
			if (this.canEditDealine)
				BX.removeClass(this.layout.deadlineSlider, "task-gantt-deadline-read-only");
			else
				BX.addClass(this.layout.deadlineSlider, "task-gantt-deadline-read-only");

			this.layout.deadlineSlider.style.left = this.chart.getPixelsInPeriod(this.chart.minDate, this.dateDeadline, true) - 3 + "px";
			this.resizeDeadlineBar(this.dateDeadline);
			this.resizeDeadlineOverdueBar(this.dateDeadline);
			this.resizeRealOverdueBar(this.dateDeadline);
		}
	}
	else
	{
		if (this.layout.deadlineSlider != null)
		{
			this.layout.row.removeChild(this.layout.deadlineBar);
			this.layout.row.removeChild(this.layout.deadlineSlider);
			this.layout.row.removeChild(this.layout.deadlineOverdue);
			this.layout.row.removeChild(this.layout.realOverdue);
		}

		this.layout.deadlineBar = null;
		this.layout.deadlineSlider = null;
		this.layout.deadlineOverdue = null;
		this.layout.realOverdue = null;
	}
};

GanttTask.prototype.offsetBars = function(offset)
{
	this.resizerOffset += offset;

	if (this.layout.planBar)
		this.layout.planBar.style.left = (parseInt(this.layout.planBar.style.left) || 0) + offset + "px";

	if (this.layout.realBar)
		this.layout.realBar.style.left = (parseInt(this.layout.realBar.style.left) || 0) + offset + "px";

	if (this.layout.completeFlag)
		this.layout.completeFlag.style.left = (parseInt(this.layout.completeFlag.style.left) || 0) + offset + "px";

	if (this.layout.deadlineSlider)
	{
		this.layout.deadlineSlider.style.left = (parseInt(this.layout.deadlineSlider.style.left) || 0) + offset + "px";
		this.layout.deadlineBar.style.left = (parseInt(this.layout.deadlineBar.style.left) || 0) + offset + "px";
		this.layout.deadlineOverdue.style.left = (parseInt(this.layout.deadlineOverdue.style.left) || 0) + offset + "px";
		this.layout.realOverdue.style.left = (parseInt(this.layout.realOverdue.style.left) || 0) + offset + "px";
	}
};

GanttTask.prototype.onFoldingClick = function(event)
{
	if (this.opened)
		this.collapse();
	else
		this.expand();
};

GanttTask.prototype.onItemNameClick = function(event)
{
	event = event || window.event;

	if (!BX.GanttChart.isLeftClick(event))
		return;

	if (BX.type.isFunction(this.details))
		this.details({ event : event });

	BX.PreventDefault(event);
};

GanttTask.prototype.onItemMenuClick = function(event)
{
	BX.TaskMenuPopup.show(this.id, this.layout.menu, this.menuItems, {
		offsetLeft : 8,
		bindOptions : { forceBindPosition : true },
		events : { onPopupClose: BX.proxy(this.onItemMenuClose, this) },
		chart : this.chart,
		task : this
	});

	this.denyItemsHover();
	BX.addClass(this.layout.menu, "task-gantt-item-menu-selected");
};

GanttTask.prototype.onItemMenuClose = function(popupWindow, event)
{
	this.allowItemsHover(event);
	BX.removeClass(this.layout.menu, "task-gantt-item-menu-selected");
};

GanttTask.prototype.onItemMouseOver = function(event)
{
	if (!this.chart.allowRowHover)
		return;

	event = event || window.event;
	if (this.isShowQuickInfo(event))
	{
		BX.fixEventPageX(event);
		var top = this.layout.item.offsetTop + this.chart.chartContainer.pos.top + 10;
		var left = event.pageX;
		var bottom = top + 17;

		BX.TaskQuickInfo.show(
			{
				left: left,
				top: top,
				bottom: bottom
			},
			this.getQuickInfoData(),
			{
				dateFormat : this.chart.dateFormat,
				dateInUTC : true,
				onDetailClick: BX.proxy(this.onQuickInfoDetails, this),
				userProfileUrl : this.chart.userProfileUrl
			}
		);
	}

	BX.addClass(this.layout.item, "task-gantt-item-hover task-gantt-item-menu-hover");
	BX.addClass(this.layout.row, "task-gantt-timeline-row-hover");
};

GanttTask.prototype.onItemMouseOut = function(event)
{
	if (!this.chart.allowRowHover)
		return;

	if (this.isShowQuickInfo(event))
		BX.TaskQuickInfo.hide();

	BX.removeClass(this.layout.item, "task-gantt-item-hover task-gantt-item-menu-hover");
	BX.removeClass(this.layout.row, "task-gantt-timeline-row-hover");
};

GanttTask.prototype.onRowMouseOver = function(event)
{
	if (!this.chart.allowRowHover)
		return;

	event = event || window.event;
	if (this.isShowQuickInfo(event))
	{
		BX.fixEventPageX(event);
		var top = this.layout.row.offsetTop + this.chart.chartContainer.pos.top + 58;
		var left = event.pageX;
		var bottom = top + 27;

		BX.TaskQuickInfo.show(
			{
				left: left,
				top: top,
				bottom: bottom
			},
			this.getQuickInfoData(),
			{
				dateFormat : this.chart.dateFormat,
				dateInUTC : true,
				onDetailClick: BX.proxy(this.onQuickInfoDetails, this),
				userProfileUrl : this.chart.userProfileUrl
			}
		);
	}

	BX.addClass(this.layout.item, "task-gantt-item-hover");
	BX.addClass(this.layout.row, "task-gantt-timeline-row-hover");
};

GanttTask.prototype.onRowMouseOut = function(event)
{
	if (!this.chart.allowRowHover)
		return;

	if (this.isShowQuickInfo(event))
		BX.TaskQuickInfo.hide();

	BX.removeClass(this.layout.item, "task-gantt-item-hover task-gantt-item-menu-hover");
	BX.removeClass(this.layout.row, "task-gantt-timeline-row-hover");
};

GanttTask.prototype.onRowDoubleClick = function(event)
{
	event = event || window.event;
	if (BX.type.isFunction(this.details))
		return this.details({ event : event });
};

GanttTask.prototype.onRowContextMenu = function(event)
{
	event = event || window.event;
	if (this.menuItems.length < 1 || event.ctrlKey)
		return;

	BX.TaskQuickInfo.hide();

	BX.TaskMenuPopup.show(this.id, event, this.menuItems, {
		offsetLeft : 1,
		autoHide : true,
		closeByEsc : true,
		bindOptions : { forceBindPosition : true },
		events : { onPopupClose: BX.proxy(this.onItemMenuClose, this) },
		chart : this.chart,
		task : this
	});

	var row = null;
	var target = event.target || event.srcElement;
	if (BX.hasClass(target, "task-gantt-timeline-row"))
		row = target;
	else if (BX.hasClass(target.parentNode, "task-gantt-timeline-row"))
		row = target.parentNode;
	else if (BX.hasClass(target.parentNode.parentNode, "task-gantt-timeline-row"))
		row = target.parentNode.parentNode;

	if (row != null && BX.type.isNotEmptyString(row.id))
	{
		var task = this.chart.getTaskById(row.id.substr("task-gantt-timeline-row-".length));
		if (task != null)
		{
			BX.addClass(task.layout.item, "task-gantt-item-hover");
		   	BX.addClass(task.layout.row, "task-gantt-timeline-row-hover");
		}
	}

	this.denyItemsHover();

	BX.PreventDefault(event);
};

GanttTask.prototype.onQuickInfoDetails = function(event, popupWindow, quickInfo)
{
	popupWindow.close();
	if (BX.type.isFunction(this.details))
		return this.details({ event : event, popupWindow : popupWindow, quickInfo : quickInfo});
};

GanttTask.prototype.isShowQuickInfo = function(event)
{
	if (this.chart.dragClientX != 0 || !this.chart.allowRowHover)
		return false;

	event = event || window.event;
	var target = event.target || event.srcElement;
	return  target == this.layout.planBar ||
			target == this.layout.realBar ||
			target == this.layout.name ||
			target == this.layout.deadlineBar ||
			target == this.layout.deadlineOverdue ||
			target == this.layout.realOverdue ||
			target == this.layout.completeFlag ||
			target == this.layout.deadlineSlider;
};

GanttTask.prototype.getQuickInfoData = function()
{
	var dateStart = this.isRealDateStart ? this.dateStart : null;
	var dateEnd = this.isRealDateEnd ? this.dateEnd : null;
	if (dateStart && this.dateStartMinutes > 0)
	{
		dateStart = new Date(this.dateStart.getTime());
		dateStart.setUTCMinutes(this.dateStartMinutes, 0, 0);
	}

	if (dateEnd && this.dateEndMinutes > 0)
	{
		dateEnd = new Date(this.dateEnd.getTime());
		dateEnd.setUTCHours(dateEnd.getUTCHours()-1, this.dateEndMinutes, 0, 0);
	}

	return {
		id : this.id,
		name : this.name,

		dateCreated : this.dateCreated,
		dateStart : dateStart,
		dateEnd : dateEnd,
		dateDeadline : this.dateDeadline,
		dateCompleted : this.dateCompleted,
		dateStarted : this.dateStarted,

		files : this.files,
		priority : this.priority,
		status : this.status,
		responsible : this.responsible,
		responsibleId : this.responsibleId,
		director : this.director,
		directorId : this.directorId
	};
};

GanttTask.prototype.denyItemsHover = function()
{
	this.chart.allowRowHover = false;
};

GanttTask.prototype.allowItemsHover = function(event)
{
	this.chart.allowRowHover = true;

	event = event || window.event || null;
	if (!event)
		return;

	var target = event.target || event.srcElement;

	if ( event.keyCode == 27 ||
		(
			target != this.layout.row &&
			target.parentNode !=  this.layout.row &&
			target.parentNode.parentNode !=  this.layout.row &&
		    target != this.layout.item && target.parentNode !=  this.layout.item
		)
	)
	{
		BX.removeClass(this.layout.item, "task-gantt-item-hover task-gantt-item-menu-hover");
		BX.removeClass(this.layout.row, "task-gantt-timeline-row-hover");
	}
	else if (target !=  this.layout.item && target.parentNode !=  this.layout.item)
		BX.removeClass(this.layout.item, "task-gantt-item-menu-hover");
};

GanttTask.prototype.isHidden = function()
{
	if (this.project.opened === false)
		return true;

	var parentTask = this.parentTask;
	while (parentTask != null)
	{
		if (parentTask.opened === false)
			return true;

		parentTask = parentTask.parentTask;
	}

	return false;
};

GanttTask.prototype.show = function()
{
	BX.removeClass(this.layout.item, "task-gantt-item-hidden");
	BX.removeClass(this.layout.row, "task-gantt-item-hidden");
};

GanttTask.prototype.hide = function()
{
	BX.addClass(this.layout.item, "task-gantt-item-hidden");
	BX.addClass(this.layout.row, "task-gantt-item-hidden");
};

GanttTask.prototype.collapse = function(skip)
{
	var children = this.childTasks.length;
	if (children < 1)
		return;

	if (!skip)
	{
		BX.removeClass(this.layout.item, "task-gantt-item-opened");
		this.opened = false;
		BX.onCustomEvent(this.chart, "onTaskOpen", [this]);
	}

	for (var i = 0; i < children; i++)
	{
		this.childTasks[i].hide();
		this.childTasks[i].collapse(true);
	}

};

GanttTask.prototype.expand = function(skip)
{
	var children = this.childTasks.length;
	if (children < 1 && this.hasChildren === false)
		return;

	if (!skip)
	{
		this.opened = true;
		BX.addClass(this.layout.item, "task-gantt-item-opened");
		BX.onCustomEvent(this.chart, "onTaskOpen", [this]);
	}

	for (var i = 0; i < children; i++)
	{
		this.childTasks[i].show();
		if (this.childTasks[i].opened)
			this.childTasks[i].expand(true);
	}
};

GanttTask.prototype.getMinDate = function()
{
	var dates = [this.dateStart, this.dateEnd, this.dateCreated, this.dateStarted, this.dateCompleted, this.dateDeadline];
	for (var i = dates.length-1; i >= 0; i--)
		if (dates[i] == null)
			dates.splice(i, 1);

	return new Date(Math.min.apply(null, dates));
};

GanttTask.prototype.getMaxDate = function()
{
	var dates = [this.dateStart, this.dateEnd, this.dateCreated, this.dateStarted, this.dateCompleted, this.dateDeadline];
	for (var i = dates.length-1; i >= 0; i--)
		if (dates[i] == null)
			dates.splice(i, 1);

	return new Date(Math.max.apply(null, dates));
};

/*==================Resize Task Bars=========*/
GanttTask.prototype.resizePlanBar = function(dateStart, dateEnd)
{
	if (this.layout.planBar == null)
		return;

	var width = this.chart.getPixelsInPeriod(dateStart, dateEnd, true);
	if (this.isRealDateEnd || (!this.isRealDateEnd && this.status == "completed" && this.dateEnd == this.dateCompleted))
		width = Math.max(width, 4);
	else
		width = Math.max(width-7, 5);

	this.layout.planBar.style.left = this.chart.getPixelsInPeriod(this.chart.minDate, dateStart);
	this.layout.planBar.style.width = width + "px";
};

GanttTask.prototype.resizeDeadlineBar = function(dateDeadline)
{
	if (this.layout.deadlineBar == null)
		return;

	var left = 0, width = 0;
	if (dateDeadline > this.dateEnd)
	{
		left = this.chart.getPixelsInPeriod(this.chart.minDate, this.dateEnd);
		width = this.chart.getPixelsInPeriod(this.dateEnd, dateDeadline);
	}
	else if (dateDeadline < this.dateStart)
	{
		left = this.chart.getPixelsInPeriod(this.chart.minDate, this.dateDeadline);
		width = this.chart.getPixelsInPeriod(dateDeadline, this.dateStart);
	}

	this.layout.deadlineBar.style.left = left;
	this.layout.deadlineBar.style.width = width;
};

GanttTask.prototype.resizeDeadlineOverdueBar = function(dateDeadline)
{
	if (this.layout.deadlineOverdue == null)
		return;

	var left = 0, width = 0;
	if (this.dateCompleted == null && this.chart.currentDatetime > dateDeadline)
	{
		left = this.chart.getPixelsInPeriod(this.chart.minDate, dateDeadline);
		width = this.chart.getPixelsInPeriod(dateDeadline, this.chart.currentDatetime);
	}
	else if (this.dateCompleted != null && this.dateCompleted > dateDeadline)
	{
		left = this.chart.getPixelsInPeriod(this.chart.minDate, dateDeadline);
		width = this.chart.getPixelsInPeriod(dateDeadline, this.dateCompleted);
	}

	this.layout.deadlineOverdue.style.left = left;
	this.layout.deadlineOverdue.style.width = width;
};

GanttTask.prototype.resizeRealOverdueBar = function(dateDeadline)
{
	if (this.layout.realOverdue == null || this.dateStarted == null)
		return;

	var left = 0, width = 0;

	if (this.dateStarted > dateDeadline)
		dateDeadline = this.dateStarted;

	if (this.dateCompleted == null && this.chart.currentDatetime > dateDeadline)
	{
		left = this.chart.getPixelsInPeriod(this.chart.minDate, dateDeadline);
		width = this.chart.getPixelsInPeriod(dateDeadline, this.chart.currentDatetime);
	}
	else if (this.dateCompleted != null && this.dateCompleted > dateDeadline)
	{
		left = this.chart.getPixelsInPeriod(this.chart.minDate, dateDeadline);
		width = this.chart.getPixelsInPeriod(dateDeadline, this.dateCompleted);
	}

	this.layout.realOverdue.style.left = left;
	this.layout.realOverdue.style.width = width;
};

/*==================Handlers==============*/
GanttTask.prototype.startResize = function(event, mouseUpHandler, mouseMoveHandler, cursor)
{
	event = event || window.event;

	BX.bind(document, "mouseup", mouseUpHandler);
	BX.bind(document, "mousemove", mouseMoveHandler);

	document.body.onselectstart = BX.False;
	document.body.ondragstart = BX.False;
	document.body.style.MozUserSelect = "none";
	document.body.style.cursor = cursor ? cursor : this.isIE && this.ieVersion < 9 ? "e-resize" : "ew-resize";
	//this.chart.layout.root.style.cursor = "ew-resize";

	BX.TaskQuickInfo.hide();
};

GanttTask.prototype.endResize = function(event, mouseUpHandler, mouseMoveHandler)
{
	event = event || window.event;

	BX.unbind(document, "mouseup", mouseUpHandler);
	BX.unbind(document, "mousemove", mouseMoveHandler);

	document.body.onselectstart = null;
	document.body.ondragstart = null;
	document.body.style.MozUserSelect = "";
	document.body.style.cursor = "default";
	//this.chart.layout.root.style.cursor = "default";
};

/*==========================Dealine Resize=========================*/
GanttTask.prototype.onDeadlineMouseDown = function(event)
{
	if (!this.canEditDealine)
		return;

	event = event || window.event;
	BX.fixEventPageX(event);
	if (!BX.GanttChart.isLeftClick(event))
		return;

	//c onsole.log(event.pageX, this.chart.chartContainer.minPageX, this.chart.chartContainer.maxPageX);
	this.resizerOffset = this.chart.getPixelsInPeriod(this.chart.minDate, this.dateDeadline, true);
	this.resizerPageX = event.pageX;
	this.resizerChangePos = false;
	this.denyItemsHover();

	this.startResize(event, BX.proxy(this.onDeadlineMouseUp, this), BX.proxy(this.onDeadlineMouseMove, this));

	BX.PreventDefault(event);
};

GanttTask.prototype.onDeadlineMouseUp = function(event)
{
	this.chart.tooltip.hide();
	this.stopAutoResize();
	this.endResize(event, BX.proxy(this.onDeadlineMouseUp, this), BX.proxy(this.onDeadlineMouseMove, this));
	//c onsole.info(this.dateStart, this.dateEnd, this.dateDeadline);
	this.allowItemsHover(event);
	if (this.resizerChangePos)
		BX.onCustomEvent(this.chart, "onTaskChange", [[this.getEventObject(["dateDeadline"])]]);
};

GanttTask.prototype.onDeadlineMouseMove = function(event)
{
	this.autoResize(event, this.resizeDeadlineDate);
};

GanttTask.prototype.resizeDeadlineDate = function(offset)
{
	this.resizerOffset = this.resizerOffset + offset;
	this.layout.deadlineSlider.style.left = this.resizerOffset - 3 + "px";

	this.dateDeadline = this.chart.getDateFromPixels(this.resizerOffset);

	this.resizeDeadlineBar(this.dateDeadline);
	this.resizeDeadlineOverdueBar(this.dateDeadline);
	this.resizeRealOverdueBar(this.dateDeadline);

	this.chart.tooltip.show(this.resizerOffset, this);

	this.chart.autoExpandTimeline(this.dateDeadline);
};

/*=========================== Date Start Resize =====================*/
GanttTask.prototype.onStartDateMouseDown = function(event)
{
	if (!this.canEditPlanDates)
		return;

	event = event || window.event;
	BX.fixEventPageX(event);
	if (!BX.GanttChart.isLeftClick(event))
		return;

	this.layout.planBarStart.style.zIndex = parseInt(this.layout.planBarEnd.style.zIndex) + 1;
	this.resizerOffset = this.chart.getPixelsInPeriod(this.chart.minDate, this.dateStart, true);
	this.resizerPageX = event.pageX;
	this.resizerChangePos = false;
	this.denyItemsHover();
	this.startResize(event, BX.proxy(this.onStartDateMouseUp, this), BX.proxy(this.onStartDateMouseMove, this));

	BX.PreventDefault(event);
};

GanttTask.prototype.onStartDateMouseUp = function(event)
{
	this.chart.tooltip.hide();
	this.stopAutoResize();
	this.endResize(event, BX.proxy(this.onStartDateMouseUp, this), BX.proxy(this.onStartDateMouseMove, this));
	//c onsole.info(this.dateStart, this.dateEnd, this.dateDeadline);
	this.allowItemsHover(event);
	if (this.resizerChangePos)
		BX.onCustomEvent(this.chart, "onTaskChange", [[this.getEventObject(["dateStart"])]]);
};

GanttTask.prototype.onStartDateMouseMove = function(event)
{
	this.autoResize(event, this.resizeStartDate);
};

GanttTask.prototype.resizeStartDate = function(offset)
{
	this.resizerOffset = this.resizerOffset + offset;
	var dateEndOffset = this.chart.getPixelsInPeriod(this.chart.minDate, this.dateEnd, true);
	var minWidth = this.isRealDateEnd ? 4 : 12;
	if ( (dateEndOffset - this.resizerOffset) < minWidth)
	{
		//Min Task Width
		if (!this.isRealDateEnd)
		{
			this.dateStart = this.chart.getDateFromPixels(this.resizerOffset);
			this.dateEnd = new Date(this.dateStart.getTime());
			this.dateEnd.setUTCHours(this.dateStart.getUTCHours() + 12);
		}
		else
			this.dateStart = this.chart.getDateFromPixels(dateEndOffset - minWidth);

		this.isRealDateStart = true;
		this.dateStartMinutes = 0;
		this.resizePlanBar(this.dateStart, this.dateEnd);
		this.resizeDeadlineBar(this.dateDeadline);
		this.chart.tooltip.show(this.resizerOffset, this);
	}
	else
	{
		this.dateStart = this.chart.getDateFromPixels(this.resizerOffset);
		this.isRealDateStart = true;
		this.dateStartMinutes = 0;
		if (!this.isRealDateEnd)
		{
			this.dateEnd = new Date(this.chart.currentDatetime.getTime());
			this.dateEnd.setUTCHours(0, 0, 0, 0);
			this.dateEnd.setUTCDate(this.dateEnd.getUTCDate()+1);
			if (this.dateEnd <= this.dateStart || (this.dateEnd - this.dateStart) < BX.GanttChart.getMsByHours(12))
			{
				this.dateEnd = new Date(this.dateStart.getTime());
				this.dateEnd.setUTCHours(this.dateStart.getUTCHours() + 12);
			}
		}

		this.resizePlanBar(this.dateStart, this.dateEnd);
		this.resizeDeadlineBar(this.dateDeadline);
		this.chart.tooltip.show(this.resizerOffset, this);
		this.chart.autoExpandTimeline(this.dateStart);
	}
};

/*===========================Date End Resize=========================*/
GanttTask.prototype.onEndDateMouseDown = function(event)
{
	if (!this.canEditPlanDates)
		return;

	event = event || window.event;
	BX.fixEventPageX(event);
	if (!BX.GanttChart.isLeftClick(event))
		return;

	this.layout.planBarEnd.style.zIndex = parseInt(this.layout.planBarStart.style.zIndex) + 1;
	this.resizerOffset = this.chart.getPixelsInPeriod(this.chart.minDate, this.dateEnd, true);
	this.resizerPageX = event.pageX;
	this.resizerChangePos = false;
	this.denyItemsHover();
	this.startResize(event, BX.proxy(this.onEndDateMouseUp, this), BX.proxy(this.onEndDateMouseMove, this));

	BX.PreventDefault(event);
};

GanttTask.prototype.onEndDateMouseUp = function(event)
{
	this.chart.tooltip.hide();
	this.stopAutoResize();
	this.endResize(event, BX.proxy(this.onEndDateMouseUp, this), BX.proxy(this.onEndDateMouseMove, this));
	//c onsole.info(this.dateStart, this.dateEnd, this.dateDeadline);
	this.allowItemsHover(event);
	if (this.resizerChangePos)
		BX.onCustomEvent(this.chart, "onTaskChange", [[this.getEventObject(["dateEnd"])]]);
};

GanttTask.prototype.onEndDateMouseMove = function(event)
{
	if (!this.isRealDateEnd)
	{
		this.isRealDateEnd = true;
		this.layout.planBar.style.width = parseInt(this.layout.planBar.style.width) + 7 + "px";
		BX.removeClass(this.layout.planBar, "task-gantt-bar-plan-endless");
	}

	this.autoResize(event, this.resizeEndDate);
};

GanttTask.prototype.resizeEndDate = function(offset)
{
	this.resizerOffset = this.resizerOffset + offset;
	var dateStartOffset = this.chart.getPixelsInPeriod(this.chart.minDate, this.dateStart, true);
	if (/*this.getMouseOffset(this.autoResizeEvent)  < dateStartOffset - 4 ||*/ (this.resizerOffset - dateStartOffset) < 4)
	{
		//Min Task Width
		this.dateEnd = this.chart.getDateFromPixels(dateStartOffset + 4);
		this.dateEndMinutes = 0;
		this.resizePlanBar(this.dateStart, this.dateEnd);
		this.resizeDeadlineBar(this.dateDeadline);
		this.chart.tooltip.show(this.resizerOffset, this);
	}
	else
	{
		this.dateEnd = this.chart.getDateFromPixels(this.resizerOffset);
		this.dateEndMinutes = 0;
		this.resizePlanBar(this.dateStart, this.dateEnd);
		this.resizeDeadlineBar(this.dateDeadline);
		this.chart.tooltip.show(this.resizerOffset, this);
		this.chart.autoExpandTimeline(this.dateEnd);
	}
};

/* Move Plan Bar */
GanttTask.prototype.onPlanBarMouseDown = function(event)
{
	if (!this.isRealDateEnd || !this.canEditPlanDates)
		return;// this.onStartDateMouseDown(event);

	event = event || window.event;
	BX.fixEventPageX(event);
	if (!BX.GanttChart.isLeftClick(event))
		return;

	this.resizerOffset = this.chart.getPixelsInPeriod(this.chart.minDate, this.dateStart, true);
	this.resizerPageX = event.pageX;
	this.resizerChangePos = false;
	this.denyItemsHover();
	this.startResize(event, BX.proxy(this.onPlanBarMouseUp, this), BX.proxy(this.onPlanBarMouseMove, this), "move");
	this.layout.planBar.style.cursor = "move";
	BX.PreventDefault(event);
};

GanttTask.prototype.onPlanBarMouseUp = function(event)
{
	event = event || window.event;

	this.chart.tooltip.hide();
	this.stopAutoResize();
	this.endResize(event, BX.proxy(this.onPlanBarMouseUp, this), BX.proxy(this.onPlanBarMouseMove, this));
	this.layout.planBar.style.cursor = "default";
	//c onsole.info(this.dateStart, this.dateEnd, this.dateDeadline);
	this.allowItemsHover(event);
	if (this.resizerChangePos)
		BX.onCustomEvent(this.chart, "onTaskChange", [[this.getEventObject(["dateStart", "dateEnd"])]]);
};

GanttTask.prototype.onPlanBarMouseMove = function(event)
{
	this.autoResize(event, this.resizeStartEndDate, true);
};

GanttTask.prototype.resizeStartEndDate = function(offset)
{
	var left = parseInt(this.layout.planBar.style.left);
	var offsetUTCHours = this.chart.getUTCHoursFromPixels(offset);

	this.resizerOffset = this.resizerOffset + offset;

	this.dateStart.setUTCHours(this.dateStart.getUTCHours() + offsetUTCHours);
	this.dateEnd.setUTCHours(this.dateEnd.getUTCHours() + offsetUTCHours);
	this.isRealDateStart = true;
	this.dateStartMinutes = 0;
	this.dateEndMinutes = 0;

	this.layout.planBar.style.left = left + offset + "px";
	this.resizeDeadlineBar(this.dateDeadline);

	//this.chart.tooltip.show(this.resizerOffset, this);
	this.chart.tooltip.show(
			this.autoResizeEvent.pageX
			- this.chart.chartContainer.pos.left - this.chart.gutterOffset
			+ this.chart.layout.timeline.scrollLeft, this);

	this.chart.autoExpandTimeline([this.dateStart, this.dateEnd]);
};

/*============== Auto Resize =========================================*/
GanttTask.prototype.autoResize = function(event, autoResizeCallback, skipSnap)
{
	event = event || window.event;
	BX.fixEventPageX(event);
	this.autoResizeEvent = event;
	this.autoResizeCallback = autoResizeCallback;
	this.resizerChangePos = true;
	skipSnap = !!skipSnap;

	if (event.pageX > this.chart.chartContainer.maxPageX && (event.pageX - this.resizerPageX) > 0 && !this.autoResizeIntID)
	{
		this.stopAutoResize();
		if (!skipSnap)
		{
			var maxOffset = this.chart.chartContainer.width - this.chart.chartContainer.padding - this.chart.gutterOffset + this.chart.layout.timeline.scrollLeft;
			this.autoResizeCallback(maxOffset - this.resizerOffset);
			this.resizerPageX = this.chart.chartContainer.maxPageX;
		}

		this.autoResizeIntID = setInterval(BX.proxy(this.autoResizeRight, this), this.autoResizeTimeout);
	}
	else if (event.pageX < this.chart.chartContainer.minPageX && (event.pageX - this.resizerPageX) < 0 && !this.autoResizeIntID)
	{
		this.stopAutoResize();

		if (!skipSnap)
		{
			var minOffset = this.chart.chartContainer.padding + this.chart.layout.timeline.scrollLeft;
			this.autoResizeCallback(minOffset - this.resizerOffset);
			this.resizerPageX = this.chart.chartContainer.minPageX;
		}
		
		this.autoResizeIntID = setInterval(BX.proxy(this.autoResizeLeft, this), this.autoResizeTimeout);
	}
	else if ((event.pageX > this.chart.chartContainer.minPageX && event.pageX < this.chart.chartContainer.maxPageX) || !this.autoResizeIntID)
	{
		this.stopAutoResize();
		this.autoResizeCallback(event.pageX - this.resizerPageX);
		this.resizerPageX = event.pageX;
	}
	else
		this.resizerPageX = event.pageX;
};

GanttTask.prototype.stopAutoResize = function()
{
	if (this.autoResizeIntID)
		clearInterval(this.autoResizeIntID);
	this.autoResizeIntID = null;
};

GanttTask.prototype.autoResizeRight = function()
{
	this.chart.layout.timeline.scrollLeft = this.chart.layout.timeline.scrollLeft + this.chart.dayInPixels;
	this.autoResizeCallback(this.chart.dayInPixels);
};

GanttTask.prototype.autoResizeLeft = function()
{
	this.chart.layout.timeline.scrollLeft = this.chart.layout.timeline.scrollLeft - this.chart.dayInPixels;
	this.autoResizeCallback(-this.chart.dayInPixels);
};

GanttTask.prototype.getMouseOffset = function(event)
{
	return event.pageX - (this.chart.chartContainer.pos.left + this.chart.gutterOffset) + this.chart.layout.timeline.scrollLeft;
};

GanttTask.prototype.getRealDates = function()
{
	var dateStart = this.isRealDateStart ? this.dateStart : null;
	var dateEnd = this.isRealDateEnd ? this.dateEnd : null;
	if (dateStart && this.dateStartMinutes > 0)
	{
		dateStart = new Date(this.dateStart.getTime());
		dateStart.setUTCMinutes(this.dateStartMinutes, 0, 0);
	}

	if (dateEnd && this.dateEndMinutes > 0)
	{
		dateEnd = new Date(this.dateEnd.getTime());
		dateEnd.setUTCHours(dateEnd.getUTCHours()-1, this.dateEndMinutes, 0, 0);
	}

	return {
		dateCreated : BX.GanttChart.convertDateFromUTC(this.dateCreated),
		dateStart : BX.GanttChart.convertDateFromUTC(dateStart),
		dateEnd : BX.GanttChart.convertDateFromUTC(dateEnd),
		dateDeadline : BX.GanttChart.convertDateFromUTC(this.dateDeadline),
		dateCompleted : BX.GanttChart.convertDateFromUTC(this.dateCompleted),
		dateStarted : BX.GanttChart.convertDateFromUTC(this.dateStarted)
	};
};

GanttTask.prototype.getEventObject = function(changes)
{
	var obj = this.getRealDates();
	obj.task = this;
	obj.changes = BX.type.isArray(changes) ? changes : [];
	return obj;
};

var GanttTooltip = function(chart)
{
	this.chart = chart;
	this.initTop = false;
	this.window = null;
	this.start = null;
	this.end = null;
	this.deadline = null;
	this.windowSize = 0;

	var initDate = this.formatDate(this.chart.currentDatetime);

	(this.window = BX.create("div", { props : { className: "task-gantt-hint" }, children :[
		BX.create("span", { props : { className: "task-gantt-hint-names"}, children : [
			BX.create("span", { props : { className: "task-gantt-hint-name"}, text : BX.message("TASKS_GANTT_DATE_START") + ":" }),
			BX.create("span", { props : { className: "task-gantt-hint-name"}, text : BX.message("TASKS_GANTT_DATE_END") + ":" }),
			BX.create("span", { props : { className: "task-gantt-hint-name"}, text : BX.message("TASKS_GANTT_DEADLINE") + ":" })
		]}),
		BX.create("span", { props : { className: "task-gantt-hint-values"}, children : [
			(this.start = BX.create("span", { props : { className: "task-gantt-hint-value"}, html : initDate })),
			(this.end = BX.create("span", { props : { className: "task-gantt-hint-value"}, html : initDate })),
			(this.deadline = BX.create("span", { props : { className: "task-gantt-hint-value"}, html : initDate }))
		]})
	]}));
};

GanttTooltip.prototype.getLayout = function()
{
	return this.window;
};

GanttTooltip.prototype.init = function(task)
{
	this.window.style.top = task.layout.row.offsetTop + 9 + "px";
	this.initTop = true;
	this.windowSize = this.window.offsetWidth;
};

GanttTooltip.prototype.show = function(resizerOffset, task)
{
	if (!this.initTop)
		this.init(task);

	var dateStart = task.isRealDateStart ? task.dateStart : null;
	var dateEnd = task.isRealDateEnd ? task.dateEnd : null;
	if (dateStart && task.dateStartMinutes > 0)
	{
		dateStart = new Date(task.dateStart.getTime());
		dateStart.setUTCMinutes(task.dateStartMinutes, 0, 0);
	}

	if (dateEnd && task.dateEndMinutes > 0)
	{
		dateEnd = new Date(task.dateEnd.getTime());
		dateEnd.setUTCHours(dateEnd.getUTCHours()-1, task.dateEndMinutes, 0, 0);
	}

	this.start.innerHTML = this.formatDate(dateStart);
	this.end.innerHTML = this.formatDate(dateEnd);
	this.deadline.innerHTML = this.formatDate(task.dateDeadline);

	var maxOffset = this.chart.chartContainer.width - this.chart.chartContainer.padding - this.chart.gutterOffset + this.chart.layout.timeline.scrollLeft;
	var minOffset = this.chart.chartContainer.padding + this.chart.layout.timeline.scrollLeft;

	if ( (resizerOffset + this.windowSize/2) >= maxOffset)
		this.window.style.left = maxOffset - this.windowSize + "px";
	else if ( (resizerOffset - this.windowSize/2) <= minOffset)
		this.window.style.left = minOffset + "px";
	else if (resizerOffset >= maxOffset)
		this.window.style.left = resizerOffset - this.windowSize + "px";
	else
		this.window.style.left = resizerOffset - this.windowSize/2 + "px";
};

GanttTooltip.prototype.formatDate = function(date)
{
	if (!date)
		return BX.message("TASKS_GANTT_EMPTY_DATE");

	var format = this.chart.dateFormat
		.replace(/YYYY/ig, "<span class=\"task-gantt-hint-year\">" + date.getUTCFullYear().toString().substr(2) + "</span>")
		.replace(/MMMM/ig, BX.util.str_pad_left((date.getUTCMonth()+1).toString(), 2, "0"))
		.replace(/MM/ig, BX.util.str_pad_left((date.getUTCMonth()+1).toString(), 2, "0"))
		.replace(/M/ig, BX.util.str_pad_left((date.getUTCMonth()+1).toString(), 2, "0"))
		.replace(/DD/ig, BX.util.str_pad_left(date.getUTCDate().toString(), 2, "0"));

	var hours = date.getUTCHours().toString();
	if (BX.isAmPmMode())
	{
		var amPm = ' am';
		if (hours > 12)
		{
			hours = hours - 12;
			amPm = ' pm';
		}
		else if (hours == 12)
		{
			amPm = ' pm';
		}
	}

	return format + " " + BX.util.str_pad_left(hours, 2, "0") + ":" + BX.util.str_pad_left(date.getUTCMinutes().toString(), 2, "0") + (amPm != undefined ? amPm : '');
};

GanttTooltip.prototype.hide = function()
{
	this.window.style.left = "-400px";
	this.initTop = false;
}

})();