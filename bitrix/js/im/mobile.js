(function (){

if (BX.ImMobile)
	return;

BX.ImMobile = function(params)
{
	BX.browser.addGlobalClass();
	if(typeof(BX.message("USER_TZ_AUTO")) == 'undefined' || BX.message("USER_TZ_AUTO") == 'Y')
		BX.message({"USER_TZ_OFFSET": -(new Date).getTimezoneOffset()*60-parseInt(BX.message("SERVER_TZ_OFFSET"))});

	if (typeof(BX.MessengerCommon) != 'undefined')
		BX.MessengerCommon.setBxIm(this);

	this.mobileVersion = true;
	this.mobileAction = params.mobileAction? params.mobileAction: 'none';

	this.revision = 2; // mobile api revision - check include.php
	this.errorMessage = '';
	this.bitrixNetworkStatus = params.bitrixNetworkStatus || false;
	this.bitrix24Status = params.bitrix24Status || false;
	this.bitrix24Admin = params.bitrix24Admin || false;
	this.bitrixIntranet = params.bitrixIntranet || false;
	this.bitrix24net = params.bitrix24net || false;
	this.bitrixXmpp = params.bitrixXmpp || false;
	this.ppStatus = params.ppStatus || false;
	this.ppServerStatus = this.ppStatus? params.ppServerStatus: false;
	this.updateStateInterval = params.updateStateInterval || 90;
	this.desktopStatus = params.desktopStatus || false;
	this.desktopVersion = params.desktopVersion || 0;
	this.xmppStatus = false;
	this.lastRecordId = 0;
	this.userId = params.userId;
	this.userEmail = params.userEmail || '';
	this.path = params.path || {};
	this.language = params.language || 'en';
	this.init = typeof(params.init) != 'undefined'? params.init: true;
	this.tryConnect = true;
	this.animationSupport = true;

	this.keyboardShow = false;

	this.sendAjaxTry = 0;
	this.pathToRoot = BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') : '/';
	this.pathToAjax = this.pathToRoot+'mobile/ajax.php?mobile_action=im&';
	this.pathToFileAjax = this.pathToAjax+'upload&';
	this.pathToBlankImage = '/bitrix/js/im/images/blank.gif';

	this.notifyCount = params.notifyCount || 0;
	this.messageCount = params.messageCount || 0;
	this.messageCountArray = {};

	this.settings = params.settings || {};
	this.settingsNotifyBlocked = params.settingsNotifyBlocked || {};

	this.timeoutUpdateCounters = null;
	this.timeoutUpdateStateLight = null;

	params.notify = params.notify || {};
	params.notify = params.message || {};
	params.notify = params.recent || {};

	for (var i in params.notify)
	{
		params.notify[i].date = parseInt(params.notify[i].date)+parseInt(BX.message('USER_TZ_OFFSET'));
		if (parseInt(i) > this.lastRecordId)
			this.lastRecordId = parseInt(i);
	}
	for (var i in params.message)
	{
		params.message[i].date = parseInt(params.message[i].date)+parseInt(BX.message('USER_TZ_OFFSET'));
		if (parseInt(i) > this.lastRecordId)
			this.lastRecordId = parseInt(i);
	}
	for (var i in params.recent)
	{
		params.recent[i].date = parseInt(params.recent[i].date)+parseInt(BX.message('USER_TZ_OFFSET'));
	}

	this.notify = new BX.ImNotifyMobile(this, {
		'counters': params.counters || {},
		'notify': params.notify || {},
		'unreadNotify' : params.unreadNotify || {},
		'flashNotify' : params.flashNotify || {},
		'countNotify' : params.countNotify || 0,
		'loadNotify' : params.loadNotify || false
	});

	this.disk = new BX.ImDiskManagerMobile(this, {
		notifyClass: this.notify,
		files: params.files || {},
		enable: params.disk && params.disk.enable
	});
	this.notify.disk = this.disk;

	this.messenger = new BX.ImMessengerMobile(this, {
		'updateStateInterval': params.updateStateInterval,
		'notifyClass': this.notify,
		'diskClass': this.disk,
		'recent': params.recent || {},
		'users': params.users || {},
		'groups': params.groups || {},
		'userChatBlockStatus': params.userChatBlockStatus || {},
		'userInGroup': params.userInGroup || {},
		'woGroups': params.woGroups || {},
		'woUserInGroup': params.woUserInGroup || {},
		'currentTab' : params.currentTab || 0,
		'chat' : params.chat || {},
		'userInChat' : params.userInChat || {},
		'userChat' : params.userChat || {},
		'hrphoto' : params.hrphoto || {},
		'message' : params.message || {},
		'showMessage' : params.showMessage || {},
		'unreadMessage' : params.unreadMessage || {},
		'flashMessage' : params.flashMessage || {},
		'countMessage' : params.countMessage || 0,
		'smile' : params.smile || false,
		'smileSet' : params.smileSet || false,
		'history' : params.history || {}
	});
	this.notify.messenger = this.messenger;
	this.disk.messenger = this.messenger;

	this.webrtc = params.webrtc || {}
	this.messenger.webrtc = this.webrtc;

	if (this.init)
	{
		BX.onCustomEvent(window, 'onImMobileInit', [this]);
		app.pullDownLoadingStop();
		this.mobileActionPrepare(params);
	}

	BX.addCustomEvent('onFrameDataProcessed', BX.delegate(function(element, fromCache)
	{
		for (var i = 0; i < element.length; i++)
		{
			if (element[i]['ID'].indexOf('im_component_') >= 0)
			{
				if (!fromCache)
				{
					this.mobileActionReady();
				}
				else
				{
					this.mobileActionFromCache();
				}
			}
		}
	},this));
}

BX.ImMobile.prototype.mobileActionPrepare = function(params)
{
	if (this.mobileAction == 'RECENT')
	{
		this.messenger.drawRecentList();
	}
	else if (this.mobileAction == 'INIT')
	{
		this.initPageAction();
	}
	else if (this.mobileAction == 'DIALOG')
	{
		this.dialogPageAction(params);
	}
}

BX.ImMobile.prototype.mobileActionFromCache = function()
{
	BX.addClass(document.body, 'im-page-from-cache');

	if (this.mobileAction == 'DIALOG')
	{
		this.messenger.currentTab = 0;
		this.messenger.openChatFlag = false;
		this.messenger.openCallFlag = false;
		this.messenger.showMessage = {}
		this.messenger.unreadMessage = {};
	}
}

BX.ImMobile.prototype.mobileActionReady = function()
{
	BX.removeClass(document.body, 'im-page-from-cache');

	BX.MessengerCommon.pullEvent();

	if (this.mobileAction == 'RECENT')
	{
		BX.addCustomEvent("onImDialogOpen", BX.delegate(function (params)
		{
			this.messenger.openMessenger(params.id, false, false);
		}, this));
		BX.addCustomEvent("onImDialogClose", BX.delegate(function (params)
		{
			this.messenger.closeMessenger(params.id);
		}, this));
	}
	else if (this.mobileAction == 'DIALOG')
	{
		BX.addCustomEvent("onOpenPageAfter", BX.delegate(function(){
			BX.MessengerCommon.readMessage(this.messenger.currentTab);
			app.onCustomEvent('onImDialogOpen', {id: this.messenger.currentTab});
		}, this));

		BX.addCustomEvent("onHidePageBefore", BX.delegate(function(){
			app.onCustomEvent('onImDialogClose', {id: this.messenger.currentTab});
		}, this));

		BXMobileApp.UI.Page.TextPanel.setUseImageButton(true);
		BXMobileApp.UI.Page.TextPanel.setParams({
			callback: BX.delegate(function (data)
			{
				if (data.event && data.event == "onKeyPress")
				{
					if (BX.util.trim(data.text).length > 2)
					{
						BX.MessengerCommon.sendWriting(this.messenger.currentTab);
					}
					this.messenger.textareaHistory[this.messenger.currentTab] = data.text;
				}
			}, this),
			placeholder: BX.message('IM_M_TEXTAREA'),
			button_name: BX.message('IM_M_MESSAGE_SEND'),
			plusAction: !this.disk.enable? "": BX.delegate(function()
			{
				this.messenger.takePhotoMenu()
			}, this),
			action: BX.delegate(function (text)
			{
				this.messenger.textareaHistory[this.messenger.currentTab] = '';
				this.messenger.sendMessage(this.messenger.currentTab, text);
				app.clearInput();
			}, this)
		});
		BXMobileApp.UI.Page.TextPanel.show();

		app.enableCaptureKeyboard(true);

		BX.bind(window, "orientationchange", BX.delegate(function(){
			if (this.messenger.popupMessengerBody.scrollHeight - this.messenger.popupMessengerBody.scrollTop < window.screen.height)
				this.messenger.autoScroll();
		}, this))

		BX.addCustomEvent("onKeyboardWillShow", BX.delegate(function()
		{
			this.keyboardShow = true;
			this.messenger.autoScroll()
		}, this))
		BX.addCustomEvent("onKeyboardDidHide", BX.delegate(function()
		{
			this.keyboardShow = false;
		}, this))

		app.pullDown({
			'enable': true,
			'pulltext': BX.message('IM_M_DIALOG_PULLTEXT'),
			'downtext': BX.message('IM_M_DIALOG_DOWNTEXT'),
			'loadtext': BX.message('IM_M_DIALOG_LOADTEXT'),
			'callback': BX.delegate(function(){
				BX.MessengerCommon.loadHistory(this.messenger.currentTab);
			}, this)
		});

		BX.addCustomEvent("onPageParamsChanged", BX.delegate(function(data){
			this.messenger.openMessenger(data.dialogId);
		}, this));

		if (BXMobileApp.apiVersion == 1)
		{
			this.messenger.openMessenger(this.messenger.currentTab);
		}
		else
		{
			BXMobileApp.UI.Page.params.get({callback:BX.delegate(function(data){
				this.messenger.openMessenger(data.dialogId);
			}, this)});
		}

		BX.bindDelegate(this.messenger.popupMessengerBodyWrap, 'click', {className: 'bx-messenger-content-item-avatar-button'}, BX.delegate(function(e)
		{
			BX.localStorage.set('impmh', true, 1);
			var userId = BX.proxy_context.parentNode.parentNode.getAttribute('data-senderId');
			this.messenger.messageReply(userId);
	
			return BX.PreventDefault(e);
		}, this));

		BX.adjust(BX('im-dialog-form'), {children: [this.messenger.popupMessengerFileForm = BX.create('form', { attrs : { action : this.pathToFileAjax}, props : { className : "bx-messenger-textarea-file-form" }, children: [
			BX.create('input', { attrs : { type : 'hidden', name: 'IM_FILE_UPLOAD', value: 'Y'}}),
			this.messenger.popupMessengerFileFormChatId = BX.create('input', { attrs : { type : 'hidden', name: 'CHAT_ID', value: 0}}),
			this.messenger.popupMessengerFileFormRegChatId = BX.create('input', { attrs : { type : 'hidden', name: 'REG_CHAT_ID', value: 0}}),
			this.messenger.popupMessengerFileFormRegMessageId = BX.create('input', { attrs : { type : 'hidden', name: 'REG_MESSAGE_ID', value: 0}}),
			this.messenger.popupMessengerFileFormRegParams = BX.create('input', { attrs : { type : 'hidden', name: 'REG_PARAMS', value: ''}}),
			BX.create('input', { attrs : { type : 'hidden', name: 'IM_AJAX_CALL', value: 'Y'}}),
			this.messenger.popupMessengerFileFormInput = BX.create('input', { attrs : { type : 'hidden', name: 'FAKE_INPUT', value: 'Y'}})
		]})]});

		this.disk.chatDialogInit();

		BX.bindDelegate(this.messenger.popupMessengerBodyWrap, 'click', {className: 'bx-messenger-content-item-like'}, BX.delegate(function(e) {
			var messageId = BX.proxy_context.parentNode.parentNode.parentNode.parentNode.getAttribute('data-blockmessageid');
			BX.MessengerCommon.messageLike(messageId);
			BX.localStorage.set('impmh', true, 1);
			return BX.PreventDefault(e);
		}, this));

		BX.bindDelegate(this.messenger.popupMessengerBodyWrap, 'click', {tagName: 'a'}, BX.delegate(function(e) {
			BX.localStorage.set('impmh', true, 1);
		}, this));

		BX.bindDelegate(this.messenger.popupMessengerBodyWrap, 'click', {className: 'bx-messenger-content-item-content'}, BX.delegate(function(e) {
			var messageId = BX.proxy_context.parentNode.getAttribute('data-blockmessageid');
			this.messenger.openMessageMenu(messageId);
		}, this));

		BX.bindDelegate(this.messenger.popupMessengerBodyWrap, 'click', {className: 'bx-messenger-content-item-error'}, BX.delegate(function()
		{
			BX.localStorage.set('impmh', true, 1);
			BX.MessengerCommon.sendMessageRetry();

			return BX.PreventDefault(e);
		}, this));
	}
}

BX.ImMobile.prototype.initPageAction = function (params)
{
	BX.addCustomEvent("UIApplicationDidBecomeActiveNotification", BX.delegate(function (params){
		setTimeout(BX.delegate(this.updateStateLight, this), 1000);
	}, this));

	BX.addCustomEvent("onImError", BX.delegate(function (params){
		if (params.error == 'AUTHORIZE_ERROR')
		{
			app.BasicAuth({success: BX.delegate(function ()
			{
				setTimeout(BX.delegate(this.updateStateLight, this), 1000);
			}, this)});
		}
		else if (params.error == 'RECENT_RELOAD')
		{
			app.BasicAuth({success: BX.delegate(function ()
			{
				setTimeout(BX.delegate(this.updateStateLight, this), 1000);
			}, this)});
		}
	}, this));

	BX.addCustomEvent("onPullEvent-im", BX.delegate(function (command, params)
	{
		if (command == 'readMessage')
		{
			this.messageCountArray[params.userId] = 0;
			this.updateCounter();
		}
		else if (command == 'readMessageChat')
		{
			this.messageCountArray['chat' + params.chatId] = 0;
			this.updateCounter();
		}
		else if (command == 'chatUserLeave')
		{
			if (params.userId == BX.message('USER_ID'))
			{
				this.messageCountArray['chat' + params.chatId] = 0;
				this.updateCounter();
			}
		}
		else if (command == 'readNotify')
		{
			this.notify.notifyLastId = parseInt(params.lastId);
			this.notifyCount = 0;
			this.updateCounter();
		}
		else if (command == 'message' || command == 'messageChat')
		{
			var userId = params.MESSAGE.senderId;
			if (userId == BX.message('USER_ID'))
			{
				this.messageCountArray[params.MESSAGE.recipientId] = 0;
				this.updateCounter();
				return;
			}
			if (command == 'messageChat')
				userId = params.MESSAGE.recipientId;

			if (typeof(this.messageCountArray[userId]) != 'undefined')
				this.messageCountArray[userId]++;
			else
				this.messageCountArray[userId] = 1;

			app.getVar({'var': 'PAGE_ID', 'from': 'current', 'callback': BX.delegate(function (PAGE_ID)
			{
				if (PAGE_ID == 'DIALOG' + userId)
					this.messageCountArray[userId] = 0;

				this.updateCounter();
			}, this)});
		}
		else if (command == 'notify')
		{
			lastId = parseInt(params.id);
			if (this.notify.notifyLastId < lastId)
				this.notify.notifyLastId = lastId;

			this.notifyCount++;
			this.updateCounter();

			if (!this.notify.notifyLoadFlag)
			{
				clearTimeout(this.notifyTimeout);
				this.notifyTimeout = setTimeout(BX.delegate(function ()
				{
					this.notify.notifyLoadFlag = true;
					app.refreshPanelPage('notifications');
				}, this), 600);
			}
		}
	}, this));

	BX.addCustomEvent("onNotificationsLastId", BX.delegate(function (lastId)
	{
		this.notify.notifyLoadFlag = false;
		lastId = parseInt(lastId);
		if (this.notify.notifyLastId < lastId)
			this.notify.notifyLastId = lastId;
	}, this));

	BX.addCustomEvent("onImDialogOpen", BX.delegate(function (params)
	{
		this.messageCountArray[params.id] = 0;
		this.updateCounter();
	}, this));

	BX.addCustomEvent("onNotificationsOpen", BX.delegate(function (params)
	{
		this.notify.notifyViewedWait();
	}, this));

	BX.addCustomEvent("onOpenPush", BX.delegate(function (push)
	{
		if (!(app.enableInVersion(2) && typeof(push) == 'object' && typeof(push.params) == 'string'))
			return false;

		if (push.params.substr(0, 8) == 'IM_MESS_')
		{
			var userId = parseInt(push.params.substr(8));
			if (userId > 0)
			{
				BXMobileApp.PageManager.loadPageUnique({
					'url' : this.pathToRoot + 'mobile/im/dialog.php'+(!app.enableInVersion(11)? "?id="+userId: ""),
					'bx24ModernStyle' : true,
					'data': {dialogId: userId}
				});
			}
		}
		else if (push.params.substr(0, 6) == 'IMINV_')
		{
			var arg = push.params.split("_");
			var userId = parseInt(arg[1]);
			var callTime = parseInt(arg[2]);

			if(!mwebrtc.timesUp(callTime*1000))
			{
				mwebrtc.callInvite(userId);
			}
		}
	}, this));

	app.setPanelPages({
		'messages_page': this.pathToRoot + "mobile/im/index.php?NEW",
		'messages_open_empty': true,
		'notifications_page': this.pathToRoot + "mobile/im/notify.php",
		'notifications_open_empty': true
	});

	this.updateStateLight();
}

BX.ImMobile.prototype.dialogPageAction = function (params)
{
	this.messenger.popupMessengerBody = document.body;
	this.messenger.popupMessengerBodyWrap = BX('im-dialog-wrap');
	BX.addClass(this.messenger.popupMessengerBodyWrap, 'bx-messenger-dialog-wrap');
	this.messenger.dialogOpen = true;
}

BX.ImMobile.prototype.updateStateLight = function ()
{
	clearTimeout(this.timeoutUpdateStateLight);
	this.timeoutUpdateStateLight = setTimeout(BX.delegate(function ()
	{
		BX.ajax({
			url: this.pathToAjax,
			method: 'POST',
			dataType: 'json',
			skipAuthCheck: true,
			timeout: 20,
			data: {'IM_UPDATE_STATE_LIGHT': 'Y', 'MOBILE': 'Y', 'SITE_ID': BX.message('SITE_ID'), 'NOTIFY': 'Y', 'MESSAGE': 'Y', 'IM_AJAX_CALL': 'Y', 'sessid': BX.bitrix_sessid()},
			onsuccess: BX.delegate(function (data)
			{
				if (data.ERROR.length == 0)
				{
					BX.message({'SERVER_TIME': data.SERVER_TIME});

					if (BX.PULL && data.PULL_CONFIG)
					{
						BX.PULL.updateChannelID({
							'METHOD': data.PULL_CONFIG.METHOD,
							'CHANNEL_ID': data.PULL_CONFIG.CHANNEL_ID,
							'CHANNEL_DT': data.PULL_CONFIG.CHANNEL_DT,
							'PATH': data.PULL_CONFIG.PATH,
							'LAST_ID': data.PULL_CONFIG.LAST_ID,
							'PATH_WS': data.PULL_CONFIG.PATH_WS
						});
					}

					if (data.COUNTER_MESSAGES)
						this.messageCount = parseInt(data.COUNTER_MESSAGES);
					if (data.COUNTER_NOTIFICATIONS)
						this.notifyCount = parseInt(data.COUNTER_NOTIFICATIONS);
					if (data.NOTIFY_LAST_ID)
						this.notify.notifyLastId = parseInt(data.NOTIFY_LAST_ID);

					if (this.messageCount > 0 && data.COUNTER_UNREAD_MESSAGES && typeof(data.COUNTER_UNREAD_MESSAGES) == 'object')
					{
						this.messageCount = 0;
						this.messageCountArray = {};
						for (var i in data.COUNTER_UNREAD_MESSAGES)
						{
							this.messageCount += data.COUNTER_UNREAD_MESSAGES[i].MESSAGE.counter;
							this.messageCountArray[i] = data.COUNTER_UNREAD_MESSAGES[i].MESSAGE.counter;
						}
						BX.onCustomEvent('onUpdateUserCounters', [data.COUNTER_UNREAD_MESSAGES]);
						BXMobileApp.onCustomEvent('onUpdateUserCounters', data.COUNTER_UNREAD_MESSAGES);
					}
					else
					{
						this.messageCountArray = {};
						BX.onCustomEvent('onUpdateUserCounters', [data.COUNTER_UNREAD_MESSAGES]);
						BXMobileApp.onCustomEvent('onUpdateUserCounters', data.COUNTER_UNREAD_MESSAGES);
					}
					this.updateCounter();

					if (data.COUNTERS && typeof(data.COUNTERS) == 'object')
					{
						BX.onCustomEvent('onImUpdateCounter', [data.COUNTERS]);
						BXMobileApp.onCustomEvent('onImUpdateCounter', data.COUNTERS);
					}

					if (this.notifyCount > 0 && !this.notify.notifyLoadFlag)
					{
						clearTimeout(this.notifyTimeout);
						this.notifyTimeout = setTimeout(BX.delegate(function ()
						{
							this.notify.notifyLoadFlag = true;
							app.refreshPanelPage('notifications');
						}, this), 600);
					}

					this.sendAjaxTry = 0;

					if (BX.PULL)
					{
						if (!BX.PULL.tryConnect())
						{
							BX.PULL.updateState(true);
						}
					}

					clearTimeout(this.timeoutUpdateStateLight);
					this.timeoutUpdateStateLight = setTimeout(BX.delegate(function ()
					{
						this.updateStateLight();
					}, this), 80000);
				}
				else if (data.ERROR == 'AUTHORIZE_ERROR' && this.sendAjaxTry <= 3)
				{
					this.sendAjaxTry++;
					BX.onCustomEvent('onImError', [{error: data.ERROR}]);
					BXMobileApp.onCustomEvent('onImError', {error: data.ERROR});

					clearTimeout(this.timeoutUpdateStateLight);
					this.timeoutUpdateStateLight = setTimeout(BX.delegate(function ()
					{
						this.updateStateLight();
					}, this), 2000);
				}
				else if (data.ERROR == 'SESSION_ERROR' && this.sendAjaxTry <= 3)
				{
					this.sendAjaxTry++;
					BX.message({'bitrix_sessid': data.BITRIX_SESSID});

					clearTimeout(this.timeoutUpdateStateLight);
					this.timeoutUpdateStateLight = setTimeout(BX.delegate(function ()
					{
						this.updateStateLight();
					}, this), 1000);
				}
				else
				{
					this.sendAjaxTry = 0;
				}
			}, this),
			onfailure: BX.delegate(function (data)
			{
				this.sendAjaxTry = 0;
			}, this)
		});
	}, this), 300);
}

BX.ImMobile.prototype.updateCounter = function ()
{
	clearTimeout(this.timeoutUpdateCounters);
	this.timeoutUpdateCounters = setTimeout(BX.delegate(function ()
	{
		this.messageCount = 0;
		for (var i in this.messageCountArray)
			this.messageCount += parseInt(this.messageCountArray[i]);

		app.setBadge(parseInt(this.messageCount + this.notifyCount));
		app.setCounters({
			'messages': this.messageCount,
			'notifications': this.notifyCount
		});
	}, this), 500);
}

BX.ImMobile.prototype.isFocus = function()
{
	return false;
}

BX.ImMobile.prototype.isFocusMobile = function(func)
{
	BXMobileApp.UI.Page.isVisible({callback: BX.delegate(function(data){
		func(data.status == 'visible');
	}, this)})

	return null;
}

BX.ImMobile.prototype.isMobile = function()
{
	return false;
}

BX.ImMobile.prototype.checkRevision = function(revision)
{
	revision = parseInt(revision);
	if (typeof(revision) == "number" && this.revision < revision)
	{
		console.log('NOTICE: Window reload, because REVISION UP ('+this.revision+' -> '+revision+')');
		BXMobileApp.UI.Page.reloadUnique()

		return false;
	}
	return true;
};

})();

