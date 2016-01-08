var ECUserControll = function(Params)
{
	this.oEC = Params.oEC;
	var _this = this;
	this.count = 0;
	this.countAgr = 0;
	this.countDec = 0;

	this.bEditMode = Params.view !== true;
	this.pAttendeesCont = Params.AttendeesCont;
	this.pAttendeesList = Params.AttendeesList;
	this.pParamsCont = Params.AdditionalParams;
	this.pSummary = Params.SummaryCont;

	this.pCount = this.pSummary.appendChild(BX.create("A", {props: {className: 'bxc-count', href:"javascript:void(0)"}}));
	this.pCountArg = this.pSummary.appendChild(BX.create("A", {props: {className: 'bxc-count-agr', href:"javascript:void(0)"}}));
	this.pCountDec = this.pSummary.appendChild(BX.create("A", {props: {className: 'bxc-count-dec', href:"javascript:void(0)"}}));

	this.pCount.onclick = function(){_this.ListMode('all');};
	this.pCountArg.onclick = function(){_this.ListMode('agree');};
	this.pCountDec.onclick = function(){_this.ListMode('decline');};

	this._getFromDate = (Params.fromDateGetter && typeof Params.fromDateGetter == 'function') ? Params.fromDateGetter : function(){return false;};
	this._getToDate = (Params.toDateGetter && typeof Params.toDateGetter == 'function') ? Params.toDateGetter : function(){return false;};
	this._getEventId = (Params.eventIdGetter && typeof Params.eventIdGetter == 'function') ? Params.eventIdGetter : function(){return false;};

	this.ListMode('all');
	this.Attendees = {};

	// Only if we need to add or delete users
	if (this.bEditMode)
	{
		this.pLinkCont = Params.AddLinkCont;
		var
			pIcon = this.pLinkCont.appendChild(BX.create("I")),
			pTitle = this.pLinkCont.appendChild(BX.create("SPAN", {text: EC_MESS.AddAttendees}));
		pIcon.onclick = pTitle.onclick = BX.proxy(this.OpenSelectUser, this);

		var arMenuItems = [{text : EC_MESS.AddGuestsDef, onclick: BX.proxy(this.OpenSelectUser, this)}];
		if (!this.oEC.bExtranet && this.oEC.type == 'group')
			arMenuItems.push({text : EC_MESS.AddGroupMemb, title: EC_MESS.AddGroupMembTitle, onclick: BX.proxy(this.oEC.AddGroupMembers, this.oEC)});
		//arMenuItems.push({text : EC_MESS.AddGuestsEmail,onclick: BX.proxy(this.AddByEmail, this)});

		if (arMenuItems.length > 1)
		{
			pMore = this.pLinkCont.appendChild(BX.create("A", {props: {href: 'javascript: void(0);', className: 'bxec-add-more'}}));
			pMore.onclick = function()
			{
				BX.PopupMenu.show('bxec_add_guest_menu', _this.pLinkCont, arMenuItems, {events: {onPopupClose: function() {BX.removeClass(pMore, "bxec-add-more-over");}}});
				BX.addClass(pMore, "bxec-add-more-over");
			};
		}

		BX.addCustomEvent(window, "onUserSelectorOnChange", BX.proxy(this.UserOnChange, this));
	}
}

ECUserControll.prototype = {
SetValues: function(Attendees)
{
	var i, l = Attendees.length, User;

	// Clear list
	BX.cleanNode(this.pAttendeesList);
	this.Attendees = {};
	this.count = 0;
	this.countAgr = 0;
	this.countDec = 0;

	for(i = 0; i < l; i++)
	{
		User = Attendees[i];
		User.key = User.id || User.email;
		if (User && User.key && !this.Attendees[User.key])
			this.DisplayAttendee(User);
	}

	if (this.bEditMode)
	{
		this.DisableUserOnChange(true, true);
		O_BXCalUserSelect.setSelected(Attendees);
	}

	this.UpdateCount();
},

GetValues: function()
{
	// for (var key in this.Attendees)
	// {
	// }

	return this.Attendees;
},

SetEmpty: function(bEmpty)
{
	if (this.bEmpty === bEmpty)
		return;

	BX.onCustomEvent(this, 'SetEmpty', [bEmpty]);

	if (bEmpty)
	{
		BX.addClass(this.pAttendeesCont, 'bxc-att-empty');
		if (this.pParamsCont)
			this.pParamsCont.style.display = 'none';
	}
	else
	{
		BX.removeClass(this.pAttendeesCont, 'bxc-att-empty');
		if (this.pParamsCont)
			this.pParamsCont.style.display = '';
	}
	this.bEmpty = bEmpty;
},

UpdateCount: function()
{
	this.pCount.innerHTML = EC_MESS.AttSumm + ' - ' + (parseInt(this.count) || 0);

	if (this.countAgr > 0)
	{
		this.pCountArg.innerHTML = EC_MESS.AttAgr + ' - ' + parseInt(this.countAgr);
		this.pCountArg.style.display = "";
	}
	else
	{
		this.pCountArg.style.display = "none";
	}

	if (this.countDec > 0)
	{
		this.pCountDec.innerHTML = EC_MESS.AttDec + ' - ' + parseInt(this.countDec);
		this.pCountDec.style.display = "";
	}
	else
	{
		this.pCountDec.style.display = "none";
	}

	this.SetEmpty(this.count == 0);
},

OpenSelectUser : function(e)
{
	if (BX.PopupMenu && BX.PopupMenu.currentItem)
		BX.PopupMenu.currentItem.popupWindow.close();

	if(!e) e = window.event;
	if (!this.SelectUserPopup)
	{
		var _this = this;
		this.SelectUserPopup = BX.PopupWindowManager.create("bxc-user-popup", this.pLinkCont, {
			offsetTop : 1,
			autoHide : true,
			closeByEsc : true,
			content : BX("BXCalUserSelect_selector_content"),
			className: 'bxc-popup-user-select',
			buttons: [
				new BX.PopupWindowButton({
					text: EC_MESS.Add,
					events: {click : function()
					{
						_this.SelectUserPopup.close();
						for (var id in _this.selectedUsers)
						{
							id = parseInt(id);
							if (!isNaN(id) && id > 0)
							{

								if (!_this.Attendees[id] && _this.selectedUsers[id]) // Add new user
								{
									_this.selectedUsers[id].key = id;
									_this.DisplayAttendee(_this.selectedUsers[id]);
								}
								else if(_this.Attendees[id] && !_this.selectedUsers[id]) // Del user from our list
								{
									_this.RemoveAttendee(id, false);
								}
							}
						}

						BX.onCustomEvent(_this, 'UserOnChange');
						_this.UpdateCount();
					}}
				}),
				new BX.PopupWindowButtonLink({
					text: EC_MESS.Close,
					className: "popup-window-button-link-cancel",
					events: {click : function(){_this.SelectUserPopup.close();}}
				})
			]
		});
	}

	// Clean
	if (this.bEditMode)
	{
		this.selectedUsers = {};
		var Attendees = [], k;
		for (k in this.Attendees)
		{
			if (this.Attendees[k] && this.Attendees[k].type != 'ext')
				Attendees.push(this.Attendees[k].User);
		}
		O_BXCalUserSelect.setSelected(Attendees);
	}

	this.SelectUserPopup.show();
	BX.PreventDefault(e);
},

AddByEmail : function(e)
{
	var _this = this;
	if (BX.PopupMenu && BX.PopupMenu.currentItem)
		BX.PopupMenu.currentItem.popupWindow.close();

	if(!e) e = window.event;
	if (!this.EmailPopup)
	{
		var pDiv = BX.create("DIV", {props:{className: 'bxc-email-cont'}, html: '<label class="bxc-email-label">' + EC_MESS.UserEmail + ':</label>'});
		this.pEmailValue = pDiv.appendChild(BX.create('INPUT', {props: {className: 'bxc-email-input'}}));

		this.EmailPopup = BX.PopupWindowManager.create("bxc-user-popup-email", this.pLinkCont, {
			offsetTop : 1,
			autoHide : true,
			content : pDiv,
			className: 'bxc-popup-user-select-email',
			closeIcon: { right : "12px", top : "5px"},
			closeByEsc : true,
			buttons: [
			new BX.PopupWindowButton({
				text: EC_MESS.Add,
				className: "popup-window-button-accept",
				events: {click : function(){
					var email = BX.util.trim(_this.pEmailValue.value);
					if (email != "" && !_this.Attendees[email])
					{
						var User = {name: email, key: email, type: 'ext', status: 'Y'};
						_this.DisplayAttendee(User);
						_this.UpdateCount();
					}
					_this.EmailPopup.close();
				}}
			}),
			new BX.PopupWindowButtonLink({
				text: EC_MESS.Close,
				className: "popup-window-button-link-cancel",
				events: {click : function(){_this.EmailPopup.close();}}
			})
		]
		});
	}

	this.EmailPopup.show();
	setTimeout(function(){BX.focus(_this.pEmailValue);}, 50);
	BX.PreventDefault(e);
},

DisableUserOnChange: function(bDisable, bTime)
{
	this.bDisableUserOnChange = bDisable === true;
	if (bTime)
		setTimeout(BX.proxy(this.DisableUserOnChange, this), 300);
},

UserOnChange: function(arUsers)
{
	if (this.bDisableUserOnChange)
		return;

	this.selectedUsers = arUsers;
},

DisplayAttendee: function(User, bUpdate)
{
	this.count++;
	if (User.status == 'Y')
		this.countAgr++;
	else if (User.status == 'N')
		this.countDec++;
	else
		User.status = 'Q';

	if (bUpdate && User.id && this.Attendees[User.id])
	{
		// ?
	}
	else
	{
		var
			_this = this,
			pBusyInfo = false,
			status = User.status.toLowerCase(),
			pRow = this.pAttendeesList.appendChild(BX.create("SPAN", {props:{className: 'bxc-attendee-row bxc-att-row-' + status}})),
			pStatus = pRow.appendChild(BX.create("I", {props: {className: 'bxc-stat-' + status, title: EC_MESS['GuestStatus_' + status] + (User.desc ? ' - ' + User.desc : '')}}));

		if (User.type == 'ext')
			pName = pRow.appendChild(BX.create("span", {props:{className: "bxc-name"}, text: (User.name || User.email)}));
		else
			pName = pRow.appendChild(BX.create("A", {props:{href: this.oEC.GetUserHref(User.id), className: "bxc-name"}, text: User.name}));


		if (this.bEditMode && User.type != 'ext')
			pBusyInfo = pRow.appendChild(BX.create("SPAN", {props:{className: "bxc-busy"}}));
		pRow.appendChild(BX.create("SPAN", {props: {className: "bxc-comma"}, html: ','}));

		if (this.bEditMode)
		{
			pRow.appendChild(BX.create("A", {props: {id: 'bxc-att-key-' + User.key, href: 'javascript:void(0)', title: EC_MESS.Delete, className: 'bxc-del-att'}})).onclick = function(e){_this.RemoveAttendee(this.id.substr('bxc-att-key-'.length)); return BX.PreventDefault(e || window.event)};
		}

		this.Attendees[User.key] = {
			User : User,
			pRow: pRow,
			pBusyCont: pBusyInfo
		};
	}
},

RemoveAttendee: function(key, bAffectToControl)
{
	bAffectToControl = bAffectToControl !== false;

	if (this.Attendees[key])
	{
		this.Attendees[key].pRow.parentNode.removeChild(this.Attendees[key].pRow);

		if (this.Attendees[key].User.status == 'Y')
			this.countAgr--;
		if (this.Attendees[key].User.status == 'N')
			this.countDec--;
		this.count--;

		this.Attendees[key] = null;
		delete this.Attendees[key];

		if (this.bEditMode)
		{
			var Attendees = [];
			for (k in this.Attendees)
			{
				if (this.Attendees[k] && this.Attendees[k].type != 'ext')
					Attendees.push(this.Attendees[k].User);
			}

			this.DisableUserOnChange(true, true);

			if (bAffectToControl)
				O_BXCalUserSelect.setSelected(Attendees);
		}
	}

	this.UpdateCount();
},

ListMode: function(mode)
{
	if (this.mode == mode)
		return;

	if (this.mode) // In start
	{
		BX.removeClass(this.pAttendeesList, 'bxc-users-mode-' + this.mode);
		BX.removeClass(this.pSummary, 'bxc-users-mode-' + this.mode);
	}

	this.mode = mode;
	BX.addClass(this.pAttendeesList, 'bxc-users-mode-' + this.mode);
	BX.addClass(this.pSummary, 'bxc-users-mode-' + this.mode);
},

CheckAccessibility : function(Params, timeout)
{
	if (this.check_access_timeout)
		this.check_access_timeout = clearTimeout(this.check_access_timeout);

	var
		bTimeout = timeout > 0,
		_this = this;

	if (bTimeout)
	{
		this.check_access_timeout = setTimeout(function(){_this.CheckAccessibility(Params, 0);}, timeout);
		return;
	}

	var
		attendees = [],
		values = this.GetValues(),
		eventId = parseInt(this._getEventId()),
		fd = this._getFromDate(),
		td = this._getToDate();

	for(id in values)
		attendees.push(id);

	if (!fd || attendees.length <= 0)
		return false;

	var reqData = {
		event_id : eventId,
		attendees : attendees,
		from: BX.date.getServerTimestamp(fd.getTime())
	};

	if (td)
		reqData.to = BX.date.getServerTimestamp(td.getTime());

	this.oEC.Request({
		postData: this.oEC.GetReqData('check_guests', reqData),
		handler: function(oRes)
		{
			if (!oRes)
				return false;

			if (oRes.data)
			{
				var id, acc, pBusyCont;
				for (id in oRes.data)
				{
					if (!_this.Attendees[id])
						continue;
					acc = oRes.data[id];
					pBusyCont = _this.Attendees[id].pBusyCont;
					if (acc &&  pBusyCont && EC_MESS['Acc_' + acc])
					{
						pBusyCont.innerHTML = '(' + EC_MESS['Acc_' + acc] + ')';
						pBusyCont.title = EC_MESS.UserAccessibility;
						pBusyCont.style.display = '';
					}
					else
					{
						pBusyCont.style.display = 'none';
					}
				}
			}
			return true;
		}
	});
}
}

