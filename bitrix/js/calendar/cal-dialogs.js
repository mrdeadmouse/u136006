// # # #  #  #  # Add Event Dialog  # # #  #  #  #
JCEC.prototype.ShowAddEventDialog = function(bShowCalendars)
{
	var _this = this;
	if (this.bReadOnly)
		return;

	if (!this.CheckSectionsCount())
		return alert(EC_MESS.NoCalendarsAlert);

	var D = this.oAddEventDialog;
	if (!D)
	{
		D = new BX.PopupWindow("BXCAddEvent" + this.id, null, {
			overlay: {opacity: 10},
			autoHide: true,
			closeByEsc : true,
			zIndex: 0,
			offsetLeft: 0,
			offsetTop: 0,
			draggable: true,
			bindOnResize: false,
			titleBar: {content: BX.create("span", {html: EC_MESS.NewEvent})},
			closeIcon: { right : "12px", top : "10px"},
			className: 'bxc-popup-window',
			buttons: [
				new BX.PopupWindowButton({
					text: EC_MESS.GoExt,
					title: EC_MESS.GoExtTitle,
					className: "bxec-popup-link-icon bxec-popup-add-ex",
					events: {click : function(){_this.OpenExFromSimple();}}
				}),
				new BX.PopupWindowButton({
					text: EC_MESS.Add,
					className: "popup-window-button-accept",
					events: {click : function(){
						if (_this.SimpleSaveNewEvent())
							_this.CloseAddEventDialog(true);
					}}
				}),
				new BX.PopupWindowButtonLink({
					text: EC_MESS.Close,
					className: "popup-window-button-link-cancel",
					events: {click : function(){_this.CloseAddEventDialog(true);}}
				})
			],
			content: BX('bxec_add_ed_' + this.id),
			events: {}
		});

		D.CAL = {
			DOM: {
				Name: BX(this.id + '_add_ed_name'),
				PeriodText: BX(this.id + '_add_ed_per_text'),
				SectSelect: BX(this.id + '_add_ed_calend_sel'),
				Warn: BX(this.id + '_add_sect_sel_warn')
			}
		};

		if (this.bIntranet && (this.Personal() || this.type != 'user'))
		{
			D.CAL.DOM.Accessibility = BX(this.id + '_add_ed_acc');
			if (D.CAL.DOM.Accessibility && BX.browser.IsIE())
				D.CAL.DOM.Accessibility.style.width = '250px';
		}
		this.oAddEventDialog = D;

		D.CAL.DOM.SectSelect.onchange = function()
		{
			_this.SaveLastSection(this.value);
			D.CAL.DOM.Warn.style.display = _this.oActiveSections[D.CAL.DOM.SectSelect.value] ? 'none' : 'block';
		};

		BX.addCustomEvent(D, 'onPopupClose', BX.proxy(this.CloseAddEventDialog, this));
	}

	var
		f, t, cts, a, cdts, perHTML,
		time_f = '', time_t = '';

	D.CAL.DOM.Name.value = '';
	this.BuildSectionSelect(D.CAL.DOM.SectSelect, this.GetLastSection());
	D.CAL.DOM.Warn.style.display = _this.oActiveSections[D.CAL.DOM.SectSelect.value] ? 'none' : 'block';

	if (this.selectDaysMode) // Month view
	{
		var
			start_ind = parseInt(this.selectDaysStartObj.id.substr(9)),
			end_ind = parseInt(this.selectDaysEndObj.id.substr(9));
		if (start_ind > end_ind) // swap start_ind and end_ind
		{
			a = end_ind;
			end_ind = start_ind;
			start_ind = a;
		}

		f = this.activeDateDaysAr[start_ind];
		t = this.activeDateDaysAr[end_ind];
	}
	else if (this.selectTimeMode) // Week view - time select
	{
		cts = this.curTimeSelection;
		f = new Date(cts.sDay.year, cts.sDay.month, cts.sDay.date, cts.sHour, cts.sMin);
		t = new Date(cts.eDay.year, cts.eDay.month, cts.eDay.date, cts.eHour, cts.eMin);

		if (f.getTime() > t.getTime())
		{
			a = f;
			f = t;
			t = a; // swap "f" and "t"
		}
	}
	else if (this.selectDayTMode) // Week view - days select
	{
		cdts = this.curDayTSelection;
		f = new Date(cdts.sDay.year, cdts.sDay.month, cdts.sDay.date);
		t = new Date(cdts.eDay.year, cdts.eDay.month, cdts.eDay.date);
	}
	else
		return;

	var
		f_day = this.ConvertDayIndex(f.getDay()),
		t_day = this.ConvertDayIndex(t.getDay());

	if (f.getTime() == t.getTime()) // one day
	{
		perHTML = this.days[f_day][0] + ' ' + bxFormatDate(f.getDate(), f.getMonth() + 1, f.getFullYear());
	}
	else
	{
		var
			d_f = f.getDate(), m_f = f.getMonth() + 1, y_f = f.getFullYear(), h_f = f.getHours(), mi_f = f.getMinutes(),
			d_t = t.getDate(), m_t = t.getMonth() + 1, y_t = t.getFullYear(), h_t = t.getHours(), mi_t = t.getMinutes(),
			bTime = !(h_f == h_t && h_f == 0 && mi_f == mi_t && mi_f == 0);

		if (bTime)
		{
			time_f = this.FormatTimeByNum(h_f, mi_f);
			time_t = this.FormatTimeByNum(h_t, mi_t);
		}

		if (m_f == m_t && y_f == y_t && d_f == d_t && bTime) // Same day, different time
			perHTML = this.days[f_day][0] + ' ' + bxFormatDate(d_f, m_f, y_f) + ', ' + time_f + ' &mdash; ' + time_t;
		else
			perHTML = this.days[f_day][0] + ' ' + bxFormatDate(d_f, m_f, y_f) + ' ' +  time_f + ' &mdash; ' +
				this.days[t_day][0] + ' ' + bxFormatDate(d_t, m_t, y_t) + ' ' + time_t;
	}

	D.CAL.DOM.PeriodText.innerHTML = perHTML;
	D.CAL.Params = {
		from: f,
		to: t,
		time_f: time_f || '',
		time_t: time_t || ''
	};
	setTimeout(function(){BX.focus(D.CAL.DOM.Name);}, 500);

	if (this.bIntranet && (this.Personal() || this.type != 'user'))
		D.CAL.DOM.Accessibility.value = 'busy';

	pos = this.GetAddDialogPosition();
	if (pos)
	{
		D.popupContainer.style.top = pos.top + "px";
		D.popupContainer.style.left = pos.left + "px";
	}

	D.show();
}

JCEC.prototype.OpenExFromSimple = function(bCallback)
{
	this.CloseAddEventDialog(true);
	if (!bCallback)
		return this.ShowEditEventPopup({bExFromSimple: true});

	var
		D1 = this.oAddEventDialog,
		D2 = this.oEditEventDialog,
		con = D2.oController,
		f = D1.CAL.Params.from,
		t = D1.CAL.Params.to;

	con._FromDateValue = con.pFromDate.value = bxFormatDate(f.getDate(), f.getMonth() + 1, f.getFullYear());
	con.pToDate.value = bxFormatDate(t.getDate(), t.getMonth() + 1, t.getFullYear());
	con._FromTimeValue = con.pFromTime.value = D1.CAL.Params.time_f || '';
	con.pToTime.value = D1.CAL.Params.time_t || '';

	var bTime = !!(D1.CAL.Params.time_f || D1.CAL.Params.time_t);
	con.pFullDay.checked = !bTime;
	con.FullDay(false, bTime);

	con.pName.value = D1.CAL.DOM.Name.value;

	if (this.bIntranet && con.pAccessibility && D1.CAL.DOM.Accessibility)
		con.pAccessibility.value = D1.CAL.DOM.Accessibility.value;

	//Set WUSIWUG Editor Content
	//setTimeout(function(){window.pLHEEvDesc.SetEditorContent('');}, 100);

	if (D1.CAL.DOM.SectSelect.value)
	{
		con.pSectSelect.value = D1.CAL.DOM.SectSelect.value;
		if (con.pSectSelect.onchange)
			con.pSectSelect.onchange();
	}
}

JCEC.prototype.CloseAddEventDialog = function(bClosePopup)
{
	if (!this.oAddEventDialog)
		return;
	switch (this.activeTabId)
	{
		case 'month':
			this.DeSelectDays();
			break;
		case 'week':
			this.DeSelectTime(this.activeTabId);
			this.DeSelectDaysT();
			break;
		case 'day':
			break;
	}
	if (bClosePopup === true)
		this.oAddEventDialog.close();
}

JCEC.prototype.GetAddDialogPosition = function()
{
	if (this.activeTabId == 'month')
	{
		var last_selected = this.arSelectedDays[this.bInvertedDaysSelection ? 0 : this.arSelectedDays.length - 1];
		if (!last_selected)
			return false;

		var pos = BX.pos(last_selected);
		pos.top += parseInt(this.dayCellHeight / 2) + 20;
		pos.left += parseInt(this.dayCellWidth / 2) + 20;

		pos.right = pos.left;
		pos.bottom = pos.top;
		pos = BX.align(pos, 360, 180);
		return pos;
	}
	else //if (this.activeTabId == 'week')
	{
		return false;
	}
}

