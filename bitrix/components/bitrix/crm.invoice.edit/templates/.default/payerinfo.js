if (typeof BX.CrmInvoicePropertiesDialog === "undefined")
{
	BX.CrmInvoicePropertiesDialog = function (settings)
	{
		this.settings = settings;
		this.random = Math.random().toString().substring(2);

		// init values
		for (var i in this.settings["personTypes"])
		{
			for (var j in this.settings[this.settings["personTypes"][i]])
			{
				this.settings[this.settings["personTypes"][i]][j]["VALUE"] = "";
			}
		}

		var self = this;
		var payerInfoEditLink = BX(this.settings["PAYER_INFO_EDIT_LINK_ID"]);
		if (payerInfoEditLink)
			BX.bind(payerInfoEditLink, "click", function(){self.onPayerInfoEdit()});
		BX.addCustomEvent("CrmInvoiceChangePersonType", function(personTypeName){self.onChangePersonType(personTypeName)});
	};

	BX.CrmInvoicePropertiesDialog.prototype = {
		"show": function ()
		{
			var self = this;
			if (this.settings["personTypeId"] <= 0)
				return;

			var content = BX.create(
				"TABLE",
				{
					"style": { "marginLeft": "12px", "marginTop": "12px", "marginRight": "12px", "marginBottom": "12px" },
					"attrs": {"id": this.random + "_table", "cellspacing": "7"}
				}
			);

			var itemParams = null;
			var itemSettings = this.settings[this.settings["personTypeId"]];
			var valueControl, size1, size2;
			for (var i in itemSettings)
			{
				itemParams = {
					"children":
						[
							BX.create("TD", {"children": [BX.create("SPAN", {"text": itemSettings[i]["NAME"] + ":"})]})
						]
				};
				valueControl = null;
				//size1 = parseInt(itemSettings[i]["SIZE1"]);
				//size2 = parseInt(itemSettings[i]["SIZE2"]);
				size1 = 40;
				size2 = 4;
				switch (itemSettings[i]["TYPE"])
				{
					case "TEXTAREA":
						//if (size1 <= 0) size1 = 40;
						//if (size2 <= 0) size2 = 4;
						//valueControl = BX.create("TEXTAREA", {"style": {"marginLeft": "10px"}, "attrs": {"cols": size1, "rows": size2}, "text": BX.util.htmlspecialchars(itemSettings[i]["VALUE"])});
						valueControl = BX.create("INPUT", {"style": {"marginLeft": "10px"}, "attrs": {"id": this.random + "_" + itemSettings[i]["ID"], "className": "bx-crm-edit-input", "type": "text", "value": itemSettings[i]["VALUE"], "size": size1}});
						break;
					case "TEXT":
						//if (size1 <= 0) size1 = 30;
						valueControl = BX.create("INPUT", {"style": {"marginLeft": "10px"}, "attrs": {"id": this.random + "_" + itemSettings[i]["ID"], "className": "bx-crm-edit-input", "type": "text", "value": itemSettings[i]["VALUE"], "size": size1}});
						break;
					default:
						//if (size1 <= 0) size1 = 30;
						valueControl = BX.create("INPUT", {"style": {"marginLeft": "10px"}, "attrs": {"id": this.random + "_" + itemSettings[i]["ID"], "className": "bx-crm-edit-input", "type": "text", "value": itemSettings[i]["VALUE"], "size": size1}});
						break;
				}
				itemParams.children.push(BX.create("TD", {"children": [valueControl]}));
				content.appendChild(BX.create("TR", itemParams));
			}

			var popup = new BX.PopupWindow(
				"crmInvoicePropetiesDialog_" + this.random,
				BX(this.settings["PAYER_INFO_FIELD_ID"]),
				{
					overlay: {opacity: 10},
					autoHide: false,
					draggable: true,
					offsetLeft: 0,
					offsetTop: 0,
					bindOptions: { forceBindPosition: false },
					closeByEsc: true,
					closeIcon: { top: '10px', right: '15px' },
					"titleBar":
					{
						"content": BX.create("SPAN", { "text": this.settings["messages"]["TITLE"] })
					},
					events:
					{
						onPopupClose: function(){ if(popup) popup.destroy(); }
					},
					"content": content,
					"buttons": [
						new BX.PopupWindowButton(
							{
								"text": this.settings["messages"]["SAVE"],
								"className": "popup-window-button-accept",
								"events":
								{
									"click": function() {
										if (popup)
										{
											if (content)
											{
												self.saveValues();
												self.rewritePayerInfoString();
											}
											popup.close();
										}
									}
								}
							}
						),
						new BX.PopupWindowButtonLink(
							{
								"text": this.settings["messages"]["CANCEL"],
								"className": "popup-window-button-link-cancel",
								"events":
								{
									"click": function() {
										if (popup) popup.close();
									}
								}
							}
						)
					]
				}
			);
			popup.show();
		},
		"parseValues": function()
		{
			var propsBlock = BX(this.settings["INVOICE_PROPS_DIV_ID"]);
			if (propsBlock)
			{
				var prInput = BX.findChildren(propsBlock, {"tag": "input", "attr": {"type": "hidden"}}, false);
				var values = {};
				var prId = null;
				for (var i in prInput)
				{
					if (prInput[i].name.toString().substring(0, 11) === "PR_INVOICE_")
					{
						prId = parseInt(prInput[i].name.toString().substring(11));
						values[prId] = prInput[i].value;
					}
				}
				var valueIndex = null;
				for (var i in this.settings["personTypes"])
				{
					for (var j in this.settings[this.settings["personTypes"][i]])
					{
						valueIndex = this.settings[this.settings["personTypes"][i]][j]["ID"];
						this.settings[this.settings["personTypes"][i]][j]["VALUE"] = (values[valueIndex]) ? values[valueIndex] : "";
					}
				}
			}
		},
		"saveValues": function()
		{
			var valuesBlock = BX(this.random + "_table");
			if (valuesBlock)
			{
				var valInput = BX.findChildren(valuesBlock, {"tag": "input", "attr": {"type": "text"}}, true);

				if (valInput)
				{
					var values = {};
					var valId = null;
					var i, j;
					var valueIndex = null;

					// parse values
					for (i = 0; i < valInput.length; i++)
					{
						if (valInput[i].id.toString().substring(0, this.random.length + 1) === this.random + "_")
						{
							valId = parseInt(valInput[i].id.toString().substring(this.random.length + 1));
							values[valId] = valInput[i].value;
						}
					}

					// update settings
					for (i in this.settings["personTypes"])
					{
						for (j in this.settings[this.settings["personTypes"][i]])
						{
							valueIndex = this.settings[this.settings["personTypes"][i]][j]["ID"];
							this.settings[this.settings["personTypes"][i]][j]["VALUE"] = (values[valueIndex]) ? values[valueIndex] : "";
						}
					}

					// update inputs
					var propsBlock = BX(this.settings["INVOICE_PROPS_DIV_ID"]);
					if (propsBlock)
					{
						var prInput = BX.findChildren(propsBlock, {"tag": "input", "attr": {"type": "hidden"}}, false);
						var prId = null;
						for (i in prInput)
						{
							if (prInput[i].name.toString().substring(0, 11) === "PR_INVOICE_")
							{
								prId = parseInt(prInput[i].name.toString().substring(11));
								prInput[i].value = (values[prId]) ? values[prId] : "";
							}
						}
					}
				}
			}
		},
		"rewritePayerInfoString": function()
		{
			var payerInfoField = BX(this.settings["PAYER_INFO_FIELD_ID"]);
			if (payerInfoField)
			{
				var payerInfoString = "";
				var personTypeId = parseInt(this.settings["personTypeId"]);
				var propId;
				if (personTypeId > 0)
				{
					var index = 0;
					for (var i in this.settings[personTypeId])
					{
						propId = this.settings[personTypeId][i]["ID"];
						if (this.settings[personTypeId][i]["VALUE"])
						{
							if (index++ > 0)
								payerInfoString += ", ";
							payerInfoString += this.settings[personTypeId][i]["VALUE"];
						}
					}
				}
				payerInfoField.innerHTML = BX.util.htmlspecialchars(payerInfoString);
			}
		},
		"onPayerInfoEdit": function()
		{
			this.parseValues();
			this.show();
		},
		"onChangePersonType": function(personTypeName)
		{
			if (personTypeName === "COMPANY" || personTypeName === "CONTACT")
				this.settings["personTypeId"] = parseInt(this.settings["personTypes"][personTypeName]);
			else
				this.settings["personTypeId"] = 0;
		}
	};
}