var ECBanner = function(oEC)
{
	var _this = this;
	this.oEC = oEC;

	var bShow = false;

	this.pWnd = BX(this.oEC.id + 'banner');
	this.pWnd.onmouseover = function(){if(_this._sect_over_timeout){clearInterval(_this._sect_over_timeout);} BX.addClass(_this.pWnd, 'bxec-hover');};
	this.pWnd.onmouseout = function(){_this._sect_over_timeout = setTimeout(function(){BX.removeClass(_this.pWnd, 'bxec-hover');}, 100);};

	BX(this.oEC.id + '_ban_close').onclick = function(){_this.Close(); return false;};

	if (this.oEC.bIntranet)
	{
		this.pOutlSel = BX(oEC.id + '_outl_sel');
		if (this.pOutlSel && this.pOutlSel.parentNode)
		{
			if (BX.browser.IsMac())
			{
				BX.remove(this.pOutlSel.parentNode);
			}
			else
			{
				bShow = true;
				this.pOutlSel.parentNode.onclick = function(){_this.ShowPopup('outlook');};
				this.pOutlSel.onmouseover = function(){BX.addClass(this, "bxec-ban-over");};
				this.pOutlSel.onmouseout = function(){BX.removeClass(this, "bxec-ban-over");};
			}
		}
	}

	if (this.oEC.bCalDAV)
	{
		this.pMobSel = BX(oEC.id + '_mob_sel');
		if (this.pMobSel && this.pMobSel.parentNode)
		{
			bShow = true;
			this.pMobSel.parentNode.onclick = function(){_this.ShowPopup('mobile');};
			this.pMobSel.onmouseover = function(){BX.addClass(this, "bxec-ban-over");};
			this.pMobSel.onmouseout = function(){BX.removeClass(this, "bxec-ban-over");};
		}
	}

	if (this.oEC.arConfig.bExchange)
	{
		var pLink = BX(oEC.id + '_exch_sync');
		if (pLink)
		{
			bShow = true;
			pLink.onclick = function(){_this.oEC.SyncExchange();return false;};
		}
	}

	if (!bShow)
	{
		this.Close(false);
	}

	this.Popup = {};

	if (!window.jsOutlookUtils)
		return BX.loadScript('/bitrix/js/calendar/outlook.js', _this.outlookRun);
}

ECBanner.prototype =
{
	ShowPopup: function(type)
	{
		var _this = this;
		if (!this.Popup[type])
			this.CreatePopup(type);

		if (this.Popup[type].bShowed)
			return this.ClosePopup(type);

		this.ClosePopup(type);
		var pWnd = this.Popup[type].pWin.Get();
		this.Popup[type].bShowed = true;

		var
			rowsCount = 0,
			i, l = this.oEC.arSections.length, cal, name, pItem;

		BX.cleanNode(pWnd);

		if (type == 'mobile')
		{
			rowsCount++;
			pItem = pWnd.appendChild(BX.create("DIV", {
				props: {id: 'ecpp_all', title: EC_MESS.AllCalendars},
				style: {backgroundColor: '#F2F8D6'},
				text: EC_MESS.AllCalendars,
				events: {
					mouseover: function(){BX.addClass(this, 'bxec-over');},
					mouseout: function(){BX.removeClass(this, 'bxec-over');}
				}
			}));

			pItem.onclick = function()
			{
				_this.RunMobile(this.id.substr('ecpp_'.length));
				_this.ClosePopup();
			}
		}

		for (i = 0; i < l; i++)
		{
			cal = this.oEC.arSections[i];
			if (!this.oEC.IsCurrentViewSect(cal))
				continue;

			if(type == 'outlook' && !cal.OUTLOOK_JS)
				continue;

			rowsCount++;
			pItem = pWnd.appendChild(BX.create("DIV", {
				props: {id: 'ecpp_' + cal.ID, title: cal.NAME, className: 'bxec-text-overflow' + (cal.bDark ? ' bxec-dark' : '')},
				style: {backgroundColor: cal.COLOR},
				text: cal.NAME,
				events: {
					mouseover: function(){BX.addClass(this, 'bxec-over');},
					mouseout: function(){BX.removeClass(this, 'bxec-over');}
				}
			}));

			if (type == 'outlook')
			{
				pItem.onclick = function()
				{
					_this.RunOutlook(this.id.substr('ecpp_'.length));
					_this.ClosePopup();
				}
			}
			else if (type == 'mobile')
			{
				pItem.onclick = function()
				{
					_this.RunMobile(this.id.substr('ecpp_'.length));
					_this.ClosePopup();
				}
			}
		}

		// Add events
		if (!this.bCloseEventsAttached)
		{
			BX.bind(document, "keyup", BX.proxy(this.OnKeyUp, this));
			setTimeout(function()
			{
				_this.bPreventClickClosing = false;
				BX.bind(document, "click", BX.proxy(_this.ClosePopup, _this));
			}, 100);
			this.bCloseEventsAttached = true;
		}

		var pos = BX.pos(this.Popup[type].pSel);
		this.Popup[type].pWin.Show(true); // Show window
		pWnd.style.width = '200px';
		pWnd.style.height = '';

		// Set start position
		pWnd.style.left = (pos.left + 0) + 'px';
		pWnd.style.top = (pos.bottom + 0) + 'px';
	},

	OnKeyUp: function(e)
	{
		if(!e) e = window.event;
		if(e.keyCode == 27)
			this.ClosePopup();
	},

	ClosePopup: function()
	{
		// if (this.bPreventClickClosing)
			// return;

		for (var type in this.Popup)
		{
			this.Popup[type].pWin.Get().style.display = "none";
			this.Popup[type].bShowed = false;
			this.Popup[type].pWin.Close();
		}

		if (this.bCloseEventsAttached)
		{
			this.bCloseEventsAttached = false;
			BX.unbind(document, "keyup", BX.proxy(this.OnKeyUp, this));
			BX.unbind(document, "click", BX.proxy(this.ClosePopup, this));
		}
	},

	CreatePopup: function(type)
	{
		var _this = this;
		this.Popup[type] = {pWin: new BX.CWindow(false, 'float')};

		if (type == 'outlook')
			this.Popup[type].pSel = this.pOutlSel;
		else if (type == 'mobile')
			this.Popup[type].pSel = this.pMobSel;

		BX.addClass(this.Popup[type].pWin.Get(), "bxec-ban-popup");
	},

	Close: function(bSaveSettings)
	{
		this.pWnd .parentNode.removeChild(this.pWnd);
		if (bSaveSettings !== false)
		{
			if (BX.admin && BX.admin.panel)
				BX.admin.panel.Notify(EC_MESS.CloseBannerNotify);
			this.oEC.userSettings.showBanner = false;
			BX.userOptions.save('calendar', 'user_settings', 'showBanner', 0);
		}
	},

	RunOutlook: function(id)
	{
		var oSect = this.oEC.oSections[id];
		if(oSect && oSect.OUTLOOK_JS && oSect.OUTLOOK_JS.length > 0)
			try{eval(oSect.OUTLOOK_JS);}catch(e){};
	},

	RunMobile: function(id)
	{
		this.oEC.ShowMobileHelpDialog(id);
	}
};

var ECMonthSelector = function(oEC)
{
	this.oEC = oEC;
	this.Build();
	this.content = {month: '', week: '', day: ''};
}

