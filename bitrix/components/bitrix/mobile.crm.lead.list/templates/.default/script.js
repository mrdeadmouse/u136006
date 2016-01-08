if(typeof(BX.CrmLeadListView) === "undefined")
{
	BX.CrmLeadListView = function()
	{
		this._isDirty = false;
		this._pageAfterOpenHandler = BX.delegate(this._onAfterPageOpen, this);
	};
	BX.extend(BX.CrmLeadListView, BX.CrmEntityListView);
	BX.CrmLeadListView.prototype.doInitialize = function()
	{
		BX.addCustomEvent("onOpenPageAfter", this._pageAfterOpenHandler);

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
	BX.CrmLeadListView.prototype.getContainer = function()
	{
		return this._container ? this._container : BX.findChild(this._wrapper, { className: "crm_dealings_list" }, true, false);
	};
	BX.CrmLeadListView.prototype.getItemContainers = function()
	{
		return BX.findChild(this.getContainer(), { className: "crm_dealings_list_item" }, true, true);
	};
	BX.CrmLeadListView.prototype.getWaiterClassName = function()
	{
		return "crm_dealings_list_item_wait";
	};
	BX.CrmLeadListView.prototype.createModel = function(data, register)
	{
		var d = this.getDispatcher();
		return d ? d.createEntityModel(data, "LEAD", register) : null;
	};
	BX.CrmLeadListView.prototype.createItemView = function(settings)
	{
		return BX.CrmLeadListItemView.create(settings);
	};
	BX.CrmLeadListView.prototype.createSearchParams = function(val)
	{
		return { FIND: val };
	};
	BX.CrmLeadListView.prototype._processExternalCreate = function(eventArgs)
	{
		this._isDirty = true;
	};
	BX.CrmLeadListView.prototype._onAfterPageOpen = function()
	{
		if(this._isDirty)
		{
			if(this.reload(this.getSetting("reloadUrl", true)))
			{
				this._isDirty = false;
			}
		}
	};
	BX.CrmLeadListView.prototype.getMessage = function(name, defaultVal)
	{
		var m = BX.CrmLeadListView.messages;
		return m.hasOwnProperty(name) ? m[name] : defaultVal;
	};
	BX.CrmLeadListView.prototype._onCreate = function()
	{
		var url = this.getSetting('editUrl', '');
		if(url !== '')
		{
			BX.CrmMobileContext.getCurrent().open({ url: url, cache: false });
		}
	};
	BX.CrmLeadListView.create = function(id, settings)
	{
		var self = new BX.CrmLeadListView();
		self.initialize(id, settings);
		return self;
	};
	if(typeof(BX.CrmLeadListView.messages) === "undefined")
	{
		BX.CrmLeadListView.messages =
		{
		};
	}
}

if(typeof(BX.CrmLeadListItemView) === "undefined")
{
	BX.CrmLeadListItemView = function()
	{
		this._list = this._dispatcher = this._model = this._container = this._progressBar = null;
	};
	BX.extend(BX.CrmLeadListItemView, BX.CrmEntityView);
	BX.CrmLeadListItemView.prototype.doInitialize = function()
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

		if(this._model)
		{
			this._model.addView(this);
		}
	};
	BX.CrmLeadListItemView.prototype.layout = function()
	{
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
					events: { "click": BX.delegate(this._onContainerClick, this) }
				}
			);

			rootContainer.appendChild(this._container);
		}


		var m = this._model;
		if(!m)
		{
			return;
		}
		var c = this._container;

		var titleContainer = BX.create("DIV", { attrs: { className: "crm_dealings_title" } });
		titleContainer.appendChild(document.createTextNode(m.getStringParam("TITLE")));

		if(m.getFloatParam("OPPORTUNITY") > 0.0)
		{
			titleContainer.appendChild(
				BX.create("SPAN",
					{ html: " - " + m.getStringParam("FORMATTED_OPPORTUNITY") }
				)
			);
		}

		c.appendChild(titleContainer);

		var infoWrapper = BX.create("DIV",
			{
				attrs: { className: "crm_dealings_company" }
			}
		);

		c.appendChild(infoWrapper);

		var legendHtml = '';
		var name = BX.util.htmlspecialchars(m.getStringParam("FORMATTED_NAME"));
		var companyTitle =  BX.util.htmlspecialchars(m.getStringParam("COMPANY_TITLE"));
		if(name !== "" && companyTitle !== "")
		{
			legendHtml = '<strong class="fwn">' + name + ', </strong><strong class="fwn" style="color:#7d7d7d;">' + companyTitle + '</strong>';
		}
		else if(name !== "")
		{
			legendHtml = '<strong class="fwn">' + name + '</strong>';
		}
		else if(companyTitle !== "")
		{
			legendHtml = '<strong class="fwn" style="color:#7d7d7d;">' + companyTitle + '</strong>';
		}
		infoWrapper.appendChild(
			BX.create("SPAN", { html: legendHtml })
		);

		var entityId =  m.getId();
		var bar = this._progressBar = BX.CrmProgressBar.create(
			"LEAD_" + entityId,
			{
				entityType: "LEAD",
				entityId: entityId,
				currentStepId: m.getStringParam("STATUS_ID"),
				rootContainer: infoWrapper,
				isEditable: false
			}
		);
		bar.layout();

		c.appendChild(
			BX.create("DIV", { attrs: { className: "clb" } })
		);
	};
	BX.CrmLeadListItemView.prototype.clearLayout = function()
	{
		if(this._container)
		{
			BX.remove(this._container);
			this._container = null;
		}
	};
	BX.CrmLeadListItemView.prototype.scrollInToView = function()
	{
		if(this._container)
		{
			BX.scrollToNode(this._container);
		}
	};
	BX.CrmLeadListItemView.prototype.getModelKey = function()
	{
		return this._model ? this._model.getKey() : "";
	};
	BX.CrmLeadListItemView.prototype._onContainerClick = function(e)
	{
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
	BX.CrmLeadListItemView.prototype.handleModelUpdate = function(model)
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
	BX.CrmLeadListItemView.prototype.handleModelDelete = function(model)
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
	BX.CrmLeadListItemView.create = function(settings)
	{
		var self = new BX.CrmLeadListItemView();
		self.initialize(settings);
		return self;
	};
}
