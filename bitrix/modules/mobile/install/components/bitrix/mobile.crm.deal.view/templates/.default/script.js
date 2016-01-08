if(typeof(BX.CrmDealView) === 'undefined')
{
	BX.CrmDealView = function()
	{
		this._id = '';
		this._settings = {};
		this._dispatcher = null;
		this._stageId = this._stageName = null;
		this._isDirty = false;
		this._prefix = '';
		this._enableAddCall = this._enableAddEmail = true;
		this._dealStageProgressBar = null;
		this._isInDealStageChangeMode = false;
		this._dealStageChangeCompleteHandler = BX.delegate(this._onExternalDealStageChange, this);
		this._syncData = {};
	};

	if(typeof(BX.CrmDealView.messages) === 'undefined')
	{
		BX.CrmDealView.messages = {};
	}

	BX.CrmDealView.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};
			this._dispatcher = this.getSetting('dispatcher', null);
			this._prefix = this.getSetting('prefix');

			var context = BX.CrmMobileContext.getCurrent();

			var canEdit = this.canEdit();
			var canDelete = this.canDelete();

			var addMeetingButton = this.resolveElement('add_meeting_btn');
			this._enableAddCall = this.getSetting('enableAddCall', true);
			var addCallButton = this.resolveElement('add_call_btn');
			this._enableAddEmail = this.getSetting('enableAddEmail', true);
			var addEmailButton = this.resolveElement('add_email_btn');

			if(canEdit)
			{
				if(addMeetingButton)
				{
					BX.bind(addMeetingButton, "click", BX.delegate(this._onMeetingAdd, this));
				}

				if(addCallButton)
				{
					BX.bind(addCallButton, "click", BX.delegate(this._onCallAdd, this));
					if(!this._enableAddCall)
					{
						BX.addClass(addCallButton, "disabled");
					}
				}

				if(addEmailButton)
				{
					BX.bind(addEmailButton, "click", BX.delegate(this._onEmailAdd, this));
					if(!this._enableAddEmail)
					{
						BX.addClass(addEmailButton, "disabled");
					}
				}

				this._stageId = this.resolveElement("stage_id");
				this._stageName = this.resolveElement("stage_name");
				var stageContainer = this.resolveElement("stage_container");
				if(stageContainer)
				{
					BX.bind(BX.findParent(stageContainer,{ className: "crm_order_status" }), "click", BX.delegate(this._onStageClick, this));

					var entityId = this.getEntityId();
					this._dealStageProgressBar = BX.CrmProgressBar.create(
						"DEAL_" + entityId,
						{
							entityType: "DEAL",
							entityId: entityId,
							currentStepId: this.getFieldValue("STAGE_ID"),
							container: stageContainer,
							isEditable: true
						}
					);

					BX.addCustomEvent(this._dealStageProgressBar, "onStepChange", BX.delegate(this._onInternalDealStageChange, this));
					BX.addCustomEvent(this._dealStageProgressBar, "onStepSelectPageRequest", BX.delegate(this._onDealStagePageRequest, this));
				}
			}
			else
			{
				if(addMeetingButton)
				{
					BX.addClass(addMeetingButton, "disabled");
				}

				if(addCallButton)
				{
					BX.addClass(addCallButton, "disabled");
				}

				if(addEmailButton)
				{
					BX.addClass(addEmailButton, "disabled");
				}
			}

			if(canEdit || canDelete)
			{
				var menuItems = [];
				if(canEdit)
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
				if(canDelete)
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
		canEdit: function()
		{
			var permissions = this.getSetting('permissions', {});
			return BX.type.isBoolean(permissions['EDIT']) && permissions['EDIT'];
		},
		canDelete: function()
		{
			var permissions = this.getSetting('permissions', {});
			return BX.type.isBoolean(permissions['DELETE']) && permissions['DELETE'];
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
			var items = BX.CrmDealView.messages;
			return BX.type.isNotEmptyString(items[name]) ? items[name] : '';
		},
		getEntityId: function()
		{
			return parseInt(this.getSetting('entityId', 0));
		},
		getContextId: function()
		{
			return this.getSetting("contextId", "");
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
		getFieldValue: function(fieldName)
		{
			var elem = this.resolveElement(fieldName);
			return elem ? elem.value : '';
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
			if(url !== '')
			{
				BX.CrmMobileContext.getCurrent().open({ url: url, cache: false });
			}
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
		_synchronize: function()
		{
			if(!this._syncData)
			{
				return;
			}

			var data, id, name;
			if(typeof(this._syncData["DEAL_STAGE"]) !== "undefined")
			{
				data = this._syncData["DEAL_STAGE"];
				id = BX.type.isNotEmptyString(data["id"]) ? data["id"] : "";

				if(this._stageId)
				{
					this._stageId.value = id;
				}

				name = BX.type.isNotEmptyString(data["name"]) ? data["name"] : "";
				if(this._stageName)
				{
					this._stageName.innerHTML = BX.util.htmlspecialchars(name !== '' ? name : id);
				}

				if(this._dealStageProgressBar)
				{
					this._dealStageProgressBar.setCurrentStepId(id, false);
				}
				delete this._syncData["DEAL_STAGE"];

				this._saveStage();
			}
		},
		_enableDealStageChangeMode: function(enable)
		{
			enable = !!enable;
			if(this._isInDealStageChangeMode === enable)
			{
				return;
			}

			this._isInDealStageChangeMode = enable;

			if(enable)
			{
				BX.addCustomEvent(
					window,
					"onCrmProgressStepSelect",
					this._dealStageChangeCompleteHandler
				);
			}
			else
			{
				BX.removeCustomEvent(
					window,
					"onCrmProgressStepSelect",
					this._dealStageChangeCompleteHandler
				);
			}
		},
		_onStageClick: function(e)
		{
			this._onDealStagePageRequest();
			BX.eventCancelBubble(e);
		},
		_onDealStagePageRequest: function()
		{
			var url = this.getSetting("dealStageSelectorUrl", "");
			if(url !== '')
			{
				BX.CrmMobileContext.getCurrent().open(
					{
						url: url,
						data:
							{
								contextId: this.getContextId(),
								currentStepId: this.getFieldValue("STAGE_ID")
							}
					}
				);
				this._enableDealStageChangeMode(true);
			}
		},
		_onInternalDealStageChange: function(eventArgs)
		{
			var id = BX.type.isNotEmptyString(eventArgs["stepId"]) ? eventArgs["stepId"] : "";
			var name = BX.type.isNotEmptyString(eventArgs["stepName"]) ? eventArgs["stepName"] : "";

			if(this._stageId)
			{
				this._stageId.value = id;
			}

			if(this._stageName)
			{
				this._stageName.innerHTML = BX.util.htmlspecialchars(name !== "" ? name : id);
			}

			this._saveStage();
		},
		_onExternalDealStageChange: function(eventArgs)
		{
			var contextId = BX.type.isNotEmptyString(eventArgs["contextId"]) ? eventArgs["contextId"] : "";
			if(contextId !== this.getContextId())
			{
				return;
			}

			this._syncData["DEAL_STAGE"] =
				{
					id: BX.type.isNotEmptyString(eventArgs["statusId"]) ? eventArgs["statusId"] : "",
					name: BX.type.isNotEmptyString(eventArgs["name"]) ? eventArgs["name"] : ""
				};

			this._enableDealStageChangeMode(false);
		},
		_onExternalCreate: function(eventArgs)
		{
			var typeName = typeof(eventArgs['typeName']) !== 'undefined' ? eventArgs['typeName'] : '';
			if(typeName === 'ACTIVITY' && BX.CrmActivityModel.checkBindings(BX.CrmDealModel.typeName, this.getEntityId(), eventArgs['data']))
			{
				this._isDirty = true;
			}
		},
		_onExternalUpdate: function(eventArgs)
		{
			var contextId = typeof(eventArgs['contextId']) !== 'undefined' ? eventArgs['contextId'] : '';
			if(contextId !== '' && contextId === this.getContextId())
			{
				//Skip this update. It has been initiated myself
				return;
			}

			var typeName = typeof(eventArgs['typeName']) !== 'undefined' ? eventArgs['typeName'] : '';
			var id = typeof(eventArgs['id']) !== 'undefined' ? parseInt(eventArgs['id']) : 0;

			if((typeName === BX.CrmDealModel.typeName && id === this.getEntityId())
				|| typeName === 'ACTIVITY' && BX.CrmActivityModel.checkBindings(BX.CrmDealModel.typeName, this.getEntityId(), eventArgs['data']))
			{
				this._isDirty = true;
			}
		},
		_onExternalDelete: function(eventArgs)
		{
			var typeName = typeof(eventArgs['typeName']) !== 'undefined' ? eventArgs['typeName'] : '';
			var id = typeof(eventArgs['id']) !== 'undefined' ? parseInt(eventArgs['id']) : 0;

			if(typeName === BX.CrmDealModel.typeName && id === this.getEntityId())
			{
				BX.CrmMobileContext.getCurrent().close();
			}
			else if(typeName === 'ACTIVITY' && BX.CrmActivityModel.checkBindings(BX.CrmDealModel.typeName, this.getEntityId(), eventArgs['data']))
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
			else
			{
				this._synchronize();
			}
		},
		_saveStage: function()
		{
			this._dispatcher.execUpdateAction(
				'set_stage',
				{ 'ID': this.getEntityId(), 'STAGE_ID': this.getFieldValue("STAGE_ID") },
				null,
				{ contextId: this.getContextId() }
			);
		}
	};

	BX.CrmDealView.items = {};
	BX.CrmDealView.create = function(id, settings)
	{
		var self = new BX.CrmDealView();
		self.initialize(id, settings);
		this.items[id] = self;
		return self;
	};
}