(function() {

if (BX.ImMessengerMobile)
	return;

BX.ImMessengerMobile = function(BXIM, params)
{
	this.BXIM = BXIM;
	this.settings = {};
	this.params = params || {};

	this.notify = params.notifyClass;
	this.disk = params.diskClass;

	this.smile = params.smile;
	this.smileSet = params.smileSet;

	this.popupMessengerLikeBlock = {};
	this.popupMessengerLikeBlockTimeout = {};

	this.sendAjaxTry = 0;
	this.updateStateStepDefault = this.BXIM.ppStatus? parseInt(params.updateStateInterval): 60;
	this.updateStateStep = this.updateStateStepDefault;
	this.updateStateTimeout = null;

	this.readMessageTimeout = {};
	this.readMessageTimeoutSend = null;

	this.users = params.users;
	this.groups = params.groups;
	this.userInGroup = params.userInGroup;
	this.woGroups = params.woGroups;
	this.woUserInGroup = params.woUserInGroup;
	this.redrawTab = {};
	this.loadLastMessageTimeout = {};
	this.showMessage = params.showMessage;
	this.unreadMessage = params.unreadMessage;
	this.flashMessage = params.flashMessage;
	this.history = params.history || {};

	this.chat = params.chat;
	this.userChat = params.userChat;
	this.userInChat = params.userInChat;
	this.userChatBlockStatus = params.userChatBlockStatus;
	this.hrphoto = params.hrphoto;

	this.dialogStatusRedrawTimeout = null;
	this.chatHeaderRedrawTimeout = null;

	this.textareaHistory = {};

	this.phones = {};

	this.errorMessage = {};
	this.message = params.message;
	this.messageTmpIndex = 0;
	this.messageCount = params.countMessage;
	this.sendMessageFlag = 0;
	this.sendMessageTmp = {};
	this.sendMessageTmpTimeout = {};

	this.popupMessenger = {'fake': true};
	this.popupMessengerTextarea = null;

	this.openChatFlag = false;
	this.popupMessengerLastMessage = 0;

	this.readedList = {};
	this.writingList = {};
	this.writingListTimeout = {};
	this.writingSendList = {};
	this.writingSendListTimeout = {};

	this.contactListPanelStatus = null;
	this.contactListSearchText = '';

	this.popupContactListElementsWrap = null;
	this.popupContactListSearchInput = null;

	this.popupMessengerConnectionStatusState = "online";
	this.popupMessengerConnectionStatusStateText = "online";
	this.popupMessengerConnectionStatus = null;
	this.popupMessengerConnectionStatusText = null;
	this.popupMessengerConnectionStatusTimeout = null;

	this.recent = params.recent? params.recent: [];
	this.recentListLoad = params.recent? true: false;
	this.recentList = true;
	this.recentListReturn = false;
	this.recentListTab = null;
	this.recentListTabCounter = null;
	this.recentListIndex = [];
	this.currentTab = params.currentTab;

	this.contactList = false;
	this.contactListTab = null;
	this.contactListLoad = false;
	this.redrawContactListTimeout = {};
	this.redrawRecentListTimeout = null;

	this.enableGroupChat = this.BXIM.ppStatus? true: false;

	this.historySearch = '';
	this.historyOpenPage = {};
	this.historyLoadFlag = {};
	this.historyEndOfList = {};

	this.popupMessengerBody = null;
	this.popupMessengerBodyDialog = null;
	this.popupMessengerBodyAnimation = null;
	this.popupMessengerBodySize = 295;
	this.popupMessengerBodyWrap = null;

	this.popupMessengerFileForm = null;
	this.popupMessengerFileDropZone = null;
	this.popupMessengerFileButton = null;
	this.popupMessengerFileFormChatId = null;
	this.popupMessengerFileFormInput = null;
}

BX.ImMessengerMobile.prototype.drawRecentList = function()
{
	app.pullDown({
		'enable': true,
		'pulltext': BX.message('IM_PULLDOWN_RL_1'),
		'downtext': BX.message('IM_PULLDOWN_RL_2'),
		'loadtext': BX.message('IM_PULLDOWN_RL_3'),
		'callback': function(){
			app.BasicAuth({
				success: function() {
					BX.onCustomEvent('onImError', [{error: 'RECENT_RELOAD'}]);
					BXMobileApp.onCustomEvent('onImError', {error: 'RECENT_RELOAD'});
					BXMobileApp.UI.Page.reload();
				},
				failture: function() {
					app.pullDownLoadingStop();
				}
			});
		}
	});

	this.popupContactListSearchInput = BX('im-contact-list-search');
	this.popupContactListSearchInput.innerHTML = '';
	BX.addClass(this.popupContactListSearchInput, 'bx-messenger-cl-wrap');
	BX.unbindAll(this.popupContactListSearchInput);

	BX.adjust(this.popupContactListSearchInput, {children: [
		this.popupContactListSearchWrap = BX.create("div", { props : { className : "bx-messenger-cl-search" }, children : [
			BX.create("div", { props : { className : "bx-messenger-input-wrap bx-messenger-cl-search-wrap" }, children : [
				this.popupContactListSearchClose = BX.create("span", {props : { className : "bx-messenger-input-close" }}),
				this.popupContactListSearchInput = BX.create("input", { attrs: {type: "text", placeholder: BX.message('IM_SEARCH_PLACEHOLDER_CP'), value: this.contactListSearchText}, props : { className : "bx-messenger-input" }})
			]})
		]})
	]});
	BX.bind(this.popupContactListSearchInput, "keyup", BX.delegate(function(e)
	{
		BX.MessengerCommon.contactListSearch(e)
	}, this));

	this.popupContactListElementsWrap = BX('im-contact-list-wrap');
	this.popupContactListElementsWrap.innerHTML = '';
	BX.unbindAll(this.popupContactListElementsWrap);

	BX.addClass(this.popupContactListElementsWrap, 'bx-messenger-recent-wrap');

	BitrixMobile.fastClick.bindDelegate(this.popupContactListElementsWrap, {className: 'bx-messenger-cl-item'}, BX.delegate(BX.MessengerCommon.contactListClickItem, BX.MessengerCommon));
	BitrixMobile.fastClick.bindDelegate(this.popupContactListElementsWrap, {className: 'bx-messenger-cl-group-title'}, BX.delegate(BX.MessengerCommon.contactListToggleGroup, BX.MessengerCommon));

	BX.bind(this.popupContactListSearchClose, "click", BX.delegate(BX.MessengerCommon.contactListSearchClear, BX.MessengerCommon));

	BX.MessengerCommon.userListRedraw();
}

BX.ImMessengerMobile.prototype.openPhotoGallery = function(currentPhoto)
{
	var nodes = BX.findChildrenByClassName(this.BXIM.messenger.popupMessengerBodyWrap, "bx-messenger-file-image-text");
	var photos = [];
	var defaultImage = '';
	var nodeSrc = '';
	for(var i = 0; i < nodes.length; i++)
	{
		nodeSrc = nodes[i].getAttribute('src');
		photos.push({
			'url': nodeSrc.replace("preview=Y&", ""),
			'description': nodes[i].innerHTML
		});
		if (currentPhoto && nodeSrc.indexOf(currentPhoto) > -1)
			defaultImage = nodeSrc.replace("preview=Y&", "");
	}
	if (photos.length > 0)
	{
		BX.localStorage.set('impmh', true, 1);
		BXMobileApp.UI.Photo.show({photos: photos, default_photo: defaultImage})
	}
}

BX.ImMessengerMobile.prototype.dialogStatusRedraw = function(params)
{
	var paramsType = params && params.type? parseInt(params.type): 'none';

	clearTimeout(this.dialogStatusRedrawTimeout);
	this.dialogStatusRedrawTimeout = setTimeout(BX.delegate(function(){
		this.dialogStatusRedrawDelay(params)
	}, this), 200);
}

BX.ImMessengerMobile.prototype.dialogStatusRedrawDelay = function(params)
{
	params = params || {};
	if (this.currentTab == 0)
		return false;

	this.openChatFlag = false;
	this.openCallFlag = false;
	if (this.currentTab.toString().substr(0,4) == 'chat')
	{
		this.openChatFlag = true;
		if (this.chat[this.currentTab.toString().substr(4)] && this.chat[this.currentTab.toString().substr(4)].style == 'call')
			this.openCallFlag = true;
	}

	if (this.openChatFlag)
	{
		var chatId = this.currentTab.toString().substr(4);
		if (this.chat[chatId] && this.chat[chatId].style != 'call')
		{
			app.menuCreate({items:[
				{ icon: 'user', name: BX.message('IM_M_MENU_USERS'), action:BX.delegate(function() { app.loadPageBlank({url: '/mobile/im/chat.php?chat_id='+this.currentTab.toString().substr(4), bx24ModernStyle: true}); }, this)},
				{ icon: 'add', name: BX.message('IM_M_MENU_ADD'), action:BX.delegate(function() {  this.extendChat(this.currentTab, true); }, this)},
				{ icon: 'reload', name: BX.message('IM_M_MENU_RELOAD'), action:function() { BXMobileApp.UI.Page.reloadUnique() }}
			]});
		}
		else
		{
			app.menuCreate({items:[
				{ icon: 'reload', name: BX.message('IM_M_MENU_RELOAD'), action:function() { BXMobileApp.UI.Page.reloadUnique() }}
			]});
		}
	}
	else if (this.currentTab)
	{
		var userId = this.currentTab;
		app.menuCreate({items:[
			{ icon: 'user', name: BX.message('IM_M_MENU_USER'), action:BX.delegate(function() { app.loadPageBlank({url: this.BXIM.path.profileTemplate.replace('#user_id#', this.currentTab), bx24ModernStyle: true});}, this)},
			{ icon: 'add', name: BX.message('IM_M_MENU_ADD'), action:BX.delegate(function() {  this.extendChat(this.currentTab, false); }, this)},
			{ icon: 'reload', name: BX.message('IM_M_MENU_RELOAD'), action:function() { BXMobileApp.UI.Page.reloadUnique() }}
		]});
	}
	if (app.enableInVersion(10))
	{
		if (this.openChatFlag)
		{
			BXMobileApp.UI.Page.TopBar.title.setText(this.chat[chatId].name);
			BXMobileApp.UI.Page.TopBar.title.setImage(BX.MessengerCommon.isBlankAvatar(this.chat[chatId].avatar)? "": this.chat[chatId].avatar);
			if (this.chat[chatId].style == 'call')
			{
				BXMobileApp.UI.Page.TopBar.title.setDetailText(BX.message("IM_VI_CALL"));
			}
			else
			{
				if (this.userInChat[chatId])
					BXMobileApp.UI.Page.TopBar.title.setDetailText(BX.message("IM_M_MENU_USERS")+": "+(this.userInChat[chatId].length));
			}
		}
		else
		{
			BXMobileApp.UI.Page.TopBar.title.setText(this.users[userId].name);
			BXMobileApp.UI.Page.TopBar.title.setImage(BX.MessengerCommon.isBlankAvatar(this.users[userId].avatar)? "": this.users[userId].avatar);
			BXMobileApp.UI.Page.TopBar.title.setDetailText(this.users[userId].workPosition);
		}
		BXMobileApp.UI.Page.TopBar.title.setCallback(function ()            {
			app.menuShow();
		});
		BXMobileApp.UI.Page.TopBar.title.show();
	}
	else
	{
		app.addButtons({
			addRefreshButton:{
				type: 'context-menu',
				style: 'custom',
				callback:function(){
					app.menuShow();
				}
			}
		});
	}

	if (this.popupMessengerFileFormChatId)
	{
		if (this.openChatFlag)
			this.popupMessengerFileFormChatId.value = chatId;
		else
			this.popupMessengerFileFormChatId.value = this.userChat[this.currentTab]? this.userChat[this.currentTab]: 0;
	}

	if (!params.slidingPanelRedrawDisable && (userId > 0 && !this.users[userId].fake || chatId > 0 && !this.chat[chatId].fake))
	{
		var showSlidingPanel = false;
		var panelButtons = {};

		if (this.openChatFlag)
		{
			if (this.openCallFlag)
			{
				showSlidingPanel = true;
				panelButtons["button_2"] =
				{
					name: BX.message("IM_PHONE_CALL"),
					type: "call_audio",
					callback: BX.delegate(function () {
						document.location.href = "tel:" + this.chat[chatId].call_number;
					},this)
				};
			}
			else
			{
				app.hideButtonPanel();
			}
		}
		else if (this.webrtc.mobileSupport && app.enableInVersion(10))
		{
			showSlidingPanel = true;

			if (this.webrtc.mobileSupport && app.enableInVersion(9))
			{
				panelButtons["button_1"] =
				{
					name: BX.message("IM_VIDEO_CALL"),
					type: "call_video",
					callback: function ()
					{
						app.onCustomEvent("onCallInvite", {"userId": userId});
					}
				};
			}

			var phoneCount = BX.MessengerCommon.countObject(this.phones[userId]);
			if (phoneCount > 0)
			{
				var sheetButtons = [];

				sheetButtons.push({
					title: BX.message("IM_AUDIO_CALL"),
					callback: BX.delegate(function () { app.onCustomEvent("onCallInvite", {"userId": userId, video: false}); }, this)
				});

				if (this.phones[userId].PERSONAL_MOBILE)
				{
					sheetButtons.push({
						title: BX.message("IM_PHONE_MOB")+": "+this.phones[userId].PERSONAL_MOBILE,
						callback: BX.delegate(function () {document.location.href = "tel:" + this.phones[userId].PERSONAL_MOBILE;}, this)
					});
				}
				if (this.phones[userId].WORK_PHONE)
				{
					sheetButtons.push({
						title: BX.message("IM_PHONE_WORK")+": "+this.phones[userId].WORK_PHONE,
						callback: BX.delegate(function () {document.location.href = "tel:" + this.phones[userId].WORK_PHONE;}, this)
					});
				}
				if (this.phones[userId].PERSONAL_PHONE)
				{
					sheetButtons.push({
						title: BX.message("IM_PHONE_DEF")+": "+this.phones[userId].PERSONAL_PHONE,
						callback: BX.delegate(function () {document.location.href = "tel:" + this.phones[userId].PERSONAL_PHONE;}, this)
					});
				}

				if (sheetButtons.length > 1)
				{
					var callSheet = new BXMobileApp.UI.ActionSheet({buttons: sheetButtons},"call_audio");
					panelButtons["button_2"] =
					{
						name: BX.message("IM_PHONE_CALL"),
						type: "call_audio",
						callback: function ()
						{
							callSheet.show();
						}
					};
				}
				else
				{
					panelButtons["button_2"] =
					{
						name: BX.message("IM_AUDIO_CALL"),
						type: "call_audio",
						callback: function ()
						{
							app.onCustomEvent("onCallInvite", {"userId": userId, video: false});
						}
					};
				}
			}
			else
			{
				panelButtons["button_2"] =
				{
					name: BX.message("IM_AUDIO_CALL"),
					type: "call_audio",
					callback: function ()
					{
						app.onCustomEvent("onCallInvite", {"userId": userId, video: false});
					}
				};
			}
		}
		else if (this.webrtc.mobileSupport && app.enableInVersion(9))
		{
			showSlidingPanel = true;

			panelButtons["button_1"] =
			{
				name: BX.message("IM_VIDEO_CALL"),
				type: "call_video",
				callback: function ()
				{
					app.onCustomEvent("onCallInvite", {"userId": userId});
				}
			};
			panelButtons["button_2"] =
			{
				name: BX.message("IM_AUDIO_CALL"),
				type: "call_audio",
				callback: function ()
				{
					app.onCustomEvent("onCallInvite", {"userId": userId, video: false});
				}
			};

		}

		if (showSlidingPanel)
		{
			app.showSlidingPanel({
				hidden_sliding_panel: false,
				buttons: panelButtons
			});
		}
	}

}

BX.ImMessengerMobile.prototype.autoScroll = function ()
{
	if (document.body.scrollHeight <= window.innerHeight)
		return false;

	this.popupMessengerBody.scrollTop = this.popupMessengerBody.scrollHeight

	return true;
}

BX.ImMessengerMobile.prototype.takePhotoMenu = function ()
{
	var action = new BXMobileApp.UI.ActionSheet({
		buttons: [
				{
					title: BX.message('IM_MENU_UPLOAD_PHOTO'),
					callback: BX.delegate(function()
					{
						app.takePhoto({
							quality: 80,
							source: 1,
							correctOrientation: true,
							targetWidth: 1024,
							targetHeight: 1024,
							destinationType: Camera.DestinationType.DATA_URL,
							callback: BX.delegate(this.disk.uploadFromMobile, this.disk)
						});
					}, this)
				},
				{
					title: BX.message('IM_MENU_UPLOAD_GALLERY'),
					callback: BX.delegate(function()
					{
						app.takePhoto({
							quality: 80,
							targetWidth: 1024,
							targetHeight: 1024,
							destinationType: Camera.DestinationType.DATA_URL,
							callback: BX.delegate(this.disk.uploadFromMobile, this.disk)
						});
					}, this)
				}
			]
		},
		"textPanelSheet"
	);
	action.show();
}

BX.ImMessengerMobile.prototype.updateChatAvatar = function(chatId, chatAvatar)
{
	if (!this.openChatFlag)
		return false;

	var currentChatId = this.currentTab.toString().substr(4);
	if (chatId != currentChatId)
		return false;

	if (app.enableInVersion(10))
	{
		BXMobileApp.UI.Page.TopBar.title.setImage(BX.MessengerCommon.isBlankAvatar(chatAvatar)? "": chatAvatar);
	}

}

BX.ImMessengerMobile.prototype.redrawChatHeader = function()
{
	clearTimeout(this.chatHeaderRedrawTimeout);
	this.chatHeaderRedrawTimeout = setTimeout(BX.delegate(function(){
		this.redrawChatHeaderDelay(params)
	}, this), 200);
}

BX.ImMessengerMobile.prototype.redrawChatHeaderDelay = function()
{
	if (!this.openChatFlag)
		return false;

	var chatId = this.currentTab.toString().substr(4);
	if (!this.chat[chatId])
		return false;

	if (this.popupMessengerFileFormChatId)
	{
		this.popupMessengerFileFormChatId.value = chatId;
	}

	if (app.enableInVersion(10))
	{
		BXMobileApp.UI.Page.TopBar.title.setText(this.chat[chatId].name);
		BXMobileApp.UI.Page.TopBar.title.setImage(BX.MessengerCommon.isBlankAvatar(this.chat[chatId].avatar)? "": this.chat[chatId].avatar);
		if (this.chat[chatId].style == 'call')
		{
			BXMobileApp.UI.Page.TopBar.title.setDetailText(BX.message("IM_VI_CALL"));
		}
		else
		{
			if (this.userInChat[chatId])
				BXMobileApp.UI.Page.TopBar.title.setDetailText(BX.message("IM_M_MENU_USERS")+": "+(this.userInChat[chatId].length));
		}
	}

}

BX.ImMessengerMobile.prototype.extraClose = function() // for exit from chat
{
	app.closeController();
}

BX.ImMessengerMobile.prototype.openMessenger = function(userId, node, openPage)
{
	if (this.BXIM.mobileAction == 'RECENT')
	{
		openPage = openPage !== false;

		if (this.currentTab != userId)
		{
			var selectedElements = BX.findChild(this.popupContactListElementsWrap, {attribute : {'data-userId' : this.currentTab}}, false);
			if (selectedElements)
			{
				BX.removeClass(selectedElements, "bx-messenger-cl-item-active");
			}
			if (!node)
			{
				selectedElements = BX.findChild(this.popupContactListElementsWrap, {attribute : {'data-userId' : userId}}, false);
				if (selectedElements)
				{
					node = selectedElements;
				}
			}
			if (node)
			{
				BX.addClass(node, "bx-messenger-cl-item-active");
			}

			this.currentTab = userId;
		}
		if (openPage)
		{
			BXMobileApp.PageManager.loadPageUnique({
				'url' : this.BXIM.pathToRoot + 'mobile/im/dialog.php'+(!app.enableInVersion(11)? "?id="+this.currentTab: ""),
				'bx24ModernStyle' : true,
				'data': {dialogId: this.currentTab}
			})
		}
	}
	else if (this.BXIM.mobileAction == 'DIALOG')
	{
		if (this.currentTab == userId && this.popupMessengerBodyWrap.innerHTML != '')
			return false;

		if (typeof(userId) == "undefined" || userId == null)
			userId = 0;

		if (userId == this.BXIM.userId)
		{
			this.currentTab = 0;
			userId = 0;
		}

		if (this.currentTab == null)
			this.currentTab = 0;

		this.openChatFlag = false;
		this.openCallFlag = false;

		if (userId.toString().substr(0,4) == 'chat')
		{
			this.openChatFlag = true;
			BX.MessengerCommon.getUserParam(userId);
			if (this.chat[userId.toString().substr(4)] && this.chat[userId.toString().substr(4)].style == 'call')
				this.openCallFlag = true;
		}
		else if (this.users[userId] && this.users[userId].id)
		{
			userId = parseInt(userId);
		}
		else
		{
			userId = parseInt(userId);
			if (isNaN(userId))
			{
				userId = 0;
			}
			else
			{
				BX.MessengerCommon.getUserParam(userId);
			}
		}
		if (!this.openChatFlag && typeof(userId) != 'number')
			userId = 0;

		if (userId == 0)
		{
			this.openChatFlag = false;
			app.closeController();
		}
		else if (this.openChatFlag || userId > 0)
		{
			this.currentTab = userId;
			BX.MessengerCommon.openDialog(this.currentTab);
		}
	}
}

BX.ImMessengerMobile.prototype.closeMessenger = function(dialogId)
{
	dialogId = dialogId? dialogId: this.currentTab;
	var selectedElements = BX.findChild(this.popupContactListElementsWrap, {attribute: {'data-userId': dialogId}}, false);
	if (selectedElements)
	{
		if (BX.hasClass(selectedElements, "bx-messenger-cl-item-active"))
		{
			BX.removeClass(selectedElements, "bx-messenger-cl-item-active");
			this.currentTab = 0;
			this.openChatFlag = false;
		}
	}
}

BX.ImMessengerMobile.prototype.closeMenuPopup = function()
{
}
BX.ImMessengerMobile.prototype.editMessageCancel = function()
{
}

BX.ImMessengerMobile.prototype.sendMessage = function(recipientId, text)
{
	recipientId = typeof(recipientId) == 'string' || typeof(recipientId) == 'number' ? recipientId: this.currentTab;
	BX.MessengerCommon.endSendWriting(recipientId);

	text = text.replace('    ', "\t");
	text = BX.util.trim(text);
	if (text.length == 0)
		return false;

	var chatId = recipientId.toString().substr(0,4) == 'chat'? recipientId.toString().substr(4): (this.userChat[recipientId]? this.userChat[recipientId]: 0);

	if (this.errorMessage[recipientId])
	{
		BX.MessengerCommon.sendMessageRetry();
		this.errorMessage[recipientId] = false;
	}

	var messageTmpIndex = this.messageTmpIndex;
	this.message['temp'+messageTmpIndex] = {'id' : 'temp'+messageTmpIndex, chatId: chatId, 'senderId' : this.BXIM.userId, 'recipientId' : recipientId, 'date' : BX.MessengerCommon.getNowDate(), 'text' : BX.MessengerCommon.prepareText(text, true) };
	if (!this.showMessage[recipientId])
		this.showMessage[recipientId] = [];
	this.showMessage[recipientId].push('temp'+messageTmpIndex);

	this.messageTmpIndex++;
	BX.localStorage.set('mti', this.messageTmpIndex, 5);
	if (recipientId != this.currentTab)
		return false;

	clearTimeout(this.textareaHistoryTimeout);

	var elLoad = BX.findChildByClassName(this.popupMessengerBodyWrap, "bx-messenger-content-load");
	if (elLoad)
		BX.remove(elLoad);

	var elEmpty = BX.findChildByClassName(this.popupMessengerBodyWrap, "bx-messenger-content-empty");
	if (elEmpty)
		BX.remove(elEmpty);

	BX.MessengerCommon.drawMessage(recipientId, this.message['temp'+messageTmpIndex]);

	this.textareaHistory[recipientId] = '';
	BX.MessengerCommon.sendMessageAjax(messageTmpIndex, recipientId, text, recipientId.toString().substr(0,4) == 'chat');

	return true;
};

BX.ImMessengerMobile.prototype.setUpdateStateStep = function()
{

}
BX.ImMessengerMobile.prototype.setUpdateStateStepCount = function()
{

}

BX.ImMessengerMobile.prototype.extendChat = function (dialogId, isChat)
{
	app.openTable({
		url: this.BXIM.pathToRoot + 'mobile/index.php?mobile_action=get_user_list',
		callback: BX.delegate(function (data)
		{
			if (!(data && data.a_users && data.a_users[0]))
				return;

			var arUsers = [];
			for (var i = 0; i < data.a_users.length; i++)
				arUsers.push(data.a_users[i]['ID'].toString());

			var data = false;
			if (!isChat)
			{
				arUsers.push(dialogId);
				data = {'IM_CHAT_ADD': 'Y', 'USERS': JSON.stringify(arUsers), 'IM_AJAX_CALL': 'Y', 'sessid': BX.bitrix_sessid()};
			}
			else
			{
				data = {'IM_CHAT_EXTEND': 'Y', 'CHAT_ID': dialogId.substr(4), 'USERS': JSON.stringify(arUsers), 'IM_AJAX_CALL': 'Y', 'sessid': BX.bitrix_sessid()};
			}
			if (!data)
				return false;

			BX.ajax({
				url: this.BXIM.pathToRoot + 'mobile/ajax.php?mobile_action=im&' + (isChat ? 'CHAT_EXTEND' : 'CHAT_ADD'),
				method: 'POST',
				dataType: 'json',
				timeout: 60,
				data: data,
				onsuccess: BX.delegate(function (data)
				{
					if (data.ERROR == '')
					{
						if (!isChat && data.CHAT_ID)
						{
							BXMobileApp.PageManager.loadPageUnique({
								'url' : this.BXIM.pathToRoot + 'mobile/im/dialog.php'+(!app.enableInVersion(11)? "?id=chat"+data.CHAT_ID: ""),
								'bx24ModernStyle' : true,
								'data': {dialogId: 'chat' + data.CHAT_ID}
							});
						}
					}
					else
					{
						alert(data.ERROR);
					}
				}, this)
			});
		}, this),
		set_focus_to_search: true,
		markmode: true,
		multiple: true,
		return_full_mode: true,
		modal: true,
		alphabet_index: true,
		outsection: false,
		okname: BX.message('IM_M_EXTEND')
	});
}

BX.ImMessengerMobile.prototype.messageReply = function(userId)
{
	if (!this.users[userId] || this.users[userId].fake)
		return false;

	var userName =  BX.util.htmlspecialcharsback(this.users[userId].name);
	userName = userName+', ';

	if (!this.textareaHistory[this.currentTab])
		this.textareaHistory[this.currentTab] = '';

	this.textareaHistory[this.currentTab] = this.textareaHistory[this.currentTab]+' '+userName;
	BXMobileApp.UI.Page.TextPanel.setText(this.textareaHistory[this.currentTab]);
	BXMobileApp.UI.Page.TextPanel.focus();
}
BX.ImMessengerMobile.prototype.openMessageMenu = function(messageId)
{
	if (!this.message[messageId] || this.BXIM.keyboardShow || BX.localStorage.get('impmh'))
		return false;

	var iLikeThis = BX.MessengerCommon.messageIsLike(messageId);

	var sheetButtons = [];

	sheetButtons.push({
		title: BX.message(iLikeThis? "IM_MENU_MESS_DISLIKE": "IM_MENU_MESS_LIKE"),
		callback: BX.delegate(function () { BX.MessengerCommon.messageLike(messageId); }, this)
	});

	var userId = this.message[messageId].senderId;
	if (userId > 0)
	{
		sheetButtons.push({
			title: BX.message("IM_MENU_MESS_REPLY"),
			callback: BX.delegate(function () { this.messageReply(userId); }, this)
		});
	}

	/*
	sheetButtons.push({
		title: BX.message("IM_MENU_MESS_QUOTE"),
		callback: BX.delegate(function () { }, this)
	});
	*/
	/*
	sheetButtons.push({
		title: BX.message("IM_MENU_MESS_LIKE_LIST"),
		callback: BX.delegate(function () { }, this)
	});
	*/

	var deleteMessageId = 0;
	var firstMessageId = BX('im-message-'+messageId)
	if (firstMessageId)
	{
		var nodes = BX.findChildrenByClassName(firstMessageId.parentNode.parentNode, "bx-messenger-message");
		for (var i = nodes.length - 1; i >= 0 && deleteMessageId == 0; i--)
		{
			if (!BX.hasClass(nodes[i], 'bx-messenger-message-deleted'))
			{
				deleteMessageId = nodes[i].id.substr(11);
			}
		}
	}

	if (BX.MessengerCommon.checkEditMessage(deleteMessageId))
	{
		/*
		sheetButtons.push({
			title: BX.message("IM_MENU_MESS_EDIT"),
			callback: BX.delegate(function () { }, this)
		});
		*/
		sheetButtons.push({
			title: BX.message("IM_MENU_MESS_DEL"),
			callback: BX.delegate(function () { this.deleteMessage(deleteMessageId); }, this)
		});
	}

	var callSheet = new BXMobileApp.UI.ActionSheet({buttons: sheetButtons},"im-message-menu");
	callSheet.show();
}

BX.ImMessengerMobile.prototype.deleteMessage = function(messageId, check)
{
	if (!BX.MessengerCommon.checkEditMessage(messageId))
		return false;

	if (check !== false)
	{
		var message = this.message[messageId].text.length > 50? this.message[messageId].text.substr(0, 47) + '...': this.message[messageId].text;

		app.confirm({
			title : BX.message('IM_MENU_MESS_DEL_CONFIRM'),
			text : '"' + message + '"',
			buttons : [BX.message('IM_MENU_MESS_DEL_YES'), BX.message('IM_MENU_MESS_DEL_NO')],
			callback : function (btnNum)
			{
				if (btnNum == 1)
				{
					BX.MessengerCommon.deleteMessageAjax(messageId);
				}
			}
		});
	}
	else
	{
		this.deleteMessageAjax(messageId);
	}
}

})();

