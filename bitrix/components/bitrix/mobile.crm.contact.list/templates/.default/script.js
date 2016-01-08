if(typeof(BX.CrmContactListView) === "undefined")
{
	BX.CrmContactListView = function()
	{
		this._isDirty = false;
		this._pageAfterOpenHandler = BX.delegate(this._onAfterPageOpen, this);
	};
	BX.extend(BX.CrmContactListView, BX.CrmEntityListView);
	BX.CrmContactListView.prototype.doInitialize = function()
	{
		BX.addCustomEvent("onOpenPageAfter", this._pageAfterOpenHandler);

		var permissions = this.getSetting('permissions', {});
		if(permissions["CREATE"])
		{
			BX.CrmMobileContext.getCurrent().createButtons(
				{
					addPostButton: { type: "plus", style: "custom", callback: BX.delegate(this._onCreate, this) }
				}
			);
		}

		this._classifierContainers = {};
		var classifierData = BX.findChild(this.getContainer(), { className: "crm_entity_classifier" }, true, true);
		if(classifierData)
		{
			for(var i = 0; i < classifierData.length; i++)
			{
				var dataInput = classifierData[i];
				var classifier = dataInput.value;
				this._classifierContainers[classifier] = BX.findParent(dataInput, { className: "crm_contact_list_separator" });
			}
		}
	};
	BX.CrmContactListView.prototype.getContainer = function()
	{
		return this._container ? this._container : BX.findChild(this._wrapper, { className: "crm_contact_list" }, true, false);
	};
	BX.CrmContactListView.prototype.getItemContainers = function()
	{
		return BX.findChild(this.getContainer(), { className: "crm_contact_list_people" }, true, true);
	};
	BX.CrmContactListView.prototype.getWaiterClassName = function()
	{
		return "crm_contact_list_people_wait";
	};
	BX.CrmContactListView.prototype.createModel = function(data, register)
	{
		var d = this.getDispatcher();
		return d ? d.createEntityModel(data, "CONTACT", register) : null;
	};
	BX.CrmContactListView.prototype.createItemView = function(settings)
	{
		return BX.CrmContactListItemView.create(settings);
	};
	BX.CrmContactListView.prototype.createSearchParams = function(val)
	{
		return { FULL_NAME: val };
	};
	BX.CrmContactListView.prototype.getMessage = function(name, defaultVal)
	{
		var m = BX.CrmContactListView.messages;
		return m.hasOwnProperty(name) ? m[name] : defaultVal;
	};
	BX.CrmContactListView.prototype.addItemView = function(itemView)
	{
		var view = itemView.getContainer();
		if(!view)
		{
			return;
		}

		var container = this.resolveItemViewContainer(itemView);
		container.appendChild(view);

		var classifierContainer = BX.findParent(container, { className: "crm_contact_list_separator" });
		if(classifierContainer && classifierContainer.style.display === "none")
		{
			classifierContainer.style.display = "";
		}
	};
	BX.CrmContactListView.prototype.removeItemView = function(itemView)
	{
		var view = itemView.getContainer();
		if(!view)
		{
			return;
		}

		var container = this.resolveItemViewContainer(itemView);
		container.removeChild(view);

		var childCount = container.childNodes ? container.childNodes.length : 0;
		if( childCount == 0)
		{
			var classifierContainer = BX.findParent(container, { className: "crm_contact_list_separator" });
			if(classifierContainer && classifierContainer.style.display === "")
			{
				classifierContainer.style.display = "none";
			}
		}
	};
	BX.CrmContactListView.prototype.resolveItemViewContainer = function(itemView)
	{
		var container = this.getContainer();
		var m = itemView.getModel();
		var classifier = m ? m.getStringParam("CLASSIFIER") : "";

		if(typeof(this._classifierContainers[classifier]) !== "undefined")
		{
			return BX.findChild(this._classifierContainers[classifier], { className: "crm_contact_list_people_list" }, true, false);
		}

		var viewContainer = BX.create("UL",
			{
				attrs: { className: "crm_contact_list_people_list" }
			}
		);

		var classifierContainer = BX.create("LI",
			{
				attrs: { className: "crm_contact_list_separator" },
				children :
				[
					BX.create("INPUT", { attrs: { type:"hidden", value: classifier } }),
					BX.create("SPAN", { text: classifier }),
					viewContainer
				]
			}
		);


		var nextClassifier = "";
		var classifiers = Object.keys(this._classifierContainers);
		for(var i = 0; i < classifiers.length; i++)
		{
			var curClassifier = classifiers[i];
			if(curClassifier > classifier)
			{
				nextClassifier = curClassifier;
			}
		}

		if(nextClassifier === "" && classifiers.length > 0)
		{
			nextClassifier = classifiers[0]
		}

		if(nextClassifier !== "")
		{
			container.insertBefore(classifierContainer, nextClassifier);
		}
		else
		{
			container.appendChild(classifierContainer);
		}

		this._classifierContainers[classifier] = classifierContainer;
		return viewContainer;
	};
	BX.CrmContactListView.prototype._onCreate = function()
	{
		var url = this.getSetting('editUrl', '');
		if(url !== '')
		{
			BX.CrmMobileContext.getCurrent().open({ url: url, cache: false });
		}
	};
	BX.CrmContactListView.prototype._processExternalCreate = function(eventArgs)
	{
		this._isDirty = true;
	};
	BX.CrmContactListView.prototype._onAfterPageOpen = function()
	{
		if(this._isDirty)
		{
			if(this.reload(this.getSetting("reloadUrl", true)))
			{
				this._isDirty = false;
			}
		}
	};
	BX.CrmContactListView.create = function(id, settings)
	{
		var self = new BX.CrmContactListView();
		self.initialize(id, settings);
		return self;
	};
	if(typeof(BX.CrmContactListView.messages) === "undefined")
	{
		BX.CrmContactListView.messages =
		{
		};
	}
}

