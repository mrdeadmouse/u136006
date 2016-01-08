if(typeof(BX.CrmDealStageManager) === "undefined")
{
	BX.CrmDealStageManager = function() {};

	BX.CrmDealStageManager.prototype =
	{
		getInfos: function() { return BX.CrmDealStageManager.infos; },
		getMessage: function(name)
		{
			var msgs = BX.CrmDealStageManager.messages;
			return BX.type.isNotEmptyString(msgs[name]) ? msgs[name] : "";
		}
	};

	BX.CrmDealStageManager.current = new BX.CrmDealStageManager();
	BX.CrmDealStageManager.infos =
	[
		{ "id": "NEW", "name": "In Progress", "sort": 10, "semantics": "process" },
		{ "id": "WON", "name": "Is Won", "sort": 20, "semantics": "success" },
		{ "id": "LOSE", "name": "Is Lost", "sort": 30, "semantics": "failure" }
	];

	BX.CrmDealStageManager.messages = {};
}

if(typeof(BX.CrmLeadStatusManager) === "undefined")
{
	BX.CrmLeadStatusManager = function() {};

	BX.CrmLeadStatusManager.prototype =
	{
		getInfos: function() { return BX.CrmLeadStatusManager.infos; },
		getMessage: function(name)
		{
			var msgs = BX.CrmLeadStatusManager.messages;
			return BX.type.isNotEmptyString(msgs[name]) ? msgs[name] : "";
		}
	};

	BX.CrmLeadStatusManager.current = new BX.CrmLeadStatusManager();
	BX.CrmLeadStatusManager.infos =
	[
		{ "id": "NEW", "name": "Not Processed", "sort": 10, "semantics": "process" },
		{ "id": "CONVERTED", "name": "Converted", "sort": 20, "semantics": "success" },
		{ "id": "JUNK", "name": "Junk", "sort": 30, "semantics": "failure" }
	];

	BX.CrmLeadStatusManager.messages = {};
}

if(typeof(BX.CrmQuoteStatusManager) === "undefined")
{
	BX.CrmQuoteStatusManager = function() {};

	BX.CrmQuoteStatusManager.prototype =
	{
		getInfos: function() { return BX.CrmQuoteStatusManager.infos; },
		getMessage: function(name)
		{
			var msgs = BX.CrmQuoteStatusManager.messages;
			return BX.type.isNotEmptyString(msgs[name]) ? msgs[name] : "";
		}
	};

	BX.CrmQuoteStatusManager.current = new BX.CrmQuoteStatusManager();
	BX.CrmQuoteStatusManager.infos =
		[
			{ "id": "DRAFT", "name": "In Progress", "sort": 10, "semantics": "process" },
			{ "id": "APPROVED", "name": "Is Approved", "sort": 20, "semantics": "success" },
			{ "id": "DECLAINED", "name": "Is Declained", "sort": 30, "semantics": "failure" }
		];

	BX.CrmQuoteStatusManager.messages = {};
}

