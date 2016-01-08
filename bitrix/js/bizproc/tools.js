BX.namespace('BX.Bizproc');
if (typeof BX.Bizproc.doInlineTask === 'undefined')
{
	BX.Bizproc.doInlineTask = function (parameters, callback, scope)
	{
		if (scope)
		{
			if (scope.__waiting)
				return false;
			scope.__waiting = true;
			if (BX.hasClass(scope, 'bp-button'))
				BX.addClass(scope, 'bp-button-wait');
		}
		if (!parameters || !parameters['TASK_ID'])
			return false;
		parameters['sessid'] = BX.bitrix_sessid();
		BX.ajax.post(
			'/bitrix/tools/bizproc_do_task_ajax.php',
			parameters,
			function()
			{
				if (scope)
				{
					scope.__waiting = false;
					BX.removeClass(scope, 'bp-button-wait');
				}
				if (callback)
					callback(arguments);
			}
		);

		return false;
	};
	BX.Bizproc.taskPopupInstance = null;
	BX.Bizproc.taskPopupCallback = null;
	BX.Bizproc.showTaskPopup = function (taskId, callback, userId, scope, useIframe)
	{
		if (scope)
		{
			if (scope.__waiting)
				return false;
			scope.__waiting = true;
			if (BX.hasClass(scope, 'bp-button'))
				BX.addClass(scope, 'bp-button-wait');
		}
		BX.Bizproc.taskPopupInstance = null;
		BX.Bizproc.taskPopupCallback = null;
		BX.ajax({
			method: 'GET',
			dataType: 'html',
			url: '/bitrix/components/bitrix/bizproc.task/popup.php?site_id='+BX.message('SITE_ID')+'&TASK_ID='
				+ taskId + (userId ? '&USER_ID=' + userId : '')
				+ (useIframe ? '&IFRAME=Y' : ''),
			onsuccess: function (HTML)
			{
				BX.load(['/bitrix/components/bitrix/bizproc.task/templates/.default/style.css'], function()
				{
					if (scope)
					{
						scope.__waiting = false;
						BX.removeClass(scope, 'bp-button-wait');
					}
					var wrapper = BX.create('div', {
						style: {width: '800px'}
					});
					wrapper.innerHTML = HTML;
					BX.Bizproc.taskPopupInstance = new BX.PopupWindow("bp-task-popup-" + taskId + Math.round(Math.random() * 100000), null, {
						content: wrapper,
						closeIcon: {right: "20px", top: "10px"},
						zIndex: -100,
						offsetLeft: 0,
						offsetTop: 0,
						closeByEsc: true,
						draggable: {restrict: false},
						overlay: {backgroundColor: 'black', opacity: 30},
						events: {
							onPopupShow: function(popup)
							{
								var title = BX.findChild(popup.contentContainer, {class: 'bp-popup-title'}, true);
								if (title)
								{
									title.style.cursor = "move";
									BX.bind(title, "mousedown", BX.proxy(popup._startDrag, popup));
								}
							},
							onPopupClose: function (popup)
							{
								popup.destroy();
							}
						}

					});
					BX.Bizproc.taskPopupInstance.show();
					BX.Bizproc.taskPopupCallback = callback;
				});
			}
		});

		return false;
	};

	BX.Bizproc.showWorkflowInfoPopup = function (workflowId)
	{
		BX.ajax({
			method: 'GET',
			dataType: 'html',
			url: '/bitrix/components/bitrix/bizproc.workflow.info/popup.php?site_id='+BX.message('SITE_ID')+'&WORKFLOW_ID=' + workflowId,
			onsuccess: function (HTML)
			{
				BX.load(['/bitrix/components/bitrix/bizproc.workflow.info/templates/.default/style.css'], function()
				{
					var wrapper = BX.create('div', {
						style: {width: '800px'}
					});
					wrapper.innerHTML = HTML;
					var popup = new BX.PopupWindow("bp-wfi-popup-" + workflowId + Math.round(Math.random() * 100000), null, {
						content: wrapper,
						closeIcon: {right: "20px", top: "10px"},
						zIndex: -100,
						offsetLeft: 0,
						offsetTop: 0,
						closeByEsc: true,
						draggable: {restrict: false},
						overlay: {backgroundColor: 'black', opacity: 30},
						events: {
							onPopupShow: function(popup)
							{
								var title = BX.findChild(popup.contentContainer, {class: 'bp-popup-title'}, true);
								if (title)
								{
									title.style.cursor = "move";
									BX.bind(title, "mousedown", BX.proxy(popup._startDrag, popup));
								}
							},
							onPopupClose: function (popup)
							{
								popup.destroy();
							}
						}

					});
					popup.show();
				});
			}
		});

		return false;
	};

	BX.Bizproc.postTaskForm = function (form, e)
	{
		if (form.BPRUNNING)
		{
			return;
		}
		BX.PreventDefault(e);

		form.action = '/bitrix/tools/bizproc_do_task_ajax.php';
		form.BPRUNNING = true;

		var actionName, actionValue, btn = document.activeElement;
		if ((!btn || !btn.type) && e.explicitOriginalTarget)
		{
			btn = e.explicitOriginalTarget;
		}

		if (!!btn && btn.type && btn.type.toLowerCase() == 'submit' && !!btn.name && !!btn.value)
		{
			actionName = btn.name;
			actionValue = btn.value;
		}

		if (!form.target)
		{
			if (null == form.BXFormTarget)
			{
				var frame_name = 'formTarget_' + Math.random();
				form.BXFormTarget = document.body.appendChild(BX.create('IFRAME', {
					props: {
						name: frame_name,
						id: frame_name,
						src: 'javascript:void(0)'
					},
					style: {
						display: 'none'
					}
				}));
			}

			form.target = form.BXFormTarget.name;
		}

		var scope = null;
		if (actionName)
		{
			scope = BX.findChild(form, {property: {type: 'submit', name: actionName}}, true);
		}
		if (scope)
		{
			BX.addClass(scope, 'bp-button-wait');
		}

		form.BXFormCallback = function (response)
		{
			form.BPRUNNING = false;
			if (scope)
			{
				BX.removeClass(scope, 'bp-button-wait');
			}
			response = BX.parseJSON(response);
			if (response.ERROR)
				alert(response.ERROR);
			else
			{
				if (!!BX.Bizproc.taskPopupInstance)
					BX.Bizproc.taskPopupInstance.close();
				if (!!BX.Bizproc.taskPopupCallback)
					BX.Bizproc.taskPopupCallback();
			}
		};
		BX.bind(form.BXFormTarget, 'load', BX.proxy(BX.ajax._submit_callback, form));
		BX.submit(form, actionName, actionValue);
	};
}
if (typeof BX.Bizproc.WorkflowFaces === 'undefined')
{
	BX.Bizproc.WorkflowFaces = {};

	BX.Bizproc.WorkflowFaces.showFaces = function(tasks, scope, simple, taskBased)
	{
		if (typeof scope.__popup === 'undefined')
		{
			scope.__popup = new BX.PopupWindow('bp-wf-faces-'+Math.round(Math.random() * 100000), scope, {
				lightShadow : true,
				offsetLeft: -51,
				offsetTop: 3,
				zIndex: 0,
				autoHide: true,
				closeByEsc: true,
				bindOptions: {position: "bottom"},
				angle: {position:'top', offset: 78},
				content : BX.Bizproc.WorkflowFaces.createMenu(tasks, simple, taskBased)
			});
		}
		if (scope.__popup.isShown())
			scope.__popup.close();
		else
			scope.__popup.show();
		return false;
	};

	BX.Bizproc.WorkflowFaces.createMenu = function(tasks, simple, taskBased)
	{
		var i, k, s = tasks.length, l;
		var	tasksContent = [];
		for (i = 0;i < s; ++i)
		{
			var cls, task = tasks[i],
				uContent = [];

			for (k = 0, l = task.USERS.length; k < l; ++k)
			{
				cls = 'bp-popup-parallel-avatar-ready';
				if (task.USERS[k].STATUS == '0')
					cls = '';
				else if (task.USERS[k].STATUS == '2')
					cls = 'bp-popup-parallel-avatar-cancel';

				var tpl = [
					'<a>',
					'<span class="bp-popup-parallel-avatar '+cls+'"><span>'+(task.USERS[k].PHOTO_SRC? '<img src="'+task.USERS[k].PHOTO_SRC+'" alt="">':'')+'</span></span>',
					'<span class="bp-popup-parallel-name" title="'+task.USERS[k].FULL_NAME+'">'+task.USERS[k].FULL_NAME+'</span>',
					'</a>'
				];
				uContent.push(tpl.join(''));
			}
			var usersMenu = uContent.join('');
			if (s == 1 && !taskBased)
				tasksContent.push(usersMenu);
			else
			{
				cls = 'bp-popup-parallel-avatar-ready';
				if (task.USERS[0].STATUS == '0')
					cls = '';
				else if (task.USERS[0].STATUS == '2')
					cls = 'bp-popup-parallel-avatar-cancel';

				var taskHead = [
					'<a class="'+(uContent.length > 1 || simple ? 'bp-popup-parallel-parent' : '')+'">',
					!simple? '<span class="bp-popup-parallel-avatar '+cls+'"><span>'+(task.USERS[0].PHOTO_SRC? '<img src="'+task.USERS[0].PHOTO_SRC+'" alt="">':'')+'</span></span>' : '',
					'<span class="bp-popup-parallel-name" title="'+task.NAME+'">'+(!simple? task.USERS[0].FULL_NAME : task.NAME)+'</span>',
					'</a>'
				];

				tasksContent.push('<div class="bp-popup-parallel-sub">'
					+taskHead.join('')
					+(uContent.length > 1 || simple ? '<div class="bp-popup-parallel">'+usersMenu+'</div></div>' : ''));
			}
		}
		return '<div class="bp-popup-parallel">'+tasksContent.join('')+'</div>';
	}
}