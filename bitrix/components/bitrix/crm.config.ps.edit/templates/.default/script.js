BX.crmPaySys = {
	formId: '',
	orderProps: {},
	orderFields: {},
	userProps: {},
	userFields: {},
	simpleMode: true,

	init: function(params)
	{
		for(var key in params)
		{
			if(params.hasOwnProperty(key))
			{
				this[key] = params[key];
			}
		}

		this.formObj = BX(this.formId);
	},
	switchMode: function()
	{
		var switcher = BX("MODE_SWITCHER");

		if(BX.crmPaySys.simpleMode)
		{
			BX.crmPSPropType.showItems();
			BX.crmPSActionFile.showNoneSimpleRows();
			switcher.innerHTML = BX.message("CRM_PS_HIDE_FIELDS");
		}
		else
		{
			BX.crmPSPropType.hideItems();
			BX.crmPSActionFile.hideNoneSimpleRows();
			switcher.innerHTML = BX.message("CRM_PS_SHOW_FIELDS");
		}

		BX.crmPaySys.simpleMode = !BX.crmPaySys.simpleMode;
	}
};

BX.crmPSPersonType = {

	init: function(params)
	{
		for(var key in params)
			this[key] = params[key];

		var persTypeSelector = BX.crmPaySys.formObj["PERSON_TYPE_ID"];
		if(persTypeSelector)
			BX.bind(persTypeSelector, 'change', BX.delegate(BX.crmPSPersonType.onSelect, this));
	},

	getId: function()
	{
		return BX.crmPaySys.formObj["PERSON_TYPE_ID"].value;
	},

	onSelect: function()
	{
		var opFNames = this.getOrderPropsFNames();
		this.replaceOrderPropsSelectors(opFNames);
	},

	getOrderPropsFNames: function()
	{
		var retNames = [];

		for (var i = 0, l = BX.crmPaySys.formObj.elements.length; i< l; i++)
		{
			if(!/^TYPE_/.test(BX.crmPaySys.formObj.elements[i].name))
				continue;

			if(BX.crmPaySys.formObj.elements[i].value != "PROPERTY")
				continue;

			retNames.push(BX.crmPaySys.formObj.elements[i].name.replace(/^TYPE_/, ""));
		}

		return retNames;
	},

	replaceOrderPropsSelectors: function(opFNames)
	{
		var persTypeId = this.getId();

		for(var i = 0, l = opFNames.length; i< l; i++)
			BX.crmPSPropType.insertOptions(BX.crmPaySys.formObj["VALUE1_"+opFNames[i]], BX.crmPaySys.orderProps[persTypeId]);
	}
};

