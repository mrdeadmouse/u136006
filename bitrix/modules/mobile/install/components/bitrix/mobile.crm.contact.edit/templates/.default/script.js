if(typeof(BX.CrmContactEditor) === "undefined")
{
	BX.CrmContactEditor = function()
	{
		this._id = '';
		this._settings = {};
		this._prefix = '';
		this._typeId = this._typeName = null;
		this._assignedById = this._assignedByName = null;
		this._companyId = this._companyName = null;
		this._addPhoneBtn = this._addEmailBtn = null;
		this._photo = this._photoId = null;
		this._dispatcher = null;
		this._isDirty = false;
		this._contextMenuId = '';
	};

	BX.CrmContactEditor.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};
			this._prefix = this.getSetting('prefix');
			this._dispatcher = this.getSetting('dispatcher', null);

			this._photo = this.resolveElement('photo');
			if(this._photo)
			{
				BX.bind(this._photo, 'click', BX.delegate(this._onPhotoAdd, this));
			}

			this._typeId = this.resolveElement('type_id');
			if(this._typeId)
			{
				BX.bind(BX.findParent(
					this._typeId,
					{ className: 'crm_block_container' }), 'click', BX.delegate(this._onTypeSelect, this)
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

			this._companyId = this.resolveElement('company_id');
			if(this._companyId)
			{
				BX.bind(BX.findParent(
					this._companyId,
					{ className: 'crm_block_container' }), 'click', BX.delegate(this._onCompanySelect, this)
				);
			}
			this._companyName = this.resolveElement('company_name');

			this._photoId = this.resolveElement('photo_id');

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
				'onCrmStatusSelect',
				BX.delegate(this._onExternalStatusSelect, this)
			);

			BX.addCustomEvent(
				window,
				'onCrmClientSelect',
				BX.delegate(this._onExternalClientSelect, this)
			);

			BX.addCustomEvent(
				window,
				'onOpenPageBefore',
				BX.delegate(this._onBeforePageOpen, this)
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
			var messages = BX.CrmContactEditor.messages;
			return BX.type.isNotEmptyString(messages[name]) ? messages[name] : '';
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
		_onPhotoAdd: function(e)
		{
			var context = BX.CrmMobileContext.getCurrent();
			if(this._contextMenuId !== 'IMAGE_SOURCE')
			{
				context.createMenu(
					[
						{
							//icon: 'edit',
							name: this.getMessage('openPhotoAlbumMenuItem'),
							action: BX.delegate(this._addPhotoFromLibrary, this)
						},
						{
							//icon: 'edit',
							name:  this.getMessage('takePhotoMenuItem'),
							action: BX.delegate(this._addPhotoFromCamera, this)
						}
					]
				);
				this._contextMenuId = 'IMAGE_SOURCE';
			}
			context.showMenu();
		},
		_addPhotoFromLibrary: function()
		{
			BX.CrmMobileContext.getCurrent().hideMenu();
			this._addPhoto(0);
		},
		_addPhotoFromCamera: function()
		{
			BX.CrmMobileContext.getCurrent().hideMenu();
			this._addPhoto(1);
		},
		_addPhoto: function(sourceId)
		{
			var self = this;
			app.takePhoto({
				source: sourceId,
				correctOrientation: true,
				callback: function(fileURI)
				{
					var uploadUrl = self.getSetting('uploadUrl', '');

					if(!BX.type.isNotEmptyString(uploadUrl))
					{
						return;
					}

					function onSuccess(result)
					{
						var response = decodeURIComponent(result.response);
						var info = eval('(' + response + ')');
						if(typeof(info['fileId']) !== 'undefined')
						{
							self._setupPhoto(info);
						}
					}
					function onError(error)
					{
					}

					var options = new FileUploadOptions();
					options.fileKey = 'file';
					var fileName = fileURI.substr(fileURI.lastIndexOf('/') + 1);
					if(fileName.indexOf ('.') < 0)
					{
						fileName += '.jpg';
					}
					options.fileName = fileName;
					options.mimeType = 'image/jpeg';
					options.params = { fullpath: fileURI, name: options.fileName, sessid: BX.bitrix_sessid() };

					options.chunkedMode = false;
					options.headers = { Connection: 'close' };

					var ft = new FileTransfer();
					ft.upload(fileURI, uploadUrl, onSuccess, onError, options, true);
				}
			});
		},
		_setupPhoto: function(info)
		{
			var fileId = typeof(info['fileId']) !== 'undefined' ? parseInt(info['fileId']) : 0;
			if(this._photoId)
			{
				this._photoId.value = fileId;
			}

			var showUrl = typeof(info['showUrl']) !== 'undefined' ? info['showUrl'] : '';
			if(this._photo)
			{
				if(!BX.CrmMobileContext.getCurrent().isAndroid())
				{
					this._photo.src = showUrl;
				}
				else
				{
					//HACK to invalidate image
					var parent = this._photo.parentNode;
					parent.removeChild(this._photo);

					this._photo.src = showUrl;

					parent.appendChild(this._photo);
				}
			}
		},
		_onTypeSelect: function()
		{
			var url = this.getSetting('contactTypeSelectorUrl', '');
			if(url !== '')
			{
				BX.CrmMobileContext.getCurrent().open({ url: url });
			}
		},
		_onCompanySelect: function()
		{
			var url = this.getSetting('companySelectorUrl', '');
			if(url !== '')
			{
				BX.CrmMobileContext.getCurrent().open({ url: url, cache: this.getEntityId() > 0 });
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
				'PHOTO': this.getFieldValue('PHOTO_ID'),
				'NAME': this.getFieldValue('NAME'),
				'SECOND_NAME': this.getFieldValue('SECOND_NAME'),
				'LAST_NAME': this.getFieldValue('LAST_NAME'),
				'ADDRESS': this.getFieldValue('ADDRESS'),
				'ADDRESS_2': this.getFieldValue('ADDRESS_2'),
				'ADDRESS_CITY': this.getFieldValue('ADDRESS_CITY'),
				'ADDRESS_REGION': this.getFieldValue('ADDRESS_REGION'),
				'ADDRESS_PROVINCE': this.getFieldValue('ADDRESS_PROVINCE'),
				'ADDRESS_POSTAL_CODE': this.getFieldValue('ADDRESS_POSTAL_CODE'),
				'ADDRESS_COUNTRY': this.getFieldValue('ADDRESS_COUNTRY'),
				'COMMENTS': this.getFieldValue('COMMENTS'),
				'TYPE_ID': this.getFieldValue('TYPE_ID'),
				'ASSIGNED_BY_ID': this.getFieldValue('ASSIGNED_BY_ID')
			};

			var companyId = this.getFieldValue('COMPANY_ID');
			if(companyId !== '')
			{
				data['COMPANY_ID'] = companyId;
			}

			this._saveMultiFields('PHONE', data);
			this._saveMultiFields('EMAIL', data);

			if(entityId <= 0)
			{
				this._dispatcher.createEntity(
					data,
					BX.CrmMobileContext.getCurrent().createCloseHandler(),
					{
						contextId: this.getSetting('contextId', ''),
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
						contextId: this.getSetting('contextId', ''),
						title: this.getSetting('title', '')
					}
				);
			}
		},
		_onExternalUpdate: function(eventArgs)
		{
			var typeName = typeof(eventArgs['typeName']) !== 'undefined' ? eventArgs['typeName'] : '';
			var id = typeof(eventArgs['id']) !== 'undefined' ? parseInt(eventArgs['id']) : 0;
			var senderId = typeof(eventArgs['senderId']) !== 'undefined' ? eventArgs['senderId'] : '';

			if(typeName === 'CONTACT' && id === this.getEntityId() && senderId !== this._dispatcher.getId())
			{
				this._isDirty = true;
			}
		},
		_onExternalDelete: function(eventArgs)
		{
			var typeName = typeof(eventArgs['typeName']) !== 'undefined' ? eventArgs['typeName'] : '';
			var id = typeof(eventArgs['id']) !== 'undefined' ? parseInt(eventArgs['id']) : 0;

			if(typeName === 'CONTACT' && id === this.getEntityId())
			{
				BX.CrmMobileContext.getCurrent().close();
			}
		},
		_onExternalStatusSelect: function(eventArgs)
		{
			var contextId = typeof(eventArgs['contextId']) ? eventArgs['contextId'] : '';
			if(contextId !== this.getSetting('contextId', ''))
			{
				return;
			}

			var typeId = typeof(eventArgs['typeId']) ? eventArgs['typeId'] : '';
			var statusId = typeof(eventArgs['statusId']) ? eventArgs['statusId'] : '';
			var name = typeof(eventArgs['name']) ? eventArgs['name'] : '';

			if(typeId === 'CONTACT_TYPE')
			{
				if(this._typeId)
				{
					this._typeId.value = statusId;
				}

				if(this._typeName)
				{
					this._typeName.innerHTML = BX.util.htmlspecialchars(name !== '' ? name : statusId);
				}
			}
		},
		_onExternalClientSelect: function(eventArgs)
		{
			var contextId = typeof(eventArgs['contextId']) ? eventArgs['contextId'] : '';
			if(contextId !== this.getSetting('contextId', ''))
			{
				return;
			}

			var typeName = typeof(eventArgs['typeName']) ? eventArgs['typeName'] : '';
			if(typeName !== 'COMPANY')
			{
				return;
			}

			var id = typeof(eventArgs['id']) ? parseInt(eventArgs['id']) : 0;
			var caption = typeof(eventArgs['caption']) ? eventArgs['caption'] : '';

			if(this._companyId)
			{
				this._companyId.value = id;
			}

			if(this._companyName)
			{
				this._companyName.innerHTML = BX.util.htmlspecialchars(caption !== '' ? caption : id);
			}
		},
		_onBeforePageOpen: function()
		{
			if(this._isDirty)
			{
				this._isDirty = false;
				BX.CrmMobileContext.getCurrent().reload();
			}
		}
	};

	if(typeof(BX.CrmContactEditor.messages) === 'undefined')
	{
		BX.CrmContactEditor.messages = {};
	}

	BX.CrmContactEditor.items = {};
	BX.CrmContactEditor.create = function(id, settings)
	{
		var self = new BX.CrmContactEditor();
		self.initialize(id, settings);
		this.items[id] = self;
		return self;
	};
}