// # # #  #  #  # Edit Event Dialog  # # #  #  #  #
JCEC.prototype.CreateEditEventPopup = function(bCheck)
{
	var
		_this = this,
		content = BX.create('DIV'),
		pTitle = BX.create("span", {props:{className: 'bxce-dialog-title'}, html: EC_MESS.NewEvent});

	var D = new BX.PopupWindow("BXCEditEvent" + this.id, null, {
		overlay: {opacity: 10},
		autoHide: false,
		closeByEsc : true,
		zIndex: -100,
		offsetLeft: 0,
		offsetTop: 0,
		draggable: true,
		titleBar: {content: pTitle},
		closeIcon: { right : "12px", top : "10px"},
		className: "bxc-popup-tabed bxc-popup-window",
		buttons: [
			new BX.PopupWindowButton({
				text: EC_MESS.Delete,
				id: this.id + 'ed-del-button',
				className: "bxec-popup-link-icon bxec-popup-del-ex",
				events: {click : function(){
					if (_this.Event.Delete(D.CAL.oEvent))
						_this.CloseEditEventDialog(true);
				}}
			}),
			new BX.PopupWindowButton({
				text: EC_MESS.Save,
				className: "popup-window-button-accept",
				events: {click : function(){
					if (_this.oEditEventDialog.CAL.bMeetingStyleFields)
					{
						_this.Event.SetMeetingParams({callback: function(){_this.CloseEditEventDialog(true);}, bLocationChecked: false});
					}
					else
					{
						_this.oEditEventDialog.oController.SaveForm({callback: function(){_this.CloseEditEventDialog(true);}});
					}
				}}
			}),
			new BX.PopupWindowButtonLink({
				text: EC_MESS.Close,
				className: "popup-window-button-link-cancel",
				events: {click : function(){_this.CloseEditEventDialog(true);}}
			})
		],
		content: content,
		//content: BX('bxec_edit_ed_' + this.id),
		events: {}
	});

	BX.addCustomEvent(D, 'onPopupClose', BX.proxy(this.CloseEditEventDialog, this));

	D.CAL = {
		DOM: {
			content: content,
			Title: pTitle,
			DelBut: BX(this.id + 'ed-del-button')
		}
	};

	this.oEditEventDialog = D;
};

JCEC.prototype.ShowEditEventPopup = function(Params)
{
	if (!this.oEditEventDialog)
		this.CreateEditEventPopup();

	if (this.showDialogRequest || this.oEditEventDialog.bShowed)
		return;

	if (window.LHEPostForm && window.LHEPostForm.unsetHandler && LHEPostForm.getHandler(this.id + '_event_editor'))
	{
		window.LHEPostForm.unsetHandler(this.id + '_event_editor');
	}

	if (!Params)
		Params = {};

	var
		_this = this, eventId,
		bExFromSimple = Params.bExFromSimple,
		D = this.oEditEventDialog;

	D.CAL.oEvent = Params.oEvent || {};
	eventId = (D.CAL.oEvent && D.CAL.oEvent.ID) ? D.CAL.oEvent.ID : 0;

	if (eventId)
	{
		D.CAL.DOM.DelBut.style.display = '';
		D.CAL.DOM.Title.innerHTML = EC_MESS.EditEvent;
	}
	else
	{
		D.CAL.DOM.DelBut.style.display = 'none';
		D.CAL.DOM.Title.innerHTML = EC_MESS.NewEvent;
	}

	this.showDialogRequest = true;
	BX.ajax.get(
		this.actionUrl,
		this.GetReqData('get_edit_event_dialog',
			{
				event_id : eventId ,
				js_id: this.id
			}),
		function(html)
		{
			_this.showDialogRequest = false;
			D.CAL.DOM.content.innerHTML = html;
		}
	);

	// Destination
	function f()
	{
		D.CAL.DOM.pTabs = BX(_this.id + '_edit_tabs');
		if (!D.CAL.DOM.pTabs)
			return;
		_this.ChargePopupTabs(D, _this.id + 'ed-tab-');

		if (!Params.bRunPlanner)
			_this.oEditEventDialog.show();

		if (window.__ATTENDEES_ACC)
			D.CAL.oEvent['~ATTENDEES'] = window.__ATTENDEES_ACC;

		D.oController = new EditEventPopupController({
			form: document.forms.event_edit_form,
			oEC: _this,
			oEvent: D.CAL.oEvent,
			id: _this.id,
			editorContId: "bx_cal_editor_cont_" + _this.id,
			LHEJsObjName: 'pLHEEvDesc',
			LHEId: 'LHEEvDesc',
			WDControllerCID : window.__UPLOAD_WEBDAV_ELEMENT_CID,
			arFiles: window.__UPLOAD_WEBDAV_ELEMENT_VALUE,
			Title: D.CAL.DOM.Title
		});

		if (window.editEventDestinationFormName)
		{
			BxEditEventGridSetLinkName(window.editEventDestinationFormName);
			BX.bind(BX('event-grid-dest-input'), 'keyup', BxEditEventGridSearch);
			BX.bind(BX('event-grid-dest-input'), 'keydown', BxEditEventGridSearchBefore);
			BX.bind(BX('event-grid-dest-add-link'), 'click', function(e){BX.SocNetLogDestination.openDialog(editEventDestinationFormName); BX.PreventDefault(e); });
			BX.bind(BX('event-grid-dest-cont'), 'click', function(e){BX.SocNetLogDestination.openDialog(editEventDestinationFormName); BX.PreventDefault(e);});
		}
		BX.removeCustomEvent('onAjaxSuccessFinish', f);

		if (Params.bRunPlanner)
			_this.RunPlanner(
				{curEventId: false, attendees: [{id: _this.userId, name: _this.userName}]}
			);

		if (bExFromSimple)
			_this.OpenExFromSimple(true);
	}
	BX.addCustomEvent('onAjaxSuccessFinish', f);
};

JCEC.prototype._LocOnChange = function(oLoc, ind, value)
{
	var D = this.oEditEventDialog;
	if (ind === false)
	{
		D.CAL.Loc.NEW = value || '';
	}
	else
	{
		// Same meeting room
		if (ind != D.CAL.Loc.OLD_mrid)
			D.CAL.Loc.CHANGED = true;
		D.CAL.Loc.NEW = 'ECMR_' + this.meetingRooms[ind].ID;
	}
};

JCEC.prototype.SetEditingMeetingFields = function(bDeactivate)
{
	bDeactivate = !!bDeactivate;
	var D = this.oEditEventDialog;
	if (D.CAL.bMeetingStyleFields != bDeactivate)
	{
		D.CAL.bMeetingStyleFields = bDeactivate; //
		if (bDeactivate)
		{
			BX.addClass(D.CAL.Tabs[0].cont, 'bxc-meeting-edit-dis');
			D.CAL.DOM.pTabs.style.display = bDeactivate ? 'none' : '';
			// Move reminder params to first tab
			D.CAL.Tabs[0].cont.appendChild(D.CAL.DOM.RemindCnt);
		}
		else
		{
			BX.removeClass(D.CAL.Tabs[0].cont, 'bxc-meeting-edit-dis');
			D.CAL.DOM.pTabs.style.display = '';
			// Move reminder params back to the third tab
			D.CAL.Tabs[3].cont.insertBefore(D.CAL.DOM.RemindCnt, D.CAL.Tabs[3].cont.firstChild);
		}
	}
}

JCEC.prototype.CloseEditEventDialog = function(bClosePopup)
{
	if (!this.oEditEventDialog)
		return;
	if (bClosePopup === true)
	{
		if (this.oEditEventDialog.oController.Location && this.oEditEventDialog.oController.Location.oPopup)
			this.oEditEventDialog.oController.Location.oPopup.destroy();

		this.oEditEventDialog.close();
	}
}

// # # #  #  #  # View Event Dialog  # # #  #  #  #
JCEC.prototype.CreateViewEventPopup = function()
{
	var
		_this = this,
		content = BX.create('DIV'),
		D = new BX.PopupWindow("BXCViewEvent" + this.id, null, {
		overlay: {opacity: 10},
		autoHide: false,
		closeByEsc : true,
		zIndex: -100,
		offsetLeft: 0,
		offsetTop: 0,
		draggable: true,
		titleBar: {content: BX.create("span", {props: {className: 'bxec-popup-title', id: this.id + '_viewev_title'}, html: EC_MESS.ViewingEvent})},
		closeIcon: {right : "12px", top : "10px"},
		className: "bxc-popup-tabed bxc-popup-window",
		buttons: [
			new BX.PopupWindowButton({
				text:  EC_MESS.Delete,
				id: this.id + '_viewev_del_but',
				className: "bxec-popup-link-icon bxec-popup-del-ev",
				events: {click : function(){
					if(_this.Event.Delete(D.CAL.oEvent))
						_this.CloseViewDialog(true);
				}}
			}),
			new BX.PopupWindowButton({
				text: EC_MESS.Edit,
				className: "bxec-popup-link-icon bxec-popup-ed-ev",
				id: this.id + '_viewev_edit_but',
				events: {click : function(){
					_this.ShowEditEventPopup({oEvent: D.CAL.oEvent});
					_this.CloseViewDialog(true);
				}}
			}),
			new BX.PopupWindowButton({
				text: EC_MESS.Close,
				className: "popup-window-button-accept",
				events: {click : function(){_this.CloseViewDialog(true);}}
			})
		],
		content: content,
		events: {}
	});

	BX.addCustomEvent(D, 'onPopupClose', BX.proxy(this.CloseViewDialog, this));

	D.CAL = {
		DOM: {
			content: content,
			TITLE: BX(this.id + '_viewev_title'),
			delBut : BX(this.id + '_viewev_del_but'),
			editBut : BX(this.id + '_viewev_edit_but')
		}
	};

	BX.bind(D.CAL.DOM.content, "click", function(e)
	{
		var targ = e.target || e.srcElement;

		var bxMoreUsers = targ.getAttribute('data-bx-more-users');
		if (!!bxMoreUsers)
		{
			var attCont = BX(bxMoreUsers);
			if (attCont)
				BX.addClass(attCont, 'bx-cal-view-att-cont-full');
			return;
		}

		var bxSetStatusLink = targ.getAttribute('data-bx-set-status') || targ.parentNode.getAttribute('data-bx-set-status');
		if (!!bxSetStatusLink)
		{
			_this.Event.SetMeetingStatus(bxSetStatusLink == 'Y');
			D.close();
			return BX.PreventDefault(e || window.event);
		}
	});
	this.oViewEventDialog = D;
};

