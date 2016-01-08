if(typeof(BX.CrmProductListView) === "undefined")
{
	BX.CrmProductListView = function()
	{
		this._hasMenuButtons = false;
		this._extMenuButtons = null;
		this._contextId = "";
	};
	BX.extend(BX.CrmProductListView, BX.CrmEntityListView);
	BX.CrmProductListView.prototype.doInitialize = function()
	{
		this._contextId = this.getSetting("contextId", "");
		BX.addCustomEvent("onOpenPageAfter", BX.delegate(this._onAfterPageOpen, this));
	};
	BX.CrmProductListView.prototype.initializeFromExternalData = function()
	{
		var self = this;
		BX.CrmMobileContext.getCurrent().getPageParams(
			{
				callback: function(data)
				{
					if(data)
					{
						self._contextId = BX.type.isNotEmptyString(data["contextId"]) ? data["contextId"] : "";
					}
				}
			}
		);
	};
	BX.CrmProductListView.prototype.getContainer = function()
	{
		return this._container ? this._container : BX.findChild(this._wrapper, { className: "crm_product_list" }, true, false);
	};
	BX.CrmProductListView.prototype.getItemContainers = function()
	{
		return BX.findChild(this.getContainer(), { className: "crm_itemcategory_item" }, true, true);
	};
	BX.CrmProductListView.prototype.getWaiterClassName = function()
	{
		return "crm_itemcategory_item_wait";
	};
	BX.CrmProductListView.prototype.createModel = function(data, register)
	{
		var d = this.getDispatcher();
		return d ? d.createEntityModel(data, "PRODUCT", register) : null;
	};
	BX.CrmProductListView.prototype.createItemView = function(settings)
	{
		return BX.CrmProductListItemView.create(settings);
	};
	BX.CrmProductListView.prototype.createSearchParams = function(val)
	{
		return { NAME: val };
	};
	BX.CrmProductListView.prototype.isInSelectMode = function()
	{
		return this.getSetting("listMode", "") === "SELECTOR";
	};
	BX.CrmProductListView.prototype.getContextId = function()
	{
		return this._contextId;
	};
	BX.CrmProductListView.prototype.setContextId = function(contextId)
	{
		this._contextId = contextId;
	};
	BX.CrmProductListView.prototype.processItemSelection = function(item)
	{
		if(!this.isInSelectMode())
		{
			return;
		}
		var m = item.getModel();
		if(!m)
		{
			return;
		}

		var eventArgs =
		{
			contextId: this.getContextId(),
			modelData:
			{
				"ID" : m.getIntParam("ID"),
				"NAME" : m.getStringParam("NAME"),
				"CURRENCY_ID" : m.getStringParam("CURRENCY_ID"),
				"PRICE" : m.getFloatParam("PRICE"),
				"FORMATTED_PRICE" : m.getStringParam("FORMATTED_PRICE")
			}
		};

		var context = BX.CrmMobileContext.getCurrent();
		context.riseEvent("onCrmProductSelect", eventArgs, 2);
		window.setTimeout(context.createBackHandler(), 0);
	};
	BX.CrmProductListView.prototype.setExternalMenuButtons = function(menuButtons)
	{
		this._extMenuButtons = menuButtons;
		if(this.isVisible())
		{
			this.prepareButtons();
		}
	};
	BX.CrmProductListView.prototype.prepareButtons = function()
	{
		var context = BX.CrmMobileContext.getCurrent();
		context.removeButtons({ position: "right" });

		if(this._extMenuButtons)
		{
			context.createButtons(this._extMenuButtons);
			this._hasMenuButtons = true;
		}
	};
	BX.CrmProductListView.prototype._onAfterPageOpen = function()
	{
		this.initializeFromExternalData();
	};
	BX.CrmProductListView.prototype._onAfterVisibilityChange = function()
	{
		if(this.isVisible())
		{
			this.prepareButtons();
		}
		else if(this._hasMenuButtons)
		{
			BX.CrmMobileContext.getCurrent().removeButtons({ position: "right" });
			this._hasMenuButtons = false;
		}
	};

	BX.CrmProductListView.prototype.getMessage = function(name, defaultVal)
	{
		var m = BX.CrmProductListView.messages;
		return m.hasOwnProperty(name) ? m[name] : defaultVal;
	};
	BX.CrmProductListView.create = function(id, settings)
	{
		var self = new BX.CrmProductListView();
		self.initialize(id, settings);
		return self;
	};
	if(typeof(BX.CrmProductListView.messages) === "undefined")
	{
		BX.CrmProductListView.messages =
		{
		};
	}
}

