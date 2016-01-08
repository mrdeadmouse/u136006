if(typeof(BX.CrmControlPanel) === "undefined")
{
	BX.CrmControlPanel = function()
	{
		this._id = "";
		this._settings = null;
		this._container = null;
		this._wrapper = null;
		this._searchContainer = null;
		this._items = [];
		this._activeItem = null;
		this._additionalItem = null;
		this._slider = null;
		this._isFixed = false;
		this._isFixedLayout = false;
		this._scrollHandler = BX.delegate(this._onWindowScroll, this);
		this._resizeHandler = BX.delegate(this._onWindowResize, this);
		this._searchButton = null;
		this._pinButton = null;
	};

	BX.CrmControlPanel.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "";
			this._settings = settings ? settings : BX.CrmParamBag.create(null);
			this._container = BX(this.getSetting("containerId"));
			this._wrapper = BX(this.getSetting("wrapperId"));
			this._searchContainer = BX(this.getSetting("searchContainerId"));

			var itemInfos = this.getSetting("itemInfos", []);
			for(var i = 0; i < itemInfos.length; i++)
			{
				var itemInfo = itemInfos[i];
				var itemSettings = BX.CrmParamBag.create(BX.clone(itemInfo));
				itemSettings.setParam("panel", this);
				var item = BX.CrmControlPanelItem.create(itemSettings.getParam("id"), itemSettings);
				if(item.isActive())
				{
					this._activeItem = item;
				}
				this._items.push(item);
			}

			var additionalItemInfo = this.getSetting("additionalItemInfo", null);
			if(additionalItemInfo)
			{
				var additionalItemSettings = BX.CrmParamBag.create(BX.clone(additionalItemInfo));
				additionalItemSettings.setParam("panel", this);
				this._additionalItem = BX.CrmControlPanelItem.create(additionalItemSettings.getParam("id"), additionalItemSettings);
			}

			this._searchButton = BX.findChild(this._container, { "tag": "SPAN", "class": "crm-search-btn" }, true, false);
			if(this._searchButton)
			{
				BX.bind(this._searchButton, "click", BX.delegate(this._onSearchButtonClick, this));
			}

			this._pinButton = BX.findChild(this._container, { "tag": "SPAN", "class": "crm-menu-fixed-btn" }, true, false);
			if(this._pinButton)
			{
				BX.bind(this._pinButton, "click", BX.delegate(this._onPinButtonClick, this));
			}

			BX.addCustomEvent("onPullEvent-main", BX.delegate(function(command,params){
				if (command == "user_counter" && params[BX.message("SITE_ID")])
				{
					var counters = BX.clone(params[BX.message('SITE_ID')]);
					this.updateCounters(counters);
				}
			}, this));
			BX.addCustomEvent(window, "onImUpdateCounter", BX.delegate(function(counters){
				if (!counters)
					return;

				this.updateCounters(BX.clone(counters));
			}, this));

			this._slider = BX.CrmControlPanelSlider.create(
				this._id,
				BX.CrmParamBag.create(
					{
						wrapperId: this.getSetting("itemWrapperId"),
						anchorId: this.getSetting("anchorId"),
						items: this._items,
						additionalItem: this._additionalItem
					}
				)
			);

			this._isFixed = this.getSetting("isFixed", false);
			if(this._isFixed)
			{
				this.adjust();
				BX.bind(window, "scroll", this._scrollHandler);
				BX.bind(window, "resize", this._resizeHandler);
			}
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.getParam(name, defaultval);
		},
		getId: function()
		{
			return this._id;
		},
		getItemContainerId: function(id)
		{
			return this.getSetting("itemContainerPrefix", "crm_ctrl_panel_item_") + id.toLowerCase();
		},
		requireItemActivityChange: function(item)
		{
			return true;
		},
		handleItemActivityChange: function(item)
		{
			if(!item.isActive())
			{
				return;
			}

			if(this._activeItem !== null && this._activeItem !== item)
			{
				this._activeItem.setActive(false, true, true);
			}

			this._activeItem = item;
		},
		getNodeRect: function(node)
		{
			var r = node.getBoundingClientRect();
			return(
				{
					top: r.top, bottom: r.bottom, left: r.left, right: r.right,
					width: typeof(r.width) !== "undefined" ? r.width : (r.right - r.left),
					height: typeof(r.height) !== "undefined" ? r.height : (r.bottom - r.top)
				}
			);
		},
		getRect: function()
		{
			return this._isFixed ? BX.pos(this._wrapper, true) : BX.pos(this._container);
		},
		adjust: function()
		{
			if(!this._isFixed)
			{
				if(this._isFixedLayout)
				{
					this._isFixedLayout = false;
					BX.removeClass(this._wrapper, "crm-menu-fixed");

					this._container.style.height = this._container.style.width = "";
					this._wrapper.style.height = this._wrapper.style.width = this._wrapper.style.left = this._wrapper.style.top = "";

					BX.onCustomEvent(
						window,
						"CrmControlPanelLayoutChange",
						[this]
					);
				}
				return;
			}

			if (this.getNodeRect(this._container).top <= 0)
			{
				if(this._isFixedLayout)
				{
					//synchronize wrapper width
					this._wrapper.style.width = this.getNodeRect(this._container).width.toString() + "px";
				}
				else
				{
					var r = this.getNodeRect(this._container);
					this._container.style.height = r.height + "px";
					this._wrapper.style.width = r.width + "px";
					this._wrapper.style.left = r.left + "px";
					this._wrapper.style.top = 0;

					BX.addClass(this._wrapper, "crm-menu-fixed");
					this._isFixedLayout = true;

					if(this._searchContainer)
					{
						BX.onCustomEvent(this._searchContainer, "OnNodeLayoutChange");
					}

					BX.onCustomEvent(
						window,
						"CrmControlPanelLayoutChange",
						[this]
					);
				}
			}
			else if(this._isFixedLayout)
			{
				this._isFixedLayout = false;
				BX.removeClass(this._wrapper, "crm-menu-fixed");

				this._container.style.height = this._container.style.width = "";
				this._wrapper.style.height = this._wrapper.style.width = this._wrapper.style.left = this._wrapper.style.top = "";

				if(this._searchContainer)
				{
					BX.onCustomEvent(this._searchContainer, "OnNodeLayoutChange");
				}

				BX.onCustomEvent(
					window,
					"CrmControlPanelLayoutChange",
					[this]
				);
			}

			this._slider.adjust();
		},
		isFixed: function()
		{
			return this._isFixed;
		},
		setFixed: function(fixed)
		{
			fixed = !!fixed;
			if(this._isFixed === fixed)
			{
				return;
			}

			if(fixed)
			{
				BX.unbind(window, "scroll", this._scrollHandler);
				BX.bind(window, "scroll", this._scrollHandler);

				BX.unbind(window, "resize", this._resizeHandler);
				BX.bind(window, "resize", this._resizeHandler);

				BX.removeClass(this._pinButton, "crm-lead-header-contact-btn-unpin");
				BX.addClass(this._pinButton, "crm-lead-header-contact-btn-pin");
			}
			else
			{
				BX.unbind(window, "scroll", this._scrollHandler);
				BX.unbind(window, "resize", this._resizeHandler);


				BX.removeClass(this._pinButton, "crm-lead-header-contact-btn-pin");
				BX.addClass(this._pinButton, "crm-lead-header-contact-btn-unpin");

				this._container.style.height = this._container.style.width = "";
				this._wrapper.style.height = this._wrapper.style.width = this._wrapper.style.left = this._wrapper.style.top = "";
			}
			this._isFixed = fixed;
			this.adjust();

			BX.onCustomEvent(
				window,
				"CrmControlPanelFixed",
				[this, this._isFixed]
			);

			BX.userOptions.save("crm.control.panel", this.getId().toLowerCase(), "fixed", (fixed ? "Y" : "N"));
		},
		_onSearchButtonClick: function(e)
		{
			var searcForm = BX.findChild(this._container, { "tag": "FORM", "class": "crm-search" }, true, false);
			if(searcForm)
			{
				searcForm.submit();
			}
		},
		_onWindowScroll: function()
		{
			this.adjust();
		},
		_onWindowResize: function(e)
		{
			this.adjust();
		},
		_onPinButtonClick: function(e)
		{
			this.setFixed(!this.isFixed());
		},
		updateCounters: function(counters, send)
		{
			send = send == false ? false : true;

			for (var id in counters)
			{
				if (id == "CRM_**")
				{
					var counter = BX("crm_menu_counter", true);
					if (!counter)
					{
						break;
					}

					if (counters[id] > 0)
					{
						counter.innerHTML = (counters[id] > 99 ? "99+" : counters[id]);
						counter.style.display = "inline-block";
					}
					else
					{
						counter.innerHTML = 0;
						counter.style.display = "none";
					}

					break;
				}
				else
				{
					continue;
				}
			}
		}
	};
	BX.CrmControlPanel._default = null;
	BX.CrmControlPanel.setDefault = function(item)
	{
		this._default = item;
	};
	BX.CrmControlPanel.getDefault = function()
	{
		return this._default;
	};
	BX.CrmControlPanel.items = {};
	BX.CrmControlPanel.create = function(id, settings)
	{
		var self = new BX.CrmControlPanel();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}