JCEC.prototype.ShowViewEventPopup = function(oEvent)
{
	if (!this.oViewEventDialog)
		this.CreateViewEventPopup();

	var
		_this = this,
		D = this.oViewEventDialog,
		eventId = oEvent.ID;

	BX.addCustomEvent("OnUCFeedChanged", BX.proxy(function(){this.AdjustOverlay(this.oViewEventDialog);}, this));

	D.CAL.oEvent = oEvent;
	BX.ajax.get(
		this.actionUrl,
		this.GetReqData('get_view_event_dialog',
			{
				event_id : eventId,
				js_id: this.id,
				section_name: this.oSections[oEvent.SECT_ID] ? this.oSections[oEvent.SECT_ID].NAME : '',
				from_ts: BX.date.getServerTimestamp(oEvent.DT_FROM_TS)
			}),
		function(html)
		{
			D.CAL.DOM.content.innerHTML = html;
			D.CAL.DOM.pTabs = BX(_this.id + '_viewev_tabs');
			_this.ChargePopupTabs(D, _this.id + 'view-tab-');
			_this.oViewEventDialog.show();

			D.CAL.DOM.TITLE.innerHTML = EC_MESS.ViewingEvent + ': ' + BX.util.htmlspecialchars(oEvent.NAME);

			// Hide edit & delete links for read only events
			if (_this.bIntranet && _this.Event.IsHost(oEvent) && _this.Event.CanDo(oEvent, 'edit'))
			{
				D.CAL.DOM.delBut.style.display = "";
				D.CAL.DOM.editBut.style.display = "";
			}
			else if (_this.bIntranet && _this.Event.IsAttendee(oEvent))
			{
				D.CAL.DOM.delBut.style.display = "none";
				D.CAL.DOM.editBut.style.display = "none";
			}
			else
			{
				if (_this.Event.CanDo(oEvent, 'edit'))
				{
					D.CAL.DOM.delBut.style.display = "";
					D.CAL.DOM.editBut.style.display = "";
				}
				else
				{
					D.CAL.DOM.delBut.style.display = "none";
					D.CAL.DOM.editBut.style.display = "none";
				}
			}

			BX.viewElementBind(
				'bx-cal-view-files-' + _this.id + eventId,
				{showTitle: true},
				function(node){
					return BX.type.isElementNode(node) && (node.getAttribute('data-bx-viewer') || node.getAttribute('data-bx-image'));
				}
			);
		}
	);
};

JCEC.prototype.AdjustOverlay = function(popup)
{
	setTimeout(function(){
		if (popup && popup.overlay && popup.resizeOverlay)
			popup.resizeOverlay();
	}, 200);
};

JCEC.prototype.CloseViewDialog = function(bClosePopup)
{
	BX.removeCustomEvent("OnUCFeedChanged", BX.proxy(function(){this.AdjustOverlay(this.oViewEventDialog);}, this));
	if (bClosePopup === true)
		this.oViewEventDialog.close();
};

// # # #  #  #  # EDIT CALENDAR DIALOG # # #  #  #  #
JCEC.prototype.CreateSectDialog = function()
{
	var
		pTitle = BX.create("span", {html: EC_MESS.NewCalenTitle}),
		_this = this;

	var D = new BX.PopupWindow("BXCSection" + this.id, null, {
		overlay: {opacity: 10},
		autoHide: false,
		closeByEsc : true,
		zIndex: -100,
		offsetLeft: 0,
		offsetTop: 0,
		draggable: true,
		titleBar: {content: pTitle},
		closeIcon: {right : "12px", top : "10px"},
		className: "bxc-popup-tabed bxc-popup-window",
		buttons: [
			new BX.PopupWindowButton({
				text: EC_MESS.DelSect,
				id: this.id + '_bxec_cal_del_but',
				className: "bxec-popup-link-icon bxec-popup-del-ex",
				events: {click : function(){
					if (_this.DeleteSection(D.CAL.oSect))
						_this.CloseSectDialog(true);
				}}
			}),
			new BX.PopupWindowButton({
				text: EC_MESS.Save,
				className: "popup-window-button-accept",
				events: {click : function(){if (_this.SaveSection()){_this.CloseSectDialog(true);}}}
			}),
			new BX.PopupWindowButtonLink({
				text: EC_MESS.Close,
				className: "popup-window-button-link-cancel",
				events: {click : function(){_this.CloseSectDialog(true);}}
			})
		],
		content: BX('bxec_sect_d_' + this.id),
		events: {}
	});

	BX.addCustomEvent(D, 'onPopupClose', BX.proxy(this.CloseSectDialog, this));

	D.CAL = {
		DOM: {
			Title: pTitle,
			pTabs: BX(this.id + '_editsect_tabs'),
			Name: BX(this.id + '_edcal_name'),
			Desc: BX(this.id + '_edcal_desc'),
			ExpAllow: BX(this.id + '_bxec_cal_exp_allow'),
			delBut: BX(this.id + '_bxec_cal_del_but')
		}
	};

	D.CAL.Access = new ECCalendarAccess({
		bind: 'calendar_section',
		GetAccessName: BX.proxy(this.GetAccessName, this),
		pCont: BX(this.id + 'access-values-cont'),
		pLink: BX(this.id + 'access-link')
	});

	D.CAL.ColorControl = this.InitColorDialogControl('sect', function(color, textColor){
		D.CAL.Color = color;
		D.CAL.TextColor = textColor;
	});

	if (this.arConfig.bExchange && this.Personal())
		D.CAL.DOM.Exch = BX(this.id + '_bxec_cal_exch');

	this.ChargePopupTabs(D, this.id + 'sect-tab-');
	this.oSectDialog = D;

	if (this.bUser && this.Personal())
		D.CAL.DOM.MeetingCalendarCh = BX(this.id + '_bxec_meeting_calendar');

	if (this.bSuperpose && this.Personal())
	{
		D.CAL.DOM.add2SPCont = BX(this.id + '_bxec_cal_add2sp_cont');
		D.CAL.DOM.add2SP = BX(this.id + '_bxec_cal_add2sp');
	}
	D.CAL.DOM.ExpAllow.onclick = function() {_this._AllowCalendarExportHandler(this.checked);};
}

JCEC.prototype.ShowSectionDialog = function(oSect)
{
	if (!this.oSectDialog)
		this.CreateSectDialog();

	var D = this.oSectDialog;
	D.show();
	this.SetPopupTab(0, D); // Activate first tab

	if (!oSect)
	{
		oSect = {
			PERM: {
				access:true,//this.PERM.access,
				add:true, edit:true, edit_section:true, view_full:true, view_time:true, view_title:true
			}
		};
		D.CAL.bNew = true;

		D.CAL.DOM.Title.innerHTML = EC_MESS.NewCalenTitle;
		D.CAL.DOM.delBut.style.display = 'none';

		oSect.COLOR = this.GetFreeDialogColor();

		D.CAL.DOM.ExpAllow.checked = true;
		this._AllowCalendarExportHandler(true);
		if (D.CAL.DOM.ExpSet)
			D.CAL.DOM.ExpSet.value = 'all';

		if (this.bSuperpose && this.Personal())
		{
			D.CAL.DOM.add2SP.checked = true;
			D.CAL.DOM.add2SPCont.style.display = BX.browser.IsIE() ? 'inline' : 'table-row';
		}

		if (this.arConfig.bExchange && this.Personal())
		{
			D.CAL.DOM.Exch.disabled = false;
			D.CAL.DOM.Exch.checked = true;
		}

		// Default access
		oSect.ACCESS = this.new_section_access;
	}
	else // Edit Section
	{
		if (this.arConfig.bExchange && this.Personal())
		{
			D.CAL.DOM.Exch.checked = !!oSect.IS_EXCHANGE;
			D.CAL.DOM.Exch.disabled = true;
		}

		D.CAL.bNew = false;
		D.CAL.DOM.Title.innerHTML = EC_MESS.EditCalenTitle;
		D.CAL.DOM.delBut.style.display = '';

		if (!oSect.COLOR)
			oSect.COLOR = this.arConfig.arCalColors[0];

		D.CAL.DOM.ExpAllow.checked = oSect.EXPORT || false;
		this._AllowCalendarExportHandler(oSect.EXPORT);
		if (oSect.EXPORT)
			D.CAL.DOM.ExpSet.value = oSect.EXPORT_SET || 'all';
		if (this.bSuperpose  && this.Personal())
			D.CAL.DOM.add2SPCont.style.display = 'none';
	}

	D.CAL.ColorControl.Set(oSect.COLOR, oSect.TEXT_COLOR);

	// Access
	this.ShowPopupTab(D.CAL.Tabs[1], oSect.PERM.access);
	if (oSect.PERM.access)
	{
		if (this.type == 'user' && this.Personal() && oSect.ACCESS['U' + this.ownerId])
			delete oSect.ACCESS['U' + this.ownerId];
		else if (this.type == 'group' && oSect.ACCESS['SG' + this.ownerId + '_A'])
			delete oSect.ACCESS['SG' + this.ownerId + '_A'];

		D.CAL.Access.SetSelected(oSect.ACCESS);
	}

	D.CAL.oSect = oSect;
	this.bEditCalDialogOver = false;

	if (this.bUser && this.Personal())
		D.CAL.DOM.MeetingCalendarCh.checked = (!D.CAL.bNew && this.userSettings.meetSection == oSect.ID);

	var _this = this;
	D.CAL.DOM.Name.value = oSect.NAME || '';
	D.CAL.DOM.Desc.value = oSect.DESCRIPTION || '';

	BX.focus(D.CAL.DOM.Name);
}

