BX.LiveFeedShowClass = (function ()
{
	var LiveFeedShowClass = function (parameters)
	{
		this.ajaxUrl = '/bitrix/components/bitrix/lists.lists/ajax.php';
		this.randomString = parameters.randomString;
	};

	LiveFeedShowClass.prototype.showLiveFeed = function (iblockId)
	{
		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.addToLinkParam(this.ajaxUrl, 'action', 'setLiveFeed'),
			data: {
				iblockId: iblockId,
				checked: BX('bx-lists-show-live-feed-'+iblockId).checked,
				sessid: BX.bitrix_sessid()
			},
			onsuccess: BX.delegate(function (result)
			{
				if(result.status == 'error')
				{
					result.errors = result.errors || [{}];
					this.showModalWithStatusAction({
						status: 'error',
						message: result.errors.pop().message
					})
				}
			}, this)
		});
	};

	LiveFeedShowClass.prototype.createDefaultProcesses = function ()
	{
		BX.addClass(BX('bx-lists-default-processes'), 'webform-small-button-wait');
		BX('bx-lists-default-processes').setAttribute('onclick','');
		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.addToLinkParam(this.ajaxUrl, 'action', 'createDefaultProcesses'),
			data: {
				siteId: BX('bx-lists-select-site').value,
				sessid: BX.bitrix_sessid()
			},
			onsuccess: BX.delegate(function (result)
			{
				if(result.status == 'success')
				{
					location.reload();
				}
				else
				{
					BX('bx-lists-default-processes').setAttribute('onclick','BX["LiveFeedShowClass_'+this.randomString+'"].createDefaultProcesses();');
					result.errors = result.errors || [{}];
					this.showModalWithStatusAction({
						status: 'error',
						message: result.errors.pop().message
					});
					BX.removeClass(BX('bx-lists-default-processes'), 'webform-small-button-wait');
				}
			}, this)
		});
	};

	LiveFeedShowClass.prototype.addToLinkParam = function (link, name, value)
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

	LiveFeedShowClass.prototype.showModalWithStatusAction = function (response, action)
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

	return LiveFeedShowClass;

})();

