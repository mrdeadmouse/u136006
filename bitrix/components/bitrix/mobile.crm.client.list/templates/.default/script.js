// LIST VIEW -->
if(typeof(BX.CrmClientListView) === "undefined")
{
	BX.CrmClientListView = function()
	{
		this._selectedItem = null;
		this._pageAfterOpenHandler = BX.delegate(this._onAfterPageOpen, this);
		this._externalModel = null;
		this._isDirty = false;
	};
	BX.extend(BX.CrmClientListView, BX.CrmEntityListView);
	BX.CrmClientListView.prototype.doInitialize = function()
	{
		BX.addCustomEvent("onOpenPageAfter", this._pageAfterOpenHandler);
	};
	BX.CrmClientListView.prototype.getContainer = function()
	{
		return this._container ? this._container : BX.findChild(this._wrapper, { className: "crm_list_tel_list" }, true, false);
	};
	BX.CrmClientListView.prototype.getItemContainers = function()
	{
		return BX.findChild(this.getContainer(), { className: "crm_list_tel" }, true, true);
	};
	BX.CrmClientListView.prototype.getWaiterClassName = function()
	{
		return "crm_list_tel_wait";
	};
	BX.CrmClientListView.prototype.createModel = function(data, register)
	{
		var d = this.getDispatcher();
		return d ? d.createEntityModel(data, this.getEntityType(), register) : null;
	};
	BX.CrmClientListView.prototype.createItemView = function(settings)
	{
		settings["enableQuickSelect"] = this.getSetting("enableQuickSelect", false);
		if(typeof(settings["escalation"]) === "undefined")
		{
			settings["escalation"] = this.getEscalation();
		}

		var entityType = this.getEntityType();
		if(entityType === BX.CrmContactModel.typeName)
		{
			return BX.CrmClientContactListItemView.create(settings);
		}
		else if(entityType === BX.CrmCompanyModel.typeName)
		{
			return BX.CrmClientCompanyListItemView.create(settings);
		}
		return null;
	};
	BX.CrmClientListView.prototype.getEscalation = function()
	{
		return parseInt(this.getSetting("escalation", 2));
	};
	BX.CrmClientListView.prototype.setSelectedItem = function(item)
	{
		if(this._selectedItem === item)
		{
			return;
		}

		if(this._selectedItem)
		{
			this._selectedItem.setSelected(false);
		}

		this._selectedItem = item;

		if(this._selectedItem)
		{
			this._selectedItem.setSelected(true);
		}
	};
	BX.CrmClientListView.prototype.notifyItemSelected = function(item, relatedItem)
	{
		var m = item.getModel();
		if(!m)
		{
			return;
		}

		var context = BX.CrmMobileContext.getCurrent();
		var eventArgs =
		{
			id: m.getId(),
			caption: m.getCaption(),
			typeName: m.getTypeName(),
			contextId: this.getContextId()
		};

		if(relatedItem)
		{
			m = relatedItem.getModel();
			if(m)
			{
				eventArgs["related"] =
					{
						id: m.getId(),
						caption: m.getCaption(),
						typeName: m.getTypeName()
					};
			}
		}

		context.riseEvent("onCrmClientSelect", eventArgs, this.getEscalation());
		window.setTimeout(context.createBackHandler(), 0);
	};
	BX.CrmClientListView.prototype.getContextId = function()
	{
		return this.getSetting("contextId", "");
	};
	BX.CrmClientListView.prototype.getEntityType = function()
	{
		return this.getSetting("entityType")
	};
	BX.CrmClientListView.prototype._processExternalCreate = function(eventArgs)
	{
		var data = typeof(eventArgs["data"]) !== "undefined" ? eventArgs["data"] : null;
		if(data)
		{
			this._externalModel = BX.CrmEntityDispatcher.constructEntityModel(data, this.getEntityType());
		}
	};
	BX.CrmClientListView.prototype._onBeforeItemDelete = function(item)
	{
		if(item && item === this._selectedItem)
		{
			this._selectedItem.setSelected(false);
			this._selectedItem = null;
		}
	};
	BX.CrmClientListView.prototype._onAfterPageOpen = function()
	{
		if(this._externalModel)
		{
			var m = this._externalModel;
			BX.CrmMobileContext.getCurrent().riseEvent(
				"onCrmClientSelect",
				{
					typeName: m.getTypeName(),
					id: m.getId(),
					caption: m.getCaption(),
					contextId: this.getContextId()
				},
				2
			);
			this._externalModel = null;
			this._isDirty = true;
			BX.CrmMobileContext.getCurrent().back();
			return;
		}

		if(this._isDirty)
		{
			if(this.reload(this.getSetting("reloadUrl", true)))
			{
				this._isDirty = false;
			}
		}
	};
	BX.CrmClientListView.prototype.createSearchParams = function(val)
	{
		var entityType = this.getEntityType();
		if(entityType === "CONTACT")
		{
			return { FULL_NAME: val };
		}
		else if(entityType === "COMPANY")
		{
			return { TITLE: val };
		}
		return null;
	};
	BX.CrmClientListView.prototype.openInnerList = function(item, entityType)
	{
		var listContainer = BX.create("DIV",
			{
				attrs: { className: "crm_company_list" },
				style: { margin: "15px", padding: "10px", display: "none" }
			}
		);
		var itemContainer = item.getContainer();
		itemContainer.appendChild(listContainer);

		var searchParams = {};
		var m = item.getModel();
		var ownerEntityType = m.getTypeName();
		if(ownerEntityType === BX.CrmContactModel.typeName)
		{
			searchParams["ID"] = m.getIntParam("COMPANY_ID");
		}
		else if(ownerEntityType === BX.CrmCompanyModel.typeName)
		{
			searchParams["COMPANY_ID"] = m.getId();
		}

		var listManager = BX.CrmClientListManager.getCurrent();
		return BX.CrmInnerClientListView.create(
			this.getId() + "_" + entityType.toLowerCase(),
			{
				dispatcher: listManager.resolveDispatcher(entityType),
				container: listContainer,
				entityType: entityType,
				searchPageUrl: listManager.resolveSearchUrl(entityType),
				searchParams: searchParams,
				parentItem: item,
				parentList: this
			}
		);
	};
	BX.CrmClientListView.items = {};
	BX.CrmClientListView.create = function(id, settings)
	{
		var self = new BX.CrmClientListView();
		self.initialize(id, settings);
		this.items[id] = self;

		return self;
	};
}
// <-- LIST VIEW