JCEC.prototype.CloseSectDialog = function(bClosePopup)
{
	if (bClosePopup === true)
		this.oSectDialog.close();
}

JCEC.prototype._AllowCalendarExportHandler = function(bAllow)
{
	if (!this.oSectDialog.CAL.DOM.ExpDiv)
		this.oSectDialog.CAL.DOM.ExpDiv = BX(this.id + '_bxec_calen_exp_div');
	if (!this.oSectDialog.CAL.DOM.ExpSet && bAllow)
		this.oSectDialog.CAL.DOM.ExpSet = BX(this.id + '_bxec_calen_exp_set');
	this.oSectDialog.CAL.DOM.ExpDiv.style.display = bAllow ? 'block' : 'none';
}

// # # #  #  #  # Export Calendar Dialog # # #  #  #  #
JCEC.prototype.CreateExportDialog = function()
{
	var _this = this;
	var pTitle = BX.create("span");
	var D = new BX.PopupWindow("BXCExportDialog" + this.id, null, {
		overlay: {opacity: 10},
		autoHide: false,
		closeByEsc : true,
		zIndex: -100,
		offsetLeft: 0,
		offsetTop: 0,
		draggable: true,
		titleBar: {content: pTitle},
		closeIcon: {right : "12px", top : "10px"},
		className: "bxc-popup-window",
		buttons: [
			new BX.PopupWindowButton({
				text: EC_MESS.Close,
				className: "popup-window-button-accept",
				events: {click : function(){_this.CloseExportDialog(true);}}
			})
		],
		content: BX('bxec_excal_' + this.id)
	});

	BX.addCustomEvent(D, 'onPopupClose', BX.proxy(this.CloseExportDialog, this));

	D.CAL = {
		DOM: {
			Title: pTitle,
			Link: BX(this.id + '_excal_link'),
			NoticeLink: BX(this.id + '_excal_link_outlook'),
			Text: BX(this.id + '_excal_text'),
			Warn: BX(this.id + '_excal_warning')
		}
	};

	D.CAL.DOM.NoticeLink.onclick = function(){this.parentNode.className = "";};
	this.oExportDialog = D;
}

JCEC.prototype.ShowExportDialog = function(oCalen)
{
	if (oCalen && oCalen.EXPORT && !oCalen.EXPORT.ALLOW)
		return;

	if (!this.oExportDialog)
		this.CreateExportDialog();

	var D = this.oExportDialog;
	D.show();

	D.CAL.DOM.NoticeLink.parentNode.className = "bxec-excal-notice-hide"; // Hide help
	D.CAL.DOM.Warn.className = 'bxec-export-warning-hidden';

	// Create link
	var link = this.path;
	link += (link.indexOf('?') >= 0) ? '&' : '?';

	if (oCalen)
	{
		D.CAL.DOM.Title.innerHTML = EC_MESS.ExpDialTitle;
		D.CAL.DOM.Text.innerHTML = EC_MESS.ExpText;
		link += 'action=export' + oCalen.EXPORT.LINK;
	}

	var webCalLink = 'webcal' + link.substr(link.indexOf('://'));
	D.CAL.DOM.Link.onclick = function(e) {window.location.href = webCalLink; BX.PreventDefault(e);};
	D.CAL.DOM.Link.href = link;
	D.CAL.DOM.Link.innerHTML = link;

	BX.ajax.get(link + '&check=Y', "", function(result)
	{
		setTimeout(function()
		{
			BX.closeWait(D.CAL.DOM.Title);
			if (!result || result.length <= 0 || result.toUpperCase().indexOf('BEGIN:VCALENDAR') == -1)
				D.CAL.DOM.Warn.className = 'bxec-export-warning';
		}, 300);
	});
}

JCEC.prototype.CloseExportDialog = function(bClosePopup)
{
	if (bClosePopup === true)
		this.oExportDialog.close();
};

// # # #  #  #  # Superpose Calendar Dialog # # #  #  #  #
JCEC.prototype.CreateSuperposeDialog = function()
{
	var _this = this;
	var D = new BX.PopupWindow("BXCSuperpose" + this.id, null, {
		overlay: {opacity: 10},
		autoHide: false,
		closeByEsc : true,
		zIndex: -100,
		offsetLeft: 0,
		offsetTop: 0,
		draggable: true,
		titleBar: {content: BX.create("span", {html: EC_MESS.SPCalendars})},
		closeIcon: { right : "12px", top : "10px"},
		className: "bxc-popup-window",
		buttons: [
			new BX.PopupWindowButton({
				text: EC_MESS.Save,
				className: "popup-window-button-accept",
				events: {click : function(){
					_this.SPD_SaveSuperposed();
					_this.CloseSuperposeDialog(true);
				}}
			}),
			new BX.PopupWindowButtonLink({
				text: EC_MESS.Close,
				className: "popup-window-button-link-cancel",
				events: {click : function(){_this.CloseSuperposeDialog(true);}}
			})
		],
		content: BX('bxec_superpose_' + this.id),
		events: {}
	});

	BX.addCustomEvent(D, 'onPopupClose', BX.proxy(this.CloseSuperposeDialog, this));

	D.CAL = {
		DOM: {
			UserCntInner: BX('bxec_sp_type_user_cont_' + this.id),
			GroupCnt: BX('bxec_sp_type_group_' + this.id),
			GroupCntInner: BX('bxec_sp_type_group_cont_' + this.id),
			CommonCnt: BX('bxec_sp_type_common_' + this.id),
			DelAllUsersLink: BX('bxec_sp_dell_all_sp_' + this.id),
			UserSearchCont: BX(this.id + '_sp_user_search_input_cont'),
			NotFoundNotice: BX(this.id + '_sp_user_nf_notice'),
			arCat : {}
		},
		arSect: {},
		arGroups: {},
		arCals: {},
		curTrackedUsers: {}
	};

	BX.addCustomEvent(window, "onUserSelectorOnChange", function(arUsers){D.CAL.curTrackedUsers = arUsers;});

	D.CAL.DOM.AddUsersLinkCont = BX(this.id + '_user_control_link_sp');

	D.CAL.DOM.AddUsersLinkCont.onclick = function(e)
	{
		if (BX.PopupMenu && BX.PopupMenu.currentItem)
			BX.PopupMenu.currentItem.popupWindow.close();

		if(!e)
			e = window.event;

		if (!D.CAL.DOM.SelectUserPopup)
		{
			D.CAL.DOM.SelectUserPopup = BX.PopupWindowManager.create("bxc-user-popup-sp", D.CAL.DOM.AddUsersLinkCont, {
				offsetTop : 1,
				autoHide : true,
				closeByEsc : true,
				content : BX("BXCalUserSelectSP_selector_content"),
				className: 'bxc-popup-user-select',
				buttons: [
					new BX.PopupWindowButton({
						text: EC_MESS.Add,
						events: {click : function()
						{
							D.CAL.DOM.SelectUserPopup.close();

							var users = [], i;
							for (i in D.CAL.curTrackedUsers)
								if (D.CAL.curTrackedUsers[i] && i > 0 && parseInt(i) == i)
									users.push(i);

							_this.Request({
								postData: _this.GetReqData('spcal_user_cals', {users : users}),
								errorText: EC_MESS.CalenSaveErr,
								handler: function(oRes)
								{
									if (oRes)
									{
										if (oRes.sections)
										{
											_this.SPD_BuildSections(oRes.sections, true);
										}
										else
										{
											_this.oSupDialog.CAL.DOM.NotFoundNotice.style.visibility = 'visible';
											setTimeout(function(){_this.oSupDialog.CAL.DOM.NotFoundNotice.style.visibility = 'hidden';}, 4000);
										}
										return true;
									}
									return false;
								}
							});
						}}
					}),
					new BX.PopupWindowButtonLink({
						text: EC_MESS.Close,
						className: "popup-window-button-link-cancel",
						events: {click : function(){D.CAL.DOM.SelectUserPopup.close();}}
					})
				]
			});
		}

		D.CAL.curTrackedUsers = {};
		D.CAL.DOM.SelectUserPopup.show();
		BX.PreventDefault(e);
	};

	this.oSupDialog = D;
};

