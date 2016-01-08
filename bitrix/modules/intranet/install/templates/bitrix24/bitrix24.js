/*Global Settings */
(function() {

	BX.addCustomEvent("onFrameDataRequestFail", function(response) {
		top.location = "/auth/?backurl=" + B24.getBackUrl();
	});

	BX.addCustomEvent("onAjaxFailure", function(status) {
		var redirectUrl = "/auth/?backurl=" + B24.getBackUrl();
		if (status == "auth")
		{
			top.location = redirectUrl;
		}
	});

	BX.addCustomEvent("onPopupWindowInit", function(uniquePopupId, bindElement, params) {
		//if (BX.util.in_array(uniquePopupId, ["task-legend-popup"]))
		//	params.lightShadow = true;

		if (uniquePopupId == "bx_log_filter_popup")
		{
			params.lightShadow = true;
			params.className = "";
		}
		else if (uniquePopupId == "task-legend-popup")
		{
			params.lightShadow = true;
			params.offsetTop = -15;
			params.offsetLeft = -670;
			params.angle = {offset : 740};
		}
		else if ((uniquePopupId == "task-gantt-filter") || (uniquePopupId == "task-list-filter"))
		{
			params.lightShadow = true;
			params.className = "";
		}
		else if (uniquePopupId.indexOf("sonet_iframe_popup_") > -1)
		{
			params.lightShadow = true;
		}
	});

	BX.addCustomEvent("onJCClockInit", function(config) {

		JCClock.setOptions({
			"centerXInline" : 83,
			"centerX" : 83,
			"centerYInline" : 67,
			"centerY" : 79,
			"minuteLength" : 31,
			"hourLength" : 26,
			"popupHeight" : 229,
			"inaccuracy" : 15,
			"cancelCheckClick" : true
		});
	});

	BX.PopupWindow.setOptions({
		"angleMinTop" : 35,
		"angleMinRight" : 10,
		"angleMinBottom" : 35,
		"angleMinLeft" : 10,
		"angleTopOffset" : 5,
		"angleLeftOffset" : 45,
		"offsetLeft" : 0, //-15,
		"offsetTop" : 2,
		"positionTopXOffset" : -11 //20
	});

	BX.addCustomEvent("onPullEvent-main", function(command,params){
		if (command == "user_counter" && params[BX.message("SITE_ID")])
		{
			var counters = BX.clone(params[BX.message('SITE_ID')]);
			B24.updateCounters(counters);
		}
	});

	BX.addCustomEvent(window, "onImUpdateCounter", function(counters){

		if (!counters)
			return;

		B24.updateCounters(BX.clone(counters));
	});

	BX.addCustomEvent("onCounterDecrement", function(iDecrement) {

		var counterWrap = BX("menu-counter-live-feed", true);
		if (!counterWrap)
			return;

		iDecrement = parseInt(iDecrement);
		var oldVal = parseInt(counterWrap.innerHTML);
		var newVal = oldVal - iDecrement;
		if (newVal > 0)
			counterWrap.innerHTML = newVal;
		else
			BX.removeClass(counterWrap.parentNode.parentNode.parentNode, "menu-item-with-index");
	});

	BX.addCustomEvent("onImUpdateCounterNotify", function(counter) {
		B24.updateInformer(BX("im-informer-events", true), counter);
	});

	BX.addCustomEvent("onImUpdateCounterMessage", function(counter) {
		B24.updateInformer(BX("im-informer-messages", true), counter);
	});

	BX.addCustomEvent("onImUpdateCounterNetwork", function(counter) {
		B24.updateInformer(BX("b24network-informer-events", true), counter);
	});

//connection status===
	BX.addCustomEvent("onPullError", BX.delegate(function(error, code) {
		if (error == 'AUTHORIZE_ERROR')
		{
			B24.connectionStatus("offline");
		}
		else if (error == 'RECONNECT' && (code == 1008 || code == 1006))
		{
			B24.connectionStatus("connecting");
		}
	}, this));

	BX.addCustomEvent("onImError", BX.delegate(function(error, sendErrorCode) {
		if (error == 'AUTHORIZE_ERROR' || error == 'SEND_ERROR' && sendErrorCode == 'AUTHORIZE_ERROR')
		{
			B24.connectionStatus("offline");
		}
		else if (error == 'CONNECT_ERROR')
		{
			B24.connectionStatus("offline");
		}
	}, this));

	BX.addCustomEvent("onPullStatus", BX.delegate(function(status){
		if (status == 'offline')
			B24.connectionStatus("offline");
		else
			B24.connectionStatus("online");
	}, this));

	BX.bind(window, "online", BX.delegate(function(){
		B24.connectionStatus("online");
	}, this));

	BX.bind(window, "offline", BX.delegate(function(){
		B24.connectionStatus("offline");
	}, this));
//==connection status

	if (BX.browser.SupportLocalStorage())
	{
		BX.addCustomEvent(window, 'onLocalStorageSet', function(params)
		{
			if (params.key.substring(0, 4) == 'lmc-')
			{
				var counters = {};
					counters[params.key.substring(4)] = params.value;
				B24.updateCounters(counters, false);
			}
		});
	}

	BX.ready(function () {
		var upButtonWrap = BX("feed-up-btn-wrap", true);
		var menu = BX("menu", true);

		BX.bind(window, "scroll", B24.onScroll);
		if (menu && upButtonWrap)
		{
			BX.bind(window, "resize", B24.onScroll);
		}
	});
})();