if(typeof(BX.CrmProductListItemView) === "undefined")
{
	BX.CrmProductListItemView = function()
	{
		this._list = this._dispatcher = this._model = this._container = null;
	};
	BX.extend(BX.CrmProductListItemView, BX.CrmEntityView);
	BX.CrmProductListItemView.prototype.doInitialize = function()
	{
		this._list = this.getSetting("list", null);
		this._dispatcher = this.getSetting("dispatcher", null);
		this._model = this.getSetting("model", null);
		this._container = this.getSetting("container", null);

		if(!this._model && this._container)
		{
			var info = BX.findChild(this._container, { className: "crm_entity_info" }, true, false);
			this._model = info ? this._dispatcher.getModelById(info.value) : null;
		}

		if(this._list.isInSelectMode() && this._container)
		{
			BX.bind(this._container, "click", BX.delegate(this._onContainerClick, this));
		}

		if(this._model)
		{
			this._model.addView(this);
		}
	};
	BX.CrmProductListItemView.prototype.layout = function()
	{
		if(this._container)
		{
			BX.cleanNode(this._container);
		}
		else
		{
			this._container = BX.create("LI", { attrs: { "class": "crm_itemcategory_item" } });
			this._list.addItemView(this);

			if(this._list.isInSelectMode())
			{
				BX.addClass(this._container, "crm_arrow");
				BX.bind(this._container, "click", BX.delegate(this._onContainerClick, this));
			}
		}

		var m = this._model;
		if(!m)
		{
			return;
		}

		var titleWrapper = BX.create("DIV", { attrs: { className: "crm_itemcategory_title" } });
		this._container.appendChild(titleWrapper);
		titleWrapper.appendChild(document.createTextNode(m.getStringParam("NAME")));
		titleWrapper.appendChild(BX.create("SPAN", { html: (" " + m.getStringParam("FORMATTED_PRICE")) }));

		var sectionName = m.getStringParam("SECTION_NAME");
		if(sectionName !== "")
		{
			this._container.appendChild(
				BX.create(
					"DIV",
					{
						attrs: { className: "crm_category_desc" },
						children:
						[
							BX.create("SPAN", { text: sectionName })
						]
					}
				)
			);
		}

		this._container.appendChild(
			BX.create("DIV", { attrs: { className: "clb" } })
		);
	};
	BX.CrmProductListItemView.prototype.clearLayout = function()
	{
		this._list.removeItemView(this);
		this._container = null;
	};
	BX.CrmProductListItemView.prototype.scrollInToView = function()
	{
		if(this._container)
		{
			BX.scrollToNode(this._container);
		}
	};
	BX.CrmProductListItemView.prototype.getContainer = function()
	{
		return this._container;
	};
	BX.CrmProductListItemView.prototype.getModel = function()
	{
		return this._model;
	};
	BX.CrmProductListItemView.prototype.getModelKey = function()
	{
		return this._model ? this._model.getKey() : "";
	};
	BX.CrmProductListItemView.prototype._onContainerClick = function(e)
	{
		this._list.processItemSelection(this);
	};
	BX.CrmProductListItemView.create = function(settings)
	{
		var self = new BX.CrmProductListItemView();
		self.initialize(settings);
		return self;
	};
}