// --> INNER VIEW
if(typeof(BX.CrmInnerClientListView) === "undefined")
{
	BX.CrmInnerClientListView = function()
	{
		this._selectedItem = this._parentList = this._parentItem = this._tail = null;
	};
	BX.extend(BX.CrmInnerClientListView, BX.CrmEntityListView);
	BX.CrmInnerClientListView.prototype.doInitialize = function()
	{
		this._parentList = this.getSetting("parentList");
		if(!this._parentList)
		{
			throw "BX.CrmInnerClientListView: Could not find parent list.";
		}

		this._parentItem = this.getSetting("parentItem");
		if(!this._parentItem)
		{
			throw "BX.CrmInnerClientListView: Could not find parent item.";
		}

		this._beginSearchRequest(this.getSetting("searchParams"));
	};
	BX.CrmInnerClientListView.prototype.getEntityType = function()
	{
		return this.getSetting("entityType", "");
	};
	BX.CrmInnerClientListView.prototype.getParentList = function()
	{
		return this._parentList;
	};
	BX.CrmInnerClientListView.prototype.getParentItem = function()
	{
		return this._parentItem;
	};
	BX.CrmInnerClientListView.prototype.getContainer = function()
	{
		if(!this._container)
		{
			this._container = this.getSetting("container", null);
		}
		return this._container;
	};
	BX.CrmInnerClientListView.prototype.getItemContainers = function()
	{
		return BX.findChild(this.getContainer(), { className: "crm_contactlist_tel_info" }, true, true);
	};
	BX.CrmInnerClientListView.prototype.createModel = function(data, register)
	{
		var d = this.getDispatcher();
		return d ? d.createEntityModel(data, this.getEntityType(), register) : null;
	};
	BX.CrmInnerClientListView.prototype.createItemView = function(settings)
	{
		settings["entityType"] = this.getSetting("entityType");
		return BX.CrmInnerClientListItemView.create(settings);
	};
	BX.CrmInnerClientListView.prototype.setSelectedItem = function(item)
	{
		if(this._selectedItem === item)
		{
			return;
		}

		if(this._selectedItem)
		{
			this._selectedItem.setSelected(false);
		}

		this._selectedItem = item;

		if(this._selectedItem)
		{
			this._selectedItem.setSelected(true);
		}
	};
	BX.CrmInnerClientListView.prototype._createStub = function()
	{
		var text = "";
		var typeName = this.getEntityType();
		if(typeName === BX.CrmContactModel.typeName)
		{
			text = this.getMessage("noContacts");
		}
		else if(typeName === BX.CrmCompanyModel.typeName)
		{
			text = this.getMessage("noCompanies");
		}
		var container = this.getContainer();
		container.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm_contact_info tac" },
					children:
					[
						BX.create("STRONG",
							{
								style: { color: "#9ca9b6", "fontSize": "15px", display: "inline-block" },
								text: text
							}
						)
					]
				}
			)
		);
	};

	BX.CrmInnerClientListView.prototype.getMessage = function(name)
	{
		var messages = BX.CrmInnerClientListView.messages;
		return BX.type.isNotEmptyString(messages[name]) ? messages[name] : "";
	};
	if(typeof(BX.CrmInnerClientListView.messages) === "undefined")
	{
		BX.CrmInnerClientListView.messages = {};
	}
	BX.CrmInnerClientListView.items = {};
	BX.CrmInnerClientListView.create = function(id, settings)
	{
		var self = new BX.CrmInnerClientListView();
		self.initialize(id, settings);
		this.items[id] = self;

		return self;
	};

	BX.CrmInnerClientListView.prototype._onSearchRequestCompleted = function()
	{
		this._parentItem.processInnerListItemsLoaded(this);
	};
	BX.CrmInnerClientListView.prototype.processItemSelection = function(item)
	{
		this._parentItem.processInnerListItemSelection(this, item);
	};
}