BX.crmPSPropType = {
	init: function(params)
	{
		for(var key in params)
			this[key] = params[key];

		this.bindEvents();
	},

	bindEvents: function()
	{
		var onSelectFunc = function(){ var selObj = this; BX.delegate(BX.crmPSPropType.onSelect(selObj), this); };

		for (var i = 0, l = BX.crmPaySys.formObj.elements.length; i< l; i++)
		{
			if(!/^TYPE_/.test(BX.crmPaySys.formObj.elements[i].name))
				continue;

			var typeSelector = BX.crmPaySys.formObj.elements[i];

			if(typeSelector)
				BX.bind(typeSelector, 'change', onSelectFunc);
		}
	},

	onSelect: function(selObj)
	{
		this.replaceValueField(selObj);
	},

	replaceValueField: function(selObj)
	{
		var fieldName = selObj.name.replace(/^TYPE_/, "");

		if(selObj.value == "USER")
			this.setUserProps(fieldName);
		else if(selObj.value == "ORDER")
			this.setOrderFields(fieldName);
		else if(selObj.value == "PROPERTY")
			this.setOrderProps(fieldName);
		else if(selObj.value == "")
			this.setOtherProps(fieldName);
	},

	setUserProps: function(fieldName)
	{
		this.insertOptions(BX.crmPaySys.formObj["VALUE1_"+fieldName], BX.crmPaySys.userProps);
		this.showSelectorField(fieldName);
	},

	setOrderFields: function(fieldName)
	{
		this.insertOptions(BX.crmPaySys.formObj["VALUE1_"+fieldName], BX.crmPaySys.orderFields);
		this.showSelectorField(fieldName);
	},

	setOrderProps: function(fieldName)
	{
		var persTypeId = BX.crmPSPersonType.getId();
		var props = BX.crmPaySys.orderProps[persTypeId];

		var actionFileId = BX.crmPSActionFile.getId();
		var matches = (/^([a-z]+)(?:_([a-z]+))?$/i).exec(actionFileId);
		var actionName = "";
		if(BX.type.isArray(matches) && matches.length > 1)
		{
			actionName = matches[1];
		}

		if(actionName !== "" && typeof(BX.crmPaySys.userFields[actionName]) !== "undefined")
		{
			props = BX.clone(props);
			var userFields = BX.crmPaySys.userFields[actionName];
			for(var k in userFields)
			{
				if(!userFields.hasOwnProperty(k))
				{
					continue;
				}
				props[k] = userFields[k];
			}
		}

		this.insertOptions(BX.crmPaySys.formObj["VALUE1_" + fieldName], props);
		this.showSelectorField(fieldName);
	},

	setOtherProps: function(fieldName)
	{
		BX.crmPaySys.formObj["VALUE2_"+fieldName].value = '';
		this.showInputField(fieldName);
	},

	showSelectorField: function(fieldName, bHide)
	{
		var bShow = !bHide;
		BX.crmPaySys.formObj["VALUE1_"+fieldName].style.display = bShow ? '' : 'none';
		BX.crmPaySys.formObj["VALUE2_"+fieldName].style.display = bShow ? 'none' : '';
	},

	showInputField: function(fieldName)
	{
		this.showSelectorField(fieldName, true);
	},

	insertOptions: function(selObj, oItems)
	{
		var oldVal = selObj.value;

		selObj.options.length = 0;

		for(var property in oItems)
		{
			var option=document.createElement("option"); //todo: make clone
			option.value=property;
			option.text=oItems[property];
			try
			{
				selObj.add(option, null);
			}
			catch(ex)
			{
				selObj.add(option);
			}
		}

		selObj.value = oldVal;
	},

	hideItems: function(bShow)
	{
		bHide = !bShow;

		for (var i = 0, l = BX.crmPaySys.formObj.elements.length; i< l; i++)
		{
			if(!/^TYPE_/.test(BX.crmPaySys.formObj.elements[i].name))
				continue;

			var typeSelector = BX.crmPaySys.formObj.elements[i];

			if(typeSelector && typeSelector.value != 'SELECT' && typeSelector.value != 'FILE')
				typeSelector.style.display = bHide ? 'none' : '';
		}
	},

	showItems: function()
	{
		this.hideItems(true);
	}
};