JCEC.prototype.SPD_BuildSections = function(arSections, bRegister)
{
	var
		_this = this,
		D = this.oSupDialog,
		i, oSect, pCatCont, pItem, key, catTitle, id;

	BX.cleanNode(D.CAL.DOM.UserCntInner);
	BX.cleanNode(D.CAL.DOM.GroupCntInner);
	BX.cleanNode(D.CAL.DOM.CommonCnt);

	for (i in arSections)
	{
		oSect = arSections[i];

		if (!oSect.ID)
			return;

		if (oSect.CAL_TYPE == 'user')
		{
			pCatCont = D.CAL.DOM.UserCntInner;
		}
		else if(oSect.CAL_TYPE == 'group')
		{
			pCatCont = D.CAL.DOM.GroupCntInner;
			D.CAL.DOM.GroupCnt.style.display = "block";
		}
		else
		{
			pCatCont = D.CAL.DOM.CommonCnt;
			D.CAL.DOM.CommonCnt.style.display = "block";
		}

		key = oSect.CAL_TYPE + oSect.OWNER_ID;
		if (!D.CAL.DOM.arCat[key] || !BX.isNodeInDom(D.CAL.DOM.arCat[key].pCat))
		{
			if (oSect.CAL_TYPE == 'user' || oSect.CAL_TYPE == 'group')
				catTitle = oSect.OWNER_NAME;
			else
				catTitle = oSect.TYPE_NAME;

			pCat = pCatCont.appendChild(BX.create("DIV", {props: {className: "bxc-spd-cat"}}));
			pCatTitle = pCat.appendChild(BX.create("DIV", {props: {className: "bxc-spd-cat-title"}, html: '<span class="bxc-spd-cat-plus"></span><span class="bxc-spd-cat-title-inner">' + BX.util.htmlspecialchars(catTitle) + '</span>'}));
			pCatSections = pCat.appendChild(BX.create("DIV", {props: {className: "bxc-spd-cat-sections"}}));
			pCatTitle.onclick = function(){BX.toggleClass(this.parentNode, "bxc-spd-cat-collapsed")}

			// Add link for del user from tracking users list
			if (oSect.CAL_TYPE == 'user' && oSect.OWNER_ID != this.userId)
			{
				pCatTitle.appendChild(BX.create("A", {props: {href: "javascript:void(0);", className: "bxc-spd-del-cat", title: EC_MESS.DeleteDynSPGroupTitle}, text: EC_MESS.DeleteDynSPGroup, events: {click: function(e){_this.SPD_DelTrackingUser(this.getAttribute('bx-data'), this); return BX.PreventDefault(e)}}})).setAttribute('bx-data', oSect.OWNER_ID);
			}

			D.CAL.DOM.arCat[key] = {
				pCat : pCat,
				pTitle : pCatTitle,
				pSections : pCatSections
			};
		}

		id = this.id + "spd-sect" + oSect.ID;
		pItem = BX.create("DIV", {props: {className: "bxc-spd-sect-cont"}});
		pCh = pItem.appendChild(BX.create("SPAN", {props: {className: "bxc-spd-sect-check"}})).appendChild(BX.create("INPUT", {props: {type: "checkbox", id: id}}));
		pLabel = pItem.appendChild(BX.create("SPAN", {props: {className: "bxc-spd-sect-label"}, html: '<label for="' + id + '"><span>' + BX.util.htmlspecialchars(oSect.NAME) + '</span></label>'}));

		D.CAL.DOM.arCat[key].pSections.appendChild(pItem);
		D.CAL.arSect[oSect.ID] = {pCh: pCh, pItem: pItem, oSect: oSect};

		if (bRegister)
			this.arSPSections.push(oSect);
	}
};

JCEC.prototype.SPD_SaveSuperposed = function()
{
	var
		i, item;

	for (i in this.oSupDialog.CAL.arSect)
	{
		item = this.oSupDialog.CAL.arSect[i];
		if (item.pCh.checked)
		{
			// Section already added to superposed
			if(this.arSectionsInd[i] && this.arSections[this.arSectionsInd[i]])
			{
				this.arSections[this.arSectionsInd[i]].SUPERPOSED = true;
			}
			else if(!this.arSectionsInd[i])
			{
				item.oSect.SUPERPOSED = true;
				this.arSections.push(item.oSect);
				this.arSectionsInd[item.oSect.ID] = this.arSections.length - 1;
			}
		}
		else
		{
			if (this.arSectionsInd[i] && this.arSections[this.arSectionsInd[i]])
			{
				this.arSections[this.arSectionsInd[i]].SUPERPOSED = false;
			}
		}
	}
	this.SetSuperposed();
};

JCEC.prototype.SPD_DelTrackingUser = function(userId, pLink)
{
	var pCont = BX.findParent(pLink, {className: 'bxc-spd-cat'});
	if (pCont)
		pCont.parentNode.removeChild(pCont);

	var i, item;
	for (i in this.oSupDialog.CAL.arSect)
	{
		if (this.oSupDialog.CAL.arSect.hasOwnProperty(i))
		{
			item = this.oSupDialog.CAL.arSect[i];
			if (item && item.oSect && item.oSect.CAL_TYPE=='user' && item.oSect.OWNER_ID == userId)
				item.pCh.checked = false;
		}
	}
	this.SPD_SaveSuperposed();
	this.arSPSections = null;

	this.Request({
		postData: this.GetReqData('spcal_del_user', {userId: parseInt(userId)}),
		handler: function(oRes)
		{
			if (oRes)
				return true;
		}
	});
}

JCEC.prototype.ShowSuperposeDialog = function()
{
	var _this = this;
	if (!this.arSPSections)
	{
		return this.Request({
			getData: _this.GetReqData('get_superposed'),
			handler: function(oRes)
			{
				if (oRes)
				{
					_this.arSPSections = oRes.sections || [];
					return _this.ShowSuperposeDialog();
				}
				return false;
			}
		});
	}

	if (!this.oSupDialog)
		this.CreateSuperposeDialog();

	// All sections with checkboxes and groups and categories builds here
	if (this.arSPSections)
		this.SPD_BuildSections(this.arSPSections, false);

	var D = this.oSupDialog;
	D.show();

	for (var i = 0, l = l = this.arSections.length; i < l; i++)
	{
		if (this.arSections[i].ID && D.CAL.arSect[this.arSections[i].ID])
			D.CAL.arSect[this.arSections[i].ID].pCh.checked = !!this.arSections[i].SUPERPOSED;
	}
}

JCEC.prototype.CloseSuperposeDialog = function(bClosePopup)
{
	if (bClosePopup === true)
		this.oSupDialog.close();
};

JCEC.prototype.BuildSectionSelect = function(oSel, value)
{
	oSel.options.length = 0;
	var i, opt, el, selected;
	oSel.parentNode.className = 'bxec-cal-sel-cel';
	for (i = 0; i < this.arSections.length; i++)
	{
		el = this.arSections[i];
		if (el.PERM.edit_section && this.IsCurrentViewSect(el))
		{
			selected = value == el.ID;
			opt = new Option(el.NAME, el.ID, selected, selected);
			oSel.options.add(opt);
			if(!BX.browser.IsIE())
				opt.style.backgroundColor = el.COLOR;
		}
	}

	if (oSel.options.length <= 0)
		oSel.parentNode.className = 'bxec-cal-sel-cel-empty';
};

JCEC.prototype.IsCurrentViewSect = function(el)
{
	return el.CAL_TYPE == this.type && el.OWNER_ID == this.ownerId;
};

// # # #  #  #  # User Settings Dialog # # #  #  #  #
JCEC.prototype.CreateSetDialog = function()
{
	var _this = this;
	var D = new BX.PopupWindow("BXCSettings" + this.id, null, {
		overlay: {opacity: 10},
		autoHide: false,
		closeByEsc : true,
		zIndex: -100,
		offsetLeft: 0,
		offsetTop: 0,
		draggable: true,
		titleBar: {content: BX.create("span", {props: {className: 'bxec-popup-title', id: this.id + '_viewev_title'}, html: this.PERM.access ? EC_MESS.Settings : EC_MESS.UserSettings})},
		closeIcon: {right : "12px", top : "10px"},
		className: 'bxc-popup-tabed bxc-popup-window',
		buttons: [
			new BX.PopupWindowButton({
				text: EC_MESS.Save,
				className: "popup-window-button-accept",
				events: {click : function(){
					_this.CloseSetDialog(true);
					_this.SaveSettings();
				}}
			}),
			new BX.PopupWindowButtonLink({
				text: EC_MESS.Close,
				className: "popup-window-button-link-cancel",
				events: {click : function(){_this.CloseSetDialog(true);}}
			})
		],
		content: BX('bxec_uset_' + this.id)
	});

	BX.addCustomEvent(D, 'onPopupClose', BX.proxy(this.CloseSetDialog, this));

	D.CAL = {
		inPersonal : this.type == 'user' && this.ownerId == this.userId,
		DOM: {
			pTabs: BX(this.id + '_set_tabs'),
			ShowMuted: BX(this.id + '_show_muted')
		}
	};

	if (D.CAL.inPersonal)
	{
		D.CAL.DOM.SectSelect = BX(this.id + '_set_sect_sel');
		D.CAL.DOM.Blink = BX(this.id + '_uset_blink');
		D.CAL.DOM.ShowBanner = BX(this.id + '_show_banner');
		D.CAL.DOM.ShowDeclined = BX(this.id + '_show_declined');
	}


	if (this.PERM.access)
	{
		D.CAL.Access = new ECCalendarAccess({
			bind: 'calendar_type',
			GetAccessName: BX.proxy(this.GetAccessName, this),
			pCont: BX(this.id + 'type-access-values-cont'),
			pLink: BX(this.id + 'type-access-link')
		});

		D.CAL.DOM.WorkTimeStart = BX(this.id + 'work_time_start');
		D.CAL.DOM.WorkTimeEnd = BX(this.id + 'work_time_end');
		D.CAL.DOM.WeekHolidays = BX(this.id + 'week_holidays');
		D.CAL.DOM.YearHolidays = BX(this.id + 'year_holidays');
		D.CAL.DOM.YearWorkdays = BX(this.id + 'year_workdays');
		D.CAL.DOM.WeekStart = BX(this.id + 'week_start');
	}

	if (this.bSuperpose)
	{
		D.CAL.DOM.ManageSuperpose = BX(this.id + '-set-manage-sp');
		D.CAL.DOM.ManageSuperpose.onclick = function(){_this.ShowSuperposeDialog()};
	}

	D.CAL.DOM.ManageCalDav = BX(this.id + '_manage_caldav');
	if (D.CAL.DOM.ManageCalDav)
		D.CAL.DOM.ManageCalDav.onclick = function(){_this.ShowExternalDialog()};

	D.CAL.DOM.UsetClearAll = BX(this.id + '_uset_clear');
	if (D.CAL.DOM.UsetClearAll)
		D.CAL.DOM.UsetClearAll.onclick = function()
		{
			if (confirm(EC_MESS.ClearUserSetConf))
			{
				_this.CloseSetDialog(true);
				_this.ClearPersonalSettings();
			}
		};

	this.ChargePopupTabs(D, this.id + 'set-tab-');

	if (!this.PERM.access && D.CAL.DOM.pTabs)
		D.CAL.DOM.pTabs.style.display = 'none';

	this.oSetDialog = D;
}

