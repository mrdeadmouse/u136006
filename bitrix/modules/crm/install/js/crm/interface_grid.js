if(typeof(BX.CrmInterfaceGridManager) == 'undefined')
{
	// ownerType, gridId, formName, allRowsCheckBoxId, serviceUrl
	BX.CrmInterfaceGridManager = function()
	{
		this._id = '';
		this._settings = {};
		this._toolbarMenu = null;
		this._applyButtonClickHandler = BX.delegate(this._handleFormApplyButtonClick, this);
		this._setFilterFieldsHandler = BX.delegate(this._onSetFilterFields, this);
		this._getFilterFieldsHandler = BX.delegate(this._onGetFilterFields, this);
	};

	BX.CrmInterfaceGridManager.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};

			this._makeBindings();
			BX.ready(BX.delegate(this._bindOnGridReload, this));

			BX.addCustomEvent(
				window,
				"CrmInterfaceToolbarMenuShow",
				BX.delegate(this._onToolbarMenuShow, this)
			);
			BX.addCustomEvent(
				window,
				"CrmInterfaceToolbarMenuClose",
				BX.delegate(this._onToolbarMenuClose, this)
			);

			BX.addCustomEvent(
				window,
				"BXInterfaceGridCheckColumn",
				BX.delegate(this._onGridColumnCheck, this)
			);
		},
		_onGridColumnCheck: function(sender, eventArgs)
		{
			if(this._toolbarMenu)
			{
				eventArgs["columnMenu"] = this._toolbarMenu.GetMenuByItemId(eventArgs["targetElement"].id);
			}
		},
		_onToolbarMenuShow: function(sender, eventArgs)
		{
			this._toolbarMenu = eventArgs["menu"];
			eventArgs["items"] = this.getGridJsObject().settingsMenu;
		},
		_onToolbarMenuClose: function(sender, eventArgs)
		{
			if(eventArgs["menu"] === this._toolbarMenu)
			{
				this._toolbarMenu = null;
				this.getGridJsObject().SaveColumns();
			}
		},
		getId: function()
		{
			return this._id;
		},
		reinitialize: function()
		{
			this._makeBindings();
		},
		_makeBindings: function()
		{
			var form = this.getForm();
			if(form)
			{
				BX.unbind(form['apply'], 'click', this._applyButtonClickHandler);
				BX.bind(form['apply'], 'click', this._applyButtonClickHandler);
			}

			BX.ready(BX.delegate(this._bindOnSetFilterFields, this));
		},
		_bindOnGridReload: function()
		{
			BX.addCustomEvent(
				window,
				'BXInterfaceGridAfterReload',
				BX.delegate(this._makeBindings, this)
			);
		},
		_bindOnSetFilterFields: function()
		{
			var grid = this.getGridJsObject();

			BX.removeCustomEvent(grid, 'AFTER_SET_FILTER_FIELDS', this._setFilterFieldsHandler);
			BX.addCustomEvent(grid, 'AFTER_SET_FILTER_FIELDS', this._setFilterFieldsHandler);

			BX.removeCustomEvent(grid, 'AFTER_GET_FILTER_FIELDS', this._getFilterFieldsHandler);
			BX.addCustomEvent(grid, 'AFTER_GET_FILTER_FIELDS', this._getFilterFieldsHandler);
		},
		registerFilter: function(filter)
		{
			BX.addCustomEvent(
				filter,
				'AFTER_SET_FILTER_FIELDS',
				BX.delegate(this._onSetFilterFields, this)
			);

			BX.addCustomEvent(
				filter,
				'AFTER_GET_FILTER_FIELDS',
				BX.delegate(this._onGetFilterFields, this)
			);
		},
		_onSetFilterFields: function(sender, form, fields)
		{
			var infos = this.getSetting('filterFields', null);
			if(!BX.type.isArray(infos))
			{
				return;
			}

			var isSettingsContext = form.name.indexOf('flt_settings') === 0;

			var count = infos.length;
			var element = null;
			var paramName = '';
			for(var i = 0; i < count; i++)
			{
				var info = infos[i];
				var id = BX.type.isNotEmptyString(info['id']) ? info['id'] : '';
				var type = BX.type.isNotEmptyString(info['typeName']) ? info['typeName'].toUpperCase() : '';
				var params = info['params'] ? info['params'] : {};

				if(type === 'USER')
				{
					var data = params['data'] ? params['data'] : {};
					this._setElementByFilter(
						data[isSettingsContext ? 'settingsElementId' : 'elementId'],
						data['paramName'],
						fields
					);

					var search = params['search'] ? params['search'] : {};
					this._setElementByFilter(
						search[isSettingsContext ? 'settingsElementId' : 'elementId'],
						search['paramName'],
						fields
					);
				}
			}
		},
		_setElementByFilter: function(elementId, paramName, filter)
		{
			var element = BX.type.isNotEmptyString(elementId) ? BX(elementId) : null;
			if(BX.type.isElementNode(element))
			{
				element.value = BX.type.isNotEmptyString(paramName) && filter[paramName] ? filter[paramName] : '';
			}
		},
		_onGetFilterFields: function(sender, form, fields)
		{
			var infos = this.getSetting('filterFields', null);
			if(!BX.type.isArray(infos))
			{
				return;
			}

			var isSettingsContext = form.name.indexOf('flt_settings') === 0;
			var count = infos.length;
			for(var i = 0; i < count; i++)
			{
				var info = infos[i];
				var id = BX.type.isNotEmptyString(info['id']) ? info['id'] : '';
				var type = BX.type.isNotEmptyString(info['typeName']) ? info['typeName'].toUpperCase() : '';
				var params = info['params'] ? info['params'] : {};

				if(type === 'USER')
				{
					var data = params['data'] ? params['data'] : {};
					this._setFilterByElement(
						data[isSettingsContext ? 'settingsElementId' : 'elementId'],
						data['paramName'],
						fields
					);

					var search = params['search'] ? params['search'] : {};
					this._setFilterByElement(
						search[isSettingsContext ? 'settingsElementId' : 'elementId'],
						search['paramName'],
						fields
					);
				}
			}
		},
		_setFilterByElement: function(elementId, paramName, filter)
		{
			var element = BX.type.isNotEmptyString(elementId) ? BX(elementId) : null;
			if(BX.type.isElementNode(element) && BX.type.isNotEmptyString(paramName))
			{
				filter[paramName] = element.value;
			}
		},
		getSetting: function (name, defaultval)
		{
			return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
		},
		getOwnerType: function()
		{
			return this.getSetting('ownerType', '');
		},
		getForm: function()
		{
			return document.forms[this.getSetting('formName', '')];
		},
		getGrid: function()
		{
			return BX(this.getSetting('gridId', ''));
		},
		getGridJsObject: function()
		{
			var gridId = this.getSetting('gridId', '');
			return BX.type.isNotEmptyString(gridId) ? window['bxGrid_' + gridId] : null;
		},
		getAllRowsCheckBox: function()
		{
			return BX(this.getSetting('allRowsCheckBoxId', ''));
		},
		getEditor: function()
		{
			var editorId = this.getSetting('activityEditorId', '');
			return BX.CrmActivityEditor.items[editorId] ? BX.CrmActivityEditor.items[editorId] : null;
		},
		getServiceUrl: function()
		{
			return this.getSetting('serviceUrl', '/bitrix/components/bitrix/crm.activity.editor/ajax.php');
		},
		_loadCommunications: function(commType, ids, callback)
		{
			BX.ajax(
				{
					'url': this.getServiceUrl(),
					'method': 'POST',
					'dataType': 'json',
					'data':
					{
						'ACTION' : 'GET_ENTITIES_DEFAULT_COMMUNICATIONS',
						'COMMUNICATION_TYPE': commType,
						'ENTITY_TYPE': this.getOwnerType(),
						'ENTITY_IDS': ids,
						'GRID_ID': this.getSetting('gridId', '')
					},
					onsuccess: function(data)
					{
						if(data && data['DATA'] && callback)
						{
							callback(data['DATA']);
						}
					},
					onfailure: function(data)
					{
					}
				}
			);
		},
		_onEmailDataLoaded: function(data)
		{
			var settings = {};
			if(data)
			{
				var items = BX.type.isArray(data['ITEMS']) ? data['ITEMS'] : [];
				if(items.length > 0)
				{
					var entityType = data['ENTITY_TYPE'] ? data['ENTITY_TYPE'] : '';
					var comms = settings['communications'] = [];
					for(var i = 0; i < items.length; i++)
					{
						var item = items[i];
						comms.push(
							{
								'type': 'EMAIL',
								'entityTitle': '',
								'entityType': entityType,
								'entityId': item['entityId'],
								'value': item['value']
							}
						);
					}
				}
			}

			this.addEmail(settings);
		},
		_onCallDataLoaded: function(data)
		{
			var settings = {};
			if(data)
			{
				var items = BX.type.isArray(data['ITEMS']) ? data['ITEMS'] : [];
				if(items.length > 0)
				{
					var entityType = data['ENTITY_TYPE'] ? data['ENTITY_TYPE'] : '';
					var comms = settings['communications'] = [];
					var item = items[0];
					comms.push(
						{
							'type': 'PHONE',
							'entityTitle': '',
							'entityType': entityType,
							'entityId': item['entityId'],
							'value': item['value']
						}
					);
					settings['ownerType'] = entityType;
					settings['ownerID'] = item['entityId'];
				}
			}

			this.addCall(settings);
		},
		_onMeetingDataLoaded: function(data)
		{
			var settings = {};
			if(data)
			{
				var items = BX.type.isArray(data['ITEMS']) ? data['ITEMS'] : [];
				if(items.length > 0)
				{
					var entityType = data['ENTITY_TYPE'] ? data['ENTITY_TYPE'] : '';
					var comms = settings['communications'] = [];
					var item = items[0];
					comms.push(
						{
							'type': '',
							'entityTitle': '',
							'entityType': entityType,
							'entityId': item['entityId'],
							'value': item['value']
						}
					);
					settings['ownerType'] = entityType;
					settings['ownerID'] = item['entityId'];
				}
			}

			this.addMeeting(settings);
		},
		_handleFormApplyButtonClick: function(e)
		{
			var form = this.getForm();
			if(!form)
			{
				return true;
			}

			var selected = form.elements['action_button_' + this.getSetting('gridId', '')];
			if(!selected)
			{
				return;
			}
			
			var value = selected.value;
			if (value === 'subscribe')
			{
				var allRowsCheckBox = this.getAllRowsCheckBox();
				var ids = [];
				if(!(allRowsCheckBox && allRowsCheckBox.checked))
				{
					var checkboxes = BX.findChildren(
						this.getGrid(),
						{
							'tagName': 'INPUT',
							'attribute': { 'type': 'checkbox' }
						},
						true
					);

					if(checkboxes)
					{
						for(var i = 0; i < checkboxes.length; i++)
						{
							var checkbox = checkboxes[i];
							if(checkbox.id.indexOf('ID') == 0 && checkbox.checked)
							{
								ids.push(checkbox.value);
							}
						}
					}
				}
				this._loadCommunications('EMAIL', ids, BX.delegate(this._onEmailDataLoaded, this));
				return BX.PreventDefault(e);
			}

			return true;
		},
		addEmail: function(settings)
		{
			var editor = this.getEditor();
			if(!editor)
			{
				return;
			}

			settings = settings ? settings : {};
			if(typeof(settings['ownerID']) !== 'undefined')
			{
				settings['ownerType'] = this.getOwnerType();
			}

			editor.addEmail(settings);
		},
		addCall: function(settings)
		{
			var editor = this.getEditor();
			if(!editor)
			{
				return;
			}

			settings = settings ? settings : {};
			if(typeof(settings['ownerID']) !== 'undefined')
			{
				settings['ownerType'] = this.getOwnerType();
			}

			editor.addCall(settings);
		},
		addMeeting: function(settings)
		{
			var editor = this.getEditor();
			if(!editor)
			{
				return;
			}

			settings = settings ? settings : {};
			if(typeof(settings['ownerID']) !== 'undefined')
			{
				settings['ownerType'] = this.getOwnerType();
			}

			editor.addMeeting(settings);
		},
		addTask: function(settings)
		{
			var editor = this.getEditor();
			if(!editor)
			{
				return;
			}

			settings = settings ? settings : {};
			if(typeof(settings['ownerID']) !== 'undefined')
			{
				settings['ownerType'] = this.getOwnerType();
			}

			editor.addTask(settings);
		},
		viewActivity: function(id, optopns)
		{
			var editor = this.getEditor();
			if(editor)
			{
				editor.viewActivity(id, optopns);
			}
		}
	};

	BX.CrmInterfaceGridManager.items = {};
	BX.CrmInterfaceGridManager.create = function(id, settings)
	{
		var self = new BX.CrmInterfaceGridManager();
		self.initialize(id, settings);
		this.items[id] = self;

		BX.onCustomEvent(
			this,
			'CREATED',
			[self]
		);

		return self;
	};
	BX.CrmInterfaceGridManager.addEmail = function(editorId, settings)
	{
		if(typeof(this.items[editorId]) !== 'undefined')
		{
			this.items[editorId].addEmail(settings);
		}
	};
	BX.CrmInterfaceGridManager.addCall = function(editorId, settings)
	{
		if(typeof(this.items[editorId]) !== 'undefined')
		{
			this.items[editorId].addCall(settings);
		}
	};
	BX.CrmInterfaceGridManager.addMeeting = function(editorId, settings)
	{
		if(typeof(this.items[editorId]) !== 'undefined')
		{
			this.items[editorId].addMeeting(settings);
		}
	};
	BX.CrmInterfaceGridManager.addTask = function(editorId, settings)
	{
		if(typeof(this.items[editorId]) !== 'undefined')
		{
			this.items[editorId].addTask(settings);
		}
	};
	BX.CrmInterfaceGridManager.viewActivity = function(editorId, id, optopns)
	{
		if(typeof(this.items[editorId]) !== 'undefined')
		{
			this.items[editorId].viewActivity(id, optopns);
		}
	};
	BX.CrmInterfaceGridManager.showPopup = function(id, anchor, items)
	{
		BX.PopupMenu.show(
			id,
			anchor,
			items,
			{
				offsetTop:0,
				offsetLeft:-30
			});
	};
	BX.CrmInterfaceGridManager.reloadGrid = function(gridId)
	{
		var grid = window['bxGrid_' + gridId];
		if(!grid || !BX.type.isFunction(grid.Reload))
		{
			return false;
		}
		grid.Reload();
		return true;
	};
	BX.CrmInterfaceGridManager.applyFilter = function(gridId, filterName)
	{
		var grid = window['bxGrid_' + gridId];
		if(!grid || !BX.type.isFunction(grid.Reload))
		{
			return false;
		}

		grid.ApplyFilter(filterName);
		return true;
	};
	BX.CrmInterfaceGridManager.clearFilter = function(gridId)
	{
		var grid = window['bxGrid_' + gridId];
		if(!grid || !BX.type.isFunction(grid.ClearFilter))
		{
			return false;
		}

		grid.ClearFilter();
		return true;
	};
	BX.CrmInterfaceGridManager.menus = {};
	BX.CrmInterfaceGridManager.createMenu = function(menuId, items, zIndex)
	{
		zIndex = parseInt(zIndex);
		var menu = new PopupMenu(menuId, !isNaN(zIndex) ? zIndex : 1010);
		if(BX.type.isArray(items))
		{
			menu.settingsMenu = items;
		}
		this.menus[menuId] = menu;
	};
	BX.CrmInterfaceGridManager.showMenu = function(menuId, anchor)
	{
		var menu = this.menus[menuId];
		if(typeof(menu) !== 'undefined')
		{
			menu.ShowMenu(anchor, menu.settingsMenu, false, false);
		}
	};
	BX.CrmInterfaceGridManager.expandEllipsis = function(ellepsis)
	{
		if(!BX.type.isDomNode(ellepsis))
		{
			return false;
		}

	    var cut = BX.findNextSibling(ellepsis, { 'class': 'bx-crm-text-cut-on' });
		if(cut)
		{
			BX.removeClass(cut, 'bx-crm-text-cut-on');
			BX.addClass(cut, 'bx-crm-text-cut-off');
			cut.style.display = '';
		}

		ellepsis.style.display = 'none';
		return true;
	};
}