if(typeof(BX.CrmInnerClientListItemView) === "undefined")
{
	BX.CrmInnerClientListItemView = function()
	{
		this._list = this._dispatcher = this._model = this._container = this._buttonPanel =null;
		this._heading = this._tail = null;
		this._containerClickHandler = BX.delegate(this._onContainerClick, this);

		this._isSelected = false;
		this._hasLayout = false;
	};
	BX.extend(BX.CrmInnerClientListItemView, BX.CrmEntityView);
	BX.CrmInnerClientListItemView.prototype.doInitialize = function()
	{
		this._list = this.getSetting("list", null);
		this._dispatcher = this.getSetting("dispatcher", null);
		this._model = this.getSetting("model", null);
		this._container = this.getSetting("container", null);
	};
	BX.CrmInnerClientListItemView.prototype.layout = function()
	{
		if(this._hasLayout)
		{
			return;
		}

		var m = this._model;
		if(!m)
		{
			return;
		}

		this._heading = BX.create("DIV",
			{
				attrs: { className: "clb" }
			}
		);

		if(this._list.getItemCount() > 1)
		{
			this._heading.appendChild(BX.create("HR"));
		}
		this._list.getContainer().appendChild(this._heading);

		if(this._container)
		{
			BX.cleanNode(this._container);
		}
		else
		{
			this._container = BX.create("DIV",
				{
					attrs: { className: "crm_contactlist_tel_info bdbox" },
					events: { click: this._containerClickHandler }
				}
			);

			this._list.addItemView(this);
		}

		var title = "";
		var imageUrl = "";
		var typeName = m.getTypeName();
		if(typeName === BX.CrmCompanyModel.typeName)
		{
			title = m.getStringParam("TITLE");
			imageUrl = m.getStringParam("LIST_IMAGE_URL");
		}
		else if(typeName === BX.CrmContactModel.typeName)
		{
			title = m.getStringParam("FORMATTED_NAME");
			imageUrl = m.getStringParam("LIST_IMAGE_URL");
		}

		this._container.appendChild(
			BX.create("A",
				{
					attrs: { className: "crm_company_img", href : "#" },
					events: { click: BX.eventReturnFalse },
					children:
					[
						BX.create("SPAN",
							{
								attrs: { className: "p0" },
								children:
								[
									BX.create("IMG", { attrs: { className: "fln p0", src: imageUrl } })
								]
							}
						)
					]
				}
			)
		);

		this._container.appendChild(BX.create("STRONG", { text: title, style: { lineHeight: "41px" } }));

		this._tail = BX.create("DIV",
			{
				attrs: { className: "clb" }
			}
		);
		this._list.getContainer().appendChild(this._tail);

		this._hasLayout = true;
	};
	BX.CrmInnerClientListItemView.prototype.clearLayout = function()
	{
		if(!this._hasLayout)
		{
			return;
		}

		this.removeButtons();

		BX.remove(this._heading);
		this._heading = null;


		BX.remove(this._tail);
		this._tail = null;

		BX.cleanNode(this._container);
		this._list.removeItemView(this);
		this._container = null;

		this._hasLayout = false;
	};
	BX.CrmInnerClientListItemView.prototype.scrollInToView = function()
	{
		if(this._container)
		{
			BX.scrollToNode(this._container);
		}
	};
	BX.CrmInnerClientListItemView.prototype.getContainer = function()
	{
		return this._container;
	};
	BX.CrmInnerClientListItemView.prototype.getModel = function()
	{
		return this._model;
	};
	BX.CrmInnerClientListItemView.prototype.getModelKey = function()
	{
		return this._model ? this._model.getKey() : "";
	};
	BX.CrmInnerClientListItemView.prototype.isSelected = function()
	{
		return this._isSelected;
	};
	BX.CrmInnerClientListItemView.prototype.setSelected = function(selected)
	{
		selected = !!selected;
		if(this._isSelected === selected)
		{
			return;
		}

		this._isSelected = selected;
		if(this._isSelected)
		{
			BX.addClass(this._container, 'selected');
		}
		else
		{
			BX.removeClass(this._container, 'selected');
		}

		if(selected)
		{
			this.createButtons();
		}
		else
		{
			this.removeButtons();
		}
	};
	BX.CrmInnerClientListItemView.prototype.getEnityType = function()
	{
		return this.getSetting("entityType", "");
	};
	BX.CrmInnerClientListItemView.prototype.createButtons = function()
	{
		if(this._hasLayout && !this._buttonPanel)
		{
			this._buttonPanel = BX.CrmClientListItemButtonPanel.create({ item: this, parentContainer: this._tail.parentNode, insertBefore: this._tail });
			this._buttonPanel.layout();
		}
	};
	BX.CrmInnerClientListItemView.prototype.removeButtons = function()
	{
		if(this._buttonPanel)
		{
			this._buttonPanel.clearLayout();
			this._buttonPanel = null;
		}
	};
	BX.CrmInnerClientListItemView.prototype.processButtonClick = function(panel, bid)
	{
		if(!this.isSelected())
		{
			return;
		}

		if(bid === BX.CrmClientListItemButtonPanel.bid.cancel)
		{
			this._list.setSelectedItem(null);
		}
		else if(bid === BX.CrmClientListItemButtonPanel.bid.accept)
		{
			this._list.processItemSelection(this);
		}
	};
	BX.CrmInnerClientListItemView.prototype._onContainerClick = function(e)
	{
		if(!this.isSelected())
		{
			this._list.setSelectedItem(this);
		}

		return BX.PreventDefault(e);
	};
	BX.CrmInnerClientListItemView.create = function(settings)
	{
		var self = new BX.CrmInnerClientListItemView();
		self.initialize(settings);
		return self;
	}
}
// <-- INNER VIEW