var B24 = {

	upButtonScrollLock: false,
	b24ConnectionStatusState: "online",
	b24ConnectionStatus: null,
	b24ConnectionStatusText: null,
	b24ConnectionStatusTimeout: null,

	formateDate : function(time){
		return BX.util.str_pad(time.getHours(), 2, '0', 'left') + ':' + BX.util.str_pad(time.getMinutes(), 2, '0', 'left');
	},

	HelpPopupWindow : {
		legend : null,
		show :  function(steps, bindElement, settings)
		{
			if (this.popup == null)
				this.legend = new B24.HelpPopup(steps, bindElement, {
					"selectedClass" : "b24-help-popup-page-selected"
				});

			this.legend.popup.show();
		}
	},

	VideoPopupWindow : {
		legend : null,
		currentStepId : null,
		steps : [],
		params : {},

		init : function(steps, params)
		{
			if (BX.type.isArray(steps))
			{
				this.steps = steps;
			}

			this.params = params || {};
		},

		create : function()
		{
			if (this.legend == null)
			{
				var settings = {
					"video" : true,
					"selectedClass" : "b24-video-popup-menu-item-selected"
				};

				for (var param in this.params)
				{
					if (this.params.hasOwnProperty(param))
					{
						settings[param] = this.params[param];
					}
				}

				this.legend = new B24.HelpPopup(this.steps, null, settings);
			}
		},

		show : function(stepId)
		{
			if (this.legend == null)
			{
				this.create();
			}

			if (BX.type.isNotEmptyString(stepId))
			{
				this.setCurrentStep(stepId);
			}

			var stepNumber = this.legend.getStepPositionById(this.currentStepId);
			if (stepNumber >= 0)
			{
				this.legend.showStepByNumber(stepNumber);
			}

			if (!this.legend.popup.isShown())
			{
				this.legend.popup.show();
			}
			else
			{
				this.legend.scrollToCurrent();
			}
		},

		close : function()
		{
			if (this.legend)
			{
				this.legend.popup.close();
			}
		},

		setCurrentStep : function(stepId)
		{
			this.currentStepId = stepId;
		},

		existsStep : function(stepId)
		{
			if (!this.legend)
				return false;

			return this.legend.getStepPositionById(stepId) >= 0;
		}
	},

	openLanguagePopup: function(button)
	{
		var langs = JSON.parse(button.getAttribute("data-langs"));
		var items = [];
		for (var lang in langs)
		{
			items.push({
				text: langs[lang],
				className: lang,
				onclick: function(event, item)
				{
					B24.changeLanguage(item.className);
				}
			});
		}

		BX.PopupMenu.show("language-popup", button, items, { offsetTop:10, offsetLeft:0 });
	},

	changeLanguage: function(lang)
	{
		window.location.href = "/auth/?user_lang=" + lang + "&backurl=" + B24.getBackUrl();
	},

	getBackUrl: function()
	{
		var backUrl = window.location.pathname;
		var query = B24.getQueryString(["logout", "login", "back_url_pub", "user_lang"]);
		return backUrl + (query.length > 0 ? "?" + query : "");
	},

	getQueryString : function(ignoredParams)
	{
		var query = window.location.search.substring(1);
		if (!BX.type.isNotEmptyString(query))
		{
			return "";
		}

		var vars = query.split("&");
		ignoredParams = BX.type.isArray(ignoredParams) ? ignoredParams : [];

		var result = "";
		for (var i = 0; i < vars.length; i++)
		{
			var pair = vars[i].split("=");
			var equal = vars[i].indexOf("=");
			var key = pair[0];
			var value = BX.type.isNotEmptyString(pair[1]) ? pair[1] : false;
			if (!BX.util.in_array(key, ignoredParams))
			{
				if (result !== "")
				{
					result += "&";
				}
				result += key + (equal !== -1 ? "=" : "") + (value !== false ? value : "" );
			}
		}

		return result;
	},

	updateInformer : function(informer, counter)
	{
		if (counter > 0)
		{
			informer.innerHTML = counter;
			BX.addClass(informer, "header-informer-act");
		}
		else
		{
			informer.innerHTML = "";
			BX.removeClass(informer, "header-informer-act");
		}
	},

	updateCounters : function(counters, send)
	{
		send = send == false ? false : true;

		var bCalculateCRM = false;

		for (var id in counters)
		{
			if (window.B24menuItemsObj)
				window.B24menuItemsObj.allCounters[id] = counters[id];

			if (id == "**")
			{
				oCounter = {
					iCommentsMenuRead: 0
				};

				BX.onCustomEvent(window, 'onMenuUpdateCounter', [oCounter]);
				counters[id] -= oCounter.iCommentsMenuRead;
			}

			if (id == "CRM_**")
			{
				bCalculateCRM = true;
				if (BX("menu-counter-crm_cur_act"))
				{
					BX("menu-counter-crm_cur_act").setAttribute("data-counter-crmstream", counters[id]);
				}
			}
			else if (id == "crm_cur_act")
			{
				bCalculateCRM = true;
				if (BX("menu-counter-crm_cur_act"))
				{
					BX("menu-counter-crm_cur_act").setAttribute("data-counter-crmact", counters[id]);
				}
			}

			var counter = BX(id == "**" ? "menu-counter-live-feed" : "menu-counter-" + id.toLowerCase(), true);
			if (counter)
			{
				if (counters[id] > 0)
				{
					counter.innerHTML = id == "mail_unseen"
						? (counters[id] > 99 ? "99+" : counters[id])
						: (counters[id] > 50 ? "50+" : counters[id]);
					BX.addClass(counter.parentNode.parentNode.parentNode, "menu-item-with-index");
				}
				else
				{
					BX.removeClass(counter.parentNode.parentNode.parentNode, "menu-item-with-index");

					if (counters[id] < 0)
					{
						var warning = BX('menu-counter-warning-'+id.toLowerCase());
						if (warning)
							warning.style.display = 'inline-block';
					}
				}

				if (send)
					BX.localStorage.set('lmc-'+id, counters[id], 5);
			}
			else if (
				bCalculateCRM
				&& BX("menu-counter-crm_cur_act")
			)
			{
				var val = parseInt(BX("menu-counter-crm_cur_act").getAttribute("data-counter-crmact")) + parseInt(BX("menu-counter-crm_cur_act").getAttribute("data-counter-crmstream"));
				if (BX.type.isNumber(val))
				{
					BX("menu-counter-crm_cur_act").innerHTML = (val > 50 ? "50+" : val);
					bCalculateCRM = false;
				}
			}
		}

		if (window.B24menuItemsObj)
		{
			var sumHiddenCounters = 0;
			for(var i = 0, l = window.B24menuItemsObj.hiddenCounters.length; i < l; i++)
			{
				if (window.B24menuItemsObj.allCounters[window.B24menuItemsObj.hiddenCounters[i]])
				{
					sumHiddenCounters+= (+window.B24menuItemsObj.allCounters[window.B24menuItemsObj.hiddenCounters[i]]);
				}
			}

			BX("menu-hidden-counter").style.display = (sumHiddenCounters > 0) ? "inline-block" : "none";
			BX("menu-hidden-counter").innerHTML = sumHiddenCounters > 50 ? "50+" : sumHiddenCounters;
		}
	},

	showNotifyPopup : function(button)
	{
		if (BX.hasClass(button, "header-informer-press"))
		{
			BX.removeClass(button, "header-informer-press");
			BXIM.closeNotify();
		}
		else
		{
			BXIM.openNotify();
		}
	},

	showMessagePopup : function(button)
	{
		if (typeof(BXIM) == 'undefined')
			return false;

		if (BXIM.isOpenMessenger())
		{
			BXIM.closeMessenger();
		}
		else
		{
			BXIM.openMessenger();
		}
	},

	closeBanner : function(bannerId)
	{
		BX.userOptions.save('bitrix24', 'banners',  bannerId, 'Y');
		var banner = BX("sidebar-banner-" + bannerId);
		if (banner)
		{
			banner.style.minHeight = "auto";
			banner.style.overflow = "hidden";
			banner.style.border = "none";
			(new BX.easing({
				duration : 500,
				start : { height : banner.offsetHeight, opacity : 100 },
				finish : { height : 0, opacity: 0 },
				transition : BX.easing.makeEaseOut(BX.easing.transitions.quart),
				step : function(state){
					if (state.height >= 0)
					{
						banner.style.height = state.height + "px";
						banner.style.opacity = state.opacity/100;
					}

					if (state.height <= 17)
					{
						banner.style.marginBottom = state.height + "px";
					}
				},
				complete : function() {
					banner.style.display = "none";
				}
			})).animate();
		}
	},

	showLoading: function(timeout)
	{
		timeout = timeout || 500;
		function show()
		{
			var loader = BX("b24-loader");
			if (loader)
			{
				BX.addClass(loader, "b24-loader-show");
				return true;
			}

			return false;
		}

		setTimeout(function() {
			if (!show() && !BX.isReady)
			{
				BX.ready(show);
			}
		}, timeout);
	}
};

