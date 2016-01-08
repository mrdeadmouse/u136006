BX.namespace("BX.Disk");
BX.Disk.BreadcrumbsClass = (function ()
{

	var BreadcrumbsClass = function (parameters)
	{
		this.containerId = parameters.containerId;
		this.collapsedCrumbs = parameters.collapsedCrumbs || [];
		this.showOnlyDeleted = parameters.showOnlyDeleted || 0;
		this.container = BX(this.containerId);

		this.ajaxUrl = '/bitrix/components/bitrix/disk.breadcrumbs/ajax.php';

		//this.adjust();
		this.container.style.opacity = 1;

		this.setEvents();
	};

	BreadcrumbsClass.prototype.setEvents = function ()
	{
		BX.bindDelegate(this.container, "click", {className: 'icon-arrow'}, BX.proxy(this.onClickArrow, this));
		BX.bind(BX('root_dots_' + this.containerId), "click", BX.proxy(this.onClickDots, this));
		//BX.bind(window, 'resize', BX.proxy(this.adjust, this));
	};

	BreadcrumbsClass.prototype.adjust = function ()
	{
		var toolbarContainer = BX.findParent(this.container, {
			className: 'bx-disk-interface-toolbar-container'
		}, BX('workarea-content'));

		if (toolbarContainer) {
			var sortContainer = BX.findChild(toolbarContainer, {
				className: 'bx-disk-interface-sort'
			}, true);
		}

		var breadcrumbsWidth = this.container.offsetWidth;
		var toolbarWidth = toolbarContainer ? toolbarContainer.offsetWidth : 0;
		var sortWidth = sortContainer ? sortContainer.offsetWidth : 0;

		if (!toolbarWidth) {
			return;
		}
		var maxWidth = sortWidth + breadcrumbsWidth;
		if (toolbarWidth > maxWidth) {
			return;
		}
		var crumbs = BX.findChildren(this.container, {
			tagName: 'span',
			className: 'bx-disk-interface-bread-crumbs-item'
		}, true);

		var storage = crumbs.shift();
		var lastCrumb = crumbs.pop();
		this.collapsedCrumbs = [];
		for (var i in crumbs) {
			if (sortWidth + this.container.offsetWidth - toolbarWidth <= 0) {
				break;
			}
			if (!crumbs.hasOwnProperty(i)) {
				continue;
			}
			var crumb = crumbs[i];
			var separator = BX.nextSibling(crumb);
			var separatorWidth = 0;
			if (BX.hasClass(separator, 'bx-disk-bread-crumbs-separator')) {
				separatorWidth = separator.offsetWidth;
			}
			else {
				separator = null;
			}

			this.collapsedCrumbs.push({
				text: crumb.getAttribute('data-objectName'),
				title: crumb.getAttribute('data-objectName'),
				href: crumb.getAttribute('data-objectParentPath')
			});
			BX.hide(crumb);
			separator && BX.hide(separator);
		}
		if (sortWidth + this.container.offsetWidth - toolbarWidth > 0) {
			this.collapsedCrumbs.unshift({
				text: storage.getAttribute('data-objectName'),
				title: storage.getAttribute('data-objectName'),
				href: storage.getAttribute('data-objectParentPath')
			});
			BX.hide(storage);
		}


		BX('root_dots_' + this.containerId).style.display = 'inline-block';
		BX('root_arrow_' + this.containerId).style.display = 'none';
		BX.bind(BX('root_dots_' + this.containerId), "click", BX.proxy(this.onClickDots, this));
	};

	BreadcrumbsClass.prototype.expand = function (crumb, arrow, items)
	{
		var objectId = crumb.getAttribute('data-objectId');
		var basePath = crumb.getAttribute('data-objectParentPath');
		if (basePath == '/') {
			basePath = '';
		}
		else if (basePath.lastIndexOf('/') == basePath.length - 1) {
			basePath = basePath.substr(0, basePath.length - 1);
		}

		var dropdownElements = [];
		for (var i in items) {
			if (!items.hasOwnProperty(i)) {
				continue;
			}
			var item = items[i];
			dropdownElements.push({
				text: item['name'],
				title: item['name'],
				href: basePath + '/' + encodeURIComponent(item['name']) + '/'
			});
		}
		BX.PopupMenu.show(
			'disk_breadcrumbs_' + objectId,
			arrow,
			dropdownElements,
			{
				autoHide: true,
				//offsetTop: 0,
				//offsetLeft:25,
				angle: {offset: 0},
				events: {
					onPopupClose: function ()
					{
					}
				}
			}
		);
	};

	BreadcrumbsClass.prototype.onClickDots = function (event)
	{
		var arrowTarget = event.srcElement || event.target;
		BX.PopupMenu.show(
			'disk_breadcrumbs_dots',
			arrowTarget,
			this.collapsedCrumbs,
			{
				autoHide: true,
				//offsetTop: 0,
				//offsetLeft:25,
				angle: {offset: 0},
				events: {
					onPopupClose: function ()
					{
					}
				}
			}
		);

		BX.PreventDefault(event);
	};

	BreadcrumbsClass.prototype.onClickArrow = function (event)
	{
		var arrowTarget = event.srcElement || event.target;
		var crumb = BX.findParent(arrowTarget, {
			className: 'bx-disk-interface-bread-crumbs-item-container'
		}, this.container);

		var objectId = crumb.getAttribute('data-objectId');
		var isRoot = crumb.getAttribute('data-isRoot');
		if (!objectId) {
			BX.PreventDefault(event);
			return;
		}

		var menu = BX.PopupMenu.getMenuById('disk_breadcrumbs_' + objectId);
		if(menu && menu.popupWindow)
		{
			if(menu.popupWindow.isShown())
			{
				BX.PopupMenu.destroy('disk_breadcrumbs_' + objectId);
			}
			else
			{
				menu.popupWindow.show();
			}
			BX.PreventDefault(event);

			return;
		}

		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'showSubFolders'),
			data: {
				objectId: objectId,
				showOnlyDeleted: this.showOnlyDeleted? 1 : 0,
				isRoot: isRoot? 1 : 0,
				sessid: BX.bitrix_sessid()
			},
			onsuccess: BX.delegate(function (data)
			{
				if (!data) {
					return;
				}

				this.expand(crumb, arrowTarget, data.items);
			}, this)
		});


		BX.PreventDefault(event);
	};

	return BreadcrumbsClass;
})();