if(typeof(BX.CrmInvoiceStatusManager) === "undefined")
{
	BX.CrmInvoiceStatusManager = function() {
		this.dlg = null;
	};

	BX.CrmInvoiceStatusManager.prototype =
	{
		getSetting: function(name, defaultval)
		{
			return (typeof(BX.CrmInvoiceStatusManager.settings[name]) !== 'undefined') ?
				BX.CrmInvoiceStatusManager.settings[name] : defaultval;
		},
		setSetting: function(name, val)
		{
			BX.CrmInvoiceStatusManager.settings[name] = val;
		},
		getInfos: function() { return BX.CrmInvoiceStatusManager.infos; },
		getMessage: function(name)
		{
			var msgs = BX.CrmInvoiceStatusManager.messages;
			return BX.type.isNotEmptyString(msgs[name]) ? msgs[name] : "";
		},
		_handleDateInputClick: function(e)
		{
			var inputId = BX(this.dlgDateControlId);
			BX.calendar({ node: BX(inputId), field: inputId, bTime: false, serverTime: this.getSetting('serverTime', ''), bHideTimebar: true });
		},
		_handleDateImageMouseOver: function(e)
		{
			BX.addClass(e.target, 'calendar-icon-hover');
		},
		_handleDateImageMouseOut: function(e)
		{
			if(!e)
			{
				e = window.event;
			}

			BX.removeClass(e.target, 'calendar-icon-hover');
		}
	};

	BX.CrmInvoiceStatusManager.current = new BX.CrmInvoiceStatusManager();
	BX.CrmInvoiceStatusManager.settings = {};
	BX.CrmInvoiceStatusManager.statusInfoValues = [];
	BX.CrmInvoiceStatusManager.infos =
		[
			{ "id": "N", "name": "In Progress", "sort": 10, "semantics": "process" },
			{ "id": "F", "name": "Is Paid", "sort": 20, "semantics": "success", "hasParams": true },
			{ "id": "D", "name": "Is Dismiss", "sort": 30, "semantics": "failure" }
		];

	BX.CrmInvoiceStatusManager.messages = {};
	BX.CrmInvoiceStatusManager.failureDialogEventsBinded = false;

	BX.CrmInvoiceStatusManager.failureDialogEventsBind = function() {
		if (!BX.CrmInvoiceStatusManager.failureDialogEventsBinded)
		{
			BX.CrmInvoiceStatusManager.failureDialogEventsBinded = true;
			BX.addCustomEvent("CrmProcessFailureDialogContentCreated", function(val) {
				var self = BX.CrmInvoiceStatusManager.current;
				var entityType = val.getEntityType();

				if (entityType === "INVOICE")
				{
					var entityId = parseInt(val.getEntityId());
					var wrapper = val.getWrapper();
					var isSuccess = (val.getValue() === val.getSuccessValue());
					var paramsId = "crm_" + entityType + entityId + "_params";
					var successParamsId = paramsId + '_success';
					var failureParamsId = paramsId + '_failure';
					var dateControlIdSuccess = "crm_" + entityType + entityId + "_date_success";
					var dateControlIdFail = "crm_" + entityType + entityId + "_date_fail";

					self.isSuccess = isSuccess;
					self.successParamsId = successParamsId;
					self.failureParamsId = failureParamsId;
					self.dlgDateControlId = isSuccess ? dateControlIdSuccess : dateControlIdFail;

					if (wrapper && entityId > 0 && entityType.length > 0)
					{
						var statusInfoValues = BX.CrmInvoiceStatusManager.statusInfoValues[entityId];
						var content = null;
						content = BX.create(
							"DIV",
							{
								"attrs": {
									"id": paramsId
								},
								"children":
									[
										BX.create(
											"DIV",
											{
												"attrs": {
													"id": successParamsId,
													"class": "crm-invoice-term-dialog-params",
													"style": isSuccess ? "": "display: none;"
												},
												"children":
													[
														BX.create(
															"TABLE",
															{
																"children":
																	[
																		BX.create(
																			"TR",
																			{
																				"children":
																					[
																						BX.create(
																							"TD",
																							{
																								"class": "left-column",
																								"text": self.getMessage("dateLabelText")+":"
																							}
																						),
																						BX.create(
																							"TD",
																							{
																								"children":
																									[
																										// date control
																										BX.create(
																											'INPUT',
																											{
																												attrs: { className: 'bx-crm-dialog-input' },
																												props:
																												{
																													type: 'text',
																													id: dateControlIdSuccess,
																													name: 'PAY_VOUCHER_DATE',
																													value: (statusInfoValues['PAY_VOUCHER_DATE']) ? statusInfoValues['PAY_VOUCHER_DATE'] : BX.formatDate(null, BX.message('FORMAT_DATE'))
																												},
																												style:
																												{
																													width:'70px'
																												},
																												events:
																												{
																													click: BX.delegate(self._handleDateInputClick, self)
																												}
																											}
																										),
																										BX.create(
																											'A',
																											{
																												props:
																												{
																													href:'javascript:void(0);',
																													title: self.getMessage('setDate')
																												},
																												children:
																													[
																														BX.create(
																															'IMG',
																															{
																																attrs:
																																{
																																	src: self.getSetting('imagePath', '') + 'calendar.gif',
																																	className: 'calendar-icon',
																																	alt: self.getMessage('setDate')
																																},
																																events:
																																{
																																	click: BX.delegate(self._handleDateInputClick, self),
																																	mouseover: BX.delegate(self._handleDateImageMouseOver, self),
																																	mouseout: BX.delegate(self._handleDateImageMouseOut, self)
																																}
																															}
																														)
																													]
																											}
																										)
																									]
																							}
																						)
																					]
																			}
																		),
																		BX.create(
																			"TR",
																			{
																				"children":
																					[
																						BX.create(
																							"TD",
																							{
																								"class": "left-column",
																								"text": self.getMessage("payVoucherNumLabelText")+":"
																							}
																						),
																						BX.create(
																							"TD",
																							{
																								"children":
																									[
																										BX.create(
																											"INPUT",
																											{
																												"attrs":
																												{
																													"class": "bx-crm-dialog-input",
																													"type": "text",
																													"name": "PAY_VOUCHER_NUM",
																													"value": (statusInfoValues['PAY_VOUCHER_NUM']) ? statusInfoValues['PAY_VOUCHER_NUM'].substring(0, 20) : '',
																													"maxlength": 20,
																													"size": 20
																												}
																											}
																										)
																									]
																							}
																						)
																					]
																			}
																		)
																	]
															}
														),
														BX.create(
															"DIV",
															{
																"attrs": {
																	"class": "separator"
																}
															}
														),
														BX.create(
															"SPAN",
															{
																"attrs": {
																	"class": "comment-header"
																},
																"text": self.getMessage("commentLabelText")+":"
															}
														),
														BX.create(
															"TEXTAREA",
															{
																"attrs": {
																	"class": "bx-crm-dialog-invoice-textarea",
																	"name": "REASON_MARKED_SUCCESS"
																},
																"text": (statusInfoValues['REASON_MARKED']) ? statusInfoValues['REASON_MARKED'] : ''
															}
														)
													]
											}
										),
										BX.create(
											"DIV",
											{
												"attrs": {
													"id": failureParamsId,
													"class": "crm-invoice-term-dialog-params",
													"style": isSuccess ? "display: none;" : ""
												},
												"children":
													[
														BX.create(
															"TABLE",
															{
																"children":
																	[
																		BX.create(
																			"TR",
																			{
																				"children":
																					[
																						BX.create(
																							"TD",
																							{
																								"class": "left-column",
																								"text": self.getMessage("dateLabelText")+":"
																							}
																						),
																						BX.create(
																							"TD",
																							{
																								"children":
																									[
																										// date control
																										BX.create(
																											'INPUT',
																											{
																												attrs: { className: 'bx-crm-dialog-input' },
																												props:
																												{
																													type: 'text',
																													id: dateControlIdFail,
																													name: 'DATE_MARKED',
																													value: (statusInfoValues['DATE_MARKED']) ? statusInfoValues['DATE_MARKED'] : BX.formatDate(null, BX.message('FORMAT_DATE'))
																												},
																												style:
																												{
																													width:'70px'
																												},
																												events:
																												{
																													click: BX.delegate(self._handleDateInputClick, self)
																												}
																											}
																										),
																										BX.create(
																											'A',
																											{
																												props:
																												{
																													href:'javascript:void(0);',
																													title: self.getMessage('setDate')
																												},
																												children:
																													[
																														BX.create(
																															'IMG',
																															{
																																attrs:
																																{
																																	src: self.getSetting('imagePath', '') + 'calendar.gif',
																																	className: 'calendar-icon',
																																	alt: self.getMessage('setDate')
																																},
																																events:
																																{
																																	click: BX.delegate(self._handleDateInputClick, self),
																																	mouseover: BX.delegate(self._handleDateImageMouseOver, self),
																																	mouseout: BX.delegate(self._handleDateImageMouseOut, self)
																																}
																															}
																														)
																													]
																											}
																										)
																									]
																							}
																						)
																					]
																			}
																		)
																	]
															}
														),
														BX.create(
															"DIV",
															{
																"attrs": {
																	"class": "separator"
																}
															}
														),
														BX.create(
															"SPAN",
															{
																"attrs": {
																	"class": "comment-header"
																},
																"text": self.getMessage("commentLabelText")+":"
															}
														),
														BX.create(
															"TEXTAREA",
															{
																"attrs": {
																	"class": "bx-crm-dialog-invoice-textarea",
																	"name": "REASON_MARKED"
																},
																"text": (statusInfoValues['REASON_MARKED']) ? statusInfoValues['REASON_MARKED'] : ''
															}
														)
													]
											}
										)
									]
							}
						);
						if (content)
							wrapper.appendChild(content);
					}
				}
			});
			BX.addCustomEvent("CrmProcessFailureDialogValueChanged", function(failDlg, val) {
				var self = BX.CrmInvoiceStatusManager.current;
				var entityType = failDlg.getEntityType();
				if (entityType === "INVOICE")
				{
					var entityId = parseInt(failDlg.getEntityId());
					var wrapper = failDlg.getWrapper();
					var isSuccess = (val === failDlg.getSuccessValue());
					var paramsId = "crm_" + entityType + entityId + "_params";
					var successParamsId = paramsId + '_success';
					var failureParamsId = paramsId + '_failure';
					var successContainer = BX(successParamsId);
					var failureContainer = BX(failureParamsId);
					var dateControlIdSuccess = "crm_" + entityType + entityId + "_date_success";
					var dateControlIdFail = "crm_" + entityType + entityId + "_date_fail";

					self.isSuccess = isSuccess;
					self.dlgDateControlId = isSuccess ? dateControlIdSuccess : dateControlIdFail;

					if (successContainer)
						successContainer.setAttribute("style", isSuccess ? "" : "display: none;");
					if (failureContainer)
						failureContainer.setAttribute("style", isSuccess ? "display: none;" : "")
				}
			});
			BX.addCustomEvent("CrmProgressControlBeforeFailureDialogClose", function(progressControl, failDlg) {
				var entityType = failDlg.getEntityType();
				if (entityType === "INVOICE")
				{
					var self = BX.CrmInvoiceStatusManager.current;
					var containter = BX(self.isSuccess ? self.successParamsId : self.failureParamsId);

					self.saveParams = {};
					if (containter)
					{
						var els = [];
						var inputs = BX.findChildren(containter, {"tag": "input"}, true);
						if (inputs)
							els = els.concat(inputs);
						var textareas = BX.findChildren(containter, {"tag": "textarea"}, true);
						if (textareas)
							els = els.concat(textareas);
						var name;
						for (var i in els)
						{
							name = els[i].getAttribute('name');
							if (name)
								self.saveParams[name] = els[i].value;
						}
					}
				}
			});
			BX.addCustomEvent("CrmProgressControlBeforeSave", function(progressControl, params) {
				var self = BX.CrmInvoiceStatusManager.current;
				if (typeof(self.saveParams) === 'object')
				{
					var entityId = progressControl.getEntityId();
					var valName;
					for (var name in self.saveParams)
					{
						if (entityId > 0)
						{
							if (name === "REASON_MARKED_SUCCESS")
								valName = "REASON_MARKED";
							else
								valName = name;
							BX.CrmInvoiceStatusManager.statusInfoValues[entityId][valName] = self.saveParams[name];
						}
						params[name] = self.saveParams[name];
					}
				}
				params['STATE_SUCCESS'] = self.isSuccess ? "Y" : "N";
			});
		}
	};
}