// BASE CLIENT ITEM -->
if(typeof(BX.CrmClientListItemView) === "undefined")
{
	BX.CrmClientListItemView = function()
	{
		this._list = this._dispatcher = this._model = this._container = this._buttonPanel = null;
		this._isSelected = false;
	};
	BX.extend(BX.CrmClientListItemView, BX.CrmEntityView);
	BX.CrmClientListItemView.prototype.doInitialize = function()
	{
		this._list = this.getSetting("list", null);
		this._dispatcher = this.getSetting("dispatcher", null);
		this._model = this.getSetting("model", null);
		this._container = this.getSetting("container", null);

		this._escalation = parseInt(this.getSetting("escalation", 2));

		if(!this._model && this._container)
		{
			var info = BX.findChild(this._container, { className: "crm_entity_info" }, true, false);
			this._model = info ? this._dispatcher.getModelById(info.value) : null;
		}

		if(this._model)
		{
			this._model.addView(this);
		}

		this.postInitialize();
	};
	BX.CrmClientListItemView.prototype.postInitialize = function()
	{
	};
	BX.CrmClientListItemView.prototype.scrollInToView = function()
	{
		if(this._container)
		{
			BX.scrollToNode(this._container);
		}
	};
	BX.CrmClientListItemView.prototype.getContainer = function()
	{
		return this._container;
	};
	BX.CrmClientListItemView.prototype.getModel = function()
	{
		return this._model;
	};
	BX.CrmClientListItemView.prototype.getModelKey = function()
	{
		return this._model ? this._model.getKey() : "";
	};
	BX.CrmClientListItemView.prototype.isSelected = function()
	{
		return this._isSelected;
	};
	BX.CrmClientListItemView.prototype.setSelected = function(selected)
	{
		selected = !!selected;
		if(this._isSelected === selected)
		{
			return;
		}

		this._isSelected = selected;
		if(this._isSelected)
		{
			BX.addClass(this._container, 'selected');
		}
		else
		{
			BX.removeClass(this._container, 'selected');
		}

		if(this.isQuickSelectEnabled())
		{
			this.notitySelect();
		}
		else
		{
			if(selected)
			{
				this.createButtons();
			}
			else
			{
				this.removeButtons();
			}
		}
	};
	BX.CrmClientListItemView.prototype.isQuickSelectEnabled = function()
	{
		return this.getSetting("enableQuickSelect", false);
	};
	BX.CrmClientListItemView.prototype.getButtonPanel = function()
	{
		return this._buttonPanel;
	};
	BX.CrmClientListItemView.prototype.createButtons = function()
	{
		if(!this._buttonPanel)
		{
			this._buttonPanel = BX.CrmClientListItemButtonPanel.create({ item: this, parentContainer: this._container });
			this._buttonPanel.layout();
		}
	};
	BX.CrmClientListItemView.prototype.removeButtons = function()
	{
		if(this._buttonPanel)
		{
			this._buttonPanel.clearLayout();
			this._buttonPanel = null;
		}
	};
	BX.CrmClientListItemView.prototype.processButtonClick = function(panel, bid)
	{
		if(!this.isSelected())
		{
			return;
		}

		if(bid === BX.CrmClientListItemButtonPanel.bid.cancel)
		{
			this._list.setSelectedItem(null);
		}
		else if(bid === BX.CrmClientListItemButtonPanel.bid.accept)
		{
			this.notitySelect();
		}
	};
	BX.CrmClientListItemView.prototype.getEscalation = function()
	{
		return this._escalation;
	};
	BX.CrmClientListItemView.prototype.notitySelect = function()
	{
		this._list.notifyItemSelected(this, null);
	};
	BX.CrmClientListItemView.prototype.getMessage = function(name)
	{
		var messages = BX.CrmClientListItemView.messages;
		return BX.type.isNotEmptyString(messages[name]) ? messages[name] : "";
	};
	if(typeof(BX.CrmClientListItemView.messages) === "undefined")
	{
		BX.CrmClientListItemView.messages = {};
	}
	BX.CrmClientListItemView.prototype.handleModelUpdate = function(model)
	{
		if(this._model !== model)
		{
			return;
		}

		this.layout();
		if(this._list)
		{
			this._list.handleItemUpdate(this);
		}
	};
	BX.CrmClientListItemView.prototype.handleModelDelete = function(model)
	{
		if(this._model !== model)
		{
			return;
		}

		this.clearLayout();
		if(this._list)
		{
			this._list.handleItemDelete(this);
		}
	};
}
// <-- BASE CLIENT ITEM

