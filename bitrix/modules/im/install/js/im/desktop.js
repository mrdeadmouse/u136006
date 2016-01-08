/**
 * Class for Desktop App
 * @param params
 * @constructor
 */

;(function (window)
{
	if (window.BX.desktop) return;

	var BX = window.BX;

	BX.desktop = function (params)
	{
		params = params || {};

		this.apiReady = typeof(BXDesktopSystem) != "undefined" || typeof(BXDesktopWindow) != "undefined";
		this.clientVersion = 0;

		this.autorun = null;
		this.focusTimeout = null;
		this.lastSetIcon = null;
		this.showNotifyId = {};
		this.popupConfirm = null;
		this.htmlWrapperHead = null;
		this.tryCheckConnect = {};

		this.topmostWindow = null;
		this.topmostWindowTimeout = null;

		this.content = null;
		this.contentFullWindow = true;
		this.contentMenu = null;
		this.contentAvatar = null;
		this.contentTab = null;
		this.contentTabContent = null;

		this.currentTab = '';
		this.currentTabTarget = '';
		this.lastTab = '';
		this.lastTabTarget = '';

		this.path = {};
		this.path.mainUserOptions = '/desktop_app/options.ajax.php';
		this.path.pathToAjax = '/desktop_app/im.ajax.php';

		this.tabItems = {};
		this.tabRedrawTimeout = null;
		this.userInfo = {id: 0, name: '', gender: 'M', avatar: '', profile: ''};

		/* sizes */
		this.width = 914;
		this.height = 454;
		this.minWidth = 914;
		this.minHeight = 454;

		this.timeoutDelayOfLogout = null;

		this.addCustomEvent("bxImLogoutInit", BX.delegate(function(terminate, reason) {
			this.onCustomEventForTab(TAB_CP, "bxImLogoutStart", []);
			this.logout(terminate, reason, true);
		}, this));

		BX.bind(window, "keydown", BX.delegate(function(e) {
			if (e.keyCode == 82 && (e.ctrlKey == true || e.metaKey == true))
			{
				if (e.shiftKey == true && typeof(BXIM) != 'undefined')
				{
					BXIM.setLocalConfig('global_msz', false);
					BX.desktop.apiReady = false;
					console.log('NOTICE: User use /windowReload + /clearWindowSize');
				}
				else
				{
					console.log('NOTICE: User use /windowReload');
				}
				this.windowReload();
			}
			else if (e.keyCode == 68 && (e.ctrlKey == true || e.metaKey == true) && e.shiftKey == true)
			{
				this.openDeveloperTools();
				console.log('NOTICE: User use /openDeveloperTools');
			}
			else if (e.keyCode == 76 && (e.ctrlKey == true || e.metaKey == true) && e.shiftKey == true)
			{
				this.openLogsFolder();
				console.log('NOTICE: User use /openLogsFolder');
			}
		}, this));
	};

	BX.desktop.prototype.init = function ()
	{
		if (BX('bx-desktop-placeholder'))
		{
			this.contentFullWindow = false;
			this.content = BX('bx-desktop-placeholder');

			if (this.content.offsetWidth < this.minWidth)
				BX.style(this.content, 'width', this.minWidth+'px');

			if (this.content.offsetHeight < this.minHeight)
				BX.style(this.content, 'height', this.minHeight+'px');
		}
		else
		{
			this.content = BX.create('div', {attrs: {id: 'bx-desktop-placeholder'}});
			document.body.insertBefore(this.content, document.body.firstChild);
		}

		if (this.ready() && !this.enableInVersion(26))
		{
			BX.PULL.tryConnectSet(null, false);
			this.notSupported();
			this.apiReady = false;

			return false;
		}

		this.setWindowResizable(true);
		this.setWindowMinSize({ Width: this.minWidth, Height: this.minHeight });

		if (this.ready())
			console.log(BX.message('BXD_DEFAULT_TITLE').replace('#VERSION#', this.getApiVersion(true)));

		if (!BX.browser.IsMac() && document.head)
			document.head.insertBefore(BX.create("style", {attrs: {type: 'text/css'}, html: "@font-face { font-family: 'helvetica neue'; src: local('Arial'); } @font-face { font-family: 'Helvetica'; src: local('Arial'); }"}), document.head.firstChild);

		BX.ready(function(){
			BX.addClass(document.body, 'bx-desktop');
		});
		BX.desktop.addTab({
			id: 'exit',
			title: BX.message('BXD_LOGOUT'),
			order: 1100,
			target: false,
			events: {
				open: BX.delegate(function(){
					this.logout(false, 'exit_tab');
				}, this)
			}
		});

		BX.addCustomEvent("onPullRevisionUp", function(newRevision, oldRevision) {
			BX.PULL.closeConfirm();
			console.log('NOTICE: Window reload, becouse PULL REVISION UP ('+oldRevision+' -> '+newRevision+')');
			location.reload();
		});
		BX.addCustomEvent("onPullError", BX.delegate(function(error, code) {
			if (error == 'AUTHORIZE_ERROR')
			{
				this.setIconStatus('offline');
				this.login(function(){
					console.log('DESKTOP LOGIN: success after PullError');
					BX.PULL.setPrivateVar('_pullTryConnect', true);
					BX.PULL.updateState('13', true);
				});
			}
			else if (error == 'RECONNECT')
			{
				this.setIconStatus('offline');
			}
		}, this));

		BX.addCustomEvent("onImError", BX.delegate(function(error, sendErrorCode) {
			if (error == 'AUTHORIZE_ERROR' || error == 'SEND_ERROR' && sendErrorCode == 'AUTHORIZE_ERROR')
			{
				this.setIconStatus('offline');
				this.login(BX.delegate(function(){
					this.setIconStatus('online');

					var textError = 'DESKTOP LOGIN: success after ImError';
					console.log(textError);

					if (typeof(BXIM) != 'undefined')
					{
						BX.desktop.log('phone.'+BXIM.userEmail+'.log', textError);
						BXIM.messenger.connectionStatus('online', false);
					}
				},this));
			}
			else if (error == 'CONNECT_ERROR')
			{
				this.setIconStatus('offline');
			}
		}, this));

		if (this.ready())
		{
			BX.userOptions.setAjaxPath(this.path.mainUserOptions);

			BX.addCustomEvent("onPullStatus", BX.delegate(function(status){
				if (status == 'offline')
					this.setIconStatus('offline');
				else
					this.setIconStatus(BXIM && BXIM.settings? BXIM.settings.status: 'online');
			}, this));

			BX.bind(window, "online", BX.delegate(function(){
				this.setIconStatus(BXIM && BXIM.settings? BXIM.settings.status: 'online');
			}, this));

			BX.bind(window, "offline", BX.delegate(function(){
				this.setIconStatus('offline');
			}, this));

			this.addCustomEvent("BXWakeAction", BX.delegate(function(){
				this.setIconStatus(BXIM && BXIM.settings? BXIM.settings.status: 'online');
			}, this));

			this.addCustomEvent("BXSleepAction", BX.delegate(function(){
				this.setIconStatus('offline');
			}, this));

			this.addCustomEvent("BXExitApplication", BX.delegate(function() {
				this.preventShutdown();
				this.logout(true, 'exit_event');
			}, this));
		}

		BX.bind(window, "resize", BX.delegate(function(){
			this.adjustSize();
		}, this));

		this.addCustomEvent("BXChangeTab", BX.delegate(function(tabId) {
			this.changeTab(tabId)
		}, this));


		/*
		if (BX.browser.IsMac())
		{
			this.addCustomEvent("BXForegroundChanged", BX.delegate(function(focus)
			{
				clearTimeout(this.focusTimeout);
				this.focusTimeout = setTimeout(BX.delegate(function(){

				}, this), focus? 500: 0);
			}, this));
		}
		else if (this.enableInVersion(20))
		{
			BX.bind(window, "blur", BX.delegate(function(){

			}, this));
			BX.bind(window, "focus", BX.delegate(function(){

			}, this));
		}
		*/

		this.addCustomEvent("BXTrayConstructMenu", BX.delegate(function() {
			this.onCustomEvent('main','BXTrayMenu', [])
			setTimeout(function(){
				BX.desktop.finalizeTrayMenu();
			});
		}, this));

		BX.onCustomEvent(window, 'onDesktopInit', [this]);
	}

	BX.desktop.prototype.notSupported = function ()
	{
		this.setWindowMinSize({ Width: 864, Height: 493 });
		this.setWindowSize({ Width: 864, Height: 493 });
		this.setWindowResizable(false);
		this.setWindowTitle(BX.message('BXD_DEFAULT_TITLE').replace('#VERSION#', this.getApiVersion(true)))

		var updateContent = BX.create("div", { props : { className : "bx-desktop-update-box" }, children : [
			BX.create("div", { props : { className : "bx-desktop-update-box-text" }, html: BX.message('BXD_NEED_UPDATE')}),
			BX.create("div", { props : { className : "bx-desktop-update-box-btn" }, events : { click :  BX.delegate(function(){this.checkUpdate(true)}, this)}, html: BX.message('BXD_NEED_UPDATE_BTN')})
		]});
		BX.ready(function(){
			document.body.innerHTML = '';
			document.body.appendChild(updateContent);
			BX.onCustomEvent(window, 'onDesktopOutdated', [this]);
		});
	}

	BX.desktop.prototype.getCurrentUrl = function ()
	{
		return document.location.protocol+'//'+document.location.hostname+(document.location.port == ''?'':':'+document.location.port)
	}

	BX.desktop.prototype.ready = function ()
	{
		return this.apiReady;
	}

	BX.desktop.prototype.login = function (callback)
	{
		var textError = 'DESKTOP LOGIN: try to login';
		console.log(textError);

		if (typeof(BXIM) != 'undefined')
		{
			BX.desktop.log('phone.'+BXIM.userEmail+'.log', textError);
		}
		if (!this.ready())
		{
			this.windowReload();
			return false;
		}

		var params = {};

		if (typeof(callback)=='function')
		{
			params.success = BX.delegate(function(sessid) {
				if (typeof(sessid) == "string")
				{
					BX.message({'bitrix_sessid': sessid});
				}
				callback(sessid);
				this.onCustomEvent('main','BXLoginSuccess', [sessid]);
			}, this);
		}
		else
		{
			params.success = BX.delegate(this.loginSuccessCallback, this);
		}

		BXDesktopSystem.Login(params);

		return true;
	}
	
	BX.desktop.prototype.loginSuccessCallback = function (sessid)
	{
		if (typeof(sessid) == "string")
		{
			BX.message({'bitrix_sessid': sessid});
		}

		if (!this.ready()) return false;

		this.windowReload()

		return true;
	}

	BX.desktop.prototype.showLoginForm = function ()
	{
		BXDesktopSystem.Logout(1, 'login_form');
	}

	BX.desktop.prototype.windowReload = function ()
	{
		location.reload();
	}

	BX.desktop.prototype.logout = function (terminate, reason, skipCheck)
	{
		if (!this.ready())
		{
			location.href = '/?logout=yes';
			return true;
		}

		if (false && this.enableInVersion(27) && !skipCheck && this.getContextWindow() === TAB_CP && this.getNetworkAuthorizeStatus()) // todo enable in future
		{
			this.timeoutDelayOfLogout = setTimeout(BX.delegate(function(){
				this.logout(terminate, reason, true);
			}, this), 2000)

			this.onCustomEventForTab(TAB_B24NET, "bxImLogoutInit", [terminate, reason]);

			this.addCustomEvent("bxImLogoutStart", BX.delegate(function() {
				clearTimeout(this.timeoutDelayOfLogout);
			}, this));

			this.addCustomEvent("bxImLogoutEnd", BX.delegate(function(terminate, reason) {
				this.logout(terminate, reason, true);
			}, this));

			return false;
		}

		terminate = terminate == true;

		this.apiReady = false;

		BX.ajax({
			url: this.path.pathToAjax+'?DESKTOP_LOGOUT',
			method: 'POST',
			dataType: 'json',
			timeout: 30,
			data: {'IM_DESKTOP_LOGOUT' : 'Y', 'sessid': BX.bitrix_sessid()},
			onsuccess: BX.delegate(function()
			{
				if (reason)
					console.log('Logout reason: '+reason);

				if (false && this.enableInVersion(27) && this.getContextWindow() === TAB_B24NET)  // todo enable in future
				{
					this.onCustomEventForTab(TAB_CP, "bxImLogoutEnd", [terminate, reason]);
				}

				if (terminate)
					BXDesktopSystem.Shutdown();
				else
					BXDesktopSystem.Logout(2);
			}, this),
			onfailure: BX.delegate(function()
			{
				if (reason)
					console.log('Logout reason (fail): '+reason);

				if (false && this.enableInVersion(27) && this.getContextWindow() === TAB_B24NET) // todo enable in future
				{
					this.onCustomEventForTab(TAB_CP, "bxImLogoutEnd", [terminate, reason]);
				}

				if (terminate)
					BXDesktopSystem.Shutdown();
				else
					BXDesktopSystem.Logout(3);
			}, this)
		});

		return true;
	}

	BX.desktop.prototype.checkUpdate = function (openBrowser)
	{
		if (typeof(BXDesktopSystem) == 'undefined')
			return false;

		openBrowser = typeof(openBrowser) != 'boolean'? false: openBrowser;
		if (!openBrowser && this.enableInVersion(16))
			BXDesktopSystem.ExecuteCommand("update.check", { NotifyNoUpdates: true, ShowNotifications: true});
		else
			this.browse(BX.browser.IsMac()? "http://dl.bitrix24.com/b24/bitrix24_desktop.dmg": "http://dl.bitrix24.com/b24/bitrix24_desktop.exe", "desktopApp");

		return true;
	}

	BX.desktop.prototype.getApiVersion = function (full)
	{
		if (typeof(BXDesktopSystem) == 'undefined')
			return 0;

		if (!this.clientVersion)
			this.clientVersion = BXDesktopSystem.GetProperty('versionParts');

		return full? this.clientVersion.join('.'): this.clientVersion[3];
	}

	BX.desktop.prototype.enableInVersion = function (version)
	{
		if (typeof(BXDesktopSystem) == 'undefined')
			return false;

		return this.getApiVersion() >= parseInt(version);
	}

	BX.desktop.prototype.addCustomEvent = function(eventName, eventHandler)
	{
		if (!this.ready()) return false;

		window.addEventListener(eventName, function (e)
		{
			var arEventParams = [];
			for(var i in e.detail)
				arEventParams.push(e.detail[i]);

			eventHandler.apply(window, arEventParams);
		});

		return true;
	}

	BX.desktop.prototype.onCustomEvent = function(windowTarget, eventName, arEventParams)
	{
		if (!this.ready()) return false;

		if (arguments.length == 2)
		{
			arEventParams = eventName
			eventName = windowTarget;
			windowTarget = 'all';
		}
		else if (arguments.length < 2)
		{
			return false;
		}

		var objEventParams = {};
		for (var i = 0; i < arEventParams.length; i++)
			objEventParams[i] = arEventParams[i];

		if (windowTarget == 'all')
		{
			var mainWindow = opener? opener: top;
			for (var i = 0; i < mainWindow.BXWindows.length; i++)
			{
				if (mainWindow.BXWindows[i] && mainWindow.BXWindows[i].name != '' && mainWindow.BXWindows[i].BXDesktopWindow && mainWindow.BXWindows[i].BXDesktopWindow.DispatchCustomEvent)
					mainWindow.BXWindows[i].BXDesktopWindow.DispatchCustomEvent(eventName, objEventParams);
			}
			mainWindow.BXDesktopWindow.DispatchCustomEvent(eventName, objEventParams);
		}
		else
		{
			if (windowTarget = this.findWindow(windowTarget))
				windowTarget.DispatchCustomEvent(eventName, objEventParams);
		}

		return true;
	}

	BX.desktop.prototype.onCustomEventForTab = function(tabIdTarget, eventName, arEventParams)
	{
		if (!this.enableInVersion(27)) // TODO need new function from Desktop 27
			return true;

		return true;
	}

	BX.desktop.prototype.findWindow = function (name)
	{
		if (!this.ready()) return null;

		if (typeof(name) == 'undefined')
			name = 'main';

		var mainWindow = opener? opener: top;
		if (name == 'main')
		{
			return mainWindow.BXDesktopWindow;
		}
		else
		{
			for (var i = 0; i < mainWindow.BXWindows.length; i++)
			{
				if (mainWindow.BXWindows[i].name === name)
					return mainWindow.BXWindows[i].BXDesktopWindow;
			}
		}
		return null;
	}

	BX.desktop.prototype.windowIsFocused = function ()
	{
		if (!this.ready()) return false;

		return BXDesktopWindow.GetProperty("isForeground");
	}

	BX.desktop.prototype.setIconStatus = function (status)
	{
		if (!this.ready()) return false;

		if (this.lastSetIcon == status)
			return false;

		this.lastSetIcon = status;
		BXDesktopSystem.SetIconStatus(status);

		return true;
	}

	BX.desktop.prototype.setIconBadge = function (count, important)
	{
		if (!this.ready()) return false;

		important = important === true;

		BXDesktopSystem.SetIconBadge(count+'', important);

		return true;
	}

	BX.desktop.prototype.setIconTooltip = function (iconTitle)
	{
		if (!this.ready()) return false;

		return BXDesktopSystem.ExecuteCommand('tooltip.change', iconTitle);
	}

	BX.desktop.prototype.setWindowResizable = function (enabled)
	{
		if (!this.ready()) return false;

		BXDesktopWindow.SetProperty("resizable", enabled !== false);

		return false;
	}

	BX.desktop.prototype.setWindowClosable = function (enabled)
	{
		if (!this.ready()) return false;

		BXDesktopWindow.SetProperty("closable", enabled !== false);

		return false;
	}



	BX.desktop.prototype.flashIcon = function (voiced)
	{
		if (!this.ready()) return false;

		BXDesktopSystem.FlashIcon(voiced == true);

		return true;
	}

	BX.desktop.prototype.checkInternetConnection = function (successCallback, failureCallback, tryCount, tryName)
	{
		if (typeof(successCallback) != 'function')
		{
			successCallback = function ()
			{
				if (typeof(BXIM) != 'undefined')
				{
					BXIM.messenger.connectionStatus('online', false);
				}
			};
		}

		if (typeof(failureCallback) != 'function')
			failureCallback = function() {};

		if (typeof(tryCount) != "number")
			tryCount = 1;

		if (!tryName && tryCount > 1)
			tryName = +new Date();

		if (typeof(BXIM) != 'undefined')
		{
			BXIM.messenger.connectionStatus('connecting');
		}

		BX.ajax({
			url: '//www.bitrixsoft.com/200.ok.'+(+new Date),
			method: 'GET',
			dataType: 'html',
			skipAuthCheck: true,
			skipBxHeader: true,
			timeout: 1,
			onsuccess: function(data){
				if (data == 'OK')
				{
					console.log('Checking internet connection... success!');
					delete BX.desktop.tryCheckConnect[tryName];
					successCallback();
				}
				else
				{
					if (typeof(BXIM) != 'undefined')
					{
						BXIM.messenger.connectionStatus('offline');
					}
					console.log('Checking internet connection... failure!');
					if (tryCount == 1)
					{
						delete BX.desktop.tryCheckConnect[tryName];
						failureCallback();
					}
					else
					{
						if (typeof(BXIM) != 'undefined')
						{
							BXIM.messenger.connectionStatus('connecting');
						}
						clearTimeout(BX.desktop.tryCheckConnect[tryName]);
						BX.desktop.tryCheckConnect[tryName] = setTimeout(function(){
							BX.desktop.checkInternetConnection(successCallback, failureCallback, tryCount-1, tryName)
						}, 5000);
					}
				}
			},
			onfailure: function(){
				console.log('Checking internet connection... failure!');
				if (tryCount == 1)
				{
					delete BX.desktop.tryCheckConnect[tryName];
					failureCallback();
				}
				else
				{
					clearTimeout(BX.desktop.tryCheckConnect[tryName]);
					BX.desktop.tryCheckConnect[tryName] = setTimeout(function(){
						BX.desktop.checkInternetConnection(successCallback, failureCallback, tryCount-1, tryName)
					}, 5000);
				}
			}
		});

		return true;
	}

	BX.desktop.prototype.getWorkArea = function ()
	{
		if (!this.ready())
			return false;

		var coordinates = BXDesktopSystem.GetWorkArea();

		return {top: coordinates[0], left: coordinates[1], right: coordinates[2], bottom: coordinates[3]}
	}

	BX.desktop.prototype.showNotification = function (notifyId, content, js)
	{
		if (!this.ready() || content == "")
			return false;

		if (this.showNotifyId[notifyId])
			return false;

		this.showNotifyId[notifyId] = true;

		BXDesktopSystem.ExecuteCommand('notification.show.html', this.getHtmlPage(content, js, 'desktop-notify-popup'));

		return true;
	}

	BX.desktop.prototype.adjustSize = function (width, height)
	{
		var innerWidth = 0;
		var innerHeight = 0;

		if (this.contentFullWindow)
		{
			innerWidth = window.innerWidth;
			innerHeight = window.innerHeight;
		}
		else
		{
			try {
				BX.style(document.body, 'height', window.innerHeight+'px');
			}
			catch (e)
			{
				setTimeout(function(){
					BX.desktop.adjustSize(width, height);
				}, 500);
			}
			innerWidth = Math.max(this.content.offsetWidth, this.minWidth);
			innerHeight = Math.max(this.content.offsetHeight, this.minHeight);
		}

		if ((!width || !height) && (innerHeight < this.minHeight || innerWidth < this.minWidth))
		{
			if (this.ready())
				BXDesktopWindow.SetProperty("clientSize", { Width: this.width, Height: this.height});
			return false;
		}

		this.width = width? width: innerWidth;
		this.height = height? height: innerHeight;

		BX.style(this.contentMenu, 'height', this.height+'px');
		BX.style(this.contentTabContent, 'height', this.height+'px');

		return true;
	}

	BX.desktop.prototype.resize = function ()
	{
		if (!this.ready()) return false;

		BXDesktopWindow.SetProperty("clientSize", { Width: document.body.offsetWidth, Height: document.body.offsetHeight});

		return true;
	}

	BX.desktop.prototype.windowCommand = function (windowTarget, command)
	{
		if (!this.ready()) return false;

		if (arguments.length == 1)
		{
			command = windowTarget
			windowTarget = window;
		}

		if (command == "show" && windowTarget == window)
		{
			BX.desktop.setActiveWindow();
		}

		try
		{
			if (command == "show" || command == "hide" || command == "freeze" || command == "unfreeze")
			{
				windowTarget.BXDesktopWindow.ExecuteCommand(command);
			}
			else if (command == "close")
			{
				windowTarget.BXDesktopWindow.ExecuteCommand("close");
			}
		}
		catch(e)
		{
			console.log('ExecuteCommand Error', command, windowTarget, e);
			console.trace();
		}

		return true;
	}

	BX.desktop.prototype.openTopmostWindow = function(html, js, bodyClass)
	{
		if (!this.ready())
			return false;

		this.closeTopmostWindow();
		this.topmostWindow = BXDesktopSystem.ExecuteCommand('topmost.show.html', this.getHtmlPage(html, js, bodyClass));

		return true;
	};

	BX.desktop.prototype.closeTopmostWindow = function()
	{
		if (this.topmostWindow)
		{
			this.windowCommand(this.topmostWindow, "close");
			this.topmostWindow = null;
		}
		return true;
	}

	BX.desktop.prototype.getHtmlPage = function(content, jsContent, bodyClass)
	{
		if (!this.ready()) return;

		content = content || '';
		jsContent = jsContent || '';
		bodyClass = bodyClass || '';

		if (this.htmlWrapperHead == null)
			this.htmlWrapperHead = document.head.outerHTML.replace(/BX\.PULL\.start\([^)]*\);/g, '');

		if (content != '' && BX.type.isDomNode(content))
			content = content.outerHTML;

		if (jsContent != '' && BX.type.isDomNode(jsContent))
			jsContent = jsContent.outerHTML;

		if (jsContent != '')
			jsContent = '<script type="text/javascript">BX.ready(function(){'+jsContent+'});</script>';

		return '<!DOCTYPE html><html>'+this.htmlWrapperHead+'<body class="im-desktop im-desktop-popup '+bodyClass+'">'+content+jsContent+'</body></html>';
	};

	BX.desktop.prototype.openDeveloperTools = function()
	{
		if (typeof(BXDesktopWindow) == 'undefined')
			return false;

		BXDesktopWindow.OpenDeveloperTools();

		return true;
	};

	BX.desktop.prototype.openLogsFolder = function()
	{
		if (!this.ready()) return false;

		BXDesktopSystem.OpenLogsFolder();

		return true;
	};

	BX.desktop.prototype.browse = function (url)
	{
		if (typeof(BXDesktopSystem) == 'undefined')
			return false;

		BXDesktopSystem.ExecuteCommand('browse', url);

		return true;
	}

	BX.desktop.prototype.autorunStatus = function(value)
	{
		if (!this.ready()) return false;

		if (typeof(value) !='boolean')
		{
			if (this.autorun == null)
				this.autorun = BXDesktopSystem.GetProperty("autostart");
		}
		else
		{
			this.autorun = value;
			BXDesktopSystem.SetProperty("autostart", this.autorun);
		}
		return this.autorun;
	};

	BX.desktop.prototype.diskAttachStatus = function()
	{
		if (!this.ready()) return false;

		return BitrixDisk? BitrixDisk.enabled: false;
	};

	BX.desktop.prototype.clipboardSelected = function (element)
	{
		var resultText = "";
		if (typeof(element) == 'object' && (element.tagName == 'TEXTAREA' || element.tagName == 'INPUT'))
		{
			var selectionStart = element.selectionStart;
			var selectionEnd = element.selectionEnd;
			resultText = element.value.substring(selectionStart, selectionEnd);
		}
		else
		{
			if (window.getSelection().toString().length > 0)
			{
				var range = window.getSelection().getRangeAt(0).cloneContents();
				var div = document.createElement("div");
				div.appendChild(range);
				resultText = div.innerHTML;
			}
		}
		if (resultText.length > 0)
		{
			resultText = BX.util.htmlspecialcharsback(resultText);
			resultText = resultText.split('&nbsp;&nbsp;&nbsp;&nbsp;').join("\t");
			resultText = resultText.replace(/<img.*?data-code="([^"]*)".*?>/ig, '$1');
			resultText = resultText.replace(/&nbsp;/ig, ' ').replace(/&copy;/, '(c)');
			resultText = resultText.replace(/<div class=\"bx-messenger-hr\"><\/div>/ig, '\n');
			resultText = resultText.replace(/<span class=\"bx-messenger-clear\"><\/span>/ig, '\n');
			resultText = resultText.replace(/<s>([^"]*)<\/s>/ig, '');
			resultText = resultText.replace(/<(\/*)([buis]+)>/ig, '[$1$2]');
			resultText = resultText.replace(/<a.*?href="([^"]*)".*?>.*?<\/a>/ig, '$1');
			resultText = resultText.replace(/------------------------------------------------------(.*?)------------------------------------------------------/gmi, "["+BX.message("BXD_QUOTE_BLOCK")+"]");
			resultText = resultText.replace('<br />', '\n').replace(/<\/?[^>]+>/gi, '');
		}
		return resultText;
	}

	BX.desktop.prototype.clipboardCopy = function(callback, cut)
	{
		if (!this.ready()) return false;

		document.execCommand(cut == true? "cut": "copy");

		var clipboardTextArea = BX.create('textarea', { style : {'position': 'absolute', 'opacity': 0, 'top': -1000, 'left': -1000}});
		document.body.insertBefore(clipboardTextArea, document.body.firstChild);
		clipboardTextArea.focus();
		document.execCommand("paste");
		var text = clipboardTextArea.value;

		if (typeof (callback) == 'function')
		{
			var textNew = callback(clipboardTextArea.value);
			if (typeof (textNew) != 'undefined')
				text = clipboardTextArea.value = textNew;

			clipboardTextArea.selectionStart = 0;
			document.execCommand("copy");
		}
		BX.remove(clipboardTextArea);

		return text;
	}

	BX.desktop.prototype.clipboardCut = function ()
	{
		return this.clipboardCopy(null, true);
	}

	BX.desktop.prototype.clipboardPaste = function ()
	{
		if (!this.ready()) return false;

		document.execCommand("paste");

		return true;
	}

	BX.desktop.prototype.clipboardDelete = function ()
	{
		if (!this.ready()) return false;

		document.execCommand("delete");

		return true;
	}

	BX.desktop.prototype.clipboardUndo = function ()
	{
		if (!this.ready()) return false;

		document.execCommand("undo");

		return true;
	}

	BX.desktop.prototype.clipboardRedo = function ()
	{
		if (!this.ready()) return false;

		document.execCommand("redo");

		return true;
	}

	BX.desktop.prototype.selectAll = function (element)
	{
		if (!this.ready()) return false;

		element.selectionStart = 0;

		return true;
	}

	BX.desktop.prototype.getLocalConfig = function(name, def)
	{
		def = typeof(def) == 'undefined'? null: def;

		if (!this.ready()) return def;

		var result = BXDesktopSystem.QuerySettings(name, def+'');

		if (typeof(result) == 'string' && result.length > 0)
		{
			try {
				result = JSON.parse(result);
			}
			catch(e) { result = def; }
		}

		return result;
	};

	BX.desktop.prototype.setLocalConfig = function(name, value)
	{
		if (!this.ready()) return false;

		if (typeof(value) == 'object')
			value = JSON.stringify(value);
		else if (typeof(value) == 'boolean')
			value = value? 'true': 'false';
		else if (typeof(value) == 'undefined')
			value = '';
		else if (typeof(value) != 'string')
			value = value+'';

		BXDesktopSystem.StoreSettings(name, value);

		return true;
	};

	BX.desktop.prototype.removeLocalConfig = function(name)
	{
		if (!this.ready()) return false;

		BXDesktopSystem.StoreSettings(name, null);

		return true;
	};

	BX.desktop.prototype.openConfirm = function(text, buttons, modal)
	{
		if (this.popupConfirm != null)
			this.popupConfirm.destroy();

		if (typeof(text) == "object")
			text = '<div class="bx-desktop-confirm-title">'+text.title+'</div>'+text.message;

		modal = modal !== false;
		if (typeof(buttons) == "undefined" || typeof(buttons) == "object" && buttons.length <= 0)
		{
			buttons = [new BX.PopupWindowButton({
				text : BX.message('BXD_CONFIRM_CLOSE'),
				className : "popup-window-button-decline",
				events : { click : function(e) { this.popupWindow.close(); BX.PreventDefault(e) } }
			})];
		}
		this.popupConfirm = new BX.PopupWindow('bx-desktop-confirm', null, {
			zIndex: 200,
			autoHide: buttons === false,
			buttons : buttons,
			closeByEsc: buttons === false,
			overlay : modal,
			events : { onPopupClose : function() { this.destroy() }, onPopupDestroy : BX.delegate(function() { this.popupConfirm = null }, this)},
			content : BX.create("div", { props : { className : (buttons === false? " bx-desktop-confirm-without-buttons": "bx-desktop-confirm") }, html: text})
		});
		this.popupConfirm.show();
		BX.bind(this.popupConfirm.popupContainer, "click", BX.PreventDefault);
		BX.bind(this.popupConfirm.contentContainer, "click", BX.PreventDefault);
		BX.bind(this.popupConfirm.overlay.element, "click", BX.PreventDefault);

		return true;
	};

	BX.desktop.prototype.log = function (filename, text)
	{
		if (!this.ready()) return false;

		BXDesktopSystem.Log(filename, text);

		return true;
	}

	BX.desktop.prototype.createWindow = function (name, callback)
	{
		BXDesktopSystem.GetWindow(name, callback)
	}

	BX.desktop.prototype.getWindowTitle = function (title)
	{
		if (!this.ready()) return false;

		BXDesktopWindow.GetProperty("title");

		return true;
	}

	BX.desktop.prototype.setWindowTitle = function (title)
	{
		if (!this.ready()) return false;

		if (typeof(title) == 'undefined')
			return false;

		title = BX.util.trim(title);
		if (title.length <= 0)
			return false;

		BXDesktopWindow.SetProperty("title", title);

		return true;
	}

	BX.desktop.prototype.setWindowPosition = function (params)
	{
		if (!this.ready()) return false;

		BXDesktopWindow.SetProperty("position", params);

		return true;
	}

	BX.desktop.prototype.setWindowSize = function (params)
	{
		if (!this.ready()) return false;

		BXDesktopWindow.SetProperty("clientSize", params);
		if (params.Width && params.Height)
			this.adjustSize(params.Width, params.Height);

		return true;
	}

	BX.desktop.prototype.setWindowMinSize = function (params)
	{
		if (!this.ready())
			return false;

		if (!params.Width || !params.Height)
			return false;

		this.minWidth = params.Width;
		this.minHeight = params.Height;

		BXDesktopWindow.SetProperty("minClientSize", params);

		return true;
	}

	BX.desktop.prototype.addTrayMenuItem = function (params)
	{
		if (!this.ready()) return false;

		BXDesktopWindow.AddTrayMenuItem(params)

		return true;
	}

	BX.desktop.prototype.finalizeTrayMenu = function ()
	{
		if (!this.ready()) return false;

		BXDesktopWindow.EndTrayMenuItem();

		return true;
	}

	BX.desktop.prototype.preventShutdown = function ()
	{
		if (!this.ready()) return false;

		BXDesktopSystem.PreventShutdown();

		return true;
	}

	BX.desktop.prototype.diskReportStorageNotification = function (command, params)
	{
		if (!this.ready()) return false;

		BXDesktopSystem.ReportStorageNotification(command, params);

		return true;
	}

	BX.desktop.prototype.diskOpenFolder = function ()
	{
		if (!this.ready()) return false;

		BXFileStorage.OpenFolder();

		return true;
	}

	/* Interface */
	BX.desktop.prototype.addSeparator = function (params)
	{
		params.type = 'separator';
		params.id = 'sep'+(+new Date())
		this.tabItems[params.id] = params;

		this.drawTabs();
	}

	BX.desktop.prototype.addTab = function (params)
	{
		if (!params || !params.id || !params.title)
			return false;

		if (!params.order)
			params.order = 500;

		params.hide = params.hide? true: false;

		if (parseInt(params.badge) > 0)
		{
			params.badge = parseInt(params.badge);
		}
		else
		{
			params.badge = 0;
		}

		if (!params.initContent || !BX.type.isDomNode(params.initContent))
			params.initContent = null;

		if (!params.events)
			params.events = {};

		if (typeof(params.target) == 'undefined')
			params.target = params.id;

		if (!params.events.open)
			params.events.open = function() {}

		if (!params.events.close)
			params.events.close = function() {}

		if (!params.events.init)
			params.events.init = function() {}

		params.type = 'item';

		this.tabItems[params.id] = params;

		this.drawTabs();
	}

	BX.desktop.prototype.drawTabs = function (force)
	{
		if (!force)
		{
			clearTimeout(this.tabRedrawTimeout);
			this.tabRedrawTimeout = setTimeout(BX.delegate(function(){
				this.drawTabs(true);
			}, this), 100);

			return true;
		}
		if (!this.contentTabContent)
		{
			if (!this.drawAppearance())
				return false;
		}

		this.contentTab.innerHTML = '';
		var arTabs = BX.util.objectSort(this.tabItems, 'order', 'asc');
		for (var i = 0; i < arTabs.length; i++)
		{
			this.drawTab(arTabs[i]);
		}
		BX.onCustomEvent(this, 'OnDesktopTabsInit');
		if (this.currentTab == '')
		{
			if (arTabs[0].id == 'exit')
			{
				if (typeof(arTabs[1]) != 'undefined')
				{
					this.changeTab(arTabs[1].id);
				}
			}
			else
			{
				this.changeTab(arTabs[0].id);
			}
		}

		this.updateTabBadge();

		return true;
	}

	BX.desktop.prototype.drawTab = function (params)
	{
		if (params.type == 'separator')
		{
			this.contentTab.appendChild(
				BX.create('div', { attrs : { 'data-id' : params.id, id: 'bx-desktop-sep-'+params.id}, props : { className : "bx-desktop-separator"}})
			);
		}
		else
		{
			this.contentTab.appendChild(
				BX.create('div', { attrs : { 'data-id' : params.id, id: 'bx-desktop-tab-'+params.id, title: params.title}, props : { className : "bx-desktop-tab bx-desktop-tab-"+params.id+(this.currentTab == params.id? ' bx-desktop-tab-active': '')+(params.hide? ' bx-desktop-tab-hide': '') }, children: [
					BX.create('span', { props : { className : "bx-desktop-tab-counter" }, html: params.badge > 0? '<span class="bx-desktop-tab-counter-digit">'+(params.badge > 50? '50+': params.badge)+'</span>': ''}),
					BX.create('div', { props : { className : "bx-desktop-tab-icon bx-desktop-tab-icon-"+params.id }})
				]})
			);

			if (!BX('bx-desktop-tab-content-'+params.id) && params.id == params.target)
			{
				this.contentTabContent.appendChild(
					BX.create('div', { attrs : { 'data-id': params.id, id: 'bx-desktop-tab-content-'+params.id}, props : { className : "bx-desktop-tab-content bx-desktop-tab-content-"+params.id+(this.currentTab == params.id? ' bx-desktop-tab-content-active': '') }, children: params.initContent? [params.initContent]: []})
				);
				params.events.init();
			}
		}
		return true;
	}

	BX.desktop.prototype.drawAppearance = function ()
	{
		if (!this.content)
			return false;

		this.content.innerHTML = '';
		this.content.appendChild(
			BX.create("div", { props : { className : 'bx-desktop-appearance'}, style: {minHeight: this.minHeight+'px'}, children: [
				this.contentMenu = BX.create("div", { props : { className : 'bx-desktop-appearance-menu'}, children: [
					this.contentAvatar = BX.create("div", { props : { className : 'bx-desktop-appearance-avatar'}}),
					this.contentTab = BX.create("div", { props : { className : 'bx-desktop-appearance-tab'}})
				]}),
				this.contentTabContent = BX.create("div", { props : { className : 'bx-desktop-appearance-content'}})
			]})
		);

		BX.bindDelegate(this.contentTab, "click", {className: 'bx-desktop-tab'}, BX.delegate(function(event){
			this.changeTab(event, false);
			BX.PreventDefault(event);
		}, this));
		this.adjustSize();

		return true;
	}

	BX.desktop.prototype.changeTab = function (tabId, force)
	{
		force = typeof(force) == 'undefined'? true: force;

		if (typeof(tabId) == 'object')
		{
			if (!BX.proxy_context)
			{
				return false;
			}
			tabId = BX.proxy_context.getAttribute('data-id');
		}
		if (!force && this.currentTab == tabId)
		{
			tabId = this.lastTab;
		}
		if (!this.tabItems[tabId])
			return false;
		if (this.tabItems[tabId].target)
		{
			var fireEvent = false;
			if (!force || this.currentTab != tabId)
			{
				this.lastTab = this.currentTab;
				this.lastTabTarget = this.currentTabTarget;
				this.currentTab = this.tabItems[tabId].id;
				this.currentTabTarget = this.tabItems[tabId].target;

				fireEvent = true;
			}

			if (BX('bx-desktop-tab-'+this.lastTab))
				BX.removeClass(BX('bx-desktop-tab-'+this.lastTab), 'bx-desktop-tab-active');

			if (BX('bx-desktop-tab-'+tabId))
				BX.addClass(BX('bx-desktop-tab-'+tabId), 'bx-desktop-tab-active');

			if (BX('bx-desktop-tab-content-'+this.lastTab))
			{
				BX.removeClass(BX('bx-desktop-tab-content-'+this.lastTab), 'bx-desktop-tab-content-active');
			}
			else if (BX('bx-desktop-tab-content-'+this.lastTabTarget))
			{
				BX.removeClass(BX('bx-desktop-tab-content-'+this.lastTabTarget), 'bx-desktop-tab-content-active');
			}

			if (BX('bx-desktop-tab-content-'+this.currentTab))
			{
				BX.addClass(BX('bx-desktop-tab-content-'+this.currentTab), 'bx-desktop-tab-content-active');
			}
			else if (BX('bx-desktop-tab-content-'+this.currentTabTarget))
			{
				BX.addClass(BX('bx-desktop-tab-content-'+this.currentTabTarget), 'bx-desktop-tab-content-active');
			}

			if (fireEvent)
			{
				if (this.tabItems[this.lastTab])
				{
					this.tabItems[this.lastTab].events.close();
				}

				if (this.tabItems[this.currentTab])
				{
					BX.onCustomEvent(this, 'OnDesktopTabChange', [this.currentTab, this.lastTab]);
					this.tabItems[this.currentTab].events.open();
				}

			}
		}
		else
		{
			this.tabItems[tabId].events.open();
		}

		return true;
	}

	BX.desktop.prototype.closeTab = function (tabId)
	{
		tabId = tabId || this.getCurrentTab();

		if (!this.tabItems[tabId] || this.getCurrentTab() != tabId)
			return false;

		if (this.tabItems[tabId].target != this.currentTabTarget)
		{
			this.changeTab(tabId, false);
		}
		else
		{
			if (BX('bx-desktop-tab-'+this.currentTab))
				BX.removeClass(BX('bx-desktop-tab-'+this.currentTab), 'bx-desktop-tab-active');

			if (BX('bx-desktop-tab-'+this.lastTab))
				BX.addClass(BX('bx-desktop-tab-'+this.lastTab), 'bx-desktop-tab-active');

			var lastTab = this.lastTab;
			this.lastTab = this.currentTab;
			this.currentTab = lastTab;
		}
	}

	BX.desktop.prototype.setTabBadge = function (tabId, value)
	{
		if (!this.tabItems[tabId])
			return false;

		value = parseInt(value);
		this.tabItems[tabId].badge = value>0? value: 0;

		if (value > 50)
			value = '50+';

		if (BX('bx-desktop-tab-'+tabId))
		{
			var counter = BX.findChild(BX('bx-desktop-tab-'+tabId), {className : "bx-desktop-tab-counter"}, true);
			if (counter)
				counter.innerHTML = value? '<span class="bx-desktop-tab-counter-digit">'+value+'</span>': '';
		}

		this.updateTabBadge();
	}

	BX.desktop.prototype.updateTabBadge = function ()
	{
		if (!this.ready())
			return false;

		var value = 0;
		for (var tabId in this.tabItems)
		{
			if (this.tabItems[tabId].badge)
				value += this.tabItems[tabId].badge;
		}

		if (value <= 0)
			value = '';
		else if (value > 50)
			value = '50+';

		BXDesktopSystem.SetTabBadge(this.getContextWindow(), value+'');
	}

	BX.desktop.prototype.setTabContent = function (tabId, content)
	{
		if (!this.tabItems[tabId])
			return false;

		if (BX('bx-desktop-tab-content-'+tabId))
		{
			if (BX.type.isDomNode(content))
			{
				BX('bx-desktop-tab-content-'+tabId).innerHTML = '';
				BX('bx-desktop-tab-content-'+tabId).appendChild(content);
			}
			else
			{
				BX('bx-desktop-tab-content-'+tabId).innerHTML = content;
			}
		}
		else
		{
			this.tabItems[tabId].initContent = content;
		}

		return true;
	}

	BX.desktop.prototype.getCurrentTab = function ()
	{
		return this.currentTab;
	}

	BX.desktop.prototype.getCurrentTabTarget = function ()
	{
		return this.currentTabTarget;
	}

	BX.desktop.prototype.isActiveWindow = function ()
	{
		if (!this.ready())
			return false;

		return BXDesktopSystem.IsActiveTab();
	}
	BX.desktop.prototype.getActiveWindow = function ()
	{
		if (!this.ready())
			return 1;

		return BXDesktopSystem.ActiveTab();
	}
	BX.desktop.prototype.getNetworkAuthorizeStatus = function ()
	{
		if (!this.enableInVersion(27))
			return true;

		return true; // TODO need new function from Desktop 27
	}
	BX.desktop.prototype.getContextWindow = function ()
	{
		if (!this.ready())
			return 1;

		if(this.isActiveWindow())
		{
			return this.getActiveWindow();
		}
		else
		{
			if(this.getActiveWindow() == TAB_CP)
			{
				return TAB_B24NET;
			}
			else
			{
				return TAB_CP;
			}
		}
	}
	BX.desktop.prototype.setActiveWindow = function (windowId)
	{
		if (!this.ready())
			return false;

		if (typeof(windowId) != 'undefined')
		{
			if (windowId == TAB_B24NET || windowId == TAB_CP)
			{
				BXDesktopSystem.SetActiveTabI(windowId);
			}
		}
		else
		{
			BXDesktopSystem.SetActiveTab();
		}
	}

	BX.desktop.prototype.setUserInfo = function (params)
	{
		if (!params || !params.id || !params.name)
			return false;

		if (!params.gender)
			params.gender = 'M';

		if (!params.avatar || !params.profile)
			params.avatar = '';

		this.userInfo = params;

		if (!this.contentAvatar)
		{
			if (!this.drawAppearance())
				return false;
		}

		var events = {};

		if (this.userInfo.onclick)
		{
			events.click = function(e){
				BX.desktop.userInfo.onclick();
				return BX.PreventDefault(e);
			}
		}

		this.contentAvatar.innerHTML = '';
		this.contentAvatar.appendChild(
			BX.create('a', { attrs : { href : this.userInfo.profile, title : BX.util.htmlspecialcharsback(this.userInfo.name), target: "_blank" }, props : { className : "bx-desktop-avatar" }, events: events, children: [
				BX.create('img', { attrs : { src : this.userInfo.avatar}, props : { className : "bx-desktop-avatar-img bx-desktop-avatar-img-default" }})
			]})
		);

		return true;
	}

	BX.desktop.prototype.updateUserInfo = function (params)
	{
		for (var i in params)
		{
			this.userInfo[i] = params[i];
		}
		return this.setUserInfo(this.userInfo);
	}

	BX.desktop.prototype.getUserInfo = function()
	{
		return this.userInfo;
	}

	BX.desktop = new BX.desktop();
})(window);