if(typeof(BX.CrmProgressControl) === "undefined")
{
	BX.CrmProgressControl = function()
	{
		this._id = "";
		this._settings = null;
		this._container = null;
		this._legendContainer = null;
		this._entityId = 0;
		this._entityType = null;
		this._currentStepId = "";
		this._manager = null;
		this._stepInfos = null;
		this._steps = [];
		this._terminationDlg = null;
		this._failureDlg = null;
		this._isFrozen = false;
		this._isReadOnly = false;
	};

	BX.CrmProgressControl.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : BX.CrmParamBag.create(null);
			this._container = BX(this.getSetting("containerId"));
			this._legendContainer = BX.findNextSibling(
				this._container,
				{
					"className": "crm-list-stage-bar-title"
				}
			);

			this._entityId = parseInt(this.getSetting("entityId", 0));
			this._entityType = this.getSetting("entityType");
			this._currentStepId = this.getSetting("currentStepId");

			if(this._entityType === 'DEAL')
			{
				this._manager = BX.CrmDealStageManager.current;
			}
			else if(this._entityType === 'LEAD')
			{
				this._manager = BX.CrmLeadStatusManager.current;
			}
			else if(this._entityType === 'QUOTE')
			{
				this._manager = BX.CrmQuoteStatusManager.current;
			}
			else if(this._entityType === 'INVOICE')
			{
				this._manager = BX.CrmInvoiceStatusManager.current;
			}

			var stepInfos = this._stepInfos = this._manager.getInfos();
			var currentStepIndex = this._findStepInfoIndex(this._currentStepId);
			var currentStepInfo = currentStepIndex >= 0 ? stepInfos[currentStepIndex] : null;

			this._isReadOnly = this.getSetting("readOnly", false);
			this._isFrozen = this._isReadOnly
				|| (currentStepInfo && BX.type.isBoolean(currentStepInfo["isFrozen"]) ? currentStepInfo["isFrozen"] : false);

			for(var i = 0; i < stepInfos.length; i++)
			{
				var info = stepInfos[i];
				var stepContainer = this.getStepContainer(info["id"]);
				if(!stepContainer)
				{
					continue;
				}

				var sort = parseInt(info["sort"]);
				this._steps.push(
					BX.CrmProgressStep.create(
						info["id"],
						BX.CrmParamBag.create(
							{
								"name": info["name"],
								"hint": BX.type.isNotEmptyString(info["hint"]) ? info["hint"] : '',
								"sort": sort,
								"isPassed": i <= currentStepIndex,
								"control": this
							}
						)
					)
				);
			}
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.getParam(name, defaultval);
		},
		setSetting: function(name, val)
		{
			this._settings.setParam(name, val);
		},
		getId: function()
		{
			return this._id;
		},
		getEntityType: function()
		{
			return this._entityType;
		},
		getEntityId: function()
		{
			return this._entityId;
		},
		getCurrentStepId: function()
		{
			return this._currentStepId;
		},
		isFrozen: function()
		{
			return this._isFrozen;
		},
		isReadOnly: function()
		{
			return this._isReadOnly;
		},
		getStepContainer: function(id)
		{
			return BX.type.isNotEmptyString(id)
				? BX.findChild(this._container, { "tag": "DIV", "class": "crm-stage-" + id.toLowerCase() }, true)
				: null;
		},
		setCurrentStep: function(step)
		{
			this._closeTerminationDialog();

			if(this._isReadOnly || this._isFrozen)
			{
				return;
			}

			var stepIndex = this._findStepInfoIndex(step.getId());
			if(stepIndex < 0)
			{
				return;
			}

			if(stepIndex === (this._steps.length - 1)
				&& this._findStepInfoBySemantics("success")
				&& this._findStepInfoBySemantics("failure"))
			{
				//User have to make choice
				this._openTerminationDialog();
				return;
			}

			if(this._currentStepId !== step.getId())
			{
				this._currentStepId = step.getId();
				this._layout();
				this._save();
			}
		},
		setCurrentStepId: function(stepId)
		{
			if(this._currentStepId !== stepId)
			{
				this._currentStepId = stepId;
				this._layout();
			}
		},
		getCurrentStepInfo: function()
		{
			var stepIndex = this._findStepInfoIndex(this._currentStepId);
			return stepIndex >= 0 ? this._stepInfos[stepIndex] : null;
		},
		_layout: function()
		{
			var stepIndex = this._findStepInfoIndex(this._currentStepId);
			if(stepIndex < 0)
			{
				return;
			}

			for(var i = 0; i < this._steps.length; i++)
			{
				this._steps[i].setPassed(i <= stepIndex);
			}

			var stepInfo = this._stepInfos[stepIndex];

			this._isFrozen = BX.type.isBoolean(stepInfo["isFrozen"]) ? stepInfo["isFrozen"] : false;
			var semantics = BX.type.isNotEmptyString(stepInfo["semantics"]) ? stepInfo["semantics"] : "";
			
			if(semantics === "success")
			{
				BX.addClass(this._container, "crm-list-stage-end-good");
				BX.removeClass(this._container, "crm-list-stage-end-bad");
			}
			else if(semantics === "failure" || semantics === "apology")
			{
				BX.removeClass(this._container, "crm-list-stage-end-good");
				BX.addClass(this._container, "crm-list-stage-end-bad");
			}
			else
			{
				BX.removeClass(this._container, "crm-list-stage-end-good");
				BX.removeClass(this._container, "crm-list-stage-end-bad");
			}

			if(this._legendContainer)
			{
				this._legendContainer.innerHTML = BX.util.htmlspecialchars(BX.type.isNotEmptyString(stepInfo["name"]) ? stepInfo["name"] : stepInfo["id"]);
			}
		},
		_openTerminationDialog: function()
		{
			this._enableStepHints(false);

			if(this._terminationDlg)
			{
				this._terminationDlg.close();
				this._terminationDlg = null;
			}

			var apologies = this._findAllStepInfoBySemantics("apology");
			this._terminationDlg = BX.CrmProcessTerminationDialog.create(
				(this._id + "_TERMINATION"),
				BX.CrmParamBag.create(
					{
						"title": this._manager.getMessage("dialogTitle"),
						//"apologyTitle": this._manager.getMessage("apologyTitle"),
						"failureTitle": apologies.length > 0 ? this._manager.getMessage("failureTitle") : "",
						"anchor": this._container,
						"success": this._findStepInfoBySemantics("success"),
						"failure": this._findStepInfoBySemantics("failure"),
						"apologies": apologies,
						"callback": BX.delegate(this._onTerminationDialogClose, this)
					}
				)
			);
			this._terminationDlg.open();
		},
		_closeTerminationDialog: function()
		{
			if(!this._terminationDlg)
			{
				return;
			}

			this._terminationDlg.close(false);
			this._terminationDlg = null;
			this._enableStepHints(true);
		},
		_onTerminationDialogClose: function(dialog, params)
		{
			if(this._terminationDlg !== dialog)
			{
				return;
			}

			this._closeTerminationDialog();

			var stepId = BX.type.isNotEmptyString(params["result"]) ? params["result"] : "";

			var index = this._findStepInfoIndex(stepId);
			if(index < 0)
			{
				return;
			}

			this._currentStepId = stepId;

			var openFailureDialog = false;

			var info = this._stepInfos[index];
			var failure = this._findStepInfoBySemantics("failure");

			if(failure && failure["id"] === stepId)
			{
				openFailureDialog = true;
			}
			else if(info["semantics"] === "success")
			{
				if(typeof(info["hasParams"]) !== "undefined" && info["hasParams"] === true)
				{
					openFailureDialog = true;
				}
				else
				{
					var finalUrl = this.getSetting("finalUrl", "");
					if(finalUrl !== "")
					{
						window.location = finalUrl;
						return;
					}
				}
			}

			if(openFailureDialog)
			{
				this._openFailureDialog();
				return;
			}

			this._layout();
			this._save();
		},
		_openFailureDialog: function()
		{
			this._enableStepHints(false);

			if(this._failureDlg)
			{
				this._failureDlg.close();
				this._failureDlg = null;
			}

			var currentStepIndex = this._findStepInfoIndex(this._currentStepId);
			var info = currentStepIndex >= 0 ? this._stepInfos[currentStepIndex] : null;
			var initValue = info ? info["id"] : "";

			var apologies = this._findAllStepInfoBySemantics("apology");
			this._failureDlg = BX.CrmProcessFailureDialog.create(
				(this._id + "_FAILURE"),
				BX.CrmParamBag.create(
					{
						//"title": this._manager.getMessage("dialogTitle"),
						"entityType": this._entityType,
						"entityId": this._entityId,
						"initValue": initValue,
						"failureTitle": apologies.length > 0 ? this._manager.getMessage("failureTitle") : "",
						"selectorTitle": this._manager.getMessage("selectorTitle"),
						"anchor": this._container,
						"success": this._findStepInfoBySemantics("success"),
						"failure": this._findStepInfoBySemantics("failure"),
						"apologies": apologies,
						"callback": BX.delegate(this._onFailureDialogClose, this)
					}
				)
			);
			this._failureDlg.open();
		},
		_closeFailureDialog: function()
		{
			if(!this._failureDlg)
			{
				return;
			}

			this._failureDlg.close(false);
			this._failureDlg = null;
			this._enableStepHints(true);
		},
		_onFailureDialogClose: function(dialog, params)
		{
			if(this._failureDlg !== dialog)
			{
				return;
			}

			BX.onCustomEvent(this, 'CrmProgressControlBeforeFailureDialogClose', [ this, this._failureDlg ]);
			this._closeFailureDialog();
			var bid = BX.type.isNotEmptyString(params["bid"]) ? params["bid"] : "";
			if(bid !== "accept")
			{
				return;
			}

			var id = BX.type.isNotEmptyString(params["result"]) ? params["result"] : "";
			var index = this._findStepInfoIndex(id);
			if(index >= 0)
			{
				var info = this._stepInfos[index];
				if(info["semantics"] === "success")
				{
					var finalUrl = this.getSetting("finalUrl", "");
					if(finalUrl !== "")
					{
						window.location = finalUrl;
						return;
					}
				}
				this._currentStepId = info["id"];
				this._layout();
				this._save();
			}
		},
		_save: function()
		{
			var serviceUrl = this.getSetting("serviceUrl");
			var value = this.getCurrentStepId();
			var type = this.getEntityType();
			var id = this.getEntityId();

			if(serviceUrl === "" || value === "" || type === "" || id <= 0)
			{
				return;
			}

			var data =
			{
				"ACTION" : "SAVE_PROGRESS",
				"VALUE": value,
				"TYPE": type,
				"ID": id
			};

			BX.onCustomEvent(this, 'CrmProgressControlBeforeSave', [ this, data ]);

			var self = this;
			BX.ajax(
				{
					"url": serviceUrl,
					"method": "POST",
					"dataType": 'json',
					"data": data,
					"onsuccess": function(data)
					{
						BX.onCustomEvent(self, 'CrmProgressControlAfterSaveSucces', [ self, data ]);
						BX.CrmProgressControl._synchronize(self);
					},
					"onfailure": function(data)
					{
						BX.onCustomEvent(self, 'CrmProgressControlAfterSaveFailed', [ self, data ]);
					}
				}
			);
		},
		_findStepInfoBySemantics: function(semantics)
		{
			var infos = this._stepInfos;
			for(var i = 0; i < infos.length; i++)
			{
				var info = infos[i];
				var s = BX.type.isNotEmptyString(info["semantics"]) ? info["semantics"] : '';
				if(semantics === s)
				{
					return info;
				}
			}

			return null;
		},
		_findAllStepInfoBySemantics: function(semantics)
		{
			var result = [];
			var infos = this._stepInfos;
			for(var i = 0; i < infos.length; i++)
			{
				var info = infos[i];
				var s = BX.type.isNotEmptyString(info["semantics"]) ? info["semantics"] : '';
				if(semantics === s)
				{
					result.push(info);
				}
			}

			return result;
		},
		_findStepInfoIndex: function(id)
		{
			var infos = this._stepInfos;
			for(var i = 0; i < infos.length; i++)
			{
				if(infos[i]["id"] === id)
				{
					return i;
				}
			}

			return -1;
		},
		_enableStepHints: function(enable)
		{
			for(var i = 0; i < this._steps.length; i++)
			{
				this._steps[i].enableHint(enable);
			}
		}
	};

	BX.CrmProgressControl.items = {};
	BX.CrmProgressControl.create = function(id, settings)
	{
		var self = new BX.CrmProgressControl();
		self.initialize(id, settings);
		this.items[id] = self;
		return self;
	};
	BX.CrmProgressControl._synchronize = function(item)
	{
		var type = item.getEntityType();
		var id = item.getEntityId();

		for(var itemId in this.items)
		{
			if(!this.items.hasOwnProperty(itemId))
			{
				continue;
			}

			var curItem = this.items[itemId];
			if(curItem === item)
			{
				continue;
			}

			if(curItem.getEntityType() === type && curItem.getEntityId() === id)
			{
				curItem.setCurrentStepId(item.getCurrentStepId());
			}
		}
	}
}