// COMPANY ITEM -->
if(typeof(BX.CrmClientCompanyListItemView) === "undefined")
{
	BX.CrmClientCompanyListItemView = function()
	{
		this._contactButton = this._innerList = null;
		this._onContactSelect = BX.delegate(this._onContactButtonClick, this);
		this._isInContactSelectMode = false;
	};
	BX.extend(BX.CrmClientCompanyListItemView, BX.CrmClientListItemView);
	BX.CrmClientCompanyListItemView.prototype.postInitialize = function()
	{
		if(this._container)
		{
			BX.bind(this._container, 'click', BX.delegate(this._onContainerClick, this));
		}
	};
	BX.CrmClientCompanyListItemView.prototype.layout = function()
	{
		if(this._container)
		{
			BX.cleanNode(this._container);
		}
		else
		{
			this._container = BX.create("LI",
				{
					attrs: { "class": "crm_list_tel" },
					events: { "click": BX.delegate(this._onContainerClick, this) }
				}
			);

			this._list.addItemView(this);
		}

		var m = this._model;
		if(!m)
		{
			return;
		}

		this._container.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm_contactlist_tel_info crm_arrow" },
					children:
						[
							BX.create("A",
								{
									attrs: { className: "crm_company_img" },
									events: { "click": BX.eventReturnFalse },
									style: { marginLeft: "10px" },
									children:
										[
											BX.create("SPAN",
												{
													attrs: { className: "p0" },
													children:
													[
														BX.create("IMG",
															{
																attrs:
																	{
																		className: "fln p0",
																		src: m.getStringParam("LIST_IMAGE_URL")
																	}
															}
														)
													]
												}
											)
										]
								}
							),
							BX.create("STRONG",
								{
									attrs:
										{
											style: { lineHeight: "41px" }
										},
									style: { lineHeight: "41px" },
									text: m.getStringParam("TITLE")
								}
							)
						]
				}
			)
		);

		this._container.appendChild(
			BX.create("DIV", { attrs: { className: "clb" } })
		);
	};
	BX.CrmClientCompanyListItemView.prototype.clearLayout = function()
	{
		this._list.removeItemView(this);
		this._container = null;
	};
	BX.CrmClientCompanyListItemView.prototype._onContainerClick = function(e)
	{
		if(this.isSelected())
		{
			return;
		}

		this._list.setSelectedItem(this);
		return BX.PreventDefault(e);
	};
	BX.CrmClientCompanyListItemView.prototype.createButtons = function()
	{
		BX.CrmClientCompanyListItemView.superclass.createButtons.call(this);

		var container = this.getButtonPanel().getContainer();

		container.appendChild(BX.create("HR", { attrs: { className: "crm_hr" } }));
		container.appendChild(BX.create("BR"));

		this._contactButton = BX.create("A",
			{
				attrs: { className: "crm_buttons detail dib wa" },
				style: { marginTop: "0", padding: "1px 15px" },
				events: { click:  this._onContactSelect },
				text: this.getMessage("contactButtonTitle")
			}
		);

		container.appendChild(this._contactButton);
		container.appendChild(BX.create("BR"));
	};
	BX.CrmClientCompanyListItemView.prototype.removeButtons = function()
	{
		BX.unbind(this._contactButton, "click", this._onContactSelect);
		this.getButtonPanel().getContainer().removeChild(this._contactButton);
		this._contactButton = null;

		if(this._isInContactSelectMode)
		{
			if(this._innerList)
			{
				this._innerList.release();
				this._innerList = null;
			}
			this._isInContactSelectMode = false;
		}

		BX.CrmClientCompanyListItemView.superclass.removeButtons.call(this);
	};
	BX.CrmClientCompanyListItemView.prototype._onContactButtonClick = function(e)
	{
		if(this._isInContactSelectMode)
		{
			return BX.PreventDefault(e);
		}

		this._innerList = this._list.openInnerList(this, BX.CrmContactModel.typeName);
		this._isInContactSelectMode = true;
		return BX.PreventDefault(e);
	};
	BX.CrmClientCompanyListItemView.prototype.processInnerListItemsLoaded = function(list)
	{
		if(list.hasItems())
		{
			this.getButtonPanel().setVisible(false);
		}
		else
		{
			BX.removeClass(this._contactButton, "dib");
			this._contactButton.style.display = "none";
		}

		list.setVisible(true);
	};
	BX.CrmClientCompanyListItemView.prototype.processInnerListItemSelection = function(list, item)
	{
		this._list.notifyItemSelected(this, item);
	};
	BX.CrmClientCompanyListItemView.prototype.getMessage = function(name)
	{
		var m = BX.CrmClientCompanyListItemView.messages;
		if(BX.type.isNotEmptyString(m[name]))
		{
			return m[name];
		}

		return BX.CrmClientCompanyListItemView.superclass.getMessage.call(this, name);
	};

	if(typeof(BX.CrmClientCompanyListItemView.messages) === "undefined")
	{
		BX.CrmClientCompanyListItemView.messages = {};
	}

	BX.CrmClientCompanyListItemView.create = function(settings)
	{
		var self = new BX.CrmClientCompanyListItemView();
		self.initialize(settings);
		return self;
	};
}
// <-- COMPANY ITEM

