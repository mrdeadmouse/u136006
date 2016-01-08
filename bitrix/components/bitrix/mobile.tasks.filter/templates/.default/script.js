function __MB_TASKS_TASK_FILTER_scrollPageBottom()
{
	var objDiv = document.body;
	objDiv.scrollTop = objDiv.scrollHeight;
}

function __MB_TASKS_TASK_FILTER_SwitchFilter(id)
{
	var prevId = BX('tasks-filter-current').value;
	BX('tasks-filter-current').value = id;

	if (BX("tasks_filter_preset_" + prevId))
		BX("tasks_filter_preset_" + prevId).className = 'task-filter-row';

	if (BX("tasks_filter_preset_" + id))
		BX("tasks_filter_preset_" + id).className = 'task-filter-row tasks-filter-active-preset';

	app.onCustomEvent(
		'onTasksFilterChange',
		{
			module_id:        'tasks',
			emitter:          'tasks filter',
			filter_preset_id: id,
			user_id:          MBTasks.userId
		}
	);

	app.closeController();
}

BX.addCustomEvent(
	'onTasksFilterChange', 
	function(data)
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

		var prevId = BX('tasks-filter-current').value;
		BX('tasks-filter-current').value = data.filter_preset_id;

		if (BX("tasks_filter_preset_" + prevId))
			BX("tasks_filter_preset_" + prevId).className = '';

		if (BX("tasks_filter_preset_" + data.filter_preset_id))
			BX("tasks_filter_preset_" + data.filter_preset_id).className = 'task-filter-row tasks-filter-active-preset';

		return;
	}
);

BX.ready(function(){
	if (window.MBTasks.CPT.Filter.LoadingImage)
		return;

	MBTasks.CPT.Filter.LoadingImage = BX.create(
		'img', { props: { src: '/bitrix/templates/mobile_app/images/tasks/loader_small.gif' } }
	);

	MBTasks.CPT.Filter.GetNextId = function()
	{
		return (MBTasks.CPT.Filter.counter++);
	}
});