if(typeof(BX.CrmControlPanelItem) === "undefined")
{
	BX.CrmControlPanelItem = function()
	{
		this._id = "";
		this._settings = null;
		this._panel = null;
		this._container = null;
		this._actions = null;
		this._childItems = null;
		this._isVisible = true;
		this._menuId = "";
		this._isMenuShown = false;


	};

	BX.CrmControlPanelItem.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "";
			this._menuId = 'crm_menu_popup_' + this._id.toLowerCase();
			this._settings = settings ? settings : BX.CrmParamBag.create(null);
			this._panel = this.getSetting("panel");
			this._container = BX(this._panel.getItemContainerId(this._id));
			if(!this._container)
			{
				throw "BX.CrmControlPanelItem: Container is not found.";
			}

			this._isVisible = this._container.style.display !== "none";
			this._isActive = this.getSetting("isActive", false);
			this._actions = this.getSetting("actions", []);
			this._childItems = this.getSetting("childItems", []);

			BX.bind(
				this._getLink(),
				"click",
				BX.delegate(this._onClick, this)
			);

			if(this._findAction("CREATE"))
			{
				var action = this._findAction("CREATE");
				this._container.appendChild(
					BX.create(
						"A",
						{
							"attrs": { "class": "crm-menu-plus-btn" },
							"props": { "href": this._getActionUrl("CREATE") },
							"events": { "click": BX.delegate(this._onCreateButtonClick, this) }
						}
					)
				);
			}
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.getParam(name, defaultval);
		},
		getId: function()
		{
			return this._id;
		},
		isActive: function()
		{
			return this._isActive;
		},
		setActive: function(active, force, silent)
		{
			active = !!active;
			if(this._isActive === active)
			{
				return;
			}

			if(!force && !this._panel.requireItemActivityChange(this))
			{
				return;
			}

			this._isActive = active;
			if(active)
			{
				BX.addClass(this._getLink(), "crm-menu-item-active");
			}
			else
			{
				BX.removeClass(this._getLink(), "crm-menu-item-active");
			}

			if(!silent)
			{
				this._panel.handleItemActivityChange(this);
			}
		},
		getContainer: function()
		{
			return this._container;
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

			this._container.style.display = visible ? "" : "none";
			this._isVisible = visible;
		},
		hasUrl: function()
		{
			var url = this.getSetting("url");
			return BX.type.isNotEmptyString(url) && url !== "#";
		},
		hasChildItems: function()
		{
			return this._childItems.length > 0;
		},
		getChildItems: function()
		{
			return this._childItems;
		},
		setChildItems: function(childItems)
		{
			this._childItems = BX.type.isArray(childItems) ? childItems : [];
		},
		prepareChildItemSettings: function()
		{
			return (
				{
					id: this._id,
					name: this.getSetting("name", this._id),
					icon: this.getSetting("icon", ""),
					url: this.getSetting("url", "")
				}
			);
		},
		showSubMenu: function()
		{
			if(this._isMenuShown)
			{
				return;
			}

			var menuItems = [];
			for(var i = 0; i < this._childItems.length; i++)
			{
				var childItem = this._childItems[i];
				var name = childItem["name"];
				if(!BX.type.isNotEmptyString(name))
				{
					continue;
				}

				var className = BX.type.isNotEmptyString(childItem["icon"]) ? "crm-menu-more-" + childItem["icon"] : "";
				menuItems.push(
					{ "text": name, "className": className , "href" : BX.type.isNotEmptyString(childItem["url"]) ? childItem["url"] : "" }
				);
			}

			if(menuItems.length === 0)
			{
				return;
			}

			if(typeof(BX.PopupMenu.Data[this._menuId]) !== "undefined")
			{
				BX.PopupMenu.Data[this._menuId].popupWindow.destroy();
				delete BX.PopupMenu.Data[this._menuId];
			}

			var anchor = this._getLink();
			var anchorPos = BX.pos(anchor);

			BX.PopupMenu.show(
				this._menuId,
				anchor,
				menuItems,
				{
					"autoHide": true,
					"offsetLeft": (anchorPos["width"] / 2) - 18,
					"offsetTop": 4,
					"angle":
					{
						"position": "top",
						"offset": 20
					},
					"events":
					{
						"onPopupClose" : BX.delegate(this._onSubMenuClose, this)
					}
				}
		   );

		   this._isMenuShown = true;
		},
		closeSubMenu: function()
		{
			if(!this._isMenuShown)
			{
				return;
			}

			BX.PopupMenu.destroy(this._menuId);
			this._isMenuShown = false;
		},
		_onSubMenuClose: function()
		{
			this.setActive(false, false, true);
			this._isMenuShown = false;
		},
		_getActionUrl: function(id)
		{
			var action = this._findAction(id);
			return action && BX.type.isNotEmptyString(action["url"]) ? action["url"] : "#";
		},
		_findAction: function(id)
		{
			for(var i = 0; i < this._actions.length; i++)
			{
				var action = this._actions[i];
				if(action["id"] === id)
				{
					return action;
				}
			}

			return null;
		},
		_processAction: function(action)
		{
			if(!action)
			{
				return;
			}

			if(BX.type.isNotEmptyString(action["script"]))
			{
				try{ eval(action["script"]); }
				catch(ex){}
			}

			if(BX.type.isNotEmptyString(action["url"]))
			{
				window.location = action["url"];
			}
		},
		_getLink: function()
		{
			return BX.findChild(this._container, { "tag": "A", "class": "crm-menu-item" }, true, false);
		},
		_onClick: function(e)
		{
			if(this.hasChildItems())
			{
				if(!this.isActive())
				{
					this.setActive(true, false, true);
				}

				if(!this._isMenuShown)
				{
					this.showSubMenu();
				}
				else
				{
					this.closeSubMenu();
				}

				return BX.PreventDefault(e);
			}

			if(!this.isActive())
			{
				this.setActive(true);
			}

			return this.hasUrl() ? true : BX.PreventDefault(e);
		},
		_onCreateButtonClick: function(e)
		{
			this._processAction(this._findAction("CREATE"));
			return BX.eventReturnFalse(e);
		}
	};

	BX.CrmControlPanelItem.items = {};
	BX.CrmControlPanelItem.create = function(id, settings)
	{
		var self = new BX.CrmControlPanelItem();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}