BX.crmPSActionFile = {

	arFields: {},
	arFieldsList: {},
	arSavedFields: {},
	typeValuesTmpl: '',
	fileValuesTmpl: '',
	selectValuesTmpl: '',

	init: function(params)
	{
		for(var key in params)
			this[key] = params[key];

		var actFileSelector = BX.crmPaySys.formObj["ACTION_FILE"];
		if (actFileSelector)
			BX.bind(actFileSelector, 'change', BX.delegate(BX.crmPSActionFile.onSelect, this));

		if (BX.crmPaySys.simpleMode)
			this.hideNoneSimpleRows();
	},

	getId: function()
	{
		return BX.crmPaySys.formObj["ACTION_FILE"].value;
	},

	onSelect: function()
	{
		this.getAjaxFields();
	},

	getAjaxFields: function(params)
	{
		var actionFile = this.getId();

		if (this.arFields[actionFile])
		{
			this.insertFields(actionFile, this.arFields[actionFile]);

			if (this.arFieldsList[actionFile])
				this.setFieldsList(this.arFieldsList[actionFile]);

			return;
		}

		data = {
			'id': actionFile,
			'action': 'get_fields',
			'person_type': BX.crmPSPersonType.getId(),
			'sessid': BX.bitrix_sessid()
		};

		BX.ajax({
			data: data,
			method: 'POST',
			dataType: 'json',
			url: BX.crmPaySys.url+'/ajax.php',
			onsuccess: BX.delegate(function(result) {

										if(result.ERROR)
											BX.debug("BX.crmPSActionFile.getAjaxFields: "+result.ERROR);
										else
										{
											if(result.FIELDS)
											{
												this.arFields[actionFile] = result.FIELDS;
												this.insertFields(actionFile, result.FIELDS);
											}

											if(result.FIELDS_LIST)
											{
												this.arFieldsList[actionFile] = result.FIELDS_LIST;
												this.setFieldsList(result.FIELDS_LIST);
											}
										}
									}, this
						),
			onfailure: function() {BX.debug('onfailure: BX.crmPSActionFile.getAjaxFields');}
		});
	},

	saveFields: function()
	{
		for (var i = 0, l = BX.crmPaySys.formObj.elements.length; i < l; i++)
		{
			if(!/^TYPE_/.test(BX.crmPaySys.formObj.elements[i].name))
				continue;

			var fName = BX.crmPaySys.formObj.elements[i].name.replace(/^TYPE_/, "");

			this.arSavedFields[fName] = {};

			this.arSavedFields[fName].TYPE = BX.crmPaySys.formObj["TYPE_"+fName].value;
			switch (this.arSavedFields[fName].TYPE)
			{
				case 'FILE':
					var fieldPreview = BX(fName+'_preview_img');
					if (fieldPreview)
					{
						this.arSavedFields[fName].SRC    = fieldPreview.getAttribute('src');
						this.arSavedFields[fName].HEIGHT = fieldPreview.getAttribute('height');
						this.arSavedFields[fName].WIDTH  = fieldPreview.getAttribute('width');
					}
					break;
				case 'SELECT':
					var value1 = BX("VALUE1_"+fName);
					var options = {};
					for (var index = 0; index < value1.options.length; index++)
						options[value1.options[index].value] = value1.options[index].text;
					this.arSavedFields[fName].VALUE1 = value1.value;
					this.arSavedFields[fName].OPTIONS = options;
					break;
				default:
					var value1 = BX("VALUE1_"+fName);
					var value2 = BX("VALUE2_"+fName);
					this.arSavedFields[fName].VALUE1 = value1.value;
					if (value2)
						this.arSavedFields[fName].VALUE2 = value2.value;
			}
		}
	},

	restoreFields: function()
	{
		if (this.arSavedFields)
		{
			for (var fName in this.arSavedFields)
			{
				var type = BX("TYPE_"+fName);

				if (type)
					type.value = this.arSavedFields[fName]["TYPE"]

				switch (type.value)
				{
					case 'FILE':
						var fieldPreview = BX(fName+'_preview_img');
						if (fieldPreview && this.arSavedFields[fName] && this.arSavedFields[fName].SRC)
						{
							fieldPreview.setAttribute('src', this.arSavedFields[fName].SRC);
							fieldPreview.setAttribute('height', this.arSavedFields[fName].HEIGHT);
							fieldPreview.setAttribute('width', this.arSavedFields[fName].WIDTH);
						}
						else
						{
							BX(fName + '_preview').style.display = "none";
						}
						break;
					case 'SELECT':
						var value1 = BX("VALUE1_"+fName);
						if (this.arSavedFields[fieldId])
						{
							BX.crmPSPropType.insertOptions(value1, this.arSavedFields[fName].OPTIONS);
							value1.value = this.arSavedFields[fName].VALUE1;
						}
						else
						{
							value1.value = 0;
						}
						break;
					default:
						var value1 = BX("VALUE1_"+fName);
						var value2 = BX("VALUE2_"+fName);
						value1.value = this.arSavedFields[fName]["VALUE1"];
						if (value2)
							value2.value = this.arSavedFields[fName]["VALUE2"];
				}
			}
		}
	},

	insertFields: function(actId, arFields)
	{
		this.saveFields();
		this.removeFields();

		var propsTable = BX.crmPaySys.formObj["ACTION_FILE"].parentNode.parentNode.parentNode,
			row = false;

		for (var fieldId in arFields)
		{
			row = this.makeRow(fieldId, arFields[fieldId]);
			propsTable.insertBefore(row, propsTable.lastElementChild);

			var typeSelector = BX("TYPE_"+fieldId);

			if (!typeSelector)
				continue;

			typeSelector.value = this.arSavedFields[fieldId] && this.arSavedFields[fieldId].TYPE ? this.arSavedFields[fieldId].TYPE : arFields[fieldId].TYPE;
			BX.crmPSPropType.replaceValueField(typeSelector);

			if (BX.crmPaySys.simpleMode)
			{
				if (typeSelector.value != '' && typeSelector.value != 'SELECT' && typeSelector.value != 'FILE')
					row.style.display = 'none';

				typeSelector.style.display = 'none';
			}

			switch (typeSelector.value)
			{
				case 'FILE':
					var fieldPreview = BX(fieldId+'_preview_img');
					if (fieldPreview && this.arSavedFields[fieldId] && this.arSavedFields[fieldId].SRC)
					{
						fieldPreview.setAttribute('src', this.arSavedFields[fieldId].SRC);
						fieldPreview.setAttribute('height', this.arSavedFields[fieldId].HEIGHT);
						fieldPreview.setAttribute('width', this.arSavedFields[fieldId].WIDTH);
					}
					else
					{
						BX(fieldId + '_preview').style.display = "none";
					}
					break;
				case 'SELECT':
					var valueField = BX("VALUE1_"+fieldId);
					if (this.arSavedFields[fieldId])
					{
						BX.crmPSPropType.insertOptions(valueField, this.arSavedFields[fieldId].OPTIONS);
						valueField.value = this.arSavedFields[fieldId].VALUE1;
					}
					else
					{
						valueField.value = 0;
					}
					break;
				case '':
					var valueField = BX("VALUE2_"+fieldId);
					if (valueField && valueField != '')
					{
						valueField.value = this.arSavedFields[fieldId] && this.arSavedFields[fieldId].VALUE2
							? this.arSavedFields[fieldId].VALUE2
							: '';
					}
					break;
				default:
					var valueField = BX("VALUE1_"+fieldId);
					if (valueField && valueField != '')
					{
						valueField.value = this.arSavedFields[fieldId] && this.arSavedFields[fieldId].VALUE1
							? this.arSavedFields[fieldId].VALUE1
							: arFields[fieldId].VALUE;
					}
			}
		}

		BX.crmPSPropType.bindEvents();
	},

	makeRow: function(fieldId, arField)
	{
		var row = document.createElement("tr"),
			fieldName = document.createElement("td"),
			fieldValue = document.createElement("td");

		fieldName.className = "bx-field-name bx-padding";
		fieldName.title = arField.DESCR;
		fieldName.innerHTML = arField.NAME+":";
		row.appendChild(fieldName);

		var valueHtml = '';
		switch (arField.TYPE)
		{
			case 'FILE':
				valueHtml = this.fileValuesTmpl.replace(/\#FIELD_ID\#/g, fieldId);
				break;
			case 'SELECT':
				valueHtml = this.selectValuesTmpl.replace(/\#FIELD_ID\#/g, fieldId);
				break;
			default:
				valueHtml = this.typeValuesTmpl.replace(/\#FIELD_ID\#/g, fieldId);
		}

		fieldValue.className = "bx-field-value";
		fieldValue.innerHTML = valueHtml;
		row.appendChild(fieldValue);
		return row;
	},

	removeFields: function()
	{
		var firstFieldRow = BX.crmPaySys.formObj["ACTION_FILE"].parentNode.parentNode.nextElementSibling.nextElementSibling,
			nextRow = firstFieldRow;

		do
		{
			tmpRow = nextRow.nextElementSibling;
			nextRow.parentNode.removeChild(nextRow);
			nextRow = tmpRow;
		}
		while(nextRow && nextRow.className != " bx-bottom");


	},

	setFieldsList: function(list)
	{
		var actListObj = BX.crmPaySys.formObj["PS_ACTION_FIELDS_LIST"];

		if(actListObj)
			actListObj.value = list;
	},

	hideNoneSimpleRows: function(bShow)
	{
		bHide = !bShow;

		for (var i = 0, l = BX.crmPaySys.formObj.elements.length; i< l; i++)
		{
			if(!/^TYPE_/.test(BX.crmPaySys.formObj.elements[i].name))
				continue;

			var typeSelector = BX.crmPaySys.formObj[BX.crmPaySys.formObj.elements[i].name];

			if(!typeSelector || typeSelector.value == '' || typeSelector.value == 'SELECT' || typeSelector.value == 'FILE')
				continue;

			var propsTable = typeSelector.parentNode.parentNode;

			if(propsTable)
				propsTable.style.display = bHide ? 'none' : '';
		}
	},
	showNoneSimpleRows: function()
	{
		this.hideNoneSimpleRows(true);
	}
};