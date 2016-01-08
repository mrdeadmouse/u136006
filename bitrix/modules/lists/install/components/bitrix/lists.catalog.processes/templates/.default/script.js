BX.CatalogProcessesClass = (function ()
{
	var CatalogProcessesClass = function (parameters)
	{
		this.ajaxUrl = '/bitrix/components/bitrix/lists.catalog.processes/ajax.php';
		this.randomString = parameters.randomString;
	};

	CatalogProcessesClass.prototype.removeElement = function (elem)
	{
		return elem.parentNode ? elem.parentNode.removeChild(elem) : elem;
	};

	CatalogProcessesClass.prototype.addToLinkParam = function (link, name, value)
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

	CatalogProcessesClass.prototype.showModalWithStatusAction = function (response, action)
	{
		response = response || {};
		if (!response.message) {
			if (response.status == 'success') {
				response.message = BX.message('LISTS_LCP_TEMPLATE_STATUS_ACTION_SUCCESS');
			}
			else {
				response.message = BX.message('LISTS_LCP_TEMPLATE_STATUS_ACTION_ERROR') + '. ' + this.getFirstErrorFromResponse(response);
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
		}, 3500);
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
			}, 3500);
		};
	};

	CatalogProcessesClass.prototype.show = function (el)
	{
		if (this.getRealDisplay(el) != 'none')
			return;

		var old = el.getAttribute("displayOld");
		el.style.display = old || "";

		if (this.getRealDisplay(el) === "none" ) {
			var nodeName = el.nodeName, body = document.body, display;

			if (displayCache[nodeName]) {
				display = displayCache[nodeName];
			} else {
				var testElem = document.createElement(nodeName);
				body.appendChild(testElem);
				display = this.getRealDisplay(testElem);

				if (display === "none" ) {
					display = "block";
				}

				body.removeChild(testElem);
				displayCache[nodeName] = display;
			}

			el.setAttribute('displayOld', display);
			el.style.display = display;
		}
	};

	CatalogProcessesClass.prototype.hide = function (el)
	{
		if (!el.getAttribute('displayOld'))
		{
			el.setAttribute("displayOld", el.style.display);
		}
		el.style.display = "none";
	};

	CatalogProcessesClass.prototype.getRealDisplay = function (elem) {
		if (elem.currentStyle) {
			return elem.currentStyle.display;
		} else if (window.getComputedStyle) {
			var computedStyle = window.getComputedStyle(elem, null );
			return computedStyle.getPropertyValue('display');
		}
	};

	CatalogProcessesClass.prototype.modalWindow = function (params)
	{
		params = params || {};
		params.title = params.title || false;
		params.bindElement = params.bindElement || null;
		params.overlay = typeof params.overlay == "undefined" ? true : params.overlay;
		params.autoHide = params.autoHide || false;
		params.closeIcon = typeof params.closeIcon == "undefined"? {right: "20px", top: "10px"} : params.closeIcon;
		params.modalId = params.modalId || 'lists_modal_window_' + (Math.random() * (200000 - 100) + 100);
		params.withoutContentWrap = typeof params.withoutContentWrap == "undefined" ? false : params.withoutContentWrap;
		params.contentClassName = params.contentClassName || '';
		params.contentStyle = params.contentStyle || {};
		params.content = params.content || [];
		params.buttons = params.buttons || false;
		params.events = params.events || {};
		params.withoutWindowManager = !!params.withoutWindowManager || false;

		var contentDialogChildren = [];
		if (params.title) {
			contentDialogChildren.push(BX.create('div', {
				props: {
					className: 'bx-lists-popup-title'
				},
				text: params.title
			}));
		}
		if (params.withoutContentWrap) {
			contentDialogChildren = contentDialogChildren.concat(params.content);
		}
		else {
			contentDialogChildren.push(BX.create('div', {
				props: {
					className: 'bx-lists-popup-content ' + params.contentClassName
				},
				style: params.contentStyle,
				children: params.content
			}));
		}
		var buttons = [];
		if (params.buttons) {
			for (var i in params.buttons) {
				if (!params.buttons.hasOwnProperty(i)) {
					continue;
				}
				if (i > 0) {
					buttons.push(BX.create('SPAN', {html: '&nbsp;'}));
				}
				buttons.push(params.buttons[i]);
			}

			contentDialogChildren.push(BX.create('div', {
				props: {
					className: 'bx-lists-popup-buttons'
				},
				children: buttons
			}));
		}

		var contentDialog = BX.create('div', {
			props: {
				className: 'bx-lists-popup-container'
			},
			children: contentDialogChildren
		});

		params.events.onPopupShow = BX.delegate(function () {
			if (buttons.length) {
				firstButtonInModalWindow = buttons[0];
				BX.bind(document, 'keydown', BX.proxy(this._keyPress, this));
			}

			if(params.events.onPopupShow)
				BX.delegate(params.events.onPopupShow, BX.proxy_context);
		}, this);
		var closePopup = params.events.onPopupClose;
		params.events.onPopupClose = BX.delegate(function () {

			firstButtonInModalWindow = null;
			try
			{
				BX.unbind(document, 'keydown', BX.proxy(this._keypress, this));
			}
			catch (e) { }

			if(closePopup)
			{
				BX.delegate(closePopup, BX.proxy_context)();
			}

			if(params.withoutWindowManager)
			{
				delete windowsWithoutManager[params.modalId];
			}

			BX.proxy_context.destroy();
		}, this);

		var modalWindow;
		if(params.withoutWindowManager)
		{
			if(!!windowsWithoutManager[params.modalId])
			{
				return windowsWithoutManager[params.modalId]
			}
			modalWindow = new BX.PopupWindow(params.modalId, params.bindElement, {
				content: contentDialog,
				closeByEsc: true,
				closeIcon: params.closeIcon,
				autoHide: params.autoHide,
				overlay: params.overlay,
				events: params.events,
				buttons: [],
				zIndex : isNaN(params["zIndex"]) ? 0 : params.zIndex
			});
			windowsWithoutManager[params.modalId] = modalWindow;
		}
		else
		{
			modalWindow = BX.PopupWindowManager.create(params.modalId, params.bindElement, {
				content: contentDialog,
				closeByEsc: true,
				closeIcon: params.closeIcon,
				autoHide: params.autoHide,
				overlay: params.overlay,
				events: params.events,
				buttons: [],
				zIndex : isNaN(params["zIndex"]) ? 0 : params.zIndex
			});

		}

		modalWindow.show();

		return modalWindow;
	};

	CatalogProcessesClass.prototype.installProcesses = function (selector)
	{
		BX.addClass(selector, 'webform-small-button-wait');
		selector.setAttribute('onclick','');
		var selectedProcesses = BX.findChildrenByClassName(BX('bx-lists-lcp-total-div'), 'bx-lists-lcp-table-tr-mousedown');
		var processes = [];
		for(var k in selectedProcesses)
		{
			processes.push(selectedProcesses[k].getAttribute('data-file'))
		}
		var siteId = null;
		if(BX('bx-lists-lcp-site-id'))
		{
			siteId = BX('bx-lists-lcp-site-id').value;
		}
		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.addToLinkParam(this.ajaxUrl, 'action', 'installProcesses'),
			data: {
				siteId: siteId,
				processes: processes,
				sessid: BX.bitrix_sessid()
			},
			onsuccess: BX.delegate(function (result)
			{
				if(result.status == 'success')
				{
					for(var k in selectedProcesses)
					{
						selectedProcesses[k].setAttribute('id', 'not allocated');
						selectedProcesses[k].setAttribute('data-pick-out', 'not allocated');
						selectedProcesses[k].setAttribute('class', 'bx-lists-lcp-table-tr-mouseout');
						var selectedTitle = BX.findChildrenByClassName(selectedProcesses[k], 'bx-lists-lcp-table-td-name');
						for(var i = 0; i < selectedTitle.length; i++)
						{
							selectedTitle[i].innerHTML = selectedTitle[i].innerHTML + BX.message('LISTS_LCP_TEMPLATE_PROCESS_INSTALLED');
						}
					}
					this.showModalWithStatusAction({
						status: 'success',
						message: result.message
					});
				}
				else
				{
					result.errors = result.errors || [{}];
					this.showModalWithStatusAction({
						status: 'error',
						message: result.errors.pop().message
					});
				}
				BX.removeClass(selector, 'webform-small-button-wait');
				selector.setAttribute('onclick','BX["CatalogProcessesClass_'+this.randomString+'"].installProcesses(this);');
			}, this)
		});
	};

	CatalogProcessesClass.prototype.mousedown = function (event)
	{
		var pickOut = event.getAttribute('data-pick-out');
		if(pickOut == 'allocate')
		{
			if(event.className == 'bx-lists-lcp-table-tr-mousedown')
			{
				event.className = 'bx-lists-lcp-table-tr-mouseout';
			}
			else
			{
				event.className = 'bx-lists-lcp-table-tr-mousedown';
			}
		}
	};

	CatalogProcessesClass.prototype.mouseover = function (event)
	{
		var pickOut = event.getAttribute('data-pick-out');
		if(pickOut == 'allocate')
		{
			if(event.className != 'bx-lists-lcp-table-tr-mousedown')
			{
				event.className = 'bx-lists-lcp-table-tr-mouseover';
			}
		}
	};

	CatalogProcessesClass.prototype.mouseout = function (event)
	{
		var pickOut = event.getAttribute('data-pick-out');
		if(pickOut == 'allocate')
		{
			if(event.className != 'bx-lists-lcp-table-tr-mousedown')
			{
				event.className = 'bx-lists-lcp-table-tr-mouseout';
			}
		}
	};

	return CatalogProcessesClass;

})();
