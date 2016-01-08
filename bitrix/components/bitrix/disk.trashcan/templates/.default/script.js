BX.namespace("BX.Disk");
BX.Disk.TrashCanClass = (function (){

	var TrashCanClass = function (parameters)
	{
		this.grid = parameters.grid;
		this.infoPanelContainer = parameters.infoPanelContainer;
		this.gridGroupActionButton = BX(parameters.gridGroupActionButton);
		this.gridShowTreeButton = BX(parameters.gridShowTreeButton);
		this.rootObject = parameters.rootObject || {};
		this.currentObjectIdInInfoPanel = null;
		this.actionGroupButton = 'move';
		this.checkboxes = {};

		this.ajaxUrl = '/bitrix/components/bitrix/disk.trashcan/ajax.php';

		this.grid.SetActionName('move');
		this.setGroupActionTargetObjectId(this.rootObject.id);
		this.setEvents();
	};

	TrashCanClass.prototype.setEvents = function()
	{
		BX.bind(BX('delete_button_' + this.grid.table_id), "click", BX.proxy(this.onClickDeleteGroup, this));
		BX.bind(this.gridGroupActionButton, 'click', BX.proxy(this.onClickGridGroupActionButton, this));
		BX.bind(this.gridShowTreeButton, 'click', BX.proxy(this.onClickGridShowTreeButton, this));

		BX.addCustomEvent("onEmptyTrashCan", BX.proxy(this.openConfirmEmpty, this));
		BX.addCustomEvent("onSelectRow", BX.proxy(this.onSelectRow, this));
		BX.addCustomEvent("onUnSelectRow", BX.proxy(this.onUnSelectRow, this));
	};

	TrashCanClass.prototype.setGroupActionTargetObjectId = function (targetObjectId)
	{
		var pos = BX('grid_group_action_target_object');
		if(!pos)
		{
			this.grid.GetForm().appendChild(BX.create('input', {
				props: {
					id: 'grid_group_action_target_object',
					name: 'grid_group_action_target_object',
					type: 'hidden',
					value: targetObjectId
				}
			}));
		}
		else
		{
			pos.value = targetObjectId;
		}
	};

	TrashCanClass.prototype.onClickGridGroupActionButton = function (e)
	{
		function findLabelSpan(bindNode)
		{
			if(!bindNode)
				return;
			return BX.findChild(bindNode, {tagName: 'span'});
		}
		BX.PopupMenu.show(
			'folder-list-action-all-btn-menu',
			BX('folder-list-action-all-btn'),
			[
				{
					text: BX.message('DISK_TRASHCAN_TITLE_GRID_TOOLBAR_MOVE_BUTTON'),
					onclick: BX.delegate(function (e) {
						this.actionGroupButton = 'move';
						this.grid.SetActionName('move');
						var menu = BX.PopupMenu.getMenuById('folder-list-action-all-btn-menu');
						if(menu)
						{
							BX.adjust(findLabelSpan(menu.bindElement), {text: BX.message('DISK_TRASHCAN_TITLE_GRID_TOOLBAR_MOVE_BUTTON')});
							menu.popupWindow.close();
						}
					}, this)
				},
				{
					text: BX.message('DISK_TRASHCAN_TITLE_GRID_TOOLBAR_COPY_BUTTON'),
					onclick: BX.delegate(function (e) {
						this.actionGroupButton = 'copy';
						this.grid.SetActionName('copy');
						var menu = BX.PopupMenu.getMenuById('folder-list-action-all-btn-menu');
						if(menu)
						{
							BX.adjust(findLabelSpan(menu.bindElement), {text: BX.message('DISK_TRASHCAN_TITLE_GRID_TOOLBAR_COPY_BUTTON')});
							menu.popupWindow.close();
						}
					}, this)
				}
			],
			{
				autoHide: true
			}
		);
	};

	TrashCanClass.prototype.onClickDeleteGroup = function(e)
	{
		if(!this.grid.IsActionEnabled())
			return false;
		var allRows = document.getElementById('actallrows_' + this.grid.table_id);

		this.openConfirmDeleteGroup({
			attemptDeleteAll: allRows && allRows.checked
		});
		BX.PreventDefault(e);
		return false;
	};

	TrashCanClass.prototype.removeRow = function(objectId, completeCallback)
	{
		this.grid.removeRow(objectId, completeCallback);
	};

	TrashCanClass.prototype.getRow = function(objectId)
	{
		return this.grid.getRow(objectId);
	};

	TrashCanClass.prototype.getRowByCheckBox = function(checkbox)
	{
		return this.grid.getRowByCheckBox(checkbox);
	};

	TrashCanClass.prototype.getCheckbox = function(objectId)
	{
		return this.grid.getCheckbox(objectId);
	};

	TrashCanClass.prototype.openConfirmDelete = function (parameters)
	{
		var name = parameters.object.name;
		var objectId = parameters.object.id;
		var isFolder = parameters.object.isFolder;
		var messageDescription = '';
		if (isFolder) {
			messageDescription = BX.message('DISK_TRASHCAN_TRASH_DELETE_DESTROY_FOLDER_CONFIRM');
		} else {
			messageDescription = BX.message('DISK_TRASHCAN_TRASH_DELETE_DESTROY_FILE_CONFIRM');
		}
		var buttons = [
			BX.create('a', {
				text: BX.message('DISK_TRASHCAN_TRASH_DESTROY_BUTTON'),
				props: {
					className: 'bx-disk-btn bx-disk-btn-big bx-disk-btn-green'
				},
				events: {
					click: BX.delegate(function (e)
					{
						BX.PopupWindowManager.getCurrentPopup().destroy();
						BX.PreventDefault(e);

						BX.ajax({
							method: 'POST',
							dataType: 'json',
							url: BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'destroy'),
							data: {
								objectId: objectId,
								sessid: BX.bitrix_sessid()
							},
							onsuccess: BX.delegate(function (data)
							{
								if (!data) {
									return;
								}
								if (data.status == 'success') {
									this.removeRow(objectId, function ()
									{
										BX.Disk.showModalWithStatusAction(data);
									});
									return;
								}
								BX.Disk.showModalWithStatusAction(data);
							}, this)
						});

						return false;
					}, this)
				}
			}),
			BX.create('a', {
				text: BX.message('DISK_TRASHCAN_TRASH_CANCEL_DELETE_BUTTON'),
				props: {
					className: 'bx-disk-btn bx-disk-btn-big bx-disk-btn-transparent'
				},
				events: {
					click: function (e)
					{
						BX.PopupWindowManager.getCurrentPopup().destroy();
						BX.PreventDefault(e);
						return false;
					}
				}
			})
		];

		BX.Disk.modalWindow({
			modalId: 'bx-link-unlink-confirm',
			title: BX.message('DISK_TRASHCAN_TRASH_DELETE_TITLE'),
			contentClassName: 'tac',
			contentStyle: {
				paddingTop: '70px',
				paddingBottom: '70px'
			},
			content: messageDescription.replace('#NAME#', name),
			buttons: buttons
		});
	};

	TrashCanClass.prototype.openConfirmRestore = function (parameters)
	{
		var name = parameters.object.name;
		var objectId = parameters.object.id;
		var isFolder = parameters.object.isFolder;
		var messageDescription = '';
		if (isFolder) {
			messageDescription = BX.message('DISK_TRASHCAN_TRASH_RESTORE_FOLDER_CONFIRM');
		} else {
			messageDescription = BX.message('DISK_TRASHCAN_TRASH_RESTORE_FILE_CONFIRM');
		}
		var buttons = [
			BX.create('a', {
				text: BX.message('DISK_TRASHCAN_TRASH_RESTORE_BUTTON'),
				props: {
					className: 'bx-disk-btn bx-disk-btn-big bx-disk-btn-green'
				},
				events: {
					click: BX.delegate(function (e)
					{
						BX.PopupWindowManager.getCurrentPopup().destroy();
						BX.PreventDefault(e);

						BX.ajax({
							method: 'POST',
							dataType: 'json',
							url: BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'restore'),
							data: {
								objectId: objectId,
								sessid: BX.bitrix_sessid()
							},
							onsuccess: BX.delegate(function (data)
							{
								if (!data) {
									return;
								}
								if (data.status == 'success') {
									this.removeRow(objectId, function ()
									{
										BX.Disk.showModalWithStatusAction(data);
									});
									return;
								}
								BX.Disk.showModalWithStatusAction(data);
							}, this)
						});

						return false;
					}, this)
				}
			}),
			BX.create('a', {
				text: BX.message('DISK_TRASHCAN_TRASH_CANCEL_DELETE_BUTTON'),
				props: {
					className: 'bx-disk-btn bx-disk-btn-big bx-disk-btn-transparent'
				},
				events: {
					click: function (e)
					{
						BX.PopupWindowManager.getCurrentPopup().destroy();
						BX.PreventDefault(e);
						return false;
					}
				}
			})
		];

		BX.Disk.modalWindow({
			modalId: 'bx-link-unlink-confirm',
			title: BX.message('DISK_TRASHCAN_TRASH_RESTORE_TITLE'),
			contentClassName: 'tac',
			contentStyle: {
				paddingTop: '70px',
				paddingBottom: '70px'
			},
			content: messageDescription.replace('#NAME#', name),
			buttons: buttons
		});
	};

	var stopEmptyTrashCan = false;
	var countItemsToDestroy = 0;
	function destroyPortion()
	{
		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'destroyPortion'),
			data: {
				objectId: this.rootObject.id,
				sessid: BX.bitrix_sessid()
			},
			onsuccess: BX.delegate(function (data)
			{
				if (!data || data.status != 'success')
					BX.Disk.showModalWithStatusAction(data);

				countItemsToDestroy -= data.countItems;
				if(countItemsToDestroy < 0)
				{
					countItemsToDestroy = 0;
				}
				var container = BX('bx-elements-to-destroy');
				BX.adjust(container, {
					text: countItemsToDestroy
				});

				if(countItemsToDestroy && !stopEmptyTrashCan)
				{
					BX.delegate(destroyPortion, this)();
				}
				else
				{
					BX.reload();
				}

			}, this)
		});
	}

	TrashCanClass.prototype.openConfirmEmpty = function()
	{
		var messageDescription = BX.message('DISK_TRASHCAN_TRASH_EMPTY_FOLDER_CONFIRM');

		var buttons = [
			BX.create('a', {
				text: BX.message('DISK_TRASHCAN_TRASH_EMPTY_BUTTON'),
				props: {
					id: 'bx-disk-btn-start-trashcan',
					className: 'bx-disk-btn bx-disk-btn-big bx-disk-btn-green'
				},
				events: {
					click: BX.delegate(function (e)
					{
						BX.PreventDefault(e);

						BX.remove(BX('bx-disk-btn-start-trashcan'));
						BX.remove(BX('bx-disk-btn-cancel-trashcan'));
						BX.show(BX('bx-disk-btn-stop-empty-trashcan'), 'inline-block');

						BX.ajax({
							method: 'POST',
							dataType: 'json',
							url: BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'calculate'),
							data: {
								objectId: this.rootObject.id,
								sessid: BX.bitrix_sessid()
							},
							onsuccess: BX.delegate(function (data)
							{
								if(!data || data.status != 'success')
									BX.Disk.showModalWithStatusAction(data);

								var container = BX('bx-empty-trashcan-container');
								BX.cleanNode(container);

								countItemsToDestroy = data.countItems;
								BX.adjust(container, {
									children: [
										BX.create('span', {
											text: BX.message('DISK_TRASHCAN_TRASH_COUNT_ELEMENTS')
										}),
										BX.create('span', {
											props: {
												id: 'bx-elements-to-destroy'
											},
											text: data.countItems
										}),
										BX.create('span', {
											style: {
												margin: '0 auto',
												backgroundColor: 'transparent',
												border: 'none',
												position: 'relative'
											},
											props: {
												id: 'wd_progress',
												className: 'bx-core-waitwindow'
											}
										})
									]
								});
								BX.delegate(destroyPortion, this)();

							}, this)
						});
					}, this)
				}
			}),
			BX.create('a', {
				text: BX.message('DISK_TRASHCAN_TRASH_CANCEL_DELETE_BUTTON'),
				props: {
					id: 'bx-disk-btn-cancel-trashcan',
					className: 'bx-disk-btn bx-disk-btn-big bx-disk-btn-transparent'
				},
				events: {
					click: function (e)
					{
						BX.PopupWindowManager.getCurrentPopup().destroy();
						BX.PreventDefault(e);
						return false;
					}
				}
			}),
			BX.create('a', {
				text: BX.message('DISK_TRASHCAN_TRASH_CANCEL_STOP_BUTTON'),
				props: {
					id: 'bx-disk-btn-stop-empty-trashcan',
					className: 'bx-disk-btn bx-disk-btn-big bx-disk-btn-transparent'
				},
				style: {
					display: 'none'
				},
				events: {
					click: function (e)
					{
						stopEmptyTrashCan = true;
						BX.PreventDefault(e);
						return false;
					}
				}
			})
		];

		BX.Disk.modalWindow({
			modalId: 'bx-empty-trashcan-modal',
			title: BX.message('DISK_TRASHCAN_TRASH_EMPTY_TITLE'),
			contentClassName: 'tac',
			contentStyle: {
				paddingTop: '70px',
				paddingBottom: '70px'
			},
			content: [
				BX.create('div', {
					props: {
						id: 'bx-empty-trashcan-container'
					},
					text: messageDescription
				})
			],
			buttons: buttons
		});
	};

	TrashCanClass.prototype.openConfirmDeleteGroup = function (parameters)
	{
		var messageDescription = BX.message('DISK_TRASHCAN_TRASH_DELETE_GROUP_CONFIRM');
		var buttons = [
			BX.create('a', {
				text: BX.message('DISK_TRASHCAN_TRASH_DESTROY_BUTTON'),
				props: {
					className: 'bx-disk-btn bx-disk-btn-big bx-disk-btn-green'
				},
				events: {
					click: BX.delegate(function (e) {
						BX.PopupWindowManager.getCurrentPopup().destroy();
						BX.PreventDefault(e);

						this.grid.ActionDelete();
						return false;
					}, this)
				}
			}),

			BX.create('a', {
				text: BX.message('DISK_TRASHCAN_TRASH_CANCEL_DELETE_BUTTON'),
				props: {
					className: 'bx-disk-btn bx-disk-btn-big bx-disk-btn-transparent'
				},
				events: {
					click: function (e) {
						BX.PopupWindowManager.getCurrentPopup().destroy();
						BX.PreventDefault(e);
						return false;
					}
				}
			})
		];

		BX.Disk.modalWindow({
			modalId: 'bx-link-unlink-confirm',
			title: BX.message('DISK_TRASHCAN_TRASH_DELETE_TITLE'),
			contentClassName: 'tac',
			contentStyle: {
				paddingTop: '70px',
				paddingBottom: '70px'
			},
			content: messageDescription,
			buttons: buttons
		});
	};

	TrashCanClass.prototype.openConfirmRestoreGroup = function (parameters)
	{
		var messageDescription = BX.message('DISK_TRASHCAN_TRASH_RESTORE_GROUP_CONFIRM');
		var buttons = [
			BX.create('a', {
				text: BX.message('DISK_TRASHCAN_TRASH_RESTORE_BUTTON'),
				props: {
					className: 'bx-disk-btn bx-disk-btn-big bx-disk-btn-green'
				},
				events: {
					click: BX.delegate(function (e) {
						BX.PopupWindowManager.getCurrentPopup().destroy();
						BX.PreventDefault(e);

						this.grid.SetActionName('restore');
						this.actionGroupButton = 'restore';
						this.grid.SetActionName('restore');

						BX.submit(this.grid.GetForm());

						return false;
					}, this)
				}
			}),

			BX.create('a', {
				text: BX.message('DISK_TRASHCAN_TRASH_CANCEL_DELETE_BUTTON'),
				props: {
					className: 'bx-disk-btn bx-disk-btn-big bx-disk-btn-transparent'
				},
				events: {
					click: function (e) {
						BX.PopupWindowManager.getCurrentPopup().destroy();
						BX.PreventDefault(e);
						return false;
					}
				}
			})
		];

		BX.Disk.modalWindow({
			modalId: 'bx-link-unlink-confirm',
			title: BX.message('DISK_TRASHCAN_TRASH_RESTORE_TITLE'),
			contentClassName: 'tac',
			contentStyle: {
				paddingTop: '70px',
				paddingBottom: '70px'
			},
			content: messageDescription,
			buttons: buttons
		});
	};

	TrashCanClass.prototype.getFirstSelectedCheckbox = function()
	{
		var i;
		for (i in this.checkboxes) {
			if (this.checkboxes.hasOwnProperty(i) && typeof(i) !== 'function' && this.checkboxes[i] == true) {
				break;
			}
		}
		return this.getCheckbox(i);
	};

	TrashCanClass.prototype.addCheckbox = function(checkbox)
	{
		var objectId = checkbox.value;
		if(!this.checkboxes[objectId])
		{
			this.checkboxes[objectId] = true;
		}
	};

	TrashCanClass.prototype.deleteCheckbox = function(checkbox)
	{
		var objectId = checkbox.value;
		if(this.checkboxes[objectId])
		{
			this.checkboxes[objectId] = false;
		}
	};

	TrashCanClass.prototype.showInfoPanelManyObject = function(objectIds)
	{
		var iconClass = 'bx-file-icon-container-small bx-disk-file-icon';
		var buttons = [];

		buttons.push(BX.create('a', {
			text: BX.message('DISK_TRASHCAN_TITLE_SIDEBAR_MANY_RESTORE_BUTTON'),
			style: {
				width: '145px'
			},
			props: {
				className: 'bx-disk-btn bx-disk-btn-medium bx-disk-btn-lightgray'
			},
			events: {
				click: BX.proxy(this.openConfirmRestoreGroup, this)
			}
		}));
		buttons.push(BX.create('SPAN', {html: '&nbsp;'}));
		buttons.push(BX.create('a', {
			text: BX.message('DISK_TRASHCAN_TITLE_SIDEBAR_MANY_DELETE_BUTTON'),
			style: {
				width: '145px'
			},
			props: {
				className: 'bx-disk-btn bx-disk-btn-medium bx-disk-btn-lightgray'
			},
			events: {
				click: BX.delegate(function(e){
					BX.fireEvent(BX('delete_button_' + this.grid.table_id), 'click');
				}, this)
			}
		}));

		this.showInfoPanel({
			buttons: buttons,
			isFolder: false,
			icon: {
				className: iconClass
			},
			title: {
				text: BX.Disk.getNumericCase(objectIds.length, BX.message('DISK_TRASHCAN_SELECTED_OBJECT_1'), BX.message('DISK_TRASHCAN_SELECTED_OBJECT_21'), BX.message('DISK_TRASHCAN_SELECTED_OBJECT_2_4'), BX.message('DISK_TRASHCAN_SELECTED_OBJECT_5_20')).replace('#COUNT#', objectIds.length)
			}
		});
	};

	TrashCanClass.prototype.showInfoPanelSingleObject = function(objectId)
	{
		this.currentObjectIdInInfoPanel = objectId;
		var buttons = [];
		var internalLink = '';
		var row = this.getRow(objectId);
		var title = BX.findChild(row, {
			tagName: 'a',
			className: 'bx-disk-folder-title'
		}, true);
		var iconClass = 'bx-file-icon-container-small bx-disk-file-icon';
		var icon = BX.findChild(row, function(node){
			return BX.type.isElementNode(node) && (BX.hasClass(node, 'bx-disk-file-icon') || BX.hasClass(node, 'bx-disk-folder-icon'));
		}, true);
		if(icon)
		{
			iconClass = icon.className;
		}

		if(!title)
		{
			return;
		}
		if(!row.oncontextmenu)
		{
			return;
		}
		var actions = row.oncontextmenu();
		for (var i in actions) {
			if (!actions.hasOwnProperty(i)) {
				continue;
			}
			var action = actions[i];
			if(action && action['PSEUDO_NAME'])
			{
				switch(action['PSEUDO_NAME'].toLowerCase())
				{
					case 'open':
						if(buttons.length > 0){
							buttons.push(BX.create('SPAN', {html: '&nbsp;'}));
						}
						buttons.push(BX.create('a', {
							attrs: {onclick: action['ONCLICK']},
							text: action['TEXT'],
							style: {
								width: '145px'
							},
							props: {
								className: 'bx-disk-btn bx-disk-btn-medium bx-disk-btn-green'
							}
						}));
						break;
					case 'restore':
					case 'download':
					case 'delete':
					case 'destroy':
						if(buttons.length > 0){
							buttons.push(BX.create('SPAN', {html: '&nbsp;'}));
						}
						buttons.push(BX.create('a', {
							attrs: {onclick: action['ONCLICK']},
							text: action['TEXT'],
							style: {
								width: '145px'
							},
							props: {
								className: 'bx-disk-btn bx-disk-btn-medium bx-disk-btn-lightgray'
							}
						}));
						break;
				}
			}
		}

		this.showInfoPanel({
			buttons: buttons,
			isFolder: BX.hasClass(icon, 'bx-disk-folder-icon'),
			icon: {
				className: iconClass
			},
			objectId: objectId,
			title: {
				text: title.text,
				date: title.getAttribute('data-bx-dateModify'),
				href: title.getAttribute('href')
			}
		});
	};

	TrashCanClass.prototype.showInfoPanel = function(params)
	{
		var title = params.title || {};
		var icon = params.icon || {};
		var buttons = params.buttons || [];
		var internalLink = params.internalLink || '';
		var isFolder = params.isFolder;
		var objectId = params.objectId || null;

		if(!title)
		{
			return;
		}

		var infoPanelContainer = BX('disk_info_panel');
		var emptyContainer = BX('bx_disk_empty_select_section');
		if(emptyContainer)
			BX.hide(emptyContainer);

		BX.cleanNode(infoPanelContainer);
		this.containerWithExtAndIntLinks = null;
		var child = BX.create('div');
		infoPanelContainer.appendChild(child);


		child.appendChild(BX.create('div', {
				props: {
					className: 'bx-disk-info-panel'
				},
				children: [
					BX.create('div', {
						props: {
							className: 'bx-disk-info-panel-relative'
						},
						children: [
							BX.create('div', {
								props: {
									className: 'bx-disk-info-panel-icon'
								},
								children: [
									BX.create('div', {
										props: {
											className: icon.className
										}
									})
								]
							}),
							BX.create('div', {
								props: {
									className: 'bx-disk-info-panel-element-name-container'
								},
								children: [
									BX.create('div', {
										props: {
											id: 'disk_info_panel_name',
											className: 'bx-disk-info-panel-name'
										},
										children: [
											BX.create('a', {
												text: title.text,
												props: {
													title: title.text,
													href: title.href || 'javascript:void(0);'
												}
											})
										]
									}),
									BX.create('div', {
										text: title.date,
										props: {
											className: 'bx-disk-info-panel-date'
										}
									})
								]
							}),
							BX.create('div', {
								props: {
									className: 'bx-disk-info-panel-context'
								}
							})
						]
					}),
					BX.create('div', {
						props: {
							className: 'tal'
						},
						children: buttons
					})
				]
			}));

		(new BX.easing({
			duration : 300,
			start : { opacity: 0, height : 0},
			finish : { opacity : 100, height : child.offsetHeight},
			transition : BX.easing.makeEaseOut(BX.easing.transitions.quad),
			step : function(state) {
				infoPanelContainer.style.height = state.height + "px";
				infoPanelContainer.style.opacity = state.opacity / 100;
			},
			complete : BX.delegate(function() {
				infoPanelContainer.style.cssText = "";
			}, this)
		})).animate();

	};

	TrashCanClass.prototype.onSelectRow = function(grid, selCount, checkbox)
	{
		this.addCheckbox(checkbox);
		if (selCount == 1) {
			this.showInfoPanelSingleObject(checkbox.value);
		}
		else
		{

			var checkboxes = this.grid.GetCheckedCheckboxes();
			var ids = [];
			for (var i in  checkboxes) {
				if (!checkboxes.hasOwnProperty(i) || checkboxes[i].name != 'ID[]') {
					continue;
				}
				ids.push(checkboxes[i].value);
			}

			this.showInfoPanelManyObject(ids);
		}
	};

	TrashCanClass.prototype.onUnSelectRow = function(grid, selCount, checkbox)
	{
		this.deleteCheckbox(checkbox);
		if (selCount == 0) {
			var objectId = checkbox.value;
			var infoPanelContainer = BX('disk_info_panel');

			var child = BX.firstChild(infoPanelContainer);

			(new BX.easing({
				duration : 300,
				start : { opacity: 100, height : child.offsetHeight},
				finish : { opacity : 0, height : 0},
				transition : BX.easing.makeEaseOut(BX.easing.transitions.quad),
				step : function(state) {
					infoPanelContainer.style.height = state.height + "px";
					infoPanelContainer.style.opacity = state.opacity / 10;
				},
				complete : BX.delegate(function() {
					//infoPanelContainer.style.cssText = "";
					//BX.cleanNode(infoPanelContainer);
					var emptyContainer = BX('bx_disk_empty_select_section');
					if(emptyContainer)
						BX.show(emptyContainer, 'block');

				}, this)
			})).animate();
		}
		else
		{
			this.onSelectRow(grid, selCount, this.getFirstSelectedCheckbox());
		}
	};

	return TrashCanClass;
})();