/***************** UP button **********************/
B24.onScroll = function()
{
	var windowScroll = BX.GetWindowScrollPos();
	if (B24.b24ConnectionStatus)
	{
		if (B24.b24ConnectionStatus.getAttribute('data-float') == 'true')
		{
			if (windowScroll.scrollTop < 60)
			{
				BX.removeClass(B24.b24ConnectionStatus, 'bx24-connection-status-float');
				B24.b24ConnectionStatus.setAttribute('data-float', 'false');
			}
		}
		else
		{
			if (windowScroll.scrollTop > 60)
			{
				BX.addClass(B24.b24ConnectionStatus, 'bx24-connection-status-float')
				B24.b24ConnectionStatus.setAttribute('data-float', 'true');
			}
		}
	}

	if (B24.upButtonScrollLock)
		return;

	B24.upButtonScrollLock = true;

	setTimeout(function() {
		B24.upButtonScrollLock = false;
	}, 150);

	var menu = BX("menu", true);
	if (!menu)
		return;

	var windowSize = BX.GetWindowInnerSize();
	var menuPos = BX.pos(menu);

	var upBtn = BX("feed-up-btn-wrap", true);
	upBtn.style.left = "-" + windowScroll.scrollLeft + "px";

	if ((windowScroll.scrollTop + parseInt(windowSize.innerHeight*0.33)) > menuPos.bottom)
		B24.showUpButton(true, upBtn);
	else
		B24.showUpButton(false, upBtn);
};

B24.showUpButton = function(status, upBtn)
{
	if (!upBtn)
		return;

	if (!!status)
		BX.addClass(upBtn, 'feed-up-btn-wrap-anim');
	else
		BX.removeClass(upBtn, 'feed-up-btn-wrap-anim');
};

B24.goUp = function()
{
	var upBtn = BX("feed-up-btn-wrap", true);
	if (upBtn)
	{
		upBtn.style.display = "none";
		BX.removeClass(upBtn, 'feed-up-btn-wrap-anim');
	}

	var windowScroll = BX.GetWindowScrollPos();

	(new BX.easing({
		duration : 500,
		start : { scroll : windowScroll.scrollTop },
		finish : { scroll : 0 },
		transition : BX.easing.makeEaseOut(BX.easing.transitions.quart),
		step : function(state){
			window.scrollTo(0, state.scroll);
		},
		complete: function() {
			if (upBtn)
				upBtn.style.display = "block";
			BX.onCustomEvent(window, 'onGoUp');
		}
	})).animate();
};

/***************** Search Title **********************/
B24.SearchTitle = function(arParams)
{
	var _this = this;

	this.arParams = {
		'AJAX_PAGE': arParams.AJAX_PAGE,
		'CONTAINER_ID': arParams.CONTAINER_ID,
		'INPUT_ID': arParams.INPUT_ID,
		'MIN_QUERY_LEN': parseInt(arParams.MIN_QUERY_LEN)
	};

	if(arParams.MIN_QUERY_LEN <= 0)
		arParams.MIN_QUERY_LEN = 1;

	this.cache = [];
	this.cache_key = null;

	this.startText = '';
	this.currentRow = -1;
	this.RESULT = null;
	this.CONTAINER = null;
	this.INPUT = null;
	this.timeout = null;

	this.CreateResultWrap = function()
	{
		if (_this.RESULT == null)
		{
			this.RESULT = document.body.appendChild(document.createElement("DIV"));
			this.RESULT.className = 'title-search-result title-search-result-header';
		}
	};

	this.ShowResult = function(result)
	{
		_this.CreateResultWrap();
		/* modified */
		var ieTop = 0;
		var ieLeft = 0;
		var ieWidth = 0;
		if(BX.browser.IsIE())
		{
			ieTop = 0;
			ieLeft = 1;
			ieWidth = -1;

			if(/MSIE 7/i.test(navigator.userAgent))
			{
				ieTop = -1;
				ieLeft = -1;
				ieWidth = -2;
			}
		}

		var pos = BX.pos(_this.CONTAINER);
		pos.width = pos.right - pos.left;
		_this.RESULT.style.position = 'absolute';
		_this.RESULT.style.top = pos.bottom + ieTop - 1 + 'px';/* modified */
		_this.RESULT.style.left = pos.left + ieLeft + 'px';/* modified */
		_this.RESULT.style.width = (pos.width + ieWidth - 2) + 'px';/* modified */
		if(result != null)
			_this.RESULT.innerHTML = result;

		if(_this.RESULT.innerHTML.length > 0)
			_this.RESULT.style.display = 'block';
		else
			_this.RESULT.style.display = 'none';
	};

	this.onKeyPress = function(keyCode)
	{
		_this.CreateResultWrap();
		var tbl = BX.findChild(_this.RESULT, {'tag':'table','class':'title-search-result'}, true);
		if(!tbl)
			return false;

		var cnt = tbl.rows.length;

		switch (keyCode)
		{
			case 27: // escape key - close search div
				_this.RESULT.style.display = 'none';
				_this.currentRow = -1;
				_this.UnSelectAll();
				return true;

			case 40: // down key - navigate down on search results
				if(_this.RESULT.style.display == 'none')
					_this.RESULT.style.display = 'block';

				var first = -1;
				for(var i = 0; i < cnt; i++)
				{
					if(!BX.findChild(tbl.rows[i], {'class':'title-search-separator'}, true))
					{
						if(first == -1)
							first = i;

						if(_this.currentRow < i)
						{
							_this.currentRow = i;
							break;
						}
						else if(tbl.rows[i].className == 'title-search-selected')
						{
							tbl.rows[i].className = '';
						}
					}
				}

				if(i == cnt && _this.currentRow != i)
					_this.currentRow = first;

				tbl.rows[_this.currentRow].className = 'title-search-selected';
				return true;

			case 38: // up key - navigate up on search results
				if(_this.RESULT.style.display == 'none')
					_this.RESULT.style.display = 'block';

				var last = -1;
				for(var i = cnt-1; i >= 0; i--)
				{
					if(!BX.findChild(tbl.rows[i], {'class':'title-search-separator'}, true))
					{
						if(last == -1)
							last = i;

						if(_this.currentRow > i)
						{
							_this.currentRow = i;
							break;
						}
						else if(tbl.rows[i].className == 'title-search-selected')
						{
							tbl.rows[i].className = '';
						}
					}
				}

				if(i < 0 && _this.currentRow != i)
					_this.currentRow = last;

				tbl.rows[_this.currentRow].className = 'title-search-selected';
				return true;

			case 13: // enter key - choose current search result
				if(_this.RESULT.style.display == 'block')
				{
					for(var i = 0; i < cnt; i++)
					{
						if(_this.currentRow == i)
						{
							if(!BX.findChild(tbl.rows[i], {'class':'title-search-separator'}, true))
							{
								var a = BX.findChild(tbl.rows[i], {'tag':'a'}, true);
								if(a)
								{
									window.location = a.href;
									return true;
								}
							}
						}
					}
				}
				return false;
		}

		return false;
	};

	this.onTimeout = function()
	{
		if (_this.INPUT.value == _this.oldValue || _this.INPUT.value == _this.startText)
		{
			return;
		}

		if (_this.INPUT.value.length >= _this.arParams.MIN_QUERY_LEN)
		{
			_this.oldValue = _this.INPUT.value;
			_this.cache_key = _this.arParams.INPUT_ID + '|' + _this.INPUT.value;
			if (_this.cache[_this.cache_key] == null)
			{
				if (_this.timeout)
					clearInterval(_this.timeout);

				BX.ajax.post(
					_this.arParams.AJAX_PAGE,
					{
						'ajax_call':'y',
						'INPUT_ID':_this.arParams.INPUT_ID,
						'q':_this.INPUT.value
					},
					function(result)
					{
						_this.cache[_this.cache_key] = result;
						_this.ShowResult(result);
						_this.currentRow = -1;
						_this.EnableMouseEvents();
						_this.timeout = setInterval(_this.onTimeout, 500);
					}
				);
			}
			else
			{
				_this.ShowResult(_this.cache[_this.cache_key]);
				_this.currentRow = -1;
				_this.EnableMouseEvents();
			}
		}
		else
		{
			//_this.RESULT.style.display = 'none';
			_this.currentRow = -1;
			_this.UnSelectAll();
		}
	};

	this.UnSelectAll = function()
	{
		var tbl = BX.findChild(_this.RESULT, {'tag':'table','class':'title-search-result'}, true);
		if(tbl)
		{
			var cnt = tbl.rows.length;
			for(var i = 0; i < cnt; i++)
				tbl.rows[i].className = '';
		}
	};

	this.EnableMouseEvents = function()
	{
		var tbl = BX.findChild(_this.RESULT, {'tag':'table','class':'title-search-result'}, true);
		if(tbl)
		{
			var cnt = tbl.rows.length;
			for(var i = 0; i < cnt; i++)
				if(!BX.findChild(tbl.rows[i], {'class':'title-search-separator'}, true))
				{
					tbl.rows[i].id = 'row_' + i;
					tbl.rows[i].onmouseover = function (e) {
						if(_this.currentRow != this.id.substr(4))
						{
							_this.UnSelectAll();
							this.className = 'title-search-selected';
							_this.currentRow = this.id.substr(4);
						}
					};
					tbl.rows[i].onmouseout = function (e) {
						this.className = '';
						_this.currentRow = -1;
					};
				}
		}
	};

	this.onFocusLost = function(hide)
	{
		if (_this.RESULT != null)
		{
			setTimeout(function() {_this.RESULT.style.display = 'none'}, 250);
		}

		if (_this.timeout)
			clearInterval(_this.timeout);
	};

	this.onFocusGain = function()
	{
		_this.CreateResultWrap();
		if(_this.RESULT && _this.RESULT.innerHTML.length)
			_this.ShowResult();

		this.timeout = setInterval(this.onTimeout, 500);
	};

	this.onWindowResize = function()
	{
		if (_this.RESULT != null)
		{
			_this.ShowResult();
		}
	};

	this.onKeyDown = function(event)
	{
		event = event || window.event;

		if (_this.RESULT && _this.RESULT.style.display == 'block')
		{
			if(_this.onKeyPress(event.keyCode))
				return BX.PreventDefault(event);
		}
	};

	this.Init = function()
	{
		this.CONTAINER = BX(this.arParams.CONTAINER_ID);
		this.INPUT = BX(this.arParams.INPUT_ID);
		this.startText = this.oldValue = this.INPUT.value;

		BX.bind(this.INPUT, "focus", BX.proxy(this.onFocusGain, this));
		BX.bind(window, "resize", BX.proxy(this.onWindowResize, this));
		BX.bind(this.INPUT, "blur", BX.proxy(this.onFocusLost));

		if(BX.browser.IsSafari() || BX.browser.IsIE())
			this.INPUT.onkeydown = this.onKeyDown;
		else
			this.INPUT.onkeypress = this.onKeyDown;
	};

	BX.ready(function (){_this.Init(arParams);});
};

