if(typeof(BX.CrmInvoiceListView) === "undefined")
{
	BX.CrmInvoiceListView = function()
	{
		//this._mode = this._contextId = "";
		this._selectedItem = null;

		this._isDirty = false;
		this._pageAfterOpenHandler = BX.delegate(this._onAfterPageOpen, this);
	};
	BX.extend(BX.CrmInvoiceListView, BX.CrmEntityListView);
	BX.CrmInvoiceListView.prototype.doInitialize = function()
	{
		BX.addCustomEvent("onOpenPageAfter", this._pageAfterOpenHandler);
		//this._mode = this.getSetting("mode", "");
		this._contextId = this.getSetting("contextId", "");

		var permissions = this.getSetting("permissions", {});
		if(permissions["CREATE"])
		{
			BX.CrmMobileContext.getCurrent().createButtons(
				{
					addPostButton: { type: "plus", style: "custom", callback: BX.delegate(this._onCreate, this) }
				}
			);
		}
	};
	BX.CrmInvoiceListView.prototype.getContainer = function()
	{
		return this._container ? this._container : BX.findChild(this._wrapper, { className: "crm_dealings_list" }, true, false);
	};
	BX.CrmInvoiceListView.prototype.getItemContainers = function()
	{
		return BX.findChild(this.getContainer(), { className: "crm_dealings_list_item" }, true, true);
	};
	BX.CrmInvoiceListView.prototype.getWaiterClassName = function()
	{
		return "crm_dealings_list_item_wait";
	};
	BX.CrmInvoiceListView.prototype.createModel = function(data, register)
	{
		var d = this.getDispatcher();
		return d ? d.createEntityModel(data, "INVOICE", register) : null;
	};
	BX.CrmInvoiceListView.prototype.createItemView = function(settings)
	{
		return BX.CrmInvoiceListItemView.create(settings);
	};
	BX.CrmInvoiceListView.prototype.createSearchParams = function(val)
	{
		return { ORDER_TOPIC: val };
	};
	BX.CrmInvoiceListView.prototype.getMessage = function(name, defaultVal)
	{
		var m = BX.CrmInvoiceListView.messages;
		return m.hasOwnProperty(name) ? m[name] : defaultVal;
	};
	BX.CrmInvoiceListView.prototype._onCreate = function()
	{
		var url = this.getSetting('editUrl', '');
		if(url !== '')
		{
			BX.CrmMobileContext.getCurrent().open({ url: url, cache: false });
		}
	};
	BX.CrmInvoiceListView.prototype.getContextId = function()
	{
		return this._contextId;
	};
	/*BX.CrmInvoiceListView.prototype.getMode = function()
	{
		return this._mode;
	};
	BX.CrmInvoiceListView.prototype._notifySelected = function()
	{
		if(!this._selectedItem)
		{
			return;
		}

		var m = this._selectedItem.getModel();
		if(m)
		{
			var c = BX.CrmMobileContext.getCurrent();
			c.riseEvent(
				"onCrmDealSelect",
				{
					id: m.getId(),
					title: m.getStringParam("TITLE"),
					contextId: this._contextId
				},
				2
			);
			c.back();
		}
	};
	BX.CrmInvoiceListView.prototype.handleItemSelection = function(item)
	{
		if(this._mode !== "SELECTOR")
		{
			return true;
		}

		this._selectedItem = item;
		window.setTimeout(BX.delegate(this._notifySelected, this), 0);
		return false;
	};
	BX.CrmInvoiceListView.prototype.initializeFromExternalData = function()
	{
		var self = this;
		BX.CrmMobileContext.getCurrent().getPageParams(
			{
				callback: function(data)
				{
					if(data)
					{
						var contextId = BX.type.isNotEmptyString(data["contextId"]) ? data["contextId"] : "";
						if(contextId !== self._contextId)
						{
							self._contextId = contextId;
						}
					}
				}
			}
		);
	};*/
	BX.CrmInvoiceListView.prototype._processExternalCreate = function(eventArgs)
	{
		this._isDirty = true;
	};
	BX.CrmInvoiceListView.prototype._onAfterPageOpen = function()
	{
		if(this._isDirty && this.reload(this.getSetting("reloadUrl", true)))
		{
			this._isDirty = false;
		}
		else
		{
			this.initializeFromExternalData();
		}
	};
	BX.CrmInvoiceListView.prototype.prepareNumber = function(number)
	{
		return this.getMessage("numberTemplate", "").replace(/#NUM#/gi, number);
	};
	BX.CrmInvoiceListView.create = function(id, settings)
	{
		var self = new BX.CrmInvoiceListView();
		self.initialize(id, settings);
		return self;
	};
	if(typeof(BX.CrmInvoiceListView.messages) === "undefined")
	{
		BX.CrmInvoiceListView.messages =
		{
		};
	}
}

if(typeof(BX.CrmInvoiceListItemView) === "undefined")
{
	BX.CrmInvoiceListItemView = function()
	{
		this._list = this._dispatcher = this._model = this._container = this._progressBar = null;
		this._containerClickHandler = BX.delegate(this._onContainerClick, this);
		this._hasLayout = false;
	};
	BX.extend(BX.CrmInvoiceListItemView, BX.CrmEntityView);
	BX.CrmInvoiceListItemView.prototype.doInitialize = function()
	{
		this._list = this.getSetting("list", null);
		this._dispatcher = this.getSetting("dispatcher", null);
		this._model = this.getSetting("model", null);
		this._container = this.getSetting("container", null);
		this._hasLayout = this._container !== null;

		if(this._hasLayout)
		{
			BX.bind(this._container, "click", this._containerClickHandler);
		}

		if(!this._model && this._hasLayout)
		{
			var id = this._container.getAttribute("data-entity-id");
			if(BX.type.isNotEmptyString(id))
			{
				this._model = this._dispatcher.getModelById(id);
			}
		}

		if(this._model)
		{
			this._model.addView(this);
		}
	};
	BX.CrmInvoiceListItemView.prototype.layout = function(force)
	{
		force = !!force;

		if(this._hasLayout && !force)
		{
			return;
		}

		//var isInSelectorMode = this._list.getMode() === "SELECTOR";
		if(this._container)
		{
			BX.cleanNode(this._container);
		}
		else
		{
			var rootContainer = this.getSetting("rootContainer", null);
			this._container = BX.create("LI",
				{
					attrs: { "class": "crm_dealings_list_item" },
					events: { "click": this._containerClickHandler }
				}
			);

			/*if(isInSelectorMode)
			{
				BX.addClass(this._container, "crm_arrow");
			}*/

			rootContainer.appendChild(this._container);
		}

		var m = this._model;
		if(!m)
		{
			return;
		}
		var c = this._container;

		var titleContainer = BX.create("DIV", { attrs: { className: "crm_dealings_title" } });
		c.appendChild(titleContainer);

		titleContainer.appendChild(
			BX.create("SPAN",
				{
					attrs: { className: "crm_numorder" },
					text: this._list.prepareNumber(m.getStringParam("ACCOUNT_NUMBER")) + " "
				}
			)
		);
		titleContainer.appendChild(
			document.createTextNode(m.getStringParam("ORDER_TOPIC"))
		);
		titleContainer.appendChild(
			BX.create("SPAN", { text: " - " + m.getStringParam("FORMATTED_PRICE") })
		);

		var infoContainer = BX.create("DIV", { attrs: { className: "crm_dealings_company" } });
		c.appendChild(infoContainer);

		var clientName = m.getStringParam("CONTACT_FULL_NAME", "");
		if(clientName === "")
		{
			clientName = m.getStringParam("COMPANY_TITLE", "");
		}

		var clientInfo = BX.create("SPAN");
		infoContainer.appendChild(clientInfo);
		clientInfo.appendChild(
			BX.create("SPAN",
				{
					style: { color: "#6f7272" },
					text: this._list.getMessage("clientCaption", "") + " "
				}
			));
		clientInfo.appendChild(document.createTextNode(clientName));

		var dealTitle = m.getStringParam("DEAL_TITLE", "");
		if(dealTitle !== "")
		{
			var dealInfo = BX.create("SPAN");
			infoContainer.appendChild(dealInfo);
			dealInfo.appendChild(
				BX.create("SPAN",
					{
						style: { color: "#6f7272" },
						text: this._list.getMessage("dealCaption", "") + " "
					}
				));
			dealInfo.appendChild(document.createTextNode(dealTitle));
		}

		var entityId =  m.getId();
		this._progressBar = BX.CrmProgressBar.create(
			"INVOICE_" + entityId,
			{
				entityType: "INVOICE",
				entityId: entityId,
				currentStepId: m.getStringParam("STATUS_ID"),
				rootContainer: infoContainer,
				isEditable: false
			}
		);

		this._progressBar.layout();

		c.appendChild(
			BX.create("DIV", { attrs: { className: "clb" } })
		);

		this._hasLayout = true;
	};
	BX.CrmInvoiceListItemView.prototype.clearLayout = function()
	{
		if(!this._hasLayout)
		{
			return;
		}

		BX.unbind(this._container, "click", this._containerClickHandler);
		BX.cleanNode(this._container);
		BX.remove(this._container);
		this._container = null;

		this._hasLayout = false;
	};
	BX.CrmInvoiceListItemView.prototype.scrollInToView = function()
	{
		if(this._container)
		{
			BX.scrollToNode(this._container);
		}
	};
	BX.CrmInvoiceListItemView.prototype.getModelKey = function()
	{
		return this._model ? this._model.getKey() : "";
	};
	BX.CrmInvoiceListItemView.prototype.getModel = function()
	{
		return this._model;
	};
	BX.CrmInvoiceListItemView.prototype._onContainerClick = function(e)
	{
		/*if(!this._list.handleItemSelection(this))
		{
			return;
		}*/

		var m = this._model;
		if(!m)
		{
			return;
		}

		var showUrl = m.getDataParam("SHOW_URL", "");
		if(showUrl !== "")
		{
			BX.CrmMobileContext.redirect({ url: showUrl });
		}
	};
	BX.CrmInvoiceListItemView.prototype.handleModelUpdate = function(model)
	{
		if(this._model !== model)
		{
			return;
		}

		this.layout(true);
		if(this._list)
		{
			this._list.handleItemUpdate(this);
		}
	};
	BX.CrmInvoiceListItemView.prototype.handleModelDelete = function(model)
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
	BX.CrmInvoiceListItemView.create = function(settings)
	{
		var self = new BX.CrmInvoiceListItemView();
		self.initialize(settings);
		return self;
	};
}
