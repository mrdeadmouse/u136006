if(typeof(BX.CrmInvoiceView) === 'undefined')
{
	BX.CrmInvoiceView = function()
	{
		this._id = '';
		this._settings = {};
		this._dispatcher = null;
		this._model = null;
		this._statusId = this._statusName = null;
		this._isDirty = false;
		this._prefix = '';
		this._invoiceStatusProgressBar = null;
		this._isInInvoiceStatusChangeMode = false;
		this._invoiceStatusChangeCompleteHandler = BX.delegate(this._onExternalInvoiceStatusChange, this);
		this._clientEmailComm = null;
		this._syncData = {};
	};

	if(typeof(BX.CrmInvoiceView.messages) === 'undefined')
	{
		BX.CrmInvoiceView.messages = {};
	}

	BX.CrmInvoiceView.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};
			this._dispatcher = this.getSetting('dispatcher', null);
			if(!this._dispatcher)
			{
				throw 'BX.CrmInvoiceView. Could not find dispatcher.';
			}

			this._model = this._dispatcher.getModelById(this.getEntityId());
			if(!this._model)
			{
				throw 'BX.CrmInvoiceView. Could not find model.';
			}

			this._prefix = this.getSetting('prefix');

			var context = BX.CrmMobileContext.getCurrent();

			var canEdit = this.canEdit();
			var canDelete = this.canDelete();

			this._clientEmailComm = this.getSetting('clientEmailComm', null);
			var addEmailButton = this.resolveElement('add_email_btn');

			if(canEdit)
			{
				if(addEmailButton)
				{
					BX.bind(addEmailButton, "click", BX.delegate(this._onEmailAdd, this));
					if(!this._clientEmailComm && !BX.hasClass(addEmailButton, "disabled"))
					{
						BX.addClass(addEmailButton, "disabled");
					}
					if(this._clientEmailComm && BX.hasClass(addEmailButton, "disabled"))
					{
						BX.removeClass(addEmailButton, "disabled");
					}
				}

				this._statusId = this.resolveElement("status_id");
				this._statusName = this.resolveElement("status_name");
				var statusContainer = this.resolveElement("status_container");
				if(statusContainer)
				{
					BX.bind(BX.findParent(statusContainer,{ className: "crm_order_status" }), "click", BX.delegate(this._onStatusClick, this));

					var entityId = this.getEntityId();
					this._invoiceStatusProgressBar = BX.CrmProgressBar.create(
						"INVOICE_" + entityId,
						{
							entityType: "INVOICE",
							entityId: entityId,
							currentStepId: this.getFieldValue("STATUS_ID"),
							container: statusContainer,
							isEditable: true
						}
					);

					BX.addCustomEvent(this._invoiceStatusProgressBar, "onStepChange", BX.delegate(this._onInternalInvoiceStatusChange, this));
					BX.addCustomEvent(this._invoiceStatusProgressBar, "onStepSelectPageRequest", BX.delegate(this._onInvoiceStatusPageRequest, this));
				}
			}
			else
			{
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
			var items = BX.CrmInvoiceView.messages;
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
		_onEmailAdd: function(e)
		{
			if(!this._clientEmailComm || this.getSetting('emailEditUrl', '') === '')
			{
				BX.CrmMobileContext.getCurrent().alert(this.getMessage("sendEmailTitle"), this.getMessage("clientEmailNotFound"));
				return BX.PreventDefault(e);
			}

			BX.CrmMobileContext.getCurrent().showPopupLoader();
			BX.ajax(
				{
					url: this.getSetting("serviceUrl", ""),
					method: "POST",
					dataType: "json",
					data:
					{
						ACTION : "PREPARE_PDF",
						SOURCE_DATA:
						{
							ID: this.getEntityId()
						}
					},
					onsuccess: BX.delegate(this._onPreparePdfSuccess, this),
					onfailure: BX.delegate(this._onPreparePdfFailure, this)
				}
			);
			return BX.PreventDefault(e);
		},
		_onPreparePdfSuccess: function(data)
		{
			BX.CrmMobileContext.getCurrent().hidePopupLoader();

			var url = this.getSetting('emailEditUrl', '');
			if(!this._clientEmailComm || url === '')
			{
				return;
			}

			var resultData = typeof(data["RESULT_DATA"]) !== "undefined" ? data["RESULT_DATA"] : null;
			if(!resultData)
			{
				if(BX.type.isNotEmptyString(data["ERROR"]))
				{
					BX.CrmMobileContext.getCurrent().alert(this.getMessage("sendEmailTitle"), data["ERROR"]);
				}
				return;
			}

			var elementInfo = typeof(resultData["ELEMENT_INFO"]) !== "undefined" ? resultData["ELEMENT_INFO"] : null;
			if(!elementInfo)
			{
				return;
			}

			var emailEditorSettings =
				{
					contextId: this.getContextId(),
					timestamp: (new Date()).getTime(),
					subject: this.getSetting("emailSubject", ""),
					//description: "",
					communication: this._clientEmailComm,
					storageTypeId: BX.CrmActivityStorageType.disk,
					storageElements:
						[
							{
								id: elementInfo["ID"],
								name: elementInfo["NAME"],
								url: elementInfo["VIEW_URL"]
							}
						]
				};

			BX.CrmMobileContext.getCurrent().open(
				{
					url: url,
					cache: false,
					data: emailEditorSettings
				}
			);
		},
		_onPreparePdfFailure: function(data)
		{
			BX.CrmMobileContext.getCurrent().hidePopupLoader();

			if(BX.type.isNotEmptyString(data["ERROR"]))
			{
				BX.CrmMobileContext.getCurrent().alert(this.getMessage("sendEmailTitle"), data["ERROR"]);
			}
		},
		_synchronize: function()
		{
			if(!this._syncData)
			{
				return;
			}

			var data, id, name;
			if(typeof(this._syncData["INVOICE_STATUS"]) !== "undefined")
			{
				data = this._syncData["INVOICE_STATUS"];
				id = BX.type.isNotEmptyString(data["id"]) ? data["id"] : "";

				if(this._statusId)
				{
					this._statusId.value = id;
				}

				name = BX.type.isNotEmptyString(data["name"]) ? data["name"] : "";
				if(this._statusName)
				{
					this._statusName.innerHTML = BX.util.htmlspecialchars(name !== '' ? name : id);
				}

				if(this._invoiceStatusProgressBar)
				{
					this._invoiceStatusProgressBar.setCurrentStepId(id, false);
				}
				delete this._syncData["INVOICE_STATUS"];

				this._saveStatus(typeof(data["additionalData"]) !== "undefined" ? data["additionalData"] : {});
			}
		},
		_enableInvoiceStatusChangeMode: function(enable)
		{
			enable = !!enable;
			if(this._isInInvoiceStatusChangeMode === enable)
			{
				return;
			}

			this._isInInvoiceStatusChangeMode = enable;

			if(enable)
			{
				BX.addCustomEvent(
					window,
					"onCrmProgressStepSelect",
					this._invoiceStatusChangeCompleteHandler
				);
			}
			else
			{
				BX.removeCustomEvent(
					window,
					"onCrmProgressStepSelect",
					this._invoiceStatusChangeCompleteHandler
				);
			}
		},
		_onStatusClick: function(e)
		{
			this._onInvoiceStatusPageRequest();
			BX.eventCancelBubble(e);
		},
		_onInvoiceStatusPageRequest: function()
		{
			var url = this.getSetting("invoiceStatusSelectorUrl", "");
			if(url !== '')
			{
				var m = this._model;
				BX.CrmMobileContext.getCurrent().open(
					{
						url: url,
						data:
							{
								contextId: this.getContextId(),
								currentStepId: this.getFieldValue("STATUS_ID"),
								modelData:
									{
										PAYMENT_TIME_STAMP: m.getIntParam("PAYMENT_TIME_STAMP"),
										PAYMENT_DATE: m.getStringParam("PAYMENT_DATE"),
										PAYMENT_DOC: m.getStringParam("PAYMENT_DOC"),
										PAYMENT_COMMENT: m.getStringParam("PAYMENT_COMMENT"),
										CANCEL_TIME_STAMP: m.getIntParam("CANCEL_TIME_STAMP"),
										CANCEL_DATE: m.getStringParam("CANCEL_DATE"),
										CANCEL_REASON: m.getStringParam("CANCEL_REASON")
									}
							}
					}
				);
				this._enableInvoiceStatusChangeMode(true);
			}
		},
		_onInternalInvoiceStatusChange: function(eventArgs)
		{
			var id = BX.type.isNotEmptyString(eventArgs["stepId"]) ? eventArgs["stepId"] : "";
			var name = BX.type.isNotEmptyString(eventArgs["stepName"]) ? eventArgs["stepName"] : "";

			if(this._statusId)
			{
				this._statusId.value = id;
			}

			if(this._statusName)
			{
				this._statusName.innerHTML = BX.util.htmlspecialchars(name !== "" ? name : id);
			}

			this._saveStatus({});
		},
		_onExternalInvoiceStatusChange: function(eventArgs)
		{
			var contextId = BX.type.isNotEmptyString(eventArgs["contextId"]) ? eventArgs["contextId"] : "";
			if(contextId !== this.getContextId())
			{
				return;
			}

			this._syncData["INVOICE_STATUS"] =
				{
					id: BX.type.isNotEmptyString(eventArgs["statusId"]) ? eventArgs["statusId"] : "",
					name: BX.type.isNotEmptyString(eventArgs["name"]) ? eventArgs["name"] : "",
					additionalData: typeof(eventArgs["additionalData"]) !== "undefined" ? eventArgs["additionalData"] : {}
				};

			this._enableInvoiceStatusChangeMode(false);
		},
		/*_onExternalCreate: function(eventArgs)
		{
			var typeName = typeof(eventArgs['typeName']) !== 'undefined' ? eventArgs['typeName'] : '';
			if(typeName === 'ACTIVITY' && BX.CrmActivityModel.checkBindings(BX.CrmInvoiceModel.typeName, this.getEntityId(), eventArgs['data']))
			{
				this._isDirty = true;
			}
		},*/
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

			/*if((typeName === BX.CrmInvoiceModel.typeName && id === this.getEntityId())
				|| typeName === 'ACTIVITY' && BX.CrmActivityModel.checkBindings(BX.CrmInvoiceModel.typeName, this.getEntityId(), eventArgs['data']))*/
			if(typeName === BX.CrmInvoiceModel.typeName && id === this.getEntityId())
			{
				this._isDirty = true;
			}
		},
		_onExternalDelete: function(eventArgs)
		{
			var typeName = typeof(eventArgs['typeName']) !== 'undefined' ? eventArgs['typeName'] : '';
			var id = typeof(eventArgs['id']) !== 'undefined' ? parseInt(eventArgs['id']) : 0;

			if(typeName === BX.CrmInvoiceModel.typeName && id === this.getEntityId())
			{
				BX.CrmMobileContext.getCurrent().close();
			}
			/*else if(typeName === 'ACTIVITY' && BX.CrmActivityModel.checkBindings(BX.CrmInvoiceModel.typeName, this.getEntityId(), eventArgs['data']))
			{
				this._isDirty = true;
			}*/
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
		_saveStatus: function(additionalData)
		{
			this._dispatcher.execUpdateAction(
				"set_status",
				{
					"ID": this.getEntityId(),
					"STATUS_ID": this.getFieldValue("STATUS_ID"),
					"ADDITIONAL_DATA": additionalData
				},
				BX.delegate(this.reloadAsync, this),
				{ contextId: this.getContextId() }
			);
		}
	};

	BX.CrmInvoiceView.items = {};
	BX.CrmInvoiceView.create = function(id, settings)
	{
		var self = new BX.CrmInvoiceView();
		self.initialize(id, settings);
		this.items[id] = self;
		return self;
	};
}
