BX.namespace("BX.Disk");
BX.Disk.FileViewClass = (function ()
{
	var BXFFDocLink = function () {
	this.xpi_path_en =  "/bitrix/webdav/ff_bx_integration@bitrixsoft.com.xpi";
	this.xpi_path_ru =  "/bitrix/webdav/ff_bx_integration@1c-bitrix.ru.xpi";
	this.xpi_version = "0.7";

	try
	{
		if (window.phpVars.LANGUAGE_ID == 'ru')
		{
			this.xpi_path = this.xpi_path_ru;
		} else {
			this.xpi_path = this.xpi_path_en;
		}
	} catch(e) {
		this.xpi_path = this.xpi_path_en;
	}

	this.CheckVersion = function ()
	{
		var pluginUpdate = false;
		if ('undefined' != typeof ff_bx_integration)
		{
			var ver = 2; // version ok
			pluginUpdate = this.CompareVersions(ff_bx_integration, this.xpi_version);
			if (pluginUpdate) ver = 1; // need update
			return ver;
		}
		return 0; // no plugin
	};

	this.GetOfficeType = function ()
	{
		if ('undefined' != typeof ff_bx_integration_office)
			return ff_bx_integration_office;
		else
			return false;
	};

	this.Bind = function (eventName, eventData)
	{
		var id = 'BXPluginDataElement';

		if (BX(id))
			BX.remove(BX(id));

		var element = document.createElement(id);
		element.setAttribute("id", id);
		element.setAttribute("data", eventData);
		document.documentElement.appendChild(element);

		var evt = document.createEvent("Events");
		evt.initEvent(eventName, true, false);
		element.dispatchEvent(evt);
		return true;
	};

	this.OpenConfig = function()
	{
		return this.Bind("BitrixWebdavConfig", "");
	};

	this.OpenDoc = function(doc)
	{
		var items = doc.split('.');
		var ext = items.pop().toLowerCase();
		items.push(ext);
		doc = items.join('.');
		this.Bind("BitrixWebdavOpenFile", doc);
	};

	this.CompareVersions = function (v1, v2) // true if v2 > v1
	{
		var a1 = v1.split(".");
		var a2 = v2.split(".");
		for (var i=0; i<a1.length; i++)
		{
			x1 = parseInt(a1[i]) || 0;
			x2 = parseInt(a2[i]) || 0;
			if (x2 > x1) return true;
		}
		return false;
	};

	this.ShowDialog = function (mode, file)
	{
		if (mode == null || mode == false)
		{
			var version = this.CheckVersion();
			if (version == 2)
				return this.OpenConfig();
			else if (version == 1)
			mode = 'update';
			else
				mode = 'install';
		}

		var installUrl = this.xpi_path;

		BX.CDialog.prototype.btnWdInstall = BX.CDialog.btnWdInstall =
			BX.create('a', {
				text: mode=='update'? BX.message('DISK_FILE_VIEW_BTN_UPDATE') : BX.message('DISK_FILE_VIEW_BTN_INSTALL'),
				props: {
					className: 'bx-disk-btn bx-disk-btn-big bx-disk-btn-green'
				},
				events: {
					click: function (e) {
						window.location = installUrl;
						BX.PopupWindowManager.getCurrentPopup().destroy();
						BX.PreventDefault(e);
						return false;
					}
				}
			});


		BX.CDialog.prototype.btnWdOpen = BX.CDialog.btnWdOpen = BX.create('a', {
			text: BX.message('DISK_FILE_VIEW_BTN_OPEN'),
			props: {
				className: 'bx-disk-btn bx-disk-btn-big bx-disk-btn-transparent'
			},
			events: {
				click: function (e) {
					var disable = BX.findChild(BX.PopupWindowManager.getCurrentPopup().contentContainer, {'attribute':{'name':'ff_extension_disable'}}, true);
					if (disable.checked)
					{
						window.suggest_ff_extension = false;
						if (null != jsUserOptions)
						{
							if(!jsUserOptions.options)
								jsUserOptions.options = new Object();
							jsUserOptions.options['webdav.suggest.ff_extension'] = ['webdav', 'suggest', 'ff_extension', false, false];
							jsUserOptions.SendData(null);
						}
					}

					BX.PopupWindowManager.getCurrentPopup().destroy();
					BX.PreventDefault(e);
					return false;
				}
			}
		});

		BX.CDialog.prototype.btnWdInstallCancell = BX.CDialog.btnWdInstallCancell = BX.create('a', {
			text: BX.message('DISK_FILE_VIEW_BTN_INSTALL_CANCEL'),
			props: {
				className: 'bx-disk-btn bx-disk-btn-big bx-disk-btn-transparent'
			},
			events: {
				click: function (e) {
					var disable = BX.findChild(BX.PopupWindowManager.getCurrentPopup().contentContainer, {'attribute':{'name':'ff_extension_disable'}}, true);
					if (disable && disable.checked)
					{
						window.suggest_ff_extension = false;
						if (null != jsUserOptions)
						{
							jsUserOptions.SaveOption('webdav', 'suggest', 'ff_extension', false);
						}
					}

					BX.PopupWindowManager.getCurrentPopup().destroy();
					BX.PreventDefault(e);
					return false;
				}
			}
		});

		var msg = ((mode=='update') ? 'DISK_FILE_VIEW_FF_EXTENSION_UPDATE' : 'DISK_FILE_VIEW_FF_EXTENSION_INSTALL');
		msg = "<p>" + BX.message(msg).replace('#LINK#', this.xpi_path) + "</p>";
		var title = BX.message('DISK_FILE_VIEW_FF_EXTENSION_TITLE');
		var help = "<p>" + BX.message('DISK_FILE_VIEW_FF_EXTENSION_HELP') + "</p>";
		var disable = "";
		if (file != null)
			disable = "<p style=\"margin-top:20px;\">" + BX.message('DISK_FILE_VIEW_FF_EXTENSION_DISABLE') + "</p>";
		var arParams = {'title': title, 'content': msg+help+disable, 'width':'530', 'height':'200'};
		if (file != null)
		{
			arParams['file'] = file;
			arParams['buttons'] = [BX.CDialog.btnWdInstall, BX.CDialog.btnWdOpen];
		}
		else
		{
			arParams['buttons'] = [BX.CDialog.btnWdInstall, BX.CDialog.btnWdInstallCancell];
		}

		BX.Disk.modalWindow({
			modalId: 'bx-ff-ext-webdav',
			title: title,
			contentStyle: {
				width: '600px',
				paddingTop: '70px',
				paddingBottom: '70px'
			},
			content: [arParams['content']],
			buttons: arParams['buttons']
		});
	};
};


	var historyTabsIsLoaded = false;
	var bpTabsIsLoaded = false;

	var FileViewClass = function (parameters)
	{
		//this.grid = parameters.grid;
		this.webdavEditPath = parameters.webdavEditPath;
		this.object = parameters.object || {};

		this.tabs = new BX.Disk.TabsClass(parameters.tabs);

		this.ajaxUrl = '/bitrix/components/bitrix/disk.file.view/ajax.php';

		if(!parameters.withoutEventBinding)
			this.setEvents();

		if(!this.checkWebdavEditButton())
		{
			BX.remove(BX('bx-disk-file-edit-btn-webdav'));
		}
		else
		{
			BX.style(BX('bx-disk-file-edit-btn-webdav'), 'display', '');
		}
	};

	FileViewClass.prototype.setEvents = function ()
	{
		BX.bind(BX('bx-disk-sidebar-shared-outlink'), 'click', BX.proxy(this.onClickExternalSwitcher, this));
		BX.bind(BX('bx-disk-sidebar-shared-outlink-input'), 'click', BX.proxy(this.onClickExternalInput));
		BX.bind(BX('bx-disk-file-edit-btn'), 'click', BX.proxy(this.onClickEditButton));
		BX.bind(BX('bx-disk-file-edit-btn-webdav'), 'click', BX.proxy(this.onClickWebdavEditButton, this));

		if(!!this.tabs)
		{
			BX.addCustomEvent(this.tabs, 'onChangeTab', BX.delegate(function(tabName){

				if(tabName == 'bp' && bpTabsIsLoaded || tabName == 'history' && historyTabsIsLoaded)
				{
					return;
				}

				if(tabName == 'history')
				{
					var loader = BX.create('div', {
						props: {
							className: "disk-loading-circle"
						}
					});
					BX('bx-disk-version-grid').appendChild(loader);
					BX.addClass(loader, 'disk-anim');

					BX.ajax({
						url: BX.Disk.addToLinkParam(document.location.href.replace(document.location.hash, ''), 'action', 'showVersion'),
						method: 'POST',
						dataType: 'html',
						scriptsRunFirst: true,
						data: {
							sessid: BX.bitrix_sessid()
						},
						onsuccess: BX.delegate(function (data)
						{
							BX.remove(loader);
							historyTabsIsLoaded = true;
							BX.adjust(BX('bx-disk-version-grid'), {
								html: data
							})
						}, this)
					});
				}
				else if(tabName == 'bp')
				{
					var loader = BX.create('div', {
						props: {
							className: "disk-loading-circle"
						}
					});
					BX('bx-disk-bp-content').appendChild(loader);
					BX.addClass(loader, 'disk-anim');

					BX.ajax({
						url: BX.Disk.addToLinkParam(document.location.href.replace(document.location.hash, ''), 'action', 'showBp'),
						method: 'POST',
						dataType: 'html',
						scriptsRunFirst: true,
						data: {
							sessid: BX.bitrix_sessid()
						},
						onsuccess: BX.delegate(function (data)
						{
							BX.remove(loader);
							bpTabsIsLoaded = true;
							BX.adjust(BX('bx-disk-bp-content'), {
								html: data
							})
						}, this)
					});
				}

			}, this));
		}
	};

	FileViewClass.prototype.onClickExternalInput = function(e)
	{
		BX.focus(this);
		this.setSelectionRange(0, this.value.length);
	};

	FileViewClass.prototype.onClickEditButton = function(e)
	{
		BX.PreventDefault(e);
		BX.fireEvent(BX.firstChild(BX('bx-disk-filepage-filename')), 'click');
		//todo shit! or sheet?
		if(top.BX.CViewer.objNowInShow)
		{
			top.BX.CViewer.objNowInShow.runActionByCurrentElement('forceEdit', {obElementViewer: top.BX.CViewer.objNowInShow});
		}
	};

	FileViewClass.prototype.checkWebdavEditButton = function()
	{
		if (BX.browser.IsIE() || BX.browser.IsIE11()) {
			try {
				if (new ActiveXObject("SharePoint.OpenDocuments.2")) {
					return true;
				}
			}
			catch (e) {
			}
			return false;
		}
		else if (navigator.userAgent.indexOf('Firefox') != -1) {
			var fireFoxExtension = new BXFFDocLink();
			var plugin = fireFoxExtension.CheckVersion();
			if (plugin == 2) {
				return true;
			}
			else if ((typeof window.suggest_ff_extension != 'undefined') && window.suggest_ff_extension == true) {
				return true;
			}
		}

		return false;
	};

	FileViewClass.prototype.onClickWebdavEditButton = function(e)
	{
		BX.PreventDefault(e);

		function EditDocWithProgID(file) {
			var prefix = location.protocol + "//" + location.host;
			var url = file;
			if (url.indexOf(prefix) < 0) url = prefix + url;

			if (BX.browser.IsIE() || BX.browser.IsIE11()) {
				try {
					var EditDocumentButton = new ActiveXObject("SharePoint.OpenDocuments.2");
					if (EditDocumentButton) {
						if (EditDocumentButton.EditDocument2(window, url))
							return false;

					}
				}
				catch (e) {
				}
				return true;
			}
			else if (navigator.userAgent.indexOf('Firefox') != -1) {
				var fireFoxExtension = new BXFFDocLink();
				var plugin = fireFoxExtension.CheckVersion();
				if (plugin == 2) {
					if ((navigator.userAgent.indexOf('Mac OS X') != -1) && (url.indexOf('.xl') != -1))
						url = url.replace(/ /g, "%20"); // MS Office 2011 mad
					fireFoxExtension.OpenDoc(url);
					return false;
				}
				else if ((typeof window.suggest_ff_extension != 'undefined') && window.suggest_ff_extension == true) {
					fireFoxExtension.ShowDialog(null, url);
					return false;
				}
				else {
					return true;
				}
			}

			return true;
		}

		if(EditDocWithProgID(this.webdavEditPath))
		{
			return false;
		}
	};

	FileViewClass.prototype.openConfirmRestore = function (parameters)
	{
		var name = parameters.object.name;
		var objectId = parameters.object.id;
		var versionId = parameters.version.id;
		var messageDescription = BX.message('DISK_FILE_VIEW_VERSION_RESTORE_CONFIRM');
		var buttons = [
			BX.create('a', {
				text: BX.message('DISK_FILE_VIEW_VERSION_RESTORE_BUTTON'),
				props: {
					className: 'bx-disk-btn bx-disk-btn-big bx-disk-btn-green'
				},
				events: {
					click: BX.delegate(function (e) {
						BX.PopupWindowManager.getCurrentPopup().destroy();
						BX.PreventDefault(e);

						BX.ajax({
							method: 'POST',
							dataType: 'json',
							url: BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'restoreFromVersion'),
							data: {
								objectId: objectId,
								versionId: versionId,
								sessid: BX.bitrix_sessid()
							},
							onsuccess: BX.delegate(function (data) {
								if (!data) {
									return;
								}
								BX.Disk.showModalWithStatusAction(data);
							}, this)
						});

						return false;
					}, this)
				}
			}),
			BX.create('a', {
				text: BX.message('DISK_FILE_VIEW_VERSION_CANCEL_BUTTON'),
				props: {
					className: 'bx-disk-btn bx-disk-btn-big bx-disk-btn-transparent'
				},
				events: {
					click: function (e) {
						BX.PopupWindowManager.getCurrentPopup().destroy();
						BX.PreventDefault(e);
						return false;
					}
				}
			})
		];

		BX.Disk.modalWindow({
			modalId: 'bx-link-unlink-confirm',
			title: BX.message('DISK_FILE_VIEW_VERSION_RESTORE_TITLE'),
			contentClassName: 'tac',
			contentStyle: {
				paddingTop: '70px',
				paddingBottom: '70px'
			},
			content: messageDescription.replace('#NAME#', name),
			buttons: buttons
		});
	};

	FileViewClass.prototype.openConfirmDeleteVersion = function (parameters)
	{
		var name = parameters.object.name;
		var objectId = parameters.object.id;
		var versionId = parameters.version.id;
		var messageDescription = BX.message('DISK_FILE_VIEW_VERSION_DELETE_VERSION_CONFIRM');
		var buttons = [
			BX.create('a', {
				text: BX.message('DISK_FILE_VIEW_VERSION_DELETE_VERSION_BUTTON'),
				props: {
					className: 'bx-disk-btn bx-disk-btn-big bx-disk-btn-green'
				},
				events: {
					click: BX.delegate(function (e) {
						BX.PopupWindowManager.getCurrentPopup().destroy();
						BX.PreventDefault(e);

						BX.ajax({
							method: 'POST',
							dataType: 'json',
							url: BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'deleteVersion'),
							data: {
								objectId: objectId,
								versionId: versionId,
								sessid: BX.bitrix_sessid()
							},
							onsuccess: BX.delegate(function (data) {
								if (!data) {
									return;
								}
								BX.Disk.showModalWithStatusAction(data);
							}, this)
						});

						return false;
					}, this)
				}
			}),
			BX.create('a', {
				text: BX.message('DISK_FILE_VIEW_VERSION_CANCEL_BUTTON'),
				props: {
					className: 'bx-disk-btn bx-disk-btn-big bx-disk-btn-transparent'
				},
				events: {
					click: function (e) {
						BX.PopupWindowManager.getCurrentPopup().destroy();
						BX.PreventDefault(e);
						return false;
					}
				}
			})
		];

		BX.Disk.modalWindow({
			modalId: 'bx-link-unlink-confirm',
			title: BX.message('DISK_FILE_VIEW_VERSION_DELETE_VERSION_TITLE'),
			contentClassName: 'tac',
			contentStyle: {
				paddingTop: '70px',
				paddingBottom: '70px'
			},
			content: messageDescription.replace('#NAME#', name),
			buttons: buttons
		});
	};

	FileViewClass.prototype.deleteBizProc = function (idBizProc)
	{
		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'deleteBizProc'),
			data: {
				idBizProc: idBizProc,
				fileId: this.object.id,
				sessid: BX.bitrix_sessid()
			},
			onsuccess: BX.delegate(function (response) {
				if(response)
				{
					BX.remove(BX(idBizProc));
				}
			}, this)
		});
	}

	FileViewClass.prototype.stopBizProc = function (idBizProc)
	{
		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'stopBizProc'),
			data: {
				idBizProc: idBizProc,
				fileId: this.object.id,
				sessid: BX.bitrix_sessid()
			},
			onsuccess: BX.delegate(function (response) {
				if(response.status == 'success')
				{
					var url = document.location.href.replace("#tab-bp", '');
					url += "#tab-bp";
					document.location.href = url;
					location.reload();
				}
				else
				{
					response.errors = response.errors || [{}];
					BX.Disk.showModalWithStatusAction({
						status: 'error',
						message: response.errors.pop().message
					})
				}
			}, this)
		});
	}

	FileViewClass.prototype.sortBizProcLog = function ()
	{
		var newOnclickStr = BX.findChildByClassName(BX('workarea-content'), 'bx-sortable').getAttribute('onclick').replace('log_sort=1&', '');
		newOnclickStr = newOnclickStr.replace('?log_workflow', '?log_sort=1&log_workflow');
		newOnclickStr = newOnclickStr.replace('&action=showBp', '');
		BX.findChildByClassName(BX('workarea-content'), 'bx-sortable').setAttribute('onclick', newOnclickStr);
	}

	FileViewClass.prototype.fixUrlForSort = function ()
	{
		var url = document.location.href.replace('&log_sort=1', '');
		url += "#tab-bp";
		document.location.href = url;
	}

	FileViewClass.prototype.onClickExternalSwitcher = function(e)
	{
		var target = e.target || e.srcElement;
		if(BX.hasClass(target.parentNode, 'on'))
		{
			BX.ajax({
				method: 'POST',
				dataType: 'json',
				url: BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'disableExternalLink'),
				data: {
					objectId: this.object.id,
					sessid: BX.bitrix_sessid()
				},
				onsuccess: BX.delegate(function (response) {
				}, this)
			});
			this.switchOffExtLinkInPanel();
			BX.PreventDefault(e);
		}
		else
		{
			BX.ajax({
				method: 'POST',
				dataType: 'json',
				url: BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'generateExternalLink'),
				data: {
					objectId: this.object.id,
					sessid: BX.bitrix_sessid()
				},
				onsuccess: BX.delegate(function (response) {
					if (!response || response.status != 'success') {
						return;
					}

					this.switchOnExtLinkInPanel(response);
					BX.PreventDefault(e);
				}, this)
			});
		}
	};

	FileViewClass.prototype.switchOffExtLinkInPanel = function(params)
	{
		var extInput = BX('bx-disk-sidebar-shared-outlink-input');
		var label = BX('bx-disk-sidebar-shared-outlink-label');
		extInput.value = '';
		BX.adjust(label, {text: BX.message('DISK_FILE_VIEW_EXT_LINK_OFF')});
		BX.removeClass(label.parentNode, 'on');
		BX.addClass(label.parentNode, 'off');
	};

	FileViewClass.prototype.switchOnExtLinkInPanel = function(params)
	{
		var extInput = BX('bx-disk-sidebar-shared-outlink-input');
		var label = BX('bx-disk-sidebar-shared-outlink-label');
		extInput.value = params.link || '';
		BX.adjust(label, {text: BX.message('DISK_FILE_VIEW_EXT_LINK_ON')});
		BX.removeClass(label.parentNode, 'off');
		BX.addClass(label.parentNode, 'on');
	};

	FileViewClass.prototype.getInternalLink = function ()
	{
		BX.Disk.modalWindow({
			modalId: 'bx-disk-internal-link',
			title: BX.message('DISK_FILE_VIEW_COPY_INTERNAL_LINK'),
			contentClassName: 'tac',
			contentStyle: {
			},
			events: {
				onAfterPopupShow: function () {
					var inputLink = BX('disk-get-internal-link');
					BX.focus(inputLink);
					inputLink.setSelectionRange(0, inputLink.value.length)
				},
				onPopupClose: function () {
					this.destroy();
				}
			},
			content: [
				BX.create('label', {
					props: {
						className: 'bx-disk-popup-label',
						for: 'disk-get-internal-link'
					}
				}),
				BX.create('input', {
					style: {
						marginTop: '10px'
					},
					props: {
						id: 'disk-get-internal-link',
						className: 'bx-disk-popup-input',
						type: 'text',
						value: document.location.href
					}
				})
			],
			buttons: [
				BX.create('a', {
					text: BX.message('DISK_FILE_VIEW_BTN_CLOSE'),
					props: {
						className: 'bx-disk-btn bx-disk-btn-big bx-disk-btn-transparent'
					},
					events: {
						click: function () {
							BX.PopupWindowManager.getCurrentPopup().close();
						}
					}
				})
			]
		});
	};

	FileViewClass.prototype.openConfirmDeleteGroupVersion = function (parameters)
	{
		var messageDescription = BX.message('DISK_FILE_VIEW_VERSION_DELETE_GROUP_CONFIRM');
		var buttons = [
			BX.create('a', {
				text: BX.message('DISK_FILE_VIEW_VERSION_DELETE_VERSION_BUTTON'),
				props: {
					className: 'bx-disk-btn bx-disk-btn-big bx-disk-btn-green'
				},
				events: {
					click: BX.delegate(function (e) {
						BX.PopupWindowManager.getCurrentPopup().destroy();
						BX.PreventDefault(e);

						this.grid.ActionDelete();
						return false;
					}, this)
				}
			}),

			BX.create('a', {
				text: BX.message('DISK_FILE_VIEW_VERSION_CANCEL_BUTTON'),
				props: {
					className: 'bx-disk-btn bx-disk-btn-big bx-disk-btn-transparent'
				},
				events: {
					click: function (e) {
						BX.PopupWindowManager.getCurrentPopup().destroy();
						BX.PreventDefault(e);
						return false;
					}
				}
			})
		];

		BX.Disk.modalWindow({
			modalId: 'bx-link-unlink-confirm',
			title: BX.message('DISK_FILE_VIEW_VERSION_DELETE_TITLE'),
			contentClassName: 'tac',
			contentStyle: {
				paddingTop: '70px',
				paddingBottom: '70px'
			},
			content: messageDescription,
			buttons: buttons
		});
	};	

	return FileViewClass;
})();