if(typeof(BX.CrmContactListItemView) === "undefined")
{
	BX.CrmContactListItemView = function()
	{
		this._list = this._dispatcher = this._model = this._container = null;
	};
	BX.extend(BX.CrmContactListItemView, BX.CrmEntityView);
	BX.CrmContactListItemView.prototype.doInitialize = function()
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
	BX.CrmContactListItemView.prototype.layout = function()
	{
		if(this._container)
		{
			BX.cleanNode(this._container);
		}
		else
		{
			this._container = BX.create("LI",
				{
					attrs: { "class": "crm_contact_list_people" },
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
					attrs: { className: "crm_contactlist_info" },
					children:
					[
						BX.create("IMG",
							{ attrs: { src: m.getStringParam("LIST_IMAGE_URL") } }
						),
						BX.create("STRONG",
							{ text: m.getStringParam("FORMATTED_NAME") }
						),
						BX.create("SPAN",
							{ text: m.getStringParam("LEGEND") }
						)
					]
				}
			)
		);
	};
	BX.CrmContactListItemView.prototype.clearLayout = function()
	{
		this._list.removeItemView(this);
		this._container = null;
	};
	BX.CrmContactListItemView.prototype.scrollInToView = function()
	{
		if(this._container)
		{
			BX.scrollToNode(this._container);
		}
	};
	BX.CrmContactListItemView.prototype.getContainer = function()
	{
		return this._container;
	};
	BX.CrmContactListItemView.prototype.getModel = function()
	{
		return this._model;
	};
	BX.CrmContactListItemView.prototype.getModelKey = function()
	{
		return this._model ? this._model.getKey() : "";
	};
	BX.CrmContactListItemView.prototype._onContainerClick = function(e)
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
	BX.CrmContactListItemView.prototype.handleModelUpdate = function(model)
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
	BX.CrmContactListItemView.prototype.handleModelDelete = function(model)
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
	BX.CrmContactListItemView.create = function(settings)
	{
		var self = new BX.CrmContactListItemView();
		self.initialize(settings);
		return self;
	};
}