// CONTACT ITEM -->
if(typeof(BX.CrmClientContactListItemView) === "undefined")
{
	BX.CrmClientContactListItemView = function()
	{
		this._companyButton = this._innerList = null;
		this._onCompanySelect = BX.delegate(this._onCompanyButtonClick, this);
		this._isInCompanySelectMode = false;
	};
	BX.extend(BX.CrmClientContactListItemView, BX.CrmClientListItemView);
	BX.CrmClientContactListItemView.prototype.postInitialize = function()
	{
		if(this._container)
		{
			BX.bind(this._container, 'click', BX.delegate(this._onContainerClick, this));
		}
	};
	BX.CrmClientContactListItemView.prototype.layout = function()
	{
		if(this._container)
		{
			BX.cleanNode(this._container);
		}
		else
		{
			this._container = BX.create("LI",
				{
					attrs: { "class": "crm_list_tel" },
					events: { "click": BX.delegate(this._onContainerClick, this) }
				}
			);

			this._list.addItemView(this);
		}

		var m = this._model;
		if(!m)
		{
			return;
		}

		this._container.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm_contactlist_tel_info crm_arrow" },
					children:
						[
							BX.create("IMG", { attrs: { src: m.getStringParam("LIST_IMAGE_URL") } }),
							BX.create("STRONG", { text: m.getStringParam("FORMATTED_NAME") }),
							BX.create("SPAN", { text: m.getStringParam("LEGEND") })
						]
				}
			)
		);

		this._container.appendChild(
			BX.create("DIV", { attrs: { className: "clb" } })
		);
	};
	BX.CrmClientContactListItemView.prototype.clearLayout = function()
	{
		BX.cleanNode(this._container);
		this._list.removeItemView(this);
		this._container = null;
	};
	BX.CrmClientContactListItemView.prototype._onContainerClick = function(e)
	{
		/*if(this._isInCompanySelectMode)
		{
			this.getButtonPanel().setVisible(true);
			if(this._innerList)
			{
				this._innerList.release();
				this._innerList = null;
			}
			this._isInCompanySelectMode = false;
		}*/

		if(this.isSelected())
		{
			return;
		}
		this._list.setSelectedItem(this);
		return BX.PreventDefault(e);
	};
	BX.CrmClientContactListItemView.prototype.createButtons = function()
	{
		BX.CrmClientContactListItemView.superclass.createButtons.call(this);

		var container = this.getButtonPanel().getContainer();
		var m = this._model;
		if(m && m.getIntParam('COMPANY_ID') > 0)
		{
			container.appendChild(BX.create("HR", { attrs: { className: "crm_hr" } }));
			container.appendChild(BX.create("BR"));
			this._companyButton = BX.create("A",
				{
					attrs: { className: "crm_buttons detail dib wa" },
					style: { marginTop: "0", padding: "1px 15px" },
					events: { click:  this._onCompanySelect },
					text: this.getMessage("companyButtonTitle")
				}
			);

			container.appendChild(this._companyButton);
		}
		container.appendChild(BX.create("BR"));
	};
	BX.CrmClientContactListItemView.prototype.removeButtons = function()
	{
		if(this._companyButton)
		{
			BX.unbind(this._companyButton, "click", this._onCompanySelect);
			this.getButtonPanel().getContainer().removeChild(this._companyButton);
			this._companyButton = null;

			if(this._isInCompanySelectMode)
			{
				if(this._innerList)
				{
					this._innerList.release();
					this._innerList = null;
				}
				this._isInCompanySelectMode = false;
			}
		}

		BX.CrmClientContactListItemView.superclass.removeButtons.call(this);
	};
	BX.CrmClientContactListItemView.prototype._onCompanyButtonClick = function(e)
	{
		if(this._isInCompanySelectMode)
		{
			return BX.PreventDefault(e);
		}

		this._innerList = this._list.openInnerList(this, BX.CrmCompanyModel.typeName);
		this._isInCompanySelectMode = true;
		return BX.PreventDefault(e);
	};
	BX.CrmClientContactListItemView.prototype.processInnerListItemsLoaded = function(list)
	{
		if(list.hasItems())
		{
			this.getButtonPanel().setVisible(false);
		}
		else
		{
			BX.removeClass(this._companyButton, "dib");
			this._companyButton.style.display = "none";
		}

		list.setVisible(true);
	};
	BX.CrmClientContactListItemView.prototype.processInnerListItemSelection = function(list, item)
	{
		this._list.notifyItemSelected(this, item);
	};
	BX.CrmClientContactListItemView.prototype.getMessage = function(name)
	{
		var m = BX.CrmClientContactListItemView.messages;
		if(BX.type.isNotEmptyString(m[name]))
		{
			return m[name];
		}

		return BX.CrmClientContactListItemView.superclass.getMessage.call(this, name);
	};
	if(typeof(BX.CrmClientContactListItemView.messages) === "undefined")
	{
		BX.CrmClientContactListItemView.messages = {};
	}
	BX.CrmClientContactListItemView.create = function(settings)
	{
		var self = new BX.CrmClientContactListItemView();
		self.initialize(settings);
		return self;
	};
}
// <-- CONTACT ITEM

if(typeof(BX.CrmClientCreator) === "undefined")
{
	BX.CrmClientCreator = function()
	{
		this._typeName = "";
		this._settings = {};
		this._manager = null;
		this._view = null;
	};

	BX.CrmClientCreator.prototype  =
	{
		initialize: function(typeName, settings)
		{
			this._typeName = typeName;
			this._settings = settings ? settings : {};
			this._view =  BX.CrmClientListView.items[this.getSetting("viewId")];
			this._manager = this.getSetting("manager", null);
		},
		getTypeName: function()
		{
			return this._typeName;
		},
		getSetting: function(name, defaultVal)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultVal;
		},
		open: function()
		{
			var url = this.getSetting("createUrl", "");
			if(url !== "")
			{
				BX.CrmMobileContext.getCurrent().open({ url: url, cache: false });
			}
		},
		createOpenHandler: function()
		{
			return BX.delegate(this.open, this);
		}
	};

	BX.CrmClientCreator.create = function(typeName, settings)
	{
		var self = new BX.CrmClientCreator();
		self.initialize(typeName, settings);
		return self;
	}
}

