if(typeof(BX.CrmCompanyListView) === "undefined")
{
	BX.CrmCompanyListView = function()
	{
		this._isDirty = false;
		this._pageAfterOpenHandler = BX.delegate(this._onAfterPageOpen, this);
	};
	BX.extend(BX.CrmCompanyListView, BX.CrmEntityListView);
	BX.CrmCompanyListView.prototype.doInitialize = function()
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
	BX.CrmCompanyListView.prototype.getContainer = function()
	{
		return this._container ? this._container : BX.findChild(this._wrapper, { className: "crm_company_list" }, true, false);
	};
	BX.CrmCompanyListView.prototype.getItemContainers = function()
	{
		return BX.findChild(this.getContainer(), { className: "crm_company_list_item" }, true, true);
	};
	BX.CrmCompanyListView.prototype.getWaiterClassName = function()
	{
		return "crm_company_list_item_wait";
	};
	BX.CrmCompanyListView.prototype.createModel = function(data, register)
	{
		var d = this.getDispatcher();
		return d ? d.createEntityModel(data, "COMPANY", register) : null;
	};
	BX.CrmCompanyListView.prototype.createItemView = function(settings)
	{
		return BX.CrmCompanyListItemView.create(settings);
	};
	BX.CrmCompanyListView.prototype.createSearchParams = function(val)
	{
		return { TITLE: val };
	};
	BX.CrmCompanyListView.prototype._onCreate = function()
	{
		var url = this.getSetting('editUrl', '');
		if(url !== '')
		{
			BX.CrmMobileContext.getCurrent().open({ url: url, cache: false });
		}
	};
	BX.CrmCompanyListView.prototype._processExternalCreate = function(eventArgs)
	{
		this._isDirty = true;
	};
	BX.CrmCompanyListView.prototype._onAfterPageOpen = function()
	{
		if(this._isDirty)
		{
			if(this.reload(this.getSetting("reloadUrl", true)))
			{
				this._isDirty = false;
			}
		}
	};
	BX.CrmCompanyListView.prototype.getMessage = function(name, defaultVal)
	{
		var m = BX.CrmCompanyListView.messages;
		return m.hasOwnProperty(name) ? m[name] : defaultVal;
	};
	BX.CrmCompanyListView.create = function(id, settings)
	{
		var self = new BX.CrmCompanyListView();
		self.initialize(id, settings);
		return self;
	};
	if(typeof(BX.CrmCompanyListView.messages) === "undefined")
	{
		BX.CrmCompanyListView.messages =
		{
		};
	}
}

if(typeof(BX.CrmCompanyListItemView) === "undefined")
{
	BX.CrmCompanyListItemView = function()
	{
		this._list = this._dispatcher = this._model = this._container = null;
	};
	BX.extend(BX.CrmCompanyListItemView, BX.CrmEntityView);
	BX.CrmCompanyListItemView.prototype.doInitialize = function()
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
	BX.CrmCompanyListItemView.prototype.layout = function()
	{
		if(this._container)
		{
			BX.cleanNode(this._container);
		}
		else
		{
			this._container = BX.create("LI",
				{
					attrs: { "class": "crm_company_list_item crm_arrow" },
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

		var imageContainer = BX.create("SPAN");
		var imageUrl = m.getStringParam("LIST_IMAGE_URL");
		if(imageUrl !== "")
		{
			imageContainer.appendChild(
				BX.create("IMG",
					{ attrs: { src: m.getStringParam("LIST_IMAGE_URL") } }
				)
			);
		}

		this._container.appendChild(
			BX.create("A",
				{
					attrs: { className: "crm_company_img" },
					//events: { "click": BX.delegate(this._onImageClick, this) },
					//events: { "click": BX.PreventDefault },
					children: [ imageContainer ]
				}
			)
		);

		this._container.appendChild(
			BX.create("A",
				{
					attrs: { className: "crm_company_title" },
					text: m.getStringParam("TITLE")
					//events: { "click": BX.delegate(this._onTitleClick, this) }
					//events: { "click": BX.PreventDefault }
				}
			)
		);

		var detailContainer = BX.create(
			"DIV",
			{
				attrs: { className: "crm_company_company" }
			}
		);


		var typeName = m.getStringParam("COMPANY_TYPE_NAME");
		if(typeName !== "")
		{
			detailContainer.appendChild(
				document.createTextNode(this._list.getMessage("typeTitle") + ": ")
			);

			detailContainer.appendChild(BX.create("SPAN", { text: typeName }));
		}

		var industryName = m.getStringParam("INDUSTRY_NAME");
		if(industryName !== "")
		{
			if(typeName !== "")
			{
				detailContainer.appendChild(BX.create("BR"));
			}
			detailContainer.appendChild(
				document.createTextNode(this._list.getMessage("industryTitle") + ": ")
			);

			detailContainer.appendChild(BX.create("SPAN", { text: industryName }));
		}

		this._container.appendChild(detailContainer);

		this._container.appendChild(
			BX.create("DIV", { attrs: { className: "clb" } })
		);
	};
	BX.CrmCompanyListItemView.prototype.clearLayout = function()
	{
		this._list.removeItemView(this);
		this._container = null;
	};
	BX.CrmCompanyListItemView.prototype.scrollInToView = function()
	{
		if(this._container)
		{
			BX.scrollToNode(this._container);
		}
	};
	BX.CrmCompanyListItemView.prototype.getContainer = function()
	{
		return this._container;
	};
	BX.CrmCompanyListItemView.prototype.getModel = function()
	{
		return this._model;
	};
	BX.CrmCompanyListItemView.prototype.getModelKey = function()
	{
		return this._model ? this._model.getKey() : "";
	};
	BX.CrmCompanyListItemView.prototype.redirectToView = function()
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
	BX.CrmCompanyListItemView.prototype._onContainerClick = function(e)
	{
		this.redirectToView();
	};
	BX.CrmCompanyListItemView.prototype._onImageClick = function(e)
	{
		this.redirectToView();
		return BX.PreventDefault(e);
	};
	BX.CrmCompanyListItemView.prototype._onTitleClick = function(e)
	{
		this.redirectToView();
		return BX.PreventDefault(e);
	};
	BX.CrmCompanyListItemView.prototype.handleModelUpdate = function(model)
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
	BX.CrmCompanyListItemView.prototype.handleModelDelete = function(model)
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
	BX.CrmCompanyListItemView.create = function(settings)
	{
		var self = new BX.CrmCompanyListItemView();
		self.initialize(settings);
		return self;
	};
}
