BX.ListsProcessesClass = (function ()
{
	var ListsProcessesClass = function (parameters)
	{
		this.ajaxUrl = '/bitrix/components/bitrix/lists.user.processes/ajax.php';
	};

	ListsProcessesClass.prototype.showProcesses = function (iblockId)
	{
		var tabContainer = BX('bx-lists-store_items'),
			menuItemsLists = [],
			tabs = BX.findChildren(tabContainer, {'tag':'span', 'className': 'feed-add-post-form-link-lists'}, true);

		if(tabs.length)
		{
			menuItemsLists = this.getMenuItems(tabs);
			this.showMoreMenuLists(menuItemsLists);
		}
		else
		{
			var siteId = null, siteDir = null;
			if(BX('bx-lists-select-site-id'))
			{
				siteId = BX('bx-lists-select-site-id').value;
			}
			if(BX('bx-lists-select-site'))
			{
				siteDir = BX('bx-lists-select-site').value;
			}
			BX.ajax({
				method: 'POST',
				dataType: 'json',
				url: this.addToLinkParam(this.ajaxUrl, 'action', 'showProcesses'),
				data: {
					siteDir: siteDir,
					siteId: siteId,
					sessid: BX.bitrix_sessid()
				},
				onsuccess: BX.delegate(function (result)
				{
					if(result.status == 'success')
					{
						for(var k in result.lists)
						{
							tabContainer.appendChild(BX.create('span', {
								attrs: {
									'data-name': result.lists[k].name,
									'data-picture': result.lists[k].picture,
									'data-url': 'document.location.href = "'+result.lists[k].url+'"'
								},
								props:{
									className: 'feed-add-post-form-link-lists',
									id: 'bx-lists-tab-create-processes'
								},
								style : {
									display: 'none'
								}
							}));
						}
						tabs = BX.findChildren(tabContainer, {'tag':'span', 'className': 'feed-add-post-form-link-lists'}, true);
						menuItemsLists = this.getMenuItems(tabs);
						this.showMoreMenuLists(menuItemsLists);
					}
					else
					{
						result.errors = result.errors || [{}];
						this.showModalWithStatusAction({
							status: 'error',
							message: result.errors.pop().message
						})
					}
				}, this)
			});
		}
	};

	ListsProcessesClass.prototype.getMenuItems = function(tabs)
	{
		var menuItemsLists = [];
		for (var i = 0; i < tabs.length; i++)
		{
			menuItemsLists.push({
				tabId : "lists",
				text : tabs[i].getAttribute("data-name"),
				className : "feed-add-post-form-lists",
				onclick : tabs[i].getAttribute("data-url")
			});
		}
		return menuItemsLists;
	};

	ListsProcessesClass.prototype.showMoreMenuLists = function(menuItemsLists)
	{
		var menu = BX.PopupMenu.create(
			"lists",
			BX("bx-lists-create-processes"),
			menuItemsLists,
			{
				closeByEsc : true,
				offsetTop: 5,
				offsetLeft: 12,
				angle: true
			}
		);
		var spanIcon = BX.findChildren(BX('popup-window-content-menu-popup-lists'), {'tag':'span', 'className': 'menu-popup-item-icon'}, true),
			spanDataPicture = BX.findChildren(BX('bx-lists-store_items'), {'tag':'span', 'className': 'feed-add-post-form-link-lists'}, true);

		for(var i = 0; i < spanIcon.length; i++)
		{
			spanIcon[i].innerHTML = spanDataPicture[i].getAttribute('data-picture');
		}
		menu.popupWindow.show();
	};

	ListsProcessesClass.prototype.addToLinkParam = function (link, name, value)
	{
		if (!link.length) {
			return '?' + name + '=' + value;
		}
		link = BX.util.remove_url_param(link, name);
		if (link.indexOf('?') != -1) {
			return link + '&' + name + '=' + value;
		}
		return link + '?' + name + '=' + value;
	};

	ListsProcessesClass.prototype.showModalWithStatusAction = function (response, action)
	{
		response = response || {};
		if (!response.message) {
			if (response.status == 'success') {
				response.message = BX.message('LISTS_JS_STATUS_ACTION_SUCCESS');
			}
			else {
				response.message = BX.message('LISTS_JS_STATUS_ACTION_ERROR') + '. ' + this.getFirstErrorFromResponse(response);
			}
		}
		var messageBox = BX.create('div', {
			props: {
				className: 'bx-lists-alert'
			},
			children: [
				BX.create('span', {
					props: {
						className: 'bx-lists-aligner'
					}
				}),
				BX.create('span', {
					props: {
						className: 'bx-lists-alert-text'
					},
					text: response.message
				}),
				BX.create('div', {
					props: {
						className: 'bx-lists-alert-footer'
					}
				})
			]
		});

		var currentPopup = BX.PopupWindowManager.getCurrentPopup();
		if(currentPopup)
		{
			currentPopup.destroy();
		}

		var idTimeout = setTimeout(function ()
		{
			var w = BX.PopupWindowManager.getCurrentPopup();
			if (!w || w.uniquePopupId != 'bx-lists-status-action') {
				return;
			}
			w.close();
			w.destroy();
		}, 3000);
		var popupConfirm = BX.PopupWindowManager.create('bx-lists-status-action', null, {
			content: messageBox,
			onPopupClose: function ()
			{
				this.destroy();
				clearTimeout(idTimeout);
			},
			autoHide: true,
			zIndex: 2000,
			className: 'bx-lists-alert-popup'
		});
		popupConfirm.show();

		BX('bx-lists-status-action').onmouseover = function (e)
		{
			clearTimeout(idTimeout);
		};

		BX('bx-lists-status-action').onmouseout = function (e)
		{
			idTimeout = setTimeout(function ()
			{
				var w = BX.PopupWindowManager.getCurrentPopup();
				if (!w || w.uniquePopupId != 'bx-lists-status-action') {
					return;
				}
				w.close();
				w.destroy();
			}, 3000);
		};
	};

	return ListsProcessesClass;

})();