ECMonthSelector.prototype = {
	Build : function()
	{
		var _this = this;
		this.pPrev = BX(this.oEC.id + "selector-prev");
		this.pNext = BX(this.oEC.id + "selector-next");
		this.pCont = BX(this.oEC.id + "selector-cont");
		this.pContInner = BX(this.oEC.id + "selector-cont-inner");

		this.pPrev.onclick = function(){_this.ChangeValue(false);};
		this.pNext.onclick = function(){_this.ChangeValue(true);};
	},

	ChangeMode : function(mode)
	{
		this.mode = mode || this.oEC.activeTabId;
		if (this.mode == 'month')
		{
			this.pCont.className = 'bxec-sel-but';
			this.pCont.onclick = BX.proxy(this.ShowMonthPopup, this);
		}
		else
		{
			this.pCont.className = 'bxec-sel-text';
			this.pCont.onclick = BX.False;
		}
	},

	OnChange : function(year, month, week, date)
	{
		month = parseInt(month, 10);
		year = parseInt(year);
		var res, dayOffset;

		this.pNext.style.marginLeft = (this.mode == 'month' && BX.browser.IsIE() && !BX.browser.IsIE9()) ? '10px' : ''; // Hack for IE 8

		if (this.mode == 'month')
		{
			if (month < 0 || month > 11)
				return alert('Error! Incorrect month');

			this.content.month = this.oEC.arConfig.month[month] + ',&nbsp;' + year + '<span class="bxec-sel-but-arr">';
		}
		else if (this.mode == 'week')
		{
			var startWeekDate = new Date();
			startWeekDate.setFullYear(year, month, 1);

			//if (week < 0 && this.oEC.weekStart != this.oEC.GetWeekDayByInd(startWeekDate.getDay()))
			//	week = 0;

			dayOffset = this.oEC.GetWeekDayOffset(this.oEC.GetWeekDayByInd(startWeekDate.getDay()));

			if(dayOffset > 0)
				startWeekDate.setDate(startWeekDate.getDate() - dayOffset); // Now it-s first day in of this week

			if (week != 0)
				startWeekDate.setDate(startWeekDate.getDate() + (7 * week));

			var oSunDate = new Date(startWeekDate.getTime());
			oSunDate.setDate(oSunDate.getDate() + 6);
			var
				content,
				month_r = this.oEC.arConfig.month_r,
				d_f = startWeekDate.getDate(),
				m_f = startWeekDate.getMonth(),
				y_f = startWeekDate.getFullYear(),
				d_t = oSunDate.getDate(),
				m_t = oSunDate.getMonth(),
				y_t = oSunDate.getFullYear();

			if (m_f == m_t)
				content = d_f + '&nbsp;-&nbsp;' + d_t + '&nbsp;' + month_r[m_f] + '&nbsp;' + y_f;
			else if(y_f == y_t)
				content = d_f + '&nbsp;' + month_r[m_f] + '&nbsp;-&nbsp;' + d_t + '&nbsp;' + month_r[m_t] + '&nbsp;' + y_f;
			else
				content = d_f + '&nbsp;' + month_r[m_f] + '&nbsp;' + y_f + '&nbsp;-&nbsp;' + d_t + '&nbsp;' + month_r[m_t] + '&nbsp;' + y_t;

			this.content.week = '<nobr>' + content + '</nobr>';
			res = {
				dateFrom: d_f,
				monthFrom: m_f,
				yearFrom: y_f,
				weekStartDate: startWeekDate,
				monthTo: m_t,
				yearTo: y_t,
				dateTo: d_t,
				weekEndDate: oSunDate
			};
		}
		else if (this.mode == 'day')
		{
			var oDate = new Date();
			oDate.setFullYear(year, month, date);
			day = this.oEC.ConvertDayIndex(oDate.getDay());
			date = oDate.getDate(),
			month = oDate.getMonth(),
			year = oDate.getFullYear();

			this.content.day = '<nobr>' + this.oEC.arConfig.days[day][0] + ',&nbsp;' + date + '&nbsp;' + this.oEC.arConfig.month_r[month] + '&nbsp;' + year + '</nobr>';
			res = {date: date, month: month, year: year, oDate: oDate};
		}

		this.Show(this.mode);
		return res;
	},

	Show: function(mode)
	{
		this.pContInner.innerHTML = this.content[mode];
	},

	ChangeValue: function(bNext)
	{
		var delta = bNext ? 1 : -1;
		if (this.mode == 'month')
		{
			//IncreaseCurMonth
			var m = bxInt(this.oEC.activeDate.month) + delta;
			var y = this.oEC.activeDate.year;
			if (m < 0)
			{
				m += 12;
				y--;
			}
			else if (m > 11)
			{
				m -= 12;
				y++;
			}
			this.oEC.SetMonth(m, y);
		}
		else if (this.mode == 'week')
		{
			this.oEC.SetWeek(this.oEC.activeDate.week + delta, this.oEC.activeDate.month, this.oEC.activeDate.year);
		}
		else if (this.mode == 'day')
		{
			this.oEC.SetDay(this.oEC.activeDate.date + delta, this.oEC.activeDate.month, this.oEC.activeDate.year);
		}
	},

	ShowMonthPopup: function()
	{
		if (!this.oMonthWin)
		{
			var _this = this;
			this.oMonthWin = new BX.PopupWindow(this.oEC.id + "bxc-month-sel", this.pCont, {
				overlay: {opacity: 1},
				autoHide : true,
				offsetTop : 1,
				offsetLeft : 0,
				lightShadow : true,
				content : BX('bxec_month_win_' + this.oEC.id)
			});
			this.oMonthWin.CAL = {
				DOM : {
					Year: BX(this.oEC.id + 'md-year'),
					MonthList: BX(this.oEC.id + 'md-month-list')
				},
				curYear: parseInt(this.oEC.activeDate.year)
			};

			this.oMonthWin.CAL.DOM.Year.innerHTML = this.oMonthWin.CAL.curYear;
			BX(this.oEC.id + 'md-selector-prev').onclick = function(){_this.oMonthWin.CAL.DOM.Year.innerHTML = --_this.oMonthWin.CAL.curYear;};
			BX(this.oEC.id + 'md-selector-next').onclick = function(){_this.oMonthWin.CAL.DOM.Year.innerHTML = ++_this.oMonthWin.CAL.curYear;};

			var
				i, m, div,
				arM = [0, 4, 8, 1, 5, 9, 2, 6, 10, 3, 7, 11];

			for (i = 0; i < 12; i++)
			{
				m = arM[i];
				div = this.oMonthWin.CAL.DOM.MonthList.appendChild(BX.create("DIV", {
					props: {id: 'bxec_ms_m_' + arM[i], className: 'bxec-month-div' + (arM[i] == this.oEC.activeDate.month ? ' bxec-month-act' : '') + ' bxec-' + this.GetSeason(arM[i])},
					html: '<span>' + this.oEC.arConfig.month[arM[i]] + '</span>',
					events: {click: function()
					{
						//_this.MonthWinSetMonth(this);
						BX.removeClass(_this.oMonthWin.CAL.DOM.curMonth, 'bxec-month-act');
						BX.addClass(this, 'bxec-month-act');
						_this.oMonthWin.CAL.DOM.curMonth = this;
						_this.oEC.SetMonth(parseInt(this.id.substr('bxec_ms_m_'.length)), _this.oMonthWin.CAL.curYear);
						_this.oMonthWin.close();
					}}
				}));
				if (arM[i] == this.oEC.activeDate.month)
					this.oMonthWin.CAL.DOM.curMonth = div;
			}
		}

		this.oMonthWin.show();
	},

	GetSeason : function(m)
	{
		switch(m)
		{
			case 11: case 0: case 1:
				return 'winter';
			case 2: case 3: case 4:
				return 'spring';
			case 5: case 6: case 7:
				return 'summer';
			case 8: case 9: case 10:
				return 'autumn';
		}
	}
};

var ECCalendarAccess = function(Params)
{
	BX.Access.Init();
	if (!window.EC_MESS)
		EC_MESS = {};

	this.bind = Params.bind;
	this.GetAccessName = Params.GetAccessName;
	this.pTbl = Params.pCont.appendChild(BX.create("TABLE", {props: {className: "bxc-access-tbl"}}));
	this.pSel = BX('bxec-' + this.bind);
	var _this = this;
	this.delTitle = Params.delTitle || EC_MESS.Delete;
	this.noAccessRights = Params.noAccessRights || EC_MESS.NoAccessRights;

	this.inputName = Params.inputName || false;

	Params.pLink.onclick = function(){
		BX.Access.ShowForm({
			callback: BX.proxy(_this.InsertRights, _this),
			bind: _this.bind
		});
	};
}

ECCalendarAccess.prototype = {
	InsertRights: function(obSelected)
	{
		var provider, code;
		for(provider in obSelected)
			for(code in obSelected[provider])
				this.InsertAccessRow(BX.Access.GetProviderName(provider) + ' ' + obSelected[provider][code].name, code);
	},

	InsertAccessRow: function(title, code, value)
	{
		var _this = this, row, pLeft, pRight, pTaskSelect;
		if (this.pTbl.rows[0] && this.pTbl.rows[0].cells[0] && this.pTbl.rows[0].cells[0].className.indexOf('bxc-access-no-vals') != -1)
			this.DeleteRow(0);

		row = this.pTbl.insertRow(-1);
		pLeft = BX.adjust(row.insertCell(-1), {props : {className: 'bxc-access-c-l'}, html: title + ':'});
		pRight = BX.adjust(row.insertCell(-1), {props : {className: 'bxc-access-c-r'}});
		pTaskSelect = pRight.appendChild(this.pSel.cloneNode(true));
		//pTaskSelect.name = 'BXEC_ACCESS_' + code;
		pTaskSelect.id = 'BXEC_ACCESS_' + code;

		if (value)
			pTaskSelect.value = value;
		pDel = pRight.appendChild(BX.create('A', {props:{className: 'access-delete', href: 'javascript:void(0)', title: this.delTitle}, events: {click: function(){_this.DeleteRow(this.parentNode.parentNode.rowIndex);}}}));

		if (this.inputName)
		{
			pTaskSelect.name = this.inputName + '[' + code + ']';
			//pRight.appendChild(BX.create('INPUT', {props:{type: 'hidden', value: this.inputName + '[' + code + ']'}}));
		}
	},

	DeleteRow: function(rowIndex)
	{
		if (this.pTbl.rows[rowIndex])
			this.pTbl.deleteRow(rowIndex);
	},

	GetValues: function()
	{
		var
			id, taskId,
			res = {},
			arSelect = this.pTbl.getElementsByTagName("SELECT"),
			i, l = arSelect.length;

		for(i = 0; i < l; i++)
		{
			id = arSelect[i].id.substr('BXEC_ACCESS_'.length);
			taskId = arSelect[i].value;
			res[id] = taskId;
		}

		return res;
	},

	SetSelected: function(oAccess)
	{
		if (!oAccess)
			oAccess = {};

		while (this.pTbl.rows[0])
			this.pTbl.deleteRow(0);

		var
			code,
			oSelected = {};

		for (code in oAccess)
		{
			this.InsertAccessRow(this.GetTitleByCode(code), code, oAccess[code]);
			oSelected[code] = true;
		}

		// Insert 'no value'  if no permissions exists
		if (this.pTbl.rows.length <= 0)
			BX.adjust(this.pTbl.insertRow(-1).insertCell(-1), {props : {className: 'bxc-access-no-vals', colSpan: 2}, html: '<span>' + this.noAccessRights + '</span>'});

		BX.Access.SetSelected(oSelected, this.bind);
	},

	GetTitleByCode: function(code)
	{
		return this.GetAccessName(code);
	}
};

function ECColorPicker(Params)
{
	//this.bCreated = false;
	this.bOpened = false;
	this.zIndex = 5000;
	this.id = '';
	this.Popups = {};
	this.Conts = {};
}