if(typeof(BX.CrmProgressStep) === "undefined")
{
	BX.CrmProgressStep = function()
	{
		this._id = "";
		this._settings = null;
		this._control = null;
		this._container = null;
		this._name = "";
		this._hint = "";
		this._isPassed = false;
		this._enableHint = true;
		this._hintPopup = null;
		this._hintPopupTimeoutId = null;
	};

	BX.CrmProgressStep.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : BX.CrmParamBag.create(null);
			this._control = this.getSetting("control");
			this._container = this._control.getStepContainer(this._id);
			this._name = this.getSetting("name");
			this._hint = this.getSetting("hint", "");
			this._isPassed = this.getSetting("isPassed", false);

			BX.bind(this._container, "mouseover", BX.delegate(this._onMouseOver, this));
			BX.bind(this._container, "mouseout", BX.delegate(this._onMouseOut, this));
			BX.bind(this._container, "click", BX.delegate(this._onClick, this));
		},
		getId: function()
		{
			return this._id;
		},
		getName: function()
		{
			return this._name;
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.getParam(name, defaultval);
		},
		isPassed: function()
		{
			return this._isPassed;
		},
		setPassed: function(passed)
		{
			passed = !!passed;
			if(this._isPassed === passed)
			{
				return;
			}

			this._isPassed = passed;

			var wrapper = BX.findParent(this._container, { "class": "crm-list-stage-bar-part" });
			if(passed)
			{
				BX.addClass(wrapper, "crm-list-stage-passed");
			}
			else
			{
				BX.removeClass(wrapper, "crm-list-stage-passed");
			}
		},
		isHintEnabled: function()
		{
			return this._enableHint;
		},
		enableHint: function(enable)
		{
			enable = !!enable;
			if(this._enableHint === enable)
			{
				return;
			}

			this._enableHint = enable;
			if(!enable)
			{
				this.hideStepHint();
			}
		},
		displayStepHint: function(step)
		{
			if(!this._enableHint || this._hintPopup)
			{
				return;
			}

			var pos = BX.pos(this._container);
			this._hintPopup = BX.PopupWindowManager.create(
				"step-hint-" + this._id,
				step,
				{
					"angle": {
						"position": "bottom",
						"offset": 0
					},
					"offsetLeft": pos["width"] / 2,
					"offsetTop": 5,
					"content": BX.create(
						"SPAN",
						{
							"attrs": { "class": "crm-list-bar-popup-text" },
							"text": this._hint !== '' ? this._hint : this._name
						}
					),
					"className": "crm-list-bar-popup-table"
				}
			);
			this._hintPopup.show();
		},
		hideStepHint: function()
		{
			if(!this._hintPopup)
			{
				return;
			}

			this._hintPopup.close();
			this._hintPopup.destroy();
			this._hintPopup = null;
		},
		_onClick: function(e)
		{
			this._control.setCurrentStep(this);
		},
		_onMouseOver: function(e)
		{
			if(this._hintPopupTimeoutId !== null)
			{
				window.clearTimeout(this._hintPopupTimeoutId);
			}

			e = e || window.event;
			var target = e.target || e.srcElement;
			var self = this;
			this._hintPopupTimeoutId = window.setTimeout(function(){ self._hintPopupTimeoutId = null; self.displayStepHint(target); }, 300 );
		},
		_onMouseOut: function(e)
		{
			if(this._hintPopupTimeoutId !== null)
			{
				window.clearTimeout(this._hintPopupTimeoutId);
			}

			if(!this._enableHint)
			{
				return;
			}

			var self = this;
			this._hintPopupTimeoutId = window.setTimeout(function(){ self._hintPopupTimeoutId = null; self.hideStepHint(); }, 300 );
		}
	};

	BX.CrmProgressStep.create = function(id, settings)
	{
		var self = new BX.CrmProgressStep();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmProcessTerminationDialog) === "undefined")
{
	BX.CrmProcessTerminationDialog = function()
	{
		this._id = "";
		this._settings = null;
		this._popup = null;
		this._wrapper = null;
		this._result = "";
		this._enableCallback = true;
	};

	BX.CrmProcessTerminationDialog.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : BX.CrmParamBag.create(null);
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.getParam(name, defaultval);
		},
		getId: function()
		{
			return this._id;
		},
		getResult: function()
		{
			return this._result;
		},
		open: function()
		{
			if(!this._popup)
			{
				this._popup = BX.PopupWindowManager.create(
					this._id,
					this.getSetting("anchor"),
					{
						"closeByEsc": true,
						"autoHide": true,
						"offsetLeft": -50,
						"closeIcon": true,
						"className": "crm-list-end-deal",
						"content": this._prepareContent(),
						"events": { "onPopupClose": BX.delegate(this._onPopupClose, this) }
					}
				);
			}
			this._popup.show();
		},
		close: function(enableCallback)
		{
			this._enableCallback = !!enableCallback;
			if(this._popup)
			{
				this._popup.close();
			}
		},
		_onPopupClose: function()
		{
			if(this._popup)
			{
				this._popup.destroy();
				this._popup = null;
			}

			this._executeCallback();
		},
		_prepareContent: function()
		{
			var wrapper = this._wrapper = BX.create(
				"DIV",
				{ "attrs": { "class": "crm-list-end-deal-block" } }
			);

			var title = this.getSetting("title", "");
			wrapper.appendChild(
				BX.create(
					"DIV",
					{
						"attrs":
						{
							"class": "crm-list-end-deal-text"
						},
						"text": title
					}
				)
			);

			var buttonBlock = BX.create(
				"DIV",
				{
					"attrs":
					{
						"class": "crm-list-end-deal-buttons-block"
					}
				}
			);

			var success = this.getSetting("success");
			if(success)
			{
				var successText = BX.type.isNotEmptyString(success["name"]) ? success["name"] : "Success";
				var successButton = BX.create(
					"A",
					{
						attrs:
						{
							className: "webform-small-button webform-small-button-accept",
							href: "#"
						},
						children:
						[
							BX.create("SPAN", { attrs: { className: "webform-small-button-left" } }),
							BX.create("SPAN", { attrs: { className: "webform-small-button-text" }, text: successText }),
							BX.create("SPAN", { attrs: { className: "webform-small-button-right" } })
						]
					}
				);

				buttonBlock.appendChild(successButton);
				var successId = BX.type.isNotEmptyString(success["id"]) ? success["id"] : "success";
				BX.CrmSubscriber.subscribe(
					this.getId() + "_" + successId,
					successButton, "click", BX.delegate(this._onButtonClick, this),
					BX.CrmParamBag.create({ "id": successId, "preventDefault": true })
				);
			}

			var failure = this.getSetting("failure");
			if(failure)
			{
				// Check if custom failure text is defined
				var failureTitle = this.getSetting("failureTitle", "");
				if(failureTitle === "")
				{
					failureTitle = BX.type.isNotEmptyString(failure["name"]) ? failure["name"] : "Failure";
				}

				var failureButton = BX.create(
					"A",
					{
						attrs:
						{
							className: "webform-small-button webform-small-button-decline",
							href: "#"
						},
						children:
						[
							BX.create("SPAN", { attrs: { className: "webform-small-button-left" } }),
							BX.create("SPAN", { attrs: { className: "webform-small-button-text" }, text: failureTitle }),
							BX.create("SPAN", { attrs: { className: "webform-small-button-right" } })
						]
					}
				);

				buttonBlock.appendChild(failureButton);
				var failureId = BX.type.isNotEmptyString(failure["id"]) ? failure["id"] : "failure";
				BX.CrmSubscriber.subscribe(
					this.getId() + '_' + failureId,
					failureButton, "click", BX.delegate(this._onButtonClick, this),
					BX.CrmParamBag.create({ "id": failureId, "preventDefault": true })
				);
			}
			wrapper.appendChild(buttonBlock);

			return wrapper;
		},
		_onButtonClick: function(subscriber, params)
		{
			this._result = subscriber.getSetting("id", "");
			this._executeCallback();
		},
		_executeCallback: function()
		{
			if(this._enableCallback)
			{
				var callback = this.getSetting("callback");
				if(BX.type.isFunction(callback))
				{
					callback(this, { "result": this._result });
				}
			}
		}
	};

	BX.CrmProcessTerminationDialog.create = function(id, settings)
	{
		var self = new BX.CrmProcessTerminationDialog();
		self.initialize(id, settings);
		return self;
	}
}