if(typeof(BX.CrmProductSectionListView) === "undefined")
{
	BX.CrmProductSectionListView = function()
	{
		this._hasMenuButtons = false;
	};
	BX.extend(BX.CrmProductSectionListView, BX.CrmEntityListView);
	BX.CrmProductSectionListView.prototype.doInitialize = function()
	{
		this.prepareButtons();
	};
	BX.CrmProductSectionListView.prototype.getContainer = function()
	{
		return this._container ? this._container : BX.findChild(this._wrapper, { className: "crm_product_section_list" }, true, false);
	};
	BX.CrmProductSectionListView.prototype.getItemContainers = function()
	{
		return BX.findChild(this.getContainer(), { className: "crm_itemcategory_item" }, true, true);
	};
	BX.CrmProductSectionListView.prototype.createModel = function(data, register)
	{
		var d = this.getDispatcher();
		return d ? d.createEntityModel(data, "PRODUCT_SECTION", register) : null;
	};

	BX.CrmProductSectionListView.prototype.createItemView = function(settings)
	{
		return BX.CrmProductSectionListItemView.create(settings);
	};
	BX.CrmProductSectionListView.prototype._onAfterVisibilityChange = function()
	{
		if(this.isVisible())
		{
			this.prepareButtons();
		}
		else if(this._hasMenuButtons)
		{
			BX.CrmMobileContext.getCurrent().removeButtons({ position: "right" });
			this._hasMenuButtons = false;
		}
	};
	BX.CrmProductSectionListView.prototype.prepareButtons = function()
	{
		var context = BX.CrmMobileContext.getCurrent();
		context.removeButtons({ position: "right" });

		var sectionId = parseInt(this.getSetting("sectionId", 0));
		if(sectionId > 0)
		{
			context.createButtons(
				{
					addPostButton:
						{
							type: "right_text",
							style: "custom",
							name: this.getMessage("buttonUpCaption"),
							callback: BX.delegate(this._onGoToUpButtonClick, this)
						}
				}
			);
			this._hasMenuButtons = true;
		}
	};
	BX.CrmProductSectionListView.prototype.goToSection = function(sectionId)
	{
		if(this._context.isOffLine())
		{
			return;
		}

		BX.CrmMobileContext.getCurrent().showWait();
		BX.ajax(
			{
				url: this.getSetting("serviceUrl", ""),
				method: "POST",
				dataType: "json",
				data:
				{
					"ACTION" : "GET_SECTIONS",
					"CATALOG_ID": this.getSetting("catalogId", 0),
					"SECTION_ID": sectionId
				},
				onsuccess: BX.delegate(this._onSectionListRequestSuccess, this)
			}
		);
	};
	BX.CrmProductSectionListView.prototype._onSectionListRequestSuccess = function(data)
	{
		BX.CrmMobileContext.getCurrent().hideWait();

		this._clearItems();
		this._synchronizeItemData(data["MODELS"]);

		var sectionId = parseInt(data["SECTION_ID"]);
		this.setSetting("sectionId", sectionId);

		this.setSetting("parentSectionId", parseInt(data["PARENT_SECTION_ID"]));

		var activeSectionContainer = BX(this.getSetting("activeSectionContainerId"));
		if(activeSectionContainer)
		{
			activeSectionContainer.innerHTML = BX.util.htmlspecialchars(sectionId > 0
				? data["SECTION_NAME"] : this.getMessage("rootSectionLegend"));
		}

		this.prepareButtons();
	};
	BX.CrmProductSectionListView.prototype.processItemSelection = function(item)
	{
		var m = item.getModel();
		if(!m)
		{
			return;
		}

		this.goToSection(m.getIntParam("ID"));
	};
	BX.CrmProductSectionListView.prototype.getSectionProductListUrl = function()
	{
		return this.getSetting("sectionProductUrlTemplate").replace(/#section_id#/i, this.getSetting("sectionId"));
	};
	BX.CrmProductSectionListView.prototype._onGoToUpButtonClick = function(e)
	{
		var parentSectionId = parseInt(this.getSetting("parentSectionId", 0));
		this.goToSection(parentSectionId);
	};
	BX.CrmProductSectionListView.prototype.getMessage = function(name, defaultVal)
	{
		var m = BX.CrmProductSectionListView.messages;
		return m.hasOwnProperty(name) ? m[name] : defaultVal;
	};
	BX.CrmProductSectionListView.create = function(id, settings)
	{
		var self = new BX.CrmProductSectionListView();
		self.initialize(id, settings);
		return self;
	};
	if(typeof(BX.CrmProductSectionListView.messages) === "undefined")
	{
		BX.CrmProductSectionListView.messages =
		{
		};
	}
}

if(typeof(BX.CrmProductSectionListItemView) === "undefined")
{
	BX.CrmProductSectionListItemView = function()
	{
		this._list = this._dispatcher = this._model = this._container = null;
	};
	BX.extend(BX.CrmProductSectionListItemView, BX.CrmEntityView);
	BX.CrmProductSectionListItemView.prototype.doInitialize = function()
	{
		this._list = this.getSetting("list", null);
		this._dispatcher = this.getSetting("dispatcher", null);

		this._model = this.getSetting("model", null);
		this._container = this.getSetting("container", null);
		if(this._container)
		{
			BX.bind(this._container, "click", BX.delegate(this._onContainerClick, this));
		}

		if(!this._model && this._container)
		{
			var info = BX.findChild(this._container, { className: "crm_entity_info" }, true, false);
			this._model = info ? this._dispatcher.getModelById(info.value) : null;
		}

		if(this._model)
		{
			this._model.addView(this);
		}
	};
	BX.CrmProductSectionListItemView.prototype.layout = function()
	{
		if(this._container)
		{
			BX.cleanNode(this._container);
		}
		else
		{
			this._container = BX.create("LI", { attrs: { "class": "crm_itemcategory_item crm_arrow" } });
			this._list.addItemView(this);
			BX.bind(this._container, "click", BX.delegate(this._onContainerClick, this));
		}

		var m = this._model;
		if(!m)
		{
			return;
		}

		var titleWrapper = BX.create("DIV", { attrs: { className: "crm_category_title" } });
		this._container.appendChild(titleWrapper);
		titleWrapper.appendChild(document.createTextNode(m.getStringParam("NAME")));
	};
	BX.CrmProductSectionListItemView.prototype.clearLayout = function()
	{
		this._list.removeItemView(this);
		this._container = null;
	};
	BX.CrmProductSectionListItemView.prototype.scrollInToView = function()
	{
		if(this._container)
		{
			BX.scrollToNode(this._container);
		}
	};
	BX.CrmProductSectionListItemView.prototype.getContainer = function()
	{
		return this._container;
	};
	BX.CrmProductSectionListItemView.prototype.getModel = function()
	{
		return this._model;
	};
	BX.CrmProductSectionListItemView.prototype.getModelKey = function()
	{
		return this._model ? this._model.getKey() : "";
	};
	BX.CrmProductSectionListItemView.prototype._onContainerClick = function(e)
	{
		this._list.processItemSelection(this);
	};
	BX.CrmProductSectionListItemView.create = function(settings)
	{
		var self = new BX.CrmProductSectionListItemView();
		self.initialize(settings);
		return self;
	};
}

if(typeof(BX.CrmProductListTurnView) === "undefined")
{
	BX.CrmProductListTurnView = function()
	{
		this._settings = {};
		this._sectionView = this._productView = this._showSectionButton = null;
		this._isVisible = true;
		this._mode = "section";
	};

	BX.CrmProductListTurnView.prototype =
	{
		initialize: function(settings)
		{
			this._settings = settings ? settings : {};
			this._sectionView = this.getSetting("sectionView");
			this._productView = this.getSetting("productView");

			this._productView.setExternalMenuButtons(
				{
					addPostButton:
						{
							type: "right_text",
							style: "custom",
							name: this.getMessage("buttonUpCaption"),
							callback: BX.delegate(this._onProductGoToUpButtonClick, this)
						}
				}
			);

			this._showSectionButton = BX(this.getSetting("showSectionButtonId"));

			if(this._showSectionButton)
			{
				BX.bind(this._showSectionButton, "click", BX.delegate(this._onShowSectionButtonClick, this));
			}

			this._isVisible = this.getSetting("isVisible", true);
		},
		getSetting: function(name, defaultVal)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultVal;
		},
		isVisible: function()
		{
			return this._isVisible;
		},
		setVisible: function(visible)
		{
			visible = !!visible;

			if(this._isVisible === visible)
			{
				return;
			}

			this._isVisible = visible;

			if(this._mode === 'product')
			{
				this._productView.setVisible(visible);
			}
			else
			{
				this._sectionView.setVisible(visible);
				this._showSectionButton.style.display = visible ? "" : "none";
			}
		},
		_onShowSectionButtonClick: function(e)
		{
			var url = this._sectionView.getSectionProductListUrl();
			if(BX.type.isNotEmptyString(url))
			{
				this._sectionView.setVisible(false);
				this._showSectionButton.style.display = "none";
				this._productView.reload(url, true);
				this._productView.setVisible(true);
				this._mode = 'product';
			}
		},
		_onProductGoToUpButtonClick: function(e)
		{
			this._productView.setVisible(false);
			this._sectionView.setVisible(true);
			this._showSectionButton.style.display = "";
			this._mode = "section";
		},
		getMessage: function(name, defaultVal)
		{
			var m = BX.CrmProductListTurnView.messages;
			return m.hasOwnProperty(name) ? m[name] : defaultVal;
		}
	};

	if(typeof(BX.CrmProductListTurnView.messages) === "undefined")
	{
		BX.CrmProductListTurnView.messages =
		{
		};
	}

	BX.CrmProductListTurnView.create = function(settings)
	{
		var self = new BX.CrmProductListTurnView();
		self.initialize(settings);
		return self;
	};
}