ECColorPicker.prototype = {
	Create: function ()
	{
		var _this = this;
		var pColCont = document.body.appendChild(BX.create("DIV", {props: {className: "ec-colpick-cont"}, style: {zIndex: this.zIndex}}));

		var
			arColors = ['#FF0000', '#FFFF00', '#00FF00', '#00FFFF', '#0000FF', '#FF00FF', '#FFFFFF', '#EBEBEB', '#E1E1E1', '#D7D7D7', '#CCCCCC', '#C2C2C2', '#B7B7B7', '#ACACAC', '#A0A0A0', '#959595',
			'#EE1D24', '#FFF100', '#00A650', '#00AEEF', '#2F3192', '#ED008C', '#898989', '#7D7D7D', '#707070', '#626262', '#555', '#464646', '#363636', '#262626', '#111', '#000000',
			'#F7977A', '#FBAD82', '#FDC68C', '#FFF799', '#C6DF9C', '#A4D49D', '#81CA9D', '#7BCDC9', '#6CCFF7', '#7CA6D8', '#8293CA', '#8881BE', '#A286BD', '#BC8CBF', '#F49BC1', '#F5999D',
			'#F16C4D', '#F68E54', '#FBAF5A', '#FFF467', '#ACD372', '#7DC473', '#39B778', '#16BCB4', '#00BFF3', '#438CCB', '#5573B7', '#5E5CA7', '#855FA8', '#A763A9', '#EF6EA8', '#F16D7E',
			'#EE1D24', '#F16522', '#F7941D', '#FFF100', '#8FC63D', '#37B44A', '#00A650', '#00A99E', '#00AEEF', '#0072BC', '#0054A5', '#2F3192', '#652C91', '#91278F', '#ED008C', '#EE105A',
			'#9D0A0F', '#A1410D', '#A36209', '#ABA000', '#588528', '#197B30', '#007236', '#00736A', '#0076A4', '#004A80', '#003370', '#1D1363', '#450E61', '#62055F', '#9E005C', '#9D0039',
			'#790000', '#7B3000', '#7C4900', '#827A00', '#3E6617', '#045F20', '#005824', '#005951', '#005B7E', '#003562', '#002056', '#0C004B', '#30004A', '#4B0048', '#7A0045', '#7A0026'],
			row, cell, colorCell,
			tbl = BX.create("TABLE", {props: {className: 'ec-colpic-tbl'}}),
			i, l = arColors.length;

		row = tbl.insertRow(-1);
		cell = row.insertCell(-1);
		cell.colSpan = 8;
		var defBut = cell.appendChild(BX.create("SPAN", {props: {className: 'ec-colpic-def-but'}, text: EC_MESS.DefaultColor}));
		defBut.onmouseover = function()
		{
			this.className = 'ec-colpic-def-but ec-colpic-def-but-over';
			colorCell.style.backgroundColor = '#FF0000';
		};
		defBut.onmouseout = function(){this.className = 'ec-colpic-def-but';};
		defBut.onmousedown = function(e){_this.Select('#FF0000');}

		colorCell = row.insertCell(-1);
		colorCell.colSpan = 8;
		colorCell.className = 'ec-color-inp-cell';
		colorCell.style.backgroundColor = arColors[38];

		for(i = 0; i < l; i++)
		{
			if (Math.round(i / 16) == i / 16) // new row
				row = tbl.insertRow(-1);

			cell = row.insertCell(-1);
			cell.innerHTML = '&nbsp;';
			cell.className = 'ec-col-cell';
			cell.style.backgroundColor = arColors[i];
			cell.id = 'lhe_color_id__' + i;

			cell.onmouseover = function (e)
			{
				this.className = 'ec-col-cell ec-col-cell-over';
				colorCell.style.backgroundColor = arColors[this.id.substring('lhe_color_id__'.length)];
			};
			cell.onmouseout = function (e){this.className = 'ec-col-cell';};
			cell.onmousedown = function (e)
			{
				var k = this.id.substring('lhe_color_id__'.length);
				_this.Select(arColors[k]);
			};
		}

		pColCont.appendChild(tbl);

		this.Conts[this.id] = pColCont;
		//this.bCreated = true;
	},

	Open: function(Params)
	{
		this.id = Params.id + Math.round(Math.random() * 1000000);
		this.key = Params.key;
		this.OnSelect = Params.onSelect;

		if (!this.Conts[this.id])
			this.Create();

		if (!this.Popups[this.id])
		{
			this.Popups[this.id] = BX.PopupWindowManager.create("bxc-color-popup" + this.id, Params.pWnd, {
				autoHide : true,
				offsetTop : 1,
				offsetLeft : 0,
				lightShadow : true,
				content : this.Conts[this.id]
			});
		}

		this.Popups[this.id].show();
	},

	Close: function ()
	{
		this.Popups[this.id].close();
		this.Popups[this.id].destroy();
	},

	OnKeyPress: function(e)
	{
		if(!e) e = window.event
		if(e.keyCode == 27)
			this.Close();
	},

	Select: function (color)
	{
		if (this.OnSelect && typeof this.OnSelect == 'function')
			this.OnSelect(color);
		this.Close();
	}
};


/* DESTINATION */
// Calbacks for destination
window.BxEditEventGridSetLinkName = function(name)
{
	var destLink = BX('event-grid-dest-add-link');
	if (destLink)
		destLink.innerHTML = BX.SocNetLogDestination.getSelectedCount(name) > 0 ? BX.message("BX_FPD_LINK_2") : BX.message("BX_FPD_LINK_1");
}

window.BxEditEventGridSelectCallback = function(item, type, search)
{
	var type1 = type;
	prefix = 'S';
	if (type == 'sonetgroups')
		prefix = 'SG';
	else if (type == 'groups')
	{
		prefix = 'UA';
		type1 = 'all-users';
	}
	else if (type == 'users')
		prefix = 'U';
	else if (type == 'department')
		prefix = 'DR';

	BX('event-grid-dest-item').appendChild(
		BX.create("span", { attrs : {'data-id' : item.id }, props : {className : "event-grid-dest event-grid-dest-"+type1 }, children: [
			BX.create("input", { attrs : {type : 'hidden', name : 'EVENT_DESTINATION['+prefix+'][]', value : item.id }}),
			BX.create("span", { props : {className : "event-grid-dest-text" }, html : item.name}),
			BX.create("span", { props : {className : "feed-event-del-but"}, attrs: {'data-item-id': item.id, 'data-item-type': type}})
		]})
	);

	BX.onCustomEvent('OnDestinationAddNewItem', [item]);
	BX('event-grid-dest-input').value = '';
	BxEditEventGridSetLinkName(editEventDestinationFormName);
}

// remove block
window.BxEditEventGridUnSelectCallback = function(item, type, search)
{
	var elements = BX.findChildren(BX('event-grid-dest-item'), {attribute: {'data-id': ''+item.id+''}}, true);
	if (elements != null)
	{
		for (var j = 0; j < elements.length; j++)
			BX.remove(elements[j]);
	}

	BX.onCustomEvent('OnDestinationUnselect');
	BX('event-grid-dest-input').value = '';
	BxEditEventGridSetLinkName(editEventDestinationFormName);
}
window.BxEditEventGridOpenDialogCallback = function()
{
	BX.style(BX('event-grid-dest-input-box'), 'display', 'inline-block');
	BX.style(BX('event-grid-dest-add-link'), 'display', 'none');
	BX.focus(BX('event-grid-dest-input'));
}

window.BxEditEventGridCloseDialogCallback = function()
{
	if (!BX.SocNetLogDestination.isOpenSearch() && BX('event-grid-dest-input').value.length <= 0)
	{
		BX.style(BX('event-grid-dest-input-box'), 'display', 'none');
		BX.style(BX('event-grid-dest-add-link'), 'display', 'inline-block');
		BxEditEventGridDisableBackspace();
	}
}

window.BxEditEventGridCloseSearchCallback = function()
{
	if (!BX.SocNetLogDestination.isOpenSearch() && BX('event-grid-dest-input').value.length > 0)
	{
		BX.style(BX('event-grid-dest-input-box'), 'display', 'none');
		BX.style(BX('event-grid-dest-add-link'), 'display', 'inline-block');
		BX('event-grid-dest-input').value = '';
		BxEditEventGridDisableBackspace();
	}

}
window.BxEditEventGridDisableBackspace = function(event)
{
	if (BX.SocNetLogDestination.backspaceDisable || BX.SocNetLogDestination.backspaceDisable != null)
		BX.unbind(window, 'keydown', BX.SocNetLogDestination.backspaceDisable);

	BX.bind(window, 'keydown', BX.SocNetLogDestination.backspaceDisable = function(event){
		if (event.keyCode == 8)
		{
			BX.PreventDefault(event);
			return false;
		}
	});
	setTimeout(function(){
		BX.unbind(window, 'keydown', BX.SocNetLogDestination.backspaceDisable);
		BX.SocNetLogDestination.backspaceDisable = null;
	}, 5000);
}

window.BxEditEventGridSearchBefore = function(event)
{
	if (event.keyCode == 8 && BX('event-grid-dest-input').value.length <= 0)
	{
		BX.SocNetLogDestination.sendEvent = false;
		BX.SocNetLogDestination.deleteLastItem(editEventDestinationFormName);
	}

	return true;
}
window.BxEditEventGridSearch = function(event)
{
	if (event.keyCode == 16 || event.keyCode == 17 || event.keyCode == 18 || event.keyCode == 20 || event.keyCode == 244 || event.keyCode == 224 || event.keyCode == 91)
		return false;

	if (event.keyCode == 13)
	{
		BX.SocNetLogDestination.selectFirstSearchItem(editEventDestinationFormName);
		return true;
	}
	if (event.keyCode == 27)
	{
		BX('event-grid-dest-input').value = '';
		BX.style(BX('event-grid-dest-add-link'), 'display', 'inline');
	}
	else
	{
		BX.SocNetLogDestination.search(BX('event-grid-dest-input').value, true, editEventDestinationFormName);
	}

	if (!BX.SocNetLogDestination.isOpenDialog() && BX('event-grid-dest-input').value.length <= 0)
	{
		BX.SocNetLogDestination.openDialog(editEventDestinationFormName);
	}
	else
	{
		if (BX.SocNetLogDestination.sendEvent && BX.SocNetLogDestination.isOpenDialog())
			BX.SocNetLogDestination.closeDialog();
	}
	if (event.keyCode == 8)
	{
		BX.SocNetLogDestination.sendEvent = true;
	}
	return true;
}
/* END DESTINATION */