(function() {

if (BX.ImNotifyMobile)
	return;

BX.ImNotifyMobile = function(rootObject, params)
{
	this.BXIM = rootObject;
	this.sendAjaxTry = 0;
	this.notifyLastId = 0;
	this.notifyLoadFlag = false;
	this.timeoutNotifyViewedWait = null;
}

BX.ImNotifyMobile.prototype.notifyViewedWait = function ()
{
	clearTimeout(this.timeoutNotifyViewedWait);
	if (!this.notifyLoadFlag)
	{
		this.timeoutNotifyViewedWait = setTimeout(BX.delegate(this.notifyViewed, this), 300)
		this.BXIM.notifyCount = 0;
		this.BXIM.updateCounter();
	}
	else
	{
		clearTimeout(this.timeoutNotifyViewedWait);
		this.timeoutNotifyViewedWait = setTimeout(BX.delegate(this.notifyViewedWait, this), 2000)
	}
}

BX.ImNotifyMobile.prototype.notifyViewed = function ()
{
	if (parseInt(this.notifyLastId) <= 0)
		return false;

	BX.ajax({
		url: this.BXIM.pathToAjax,
		method: 'POST',
		dataType: 'json',
		skipAuthCheck: true,
		data: {'IM_NOTIFY_VIEWED': 'Y', 'MAX_ID': parseInt(this.notifyLastId), 'IM_AJAX_CALL': 'Y', 'sessid': BX.bitrix_sessid()},
		onsuccess: BX.delegate(function (data)
		{
			if (data.ERROR.length == 0)
			{
				this.sendAjaxTry = 0;
				this.notifyLastId = 0;
			}
			else if (data.ERROR == 'AUTHORIZE_ERROR' && this.sendAjaxTry <= 3)
			{
				this.sendAjaxTry++;
				BX.onCustomEvent('onImError', [{error: data.ERROR}]);
				BXMobileApp.onCustomEvent('onImError', {error: data.ERROR});

				setTimeout(BX.delegate(function ()
				{
					this.notifyViewed();
				}, this), 2000);
			}
			else if (data.ERROR == 'SESSION_ERROR' && this.sendAjaxTry <= 3)
			{
				this.sendAjaxTry++;
				BX.message({'bitrix_sessid': data.BITRIX_SESSID});
				setTimeout(BX.delegate(function ()
				{
					this.notifyViewed();
				}, this), 1000);
			}
			else
			{
				this.sendAjaxTry = 0;
			}
		}, this),
		onfailure: BX.delegate(function (data)
		{
			this.sendAjaxTry = 0;
		}, this)
	});

	return true;
}

})();