if(typeof(BX.CrmProductListViewSwitch) === "undefined")
{
	BX.CrmProductListViewSwitch = function()
	{
		this._settings = {};
		this._items = [];
	};

	BX.CrmProductListViewSwitch.prototype =
	{
		initialize: function(settings)
		{
			this._settings = settings ? settings : {};

			var itemData = this.getSetting("itemData", []);
			for(var i = 0; i < itemData.length; i++)
			{
				var itemSettings = itemData[i];
				itemSettings["switch"] = this;
				this._items.push(BX.CrmProductListViewSwitchItem.create(itemSettings));
			}
		},
		getSetting: function(name, defaultVal)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultVal;
		},
		processItemSwitch: function(item)
		{
			for(var i = 0; i < this._items.length; i++)
			{
				var curItem = this._items[i];
				curItem.setVisible(curItem === item);
			}
		}
	};

	BX.CrmProductListViewSwitch.create = function(settings)
	{
		var self = new BX.CrmProductListViewSwitch();
		self.initialize(settings);
		return self;
	};
}

if(typeof(BX.CrmProductListViewSwitchItem) === "undefined")
{
	BX.CrmProductListViewSwitchItem = function()
	{
		this._settings = {};
		this._elements = [];
		this._button = null;
		this._isVisible = true;
	};

	BX.CrmProductListViewSwitchItem.prototype =
	{
		initialize: function(settings)
		{
			this._settings = settings ? settings : {};
			this._switch = this.getSetting("switch");
			if(!this._switch)
			{
				throw "BX.CrmProductListViewSwitchItem: could not find switch!";
			}

			this._elements = this.getSetting("elements", []);
			var btn = this._button = this.getSetting("button");
			if(btn)
			{
				BX.bind(btn, "click", BX.delegate(this._onButtonClick, this));
			}

			this._isVisible = this.getSetting("isVisible");
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

			for(var i = 0; i < this._elements.length; i++)
			{
				var elem = this._elements[i];
				var obj = elem["object"];
				if(!obj)
				{
					continue;
				}

				var elemType = BX.type.isNotEmptyString(elem["type"]) ? elem["type"] : "";
				if(elemType === "view")
				{
					obj.setVisible(visible);
				}
				else if(BX.type.isElementNode(obj))
				{
					obj.style.display = visible ? "" : "none";
				}
			}

			if(visible)
			{
				BX.addClass(this._button.parentNode, "current");
			}
			else
			{
				BX.removeClass(this._button.parentNode, "current");
			}
		},
		isVisible: function()
		{
			return this._isVisible;
		},
		_onButtonClick: function(e)
		{
			this._switch.processItemSwitch(this);
			return BX.PreventDefault(e);
		}
	};

	BX.CrmProductListViewSwitchItem.create = function(settings)
	{
		var self = new BX.CrmProductListViewSwitchItem();
		self.initialize(settings);
		return self;
	};
}
