if(typeof(BX.CrmLeadEditor) === "undefined")
{
	BX.CrmLeadEditor = function()
	{
		this._id = '';
		this._settings = {};
		this._prefix = '';
		this._statusId = this._statusName = this._sourceId = this._sourceName = this._currencyId = this._currencyName = null;
		this._assignedById = this._assignedByName = null;
		this._addPhoneBtn = this._addEmailBtn = null;
		this._statusProgressBar = null;
		this._dispatcher = null;
		this._isDirty = false;
		this._contextMenuId = '';
		this._productRowList = null;

		this._productRowListChangeHandler = BX.delegate(this._onProductRowListChange, this);
		this._leadStatusChangeCompleteHandler = BX.delegate(this._onExternalLeadStatusChange, this);
		this._currencyChangeCompleteHandler = BX.delegate(this._onExternalCurrencyChange, this);
		this._statusChangeCompleteHandler = BX.delegate(this._onExternalStatusChange, this);

		this._isInStatusChangeMode = false;
		this._isInLeadStatusChangeMode = false;
		this._isInCurrencyChangeMode = false;
		this._syncData = {};
	};

	BX.CrmLeadEditor.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};
			this._prefix = this.getSetting('prefix');
			this._dispatcher = this.getSetting('dispatcher', null);

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

			this._assignedById = this.resolveElement('assigned_by_id');
			if(this._assignedById)
			{
				BX.bind(BX.findParent(
					this._assignedById,
					{ className: 'crm_block_container' }), 'click', BX.delegate(this._onResponsibleSelect, this)
				);
			}
			this._assignedByName = this.resolveElement('assigned_by_name');

			this._addPhoneBtn = this.resolveElement('phone_add_btn');
			if(this._addPhoneBtn)
			{
				BX.bind(this._addPhoneBtn, 'click', BX.delegate(this._onPhoneAdd, this));
			}

			this._addEmailBtn = this.resolveElement('email_add_btn');
			if(this._addEmailBtn)
			{
				BX.bind(this._addEmailBtn, 'click', BX.delegate(this._onEmailAdd, this));
			}

			this._sourceId = this.resolveElement('source_id');
			if(this._sourceId)
			{
				BX.bind(
					BX.findParent(this._sourceId, { className: 'crm_block_container' }),
					'click',
					BX.delegate(this._onSourceSelect, this)
				);
			}
			this._sourceName = this.resolveElement('source_name');

			this._statusId = this.resolveElement("status_id");
			this._statusName = this.resolveElement("status_name");
			var statusContainer = this.resolveElement("status_container");
			if(statusContainer)
			{
				BX.bind(BX.findParent(statusContainer,{ className: "crm_meeting_info" }), "click", BX.delegate(this._onStatusClick, this));
				var entityId = this.getEntityId();
				this._statusProgressBar = BX.CrmProgressBar.create(
					"LEAD_" + entityId,
					{
						entityType: "LEAD",
						entityId: entityId,
						currentStepId: this.getFieldValue("STATUS_ID"),
						container: statusContainer,
						isEditable: true
					}
				);

				BX.addCustomEvent(this._statusProgressBar, "onStepChange", BX.delegate(this._onInternalLeadStatusChange, this));
				BX.addCustomEvent(this._statusProgressBar, "onStepSelectPageRequest", BX.delegate(this._onLeadStatusPageRequest, this));
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
			var messages = BX.CrmLeadEditor.messages;
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
		_onPhoneAdd: function(e)
		{
			this._createMultiField(
				'PHONE',
				BX.findPreviousSibling(this._addPhoneBtn, { tagName: 'DIV', className: 'clb' })
			);
			return BX.PreventDefault(e);
		},
		_onEmailAdd: function(e)
		{
			this._createMultiField(
				'EMAIL',
				BX.findPreviousSibling(this._addEmailBtn, { tagName: 'DIV', className: 'clb' })
			);
			return BX.PreventDefault(e);
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
		_onSourceSelect: function(e)
		{
			var url = this.getSetting('sourceSelectorUrl', '');
			if(url !== '')
			{
				BX.CrmMobileContext.getCurrent().open({ url: url });
				this._enableStatusChangeMode(true);
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
		_createMultiField: function(typeName, anchor)
		{
			var index = 0;
			var fieldId = '';
			var prefix = typeName + '_';
			do
			{
				index++;
				fieldId = 'n' + index.toString();
			} while(BX.type.isDomNode(this.resolveElement(prefix + fieldId + '_VALUE')));

			var multiFields = this.getSetting('multiFields', {});
			if(typeof(multiFields[typeName]) === 'undefined')
			{
				multiFields[typeName] = {};
			}
			multiFields[typeName][fieldId] = {};

			var input = BX.create(
				'INPUT',
				{
					props:
					{
						id: this.prepareElementId(prefix + fieldId + '_VALUE'),
						type: 'text',
						className: 'crm_input_text fll'
					},
					style: { width: '70%' }
				}
			);
			anchor.parentNode.insertBefore(input, anchor);

			var select = BX.create(
				'SELECT',
				{
					props:
					{
						id: this.prepareElementId(prefix + fieldId + '_VALUE_TYPE'),
						className: 'crm_input_select flr'
					}
				}
			);

			var infos = this.getSetting('multiFieldInfos', {});
			var valueTypeInfos = typeof(infos[typeName]) !== 'undefined' ? infos[typeName] : {};
			for(var valueTypeId in valueTypeInfos)
			{
				if(!valueTypeInfos.hasOwnProperty(valueTypeId))
				{
					continue;
				}

				var valueTypeName = valueTypeInfos[valueTypeId];
				var option = BX.create(
					'OPTION',
					{
						value: valueTypeId,
						text: valueTypeName
					}
				);

				if(!BX.browser.isIE)
				{
					select.add(option,null);
				}
				else
				{
					try
					{
						// for IE earlier than version 8
						select.add(option, select.options[null]);
					}
					catch (e)
					{
						select.add(option,null);
					}
				}

			}
			anchor.parentNode.insertBefore(select, anchor);
		},
		_saveMultiFields: function(typeName, data)
		{
			var multiFields = this.getSetting('multiFields', {});
			if(typeof(multiFields[typeName]) === 'undefined')
			{
				return;
			}

			if(typeof(data['FM']) === 'undefined')
			{
				data['FM'] = {};
			}

			if(typeof(data['FM'][typeName]) === 'undefined')
			{
				data['FM'][typeName] = {};
			}

			var fields = multiFields[typeName];
			for(var key in fields)
			{
				if(!fields.hasOwnProperty(key))
				{
					continue;
				}

				var prefix = typeName + '_' + key;

				var value = this.getFieldValue(prefix + '_value');
				if(value === '')
				{
					continue;
				}

				data['FM'][typeName][key] =
					{
						'VALUE': value,
						'VALUE_TYPE': this.getFieldValue(prefix + '_value_type')
					};
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
				'NAME': this.getFieldValue('NAME'),
				'SECOND_NAME': this.getFieldValue('SECOND_NAME'),
				'LAST_NAME': this.getFieldValue('LAST_NAME'),
				'COMPANY_TITLE': this.getFieldValue('COMPANY_TITLE'),
				'CURRENCY_ID': this.getFieldValue('CURRENCY_ID'),
				'OPPORTUNITY': this.getFieldValue('OPPORTUNITY'),
				'STATUS_ID': this.getFieldValue('STATUS_ID'),
				'ADDRESS': this.getFieldValue('ADDRESS'),
				'ADDRESS_2': this.getFieldValue('ADDRESS_2'),
				'ADDRESS_CITY': this.getFieldValue('ADDRESS_CITY'),
				'ADDRESS_REGION': this.getFieldValue('ADDRESS_REGION'),
				'ADDRESS_PROVINCE': this.getFieldValue('ADDRESS_PROVINCE'),
				'ADDRESS_POSTAL_CODE': this.getFieldValue('ADDRESS_POSTAL_CODE'),
				'ADDRESS_COUNTRY': this.getFieldValue('ADDRESS_COUNTRY'),
				'COMMENTS': this.getFieldValue('COMMENTS'),
				'SOURCE_ID': this.getFieldValue('SOURCE_ID'),
				'ASSIGNED_BY_ID': this.getFieldValue('ASSIGNED_BY_ID')
			};

			data['PROCESS_PRODUCT_ROWS'] = 'Y';
			data['PRODUCT_ROWS'] = this._productRowList ? this._productRowList.prepareForSave() : [];

			this._saveMultiFields('PHONE', data);
			this._saveMultiFields('EMAIL', data);

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
		_enableLeadStatusChangeMode: function(enable)
		{
			enable = !!enable;
			if(this._isInLeadStatusChangeMode === enable)
			{
				return;
			}

			this._isInLeadStatusChangeMode = enable;

			if(enable)
			{
				BX.addCustomEvent(
					window,
					"onCrmProgressStepSelect",
					this._leadStatusChangeCompleteHandler
				);
			}
			else
			{
				BX.removeCustomEvent(
					window,
					"onCrmProgressStepSelect",
					this._leadStatusChangeCompleteHandler
				);
			}
		},
		_enableStatusChangeMode: function(enable)
		{
			enable = !!enable;
			if(this._isInStatusChangeMode === enable)
			{
				return;
			}

			this._isInStatusChangeMode = enable;

			if(enable)
			{
				BX.addCustomEvent(
					window,
					'onCrmStatusSelect',
					this._statusChangeCompleteHandler
				);
			}
			else
			{
				BX.removeCustomEvent(
					window,
					'onCrmStatusSelect',
					this._statusChangeCompleteHandler
				);
			}
		},
		_onStatusClick: function(e)
		{
			this._onLeadStatusPageRequest();
			BX.eventCancelBubble(e);
		},
		_onLeadStatusPageRequest: function()
		{
			var url = this.getSetting("leadStatusSelectorUrl", "");
			if(url !== '')
			{
				BX.CrmMobileContext.getCurrent().open(
					{
						url: url,
						data:
							{
								contextId: this.getContextId(),
								currentStepId: this.getFieldValue("STATUS_ID"),
								disabledStepIds: ["CONVERTED"]
							}
					}
				);
				this._enableLeadStatusChangeMode(true);
			}
		},
		_onInternalLeadStatusChange: function(eventArgs)
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
		},
		_onExternalUpdate: function(eventArgs)
		{
			var typeName = typeof(eventArgs['typeName']) !== 'undefined' ? eventArgs['typeName'] : '';
			var id = typeof(eventArgs['id']) !== 'undefined' ? parseInt(eventArgs['id']) : 0;
			var senderId = typeof(eventArgs['senderId']) !== 'undefined' ? eventArgs['senderId'] : '';

			if(typeName === BX.CrmLeadModel.typeName && id === this.getEntityId() && senderId !== this._dispatcher.getId())
			{
				this._isDirty = true;
			}
		},
		_onExternalDelete: function(eventArgs)
		{
			var typeName = typeof(eventArgs['typeName']) !== 'undefined' ? eventArgs['typeName'] : '';
			var id = typeof(eventArgs['id']) !== 'undefined' ? parseInt(eventArgs['id']) : 0;

			if(typeName === BX.CrmLeadModel.typeName && id === this.getEntityId())
			{
				BX.CrmMobileContext.getCurrent().close();
			}
		},
		_onExternalLeadStatusChange: function(eventArgs)
		{
			var contextId = BX.type.isNotEmptyString(eventArgs["contextId"]) ? eventArgs["contextId"] : "";
			if(contextId !== this.getContextId())
			{
				return;
			}

			this._syncData["STATUS"] =
				{
					id: BX.type.isNotEmptyString(eventArgs["statusId"]) ? eventArgs["statusId"] : "",
					name: BX.type.isNotEmptyString(eventArgs["name"]) ? eventArgs["name"] : ""
				};

			this._enableLeadStatusChangeMode(false);
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
		_onExternalStatusChange: function(eventArgs)
		{
			var contextId = typeof(eventArgs['contextId']) ? eventArgs['contextId'] : '';
			if(contextId !== this.getSetting('contextId', ''))
			{
				return;
			}

			var statusId = typeof(eventArgs['statusId']) ? eventArgs['statusId'] : '';
			var name = typeof(eventArgs['name']) ? eventArgs['name'] : '';
			this._syncData["SOURCE"] =
				{
					id: statusId,
					name: name
				};
			this._enableStatusChangeMode(false);
		},
		_synchronize: function()
		{
			if(!this._syncData)
			{
				return;
			}

			if(typeof(this._syncData["STATUS"]) !== "undefined")
			{
				var data = this._syncData["STATUS"];
				var id = BX.type.isNotEmptyString(data["id"]) ? data["id"] : "";
				if(this._statusId)
				{
					this._statusId.value = id;
				}

				var name = BX.type.isNotEmptyString(data["name"]) ? data["name"] : "";
				if(this._statusName)
				{
					this._statusName.innerHTML = BX.util.htmlspecialchars(name);
				}

				if(this._statusProgressBar)
				{
					this._statusProgressBar.setCurrentStepId(id, false);
				}

				delete this._syncData["STATUS"];
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
			else if(typeof(this._syncData["SOURCE"]) !== "undefined")
			{
				var data = this._syncData["SOURCE"];
				var id = BX.type.isNotEmptyString(data["id"]) ? data["id"] : "";

				if(this._sourceId)
				{
					this._sourceId.value = id;
				}

				var name = BX.type.isNotEmptyString(data["name"]) ? data["name"] : "";
				if(this._sourceName)
				{
					this._sourceName.innerHTML = BX.util.htmlspecialchars(name !== '' ? name : id);
				}
				delete this._syncData["SOURCE"];
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

	if(typeof(BX.CrmLeadEditor.messages) === 'undefined')
	{
		BX.CrmLeadEditor.messages = {};
	}

	BX.CrmLeadEditor.items = {};
	BX.CrmLeadEditor.create = function(id, settings)
	{
		var self = new BX.CrmLeadEditor();
		self.initialize(id, settings);
		this.items[id] = self;
		return self;
	};
}
