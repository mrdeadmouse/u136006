BX.namespace("BX.Disk");
BX.Disk.ExternalLinkListClass = (function ()
{
	var ExternalLinkListClass = function (parameters)
	{
		this.grid = parameters.grid;
		this.gridGroupActionButton = BX(parameters.gridGroupActionButton);
		this.gridShowTreeButton = BX(parameters.gridShowTreeButton);
		this.rootObject = parameters.rootObject || {};
		this.checkboxes = {};

		this.actionGroupButton = 'delete';
		this.ajaxUrl = '/bitrix/components/bitrix/disk.external.link.list/ajax.php';


		this.grid.SetActionName('delete');
		this.setEvents();
	};

	ExternalLinkListClass.prototype.myInstanceMethod = function ()
	{
	};

	ExternalLinkListClass.prototype.setEvents = function()
	{
		BX.bind(BX('delete_button_' + this.grid.table_id), "click", BX.proxy(this.onClickDeleteGroup, this));
		BX.addCustomEvent("onSelectRow", BX.proxy(this.onSelectRow, this));
		BX.addCustomEvent("onUnSelectRow", BX.proxy(this.onUnSelectRow, this));
	};

	ExternalLinkListClass.prototype.removeRow = function(objectId, completeCallback)
	{
		this.grid.removeRow(objectId, completeCallback);
	};

	ExternalLinkListClass.prototype.getRow = function(objectId)
	{
		return this.grid.getRow(objectId);
	};

	ExternalLinkListClass.prototype.getRowByCheckBox = function(checkbox)
	{
		return this.grid.getRowByCheckBox(checkbox);
	};

	ExternalLinkListClass.prototype.getCheckbox = function(objectId)
	{
		return this.grid.getCheckbox(objectId);
	};

	ExternalLinkListClass.prototype.disableExternalLink = function (externalId, objectId)
	{
		BX.Disk.modalWindowLoader(BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'disableExternalLink'), {
			id: 'bx-disk-external-link-loader',
			responseType: 'json',
			postData: {
				externalId: externalId,
				objectId: objectId
			},
			afterSuccessLoad: BX.delegate(function(response){

				if(!response || response.status != 'success')
				{
					BX.Disk.showModalWithStatusAction(response);
					return;
				}
				this.removeRow(externalId, function(){
					BX.Disk.showModalWithStatusAction(response);
				});

			}, this)
		});

		return false;
	};

	ExternalLinkListClass.prototype.onClickDeleteGroup = function(e)
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

	ExternalLinkListClass.prototype.openConfirmDeleteGroup = function (parameters)
	{
		var messageDescription = BX.message('DISK_EXTERNAL_LINK_LIST_DELETE_GROUP_CONFIRM');
		var buttons = [
			BX.create('a', {
				text: BX.message('DISK_EXTERNAL_LINK_LIST_DELETE_BUTTON'),
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
				text: BX.message('DISK_EXTERNAL_LINK_LIST_CANCEL_DELETE_BUTTON'),
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
			title: BX.message('DISK_EXTERNAL_LINK_LIST_DELETE_TITLE'),
			contentClassName: 'tac',
			contentStyle: {
				paddingTop: '70px',
				paddingBottom: '70px'
			},
			content: messageDescription,
			buttons: buttons
		});
	};

	ExternalLinkListClass.prototype.showInfoPanelManyObject = function(objectIds)
	{
		var iconClass = 'bx-file-icon-container-small bx-disk-file-icon';
		var buttons = [];

		buttons.push(BX.create('a', {
			text: BX.message('DISK_EXTERNAL_LINK_LIST_DELETE_BUTTON'),
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
				text: BX.Disk.getNumericCase(objectIds.length, BX.message('DISK_EXTERNAL_LINK_LIST_SELECTED_OBJECT_1'), BX.message('DISK_EXTERNAL_LINK_LIST_SELECTED_OBJECT_21'), BX.message('DISK_EXTERNAL_LINK_LIST_SELECTED_OBJECT_2_4'), BX.message('DISK_EXTERNAL_LINK_LIST_SELECTED_OBJECT_5_20')).replace('#COUNT#', objectIds.length)
			}
		});
	};

	ExternalLinkListClass.prototype.showInfoPanelSingleObject = function(objectId)
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
					case 'download':
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
					case 'disable_external_link':
						if(buttons.length > 0){
							buttons.push(BX.create('SPAN', {html: '&nbsp;'}));
						}
						buttons.push(BX.create('a', {
							attrs: {onclick: action['ONCLICK']},
							text: action['SHORT_TEXT'],
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
				href: title.getAttribute('href'),
				date: title.getAttribute('data-bx-dateModify')
			}
		});
	};

	ExternalLinkListClass.prototype.showInfoPanel = function(params)
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
	
	ExternalLinkListClass.prototype.addCheckbox = function(checkbox)
	{
		var objectId = checkbox.value;
		if(!this.checkboxes[objectId])
		{
			this.checkboxes[objectId] = true;
		}
	};

	ExternalLinkListClass.prototype.deleteCheckbox = function(checkbox)
	{
		var objectId = checkbox.value;
		if(this.checkboxes[objectId])
		{
			this.checkboxes[objectId] = false;
		}
	};

	ExternalLinkListClass.prototype.onSelectRow = function(grid, selCount, checkbox)
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

	ExternalLinkListClass.prototype.onUnSelectRow = function(grid, selCount, checkbox)
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

	ExternalLinkListClass.prototype.getFirstSelectedCheckbox = function()
	{
		var i;
		for (i in this.checkboxes) {
			if (this.checkboxes.hasOwnProperty(i) && typeof(i) !== 'function' && this.checkboxes[i] == true) {
				break;
			}
		}
		return this.getCheckbox(i);
	};

	return ExternalLinkListClass;
})();