/***************** Left Menu ************************/
B24.toggleMenu = function(menuItem, messageShow, messageHide)
{
	var menuBlock = BX.findChild(menuItem.parentNode, {tagName:'ul'}, false, false);

	var menuItems = BX.findChildren(menuBlock, {tagName : "li"}, false);
	if (!menuItems)
		return;

	var toggleText = BX.findChild(menuItem, {className:"menu-toggle-text"}, true, false);
	if (!toggleText)
		return;

	if (BX.hasClass(menuBlock, "menu-items-close"))
	{
		menuBlock.style.height = "0px";
		BX.removeClass(menuBlock, "menu-items-close");
		BX.removeClass(BX.nextSibling(BX.nextSibling(menuItem)), "menu-items-close");
		menuBlock.style.opacity = 0;
		animation(true, menuBlock, menuBlock.scrollHeight);

		toggleText.innerHTML = messageHide;
		BX.userOptions.save("bitrix24", menuItem.id, "hide", "N");
	}
	else
	{
		animation(false, menuBlock, menuBlock.offsetHeight);
		toggleText.innerHTML = messageShow;
		BX.userOptions.save("bitrix24", menuItem.id, "hide", "Y");
	}

	function animation(opening, menuBlock, maxHeight)
	{
		menuBlock.style.overflow = "hidden";
		(new BX.easing({
			duration : 200,
			start : { opacity: opening ? 0 : 100, height: opening ? 0 : maxHeight },
			finish : { opacity: opening ? 100 : 0, height: opening ? maxHeight : 0 },
			transition : BX.easing.transitions.linear,
			step : function(state)
			{
				menuBlock.style.opacity = state.opacity/100;
				menuBlock.style.height = state.height + "px";

			},
			complete : function()
			{
				if (!opening)
				{
					BX.addClass(menuBlock, "menu-items-close");
					BX.addClass(BX.nextSibling(BX.nextSibling(menuItem)), "menu-items-close");
				}
				menuBlock.style.cssText = "";
			}

		})).animate();
	}
};

/***************** Help Popup ************************/
B24.HelpPopup = function(steps, bindELement, settings)
{
	this.currentStep = null;
	this.layout = {
		title : null,
		paging : null,
		previousButton : null,
		nextButton : null,
		link : null,
		banner : null
	};

	this.selectedClass = BX.type.isNotEmptyString(settings.selectedClass) ? settings.selectedClass : "b24-popup-selected";
	this.steps = [];
	this.settings = settings || {};

	if (settings && settings.video)
	{
		this.createVideoLayout(steps);
	}
	else
	{
		this.createHelpLayout(steps);
	}

	var defaultStep = BX.type.isNumber(settings.defaultStep) ? settings.defaultStep : 0;
	if (settings && BX.type.isNotEmptyString(settings.context))
	{
		loop:
		for (var i = 0; i < this.steps.length; i++)
		{
			if (!BX.type.isArray(this.steps[i].patterns))
			{
				continue;
			}

			for (var j=0; j < this.steps[i].patterns.length; j++)
			{
				if (this.steps[i].patterns[j].test(settings.context))
				{
					defaultStep = i;
					break loop;
				}
			}

		}
	}

	this.showStepByNumber(defaultStep);
};

B24.HelpPopup.prototype.createHelpLayout = function(steps)
{
	var content = [];
	var paging = [];
	if (BX.type.isArray(steps))
	{
		for (var i = 0; i < steps.length; i++)
		{
			var step = steps[i];
			if (!BX.type.isNotEmptyString(step.title) || !BX.type.isNotEmptyString(step.content))
				continue;

			var stepContent = BX.create("div", { props : { className : "b24-help-popup-step" },  children : [
				BX.create("div", { props:{ className: "b24-help-popup-title" }, html : step.title }),
				BX.create("div", { props:{ className: "b24-help-popup-content" }, html : step.content })
			]});
			var stepPage = BX.create("span", {
				props : { className : "b24-help-popup-page"},
				html : i+1,
				events : { click : BX.proxy(this.onPageClick, this)}
			});
			this.steps.push({ content : stepContent, page : stepPage });

			content.push(stepContent);
			paging.push(stepPage);
		}
	}

	this.popup = BX.PopupWindowManager.create("b24-help-popup", null, {
		closeIcon : { top : "10px", right : "15px"},
		offsetTop : 1,
		overlay : { opacity : 20 },
		lightShadow : true,
		draggable : { restrict : true},
		closeByEsc : true,
		titleBar: {content: BX.create("span", {html: BX.message['B24_HELP_TITLE'] ? BX.message('B24_HELP_TITLE') : "Help"})},
		content : BX.create("div", { props : { className : "b24-help-popup" }, children : [
			BX.create("div", { props : { className : "b24-help-popup-contents" }, children : content }),
			BX.create("div", { props : { className : "b24-help-popup-navigation" }, children : [
				(this.layout.paging = BX.create("div", { props:{ className: "b24-help-popup-paging" }, children: paging })),
				BX.create("div", { props : { className : "b24-help-popup-buttons" }, children : [
					(this.layout.previousButton = BX.create("span", {
						props:{ className: "popup-window-button" },
						events : { click : BX.proxy(this.showPrevStep, this) },
						children:[
							BX.create("span", { props:{ className: "popup-window-button-left" }}),
							BX.create("span", { props:{ className: "popup-window-button-text" }, html: BX.message['B24_HELP_PREV'] ? BX.message('B24_HELP_PREV') : "&larr; Back" }),
							BX.create("span", { props:{ className: "popup-window-button-right" }})
						]
					})),
					(this.layout.nextButton = BX.create("span", {
						props:{ className:"popup-window-button" },
						events : { click : BX.proxy(this.showNextStep, this) },
						children:[
							BX.create("span", { props:{ className: "popup-window-button-left" }}),
							BX.create("span", { props:{ className: "popup-window-button-text" }, html: BX.message['B24_HELP_NEXT'] ? BX.message('B24_HELP_NEXT') : "Next &rarr;" }),
							BX.create("span", { props:{ className: "popup-window-button-right" }})
						]
					}))
				]})
			]})
		]})
	});
};

