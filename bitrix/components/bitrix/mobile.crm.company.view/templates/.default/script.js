if(typeof(BX.CrmCompanyView) === 'undefined')
{
	BX.CrmCompanyView = function()
	{
		this._id = '';
		this._settings = {};
		this._dispatcher = null;
		this._isDirty = false;
		this._prefix = '';
		this._enableAddCall = this._enableAddEmail = true;
	};

	if(typeof(BX.CrmCompanyView.messages) === 'undefined')
	{
		BX.CrmCompanyView.messages = {};
	}

	BX.CrmCompanyView.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};
			this._dispatcher = this.getSetting('dispatcher', null);
			this._prefix = this.getSetting('prefix');

			var context = BX.CrmMobileContext.getCurrent();

			var addMeetingButton = this.resolveElement('add_meeting_btn');
			if(addMeetingButton)
			{
				BX.bind(addMeetingButton, "click", BX.delegate(this._onMeetingAdd, this));
			}

			this._enableAddCall = this.getSetting('enableAddCall', true);
			var addCallButton = this.resolveElement('add_call_btn');
			if(addCallButton)
			{
				BX.bind(addCallButton, "click", BX.delegate(this._onCallAdd, this));
				if(!this._enableAddCall)
				{
					BX.addClass(addCallButton, "disabled");
				}
			}

			this._enableAddEmail = this.getSetting('enableAddEmail', true);
			var addEmailButton = this.resolveElement('add_email_btn');
			if(addEmailButton)
			{
				BX.bind(addEmailButton, "click", BX.delegate(this._onEmailAdd, this));
				if(!this._enableAddEmail)
				{
					BX.addClass(addEmailButton, "disabled");
				}
			}

			BX.addCustomEvent(
				window,
				'onCrmEntityCreate',
				BX.delegate(this._onExternalCreate, this)
			);

			BX.addCustomEvent(
				window,
				'onCrmEntityUpdate',
				BX.delegate(this._onExternalUpdate, this)
			);

			BX.addCustomEvent(
				window,
				'onCrmEntityDelete',
				BX.delegate(this._onExternalDelete, this)
			);

			BX.addCustomEvent(
				window,
				'onOpenPageAfter',
				BX.delegate(this._onAfterPageOpen, this)
			);

			var permissions = this.getSetting('permissions', {});
			if(permissions['EDIT'] || permissions['DELETE'])
			{
				var menuItems = [];
				if(permissions['EDIT'])
				{
					menuItems.push(
						{
							icon: 'add',
							name:  this.getMessage('menuCreateInvoice'),
							action: BX.delegate(this._onCreateInvoice, this)

						}
					);
					menuItems.push(
						{
							icon: 'edit',
							name:  this.getMessage('menuEdit'),
							action: BX.delegate(this._onEdit, this)
						}
					);
				}
				if(permissions['DELETE'])
				{
					menuItems.push(
						{
							icon: 'delete',
							name: this.getMessage('menuDelete'),
							action: BX.delegate(this._onDelete, this)
						}
					);
				}
				context.prepareMenu(menuItems);
			}

			//TEST ONLY -->
			/*var delBtn = BX('delete');
			if(delBtn)
			{
				BX.bind(delBtn, 'click', BX.delegate(this._onDelete, this));
			}*/
			/*var reloadBtn = BX('reload');
			if(reloadBtn)
			{
				var self = this;
				BX.bind(reloadBtn, 'click', function(){ self._dispatcher.readEntity(self.getEntityId()); });
			}*/
			//<-- TEST ONLY
		},
		getId: function()
		{
			return this._id;
		},
		getSettings: function()
		{
			return this._settings;
		},
		getSetting: function(name, defaultVal)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultVal;
		},
		setSetting: function(name, val)
		{
			this._settings[name] = val;
		},
		getMessage: function(name)
		{
			var items = BX.CrmCompanyView.messages;
			return BX.type.isNotEmptyString(items[name]) ? items[name] : '';
		},
		getEntityId: function()
		{
			return parseInt(this.getSetting('entityId', 0));
		},
		prepareElementId: function(name)
		{
			name = name.toLowerCase();
			return this._prefix !== ''
					? (this._prefix + '_' + name) : name;
		},
		resolveElement: function(name)
		{
			return BX(this.prepareElementId(name));
		},
		reloadAsync: function()
		{
			window.setTimeout(
				function()
				{
					var context = BX.CrmMobileContext.getCurrent();
					context.showPopupLoader();
					context.reload();
					context.hidePopupLoader();
				},
				0
			);
		},
		_onCreateInvoice: function()
		{
			var url = this.getSetting('invoiceEditUrl', '');
			if(url === '')
			{
				return;
			}

			BX.CrmMobileContext.getCurrent().open({ url: url, cache: false });
		},
		_onEdit: function()
		{
			var url = this.getSetting('editUrl', '');
			if(url === '')
			{
				return;
			}

			//url += '&t=' + new Date().getTime();
			BX.CrmMobileContext.getCurrent().open({ url: url, cache: false });
		},
		_onDelete: function()
		{
			BX.CrmMobileContext.getCurrent().confirm(
				this.getMessage("deletionTitle"),
				this.getMessage("deletionConfirmation"),
				["OK", BX.message["JS_CORE_WINDOW_CANCEL"]],
				BX.delegate(
					function(btn){ if(btn === 1) this._dispatcher.deleteEntity(this.getEntityId()); },
					this
				)
			);
		},
		_onMeetingAdd: function(e)
		{
			var url = this.getSetting('meetingEditUrl', '');
			if(url !== '')
			{
				BX.CrmMobileContext.getCurrent().open({ url: url, cache: false });
			}

			BX.PreventDefault(e);
		},
		_onCallAdd: function(e)
		{
			if(this._enableAddCall)
			{
				var url = this.getSetting('callEditUrl', '');
				if(url !== '')
				{
					BX.CrmMobileContext.getCurrent().open({ url: url, cache: false });
				}
			}
			BX.PreventDefault(e);
		},
		_onEmailAdd: function(e)
		{
			if(this._enableAddEmail)
			{
				var url = this.getSetting('emailEditUrl', '');
				if(url !== '')
				{
					BX.CrmMobileContext.getCurrent().open({ url: url, cache: false });
				}
			}
			BX.PreventDefault(e);
		},
		_onExternalCreate: function(eventArgs)
		{
			var data = eventArgs['data'];
			var typeName = typeof(eventArgs['typeName']) !== 'undefined' ? eventArgs['typeName'] : '';
			if((typeName === BX.CrmActivityModel.typeName && BX.CrmActivityModel.checkBindings(BX.CrmCompanyModel.typeName, this.getEntityId(), data))
				|| (typeName === BX.CrmDealModel.typeName && BX.CrmDealModel.checkCompany(this.getEntityId(), data)))
			{
				this._isDirty = true;
			}
		},
		_onExternalUpdate: function(eventArgs)
		{
			var data = eventArgs['data'];
			var typeName = typeof(eventArgs['typeName']) !== 'undefined' ? eventArgs['typeName'] : '';
			var id = typeof(eventArgs['id']) !== 'undefined' ? parseInt(eventArgs['id']) : 0;

			if((typeName === BX.CrmCompanyModel.typeName && id === this.getEntityId())
				|| (typeName === BX.CrmActivityModel.typeName && BX.CrmActivityModel.checkBindings(BX.CrmCompanyModel.typeName, this.getEntityId(), data))
				|| (typeName === BX.CrmDealModel.typeName && BX.CrmDealModel.checkCompany(this.getEntityId(), data)))
			{
				this._isDirty = true;
			}
		},
		_onExternalDelete: function(eventArgs)
		{
			var data = eventArgs['data'];
			var typeName = typeof(eventArgs['typeName']) !== 'undefined' ? eventArgs['typeName'] : '';
			var id = typeof(eventArgs['id']) !== 'undefined' ? parseInt(eventArgs['id']) : 0;

			if(typeName === BX.CrmCompanyModel.typeName && id === this.getEntityId())
			{
				BX.CrmMobileContext.getCurrent().close();
			}
			else if((typeName === BX.CrmActivityModel.typeName && BX.CrmActivityModel.checkBindings(BX.CrmCompanyModel.typeName, this.getEntityId(), data))
				|| (typeName === BX.CrmDealModel.typeName && BX.CrmDealModel.checkCompany(this.getEntityId(), data)))
			{
				this._isDirty = true;
			}
		},
		_onAfterPageOpen: function()
		{
			if(this._isDirty)
			{
				this._isDirty = false;
				this.reloadAsync();
			}
		}
	};

	BX.CrmCompanyView.items = {};
	BX.CrmCompanyView.create = function(id, settings)
	{
		var self = new BX.CrmCompanyView();
		self.initialize(id, settings);
		this.items[id] = self;
		return self;
	};
}
