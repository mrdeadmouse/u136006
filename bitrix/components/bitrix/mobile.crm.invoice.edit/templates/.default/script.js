if(typeof(BX.CrmInvoiceEditor) === "undefined")
{
	BX.CrmInvoiceEditor = function()
	{
		this._id = '';
		this._settings = {};
		this._prefix = '';
		this._contactId = this._companyId = this._clientCaption = this._clientLegend = this._statusId = this._statusName = this._dateBill = this._dateBillText = this._datePayBefore = this._datePayBeforeText = this._dealId = this._dealTitle = this._personTypeId = this._paySystemId = this._paySystemName = this._locationId = this._locationName = this._payerInfo = null;
		this._responsibleId = this._responsibleName = null;
		this._invoiceStatusProgressBar = null;
		this._dispatcher = null;
		this._isDirty = false;
		this._contextMenuId = '';
		this._productRowList = null;
		this._payerRequisites = [];
		this._payerInfoFormat = "";

		this._productRowListChangeHandler = BX.delegate(this._onProductRowListChange, this);
		this._invoiceStatusChangeCompleteHandler = BX.delegate(this._onExternalInvoiceStatusChange, this);
		this._clientChangeCompleteHandler = BX.delegate(this._onExternalClientChange, this);
		this._dealChangeCompleteHandler = BX.delegate(this._onExternalDealChange, this);
		this._paySystemChangeCompleteHandler = BX.delegate(this._onExternalPaySystemChange, this);
		this._locationChangeCompleteHandler = BX.delegate(this._onExternalLocationChange, this);
		this._requisiteChangeCompleteHandler = BX.delegate(this._onExternalRequisiteChange, this);
		this._isInInvoiceStatusChangeMode = this._isInClientChangeMode = this._isInDealChangeMode = this._isInPaySystemChangeMode = this._isInLocationChangeMode = this._isInPayserInfoChangeMode =false;
		this._syncData = {};
		this._invoiceStatusData = null;
	};

	BX.CrmInvoiceEditor.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};
			this._prefix = this.getSetting('prefix');
			this._dispatcher = this.getSetting('dispatcher', null);
			if(!this._dispatcher)
			{
				throw "BX.CrmInvoiceEditor. Could not find dispatcher.";
			}

			var entityId = this.getEntityId();
			if(entityId > 0)
			{
				this._model = this._dispatcher.getModelById(this.getEntityId());
				if(!this._model)
				{
					throw "BX.CrmInvoiceEditor. Could not find model.";
				}

				var m = this._model;
				this._invoiceStatusData =
					{
						PAYMENT_TIME_STAMP: m.getIntParam("PAYMENT_TIME_STAMP"),
						PAYMENT_DATE: m.getStringParam("PAYMENT_DATE"),
						PAYMENT_DOC: m.getStringParam("PAYMENT_DOC"),
						PAYMENT_COMMENT: m.getStringParam("PAYMENT_COMMENT"),
						CANCEL_TIME_STAMP: m.getIntParam("CANCEL_TIME_STAMP"),
						CANCEL_DATE: m.getStringParam("CANCEL_DATE"),
						CANCEL_REASON: m.getStringParam("CANCEL_REASON")
					};
			}
			else
			{
				this._invoiceStatusData =
					{
						PAYMENT_TIME_STAMP: 0,
						PAYMENT_DATE: "",
						PAYMENT_DOC: "",
						PAYMENT_COMMENT: "",
						CANCEL_TIME_STAMP: 0,
						CANCEL_DATE: "",
						CANCEL_REASON: ""
					};
			}

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

			this._responsibleId = this.resolveElement('responsible_id');
			if(this._responsibleId)
			{
				BX.bind(BX.findParent(
					this._responsibleId,
					{ className: 'crm_block_container' }), 'click', BX.delegate(this._onResponsibleSelect, this)
				);
			}
			this._responsibleName = this.resolveElement('responsible_name');

			this._statusId = this.resolveElement("status_id");
			this._statusName = this.resolveElement("status_name");
			var statusContainer = this.resolveElement("status_container");
			if(statusContainer)
			{
				BX.bind(BX.findParent(statusContainer,{ className: "crm_meeting_info" }), "click", BX.delegate(this._onStatusClick, this));
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

			this._dateBill = this.resolveElement("date_bill");
			if(this._dateBill)
			{
				BX.bind(
					BX.findParent(this._dateBill, { className: "crm_meeting_info" }),
					"click",
					BX.delegate(this._onDateBillClick, this)
				);
			}
			this._dateBillText = this.resolveElement("date_bill_text");

			this._datePayBefore = this.resolveElement("date_pay_before");
			if(this._datePayBefore)
			{
				BX.bind(
					BX.findParent(this._datePayBefore, { className: "crm_meeting_info" }),
					"click",
					BX.delegate(this._onDatePayBeforeClick, this)
				);
			}
			this._datePayBeforeText = this.resolveElement("date_pay_before_text");

			this._dealId = this.resolveElement("deal_id");
			if(this._dealId)
			{
				BX.bind(
					BX.findParent(this._dealId, { className: "crm_block_container" }),
					"click",
					BX.delegate(this._onDealClick, this)
				);
			}
			this._dealTitle = this.resolveElement("deal_title");

			this._personTypeId = this.resolveElement("person_type_id");
			this._paySystemId = this.resolveElement("pay_system_id");
			if(this._paySystemId)
			{
				BX.bind(
					BX.findParent(this._paySystemId, { className: "crm_meeting_info" }),
					"click",
					BX.delegate(this._onPaySystemClick, this)
				);
			}
			this._paySystemName = this.resolveElement("pay_system_name");

			this._locationId = this.resolveElement("location_id");
			if(this._locationId)
			{
				BX.bind(
					BX.findParent(this._locationId, { className: "crm_meeting_info" }),
					"click",
					BX.delegate(this._onLocationClick, this)
				);
			}
			this._locationName = this.resolveElement("location_name");

			this._payerRequisites = this.getSetting("payerRequisites", []);
			this._payerInfoFormat = this.getSetting("payerInfoFormat", "");
			this._payerInfo = this.resolveElement("payer_info");
			if(this._payerInfo)
			{
				BX.bind(
					BX.findParent(this._payerInfo, { className: "crm_meeting_info" }),
					"click",
					BX.delegate(this._onPayerInfoClick, this)
				);
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
		setFieldValue: function(fieldName, value)
		{
			var elem = this.resolveElement(fieldName);
			if(elem)
			{
				elem.value = value;
			}
		},
		createSaveHandler: function()
		{
			return BX.delegate(this._onSave, this);
		},
		getMessage: function(name)
		{
			var messages = BX.CrmInvoiceEditor.messages;
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
		setProductRowList: function(productRowList)
		{
			if(this._productRowList === productRowList)
			{
				return;
			}

			if(this._productRowList)
			{
				this._productRowList.clearItems();

				BX.removeCustomEvent(
					this._productRowList,
					"onCrmProductRowListChange",
					this._productRowListChangeHandler
				);
			}

			this._productRowList = productRowList;

			if(this._productRowList)
			{
				this._productRowList.setup(
					this.getSetting("productRows", []),
					this._prepareTotalInfos(
						{
							TAX_INFOS : this.getSetting("taxInfos", []),
							FORMATTED_SUM_BRUTTO: this.getSetting("formattedSumBrutto", []),
							FORMATTED_SUM_NETTO: this.getSetting("formattedSumNetto", [])
						}
					),
					true
				);

				BX.addCustomEvent(
					this._productRowList,
					"onCrmProductRowListChange",
					this._productRowListChangeHandler
				);
			}
		},
		_prepareTotalInfos: function(data)
		{
			var totalInfos = [ { title: this.getMessage("sumTotal"), html: "-" } ];
			var allTaxesInPrice = true;
			var taxInfos = data["TAX_INFOS"];
			for(var i = 0; i < taxInfos.length; i++)
			{
				var taxInfo = taxInfos[i];
				if(allTaxesInPrice && !(typeof(taxInfo["IS_IN_PRICE"]) !== "undefined" && taxInfo["IS_IN_PRICE"]))
				{
					allTaxesInPrice = false;
				}
				totalInfos.push(
					{
						title:  BX.type.isNotEmptyString(taxInfo["TITLE"])
							? taxInfo["TITLE"] : "",
						html: BX.type.isNotEmptyString(taxInfo["FORMATTED_SUM"])
							? taxInfo["FORMATTED_SUM"] : ""
					}
				);
			}

			if(allTaxesInPrice)
			{
				totalInfos[0]["html"] = data["FORMATTED_SUM_BRUTTO"];
			}
			else
			{
				totalInfos[0]["html"] = data["FORMATTED_SUM_NETTO"];
				totalInfos.push(
					{
						title:  this.getMessage("sumBrutto"),
						html: data["FORMATTED_SUM_BRUTTO"]
					}
				);
			}

			return totalInfos;
		},
		_recalculate: function(options)
		{
			if(!options)
			{
				options = {};
			}

			var enableProductRows = typeof(options["enableProductRows"]) !== "undefined" ? options["enableProductRows"] : false;
			var enablePayerInfo = typeof(options["enablePayerInfo"]) !== "undefined" ? options["enablePayerInfo"] : false;

			var productRows = [];
			if(enableProductRows)
			{
				productRows = this._productRowList ? this._productRowList.prepareForSave() : [];
			}

			if(!enablePayerInfo && enableProductRows && productRows.length === 0)
			{
				if(this._productRowList)
				{
					this._productRowList.setup([], []);
				}
				return;
			}

			BX.ajax(
				{
					url: this.getSetting("serviceUrl", ""),
					method: "POST",
					dataType: "json",
					data:
					{
						ACTION : "RECALCULATE",
						SOURCE_DATA:
						{
							ENABLE_PRODUCT_ROWS: enableProductRows ? "Y" : "N",
							ENABLE_PAYER_INFO: enablePayerInfo ? "Y" : "N",
							ID: this.getEntityId(),
							COMPANY_ID: this.getFieldValue("COMPANY_ID"),
							CONTACT_ID: this.getFieldValue("CONTACT_ID"),
							PERSON_TYPE_ID: this.getFieldValue("PERSON_TYPE_ID"),
							PAY_SYSTEM_ID: this.getFieldValue("PAY_SYSTEM_ID"),
							LOCATION_ID: this.getFieldValue("LOCATION_ID"),
							PRODUCT_ROWS: productRows
						}
					},
					onsuccess: BX.delegate(this._onRecalcutaionSuccess, this),
					onfailure: BX.delegate(this._onRecalcutaionFailure, this)
				}
			);
		},
		_onRecalcutaionSuccess: function(data)
		{
			var resultData = typeof(data["RESULT_DATA"]) !== "undefined" ? data["RESULT_DATA"] : null;
			if(!resultData)
			{
				return;
			}

			if(this._personTypeId && typeof(resultData["PERSON_TYPE_ID"]) !== "undefined")
			{
				this._personTypeId.value = parseInt(resultData["PERSON_TYPE_ID"]);
			}

			if(this._paySystemId && typeof(resultData["PAY_SYSTEM_ID"]) !== "undefined")
			{
				var paySystemId = parseInt(resultData["PAY_SYSTEM_ID"]);
				this._paySystemId.value = paySystemId;

				if(this._paySystemName)
				{
					this._paySystemName.innerHTML = paySystemId > 0
						? (BX.type.isNotEmptyString(resultData["PAY_SYSTEM_NAME"])
							? BX.util.htmlspecialchars(resultData["PAY_SYSTEM_NAME"]) : paySystemId)
						: BX.util.htmlspecialchars(this.getMessage("notSpecified"));
				}

				var personTypeId = parseInt(this.getFieldValue("PERSON_TYPE_ID"));
				var paySystemContainer = BX.findParent(this._paySystemId, { className: "crm_meeting_info" });
				if(personTypeId > 0 && !BX.hasClass(paySystemContainer, "crm_arrow"))
				{
					BX.addClass(paySystemContainer, "crm_arrow");
				}
				else if(personTypeId <= 0 && BX.hasClass(paySystemContainer, "crm_arrow"))
				{
					BX.removeClass(paySystemContainer, "crm_arrow");
				}
			}

			// PAYER_REQUISITES -->
			if(BX.type.isArray(resultData["PAYER_REQUISITES"]))
			{
				this._payerRequisites = resultData["PAYER_REQUISITES"];
			}
			if(BX.type.isNotEmptyString(resultData["PAYER_INFO_FORMAT"]))
			{
				this._payerInfoFormat = resultData["PAYER_INFO_FORMAT"];
			}
			if(this._payerInfo && BX.type.isNotEmptyString(resultData["PAYER_INFO"]))
			{
				this._payerInfo.innerHTML = BX.util.htmlspecialchars(resultData["PAYER_INFO"]);

				var hasRequisites = this._payerRequisites.length > 0;
				var payerInfoContainer = BX.findParent(this._payerInfo, { className: "crm_meeting_info" });
				if(hasRequisites && !BX.hasClass(payerInfoContainer, "crm_arrow"))
				{
					BX.addClass(payerInfoContainer, "crm_arrow");
				}
				else if(!hasRequisites && BX.hasClass(payerInfoContainer, "crm_arrow"))
				{
					BX.removeClass(payerInfoContainer, "crm_arrow");
				}
			}
			//<-- PAYER_REQUISITES

			if(BX.type.isArray(resultData["PRODUCT_ROWS"]))
			{
				this._productRowList.setup(resultData["PRODUCT_ROWS"], this._prepareTotalInfos(resultData), false);
			}
		},
		_onRecalcutaionFailure: function(data)
		{
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

			if(this._responsibleId)
			{
				this._responsibleId.value = userId;
			}

			if(this._responsibleName)
			{
				this._responsibleName.innerHTML = BX.util.htmlspecialchars(userName);
			}
		},
		_enableDealChangeMode: function(enable)
		{
			enable = !!enable;
			if(this._isInDealChangeMode === enable)
			{
				return;
			}

			this._isInDealChangeMode = enable;

			if(enable)
			{
				BX.addCustomEvent(
					window,
					'onCrmDealSelect',
					this._dealChangeCompleteHandler
				);
			}
			else
			{
				BX.removeCustomEvent(
					window,
					'onCrmDealSelect',
					this._dealChangeCompleteHandler
				);
			}
		},
		_onExternalDealChange: function(eventArgs)
		{
			var contextId = typeof(eventArgs['contextId']) ? eventArgs['contextId'] : '';
			if(contextId !== this.getSetting('contextId', ''))
			{
				return;
			}

			this._syncData["DEAL"] =
			{
				id: typeof(eventArgs["id"]) ? eventArgs["id"] : 0,
				title: typeof(eventArgs["title"]) ? eventArgs["title"] : ""
			};

			this._enableDealChangeMode(false);
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
				ID: this.getEntityId(),
				ORDER_TOPIC: this.getFieldValue("ORDER_TOPIC"),
				STATUS_ID: this.getFieldValue("STATUS_ID"),
				DATE_BILL: this.getFieldValue("DATE_BILL"),
				DATE_PAY_BEFORE: this.getFieldValue("DATE_PAY_BEFORE"),
				CURRENCY_ID: this.getFieldValue("CURRENCY_ID"),
				DEAL_ID: this.getFieldValue("DEAL_ID"),
				CONTACT_ID: this.getFieldValue("CONTACT_ID"),
				COMPANY_ID: this.getFieldValue("COMPANY_ID"),
				PERSON_TYPE_ID: this.getFieldValue("PERSON_TYPE_ID"),
				LOCATION_ID: this.getFieldValue("LOCATION_ID"),
				PAY_SYSTEM_ID: this.getFieldValue("PAY_SYSTEM_ID"),
				RESPONSIBLE_ID: this.getFieldValue("RESPONSIBLE_ID"),
				COMMENTS: this.getFieldValue("COMMENTS"),
				USER_DESCRIPTION: this.getFieldValue("USER_DESCRIPTION")
			};

			if(this._invoiceStatusData)
			{
				// Store additional invoice status data
				for(var k in this._invoiceStatusData)
				{
					if(!this._invoiceStatusData.hasOwnProperty(k))
					{
						continue;
					}

					data[k] = this._invoiceStatusData[k];
				}
			}

			data["PRODUCT_ROWS"] = this._productRowList ? this._productRowList.prepareForSave() : [];

			for(var i = 0; i < this._payerRequisites.length; i++)
			{
				var requisite = this._payerRequisites[i];
				data[requisite["ALIAS"]] = requisite["VALUE"];
			}

			if(entityId <= 0)
			{
				this._dispatcher.createEntity(
					data,
					BX.CrmMobileContext.getCurrent().createCloseHandler(),
					{
						contextId: this.getContextId(),
						title: this.getSetting("title", "")
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
						title: this.getSetting("title", "")
					}
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
				BX.CrmMobileContext.getCurrent().open(
					{
						url: url,
						data:
							{
								contextId: this.getContextId(),
								currentStepId: this.getFieldValue("STATUS_ID"),
								modelData: this._invoiceStatusData
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
		},
		_onDateBillClick: function(e)
		{
			if(!this._dateBill)
			{
				return;
			}

			var timestamp = parseInt(this._dateBill.value);
			if(isNaN(timestamp) || timestamp <= 0)
			{
				timestamp = parseInt(BX.message('SERVER_TIME'));
			}

			BX.CrmMobileContext.getCurrent().showDatePicker(
				BX.date.getBrowserTimestamp(timestamp),
				"date",
				BX.delegate(this._onDateBillChange, this)
			);
		},
		_onDateBillChange: function(val)
		{
			//value format "month/day/year hour:minute"
			var timestamp = Date.parse(val);
			if(this._dateBill)
			{
				this._dateBill.value = BX.date.getServerTimestamp(timestamp);
			}

			if(this._dateBillText)
			{
				var f = BX.message("FORMAT_DATE");
				f = BX.date.convertBitrixFormat(BX.type.isNotEmptyString(f) ? f : "DD.MM.YYYY");
				this._dateBillText.innerHTML = BX.CrmInvoiceEditorHelper.trimDateTimeString(BX.date.format(f, new Date(timestamp)));
			}
		},
		_onDatePayBeforeClick: function(e)
		{
			if(!this._datePayBefore)
			{
				return;
			}

			var timestamp = parseInt(this._datePayBefore.value);
			if(isNaN(timestamp) || timestamp <= 0)
			{
				timestamp = parseInt(BX.message('SERVER_TIME'));
			}

			BX.CrmMobileContext.getCurrent().showDatePicker(
				BX.date.getBrowserTimestamp(timestamp),
				"date",
				BX.delegate(this._onDatePayBeforeChange, this)
			);
		},
		_onDatePayBeforeChange: function(val)
		{
			//value format "month/day/year hour:minute"
			var timestamp = Date.parse(val);
			if(this._datePayBefore)
			{
				this._datePayBefore.value = BX.date.getServerTimestamp(timestamp);
			}

			if(this._datePayBeforeText)
			{
				var f = BX.message("FORMAT_DATE");
				f = BX.date.convertBitrixFormat(BX.type.isNotEmptyString(f) ? f : "DD.MM.YYYY");
				this._datePayBeforeText.innerHTML = BX.CrmInvoiceEditorHelper.trimDateTimeString(BX.date.format(f, new Date(timestamp)));
			}
		},
		_onDealClick: function(e)
		{
			var url = this.getSetting("dealSelectorUrl", "");
			if(url !== '')
			{
				BX.CrmMobileContext.getCurrent().open(
					{
						url: url,
						data:
						{
							contextId: this.getContextId()
						}
					}
				);
				this._enableDealChangeMode(true);
			}
			return BX.PreventDefault(e);
		},
		_onPaySystemClick: function(e)
		{
			var personTypeId = parseInt(this.getFieldValue("PERSON_TYPE_ID"));

			if(isNaN(personTypeId) || personTypeId <= 0)
			{
				return;
			}

			var url = this.getSetting("paySystemSelectorUrl", "");
			if(url !== '')
			{
				BX.CrmMobileContext.getCurrent().open(
					{
						url: url,
						data:
						{
							contextId: this.getContextId(),
							personTypeId: personTypeId
						}
					}
				);
				this._enablePaySystemChangeMode(true);
			}
		},
		_enablePaySystemChangeMode: function(enable)
		{
			enable = !!enable;
			if(this._isInPaySystemChangeMode === enable)
			{
				return;
			}

			this._isInPaySystemChangeMode = enable;

			if(enable)
			{
				BX.addCustomEvent(
					window,
					"onCrmPaySystemSelect",
					this._paySystemChangeCompleteHandler
				);
			}
			else
			{
				BX.removeCustomEvent(
					window,
					"onCrmPaySystemSelect",
					this._paySystemChangeCompleteHandler
				);
			}
		},
		_onExternalPaySystemChange: function(eventArgs)
		{
			var contextId = typeof(eventArgs["contextId"]) ? eventArgs["contextId"] : "";
			if(contextId !== this.getSetting("contextId", ""))
			{
				return;
			}

			this._syncData["PAY_SYSTEM"] =
			{
				id: typeof(eventArgs["id"]) ? eventArgs["id"] : 0,
				name: typeof(eventArgs["name"]) ? eventArgs["name"] : ""
			};

			this._enablePaySystemChangeMode(false);
		},
		_onLocationClick: function(e)
		{
			var url = this.getSetting("locationSelectorUrl", "");
			if(url !== '')
			{
				BX.CrmMobileContext.getCurrent().open(
					{
						url: url,
						data:
						{
							contextId: this.getContextId()
						}
					}
				);
				this._enableLocationChangeMode(true);
			}
		},
		_enableLocationChangeMode: function(enable)
		{
			enable = !!enable;
			if(this._isInLocationChangeMode === enable)
			{
				return;
			}

			this._isInLocationChangeMode = enable;

			if(enable)
			{
				BX.addCustomEvent(
					window,
					"onCrmLocationSelect",
					this._locationChangeCompleteHandler
				);
			}
			else
			{
				BX.removeCustomEvent(
					window,
					"onCrmLocationSelect",
					this._locationChangeCompleteHandler
				);
			}
		},
		_onExternalLocationChange: function(eventArgs)
		{
			var contextId = typeof(eventArgs["contextId"]) ? eventArgs["contextId"] : "";
			if(contextId !== this.getSetting("contextId", ""))
			{
				return;
			}

			this._syncData["LOCATION"] =
			{
				id: BX.type.isNumber(eventArgs["id"]) ? eventArgs["id"] : 0,
				name: BX.type.isNotEmptyString(eventArgs["name"]) ? eventArgs["name"] : "",
				regionName: BX.type.isNotEmptyString(eventArgs["regionName"]) ? eventArgs["regionName"] : "",
				countryName: BX.type.isNotEmptyString(eventArgs["countryName"]) ? eventArgs["countryName"] : "",
				title: BX.type.isNotEmptyString(eventArgs["title"]) ? eventArgs["title"] : ""
			};

			this._enableLocationChangeMode(false);
		},
		_onPayerInfoClick: function(e)
		{
			var personTypeId = parseInt(this.getFieldValue("PERSON_TYPE_ID"));
			if(isNaN(personTypeId) || personTypeId <= 0 || this._payerRequisites.length === 0)
			{
				return;
			}

			var url = this.getSetting("requisiteEditUrl", "");
			if(url !== '')
			{
				BX.CrmMobileContext.getCurrent().open(
					{
						url: url,
						data:
						{
							contextId: this.getContextId(),
							personTypeId: personTypeId,
							data: this._payerRequisites
						}
					}
				);
				this._enablePayerInfoChangeMode(true);
			}
		},
		_enablePayerInfoChangeMode: function(enable)
		{
			enable = !!enable;
			if(this._isInPayserInfoChangeMode === enable)
			{
				return;
			}

			this._isInPayserInfoChangeMode = enable;

			if(enable)
			{
				BX.addCustomEvent(
					window,
					"onCrmClientRequisiteChanged",
					this._requisiteChangeCompleteHandler
				);
			}
			else
			{
				BX.removeCustomEvent(
					window,
					"onCrmClientRequisiteChanged",
					this._requisiteChangeCompleteHandler
				);
			}
		},
		_onExternalUpdate: function(eventArgs)
		{
			var typeName = typeof(eventArgs['typeName']) !== 'undefined' ? eventArgs['typeName'] : '';
			var id = typeof(eventArgs['id']) !== 'undefined' ? parseInt(eventArgs['id']) : 0;
			var senderId = typeof(eventArgs['senderId']) !== 'undefined' ? eventArgs['senderId'] : '';

			if(typeName === BX.CrmInvoiceModel.typeName && id === this.getEntityId() && senderId !== this._dispatcher.getId())
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
		_onExternalRequisiteChange: function(eventArgs)
		{
			var contextId = BX.type.isNotEmptyString(eventArgs["contextId"]) ? eventArgs["contextId"] : "";
			var personTypeId = BX.type.isNumber(eventArgs["personTypeId"]) ? eventArgs["personTypeId"] : 0;
			if(contextId !== this.getContextId() || personTypeId !== parseInt(this.getFieldValue("PERSON_TYPE_ID")))
			{
				return;
			}

			var requisite;
			var data = BX.type.isArray(eventArgs["data"]) ? eventArgs["data"] : [];
			for(var i = 0; i < data.length; i++)
			{
				var datum = data[i];
				var alias = BX.type.isNotEmptyString(datum["ALIAS"]) ? datum["ALIAS"] : "";
				var value = BX.type.isNotEmptyString(datum["VALUE"]) ? datum["VALUE"] : "";

				if(alias === "")
				{
					continue;
				}

				for(var j = 0; j < this._payerRequisites.length; j++)
				{
					requisite = this._payerRequisites[j];
					if(requisite["ALIAS"] === alias)
					{
						requisite["VALUE"] = value;
						break;
					}
				}
			}

			var infoText = "";
			var format = this._payerInfoFormat.split(",");
			for(var k = 0; k < format.length; k++)
			{
				var code = format[k];
				for(var m = 0; m < this._payerRequisites.length; m++)
				{
					requisite = this._payerRequisites[m];
					if(requisite["CODE"] !== code)
					{
						continue;
					}

					var v = requisite["VALUE"];
					if(v !== "")
					{
						if(infoText !== "")
						{
							infoText += ", " + v;
						}
						else
						{
							infoText = v;
						}
					}
					break;
				}
			}
			this._syncData["PAYER_INFO"] = { text: infoText };
			this._enablePayerInfoChangeMode(false);
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

				this._invoiceStatusData = typeof(data["additionalData"]) !== "undefined" ? data["additionalData"] : null;
				delete this._syncData["INVOICE_STATUS"];
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
				this._recalculate({ enableProductRows: true, enablePayerInfo: true });
			}
			else if(typeof(this._syncData["DEAL"]) !== "undefined")
			{
				data = this._syncData["DEAL"];
				if(this._dealId)
				{
					this._dealId.value = data["id"];
				}

				if(this._dealTitle)
				{
					this._dealTitle.innerHTML = BX.util.htmlspecialchars(data["title"]);
				}
				delete this._syncData["DEAL"];
			}
			else if(typeof(this._syncData["PAY_SYSTEM"]) !== "undefined")
			{
				data = this._syncData["PAY_SYSTEM"];
				if(this._paySystemId)
				{
					this._paySystemId.value = parseInt(data["id"]);
				}

				if(this._paySystemName)
				{
					this._paySystemName.innerHTML = BX.util.htmlspecialchars(data["name"])
				}

				delete this._syncData["PAY_SYSTEM"];
			}
			else if(typeof(this._syncData["LOCATION"]) !== "undefined")
			{
				data = this._syncData["LOCATION"];
				if(this._locationId)
				{
					this._locationId.value = parseInt(data["id"]);
				}

				if(this._locationName)
				{
					this._locationName.innerHTML = BX.util.htmlspecialchars(data["title"])
				}

				this._recalculate({ enableProductRows: true, enablePayerInfo: false });
				delete this._syncData["LOCATION"];
			}
			else if(typeof(this._syncData["PAYER_INFO"]) !== "undefined")
			{
				data = this._syncData["PAYER_INFO"];
				if(this._payerInfo)
				{
					this._payerInfo.innerHTML = BX.util.htmlspecialchars(data["text"]);
				}
				delete this._syncData["PAYER_INFO"];
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
			this._recalculate({ enableProductRows: true, enablePayerInfo: false });
		}
	};

	if(typeof(BX.CrmInvoiceEditor.messages) === 'undefined')
	{
		BX.CrmInvoiceEditor.messages = {};
	}

	BX.CrmInvoiceEditor.items = {};
	BX.CrmInvoiceEditor.create = function(id, settings)
	{
		var self = new BX.CrmInvoiceEditor();
		self.initialize(id, settings);
		this.items[id] = self;
		return self;
	};
}

if(typeof(BX.CrmInvoiceEditorHelper) === "undefined")
{
	BX.CrmInvoiceEditorHelper = function() {};
	BX.CrmInvoiceEditorHelper.trimDateTimeString = function(str)
	{
		var rx = /(\d{2}):(\d{2}):(\d{2})/;
		var ary = rx.exec(str);
		if(!ary || ary.length < 4)
		{
			return str;
		}
		var result = str.substring(0, ary.index) + ary[1] + ':' + ary[2];
		var tailPos = ary.index + 8;
		if(tailPos < str.length)
		{
			result += str.substring(tailPos);
		}
		return result;
	};
}