// SERVICE -->
if(typeof(BX.CrmClientCreatorManager) === "undefined")
{
	BX.CrmClientCreatorManager = function()
	{
		this._id = "";
		this._settings = {};
		this._items = [];
		this._menuCreated = false;
	};

	BX.CrmClientCreatorManager.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};
			var typeMap = this.getSetting("typeMap", {});
			for(var k in typeMap)
			{
				if(typeMap.hasOwnProperty(k))
				{
					var itemSettings = typeMap[k];
					itemSettings["manager"] = this;
					this._items.push(BX.CrmClientCreator.create(k, itemSettings));
				}
			}

			if(this._items.length === 0)
			{
				return;
			}

			BX.CrmMobileContext.getCurrent().createButtons(
				{
					addPostButton: { type: "plus", style: "custom", callback: BX.delegate(this._onButtonClick, this) }
				}
			);
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
		_onButtonClick: function()
		{
			var l = this._items.length;
			if(l === 0)
			{
				return;
			}

			if(l === 1)
			{
				this._items[0].open();
			}
			else
			{
				var context = BX.CrmMobileContext.getCurrent();
				if(!this._menuCreated)
				{
					var menuItems = [];
					for(var i = 0; i < l; i++)
					{
						var item = this._items[i];
						menuItems.push(
							{
								icon: 'edit',
								name:  this.getMessage(item.getTypeName()),
								action: item.createOpenHandler()
							}
						);
					}
					context.prepareMenu(menuItems);
					this._menuCreated = true;
				}
				context.showMenu();
			}
		}
	};
	BX.CrmClientCreatorManager.prototype.getMessage = function(name)
	{
		var m = BX.CrmClientCreatorManager.messages;
		if(BX.type.isNotEmptyString(m[name]))
		{
			return m[name];
		}

		return BX.CrmClientCreatorManager.superclass.getMessage.call(this, name);
	};
	if(typeof(BX.CrmClientCreatorManager.messages) === "undefined")
	{
		BX.CrmClientCreatorManager.messages = {};
	}
	BX.CrmClientCreatorManager.create = function(id, settings)
	{
		var self = new BX.CrmClientCreatorManager();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmClientListManager) === "undefined")
{
	BX.CrmClientListManager = function()
	{
		this._views = {};
		this._switches = {};
		this._dispatchers = {};
		this._searchSettings = {};
	};
	BX.CrmClientListManager.prototype =
	{
		initialize: function()
		{
		},
		registerView: function(view)
		{
			if(!view)
			{
				return;
			}
			this._views[view.getId()] = view;
		},
		registerButton: function(button, viewId)
		{
			if(!(BX.type.isDomNode(button) && BX.type.isNotEmptyString(viewId) && typeof(this._views[viewId]) !== "undefined"))
			{
				return;
			}

			this._switches[viewId] = BX.CrmClientListSwitch.create(
				{
					manager: this,
					viewId: viewId,
					button: button,
					isActive: this._views[viewId].isVisible()
				}
			);
		},
		registerDispatcher: function(dispatcher)
		{
			if(!dispatcher)
			{
				return;
			}
			this._dispatchers[dispatcher.getTypeName()] = dispatcher;
		},
		resolveDispatcher: function(typeName)
		{
			return typeof(this._dispatchers[typeName]) ? this._dispatchers[typeName] : null;
		},
		registerSearchSettings: function(settings)
		{
			if(!settings)
			{
				return;
			}
			var typeName = BX.type.isNotEmptyString(settings["typeName"]) ? settings["typeName"] : "";
			var url = BX.type.isNotEmptyString(settings["url"]) ? settings["url"] : "";

			if(typeName !== "" && url !== "")
			{
				this._searchSettings[typeName] = settings;
			}
		},
		resolveSearchUrl: function(typeName)
		{
			return typeof(this._searchSettings[typeName]) ? this._searchSettings[typeName].url : "";
		},
		switchToView: function(viewId)
		{
			if(!(BX.type.isNotEmptyString(viewId) && typeof(this._views[viewId]) !== "undefined"))
			{
				return;
			}

			var id;

			for(id in this._views)
			{
				if(!this._views.hasOwnProperty(id))
				{
					continue;
				}

				this._views[id].setVisible(id === viewId);
			}

			for(id in this._switches)
			{
				if(!this._switches.hasOwnProperty(id))
				{
					continue;
				}

				this._switches[id].setActive(id === viewId);
			}
		},
		createCompanyView: function(contactId, wrapperId, contextId)
		{
			var typeName = BX.CrmCompanyModel.typeName;
			var dispatcher = typeof(this._dispatchers[typeName]) !== "undefined" ? this._dispatchers[typeName] : null;
			var searchSettings = typeof(this._searchSettings[typeName]) !== "undefined" ? this._searchSettings[typeName] : null;

			if(!dispatcher || !searchSettings)
			{
				return null;
			}

			return BX.CrmClientListView.create(
				'xxx',
				{
					enableQuickSelect: true,
					entityType: typeName,
					dispatcher: dispatcher,
					wrapperId: wrapperId,
					contextId: contextId,
					searchPageUrl: searchSettings["url"],
					escalation: 1
				}
			);
		}
	};

	BX.CrmClientListManager.current = null;
	BX.CrmClientListManager.getCurrent = function()
	{
		if(!this.current)
		{
			this.current = this.create();
		}

		return this.current;
	};

	BX.CrmClientListManager.create = function()
	{
		var self = new BX.CrmClientListManager();
		self.initialize();
		return self;
	}
}