;(function(window){
	window.EditEventPopupController = function(config)
	{
		this.config = config;
		this.id = this.config.id;
		this.oEC = this.config.oEC;
		this.oEvent = this.config.oEvent;
		this.Form = this.config.form;

		this.Init();
	};

	window.EditEventPopupController.prototype = {
		Init: function()
		{
			this.InitDateTimeControls();

			var
				_this = this,
				editorId = this.oEC.id + '_event_editor',
				oEditor = window["BXHtmlEditor"].Get(editorId);

			if (oEditor && oEditor.IsShown())
			{
				this.CustomizeHtmlEditor(oEditor);
			}
			else
			{
				BX.addCustomEvent(window["BXHtmlEditor"], 'OnEditorCreated', function(editor)
				{
					if (editor.id == editorId)
					{
						_this.CustomizeHtmlEditor(editor);
					}
				});
			}

			if (this.oEC.allowMeetings)
				this.InitDestinationControls();
			this.FillFormFields();
		},

		GetFromToValues: function()
		{
			// Datetime limits
			var fd = BX.parseDate(this.pFromDate.value);
			if (!fd)
				return alert(EC_MESS.EventDiapStartError);

			if (this.pFullDay.checked)
				this._FromTimeValue = this.pFromTime.value = this.pToTime.value = '';

			var fromTime = this.oEC.ParseTime(this.pFromTime.value);
			fd.setHours(fromTime.h);
			fd.setMinutes(fromTime.m);
			var
				to,
				from = fd.getTime();

			var td = BX.parseDate(this.pToDate.value);
			if (td)
			{
				var toTime = this.oEC.ParseTime(this.pToTime.value);
				td.setHours(toTime.h);
				td.setMinutes(toTime.m);
				to = td.getTime();

				if (from == to && toTime.h == 0 && toTime.m == 0)
				{
					fd.setHours(0);
					fd.setMinutes(0);
					td.setHours(0);
					td.setMinutes(0);

					from = fd.getTime();
					to = td.getTime();
				}
			}
			else
			{
				if (this.oEvent.ID)
					return alert(EC_MESS.EventDiapEndError);
				else
					to = from;
			}

			if (from > to) // Date To earlier Date From - send error
				return alert(EC_MESS.EventDatesError);

			return {from: from, to: to};
		},

		SaveForm: function(Params)
		{
			var
				_this = this,
				month = parseInt(this.oEC.activeDate.month, 10),
				year = this.oEC.activeDate.year,
				url = this.oEC.actionUrl,
				reqId = Math.round(Math.random() * 1000000);

			url += (url.indexOf('?') == -1) ? '?' : '&';
			url += 'action=edit_event&bx_event_calendar_request=Y&sessid=' + BX.bitrix_sessid() + '&reqId=' + reqId;
			this.Form.action = url;

			BX('event-id' + this.id).value = this.oEvent.ID || 0;
			BX('event-month' + this.id).value = month + 1;
			BX('event-year' + this.id).value = year;

			// Datetime limits
			var fromTo = this.GetFromToValues();
			if (typeof fromTo !== 'object')
				return;

			BX('event-from-ts' + this.id).value = BX.date.getServerTimestamp(fromTo.from);
			BX('event-to-ts' + this.id).value = BX.date.getServerTimestamp(fromTo.to);

			// RRULE
			if (this.RepeatSelect.value != 'NONE')
			{
				var FREQ = this.RepeatSelect.value;

				BX('event-rrule-until' + this.id).value = '';
				if (this.RepeatDiapTo.value != EC_MESS.NoLimits)
				{
					var until = BX.parseDate(this.RepeatDiapTo.value);
					if (until && until.getTime)
						BX('event-rrule-until' + this.id).value = BX.date.getServerTimestamp(until.getTime());
				}

				if (FREQ == 'WEEKLY')
				{
					var ar = [], i;
					for (i = 0; i < 7; i++)
						if (this.RepeatWeekDaysCh[i].checked)
							ar.push(this.RepeatWeekDaysCh[i].value);

					if (ar.length == 0)
						this.RepeatSelect.value = 'NONE';
					else
						BX('event-rrule-byday' + this.id).value = ar.join(',');
				}
			}

			// Location
			BX('event-location-old' + this.id).value = this.Loc.OLD || false;
			BX('event-location-new' + this.id).value = this.Loc.NEW;


			// Check Meeting and Video Meeting rooms accessibility
			if (this.Loc.NEW.substr(0, 5) == 'ECMR_' && !Params.bLocationChecked)
			{
				this.oEC.CheckMeetingRoom(
					{
						id : this.oEvent.ID || 0,
						from : BX.date.getServerTimestamp(fromTo.from),
						to : BX.date.getServerTimestamp(fromTo.to),
						location_new : this.Loc.NEW,
						location_old : this.Loc.OLD || false
					},
					function(check)
					{
						if (!check)
							return alert(EC_MESS.MRReserveErr);
						if (check == 'reserved')
							return alert(EC_MESS.MRNotReservedErr);

						Params.bLocationChecked = true;
						_this.SaveForm(Params);
					}
				);
				return false;
			}

			BX.ajax.submit(this.Form, function(html)
			{
				var oRes = top.BXCRES[reqId];
				if(top.BXCRES[reqId])
				{
					_this.oEC.Event.UnDisplay(oRes.id, false);
					_this.oEC.HandleEvents(oRes.events, oRes.attendees);
					_this.oEC.arLoadedMonth[month + '.' + year] = true;

					if (oRes.deletedEventId > 0)
						_this.oEC.Event.UnDisplay(oRes.deletedEventId, false);

					_this.oEC.Event.Display();
				}
			});

			// Color
			var
				sectId = this.pSectSelect.value,
				oSect = _this.oEC.oSections && _this.oEC.oSections[sectId] ? _this.oEC.oSections[sectId] : {},
				text_color = this.TextColor,
				color = this.Color;

			if (!oSect.COLOR || oSect.COLOR && oSect.COLOR.toLowerCase() != color.toLowerCase())
				BX(this.id + '_bxec_color').value = color;
			if (!oSect.TEXT_COLOR || oSect.TEXT_COLOR && oSect.TEXT_COLOR.toLowerCase() != text_color.toLowerCase())
				BX(this.id + '_bxec_text_color').value = text_color;

			if (Params.callback)
				Params.callback();
		},

		InitDestinationControls: function()
		{
			var _this = this;
			BX.addCustomEvent('OnDestinationAddNewItem', BX.proxy(this.DestinationOnChange, this));
			BX.addCustomEvent('OnDestinationUnselect', BX.proxy(this.DestinationOnChange, this))

			this.pAttCont = BX('event-grid-att' + this.id);
			this.pMeetingParams = BX('event-grid-meeting-params' + this.id);
			this.pDestValuesCont = BX('event-grid-dest-item');

			BX.bind(this.pDestValuesCont, 'click', function(e)
			{
				var targ = e.target || e.srcElement;
				if (targ.className == 'feed-event-del-but') // Delete button
				{
					BX.SocNetLogDestination.deleteItem(targ.getAttribute('data-item-id'), targ.getAttribute('data-item-type'), editEventDestinationFormName);
					BX.PreventDefault(e);
				}
			});

			BX.bind(this.pDestValuesCont, 'mouseover', function(e)
			{
				var targ = e.target || e.srcElement;
				if (targ.className == 'feed-event-del-but') // Delete button
					BX.addClass(targ.parentNode, 'event-grid-dest-hover');
			});
			BX.bind(this.pDestValuesCont, 'mouseout', function(e)
			{
				var targ = e.target || e.srcElement;
				if (targ.className == 'feed-event-del-but') // Delete button
					BX.removeClass(targ.parentNode, 'event-grid-dest-hover');
			});

			this.pAttContY = BX('event-edit-att-y');
			this.pAttContN = BX('event-edit-att-n');
			this.pAttContQ = BX('event-edit-att-q');

			this.attendeeIndex = {};

			if (this.oEvent.IS_MEETING)
			{
				BX.addClass(this.pAttCont, 'event-grid-dest-cont-full');
				this.pMeetingParams.style.display = 'block';
				this.DisplayAttendees(this.oEvent['~ATTENDEES']);
			}

			this.AddMeetTextLink = BX(this.id + '_add_meet_text');
			this.HideMeetTextLink = BX(this.id + '_hide_meet_text');
			this.MeetTextCont = BX(this.id + '_meet_text_cont');
			this.MeetText = BX(this.id + '_meeting_text');

			this.OpenMeeting = BX(this.id + '_ed_open_meeting');
			this.NotifyStatus = BX(this.id + '_ed_notify_status');
			this.Reinvite = BX(this.id + '_ed_reivite');
			this.ReinviteCont = BX(this.id + '_ed_reivite_cont');

			this.AddMeetTextLink.onclick = function()
			{
				this.parentNode.style.display = 'none';
				_this.MeetTextCont.style.display = 'block';
				BX.focus(_this.MeetText);
			};

			this.HideMeetTextLink.onclick = function()
			{
				_this.AddMeetTextLink.parentNode.style.display = 'block';
				_this.MeetTextCont.style.display = 'none';
			};

			if (this.oEvent.IS_MEETING)
			{
				this.OpenMeeting.checked = !!(this.oEvent.MEETING && this.oEvent.MEETING.OPEN);
				this.NotifyStatus.checked = !!(this.oEvent.MEETING && this.oEvent.MEETING.NOTIFY);

				if (this.oEvent.MEETING)
				{
					this.MeetText.value = this.oEvent.MEETING.TEXT || '';
					if (this.oEvent.MEETING.TEXT != '')
						this.AddMeetTextLink.onclick();
				}
				else
				{
					this.MeetText.value = '';
				}
				this.Reinvite.checked = false;
			}
			else
			{
				this.AddMeetTextLink.parentNode.style.display = 'block';
				this.MeetTextCont.style.display = 'none';

				if(this.oEvent.MeetText)
					this.oEvent.MeetText.value = '';
				if (this.oEvent.HideMeetTextLink)
					this.oEvent.HideMeetTextLink.onclick();
			}

			BX(this.id + '_planner_link').onclick = function()
			{
				var attendees = [];

				if (attendees.length == 0)
					attendees.push({id: _this.oEC.userId, name: _this.oEC.userName});

				var
					loc = _this.Loc.NEW,
					arLoc = _this.oEC.ParseLocation(loc, true),
					locMrind = arLoc.mrind == undefined ? false : arLoc.mrind;

				_this.oEC.RunPlanner({
					curEventId: _this.oEvent.ID || false,
					attendees: attendees,
					fromDate: _this.pFromDate.value,
					toDate: _this.pToDate.value,
					fromTime: _this.pFromTime.value,
					toTime: _this.pToTime.value,
					location: _this.Loc.NEW,
					locationMrind: locMrind,
					oldLocationMRId: _this.Loc.OLD_mrevid
				});
			};


		},

		DestinationOnChange: function()
		{
			var
				_this = this,
				from, to,
				arInputs = this.pDestValuesCont.getElementsByTagName('INPUT'),
				i, arCodes = [];

			for (i = 0; i < arInputs.length; i++)
				arCodes.push(arInputs[i].value);

			var fromTo = this.GetFromToValues();
			if (typeof fromTo === 'object')
			{
				from = BX.date.getServerTimestamp(fromTo.from);
				to = BX.date.getServerTimestamp(fromTo.to);
			}

			this.oEC.GetAttendeesByCodes(arCodes, function(users)
			{
				if (users.length > 0)
				{
					BX.addClass(_this.pAttCont, 'event-grid-dest-cont-full');
					_this.pMeetingParams.style.display = 'block';
				}
				else
				{
					BX.removeClass(_this.pAttCont, 'event-grid-dest-cont-full');
					_this.pMeetingParams.style.display = 'none';
				}
				_this.DisplayAttendees(users);
			},
			from,
			to,
			this.oEvent.ID || false
			);
		},

		DisplayAttendees: function(users)
		{
			BX.cleanNode(this.pAttContY);
			BX.cleanNode(this.pAttContN);
			BX.cleanNode(this.pAttContQ);
			this.pAttContY.style.display = this.pAttContN.style.display = this.pAttContQ.style.display = 'none';

			var dis = {Y: false, N: false, Q: false};
			for(var i in users)
			{
				if (this.attendeeIndex[users[i].USER_ID])
					users[i].STATUS = this.attendeeIndex[users[i].USER_ID] || 'Q';
				else
					users[i].STATUS = this.attendeeIndex[users[i].USER_ID] = users[i].STATUS || 'Q';

				if (users[i].STATUS == 'Q')
				{
					this.AddAttendee(users[i], this.pAttContQ);
					if (!dis.Q)
						dis.Q = !(this.pAttContQ.style.display = '');
				}
				else if (users[i].STATUS == 'Y')
				{
					this.AddAttendee(users[i], this.pAttContY);
					if (!dis.Y)
						dis.Y = !(this.pAttContY.style.display = '');
				}
				else
				{
					this.AddAttendee(users[i], this.pAttContN);
					if (!dis.N)
						dis.N = !(this.pAttContN.style.display = '');
				}
			}
		},

		AddAttendee: function(user, cont)
		{
			var row = cont.appendChild(BX.create("DIV", {props: {}, children: [
				BX.create("A", {props: {href: user.URL, title: user.DISPLAY_NAME, className: 'bxcal-user bxcal-user-link-name', target: "_blank"}, html: '<span class="bxcal-user-status"></span><span class="bxcal-user-avatar-outer"><span class="bxcal-user-avatar"><img src="' + user.AVATAR + '" width="21" height="21" /></span></span><span class="bxcal-user-name">' + BX.util.htmlspecialchars(user.DISPLAY_NAME) + '</span>'})
			]}));

			if (user.ACC == 'busy' || user.ACC == 'absent')
				row.appendChild(BX.create("SPAN", {props: {className: 'bxcal-user-acc'}, text: '(' + EC_MESS['acc_status_' + user.ACC] + ')'}));
		},

		InitDateTimeControls: function()
		{
			// From-to
			this.pFromToCont = BX('feed-cal-from-to-cont' + this.id);
			this.pFromDate = BX('feed-cal-event-from' + this.id);
			this.pToDate = BX('feed-cal-event-to' + this.id);
			this.pFromTime = BX('feed_cal_event_from_time' + this.id);
			this.pToTime = BX('feed_cal_event_to_time' + this.id);
			this.pFullDay = BX('event-full-day' + this.id);
			this.pFromTs = BX('event-from-ts' + this.id);
			this.pToTs = BX('event-to-ts' + this.id);
			//Reminder
			this.pReminderCont = BX('feed-cal-reminder-cont' + this.id);
			this.pReminder = BX('event-reminder' + this.id);
			this.pRemType = BX('event_remind_type' + this.id);
			this.pRemCount = BX('event_remind_count' + this.id);
			// Control events
			this.pFullDay.onclick = BX.proxy(this.FullDay, this);
			this.pReminder.onclick = BX.proxy(this.Reminder, this);

			var _this = this;
			// Date
			this.pFromDate.onclick = function(){BX.calendar({node: this.parentNode, field: this, bTime: false});};
			this.pToDate.onclick = function(){BX.calendar({node: this.parentNode, field: this, bTime: false});};

			this.pFromDate.onchange = function()
			{
				if(_this._FromDateValue)
				{
					var
						prevF = BX.parseDate(_this._FromDateValue),
						F = BX.parseDate(_this.pFromDate.value),
						T = BX.parseDate(_this.pToDate.value);

					if (F)
					{
						var duration = T.getTime() - prevF.getTime();
						T = new Date(F.getTime() + duration);
						_this.pToDate.value = bxFormatDate(T.getDate(), T.getMonth() + 1, T.getFullYear());
					}
				}
				_this._FromDateValue = _this.pFromDate.value;
			};

			// Time
			this.pFromTime.parentNode.onclick = this.pFromTime.onclick = window['bxShowClock_' + 'feed_cal_event_from_time' + _this.id];
			this.pToTime.parentNode.onclick = this.pToTime.onclick = window['bxShowClock_' + 'feed_cal_event_to_time' + _this.id];

			this.pFromTime.onchange = function()
			{
				if (_this.pToTime.value == "")
				{
					if(BX.util.trim(_this.pFromDate.value) == BX.util.trim(_this.pToDate.value) && BX.util.trim(_this.pToDate.value) != '')
					{
						var fromTime = _this.oEC.ParseTime(this.value);
						if (fromTime.h >= 23)
						{
							_this.pToTime.value = _this.oEC.FormatTimeByNum(0, fromTime.m);
							var date = BX.parseDate(_this.pFromDate.value);
							if (date)
							{
								date.setDate(date.getDate() + 1);
								_this.pToDate.value = bxFormatDate(date.getDate(), date.getMonth() + 1, date.getFullYear());
							}
						}
						else
						{
							_this.pToTime.value = _this.oEC.FormatTimeByNum(parseInt(fromTime.h, 10) + 1, fromTime.m);
						}
					}
					else
					{
						_this.pToTime.value = _this.pFromTime.value;
					}
				}
				else if (_this.pToDate.value == '' || _this.pToDate.value == _this.pFromDate.value)
				{
					if (_this.pToDate.value == '')
						_this.pToDate.value = _this.pFromDate.value;

					// 1. We need prev. duration
					if(_this._FromTimeValue)
					{
						var
							F = BX.parseDate(_this.pFromDate.value),
							T = BX.parseDate(_this.pToDate.value),
							prevFromTime = _this.oEC.ParseTime(_this._FromTimeValue),
							fromTime = _this.oEC.ParseTime(_this.pFromTime.value),
							toTime = _this.oEC.ParseTime(_this.pToTime.value);

						F.setHours(prevFromTime.h);
						F.setMinutes(prevFromTime.m);
						T.setHours(toTime.h);
						T.setMinutes(toTime.m);

						var duration = T.getTime() - F.getTime();
						if (duration != 0)
						{
							F.setHours(fromTime.h);
							F.setMinutes(fromTime.m);

							T = new Date(F.getTime() + duration);
							_this.pToDate.value = bxFormatDate(T.getDate(), T.getMonth() + 1, T.getFullYear());
							_this.pToTime.value = _this.oEC.FormatTimeByNum(T.getHours(), T.getMinutes());
						}
					}
				}

				_this._FromTimeValue = _this.pFromTime.value;
			};

			// Set values
			var fd, td;
			if (this.oEvent.DT_FROM_TS || this.oEvent.DT_TO_TS)
			{
				if (!this.oEC.Event.IsRecursive(this.oEvent))
				{
					fd = bxGetDateFromTS(this.oEvent.DT_FROM_TS);
					td = bxGetDateFromTS(this.oEvent.DT_TO_TS);
				}
				else
				{
					fd = bxGetDateFromTS(this.oEvent['~DT_FROM_TS']),
					td = bxGetDateFromTS(this.oEvent['~DT_TO_TS']);
				}
			}
			else
			{
				fd = this.oEC.GetUsableDateTime(new Date().getTime());
				td = this.oEC.GetUsableDateTime(new Date().getTime() + 3600000 /* one hour*/);
			}

			if (fd)
			{
				this._FromDateValue = this.pFromDate.value = bxFormatDate(fd.date, fd.month, fd.year);
				this._FromTimeValue = this.pFromTime.value = fd.bTime ? this.oEC.FormatTimeByNum(fd.hour, fd.min) : '';
			}
			else
			{
				this._FromDateValue = this._FromTimeValue = this.pFromDate.value = this.pFromTime.value = '';
			}

			if (td)
			{
				this.pToDate.value = bxFormatDate(td.date, td.month, td.year);
				this.pToTime.value = td.bTime ? this.oEC.FormatTimeByNum(td.hour, td.min) : '';
			}
			else
			{
				this.pToDate.value = this.pToTime.value = '';
			}

			if (this.oEvent.ID)
			{
				// Reminder
				this.pFullDay.checked = this.bFullDay = this.oEvent.DT_SKIP_TIME == 'Y';
				this.pFullDay.onclick();

				if(this.oEvent.REMIND && this.oEvent.REMIND.length > 0)
				{
					// Default value
					this.pReminder.checked = true;
					this.pRemType.value = this.oEvent.REMIND[0].type;
					this.pRemCount.value = this.oEvent.REMIND[0].count;
					this.Reminder(false, true);
				}
				else
				{
					// Default value
					this.pReminder.checked = false;
					this.pRemType.value = 'min';
					this.pRemCount.value = '15';
					this.Reminder(false, false);
				}
			}
			else
			{
				// Default value
				this.pFullDay.checked = false;
				this.FullDay(false, true);

				// Default value
				this.pReminder.checked = true;
				this.pRemType.value = 'min';
				this.pRemCount.value = '15';
				this.Reminder(false, true);
			}
		},

		FillFormFields: function()
		{
			var _this = this;
			this.pName = BX(this.id + '_edit_ed_name');
			this.pName.value = this.oEvent.NAME || '';
			this.Title = this.config.Title;

			this.pName.onkeydown = this.pName.onchange = function()
			{
				if (this._titleTimeout)
					clearTimeout(this._titleTimeout);

				this._titleTimeout = setTimeout(
					function(){
						var
							val = BX.util.htmlspecialchars(_this.pName.value);
						_this.Title.innerHTML = (_this.oEvent.ID ? EC_MESS.EditEvent : EC_MESS.NewEvent) + (val != '' ? ': ' + val : '');
					}, 20
				);
			};

			// Location
			this.Location = new BXInputPopup({
				id: this.id + 'loc_1',
				values: this.oEC.bUseMR ? this.oEC.meetingRooms : false,
				input: BX(this.id + '_planner_location1'),
				defaultValue: EC_MESS.SelectMR,
				openTitle: EC_MESS.OpenMRPage,
				className: 'calendar-inp calendar-inp-time',
				noMRclassName: 'calendar-inp calendar-inp-time'
			});
			this.Loc = {};
			BX.addCustomEvent(this.Location, 'onInputPopupChanged', BX.proxy(this.LocationOnChange, this));

			if (this.oEvent.ID)
			{
				var loc = BX.util.htmlspecialcharsback(this.oEvent.LOCATION);
				this.Loc.OLD = loc;
				this.Loc.NEW = loc;
				var arLoc = this.oEC.ParseLocation(loc, true);
				if (arLoc.mrid && arLoc.mrevid)
				{
					this.Location.Set(arLoc.mrind, '');
					this.Loc.OLD_mrid = arLoc.mrid;
					this.Loc.OLD_mrevid = arLoc.mrevid;
				}
				else
				{
					this.Location.Set(false, loc);
				}
			}
			else
			{
				this.Location.Set(false, '');
			}

			// Accessibility
			this.pAccessibility = BX(this.id + '_bxec_accessibility');
			if (this.pAccessibility)
				this.pAccessibility.value = this.oEvent.ACCESSIBILITY || 'busy';
			// Private
			this.pPrivate = BX(this.id + '_bxec_private');
			if (this.pPrivate)
				this.pPrivate.checked = this.oEvent.PRIVATE_EVENT || false;
			// Importance
			this.pImportance = BX(this.id + '_bxec_importance');
			if (this.pImportance)
				this.pImportance.value = this.oEvent.IMPORTANCE || 'normal';

			// Sections
			this.pSectSelect = BX(this.id + '_edit_ed_calend_sel');
			var sectId = this.oEvent.SECT_ID || this.oEC.GetLastSection();
			if (!this.oEC.oSections[sectId])
			{
				sectId = this.oEC.arSections[0].ID;
				this.oEC.SaveLastSection(sectId);
			}

			this.oEC.BuildSectionSelect(this.pSectSelect, sectId);
			this.pSectSelect.onchange = function()
			{
				var sectId = this.value;
				if (_this.oEC.oSections[sectId])
				{
					_this.oEC.SaveLastSection(sectId);
					//D.CAL.DOM.Warn.style.display = _this.oActiveSections[sectId] ? 'none' : 'block';
					_this.ColorControl.Set(_this.oEC.oSections[sectId].COLOR, _this.oEC.oSections[sectId].TEXT_COLOR);
				}
			};

			// Repeat
			this.RepeatCheck = BX(this.id + '_edit_ed_rep_check');
			this.RepeatSelect = BX(this.id + '_edit_ed_rep_sel');
			this.RepeatCont = BX(this.id + '_edit_ed_rep_cont');

			this.RepeatPhrase1 = BX(this.id + '_edit_ed_rep_phrase1');
			this.RepeatPhrase2 = BX(this.id + '_edit_ed_rep_phrase2');
			this.RepeatWeekDays = BX(this.id + '_edit_ed_rep_week_days');
			this.RepeatCount = BX(this.id + '_edit_ed_rep_count');
			this.RepeatDiapTo = BX(this.id + 'edit-ev-rep-diap-to');

			this.RepeatSelect.onchange = function() {_this.RepeatSelectOnChange(this.value);};
			this.RepeatCount.onmousedown = function() {_this.bEditEventDialogOver = true;};

			this.RepeatCheck.onclick = function()
			{
				if (this.checked)
					BX.addClass(_this.RepeatCont, 'bxec-popup-row-repeat-show');
				else
					BX.removeClass(_this.RepeatCont, 'bxec-popup-row-repeat-show');
			};

			this.RepeatDiapTo.onblur = this.RepeatDiapTo.onchange = function()
			{
				if (this.value && this.value != EC_MESS.NoLimits)
				{
					this.style.color = '#000000';
					return;
				}
				this.value = EC_MESS.NoLimits;
				this.style.color = '#C0C0C0';
			};

			this.RepeatDiapTo.onclick = function(){BX.calendar({node: this, field: this, bTime: false});BX.focus(this);};
			this.RepeatDiapTo.onfocus = function()
			{
				if (!this.value || this.value == EC_MESS.NoLimits)
					this.value = '';
				this.style.color = '#000000';
			};

			// Set recurtion rules "RRULE"
			if (this.oEC.Event.IsRecursive(this.oEvent))
			{
				this.RepeatCheck.checked = true;
				this.RepeatSelect.value = this.oEvent.RRULE.FREQ;

			}
			else
			{
				this.RepeatCheck.checked = false;
			}
			this.RepeatCheck.onclick();
			this.RepeatSelect.onchange();

			// Color
			this.ColorControl = this.oEC.InitColorDialogControl('event', function(color, textColor)
			{
				_this.Color = color;
				_this.TextColor = textColor;
			});

			if (!this.oEvent.displayColor && this.oEC.oSections[sectId])
				this.oEvent.displayColor = this.oEC.oSections[sectId].COLOR;
			if (!this.oEvent.displayTextColor && this.oEC.oSections[sectId])
				this.oEvent.displayTextColor = this.oEC.oSections[sectId].TEXT_COLOR;
			if (this.oEvent.displayColor)
				this.ColorControl.Set(this.oEvent.displayColor, this.oEvent.displayTextColor);
			else if(this.oEC.oSections[sectId])
				this.ColorControl.Set(this.oEC.oSections[sectId].COLOR, this.oEC.oSections[sectId].TEXT_COLOR);
		},

		LocationOnChange: function(oLoc, ind, value)
		{
			var D = this.oEditEventDialog;
			if (ind === false)
			{
				this.Loc.NEW = value || '';
			}
			else
			{
				// Same meeting room
				//if (ind != this.Loc.OLD_mrid)
				//	this.Loc.CHANGED = true;
				this.Loc.NEW = 'ECMR_' + this.oEC.meetingRooms[ind].ID;
			}
		},

		RepeatSelectOnChange: function(val)
		{
			var
				D = this.oEditEventDialog,
				i, l, BYDAY, date;

			val = val.toUpperCase();

			if (val == 'NONE')
			{
				//this.RepeatSect.style.display =  'none';
			}
			else
			{
				//this.RepeatSect.style.display =  'block';
				this.RepeatPhrase2.innerHTML = EC_MESS.DeDot; // Works only for de lang

				if (val == 'WEEKLY')
				{
					this.RepeatPhrase1.innerHTML = EC_MESS.EveryF;
					this.RepeatPhrase2.innerHTML += EC_MESS.WeekP;
					this.RepeatWeekDays.style.display = (val == 'WEEKLY') ? 'inline-block' : 'none';
					BYDAY = {};

					if (!this.RepeatWeekDaysCh)
					{
						this.RepeatWeekDaysCh = [];
						for (i = 0; i < 7; i++)
							this.RepeatWeekDaysCh[i] = BX(this.id + 'bxec_week_day_' + i);
					}

					if (this.oEvent && this.oEvent.ID && this.oEvent.RRULE && this.oEvent.RRULE.BYDAY)
					{
						BYDAY = this.oEvent.RRULE.BYDAY;
					}
					else
					{
						var date = BX.parseDate(this.pFromDate.value);
						if (!date)
							date = bxGetDateFromTS(this.oEvent.DT_FROM_TS);

						if(date)
							BYDAY[this.oEC.GetWeekDayByInd(date.getDay())] = true;
					}

					for (i = 0; i < 7; i++)
						this.RepeatWeekDaysCh[i].checked = !!BYDAY[this.RepeatWeekDaysCh[i].value];
				}
				else
				{
					if (val == 'YEARLY')
						this.RepeatPhrase1.innerHTML = EC_MESS.EveryN;
					else
						this.RepeatPhrase1.innerHTML = EC_MESS.EveryM;

					if (val == 'DAILY')
						this.RepeatPhrase2.innerHTML += EC_MESS.DayP;
					else if (val == 'MONTHLY')
						this.RepeatPhrase2.innerHTML += EC_MESS.MonthP;
					else if (val == 'YEARLY')
						this.RepeatPhrase2.innerHTML += EC_MESS.YearP;

					this.RepeatWeekDays.style.display = 'none';
				}

				var bPer = this.oEvent && this.oEC.Event.IsRecursive(this.oEvent);
				this.RepeatCount.value = (!this.oEvent.ID || !bPer) ? 1 : this.oEvent.RRULE.INTERVAL;

				if (!this.oEvent.ID || !bPer)
				{
					this.RepeatDiapTo.value = '';
				}
				else
				{
					if (this.oEvent.RRULE.UNTIL)
					{
						var d = bxGetDateFromTS(this.oEvent.RRULE.UNTIL);
						if (d.date == 1 && d.month == 1 && d.year == 2038)
							this.RepeatDiapTo.value = '';
						else
							this.RepeatDiapTo.value = bxFormatDate(d.date, d.month, d.year);
					}
					else
					{
						this.RepeatDiapTo.value = '';
					}

				}
				this.RepeatDiapTo.onchange();
			}
		},

		GetLHE: function()
		{
			if (!this.oLHE)
				this.oLHE = window[this.config.LHEJsObjName];
			return this.oLHE;
		},

		CustomizeHtmlEditor: function(editor)
		{
			if (editor.toolbar.controls && editor.toolbar.controls.spoiler)
			{
				BX.remove(editor.toolbar.controls.spoiler.pCont);
			}
		},

		FullDay: function(bSaveOption, value)
		{
			if (value == undefined)
				value = !this.bFullDay;

			if (value)
				BX.removeClass(this.pFromToCont, 'feed-cal-full-day');
			else
				BX.addClass(this.pFromToCont, 'feed-cal-full-day');
			this.bFullDay = value;
		},

		Reminder: function(bSaveOption, value)
		{
			if (value == undefined)
				value = !this.bReminder;

			this.pReminderCont.className = value ? 'bxec-reminder' : 'bxec-reminder-collapsed';

			this.bReminder = value;
		},

		GetAttendees: function()
		{
			return [];
		}
	};

	window.ECDragDropControl = function(Params)
	{
		this.oEC = Params.calendar;
		this.enabled = true;
	};

	window.ECDragDropControl.prototype = {
		Reset: function()
		{
			jsDD.Reset();
		},

		RegisterDay: function(dayCont)
		{
			if(!this.enabled)
				return;

			var _this = this;
			jsDD.registerDest(dayCont);

			dayCont.onbxdestdragfinish = function(currentNode, x, y)
			{
				if (_this.oDiv)
				{
					var
						eventInd = parseInt(_this.oDiv.getAttribute('data-bx-event-ind')),
						dayDate = new Date(_this.oEC.activeDateDaysAr[_this.oEC.GetDayIndexByElement(dayCont.parentNode)].getTime());

					if (!isNaN(eventInd) && _this.oEC.arEvents[eventInd])
						_this.MoveEventToNewDate(_this.oEC.arEvents[eventInd], dayDate, "day");

					BX.removeClass(dayCont, 'bxc-day-drag');
				}

				_this.OnDragFinish();

				return true;
			};
			dayCont.onbxdestdraghover = function(currentNode, x, y)
			{
				if (_this.oDiv)
					BX.addClass(dayCont, 'bxc-day-drag');
			};
			dayCont.onbxdestdraghout = function(currentNode, x, y)
			{
				if (_this.oDiv)
					BX.removeClass(dayCont, 'bxc-day-drag');
			};
		},

		RegisterTitleDay: function(dayCont1, dayCont2, tabId)
		{
			if(!this.enabled)
				return;

			var _this = this;
			jsDD.registerDest(dayCont1);
			jsDD.registerDest(dayCont2);

			dayCont1.onbxdestdragfinish = dayCont2.onbxdestdragfinish = function(currentNode, x, y)
			{
				if (_this.oDiv)
				{
					var
						eventInd = parseInt(_this.oDiv.getAttribute('data-bx-event-ind')),
						dayInd = parseInt(dayCont1.getAttribute('data-bx-day-ind')),
						day = _this.oEC.Tabs[tabId].arDays[dayInd],
						dayDate = new Date();
					dayDate.setFullYear(day.year, day.month, day.date);

					if (!isNaN(eventInd) && _this.oEC.arEvents[eventInd])
						_this.MoveEventToNewDate(_this.oEC.arEvents[eventInd], dayDate, "day");
				}

				BX.removeClass(dayCont1, 'bxc-day-drag');
				BX.removeClass(dayCont2, 'bxc-day-drag');

				_this.OnDragFinish();
				return true;
			};
			dayCont1.onbxdestdraghover = dayCont2.onbxdestdraghover = function(currentNode, x, y)
			{
				BX.addClass(dayCont1, 'bxc-day-drag');
				BX.addClass(dayCont2, 'bxc-day-drag');
			};
			dayCont1.onbxdestdraghout = dayCont2.onbxdestdraghout = function(currentNode, x, y)
			{
				BX.removeClass(dayCont1, 'bxc-day-drag');
				BX.removeClass(dayCont2, 'bxc-day-drag');
			};
		},

		RegisterTimeline: function(timelineCont, oTab)
		{
			if(!this.enabled)
				return;

			var _this = this;
			jsDD.registerDest(timelineCont);

			timelineCont.onbxdestdragfinish = function(currentNode, x, y)
			{
				if (_this.oDiv)
				{
					var eventInd = parseInt(_this.oDiv.getAttribute('data-bx-event-ind'));
					if (isNaN(eventInd) || !_this.oEC.arEvents[eventInd])
						return;
					var oEvent = _this.oEC.arEvents[eventInd];

					if (currentNode.getAttribute('data-bx-event-resizer') == 'Y')
					{
						// Delta height
						var
							originalHeight = parseInt(_this.oDiv.getAttribute('data-bx-original-height'), 10),
							deltaHeight = _this.oDiv.offsetHeight - originalHeight,
							dur = parseInt(((deltaHeight - 1) / 40) * 3600); // In seconds

						_this.ResizeEventTimeline(oEvent, dur);
					}
					else
					{
						var dayInd = _this.oDiv.getAttribute('data-bx-day-index');

						if (dayInd != undefined && oTab.arDays[dayInd])
						{
							var
								curDay = oTab.arDays[dayInd],
								eventY = parseInt(_this.oDiv.style.top, 10) - BX.pos(timelineCont).top + timelineCont.scrollTop,
								dtFrom = Math.max((eventY - 1) / 42 * 60, 0); // In seconds

							dtFrom = Math.round(dtFrom / 10) * 10; // Round to 10 minutes

							var
								hour = parseInt(dtFrom / 60, 10),
								min = Math.max(dtFrom - hour * 60, 0),
								dayDate = new Date();

							dayDate.setFullYear(curDay.year, curDay.month, curDay.date);
							dayDate.setHours(hour);
							dayDate.setMinutes(min);
							dayDate.setSeconds(0);

							if (_this.oDiv.getAttribute("data-bx-title-event"))
							{
								oEvent.DT_SKIP_TIME = 'N'; // It cames from title
								_this.MoveEventToNewDate(oEvent, dayDate, "timeline", 3600000);
							}
							else
								_this.MoveEventToNewDate(oEvent, dayDate, "timeline");
						}
					}
				}

				_this.OnDragFinish();
				return true;
			};

			timelineCont.onbxdestdraghover = function(currentNode, x, y)
			{
				_this.timeLineEventOver = true;
				_this.PrepareTimelineDaysPos(timelineCont, oTab);
				BX.addClass(timelineCont, 'bxec-timeline-div-drag');
			};

			timelineCont.onbxdestdraghout = function(currentNode, x, y)
			{
				_this.ClearTimeline(timelineCont);
			};
			timelineCont.onbxdestdragstop = function(currentNode, x, y)
			{
				_this.ClearTimeline(timelineCont);
			};
		},

		ClearTimeline: function(timelineCont)
		{
			this.timeLineEventOver = false;
			BX.removeClass(timelineCont, 'bxec-timeline-div-drag');
			jsDD.current_dest_index = false;
		},

		GetTimelinePos: function(obDest)
		{
			return obDest.__bxpos;
		},

		PrepareTimelineDaysPos: function(timelineCont, oTab)
		{
			this.timeLinePos = this.GetTimelinePos(timelineCont);

			var pTimelineRow = oTab.pTimelineTable.rows[0];

			var dayCell, i, dayPos;
			this.arDays = [];
			for (var i = 1; i < pTimelineRow.cells.length; i++)
			{
				dayCell = pTimelineRow.cells[i];
				dayPos = BX.pos(dayCell);
				dayPos._left = dayPos.left - this.timeLinePos[0];
				dayPos._right = dayPos.right - this.timeLinePos[0];
				this.arDays.push(dayPos);
			}

			if (!this.activeDayDrop)
			{
				this.activeDayDrop = BX.create("DIV", {props: {className: 'bxec-timeline-active-day-drag-selector'}});
				this.activeDayDrop.style.height = parseInt(oTab.pTimelineTable.offsetHeight, 10) + 'px';
			}
			if (this.activeDayDrop.parentNode != timelineCont)
				timelineCont.appendChild(this.activeDayDrop);

			if (!this.timelineDragOverlay)
			{
				this.timelineDragOverlay = BX.create("DIV", {props: {className: 'bxec-timeline-drag-overlay'}});
				this.timelineDragOverlay.style.height = parseInt(oTab.pTimelineTable.offsetHeight, 10) + 'px';
			}
			if (this.timelineDragOverlay.parentNode != timelineCont)
				timelineCont.appendChild(this.timelineDragOverlay);
		},

		CheckTimelineOverPos: function(x, y)
		{
			if (this.timeLineEventOver)
			{
				this.activeDayDrop.style.display = 'block';
				var i, l = this.arDays.length;

				for (i = 0; i < l; i++)
				{
					if (x >= this.arDays[i].left && x <= this.arDays[i].right)
					{
						this.activeDayDrop.style.left = (this.arDays[i]._left - 1) + 'px';
						this.activeDayDrop.style.width = (this.arDays[i].width -1) + 'px';

						this.oDiv.style.width = (this.arDays[i].width - 5) + 'px';
						this.oDiv.style.left = (this.arDays[i].left + 1) + 'px';
						this.oDiv.style.top = (y - 10) + 'px';

						this.oDiv.setAttribute('data-bx-day-index', i);
						break;
					}
				}
			}
			else
			{
				if (this.activeDayDrop)
					this.activeDayDrop.style.display = 'none';
			}
		},

		RegisterEvent: function(oDiv, event, tab)
		{
			if(!this.enabled)
				return;

			var bDeny = (event['~TYPE'] == 'tasks' || !this.oEC.Event.CanDo(event, 'edit') || this.oEC.Event.IsRecursive(event));

			var _this = this;
			jsDD.registerObject(oDiv);

			oDiv.setAttribute("data-bx-title-event", true);

			oDiv.onbxdragstart = function()
			{
				if (bDeny)
				{
					_this.oDiv = null;
					document.body.style.cursor = 'default';
					_this.ShowDenyNotice(oDiv, event);
				}
				else
				{
					_this.oDiv = oDiv.cloneNode(true);
					_this.oDiv.className = 'bxec-event bxec-event-drag';
					document.body.appendChild(_this.oDiv);
					_this.oDiv.style.top = '-1000px';
					_this.oDiv.style.left = '-1000px';

					var moreEventsWin = _this.oEC.MoreEventsWin;
					if(moreEventsWin)
					{
						moreEventsWin.close();
						moreEventsWin.destroy();
						moreEventsWin = null;
					}
				}
			};

			oDiv.onbxdrag = function(x, y)
			{
				if (_this.oDiv)
				{
					_this.oDiv.style.left = (x - 20) + 'px';
					_this.oDiv.style.top = (y - 10) + 'px';

					if (tab == 'week_title')
					{
						// We move event from title to timeline (week, day mode)
						_this.CheckTimelineOverPos(x, y);
					}
				}
			};

			oDiv.onbxdragstop = function(x, y)
			{
				if (_this.oDiv)
				{
					setTimeout(function()
					{
						if (_this.oDiv && _this.oDiv.parentNode)
						{
							_this.oDiv.parentNode.removeChild(_this.oDiv);
							_this.oDiv = null;
						}
					}, 100);
				}
				_this.OnDragFinish();
			};

			oDiv.onbxdragfinish = function(destination, x, y)
			{
				_this.OnDragFinish();
				return true;
			};
		},

		RegisterTimelineEvent: function(oDiv, event, tab)
		{
			if(!this.enabled)
				return;

			var bDeny = (event['~TYPE'] == 'tasks' || !this.oEC.Event.CanDo(event, 'edit') || this.oEC.Event.IsRecursive(event));

			var _this = this;
			jsDD.registerObject(oDiv);

			oDiv.onbxdragstart = function()
			{
				if (bDeny)
				{
					_this.oDiv = null;
					document.body.style.cursor = 'default';
					_this.ShowDenyNotice(oDiv, event);
				}
				else
				{
					_this.oDiv = oDiv.cloneNode(true);
					_this.oDiv.className = 'bxec-tl-event bxec-event-drag';
					document.body.appendChild(_this.oDiv);
					_this.oDiv.style.top = '-1000px';
					_this.oDiv.style.left = '-1000px';
				}
			};

			oDiv.onbxdrag = function(x, y)
			{
				if (!_this.oDiv)
					return;

				if (_this.timeLineEventOver)
				{
					var i, l = _this.arDays.length;
					for (i = 0; i < l; i++)
					{
						if (x >= _this.arDays[i].left && x <= _this.arDays[i].right)
						{
							_this.oDiv.style.width = (_this.arDays[i].width - 15) + 'px';
							_this.oDiv.style.left = (_this.arDays[i].left + 1) + 'px';
							_this.oDiv.style.top = (y - 10) + 'px';
							_this.oDiv.setAttribute('data-bx-day-index', i);
							break;
						}
					}
				}
			};

			oDiv.onbxdragstop = function(x, y)
			{
				_this.OnDragFinish();
				if (!_this.oDiv)
					return;

				setTimeout(function()
				{
					if (_this.oDiv && _this.oDiv.parentNode)
					{
						_this.oDiv.parentNode.removeChild(_this.oDiv);
						_this.oDiv = null;
					}
				}, 100);
			};

			oDiv.onbxdragfinish = function(destination, x, y)
			{
				_this.OnDragFinish();
			};
		},

		RegisterTimelineEventResizer: function(ddResizer, oDiv, event, tab)
		{
			if(!this.enabled)
				return;

			var bDeny = (event['~TYPE'] == 'tasks' || !this.oEC.Event.CanDo(event, 'edit') || this.oEC.Event.IsRecursive(event));

			ddResizer.setAttribute('data-bx-event-resizer', 'Y');

			BX.bind(ddResizer, "mousedown", function(e)
			{
				var wndSize = BX.GetWindowSize();
				e = e || window.event;

				_this.timelineResize = {
					oDiv : oDiv,
					startY: e.clientY + wndSize.scrollTop,
					height: parseInt(oDiv.offsetHeight)
				};
			});

			var _this = this;
			jsDD.registerObject(ddResizer);

			ddResizer.onbxdragstart = function()
			{
				if (bDeny)
				{
					_this.oDiv = null;
					document.body.style.cursor = 'default';
					_this.ShowDenyNotice(ddResizer, event);
					return;
				}

				document.body.style.cursor = 's-resize';
				_this.oDiv = oDiv;
				BX.removeClass(_this.oDiv, 'bxec-tl-ev-hlt');
			};

			ddResizer.onbxdrag = function(x, y)
			{
				if (_this.oDiv && _this.timeLineEventOver)
				{
					var height = (_this.timelineResize.height + y - _this.timelineResize.startY + 5);
					if (height <= 0)
						height = 5;

					_this.timelineResize.oDiv.style.height = height + 'px';
				}
			};

			ddResizer.onbxdragstop = function(x, y)
			{
				_this.OnDragFinish();
				if (!_this.oDiv)
					return;
			};

			ddResizer.onbxdragfinish = function(destination, x, y)
			{
				_this.OnDragFinish();
			};
		},

		ResizeEventTimeline: function(event, length)
		{
			event.DT_LENGTH = Math.max(parseInt(event.DT_LENGTH, 10) + length, 0);
			// Round to 10 min
			event.DT_LENGTH = Math.round(event.DT_LENGTH / 600) * 600;
			event.DT_TO_TS = parseInt(event.DT_FROM_TS) + event.DT_LENGTH * 1000;
			this.oEC.Event.Display();

			var _this = this;
			this.oEC.Request({
				postData: this.oEC.GetReqData('move_event_to_date',
					{
						id: event.ID,
						from_ts: BX.date.getServerTimestamp(event.DT_FROM_TS),
						to_ts: BX.date.getServerTimestamp(event.DT_TO_TS),
						section: event.SECT_ID,
						skip_time: event.DT_SKIP_TIME
					}
				),
				errorText: EC_MESS.EventSaveError,
				handler: function(oRes)
				{
					return true;
				}
			});
		},

		MoveEventToNewDate: function(event, newDate, mode, DT_LENGTH)
		{
			var from = bxGetDateFromTS(event.DT_FROM_TS);
			if (mode == 'day')
			{
				newDate.setHours(from.hour || 0);
				newDate.setMinutes(from.min || 0);
			}

			var
				_this = this,
				from_ts = newDate.getTime();

			this.oEC.Request({
				postData: this.oEC.GetReqData('move_event_to_date',
					{
						id: event.ID,
						from_ts: BX.date.getServerTimestamp(from_ts),
						to_ts: DT_LENGTH ? BX.date.getServerTimestamp(from_ts + DT_LENGTH) : 0,
						section: event.SECT_ID,
						skip_time: event.DT_SKIP_TIME
					}
				),
				errorText: EC_MESS.EventSaveError,
				handler: function(oRes)
				{
					return true;
				}
			});

			// Update DT_FROM_TS, DT_TO_TS for event
			var dif = DT_LENGTH != undefined ? DT_LENGTH : event.DT_TO_TS - event.DT_FROM_TS;
			event.DT_FROM_TS = from_ts;
			event.DT_TO_TS = parseInt(from_ts) + parseInt(dif);

			if (DT_LENGTH != undefined)
				event.DT_LENGTH = parseInt(DT_LENGTH, 10) / 1000;

			this.oEC.Event.Display();
		},

		ShowDenyNotice: function(oDiv, event)
		{
			if (!this.pNotice)
				this.pNotice = document.body.appendChild(BX.create("DIV", {props: {className: "bxec-event-drag-deny-notice"}}));

			if (this.bNoticeShown)
				this.HideDenyNotice();

			if (event['~TYPE'] == 'tasks')
				this.pNotice.innerHTML = EC_MESS.ddDenyTask;
			else if(this.oEC.Event.IsRecursive(event))
				this.pNotice.innerHTML = EC_MESS.ddDenyRepeted;
			else
				this.pNotice.innerHTML = EC_MESS.ddDenyEvent;

			var pos = BX.align(oDiv, 250, 50, 'top');
			this.pNotice.style.left = pos.left + 'px';
			this.pNotice.style.top = pos.top + 'px';
			this.pNotice.style.display = "block";
			this.bNoticeShown = true;

			BX.bind(document, "mouseup", BX.proxy(this.HideDenyNotice, this));
		},

		HideDenyNotice: function()
		{
			if (this.bNoticeShown)
			{
				this.bNoticeShown = false;
				if (this.pNotice)
					this.pNotice.style.display = "none";

				BX.unbind(document, "mouseup", BX.proxy(this.HideDenyNotice, this));
			}
		},

		OnDragFinish: function()
		{
		},

		IsDragDropNow: function()
		{
			return jsDD.bStarted;
		}
	};
})(window);



