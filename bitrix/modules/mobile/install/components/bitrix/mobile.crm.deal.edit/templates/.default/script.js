if(typeof(BX.CrmDealEditor) === "undefined")
{
	BX.CrmDealEditor = function()
	{
		this._id = '';
		this._settings = {};
		this._prefix = '';
		this._contactId = this._companyId = this._clientCaption = this._clientLegend = this._typeId = this._typeName = this._currencyId = this._currencyName = this._stageId = this._stageName = null;
		this._assignedById = this._assignedByName = null;
		this._dealStageProgressBar = null;
		this._dispatcher = null;
		this._isDirty = false;
		this._contextMenuId = '';
		this._productRowList = null;

		this._productRowListChangeHandler = BX.delegate(this._onProductRowListChange, this);
		this._dealStageChangeCompleteHandler = BX.delegate(this._onExternalDealStageChange, this);
		this._currencyChangeCompleteHandler = BX.delegate(this._onExternalCurrencyChange, this);
		this._clientChangeCompleteHandler = BX.delegate(this._onExternalClientChange, this);
		this._typeChangeCompleteHandler = BX.delegate(this._onExternalTypeChange, this);

		this._isInTypeChangeMode = false;
		this._isInDealStageChangeMode = false;
		this._isInCurrencyChangeMode = false;
		this._isInClientChangeMode = false;
		this._syncData = {};
	};

	BX.CrmDealEditor.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};
			this._prefix = this.getSetting('prefix');
			this._dispatcher = this.getSetting('dispatcher', null);

			this._contactId = this.resolveElement('contact_id');
			this._companyId = this.resolveElement('company_id');
			if(this._contactId || this._companyId)
			{
				BX.bind(
					BX.findParent(this._contactId ? this._contactId : this._companyId,{ className: 'crm_block_container' }),
					'click',
					BX.delegate(this._onClientSelect, this)
				);
			}
			this._clientCaption = this.resolveElement('client_caption');
			this._clientLegend = this.resolveElement('client_legend');

			this._currencyId = this.resolveElement('currency_id');
			if(this._currencyId)
			{
				BX.bind(
					BX.findParent(this._currencyId,{ className: 'crm_block_container' }),
					'click',
					BX.delegate(this._onCurrencySelect, this)
				);
			}
			this._currencyName = this.resolveElement('currency_name');

			this._typeId = this.resolveElement('type_id');
			if(this._typeId)
			{
				BX.bind(
					BX.findParent(this._typeId, { className: 'crm_block_container' }),
					'click',
					BX.delegate(this._onTypeSelect, this)
				);
			}
			this._typeName = this.resolveElement('type_name');

			this._assignedById = this.resolveElement('assigned_by_id');
			if(this._assignedById)
			{
				BX.bind(BX.findParent(
					this._assignedById,
					{ className: 'crm_block_container' }), 'click', BX.delegate(this._onResponsibleSelect, this)
				);
			}
			this._assignedByName = this.resolveElement('assigned_by_name');

			this._stageId = this.resolveElement("stage_id");
			this._stageName = this.resolveElement("stage_name");
			var stageContainer = this.resolveElement("stage_container");
			if(stageContainer)
			{
				BX.bind(BX.findParent(stageContainer,{ className: "crm_meeting_info" }), "click", BX.delegate(this._onStageClick, this));
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
				'onOpenPageBefore',
				BX.delegate(this._onBeforePageOpen, this)
			);

			BX.addCustomEvent(
				window,
				'onOpenPageAfter',
				BX.delegate(this._onAfterPageOpen, this)
			);

			//TEST ONLY -->
			/*var saveBtn = BX('save');
			if(saveBtn)
			{
				BX.bind(saveBtn, 'click', BX.delegate(this._onSave, this));
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
		getFieldValue: function(fieldName)
		{
			var elem = this.resolveElement(fieldName);
			return elem ? elem.value : '';
		},
		createSaveHandler: function()
		{
			return BX.delegate(this._onSave, this);
		},
		getMessage: function(name)
		{
			var messages = BX.CrmDealEditor.messages;
			return BX.type.isNotEmptyString(messages[name]) ? messages[name] : '';
		},
		getContextId: function()
		{
			return this.getSetting("contextId", "");
		},
		getCurrencyId: function()
		{
			return this.getFieldValue("CURRENCY_ID");
		},
		setProductRowList: function(productRowList, freezeOpportunity)
		{
			if(this._productRowList === productRowList)
			{
				return;
			}

			freezeOpportunity = !!freezeOpportunity;

			if(this._productRowList)
			{
				BX.removeCustomEvent(
					this._productRowList,
					"onCrmProductRowListChange",
					this._productRowListChangeHandler
				);
			}

			this._productRowList = productRowList;

			if(this._productRowList)
			{
				BX.addCustomEvent(
					this._productRowList,
					"onCrmProductRowListChange",
					this._productRowListChangeHandler
				);
			}

			var opportunity = this.resolveElement("opportunity");
			if(opportunity)
			{
				if(!this._productRowList)
				{
					opportunity.disabled = false;
				}
				else
				{
					var hasProductRows = this._productRowList.getItemCount() > 0;
					opportunity.disabled = hasProductRows;
					if(!freezeOpportunity)
					{
						opportunity.value = hasProductRows ? this._productRowList.getSumTotal() : 0.00;
					}
				}
			}
		},
		_onCurrencySelect: function(e)
		{
			var url = this.getSetting('currencySelectorUrl', '');
			if(url !== '')
			{
				BX.CrmMobileContext.getCurrent().open(
					{
						url: url,
						data: { contextId: this.getContextId() }
					}
				);
				this._enableCurrencyChangeMode(true);
			}
		},
		_onClientSelect: function(e)
		{
			var url = this.getSetting('clientSelectorUrl', '');
			if(url !== '')
			{
				BX.CrmMobileContext.getCurrent().open(
					{
						url: url,
						data: { contextId: this.getContextId() }
					}
				);
				this._enableClientChangeMode(true);
			}
		},
		_onTypeSelect: function(e)
		{
			var url = this.getSetting('typeSelectorUrl', '');
			if(url !== '')
			{
				BX.CrmMobileContext.getCurrent().open({ url: url });
				this._enableTypeChangeMode(true);
			}
		},
		_onResponsibleSelect: function()
		{
			BX.CrmMobileContext.getCurrent().openUserSelector(
				{
					callback: BX.delegate(this._onResponsibleChange, this),
					multiple: false,
					okButtonTitle: this.getMessage('userSelectorOkButton'),
					cancelButtonTitle: this.getMessage('userSelectorCancelButton')
				}
			);
		},
		_onResponsibleChange: function(data)
		{
			var userId = 0;
			var userName = '';

			if(data && data['a_users'])
			{
				var users = data['a_users'];
				for (var key in users)
				{
					if(!users.hasOwnProperty(key))
					{
						continue;
					}

					var user = users[key];
					userId = parseInt(user['ID']);
					userName = user['NAME'];
					break;
				}
			}

			if(this._assignedById)
			{
				this._assignedById.value = userId;
			}

			if(this._assignedByName)
			{
				this._assignedByName.innerHTML = BX.util.htmlspecialchars(userName);
			}
		},
		_onSave: function()
		{
			if(!this._dispatcher)
			{
				return;
			}

			var entityId = this.getEntityId();
			var data =
			{
				'ID': this.getEntityId(),
				'TITLE': this.getFieldValue('TITLE'),
				'CURRENCY_ID': this.getFieldValue('CURRENCY_ID'),
				'OPPORTUNITY': this.getFieldValue('OPPORTUNITY'),
				'PROBABILITY': this.getFieldValue('PROBABILITY'),
				'STAGE_ID': this.getFieldValue('STAGE_ID'),
				'CONTACT_ID': this.getFieldValue('CONTACT_ID'),
				'COMPANY_ID': this.getFieldValue('COMPANY_ID'),
				'COMMENTS': this.getFieldValue('COMMENTS'),
				'TYPE_ID': this.getFieldValue('TYPE_ID'),
				'ASSIGNED_BY_ID': this.getFieldValue('ASSIGNED_BY_ID')
			};

			data['PROCESS_PRODUCT_ROWS'] = 'Y';
			data['PRODUCT_ROWS'] = this._productRowList ? this._productRowList.prepareForSave() : [];

			if(entityId <= 0)
			{
				this._dispatcher.createEntity(
					data,
					BX.CrmMobileContext.getCurrent().createCloseHandler(),
					{
						contextId: this.getContextId(),
						title: this.getSetting('title', '')
					}
				);
			}
			else
			{
				this._dispatcher.updateEntity(
					data,
					BX.CrmMobileContext.getCurrent().createCloseHandler(),
					{
						contextId: this.getContextId(),
						title: this.getSetting('title', '')
					}
				);
			}
		},
		_enableCurrencyChangeMode: function(enable)
		{
			enable = !!enable;
			if(this._isInCurrencyChangeMode === enable)
			{
				return;
			}

			this._isInCurrencyChangeMode = enable;

			if(enable)
			{
				BX.addCustomEvent(
					window,
					'onCrmCurrencySelect',
					this._currencyChangeCompleteHandler
				);
			}
			else
			{
				BX.removeCustomEvent(
					window,
					'onCrmCurrencySelect',
					this._currencyChangeCompleteHandler
				);
			}
		},
		_enableClientChangeMode: function(enable)
		{
			enable = !!enable;
			if(this._isInClientChangeMode === enable)
			{
				return;
			}

			this._isInClientChangeMode = enable;

			if(enable)
			{
				BX.addCustomEvent(
					window,
					'onCrmClientSelect',
					this._clientChangeCompleteHandler
				);
			}
			else
			{
				BX.removeCustomEvent(
					window,
					'onCrmClientSelect',
					this._clientChangeCompleteHandler
				);
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
		_enableTypeChangeMode: function(enable)
		{
			enable = !!enable;
			if(this._isInTypeChangeMode === enable)
			{
				return;
			}

			this._isInTypeChangeMode = enable;

			if(enable)
			{
				BX.addCustomEvent(
					window,
					'onCrmStatusSelect',
					this._typeChangeCompleteHandler
				);
			}
			else
			{
				BX.removeCustomEvent(
					window,
					'onCrmStatusSelect',
					this._typeChangeCompleteHandler
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
		},
		_onExternalUpdate: function(eventArgs)
		{
			var typeName = typeof(eventArgs['typeName']) !== 'undefined' ? eventArgs['typeName'] : '';
			var id = typeof(eventArgs['id']) !== 'undefined' ? parseInt(eventArgs['id']) : 0;
			var senderId = typeof(eventArgs['senderId']) !== 'undefined' ? eventArgs['senderId'] : '';

			if(typeName === BX.CrmDealModel.typeName && id === this.getEntityId() && senderId !== this._dispatcher.getId())
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
		_onExternalCurrencyChange: function(eventArgs)
		{
			var contextId = typeof(eventArgs['contextId']) ? eventArgs['contextId'] : '';
			if(contextId !== this.getSetting('contextId', ''))
			{
				return;
			}

			var id = typeof(eventArgs['id']) ? eventArgs['id'] : '';
			var name = typeof(eventArgs['name']) ? eventArgs['name'] : '';

			this._syncData["CURRENCY"] =
				{
					id: id,
					name: name
				};

			this._enableCurrencyChangeMode(false);
		},
		_onExternalClientChange: function(eventArgs)
		{
			var contextId = typeof(eventArgs["contextId"]) !== "undefined" ? eventArgs["contextId"] : "";
			if(contextId !== this.getSetting("contextId", ""))
			{
				return;
			}

			if(typeof(this._syncData["CLIENT"]) !== "undefined")
			{
				delete this._syncData["CLIENT"];
			}

			var id = typeof(eventArgs["id"]) !== "undefined" ? eventArgs["id"] : "";
			var caption = typeof(eventArgs["caption"]) !== "undefined" ? eventArgs["caption"] : "";
			var typeName = typeof(eventArgs["typeName"]) !== "undefined" ? eventArgs["typeName"] : "";

			if(typeName === BX.CrmCompanyModel.typeName)
			{
				this._syncData["CLIENT"] = { company: { id: id, title: caption }, contact: { id: 0, fullName: "" } };
			}
			else if(typeName === BX.CrmContactModel.typeName)
			{
				this._syncData["CLIENT"] = { company: { id: 0, title: "" }, contact: { id: id, fullName: caption } };
			}

			var related = typeof(eventArgs["related"]) !== "undefined" ? eventArgs["related"] : null;
			if(related)
			{
				var ralatedId = typeof(related["id"]) !== "undefined" ? related["id"] : "";
				var ralatedCaption = typeof(related["caption"]) !== "undefined" ? related["caption"] : "";
				var relatedTypeName = typeof(related["typeName"]) !== "undefined" ? related["typeName"] : "";

				if(relatedTypeName === BX.CrmCompanyModel.typeName)
				{
					this._syncData["CLIENT"].company = { id: ralatedId, title: ralatedCaption };
				}
				else if(relatedTypeName === BX.CrmContactModel.typeName)
				{
					this._syncData["CLIENT"].contact = { id: ralatedId, fullName: ralatedCaption };
				}
			}

			this._enableClientChangeMode(false);
		},
		_onExternalTypeChange: function(eventArgs)
		{
			var contextId = typeof(eventArgs['contextId']) ? eventArgs['contextId'] : '';
			if(contextId !== this.getSetting('contextId', ''))
			{
				return;
			}

			var statusId = typeof(eventArgs['statusId']) ? eventArgs['statusId'] : '';
			var name = typeof(eventArgs['name']) ? eventArgs['name'] : '';
			this._syncData["TYPE"] =
				{
					id: statusId,
					name: name
				};
			this._enableTypeChangeMode(false);
		},
		_synchronize: function()
		{
			if(!this._syncData)
			{
				return;
			}

			var data, id, name;
			if(typeof(this._syncData["TYPE"]) !== "undefined")
			{
				data = this._syncData["TYPE"];
				id = BX.type.isNotEmptyString(data["id"]) ? data["id"] : "";
				if(this._typeId)
				{
					this._typeId.value = id;
				}

				name = BX.type.isNotEmptyString(data["name"]) ? data["name"] : "";
				if(this._typeName)
				{
					this._typeName.innerHTML = BX.util.htmlspecialchars(name);
				}

				delete this._syncData["TYPE"];
			}
			else if(typeof(this._syncData["CURRENCY"]) !== "undefined")
			{
				data = this._syncData["CURRENCY"];
				id = BX.type.isNotEmptyString(data["id"]) ? data["id"] : "";
				var prevId = "";
				if(this._currencyId)
				{
					prevId = this._currencyId.value;
					this._currencyId.value = id;
				}

				name = BX.type.isNotEmptyString(data["name"]) ? data["name"] : "";
				if(this._currencyName)
				{
					this._currencyName.innerHTML = BX.util.htmlspecialchars(name !== '' ? name : id);
				}

				if(this._productRowList)
				{
					this._productRowList.setCurrencyId(id, BX.delegate(this._synchronizeOpportunity, this));
				}
				else
				{
					this._convertOpportunity(prevId, id);
				}

				delete this._syncData["CURRENCY"];
			}
			else if(typeof(this._syncData["DEAL_STAGE"]) !== "undefined")
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
			}
			else if(typeof(this._syncData["CLIENT"]) !== "undefined")
			{
				data = this._syncData["CLIENT"];

				var companyId = typeof(data.company["id"]) !== "undefined" ? parseInt(data.company["id"]) : 0;
				if(this._companyId)
				{
					this._companyId.value = companyId;
				}

				var contactId = typeof(data.contact["id"]) !== "undefined" ? parseInt(data.contact["id"]) : 0;
				if(this._contactId)
				{
					this._contactId.value = contactId;
				}

				var contactName, companyTitle, clientLegend;
				if(contactId > 0)
				{
					contactName = BX.type.isNotEmptyString(data.contact["fullName"]) ? data.contact["fullName"] : contactId;
					if(this._clientCaption)
					{
						this._clientCaption.innerHTML = contactName !== "" ? BX.util.htmlspecialchars(contactName) : contactId;
					}

					if(this._clientLegend)
					{
						clientLegend = "";
						if(companyId > 0)
						{
							clientLegend = BX.type.isNotEmptyString(data.company["title"]) ? data.company["title"] : companyId;
						}

						if(clientLegend === "")
						{
							this._clientLegend.style.display = "none";
							this._clientLegend.innerHTML = "";
						}
						else
						{
							this._clientLegend.style.display = "";
							this._clientLegend.innerHTML = BX.util.htmlspecialchars(clientLegend);
						}
					}
				}
				else if(companyId > 0)
				{
					companyTitle = BX.type.isNotEmptyString(data.company["title"]) ? data.company["title"] : "";
					if(this._clientCaption)
					{
						this._clientCaption.innerHTML = companyTitle !== "" ? BX.util.htmlspecialchars(companyTitle) : companyId;
					}

					if(this._clientLegend)
					{
							this._clientLegend.style.display = "none";
							this._clientLegend.innerHTML = "";
					}
				}
				else
				{
					if(this._clientCaption)
					{
						this._clientCaption.innerHTML = BX.util.htmlspecialchars(this.getMessage("contactNotSpecified"));
					}

					if(this._clientLegend)
					{
							this._clientLegend.style.display = "none";
							this._clientLegend.innerHTML = "";
					}
				}

				delete this._syncData["CLIENT"];
			}
		},
		_convertOpportunity: function(srcCurrencyId, dstCurrencyId)
		{
			var opportunity = parseFloat(this.getFieldValue('OPPORTUNITY'));
			if(opportunity == 0.0)
			{
				return;
			}

			var self = this;
			BX.ajax(
				{
					url: this.getSetting("serviceUrl", ""),
					method: "POST",
					dataType: "json",
					data:
					{
						"ACTION" : "CONVERT_MONEY",
						"SRC_CURRENCY_ID": srcCurrencyId,
						"DST_CURRENCY_ID": dstCurrencyId,
						"SUM": opportunity
					},
					onsuccess: function(data)
					{
						var opportunity = self.resolveElement("opportunity");
						if(opportunity)
						{
							opportunity.value = typeof(data["SUM"]) !== "undefined" ? data["SUM"] : 0.0;
						}

						if(self._currencyId)
						{
							self._currencyId.value = typeof(data["CURRENCY_ID"]) !== "undefined" ? data["CURRENCY_ID"] : "";
						}

						if(self._currencyName)
						{
							self._currencyName.innerHTML = BX.util.htmlspecialchars(typeof(data["CURRENCY_NAME"]) !== "undefined" ? data["CURRENCY_NAME"] : "");
						}
					},
					onfailure: function(data)
					{
					}
				}
			);
		},
		_synchronizeOpportunity: function()
		{
			var opportunity = this.resolveElement("opportunity");
			if(!opportunity)
			{
				return;
			}

			var list = this._productRowList;
			if(!list)
			{
				opportunity.disabled = false;
			}
			else
			{
				var hasProductRows = list.getItemCount() > 0;
				opportunity.disabled = hasProductRows;
				opportunity.value = hasProductRows ? list.getSumTotal() : 0.00;
			}
		},
		_onBeforePageOpen: function()
		{
			if(this._isDirty)
			{
				this._isDirty = false;
				BX.CrmMobileContext.getCurrent().reload();
			}
		},
		_onAfterPageOpen: function()
		{
			if(this._productRowList && this._productRowList.isInEditMode())
			{
				this._productRowList.cancelEditMode();
			}

			this._synchronize();
		},
		_onProductRowListChange: function(eventArgs)
		{
			this._synchronizeOpportunity();
		}
	};

	if(typeof(BX.CrmDealEditor.messages) === 'undefined')
	{
		BX.CrmDealEditor.messages = {};
	}

	BX.CrmDealEditor.items = {};
	BX.CrmDealEditor.create = function(id, settings)
	{
		var self = new BX.CrmDealEditor();
		self.initialize(id, settings);
		this.items[id] = self;
		return self;
	};
}