JCEC.prototype.ShowSetDialog = function()
{
	if (!this.oSetDialog)
		this.CreateSetDialog();

	var D = this.oSetDialog;
	D.show();
	this.SetPopupTab(0, D); // Activate first tab

	// Set personal user settings

	if (D.CAL.inPersonal)
	{
		D.CAL.DOM.SectSelect.options.length = 0;
		var i, l = this.arSections.length, opt, el, sel = !this.userSettings.meetSection;
		D.CAL.DOM.SectSelect.options.add(new Option(' - ' + EC_MESS.FirstInList + ' - ', 0, sel, sel));
		for (i = 0; i < l; i++)
		{
			el = this.arSections[i];
			if (el.CAL_TYPE == 'user' && el.OWNER_ID == this.userId)
			{
				sel = this.userSettings.meetSection == el.ID;
				opt = new Option(el.NAME, el.ID, sel, sel);
				opt.style.backgroundColor = el.COLOR;
				D.CAL.DOM.SectSelect.options.add(opt);
			}
		}

		D.CAL.DOM.Blink.checked = !!this.userSettings.blink;
		D.CAL.DOM.ShowBanner.checked = !!this.userSettings.showBanner;
		D.CAL.DOM.ShowDeclined.checked = !!this.userSettings.showDeclined;
	}
	D.CAL.DOM.ShowMuted.checked = !!this.userSettings.showMuted;

	if (this.PERM.access)
	{
		// Set access for calendar type
		D.CAL.Access.SetSelected(this.typeAccess);
		D.CAL.DOM.WorkTimeStart.value = this.settings.work_time_start;
		D.CAL.DOM.WorkTimeEnd.value = this.settings.work_time_end;
		for(var i = 0, l = D.CAL.DOM.WeekHolidays.options.length; i < l; i++)
			D.CAL.DOM.WeekHolidays.options[i].selected = BX.util.in_array(D.CAL.DOM.WeekHolidays.options[i].value, this.settings.week_holidays);
		D.CAL.DOM.YearHolidays.value = this.settings.year_holidays;
		D.CAL.DOM.YearWorkdays.value = this.settings.year_workdays;
		D.CAL.DOM.WeekStart.value = this.settings.week_start;
	}
};

JCEC.prototype.CloseSetDialog  = function(bClosePopup)
{
	if (bClosePopup === true)
		this.oSetDialog.close();
};

// # # #  #  #  # External Calendars Dialog # # #  #  #  #
JCEC.prototype.CreateExternalDialog = function()
{
	var _this = this;
	var D = new BX.PopupWindow("BXCExternalDialog" + this.id, null, {
		overlay: {opacity: 10},
		autoHide: false,
		closeByEsc : true,
		zIndex: -95,
		offsetLeft: 0,
		offsetTop: 0,
		draggable: true,
		titleBar: {content: BX.create("span", {html: EC_MESS.CalDavDialogTitle})},
		closeIcon: {right : "12px", top : "10px"},
		className: "bxc-popup-window",
		buttons: [
			new BX.PopupWindowButton({
				text: EC_MESS.AddCalDav,
				className: "bxec-popup-link-icon bxec-popup-add-ex",
				events: {click : function()
				{
					var i = D.CAL.arConnections.length;
					D.CAL.arConnections.push({bNew: true, name: EC_MESS.NewExCalendar, link: '', user_name: ''});
					_this.ExD_DisplayConnection(D.CAL.arConnections[i], i);
					_this.ExD_EditConnection(i);
				}}
			}),
			new BX.PopupWindowButton({
				text: EC_MESS.Save,
				className: "popup-window-button-accept",
				events: {click : function(){
					if (D.CAL.bLockClosing)
						return alert(EC_MESS.CalDavConWait);

					if (D.CAL.curEditedConInd !== false && D.CAL.arConnections[D.CAL.curEditedConInd])
						_this.ExD_SaveConnectionData(D.CAL.curEditedConInd);

					_this.arConnections = D.CAL.arConnections;
					D.CAL.bLockClosing = true;

					_this.SaveConnections(
						function(res)
						{
							D.CAL.bLockClosing = false;
							if (res)
							{
								_this.CloseExternalDialog(true);
								window.location = window.location;
							}
						},
						function(){D.CAL.bLockClosing = false;}
					);
				}}
			}),
			new BX.PopupWindowButtonLink({
				text: EC_MESS.Close,
				className: "popup-window-button-link-cancel",
				events: {click : function(){_this.CloseExternalDialog(true);}}
			})
		],
		content: BX('bxec_cdav_' + this.id)
	});

	BX.addCustomEvent(D, 'onPopupClose', BX.proxy(this.CloseExternalDialog, this));

	D.CAL = {
		DOM: {
			List: BX(this.id + '_bxec_dav_list'),
			EditConDiv: BX(this.id + '_bxec_dav_new'),
			EditName: BX(this.id + '_bxec_dav_name'),
			EditLink: BX(this.id + '_bxec_dav_link'),
			UserName: BX(this.id + '_bxec_dav_username'),
			Pass: BX(this.id + '_bxec_dav_password')
		}
	};

	this.oExternalDialog = D;
}

JCEC.prototype.ShowExternalDialog = function()
{
	if (!this.oExternalDialog)
		this.CreateExternalDialog();

	var D = this.oExternalDialog, i;
	D.show();
	D.CAL.curEditedConInd = false;

	BX.cleanNode(D.CAL.DOM.List);
	D.CAL.arConnections = BX.clone(this.arConnections);
	for (i = 0; i < this.arConnections.length; i++)
		this.ExD_DisplayConnection(D.CAL.arConnections[i], i);

	if (this.arConnections.length == 0) // No connections - open form to add new connection
	{
		i = D.CAL.arConnections.length;
		D.CAL.arConnections.push({bNew: true, name: EC_MESS.NewExCalendar, link: '', user_name: ''});
		this.ExD_DisplayConnection(D.CAL.arConnections[i], i);
		this.ExD_EditConnection(i);
	}
	else if (this.arConnections.length == 1)
	{
		this.ExD_EditConnection(0);
	}
};

JCEC.prototype.CloseExternalDialog = function(bClosePopup)
{
	if (bClosePopup === true)
		this.oExternalDialog.close();
};

JCEC.prototype.ExD_EditConnection = function(ind)
{
	var
		D = this.oExternalDialog,
		con = D.CAL.arConnections[ind];

	for(var _ind in D.CAL.arConnections)
	{
		if (D.CAL.arConnections[_ind] && _ind != ind && BX.hasClass(D.CAL.arConnections[_ind].pConDiv, "bxec-dav-item-edited"))
		{
			if (D.CAL.DOM.EditConDiv.parentNode == D.CAL.arConnections[_ind].pConDiv)
				this.ExD_SaveConnectionData(_ind);
			BX.removeClass(D.CAL.arConnections[_ind].pConDiv, "bxec-dav-item-edited");
		}
	}

	if (con.del || D.CAL.curEditedConInd === ind)
		return;

	if (D.CAL.curEditedConInd !== false && D.CAL.arConnections[D.CAL.curEditedConInd])
	{
		this.ExD_SaveConnectionData(D.CAL.curEditedConInd);
		BX.removeClass(D.CAL.arConnections[D.CAL.curEditedConInd].pConDiv, "bxec-dav-item-edited");
	}

	D.CAL.curEditedConInd = ind;

	D.CAL.DOM.EditName.value = con.name;
	D.CAL.DOM.EditLink.value = con.link;
	D.CAL.DOM.UserName.value = con.user_name;

	if (con.id > 0)
		this.ExD_CheckPass();
	else
		D.CAL.DOM.Pass.value = '';

	setTimeout(function(){BX.focus(D.CAL.DOM.EditLink);}, 100);

	D.CAL.DOM.EditName.onkeyup = D.CAL.DOM.EditName.onfocus = D.CAL.DOM.EditName.onblur = function()
	{
		if (D.CAL.changeNameTimeout)
			clearTimeout(D.CAL.changeNameTimeout);

		D.CAL.changeNameTimeout = setTimeout(function(){
			if (D.CAL.curEditedConInd !== false && D.CAL.arConnections[D.CAL.curEditedConInd])
			{
				var val = D.CAL.DOM.EditName.value;
				if (val.length > 25)
					val = val.substr(0, 23) + "...";
				D.CAL.arConnections[D.CAL.curEditedConInd].pText.innerHTML = BX.util.htmlspecialchars(val);
				D.CAL.arConnections[D.CAL.curEditedConInd].pText.title = D.CAL.DOM.EditName.value;
			}
		}, 50);
	};

	con.pConDiv.appendChild(D.CAL.DOM.EditConDiv);
	BX.addClass(con.pConDiv, "bxec-dav-item-edited");
};