B24.HelpPopup.prototype.createVideoLayout = function(steps)
{
	BX.addCustomEvent(this, "onShowStep", BX.proxy(this.onShowVideoStep, this));
	var content = [];
	var paging = [];
	if (BX.type.isArray(steps))
	{
		steps.push({
			id: "other",
			"patterns": [],
			"learning_path": "",
			"title": this.settings.learning_title,
			"title_full": this.settings.learning_title_full,
			"content": "<div class=\"b24-video-popup-player\"><a class=\"b24-video-popup-learning-banner\" href=\"http://dev.1c-bitrix.ru/learning/bitrix24/\" target=\"_blank\"><img src=\"/bitrix/templates/bitrix24/images/video-help-bg.png\" width=\"480\" height=\"270\"><span class=\"b24-video-popup-learn-question\">" + this.settings.learning_question + "<\/span><span class=\"b24-video-popup-learn-answer\">" + this.settings.learning_answer + "<\/span><\/a><\/div>"
		});

		for (var i = 0; i < steps.length; i++)
		{
			var step = steps[i];

			if (BX.type.isNotEmptyString(step.youtube) && !BX.type.isNotEmptyString(step.content))
			{
				step.content = "<iframe width=\"480\" height=\"270\" src=\"https://www.youtube.com/embed/"+ step.youtube + "?rel=0&fs=1\" frameborder=\"0\" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>";
			}

			if (!BX.type.isNotEmptyString(step.title) || !BX.type.isNotEmptyString(step.content))
				continue;

			var stepContent = null;
			if (step.content.indexOf("iframe") !== -1)
			{
				stepContent = BX.create("div", { props : { className : "b24-video-popup-step" }, children : [
					BX.create("div", { props : { className : "b24-video-popup-player" }, html : step.content })
				]});

				var iframe = stepContent.getElementsByTagName("iframe");
				if (iframe.length > 0)
				{
					var src = iframe[0].getAttribute("src");
					iframe[0].setAttribute("data-src", src);
					iframe[0].setAttribute("src", "");
				}
			}
			else
			{
				stepContent = BX.create("div", { props : { className : "b24-video-popup-step" }, html : step.content });
			}

			var stepPage = BX.create("div", {
				props : { className : "b24-video-popup-menu-item"},
				events : { click : BX.proxy(this.onPageClick, this)},
				children : [
					BX.create("div", { props : { className : "b24-video-popup-menu-index" }, html : (i+1) + "." }),
					BX.create("div", { props : { className : "b24-video-popup-menu-title" }, html : step.title })
				]
			});
			this.steps.push({
				title : step.title,
				title_full : BX.type.isNotEmptyString(step.title_full) ? step.title_full : step.title,
				content : stepContent,
				learning_path : step.learning_path,
				page : stepPage,
				id : BX.type.isNotEmptyString(step.id) ? step.id : null,
				patterns : BX.type.isArray(step.patterns) ? step.patterns : []
			});

			content.push(stepContent);
			paging.push(stepPage);
		}
	}

	this.popup = BX.PopupWindowManager.create("b24-video-popup", null, {
		closeIcon : { top : "20px", right : "20px"},
		offsetTop : 1,
		overlay : { opacity : 20 },
		lightShadow : true,
		draggable : { restrict : true},
		closeByEsc : true,
		events : {
			onPopupClose : BX.proxy(function(popupWindow)
			{
				this.unsetFrameSrc(this.currentStep);

				var transformProperty = BX.browser.isPropertySupported("transform");
				var helpBlock = BX("help-block");
				if (!transformProperty || !helpBlock)
					return;

				BX.addClass(popupWindow.popupContainer, "b24-help-popup-animation");

				var minScale = 5;
				var start = { height : popupWindow.popupContainer.offsetHeight, scale : 100 };
				var finish  = { height : 0, scale : minScale };

				var helpPos = BX.pos(helpBlock);
				var popupPos = BX.pos(popupWindow.popupContainer);
				start.left = popupPos.left;
				start.top = popupPos.top;
				finish.left = helpPos.left - ((popupPos.width - popupPos.width * (minScale / 100)) / 2);
				finish.top = helpPos.top - ((popupPos.height - popupPos.height * (minScale / 100)) / 2);

				(new BX.easing({
					duration : 500,
					start : start,
					finish : finish,
					transition : BX.easing.makeEaseOut(BX.easing.transitions.quad),
					step : BX.proxy(function(state){
						popupWindow.popupContainer.style[transformProperty] = "scale(" + state.scale / 100 +")";
						popupWindow.popupContainer.style.left = state.left + "px";
						popupWindow.popupContainer.style.top = state.top + "px";
					}, popupWindow),
					complete : BX.proxy(function() {
						popupWindow.popupContainer.style[transformProperty] = "none";
						BX.removeClass(popupWindow.popupContainer, "b24-help-popup-animation");
						popupWindow.adjustPosition();
					}, popupWindow)
				})).animate();
			}, this),
			onPopupShow: BX.proxy(function(popupWindow) {

				this.setFrameSrc(this.currentStep);
				var transformProperty = BX.browser.isPropertySupported("transform");
				if (transformProperty)
				{
					popupWindow.popupContainer.style.opacity = 0;
					var minScale = 5;
					(new BX.easing({
						duration : 500,
						start : { opacity : 0, scale : minScale },
						finish : { opacity : 100, scale : 100 },
						transition : BX.easing.makeEaseOut(BX.easing.transitions.quart),
						step : BX.proxy(function(state){
							popupWindow.popupContainer.style[transformProperty] = "scale(" + state.scale / 100 +")";
							popupWindow.popupContainer.style.opacity = state.opacity/100;
						}, popupWindow),
						complete : BX.proxy(function() {
							popupWindow.popupContainer.style[transformProperty] = "none";
							popupWindow.adjustPosition();
							this.scrollToCurrent();
						}, this)
					})).animate();
				}

			}, this)
		},
		content : BX.create("div", { props : { className : "b24-video-popup" }, children : [
			(this.layout.title = BX.create("div", { props : { className : "b24-video-popup-title" } })),
			BX.create("div", { props : { className : "b24-video-popup-contents" }, children : [
				BX.create("div", { props : { className : "b24-video-popup-menu" }, children : paging}),
				BX.create("div", { props : { className : "b24-video-popup-steps " }, children : content })
			]}),
			BX.create("div", { props : { className : "b24-video-popup-learning" }, children : [
				BX.create("span", { text : this.settings.learning_question + " "}),
				(this.layout.link = BX.create("a", {
					props : { href : "" },
					text : this.settings.learning_answer,
					attrs : { target : "_blank" }
				}))
			]})
		]})
	});

	var otherStepPos = this.getStepPositionById("other");
	if (otherStepPos >= 0)
	{
		this.layout.banner = BX.findChild(this.steps[otherStepPos].content, { className : "b24-video-popup-learning-banner"},  true);
	}

};

