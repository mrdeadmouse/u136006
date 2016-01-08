;(function (window)
{
	if (window.BX.MessengerCommon) return;

	var BX = window.BX;

	BX.MessengerCommon = function ()
	{
		this.BXIM = {};
	};

	/* Context */
	BX.MessengerCommon.prototype.setBxIm = function(dom)
	{
		this.BXIM = dom;
	}

	BX.MessengerCommon.prototype.isMobile = function()
	{
		return this.BXIM.mobileVersion;
	}

	BX.MessengerCommon.prototype.MobileActionEqual = function(action)
	{
		if (!this.isMobile())
			return true;

		for (var i = 0; i < arguments.length; i++)
		{
			if (arguments[i] == this.BXIM.mobileAction)
				return true;
		}

		return false;
	}

	BX.MessengerCommon.prototype.MobileActionNotEqual = function(action)
	{
		if (!this.isMobile())
			return false;

		for (var i = 0; i < arguments.length; i++)
		{
			if (arguments[i] == this.BXIM.mobileAction)
				return false;
		}

		return true;
	}

	BX.MessengerCommon.prototype.isScrollMax = function(element, infelicity)
	{
		if (!element) return true;
		infelicity = typeof(infelicity) == 'number'? infelicity: 0;
		return (element.scrollHeight - element.offsetHeight - infelicity <= element.scrollTop);
	};

	BX.MessengerCommon.prototype.isScrollMin = function(element)
	{
		if (!element) return false;
		return (0 == element.scrollTop);
	};

	BX.MessengerCommon.prototype.enableScroll = function(element, max, scroll)
	{
		if (!element)
			return false;

		scroll = scroll !== false;
		max = parseInt(max);

		return (scroll && this.isScrollMax(element, max));
	};

	BX.MessengerCommon.prototype.preventDefault = function(event)
	{
		event = event||window.event;

		if (event.stopPropagation)
			event.stopPropagation();
		else
			event.cancelBubble = true;

		if (typeof(BXIM) != 'undefined' && BXIM.messenger && BXIM.messenger.closeMenuPopup)
			BXIM.messenger.closeMenuPopup();

		if (typeof(BX) != 'undefined' && BX.calendar && BX.calendar.get().popup)
			BX.calendar.get().popup.close();
	};

	BX.MessengerCommon.prototype.countObject = function(obj)
	{
		var result = 0;

		for (var i in obj)
		{
			if (obj.hasOwnProperty(i))
			{
				result++;
			}
		}

		return result;
	};

	/* Element Coords */
	BX.MessengerCommon.prototype.isElementCoordsBelow = function (element, domBox, offset, returnArray)
	{
		if (this.isMobile())
		{
			return true;
		}

		if (!domBox || typeof(domBox.getElementsByClassName) == 'undefined')
		{
			return false;
		}

		offset = offset? offset: 0;

		var coords = this.getElementCoords(element, domBox);
		coords.bottom = coords.top+element.offsetHeight;

		var topVisible = (coords.top >= offset);
		var bottomVisible = (coords.bottom > offset);

		if (returnArray)
		{
			return {'top': topVisible, 'bottom': bottomVisible, 'coords': coords};
		}
		else
		{
			return (topVisible || bottomVisible);
		}
	}


	BX.MessengerCommon.prototype.isElementVisibleOnScreen = function (element, domBox, returnArray)
	{
		if (this.isMobile())
		{
			return BitrixMobile.isElementVisibleOnScreen(element);
		}

		if (!domBox || typeof(domBox.getElementsByClassName) == 'undefined')
		{
			return false;
		}

		var coords = this.getElementCoords(element, domBox);
		coords.bottom = coords.top+element.offsetHeight;

		var windowTop = domBox.scrollTop;
		var windowBottom = windowTop + domBox.clientHeight;

		var topVisible = (coords.top >= 0 && coords.top < windowBottom);
		var bottomVisible = (coords.bottom > 0 && coords.bottom < domBox.clientHeight);

		if (returnArray)
		{
			return {'top': topVisible, 'bottom': bottomVisible};
		}
		else
		{
			return (topVisible || bottomVisible);
		}
	}

	BX.MessengerCommon.prototype.getElementCoords = function (element, domBox)
	{
		if (this.isMobile())
		{
			return BitrixMobile.getElementCoords(element);
		}

		if (!domBox || typeof(domBox.getElementsByClassName) == 'undefined')
		{
			return false;
		}

		var box = element.getBoundingClientRect();
		var inBox = domBox.getBoundingClientRect();

		return {
			originTop: box.top,
			originLeft: box.left,
			top: box.top - inBox.top,
			left: box.left - inBox.left
		};
	}

	/* Date */
	BX.MessengerCommon.prototype.getDateFormatType = function(type)
	{
		type = type? type.toString().toUpperCase(): 'DEFAULT';

		var format = [];
		if (type == 'MESSAGE_TITLE')
		{
			format = [
				["tommorow", "tommorow"],
				["today", "today"],
				["yesterday", "yesterday"],
				["", BX.date.convertBitrixFormat(BX.message("IM_M_MESSAGE_TITLE_FORMAT_DATE"))]
			];
		}
		else if (type == 'MESSAGE')
		{
			format = [
				["", BX.message("IM_M_MESSAGE_FORMAT_TIME")]
			];
		}
		else if (type == 'RECENT_TITLE')
		{
			format = [
				["tommorow", "today"],
				["today", "today"],
				["yesterday", "yesterday"],
				["", BX.date.convertBitrixFormat(BX.message("IM_CL_RESENT_FORMAT_DATE"))]
			]
		}
		else
		{
			format = [
				["tommorow", "tommorow, "+BX.message("IM_M_MESSAGE_FORMAT_TIME")],
				["today", "today, "+BX.message("IM_M_MESSAGE_FORMAT_TIME")],
				["yesterday", "yesterday, "+BX.message("IM_M_MESSAGE_FORMAT_TIME")],
				["", BX.date.convertBitrixFormat(BX.message("FORMAT_DATETIME"))]
			];
		}
		return format;
	}

	BX.MessengerCommon.prototype.formatDate = function(timestamp, format)
	{
		if (typeof(format) == 'undefined')
		{
			format = this.getDateFormatType('DEFAULT')
		}
		return BX.date.format(format, parseInt(timestamp)+parseInt(BX.message("SERVER_TZ_OFFSET")), this.getNowDate()+parseInt(BX.message("SERVER_TZ_OFFSET")), true);
	};

	BX.MessengerCommon.prototype.getNowDate = function(today)
	{
		var currentDate = (new Date);
		if (today == true)
			currentDate = (new Date(currentDate.getFullYear(), currentDate.getMonth(), currentDate.getDate(), 0, 0, 0));

		return Math.round((+currentDate/1000))+parseInt(BX.message("USER_TZ_OFFSET"));
	};

	BX.MessengerCommon.prototype.getDateDiff = function (timestamp)
	{
		var userTzOffset = BX.message("USER_TZ_OFFSET");
		if (userTzOffset === "")
			return 0;

		var localTimestamp = this.getNowDate()+parseInt(BX.message("SERVER_TZ_OFFSET"));
		var incomingTimestamp = parseInt(timestamp)+parseInt(BX.message("SERVER_TZ_OFFSET"));

		return localTimestamp - incomingTimestamp;
	};

	/* Images */
	BX.MessengerCommon.prototype.isBlankAvatar = function(url)
	{
		return url == '' || url.indexOf(this.BXIM.pathToBlankImage) >= 0;
	};

	BX.MessengerCommon.prototype.hideErrorImage = function(element)
	{
		var link = element.src;
		element.parentNode.parentNode.className = ''
		element.parentNode.parentNode.innerHTML = '<a href="'+link+'" target="_blank">'+link+'</a>';
	}

	/* Text */
	BX.MessengerCommon.prototype.prepareText = function(text, prepare, quote, image, highlightText)
	{
		var textElement = text;
		prepare = prepare == true;
		quote = quote == true;
		image = image == true;
		highlightText = highlightText? highlightText: false;

		textElement = BX.util.trim(textElement);

		var quoteSign = "&gt;&gt;";
		if(quote && textElement.indexOf(quoteSign) >= 0)
		{
			var textPrepareFlag = false;
			var textPrepare = textElement.split("<br />");
			for(var i = 0; i < textPrepare.length; i++)
			{
				if(textPrepare[i].substring(0,quoteSign.length) == quoteSign)
				{
					textPrepare[i] = textPrepare[i].replace(quoteSign, "<div class=\"bx-messenger-content-quote\"><span class=\"bx-messenger-content-quote-icon\"></span><div class=\"bx-messenger-content-quote-wrap\">");
					while(++i < textPrepare.length && textPrepare[i].substring(0,quoteSign.length) == quoteSign)
					{
						textPrepare[i] = textPrepare[i].replace(quoteSign, '');
					}
					textPrepare[i-1] += '</div></div>';
					textPrepareFlag = true;
				}
			}
			textElement = textPrepare.join("<br />");
		}
		if (prepare)
		{
			textElement = BX.util.htmlspecialchars(textElement);
		}
		if (quote)
		{
			textElement = textElement.replace(/------------------------------------------------------<br \/>(.*?)\[(.*?)\]<br \/>(.*?)------------------------------------------------------(<br \/>)?/g, function(whole, p1, p2, p3, p4, offset){
				return (offset > 0? '<br>':'')+"<div class=\"bx-messenger-content-quote\"><span class=\"bx-messenger-content-quote-icon\"></span><div class=\"bx-messenger-content-quote-wrap\"><div class=\"bx-messenger-content-quote-name\">"+p1+" <span class=\"bx-messenger-content-quote-time\">"+p2+"</span></div>"+p3+"</div></div><br />";
			});
			textElement = textElement.replace(/------------------------------------------------------<br \/>(.*?)------------------------------------------------------(<br \/>)?/g, function(whole, p1, p2, p3, offset){
				return (offset > 0? '<br>':'')+"<div class=\"bx-messenger-content-quote\"><span class=\"bx-messenger-content-quote-icon\"></span><div class=\"bx-messenger-content-quote-wrap\">"+p1+"</div></div><br />";
			});
		}
		if (prepare)
		{
			textElement = textElement.replace(/\n/gi, '<br />');
		}
		textElement = textElement.replace(/\t/gi, '&nbsp;&nbsp;&nbsp;&nbsp;');

		if (image)
		{
			textElement = textElement.replace(/<a(.*?)>(http[s]{0,1}:\/\/.*?)<\/a>/ig, function(whole, aInner, text, offset)
			{
				if(!text.match(/\.(jpg|jpeg|png|gif)$/i) || text.indexOf("/docs/pub/") > 0 || text.indexOf("logout=yes") > 0)
				{
					return whole;
				}
				else if (BX.MessengerCommon.isMobile())
				{
					return (offset > 0? '<br />':'')+'<span class="bx-messenger-file-image"><span class="bx-messenger-file-image-src"><img src="'+text+'" class="bx-messenger-file-image-text" onclick="BXIM.messenger.openPhotoGallery(\''+text+'\');" onerror="BX.MessengerCommon.hideErrorImage(this)"></span></span><br>';
				}
				else
				{
					return (offset > 0? '<br />':'')+'<span class="bx-messenger-file-image"><a' +aInner+ ' target="_blank" class="bx-messenger-file-image-src"><img src="'+text+'" class="bx-messenger-file-image-text" onerror="BX.MessengerCommon.hideErrorImage(this)"></a></span><br>';
				}
			});
		}
		if (highlightText)
		{
			textElement = textElement.replace(new RegExp("("+highlightText.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&")+")",'ig'), '<span class="bx-messenger-highlight">$1</span>');
		}

		if (false)
		{
			textElement = textElement.replace(
				/^(\s*<img\s+src=[^>]+?data-code=[^>]+?width=")(\d+)("[^>]+?height=")(\d+)("[^>]+?class="bx-smile"\s*\/?>\s*)$/,
				function doubleSmileSize(match, start, width, middle, height, end) {
					return start + (parseInt(width, 10) * 2) + middle + (parseInt(height, 10) * 2) + end;
				}
			);
		}

		if (true)
		{
			textElement = textElement.replace(/\[USER=([0-9]{1,})\](.*?)\[\/USER\]/ig, function(whole, userId, text)
			{
				var html = '';

				userId = parseInt(userId);
				if (quote && text && userId > 0 && typeof(BXIM) != 'undefined')
					html = '<span class="bx-messenger-ajax '+(userId == BXIM.userId? 'bx-messenger-ajax-self': '')+'" data-entity="user" data-userId="'+userId+'">'+text+'</span>';
				else
					html = text;

				return html;
			});
			textElement = textElement.replace(/\[PCH=([0-9]{1,})\](.*?)\[\/PCH\]/ig, function(whole, historyId, text)
			{
				var html = '';

				historyId = parseInt(historyId);
				if (quote && text && historyId > 0)
					html = '<span class="bx-messenger-ajax" data-entity="phoneCallHistory" data-historyId="'+historyId+'">'+text+'</span>';
				else
					html = text;

				return html;
			});
		}
		if (textElement.substr(-6) == '<br />')
		{
			textElement = textElement.substr(0, textElement.length-6);
		}
		textElement = textElement.replace(/<br><br \/>/ig, '<br />');
		textElement = textElement.replace(/<br \/><br>/ig, '<br />');

		return textElement;
	};

	BX.MessengerCommon.prototype.prepareTextBack = function(text, trueQuote)
	{
		var textElement = text;

		trueQuote = trueQuote === true;

		textElement = BX.util.htmlspecialcharsback(textElement);
		textElement = textElement.replace(/<(\/*)([buis]+)>/ig, '[$1$2]');
		textElement = textElement.replace(/<img.*?data-code="([^"]*)".*?>/ig, '$1');
		textElement = textElement.replace(/<a.*?href="([^"]*)".*?>.*?<\/a>/ig, '$1');
		if (!trueQuote)
		{
			textElement = textElement.replace(/------------------------------------------------------(.*?)------------------------------------------------------/gmi, "["+BX.message("IM_M_QUOTE_BLOCK")+"]");
		}
		textElement = textElement.split('&nbsp;&nbsp;&nbsp;&nbsp;').join("\t");
		textElement = textElement.split('<br />').join("\n");//.replace(/<\/?[^>]+>/gi, '');

		return textElement;
	};

	/* User state */
	BX.MessengerCommon.prototype.getUserParam = function(userId, reset)
	{
		userId = typeof(userId) == 'undefined'? this.BXIM.userId: userId;
		reset = typeof(reset) == 'boolean'? reset: false;

		if (userId.toString().substr(0,4) == 'chat')
		{
			var chatId = userId.toString().substr(4);
			if (reset || !(this.BXIM.messenger.chat[chatId] && this.BXIM.messenger.chat[chatId].id))
			{
				this.BXIM.messenger.chat[chatId] = {'id': chatId, 'name': BX.message('IM_M_LOAD_USER'), 'owner': 0, workPosition: '', 'avatar': this.BXIM.pathToBlankImage, 'style': 'chat', 'fake': true};
				if (reset)
				{
					this.BXIM.messenger.chat[chatId].fake = false;
				}
			}
			return this.BXIM.messenger.chat[chatId];
		}
		else
		{
			if (reset || !(this.BXIM.messenger.users[userId] && this.BXIM.messenger.users[userId].id))
			{
				this.BXIM.messenger.users[userId] = {'id': userId, 'avatar': this.BXIM.pathToBlankImage, 'name': BX.message('IM_M_LOAD_USER'), 'profile': this.BXIM.path.profileTemplate.replace('#user_id#', userId), 'status': 'guest', workPosition: '', 'extranet': false, 'fake': true};
				this.BXIM.messenger.hrphoto[userId] = '/bitrix/js/im/images/hidef-avatar-v2.png';
				if (reset)
				{
					this.BXIM.messenger.users[userId].fake = false;
				}
			}
			return this.BXIM.messenger.users[userId];
		}
	}

	BX.MessengerCommon.prototype.getUserStatus = function(userId, getText)
	{
		userId = parseInt(userId);
		userId = isNaN(userId)? this.BXIM.userId: userId;
		getText = getText === true;

		var status = '';
		var statusText = '';
		if (typeof(this.BXIM.messenger.users[userId]) == 'undefined')
		{
			status = 'na';
			statusText = BX.message('IM_STATUS_NA');
		}
		else if (this.BXIM.messenger.users[userId].status == 'offline')
		{
			status = 'offline';
			statusText = BX.message('IM_STATUS_OFFLINE');
		}
		else if (this.BXIM.messenger.BXIM.userId == userId)
		{
			status = this.BXIM.messenger.users[userId].status;
			statusText = BX.message('IM_STATUS_'+status.toUpperCase());
		}
		else if (this.BXIM.messenger.users[userId].idle > 0)
		{
			status = 'idle';
			statusText = BX.message('IM_STATUS_AWAY_TITLE').replace('#TIME#', this.getUserIdle(userId));
		}
		else if (this.BXIM.messenger.users[userId].birthday && (this.BXIM.messenger.users[userId].status == 'online' || this.BXIM.messenger.users[userId].status == 'offline'))
		{
			status = 'birthday';
			if (this.BXIM.messenger.users[userId].status == 'offline')
			{
				statusText = BX.message('IM_STATUS_OFFLINE');
			}
			else
			{
				statusText = BX.message('IM_M_BIRTHDAY_MESSAGE_SHORT');
			}
		}
		else
		{
			status = this.BXIM.messenger.users[userId].status;
			statusText = BX.message('IM_STATUS_'+status.toUpperCase());
		}
		return getText? statusText: status;
	}

	BX.MessengerCommon.prototype.getUserIdle = function(userId)
	{
		userId = parseInt(userId);
		userId = isNaN(userId)? this.BXIM.userId: userId;

		var message = "";
		if ( this.BXIM.messenger.users[userId].idle > 0)
		{
			var idle = parseInt(this.BXIM.messenger.users[userId].idle);
			message = this.formatDate(this.BXIM.messenger.users[userId].idle, this.getNowDate()-idle >= 3600? 'Hdiff': 'idiff')
		}

		return message;
	}

	BX.MessengerCommon.prototype.getUserPosition = function(userId)
	{
		var pos = '';

		if (!this.BXIM.messenger.users[userId])
			return '';

		if (this.BXIM.messenger.users[userId].workPosition)
		{
			pos = this.BXIM.messenger.users[userId].workPosition;
		}
		else if (this.BXIM.messenger.users[userId].extranet)
		{
			pos = BX.message('IM_CL_USER_EXTRANET');
		}
		else if (this.BXIM.messenger.BXIM.bitrixIntranet)
		{
			pos = BX.message('IM_CL_USER_B24');
		}
		else
		{
			pos = BX.message('IM_CL_USER');
		}
		return pos;
	}

	/* CL & RL */
	BX.MessengerCommon.prototype.userListRedraw = function(params)
	{
		if (this.isMobile())
		{
			if (!this.MobileActionEqual('RECENT'))
			{
				return false;
			}
		}
		else
		{
			if (this.BXIM.messenger.popupMessenger == null)
				return false;
		}

		if (this.BXIM.messenger.recentList && this.BXIM.messenger.contactListSearchText != null && this.BXIM.messenger.contactListSearchText.length == 0)
			this.recentListRedraw(params);
		else
			this.contactListRedraw(params);
	};

	/* Concact List */
	BX.MessengerCommon.prototype.contactListRedraw = function(params)
	{
		params = params || {};

		if (this.BXIM.messenger.contactListSearchText != null && this.BXIM.messenger.contactListSearchText.length == 0)
			this.BXIM.messenger.recentListReturn = false;

		if (!this.isMobile())
		{
			this.BXIM.messenger.contactList = true;
			BX.addClass(this.BXIM.messenger.contactListTab, 'bx-messenger-cl-switcher-tab-active');
			this.BXIM.messenger.recentList = false;
			BX.removeClass(this.BXIM.messenger.recentListTab, 'bx-messenger-cl-switcher-tab-active');

			if (this.BXIM.messenger.popupPopupMenu != null)
				this.BXIM.messenger.popupPopupMenu.close();
		}

		if (this.BXIM.messenger.contactListSearchText.length > 0)
		{
			this.contactListPrepareSearch('contactList', this.BXIM.messenger.popupContactListElementsWrap, this.BXIM.messenger.contactListSearchText, params.FORCE? {}: {params: false, timeout: this.isMobile()? 500: 100})
		}
		else
		{
			if (this.BXIM.messenger.redrawContactListTimeout['contactList'])
				clearTimeout(this.BXIM.messenger.redrawContactListTimeout['contactList']);

			this.BXIM.messenger.popupContactListElementsWrap.innerHTML = '';
			BX.adjust(this.BXIM.messenger.popupContactListElementsWrap, {children: this.contactListPrepare()});
			if (this.isMobile())
			{
				BitrixMobile.LazyLoad.showImages();
			}
		}

		params.SEND = params.SEND == true;
		if (!this.isMobile() && params.SEND)
		{
			BX.localStorage.set('mrd', {viewGroup: this.BXIM.settings.viewGroup, viewOffline: this.BXIM.settings.viewOffline}, 5);
		}
	};

	BX.MessengerCommon.prototype.contactListPrepareSearch = function(name, bind, search, params)
	{
		if (params.params != false)
		{
			var searchParams = {'groupOpen': true, 'viewOffline': false, 'viewGroup': true, 'viewChat': false, 'viewOfflineWithPhones': false, 'extra': false, 'searchText': search};
			for (var i in params)
			{
				if (i == 'timeout' || i == 'params')
					continue;

				searchParams[i] = params[i];
			}
		}
		var timeout = params.timeout? params.timeout: 0;

		if (timeout > 0)
		{
			clearTimeout(this.BXIM.messenger.redrawContactListTimeout[name]);
			this.BXIM.messenger.redrawContactListTimeout[name] = setTimeout(BX.delegate(function(){
				bind.innerHTML = '';
				BX.adjust(bind, {children: this.contactListPrepare(searchParams)});
				if (this.isMobile())
				{
					BitrixMobile.LazyLoad.showImages();
				}
			}, this), timeout);
		}
		else
		{
			bind.innerHTML = '';
			BX.adjust(bind, {children: this.contactListPrepare(searchParams)});
			if (this.isMobile())
			{
				BitrixMobile.LazyLoad.showImages();
			}
		}
	}

	BX.MessengerCommon.prototype.contactListPrepare = function(params)
	{
		params = typeof(params) == 'object'? params: {};
		var items = [];
		var groupsTmp = {};
		var groups = {};
		var unreadUsers = [];
		var userInGroup = {};

		var searchText = typeof(params.searchText) != 'undefined'? params.searchText: this.BXIM.messenger.contactListSearchText;
		var activeSearch = !(searchText != null && searchText.length == 0);
		var extraEnable =  typeof(params.extra) != 'undefined'? params.extra: true;
		var groupOpen =  typeof(params.groupOpen) != 'undefined'? params.groupOpen: 'auto';
		var viewGroup =  typeof(params.viewGroup) != 'undefined'? params.viewGroup: activeSearch || !this.BXIM.settings? false: this.BXIM.settings.viewGroup;
		var viewOffline =  typeof(params.viewOffline) != 'undefined'? params.viewOffline: activeSearch || !this.BXIM.settings? true: this.BXIM.settings.viewOffline;
		var viewChat =  typeof(params.viewChat) != 'undefined'? params.viewChat: true;
		var viewOfflineWithPhones =  typeof(params.viewOfflineWithPhones) != 'undefined'? params.viewOfflineWithPhones: false;

		if (this.isMobile())
		{
			BitrixMobile.LazyLoad.clearImages();
		}

		var exceptUsers = {};
		if (typeof(params.exceptUsers) != 'undefined')
		{
			for (var i = 0; i < params.exceptUsers.length; i++)
				exceptUsers[params.exceptUsers[i]] = true;
		}

		if (viewGroup)
		{
			groupsTmp = this.BXIM.messenger.groups;
			userInGroup = this.BXIM.messenger.userInGroup;
		}
		else
		{
			groupsTmp = this.BXIM.messenger.woGroups;
			userInGroup = this.BXIM.messenger.woUserInGroup;
		}

		var groupCount = 0;
		for (var i in groupsTmp)
			groupCount++;

		if (groupCount <= 0 && !this.BXIM.messenger.contactListLoad)
		{
			items.push(BX.create("div", {
				props : { className: "bx-messenger-cl-item-load"},
				html : BX.message('IM_CL_LOAD')
			}));

			this.contactListGetFromServer();
			return items;
		}
		var arSearch = [];
		var arSearchAlt = [];
		if (activeSearch)
		{
			searchText = searchText+'';
			if (!this.isMobile() && this.BXIM.language=='ru' && BX.correctText)
			{
				var correctText = BX.correctText(searchText);
				if (correctText != searchText)
				{
					arSearchAlt = correctText.split(" ");
				}
			}
			arSearch = searchText.split(" ");
		}

		groups[0] = {'id': 0, 'name': BX.message('IM_M_CL_UNREAD'), 'status':'open'};
		for (var i in this.BXIM.messenger.unreadMessage) unreadUsers.push(i);
		userInGroup[0] = {'id':0, 'users': unreadUsers};
		for (var i in groupsTmp)
		{
			if (i != 'last' && i != 0 )
				groups[i] = groupsTmp[i];
		}
		if (viewChat)
		{
			var groupChat = [];
			for (var i in this.BXIM.messenger.chat)
			{
				if (!activeSearch && this.BXIM.messenger.chat[i].style == 'call')
					continue;
				
				groupChat.push(i);
			}

			groupChat.sort(function(a, b) {i = this.BXIM.messenger.chat[a].name; ii = this.BXIM.messenger.chat[b].name; if (i < ii) { return -1; } else if (i > ii) { return 1;}else{ return 0;}});

			userInGroup['chat'] = {'id':'chat', 'users': groupChat, 'isChat': true};
		}

		for (var i in groups)
		{
			var group = groups[i];
			if (typeof(group) == 'undefined' || !group.name || !BX.type.isNotEmptyString(group.name))
				continue;

			var userItems = [];
			var userDrowedInGroup = {};
			if (userInGroup[i] && !userInGroup[i].isChat)
			{
				for (var j = 0; j < userInGroup[i].users.length; j++)
				{
					var user = this.BXIM.messenger.users[userInGroup[i].users[j]];
					if (typeof(user) == 'undefined' || this.BXIM.userId == user.id || typeof(user.name) == 'undefined' || exceptUsers[user.id] || userDrowedInGroup[i+'_'+user.id])
						continue;

					userDrowedInGroup[i+'_'+user.id] = true;

					if (activeSearch)
					{
						var userSearchString = user.name.toLowerCase()+(user.workPosition? (" "+user.workPosition).toLowerCase(): "");
						var skipUser = false;
						for (var s = 0; s < arSearch.length; s++)
							if (userSearchString.indexOf(arSearch[s].toLowerCase()) < 0)
								skipUser = true;

						if (skipUser)
						{
							for (var s = 0; s < arSearchAlt.length; s++)
							{
								if (userSearchString.indexOf(arSearchAlt[s].toLowerCase()) < 0)
									skipUser = true;
								else
									skipUser = false;
							}
						}

						if (skipUser)
							continue;
					}

					var newMessage = '';
					var newMessageCount = '';
					if (extraEnable && this.BXIM.messenger.unreadMessage[user.id] && this.BXIM.messenger.unreadMessage[user.id].length>0)
					{
						newMessage = 'bx-messenger-cl-status-new-message';
						newMessageCount = '<span class="bx-messenger-cl-count-digit">'+(this.BXIM.messenger.unreadMessage[user.id].length<100? this.BXIM.messenger.unreadMessage[user.id].length: '99+')+'</span>';
					}

					var writingMessage = '';
					if (extraEnable && this.countWriting(user.id))
						writingMessage = 'bx-messenger-cl-status-writing';

					var userOnlineStatus = this.getUserStatus(user.id);
					if (viewOfflineWithPhones && user.phoneDevice && userOnlineStatus == "offline")
					{
						userOnlineStatus = 'online';
					}
					if (!activeSearch && i != 'last' && viewOffline == false && userOnlineStatus == "offline" && newMessage == '')
						continue;

					if (this.isMobile())
					{
						var lazyUserId = 'mobile-cl-avatar-id-'+user.id+'-g-'+i;
						var src = 'id="'+lazyUserId+'" src="'+this.BXIM.pathToBlankImage+'" data-src="'+user.avatar+'"';
						BitrixMobile.LazyLoad.registerImage(lazyUserId);
					}
					else
					{
						var src = '_src="'+user.avatar+'" src="'+this.BXIM.pathToBlankImage+'"';
						if (activeSearch || (group.status == "open" && groupOpen == 'auto') || groupOpen == true)
							src = 'src="'+user.avatar+'" _src="'+this.BXIM.pathToBlankImage+'"';
					}

					userItems.push(BX.create("a", {
						props : { className: "bx-messenger-cl-item bx-messenger-cl-id-"+user.id+" bx-messenger-cl-status-" +userOnlineStatus+ " " +newMessage+" "+writingMessage },
						attrs : { href:'#user'+user.id, 'data-userId' : user.id, 'data-name' : user.name, 'data-status' : userOnlineStatus, 'data-avatar' : user.avatar },
						html :  '<span class="bx-messenger-cl-count">'+newMessageCount+'</span>'+
								'<span class="bx-messenger-cl-avatar"><img class="bx-messenger-cl-avatar-img'+(this.isBlankAvatar(user.avatar)? " bx-messenger-cl-avatar-img-default": "")+'" '+src+'><span class="bx-messenger-cl-status"></span></span>'+
								'<span class="bx-messenger-cl-user">'+
									'<div class="bx-messenger-cl-user-title'+(user.extranet? " bx-messenger-user-extranet": "")+'">'+(user.nameList? user.nameList: user.name)+'</div>'+
									'<div class="bx-messenger-cl-user-desc">'+this.getUserPosition(user.id)+'</div>'+
								'</span>'
					}));
				}
				if (userItems.length > 0)
				{
					items.push(BX.create("div", {
						attrs : { 'data-groupId-wrap' : group.id },
						props : { className: "bx-messenger-cl-group" +  (activeSearch || (group.status == "open" && groupOpen == 'auto') || groupOpen == true ? " bx-messenger-cl-group-open" : "")},
						children : [
							BX.create("div", {props : { className: "bx-messenger-cl-group-title"}, attrs : { 'data-groupId' : group.id, title : group.name }, html : group.name}),
							BX.create("span", {props : { className: "bx-messenger-cl-group-wrapper"}, children : userItems})
						]
					}));
				}
			}
			else if (userInGroup[i] && userInGroup[i].isChat)
			{
				for (var j = 0; j < userInGroup[i].users.length; j++)
				{
					var chat = this.BXIM.messenger.chat[userInGroup[i].users[j]];
					if (typeof (chat) == 'undefined' || typeof(chat.name) == 'undefined' || userDrowedInGroup[i+'_chat'+chat.id])
						continue;

					userDrowedInGroup[i+'_chat'+chat.id] = true;

					if (activeSearch)
					{
						var skipUser = false;
						for (var s = 0; s < arSearch.length; s++)
							if (chat.name.toLowerCase().indexOf(arSearch[s].toLowerCase()) < 0)
								skipUser = true;

						if (skipUser)
						{
							for (var s = 0; s < arSearchAlt.length; s++)
							{
								if (chat.name.toLowerCase().indexOf(arSearchAlt[s].toLowerCase()) < 0)
									skipUser = true;
								else
									skipUser = false;
							}
						}

						if (skipUser)
							continue;
					}

					var writingMessage = '';
					if (extraEnable && this.countWriting('chat'+chat.id))
						writingMessage = 'bx-messenger-cl-status-writing';

					var newMessage = '';
					var newMessageCount = '';
					if (extraEnable && this.BXIM.messenger.unreadMessage['chat'+chat.id] && this.BXIM.messenger.unreadMessage['chat'+chat.id].length>0)
					{
						newMessage = 'bx-messenger-cl-status-new-message';
						newMessageCount = '<span class="bx-messenger-cl-count-digit">'+(this.BXIM.messenger.unreadMessage['chat'+chat.id].length<100? this.BXIM.messenger.unreadMessage['chat'+chat.id].length: '99+')+'</span>';
					}

					if (this.isMobile())
					{
						var lazyUserId = 'mobile-cl-avatar-id-chat-'+chat.id+'-g-'+i;
						var src = 'id="'+lazyUserId+'" src="'+this.BXIM.pathToBlankImage+'" data-src="'+chat.avatar+'"';
						BitrixMobile.LazyLoad.registerImage(lazyUserId);
					}
					else
					{
						var src = '_src="'+chat.avatar+'" src="'+this.BXIM.pathToBlankImage+'"';
						if (activeSearch || (group.status == "open" && groupOpen == 'auto') || groupOpen == true)
							src = 'src="'+chat.avatar+'" _src="'+this.BXIM.pathToBlankImage+'"';
					}

					userItems.push(BX.create("span", {
						props : { className: "bx-messenger-cl-item bx-messenger-cl-id-chat"+chat.id+" bx-messenger-cl-status-online "+newMessage+" "+writingMessage},
						attrs : { 'data-userId' : 'chat'+chat.id,  'data-userIsChat' : 'Y', 'data-name' : chat.name, 'data-status' : 'online', 'data-avatar' : chat.avatar },
						html :  '<span class="bx-messenger-cl-count">'+newMessageCount+'</span>'+
								'<span class="bx-messenger-cl-avatar bx-messenger-cl-avatar-'+chat.style+'"><img class="bx-messenger-cl-avatar-img'+(this.isBlankAvatar(chat.avatar)? " bx-messenger-cl-avatar-img-default": "")+'" '+src+'><span class="bx-messenger-cl-status"></span></span>'+
								'<span class="bx-messenger-cl-user">'+
									'<div class="bx-messenger-cl-user-title">'+chat.name+'</div>'+
									'<div class="bx-messenger-cl-user-desc">'+(chat.style == 'call'? BX.message('IM_CL_PHONE'): BX.message('IM_CL_CHAT'))+'</div>'+
								'</span>'
					}));
				}
				if (userItems.length > 0)
				{
					items.push(BX.create("div", {
						attrs : { 'data-groupId-wrap' : group.id },
						props : { className: "bx-messenger-cl-group" +  (activeSearch || (group.status == "open" && groupOpen == 'auto') || groupOpen == true ? " bx-messenger-cl-group-open" : "")},
						children : [
							BX.create("div", {props : { className: "bx-messenger-cl-group-title"}, attrs : { 'data-groupId' : group.id, title : group.name }, html : group.name}),
							BX.create("span", {props : { className: "bx-messenger-cl-group-wrapper"}, children : userItems})
						]
					}));
				}
			}
		}

		// search by groups
		if (this.BXIM.bitrixIntranet && activeSearch)
		{
			var foundGroup = {};
			for (var i in  this.BXIM.messenger.groups)
			{
				var skipGroup = true;
				for (var s = 0; s < arSearch.length; s++)
					if (this.BXIM.messenger.groups[i].name && this.BXIM.messenger.groups[i].name.toLowerCase().indexOf(arSearch[s].toLowerCase()) >= 0)
						skipGroup = false;

				if (skipGroup)
				{
					for (var s = 0; s < arSearchAlt.length; s++)
					{
						if (this.BXIM.messenger.groups[i].name && this.BXIM.messenger.groups[i].name.toLowerCase().indexOf(arSearchAlt[s].toLowerCase()) >= 0)
							skipGroup = false;
					}
				}

				if (!skipGroup)
				{
					foundGroup[i] = {'id': i, 'name': this.BXIM.messenger.groups[i].name, 'status':'close'};
				}
			}

			for (var i in foundGroup)
			{
				var group = foundGroup[i];
				if (typeof(group) == 'undefined' || !group.name || !BX.type.isNotEmptyString(group.name))
					continue;

				var userDrowedInGroup = {};
				var userItems = [];
				if (this.BXIM.messenger.userInGroup[i] && !this.BXIM.messenger.userInGroup[i].isChat)
				{
					for (var j = 0; j < this.BXIM.messenger.userInGroup[i].users.length; j++)
					{
						var user = this.BXIM.messenger.users[this.BXIM.messenger.userInGroup[i].users[j]];
						if (typeof(user) == 'undefined' || this.BXIM.userId == user.id || typeof(user.name) == 'undefined' || exceptUsers[user.id] || userDrowedInGroup[i+'_'+user.id])
							continue;

						userDrowedInGroup[i+'_'+user.id] = true;

						var newMessage = '';
						var newMessageCount = '';
						if (extraEnable && this.BXIM.messenger.unreadMessage[user.id] && this.BXIM.messenger.unreadMessage[user.id].length>0)
						{
							newMessage = 'bx-messenger-cl-status-new-message';
							newMessageCount = '<span class="bx-messenger-cl-count-digit">'+(this.BXIM.messenger.unreadMessage[user.id].length<100? this.BXIM.messenger.unreadMessage[user.id].length: '99+')+'</span>';
						}

						var writingMessage = '';
						if (extraEnable && this.countWriting(user.id))
							writingMessage = 'bx-messenger-cl-status-writing';

						var userOnlineStatus = this.getUserStatus(user.id);
						if (viewOfflineWithPhones && user.phoneDevice && userOnlineStatus == "offline")
						{
							userOnlineStatus = 'online';
						}
						if (i != 'last' && viewOffline == false && userOnlineStatus == "offline" && newMessage == '')
							continue;

						if (this.isMobile())
						{
							var lazyUserId = 'mobile-cl-avatar-id-'+user.id+'-g-'+i;
							var src = 'id="'+lazyUserId+'" src="'+this.BXIM.pathToBlankImage+'" data-src="'+user.avatar+'"';
							BitrixMobile.LazyLoad.registerImage(lazyUserId);
						}
						else
						{
							var src = '_src="'+user.avatar+'" src="'+this.BXIM.pathToBlankImage+'"';
							if (activeSearch || (group.status == "open" && groupOpen == 'auto') || groupOpen == true)
								src = 'src="'+user.avatar+'" _src="'+this.BXIM.pathToBlankImage+'"';
						}

						userItems.push(BX.create("span", {
							props : { className: "bx-messenger-cl-item bx-messenger-cl-id-"+user.id+" bx-messenger-cl-status-" +userOnlineStatus+ " " +newMessage+" "+writingMessage },
							attrs : { 'data-userId' : user.id, 'data-name' : user.name, 'data-status' : userOnlineStatus, 'data-avatar' : user.avatar },
							html :  '<span class="bx-messenger-cl-count">'+newMessageCount+'</span>'+
									'<span class="bx-messenger-cl-avatar"><img class="bx-messenger-cl-avatar-img'+(this.isBlankAvatar(user.avatar)? " bx-messenger-cl-avatar-img-default": "")+'" '+src+'><span class="bx-messenger-cl-status"></span></span>'+
									'<span class="bx-messenger-cl-user">'+
										'<div class="bx-messenger-cl-user-title'+(user.extranet? " bx-messenger-user-extranet": "")+'">'+(user.nameList? user.nameList: user.name)+'</div>'+
										'<div class="bx-messenger-cl-user-desc">'+this.getUserPosition(user.id)+'</div>'+
									'</span>'
						}));
					}
					if (userItems.length > 0)
					{
						items.push(BX.create("div", {
							attrs : { 'data-groupId-wrap' : group.id },
							props : { className: "bx-messenger-cl-group"+(groupOpen == true ? " bx-messenger-cl-group-open" : "") },
							children : [
								BX.create("div", {props : { className: "bx-messenger-cl-group-title"}, attrs : { 'data-groupId' : group.id, title : group.name }, html : group.name}),
								BX.create("span", {props : { className: "bx-messenger-cl-group-wrapper"}, children : userItems})
							]
						}));
					}
				}
			}
		}

		if (items.length <= 0)
		{
			items.push(BX.create("div", {
				props : { className: "bx-messenger-cl-item-empty"},
				html :  BX.message('IM_M_CL_EMPTY')
			}));
		}
		return items;
	};

	BX.MessengerCommon.prototype.contactListClickItem = function(e)
	{
		this.BXIM.messenger.closeMenuPopup();
		if (this.BXIM.messenger.popupContactListSearchInput.value != '')
		{
			this.BXIM.messenger.popupContactListSearchInput.value = '';
			this.BXIM.messenger.contactListSearchText = '';
			BX.localStorage.set('mns', this.BXIM.messenger.contactListSearchText, 5);
			if (this.BXIM.messenger.recentListReturn)
			{
				this.BXIM.messenger.recentList = true;
				this.BXIM.messenger.contactList = false;
			}
			this.userListRedraw();
		}
		if (this.isMobile())
		{
			this.BXIM.messenger.openMessenger(BX.proxy_context.getAttribute('data-userId'), BX.proxy_context);
		}
		else
		{
			this.BXIM.messenger.openMessenger(BX.proxy_context.getAttribute('data-userId'));
		}
		return BX.PreventDefault(e);
	}

	BX.MessengerCommon.prototype.contactListToggleGroup = function()
	{
		var status = '';

		var wrapper = BX.findNextSibling(BX.proxy_context, {className: 'bx-messenger-cl-group-wrapper'});
		if (wrapper.childNodes.length > 0)
		{
			var avatarNodes = BX.findChildrenByClassName(wrapper, "bx-messenger-cl-avatar-img");
			if (BX.hasClass(BX.proxy_context.parentNode, 'bx-messenger-cl-group-open'))
			{
				status = 'close';
				BX.removeClass(BX.proxy_context.parentNode, 'bx-messenger-cl-group-open');
				if (!this.isMobile() && avatarNodes)
				{
					for (var i = 0; i < avatarNodes.length; i++)
					{
						avatarNodes[i].setAttribute('_src', avatarNodes[i].src);
						avatarNodes[i].src = this.BXIM.pathToBlankImage;
					}
				}
			}
			else
			{
				status = 'open';
				BX.addClass(BX.proxy_context.parentNode, 'bx-messenger-cl-group-open');
				if (!this.isMobile() && avatarNodes)
				{
					for (var i = 0; i < avatarNodes.length; i++)
					{
						avatarNodes[i].src = avatarNodes[i].getAttribute('_src');
						avatarNodes[i].setAttribute('_src', this.BXIM.pathToBlankImage);
					}
				}
			}
		}
		else
		{
			if (BX.hasClass(BX.proxy_context.parentNode, 'bx-messenger-cl-group-open'))
			{
				status = 'close';
				BX.removeClass(BX.proxy_context.parentNode, 'bx-messenger-cl-group-open');
			}
			else
			{
				status = 'open';
				BX.addClass(BX.proxy_context.parentNode, 'bx-messenger-cl-group-open');
			}
		}

		var id = BX.proxy_context.getAttribute('data-groupId');
		var viewGroup = this.BXIM.messenger.contactListSearchText != null && this.BXIM.messenger.contactListSearchText.length > 0? false: this.BXIM.messenger.BXIM.settings.viewGroup;
		if (viewGroup)
			this.BXIM.messenger.groups[id].status = status;
		else if (this.BXIM.messenger.woGroups[id])
			this.BXIM.messenger.woGroups[id].status = status;

		BX.userOptions.save('IM', 'groupStatus', id, status);
		BX.localStorage.set('mgp', {'id': id, 'status': status}, 5);
	}

	BX.MessengerCommon.prototype.contactListGetFromServer = function()
	{
		if (this.BXIM.messenger.contactListLoad)
			return false;

		this.BXIM.messenger.contactListLoad = true;
		BX.ajax({
			url: this.BXIM.pathToAjax+'?CONTACT_LIST&V='+this.BXIM.revision,
			method: 'POST',
			dataType: 'json',
			skipAuthCheck: true,
			timeout: 30,
			data: {'IM_CONTACT_LIST' : 'Y', 'IM_AJAX_CALL' : 'Y', 'DESKTOP' : (!this.isMobile() && this.BXIM.desktop && this.BXIM.desktop.ready()? 'Y': 'N'), 'sessid': BX.bitrix_sessid()},
			onsuccess: BX.delegate(function(data)
			{
				if (data && data.BITRIX_SESSID)
				{
					BX.message({'bitrix_sessid': data.BITRIX_SESSID});
				}
				if (data.ERROR == '')
				{
					for (var i in data.USERS)
						this.BXIM.messenger.users[i] = data.USERS[i];

					for (var i in data.GROUPS)
						this.BXIM.messenger.groups[i] = data.GROUPS[i];

					for (var i in data.CHATS)
					{
						if (this.BXIM.messenger.chat[i] && this.BXIM.messenger.chat[i].fake)
							data.CHATS[i].fake = true;
						else if (!this.BXIM.messenger.chat[i])
							data.CHATS[i].fake = true;

						this.BXIM.messenger.chat[i] = data.CHATS[i];
					}

					for (var i in data.USER_IN_GROUP)
					{
						if (typeof(this.BXIM.messenger.userInGroup[i]) == 'undefined')
						{
							this.BXIM.messenger.userInGroup[i] = data.USER_IN_GROUP[i];
						}
						else
						{
							for (var j = 0; j < data.USER_IN_GROUP[i].users.length; j++)
								this.BXIM.messenger.userInGroup[i].users.push(data.USER_IN_GROUP[i].users[j]);

							this.BXIM.messenger.userInGroup[i].users = BX.util.array_unique(this.BXIM.messenger.userInGroup[i].users)
						}
					}

					for (var i in data.WO_GROUPS)
						this.BXIM.messenger.woGroups[i] = data.WO_GROUPS[i];

					for (var i in data.WO_USER_IN_GROUP)
					{
						if (typeof(this.BXIM.messenger.woUserInGroup[i]) == 'undefined')
						{
							this.BXIM.messenger.woUserInGroup[i] = data.WO_USER_IN_GROUP[i];
						}
						else
						{
							for (var j = 0; j < data.WO_USER_IN_GROUP[i].users.length; j++)
								this.BXIM.messenger.woUserInGroup[i].users.push(data.WO_USER_IN_GROUP[i].users[j]);

							this.BXIM.messenger.woUserInGroup[i].users = BX.util.array_unique(this.BXIM.messenger.woUserInGroup[i].users)
						}
					}

					this.userListRedraw();

					if (!this.isMobile())
					{
						this.BXIM.messenger.dialogStatusRedraw();

						if (this.BXIM.messenger.popupChatDialogContactListElements != null)
						{
							this.contactListPrepareSearch('popupChatDialogContactListElements', this.BXIM.messenger.popupChatDialogContactListElements, this.BXIM.messenger.popupChatDialogContactListSearch.value, {'viewOffline': true, 'viewChat': false});
						}
						if (this.BXIM.webrtc.popupTransferDialogContactListElements != null)
						{
							this.contactListPrepareSearch('popupTransferDialogContactListElements', this.BXIM.webrtc.popupTransferDialogContactListElements, this.BXIM.webrtc.popupTransferDialogContactListSearch.value, {'viewChat': false});
						}
					}
				}
				else
				{
					this.BXIM.messenger.contactListLoad = false;
					if (data.ERROR == 'SESSION_ERROR' && this.BXIM.messenger.sendAjaxTry < 2)
					{
						this.BXIM.messenger.sendAjaxTry++;
						setTimeout(BX.delegate(this.contactListGetFromServer, this), 2000);
						BX.onCustomEvent(window, 'onImError', [data.ERROR, data.BITRIX_SESSID]);
					}
					else if (data.ERROR == 'AUTHORIZE_ERROR')
					{
						this.BXIM.messenger.sendAjaxTry++;
						if (this.BXIM.desktop && this.BXIM.desktop.ready())
						{
							setTimeout(BX.delegate(this.contactListGetFromServer, this), 10000);
						}
						BX.onCustomEvent(window, 'onImError', [data.ERROR]);
					}
				}
			}, this),
			onfailure: BX.delegate(function(){
				this.BXIM.messenger.sendAjaxTry = 0;
				this.BXIM.messenger.contactListLoad = false;
			}, this)
		});
	};

	BX.MessengerCommon.prototype.contactListSearchClear = function(e)
	{
		this.BXIM.messenger.popupContactListSearchInput.value = '';
		this.BXIM.messenger.contactListSearchText = BX.util.trim(this.BXIM.messenger.popupContactListSearchInput.value);
		BX.localStorage.set('mns', this.BXIM.messenger.contactListSearchText, 5);

		if (this.isMobile())
		{
			BX.removeClass(this.BXIM.messenger.popupContactListSearchInput.parentNode, 'bx-messenger-input-wrap-active');
		}

		if (this.BXIM.messenger.recentListReturn)
		{
			this.BXIM.messenger.recentList = true;
			this.BXIM.messenger.contactList = false;
		}
		this.userListRedraw();

		return BX.PreventDefault(e);
	}

	BX.MessengerCommon.prototype.contactListSearch = function(event)
	{
		if (event.keyCode == 16 || event.keyCode == 18 || event.keyCode == 20 || event.keyCode == 244 || event.keyCode == 91) // 224, 17
			return false;

		this.BXIM.messenger.recentList = false;
		this.BXIM.messenger.contactList = true;

		if (this.isMobile())
		{
			if (!app.enableInVersion(10))
			{
				setTimeout(function(){
					document.body.scrollTop = 0;
				}, 100);
			}
		}
		else
		{
			if (event.keyCode == 27)
			{
				if (this.BXIM.messenger.contactListSearchText <= 0)
				{
					this.BXIM.messenger.popupContactListSearchInput.value = "";
					if (!this.isMobile() && this.BXIM.messenger.popupMessenger && !this.BXIM.messenger.desktop.ready() && !this.BXIM.messenger.webrtc.callInit)
					{
						this.BXIM.messenger.popupMessenger.destroy();
					}
				}
				else
				{
					this.BXIM.messenger.popupContactListSearchInput.value = "";
					this.BXIM.messenger.popupMessengerTextarea.focus();
				}
			}
			if (event.keyCode == 13)
			{
				this.BXIM.messenger.popupContactListSearchInput.value = '';
				var item = BX.findChildByClassName(this.BXIM.messenger.popupContactListElementsWrap, "bx-messenger-cl-item");
				if (item)
				{
					this.BXIM.messenger.BXIM.openMessenger(item.getAttribute('data-userid'));
				}
			}
		}

		this.BXIM.messenger.contactListSearchText = BX.util.trim(this.BXIM.messenger.popupContactListSearchInput.value);

		if (!this.isMobile())
		{
			BX.localStorage.set('mns', this.BXIM.messenger.contactListSearchText, 5);
		}

		if (this.BXIM.messenger.contactListSearchText == '')
		{
			if (this.BXIM.messenger.recentListReturn)
			{
				this.BXIM.messenger.recentList = true;
				this.BXIM.messenger.contactList = false;
			}
			if (this.isMobile())
			{
				BX.removeClass(this.BXIM.messenger.popupContactListSearchInput.parentNode, 'bx-messenger-input-wrap-active');
			}
		}
		else
		{
			if (this.isMobile())
			{
				BX.addClass(this.BXIM.messenger.popupContactListSearchInput.parentNode, 'bx-messenger-input-wrap-active');
			}
			if (this.BXIM.messenger.realSearch)
			{
				clearTimeout(this.BXIM.messenger.contactListSearchTimeout);
				this.BXIM.messenger.contactListSearchTimeout = setTimeout(BX.delegate(function(){
					if (this.BXIM.messenger.contactListSearchText.length <= 3)
						return false;

					BX.ajax({
						url: this.BXIM.pathToAjax+'?CONTACT_LIST_SEARCH&V='+this.BXIM.revision,
						method: 'POST',
						dataType: 'json',
						timeout: 30,
						data: {'IM_CONTACT_LIST_SEARCH' : 'Y', 'SEARCH' : this.BXIM.messenger.contactListSearchText, 'IM_AJAX_CALL' : 'Y', 'sessid': BX.bitrix_sessid()},
						onsuccess: BX.delegate(function(data){
							if (!this.BXIM.messenger.userInGroup['other'])
								this.BXIM.messenger.userInGroup['other'] = {'id':'other', 'users': []};
							if (!this.BXIM.messenger.woUserInGroup['other'])
								this.BXIM.messenger.woUserInGroup['other'] = {'id':'other', 'users': []};

							var _users = BX.clone(this.BXIM.messenger.userInGroup['other']['users']);
							var _woUsers = BX.clone(this.BXIM.messenger.woUserInGroup['other']['users']);
							for (var i in data.USERS)
							{
								this.BXIM.messenger.users[i] = data.USERS[i];
								this.BXIM.messenger.userInGroup['other']['users'].push(i);
								this.BXIM.messenger.woUserInGroup['other']['users'].push(i);
							}

							if (this.BXIM.messenger.contactList)
								this.contactListRedraw({FORCE: true});

							this.BXIM.messenger.userInGroup['other']['users'] = _users;
							this.BXIM.messenger.woUserInGroup['other']['users'] = _woUsers;

						}, this),
						onfailure: function(data)	{}
					});
				}, this), 1000);
			}
		}
		this.userListRedraw();
	};

	/* Recent list */
	BX.MessengerCommon.prototype.recentListRedraw = function(params)
	{
		clearTimeout(this.BXIM.messenger.redrawRecentListTimeout);
		if (this.MobileActionNotEqual('RECENT'))
			return false;

		if (!this.isMobile())
		{
			if (this.BXIM.messenger.popupMessenger == null)
				return false;

			this.BXIM.messenger.recentList = true;
			BX.addClass(this.BXIM.messenger.recentListTab, 'bx-messenger-cl-switcher-tab-active');
			this.BXIM.messenger.contactList = false;
			BX.removeClass(this.BXIM.messenger.contactListTab, 'bx-messenger-cl-switcher-tab-active');
		}

		if (this.BXIM.messenger.contactListSearchText != null && this.BXIM.messenger.contactListSearchText.length == 0)
		{
			this.BXIM.messenger.recentListReturn = true;
		}
		else
		{
			this.BXIM.messenger.contactListSearchText = '';
			this.BXIM.messenger.popupContactListSearchInput.value = '';
		}

		if (this.BXIM.messenger.redrawContactListTimeout['contactList'])
			clearTimeout(this.BXIM.messenger.redrawContactListTimeout['contactList']);

		if (!this.isMobile() && this.BXIM.messenger.popupPopupMenu != null)
			this.BXIM.messenger.popupPopupMenu.close();

		this.BXIM.messenger.popupContactListElementsWrap.innerHTML = '';
		BX.adjust(this.BXIM.messenger.popupContactListElementsWrap, {children: this.recentListPrepare(params)});

		if (this.isMobile())
		{
			BitrixMobile.LazyLoad.showImages();
		}
	};

	BX.MessengerCommon.prototype.recentListPrepare = function(params)
	{
		var items = [];
		var groups = {};
		params = typeof(params) == 'object'? params: {};

		var showOnlyChat = params.showOnlyChat;

		if (!this.BXIM.messenger.recentListLoad)
		{
			items.push(BX.create("div", {
				props : { className: "bx-messenger-cl-item-load"},
				html : BX.message('IM_CL_LOAD')
			}));

			this.recentListGetFromServer();
			return items;
		}

		if (this.isMobile())
		{
			BitrixMobile.LazyLoad.clearImages();
		}

		this.BXIM.messenger.recent.sort(function(i, ii) {var i1 = parseInt(i.date); var i2 = parseInt(ii.date); if (i1 > i2) { return -1; } else if (i1 < i2) { return 1;} else{ if (i > ii) { return -1; } else if (i < ii) { return 1;}else{ return 0;}}});
		this.BXIM.messenger.recentListIndex = [];
		for (var i = 0; i < this.BXIM.messenger.recent.length; i++)
		{
			if (typeof(this.BXIM.messenger.recent[i].userIsChat) == 'undefined')
				this.BXIM.messenger.recent[i].userIsChat = this.BXIM.messenger.recent[i].recipientId.toString().substr(0,4) == 'chat';

			var item = BX.clone(this.BXIM.messenger.recent[i]);
			var chatStatus = '';
			if (item.userIsChat)
			{
				user = this.BXIM.messenger.chat[item.userId.toString().substr(4)];
				if (typeof(user) == 'undefined' || typeof(user.name) == 'undefined')
					continue;
				var userId = 'chat'+user.id;
				//if (this.BXIM.messenger.userChatBlockStatus[this.BXIM.messenger.currentTab.toString().substr(4)] && this.BXIM.messenger.userChatBlockStatus[this.BXIM.messenger.currentTab.toString().substr(4)][this.BXIM.userId] == 'Y')
				//{
				//	chatStatus = 'bx-messenger-cl-notify-blocked';
				//}
			}
			else if (!showOnlyChat)
			{
				var user = this.BXIM.messenger.users[item.userId];
				if (typeof(user) == 'undefined' || this.BXIM.userId == user.id || typeof(user.name) == 'undefined')
					continue;

				var userId = user.id;
			}
			else
			{
				continue;
			}

			if (parseInt(item.date) > 0)
			{
				item.date = this.formatDate(item.date, this.getDateFormatType('RECENT_TITLE'));
				if (!groups[item.date])
				{
					groups[item.date] = true;
					items.push(BX.create("div", {props : { className: "bx-messenger-recent-group"}, children : [
						BX.create("span", {props : { className: "bx-messenger-recent-group-title"+(this.BXIM.language == 'ru'? ' bx-messenger-lowercase': '')}, html : item.date})
					]}));
				}
			}
			else
			{
				if (!groups['never'])
				{
					groups['never'] = true;
					items.push(BX.create("div", {props : { className: "bx-messenger-recent-group"}, children : [
						BX.create("span", {props : { className: "bx-messenger-recent-group-title"}, html : BX.message('IM_RESENT_NEVER')})
					]}));
				}
			}

			if (this.BXIM.messenger.message[item.id] && this.BXIM.messenger.message[item.id].text)
			{
				item.text = this.BXIM.messenger.message[item.id].text;
			}
			if (!item.text && item.params && item.params['FILE_ID'].length > 0)
			{
				item.text = '['+BX.message('IM_F_FILE')+']';
			}

			var newMessage = '';
			var newMessageCount = '';
			if (this.BXIM.messenger.unreadMessage[userId] && this.BXIM.messenger.unreadMessage[userId].length>0)
			{
				newMessage = 'bx-messenger-cl-status-new-message';
				newMessageCount = '<span class="bx-messenger-cl-count-digit">'+(this.BXIM.messenger.unreadMessage[userId].length<100? this.BXIM.messenger.unreadMessage[userId].length: '99+')+'</span>';
			}

			var writingMessage = '';
			var directionIcon = '';

			if (this.countWriting(userId))
				writingMessage = 'bx-messenger-cl-status-writing';

			if (item.senderId == this.BXIM.userId)
				directionIcon = '<span class="bx-messenger-cl-user-reply"></span>';

			if (!user.avatar)
				user.avatar = this.BXIM.pathToBlankImage;

			item.text = this.prepareText(item.text);
			item.text = item.text.replace(/<img.*?data-code="([^"]*)".*?>/ig, '$1');
			item.text = item.text.replace(/<s>([^"]*)<\/s>/ig, '');
			item.text = item.text.replace('<br />', ' ').replace(/<\/?[^>]+>/gi, '').replace(/------------------------------------------------------(.*?)------------------------------------------------------/gmi, " ["+BX.message("IM_M_QUOTE_BLOCK")+"] ");

			var avatarId = '';
			var avatarLink = user.avatar;
			var mobileItemActive = '';
			if (this.isMobile())
			{
				if (this.BXIM.messenger.currentTab == userId)
				{
					mobileItemActive = 'bx-messenger-cl-item-active ';
				}
				var lazyUserId = 'mobile-rc-avatar-id-'+user.id;
				avatarId = 'id="'+lazyUserId+'" data-src="'+user.avatar+'"';
				avatarLink = this.BXIM.pathToBlankImage;
				BitrixMobile.LazyLoad.registerImage(lazyUserId);
			}

			items.push(BX.create("span", {
				props : { className: "bx-messenger-cl-item  bx-messenger-cl-id-"+(item.userIsChat? 'chat':'')+user.id+" "+mobileItemActive+(item.userIsChat? "bx-messenger-cl-item-chat "+newMessage+" "+writingMessage+" "+chatStatus: "bx-messenger-cl-status-" +this.getUserStatus(user.id)+ " " +newMessage+" "+writingMessage) },
				attrs : { 'data-userId' : userId, 'data-name' : user.name, 'data-status' : this.getUserStatus(user.id), 'data-avatar' : user.avatar, 'data-userIsChat' : item.userIsChat },
				html :  '<span class="bx-messenger-cl-count">'+newMessageCount+'</span>'+
						'<span class="bx-messenger-cl-avatar '+(item.userIsChat? 'bx-messenger-cl-avatar-'+user.style: '')+'"><img class="bx-messenger-cl-avatar-img'+(this.isBlankAvatar(user.avatar)? " bx-messenger-cl-avatar-img-default": "")+'" src="'+avatarLink+'" '+avatarId+'><span class="bx-messenger-cl-status"></span></span>'+
						'<span class="bx-messenger-cl-user">'+
							'<div class="bx-messenger-cl-user-title'+(user.extranet? " bx-messenger-user-extranet": "")+'">'+(user.nameList? user.nameList: user.name)+'</div>'+
							'<div class="bx-messenger-cl-user-desc">'+directionIcon+''+item.text+'</div>'+
						'</span>'
			}));

			this.BXIM.messenger.recentListIndex.push(userId);
		}

		if (items.length <= 0)
		{
			items.push(BX.create("div", {
				props : { className: "bx-messenger-cl-item-empty"},
				html :  BX.message('IM_M_CL_EMPTY')
			}));
		}
		return items;
	};

	BX.MessengerCommon.prototype.recentListAdd = function(params)
	{
		if (!params.skipDateCheck)
		{
			for (var i = 0; i < this.BXIM.messenger.recent.length; i++)
			{
				if (this.BXIM.messenger.recent[i].userId == params.userId && parseInt(this.BXIM.messenger.recent[i].date) > parseInt(params.date))
					return false;
			}
		}

		var newRecent = [];
		newRecent.push(params);

		for (var i = 0; i < this.BXIM.messenger.recent.length; i++)
			if (this.BXIM.messenger.recent[i].userId != params.userId)
				newRecent.push(this.BXIM.messenger.recent[i]);

		this.BXIM.messenger.recent = newRecent;

		if (this.BXIM.messenger.recentList)
		{
			if (this.isMobile())
			{
				clearTimeout(this.BXIM.messenger.redrawRecentListTimeout);
				this.BXIM.messenger.redrawRecentListTimeout = setTimeout(BX.delegate(function(){
					this.recentListRedraw();
				}, this), 300);
			}
			else
			{
				this.recentListRedraw();
			}
		}
	};

	BX.MessengerCommon.prototype.recentListHide = function(userId, sendAjax)
	{
		var newRecent = [];
		for (var i = 0; i < this.BXIM.messenger.recent.length; i++)
			if (this.BXIM.messenger.recent[i].userId != userId)
				newRecent.push(this.BXIM.messenger.recent[i]);

		this.BXIM.messenger.recent = newRecent;
		if (this.BXIM.messenger.recentList)
			this.recentListRedraw();

		if (!this.isMobile())
			BX.localStorage.set('mrlr', userId, 5);

		sendAjax = sendAjax != false;
		if (sendAjax)
		{
			BX.ajax({
				url: this.BXIM.pathToAjax+'?RECENT_HIDE&V='+this.BXIM.revision,
				method: 'POST',
				dataType: 'json',
				timeout: 60,
				data: {'IM_RECENT_HIDE' : 'Y', 'USER_ID' : userId, 'IM_AJAX_CALL' : 'Y', 'sessid': BX.bitrix_sessid()}
			});
			this.readMessage(userId, true, true);
		}
	};

	BX.MessengerCommon.prototype.recentListGetFromServer = function()
	{
		if (this.BXIM.messenger.recentListLoad)
			return false;

		this.BXIM.messenger.recentListLoad = true;
		BX.ajax({
			url: this.BXIM.pathToAjax+'?RECENT_LIST&V='+this.BXIM.revision,
			method: 'POST',
			dataType: 'json',
			skipAuthCheck: true,
			timeout: 30,
			data: {'IM_RECENT_LIST' : 'Y', 'IM_AJAX_CALL' : 'Y', 'sessid': BX.bitrix_sessid()},
			onsuccess: BX.delegate(function(data)
			{
				if (data && data.BITRIX_SESSID)
				{
					BX.message({'bitrix_sessid': data.BITRIX_SESSID});
				}
				if (data.ERROR == '')
				{
					this.BXIM.messenger.recent = [];
					for (var i in data.RECENT)
					{
						data.RECENT[i].date = parseInt(data.RECENT[i].date)-parseInt(BX.message('USER_TZ_OFFSET'));
						this.BXIM.messenger.recent.push(data.RECENT[i]);
					}

					var arRecent = false;
					for(var i in this.BXIM.messenger.unreadMessage)
					{
						for (var k = 0; k < this.BXIM.messenger.unreadMessage[i].length; k++)
						{
							if (!arRecent || arRecent.SEND_DATE <= this.BXIM.messenger.message[this.BXIM.messenger.unreadMessage[i][k]].date)
							{
								arRecent = {
									'ID': this.BXIM.messenger.message[this.BXIM.messenger.unreadMessage[i][k]].id,
									'SEND_DATE': this.BXIM.messenger.message[this.BXIM.messenger.unreadMessage[i][k]].date,
									'RECIPIENT_ID': this.BXIM.messenger.message[this.BXIM.messenger.unreadMessage[i][k]].recipientId,
									'SENDER_ID': this.BXIM.messenger.message[this.BXIM.messenger.unreadMessage[i][k]].senderId,
									'USER_ID': this.BXIM.messenger.message[this.BXIM.messenger.unreadMessage[i][k]].senderId,
									'SEND_MESSAGE': this.BXIM.messenger.message[this.BXIM.messenger.unreadMessage[i][k]].text,
									'PARAMS': this.BXIM.messenger.message[this.BXIM.messenger.unreadMessage[i][k]].params
								};
							}
						}
					}
					if (arRecent)
					{
						this.recentListAdd({
							'userId': arRecent.RECIPIENT_ID.toString().substr(0,4) == 'chat'? arRecent.RECIPIENT_ID: arRecent.USER_ID,
							'id': arRecent.ID,
							'date': arRecent.SEND_DATE,
							'recipientId': arRecent.RECIPIENT_ID,
							'senderId': arRecent.SENDER_ID,
							'text': arRecent.SEND_MESSAGE,
							'params': arRecent.PARAMS
						}, true);
					}

					for (var i in data.CHAT)
					{
						if (this.BXIM.messenger.chat[i] && this.BXIM.messenger.chat[i].fake)
							data.CHAT[i].fake = true;
						else if (!this.BXIM.messenger.chat[i])
							data.CHAT[i].fake = true;

						this.BXIM.messenger.chat[i] = data.CHAT[i];
					}

					for (var i in data.USERS)
						this.BXIM.messenger.users[i] = data.USERS[i];

					if (this.BXIM.messenger.recentList)
						this.recentListRedraw();

					this.BXIM.messenger.smile = data.SMILE;
					this.BXIM.messenger.smileSet = data.SMILE_SET;

					this.BXIM.settingsNotifyBlocked = data.NOTIFY_BLOCKED;
					if (!this.isMobile())
						this.BXIM.messenger.dialogStatusRedraw();
				}
				else
				{
					this.BXIM.messenger.recentListLoad = false;
					if (data.ERROR == 'SESSION_ERROR' && this.BXIM.messenger.sendAjaxTry < 2)
					{
						this.BXIM.messenger.sendAjaxTry++;
						setTimeout(BX.delegate(this.recentListGetFromServer, this), 2000);
						BX.onCustomEvent(window, 'onImError', [data.ERROR, data.BITRIX_SESSID]);
					}
					else if (data.ERROR == 'AUTHORIZE_ERROR')
					{
						this.BXIM.messenger.sendAjaxTry++;
						if (this.BXIM.desktop && this.BXIM.desktop.ready())
						{
							setTimeout(BX.delegate(this.recentListGetFromServer, this), 10000);
						}
						BX.onCustomEvent(window, 'onImError', [data.ERROR]);
					}
				}
			}, this),
			onfailure: BX.delegate(function(){
				this.BXIM.messenger.sendAjaxTry = 0;
				this.BXIM.messenger.recentListLoad = false;
			}, this)
		});
	};

	BX.MessengerCommon.prototype.drawMessage = function(dialogId, message, scroll, appendTop)
	{
		if (this.BXIM.messenger.popupMessenger == null || dialogId != this.BXIM.messenger.currentTab || typeof(message) != 'object' || dialogId == 0 || !this.MobileActionEqual('DIALOG'))
			return false;

		appendTop = appendTop == true;
		scroll = appendTop? false: scroll;

		if (message.senderId == this.BXIM.userId && this.BXIM.messenger.popupMessengerLastMessage < message.id)
		{
			this.BXIM.messenger.popupMessengerLastMessage = message.id;
		}
		if (typeof(message.params) != 'object')
		{
			message.params = {};
		}

		this.BXIM.messenger.openChatFlag = this.BXIM.messenger.currentTab.toString().substr(0, 4) == 'chat'? true: false;

		var edited = message.params && message.params.IS_EDITED == 'Y';
		var deleted = message.params && message.params.IS_DELETED == 'Y';
		var temp = message.id.indexOf('temp') == 0;
		var retry = temp && message.retry;
		var system = message.senderId == 0;
		var isChat = this.BXIM.messenger.openChatFlag && this.BXIM.messenger.chat[this.BXIM.messenger.currentTab.toString().substr(4)] && this.BXIM.messenger.chat[this.BXIM.messenger.currentTab.toString().substr(4)].style == "group";
		var likeEnable = this.BXIM.ppServerStatus;
		if (this.BXIM.messenger.openChatFlag && this.BXIM.messenger.chat[this.BXIM.messenger.currentTab.toString().substr(4)] && this.BXIM.messenger.chat[this.BXIM.messenger.currentTab.toString().substr(4)].style == "call")
			likeEnable = false;

		var likeCount = likeEnable && typeof(message.params.LIKE) == "object" && message.params.LIKE.length > 0? message.params.LIKE.length: '';
		var iLikeThis = likeEnable && typeof(message.params.LIKE) == "object" && BX.util.in_array(this.BXIM.userId, message.params.LIKE);

		var filesNode = BX.MessengerCommon.diskDrawFiles(message.chatId, message.params.FILE_ID);
		if (filesNode.length > 0)
		{
			filesNode = BX.create("div", { props : { className : "bx-messenger-file-box"+(message.text != ''? ' bx-messenger-file-box-with-message':'') }, children: filesNode});
		}
		else
		{
			filesNode = null;
		}

		var addBlankNode = false;
		if (!filesNode && message.text.length <= 0)
		{
			addBlankNode = true;
			skipAddMessage = true;
		}

		if (message.system && message.system == 'Y')
		{
			system = true;
			message.senderId = 0;
		}

		var messageUser = this.BXIM.messenger.users[message.senderId];
		if (!system && typeof(messageUser) == 'undefined')
		{
			addBlankNode = true;
			skipAddMessage = true;
		}

		if (!this.BXIM.messenger.history[dialogId])
			this.BXIM.messenger.history[dialogId] = [];

		if (parseInt(message.id) > 0)
			this.BXIM.messenger.history[dialogId].push(message.id);

		if (!addBlankNode)
		{
			var messageId = 0;
			var skipAddMessage = false;

			var markNewMessage = false;
			if (this.BXIM.messenger.unreadMessage[dialogId] && BX.util.in_array(message.id, this.BXIM.messenger.unreadMessage[dialogId]))
				markNewMessage = true;
		}

		var insertBefore = false;
		var lastMessage = null;

		if (appendTop)
		{
			lastMessage = this.BXIM.messenger.popupMessengerBodyWrap.firstChild;
			if (lastMessage)
			{
				if (BX.hasClass(lastMessage, "bx-messenger-content-empty") || BX.hasClass(lastMessage, "bx-messenger-content-load"))
				{
					BX.remove(lastMessage);
				}
				else if (BX.hasClass(lastMessage, "bx-messenger-content-group"))
				{
					lastMessage = lastMessage.nextSibling;
				}
			}
		}
		else
		{
			lastMessage = this.BXIM.messenger.popupMessengerBodyWrap.lastChild;

			if (lastMessage && (BX.hasClass(lastMessage, "bx-messenger-content-empty") || BX.hasClass(lastMessage, "bx-messenger-content-load")))
			{
				BX.remove(lastMessage);
			}
			else if (lastMessage && BX.hasClass(lastMessage, "bx-messenger-content-item-notify"))
			{
				if (message.senderId == this.BXIM.messenger.currentTab || !this.countWriting(this.BXIM.messenger.currentTab))
				{
					BX.remove(lastMessage);
					insertBefore = false;
					lastMessage = this.BXIM.messenger.popupMessengerBodyWrap.lastChild;
				}
				else
				{
					insertBefore = true;
					lastMessage = this.BXIM.messenger.popupMessengerBodyWrap.lastChild.previousSibling;
				}
			}
		}

		if (!addBlankNode)
		{
			var dateGroupTitle = this.formatDate(message.date, this.getDateFormatType('MESSAGE_TITLE'));
			if (!BX('bx-im-go-'+dateGroupTitle))
			{
				var dateGroupChildren = []
				if (this.BXIM.desktop && this.BXIM.desktop.run())
				{
					dateGroupChildren = [
						BX.create("a", {attrs: {name: 'bx-im-go-'+message.date}, props : { className: "bx-messenger-content-group-link"}}),
						BX.create("a", {attrs: {id: 'bx-im-go-'+dateGroupTitle, href: "#bx-im-go-"+message.date}, props : { className: "bx-messenger-content-group-title"+(this.BXIM.language == 'ru'? ' bx-messenger-lowercase': '')}, html : dateGroupTitle})
					];
				}
				else
				{
					dateGroupChildren = [
						BX.create("a", {attrs: {name: 'bx-im-go-'+message.date}, props : { className: "bx-messenger-content-group-link"}}),
						BX.create("div", {attrs: {id: 'bx-im-go-'+dateGroupTitle}, props : { className: "bx-messenger-content-group-title"+(this.BXIM.language == 'ru'? ' bx-messenger-lowercase': '')}, html : dateGroupTitle})
					]
				}

				var dateGroupNode = BX.create("div", {props : { className: "bx-messenger-content-group"+(dateGroupTitle == BX.message('FD_TODAY')? " bx-messenger-content-group-today": "")}, children : dateGroupChildren});

				if (appendTop)
				{
					this.BXIM.messenger.popupMessengerBodyWrap.insertBefore(dateGroupNode, this.BXIM.messenger.popupMessengerBodyWrap.firstChild);
					lastMessage = dateGroupNode.nextSibling;
				}
				else
				{
					if (insertBefore && lastMessage.nextElementSibling)
					{
						this.BXIM.messenger.popupMessengerBodyWrap.insertBefore(dateGroupNode, lastMessage.nextElementSibling);
						lastMessage = dateGroupNode;
					}
					else
					{
						this.BXIM.messenger.popupMessengerBodyWrap.appendChild(dateGroupNode);
					}
				}
			}
			if (!system && lastMessage)
			{
				if (message.senderId == lastMessage.getAttribute('data-senderId') && parseInt(message.date)-300 < parseInt(lastMessage.getAttribute('data-messageDate')))
				{

					var lastMessageElement = BX.findChildByClassName(lastMessage, "bx-messenger-content-item-text-message");
					var newMessageElementNode = [
						BX.create("div", { props : { className : "bx-messenger-hr"}}),
						BX.create("span", {  props : { className : "bx-messenger-content-item-text-wrap"+(appendTop? " bx-messenger-content-item-text-wrap-append": "")}, children: [
							BX.create("span", { attrs: {title : BX.message('IM_M_OPEN_EXTRA_TITLE').replace('#SHORTCUT#', BX.browser.IsMac()?'CMD':'CTRL')}, props : { className : "bx-messenger-content-item-menu"}}),
							BX.create("span", { props : { className : "bx-messenger-message"+(deleted?" bx-messenger-message-deleted": " ")+(deleted || edited?" bx-messenger-message-edited": "")}, attrs: {'id' : 'im-message-'+message.id}, html: BX.MessengerCommon.prepareText(message.text, false, true, true, (!this.BXIM.messenger.openChatFlag || message.senderId == this.BXIM.userId? false: (this.BXIM.messenger.users[this.BXIM.userId].name)))}),
							filesNode
						]})
					];

					if (appendTop)
					{
						for (var i=0,len=newMessageElementNode.length; i<len; i++)
						{
							lastMessageElement.insertBefore(newMessageElementNode[i], lastMessageElement.firstChild);
						}
						lastMessage.setAttribute('data-blockmessageid', message.id);
					}
					else
					{
						for (var i=0,len=newMessageElementNode.length; i<len; i++)
						{
							lastMessageElement.appendChild(newMessageElementNode[i]);
						}

						var lastMessageDateElement = BX.findChildByClassName(lastMessage, "bx-messenger-content-item-date");
						lastMessageDateElement.innerHTML = (temp? BX.message('IM_M_DELIVERED'): ' &nbsp; '+this.formatDate(message.date, this.getDateFormatType('MESSAGE')));

						if (retry)
						{
							this.drawProgessMessage(message.id, {title: BX.message('IM_M_RETRY')});
						}
						else if (temp)
						{
							this.drawProgessMessage(message.id);
						}

						lastMessage.setAttribute('data-messageDate', message.date);
						lastMessage.setAttribute('data-messageId', message.id);
						lastMessage.setAttribute('data-senderId', message.senderId);
					}

					if (markNewMessage)
						BX.addClass(lastMessage, 'bx-messenger-content-item-new');

					messageId = message.id;
					skipAddMessage = true;
				}
			}
		}

		if (!skipAddMessage)
		{
			if (lastMessage)
				messageId = lastMessage.getAttribute('data-messageId');

			if (system)
			{
				var lastSystemElement = BX.findChild(this.BXIM.messenger.popupMessengerBodyWrap, {attribute: {'data-messageId': ''+message.id+''}}, false);
				if (!lastSystemElement)
				{
					var arMessage = BX.create("div", { attrs : { 'data-type': 'system', 'data-senderId' : message.senderId, 'data-messageId' : message.id, 'data-blockmessageid' : message.id }, props: { className : "bx-messenger-content-item bx-messenger-content-item-system"}, children: [
						BX.create("span", { props : { className : "bx-messenger-content-item-content"}, children : [
							typeof(messageUser) == 'undefined'? []:
							BX.create("span", { props : { className : "bx-messenger-content-item-avatar"}, children : [
								BX.create("span", { props : { className : "bx-messenger-content-item-arrow"}}),
								BX.create('img', { props : { className : "bx-messenger-content-item-avatar-img"+(BX.MessengerCommon.isBlankAvatar(messageUser.avatar)? " bx-messenger-content-item-avatar-img-default": "") }, attrs : {src : messageUser.avatar}})
							]}),
							BX.create("span", { props : { className : "bx-messenger-content-item-text-center"}, children: [
								BX.create("span", {  props : { className : "bx-messenger-content-item-text-message"}, children: [
									BX.create("span", {  props : { className : "bx-messenger-content-item-text-wrap"+(appendTop? " bx-messenger-content-item-text-wrap-append": "")}, children: [
										BX.create("span", { props : { className : "bx-messenger-message"+(deleted?" bx-messenger-message-deleted": "")+(deleted || edited?" bx-messenger-message-edited": "")}, attrs: {'id' : 'im-message-'+message.id}, html: BX.MessengerCommon.prepareText(message.text, false, true, true)}),
										filesNode
									]})
								]}),
								BX.create("span", {  props : { className : "bx-messenger-content-item-params"}, children: [
									BX.create("span", { props : { className : "bx-messenger-content-item-date"}, html: ' &nbsp; '+this.formatDate(message.date, this.getDateFormatType('MESSAGE'))}),
									!likeEnable? null: BX.create("span", { props : { className : "bx-messenger-content-item-like"+(iLikeThis? ' bx-messenger-content-item-liked':'')}, children: [
										BX.create("span", { attrs : {title: likeCount>0? BX.message('IM_MESSAGE_LIKE_LIST'):''}, props : { className : "bx-messenger-content-like-digit"+(likeCount<=0?' bx-messenger-content-like-digit-off':'')}, html: likeCount}),
										BX.create("span", { attrs : {'data-messageId': message.id}, props : { className : "bx-messenger-content-like-button"}, html: BX.message(!iLikeThis? 'IM_MESSAGE_LIKE':'IM_MESSAGE_DISLIKE')})
									]})
								]}),
								BX.create("span", { props : { className : "bx-messenger-clear"}})
							]})
						]})
					]});

					if (message.system && message.system == 'Y' && markNewMessage)
						BX.addClass(arMessage, 'bx-messenger-content-item-new');
				}
			}
			else if (message.senderId == this.BXIM.userId)
			{
				var arMessage = BX.create("div", { attrs : { 'data-type': 'self', 'data-senderId' : message.senderId, 'data-messageDate' : message.date, 'data-messageId' : message.id, 'data-blockmessageid' : message.id }, props: { className : "bx-messenger-content-item"}, children: [
					BX.create("span", { props : { className : "bx-messenger-content-item-content"}, children : [
						BX.create("span", { props : { className : "bx-messenger-content-item-avatar"}, children : [
							BX.create("span", { props : { className : "bx-messenger-content-item-arrow"}}),
							BX.create('img', { props : { className : "bx-messenger-content-item-avatar-img"+(BX.MessengerCommon.isBlankAvatar(messageUser.avatar)? " bx-messenger-content-item-avatar-img-default": "") }, attrs : {src : messageUser.avatar}})
						]}),
						retry? (
							BX.create("span", { props : { className : "bx-messenger-content-item-status"}, children:[
								BX.create("span", { attrs: { title: BX.message('IM_M_RETRY'), 'data-messageid': message.id, 'data-chat': parseInt(message.recipientId) > 0? 'Y':'N' }, props : { className : "bx-messenger-content-item-error"}, children:[
									BX.create("span", { props : { className : "bx-messenger-content-item-error-icon"}})
								]})
							]})
						):(
							BX.create("span", { props : { className : "bx-messenger-content-item-status"}, children: temp?[
								BX.create("span", { props : { className : "bx-messenger-content-item-progress"}})
							]: []})
						),
						BX.create("span", { props : { className : "bx-messenger-content-item-text-center"}, children: [
							BX.create("span", {  props : { className : "bx-messenger-content-item-text-message"}, children: [
								BX.create("span", {  props : { className : "bx-messenger-content-item-text-wrap"+(appendTop? " bx-messenger-content-item-text-wrap-append": "")}, children: [
									BX.create("span", { attrs: {title : BX.message('IM_M_OPEN_EXTRA_TITLE').replace('#SHORTCUT#', BX.browser.IsMac()?'CMD':'CTRL')}, props : { className : "bx-messenger-content-item-menu"}}),
									BX.create("span", { props : { className : "bx-messenger-message"+(deleted?" bx-messenger-message-deleted": " ")+(deleted || edited?" bx-messenger-message-edited": "")}, attrs: {'id' : 'im-message-'+message.id}, html: BX.MessengerCommon.prepareText(message.text, false, true, true)}),
									filesNode
								]})
							]}),
							BX.create("span", {  props : { className : "bx-messenger-content-item-params"}, children: [
								BX.create("span", { props : { className : "bx-messenger-content-item-date"}, html: (retry? BX.message('IM_M_NOT_DELIVERED') : temp? BX.message('IM_M_DELIVERED'): ' &nbsp; '+this.formatDate(message.date, this.getDateFormatType('MESSAGE')))}),
								!likeEnable? null: BX.create("span", { props : { className : "bx-messenger-content-item-like"+(iLikeThis? ' bx-messenger-content-item-liked':'')}, children: [
									BX.create("span", {  attrs : {title: likeCount>0? BX.message('IM_MESSAGE_LIKE_LIST'):''}, props : { className : "bx-messenger-content-like-digit"+(likeCount<=0?' bx-messenger-content-like-digit-off':'')}, html: likeCount}),
									BX.create("span", { attrs : {'data-messageId': message.id}, props : { className : "bx-messenger-content-like-button"}, html: BX.message(!iLikeThis? 'IM_MESSAGE_LIKE':'IM_MESSAGE_DISLIKE')})
								]})
							]}),
							BX.create("span", { props : { className : "bx-messenger-clear"}})
						]})
					]})
				]});
			}
			else
			{
				var arMessage = BX.create("div", { attrs : { 'data-type': 'other', 'data-senderId' : message.senderId, 'data-messageDate' : message.date, 'data-messageId' : message.id, 'data-blockmessageid' : message.id }, props: { className : "bx-messenger-content-item bx-messenger-content-item-2"+(markNewMessage? ' bx-messenger-content-item-new': '')}, children: [
					BX.create("span", { props : { className : "bx-messenger-content-item-content"}, children : [
						BX.create("span", { attrs: {title: (isChat? messageUser.name: '')}, props : { className : "bx-messenger-content-item-avatar bx-messenger-content-item-avatar-button"}, children : [
							BX.create("span", { props : { className : "bx-messenger-content-item-arrow"}}),
							BX.create('img', { props : { className : "bx-messenger-content-item-avatar-img"+(BX.MessengerCommon.isBlankAvatar(messageUser.avatar)? " bx-messenger-content-item-avatar-img-default": "") }, attrs : {src : messageUser.avatar}})
						]}),
						BX.create("span", { props : { className : "bx-messenger-content-item-status"}, children:[]}),
						BX.create("span", { props : { className : "bx-messenger-content-item-text-center"}, children: [
							BX.create("span", {  props : { className : "bx-messenger-content-item-text-message"}, children: [
								BX.create("span", {  props : { className : "bx-messenger-content-item-text-wrap"+(appendTop? " bx-messenger-content-item-text-wrap-append": "")}, children: [
									BX.create("span", { attrs: {title : BX.message('IM_M_OPEN_EXTRA_TITLE').replace('#SHORTCUT#', BX.browser.IsMac()?'CMD':'CTRL')}, props : { className : "bx-messenger-content-item-menu"}}),
									BX.create("span", { props : { className : "bx-messenger-message"+(deleted?" bx-messenger-message-deleted": " ")+(deleted || edited?" bx-messenger-message-edited": "")}, attrs: {'id' : 'im-message-'+message.id}, html: BX.MessengerCommon.prepareText(message.text, false, true, true, (!this.BXIM.messenger.openChatFlag || message.senderId == this.BXIM.userId? false: (this.BXIM.messenger.users[this.BXIM.userId].name)))}),
									filesNode
								]})
							]}),
							BX.create("span", {  props : { className : "bx-messenger-content-item-params"}, children: [
								BX.create("span", { props : { className : "bx-messenger-content-item-date"}, html: (temp? BX.message('IM_M_DELIVERED'): ' &nbsp; '+this.formatDate(message.date, this.getDateFormatType('MESSAGE')))}),
								!likeEnable? null: BX.create("span", { props : { className : "bx-messenger-content-item-like"+(iLikeThis? ' bx-messenger-content-item-liked':'')}, children: [
									BX.create("span", { attrs : {title: likeCount>0? BX.message('IM_MESSAGE_LIKE_LIST'):''}, props : { className : "bx-messenger-content-like-digit"+(likeCount<=0?' bx-messenger-content-like-digit-off':'')}, html: likeCount}),
									BX.create("span", { attrs : {'data-messageId': message.id}, props : { className : "bx-messenger-content-like-button"}, html: BX.message(!iLikeThis? 'IM_MESSAGE_LIKE':'IM_MESSAGE_DISLIKE')})
								]})
							]}),
							BX.create("span", { props : { className : "bx-messenger-clear"}})
						]})
					]})
				]});
			}
		}
		else if (addBlankNode)
		{
			arMessage = BX.create("div", {attrs : {'id' : 'im-message-'+message.id, 'data-messageDate' : message.date, 'data-messageId' : message.id, 'data-blockmessageid' : message.id }, props : { className : "bx-messenger-content-item-text-wrap bx-messenger-item-skipped"}});
		}

		if (!skipAddMessage || addBlankNode)
		{
			if (appendTop)
				this.BXIM.messenger.popupMessengerBodyWrap.insertBefore(arMessage, lastMessage);
			else if (insertBefore && lastMessage.nextElementSibling)
				this.BXIM.messenger.popupMessengerBodyWrap.insertBefore(arMessage, lastMessage.nextElementSibling);
			else
				this.BXIM.messenger.popupMessengerBodyWrap.appendChild(arMessage);
		}

		if (!addBlankNode && BX.MessengerCommon.enableScroll(this.BXIM.messenger.popupMessengerBody, this.BXIM.messenger.popupMessengerBody.offsetHeight, scroll))
		{
			if (this.BXIM.animationSupport)
			{
				if (this.BXIM.messenger.popupMessengerBodyAnimation != null)
					this.BXIM.messenger.popupMessengerBodyAnimation.stop();
				(this.BXIM.messenger.popupMessengerBodyAnimation = new BX.easing({
					duration : 800,
					start : { scroll : this.BXIM.messenger.popupMessengerBody.scrollTop },
					finish : { scroll : this.BXIM.messenger.popupMessengerBody.scrollHeight - this.BXIM.messenger.popupMessengerBody.offsetHeight*(this.isMobile()? 0: 1)},
					transition : BX.easing.makeEaseInOut(BX.easing.transitions.quart),
					step : BX.delegate(function(state){
						this.BXIM.messenger.popupMessengerBody.scrollTop = state.scroll;
					}, this)
				})).animate();
			}
			else
			{
				this.BXIM.messenger.popupMessengerBody.scrollTop = this.BXIM.messenger.popupMessengerBody.scrollHeight - this.BXIM.messenger.popupMessengerBody.offsetHeight*(this.isMobile()? 0: 1);
			}
		}

		return messageId;
	};

	BX.MessengerCommon.prototype.drawProgessMessage = function(messageId, button)
	{
		var element = BX('im-message-'+messageId);
		if (!element)
			return false;

		BX.addClass(element.parentNode.parentNode.parentNode.parentNode, 'bx-messenger-content-item-content-progress');
		element.parentNode.parentNode.parentNode.previousSibling.innerHTML = '';

		if (typeof (button) == 'object' || button === true)
		{
			if (this.BXIM.messenger.message[messageId])
			{
				this.BXIM.messenger.errorMessage[this.BXIM.messenger.currentTab] = true;
				BX.addClass(element.parentNode.parentNode.parentNode.parentNode, 'bx-messenger-content-item-content-progress-error');
				button.chat = button.chat? button.chat: (parseInt(this.BXIM.messenger.message[messageId].recipientId) > 0? 'Y':'N');
				BX.adjust(element.parentNode.parentNode.parentNode.previousSibling, {children: [
					BX.create("span", { attrs: { title: button.title? button.title: '', 'data-messageid': messageId, 'data-chat': button.chat }, props : { className : "bx-messenger-content-item-error"}, children:[
						BX.create("span", { props : { className : "bx-messenger-content-item-error-icon"}})
					]})
				]});
			}
			else
			{
				BX.removeClass(element.parentNode.parentNode.parentNode.parentNode, 'bx-messenger-content-item-content-progress');
				BX.removeClass(element.parentNode.parentNode.parentNode.parentNode, 'bx-messenger-content-item-content-progress-error');
			}
		}
		else
		{
			BX.adjust(element.parentNode.parentNode.parentNode.previousSibling, {children: [
				BX.create("span", { props : { className : "bx-messenger-content-item-progress"}})
			]});
		}

		return true;
	}

	BX.MessengerCommon.prototype.clearProgessMessage = function(messageId)
	{
		var element = BX('im-message-'+messageId);
		if (!element)
			return false;

		BX.removeClass(element.parentNode.parentNode.parentNode.parentNode, 'bx-messenger-content-item-content-progress');
		BX.removeClass(element.parentNode.parentNode.parentNode.parentNode, 'bx-messenger-content-item-content-progress-error');
		element.parentNode.parentNode.parentNode.previousSibling.innerHTML = '';

		return true;
	}

	/* Writing status */
	BX.MessengerCommon.prototype.startWriting = function(userId, dialogId)
	{
		if (dialogId == this.BXIM.userId)
		{
			this.BXIM.messenger.writingList[userId] = true;
			this.drawWriting(userId);

			clearTimeout(this.BXIM.messenger.writingListTimeout[userId]);
			this.BXIM.messenger.writingListTimeout[userId] = setTimeout(BX.delegate(function(){
				this.endWriting(userId);
			}, this), 29500);
		}
		else
		{
			if (!this.BXIM.messenger.writingList[dialogId])
				this.BXIM.messenger.writingList[dialogId] = {};

			if (!this.BXIM.messenger.writingListTimeout[dialogId])
				this.BXIM.messenger.writingListTimeout[dialogId] = {};

			this.BXIM.messenger.writingList[dialogId][userId] = true;
			this.drawWriting(userId, dialogId);

			clearTimeout(this.BXIM.messenger.writingListTimeout[dialogId][userId]);
			this.BXIM.messenger.writingListTimeout[dialogId][userId] = setTimeout(BX.delegate(function(){
				this.endWriting(userId, dialogId);
			}, this), 29500);
		}
	};

	BX.MessengerCommon.prototype.drawWriting = function(userId, dialogId)
	{
		if (userId == this.BXIM.userId)
			return false;

		if (this.BXIM.messenger.popupMessenger != null && this.MobileActionEqual('RECENT', 'DIALOG'))
		{
			if (this.BXIM.messenger.writingList[userId] || dialogId && this.countWriting(dialogId) > 0)
			{

				var elements = BX.findChildrenByClassName(this.BXIM.messenger.popupContactListElementsWrap, "bx-messenger-cl-id-"+(dialogId? dialogId: userId));
				if (elements)
				{
					for (var i = 0; i < elements.length; i++)
						BX.addClass(elements[i], 'bx-messenger-cl-status-writing');
				}

				if (this.MobileActionEqual('DIALOG') && (this.BXIM.messenger.currentTab == userId || dialogId && this.BXIM.messenger.currentTab == dialogId))
				{
					if (dialogId)
					{
						var userList = [];
						for (var i in this.BXIM.messenger.writingList[dialogId])
						{
							if (this.BXIM.messenger.writingList[dialogId].hasOwnProperty(i) && this.BXIM.messenger.users[i])
							{
								userList.push(this.BXIM.messenger.users[i].name);
							}
						}
						this.drawNotifyMessage(dialogId, 'writing', BX.message('IM_M_WRITING').replace('#USER_NAME#', userList.join(', ')));
					}
					else
					{
						if (!this.isMobile())
						{
							this.BXIM.messenger.popupMessengerPanelAvatar.parentNode.className = 'bx-messenger-panel-avatar bx-messenger-panel-avatar-status-writing';
						}
						this.drawNotifyMessage(userId, 'writing', BX.message('IM_M_WRITING').replace('#USER_NAME#', this.BXIM.messenger.users[userId].name));
					}
				}

			}
			else if (!this.BXIM.messenger.writingList[userId] || dialogId && this.countWriting(dialogId) == 0)
			{
				var elements = BX.findChildrenByClassName(this.BXIM.messenger.popupContactListElementsWrap, "bx-messenger-cl-id-"+(dialogId? dialogId: userId));
				if (elements)
				{
					for (var i = 0; i < elements.length; i++)
						BX.removeClass(elements[i], 'bx-messenger-cl-status-writing');
				}

				if (this.MobileActionEqual('DIALOG') && (this.BXIM.messenger.currentTab == userId || this.BXIM.messenger.currentTab == dialogId))
				{
					if (!dialogId)
					{
						if (!this.isMobile())
							this.BXIM.messenger.popupMessengerPanelAvatar.parentNode.className = 'bx-messenger-panel-avatar bx-messenger-panel-avatar-status-' + this.getUserStatus(userId);
					}

					var lastMessage = this.BXIM.messenger.popupMessengerBodyWrap.lastChild;
					if (lastMessage && BX.hasClass(lastMessage, "bx-messenger-content-item-notify"))
					{
						if (!dialogId && this.BXIM.messenger.readedList[userId])
						{
							this.drawReadMessage(userId, this.BXIM.messenger.readedList[userId].messageId, this.BXIM.messenger.readedList[userId].date, false);
						}
						else if (BX.MessengerCommon.enableScroll(this.BXIM.messenger.popupMessengerBody, this.BXIM.messenger.popupMessengerBody.offsetHeight)) // TODO mobile
						{
							if (this.BXIM.animationSupport)
							{
								if (this.BXIM.messenger.popupMessengerBodyAnimation != null)
									this.BXIM.messenger.popupMessengerBodyAnimation.stop();
								(this.BXIM.messenger.popupMessengerBodyAnimation = new BX.easing({
									duration : 800,
									start : {scroll : this.BXIM.messenger.popupMessengerBody.scrollTop},
									finish : {scroll : this.BXIM.messenger.popupMessengerBody.scrollTop - lastMessage.offsetHeight},
									transition : BX.easing.makeEaseInOut(BX.easing.transitions.quart),
									step : BX.delegate(function (state)
									{
										this.BXIM.messenger.popupMessengerBody.scrollTop = state.scroll;
									}, this),
									complete : BX.delegate(function ()
									{
										BX.remove(lastMessage);
									}, this)
								})).animate();
							}
							else
							{
								this.BXIM.messenger.popupMessengerBody.scrollTop = this.BXIM.messenger.popupMessengerBody.scrollTop - lastMessage.offsetHeight;
								BX.remove(lastMessage);
							}
						}
						else
						{
							this.BXIM.messenger.popupMessengerBody.scrollTop = this.BXIM.messenger.popupMessengerBody.scrollTop - lastMessage.offsetHeight;
							BX.remove(lastMessage);
						}
					}
				}
			}
		}
	};

	BX.MessengerCommon.prototype.endWriting = function(userId, dialogId)
	{
		if (dialogId)
		{
			if (this.BXIM.messenger.writingListTimeout[dialogId] && this.BXIM.messenger.writingListTimeout[dialogId][userId])
				clearTimeout(this.BXIM.messenger.writingListTimeout[dialogId][userId]);

			if (this.BXIM.messenger.writingList[dialogId] && this.BXIM.messenger.writingList[dialogId][userId])
				delete this.BXIM.messenger.writingList[dialogId][userId];
		}
		else
		{
			clearTimeout(this.BXIM.messenger.writingListTimeout[userId]);
			delete this.BXIM.messenger.writingList[userId];
		}
		this.drawWriting(userId, dialogId);
	};

	BX.MessengerCommon.prototype.sendWriting = function(dialogId)
	{
		if (!this.BXIM.ppServerStatus)
			return false;

		if (!this.BXIM.messenger.writingSendList[dialogId])
		{
			clearTimeout(this.BXIM.messenger.writingSendListTimeout[dialogId]);
			this.BXIM.messenger.writingSendList[dialogId] = true;
			BX.ajax({
				url: this.BXIM.pathToAjax+'?START_WRITING&V='+this.BXIM.revision,
				method: 'POST',
				dataType: 'json',
				timeout: 30,
				data: {'IM_START_WRITING' : 'Y', 'DIALOG_ID' : dialogId, 'IM_AJAX_CALL' : 'Y', 'sessid': BX.bitrix_sessid()},
				onsuccess: BX.delegate(function(data)
				{
					if (data && data.BITRIX_SESSID)
					{
						BX.message({'bitrix_sessid': data.BITRIX_SESSID});
					}
					if (data.ERROR == 'AUTHORIZE_ERROR' && this.BXIM.desktop.ready() && this.BXIM.messenger.sendAjaxTry < 3)
					{
						this.BXIM.messenger.sendAjaxTry++;
						BX.onCustomEvent(window, 'onImError', [data.ERROR]);
					}
					else if (data.ERROR == 'SESSION_ERROR' && this.BXIM.messenger.sendAjaxTry < 2)
					{
						this.BXIM.messenger.sendAjaxTry++;
						BX.onCustomEvent(window, 'onImError', [data.ERROR, data.BITRIX_SESSID]);
					}
					else
					{
						if (data.ERROR == 'AUTHORIZE_ERROR' || data.ERROR == 'SESSION_ERROR')
						{
							BX.onCustomEvent(window, 'onImError', [data.ERROR]);
						}
					}
				}, this)
			});
			this.BXIM.messenger.writingSendListTimeout[dialogId] = setTimeout(BX.delegate(function(){
				this.endSendWriting(dialogId);
			}, this), 30000);
		}
	};

	BX.MessengerCommon.prototype.endSendWriting = function(dialogId)
	{
		clearTimeout(this.BXIM.messenger.writingSendListTimeout[dialogId]);
		this.BXIM.messenger.writingSendList[dialogId] = false;
	};

	BX.MessengerCommon.prototype.countWriting = function(dialogId)
	{
		var count = 0;
		if (this.BXIM.messenger.writingList[dialogId])
		{
			if (typeof(this.BXIM.messenger.writingList[dialogId]) == 'object')
			{
				for(var i in this.BXIM.messenger.writingList[dialogId])
				{
					if(this.BXIM.messenger.writingList[dialogId].hasOwnProperty(i))
					{
						count++;
					}
				}
			}
			else
			{
				count = 1;
			}
		}

		return count;
	}

	/* Chats */
	BX.MessengerCommon.prototype.leaveFromChat = function(chatId, sendAjax)
	{
		if (!this.BXIM.messenger.chat[chatId])
			return false;

		sendAjax = sendAjax != false;

		if (!sendAjax)
		{
			delete this.BXIM.messenger.chat[chatId];
			delete this.BXIM.messenger.userInChat[chatId];
			delete this.BXIM.messenger.unreadMessage[chatId];

			if (this.BXIM.messenger.popupMessenger != null)
			{
				if (this.BXIM.messenger.currentTab == 'chat'+chatId)
				{
					this.BXIM.messenger.currentTab = 0;
					this.BXIM.messenger.openChatFlag = false;
					this.BXIM.messenger.openCallFlag = false;
					this.BXIM.messenger.extraClose();
				}
				BX.MessengerCommon.userListRedraw();
			}
		}
		else
		{
			BX.ajax({
				url: this.BXIM.pathToAjax+'?CHAT_LEAVE&V='+this.BXIM.revision,
				method: 'POST',
				dataType: 'json',
				timeout: 60,
				data: {'IM_CHAT_LEAVE' : 'Y', 'CHAT_ID' : chatId, 'IM_AJAX_CALL' : 'Y', 'sessid': BX.bitrix_sessid()},
				onsuccess: BX.delegate(function(data){
					if (data.ERROR == '')
					{
						this.readMessage('chat'+data.CHAT_ID, true, false);
						delete this.BXIM.messenger.chat[data.CHAT_ID];
						delete this.BXIM.messenger.userInChat[data.CHAT_ID];
						delete this.BXIM.messenger.unreadMessage[data.CHAT_ID];

						if (this.BXIM.messenger.popupMessenger != null)
						{
							if (this.BXIM.messenger.currentTab == 'chat'+data.CHAT_ID)
							{
								this.BXIM.messenger.currentTab = 0;
								this.BXIM.messenger.openChatFlag = false;
								this.BXIM.messenger.openCallFlag = false;
								BX.localStorage.set('mct', this.BXIM.messenger.currentTab, 15);
								this.BXIM.messenger.extraClose();
							}
							if (this.BXIM.messenger.recentList)
								BX.MessengerCommon.recentListRedraw();
						}
						BX.localStorage.set('mcl', data.CHAT_ID, 5);
					}
				}, this)
			});
		}
	};

	/* Pull Events */
	BX.MessengerCommon.prototype.pullEvent = function()
	{
		BX.addCustomEvent((this.isMobile()? "onPull-im": "onPullEvent-im"), BX.delegate(function(command,params)
		{
			if (this.isMobile())
			{
				params = command.params;
				command = command.command;
			}

			if (command == 'desktopOffline')
			{
				this.BXIM.desktopStatus = false;
			}
			else if (command == 'desktopOnline')
			{
				this.BXIM.desktopStatus = true;
			}
			else if (command == 'readMessage')
			{
				if (this.MobileActionNotEqual('RECENT', 'DIALOG'))
					return false;

				this.readMessage(params.userId, false, false);
			}
			else if (command == 'readMessageChat')
			{
				if (this.MobileActionNotEqual('RECENT', 'DIALOG'))
					return false;

				this.readMessage('chat'+params.chatId, false, false);
			}
			else if (command == 'readMessageApponent')
			{
				if (this.MobileActionNotEqual('RECENT', 'DIALOG'))
					return false;

				params.date = parseInt(params.date)+parseInt(BX.message('USER_TZ_OFFSET'));
				this.drawReadMessage(params.userId, params.lastId, params.date);
			}
			else if (command == 'startWriting')
			{
				if (this.MobileActionNotEqual('RECENT', 'DIALOG'))
					return false;

				this.startWriting(params.senderId, params.dialogId);
			}
			else if (command == 'message' || command == 'messageChat')
			{
				if (this.MobileActionNotEqual('RECENT', 'DIALOG'))
					return false;

				if (this.BXIM.lastRecordId >= params.MESSAGE.id)
					return false;

				var data = {};
				data.MESSAGE = {};
				data.USERS_MESSAGE = {};
				params.MESSAGE.date = parseInt(params.MESSAGE.date)+parseInt(BX.message('USER_TZ_OFFSET'));
				for (var i in params.CHAT)
				{
					if (this.BXIM.messenger.chat[i] && this.BXIM.messenger.chat[i].fake)
						params.CHAT[i].fake = true;
					else if (!this.BXIM.messenger.chat[i])
						params.CHAT[i].fake = true;

					this.BXIM.messenger.chat[i] = params.CHAT[i];
				}
				for (var i in params.USER_IN_CHAT)
				{
					this.BXIM.messenger.userInChat[i] = params.USER_IN_CHAT[i];
				}
				for (var i in params.USER_BLOCK_CHAT)
				{
					this.BXIM.messenger.userChatBlockStatus[i] = params.USER_BLOCK_CHAT[i];
				}
				var userChangeStatus = {};
				for (var i in params.USERS)
				{
					if (this.BXIM.messenger.users[i] && this.BXIM.messenger.users[i].status != params.USERS[i].status && parseInt(params.MESSAGE.date)+180 > BX.MessengerCommon.getNowDate())
					{
						userChangeStatus[i] = this.BXIM.messenger.users[i].status;
						this.BXIM.messenger.users[i].status = params.USERS[i].status;
					}
				}
				if (this.MobileActionEqual('RECENT'))
				{
					for (var i in userChangeStatus)
					{
						if (!this.BXIM.messenger.users[i])
							continue;

						var elements = BX.findChildrenByClassName(this.BXIM.messenger.popupContactListElementsWrap, "bx-messenger-cl-id-"+i);
						if (elements != null)
						{
							for (var j = 0; j < elements.length; j++)
							{
								BX.removeClass(elements[j], 'bx-messenger-cl-status-' + userChangeStatus[i]);
								BX.addClass(elements[j], 'bx-messenger-cl-status-' + BX.MessengerCommon.getUserStatus(i));
								elements[j].setAttribute('data-status', BX.MessengerCommon.getUserStatus(i));
							}
						}
					}
				}
				elements = null;
				data.USERS = params.USERS;

				if (this.MobileActionEqual('DIALOG'))
				{
					for (var i in params.FILES)
					{
						if (!this.BXIM.disk.files[params.CHAT_ID])
							this.BXIM.disk.files[params.CHAT_ID] = {};
						if (this.BXIM.disk.files[params.CHAT_ID][i])
							continue;
						params.FILES[i].date = parseInt(params.FILES[i].date)+parseInt(BX.message('USER_TZ_OFFSET'));
						this.BXIM.disk.files[params.CHAT_ID][i] = params.FILES[i];
					}
				}

				data.MESSAGE[params.MESSAGE.id] = params.MESSAGE;
				this.BXIM.lastRecordId = params.MESSAGE.id;
				if (params.MESSAGE.senderId == this.BXIM.userId)
				{
					if (this.BXIM.messenger.sendMessageFlag > 0 || this.BXIM.messenger.message[params.MESSAGE.id])
						return;

					this.readMessage(params.MESSAGE.recipientId, false, false);

					data.USERS_MESSAGE[params.MESSAGE.recipientId] = [params.MESSAGE.id];
					this.updateStateVar(data);
					BX.MessengerCommon.recentListAdd({
						'userId': params.MESSAGE.recipientId,
						'id': params.MESSAGE.id,
						'date': parseInt(params.MESSAGE.date)+parseInt(BX.message("SERVER_TZ_OFFSET")),
						'recipientId': params.MESSAGE.recipientId,
						'senderId': params.MESSAGE.senderId,
						'text': params.MESSAGE.text,
						'params': params.MESSAGE.params
					}, true);
				}
				else
				{
					data.UNREAD_MESSAGE = {};
					data.UNREAD_MESSAGE[command == 'messageChat'? params.MESSAGE.recipientId: params.MESSAGE.senderId] = [params.MESSAGE.id];
					data.USERS_MESSAGE[command == 'messageChat'?params.MESSAGE.recipientId: params.MESSAGE.senderId] = [params.MESSAGE.id];
					if (command == 'message')
						this.endWriting(params.MESSAGE.senderId);
					else
						this.endWriting(params.MESSAGE.senderId, params.MESSAGE.recipientId);
					this.updateStateVar(data);
					BX.MessengerCommon.recentListAdd({
						'userId': command == 'messageChat'? params.MESSAGE.recipientId: params.MESSAGE.senderId,
						'id': params.MESSAGE.id,
						'date': parseInt(params.MESSAGE.date)+parseInt(BX.message("SERVER_TZ_OFFSET")),
						'recipientId': params.MESSAGE.recipientId,
						'senderId': params.MESSAGE.senderId,
						'text': params.MESSAGE.text,
						'params': params.MESSAGE.params
					}, true);
				}
				BX.localStorage.set('mfm', this.BXIM.messenger.flashMessage, 80);
			}
			else if (command == 'messageUpdate' || command == 'messageDelete')
			{
				if (this.MobileActionNotEqual('DIALOG', 'RECENT'))
					return false;

				if (this.BXIM.messenger.message[params.id])
				{
					if (!this.BXIM.messenger.message[params.id].params)
						this.BXIM.messenger.message[params.id].params = {};

					var dialogId = 0;
					if (command == 'messageDelete')
					{
						params.message = BX.message('IM_M_DELETED');
						this.BXIM.messenger.message[params.id].params.IS_DELETED = 'Y';
					}
					else if (command == 'messageUpdate')
					{
						this.BXIM.messenger.message[params.id].params.IS_EDITED = 'Y';
					}

					this.BXIM.messenger.message[params.id].text = params.text;

					if (params.type == 'private')
					{
						dialogId = params.fromUserId == this.BXIM.messenger.BXIM.userId? params.toUserId: params.fromUserId;
						this.endWriting(dialogId);
					}
					else
					{
						dialogId = 'chat' + params.chatId;
						this.endWriting(params.senderId, dialogId);
					}

					if (this.BXIM.messenger.currentTab == dialogId && BX('im-message-'+params.id))
					{
						var messageBox = BX('im-message-'+params.id);
						BX.addClass(messageBox, (command == 'messageDelete'? 'bx-messenger-message-edited bx-messenger-message-deleted': 'bx-messenger-message-edited'));
						messageBox.innerHTML = BX.MessengerCommon.prepareText(this.BXIM.messenger.message[params.id].text, false, true, true);
						BX.addClass(messageBox, 'bx-messenger-message-edited-anim');
						if (messageBox.nextSibling && BX.hasClass(messageBox.nextSibling, 'bx-messenger-file-box'))
						{
							BX.addClass(messageBox.nextSibling, 'bx-messenger-file-box-with-message');
						}
						setTimeout(BX.delegate(function(){
							BX.removeClass(messageBox, 'bx-messenger-message-edited-anim');
						}, this), 1000);
					}

					if (this.BXIM.messenger.recentList)
						BX.MessengerCommon.recentListRedraw();
				}
			}
			else if (command == 'messageLike')
			{
				if (this.MobileActionNotEqual('DIALOG'))
					return false;

				var iLikeThis = BX.util.in_array(this.BXIM.userId, params.users);
				var likeCount = params.users.length > 0? params.users.length: '';

				if  (!this.BXIM.messenger.message[params.id])
				{
					return false;
				}

				if (typeof(this.BXIM.messenger.message[params.id].params) != 'object')
				{
					this.BXIM.messenger.message[params.id].params = {};
				}

				this.BXIM.messenger.message[params.id].params.LIKE = params.users;

				if (BX('im-message-'+params.id))
				{
					var element = BX.findChild(this.BXIM.messenger.popupMessengerBodyWrap, {attribute: {'data-blockmessageid': ''+params.id+''}}, false);
					if (element)
					{
						var elementLike = BX.findChildByClassName(element, "bx-messenger-content-item-like");
						if (elementLike)
						{
							var elementLikeDigit = BX.findChildByClassName(elementLike, "bx-messenger-content-like-digit", false);
							var elementLikeButton = BX.findChildByClassName(elementLike, "bx-messenger-content-like-button", false);

							if (iLikeThis)
							{
								elementLikeButton.innerHTML = BX.message('IM_MESSAGE_DISLIKE');
								BX.addClass(elementLike, 'bx-messenger-content-item-liked');
							}
							else
							{
								elementLikeButton.innerHTML = BX.message('IM_MESSAGE_LIKE');
								BX.removeClass(elementLike, 'bx-messenger-content-item-liked');
							}

							if (likeCount>0)
							{
								elementLikeDigit.setAttribute('title', BX.message('IM_MESSAGE_LIKE_LIST'));
								BX.removeClass(elementLikeDigit, 'bx-messenger-content-like-digit-off');
							}
							else
							{
								elementLikeDigit.setAttribute('title', '');
								BX.addClass(elementLikeDigit, 'bx-messenger-content-like-digit-off');
							}

							if (elementLikeDigit.innerHTML < likeCount)
							{
								BX.addClass(element.firstChild, 'bx-messenger-content-item-plus-like');
								setTimeout(function(){
									BX.removeClass(element.firstChild, 'bx-messenger-content-item-plus-like');
								}, 500);
							}
							elementLikeDigit.innerHTML = likeCount;
						}
					}
				}
			}
			else if (command == 'fileUpload')  // TODO mobile
			{
				if (this.MobileActionNotEqual('DIALOG'))
					return false;

				if (this.BXIM.disk.filesProgress[params.fileTmpId])
					return false;

				if (this.BXIM.disk.files[params.fileChatId] && this.BXIM.disk.files[params.fileChatId][params.fileId])
				{
					params.fileParams['preview'] = this.BXIM.disk.files[params.fileChatId][params.fileId]['preview'];
				}
				if (!this.BXIM.disk.files[params.fileChatId])
					this.BXIM.disk.files[params.fileChatId] = {};
				this.BXIM.disk.files[params.fileChatId][params.fileId] = params.fileParams;
				BX.MessengerCommon.diskRedrawFile(params.fileChatId, params.fileId);

				if (BX.MessengerCommon.enableScroll(this.BXIM.messenger.popupMessengerBody, this.BXIM.messenger.popupMessengerBody.offsetHeight))
				{
					if (this.BXIM.messenger.BXIM.animationSupport)
					{
						if (this.BXIM.messenger.popupMessengerBodyAnimation != null)
							this.BXIM.messenger.popupMessengerBodyAnimation.stop();
						(this.BXIM.messenger.popupMessengerBodyAnimation = new BX.easing({
							duration : 800,
							start : { scroll : this.BXIM.messenger.popupMessengerBody.scrollTop },
							finish : { scroll : this.BXIM.messenger.popupMessengerBody.scrollHeight - this.BXIM.messenger.popupMessengerBody.offsetHeight*(this.isMobile()? 0: 1)},
							transition : BX.easing.makeEaseInOut(BX.easing.transitions.quart),
							step : BX.delegate(function(state){
								this.BXIM.messenger.popupMessengerBody.scrollTop = state.scroll;
							}, this)
						})).animate();
					}
					else
					{
						this.BXIM.messenger.popupMessengerBody.scrollTop = this.BXIM.messenger.popupMessengerBody.scrollHeight - this.BXIM.messenger.popupMessengerBody.offsetHeight*(this.isMobile()? 0: 1);
					}
				}
			}
			else if (command == 'fileUnRegister')  // TODO mobile
			{
				if (this.MobileActionNotEqual('DIALOG'))
					return false;

				for (var id in params.files)
				{
					if (this.BXIM.disk.filesRegister[params.chatId])
					{
						delete this.BXIM.disk.filesRegister[params.chatId][params.files[id]];
					}
					if (this.BXIM.disk.files[params.chatId])
					{
						this.BXIM.disk.files[params.chatId][params.files[id]].status = 'error';
						BX.MessengerCommon.diskRedrawFile(params.chatId, params.files[id]);
					}
					delete this.BXIM.disk.filesProgress[id];
				}
				this.drawTab(this.BXIM.messenger.getRecipientByChatId(params.chatId));
			}
			else if (command == 'chatRename')
			{
				if (this.MobileActionNotEqual('DIALOG', 'RECENT'))
					return false;

				if (this.BXIM.messenger.chat[params.chatId])
				{
					this.BXIM.messenger.chat[params.chatId].name = params.chatTitle;
					this.BXIM.messenger.redrawChatHeader();
				}
			}
			else if (command == 'chatAvatar')
			{
				if (this.MobileActionNotEqual('DIALOG', 'RECENT'))
					return false;
				this.BXIM.messenger.updateChatAvatar(params.chatId, params.chatAvatar);
			}
			else if (command == 'chatUserAdd')
			{
				if (this.MobileActionNotEqual('DIALOG', 'RECENT'))
					return false;

				for (var i in params.users)
					this.BXIM.messenger.users[i] = params.users[i];

				if (!this.BXIM.messenger.chat[params.chatId])
				{
					this.BXIM.messenger.chat[params.chatId] = {'id': params.chatId, 'name': params.chatId, 'owner': params.chatOwner, 'fake': true};
				}
				else
				{
					if (this.BXIM.messenger.userInChat[params.chatId])
					{
						for (i = 0; i < params.newUsers.length; i++)
							this.BXIM.messenger.userInChat[params.chatId].push(params.newUsers[i]);
					}
					else
						this.BXIM.messenger.userInChat[params.chatId] = params.newUsers;

					this.BXIM.messenger.redrawChatHeader();
				}
			}
			else if (command == 'chatUserLeave')
			{
				if (this.MobileActionNotEqual('DIALOG', 'RECENT'))
					return false;

				if (params.userId == this.BXIM.userId)
				{
					this.readMessage('chat'+params.chatId, true, false);
					this.leaveFromChat(params.chatId, false);
					if (params.message.length > 0)
						this.BXIM.openConfirm({title: BX.util.htmlspecialchars(params.chatTitle), message: params.message});
				}
				else if (this.MobileActionEqual('DIALOG'))
				{
					if (!this.BXIM.messenger.chat[params.chatId] || !this.BXIM.messenger.userInChat[params.chatId])
						return false;

					var newStack = [];
					for (var i = 0; i < this.BXIM.messenger.userInChat[params.chatId].length; i++)
						if (this.BXIM.messenger.userInChat[params.chatId][i] != params.userId)
							newStack.push(this.BXIM.messenger.userInChat[params.chatId][i]);

					this.BXIM.messenger.userInChat[params.chatId] = newStack;
					this.BXIM.messenger.redrawChatHeader();
				}
			}
			else if (command == 'notify') // TODO mobile
			{
				if (this.MobileActionNotEqual('NOTIFY'))
					return false;

				if (this.BXIM.lastRecordId >= params.id)
					return false;

				params.date = parseInt(params.date)+parseInt(BX.message('USER_TZ_OFFSET'));
				var data = {};
				data.UNREAD_NOTIFY = {};
				data.UNREAD_NOTIFY[params.id] = [params.id];
				this.BXIM.messenger.notify.notify[params.id] = params;
				this.BXIM.messenger.notify.flashNotify[params.id] = params.silent != 'Y';
				if (params.settingName == "main|rating_vote" && params.original_tag.substr(0,16) == "RATING|IMMESSAGE")
				{
					var messageId = params.original_tag.substr(17);
					if (this.BXIM.messenger.message[messageId] && this.BXIM.messenger.message[messageId].recipientId == this.BXIM.messenger.currentTab && this.BXIM.windowFocus)
					{
						delete data.UNREAD_NOTIFY[params.id];
						this.BXIM.notify.flashNotify[params.id] = false;
						this.BXIM.notify.viewNotify(params.id);
					}
				}

				if (params.silent == 'N')
					this.BXIM.notify.changeUnreadNotify(data.UNREAD_NOTIFY);
				BX.localStorage.set('mfn', this.BXIM.notify.flashNotify, 80);
				this.BXIM.lastRecordId = params.id;
			}
			else if (command == 'readNotify')  // TODO mobile
			{
				if (this.MobileActionNotEqual('NOTIFY'))
					return false;

				this.BXIM.notify.initNotifyCount = 0;
				params.lastId = parseInt(params.lastId);
				for (var i in this.BXIM.notify.unreadNotify)
				{
					var notify = this.BXIM.notify.notify[this.BXIM.notify.unreadNotify[i]];
					if (notify && notify.type != 1 && notify.id <= params.lastId)
					{
						delete this.BXIM.notify.unreadNotify[i];
					}
				}
				this.BXIM.notify.updateNotifyCount(false);
			}
			else if (command == 'confirmNotify')  // TODO mobile
			{
				if (this.MobileActionNotEqual('NOTIFY'))
					return false;

				var notifyId = parseInt(params.id);
				delete this.BXIM.notify.notify[notifyId];
				delete this.BXIM.notify.unreadNotify[notifyId];
				delete this.BXIM.notify.flashNotify[notifyId];
				this.BXIM.notify.updateNotifyCount(false);
				if (this.BXIM.messenger.popupMessenger != null && this.BXIM.notifyOpen)
					this.BXIM.notify.openNotify(true);
			}
			else if (command == 'readNotifyOne')  // TODO mobile
			{
				if (this.MobileActionNotEqual('NOTIFY'))
					return false;

				var notify = this.BXIM.notify.notify[params.id];
				if (notify && notify.type != 1)
					delete this.BXIM.notify.unreadNotify[params.id];

				this.BXIM.notify.updateNotifyCount(false);
				if (this.BXIM.messenger.popupMessenger != null && this.BXIM.notifyOpen)
					this.BXIM.notify.openNotify(true);

			}
		}, this));

		BX.addCustomEvent((this.isMobile()? "onPullOnline": "onPullOnlineEvent"), BX.delegate(function(command,params)
		{
			if (this.isMobile())
			{
				params = command.params;
				command = command.command;
			}
			if (command == 'user_online')
			{
				if (this.BXIM.messenger.users[params.USER_ID])
				{
					var contactListRedraw = false;

					if (typeof(this.BXIM.messenger.users[params.USER_ID].idle) == 'undefined')
					{
						this.BXIM.messenger.users[params.USER_ID].idle = 0;
					}
					if (this.BXIM.messenger.users[params.USER_ID].idle != 0)
					{
						this.BXIM.messenger.users[params.USER_ID].idle = 0;
						contactListRedraw = true;
					}

					if (typeof(params.STATUS) != 'undefined')
					{
						if (this.BXIM.messenger.users[params.USER_ID].status != params.STATUS)
						{
							this.BXIM.messenger.users[params.USER_ID].status = params.STATUS;
							contactListRedraw = true;
						}
					}

					if (contactListRedraw)
					{
						this.BXIM.messenger.dialogStatusRedraw();
						this.userListRedraw();
					}
				}
			}
			else if (command == 'user_offline')
			{
				if (this.BXIM.messenger.users[params.USER_ID])
				{
					if (this.BXIM.messenger.users[params.USER_ID].status != 'offline')
					{
						this.BXIM.messenger.users[params.USER_ID].status = 'offline';
						this.BXIM.messenger.users[params.USER_ID].idle = 0;
						this.BXIM.messenger.dialogStatusRedraw();
						BX.MessengerCommon.userListRedraw();
					}
				}
			}
			else if (command == 'user_status')
			{
				if (this.BXIM.messenger.users[params.USER_ID] && params.STATUS)
				{
					var contactListRedraw = false;
					if (typeof(params.IDLE) != 'undefined')
					{
						if (typeof(this.BXIM.messenger.users[params.USER_ID].idle) == 'undefined')
						{
							this.BXIM.messenger.users[params.USER_ID].idle = 0;
						}
						if (this.BXIM.messenger.users[params.USER_ID].idle != params.IDLE)
						{
							this.BXIM.messenger.users[params.USER_ID].idle = params.IDLE;
							contactListRedraw = true;
						}
					}
					if (typeof(params.STATUS) != 'undefined')
					{
						if (this.BXIM.messenger.users[params.USER_ID].status != params.STATUS)
						{
							this.BXIM.messenger.users[params.USER_ID].status = params.STATUS;
							contactListRedraw = true;
						}
					}
					if (contactListRedraw)
					{
						this.BXIM.messenger.dialogStatusRedraw();
						BX.MessengerCommon.userListRedraw();
					}
				}
			}
			else if (command == 'online_list')
			{
				var contactListRedraw = false;
				for (var i in this.BXIM.messenger.users)
				{
					if (typeof(params.USERS[i]) == 'undefined')
					{
						if (this.BXIM.messenger.users[i].status != 'offline')
						{
							this.BXIM.messenger.users[i].status = 'offline';
							this.BXIM.messenger.users[i].idle = 0;
							contactListRedraw = true;
						}
					}
					else
					{
						if (typeof(params.USERS[i].idle) != 'undefined')
						{
							if (typeof(this.BXIM.messenger.users[i].idle) == 'undefined')
							{
								this.BXIM.messenger.users[i].idle = 0;
							}
							if (this.BXIM.messenger.users[i].idle != params.USERS[i].idle)
							{
								this.BXIM.messenger.users[i].idle = params.USERS[i].idle;
								contactListRedraw = true;
							}
						}
						if (typeof(params.USERS[i].status) != 'undefined')
						{
							if (this.BXIM.messenger.users[i].status != params.USERS[i].status)
							{
								this.BXIM.messenger.users[i].status = params.USERS[i].status;
								contactListRedraw = true;
							}
						}
					}
				}
				if (contactListRedraw)
				{
					this.BXIM.messenger.dialogStatusRedraw();
					BX.MessengerCommon.userListRedraw();
				}
			}

		}, this));
	}

	/* Fetch messages */
	BX.MessengerCommon.prototype.updateStateVar = function(data, send, writeMessage)
	{
		writeMessage = writeMessage !== false;
		if (typeof(data.CHAT) != "undefined")
		{
			for (var i in data.CHAT)
			{
				if (this.BXIM.messenger.chat[i] && this.BXIM.messenger.chat[i].fake)
					data.CHAT[i].fake = true;
				else if (!this.BXIM.messenger.chat[i])
					data.CHAT[i].fake = true;

				this.BXIM.messenger.chat[i] = data.CHAT[i];
			}
		}
		if (typeof(data.USER_IN_CHAT) != "undefined")
		{
			for (var i in data.USER_IN_CHAT)
			{
				this.BXIM.messenger.userInChat[i] = data.USER_IN_CHAT[i];
			}
		}
		if (typeof(data.USER_BLOCK_CHAT) != "undefined")
		{
			for (var i in data.USER_BLOCK_CHAT)
			{
				this.BXIM.messenger.userChatBlockStatus[i] = data.USER_BLOCK_CHAT[i];
			}
		}
		if (typeof(data.USERS) != "undefined")
		{
			for (var i in data.USERS)
			{
				this.BXIM.messenger.users[i] = data.USERS[i];
			}
		}
		if (typeof(data.USER_IN_GROUP) != "undefined")
		{
			for (var i in data.USER_IN_GROUP)
			{
				if (typeof(this.BXIM.messenger.userInGroup[i]) == 'undefined')
				{
					this.BXIM.messenger.userInGroup[i] = data.USER_IN_GROUP[i];
				}
				else
				{
					for (var j = 0; j < data.USER_IN_GROUP[i].users.length; j++)
						this.BXIM.messenger.userInGroup[i].users.push(data.USER_IN_GROUP[i].users[j]);

					this.BXIM.messenger.userInGroup[i].users = BX.util.array_unique(this.BXIM.messenger.userInGroup[i].users)
				}
			}
		}
		if (typeof(data.WO_USER_IN_GROUP) != "undefined")
		{
			for (var i in data.WO_USER_IN_GROUP)
			{
				if (typeof(this.BXIM.messenger.woUserInGroup[i]) == 'undefined')
				{
					this.BXIM.messenger.woUserInGroup[i] = data.WO_USER_IN_GROUP[i];
				}
				else
				{
					for (var j = 0; j < data.WO_USER_IN_GROUP[i].users.length; j++)
						this.BXIM.messenger.woUserInGroup[i].users.push(data.WO_USER_IN_GROUP[i].users[j]);

					this.BXIM.messenger.woUserInGroup[i].users = BX.util.array_unique(this.BXIM.messenger.woUserInGroup[i].users)
				}
			}
		}
		if (typeof(data.MESSAGE) != "undefined")
		{
			for (var i in data.MESSAGE)
			{
				this.BXIM.messenger.message[i] = data.MESSAGE[i];
				this.BXIM.lastRecordId = parseInt(i) > this.BXIM.lastRecordId? parseInt(i): this.BXIM.lastRecordId;
			}
		}

		this.changeUnreadMessage(data.UNREAD_MESSAGE, send);

		if (typeof(data.USERS_MESSAGE) != "undefined")
		{
			for (var i in data.USERS_MESSAGE)
			{
				data.USERS_MESSAGE[i].sort(BX.delegate(function(i, ii) {i = parseInt(i); ii = parseInt(ii); if (!this.BXIM.messenger.message[i] || !this.BXIM.messenger.message[ii]){return 0;} var i1 = parseInt(this.BXIM.messenger.message[i].date); var i2 = parseInt(this.BXIM.messenger.message[ii].date); if (i1 < i2) { return -1; } else if (i1 > i2) { return 1;} else{ if (i < ii) { return -1; } else if (i > ii) { return 1;}else{ return 0;}}}, this));
				if (!this.BXIM.messenger.showMessage[i])
					this.BXIM.messenger.showMessage[i] = data.USERS_MESSAGE[i];

				for (var j = 0; j < data.USERS_MESSAGE[i].length; j++)
				{
					if (!BX.util.in_array(data.USERS_MESSAGE[i][j], this.BXIM.messenger.showMessage[i]))
					{
						this.BXIM.messenger.showMessage[i].push(data.USERS_MESSAGE[i][j]);
						if (this.BXIM.messenger.history[i])
							this.BXIM.messenger.history[i] = BX.util.array_merge(this.BXIM.messenger.history[i], data.USERS_MESSAGE[i]);
						else
							this.BXIM.messenger.history[i] = data.USERS_MESSAGE[i];

						if (writeMessage && this.BXIM.messenger.currentTab == i && this.MobileActionEqual('DIALOG'))
							this.drawMessage(i, this.BXIM.messenger.message[data.USERS_MESSAGE[i][j]]);
					}
				}
			}
		}
	};

	BX.MessengerCommon.prototype.changeUnreadMessage = function(unreadMessage, send)
	{
		send = send != false;

		var playSound = false;
		var contactListRedraw = false;
		var needRedrawDialogStatus = true;
		for (var i in unreadMessage)
		{
			var skipPopup = false;
			if (this.BXIM.xmppStatus && i.toString().substr(0,4) != 'chat')
			{
				if (!(this.BXIM.messenger.popupMessenger != null && this.BXIM.messenger.currentTab == i && this.BXIM.isFocus()))
				{
					contactListRedraw = true;
					if (this.BXIM.messenger.unreadMessage[i])
						this.BXIM.messenger.unreadMessage[i] = BX.util.array_unique(BX.util.array_merge(this.BXIM.messenger.unreadMessage[i], unreadMessage[i]));
					else
						this.BXIM.messenger.unreadMessage[i] = unreadMessage[i];
				}
				skipPopup = true;
			}

			if (!skipPopup)
			{
				if (this.BXIM.messenger.popupMessenger != null && this.BXIM.messenger.currentTab == i && this.BXIM.isFocus())
				{
					if (typeof (this.BXIM.messenger.flashMessage[i]) == 'undefined')
						this.BXIM.messenger.flashMessage[i] = {};

					for (var k = 0; k < unreadMessage[i].length; k++)
					{
						if (this.BXIM.isFocus())
							this.BXIM.messenger.flashMessage[i][unreadMessage[i][k]] = false;

						if (this.BXIM.messenger.message[unreadMessage[i][k]] && this.BXIM.messenger.message[unreadMessage[i][k]].senderId == this.BXIM.messenger.currentTab)
							playSound = true;
					}
					this.readMessage(i, true, true, true);
				}
				else if (this.isMobile() && this.BXIM.messenger.currentTab == i)
				{
					var dialogId = this.BXIM.messenger.currentTab;
					this.BXIM.isFocusMobile(BX.delegate(function(visible){
						if (visible)
						{
							BX.MessengerCommon.readMessage(dialogId, true, true, true);
						}
					},this));
					if (this.BXIM.messenger.unreadMessage[dialogId])
						this.BXIM.messenger.unreadMessage[dialogId] = BX.util.array_unique(BX.util.array_merge(this.BXIM.messenger.unreadMessage[dialogId], unreadMessage[dialogId]));
					else
						this.BXIM.messenger.unreadMessage[dialogId] = unreadMessage[dialogId];
				}
				else
				{
					contactListRedraw = true;
					if (this.BXIM.messenger.unreadMessage[i])
						this.BXIM.messenger.unreadMessage[i] = BX.util.array_unique(BX.util.array_merge(this.BXIM.messenger.unreadMessage[i], unreadMessage[i]));
					else
						this.BXIM.messenger.unreadMessage[i] = unreadMessage[i];

					if (typeof (this.BXIM.messenger.flashMessage[i]) == 'undefined')
					{
						this.BXIM.messenger.flashMessage[i] = {};
						for (var k = 0; k < unreadMessage[i].length; k++)
						{
							var resultOfNameSearch = this.BXIM.messenger.message[unreadMessage[i][k]].text.match(new RegExp("("+this.BXIM.messenger.users[this.BXIM.userId].name.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&")+")",'ig'));
							if (this.BXIM.settings.status != 'dnd' || resultOfNameSearch)
							{
								this.BXIM.messenger.flashMessage[i][unreadMessage[i][k]] = send;
							}
						}
					}
					else
					{
						for (var k = 0; k < unreadMessage[i].length; k++)
						{
							var resultOfNameSearch = this.BXIM.messenger.message[unreadMessage[i][k]].text.match(new RegExp("("+this.BXIM.messenger.users[this.BXIM.userId].name.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&")+")",'ig'));
							if (this.BXIM.settings.status != 'dnd' || resultOfNameSearch)
							{
								if (!send && !this.BXIM.isFocus())
								{
									this.BXIM.messenger.flashMessage[i][unreadMessage[i][k]] = false;
								}
								else
								{
									if (typeof (this.BXIM.messenger.flashMessage[i][unreadMessage[i][k]]) == 'undefined')
										this.BXIM.messenger.flashMessage[i][unreadMessage[i][k]] = true;
								}
							}
						}
					}
				}
			}

			var arRecent = false;
			for (var k = 0; k < unreadMessage[i].length; k++)
			{
				if (!arRecent || arRecent.SEND_DATE <= parseInt(this.BXIM.messenger.message[unreadMessage[i][k]].date)+parseInt(BX.message("SERVER_TZ_OFFSET")))
				{
					arRecent = {
						'ID': this.BXIM.messenger.message[unreadMessage[i][k]].id,
						'SEND_DATE': parseInt(this.BXIM.messenger.message[unreadMessage[i][k]].date)+parseInt(BX.message("SERVER_TZ_OFFSET")),
						'RECIPIENT_ID': this.BXIM.messenger.message[unreadMessage[i][k]].recipientId,
						'SENDER_ID': this.BXIM.messenger.message[unreadMessage[i][k]].senderId,
						'USER_ID': this.BXIM.messenger.message[unreadMessage[i][k]].senderId,
						'SEND_MESSAGE': this.BXIM.messenger.message[unreadMessage[i][k]].text,
						'PARAMS': this.BXIM.messenger.message[unreadMessage[i][k]].params
					};
				}
			}
			if (arRecent)
			{
				BX.MessengerCommon.recentListAdd({
					'userId': arRecent.RECIPIENT_ID.toString().substr(0,4) == 'chat'? arRecent.RECIPIENT_ID: arRecent.USER_ID,
					'id': arRecent.ID,
					'date': arRecent.SEND_DATE,
					'recipientId': arRecent.RECIPIENT_ID,
					'senderId': arRecent.SENDER_ID,
					'text': arRecent.SEND_MESSAGE,
					'params': arRecent.PARAMS
				}, true);
			}
			if (this.MobileActionEqual('DIALOG') && this.BXIM.messenger.popupMessenger != null && this.BXIM.messenger.currentTab == i)
			{
				needRedrawDialogStatus = true;
			}
		}
		if (needRedrawDialogStatus)
		{
			this.BXIM.messenger.dialogStatusRedraw(this.isMobile()? {type: 1, slidingPanelRedrawDisable: true}: {});
		}

		if (this.MobileActionEqual('RECENT') && this.BXIM.messenger.popupMessenger != null && !this.BXIM.messenger.recentList && contactListRedraw)
			BX.MessengerCommon.userListRedraw();

		if (!this.isMobile())
		{
			this.BXIM.messenger.newMessage(send);
			this.BXIM.messenger.updateMessageCount(send);

			if (send && playSound && this.BXIM.settings.status != 'dnd')
			{
				this.BXIM.playSound("newMessage2");
			}
		}
	}

	BX.MessengerCommon.prototype.readMessage = function(userId, send, sendAjax, skipCheck)
	{
		if (!userId)
			return false;

		skipCheck = skipCheck == true;
		if (!skipCheck && (!this.BXIM.messenger.unreadMessage[userId] || this.BXIM.messenger.unreadMessage[userId].length <= 0))
			return false;

		send = send != false;
		sendAjax = sendAjax !== false;

		if (this.BXIM.messenger.popupMessenger != null)
		{
			var elements = BX.findChildrenByClassName(this.BXIM.messenger.popupContactListElementsWrap, "bx-messenger-cl-id-"+userId);
			if (elements != null)
				for (var i = 0; i < elements.length; i++)
					elements[i].firstChild.innerHTML = '';

			elements = BX.findChildrenByClassName(this.BXIM.messenger.popupMessengerBodyWrap, "bx-messenger-content-item-new", false);
			if (elements != null)
				for (var i = 0; i < elements.length; i++)
					if (elements[i].getAttribute('data-notifyType') != 1)
						BX.removeClass(elements[i], 'bx-messenger-content-item-new');
		}
		var lastId = 0;
		if (Math && this.BXIM.messenger.unreadMessage[userId])
			lastId = Math.max.apply(Math, this.BXIM.messenger.unreadMessage[userId]);

		if (this.BXIM.messenger.unreadMessage[userId])
			delete this.BXIM.messenger.unreadMessage[userId];

		if (this.BXIM.messenger.flashMessage[userId])
			delete this.BXIM.messenger.flashMessage[userId];

		BX.localStorage.set('mfm', this.BXIM.messenger.flashMessage, 80);

		if (!this.isMobile())
		{
			this.BXIM.messenger.updateMessageCount(send);
		}

		if (sendAjax)
		{
			clearTimeout(this.BXIM.messenger.readMessageTimeout[userId+'_'+this.BXIM.messenger.currentTab]);
			this.BXIM.messenger.readMessageTimeout[userId+'_'+this.BXIM.messenger.currentTab] = setTimeout(BX.delegate(function(){
				var sendData = {'IM_READ_MESSAGE' : 'Y', 'USER_ID' : userId, 'TAB' : this.BXIM.messenger.currentTab, 'IM_AJAX_CALL' : 'Y', 'sessid': BX.bitrix_sessid()};
				if (parseInt(lastId) > 0)
					sendData['LAST_ID'] = lastId;
				var _ajax = BX.ajax({
					url: this.BXIM.pathToAjax+'?READ_MESSAGE&V='+this.BXIM.revision,
					method: 'POST',
					dataType: 'json',
					timeout: 60,
					skipAuthCheck: true,
					data: sendData,
					onsuccess: BX.delegate(function(data)
					{
						if (data && data.BITRIX_SESSID)
						{
							BX.message({'bitrix_sessid': data.BITRIX_SESSID});
						}
						if (data.ERROR != '')
						{
							if (data.ERROR == 'SESSION_ERROR' && this.BXIM.messenger.sendAjaxTry < 2)
							{
								this.BXIM.messenger.sendAjaxTry++;
								setTimeout(BX.delegate(function(){
									this.readMessage(userId, false, true);
								}, this), 2000);
								BX.onCustomEvent(window, 'onImError', [data.ERROR, data.BITRIX_SESSID]);
							}
							else if (data.ERROR == 'AUTHORIZE_ERROR')
							{
								this.BXIM.messenger.sendAjaxTry++;
								if (this.BXIM.desktop && this.BXIM.desktop.ready())
								{
									setTimeout(BX.delegate(function(){
										this.readMessage(userId, false, true);
									}, this), 10000);
								}
								BX.onCustomEvent(window, 'onImError', [data.ERROR]);
							}
						}
					}, this),
					onfailure: BX.delegate(function()	{
						this.BXIM.messenger.sendAjaxTry = 0;
						try {
							if (typeof(_ajax) == 'object' && _ajax.status == 0)
								BX.onCustomEvent(window, 'onImError', ['CONNECT_ERROR']);
						}
						catch(e) {}
					}, this)
				});
			}, this), 200);
		}
		if (send)
		{
			BX.localStorage.set('mrm', userId, 5);
			BX.localStorage.set('mnnb', true, 1);
		}
	};

	BX.MessengerCommon.prototype.drawReadMessage = function(userId, messageId, date, animation)
	{
		var lastId = Math.max.apply(Math, this.BXIM.messenger.showMessage[userId]);
		if (lastId != messageId || this.BXIM.messenger.message[lastId].senderId == userId)
		{
			this.BXIM.messenger.readedList[userId] = false;
			return false;
		}

		this.BXIM.messenger.readedList[userId] = {
			'messageId' : messageId,
			'date' : date
		};
		if (!this.countWriting(userId))
		{
			animation = animation != false;

			this.drawNotifyMessage(userId, 'readed', BX.message('IM_M_READED').replace('#DATE#', this.formatDate(date)), animation);
		}
	};

	BX.MessengerCommon.prototype.drawNotifyMessage = function(userId, icon, message, animation)
	{
		if (this.BXIM.messenger.popupMessenger == null || userId != this.BXIM.messenger.currentTab || typeof(message) == 'undefined' || typeof(icon) == 'undefined' || userId == 0)
			return false;

		var lastChild = this.BXIM.messenger.popupMessengerBodyWrap.lastChild;
		if (BX.hasClass(lastChild, "bx-messenger-content-empty"))
			return false;

		var arMessage = BX.create("div", { attrs : { 'data-type': 'notify'}, props: { className : "bx-messenger-content-item bx-messenger-content-item-notify"}, children: [
			BX.create("span", { props : { className : "bx-messenger-content-item-content"}, children : [
				BX.create("span", { props : { className : "bx-messenger-content-item-text-center"}, children: [
					BX.create("span", {  props : { className : "bx-messenger-content-item-text-message"}, html: '<span class="bx-messenger-content-item-notify-icon-'+icon+'"></span>'+this.prepareText(message, false, true, true)})
				]})
			]})
		]});

		if (BX.hasClass(lastChild, "bx-messenger-content-item-notify"))
			BX.remove(lastChild);

		this.BXIM.messenger.popupMessengerBodyWrap.appendChild(arMessage);

		animation = animation != false;
		if (this.BXIM.messenger.popupMessengerBody && BX.MessengerCommon.enableScroll(this.BXIM.messenger.popupMessengerBody, this.BXIM.messenger.popupMessengerBody.offsetHeight))
		{
			if (this.BXIM.animationSupport && animation)
			{
				if (this.BXIM.messenger.popupMessengerBodyAnimation != null)
					this.BXIM.messenger.popupMessengerBodyAnimation.stop();
				(this.BXIM.messenger.popupMessengerBodyAnimation = new BX.easing({
					duration : 1200,
					start : { scroll : this.BXIM.messenger.popupMessengerBody.scrollTop},
					finish : { scroll : this.BXIM.messenger.popupMessengerBody.scrollHeight - this.BXIM.messenger.popupMessengerBody.offsetHeight*(this.isMobile()? 0: 1)},
					transition : BX.easing.makeEaseInOut(BX.easing.transitions.quart),
					step : BX.delegate(function(state){
						this.BXIM.messenger.popupMessengerBody.scrollTop = state.scroll;
					}, this)
				})).animate();
			}
			else
			{
				this.BXIM.messenger.popupMessengerBody.scrollTop = this.BXIM.messenger.popupMessengerBody.scrollHeight - this.BXIM.messenger.popupMessengerBody.offsetHeight*(this.isMobile()? 0: 1);
			}
		}
	};

	BX.MessengerCommon.prototype.loadHistory = function(userId, isHistoryDialog)
	{
		isHistoryDialog = typeof(isHistoryDialog) == 'undefined'? true: isHistoryDialog;

		if (!this.BXIM.messenger.historyEndOfList[userId])
			this.BXIM.messenger.historyEndOfList[userId] = {};

		if (!this.BXIM.messenger.historyLoadFlag[userId])
			this.BXIM.messenger.historyLoadFlag[userId] = {};

		if (this.BXIM.messenger.historyLoadFlag[userId] && this.BXIM.messenger.historyLoadFlag[userId][isHistoryDialog])
		{
			if (this.isMobile())
				app.pullDownLoadingStop();
			return;
		}

		if (this.isMobile())
		{
			isHistoryDialog = false;
		}
		else
		{
			if (isHistoryDialog)
			{
				if (this.BXIM.messenger.historySearch != "" || this.BXIM.messenger.historyDateSearch != "")
					return;

				if (!(this.BXIM.messenger.popupHistoryItems.scrollTop > this.BXIM.messenger.popupHistoryItems.scrollHeight - this.BXIM.messenger.popupHistoryItems.offsetHeight - 100))
					return;
			}
			else
			{
				if (this.BXIM.messenger.popupMessengerBody.scrollTop >= 5)
					return;
			}
		}

		if (!this.BXIM.messenger.historyEndOfList[userId] || !this.BXIM.messenger.historyEndOfList[userId][isHistoryDialog])
		{
			var elements = [];
			if (isHistoryDialog)
			{
				elements = BX.findChildrenByClassName(this.BXIM.messenger.popupHistoryBodyWrap, "bx-messenger-history-item-text");
			}
			else
			{
				elements = BX.findChildrenByClassName(this.BXIM.messenger.popupMessengerBodyWrap, "bx-messenger-content-item-text-wrap");
			}

			if (!this.isMobile() && elements.length < 20)
			{
				return false;
			}

			if (elements.length > 0)
				this.BXIM.messenger.historyOpenPage[userId] = Math.floor(elements.length/20)+1;
			else
				this.BXIM.messenger.historyOpenPage[userId] = 1;

			var tmpLoadMoreWait = null;
			if (!this.isMobile())
			{
				tmpLoadMoreWait = BX.create("div", { props : { className : "bx-messenger-content-load-more-history" }, children : [
					BX.create('span', { props : { className : "bx-messenger-content-load-img" }}),
					BX.create("span", { props : { className : "bx-messenger-content-load-text" }, html : BX.message('IM_M_LOAD_MESSAGE')})
				]});
				if (isHistoryDialog)
				{
					this.BXIM.messenger.popupHistoryBodyWrap.appendChild(tmpLoadMoreWait);
				}
				else
				{
					this.BXIM.messenger.popupMessengerBodyWrap.insertBefore(tmpLoadMoreWait, this.BXIM.messenger.popupMessengerBodyWrap.firstChild);
				}
			}

			if (!this.BXIM.messenger.historyLoadFlag[userId])
				this.BXIM.messenger.historyLoadFlag[userId] = {};

			this.BXIM.messenger.historyLoadFlag[userId][isHistoryDialog] = true;

			BX.ajax({
				url: this.BXIM.pathToAjax+'?HISTORY_LOAD_MORE&V='+this.BXIM.revision,
				method: 'POST',
				dataType: 'json',
				timeout: 30,
				data: {'IM_HISTORY_LOAD_MORE' : 'Y', 'USER_ID' : userId, 'PAGE_ID' : this.BXIM.messenger.historyOpenPage[userId], 'IM_AJAX_CALL' : 'Y', 'sessid': BX.bitrix_sessid()},
				onsuccess: BX.delegate(function(data){
					if (tmpLoadMoreWait)
						BX.remove(tmpLoadMoreWait);

					if (this.isMobile())
						app.pullDownLoadingStop();

					this.BXIM.messenger.historyLoadFlag[userId][isHistoryDialog] = false;

					if (data.MESSAGE.length == 0)
					{
						this.BXIM.messenger.historyEndOfList[userId][isHistoryDialog] = true;
						return;
					}

					for (var i in data.FILES)
					{
						if (!this.BXIM.disk.files[data.CHAT_ID])
							this.BXIM.disk.files[data.CHAT_ID] = {};
						if (this.BXIM.disk.files[data.CHAT_ID][i])
							continue;
						data.FILES[i].date = parseInt(data.FILES[i].date)+parseInt(BX.message('USER_TZ_OFFSET'));
						this.BXIM.disk.files[data.CHAT_ID][i] = data.FILES[i];
					}

					var countMessages = 0;
					for (var i in data.MESSAGE)
					{
						data.MESSAGE[i].date = parseInt(data.MESSAGE[i].date)+parseInt(BX.message('USER_TZ_OFFSET'));
						this.BXIM.messenger.message[i] = data.MESSAGE[i];

						countMessages++;
					}
					if (countMessages < 20)
					{
						this.BXIM.messenger.historyEndOfList[userId][isHistoryDialog] = true;
					}
					for (var i in data.USERS_MESSAGE)
					{
						if (isHistoryDialog)
						{
							if (this.BXIM.messenger.history[i])
								this.BXIM.messenger.history[i] = BX.util.array_merge(this.BXIM.messenger.history[i], data.USERS_MESSAGE[i]);
							else
								this.BXIM.messenger.history[i] = data.USERS_MESSAGE[i];
						}
						else
						{
							if (this.BXIM.messenger.showMessage[i])
								this.BXIM.messenger.showMessage[i] = BX.util.array_unique(BX.util.array_merge(data.USERS_MESSAGE[i], this.BXIM.messenger.showMessage[i]));
							else
								this.BXIM.messenger.showMessage[i] = data.USERS_MESSAGE[i];
						}
					}
					if (isHistoryDialog)
					{
						for (var i = 0; i < data.USERS_MESSAGE[userId].length; i++)
						{
							var history = this.BXIM.messenger.message[data.USERS_MESSAGE[userId][i]];
							if (history)
							{
								if (BX('im-message-history-'+history.id))
									continue;

								var dateGroupTitle = BX.MessengerCommon.formatDate(history.date, BX.MessengerCommon.getDateFormatType('MESSAGE_TITLE'));
								if (!BX('bx-im-history-'+dateGroupTitle))
								{
									var dateGroupTitleNode = BX.create("div", {props : { className: "bx-messenger-content-group bx-messenger-content-group-history"}, children : [
										BX.create("div", {attrs: {id: 'bx-im-history-'+dateGroupTitle}, props : { className: "bx-messenger-content-group-title"+(this.BXIM.language == 'ru'? ' bx-messenger-lowercase': '')}, html : dateGroupTitle})
									]});
									this.BXIM.messenger.popupHistoryBodyWrap.appendChild(dateGroupTitleNode);
								}

								var history = this.BXIM.messenger.drawMessageHistory(history);
								if (history)
									this.BXIM.messenger.popupHistoryBodyWrap.appendChild(history);
							}
						}
					}
					else
					{
						var lastChildBeforeChangeDom = this.BXIM.messenger.popupMessengerBodyWrap.firstChild.nextSibling;
						lastChildBeforeChangeDom = BX('im-message-'+lastChildBeforeChangeDom.getAttribute('data-blockmessageid'));

						for (var i = 0; i < data.USERS_MESSAGE[userId].length; i++)
						{
							var history = this.BXIM.messenger.message[data.USERS_MESSAGE[userId][i]];
							if (history)
							{
								if (BX('im-message-'+history.id))
									continue;

								BX.MessengerCommon.drawMessage(userId, history, false, true);
							}
						}
						this.BXIM.messenger.popupMessengerBody.scrollTop = lastChildBeforeChangeDom.offsetTop-this.BXIM.messenger.popupMessengerBody.offsetTop-lastChildBeforeChangeDom.offsetHeight-100;
					}
				}, this),
				onfailure: BX.delegate(function(){
					if (tmpLoadMoreWait)
						BX.remove(tmpLoadMoreWait);
					if (this.isMobile())
						app.pullDownLoadingStop();
				},this)
			});
		}
	};

	BX.MessengerCommon.prototype.loadUserData = function(userId)
	{
		BX.ajax({
			url: this.BXIM.pathToAjax+'?USER_DATA_LOAD&V='+this.BXIM.revision,
			method: 'POST',
			dataType: 'json',
			timeout: 30,
			data: {'IM_USER_DATA_LOAD' : 'Y', 'USER_ID' : userId, 'IM_AJAX_CALL' : 'Y', 'sessid': BX.bitrix_sessid()},
			onsuccess: BX.delegate(function(data)
			{
				if (data.ERROR == '')
				{
					this.BXIM.messenger.userChat[userId] = data.CHAT_ID;

					BX.MessengerCommon.getUserParam(userId, true);
					this.BXIM.messenger.users[userId].name = BX.message('IM_M_USER_NO_ACCESS');

					for (var i in data.USERS)
					{
						this.BXIM.messenger.users[i] = data.USERS[i];
					}
					for (var i in data.PHONES)
					{
						this.BXIM.messenger.phones[i] = {};
						for (var j in data.PHONES[i])
						{
							this.BXIM.messenger.phones[i][j] = BX.util.htmlspecialcharsback(data.PHONES[i][j]);
						}
					}
					for (var i in data.USER_IN_GROUP)
					{
						if (typeof(this.BXIM.messenger.userInGroup[i]) == 'undefined')
						{
							this.BXIM.messenger.userInGroup[i] = data.USER_IN_GROUP[i];
						}
						else
						{
							for (var j = 0; j < data.USER_IN_GROUP[i].users.length; j++)
								this.BXIM.messenger.userInGroup[i].users.push(data.USER_IN_GROUP[i].users[j]);

							this.BXIM.messenger.userInGroup[i].users = BX.util.array_unique(this.BXIM.messenger.userInGroup[i].users)
						}
					}
					for (var i in data.WO_USER_IN_GROUP)
					{
						if (typeof(this.BXIM.messenger.woUserInGroup[i]) == 'undefined')
						{
							this.BXIM.messenger.woUserInGroup[i] = data.WO_USER_IN_GROUP[i];
						}
						else
						{
							for (var j = 0; j < data.WO_USER_IN_GROUP[i].users.length; j++)
								this.BXIM.messenger.woUserInGroup[i].users.push(data.WO_USER_IN_GROUP[i].users[j]);

							this.BXIM.messenger.woUserInGroup[i].users = BX.util.array_unique(this.BXIM.messenger.woUserInGroup[i].users)
						}
					}

					this.BXIM.messenger.dialogStatusRedraw();
				}
				else
				{
					this.BXIM.messenger.redrawTab[userId] = true;
					if (data.ERROR == 'ACCESS_DENIED')
					{
						this.BXIM.messenger.currentTab = 0;
						this.BXIM.messenger.openChatFlag = false;
						this.BXIM.messenger.openCallFlag = false;
						this.BXIM.messenger.extraClose();
					}
				}
			}, this)
		});
	};

	BX.MessengerCommon.prototype.loadChatData = function(chatId)
	{
		BX.ajax({
			url: this.BXIM.pathToAjax+'?CHAT_DATA_LOAD&V='+this.BXIM.revision,
			method: 'POST',
			dataType: 'json',
			timeout: 30,
			data: {'IM_CHAT_DATA_LOAD' : 'Y', 'CHAT_ID' : chatId, 'IM_AJAX_CALL' : 'Y', 'sessid': BX.bitrix_sessid()},
			onsuccess: BX.delegate(function(data)
			{
				if (data.ERROR == '')
				{
					if (this.BXIM.messenger.chat[data.CHAT_ID].fake)
					{
						this.BXIM.messenger.chat[data.CHAT_ID].name = BX.message('IM_M_USER_NO_ACCESS');
					}

					for (var i in data.CHAT)
					{
						this.BXIM.messenger.chat[i] = data.CHAT[i];
					}
					for (var i in data.USER_IN_CHAT)
					{
						this.BXIM.messenger.userInChat[i] = data.USER_IN_CHAT[i];
					}
					for (var i in data.USER_BLOCK_CHAT)
					{
						this.BXIM.messenger.userChatBlockStatus[i] = data.USER_BLOCK_CHAT[i];
					}
					if (this.BXIM.messenger.currentTab == 'chat'+data.CHAT_ID)
					{
						if (this.BXIM.messenger.chat[data.CHAT_ID] && this.BXIM.messenger.chat[data.CHAT_ID].style == 'call')
						{
							this.BXIM.messenger.openCallFlag = true;
						}
					}
					this.BXIM.messenger.dialogStatusRedraw();
				}
			}, this)
		});
	};

	BX.MessengerCommon.prototype.loadLastMessage = function(userId, userIsChat)
	{
		if (this.BXIM.messenger.loadLastMessageTimeout[userId])
			return false;

		this.BXIM.messenger.historyWindowBlock = true;

		delete this.BXIM.messenger.redrawTab[userId];
		this.BXIM.messenger.loadLastMessageTimeout[userId] = true;

		BX.ajax({
			url: this.BXIM.pathToAjax+'?LOAD_LAST_MESSAGE&V='+this.BXIM.revision,
			method: 'POST',
			dataType: 'json',
			skipAuthCheck: true,
			timeout: 90,
			data: {'IM_LOAD_LAST_MESSAGE' : 'Y', 'CHAT' : userIsChat? 'Y': 'N', 'USER_ID' : userId, 'USER_LOAD' : userIsChat? (this.BXIM.messenger.chat[userId.toString().substr(4)] && this.BXIM.messenger.chat[userId.toString().substr(4)].fake? 'Y': 'N'): 'Y', 'TAB' : this.BXIM.messenger.currentTab, 'READ' : this.BXIM.messenger.BXIM.isFocus()? 'Y': 'N', 'MOBILE' : this.isMobile()? 'Y': 'N', 'IM_AJAX_CALL' : 'Y', 'sessid': BX.bitrix_sessid()},
			onsuccess: BX.delegate(function(data)
			{
				this.BXIM.messenger.loadLastMessageTimeout[userId] = false;

				if (!data)
					return false;

				if (data && data.BITRIX_SESSID)
				{
					BX.message({'bitrix_sessid': data.BITRIX_SESSID});
				}

				if (data.ERROR == '')
				{
					if (!userIsChat)
					{
						this.BXIM.messenger.userChat[userId] = data.CHAT_ID;

						BX.MessengerCommon.getUserParam(userId, true);
						this.BXIM.messenger.users[userId].name = BX.message('IM_M_USER_NO_ACCESS');
					}

					for (var i in data.USERS)
					{
						this.BXIM.messenger.users[i] = data.USERS[i];
					}

					for (var i in data.PHONES)
					{
						this.BXIM.messenger.phones[i] = {};
						for (var j in data.PHONES[i])
						{
							this.BXIM.messenger.phones[i][j] = BX.util.htmlspecialcharsback(data.PHONES[i][j]);
						}
					}
					for (var i in data.USER_IN_GROUP)
					{
						if (typeof(this.BXIM.messenger.userInGroup[i]) == 'undefined')
						{
							this.BXIM.messenger.userInGroup[i] = data.USER_IN_GROUP[i];
						}
						else
						{
							for (var j = 0; j < data.USER_IN_GROUP[i].users.length; j++)
								this.BXIM.messenger.userInGroup[i].users.push(data.USER_IN_GROUP[i].users[j]);

							this.BXIM.messenger.userInGroup[i].users = BX.util.array_unique(this.BXIM.messenger.userInGroup[i].users)
						}
					}
					for (var i in data.WO_USER_IN_GROUP)
					{
						if (typeof(this.BXIM.messenger.woUserInGroup[i]) == 'undefined')
						{
							this.BXIM.messenger.woUserInGroup[i] = data.WO_USER_IN_GROUP[i];
						}
						else
						{
							for (var j = 0; j < data.WO_USER_IN_GROUP[i].users.length; j++)
								this.BXIM.messenger.woUserInGroup[i].users.push(data.WO_USER_IN_GROUP[i].users[j]);

							this.BXIM.messenger.woUserInGroup[i].users = BX.util.array_unique(this.BXIM.messenger.woUserInGroup[i].users)
						}
					}

					for (var i in data.READED_LIST)
					{
						data.READED_LIST[i].date = parseInt(data.READED_LIST[i].date)+parseInt(BX.message('USER_TZ_OFFSET'));
						this.BXIM.messenger.readedList[i] = data.READED_LIST[i];
					}

					if (!userIsChat && data.USER_LOAD == 'Y')
						BX.MessengerCommon.userListRedraw();

					for (var i in data.FILES)
					{
						if (!this.BXIM.messenger.disk.files[data.CHAT_ID])
							this.BXIM.messenger.disk.files[data.CHAT_ID] = {};

						data.FILES[i].date = parseInt(data.FILES[i].date)+parseInt(BX.message('USER_TZ_OFFSET'));
						this.BXIM.messenger.disk.files[data.CHAT_ID][i] = data.FILES[i];
					}

					this.BXIM.messenger.sendAjaxTry = 0;
					var messageCnt = 0;
					for (var i in data.MESSAGE)
					{
						messageCnt++;
						data.MESSAGE[i].date = parseInt(data.MESSAGE[i].date)+parseInt(BX.message('USER_TZ_OFFSET'));
						this.BXIM.messenger.message[i] = data.MESSAGE[i];
						this.BXIM.lastRecordId = parseInt(i) > this.BXIM.lastRecordId? parseInt(i): this.BXIM.lastRecordId;
					}

					if (messageCnt <= 0)
						delete this.BXIM.messenger.redrawTab[data.USER_ID];

					for (var i in data.USERS_MESSAGE)
					{
						if (this.BXIM.messenger.showMessage[i])
							this.BXIM.messenger.showMessage[i] = BX.util.array_unique(BX.util.array_merge(data.USERS_MESSAGE[i], this.BXIM.messenger.showMessage[i]));
						else
							this.BXIM.messenger.showMessage[i] = data.USERS_MESSAGE[i];
					}
					if (userIsChat && this.BXIM.messenger.chat[data.USER_ID.substr(4)].fake)
					{
						this.BXIM.messenger.chat[data.USER_ID.toString().substr(4)].name = BX.message('IM_M_USER_NO_ACCESS');
					}

					for (var i in data.CHAT)
					{
						this.BXIM.messenger.chat[i] = data.CHAT[i];
					}
					for (var i in data.USER_IN_CHAT)
					{
						this.BXIM.messenger.userInChat[i] = data.USER_IN_CHAT[i];
					}
					for (var i in data.USER_BLOCK_CHAT)
					{
						this.BXIM.messenger.userChatBlockStatus[i] = data.USER_BLOCK_CHAT[i];
					}
					if (this.BXIM.messenger.currentTab == data.USER_ID)
					{
						if (this.BXIM.messenger.currentTab.toString().substr(0, 4) == 'chat' && this.BXIM.messenger.chat[this.BXIM.messenger.currentTab.toString().substr(4)] && this.BXIM.messenger.chat[this.BXIM.messenger.currentTab.toString().substr(4)].style == 'call')
						{
							this.BXIM.messenger.openCallFlag = true;
						}
					}
					BX.MessengerCommon.drawTab(data.USER_ID, this.BXIM.messenger.currentTab == data.USER_ID);

					if (this.BXIM.messenger.currentTab == data.USER_ID && this.BXIM.messenger.readedList[data.USER_ID])
						BX.MessengerCommon.drawReadMessage(data.USER_ID, this.BXIM.messenger.readedList[data.USER_ID].messageId, this.BXIM.messenger.readedList[data.USER_ID].date, false);

					this.BXIM.messenger.historyWindowBlock = false;

					if (this.BXIM.isFocus())
					{
						BX.MessengerCommon.readMessage(data.USER_ID, true, false);
					}
				}
				else
				{
					this.BXIM.messenger.redrawTab[userId] = true;
					if (data.ERROR == 'ACCESS_DENIED')
					{
						this.BXIM.messenger.currentTab = 0;
						this.BXIM.messenger.openChatFlag = false;
						this.BXIM.messenger.openCallFlag = false;
						this.BXIM.messenger.extraClose();
					}
					else if (data.ERROR == 'SESSION_ERROR' && this.BXIM.messenger.sendAjaxTry < 2)
					{
						this.BXIM.messenger.sendAjaxTry++;
						setTimeout(BX.delegate(function(){this.loadLastMessage(userId, userIsChat)}, this), 2000);
						BX.onCustomEvent(window, 'onImError', [data.ERROR, data.BITRIX_SESSID]);
					}
					else if (data.ERROR == 'AUTHORIZE_ERROR')
					{
						this.BXIM.messenger.sendAjaxTry++;
						if (this.BXIM.desktop && this.BXIM.desktop.ready())
						{
							setTimeout(BX.delegate(function (){
								this.loadLastMessage(userId, userIsChat)
							}, this), 10000);
						}
						BX.onCustomEvent(window, 'onImError', [data.ERROR]);
					}
				}
			}, this),
			onfailure: BX.delegate(function(){
				this.BXIM.messenger.loadLastMessageTimeout[userId] = false;
				this.BXIM.messenger.historyWindowBlock = false;
				this.BXIM.messenger.sendAjaxTry = 0;
				this.BXIM.messenger.redrawTab[userId] = true;
			}, this)
		});
	};

	BX.MessengerCommon.prototype.openDialog = function(userId, extraClose, callToggle)
	{
		var user = BX.MessengerCommon.getUserParam(userId);
		if (user.id <= 0)
			return false;

		this.BXIM.messenger.currentTab = userId;
		if (userId.toString().substr(0,4) == 'chat')
		{
			this.BXIM.messenger.openChatFlag = true;
			if (this.BXIM.messenger.chat[userId.toString().substr(4)] && this.BXIM.messenger.chat[userId.toString().substr(4)].style == 'call')
				this.BXIM.messenger.openCallFlag = true;
		}
		BX.localStorage.set('mct', this.BXIM.messenger.currentTab, 15);

		this.BXIM.messenger.dialogStatusRedraw();

		if (!this.isMobile())
		{
			this.BXIM.messenger.popupMessengerPanel.className  = this.BXIM.messenger.openChatFlag? 'bx-messenger-panel bx-messenger-hide': 'bx-messenger-panel';
			if (this.BXIM.messenger.openChatFlag)
			{
				this.BXIM.messenger.popupMessengerPanel2.className = this.BXIM.messenger.openCallFlag? 'bx-messenger-panel bx-messenger-hide': 'bx-messenger-panel';
				this.BXIM.messenger.popupMessengerPanel3.className = this.BXIM.messenger.openCallFlag? 'bx-messenger-panel': 'bx-messenger-panel bx-messenger-hide';
			}
			else
			{
				this.BXIM.messenger.popupMessengerPanel2.className = 'bx-messenger-panel bx-messenger-hide';
				this.BXIM.messenger.popupMessengerPanel3.className = 'bx-messenger-panel bx-messenger-hide';
			}
		}

		extraClose = extraClose == true;
		callToggle = callToggle != false;

		var arMessage = [];
		if (typeof(this.BXIM.messenger.showMessage[userId]) != 'undefined' && this.BXIM.messenger.showMessage[userId].length > 0)
		{
			if (!user.fake && this.BXIM.messenger.showMessage[userId].length >= 15)
			{
				this.BXIM.messenger.redrawTab[userId] = false;
			}
			else
			{
				this.drawTab(userId, true);
				this.BXIM.messenger.redrawTab[userId] = true;
			}
		}
		else if (this.BXIM.messenger.popupMessengerConnectionStatusState != 'online')
		{
			arMessage = [BX.create("div", { props : { className : "bx-messenger-content-empty"}, children : [
				BX.create("span", { props : { className : "bx-messenger-content-load-text"}, html: BX.message("IM_M_LOAD_ERROR")})
			]})];
			this.BXIM.messenger.redrawTab[userId] = true;
		}
		else if (typeof(this.BXIM.messenger.showMessage[userId]) == 'undefined')
		{
			arMessage = [BX.create("div", { props : { className : "bx-messenger-content-load"}, children : [
				BX.create('span', { props : { className : "bx-messenger-content-load-img" }}),
				BX.create("span", { props : { className : "bx-messenger-content-load-text"}, html: BX.message('IM_M_LOAD_MESSAGE')})
			]})];
			this.BXIM.messenger.redrawTab[userId] = true;
		}
		else if (this.BXIM.messenger.redrawTab[userId] && this.BXIM.messenger.showMessage[userId].length == 0)
		{
			arMessage = [BX.create("div", { props : { className : "bx-messenger-content-load"}, children : [
				BX.create('span', { props : { className : "bx-messenger-content-load-img" }}),
				BX.create("span", { props : { className : "bx-messenger-content-load-text"}, html: BX.message("IM_M_LOAD_MESSAGE")})
			]})];
			this.BXIM.messenger.showMessage[userId] = [];
		}
		else
		{
			arMessage = [BX.create("div", { props : { className : "bx-messenger-content-empty"}, children : [
				BX.create("span", { props : { className : "bx-messenger-content-load-text"}, html: BX.message(this.BXIM.settings.loadLastMessage? "IM_M_NO_MESSAGE_2": "IM_M_NO_MESSAGE")})
			]})];
		}
		if (arMessage.length > 0)
		{
			this.BXIM.messenger.popupMessengerBodyWrap.innerHTML = '';
			BX.adjust(this.BXIM.messenger.popupMessengerBodyWrap, {children: arMessage});
		}

		if (extraClose)
			this.BXIM.messenger.extraClose();

		if (this.isMobile())
		{
			BXMobileApp.UI.Page.TextPanel.setText(this.BXIM.messenger.textareaHistory[userId]? this.BXIM.messenger.textareaHistory[userId]: "");
		}
		else
		{
			this.BXIM.messenger.popupMessengerTextarea.value = this.BXIM.messenger.textareaHistory[userId]? this.BXIM.messenger.textareaHistory[userId]: "";
		}

		if (this.BXIM.messenger.redrawTab[userId])
		{
			if (this.BXIM.settings.loadLastMessage)
			{
				this.loadLastMessage(userId, this.BXIM.messenger.openChatFlag);
			}
			else
			{
				if (this.BXIM.messenger.openChatFlag)
					BX.MessengerCommon.loadChatData(userId.toString().substr(4));
				else
					BX.MessengerCommon.loadUserData(userId);

				delete this.BXIM.messenger.redrawTab[userId];
				this.drawTab(userId, true);
			}
		}
		else
		{
			this.drawTab(userId, true);
		}

		if (!this.BXIM.messenger.redrawTab[userId])
		{
			if (this.isMobile())
			{
				this.BXIM.isFocusMobile(BX.delegate(function(visible){
					if (visible)
					{
						BX.MessengerCommon.readMessage(userId); // TODO
					}
				},this));
			}
			else if (this.BXIM.isFocus())
			{
				this.readMessage(userId);
			}
		}

		if (!this.isMobile())
			this.BXIM.messenger.resizeMainWindow();

		if (BX.MessengerCommon.countWriting(userId))
		{
			if (this.BXIM.messenger.openChatFlag)
				BX.MessengerCommon.drawWriting(0, userId);
			else
				BX.MessengerCommon.drawWriting(userId);
		}
		else if (this.BXIM.messenger.readedList[userId])
		{
			this.drawReadMessage(userId, this.BXIM.messenger.readedList[userId].messageId, this.BXIM.messenger.readedList[userId].date, false);
		}

		if (!this.isMobile() && callToggle)
			this.BXIM.webrtc.callOverlayToggleSize(true);

		BX.onCustomEvent("onImDialogOpen", [{id: userId}]);
		if (this.isMobile())
		{
			app.onCustomEvent('onImDialogOpen', {'id': userId});
		}
	};

	BX.MessengerCommon.prototype.drawTab = function(userId, scroll)
	{
		if (this.BXIM.messenger.popupMessenger == null || userId != this.BXIM.messenger.currentTab)
			return false;

		this.BXIM.messenger.dialogStatusRedraw();

		this.BXIM.messenger.popupMessengerBodyWrap.innerHTML = '';

		if (!this.BXIM.messenger.showMessage[userId] || this.BXIM.messenger.showMessage[userId].length <= 0)
		{
			this.BXIM.messenger.popupMessengerBodyWrap.appendChild(BX.create("div", { props : { className : "bx-messenger-content-empty"}, children : [
				BX.create("span", { props : { className : "bx-messenger-content-load-text"}, html: BX.message(this.BXIM.settings.loadLastMessage? "IM_M_NO_MESSAGE_2": "IM_M_NO_MESSAGE")})
			]}));
		}

		if (this.BXIM.messenger.showMessage[userId])
			this.BXIM.messenger.showMessage[userId].sort(BX.delegate(function(i, ii) {if (!this.BXIM.messenger.message[i] || !this.BXIM.messenger.message[ii]){return 0;} var i1 = parseInt(this.BXIM.messenger.message[i].date); var i2 = parseInt(this.BXIM.messenger.message[ii].date); if (i1 < i2) { return -1; } else if (i1 > i2) { return 1;} else{ if (i < ii) { return -1; } else if (i > ii) { return 1;}else{ return 0;}}}, this));
		else
			this.BXIM.messenger.showMessage[userId] = [];

		for (var i = 0; i < this.BXIM.messenger.showMessage[userId].length; i++)
			BX.MessengerCommon.drawMessage(userId, this.BXIM.messenger.message[this.BXIM.messenger.showMessage[userId][i]], false);

		scroll = scroll != false;
		if (scroll)
		{
			if (this.BXIM.messenger.popupMessengerBodyAnimation != null)
				this.BXIM.messenger.popupMessengerBodyAnimation.stop();

			if (this.BXIM.messenger.unreadMessage[userId] && this.BXIM.messenger.unreadMessage[userId].length > 0)
			{
				var textElement = BX('im-message-'+this.BXIM.messenger.unreadMessage[userId][0]);
				if (textElement)
					this.BXIM.messenger.popupMessengerBody.scrollTop  = textElement.offsetTop-60-this.BXIM.messenger.popupMessengerBodyWrap.offsetTop;
				else
					this.BXIM.messenger.popupMessengerBody.scrollTop = this.BXIM.messenger.popupMessengerBody.scrollHeight - this.BXIM.messenger.popupMessengerBody.offsetHeight*(this.isMobile()? 0: 1);
			}
			else
			{
				this.BXIM.messenger.popupMessengerBody.scrollTop = this.BXIM.messenger.popupMessengerBody.scrollHeight - this.BXIM.messenger.popupMessengerBody.offsetHeight*(this.isMobile()? 0: 1);
			}
		}
		delete this.BXIM.messenger.redrawTab[userId];
	};

	/* Send Message */
	BX.MessengerCommon.prototype.sendMessageAjax = function(messageTmpIndex, recipientId, messageText, sendMessageToChat)
	{
		if (this.BXIM.messenger.popupMessengerConnectionStatusState != 'online')
			return false;

		BX.MessengerCommon.drawProgessMessage('temp'+messageTmpIndex);

		if (this.BXIM.messenger.sendMessageFlag < 0)
			this.BXIM.messenger.sendMessageFlag = 0;

		clearTimeout(this.BXIM.messenger.sendMessageTmpTimeout['temp'+messageTmpIndex]);
		if (this.BXIM.messenger.sendMessageTmp[messageTmpIndex])
			return false;

		this.BXIM.messenger.sendMessageTmp[messageTmpIndex] = true;
		sendMessageToChat = sendMessageToChat == true;
		this.BXIM.messenger.sendMessageFlag++;

		BX.MessengerCommon.recentListAdd({
			'id': 'temp'+messageTmpIndex,
			'date': BX.MessengerCommon.getNowDate()+parseInt(BX.message("SERVER_TZ_OFFSET")),
			'skipDateCheck': true,
			'recipientId': recipientId,
			'senderId': this.BXIM.userId,
			'text': BX.MessengerCommon.prepareText(messageText, true),
			'userId': recipientId,
			'params': {}
		}, true);

		var _ajax = BX.ajax({
			url: this.BXIM.pathToAjax+'?MESSAGE_SEND&V='+this.BXIM.revision,
			method: 'POST',
			dataType: 'json',
			skipAuthCheck: true,
			timeout: 60,
			data: {'IM_SEND_MESSAGE' : 'Y', 'CHAT': sendMessageToChat? 'Y': 'N', 'ID' : 'temp'+messageTmpIndex, 'RECIPIENT_ID' : recipientId, 'MESSAGE' : messageText, 'TAB' : this.BXIM.messenger.currentTab, 'USER_TZ_OFFSET': BX.message('USER_TZ_OFFSET'), 'IM_AJAX_CALL' : 'Y', 'sessid': BX.bitrix_sessid()},
			onsuccess: BX.delegate(function(data)
			{
				this.BXIM.messenger.sendMessageFlag--;

				if (data && data.BITRIX_SESSID)
				{
					BX.message({'bitrix_sessid': data.BITRIX_SESSID});
				}

				if (data.ERROR == '')
				{
					this.BXIM.messenger.sendAjaxTry = 0;
					this.BXIM.messenger.message[data.TMP_ID].text = data.SEND_MESSAGE;
					this.BXIM.messenger.message[data.TMP_ID].date = parseInt(data.SEND_DATE);
					this.BXIM.messenger.message[data.TMP_ID].id = data.ID;
					this.BXIM.messenger.message[data.ID] = this.BXIM.messenger.message[data.TMP_ID];

					if (this.BXIM.messenger.popupMessengerLastMessage == data.TMP_ID)
						this.BXIM.messenger.popupMessengerLastMessage = data.ID;

					delete this.BXIM.messenger.message[data.TMP_ID];
					var message = this.BXIM.messenger.message[data.ID];

					var idx = BX.util.array_search(''+data.TMP_ID+'', this.BXIM.messenger.showMessage[data.RECIPIENT_ID]);
					if (this.BXIM.messenger.showMessage[data.RECIPIENT_ID][idx])
						this.BXIM.messenger.showMessage[data.RECIPIENT_ID][idx] = ''+data.ID+'';

					for (var i = 0; i < this.BXIM.messenger.recent.length; i++)
					{
						if (this.BXIM.messenger.recent[i].id == data.TMP_ID)
						{
							this.BXIM.messenger.recent[i].id = ''+data.ID+'';
							break;
						}
					}

					if (data.RECIPIENT_ID == this.BXIM.messenger.currentTab)
					{
						var element = BX.findChild(this.BXIM.messenger.popupMessengerBodyWrap, {attribute: {'data-messageid': ''+data.TMP_ID+''}}, true);
						if (element)
						{
							element.setAttribute('data-messageid',	''+data.ID+'');
							if (element.getAttribute('data-blockmessageid') == ''+data.TMP_ID+'')
							{
								element.setAttribute('data-blockmessageid', ''+data.ID+'');
							}
							else
							{
								var element2 = BX.findChild(this.BXIM.messenger.popupMessengerBodyWrap, {attribute: {'data-blockmessageid': ''+data.TMP_ID+''}}, true);
								if (element2)
								{
									element2.setAttribute('data-blockmessageid', ''+data.ID+'');
								}
							}
						}

						var textElement = BX('im-message-'+data.TMP_ID);
						if (textElement)
						{
							textElement.id = 'im-message-'+data.ID;
							textElement.innerHTML =  BX.MessengerCommon.prepareText(data.SEND_MESSAGE, false, true, true);
						}

						var messageUser = this.BXIM.messenger.users[message.senderId];
						var lastMessageElementDate = BX.findChildByClassName(element, "bx-messenger-content-item-date");
						if (lastMessageElementDate)
							lastMessageElementDate.innerHTML = ' &nbsp; '+BX.MessengerCommon.formatDate(message.date, BX.MessengerCommon.getDateFormatType('MESSAGE'));

						BX.MessengerCommon.clearProgessMessage(data.ID);
					}

					if (this.BXIM.messenger.history[data.RECIPIENT_ID])
						this.BXIM.messenger.history[data.RECIPIENT_ID].push(message.id);
					else
						this.BXIM.messenger.history[data.RECIPIENT_ID] = [message.id];

					this.BXIM.messenger.updateStateVeryFastCount = 2;
					this.BXIM.messenger.updateStateFastCount = 5;
					this.BXIM.messenger.setUpdateStateStep();

					if (BX.PULL)
					{
						BX.PULL.setUpdateStateStepCount(2,5);
					}
					BX.MessengerCommon.updateStateVar(data, true, true);
					BX.localStorage.set('msm', {'id': data.ID, 'recipientId': data.RECIPIENT_ID, 'date': data.SEND_DATE, 'text' : data.SEND_MESSAGE, 'senderId' : this.BXIM.userId, 'MESSAGE': data.MESSAGE, 'USERS_MESSAGE': data.USERS_MESSAGE, 'USERS': data.USERS, 'USER_IN_GROUP': data.USER_IN_GROUP, 'WO_USER_IN_GROUP': data.WO_USER_IN_GROUP}, 5);

					if (this.BXIM.animationSupport)
					{
						if (this.BXIM.messenger.popupMessengerBodyAnimation != null)
							this.BXIM.messenger.popupMessengerBodyAnimation.stop();
						(this.BXIM.messenger.popupMessengerBodyAnimation = new BX.easing({
							duration : 800,
							start : { scroll : this.BXIM.messenger.popupMessengerBody.scrollTop},
							finish : { scroll : this.BXIM.messenger.popupMessengerBody.scrollHeight - this.BXIM.messenger.popupMessengerBody.offsetHeight*(this.isMobile()? 0: 1)},
							transition : BX.easing.makeEaseInOut(BX.easing.transitions.quart),
							step : BX.delegate(function(state){
								this.BXIM.messenger.popupMessengerBody.scrollTop = state.scroll;
							}, this)
						})).animate();
					}
					else
					{
						this.BXIM.messenger.popupMessengerBody.scrollTop = this.BXIM.messenger.popupMessengerBody.scrollHeight - this.BXIM.messenger.popupMessengerBody.offsetHeight*(this.isMobile()? 0: 1);
					}

					if (!this.MobileActionEqual('RECENT') && this.BXIM.messenger.recentList)
						BX.MessengerCommon.recentListRedraw();
				}
				else
				{
					if (data.ERROR == 'SESSION_ERROR' && this.BXIM.messenger.sendAjaxTry < 2)
					{
						this.BXIM.messenger.sendAjaxTry++;
						setTimeout(BX.delegate(function(){
							this.BXIM.messenger.sendMessageTmp[messageTmpIndex] = false;
							this.sendMessageAjax(messageTmpIndex, recipientId, messageText, sendMessageToChat);
						}, this), 2000);
						BX.onCustomEvent(window, 'onImError', [data.ERROR, data.BITRIX_SESSID]);
					}
					else if (data.ERROR == 'AUTHORIZE_ERROR')
					{
						this.BXIM.messenger.sendAjaxTry++;
						if (this.BXIM.desktop && this.BXIM.desktop.ready())
						{
							setTimeout(BX.delegate(function (){
								this.BXIM.messenger.sendMessageTmp[messageTmpIndex] = false;
								this.sendMessageAjax(messageTmpIndex, recipientId, messageText, sendMessageToChat);
							}, this), 10000);
						}
						BX.onCustomEvent(window, 'onImError', [data.ERROR]);
					}
					else
					{
						this.BXIM.messenger.sendMessageTmp[messageTmpIndex] = false;
						var element = BX.findChild(this.BXIM.messenger.popupMessengerBodyWrap, {attribute: {'data-messageid': 'temp'+messageTmpIndex}}, true);
						var lastMessageElementDate = BX.findChildByClassName(element, "bx-messenger-content-item-date");
						if (lastMessageElementDate)
						{
							if (data.ERROR == 'SESSION_ERROR' || data.ERROR == 'AUTHORIZE_ERROR' || data.ERROR == 'UNKNOWN_ERROR' || data.ERROR == 'IM_MODULE_NOT_INSTALLED')
								lastMessageElementDate.innerHTML = BX.message('IM_M_NOT_DELIVERED');
							else
								lastMessageElementDate.innerHTML = data.ERROR;
						}
						BX.onCustomEvent(window, 'onImError', ['SEND_ERROR', data.ERROR, data.TMP_ID, data.SEND_DATE, data.SEND_MESSAGE, data.RECIPIENT_ID]);

						BX.MessengerCommon.drawProgessMessage('temp'+messageTmpIndex, {title: BX.message('IM_M_RETRY'), chat: sendMessageToChat? 'Y':'N'});

						if (this.BXIM.messenger.message['temp'+messageTmpIndex])
							this.BXIM.messenger.message['temp'+messageTmpIndex].retry = true;
					}
				}
			}, this),
			onfailure: BX.delegate(function()	{
				this.BXIM.messenger.sendMessageFlag--;
				this.BXIM.messenger.sendMessageTmp[messageTmpIndex] = false;
				var element = BX.findChild(this.BXIM.messenger.popupMessengerBodyWrap, {attribute: {'data-messageid': 'temp'+messageTmpIndex}}, true);
				var lastMessageElementDate = BX.findChildByClassName(element, "bx-messenger-content-item-date");
				if (lastMessageElementDate)
					lastMessageElementDate.innerHTML = BX.message('IM_M_NOT_DELIVERED');

				BX.MessengerCommon.drawProgessMessage('temp'+messageTmpIndex, {title: BX.message('IM_M_RETRY'), chat: sendMessageToChat? 'Y':'N'});

				this.BXIM.messenger.sendAjaxTry = 0;
				try {
					if (typeof(_ajax) == 'object' && _ajax.status == 0)
						BX.onCustomEvent(window, 'onImError', ['CONNECT_ERROR']);
				}
				catch(e) {}
				if (this.BXIM.messenger.message['temp'+messageTmpIndex])
					this.BXIM.messenger.message['temp'+messageTmpIndex].retry = true;
			}, this)
		});
	};


	BX.MessengerCommon.prototype.sendMessageRetry = function()
	{
		var currentTab = this.BXIM.messenger.currentTab;
		var messageStack = [];
		for (var i = 0; i < this.BXIM.messenger.showMessage[currentTab].length; i++)
		{
			var message = this.BXIM.messenger.message[this.BXIM.messenger.showMessage[currentTab][i]];
			if (!message || message.id.indexOf('temp') != 0)
				continue;

			message.text = BX.MessengerCommon.prepareTextBack(message.text);

			messageStack.push(message);
		}
		if (messageStack.length <= 0)
			return false;

		messageStack.sort(function(i, ii) {i = i.id.substr(4); ii = ii.id.substr(4); if (i < ii) { return -1; } else if (i > ii) { return 1;}else{ return 0;}});
		for (var i = 0; i < messageStack.length; i++)
		{
			var element = BX.findChild(this.BXIM.messenger.popupMessengerBodyWrap, {attribute: {'data-messageid': ''+messageStack[i].id+''}}, true);
			var lastMessageElementDate = BX.findChildByClassName(element, "bx-messenger-content-item-date");
			if (lastMessageElementDate)
				lastMessageElementDate.innerHTML = BX.message('IM_M_DELIVERED');

			this.sendMessageRetryTimeout(messageStack[i], 100*i);
		}
	};

	BX.MessengerCommon.prototype.sendMessageRetryTimeout = function(message, timeout)
	{
		clearTimeout(this.BXIM.messenger.sendMessageTmpTimeout[message.id]);
		this.BXIM.messenger.sendMessageTmpTimeout[message.id] = setTimeout(BX.delegate(function() {
			BX.MessengerCommon.sendMessageAjax(message.id.substr(4), message.recipientId, message.text, message.recipientId.toString().substr(0,4) == 'chat');
		}, this), timeout);
	};

	BX.MessengerCommon.prototype.messageLike = function(messageId, onlyDraw)
	{
		if (messageId.toString().substr(0,4) == 'temp' || !this.BXIM.messenger.message[messageId] || this.BXIM.messenger.popupMessengerLikeBlock[messageId])
			return false;

		onlyDraw = typeof(onlyDraw) == 'undefined'? false: onlyDraw;

		if (!this.BXIM.messenger.message[messageId].params)
		{
			this.BXIM.messenger.message[messageId].params = {};
		}
		if (!this.BXIM.messenger.message[messageId].params.LIKE)
		{
			this.BXIM.messenger.message[messageId].params.LIKE = [];
		}

		var iLikeThis = BX.util.in_array(this.BXIM.userId, this.BXIM.messenger.message[messageId].params.LIKE);
		if (!onlyDraw)
		{
			var likeAction = iLikeThis? 'minus': 'plus';
			if (likeAction == 'plus')
			{
				this.BXIM.messenger.message[messageId].params.LIKE.push(this.BXIM.userId);
				iLikeThis = true;
			}
			else
			{
				var newLikeArray = [];
				for (var i = 0; i < this.BXIM.messenger.message[messageId].params.LIKE.length; i++)
				{
					if (this.BXIM.messenger.message[messageId].params.LIKE[i] != this.BXIM.userId)
					{
						newLikeArray.push(this.BXIM.messenger.message[messageId].params.LIKE[i])
					}
				}
				this.BXIM.messenger.message[messageId].params.LIKE = newLikeArray;
				iLikeThis = false;
			}
		}
		var likeCount = this.BXIM.messenger.message[messageId].params.LIKE.length > 0? this.BXIM.messenger.message[messageId].params.LIKE.length: '';

		if (BX('im-message-'+messageId))
		{
			var element = BX.findChild(this.BXIM.messenger.popupMessengerBodyWrap, {attribute: {'data-blockmessageid': ''+messageId+''}}, false);
			var elementLike = BX.findChildByClassName(element, "bx-messenger-content-item-like");
			var elementLikeDigit = BX.findChildByClassName(element, "bx-messenger-content-like-digit", false);
			var elementLikeButton = BX.findChildByClassName(element, "bx-messenger-content-like-button", false);

			if (iLikeThis)
			{
				elementLikeButton.innerHTML = BX.message('IM_MESSAGE_DISLIKE');
				BX.addClass(elementLike, 'bx-messenger-content-item-liked');
			}
			else
			{
				elementLikeButton.innerHTML = BX.message('IM_MESSAGE_LIKE');
				BX.removeClass(elementLike, 'bx-messenger-content-item-liked');
			}

			if (likeCount>0)
			{
				elementLikeDigit.setAttribute('title', BX.message('IM_MESSAGE_LIKE_LIST'));
				BX.removeClass(elementLikeDigit, 'bx-messenger-content-like-digit-off');
			}
			else
			{
				elementLikeDigit.setAttribute('title', '');
				BX.addClass(elementLikeDigit, 'bx-messenger-content-like-digit-off');
			}

			elementLikeDigit.innerHTML = likeCount;
		}
		if (!onlyDraw)
		{
			clearTimeout(this.BXIM.messenger.popupMessengerLikeBlockTimeout[messageId]);
			this.BXIM.messenger.popupMessengerLikeBlockTimeout[messageId] = setTimeout(BX.delegate(function(){
				this.BXIM.messenger.popupMessengerLikeBlock[messageId] = true;
				BX.ajax({
					url: this.BXIM.pathToAjax+'?MESSAGE_LIKE&V='+this.BXIM.revision,
					method: 'POST',
					dataType: 'json',
					timeout: 30,
					data: {'IM_LIKE_MESSAGE' : 'Y', 'ID': messageId, 'ACTION' : likeAction, 'IM_AJAX_CALL' : 'Y', 'sessid': BX.bitrix_sessid()},
					onsuccess: BX.delegate(function(data) {
						if (data.ERROR == '')
						{
							this.BXIM.messenger.message[messageId].params.LIKE = data.LIKE;
						}
						this.BXIM.messenger.popupMessengerLikeBlock[messageId] = false;
						BX.MessengerCommon.messageLike(messageId, true);
					}, this),
					onfailure: BX.delegate(function(data) {
						this.BXIM.messenger.popupMessengerLikeBlock[messageId] = false;
					}, this)
				});
			},this), 1000);
		}

		return true;
	}

	BX.MessengerCommon.prototype.messageIsLike = function(messageId)
	{
		return typeof(this.BXIM.messenger.message[messageId].params.LIKE) == "object" && BX.util.in_array(this.BXIM.userId, this.BXIM.messenger.message[messageId].params.LIKE);
	}

	BX.MessengerCommon.prototype.checkEditMessage = function(id)
	{
		var result = false;
		if (
			this.BXIM.ppServerStatus && parseInt(id) != 0 && id.toString().substr(0,4) != 'temp' &&
			this.BXIM.messenger.message[id] && this.BXIM.messenger.message[id].senderId == this.BXIM.userId &&
			parseInt(this.BXIM.messenger.message[id].date)+259200 > (new Date().getTime())/1000 &&
			(!this.BXIM.messenger.message[id].params || this.BXIM.messenger.message[id].params.IS_DELETED != 'Y') &&
			BX('im-message-'+id) && BX.util.in_array(id, this.BXIM.messenger.showMessage[this.BXIM.messenger.currentTab])
		)
		{
			result = true;
		}

		return result;
	}

	BX.MessengerCommon.prototype.deleteMessageAjax = function(id)
	{
		this.BXIM.messenger.editMessageCancel();

		if (!BX.MessengerCommon.checkEditMessage(id))
			return false;

		BX.MessengerCommon.drawProgessMessage(id);

		BX.ajax({
			url: this.BXIM.pathToAjax+'?MESSAGE_DELETE&V='+this.BXIM.revision,
			method: 'POST',
			dataType: 'json',
			timeout: 30,
			data: {'IM_DELETE_MESSAGE' : 'Y', ID: id, 'IM_AJAX_CALL' : 'Y', 'sessid': BX.bitrix_sessid()},
			onsuccess: BX.delegate(function(data) {
				if (data.ERROR)
					return false;

				BX.MessengerCommon.clearProgessMessage(id);
			}, this),
			onfailure: BX.delegate(function() {
				BX.MessengerCommon.clearProgessMessage(id);
			}, this)
		});

		return true;
	}

	/* Disk Manager */
	BX.MessengerCommon.prototype.diskDrawFiles = function(chatId, fileId, params)
	{
		if (!this.BXIM.disk.enable)
			return [];

		if (typeof(this.BXIM.disk.files[chatId]) == 'undefined')
			return [];

		var fileIds = [];
		if (typeof(fileId) != 'object')
		{
			if (typeof(this.BXIM.disk.files[chatId][fileId]) == 'undefined')
				return [];

			fileIds.push(fileId);
		}
		else
		{
			fileIds = fileId;
		}
		params = params || {};

		var urlContext = this.isMobile()? 'mobile': (this.BXIM.desktop.ready()? 'desktop': 'default');
		var enableLink = true;
		var nodeCollection = [];

		for (var i = 0; i < fileIds.length; i++)
		{
			var file = this.BXIM.disk.files[chatId][fileIds[i]];
			if (!file)
				continue;

			if (params.status)
			{
				if (typeof(params.status) != 'object')
				{
					params.status = [params.status];
				}
				if (!BX.util.in_array(file.status, params.status))
				{
					continue;
				}
			}

			var preview = null;
			if (file.type == 'image' && (file.preview || file.urlPreview[urlContext]))
			{
				var imageNodeMobile = null;
				if (this.isMobile() && file.preview && typeof(file.preview) != 'string')
				{
					if (file.urlPreview[urlContext])
					{
						var imageNodeMobile = BX.create("div", { attrs:{'src': file.urlPreview[urlContext]}, props : { className: "bx-messenger-file-image-text bx-messenger-hide"}});
					}
				}
				if (file.preview && typeof(file.preview) != 'string')
				{
					var imageNode = file.preview;
					if (file.urlPreview[urlContext])
					{
						file.preview = '';
					}
				}
				else
				{
					var imageNode = BX.create("img", { attrs:{'src': file.urlPreview[urlContext]? file.urlPreview[urlContext]: file.preview}, props : { className: "bx-messenger-file-image-text"}});
				}

				if (enableLink && file.urlShow[urlContext])
				{
					if (this.isMobile() && file.urlPreview[urlContext])
					{
						preview = BX.create("div", {props : { className: "bx-messenger-file-preview"},  children: [
							BX.create("span", {props : { className: "bx-messenger-file-image"},  children: [
								BX.create("span", {events: {click: BX.delegate(function(){
									this.BXIM.messenger.openPhotoGallery(file.urlPreview[urlContext]);
								}, this)}, props : { className: "bx-messenger-file-image-src"},  children: [
									imageNodeMobile,
									imageNode
								]})
							]}),
							BX.create("br")
						]});
					}
					else
					{
						preview = BX.create("div", {props : { className: "bx-messenger-file-preview"},  children: [
							BX.create("span", {props : { className: "bx-messenger-file-image"},  children: [
								BX.create("a", {attrs: {'href': file.urlShow[urlContext], 'target': '_blank'}, props : { className: "bx-messenger-file-image-src"},  children: [
									imageNode
								]})
							]}),
							BX.create("br")
						]});
					}
				}
				else
				{
					preview = BX.create("div", {props : { className: "bx-messenger-file-preview"},  children: [
						BX.create("span", {props : { className: "bx-messenger-file-image"},  children: [
							BX.create("span", {props : { className: "bx-messenger-file-image-src"},  children: [
								imageNode
							]})
						]}),
						BX.create("br")
					]});
				}
			}
			var fileName = file.name;
			if (this.isMobile())
			{
				if (fileName.length > 20)
				{
					fileName = fileName.substr(0, 8)+'...'+fileName.substr(fileName.length-13, fileName.length);
				}
			}
			else
			{
				if (fileName.length > 40)
				{
					fileName = fileName.substr(0, 20)+'...'+fileName.substr(fileName.length-23, fileName.length);
				}
			}
			var title = BX.create("span", { attrs: {'title': file.name}, props : { className: "bx-messenger-file-title"}, children: [
				BX.create("span", { props : { className: "bx-messenger-file-title-name"}, html: fileName})
			]});
			if (enableLink && (file.urlShow[urlContext] || file.urlDownload[urlContext]))
			{
				if (this.isMobile())
					title = BX.create("span", { props : { className: "bx-messenger-file-title-href"}, events: {click: function(){ BX.localStorage.set('impmh', true, 1);  app.openDocument({url: file.urlDownload['mobile'], filename: fileName}) }}, children: [title]});
				else
					title = BX.create("a", { props : { className: "bx-messenger-file-title-href"}, attrs: {'href': file.urlShow? file.urlShow[urlContext]: file.urlDownload[urlContext], 'target': '_blank'}, children: [title]});
			}
			title = BX.create("div", { props : { className: "bx-messenger-file-attrs"}, children: [
				title,
				BX.create("span", { props : { className: "bx-messenger-file-size"}, html: BX.UploaderUtils.getFormattedSize(file.size)})
			]});

			var status = null;
			if (file.status == 'done')
			{
				if (!this.isMobile())
				{
					status = BX.create("div", { props : { className: "bx-messenger-file-download"}, children: [
						!file.urlDownload || !enableLink? null: BX.create("a", {attrs: {'href': file.urlDownload[urlContext], 'target': '_blank'}, props : { className: "bx-messenger-file-download-link bx-messenger-file-download-pc"}, html: BX.message('IM_F_DOWNLOAD')}),
						!file.urlDownload || !this.BXIM.disk.enable? null: BX.create("span", { props : { className: "bx-messenger-file-download-link bx-messenger-file-download-disk"}, html: BX.message('IM_F_DOWNLOAD_DISK'), events: {click:BX.delegate(function(){
							var chatId = BX.proxy_context.parentNode.parentNode.getAttribute('data-chatId');
							var fileId = BX.proxy_context.parentNode.parentNode.getAttribute('data-fileId');
							var boxId = BX.proxy_context.parentNode.parentNode.getAttribute('data-boxId');
							this.BXIM.disk.saveToDisk(chatId, fileId, {boxId: boxId});
						}, this)}})
					]});
				}
				else
				{
					status = BX.create("div", { props : { className: "bx-messenger-file-download"}, children: []});
				}
			}
			else if (file.status == 'upload')
			{
				var statusStyles = {};
				var styles2 = '';
				var statusDelete = null;
				var statusClassName = '';
				var statusTitle = '';
				if (file.authorId == this.BXIM.userId && file.progress >= 0)
				{
					statusTitle = BX.message('IM_F_UPLOAD_2').replace('#PERCENT#', file.progress);
					statusStyles = { width: file.progress+'%' };
					statusDelete = BX.create("span", { attrs: {title: BX.message('IM_F_CANCEL')}, props : { className: "bx-messenger-file-delete"}})
				}
				else
				{
					statusTitle = BX.message('IM_F_UPLOAD');
					statusClassName = " bx-messenger-file-progress-infinite";
				}
				status = BX.create("div", { props : { className: "bx-messenger-progress-box"}, children: [
					BX.create("span", { attrs: {title: statusTitle}, props : { className: "bx-messenger-file-progress"}, children: [
						BX.create("span", { props : { className: "bx-messenger-file-progress-line"+statusClassName}, style : statusStyles})
					]}),
					statusDelete
				]});
			}
			else if (file.status == 'error')
			{
				status = BX.create("span", { props : { className: "bx-messenger-file-status-error"}, html: file.errorText? file.errorText: BX.message('IM_F_ERROR')})
			}

			if (!status)
				return false;

			if (fileIds.length == 1 && params.showInner == 'Y')
			{
				nodeCollection = [preview, title, status];
			}
			else
			{
				var boxId = params.boxId? params.boxId: 'im-file';
				nodeCollection.push(BX.create("div", {
					attrs: { id: boxId+'-'+file.id, 'data-chatId': file.chatId , 'data-fileId': file.id, 'data-boxId': boxId},
					props : { className: "bx-messenger-file"},
					children: [preview, title, status]
				}));
			}
		}

		return nodeCollection
	}

	BX.MessengerCommon.prototype.diskRedrawFile = function(chatId, fileId, params)
	{
		params = params || {};
		var boxId = params.boxId? params.boxId: 'im-file';

		var fileBox = BX(boxId+'-'+fileId);
		if (fileBox)
		{
			var result = this.diskDrawFiles(chatId, fileId, {'showInner': 'Y', 'boxId': boxId});
			if (result)
			{
				fileBox.innerHTML = '';
				BX.adjust(fileBox, {children: result});
			}
		}
	}

	BX.MessengerCommon.prototype.diskChatDialogFileInited = function(id, file, agent)
	{
		var chatId = agent.form.CHAT_ID.value;

		if (!this.BXIM.disk.files[chatId])
			this.BXIM.disk.files[chatId] = {};

		this.BXIM.disk.files[chatId][id] = {
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
			'authorName': this.BXIM.messenger.users[this.BXIM.userId].name,
			'urlPreview': '',
			'urlShow': '',
			'urlDownload': ''
		};

		if (!this.BXIM.disk.filesRegister[chatId])
			this.BXIM.disk.filesRegister[chatId] = {};

		this.BXIM.disk.filesRegister[chatId][id] = {
			'id': id,
			'type': this.BXIM.disk.files[chatId][id].type,
			'mimeType': file.file.type,
			'name': this.BXIM.disk.files[chatId][id].name,
			'size': this.BXIM.disk.files[chatId][id].size
		};

		this.diskChatDialogFileRegister(chatId);

	}
	BX.MessengerCommon.prototype.diskChatDialogFileRegister = function(chatId)
	{
		clearTimeout(this.BXIM.disk.timeout[chatId]);
		this.BXIM.disk.timeout[chatId] = setTimeout(BX.delegate(function(){
			var recipientId = 0;
			if (this.BXIM.messenger.chat[chatId] && this.BXIM.messenger.chat[chatId].style != 'private')
			{
				recipientId = 'chat'+chatId;
			}
			else
			{
				for (var userId in this.BXIM.messenger.userChat)
				{
					if (this.BXIM.messenger.userChat[userId] == chatId)
					{
						recipientId = userId;
						break;
					}
				}
			}
			if (!recipientId)
				return false;

			var paramsFileId = []
			for (var id in this.BXIM.disk.filesRegister[chatId])
			{
				paramsFileId.push(id);
			}
			var tmpMessageId = 'tempFile'+this.BXIM.disk.fileTmpId;
			this.BXIM.messenger.message[tmpMessageId] = {
				'id': tmpMessageId,
				'chatId': chatId,
				'senderId': this.BXIM.userId,
				'recipientId': recipientId,
				'date': BX.MessengerCommon.getNowDate(),
				'text': '',
				'params': {'FILE_ID': paramsFileId}
			};
			if (!this.BXIM.messenger.showMessage[recipientId])
				this.BXIM.messenger.showMessage[recipientId] = [];

			this.BXIM.messenger.showMessage[recipientId].push(tmpMessageId);
			BX.MessengerCommon.drawMessage(recipientId, this.BXIM.messenger.message[tmpMessageId]);
			BX.MessengerCommon.drawProgessMessage(tmpMessageId);

			this.BXIM.messenger.sendMessageFlag++;
			this.BXIM.messenger.popupMessengerFileFormInput.setAttribute('disabled', true);

			this.BXIM.disk.OldBeforeUnload = window.onbeforeunload;
			window.onbeforeunload = function(){
				if (typeof(BX.PULL) != 'undefined' && typeof(BX.PULL.tryConnectDelay) == 'function') // TODO change to right code in near future (e.shelenkov)
				{
					BX.PULL.tryConnectDelay();
				}
				return BX.message('IM_F_EFP')
			};

			BX.ajax({
				url: this.BXIM.pathToFileAjax+'?FILE_REGISTER&V='+this.BXIM.revision,
				method: 'POST',
				dataType: 'json',
				timeout: 30,
				data: {'IM_FILE_REGISTER' : 'Y', CHAT_ID: chatId, RECIPIENT_ID: recipientId, MESSAGE_TMP_ID: tmpMessageId, FILES: JSON.stringify(this.BXIM.disk.filesRegister[chatId]), 'IM_AJAX_CALL' : 'Y', 'sessid': BX.bitrix_sessid()},
				onsuccess: BX.delegate(function(data) {
					if (data.ERROR != '')
					{
						this.BXIM.messenger.sendMessageFlag--;
						delete this.BXIM.messenger.message[tmpMessageId];
						BX.MessengerCommon.drawTab(recipientId);
						window.onbeforeunload = this.BXIM.disk.OldBeforeUnload;

						this.BXIM.disk.filesRegister[chatId] = {};

						if (this.BXIM.disk.formAgents['imDialog']["clear"])
							this.BXIM.disk.formAgents['imDialog'].clear();

						return false;
					}

					this.BXIM.messenger.sendMessageFlag--;
					var messagefileId = [];
					var filesProgress = {};
					for(var tmpId in data.FILE_ID)
					{
						var newFile = data.FILE_ID[tmpId];

						delete this.BXIM.disk.filesRegister[data.CHAT_ID][newFile.TMP_ID];

						if (parseInt(newFile.FILE_ID) > 0)
						{
							filesProgress[newFile.TMP_ID] = newFile.FILE_ID;
							this.BXIM.disk.filesProgress[newFile.TMP_ID] = newFile.FILE_ID;
							this.BXIM.disk.filesMessage[newFile.TMP_ID] = data.MESSAGE_ID;

							this.BXIM.disk.files[data.CHAT_ID][newFile.FILE_ID] = {};
							for (var key in this.BXIM.disk.files[data.CHAT_ID][newFile.TMP_ID])
								this.BXIM.disk.files[data.CHAT_ID][newFile.FILE_ID][key] = this.BXIM.disk.files[data.CHAT_ID][newFile.TMP_ID][key];
							this.BXIM.disk.files[data.CHAT_ID][newFile.FILE_ID]['id'] = newFile.FILE_ID;
							delete this.BXIM.disk.files[data.CHAT_ID][newFile.TMP_ID];

							this.BXIM.disk.files[data.CHAT_ID][newFile.FILE_ID]['name'] = newFile.FILE_NAME;
							if (BX('im-file-'+newFile.TMP_ID))
							{
								BX('im-file-'+newFile.TMP_ID).setAttribute('data-fileId', newFile.FILE_ID);
								BX('im-file-'+newFile.TMP_ID).id = 'im-file-'+newFile.FILE_ID;
								BX.MessengerCommon.diskRedrawFile(data.CHAT_ID, newFile.FILE_ID);
							}

							messagefileId.push(newFile.FILE_ID);
						}
						else
						{
							this.BXIM.disk.files[data.CHAT_ID][newFile.TMP_ID]['status'] = 'error';
							BX.MessengerCommon.diskRedrawFile(data.CHAT_ID, newFile.TMP_ID);
						}
					}

					this.BXIM.messenger.message[data.MESSAGE_ID] = BX.clone(this.BXIM.messenger.message[data.MESSAGE_TMP_ID]);
					this.BXIM.messenger.message[data.MESSAGE_ID]['id'] = data.MESSAGE_ID;
					this.BXIM.messenger.message[data.MESSAGE_ID]['params']['FILE_ID'] = messagefileId;

					if (this.BXIM.messenger.popupMessengerLastMessage == data.MESSAGE_TMP_ID)
						this.BXIM.messenger.popupMessengerLastMessage = data.MESSAGE_ID;

					delete this.BXIM.messenger.message[data.MESSAGE_TMP_ID];

					var idx = BX.util.array_search(''+data.MESSAGE_TMP_ID+'', this.BXIM.messenger.showMessage[data.RECIPIENT_ID]);
					if (this.BXIM.messenger.showMessage[data.RECIPIENT_ID][idx])
						this.BXIM.messenger.showMessage[data.RECIPIENT_ID][idx] = ''+data.MESSAGE_ID+'';

					if (BX('im-message-'+data.MESSAGE_TMP_ID))
					{
						BX('im-message-'+data.MESSAGE_TMP_ID).id = 'im-message-'+data.MESSAGE_ID;
						var element = BX.findChild(this.BXIM.messenger.popupMessengerBodyWrap, {attribute: {'data-messageid': ''+data.MESSAGE_TMP_ID}}, true);
						if (element)
						{
							element.setAttribute('data-messageid',	''+data.MESSAGE_ID+'');
							if (element.getAttribute('data-blockmessageid') == ''+data.MESSAGE_TMP_ID)
								element.setAttribute('data-blockmessageid',	''+data.MESSAGE_ID+'');
						}
						else
						{
							var element2 = BX.findChild(this.BXIM.messenger.popupMessengerBodyWrap, {attribute: {'data-blockmessageid': ''+data.MESSAGE_TMP_ID}}, true);
							if (element2)
							{
								element2.setAttribute('data-blockmessageid', ''+data.MESSAGE_ID+'');
							}
						}
						var lastMessageElementDate = BX.findChildByClassName(element, "bx-messenger-content-item-date");
						if (lastMessageElementDate)
							lastMessageElementDate.innerHTML = ' &nbsp; '+BX.MessengerCommon.formatDate(this.BXIM.messenger.message[data.MESSAGE_ID].date, BX.MessengerCommon.getDateFormatType('MESSAGE'));
					}
					BX.MessengerCommon.clearProgessMessage(data.MESSAGE_ID);

					if (this.BXIM.messenger.history[data.RECIPIENT_ID])
						this.BXIM.messenger.history[data.RECIPIENT_ID].push(data.MESSAGE_ID);
					else
						this.BXIM.messenger.history[data.RECIPIENT_ID] = [data.MESSAGE_ID];

					this.BXIM.messenger.popupMessengerFileFormRegChatId.value = data.CHAT_ID;
					this.BXIM.messenger.popupMessengerFileFormRegMessageId.value = data.MESSAGE_ID;
					this.BXIM.messenger.popupMessengerFileFormRegParams.value = JSON.stringify(filesProgress);

					this.BXIM.disk.formAgents['imDialog'].submit();

					this.BXIM.messenger.popupMessengerFileFormInput.removeAttribute('disabled');
				}, this),
				onfailure: BX.delegate(function(){
					this.BXIM.messenger.sendMessageFlag--;
					delete this.BXIM.messenger.message[tmpMessageId];
					this.BXIM.disk.filesRegister[chatId] = {};

					BX.MessengerCommon.drawTab(recipientId);
					window.onbeforeunload = this.BXIM.disk.OldBeforeUnload;

					if (this.BXIM.disk.formAgents['imDialog']["clear"])
						this.BXIM.disk.formAgents['imDialog'].clear();

				}, this)
			});
			this.BXIM.disk.fileTmpId++;
		}, this), 500);
	}
	BX.MessengerCommon.prototype.diskChatDialogFileStart = function(status, percent, agent, pIndex)
	{
		var fileId = this.BXIM.disk.filesProgress[status.id];
		formFields = agent.streams.packages.getItem(pIndex).data;
		if (!this.BXIM.disk.files[formFields.REG_CHAT_ID][fileId])
			return false;

		this.BXIM.disk.files[formFields.REG_CHAT_ID][fileId].progress = parseInt(percent);
		BX.MessengerCommon.diskRedrawFile(formFields.REG_CHAT_ID, fileId);
	}
	BX.MessengerCommon.prototype.diskChatDialogFileProgress = function(status, percent, agent, pIndex)
	{
		var fileId = this.BXIM.disk.filesProgress[status.id];
		formFields = agent.streams.packages.getItem(pIndex).data;
		if (!this.BXIM.disk.files[formFields.REG_CHAT_ID][fileId])
			return false;

		this.BXIM.disk.files[formFields.REG_CHAT_ID][fileId].progress = parseInt(percent);
		BX.MessengerCommon.diskRedrawFile(formFields.REG_CHAT_ID, fileId);
	}
	BX.MessengerCommon.prototype.diskChatDialogFileDone = function(status, file, agent, pIndex)
	{
		if (!this.BXIM.disk.files[file.file.fileChatId][file.file.fileId])
			return false;

		if (this.BXIM.disk.files[file.file.fileChatId] && this.BXIM.disk.files[file.file.fileChatId][file.file.fileId])
		{
			file.file.fileParams['preview'] = this.BXIM.disk.files[file.file.fileChatId][file.file.fileId]['preview'];
		}
		if (!this.BXIM.disk.files[file.file.fileChatId])
			this.BXIM.disk.files[file.file.fileChatId] = {};
		this.BXIM.disk.files[file.file.fileChatId][file.file.fileId] = file.file.fileParams;
		BX.MessengerCommon.diskRedrawFile(file.file.fileChatId, file.file.fileId);

		delete this.BXIM.disk.filesMessage[file.file.fileTmpId];
		window.onbeforeunload = this.BXIM.disk.OldBeforeUnload;
	}
	BX.MessengerCommon.prototype.diskChatDialogFileError = function(item, file, agent, pIndex)
	{
		var fileId = this.BXIM.disk.filesProgress[item.id];
		formFields = agent.streams.packages.getItem(pIndex).data;
		if (!this.BXIM.disk.files[formFields.REG_CHAT_ID][fileId])
			return false;

		item.deleteFile();

		this.BXIM.disk.files[formFields.REG_CHAT_ID][fileId].status = "error";
		this.BXIM.disk.files[formFields.REG_CHAT_ID][fileId].errorText = file.error;
		BX.MessengerCommon.diskRedrawFile(formFields.REG_CHAT_ID, fileId);
		window.onbeforeunload = this.BXIM.disk.OldBeforeUnload;
	}
	BX.MessengerCommon.prototype.diskChatDialogUploadError = function(stream, pIndex, data)
	{
		var files = JSON.parse(stream.post.REG_PARAMS);
		var messages = {};
		for (var tmpId in files)
		{
			if (this.BXIM.disk.filesMessage[tmpId])
			{
				delete this.BXIM.disk.filesMessage[tmpId];
			}
			if (this.BXIM.disk.filesRegister[stream.post.REG_CHAT_ID])
			{
				delete this.BXIM.disk.filesRegister[stream.post.REG_CHAT_ID][tmpId];
				delete this.BXIM.disk.filesRegister[stream.post.REG_CHAT_ID][files[tmpId]];
			}
			if (this.BXIM.disk.files[stream.post.REG_CHAT_ID])
			{
				if (this.BXIM.disk.files[stream.post.REG_CHAT_ID][files[tmpId]])
				{
					this.BXIM.disk.files[stream.post.REG_CHAT_ID][files[tmpId]].status = 'error';
					BX.MessengerCommon.diskRedrawFile(stream.post.REG_CHAT_ID, files[tmpId]);
				}
				if (this.BXIM.disk.files[stream.post.REG_CHAT_ID][tmpId])
				{
					this.BXIM.disk.files[stream.post.REG_CHAT_ID][tmpId].status = 'error';
					BX.MessengerCommon.diskRedrawFile(stream.post.REG_CHAT_ID, tmpId);
				}

			}
			delete this.BXIM.disk.filesProgress[tmpId];
		}
		BX.ajax({
			url: this.BXIM.pathToFileAjax+'?FILE_UNREGISTER&V='+this.BXIM.revision,
			method: 'POST',
			dataType: 'json',
			timeout: 30,
			data: {'IM_FILE_UNREGISTER' : 'Y', CHAT_ID: stream.post.REG_CHAT_ID, FILES: stream.post.REG_PARAMS, MESSAGES: JSON.stringify(messages), 'IM_AJAX_CALL' : 'Y', 'sessid': BX.bitrix_sessid()}
		});
		window.onbeforeunload = this.BXIM.disk.OldBeforeUnload;
		BX.MessengerCommon.drawTab(this.BXIM.messenger.getRecipientByChatId(stream.post.REG_CHAT_ID));
	}

	BX.MessengerCommon = new BX.MessengerCommon();

})(window);