(function() {

if (BX.ImDiskManagerMobile)
	return;

BX.ImDiskManagerMobile = function(rootObject, params)
{
	this.BXIM = rootObject;
	this.notify = params.notifyClass;
	this.enable = params.enable;
	this.lightVersion = false;

	this.formBlocked = {};
	this.formAgents = {};

	this.files = params.files;
	this.filesProgress = {};
	this.filesMessage = {};
	this.filesRegister = {};

	this.fileTmpId = 1;

	this.timeout = {};

	BX.garbage(function(){
		var messages = {};
		var chatId = 0;
		for (var tmpId in this.filesMessage)
		{
			messages[tmpId] = this.filesMessage[tmpId];
			if (this.messenger.message[messages[tmpId]])
			{
				chatId = this.messenger.message[messages[tmpId]].chatId;
			}
		}
		if (chatId > 0)
		{
			BX.ajax({
				url: this.BXIM.pathToFileAjax+'?FILE_TERMINATE&V='+this.BXIM.revision,
				method: 'POST',
				dataType: 'json',
				timeout: 30,
				async: false,
				data: {'IM_FILE_UNREGISTER' : 'Y', CHAT_ID: chatId, FILES: JSON.stringify(this.filesProgress), MESSAGES: JSON.stringify(messages), 'IM_AJAX_CALL' : 'Y', 'sessid': BX.bitrix_sessid()}
			});
		}
	}, this);
}


BX.ImDiskManagerMobile.prototype.chatDialogInit = function()
{
	this.formAgents['imDialog'] = BX.Uploader.getInstance({
		id : 'imDialog',
		allowUpload : "A",
		uploadMethod : "deferred",
		uploadFormData : "Y",
		showImage : true,
		filesInputMultiple: true,
		uploadFileUrl : this.BXIM.pathToFileAjax,
		input : null,
		fields: {preview: {params: {width: 212, height: 119}}}
	});
	this.formAgents['imDialog'].form = this.messenger.popupMessengerFileForm;

	BX.addCustomEvent(this.formAgents['imDialog'], "onError", BX.delegate(BX.MessengerCommon.diskChatDialogUploadError, BX.MessengerCommon));

	BX.addCustomEvent(this.formAgents['imDialog'], "onFileIsInited", BX.delegate(function(id, file, agent){
		BX.MessengerCommon.diskChatDialogFileInited(id, file, agent);
		BX.addCustomEvent(file, 'onUploadStart', BX.delegate(BX.MessengerCommon.diskChatDialogFileStart, BX.MessengerCommon));
		BX.addCustomEvent(file, 'onUploadProgress', BX.delegate(BX.MessengerCommon.diskChatDialogFileProgress, BX.MessengerCommon));
		BX.addCustomEvent(file, 'onUploadDone', BX.delegate(BX.MessengerCommon.diskChatDialogFileDone, BX.MessengerCommon));
		BX.addCustomEvent(file, 'onUploadError', BX.delegate(BX.MessengerCommon.diskChatDialogFileError, BX.MessengerCommon));
	}, this));
};

BX.ImDiskManagerMobile.prototype.uploadFromMobile = function(image)
{
	var dataBlob = BX.UploaderUtils.dataURLToBlob("data:image/png;base64,"+image);
	dataBlob.name = 'mobile_'+BX.date.format("Ymd_His")+'.png';
	this.formAgents['imDialog'].onChange([dataBlob]);
};

BX.ImDiskManagerMobile.prototype.diskChatDialogFileInited = function(id, file, agent)
{
	var chatId = agent.form.CHAT_ID.value;

	if (!this.files[chatId])
		this.files[chatId] = {};

	this.files[chatId][id] = {
		'id': id,
		'tempId': id,
		'chatId': chatId,
		'date': BX.MessengerCommon.getNowDate(),
		'type': file.isImage? 'image': 'file',
		'preview': file.isImage? file.canvas: '',
		'name': file.name,
		'size': file.file.size,
		'status': 'upload',
		'progress': -1,
		'authorId': this.BXIM.userId,
		'authorName': this.messenger.users[this.BXIM.userId].name,
		'urlPreview': '',
		'urlShow': '',
		'urlDownload': ''
	};

	if (!this.filesRegister[chatId])
		this.filesRegister[chatId] = {};

	this.filesRegister[chatId][id] = {
		'id': id,
		'type': this.files[chatId][id].type,
		'mimeType': file.file.type,
		'name': this.files[chatId][id].name,
		'size': this.files[chatId][id].size
	};

	BX.MessengerCommon.diskChatDialogFileRegister(chatId);

}
BX.ImDiskManagerMobile.prototype.saveToDisk = function()
{
	return true
}

})();