B24.HelpPopup.prototype.onShowVideoStep = function(prevStep, newStep)
{
	this.setLearningLink(newStep);

	if (!prevStep)
	{
		return;
	}

	this.setFrameSrc(newStep);
	this.unsetFrameSrc(prevStep);
};

B24.HelpPopup.prototype.setFrameSrc = function(step)
{
	if (!step)
	{
		return;
	}

	var iframe = step.content.getElementsByTagName("iframe");
	if (iframe.length > 0 && iframe[0].getAttribute("data-src") != iframe[0].getAttribute("src"))
	{
		iframe[0].setAttribute("src", iframe[0].getAttribute("data-src"));
	}
};

B24.HelpPopup.prototype.unsetFrameSrc = function(step)
{
	if (!step)
	{
		return;
	}

	var iframe = step.content.getElementsByTagName("iframe");
	if (iframe.length > 0)
	{
		iframe[0].setAttribute("src", "");
	}
};

B24.HelpPopup.prototype.setLearningLink = function(step)
{
	var path = BX.type.isNotEmptyString(step.learning_path) && this.settings.currentStepId != step.id
				? step.learning_path
				: (window.location.pathname == "/" ? "/start/" : window.location.pathname);

	if (BX.type.isNotEmptyString(this.settings.site_dir) && this.settings.site_dir != "/")
	{
		path = path.replace(this.settings.site_dir, "/");
	}

	path = path.replace(/\d+/gi, "");
	path = path.replace("/contacts/", "/company/");
	path = path.replace(/^\/company\/personal\/user\/\/files\/(.*)/, "/docs/");
	path = path.replace(/^\/workgroups\/group\/\/files\/(.*)/, "/docs/");
	path = path.replace(/^\/docs\/(.*)/, "/docs/");

	var href = this.settings.learning_url + "?path=" + encodeURIComponent(path);
	this.layout.link.href = href;

	if (this.layout.banner)
	{
		this.layout.banner.href = href;
	}
};

B24.HelpPopup.prototype.scrollToCurrent = function()
{
	var menu = this.currentStep.page.offsetParent;
	var menuHeight = menu.offsetHeight;
	var menuItem = this.currentStep.page;
	var menuItemOffset = menuItem.offsetTop;
	var menuItemHeight = menuItem.offsetHeight;
	if ( (menuItemOffset + menuItemHeight) > menuHeight)
	{
		menu.scrollTop = menuItemOffset - menuHeight + menuItemHeight;
	}
	else
	{
		menu.scrollTop = 0;
	}
};

B24.HelpPopup.prototype.showStepByNumber = function(number)
{
	if (!this.steps[number] || this.currentStep == this.steps[number])
		return;

	if (this.currentStep != null)
	{
		this.currentStep.content.style.display = "none";
		BX.removeClass(this.currentStep.page, this.selectedClass);
	}

	this.steps[number].content.style.display = "block";
	BX.addClass(this.steps[number].page, this.selectedClass);

	if (this.layout.title)
	{
		this.layout.title.innerHTML = this.steps[number].title_full;
	}

	BX.onCustomEvent(this, "onShowStep", [this.currentStep, this.steps[number]]);

	this.currentStep = this.steps[number];
};

B24.HelpPopup.prototype.onPageClick = function(event)
{
	for (var i = 0; i < this.steps.length; i++)
	{
		if (this.steps[i].page == BX.proxy_context)
		{
			this.showStepByNumber(i);
			break;
		}
	}
};

B24.HelpPopup.prototype.showNextStep = function()
{
	var currentPosition = this.getStepPosition(this.currentStep);

	if (currentPosition + 1 > this.steps.length - 1)
		this.showStepByNumber(0);
	else
		this.showStepByNumber(currentPosition + 1);
};

B24.HelpPopup.prototype.showPrevStep = function()
{
	var currentPosition = this.getStepPosition(this.currentStep);
	if (currentPosition > 0)
		this.showStepByNumber(currentPosition - 1);
	else
		this.showStepByNumber(this.steps.length - 1);
};

B24.HelpPopup.prototype.getStepPosition = function(step)
{
	for (var i = 0; i < this.steps.length; i++)
	{
		if (this.steps[i] == step)
			return i;
	}

	return -1;
};

B24.HelpPopup.prototype.getStepPositionById = function(stepId)
{
	for (var i = 0; i < this.steps.length; i++)
	{
		if (this.steps[i].id == stepId)
			return i;
	}

	return -1;
};

function showPartnerForm(arParams)
{
	BX = window.BX;
	BX.Bitrix24PartnerForm =
	{
		bInit: false,
		popup: null,
		arParams: {}
	}
	BX.Bitrix24PartnerForm.arParams = arParams;
	BX.message(arParams['MESS']);
	BX.Bitrix24PartnerForm.popup = BX.PopupWindowManager.create("BXPartner", null, {
		autoHide: false,
		zIndex: 0,
		offsetLeft: 0,
		offsetTop: 0,
		overlay : true,
		draggable: {restrict:true},
		closeByEsc: true,
		titleBar: {content: BX.create("span", {html: BX.message('BX24_PARTNER_TITLE')})},
		closeIcon: { right : "12px", top : "10px"},
		buttons: [
			new BX.PopupWindowButtonLink({
				text: BX.message('BX24_CLOSE_BUTTON'),
				className: "popup-window-button-link-cancel",
				events: { click : function()
				{
					this.popupWindow.close();
				}}
			})
		],
		content: '<div style="width:450px;height:230px"></div>',
		events: {
			onAfterPopupShow: function()
			{
				this.setContent('<div style="width:450px;height:230px">'+BX.message('BX24_LOADING')+'</div>');
				BX.ajax.post(
					'/bitrix/tools/b24_site_partner.php',
					{
						lang: BX.message('LANGUAGE_ID'),
						site_id: BX.message('SITE_ID') || '',
						arParams: BX.Bitrix24PartnerForm.arParams
					},
					BX.delegate(function(result)
						{
							this.setContent(result);
						},
						this)
				);
			}
		}
	});

	BX.Bitrix24PartnerForm.popup.show();
}