if(typeof(BX.CrmClientListItemButtonPanel) === "undefined")
{
	BX.CrmClientListItemButtonPanel = function()
	{
		this._settings = {};
		this._item = null;
		this._parentContainer = this._container = this._acceptButton = this._cancelButton = null;
		this._onCancel = BX.delegate(this._onCancelClick, this);
		this._onAccept = BX.delegate(this._onAcceptClick, this);

		this._isVisible = true;
		this._hasLayout = false;
	};
	BX.CrmClientListItemButtonPanel.bid = { cancel: 0, accept: 1 };
	BX.CrmClientListItemButtonPanel.prototype =
	{
		initialize: function(settings)
		{
			this._settings = settings ? settings : {};

			this._item = this.getSetting("item", null);
			if(!this._item)
			{
				throw "BX.CrmClientListItemButtonPanel: Could not find item";
			}

			this._parentContainer = this.getSetting("parentContainer");
			if(!this._parentContainer)
			{
				throw "BX.CrmClientListItemButtonPanel: Could not find parent container";
			}


		},
		getSetting: function(name, defaultVal)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultVal;
		},
		setVisible: function(visible)
		{
			visible = !!visible;

			if(this._isVisible === visible)
			{
				return;
			}

			this._isVisible = visible;

			if(this._hasLayout)
			{
				this._container.style.display = visible ? "" : "none";
			}
		},
		isVisible: function()
		{
			return this._isVisible;
		},
		layout: function()
		{
			if(this._hasLayout)
			{
				this.clearLayout();
			}

			this._acceptButton = BX.create("A",
				{
					attrs: { className: "crm accept-button wa" },
					style: { marginLeft: "0", padding: "0 10px", height: "40px", fontSize: "17px" },
					events: { click:  this._onAccept },
					text: this.getMessage("acceptButtonTitle")
				}
			);
			this._cancelButton = BX.create("A",
				{
					attrs: { className: "crm_buttons detail dib wa" },
					style: { marginRight: "0", marginTop: "0", padding: "1px 10px", height: "19px", lineHeight: "17px", fontSize: "17px" },
					events: { click:  this._onCancel },
					text: this.getMessage("cancelButtonTitle")
				}
			);

			this._container = BX.create("DIV",
				{
					attrs: { className: "crm_tac" },
					children:
						[
							BX.create("BR"),
							this._acceptButton,
							this._cancelButton,
							BX.create("BR")
						]
				}
			);

			var insertBefore = this.getSetting("insertBefore", null);
			if(BX.type.isDomNode(insertBefore))
			{
				this._parentContainer.insertBefore(this._container, insertBefore);
			}
			else
			{
				this._parentContainer.appendChild(this._container);
			}

			this._container.style.display = this._isVisible ? "" : "none";

			this._hasLayout = true;
		},
		clearLayout: function()
		{
			BX.unbind(this._acceptButton, "click", this._onAccept);
			this._container.removeChild(this._acceptButton);
			this._acceptButton = null;

			BX.unbind(this._cancelButton, "click", this._onCancel);
			this._container.removeChild(this._cancelButton);
			this._cancelButton = null;

			BX.cleanNode(this._container, true);

			this._hasLayout = false;
		},
		getContainer: function()
		{
			return this._container;
		},
		getMessage: function(name)
		{
			var messages = BX.CrmClientListItemButtonPanel.messages;
			return BX.type.isNotEmptyString(messages[name]) ? messages[name] : "";
		},
		_onAcceptClick: function(e)
		{
			this._item.processButtonClick(this, BX.CrmClientListItemButtonPanel.bid.accept);
			return BX.PreventDefault(e);
		},
		_onCancelClick: function(e)
		{
			this._item.processButtonClick(this, BX.CrmClientListItemButtonPanel.bid.cancel);
			return BX.PreventDefault(e);
		}
	};

	if(typeof(BX.CrmClientListItemButtonPanel.messages) === "undefined")
	{
		BX.CrmClientListItemButtonPanel.messages = {};
	}

	BX.CrmClientListItemButtonPanel.create = function(settings)
	{
		var self = new BX.CrmClientListItemButtonPanel();
		self.initialize(settings);
		return self;
	};
}

if(typeof(BX.CrmClientListSwitch) === "undefined")
{
	BX.CrmClientListSwitch = function()
	{
		this._settings = {};
		this._manager = null;
		this._viewId = "";
		this._button = null;
		this._isActive = false;
	};
	BX.CrmClientListSwitch.prototype =
	{
		initialize: function(settings)
		{
			this._settings = settings ? settings : {};
			this._manager = this.getSetting("manager", null);
			if(!this._manager)
			{
				throw  "BX.CrmClientListSwitch: Could not find manager.";
			}
			this._viewId = this.getSetting("viewId", "");
			if(!this._viewId)
			{
				throw  "BX.CrmClientListSwitch: Could not find view id.";
			}

			this._button = this.getSetting("button", null);
			if(!this._button)
			{
				throw  "BX.CrmClientListSwitch: Could not find button.";
			}

			BX.bind(this._button, "click", BX.delegate(this._onButtonClick, this));

			this._isActive = this.getSetting("isActive", false);
		},

		getSetting: function(name, defaultVal)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultVal;
		},
		getViewId: function()
		{
			return this._viewId;
		},
		setActive: function(active)
		{
			active = !!active;

			if(this._isActive === active)
			{
				return;
			}

			this._isActive = active;

			if(active)
			{
				BX.addClass(this._button, "current");
			}
			else
			{
				BX.removeClass(this._button, "current");
			}
		},
		_onButtonClick: function(e)
		{
			this._manager.switchToView(this._viewId);
			return BX.PreventDefault(e);
		}
	};
	BX.CrmClientListSwitch.create = function(settings)
	{
		var self = new BX.CrmClientListSwitch();
		self.initialize(settings);
		return self;
	};
}
// <-- SERVICE