if(typeof(BX.CrmControlPanelSliderInitData) === "undefined")
{
	BX.CrmControlPanelSliderInitData = {};
}

if(typeof(BX.CrmControlPanelSlider) === "undefined")
{
	BX.CrmControlPanelSlider = function()
	{
		this._id = "";
		this._settings = null;
		this._wrapper = null;
		this._anchor = null;
		this._items = null;
		this._additionalItem = null;
		this._borderingItemIndex = -1;
		this._resizeHandler = BX.delegate(this._onResize, this);
	};
	BX.CrmControlPanelSlider.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "";
			this._settings = settings ? settings : BX.CrmParamBag.create(null);

			this._wrapper = BX(this.getSetting("wrapperId"));
			if(!this._wrapper)
			{
				throw "BX.CrmControlPanelSlider. Wrapper is not found.";
			}
			this._items = this.getSetting("items");
			if(!this._items)
			{
				throw "BX.CrmControlPanelSlider. Items are not found.";
			}
			this._additionalItem = this.getSetting("additionalItem");
			this._anchor = BX(this.getSetting("anchorId"));


			if(typeof(BX.CrmControlPanelSliderInitData[this._id]) !== "undefined"
				&& typeof(BX.CrmControlPanelSliderInitData[this._id]["borderingItemIndex"]) !== "undefined")
			{
				this._borderingItemIndex = BX.CrmControlPanelSliderInitData[this._id]["borderingItemIndex"];
				this._setupAdditionalItemChildren();
			}

			this.adjust();
			BX.bind(window, 'resize', this._resizeHandler);
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.getParam(name, defaultval);
		},
		getId: function()
		{
			return this._id;
		},
		adjust: function()
		{
			var qty = this._items.length;
			if(qty === 0)
			{
				return;
			}

			var firstItem = this._items[0];
			var ceiling = BX.pos(firstItem.getContainer()).top;

			var item;
			var lastIndex = qty - 1;
			var borderItem = null;
			var borderItemContainer = null;
			var additionalItem = this._additionalItem;
			var additionalItemContainer = additionalItem.getContainer();
			additionalItem.setVisible(false);

			var borderIndex = -1;
			for(var i = lastIndex; i > 0; i--)
			{
				item = this._items[i];
				if(BX.pos(item.getContainer()).top > ceiling)
				{
					continue;
				}

				borderIndex = i;
				if(borderIndex < lastIndex)
				{
					borderItem = item;
					borderItemContainer = item.getContainer();
					borderItemContainer.parentNode.insertBefore(additionalItemContainer, borderItemContainer);

					additionalItem.setVisible(true);
					var borderItemCeiling = BX.pos(borderItem.getContainer()).top;
					additionalItem.setVisible(false);

					while(borderIndex < lastIndex && borderItemCeiling <= ceiling)
					{
						borderIndex++;

						borderItem = this._items[borderIndex];
						borderItemContainer = borderItem.getContainer();
						borderItemContainer.parentNode.insertBefore(additionalItemContainer, borderItemContainer);

						additionalItem.setVisible(true);
						borderItemCeiling = BX.pos(borderItemContainer).top;
						additionalItem.setVisible(false);
					}
				}
				break;
			}

			if(borderIndex < 0)
			{
				borderIndex = 0;
			}

			if(borderIndex < lastIndex)
			{
				borderItem = this._items[borderIndex];
				borderItemContainer = borderItem.getContainer();
				borderItemContainer.parentNode.insertBefore(additionalItemContainer, borderItemContainer);
				additionalItem.setVisible(true);
			}
			this._borderingItemIndex = borderIndex;
			this._setupAdditionalItemChildren();
		},
		_onResize: function(e)
		{
			this.adjust();
		},
		_setupAdditionalItemChildren: function()
		{
			if(!this._additionalItem)
			{
				return;
			}

			var children = [];
			var lastIndex = this._items.length - 1;
			if(this._borderingItemIndex < lastIndex)
			{
				for(var i = lastIndex; i >= this._borderingItemIndex; i--)
				{
					children.push(this._items[i].prepareChildItemSettings());
				}
			}
			if(children.length > 0)
			{
				children.reverse();
			}

			this._additionalItem.closeSubMenu();
			this._additionalItem.setChildItems(children);
		}
	};
	BX.CrmControlPanelSlider.create = function(id, settings)
	{
		var self = new BX.CrmControlPanelSlider();
		self.initialize(id, settings);
		return self;
	};
}