/****************** Timemanager *********************/
B24.Timemanager = {

	inited : false,

	layout : {
		block : null,
		timer : null,
		info : null,
		event : null,
		tasks : null,
		status : null
	},

	data : null,
	timer : null,
	clock : null,

	formatTime : function(ts, bSec)
	{
		return BX.util.str_pad(parseInt(ts/3600), 2, '0', 'left')+':'+BX.util.str_pad(parseInt(ts%3600/60), 2, '0', 'left')+(!!bSec ? (':'+BX.util.str_pad(ts%60, 2, '0', 'left')) : '');
	},

	formatWorkTime : function(h, m, s)
	{
		return '<span class="tm-popup-notice-time-hours"><span class="tm-popup-notice-time-number">' + h + '</span></span><span class="tm-popup-notice-time-minutes"><span class="tm-popup-notice-time-number">' + BX.util.str_pad(m, 2, '0', 'left') + '</span></span><span class="tm-popup-notice-time-seconds"><span class="tm-popup-notice-time-number">' + BX.util.str_pad(s, 2, '0', 'left') + '</span></span>';
	},

	formatCurrentTime : function(hours, minutes, seconds)
	{
		var mt = "";
		if (BX.isAmPmMode())
		{
			mt = "AM";
			if (hours > 12)
			{
				hours = hours - 12;
				mt = "PM";
			}
			else if (hours == 0)
			{
				hours = 12;
				mt = "AM";
			}
			else if (hours == 12)
			{
				mt = "PM";
			}

			mt = '<span class="time-am-pm">' + mt + '</span>';
		}
		else
			hours = BX.util.str_pad(hours, 2, "0", "left");

		return '<span class="time-hours">' + hours + '</span>' +
			'<span class="time-semicolon">:</span>' +
			'<span class="time-minutes">' + BX.util.str_pad(minutes, 2, "0", "left") + '</span>' +
			mt;
	},

	init : function(reportJson)
	{
		BX.addCustomEvent("onTimeManDataRecieved", BX.proxy(this.onDataRecieved, this));
		BX.addCustomEvent("onTimeManNeedRebuild", BX.proxy(this.onDataRecieved, this));
		BX.addCustomEvent("onPlannerDataRecieved", BX.proxy(this.onPlannerDataRecieved, this));
		BX.addCustomEvent("onPlannerQueryResult", BX.proxy(this.onPlannerQueryResult, this));
		BX.addCustomEvent("onTaskTimerChange", BX.proxy(this.onTaskTimerChange, this));

		BX.timer.registerFormat("worktime_notice_timeman",BX.proxy(this.formatWorkTime, this));
		BX.timer.registerFormat("bitrix24_time",BX.proxy(this.formatCurrentTime, this));

		BX.addCustomEvent(window, "onTimemanInit", BX.proxy(function() {

			this.inited = true;

			this.layout.block = BX("timeman-block");
			this.layout.timer = BX("timeman-timer");
			this.layout.info = BX("timeman-info");
			this.layout.event = BX("timeman-event");
			this.layout.tasks = BX("timeman-tasks");
			this.layout.status = BX("timeman-status");
			this.layout.statusBlock = BX("timeman-status-block");
			this.layout.taskTime = BX("timeman-task-time");
			this.layout.taskTimer = BX("timeman-task-timer");

			window.BXTIMEMAN.ShowFormWeekly(reportJson);

			BX.bind(this.layout.block, "click", BX.proxy(this.onTimemanClick, this));

			BXTIMEMAN.setBindOptions({
				node: this.layout.block,
				mode: "popup",
				popupOptions: {
					angle : { position : "top", offset : 130},
					offsetTop : 10,
					autoHide : true,
					offsetLeft : -60,
					zIndex : -1,
					events : {
						onPopupClose : BX.proxy(function() {
							BX.removeClass(this.layout.block, "timeman-block-active");
						}, this)
					}
				}
			});

			this.redraw();

		}, this));
	},

	onTimemanClick : function()
	{
		BX.addClass(this.layout.block, "timeman-block-active");
		BXTIMEMAN.Open();
	},

	onTaskTimerChange : function(params)
	{
		if (params.action === 'refresh_daemon_event')
		{
			if(!!this.taskTimerSwitch)
			{
				this.layout.taskTime.style.display = '';
				if(this.layout.info.style.display != 'none')
				{
					this.layout.statusBlock.style.display = 'none';
				}
				this.taskTimerSwitch = false;
			}

			var s = '';
			s += this.formatTime(parseInt(params.data.TIMER.RUN_TIME||0) + parseInt(params.data.TASK.TIME_SPENT_IN_LOGS||0), true);

			if(!!params.data.TASK.TIME_ESTIMATE && params.data.TASK.TIME_ESTIMATE > 0)
			{
				s += ' / ' + this.formatTime(parseInt(params.data.TASK.TIME_ESTIMATE));
			}

			this.layout.taskTimer.innerHTML = s;
		}
		else if(params.action === 'start_timer')
		{
			this.taskTimerSwitch = true;
		}
		else if(params.action === 'stop_timer')
		{
			this.layout.taskTime.style.display = 'none';
			this.layout.statusBlock.style.display = '';
		}
	},

	setTimer : function()
	{
		if (this.timer)
		{
			this.timer.setFrom(new Date(this.data.INFO.DATE_START * 1000));
			this.timer.dt = -this.data.INFO.TIME_LEAKS * 1000;
		}
		else
		{
			this.timer = BX.timer(this.layout.timer, {
				from: new Date(this.data.INFO.DATE_START*1000),
				dt: -this.data.INFO.TIME_LEAKS * 1000,
				display: "simple"
			});
		}
	},

	stopTimer : function()
	{
		if (this.timer != null)
		{
			BX.timer.stop(this.timer);
			this.timer = null;
		}
	},

	redraw_planner: function(data)
	{
		if(!!data.TASKS_ENABLED)
		{
			data.TASKS_COUNT = !data.TASKS_COUNT ? 0 : data.TASKS_COUNT;
			this.layout.tasks.innerHTML = data.TASKS_COUNT;
			this.layout.tasks.style.display = data.TASKS_COUNT == 0 ? "none" : "inline-block";
		}

		if(!!data.CALENDAR_ENABLED)
		{
			this.layout.event.innerHTML = data.EVENT_TIME;
			this.layout.event.style.display = data.EVENT_TIME == '' ? 'none' : 'inline-block';
		}

		this.layout.info.style.display =
			(BX.style(this.layout.tasks, "display") == 'none' && BX.style(this.layout.event, "display") == 'none')
				? 'none'
				: 'block';
	},

	redraw : function()
	{
		this.redraw_planner(this.data.PLANNER);

		if (this.data.STATE == "CLOSED" && (this.data.CAN_OPEN == "REOPEN" || !this.data.CAN_OPEN))
			this.layout.status.innerHTML = this.getStatusName("COMPLETED");
		else
			this.layout.status.innerHTML = this.getStatusName(this.data.STATE);

		// if (this.data.STATE == "OPENED")
		// 	this.setTimer();
		// else
		// {
		// 	this.stopTimer();
		// 	var workedTime = (this.data.INFO.DATE_FINISH - this.data.INFO.DATE_START - this.data.INFO.TIME_LEAKS);
		// 	this.layout.timer.innerHTML = BX.timeman.formatTime(workedTime);
		// }
		if (!this.timer)
			this.timer = BX.timer({container: this.layout.timer, display : "bitrix24_time"}); //BX.timer.clock(this.layout.timer);

		var statusClass = "";
		if (this.data.STATE == "CLOSED")
		{
			if (this.data.CAN_OPEN == "REOPEN" || !this.data.CAN_OPEN)
				statusClass = "timeman-completed";
			else
				statusClass = "timeman-start";
		}
		else if (this.data.STATE == "PAUSED")
			statusClass = "timeman-paused";
		else if (this.data.STATE == "EXPIRED")
			statusClass = "timeman-expired";

		BX.removeClass(this.layout.block, "timeman-completed timeman-start timeman-paused timeman-expired");
		BX.addClass(this.layout.block, statusClass);

		if (statusClass == "timeman-start" || statusClass == "timeman-paused")
		{
			this.startAnimation();
		}
		else
		{
			this.endAnimation();
		}
	},

	getStatusName : function(id)
	{
		return BX.message("TM_STATUS_" + id);
	},

	onDataRecieved : function(data)
	{
		data.OPEN_NOW = false;

		this.data = data;

		if (this.inited)
			this.redraw();
	},

	onPlannerQueryResult : function(data, action)
	{
		if (this.inited)
			this.redraw_planner(data);
	},

	onPlannerDataRecieved : function(ob, data)
	{
		if (this.inited)
			this.redraw_planner(data);
	},

	animation : null,
	animationTimeout : 30000,
	blinkAnimation : null,
	blinkLimit : 10,
	blinkTimeout : 750,

	startAnimation : function()
	{
		if (this.animation !== null)
		{
			this.endAnimation();
		}

		this.startBlink();
		this.animation = setInterval(BX.proxy(this.startBlink, this), this.animationTimeout);
	},

	endAnimation : function()
	{
		this.endBlink();

		if (this.animation)
		{
			clearInterval(this.animation);
		}

		this.animation = null;
	},

	startBlink : function()
	{
		if (this.blinkAnimation !== null)
		{
			this.endBlink();
		}

		var counter = 0;
		this.blinkAnimation = setInterval(BX.proxy(function()
		{
			if (++counter >= this.blinkLimit)
			{
				clearInterval(this.blinkAnimation);
				BX.show(BX("timeman-background", true));
			}
			else
			{
				BX.toggle(BX("timeman-background", true));
			}

		}, this), this.blinkTimeout);
	},

	endBlink : function()
	{
		if (this.blinkAnimation)
		{
			clearInterval(this.blinkAnimation);
		}

		BX("timeman-background", true).style.cssText = "";
		this.blinkAnimation = null;
	}
};