JCEC.prototype.ExD_DisplayConnection = function(con, ind)
{
	var
		_this = this,
		D = this.oExternalDialog,
		pConDiv = D.CAL.DOM.List.appendChild(BX.create("DIV", {props: {id: 'bxec_dav_con_' + ind, className: 'bxec-dav-item' + (ind % 2 == 0 ? '' : ' bxec-dav-item-1')}})),
		pTitle = pConDiv.appendChild(BX.create("DIV", {props: {className: 'bxec-dav-item-name'}})),
		pStatus = pTitle.appendChild(BX.create("IMG", {props: {src: "/bitrix/images/1.gif", className: 'bxec-dav-item-status'}})),
		pText = pTitle.appendChild(BX.create("SPAN", {text: con.name})),
		pCount = pTitle.appendChild(BX.create("SPAN", {text: ''})),
		pEdit = pTitle.appendChild(BX.create("A", {props: {href: 'javascript: void(0);', className: 'bxec-dav-edit'}, text: EC_MESS.CalDavEdit})),
		pCol = pTitle.appendChild(BX.create("A", {props: {href: 'javascript: void(0);', className: 'bxec-dav-col'}, text: EC_MESS.CalDavCollapse})),
		pDel = pTitle.appendChild(BX.create("A", {props: {href: 'javascript: void(0);', className: 'bxec-dav-del'}, text: EC_MESS.CalDavDel})),
		pRest = pTitle.appendChild(BX.create("A", {props: {href: 'javascript: void(0);', className: 'bxec-dav-rest'}, text: EC_MESS.CalDavRestore})),
		pDelCalendars = pTitle.appendChild(BX.create("DIV", {props: {className: 'bxec-dav-del-cal'}, children: [BX.create("LABEL", {props: {htmlFor: 'bxec_dav_con_del_cal_' + ind}, text: EC_MESS.DelConCalendars})]})),
		pDelCalCh = pDelCalendars.appendChild(BX.create("INPUT", {props: {type: 'checkbox', id: 'bxec_dav_con_del_cal_' + ind, checked: true}}));

	if (con.id > 0)
	{
		var cn = 'bxec-dav-item-status', title;
		if (con.last_result.indexOf("[200]") >= 0)
		{
			cn += ' bxec-dav-ok';
			title = EC_MESS.SyncOk + '. ' + EC_MESS.SyncDate + ': ' + con.sync_date;
		}
		else
		{
			cn += ' bxec-dav-error';
			title = EC_MESS.SyncError + ': ' + con.last_result + '. '+ EC_MESS.SyncDate + ': ' + con.sync_date;
		}
		pStatus.className = cn;
		pStatus.title = title;

		var i, l = this.arSections.length, count = 0;
		for (i = 0; i < l; i++)
			if (this.arSections[i] && this.arSections[i].CAL_DAV_CON == con.id)
				count++;

		pCount.innerHTML = " (" + count + ")";
	}

	pConDiv.onmouseover = function(){BX.addClass(this, "bxec-dav-item-over");};
	pConDiv.onmouseout = function(){BX.removeClass(this, "bxec-dav-item-over");};

	pConDiv.onclick = function()
	{
		ind = parseInt(this.id.substr('bxec_dav_con_'.length));
		_this.ExD_EditConnection(ind);
	};

	pCol.onclick = function(e)
	{
		var ind = parseInt(this.parentNode.parentNode.id.substr('bxec_dav_con_'.length));
		if (D.CAL.arConnections[ind])
		{
			_this.ExD_SaveConnectionData(ind);
			BX.removeClass(D.CAL.arConnections[ind].pConDiv, "bxec-dav-item-edited");
			_this.oExternalDialog.curEditedConInd = false;
		}
		return BX.PreventDefault(e);
	};

	pDel.onclick = function(e)
	{
		var ind = parseInt(this.parentNode.parentNode.id.substr('bxec_dav_con_'.length));
		if (D.CAL.arConnections[ind])
		{
			D.CAL.arConnections[ind].del = true;
			BX.removeClass(D.CAL.arConnections[ind].pConDiv, "bxec-dav-item-edited");
			BX.addClass(D.CAL.arConnections[ind].pConDiv, "bxec-dav-item-deleted");
			_this.ExD_SaveConnectionData(ind);
			_this.oExternalDialog.curEditedConInd = false;
		}

		return BX.PreventDefault(e);
	};

	pRest.onclick = function(e)
	{
		var ind = parseInt(this.parentNode.parentNode.id.substr('bxec_dav_con_'.length));
		if (D.CAL.arConnections[ind])
		{
			D.CAL.arConnections[ind].del = false;
			BX.removeClass(D.CAL.arConnections[ind].pConDiv, "bxec-dav-item-deleted");
		}
		return BX.PreventDefault(e);
	};

	pEdit.onclick = function(e)
	{
		var ind = parseInt(this.parentNode.parentNode.id.substr('bxec_dav_con_'.length));
		if (D.CAL.arConnections[ind])
		{
			for(var _ind in D.CAL.arConnections)
			{
				if (D.CAL.arConnections[_ind] && _ind != ind && BX.hasClass(D.CAL.arConnections[_ind].pConDiv, "bxec-dav-item-edited"))
				{
					if (D.CAL.DOM.EditConDiv.parentNode == D.CAL.arConnections[_ind].pConDiv)
						_this.ExD_SaveConnectionData(_ind);
					BX.removeClass(D.CAL.arConnections[_ind].pConDiv, "bxec-dav-item-edited");
				}
			}

			var con = D.CAL.arConnections[ind];
			BX.addClass(con.pConDiv, "bxec-dav-item-edited");
			con.pConDiv.appendChild(D.CAL.DOM.EditConDiv);
			_this.oExternalDialog.curEditedConInd = true;

			D.CAL.DOM.EditName.value = con.name;
			D.CAL.DOM.EditLink.value = con.link;
			D.CAL.DOM.UserName.value = con.user_name;

			if (con.id > 0)
				_this.ExD_CheckPass();
			else
				D.CAL.DOM.Pass.value = '';

		}
		return BX.PreventDefault(e);
	};

	con.pConDiv = pConDiv;
	con.pText = pText;
	con.pDelCalendars = pDelCalCh;
}

JCEC.prototype.ExD_SaveConnectionData = function(ind)
{
	var
		D = this.oExternalDialog,
		con = D.CAL.arConnections[ind];

	con.name = D.CAL.DOM.EditName.value;
	con.link = D.CAL.DOM.EditLink.value;
	con.user_name = D.CAL.DOM.UserName.value;
	con.pass = 'bxec_not_modify_pass';

	if (D.CAL.DOM.Pass.type.toLowerCase() == 'password' && D.CAL.DOM.Pass.title != EC_MESS.CalDavNoChange)
		con.pass = D.CAL.DOM.Pass.value;
};

JCEC.prototype.ExD_CheckPass = function()
{
	var D = this.oExternalDialog;

	if (!BX.browser.IsIE())
	{
		D.CAL.DOM.Pass.type = 'text';
		D.CAL.DOM.Pass.value = EC_MESS.CalDavNoChange;
	}
	else
	{
		D.CAL.DOM.Pass.value = '';
	}

	D.CAL.DOM.Pass.title = EC_MESS.CalDavNoChange;
	D.CAL.DOM.Pass.className = 'bxec-dav-no-change';
	D.CAL.DOM.Pass.onfocus = D.CAL.DOM.Pass.onmousedown = function()
	{
		if (!BX.browser.IsIE())
			this.type = 'password';
		this.value = '';
		this.title = '';
		this.className = '';
		this.onfocus = this.onmousedown = null;
		BX.focus(this);
	};
};