if(typeof(BX.CrmProcessFailureDialog) === "undefined")
{
	BX.CrmProcessFailureDialog = function()
	{
		this._id = "";
		this._settings = null;
		this._popup = null;
		this._wrapper = null;
		this._callback = null;
		this._enableCallback = true;
		this._value = "";
		this._bid = "";
		this._successInfo = null;
		this._failureInfo = null;
		this._apologyInfos = null;
		this._failureTitle = "";
		this._selectorTitle = "";
		this._radioButtonBlock = null;
		this._popupMenuId = "";
		this._popupMenu = null;
	};

	BX.CrmProcessFailureDialog.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : BX.CrmParamBag.create(null);
			this._callback = this.getSetting("callback", null);
			this._successInfo = this.getSetting("success", null);
			if(!this._successInfo)
			{
				throw "BX.CrmProcessFailureDialog: 'success' setting is not found!";
			}

			this._failureInfo = this.getSetting("failure", null);
			if(!this._failureInfo)
			{
				throw "BX.CrmProcessFailureDialog: 'failure' setting is not found!";
			}

			this._apologyInfos = this.getSetting("apologies", null);
			if(!BX.type.isArray(this._apologyInfos))
			{
				this._apologyInfos = [];
			}

			// Try to setup initial value
			var initValue = this.getSetting("initValue", "");
			if(initValue === "")
			{
				initValue = this._failureInfo["id"];
			}

			this._value = initValue;

			this._failureTitle = this.getSetting("failureTitle", "");
			this._selectorTitle = this.getSetting("selectorTitle", "");

			this._popupMenuId = this._id + "_MENU";
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.getParam(name, defaultval);
		},
		getEntityType: function()
		{
			return this.getSetting("entityType");
		},
		getEntityId: function()
		{
			return this.getSetting("entityId");
		},
		getId: function()
		{
			return this._id;
		},
		getValue: function()
		{
			return this._value;
		},
		setValue: function(val, refresh)
		{
			if(this._value === val)
			{
				return;
			}

			this._value = val;

			if(typeof(refresh) === "undefined")
			{
				// Setup by default
				refresh = true;
			}
			else
			{
				refresh = !!refresh;
			}

			if(refresh)
			{
				var buttons = BX.findChildren(
					this._wrapper,
					{
						"className": "crm-list-fail-deal-button"
					},
					true
				);

				for(var i = 0; i < buttons.length; i++)
				{
					var button = buttons[i];
					button.checked = button.value === val;
				}
			}

			BX.onCustomEvent(this, 'CrmProcessFailureDialogValueChanged', [ this, val ]);
		},
		getSuccessValue: function()
		{
			return this._successInfo["id"];
		},
		getBid: function()
		{
			return this._bid;
		},
		getWrapper: function()
		{
			return this._wrapper;
		},
		open: function()
		{
			if(this._popup)
			{
				this._popup.show();
				return;
			}

			this._popup = new BX.PopupWindow(
				this._id,
				this.getSetting("anchor"),
				{
					"closeByEsc": true,
					"autoHide": true,
					"offsetLeft": -50,
					"closeIcon": true,
					"className": "crm-list-fail-deal",
					"titleBar": { "content": this._prepareTitle() },
					"content": this._prepareContent(),
					"events": { "onPopupClose": BX.delegate(this._onPopupClose, this) },
					"buttons":
					[
						new BX.PopupWindowButton(
							{
								"text": BX.message["JS_CORE_WINDOW_SAVE"],
								"className": "popup-window-button-accept",
								"events":
								{
									"click": BX.delegate(this._onAcceptButtonClick, this)
								}
							}
						),
						new BX.PopupWindowButtonLink(
							{
								"text": BX.message["JS_CORE_WINDOW_CANCEL"],
								"className": "popup-window-button-link-cancel",
								"events":
								{
									"click": BX.delegate(this._onCancelButtonClick, this)
								}
							}
						)
					]
				}
			);
			this._popup.show();
		},
		close: function(enableCallback)
		{
			this._enableCallback = !!enableCallback;
			if(this._popup)
			{
				this._popup.close();
			}
		},
		getFailureTitle: function()
		{
			var result = this._failureTitle;
			if(result == "")
			{
				result = BX.type.isNotEmptyString(this._failureInfo["name"])
					? this._failureInfo["name"] : this._failureInfo["id"];
			}

			return result;
		},
		getSuccessTitle: function()
		{
			return BX.type.isNotEmptyString(this._successInfo["name"])
					? this._successInfo["name"] : this._successInfo["id"];
		},
		_onPopupClose: function()
		{
			this._closePopupMenu();

			if(this._popup)
			{
				this._popup.destroy();
				this._popup = null;
			}

			this._executeCallback();
		},
		_closePopupMenu: function()
		{
			if(this._popupMenu)
			{
				BX.PopupMenu.Data[this._popupMenuId].popupWindow.destroy();
				delete BX.PopupMenu.Data[this._popupMenuId];
				this._popupMenu = null;
			}
		},
		_prepareTitle: function()
		{
			var wrapper = BX.create(
				"DIV",
				{
					"attrs":
					{
						"class": "crm-list-fail-deal-selector-block"
					}
				}
			);

			wrapper.appendChild(
				BX.create("SPAN", { "text": this._selectorTitle + ": " })
			);

			var isSuccess = this._value === this._successInfo["id"];

			this._selector = BX.create(
				"DIV",
				{
					"attrs":
					{
						"class": "crm-list-end-deal-option crm-list-end-deal-option-" + (isSuccess ? "success" : "fail")
					},
					"events":
					{
						"click": BX.delegate(this._onSelectorClick, this)
					},
					"text": isSuccess ? this.getSuccessTitle() : this.getFailureTitle()
				}
			);

			wrapper.appendChild(this._selector);
			return wrapper;
		},
		_prepareContent: function()
		{
			var wrapper = this._wrapper = BX.create(
				"DIV",
				{ "attrs": { "class": "crm-list-fail-deal-block" } }
			);

			var title = this.getSetting("title", "");
			if(title !== "")
			{
				wrapper.appendChild(
					BX.create(
						"DIV",
						{
							"attrs":
							{
								"class": "crm-list-end-deal-text"
							},
							"text": title
						}
					)
				);
			}

			this._radioButtonBlock = BX.create(
				"DIV",
				{
					"attrs":
					{
						"class": "crm-list-end-deal-block-section"
					}
				}
			);

			var infos = [ this._failureInfo ];
			var apologies = this._apologyInfos;
			if(BX.type.isArray(apologies) && apologies.length >0)
			{
				for(var i = 0; i < apologies.length; i++)
				{
					infos.push(apologies[i]);
				}
			}

			for(var j = 0; j < infos.length; j++)
			{
				var radioButtonWrapper = BX.create(
					"DIV",
					{
						"attrs":
						{
							"class": "crm-list-end-deal-button-wrapper"
						}
					}
				);

				var info = infos[j];
				var curInfoId = info["id"];
				var buttonId = this._id + '_' + curInfoId;
				var button = BX.create(
					"INPUT",
					{
						"attrs":
						{
							"id": buttonId,
							"name": this._id,
							"class": "crm-list-fail-deal-button",
							"type": "radio",
							"value": info["id"]
						}
					}
				);

				button.checked = this._value === curInfoId;

				BX.CrmSubscriber.subscribe(
					this._id + "_" + curInfoId,
					button, "change", BX.delegate(this._onRadioButtonClick, this),
					BX.CrmParamBag.create({ "id": curInfoId })
				);


				radioButtonWrapper.appendChild(button);
				radioButtonWrapper.appendChild(
					BX.create(
						"LABEL",
						{
							"attrs":
							{
								"class": "crm-list-fail-deal-button-label",
								"for": buttonId
							},
							"text": BX.type.isNotEmptyString(info["name"]) ? info["name"] : curInfoId
						}
					)
				);

				this._radioButtonBlock.appendChild(radioButtonWrapper);
			}

			if(this._value === this._successInfo["id"] || apologies.length === 0)
			{
				this._radioButtonBlock.style.display = "none";
			}

			wrapper.appendChild(this._radioButtonBlock);

			BX.onCustomEvent(this, 'CrmProcessFailureDialogContentCreated', [ this, wrapper ]);

			return wrapper;
		},
		_onRadioButtonClick: function(subscriber, params)
		{
			this.setValue(subscriber.getSetting("id", ""), false);
		},
		_onAcceptButtonClick: function(e)
		{
			this._bid = "accept";
			this._executeCallback();
		},
		_onCancelButtonClick: function(e)
		{
			this._bid = "cancel";
			this._value = "";
			this._executeCallback();
		},
		_onSelectorClick: function()
		{
			if(this._popupMenu)
			{
				this._closePopupMenu();
				return;
			}

			if(typeof(BX.PopupMenu.Data[this._popupMenuId]) !== "undefined")
			{
				BX.PopupMenu.Data[this._popupMenuId].popupWindow.destroy();
				delete BX.PopupMenu.Data[this._popupMenuId];
			}

			var self = this;
			BX.PopupMenu.show(
				this._popupMenuId,
				this._selector,
				[
					{
						text: this.getFailureTitle(),
						onclick: function()
							{
								self.setValue(self._failureInfo["id"], true);
								if(self._radioButtonBlock.style.display === "none" && self._apologyInfos.length > 0)
								{
									self._radioButtonBlock.style.display = "";
								}
								self._selector.innerHTML = BX.util.htmlspecialchars(self.getFailureTitle());
								BX.removeClass(self._selector, "crm-list-end-deal-option-success");
								BX.addClass(self._selector, "crm-list-end-deal-option-fail");
								self._closePopupMenu();
							}
					},
					{
						text: this.getSuccessTitle(),
						onclick: function()
							{
								self.setValue(self._successInfo["id"], true);
								if(self._radioButtonBlock.style.display !== "none")
								{
									self._radioButtonBlock.style.display = "none";
								}
								self._selector.innerHTML = BX.util.htmlspecialchars(self.getSuccessTitle());
								BX.removeClass(self._selector, "crm-list-end-deal-option-fail");
								BX.addClass(self._selector, "crm-list-end-deal-option-success");
								self._closePopupMenu();
							}
					}
				],
				{
					autoHide: true,
					offsetTop:0,
					offsetLeft:-30
				}
			);

			this._popupMenu = BX.PopupMenu.Data[this._popupMenuId];
		},
		_executeCallback: function()
		{
			if(this._enableCallback)
			{
				var callback = this._callback;
				if(BX.type.isFunction(callback))
				{
					callback(this, { "bid": this._bid, "result": this._value });
				}
			}
		}
	};

	BX.CrmProcessFailureDialog.create = function(id, settings)
	{
		var self = new BX.CrmProcessFailureDialog();
		self.initialize(id, settings);
		return self;
	}
}