/****************** Invite Dialog *******************/
B24.Bitrix24InviteDialog =
{
	bInit: false,
	popup: null,
	arParams: {}
};

B24.Bitrix24InviteDialog.Init = function(arParams)
{
	if(arParams)
		B24.Bitrix24InviteDialog.arParams = arParams;

	if(B24.Bitrix24InviteDialog.bInit)
		return;

	BX.message(arParams['MESS']);

	B24.Bitrix24InviteDialog.bInit = true;

	BX.ready(BX.delegate(function()
	{
		B24.Bitrix24InviteDialog.popup = BX.PopupWindowManager.create("B24InviteDialog", null, {
			autoHide: false,
			zIndex: 0,
			offsetLeft: 0,
			offsetTop: 0,
			overlay:true,
			draggable: {restrict:true},
			closeByEsc: true,
			titleBar: {content: BX.create("span", {html: BX.message('BX24_INVITE_TITLE_INVITE')})},
			closeIcon: { right : "12px", top : "10px"},
			buttons: [
			],
			content: '<div style="width:450px;height:230px"></div>',
			events: {
				onAfterPopupShow: function()
				{
					this.setContent('<div style="width:450px;height:230px">'+BX.message('BX24_LOADING')+'</div>');
					BX.ajax.post(
						'/bitrix/tools/intranet_invite_dialog.php',
						{
							lang: BX.message('LANGUAGE_ID'),
							site_id: BX.message('SITE_ID') || '',
							arParams: B24.Bitrix24InviteDialog.arParams
						},
						BX.delegate(function(result)
							{
								this.setContent(result);
							},
							this)
					);
				},
				onPopupClose: function()
				{
					BX.InviteDialog.onInviteDialogClose();
				}
			}
		});
	}, this));
};

B24.Bitrix24InviteDialog.ShowForm = function(arParams)
{
	B24.Bitrix24InviteDialog.Init(arParams);
	B24.Bitrix24InviteDialog.popup.params.zIndex = (BX.WindowManager? BX.WindowManager.GetZIndex() : 0);
	B24.Bitrix24InviteDialog.popup.show();
};

B24.Bitrix24InviteDialog.ReInvite = function(reinvite_user_id)
{
	BX.ajax.post(
		'/bitrix/tools/intranet_invite_dialog.php',
		{
			lang: BX.message('LANGUAGE_ID'),
			site_id: BX.message('SITE_ID') || '',
			reinvite: reinvite_user_id,
			sessid: BX.bitrix_sessid()
		},
		BX.delegate(function(result)
			{
			},
			this)
	);
};

B24.connectionStatus = function(status)
{
	if (!(status == 'online' || status == 'connecting' || status == 'offline'))
		return false;

	if (this.b24ConnectionStatusState == status)
		return false;

	this.b24ConnectionStatusState = status;

	var statusClass = '';

	if (status == 'offline')
	{
		b24ConnectionStatusStateText = BX.message('BITRIX24_CS_OFFLINE');
		statusClass = 'bx24-connection-status-offline';
	}
	else if (status == 'connecting')
	{
		b24ConnectionStatusStateText = BX.message('BITRIX24_CS_CONNECTING');
		statusClass = 'bx24-connection-status-connecting';
	}
	else if (status == 'online')
	{
		b24ConnectionStatusStateText = BX.message('BITRIX24_CS_ONLINE');
		statusClass = 'bx24-connection-status-online';
	}

	clearTimeout(this.b24ConnectionStatusTimeout);

	var connectionPopup = document.querySelector('[data-role="b24-connection-status"]');
	if (!connectionPopup)
	{
		var windowScroll = BX.GetWindowScrollPos();
		var isFloat = windowScroll.scrollTop > 60;

		this.b24ConnectionStatus = BX.create("div", {
			attrs : {
				className : "bx24-connection-status "+(this.b24ConnectionStatusState == 'online'? "bx24-connection-status-hide": "bx24-connection-status-show bx24-connection-status-"+this.b24ConnectionStatusState)+(isFloat? " bx24-connection-status-float": ""),
				"data-role" : "b24-connection-status",
				"data-float" : isFloat? "true": "false"
			},
			children : [
				BX.create("div", { props : { className : "bx24-connection-status-wrap" }, children : [
					this.b24ConnectionStatusText = BX.create("span", { props : { className : "bx24-connection-status-text"}, html: b24ConnectionStatusStateText}),
					BX.create("span", { props : { className : "bx24-connection-status-text-reload"}, children : [
						BX.create("span", { props : { className : "bx24-connection-status-text-reload-title"}, html: BX.message('BITRIX24_CS_RELOAD')}),
						BX.create("span", { props : { className : "bx24-connection-status-text-reload-hotkey"}, html: (BX.browser.IsMac()? "&#8984;+R": "Ctrl+R")})
					], events: {
						'click': function(){ location.reload() }
					}})
				]})
			]
		});
	}
	else
	{
		this.b24ConnectionStatus = connectionPopup;
	}

	if (!this.b24ConnectionStatus)
		return false;

	if (status == 'online')
	{
		clearTimeout(this.b24ConnectionStatusTimeout);
		this.b24ConnectionStatusTimeout = setTimeout(BX.delegate(function(){
			BX.removeClass(this.b24ConnectionStatus, "bx24-connection-status-show");
			this.b24ConnectionStatusTimeout = setTimeout(BX.delegate(function(){
				BX.removeClass(this.b24ConnectionStatus, "bx24-connection-status-hide");
			}, this), 1000);
		}, this), 4000);
	}

	this.b24ConnectionStatus.className = "bx24-connection-status bx24-connection-status-show "+statusClass+" "+(this.b24ConnectionStatus.getAttribute('data-float') == 'true'? 'bx24-connection-status-float': '');
	this.b24ConnectionStatusText.innerHTML = b24ConnectionStatusStateText;

	if (!connectionPopup)
	{
		var nextNode = BX.findChild(document.body, {className: "bx-layout-inner-table"}, true, false);
		nextNode.parentNode.insertBefore(this.b24ConnectionStatus, nextNode);
	}

	return true;
}