// # # #  #  #  # Mobile help Dialog # # #  #  #  #
JCEC.prototype.CreateMobileHelpDialog = function()
{
	var _this = this;
	var D = new BX.PopupWindow("BXCMobileHelp" + this.id, null, {
		overlay: {opacity: 10},
		autoHide: false,
		closeByEsc : true,
		zIndex: -100,
		offsetLeft: 0,
		offsetTop: 0,
		draggable: true,
		titleBar: {content: BX.create("span", {html: EC_MESS.MobileHelpTitle})},
		closeIcon: {right : "12px", top : "10px"},
		className: "bxc-popup-window",
		buttons: [
			new BX.PopupWindowButton({
				text: EC_MESS.Close,
				className: "popup-window-button-accept",
				events: {click : function(){_this.CloseMobileHelpDialog(true);}}
			})
		],
		content: BX('bxec_mobile_' + this.id)
	});

	BX.addCustomEvent(D, 'onPopupClose', BX.proxy(this.CloseMobileHelpDialog, this));

	D.CAL = {
		DOM: {
			iPhoneLink: BX('bxec_mob_link_iphone_' + this.id),
			macLink: BX('bxec_mob_link_mac_' + this.id),
			birdLink: BX('bxec_mob_link_bird_' + this.id),
			iPhoneAllCont: BX('bxec_mobile_iphone_all' + this.id),
			iPhoneOneCont: BX('bxec_mobile_iphone_one' + this.id),
			macCont: BX('bxec_mobile_mac_cont' + this.id),
			birdAllCont: BX('bxec_mobile_sunbird_all' + this.id),
			birdOneCont: BX('bxec_mobile_sunbird_one' + this.id)
		}
	};

	D.CAL.DOM.iPhoneLink.onclick = function()
	{
		if (D.CAL.calendarId == 'all')
		{
			if (D.CAL.biPhoneAllOpened)
			{
				D.CAL.DOM.iPhoneAllCont.style.display = 'none';
				BX.addClass(this, 'bxec-link-hidden');
			}
			else
			{
				D.CAL.DOM.iPhoneAllCont.style.display = 'block';
				BX.removeClass(this, 'bxec-link-hidden');
			}
			D.CAL.biPhoneAllOpened = !D.CAL.biPhoneAllOpened;
		}
		else
		{
			if (D.CAL.biPhoneOneOpened)
			{
				D.CAL.DOM.iPhoneOneCont.style.display = 'none';
				BX.addClass(this, 'bxec-link-hidden');
			}
			else
			{
				D.CAL.DOM.iPhoneOneCont.style.display = 'block';
				BX.removeClass(this, 'bxec-link-hidden');
			}
			D.CAL.biPhoneOneOpened = !D.CAL.biPhoneOneOpened;
		}
	};

	D.CAL.DOM.macLink.onclick = function()
	{
		if (D.CAL.bMacOpened)
		{
			D.CAL.DOM.macCont.style.display = 'none';
			BX.addClass(this, 'bxec-link-hidden');
		}
		else
		{
			D.CAL.DOM.macCont.style.display = 'block';
			BX.removeClass(this, 'bxec-link-hidden');
		}
		D.CAL.bMacOpened = !D.CAL.bMacOpened;
	};

	D.CAL.DOM.birdLink.onclick = function()
	{
		if (D.CAL.calendarId == 'all')
		{
			if (D.CAL.bbirdAllOpened)
			{
				D.CAL.DOM.birdAllCont.style.display = 'none';
				BX.addClass(this, 'bxec-link-hidden');
			}
			else
			{
				D.CAL.DOM.birdAllCont.style.display = 'block';
				BX.removeClass(this, 'bxec-link-hidden');
			}
			D.CAL.bbirdAllOpened = !D.CAL.bbirdAllOpened;
		}
		else
		{
			if (D.CAL.bbirdOneOpened)
			{
				D.CAL.DOM.birdOneCont.style.display = 'none';
				BX.addClass(this, 'bxec-link-hidden');
			}
			else
			{
				D.CAL.DOM.birdOneCont.style.display = 'block';
				BX.removeClass(this, 'bxec-link-hidden');
			}
			D.CAL.bbirdOneOpened = !D.CAL.bbirdOneOpened;
		}
	};

	this.oMobileDialog = D;
}

JCEC.prototype.ShowMobileHelpDialog = function(calendarId)
{
	if (!this.oMobileDialog)
		this.CreateMobileHelpDialog();

	var D = this.oMobileDialog;
	D.show();

	D.CAL.calendarId = calendarId;
	D.CAL.DOM.iPhoneAllCont.style.display = "none";
	D.CAL.DOM.iPhoneOneCont.style.display = "none";
	D.CAL.DOM.birdAllCont.style.display = "none";
	D.CAL.DOM.birdOneCont.style.display = "none";
	D.CAL.DOM.macCont.style.display = "none";

	BX.addClass(D.CAL.DOM.birdLink, 'bxec-link-hidden');
	BX.addClass(D.CAL.DOM.iPhoneLink, 'bxec-link-hidden');
	BX.addClass(D.CAL.DOM.macLink, 'bxec-link-hidden');

	var arLinks = [], i;
	if (calendarId == 'all')
	{
		arLinks = arLinks.concat(BX.findChildren(D.CAL.DOM.iPhoneAllCont, {tagName: 'SPAN', className: 'bxec-link'}, true));
		arLinks = arLinks.concat(BX.findChildren(D.CAL.DOM.birdAllCont, {tagName: 'SPAN', className: 'bxec-link'}, true));

		for (i = 0; i < arLinks.length; i++)
			if (arLinks[i] && arLinks[i].nodeName)
				arLinks[i].innerHTML = this.arConfig.caldav_link_all;
	}
	else
	{
		arLinks = arLinks.concat(BX.findChildren(D.CAL.DOM.iPhoneOneCont, {tagName: 'SPAN', className: 'bxec-link'}, true));
		arLinks = arLinks.concat(BX.findChildren(D.CAL.DOM.birdOneCont, {tagName: 'SPAN', className: 'bxec-link'}, true));

		for (i = 0; i < arLinks.length; i++)
		{
			if (arLinks[i] && arLinks[i].nodeName)
			{
				arLinks[i].innerHTML = BX.util.htmlspecialchars(this.arConfig.caldav_link_one.replace('#CALENDAR_ID#', calendarId));
			}
		}
	}

	arLinks = BX.findChildren(D.CAL.DOM.macCont, {tagName: 'SPAN', className: 'bxec-link'}, true);
	for (i = 0; i < arLinks.length; i++)
	{
		if (arLinks[i] && arLinks[i].nodeName)
		{
			arLinks[i].innerHTML = this.arConfig.caldav_link_all.replace(/^https?:\/\//ig, '');
		}
	}
};

JCEC.prototype.CloseMobileHelpDialog = function(bClosePopup)
{
	if (bClosePopup === true)
		this.oMobileDialog.close();
};

JCEC.prototype.ChargePopupTabs = function(oPopup, idPrefix)
{
	if (!oPopup || !oPopup.CAL || !oPopup.CAL.DOM || !oPopup.CAL.DOM.pTabs)
		return;

	// Set tabs
	oPopup.CAL.Tabs = [];
	oPopup.CAL.activeTab = false;
	var tab, _this = this, activated;
	for (var i in oPopup.CAL.DOM.pTabs.childNodes)
	{
		tab = oPopup.CAL.DOM.pTabs.childNodes[i];
		if (tab.nodeType == '1' && tab.className  && tab.className.indexOf('bxec-d-tab') != -1)
		{
			oPopup.CAL.Tabs.push(
			{
				tab: tab,
				cont: BX(tab.id + '-cont'),
				showed: tab.style.display != 'none'
			});
			tab.onclick = function(){_this.SetPopupTab(parseInt(this.id.substr(idPrefix.length)), oPopup)};
		}
	}
}

JCEC.prototype.ShowPopupTab = function(Tab, bShow)
{
	Tab.tab.style.display = bShow ? '' : 'none';
	Tab.cont.style.display = bShow ? '' : 'none';
	Tab.showed = !!bShow;
}

JCEC.prototype.SetPopupTab = function(curInd, oPopup)
{
	var
		i, Tab, Tabs = oPopup.CAL.Tabs;

	if (Tabs && oPopup.CAL.activeTab !== curInd && !Tabs[curInd].bDisabled)
	{
		for (i in Tabs)
		{
			Tab = Tabs[i];
			if (!Tab || !Tab.cont)
				continue;

			if (i == curInd)
			{
				Tab.cont.style.display = 'block';
				BX.addClass(Tab.tab, 'bxec-d-tab-act');
			}
			else
			{
				Tab.cont.style.display = 'none';
				BX.removeClass(Tab.tab, 'bxec-d-tab-act');
			}

			BX.onCustomEvent(Tab, 'OnSetTab', [Tab, (i == curInd)]);
		}
		oPopup.CAL.activeTab = curInd;
		this.AdjustOverlay(oPopup);
	}
}

JCEC.prototype.InitColorDialogControl = function(key, OnSetValues)
{
	var
		_this = this,
		id = this.id + '-' + key,
		colorCont = BX(id + '-color-cont'),
		pColor = BX(id + '-color-inp'),
		pTextColor = BX(id + '-text-color-inp');

	function SetColors(color, text_color, check)
	{
		if (!text_color || (check && (text_color == '#FFFFFF' || text_color == '#000000')))
			text_color = _this.ColorIsDark(color) ? '#FFFFFF' : '#000000';

		try
		{
			pColor.value = color;
			pColor.style.backgroundColor = color;
			pColor.style.color = text_color;
		}
		catch(e)
		{
			color = this.arConfig.arCalColors[0];
			pColor.style.color = '#000000';
		}

		if (OnSetValues && typeof OnSetValues == 'function')
			OnSetValues(color, text_color);
	}

	colorCont.onclick = function(e)
	{
		if (!e)
			e = window.event;
		var targ = e.target || e.srcElement;
		if (targ && targ.nodeName && targ.nodeName.toLowerCase() == 'a')
		{
			var ind = parseInt(targ.id.substr((id + '-color-').length), 10);
			if (_this.arConfig.arCalColors[ind])
				SetColors(_this.arConfig.arCalColors[ind]);
		}
	};
	pColor.onblur = pColor.onkeyup = function(){SetColors(this.value);};
	pColor.onclick = function(){_this.ColorPicker.Open(
		{
			pWnd: this,
			key: key,
			id: id + '-bg',
			onSelect: function(value){SetColors(value, pColor.style.color, true);}
		});
	};

	pTextColor.onclick = function(){_this.ColorPicker.Open(
		{
			pWnd: this,
			key: key,
			id: id + '-text',
			onSelect: function(value){SetColors(pColor.value, value, false);}
		});
	};

	return {Set: SetColors}
}