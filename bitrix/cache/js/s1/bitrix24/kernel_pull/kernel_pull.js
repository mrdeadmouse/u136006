; /* /bitrix/js/calendar/core_planner_handler.js?145227743715147*/
; /* /bitrix/js/timeman/core_timeman.js?1452277479195207*/
; /* /bitrix/js/im/common.min.js?1452277455134164*/
; /* /bitrix/js/im/im.min.js?1452277455452605*/
; /* /bitrix/js/pull/pull.min.js?145227746429859*/

; /* Start:"a:4:{s:4:"full";s:59:"/bitrix/js/calendar/core_planner_handler.js?145227743715147";s:6:"source";s:43:"/bitrix/js/calendar/core_planner_handler.js";s:3:"min";s:0:"";s:3:"map";s:0:"";}"*/
;(function(){

if(!!window.BX.CCalendarPlannerHandler)
	return;

var BX = window.BX;

BX.CCalendarPlannerHandler = function()
{
	this.PLANNER = null;
	this.EVENTS = null;
	this.EVENTS_LIST = null;
	this.EVENTWND = {};
	this.CLOCK = null;

	BX.addCustomEvent('onPlannerDataRecieved', BX.proxy(this.draw, this));
};

BX.CCalendarPlannerHandler.prototype.draw = function(obPlanner, DATA)
{
	if(!!this._skipDraw)
	{
		this._skipDraw = false;
		return;
	}

	this.PLANNER = obPlanner;

	if(!DATA.CALENDAR_ENABLED)
		return;

	if (!this.EVENTS)
	{
		this.EVENTS = BX.create('DIV');
		this.EVENTS.appendChild(BX.create('DIV', {
			props: {className: 'tm-popup-section tm-popup-section-events'},
			html: '<span class="tm-popup-section-text">' + BX.message('JS_CORE_PL_EVENTS') + '</span>'
		}));

		this.EVENTS.appendChild(BX.create('DIV', {
			props: {className: 'tm-popup-events' + (BX.isAmPmMode() ? " tm-popup-events-ampm" : "")},
			children: [
				(this.EVENTS_LIST = BX.create('DIV', {
					props: {className: 'tm-popup-event-list'}
				})),
				this.drawEventForm(BX.proxy(this._createEventCallback, this))
			]
		}));
	}
	else
	{
		BX.cleanNode(this.EVENTS_LIST);
	}

	if (DATA.EVENTS.length > 0)
	{
		BX.removeClass(this.EVENTS, 'tm-popup-events-empty');
		var LAST_EVENT = null;
		for (var i=0,l=DATA.EVENTS.length;i<l;i++)
		{
			var q = this.EVENTS_LIST.appendChild(this.drawEvent(DATA.EVENTS[i]));

			if (DATA.EVENT_LAST_ID && DATA.EVENT_LAST_ID == DATA.EVENTS[i].ID)
				LAST_EVENT = q;
		}

		if (!!LAST_EVENT)
		{
			BX.defer(function()
			{
				if (LAST_EVENT.offsetTop < this.EVENTS_LIST.scrollTop || LAST_EVENT.offsetTop + LAST_EVENT.offsetHeight > this.EVENTS_LIST.scrollTop + this.EVENTS_LIST.offsetHeight)
				{
					this.EVENTS_LIST.scrollTop = LAST_EVENT.offsetTop - parseInt(this.EVENTS_LIST.offsetHeight/2);
				}
			}, this)();
		}
	}
	else
	{
		BX.addClass(this.EVENTS, 'tm-popup-events-empty');
	}

	obPlanner.addBlock(this.EVENTS, 300);
};

BX.CCalendarPlannerHandler.prototype.drawEvent = function(event, additional_props, fulldate)
{
	additional_props = additional_props || {};
	additional_props.className = 'tm-popup-event-name';
	fulldate = fulldate || false;

	return BX.create('DIV', {
		props: {
			className: 'tm-popup-event',
			bx_event_id: event.ID
		},
		children: [
			BX.create('DIV', {
				props: {className: 'tm-popup-event-datetime'},
				html: '<span class="tm-popup-event-time-start' + (event.DATE_FROM_TODAY ? '' : ' tm-popup-event-time-passed') + '">'+(fulldate?BX.timeman.formatDate(event.DATE_FROM)+' ':'')+ event.TIME_FROM + '</span><span class="tm-popup-event-separator">-</span><span class="tm-popup-event-time-end' + (event.DATE_TO_TODAY ? '' : ' tm-popup-event-time-passed') + '">' +(fulldate?BX.timeman.formatDate(event.DATE_TO)+' ':'')+  event.TIME_TO + '</span>'
			}),
			BX.create('DIV', {
				props: additional_props,
				events: event.ID ? {click: BX.proxy(this.showEvent, this)} : null,
				html: '<span class="tm-popup-event-text">' + BX.util.htmlspecialchars(event.NAME) + '</span>'
			})
		]
	});
};

BX.CCalendarPlannerHandler.prototype.showEvent = function(e)
{
	var event_id = BX.proxy_context.parentNode.bx_event_id;

	if (this.EVENTWND[event_id] && this.EVENTWND[event_id].node != BX.proxy_context)
	{
		this.EVENTWND[event_id].Clear();
		this.EVENTWND[event_id] = null;
	}

	if (!this.EVENTWND[event_id])
	{
		this.EVENTWND[event_id] = new BX.CCalendarPlannerEventPopup({
			planner: this.PLANNER,
			node: BX.proxy_context,
			bind: this.EVENTS.firstChild,
			id: event_id
		});
	}

	BX.onCustomEvent(this, 'onEventWndShow', [this.EVENTWND[event_id]]);

	this._skipDraw = true;
	this.EVENTWND[event_id].Show(this.PLANNER);

	return BX.PreventDefault(e);
};

BX.CCalendarPlannerHandler.prototype.drawEventForm = function(cb)
{
	var mt_format_css = BX.isAmPmMode() ? '_am_pm' : '';

	var handler = BX.delegate(function(e, bEnterPressed)
		{
			inp_Name.value = BX.util.trim(inp_Name.value);
			if (inp_Name.value && inp_Name.value!=BX.message('JS_CORE_PL_EVENTS_ADD'))
			{
				cb({
					from: inp_TimeFrom.value,
					to: inp_TimeTo.value,
					name: inp_Name.value,
					absence: inp_Absence.checked ? 'Y' : 'N'
				});

				BX.timer.start(inp_TimeFrom.bxtimer);
				BX.timer.start(inp_TimeTo.bxtimer);

				if (!bEnterPressed)
				{
					BX.addClass(inp_Name.parentNode, 'tm-popup-event-form-disabled')
					inp_Name.value = BX.message('JS_CORE_PL_EVENTS_ADD');
				}
				else
				{
					inp_Name.value = '';
				}
			}

			return (e || window.event) ? BX.PreventDefault(e) : null;
		}, this),

		handler_name_focus = function()
		{
			BX.removeClass(this.parentNode, 'tm-popup-event-form-disabled');
			if(this.value == BX.message('JS_CORE_PL_EVENTS_ADD'))
				this.value = '';
		};

	var inp_TimeFrom = BX.create('INPUT', {
		props: {type: 'text', className: 'tm-popup-event-start-time-textbox' + mt_format_css}
	});

	inp_TimeFrom.onclick = BX.delegate(function()
	{
		var cb = BX.delegate(function(value) {
			this.CLOCK.closeWnd();

			var oldvalue_From = unFormatTime(inp_TimeFrom.value),
				oldvalue_To = unFormatTime(inp_TimeTo.value);

			var diff = 3600;
			if (oldvalue_From && oldvalue_To)
				diff = oldvalue_To - oldvalue_From;

			BX.timer.stop(inp_TimeFrom.bxtimer);
			BX.timer.stop(inp_TimeTo.bxtimer);

			inp_TimeFrom.value = value;

			inp_TimeTo.value = formatTime(unFormatTime(value) + diff);

			inp_TimeTo.focus();
			inp_TimeTo.onclick();
		}, this);

		if (!this.CLOCK)
		{
			this.CLOCK = new BX.CClockSelector({
				start_time: unFormatTime(inp_TimeFrom.value),
				node: inp_TimeFrom,
				callback: cb
			});
		}
		else
		{
			this.CLOCK.setNode(inp_TimeFrom);
			this.CLOCK.setTime(unFormatTime(inp_TimeFrom.value));
			this.CLOCK.setCallback(cb);
		}

		inp_TimeFrom.blur();
		this.CLOCK.Show();
	}, this);

	inp_TimeFrom.bxtimer = BX.timer(inp_TimeFrom, {dt: 3600000, accuracy: 3600});

	var inp_TimeTo = BX.create('INPUT', {
		props: {type: 'text', className: 'tm-popup-event-end-time-textbox' + mt_format_css}
	});

	inp_TimeTo.onclick = BX.delegate(function()
	{
		var cb = BX.delegate(function(value) {
			this.CLOCK.closeWnd();
			inp_TimeTo.value = value;

			BX.timer.stop(inp_TimeFrom.bxtimer);
			BX.timer.stop(inp_TimeTo.bxtimer);

			inp_Name.focus();
			handler_name_focus.apply(inp_Name);
		}, this);

		if (!this.CLOCK)
		{
			this.CLOCK = new BX.CClockSelector({
				start_time: unFormatTime(inp_TimeTo.value),
				node: inp_TimeTo,
				callback: cb
			});
		}
		else
		{
			this.CLOCK.setNode(inp_TimeTo);
			this.CLOCK.setTime(unFormatTime(inp_TimeTo.value));
			this.CLOCK.setCallback(cb);
		}

		inp_TimeTo.blur();
		this.CLOCK.Show();
	}, this);

	inp_TimeTo.bxtimer = BX.timer(inp_TimeTo, {dt: 7200000, accuracy: 3600});

	var inp_Name = BX.create('INPUT', {
		props: {type: 'text', className: 'tm-popup-event-form-textbox' + mt_format_css, value: BX.message('JS_CORE_PL_EVENTS_ADD')},
		events: {
			keypress: function(e) {
				return (e.keyCode == 13) ? handler(e, true) : true;
			},
			blur: function() {
				if (this.value == '')
				{
					BX.addClass(this.parentNode, 'tm-popup-event-form-disabled');
					this.value = BX.message('JS_CORE_PL_EVENTS_ADD');
				}
			},
			focus: handler_name_focus
		}
	});

	var id = 'bx_tm_absence_' + Math.random();
	var inp_Absence = BX.create('INPUT', {
		props: {type: 'checkbox', className: 'checkbox', id: id}
	});

	this.EVENTS_FORM = BX.create('DIV', {
		props: {className: 'tm-popup-event-form tm-popup-event-form-disabled'},
		children: [
			inp_TimeFrom, inp_TimeTo, inp_Name,
			BX.create('SPAN', {
				props: {className: 'tm-popup-event-form-submit'},
				events: {
					click: handler
				}
			}),
			BX.create('DIV', {
				props: {className:'tm-popup-event-form-options'},
				children: [
					inp_Absence,
					BX.create('LABEL', {props: {htmlFor: id}, text: BX.message('JS_CORE_PL_EVENT_ABSENT')})
				]
			})
		]
	});

	return this.EVENTS_FORM;
};

BX.CCalendarPlannerHandler.prototype._createEventCallback = function(ev)
{
	calendarLastParams = ev;

	this.PLANNER.query('calendar_add', ev);

	this.EVENTS_LIST.appendChild(this.drawEvent({
		DATE_FROM_TODAY: true, DATE_TO_TODAY: true,
		NAME: BX.util.htmlspecialchars(ev.name),
		TIME_FROM: ev.from,
		TIME_TO: ev.to
	}));
};

/**************************/
BX.CCalendarPlannerEventPopup = function(params)
{
	this.params = params;
	this.node = params.node;

	var ie7 = false;
	/*@cc_on
		 @if (@_jscript_version <= 5.7)
			ie7 = true;
		/*@end
	@*/

	this.popup = BX.PopupWindowManager.create('event_' + this.params.id, this.params.bind, {
		closeIcon : {right: "12px", top: "10px"},
		closeByEsc: true,
		offsetLeft : ie7 || (document.documentMode && document.documentMode <= 7) ? -347 : -340,
		autoHide: true,
		bindOptions : {
			forceBindPosition : true,
			forceTop : true
		},
		angle : {
			position: "right",
			offset : this.params.angle_offset || 27
		}
	});

	BX.addCustomEvent(this.parent, 'onEventWndShow', BX.delegate(this.onEventWndShow, this))

	this.bSkipShow = false;
	this.isReady = false;
};

BX.CCalendarPlannerEventPopup.prototype.onEventWndShow = function(wnd)
{
	if (wnd != this)
	{
		if (this.popup)
			this.popup.close();
		else
			this.bSkipShow = true;
	}
};

BX.CCalendarPlannerEventPopup.prototype.Show = function(planner, data)
{
	BX.removeCustomEvent(planner, 'onPlannerDataRecieved', BX.proxy(this.Show, this));

	data = data || this.data;

	if (data && data.error)
		return;

	if (!data)
	{
		BX.addCustomEvent(planner, 'onPlannerDataRecieved', BX.proxy(this.Show, this));
		return planner.query('calendar_show', {id: this.params.id});
	}

	if(data.EVENT)
	{
		data = data.EVENT;
	}

	this.data = data;

	if (this.bSkipShow)
	{
		this.bSkipShow = false;
	}
	else
	{
		this.popup.setContent(this.GetContent());
		this.popup.setButtons(this.GetButtons());

		var offset = 0;
		if (this.params.node && this.params.node.parentNode && this.params.node.parentNode.parentNode)
		{
			offset = this.params.node.parentNode.offsetTop - this.params.node.parentNode.parentNode.scrollTop;
		}

		this.popup.setOffset({offsetTop: this.params.offsetTop || (offset - 20)});
		//popup.setAngle({ offset : 27 });
		this.popup.adjustPosition();
		this.popup.show();
	}

	return true;
};

BX.CCalendarPlannerEventPopup.prototype.GetContent = function()
{
	var html = '<div class="tm-event-popup">',
		hr = '<div class="popup-window-hr"><i></i></div>';

	html += '<div class="tm-popup-title"><a class="tm-popup-title-link" href="' + this.data.URL + '">' + BX.util.htmlspecialchars(this.data.NAME) +'</a></div>';
	if (this.data.DESCRIPTION)
	{
		html += hr + '<div class="tm-event-popup-description">' + this.data.DESCRIPTION + '</div>';
	}

	html += hr;

	html += '<div class="tm-event-popup-time"><div class="tm-event-popup-time-interval">' + this.data.DATE_F + '</div>';
	if (this.data.DATE_F_TO)
		html += '<div class="tm-event-popup-time-hint">(' + this.data.DATE_F_TO + ')</div></div>'


	if (this.data.GUESTS)
	{
		html += hr + '<div class="tm-event-popup-participants">';

		if (this.data.HOST)
		{
			html += '<div class="tm-event-popup-participant"><div class="tm-event-popup-participant-status tm-event-popup-participant-status-accept"></div><div class="tm-event-popup-participant-name"><a class="tm-event-popup-participant-link" href="' + this.data.HOST.url + '">' + this.data.HOST.name + '</a><span class="tm-event-popup-participant-hint">' + BX.message('JS_CORE_PL_EVENT_HOST') + '</span></div></div>';
		}

		if (this.data.GUESTS.length > 0)
		{
			html += '<table cellspacing="0" class="tm-event-popup-participants-grid"><tbody><tr>';

			var d = Math.ceil(this.data.GUESTS.length/2),
				grids = ['',''];

			for (var i=0;i<this.data.GUESTS.length; i++)
			{
				var status = '';
				if (this.data.GUESTS[i].status == 'Y')
					status = 'tm-event-popup-participant-status-accept';
				else if (this.data.GUESTS[i].status == 'N')
					status = 'tm-event-popup-participant-status-decline';

				grids[i<d?0:1] += '<div class="tm-event-popup-participant"><div class="tm-event-popup-participant-status ' + status + '"></div><div class="tm-event-popup-participant-name"><a class="tm-event-popup-participant-link" href="' + this.data.GUESTS[i].url + '">' + this.data.GUESTS[i].name + '</a></div></div>';
			}

			html += '<td class="tm-event-popup-participants-grid-left">' + grids[0] + '</td><td class="tm-event-popup-participants-grid-right">' + grids[1] + '</td>';

			html += '</tr></tbody></table>';

		}

		html += '</div>';
	}

	html += '</div>';

	return html;
};

BX.CCalendarPlannerEventPopup.prototype.GetButtons = function()
{
	var btns = [], q = BX.proxy(this.Query, this);

	if (this.data.STATUS === 'Q')
	{
		btns.push(new BX.PopupWindowButton({
			text : BX.message('JS_CORE_PL_EVENT_CONFIRM'),
			className : "popup-window-button-create",
			events : {
				click: function() {q('CONFIRM=Y');}
			}
		}));
		btns.push(new BX.PopupWindowButton({
			text : BX.message('JS_CORE_PL_EVENT_REJECT'),
			className : "popup-window-button-cancel",
			events : {
				click: function() {q('CONFIRM=N');}
			}
		}));
	}
	else
	{
		btns.push(new BX.PopupWindowButtonLink({
			text : BX.message('JS_CORE_WINDOW_CLOSE'),
			className : "popup-window-button-link-cancel",
			events : {click : function(e) {this.popupWindow.close();return BX.PreventDefault(e);}}
		}));

	}

	return btns;
};

BX.CCalendarPlannerEventPopup.prototype.Clear = function()
{
	if (this.popup)
	{
		this.popup.close();
		this.popup.destroy();
		this.popup = null;
	}

	this.node = null;
};

BX.CCalendarPlannerEventPopup.prototype.Query = function(str)
{
	BX.ajax({
		method: 'GET',
		url: this.data.URL + '&' + str,
		processData: false,
		onsuccess: BX.proxy(this._Query, this)
	});
};

BX.CCalendarPlannerEventPopup.prototype._Query = function()
{
	this.data = null;
	this.Show();
};


function formatTime(time, bSec, bSkipAmPm)
{
	var mt = '';
	if (BX.isAmPmMode() && !bSkipAmPm)
	{
		if (parseInt(time/3600) > 12)
		{
			time = parseInt(time) - 12*3600;
			mt = ' pm';
		}
		else if (parseInt(time/3600) == 12)
		{
			mt = ' pm';
		}
		else if (parseInt(time/3600) == 0)
		{
			time = parseInt(time) + 12*3600;
			mt = ' am';
		}
		else
			mt = ' am';

		if (!!bSec)
			return parseInt(time/3600) + ':' + BX.util.str_pad(parseInt((time%3600)/60), 2, '0', 'left') + ':' + BX.util.str_pad(time%60, 2, '0', 'left') + mt;
		else
			return parseInt(time/3600) + ':' + BX.util.str_pad(parseInt((time%3600)/60), 2, '0', 'left') + mt;
	}
	else
	{
		if (!!bSec)
			return BX.util.str_pad(parseInt(time/3600), 2, '0', 'left') + ':' + BX.util.str_pad(parseInt((time%3600)/60), 2, '0', 'left') + ':' + BX.util.str_pad(time%60, 2, '0', 'left') + mt;
		else
			return BX.util.str_pad(parseInt(time/3600), 2, '0', 'left') + ':' + BX.util.str_pad(parseInt((time%3600)/60), 2, '0', 'left') + mt;
	}
};

function unFormatTime(time)
{
	var q = time.split(/[\s:]+/);
	if (q.length == 3)
	{
		var mt = q[2];
		if (mt == 'pm' && q[0] < 12)
			q[0] = parseInt(q[0], 10) + 12;

		if (mt == 'am' && q[0] == 12)
			q[0] = 0;

	}
	return parseInt(q[0], 10) * 3600 + parseInt(q[1], 10) * 60;
};

new BX.CCalendarPlannerHandler();
})();
/* End */
;
; /* Start:"a:4:{s:4:"full";s:51:"/bitrix/js/timeman/core_timeman.js?1452277479195207";s:6:"source";s:34:"/bitrix/js/timeman/core_timeman.js";s:3:"min";s:0:"";s:3:"map";s:0:"";}"*/
;(function() {
var BX = window.BX;
if (BX.timeman) return;

var TMPoint = '/bitrix/tools/timeman.php',
	intervals = {
		OPENED: 60000,
		CLOSED: 30000,
		EXPIRED: 30000,
		START: 30000
	},

	selectedTimestamp = 0,
	errorReport = '',
	SITE_ID = BX.message('SITE_ID'),
	calendarLastParams = null,

	waitDiv = null,
	waitTime = 1000,
	waitPopup = null,
	waitTimeout = null;

BX.timeman = function(id, data, site_id)
{
	SITE_ID = site_id;
	new BX.CTimeMan(id, data);
}

//
BX.timeman.TASK_SUFFIXES = {"-1": "overdue", "-2": "new", 1: "new", 2: "accepted", 3: "in-progress", 4: "waiting", 5: "completed", 6: "delayed", 7: "declined"};

BX.timeman_query = function(action, data, callback, bForce)
{
	if (BX.type.isFunction(data))
	{
		callback = data;data = {};
	}

	var query_data = {
		'method': 'POST',
		'dataType': 'json',
		'url': TMPoint + '?action=' + action + '&site_id=' + SITE_ID + '&sessid=' + BX.bitrix_sessid(),
		'data':  BX.ajax.prepareData(data),
		'onsuccess': function(data) {
			BX.timeman.closeWait();
			callback(data, action)
		},
		'onfailure': function(type, e) {
			BX.timeman.closeWait();
			if (e && e.type == 'json_failure')
			{
				(new BX.PopupWindow('timeman_failure_' + Math.random(), null, {
					content: BX.create('DIV', {
						style: {width: '300px'},
						html: BX.message('JS_CORE_TM_ERROR') + '<br /><br /><small>' + BX.util.strip_tags(e.data) + '</small>'
					}),
					buttons: [
						new BX.PopupWindowButton({
							text : BX.message('JS_CORE_WINDOW_CLOSE'),
							className : "popup-window-button-decline",
							events : {
								click : function() {this.popupWindow.close()}
							}
						})
					]
				})).show();
			}

		}
	};

	if (action == 'update')
	{
		query_data.lsId = 'tm-update';
		query_data.lsTimeout = intervals.START/1000 - 1;
		query_data.lsForce = !!bForce;
	}
	else if (action == 'report')
	{
		query_data.lsId = 'tm-report';
		query_data.lsTimeout = 29;
		query_data.lsForce = !!bForce;
	}

	return BX.ajax(query_data);
}

BX.timeman.formatTime = function(time, bSec, bSkipAmPm)
{
	if (typeof time == 'object' && time.constructor == Date)
		return BX.timeman.formatTimeOb(time, bSec);

	var mt = '';
	if (BX.isAmPmMode() && !bSkipAmPm)
	{
		if (parseInt(time/3600) > 12)
		{
			time = parseInt(time) - 12*3600;
			mt = ' pm';
		}
		else if (parseInt(time/3600) == 12)
		{
			mt = ' pm';
		}
		else if (parseInt(time/3600) == 0)
		{
			time = parseInt(time) + 12*3600;
			mt = ' am';
		}
		else
			mt = ' am';

		if (!!bSec)
			return parseInt(time/3600) + ':' + BX.util.str_pad(parseInt((time%3600)/60), 2, '0', 'left') + ':' + BX.util.str_pad(time%60, 2, '0', 'left') + mt;
		else
			return parseInt(time/3600) + ':' + BX.util.str_pad(parseInt((time%3600)/60), 2, '0', 'left') + mt;
	}
	else
	{
		if (!!bSec)
			return BX.util.str_pad(parseInt(time/3600), 2, '0', 'left') + ':' + BX.util.str_pad(parseInt((time%3600)/60), 2, '0', 'left') + ':' + BX.util.str_pad(time%60, 2, '0', 'left') + mt;
		else
			return BX.util.str_pad(parseInt(time/3600), 2, '0', 'left') + ':' + BX.util.str_pad(parseInt((time%3600)/60), 2, '0', 'left') + mt;
	}
}

BX.timeman.formatDate = function(tsDate, format)
{
	var date = new Date(tsDate*1000) || new Date();
	var str = !!format
			? format :
			BX.message('FORMAT_DATE');

	return str.replace(/YYYY/ig, date.getFullYear())
		.replace(/MMMM/ig, BX.util.str_pad_left((date.getMonth()+1).toString(), 2, '0'))
		.replace(/MM/ig, BX.util.str_pad_left((date.getMonth()+1).toString(), 2, '0'))
		.replace(/M/ig, BX.util.str_pad_left((date.getMonth()+1).toString(), 2, '0'))
		.replace(/DD/ig, BX.util.str_pad_left(date.getDate().toString(), 2, '0'))
		.replace(/HH/ig, BX.util.str_pad_left(date.getHours().toString(), 2, '0'))
		.replace(/MI/ig, BX.util.str_pad_left(date.getMinutes().toString(), 2, '0'))
		.replace(/SS/ig, BX.util.str_pad_left(date.getSeconds().toString(), 2, '0'));
}

BX.timeman.formatTimeOb = function(time, bSec)
{
	if (!!bSec)
		return BX.util.str_pad(time.getHours(), 2, '0', 'left') + ':' + BX.util.str_pad(time.getMinutes(), 2, '0', 'left') + ':' + BX.util.str_pad(time.getSeconds(), 2, '0', 'left');
	else
		return BX.util.str_pad(time.getHours(), 2, '0', 'left') + ':' + BX.util.str_pad(time.getMinutes(), 2, '0', 'left');
}

BX.timeman.unFormatTime = function(time)
{
	var q = time.split(/[\s:]+/);
	if (q.length == 3)
	{
		var mt = q[2];
		if (mt == 'pm' && q[0] < 12)
			q[0] = parseInt(q[0], 10) + 12;

		if (mt == 'am' && q[0] == 12)
			q[0] = 0;

	}
	return parseInt(q[0], 10) * 3600 + parseInt(q[1], 10) * 60;
}

BX.timeman.formatWorkTime = function(time, bSec)
{
	if (!!bSec)
		return parseInt(time/3600) + BX.message('JS_CORE_H') + ' ' + parseInt((time%3600)/60) + BX.message('JS_CORE_M') + ' ' + time%60 + BX.message('JS_CORE_S');
	else
		return parseInt(time/3600) + BX.message('JS_CORE_H') + ' ' + parseInt((time%3600)/60) + BX.message('JS_CORE_M');
}

BX.timeman.formatWorkTimeView = function(time, view)
{
	if (!view)
		return BX.timeman.formatWorkTime(time);

	if (BX.type.isString(view))
	{
		view = BX.timer.getHandler(view);
	}

	return view(parseInt(time/3600), parseInt((time%3600)/60), time%60);
}

BX.timeman.showWait = function(div, timeout)
{
	waitDiv = waitDiv || div;
	div = BX(div || waitDiv) || document.body;

	if (waitTimeout)
		BX.timeman.closeWait();

	if (timeout !== 0)
	{
		return (waitTimeout = setTimeout(function(){
			BX.timeman.showWait(div, 0)
		}, timeout || waitTime));
	}

	if (!waitPopup)
	{
		waitPopup = new BX.PopupWindow('timeman_wait', div, {
			autoHide: true,
			lightShadow: true,
			content: BX.create('DIV', {props: {className: 'tm_wait'}})
		});
	}
	else
	{
		waitPopup.setBindElement(div);
	}

	var height = div.offsetHeight, width = div.offsetWidth;
	if (height > 0 && width > 0)
	{
		waitPopup.setOffset({
			offsetTop: -parseInt(height/2+15),
			offsetLeft: parseInt(width/2-15)
		});

		waitPopup.show();
	}

	return waitPopup;
}

BX.timeman.closeWait = function()
{
	if (waitTimeout)
	{
		clearTimeout(waitTimeout);
		waitTimeout = null;
	}

	if (waitPopup)
	{
		waitPopup.close();
	}
}

function _unShowInputError()
{
	BX.removeClass(this, 'bx-tm-popup-report-error');
	this.onkeypress = null;
}

function _showInputError(inp)
{
	BX.addClass(inp, 'bx-tm-popup-report-error');
	inp.focus();
	inp.onkeypress = _unShowInputError;
}

BX.timeman.editTime = function(e)
{
	if(!this.BXTIMEINPUT)
		return true;

	var
		enterHandler = function(e) {if (e.keyCode == 13) save(e);}
		inputH = BX.create('INPUT', {
			props: {className: 'tm-time-edit-input'},
			attrs: {maxLength: 2},
			events: {keypress: enterHandler}
		}),
		inputM = BX.create('INPUT', {
			props: {className: 'tm-time-edit-input'},
			attrs: {maxLength: 2},
			events: {keypress: enterHandler}
		});

	var
		content = BX.create('DIV', {
			children: [
				inputH,
				BX.create('SPAN', {html: '&nbsp;' + BX.message('JS_CORE_H') + '&nbsp;&nbsp;'}),
				inputM,
				BX.create('SPAN', {html: '&nbsp;' + BX.message('JS_CORE_M')})
			]
		}),

		resultText = this, resultInput = this.BXTIMEINPUT, checkInput = this.BXCHECKINPUT,

		save = function(e) {
			var h = parseInt(inputH.value), m = parseInt(inputM.value)
			if (isNaN(h)) h = 0;
			if (isNaN(m)) m = 0;

			/*if (h < 0 || h > 23)
			{
				_showInputError(inputH);
				return BX.PreventDefault(e);
			}*/
			if (m < 0 || m > 59)
			{
				_showInputError(inputM);
				return BX.PreventDefault(e);
			}

			wnd.close();

			resultInput.value = h * 3600 + m * 60;
			resultText.innerHTML = BX.timeman.formatWorkTime(resultInput.value);

			checkInput.checked = resultInput.value > 0;

			return BX.PreventDefault(e);
		},

		wnd = BX.PopupWindowManager.create(
		'time_edit' + (parseInt(Math.random() * 100000)), this,
		{
			autoHide: true,
			lightShadow: true,
			content: content,
			buttons: [
				new BX.PopupWindowButton({
					text : 'OK',//BX.message('JS_CORE_TM_B_SAVE'),
					className : "popup-window-button-accept",
					events : {
						click : save
					}
				}),
				new BX.PopupWindowButtonLink({
					text : BX.message('JS_CORE_TM_B_CLOSE'),
					className : "popup-window-button-link-cancel",
					events : {
						click : function() {wnd.close()}
					}
				})
			]
		}
	);

	inputH.value = parseInt(resultInput.value / 3600);
	inputM.value = parseInt((resultInput.value % 3600) / 60);

	wnd.show();
	inputH.focus();

	return BX.PreventDefault(e);
}

BX.CTimeMan = function(div, DATA)
{
	window.BXTIMEMAN = this;
	this.bInited = false;
	this.DIV = div || 'bx_tm';

	this.INTERVAL = null;
	this.INTERVAL_TIMEOUT = intervals.START;

	this.PARTS = {};
	this.DATA = DATA;
	this.EVENTS = (DATA ? DATA.EVENTS : null) || [];
	this.TASKS = (DATA ? DATA.TASKS : null) || [];

	this.WND = new BX.CTimeManWindow(this, {
		node: this.DIV,
		type: ['right', 'top']
	});
	this.WND.ACTIONS = {
		OPEN: BX.delegate(this.OpenDay, this),
		CLOSE: BX.proxy(this.CloseDayShowForm, this),
		REOPEN: BX.delegate(this.ReOpenDay, this),
		PAUSE: BX.delegate(this.PauseDay, this)
	};

	this.ERROR = false;
	this.DENY_QUERY = false;

	this.FREE_MODE = false;

	BX.ready(BX.delegate(this.Init, this));
	BX.addCustomEvent(window, 'onLocalStorageChange', BX.delegate(function(data) {
		if (data.key == 'ajax-tm-update' && data.value != 'BXAJAXWAIT')
		{
			var v = data.value;
			if (BX.type.isString(v))
				v = BX.parseJSON(v);
			if (v)
			{
				this._Update(v, 'update');
			}
		}
	}, this));
}

BX.CTimeMan.prototype.Init = function()
{
	this.DIV = BX(this.DIV);

	BX.unbindAll(this.DIV);
	BX.bind(this.DIV, 'click', BX.proxy(BXTIMEMAN.Open, BXTIMEMAN));

	this.bInited = true;

	if (!!this.DATA)
	{
		this.setData(this.DATA);
		BX.ajax.replaceLocalStorageValue('tm-update', this.DATA, intervals.START/1000 - 1)
	}

	BX.onCustomEvent(window, "onTimemanInit");
}

BX.CTimeMan.prototype.setBindOptions = function(bindOptions)
{
	this.WND.bindOptions = bindOptions;
}

BX.CTimeMan.prototype.setData = function(DATA)
{
	if (!DATA)
		return;

	if (!DATA.INFO)
		this.firstTime = true;

	this.DATA = DATA;
	this.FREE_MODE = !!this.DATA.TM_FREE;

	this.INTERVAL_TIMEOUT = intervals[this.DATA.STATE] || intervals.START;

	if (this.firstTime && this.DATA.INFO)
	{
		BX.onCustomEvent(this, 'onTimeManNeedRebuild', [this.DATA]);
		this.firstTime = false;
	}
	else
	{
		BX.onCustomEvent(this, 'onTimeManDataRecieved', [this.DATA]);
	}

	if (this.DATA.OPEN_NOW)
	{
		if (!this.WND.SHOW && !this.bWasForsedOpen)
		{
			this.Update();
			BX.ready(BX.delegate(this.WND.Show, this.WND));
		}
		this.bWasForsedOpen = true;
	}
}

BX.CTimeMan.prototype.Update = function(force)
{
	if(!!force) this._unsetError();
	this.Query('update', BX.proxy(this._Update, this), force);
}

BX.CTimeMan.prototype._Update = function(data, action)
{
	if (this._checkQueryError(data))
	{
		if (this.WND.CLOCKWND && !this.WND.CLOCKWND.SHOW)
		{
			this.WND.CLOCKWND.Clear();
		}

		this.setData(data);

		if (action != 'update')
		{
			BX.ajax.replaceLocalStorageValue('tm-update', data, intervals.START/1000 - 1)
			this.WND.clearTempData();
		}

		if (!!data.CLOSE_TIMESTAMP)
		{
			this.close_day_form_ts = data.CLOSE_TIMESTAMP;
			this.close_day_form_ts_report = data.CLOSE_TIMESTAMP_REPORT;
			this.CloseDayShowForm();
		}
	}
}

BX.CTimeMan.prototype.Query = function(action, data, callback, bForce)
{
	if (this.DENY_QUERY)
		return;

	if (BX.type.isFunction(data))
	{
		bForce = !!callback; callback = data; data = {};
	}

	if (this.WND && this.WND.SHOW)
	{
		data.full = 'Y';
		BX.timeman.showWait(this.WND.DIV || this.DIV);
	}

	BX.timeman_query(action, data, callback, bForce);
}

BX.CTimeMan.prototype._setFatalError = function(e)
{
	this.ERROR = true;

	if (this.INTERVAL)
		clearTimeout(this.INTERVAL);

	this.DIV.innerHTML = e;

	BX.addClass(this.DIV, 'bx-tm-error');
}

BX.CTimeMan.prototype._setDenyError = function(e)
{
	this.DENY_QUERY = true;

	if (this.INTERVAL)
		clearTimeout(this.INTERVAL);

	BX.addClass(this.DIV, 'bx-tm-error');
}

BX.CTimeMan.prototype._unsetError = function()
{
	BX.addClass(this.DIV, 'bx-tm-error');
	if (this.ERROR)
	{
		this.ERROR = false;
	}

	this.DENY_QUERY = false;

	BX.removeClass(this.DIV, 'bx-tm-error');
}

BX.CTimeMan.prototype._checkQueryError = function(data)
{
	if (data && data.error)
	{
		if (data.type)
		{
			switch(data.type)
			{
				case 'fatal':
					this._setFatalError(data.error)
				break;

				case 'deny_query':
					this._setDenyError(data.error)
				break;
			}
		}
		else if (data.error_id == 'REPORT_NEEDED')
		{
			this._showReportField(data.error);
		}
		else if (data.error_id == 'CHOOSE_CALENDAR')
		{
			this._showCalendarField(data.error);
		}
		else if (data.error_id == 'WD_EXPIRED')
		{
			setTimeout(BX.proxy(function(){this.Update(true)}, this), 10);
		}

		return false;
	}

	this._unsetError();

	return true;
}

BX.CTimeMan.prototype._showReportField = function(error_string)
{
	this.WND.showReportField(error_string);
}

BX.CTimeMan.prototype._showCalendarField = function(calendars)
{
	(new BX.CTimeManCalendarSelector(this, {
		node: this.WND.EVENTS,
		data: calendars
	})).Show();
}

BX.CTimeMan.prototype.Open = function()
{
	if (this.WND.Show())
	{
		this.Update(true);
	}
}

BX.CTimeMan.prototype.setTimestamp = function(ts)
{
	selectedTimestamp = parseInt(ts);
	if (isNaN(selectedTimestamp))
		selectedTimestamp = 0;
}

BX.CTimeMan.prototype.setReport = function(report)
{
	errorReport = report;
}

BX.CTimeMan.prototype.CloseDayShowForm = function(e)
{
	if (this.CLOSE_DAY_FORM)
	{
		this.CLOSE_DAY_FORM.popup.close();
		this.CLOSE_DAY_FORM = null;
	}

	if (this.DATA.REPORT_REQ == 'A' || this.FREE_MODE)
	{
		if (this.DATA.STATE == 'EXPIRED' && !this.WND.TIMESTAMP)
			this.WND.ShowClock();
		else
			this.CloseDay(e);
	}
	else
	{
		this.CLOSE_DAY_FORM = new BX.CTimeManReportForm(
			this,
			{
				node: this.DIV,
				bind: this.DIV,
				mode: 'edit',
				external_finish_ts: this.close_day_form_ts,
				external_finish_ts_report: this.close_day_form_ts_report
			}
		);

		this.CLOSE_DAY_FORM.Show();
	}

	if (e || window.event)
		return BX.PreventDefault(e);

	return false;
}

BX.CTimeMan.prototype.CheckNeedToReportImm = function()
{
	this.CheckNeedToReport(true);
}

BX.CTimeMan.prototype.CheckNeedToReport = function(bForce)
{
	if (!this.WEEKLY_FORM)
	{
		bForce = (bForce)?"Y":"N";
		BX.timeman_query('check_report', {force:bForce}, BX.proxy(this.ShowFormWeekly, this));
	}
	else
		this.WEEKLY_FORM.popup.show();
	return false;
}

BX.CTimeMan.prototype.ShowFormWeekly = function(data)
{
	this.ShowCallReport = false;
	report_info = data.REPORT_INFO;
	report_data = data.REPORT_DATA;

	this.REPORT_FULL_MODE = report_info.MODE;

	if (report_info.IS_REPORT_DAY == "Y")
	{
		this.ShowCallReport = true;
		BX.addCustomEvent("OnWorkReportSend", function(){
			if (BX("work_report_call_link"))
				BX.hide(BX("work_report_call_link"));
		});
	}

	if (report_data.INFO)
	{
		if (!this.WEEKLY_FORM || this.WEEKLY_FORM == null)
		{
			this.WEEKLY_FORM = new BX.CTimeManReportFormWeekly(
				this,
				{
					node: this.DIV,
					bind: this.DIV,
					mode: 'edit'
				}
			);
			this.WEEKLY_FORM.data = report_data;
			this.WEEKLY_FORM.Show();
		}
		else
			this.WEEKLY_FORM.popup.show();
		window.WEEKLY_FORM = this.WEEKLY_FORM;
	}

	return false;
}


BX.CTimeMan.prototype.CloseDay = function(e)
{
	this.Query('close', {timestamp: selectedTimestamp, report: errorReport}, BX.proxy(this._Update, this));
	this.setTimestamp(0);
	this.setReport('');
	return BX.PreventDefault(e);
}

BX.CTimeMan.prototype.OpenDay = function(e)
{
	this.Query('open', {timestamp: selectedTimestamp, report: errorReport}, BX.proxy(this._Update, this));
	this.setTimestamp(0);
	this.setReport('');
	return BX.PreventDefault(e);
}

BX.CTimeMan.prototype.ReOpenDay = function(e)
{
	this.setTimestamp(0);
	this.setReport('');
	this.Query('reopen', {}, BX.proxy(this._Update, this));
	return BX.PreventDefault(e);
}

BX.CTimeMan.prototype.PauseDay = function(e)
{
	this.setTimestamp(0);
	this.setReport('');
	this.Query('pause', {}, BX.proxy(this._Update, this));
	return BX.PreventDefault(e);
}

BX.CTimeMan.prototype.calendarEntryAdd = function(params, cb)
{
	calendarLastParams = params;
	this.Query('calendar_add', params, cb || BX.proxy(this._Update, this));
}

BX.CTimeMan.prototype.taskPost = function(entry, callback)
{
	if (typeof entry == 'object')
	{
		this.TASK_CHANGES[entry.action].push(entry.id);

		if (this.TASK_CHANGE_TIMEOUT)
			clearTimeout(this.TASK_CHANGE_TIMEOUT);

		if (entry.action == 'add')
			this.taskPost();
		else
		{
			this.DENY_QUERY = true; // we should deny popup updating because of possible errors

			this.TASK_CHANGE_TIMEOUT = setTimeout(
				BX.proxy(this.taskPost, this), 1000
			);
		}
	}
	else
	{
		this.DENY_QUERY = false;

		this.TASKS = [];
		this.Query('task', this.TASK_CHANGES, BX.proxy(this._Update, this));
		this.TASK_CHANGES = {add: [], remove: []};
	}
}

BX.CTimeMan.prototype.taskEntryAdd = function(params, cb)
{
	this.TASKS = [];
	this.Query('task', params, cb || BX.proxy(this._Update, this));
}


/***********************************************************/

BX.CTimeManWindow = function(parent, bindOptions)
{
	this.PARENT = parent;
	this.DIV = null;
	this.POPUP = null;
	this.LAYOUT = null;

	this.bindOptions = bindOptions;

	this.DATA = {};
	this.ACTIONS = {};

	this.SHOW = false;
	this.CREATE = false;

	this.TIMESTAMP = false;
	this.ERROR_REPORT = '';
	this.MAIN_BTN_HANDLER = null;

	this.REPORT = null;

	this.DASHBOARD = null;

	this.TASKWND = {};
	this.EVENTWND = {};

	BX.addCustomEvent(this.PARENT, 'onTimeManDataRecieved', BX.delegate(this.onTimeManChangeState, this));
	BX.addCustomEvent(this.PARENT, 'onTimeManNeedRebuild', BX.delegate(this.onTimeManNeedRebuild, this));
	BX.addCustomEvent('onTopPanelCollapse', BX.proxy(this.Align, this));
	BX.bind(window, 'resize', BX.proxy(this.Align, this));
}

BX.CTimeManWindow.prototype.onTimeManChangeState = function(DATA)
{
	if (!this.CREATE)
		return;

	this.DATA = DATA;

	if (this.SHOW)
		this.Align();
}

BX.CTimeManWindow.prototype.onTimeManNeedRebuild = function(DATA)
{
	this.CREATE = true;

	this.Create(DATA);

	if (this.SHOW)
		this.Align();
}

BX.CTimeManWindow.prototype.Create = function(DATA)
{
	if (!this.CREATE)
		return;

	if (this.NOTICE_TIMER)
	{
		BX.timer.stop(this.NOTICE_TIMER);
		this.NOTICE_TIMER = null;
	}

	if (this.bindOptions.mode == 'popup')
	{
		if (!this.POPUP)
		{
			var p = this.bindOptions.popupOptions || {
				autoHide: true,
				lightShadow: true,
				bindOptions : {
					forceBindPosition : true,
					forceTop : true
				},
				angle : {
					position: "top",
					offset : 50
				}
			};

			p.lightShadow = true;

			this.POPUP = new BX.PopupWindow('timeman_main', this.bindOptions.node, p);
		}

		this.POPUP.setContent(this.CreateLayoutTable(DATA));
	}
	else
	{
		if (this.DIV)
		{
			BX.cleanNode(this.DIV);
			this.DIV = null;
		}

		if (null == this.DIV)
		{
			this.DIV = document.body.appendChild(BX.create('DIV', {
				props: {id: 'tm-popup'},
				events: {
					click: BX.eventCancelBubble
				},
				style: {
					position: 'absolute',
					display: this.SHOW ? 'block' : 'none'
				}
			}));

			this.DIV.appendChild(this.CreateLayoutTable());
		}

		BX.cleanNode(this.LAYOUT);

		this.LAYOUT.appendChild(this.CreateDashboard(DATA));
		this.LAYOUT.appendChild(_createHR());
	}

	this.LAYOUT.appendChild(this.CreateNoticeRow(DATA));
	this.LAYOUT.appendChild(this.CreateMainRow(DATA));

	if (DATA.INFO)
	{
		if(DATA.PLANNER)
		{
			this.PLANNER = new BX.CPlanner(DATA.PLANNER);
			this.PLANNER.WND = this;

			this.LAYOUT.appendChild(this.PLANNER.drawAdditional());
		}

		this.TABCONTROL = new BX.CTimeManTabControl(
			this.LAYOUT.appendChild(BX.create('DIV'))
		);

		if (DATA.PLANNER)
		{
			this.TABCONTROL.addTab({
				id: 'plans',
				title: BX.message('JS_CORE_TM_PLAN'),
				content: [
					this.PLANNER.draw()
				]
			});
		}

		this.TABCONTROL.addTab({
			id: 'report',
			title: BX.message('JS_CORE_TM_REPORT'),
			content: this.CreateReport()
		});

		this.call_report = BX.create("SPAN",{
			attrs:{id:"work_report_call_link"},
			props:{className:"wr-call-lable"},
			html:BX.message("JS_CORE_TMR_REPORT_FULL_" + this.PARENT.REPORT_FULL_MODE),
			events:{"click":BX.proxy(BXTIMEMAN.CheckNeedToReportImm,BXTIMEMAN)}
		});

		if(BXTIMEMAN.ShowCallReport == true)
		{
			this.call_report.style.display ="inline-block";
		}
		else
		{
			this.call_report.style.display = "none";
		}

		this.TABCONTROL.HEAD.appendChild(this.call_report);

		BX.addCustomEvent(this.PARENT, 'onTimeManDataRecieved', BX.delegate(this.CreateDashboard, this));
		BX.addCustomEvent('onPlannerQueryResult', BX.delegate(function(data){
			this.DATA.PLANNER = data;
			this.CreateDashboard(this.DATA);
		}, this));
		BX.addCustomEvent(this.PARENT, 'onTimeManDataRecieved', BX.delegate(this.CreateNoticeRow, this));
		BX.addCustomEvent(this.PARENT, 'onTimeManDataRecieved', BX.delegate(this.CreateMainRow, this));

		BX.addCustomEvent(this.PARENT, 'onTimeManDataRecieved', BX.delegate(function(DATA){
			if(!!DATA.PLANNER)
				this.update(DATA.PLANNER);
		}, this.PLANNER));
	}

	BX.onCustomEvent(this, 'onTimeManWindowBuild', [this, this.LAYOUT, DATA])

	this.Align();
}

BX.CTimeManWindow.prototype.CreateDashboard = function(DATA)
{
	var event_time, clock, state, tasks_counter;

	if (null == this.DASHBOARD)
	{
		this.DASHBOARD = BX.create('SPAN', {
			props: {className: 'tm-popup-dashboard'},
			events: {
				click: BX.proxy(this.Hide, this)
			},
			children: [
				BX.create('SPAN', {props:{className:'tm-dashboard-arrow'}}),
				BX.create('SPAN', {
					props:{className:'tm-dashboard-title'},
					html: BX.message('JS_CORE_TM_POPUP_HIDE')
				}),
				(event_time = BX.create('SPAN', {
					children: [
						BX.create('SPAN', {props:{className:'tm-dashboard-bell'}}),
						BX.create('SPAN', {props:{className:'tm-dashboard-text'}})
					]
				})),
				BX.create('SPAN', {props:{className:'tm-dashboard-clock'}}),
				BX.create('SPAN', {
					props: {className:'tm-dashboard-text'},
					children: [
						(clock = BX.create('SPAN', {props: {id: 'bx_tm_clock'}})),
						(state = BX.create('SPAN', {
							props: {className: 'tm-dashboard-subtext', id: 'bx_tm_state'}
						}))
					]
				}),
				(tasks_counter = BX.create('SPAN', {
					children: [
						BX.create('SPAN', {props:{className:'tm-dashboard-flag'}}),
						BX.create('SPAN', {props: {className: 'tm-dashboard-text'}})
					]
				}))
			]
		});

		new BX.CHint({parent: event_time, hint: BX.message('JS_CORE_HINT_EVENTS')});
		new BX.CHint({parent: state.parentNode, hint: BX.message('JS_CORE_HINT_STATE')});
		new BX.CHint({parent: tasks_counter, hint: BX.message('JS_CORE_HINT_TASKS')});
	}
	else
	{
		event_time = this.DASHBOARD.firstChild.nextSibling.nextSibling;
		clock = event_time.nextSibling.nextSibling.firstChild;
		state = clock.nextSibling;
		tasks_counter = this.DASHBOARD.lastChild;
	}

	if (DATA.PLANNER.TASKS_ENABLED && DATA.PLANNER.TASKS_COUNT > 0)
	{
		tasks_counter.lastChild.innerHTML = DATA.PLANNER.TASKS_COUNT;
		BX.show(tasks_counter);
	}
	else
	{
		BX.hide(tasks_counter);
	}

	if (!!DATA.PLANNER.EVENT_TIME)
	{
		event_time.lastChild.innerHTML = DATA.PLANNER.EVENT_TIME;
		BX.show(event_time);
	}
	else
	{
		BX.hide(event_time);
	}

	if (!clock.TIMER)
		clock.TIMER = BX.timer.clock(clock);

	if (DATA.STATE == 'OPENED')
	{
		if (state.TIMER)
		{
			state.TIMER.setFrom(new Date(DATA.INFO.DATE_START*1000));
			state.TIMER.dt = -DATA.INFO.TIME_LEAKS * 1000;
		}
		else
		{
			state.TIMER = BX.timer(state, {
				from: new Date(DATA.INFO.DATE_START*1000),
				dt: -DATA.INFO.TIME_LEAKS * 1000,
				display: 'worktime_timeman'
			});
		}
	}
	else
	{
		if (state.TIMER)
		{
			BX.timer.stop(state.TIMER);
			state.TIMER = null;
			BX.cleanNode(state);
		}

		if (DATA.STATE == 'PAUSED' || DATA.STATE == 'CLOSED' && DATA.CAN_OPEN != 'OPEN')
		{
			var q = (DATA.INFO.DATE_FINISH - DATA.INFO.DATE_START - DATA.INFO.TIME_LEAKS);
			state.innerHTML = BX.timeman.formatWorkTimeView(q, 'worktime_timeman');
		}
	}

	return this.DASHBOARD;
}

BX.CTimeManWindow.prototype.CreateLayoutTable = function()
{
	if (this.bindOptions.mode == 'popup')
	{
		this.LAYOUT = BX.create('DIV', {props: {className: 'tm-popup-content'}});
		return this.LAYOUT;
	}
	else
	{
		var t = BX.create('TABLE', {
			attrs: {cellSpacing: '0'}, props: {className: 'tm-popup-layout'},
			children: [BX.create('TBODY')]
		});

		var r = t.tBodies[0].insertRow(-1);

		var leftBorder = r.insertCell(-1);
		leftBorder.className = 'tm-popup-layout-left';
		leftBorder.appendChild(BX.create('DIV', {props: {className: 'tm-popup-layout-left-spacer'}}));

		var c = r.insertCell(-1);
		c.className = 'tm-popup-layout-center';

		var rightBorder = r.insertCell(-1);
		rightBorder.className = 'tm-popup-layout-right';
		rightBorder.appendChild(BX.create('DIV', {props: {className: 'tm-popup-layout-right-spacer'}}));

		r = t.tBodies[0].insertRow(-1);
		r.insertCell(-1).className = 'tm-popup-layout-left-corner';
		r.insertCell(-1).className = 'tm-popup-layout-center-corner';
		r.insertCell(-1).className = 'tm-popup-layout-right-corner';

		this.LAYOUT = c.appendChild(BX.create('DIV', {props: {className: 'tm-popup-content'}}));

		return t;
	}
}

BX.CTimeManWindow.prototype.CreateNoticeRow = function(DATA)
{
	var row_notice, row_timer, row_edit;

	if (null == this.NOTICE)
	{
		this.NOTICE = BX.create('DIV', {
			props: {className: 'tm-popup-notice'},
			children: [
				BX.create('SPAN', {props: {className: 'tm-popup-notice-left'}}),
				(row_notice = BX.create('SPAN', {props: {className: 'tm-popup-notice-text'}})),
				(row_timer = BX.create('SPAN', {props: {className: 'tm-popup-notice-time'}})),
				(row_edit = BX.create('SPAN', {
					props: {className: 'tm-popup-notice-pencil'},
					events: {
						click: BX.proxy(this.ShowEdit, this)
					}
				})),
				BX.create('SPAN', {props: {className: 'tm-popup-notice-right'}})
			]
		});
		this.NOTICE_STATE = _getStateHash(DATA, ['STATE', 'CAN_OPEN', 'CAN_EDIT']) + '/' + _getStateHash(DATA.INFO, ['DATE_START', 'DATE_FINISH', 'TIME_LEAKS']);
	}
	else
	{
		var newState = _getStateHash(DATA, ['STATE', 'CAN_OPEN', 'CAN_EDIT']) + '/' + _getStateHash(DATA.INFO, ['DATE_START', 'DATE_FINISH', 'TIME_LEAKS']);

		if (newState == this.NOTICE_STATE)
			return null;

		this.NOTICE_STATE = newState;

		if (this.NOTICE_TIMER)
		{
			BX.timer.stop(this.NOTICE_TIMER)
			this.NOTICE_TIMER = null;
		}

		row_notice = this.NOTICE.firstChild.nextSibling;
		row_timer = row_notice.nextSibling;
		row_edit = row_timer.nextSibling;
	}

	if (!DATA.ID || !DATA.CAN_EDIT || (DATA.STATE == 'EXPIRED' && DATA.REPORT_REQ != 'A'))
		BX.hide(row_edit);
	else
		BX.show(row_edit);

	if (DATA.STATE != 'EXPIRED')
	{
		BX.adjust(row_notice, {
			text: BX.message('JS_CORE_TM_WD_OPENED') + ' '
		});

		BX.show(row_timer);

		if (DATA.STATE == 'OPENED')
		{
			this.NOTICE_TIMER = BX.timer(row_timer, {from: DATA.INFO.DATE_START * 1000, accuracy: 1, dt: -1000 * DATA.INFO.TIME_LEAKS, display: 'worktime_notice_timeman'});
		}
		else if (DATA.CAN_OPEN == 'OPEN')
		{
			row_timer.innerHTML = BX.timeman.formatWorkTimeView(0, 'worktime_notice_timeman');
		}
		else
		{
			var q = (DATA.INFO.DATE_FINISH - DATA.INFO.DATE_START - DATA.INFO.TIME_LEAKS);
			row_timer.innerHTML = BX.timeman.formatWorkTimeView(q, 'worktime_notice_timeman');

			//this.NOTICE_TIMER = BX.timer(row_timer, {from: DATA.INFO.DATE_FINISH * 1000, accuracy: 1, dt: 1000 * DATA.INFO.TIME_LEAKS, display: 'worktime_notice_timeman'});
		}
	}
	else
	{
		BX.hide(row_timer);
		BX.adjust(row_notice, {text: BX.message('JS_CORE_TM_WD_EXPIRED')});
	}

	return this.NOTICE;
}

BX.CTimeManWindow.prototype.CreateMainRow = function(DATA)
{
	var row_pause;

	if (null == this.MAIN_ROW)
	{
		this.MAIN_ROW = BX.create('DIV', {props: {className: 'tm-popup-timeman'}});

		row_pause = this.MAIN_ROW.appendChild(BX.create('DIV', {
			props: {className: 'tm-popup-timeman-pause'},
			children: [
				BX.create('SPAN', {
					props: {
						className: 'tm-popup-timeman-pause-timer-caption'
					},
					text: BX.message('JS_CORE_TM_WD_PAUSED')
				}),
				BX.create('SPAN', {props: {className: 'tm-popup-timeman-pause-time'}})
			]
		}));

		var t = this.MAIN_ROW.appendChild(BX.create('TABLE', {
			attrs: {cellSpacing: '0'},
			props: {className: 'tm-popup-timeman-layout'},
			children: [BX.create('TBODY')]
		})),
		r = t.tBodies[0].insertRow(-1);

		this.MAIN_ROW_CELL_TIMER = r.insertCell(-1);
		this.MAIN_ROW_CELL_TIMER.className = 'tm-popup-timeman-layout-time';

		this.MAIN_ROW_CELL_BTN = r.insertCell(-1);
		this.MAIN_ROW_CELL_BTN.className = 'tm-popup-timeman-layout-button';

		this.MAIN_ROW_CELL_TIMER.appendChild(this.CreateMainPauseControl(DATA));

		this.MAIN_ROW_STATE = _getStateHash(DATA, ['STATE', 'CAN_OPEN', 'CAN_EDIT']) + '/' + _getStateHash(DATA.INFO, ['DATE_START', 'DATE_FINISH', 'TIME_LEAKS']);
	}
	else
	{
		var newState = _getStateHash(DATA, ['STATE', 'CAN_OPEN', 'CAN_EDIT']) + '/' + _getStateHash(DATA.INFO, ['DATE_START', 'DATE_FINISH', 'TIME_LEAKS']);
		if (newState == this.MAIN_ROW_STATE)
			return null;

		this.MAIN_ROW_STATE = newState;

		this.MAIN_ROW.className = 'tm-popup-timeman';
		BX.cleanNode(this.MAIN_ROW_CELL_BTN);

		row_pause = this.MAIN_ROW.firstChild;

		if (!!this.CLOCKWND)
		{
			this.CLOCKWND.Clear();
			this.CLOCKWND = null;
		}

	}

	if (this.PAUSE_TIMER)
	{
		BX.timer.stop(this.PAUSE_TIMER);
		this.PAUSE_TIMER = null;
	}


	if (DATA.STATE != 'PAUSED')
	{
		this.MAIN_ROW_CELL_TIMER.firstChild.className = 'webform-button tm-webform-button-pause';
		BX.hide(row_pause);
	}
	else
	{
		this.MAIN_ROW_CELL_TIMER.firstChild.className = 'webform-button tm-webform-button-play';
		BX.show(row_pause);

		this.PAUSE_TIMER = BX.timer(row_pause.lastChild, {
			from: DATA.INFO.DATE_FINISH * 1000,
			accuracy: 1,
			dt: 1000 * DATA.INFO.TIME_LEAKS,
			display: 'worktime_notice_timeman'
		});
	}

	var btn = 'OPEN';
	if (DATA.STATE == 'EXPIRED' || DATA.STATE == 'OPENED' || DATA.STATE == 'PAUSED')
		btn = 'CLOSE';
	else if (DATA.STATE == 'CLOSED' && DATA.CAN_OPEN == 'REOPEN')
		btn = 'REOPEN';

	this.MAIN_BTN_HANDLER = this.ACTIONS[btn];

	if (DATA.STATE != 'CLOSED' || DATA.CAN_OPEN)
	{
		this.MAIN_BUTTON = this.MAIN_ROW_CELL_BTN.appendChild(BX.create('DIV', {
			props: {className: 'tm-popup-button-handler'},
			events: {
				click: BX.proxy(this.MainButtonClick, this)
			},

			html: '<span class="webform-button tm-popup-main-button webform-button-' + (DATA.STATE != 'CLOSED' ? 'decline' : 'accept') + '"><span class="webform-button-left"></span><span class="webform-button-text">' + BX.message('JS_CORE_TM_' + btn) + '</span><span class="webform-button-right"></span></span>'
		}));

		if(!!DATA.SOCSERV_ENABLED)
		{
			this.MAIN_ROW_CELL_BTN.appendChild(BX.create('span', {
				props: {className: 'tm-popup-social-btn', id: 'tm_popup_social_btn'},
				events: {
					click: BX.proxy(function(){
						if(!this.SOCSERV_WND)
						{
							BX.loadScript(
								'/bitrix/tools/oauth/socserv.ajax.php?action=getsettings&site_id=' + BX.message('SITE_ID') + '&sessid=' + BX.bitrix_sessid()
							)
						}
						else
						{
							this.SOCSERV_WND.showWnd();
						}
					}, this)
				}
			}));
		}

		if (DATA.CAN_EDIT && DATA.STATE != 'PAUSED')
		{
			this.MAIN_ROW_CELL_BTN.appendChild(BX.create('SPAN', {
				props: {className: 'tm-popup-change-time-link'},
				events: {
					click: BX.proxy(this.ShowClock, this)
				},
				text: BX.message('JS_CORE_TM_CHTIME_' + DATA.STATE)
			}));
		}
	}
	else
	{
		this.MAIN_ROW_CELL_BTN.innerHTML = BX.message('JS_CORE_TM_CLOSED');
	}

	var className = 'tm-popup-timeman';

	if (DATA.STATE == 'PAUSED')
	{
		className += ' tm-popup-timeman-paused-mode'
	}

	// single button mode: day is expired or day is closed and cannot be reopened
	if (DATA.STATE == 'CLOSED' && DATA.CAN_OPEN != 'REOPEN')
	{
		className += ' tm-popup-timeman-button-mode tm-popup-timeman-change-time-mode';
	}
	else if (DATA.STATE == 'CLOSED' || DATA.STATE == 'EXPIRED')
	{
		className += ' tm-popup-timeman-button-mode';
	}
	// only unpause button mode: day is paused
	/*else if  (DATA.STATE == 'CLOSED' && DATA.CAN_OPEN == 'REOPEN')
	{
		className += ' tm-popup-timeman-time-mode';
	}*/
	else
	{
		className += ' tm-popup-timeman-buttons-mode' + (!DATA.TM_FREE && DATA.REPORT_REQ != 'A' ? '' : ' tm-popup-timeman-change-time-mode');
	}
	this.MAIN_ROW.className = className;

	return this.MAIN_ROW;
}

BX.CTimeManWindow.prototype.CreateMainPauseControl = function(DATA)
{
	var c = BX.create('SPAN', {
		events: {
			click: BX.proxy(this.PauseButtonClick, this)
		},
		html: '<span class="webform-button-left"></span><span class="webform-button-icon"></span><span class="webform-button-text text-pause">' + BX.message('JS_CORE_TM_PAUSE') + '</span><span class="webform-button-text text-play">' + BX.message('JS_CORE_TM_UNPAUSE') + '</span><span class="webform-button-right"></span>'
	});

	return c;
}

BX.CTimeManWindow.prototype.CreateReport = function()
{
	if (!this.REPORT)
		this.REPORT = new BX.CTimeManReport(this);

	return this.REPORT.Create();
}

BX.CTimeManWindow.prototype.CreateEvent = function(event, additional_props, fulldate)
{
	additional_props = additional_props || {};
	additional_props.className = 'tm-popup-event-name';
	fulldate = fulldate || false;

	if(!!event.DATE_FROM)
		event.DATE_FROM = event.DATE_FROM.split(' ')[0];
	if(!!event.DATE_TO)
		event.DATE_TO = event.DATE_TO.split(' ')[0];

	if(!!event.DATE_FROM && event.DATE_FROM == parseInt(event.DATE_FROM))
		event.DATE_FROM = BX.timeman.formatDate(event.DATE_FROM, false);
	if(!!event.DATE_TO && event.DATE_TO == parseInt(event.DATE_TO))
		event.DATE_TO = BX.timeman.formatDate(event.DATE_TO, false);

	if(!!event.TIME_FROM && event.TIME_FROM == parseInt(event.TIME_FROM))
		event.TIME_FROM = BX.timeman.formatTime(event.TIME_FROM, false);
	if(!!event.TIME_TO && event.TIME_TO == parseInt(event.TIME_TO))
		event.TIME_TO = BX.timeman.formatTime(event.TIME_TO, false);

	return BX.create('DIV', {
		props: {
			className: 'tm-popup-event',
			bx_event_id: event.ID
		},
		children: [
			BX.create('DIV', {
				props: {className: 'tm-popup-event-datetime'},
				html: '<span class="tm-popup-event-time-start' + (event.DATE_FROM_TODAY ? '' : ' tm-popup-event-time-passed') + '">'+(fulldate?event.DATE_FROM+' ':'')+event.TIME_FROM + '</span><span class="tm-popup-event-separator">-</span><span class="tm-popup-event-time-end' + (event.DATE_TO_TODAY ? '' : ' tm-popup-event-time-passed') + '">' +(fulldate?event.DATE_TO+' ':'')+ event.TIME_TO + '</span>'
			}),
			BX.create('DIV', {
				props: additional_props,
				events: event.ID ? {click: BX.proxy(this.showEvent, this)} : null,
				html: '<span class="tm-popup-event-text">' + BX.util.htmlspecialchars(event.NAME) + '</span>'
			})
		]
	});
}

BX.CTimeManWindow.prototype.EVENTWND = {};

BX.CTimeManWindow.prototype.showEvent = function(e)
{
	var event_id = BX.proxy_context.parentNode.bx_event_id;
	if (this.EVENTWND[event_id] && this.EVENTWND[event_id].node != BX.proxy_context)
	{
		this.EVENTWND[event_id].Clear();
		this.EVENTWND[event_id] = null;
	}

	if (!this.EVENTWND[event_id])
	{
		this.EVENTWND[event_id] = new BX.CTimeManEventPopup(this, {
			node: BX.proxy_context,
			bind: BX.proxy_context.BXPOPUPBIND || this.EVENTS.firstChild,// this.PARENT.CLOSE_DAY_FORM ? this.PARENT.CLOSE_DAY_FORM.listEvents : this.EVENTS.firstChild,
			id: event_id,
			angle_offset: BX.proxy_context.BXPOPUPANGLEOFFSET
		});
	}

	BX.onCustomEvent(this, 'onEventWndShow', [this.EVENTWND[event_id]]);

	this.EVENTWND[event_id].Show();

	return BX.PreventDefault(e);
}


BX.CTimeManWindow.prototype.CreateEventsForm = function(cb)
{
	var mt_format_css = BX.isAmPmMode() ? '_am_pm' : '';

	var handler = BX.delegate(function(e, bEnterPressed)
	{
		inp_Name.value = BX.util.trim(inp_Name.value);
		if (inp_Name.value && inp_Name.value!=BX.message('JS_CORE_TM_EVENTS_ADD'))
		{
			cb({
				from: inp_TimeFrom.value,
				to: inp_TimeTo.value,
				name: inp_Name.value,
				absence: inp_Absence.checked ? 'Y' : 'N'
			});

			BX.timer.start(inp_TimeFrom.bxtimer);
			BX.timer.start(inp_TimeTo.bxtimer);

			if (!bEnterPressed)
			{
				BX.addClass(inp_Name.parentNode, 'tm-popup-event-form-disabled')
				inp_Name.value = BX.message('JS_CORE_TM_EVENTS_ADD');
			}
			else
			{
				inp_Name.value = '';
			}
		}

		return (e || window.event) ? BX.PreventDefault(e) : null;
	}, this),

	handler_name_focus = function()
	{
		BX.removeClass(this.parentNode, 'tm-popup-event-form-disabled');
		if (this.value == BX.message('JS_CORE_TM_EVENTS_ADD'))
			this.value = '';
	};

	var inp_TimeFrom = BX.create('INPUT', {
		props: {type: 'text', className: 'tm-popup-event-start-time-textbox' + mt_format_css}
	});

	inp_TimeFrom.onclick = BX.delegate(function()
	{
		var cb = BX.delegate(function(value) {
			this.CLOCK.closeWnd();

			var oldvalue_From = BX.timeman.unFormatTime(inp_TimeFrom.value),
				oldvalue_To = BX.timeman.unFormatTime(inp_TimeTo.value);

			var diff = 3600;
			if (oldvalue_From && oldvalue_To)
				diff = oldvalue_To - oldvalue_From;

			BX.timer.stop(inp_TimeFrom.bxtimer);
			BX.timer.stop(inp_TimeTo.bxtimer);

			inp_TimeFrom.value = value;

			inp_TimeTo.value = BX.timeman.formatTime(BX.timeman.unFormatTime(value) + diff);

			inp_TimeTo.focus();
			inp_TimeTo.onclick();
		}, this);

		if (!this.CLOCK)
		{
			this.CLOCK = new BX.CTimeManClock(this, {
				start_time: BX.timeman.unFormatTime(inp_TimeFrom.value),
				node: inp_TimeFrom,
				callback: cb
			});
		}
		else
		{
			this.CLOCK.setNode(inp_TimeFrom);
			this.CLOCK.setTime(BX.timeman.unFormatTime(inp_TimeFrom.value));
			this.CLOCK.setCallback(cb);
		}

		inp_TimeFrom.blur();
		this.CLOCK.Show();
	}, this);

	inp_TimeFrom.bxtimer = BX.timer(inp_TimeFrom, {dt: 3600000, accuracy: 3600});

	var inp_TimeTo = BX.create('INPUT', {
		props: {type: 'text', className: 'tm-popup-event-end-time-textbox' + mt_format_css}
	});

	inp_TimeTo.onclick = BX.delegate(function()
	{
		var cb = BX.delegate(function(value) {
			this.CLOCK.closeWnd();
			inp_TimeTo.value = value;

			BX.timer.stop(inp_TimeFrom.bxtimer);
			BX.timer.stop(inp_TimeTo.bxtimer);

			inp_Name.focus();
			handler_name_focus.apply(inp_Name);
		}, this);

		if (!this.CLOCK)
		{
			this.CLOCK = new BX.CTimeManClock(this, {
				start_time: BX.timeman.unFormatTime(inp_TimeTo.value),
				node: inp_TimeTo,
				callback: cb
			});
		}
		else
		{
			this.CLOCK.setNode(inp_TimeTo);
			this.CLOCK.setTime(BX.timeman.unFormatTime(inp_TimeTo.value));
			this.CLOCK.setCallback(cb);
		}

		inp_TimeTo.blur();
		this.CLOCK.Show();
	}, this);

	inp_TimeTo.bxtimer = BX.timer(inp_TimeTo, {dt: 7200000, accuracy: 3600});

	var inp_Name = BX.create('INPUT', {
		props: {type: 'text', className: 'tm-popup-event-form-textbox' + mt_format_css, value: BX.message('JS_CORE_TM_EVENTS_ADD')},
		events: {
			keypress: function(e) {
				return (e.keyCode == 13) ? handler(e, true) : true;
			},
			blur: function() {
				if (this.value == '')
				{
					BX.addClass(this.parentNode, 'tm-popup-event-form-disabled');
					this.value = BX.message('JS_CORE_TM_EVENTS_ADD');
				}
			},
			focus: handler_name_focus
		}
	});

	var id = 'bx_tm_absence_' + Math.random();
	var inp_Absence = BX.create('INPUT', {
		props: {type: 'checkbox', className: 'checkbox', id: id}
	});

	this.EVENTS_FORM = BX.create('DIV', {
		props: {className: 'tm-popup-event-form tm-popup-event-form-disabled'},
		children: [
			inp_TimeFrom, inp_TimeTo, inp_Name,
			BX.create('SPAN', {
				props: {className: 'tm-popup-event-form-submit'},
				events: {
					click: handler
				}
			}),
			BX.create('DIV', {
				props: {className:'tm-popup-event-form-options'},
				children: [
					inp_Absence,
					BX.create('LABEL', {props: {htmlFor: id}, text: BX.message('JS_CORE_TM_EVENT_ABSENT')})
				]
			})
		]
	});

	return this.EVENTS_FORM;
}

BX.CTimeManWindow.prototype.CreateTaskCallback = function(t)
{
	this.PARENT.taskEntryAdd({
		name: t.name
	});

	this.TASKS_LIST.appendChild(BX.create('LI', {
		props: {className: 'tm-popup-task'},
		text: t.name
	}));
}

BX.CTimeManWindow.prototype.CreateTasks = function(DATA)
{
	if (!DATA.TASKS_ENABLED || this.PARENT.TASK_CHANGES.add.length > 0 || this.PARENT.TASK_CHANGES.remove.length > 0)
		return null;

	if (DATA.FULL === false && !!this.TASKS)
		return this.TASKS;

	if (null == this.TASKS)
	{
		this.TASKS = BX.create('DIV');

		this.TASKS.appendChild(BX.create('DIV', {
			props: {className: 'tm-popup-section tm-popup-section-tasks'},
			children: [
				BX.create('SPAN', {props: {className: 'tm-popup-section-left'}}),
				BX.create('SPAN', {
					props: {className: 'tm-popup-section-text'},
					text: BX.message('JS_CORE_TM_TASKS')
				}),
				(this.TASKS_LINK = BX.create('span', {
					props: {className: 'tm-popup-section-right-link'},
					events: {click: BX.proxy(this.ShowTasks, this)},
					text: BX.message('JS_CORE_TM_TASKS_CHOOSE')
				})),
				BX.create('SPAN', {props: {className: 'tm-popup-section-right'}})
			]
		}));

		this.TASKS.appendChild(BX.create('DIV', {
			props: {className: 'tm-popup-tasks'},
			children: [
			(this.TASKS_LIST = BX.create('OL', {
				props: {
					className: 'tm-popup-task-list'
				}
			})),
			this.CreateTasksForm(BX.proxy(this.CreateTaskCallback, this))
		]}));

		//this.TASKS_STATE = DATA.STATE + ':' + DATA.TASKS.length;
	}
	else
	{
		// var newState = DATA.STATE + ':' + DATA.TASKS.length;

		// if (newState == this.TASKS_STATE)
			// return;

		// this.TASKS_STATE = newState;

		BX.cleanNode(this.TASKS_LIST);
	}

	if (/*DATA.STATE == 'OPENED' && */DATA.TASKS && DATA.TASKS.length > 0)
	{
		var LAST_TASK = null;
		BX.removeClass(this.TASKS, 'tm-popup-tasks-empty');
		for (var i=0,l=DATA.TASKS.length; i<l; i++)
		{
			var q = this.TASKS_LIST.appendChild(BX.create('LI', {
				props: {
					className: 'tm-popup-task tm-popup-task-status-' + BX.timeman.TASK_SUFFIXES[DATA.TASKS[i].STATUS],
					bx_task_id: DATA.TASKS[i].ID
				},
				children:
				[
					BX.create('SPAN', {props: {className: 'tm-popup-task-icon'}}),
					BX.create('SPAN', {
						props: {
							className: 'tm-popup-task-name',
							BXPOPUPBIND: this.TASKS.firstChild
						},
						text: DATA.TASKS[i].TITLE,
						events: {click: BX.proxy(this.showTask, this)}
					}),
					BX.create('SPAN', {
						props: {className: 'tm-popup-task-delete'},
						events: {click: BX.proxy(this.removeTask, this)}
					})
				]
			}));

			if (DATA.TASK_LAST_ID && DATA.TASKS[i].ID == DATA.TASK_LAST_ID)
			{
				LAST_TASK = q;
			}
		}

		if (LAST_TASK)
		{
			setTimeout(BX.delegate(function()
			{
				if (LAST_TASK.offsetTop < this.TASKS_LIST.scrollTop || LAST_TASK.offsetTop + LAST_TASK.offsetHeight > this.TASKS_LIST.scrollTop + this.TASKS_LIST.offsetHeight)
				{
					this.TASKS_LIST.scrollTop = LAST_TASK.offsetTop - parseInt(this.TASKS_LIST.offsetHeight/2);
				}
			}, this), 10);
		}
	}
	else
	{
		BX.addClass(this.TASKS, 'tm-popup-tasks-empty');
	}

	/*
	if (DATA.STATE !== 'OPENED')
		BX.hide(this.TASKS);
	else
		BX.show(this.TASKS);
	*/

	return this.TASKS;
}

BX.CTimeManWindow.prototype.CreateTasksForm = function(cb)
{
	var handler = BX.delegate(function(e, bEnterPressed) {
		inp_Task.value = BX.util.trim(inp_Task.value);
		if (inp_Task.value && inp_Task.value!=BX.message('JS_CORE_TM_TASKS_ADD'))
		{
			cb({
				name: inp_Task.value
			});

			if (!bEnterPressed)
			{
				BX.addClass(inp_Task.parentNode, 'tm-popup-task-form-disabled')
				inp_Task.value = BX.message('JS_CORE_TM_TASKS_ADD');
			}
			else
			{
				inp_Task.value = '';
			}
		}

		return BX.PreventDefault(e);
	}, this);

	var inp_Task = BX.create('INPUT', {
		props: {type: 'text', className: 'tm-popup-task-form-textbox', value: BX.message('JS_CORE_TM_TASKS_ADD')},
		events: {
			keypress: function(e) {
				return (e.keyCode == 13) ? handler(e, true) : true;
			},
			blur: function() {
				if (this.value == '')
				{
					BX.addClass(this.parentNode, 'tm-popup-task-form-disabled');
					this.value = BX.message('JS_CORE_TM_TASKS_ADD');
				}
			},
			focus: function() {
				BX.removeClass(this.parentNode, 'tm-popup-task-form-disabled');
				if (this.value == BX.message('JS_CORE_TM_TASKS_ADD'))
					this.value = '';
			}
		}
	});

	BX.focusEvents(inp_Task);

	return BX.create('DIV', {
		props: {
			className: 'tm-popup-task-form tm-popup-task-form-disabled'
		},
		children: [
			inp_Task,
			BX.create('SPAN', {
				props: {className: 'tm-popup-task-form-submit'},
				events: {click: handler}
			})
		]
	});
}

BX.CTimeManWindow.prototype.ShowTasks = function()
{
	if (null == this.TASKSWND)
	{
		this.TASKSWND = new BX.CTimeManTasksSelector(this, {
			node: BX.proxy_context,
			onselect: BX.proxy(this.addTask, this)
		});
	}
	else
	{
		this.TASKSWND.setNode(BX.proxy_context);
	}

	this.TASKSWND.Show();
}

BX.CTimeManWindow.prototype.addTask = function(task_data)
{
	this.TASKS_LIST.appendChild(BX.create('LI', {
		props: {className: 'tm-popup-task'},
		text: task_data.name
	}));

	this.PARENT.taskPost({action: 'add', id: task_data.id});
}

BX.CTimeManWindow.prototype.removeTask = function(e)
{
	this.PARENT.taskPost({action: 'remove', id: BX.proxy_context.parentNode.bx_task_id});
	BX.cleanNode(BX.proxy_context.parentNode, true);

	return BX.PreventDefault(e);
}

BX.CTimeManWindow.prototype.showTask = function(e)
{
	var task_id = BX.proxy_context.parentNode.bx_task_id;

	var tasks = (this.data && this.data.INFO) ? this.data.INFO.TASKS : this.DATA.TASKS,
		arTasks = [];
	if (tasks.length > 0)
	{
		for(var i=0; i<tasks.length; i++)
			arTasks.push(tasks[i].ID);
		taskIFramePopup.tasksList = arTasks;
		taskIFramePopup.view(task_id);
	}

	return false;
}

BX.CTimeManWindow.prototype.ShowClock = function(error_string, start_time)
{
	if (!BX.type.isString(error_string))
		error_string = null;

	if (null == this.CLOCKWND)
	{
		this.CLOCKWND = new BX.CTimeManTimeSelector(this, {
			node: this.MAIN_BUTTON,
			error: error_string,
			start_time: start_time || (this.DATA.STATE == 'EXPIRED' && this.DATA.EXPIRED_DATE ? this.DATA.EXPIRED_DATE : null),
			free_mode: this.PARENT.FREE_MODE
		});
	}
	else
	{
		//this.CLOCKWND.CreateContent();
		this.CLOCKWND.setError(error_string);
		this.CLOCKWND.setNode(this.MAIN_BUTTON);

		if (this.DATA.STATE == 'EXPIRED' && this.DATA.EXPIRED_DATE)
			this.CLOCKWND.setTime(this.DATA.EXPIRED_DATE);
	}

	this.CLOCKWND.Show();
}

BX.CTimeManWindow.prototype.ShowEdit = function()
{
	if (null == this.EDITWND)
	{
		this.EDITWND = new BX.CTimeManEditPopup(this, {
			node: this.NOTICE,
			bind: this.NOTICE,
			entry: this.PARENT.DATA,
			free_mode: this.PARENT.FREE_MODE
		});
	}
	else
	{
		this.EDITWND.setNode(this.NOTICE);
		this.EDITWND.setData(this.PARENT.DATA);
	}

	this.EDITWND.Show();
}

BX.CTimeManWindow.prototype.PauseButtonClick = function(e)
{
	var action = this.PARENT.DATA.INFO.PAUSED ? this.ACTIONS.REOPEN : this.ACTIONS.PAUSE;
	return action(e);
}

BX.CTimeManWindow.prototype.MainButtonClick = function(e)
{
	this.PARENT.setTimestamp(this.TIMESTAMP);
	this.PARENT.setReport(this.ERROR_REPORT);

	if ((this.MAIN_BTN_HANDLER == this.ACTIONS.OPEN) && this.REPORT)
	{
		this.REPORT.Reset();
	}

	return this.MAIN_BTN_HANDLER(e);
}

BX.CTimeManWindow.prototype.clearTempData = function()
{
	this.TIMESTAMP = 0;
	this.ERROR_REPORT = '';
}

BX.CTimeManWindow.prototype.showReportField = function(error_string)
{
	this.ShowClock(error_string);
}

BX.CTimeManWindow.prototype.Align = function()
{
	if (!this.SHOW)
		return;

	if (this.bindOptions.mode != 'popup')
	{
		var wndSize = BX.GetWindowInnerSize();

		this.bindOptions.node = BX(this.bindOptions.node);
		if (this.bindOptions.node)
		{
			var pos = BX.pos(this.bindOptions.node),
				top = 0, left = 0;

			left = this.bindOptions.type[0] == 'right' || this.bindOptions.type[1] == 'right'
				? pos.right - 390
				: pos.left;

			top = this.bindOptions.type[0] == 'top' || this.bindOptions.type[1] == 'top'
				? pos.top
				: pos.bottom;

			if (this.bindOptions.offsetLeft)
				left += this.bindOptions.offsetLeft;
			if (this.bindOptions.offsetTop)
				top += this.bindOptions.offsetTop;
		}

		if (left <= 0)
			left = pos.left;

		this.DIV.style.left = left + 'px';
		this.DIV.style.top = top + 'px';
	}
}

BX.CTimeManWindow.prototype.isShown = function()
{
	if(this.bindOptions.mode == 'popup')
	{
		return !!this.POPUP && this.POPUP.isShown()
	}
	else
	{
		return !!this.SHOW;
	}
}

BX.CTimeManWindow.prototype.Show = function()
{
	if (!this.PARENT.DATA || !this.PARENT.DATA.STATE)
		return false;

	this.CREATE = true;

	if (null == this.DIV && null == this.POPUP)
		this.Create(this.PARENT.DATA);

	this.DATA = this.PARENT.DATA;

	if (this.bindOptions.mode == 'popup')
	{
		if (this.POPUP.isShown())
		{
			this.Hide();
			return false;
		}
		else
		{
			this.POPUP.show();
		}
	}
	else
	{
		if (this.DIV.style.display == 'block')
		{
			this.Hide()
			return false;
		}
		else
		{
			this.SHOW = true;
			this.Align();
			this.DIV.style.display = 'block';

			setTimeout(BX.proxy(this.onAfterShow, this), 10);

		}
	}

	BX.onCustomEvent('onTimeManWindowOpen', [this]);
	return true;
}

BX.CTimeManWindow.prototype.onAfterShow = function()
{
	BX.bind(document, 'click', BX.proxy(this.HideClick, this));
}

BX.CTimeManWindow.prototype.HideClick = function(e)
{
	if (e.button == 2) return true;
	this.Hide();
}

BX.CTimeManWindow.prototype.Hide = function()
{
	if (this.bindOptions.mode == 'popup')
	{
		this.POPUP.close();
	}
	else
	{
		BX.unbind(document, 'click', BX.proxy(this.HideClick, this));

		this.DIV.style.display = 'none';
		this.SHOW = false;
	}

	BX.onCustomEvent('onTimeManWindowClose', [this]);
}

/********************************************/

BX.CTimeManClock = function(parent, params)
{
	this.parent = parent;
	this.params = params;

	this.params.popup_buttons = this.params.popup_buttons || [
		new BX.PopupWindowButton({
			text : BX.message('JS_CORE_TM_EVENT_SET'),
			className : "popup-window-button-create",
			events : {click : BX.proxy(this.setValue, this)}
		})
	];

	this.isReady = false;

	var p = this.params.popup_config || {
		offsetLeft: -45,
		offsetTop: -135,
		autoHide: true,
		closeIcon: true,
		closeByEsc: true,
		zIndex: this.params.zIndex
	};

	p.lightShadow = true;

	this.WND = new BX.PopupWindow(
		this.params.popup_id || 'timeman_clock_popup',
		this.params.node,
		p
	);

	this.SHOW = false;
	BX.addCustomEvent(this.WND, "onPopupClose", BX.delegate(this.onPopupClose, this));

	this.obClocks = {};
	this.CLOCK_ID = this.params.clock_id || 'timeman_clock';
}

BX.CTimeManClock.prototype.Show = function()
{
	if (!this.isReady)
	{
		BX.timeman.showWait(this.parent.DIV);
		BX.addCustomEvent('onTMClockRegister', BX.proxy(this.onTMClockRegister, this));
		return BX.ajax.get(TMPoint, {action:'clock', start_time: this.params.start_time, clock_id: this.CLOCK_ID, sessid: BX.bitrix_sessid()}, BX.delegate(this.Ready, this));
	}

	this.WND.setButtons(this.params.popup_buttons);
	this.WND.show();

	this.SHOW = true;

	if (window['bxClock_' + this.obClocks[this.CLOCK_ID]])
	{
		setTimeout("window['bxClock_" + this.obClocks[this.CLOCK_ID] + "'].CalculateCoordinates()", 40);
	}

	return true;
}

BX.CTimeManClock.prototype.onTMClockRegister = function(obClocks)
{
	if (obClocks[this.CLOCK_ID])
	{
		this.obClocks[this.CLOCK_ID] = obClocks[this.CLOCK_ID];
		BX.removeCustomEvent('onTMClockRegister', BX.proxy(this.onTMClockRegister, this));
	}
}

BX.CTimeManClock.prototype.Ready = function(data)
{
	this.content = this.CreateContent(data);
	this.WND.setContent(this.content);

	this.isReady = true;
	BX.timeman.closeWait();

	setTimeout(BX.proxy(this.Show, this), 30);
}

BX.CTimeManClock.prototype.CreateContent = function(data)
{
	return BX.create('DIV', {
		events: {click: BX.PreventDefault},
		html:
			'<div class="bx-tm-popup-clock-wnd-title">' + BX.message('JS_CORE_CL') + '</div>'
			+ _createHR(true)
			+ '<div class="bx-tm-popup-clock">' + data + '</div>'
	});
}

BX.CTimeManClock.prototype.setValue = function(e)
{
	if (this.params.callback)
	{
		var input = BX.findChild(this.content, {tagName: 'INPUT'}, true);
		this.params.callback.apply(this.params.node, [input.value]);
	}

	return BX.PreventDefault(e);
}

BX.CTimeManClock.prototype.closeWnd = function(e)
{
	this.WND.close();
	return (e || window.event) ? BX.PreventDefault(e) : true;
}

BX.CTimeManClock.prototype.setNode = function(node)
{
	this.WND.setBindElement(node);
}

BX.CTimeManClock.prototype.setTime = function(timestamp)
{
	this.params.start_time = timestamp;
	if (window['bxClock_' + this.obClocks[this.CLOCK_ID]])
	{
		window['bxClock_' +  this.obClocks[this.CLOCK_ID]].SetTime(parseInt(timestamp/3600), parseInt((timestamp%3600)/60));
	}
}

BX.CTimeManClock.prototype.setCallback = function(cb)
{
	this.params.callback = cb;
}

BX.CTimeManClock.prototype.onPopupClose = function()
{
	this.SHOW = false;
}

/*********************************************************************/

BX.CTimeManTimeSelector = function(parent, params)
{
	params = params || {};

	params.popup_id = 'timeman_time_selector_popup' + Math.random();
	params.popup_config = {
		offsetLeft: -50,
		offsetTop: -30,
		autoHide: true,
		closeIcon: true,
		closeByEsc: true
	};

	this.free_mode = !!params.free_mode;

	params.popup_buttons = [
		new BX.PopupWindowButton({
			text : parent.MAIN_BUTTON.textContent || parent.MAIN_BUTTON.innerText,
			className : parent.DATA.STATE == "CLOSED" ? "popup-window-button-accept" : "popup-window-button-decline",
			events : {click : BX.proxy(this.setValue, this)}
		}),
		new BX.PopupWindowButtonLink({
			text : BX.message('JS_CORE_TM_B_CLOSE'),
			className : "popup-window-button-link-cancel",
			events : {click : BX.proxy(this.closeWnd, this)}
		})
	];

	BX.CTimeManTimeSelector.superclass.constructor.apply(this, [parent, params]);

	this.CLOCK_ID = 'timeman_report_clock';
}
BX.extend(BX.CTimeManTimeSelector, BX.CTimeManClock);

BX.CTimeManTimeSelector.prototype.CreateContent = function(data)
{
	if (!this.content)
	{
		var table = BX.create('TABLE'),
			row = (table.appendChild(BX.create('TBODY'))).insertRow(-1);

		var cell = row.insertCell(-1);
		cell.innerHTML = '<div class="bx-tm-popup-clock-wnd-clock">' + data + '</div>';

		if (!this.free_mode)
		{
			cell = row.insertCell(-1);
			cell.appendChild(BX.create('DIV', {
				props: {className: 'bx-tm-popup-clock-wnd-report'},
				children: [
					(this.content_subtitle = BX.create('DIV', {
						props: {
							className: 'bx-tm-popup-clock-wnd-subtitle'
						},
						html: BX.message('JS_CORE_TM_CHTIME_CAUSE')
					})),
					(this.REPORT = BX.create('TEXTAREA'))
				]
			}));
		}
		else
		{
			table.setAttribute('align', 'center');
		}

		this.content = (BX.create('DIV', {
			props: {className: 'bx-tm-popup-clock-wnd bx-tm-popup-time-selector-wnd'},
			children: [
				(this.content_title = BX.create('DIV', {
					props: {className: 'bx-tm-popup-clock-wnd-title'},
					html: BX.message('JS_CORE_TM_CHTIME_' + this.parent.DATA.STATE)
				})),
				_createHR(),
				BX.create('DIV', {
					props: {className: this.free_mode ? 'bx-tm-popup-clock-free-mode' : ''},
					children: [table]
				})
			]
		}));
	}
	else
	{
		this.content_title.innerHTML = BX.message('JS_CORE_TM_CHTIME_' + this.parent.DATA.STATE);
		this.content_subtitle.innerHTML = this.parent.DATA.STATE != 'EXPIRED' ? BX.message('JS_CORE_TM_CHTIME_CAUSE') : '&nbsp;';
	}

	//this.setError(this.params.error);
	return this.content;
}

BX.CTimeManTimeSelector.prototype.setValue = function(e)
{
	var r = this.REPORT ? BX.util.trim(this.REPORT.value) : '';

	if (this.free_mode || r.length > 0)
	{
		var input = BX.findChild(this.content, {tagName: 'INPUT'}, true);

		this.parent.TIMESTAMP = BX.timeman.unFormatTime(input.value);
		this.parent.ERROR_REPORT = this.free_mode ? '' : r;
		this.parent.MainButtonClick(e);

		this.SHOW = false;
		//this.REPORT.value = '';
		//this.WND.close();
	}
	else
	{
		this.REPORT.className = 'bx-tm-popup-clock-wnd-report-error';
		this.REPORT.focus();
		this.REPORT.onkeypress = function() {this.className = '';this.onkeypress = null;};
	}
}

BX.CTimeManTimeSelector.prototype.setError = function(error)
{
	if (error)
	{
		if (confirm(error))
		{
			this.setValue();
			this.WND.close();
		}
	}
}

BX.CTimeManTimeSelector.prototype.setNode = function(node)
{
	BX.CTimeManTimeSelector.superclass.setNode.apply(this, arguments);
	this.params.popup_buttons[0].setName(node.textContent || node.innerText);
}

BX.CTimeManTimeSelector.prototype.Clear = function()
{
	// if (this.REPORT)
		// this.REPORT.value = '';

	// var now = new Date();
	// window.bxClock_timeman_report_clock.SetTime(now.getHours(), now.getMinutes());

	this.closeWnd();
}

/************************************************************************/

BX.CTimeManEditPopup = function(parent, params)
{
	params = params || {};

	this.mode = params.mode = params.mode || 'edit';
	this.free_mode = !!params.free_mode;

	params.popup_id = 'timeman_edit_popup_' + (Math.random() * 100000);
	params.popup_config = {
		offsetLeft: -50,
		offsetTop: -30,
		autoHide: true,
		closeIcon: true,
		closeByEsc: true
	};

	this.bChanged = false;
	params.popup_buttons = [
		(this.SAVEBUTTON = new BX.PopupWindowButton({
			text : BX.message('JS_CORE_TM_B_SAVE'),
			className : "popup-window-button",
			events : {click : BX.proxy(this.setValue, this)}
		})),
		new BX.PopupWindowButtonLink({
			text : BX.message('JS_CORE_TM_B_CLOSE'),
			className : "popup-window-button-link-cancel",
			events : {click : BX.proxy(this.closeWnd, this)}
		})
	];

	BX.CTimeManEditPopup.superclass.constructor.apply(this, [parent, params]);

	this.checkEntry();

	this.CLOCK_ID = 'timeman_edit_from';
	this.CLOCK_ID_1 = 'timeman_edit_to';
	this.obClocks = {};

	this.arInputs = {};
	this.arPause = [];

	BX.addCustomEvent(this.parent.PARENT, 'onTimeManDataRecieved', BX.proxy(this.setData, this));
}
BX.extend(BX.CTimeManEditPopup, BX.CTimeManClock);

BX.CTimeManEditPopup.prototype.setData = function(data)
{
	this.params.entry = data;

	if (this.isReady)
	{
		this.CreateContent();
	}
}

BX.CTimeManEditPopup.prototype.checkEntry = function()
{
	this.params.entry.INFO.TIME_START = parseInt(this.params.entry.INFO.TIME_START);
	this.params.entry.INFO.TIME_FINISH = parseInt(this.params.entry.INFO.TIME_FINISH);
	this.params.entry.INFO.DURATION = parseInt(this.params.entry.INFO.DURATION);

	this.date_start = new Date(this.params.entry.INFO.DATE_START * 1000);
	this.date_finish = this.params.entry.INFO.DATE_FINISH
		? new Date(this.params.entry.INFO.DATE_FINISH * 1000)
		: new Date();

	this.timezone_diff = (this.date_start.getHours() - Math.floor(this.params.entry.INFO.TIME_START/3600))*3600000;

	this.today = (new Date((new Date).valueOf()-this.timezone_diff));

	this.bFinished = this.params.entry.STATE == 'CLOSED';
	this.bExpired = this.params.entry.STATE == 'EXPIRED';

	if (this.bExpired)
	{
		this.bChanged = true;
		this.params.entry.EXPIRED_DATE = parseInt(this.params.entry.EXPIRED_DATE);

		this.SetSaveButton({caption: BX.message('JS_CORE_TM_CLOSE'), className: 'popup-window-button-decline'});
	}
}

BX.CTimeManEditPopup.prototype.SetSaveButton = function(params)
{
	if (!params)
		params = {caption: BX.message('JS_CORE_TM_B_SAVE'), className: ''};

	if (params.className || params.className === '')
		this.SAVEBUTTON.setClassName('popup-window-button ' + params.className);
	if (params.caption)
		this.SAVEBUTTON.setName(params.caption);
}

BX.CTimeManEditPopup.prototype.Show = function()
{
	if (!this.isReady)
	{
		BX.addCustomEvent('onTMClockRegister', BX.proxy(this.onTMClockRegister, this));
		BX.timeman.showWait(this.parent.DIV);
		return BX.ajax.get(TMPoint, {
			action:'clock',
			clock_id: this.CLOCK_ID,
			clock_id_1: this.CLOCK_ID_1,
			start_time: this.params.entry.INFO.TIME_START,
			start_time_1: this.params.entry.INFO.TIME_FINISH || this.params.entry.EXPIRED_DATE || '',
			sessid: BX.bitrix_sessid()
		}, BX.delegate(this.Ready, this));
	}

	BX.CTimeManEditPopup.superclass.Show.apply(this, arguments);

	if (!this.bOnChangeSet)
		setTimeout(BX.proxy(this.SetClockOnChange, this), 20);

	return true;
}

BX.CTimeManEditPopup.prototype.onTMClockRegister = function(obClocks)
{
	if (obClocks[this.CLOCK_ID])
	{
		this.obClocks[this.CLOCK_ID] = obClocks[this.CLOCK_ID];
		this.obClocks[this.CLOCK_ID_1] = obClocks[this.CLOCK_ID_1];

		BX.removeCustomEvent('onTMClockRegister', BX.proxy(this.onTMClockRegister, this));
	}
}

BX.CTimeManEditPopup.prototype.SetClockOnChange = function()
{
	if (this.bOnChangeSet)
		return;

	var arInputs = BX.findChildren(this.CLOCKS_CONTAINER, {tagName: 'INPUT', property: {type: 'hidden'}}, true);
	for (var i=0; i<arInputs.length; i++)
	{
		this.arInputs[arInputs[i].name] = arInputs[i];
		this.arInputs[arInputs[i].name].BXORIGINALVALUE = ''
		this.arInputs[arInputs[i].name].onchange = BX.proxy(this._input_onchange, this);
	}

	this.bOnChangeSet = true;
}

BX.CTimeManEditPopup.prototype._input_onchange = function()
{
	var input = BX.proxy_context,
		v1 = this.arInputs.timeman_edit_from.value.split(':'),
		v2 = this.arInputs.timeman_edit_to.value.split(':');

	if (input.BXORIGINALVALUE === '')
	{
		input.BXORIGINALVALUE = (this.bExpired && input.name == 'timeman_edit_to') ? 0 : input.value;
	}
	else if (input.value != input.BXORIGINALVALUE)
	{
		if (input.name == 'timeman_edit_to' && !this.bFinished && !this.bExpired)
		{
			this.SetSaveButton({className: "popup-window-button-decline", caption: BX.message('JS_CORE_TM_CLOSE')});
		}
		else if (this.SAVEBUTTON.text != BX.message('JS_CORE_TM_CLOSE'))
		{
			this.SetSaveButton({className: "popup-window-button-create"})
		}

		this.bChanged = true;
	}

	v1[0] = parseInt(v1[0], 10);
	v2[0] = parseInt(v2[0], 10);

	if (BX.isAmPmMode() && v1[0] < 12 && /pm/i.test(v1[1]))
		v1[0] += 12;

	if (BX.isAmPmMode() && v2[0] < 12 && /pm/i.test(v2[1]))
		v2[0] += 12;

	v1[1] = parseInt(v1[1], 10);
	v2[1] = parseInt(v2[1], 10);

	if (v1[0]*3600 + v1[1]*60 >= v2[0]*3600 + v2[1]*60)
	{
		if (input.name == 'timeman_edit_from')
			window['bxClock_' + this.obClocks[this.CLOCK_ID]].SetTime.apply(window['bxClock_' + this.obClocks[this.CLOCK_ID]], v2[1] > 0 ? [v2[0], v2[1]-5] : [v2[0]-1, 55]);
		else
		{
			window['bxClock_' + this.obClocks[this.CLOCK_ID_1]].SetTime.apply(window['bxClock_' + this.obClocks[this.CLOCK_ID_1]], v1[1] < 55 ? [v1[0], v1[1]+5] : [v1[0]+1, 0]);
		}
	}
}

BX.CTimeManEditPopup.prototype.CreateContent = function(data)
{
	if (!this.content)
	{
		var arChildren = [
			(this.content_title = BX.create('DIV', {
				props: {className: 'bx-tm-popup-clock-wnd-title'},
				html: BX.message('JS_CORE_TM_CHTIME_DAY')
			})),
			_createHR(),
			BX.create('DIV', {
				props: {className: 'bx-tm-popup-edit-clock-wnd-clock'},
				html: '<span class="bx-tm-clock-caption">'+BX.message('JS_CORE_TM_ARR')+'</span><span class="bx-tm-clock-caption">'+BX.message('JS_CORE_TM_DEP')+'</span>'
			}),
			(this.CLOCKS_CONTAINER = BX.create('DIV', {
				props: {className: 'bx-tm-popup-edit-clock-wnd-clock'},
				html: data
			}))
		];

		if (this.mode == 'edit' && !this.free_mode)
		{
			arChildren.push(_createHR());
			arChildren.push(BX.create('DIV', {
				props: {className: 'bx-tm-popup-clock-wnd-report'},
				children: [
					BX.create('DIV', {
						props: {
							className: 'bx-tm-popup-clock-wnd-subtitle'
						},
						html: BX.message('JS_CORE_TM_CHTIME_CAUSE')
					}),
					(this.REPORT = BX.create('TEXTAREA'))
				]
			}));
		}
		arChildren.push(BX.create('DIV', {style: {height: '1px', clear: 'both'}}));

		this.content = (BX.create('DIV', {
			props: {className: 'bx-tm-popup-clock-wnd bx-tm-popup-edit-clock-wnd'},
			children: arChildren
		}));

		this.WNDSTATE = _getStateHash(this.params.entry, ['STATE', 'CAN_EDIT']) + '/' + _getStateHash(this.params.entry.INFO, ['DATE_START', 'DATE_FINISH', 'TIME_LEAKS']);
	}
	else
	{
		var newState = _getStateHash(this.params.entry, ['STATE', 'CAN_EDIT']) + '/' + _getStateHash(this.params.entry.INFO, ['DATE_START', 'DATE_FINISH', 'TIME_LEAKS']);

		if (newState == this.WNDSTATE)
			return true;

		this.WNDSTATE = newState;

		this.restoreButtons();

		// window.bxClock_timeman_edit_from.SetTime(this.date_start.getHours(), this.date_start.getMinutes());
		// window.bxClock_timeman_edit_to.SetTime(this.date_finish.getHours(), this.date_finish.getMinutes());

		if (this.CONT_PAUSEEDITOR)
		{
			if (this.HINT_PAUSEEDITOR)
			{
				this.HINT_PAUSEEDITOR.Destroy();
				this.HINT_PAUSEEDITOR = null;
			}

			this.CONT_PAUSEEDITOR.parentNode.removeChild(this.CONT_PAUSEEDITOR);
			this.CONT_PAUSEEDITOR = null;
		}

		if (this.CONT_DURATION)
		{
			this.CONT_DURATION.parentNode.removeChild(this.CONT_DURATION);
			this.CONT_DURATION = null;
		}
	}

	if (this.params.entry.CAN_EDIT || this.params.entry.INFO.CAN_EDIT == 'Y')
	{
		this.INPUT_TIME_LEAKS = BX.create('INPUT', {
			props: {
				type: 'text',
				className: 'bx-tm-report-edit',
				value: BX.timeman.formatTime(this.params.entry.INFO.TIME_LEAKS || 0, false, true)
			},
			style: {
				width: '40px'
			},
			events: {
				change: BX.proxy(this._input_onchange, this)
			}
		});
	}
	else
	{
		this.INPUT_TIME_LEAKS = BX.create('INPUT', {
			props: {
				disabled: true,
				type: 'text',
				className: 'bx-tm-report-edit',
				value: BX.timeman.formatTime(this.params.entry.INFO.TIME_LEAKS || 0, false, true)
			},
			style: {
				width: '40px'
			}
		})
	}

	this.CONT_PAUSEEDITOR = this.content.insertBefore(BX.create('DIV', {
		props: {className: 'bx-tm-popup-clock-wnd-report'},
		children: [
			BX.create('DIV', {
				props: {
					className: 'bx-tm-popup-clock-wnd-subtitle'
				},
				html: BX.message('JS_CORE_TM_WD_PAUSED_1')
			}),
			BX.create('DIV', {
				props: {className: 'bx-tm-edit-section'},
				children:
				[
					this.INPUT_TIME_LEAKS
				]
			})
		]
	}), this.CLOCKS_CONTAINER.nextSibling);

	this.INPUT_TIME_LEAKS.BXORIGINALVALUE = this.INPUT_TIME_LEAKS.value;
	if (!!this.PAUSE_TIMER)
		BX.timer.stop(this.PAUSE_TIMER);

	if (this.params.entry.STATE == 'PAUSED')
	{
		var q = this.INPUT_TIME_LEAKS.parentNode.appendChild(BX.create('SPAN', {props: {className: 'bx-tm-field'}, html: '+'}));
		this.PAUSE_TIMER = BX.timer(q.appendChild(BX.create('SPAN')), {
			from: this.params.entry.INFO.DATE_FINISH * 1000,
			accuracy: 1
		});
	}

	this.content.insertBefore(_createHR(), this.CLOCKS_CONTAINER.nextSibling);

	var d = this.params.entry.INFO.DURATION > 0
		? this.params.entry.INFO.DURATION
		: (!!this.params.entry.INFO.TIME_FINISH && !isNaN(this.params.entry.INFO.TIME_FINISH)
			? this.params.entry.INFO.TIME_FINISH - this.params.entry.INFO.TIME_START - this.params.entry.INFO.TIME_LEAKS
			: (
				this.bExpired
				? this.params.entry.EXPIRED_DATE - this.params.entry.INFO.TIME_START - this.params.entry.INFO.TIME_LEAKS
				: parseInt((new Date()).valueOf()/1000) - parseInt(this.params.entry.INFO.DATE_START) - parseInt(this.params.entry.INFO.TIME_LEAKS)
			)
		);

	this.CONT_DURATION = this.content.insertBefore(BX.create('DIV', {
		props: {className: 'bx-tm-field'},
		children: [
			BX.create('SPAN', {props: {className: 'bx-tm-report-caption'}, text: BX.message('JS_CORE_TM_WD_OPENED') + ' '}),
			BX.create('SPAN', {
				props: {className: 'bx-tm-report-field', bx_tm_tag: 'DURATION'},
				html: BX.timeman.formatWorkTime(d)
			})
		]
	}), this.CLOCKS_CONTAINER.nextSibling);

	return this.content;
}

BX.CTimeManEditPopup.prototype.setValue = function(e)
{
	if (!this.bChanged)
		return;

	var v, r = this.free_mode ? '' : (this.mode == 'edit' ? BX.util.trim(this.REPORT.value) : 'modified by admin');

	if (this.free_mode || r.length > 0)
	{
		var data = {};

		if (this.arInputs[this.CLOCK_ID].value != this.arInputs[this.CLOCK_ID].BXORIGINALVALUE)
		{
			data[this.CLOCK_ID] = BX.timeman.unFormatTime(this.arInputs[this.CLOCK_ID].value);
		}

		if (this.arInputs[this.CLOCK_ID_1].value != this.arInputs[this.CLOCK_ID_1].BXORIGINALVALUE)
		{
			data[this.CLOCK_ID_1] = BX.timeman.unFormatTime(this.arInputs[this.CLOCK_ID_1].value);
		}

		if (this.INPUT_TIME_LEAKS.value != this.INPUT_TIME_LEAKS.BXORIGINALVALUE)
		{
			data.TIME_LEAKS = BX.timeman.unFormatTime(this.INPUT_TIME_LEAKS.value);
		}

		data.report = r;

		this.parent.PARENT.Query('save', data, BX.proxy(this.parent.PARENT._Update, this.parent.PARENT));

		this.bChanged = false;
		this.restoreButtons();

		this.SHOW = false;

		if (this.REPORT)
			this.REPORT.value = '';

		this.arInputs[this.CLOCK_ID].value = this.arInputs[this.CLOCK_ID].BXORIGINALVALUE;
		this.arInputs[this.CLOCK_ID_1].value = this.arInputs[this.CLOCK_ID_1].BXORIGINALVALUE;
		this.arPause = [];

		this.WND.close();
	}
	else
	{
		this.REPORT.className = 'bx-tm-popup-clock-wnd-report-error';
		this.REPORT.focus();
		this.REPORT.onkeypress = function() {this.className = '';this.onkeypress = null;};
	}
}

BX.CTimeManEditPopup.prototype.Clear = function()
{
	window.bxClock_timeman_edit_from = null;
	window.bxClock_timeman_edit_to = null;

	if (this.WND)
	{
		this.WND.close();
		this.WND.destroy();
		this.WND = null;
	}

}

BX.CTimeManEditPopup.prototype.restoreButtons = function()
{
	this.SetSaveButton();
	this.checkEntry();
}

/************************************************************/

BX.CTimeManTasksSelector = function(parent, params)
{
	this.parent = parent;
	this.params = params;

	this.isReady = false;
	this.WND = BX.PopupWindowManager.create(
		'timeman_tasks_selector_' + parseInt(Math.random() * 10000), this.params.node,
		{
			autoHide: true,
			content: (this.content = BX.create('DIV')),
			buttons: [
				new BX.PopupWindowButtonLink({
					text : BX.message('JS_CORE_TM_B_CLOSE'),
					className : "popup-window-button-link-cancel",
					events : {click : function(e) {this.popupWindow.close();return BX.PreventDefault(e);}}
				})
			]
		}
	);
}

BX.CTimeManTasksSelector.prototype.Show = function()
{
	if (!this.isReady)
	{
		var suffix = parseInt(Math.random() * 10000);
		window['TIMEMAN_ADD_TASK_' + suffix] = BX.proxy(this.setValue, this);

		BX.timeman.showWait();
		return BX.ajax.get(TMPoint, {action:'tasks', suffix: suffix, sessid: BX.bitrix_sessid()}, BX.delegate(this.Ready, this));
	}

	return this.WND.show();
}

BX.CTimeManTasksSelector.prototype.Hide = function()
{
	this.WND.close();
}

BX.CTimeManTasksSelector.prototype.Ready = function(data)
{
	this.content.innerHTML = data;

	this.isReady = true;
	this.Show();
	BX.timeman.closeWait();
}

BX.CTimeManTasksSelector.prototype.setValue = function(task)
{
	this.params.onselect(task)
	this.WND.close();
}

BX.CTimeManTasksSelector.prototype.setNode = function(node)
{
	this.WND.setBindElement(node);
}
/***************************************************************************/

BX.CTimeManPopup = function(node, bind, popup_id, popup_additional)
{
	this.node = node;
	this.popup_id = popup_id;

	popup_additional = popup_additional || {};

	var ie7 = false;
	/*@cc_on
		 @if (@_jscript_version <= 5.7)
			ie7 = true;
		/*@end
	@*/

	this.popup = BX.PopupWindowManager.create(this.popup_id, bind, {
		closeIcon : {right: "12px", top: "10px"},
		offsetLeft : ie7 || (document.documentMode && document.documentMode <= 7) ? -347 : -340,
		autoHide: true,
		bindOptions : {
			forceBindPosition : true,
			forceTop : true
		},
		angle : {
			position: "right",
			offset : popup_additional.angle_offset || 27
		}
	});
}

BX.CTimeManPopup.prototype.Show = function()
{
	this.popup.setTitleBar({content: this.GetTitle()});
	this.popup.setContent(this.GetContent());
	this.popup.setButtons(this.GetButtons());

	var offset = 0;
	if (this.node && this.node.parentNode && this.node.parentNode.parentNode)
		offset = this.node.parentNode.offsetTop - this.node.parentNode.parentNode.scrollTop;

	this.popup.setOffset({offsetTop: this.params.offsetTop || (offset - 20)});
	//popup.setAngle({ offset : 27 });
	this.popup.adjustPosition();
	this.popup.show();
}

BX.CTimeManPopup.prototype.setNode = function(node)
{
	this.node = node;
	this.popup.setBindElement(node);
}

BX.CTimeManPopup.prototype.Clear = function()
{
	if (this.popup)
	{
		this.popup.close();
		this.popup.destroy();
		this.popup = null;
	}

	this.node = null;
}

BX.CTimeManPopup.prototype.GetTitle = function(){return '';}
BX.CTimeManPopup.prototype.GetContent = function(){return '';}
BX.CTimeManPopup.prototype.GetButtons = function(){return [];}

/**************************/
BX.CTimeManEventPopup = function(parent, params)
{
	this.parent = parent;
	this.params = params;

	BX.CTimeManEventPopup.superclass.constructor.apply(this, [this.params.node, this.params.bind, 'event_' + this.params.id, this.params]);

	BX.addCustomEvent(this.parent, 'onTaskWndShow', BX.delegate(this.onEventWndShow, this))
	BX.addCustomEvent(this.parent, 'onEventWndShow', BX.delegate(this.onEventWndShow, this))

	this.bSkipShow = false;
	this.isReady = false;
}
BX.extend(BX.CTimeManEventPopup, BX.CTimeManPopup);

BX.CTimeManEventPopup.prototype.onEventWndShow = function(wnd)
{
	if (wnd != this)
	{
		if (this.popup)
			this.popup.close();
		else
			this.bSkipShow = true;
	}
}

BX.CTimeManEventPopup.prototype.Show = function(data)
{
	data = data || this.data;

	if (data && data.error)
		return;

	if (!data)
	{
		BX.timeman.showWait();
		return BX.timeman_query('calendar_show', {id: this.params.id}, BX.proxy(this.Show, this));
	}
	else if (BX.type.isArray(data) && data.length == 0)
	{
		if (this.popup)
			this.popup.close();

		if (this.parent.PARENT)
			this.parent.PARENT.Update();

		return false;
	}

	this.data = data;

	if (this.bSkipShow)
		this.bSkipShow = true;
	else
		BX.CTimeManEventPopup.superclass.Show.apply(this);

	return true;
}

BX.CTimeManEventPopup.prototype.GetContent = function()
{
	var html = '<div class="tm-event-popup">'
	html += '<div class="tm-popup-title"><a class="tm-popup-title-link" href="' + this.data.URL + '">' + BX.util.htmlspecialchars(this.data.NAME) +'</a></div>';
	if (this.data.DESCRIPTION)
	{
		html += _createHR(true);
		html += '<div class="tm-event-popup-description">' + this.data.DESCRIPTION + '</div>';
	}

	html += _createHR(true);

	html += '<div class="tm-event-popup-time"><div class="tm-event-popup-time-interval">' + this.data.DATE_F + '</div>';
	if (this.data.DATE_F_TO)
		html += '<div class="tm-event-popup-time-hint">(' + this.data.DATE_F_TO + ')</div></div>'


	if (this.data.GUESTS)
	{
		html += _createHR(true);
		html += '<div class="tm-event-popup-participants">';

		if (this.data.HOST)
		{
			html += '<div class="tm-event-popup-participant"><div class="tm-event-popup-participant-status tm-event-popup-participant-status-accept"></div><div class="tm-event-popup-participant-name"><a class="tm-event-popup-participant-link" href="' + this.data.HOST.url + '">' + this.data.HOST.name + '</a><span class="tm-event-popup-participant-hint">' + BX.message('JS_CORE_HOST') + '</span></div></div>';
		}

		if (this.data.GUESTS.length > 0)
		{
			html += '<table cellspacing="0" class="tm-event-popup-participants-grid"><tbody><tr>';

			var d = Math.ceil(this.data.GUESTS.length/2),
				grids = ['',''];

			for (var i=0;i<this.data.GUESTS.length; i++)
			{
				var status = '';
				if (this.data.GUESTS[i].status == 'Y')
					status = 'tm-event-popup-participant-status-accept';
				else if (this.data.GUESTS[i].status == 'N')
					status = 'tm-event-popup-participant-status-decline';

				grids[i<d?0:1] += '<div class="tm-event-popup-participant"><div class="tm-event-popup-participant-status ' + status + '"></div><div class="tm-event-popup-participant-name"><a class="tm-event-popup-participant-link" href="' + this.data.GUESTS[i].url + '">' + this.data.GUESTS[i].name + '</a></div></div>';
			}

			html += '<td class="tm-event-popup-participants-grid-left">' + grids[0] + '</td><td class="tm-event-popup-participants-grid-right">' + grids[1] + '</td>';

			html += '</tr></tbody></table>';

		}

		html += '</div>';
	}

	html += '</div>';

	return html;
}

BX.CTimeManEventPopup.prototype.Query = function(str)
{
	BX.ajax({
		method: 'GET',
		url: this.data.URL + '&' + str,
		processData: false,
		onsuccess: BX.proxy(this._Query, this)
	});
}

BX.CTimeManEventPopup.prototype._Query = function()
{
	this.data = null;this.Show();
}

BX.CTimeManEventPopup.prototype.GetButtons = function()
{
	var btns = [], q = BX.proxy(this.Query, this);

	if (this.data.STATUS === 'Q')
	{
		btns.push(new BX.PopupWindowButton({
			text : BX.message('JS_CORE_TM_CONFIRM'),
			className : "popup-window-button-create",
			events : {
				click: function() {q('CONFIRM=Y');}
			}
		}));
		btns.push(new BX.PopupWindowButton({
			text : BX.message('JS_CORE_TM_REJECT'),
			className : "popup-window-button-cancel",
			events : {
				click: function() {q('CONFIRM=N');}
			}
		}));
	}
	else
	{
		btns.push(new BX.PopupWindowButtonLink({
			text : BX.message('JS_CORE_TM_B_CLOSE'),
			className : "popup-window-button-link-cancel",
			events : {click : function(e) {this.popupWindow.close();return BX.PreventDefault(e);}}
		}));

	}

	return btns;
}


/*************************************************************/

BX.CTimeManCalendarSelector = function(parent, params)
{
	this.parent = parent;
	this.params = params;

	this.WND = BX.PopupWindowManager.create(
		'timeman_calendar_selector', this.params.node,
		{
			autoHide: true,
			content: (this.content = BX.create('DIV'))
		}
	);

	this.current_calendar = null;
	this.current_row = null;

	this.bRemember = false;
}

BX.CTimeManCalendarSelector.prototype.Show = function()
{
	this.content.appendChild(BX.create('B', {text: this.params.data.TEXT}));
	var q = this.content.appendChild(BX.create('DIV', {props: {className: 'bx-tm-calendars-list'}}));

	for (var i=0,l=this.params.data.CALENDARS.length; i<l; i++)
	{
		var c = this.params.data.CALENDARS[i];
		q.appendChild(BX.create('DIV', {
			props: {bx_calendar_id: c.ID},
			style: {backgroundColor: c.COLOR, cursor: 'pointer'},
			events: {
				click: BX.proxy(this.Click, this)
			},
			html: c.NAME
		}))
	}

	var id = 'tm_calendar_remember_' + Math.random();
	this.content.appendChild(BX.create('DIV', {
		children: [
			BX.create('INPUT', {
				props: {
					type: 'checkbox',
					id: id
				},
				events: {
					click: BX.delegate(function() {this.bRemember=BX.proxy_context.checked}, this)
				}
			}),
			BX.create('LABEL', {
				props: {htmlFor: id},
				text: BX.message('JS_CORE_TM_REM')
			})
		]
	}))

	this.WND.setButtons([
		new BX.PopupWindowButton({
			text : BX.message('JS_CORE_TM_B_ADD'),
			className : "popup-window-button-create",
			events : {click : BX.proxy(this.setValue, this)}
		}),
		new BX.PopupWindowButtonLink({
			text : BX.message('JS_CORE_TM_B_CLOSE'),
			className : "popup-window-button-link-cancel",
			events : {click : BX.proxy(this.closeWnd, this)}
		})
	]);

	this.WND.show();
}

BX.CTimeManCalendarSelector.prototype.Click = function(e)
{
	if (this.current_row)
		BX.removeClass(this.current_row, 'bx-tm-calendar-current');

	this.current_row = BX.proxy_context;
	this.current_calendar = this.current_row.bx_calendar_id;

	BX.addClass(this.current_row, 'bx-tm-calendar-current');

	return BX.PreventDefault(e);
}

BX.CTimeManCalendarSelector.prototype.closeWnd = function(e)
{
	this.WND.close();
	this.WND.destroy();
	this.parent.Update();
	return BX.PreventDefault(e);
}

BX.CTimeManCalendarSelector.prototype.setValue = function(e)
{
	if (this.current_calendar)
	{
		calendarLastParams.cal = this.current_calendar;

		if (this.bRemember)
			calendarLastParams.cal_set_default = 'Y'

		this.parent.calendarEntryAdd(calendarLastParams);
		return this.closeWnd(e);
	}

	return BX.PreventDefault(e);
}
/*************************************************************/
BX.CTimeManReport = function(parent)
{
	this.parent = parent;

	this.REPORT_CONTAINER = null;
	this.REPORT_BTN = null;
	this.REPORT = null;

	this.REPORT_TEXT = '';
	this.REPORT_SAVE_TIME = 0;
	this.REPORT_CLIENT_SAVE_TIME = 0;

	this.bChanged = false;
	this.bCanSave = false;

	this.ENTRY_ID = this.parent.PARENT.DATA.ID;

	BX.addCustomEvent(this.parent.PARENT, 'onTimeManDataRecieved', BX.delegate(function(data){
		if (data.FULL !== false)
		{

			this.ENTRY_ID = data.ID;
			if (typeof this.parent.DATA.REPORT != 'undefined')
			{
				this.REPORT_TEXT = this.REPORT.value = this.parent.DATA.REPORT;
				if (this.parent.DATA.REPORT_TS > 0)
					this.REPORT_SAVE_TIME = new Date(this.parent.DATA.REPORT_TS * 1000);
			}

			this.REPORT.disabled = (data.STATE == 'CLOSED' && data.REPORT_REQ != 'A');
		}
	}, this));

	this.save_timer = null;
}

BX.CTimeManReport.prototype.Create = function()
{
	if (this.REPORT_CONTAINER)
	{
		if (this.REPORT_CONTAINER.parentNode)
			this.REPORT_CONTAINER.parentNode.removeChild(this.REPORT_CONTAINER)

		this.Reset();

		return this.REPORT_CONTAINER;
	}

	this.REPORT = BX.create('TEXTAREA', {
		props: {
			className: 'tm-popup-report-textarea',
			placeholder: BX.message('JS_CORE_TM_REPORT_PH'),
			value: this.REPORT_TEXT || '',
			disabled: (this.parent.DATA.STATE == 'CLOSED' && this.parent.DATA.REPORT_REQ != 'A')
		},
		events: {
			blur: BX.delegate(this._reportBlur, this),
			keypress: BX.delegate(this._reportKeyPress, this),
			paste: BX.delegate(this._reportKeyPress, this)
		}
	});

	BX.focusEvents(this.REPORT);

	this.REPORT_CONTAINER = BX.create('DIV', {
		props: {className: 'tm-popup-report'},
		children: [
			BX.create('DIV', {
				props: {className: 'tm-popup-report-text'},
				children: [
					this.REPORT
				]
			}),
			BX.create('DIV', {
				props: {className: 'tm-popup-report-buttons'},
				children: [
					(this.REPORT_BTN = BX.create('SPAN', {
						props: {className: 'webform-small-button webform-small-button-accept webform-button-disable'},
						events: {click: BX.proxy(this._btnClick, this)},
						html: BX.message('JS_CORE_TM_B_SAVE')
					}))
				]
			})
		]
	});

	BX.addCustomEvent(window, 'onTimeManReportChange', BX.delegate(function(report, ts) {
		this.SaveFinished({REPORT: report, REPORT_TS: ts});
	}, this));
	BX.addCustomEvent(window, 'onTimeManReportChangeText', BX.delegate(function(report) {
		this.REPORT.value = report;
	}, this));

	return this.REPORT_CONTAINER;
}

BX.CTimeManReport.prototype.setEditMode = function(f)
{
	this.bCanSave = !!f;

	if (this.bCanSave)
	{
		BX.addClass(this.REPORT_CONTAINER, 'tm-popup-report-editmode');
		BX.removeClass(this.REPORT_BTN, 'webform-button-disable');
	}
	else
	{
		BX.removeClass(this.REPORT_CONTAINER, 'tm-popup-report-editmode');
		BX.addClass(this.REPORT_BTN, 'webform-button-disable');
	}
};

BX.CTimeManReport.prototype._reportKeyPress = function(e)
{
	this.bChanged = true;
	this.setEditMode(true);
}

BX.CTimeManReport.prototype._reportBlur = function(e)
{
	BX.onGlobalCustomEvent('onTimeManReportChangeText', [this.REPORT.value], true);

	if (this.bChanged)
		this.Save();
}

BX.CTimeManReport.prototype._btnClick = function(e)
{
	if (this.bCanSave)
		this.Save();
}

BX.CTimeManReport.prototype.Save = function()
{
	if (this.bChanged && !!this.saveXhr)
	{
		if (this.save_timer)
			clearTimeout(this.save_timer);

		this.save_timer = setTimeout(BX.proxy(this.Save, this), 1000);
		return;
	}

	this.setEditMode(false);

	this.REPORT_BTN.innerHTML = BX.message('JS_CORE_TM_B_SAVING');

	this.REPORT_TEXT = this.REPORT.value;

	BX.timeman.showWait();
	this.saveXhr = BX.timeman_query('report', {
		entry_id: this.ENTRY_ID,
		report: this.REPORT_TEXT,
		report_ts: this.REPORT_SAVE_TIME ? parseInt(this.REPORT_SAVE_TIME.valueOf() / 1000) : 0
	}, BX.proxy(this.SaveFinished, this), this.bChanged);

	if (!this.save_timer)
		this.bChanged = false;
}

BX.CTimeManReport.prototype.SaveFinished = function(data)
{
	if (!data)
	{
		this.saveXhr = null;
		return;
	}

	if (!this.REPORT_SAVE_TIME)
	{
		this.REPORT_TEXT = this.REPORT.value = this.parent.DATA.REPORT = data.REPORT || '';
		this.setEditMode(false);
	}
	else
	{
		this.REPORT_TEXT = this.parent.DATA.REPORT = data.REPORT;

		if (!this.bChanged)
		{
			this.REPORT.value = this.REPORT_TEXT || '';
			this.setEditMode(false);
		}
		else
		{
			this.setEditMode(true);
		}
	}

	this.parent.DATA.REPORT_TS = data.REPORT_TS;
	this.REPORT_SAVE_TIME = new Date(data.REPORT_TS * 1000);
	this.REPORT_CLIENT_SAVE_TIME = new Date();
	this.REPORT_BTN.innerHTML = BX.message('JS_CORE_TM_B_SAVE');

	if (this.saveXhr)
		BX.onGlobalCustomEvent('onTimeManReportChange', [this.REPORT_TEXT, parseInt(this.REPORT_SAVE_TIME.valueOf() / 1000)], true);

	this.saveXhr = null;
}

BX.CTimeManReport.prototype.ForceReload = function()
{
	if (!this.bChanged)
	{
		this.Reset();
		setTimeout(BX.proxy(this.Save, this), 10);
	}
}

BX.CTimeManReport.prototype.Reset = function()
{
	this.REPORT_TEXT = this.REPORT.value = this.parent.DATA.REPORT;
	if (this.parent.DATA.REPORT_TS > 0)
		this.REPORT_SAVE_TIME = new Date(this.parent.DATA.REPORT_TS * 1000);
}

/*************************************************************/
BX.CTimeManTabControl = function(DIV)
{
	this.DIV = DIV;
	this.DIV.className = 'tm-tabs-box';
	this.HEAD = this.DIV.appendChild(BX.create('DIV', {props: {className: 'tm-tabs'}}));
	this.DIV.appendChild(_createHR(false, 'tm-tabs-hr'));
	this.TABS = this.DIV.appendChild(BX.create('DIV', {props: {className: 'tm-tabs-content'}}));

	this.arTabs = null;

	this.selectedTab = BX.localStorage.get('tm_tab');
}

BX.CTimeManTabControl.prototype.addTab = function(params)
{
	if (!this.arTabs)
	{
		params.first = true;
		this.arTabs = {};

		if(!this.selectedTab)
		{
			this.selectedTab = params.id;
		}
	}
	else
	{
		params.first = false;
	}

	this.arTabs[params.id] = {
		title: params.title,
		content: params.content,
		first: params.first
	};

	this.createTab(params.id);
}

BX.CTimeManTabControl.prototype.createTab = function(id)
{
	this.arTabs[id].tab = this.HEAD.appendChild(BX.create('SPAN', {
		props: {BXTABID: id, className: 'tm-tab' + (id==this.selectedTab ? ' tm-tab-selected' : '')},
		events: {click: BX.delegate(function(){
			this.selectTab(id);
		}, this)},
		html: this.arTabs[id].title
	}));

	this.arTabs[id].tab_content = this.TABS.appendChild(BX.create('DIV', {
		props: {className: 'tm-tab-content' + (id==this.selectedTab ? ' tm-tab-content-selected' : '')},
		children: BX.type.isArray(this.arTabs[id].content)
			? this.arTabs[id].content
			: (
				BX.type.isDomNode(this.arTabs[id].content)
					? [this.arTabs[id].content]
					: null
				)
	}));

	if (BX.type.isNotEmptyString(this.arTabs[id].content))
	{
		this.arTabs[id].tab_content.innerHTML = this.arTabs[id].content;
	}
}

BX.CTimeManTabControl.prototype.selectTab = function(id)
{
	BX.removeClass(this.arTabs[this.selectedTab].tab, 'tm-tab-selected');
	BX.removeClass(this.arTabs[this.selectedTab].tab_content, 'tm-tab-content-selected');
	this.selectedTab = id;
	BX.addClass(this.arTabs[this.selectedTab].tab, 'tm-tab-selected');
	BX.addClass(this.arTabs[this.selectedTab].tab_content, 'tm-tab-content-selected');

	this.saveTab();
}

BX.CTimeManTabControl.prototype.saveTab = function()
{
	BX.localStorage.set('tm_tab', this.selectedTab, 86400*30);
}

BX.CTimeManTabEditorControl = function(params)
{
	this.div = params.div||BX.create("DIV");
	this.tabs = {};
	this.mode = "view";
	this.isLHEinit = false;
	if(params.uselocalstorage && params.localstorage_key)
	{
		this.uselocalstorage = true;
		this.localstorage_key = params.localstorage_key;
		BX.addCustomEvent(window, 'onLocalStorageSet', BX.proxy(function(data) {
			if (data.key == this.localstorage_key && data.value)
			{
				for (i in this.tabs)
				{
					if (data.value[i])
						this.SetTabContent(i,data.value[i]);
				}
			}
		}, this));

	}
	else
		this.uselocalstorage = false;
	this.first_tab = false;
	this.parent = params.parent || false;
	this.current_tab_id = false;
	this.lhename = params.lhename||"obTimemanEditor";
	this.TABCONTROL = new BX.CTimeManTabControl(this.div);
	this.TABCONTROL.saveTab = BX.DoNothing;
	this.TABCONTROL.selectedTab = null;

	this.TABCONTROL._selectTab = this.TABCONTROL.selectTab;
	this.TABCONTROL.selectTab = BX.proxy(function(id)
	{
		if (!this.isLHEinit && this.mode == "edit")
			return false;

		this.TABCONTROL._selectTab(id);
	},this);
}
BX.CTimeManTabEditorControl.prototype.addTab = function(params)
{
	var tab = {};
	tab.PARAMS = params;
	tab.ID = tab.PARAMS.ID;
	tab.TITLE = tab.PARAMS.TITLE;
	tab.CONTENT = tab.PARAMS.CONTENT;
	if (this.first_tab == false)
	{
		this.current_tab_id = tab.ID;
		this.first_tab = true;
	}
	this.TABCONTROL.addTab({
		id: tab.PARAMS.ID,
		title: tab.PARAMS.TITLE,
		content: [
			tab.LHEDIV = BX.create('DIV', {
				attrs:{id: tab.ID+"_editor"},
				style:{
					border:"1px solid #D9D9D9",height:"200px",display:"none"
				}
			}),
			tab.VIEWDIV =  BX.create('DIV', {
				style:{border:"none",maxHeight:"200px",padding:"5px",overflow:"auto"},
				html:tab.PARAMS.CONTENT
			})
		]
	});
	tab.CONTROL = this.TABCONTROL.arTabs[tab.ID];
	this.tabs[tab.ID] = tab;
	BX.bind(this.TABCONTROL.arTabs[tab.ID].tab, "click",BX.proxy(function()
	{
		var prev_tab = this.current_tab_id;
		this.current_tab_id = tab.ID;
		if (this.mode == "view")
			return;
		else if((!this.isLHEinit && this.mode == "edit")
		||(prev_tab ==this.current_tab_id)
		)
		{
			this.current_tab_id = prev_tab;
			return;
		}

		this.tabs[prev_tab].CONTENT = this.editor.GetEditorContent();
		var pEditorCont = BX("bxlhe_frame_" + this.editor.id);
		this.tabs[tab.ID].LHEDIV.appendChild(pEditorCont);

		if (BX.browser.IsIE())
		{
			var _this = this;
			pEditorCont.style.visibility = 'hidden';
			setTimeout(function()
			{
				_this.editor.ReInit(_this.tabs[tab.ID].CONTENT);
				pEditorCont.style.visibility = 'visible';
				BX.bind(_this.editor.pEditorDocument, 'keyup', BX.proxy(_this.SaveToLocalStorage, _this));
			}, 100);
		}
		else
		{
			this.editor.ReInit(this.tabs[tab.ID].CONTENT);
			BX.bind(this.editor.pEditorDocument, 'keyup', BX.proxy(this.SaveToLocalStorage, this));
		}
	},this));
}

BX.CTimeManTabEditorControl.prototype.InitLHE = function()
{
	if (this.tabs == 0)
		return;

	var current_tab = this.tabs[this.current_tab_id];
	BX.ajax.get("/bitrix/tools/timeman.php?action=editor&obname="+this.lhename+"&sessid=" + BX.bitrix_sessid(), function(data){
		current_tab.LHEDIV.innerHTML = data;
	});
	BX.addCustomEvent(window, 'LHE_OnInit', BX.proxy(function(data){
		if(data.id == this.lhename)
		{
			if (!BX.CDialog)
			{
				arScripts = ["/bitrix/js/main/core/core_window.js"];
				arCss = ["/bitrix/js/main/core/css/core_window.css"];
				BX.loadCSS(arCss);
				BX.loadScript(arScripts);
			}
			this.editor = data;
			this.editor.SetContent(this.tabs[this.current_tab_id].CONTENT);
			this.editor.SetEditorContent(this.tabs[this.current_tab_id].CONTENT);
			BX.bind(this.editor.pEditorDocument, 'keyup', BX.proxy(this.SaveToLocalStorage,this));
			this.isLHEinit = true;

			this.TABCONTROL.selectTab(this.current_tab_id);
		}
	},this));
}

BX.CTimeManTabEditorControl.prototype.SwitchEdit = function()
{
	if (this.tabs.length == 0 || this.mode == "edit")
		return;
	for (i in this.tabs)
	{
		this.tabs[i].VIEWDIV.style.display = "none";
		this.tabs[i].LHEDIV.style.display = "block";
		this.tabs[i].LHEDIV.innerHTML = "";
	}
	this.mode = "edit";
	this.InitLHE();
}

BX.CTimeManTabEditorControl.prototype.SwitchView = function()
{
	if (this.tabs == 0 || this.mode == "view")
		return;
	for (i in this.tabs)
	{
		if (i ==  this.current_tab_id)
		{
			this.tabs[i].VIEWDIV.innerHTML = this.editor.GetEditorContent();
			this.tabs[i].CONTENT = this.editor.GetEditorContent();
		}
		else
			this.tabs[i].VIEWDIV.innerHTML = this.tabs[i].CONTENT;
		this.tabs[i].VIEWDIV.style.display = "block";
		this.tabs[i].LHEDIV.style.display = "none";

	}
	this.mode = "view";
}

BX.CTimeManTabEditorControl.prototype.GetTabContent = function(tabId)
{
	var tabId = tabId||false;
	var tabContent = "";
	if (this.tabs == 0 || tabId == false || !this.tabs[tabId])
		return;
	if (this.current_tab_id == tabId && this.mode == "edit")
		tabContent = this.editor.GetEditorContent();
	else
		tabContent = this.tabs[tabId].CONTENT;

	return tabContent;
}

BX.CTimeManTabEditorControl.prototype.SetTabContent = function(tabId,content)
{
	var tabId = tabId||false;
	var tabContent = "";
	if (this.tabs == 0 || tabId == false || !this.tabs[tabId])
		return false;
	if (this.current_tab_id == tabId && this.mode == "edit")
		this.editor.SetEditorContent(content);
	else
		this.tabs[i].CONTENT = content;
	BX.bind(this.editor.pEditorDocument, 'keyup', BX.proxy(this.SaveToLocalStorage,this));//condition inside
	return true;
}

BX.CTimeManTabEditorControl.prototype.SaveToLocalStorage = function()
{
	if (this.timerID)
		clearTimeout(this.timerID);
	if (this.uselocalstorage != true)
		return;
	var data = {};
	for (i in this.tabs)
		data[i] = this.GetTabContent(i);
	this.timerID = setTimeout(BX.proxy(function(){
		BX.localStorage.set(this.localstorage_key,data);
	},this), 1000);
}

BX.CTimeManUploadForm = function(params)
{
	this.id = "TimemanUpload"+((!params.id)?"0":params.id);
	this.report_id = params.id||0;
	this.user_id = params.user_id;//||BX.message('USER_ID');
	this.files = params.files_list||[];
	window[this.id] = this;
	this.DIV = ((params.div)
		?params.div
		:BX.create("DIV")
	);
	this.mode = params.mode||"view";
}

BX.CTimeManUploadForm.prototype.UploadFile = function()
{
	var files = [];
	if (this.fileinput.files!=undefined)
	{
		if(this.fileinput.files.length > 0)
		{
			for(var i=0; i < this.fileinput.files.length; i++)
			{
				var n = this.fileinput.files[i].name||this.fileinput.files[i].fileName;
				if(!!n)
				{
					files.push({
						fileName: n
					});
				}
			}
		}
	} else {
		var filePath = this.fileinput.value;
		var fileTitle = filePath.replace(/.*\\(.*)/, "$1");
		fileTitle = fileTitle.replace(/.*\/(.*)/, "$1");
		files = [
			{ fileName : fileTitle}
		];
	}

	var uniqueID;
	do {
		uniqueID = Math.floor(Math.random() * 99999);
	} while(BX("iframe_" + uniqueID));

	var list = BX("webform-upload-"+this.report_id);
	var items = [];
	for (var i = 0; i < files.length; i++)
	{
		var li = BX.create("li", {
			props : { className : "uploading",  id : "file-" + files[i].fileName + "-" + uniqueID},
			children : [
				BX.create("a", {
					props : { href : "", target : "_blank", className : "upload-file-name"},
					text : files[i].fileName,
					events : { click : function(e) {
						BX.PreventDefault(e);
					}}
				}),
				BX.create("i", { }),
				BX.create("a", {
					props : { href : "", className : "delete-file"},
					events : { click : function(e) {
						BX.PreventDefault(e);
					}}
				})
			]
		});

		list.appendChild(li);
		items.push(li);
	}

	var iframeName = "iframe-" + uniqueID;
	var iframe = BX.create("iframe", {
		props : {name : iframeName, id : iframeName},
		style : {display : "none"}
	});
	document.body.appendChild(iframe);
	var originalParent = this.fileinput.parentNode;
	var form = BX.create("form", {
		props : {
			method : "post",
			action : "/bitrix/tools/timeman.php",
			enctype : "multipart/form-data",
			encoding : "multipart/form-data",
			target : iframeName
		},
		style : {display : "none"},
		children : [
			this.fileinput,
			BX.create("input", {
				props : {
					type : "hidden",
					name : "sessid",
					value : BX.bitrix_sessid()
				}
			}),
			BX.create("input", {
				props : {
					type : "hidden",
					name : "uniqueID",
					value : uniqueID
				}
			}),
			BX.create("input", {
				props : {
					type : "hidden",
					name : "mode",
					value : "upload"
				}
			}),
			BX.create("input", {
				props : {
					type : "hidden",
					name : "action",
					value : "upload_attachment"
				}
			}),
			BX.create("input", {
				props : {
					type : "hidden",
					name : "report_id",
					value : this.report_id
				}
			}),
			BX.create("input", {
				props : {
					type : "hidden",
					name : "user_id",
					value : this.user_id
				}
			}),
			BX.create("input", {
				props : {
					type : "hidden",
					name : "form_id",
					value : this.id
				}
			})
		]
	});
	document.body.appendChild(form);
	form.appendChild(this.fileinput);
	BX.submit(form, null, null, BX.delegate(function(){
		originalParent.appendChild(this.fileinput);
		BX.cleanNode(form, true);
	}, this));
}


BX.CTimeManUploadForm.prototype.DeleteFile = function(e)
{
	_this = BX.proxy_context;
	if (confirm(BX.message("JS_CORE_TM_CONFIRM_TO_DELETE"))) {
		if (!BX.hasClass(_this.parentNode, "saved"))
		{
			var data = {
				fileID : _this.nextSibling.value,
				sessid : BX.bitrix_sessid(),
				mode : "delete",
				action: "upload_attachment",
				report_id:this.report_id,
				user_id:this.user_id
			}
			var url = "/bitrix/tools/timeman.php";
			BX.ajax.post(url, data);
		}
		BX.remove(_this.parentNode);
		for(i=0;i<this.files.length;i++)
		{
			if (_this.nextSibling.value == this.files[i].fileID)
			{
				this.files.splice(i,1);
				break;
			}
		}
	}
	BX.onCustomEvent(this, 'OnUploadFormRefresh', []);
	BX.PreventDefault(e);
}

BX.CTimeManUploadForm.prototype.GetUploadForm = function()
{
	var filelist = BX.create("OL",{
						props:{className:"report-webform-field-upload-list"},
						attrs:{id:"webform-upload-"+this.report_id}
					});
	if (this.files && this.files.length>0)
	{
		var files =this.files;
		for(i=0;i<files.length;i++)
		{
			filelist.appendChild(
				BX.create("li", {
				props : {id : "file-" + files[i].name + "-" + files[i].uniqueID},
				children : [
					BX.create("a", {
						props : { href : "/bitrix/tools/timeman.php?action=get_attachment&fid="+files[i].fileID+"&report_id="+this.report_id+"&user_id="+this.user_id+"&sessid="+BX.bitrix_sessid(), target : "_blank", className : "upload-file-name"},
						text : files[i].name
					}),
					((this.mode == "edit")?BX.create("a", {
						props : {className : "delete-file"},
						events:{"click":BX.proxy(this.DeleteFile,this)}
						}):null),
					BX.create("INPUT",{
						attrs:{type:"hidden", name:"FILES[]", value:files[i].fileID}
					})
				]
			})
			);
		}
	}
	this.uploadform = BX.create("DIV",{
		props:{className:"webform-row task-attachments-row"},
		children:[
			BX.create("DIV",{
				props:{className:"webform-field webform-field-attachments"},
				children:[
					BX.create("DIV",{
						props:{className:"tm-popup-section-title"},
						children:[
							BX.create("DIV",{
								props:{className:"tm-popup-section-title-text"},
								html:BX.message("JS_CORE_TM_FILES")
							}),
							BX.create("DIV",{
								props:{className:"tm-popup-section-title-line"}
							})
						]
					}),
					filelist,
					((this.mode == "edit")
					?BX.create("DIV",{
						props:{className:"webform-field-upload"},
						children:[
							BX.create("SPAN",{
								props:{className:"webform-small-button webform-button-upload"},
								children:[
									BX.message('JS_CORE_TM_UPLOAD_FILES')
								]
							}),
							(this.fileinput = BX.create("INPUT",{
								attrs:{type:"file",name:"report-attachments[]", size:"1", multiple:"multiple", id:"report-upload"}
							}))
						]
					})
					:null
					)
				]
			})
		]
	});
	if(this.mode == "edit")
		this.fileinput.onchange = BX.proxy(this.UploadFile,this);
	this.DIV.appendChild(this.uploadform);

	return this.DIV;
}

BX.CTimeManUploadForm.prototype.RefreshUpload = function(files,uniqueID)
{
	for(i = 0; i < files.length; i++)
	{
		var elem = BX("file-" + files[i].name + "-" + uniqueID);
		if (files[i].fileID)
		{
			BX.removeClass(elem, "uploading");
			BX.adjust(elem.firstChild, {props : {href : "/bitrix/tools/timeman.php?action=get_attachment&fid="+files[i].fileID+"&report_id="+this.report_id+"&user_id="+this.user_id+"&sessid="+BX.bitrix_sessid()}});
			BX.unbindAll(elem.firstChild);
			BX.unbindAll(elem.lastChild);
			BX.bind(elem.lastChild, "click", BX.proxy(this.DeleteFile,this));

			elem.appendChild(BX.create("input", {
				props : {
					type : "hidden",
					name : "FILES[]",
					value : files[i].fileID
				}
			}));
			files[i].uniqueID = uniqueID;
			this.files.push(files[i]);
		}
		else
		{
			BX.cleanNode(elem, true);
		}
	}
	if(BX("iframe-" + uniqueID))
		BX.cleanNode(BX("iframe-" + uniqueID), true);
	BX.onCustomEvent(this, 'OnUploadFormRefresh', []);
}

/********************************************************************************/
//weekly


BX.CTimeManReportFormWeekly = function(parent, params)
{
	this.parent = parent;
	this.params = params;
	this.files = new Array();
	this.post = false;
	this.node = this.params.node;
	this.mode = this.params.mode || 'edit';

	this.data = params.data;

	this.popup_id = 'timeman_weekly_report_popup_' + parseInt(Math.random() * 100000);

	this.bLoaded = !!this.data;
	this.bTimeEdited = false;

	this.params.offsetTop = 5;
	this.params.offsetLeft = -50;
	this.ACTIVE = "Y";

	this.ie7 = false;
	/*@cc_on
		 @if (@_jscript_version <= 5.7)
			this.ie7 = true;
		/*@end
	@*/
	this.table = BX.create("TABLE",{
			props:{className:"report-popup-main-table"},
			children:[
				BX.create("TBODY",{
					children:[
						BX.create("TR",{
							children:[
								this.prev = BX.create("TD",{
									props:{className:"report-popup-prev-slide-wrap"}
								}),
								this.popup_place = BX.create("TD",{
									attrs:{align:"center",valign:"top"},
									style:{paddingTop:"20px"},
									props:{className:"report-popup-main-block-wrap"}
								}),
								this.next = BX.create("TD",{
									props:{className:"report-popup-next-slide-wrap"},
									children:[
										this.closeLink = BX.create("A",{
											attrs:{href:"javascript: void(0)"},
											events:{"click":BX.proxy(this.PopupClose,this)},
											props:{className:"report-popup-close"},
											children:[
												BX.create("SPAN",{
													props:{className:"report-popup-close"}
													})
											]
										})
									]

								})
							]

						})
					]

				})
			]
		}
	);
	this.overlay = BX.create("DIV",{
			props:{className:"report-fixed-overlay"},
			children:[
				(this.coreoverlay = BX.create("DIV",{
					props:{className:"bx-tm-dialog-overlay"}
					//attrs:{width:"100%",heigth:"100%"}
					}
				)),
				this.table
				]
			}
			);
	document.body.appendChild(this.overlay);
	this.popup = new BX.PopupWindow(this.popup_id, null, {
		closeIcon : {right: "12px", top: "10px"},
		autoHide: false,
		draggable:false,
		closeByEsc:true,
		titleBar: true
	});

BX.addCustomEvent(this.popup, "onPopupClose", BX.proxy(function(){
	this.overlay.style.display = "none";
	BX.removeClass(document.body, "report-body-overflow");
	}, this));
	BX.addCustomEvent(this.popup, "onAfterPopupShow", BX.proxy(function(){
	this.overlay.style.display = "block";
	BX.addClass(document.body, "report-body-overflow");
	setTimeout(BX.proxy(this.FixOverlay,this),10)
//	this.FixOverlay();
	}, this));
	this.popup_place.appendChild(
			BX.create("DIV",{
			style:{display:"inline-block"},
			children:[
				this.popup.popupContainer
				]
			})
		);

	this.FixOverlay();
	BX.bind(window.top, "resize", BX.proxy(this.FixOverlay, this))
	this.ACTIONS = {
		delay: BX.proxy(this.ActionDelay, this),
		edit: BX.proxy(this.ActionEdit, this),
		save: BX.proxy(this.ActionSave, this),
		send: BX.proxy(this.ActionSend, this)

	}
}

BX.extend(BX.CTimeManReportFormWeekly, BX.CTimeManPopup)

BX.CTimeManReportFormWeekly.prototype.FixOverlay = function()
{
	this.popup.popupContainer.style.position = "relative";
	this.popup.popupContainer.style.left = "0px";
	var size = BX.GetWindowInnerSize();
	this.overlay.style.height = size.innerHeight + "px";
	this.overlay.style.width = size.innerWidth + "px";
	var scroll = BX.GetWindowScrollPos();
	this.overlay.firstChild.style.height = Math.max(this.popup.popupContainer.offsetHeight+50, this.overlay.clientHeight)+"px";
	this.overlay.firstChild.style.width = Math.max(1024, this.overlay.clientWidth) + "px";
	this.popup.popupContainer.style.top = "0px";
	this.closeLink.style.width = this.closeLink.parentNode.clientWidth + 'px';
}

BX.CTimeManReportFormWeekly.prototype.PopupClose = function()
{
	this.popup.close();
}
BX.CTimeManReportFormWeekly.prototype.Show = function(data)
{
	if (!data && !this.data)
	{
		BX.timeman.showWait();
		if (this.mode == 'edit')
			BX.timeman_query('check_report', {}, BX.proxy(this.Show, this));
		return;
	}
	if (window.BXTIMEMANREPORTFORMWEEKLY && window.BXTIMEMANREPORTFORMWEEKLY != this)
		window.BXTIMEMANREPORTFORMWEEKLY.popup.close();

	window.BXTIMEMANREPORTFORMWEEKLY = this;

	BX.timeman.closeWait();
	if (!this.data)
		this.data = data.REPORT_DATA;
	if(!this.data.INFO)
		return;
	BX.addCustomEvent(window, 'onLocalStorageSet', BX.proxy(
		function(data)
		{
			var key = this.data.REPORT_DATE_FROM+"#"+this.data.REPORT_DATE_TO+"_ACTION";
			if (data.key == key && data.value.LAST_ACTION == "SEND_REPORT")
			{
				this.parent.ShowCallReport = false;
				BX.onCustomEvent(this, 'OnWorkReportSend', []);
				this.popup.close();
			}
		}, this)
	);
	this.popup.setContent(this.GetContent());
	this.popup.setButtons(this.GetButtons());
	this.popup.setTitleBar({content :this.GetTitle()});
	//closeByEsc disable fix for task-popup
	try
	{
		BX.addCustomEvent(taskIFramePopup, 'onBeforeShow', BX.proxy(function(){
			this.popup.setClosingByEsc(false);
		},this));
		BX.addCustomEvent(taskIFramePopup, 'onBeforeHide', BX.proxy(function(){
			this.popup.setClosingByEsc(true);
		},this));
	}catch(e){}
	this.popup.show();
	this.popup.setOffset({offsetTop: 1});

	return true;
}


BX.CTimeManReportFormWeekly.prototype.GetContentPeopleRow = function()
{
	return BX.create('DIV', {
		props: {className: 'tm-report-popup-people'},
		html: '<div class="tm-report-popup-r1"></div><div class="tm-report-popup-r0"></div>\
<div class="tm-report-popup-people-inner">\
	<div class="tm-report-popup-user tm-report-popup-employee">\
		<span class="tm-report-popup-user-label">' + BX.message('JS_CORE_TMR_FROM') + ':</span><a href="' + this.data.FROM.URL + '" class="tm-report-popup-user-avatar"' + (this.data.FROM.PHOTO ? ' style="background: url(\'' + this.data.FROM.PHOTO + '\') no-repeat scroll center center transparent;"' : '') + '></a><span class="tm-report-popup-user-info"><a href="' + this.data.FROM.URL + '" class="tm-report-popup-user-name">' + this.data.FROM.NAME + '</a><span class="tm-report-popup-user-position">' + (this.data.FROM.WORK_POSITION || '&nbsp;') + '</span></span>\
	</div>\
	<div class="tm-report-popup-user tm-report-popup-director">\
		<span class="tm-report-popup-user-label">' + BX.message('JS_CORE_TMR_TO') + ':</span><a href="' + this.data.TO[0].URL + '" class="tm-report-popup-user-avatar"' + (this.data.TO[0].PHOTO ? ' style="background: url(\'' + this.data.TO[0].PHOTO + '\') no-repeat scroll center center transparent;"' : '') + '></a><span class="tm-report-popup-user-info"><a href="' + this.data.TO[0].URL + '" class="tm-report-popup-user-name">' + this.data.TO[0].NAME + '</a><span class="tm-report-popup-user-position">' + (this.data.TO[0].WORK_POSITION || '&nbsp;') + '</span></span>\
	</div>\
</div>\
<div class="tm-report-popup-r0"></div><div class="tm-report-popup-r1"></div>'
	});
}

BX.CTimeManReportFormWeekly.prototype.GetContentReportRow = function(report_value)
{
	this.TABCONTROL = new BX.CTimeManTabEditorControl({
			lhename:"obReportWeekly",
			parent:this,
			uselocalstorage: true,
			localstorage_key: this.data.INFO.REPORT_DATE_FROM+"#"+this.data.INFO.REPORT_DATE_TO
		}
	);
	this.TABCONTROL.addTab({
		ID:"report_text",
		TITLE:BX.message('JS_CORE_TMR_REPORT'),
		CONTENT:this.data.REPORT
	});
	this.TABCONTROL.addTab({
		ID:"plan_text",
		TITLE:BX.message('JS_CORE_TMR_PLAN'),
		CONTENT:this.data.PLANS
	});
	this.TABCONTROL.SwitchEdit();
	return  BX.create('DIV', {
			props: {className: 'tm-report-popup-desc'},
			children: [
				BX.create('DIV', {
					props: {className: 'tm-report-popup-desc-text'},
					children: [
						this.TABCONTROL.div
					]
				})
			]
		});
}

BX.CTimeManReportFormWeekly.prototype._addTask = function(task_data)
{
	if (this.data.INFO.TASKS && this.data.INFO.TASKS.length>0)
		for(i=0;i<this.data.INFO.TASKS.length;i++)
		{
			if (this.data.INFO.TASKS[i].ID == task_data.id)
				return;
		}
	BX.timeman_query('get_task', {task_id:task_data.id}, BX.proxy(this.addTask, this));
}

BX.CTimeManReportFormWeekly.prototype.addTask = function(task_data)
{

	var inp, inpTime;

	if (typeof task_data.TIME == 'undefined')
		task_data.TIME = 0;

	var taskTime = 0;

	this.listTasks.appendChild(
		BX.create('LI', {
			props: {
				className: 'tm-popup-task tm-popup-task-status-' + BX.timeman.TASK_SUFFIXES[task_data.STATUS],
				bx_task_id: task_data.ID
			},
			children:
			[
				(inp = this.mode == 'admin' ? null : BX.create('INPUT', {
					props: {
						className: 'tm-report-popup-include-checkbox',
						value: task_data.ID,
						checked: typeof tasks_unchecked == 'undefined'
							? taskTime > 0 : false,
						defaultChecked: true
					},
					attrs: {type: 'checkbox'}
				})),
				BX.create('SPAN', {
					props: {
						className: 'tm-popup-task-name',
						BXPOPUPBIND: this.tdTasks.firstChild,
						BXPOPUPPARENT: this.listTasks,
						BXPOPUPANGLEOFFSET: 44
					},
					text: task_data.TITLE,
					events: {click: BX.proxy(this.parent.WND.showTask, this)}
				}),
				(inpTime = this.mode == 'admin' ? null : BX.create('INPUT', {
					props: {value: taskTime},
					attrs: {type: 'hidden'}
				})),
				BX.create('SPAN', {
					props: {
						className: 'tm-popup-task-time' + (this.mode == 'admin' ? '-admin' : ''),
						BXTIMEINPUT: this.mode == 'admin' ? null : inpTime,
						BXCHECKINPUT: this.mode == 'admin' ? null : inp
					},
					events: this.mode == 'admin' ? null : {click:BX.timeman.editTime},
					text: BX.timeman.formatWorkTime(taskTime)
				})

				/*BX.create('SPAN', {
					props: {className: 'tm-popup-task-delete'},
					events: {click: BX.proxy(this.parent.WND.removeTask, this.parent.WND)}
				})*/
			]
		})
	);

	if (inp)
	{
		this.listInputTasks.push(inp);
		this.listInputTasksTime.push(inpTime);
	}
		this.incLabel.style.display = "block";
		this.data.INFO.TASKS[this.data.INFO.TASKS.length] = task_data;
}

BX.CTimeManReportFormWeekly.prototype.GetContentTasks = function(tdTasks, tasks_unchecked, tasks_time)
{
	this.tdTasks = tdTasks;
	tdTasks.className = 'tm-report-popup-tasks';
	this.selectorLink = BX.create("DIV",{
				props:{className:"tm-popup-section-title-link tm-popup-section-title-link-weekly"},
				html:BX.message('JS_CORE_TM_TASKS_CHOOSE')

		});
	if(this.TASKSWND == null)
	{
		this.TASKSWND = new BX.CTimeManTasksSelector(this, {
				node: this.selectorLink,
				onselect: BX.proxy(this._addTask, this)
			});
	}
	else
		this.TASKSWND.setNode(this.selectorLink);
	this.selectorLink.onclick = BX.proxy(this.TASKSWND.Show, this.TASKSWND);
	tdTasks.appendChild(BX.create('DIV', {
		props: {className: 'tm-popup-section-title'},
		children:[
			BX.create("DIV",{
				props:{className:"tm-popup-section-title-text"},
				html:BX.message('JS_CORE_TM_TASKS')

		}),
		this.selectorLink,
		BX.create("DIV",{
				props:{className:"tm-popup-section-title-line"}

		})
		]
	}));
		this.listTasks = null;
		tdTasks.appendChild(BX.create('DIV', {

			props: {className: 'tm-popup-tasks'},
			children: [
				this.mode == 'admin' ? null :
				this.incLabel = BX.create('DIV', {
					props: {className: 'tm-report-popup-inlude-tasks'},
					style:{display:"none"},
					html: '<span class="tm-report-popup-inlude-arrow"></span><span class="tm-report-popup-inlude-hint">' + BX.message('JS_CORE_TMR_REPORT_INC') + '</span>'
				}),
				(this.listTasks = BX.create('OL', {props: {className: 'tm-popup-task-list'}}))
			]
		}));

	this.listInputTasks = []; this.listInputTasksTime = [];
	if (this.data.INFO.TASKS && this.data.INFO.TASKS.length > 0)
	{
		this.incLabel.style.display = "block";
		for (var i=0;i<this.data.INFO.TASKS.length;i++)
		{
			var inp, inpTime;

			if (typeof this.data.INFO.TASKS[i].TIME == 'undefined')
			{
				this.data.INFO.TASKS[i].TIME = 0;
				// if(typeof this.data.INFO.TASKS[i].TIME_ESTIMATE != 'undefined')
				// {
				// 	this.data.INFO.TASKS[i].TIME += parseInt(this.data.INFO.TASKS[i].TIME_ESTIMATE)||0;
				// }

				if(typeof this.data.INFO.TASKS[i].TIME_SPENT_IN_LOGS != 'undefined')
				{
					this.data.INFO.TASKS[i].TIME += parseInt(this.data.INFO.TASKS[i].TIME_SPENT_IN_LOGS)||0;
				}
			}

			var taskTime = typeof tasks_time[i] == 'undefined' ? this.data.INFO.TASKS[i].TIME : tasks_time[i];

			this.listTasks.appendChild(
				BX.create('LI', {
					props: {
						className: 'tm-popup-task tm-popup-task-status-' + BX.timeman.TASK_SUFFIXES[this.data.INFO.TASKS[i].STATUS],
						bx_task_id: this.data.INFO.TASKS[i].ID
					},
					children:
					[
						(inp = this.mode == 'admin' ? null : BX.create('INPUT', {
							props: {
								className: 'tm-report-popup-include-checkbox',
								value: this.data.INFO.TASKS[i].ID,
								checked: typeof tasks_unchecked[i] == 'undefined'
									? taskTime > 0 : false,
								defaultChecked: true
							},
							attrs: {type: 'checkbox'}
						})),
						BX.create('SPAN', {
							props: {
								className: 'tm-popup-task-name',
								BXPOPUPBIND: tdTasks.firstChild,
								BXPOPUPPARENT: this.listTasks,
								BXPOPUPANGLEOFFSET: 44
							},
							text: this.data.INFO.TASKS[i].TITLE,
							events: {click: BX.proxy(this.parent.WND.showTask, this)}
						}),
						(inpTime = this.mode == 'admin' ? null : BX.create('INPUT', {
							props: {value: taskTime},
							attrs: {type: 'hidden'}
						})),
						BX.create('SPAN', {
							props: {
								className: 'tm-popup-task-time' + (this.mode == 'admin' ? '-admin' : ''),
								BXTIMEINPUT: this.mode == 'admin' ? null : inpTime,
								BXCHECKINPUT: this.mode == 'admin' ? null : inp
							},
							events: this.mode == 'admin' ? null : {click:BX.timeman.editTime},
							text: BX.timeman.formatWorkTime(taskTime)
						})

						/*BX.create('SPAN', {
							props: {className: 'tm-popup-task-delete'},
							events: {click: BX.proxy(this.parent.WND.removeTask, this.parent.WND)}
						})*/
					]
				})
			);

			if (inp)
			{
				this.listInputTasks.push(inp);
				this.listInputTasksTime.push(inpTime);
			}
		}
	}

	else
		this.data.INFO.TASKS = [];
}

BX.CTimeManReportFormWeekly.prototype.GetContentEvents = function(tdEvents)
{


	if (this.data.INFO.EVENTS && this.data.INFO.EVENTS.length > 0)
	{
		tdEvents.className = 'tm-report-popup-events' + (BX.isAmPmMode() ? " tm-popup-events-ampm" : "");
			tdEvents.appendChild(BX.create('DIV', {
				props: {className: 'tm-popup-section-title'},
				html: '<div class="tm-popup-section-title-text">' + BX.message('JS_CORE_TM_EVENTS') + '</div><div class="tm-popup-section-title-line"></div>'
			}));
		this.listEvents = null;
		tdEvents.appendChild(BX.create('DIV', {
			props: {className: 'tm-popup-events' },
			children: [
				(this.listEvents = BX.create('DIV', {props: {className: 'tm-popup-event-list'}}))
			]
		}));

		for (var i=0;i<this.data.INFO.EVENTS.length;i++)
		{
			this.listEvents.appendChild(
				this.parent.WND.CreateEvent(this.data.INFO.EVENTS[i], {
					BXPOPUPBIND: tdEvents.firstChild,
					BXPOPUPANGLEOFFSET: 44
				},
				true
			)
			)
		}
	}
}

BX.CTimeManReportFormWeekly.prototype.taskEntryAdd = function(task)
{
	this.parent.taskEntryAdd(task, BX.delegate(function(data) {
		this.parent._Update(data);
		this.data = null;
		this.Show();
	}, this));
}

BX.CTimeManReportFormWeekly.prototype.calendarEntryAdd = function(ev)
{
	this.parent.calendarEntryAdd(ev, BX.delegate(function(data) {
		if (data && data.error && data.error_id == 'CHOOSE_CALENDAR')
		{
			this.Update = BX.proxy(this.parent.Update, this.parent); // hack
			(new BX.CTimeManCalendarSelector(this, {
				node: this.eventsForm,
				data: data.error
			})).Show();
		}
		else
		{
			this.parent._Update(data);
			this.data = null;
			this.Show();
		}
	}, this));
}

BX.CTimeManReportFormWeekly.prototype.GetContent = function()
{
	var report_value = '', tasks_unchecked = {}, tasks_time = {};

	if (this.DIV)
	{
		report_value = this.data.REPORT ? this.data.REPORT : '';

		if (this.listInputTasks)
		{
			for (i = 0, l = this.listInputTasks.length; i < l; i++)
			{
				if (!this.listInputTasks[i].checked)
				{
					tasks_unchecked[i] = true;
				}
				tasks_time[i] = this.listInputTasksTime[i].value;
			}
		}

		BX.cleanNode(this.DIV);
		this.DIV = null;
	}

	this.DIV = BX.create('DIV', {
		props: {className: 'tm-report-popup' + (this.mode == 'edit' ? '' : ' tm-report-popup-read-mode') + (this.ie7 ? ' tm-report-popup-ie7' : '')},
		children: [
			this.GetContentPeopleRow(),
			this.GetContentReportRow(report_value)
		]
	});
	this.fileForm = new BX.CTimeManUploadForm({
		id:this.data.REPORT_ID,
		user_id: this.data.FROM.ID,
		files_list:this.data.INFO.FILES,
		mode:"edit",
		div:this.DIV
	});
	this.fileForm.GetUploadForm();
	BX.addCustomEvent(this.fileForm, "OnUploadFormRefresh", BX.proxy(function(){
		this.FixOverlay();
	},this));
	if (this.mode == 'edit' ||
		this.data.INFO.TASKS && this.data.INFO.TASKS.length > 0 || this.data.INFO.EVENTS && this.data.INFO.EVENTS.length > 0)
	{

		var tr = this.DIV.appendChild(BX.create('TABLE', {
			props: {className: 'tm-report-popup-items'},
			attrs: {cellSpacing: '0'},
			children: [BX.create('TBODY')]
		})).tBodies[0].insertRow(-1);

		if (this.data.INFO.TASKS_ENABLED && (this.mode == 'edit' || this.data.INFO.TASKS && this.data.INFO.TASKS.length > 0))
			this.GetContentTasks(tr.insertCell(-1), tasks_unchecked, tasks_time);
		if (this.data.INFO.CALENDAR_ENABLED && this.data.INFO.EVENTS && this.data.INFO.EVENTS.length > 0)
			this.GetContentEvents(tr.insertCell(-1));
	}

	return this.DIV;
}

BX.CTimeManReportFormWeekly.prototype.GetTitle = function()
{
	var title = BX.create('DIV', {
		props: {className: 'tm-report-popup-titlebar'},
		children: [
			BX.create('DIV', {
				props: {className: 'tm-report-popup-title'},
				text: BX.message('JS_CORE_TMR_REPORT_WEEKLY')
			}),
			BX.create('SPAN', {
				props: {className: 'tm-report-popup-title-date'},
				text: this.data.INFO.DATE_TEXT
			})
		]
	});

	if (false && this.mode == 'admin') // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	{
		title.insertBefore(BX.create('SPAN', {
			props: {
				className: 'tm-report-popup-title-left'
			},
			events:
			{
				click: BX.proxy(this.ClickPrevious, this)
			}
		}), title.lastChild);

		title.appendChild(BX.create('SPAN', {
			props: {
				className: 'tm-report-popup-title-right'
			},
			events:
			{
				click: BX.proxy(this.ClickNext, this)
			}
		}))
	}

	return title;
}

BX.CTimeManReportFormWeekly.prototype.GetButtons = function()
{
	var b = [];
	if (this.mode == 'edit')
	{
		b.push(
			new BX.PopupWindowButton({
				text : BX.message('JS_CORE_TMR_SUBMIT_WEEKLY'),
				id:"tm-work-report-send",
				className : "popup-window-button-accept",
				events : {click: this.ACTIONS["send"]}
			})
		);
		b.push(
			new BX.PopupWindowButton({
				text : BX.message('JS_CORE_TM_B_SAVE'),
				id:"tm-work-report-save",
				className : "popup-window-button",
				events : {click: this.ACTIONS['save']}
			})
		);
		b.push(
			new BX.PopupWindowButton({
				text : BX.message('JS_CORE_TMR_DELAY_WEEKLY'),
				id:"tm-work-report-delay",
				className : "popup-window-button",
				events : {click: this.ACTIONS['delay']}
			})
		);

	}
	return b;
}


BX.CTimeManReportFormWeekly.prototype.ActionEdit = function()
{
	BX.timeman.showWait(this.popup.popupContainer,0);
	if (this.post == true)
		return;
	var i, l, data = {
			REPORT_ID:this.data.REPORT_ID,
			DATE_FROM:this.data.INFO.REPORT_DATE_FROM,
			DATE_TO:this.data.INFO.REPORT_DATE_TO,
			TO_USER: this.data.TO[0].ID,
			FILES:this.fileForm.files,
			PLANS:this.TABCONTROL.GetTabContent("plan_text"),
			REPORT:this.TABCONTROL.GetTabContent("report_text"),
			TASKS:[],
			TASKS_TIME:[],
			EVENTS:[],
			ACTIVE: this.ACTIVE,
			DELAY:this.DELAY
		}

		if (this.data.INFO.EVENTS)
		for (i = 0, l = this.data.INFO.EVENTS.length; i < l; i++)
			data.EVENTS.push(this.data.INFO.EVENTS[i]);
		if (this.listInputTasks)
		{
			for (i = 0, l = this.listInputTasks.length; i < l; i++)
			{
				if (this.listInputTasks[i].checked)
				{
					data.TASKS.push(this.data.INFO.TASKS[i]);
					data.TASKS_TIME.push(this.listInputTasksTime[i].value);
				}
			}
		}
		this.post = true;
		this.parent.Query('save_full_report', data, BX.proxy(this._ActionEdit, this));
}

BX.CTimeManReportFormWeekly.prototype.ActionSave = function(e)
{
		this.ACTIVE = "N";
		this.DELAY = "N";
		this.closeAfterPost = false;
		this.ActionEdit();
}

BX.CTimeManReportFormWeekly.prototype.ActionSend = function(e)
{
		this.ACTIVE = "Y";
		this.DELAY = "N";
		this.closeAfterPost = true;
		this.ActionEdit();
}




BX.CTimeManReportFormWeekly.prototype.ActionDelay = function(e)
{
	this.ACTIVE = "N";
	this.DELAY = "Y";
	this.closeAfterPost = true;
	this.ActionEdit();
}

BX.CTimeManReportFormWeekly.prototype._ActionEdit = function(report_id)
{
	this.post = false;
	if (this.ACTIVE == "Y")
	{
		BX.localStorage.set(this.data.REPORT_DATE_FROM+"#"+this.data.REPORT_DATE_TO+"_ACTION",{LAST_ACTION:"SEND_REPORT"});
		BX.localStorage.remove(this.data.REPORT_DATE_FROM+"#"+this.data.REPORT_DATE_TO+"_ACTION");
		this.parent.ShowCallReport = false;
		BX.onCustomEvent(this, 'OnWorkReportSend', []);
	}
	if(report_id)
		this.data.REPORT_ID = report_id;
	if(this.closeAfterPost == true)
		this.popup.close();
}


BX.CTimeManReportFormWeekly.prototype.ActionAdmin = function()
{
	var data = {
		ID: this.data.INFO.INFO.ID
	};

	if (this.bTimeEdited)
	{
		data.INFO = {
			TIME_START: this.data.INFO.INFO.TIME_START,
			TIME_FINISH: this.data.INFO.INFO.TIME_FINISH
		};
	}

	this.parent.Query('admin_save', data, BX.proxy(this._ActionAdmin, this));
}

BX.CTimeManReportFormWeekly.prototype._ActionAdmin = function(data)
{
	//TODO: we should update report cell here
	this.data = data;

	var tmp_data = BX.clone(this.data.INFO.INFO, true);
	tmp_data.ACTIVE = tmp_data.ACTIVE == 'Y';
	tmp_data.PAUSED = tmp_data.PAUSED == 'Y';
	tmp_data.ACTIVATED = tmp_data.ACTIVATED == 'Y';
	tmp_data.CAN_EDIT = this.data.INFO.CAN_EDIT == 'Y';

	this.params.parent_object.Reset(tmp_data);
}

BX.CTimeManReportFormWeekly.prototype.TimeEdit = function()
{
	if (this.POPUP_TIME)
	{
		this.POPUP_TIME.Clear();
		this.POPUP_TIME = null;
	}

	var tmp_data = {
		ID: this.data.INFO.INFO.ID,
		CAN_EDIT: this.mode == 'edit' || this.data.INFO.CAN_EDIT == 'Y',
		INFO: {
			DATE_START: this.data.INFO.INFO.DATE_START,
			TIME_START: this.data.INFO.INFO.TIME_START,
			TIME_FINISH: this.data.INFO.INFO.TIME_FINISH || this.data.INFO.EXPIRED_DATE,
			TIME_LEAKS: this.data.INFO.INFO.TIME_LEAKS,
			DURATION: this.data.INFO.INFO.DURATION
		}
	};

	if (!tmp_data.INFO.TIME_FINISH)
	{
		var q = new Date();
		tmp_data.INFO.TIME_FINISH = q.getHours()*3600 + q.getMinutes() * 60 - (this.params.parent_object ? this.params.parent_object.timezone_diff/1000 : 0);
	}

	this.POPUP_TIME = new BX.CTimeManEditPopup(this.parent, {
		node: this.TIME_EDIT_BUTTON || this.node,
		bind: this.TIME_EDIT_BUTTON || this.node,
		entry: tmp_data,
		mode: this.mode
	});

	this.POPUP_TIME.params.popup_buttons = [
		(this.POPUP_TIME.SAVEBUTTON = new BX.PopupWindowButton({
			text : BX.message('JS_CORE_TM_B_SAVE'),
			className : "popup-window-button-create",
			events : {click : BX.proxy(this.setEditValue, this)}
		})),
		new BX.PopupWindowButtonLink({
			text : BX.message('JS_CORE_TM_B_CLOSE'),
			className : "popup-window-button-link-cancel",
			events : {click : BX.proxy(this.POPUP_TIME.closeWnd, this.POPUP_TIME)}
		})
	];
	this.POPUP_TIME.WND.setButtons(this.POPUP_TIME.params.popup_buttons);

	this.POPUP_TIME._SetSaveButton = this.POPUP_TIME.SetSaveButton;
	this.POPUP_TIME.SetSaveButton = BX.DoNothing;
	this.POPUP_TIME.restoreButtons();
	this.POPUP_TIME.Show();
}

BX.CTimeManReportFormWeekly.prototype.setEditValue = function(e)
{
	var v, r = this.mode == 'edit' ? BX.util.trim(this.POPUP_TIME.REPORT.value) : BX.message('JS_CORE_TMR_ADMIN');
	if (r.length <= 0)
	{
		this.POPUP_TIME.REPORT.className = 'bx-tm-popup-clock-wnd-report-error';
		this.POPUP_TIME.REPORT.focus();
		this.POPUP_TIME.REPORT.onkeypress = function() {this.className = '';this.onkeypress = null;};
	}
	else
	{
		/*
		if (this.arPause.length > 0)
		{
			for(var i=0;i<this.arPause.length;i++)
				data[this.arPause[i].fld] = this.arPause[i].val
		}

		data.report = r;

		this.parent.PARENT.Query('save', data, BX.proxy(this.parent.PARENT._Update, this.parent.PARENT));

		this.bChanged = false;
		this.restoreButtons();

		this.SHOW = false;
		this.REPORT.value = '';

		this.arInputs[this.CLOCK_ID].value = this.arInputs[this.CLOCK_ID].BXORIGINALVALUE;
		this.arInputs[this.CLOCK_ID_1].value = this.arInputs[this.CLOCK_ID_1].BXORIGINALVALUE;
		this.arPause = [];

		this.WND.close();

		*/

		this.data.INFO.INFO.TIME_START = BX.timeman.unFormatTime(this.POPUP_TIME.arInputs.timeman_edit_from.value);
		this.data.INFO.INFO.TIME_FINISH = BX.timeman.unFormatTime(this.POPUP_TIME.arInputs.timeman_edit_to.value);

		this.data.INFO.INFO.DURATION = this.data.INFO.INFO.TIME_FINISH - this.data.INFO.INFO.TIME_FINISH - this.data.INFO.INFO.TIME_LEAKS;

		if (this.data.INFO.STATE == 'EXPIRED')
			this.data.INFO.EXPIRED_DATE = this.data.INFO.INFO.TIME_FINISH;

		var now = new Date();

		if (!this.data.REPORTS.DURATION)
			this.data.REPORTS.DURATION = [];

		this.data.REPORTS.DURATION[0] = {
			ACTIVE: true,
			REPORT: r,
			TIME: now.getHours() * 3600 + now.getMinutes() * 60 + now.getSeconds(),
			DATE_TIME: parseInt(now.valueOf() / 1000)
		};

		this.POPUP_TIME.WND.close();

		this.bTimeEdited = true;
		this.Show(this.data);
	}

	return BX.PreventDefault(e)
}

BX.CTimeManReportFormWeekly.prototype.ShowTpls = function(e)
{
	if (!this.TPLWND)
	{
		var content = BX.create('DIV', {props: {className: 'bx-tm-report-tpl'}}), TPLWND;

		var rep = this.REPORT_TEXT;
		var handler = function() {rep.value = this.BXTEXT; TPLWND.close();};

		for (var i=0; i<this.data.REPORT_TPL.length; i++)
		{
			content.appendChild(BX.create('SPAN', {
				props: {className: 'bx-tm-report-tpl-item',BXTEXT: BX.util.trim(this.data.REPORT_TPL[i])},
				events: {click: handler},
				text: BX.util.trim(this.data.REPORT_TPL[i])
			}));
		}

		TPLWND = this.TPLWND = BX.PopupWindowManager.create(
			'timeman_template_selector', BX.proxy_context,
			{
				autoHide: true,
				content: content
			}
		);
	}
	else
	{
		this.TPLWND.setBindElement(BX.proxy_context)
	}

	this.TPLWND.show();

	return BX.PreventDefault(e);
}

BX.CTimeManReportFormWeekly.prototype.ClickPrevious = function()
{
	alert('Previous!');

}

BX.CTimeManReportFormWeekly.prototype.ClickNext = function()
{
	alert('Next!');
}

BX.CTimeManReportFormWeekly.prototype.Clear = function()
{
	this.bCleared = true;

	if (this.POPUP_TIME)
	{
		this.POPUP_TIME.WND.close();
		this.POPUP_TIME.WND.destroy();
		this.POPUP_TIME.Clear();
		this.POPUP_TIME = null;
	}

	this.popup.close();
	this.popup.destroy();
}


/*******************************************************************************/
BX.CTimeManReportForm = function(parent, params)
{
	this.parent = parent;
	this.params = params;

	this.node = this.params.node;
	this.mode = this.params.mode || 'edit';

	this.data = params.data;
	this.external_finish_ts = params.external_finish_ts;
	this.external_finish_ts_report = params.external_finish_ts_report;

	this.popup_id = 'timeman_daily_report_popup_' + parseInt(Math.random() * 100000);

	this.bLoaded = !!this.data;
	this.bTimeEdited = false;

	this.params.offsetTop = 5;
	this.params.offsetLeft = -50;

	this.ie7 = false;
	/*@cc_on
		 @if (@_jscript_version <= 5.7)
			this.ie7 = true;
		/*@end
	@*/

	this.popup = new BX.PopupWindow(this.popup_id, this.params.bind, {
		closeIcon : {right: "12px", top: "10px"},
		offsetLeft : this.params.offsetLeft || 500,
		draggable: false, // !params.type,
		autoHide: false,
		closeByEsc: true,
		titleBar: true,
		bindOptions : {
			forceBindPosition : true,
			forceTop : false
		}
	});

	this.ACTIONS = {
		edit: BX.proxy(this.ActionEdit, this),
		admin: BX.proxy(this.ActionAdmin, this)
	}
}

BX.extend(BX.CTimeManReportForm, BX.CTimeManPopup)

BX.CTimeManReportForm.prototype.Show = function(data)
{
	if (!data && !this.data)
	{
		BX.timeman.showWait();
		if (this.mode == 'edit')
			BX.timeman_query('close', {}, BX.proxy(this.Show, this));
		return;
	}

	if (window.BXTIMEMANREPORTFORM && window.BXTIMEMANREPORTFORM != this)
		window.BXTIMEMANREPORTFORM.popup.close();

	window.BXTIMEMANREPORTFORM = this;

	BX.timeman.closeWait();

	this.data = data || this.data;

	if (this.external_finish_ts)
	{
		this.data.INFO.INFO.TIME_FINISH = this.external_finish_ts;
	}

	if (this.mode == 'edit' && this.data.INFO.STATE === 'EXPIRED' && !this.bTimeEdited)
	{
		this.TimeEdit();
		return;
	}
	else
	{
		BX.CTimeManReportForm.superclass.Show.apply(this);
		this.popup.setOffset({offsetTop: 1});
		return true;
	}
}

BX.CTimeManReportForm.prototype.GetContentTimeRow = function()
{
	var
		now = new Date(),

		tz_emp = parseInt(this.data.INFO.INFO.TIME_OFFSET),
		tz_self = parseInt(BX.message('USER_TZ_OFFSET')),

		time_finish = this.data.INFO.INFO.TIME_FINISH
			? this.data.INFO.INFO.TIME_FINISH
			: (
				this.data.INFO.STATE == 'EXPIRED'
					? this.data.INFO.EXPIRED_DATE
					: now.getHours() * 3600 + now.getMinutes() * 60 + now.getSeconds() - tz_self + tz_emp
			),
		duration = parseInt(this.data.INFO.INFO.DURATION) > 0
			? parseInt(this.data.INFO.INFO.DURATION)
			: time_finish - this.data.INFO.INFO.TIME_START - this.data.INFO.INFO.TIME_LEAKS;

	var obTime = BX.create('DIV', {props: {className: 'tm-report-popup-time-brief'}});

	obTime.appendChild(BX.create('SPAN', {
		props: {className: 'tm-report-popup-time-title'},
		text: BX.message('JS_CORE_TMR_WORKTIME')
	}));

	var bBrief = this.data.REPORTS && (
			this.data.REPORTS.DURATION && this.data.REPORTS.DURATION.length > 0
			|| this.data.REPORTS.TIME_START && this.data.REPORTS.TIME_START.length > 0
			|| this.data.REPORTS.TIME_FINISH && this.data.REPORTS.TIME_FINISH.length > 0
		);

	if (!bBrief)
	{
		var children = [
			BX.create('SPAN', {
				props: {className: 'tm-report-popup-time-label'},
				html: BX.message('JS_CORE_TM_ARR') + ':'
			}),
			BX.create('SPAN', {
				props: {className: 'tm-report-popup-time-value'},
				html: BX.timeman.formatTime(this.data.INFO.INFO.TIME_START, false)
			}),
			BX.create('SPAN', {props: {className: 'tm-report-popup-time-separator'}})
		]

		if (this.data.INFO.INFO.TIME_LEAKS > 0)
		{
			children = BX.util.array_merge(children, [
				BX.create('SPAN', {
					props: {className: 'tm-report-popup-time-label'},
					html: BX.message('JS_CORE_TMR_PAUSE') + ':'
				}),
				BX.create('SPAN', {
					props: {className: 'tm-report-popup-time-value'},
					html: BX.timeman.formatWorkTime(this.data.INFO.INFO.TIME_LEAKS, false)
				}),
				BX.create('SPAN', {props: {className: 'tm-report-popup-time-separator'}})
			]);
		}

		children = BX.util.array_merge(children, [
			BX.create('SPAN', {
				props: {className: 'tm-report-popup-time-label'},
				html: BX.message('JS_CORE_TM_DEP') + ':'
			}),
			BX.create('SPAN', {
				props: {className: 'tm-report-popup-time-value'},
				html: BX.timeman.formatTime(time_finish, false)
			}),
			BX.create('SPAN', {props: {className: 'tm-report-popup-time-separator'}}),

			BX.create('SPAN', {
				props: {className: 'tm-report-popup-time-label'},
				html: BX.message('JS_CORE_TMR_DURATION') + ':'
			}),
			BX.create('SPAN', {
				props: {className: 'tm-report-popup-time-value'},
				html: BX.timeman.formatWorkTime(duration, false)
			})
		]);

		BX.adjust(obTime, {children: [BX.create('SPAN', {
			props: {className: 'tm-report-popup-time-data'},
			children: children
		})]});
	}



	if (this.data.INFO.CAN_EDIT == 'Y' || this.data.CAN_EDIT == 'Y')
	{
		this.TIME_EDIT_BUTTON = obTime.appendChild(BX.create('SPAN', {
			props: {className: 'tm-report-popup-time-edit'},
			events: {click: BX.proxy(this.TimeEdit, this)}
		}));
	}

	obTime = [obTime];

	if (bBrief)
	{
		var time_extra = '', obTable = null;

		obTime[1] = BX.create('DIV', {
			props: {className: 'tm-report-popup-time-full'},
			children: [
				BX.create('TABLE', {
					attrs: {cellSpacing: 0},
					props: {className: 'tm-report-popup-time-grid' + (this.data.INFO.INFO.TIME_LEAKS > 0 ? '' :  ' tm-report-popup-time-grid-minimal')},
					children: [
						(obTable = BX.create('TBODY'))
					]
				})
			]
		});

		var obRow = obTable.insertRow(-1);
		var obCell = BX.adjust(obRow.insertCell(-1), {
			props: {
				className: 'tm-report-popup-time-start' +
					(!this.data.REPORTS.TIME_START
						? ''
						: (this.data.REPORTS.TIME_START[0].ACTIVE
							? ' tm-report-popup-time-changed'
							: ' tm-report-popup-time-approved'
						))
			},
			html: '<span class="tm-report-popup-time-label">' + BX.message('JS_CORE_TM_ARR') + ':</span><span class="tm-report-popup-time-value">' + BX.timeman.formatTime(this.data.INFO.INFO.TIME_START, false) + '</span>'
		});

		if (this.data.REPORTS.TIME_START)
		{
			time_extra += '<tr><td class="tm-report-popup-time-extra-label">' + BX.message('JS_CORE_TMR_REPORT_START') + ':</td><td class="tm-report-popup-time-extra-text">' + this.data.REPORTS.TIME_START[0].REPORT + '</td></tr>';

			obCell.appendChild(BX.create('SPAN', {
				props: {className: 'tm-report-popup-time-real'},
				html: '(<span class="tm-report-popup-time-label">' + BX.message('JS_CORE_TMR_REPORT_ORIG') + ':</span><span class="tm-report-popup-time-value">' + BX.timeman.formatTime(this.data.REPORTS.TIME_START[0].TIME%86400, false) + '</span>)'
			}));

			obCell.appendChild(BX.create('SPAN', {
				props: {className: 'tm-report-popup-time-fixed'},
				html: this.data.REPORTS.TIME_START[0].ACTIVE
					? BX.message('JS_CORE_TMR_NA')
					: BX.message('JS_CORE_TMR_A') + ' ' + this.data.REPORTS.TIME_START[0].DATE_TIME
			}));
		}

		if (this.data.INFO.INFO.TIME_LEAKS > 0)
		{
			var pauseCont;

			obCell = BX.adjust(obRow.insertCell(-1), {
				props: {
					className: 'tm-report-popup-time-break'
				},
				children: [
					BX.create('SPAN', {props: {className: 'tm-report-popup-time-label'}, html: BX.message('JS_CORE_TMR_PAUSE') + ':'}),
					(pauseCont = BX.create('SPAN', {
						props: {className: 'tm-report-popup-time-value'},
						html: BX.timeman.formatWorkTime(this.data.INFO.INFO.TIME_LEAKS)
					}))
				]
			});

			if (this.data.INFO.INFO.PAUSED === 'Y' || this.data.INFO.INFO.PAUSED === true)
			{
				BX.timer(pauseCont, {
					from: (this.data.INFO.INFO.DATE_FINISH * 1000) || new Date(),
					dt: (1000 * this.data.INFO.INFO.TIME_LEAKS),
					display: 'worktime'
				});
			}
		}

		var finishCont, durationCont;

		obCell = BX.adjust(obRow.insertCell(-1), {
			props: {
				className: 'tm-report-popup-time-end' +
					(!this.data.REPORTS.TIME_FINISH
						? ''
						: (this.data.REPORTS.TIME_FINISH[0].ACTIVE
							? ' tm-report-popup-time-changed'
							: ' tm-report-popup-time-approved'
						))
			},
			children: [
				BX.create('SPAN', {props: {className: 'tm-report-popup-time-label'}, html: BX.message('JS_CORE_TM_DEP') + ':'}),
				(finishCont = BX.create('SPAN', {
					props: {className: 'tm-report-popup-time-value' + (this.data.INFO.STATE == 'EXPIRED' ? ' tm-report-popup-time-expired' : '')},
					html: BX.timeman.formatTime(time_finish, false)
				}))
			]
		});

		if (time_finish === 0)
			BX.timer.clock(finishCont, this.params.parent_object.timezone_diff ? this.params.parent_object.timezone_diff : 0);

		if (this.data.REPORTS.TIME_FINISH)
		{
			time_extra += '<tr><td class="tm-report-popup-time-extra-label">' + BX.message('JS_CORE_TMR_REPORT_FINISH') + ':</td><td class="tm-report-popup-time-extra-text">' + this.data.REPORTS.TIME_FINISH[0].REPORT + '</td></tr>';

			obCell.appendChild(BX.create('SPAN', {
				props: {className: 'tm-report-popup-time-real'},
				html: '(<span class="tm-report-popup-time-label">' + BX.message('JS_CORE_TMR_REPORT_ORIG') + ':</span><span class="tm-report-popup-time-value">' + BX.timeman.formatTime(this.data.REPORTS.TIME_FINISH[0].TIME%86400, false) + '</span>)'
			}))

			obCell.appendChild(BX.create('SPAN', {
				props: {className: 'tm-report-popup-time-fixed'},
				html: this.data.REPORTS.TIME_FINISH[0].ACTIVE
					? BX.message('JS_CORE_TMR_NA')
					: BX.message('JS_CORE_TMR_A') + ' ' + this.data.REPORTS.TIME_FINISH[0].DATE_TIME
			}));
		}

		obCell = BX.adjust(obRow.insertCell(-1), {
			props: {
				className: 'tm-report-popup-time-duration' +
					(!this.data.REPORTS.DURATION
						? ''
						: (this.data.REPORTS.DURATION[0].ACTIVE
							? ' tm-report-popup-time-changed'
							: ' tm-report-popup-time-approved'
						))
			},
			children: [
				BX.create('SPAN', {props: {className: 'tm-report-popup-time-label'}, html: BX.message('JS_CORE_TMR_DURATION') + ':'}),
				(durationCont = BX.create('SPAN', {
					props: {className: 'tm-report-popup-time-value' + (this.data.INFO.STATE == 'EXPIRED' ? ' tm-report-popup-time-expired' : '')},
					html: BX.timeman.formatWorkTime(duration)
				}))
			]
		});

		if (this.data.REPORTS.DURATION)
		{
			time_extra += '<tr><td class="tm-report-popup-time-extra-label">' + BX.message('JS_CORE_TMR_REPORT_DURATION') + ':</td><td class="tm-report-popup-time-extra-text">' + this.data.REPORTS.DURATION[0].REPORT + '</td></tr>';

			obCell.appendChild(BX.create('SPAN', {
				props: {className: 'tm-report-popup-time-real'},
				html: '(<span class="tm-report-popup-time-label">' + BX.message('JS_CORE_TMR_REPORT_ORIG') + ':</span><span class="tm-report-popup-time-value">' + BX.timeman.formatTime(this.data.REPORTS.DURATION[0].TIME%86400, false) + '</span>)'
			}))

			obCell.appendChild(BX.create('SPAN', {
				props: {className: 'tm-report-popup-time-fixed'},
				html: this.data.REPORTS.DURATION[0].ACTIVE
					? BX.message('JS_CORE_TMR_NA')
					: BX.message('JS_CORE_TMR_A') + ' ' + this.data.REPORTS.DURATION[0].DATE_TIME
			}));
		}

		if (time_extra)
		{
			obTime[2] = BX.create('DIV', {
				props: {className: 'tm-report-popup-time-extra'},
				html: '<div class="tm-report-popup-time-extra-inner"><table class="tm-report-popup-time-extra-layout" cellspacing="0"><tbody>'
				+ time_extra
				+ '</tbody></table></div>'
			});
		}
	}

	this.ROW_TIME = BX.create('DIV', {
		props: {className: 'tm-report-popup-time'},
		children: [
			BX.create('DIV', {props: {className: 'tm-report-popup-r1'}}),
			BX.create('DIV', {props: {className: 'tm-report-popup-r0'}}),
			BX.create('DIV', {
				props: {className: 'tm-report-popup-time-inner'},
				children: obTime
			}),
			BX.create('DIV', {props: {className: 'tm-report-popup-r0'}}),
			BX.create('DIV', {props: {className: 'tm-report-popup-r1'}})
		]
	});

	return this.ROW_TIME;
}

BX.CTimeManReportForm.prototype.GetContentPeopleRow = function()
{
	return BX.create('DIV', {
		props: {className: 'tm-report-popup-people'},
		html: '<div class="tm-report-popup-r1"></div><div class="tm-report-popup-r0"></div>\
<div class="tm-report-popup-people-inner">\
	<div class="tm-report-popup-user tm-report-popup-employee">\
		<span class="tm-report-popup-user-label">' + BX.message('JS_CORE_TMR_FROM') + ':</span><a href="' + this.data.FROM.URL + '" class="tm-report-popup-user-avatar"' + (this.data.FROM.PHOTO ? ' style="background: url(\'' + this.data.FROM.PHOTO + '\') no-repeat scroll center center transparent;"' : '') + '></a><span class="tm-report-popup-user-info"><a href="' + this.data.FROM.URL + '" class="tm-report-popup-user-name">' + this.data.FROM.NAME + '</a><span class="tm-report-popup-user-position">' + (this.data.FROM.WORK_POSITION || '&nbsp;') + '</span></span>\
	</div>\
	<div class="tm-report-popup-user tm-report-popup-director">\
		<span class="tm-report-popup-user-label">' + BX.message('JS_CORE_TMR_TO') + ':</span><a href="' + this.data.TO[0].URL + '" class="tm-report-popup-user-avatar"' + (this.data.TO[0].PHOTO ? ' style="background: url(\'' + this.data.TO[0].PHOTO + '\') no-repeat scroll center center transparent;"' : '') + '></a><span class="tm-report-popup-user-info"><a href="' + this.data.TO[0].URL + '" class="tm-report-popup-user-name">' + BX.util.htmlspecialchars(this.data.TO[0].NAME) + '</a><span class="tm-report-popup-user-position">' + (BX.util.htmlspecialchars(this.data.TO[0].WORK_POSITION || '') || '&nbsp;') + '</span></span>\
	</div>\
</div>\
<div class="tm-report-popup-r0"></div><div class="tm-report-popup-r1"></div>'
	});
}

BX.CTimeManReportForm.prototype.GetContentReportRow = function(report_value)
{
	return this.mode == 'edit'
		? BX.create('DIV', {
			props: {className: 'tm-report-popup-desc'},
			children: [
				BX.create('DIV', {
					props: {className: 'tm-report-popup-desc-title'},
					children: [
						BX.create('DIV', {props: {className: 'tm-report-popup-desc-label'}, text: BX.message('JS_CORE_TMR_REPORT')}),
						(this.data.REPORT_TPL.length > 0 ? BX.create('DIV', {
							props: {className: 'tm-report-popup-desc-templates'},
							events: {
								click: BX.delegate(this.ShowTpls, this)
							},
							children: [
								BX.create('SPAN', {
									props: {className: 'tm-report-popup-desc-templates-label'},
									text: BX.message('JS_CORE_TMR_REPORT_TPL')
								}),
								BX.create('SPAN', {
									props: {className: 'tm-report-popup-desc-templates-arrow'}
								})
							]
						}) : null)
					]
				}),
				BX.create('DIV', {
					props: {className: 'tm-report-popup-desc-text'},
					children: [
						(this.REPORT_TEXT = BX.create('TEXTAREA', {
							props: {
								className: 'tm-report-popup-desc-textarea',
								value: report_value || this.data.REPORT
							},
							attrs: {
								rows: '5', cols: '65'
							}

						}))
					]
				})
			]
		})
		: (
			this.data.REPORT.length > 0
				? BX.create('DIV', {
					props: {className: 'tm-report-popup-desc'},
					html: '<div class="tm-popup-section-title"><div class="tm-popup-section-title-text">' + BX.message('JS_CORE_TMR_REPORT') + '</div><div class="tm-popup-section-title-line"></div></div><div class="tm-report-popup-desc-text">' + this.data.REPORT + '</div>'
				})
				: null
		);
}

BX.CTimeManReportForm.prototype.GetContentTasks = function(tdTasks, tasks_unchecked, tasks_time)
{
	tdTasks.className = 'tm-report-popup-tasks';
	tdTasks.appendChild(BX.create('DIV', {
		props: {className: 'tm-popup-section-title'},
		html: '<div class="tm-popup-section-title-text">' + BX.message('JS_CORE_TM_TASKS') + '</div>'
		+ (false && this.mode == 'edit' ? '<div class="tm-popup-section-title-link">' + BX.message('JS_CORE_TM_TASKS_CHOOSE') + '</div>' : '') +
		'<div class="tm-popup-section-title-line"></div>'
	}));

	if (this.data.INFO.TASKS && this.data.INFO.TASKS.length > 0)
	{
		this.listTasks = null;
		tdTasks.appendChild(BX.create('DIV', {
			props: {className: 'tm-popup-tasks' + (this.data.INFO.TASKS.length > 10 ? ' tm-popup-tasks-tens' : '')},
			children: [
				this.mode == 'admin' ? null : BX.create('DIV', {
					props: {className: 'tm-report-popup-inlude-tasks'},
					html: '<span class="tm-report-popup-inlude-arrow"></span><span class="tm-report-popup-inlude-hint">' + BX.message('JS_CORE_TMR_REPORT_INC') + '</span>'
				}),
				(this.listTasks = BX.create('OL', {props: {className: 'tm-popup-task-list'}}))
			]
		}));

		this.listInputTasks = []; this.listInputTasksTime = [];
		for (var i=0;i<this.data.INFO.TASKS.length;i++)
		{
			var inp, inpTime;

			if (typeof this.data.INFO.TASKS[i].TIME == 'undefined')
				this.data.INFO.TASKS[i].TIME = 0;

			var taskTime = typeof tasks_time[i] == 'undefined' ? this.data.INFO.TASKS[i].TIME : tasks_time[i];

			this.listTasks.appendChild(
				BX.create('LI', {
					props: {
						className: 'tm-popup-task tm-popup-task-status-' + BX.timeman.TASK_SUFFIXES[this.data.INFO.TASKS[i].STATUS],
						bx_task_id: this.data.INFO.TASKS[i].ID
					},
					children:
					[
						(inp = this.mode == 'admin' ? null : BX.create('INPUT', {
							props: {
								className: 'tm-report-popup-include-checkbox',
								value: this.data.INFO.TASKS[i].ID,
								checked: typeof tasks_unchecked[i] == 'undefined'
									? taskTime > 0 : false,
								defaultChecked: true
							},
							attrs: {type: 'checkbox'}
						})),
						BX.create('SPAN', {
							props: {
								className: 'tm-popup-task-name',
								BXPOPUPBIND: tdTasks.firstChild,
								BXPOPUPPARENT: this.listTasks,
								BXPOPUPANGLEOFFSET: 44
							},
							text: this.data.INFO.TASKS[i].TITLE,
							events: {click: BX.proxy(BX.CTimeManWindow.prototype.showTask, this)}
						}),
						(inpTime = this.mode == 'admin' ? null : BX.create('INPUT', {
							props: {value: taskTime},
							attrs: {type: 'hidden'}
						})),
						BX.create('SPAN', {
							props: {
								className: 'tm-popup-task-time' + (this.mode == 'admin' ? '-admin' : ''),
								BXTIMEINPUT: this.mode == 'admin' ? null : inpTime,
								BXCHECKINPUT: this.mode == 'admin' ? null : inp
							},
							events: this.mode == 'admin' ? null : {click:BX.timeman.editTime},
							text: BX.timeman.formatWorkTime(taskTime)
						})

						/*BX.create('SPAN', {
							props: {className: 'tm-popup-task-delete'},
							events: {click: BX.proxy(this.parent.WND.removeTask, this.parent.WND)}
						})*/
					]
				})
			);

			if (inp)
			{
				this.listInputTasks.push(inp);
				this.listInputTasksTime.push(inpTime);
			}
		}
	}

	if (this.mode == 'edit')
	{
		this.tasksForm = tdTasks.appendChild(this.parent.WND.CreateTasksForm(BX.proxy(this.taskEntryAdd, this)));
		if (!this.data.INFO.TASKS || this.data.INFO.TASKS.length <= 0)
		{
			this.tasksForm.style.marginLeft = '0px';
		}
	}
}

BX.CTimeManReportForm.prototype.taskEntryAdd = function(task)
{
	this.parent.taskEntryAdd(task, BX.delegate(function(data) {
		this.parent._Update(data);
		this.data = null;
		this.Show();
	}, this));
}

BX.CTimeManReportForm.prototype.GetContentEvents = function(tdEvents)
{
	tdEvents.className = 'tm-report-popup-events' + (BX.isAmPmMode() ? " tm-popup-events-ampm" : "");
	tdEvents.appendChild(BX.create('DIV', {
		props: {className: 'tm-popup-section-title'},
		html: '<div class="tm-popup-section-title-text">' + BX.message('JS_CORE_TM_EVENTS') + '</div>\
<div class="tm-popup-section-title-line"></div>'
	}));

	if (this.data.INFO.EVENTS && this.data.INFO.EVENTS.length > 0)
	{
		this.listEvents = null;
		tdEvents.appendChild(BX.create('DIV', {
			props: {className: 'tm-popup-events' },
			children: [
				(this.listEvents = BX.create('DIV', {props: {className: 'tm-popup-event-list'}}))
			]
		}));

		for (var i=0;i<this.data.INFO.EVENTS.length;i++)
		{
			this.listEvents.appendChild(
				BX.CTimeManWindow.prototype.CreateEvent(this.data.INFO.EVENTS[i], {
					BXPOPUPBIND: tdEvents.firstChild,
					BXPOPUPANGLEOFFSET: 44
				})
			)
		}
	}

	if (this.mode == 'edit')
	{
		this.eventsForm = tdEvents.appendChild(this.parent.WND.CreateEventsForm(BX.proxy(this.calendarEntryAdd, this)));
	}
}

BX.CTimeManReportForm.prototype.calendarEntryAdd = function(ev)
{
	this.parent.calendarEntryAdd(ev, BX.delegate(function(data) {
		if (data && data.error && data.error_id == 'CHOOSE_CALENDAR')
		{
			this.Update = BX.proxy(this.parent.Update, this.parent); // hack
			(new BX.CTimeManCalendarSelector(this, {
				node: this.eventsForm,
				data: data.error
			})).Show();
		}
		else
		{
			this.parent._Update(data);
			this.data = null;
			this.Show();
		}
	}, this));
}

BX.CTimeManReportForm.prototype.GetContentComments = function()
{
	var comment_link_span, comment_area_edit, comment_text,
		entry_id = this.data.INFO.ID || this.data.INFO.INFO.ID,
		owner_id = this.data.FROM.ID || this.data.INFO.INFO.USER_ID;

	var sendComment = function()
	{
		if (comment_text.value.length<=0)
			return;

		var data = {
			comment_text: comment_text.value,
			entry_id: entry_id,
			owner_id: owner_id
		};

		// comments_div.style.minHeight = "50px";
		// BX.timeman.showWait(this.comments_div,0);
		comment_area_edit.style.display = "none";
		comment_link_span.style.display = "block";
		comment_text.value = "";

		BX.timeman_query("add_comment_entry", data, BX.proxy(function(data){
			if (data.COMMENTS)
				comment_form.firstChild.innerHTML = data.COMMENTS;
			comment_form.parentNode.scrollTop = comment_form.offsetHeight;
		},this));
	};

	var enterHandler = function(e) {
		if(((e.keyCode == 0xA)||(e.keyCode == 0xD)) && e.ctrlKey == true)
			sendComment.apply(this, []);
	};

	var comment_form = BX.create("DIV",{
		props:{className:"tm-comment-link-div"},
		children:[
			BX.create('DIV', {html: this.data.COMMENTS}),
			comment_link_span = BX.create("SPAN",{
				props:{className:"tm-item-comments-add"},
				children:[
					BX.create("A",{
						attrs:{href:"javascript:void(0)"},
						html:BX.message("JS_CORE_TMR_ADD_COMMENT"),
						events:{"click":
							BX.delegate(
								function()
								{
									comment_area_edit.style.display = "block";
									comment_link_span.style.display = "none";
									comment_form.parentNode.scrollTop = comment_form.offsetHeight;
//										this.slider.FixOverlay();
								},
								this
							)
						}
					})
				]

			}),
			(comment_area_edit = BX.create("DIV",{
				style:{display:"none"},
				children:[
					(comment_text = BX.create("TEXTAREA",{
						props:{className:"tm_comment_text"},
						attrs:{cols:35,rows:4},
						events:{keypress:BX.proxy(enterHandler,this)}
					})),
					BX.create("DIV",{
						children:[
							BX.create("INPUT",{
								attrs:{type:"button",value:BX.message("JS_CORE_TMR_SEND_COMMENT")},
								events:{"click":BX.proxy(sendComment,this)}
							})
					]})
				]
			}))
		]
	});
	return BX.create('DIV', {
		style: {
			marginTop: '6px',
			overflow: 'auto',
			maxHeight: '200px'
		},
		children: [comment_form]
	});
}


BX.CTimeManReportForm.prototype.GetContent = function()
{
	var report_value = '', tasks_unchecked = {}, tasks_time = {};

	if (this.DIV)
	{
		report_value = this.REPORT_TEXT ? this.REPORT_TEXT.value : '';

		if (this.listInputTasks)
		{
			for (i = 0, l = this.listInputTasks.length; i < l; i++)
			{
				if (!this.listInputTasks[i].checked)
				{
					tasks_unchecked[i] = true;
				}
				tasks_time[i] = this.listInputTasksTime[i].value;
			}
		}

		BX.cleanNode(this.DIV);
		this.DIV = null;
	}

	this.DIV = BX.create('DIV', {
		props: {className: 'tm-report-popup' + (this.mode == 'edit' ? '' : ' tm-report-popup-read-mode') + (this.ie7 ? ' tm-report-popup-ie7' : '')},
		children: [
			this.GetContentPeopleRow(),
			this.GetContentTimeRow(),
			this.GetContentReportRow(report_value)
		]
	});

	if (this.mode == 'edit' ||
		this.data.INFO.TASKS && this.data.INFO.TASKS.length > 0 || this.data.INFO.EVENTS && this.data.INFO.EVENTS.length > 0)
	{

		var tr = this.DIV.appendChild(BX.create('TABLE', {
			props: {className: 'tm-report-popup-items'},
			attrs: {cellSpacing: '0'},
			children: [BX.create('TBODY')]
		})).tBodies[0].insertRow(-1);

		if (this.data.INFO.TASKS_ENABLED && (this.mode == 'edit' || this.data.INFO.TASKS && this.data.INFO.TASKS.length > 0))
			this.GetContentTasks(tr.insertCell(-1), tasks_unchecked, tasks_time);
		if (this.data.INFO.CALENDAR_ENABLED && (this.mode == 'edit' || this.data.INFO.EVENTS && this.data.INFO.EVENTS.length > 0))
			this.GetContentEvents(tr.insertCell(-1));
	}

	this.DIV.appendChild(BX.create('DIV', {props: {className: 'tm-popup-section-title-line'}}));
	this.DIV.appendChild(this.GetContentComments());

	return this.DIV;
}

BX.CTimeManReportForm.prototype.GetTitle = function()
{
	var
		title = BX.create('DIV', {
			props: {className: 'tm-report-popup-titlebar'},
			html: '<div class="tm-report-popup-title"><span>'+BX.util.htmlspecialchars(BX.message('JS_CORE_TMR_TITLE'))+'</span></div><span class="tm-report-popup-title-date">'+BX.util.htmlspecialchars(this.data.INFO.DATE_TEXT)+'</span>'
		});

	if (this.mode != 'edit')
	{
		var
			tz_emp = parseInt(this.data.INFO.INFO.TIME_OFFSET)+parseInt(BX.message('SERVER_TZ_OFFSET')),
			tz_self = parseInt(BX.message('USER_TZ_OFFSET'))+parseInt(BX.message('SERVER_TZ_OFFSET'));

		title.firstChild.appendChild(BX.create('SPAN', {
			props: {className: 'tm-report-popup-title-additional'},
			events: {
				mouseover: BX.delegate(function() {
					BX.hint(BX.proxy_context, '<div class="tm-report-popup-titlebar-hint">' + BX.message('JS_CORE_TMR_TITLE_HINT').replace('#IP_OPEN#', this.data.INFO.INFO.IP_OPEN).replace('#IP_CLOSE#', this.data.INFO.INFO.IP_CLOSE||'N/A').replace('#TIME_OFFSET#', (tz_emp > 0 ? '+' : '-')+BX.timeman.formatTime(Math.abs(tz_emp), false, true)).replace('#TIME_OFFSET_SELF#', (tz_self > 0 ? '+' : '-')+BX.timeman.formatTime(Math.abs(tz_self), false, true))+'</div>');
				}, this)
			}
		}));
	}

	return title;
}

BX.CTimeManReportForm.prototype.GetButtons = function()
{
	var b = [];
	if (this.mode == 'edit')
	{
		b.push(
			new BX.PopupWindowButton({
				text : BX.message('JS_CORE_TM_CLOSE'),
				className : "popup-window-button-decline",
				events : {click: this.ACTIONS[this.mode]}
			})
		);
	}
	else
	{
		if (this.data.INFO.CAN_EDIT == 'Y' || this.data.CAN_EDIT == 'Y')
		{
			this.SAVEBUTTON = new BX.PopupWindowButton({
				text : this.data.INFO.INFO.ACTIVE == 'Y' ? BX.message('JS_CORE_TM_B_SAVE') : BX.message('JS_CORE_TM_CONFIRM'),
				className : this.data.INFO.INFO.ACTIVE == 'Y' ? "" : "popup-window-button-accept",
				events: {click: this.ACTIONS[this.mode]}
			});
			b.push(this.SAVEBUTTON);
		}
	}

	b.push(new BX.PopupWindowButtonLink({
		text : BX.message('JS_CORE_TM_B_CLOSE'),
		className : "popup-window-button-link-cancel",
		events : {click : function(e) {this.popupWindow.close();return BX.PreventDefault(e);}}
	}));

	return b;
}

BX.CTimeManReportForm.prototype.CheckReport = function()
{
	if (this.data.REPORT_REQ == 'Y')
	{
		var r = this.REPORT_TEXT.value.replace(/\s/g, '');
		if (r.length <= 0)
			return false;

		if (this.data.REPORT_TPL && this.data.REPORT_TPL.length > 0)
		{
			for (var i=0; i < this.data.REPORT_TPL.length; i++)
			{
				if (r == this.data.REPORT_TPL[i].replace(/\s/g, ''))
					return false;
			}
		}
	}

	return true;
}

BX.CTimeManReportForm.prototype.ActionEdit = function(e)
{
	if (this.data.INFO.STATE === 'EXPIRED' && !this.bTimeEdited)
	{
		this.TimeEdit();
	}
	else if (!this.CheckReport())
	{
		BX.addClass(this.REPORT_TEXT, 'bx-tm-popup-report-error');
		this.REPORT_TEXT.focus();
		this.REPORT_TEXT.onkeypress = function() {BX.removeClass(this, 'bx-tm-popup-report-error'); this.onkeypress = null;};
	}
	else
	{
		var i, l, data = {
			REPORT: BX.util.trim(this.REPORT_TEXT.value),
			ready: 'Y'
		}

		data.TASKS = []; data.TASKS_TIME = [];
		if (this.listInputTasks)
		{
			for (i = 0, l = this.listInputTasks.length; i < l; i++)
			{
				if (this.listInputTasks[i].checked)
				{
					data.TASKS.push(this.listInputTasks[i].value);
					data.TASKS_TIME.push(this.listInputTasksTime[i].value);
				}
			}
		}

		if (this.bTimeEdited)
		{
			if (this.POPUP_TIME.arInputs[this.POPUP_TIME.CLOCK_ID].value != this.POPUP_TIME.arInputs[this.POPUP_TIME.CLOCK_ID].BXORIGINALVALUE)
			{
				data[this.POPUP_TIME.CLOCK_ID] = BX.timeman.unFormatTime(this.POPUP_TIME.arInputs[this.POPUP_TIME.CLOCK_ID].value);
			}

			if (this.POPUP_TIME.arInputs[this.POPUP_TIME.CLOCK_ID_1].value != this.POPUP_TIME.arInputs[this.POPUP_TIME.CLOCK_ID_1].BXORIGINALVALUE || this.data.INFO.STATE === 'EXPIRED')
			{
				data[this.POPUP_TIME.CLOCK_ID_1] = BX.timeman.unFormatTime(this.POPUP_TIME.arInputs[this.POPUP_TIME.CLOCK_ID_1].value);
			}

			if (this.POPUP_TIME.INPUT_TIME_LEAKS.value != this.POPUP_TIME.INPUT_TIME_LEAKS.BXORIGINALVALUE)
			{
				data['TIME_LEAKS'] = BX.timeman.unFormatTime(this.POPUP_TIME.INPUT_TIME_LEAKS.value);
			}

			data.report = this.data.REPORTS.DURATION && this.data.REPORTS.DURATION[0] ? this.data.REPORTS.DURATION[0].REPORT : '';
		}
		else if (this.external_finish_ts)
		{
			data.timeman_edit_to = this.external_finish_ts
			data.report = this.external_finish_ts_report;
		}

		this.parent.Query('close', data, BX.proxy(this._ActionEdit, this));
	}

	return BX.PreventDefault(e);
}

BX.CTimeManReportForm.prototype._ActionEdit = function()
{
	this.popup.close();
	this.parent._Update.apply(this.parent, arguments);
}


BX.CTimeManReportForm.prototype.ActionAdmin = function()
{
	if (this.bTimeEdited || this.data.INFO.INFO.ACTIVE != 'Y')
	{
		var data = {
			ID: this.data.INFO.INFO.ID
		};

		if (this.bTimeEdited)
		{
			data.INFO = {
				TIME_START: this.data.INFO.INFO.TIME_START,
				TIME_FINISH: this.data.INFO.INFO.TIME_FINISH,
				TIME_LEAKS: this.data.INFO.INFO.TIME_LEAKS
			};
		}

		BX.timeman_query('admin_save', data, BX.proxy(this._ActionAdmin, this));
	}
	else
	{
		this.popup.close();
	}
}

BX.CTimeManReportForm.prototype._ActionAdmin = function(data)
{
	//TODO: we should update report cell here

	this.data = data;

	var tmp_data = BX.clone(this.data.INFO.INFO, true);
	tmp_data.ACTIVE = tmp_data.ACTIVE == 'Y';
	tmp_data.PAUSED = tmp_data.PAUSED == 'Y';
	tmp_data.ACTIVATED = tmp_data.ACTIVATED == 'Y';
	tmp_data.CAN_EDIT = (this.data.INFO.CAN_EDIT == 'Y' || this.data.CAN_EDIT == 'Y') ? 'Y' : 'N';

	BX.onCustomEvent(this, 'onEntryNeedReload', [tmp_data]);
	//this.params.parent_object.Reset(tmp_data);

	this.bTimeEdited = false;
}

BX.CTimeManReportForm.prototype.TimeEdit = function()
{
	if (this.POPUP_TIME)
	{
		this.POPUP_TIME.Clear();
		this.POPUP_TIME = null;
	}

	var tmp_data = {
		ID: this.data.INFO.INFO.ID,
		INFO: {
			CAN_EDIT: /*this.mode == 'edit' || */this.data.INFO.CAN_EDIT == 'Y' || this.data.CAN_EDIT == 'Y' ? 'Y' : 'N',
			DATE_START: this.data.INFO.INFO.DATE_START,
			TIME_START: this.data.INFO.INFO.TIME_START,
			TIME_FINISH: this.data.INFO.INFO.TIME_FINISH || this.data.INFO.EXPIRED_DATE,
			TIME_LEAKS: this.data.INFO.INFO.TIME_LEAKS,
			DURATION: this.data.INFO.INFO.DURATION
		}
	};

	if (!tmp_data.INFO.TIME_FINISH)
	{
		var q = new Date();
		tmp_data.INFO.TIME_FINISH = q.getHours()*3600 + q.getMinutes() * 60 - (this.params.parent_object ? this.params.parent_object.timezone_diff/1000 : 0);
	}

	this.POPUP_TIME = new BX.CTimeManEditPopup(this.parent, {
		node: this.TIME_EDIT_BUTTON || this.node,
		bind: this.TIME_EDIT_BUTTON || this.node,
		entry: tmp_data,
		mode: this.mode
	});

	this.POPUP_TIME.params.popup_buttons = [
		(this.POPUP_TIME.SAVEBUTTON = new BX.PopupWindowButton({
			text : BX.message('JS_CORE_TM_B_SAVE'),
			className : "popup-window-button-create",
			events : {click : BX.proxy(this.setEditValue, this)}
		})),
		new BX.PopupWindowButtonLink({
			text : BX.message('JS_CORE_TM_B_CLOSE'),
			className : "popup-window-button-link-cancel",
			events : {click : BX.proxy(this.POPUP_TIME.closeWnd, this.POPUP_TIME)}
		})
	];
	this.POPUP_TIME.WND.setButtons(this.POPUP_TIME.params.popup_buttons);

	this.POPUP_TIME._SetSaveButton = this.POPUP_TIME.SetSaveButton;
	this.POPUP_TIME.SetSaveButton = BX.DoNothing;
	this.POPUP_TIME.restoreButtons();
	this.POPUP_TIME.Show();
}

BX.CTimeManReportForm.prototype.setEditValue = function(e)
{
	var v, r = this.mode == 'edit' ? BX.util.trim(this.POPUP_TIME.REPORT.value) : BX.message('JS_CORE_TMR_ADMIN');
	if (r.length <= 0)
	{
		this.POPUP_TIME.REPORT.className = 'bx-tm-popup-clock-wnd-report-error';
		this.POPUP_TIME.REPORT.focus();
		this.POPUP_TIME.REPORT.onkeypress = function() {this.className = '';this.onkeypress = null;};
	}
	else
	{
		if (this.data.INFO.CAN_EDIT == 'Y' || this.data.CAN_EDIT == 'Y')
		{
			this.data.INFO.INFO.TIME_START = BX.timeman.unFormatTime(this.POPUP_TIME.arInputs.timeman_edit_from.value);
			this.data.INFO.INFO.TIME_LEAKS = BX.timeman.unFormatTime(this.POPUP_TIME.INPUT_TIME_LEAKS.value);
		}

		if (this.data.INFO.CAN_EDIT == 'Y' || this.data.CAN_EDIT == 'Y' || this.data.INFO.STATE == 'EXPIRED')
		{
			this.data.INFO.INFO.TIME_FINISH = BX.timeman.unFormatTime(this.POPUP_TIME.arInputs.timeman_edit_to.value);
		}

		if (this.data.INFO.STATE == 'EXPIRED')
		{
			this.data.INFO.EXPIRED_DATE = this.data.INFO.INFO.TIME_FINISH;
		}

		this.data.INFO.INFO.DURATION = this.data.INFO.INFO.TIME_FINISH - this.data.INFO.INFO.TIME_FINISH - this.data.INFO.INFO.TIME_LEAKS;

		var now = new Date();

		if (!this.data.REPORTS.DURATION)
			this.data.REPORTS.DURATION = [];

		this.data.REPORTS.DURATION[0] = {
			ACTIVE: true,
			REPORT: r,
			TIME: now.getHours() * 3600 + now.getMinutes() * 60 + now.getSeconds(),
			DATE_TIME: parseInt(now.valueOf() / 1000)
		};

		this.POPUP_TIME.WND.close();

		this.bTimeEdited = true;
		this.Show(this.data);

		if (this.mode == 'admin')
		{
			this.SAVEBUTTON.setClassName('popup-window-button-accept');
		}
	}

	return BX.PreventDefault(e)
}

BX.CTimeManReportForm.prototype.ShowTpls = function(e)
{
	if (!this.TPLWND)
	{
		var content = BX.create('DIV', {props: {className: 'bx-tm-report-tpl'}}), TPLWND;

		var rep = this.REPORT_TEXT;
		var handler = function() {rep.value = this.BXTEXT; TPLWND.close();};

		for (var i=0; i<this.data.REPORT_TPL.length; i++)
		{
			var text = BX.util.trim(this.data.REPORT_TPL[i]);
			content.appendChild(BX.create('SPAN', {
				props: {className: 'bx-tm-report-tpl-item',BXTEXT: text},
				events: {click: handler},
				text: text || BX.message('JS_CORE_EMPTYTPL')
			}));
		}

		TPLWND = this.TPLWND = BX.PopupWindowManager.create(
			'timeman_template_selector_' + Math.random(), BX.proxy_context,
			{
				autoHide: true,
				content: content
			}
		);
	}
	else
	{
		this.TPLWND.setBindElement(BX.proxy_context)
	}

	this.TPLWND.show();

	return BX.PreventDefault(e);
}

BX.CTimeManReportForm.prototype.Clear = function()
{
	this.bCleared = true;

	if (this.POPUP_TIME)
	{
		this.POPUP_TIME.WND.close();
		this.POPUP_TIME.WND.destroy();
		this.POPUP_TIME.Clear();
		this.POPUP_TIME = null;
	}

	this.popup.close();
	this.popup.destroy();
}

/****view********************************************************************/

BX.JSTimeManReportFullForm = function(userdata, slider)
{

	this.popupform = null;
	this.slider = slider;
	this.report_data = userdata;
	this.cell = false;
	this.data = null;
	this.empty_slider = "Y";
	if (this.popupform == null)
	{
		this.popupform = new BX.PopupWindow(
				"popup_report_"+Math.random(),
				null,
				{
					autoHide : false,
					closeIcon: { right: "12px", top: "10px"},
					draggable:false,
					titleBar:true,
					closeByEsc:true,
					bindOnResize:false

				}
			);
		//closebyEsc disable fix for task-popup
		try
		{
			BX.addCustomEvent(taskIFramePopup, 'onBeforeShow', BX.proxy(function(){
				this.popupform.setClosingByEsc(false);
				if(this.slider){
					this.slider.nextReportLink.style.visibility = "hidden";
					this.slider.prevReportLink.style.visibility = "hidden";
					this.slider.closeLink.style.visibility = "hidden";
				}
			},this));
			BX.addCustomEvent(taskIFramePopup, 'onBeforeHide', BX.proxy(function(){
				this.popupform.setClosingByEsc(true);
				if(this.slider){
					this.slider.nextReportLink.style.visibility = "visible";
					this.slider.prevReportLink.style.visibility = "visible";
					this.slider.closeLink.style.visibility = "visible";
				}
			},this));
		}catch(e){}
	}
	BX.bind(this.cell, 'click', BX.proxy(this.Click, this));
}

BX.JSTimeManReportFullForm.prototype.setData = function(data)
{
	this.data = data;
	if(this.data.REPORT_LIST.length>0 && this.empty_slider == "Y")
	{
		this.empty_slider = "N";
		this.report_list = this.data.REPORT_LIST;
	}
	this.GetContent();
	/*if (this.popupform.isShown() != true)*/
		this.Show();

}

BX.JSTimeManReportFullForm.prototype.Click = function()
{
		BX.timeman.showWait(this.popupform.popupContainer,0);
		BX.timeman_query('admin_report_full', {
			report_id: this.report_data.ID,
			user_id: this.report_data.USER_ID,
			empty_slider:this.empty_slider
		},BX.proxy(this.setData,this));
}
BX.JSTimeManReportFullForm.prototype.Show = function(data)
{
	this.popupform.show();
}

BX.JSTimeManReportFullForm.prototype.Clear = function()
{
	this.bCleared = true;
	if (this.popupform)
	{
		this.popupform.close();
		this.popupform.destroy();
	}
}

BX.JSTimeManReportFullForm.prototype.EditMode = function()
{
	this.TABCONTROL.SwitchEdit();
	this.EditLink.style.display = "none";
	this.SaveLink.style.display = "inline-block";
	this.EditLink.click = BX.proxy(this.SaveReportText,this);
	this.slider.FixOverlay();
}

BX.JSTimeManReportFullForm.prototype.SaveReportText = function()
{
	var data = {
		report_id: this.data.INFO.ID,
		user_id:this.data.INFO.USER_ID,
		report_text:this.TABCONTROL.GetTabContent('report_text_'+this.data.INFO.USER_ID),
		plan_text:this.TABCONTROL.GetTabContent('plan_text_'+this.data.INFO.USER_ID),
		edit_report:"Y"
	};
	BX.timeman.showWait(this.popupform.popupContainer,0);
	BX.timeman_query('user_report_edit',data,BX.proxy(this.UpdateReportArea,this));
}

BX.JSTimeManReportFullForm.prototype.UpdateReportArea = function(data)
{
	if (data.success == true)
	{
		this.TABCONTROL.SwitchView();
		this.SaveLink.style.display = "none";
		this.EditLink.style.display = "inline-block";
	}
	this.slider.FixOverlay();
}


BX.JSTimeManReportFullForm.prototype.GetContent = function()
{
	var report_value = '', tasks_unchecked = {}, tasks_time = {};
	if(!this.WND)
		this.WND = new BX.CTimeManWindow(this.popupform);
	for (var key in this.WND.EVENTWND) {
		var val = this.WND.EVENTWND[key];
		val.Clear();
	}
	var title = BX.create("DIV",{
		props:{className:"tm-report-title"},
		html:this.data.INFO.TEXT_TITLE});
	this.fileForm = false;
	if(this.data.INFO.CAN_EDIT_TEXT == "Y" || (this.data.INFO.FILES && this.data.INFO.FILES.length>0))
	{
		this.fileForm = new BX.CTimeManUploadForm({
			id:this.data.INFO.ID,
			user_id: this.data.INFO.USER_ID,
			files_list:this.data.INFO.FILES,
			mode:(this.data.INFO.CAN_EDIT_TEXT == "Y"?"edit":"view")
		});
			BX.addCustomEvent(this.fileForm, "OnUploadFormRefresh", BX.proxy(function(){
				this.slider.FixOverlay();
			},this));
	}

	var content = BX.create("DIV",{
		props:{className:'tm-report-popup tm-report-popup-read-mode'+(this.ie7 ? ' tm-report-popup-ie7' : '')},
		children:
		[
			this.GetPeople(),
			this.GetReportRow(),
			((this.fileForm)?this.fileForm.GetUploadForm():null)
		]
	}
	);
	if(this.data.INFO.TASKS && this.data.INFO.TASKS.length > 0 || this.data.INFO.EVENTS && this.data.INFO.EVENTS.length > 0)
	{

		var tr = content.appendChild(BX.create('TABLE', {
			props: {className: 'tm-report-popup-items'},
			attrs: {cellSpacing: '0'},
			children: [BX.create('TBODY')]
		})).tBodies[0].insertRow(-1);

		if (this.data.INFO.TASKS_ENABLED && (this.mode == 'edit' || this.data.INFO.TASKS && this.data.INFO.TASKS.length > 0))
			this.GetTasks(tr.insertCell(-1), tasks_unchecked, tasks_time);
		if (this.data.INFO.CALENDAR_ENABLED && (this.mode == 'edit' || this.data.INFO.EVENTS && this.data.INFO.EVENTS.length > 0))
			this.GetEvents(tr.insertCell(-1));
	}
	this.selectMark = this.data.INFO.MARK;

	BX.onCustomEvent(this, 'onWorkReportMarkChange', [this.data]);
	if (this.data.INFO.CAN_EDIT)
	{
		this.mark_div = BX.create('DIV', {
			props: {className: "tm-popup-estimate-popup-center"},
			children:[
						(this.markg = BX.create('DIV',{
							props:{className:'tm-popup-estimate-but tm-but-plus'+((this.selectMark == "G")?' tm-but-active':'')},
							html:'<span class="tm-popup-estimate-but-l"></span><span class="tm-popup-estimate-but-c"><span class="tm-popup-estimate-but-icon"></span><span class="tm-popup-estimate-but-text">'+BX.message("JS_CORE_TMR_MARK_G_W")+'</span></span><span class="tm-popup-estimate-but-r"></span>',
							events:{'click':BX.proxy(function(){this.ChangeMark("G")},this)}
						})),
						(this.markb = BX.create('DIV',{
							props:{className:'tm-popup-estimate-but tm-but-minus'+((this.selectMark == "B")?' tm-but-active':'')},
							html:'<span class="tm-popup-estimate-but-l"></span><span class="tm-popup-estimate-but-c"><span class="tm-popup-estimate-but-icon"></span><span class="tm-popup-estimate-but-text">'+BX.message("JS_CORE_TMR_MARK_B_W")+'</span></span><span class="tm-popup-estimate-but-r"></span>',
							events:{'click':BX.proxy(function(){this.ChangeMark("B")},this)}
						})),
						(this.markn = BX.create('DIV',{
							props:{className:'tm-popup-estimate-but tm-but-not-rated'+((this.selectMark == "N")?' tm-but-active':'')},
							html:'<span class="tm-popup-estimate-but-l"></span><span class="tm-popup-estimate-but-c"><span class="tm-popup-estimate-but-icon"></span><span class="tm-popup-estimate-but-text">'+BX.message("JS_CORE_TMR_MARK_N_W")+'</span></span><span class="tm-popup-estimate-but-r"></span>',
							events:{'click':BX.proxy(function(){this.ChangeMark("N")},this)}
						})),
						(this.markx = BX.create('DIV',{
							props:{className:'tm-popup-estimate-but tm-but-notconfirm'+((this.selectMark == "X")?' tm-but-active':'')},
							html:'<span class="tm-popup-estimate-but-l"></span><span class="tm-popup-estimate-but-c"><span class="tm-popup-estimate-but-icon"></span><span class="tm-popup-estimate-but-text">'+BX.message("JS_CORE_TMR_MARK_X")+'</span></span><span class="tm-popup-estimate-but-r"></span>',
							events:{'click':BX.proxy(function(){this.ChangeMark("X")},this)}
						})),
						BX.create("DIV",{
							props:{className:"tm-popup-bord"},
							html:'<div class="tm-popup-estimate-top-bord"></div><div class="tm-popup-estimate-bot-bord"></div>'
						}
						)


			]
		});
	}
	else
	{
		this.mark_div = BX.create('DIV', {
			props: {className: "tm-popup-estimate-popup-center"},
			children:[
				BX.create("DIV",{
					props:{className:"mark-clean report-mark-"+this.selectMark +"-clean"},
					html:BX.message("JS_CORE_TMR_MARK_"+this.selectMark)

				}
				),
				'<div class="tm-popup-bord"><div class="tm-popup-estimate-top-bord"></div><div class="tm-popup-estimate-bot-bord"></div></div>'
			]
			}
		);

	}
		var approve_info = "";
		approve_info = "<div class=\"tm-not-approve\">"+BX.message('JS_CORE_TMR_NOT_ACCEPT')+"</div>";
		if (this.data.INFO.APPROVER<=0 && !this.data.INFO.CAN_EDIT)
		{
			this.info = BX.create('DIV', {
							props:{className:"tm-popup-estimate-right"},
							style:{width:"100%"},
							html:approve_info
						});
			this.mark_area = BX.create('DIV', {
				props:{className:"tm-popup-estimate-item"},
				children:[
					'<div class="tm-popup-section-title-line"></div>',
					this.info

				]
			});
		}
		else
		{
			if (this.data.INFO.APPROVER>0)
			{
				approve_info= "<span class=\'tm-popup-est-right-item tm-popup-item-name\'>"+BX.message('JS_CORE_TMR_REPORT_APPROVER')+":</span><span class='tm-popup-est-right-item'>"+"<a href=\""+this.data.INFO.APPROVER_INFO.URL+"\">"+this.data.INFO.APPROVER_INFO.NAME+"</a>"+"</span>";
				approve_info+="<span class=\'tm-popup-est-right-item tm-popup-item-name\'>"+BX.message('JS_CORE_TMR_ACCEPT_DATE')+":</span><span class='tm-popup-est-right-item'>"+this.data.INFO.APPROVE_DATE+"</span>";
			}

			this.info = BX.create('DIV', {
							props:{className:"tm-popup-estimate-right"},
							html:approve_info
						});
			this.mark_area = BX.create('DIV', {
				props:{className:"tm-popup-estimate-item"},
				children:[
					'<div class="tm-popup-section-title-line"></div>',
					BX.create("DIV",{
						props:{className:"tm-popup-estimate-popup-left"},
						children:[
							'<div class="tm-popup-estimate-cont">'+((this.data.INFO.CAN_EDIT)?BX.message("JS_CORE_TMR_APPROVING_REPORT"):BX.message("JS_CORE_TMR_MARK"))+'</div>',
							'<div class="tm-popup-bord"><div class="tm-popup-estimate-top-bord"></div><div class="tm-popup-estimate-bot-bord"></div>'
						]
					}
					),
					this.mark_div,
					this.info

				]
			});
		}
		this.enterHandler = function(e) {if(((e.keyCode == 0xA)||(e.keyCode == 0xD)) && e.ctrlKey == true) this.AddComment();}
		this.comment_form = BX.create("DIV",{
			props:{className:"tm-comment-link-div"},
			children:[
				this.comment_link_span = BX.create("SPAN",{
					props:{className:"tm-item-comments-add"},
					children:[
						BX.create("A",{
							attrs:{href:"javascript:void(0)"},
							html:BX.message("JS_CORE_TMR_ADD_COMMENT"),
							events:{"click":
								BX.proxy(
									function()
									{
										this.comment_area_edit.style.display = "block";
										this.comment_link_span.style.display = "none";
										this.slider.FixOverlay();
									},
									this
								)
							}
						})
					]

				}),
				(this.comment_area_edit = BX.create("DIV",{
					style:{display:"none"},
					children:[
						(this.comment_text = BX.create("TEXTAREA",{
							props:{className:"tm_comment_text"},
							attrs:{cols:35,rows:4},
							events:{keypress:BX.proxy(this.enterHandler,this)}
						})),
						BX.create("DIV",{
							children:[
								BX.create("INPUT",{
									attrs:{type:"button",value:BX.message("JS_CORE_TMR_SEND_COMMENT")},
									events:{"click":BX.proxy(this.AddComment,this)}
								})
						]})
					]
				}))
			]
		});
		content.appendChild(
				BX.create('DIV', {
				children:[
					((this.mark_area)?this.mark_area:null),
					BX.create("DIV",{
						props:{className:"tm-popup-section-title-line"}
					}),
					(this.comments_div = BX.create("DIV",{
						style:{marginTop:"6px"},
						html:this.data.COMMENTS

					})),
					this.comment_form
				]
			})
		);
	this.popupform.setContent(content);
	this.popupform.setTitleBar({content :title});
	BX.timeman.closeWait();
}

BX.JSTimeManReportFullForm.prototype.GetEvents = function(tdEvents)
{
	tdEvents.className = 'tm-report-popup-events';
		tdEvents.appendChild(BX.create('DIV', {
			props: {className: 'tm-popup-section-title'},
			html: '<div class="tm-popup-section-title-text">' + BX.message('JS_CORE_TM_EVENTS') + '</div>\
	<div class="tm-popup-section-title-line"></div>'
		}));

		if (this.data.INFO.EVENTS && this.data.INFO.EVENTS.length > 0)
		{
			this.listEvents = null;
			tdEvents.appendChild(BX.create('DIV', {
				props: {className: 'tm-popup-events'},
				children: [
					(this.listEvents = BX.create('DIV', {props: {className: 'tm-popup-event-list'}}))
				]
			}));

			for (var i=0;i<this.data.INFO.EVENTS.length;i++)
			{
				this.listEvents.appendChild(
					this.WND.CreateEvent(this.data.INFO.EVENTS[i], {
						BXPOPUPBIND: tdEvents.firstChild,
						BXPOPUPANGLEOFFSET: 44
						},true)
					)
			}
		}
}

BX.JSTimeManReportFullForm.prototype.GetPeople = function()
{
	return BX.create('DIV', {
		props: {className: 'tm-report-popup-people'},
		html: '<div class="tm-report-popup-r1"></div><div class="tm-report-popup-r0"></div>\
<div class="tm-report-popup-people-inner">\
	<div class="tm-report-popup-user tm-report-popup-employee">\
		<span class="tm-report-popup-user-label">' + BX.message('JS_CORE_TMR_FROM') + ':</span><a href="' + this.data.FROM.URL + '" class="tm-report-popup-user-avatar"' + (this.data.FROM.PHOTO ? ' style="background: url(\'' + this.data.FROM.PHOTO + '\') no-repeat scroll center center transparent;"' : '') + '></a><span class="tm-report-popup-user-info"><a href="' + this.data.FROM.URL + '" class="tm-report-popup-user-name">' + this.data.FROM.NAME + '</a><span class="tm-report-popup-user-position">' + (this.data.FROM.WORK_POSITION || '&nbsp;') + '</span></span>\
	</div>\
	<div class="tm-report-popup-user tm-report-popup-director">\
		<span class="tm-report-popup-user-label">' + BX.message('JS_CORE_TMR_TO') + ':</span><a href="' + this.data.TO[0].URL + '" class="tm-report-popup-user-avatar"' + (this.data.TO[0].PHOTO ? ' style="background: url(\'' + this.data.TO[0].PHOTO + '\') no-repeat scroll center center transparent;"' : '') + '></a><span class="tm-report-popup-user-info"><a href="' + this.data.TO[0].URL + '" class="tm-report-popup-user-name">' + this.data.TO[0].NAME + '</a><span class="tm-report-popup-user-position">' + (this.data.TO[0].WORK_POSITION || '&nbsp;') + '</span></span>\
	</div>\
</div>\
<div class="tm-report-popup-r0"></div><div class="tm-report-popup-r1"></div>'
	});
}

BX.JSTimeManReportFullForm.prototype.GetReportRow = function()
{
	this.TABCONTROL = new BX.CTimeManTabEditorControl({
			lhename:"obReportForm"+this.data.INFO.USER_ID,
			parent:this
		}
	);
	this.TABCONTROL.addTab({
		ID:"report_text_"+this.data.INFO.USER_ID,
		TITLE:BX.message('JS_CORE_TMR_REPORT'),
		CONTENT:((this.data.INFO.REPORT_STRIP_TAGS.length>0)?this.data.INFO.REPORT:"<i style='color:#999'>"+BX.message("JS_CORE_TMR_REPORT_EMPTY"))+"</i>"
	});
	this.TABCONTROL.addTab({
		ID:"plan_text_"+this.data.INFO.USER_ID,
		TITLE:BX.message('JS_CORE_TMR_PLAN'),
		CONTENT:((this.data.INFO.PLAN_STRIP_TAGS.length>0)?this.data.INFO.PLANS:"<i style='color:#999'>"+BX.message("JS_CORE_TMR_PLAN_EMPTY"))+"</i>"
	});
	return  BX.create('DIV', {
					props: {className: 'tm-report-popup-desc'},
					children:[
						BX.create("DIV",{
								props:{className:"tm-report-popup-desc-text-view"},
								children:[
									((this.data.INFO.CAN_EDIT_TEXT=="Y")?
										BX.create("SPAN",{
											props:{className:"tm-link-div"},
											children:[
												this.EditLink = BX.create("A",{
														props:{className:"tm-edit-link"},
														text:BX.message("JS_CORE_TMR_PARENT_EDIT"),
														events:{"click":BX.proxy(this.EditMode,this)}
													}),
												this.SaveLink = BX.create("DIV",{
														props:{className:"tm-ag-buttons-save"},
														style:{display:"none"},
														children:[
															BX.create("SPAN",{
																props:{className:"tm-ag-buttons-left"}
															}),
															BX.create("SPAN",{
																props:{className:"tm-ag-buttons-cont"},
																children:[
																	BX.create("SPAN",{
																		props:{className:"tm-ag-buttons-icon"}
																	}),
																	"<span>"+BX.message("JS_CORE_TM_B_SAVE")+"</span>"
																]
															}),
															BX.create("SPAN",{
																props:{className:"tm-ag-buttons-right"}
															})
														],
														events:{"click":BX.proxy(this.SaveReportText,this)}
													})
											]

											}):null),
											this.TABCONTROL.div

								]
							})

					]
				});
}

BX.JSTimeManReportFullForm.prototype.GetTasks = function(tdTasks, tasks_unchecked, tasks_time)
{
	tdTasks.className = 'tm-report-popup-tasks';
	tdTasks.appendChild(BX.create('DIV', {
		props: {className: 'tm-popup-section-title'},
		html: '<div class="tm-popup-section-title-text">' + BX.message('JS_CORE_TM_TASKS') + '</div>'
		+ (false && this.mode == 'edit' ? '<div class="tm-popup-section-title-link">' + BX.message('JS_CORE_TM_TASKS_CHOOSE') + '</div>' : '') +
		'<div class="tm-popup-section-title-line"></div>'
	}));
	if (this.data.INFO.TASKS && this.data.INFO.TASKS.length > 0)
	{
		this.listTasks = null;
		tdTasks.appendChild(BX.create('DIV', {
			props: {className: 'tm-popup-tasks' + (this.data.INFO.TASKS.length > 10 ? ' tm-popup-tasks-tens' : '')},
			children: [
				(this.listTasks = BX.create('OL', {props: {className: 'tm-popup-task-list'}}))
			]
		}));

		this.listInputTasks = []; this.listInputTasksTime = [];
		for (var i=0;i<this.data.INFO.TASKS.length;i++)
		{
			var inp, inpTime;

			if (typeof this.data.INFO.TASKS[i].TIME == 'undefined')
				this.data.INFO.TASKS[i].TIME = 0;

			var taskTime = typeof tasks_time[i] == 'undefined' ? this.data.INFO.TASKS[i].TIME : tasks_time[i];
			this.listTasks.appendChild(
				BX.create('LI', {
					props: {
						className: 'tm-popup-task tm-popup-task-status-' + BX.timeman.TASK_SUFFIXES[this.data.INFO.TASKS[i].STATUS],
						bx_task_id: this.data.INFO.TASKS[i].ID
					},
					children:
					[
						BX.create('SPAN', {
							props: {
								className: 'tm-popup-task-name'
							},
							text: this.data.INFO.TASKS[i].TITLE,
							events: {click: BX.proxy(this.WND.showTask, this)}
						}),
						BX.create('SPAN', {
							props: {
								className: 'tm-popup-task-time-admin'
							},
						text: BX.timeman.formatWorkTime(taskTime)
						})

						/*BX.create('SPAN', {
							props: {className: 'tm-popup-task-delete'},
							events: {click: BX.proxy(this.parent.WND.removeTask, this.parent.WND)}
						})*/
					]
				})
			);

			if (inp)
			{
				this.listInputTasks.push(inp);
				this.listInputTasksTime.push(inpTime);
			}
		}
	}
}


BX.JSTimeManReportFullForm.prototype.Approve = function()
{
	var data = {
		mark: this.selectMark,
		approve:"Y",
		user_id:this.data.FROM.ID,
		report_id:this.data.INFO.ID
	}
	BX.timeman.showWait(this.mark_div,0);
	BX.timeman_query("admin_report_full",data,BX.proxy(
	function(data){
		BX.onCustomEvent(this, 'onWorkReportMarkChange', [data]);
		this.setData(data);
	},this));
}

BX.JSTimeManReportFullForm.prototype.AddComment = function()
{
	if (this.comment_text.value.length<=0)
		return;
	var data = {
		comment_text:this.comment_text.value,
		report_owner:this.data.FROM.ID,
		report_id:this.data.INFO.ID,
		add_comment:"Y"

	};
	this.comments_div.style.minHeight = "50px";
	BX.timeman.showWait(this.comments_div,0);
	this.comment_area_edit.style.display = "none";
	this.comment_link_span.style.display = "block";
	this.comment_text.value = "";
	BX.timeman_query("add_comment_full_report",data,BX.proxy(this.RefreshComments,this));
}

BX.JSTimeManReportFullForm.prototype.RefreshComments = function(data)
{
	if(data.COMMENTS)
	{
		this.comments_div.innerHTML = data.COMMENTS;
		if (BX("report_comments_count_"+this.data.INFO.ID) && data.COMMENTS_COUNT>0)
		{
			var comments = BX("report_comments_count_"+this.data.INFO.ID);
			comments.style.display = "inline-block";
			comments.innerHTML = data.COMMENTS_COUNT;
		}
		this.slider.FixOverlay();
	}
}

/************************************************************************/
BX.bindFullReport = function(report_id,user_id)
{
	if (!window["report"+report_id])
		window["report"+report_id] = new BX.JSTimeManReportFullForm({ID:report_id,USER_ID:user_id});
	window["report"+report_id].Click();
}

/************************************************************************/

BX.JSTimeManReportFullForm.prototype.ChangeMark = function(mark)
{
	var report_mark = mark.toLowerCase();
	BX.removeClass(this.markb,"tm-but-active");
	BX.removeClass(this.markn,"tm-but-active");
	BX.removeClass(this.markx,"tm-but-active");
	BX.removeClass(this.markg,"tm-but-active");
	BX.addClass(this["mark"+report_mark],"tm-but-active");
	if(this.selectMark!=mark)
	{
		this.selectMark = mark;
		this.Approve();
	}
}

/************************************************************************/
BX.StartSlider = function(user,start)
{
	BX.timeman.showWait(document.body,0);
	if (!window["report"+user])
	{
		window["report"+user] = new BX.ReportSlider(user,start);
	}

	window["report"+user].ShowReport(start);
}
BX.ReportSlider = function(user,start_report_id)
{
	this.cur_report = start_report_id || false;
	this.reports = {};
	this.user_id = user;
	this.popup = false;

	this.table = BX.create("TABLE",{
			props:{className:"report-popup-main-table"},
			children:[
				BX.create("TBODY",{
					children:[
						BX.create("TR",{
							children:[
								this.prev = BX.create("TD",{
									props:{className:"report-popup-prev-slide-wrap"},
									children:[
										this.prevReportLink = BX.create("A",{
											props:{className:"report-popup-prev-slide"},
											attrs:{href:"javascript: void(0)"},
											children:[
												BX.create("SPAN",{})
											],
											events:{"click":BX.proxy(this.PrevReport,this)}
										})
									]
								}),
								this.popup_place = BX.create("TD",{
									attrs:{align:"center",valign:"top"},
									style:{paddingTop:"20px"},
									props:{className:"report-popup-main-block-wrap"}
								}),
								this.next = BX.create("TD",{
									props:{className:"report-popup-next-slide-wrap"},
									children:[
										this.closeLink = BX.create("A",{
											attrs:{href:"javascript: void(0)"},
											events:{"click":BX.proxy(this.PopupClose,this)},
											props:{className:"report-popup-close"},
											children:[
												BX.create("SPAN",{
													props:{className:"report-popup-close"}
													})
											]
										}),
										this.nextReportLink = BX.create("A",{
											attrs:{href:"javascript: void(0)"},
											props:{className:"report-popup-next-slide"},
											children:[
												BX.create("SPAN",{})
											],
											events:{"click":BX.proxy(this.NextReport,this)}
										})
									]

								})
							]

						})
					]

				})
			]
		}
	);
	this.overlay = BX.create("DIV",{
		props:{className:"report-fixed-overlay"},
		children:[
			(this.coreoverlay = BX.create("DIV",{
				props:{className:"bx-tm-dialog-overlay"}
				//attrs:{width:"100%",heigth:"100%"}
				}
			)),
			this.table
		]
	}
	);
	document.body.appendChild(this.overlay);
	BX.bind(window.top, "resize", BX.proxy(this.FixOverlay, this))
}

BX.ReportSlider.prototype.ShowReport = function(report_id)
{
	this.cur_report = report_id;

	if(!this.report_form)
	{
		this.report_form = new BX.JSTimeManReportFullForm({ID:this.cur_report,USER_ID:this.user_id},this);
		BX.addCustomEvent(this.report_form.popupform, "onPopupClose", BX.proxy(function(){
			this.overlay.style.display = "none";
			BX.removeClass(document.body, "report-body-overflow");
			}, this));
		BX.addCustomEvent(this.report_form.popupform, "onPopupShow", BX.proxy(function(){
			this.overlay.style.display = "block";
			BX.addClass(document.body, "report-body-overflow");
			}, this));
		BX.addCustomEvent(this.report_form.popupform, "onAfterPopupShow", BX.proxy(function(){
		this.report_form.popupform.popupContainer.style.position = "relative";
		this.report_form.popupform.popupContainer.style.left = "0px";
		this.FixOverlay();
		}, this));

		this.popup_place.appendChild(
			BX.create("DIV",{
			style:{display:"inline-block"},
			children:[
				BX(this.report_form.popupform.uniquePopupId)
			]
			}
			)

			);

	}

	this.report_form.report_data = {ID:this.cur_report,USER_ID:this.user_id};

	this.report_form.Click();

}
BX.ReportSlider.prototype.FixOverlay = function()
{
		this.report_form.popupform.popupContainer.style.position = "relative";
		this.report_form.popupform.popupContainer.style.left = "0px";
		var size = BX.GetWindowInnerSize();
		this.overlay.style.height = size.innerHeight + "px";
		this.overlay.style.width = size.innerWidth + "px";
		var scroll = BX.GetWindowScrollPos();

		if (BX.browser.IsIE() && !BX.browser.IsIE9())
		{
			this.table.style.width = (size.innerWidth - 20) + "px";
		}
		//this.table.style.height = this.overlay.style.height + "px";
		this.overlay.firstChild.style.height = Math.max(this.report_form.popupform.popupContainer.offsetHeight+50, this.overlay.clientHeight)+"px";
		this.overlay.firstChild.style.width = Math.max(1024, this.overlay.clientWidth) + "px";
		this.report_form.popupform.popupContainer.style.top = "0px";
		this.Recalc();
		this.__adjustControls();
}

BX.ReportSlider.prototype.__adjustControls = function(){
		/*if (this.lastAction != "view" || ((!this.currentList || this.currentList.length <= 1 || this.__indexOf(this.currentTaskId, this.currentList) == -1) && (this.tasksList.length <= 1 || this.__indexOf(this.currentTaskId, this.tasksList) == -1)))
		{
			this.nextReportLink.style.display = this.prevReportLink.style.display = "none";
		}
		else*/
		{
			if(!BX.browser.IsDoctype() && BX.browser.IsIE())
			{
				this.nextReportLink.style.height = this.prevReportLink.style.height = document.documentElement.offsetHeight + "px";
				this.prevReportLink.style.width = (this.prevReportLink.parentNode.clientWidth - 1) + 'px';
				this.nextReportLink.style.width = (this.nextReportLink.parentNode.clientWidth - 1) + 'px';
			}
			else
			{
				this.nextReportLink.style.height = this.prevReportLink.style.height = document.documentElement.clientHeight + "px";
				this.prevReportLink.style.width = this.prevReportLink.parentNode.clientWidth + 'px';
				this.nextReportLink.style.width = this.nextReportLink.parentNode.clientWidth + 'px';
			}
			this.prevReportLink.firstChild.style.left = (this.prevReportLink.parentNode.clientWidth * 4 / 10) + 'px';
			this.nextReportLink.firstChild.style.right = (this.nextReportLink.parentNode.clientWidth * 4 / 10) + 'px';

		}
		this.closeLink.style.width = this.closeLink.parentNode.clientWidth + 'px';
}

BX.ReportSlider.prototype.Recalc = function()
{
	if(!this.report_form || this.report_form.empty_slider != "N")
		return;
	var len = this.report_form.report_list.length;
	if (len == 1)
	{
		this.nextReportLink.style.display = "none";
		this.prevReportLink.style.display = "none";
	}
	else
	{
		for(i=0;i<len;i++)
		{
			if(this.report_form.report_list[i] == this.cur_report)
			{
				if (len == (i+1))
				{
					this.nextReportLink.style.display = "none";
					this.prevReportLink.style.display = "block";
				}
				else if(i==0)
				{
					this.nextReportLink.style.display = "block";
					this.prevReportLink.style.display = "none";
				}
				else
				{
					this.nextReportLink.style.display = "block";
					this.prevReportLink.style.display = "block";
				}
				break;
			}

		}
	}
};
BX.ReportSlider.prototype.NextReport = function()
{
	var nextreport = this.cur_report;
	for(i=0;i<this.report_form.report_list.length;i++)
	{
		if((this.report_form.report_list[i] == this.cur_report)
			&& ((i+1)!=this.report_form.report_list.length)
		)
		{
			nextreport = this.report_form.report_list[i+1];
			break;
		}
	}
	if(nextreport!=this.cur_report)
		this.ShowReport(nextreport)

}
BX.ReportSlider.prototype.PrevReport = function()
{
	var prevreport = this.cur_report;
	for(i=0;i<this.report_form.report_list.length;i++)
	{
		if((this.report_form.report_list[i] == this.cur_report)
			&& i!=0)
		{
			prevreport = this.report_form.report_list[i-1];
			break;
		}
	}
	if(prevreport!=this.cur_report)
		this.ShowReport(prevreport)

}

BX.ReportSlider.prototype.PopupClose = function()
{
	this.report_form.popupform.close();
}

BX.StartNotifySlider = function(user, start, type)
{
	BX.timeman.showWait(document.body, 0);
	if (!window["timeman_notify_"+user])
	{
		window["timeman_notify_"+user] = new BX.TimeManSlider(user,start,type);
	}
	else
	{
		window["timeman_notify_"+user].Load(start);
	}
}

BX.TimeManSlider = function(user,start,type)
{
	this.WND = null;

	this.user = user;
	this.type = type;

	this.Load(start);
	BX.addCustomEvent('onEntryNeedReload', BX.proxy(this.Reset, this));
}

BX.TimeManSlider.prototype.Load = function(ID)
{
	BX.timeman_query('admin_entry', {
		ID: ID,
		slider_type: this.type
	}, BX.proxy(this.Show, this));
}

BX.TimeManSlider.prototype.Show = function(data)
{
	this.data = data;
	if (null == this.WND || !!this.WND.bCleared)
	{
		this.WND = null;

		this.WND = new BX.CTimeManReportForm(
			window.BXTIMEMAN || {}, // !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
			{
				node: document.body,
				mode: 'admin',
				data: data,
				offsetLeft: -100,
				parent_object: this,
				type: this.type
			}
		);

		this.ShowOverlay();

		BX.addCustomEvent(this.WND.popup, "onPopupClose", BX.proxy(function(){
			this.overlay.style.display = "none";
			BX.removeClass(document.body, "report-body-overflow");
		}, this));
		BX.addCustomEvent(this.WND.popup, "onPopupShow", BX.proxy(function(){
			this.overlay.style.display = "block";
			BX.addClass(document.body, "report-body-overflow");
		}, this));

		this.WND.Show();
	}
	else
	{
		this.WND.Show(data);
		setTimeout(BX.proxy(this.FixOverlay, this),100);
	}

	if (this.data.NEIGHBOURS.NEXT > 0)
		BX.show(this.nextLink);
	else
		BX.hide(this.nextLink);

	if (this.data.NEIGHBOURS.PREV > 0)
		BX.show(this.prevLink);
	else
		BX.hide(this.prevLink);

	setTimeout(BX.proxy(this.FixOverlay, this),100);
}

// used
BX.TimeManSlider.prototype.Reset = function(data)
{
	this.Load(data.ID);
	// redraw LF entry here
}

BX.TimeManSlider.prototype.ShowOverlay = function()
{
	this.prevLink = BX.create("A",{
		props:{className:"timeman-popup-prev-slide"},
		attrs:{href:"javascript: void(0)"},
		children:[
			BX.create("SPAN")
		],
		events:{"click": BX.proxy(this.Prev, this)}
	});

	this.nextLink = BX.create("A", {
		props:{className:"timeman-popup-next-slide"},
		attrs:{href:"javascript: void(0)"},
		children:[
			BX.create("SPAN")
		],
		events:{"click": BX.proxy(this.Next,this)}
	});

	this.overlay = BX.create("DIV",{
		props:{className:"report-fixed-overlay"},
		style:{zIndex: 999},
		children:[
			(this.coreoverlay = BX.create("DIV",{
				props:{className:"bx-tm-dialog-overlay"}
				//attrs:{width:"100%",heigth:"100%"}
				}
			)),

			this.nextLink, this.prevLink,

			(this.closeLink = BX.create("A",{
				attrs:{href:"javascript: void(0)"},
				events:{"click":BX.proxy(this.WND.popup.close,this.WND.popup)},
				props:{className:"timeman-popup-close"},
				children:[
					BX.create("SPAN")
				]
			}))
		]
	});
	document.body.appendChild(this.overlay);
	BX.bind(top, "resize", BX.proxy(this.FixOverlay, this))
}

BX.TimeManSlider.prototype.Prev = function()
{
	if (this.data.NEIGHBOURS && this.data.NEIGHBOURS.PREV)
	{
		this.Load(this.data.NEIGHBOURS.PREV);
	}
	BX.proxy_context.blur();
}

BX.TimeManSlider.prototype.Next = function()
{
	if (this.data.NEIGHBOURS && this.data.NEIGHBOURS.NEXT)
	{
		this.Load(this.data.NEIGHBOURS.NEXT);
	}
	BX.proxy_context.blur();
}

BX.TimeManSlider.prototype.FixOverlay = function()
{
	var wnd_size = BX.GetWindowInnerSize();
	var popup_size = BX.pos(this.WND.popup.popupContainer);

	this.overlay.style.height = wnd_size.innerHeight + "px";
	this.overlay.style.width = wnd_size.innerWidth + "px";

	if(!!this.data.NEIGHBOURS)
	{
		if (this.data.NEIGHBOURS.NEXT > 0)
		{
			this.nextLink.firstChild.style.right = parseInt((wnd_size.innerWidth - popup_size.right) / 2) + 'px';
		}
		if (this.data.NEIGHBOURS.PREV > 0)
		{
			this.prevLink.firstChild.style.left = parseInt((popup_size.left) / 2) + 'px';
		}
	}
}
/**************************************************************************************************/

function _getStateHash(DATA, keys)
{
	var hash = '';
	if (DATA)
	{
		for (var i=0,l=keys.length;i<l;i++)
			hash += (i>0 ? '|' : '') + keys[i] + ':' + (DATA[keys[i]] ? DATA[keys[i]].valueOf() : 'null');
	}

	return hash;
}

function _createHR(bHtml, className)
{
	return bHtml ? '<div class="' + (className || 'popup-window-hr') + '"><i></i></div>' : BX.create('DIV', {
		props: {className: className || 'popup-window-hr'}, html: '<i></i>'
	});
}

function _worktime_timeman(h, m, s)
{
	var r = (
		(h > 0 ? h + BX.message('JS_CORE_H') + ' ' : '')
		+ (m > 0 ? m + BX.message('JS_CORE_M') : '')
	) || s + BX.message('JS_CORE_S');

	return '(' + BX.util.trim(r) + ')';
}

function _worktime_notice_timeman(h, m, s)
{
	return '<span class="tm-popup-notice-time-hours"><span class="tm-popup-notice-time-number">' + h + '</span><span class="tm-popup-notice-time-unit">' + BX.message('JS_CORE_H') + '</span></span><span class="tm-popup-notice-time-minutes"><span class="tm-popup-notice-time-number">' + m + '</span><span class="tm-popup-notice-time-unit">' + BX.message('JS_CORE_M') + '</span></span><span class="tm-popup-notice-time-seconds"><span class="tm-popup-notice-time-number">' + s + '</span><span class="tm-popup-notice-time-unit">' + BX.message('JS_CORE_S') + '</span></span>';
}

/*customize timer */
BX.timer.registerFormat('worktime_timeman', _worktime_timeman);
BX.timer.registerFormat('worktime_notice_timeman', _worktime_notice_timeman);
})();

/* End */
;
; /* Start:"a:4:{s:4:"full";s:44:"/bitrix/js/im/common.min.js?1452277455134164";s:6:"source";s:23:"/bitrix/js/im/common.js";s:3:"min";s:27:"/bitrix/js/im/common.min.js";s:3:"map";s:27:"/bitrix/js/im/common.map.js";}"*/
(function(e){if(e.BX.MessengerCommon)return;var s=e.BX;s.MessengerCommon=function(){this.BXIM={}};s.MessengerCommon.prototype.setBxIm=function(e){this.BXIM=e};s.MessengerCommon.prototype.isMobile=function(){return this.BXIM.mobileVersion};s.MessengerCommon.prototype.MobileActionEqual=function(e){if(!this.isMobile())return true;for(var s=0;s<arguments.length;s++){if(arguments[s]==this.BXIM.mobileAction)return true}return false};s.MessengerCommon.prototype.MobileActionNotEqual=function(e){if(!this.isMobile())return false;for(var s=0;s<arguments.length;s++){if(arguments[s]==this.BXIM.mobileAction)return false}return true};s.MessengerCommon.prototype.isScrollMax=function(e,s){if(!e)return true;s=typeof s=="number"?s:0;return e.scrollHeight-e.offsetHeight-s<=e.scrollTop};s.MessengerCommon.prototype.isScrollMin=function(e){if(!e)return false;return 0==e.scrollTop};s.MessengerCommon.prototype.enableScroll=function(e,s,t){if(!e)return false;t=t!==false;s=parseInt(s);return t&&this.isScrollMax(e,s)};s.MessengerCommon.prototype.preventDefault=function(t){t=t||e.event;if(t.stopPropagation)t.stopPropagation();else t.cancelBubble=true;if(typeof BXIM!="undefined"&&BXIM.messenger&&BXIM.messenger.closeMenuPopup)BXIM.messenger.closeMenuPopup();if(typeof s!="undefined"&&s.calendar&&s.calendar.get().popup)s.calendar.get().popup.close()};s.MessengerCommon.prototype.countObject=function(e){var s=0;for(var t in e){if(e.hasOwnProperty(t)){s++}}return s};s.MessengerCommon.prototype.isElementCoordsBelow=function(e,s,t,r){if(this.isMobile()){return true}if(!s||typeof s.getElementsByClassName=="undefined"){return false}t=t?t:0;var i=this.getElementCoords(e,s);i.bottom=i.top+e.offsetHeight;var n=i.top>=t;var a=i.bottom>t;if(r){return{top:n,bottom:a,coords:i}}else{return n||a}};s.MessengerCommon.prototype.isElementVisibleOnScreen=function(e,s,t){if(this.isMobile()){return BitrixMobile.isElementVisibleOnScreen(e)}if(!s||typeof s.getElementsByClassName=="undefined"){return false}var r=this.getElementCoords(e,s);r.bottom=r.top+e.offsetHeight;var i=s.scrollTop;var n=i+s.clientHeight;var a=r.top>=0&&r.top<n;var o=r.bottom>0&&r.bottom<s.clientHeight;if(t){return{top:a,bottom:o}}else{return a||o}};s.MessengerCommon.prototype.getElementCoords=function(e,s){if(this.isMobile()){return BitrixMobile.getElementCoords(e)}if(!s||typeof s.getElementsByClassName=="undefined"){return false}var t=e.getBoundingClientRect();var r=s.getBoundingClientRect();return{originTop:t.top,originLeft:t.left,top:t.top-r.top,left:t.left-r.left}};s.MessengerCommon.prototype.getDateFormatType=function(e){e=e?e.toString().toUpperCase():"DEFAULT";var t=[];if(e=="MESSAGE_TITLE"){t=[["tommorow","tommorow"],["today","today"],["yesterday","yesterday"],["",s.date.convertBitrixFormat(s.message("IM_M_MESSAGE_TITLE_FORMAT_DATE"))]]}else if(e=="MESSAGE"){t=[["",s.message("IM_M_MESSAGE_FORMAT_TIME")]]}else if(e=="RECENT_TITLE"){t=[["tommorow","today"],["today","today"],["yesterday","yesterday"],["",s.date.convertBitrixFormat(s.message("IM_CL_RESENT_FORMAT_DATE"))]]}else{t=[["tommorow","tommorow, "+s.message("IM_M_MESSAGE_FORMAT_TIME")],["today","today, "+s.message("IM_M_MESSAGE_FORMAT_TIME")],["yesterday","yesterday, "+s.message("IM_M_MESSAGE_FORMAT_TIME")],["",s.date.convertBitrixFormat(s.message("FORMAT_DATETIME"))]]}return t};s.MessengerCommon.prototype.formatDate=function(e,t){if(typeof t=="undefined"){t=this.getDateFormatType("DEFAULT")}return s.date.format(t,parseInt(e)+parseInt(s.message("SERVER_TZ_OFFSET")),this.getNowDate()+parseInt(s.message("SERVER_TZ_OFFSET")),true)};s.MessengerCommon.prototype.getNowDate=function(e){var t=new Date;if(e==true)t=new Date(t.getFullYear(),t.getMonth(),t.getDate(),0,0,0);return Math.round(+t/1e3)+parseInt(s.message("USER_TZ_OFFSET"))};s.MessengerCommon.prototype.getDateDiff=function(e){var t=s.message("USER_TZ_OFFSET");if(t==="")return 0;var r=this.getNowDate()+parseInt(s.message("SERVER_TZ_OFFSET"));var i=parseInt(e)+parseInt(s.message("SERVER_TZ_OFFSET"));return r-i};s.MessengerCommon.prototype.isBlankAvatar=function(e){return e==""||e.indexOf(this.BXIM.pathToBlankImage)>=0};s.MessengerCommon.prototype.hideErrorImage=function(e){var s=e.src;e.parentNode.parentNode.className="";e.parentNode.parentNode.innerHTML='<a href="'+s+'" target="_blank">'+s+"</a>"};s.MessengerCommon.prototype.prepareText=function(e,t,r,i,n){var a=e;t=t==true;r=r==true;i=i==true;n=n?n:false;a=s.util.trim(a);var o="&gt;&gt;";if(r&&a.indexOf(o)>=0){var m=false;var g=a.split("<br />");for(var l=0;l<g.length;l++){if(g[l].substring(0,o.length)==o){g[l]=g[l].replace(o,'<div class="bx-messenger-content-quote"><span class="bx-messenger-content-quote-icon"></span><div class="bx-messenger-content-quote-wrap">');while(++l<g.length&&g[l].substring(0,o.length)==o){g[l]=g[l].replace(o,"")}g[l-1]+="</div></div>";m=true}}a=g.join("<br />")}if(t){a=s.util.htmlspecialchars(a)}if(r){a=a.replace(/------------------------------------------------------<br \/>(.*?)\[(.*?)\]<br \/>(.*?)------------------------------------------------------(<br \/>)?/g,function(e,s,t,r,i,n){return(n>0?"<br>":"")+'<div class="bx-messenger-content-quote"><span class="bx-messenger-content-quote-icon"></span><div class="bx-messenger-content-quote-wrap"><div class="bx-messenger-content-quote-name">'+s+' <span class="bx-messenger-content-quote-time">'+t+"</span></div>"+r+"</div></div><br />"});a=a.replace(/------------------------------------------------------<br \/>(.*?)------------------------------------------------------(<br \/>)?/g,function(e,s,t,r,i){return(i>0?"<br>":"")+'<div class="bx-messenger-content-quote"><span class="bx-messenger-content-quote-icon"></span><div class="bx-messenger-content-quote-wrap">'+s+"</div></div><br />"})}if(t){a=a.replace(/\n/gi,"<br />")}a=a.replace(/\t/gi,"&nbsp;&nbsp;&nbsp;&nbsp;");if(i){a=a.replace(/<a(.*?)>(http[s]{0,1}:\/\/.*?)<\/a>/gi,function(e,t,r,i){if(!r.match(/\.(jpg|jpeg|png|gif)$/i)||r.indexOf("/docs/pub/")>0||r.indexOf("logout=yes")>0){return e}else if(s.MessengerCommon.isMobile()){return(i>0?"<br />":"")+'<span class="bx-messenger-file-image"><span class="bx-messenger-file-image-src"><img src="'+r+'" class="bx-messenger-file-image-text" onclick="BXIM.messenger.openPhotoGallery(\''+r+'\');" onerror="BX.MessengerCommon.hideErrorImage(this)"></span></span><br>'}else{return(i>0?"<br />":"")+'<span class="bx-messenger-file-image"><a'+t+' target="_blank" class="bx-messenger-file-image-src"><img src="'+r+'" class="bx-messenger-file-image-text" onerror="BX.MessengerCommon.hideErrorImage(this)"></a></span><br>'}})}if(n){a=a.replace(new RegExp("("+n.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g,"\\$&")+")","ig"),'<span class="bx-messenger-highlight">$1</span>')}if(false){a=a.replace(/^(\s*<img\s+src=[^>]+?data-code=[^>]+?width=")(\d+)("[^>]+?height=")(\d+)("[^>]+?class="bx-smile"\s*\/?>\s*)$/,function h(e,s,t,r,i,n){return s+parseInt(t,10)*2+r+parseInt(i,10)*2+n})}if(true){a=a.replace(/\[USER=([0-9]{1,})\](.*?)\[\/USER\]/gi,function(e,s,t){var i="";s=parseInt(s);if(r&&t&&s>0&&typeof BXIM!="undefined")i='<span class="bx-messenger-ajax '+(s==BXIM.userId?"bx-messenger-ajax-self":"")+'" data-entity="user" data-userId="'+s+'">'+t+"</span>";else i=t;return i});a=a.replace(/\[PCH=([0-9]{1,})\](.*?)\[\/PCH\]/gi,function(e,s,t){var i="";s=parseInt(s);if(r&&t&&s>0)i='<span class="bx-messenger-ajax" data-entity="phoneCallHistory" data-historyId="'+s+'">'+t+"</span>";else i=t;return i})}if(a.substr(-6)=="<br />"){a=a.substr(0,a.length-6)}a=a.replace(/<br><br \/>/gi,"<br />");a=a.replace(/<br \/><br>/gi,"<br />");return a};s.MessengerCommon.prototype.prepareTextBack=function(e,t){var r=e;t=t===true;r=s.util.htmlspecialcharsback(r);r=r.replace(/<(\/*)([buis]+)>/gi,"[$1$2]");r=r.replace(/<img.*?data-code="([^"]*)".*?>/gi,"$1");r=r.replace(/<a.*?href="([^"]*)".*?>.*?<\/a>/gi,"$1");if(!t){r=r.replace(/------------------------------------------------------(.*?)------------------------------------------------------/gim,"["+s.message("IM_M_QUOTE_BLOCK")+"]")}r=r.split("&nbsp;&nbsp;&nbsp;&nbsp;").join("	");r=r.split("<br />").join("\n");return r};s.MessengerCommon.prototype.getUserParam=function(e,t){e=typeof e=="undefined"?this.BXIM.userId:e;t=typeof t=="boolean"?t:false;if(e.toString().substr(0,4)=="chat"){var r=e.toString().substr(4);if(t||!(this.BXIM.messenger.chat[r]&&this.BXIM.messenger.chat[r].id)){this.BXIM.messenger.chat[r]={id:r,name:s.message("IM_M_LOAD_USER"),owner:0,workPosition:"",avatar:this.BXIM.pathToBlankImage,style:"chat",fake:true};if(t){this.BXIM.messenger.chat[r].fake=false}}return this.BXIM.messenger.chat[r]}else{if(t||!(this.BXIM.messenger.users[e]&&this.BXIM.messenger.users[e].id)){this.BXIM.messenger.users[e]={id:e,avatar:this.BXIM.pathToBlankImage,name:s.message("IM_M_LOAD_USER"),profile:this.BXIM.path.profileTemplate.replace("#user_id#",e),status:"guest",workPosition:"",extranet:false,fake:true};this.BXIM.messenger.hrphoto[e]="/bitrix/js/im/images/hidef-avatar-v2.png";if(t){this.BXIM.messenger.users[e].fake=false}}return this.BXIM.messenger.users[e]}};s.MessengerCommon.prototype.getUserStatus=function(e,t){e=parseInt(e);e=isNaN(e)?this.BXIM.userId:e;t=t===true;var r="";var i="";if(typeof this.BXIM.messenger.users[e]=="undefined"){r="na";i=s.message("IM_STATUS_NA")}else if(this.BXIM.messenger.users[e].status=="offline"){r="offline";i=s.message("IM_STATUS_OFFLINE")}else if(this.BXIM.messenger.BXIM.userId==e){r=this.BXIM.messenger.users[e].status;i=s.message("IM_STATUS_"+r.toUpperCase())}else if(this.BXIM.messenger.users[e].idle>0){r="idle";i=s.message("IM_STATUS_AWAY_TITLE").replace("#TIME#",this.getUserIdle(e))}else if(this.BXIM.messenger.users[e].birthday&&(this.BXIM.messenger.users[e].status=="online"||this.BXIM.messenger.users[e].status=="offline")){r="birthday";if(this.BXIM.messenger.users[e].status=="offline"){i=s.message("IM_STATUS_OFFLINE")}else{i=s.message("IM_M_BIRTHDAY_MESSAGE_SHORT")}}else{r=this.BXIM.messenger.users[e].status;i=s.message("IM_STATUS_"+r.toUpperCase())}return t?i:r};s.MessengerCommon.prototype.getUserIdle=function(e){e=parseInt(e);e=isNaN(e)?this.BXIM.userId:e;var s="";if(this.BXIM.messenger.users[e].idle>0){var t=parseInt(this.BXIM.messenger.users[e].idle);s=this.formatDate(this.BXIM.messenger.users[e].idle,this.getNowDate()-t>=3600?"Hdiff":"idiff")}return s};s.MessengerCommon.prototype.getUserPosition=function(e){var t="";if(!this.BXIM.messenger.users[e])return"";if(this.BXIM.messenger.users[e].workPosition){t=this.BXIM.messenger.users[e].workPosition}else if(this.BXIM.messenger.users[e].extranet){t=s.message("IM_CL_USER_EXTRANET")}else if(this.BXIM.messenger.BXIM.bitrixIntranet){t=s.message("IM_CL_USER_B24")}else{t=s.message("IM_CL_USER")}return t};s.MessengerCommon.prototype.userListRedraw=function(e){if(this.isMobile()){if(!this.MobileActionEqual("RECENT")){return false}}else{if(this.BXIM.messenger.popupMessenger==null)return false}if(this.BXIM.messenger.recentList&&this.BXIM.messenger.contactListSearchText!=null&&this.BXIM.messenger.contactListSearchText.length==0)this.recentListRedraw(e);else this.contactListRedraw(e)};s.MessengerCommon.prototype.contactListRedraw=function(e){e=e||{};if(this.BXIM.messenger.contactListSearchText!=null&&this.BXIM.messenger.contactListSearchText.length==0)this.BXIM.messenger.recentListReturn=false;if(!this.isMobile()){this.BXIM.messenger.contactList=true;s.addClass(this.BXIM.messenger.contactListTab,"bx-messenger-cl-switcher-tab-active");this.BXIM.messenger.recentList=false;s.removeClass(this.BXIM.messenger.recentListTab,"bx-messenger-cl-switcher-tab-active");if(this.BXIM.messenger.popupPopupMenu!=null)this.BXIM.messenger.popupPopupMenu.close()}if(this.BXIM.messenger.contactListSearchText.length>0){this.contactListPrepareSearch("contactList",this.BXIM.messenger.popupContactListElementsWrap,this.BXIM.messenger.contactListSearchText,e.FORCE?{}:{params:false,timeout:this.isMobile()?500:100})}else{if(this.BXIM.messenger.redrawContactListTimeout["contactList"])clearTimeout(this.BXIM.messenger.redrawContactListTimeout["contactList"]);this.BXIM.messenger.popupContactListElementsWrap.innerHTML="";s.adjust(this.BXIM.messenger.popupContactListElementsWrap,{children:this.contactListPrepare()});if(this.isMobile()){BitrixMobile.LazyLoad.showImages()}}e.SEND=e.SEND==true;if(!this.isMobile()&&e.SEND){s.localStorage.set("mrd",{viewGroup:this.BXIM.settings.viewGroup,viewOffline:this.BXIM.settings.viewOffline},5)}};s.MessengerCommon.prototype.contactListPrepareSearch=function(e,t,r,i){if(i.params!=false){var n={groupOpen:true,viewOffline:false,viewGroup:true,viewChat:false,viewOfflineWithPhones:false,extra:false,searchText:r};for(var a in i){if(a=="timeout"||a=="params")continue;n[a]=i[a]}}var o=i.timeout?i.timeout:0;if(o>0){clearTimeout(this.BXIM.messenger.redrawContactListTimeout[e]);this.BXIM.messenger.redrawContactListTimeout[e]=setTimeout(s.delegate(function(){t.innerHTML="";s.adjust(t,{children:this.contactListPrepare(n)});if(this.isMobile()){BitrixMobile.LazyLoad.showImages()}},this),o)}else{t.innerHTML="";s.adjust(t,{children:this.contactListPrepare(n)});if(this.isMobile()){BitrixMobile.LazyLoad.showImages()}}};s.MessengerCommon.prototype.contactListPrepare=function(e){e=typeof e=="object"?e:{};var t=[];var r={};var i={};var n=[];var a={};var o=typeof e.searchText!="undefined"?e.searchText:this.BXIM.messenger.contactListSearchText;var m=!(o!=null&&o.length==0);var g=typeof e.extra!="undefined"?e.extra:true;var l=typeof e.groupOpen!="undefined"?e.groupOpen:"auto";var h=typeof e.viewGroup!="undefined"?e.viewGroup:m||!this.BXIM.settings?false:this.BXIM.settings.viewGroup;var I=typeof e.viewOffline!="undefined"?e.viewOffline:m||!this.BXIM.settings?true:this.BXIM.settings.viewOffline;var M=typeof e.viewChat!="undefined"?e.viewChat:true;var p=typeof e.viewOfflineWithPhones!="undefined"?e.viewOfflineWithPhones:false;if(this.isMobile()){BitrixMobile.LazyLoad.clearImages()}var d={};if(typeof e.exceptUsers!="undefined"){for(var u=0;u<e.exceptUsers.length;u++)d[e.exceptUsers[u]]=true}if(h){r=this.BXIM.messenger.groups;a=this.BXIM.messenger.userInGroup}else{r=this.BXIM.messenger.woGroups;a=this.BXIM.messenger.woUserInGroup}var f=0;for(var u in r)f++;if(f<=0&&!this.BXIM.messenger.contactListLoad){t.push(s.create("div",{props:{className:"bx-messenger-cl-item-load"},html:s.message("IM_CL_LOAD")}));this.contactListGetFromServer();return t}var c=[];var B=[];if(m){o=o+"";if(!this.isMobile()&&this.BXIM.language=="ru"&&s.correctText){var X=s.correctText(o);if(X!=o){B=X.split(" ")}}c=o.split(" ")}i[0]={id:0,name:s.message("IM_M_CL_UNREAD"),status:"open"};for(var u in this.BXIM.messenger.unreadMessage)n.push(u);a[0]={id:0,users:n};for(var u in r){if(u!="last"&&u!=0)i[u]=r[u]}if(M){var E=[];for(var u in this.BXIM.messenger.chat){if(!m&&this.BXIM.messenger.chat[u].style=="call")continue;E.push(u)}E.sort(function(e,s){u=this.BXIM.messenger.chat[e].name;ii=this.BXIM.messenger.chat[s].name;if(u<ii){return-1}else if(u>ii){return 1}else{return 0}});a["chat"]={id:"chat",users:E,isChat:true}}for(var u in i){var S=i[u];if(typeof S=="undefined"||!S.name||!s.type.isNotEmptyString(S.name))continue;var _=[];var b={};if(a[u]&&!a[u].isChat){for(var T=0;T<a[u].users.length;T++){var C=this.BXIM.messenger.users[a[u].users[T]];if(typeof C=="undefined"||this.BXIM.userId==C.id||typeof C.name=="undefined"||d[C.id]||b[u+"_"+C.id])continue;b[u+"_"+C.id]=true;if(m){var R=C.name.toLowerCase()+(C.workPosition?(" "+C.workPosition).toLowerCase():"");var v=false;for(var x=0;x<c.length;x++)if(R.indexOf(c[x].toLowerCase())<0)v=true;if(v){for(var x=0;x<B.length;x++){if(R.indexOf(B[x].toLowerCase())<0)v=true;else v=false}}if(v)continue}var A="";var y="";if(g&&this.BXIM.messenger.unreadMessage[C.id]&&this.BXIM.messenger.unreadMessage[C.id].length>0){A="bx-messenger-cl-status-new-message";y='<span class="bx-messenger-cl-count-digit">'+(this.BXIM.messenger.unreadMessage[C.id].length<100?this.BXIM.messenger.unreadMessage[C.id].length:"99+")+"</span>"}var L="";if(g&&this.countWriting(C.id))L="bx-messenger-cl-status-writing";var N=this.getUserStatus(C.id);if(p&&C.phoneDevice&&N=="offline"){N="online"}if(!m&&u!="last"&&I==false&&N=="offline"&&A=="")continue;if(this.isMobile()){var D="mobile-cl-avatar-id-"+C.id+"-g-"+u;var w='id="'+D+'" src="'+this.BXIM.pathToBlankImage+'" data-src="'+C.avatar+'"';BitrixMobile.LazyLoad.registerImage(D)}else{var w='_src="'+C.avatar+'" src="'+this.BXIM.pathToBlankImage+'"';if(m||S.status=="open"&&l=="auto"||l==true)w='src="'+C.avatar+'" _src="'+this.BXIM.pathToBlankImage+'"'}_.push(s.create("a",{props:{className:"bx-messenger-cl-item bx-messenger-cl-id-"+C.id+" bx-messenger-cl-status-"+N+" "+A+" "+L},attrs:{href:"#user"+C.id,"data-userId":C.id,"data-name":C.name,"data-status":N,"data-avatar":C.avatar},html:'<span class="bx-messenger-cl-count">'+y+"</span>"+'<span class="bx-messenger-cl-avatar"><img class="bx-messenger-cl-avatar-img'+(this.isBlankAvatar(C.avatar)?" bx-messenger-cl-avatar-img-default":"")+'" '+w+'><span class="bx-messenger-cl-status"></span></span>'+'<span class="bx-messenger-cl-user">'+'<div class="bx-messenger-cl-user-title'+(C.extranet?" bx-messenger-user-extranet":"")+'">'+(C.nameList?C.nameList:C.name)+"</div>"+'<div class="bx-messenger-cl-user-desc">'+this.getUserPosition(C.id)+"</div>"+"</span>"}))}if(_.length>0){t.push(s.create("div",{attrs:{"data-groupId-wrap":S.id},props:{className:"bx-messenger-cl-group"+(m||S.status=="open"&&l=="auto"||l==true?" bx-messenger-cl-group-open":"")},children:[s.create("div",{props:{className:"bx-messenger-cl-group-title"},attrs:{"data-groupId":S.id,title:S.name},html:S.name}),s.create("span",{props:{className:"bx-messenger-cl-group-wrapper"},children:_})]}))}}else if(a[u]&&a[u].isChat){for(var T=0;T<a[u].users.length;T++){var O=this.BXIM.messenger.chat[a[u].users[T]];if(typeof O=="undefined"||typeof O.name=="undefined"||b[u+"_chat"+O.id])continue;b[u+"_chat"+O.id]=true;if(m){var v=false;for(var x=0;x<c.length;x++)if(O.name.toLowerCase().indexOf(c[x].toLowerCase())<0)v=true;if(v){for(var x=0;x<B.length;x++){if(O.name.toLowerCase().indexOf(B[x].toLowerCase())<0)v=true;else v=false}}if(v)continue}var L="";if(g&&this.countWriting("chat"+O.id))L="bx-messenger-cl-status-writing";var A="";var y="";if(g&&this.BXIM.messenger.unreadMessage["chat"+O.id]&&this.BXIM.messenger.unreadMessage["chat"+O.id].length>0){A="bx-messenger-cl-status-new-message";y='<span class="bx-messenger-cl-count-digit">'+(this.BXIM.messenger.unreadMessage["chat"+O.id].length<100?this.BXIM.messenger.unreadMessage["chat"+O.id].length:"99+")+"</span>"}if(this.isMobile()){var D="mobile-cl-avatar-id-chat-"+O.id+"-g-"+u;var w='id="'+D+'" src="'+this.BXIM.pathToBlankImage+'" data-src="'+O.avatar+'"';BitrixMobile.LazyLoad.registerImage(D)}else{var w='_src="'+O.avatar+'" src="'+this.BXIM.pathToBlankImage+'"';if(m||S.status=="open"&&l=="auto"||l==true)w='src="'+O.avatar+'" _src="'+this.BXIM.pathToBlankImage+'"'}_.push(s.create("span",{props:{className:"bx-messenger-cl-item bx-messenger-cl-id-chat"+O.id+" bx-messenger-cl-status-online "+A+" "+L},attrs:{"data-userId":"chat"+O.id,"data-userIsChat":"Y","data-name":O.name,"data-status":"online","data-avatar":O.avatar},html:'<span class="bx-messenger-cl-count">'+y+"</span>"+'<span class="bx-messenger-cl-avatar bx-messenger-cl-avatar-'+O.style+'"><img class="bx-messenger-cl-avatar-img'+(this.isBlankAvatar(O.avatar)?" bx-messenger-cl-avatar-img-default":"")+'" '+w+'><span class="bx-messenger-cl-status"></span></span>'+'<span class="bx-messenger-cl-user">'+'<div class="bx-messenger-cl-user-title">'+O.name+"</div>"+'<div class="bx-messenger-cl-user-desc">'+(O.style=="call"?s.message("IM_CL_PHONE"):s.message("IM_CL_CHAT"))+"</div>"+"</span>"}))}if(_.length>0){t.push(s.create("div",{attrs:{"data-groupId-wrap":S.id},props:{className:"bx-messenger-cl-group"+(m||S.status=="open"&&l=="auto"||l==true?" bx-messenger-cl-group-open":"")},children:[s.create("div",{props:{className:"bx-messenger-cl-group-title"},attrs:{"data-groupId":S.id,title:S.name},html:S.name}),s.create("span",{props:{className:"bx-messenger-cl-group-wrapper"},children:_})]}))}}}if(this.BXIM.bitrixIntranet&&m){var U={};for(var u in this.BXIM.messenger.groups){var G=true;for(var x=0;x<c.length;x++)if(this.BXIM.messenger.groups[u].name&&this.BXIM.messenger.groups[u].name.toLowerCase().indexOf(c[x].toLowerCase())>=0)G=false;if(G){for(var x=0;x<B.length;x++){if(this.BXIM.messenger.groups[u].name&&this.BXIM.messenger.groups[u].name.toLowerCase().indexOf(B[x].toLowerCase())>=0)G=false}}if(!G){U[u]={id:u,name:this.BXIM.messenger.groups[u].name,status:"close"}}}for(var u in U){var S=U[u];if(typeof S=="undefined"||!S.name||!s.type.isNotEmptyString(S.name))continue;var b={};var _=[];if(this.BXIM.messenger.userInGroup[u]&&!this.BXIM.messenger.userInGroup[u].isChat){for(var T=0;T<this.BXIM.messenger.userInGroup[u].users.length;T++){var C=this.BXIM.messenger.users[this.BXIM.messenger.userInGroup[u].users[T]];if(typeof C=="undefined"||this.BXIM.userId==C.id||typeof C.name=="undefined"||d[C.id]||b[u+"_"+C.id])continue;b[u+"_"+C.id]=true;var A="";var y="";if(g&&this.BXIM.messenger.unreadMessage[C.id]&&this.BXIM.messenger.unreadMessage[C.id].length>0){A="bx-messenger-cl-status-new-message";y='<span class="bx-messenger-cl-count-digit">'+(this.BXIM.messenger.unreadMessage[C.id].length<100?this.BXIM.messenger.unreadMessage[C.id].length:"99+")+"</span>"}var L="";if(g&&this.countWriting(C.id))L="bx-messenger-cl-status-writing";var N=this.getUserStatus(C.id);if(p&&C.phoneDevice&&N=="offline"){N="online"}if(u!="last"&&I==false&&N=="offline"&&A=="")continue;if(this.isMobile()){var D="mobile-cl-avatar-id-"+C.id+"-g-"+u;var w='id="'+D+'" src="'+this.BXIM.pathToBlankImage+'" data-src="'+C.avatar+'"';BitrixMobile.LazyLoad.registerImage(D)}else{var w='_src="'+C.avatar+'" src="'+this.BXIM.pathToBlankImage+'"';if(m||S.status=="open"&&l=="auto"||l==true)w='src="'+C.avatar+'" _src="'+this.BXIM.pathToBlankImage+'"'}_.push(s.create("span",{props:{className:"bx-messenger-cl-item bx-messenger-cl-id-"+C.id+" bx-messenger-cl-status-"+N+" "+A+" "+L},attrs:{"data-userId":C.id,"data-name":C.name,"data-status":N,"data-avatar":C.avatar},html:'<span class="bx-messenger-cl-count">'+y+"</span>"+'<span class="bx-messenger-cl-avatar"><img class="bx-messenger-cl-avatar-img'+(this.isBlankAvatar(C.avatar)?" bx-messenger-cl-avatar-img-default":"")+'" '+w+'><span class="bx-messenger-cl-status"></span></span>'+'<span class="bx-messenger-cl-user">'+'<div class="bx-messenger-cl-user-title'+(C.extranet?" bx-messenger-user-extranet":"")+'">'+(C.nameList?C.nameList:C.name)+"</div>"+'<div class="bx-messenger-cl-user-desc">'+this.getUserPosition(C.id)+"</div>"+"</span>"}))}if(_.length>0){t.push(s.create("div",{attrs:{"data-groupId-wrap":S.id},props:{className:"bx-messenger-cl-group"+(l==true?" bx-messenger-cl-group-open":"")},children:[s.create("div",{props:{className:"bx-messenger-cl-group-title"},attrs:{"data-groupId":S.id,title:S.name},html:S.name}),s.create("span",{props:{className:"bx-messenger-cl-group-wrapper"},children:_})]}))}}}}if(t.length<=0){t.push(s.create("div",{props:{className:"bx-messenger-cl-item-empty"},html:s.message("IM_M_CL_EMPTY")}))}return t};s.MessengerCommon.prototype.contactListClickItem=function(e){this.BXIM.messenger.closeMenuPopup();if(this.BXIM.messenger.popupContactListSearchInput.value!=""){this.BXIM.messenger.popupContactListSearchInput.value="";this.BXIM.messenger.contactListSearchText="";s.localStorage.set("mns",this.BXIM.messenger.contactListSearchText,5);if(this.BXIM.messenger.recentListReturn){this.BXIM.messenger.recentList=true;this.BXIM.messenger.contactList=false}this.userListRedraw()}if(this.isMobile()){this.BXIM.messenger.openMessenger(s.proxy_context.getAttribute("data-userId"),s.proxy_context)}else{this.BXIM.messenger.openMessenger(s.proxy_context.getAttribute("data-userId"))}return s.PreventDefault(e)};s.MessengerCommon.prototype.contactListToggleGroup=function(){var e="";var t=s.findNextSibling(s.proxy_context,{className:"bx-messenger-cl-group-wrapper"});if(t.childNodes.length>0){var r=s.findChildrenByClassName(t,"bx-messenger-cl-avatar-img");if(s.hasClass(s.proxy_context.parentNode,"bx-messenger-cl-group-open")){e="close";s.removeClass(s.proxy_context.parentNode,"bx-messenger-cl-group-open");if(!this.isMobile()&&r){for(var i=0;i<r.length;i++){r[i].setAttribute("_src",r[i].src);r[i].src=this.BXIM.pathToBlankImage}}}else{e="open";s.addClass(s.proxy_context.parentNode,"bx-messenger-cl-group-open");if(!this.isMobile()&&r){for(var i=0;i<r.length;i++){r[i].src=r[i].getAttribute("_src");r[i].setAttribute("_src",this.BXIM.pathToBlankImage)}}}}else{if(s.hasClass(s.proxy_context.parentNode,"bx-messenger-cl-group-open")){e="close";s.removeClass(s.proxy_context.parentNode,"bx-messenger-cl-group-open")}else{e="open";s.addClass(s.proxy_context.parentNode,"bx-messenger-cl-group-open")}}var n=s.proxy_context.getAttribute("data-groupId");var a=this.BXIM.messenger.contactListSearchText!=null&&this.BXIM.messenger.contactListSearchText.length>0?false:this.BXIM.messenger.BXIM.settings.viewGroup;if(a)this.BXIM.messenger.groups[n].status=e;else if(this.BXIM.messenger.woGroups[n])this.BXIM.messenger.woGroups[n].status=e;s.userOptions.save("IM","groupStatus",n,e);s.localStorage.set("mgp",{id:n,status:e},5)};s.MessengerCommon.prototype.contactListGetFromServer=function(){if(this.BXIM.messenger.contactListLoad)return false;this.BXIM.messenger.contactListLoad=true;s.ajax({url:this.BXIM.pathToAjax+"?CONTACT_LIST&V="+this.BXIM.revision,method:"POST",dataType:"json",skipAuthCheck:true,timeout:30,data:{IM_CONTACT_LIST:"Y",IM_AJAX_CALL:"Y",DESKTOP:!this.isMobile()&&this.BXIM.desktop&&this.BXIM.desktop.ready()?"Y":"N",sessid:s.bitrix_sessid()},onsuccess:s.delegate(function(t){if(t&&t.BITRIX_SESSID){s.message({bitrix_sessid:t.BITRIX_SESSID})}if(t.ERROR==""){for(var r in t.USERS)this.BXIM.messenger.users[r]=t.USERS[r];for(var r in t.GROUPS)this.BXIM.messenger.groups[r]=t.GROUPS[r];for(var r in t.CHATS){if(this.BXIM.messenger.chat[r]&&this.BXIM.messenger.chat[r].fake)t.CHATS[r].fake=true;else if(!this.BXIM.messenger.chat[r])t.CHATS[r].fake=true;this.BXIM.messenger.chat[r]=t.CHATS[r]}for(var r in t.USER_IN_GROUP){if(typeof this.BXIM.messenger.userInGroup[r]=="undefined"){this.BXIM.messenger.userInGroup[r]=t.USER_IN_GROUP[r]}else{for(var i=0;i<t.USER_IN_GROUP[r].users.length;i++)this.BXIM.messenger.userInGroup[r].users.push(t.USER_IN_GROUP[r].users[i]);this.BXIM.messenger.userInGroup[r].users=s.util.array_unique(this.BXIM.messenger.userInGroup[r].users)}}for(var r in t.WO_GROUPS)this.BXIM.messenger.woGroups[r]=t.WO_GROUPS[r];for(var r in t.WO_USER_IN_GROUP){if(typeof this.BXIM.messenger.woUserInGroup[r]=="undefined"){this.BXIM.messenger.woUserInGroup[r]=t.WO_USER_IN_GROUP[r]}else{for(var i=0;i<t.WO_USER_IN_GROUP[r].users.length;i++)this.BXIM.messenger.woUserInGroup[r].users.push(t.WO_USER_IN_GROUP[r].users[i]);this.BXIM.messenger.woUserInGroup[r].users=s.util.array_unique(this.BXIM.messenger.woUserInGroup[r].users)}}this.userListRedraw();if(!this.isMobile()){this.BXIM.messenger.dialogStatusRedraw();if(this.BXIM.messenger.popupChatDialogContactListElements!=null){this.contactListPrepareSearch("popupChatDialogContactListElements",this.BXIM.messenger.popupChatDialogContactListElements,this.BXIM.messenger.popupChatDialogContactListSearch.value,{viewOffline:true,viewChat:false})}if(this.BXIM.webrtc.popupTransferDialogContactListElements!=null){this.contactListPrepareSearch("popupTransferDialogContactListElements",this.BXIM.webrtc.popupTransferDialogContactListElements,this.BXIM.webrtc.popupTransferDialogContactListSearch.value,{viewChat:false})}}}else{this.BXIM.messenger.contactListLoad=false;if(t.ERROR=="SESSION_ERROR"&&this.BXIM.messenger.sendAjaxTry<2){this.BXIM.messenger.sendAjaxTry++;setTimeout(s.delegate(this.contactListGetFromServer,this),2e3);s.onCustomEvent(e,"onImError",[t.ERROR,t.BITRIX_SESSID])}else if(t.ERROR=="AUTHORIZE_ERROR"){this.BXIM.messenger.sendAjaxTry++;if(this.BXIM.desktop&&this.BXIM.desktop.ready()){setTimeout(s.delegate(this.contactListGetFromServer,this),1e4)}s.onCustomEvent(e,"onImError",[t.ERROR])}}},this),onfailure:s.delegate(function(){this.BXIM.messenger.sendAjaxTry=0;this.BXIM.messenger.contactListLoad=false},this)})};s.MessengerCommon.prototype.contactListSearchClear=function(e){this.BXIM.messenger.popupContactListSearchInput.value="";this.BXIM.messenger.contactListSearchText=s.util.trim(this.BXIM.messenger.popupContactListSearchInput.value);s.localStorage.set("mns",this.BXIM.messenger.contactListSearchText,5);if(this.isMobile()){s.removeClass(this.BXIM.messenger.popupContactListSearchInput.parentNode,"bx-messenger-input-wrap-active")}if(this.BXIM.messenger.recentListReturn){this.BXIM.messenger.recentList=true;this.BXIM.messenger.contactList=false}this.userListRedraw();return s.PreventDefault(e)};s.MessengerCommon.prototype.contactListSearch=function(e){if(e.keyCode==16||e.keyCode==18||e.keyCode==20||e.keyCode==244||e.keyCode==91)return false;this.BXIM.messenger.recentList=false;this.BXIM.messenger.contactList=true;if(this.isMobile()){if(!app.enableInVersion(10)){setTimeout(function(){document.body.scrollTop=0},100)}}else{if(e.keyCode==27){if(this.BXIM.messenger.contactListSearchText<=0){this.BXIM.messenger.popupContactListSearchInput.value="";if(!this.isMobile()&&this.BXIM.messenger.popupMessenger&&!this.BXIM.messenger.desktop.ready()&&!this.BXIM.messenger.webrtc.callInit){this.BXIM.messenger.popupMessenger.destroy()}}else{this.BXIM.messenger.popupContactListSearchInput.value="";this.BXIM.messenger.popupMessengerTextarea.focus()}}if(e.keyCode==13){this.BXIM.messenger.popupContactListSearchInput.value="";var t=s.findChildByClassName(this.BXIM.messenger.popupContactListElementsWrap,"bx-messenger-cl-item");if(t){this.BXIM.messenger.BXIM.openMessenger(t.getAttribute("data-userid"))}}}this.BXIM.messenger.contactListSearchText=s.util.trim(this.BXIM.messenger.popupContactListSearchInput.value);if(!this.isMobile()){s.localStorage.set("mns",this.BXIM.messenger.contactListSearchText,5)}if(this.BXIM.messenger.contactListSearchText==""){if(this.BXIM.messenger.recentListReturn){this.BXIM.messenger.recentList=true;this.BXIM.messenger.contactList=false}if(this.isMobile()){s.removeClass(this.BXIM.messenger.popupContactListSearchInput.parentNode,"bx-messenger-input-wrap-active")}}else{if(this.isMobile()){s.addClass(this.BXIM.messenger.popupContactListSearchInput.parentNode,"bx-messenger-input-wrap-active")}if(this.BXIM.messenger.realSearch){clearTimeout(this.BXIM.messenger.contactListSearchTimeout);this.BXIM.messenger.contactListSearchTimeout=setTimeout(s.delegate(function(){if(this.BXIM.messenger.contactListSearchText.length<=3)return false;s.ajax({url:this.BXIM.pathToAjax+"?CONTACT_LIST_SEARCH&V="+this.BXIM.revision,method:"POST",dataType:"json",timeout:30,data:{IM_CONTACT_LIST_SEARCH:"Y",SEARCH:this.BXIM.messenger.contactListSearchText,IM_AJAX_CALL:"Y",sessid:s.bitrix_sessid()},onsuccess:s.delegate(function(e){if(!this.BXIM.messenger.userInGroup["other"])this.BXIM.messenger.userInGroup["other"]={id:"other",users:[]};if(!this.BXIM.messenger.woUserInGroup["other"])this.BXIM.messenger.woUserInGroup["other"]={id:"other",users:[]};var t=s.clone(this.BXIM.messenger.userInGroup["other"]["users"]);var r=s.clone(this.BXIM.messenger.woUserInGroup["other"]["users"]);for(var i in e.USERS){this.BXIM.messenger.users[i]=e.USERS[i];this.BXIM.messenger.userInGroup["other"]["users"].push(i);this.BXIM.messenger.woUserInGroup["other"]["users"].push(i)}if(this.BXIM.messenger.contactList)this.contactListRedraw({FORCE:true});this.BXIM.messenger.userInGroup["other"]["users"]=t;this.BXIM.messenger.woUserInGroup["other"]["users"]=r},this),onfailure:function(e){}})},this),1e3)}}this.userListRedraw()};s.MessengerCommon.prototype.recentListRedraw=function(e){clearTimeout(this.BXIM.messenger.redrawRecentListTimeout);if(this.MobileActionNotEqual("RECENT"))return false;if(!this.isMobile()){
if(this.BXIM.messenger.popupMessenger==null)return false;this.BXIM.messenger.recentList=true;s.addClass(this.BXIM.messenger.recentListTab,"bx-messenger-cl-switcher-tab-active");this.BXIM.messenger.contactList=false;s.removeClass(this.BXIM.messenger.contactListTab,"bx-messenger-cl-switcher-tab-active")}if(this.BXIM.messenger.contactListSearchText!=null&&this.BXIM.messenger.contactListSearchText.length==0){this.BXIM.messenger.recentListReturn=true}else{this.BXIM.messenger.contactListSearchText="";this.BXIM.messenger.popupContactListSearchInput.value=""}if(this.BXIM.messenger.redrawContactListTimeout["contactList"])clearTimeout(this.BXIM.messenger.redrawContactListTimeout["contactList"]);if(!this.isMobile()&&this.BXIM.messenger.popupPopupMenu!=null)this.BXIM.messenger.popupPopupMenu.close();this.BXIM.messenger.popupContactListElementsWrap.innerHTML="";s.adjust(this.BXIM.messenger.popupContactListElementsWrap,{children:this.recentListPrepare(e)});if(this.isMobile()){BitrixMobile.LazyLoad.showImages()}};s.MessengerCommon.prototype.recentListPrepare=function(e){var t=[];var r={};e=typeof e=="object"?e:{};var i=e.showOnlyChat;if(!this.BXIM.messenger.recentListLoad){t.push(s.create("div",{props:{className:"bx-messenger-cl-item-load"},html:s.message("IM_CL_LOAD")}));this.recentListGetFromServer();return t}if(this.isMobile()){BitrixMobile.LazyLoad.clearImages()}this.BXIM.messenger.recent.sort(function(e,s){var t=parseInt(e.date);var r=parseInt(s.date);if(t>r){return-1}else if(t<r){return 1}else{if(e>s){return-1}else if(e<s){return 1}else{return 0}}});this.BXIM.messenger.recentListIndex=[];for(var n=0;n<this.BXIM.messenger.recent.length;n++){if(typeof this.BXIM.messenger.recent[n].userIsChat=="undefined")this.BXIM.messenger.recent[n].userIsChat=this.BXIM.messenger.recent[n].recipientId.toString().substr(0,4)=="chat";var a=s.clone(this.BXIM.messenger.recent[n]);var o="";if(a.userIsChat){g=this.BXIM.messenger.chat[a.userId.toString().substr(4)];if(typeof g=="undefined"||typeof g.name=="undefined")continue;var m="chat"+g.id}else if(!i){var g=this.BXIM.messenger.users[a.userId];if(typeof g=="undefined"||this.BXIM.userId==g.id||typeof g.name=="undefined")continue;var m=g.id}else{continue}if(parseInt(a.date)>0){a.date=this.formatDate(a.date,this.getDateFormatType("RECENT_TITLE"));if(!r[a.date]){r[a.date]=true;t.push(s.create("div",{props:{className:"bx-messenger-recent-group"},children:[s.create("span",{props:{className:"bx-messenger-recent-group-title"+(this.BXIM.language=="ru"?" bx-messenger-lowercase":"")},html:a.date})]}))}}else{if(!r["never"]){r["never"]=true;t.push(s.create("div",{props:{className:"bx-messenger-recent-group"},children:[s.create("span",{props:{className:"bx-messenger-recent-group-title"},html:s.message("IM_RESENT_NEVER")})]}))}}if(this.BXIM.messenger.message[a.id]&&this.BXIM.messenger.message[a.id].text){a.text=this.BXIM.messenger.message[a.id].text}if(!a.text&&a.params&&a.params["FILE_ID"].length>0){a.text="["+s.message("IM_F_FILE")+"]"}var l="";var h="";if(this.BXIM.messenger.unreadMessage[m]&&this.BXIM.messenger.unreadMessage[m].length>0){l="bx-messenger-cl-status-new-message";h='<span class="bx-messenger-cl-count-digit">'+(this.BXIM.messenger.unreadMessage[m].length<100?this.BXIM.messenger.unreadMessage[m].length:"99+")+"</span>"}var I="";var M="";if(this.countWriting(m))I="bx-messenger-cl-status-writing";if(a.senderId==this.BXIM.userId)M='<span class="bx-messenger-cl-user-reply"></span>';if(!g.avatar)g.avatar=this.BXIM.pathToBlankImage;a.text=this.prepareText(a.text);a.text=a.text.replace(/<img.*?data-code="([^"]*)".*?>/gi,"$1");a.text=a.text.replace(/<s>([^"]*)<\/s>/gi,"");a.text=a.text.replace("<br />"," ").replace(/<\/?[^>]+>/gi,"").replace(/------------------------------------------------------(.*?)------------------------------------------------------/gim," ["+s.message("IM_M_QUOTE_BLOCK")+"] ");var p="";var d=g.avatar;var u="";if(this.isMobile()){if(this.BXIM.messenger.currentTab==m){u="bx-messenger-cl-item-active "}var f="mobile-rc-avatar-id-"+g.id;p='id="'+f+'" data-src="'+g.avatar+'"';d=this.BXIM.pathToBlankImage;BitrixMobile.LazyLoad.registerImage(f)}t.push(s.create("span",{props:{className:"bx-messenger-cl-item  bx-messenger-cl-id-"+(a.userIsChat?"chat":"")+g.id+" "+u+(a.userIsChat?"bx-messenger-cl-item-chat "+l+" "+I+" "+o:"bx-messenger-cl-status-"+this.getUserStatus(g.id)+" "+l+" "+I)},attrs:{"data-userId":m,"data-name":g.name,"data-status":this.getUserStatus(g.id),"data-avatar":g.avatar,"data-userIsChat":a.userIsChat},html:'<span class="bx-messenger-cl-count">'+h+"</span>"+'<span class="bx-messenger-cl-avatar '+(a.userIsChat?"bx-messenger-cl-avatar-"+g.style:"")+'"><img class="bx-messenger-cl-avatar-img'+(this.isBlankAvatar(g.avatar)?" bx-messenger-cl-avatar-img-default":"")+'" src="'+d+'" '+p+'><span class="bx-messenger-cl-status"></span></span>'+'<span class="bx-messenger-cl-user">'+'<div class="bx-messenger-cl-user-title'+(g.extranet?" bx-messenger-user-extranet":"")+'">'+(g.nameList?g.nameList:g.name)+"</div>"+'<div class="bx-messenger-cl-user-desc">'+M+""+a.text+"</div>"+"</span>"}));this.BXIM.messenger.recentListIndex.push(m)}if(t.length<=0){t.push(s.create("div",{props:{className:"bx-messenger-cl-item-empty"},html:s.message("IM_M_CL_EMPTY")}))}return t};s.MessengerCommon.prototype.recentListAdd=function(e){if(!e.skipDateCheck){for(var t=0;t<this.BXIM.messenger.recent.length;t++){if(this.BXIM.messenger.recent[t].userId==e.userId&&parseInt(this.BXIM.messenger.recent[t].date)>parseInt(e.date))return false}}var r=[];r.push(e);for(var t=0;t<this.BXIM.messenger.recent.length;t++)if(this.BXIM.messenger.recent[t].userId!=e.userId)r.push(this.BXIM.messenger.recent[t]);this.BXIM.messenger.recent=r;if(this.BXIM.messenger.recentList){if(this.isMobile()){clearTimeout(this.BXIM.messenger.redrawRecentListTimeout);this.BXIM.messenger.redrawRecentListTimeout=setTimeout(s.delegate(function(){this.recentListRedraw()},this),300)}else{this.recentListRedraw()}}};s.MessengerCommon.prototype.recentListHide=function(e,t){var r=[];for(var i=0;i<this.BXIM.messenger.recent.length;i++)if(this.BXIM.messenger.recent[i].userId!=e)r.push(this.BXIM.messenger.recent[i]);this.BXIM.messenger.recent=r;if(this.BXIM.messenger.recentList)this.recentListRedraw();if(!this.isMobile())s.localStorage.set("mrlr",e,5);t=t!=false;if(t){s.ajax({url:this.BXIM.pathToAjax+"?RECENT_HIDE&V="+this.BXIM.revision,method:"POST",dataType:"json",timeout:60,data:{IM_RECENT_HIDE:"Y",USER_ID:e,IM_AJAX_CALL:"Y",sessid:s.bitrix_sessid()}});this.readMessage(e,true,true)}};s.MessengerCommon.prototype.recentListGetFromServer=function(){if(this.BXIM.messenger.recentListLoad)return false;this.BXIM.messenger.recentListLoad=true;s.ajax({url:this.BXIM.pathToAjax+"?RECENT_LIST&V="+this.BXIM.revision,method:"POST",dataType:"json",skipAuthCheck:true,timeout:30,data:{IM_RECENT_LIST:"Y",IM_AJAX_CALL:"Y",sessid:s.bitrix_sessid()},onsuccess:s.delegate(function(t){if(t&&t.BITRIX_SESSID){s.message({bitrix_sessid:t.BITRIX_SESSID})}if(t.ERROR==""){this.BXIM.messenger.recent=[];for(var r in t.RECENT){t.RECENT[r].date=parseInt(t.RECENT[r].date)-parseInt(s.message("USER_TZ_OFFSET"));this.BXIM.messenger.recent.push(t.RECENT[r])}var i=false;for(var r in this.BXIM.messenger.unreadMessage){for(var n=0;n<this.BXIM.messenger.unreadMessage[r].length;n++){if(!i||i.SEND_DATE<=this.BXIM.messenger.message[this.BXIM.messenger.unreadMessage[r][n]].date){i={ID:this.BXIM.messenger.message[this.BXIM.messenger.unreadMessage[r][n]].id,SEND_DATE:this.BXIM.messenger.message[this.BXIM.messenger.unreadMessage[r][n]].date,RECIPIENT_ID:this.BXIM.messenger.message[this.BXIM.messenger.unreadMessage[r][n]].recipientId,SENDER_ID:this.BXIM.messenger.message[this.BXIM.messenger.unreadMessage[r][n]].senderId,USER_ID:this.BXIM.messenger.message[this.BXIM.messenger.unreadMessage[r][n]].senderId,SEND_MESSAGE:this.BXIM.messenger.message[this.BXIM.messenger.unreadMessage[r][n]].text,PARAMS:this.BXIM.messenger.message[this.BXIM.messenger.unreadMessage[r][n]].params}}}}if(i){this.recentListAdd({userId:i.RECIPIENT_ID.toString().substr(0,4)=="chat"?i.RECIPIENT_ID:i.USER_ID,id:i.ID,date:i.SEND_DATE,recipientId:i.RECIPIENT_ID,senderId:i.SENDER_ID,text:i.SEND_MESSAGE,params:i.PARAMS},true)}for(var r in t.CHAT){if(this.BXIM.messenger.chat[r]&&this.BXIM.messenger.chat[r].fake)t.CHAT[r].fake=true;else if(!this.BXIM.messenger.chat[r])t.CHAT[r].fake=true;this.BXIM.messenger.chat[r]=t.CHAT[r]}for(var r in t.USERS)this.BXIM.messenger.users[r]=t.USERS[r];if(this.BXIM.messenger.recentList)this.recentListRedraw();this.BXIM.messenger.smile=t.SMILE;this.BXIM.messenger.smileSet=t.SMILE_SET;this.BXIM.settingsNotifyBlocked=t.NOTIFY_BLOCKED;if(!this.isMobile())this.BXIM.messenger.dialogStatusRedraw()}else{this.BXIM.messenger.recentListLoad=false;if(t.ERROR=="SESSION_ERROR"&&this.BXIM.messenger.sendAjaxTry<2){this.BXIM.messenger.sendAjaxTry++;setTimeout(s.delegate(this.recentListGetFromServer,this),2e3);s.onCustomEvent(e,"onImError",[t.ERROR,t.BITRIX_SESSID])}else if(t.ERROR=="AUTHORIZE_ERROR"){this.BXIM.messenger.sendAjaxTry++;if(this.BXIM.desktop&&this.BXIM.desktop.ready()){setTimeout(s.delegate(this.recentListGetFromServer,this),1e4)}s.onCustomEvent(e,"onImError",[t.ERROR])}}},this),onfailure:s.delegate(function(){this.BXIM.messenger.sendAjaxTry=0;this.BXIM.messenger.recentListLoad=false},this)})};s.MessengerCommon.prototype.drawMessage=function(e,t,r,i){if(this.BXIM.messenger.popupMessenger==null||e!=this.BXIM.messenger.currentTab||typeof t!="object"||e==0||!this.MobileActionEqual("DIALOG"))return false;i=i==true;r=i?false:r;if(t.senderId==this.BXIM.userId&&this.BXIM.messenger.popupMessengerLastMessage<t.id){this.BXIM.messenger.popupMessengerLastMessage=t.id}if(typeof t.params!="object"){t.params={}}this.BXIM.messenger.openChatFlag=this.BXIM.messenger.currentTab.toString().substr(0,4)=="chat"?true:false;var n=t.params&&t.params.IS_EDITED=="Y";var a=t.params&&t.params.IS_DELETED=="Y";var o=t.id.indexOf("temp")==0;var m=o&&t.retry;var g=t.senderId==0;var l=this.BXIM.messenger.openChatFlag&&this.BXIM.messenger.chat[this.BXIM.messenger.currentTab.toString().substr(4)]&&this.BXIM.messenger.chat[this.BXIM.messenger.currentTab.toString().substr(4)].style=="group";var h=this.BXIM.ppServerStatus;if(this.BXIM.messenger.openChatFlag&&this.BXIM.messenger.chat[this.BXIM.messenger.currentTab.toString().substr(4)]&&this.BXIM.messenger.chat[this.BXIM.messenger.currentTab.toString().substr(4)].style=="call")h=false;var I=h&&typeof t.params.LIKE=="object"&&t.params.LIKE.length>0?t.params.LIKE.length:"";var M=h&&typeof t.params.LIKE=="object"&&s.util.in_array(this.BXIM.userId,t.params.LIKE);var p=s.MessengerCommon.diskDrawFiles(t.chatId,t.params.FILE_ID);if(p.length>0){p=s.create("div",{props:{className:"bx-messenger-file-box"+(t.text!=""?" bx-messenger-file-box-with-message":"")},children:p})}else{p=null}var d=false;if(!p&&t.text.length<=0){d=true;c=true}if(t.system&&t.system=="Y"){g=true;t.senderId=0}var u=this.BXIM.messenger.users[t.senderId];if(!g&&typeof u=="undefined"){d=true;c=true}if(!this.BXIM.messenger.history[e])this.BXIM.messenger.history[e]=[];if(parseInt(t.id)>0)this.BXIM.messenger.history[e].push(t.id);if(!d){var f=0;var c=false;var B=false;if(this.BXIM.messenger.unreadMessage[e]&&s.util.in_array(t.id,this.BXIM.messenger.unreadMessage[e]))B=true}var X=false;var E=null;if(i){E=this.BXIM.messenger.popupMessengerBodyWrap.firstChild;if(E){if(s.hasClass(E,"bx-messenger-content-empty")||s.hasClass(E,"bx-messenger-content-load")){s.remove(E)}else if(s.hasClass(E,"bx-messenger-content-group")){E=E.nextSibling}}}else{E=this.BXIM.messenger.popupMessengerBodyWrap.lastChild;if(E&&(s.hasClass(E,"bx-messenger-content-empty")||s.hasClass(E,"bx-messenger-content-load"))){s.remove(E)}else if(E&&s.hasClass(E,"bx-messenger-content-item-notify")){if(t.senderId==this.BXIM.messenger.currentTab||!this.countWriting(this.BXIM.messenger.currentTab)){s.remove(E);X=false;E=this.BXIM.messenger.popupMessengerBodyWrap.lastChild}else{X=true;E=this.BXIM.messenger.popupMessengerBodyWrap.lastChild.previousSibling}}}if(!d){var S=this.formatDate(t.date,this.getDateFormatType("MESSAGE_TITLE"));if(!s("bx-im-go-"+S)){var _=[];if(this.BXIM.desktop&&this.BXIM.desktop.run()){_=[s.create("a",{attrs:{name:"bx-im-go-"+t.date},props:{className:"bx-messenger-content-group-link"}}),s.create("a",{attrs:{id:"bx-im-go-"+S,href:"#bx-im-go-"+t.date},props:{className:"bx-messenger-content-group-title"+(this.BXIM.language=="ru"?" bx-messenger-lowercase":"")},html:S})]}else{_=[s.create("a",{attrs:{name:"bx-im-go-"+t.date},props:{className:"bx-messenger-content-group-link"}}),s.create("div",{attrs:{id:"bx-im-go-"+S},props:{className:"bx-messenger-content-group-title"+(this.BXIM.language=="ru"?" bx-messenger-lowercase":"")},html:S})]}var b=s.create("div",{props:{className:"bx-messenger-content-group"+(S==s.message("FD_TODAY")?" bx-messenger-content-group-today":"")},children:_});if(i){this.BXIM.messenger.popupMessengerBodyWrap.insertBefore(b,this.BXIM.messenger.popupMessengerBodyWrap.firstChild);E=b.nextSibling}else{if(X&&E.nextElementSibling){this.BXIM.messenger.popupMessengerBodyWrap.insertBefore(b,E.nextElementSibling);E=b}else{this.BXIM.messenger.popupMessengerBodyWrap.appendChild(b)}}}if(!g&&E){if(t.senderId==E.getAttribute("data-senderId")&&parseInt(t.date)-300<parseInt(E.getAttribute("data-messageDate"))){var T=s.findChildByClassName(E,"bx-messenger-content-item-text-message");var C=[s.create("div",{props:{className:"bx-messenger-hr"}}),s.create("span",{props:{className:"bx-messenger-content-item-text-wrap"+(i?" bx-messenger-content-item-text-wrap-append":"")},children:[s.create("span",{attrs:{title:s.message("IM_M_OPEN_EXTRA_TITLE").replace("#SHORTCUT#",s.browser.IsMac()?"CMD":"CTRL")},props:{className:"bx-messenger-content-item-menu"}}),s.create("span",{props:{className:"bx-messenger-message"+(a?" bx-messenger-message-deleted":" ")+(a||n?" bx-messenger-message-edited":"")},attrs:{id:"im-message-"+t.id},html:s.MessengerCommon.prepareText(t.text,false,true,true,!this.BXIM.messenger.openChatFlag||t.senderId==this.BXIM.userId?false:this.BXIM.messenger.users[this.BXIM.userId].name)}),p]})];if(i){for(var R=0,v=C.length;R<v;R++){T.insertBefore(C[R],T.firstChild)}E.setAttribute("data-blockmessageid",t.id)}else{for(var R=0,v=C.length;R<v;R++){T.appendChild(C[R])}var x=s.findChildByClassName(E,"bx-messenger-content-item-date");x.innerHTML=o?s.message("IM_M_DELIVERED"):" &nbsp; "+this.formatDate(t.date,this.getDateFormatType("MESSAGE"));if(m){this.drawProgessMessage(t.id,{title:s.message("IM_M_RETRY")})}else if(o){this.drawProgessMessage(t.id)}E.setAttribute("data-messageDate",t.date);E.setAttribute("data-messageId",t.id);E.setAttribute("data-senderId",t.senderId)}if(B)s.addClass(E,"bx-messenger-content-item-new");f=t.id;c=true}}}if(!c){if(E)f=E.getAttribute("data-messageId");if(g){var A=s.findChild(this.BXIM.messenger.popupMessengerBodyWrap,{attribute:{"data-messageId":""+t.id+""}},false);if(!A){var y=s.create("div",{attrs:{"data-type":"system","data-senderId":t.senderId,"data-messageId":t.id,"data-blockmessageid":t.id},props:{className:"bx-messenger-content-item bx-messenger-content-item-system"},children:[s.create("span",{props:{className:"bx-messenger-content-item-content"},children:[typeof u=="undefined"?[]:s.create("span",{props:{className:"bx-messenger-content-item-avatar"},children:[s.create("span",{props:{className:"bx-messenger-content-item-arrow"}}),s.create("img",{props:{className:"bx-messenger-content-item-avatar-img"+(s.MessengerCommon.isBlankAvatar(u.avatar)?" bx-messenger-content-item-avatar-img-default":"")},attrs:{src:u.avatar}})]}),s.create("span",{props:{className:"bx-messenger-content-item-text-center"},children:[s.create("span",{props:{className:"bx-messenger-content-item-text-message"},children:[s.create("span",{props:{className:"bx-messenger-content-item-text-wrap"+(i?" bx-messenger-content-item-text-wrap-append":"")},children:[s.create("span",{props:{className:"bx-messenger-message"+(a?" bx-messenger-message-deleted":"")+(a||n?" bx-messenger-message-edited":"")},attrs:{id:"im-message-"+t.id},html:s.MessengerCommon.prepareText(t.text,false,true,true)}),p]})]}),s.create("span",{props:{className:"bx-messenger-content-item-params"},children:[s.create("span",{props:{className:"bx-messenger-content-item-date"},html:" &nbsp; "+this.formatDate(t.date,this.getDateFormatType("MESSAGE"))}),!h?null:s.create("span",{props:{className:"bx-messenger-content-item-like"+(M?" bx-messenger-content-item-liked":"")},children:[s.create("span",{attrs:{title:I>0?s.message("IM_MESSAGE_LIKE_LIST"):""},props:{className:"bx-messenger-content-like-digit"+(I<=0?" bx-messenger-content-like-digit-off":"")},html:I}),s.create("span",{attrs:{"data-messageId":t.id},props:{className:"bx-messenger-content-like-button"},html:s.message(!M?"IM_MESSAGE_LIKE":"IM_MESSAGE_DISLIKE")})]})]}),s.create("span",{props:{className:"bx-messenger-clear"}})]})]})]});if(t.system&&t.system=="Y"&&B)s.addClass(y,"bx-messenger-content-item-new")}}else if(t.senderId==this.BXIM.userId){var y=s.create("div",{attrs:{"data-type":"self","data-senderId":t.senderId,"data-messageDate":t.date,"data-messageId":t.id,"data-blockmessageid":t.id},props:{className:"bx-messenger-content-item"},children:[s.create("span",{props:{className:"bx-messenger-content-item-content"},children:[s.create("span",{props:{className:"bx-messenger-content-item-avatar"},children:[s.create("span",{props:{className:"bx-messenger-content-item-arrow"}}),s.create("img",{props:{className:"bx-messenger-content-item-avatar-img"+(s.MessengerCommon.isBlankAvatar(u.avatar)?" bx-messenger-content-item-avatar-img-default":"")},attrs:{src:u.avatar}})]}),m?s.create("span",{props:{className:"bx-messenger-content-item-status"},children:[s.create("span",{attrs:{title:s.message("IM_M_RETRY"),"data-messageid":t.id,"data-chat":parseInt(t.recipientId)>0?"Y":"N"},props:{className:"bx-messenger-content-item-error"},children:[s.create("span",{props:{className:"bx-messenger-content-item-error-icon"}})]})]}):s.create("span",{props:{className:"bx-messenger-content-item-status"},children:o?[s.create("span",{props:{className:"bx-messenger-content-item-progress"}})]:[]}),s.create("span",{props:{className:"bx-messenger-content-item-text-center"},children:[s.create("span",{props:{className:"bx-messenger-content-item-text-message"},children:[s.create("span",{props:{className:"bx-messenger-content-item-text-wrap"+(i?" bx-messenger-content-item-text-wrap-append":"")},children:[s.create("span",{attrs:{title:s.message("IM_M_OPEN_EXTRA_TITLE").replace("#SHORTCUT#",s.browser.IsMac()?"CMD":"CTRL")},props:{className:"bx-messenger-content-item-menu"}}),s.create("span",{props:{className:"bx-messenger-message"+(a?" bx-messenger-message-deleted":" ")+(a||n?" bx-messenger-message-edited":"")},attrs:{id:"im-message-"+t.id},html:s.MessengerCommon.prepareText(t.text,false,true,true)}),p]})]}),s.create("span",{props:{className:"bx-messenger-content-item-params"},children:[s.create("span",{props:{className:"bx-messenger-content-item-date"},html:m?s.message("IM_M_NOT_DELIVERED"):o?s.message("IM_M_DELIVERED"):" &nbsp; "+this.formatDate(t.date,this.getDateFormatType("MESSAGE"))}),!h?null:s.create("span",{props:{className:"bx-messenger-content-item-like"+(M?" bx-messenger-content-item-liked":"")},children:[s.create("span",{attrs:{title:I>0?s.message("IM_MESSAGE_LIKE_LIST"):""},props:{className:"bx-messenger-content-like-digit"+(I<=0?" bx-messenger-content-like-digit-off":"")},html:I}),s.create("span",{attrs:{"data-messageId":t.id},props:{className:"bx-messenger-content-like-button"},html:s.message(!M?"IM_MESSAGE_LIKE":"IM_MESSAGE_DISLIKE")})]})]}),s.create("span",{props:{className:"bx-messenger-clear"}})]})]})]})}else{var y=s.create("div",{attrs:{"data-type":"other","data-senderId":t.senderId,"data-messageDate":t.date,"data-messageId":t.id,"data-blockmessageid":t.id},props:{className:"bx-messenger-content-item bx-messenger-content-item-2"+(B?" bx-messenger-content-item-new":"")},children:[s.create("span",{props:{className:"bx-messenger-content-item-content"},children:[s.create("span",{attrs:{title:l?u.name:""},props:{className:"bx-messenger-content-item-avatar bx-messenger-content-item-avatar-button"},children:[s.create("span",{props:{className:"bx-messenger-content-item-arrow"}}),s.create("img",{props:{className:"bx-messenger-content-item-avatar-img"+(s.MessengerCommon.isBlankAvatar(u.avatar)?" bx-messenger-content-item-avatar-img-default":"")},attrs:{src:u.avatar}})]}),s.create("span",{props:{className:"bx-messenger-content-item-status"},children:[]}),s.create("span",{props:{className:"bx-messenger-content-item-text-center"},children:[s.create("span",{props:{className:"bx-messenger-content-item-text-message"},children:[s.create("span",{props:{className:"bx-messenger-content-item-text-wrap"+(i?" bx-messenger-content-item-text-wrap-append":"")},children:[s.create("span",{attrs:{title:s.message("IM_M_OPEN_EXTRA_TITLE").replace("#SHORTCUT#",s.browser.IsMac()?"CMD":"CTRL")},props:{className:"bx-messenger-content-item-menu"}}),s.create("span",{props:{className:"bx-messenger-message"+(a?" bx-messenger-message-deleted":" ")+(a||n?" bx-messenger-message-edited":"")},attrs:{id:"im-message-"+t.id},html:s.MessengerCommon.prepareText(t.text,false,true,true,!this.BXIM.messenger.openChatFlag||t.senderId==this.BXIM.userId?false:this.BXIM.messenger.users[this.BXIM.userId].name)}),p]})]}),s.create("span",{props:{className:"bx-messenger-content-item-params"},children:[s.create("span",{props:{className:"bx-messenger-content-item-date"},html:o?s.message("IM_M_DELIVERED"):" &nbsp; "+this.formatDate(t.date,this.getDateFormatType("MESSAGE"))}),!h?null:s.create("span",{props:{className:"bx-messenger-content-item-like"+(M?" bx-messenger-content-item-liked":"")},children:[s.create("span",{attrs:{title:I>0?s.message("IM_MESSAGE_LIKE_LIST"):""},props:{className:"bx-messenger-content-like-digit"+(I<=0?" bx-messenger-content-like-digit-off":"")},html:I}),s.create("span",{attrs:{"data-messageId":t.id},props:{className:"bx-messenger-content-like-button"},html:s.message(!M?"IM_MESSAGE_LIKE":"IM_MESSAGE_DISLIKE")})]})]}),s.create("span",{props:{className:"bx-messenger-clear"}})]})]})]})}}else if(d){y=s.create("div",{attrs:{id:"im-message-"+t.id,"data-messageDate":t.date,"data-messageId":t.id,"data-blockmessageid":t.id},props:{className:"bx-messenger-content-item-text-wrap bx-messenger-item-skipped"}})}if(!c||d){if(i)this.BXIM.messenger.popupMessengerBodyWrap.insertBefore(y,E);else if(X&&E.nextElementSibling)this.BXIM.messenger.popupMessengerBodyWrap.insertBefore(y,E.nextElementSibling);else this.BXIM.messenger.popupMessengerBodyWrap.appendChild(y)}if(!d&&s.MessengerCommon.enableScroll(this.BXIM.messenger.popupMessengerBody,this.BXIM.messenger.popupMessengerBody.offsetHeight,r)){if(this.BXIM.animationSupport){if(this.BXIM.messenger.popupMessengerBodyAnimation!=null)this.BXIM.messenger.popupMessengerBodyAnimation.stop();(this.BXIM.messenger.popupMessengerBodyAnimation=new s.easing({duration:800,start:{scroll:this.BXIM.messenger.popupMessengerBody.scrollTop},finish:{scroll:this.BXIM.messenger.popupMessengerBody.scrollHeight-this.BXIM.messenger.popupMessengerBody.offsetHeight*(this.isMobile()?0:1)},transition:s.easing.makeEaseInOut(s.easing.transitions.quart),step:s.delegate(function(e){this.BXIM.messenger.popupMessengerBody.scrollTop=e.scroll},this)})).animate()}else{this.BXIM.messenger.popupMessengerBody.scrollTop=this.BXIM.messenger.popupMessengerBody.scrollHeight-this.BXIM.messenger.popupMessengerBody.offsetHeight*(this.isMobile()?0:1)}}return f};s.MessengerCommon.prototype.drawProgessMessage=function(e,t){var r=s("im-message-"+e);if(!r)return false;s.addClass(r.parentNode.parentNode.parentNode.parentNode,"bx-messenger-content-item-content-progress");r.parentNode.parentNode.parentNode.previousSibling.innerHTML="";if(typeof t=="object"||t===true){if(this.BXIM.messenger.message[e]){this.BXIM.messenger.errorMessage[this.BXIM.messenger.currentTab]=true;s.addClass(r.parentNode.parentNode.parentNode.parentNode,"bx-messenger-content-item-content-progress-error");t.chat=t.chat?t.chat:parseInt(this.BXIM.messenger.message[e].recipientId)>0?"Y":"N";s.adjust(r.parentNode.parentNode.parentNode.previousSibling,{children:[s.create("span",{attrs:{title:t.title?t.title:"","data-messageid":e,"data-chat":t.chat},props:{className:"bx-messenger-content-item-error"},children:[s.create("span",{props:{className:"bx-messenger-content-item-error-icon"}})]})]})}else{s.removeClass(r.parentNode.parentNode.parentNode.parentNode,"bx-messenger-content-item-content-progress");s.removeClass(r.parentNode.parentNode.parentNode.parentNode,"bx-messenger-content-item-content-progress-error")}}else{s.adjust(r.parentNode.parentNode.parentNode.previousSibling,{children:[s.create("span",{props:{className:"bx-messenger-content-item-progress"}})]})}return true};s.MessengerCommon.prototype.clearProgessMessage=function(e){var t=s("im-message-"+e);if(!t)return false;s.removeClass(t.parentNode.parentNode.parentNode.parentNode,"bx-messenger-content-item-content-progress");s.removeClass(t.parentNode.parentNode.parentNode.parentNode,"bx-messenger-content-item-content-progress-error");t.parentNode.parentNode.parentNode.previousSibling.innerHTML="";return true};s.MessengerCommon.prototype.startWriting=function(e,t){if(t==this.BXIM.userId){this.BXIM.messenger.writingList[e]=true;this.drawWriting(e);clearTimeout(this.BXIM.messenger.writingListTimeout[e]);this.BXIM.messenger.writingListTimeout[e]=setTimeout(s.delegate(function(){this.endWriting(e)},this),29500)}else{if(!this.BXIM.messenger.writingList[t])this.BXIM.messenger.writingList[t]={};if(!this.BXIM.messenger.writingListTimeout[t])this.BXIM.messenger.writingListTimeout[t]={};this.BXIM.messenger.writingList[t][e]=true;this.drawWriting(e,t);clearTimeout(this.BXIM.messenger.writingListTimeout[t][e]);this.BXIM.messenger.writingListTimeout[t][e]=setTimeout(s.delegate(function(){this.endWriting(e,t)},this),29500)}};s.MessengerCommon.prototype.drawWriting=function(e,t){if(e==this.BXIM.userId)return false;if(this.BXIM.messenger.popupMessenger!=null&&this.MobileActionEqual("RECENT","DIALOG")){if(this.BXIM.messenger.writingList[e]||t&&this.countWriting(t)>0){var r=s.findChildrenByClassName(this.BXIM.messenger.popupContactListElementsWrap,"bx-messenger-cl-id-"+(t?t:e));if(r){for(var i=0;i<r.length;i++)s.addClass(r[i],"bx-messenger-cl-status-writing")}if(this.MobileActionEqual("DIALOG")&&(this.BXIM.messenger.currentTab==e||t&&this.BXIM.messenger.currentTab==t)){if(t){var n=[];for(var i in this.BXIM.messenger.writingList[t]){if(this.BXIM.messenger.writingList[t].hasOwnProperty(i)&&this.BXIM.messenger.users[i]){n.push(this.BXIM.messenger.users[i].name)}}this.drawNotifyMessage(t,"writing",s.message("IM_M_WRITING").replace("#USER_NAME#",n.join(", ")))}else{if(!this.isMobile()){this.BXIM.messenger.popupMessengerPanelAvatar.parentNode.className="bx-messenger-panel-avatar bx-messenger-panel-avatar-status-writing"}this.drawNotifyMessage(e,"writing",s.message("IM_M_WRITING").replace("#USER_NAME#",this.BXIM.messenger.users[e].name))}}}else if(!this.BXIM.messenger.writingList[e]||t&&this.countWriting(t)==0){var r=s.findChildrenByClassName(this.BXIM.messenger.popupContactListElementsWrap,"bx-messenger-cl-id-"+(t?t:e));if(r){for(var i=0;i<r.length;i++)s.removeClass(r[i],"bx-messenger-cl-status-writing")}if(this.MobileActionEqual("DIALOG")&&(this.BXIM.messenger.currentTab==e||this.BXIM.messenger.currentTab==t)){if(!t){if(!this.isMobile())this.BXIM.messenger.popupMessengerPanelAvatar.parentNode.className="bx-messenger-panel-avatar bx-messenger-panel-avatar-status-"+this.getUserStatus(e)}var a=this.BXIM.messenger.popupMessengerBodyWrap.lastChild;if(a&&s.hasClass(a,"bx-messenger-content-item-notify")){if(!t&&this.BXIM.messenger.readedList[e]){this.drawReadMessage(e,this.BXIM.messenger.readedList[e].messageId,this.BXIM.messenger.readedList[e].date,false)}else if(s.MessengerCommon.enableScroll(this.BXIM.messenger.popupMessengerBody,this.BXIM.messenger.popupMessengerBody.offsetHeight)){if(this.BXIM.animationSupport){if(this.BXIM.messenger.popupMessengerBodyAnimation!=null)this.BXIM.messenger.popupMessengerBodyAnimation.stop();(this.BXIM.messenger.popupMessengerBodyAnimation=new s.easing({duration:800,start:{scroll:this.BXIM.messenger.popupMessengerBody.scrollTop},finish:{scroll:this.BXIM.messenger.popupMessengerBody.scrollTop-a.offsetHeight},transition:s.easing.makeEaseInOut(s.easing.transitions.quart),step:s.delegate(function(e){this.BXIM.messenger.popupMessengerBody.scrollTop=e.scroll},this),complete:s.delegate(function(){s.remove(a)},this)})).animate()}else{this.BXIM.messenger.popupMessengerBody.scrollTop=this.BXIM.messenger.popupMessengerBody.scrollTop-a.offsetHeight;s.remove(a)}}else{this.BXIM.messenger.popupMessengerBody.scrollTop=this.BXIM.messenger.popupMessengerBody.scrollTop-a.offsetHeight;s.remove(a)}}}}}};s.MessengerCommon.prototype.endWriting=function(e,s){if(s){if(this.BXIM.messenger.writingListTimeout[s]&&this.BXIM.messenger.writingListTimeout[s][e])clearTimeout(this.BXIM.messenger.writingListTimeout[s][e]);if(this.BXIM.messenger.writingList[s]&&this.BXIM.messenger.writingList[s][e])delete this.BXIM.messenger.writingList[s][e]}else{clearTimeout(this.BXIM.messenger.writingListTimeout[e]);delete this.BXIM.messenger.writingList[e]}this.drawWriting(e,s)};s.MessengerCommon.prototype.sendWriting=function(t){if(!this.BXIM.ppServerStatus)return false;if(!this.BXIM.messenger.writingSendList[t]){clearTimeout(this.BXIM.messenger.writingSendListTimeout[t]);this.BXIM.messenger.writingSendList[t]=true;s.ajax({url:this.BXIM.pathToAjax+"?START_WRITING&V="+this.BXIM.revision,method:"POST",dataType:"json",timeout:30,data:{IM_START_WRITING:"Y",DIALOG_ID:t,IM_AJAX_CALL:"Y",sessid:s.bitrix_sessid()},onsuccess:s.delegate(function(t){if(t&&t.BITRIX_SESSID){s.message({bitrix_sessid:t.BITRIX_SESSID})}if(t.ERROR=="AUTHORIZE_ERROR"&&this.BXIM.desktop.ready()&&this.BXIM.messenger.sendAjaxTry<3){this.BXIM.messenger.sendAjaxTry++;s.onCustomEvent(e,"onImError",[t.ERROR])}else if(t.ERROR=="SESSION_ERROR"&&this.BXIM.messenger.sendAjaxTry<2){this.BXIM.messenger.sendAjaxTry++;s.onCustomEvent(e,"onImError",[t.ERROR,t.BITRIX_SESSID])}else{if(t.ERROR=="AUTHORIZE_ERROR"||t.ERROR=="SESSION_ERROR"){s.onCustomEvent(e,"onImError",[t.ERROR])}}},this)});this.BXIM.messenger.writingSendListTimeout[t]=setTimeout(s.delegate(function(){this.endSendWriting(t)},this),3e4)}};s.MessengerCommon.prototype.endSendWriting=function(e){clearTimeout(this.BXIM.messenger.writingSendListTimeout[e]);this.BXIM.messenger.writingSendList[e]=false};s.MessengerCommon.prototype.countWriting=function(e){var s=0;if(this.BXIM.messenger.writingList[e]){if(typeof this.BXIM.messenger.writingList[e]=="object"){for(var t in this.BXIM.messenger.writingList[e]){if(this.BXIM.messenger.writingList[e].hasOwnProperty(t)){s++}}}else{s=1}}return s};s.MessengerCommon.prototype.leaveFromChat=function(e,t){if(!this.BXIM.messenger.chat[e])return false;t=t!=false;if(!t){delete this.BXIM.messenger.chat[e];delete this.BXIM.messenger.userInChat[e];delete this.BXIM.messenger.unreadMessage[e];if(this.BXIM.messenger.popupMessenger!=null){if(this.BXIM.messenger.currentTab=="chat"+e){this.BXIM.messenger.currentTab=0;this.BXIM.messenger.openChatFlag=false;this.BXIM.messenger.openCallFlag=false;this.BXIM.messenger.extraClose()}s.MessengerCommon.userListRedraw()}}else{s.ajax({url:this.BXIM.pathToAjax+"?CHAT_LEAVE&V="+this.BXIM.revision,method:"POST",dataType:"json",timeout:60,data:{IM_CHAT_LEAVE:"Y",CHAT_ID:e,IM_AJAX_CALL:"Y",sessid:s.bitrix_sessid()},onsuccess:s.delegate(function(e){if(e.ERROR==""){this.readMessage("chat"+e.CHAT_ID,true,false);delete this.BXIM.messenger.chat[e.CHAT_ID];delete this.BXIM.messenger.userInChat[e.CHAT_ID];delete this.BXIM.messenger.unreadMessage[e.CHAT_ID];if(this.BXIM.messenger.popupMessenger!=null){
if(this.BXIM.messenger.currentTab=="chat"+e.CHAT_ID){this.BXIM.messenger.currentTab=0;this.BXIM.messenger.openChatFlag=false;this.BXIM.messenger.openCallFlag=false;s.localStorage.set("mct",this.BXIM.messenger.currentTab,15);this.BXIM.messenger.extraClose()}if(this.BXIM.messenger.recentList)s.MessengerCommon.recentListRedraw()}s.localStorage.set("mcl",e.CHAT_ID,5)}},this)})}};s.MessengerCommon.prototype.pullEvent=function(){s.addCustomEvent(this.isMobile()?"onPull-im":"onPullEvent-im",s.delegate(function(e,t){if(this.isMobile()){t=e.params;e=e.command}if(e=="desktopOffline"){this.BXIM.desktopStatus=false}else if(e=="desktopOnline"){this.BXIM.desktopStatus=true}else if(e=="readMessage"){if(this.MobileActionNotEqual("RECENT","DIALOG"))return false;this.readMessage(t.userId,false,false)}else if(e=="readMessageChat"){if(this.MobileActionNotEqual("RECENT","DIALOG"))return false;this.readMessage("chat"+t.chatId,false,false)}else if(e=="readMessageApponent"){if(this.MobileActionNotEqual("RECENT","DIALOG"))return false;t.date=parseInt(t.date)+parseInt(s.message("USER_TZ_OFFSET"));this.drawReadMessage(t.userId,t.lastId,t.date)}else if(e=="startWriting"){if(this.MobileActionNotEqual("RECENT","DIALOG"))return false;this.startWriting(t.senderId,t.dialogId)}else if(e=="message"||e=="messageChat"){if(this.MobileActionNotEqual("RECENT","DIALOG"))return false;if(this.BXIM.lastRecordId>=t.MESSAGE.id)return false;var r={};r.MESSAGE={};r.USERS_MESSAGE={};t.MESSAGE.date=parseInt(t.MESSAGE.date)+parseInt(s.message("USER_TZ_OFFSET"));for(var i in t.CHAT){if(this.BXIM.messenger.chat[i]&&this.BXIM.messenger.chat[i].fake)t.CHAT[i].fake=true;else if(!this.BXIM.messenger.chat[i])t.CHAT[i].fake=true;this.BXIM.messenger.chat[i]=t.CHAT[i]}for(var i in t.USER_IN_CHAT){this.BXIM.messenger.userInChat[i]=t.USER_IN_CHAT[i]}for(var i in t.USER_BLOCK_CHAT){this.BXIM.messenger.userChatBlockStatus[i]=t.USER_BLOCK_CHAT[i]}var n={};for(var i in t.USERS){if(this.BXIM.messenger.users[i]&&this.BXIM.messenger.users[i].status!=t.USERS[i].status&&parseInt(t.MESSAGE.date)+180>s.MessengerCommon.getNowDate()){n[i]=this.BXIM.messenger.users[i].status;this.BXIM.messenger.users[i].status=t.USERS[i].status}}if(this.MobileActionEqual("RECENT")){for(var i in n){if(!this.BXIM.messenger.users[i])continue;var a=s.findChildrenByClassName(this.BXIM.messenger.popupContactListElementsWrap,"bx-messenger-cl-id-"+i);if(a!=null){for(var o=0;o<a.length;o++){s.removeClass(a[o],"bx-messenger-cl-status-"+n[i]);s.addClass(a[o],"bx-messenger-cl-status-"+s.MessengerCommon.getUserStatus(i));a[o].setAttribute("data-status",s.MessengerCommon.getUserStatus(i))}}}}a=null;r.USERS=t.USERS;if(this.MobileActionEqual("DIALOG")){for(var i in t.FILES){if(!this.BXIM.disk.files[t.CHAT_ID])this.BXIM.disk.files[t.CHAT_ID]={};if(this.BXIM.disk.files[t.CHAT_ID][i])continue;t.FILES[i].date=parseInt(t.FILES[i].date)+parseInt(s.message("USER_TZ_OFFSET"));this.BXIM.disk.files[t.CHAT_ID][i]=t.FILES[i]}}r.MESSAGE[t.MESSAGE.id]=t.MESSAGE;this.BXIM.lastRecordId=t.MESSAGE.id;if(t.MESSAGE.senderId==this.BXIM.userId){if(this.BXIM.messenger.sendMessageFlag>0||this.BXIM.messenger.message[t.MESSAGE.id])return;this.readMessage(t.MESSAGE.recipientId,false,false);r.USERS_MESSAGE[t.MESSAGE.recipientId]=[t.MESSAGE.id];this.updateStateVar(r);s.MessengerCommon.recentListAdd({userId:t.MESSAGE.recipientId,id:t.MESSAGE.id,date:parseInt(t.MESSAGE.date)+parseInt(s.message("SERVER_TZ_OFFSET")),recipientId:t.MESSAGE.recipientId,senderId:t.MESSAGE.senderId,text:t.MESSAGE.text,params:t.MESSAGE.params},true)}else{r.UNREAD_MESSAGE={};r.UNREAD_MESSAGE[e=="messageChat"?t.MESSAGE.recipientId:t.MESSAGE.senderId]=[t.MESSAGE.id];r.USERS_MESSAGE[e=="messageChat"?t.MESSAGE.recipientId:t.MESSAGE.senderId]=[t.MESSAGE.id];if(e=="message")this.endWriting(t.MESSAGE.senderId);else this.endWriting(t.MESSAGE.senderId,t.MESSAGE.recipientId);this.updateStateVar(r);s.MessengerCommon.recentListAdd({userId:e=="messageChat"?t.MESSAGE.recipientId:t.MESSAGE.senderId,id:t.MESSAGE.id,date:parseInt(t.MESSAGE.date)+parseInt(s.message("SERVER_TZ_OFFSET")),recipientId:t.MESSAGE.recipientId,senderId:t.MESSAGE.senderId,text:t.MESSAGE.text,params:t.MESSAGE.params},true)}s.localStorage.set("mfm",this.BXIM.messenger.flashMessage,80)}else if(e=="messageUpdate"||e=="messageDelete"){if(this.MobileActionNotEqual("DIALOG","RECENT"))return false;if(this.BXIM.messenger.message[t.id]){if(!this.BXIM.messenger.message[t.id].params)this.BXIM.messenger.message[t.id].params={};var m=0;if(e=="messageDelete"){t.message=s.message("IM_M_DELETED");this.BXIM.messenger.message[t.id].params.IS_DELETED="Y"}else if(e=="messageUpdate"){this.BXIM.messenger.message[t.id].params.IS_EDITED="Y"}this.BXIM.messenger.message[t.id].text=t.text;if(t.type=="private"){m=t.fromUserId==this.BXIM.messenger.BXIM.userId?t.toUserId:t.fromUserId;this.endWriting(m)}else{m="chat"+t.chatId;this.endWriting(t.senderId,m)}if(this.BXIM.messenger.currentTab==m&&s("im-message-"+t.id)){var g=s("im-message-"+t.id);s.addClass(g,e=="messageDelete"?"bx-messenger-message-edited bx-messenger-message-deleted":"bx-messenger-message-edited");g.innerHTML=s.MessengerCommon.prepareText(this.BXIM.messenger.message[t.id].text,false,true,true);s.addClass(g,"bx-messenger-message-edited-anim");if(g.nextSibling&&s.hasClass(g.nextSibling,"bx-messenger-file-box")){s.addClass(g.nextSibling,"bx-messenger-file-box-with-message")}setTimeout(s.delegate(function(){s.removeClass(g,"bx-messenger-message-edited-anim")},this),1e3)}if(this.BXIM.messenger.recentList)s.MessengerCommon.recentListRedraw()}}else if(e=="messageLike"){if(this.MobileActionNotEqual("DIALOG"))return false;var l=s.util.in_array(this.BXIM.userId,t.users);var h=t.users.length>0?t.users.length:"";if(!this.BXIM.messenger.message[t.id]){return false}if(typeof this.BXIM.messenger.message[t.id].params!="object"){this.BXIM.messenger.message[t.id].params={}}this.BXIM.messenger.message[t.id].params.LIKE=t.users;if(s("im-message-"+t.id)){var I=s.findChild(this.BXIM.messenger.popupMessengerBodyWrap,{attribute:{"data-blockmessageid":""+t.id+""}},false);if(I){var M=s.findChildByClassName(I,"bx-messenger-content-item-like");if(M){var p=s.findChildByClassName(M,"bx-messenger-content-like-digit",false);var d=s.findChildByClassName(M,"bx-messenger-content-like-button",false);if(l){d.innerHTML=s.message("IM_MESSAGE_DISLIKE");s.addClass(M,"bx-messenger-content-item-liked")}else{d.innerHTML=s.message("IM_MESSAGE_LIKE");s.removeClass(M,"bx-messenger-content-item-liked")}if(h>0){p.setAttribute("title",s.message("IM_MESSAGE_LIKE_LIST"));s.removeClass(p,"bx-messenger-content-like-digit-off")}else{p.setAttribute("title","");s.addClass(p,"bx-messenger-content-like-digit-off")}if(p.innerHTML<h){s.addClass(I.firstChild,"bx-messenger-content-item-plus-like");setTimeout(function(){s.removeClass(I.firstChild,"bx-messenger-content-item-plus-like")},500)}p.innerHTML=h}}}}else if(e=="fileUpload"){if(this.MobileActionNotEqual("DIALOG"))return false;if(this.BXIM.disk.filesProgress[t.fileTmpId])return false;if(this.BXIM.disk.files[t.fileChatId]&&this.BXIM.disk.files[t.fileChatId][t.fileId]){t.fileParams["preview"]=this.BXIM.disk.files[t.fileChatId][t.fileId]["preview"]}if(!this.BXIM.disk.files[t.fileChatId])this.BXIM.disk.files[t.fileChatId]={};this.BXIM.disk.files[t.fileChatId][t.fileId]=t.fileParams;s.MessengerCommon.diskRedrawFile(t.fileChatId,t.fileId);if(s.MessengerCommon.enableScroll(this.BXIM.messenger.popupMessengerBody,this.BXIM.messenger.popupMessengerBody.offsetHeight)){if(this.BXIM.messenger.BXIM.animationSupport){if(this.BXIM.messenger.popupMessengerBodyAnimation!=null)this.BXIM.messenger.popupMessengerBodyAnimation.stop();(this.BXIM.messenger.popupMessengerBodyAnimation=new s.easing({duration:800,start:{scroll:this.BXIM.messenger.popupMessengerBody.scrollTop},finish:{scroll:this.BXIM.messenger.popupMessengerBody.scrollHeight-this.BXIM.messenger.popupMessengerBody.offsetHeight*(this.isMobile()?0:1)},transition:s.easing.makeEaseInOut(s.easing.transitions.quart),step:s.delegate(function(e){this.BXIM.messenger.popupMessengerBody.scrollTop=e.scroll},this)})).animate()}else{this.BXIM.messenger.popupMessengerBody.scrollTop=this.BXIM.messenger.popupMessengerBody.scrollHeight-this.BXIM.messenger.popupMessengerBody.offsetHeight*(this.isMobile()?0:1)}}}else if(e=="fileUnRegister"){if(this.MobileActionNotEqual("DIALOG"))return false;for(var u in t.files){if(this.BXIM.disk.filesRegister[t.chatId]){delete this.BXIM.disk.filesRegister[t.chatId][t.files[u]]}if(this.BXIM.disk.files[t.chatId]){this.BXIM.disk.files[t.chatId][t.files[u]].status="error";s.MessengerCommon.diskRedrawFile(t.chatId,t.files[u])}delete this.BXIM.disk.filesProgress[u]}this.drawTab(this.BXIM.messenger.getRecipientByChatId(t.chatId))}else if(e=="chatRename"){if(this.MobileActionNotEqual("DIALOG","RECENT"))return false;if(this.BXIM.messenger.chat[t.chatId]){this.BXIM.messenger.chat[t.chatId].name=t.chatTitle;this.BXIM.messenger.redrawChatHeader()}}else if(e=="chatAvatar"){if(this.MobileActionNotEqual("DIALOG","RECENT"))return false;this.BXIM.messenger.updateChatAvatar(t.chatId,t.chatAvatar)}else if(e=="chatUserAdd"){if(this.MobileActionNotEqual("DIALOG","RECENT"))return false;for(var i in t.users)this.BXIM.messenger.users[i]=t.users[i];if(!this.BXIM.messenger.chat[t.chatId]){this.BXIM.messenger.chat[t.chatId]={id:t.chatId,name:t.chatId,owner:t.chatOwner,fake:true}}else{if(this.BXIM.messenger.userInChat[t.chatId]){for(i=0;i<t.newUsers.length;i++)this.BXIM.messenger.userInChat[t.chatId].push(t.newUsers[i])}else this.BXIM.messenger.userInChat[t.chatId]=t.newUsers;this.BXIM.messenger.redrawChatHeader()}}else if(e=="chatUserLeave"){if(this.MobileActionNotEqual("DIALOG","RECENT"))return false;if(t.userId==this.BXIM.userId){this.readMessage("chat"+t.chatId,true,false);this.leaveFromChat(t.chatId,false);if(t.message.length>0)this.BXIM.openConfirm({title:s.util.htmlspecialchars(t.chatTitle),message:t.message})}else if(this.MobileActionEqual("DIALOG")){if(!this.BXIM.messenger.chat[t.chatId]||!this.BXIM.messenger.userInChat[t.chatId])return false;var f=[];for(var i=0;i<this.BXIM.messenger.userInChat[t.chatId].length;i++)if(this.BXIM.messenger.userInChat[t.chatId][i]!=t.userId)f.push(this.BXIM.messenger.userInChat[t.chatId][i]);this.BXIM.messenger.userInChat[t.chatId]=f;this.BXIM.messenger.redrawChatHeader()}}else if(e=="notify"){if(this.MobileActionNotEqual("NOTIFY"))return false;if(this.BXIM.lastRecordId>=t.id)return false;t.date=parseInt(t.date)+parseInt(s.message("USER_TZ_OFFSET"));var r={};r.UNREAD_NOTIFY={};r.UNREAD_NOTIFY[t.id]=[t.id];this.BXIM.messenger.notify.notify[t.id]=t;this.BXIM.messenger.notify.flashNotify[t.id]=t.silent!="Y";if(t.settingName=="main|rating_vote"&&t.original_tag.substr(0,16)=="RATING|IMMESSAGE"){var c=t.original_tag.substr(17);if(this.BXIM.messenger.message[c]&&this.BXIM.messenger.message[c].recipientId==this.BXIM.messenger.currentTab&&this.BXIM.windowFocus){delete r.UNREAD_NOTIFY[t.id];this.BXIM.notify.flashNotify[t.id]=false;this.BXIM.notify.viewNotify(t.id)}}if(t.silent=="N")this.BXIM.notify.changeUnreadNotify(r.UNREAD_NOTIFY);s.localStorage.set("mfn",this.BXIM.notify.flashNotify,80);this.BXIM.lastRecordId=t.id}else if(e=="readNotify"){if(this.MobileActionNotEqual("NOTIFY"))return false;this.BXIM.notify.initNotifyCount=0;t.lastId=parseInt(t.lastId);for(var i in this.BXIM.notify.unreadNotify){var B=this.BXIM.notify.notify[this.BXIM.notify.unreadNotify[i]];if(B&&B.type!=1&&B.id<=t.lastId){delete this.BXIM.notify.unreadNotify[i]}}this.BXIM.notify.updateNotifyCount(false)}else if(e=="confirmNotify"){if(this.MobileActionNotEqual("NOTIFY"))return false;var X=parseInt(t.id);delete this.BXIM.notify.notify[X];delete this.BXIM.notify.unreadNotify[X];delete this.BXIM.notify.flashNotify[X];this.BXIM.notify.updateNotifyCount(false);if(this.BXIM.messenger.popupMessenger!=null&&this.BXIM.notifyOpen)this.BXIM.notify.openNotify(true)}else if(e=="readNotifyOne"){if(this.MobileActionNotEqual("NOTIFY"))return false;var B=this.BXIM.notify.notify[t.id];if(B&&B.type!=1)delete this.BXIM.notify.unreadNotify[t.id];this.BXIM.notify.updateNotifyCount(false);if(this.BXIM.messenger.popupMessenger!=null&&this.BXIM.notifyOpen)this.BXIM.notify.openNotify(true)}},this));s.addCustomEvent(this.isMobile()?"onPullOnline":"onPullOnlineEvent",s.delegate(function(e,t){if(this.isMobile()){t=e.params;e=e.command}if(e=="user_online"){if(this.BXIM.messenger.users[t.USER_ID]){var r=false;if(typeof this.BXIM.messenger.users[t.USER_ID].idle=="undefined"){this.BXIM.messenger.users[t.USER_ID].idle=0}if(this.BXIM.messenger.users[t.USER_ID].idle!=0){this.BXIM.messenger.users[t.USER_ID].idle=0;r=true}if(typeof t.STATUS!="undefined"){if(this.BXIM.messenger.users[t.USER_ID].status!=t.STATUS){this.BXIM.messenger.users[t.USER_ID].status=t.STATUS;r=true}}if(r){this.BXIM.messenger.dialogStatusRedraw();this.userListRedraw()}}}else if(e=="user_offline"){if(this.BXIM.messenger.users[t.USER_ID]){if(this.BXIM.messenger.users[t.USER_ID].status!="offline"){this.BXIM.messenger.users[t.USER_ID].status="offline";this.BXIM.messenger.users[t.USER_ID].idle=0;this.BXIM.messenger.dialogStatusRedraw();s.MessengerCommon.userListRedraw()}}}else if(e=="user_status"){if(this.BXIM.messenger.users[t.USER_ID]&&t.STATUS){var r=false;if(typeof t.IDLE!="undefined"){if(typeof this.BXIM.messenger.users[t.USER_ID].idle=="undefined"){this.BXIM.messenger.users[t.USER_ID].idle=0}if(this.BXIM.messenger.users[t.USER_ID].idle!=t.IDLE){this.BXIM.messenger.users[t.USER_ID].idle=t.IDLE;r=true}}if(typeof t.STATUS!="undefined"){if(this.BXIM.messenger.users[t.USER_ID].status!=t.STATUS){this.BXIM.messenger.users[t.USER_ID].status=t.STATUS;r=true}}if(r){this.BXIM.messenger.dialogStatusRedraw();s.MessengerCommon.userListRedraw()}}}else if(e=="online_list"){var r=false;for(var i in this.BXIM.messenger.users){if(typeof t.USERS[i]=="undefined"){if(this.BXIM.messenger.users[i].status!="offline"){this.BXIM.messenger.users[i].status="offline";this.BXIM.messenger.users[i].idle=0;r=true}}else{if(typeof t.USERS[i].idle!="undefined"){if(typeof this.BXIM.messenger.users[i].idle=="undefined"){this.BXIM.messenger.users[i].idle=0}if(this.BXIM.messenger.users[i].idle!=t.USERS[i].idle){this.BXIM.messenger.users[i].idle=t.USERS[i].idle;r=true}}if(typeof t.USERS[i].status!="undefined"){if(this.BXIM.messenger.users[i].status!=t.USERS[i].status){this.BXIM.messenger.users[i].status=t.USERS[i].status;r=true}}}}if(r){this.BXIM.messenger.dialogStatusRedraw();s.MessengerCommon.userListRedraw()}}},this))};s.MessengerCommon.prototype.updateStateVar=function(e,t,r){r=r!==false;if(typeof e.CHAT!="undefined"){for(var i in e.CHAT){if(this.BXIM.messenger.chat[i]&&this.BXIM.messenger.chat[i].fake)e.CHAT[i].fake=true;else if(!this.BXIM.messenger.chat[i])e.CHAT[i].fake=true;this.BXIM.messenger.chat[i]=e.CHAT[i]}}if(typeof e.USER_IN_CHAT!="undefined"){for(var i in e.USER_IN_CHAT){this.BXIM.messenger.userInChat[i]=e.USER_IN_CHAT[i]}}if(typeof e.USER_BLOCK_CHAT!="undefined"){for(var i in e.USER_BLOCK_CHAT){this.BXIM.messenger.userChatBlockStatus[i]=e.USER_BLOCK_CHAT[i]}}if(typeof e.USERS!="undefined"){for(var i in e.USERS){this.BXIM.messenger.users[i]=e.USERS[i]}}if(typeof e.USER_IN_GROUP!="undefined"){for(var i in e.USER_IN_GROUP){if(typeof this.BXIM.messenger.userInGroup[i]=="undefined"){this.BXIM.messenger.userInGroup[i]=e.USER_IN_GROUP[i]}else{for(var n=0;n<e.USER_IN_GROUP[i].users.length;n++)this.BXIM.messenger.userInGroup[i].users.push(e.USER_IN_GROUP[i].users[n]);this.BXIM.messenger.userInGroup[i].users=s.util.array_unique(this.BXIM.messenger.userInGroup[i].users)}}}if(typeof e.WO_USER_IN_GROUP!="undefined"){for(var i in e.WO_USER_IN_GROUP){if(typeof this.BXIM.messenger.woUserInGroup[i]=="undefined"){this.BXIM.messenger.woUserInGroup[i]=e.WO_USER_IN_GROUP[i]}else{for(var n=0;n<e.WO_USER_IN_GROUP[i].users.length;n++)this.BXIM.messenger.woUserInGroup[i].users.push(e.WO_USER_IN_GROUP[i].users[n]);this.BXIM.messenger.woUserInGroup[i].users=s.util.array_unique(this.BXIM.messenger.woUserInGroup[i].users)}}}if(typeof e.MESSAGE!="undefined"){for(var i in e.MESSAGE){this.BXIM.messenger.message[i]=e.MESSAGE[i];this.BXIM.lastRecordId=parseInt(i)>this.BXIM.lastRecordId?parseInt(i):this.BXIM.lastRecordId}}this.changeUnreadMessage(e.UNREAD_MESSAGE,t);if(typeof e.USERS_MESSAGE!="undefined"){for(var i in e.USERS_MESSAGE){e.USERS_MESSAGE[i].sort(s.delegate(function(e,s){e=parseInt(e);s=parseInt(s);if(!this.BXIM.messenger.message[e]||!this.BXIM.messenger.message[s]){return 0}var t=parseInt(this.BXIM.messenger.message[e].date);var r=parseInt(this.BXIM.messenger.message[s].date);if(t<r){return-1}else if(t>r){return 1}else{if(e<s){return-1}else if(e>s){return 1}else{return 0}}},this));if(!this.BXIM.messenger.showMessage[i])this.BXIM.messenger.showMessage[i]=e.USERS_MESSAGE[i];for(var n=0;n<e.USERS_MESSAGE[i].length;n++){if(!s.util.in_array(e.USERS_MESSAGE[i][n],this.BXIM.messenger.showMessage[i])){this.BXIM.messenger.showMessage[i].push(e.USERS_MESSAGE[i][n]);if(this.BXIM.messenger.history[i])this.BXIM.messenger.history[i]=s.util.array_merge(this.BXIM.messenger.history[i],e.USERS_MESSAGE[i]);else this.BXIM.messenger.history[i]=e.USERS_MESSAGE[i];if(r&&this.BXIM.messenger.currentTab==i&&this.MobileActionEqual("DIALOG"))this.drawMessage(i,this.BXIM.messenger.message[e.USERS_MESSAGE[i][n]])}}}}};s.MessengerCommon.prototype.changeUnreadMessage=function(e,t){t=t!=false;var r=false;var i=false;var n=true;for(var a in e){var o=false;if(this.BXIM.xmppStatus&&a.toString().substr(0,4)!="chat"){if(!(this.BXIM.messenger.popupMessenger!=null&&this.BXIM.messenger.currentTab==a&&this.BXIM.isFocus())){i=true;if(this.BXIM.messenger.unreadMessage[a])this.BXIM.messenger.unreadMessage[a]=s.util.array_unique(s.util.array_merge(this.BXIM.messenger.unreadMessage[a],e[a]));else this.BXIM.messenger.unreadMessage[a]=e[a]}o=true}if(!o){if(this.BXIM.messenger.popupMessenger!=null&&this.BXIM.messenger.currentTab==a&&this.BXIM.isFocus()){if(typeof this.BXIM.messenger.flashMessage[a]=="undefined")this.BXIM.messenger.flashMessage[a]={};for(var m=0;m<e[a].length;m++){if(this.BXIM.isFocus())this.BXIM.messenger.flashMessage[a][e[a][m]]=false;if(this.BXIM.messenger.message[e[a][m]]&&this.BXIM.messenger.message[e[a][m]].senderId==this.BXIM.messenger.currentTab)r=true}this.readMessage(a,true,true,true)}else if(this.isMobile()&&this.BXIM.messenger.currentTab==a){var g=this.BXIM.messenger.currentTab;this.BXIM.isFocusMobile(s.delegate(function(e){if(e){s.MessengerCommon.readMessage(g,true,true,true)}},this));if(this.BXIM.messenger.unreadMessage[g])this.BXIM.messenger.unreadMessage[g]=s.util.array_unique(s.util.array_merge(this.BXIM.messenger.unreadMessage[g],e[g]));else this.BXIM.messenger.unreadMessage[g]=e[g]}else{i=true;if(this.BXIM.messenger.unreadMessage[a])this.BXIM.messenger.unreadMessage[a]=s.util.array_unique(s.util.array_merge(this.BXIM.messenger.unreadMessage[a],e[a]));else this.BXIM.messenger.unreadMessage[a]=e[a];if(typeof this.BXIM.messenger.flashMessage[a]=="undefined"){this.BXIM.messenger.flashMessage[a]={};for(var m=0;m<e[a].length;m++){var l=this.BXIM.messenger.message[e[a][m]].text.match(new RegExp("("+this.BXIM.messenger.users[this.BXIM.userId].name.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g,"\\$&")+")","ig"));if(this.BXIM.settings.status!="dnd"||l){this.BXIM.messenger.flashMessage[a][e[a][m]]=t}}}else{for(var m=0;m<e[a].length;m++){var l=this.BXIM.messenger.message[e[a][m]].text.match(new RegExp("("+this.BXIM.messenger.users[this.BXIM.userId].name.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g,"\\$&")+")","ig"));if(this.BXIM.settings.status!="dnd"||l){if(!t&&!this.BXIM.isFocus()){this.BXIM.messenger.flashMessage[a][e[a][m]]=false}else{if(typeof this.BXIM.messenger.flashMessage[a][e[a][m]]=="undefined")this.BXIM.messenger.flashMessage[a][e[a][m]]=true}}}}}}var h=false;for(var m=0;m<e[a].length;m++){if(!h||h.SEND_DATE<=parseInt(this.BXIM.messenger.message[e[a][m]].date)+parseInt(s.message("SERVER_TZ_OFFSET"))){h={ID:this.BXIM.messenger.message[e[a][m]].id,SEND_DATE:parseInt(this.BXIM.messenger.message[e[a][m]].date)+parseInt(s.message("SERVER_TZ_OFFSET")),RECIPIENT_ID:this.BXIM.messenger.message[e[a][m]].recipientId,SENDER_ID:this.BXIM.messenger.message[e[a][m]].senderId,USER_ID:this.BXIM.messenger.message[e[a][m]].senderId,SEND_MESSAGE:this.BXIM.messenger.message[e[a][m]].text,PARAMS:this.BXIM.messenger.message[e[a][m]].params}}}if(h){s.MessengerCommon.recentListAdd({userId:h.RECIPIENT_ID.toString().substr(0,4)=="chat"?h.RECIPIENT_ID:h.USER_ID,id:h.ID,date:h.SEND_DATE,recipientId:h.RECIPIENT_ID,senderId:h.SENDER_ID,text:h.SEND_MESSAGE,params:h.PARAMS},true)}if(this.MobileActionEqual("DIALOG")&&this.BXIM.messenger.popupMessenger!=null&&this.BXIM.messenger.currentTab==a){n=true}}if(n){this.BXIM.messenger.dialogStatusRedraw(this.isMobile()?{type:1,slidingPanelRedrawDisable:true}:{})}if(this.MobileActionEqual("RECENT")&&this.BXIM.messenger.popupMessenger!=null&&!this.BXIM.messenger.recentList&&i)s.MessengerCommon.userListRedraw();if(!this.isMobile()){this.BXIM.messenger.newMessage(t);this.BXIM.messenger.updateMessageCount(t);if(t&&r&&this.BXIM.settings.status!="dnd"){this.BXIM.playSound("newMessage2")}}};s.MessengerCommon.prototype.readMessage=function(t,r,i,n){if(!t)return false;n=n==true;if(!n&&(!this.BXIM.messenger.unreadMessage[t]||this.BXIM.messenger.unreadMessage[t].length<=0))return false;r=r!=false;i=i!==false;if(this.BXIM.messenger.popupMessenger!=null){var a=s.findChildrenByClassName(this.BXIM.messenger.popupContactListElementsWrap,"bx-messenger-cl-id-"+t);if(a!=null)for(var o=0;o<a.length;o++)a[o].firstChild.innerHTML="";a=s.findChildrenByClassName(this.BXIM.messenger.popupMessengerBodyWrap,"bx-messenger-content-item-new",false);if(a!=null)for(var o=0;o<a.length;o++)if(a[o].getAttribute("data-notifyType")!=1)s.removeClass(a[o],"bx-messenger-content-item-new")}var m=0;if(Math&&this.BXIM.messenger.unreadMessage[t])m=Math.max.apply(Math,this.BXIM.messenger.unreadMessage[t]);if(this.BXIM.messenger.unreadMessage[t])delete this.BXIM.messenger.unreadMessage[t];if(this.BXIM.messenger.flashMessage[t])delete this.BXIM.messenger.flashMessage[t];s.localStorage.set("mfm",this.BXIM.messenger.flashMessage,80);if(!this.isMobile()){this.BXIM.messenger.updateMessageCount(r)}if(i){clearTimeout(this.BXIM.messenger.readMessageTimeout[t+"_"+this.BXIM.messenger.currentTab]);this.BXIM.messenger.readMessageTimeout[t+"_"+this.BXIM.messenger.currentTab]=setTimeout(s.delegate(function(){var r={IM_READ_MESSAGE:"Y",USER_ID:t,TAB:this.BXIM.messenger.currentTab,IM_AJAX_CALL:"Y",sessid:s.bitrix_sessid()};if(parseInt(m)>0)r["LAST_ID"]=m;var i=s.ajax({url:this.BXIM.pathToAjax+"?READ_MESSAGE&V="+this.BXIM.revision,method:"POST",dataType:"json",timeout:60,skipAuthCheck:true,data:r,onsuccess:s.delegate(function(r){if(r&&r.BITRIX_SESSID){s.message({bitrix_sessid:r.BITRIX_SESSID})}if(r.ERROR!=""){if(r.ERROR=="SESSION_ERROR"&&this.BXIM.messenger.sendAjaxTry<2){this.BXIM.messenger.sendAjaxTry++;setTimeout(s.delegate(function(){this.readMessage(t,false,true)},this),2e3);s.onCustomEvent(e,"onImError",[r.ERROR,r.BITRIX_SESSID])}else if(r.ERROR=="AUTHORIZE_ERROR"){this.BXIM.messenger.sendAjaxTry++;if(this.BXIM.desktop&&this.BXIM.desktop.ready()){setTimeout(s.delegate(function(){this.readMessage(t,false,true)},this),1e4)}s.onCustomEvent(e,"onImError",[r.ERROR])}}},this),onfailure:s.delegate(function(){this.BXIM.messenger.sendAjaxTry=0;try{if(typeof i=="object"&&i.status==0)s.onCustomEvent(e,"onImError",["CONNECT_ERROR"])}catch(t){}},this)})},this),200)}if(r){s.localStorage.set("mrm",t,5);s.localStorage.set("mnnb",true,1)}};s.MessengerCommon.prototype.drawReadMessage=function(e,t,r,i){var n=Math.max.apply(Math,this.BXIM.messenger.showMessage[e]);if(n!=t||this.BXIM.messenger.message[n].senderId==e){this.BXIM.messenger.readedList[e]=false;return false}this.BXIM.messenger.readedList[e]={messageId:t,date:r};if(!this.countWriting(e)){i=i!=false;this.drawNotifyMessage(e,"readed",s.message("IM_M_READED").replace("#DATE#",this.formatDate(r)),i)}};s.MessengerCommon.prototype.drawNotifyMessage=function(e,t,r,i){if(this.BXIM.messenger.popupMessenger==null||e!=this.BXIM.messenger.currentTab||typeof r=="undefined"||typeof t=="undefined"||e==0)return false;var n=this.BXIM.messenger.popupMessengerBodyWrap.lastChild;if(s.hasClass(n,"bx-messenger-content-empty"))return false;var a=s.create("div",{attrs:{"data-type":"notify"},props:{className:"bx-messenger-content-item bx-messenger-content-item-notify"},children:[s.create("span",{props:{className:"bx-messenger-content-item-content"},children:[s.create("span",{props:{className:"bx-messenger-content-item-text-center"},children:[s.create("span",{props:{className:"bx-messenger-content-item-text-message"},html:'<span class="bx-messenger-content-item-notify-icon-'+t+'"></span>'+this.prepareText(r,false,true,true)})]})]})]});if(s.hasClass(n,"bx-messenger-content-item-notify"))s.remove(n);this.BXIM.messenger.popupMessengerBodyWrap.appendChild(a);i=i!=false;if(this.BXIM.messenger.popupMessengerBody&&s.MessengerCommon.enableScroll(this.BXIM.messenger.popupMessengerBody,this.BXIM.messenger.popupMessengerBody.offsetHeight)){if(this.BXIM.animationSupport&&i){if(this.BXIM.messenger.popupMessengerBodyAnimation!=null)this.BXIM.messenger.popupMessengerBodyAnimation.stop();(this.BXIM.messenger.popupMessengerBodyAnimation=new s.easing({duration:1200,start:{scroll:this.BXIM.messenger.popupMessengerBody.scrollTop},finish:{scroll:this.BXIM.messenger.popupMessengerBody.scrollHeight-this.BXIM.messenger.popupMessengerBody.offsetHeight*(this.isMobile()?0:1)},transition:s.easing.makeEaseInOut(s.easing.transitions.quart),step:s.delegate(function(e){this.BXIM.messenger.popupMessengerBody.scrollTop=e.scroll},this)})).animate()}else{this.BXIM.messenger.popupMessengerBody.scrollTop=this.BXIM.messenger.popupMessengerBody.scrollHeight-this.BXIM.messenger.popupMessengerBody.offsetHeight*(this.isMobile()?0:1)}}};s.MessengerCommon.prototype.loadHistory=function(e,t){t=typeof t=="undefined"?true:t;if(!this.BXIM.messenger.historyEndOfList[e])this.BXIM.messenger.historyEndOfList[e]={};if(!this.BXIM.messenger.historyLoadFlag[e])this.BXIM.messenger.historyLoadFlag[e]={};if(this.BXIM.messenger.historyLoadFlag[e]&&this.BXIM.messenger.historyLoadFlag[e][t]){if(this.isMobile())app.pullDownLoadingStop();return}if(this.isMobile()){t=false}else{if(t){if(this.BXIM.messenger.historySearch!=""||this.BXIM.messenger.historyDateSearch!="")return;if(!(this.BXIM.messenger.popupHistoryItems.scrollTop>this.BXIM.messenger.popupHistoryItems.scrollHeight-this.BXIM.messenger.popupHistoryItems.offsetHeight-100))return}else{if(this.BXIM.messenger.popupMessengerBody.scrollTop>=5)return}}if(!this.BXIM.messenger.historyEndOfList[e]||!this.BXIM.messenger.historyEndOfList[e][t]){var r=[];if(t){r=s.findChildrenByClassName(this.BXIM.messenger.popupHistoryBodyWrap,"bx-messenger-history-item-text")}else{r=s.findChildrenByClassName(this.BXIM.messenger.popupMessengerBodyWrap,"bx-messenger-content-item-text-wrap")}if(!this.isMobile()&&r.length<20){return false}if(r.length>0)this.BXIM.messenger.historyOpenPage[e]=Math.floor(r.length/20)+1;else this.BXIM.messenger.historyOpenPage[e]=1;var i=null;if(!this.isMobile()){i=s.create("div",{props:{className:"bx-messenger-content-load-more-history"},children:[s.create("span",{props:{className:"bx-messenger-content-load-img"}}),s.create("span",{props:{className:"bx-messenger-content-load-text"},html:s.message("IM_M_LOAD_MESSAGE")})]});if(t){this.BXIM.messenger.popupHistoryBodyWrap.appendChild(i)}else{this.BXIM.messenger.popupMessengerBodyWrap.insertBefore(i,this.BXIM.messenger.popupMessengerBodyWrap.firstChild)}}if(!this.BXIM.messenger.historyLoadFlag[e])this.BXIM.messenger.historyLoadFlag[e]={};this.BXIM.messenger.historyLoadFlag[e][t]=true;s.ajax({url:this.BXIM.pathToAjax+"?HISTORY_LOAD_MORE&V="+this.BXIM.revision,method:"POST",dataType:"json",timeout:30,data:{IM_HISTORY_LOAD_MORE:"Y",USER_ID:e,PAGE_ID:this.BXIM.messenger.historyOpenPage[e],IM_AJAX_CALL:"Y",sessid:s.bitrix_sessid()},onsuccess:s.delegate(function(r){if(i)s.remove(i);if(this.isMobile())app.pullDownLoadingStop();this.BXIM.messenger.historyLoadFlag[e][t]=false;if(r.MESSAGE.length==0){this.BXIM.messenger.historyEndOfList[e][t]=true;return}for(var n in r.FILES){if(!this.BXIM.disk.files[r.CHAT_ID])this.BXIM.disk.files[r.CHAT_ID]={};if(this.BXIM.disk.files[r.CHAT_ID][n])continue;r.FILES[n].date=parseInt(r.FILES[n].date)+parseInt(s.message("USER_TZ_OFFSET"));this.BXIM.disk.files[r.CHAT_ID][n]=r.FILES[n]}var a=0;for(var n in r.MESSAGE){r.MESSAGE[n].date=parseInt(r.MESSAGE[n].date)+parseInt(s.message("USER_TZ_OFFSET"));this.BXIM.messenger.message[n]=r.MESSAGE[n];a++}if(a<20){this.BXIM.messenger.historyEndOfList[e][t]=true}for(var n in r.USERS_MESSAGE){if(t){if(this.BXIM.messenger.history[n])this.BXIM.messenger.history[n]=s.util.array_merge(this.BXIM.messenger.history[n],r.USERS_MESSAGE[n]);else this.BXIM.messenger.history[n]=r.USERS_MESSAGE[n]}else{if(this.BXIM.messenger.showMessage[n])this.BXIM.messenger.showMessage[n]=s.util.array_unique(s.util.array_merge(r.USERS_MESSAGE[n],this.BXIM.messenger.showMessage[n]));else this.BXIM.messenger.showMessage[n]=r.USERS_MESSAGE[n]}}if(t){for(var n=0;n<r.USERS_MESSAGE[e].length;n++){var o=this.BXIM.messenger.message[r.USERS_MESSAGE[e][n]];if(o){if(s("im-message-history-"+o.id))continue;var m=s.MessengerCommon.formatDate(o.date,s.MessengerCommon.getDateFormatType("MESSAGE_TITLE"));if(!s("bx-im-history-"+m)){var g=s.create("div",{props:{className:"bx-messenger-content-group bx-messenger-content-group-history"},children:[s.create("div",{attrs:{id:"bx-im-history-"+m},props:{className:"bx-messenger-content-group-title"+(this.BXIM.language=="ru"?" bx-messenger-lowercase":"")},html:m})]});this.BXIM.messenger.popupHistoryBodyWrap.appendChild(g)}var o=this.BXIM.messenger.drawMessageHistory(o);if(o)this.BXIM.messenger.popupHistoryBodyWrap.appendChild(o)}}}else{var l=this.BXIM.messenger.popupMessengerBodyWrap.firstChild.nextSibling;l=s("im-message-"+l.getAttribute("data-blockmessageid"));for(var n=0;n<r.USERS_MESSAGE[e].length;n++){var o=this.BXIM.messenger.message[r.USERS_MESSAGE[e][n]];if(o){if(s("im-message-"+o.id))continue;s.MessengerCommon.drawMessage(e,o,false,true)}}this.BXIM.messenger.popupMessengerBody.scrollTop=l.offsetTop-this.BXIM.messenger.popupMessengerBody.offsetTop-l.offsetHeight-100}},this),onfailure:s.delegate(function(){if(i)s.remove(i);if(this.isMobile())app.pullDownLoadingStop()},this)})}};s.MessengerCommon.prototype.loadUserData=function(e){s.ajax({url:this.BXIM.pathToAjax+"?USER_DATA_LOAD&V="+this.BXIM.revision,method:"POST",dataType:"json",timeout:30,data:{IM_USER_DATA_LOAD:"Y",USER_ID:e,IM_AJAX_CALL:"Y",sessid:s.bitrix_sessid()},onsuccess:s.delegate(function(t){if(t.ERROR==""){this.BXIM.messenger.userChat[e]=t.CHAT_ID;s.MessengerCommon.getUserParam(e,true);this.BXIM.messenger.users[e].name=s.message("IM_M_USER_NO_ACCESS");for(var r in t.USERS){this.BXIM.messenger.users[r]=t.USERS[r]}for(var r in t.PHONES){this.BXIM.messenger.phones[r]={};for(var i in t.PHONES[r]){this.BXIM.messenger.phones[r][i]=s.util.htmlspecialcharsback(t.PHONES[r][i])}}for(var r in t.USER_IN_GROUP){if(typeof this.BXIM.messenger.userInGroup[r]=="undefined"){this.BXIM.messenger.userInGroup[r]=t.USER_IN_GROUP[r]}else{for(var i=0;i<t.USER_IN_GROUP[r].users.length;i++)this.BXIM.messenger.userInGroup[r].users.push(t.USER_IN_GROUP[r].users[i]);this.BXIM.messenger.userInGroup[r].users=s.util.array_unique(this.BXIM.messenger.userInGroup[r].users)}}for(var r in t.WO_USER_IN_GROUP){if(typeof this.BXIM.messenger.woUserInGroup[r]=="undefined"){this.BXIM.messenger.woUserInGroup[r]=t.WO_USER_IN_GROUP[r]}else{for(var i=0;i<t.WO_USER_IN_GROUP[r].users.length;i++)this.BXIM.messenger.woUserInGroup[r].users.push(t.WO_USER_IN_GROUP[r].users[i]);this.BXIM.messenger.woUserInGroup[r].users=s.util.array_unique(this.BXIM.messenger.woUserInGroup[r].users);

}}this.BXIM.messenger.dialogStatusRedraw()}else{this.BXIM.messenger.redrawTab[e]=true;if(t.ERROR=="ACCESS_DENIED"){this.BXIM.messenger.currentTab=0;this.BXIM.messenger.openChatFlag=false;this.BXIM.messenger.openCallFlag=false;this.BXIM.messenger.extraClose()}}},this)})};s.MessengerCommon.prototype.loadChatData=function(e){s.ajax({url:this.BXIM.pathToAjax+"?CHAT_DATA_LOAD&V="+this.BXIM.revision,method:"POST",dataType:"json",timeout:30,data:{IM_CHAT_DATA_LOAD:"Y",CHAT_ID:e,IM_AJAX_CALL:"Y",sessid:s.bitrix_sessid()},onsuccess:s.delegate(function(e){if(e.ERROR==""){if(this.BXIM.messenger.chat[e.CHAT_ID].fake){this.BXIM.messenger.chat[e.CHAT_ID].name=s.message("IM_M_USER_NO_ACCESS")}for(var t in e.CHAT){this.BXIM.messenger.chat[t]=e.CHAT[t]}for(var t in e.USER_IN_CHAT){this.BXIM.messenger.userInChat[t]=e.USER_IN_CHAT[t]}for(var t in e.USER_BLOCK_CHAT){this.BXIM.messenger.userChatBlockStatus[t]=e.USER_BLOCK_CHAT[t]}if(this.BXIM.messenger.currentTab=="chat"+e.CHAT_ID){if(this.BXIM.messenger.chat[e.CHAT_ID]&&this.BXIM.messenger.chat[e.CHAT_ID].style=="call"){this.BXIM.messenger.openCallFlag=true}}this.BXIM.messenger.dialogStatusRedraw()}},this)})};s.MessengerCommon.prototype.loadLastMessage=function(t,r){if(this.BXIM.messenger.loadLastMessageTimeout[t])return false;this.BXIM.messenger.historyWindowBlock=true;delete this.BXIM.messenger.redrawTab[t];this.BXIM.messenger.loadLastMessageTimeout[t]=true;s.ajax({url:this.BXIM.pathToAjax+"?LOAD_LAST_MESSAGE&V="+this.BXIM.revision,method:"POST",dataType:"json",skipAuthCheck:true,timeout:90,data:{IM_LOAD_LAST_MESSAGE:"Y",CHAT:r?"Y":"N",USER_ID:t,USER_LOAD:r?this.BXIM.messenger.chat[t.toString().substr(4)]&&this.BXIM.messenger.chat[t.toString().substr(4)].fake?"Y":"N":"Y",TAB:this.BXIM.messenger.currentTab,READ:this.BXIM.messenger.BXIM.isFocus()?"Y":"N",MOBILE:this.isMobile()?"Y":"N",IM_AJAX_CALL:"Y",sessid:s.bitrix_sessid()},onsuccess:s.delegate(function(i){this.BXIM.messenger.loadLastMessageTimeout[t]=false;if(!i)return false;if(i&&i.BITRIX_SESSID){s.message({bitrix_sessid:i.BITRIX_SESSID})}if(i.ERROR==""){if(!r){this.BXIM.messenger.userChat[t]=i.CHAT_ID;s.MessengerCommon.getUserParam(t,true);this.BXIM.messenger.users[t].name=s.message("IM_M_USER_NO_ACCESS")}for(var n in i.USERS){this.BXIM.messenger.users[n]=i.USERS[n]}for(var n in i.PHONES){this.BXIM.messenger.phones[n]={};for(var a in i.PHONES[n]){this.BXIM.messenger.phones[n][a]=s.util.htmlspecialcharsback(i.PHONES[n][a])}}for(var n in i.USER_IN_GROUP){if(typeof this.BXIM.messenger.userInGroup[n]=="undefined"){this.BXIM.messenger.userInGroup[n]=i.USER_IN_GROUP[n]}else{for(var a=0;a<i.USER_IN_GROUP[n].users.length;a++)this.BXIM.messenger.userInGroup[n].users.push(i.USER_IN_GROUP[n].users[a]);this.BXIM.messenger.userInGroup[n].users=s.util.array_unique(this.BXIM.messenger.userInGroup[n].users)}}for(var n in i.WO_USER_IN_GROUP){if(typeof this.BXIM.messenger.woUserInGroup[n]=="undefined"){this.BXIM.messenger.woUserInGroup[n]=i.WO_USER_IN_GROUP[n]}else{for(var a=0;a<i.WO_USER_IN_GROUP[n].users.length;a++)this.BXIM.messenger.woUserInGroup[n].users.push(i.WO_USER_IN_GROUP[n].users[a]);this.BXIM.messenger.woUserInGroup[n].users=s.util.array_unique(this.BXIM.messenger.woUserInGroup[n].users)}}for(var n in i.READED_LIST){i.READED_LIST[n].date=parseInt(i.READED_LIST[n].date)+parseInt(s.message("USER_TZ_OFFSET"));this.BXIM.messenger.readedList[n]=i.READED_LIST[n]}if(!r&&i.USER_LOAD=="Y")s.MessengerCommon.userListRedraw();for(var n in i.FILES){if(!this.BXIM.messenger.disk.files[i.CHAT_ID])this.BXIM.messenger.disk.files[i.CHAT_ID]={};i.FILES[n].date=parseInt(i.FILES[n].date)+parseInt(s.message("USER_TZ_OFFSET"));this.BXIM.messenger.disk.files[i.CHAT_ID][n]=i.FILES[n]}this.BXIM.messenger.sendAjaxTry=0;var o=0;for(var n in i.MESSAGE){o++;i.MESSAGE[n].date=parseInt(i.MESSAGE[n].date)+parseInt(s.message("USER_TZ_OFFSET"));this.BXIM.messenger.message[n]=i.MESSAGE[n];this.BXIM.lastRecordId=parseInt(n)>this.BXIM.lastRecordId?parseInt(n):this.BXIM.lastRecordId}if(o<=0)delete this.BXIM.messenger.redrawTab[i.USER_ID];for(var n in i.USERS_MESSAGE){if(this.BXIM.messenger.showMessage[n])this.BXIM.messenger.showMessage[n]=s.util.array_unique(s.util.array_merge(i.USERS_MESSAGE[n],this.BXIM.messenger.showMessage[n]));else this.BXIM.messenger.showMessage[n]=i.USERS_MESSAGE[n]}if(r&&this.BXIM.messenger.chat[i.USER_ID.substr(4)].fake){this.BXIM.messenger.chat[i.USER_ID.toString().substr(4)].name=s.message("IM_M_USER_NO_ACCESS")}for(var n in i.CHAT){this.BXIM.messenger.chat[n]=i.CHAT[n]}for(var n in i.USER_IN_CHAT){this.BXIM.messenger.userInChat[n]=i.USER_IN_CHAT[n]}for(var n in i.USER_BLOCK_CHAT){this.BXIM.messenger.userChatBlockStatus[n]=i.USER_BLOCK_CHAT[n]}if(this.BXIM.messenger.currentTab==i.USER_ID){if(this.BXIM.messenger.currentTab.toString().substr(0,4)=="chat"&&this.BXIM.messenger.chat[this.BXIM.messenger.currentTab.toString().substr(4)]&&this.BXIM.messenger.chat[this.BXIM.messenger.currentTab.toString().substr(4)].style=="call"){this.BXIM.messenger.openCallFlag=true}}s.MessengerCommon.drawTab(i.USER_ID,this.BXIM.messenger.currentTab==i.USER_ID);if(this.BXIM.messenger.currentTab==i.USER_ID&&this.BXIM.messenger.readedList[i.USER_ID])s.MessengerCommon.drawReadMessage(i.USER_ID,this.BXIM.messenger.readedList[i.USER_ID].messageId,this.BXIM.messenger.readedList[i.USER_ID].date,false);this.BXIM.messenger.historyWindowBlock=false;if(this.BXIM.isFocus()){s.MessengerCommon.readMessage(i.USER_ID,true,false)}}else{this.BXIM.messenger.redrawTab[t]=true;if(i.ERROR=="ACCESS_DENIED"){this.BXIM.messenger.currentTab=0;this.BXIM.messenger.openChatFlag=false;this.BXIM.messenger.openCallFlag=false;this.BXIM.messenger.extraClose()}else if(i.ERROR=="SESSION_ERROR"&&this.BXIM.messenger.sendAjaxTry<2){this.BXIM.messenger.sendAjaxTry++;setTimeout(s.delegate(function(){this.loadLastMessage(t,r)},this),2e3);s.onCustomEvent(e,"onImError",[i.ERROR,i.BITRIX_SESSID])}else if(i.ERROR=="AUTHORIZE_ERROR"){this.BXIM.messenger.sendAjaxTry++;if(this.BXIM.desktop&&this.BXIM.desktop.ready()){setTimeout(s.delegate(function(){this.loadLastMessage(t,r)},this),1e4)}s.onCustomEvent(e,"onImError",[i.ERROR])}}},this),onfailure:s.delegate(function(){this.BXIM.messenger.loadLastMessageTimeout[t]=false;this.BXIM.messenger.historyWindowBlock=false;this.BXIM.messenger.sendAjaxTry=0;this.BXIM.messenger.redrawTab[t]=true},this)})};s.MessengerCommon.prototype.openDialog=function(e,t,r){var i=s.MessengerCommon.getUserParam(e);if(i.id<=0)return false;this.BXIM.messenger.currentTab=e;if(e.toString().substr(0,4)=="chat"){this.BXIM.messenger.openChatFlag=true;if(this.BXIM.messenger.chat[e.toString().substr(4)]&&this.BXIM.messenger.chat[e.toString().substr(4)].style=="call")this.BXIM.messenger.openCallFlag=true}s.localStorage.set("mct",this.BXIM.messenger.currentTab,15);this.BXIM.messenger.dialogStatusRedraw();if(!this.isMobile()){this.BXIM.messenger.popupMessengerPanel.className=this.BXIM.messenger.openChatFlag?"bx-messenger-panel bx-messenger-hide":"bx-messenger-panel";if(this.BXIM.messenger.openChatFlag){this.BXIM.messenger.popupMessengerPanel2.className=this.BXIM.messenger.openCallFlag?"bx-messenger-panel bx-messenger-hide":"bx-messenger-panel";this.BXIM.messenger.popupMessengerPanel3.className=this.BXIM.messenger.openCallFlag?"bx-messenger-panel":"bx-messenger-panel bx-messenger-hide"}else{this.BXIM.messenger.popupMessengerPanel2.className="bx-messenger-panel bx-messenger-hide";this.BXIM.messenger.popupMessengerPanel3.className="bx-messenger-panel bx-messenger-hide"}}t=t==true;r=r!=false;var n=[];if(typeof this.BXIM.messenger.showMessage[e]!="undefined"&&this.BXIM.messenger.showMessage[e].length>0){if(!i.fake&&this.BXIM.messenger.showMessage[e].length>=15){this.BXIM.messenger.redrawTab[e]=false}else{this.drawTab(e,true);this.BXIM.messenger.redrawTab[e]=true}}else if(this.BXIM.messenger.popupMessengerConnectionStatusState!="online"){n=[s.create("div",{props:{className:"bx-messenger-content-empty"},children:[s.create("span",{props:{className:"bx-messenger-content-load-text"},html:s.message("IM_M_LOAD_ERROR")})]})];this.BXIM.messenger.redrawTab[e]=true}else if(typeof this.BXIM.messenger.showMessage[e]=="undefined"){n=[s.create("div",{props:{className:"bx-messenger-content-load"},children:[s.create("span",{props:{className:"bx-messenger-content-load-img"}}),s.create("span",{props:{className:"bx-messenger-content-load-text"},html:s.message("IM_M_LOAD_MESSAGE")})]})];this.BXIM.messenger.redrawTab[e]=true}else if(this.BXIM.messenger.redrawTab[e]&&this.BXIM.messenger.showMessage[e].length==0){n=[s.create("div",{props:{className:"bx-messenger-content-load"},children:[s.create("span",{props:{className:"bx-messenger-content-load-img"}}),s.create("span",{props:{className:"bx-messenger-content-load-text"},html:s.message("IM_M_LOAD_MESSAGE")})]})];this.BXIM.messenger.showMessage[e]=[]}else{n=[s.create("div",{props:{className:"bx-messenger-content-empty"},children:[s.create("span",{props:{className:"bx-messenger-content-load-text"},html:s.message(this.BXIM.settings.loadLastMessage?"IM_M_NO_MESSAGE_2":"IM_M_NO_MESSAGE")})]})]}if(n.length>0){this.BXIM.messenger.popupMessengerBodyWrap.innerHTML="";s.adjust(this.BXIM.messenger.popupMessengerBodyWrap,{children:n})}if(t)this.BXIM.messenger.extraClose();if(this.isMobile()){BXMobileApp.UI.Page.TextPanel.setText(this.BXIM.messenger.textareaHistory[e]?this.BXIM.messenger.textareaHistory[e]:"")}else{this.BXIM.messenger.popupMessengerTextarea.value=this.BXIM.messenger.textareaHistory[e]?this.BXIM.messenger.textareaHistory[e]:""}if(this.BXIM.messenger.redrawTab[e]){if(this.BXIM.settings.loadLastMessage){this.loadLastMessage(e,this.BXIM.messenger.openChatFlag)}else{if(this.BXIM.messenger.openChatFlag)s.MessengerCommon.loadChatData(e.toString().substr(4));else s.MessengerCommon.loadUserData(e);delete this.BXIM.messenger.redrawTab[e];this.drawTab(e,true)}}else{this.drawTab(e,true)}if(!this.BXIM.messenger.redrawTab[e]){if(this.isMobile()){this.BXIM.isFocusMobile(s.delegate(function(t){if(t){s.MessengerCommon.readMessage(e)}},this))}else if(this.BXIM.isFocus()){this.readMessage(e)}}if(!this.isMobile())this.BXIM.messenger.resizeMainWindow();if(s.MessengerCommon.countWriting(e)){if(this.BXIM.messenger.openChatFlag)s.MessengerCommon.drawWriting(0,e);else s.MessengerCommon.drawWriting(e)}else if(this.BXIM.messenger.readedList[e]){this.drawReadMessage(e,this.BXIM.messenger.readedList[e].messageId,this.BXIM.messenger.readedList[e].date,false)}if(!this.isMobile()&&r)this.BXIM.webrtc.callOverlayToggleSize(true);s.onCustomEvent("onImDialogOpen",[{id:e}]);if(this.isMobile()){app.onCustomEvent("onImDialogOpen",{id:e})}};s.MessengerCommon.prototype.drawTab=function(e,t){if(this.BXIM.messenger.popupMessenger==null||e!=this.BXIM.messenger.currentTab)return false;this.BXIM.messenger.dialogStatusRedraw();this.BXIM.messenger.popupMessengerBodyWrap.innerHTML="";if(!this.BXIM.messenger.showMessage[e]||this.BXIM.messenger.showMessage[e].length<=0){this.BXIM.messenger.popupMessengerBodyWrap.appendChild(s.create("div",{props:{className:"bx-messenger-content-empty"},children:[s.create("span",{props:{className:"bx-messenger-content-load-text"},html:s.message(this.BXIM.settings.loadLastMessage?"IM_M_NO_MESSAGE_2":"IM_M_NO_MESSAGE")})]}))}if(this.BXIM.messenger.showMessage[e])this.BXIM.messenger.showMessage[e].sort(s.delegate(function(e,s){if(!this.BXIM.messenger.message[e]||!this.BXIM.messenger.message[s]){return 0}var t=parseInt(this.BXIM.messenger.message[e].date);var r=parseInt(this.BXIM.messenger.message[s].date);if(t<r){return-1}else if(t>r){return 1}else{if(e<s){return-1}else if(e>s){return 1}else{return 0}}},this));else this.BXIM.messenger.showMessage[e]=[];for(var r=0;r<this.BXIM.messenger.showMessage[e].length;r++)s.MessengerCommon.drawMessage(e,this.BXIM.messenger.message[this.BXIM.messenger.showMessage[e][r]],false);t=t!=false;if(t){if(this.BXIM.messenger.popupMessengerBodyAnimation!=null)this.BXIM.messenger.popupMessengerBodyAnimation.stop();if(this.BXIM.messenger.unreadMessage[e]&&this.BXIM.messenger.unreadMessage[e].length>0){var i=s("im-message-"+this.BXIM.messenger.unreadMessage[e][0]);if(i)this.BXIM.messenger.popupMessengerBody.scrollTop=i.offsetTop-60-this.BXIM.messenger.popupMessengerBodyWrap.offsetTop;else this.BXIM.messenger.popupMessengerBody.scrollTop=this.BXIM.messenger.popupMessengerBody.scrollHeight-this.BXIM.messenger.popupMessengerBody.offsetHeight*(this.isMobile()?0:1)}else{this.BXIM.messenger.popupMessengerBody.scrollTop=this.BXIM.messenger.popupMessengerBody.scrollHeight-this.BXIM.messenger.popupMessengerBody.offsetHeight*(this.isMobile()?0:1)}}delete this.BXIM.messenger.redrawTab[e]};s.MessengerCommon.prototype.sendMessageAjax=function(t,r,i,n){if(this.BXIM.messenger.popupMessengerConnectionStatusState!="online")return false;s.MessengerCommon.drawProgessMessage("temp"+t);if(this.BXIM.messenger.sendMessageFlag<0)this.BXIM.messenger.sendMessageFlag=0;clearTimeout(this.BXIM.messenger.sendMessageTmpTimeout["temp"+t]);if(this.BXIM.messenger.sendMessageTmp[t])return false;this.BXIM.messenger.sendMessageTmp[t]=true;n=n==true;this.BXIM.messenger.sendMessageFlag++;s.MessengerCommon.recentListAdd({id:"temp"+t,date:s.MessengerCommon.getNowDate()+parseInt(s.message("SERVER_TZ_OFFSET")),skipDateCheck:true,recipientId:r,senderId:this.BXIM.userId,text:s.MessengerCommon.prepareText(i,true),userId:r,params:{}},true);var a=s.ajax({url:this.BXIM.pathToAjax+"?MESSAGE_SEND&V="+this.BXIM.revision,method:"POST",dataType:"json",skipAuthCheck:true,timeout:60,data:{IM_SEND_MESSAGE:"Y",CHAT:n?"Y":"N",ID:"temp"+t,RECIPIENT_ID:r,MESSAGE:i,TAB:this.BXIM.messenger.currentTab,USER_TZ_OFFSET:s.message("USER_TZ_OFFSET"),IM_AJAX_CALL:"Y",sessid:s.bitrix_sessid()},onsuccess:s.delegate(function(a){this.BXIM.messenger.sendMessageFlag--;if(a&&a.BITRIX_SESSID){s.message({bitrix_sessid:a.BITRIX_SESSID})}if(a.ERROR==""){this.BXIM.messenger.sendAjaxTry=0;this.BXIM.messenger.message[a.TMP_ID].text=a.SEND_MESSAGE;this.BXIM.messenger.message[a.TMP_ID].date=parseInt(a.SEND_DATE);this.BXIM.messenger.message[a.TMP_ID].id=a.ID;this.BXIM.messenger.message[a.ID]=this.BXIM.messenger.message[a.TMP_ID];if(this.BXIM.messenger.popupMessengerLastMessage==a.TMP_ID)this.BXIM.messenger.popupMessengerLastMessage=a.ID;delete this.BXIM.messenger.message[a.TMP_ID];var o=this.BXIM.messenger.message[a.ID];var m=s.util.array_search(""+a.TMP_ID+"",this.BXIM.messenger.showMessage[a.RECIPIENT_ID]);if(this.BXIM.messenger.showMessage[a.RECIPIENT_ID][m])this.BXIM.messenger.showMessage[a.RECIPIENT_ID][m]=""+a.ID+"";for(var g=0;g<this.BXIM.messenger.recent.length;g++){if(this.BXIM.messenger.recent[g].id==a.TMP_ID){this.BXIM.messenger.recent[g].id=""+a.ID+"";break}}if(a.RECIPIENT_ID==this.BXIM.messenger.currentTab){var l=s.findChild(this.BXIM.messenger.popupMessengerBodyWrap,{attribute:{"data-messageid":""+a.TMP_ID+""}},true);if(l){l.setAttribute("data-messageid",""+a.ID+"");if(l.getAttribute("data-blockmessageid")==""+a.TMP_ID+""){l.setAttribute("data-blockmessageid",""+a.ID+"")}else{var h=s.findChild(this.BXIM.messenger.popupMessengerBodyWrap,{attribute:{"data-blockmessageid":""+a.TMP_ID+""}},true);if(h){h.setAttribute("data-blockmessageid",""+a.ID+"")}}}var I=s("im-message-"+a.TMP_ID);if(I){I.id="im-message-"+a.ID;I.innerHTML=s.MessengerCommon.prepareText(a.SEND_MESSAGE,false,true,true)}var M=this.BXIM.messenger.users[o.senderId];var p=s.findChildByClassName(l,"bx-messenger-content-item-date");if(p)p.innerHTML=" &nbsp; "+s.MessengerCommon.formatDate(o.date,s.MessengerCommon.getDateFormatType("MESSAGE"));s.MessengerCommon.clearProgessMessage(a.ID)}if(this.BXIM.messenger.history[a.RECIPIENT_ID])this.BXIM.messenger.history[a.RECIPIENT_ID].push(o.id);else this.BXIM.messenger.history[a.RECIPIENT_ID]=[o.id];this.BXIM.messenger.updateStateVeryFastCount=2;this.BXIM.messenger.updateStateFastCount=5;this.BXIM.messenger.setUpdateStateStep();if(s.PULL){s.PULL.setUpdateStateStepCount(2,5)}s.MessengerCommon.updateStateVar(a,true,true);s.localStorage.set("msm",{id:a.ID,recipientId:a.RECIPIENT_ID,date:a.SEND_DATE,text:a.SEND_MESSAGE,senderId:this.BXIM.userId,MESSAGE:a.MESSAGE,USERS_MESSAGE:a.USERS_MESSAGE,USERS:a.USERS,USER_IN_GROUP:a.USER_IN_GROUP,WO_USER_IN_GROUP:a.WO_USER_IN_GROUP},5);if(this.BXIM.animationSupport){if(this.BXIM.messenger.popupMessengerBodyAnimation!=null)this.BXIM.messenger.popupMessengerBodyAnimation.stop();(this.BXIM.messenger.popupMessengerBodyAnimation=new s.easing({duration:800,start:{scroll:this.BXIM.messenger.popupMessengerBody.scrollTop},finish:{scroll:this.BXIM.messenger.popupMessengerBody.scrollHeight-this.BXIM.messenger.popupMessengerBody.offsetHeight*(this.isMobile()?0:1)},transition:s.easing.makeEaseInOut(s.easing.transitions.quart),step:s.delegate(function(e){this.BXIM.messenger.popupMessengerBody.scrollTop=e.scroll},this)})).animate()}else{this.BXIM.messenger.popupMessengerBody.scrollTop=this.BXIM.messenger.popupMessengerBody.scrollHeight-this.BXIM.messenger.popupMessengerBody.offsetHeight*(this.isMobile()?0:1)}if(!this.MobileActionEqual("RECENT")&&this.BXIM.messenger.recentList)s.MessengerCommon.recentListRedraw()}else{if(a.ERROR=="SESSION_ERROR"&&this.BXIM.messenger.sendAjaxTry<2){this.BXIM.messenger.sendAjaxTry++;setTimeout(s.delegate(function(){this.BXIM.messenger.sendMessageTmp[t]=false;this.sendMessageAjax(t,r,i,n)},this),2e3);s.onCustomEvent(e,"onImError",[a.ERROR,a.BITRIX_SESSID])}else if(a.ERROR=="AUTHORIZE_ERROR"){this.BXIM.messenger.sendAjaxTry++;if(this.BXIM.desktop&&this.BXIM.desktop.ready()){setTimeout(s.delegate(function(){this.BXIM.messenger.sendMessageTmp[t]=false;this.sendMessageAjax(t,r,i,n)},this),1e4)}s.onCustomEvent(e,"onImError",[a.ERROR])}else{this.BXIM.messenger.sendMessageTmp[t]=false;var l=s.findChild(this.BXIM.messenger.popupMessengerBodyWrap,{attribute:{"data-messageid":"temp"+t}},true);var p=s.findChildByClassName(l,"bx-messenger-content-item-date");if(p){if(a.ERROR=="SESSION_ERROR"||a.ERROR=="AUTHORIZE_ERROR"||a.ERROR=="UNKNOWN_ERROR"||a.ERROR=="IM_MODULE_NOT_INSTALLED")p.innerHTML=s.message("IM_M_NOT_DELIVERED");else p.innerHTML=a.ERROR}s.onCustomEvent(e,"onImError",["SEND_ERROR",a.ERROR,a.TMP_ID,a.SEND_DATE,a.SEND_MESSAGE,a.RECIPIENT_ID]);s.MessengerCommon.drawProgessMessage("temp"+t,{title:s.message("IM_M_RETRY"),chat:n?"Y":"N"});if(this.BXIM.messenger.message["temp"+t])this.BXIM.messenger.message["temp"+t].retry=true}}},this),onfailure:s.delegate(function(){this.BXIM.messenger.sendMessageFlag--;this.BXIM.messenger.sendMessageTmp[t]=false;var r=s.findChild(this.BXIM.messenger.popupMessengerBodyWrap,{attribute:{"data-messageid":"temp"+t}},true);var i=s.findChildByClassName(r,"bx-messenger-content-item-date");if(i)i.innerHTML=s.message("IM_M_NOT_DELIVERED");s.MessengerCommon.drawProgessMessage("temp"+t,{title:s.message("IM_M_RETRY"),chat:n?"Y":"N"});this.BXIM.messenger.sendAjaxTry=0;try{if(typeof a=="object"&&a.status==0)s.onCustomEvent(e,"onImError",["CONNECT_ERROR"])}catch(o){}if(this.BXIM.messenger.message["temp"+t])this.BXIM.messenger.message["temp"+t].retry=true},this)})};s.MessengerCommon.prototype.sendMessageRetry=function(){var e=this.BXIM.messenger.currentTab;var t=[];for(var r=0;r<this.BXIM.messenger.showMessage[e].length;r++){var i=this.BXIM.messenger.message[this.BXIM.messenger.showMessage[e][r]];if(!i||i.id.indexOf("temp")!=0)continue;i.text=s.MessengerCommon.prepareTextBack(i.text);t.push(i)}if(t.length<=0)return false;t.sort(function(e,s){e=e.id.substr(4);s=s.id.substr(4);if(e<s){return-1}else if(e>s){return 1}else{return 0}});for(var r=0;r<t.length;r++){var n=s.findChild(this.BXIM.messenger.popupMessengerBodyWrap,{attribute:{"data-messageid":""+t[r].id+""}},true);var a=s.findChildByClassName(n,"bx-messenger-content-item-date");if(a)a.innerHTML=s.message("IM_M_DELIVERED");this.sendMessageRetryTimeout(t[r],100*r)}};s.MessengerCommon.prototype.sendMessageRetryTimeout=function(e,t){clearTimeout(this.BXIM.messenger.sendMessageTmpTimeout[e.id]);this.BXIM.messenger.sendMessageTmpTimeout[e.id]=setTimeout(s.delegate(function(){s.MessengerCommon.sendMessageAjax(e.id.substr(4),e.recipientId,e.text,e.recipientId.toString().substr(0,4)=="chat")},this),t)};s.MessengerCommon.prototype.messageLike=function(e,t){if(e.toString().substr(0,4)=="temp"||!this.BXIM.messenger.message[e]||this.BXIM.messenger.popupMessengerLikeBlock[e])return false;t=typeof t=="undefined"?false:t;if(!this.BXIM.messenger.message[e].params){this.BXIM.messenger.message[e].params={}}if(!this.BXIM.messenger.message[e].params.LIKE){this.BXIM.messenger.message[e].params.LIKE=[]}var r=s.util.in_array(this.BXIM.userId,this.BXIM.messenger.message[e].params.LIKE);if(!t){var i=r?"minus":"plus";if(i=="plus"){this.BXIM.messenger.message[e].params.LIKE.push(this.BXIM.userId);r=true}else{var n=[];for(var a=0;a<this.BXIM.messenger.message[e].params.LIKE.length;a++){if(this.BXIM.messenger.message[e].params.LIKE[a]!=this.BXIM.userId){n.push(this.BXIM.messenger.message[e].params.LIKE[a])}}this.BXIM.messenger.message[e].params.LIKE=n;r=false}}var o=this.BXIM.messenger.message[e].params.LIKE.length>0?this.BXIM.messenger.message[e].params.LIKE.length:"";if(s("im-message-"+e)){var m=s.findChild(this.BXIM.messenger.popupMessengerBodyWrap,{attribute:{"data-blockmessageid":""+e+""}},false);var g=s.findChildByClassName(m,"bx-messenger-content-item-like");var l=s.findChildByClassName(m,"bx-messenger-content-like-digit",false);var h=s.findChildByClassName(m,"bx-messenger-content-like-button",false);if(r){h.innerHTML=s.message("IM_MESSAGE_DISLIKE");s.addClass(g,"bx-messenger-content-item-liked")}else{h.innerHTML=s.message("IM_MESSAGE_LIKE");s.removeClass(g,"bx-messenger-content-item-liked")}if(o>0){l.setAttribute("title",s.message("IM_MESSAGE_LIKE_LIST"));s.removeClass(l,"bx-messenger-content-like-digit-off")}else{l.setAttribute("title","");s.addClass(l,"bx-messenger-content-like-digit-off")}l.innerHTML=o}if(!t){clearTimeout(this.BXIM.messenger.popupMessengerLikeBlockTimeout[e]);this.BXIM.messenger.popupMessengerLikeBlockTimeout[e]=setTimeout(s.delegate(function(){this.BXIM.messenger.popupMessengerLikeBlock[e]=true;s.ajax({url:this.BXIM.pathToAjax+"?MESSAGE_LIKE&V="+this.BXIM.revision,method:"POST",dataType:"json",timeout:30,data:{IM_LIKE_MESSAGE:"Y",ID:e,ACTION:i,IM_AJAX_CALL:"Y",sessid:s.bitrix_sessid()},onsuccess:s.delegate(function(t){if(t.ERROR==""){this.BXIM.messenger.message[e].params.LIKE=t.LIKE}this.BXIM.messenger.popupMessengerLikeBlock[e]=false;s.MessengerCommon.messageLike(e,true)},this),onfailure:s.delegate(function(s){this.BXIM.messenger.popupMessengerLikeBlock[e]=false},this)})},this),1e3)}return true};s.MessengerCommon.prototype.messageIsLike=function(e){return typeof this.BXIM.messenger.message[e].params.LIKE=="object"&&s.util.in_array(this.BXIM.userId,this.BXIM.messenger.message[e].params.LIKE)};s.MessengerCommon.prototype.checkEditMessage=function(e){var t=false;if(this.BXIM.ppServerStatus&&parseInt(e)!=0&&e.toString().substr(0,4)!="temp"&&this.BXIM.messenger.message[e]&&this.BXIM.messenger.message[e].senderId==this.BXIM.userId&&parseInt(this.BXIM.messenger.message[e].date)+259200>(new Date).getTime()/1e3&&(!this.BXIM.messenger.message[e].params||this.BXIM.messenger.message[e].params.IS_DELETED!="Y")&&s("im-message-"+e)&&s.util.in_array(e,this.BXIM.messenger.showMessage[this.BXIM.messenger.currentTab])){t=true}return t};s.MessengerCommon.prototype.deleteMessageAjax=function(e){this.BXIM.messenger.editMessageCancel();if(!s.MessengerCommon.checkEditMessage(e))return false;s.MessengerCommon.drawProgessMessage(e);s.ajax({url:this.BXIM.pathToAjax+"?MESSAGE_DELETE&V="+this.BXIM.revision,method:"POST",dataType:"json",timeout:30,data:{IM_DELETE_MESSAGE:"Y",ID:e,IM_AJAX_CALL:"Y",sessid:s.bitrix_sessid()},onsuccess:s.delegate(function(t){if(t.ERROR)return false;s.MessengerCommon.clearProgessMessage(e)},this),onfailure:s.delegate(function(){s.MessengerCommon.clearProgessMessage(e)},this)});return true};s.MessengerCommon.prototype.diskDrawFiles=function(e,t,r){if(!this.BXIM.disk.enable)return[];if(typeof this.BXIM.disk.files[e]=="undefined")return[];var i=[];if(typeof t!="object"){if(typeof this.BXIM.disk.files[e][t]=="undefined")return[];i.push(t)}else{i=t}r=r||{};var n=this.isMobile()?"mobile":this.BXIM.desktop.ready()?"desktop":"default";var a=true;var o=[];for(var m=0;m<i.length;m++){var g=this.BXIM.disk.files[e][i[m]];if(!g)continue;if(r.status){if(typeof r.status!="object"){r.status=[r.status]}if(!s.util.in_array(g.status,r.status)){continue}}var l=null;if(g.type=="image"&&(g.preview||g.urlPreview[n])){var h=null;if(this.isMobile()&&g.preview&&typeof g.preview!="string"){if(g.urlPreview[n]){var h=s.create("div",{attrs:{src:g.urlPreview[n]},props:{className:"bx-messenger-file-image-text bx-messenger-hide"}})}}if(g.preview&&typeof g.preview!="string"){var I=g.preview;if(g.urlPreview[n]){g.preview=""}}else{var I=s.create("img",{attrs:{src:g.urlPreview[n]?g.urlPreview[n]:g.preview},props:{className:"bx-messenger-file-image-text"}})}if(a&&g.urlShow[n]){if(this.isMobile()&&g.urlPreview[n]){l=s.create("div",{props:{className:"bx-messenger-file-preview"},children:[s.create("span",{props:{className:"bx-messenger-file-image"},children:[s.create("span",{events:{click:s.delegate(function(){this.BXIM.messenger.openPhotoGallery(g.urlPreview[n])},this)},props:{className:"bx-messenger-file-image-src"},children:[h,I]})]}),s.create("br")]})}else{l=s.create("div",{props:{className:"bx-messenger-file-preview"},children:[s.create("span",{props:{className:"bx-messenger-file-image"},children:[s.create("a",{attrs:{href:g.urlShow[n],target:"_blank"},props:{className:"bx-messenger-file-image-src"},children:[I]})]}),s.create("br")]})}}else{l=s.create("div",{props:{className:"bx-messenger-file-preview"},children:[s.create("span",{props:{className:"bx-messenger-file-image"},children:[s.create("span",{props:{className:"bx-messenger-file-image-src"},children:[I]})]}),s.create("br")]})}}var M=g.name;if(this.isMobile()){if(M.length>20){M=M.substr(0,8)+"..."+M.substr(M.length-13,M.length)}}else{if(M.length>40){M=M.substr(0,20)+"..."+M.substr(M.length-23,M.length)}}var p=s.create("span",{attrs:{title:g.name},props:{className:"bx-messenger-file-title"},children:[s.create("span",{props:{className:"bx-messenger-file-title-name"},html:M})]});if(a&&(g.urlShow[n]||g.urlDownload[n])){if(this.isMobile())p=s.create("span",{props:{className:"bx-messenger-file-title-href"},events:{click:function(){s.localStorage.set("impmh",true,1);app.openDocument({url:g.urlDownload["mobile"],filename:M})}},children:[p]});else p=s.create("a",{props:{className:"bx-messenger-file-title-href"},attrs:{href:g.urlShow?g.urlShow[n]:g.urlDownload[n],target:"_blank"},children:[p]})}p=s.create("div",{props:{className:"bx-messenger-file-attrs"},children:[p,s.create("span",{props:{className:"bx-messenger-file-size"},html:s.UploaderUtils.getFormattedSize(g.size)})]});var d=null;if(g.status=="done"){if(!this.isMobile()){d=s.create("div",{props:{className:"bx-messenger-file-download"},children:[!g.urlDownload||!a?null:s.create("a",{attrs:{href:g.urlDownload[n],target:"_blank"},props:{className:"bx-messenger-file-download-link bx-messenger-file-download-pc"},html:s.message("IM_F_DOWNLOAD")}),!g.urlDownload||!this.BXIM.disk.enable?null:s.create("span",{props:{className:"bx-messenger-file-download-link bx-messenger-file-download-disk"},html:s.message("IM_F_DOWNLOAD_DISK"),events:{click:s.delegate(function(){var e=s.proxy_context.parentNode.parentNode.getAttribute("data-chatId");var t=s.proxy_context.parentNode.parentNode.getAttribute("data-fileId");var r=s.proxy_context.parentNode.parentNode.getAttribute("data-boxId");this.BXIM.disk.saveToDisk(e,t,{boxId:r})},this)}})]})}else{d=s.create("div",{props:{className:"bx-messenger-file-download"},children:[]})}}else if(g.status=="upload"){var u={};var f="";var c=null;var B="";var X="";if(g.authorId==this.BXIM.userId&&g.progress>=0){X=s.message("IM_F_UPLOAD_2").replace("#PERCENT#",g.progress);u={width:g.progress+"%"};c=s.create("span",{attrs:{title:s.message("IM_F_CANCEL")},props:{className:"bx-messenger-file-delete"}})}else{X=s.message("IM_F_UPLOAD");B=" bx-messenger-file-progress-infinite"}d=s.create("div",{props:{className:"bx-messenger-progress-box"},children:[s.create("span",{attrs:{title:X},props:{className:"bx-messenger-file-progress"},children:[s.create("span",{props:{className:"bx-messenger-file-progress-line"+B},style:u})]}),c]})}else if(g.status=="error"){d=s.create("span",{props:{className:"bx-messenger-file-status-error"},html:g.errorText?g.errorText:s.message("IM_F_ERROR")})}if(!d)return false;if(i.length==1&&r.showInner=="Y"){o=[l,p,d]}else{var E=r.boxId?r.boxId:"im-file";o.push(s.create("div",{attrs:{id:E+"-"+g.id,"data-chatId":g.chatId,"data-fileId":g.id,"data-boxId":E},props:{className:"bx-messenger-file"},children:[l,p,d]}))}}return o};s.MessengerCommon.prototype.diskRedrawFile=function(e,t,r){r=r||{};var i=r.boxId?r.boxId:"im-file";var n=s(i+"-"+t);if(n){var a=this.diskDrawFiles(e,t,{showInner:"Y",boxId:i});if(a){n.innerHTML="";s.adjust(n,{children:a})}}};s.MessengerCommon.prototype.diskChatDialogFileInited=function(e,t,r){var i=r.form.CHAT_ID.value;if(!this.BXIM.disk.files[i])this.BXIM.disk.files[i]={};this.BXIM.disk.files[i][e]={id:e,tempId:e,chatId:i,date:s.MessengerCommon.getNowDate(),type:t.isImage?"image":"file",preview:t.isImage?t.canvas:"",name:t.name,size:t.file.size,status:"upload",progress:-1,authorId:this.BXIM.userId,authorName:this.BXIM.messenger.users[this.BXIM.userId].name,urlPreview:"",urlShow:"",urlDownload:""};if(!this.BXIM.disk.filesRegister[i])this.BXIM.disk.filesRegister[i]={};this.BXIM.disk.filesRegister[i][e]={id:e,type:this.BXIM.disk.files[i][e].type,mimeType:t.file.type,name:this.BXIM.disk.files[i][e].name,size:this.BXIM.disk.files[i][e].size};this.diskChatDialogFileRegister(i)};s.MessengerCommon.prototype.diskChatDialogFileRegister=function(t){clearTimeout(this.BXIM.disk.timeout[t]);this.BXIM.disk.timeout[t]=setTimeout(s.delegate(function(){var r=0;if(this.BXIM.messenger.chat[t]&&this.BXIM.messenger.chat[t].style!="private"){r="chat"+t}else{for(var i in this.BXIM.messenger.userChat){if(this.BXIM.messenger.userChat[i]==t){r=i;break}}}if(!r)return false;var n=[];for(var a in this.BXIM.disk.filesRegister[t]){n.push(a)}var o="tempFile"+this.BXIM.disk.fileTmpId;this.BXIM.messenger.message[o]={id:o,chatId:t,senderId:this.BXIM.userId,recipientId:r,date:s.MessengerCommon.getNowDate(),text:"",params:{FILE_ID:n}};if(!this.BXIM.messenger.showMessage[r])this.BXIM.messenger.showMessage[r]=[];this.BXIM.messenger.showMessage[r].push(o);s.MessengerCommon.drawMessage(r,this.BXIM.messenger.message[o]);s.MessengerCommon.drawProgessMessage(o);this.BXIM.messenger.sendMessageFlag++;this.BXIM.messenger.popupMessengerFileFormInput.setAttribute("disabled",true);this.BXIM.disk.OldBeforeUnload=e.onbeforeunload;e.onbeforeunload=function(){if(typeof s.PULL!="undefined"&&typeof s.PULL.tryConnectDelay=="function"){s.PULL.tryConnectDelay()}return s.message("IM_F_EFP")};s.ajax({url:this.BXIM.pathToFileAjax+"?FILE_REGISTER&V="+this.BXIM.revision,method:"POST",dataType:"json",timeout:30,data:{IM_FILE_REGISTER:"Y",CHAT_ID:t,RECIPIENT_ID:r,MESSAGE_TMP_ID:o,FILES:JSON.stringify(this.BXIM.disk.filesRegister[t]),IM_AJAX_CALL:"Y",sessid:s.bitrix_sessid()},onsuccess:s.delegate(function(i){if(i.ERROR!=""){this.BXIM.messenger.sendMessageFlag--;delete this.BXIM.messenger.message[o];s.MessengerCommon.drawTab(r);e.onbeforeunload=this.BXIM.disk.OldBeforeUnload;this.BXIM.disk.filesRegister[t]={};if(this.BXIM.disk.formAgents["imDialog"]["clear"])this.BXIM.disk.formAgents["imDialog"].clear();return false}this.BXIM.messenger.sendMessageFlag--;var n=[];var a={};for(var m in i.FILE_ID){var g=i.FILE_ID[m];delete this.BXIM.disk.filesRegister[i.CHAT_ID][g.TMP_ID];if(parseInt(g.FILE_ID)>0){a[g.TMP_ID]=g.FILE_ID;this.BXIM.disk.filesProgress[g.TMP_ID]=g.FILE_ID;this.BXIM.disk.filesMessage[g.TMP_ID]=i.MESSAGE_ID;this.BXIM.disk.files[i.CHAT_ID][g.FILE_ID]={};for(var l in this.BXIM.disk.files[i.CHAT_ID][g.TMP_ID])this.BXIM.disk.files[i.CHAT_ID][g.FILE_ID][l]=this.BXIM.disk.files[i.CHAT_ID][g.TMP_ID][l];

this.BXIM.disk.files[i.CHAT_ID][g.FILE_ID]["id"]=g.FILE_ID;delete this.BXIM.disk.files[i.CHAT_ID][g.TMP_ID];this.BXIM.disk.files[i.CHAT_ID][g.FILE_ID]["name"]=g.FILE_NAME;if(s("im-file-"+g.TMP_ID)){s("im-file-"+g.TMP_ID).setAttribute("data-fileId",g.FILE_ID);s("im-file-"+g.TMP_ID).id="im-file-"+g.FILE_ID;s.MessengerCommon.diskRedrawFile(i.CHAT_ID,g.FILE_ID)}n.push(g.FILE_ID)}else{this.BXIM.disk.files[i.CHAT_ID][g.TMP_ID]["status"]="error";s.MessengerCommon.diskRedrawFile(i.CHAT_ID,g.TMP_ID)}}this.BXIM.messenger.message[i.MESSAGE_ID]=s.clone(this.BXIM.messenger.message[i.MESSAGE_TMP_ID]);this.BXIM.messenger.message[i.MESSAGE_ID]["id"]=i.MESSAGE_ID;this.BXIM.messenger.message[i.MESSAGE_ID]["params"]["FILE_ID"]=n;if(this.BXIM.messenger.popupMessengerLastMessage==i.MESSAGE_TMP_ID)this.BXIM.messenger.popupMessengerLastMessage=i.MESSAGE_ID;delete this.BXIM.messenger.message[i.MESSAGE_TMP_ID];var h=s.util.array_search(""+i.MESSAGE_TMP_ID+"",this.BXIM.messenger.showMessage[i.RECIPIENT_ID]);if(this.BXIM.messenger.showMessage[i.RECIPIENT_ID][h])this.BXIM.messenger.showMessage[i.RECIPIENT_ID][h]=""+i.MESSAGE_ID+"";if(s("im-message-"+i.MESSAGE_TMP_ID)){s("im-message-"+i.MESSAGE_TMP_ID).id="im-message-"+i.MESSAGE_ID;var I=s.findChild(this.BXIM.messenger.popupMessengerBodyWrap,{attribute:{"data-messageid":""+i.MESSAGE_TMP_ID}},true);if(I){I.setAttribute("data-messageid",""+i.MESSAGE_ID+"");if(I.getAttribute("data-blockmessageid")==""+i.MESSAGE_TMP_ID)I.setAttribute("data-blockmessageid",""+i.MESSAGE_ID+"")}else{var M=s.findChild(this.BXIM.messenger.popupMessengerBodyWrap,{attribute:{"data-blockmessageid":""+i.MESSAGE_TMP_ID}},true);if(M){M.setAttribute("data-blockmessageid",""+i.MESSAGE_ID+"")}}var p=s.findChildByClassName(I,"bx-messenger-content-item-date");if(p)p.innerHTML=" &nbsp; "+s.MessengerCommon.formatDate(this.BXIM.messenger.message[i.MESSAGE_ID].date,s.MessengerCommon.getDateFormatType("MESSAGE"))}s.MessengerCommon.clearProgessMessage(i.MESSAGE_ID);if(this.BXIM.messenger.history[i.RECIPIENT_ID])this.BXIM.messenger.history[i.RECIPIENT_ID].push(i.MESSAGE_ID);else this.BXIM.messenger.history[i.RECIPIENT_ID]=[i.MESSAGE_ID];this.BXIM.messenger.popupMessengerFileFormRegChatId.value=i.CHAT_ID;this.BXIM.messenger.popupMessengerFileFormRegMessageId.value=i.MESSAGE_ID;this.BXIM.messenger.popupMessengerFileFormRegParams.value=JSON.stringify(a);this.BXIM.disk.formAgents["imDialog"].submit();this.BXIM.messenger.popupMessengerFileFormInput.removeAttribute("disabled")},this),onfailure:s.delegate(function(){this.BXIM.messenger.sendMessageFlag--;delete this.BXIM.messenger.message[o];this.BXIM.disk.filesRegister[t]={};s.MessengerCommon.drawTab(r);e.onbeforeunload=this.BXIM.disk.OldBeforeUnload;if(this.BXIM.disk.formAgents["imDialog"]["clear"])this.BXIM.disk.formAgents["imDialog"].clear()},this)});this.BXIM.disk.fileTmpId++},this),500)};s.MessengerCommon.prototype.diskChatDialogFileStart=function(e,t,r,i){var n=this.BXIM.disk.filesProgress[e.id];formFields=r.streams.packages.getItem(i).data;if(!this.BXIM.disk.files[formFields.REG_CHAT_ID][n])return false;this.BXIM.disk.files[formFields.REG_CHAT_ID][n].progress=parseInt(t);s.MessengerCommon.diskRedrawFile(formFields.REG_CHAT_ID,n)};s.MessengerCommon.prototype.diskChatDialogFileProgress=function(e,t,r,i){var n=this.BXIM.disk.filesProgress[e.id];formFields=r.streams.packages.getItem(i).data;if(!this.BXIM.disk.files[formFields.REG_CHAT_ID][n])return false;this.BXIM.disk.files[formFields.REG_CHAT_ID][n].progress=parseInt(t);s.MessengerCommon.diskRedrawFile(formFields.REG_CHAT_ID,n)};s.MessengerCommon.prototype.diskChatDialogFileDone=function(t,r,i,n){if(!this.BXIM.disk.files[r.file.fileChatId][r.file.fileId])return false;if(this.BXIM.disk.files[r.file.fileChatId]&&this.BXIM.disk.files[r.file.fileChatId][r.file.fileId]){r.file.fileParams["preview"]=this.BXIM.disk.files[r.file.fileChatId][r.file.fileId]["preview"]}if(!this.BXIM.disk.files[r.file.fileChatId])this.BXIM.disk.files[r.file.fileChatId]={};this.BXIM.disk.files[r.file.fileChatId][r.file.fileId]=r.file.fileParams;s.MessengerCommon.diskRedrawFile(r.file.fileChatId,r.file.fileId);delete this.BXIM.disk.filesMessage[r.file.fileTmpId];e.onbeforeunload=this.BXIM.disk.OldBeforeUnload};s.MessengerCommon.prototype.diskChatDialogFileError=function(t,r,i,n){var a=this.BXIM.disk.filesProgress[t.id];formFields=i.streams.packages.getItem(n).data;if(!this.BXIM.disk.files[formFields.REG_CHAT_ID][a])return false;t.deleteFile();this.BXIM.disk.files[formFields.REG_CHAT_ID][a].status="error";this.BXIM.disk.files[formFields.REG_CHAT_ID][a].errorText=r.error;s.MessengerCommon.diskRedrawFile(formFields.REG_CHAT_ID,a);e.onbeforeunload=this.BXIM.disk.OldBeforeUnload};s.MessengerCommon.prototype.diskChatDialogUploadError=function(t,r,i){var n=JSON.parse(t.post.REG_PARAMS);var a={};for(var o in n){if(this.BXIM.disk.filesMessage[o]){delete this.BXIM.disk.filesMessage[o]}if(this.BXIM.disk.filesRegister[t.post.REG_CHAT_ID]){delete this.BXIM.disk.filesRegister[t.post.REG_CHAT_ID][o];delete this.BXIM.disk.filesRegister[t.post.REG_CHAT_ID][n[o]]}if(this.BXIM.disk.files[t.post.REG_CHAT_ID]){if(this.BXIM.disk.files[t.post.REG_CHAT_ID][n[o]]){this.BXIM.disk.files[t.post.REG_CHAT_ID][n[o]].status="error";s.MessengerCommon.diskRedrawFile(t.post.REG_CHAT_ID,n[o])}if(this.BXIM.disk.files[t.post.REG_CHAT_ID][o]){this.BXIM.disk.files[t.post.REG_CHAT_ID][o].status="error";s.MessengerCommon.diskRedrawFile(t.post.REG_CHAT_ID,o)}}delete this.BXIM.disk.filesProgress[o]}s.ajax({url:this.BXIM.pathToFileAjax+"?FILE_UNREGISTER&V="+this.BXIM.revision,method:"POST",dataType:"json",timeout:30,data:{IM_FILE_UNREGISTER:"Y",CHAT_ID:t.post.REG_CHAT_ID,FILES:t.post.REG_PARAMS,MESSAGES:JSON.stringify(a),IM_AJAX_CALL:"Y",sessid:s.bitrix_sessid()}});e.onbeforeunload=this.BXIM.disk.OldBeforeUnload;s.MessengerCommon.drawTab(this.BXIM.messenger.getRecipientByChatId(t.post.REG_CHAT_ID))};s.MessengerCommon=new s.MessengerCommon})(window);
/* End */
;
; /* Start:"a:4:{s:4:"full";s:40:"/bitrix/js/im/im.min.js?1452277455452605";s:6:"source";s:19:"/bitrix/js/im/im.js";s:3:"min";s:23:"/bitrix/js/im/im.min.js";s:3:"map";s:23:"/bitrix/js/im/im.map.js";}"*/
(function(){if(BX.IM)return;BX.IM=function(e,t){if(typeof BX.message("USER_TZ_AUTO")=="undefined"||BX.message("USER_TZ_AUTO")=="Y")BX.message({USER_TZ_OFFSET:-(new Date).getTimezoneOffset()*60-parseInt(BX.message("SERVER_TZ_OFFSET"))});if(typeof BX.MessengerCommon!="undefined")BX.MessengerCommon.setBxIm(this);this.mobileVersion=false;this.mobileAction="none";this.revision=54;this.ieVersion=BX.browser.DetectIeVersion();this.errorMessage="";this.animationSupport=true;this.bitrixNetworkStatus=t.bitrixNetworkStatus;this.bitrix24Status=t.bitrix24Status;this.bitrix24Admin=t.bitrix24Admin;this.bitrixIntranet=t.bitrixIntranet;this.bitrix24net=t.bitrix24net;this.bitrixXmpp=t.bitrixXmpp;this.ppStatus=t.ppStatus;this.ppServerStatus=this.ppStatus?t.ppServerStatus:false;this.updateStateInterval=t.updateStateInterval;this.desktopStatus=t.desktopStatus||false;this.desktopVersion=t.desktopVersion;this.xmppStatus=t.xmppStatus;this.lastRecordId=0;this.userId=t.userId;this.userEmail=t.userEmail;this.path=t.path;this.language=t.language||"en";this.init=typeof t.init!="undefined"?t.init:true;this.windowFocus=true;this.windowFocusTimeout=null;this.extraBind=null;this.extraOpen=false;this.dialogOpen=false;this.notifyOpen=false;this.adjustSizeTimeout=null;this.tryConnect=true;this.openSettingsFlag=typeof t.openSettings!="undefined"?t.openSettings:false;this.popupConfirm=null;this.settings=t.settings;this.settingsView=t.settingsView||{common:{},notify:{},privacy:{}};this.settingsNotifyBlocked=t.settingsNotifyBlocked||{};this.settingsTableConfig={};this.settingsSaveCallback={};this.saveSettingsTimeout={};this.popupSettings=null;if(t.users&&t.users[this.userId])t.users[this.userId].status=this.settings.status;this.pathToAjax=t.path.im?t.path.im:"/bitrix/components/bitrix/im.messenger/im.ajax.php";this.pathToCallAjax=t.path.call?t.path.call:"/bitrix/components/bitrix/im.messenger/call.ajax.php";this.pathToFileAjax=t.path.file?t.path.file:"/bitrix/components/bitrix/im.messenger/file.ajax.php";this.pathToBlankImage="/bitrix/js/im/images/blank.gif";this.audio={};this.audio.reminder=null;this.audio.newMessage1=null;this.audio.newMessage2=null;this.audio.send=null;this.audio.dialtone=null;this.audio.ringtone=null;this.audio.start=null;this.audio.stop=null;this.audio.current=null;this.audio.timeout={};this.mailCount=t.mailCount;this.notifyCount=t.notifyCount||0;this.messageCount=t.messageCount||0;this.quirksMode=BX.browser.IsIE()&&!BX.browser.IsDoctype()&&(/MSIE 8/.test(navigator.userAgent)||/MSIE 9/.test(navigator.userAgent));this.platformName=BX.browser.IsMac()?"OS X":/windows/.test(navigator.userAgent.toLowerCase())?"Windows":"";if(BX.browser.IsIE()&&!BX.browser.IsIE9()&&/MSIE 7/i.test(navigator.userAgent))this.errorMessage=BX.message("IM_M_OLD_BROWSER");this.desktop=new BX.IM.Desktop(this,{desktop:t.desktop});this.webrtc=new BX.IM.WebRTC(this,{desktopClass:this.desktop,phoneEnabled:t.webrtc&&t.webrtc.phoneEnabled||false,phoneSipAvailable:t.webrtc&&t.webrtc.phoneSipAvailable||0,phoneDeviceActive:t.webrtc&&t.webrtc.phoneDeviceActive||"N",phoneDeviceCall:t.webrtc&&t.webrtc.phoneDeviceCall||"Y",phoneCrm:t.phoneCrm&&t.phoneCrm||{},turnServer:t.webrtc&&t.webrtc.turnServer||"",turnServerFirefox:t.webrtc&&t.webrtc.turnServerFirefox||"",turnServerLogin:t.webrtc&&t.webrtc.turnServerLogin||"",turnServerPassword:t.webrtc&&t.webrtc.turnServerPassword||"",panel:e!=null?e:BX.create("div")});this.desktop.webrtc=this.webrtc;this.windowTitle=this.desktop.ready()?"":document.title;for(var s in t.notify){t.notify[s].date=parseInt(t.notify[s].date)+parseInt(BX.message("USER_TZ_OFFSET"));if(parseInt(s)>this.lastRecordId)this.lastRecordId=parseInt(s)}for(var s in t.message){t.message[s].date=parseInt(t.message[s].date)+parseInt(BX.message("USER_TZ_OFFSET"));if(parseInt(s)>this.lastRecordId)this.lastRecordId=parseInt(s)}for(var s in t.recent){t.recent[s].date=parseInt(t.recent[s].date)+parseInt(BX.message("USER_TZ_OFFSET"))}if(BX.browser.SupportLocalStorage()){BX.addCustomEvent(window,"onLocalStorageSet",BX.proxy(this.storageSet,this));var i=BX.localStorage.get("lri");if(parseInt(i)>this.lastRecordId)this.lastRecordId=parseInt(i);BX.garbage(function(){BX.localStorage.set("lri",this.lastRecordId,60)},this)}this.notifyManager=new BX.IM.NotifyManager(this,{});this.notify=new BX.Notify(this,{desktopClass:this.desktop,webrtcClass:this.webrtc,domNode:e!=null?e:BX.create("div"),counters:t.counters||{},mailCount:t.mailCount||0,notify:t.notify||{},unreadNotify:t.unreadNotify||{},flashNotify:t.flashNotify||{},countNotify:t.countNotify||0,loadNotify:t.loadNotify});this.webrtc.notify=this.notify;this.desktop.notify=this.notify;this.disk=new BX.IM.DiskManager(this,{notifyClass:this.notify,desktopClass:this.desktop,files:t.files||{},enable:t.disk&&t.disk.enable});this.notify.disk=this.disk;this.webrtc.disk=this.disk;this.desktop.disk=this.disk;this.messenger=new BX.Messenger(this,{updateStateInterval:t.updateStateInterval,notifyClass:this.notify,webrtcClass:this.webrtc,desktopClass:this.desktop,diskClass:this.disk,recent:t.recent,users:t.users||{},groups:t.groups||{},userChatBlockStatus:t.userChatBlockStatus||{},userInGroup:t.userInGroup||{},woGroups:t.woGroups||{},woUserInGroup:t.woUserInGroup||{},currentTab:t.currentTab||0,chat:t.chat||{},userInChat:t.userInChat||{},userChat:t.userChat||{},hrphoto:t.hrphoto||{},message:t.message||{},showMessage:t.showMessage||{},unreadMessage:t.unreadMessage||{},flashMessage:t.flashMessage||{},countMessage:t.countMessage||0,smile:t.smile||false,smileSet:t.smileSet||false,history:t.history||{},openMessenger:typeof t.openMessenger!="undefined"?t.openMessenger:false,openHistory:typeof t.openHistory!="undefined"?t.openHistory:false,openNotify:typeof t.openNotify!="undefined"?t.openNotify:false});this.webrtc.messenger=this.messenger;this.notify.messenger=this.messenger;this.desktop.messenger=this.messenger;this.disk.messenger=this.messenger;this.network=new BX.Network(this,{notifyClass:this.notify,messengerClass:this.messenger,desktopClass:this.desktop});if(this.init){BX.addCustomEvent(window,"onImUpdateCounterNotify",BX.proxy(this.updateCounter,this));BX.addCustomEvent(window,"onImUpdateCounterMessage",BX.proxy(this.updateCounter,this));BX.addCustomEvent(window,"onImUpdateCounterMail",BX.proxy(this.updateCounter,this));BX.addCustomEvent(window,"onImUpdateCounter",BX.proxy(this.updateCounter,this));BX.bind(window,"blur",BX.delegate(function(){this.changeFocus(false)},this));BX.bind(window,"focus",this.setFocusFunction=BX.delegate(function(){if(this.windowFocus)return false;if(this.desktop.ready()&&!BX.desktop.isActiveWindow())return false;this.changeFocus(true);if(this.isFocus()&&this.messenger.unreadMessage[this.messenger.currentTab]&&this.messenger.unreadMessage[this.messenger.currentTab].length>0)BX.MessengerCommon.readMessage(this.messenger.currentTab);if(this.isFocus("notify")){if(this.notify.unreadNotifyLoad)this.notify.loadNotify();else if(this.notify.notifyUpdateCount>0)this.notify.viewNotifyAll()}},this));if(this.desktop.ready())BX.bind(window,"click",this.setFocusFunction);BX.addCustomEvent("onPullEvent-xmpp",BX.delegate(function(e,t){if(e=="lastActivityDate"){this.xmppStatus=t.timestamp>0}},this));this.updateCounter();BX.onCustomEvent(window,"onImInit",[this])}if(this.openSettingsFlag!==false)this.openSettings(this.openSettingsFlag=="true"?{}:{onlyPanel:this.openSettingsFlag.toString().toLowerCase()})};BX.IM.prototype.isFocus=function(e){e=typeof e=="undefined"?"dialog":e;if(!this.desktop.run()&&(this.messenger==null||this.messenger.popupMessenger==null))return false;if(e=="dialog"){if(this.desktop.ready()&&BX.desktop.getCurrentTab()!="im"&&BX.desktop.getCurrentTab()!="im-phone")return false;if(this.messenger&&!BX.MessengerCommon.isScrollMax(this.messenger.popupMessengerBody,200))return false;if(this.dialogOpen==false)return false}else if(e=="notify"){if(this.desktop.ready()&&BX.desktop.getCurrentTab()!="notify"&&BX.desktop.getCurrentTab()!="im-phone")return false;if(this.notifyOpen==false)return false}if(this.quirksMode||BX.browser.IsIE()&&!BX.browser.IsIE9())return true;return this.windowFocus};BX.IM.prototype.changeFocus=function(e){this.windowFocus=typeof e=="boolean"?e:false;return this.windowFocus};BX.IM.prototype.playSound=function(e,t){t=t?true:false;if(!t&&(!this.init||this.webrtc.callActive))return false;var s={stop:true,start:true,dialtone:true,ringtone:true,error:true};if(!this.settings.enableSound&&!s[e])return false;BX.localStorage.set("mps",true,1);try{this.stopSound();this.audio.current=this.audio[e];this.audio[e].play()}catch(i){this.audio.current=null}};BX.IM.prototype.repeatSound=function(e,t){BX.localStorage.set("mrs",{sound:e,time:t},1);if(this.audio.timeout[e])clearTimeout(this.audio.timeout[e]);if(this.desktop.ready()||!this.desktopStatus)this.playSound(e);this.audio.timeout[e]=setTimeout(BX.delegate(function(){this.repeatSound(e,t)},this),t)};BX.IM.prototype.stopRepeatSound=function(e,t){t=t!=false;if(t)BX.localStorage.set("mrss",{sound:e},1);if(this.audio.timeout[e])clearTimeout(this.audio.timeout[e]);if(!this.audio[e])return false;this.audio[e].pause();this.audio[e].currentTime=0};BX.IM.prototype.stopSound=function(){if(this.audio.current){this.audio.current.pause();this.audio.current.currentTime=0}};BX.IM.prototype.autoHide=function(e){e=e||window.event;if(e.which==1){if(this.popupSettings!=null)this.popupSettings.destroy();else if(this.messenger.popupHistory!=null)this.messenger.popupHistory.destroy();else if(BX.DiskFileDialog&&BX.DiskFileDialog.popupWindow!=null)BX.DiskFileDialog.popupWindow.destroy();else if(!this.webrtc.callInit&&this.messenger.popupMessenger!=null)this.messenger.popupMessenger.destroy()}};BX.IM.prototype.updateCounter=function(e,t){if(t=="MESSAGE")this.messageCount=e;else if(t=="NOTIFY")this.notifyCount=e;else if(t=="MAIL")this.mailCount=e;var s=0;if(this.notifyCount>0)s+=parseInt(this.notifyCount);if(this.messageCount>0)s+=parseInt(this.messageCount);if(this.desktop.run()){var i="";if(s>99)i="99+";else if(s>0)i=s;var a=BX.message("IM_DESKTOP_UNREAD_EMPTY");if(this.notifyCount>0&&this.messageCount>0)a=BX.message("IM_DESKTOP_UNREAD_MESSAGES_NOTIFY");else if(this.notifyCount>0)a=BX.message("IM_DESKTOP_UNREAD_NOTIFY");else if(this.messageCount>0)a=BX.message("IM_DESKTOP_UNREAD_MESSAGES");else if(this.notify!=null&&this.notify.getCounter("**")>0)a=BX.message("IM_DESKTOP_UNREAD_LF");BX.desktop.setIconTooltip(a);BX.desktop.setIconBadge(i,this.messageCount>0);if(this.notify!=null){var n=this.notify.getCounter("**");BX.desktop.setTabBadge("im-lf",n)}}BX.onCustomEvent(window,"onImUpdateSumCounters",[s,"SUM"]);if(this.settings.status!="dnd"&&!this.desktopStatus&&s>0){if(!this.desktop.ready()&&document.title!="("+s+") "+this.windowTitle)document.title="("+s+") "+this.windowTitle;if(this.messageCount>0)BX.addClass(this.notify.panelButtonMessage,"bx-notifier-message-new");else BX.removeClass(this.notify.panelButtonMessage,"bx-notifier-message-new")}else{if(!this.desktop.ready()&&document.title!=this.windowTitle)document.title=this.windowTitle;if(this.messageCount<=0||this.settings.status=="dnd"||this.desktopStatus)BX.removeClass(this.notify.panelButtonMessage,"bx-notifier-message-new")}};BX.IM.prototype.openNotify=function(e){setTimeout(BX.delegate(function(){this.notify.openNotify()},this),200)};BX.IM.prototype.closeNotify=function(){BX.onCustomEvent(window,"onImNotifyWindowClose",[]);if(this.messenger.popupMessenger!=null&&!this.webrtc.callInit)this.messenger.popupMessenger.destroy()};BX.IM.prototype.toggleNotify=function(){if(this.isOpenNotify())this.closeNotify();else this.openNotify()};BX.IM.prototype.isOpenNotify=function(){return this.notifyOpen};BX.IM.prototype.callTo=function(e,t){t=!(typeof t!="undefined"&&!t);if(!this.desktop.ready()&&this.desktopStatus&&this.desktopVersion>=18){BX.desktopUtils.goToBx("bx://callto/"+(t?"video":"audio")+"/"+e+(this.bitrix24net?"/bitrix24net/Y":""))}else{this.webrtc.callInvite(e,t)}};BX.IM.prototype.phoneTo=function(e,t){t=t?t:{};if(!this.desktop.ready()&&this.desktopStatus&&this.desktopVersion>=18){var s="";if(t){if(typeof t!="object"){try{t=JSON.parse(t)}catch(i){t={}}}for(var a in t)s=s+"!!"+a+"!!"+t[a];s="/params/"+s.substr(2)}if(this.webrtc.popupKeyPad)this.webrtc.popupKeyPad.close();this.webrtc.phoneNumberLast=e;this.setLocalConfig("phone_last",e);BX.desktopUtils.goToBx("bx://callto/phone/"+escape(e)+s+(this.bitrix24net?"/bitrix24net/Y":""))}else{if(typeof t!="object"){try{t=JSON.parse(t)}catch(i){t={}}}setTimeout(BX.delegate(function(){this.webrtc.phoneCall(e,t)},this),200)}return true};BX.IM.prototype.checkCallSupport=function(){return this.webrtc.callSupport()};BX.IM.prototype.openMessenger=function(e){setTimeout(BX.delegate(function(){this.messenger.openMessenger(e)},this),200)};BX.IM.prototype.closeMessenger=function(){if(this.messenger.popupMessenger!=null&&!this.webrtc.callInit)this.messenger.popupMessenger.destroy()};BX.IM.prototype.isOpenMessenger=function(){return this.dialogOpen};BX.IM.prototype.toggleMessenger=function(){if(this.isOpenMessenger())this.closeMessenger();else if(this.extraOpen&&!this.isOpenNotify())this.closeMessenger();else this.openMessenger(this.messenger.currentTab)};BX.IM.prototype.openHistory=function(e){setTimeout(BX.proxy(function(){this.messenger.openHistory(e)},this),200)};BX.IM.prototype.openContactList=function(){return false};BX.IM.prototype.closeContactList=function(){return false};BX.IM.prototype.isOpenContactList=function(){return false};BX.IM.prototype.checkRevision=function(e){e=parseInt(e);if(typeof e=="number"&&this.revision<e){if(this.desktop.run()){console.log("NOTICE: Window reload, because REVISION UP ("+this.revision+" -> "+e+")");BX.desktop.windowReload()}else{if(this.isOpenMessenger()){this.closeMessenger();this.openMessenger()}this.errorMessage=BX.message("IM_M_OLD_REVISION").replace("#WM_NAME#",this.bitrixIntranet?BX.message("IM_BC"):BX.message("IM_WM"));this.tryConnect=false}return false}return true};BX.IM.prototype.openSettings=function(e){if(this.messenger&&this.messenger.popupMessengerConnectionStatusState!="online")return false;e=typeof e=="object"?e:{};if(this.popupSettings!=null||!this.messenger)return false;if(!this.desktop.run())this.messenger.setClosingByEsc(false);this.settingsSaveCallback={};this.settingsTableConfig={};this.settingsView.common={title:BX.message("IM_SETTINGS_COMMON"),settings:[{title:BX.message("IM_M_VIEW_OFFLINE_OFF"),type:"checkbox",name:"viewOffline",checked:!this.settings.viewOffline,saveCallback:BX.delegate(function(e){return!e.checked},this)},{title:BX.message("IM_M_VIEW_GROUP_OFF"),type:"checkbox",name:"viewGroup",checked:!this.settings.viewGroup,saveCallback:BX.delegate(function(e){return!e.checked},this)},{type:"space"},{title:BX.message("IM_M_LLM"),type:"checkbox",name:"loadLastMessage",checked:this.settings.loadLastMessage},{title:BX.message("IM_M_LLN"),type:"checkbox",name:"loadLastNotify",checked:this.settings.loadLastNotify},{type:"space"},{title:BX.message("IM_M_ENABLE_SOUND"),type:"checkbox",name:"enableSound",checked:this.settings.enableSound},this.desktop.ready()?{title:BX.message("IM_M_ENABLE_BIRTHDAY"),type:"checkbox",checked:this.desktop.birthdayStatus(),callback:BX.delegate(function(){this.desktop.birthdayStatus(!this.desktop.birthdayStatus())},this)}:null,{title:BX.message("IM_M_KEY_SEND"),type:"select",name:"sendByEnter",value:this.settings.sendByEnter?"Y":"N",items:[{title:BX.browser.IsMac()?"&#8984;+Enter":"Ctrl+Enter",value:"N"},{title:"Enter",value:"Y"}],saveCallback:BX.delegate(function(e){return e[e.selectedIndex].value=="Y"},this)},{type:"space"},this.desktop.ready()?{title:BX.message("IM_M_DESKTOP_AUTORUN_ON"),type:"checkbox",checked:BX.desktop.autorunStatus(),callback:BX.delegate(function(){BX.desktop.autorunStatus(!BX.desktop.autorunStatus())},this)}:null]};this.settingsView.notify={title:BX.message("IM_SETTINGS_NOTIFY"),settings:[{type:"notifyControl"},{type:"table",name:"notify",show:this.settings.notifyScheme=="expert"},{type:"table",name:"simpleNotify",show:this.settings.notifyScheme=="simple"}]};this.settingsTableConfig["notify"]={condition:BX.delegate(function(){return this.settingsTableConfig["notify"].rows.length>0},this),headers:["",BX.message("IM_SETTINGS_NOTIFY_SITE"),this.bitrixXmpp?BX.message("IM_SETTINGS_NOTIFY_XMPP"):false,BX.message("IM_SETTINGS_NOTIFY_EMAIL")],rows:[],error_rows:BX.create("div",{props:{className:" bx-messenger-content-item-progress bx-messenger-content-item-progress-with-text"},html:BX.message("IM_SETTINGS_LOAD")})};this.settingsTableConfig["simpleNotify"]={condition:BX.delegate(function(){return this.settingsTableConfig["simpleNotify"].rows.length>0},this),headers:[BX.message("IM_SETTINGS_SNOTIFY"),""],rows:[]};this.settingsView.privacy={title:BX.message("IM_SETTINGS_PRIVACY"),condition:BX.delegate(function(){return!this.bitrixIntranet},this),settings:[{title:BX.message("IM_SETTINGS_PRIVACY_MESS"),name:"privacyMessage",type:"select",items:[{title:BX.message("IM_SETTINGS_SELECT_1"),value:"all"},{title:BX.message("IM_SETTINGS_SELECT_2"),value:"contact"}],value:this.settings.privacyMessage},{title:BX.message("IM_SETTINGS_PRIVACY_CALL"),name:"privacyCall",type:"select",items:[{title:BX.message("IM_SETTINGS_SELECT_1"),value:"all"},{title:BX.message("IM_SETTINGS_SELECT_2"),value:"contact"}],value:this.settings.privacyCall},{title:BX.message("IM_SETTINGS_PRIVACY_CHAT"),name:"privacyChat",type:"select",items:[{title:BX.message("IM_SETTINGS_SELECT_1_2"),value:"all"},{title:BX.message("IM_SETTINGS_SELECT_2_2"),value:"contact"}],value:this.settings.privacyChat},{title:BX.message("IM_SETTINGS_PRIVACY_SEARCH"),name:"privacySearch",type:"select",items:[{title:BX.message("IM_SETTINGS_SELECT_1_3"),value:"all"},{title:BX.message("IM_SETTINGS_SELECT_2_3"),value:"contact"}],value:this.settings.privacySearch},this.bitrix24net?{title:BX.message("IM_SETTINGS_PRIVACY_PROFILE"),name:"privacyProfile",type:"select",items:[{title:BX.message("IM_SETTINGS_SELECT_1_3"),value:"all"},{title:BX.message("IM_SETTINGS_SELECT_2_3"),value:"contact"},{title:BX.message("IM_SETTINGS_SELECT_3_3"),value:"nobody"}],value:this.settings.privacyProfile}:null]};BX.onCustomEvent(this,"prepareSettingsView",[]);if(e.onlyPanel&&!this.settingsView[e.onlyPanel])return false;this.popupSettingsButtonSave=new BX.PopupWindowButton({text:BX.message("IM_SETTINGS_SAVE"),className:"popup-window-button-accept",events:{click:BX.delegate(function(){this.popupSettingsButtonSave.setClassName("popup-window-button");this.popupSettingsButtonSave.setName(BX.message("IM_SETTINGS_WAIT"));BX.hide(this.popupSettingsButtonClose.buttonNode);this.saveFormSettings()},this)}});this.popupSettingsButtonClose=new BX.PopupWindowButton({text:BX.message("IM_SETTINGS_CLOSE"),className:"popup-window-button-close",events:{click:BX.delegate(function(){this.popupSettings.close();BX.hide(this.popupSettingsButtonSave.buttonNode);BX.hide(this.popupSettingsButtonClose.buttonNode)},this)}});this.popupSettingsBody=BX.create("div",{props:{className:"bx-messenger-settings"},children:this.prepareSettings({onlyPanel:e.onlyPanel?e.onlyPanel:false,active:e.active?e.active:false})});if(this.desktop.ready()){if(this.init){this.desktop.openSettings(this.popupSettingsBody,"BXIM.openSettings("+JSON.stringify(e)+"); BX.desktop.resize(); ",e);return false}else{this.popupSettings=new BX.PopupWindowDesktop;BX.addClass(this.popupSettingsBody,"bx-messenger-mark");this.desktop.drawOnPlaceholder(this.popupSettingsBody)}}else{this.popupSettings=new BX.PopupWindow("bx-messenger-popup-settings",null,{lightShadow:true,autoHide:false,zIndex:200,overlay:{opacity:50,backgroundColor:"#000000"},buttons:[this.popupSettingsButtonSave,this.popupSettingsButtonClose],draggable:{restrict:true},closeByEsc:true,events:{onPopupClose:function(){this.destroy()},onPopupDestroy:BX.delegate(function(){this.popupSettings=null;if(!this.desktop.run()&&this.messenger.popupMesseger==null)BX.bind(document,"click",BX.proxy(this.autoHide,this));this.messenger.setClosingByEsc(true)},this)},titleBar:{content:BX.create("span",{props:{className:"bx-messenger-title"},html:e.onlyPanel?this.settingsView[e.onlyPanel].title:BX.message("IM_SETTINGS")})},closeIcon:{top:"10px",right:"13px"},content:this.popupSettingsBody});this.popupSettings.show();BX.addClass(this.popupSettings.popupContainer,"bx-messenger-mark");BX.bind(this.popupSettings.popupContainer,"click",BX.MessengerCommon.preventDefault)}BX.bindDelegate(this.popupSettingsBody,"click",{className:"bx-messenger-settings-tab"},BX.delegate(function(){var e=BX.findChildrenByClassName(BX.proxy_context.parentNode,"bx-messenger-settings-tab",false);for(var t=0;t<e.length;t++)BX.removeClass(e[t],"bx-messenger-settings-tab-active");BX.addClass(BX.proxy_context,"bx-messenger-settings-tab-active");var e=BX.findChildrenByClassName(BX.proxy_context.parentNode.nextSibling,"bx-messenger-settings-content",false);for(var t=0;t<e.length;t++){if(parseInt(BX.proxy_context.getAttribute("data-id"))==t)BX.addClass(e[t],"bx-messenger-settings-content-active");else BX.removeClass(e[t],"bx-messenger-settings-content-active")}if(this.desktop.ready())this.desktop.autoResize()},this));if(this.settings.notifyScheme=="simple")this.GetSimpleNotifySettings();else this.GetNotifySettings();if(!this.desktop.ready())BX.bind(document,"click",BX.proxy(this.autoHide,this))};BX.IM.prototype.prepareSettings=function(e){e=typeof e=="object"?e:{};var t=[];var s=[];var i=true;var a=0;for(var n in this.settingsView){if(this.settingsView[n].condition&&!this.settingsView[n].condition())continue;var o={};if(this.settingsView[n].click)o={click:BX.delegate(this.settingsView[n].click,this)};if(e.active&&this.settingsView[e.active]){if(e.active==n)i=true;else i=false}s.push(BX.create("div",{attrs:{"data-id":a+""},props:{className:"bx-messenger-settings-tab"+(i?" bx-messenger-settings-tab-active":"")},html:this.settingsView[n].title,events:o}));i=false;a++}t.push(BX.create("div",{style:{display:!e.onlyPanel?"block":"none"},props:{className:"bx-messenger-settings-tabs"},children:s}));var s=[];var i=true;for(var n in this.settingsView){if(this.settingsView[n].condition&&!this.settingsView[n].condition())continue;if(e.active&&this.settingsView[e.active]){if(e.active==n)i=true;else i=false}var r=[];if(this.settingsView[n].settings){var l=[];for(var p=0;p<this.settingsView[n].settings.length;p++){if(typeof this.settingsView[n].settings[p]!="object"||this.settingsView[n].settings[p]===null)continue;if(this.settingsView[n].settings[p].condition&&!this.settingsView[n].settings[p].condition())continue;if(this.settingsView[n].settings[p].type=="notifyControl"||this.settingsView[n].settings[p].type=="table"||this.settingsView[n].settings[p].type=="space"){l.push(BX.create("tr",{children:[BX.create("td",{attrs:{colspan:2},children:this.prepareSettingsItem(this.settingsView[n].settings[p])})]}))}else{l.push(BX.create("tr",{children:[BX.create("td",{attrs:{width:"55%"},html:this.settingsView[n].settings[p].title}),BX.create("td",{attrs:{width:"45%"},children:this.prepareSettingsItem(this.settingsView[n].settings[p])})]}))}}if(l.length>0)r.push(BX.create("table",{attrs:{cellpadding:"0",cellspacing:"0",border:"0",width:"100%"},props:{className:"bx-messenger-settings-table bx-messenger-settings-table-style-"+n},children:l}))}s.push(BX.create("div",{style:{display:e.onlyPanel?e.onlyPanel==n?"block":"none":""},props:{id:"bx-messenger-settings-content-"+n,className:"bx-messenger-settings-content"+(i?" bx-messenger-settings-content-active":"")},children:r}));i=false}t.push(BX.create("div",{props:{className:"bx-messenger-settings-contents"},children:s}));if(this.desktop.ready()){t.push(BX.create("div",{props:{className:"popup-window-buttons"},children:[this.popupSettingsButtonSave.buttonNode,this.popupSettingsButtonClose.buttonNode]}))}return t};BX.IM.prototype.prepareSettingsTable=function(e){var t=this.settingsTableConfig[e];if(!t.error_rows&&t.condition&&!BX.delegate(t.condition,this)())return null;var s=[];var i=[];for(var a=0;a<t.headers.length;a++){if(typeof t.headers[a]=="boolean")continue;i.push(BX.create("th",{html:t.headers[a]}))}if(i.length>0)s.push(BX.create("tr",{children:i}));if(t.error_rows&&t.condition&&!t.condition()){s.push(BX.create("tr",{children:[BX.create("td",{attrs:{colspan:t.headers.length},style:{textAlign:"center"},children:[t.error_rows]})]}));t.rows=[]}for(var a=0;a<t.rows.length;a++){var n=[];for(var o=0;o<t.rows[a].length;o++){if(typeof t.rows[a][o]!="object"||t.rows[a][o]===null)continue;var r={};var l={};if(t.rows[a][o].type=="separator"){r={colspan:t.headers.length};l={className:"bx-messenger-settings-table-sep"}}else if(t.rows[a][o].type=="error"){r={colspan:t.headers.length};l={className:"bx-messenger-settings-table-error"}}n.push(BX.create("td",{attrs:r,props:l,children:this.prepareSettingsItem(t.rows[a][o])}))}if(n.length>0)s.push(BX.create("tr",{children:n}))}var p=null;if(s.length>0)p=BX.create("table",{attrs:{cellpadding:"0",cellspacing:"0",border:"0"},props:{className:"bx-messenger-settings-table-extra bx-messenger-settings-table-extra-"+e},children:s});return p};BX.IM.prototype.prepareSettingsItem=function(e){var t=[];var s=BX.clone(e);if(s.type=="space"){t.push(BX.create("span",{props:{className:"bx-messenger-settings-space"}}))}if(s.type=="text"||s.type=="separator"||s.type=="error"){t.push(BX.create("span",{html:s.title}))}if(s.type=="link"){if(s.callback)var i={click:s.callback};t.push(BX.create("span",{props:{className:"bx-messenger-settings-link"},attrs:s.attrs,html:s.title,events:i}))}if(s.type=="checkbox"){if(s.callback)var i={change:s.callback};if(typeof s.checked=="undefined")s.checked=this.settings[s.name]!=false;var a={type:"checkbox",name:s.name?s.name:false,checked:s.checked==true?"true":false,disabled:s.disabled==true?"true":false};if(s.name)a["data-save"]=1;var n=BX.create("input",{attrs:a,events:i});t.push(n);if(s.saveCallback)this.settingsSaveCallback[s.name]=s.saveCallback}else if(s.type=="select"){if(s.callback)var i={change:s.callback};var o=[];for(var r=0;r<s.items.length;r++){o.push(BX.create("option",{attrs:{value:s.items[r].value,selected:s.value==s.items[r].value?"true":false},html:s.items[r].title}))}var a={name:s.name};if(s.name)a["data-save"]=1;var n=BX.create("select",{attrs:a,events:i,children:o});t.push(n);if(s.saveCallback)this.settingsSaveCallback[s.name]=s.saveCallback}else if(s.type=="table"){t.push(BX.create("div",{attrs:{id:"bx-messenger-settings-table-"+s.name},style:{display:s.show?"block":"none"},children:[this.prepareSettingsTable(s.name)]}))}else if(s.type=="notifyControl"){var l=BX.delegate(function(){if(BX.proxy_context.value=="simple"){BX.hide(BX("bx-messenger-settings-table-notify"));BX.show(BX("bx-messenger-settings-table-simpleNotify"));BX.show(BX("bx-messenger-settings-notify-clients"));this.GetSimpleNotifySettings()}else{BX.show(BX("bx-messenger-settings-table-notify"));BX.hide(BX("bx-messenger-settings-table-simpleNotify"));BX.hide(BX("bx-messenger-settings-notify-clients"));this.GetNotifySettings()}},this);t.push(BX.create("div",{props:{className:"bx-messenger-settings-notify-type"},children:[BX.create("input",{attrs:{id:"notifySchemeSimpleValue","data-save":1,type:"radio",name:"notifyScheme",value:"simple",checked:this.settings.notifyScheme=="simple"},events:{change:l}}),BX.create("label",{attrs:{"for":"notifySchemeSimpleValue"},html:" "+BX.message("IM_SETTINGS_NS_1")+" "}),BX.create("input",{attrs:{id:"notifySchemeExpertValue","data-save":1,type:"radio",name:"notifyScheme",value:"expert",checked:this.settings.notifyScheme=="expert"},events:{change:l}}),BX.create("label",{attrs:{"for":"notifySchemeExpertValue"},html:" "+BX.message("IM_SETTINGS_NS_2")+" "})]}));t.push(BX.create("div",{attrs:{id:"bx-messenger-settings-notify-clients"},style:{display:this.settings.notifyScheme=="simple"?"block":"none"},props:{className:"bx-messenger-settings-notify-clients"},children:[BX.create("div",{props:{className:"bx-messenger-settings-notify-clients-title"},html:BX.message("IM_SETTINGS_NC_1")}),BX.create("div",{props:{className:"bx-messenger-settings-notify-clients-item"},children:[BX.create("input",{attrs:{"data-save":1,type:"checkbox",id:"notifySchemeSendSite",name:"notifySchemeSendSite",value:"Y",checked:this.settings.notifySchemeSendSite}}),BX.create("label",{attrs:{"for":"notifySchemeSendSite"},html:" "+BX.message("IM_SETTINGS_NC_2")+"<br />"})]}),this.bitrixXmpp?BX.create("div",{props:{className:"bx-messenger-settings-notify-clients-item"},children:[BX.create("input",{attrs:{"data-save":1,type:"checkbox",id:"notifySchemeSendXmpp",name:"notifySchemeSendXmpp",value:"Y",checked:this.settings.notifySchemeSendXmpp}}),BX.create("label",{attrs:{"for":"notifySchemeSendXmpp"},html:" "+BX.message("IM_SETTINGS_NC_3")+"<br />"})]}):null,BX.create("div",{props:{className:"bx-messenger-settings-notify-clients-item"},children:[BX.create("input",{attrs:{"data-save":1,type:"checkbox",id:"notifySchemeSendEmail",name:"notifySchemeSendEmail",value:"Y",checked:this.settings.notifySchemeSendEmail}}),BX.create("label",{attrs:{"for":"notifySchemeSendEmail"},html:" "+BX.message("IM_SETTINGS_NC_4").replace("#MAIL#",this.userEmail)+""})]})]}))}return t};BX.IM.prototype.saveSettings=function(e){var t="";for(var s in e){this.settings[s]=e[s];t=t+s}BX.localStorage.set("ims",JSON.stringify(this.settings),5);if(this.saveSettingsTimeout[t])clearTimeout(this.saveSettingsTimeout[t]);this.saveSettingsTimeout[t]=setTimeout(BX.delegate(function(){BX.ajax({url:this.pathToAjax+"?SETTINGS_SAVE&V="+this.revision,method:"POST",dataType:"json",timeout:30,data:{IM_SETTING_SAVE:"Y",IM_AJAX_CALL:"Y",SETTINGS:JSON.stringify(e),sessid:BX.bitrix_sessid()}});delete this.saveSettingsTimeout[t]},this),700)};BX.IM.prototype.saveFormSettings=function(){var e=BX.findChildren(this.popupSettingsBody,{attribute:"data-save"},true);for(var t=0;t<e.length;t++){if(e[t].tagName=="INPUT"&&e[t].type=="checkbox"){if(typeof this.settingsSaveCallback[e[t].name]=="function")this.settings[e[t].name]=this.settingsSaveCallback[e[t].name](e[t]);else this.settings[e[t].name]=e[t].checked}else if(e[t].tagName=="INPUT"&&e[t].type=="radio"&&e[t].checked){if(typeof this.settingsSaveCallback[e[t].name]=="function")this.settings[e[t].name]=this.settingsSaveCallback[e[t].name](e[t]);else this.settings[e[t].name]=e[t].value}else if(e[t].tagName=="SELECT"){if(typeof this.settingsSaveCallback[e[t].name]=="function")this.settings[e[t].name]=this.settingsSaveCallback[e[t].name](e[t]);else this.settings[e[t].name]=e[t][e[t].selectedIndex].value}}var s=this.settings["notifyScheme"]=="simple"?{}:{notify:{}};for(var i in this.settings){if(i.substr(0,7)=="notify|"){if(s["notify"])s["notify"][i.substr(7)]=this.settings[i]}else{s[i]=this.settings[i]}}if(this.desktop.ready()){BX.desktop.onCustomEvent("bxSaveSettings",[this.settings])}else{BX.localStorage.set("ims",JSON.stringify(this.settings),5)}if(this.messenger!=null){BX.MessengerCommon.userListRedraw(true);if(this.messenger.popupMessengerTextareaSendType)this.messenger.popupMessengerTextareaSendType.innerHTML=this.settings.sendByEnter?"Enter":BX.browser.IsMac()?"&#8984;+Enter":"Ctrl+Enter"}BX.ajax({url:this.pathToAjax+"?SETTINGS_FORM_SAVE&V="+this.revision,method:"POST",dataType:"json",timeout:30,data:{IM_SETTINGS_SAVE:"Y",IM_AJAX_CALL:"Y",SETTINGS:JSON.stringify(s),sessid:BX.bitrix_sessid()},onsuccess:BX.delegate(function(){this.popupSettings.close()},this),onfailure:BX.delegate(function(){this.popupSettingsButtonSave.setClassName("popup-window-button popup-window-button-accept");this.popupSettingsButtonSave.setName(BX.message("IM_SETTINGS_SAVE"));BX.show(this.popupSettingsButtonClose.buttonNode)},this)})};BX.IM.prototype.GetNotifySettings=function(){BX.ajax({url:this.pathToAjax+"?SETTINGS_NOTIFY_LOAD&V="+this.revision,method:"POST",dataType:"json",timeout:30,data:{IM_SETTINGS_NOTIFY_LOAD:"Y",IM_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()},onsuccess:BX.delegate(function(e){if(e.ERROR==""){if(this.settings.notifyScheme=="simple"){
for(var t in e.VALUES){if(!BX("notifySchemeSendSite").checked&&t.substr(0,5)=="site|")e.VALUES[t]=false;else if(this.bitrixXmpp&&!BX("notifySchemeSendXmpp").checked&&t.substr(0,5)=="xmpp|")e.VALUES[t]=false;else if(!BX("notifySchemeSendEmail").checked&&t.substr(0,6)=="email|")e.VALUES[t]=false;this.settings["notify|"+t]=e.VALUES[t]}}else{for(var t in e.VALUES)this.settings["notify|"+t]=e.VALUES[t]}var s=[];if(e.NAMES["im"]){s.push([{type:"separator",title:e.NAMES["im"].NAME}]);for(var i in e.NAMES["im"]["NOTIFY"]){var a=e.NAMES["im"]["NOTIFY"][i];if(i=="message")s.push([{type:"text",title:a},{type:"checkbox",checked:true,disabled:true},this.bitrixXmpp?{type:"checkbox",checked:true,disabled:true}:false,{type:"checkbox",name:"notify|email|im|"+i}]);else s.push([{type:"text",title:a},{type:"checkbox",name:"notify|site|im|"+i},this.bitrixXmpp?{type:"checkbox",name:"notify|xmpp|im|"+i}:false,{type:"checkbox",name:"notify|email|im|"+i}])}delete e.NAMES["im"]}for(var n in e.NAMES){if(n=="im")continue;s.push([{type:"separator",title:e.NAMES[n].NAME}]);for(var i in e.NAMES[n]["NOTIFY"]){var a=e.NAMES[n]["NOTIFY"][i];s.push([{type:"text",title:a},{type:"checkbox",name:"notify|site|"+n+"|"+i},this.bitrixXmpp?{type:"checkbox",name:"notify|xmpp|"+n+"|"+i}:false,{type:"checkbox",name:"notify|email|"+n+"|"+i}])}}this.settingsTableConfig["notify"].rows=s}else{this.settingsTableConfig["notify"].rows=[[{type:"error",title:BX.message("IM_M_ERROR")}]]}BX("bx-messenger-settings-table-notify").innerHTML="";BX.adjust(BX("bx-messenger-settings-table-notify"),{children:[this.prepareSettingsTable("notify")]});if(e.ERROR!="")this.settingsTableConfig["notify"].rows=[];if(this.desktop.ready())this.desktop.autoResize()},this),onfailure:BX.delegate(function(){this.settingsTableConfig["notify"].rows=[[{type:"error",title:BX.message("IM_M_ERROR")}]];BX("bx-messenger-settings-table-notify").innerHTML="";BX.adjust(BX("bx-messenger-settings-table-notify"),{children:[this.prepareSettingsTable("notify")]});this.settingsTableConfig["notify"].rows=[];if(this.desktop.ready())this.desktop.autoResize()},this)})};BX.IM.prototype.GetSimpleNotifySettings=function(){BX.ajax({url:this.pathToAjax+"?SETTINGS_SIMPLE_NOTIFY_LOAD&V="+this.revision,method:"POST",dataType:"json",timeout:30,data:{IM_SETTINGS_SIMPLE_NOTIFY_LOAD:"Y",IM_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()},onsuccess:BX.delegate(function(e){if(e.ERROR==""){var t=[];for(var s in e.VALUES){t.push([{type:"separator",title:e.NAMES[s].NAME}]);for(var i in e.VALUES[s]){var a=e.NAMES[s]["NOTIFY"][i];t.push([{type:"text",title:a},{type:"link",title:BX.message("IM_SETTINGS_SNOTIFY_ENABLE"),attrs:{"data-settingName":s+"|"+i},callback:BX.delegate(function(){this.removeSimpleNotify(BX.proxy_context)},this)}]);this.settingsNotifyBlocked[s+"|"+i]=true}}this.settingsTableConfig["simpleNotify"].rows=t}else{this.settingsTableConfig["simpleNotify"].rows=[[{type:"error",title:BX.message("IM_M_ERROR")}]]}BX("bx-messenger-settings-table-simpleNotify").innerHTML="";BX.adjust(BX("bx-messenger-settings-table-simpleNotify"),{children:[this.prepareSettingsTable("simpleNotify")]});if(e.ERROR!="")this.settingsTableConfig["simpleNotify"].rows=[];if(this.desktop.ready())this.desktop.autoResize()},this),onfailure:BX.delegate(function(){this.settingsTableConfig["simpleNotify"].rows=[[{type:"error",title:BX.message("IM_M_ERROR")}]];if(BX("bx-messenger-settings-table-simpleNotify")){BX("bx-messenger-settings-table-simpleNotify").innerHTML="";BX.adjust(BX("bx-messenger-settings-table-simpleNotify"),{children:[this.prepareSettingsTable("simpleNotify")]})}this.settingsTableConfig["simpleNotify"].rows=[];if(this.desktop.ready())this.desktop.autoResize()},this)})};BX.IM.prototype.removeSimpleNotify=function(e){var t=e.parentNode.parentNode.parentNode;if(!e.parentNode.parentNode.nextSibling&&e.parentNode.parentNode.previousSibling.childNodes[0].className!="bx-messenger-settings-table-sep"){BX.remove(e.parentNode.parentNode)}else if(e.parentNode.parentNode.previousSibling&&e.parentNode.parentNode.previousSibling.childNodes[0].className!="bx-messenger-settings-table-sep"){BX.remove(e.parentNode.parentNode)}else if(e.parentNode.parentNode.nextSibling&&e.parentNode.parentNode.nextSibling.childNodes[0].className!="bx-messenger-settings-table-sep"){BX.remove(e.parentNode.parentNode)}else if(e.parentNode.parentNode.previousSibling.childNodes[0].className=="bx-messenger-settings-table-sep"&&!e.parentNode.parentNode.nextSibling){BX.remove(e.parentNode.parentNode.previousSibling);BX.remove(e.parentNode.parentNode)}else if(e.parentNode.parentNode.previousSibling.childNodes[0].className=="bx-messenger-settings-table-sep"&&e.parentNode.parentNode.nextSibling.childNodes[0].className=="bx-messenger-settings-table-sep"){BX.remove(e.parentNode.parentNode.previousSibling);BX.remove(e.parentNode.parentNode)}if(t.childNodes.length<=1)BX.remove(t);this.notify.blockNotifyType(e.getAttribute("data-settingName"));if(this.desktop.ready())this.desktop.autoResize()};BX.IM.prototype.openConfirm=function(e,t,s){if(this.popupConfirm!=null)this.popupConfirm.destroy();if(typeof e=="object")e='<div class="bx-messenger-confirm-title">'+e.title+"</div>"+e.message;s=s!==false;if(typeof t=="undefined"||typeof t=="object"&&t.length<=0){t=[new BX.PopupWindowButton({text:BX.message("IM_NOTIFY_CONFIRM_CLOSE"),className:"popup-window-button-decline",events:{click:function(e){this.popupWindow.close();BX.PreventDefault(e)}}})]}this.popupConfirm=new BX.PopupWindow("bx-notifier-popup-confirm",null,{zIndex:200,autoHide:t===false,buttons:t,closeByEsc:t===false,overlay:s,events:{onPopupClose:function(){this.destroy()},onPopupDestroy:BX.delegate(function(){this.popupConfirm=null},this)},content:BX.create("div",{props:{className:t===false?" bx-messenger-confirm-without-buttons":"bx-messenger-confirm"},html:e})});BX.addClass(this.popupConfirm.popupContainer,"bx-messenger-mark");this.popupConfirm.show();BX.bind(this.popupConfirm.popupContainer,"click",BX.MessengerCommon.preventDefault);BX.bind(this.popupConfirm.contentContainer,"click",BX.PreventDefault);BX.bind(this.popupConfirm.overlay.element,"click",BX.PreventDefault)};BX.IM.getSelectionText=function(){var e="";if(window.getSelection){e=window.getSelection().toString()}else{e=document.selection.createRange().text}return e};BX.IM.prototype.getLocalConfig=function(e,t){if(this.desktop.ready()){return BX.desktop.getLocalConfig(e,t)}t=typeof t=="undefined"?null:t;if(!BX.browser.SupportLocalStorage()){return t}if(this.desktop.run()&&!this.desktop.ready())e="full-"+e;var s=BX.localStorage.get(e);if(s==null){return t}if(typeof s=="string"&&s.length>0){try{s=JSON.parse(s)}catch(i){s=t}}return s};BX.IM.prototype.setLocalConfig=function(e,t){if(this.desktop.run()){if(this.desktop.ready())return BX.desktop.setLocalConfig(e,t);else return false}if(typeof t=="object")t=JSON.stringify(t);else if(typeof t=="boolean")t=t?"true":"false";else if(typeof t=="undefined")t="";else if(typeof t!="string")t=t+"";if(!BX.browser.SupportLocalStorage())return false;if(this.desktop.run()&&!this.desktop.ready())e="full-"+e;BX.localStorage.set(e,t,86400);return true};BX.IM.prototype.removeLocalConfig=function(e){if(this.desktop.ready()){return BX.desktop.removeLocalConfig(e)}if(!BX.browser.SupportLocalStorage())return false;if(this.desktop.run()&&!this.desktop.ready())e="full-"+e;BX.localStorage.remove(e);return true};BX.IM.prototype.storageSet=function(e){if(e.key=="mps"){this.stopSound()}else if(e.key=="mrs"){this.repeatSound(e.value.sound,e.value.time)}else if(e.key=="mrss"){this.stopRepeatSound(e.value.sound,false)}}})();(function(){if(BX.Notify)return;BX.Notify=function(e,t){this.BXIM=e;this.settings={};this.params=t||{};this.windowInnerSize={};this.windowScrollPos={};this.sendAjaxTry=0;this.webrtc=t.webrtcClass;this.desktop=t.desktopClass;this.panel=t.domNode;if(this.desktop.run())BX.hide(this.panel);BX.bind(this.panel,"click",BX.MessengerCommon.preventDefault);this.notifyCount=t.countNotify;this.notifyUpdateCount=t.countNotify;this.counters=t.counters;this.mailCount=t.mailCount;this.notifyHistoryPage=0;this.notifyHistoryLoad=false;this.notifyBody=null;this.notify=t.notify;this.notifyLoad=false;this.unreadNotify=t.unreadNotify;this.unreadNotifyLoad=t.loadNotify;this.flashNotify=t.flashNotify;this.initNotifyCount=t.countNotify;this.confirmDisabledButtons=false;if(this.unreadNotifyLoad){for(var s in this.notify)this.initNotifyCount--}if(BX.browser.IsDoctype())BX.addClass(this.panel,"bx-notifier-panel-doc");else BX.addClass(document.body,"bx-no-doctype");this.panelButtonCall=BX.findChildByClassName(this.panel,"bx-notifier-call");if(!this.webrtc.phoneEnabled){BX.style(this.panelButtonCall,"display","none")}this.panelButtonNetwork=BX.findChildByClassName(this.panel,"bx-notifier-network");this.panelButtonNetworkCount=BX.findChildByClassName(this.panelButtonNetwork,"bx-notifier-indicator-count");if(this.panelButtonNetwork!=null){if(this.BXIM.bitrixNetworkStatus){this.panelButtonNetwork.href="https://www.bitrix24.net/";this.panelButtonNetwork.setAttribute("target","_blank");if(this.panelButtonNetworkCount!=null)this.panelButtonNetworkCount.innerHTML=""}else{BX.style(this.panelButtonNetwork,"display","none");this.panelButtonNetworkCount.innerHTML=""}}this.panelButtonNotify=BX.findChildByClassName(this.panel,"bx-notifier-notify");this.panelButtonNotifyCount=BX.findChildByClassName(this.panelButtonNotify,"bx-notifier-indicator-count");if(this.panelButtonNotifyCount!=null)this.panelButtonNotifyCount.innerHTML="";this.panelButtonMessage=BX.findChildByClassName(this.panel,"bx-notifier-message");this.panelButtonMessageCount=BX.findChildByClassName(this.panelButtonMessage,"bx-notifier-indicator-count");if(this.panelButtonMessageCount!=null)this.panelButtonMessageCount.innerHTML="";this.panelButtonMail=BX.findChildByClassName(this.panel,"bx-notifier-mail");this.panelButtonMailCount=BX.findChildByClassName(this.panelButtonMail,"bx-notifier-indicator-count");if(this.panelButtonMail!=null){this.panelButtonMail.href=this.BXIM.path.mail;this.panelButtonMail.setAttribute("target","_blank");if(this.panelButtonMessageCount!=null)this.panelButtonMailCount.innerHTML=""}this.panelDragLabel=BX.findChildByClassName(this.panel,"bx-notifier-drag");this.messenger=null;this.messengerNotifyButton=null;this.messengerNotifyButtonCount=null;this.popupNotifyItem=null;this.popupNotifySize=383;this.popupNotifySizeDefault=383;this.popupNotifyButtonFilter=null;this.popupNotifyButtonFilterBox=null;this.popupHistoryFilterVisible=false;this.popupNotifyMore=null;this.dragged=false;this.dragPageX=0;this.dragPageY=0;if(this.BXIM.init){if(this.desktop.run()){BX.desktop.addTab({id:"notify",title:BX.message("IM_SETTINGS_NOTIFY"),order:110,target:"im",events:{open:BX.delegate(function(){this.openNotify(false,true)},this)}})}this.panel.appendChild(this.BXIM.audio.reminder=BX.create("audio",{props:{className:"bx-notify-audio"},children:[BX.create("source",{attrs:{src:"/bitrix/js/im/audio/reminder.ogg",type:"audio/ogg; codecs=vorbis"}}),BX.create("source",{attrs:{src:"/bitrix/js/im/audio/reminder.mp3",type:"audio/mpeg"}})]}));if(typeof this.BXIM.audio.reminder.play=="undefined"){this.BXIM.settings.enableSound=false}if(BX.browser.SupportLocalStorage()){BX.addCustomEvent(window,"onLocalStorageSet",BX.proxy(this.storageSet,this));var i=BX.localStorage.get("npp");this.BXIM.settings.panelPositionHorizontal=!!i?i.h:this.BXIM.settings.panelPositionHorizontal;this.BXIM.settings.panelPositionVertical=!!i?i.v:this.BXIM.settings.panelPositionVertical;var a=BX.localStorage.get("mfn");if(a){for(var s in this.flashNotify)if(this.flashNotify[s]!=a[s]&&a[s]==false)this.flashNotify[s]=false}BX.garbage(function(){BX.localStorage.set("mfn",this.flashNotify,15)},this)}BX.bind(this.panelButtonNotify,"click",BX.proxy(function(){this.toggleNotify()},this.BXIM));if(this.webrtc.phoneEnabled){BX.bind(this.panelButtonCall,"click",BX.delegate(this.webrtc.openKeyPad,this.webrtc));BX.bind(window,"scroll",BX.delegate(function(){if(this.webrtc.popupKeyPad)this.webrtc.popupKeyPad.close()},this))}BX.bind(this.panelDragLabel,"mousedown",BX.proxy(this._startDrag,this));BX.bind(this.panelDragLabel,"dobleclick",BX.proxy(this._stopDrag,this));this.updateNotifyMailCount();if(!this.desktop.run()){this.adjustPosition({resize:true});BX.bind(window,"resize",BX.proxy(function(){this.closePopup();this.adjustPosition({resize:true})},this));if(!BX.browser.IsDoctype())BX.bind(window,"scroll",BX.proxy(function(){this.adjustPosition({scroll:true})},this))}setTimeout(BX.delegate(function(){this.newNotify();this.updateNotifyCounters();this.updateNotifyCount()},this),500)}BX.addCustomEvent(window,"onSonetLogCounterClear",BX.proxy(function(e){var t={};t[e]=0;this.updateNotifyCounters(t)},this))};BX.Notify.prototype.getCounter=function(e){if(typeof e!="string")return false;e=e.toString();if(e=="im_notify")return this.notifyCount;if(e=="im_message")return this.BXIM.messageCount;return this.counters[e]?this.counters[e]:0};BX.Notify.prototype.updateNotifyCounters=function(e,t){t=t!=false;if(typeof e=="object"){for(var s in e)this.counters[s]=e[s]}BX.onCustomEvent(window,"onImUpdateCounter",[this.counters]);if(t)BX.localStorage.set("nuc",this.counters,5)};BX.Notify.prototype.updateNotifyMailCount=function(e,t){t=t!=false;if(typeof e!="undefined"||parseInt(e)>0)this.mailCount=parseInt(e);if(this.mailCount>0)BX.removeClass(this.panelButtonMail,"bx-notifier-hide");else BX.addClass(this.panelButtonMail,"bx-notifier-hide");var s="";if(this.mailCount>99)s="99+";else if(this.mailCount>0)s=this.mailCount;if(this.panelButtonMailCount!=null){this.panelButtonMailCount.innerHTML=s;this.adjustPosition({resize:true,timeout:500})}BX.onCustomEvent(window,"onImUpdateCounterMail",[this.mailCount,"MAIL"]);if(t)BX.localStorage.set("numc",this.mailCount,5)};BX.Notify.prototype.updateNotifyCount=function(e){e=e!=false;var t=0;var s=0;if(this.unreadNotifyLoad)t=this.initNotifyCount;for(var i in this.unreadNotify){if(this.unreadNotify[i]==null)continue;var a=this.notify[this.unreadNotify[i]];if(!a)continue;if(a.type!=1)s++;t++}var n="";if(t>99)n="99+";else if(t>0)n=t;if(this.panelButtonNotifyCount!=null){this.panelButtonNotifyCount.innerHTML=n;this.adjustPosition({resize:true,timeout:500})}if(this.messengerNotifyButtonCount!=null)this.messengerNotifyButtonCount.innerHTML=parseInt(n)>0?'<span class="bx-messenger-cl-count-digit">'+n+"</span>":"";if(this.desktop.run())BX.desktop.setTabBadge("notify",t);this.notifyCount=parseInt(t);this.notifyUpdateCount=parseInt(s);BX.onCustomEvent(window,"onImUpdateCounterNotify",[this.notifyCount,"NOTIFY"]);if(e)BX.localStorage.set("nunc",{unread:this.unreadNotify,flash:this.flashNotify},5)};BX.Notify.prototype.changeUnreadNotify=function(e,t){t=t!=false;var s=false;for(var i in e){if(!this.BXIM.xmppStatus&&this.BXIM.settings.status!="dnd")this.flashNotify[e[i]]=true;else this.flashNotify[e[i]]=false;this.unreadNotify[e[i]]=e[i];s=true}this.newNotify(t);if(s&&this.BXIM.notifyOpen)this.openNotify(true);this.updateNotifyCount(t)};BX.Notify.prototype.viewNotify=function(e){if(parseInt(e)<=0)return false;var t=this.notify[e];if(t&&t.type!=1)delete this.unreadNotify[e];delete this.flashNotify[e];BX.localStorage.set("mfn",this.flashNotify,80);BX.ajax({url:this.BXIM.pathToAjax+"?NOTIFY_VIEW&V="+this.BXIM.revision,method:"POST",dataType:"json",timeout:60,data:{IM_NOTIFY_VIEW:"Y",ID:parseInt(e),IM_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()}});if(this.BXIM.notifyOpen){var s=BX.findChildrenByClassName(this.popupNotifyItem,"bx-notifier-item-new",false);if(s!=null)for(var i=0;i<s.length;i++)BX.removeClass(s[i],"bx-notifier-item-new")}this.updateNotifyCount(false);return true};BX.Notify.prototype.viewNotifyAll=function(){var e=0;for(var t in this.unreadNotify){var s=this.notify[t];if(s&&s.type!=1)delete this.unreadNotify[t];delete this.flashNotify[t];e=e<t?t:e}if(parseInt(e)<=0)return false;BX.localStorage.set("mfn",this.flashNotify,80);BX.ajax({url:this.BXIM.pathToAjax+"?NOTIFY_VIEWED&V="+this.BXIM.revision,method:"POST",dataType:"json",timeout:60,data:{IM_NOTIFY_VIEWED:"Y",MAX_ID:parseInt(e),IM_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()}});if(this.BXIM.notifyOpen){var i=BX.findChildrenByClassName(this.popupNotifyItem,"bx-notifier-item-new",false);if(i!=null){for(var t=0;t<i.length;t++){if(i[t].getAttribute("data-notifyType")!=1)BX.removeClass(i[t],"bx-notifier-item-new")}}}this.updateNotifyCount(false);return true};BX.Notify.prototype.newNotify=function(e){e=e!=false;var t=[];var s=[];var i=[];for(var a in this.flashNotify){if(this.flashNotify[a]===true){i.push(parseInt(a));this.flashNotify[a]=false}}var n={};i.sort(BX.delegate(function(e,t){if(!this.notify[e]||!this.notify[t]){return 0}var s=parseInt(this.notify[e].date);var i=parseInt(this.notify[t].date);var a=parseInt(this.notify[e].type);var n=parseInt(this.notify[t].type);if(a==1&&n!=1){return-1}else if(n==1&&a!=1){return 1}else if(i>s){return 1}else if(i<s){return-1}else{return 0}},this));for(var a=0;a<i.length;a++){var o=this.notify[i[a]];if(o&&o.userId&&o.userName)n[o.userId]=o.userName;o=this.createNotify(this.notify[i[a]],true);if(o!==false){t.push(o);o=this.notify[i[a]];s.push({title:o.userName?BX.util.htmlspecialcharsback(o.userName):BX.message("IM_NOTIFY_WINDOW_NEW_TITLE"),text:BX.util.htmlspecialcharsback(o.text).split("<br />").join("\n").replace(/<\/?[^>]+>/gi,""),icon:o.userAvatar?o.userAvatar:"",tag:"im-notify-"+o.tag})}}if(t.length>5){var r="";for(var a in n)r+=", <i>"+n[a]+"</i>";var o={id:0,type:4,date:+new Date/1e3,tag:"",original_tag:"",title:BX.message("IM_NM_NOTIFY_1").replace("#COUNT#",t.length),text:r.length>0?BX.message("IM_NM_NOTIFY_2").replace("#USERS#",r.substr(2)):BX.message("IM_NM_NOTIFY_3")};o=this.createNotify(o,true);BX.style(o,"cursor","pointer");t=[o];s=[{id:"",title:BX.message("IM_NM_NOTIFY_1").replace("#COUNT#",t.length),text:r.length>0?BX.message("IM_NM_NOTIFY_2").replace("#USERS#",BX.util.htmlspecialcharsback(r.substr(2))).replace(/<\/?[^>]+>/gi,""):BX.message("IM_NM_NOTIFY_3")}]}if(t.length==0)return false;if(this.desktop.ready())BX.desktop.flashIcon(false);this.closePopup();if(!(!this.desktop.ready()&&this.desktop.run())&&(this.BXIM.settings.status=="dnd"||!this.desktop.ready()&&this.BXIM.desktopStatus))return false;if(e&&!this.BXIM.xmppStatus)this.BXIM.playSound("reminder");if(e&&this.desktop.ready()){for(var a=0;a<t.length;a++){var l=t[a].getAttribute("data-notifyId");var p='var notify = BX.findChildByClassName(document.body, "bx-notifier-item");'+'BX.bind(BX.findChildByClassName(notify, "bx-notifier-item-delete"), "click", function(event){ if (this.getAttribute("data-notifyType") != 1) { BX.desktop.onCustomEvent("main", "bxImClickCloseNotify", [this.getAttribute("data-notifyId")]); } BX.desktop.windowCommand("close"); BX.MessengerCommon.preventDefault(event); });'+(t[a].id>0?"":'BX.bind(notify, "click", function(event){ BX.desktop.onCustomEvent("main", "bxImClickNotify", []); BX.desktop.windowCommand("close"); BX.MessengerCommon.preventDefault(event); });')+'BX.bindDelegate(notify, "click", {className: "bx-notifier-item-button"}, BX.delegate(function(){ '+'BX.desktop.windowCommand("freeze");'+'notifyId = BX.proxy_context.getAttribute("data-id");'+"BXIM.notify.confirmRequest({"+'"notifyId": notifyId,'+'"notifyValue": BX.proxy_context.getAttribute("data-value"),'+'"notifyURL": BX.proxy_context.getAttribute("data-url"),'+'"notifyTag": BXIM.notify.notify[notifyId] && BXIM.notify.notify[notifyId].tag? BXIM.notify.notify[notifyId].tag: null,'+'"groupDelete": BX.proxy_context.getAttribute("data-group") == null? false: true,'+"}, true);"+'BX.desktop.onCustomEvent("main", "bxImClickConfirmNotify", [notifyId]); '+"}, BXIM.notify));"+'BX.bind(notify, "contextmenu", function(){ BX.desktop.windowCommand("close")});';this.desktop.openNewNotify(l,t[a],p)}}else if(e&&!this.BXIM.windowFocus&&this.BXIM.notifyManager.nativeNotifyGranted()){for(var a=0;a<s.length;a++){var o=s[a];o.onshow=function(){var e=this;setTimeout(function(){e.close()},5e3)};o.onclick=function(){window.focus();top.BXIM.openNotify();this.close()};this.BXIM.notifyManager.nativeNotify(o)}}else{if(this.BXIM.windowFocus&&this.BXIM.notifyManager.nativeNotifyGranted()){BX.localStorage.set("mnnb",true,1)}for(var a=0;a<t.length;a++){this.BXIM.notifyManager.add({html:t[a],tag:t[a].id>0?"im-notify-"+this.notify[t[a].getAttribute("data-notifyId")].tag:"",originalTag:t[a].id>0?this.notify[t[a].getAttribute("data-notifyId")].original_tag:"",notifyId:t[a].getAttribute("data-notifyId"),notifyType:t[a].getAttribute("data-notifyType"),click:t[a].id>0?null:BX.delegate(function(e){this.openNotify();e.close()},this),close:BX.delegate(function(e){if(e.notifyParams.notifyType!=1&&e.notifyParams.notifyId)this.viewNotify(e.notifyParams.notifyId)},this)})}}return true};BX.Notify.prototype.confirmRequest=function(e,t){if(this.confirmDisabledButtons)return false;t=t==true;e.notifyOriginTag=this.notify[e.notifyId]?this.notify[e.notifyId].original_tag:"";if(e.groupDelete&&e.notifyTag!=null){for(var s in this.notify){if(this.notify[s].tag==e.notifyTag)delete this.notify[s]}}else delete this.notify[e.notifyId];this.updateNotifyCount();if(t&&this.desktop.ready())BX.desktop.windowCommand("freeze");else BX.hide(BX.proxy_context.parentNode.parentNode.parentNode);BX.ajax({url:this.BXIM.pathToAjax+"?NOTIFY_CONFIRM&V="+this.BXIM.revision,method:"POST",dataType:"json",timeout:30,data:{IM_NOTIFY_CONFIRM:"Y",NOTIFY_ID:e.notifyId,NOTIFY_VALUE:e.notifyValue,IM_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()},onsuccess:BX.delegate(function(){if(e.notifyURL!=null){if(t&&this.desktop.ready())BX.desktop.browse(e.notifyURL);else location.href=e.notifyURL;this.confirmDisabledButtons=true}BX.onCustomEvent(window,"onImConfirmNotify",[{NOTIFY_ID:e.notifyId,NOTIFY_TAG:e.notifyOriginTag,NOTIFY_VALUE:e.notifyValue}]);if(t&&this.desktop.ready())BX.desktop.windowCommand("close")},this),onfailure:BX.delegate(function(){if(this.desktop.ready())BX.desktop.windowCommand("close")},this)});if(e.groupDelete)BX.localStorage.set("nrgn",e.notifyTag,5);else BX.localStorage.set("nrn",e.notifyId,5);return false};BX.Notify.prototype.drawNotify=function(e,t){t=t==true;var s=typeof e=="object"?e:BX.clone(this.notify);var i={};var a={};for(var n in s){if(s[n].tag!=""){if(!a[s[n].tag]||!a[s[n].tag][s[n].userId]){if(a[s[n].tag]){if(!a[s[n].tag][s[n].userId])a[s[n].tag][s[n].userId]=s[n].id;if(parseInt(i[s[n].tag].date)<=parseInt(s[n].date)){s[n].groupped=true;delete s[i[s[n].tag].id];i[s[n].tag]=s[n]}else{s[i[s[n].tag].id].groupped=true;delete s[n]}}else{a[s[n].tag]={};a[s[n].tag][s[n].userId]=s[n].id;i[s[n].tag]=s[n]}}else{if(parseInt(i[s[n].tag].date)<=parseInt(s[n].date)){s[n].groupped=true;delete s[i[s[n].tag].id];i[s[n].tag]=s[n]}else{s[i[s[n].tag].id].groupped=true;delete s[n]}}}}var o=[];var r=[];for(var n in s)r.push(parseInt(n));r.sort(function(e,t){if(!s[e]||!s[t]){return 0}var i=parseInt(s[e].date);var a=parseInt(s[t].date);var n=parseInt(s[e].type);var o=parseInt(s[t].type);if(n==1&&o!=1){return-1}else if(o==1&&n!=1){return 1}else if(a>i){return 1}else if(a<i){return-1}else{return 0}});for(var n=0;n<r.length;n++){var l=s[r[n]];if(l.groupped){l.otherCount=0;if(this.notify[l.id]){this.notify[l.id].otherItems=[];for(var p in a[l.tag]){if(this.notify[l.id].userId!=p)this.notify[l.id].otherItems.push(a[l.tag][p])}l.otherCount=this.notify[l.id].otherItems.length}if(l.otherCount>0&&l.type==2)l.type=3}l=this.createNotify(l);if(l!==false)o.push(l)}if(o.length==0){if(this.messenger.popupMessengerConnectionStatusState!="online"){o.push(BX.create("div",{attrs:{style:"padding-top: 231px; margin-bottom: 45px;"},props:{className:"bx-messenger-box-empty bx-notifier-content-empty",id:"bx-notifier-content-empty"},html:BX.message("IM_NOTIFY_ERROR")}));o.push(BX.create("a",{attrs:{href:"#notifyHistory",id:"bx-notifier-content-link-history"},props:{className:"bx-notifier-content-link-history bx-notifier-content-link-history-empty"},children:[BX.create("span",{props:{className:"bx-notifier-item-button bx-notifier-item-button-white"},html:BX.message("IM_NOTIFY_HISTORY_2")})]}));this.notifyLoad=false}else if(this.BXIM.settings.loadLastNotify&&!this.notifyLoad||this.unreadNotifyLoad){o.push(BX.create("div",{attrs:{style:"padding-top: 162px;"},props:{className:"bx-notifier-content-load",id:"bx-notifier-content-load"},children:[BX.create("div",{props:{className:"bx-notifier-content-load-block bx-notifier-item"},children:[BX.create("span",{props:{className:"bx-notifier-content-load-block-img"}}),BX.create("span",{props:{className:"bx-notifier-content-load-block-text"},html:BX.message("IM_NOTIFY_LOAD_NOTIFY")})]})]}))}else if(!t&&!this.BXIM.settings.loadLastNotify){o.push(BX.create("div",{attrs:{style:"padding-top: 231px; margin-bottom: 45px;"},props:{className:"bx-messenger-box-empty bx-notifier-content-empty",id:"bx-notifier-content-empty"},html:BX.message("IM_NOTIFY_EMPTY_2")}));o.push(BX.create("a",{attrs:{href:"#notifyHistory",id:"bx-notifier-content-link-history"},props:{className:"bx-notifier-content-link-history bx-notifier-content-link-history-empty"},children:[BX.create("span",{props:{className:"bx-notifier-item-button bx-notifier-item-button-white"},html:BX.message("IM_NOTIFY_HISTORY")})]}))}else if(!t){o.push(BX.create("div",{attrs:{style:"padding-top: 231px; margin-bottom: 45px;"},props:{className:"bx-messenger-box-empty bx-notifier-content-empty",id:"bx-notifier-content-empty"},html:BX.message("IM_NOTIFY_EMPTY_3")}));o.push(BX.create("a",{attrs:{href:"#notifyHistory",id:"bx-notifier-content-link-history"},props:{className:"bx-notifier-content-link-history bx-notifier-content-link-history-empty"},children:[BX.create("span",{props:{className:"bx-notifier-item-button bx-notifier-item-button-white"},html:BX.message("IM_NOTIFY_HISTORY_LATE")})]}))}if(this.BXIM.settings.loadLastNotify)return o}else if(!t){o.push(BX.create("a",{attrs:{href:"#notifyHistory",id:"bx-notifier-content-link-history"},props:{className:"bx-notifier-content-link-history bx-notifier-content-link-history-empty"},children:[BX.create("span",{props:{className:"bx-notifier-item-button bx-notifier-item-button-white"},html:BX.message("IM_NOTIFY_HISTORY_LATE")})]}))}return o};BX.Notify.prototype.openNotify=function(e,t){e=e==true;t=t==true;if(this.messenger.popupMessenger==null)this.messenger.openMessenger(false);if(this.BXIM.notifyOpen&&!t){if(!e){this.messenger.extraClose(true);return false}}else{this.BXIM.dialogOpen=false;this.BXIM.notifyOpen=true;if(!this.desktop.run()){this.messengerNotifyButton.className="bx-messenger-cl-notify-button bx-messenger-cl-notify-button-active"}}this.messenger.closeMenuPopup();this.webrtc.callOverlayToggleSize(true);var s=this.drawNotify();this.notifyBody=BX.create("div",{props:{className:"bx-notifier-wrap"},children:[BX.create("div",{props:{className:"bx-messenger-panel"},children:[BX.create("span",{props:{className:"bx-messenger-panel-avatar bx-messenger-avatar-notify"}}),this.popupNotifyButtonFilter=BX.create("a",{props:{className:"bx-messenger-panel-filter bx-messenger-panel-filter-notify"},html:this.popupNotifyFilterVisible?BX.message("IM_PANEL_FILTER_OFF"):BX.message("IM_PANEL_FILTER_ON")}),BX.create("span",{props:{className:"bx-messenger-panel-title bx-messenger-panel-title-middle"},html:BX.message("IM_NOTIFY_WINDOW_TITLE")})]}),this.popupNotifyButtonFilterBox=BX.create("div",{props:{className:"bx-messenger-panel-filter-box"},style:{display:this.popupNotifyFilterVisible?"block":"none"},children:[BX.create("div",{props:{className:"bx-messenger-filter-name"},html:BX.message("IM_PANEL_FILTER_NAME")}),this.popupHistorySearchDateWrap=BX.create("div",{props:{className:"bx-messenger-filter-date bx-messenger-input-wrap bx-messenger-filter-date-notify"},html:'<span class="bx-messenger-input-date"></span><a class="bx-messenger-input-close" href="#close"></a><input type="text" class="bx-messenger-input" value="" tabindex="1002" placeholder="'+BX.message("IM_PANEL_FILTER_DATE")+'" />'})]}),this.popupNotifyItem=BX.create("div",{props:{className:"bx-notifier-item-wrap"},style:{height:this.popupNotifySize+"px"},children:s})]});this.messenger.extraOpen(this.notifyBody);this.BXIM.notifyManager.nativeNotifyAccessForm();if(this.unreadNotifyLoad)this.loadNotify();else if(!this.notifyLoad&&this.BXIM.settings.loadLastNotify)this.notifyHistory();if(!e&&this.BXIM.isFocus("notify")&&this.notifyUpdateCount>0)this.viewNotifyAll();BX.bind(this.popupNotifyButtonFilter,"click",BX.delegate(function(){if(this.popupNotifyFilterVisible){this.popupNotifyButtonFilter.innerHTML=BX.message("IM_PANEL_FILTER_ON");this.popupNotifySize=this.popupNotifySize+this.popupNotifyButtonFilterBox.offsetHeight;this.popupNotifyItem.style.height=this.popupNotifySize+"px";BX.style(this.popupNotifyButtonFilterBox,"display","none");this.popupNotifyFilterVisible=false}else{this.popupNotifyButtonFilter.innerHTML=BX.message("IM_PANEL_FILTER_OFF");BX.style(this.popupNotifyButtonFilterBox,"display","block");this.popupNotifySize=this.popupNotifySize-this.popupNotifyButtonFilterBox.offsetHeight;this.popupNotifyItem.style.height=this.popupNotifySize+"px";this.popupNotifyFilterVisible=true}},this));BX.bind(this.popupNotifyItem,"scroll",BX.delegate(function(){if(this.messenger.popupPopupMenu!=null)this.messenger.popupPopupMenu.close()},this));BX.bind(BX("bx-notifier-content-link-history"),"click",BX.delegate(this.notifyHistory,this));BX.bind(this.popupNotifyItem,"click",BX.delegate(this.closePopup,this));BX.bindDelegate(this.popupNotifyItem,"click",{className:"bx-notifier-item-help"},BX.proxy(function(e){if(this.popupNotifyMore!=null)this.popupNotifyMore.destroy();else{var t=this.notify[BX.proxy_context.getAttribute("data-help")];if(!t.otherItems)return false;var s='<span class="bx-notifier-item-help-popup">';for(var i=0;i<t.otherItems.length;i++){var a=BX.MessengerCommon.getUserParam(this.notify[t.otherItems[i]].userId);s+='<a class="bx-notifier-item-help-popup-img" href="'+this.notify[t.otherItems[i]].userLink+'"  onclick="BXIM.openMessenger('+this.notify[t.otherItems[i]].userId+'); return false;" target="_blank"><span class="bx-notifier-popup-avatar bx-notifier-popup-avatar-status-'+a.status+'"><img class="bx-notifier-popup-avatar-img'+(BX.MessengerCommon.isBlankAvatar(this.notify[t.otherItems[i]].userAvatar)?" bx-notifier-popup-avatar-img-default":"")+'" src="'+this.notify[t.otherItems[i]].userAvatar+'"></span><span class="bx-notifier-item-help-popup-name '+(a.extranet?" bx-notifier-popup-avatar-extranet":"")+'">'+BX.MessengerCommon.prepareText(this.notify[t.otherItems[i]].userName)+"</span></a>"}s+="</span>";this.popupNotifyMore=new BX.PopupWindow("bx-notifier-other-window",BX.proxy_context,{zIndex:200,lightShadow:true,offsetTop:-2,offsetLeft:3,autoHide:true,closeByEsc:true,bindOptions:{position:"top"},events:{onPopupClose:function(){this.destroy()},onPopupDestroy:BX.proxy(function(){this.popupNotifyMore=null},this)},content:BX.create("div",{props:{className:"bx-messenger-popup-menu"},html:s})});this.popupNotifyMore.setAngle({});this.popupNotifyMore.show();BX.bind(this.popupNotifyMore.popupContainer,"click",BX.MessengerCommon.preventDefault)}return BX.PreventDefault(e)},this));BX.bindDelegate(this.popupNotifyItem,"click",{className:"bx-notifier-item-delete"},BX.proxy(function(e){if(!BX.proxy_context)return;BX.proxy_context.setAttribute("id","bx-notifier-item-delete-"+BX.proxy_context.getAttribute("data-notifyId"));this.deleteNotify(BX.proxy_context.getAttribute("data-notifyId"));return BX.PreventDefault(e)},this));BX.bindDelegate(this.popupNotifyItem,"click",{className:"bx-notifier-item-button"},BX.proxy(function(e){if(this.messenger.popupMessengerConnectionStatusState!="online")return false;var t=BX.proxy_context.getAttribute("data-id");this.confirmRequest({notifyId:t,notifyValue:BX.proxy_context.getAttribute("data-value"),notifyURL:BX.proxy_context.getAttribute("data-url"),notifyTag:this.notify[t]&&this.notify[t].tag?this.notify[t].tag:null,groupDelete:BX.proxy_context.getAttribute("data-group")!=null
});if(BX.proxy_context.parentNode.parentNode.parentNode.previousSibling==null&&BX.proxy_context.parentNode.parentNode.parentNode.nextSibling==null)this.openNotify(true);else if(BX.proxy_context.parentNode.parentNode.parentNode.previousSibling==null&&BX.proxy_context.parentNode.parentNode.parentNode.nextSibling.tagName.toUpperCase()=="A")this.openNotify(true);else BX.remove(BX.proxy_context.parentNode.parentNode.parentNode);return BX.PreventDefault(e)},this));if(this.desktop.ready()){BX.bindDelegate(this.popupNotifyItem,"contextmenu",{className:"bx-notifier-item-content"},BX.delegate(function(e){this.messenger.openPopupMenu(e,"notify",false);return BX.PreventDefault(e)},this))}else{BX.bindDelegate(this.popupNotifyItem,"contextmenu",{className:"bx-notifier-item-delete"},BX.proxy(function(e){if(!BX.proxy_context)return;BX.proxy_context.setAttribute("id","bx-notifier-item-delete-"+BX.proxy_context.getAttribute("data-notifyId"));this.messenger.openPopupMenu(BX.proxy_context,"notifyDelete");return BX.PreventDefault(e)},this))}return false};BX.Notify.prototype.deleteNotify=function(e){var t=BX("bx-notifier-item-delete-"+e);var s=false;if(this.notify[e]){s=true;var i=null;if(this.notify[e].tag)i=this.notify[e].tag;var a=!(t.getAttribute("data-group")==null||i==null);if(a){for(var n in this.notify){if(this.notify[n].tag==i)delete this.notify[n]}}else delete this.notify[e]}this.updateNotifyCount();if(s){var o={};if(a)o={IM_NOTIFY_GROUP_REMOVE:"Y",NOTIFY_ID:e,IM_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()};else o={IM_NOTIFY_REMOVE:"Y",NOTIFY_ID:e,IM_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()};BX.ajax({url:this.BXIM.pathToAjax+"?NOTIFY_REMOVE&V="+this.BXIM.revision,method:"POST",dataType:"json",timeout:30,data:o});if(a)BX.localStorage.set("nrgn",i,5);else BX.localStorage.set("nrn",e,5)}if(t.parentNode.parentNode.previousSibling==null&&t.parentNode.parentNode.nextSibling==null){this.openNotify(true)}else if(t.parentNode.parentNode.previousSibling==null&&t.parentNode.parentNode.nextSibling.tagName.toUpperCase()=="A"){this.notifyLoad=false;this.notifyHistoryPage=0;this.openNotify(true)}else BX.remove(t.parentNode.parentNode);return true};BX.Notify.prototype.blockNotifyType=function(e){var t=typeof this.BXIM.settingsNotifyBlocked[e]=="undefined";BX.ajax({url:this.BXIM.pathToAjax+"?NOTIFY_BLOCK_TYPE&V="+this.BXIM.revision,method:"POST",dataType:"json",timeout:30,data:{IM_NOTIFY_BLOCK_TYPE:"Y",BLOCK_TYPE:e,BLOCK_RESULT:t?"Y":"N",IM_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()}});if(t){this.BXIM.settingsNotifyBlocked[e]=true;this.BXIM.settings["site|".settingName]=false;this.BXIM.settings["xmpp|".settingName]=false;this.BXIM.settings["email|".settingName]=false}else{delete this.BXIM.settingsNotifyBlocked[e];this.BXIM.settings["site|".settingName]=true;this.BXIM.settings["xmpp|".settingName]=true;this.BXIM.settings["email|".settingName]=true}return true};BX.Notify.prototype.closeNotify=function(){if(!this.desktop.run()){this.messengerNotifyButton.className="bx-messenger-cl-notify-button"}this.BXIM.notifyOpen=false;this.popupNotifyItem=null;BX.unbindAll(this.popupNotifyButtonFilter);BX.unbindAll(this.popupNotifyItem)};BX.Notify.prototype.loadNotify=function(e){if(this.loadNotityBlock)return false;e=e!=false;this.loadNotityBlock=true;BX.ajax({url:this.BXIM.pathToAjax+"?NOTIFY_LOAD&V="+this.BXIM.revision,method:"POST",dataType:"json",lsId:"IM_NOTIFY_LOAD",lsTimeout:5,timeout:30,data:{IM_NOTIFY_LOAD:"Y",IM_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()},onsuccess:BX.delegate(function(t){this.loadNotityBlock=false;this.unreadNotifyLoad=false;this.notifyLoad=true;var s={};if(typeof t.NOTIFY=="object"){for(var i in t.NOTIFY){t.NOTIFY[i].date=parseInt(t.NOTIFY[i].date)+parseInt(BX.message("USER_TZ_OFFSET"));s[i]=this.notify[i]=t.NOTIFY[i];this.BXIM.lastRecordId=parseInt(i)>this.BXIM.lastRecordId?parseInt(i):this.BXIM.lastRecordId;if(t.NOTIFY[i].type!="1")delete this.unreadNotify[i];else this.unreadNotify[i]=i}}if(e){this.openNotify(true);if(this.BXIM.settings.loadLastNotify)this.notifyHistory();BX.localStorage.set("nln",true,5)}this.updateNotifyCount()},this),onfailure:BX.delegate(function(){this.loadNotityBlock=false},this)})};BX.Notify.prototype.notifyHistory=function(e){e=e||window.event;if(this.notifyHistoryLoad)return false;if(this.messenger&&this.messenger.popupMessengerConnectionStatusState!="online")return false;if(BX("bx-notifier-content-link-history")){BX("bx-notifier-content-link-history").innerHTML='<span class="bx-notifier-item-button bx-notifier-item-button-white">'+BX.message("IM_NOTIFY_LOAD_NOTIFY")+"..."+"</span>"}this.notifyHistoryLoad=true;BX.ajax({url:this.BXIM.pathToAjax+"?NOTIFY_HISTORY_LOAD_MORE&V="+this.BXIM.revision,method:"POST",dataType:"json",timeout:30,data:{IM_NOTIFY_HISTORY_LOAD_MORE:"Y",PAGE:!this.BXIM.settings.loadLastNotify&&this.notifyHistoryPage==0?1:this.notifyHistoryPage,IM_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()},onsuccess:BX.delegate(function(e){if(e&&e.BITRIX_SESSID){BX.message({bitrix_sessid:e.BITRIX_SESSID})}if(e.ERROR==""){this.notifyLoad=true;BX.remove(BX("bx-notifier-content-load"));this.sendAjaxTry=0;var t={};var s=0;if(typeof e.NOTIFY=="object"){for(var i in e.NOTIFY){e.NOTIFY[i].date=parseInt(e.NOTIFY[i].date)+parseInt(BX.message("USER_TZ_OFFSET"));if(!this.notify[i])t[i]=e.NOTIFY[i];if(!this.notify[i]){this.notify[i]=BX.clone(e.NOTIFY[i])}s++}}if(this.popupNotifyItem){if(BX("bx-notifier-content-link-history"))BX.remove(BX("bx-notifier-content-link-history"));if(s>0){if(BX("bx-notifier-content-empty"))BX.remove(BX("bx-notifier-content-empty"));var t=this.drawNotify(t,true);for(var i=0;i<t.length;i++){this.popupNotifyItem.appendChild(t[i])}if(s<20&&this.notifyHistoryPage>0){BX.remove(BX("bx-notifier-content-link-history"))}else{this.popupNotifyItem.appendChild(BX.create("a",{attrs:{href:"#notifyHistory",id:"bx-notifier-content-link-history"},events:{click:BX.delegate(this.notifyHistory,this)},props:{className:"bx-notifier-content-link-history"},children:[BX.create("span",{props:{className:"bx-notifier-item-button bx-notifier-item-button-white"},html:BX.message("IM_NOTIFY_HISTORY_LATE")})]}));if(s>=20&&this.notifyHistoryPage==0)this.notifyHistoryPage=1}}else if(s<=0&&this.notifyHistoryPage==0){if(BX("bx-notifier-content-link-history"))BX.remove(BX("bx-notifier-content-link-history"));this.popupNotifyItem.innerHTML="";this.popupNotifyItem.appendChild(BX.create("div",{attrs:{style:"padding-top: 248px; margin-bottom: 31px;"},props:{className:"bx-messenger-box-empty bx-notifier-content-empty",id:"bx-notifier-content-empty"},html:BX.message("IM_NOTIFY_EMPTY_3")}));this.popupNotifyItem.appendChild(BX.create("a",{attrs:{href:"#notifyHistory",id:"bx-notifier-content-link-history"},events:{click:BX.delegate(this.notifyHistory,this)},props:{className:"bx-notifier-content-link-history bx-notifier-content-link-history-empty"},children:[BX.create("span",{props:{className:"bx-notifier-item-button bx-notifier-item-button-white"},html:BX.message("IM_NOTIFY_HISTORY_LATE")})]}))}else{if(this.popupNotifyItem.innerHTML==""){this.popupNotifyItem.appendChild(BX.create("div",{attrs:{style:"padding-top: 248px; margin-bottom: 31px;"},props:{className:"bx-messenger-box-empty bx-notifier-content-empty",id:"bx-notifier-content-empty"},html:BX.message("IM_NOTIFY_EMPTY_3")}))}}}this.notifyHistoryLoad=false;this.notifyHistoryPage++}else{if(e.ERROR=="SESSION_ERROR"&&this.sendAjaxTry<2){this.sendAjaxTry++;BX.message({bitrix_sessid:e.BITRIX_SESSID});setTimeout(BX.delegate(function(){this.notifyHistoryLoad=false;this.notifyHistory()},this),2e3);BX.onCustomEvent(window,"onImError",[e.ERROR,e.BITRIX_SESSID])}else if(e.ERROR=="AUTHORIZE_ERROR"){this.sendAjaxTry++;if(this.desktop.ready()){setTimeout(BX.delegate(function(){this.notifyHistoryLoad=false;this.notifyHistory()},this),1e4)}BX.onCustomEvent(window,"onImError",[e.ERROR])}}},this),onfailure:BX.delegate(function(){this.notifyHistoryLoad=false;this.sendAjaxTry=0},this)});if(e)return BX.PreventDefault(e);else return true};BX.Notify.prototype.adjustPosition=function(e){if(this.desktop.run())return false;e=e||{};e.timeout=typeof e.timeout=="number"?parseInt(e.timeout):0;clearTimeout(this.adjustPositionTimeout);this.adjustPositionTimeout=setTimeout(BX.delegate(function(){e.scroll=e.scroll||!BX.browser.IsDoctype();e.resize=e.resize||false;if(!this.windowScrollPos.scrollLeft)this.windowScrollPos={scrollLeft:0,scrollTop:0};if(e.scroll)this.windowScrollPos=BX.GetWindowScrollPos();if(e.resize||!this.windowInnerSize.innerWidth){this.windowInnerSize=BX.GetWindowInnerSize();if(this.BXIM.settings.panelPositionVertical=="bottom"&&typeof window.scroll=="function"&&!(BX.browser.IsAndroid()||BX.browser.IsIOS())){if(typeof window.scrollX!="undefined"&&typeof window.scrollY!="undefined"){var t=window.scrollX;window.scroll(1,window.scrollY);this.windowInnerSize.innerHeight+=window.scrollX==1?-16:0;window.scroll(t,window.scrollY)}else{var s=document.documentElement.scrollLeft?document.documentElement.scrollLeft:document.body.scrollLeft;var i=document.documentElement.scrollTop?document.documentElement.scrollTop:document.body.scrollTop;var t=s;window.scroll(1,i);s=document.documentElement.scrollLeft?document.documentElement.scrollLeft:document.body.scrollLeft;this.windowInnerSize.innerHeight+=s==1?-16:0;window.scroll(t,i)}}}if(e.scroll||e.resize){if(this.BXIM.settings.panelPositionHorizontal=="left")this.panel.style.left=this.windowScrollPos.scrollLeft+25+"px";else if(this.BXIM.settings.panelPositionHorizontal=="center")this.panel.style.left=(this.windowScrollPos.scrollLeft+this.windowInnerSize.innerWidth-this.panel.offsetWidth)/2+"px";else if(this.BXIM.settings.panelPositionHorizontal=="right")this.panel.style.left=this.windowScrollPos.scrollLeft+this.windowInnerSize.innerWidth-this.panel.offsetWidth-35+"px";if(this.BXIM.settings.panelPositionVertical=="top"){this.panel.style.top=this.windowScrollPos.scrollTop+"px";if(BX.hasClass(this.panel,"bx-notifier-panel-doc"))this.panel.className="bx-notifier-panel bx-notifier-panel-top bx-notifier-panel-doc";else this.panel.className="bx-notifier-panel bx-notifier-panel-top"}else if(this.BXIM.settings.panelPositionVertical=="bottom"){if(BX.hasClass(this.panel,"bx-notifier-panel-doc"))this.panel.className="bx-notifier-panel bx-notifier-panel-bottom bx-notifier-panel-doc";else this.panel.className="bx-notifier-panel bx-notifier-panel-bottom";this.panel.style.top=this.windowScrollPos.scrollTop+this.windowInnerSize.innerHeight-this.panel.offsetHeight+"px"}}},this),e.timeout)};BX.Notify.prototype.move=function(e,t){var s=parseInt(this.panel.style.left)+e;var i=parseInt(this.panel.style.top)+t;if(s<0)s=0;var a=BX.GetWindowScrollSize();var n=this.panel.offsetWidth;var o=this.panel.offsetHeight;if(s>a.scrollWidth-n)s=a.scrollWidth-n;if(i>a.scrollHeight-o)i=a.scrollHeight-o;if(i<0)i=0;this.panel.style.left=s+"px";this.panel.style.top=i+"px"};BX.Notify.prototype._startDrag=function(e){e=e||window.event;BX.fixEventPageXY(e);this.dragPageX=e.pageX;this.dragPageY=e.pageY;this.dragged=false;this.closePopup();BX.bind(document,"mousemove",BX.proxy(this._moveDrag,this));BX.bind(document,"mouseup",BX.proxy(this._stopDrag,this));if(document.body.setCapture)document.body.setCapture();document.body.ondrag=BX.False;document.body.onselectstart=BX.False;document.body.style.cursor="move";document.body.style.MozUserSelect="none";this.panel.style.MozUserSelect="none";BX.addClass(this.panel,"bx-notifier-panel-drag-"+(this.BXIM.settings.panelPositionVertical=="top"?"top":"bottom"));return BX.PreventDefault(e)};BX.Notify.prototype._moveDrag=function(e){e=e||window.event;BX.fixEventPageXY(e);if(this.dragPageX==e.pageX&&this.dragPageY==e.pageY)return;this.move(e.pageX-this.dragPageX,e.pageY-this.dragPageY);this.dragPageX=e.pageX;this.dragPageY=e.pageY;if(!this.dragged){BX.onCustomEvent(this,"onPopupDragStart");this.dragged=true}BX.onCustomEvent(this,"onPopupDrag")};BX.Notify.prototype._stopDrag=function(e){if(document.body.releaseCapture)document.body.releaseCapture();BX.unbind(document,"mousemove",BX.proxy(this._moveDrag,this));BX.unbind(document,"mouseup",BX.proxy(this._stopDrag,this));document.body.ondrag=null;document.body.onselectstart=null;document.body.style.cursor="";document.body.style.MozUserSelect="";this.panel.style.MozUserSelect="";BX.removeClass(this.panel,"bx-notifier-panel-drag-"+(this.BXIM.settings.panelPositionVertical=="top"?"top":"bottom"));BX.onCustomEvent(this,"onPopupDragEnd");var t=BX.GetWindowScrollPos();this.BXIM.settings.panelPositionVertical=this.windowInnerSize.innerHeight/2>(e.pageY-t.scrollTop||e.y)?"top":"bottom";if(this.windowInnerSize.innerWidth/3>(e.pageX-t.scrollLeft||e.x))this.BXIM.settings.panelPositionHorizontal="left";else if(this.windowInnerSize.innerWidth/3*2<(e.pageX-t.scrollLeft||e.x))this.BXIM.settings.panelPositionHorizontal="right";else this.BXIM.settings.panelPositionHorizontal="center";this.BXIM.saveSettings({panelPositionVertical:this.BXIM.settings.panelPositionVertical,panelPositionHorizontal:this.BXIM.settings.panelPositionHorizontal});BX.localStorage.set("npp",{v:this.BXIM.settings.panelPositionVertical,h:this.BXIM.settings.panelPositionHorizontal});this.adjustPosition({resize:true});this.dragged=false;return BX.PreventDefault(e)};BX.Notify.prototype.closePopup=function(){if(this.popupNotifyMore!=null)this.popupNotifyMore.destroy();if(this.messenger!=null&&this.messenger.popupPopupMenu!=null)this.messenger.popupPopupMenu.destroy()};BX.Notify.prototype.createNotify=function(e,t){var s=false;if(!e)return false;t=t==true;if(this.desktop.run()){e.text=e.text.replace(/<a(.*?)>(.*?)<\/a>/gi,function(e,t,s){return"<a"+t.replace('target="_self"','target="_blank"')+">"+s+"</a>"})}var i=this.unreadNotify[e.id]&&!t?" bx-notifier-item-new":"";e.userAvatar=e.userAvatar?e.userAvatar:this.BXIM.pathToBlankImage;if(e.type==1&&typeof e.buttons!="undefined"&&e.buttons.length>0){var a=[];for(var n=0;n<e.buttons.length;n++){var o=e.buttons[n].TYPE=="accept"?"accept":e.buttons[n].TYPE=="cancel"?"cancel":"default";var r={"data-id":e.id,"data-value":e.buttons[n].VALUE};if(e.grouped)r["data-group"]="Y";if(e.buttons[n].URL)r["data-url"]=e.buttons[n].URL;a.push(BX.create("span",{props:{className:"bx-notifier-item-button bx-notifier-item-button-"+o},attrs:r,html:e.buttons[n].TITLE}))}s=BX.create("div",{attrs:{"data-notifyId":e.id,"data-notifyType":e.type},props:{className:"bx-notifier-item"+i},children:[BX.create("span",{props:{className:"bx-notifier-item-content"},children:[BX.create("span",{props:{className:"bx-notifier-item-avatar"},children:[BX.create("img",{props:{className:"bx-notifier-item-avatar-img"+(BX.MessengerCommon.isBlankAvatar(e.userAvatar)?" bx-notifier-item-avatar-img-default":"")},attrs:{src:e.userAvatar}})]}),BX.create("span",{props:{className:"bx-notifier-item-delete bx-notifier-item-delete-fake"}}),BX.create("span",{props:{className:"bx-notifier-item-date"},html:BX.MessengerCommon.formatDate(e.date)}),e.userName?BX.create("span",{props:{className:"bx-notifier-item-name"},html:'<a href="'+e.userLink+'" onclick="if (BXIM.init) { BXIM.openMessenger('+e.userId+'); return false; } ">'+BX.MessengerCommon.prepareText(e.userName)+"</a>"}):null,BX.create("span",{props:{className:"bx-notifier-item-text"},html:e.text}),BX.create("span",{props:{className:"bx-notifier-item-button-wrap"},children:a})]})]})}else if(e.type==2||e.type==1&&typeof e.buttons!="undefined"&&e.buttons.length<=0){s=BX.create("div",{attrs:{"data-notifyId":e.id,"data-notifyType":e.type},props:{className:"bx-notifier-item"+i},children:[BX.create("span",{props:{className:"bx-notifier-item-content"},children:[BX.create("span",{props:{className:"bx-notifier-item-avatar"},children:[BX.create("img",{props:{className:"bx-notifier-item-avatar-img"+(BX.MessengerCommon.isBlankAvatar(e.userAvatar)?" bx-notifier-item-avatar-img-default":"")},attrs:{src:e.userAvatar}})]}),BX.create("a",{attrs:{href:"#","data-notifyId":e.id,"data-notifyType":e.type},props:{className:"bx-notifier-item-delete"}}),BX.create("span",{props:{className:"bx-notifier-item-date"},html:BX.MessengerCommon.formatDate(e.date)}),BX.create("span",{props:{className:"bx-notifier-item-name"},html:'<a href="'+e.userLink+'" onclick="if (BXIM.init) { BXIM.openMessenger('+e.userId+'); return false; } ">'+BX.MessengerCommon.prepareText(e.userName)+"</a>"}),BX.create("span",{props:{className:"bx-notifier-item-text"},html:e.text})]})]})}else if(e.type==3){s=BX.create("div",{attrs:{"data-notifyId":e.id,"data-notifyType":e.type},props:{className:"bx-notifier-item"+i},children:[BX.create("span",{props:{className:"bx-notifier-item-content"},children:[BX.create("span",{props:{className:"bx-notifier-item-avatar-group"},children:[BX.create("span",{props:{className:"bx-notifier-item-avatar"},children:[BX.create("img",{props:{className:"bx-notifier-item-avatar-img"+(BX.MessengerCommon.isBlankAvatar(e.userAvatar)?" bx-notifier-item-avatar-img-default":"")},attrs:{src:e.userAvatar}})]})]}),BX.create("a",{attrs:{href:"#","data-notifyId":e.id,"data-group":"Y","data-notifyType":e.type},props:{className:"bx-notifier-item-delete"}}),BX.create("span",{props:{className:"bx-notifier-item-date"},html:BX.MessengerCommon.formatDate(e.date)}),BX.create("span",{props:{className:"bx-notifier-item-name"},html:BX.message("IM_NOTIFY_GROUP_NOTIFY").replace("#USER_NAME#",'<a href="'+e.userLink+'" onclick="if (BXIM.init) { BXIM.openMessenger('+e.userId+'); return false;} ">'+BX.MessengerCommon.prepareText(e.userName)+"</a>").replace("#U_START#",'<span class="bx-notifier-item-help" data-help="'+e.id+'">').replace("#U_END#","</span>").replace("#COUNT#",e.otherCount)}),BX.create("span",{props:{className:"bx-notifier-item-text"},html:e.text})]})]})}else{s=BX.create("div",{attrs:{"data-notifyId":e.id,"data-notifyType":e.type},props:{className:"bx-notifier-item"+i},children:[BX.create("span",{props:{className:"bx-notifier-item-content"},children:[BX.create("span",{props:{className:"bx-notifier-item-avatar"},children:[BX.create("img",{props:{className:"bx-notifier-item-avatar-img bx-notifier-item-avatar-img-default-2"},attrs:{src:e.userAvatar}})]}),BX.create("a",{attrs:{href:"#","data-notifyId":e.id,"data-notifyType":e.type},props:{className:"bx-notifier-item-delete"}}),BX.create("span",{props:{className:"bx-notifier-item-date"},html:BX.MessengerCommon.formatDate(e.date)}),e.title&&e.title.length>0?BX.create("span",{props:{className:"bx-notifier-item-name"},html:BX.MessengerCommon.prepareText(e.title)}):null,BX.create("span",{props:{className:"bx-notifier-item-text"},html:e.text})]})]})}return s};BX.Notify.prototype.storageSet=function(e){if(e.key=="npp"){var t=BX.localStorage.get(e.key);this.BXIM.settings.panelPositionHorizontal=!!t?t.h:this.BXIM.settings.panelPositionHorizontal;this.BXIM.settings.panelPositionVertical=!!t?t.v:this.BXIM.settings.panelPositionVertical;this.adjustPosition({resize:true})}else if(e.key=="nun"){this.notify=e.value}else if(e.key=="nrn"){delete this.notify[e.value];this.updateNotifyCount(false)}else if(e.key=="nrgn"){for(var s in this.notify){if(this.notify[s].tag==e.value)delete this.notify[s]}this.updateNotifyCount()}else if(e.key=="numc"){this.updateNotifyMailCount(e.value,false)}else if(e.key=="nuc"){this.updateNotifyCounters(e.value,false)}else if(e.key=="nunc"){setTimeout(BX.delegate(function(){this.unreadNotify=e.value.unread;this.flashNotify=e.value.flash;this.updateNotifyCount(false)},this),500)}else if(e.key=="nln"){this.loadNotify(false)}}})();(function(){if(BX.Messenger)return;BX.Messenger=function(e,t){this.BXIM=e;this.BXIM.messenger=this;this.settings={};this.params=t||{};this.realSearch=!this.BXIM.bitrixIntranet&&!this.BXIM.bitrix24net;this.updateStateCount=1;this.sendAjaxTry=0;this.updateStateVeryFastCount=0;this.updateStateFastCount=0;this.updateStateStepDefault=this.BXIM.ppStatus?parseInt(t.updateStateInterval):60;this.updateStateStep=this.updateStateStepDefault;this.updateStateTimeout=null;this.redrawContactListTimeout={};this.redrawRecentListTimeout=null;this.floatDateTimeout=null;this.readMessageTimeout={};this.readMessageTimeoutSend=null;this.webrtc=t.webrtcClass;this.notify=t.notifyClass;this.desktop=t.desktopClass;this.smile=t.smile;this.smileSet=t.smileSet;this.recentListIndex=[];if(t.recent){this.recent=t.recent;this.recentListLoad=true}else{this.recent=[];this.recentListLoad=false}this.popupTooltip=null;this.users=t.users;this.groups=t.groups;this.userInGroup=t.userInGroup;this.woGroups=t.woGroups;this.woUserInGroup=t.woUserInGroup;this.currentTab=t.currentTab;this.redrawTab={};this.loadLastMessageTimeout={};this.showMessage=t.showMessage;this.unreadMessage=t.unreadMessage;this.flashMessage=t.flashMessage;this.disk=t.diskClass;this.disk.messenger=this;this.popupMessengerFileForm=null;this.popupMessengerFileDropZone=null;this.popupMessengerFileButton=null;this.popupMessengerFileFormChatId=null;this.popupMessengerFileFormInput=null;this.chat=t.chat;this.userChat=t.userChat;this.userInChat=t.userInChat;this.userChatBlockStatus=t.userChatBlockStatus;this.hrphoto=t.hrphoto;this.phones={};this.errorMessage={};this.message=t.message;this.messageTmpIndex=0;this.history=t.history;this.textareaHistory={};this.textareaHistoryTimeout=null;this.messageCount=t.countMessage;this.sendMessageFlag=0;this.sendMessageTmp={};this.sendMessageTmpTimeout={};this.popupSettings=null;this.popupSettingsBody=null;this.popupChatDialog=null;this.popupChatDialogContactListElements=null;this.popupChatDialogContactListSearch=null;this.popupChatDialogDestElements=null;this.popupChatDialogUsers={};this.popupChatDialogSendBlock=false;this.renameChatDialogFlag=false;this.renameChatDialogInput=null;this.popupKeyPad=null;this.popupHistory=null;this.popupHistoryElements=null;this.popupHistoryItems=null;this.popupHistoryItemsSize=475;this.popupHistorySearchDateWrap=null;this.popupHistorySearchWrap=null;this.popupHistoryFilesSearchWrap=null;this.popupHistoryButtonDeleteAll=null;this.popupHistoryButtonFilter=null;this.popupHistoryButtonFilterBox=null;this.popupHistoryFilterVisible=true;this.popupHistoryBodyWrap=null;this.popupHistoryFilesItems=null;this.popupHistoryFilesBodyWrap=null;this.popupHistorySearchInput=null;this.historyUserId=0;this.historyChatId=0;this.historyDateSearch="";this.historySearch="";this.historyLastSearch={};this.historySearchBegin=false;this.historySearchTimeout=null;this.historyFilesSearch="";this.historyFilesLastSearch={};this.historyFilesSearchBegin=false;this.historyFilesSearchTimeout=null;this.historyWindowBlock=false;this.historyMessageSplit="------------------------------------------------------";this.historyOpenPage={};this.historyLoadFlag={};this.historyEndOfList={};this.historyFilesOpenPage={};this.historyFilesLoadFlag={};this.historyFilesEndOfList={};this.popupMessenger=null;this.popupMessengerWindow={};this.popupMessengerExtra=null;this.popupMessengerTopLine=null;this.popupMessengerDesktopTimeout=null;this.popupMessengerFullWidth=864;this.popupMessengerMinWidth=864;this.popupMessengerFullHeight=454;this.popupMessengerMinHeight=454;this.popupMessengerDialog=null;this.popupMessengerBody=null;this.popupMessengerBodyDialog=null;this.popupMessengerBodyAnimation=null;this.popupMessengerBodySize=295;this.popupMessengerBodyWrap=null;this.popupMessengerLikeBlock={};this.popupMessengerLikeBlockTimeout={};this.popupMessengerConnectionStatusState="online";this.popupMessengerConnectionStatusStateText="online";this.popupMessengerConnectionStatus=null;this.popupMessengerConnectionStatusText=null;this.popupMessengerConnectionStatusTimeout=null;this.popupMessengerEditForm=null;this.popupMessengerEditFormTimeout=null;this.popupMessengerEditTextarea=null;this.popupMessengerEditMessageId=0;this.popupMessengerPanel=null;this.popupMessengerPanelAvatar=null;this.popupMessengerPanelCall1=null;this.popupMessengerPanelCall2=null;this.popupMessengerPanelCall3=null;this.popupMessengerPanelTitle=null;this.popupMessengerPanelStatus=null;this.popupMessengerPanel2=null;this.popupMessengerPanel3=null;this.popupMessengerPanelChatTitle=null;this.popupMessengerPanelUsers=null;this.popupMessengerTextareaPlace=null;this.popupMessengerTextarea=null;this.popupMessengerTextareaSendType=null;this.popupMessengerTextareaResize={};this.popupMessengerTextareaSize=49;this.popupMessengerLastMessage=0;this.readedList={};this.writingList={};this.writingListTimeout={};this.writingSendList={};this.writingSendListTimeout={};this.contactListPanelStatus=null;this.contactListSearchText="";this.popupPopupMenu=null;this.popupPopupMenuDateCreate=0;this.popupSmileMenu=null;this.popupSmileMenuGallery=null;this.popupSmileMenuSet=null;this.recentList=true;this.recentListReturn=false;this.recentListTab=null;this.recentListTabCounter=null;this.contactList=false;this.contactListTab=null;this.openMessengerFlag=false;this.openChatFlag=false;this.openCallFlag=false;this.contactListLoad=false;this.popupContactListSize=254;this.popupContactListSearchInput=null;this.popupContactListSearchClose=null;this.popupContactListWrap=null;this.popupContactListElements=null;this.popupContactListElementsSize=this.desktop.run()?332:295;this.popupContactListElementsSizeDefault=this.desktop.run()?332:295;this.popupContactListElementsWrap=null;this.contactListPanelSettings=null;this.enableGroupChat=this.BXIM.ppStatus?true:false;if(this.BXIM.init){if(this.desktop.run()){BX.desktop.setUserInfo(BX.MessengerCommon.getUserParam());BX.desktop.addTab({id:"im",title:BX.message("IM_DESKTOP_OPEN_MESSENGER").replace("#COUNTER#",""),order:100,events:{open:BX.delegate(function(){if(!this.BXIM.dialogOpen)this.openMessenger(this.currentTab)},this)}});if(this.webrtc.phoneSupport()){BX.desktop.addTab({id:"im-phone",title:BX.message("IM_PHONE_DESC"),order:120,target:"im",events:{open:BX.delegate(this.webrtc.openKeyPad,this.webrtc),close:BX.delegate(function(){if(this.webrtc.popupKeyPad)this.webrtc.popupKeyPad.close()},this)}})}}BX.addCustomEvent("onPullError",BX.delegate(function(e,t){if(e=="AUTHORIZE_ERROR"){if(this.desktop.ready()){this.connectionStatus("connecting")}else{this.connectionStatus("offline")}}else if(e=="RECONNECT"&&(t==1008||t==1006)){this.connectionStatus("connecting")}},this));BX.addCustomEvent("OnDesktopTabChange",BX.delegate(function(){this.closeMenuPopup()},this));BX.addCustomEvent("onImError",BX.delegate(function(e,t){if(e=="AUTHORIZE_ERROR"||e=="SEND_ERROR"&&t=="AUTHORIZE_ERROR"){if(this.desktop.ready()){this.connectionStatus("connecting")}else{this.connectionStatus("offline")}}},this));BX.addCustomEvent("onPullStatus",BX.delegate(function(e){this.connectionStatus(e=="offline"?"offline":"online")},this));BX.bind(window,"online",BX.delegate(function(){this.connectionStatus("online")},this));BX.bind(window,"offline",BX.delegate(function(){this.connectionStatus("offline")},this));this.notify.panel.appendChild(this.BXIM.audio.newMessage1=BX.create("audio",{props:{className:"bx-messenger-audio"},children:[BX.create("source",{attrs:{src:"/bitrix/js/im/audio/new-message-1.ogg",type:"audio/ogg; codecs=vorbis"}}),BX.create("source",{attrs:{src:"/bitrix/js/im/audio/new-message-1.mp3",type:"audio/mpeg"}})]}));this.notify.panel.appendChild(this.BXIM.audio.newMessage2=BX.create("audio",{props:{className:"bx-messenger-audio"},children:[BX.create("source",{attrs:{src:"/bitrix/js/im/audio/new-message-2.ogg",type:"audio/ogg; codecs=vorbis"}}),BX.create("source",{attrs:{src:"/bitrix/js/im/audio/new-message-2.mp3",type:"audio/mpeg"}})]}));this.notify.panel.appendChild(this.BXIM.audio.send=BX.create("audio",{props:{className:"bx-messenger-audio"},children:[BX.create("source",{attrs:{src:"/bitrix/js/im/audio/send.ogg",type:"audio/ogg; codecs=vorbis"}}),BX.create("source",{attrs:{src:"/bitrix/js/im/audio/send.mp3",type:"audio/mpeg"}})]}));if(typeof this.BXIM.audio.send.play=="undefined"){this.BXIM.settings.enableSound=false}for(var s in this.unreadMessage){if(typeof this.flashMessage[s]=="undefined")this.flashMessage[s]={};for(var i=this.unreadMessage[s].length-1;i>=0;i--){BX.localStorage.set("mum",{userId:s,message:this.message[this.unreadMessage[s][i]]},5)}}BX.localStorage.set("muum",this.unreadMessage,5);BX.bind(this.notify.panelButtonMessage,"click",BX.delegate(function(){if(this.BXIM.messageCount<=0)this.BXIM.toggleMessenger();else this.BXIM.openMessenger()},this));var a=this.BXIM.getLocalConfig("global_msz",false);if(a){this.popupMessengerFullWidth=parseInt(a.wz);this.popupMessengerTextareaSize=parseInt(a.ta2);this.popupMessengerBodySize=parseInt(a.b)>0?parseInt(a.b):this.popupMessengerBodySize;this.popupHistoryItemsSize=parseInt(a.hi);this.popupMessengerFullHeight=parseInt(a.fz);this.popupContactListElementsSize=parseInt(a.ez);this.notify.popupNotifySize=parseInt(a.nz);this.popupHistoryFilterVisible=a.hf;if(this.desktop.ready()){BX.desktop.setWindowSize({Width:parseInt(a.dw),Height:parseInt(a.dh)});this.desktop.initHeight=parseInt(a.dh)}}else{if(this.desktop.ready()){BX.desktop.setWindowSize({Width:BX.desktop.minWidth,Height:BX.desktop.minHeight});this.desktop.initHeight=BX.desktop.minHeight}}if(this.desktop.ready()){BX.bind(window,"resize",BX.delegate(function(){this.adjustSize()},this.desktop))}if(BX.browser.SupportLocalStorage()){var n=BX.localStorage.get("mcr2");if(n){for(var s in n.users)this.users[s]=n.users[s];for(var s in n.hrphoto)this.hrphoto[s]=n.hrphoto[s];for(var s in n.chat)this.chat[s]=n.chat[s];for(var s in n.userInChat)this.userInChat[s]=n.userInChat[s];this.callInit=true;setTimeout(BX.delegate(function(){this.webrtc.callNotifyWait(n.callChatId,n.callUserId,n.callVideo,n.callToGroup)},this),500)}BX.addCustomEvent(window,"onLocalStorageSet",BX.delegate(this.storageSet,this));this.textareaHistory=BX.localStorage.get("mtah")||{};this.currentTab=BX.localStorage.get("mct")||this.currentTab;this.contactListSearchText=BX.localStorage.get("mcls")!=null?BX.localStorage.get("mcls")+"":"";this.messageTmpIndex=BX.localStorage.get("mti")||0;var o=BX.localStorage.get("mfm");if(o){for(var s in this.flashMessage)for(var r in this.flashMessage[s])if(o[s]&&this.flashMessage[s][r]!=o[s][r]&&o[s][r]==false)this.flashMessage[s][r]=false}BX.garbage(function(){BX.localStorage.set("mti",this.messageTmpIndex,15);BX.localStorage.set("mtah",this.textareaHistory,15);BX.localStorage.set("mct",this.currentTab,15);BX.localStorage.set("mfm",this.flashMessage,15);BX.localStorage.set("mcls",this.contactListSearchText+"",15);this.BXIM.setLocalConfig("mtah2",this.textareaHistory);if(this.desktop.ready()&&(window.innerWidth<BX.desktop.minWidth||window.innerHeight<BX.desktop.minHeight))return false;this.BXIM.setLocalConfig("global_msz",{wz:this.popupMessengerFullWidth,ta2:this.popupMessengerTextareaSize,b:this.popupMessengerBodySize,cl:this.popupContactListSize,hi:this.popupHistoryItemsSize,fz:this.popupMessengerFullHeight,ez:this.popupContactListElementsSize,nz:this.notify.popupNotifySize,hf:this.popupHistoryFilterVisible,dw:window.innerWidth,dh:window.innerHeight,place:"garbage"})},this)}else{var l=this.BXIM.getLocalConfig("mtah",false);if(l){this.textareaHistory=l;this.BXIM.removeLocalConfig("mtah")}var p=this.BXIM.getLocalConfig("mct",false);if(p){this.currentTab=p;this.BXIM.removeLocalConfig("mct")}BX.garbage(function(){this.BXIM.setLocalConfig("mct",this.currentTab);this.BXIM.setLocalConfig("mtah",this.textareaHistory);if(this.desktop.ready()&&(window.innerWidth<BX.desktop.minWidth||window.innerHeight<BX.desktop.minHeight))return false;this.BXIM.setLocalConfig("global_msz",{wz:this.popupMessengerFullWidth,ta2:this.popupMessengerTextareaSize,b:this.popupMessengerBodySize,cl:this.popupContactListSize,hi:this.popupHistoryItemsSize,fz:this.popupMessengerFullHeight,ez:this.popupContactListElementsSize,nz:this.notify.popupNotifySize,hf:this.popupHistoryFilterVisible,dw:window.innerWidth,dh:window.innerHeight,place:"garbage"})},this)}BX.MessengerCommon.pullEvent();BX.addCustomEvent("onPullError",BX.delegate(function(e){if(e=="AUTHORIZE_ERROR")this.sendAjaxTry++},this));for(var h in this.users){if(this.users[h].birthday&&h!=this.BXIM.userId){
this.message[h+"birthday"]={id:h+"birthday",senderId:0,recipientId:h,date:BX.MessengerCommon.getNowDate(true),text:BX.message("IM_M_BIRTHDAY_MESSAGE").replace("#USER_NAME#",'<img src="/bitrix/js/im/images/blank.gif" class="bx-messenger-birthday-icon"><strong>'+this.users[h].name+"</strong>")};if(!this.showMessage[h])this.showMessage[h]=[];this.showMessage[h].push(h+"birthday");this.showMessage[h].sort(BX.delegate(function(e,t){if(!this.message[e]||!this.message[t]){return 0}var s=parseInt(this.message[e].date);var i=parseInt(this.message[t].date);if(s<i){return-1}else if(s>i){return 1}else{if(e<t){return-1}else if(e>t){return 1}else{return 0}}},this));var c=this.showMessage[h][this.showMessage[h].length-1];BX.MessengerCommon.recentListAdd({userId:h,id:this.message[c].id,date:parseInt(this.message[c].date)-parseInt(BX.message("USER_TZ_OFFSET")),recipientId:this.message[c].recipientId,senderId:this.message[c].senderId,text:c==h+"birthday"?BX.message("IM_M_BIRTHDAY_MESSAGE_SHORT").replace("#USER_NAME#",this.users[h].name):this.message[c].text,params:{}},true);this.recent.sort(BX.delegate(function(e,t){if(!this.message[e.id]||!this.message[t.id]){return 0}var s=parseInt(this.message[e.id].date);var i=parseInt(this.message[t.id].date);if(s>i){return-1}else if(s<i){return 1}else{if(e>t){return-1}else if(e<t){return 1}else{return 0}}},this));var u=this.BXIM.getLocalConfig("birthdayPopup"+(new Date).getFullYear(),{});if(this.desktop.birthdayStatus()&&!u[h]){this.message[h+"birthdayPopup"]={id:h+"birthdayPopup",senderId:0,recipientId:h,date:BX.MessengerCommon.getNowDate(true),text:BX.message("IM_M_BIRTHDAY_MESSAGE_SHORT").replace("#USER_NAME#",this.users[h].name)};if(this.desktop.ready()){if(!this.unreadMessage[h])this.unreadMessage[h]=[];this.unreadMessage[h].push(h+"birthdayPopup");if(!this.flashMessage[h])this.flashMessage[h]={};this.flashMessage[h][h+"birthdayPopup"]=true}u[h]=true;this.BXIM.removeLocalConfig("birthdayPopup"+((new Date).getFullYear()-1));this.BXIM.setLocalConfig("birthdayPopup"+(new Date).getFullYear(),u)}}}this.updateState();if(t.openMessenger!==false)this.openMessenger(t.openMessenger);else if(this.openMessengerFlag)this.openMessenger(this.currentTab);if(t.openHistory!==false)this.openHistory(t.openHistory);if(t.openNotify!==false)this.BXIM.openNotify();if(this.BXIM.settings.status!="dnd")this.newMessage();this.updateMessageCount()}else{if(t.openMessenger!==false)this.BXIM.openMessenger(t.openMessenger);if(t.openHistory!==false)this.BXIM.openHistory(t.openHistory)}};BX.Messenger.prototype.openMessenger=function(e){if(this.BXIM.errorMessage!=""){this.BXIM.openConfirm(this.BXIM.errorMessage);return false}if(this.BXIM.popupSettings!=null&&!this.desktop.run())this.BXIM.popupSettings.close();if(this.popupMessenger!=null&&this.dialogOpen&&this.currentTab==e&&e!=0)return false;if(this.popupMessengerEditForm)this.editMessageCancel();if(e==this.BXIM.userId){this.currentTab=0;e=0}BX.localStorage.set("mcam",true,5);if(typeof e=="undefined"||e==null)e=0;if(this.currentTab==null)this.currentTab=0;this.openChatFlag=false;this.openCallFlag=false;var t=false;if(typeof e=="boolean"){e=0}else if(e==0){t=true;for(var s in this.unreadMessage){e=s;t=false;break}if(e==0&&this.currentTab!=null){if(this.users[this.currentTab]&&this.users[this.currentTab].id)e=this.currentTab;else if(this.chat[this.currentTab.toString().substr(4)]&&this.chat[this.currentTab.toString().substr(4)].id)e=this.currentTab}if(e.toString().substr(0,4)=="chat"){BX.MessengerCommon.getUserParam(e);this.openChatFlag=true;if(this.chat[e.toString().substr(4)].style=="call")this.openCallFlag=true}else{e=parseInt(e)}}else if(e.toString().substr(0,4)=="chat"){BX.MessengerCommon.getUserParam(e);this.openChatFlag=true;if(this.chat[e.toString().substr(4)].style=="call")this.openCallFlag=true}else if(this.users[e]&&this.users[e].id){e=parseInt(e)}else{e=parseInt(e);if(isNaN(e)){e=0}else{BX.MessengerCommon.getUserParam(e)}}if(!this.openChatFlag&&typeof e!="number")e=0;if(this.openChatFlag||e>0){this.currentTab=e;this.BXIM.notifyManager.closeByTag("im-message-"+e);BX.localStorage.set("mct",this.currentTab,15)}if(this.desktop.run()&&BX.desktop.currentTab!="im"){BX.desktop.changeTab("im")}if(this.popupMessenger!=null){BX.MessengerCommon.openDialog(e,this.BXIM.dialogOpen?false:true);if(!(BX.browser.IsAndroid()||BX.browser.IsIOS())){if(t&&this.popupContactListSearchInput!=null)this.popupContactListSearchInput.focus();else this.popupMessengerTextarea.focus()}return false}var i={width:this.popupMessengerFullWidth+"px"};if(this.desktop.run()){i={};if(!BX.desktop.contentFullWindow){var a=BX.desktop.content.offsetHeight-this.popupMessengerFullHeight;this.popupContactListElementsSize=this.popupContactListElementsSize+a;this.popupMessengerBodySize=this.popupMessengerBodySize+a;this.popupMessengerFullHeight=this.popupMessengerFullHeight+a;this.notify.popupNotifySize=this.notify.popupNotifySize+a}}this.popupMessengerContent=BX.create("div",{props:{className:"bx-messenger-box bx-messenger-mark "+(this.webrtc.callInit?" bx-messenger-call"+(this.callOverlayMinimize?"":" bx-messenger-call-maxi"):"")},style:i,children:[this.popupContactListWrap=BX.create("div",{props:{className:"bx-messenger-box-contact"},style:{width:this.popupContactListSize+"px"},children:[BX.create("div",{props:{className:"bx-messenger-cl-switcher"},children:[BX.create("div",{props:{className:"bx-messenger-cl-switcher-wrap"},children:[this.contactListTab=BX.create("span",{props:{className:"bx-messenger-cl-switcher-tab bx-messenger-cl-switcher-tab-cl"},children:[BX.create("div",{props:{className:"bx-messenger-cl-switcher-tab-wrap"},html:BX.message("IM_CL_TAB_LIST")})]}),this.recentListTab=BX.create("span",{props:{className:"bx-messenger-cl-switcher-tab bx-messenger-cl-switcher-tab-recent"},children:[BX.create("div",{props:{className:"bx-messenger-cl-switcher-tab-wrap"},children:[this.recentListTabCounter=BX.create("span",{props:{className:"bx-messenger-cl-count bx-messenger-cl-switcher-tab-count"},html:this.messageCount>0?'<span class="bx-messenger-cl-count-digit">'+(this.messageCount<100?this.messageCount:"99+")+"</span>":""}),BX.create("div",{props:{className:"bx-messenger-cl-switcher-tab-text"},html:BX.message("IM_CL_TAB_RECENT")})]})]})]})]}),BX.create("div",{props:{className:"bx-messenger-input-search"+(this.webrtc.phoneEnabled&&!this.desktop.run()?" bx-messenger-input-search-phone":"")},children:[this.popupContactListSearchCall=BX.create("span",{props:{className:"bx-messenger-cl-switcher-tab-wrap bx-messenger-input-search-call"},html:'<span class="bx-messenger-input-search-call-icon"></span>'}),BX.create("div",{props:{className:"bx-messenger-input-wrap bx-messenger-cl-search-wrap"},children:[this.popupContactListSearchClose=BX.create("a",{attrs:{href:"#close"},props:{className:"bx-messenger-input-close"}}),this.popupContactListSearchInput=BX.create("input",{attrs:{type:"text",placeholder:BX.message(this.BXIM.bitrixIntranet?"IM_M_SEARCH_PLACEHOLDER_CP":"IM_M_SEARCH_PLACEHOLDER"),value:this.contactListSearchText},props:{className:"bx-messenger-input"}})]})]}),this.popupContactListElements=BX.create("div",{props:{className:"bx-messenger-cl"},style:{height:this.popupContactListElementsSize+"px"},children:[this.popupContactListElementsWrap=BX.create("div",{props:{className:"bx-messenger-cl-wrap bx-messenger-recent-wrap"}})]}),this.desktop.run()?null:BX.create("div",{props:{className:"bx-messenger-cl-notify-wrap"},children:[this.notify.messengerNotifyButton=BX.create("div",{props:{className:"bx-messenger-cl-notify-button"},events:{click:BX.delegate(this.notify.openNotify,this.notify)},children:[BX.create("span",{props:{className:"bx-messenger-cl-notify-text"},html:BX.message("IM_NOTIFY_BUTTON_TITLE")}),this.notify.messengerNotifyButtonCount=BX.create("span",{props:{className:"bx-messenger-cl-count"},html:parseInt(this.notify.notifyCount)>0?'<span class="bx-messenger-cl-count-digit">'+this.notify.notifyCount+"</span>":""})]})]}),BX.create("div",{props:{className:"bx-messenger-cl-panel"},children:[BX.create("div",{props:{className:"bx-messenger-cl-panel-wrap"},children:[this.contactListPanelStatus=BX.create("span",{props:{className:"bx-messenger-cl-panel-status-wrap bx-messenger-cl-panel-status-"+BX.MessengerCommon.getUserStatus()},html:'<span class="bx-messenger-cl-panel-status"></span><span class="bx-messenger-cl-panel-status-text">'+BX.message("IM_STATUS_"+BX.MessengerCommon.getUserStatus().toUpperCase())+'</span><span class="bx-messenger-cl-panel-status-arrow"></span>'}),BX.create("span",{props:{className:"bx-messenger-cl-panel-right-wrap"},children:[this.contactListPanelSettings=this.desktop.run()?null:BX.create("span",{props:{title:BX.message("IM_SETTINGS"),className:"bx-messenger-cl-panel-settings-wrap"}})]})]})]})]}),this.popupMessengerDialog=BX.create("div",{props:{className:"bx-messenger-box-dialog"},style:{marginLeft:this.popupContactListSize+"px"},children:[this.popupMessengerPanel=BX.create("div",{props:{className:"bx-messenger-panel"+(this.openChatFlag?" bx-messenger-hide":"")},children:[BX.create("a",{attrs:{href:this.users[this.currentTab]?this.users[this.currentTab].profile:BX.MessengerCommon.getUserParam().profile},props:{className:"bx-messenger-panel-avatar bx-messenger-panel-avatar-status-"+BX.MessengerCommon.getUserStatus(this.users[this.currentTab]?this.currentTab:"")},children:[this.popupMessengerPanelAvatar=BX.create("img",{attrs:{src:this.BXIM.pathToBlankImage},props:{className:"bx-messenger-panel-avatar-img bx-messenger-panel-avatar-img-default"}}),BX.create("span",{props:{className:"bx-messenger-panel-avatar-status"}})],events:{mouseover:BX.delegate(function(e){if(this.users[this.currentTab]){BX.proxy_context.title=BX.MessengerCommon.getUserStatus(this.currentTab,true)}},this)}}),BX.create("a",{attrs:{href:"#history",title:BX.message("IM_M_OPEN_HISTORY_2")},props:{className:"bx-messenger-panel-button bx-messenger-panel-history"},events:{click:BX.delegate(function(e){this.openHistory(this.currentTab);BX.PreventDefault(e)},this)}}),this.popupMessengerPanelCall1=this.callButton(),this.enableGroupChat?BX.create("a",{attrs:{href:"#chat",title:BX.message("IM_M_CHAT_TITLE")},props:{className:"bx-messenger-panel-button bx-messenger-panel-chat"},events:{click:BX.delegate(function(e){this.openChatDialog({type:"CHAT_ADD",bind:BX.proxy_context});BX.PreventDefault(e)},this)}}):null,BX.create("span",{props:{className:"bx-messenger-panel-title"},children:[this.popupMessengerPanelTitle=BX.create("a",{props:{className:"bx-messenger-panel-title-link"+(this.users[this.currentTab]&&this.users[this.currentTab].extranet?" bx-messenger-user-extranet":"")},attrs:{href:this.users[this.currentTab]?this.users[this.currentTab].profile:BX.MessengerCommon.getUserParam().profile},html:this.users[this.currentTab]?this.users[this.currentTab].name:""})]}),this.popupMessengerPanelStatus=BX.create("span",{props:{className:"bx-messenger-panel-desc"},html:BX.MessengerCommon.getUserPosition(this.currentTab)})]}),this.popupMessengerPanel2=BX.create("div",{props:{className:"bx-messenger-panel"+(this.openChatFlag&&!this.openCallFlag?"":" bx-messenger-hide")},children:[this.popupMessengerPanelAvatarForm2=BX.create("form",{attrs:{action:this.BXIM.pathToFileAjax},props:{className:"bx-messenger-panel-avatar bx-messenger-panel-avatar-group"},children:[BX.create("div",{props:{className:"bx-messenger-panel-avatar-progress"},html:'<div class="bx-messenger-panel-avatar-progress-image"></div>'}),BX.create("input",{attrs:{type:"hidden",name:"IM_AVATAR_UPDATE",value:"Y"}}),this.popupMessengerPanelAvatarId2=BX.create("input",{attrs:{type:"hidden",name:"CHAT_ID",value:this.currentTab.toString().substr(4)}}),BX.create("input",{attrs:{type:"hidden",name:"IM_AJAX_CALL",value:"Y"}}),this.popupMessengerPanelAvatarUpload2=this.disk.lightVersion||!this.BXIM.ppServerStatus?null:BX.create("input",{attrs:{type:"file",title:BX.message("IM_M_AVATAR_UPLOAD")},props:{className:"bx-messenger-panel-avatar-upload"}}),this.popupMessengerPanelAvatar2=BX.create("img",{attrs:{src:this.BXIM.pathToBlankImage},props:{className:"bx-messenger-panel-avatar-img bx-messenger-panel-avatar-img-default"}}),this.popupMessengerPanelStatus2=BX.create("span",{props:{className:"bx-messenger-panel-avatar-status "+(this.userChatBlockStatus[this.currentTab.toString().substr(4)]&&this.userChatBlockStatus[this.currentTab.toString().substr(4)][this.BXIM.userId]=="Y"?"bx-messenger-panel-avatar-status-notify-block":"bx-messenger-panel-avatar-status-chat")}})]}),this.popupMessengerPanelCall2=this.callButton(),this.enableGroupChat?BX.create("a",{attrs:{href:"#chat",title:BX.message("IM_M_CHAT_TITLE")},props:{className:"bx-messenger-panel-button bx-messenger-panel-chat"},events:{click:BX.delegate(function(e){this.openChatDialog({chatId:this.currentTab.toString().substr(4),type:"CHAT_EXTEND",bind:BX.proxy_context});BX.PreventDefault(e)},this)}}):null,BX.create("a",{attrs:{href:"#history",title:BX.message("IM_M_OPEN_HISTORY_2")},props:{className:"bx-messenger-panel-button bx-messenger-panel-history"},events:{click:BX.delegate(function(e){this.openHistory(this.currentTab);BX.PreventDefault(e)},this)}}),this.popupMessengerPanelChatTitle=BX.create("span",{props:{className:"bx-messenger-panel-title bx-messenger-panel-title-chat"},html:this.chat[this.currentTab.toString().substr(4)]?this.chat[this.currentTab.toString().substr(4)].name:BX.message("IM_CL_LOAD")}),BX.create("span",{props:{className:"bx-messenger-panel-desc"},children:[this.popupMessengerPanelUsers=BX.create("div",{props:{className:"bx-messenger-panel-chat-users"},html:BX.message("IM_CL_LOAD")})]})]}),this.popupMessengerPanel3=BX.create("div",{props:{className:"bx-messenger-panel"+(this.openChatFlag&&this.openCallFlag?"":" bx-messenger-hide")},children:[this.popupMessengerPanelAvatarForm3=BX.create("form",{attrs:{action:this.BXIM.pathToFileAjax},props:{className:"bx-messenger-panel-avatar bx-messenger-panel-avatar-call"},children:[BX.create("div",{props:{className:"bx-messenger-panel-avatar-progress"},html:'<div class="bx-messenger-panel-avatar-progress-image"></div>'}),BX.create("input",{attrs:{type:"hidden",name:"IM_AVATAR_UPDATE",value:"Y"}}),this.popupMessengerPanelAvatarId3=BX.create("input",{attrs:{type:"hidden",name:"CHAT_ID",value:this.currentTab.toString().substr(4)}}),BX.create("input",{attrs:{type:"hidden",name:"IM_AJAX_CALL",value:"Y"}}),this.popupMessengerPanelAvatarUpload3=this.disk.lightVersion||!this.BXIM.ppServerStatus?null:BX.create("input",{attrs:{type:"file",title:BX.message("IM_M_AVATAR_UPLOAD_2")},props:{className:"bx-messenger-panel-avatar-upload"}}),this.popupMessengerPanelAvatar3=BX.create("img",{attrs:{src:this.BXIM.pathToBlankImage},props:{className:"bx-messenger-panel-avatar-img bx-messenger-panel-avatar-img-default"}})]}),BX.create("a",{attrs:{href:"#history",title:BX.message("IM_M_OPEN_HISTORY_2")},props:{className:"bx-messenger-panel-button bx-messenger-panel-history"},events:{click:BX.delegate(function(e){this.openHistory(this.currentTab);BX.PreventDefault(e)},this)}}),this.popupMessengerPanelCall3=this.callButton("call"),this.popupMessengerPanelCallTitle=BX.create("span",{props:{className:"bx-messenger-panel-title"},html:this.chat[this.currentTab.toString().substr(4)]?this.chat[this.currentTab.toString().substr(4)].name:BX.message("IM_CL_LOAD")}),BX.create("span",{props:{className:"bx-messenger-panel-desc"},html:BX.message("IM_PHONE_DESC")})]}),this.popupMessengerConnectionStatus=BX.create("div",{props:{className:"bx-messenger-connection-status "+(this.popupMessengerConnectionStatusState=="online"?"bx-messenger-connection-status-hide":"bx-messenger-connection-status-show bx-messenger-connection-status-"+this.popupMessengerConnectionStatusState)},children:[BX.create("div",{props:{className:"bx-messenger-connection-status-wrap"},children:[this.popupMessengerConnectionStatusText=BX.create("span",{props:{className:"bx-messenger-connection-status-text"},html:this.popupMessengerConnectionStatusStateText}),BX.create("span",{props:{className:"bx-messenger-connection-status-text-reload"},children:[BX.create("span",{props:{className:"bx-messenger-connection-status-text-reload-title"},html:BX.message("IM_CS_RELOAD")}),BX.create("span",{props:{className:"bx-messenger-connection-status-text-reload-hotkey"},html:BX.browser.IsMac()?"&#8984;+R":"Ctrl+R"})],events:{click:function(){location.reload()}}})]})]}),this.popupMessengerEditForm=BX.create("div",{props:{className:"bx-messenger-editform bx-messenger-editform-disable"},children:[BX.create("div",{props:{className:"bx-messenger-editform-wrap"},children:[BX.create("div",{props:{className:"bx-messenger-editform-textarea"},children:[this.popupMessengerEditTextarea=BX.create("textarea",{props:{value:"",className:"bx-messenger-editform-textarea-input"},style:{height:this.popupMessengerTextareaSize+"px"}})]}),BX.create("div",{props:{className:"bx-messenger-editform-buttons"},children:[BX.create("span",{props:{className:"popup-window-button popup-window-button-accept"},children:[BX.create("span",{props:{className:"popup-window-button-left"}}),BX.create("span",{props:{className:"popup-window-button-text"},html:BX.message("IM_M_CHAT_BTN_EDIT")}),BX.create("span",{props:{className:"popup-window-button-right"}})],events:{click:BX.delegate(function(e){this.editMessageAjax(this.popupMessengerEditMessageId,this.popupMessengerEditTextarea.value)},this)}}),BX.create("span",{props:{className:"popup-window-button"},children:[BX.create("span",{props:{className:"popup-window-button-left"}}),BX.create("span",{props:{className:"popup-window-button-text"},html:BX.message("IM_M_CHAT_BTN_CANCEL")}),BX.create("span",{props:{className:"popup-window-button-right"}})],events:{click:BX.delegate(function(e){this.editMessageCancel()},this)}}),BX.create("span",{props:{className:"bx-messenger-editform-progress"},html:BX.message("IM_MESSAGE_EDIT_TEXT")})]})]})]}),this.popupMessengerBodyDialog=BX.create("div",{props:{className:"bx-messenger-body-dialog bxu-file-input-over"},children:[this.popupMessengerFileDropZone=!this.disk.enable?null:BX.create("div",{props:{className:"bx-messenger-file-dropzone"},children:[BX.create("div",{props:{className:"bx-messenger-file-dropzone-wrap"},children:[BX.create("div",{props:{className:"bx-messenger-file-dropzone-icon"}}),BX.create("div",{props:{className:"bx-messenger-file-dropzone-text"},html:BX.message("IM_F_DND_TEXT")})]})]}),this.popupMessengerBody=BX.create("div",{props:{className:"bx-messenger-body"},style:{height:this.popupMessengerBodySize+"px"},children:[this.popupMessengerBodyWrap=BX.create("div",{props:{className:"bx-messenger-body-wrap"}})]}),this.popupMessengerTextareaPlace=BX.create("div",{props:{className:"bx-messenger-textarea-place"},children:[BX.create("div",{props:{className:"bx-messenger-textarea-resize"},events:{mousedown:BX.delegate(this.resizeTextareaStart,this)}}),BX.create("div",{props:{className:"bx-messenger-textarea-send"},children:[BX.create("div",{attrs:{title:BX.message("IM_SMILE_MENU")},props:{className:"bx-messenger-textarea-smile"},events:{click:BX.delegate(function(e){this.openSmileMenu();return BX.PreventDefault(e)},this)}}),BX.create("a",{attrs:{href:"#send"},props:{className:"bx-messenger-textarea-send-button"},events:{click:BX.delegate(this.sendMessage,this)}}),this.popupMessengerTextareaSendType=BX.create("span",{attrs:{title:BX.message("IM_M_SEND_TYPE_TITLE")},props:{className:"bx-messenger-textarea-cntr-enter"},html:this.BXIM.settings.sendByEnter?"Enter":BX.browser.IsMac()?"&#8984;+Enter":"Ctrl+Enter"})]}),this.popupMessengerFileButton=!this.disk.enable?null:BX.create("div",{attrs:{title:BX.message("IM_F_UPLOAD_MENU")},props:{className:"bx-messenger-textarea-file"+(this.disk.lightVersion?" bx-messenger-textarea-file-light":"")},children:[BX.create("div",{attrs:{title:this.BXIM.ieVersion>1?BX.message("IM_F_UPLOAD_MENU"):" "},props:{className:"bx-messenger-textarea-file-popup"},children:[this.popupMessengerFileForm=BX.create("form",{attrs:{action:this.BXIM.pathToFileAjax,style:this.disk.lightVersion?"z-index: 0":""},props:{className:"bx-messenger-textarea-file-form"},children:[BX.create("input",{attrs:{type:"hidden",name:"IM_FILE_UPLOAD",value:"Y"}}),this.popupMessengerFileFormChatId=BX.create("input",{attrs:{type:"hidden",name:"CHAT_ID",value:0}}),this.popupMessengerFileFormRegChatId=BX.create("input",{attrs:{type:"hidden",name:"REG_CHAT_ID",value:0}}),this.popupMessengerFileFormRegMessageId=BX.create("input",{attrs:{type:"hidden",name:"REG_MESSAGE_ID",value:0}}),this.popupMessengerFileFormRegParams=BX.create("input",{attrs:{type:"hidden",name:"REG_PARAMS",value:""}}),BX.create("input",{attrs:{type:"hidden",name:"IM_AJAX_CALL",value:"Y"}}),this.popupMessengerFileFormInput=BX.create("input",{attrs:{type:"file",multiple:"true",title:this.BXIM.ieVersion>1?BX.message("IM_F_UPLOAD_MENU"):" "},props:{className:"bx-messenger-textarea-file-popup-input"}})]}),this.disk.lightVersion?null:BX.create("div",{props:{className:"bx-messenger-popup-menu-item"},html:BX.message("IM_F_UPLOAD_MENU_1")}),this.disk.lightVersion?null:BX.create("div",{props:{className:"bx-messenger-menu-hr"}}),BX.create("div",{props:{className:"bx-messenger-popup-menu-item"},html:BX.message("IM_F_UPLOAD_MENU_2"),events:{click:BX.delegate(function(){this.disk.openFileDialog()},this)}}),BX.create("div",{props:{className:"bx-messenger-textarea-file-popup-arrow"}})]})],events:{click:BX.delegate(function(e){if(this.popupMessengerConnectionStatusState!="online")return false;if(BX.hasClass(this.popupMessengerFileButton,"bx-messenger-textarea-file-active")){setTimeout(BX.delegate(function(){this.closePopupFileMenu()},this),100)}else{if(parseInt(this.popupMessengerFileFormChatId.value)<=0||this.popupMessengerFileFormInput.getAttribute("disabled"))return false;this.closeMenuPopup();this.popupPopupMenuDateCreate=+new Date;BX.addClass(this.popupMessengerFileButton,"bx-messenger-textarea-file-active");if(this.desktop.run()){BX.addClass(this.popupMessengerFileButton,"bx-messenger-textarea-file-desktop")}this.setClosingByEsc(false)}},this)}}),BX.create("div",{props:{className:"bx-messenger-textarea"},children:[this.popupMessengerTextarea=BX.create("textarea",{props:{value:this.textareaHistory[e]?this.textareaHistory[e]:"",className:"bx-messenger-textarea-input"},style:{height:this.popupMessengerTextareaSize+"px"}})]}),BX.create("div",{props:{className:"bx-messenger-textarea-clear"}}),this.BXIM.desktop.run()?null:BX.create("span",{props:{className:"bx-messenger-resize"},events:{mousedown:BX.delegate(this.resizeWindowStart,this)}})]})]})]}),this.popupMessengerExtra=BX.create("div",{props:{className:"bx-messenger-box-extra"},style:{marginLeft:this.popupContactListSize+"px",height:this.popupMessengerFullHeight+"px"}})]});this.BXIM.dialogOpen=true;if(this.desktop.run()){var n=this.BXIM.bitrixIntranet?!BX.browser.IsMac()?BX.message("IM_DESKTOP_B24_TITLE"):BX.message("IM_DESKTOP_B24_OSX_TITLE"):BX.message("IM_WM");BX.desktop.setWindowTitle(n);this.popupMessenger=new BX.PopupWindowDesktop(this.BXIM);BX.desktop.setTabContent("im",this.popupMessengerContent);BX.bind(this.popupMessengerContent,"click",BX.delegate(this.closePopupFileMenu,this));this.disk.chatDialogInit();this.disk.chatAvatarInit()}else{this.popupMessenger=new BX.PopupWindow("bx-messenger-popup-messenger",null,{lightShadow:true,autoHide:false,closeByEsc:true,overlay:{opacity:50,backgroundColor:"#000000"},draggable:{restrict:true},events:{onPopupShow:BX.delegate(function(){this.disk.chatDialogInit();this.disk.chatAvatarInit()},this),onPopupClose:function(){this.destroy()},onPopupDestroy:BX.delegate(function(){if(this.BXIM.popupSettings!=null)this.BXIM.popupSettings.close();if(this.webrtc.callInit){this.webrtc.callCommand(this.webrtc.callChatId,"decline",{ACTIVE:this.callActive?"Y":"N",INITIATOR:this.initiator?"Y":"N"});this.webrtc.callAbort()}this.closeMenuPopup();this.popupMessenger=null;this.popupMessengerContent=null;this.BXIM.extraOpen=false;this.BXIM.dialogOpen=false;this.BXIM.notifyOpen=false;clearTimeout(this.popupMessengerDesktopTimeout);this.setUpdateStateStep();BX.unbind(document,"click",BX.proxy(this.BXIM.autoHide,this.BXIM));BX.unbind(window,"keydown",BX.proxy(this.closePopupFileMenuKeydown,this));this.webrtc.callOverlayClose()},this)},titleBar:{content:BX.create("span",{props:{className:"bx-messenger-title"},html:this.BXIM.bitrixIntranet?BX.message("IM_BC"):BX.message("IM_WM")})},closeIcon:{top:"10px",right:"13px"},content:this.popupMessengerContent});this.popupMessenger.show();BX.bind(this.popupMessenger.popupContainer,"click",BX.MessengerCommon.preventDefault);if(this.webrtc.ready()){BX.addCustomEvent(this.popupMessenger,"onPopupDragStart",BX.delegate(function(){if(this.webrtc.callDialogAllow!=null)this.webrtc.callDialogAllow.destroy()},this))}BX.bind(document,"click",BX.proxy(this.BXIM.autoHide,this.BXIM));BX.bind(window,"keydown",BX.proxy(this.closePopupFileMenuKeydown,this))}this.popupMessengerTopLine=BX.create("div",{props:{className:"bx-messenger-box-topline"}});this.popupMessengerContent.insertBefore(this.popupMessengerTopLine,this.popupMessengerContent.firstChild);if(!this.desktop.run()&&this.BXIM.bitrixIntranet&&this.BXIM.platformName!=""&&this.BXIM.settings.bxdNotify){clearTimeout(this.popupMessengerDesktopTimeout);this.popupMessengerDesktopTimeout=setTimeout(BX.delegate(function(){var e=BX.delegate(function(){window.open(BX.browser.IsMac()?"http://dl.bitrix24.com/b24/bitrix24_desktop.dmg":"http://dl.bitrix24.com/b24/bitrix24_desktop.exe","desktopApp");this.BXIM.settings.bxdNotify=false;this.BXIM.saveSettings({bxdNotify:this.BXIM.settings.bxdNotify});this.hideTopLine()},this);var t=BX.delegate(function(){this.BXIM.settings.bxdNotify=false;this.BXIM.saveSettings({bxdNotify:this.BXIM.settings.bxdNotify});this.hideTopLine()},this);this.showTopLine(BX.message("IM_DESKTOP_INSTALL").replace("#WM_NAME#",this.BXIM.bitrixIntranet?BX.message("IM_BC"):BX.message("IM_WM")).replace("#OS#",this.BXIM.platformName),[{title:BX.message("IM_DESKTOP_INSTALL_Y"),callback:e},{title:BX.message("IM_DESKTOP_INSTALL_N"),callback:t}])},this),15e3)}if(this.webrtc.callNotify!=null){if(this.webrtc.ready()){this.setClosingByEsc(false);BX.addClass(BX("bx-messenger-popup-messenger"),"bx-messenger-popup-messenger-dont-close");BX.removeClass(this.webrtc.callNotify.contentContainer.children[0],"bx-messenger-call-overlay-float");this.popupMessengerContent.insertBefore(this.webrtc.callNotify.contentContainer.children[0],this.popupMessengerContent.firstChild);this.webrtc.callNotify.close()}else{this.webrtc.callOverlayClose(false)}}BX.MessengerCommon.userListRedraw();if(this.BXIM.quirksMode){this.popupContactListWrap.style.position="absolute";this.popupContactListWrap.style.display="block"}this.setUpdateStateStep();if(!(BX.browser.IsAndroid()||BX.browser.IsIOS())&&this.popupMessenger!=null){if(t&&this.popupContactListSearchInput!=null){setTimeout(BX.delegate(function(){this.popupContactListSearchInput.focus()},this),50)}else{setTimeout(BX.delegate(function(){this.popupMessengerTextarea.focus()},this),50)}}BX.bind(this.recentListTab,"click",BX.delegate(function(e){var t={};if(e.metaKey==true||e.ctrlKey==true)t.showOnlyChat=true;BX.MessengerCommon.recentListRedraw(t)},this));if(this.webrtc.phoneEnabled){if(!this.desktop.run()){BX.bind(this.popupContactListSearchCall,"click",BX.delegate(this.webrtc.openKeyPad,this.webrtc))}}BX.bind(this.contactListTab,"click",BX.delegate(function(){this.contactListSearchText="";this.popupContactListSearchInput.value="";BX.MessengerCommon.contactListRedraw()},this));BX.bind(this.popupContactListSearchClose,"click",BX.delegate(BX.MessengerCommon.contactListSearchClear,BX.MessengerCommon));BX.bind(this.popupContactListSearchInput,"focus",BX.delegate(function(){this.setClosingByEsc(false)},this));BX.bind(this.popupContactListSearchInput,"blur",BX.delegate(function(){this.setClosingByEsc(true)},this));if(this.desktop.ready()){BX.bind(this.popupContactListSearchInput,"contextmenu",BX.delegate(function(e){this.openPopupMenu(e,"copypaste",false);return BX.PreventDefault(e)},this))}BX.bind(this.popupContactListSearchInput,"keyup",BX.delegate(BX.MessengerCommon.contactListSearch,BX.MessengerCommon));BX.bind(this.popupMessengerPanelChatTitle,"click",BX.delegate(this.renameChatDialog,this));BX.bindDelegate(this.popupMessengerPanelUsers,"click",{className:"bx-messenger-panel-chat-user"},BX.delegate(function(e){this.openPopupMenu(BX.proxy_context,"chatUser");return BX.PreventDefault(e)},this));BX.bindDelegate(this.popupMessengerPanelUsers,"click",{className:"bx-notifier-popup-user-more"},BX.delegate(function(e){if(this.popupChatUsers!=null){this.popupChatUsers.destroy();return false}var t=this.currentTab.toString().substr(4);var s='<span class="bx-notifier-item-help-popup">';for(var i=parseInt(BX.proxy_context.getAttribute("data-last-item"));i<this.userInChat[t].length;i++){if(this.userInChat[t][i])s+='<span class="bx-notifier-item-help-popup-img bx-messenger-panel-chat-user" data-userId="'+this.userInChat[t][i]+'"><span class="bx-notifier-popup-avatar  bx-notifier-popup-avatar-status-'+BX.MessengerCommon.getUserStatus(this.userInChat[t][i])+'"><img class="bx-notifier-popup-avatar-img'+(BX.MessengerCommon.isBlankAvatar(this.users[this.userInChat[t][i]].avatar)?" bx-notifier-popup-avatar-img-default":"")+'" src="'+this.users[this.userInChat[t][i]].avatar+'"></span><span class="bx-notifier-item-help-popup-name  '+(this.users[this.userInChat[t][i]].extranet?" bx-notifier-popup-avatar-extranet":"")+'">'+this.users[this.userInChat[t][i]].name+"</span></span>"}s+="</span>";this.popupChatUsers=new BX.PopupWindow("bx-messenger-popup-chat-users",BX.proxy_context,{zIndex:200,lightShadow:true,offsetTop:-2,offsetLeft:3,autoHide:true,closeByEsc:true,events:{onPopupClose:function(){this.destroy()},onPopupDestroy:BX.proxy(function(){this.popupChatUsers=null},this)},content:BX.create("div",{props:{className:"bx-messenger-popup-menu"},html:s})});this.popupChatUsers.setAngle({offset:BX.proxy_context.offsetWidth});this.popupChatUsers.show();BX.bindDelegate(this.popupChatUsers.popupContainer,"click",{className:"bx-messenger-panel-chat-user"},BX.delegate(function(e){this.openPopupMenu(BX.proxy_context,"chatUser");return BX.PreventDefault(e)},this));return BX.PreventDefault(e)},this));BX.bindDelegate(this.popupContactListElements,"contextmenu",{className:"bx-messenger-cl-item"},BX.delegate(function(e){this.openPopupMenu(BX.proxy_context,"contactList");return BX.PreventDefault(e)},this));BX.bindDelegate(this.popupContactListElements,"click",{className:"bx-messenger-cl-item"},BX.delegate(BX.MessengerCommon.contactListClickItem,BX.MessengerCommon));BX.bind(this.popupContactListElements,"scroll",BX.delegate(function(){if(this.popupPopupMenu!=null&&this.popupPopupMenuDateCreate+500<+new Date)this.popupPopupMenu.close()},this));BX.bindDelegate(this.popupContactListElements,"click",{className:"bx-messenger-cl-group-title"},BX.delegate(BX.MessengerCommon.contactListToggleGroup,BX.MessengerCommon));BX.bind(this.contactListPanelStatus,"click",BX.delegate(function(e){this.openPopupMenu(this.contactListPanelStatus,"status");return BX.PreventDefault(e)},this));if(this.contactListPanelSettings)BX.bind(this.contactListPanelSettings,"click",BX.delegate(function(e){this.openSettings();BX.PreventDefault(e)},this.BXIM));BX.bind(this.popupMessengerEditTextarea,"focus",BX.delegate(function(){this.setClosingByEsc(false)},this));BX.bind(this.popupMessengerEditTextarea,"blur",BX.delegate(function(){this.setClosingByEsc(true)},this));BX.bind(this.popupMessengerEditTextarea,"keydown",BX.delegate(function(e){this.textareaPrepareText(BX.proxy_context,e,BX.delegate(function(){this.editMessageAjax(this.popupMessengerEditMessageId,this.popupMessengerEditTextarea.value)},this),BX.delegate(function(){this.editMessageCancel()},this))},this));BX.bind(this.popupMessengerBody,"scroll",BX.delegate(function(){if(this.unreadMessage[this.currentTab]&&this.unreadMessage[this.currentTab].length>0&&BX.MessengerCommon.isScrollMax(this.popupMessengerBody,200)&&this.BXIM.isFocus()){
clearTimeout(this.readMessageTimeout);this.readMessageTimeout=setTimeout(BX.delegate(function(){BX.MessengerCommon.readMessage(this.currentTab)},this),100)}if(typeof this.popupMessengerBodyWrap.getElementsByClassName!="undefined"){var e={};var t=this.popupMessengerBodyWrap.getElementsByClassName("bx-messenger-content-group");var s=this.popupMessengerBody.getBoundingClientRect().top;for(var i=0;i<t.length;i++){e=BX.MessengerCommon.isElementCoordsBelow(t[i],this.popupMessengerBody,33,true);if(t[i].className!="bx-messenger-content-group bx-messenger-content-group-today"){t[i].className="bx-messenger-content-group "+(e.top?"":"bx-messenger-content-group-float");t[i].firstChild.nextSibling.style.marginLeft=e.top?"":Math.round(t[i].offsetWidth/2-t[i].firstChild.nextSibling.offsetWidth/2)+"px";t[i].firstChild.nextSibling.style.marginTop=e.top?"":-e.coords.top+14+"px"}if(!e.top&&t[i-1]){t[i-1].className="bx-messenger-content-group";t[i-1].firstChild.nextSibling.style.marginLeft="";t[i-1].firstChild.nextSibling.style.marginTop=""}}}BX.MessengerCommon.loadHistory(this.currentTab,false)},this));if(this.desktop.ready()){BX.bind(this.popupMessengerTextarea,"contextmenu",BX.delegate(function(e){this.openPopupMenu(e,"copypaste",false);return BX.PreventDefault(e)},this))}BX.bind(this.popupMessengerTextarea,"focus",BX.delegate(function(){this.setClosingByEsc(false)},this));BX.bind(this.popupMessengerTextarea,"blur",BX.delegate(function(){this.setClosingByEsc(true)},this));BX.bind(this.popupMessengerTextarea,"keydown",BX.delegate(function(e){this.textareaPrepareText(BX.proxy_context,e,BX.delegate(this.sendMessage,this),BX.delegate(function(){if(BX.util.trim(this.popupMessengerEditTextarea.value).length<=0){this.popupMessengerEditTextarea.value="";if(this.popupMessenger&&!this.webrtc.callInit&&this.popupMessengerEditTextarea.value.length<=0)this.popupMessenger.destroy()}else{this.popupMessengerEditTextarea.value=""}},this))},this));BX.bind(this.popupMessengerTextareaSendType,"click",BX.delegate(function(){this.BXIM.settings.sendByEnter=this.BXIM.settings.sendByEnter?false:true;this.BXIM.saveSettings({sendByEnter:this.BXIM.settings.sendByEnter});BX.proxy_context.innerHTML=this.BXIM.settings.sendByEnter?"Enter":BX.browser.IsMac()?"&#8984;+Enter":"Ctrl+Enter"},this));if(this.desktop.ready()){BX.bindDelegate(this.popupMessengerBodyWrap,"contextmenu",{className:"bx-messenger-content-item-content"},BX.delegate(function(e){this.openPopupMenu(e,"dialogContext",false);return BX.PreventDefault(e)},this))}BX.bindDelegate(this.popupMessengerBodyWrap,"click",{className:"bx-messenger-content-item-avatar-button"},BX.delegate(function(e){var t=BX.proxy_context.parentNode.parentNode.getAttribute("data-senderId");if(!this.users[t]||this.users[t].fake)return false;var s=BX.util.htmlspecialcharsback(this.users[t].name);if(e.metaKey||e.ctrlKey){s="[USER="+t+"]"+s+"[/USER]"}else{s=s+","}this.insertTextareaText(this.popupMessengerTextarea," "+s+" ",false);this.popupMessengerTextarea.focus();return BX.PreventDefault(e)},this));BX.bindDelegate(this.popupMessengerBodyWrap,"click",{className:"bx-messenger-content-item-menu"},BX.delegate(function(e){if(e.metaKey||e.ctrlKey){var t=BX.proxy_context.nextSibling.id.replace("im-message-","");if(this.message[t]&&this.users[this.message[t].senderId].name){var s=[];if(this.message[t].text){s.push(BX.MessengerCommon.prepareTextBack(this.message[t].text))}if(this.message[t].params&&this.message[t].params.FILE_ID){for(var i=0;i<this.message[t].params.FILE_ID.length;i++){var a=this.message[t].params.FILE_ID[i];var n=this.message[t].chatId;if(this.disk.files[n][a]){s.push("["+BX.message("IM_F_FILE")+": "+this.disk.files[n][a].name+"]")}}}if(s.length>0){this.insertQuoteText(this.users[this.message[t].senderId].name,this.message[t].date,s.join("\n"))}}}else{this.openPopupMenu(BX.proxy_context,"dialogMenu")}return BX.PreventDefault(e)},this));BX.bindDelegate(this.popupMessengerBodyWrap,"click",{className:"bx-messenger-content-like-digit"},BX.delegate(function(e){var t=BX.proxy_context.parentNode.parentNode.parentNode.parentNode.parentNode.getAttribute("data-blockmessageid");if(t.substr(0,4)=="temp"||!this.message[t].params||!this.message[t].params["LIKE"]||this.message[t].params["LIKE"].length<=0)return false;if(this.popupChatUsers!=null){this.popupChatUsers.destroy();return false}var s='<span class="bx-notifier-item-help-popup">';for(var i=0;i<this.message[t].params["LIKE"].length;i++){if(this.users[this.message[t].params["LIKE"][i]])s+='<span class="bx-notifier-item-help-popup-img bx-messenger-panel-chat-user" data-userId="'+this.message[t].params["LIKE"][i]+'"><span class="bx-notifier-popup-avatar  bx-notifier-popup-avatar-status-'+BX.MessengerCommon.getUserStatus(this.message[t].params["LIKE"][i])+'"><img class="bx-notifier-popup-avatar-img'+(BX.MessengerCommon.isBlankAvatar(this.users[this.message[t].params["LIKE"][i]].avatar)?" bx-notifier-popup-avatar-img-default":"")+'" src="'+this.users[this.message[t].params["LIKE"][i]].avatar+'"></span><span class="bx-notifier-item-help-popup-name  '+(this.users[this.message[t].params["LIKE"][i]].extranet?" bx-notifier-popup-avatar-extranet":"")+'">'+this.users[this.message[t].params["LIKE"][i]].name+"</span></span>"}s+="</span>";this.popupChatUsers=new BX.PopupWindow("bx-messenger-popup-chat-users",BX.proxy_context,{zIndex:200,lightShadow:true,offsetTop:-2,offsetLeft:3,autoHide:true,closeByEsc:true,bindOptions:{position:"top"},events:{onPopupClose:function(){this.destroy()},onPopupDestroy:BX.proxy(function(){this.popupChatUsers=null},this)},content:BX.create("div",{props:{className:"bx-messenger-popup-menu"},html:s})});this.popupChatUsers.setAngle({offset:BX.proxy_context.offsetWidth});this.popupChatUsers.show();BX.bindDelegate(this.popupChatUsers.popupContainer,"click",{className:"bx-messenger-panel-chat-user"},BX.delegate(function(e){this.openPopupMenu(BX.proxy_context,"chatUser");return BX.PreventDefault(e)},this));return BX.PreventDefault(e)},this));BX.bindDelegate(this.popupMessengerBodyWrap,"click",{className:"bx-messenger-content-like-button"},BX.delegate(function(e){var t=BX.proxy_context.parentNode.parentNode.parentNode.parentNode.parentNode.getAttribute("data-blockmessageid");BX.MessengerCommon.messageLike(t);return BX.PreventDefault(e)},this));BX.bindDelegate(this.popupMessengerBodyWrap,"click",{className:"bx-messenger-ajax"},BX.delegate(function(){if(BX.proxy_context.getAttribute("data-entity")=="user"){this.openPopupExternalData(BX.proxy_context,"user",true,{ID:BX.proxy_context.getAttribute("data-userId")})}else if(this.webrtc.phoneSupport()&&BX.proxy_context.getAttribute("data-entity")=="phoneCallHistory"){this.openPopupExternalData(BX.proxy_context,"phoneCallHistory",true,{ID:BX.proxy_context.getAttribute("data-historyID")})}},this));BX.bind(this.popupMessengerBody,"scroll",BX.delegate(function(){if(this.popupPopupMenu!=null)this.popupPopupMenu.close();if(this.popupChatUsers!=null)this.popupChatUsers.close()},this));BX.bindDelegate(this.popupMessengerBodyWrap,"click",{className:"bx-messenger-content-item-error"},BX.delegate(BX.MessengerCommon.sendMessageRetry,BX.MessengerCommon));if(e==0){this.extraOpen(BX.create("div",{attrs:{style:"padding-top: 300px"},props:{className:"bx-messenger-box-empty"},html:BX.message("IM_M_EMPTY")}))}else BX.MessengerCommon.openDialog(e)};BX.Messenger.prototype.tooltip=function(e,t,s){if(this.popupTooltip!=null)this.popupTooltip.close();s=s||{};s.offsetLeft=s.offsetLeft||0;s.offsetTop=s.offsetTop||this.desktop.ready()?0:-10;this.popupTooltip=new BX.PopupWindow("bx-messenger-tooltip",e,{lightShadow:true,autoHide:true,darkMode:true,offsetLeft:s.offsetLeft,offsetTop:s.offsetTop,closeIcon:{},bindOptions:{position:"top"},events:{onPopupClose:function(){this.destroy()},onPopupDestroy:BX.delegate(function(){this.popupTooltip=null},this)},zIndex:200,content:BX.create("div",{props:{style:"padding-right: 5px;"},html:t})});this.popupTooltip.setAngle({offset:33,position:"bottom"});this.popupTooltip.show();return true};BX.Messenger.prototype.dialogStatusRedraw=function(){if(this.popupMessenger==null)return false;this.popupMessengerPanelCall1.className=this.callButtonStatus(this.currentTab);this.popupMessengerPanelCall2.className=this.callButtonStatus(this.currentTab);this.popupMessengerPanelCall3.className=this.phoneButtonStatus();if(this.openChatFlag){var e=false;if(this.renameChatDialogFlag)e=true;this.redrawChatHeader();if(e)this.renameChatDialog()}else if(this.users[this.currentTab]){if(this.popupMessengerFileFormChatId){this.popupMessengerFileFormChatId.value=this.userChat[this.currentTab]?this.userChat[this.currentTab]:0;if(parseInt(this.popupMessengerFileFormChatId.value)>0){this.popupMessengerFileFormInput.removeAttribute("disabled")}else{this.popupMessengerFileFormInput.setAttribute("disabled","true")}}this.popupMessengerPanelAvatar.parentNode.href=this.users[this.currentTab].profile;this.popupMessengerPanelAvatar.parentNode.className="bx-messenger-panel-avatar bx-messenger-panel-avatar-status-"+BX.MessengerCommon.getUserStatus(this.currentTab);this.popupMessengerPanelAvatar.parentNode.title=BX.MessengerCommon.getUserStatus(this.currentTab,true);this.popupMessengerPanelAvatar.src=this.users[this.currentTab].avatar?this.users[this.currentTab].avatar:this.BXIM.pathToBlankImage;this.popupMessengerPanelAvatar.className="bx-messenger-panel-avatar-img"+(BX.MessengerCommon.isBlankAvatar(this.popupMessengerPanelAvatar.src)?" bx-messenger-panel-avatar-img-default":"");this.popupMessengerPanelTitle.href=this.users[this.currentTab].profile;this.popupMessengerPanelTitle.innerHTML=this.users[this.currentTab].name;this.popupMessengerPanelStatus.innerHTML=BX.MessengerCommon.getUserPosition(this.currentTab);if(this.users[this.currentTab].extranet){BX.addClass(this.popupMessengerPanelTitle,"bx-messenger-user-extranet")}else{BX.removeClass(this.popupMessengerPanelTitle,"bx-messenger-user-extranet")}}return true};BX.Messenger.prototype.callButton=function(e){var t=null;if(e=="call"){t=BX.create("span",{props:{className:this.phoneButtonStatus()},children:[BX.create("a",{attrs:{href:"#call",title:BX.message("IM_PHONE_CALL")},props:{className:"bx-messenger-panel-button bx-messenger-panel-call-audio"},events:{click:BX.delegate(function(e){if(this.webrtc.callInit)return false;var t=this.chat[this.currentTab.toString().substr(4)];if(t.call_number){this.BXIM.phoneTo("+"+t.call_number)}else{this.webrtc.openKeyPad()}BX.PreventDefault(e)},this)},html:'<span class="bx-messenger-panel-button-icon"></span>'})]})}else{t=BX.create("span",{props:{className:this.callButtonStatus(this.currentTab)},children:[BX.create("a",{attrs:{href:"#call",title:BX.message("IM_M_CALL_VIDEO")},props:{className:"bx-messenger-panel-button bx-messenger-panel-call-video"},events:{click:BX.delegate(function(e){if(!this.webrtc.callInit)this.BXIM.callTo(this.currentTab,true);BX.PreventDefault(e)},this)},html:'<span class="bx-messenger-panel-button-icon"></span>'}),BX.create("a",{attrs:{href:"#callMenu"},props:{className:"bx-messenger-panel-call-menu"},events:{click:BX.delegate(function(e){if(!this.webrtc.callInit)this.openPopupMenu(BX.proxy_context,"callMenu");BX.PreventDefault(e)},this)}})]})}return t};BX.Messenger.prototype.callButtonStatus=function(e){var t="bx-messenger-panel-button-box bx-messenger-panel-call-hide";if(this.BXIM.ppServerStatus)t=!this.webrtc.callSupport(e,this)||this.webrtc.callInit?"bx-messenger-panel-button-box bx-messenger-panel-call-disabled":"bx-messenger-panel-button-box bx-messenger-panel-call-enabled";return t};BX.Messenger.prototype.phoneButtonStatus=function(){var e="bx-messenger-panel-call-hide";if(this.BXIM.ppServerStatus)e=this.webrtc.phoneSupport()?"bx-messenger-panel-call-enabled":"bx-messenger-panel-call-disabled";return"bx-messenger-panel-call-phone "+e};BX.Messenger.prototype.muteMessageChat=function(e,t,s){if(!this.chat[e])return false;s=s!=false;if(!this.userChatBlockStatus[e])this.userChatBlockStatus[e]={};if(t){this.userChatBlockStatus[e][this.BXIM.userId]=t}else{if(this.userChatBlockStatus[e][this.BXIM.userId]=="Y")this.userChatBlockStatus[e][this.BXIM.userId]="N";else this.userChatBlockStatus[e][this.BXIM.userId]="Y"}this.dialogStatusRedraw();if(s){BX.localStorage.set("mcl2",{chatId:e,mute:this.userChatBlockStatus[e][this.BXIM.userId]},5);BX.ajax({url:this.BXIM.pathToAjax+"?CHAT_MUTE&V="+this.BXIM.revision,method:"POST",dataType:"json",timeout:60,data:{IM_CHAT_MUTE:"Y",CHAT_ID:e,MUTE:this.userChatBlockStatus[e][this.BXIM.userId],IM_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()}})}};BX.Messenger.prototype.kickFromChat=function(e,t){if(!this.chat[e]&&this.chat[e].owner!=this.BXIM.userId&&!this.userId[t])return false;BX.ajax({url:this.BXIM.pathToAjax+"?CHAT_LEAVE&V="+this.BXIM.revision,method:"POST",dataType:"json",timeout:60,data:{IM_CHAT_LEAVE:"Y",CHAT_ID:e,USER_ID:t,IM_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()},onsuccess:BX.delegate(function(e){if(e.ERROR==""){for(var s=0;s<this.userInChat[e.CHAT_ID].length;s++)if(this.userInChat[e.CHAT_ID][s]==t)delete this.userInChat[e.CHAT_ID][s];if(this.popupMessenger!=null)BX.MessengerCommon.userListRedraw();if(!this.BXIM.ppServerStatus)BX.PULL.updateState(true);BX.localStorage.set("mclk",{chatId:e.CHAT_ID,userId:e.USER_ID},5)}},this)})};BX.Messenger.prototype.redrawChatHeader=function(){if(!this.openChatFlag)return false;var e=this.currentTab.toString().substr(4);if(!this.chat[e])return false;if(this.popupMessengerFileFormChatId){this.popupMessengerFileFormChatId.value=e;if(parseInt(this.popupMessengerFileFormChatId.value)>0){this.popupMessengerFileFormInput.removeAttribute("disabled")}else{this.popupMessengerFileFormInput.setAttribute("disabled","true")}}this.renameChatDialogFlag=false;if(this.chat[e].style=="call"){this.popupMessengerPanelAvatar3.src=this.chat[e].avatar?this.chat[e].avatar:this.BXIM.pathToBlankImage;this.popupMessengerPanelAvatar2.className="bx-messenger-panel-avatar-img"+(BX.MessengerCommon.isBlankAvatar(this.popupMessengerPanelAvatar3.src)?" bx-messenger-panel-avatar-img-default":"");this.popupMessengerPanelCallTitle.innerHTML=this.chat[e].name;this.popupMessengerPanelAvatarId3.value=e;this.disk.avatarFormIsBlocked(e,"popupMessengerPanelAvatarUpload3",this.popupMessengerPanelAvatarForm3);this.popupMessengerPanelStatus2.className="bx-messenger-panel-avatar-status bx-messenger-panel-avatar-status-chat"}else{this.popupMessengerPanelStatus2.className="bx-messenger-panel-avatar-status "+(this.userChatBlockStatus[e]&&this.userChatBlockStatus[e][this.BXIM.userId]=="Y"?"bx-messenger-panel-avatar-status-notify-block":"bx-messenger-panel-avatar-status-chat");this.popupMessengerPanelAvatar2.src=this.chat[e].avatar?this.chat[e].avatar:this.BXIM.pathToBlankImage;this.popupMessengerPanelAvatar2.className="bx-messenger-panel-avatar-img"+(BX.MessengerCommon.isBlankAvatar(this.popupMessengerPanelAvatar2.src)?" bx-messenger-panel-avatar-img-default":"");this.popupMessengerPanelChatTitle.innerHTML=this.chat[e].name;this.popupMessengerPanelAvatarId2.value=e;this.disk.avatarFormIsBlocked(e,"popupMessengerPanelAvatarUpload2",this.popupMessengerPanelAvatarForm2)}this.popupMessengerPanel2.className=this.chat[e].style=="call"?"bx-messenger-panel bx-messenger-hide":"bx-messenger-panel";this.popupMessengerPanel3.className=this.chat[e].style=="call"?"bx-messenger-panel":"bx-messenger-panel bx-messenger-hide";if(!this.userInChat[e])return false;var t=false;this.popupMessengerPanelUsers.innerHTML="";var s=Math.floor(this.popupMessengerPanelUsers.offsetWidth/135);if(s>=this.userInChat[e].length){for(var i=0;i<this.userInChat[e].length&&i<s;i++){var a=this.users[this.userInChat[e][i]];if(a){this.popupMessengerPanelUsers.innerHTML+='<span class="bx-messenger-panel-chat-user" data-userId="'+a.id+'">'+'<span class="bx-notifier-popup-avatar bx-notifier-popup-avatar-status-'+BX.MessengerCommon.getUserStatus(a.id)+(this.chat[e].owner==a.id?" bx-notifier-popup-avatar-owner":"")+(a.extranet?" bx-notifier-popup-avatar-extranet":"")+'">'+'<img class="bx-notifier-popup-avatar-img'+(BX.MessengerCommon.isBlankAvatar(a.avatar)?" bx-notifier-popup-avatar-img-default":"")+'" src="'+a.avatar+'">'+'<span class="bx-notifier-popup-avatar-status-icon"></span>'+"</span>"+'<span class="bx-notifier-popup-user-name'+(a.extranet?" bx-messenger-panel-chat-user-name-extranet":"")+'">'+a.name+"</span>"+"</span>";t=true}}}else{s=Math.floor((this.popupMessengerPanelUsers.offsetWidth-10)/32);for(var i=0;i<this.userInChat[e].length&&i<s;i++){var a=this.users[this.userInChat[e][i]];if(a){this.popupMessengerPanelUsers.innerHTML+='<span class="bx-messenger-panel-chat-user" data-userId="'+a.id+'">'+'<span class="bx-notifier-popup-avatar bx-notifier-popup-avatar-status-'+BX.MessengerCommon.getUserStatus(a.id)+(this.chat[e].owner==a.id?" bx-notifier-popup-avatar-owner":"")+(a.extranet?" bx-notifier-popup-avatar-extranet":"")+'">'+'<img class="bx-notifier-popup-avatar-img'+(BX.MessengerCommon.isBlankAvatar(a.avatar)?" bx-notifier-popup-avatar-img-default":"")+'" src="'+a.avatar+'" title="'+a.name+'">'+'<span class="bx-notifier-popup-avatar-status-icon"></span>'+"</span>"+"</span>";t=true}}if(t&&this.userInChat[e].length>s)this.popupMessengerPanelUsers.innerHTML+='<span class="bx-notifier-popup-user-more" data-last-item="'+i+'">'+BX.message("IM_M_CHAT_MORE_USER").replace("#USER_COUNT#",this.userInChat[e].length-s)+"</span>"}if(!t)this.popupMessengerPanelUsers.innerHTML=BX.message("IM_CL_LOAD")};BX.Messenger.prototype.updateChatAvatar=function(e,t){if(this.chat[e]&&t&&t.length>0){this.chat[e].avatar=t;this.dialogStatusRedraw();BX.MessengerCommon.userListRedraw()}return true};BX.Messenger.prototype.renameChatDialog=function(){if(this.renameChatDialogFlag)return false;this.renameChatDialogFlag=true;var e=this.currentTab.toString().substr(4);this.popupMessengerPanelChatTitle.innerHTML="";BX.adjust(this.popupMessengerPanelChatTitle,{children:[BX.create("div",{props:{className:"bx-messenger-input-wrap bx-messenger-panel-title-chat-input"},children:[this.renameChatDialogInput=BX.create("input",{props:{className:"bx-messenger-input"},attrs:{type:"text",value:BX.util.htmlspecialcharsback(this.chat[e].name)}})]})]});this.renameChatDialogInput.focus();BX.bind(this.renameChatDialogInput,"blur",BX.delegate(function(){this.renameChatDialogInput.value=BX.util.trim(this.renameChatDialogInput.value);if(this.popupMessengerConnectionStatusState=="online"&&this.renameChatDialogInput.value.length>0&&this.chat[e].name!=BX.util.htmlspecialchars(this.renameChatDialogInput.value)){this.chat[e].name=BX.util.htmlspecialchars(this.renameChatDialogInput.value);this.popupMessengerPanelChatTitle.innerHTML=this.chat[e].name;BX.ajax({url:this.BXIM.pathToAjax+"?CHAT_RENAME&V="+this.BXIM.revision,method:"POST",dataType:"json",timeout:60,data:{IM_CHAT_RENAME:"Y",CHAT_ID:e,CHAT_TITLE:this.renameChatDialogInput.value,IM_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()},onsuccess:BX.delegate(function(){if(!this.BXIM.ppServerStatus)BX.PULL.updateState(true)},this)})}BX.remove(this.renameChatDialogInput);this.renameChatDialogInput=null;this.popupMessengerPanelChatTitle.innerHTML=this.chat[e].name;this.renameChatDialogFlag=false},this));BX.bind(this.renameChatDialogInput,"keydown",BX.delegate(function(t){if(t.keyCode==27&&!this.desktop.ready()){this.renameChatDialogInput.value=this.chat[e].name;this.popupMessengerTextarea.focus();return BX.PreventDefault(t)}else if(t.keyCode==9||t.keyCode==13){this.popupMessengerTextarea.focus();return BX.PreventDefault(t)}},this))};BX.Messenger.prototype.openChatDialog=function(e){if(!this.enableGroupChat)return false;if(this.popupChatDialog!=null){this.popupChatDialog.close();return false}var t=null;if(e.type=="CHAT_ADD"||e.type=="CHAT_EXTEND"||e.type=="CALL_INVITE_USER")t=e.type;else return false;e.maxUsers=typeof e.maxUsers=="undefined"?100:parseInt(e.maxUsers);var s=[];if(typeof e.chatId!="undefined"&&this.userInChat[e.chatId]){s=this.userInChat[e.chatId];e.maxUsers=e.maxUsers-this.userInChat[e.chatId].length}var i=e.bind?e.bind:null;this.popupChatDialog=new BX.PopupWindow("bx-messenger-popup-newchat",i,{lightShadow:true,offsetTop:5,offsetLeft:this.desktop.run()?this.webrtc.callActive?5:0:this.webrtc.callActive?-162:-170,autoHide:true,buttons:[new BX.PopupWindowButton({text:BX.message("IM_M_CHAT_BTN_JOIN"),className:"popup-window-button-accept",events:{click:BX.delegate(function(){if(t=="CHAT_ADD"){var e=[this.currentTab];for(var s in this.popupChatDialogUsers)e.push(this.popupChatDialogUsers[s]);this.sendRequestChatDialog(t,e)}else if(t=="CHAT_EXTEND"){var e=[];for(var s in this.popupChatDialogUsers)e.push(this.popupChatDialogUsers[s]);this.sendRequestChatDialog(t,e,this.currentTab.toString().substr(4))}else if(t=="CALL_INVITE_USER"){var e=[];for(var s in this.popupChatDialogUsers)e.push(this.popupChatDialogUsers[s]);this.webrtc.callInviteUserToChat(e)}},this)}}),new BX.PopupWindowButton({text:BX.message("IM_M_CHAT_BTN_CANCEL"),events:{click:BX.delegate(function(){this.popupChatDialog.close()},this)}})],closeByEsc:true,zIndex:200,events:{onPopupClose:function(){this.destroy()},onPopupDestroy:BX.delegate(function(){this.popupChatDialogUsers={};this.popupChatDialog=null;this.popupChatDialogContactListElements=null},this)},content:BX.create("div",{props:{className:"bx-messenger-popup-newchat-wrap"},children:[BX.create("div",{props:{className:"bx-messenger-popup-newchat-caption"},html:BX.message("IM_M_CHAT_TITLE")}),BX.create("div",{props:{className:"bx-messenger-popup-newchat-box bx-messenger-popup-newchat-dest bx-messenger-popup-newchat-dest-even"},children:[this.popupChatDialogDestElements=BX.create("span",{props:{className:"bx-messenger-dest-items"}}),this.popupChatDialogContactListSearch=BX.create("input",{props:{className:"bx-messenger-input"},attrs:{type:"text",placeholder:BX.message(this.BXIM.bitrixIntranet?"IM_M_SEARCH_PLACEHOLDER_CP":"IM_M_SEARCH_PLACEHOLDER"),value:""}})]}),this.popupChatDialogContactListElements=BX.create("div",{props:{className:"bx-messenger-popup-newchat-box bx-messenger-popup-newchat-cl bx-messenger-recent-wrap"},children:[]})]})});BX.MessengerCommon.contactListPrepareSearch("popupChatDialogContactListElements",this.popupChatDialogContactListElements,"",{viewOffline:true,viewChat:false,exceptUsers:s});this.popupChatDialog.setAngle({offset:this.desktop.run()?20:188});this.popupChatDialog.show();this.popupChatDialogContactListSearch.focus();BX.addClass(this.popupChatDialog.popupContainer,"bx-messenger-mark");BX.bind(this.popupChatDialog.popupContainer,"click",BX.PreventDefault);BX.bind(this.popupChatDialogContactListSearch,"keyup",BX.delegate(function(e){if(e.keyCode==16||e.keyCode==17||e.keyCode==18||e.keyCode==20||e.keyCode==244||e.keyCode==224||e.keyCode==91)return false;if(e.keyCode==27&&this.popupChatDialogContactListSearch.value!="")BX.MessengerCommon.preventDefault(e);if(e.keyCode==27){this.popupChatDialogContactListSearch.value=""}if(e.keyCode==13){this.popupContactListSearchInput.value="";var t=BX.findChildByClassName(this.popupChatDialogContactListElements,"bx-messenger-cl-item");if(t){if(this.popupChatDialogContactListSearch.value!=""){this.popupChatDialogContactListSearch.value=""}if(this.popupChatDialogUsers[t.getAttribute("data-userId")])delete this.popupChatDialogUsers[t.getAttribute("data-userId")];else this.popupChatDialogUsers[t.getAttribute("data-userId")]=t.getAttribute("data-userId");this.redrawChatDialogDest()}}BX.MessengerCommon.contactListPrepareSearch("popupChatDialogContactListElements",this.popupChatDialogContactListElements,this.popupChatDialogContactListSearch.value,{viewOffline:true,viewChat:false,exceptUsers:s,timeout:100})},this));BX.bindDelegate(this.popupChatDialogDestElements,"click",{className:"bx-messenger-dest-del"},BX.delegate(function(){delete this.popupChatDialogUsers[BX.proxy_context.getAttribute("data-userId")];e.maxUsers=e.maxUsers+1;if(e.maxUsers>0)BX.show(this.popupChatDialogContactListSearch);this.redrawChatDialogDest()},this));BX.bindDelegate(this.popupChatDialogContactListElements,"click",{className:"bx-messenger-cl-item"},BX.delegate(function(t){if(this.popupChatDialogContactListSearch.value!=""){this.popupChatDialogContactListSearch.value="";BX.MessengerCommon.contactListPrepareSearch("popupChatDialogContactListElements",this.popupChatDialogContactListElements,this.popupChatDialogContactListSearch.value,{viewOffline:true,viewChat:false,exceptUsers:s})}if(this.popupChatDialogUsers[BX.proxy_context.getAttribute("data-userId")]){e.maxUsers=e.maxUsers+1;delete this.popupChatDialogUsers[BX.proxy_context.getAttribute("data-userId")]}else{if(e.maxUsers<=0)return false;e.maxUsers=e.maxUsers-1;this.popupChatDialogUsers[BX.proxy_context.getAttribute("data-userId")]=BX.proxy_context.getAttribute("data-userId")}if(e.maxUsers<=0)BX.hide(this.popupChatDialogContactListSearch);else BX.show(this.popupChatDialogContactListSearch);this.redrawChatDialogDest();return BX.PreventDefault(t)},this))};BX.Messenger.prototype.redrawChatDialogDest=function(){var e="";var t=0;for(var s in this.popupChatDialogUsers){t++;e+='<span class="bx-messenger-dest-block">'+'<span class="bx-messenger-dest-text">'+this.users[s].name+"</span>"+'<span class="bx-messenger-dest-del" data-userId="'+s+'"></span></span>'}this.popupChatDialogDestElements.innerHTML=e;this.popupChatDialogDestElements.parentNode.scrollTop=this.popupChatDialogDestElements.parentNode.offsetHeight;if(BX.util.even(t))BX.addClass(this.popupChatDialogDestElements.parentNode,"bx-messenger-popup-newchat-dest-even");else BX.removeClass(this.popupChatDialogDestElements.parentNode,"bx-messenger-popup-newchat-dest-even");this.popupChatDialogContactListSearch.focus()};BX.Messenger.prototype.sendRequestChatDialog=function(e,t,s){if(this.popupChatDialogSendBlock)return false;var i="";if(e=="CHAT_ADD"&&t.length<=1){i=BX.message("IM_M_CHAT_ERROR_1")}else if(e=="CHAT_EXTEND"&&t.length==0){if(this.popupChatDialog!=null)this.popupChatDialog.close();return false}if(i!=""){this.BXIM.openConfirm(i);return false}this.popupChatDialogSendBlock=true;if(this.popupChatDialog!=null)this.popupChatDialog.buttons[0].setClassName("popup-window-button-disable");var a=false;if(e=="CHAT_ADD")a={IM_CHAT_ADD:"Y",USERS:JSON.stringify(t),IM_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()};else if(e=="CHAT_EXTEND")a={IM_CHAT_EXTEND:"Y",CHAT_ID:s,USERS:JSON.stringify(t),IM_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()};if(!a)return false;BX.ajax({url:this.BXIM.pathToAjax+"?"+e+"&V="+this.BXIM.revision,method:"POST",dataType:"json",timeout:60,data:a,onsuccess:BX.delegate(function(e){this.popupChatDialogSendBlock=false;if(this.popupChatDialog!=null)this.popupChatDialog.buttons[0].setClassName("popup-window-button-accept");if(e.ERROR==""){if(!this.BXIM.ppServerStatus)BX.PULL.updateState(true);if(e.CHAT_ID){if(this.BXIM.ppServerStatus&&this.currentTab!="chat"+e.CHAT_ID){this.openMessenger("chat"+e.CHAT_ID)}else if(!this.BXIM.ppServerStatus&&this.currentTab!="chat"+e.CHAT_ID){setTimeout(BX.delegate(function(){this.openMessenger("chat"+e.CHAT_ID)},this),500)}}this.popupChatDialogSendBlock=false;if(this.popupChatDialog!=null)this.popupChatDialog.close()}else{this.BXIM.openConfirm(e.ERROR)}},this)})};BX.Messenger.prototype.getRecipientByChatId=function(e){if(this.chat[e]){recipientId="chat"+e}else{for(var t in this.userChat){if(this.userChat[t]==e){recipientId=t;break}}}return recipientId};BX.Messenger.prototype.openContactList=function(){return this.openMessenger()};BX.Messenger.prototype.openPopupMenu=function(e,t,s,i){if(this.popupSmileMenu!=null)this.popupSmileMenu.destroy();this.closePopupFileMenu();if(this.popupPopupMenu!=null){this.popupPopupMenu.destroy();return false}var a=0;var n=10;var o=[];var r={};var l={offset:4};this.popupPopupMenuStyle="";if(t=="status"){r={position:"top"};o=[{icon:"bx-messenger-status-online",text:BX.message("IM_STATUS_ONLINE"),onclick:BX.delegate(function(){this.setStatus("online");this.closeMenuPopup()},this)},{icon:"bx-messenger-status-away",text:BX.message("IM_STATUS_AWAY"),onclick:BX.delegate(function(){this.setStatus("away");this.closeMenuPopup()},this)},{icon:"bx-messenger-status-dnd",text:BX.message("IM_STATUS_DND"),onclick:BX.delegate(function(){this.setStatus("dnd");this.closeMenuPopup()},this)}]}else if(t=="notifyDelete"){var p=e.getAttribute("data-notifyId");var h=this.notify.notify[p].settingName;var c=typeof this.BXIM.settingsNotifyBlocked[h]=="undefined"?BX.message("IM_NOTIFY_DELETE_2"):BX.message("IM_NOTIFY_DELETE_3");o=[{text:BX.message("IM_NOTIFY_DELETE_1"),onclick:BX.delegate(function(){this.notify.deleteNotify(p);this.closeMenuPopup()},this)},{text:c,onclick:BX.delegate(function(){this.notify.blockNotifyType(h);this.closeMenuPopup()},this)}]}else if(t=="callMenu"){a=2;n=20;o=[{icon:"bx-messenger-menu-call-video",text:BX.message("IM_M_CALL_VIDEO"),onclick:BX.delegate(function(){this.BXIM.callTo(this.currentTab,true);this.closeMenuPopup()},this)},{icon:"bx-messenger-menu-call-voice",text:BX.message("IM_M_CALL_VOICE"),onclick:BX.delegate(function(){this.BXIM.callTo(this.currentTab,false);this.closeMenuPopup()},this)}];if(!this.openChatFlag&&this.phones[this.currentTab]){o.push({separator:true});if(this.phones[this.currentTab].PERSONAL_MOBILE){o.push({type:"call",text:BX.message("IM_PHONE_PERSONAL_MOBILE"),phone:BX.util.htmlspecialchars(this.phones[this.currentTab].PERSONAL_MOBILE),onclick:BX.delegate(function(){this.BXIM.phoneTo(this.phones[this.currentTab].PERSONAL_MOBILE);this.closeMenuPopup()},this)})}if(this.phones[this.currentTab].PERSONAL_PHONE){o.push({type:"call",text:BX.message("IM_PHONE_PERSONAL_PHONE"),phone:BX.util.htmlspecialchars(this.phones[this.currentTab].PERSONAL_PHONE),onclick:BX.delegate(function(){this.BXIM.phoneTo(this.phones[this.currentTab].PERSONAL_PHONE);this.closeMenuPopup()},this)})}if(this.phones[this.currentTab].WORK_PHONE){o.push({type:"call",text:BX.message("IM_PHONE_WORK_PHONE"),phone:BX.util.htmlspecialchars(this.phones[this.currentTab].WORK_PHONE),onclick:BX.delegate(function(){this.BXIM.phoneTo(this.phones[this.currentTab].WORK_PHONE);this.closeMenuPopup()},this)})}}}else if(t=="callPhoneMenu"){a=2;n=25;o=[{icon:"bx-messenger-menu-call-"+(i.video?"video":"voice"),text:"<b>"+BX.message("IM_M_CALL_BTN_RECALL_3")+"</b>",onclick:BX.delegate(function(){this.webrtc.callInvite(i.userId,i.video)},this)}];o.push({separator:true});if(this.phones[this.currentTab]){o.push({separator:true});if(this.phones[i.userId].PERSONAL_MOBILE){o.push({type:"call",text:BX.message("IM_PHONE_PERSONAL_MOBILE"),phone:BX.util.htmlspecialchars(this.phones[i.userId].PERSONAL_MOBILE),onclick:BX.delegate(function(){this.BXIM.phoneTo(this.phones[i.userId].PERSONAL_MOBILE);this.closeMenuPopup()},this)})}if(this.phones[i.userId].PERSONAL_PHONE){o.push({type:"call",text:BX.message("IM_PHONE_PERSONAL_PHONE"),phone:BX.util.htmlspecialchars(this.phones[i.userId].PERSONAL_PHONE),onclick:BX.delegate(function(){this.BXIM.phoneTo(this.phones[i.userId].PERSONAL_PHONE);this.closeMenuPopup()},this)})}if(this.phones[i.userId].WORK_PHONE){o.push({type:"call",text:BX.message("IM_PHONE_WORK_PHONE"),phone:BX.util.htmlspecialchars(this.phones[i.userId].WORK_PHONE),onclick:BX.delegate(function(){this.BXIM.phoneTo(this.phones[i.userId].WORK_PHONE);this.closeMenuPopup()},this)})}}}else if(t=="chatUser"){var u=e.getAttribute("data-userId");var d=this.currentTab.toString().substr(4);if(u==this.BXIM.userId){var m=BX.message("IM_M_CHAT_MUTE_OFF");if(this.userChatBlockStatus[this.currentTab.toString().substr(4)]&&this.userChatBlockStatus[d][this.BXIM.userId]=="Y"){m=BX.message("IM_M_CHAT_MUTE_ON")}o=[{icon:"bx-messenger-menu-chat-mute",text:m,onclick:BX.delegate(function(){this.muteMessageChat(d);this.closeMenuPopup()},this)},{icon:"bx-messenger-menu-chat-exit",text:BX.message("IM_M_CHAT_EXIT"),onclick:BX.delegate(function(){BX.MessengerCommon.leaveFromChat(d);this.closeMenuPopup()},this)}]}else{o=[{icon:"bx-messenger-menu-chat-put",text:BX.message("IM_M_CHAT_PUT"),onclick:BX.delegate(function(){
this.insertTextareaText(this.popupMessengerTextarea," "+BX.util.htmlspecialcharsback(this.users[u].name)+", ",false);this.popupMessengerTextarea.focus();this.closeMenuPopup()},this)},{icon:"bx-messenger-menu-write",text:BX.message("IM_M_WRITE_MESSAGE"),onclick:BX.delegate(function(){this.openMessenger(u);this.closeMenuPopup()},this)},!this.webrtc.callSupport(u,this)||this.webrtc.callInit?null:{icon:"bx-messenger-menu-video",text:BX.message("IM_M_CALL_VIDEO"),onclick:BX.delegate(function(){this.BXIM.callTo(u,true);this.closeMenuPopup()},this)},{icon:"bx-messenger-menu-history",text:BX.message("IM_M_OPEN_HISTORY"),onclick:BX.delegate(function(){this.openHistory(u);this.closeMenuPopup()},this)},{icon:"bx-messenger-menu-profile",text:BX.message("IM_M_OPEN_PROFILE"),href:this.users[u].profile,onclick:BX.delegate(function(){this.closeMenuPopup()},this)},this.chat[d].owner==this.BXIM.userId?{icon:"bx-messenger-menu-chat-exit",text:BX.message("IM_M_CHAT_KICK"),onclick:BX.delegate(function(){this.kickFromChat(d,u);this.closeMenuPopup()},this)}:{}]}}else if(t=="contactList"){a=2;n=25;var u=e.getAttribute("data-userId");var g=e.getAttribute("data-userIsChat");if(this.recentList||g){var m=BX.message("IM_M_CHAT_MUTE_OFF");if(g&&this.userChatBlockStatus[u.toString().substr(4)]&&this.userChatBlockStatus[u.toString().substr(4)][this.BXIM.userId]=="Y"){m=BX.message("IM_M_CHAT_MUTE_ON")}o=[{icon:"bx-messenger-menu-write",text:BX.message("IM_M_WRITE_MESSAGE"),onclick:BX.delegate(function(){this.openMessenger(u);this.closeMenuPopup()},this)},g&&(!this.webrtc.callSupport(u,this)||this.webrtc.callInit||this.chat[u.toString().substr(4)].style=="call")?null:{icon:"bx-messenger-menu-video",text:BX.message("IM_M_CALL_VIDEO"),onclick:BX.delegate(function(){this.BXIM.callTo(u,true);this.closeMenuPopup()},this)},{icon:"bx-messenger-menu-history",text:BX.message("IM_M_OPEN_HISTORY"),onclick:BX.delegate(function(){this.openHistory(u);this.closeMenuPopup()},this)},!g?{icon:"bx-messenger-menu-profile",text:BX.message("IM_M_OPEN_PROFILE"),href:this.users[u].profile,onclick:BX.delegate(function(){this.closeMenuPopup()},this)}:{},g&&this.chat[u.toString().substr(4)].style=="group"?{icon:"bx-messenger-menu-chat-mute",text:m,onclick:BX.delegate(function(){this.muteMessageChat(u.toString().substr(4));this.closeMenuPopup()},this)}:{},g&&this.chat[u.toString().substr(4)].style=="group"?{icon:"bx-messenger-menu-chat-rename",text:BX.message("IM_M_CHAT_RENAME"),onclick:BX.delegate(function(){if(this.currentTab!=u){this.openMessenger(u)}else{this.renameChatDialog()}this.closeMenuPopup()},this)}:{},g&&this.chat[u.toString().substr(4)].style=="group"?{icon:"bx-messenger-menu-chat-exit",text:BX.message("IM_M_CHAT_EXIT"),onclick:BX.delegate(function(){BX.MessengerCommon.leaveFromChat(u.toString().substr(4));this.closeMenuPopup()},this)}:{},g&&this.chat[u.toString().substr(4)].style=="group"?{}:{icon:"bx-messenger-menu-hide-"+(g?"chat":"dialog"),text:BX.message("IM_M_HIDE_"+(g?this.chat[u.toString().substr(4)].style=="group"?"CHAT":"CALL":"DIALOG")),onclick:BX.delegate(function(){BX.MessengerCommon.recentListHide(u);this.closeMenuPopup()},this)}]}else{o=[{icon:"bx-messenger-menu-write",text:BX.message("IM_M_WRITE_MESSAGE"),onclick:BX.delegate(function(){this.openMessenger(u);this.closeMenuPopup()},this)},!g&&(!this.webrtc.callSupport(u,this)||this.webrtc.callInit)?null:{icon:"bx-messenger-menu-video",text:BX.message("IM_M_CALL_VIDEO"),onclick:BX.delegate(function(){this.BXIM.callTo(u,true);this.closeMenuPopup()},this)},{icon:"bx-messenger-menu-history",text:BX.message("IM_M_OPEN_HISTORY"),onclick:BX.delegate(function(){this.openHistory(u);this.closeMenuPopup()},this)},{icon:"bx-messenger-menu-profile",text:BX.message("IM_M_OPEN_PROFILE"),href:this.users[u].profile,onclick:BX.delegate(function(){this.closeMenuPopup()},this)}]}}else if(t=="dialogContext"||t=="dialogMenu"){var f=[];if(t=="dialogMenu"){this.popupPopupMenuStyle="bx-messenger-content-item-menu-hover";l={offset:13};if(e.nextSibling){f=[e.nextSibling]}}else{var B=false;if(e.target.className.indexOf("bx-messenger-file")>=0){var X=BX.findParent(e.target,{className:"bx-messenger-file-box"});if(X&&X.previousSibling){B=true;f=[X.previousSibling]}}if(!B){if(BX.hasClass(e.target,"bx-messenger-message")){f=[e.target]}else if(e.target.className.indexOf("bx-messenger-content-quote")>=0){f=BX.findParent(e.target,{className:"bx-messenger-message"});f=[f]}else{f=BX.findChildrenByClassName(e.target,"bx-messenger-message")}if(f.length<=0){f=BX.findParent(e.target,{className:"bx-messenger-message"});f=[f]}}}if(f.length<=0||!f[f.length-1])return false;var y=BX.message("IM_M_SYSTEM_USER");var I=f[f.length-1].id.replace("im-message-","");if(this.message[I].senderId&&this.users[this.message[I].senderId])y=this.users[this.message[I].senderId].name;if(I.substr(0,4)=="temp")return false;var M=this.message[I].date;var b=t=="dialogContext"?BX.desktop.clipboardSelected():"";var v=false;var C="";if(this.openChatFlag&&this.message[I].senderId!=this.BXIM.userId&&this.users[this.message[I].senderId]){C=this.users[this.message[I].senderId].name}var x="";if(t=="dialogContext"&&(e.target.tagName=="IMG"&&e.target.parentNode.tagName=="A"||e.target.tagName=="A")){if(e.target.tagName=="A")x=e.target.href;else x=e.target.parentNode.href;if(x.indexOf("/desktop_app/")<0||x.indexOf("/desktop_app/show.file.php")>=0)v=true}var T=false;if(t=="dialogContext"&&BX.desktop){T=true}var S=false;var _=false;if(BX.MessengerCommon.checkEditMessage(I)){S=true;_=this.message[I].text==""?false:true}o=[C.length<=0?null:{text:BX.message("IM_MENU_ANSWER"),onclick:BX.delegate(function(e){this.insertTextareaText(this.popupMessengerTextarea," "+BX.util.htmlspecialcharsback(C)+", ",false);setTimeout(BX.delegate(function(){this.popupMessengerTextarea.focus()},this),200);this.closeMenuPopup()},this)},C.length<=0?null:{separator:true},v?{text:BX.message("IM_MENU_COPY3"),onclick:BX.delegate(function(){BX.desktop.clipboardCopy(BX.delegate(function(){return x},this));this.closeMenuPopup()},this)}:null,v?{separator:true}:null,b.length<=0?null:{text:BX.message("IM_MENU_QUOTE"),onclick:BX.delegate(function(){var e=BX.IM.getSelectionText();this.insertQuoteText(y,M,e);this.closeMenuPopup()},this)},{text:BX.message("IM_MENU_QUOTE2"),onclick:BX.delegate(function(){var e=[];for(var t=0;t<f.length;t++){var s=f[t].id.replace("im-message-","");if(this.message[s]){if(this.message[s].text){e.push(BX.MessengerCommon.prepareTextBack(this.message[s].text))}if(this.message[s].params&&this.message[s].params.FILE_ID){for(var i=0;i<this.message[s].params.FILE_ID.length;i++){var a=this.message[s].params.FILE_ID[i];var n=this.message[s].chatId;if(this.disk.files[n][a]){e.push("["+BX.message("IM_F_FILE")+": "+this.disk.files[n][a].name+"]")}}}}}if(e.length>0){this.insertQuoteText(y,M,e.join("\n"))}this.closeMenuPopup()},this)},T?{separator:true}:null,!T||b.length<=0?null:{text:BX.message("IM_MENU_COPY"),onclick:BX.delegate(function(){BX.desktop.clipboardCopy();this.closeMenuPopup()},this)},!T?null:{text:BX.message("IM_MENU_COPY2"),onclick:BX.delegate(function(){var e=[];for(var t=0;t<f.length;t++){var s=f[t].id.replace("im-message-","");if(this.message[s]){if(this.message[s].text){e.push(BX.MessengerCommon.prepareTextBack(this.message[s].text))}if(this.message[s].params&&this.message[s].params.FILE_ID){for(var i=0;i<this.message[s].params.FILE_ID.length;i++){var a=this.message[s].params.FILE_ID[i];var n=this.message[s].chatId;if(this.disk.files[n][a]){e.push("["+BX.message("IM_F_FILE")+": "+this.disk.files[n][a].name+"]")}}}}}if(e.length>0){BX.desktop.clipboardCopy(BX.delegate(function(t){return this.insertQuoteText(y,M,e.join("\n"),false)},this))}this.closeMenuPopup()},this)},S?{separator:true}:null,!S?null:{text:BX.message("IM_MENU_EDIT"),onclick:BX.delegate(function(){this.editMessage(I);this.closeMenuPopup()},this)},!_?null:{text:BX.message("IM_M_HISTORY_DELETE"),onclick:BX.delegate(function(){this.deleteMessage(I);this.closeMenuPopup()},this)}]}else if(t=="history"){var f=[];if(e.target.className=="bx-messenger-history-item"){f=[e.target]}else if(e.target.className.indexOf("bx-messenger-content-quote")>=0){f=BX.findParent(e.target,{className:"bx-messenger-history-item"});f=[f]}else{f=BX.findChildrenByClassName(e.target,"bx-messenger-history-item")}if(f.length<=0){f=BX.findParent(e.target,{className:"bx-messenger-history-item"});f=[f]}if(f.length<=0||!f[f.length-1])return false;var y=BX.message("IM_M_SYSTEM_USER");var I=f[f.length-1].getAttribute("data-messageId");if(this.message[I].senderId&&this.users[this.message[I].senderId])y=this.users[this.message[I].senderId].name;var M=this.message[I].date;var b=BX.desktop.clipboardSelected();var v=false;var x="";if(e.target.tagName=="IMG"&&e.target.parentNode.tagName=="A"||e.target.tagName=="A"){if(e.target.tagName=="A")x=e.target.href;else x=e.target.parentNode.href;if(x.indexOf("/desktop_app/")<0||x.indexOf("/desktop_app/show.file.php")>=0)v=true}o=[v?{text:BX.message("IM_MENU_COPY3"),onclick:BX.delegate(function(){BX.desktop.clipboardCopy(BX.delegate(function(){return x},this));this.closeMenuPopup()},this)}:null,v?{separator:true}:null,b.length<=0?null:{text:BX.message("IM_MENU_QUOTE"),onclick:BX.delegate(function(){var e=BX.IM.getSelectionText();this.insertQuoteText(y,M,e);this.closeMenuPopup()},this)},{text:BX.message("IM_MENU_QUOTE2"),onclick:BX.delegate(function(){var e=[];for(var t=0;t<f.length;t++){var s=f[t].getAttribute("data-messageId");if(this.message[s]){if(this.message[s].text){e.push(BX.MessengerCommon.prepareTextBack(this.message[s].text))}if(this.message[s].params&&this.message[s].params.FILE_ID){for(var i=0;i<this.message[s].params.FILE_ID.length;i++){var a=this.message[s].params.FILE_ID[t];var n=this.message[s].chatId;if(this.disk.files[n][a]){e.push("["+BX.message("IM_F_FILE")+": "+this.disk.files[n][a].name+"]")}}}}}if(e.length>0){this.insertQuoteText(y,M,e.join("\n"))}this.closeMenuPopup()},this)},{separator:true},b.length<=0?null:{text:BX.message("IM_MENU_COPY"),onclick:BX.delegate(function(){this.closeMenuPopup()},this)},{text:BX.message("IM_MENU_COPY2"),onclick:BX.delegate(function(){var e=[];for(var t=0;t<f.length;t++){var s=f[t].getAttribute("data-messageId");if(this.message[s]){if(this.message[s].text){e.push(BX.MessengerCommon.prepareTextBack(this.message[s].text))}if(this.message[s].params&&this.message[s].params.FILE_ID){for(var i=0;i<this.message[s].params.FILE_ID.length;i++){var a=this.message[s].params.FILE_ID[i];var n=this.message[s].chatId;if(this.disk.files[n][a]){e.push("["+BX.message("IM_F_FILE")+": "+this.disk.files[n][a].name+"]")}}}}}if(e.length>0){BX.desktop.clipboardCopy(BX.delegate(function(t){return this.insertQuoteText(y,M,e.join("\n"),false)},this))}this.closeMenuPopup()},this)}]}else if(t=="historyFileMenu"){a=4;n=8;this.popupPopupMenuStyle="bx-messenger-file-active";var N=i.fileId;var d=i.chatId;var E=this.desktop.ready()?"desktop":"default";var A=true;if(!this.disk.files[d][N])return false;o=[A?{text:BX.message("IM_F_DOWNLOAD"),href:this.disk.files[d][N].urlDownload[E],onclick:BX.delegate(function(){this.closeMenuPopup()},this)}:null,{text:BX.message("IM_F_DOWNLOAD_DISK"),onclick:BX.delegate(function(){this.disk.saveToDisk(d,N,{boxId:"im-file-history-panel"});this.closeMenuPopup()},this)}]}else if(t=="notify"){if(e.target.className=="bx-notifier-item-delete"){e.target.setAttribute("id","bx-notifier-item-delete-"+e.target.getAttribute("data-notifyId"));this.openPopupMenu(e.target,"notifyDelete");return false}var b=BX.desktop.clipboardSelected();var v=false;if(e.target.tagName=="A"&&(e.target.href.indexOf("/desktop_app/")<0||x.indexOf("/desktop_app/show.file.php")>=0)){v=true;var x=e.target.href}if(!v&&b.length<=0)return false;o=[v?{text:BX.message("IM_MENU_COPY3"),onclick:BX.delegate(function(){BX.desktop.clipboardCopy(BX.delegate(function(){return x},this));this.closeMenuPopup()},this)}:null,v?{separator:true}:null,b.length<=0?null:{text:BX.message("IM_MENU_COPY"),onclick:BX.delegate(function(){BX.desktop.clipboardCopy();this.closeMenuPopup()},this)}]}else if(t=="copylink"){if(e.target.tagName!="A"||e.target.href.indexOf("/desktop_app/")>=0&&e.target.href.indexOf("/desktop_app/show.file.php")<0)return false;o=[{text:BX.message("IM_MENU_COPY3"),onclick:BX.delegate(function(){BX.desktop.clipboardCopy(BX.delegate(function(t){return e.target.href},this));this.closeMenuPopup()},this)}]}else if(t=="copypaste"){r={position:"top"};var b=BX.desktop.clipboardSelected(e.target);o=[b.length<=0?null:{text:BX.message("IM_MENU_CUT"),onclick:BX.delegate(function(){BX.desktop.clipboardCut();this.closeMenuPopup()},this)},b.length<=0?null:{text:BX.message("IM_MENU_COPY"),onclick:BX.delegate(function(){BX.desktop.clipboardCopy();this.closeMenuPopup()},this)},{text:BX.message("IM_MENU_PASTE"),onclick:BX.delegate(function(){BX.desktop.clipboardPaste();this.closeMenuPopup()},this)},b.length<=0?null:{text:BX.message("IM_MENU_DELETE"),onclick:BX.delegate(function(){BX.desktop.clipboardDelete();this.closeMenuPopup()},this)}]}else{o=[]}this.popupPopupMenuDateCreate=+new Date;this.popupPopupMenu=new BX.PopupWindow("bx-messenger-popup-menu",e,{lightShadow:true,offsetTop:a,offsetLeft:n,autoHide:true,closeByEsc:true,zIndex:200,bindOptions:r,events:{onPopupClose:BX.delegate(function(){if(this.popupPopupMenuStyle){if(this.popupPopupMenuStyle=="bx-messenger-file-active")BX.removeClass(this.popupPopupMenu.bindElement.parentNode,this.popupPopupMenuStyle);else BX.removeClass(this.popupPopupMenu.bindElement,this.popupPopupMenuStyle)}if(this.popupPopupMenuDateCreate+1e3<+new Date)BX.proxy_context.destroy()},this),onPopupDestroy:BX.delegate(function(){if(this.popupPopupMenuStyle){if(this.popupPopupMenuStyle=="bx-messenger-file-active")BX.removeClass(this.popupPopupMenu.bindElement.parentNode,this.popupPopupMenuStyle);else BX.removeClass(this.popupPopupMenu.bindElement,this.popupPopupMenuStyle)}this.popupPopupMenu=null},this)},content:BX.create("div",{props:{className:"bx-messenger-popup-menu"},children:[BX.create("div",{props:{className:"bx-messenger-popup-menu-items"},children:BX.Messenger.MenuPrepareList(o)})]})});if(s!==false)this.popupPopupMenu.setAngle(l);this.popupPopupMenu.show();if(this.popupPopupMenuStyle){if(this.popupPopupMenuStyle=="bx-messenger-file-active")BX.addClass(e.parentNode,this.popupPopupMenuStyle);else BX.addClass(e,this.popupPopupMenuStyle)}BX.bind(this.popupPopupMenu.popupContainer,"click",BX.MessengerCommon.preventDefault);if(t=="dialogContext"||t=="notify"||t=="history"||t=="copypaste"){BX.bind(this.popupPopupMenu.popupContainer,"mousedown",function(e){e.target.click()})}return false};BX.Messenger.prototype.closePopupFileMenu=function(){if(this.popupMessengerFileButton==null)return false;if(this.popupPopupMenuDateCreate+100>+new Date)return false;if(BX.hasClass(this.popupMessengerFileButton,"bx-messenger-textarea-file-active")){BX.removeClass(this.popupMessengerFileButton,"bx-messenger-textarea-file-active");this.setClosingByEsc(true)}};BX.Messenger.prototype.closePopupFileMenuKeydown=function(e){if(e.keyCode==27){setTimeout(BX.delegate(function(){this.closePopupFileMenu()},this),100)}};BX.Messenger.prototype.openPopupExternalData=function(e,t,s,i){if(this.popupSmileMenu!=null)this.popupSmileMenu.destroy();if(this.popupPopupMenu!=null){this.popupPopupMenu.destroy();return false}this.popupPopupMenuDateCreate=+new Date;var a=this.desktop.ready()?0:0;var n=10;var o={position:"top"};var r={width:"272px",height:"100px"};var l={IM_GET_EXTERNAL_DATA:"Y",TYPE:t,TS:this.popupPopupMenuDateCreate,IM_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()};if(t=="user"){r={width:"272px",height:"100px"};l["USER_ID"]=parseInt(i["ID"]);if(this.users[l["USER_ID"]]&&!this.users[l["USER_ID"]].fake){l=false}}else if(t=="phoneCallHistory"){r={width:"239px",height:"122px"};l["HISTORY_ID"]=parseInt(i["ID"])}else{return false}this.popupPopupMenu=new BX.PopupWindow("bx-messenger-popup-menu",e,{lightShadow:true,offsetTop:a,offsetLeft:n,autoHide:true,closeByEsc:true,zIndex:200,bindOptions:o,events:{onPopupClose:function(){this.destroy()},onPopupDestroy:BX.delegate(function(){this.popupPopupMenu=null},this)},content:BX.create("div",{attrs:{id:"bx-messenger-external-data"},props:{className:"bx-messenger-external-data"},style:r,children:[BX.create("div",{props:{className:"bx-messenger-external-data-load"},html:BX.message("IM_CL_LOAD")})]})});if(s!==false)this.popupPopupMenu.setAngle({offset:4});this.popupPopupMenu.show();if(l){BX.ajax({url:this.BXIM.pathToAjax+"?GET_EXTERNAL_DATA&V="+this.BXIM.revision,method:"POST",dataType:"json",timeout:30,data:l,onsuccess:BX.delegate(function(e){if(e.ERROR){e.TYPE="noAccess"}else if(e.TYPE=="user"){for(var t in e.USERS){this.users[t]=e.USERS[t]}for(var t in e.PHONES){this.phones[t]={};for(var s in e.PHONES[t]){this.phones[t][s]=BX.util.htmlspecialcharsback(e.PHONES[t][s])}}for(var t in e.USER_IN_GROUP){if(typeof this.userInGroup[t]=="undefined"){this.userInGroup[t]=e.USER_IN_GROUP[t]}else{for(var s=0;s<e.USER_IN_GROUP[t].users.length;s++)this.userInGroup[t].users.push(e.USER_IN_GROUP[t].users[s]);this.userInGroup[t].users=BX.util.array_unique(this.userInGroup[t].users)}}for(var t in e.WO_USER_IN_GROUP){if(typeof this.woUserInGroup[t]=="undefined"){this.woUserInGroup[t]=e.WO_USER_IN_GROUP[t]}else{for(var s=0;s<e.WO_USER_IN_GROUP[t].users.length;s++)this.woUserInGroup[t].users.push(e.WO_USER_IN_GROUP[t].users[s]);this.woUserInGroup[t].users=BX.util.array_unique(this.woUserInGroup[t].users)}}}e.TS=parseInt(e.TS);if(e.TS>0&&e.TS!=this.popupPopupMenuDateCreate||!this.popupPopupMenu)return false;this.drawExternalData(e.TYPE,e)},this),onfailure:BX.delegate(function(){if(this.popupPopupMenu)this.popupPopupMenu.destroy()},this)})}else{if(t=="user")this.drawExternalData("user",{USER_ID:i["ID"]})}BX.bind(this.popupPopupMenu.popupContainer,"click",BX.PreventDefault);return false};BX.Messenger.prototype.drawExternalData=function(e,t){if(!BX("bx-messenger-external-data"))return false;if(e=="noAccess"){BX("bx-messenger-external-data").innerHTML=BX.message("IM_M_USER_NO_ACCESS")}else if(e=="user"){if(!this.users[t["USER_ID"]]){if(this.popupPopupMenu)this.popupPopupMenu.destroy();return false}BX("bx-messenger-external-data").innerHTML="";BX.adjust(BX("bx-messenger-external-data"),{children:[BX.create("div",{props:{className:"bx-messenger-external-avatar"},children:[BX.create("div",{props:{className:"bx-messenger-panel-avatar bx-messenger-panel-avatar-status-"+BX.MessengerCommon.getUserStatus(t["USER_ID"])},children:[BX.create("img",{attrs:{src:this.users[t["USER_ID"]].avatar},props:{className:"bx-messenger-panel-avatar-img"+(BX.MessengerCommon.isBlankAvatar(this.users[t["USER_ID"]].avatar)?" bx-messenger-panel-avatar-img-default":"")}}),BX.create("span",{attrs:{title:BX.MessengerCommon.getUserStatus(this.currentTab,true)},props:{className:"bx-messenger-panel-avatar-status"}})]}),BX.create("span",{props:{className:"bx-messenger-panel-title"},html:this.users[t["USER_ID"]].extranet?'<div class="bx-messenger-user-extranet">'+this.users[t["USER_ID"]].name+"</div>":this.users[t["USER_ID"]].name}),BX.create("span",{props:{className:"bx-messenger-panel-desc"},html:BX.MessengerCommon.getUserPosition(t["USER_ID"])})]}),t["USER_ID"]!=this.BXIM.userId?BX.create("div",{props:{className:"bx-messenger-external-data-buttons"},children:[BX.create("span",{props:{className:"bx-notifier-item-button bx-notifier-item-button-white"},html:BX.message("IM_M_WRITE_MESSAGE"),events:{click:BX.delegate(function(e){this.openMessenger(t["USER_ID"])},this)}}),BX.create("span",{props:{className:"bx-notifier-item-button bx-notifier-item-button-white"},html:BX.message("IM_M_CALL_BTN_HISTORY"),events:{click:BX.delegate(function(){this.openHistory(t["USER_ID"])},this)}})]}):null]})}else if(e=="phoneCallHistory"){var s=false;if(t["CALL_RECORD_HTML"]){var s={HTML:BX.message("CALL_RECORD_ERROR"),SCRIPT:[]};if(!this.desktop.ready())s=BX.processHTML(t["CALL_RECORD_HTML"],false)}BX("bx-messenger-external-data").innerHTML="";BX.adjust(BX("bx-messenger-external-data"),{children:[BX.create("div",{props:{className:"bx-messenger-record"},children:[BX.create("div",{props:{className:"bx-messenger-record-phone-box"},children:[BX.create("span",{props:{className:"bx-messenger-record-icon bx-messenger-record-icon-"+t["CALL_ICON"]},attrs:{title:t["INCOMING_TEXT"]}}),BX.create("span",{props:{className:"bx-messenger-record-phone"},html:(t["PHONE_NUMBER"]&&t["PHONE_NUMBER"].toString().length>=10?"+":"")+t["PHONE_NUMBER"]})]}),BX.create("div",{props:{className:"bx-messenger-record-reason"},html:t["CALL_FAILED_REASON"]}),BX.create("div",{props:{className:"bx-messenger-record-stats"},children:[BX.create("span",{props:{className:"bx-messenger-record-time"},html:t["CALL_DURATION_TEXT"]}),BX.create("span",{props:{className:"bx-messenger-record-cost"},html:t["COST_TEXT"]})]}),s?BX.create("div",{props:{className:"bx-messenger-record-box"},children:[BX.create("span",{props:{className:"bx-messenger-record-player"},html:s.HTML})]}):null]})]});if(s){for(var i=0;i<s.SCRIPT.length;i++){BX.evalGlobal(s.SCRIPT[i].JS)}}}};BX.Messenger.prototype.openHistory=function(e){if(this.popupMessengerConnectionStatusState!="online")return false;if(e==this.BXIM.userId)return false;if(this.historyWindowBlock)return false;this.historyLastSearch[e]="";if(!this.historyEndOfList[e])this.historyEndOfList[e]={};if(!this.historyLoadFlag[e])this.historyLoadFlag[e]={};if(this.popupHistory!=null)this.popupHistory.destroy();var t=0;var s=false;if(e.toString().substr(0,4)=="chat"){s=true;t=parseInt(e.toString().substr(4));if(t<=0)return false}else{e=parseInt(e);if(e<=0)return false;t=this.userChat[e]?this.userChat[e]:0}this.historyFilesEndOfList[t]=false;this.historyFilesLoadFlag[t]=false;this.historyUserId=e;this.historyChatId=t;if(!this.desktop.run())this.setClosingByEsc(false);this.popupHistoryPanel=null;var i=this.redrawHistoryPanel(e,t);this.popupHistoryElements=BX.create("div",{props:{className:"bx-messenger-history"+(this.BXIM.disk.enable?" bx-messenger-history-with-disk":"")},children:[this.popupHistoryPanel=BX.create("div",{props:{className:"bx-messenger-panel-wrap"},children:i}),BX.create("div",{props:{className:"bx-messenger-history-types"},children:[BX.create("span",{props:{className:"bx-messenger-history-type bx-messenger-history-type-message"},children:[this.popupHistoryButtonFilterBox=BX.create("div",{props:{className:"bx-messenger-panel-filter-box"},style:{display:this.popupHistoryFilterVisible?"block":"none"},children:[BX.create("div",{props:{className:"bx-messenger-filter-name"},html:BX.message("IM_HISTORY_FILTER_NAME")}),this.popupHistorySearchDateWrap=BX.create("div",{props:{className:"bx-messenger-filter-date bx-messenger-input-wrap"},html:'<span class="bx-messenger-input-date"></span><a class="bx-messenger-input-close" href="#close"></a><input type="text" class="bx-messenger-input" value="" tabindex="1003" placeholder="'+BX.message("IM_PANEL_FILTER_DATE")+'" />'}),this.popupHistorySearchWrap=BX.create("div",{props:{className:"bx-messenger-filter-text bx-messenger-history-filter-text bx-messenger-input-wrap"},html:'<a class="bx-messenger-input-close" href="#close"></a><input type="text" class="bx-messenger-input" tabindex="1000" placeholder="'+BX.message("IM_PANEL_FILTER_TEXT")+'" value="" />'})]}),this.popupHistoryItems=BX.create("div",{props:{className:"bx-messenger-history-items"},style:{height:this.popupHistoryItemsSize+"px"},children:[this.popupHistoryBodyWrap=BX.create("div",{props:{className:"bx-messenger-history-items-wrap"}})]})]}),BX.create("span",{props:{className:"bx-messenger-history-type bx-messenger-history-type-disk"},children:[this.popupHistoryFilesButtonFilterBox=BX.create("div",{props:{className:"bx-messenger-panel-filter-box"},style:{display:this.popupHistoryFilterVisible?"block":"none"},children:[this.popupHistoryFilesSearchWrap=BX.create("div",{props:{className:"bx-messenger-filter-text bx-messenger-input-wrap"},html:'<a class="bx-messenger-input-close" href="#close"></a><input type="text"  tabindex="1002" class="bx-messenger-input" placeholder="'+BX.message("IM_F_FILE_SEARCH")+'" value="" />'})]}),this.popupHistoryFilesItems=BX.create("div",{props:{className:"bx-messenger-history-items"},style:{height:this.popupHistoryItemsSize+"px"},children:[this.popupHistoryFilesBodyWrap=BX.create("div",{props:{className:"bx-messenger-history-items-wrap"}})]})]})]})]});if(this.BXIM.init&&this.desktop.ready()){this.desktop.openHistory(e,this.popupHistoryElements,"BXIM.openHistory('"+e+"');");return false}else if(this.desktop.ready()){this.popupHistory=new BX.PopupWindowDesktop;this.desktop.drawOnPlaceholder(this.popupHistoryElements)}else{this.popupHistory=new BX.PopupWindow("bx-messenger-popup-history",null,{lightShadow:true,offsetTop:0,autoHide:false,zIndex:100,draggable:{restrict:true},closeByEsc:true,bindOptions:{position:"top"},events:{onPopupClose:function(){this.destroy()},onPopupDestroy:BX.delegate(function(){this.popupHistory=null;this.historySearch="";this.setClosingByEsc(true);this.closeMenuPopup();var e=BX.calendar.get();if(e){e.Close()}},this)},titleBar:{content:BX.create("span",{props:{className:"bx-messenger-title"},html:BX.message("IM_M_HISTORY")})},closeIcon:{top:"10px",right:"13px"},content:this.popupHistoryElements});this.popupHistory.show();BX.bind(this.popupHistory.popupContainer,"click",BX.MessengerCommon.preventDefault)}this.drawHistory(this.historyUserId);this.drawHistoryFiles(this.historyChatId);if(this.desktop.ready()){BX.bind(this.popupHistorySearchInput,"contextmenu",BX.delegate(function(e){this.openPopupMenu(e,"copypaste",false);return BX.PreventDefault(e)},this));BX.bindDelegate(this.popupHistoryElements,"contextmenu",{className:"bx-messenger-history-item"},BX.delegate(function(e){this.openPopupMenu(e,"history",false);return BX.PreventDefault(e)},this))}BX.bindDelegate(this.popupHistoryElements,"click",{className:"bx-messenger-ajax"},BX.delegate(function(){if(BX.proxy_context.getAttribute("data-entity")=="user"){this.openPopupExternalData(BX.proxy_context,"user",true,{ID:BX.proxy_context.getAttribute("data-userId")})}else if(this.webrtc.phoneSupport()&&BX.proxy_context.getAttribute("data-entity")=="phoneCallHistory"){this.openPopupExternalData(BX.proxy_context,"phoneCallHistory",true,{ID:BX.proxy_context.getAttribute("data-historyID")})}},this));BX.bindDelegate(this.popupHistoryPanel,"click",{className:"bx-messenger-panel-filter"},BX.delegate(function(){if(this.popupHistoryFilterVisible){this.popupHistoryButtonFilter.innerHTML=BX.message("IM_HISTORY_FILTER_ON");this.popupHistoryItemsSize=this.popupHistoryItemsSize+this.popupHistoryButtonFilterBox.offsetHeight;this.popupHistoryItems.style.height=this.popupHistoryItemsSize+"px";this.popupHistoryFilesItems.style.height=this.popupHistoryItemsSize+"px";BX.style(this.popupHistoryButtonFilterBox,"display","none");BX.style(this.popupHistoryFilesButtonFilterBox,"display","none");this.popupHistoryFilterVisible=false;this.popupHistorySearchInput.value="";this.popupHistorySearchDateInput.value="";this.historySearch="";this.historyDateSearch="";this.historyFilesSearch="";this.drawHistory(this.historyUserId,false,false)}else{this.popupHistoryButtonFilter.innerHTML=BX.message("IM_HISTORY_FILTER_OFF");BX.style(this.popupHistoryButtonFilterBox,"display","block");BX.style(this.popupHistoryFilesButtonFilterBox,"display","block");this.popupHistoryItemsSize=this.popupHistoryItemsSize-this.popupHistoryButtonFilterBox.offsetHeight;this.popupHistoryItems.style.height=this.popupHistoryItemsSize+"px";this.popupHistoryFilesItems.style.height=this.popupHistoryItemsSize+"px";BX.focus(this.popupHistorySearchInput);this.popupHistoryFilterVisible=true}},this));BX.bindDelegate(this.popupHistoryPanel,"click",{className:"bx-messenger-panel-basket"},BX.delegate(function(){this.BXIM.openConfirm(BX.message("IM_M_HISTORY_DELETE_ALL_CONFIRM"),[new BX.PopupWindowButton({text:BX.message("IM_M_HISTORY_DELETE_ALL"),className:"popup-window-button-accept",events:{click:BX.delegate(function(){this.deleteAllHistory(e);BX.proxy_context.popupWindow.close()},this)}}),new BX.PopupWindowButton({text:BX.message("IM_NOTIFY_CONFIRM_CLOSE"),className:"popup-window-button-decline",events:{click:function(){this.popupWindow.close()}}})],true)},this));this.popupHistorySearchInput=BX.findChildByClassName(this.popupHistorySearchWrap,"bx-messenger-input");this.popupHistorySearchInputClose=BX.findChildByClassName(this.popupHistorySearchInput.parentNode,"bx-messenger-input-close");this.popupHistorySearchDateInput=BX.findChildByClassName(this.popupHistorySearchDateWrap,"bx-messenger-input");this.popupHistorySearchDateInputClose=BX.findChildByClassName(this.popupHistorySearchDateInput.parentNode,"bx-messenger-input-close");BX.bind(this.popupHistorySearchDateInput,"focus",BX.delegate(function(e){BX.calendar({node:BX.proxy_context,field:BX.proxy_context,bTime:false,callback_after:BX.delegate(this.newHistoryDateSearch,this)});return BX.PreventDefault(e)},this));BX.bind(this.popupHistorySearchDateInput,"click",BX.delegate(function(e){BX.calendar({node:BX.proxy_context,field:BX.proxy_context,bTime:false,callback_after:BX.delegate(this.newHistoryDateSearch,this)});return BX.PreventDefault(e)},this));BX.bind(this.popupHistorySearchDateInputClose,"click",BX.delegate(function(e){this.popupHistorySearchDateInput.value="";this.historyDateSearch="";this.historyLastSearch[this.historyUserId]="";this.drawHistory(this.historyUserId,false,false)},this));if(this.popupHistoryFilterVisible&&!BX.browser.IsAndroid()&&!BX.browser.IsIOS())BX.focus(this.popupHistorySearchInput);BX.bind(this.popupHistorySearchInputClose,"click",BX.delegate(function(e){this.popupHistorySearchInput.value="";this.historySearch="";this.historyLastSearch[this.historyUserId]="";this.drawHistory(this.historyUserId,false,false);return BX.PreventDefault(e)},this));BX.bind(this.popupHistorySearchInput,"keyup",BX.delegate(this.newHistorySearch,this));BX.bind(this.popupHistoryItems,"scroll",BX.delegate(function(){BX.MessengerCommon.loadHistory(e)},this));if(this.disk.enable){BX.bindDelegate(this.popupHistoryFilesBodyWrap,"click",{className:"bx-messenger-file-menu"},BX.delegate(function(e){var t=BX.proxy_context.parentNode.parentNode.getAttribute("data-fileId");var s=BX.proxy_context.parentNode.parentNode.getAttribute("data-chatId");this.openPopupMenu(BX.proxy_context,"historyFileMenu",true,{fileId:t,chatId:s});return BX.PreventDefault(e)},this));this.popupHistoryFilesSearchInput=BX.findChildByClassName(this.popupHistoryFilesSearchWrap,"bx-messenger-input");this.popupHistoryFilesSearchInputClose=BX.findChildByClassName(this.popupHistoryFilesSearchInput.parentNode,"bx-messenger-input-close");BX.bind(this.popupHistoryFilesSearchInputClose,"click",BX.delegate(function(e){this.popupHistoryFilesSearchInput.value="";this.historyFilesSearch="";this.historyFilesLastSearch[this.historyChatId]="";this.drawHistoryFiles(this.historyChatId,false,false);return BX.PreventDefault(e)},this));BX.bind(this.popupHistoryFilesSearchInput,"keyup",BX.delegate(this.newHistoryFilesSearch,this));BX.bind(this.popupHistoryFilesItems,"scroll",BX.delegate(function(){this.loadHistoryFiles(this.historyChatId)},this))}};BX.Messenger.prototype.loadHistoryFiles=function(e){if(this.historyFilesLoadFlag[e])return;if(this.historyFilesSearch!="")return;if(!(this.popupHistoryFilesItems.scrollTop>this.popupHistoryFilesItems.scrollHeight-this.popupHistoryFilesItems.offsetHeight-100))return;if(!this.historyFilesEndOfList[e]){this.historyFilesLoadFlag[e]=true;if(this.popupHistoryFilesBodyWrap.children.length>0)this.historyFilesOpenPage[e]=Math.floor(this.popupHistoryFilesBodyWrap.children.length/15)+1;else this.historyFilesOpenPage[e]=1;var t=null;this.popupHistoryFilesBodyWrap.appendChild(t=BX.create("div",{props:{className:"bx-messenger-content-load-more-history"},children:[BX.create("span",{props:{className:"bx-messenger-content-load-img"}}),BX.create("span",{
props:{className:"bx-messenger-content-load-text"},html:BX.message("IM_F_LOAD_FILES")})]}));BX.ajax({url:this.BXIM.pathToAjax+"?HISTORY_FILES_LOAD_MORE&V="+this.BXIM.revision,method:"POST",dataType:"json",timeout:30,data:{IM_HISTORY_FILES_LOAD:"Y",CHAT_ID:e,PAGE_ID:this.historyFilesOpenPage[e],IM_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()},onsuccess:BX.delegate(function(e){if(t)BX.remove(t);this.historyFilesLoadFlag[e.CHAT_ID]=false;if(e.FILES.length==0){this.historyFilesEndOfList[e.CHAT_ID]=true;return}var s=0;for(var i in e.FILES){if(!this.disk.files[e.CHAT_ID])this.disk.files[e.CHAT_ID]={};if(!this.disk.files[e.CHAT_ID][i]){e.FILES[i].date=parseInt(e.FILES[i].date)+parseInt(BX.message("USER_TZ_OFFSET"));this.disk.files[e.CHAT_ID][i]=e.FILES[i]}s++}if(s<15){this.historyFilesEndOfList[e.CHAT_ID]=true}for(var i in e.FILES){var a=this.disk.files[e.CHAT_ID][i];if(a&&!BX("im-file-history-panel-"+a.id)){var n=this.disk.drawHistoryFiles(e.CHAT_ID,a.id,{getElement:"Y"});if(n)this.popupHistoryFilesBodyWrap.appendChild(n)}}},this),onfailure:function(){if(t)BX.remove(t)}})}};BX.Messenger.prototype.deleteAllHistory=function(e){BX.ajax({url:this.BXIM.pathToAjax+"?HISTORY_REMOVE_ALL&V="+this.BXIM.revision,method:"POST",dataType:"json",timeout:30,data:{IM_HISTORY_REMOVE_ALL:"Y",USER_ID:e,IM_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()}});BX.localStorage.set("mhra",e,5);this.history[e]=[];this.showMessage[e]=[];this.popupHistoryBodyWrap.innerHTML="";this.popupHistoryBodyWrap.appendChild(BX.create("div",{props:{className:"bx-messenger-content-history-empty"},children:[BX.create("span",{props:{className:"bx-messenger-content-load-text"},html:BX.message("IM_M_NO_MESSAGE")})]}));if(this.desktop.ready())BX.desktop.onCustomEvent("main","bxImClearHistory",[e]);else if(this.BXIM.init)BX.MessengerCommon.drawTab(e)};BX.Messenger.prototype.drawMessageHistory=function(e){if(typeof e!="object")return null;if(typeof e.params!="object"){e.params={}}var t=e.senderId==0;if(e.system&&e.system=="Y"){t=true;e.senderId=0}var s=e.params&&e.params.IS_EDITED=="Y";var i=e.params&&e.params.IS_DELETED=="Y";var a=BX.MessengerCommon.diskDrawFiles(e.chatId,e.params.FILE_ID,{status:["done","error"],boxId:"im-file-history"});if(a.length>0){a=BX.create("div",{props:{className:"bx-messenger-file-box"+(e.text!=""?" bx-messenger-file-box-with-message":"")},children:a})}else{a=null}if(a==null&&e.text.length<=0){resultNode=BX.create("div",{attrs:{"data-messageId":e.id},props:{className:"bx-messenger-history-item-text bx-messenger-item-skipped"}})}else{resultNode=BX.create("div",{attrs:{"data-messageId":e.id},props:{className:"bx-messenger-history-item"+(e.senderId==0?" bx-messenger-history-item-3":e.senderId==this.BXIM.userId?"":" bx-messenger-history-item-2")},children:[BX.create("div",{props:{className:"bx-messenger-history-hide"},html:this.historyMessageSplit}),BX.create("span",{props:{className:"bx-messenger-history-item-avatar"},children:[BX.create("img",{props:{className:"bx-messenger-content-item-avatar-img"+(BX.MessengerCommon.isBlankAvatar(e.senderId>0?this.users[e.senderId].avatar:"")?" bx-messenger-content-item-avatar-img-default":"")},attrs:{src:e.senderId>0?this.users[e.senderId].avatar:this.BXIM.pathToBlankImage}})]}),BX.create("div",{props:{className:"bx-messenger-history-item-name"},html:(this.users[e.senderId]?this.users[e.senderId].name:BX.message("IM_M_SYSTEM_USER"))+' <span class="bx-messenger-history-hide">[</span><span class="bx-messenger-history-item-date">'+BX.MessengerCommon.formatDate(e.date,BX.MessengerCommon.getDateFormatType("MESSAGE"))+'</span><span class="bx-messenger-history-hide">]</span>'}),BX.create("div",{attrs:{id:"im-message-history-"+e.id},props:{className:"bx-messenger-history-item-text"+(i?" bx-messenger-message-deleted":" ")+(i||s?" bx-messenger-message-edited":"")},html:BX.MessengerCommon.prepareText(e.text,false,true,true)}),a,BX.create("div",{props:{className:"bx-messenger-history-hide"},html:"<br />"}),BX.create("div",{props:{className:"bx-messenger-history-hide"},html:this.historyMessageSplit})]})}return resultNode};BX.Messenger.prototype.drawHistory=function(e,t,s){if(this.popupHistory==null)return false;s=typeof s=="undefined"?true:s;var i=false;var a=0;if(e.toString().substr(0,4)=="chat"){i=true;a=e.toString().substr(4)}var n=[];var o=false;this.popupHistoryBodyWrap.innerHTML="";var r=this.historySearch.length>0;var t=!t?this.history:t;if(t[e]&&(!i&&this.users[e]||i&&this.chat[a])){var l=BX.util.array_unique(t[e]);var p={};l.sort(BX.delegate(function(e,t){e=parseInt(e);t=parseInt(t);if(!this.message[e]||!this.message[t]){return 0}var s=parseInt(this.message[e].date);var i=parseInt(this.message[t].date);if(s>i){return-1}else if(s<i){return 1}else{if(e>t){return-1}else if(e<t){return 1}else{return 0}}},this));for(var h=0;h<l.length;h++){if(r&&this.message[t[e][h]].text.toLowerCase().indexOf((this.historySearch+"").toLowerCase())<0)continue;var c=BX.MessengerCommon.formatDate(this.message[t[e][h]].date,BX.MessengerCommon.getDateFormatType("MESSAGE_TITLE"));if(!BX("bx-im-history-"+c)&&!p[c]){p[c]=true;n.push(BX.create("div",{props:{className:"bx-messenger-content-group bx-messenger-content-group-history"},children:[BX.create("div",{attrs:{id:"bx-im-history-"+c},props:{className:"bx-messenger-content-group-title"+(this.BXIM.language=="ru"?" bx-messenger-lowercase":"")},html:c})]}))}var u=this.drawMessageHistory(this.message[t[e][h]]);if(u)n.push(u)}if(n.length<=0){if(!this.historySearchBegin){o=true;n=[BX.create("div",{props:{className:"bx-messenger-content-history-empty"},children:[BX.create("span",{props:{className:"bx-messenger-content-load-text"},html:BX.message("IM_M_NO_MESSAGE")})]})]}}}else if(this.showMessage[e]&&this.showMessage[e].length<=0){o=true;n=[BX.create("div",{props:{className:"bx-messenger-content-history-empty"},children:[BX.create("span",{props:{className:"bx-messenger-content-load-text"},html:BX.message("IM_M_NO_MESSAGE")})]})]}if(n.length>0){BX.adjust(this.popupHistoryBodyWrap,{children:n});this.popupHistoryItems.scrollTop=0}if(s&&(!this.showMessage[e]||this.showMessage[e]&&this.showMessage[e].length<20)){if(o)this.popupHistoryFilesBodyWrap.innerHTML="";this.popupHistoryBodyWrap.appendChild(BX.create("div",{props:{className:BX.findChildrenByClassName(this.popupHistoryBodyWrap,"bx-messenger-history-item-text").length>0?"bx-messenger-content-load-more-history":"bx-messenger-content-load-history"},children:[BX.create("span",{props:{className:"bx-messenger-content-load-img"}}),BX.create("span",{props:{className:"bx-messenger-content-load-text"},html:BX.message("IM_M_LOAD_MESSAGE")})]}));BX.ajax({url:this.BXIM.pathToAjax+"?HISTORY_LOAD&V="+this.BXIM.revision,method:"POST",dataType:"json",skipAuthCheck:true,timeout:30,data:{IM_HISTORY_LOAD:"Y",USER_ID:e,USER_LOAD:i?this.chat[e.toString().substr(4)]&&this.chat[e.toString().substr(4)].fake?"Y":"N":this.users[e]&&this.users[e].fake?"Y":"N",IM_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()},onsuccess:BX.delegate(function(a){if(a&&a.BITRIX_SESSID){BX.message({bitrix_sessid:a.BITRIX_SESSID})}if(a.ERROR==""){if(!i){if(!this.userChat[e]){this.userChat[e]=a.CHAT_ID}}for(var n in a.FILES){if(!this.disk.files[a.CHAT_ID])this.disk.files[a.CHAT_ID]={};if(this.disk.files[a.CHAT_ID][n])continue;a.FILES[n].date=parseInt(a.FILES[n].date)+parseInt(BX.message("USER_TZ_OFFSET"));this.disk.files[a.CHAT_ID][n]=a.FILES[n]}this.showMessage[e]=[];this.sendAjaxTry=0;for(var n in a.MESSAGE){a.MESSAGE[n].date=parseInt(a.MESSAGE[n].date)+parseInt(BX.message("USER_TZ_OFFSET"));this.message[n]=a.MESSAGE[n];if(this.BXIM.settings.loadLastMessage)this.showMessage[e].push(n)}for(var n in a.USERS_MESSAGE){if(this.history[n])this.history[n]=BX.util.array_merge(this.history[n],a.USERS_MESSAGE[n]);else this.history[n]=a.USERS_MESSAGE[n]}if(!i&&this.users[e]&&!this.users[e].fake||i&&this.chat[a.CHAT_ID]&&!this.chat[a.CHAT_ID].fake){BX.cleanNode(this.popupHistoryBodyWrap);if(!a.USERS_MESSAGE[e]||a.USERS_MESSAGE[e].length<=0){this.popupHistoryBodyWrap.appendChild(BX.create("div",{props:{className:"bx-messenger-content-history-empty"},children:[BX.create("span",{props:{className:"bx-messenger-content-load-text"},html:BX.message("IM_M_NO_MESSAGE")})]}))}else{for(var n=0;n<a.USERS_MESSAGE[e].length;n++){var o=BX.MessengerCommon.formatDate(this.message[a.USERS_MESSAGE[e][n]].date,BX.MessengerCommon.getDateFormatType("MESSAGE_TITLE"));if(!BX("bx-im-history-"+o)){this.popupHistoryBodyWrap.appendChild(BX.create("div",{props:{className:"bx-messenger-content-group bx-messenger-content-group-history"},children:[BX.create("div",{attrs:{id:"bx-im-history-"+o},props:{className:"bx-messenger-content-group-title"+(this.BXIM.language=="ru"?" bx-messenger-lowercase":"")},html:o})]}))}var r=this.drawMessageHistory(this.message[a.USERS_MESSAGE[e][n]]);if(r)this.popupHistoryBodyWrap.appendChild(r)}}if(this.BXIM.settings.loadLastMessage&&this.currentTab==e)BX.MessengerCommon.drawTab(this.currentTab,true)}else{if(i&&this.chat[a.USER_ID.substr(4)].fake)this.chat[a.USER_ID.toString().substr(4)].name=BX.message("IM_M_USER_NO_ACCESS");if(!i){BX.MessengerCommon.getUserParam(e,true);this.users[e].name=BX.message("IM_M_USER_NO_ACCESS")}for(var n in a.USERS){this.users[n]=a.USERS[n]}for(var n in a.USER_IN_GROUP){if(typeof this.userInGroup[n]=="undefined"){this.userInGroup[n]=a.USER_IN_GROUP[n]}else{for(var l=0;l<a.USER_IN_GROUP[n].users.length;l++)this.userInGroup[n].users.push(a.USER_IN_GROUP[n].users[l]);this.userInGroup[n].users=BX.util.array_unique(this.userInGroup[n].users)}}for(var n in a.WO_USER_IN_GROUP){if(typeof this.woUserInGroup[n]=="undefined"){this.woUserInGroup[n]=a.WO_USER_IN_GROUP[n]}else{for(var l=0;l<a.WO_USER_IN_GROUP[n].users.length;l++)this.woUserInGroup[n].users.push(a.WO_USER_IN_GROUP[n].users[l]);this.woUserInGroup[n].users=BX.util.array_unique(this.woUserInGroup[n].users)}}for(var n in a.CHAT){this.chat[n]=a.CHAT[n]}for(var n in a.USER_IN_CHAT){this.userInChat[n]=a.USER_IN_CHAT[n]}for(var n in a.USER_BLOCK_CHAT){this.userChatBlockStatus[n]=a.USER_BLOCK_CHAT[n]}if(!i)BX.MessengerCommon.userListRedraw();this.dialogStatusRedraw();this.drawHistory(e,false,false)}if(this.historyChatId==0){this.historyChatId=a.CHAT_ID;this.drawHistoryFiles(this.historyChatId)}this.redrawHistoryPanel(e,i?a.USER_ID.substr(4):0)}else{if(a.ERROR=="SESSION_ERROR"&&this.sendAjaxTry<2){this.sendAjaxTry++;setTimeout(BX.delegate(function(){this.drawHistory(e,t,s)},this),1e3);BX.onCustomEvent(window,"onImError",[a.ERROR,a.BITRIX_SESSID])}else if(a.ERROR=="AUTHORIZE_ERROR"){this.sendAjaxTry++;if(this.desktop.ready()){setTimeout(BX.delegate(function(){this.drawHistory(e,t,s)},this),1e4)}BX.onCustomEvent(window,"onImError",[a.ERROR])}}},this),onfailure:BX.delegate(function(){this.sendAjaxTry=0},this)})}};BX.Messenger.prototype.redrawHistoryPanel=function(e,t){var s=e.toString().substr(0,4)=="chat"?true:false;var i=null;BX.MessengerCommon.getUserParam(e);if(!s){i=BX.create("div",{props:{className:"bx-messenger-panel bx-messenger-panel-bg2"},children:[BX.create("a",{attrs:{href:this.users[e].profile},props:{className:"bx-messenger-panel-avatar bx-messenger-panel-avatar-status-"+BX.MessengerCommon.getUserStatus(e)},children:[BX.create("img",{attrs:{src:this.users[e].avatar},props:{className:"bx-messenger-panel-avatar-img"+(BX.MessengerCommon.isBlankAvatar(this.users[e].avatar)?" bx-messenger-panel-avatar-img-default":"")}}),BX.create("span",{attrs:{title:BX.MessengerCommon.getUserStatus(e,true)},props:{className:"bx-messenger-panel-avatar-status"}})]}),this.popupHistoryButtonDeleteAll=BX.create("a",{props:{className:"bx-messenger-panel-basket"}}),this.popupHistoryButtonFilter=BX.create("a",{props:{className:"bx-messenger-panel-filter"},html:this.popupHistoryFilterVisible?BX.message("IM_HISTORY_FILTER_OFF"):BX.message("IM_HISTORY_FILTER_ON")}),BX.create("span",{props:{className:"bx-messenger-panel-title"},html:this.users[e].extranet?'<div class="bx-messenger-user-extranet">'+this.users[e].name+"</div>":this.users[e].name}),BX.create("span",{props:{className:"bx-messenger-panel-desc"},html:BX.MessengerCommon.getUserPosition(e)})]})}else{i=BX.create("div",{props:{className:"bx-messenger-panel bx-messenger-panel-bg2"},children:[BX.create("span",{props:{className:"bx-messenger-panel-avatar bx-messenger-panel-avatar-"+(this.chat[t].style=="call"?"call":"group")},children:[BX.create("img",{attrs:{src:this.chat[t].avatar},props:{className:"bx-messenger-panel-avatar-img"+(BX.MessengerCommon.isBlankAvatar(this.chat[t].avatar)?" bx-messenger-panel-avatar-img-default":"")}})]}),this.popupHistoryButtonDeleteAll=BX.create("a",{attrs:{title:BX.message("IM_M_HISTORY_DELETE_ALL")},props:{className:"bx-messenger-panel-basket"}}),this.popupHistoryButtonFilter=BX.create("a",{props:{className:"bx-messenger-panel-filter"},html:this.popupHistoryFilterVisible?BX.message("IM_HISTORY_FILTER_OFF"):BX.message("IM_HISTORY_FILTER_ON")}),BX.create("span",{props:{className:"bx-messenger-panel-title bx-messenger-panel-title-middle"},html:this.chat[t].name})]})}if(this.popupHistoryPanel){this.popupHistoryPanel.innerHTML="";BX.adjust(this.popupHistoryPanel,{children:[i]})}else{return[i]}};BX.Messenger.prototype.drawHistoryFiles=function(e,t,s){if(this.popupHistory==null)return false;s=typeof s=="undefined"?true:s;var i=this.historyFilesSearch.length>0;var t=!t?this.disk.files[e]:t;var a=[];var n=false;if(t){var o=BX.util.objectSort(t,"date","desc");for(var r=0;r<o.length;r++){if(i&&o[r].name.toLowerCase().indexOf((this.historyFilesSearch+"").toLowerCase())<0)continue;var l=this.disk.drawHistoryFiles(e,o[r].id,{getElement:"Y"});if(l)a.push(l)}if(a.length<=0){if(!this.historyFilesSearchBegin){n=true;a=[BX.create("div",{props:{className:"bx-messenger-content-history-empty"},children:[BX.create("span",{props:{className:"bx-messenger-content-load-text"},html:BX.message("IM_F_NO_FILES_2")})]})]}}if(a.length>=15){s=false}}else if(e==0){n=true;a=[BX.create("div",{props:{className:this.popupHistoryFilesBodyWrap.children.length>0?"bx-messenger-content-load-more-history":"bx-messenger-content-load-history"},children:[BX.create("span",{props:{className:"bx-messenger-content-load-img"}}),BX.create("span",{props:{className:"bx-messenger-content-load-text"},html:BX.message("IM_F_LOAD_FILES")})]})]}else{n=true;a=[BX.create("div",{props:{className:"bx-messenger-content-history-empty"},children:[BX.create("span",{props:{className:"bx-messenger-content-load-text"},html:BX.message("IM_F_NO_FILES_2")})]})]}this.popupHistoryFilesBodyWrap.innerHTML="";if(a.length>0){BX.adjust(this.popupHistoryFilesBodyWrap,{children:a});this.popupHistoryFilesItems.scrollTop=0}if(s&&e>0){if(n)this.popupHistoryFilesBodyWrap.innerHTML="";this.popupHistoryFilesBodyWrap.appendChild(BX.create("div",{props:{className:this.popupHistoryFilesBodyWrap.children.length>0?"bx-messenger-content-load-more-history":"bx-messenger-content-load-history"},children:[BX.create("span",{props:{className:"bx-messenger-content-load-img"}}),BX.create("span",{props:{className:"bx-messenger-content-load-text"},html:BX.message("IM_F_LOAD_FILES")})]}));BX.ajax({url:this.BXIM.pathToAjax+"?HISTORY_FILES_LOAD&V="+this.BXIM.revision,method:"POST",dataType:"json",skipAuthCheck:true,timeout:30,data:{IM_HISTORY_FILES_LOAD:"Y",CHAT_ID:e,IM_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()},onsuccess:BX.delegate(function(i){if(i&&i.BITRIX_SESSID){BX.message({bitrix_sessid:i.BITRIX_SESSID})}if(i.ERROR==""){for(var a in i.FILES){if(!this.disk.files[i.CHAT_ID])this.disk.files[i.CHAT_ID]={};i.FILES[a].date=parseInt(i.FILES[a].date)+parseInt(BX.message("USER_TZ_OFFSET"));this.disk.files[i.CHAT_ID][a]=i.FILES[a]}this.drawHistoryFiles(i.CHAT_ID,false,false)}else{if(i.ERROR=="SESSION_ERROR"&&this.sendAjaxTry<2){this.sendAjaxTry++;BX.message({bitrix_sessid:i.BITRIX_SESSID});setTimeout(BX.delegate(function(){this.drawHistoryFiles(e,t,s)},this),1e3);BX.onCustomEvent(window,"onImError",[i.ERROR,i.BITRIX_SESSID])}else if(i.ERROR=="AUTHORIZE_ERROR"){this.sendAjaxTry++;if(this.desktop.ready()){setTimeout(BX.delegate(function(){this.drawHistoryFiles(e,t,s)},this),1e4)}BX.onCustomEvent(window,"onImError",[i.ERROR])}}},this),onfailure:BX.delegate(function(){this.sendAjaxTry=0},this)})}};BX.Messenger.prototype.newHistorySearch=function(e){e=e||window.event;if(e.keyCode==27&&this.historySearch!="")BX.MessengerCommon.preventDefault(e);if(e.keyCode==27)this.popupHistorySearchInput.value="";this.historySearch=this.popupHistorySearchInput.value;if(this.historyLastSearch[this.historyUserId]==this.historySearch){return false}this.historyLastSearch[this.historyUserId]=this.historySearch;if(this.popupHistorySearchInput.value.length<=3){this.historySearch="";this.drawHistory(this.historyUserId,false,false);return false}this.popupHistorySearchDateInput.value="";this.historyDateSearch="";this.historySearchBegin=true;this.drawHistory(this.historyUserId,false,false);var t=BX.findChildByClassName(this.popupHistoryBodyWrap,"bx-messenger-content-load-history");if(t)BX.remove(t);var t=BX.findChildByClassName(this.popupHistoryBodyWrap,"bx-messenger-content-history-empty");if(t)BX.remove(t);var s=null;this.popupHistoryBodyWrap.appendChild(s=BX.create("div",{props:{className:this.popupHistoryBodyWrap.children.length>0?"bx-messenger-content-load-more-history":"bx-messenger-content-load-history"},children:[BX.create("span",{props:{className:"bx-messenger-content-load-img"}}),BX.create("span",{props:{className:"bx-messenger-content-load-text"},html:BX.message("IM_M_LOAD_MESSAGE")})]}));clearTimeout(this.historySearchTimeout);if(this.popupHistorySearchInput.value!=""){this.historySearchTimeout=setTimeout(BX.delegate(function(){BX.ajax({url:this.BXIM.pathToAjax+"?HISTORY_SEARCH&V="+this.BXIM.revision,method:"POST",dataType:"json",timeout:30,data:{IM_HISTORY_SEARCH:"Y",USER_ID:this.historyUserId,SEARCH:this.popupHistorySearchInput.value,IM_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()},onsuccess:BX.delegate(function(e){if(s)BX.remove(s);this.historySearchBegin=false;if(e.ERROR!="")return false;if(e.MESSAGE.length==0){var t={};t[e.USER_ID]=[];this.drawHistory(e.USER_ID,t,false);return}for(var i in e.MESSAGE){e.MESSAGE[i].date=parseInt(e.MESSAGE[i].date)+parseInt(BX.message("USER_TZ_OFFSET"));this.message[i]=e.MESSAGE[i]}for(var i in e.FILES){if(!this.disk.files[e.CHAT_ID])this.disk.files[e.CHAT_ID]={};e.FILES[i].date=parseInt(e.FILES[i].date)+parseInt(BX.message("USER_TZ_OFFSET"));this.disk.files[e.CHAT_ID][i]=e.FILES[i]}this.drawHistory(e.USER_ID,e.USERS_MESSAGE,false)},this),onfailure:BX.delegate(function(){if(s)BX.remove(s);this.historySearchBegin=false},this)})},this),1500)}return BX.PreventDefault(e)};BX.Messenger.prototype.newHistoryDateSearch=function(e){this.historyDateSearch=this.popupHistorySearchDateInput.value;if(this.historyLastSearch[this.historyUserId]==this.historyDateSearch){return false}this.historyLastSearch[this.historyUserId]=this.historyDateSearch;if(this.historyDateSearch.length<=3){this.historyDateSearch="";this.drawHistory(this.historyUserId,false,false);return false}this.popupHistorySearchInput.value="";this.historySearch="";this.historySearchBegin=true;var t=null;this.popupHistoryBodyWrap.innerHTML="";this.popupHistoryBodyWrap.appendChild(t=BX.create("div",{props:{className:this.popupHistoryBodyWrap.children.length>0?"bx-messenger-content-load-more-history":"bx-messenger-content-load-history"},children:[BX.create("span",{props:{className:"bx-messenger-content-load-img"}}),BX.create("span",{props:{className:"bx-messenger-content-load-text"},html:BX.message("IM_M_LOAD_MESSAGE")})]}));clearTimeout(this.historySearchTimeout);if(this.historyDateSearch!=""){this.historySearchTimeout=setTimeout(BX.delegate(function(){BX.ajax({url:this.BXIM.pathToAjax+"?HISTORY_DATE_SEARCH&V="+this.BXIM.revision,method:"POST",dataType:"json",timeout:30,data:{IM_HISTORY_DATE_SEARCH:"Y",USER_ID:this.historyUserId,DATE:this.historyDateSearch,IM_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()},onsuccess:BX.delegate(function(e){if(t)BX.remove(t);this.historySearchBegin=false;if(e.ERROR!="")return false;if(e.MESSAGE.length==0){var s={};s[e.USER_ID]=[];this.drawHistory(e.USER_ID,s,false);return}for(var i in e.MESSAGE){e.MESSAGE[i].date=parseInt(e.MESSAGE[i].date)+parseInt(BX.message("USER_TZ_OFFSET"));this.message[i]=e.MESSAGE[i]}for(var i in e.FILES){if(!this.disk.files[e.CHAT_ID])this.disk.files[e.CHAT_ID]={};e.FILES[i].date=parseInt(e.FILES[i].date)+parseInt(BX.message("USER_TZ_OFFSET"));this.disk.files[e.CHAT_ID][i]=e.FILES[i]}this.drawHistory(e.USER_ID,e.USERS_MESSAGE,false)},this),onfailure:BX.delegate(function(){if(t)BX.remove(t);this.historySearchBegin=false},this)})},this),1500)}};BX.Messenger.prototype.newHistoryFilesSearch=function(e){e=e||window.event;if(e.keyCode==27&&this.historyFilesSearch!="")BX.MessengerCommon.preventDefault(e);if(e.keyCode==27)this.popupHistoryFilesSearchInput.value="";this.historyFilesSearch=this.popupHistoryFilesSearchInput.value;if(this.historyFilesLastSearch[this.historyChatId]==this.historyFilesSearch){return false}this.historyFilesLastSearch[this.historyChatId]=this.historyFilesSearch;if(this.popupHistoryFilesSearchInput.value.length<=3){this.historyFilesSearch="";this.drawHistoryFiles(this.historyChatId,false,false);return false}this.historyFilesSearchBegin=true;this.historySearch=this.popupHistorySearchInput.value;this.drawHistoryFiles(this.historyChatId,false,false);var t=BX.findChildByClassName(this.popupHistoryFilesBodyWrap,"bx-messenger-content-load-history");if(t)BX.remove(t);var t=BX.findChildByClassName(this.popupHistoryFilesBodyWrap,"bx-messenger-content-history-empty");if(t)BX.remove(t);var s=null;this.popupHistoryFilesBodyWrap.appendChild(s=BX.create("div",{props:{className:this.popupHistoryFilesBodyWrap.children.length>0?"bx-messenger-content-load-more-history":"bx-messenger-content-load-history"},children:[BX.create("span",{props:{className:"bx-messenger-content-load-img"}}),BX.create("span",{props:{className:"bx-messenger-content-load-text"},html:BX.message("IM_F_LOAD_FILES")})]}));clearTimeout(this.historyFilesSearchTimeout);if(this.popupHistoryFilesSearchInput.value!=""){this.historyFilesSearchTimeout=setTimeout(BX.delegate(function(){BX.ajax({url:this.BXIM.pathToAjax+"?HISTORY_FILES_SEARCH&V="+this.BXIM.revision,method:"POST",dataType:"json",timeout:30,data:{IM_HISTORY_FILES_SEARCH:"Y",CHAT_ID:this.historyChatId,SEARCH:this.popupHistoryFilesSearchInput.value,IM_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()},onsuccess:BX.delegate(function(e){if(s)BX.remove(s);this.historyFilesSearchBegin=false;if(e.ERROR!="")return false;if(e.FILES.length==0){this.drawHistoryFiles(e.CHAT_ID,false,false);return}var t=false;for(var i in e.FILES){if(!this.disk.files[e.CHAT_ID])this.disk.files[e.CHAT_ID]={};if(!this.disk.files[e.CHAT_ID][i])e.FILES[i].fromSearch=true;e.FILES[i].date=parseInt(e.FILES[i].date)+parseInt(BX.message("USER_TZ_OFFSET"));this.disk.files[e.CHAT_ID][i]=e.FILES[i];t=true}this.drawHistoryFiles(e.CHAT_ID,t?e.FILES:false,false)},this),onfailure:BX.delegate(function(){if(s)BX.remove(s);this.historyFilesSearchBegin=false},this)})},this),1500)}return BX.PreventDefault(e)};BX.Messenger.prototype.setUpdateStateStep=function(e){e=e!=false;var t=this.updateStateStepDefault;if(!this.BXIM.ppStatus){if(this.popupMessenger!=null){t=20;if(this.updateStateVeryFastCount>0){t=5;this.updateStateVeryFastCount--}else if(this.updateStateFastCount>0){t=10;this.updateStateFastCount--}}}this.updateStateStep=parseInt(t);if(e)BX.localStorage.set("uss",this.updateStateStep,5);this.updateState()};BX.Messenger.prototype.updateState=function(e,t,s){if(!this.BXIM.tryConnect||this.popupMessengerConnectionStatusState=="offline")return false;e=e==true;t=t!=false;s=s||"UPDATE_STATE";clearTimeout(this.updateStateTimeout);this.updateStateTimeout=setTimeout(BX.delegate(function(){if(this.desktop.ready()){var e="IM UPDATE STATE: sending ajax"+(s=="UPDATE_STATE"?"":" ("+s+")")+" ["+this.updateStateCount+"]";BX.desktop.log("phone."+this.BXIM.userEmail+".log",e);console.log(e)}var i=BX.ajax({url:this.BXIM.pathToAjax+"?"+s+"&V="+this.BXIM.revision,method:"POST",dataType:"json",skipAuthCheck:true,lsId:"IM_UPDATE_STATE",lsTimeout:1,timeout:30,data:{IM_UPDATE_STATE:"Y",OPEN_MESSENGER:this.popupMessenger!=null?1:0,TAB:this.currentTab,FM:JSON.stringify(this.flashMessage),FN:JSON.stringify(this.notify.flashNotify),SITE_ID:BX.message("SITE_ID"),IM_AJAX_CALL:"Y",DESKTOP:this.desktop.ready()?"Y":"N",sessid:BX.bitrix_sessid()},onsuccess:BX.delegate(function(e){if(t)BX.localStorage.set("mus",true,5);if(this.desktop.ready()){var i="";if(e.ERROR==""){i="IM UPDATE STATE: success request ["+this.updateStateCount+"]"}else{i="IM UPDATE STATE: bad request ("+e.ERROR+") ["+this.updateStateCount+"]"}BX.desktop.log("phone."+this.BXIM.userEmail+".log",i);console.log(i)}this.updateStateCount++;if(e&&e.BITRIX_SESSID){BX.message({bitrix_sessid:e.BITRIX_SESSID})}if(e&&e.ERROR==""){if(!this.BXIM.checkRevision(e.REVISION))return false;if(this.BXIM.desktopDisk){this.BXIM.desktopDisk.checkRevision(e.DISK_REVISION)}BX.message({SERVER_TIME:e.SERVER_TIME});this.notify.updateNotifyCounters(e.COUNTERS,t);this.notify.updateNotifyMailCount(e.MAIL_COUNTER,t);if(!this.BXIM.xmppStatus&&e.XMPP_STATUS&&e.XMPP_STATUS=="Y")this.BXIM.xmppStatus=true;if(!this.BXIM.desktopStatus&&e.DESKTOP_STATUS&&e.DESKTOP_STATUS=="Y")this.BXIM.desktopStatus=true;var a=false;if(!(e.ONLINE.length<=0)){var n={};for(var o in this.users){if(typeof e.ONLINE[o]=="undefined"){if(this.users[o].status!="offline"){n[o]=this.users[o].status;this.users[o].status="offline";this.users[o].idle=0;a=true}}else{if(this.users[o].status!=e.ONLINE[o].status){n[o]=this.users[o].status;this.users[o].status=e.ONLINE[o].status;a=true}if(this.users[o].idle!=e.ONLINE[o].idle){this.users[o].idle=e.ONLINE[o].idle;a=true}}}}if(typeof e.FILES!="undefined"){for(var r in e.FILES){if(!this.disk.files[r])this.disk.files[r]={};for(var o in e.FILES[r]){e.FILES[r][o].date=parseInt(e.FILES[r][o].date)+parseInt(BX.message("USER_TZ_OFFSET"));this.disk.files[r][o]=e.FILES[r][o]}}}if(typeof e.MESSAGE!="undefined")for(var o in e.MESSAGE)e.MESSAGE[o].date=parseInt(e.MESSAGE[o].date)+parseInt(BX.message("USER_TZ_OFFSET"));BX.MessengerCommon.updateStateVar(e,t);if(typeof e.USERS_MESSAGE!="undefined")a=true;if(a){this.dialogStatusRedraw();BX.MessengerCommon.userListRedraw()}if(typeof e.NOTIFY!="undefined"){for(var o in e.NOTIFY){e.NOTIFY[o].date=parseInt(e.NOTIFY[o].date)+parseInt(BX.message("USER_TZ_OFFSET"));this.notify.notify[o]=e.NOTIFY[o];this.BXIM.lastRecordId=parseInt(o)>this.BXIM.lastRecordId?parseInt(o):this.BXIM.lastRecordId}for(var o in e.FLASH_NOTIFY)if(typeof this.notify.flashNotify[o]=="undefined")this.notify.flashNotify[o]=e.FLASH_NOTIFY[o];this.notify.changeUnreadNotify(e.UNREAD_NOTIFY,t)}if(BX.PULL&&e.PULL_CONFIG){BX.PULL.updateChannelID(e.PULL_CONFIG);BX.PULL.tryConnect()}this.setUpdateStateStep(false)}else if(e.ERROR=="SESSION_ERROR"&&this.sendAjaxTry<=2){this.sendAjaxTry++;setTimeout(BX.delegate(function(){this.updateState(true,t,s)},this),2e3);BX.onCustomEvent(window,"onImError",[e.ERROR,e.BITRIX_SESSID])}else if(s!="UPDATE_STATE_RECONNECT"){if(e.ERROR=="AUTHORIZE_ERROR"){this.sendAjaxTry++;if(this.desktop.ready()){setTimeout(BX.delegate(function(){this.updateState(true,t,s)},this),1e4)}BX.onCustomEvent(window,"onImError",[e.ERROR])}else if(this.sendAjaxTry<5){this.sendAjaxTry++;if(this.sendAjaxTry>=2&&!this.BXIM.desktop.ready()){BX.onCustomEvent(window,"onImError",[e.ERROR]);return false}setTimeout(BX.delegate(function(){this.updateState(true,t,s)},this),6e4);BX.onCustomEvent(window,"onImError",[e.ERROR])}else{}}},this),onfailure:BX.delegate(function(){if(this.desktop.ready()){var e="IM UPDATE STATE: failure request (code: "+i.status+") ["+this.updateStateCount+"]";BX.desktop.log("phone."+this.BXIM.userEmail+".log",e);console.log(e)}this.updateStateCount++;this.sendAjaxTry=0;this.setUpdateStateStep(false);try{if(typeof i=="object"&&i.status==0&&s!="UPDATE_STATE_RECONNECT")BX.onCustomEvent(window,"onImError",["CONNECT_ERROR"])}catch(t){}},this)})},this),e?150:this.updateStateStep*1e3)};BX.Messenger.prototype.updateStateLight=function(e,t){if(!this.BXIM.tryConnect||this.popupMessengerConnectionStatusState=="offline")return false;e=e==true;t=t!=false;clearTimeout(this.updateStateTimeout);this.updateStateTimeout=setTimeout(BX.delegate(function(){BX.ajax({url:this.BXIM.pathToAjax+"?UPDATE_STATE_LIGHT&V="+this.BXIM.revision,method:"POST",dataType:"json",skipAuthCheck:true,lsId:"IM_UPDATE_STATE_LIGHT",lsTimeout:1,timeout:this.updateStateStepDefault>10?this.updateStateStepDefault-2:10,data:{IM_UPDATE_STATE_LIGHT:"Y",SITE_ID:BX.message("SITE_ID"),IM_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()},onsuccess:BX.delegate(function(s){if(t)BX.localStorage.set("musl",true,5);if(s&&s.BITRIX_SESSID){BX.message({bitrix_sessid:s.BITRIX_SESSID})}if(s&&s.ERROR==""){if(!this.BXIM.checkRevision(s.REVISION))return false;BX.message({SERVER_TIME:s.SERVER_TIME});this.notify.updateNotifyCounters(s.COUNTERS,t);if(BX.PULL&&s.PULL_CONFIG){BX.PULL.updateChannelID(s.PULL_CONFIG);BX.PULL.tryConnect()}this.updateStateLight(e,t)}else{if(s.ERROR=="SESSION_ERROR"&&this.sendAjaxTry<=2){this.sendAjaxTry++;setTimeout(BX.delegate(function(){this.updateStateLight(true,t)},this),2e3);BX.onCustomEvent(window,"onImError",[s.ERROR,s.BITRIX_SESSID])}else if(s.ERROR=="AUTHORIZE_ERROR"){this.sendAjaxTry++;if(this.desktop.ready()){setTimeout(BX.delegate(function(){this.updateStateLight(true,t)},this),1e4)}BX.onCustomEvent(window,"onImError",[s.ERROR])}else if(this.sendAjaxTry<5){this.sendAjaxTry++;if(this.sendAjaxTry>=2&&!this.BXIM.desktop.ready()){BX.onCustomEvent(window,"onImError",[s.ERROR]);return false}setTimeout(BX.delegate(function(){this.updateStateLight(true,t)},this),6e4);BX.onCustomEvent(window,"onImError",[s.ERROR])}}},this),onfailure:BX.delegate(function(){this.sendAjaxTry=0;this.setUpdateStateStep(false);try{if(typeof _ajax=="object"&&_ajax.status==0)BX.onCustomEvent(window,"onImError",["CONNECT_ERROR"])}catch(e){}},this)})},this),e?150:this.updateStateStepDefault*1e3)};BX.Messenger.prototype.setClosingByEsc=function(e){if(this.popupMessenger==null)return false;if(e){if(!this.webrtc.callInit){this.popupMessenger.setClosingByEsc(true)}}else{this.popupMessenger.setClosingByEsc(false)}};BX.Messenger.prototype.extraOpen=function(e){this.setClosingByEsc(false);if(!this.BXIM.extraBind){BX.bind(window,"keydown",this.BXIM.extraBind=BX.proxy(function(e){if(e.keyCode==27&&!this.webrtc.callInit){if(this.popupMessenger&&!this.desktop.ready())this.popupMessenger.destroy()}},this))}this.BXIM.extraOpen=true;this.BXIM.dialogOpen=false;BX.style(this.popupMessengerDialog,"display","none");BX.style(this.popupMessengerExtra,"display","block");this.popupMessengerExtra.innerHTML="";BX.adjust(this.popupMessengerExtra,{children:[e]});this.resizeMainWindow()};BX.Messenger.prototype.extraClose=function(e,t){setTimeout(BX.delegate(function(){this.setClosingByEsc(true)},this),200);if(this.BXIM.extraBind){BX.unbind(window,"keydown",this.BXIM.extraBind);this.BXIM.extraBind=null}this.BXIM.extraOpen=false;this.BXIM.dialogOpen=true;e=e==true;t=t!=false;if(this.BXIM.notifyOpen)this.notify.closeNotify();this.closeMenuPopup();if(this.currentTab==0){this.extraOpen(BX.create("div",{attrs:{style:"padding-top: 300px"},props:{className:"bx-messenger-box-empty"},html:BX.message("IM_M_EMPTY")}))}else{BX.style(this.popupMessengerDialog,"display","block");BX.style(this.popupMessengerExtra,"display","none");this.popupMessengerExtra.innerHTML="";if(e){this.openChatFlag=this.currentTab.toString().substr(0,4)=="chat";BX.MessengerCommon.openDialog(this.currentTab,false,t)}}this.resizeMainWindow()};BX.Messenger.prototype.sendMessage=function(e){if(this.popupMessengerConnectionStatusState!="online")return false;e=typeof e=="string"||typeof e=="number"?e:this.currentTab;BX.MessengerCommon.endSendWriting(e);this.popupMessengerTextarea.value=this.popupMessengerTextarea.value.replace("    ","	");

this.popupMessengerTextarea.value=BX.util.trim(this.popupMessengerTextarea.value);if(this.popupMessengerTextarea.value.length==0)return false;if(this.BXIM.language=="ru"&&BX.correctText&&this.BXIM.settings.correctText){this.popupMessengerTextarea.value=BX.correctText(this.popupMessengerTextarea.value)}if(this.popupMessengerTextarea.value=="/clear"){this.popupMessengerTextarea.value="";this.textareaHistory[this.currentTab]="";this.showMessage[this.currentTab]=[];BX.MessengerCommon.drawTab(this.currentTab,true);if(this.desktop.ready())console.log("NOTICE: User use /clear");return false}else if(this.popupMessengerTextarea.value=="/webrtcDebug"||this.popupMessengerTextarea.value=="/webrtcDebug on"||this.popupMessengerTextarea.value=="/webrtcDebug off"){if(this.popupMessengerTextarea.value=="/webrtcDebug")this.webrtc.debug=this.webrtc.debug?false:true;else if(this.popupMessengerTextarea.value=="/webrtcDebug on")this.webrtc.debug=true;else if(this.popupMessengerTextarea.value=="/webrtcDebug off")this.webrtc.debug=false;if(this.webrtc.debug){this.tooltip(this.popupMessengerTextareaSendType.previousSibling,BX.message("IM_TIP_WEBRTC_ON"))}else{this.tooltip(this.popupMessengerTextareaSendType.previousSibling,BX.message("IM_TIP_WEBRTC_OFF"))}this.textareaHistory[this.currentTab]="";this.popupMessengerTextarea.value="";if(console&&console.log)console.log("NOTICE: User use /webrtcDebug and TURN "+(this.webrtc.debug?"ON":"OFF")+" debug");return false}else if(this.popupMessengerTextarea.value=="/windowReload"){this.textareaHistory[this.currentTab]="";this.popupMessengerTextarea.value="";location.reload();if(this.desktop.ready())console.log("NOTICE: User use /windowReload");return false}else if(this.popupMessengerTextarea.value=="/correctText on"||this.popupMessengerTextarea.value=="/correctText off"){if(this.popupMessengerTextarea.value=="/correctText on"){this.BXIM.settings.correctText=true;this.tooltip(this.popupMessengerTextareaSendType.previousSibling,BX.message("IM_TIP_AC_ON"))}else{this.BXIM.settings.correctText=false;this.tooltip(this.popupMessengerTextareaSendType.previousSibling,BX.message("IM_TIP_AC_OFF"))}this.BXIM.saveSettings({correctText:this.BXIM.settings.correctText});console.log("NOTICE: User use /correctText");return false}if(this.desktop.ready()){if(this.popupMessengerTextarea.value=="/openDeveloperTools"){this.textareaHistory[this.currentTab]="";this.popupMessengerTextarea.value="";BX.desktop.openDeveloperTools();console.log("NOTICE: User use /openDeveloperTools");return false}else if(this.popupMessengerTextarea.value=="/clearWindowSize"){this.BXIM.setLocalConfig("global_msz",false);BX.desktop.apiReady=false;location.reload();if(this.desktop.ready())console.log("NOTICE: User use /clearWindowSize");return false}}if(this.popupMessengerTextarea.value=="/showOnlyChat"){BX.MessengerCommon.recentListRedraw({showOnlyChat:true});this.textareaHistory[this.currentTab]="";this.popupMessengerTextarea.value="";return false}var t=e.toString().substr(0,4)=="chat"?e.toString().substr(4):this.userChat[e]?this.userChat[e]:0;if(this.errorMessage[e]){BX.MessengerCommon.sendMessageRetry();this.errorMessage[e]=false}var s=this.messageTmpIndex;this.message["temp"+s]={id:"temp"+s,chatId:t,senderId:this.BXIM.userId,recipientId:e,date:BX.MessengerCommon.getNowDate(),text:BX.MessengerCommon.prepareText(this.popupMessengerTextarea.value,true)};if(!this.showMessage[e])this.showMessage[e]=[];this.showMessage[e].push("temp"+s);this.messageTmpIndex++;BX.localStorage.set("mti",this.messageTmpIndex,5);if(this.popupMessengerTextarea==null||e!=this.currentTab)return false;clearTimeout(this.textareaHistoryTimeout);if(!BX.browser.IsAndroid()&&!BX.browser.IsIOS())BX.focus(this.popupMessengerTextarea);var i=BX.findChildByClassName(this.popupMessengerBodyWrap,"bx-messenger-content-load");if(i)BX.remove(i);var a=BX.findChildByClassName(this.popupMessengerBodyWrap,"bx-messenger-content-empty");if(a)BX.remove(a);BX.MessengerCommon.drawMessage(e,this.message["temp"+s]);BX.MessengerCommon.sendMessageAjax(s,e,this.popupMessengerTextarea.value,e.toString().substr(0,4)=="chat");if(this.BXIM.settings.status!="dnd"){this.BXIM.playSound("send")}this.textareaHistory[this.currentTab]="";this.popupMessengerTextarea.value="";setTimeout(BX.delegate(function(){this.popupMessengerTextarea.value=""},this),0);return true};BX.Messenger.prototype.textareaPrepareText=function(e,t,s,i){var a=true;if(t.altKey==true&&t.ctrlKey==true){}else if(t.metaKey==true||t.ctrlKey==true){var n={66:"b",83:"s",73:"i",85:"u"};if(n[t.keyCode]||t.keyCode==84||!this.desktop.ready()&&BX.browser.IsChrome()&&t.keyCode==69){var o=e.selectionStart;var r=e.selectionEnd;resultText=e.value.substring(o,r);if(t.keyCode==84||!this.desktop.ready()&&BX.browser.IsChrome()&&t.keyCode==69){if(o==r){o=0;r=e.value.length;resultText=e.value}e.value=e.value.substring(0,o)+BX.correctText(resultText,{replace_way:"AUTO",mixed:true})+e.value.substring(r,e.value.length);e.selectionStart=o;e.selectionEnd=r}else{if(o==r){return BX.PreventDefault(t)}resultTagStart=e.value.substring(o,o+3);resultTagEnd=e.value.substring(r-4,r);if(resultTagStart.toLowerCase()=="["+n[t.keyCode]+"]"&&resultTagEnd.toLowerCase()=="[/"+n[t.keyCode]+"]"){e.value=e.value.substring(0,o)+e.value.substring(o+3,r-4)+e.value.substring(r,e.value.length);e.selectionStart=o;e.selectionEnd=r-7}else{e.value=e.value.substring(0,o)+"["+n[t.keyCode]+"]"+resultText+"[/"+n[t.keyCode]+"]"+e.value.substring(r,e.value.length);e.selectionStart=o;e.selectionEnd=r+7}}return BX.PreventDefault(t)}}if(t.keyCode==9){this.insertTextareaText(e,"	");return BX.PreventDefault(t)}if(t.keyCode==27&&!this.desktop.ready()){i()}else if(t.keyCode==38&&this.popupMessengerLastMessage>0&&BX.util.trim(e.value).length<=0){this.editMessage(this.popupMessengerLastMessage)}else if(this.BXIM.settings.sendByEnter==true&&(t.ctrlKey==true||t.altKey==true)&&t.keyCode==13)this.insertTextareaText(e,"\n");else if(this.BXIM.settings.sendByEnter==true&&t.shiftKey==false&&t.keyCode==13)a=s();else if(this.BXIM.settings.sendByEnter==false&&t.ctrlKey==true&&t.keyCode==13)a=s();else if(this.BXIM.settings.sendByEnter==false&&(t.metaKey==true||t.altKey==true)&&t.keyCode==13&&BX.browser.IsMac())a=s();clearTimeout(this.textareaHistoryTimeout);this.textareaHistoryTimeout=setTimeout(BX.delegate(function(){this.textareaHistory[this.currentTab]=this.popupMessengerTextarea.value},this),200);if(BX.util.trim(e.value).length>2)BX.MessengerCommon.sendWriting(this.currentTab);if(!a)return BX.PreventDefault(t)};BX.Messenger.prototype.openSmileMenu=function(){if(!BX.proxy_context)return false;this.closePopupFileMenu();if(this.popupPopupMenu!=null)this.popupPopupMenu.destroy();if(this.popupSmileMenu!=null){this.popupSmileMenu.destroy();return false}if(this.smile==false){this.tooltip(BX.proxy_context,BX.message("IM_SMILE_NA"),{offsetLeft:-20});return false}var e={};for(var t in this.smile){if(!e[this.smile[t].SET_ID])e[this.smile[t].SET_ID]=[];e[this.smile[t].SET_ID].push(BX.create("img",{props:{className:"bx-messenger-smile-gallery-image"},attrs:{"data-code":BX.util.htmlspecialcharsback(t),style:"width: "+this.smile[t].WIDTH+"px; height: "+this.smile[t].HEIGHT+"px",src:this.smile[t].IMAGE,alt:t,title:BX.util.htmlspecialcharsback(this.smile[t].NAME)}}))}var s=0;var i=[];var a=[BX.create("span",{props:{className:"bx-messenger-smile-nav-name"},html:BX.message("IM_SMILE_SET")})];for(var t in this.smileSet){if(!e[t])continue;s++;i.push(BX.create("span",{attrs:{"data-set-id":t},props:{className:"bx-messenger-smile-gallery-set"+(s>1?" bx-messenger-smile-gallery-set-hide":"")},children:e[t]}));a.push(BX.create("span",{attrs:{"data-set-id":t,title:BX.util.htmlspecialcharsback(this.smileSet[t].NAME)},props:{className:"bx-messenger-smile-nav-item"+(s==1?" bx-messenger-smile-nav-item-active":"")}}))}this.popupSmileMenu=new BX.PopupWindow("bx-messenger-popup-smile",BX.proxy_context,{lightShadow:false,offsetTop:0,offsetLeft:-56,autoHide:true,closeByEsc:true,bindOptions:{position:"top"},zIndex:200,events:{onPopupClose:function(){this.destroy()},onPopupDestroy:BX.delegate(function(){this.popupSmileMenu=null},this)},content:BX.create("div",{props:{className:"bx-messenger-smile"},children:[this.popupSmileMenuGallery=BX.create("div",{props:{className:"bx-messenger-smile-gallery"},children:i}),this.popupSmileMenuSet=BX.create("div",{props:{className:"bx-messenger-smile-nav"+(s<=1?" bx-messenger-smile-nav-disabled":"")},children:a})]})});this.popupSmileMenu.setAngle({offset:74});this.popupSmileMenu.show();BX.bindDelegate(this.popupSmileMenuGallery,"click",{className:"bx-messenger-smile-gallery-image"},BX.delegate(function(){this.insertTextareaText(this.popupMessengerTextarea," "+BX.proxy_context.getAttribute("data-code")+" ",false);this.popupSmileMenu.close()},this));BX.bindDelegate(this.popupSmileMenuSet,"click",{className:"bx-messenger-smile-nav-item"},BX.delegate(function(){if(BX.hasClass(BX.proxy_context,"bx-messenger-smile-nav-item-active"))return false;var e=BX.findChildrenByClassName(this.popupSmileMenuGallery,"bx-messenger-smile-gallery-set",false);var t=BX.findChildrenByClassName(this.popupSmileMenuSet,"bx-messenger-smile-nav-item",false);for(var s=0;s<t.length;s++){if(BX.proxy_context==t[s]){BX.removeClass(e[s],"bx-messenger-smile-gallery-set-hide");BX.addClass(t[s],"bx-messenger-smile-nav-item-active")}else{BX.addClass(e[s],"bx-messenger-smile-gallery-set-hide");BX.removeClass(t[s],"bx-messenger-smile-nav-item-active")}}},this));return false};BX.Messenger.prototype.connectionStatus=function(e,t){t=typeof t=="undefined"?true:t;if(!(e=="online"||e=="connecting"||e=="offline"))return false;if(this.popupMessengerConnectionStatusState==e)return false;this.popupMessengerConnectionStatusState=e;var s="";if(e=="offline"){this.popupMessengerConnectionStatusStateText=BX.message("IM_CS_OFFLINE");s="bx-messenger-connection-status-offline"}else if(e=="connecting"){this.popupMessengerConnectionStatusStateText=BX.message("IM_CS_CONNECTING");s="bx-messenger-connection-status-connecting"}else if(e=="online"){this.popupMessengerConnectionStatusStateText=BX.message("IM_CS_ONLINE");s="bx-messenger-connection-status-online"}clearTimeout(this.popupMessengerConnectionStatusTimeout);if(!this.popupMessengerConnectionStatus)return false;if(e=="online"){if(t){if(this.redrawTab[this.currentTab]){BX.MessengerCommon.openDialog(this.currentTab)}else{this.updateState(true,false,"UPDATE_STATE_RECONNECT")}}clearTimeout(this.popupMessengerConnectionStatusTimeout);this.popupMessengerConnectionStatusTimeout=setTimeout(BX.delegate(function(){BX.removeClass(this.popupMessengerConnectionStatus,"bx-messenger-connection-status-show");this.popupMessengerConnectionStatusTimeout=setTimeout(BX.delegate(function(){BX.removeClass(this.popupMessengerConnectionStatus,"bx-messenger-connection-status-hide")},this),1e3)},this),4e3)}this.popupMessengerConnectionStatus.className="bx-messenger-connection-status bx-messenger-connection-status-show "+s;this.popupMessengerConnectionStatusText.innerHTML=this.popupMessengerConnectionStatusStateText;return true};BX.Messenger.prototype.editMessage=function(e){if(!BX.MessengerCommon.checkEditMessage(e))return false;BX.removeClass(this.popupMessengerEditForm,"bx-messenger-editform-disable");BX.addClass(this.popupMessengerEditForm,"bx-messenger-editform-show");this.popupMessengerEditMessageId=e;this.popupMessengerEditTextarea.value=BX.MessengerCommon.prepareTextBack(this.message[e].text,true);clearTimeout(this.popupMessengerEditFormTimeout);this.popupMessengerEditFormTimeout=setTimeout(BX.delegate(function(){if(!this.popupMessengerEditTextarea)return false;this.popupMessengerEditTextarea.focus();this.popupMessengerEditTextarea.selectionStart=this.popupMessengerEditTextarea.value.length;this.popupMessengerEditTextarea.selectionEnd=this.popupMessengerEditTextarea.value.length},this),200)};BX.Messenger.prototype.editMessageCancel=function(){this.popupMessengerEditTextarea.value="";if(BX.hasClass(this.popupMessengerEditForm,"bx-messenger-editform-disable"))return false;this.popupMessengerEditMessageId=0;BX.removeClass(this.popupMessengerEditForm,"bx-messenger-editform-show");BX.addClass(this.popupMessengerEditForm,"bx-messenger-editform-hide");clearTimeout(this.popupMessengerEditFormTimeout);this.popupMessengerEditFormTimeout=setTimeout(BX.delegate(function(){BX.removeClass(this.popupMessengerEditForm,"bx-messenger-editform-hide");BX.addClass(this.popupMessengerEditForm,"bx-messenger-editform-disable")},this),500);this.popupMessengerTextarea.focus();this.popupMessengerTextarea.selectionStart=this.popupMessengerTextarea.value.length;this.popupMessengerTextarea.selectionEnd=this.popupMessengerTextarea.value.length};BX.Messenger.prototype.editMessageAjax=function(e,t){if(this.popupMessengerConnectionStatusState!="online")return false;this.editMessageCancel();if(!BX.MessengerCommon.checkEditMessage(e))return false;if(t==BX.MessengerCommon.prepareTextBack(this.message[e].text,true))return false;t=t.replace("    ","	");t=BX.util.trim(t);if(t.length<=0){BX.MessengerCommon.deleteMessageAjax(e);return false}BX.MessengerCommon.drawProgessMessage(e);BX.ajax({url:this.BXIM.pathToAjax+"?MESSAGE_EDIT&V="+this.BXIM.revision,method:"POST",dataType:"json",timeout:30,data:{IM_EDIT_MESSAGE:"Y",ID:e,MESSAGE:t,IM_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()},onsuccess:BX.delegate(function(t){BX.MessengerCommon.clearProgessMessage(e)},this),onfailure:BX.delegate(function(){BX.MessengerCommon.clearProgessMessage(e)},this)})};BX.Messenger.prototype.deleteMessage=function(e,t){if(!BX.MessengerCommon.checkEditMessage(e))return false;if(t!==false){this.BXIM.openConfirm(BX.message("IM_M_HISTORY_DELETE_CONFIRM"),[new BX.PopupWindowButton({text:BX.message("IM_M_HISTORY_DELETE"),className:"popup-window-button-accept",events:{click:BX.delegate(function(){this.deleteMessage(e,false);BX.proxy_context.popupWindow.close()},this)}}),new BX.PopupWindowButton({text:BX.message("IM_NOTIFY_CONFIRM_CLOSE"),className:"popup-window-button-decline",events:{click:function(){this.popupWindow.close()}}})],true)}else{BX.MessengerCommon.deleteMessageAjax(e)}};BX.Messenger.prototype.insertQuoteMessage=function(e){var t=[];var s=true;var i="";var a="";var n=BX.findChildren(e.parentNode.nextSibling.firstChild,{tagName:"span"},false);for(var o=0;o<n.length;o++){var r=n[o].id.replace("im-message-","");if(this.message[r]){if(s){if(this.users[this.message[r].senderId]){i=this.users[this.message[r].senderId].name;a=this.message[r].date}s=false}t.push(BX.MessengerCommon.prepareTextBack(this.message[r].text))}}this.insertQuoteText(i,a,t.join("\n"))};BX.Messenger.prototype.insertQuoteText=function(e,t,s,i){var a=[];a.push((this.popupMessengerTextarea&&this.popupMessengerTextarea.value.length>0?"\n":"")+this.historyMessageSplit);a.push(BX.util.htmlspecialcharsback(e)+" ["+BX.MessengerCommon.formatDate(t)+"]");a.push(s);a.push(this.historyMessageSplit+"\n");if(i!==false){this.insertTextareaText(this.popupMessengerTextarea,a.join("\n"),false);setTimeout(BX.delegate(function(){this.popupMessengerTextarea.scrollTop=this.popupMessengerTextarea.scrollHeight;this.popupMessengerTextarea.focus()},this),100)}else{return a.join("\n")}};BX.Messenger.prototype.insertTextareaText=function(e,t,s){if(!e&&opener.BXIM.messenger.popupMessengerTextarea)e=opener.BXIM.messenger.popupMessengerTextarea;if(e.selectionStart||e.selectionStart=="0"){var i=e.selectionStart;var a=e.selectionEnd;e.value=e.value.substring(0,i)+t+e.value.substring(a,e.value.length);s=s!=false;if(s){e.selectionStart=i+1;e.selectionEnd=i+1}else if(BX.browser.IsChrome()||BX.browser.IsSafari()||this.desktop.ready()){e.selectionStart=e.value.length+1;e.selectionEnd=e.value.length+1}}if(document.selection&&document.documentMode&&document.documentMode<=8){e.focus();var n=document.selection.createRange();n.text=t}};BX.Messenger.prototype.resizeTextareaStart=function(e){if(this.webrtc.callOverlayFullScreen)return false;if(!e)e=window.event;this.popupMessengerTextareaResize.wndSize=BX.GetWindowScrollPos();this.popupMessengerTextareaResize.pos=BX.pos(this.popupMessengerTextarea);this.popupMessengerTextareaResize.y=e.clientY+this.popupMessengerTextareaResize.wndSize.scrollTop;this.popupMessengerTextareaResize.textOffset=this.popupMessengerTextarea.offsetHeight;this.popupMessengerTextareaResize.bodyOffset=this.popupMessengerBody.offsetHeight;BX.bind(document,"mousemove",BX.proxy(this.resizeTextareaMove,this));BX.bind(document,"mouseup",BX.proxy(this.resizeTextareaStop,this));if(document.body.setCapture)document.body.setCapture();document.onmousedown=BX.False;var t=document.body;t.ondrag=t.onselectstart=BX.False;t.style.MozUserSelect="none";t.style.cursor="move";if(this.popupSmileMenu)this.popupSmileMenu.close()};BX.Messenger.prototype.resizeTextareaMove=function(e){if(!e)e=window.event;var t=BX.GetWindowScrollPos();var s=e.clientX+t.scrollLeft;var i=e.clientY+t.scrollTop;if(this.popupMessengerTextareaResize.y==i)return;var a=Math.max(Math.min(-(i-this.popupMessengerTextareaResize.pos.top)+this.popupMessengerTextareaResize.textOffset,225),49);this.popupMessengerTextareaSize=a;this.popupMessengerTextarea.style.height=a+"px";this.popupMessengerBodySize=this.popupMessengerTextareaResize.textOffset-a+this.popupMessengerTextareaResize.bodyOffset;this.popupMessengerBody.style.height=this.popupMessengerBodySize+"px";this.resizeMainWindow();this.popupMessengerTextareaResize.x=s;this.popupMessengerTextareaResize.y=i};BX.Messenger.prototype.resizeTextareaStop=function(){if(document.body.releaseCapture)document.body.releaseCapture();BX.unbind(document,"mousemove",BX.proxy(this.resizeTextareaMove,this));BX.unbind(document,"mouseup",BX.proxy(this.resizeTextareaStop,this));document.onmousedown=null;this.popupMessengerBody.scrollTop=this.popupMessengerBody.scrollHeight-this.popupMessengerBody.offsetHeight;var e=document.body;e.ondrag=e.onselectstart=null;e.style.MozUserSelect="";e.style.cursor="";clearTimeout(this.BXIM.adjustSizeTimeout);this.BXIM.adjustSizeTimeout=setTimeout(BX.delegate(function(){this.BXIM.setLocalConfig("global_msz",{wz:this.popupMessengerFullWidth,ta2:this.popupMessengerTextareaSize,b:this.popupMessengerBodySize,cl:this.popupContactListSize,hi:this.popupHistoryItemsSize,fz:this.popupMessengerFullHeight,ez:this.popupContactListElementsSize,nz:this.notify.popupNotifySize,hf:this.popupHistoryFilterVisible,dw:window.innerWidth,dh:window.innerHeight,place:"taMove"})},this),500)};BX.Messenger.prototype.resizeWindowStart=function(){if(this.webrtc.callOverlayFullScreen)return false;if(this.popupMessengerTopLine)BX.remove(this.popupMessengerTopLine);this.popupMessengerWindow.pos=BX.pos(this.popupMessengerContent);this.popupMessengerWindow.mb=this.popupMessengerBodySize;this.popupMessengerWindow.nb=this.notify.popupNotifySize;BX.bind(document,"mousemove",BX.proxy(this.resizeWindowMove,this));BX.bind(document,"mouseup",BX.proxy(this.resizeWindowStop,this));if(document.body.setCapture)document.body.setCapture();document.onmousedown=BX.False;var e=document.body;e.ondrag=e.onselectstart=BX.False;e.style.MozUserSelect="none";e.style.cursor="move"};BX.Messenger.prototype.resizeWindowMove=function(e){if(!e)e=window.event;var t=BX.GetWindowScrollPos();var s=e.clientX+t.scrollLeft;var i=e.clientY+t.scrollTop;this.popupMessengerFullHeight=Math.max(Math.min(i-this.popupMessengerWindow.pos.top,1e3),this.popupMessengerMinHeight);this.popupMessengerFullWidth=Math.max(Math.min(s-this.popupMessengerWindow.pos.left,1200),this.popupMessengerMinWidth);this.popupMessengerContent.style.height=this.popupMessengerFullHeight+"px";this.popupMessengerContent.style.width=this.popupMessengerFullWidth+"px";var a=this.popupMessengerFullHeight-Math.max(Math.min(this.popupMessengerWindow.pos.height,1e3),this.popupMessengerMinHeight);this.popupMessengerBodySize=this.popupMessengerWindow.mb+a;if(this.popupMessengerBody!=null)this.popupMessengerBody.style.height=this.popupMessengerBodySize+"px";if(this.popupMessengerExtra!=null)this.popupMessengerExtra.style.height=this.popupMessengerFullHeight+"px";this.notify.popupNotifySize=Math.max(this.popupMessengerWindow.nb+(this.popupMessengerBodySize-this.popupMessengerWindow.mb),this.notify.popupNotifySizeDefault);if(this.notify.popupNotifyItem!=null)this.notify.popupNotifyItem.style.height=this.notify.popupNotifySize+"px";if(this.webrtc.callOverlay){BX.style(this.webrtc.callOverlay,"transition","none");BX.style(this.webrtc.callOverlay,"width",(this.popupMessengerExtra.style.display=="block"?this.popupMessengerExtra.offsetWidth-1:this.popupMessengerDialog.offsetWidth-1)+"px");BX.style(this.webrtc.callOverlay,"height",this.popupMessengerFullHeight-1+"px")}this.BXIM.messenger.redrawChatHeader();this.resizeMainWindow()};BX.Messenger.prototype.resizeWindowStop=function(){if(document.body.releaseCapture)document.body.releaseCapture();BX.unbind(document,"mousemove",BX.proxy(this.resizeWindowMove,this));BX.unbind(document,"mouseup",BX.proxy(this.resizeWindowStop,this));document.onmousedown=null;this.popupMessengerBody.scrollTop=this.popupMessengerBody.scrollHeight-this.popupMessengerBody.offsetHeight;var e=document.body;e.ondrag=e.onselectstart=null;e.style.MozUserSelect="";e.style.cursor="";if(this.webrtc.callOverlay)BX.style(this.webrtc.callOverlay,"transition","");clearTimeout(this.BXIM.adjustSizeTimeout);this.BXIM.adjustSizeTimeout=setTimeout(BX.delegate(function(){this.BXIM.setLocalConfig("global_msz",{wz:this.popupMessengerFullWidth,ta2:this.popupMessengerTextareaSize,b:this.popupMessengerBodySize,cl:this.popupContactListSize,hi:this.popupHistoryItemsSize,fz:this.popupMessengerFullHeight,ez:this.popupContactListElementsSize,nz:this.notify.popupNotifySize,hf:this.popupHistoryFilterVisible,dw:window.innerWidth,dh:window.innerHeight,place:"winMove"})},this),500)};BX.Messenger.prototype.newMessage=function(e){e=e!=false;var t=[];var s=[];var i=0;var a={};var n=0;for(var o in this.flashMessage){var r=false;var l=false;if(this.BXIM.isFocus()&&this.popupMessenger!=null&&o==this.currentTab){r=true;n++}else if(o.toString().substr(0,4)=="chat"&&this.userChatBlockStatus[o.substr(4)]&&this.userChatBlockStatus[o.substr(4)][this.BXIM.userId]=="Y"){l=true}if(r||l){for(var p in this.flashMessage[o]){if(this.flashMessage[o][p]!==false){this.flashMessage[o][p]=false;i++}}continue}for(var p in this.flashMessage[o]){if(this.flashMessage[o][p]!==false){var h=this.message[p].recipientId.toString().substr(0,4)=="chat";var c=this.message[p].recipientId;var u=h&&this.chat[c.substr(4)].style=="call";var d=!h&&this.message[p].senderId==0?o:this.message[p].senderId;var m=this.message[p].text_mobile?this.message[p].text_mobile:this.message[p].text;if(o!=this.BXIM.userId)a[o]=h?this.chat[c.substr(4)].name:this.users[d].name;m=m.replace(/------------------------------------------------------(.*?)------------------------------------------------------/gim,"["+BX.message("IM_M_QUOTE_BLOCK")+"]");if(m.length>150){m=m.substr(0,150);var g=m.lastIndexOf(" ");if(g<140)m=m.substr(0,g)+"...";else m=m.substr(0,140)+"..."}if(m==""&&this.message[p].params["FILE_ID"].length>0){m="["+BX.message("IM_F_FILE")+"]"}var f=BX.create("div",{attrs:{"data-userId":h?c:d,"data-messageId":p},props:{className:"bx-notifier-item"},children:[BX.create("span",{props:{className:"bx-notifier-item-content"},children:[BX.create("span",{props:{className:"bx-notifier-item-avatar"},children:[BX.create("img",{props:{className:"bx-notifier-item-avatar-img"+(BX.MessengerCommon.isBlankAvatar(h?this.chat[c.substr(4)].avatar:this.users[d].avatar)?h?" bx-notifier-item-avatar-img-default-"+(u?"4":"3"):" bx-notifier-item-avatar-img-default":"")},attrs:{src:h?this.chat[c.substr(4)].avatar:this.users[d].avatar}})]}),BX.create("a",{attrs:{href:"#","data-messageId":p},props:{className:"bx-notifier-item-delete"}}),BX.create("span",{props:{className:"bx-notifier-item-date"},html:BX.MessengerCommon.formatDate(this.message[p].date)}),BX.create("span",{props:{className:"bx-notifier-item-name"},html:h?this.chat[c.substr(4)].name:this.users[d].name}),BX.create("span",{props:{className:"bx-notifier-item-text"},html:(h&&d>0?"<i>"+this.users[d].name+"</i>: ":"")+BX.MessengerCommon.prepareText(m,false,true)})]})]});if(!this.BXIM.xmppStatus||this.BXIM.xmppStatus&&h){t.push(f);m=BX.util.htmlspecialcharsback(m);m=m.split("<br />").join("\n");m=m.replace(/\[USER=([0-9]{1,})\](.*?)\[\/USER\]/gi,function(e,t,s){return s});m=m.replace(/\[PCH=([0-9]{1,})\](.*?)\[\/PCH\]/gi,function(e,t,s){return s});s.push({id:h?c:d,title:BX.util.htmlspecialcharsback(h?this.chat[c.substr(4)].name:this.users[d].name),text:(h&&d>0?this.users[d].name+": ":"")+m,icon:h?this.chat[c.substr(4)].avatar:this.users[d].avatar,tag:"im-messenger-"+(h?c:d)})}this.flashMessage[o][p]=false}}}if(!(!this.desktop.ready()&&this.desktop.run())&&!this.desktop.ready()&&this.BXIM.desktopStatus)return false;if(t.length>5){var B="";for(var o in a)B+=", <i>"+a[o]+"</i>";var X={id:0,type:4,date:+new Date/1e3,title:BX.message("IM_NM_MESSAGE_1").replace("#COUNT#",t.length),text:BX.message("IM_NM_MESSAGE_2").replace("#USERS#",B.substr(2))};t=[];t.push(this.notify.createNotify(X,true));s=[];s.push({id:"",title:BX.message("IM_NM_MESSAGE_1").replace("#COUNT#",t.length),text:BX.message("IM_NM_MESSAGE_2").replace("#USERS#",BX.util.htmlspecialcharsback(B.substr(2))).replace(/<\/?[^>]+>/gi,"")})}else if(t.length==0){if(n>0&&this.desktop.ready())BX.desktop.flashIcon();if(e&&n>0&&this.BXIM.settings.status!="dnd"){this.BXIM.playSound("newMessage2")}return false}if(this.desktop.ready())BX.desktop.flashIcon();if(this.desktop.ready()){for(var o=0;o<t.length;o++){var y=t[o].getAttribute("data-messageId");var I='var notify = BX.findChildByClassName(document.body, "bx-notifier-item");'+'notify.style.cursor = "pointer";'+'BX.bind(notify, "click", function(){BX.desktop.onCustomEvent("main", "bxImClickNewMessage", [notify.getAttribute("data-userId")]); BX.desktop.windowCommand("close")});'+'BX.bind(BX.findChildByClassName(notify, "bx-notifier-item-delete"), "click", function(event){ BX.desktop.onCustomEvent("main", "bxImClickCloseMessage", [notify.getAttribute("data-userId")]); BX.desktop.windowCommand("close"); BX.MessengerCommon.preventDefault(event); });'+'BX.bind(notify, "contextmenu", function(){ BX.desktop.windowCommand("close")});';this.desktop.openNewMessage(y,t[o],I)}}else if(e&&!this.BXIM.windowFocus&&this.BXIM.notifyManager.nativeNotifyGranted()){for(var o=0;o<s.length;o++){var X=s[o];X.onshow=function(){var e=this;setTimeout(function(){e.close()},5e3)};X.onclick=function(){window.focus();top.BXIM.openMessenger(X.id);this.close()};this.BXIM.notifyManager.nativeNotify(X)}}else{if(this.BXIM.windowFocus&&this.BXIM.notifyManager.nativeNotifyGranted()){BX.localStorage.set("mnnb",true,1)}for(var o=0;o<t.length;o++){this.BXIM.notifyManager.add({html:t[o],tag:"im-message-"+t[o].getAttribute("data-userId"),userId:t[o].getAttribute("data-userId"),click:BX.delegate(function(e){this.openMessenger(e.notifyParams.userId);e.close()},this),close:BX.delegate(function(e){BX.MessengerCommon.readMessage(e.notifyParams.userId)},this)})}}if(this.desktop.ready())BX.desktop.flashIcon();if(e){this.BXIM.playSound("newMessage1")}};BX.Messenger.prototype.updateMessageCount=function(e){e=e!=false;var t=0;for(var s in this.unreadMessage)t=t+this.unreadMessage[s].length;if(e)BX.localStorage.set("mumc",{unread:this.unreadMessage,flash:this.flashMessage},5);if(this.messageCount!=t)BX.onCustomEvent(window,"onImUpdateCounterMessage",[t,"MESSAGE"]);this.messageCount=t;var i="";if(this.messageCount>99)i="99+";else if(this.messageCount>0)i=this.messageCount;if(this.notify.panelButtonMessageCount!=null){this.notify.panelButtonMessageCount.innerHTML=i;this.notify.adjustPosition({resize:true,timeout:500})}if(this.recentListTabCounter!=null)this.recentListTabCounter.innerHTML=this.messageCount>0?'<span class="bx-messenger-cl-count-digit">'+i+"</span>":"";if(this.desktop.run()){if(this.messageCount==0)BX.hide(this.notify.panelButtonMessage);else BX.show(this.notify.panelButtonMessage);BX.desktop.setTabBadge("im",this.messageCount)}return this.messageCount};BX.Messenger.prototype.setStatus=function(e,t){t=t!=false;this.users[this.BXIM.userId].status=e;this.BXIM.updateCounter();if(this.contactListPanelStatus!=null&&!BX.hasClass(this.contactListPanelStatus,"bx-messenger-cl-panel-status-"+e)){this.contactListPanelStatus.className="bx-messenger-cl-panel-status-wrap bx-messenger-cl-panel-status-"+e;var s=BX.findChildByClassName(this.contactListPanelStatus,"bx-messenger-cl-panel-status-text");s.innerHTML=BX.message("IM_STATUS_"+e.toUpperCase());if(t){this.BXIM.saveSettings({status:e});BX.onCustomEvent(this,"onStatusChange",[e]);BX.localStorage.set("mms",e,5)}}if(this.desktop.ready())BX.desktop.setIconStatus(e)};BX.Messenger.prototype.resizeMainWindow=function(){if(!this.desktop.run()){if(this.popupMessengerExtra.style.display=="block")this.popupContactListElementsSize=this.popupMessengerExtra.offsetHeight-159;else this.popupContactListElementsSize=this.popupMessengerDialog.offsetHeight-159;this.popupContactListElements.style.height=this.popupContactListElementsSize+"px"}};BX.Messenger.prototype.showTopLine=function(e,t){if(typeof e!="string")return false;var s=[];if(typeof t=="object"){var i=[];for(var a=0;a<t.length;a++)i.push(BX.create("span",{props:{className:"bx-messenger-box-topline-button"},html:t[a].title,events:{click:t[a].callback}}));s.push(BX.create("span",{props:{className:"bx-messenger-box-topline-buttons"},children:i}))}s.push(BX.create("span",{props:{className:"bx-messenger-box-topline-text"},children:[BX.create("span",{props:{className:"bx-messenger-box-topline-text-inner"},html:e})]}));this.popupMessengerTopLine.innerHTML="";BX.adjust(this.popupMessengerTopLine,{children:s});BX.addClass(this.popupMessengerTopLine,"bx-messenger-box-topline-show");return true};BX.Messenger.prototype.hideTopLine=function(){BX.removeClass(this.popupMessengerTopLine,"bx-messenger-box-topline-show")};BX.Messenger.prototype.closeMenuPopup=function(){if(this.popupPopupMenu!=null&&this.popupPopupMenuDateCreate+100<+new Date)this.popupPopupMenu.close();if(this.popupSmileMenu!=null)this.popupSmileMenu.close();if(this.notify.popupNotifyMore!=null)this.notify.popupNotifyMore.destroy();if(this.popupChatUsers!=null)this.popupChatUsers.close();if(this.webrtc.popupKeyPad!=null)this.webrtc.popupKeyPad.destroy();if(this.popupChatDialog!=null)this.popupChatDialog.destroy();if(this.popupTransferDialog!=null)this.popupTransferDialog.destroy();if(this.popupTooltip!=null)this.popupTooltip.destroy();this.closePopupFileMenu()};BX.Messenger.MenuPrepareList=function(e){var t=[];for(var s=0;s<e.length;s++){var i=e[s];if(i==null)continue;if(!i.separator&&(!i.text||!BX.type.isNotEmptyString(i.text)))continue;if(i.separator){t.push(BX.create("div",{props:{className:"bx-messenger-menu-hr"}}))}else if(i.type=="call"){var a=BX.create("a",{props:{className:"bx-messenger-popup-menu-item"},attrs:{title:i.title?i.title:"",href:i.href?i.href:""},events:i.onclick&&BX.type.isFunction(i.onclick)?{click:i.onclick}:null,html:'<div class="bx-messenger-popup-menu-item-call"><span class="bx-messenger-popup-menu-item-left"></span><span class="bx-messenger-popup-menu-item-title">'+i.text+'</span><span class="bx-messenger-popup-menu-right"></span></div>'+'<div><span class="bx-messenger-popup-menu-item-left"></span><span class="bx-messenger-popup-menu-item-text">'+i.phone+'</span><span class="bx-messenger-popup-menu-right"></span></div>'});if(i.href)a.href=i.href;t.push(a)}else{var a=BX.create("a",{props:{className:"bx-messenger-popup-menu-item"+(BX.type.isNotEmptyString(i.className)?" "+i.className:"")},attrs:{title:i.title?i.title:"",href:i.href?i.href:""},events:i.onclick&&BX.type.isFunction(i.onclick)?{click:i.onclick}:null,html:'<span class="bx-messenger-popup-menu-item-left"></span>'+(i.icon?'<span class="bx-messenger-popup-menu-item-icon '+i.icon+'"></span>':"")+'<span class="bx-messenger-popup-menu-item-text">'+i.text+'</span><span class="bx-messenger-popup-menu-right"></span>'
});if(i.href)a.href=i.href;t.push(a)}}return t};BX.Messenger.prototype.storageSet=function(e){if(e.key=="ims"){if(this.BXIM.settings.viewOffline!=e.value.viewOffline||this.BXIM.settings.viewGroup!=e.value.viewGroup)BX.MessengerCommon.userListRedraw(true);if(this.BXIM.settings.sendByEnter!=e.value.sendByEnter&&this.popupMessengerTextareaSendType)this.popupMessengerTextareaSendType.innerHTML=this.BXIM.settings.sendByEnter?"Enter":BX.browser.IsMac()?"&#8984;+Enter":"Ctrl+Enter";this.BXIM.settings=e.value}else if(e.key=="mus"){this.updateState(true,false)}else if(e.key=="musl"){this.updateStateLight(true,false)}else if(e.key=="mms"){this.setStatus(e.value,false)}else if(e.key=="mct"){}else if(e.key=="mrlr"){BX.MessengerCommon.recentListHide(e.value.userId,false)}else if(e.key=="mrd"){this.BXIM.settings.viewGroup=e.value.viewGroup;this.BXIM.settings.viewOffline=e.value.viewOffline;BX.MessengerCommon.userListRedraw()}else if(e.key=="mgp"){var t=this.contactListSearchText!=null&&this.contactListSearchText.length>0?false:this.BXIM.settings.viewGroup;if(t&&this.groups[e.value.id])this.groups[e.value.id].status=e.value.status;else if(!t&&this.woGroups[e.value.id])this.woGroups[e.value.id].status=e.value.status;BX.MessengerCommon.userListRedraw()}else if(e.key=="mrm"){BX.MessengerCommon.readMessage(e.value,false,false)}else if(e.key=="mcl"){BX.MessengerCommon.leaveFromChat(e.value,false)}else if(e.key=="mcl2"){this.muteMessageChat(e.value.chatId,e.value.mute,false)}else if(e.key=="mclk"){this.kickFromChat(e.value.chatId,e.value.userId)}else if(e.key=="mes"){this.BXIM.settings.enableSound=e.value}else if(e.key=="mti"){if(e.value>this.messageTmpIndex)this.messageTmpIndex=e.value}else if(e.key=="mns"){if(this.popupContactListSearchInput!=null)this.popupContactListSearchInput.value=e.value!=null?e.value+"":"";this.contactListSearchText=e.value!=null?e.value+"":""}else if(e.key=="msm"){if(this.message[e.value.id])return;this.message[e.value.id]=e.value;if(this.history[e.value.recipientId])this.history[e.value.recipientId].push(e.value.id);else this.history[e.value.recipientId]=[e.value.id];if(this.showMessage[e.value.recipientId])this.showMessage[e.value.recipientId].push(e.value.id);else this.showMessage[e.value.recipientId]=[e.value.id];BX.MessengerCommon.updateStateVar(e.value,false,false);BX.MessengerCommon.drawTab(e.value.recipientId,true)}else if(e.key=="uss"){this.updateStateStep=parseInt(e.value)}else if(e.key=="mumc"){setTimeout(BX.delegate(function(){var t=false;if(this.popupMessenger!=null&&this.BXIM.isFocus()){delete e.value.unread[this.currentTab];t=true}this.unreadMessage=e.value.unread;this.flashMessage=e.value.flash;this.updateMessageCount(t)},this),500)}else if(e.key=="mum"){this.message[e.value.message.id]=e.value.message;if(this.showMessage[e.value.userId]){this.showMessage[e.value.userId].push(e.value.message.id);this.showMessage[e.value.userId]=BX.util.array_unique(this.showMessage[e.value.userId])}else this.showMessage[e.value.userId]=[e.value.message.id];BX.MessengerCommon.drawMessage(e.value.userId,e.value.message,this.currentTab==e.value.userId)}else if(e.key=="muum"){BX.MessengerCommon.changeUnreadMessage(e.value,false)}else if(e.key=="mcam"&&!this.BXIM.ppServerStatus){if(this.popupMessenger!=null&&!this.webrtc.callInit)this.popupMessenger.close()}};BX.IM.Desktop=function(e,t){this.BXIM=e;this.clientVersion=false;this.markup=BX("placeholder-messanger");this.htmlWrapperHead=null;this.showNotifyId={};this.showMessageId={};this.lastSetIcon=null;this.topmostWindow=null;this.topmostWindowTimeout=null;this.topmostWindowCloseTimeout=null;this.minCallVideoWidth=320;this.minCallVideoHeight=180;this.minCallWidth=320;this.minCallHeight=35;this.minHistoryWidth=608;this.minHistoryDiskWidth=780;this.minHistoryHeight=593;this.minSettingsWidth=567;this.minSettingsHeight=BX.browser.IsMac()?326:335;if(this.run()&&!this.ready()&&BX.desktop.getApiVersion()>0){this.BXIM.init=false;this.BXIM.tryConnect=false}else if(this.run()&&this.BXIM.init){BX.desktop.addTab({id:"config",title:BX.message("IM_SETTINGS"),order:150,target:false,events:{open:BX.delegate(function(e){this.BXIM.openSettings({active:BX.desktop.getCurrentTab()})},this)}});BX.desktop.addSeparator({order:500});if(this.ready()&&!this.BXIM.bitrix24net){BX.desktop.addTab({id:"im-lf",title:BX.message("IM_DESKTOP_GO_SITE").replace("#COUNTER#",""),order:550,target:false,events:{open:function(){BX.desktop.browse(BX.desktop.getCurrentUrl())}}})}if(this.BXIM.animationSupport&&/Microsoft Windows NT 5/i.test(navigator.userAgent))this.BXIM.animationSupport=false;if(this.ready())this.BXIM.changeFocus(BX.desktop.windowIsFocused());BX.bind(window,"keydown",BX.delegate(function(e){if(!(BX.desktop.getCurrentTab()=="im"||BX.desktop.getCurrentTab()=="notify"||BX.desktop.getCurrentTab()=="im-phone"))return false;if(e.keyCode==27){if(this.messenger.popupSmileMenu){this.messenger.popupSmileMenu.destroy()}else if(this.messenger.popupMessengerFileButton!=null&&BX.hasClass(this.messenger.popupMessengerFileButton,"bx-messenger-textarea-file-active")){this.messenger.closePopupFileMenu()}else if(this.messenger.popupPopupMenu){this.messenger.popupPopupMenu.destroy()}else if(this.messenger.popupChatDialog&&this.messenger.popupChatDialogContactListSearch.value.length>=0){this.messenger.popupChatDialogContactListSearch.value=""}else if(this.BXIM.extraOpen){BX.desktop.changeTab("im");this.messenger.extraClose(true)}else if(this.messenger.renameChatDialogInput&&this.messenger.renameChatDialogInput.value.length>0){this.messenger.renameChatDialogInput.value=this.messenger.chat[this.messenger.currentTab.toString().substr(4)].name;this.messenger.popupMessengerTextarea.focus()}else if(this.messenger.popupContactListSearchInput&&this.messenger.popupContactListSearchInput.value.length>0){BX.MessengerCommon.contactListSearch({keyCode:27});this.messenger.popupMessengerTextarea.focus()}else{if(BX.util.trim(this.messenger.popupMessengerEditTextarea.value).length>0){this.messenger.editMessageCancel()}else if(BX.util.trim(this.messenger.popupMessengerTextarea.value).length<=0&&!this.webrtc.callInit){this.messenger.textareaHistory[this.messenger.currentTab]="";this.messenger.popupMessengerTextarea.value="";BX.desktop.windowCommand("hide")}else{this.messenger.textareaHistory[this.messenger.currentTab]="";this.messenger.popupMessengerTextarea.value=""}}}else if(e.altKey==true){if(e.keyCode==49||e.keyCode==50||e.keyCode==51||e.keyCode==52||e.keyCode==53||e.keyCode==54||e.keyCode==55||e.keyCode==56||e.keyCode==57){this.messenger.openMessenger(this.messenger.recentListIndex[parseInt(e.keyCode)-49]);BX.PreventDefault(e)}else if(e.keyCode==48){this.messenger.openMessenger(this.messenger.recentListIndex[9]);BX.PreventDefault(e)}}},this));BX.desktop.addCustomEvent("bxImClickNewMessage",BX.delegate(function(e){BX.desktop.windowCommand("show");BX.desktop.changeTab("im");this.BXIM.openMessenger(e)},this));BX.desktop.addCustomEvent("bxImClickCloseMessage",BX.delegate(function(e){BX.MessengerCommon.readMessage(e)},this));BX.desktop.addCustomEvent("bxImClickCloseNotify",BX.delegate(function(e){this.BXIM.notify.viewNotify(e)},this));BX.desktop.addCustomEvent("bxImClickNotify",BX.delegate(function(){BX.desktop.windowCommand("show");BX.desktop.changeTab("notify")},this));BX.desktop.addCustomEvent("bxCallDecline",BX.delegate(function(){var e=this.webrtc.callVideo;this.webrtc.callSelfDisabled=true;this.webrtc.callCommand(this.webrtc.callChatId,"decline",{ACTIVE:this.webrtc.callActive?"Y":"N",INITIATOR:this.webrtc.initiator?"Y":"N"});this.BXIM.playSound("stop");if(e&&this.webrtc.callStreamSelf!=null)this.webrtc.callOverlayVideoClose();else this.webrtc.callOverlayClose()},this));BX.desktop.addCustomEvent("bxPhoneAnswer",BX.delegate(function(){BX.desktop.windowCommand("show");BX.desktop.changeTab("im");this.BXIM.stopRepeatSound("ringtone");this.webrtc.phoneIncomingAnswer();this.closeTopmostWindow()},this));BX.desktop.addCustomEvent("bxPhoneSkip",BX.delegate(function(){this.webrtc.phoneCallFinish();this.webrtc.callAbort();this.webrtc.callOverlayClose()},this));BX.desktop.addCustomEvent("bxCallOpenDialog",BX.delegate(function(){BX.desktop.windowCommand("show");BX.desktop.changeTab("im");if(this.BXIM.dialogOpen){if(this.webrtc.callOverlayUserId>0){this.messenger.openChatFlag=false;BX.MessengerCommon.openDialog(this.webrtc.callOverlayUserId,false,false)}else{this.messenger.openChatFlag=true;BX.MessengerCommon.openDialog("chat"+this.webrtc.callOverlayChatId,false,false)}}else{if(this.webrtc.callOverlayUserId>0){this.messenger.openChatFlag=false;this.messenger.currentTab=this.webrtc.callOverlayUserId}else{this.messenger.openChatFlag=true;this.messenger.currentTab="chat"+this.webrtc.callOverlayChatId}this.messenger.extraClose(true,false)}this.webrtc.callOverlayToggleSize(false)},this));BX.desktop.addCustomEvent("bxCallMuteMic",BX.delegate(function(){if(this.webrtc.phoneCurrentCall)this.webrtc.phoneToggleAudio();else this.webrtc.toggleAudio();var e=BX.findChildByClassName(BX("bx-messenger-call-overlay-button-mic"),"bx-messenger-call-overlay-button-mic");if(e)BX.toggleClass(e,"bx-messenger-call-overlay-button-mic-off")},this));BX.desktop.addCustomEvent("bxCallAnswer",BX.delegate(function(e,t,s,i){BX.desktop.windowCommand("show");BX.desktop.changeTab("im");this.webrtc.callActive=true;BX.ajax({url:this.BXIM.pathToCallAjax+"?CALL_ANSWER&V="+this.BXIM.revision,method:"POST",dataType:"json",timeout:30,data:{IM_CALL:"Y",COMMAND:"answer",CHAT_ID:e,CALL_TO_GROUP:i?"Y":"N",RECIPIENT_ID:this.callUserId,IM_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()},onsuccess:BX.delegate(function(){this.webrtc.callDialog()},this)})},this));BX.desktop.addCustomEvent("bxCallJoin",BX.delegate(function(e,t,s,i){BX.desktop.windowCommand("show");BX.desktop.changeTab("im");this.webrtc.callAbort();this.webrtc.callOverlayClose(false);this.webrtc.callInvite(i?"chat"+e:t,s)},this));BX.desktop.addCustomEvent("bxImClearHistory",BX.delegate(function(e){this.messenger.history[e]=[];this.messenger.showMessage[e]=[];if(this.BXIM.init)BX.MessengerCommon.drawTab(e)},this));BX.desktop.addCustomEvent("bxSaveSettings",BX.delegate(function(e){this.BXIM.settings=e;if(this.BXIM.messenger!=null){BX.MessengerCommon.userListRedraw(true);if(this.BXIM.messenger.popupMessengerTextareaSendType)this.BXIM.messenger.popupMessengerTextareaSendType.innerHTML=this.BXIM.settings.sendByEnter?"Enter":BX.browser.IsMac()?"&#8984;+Enter":"Ctrl+Enter"}},this));BX.desktop.addCustomEvent("bxImClickConfirmNotify",BX.delegate(function(e){delete this.BXIM.notify.notify[e];delete this.BXIM.notify.unreadNotify[e];delete this.BXIM.notify.flashNotify[e];this.BXIM.notify.updateNotifyCount(false);if(this.BXIM.openNotify)this.BXIM.notify.openNotify(true,true)},this));BX.desktop.addCustomEvent("BXUserAway",BX.delegate(this.onAwayAction,this));BX.desktop.addCustomEvent("BXTrayAction",BX.delegate(this.onTrayAction,this));BX.desktop.addCustomEvent("BXWakeAction",BX.delegate(this.onWakeAction,this));BX.desktop.addCustomEvent("BXForegroundChanged",BX.delegate(function(e){clearTimeout(this.BXIM.windowFocusTimeout);this.BXIM.windowFocusTimeout=setTimeout(BX.delegate(function(){this.BXIM.changeFocus(e);if(this.BXIM.isFocus()&&this.messenger&&this.messenger.unreadMessage[this.messenger.currentTab]&&this.messenger.unreadMessage[this.messenger.currentTab].length>0)BX.MessengerCommon.readMessage(this.messenger.currentTab);if(this.BXIM.isFocus("notify")&&this.notify){if(this.notify.unreadNotifyLoad)this.notify.loadNotify();else if(this.notify.notifyUpdateCount>0)this.notify.viewNotifyAll()}if(e){this.closeCallFloatDialog()}else{this.openCallFloatDialog()}},this),e?500:0)},this));BX.bind(window,"blur",BX.delegate(function(){this.openCallFloatDialog()},this));BX.bind(window,"focus",BX.delegate(function(){this.closeCallFloatDialog()},this));BX.desktop.addCustomEvent("BXTrayMenu",BX.delegate(function(){var t=e.notify.getCounter("**");var s=e.notify.getCounter("im_notify");var i=e.notify.getCounter("im_message");BX.desktop.addTrayMenuItem({Id:"messenger",Order:100,Title:(BX.message("IM_DESKTOP_OPEN_MESSENGER")||"").replace("#COUNTER#",i>0?"("+i+")":""),Callback:function(){BX.desktop.windowCommand("show");BX.desktop.changeTab("im");e.messenger.openMessenger(e.messenger.currentTab)},Default:true});BX.desktop.addTrayMenuItem({Id:"notify",Order:120,Title:(BX.message("IM_DESKTOP_OPEN_NOTIFY")||"").replace("#COUNTER#",s>0?"("+s+")":""),Callback:function(){BX.desktop.windowCommand("show");BX.desktop.changeTab("notify");e.notify.openNotify(false,true)}});BX.desktop.addTrayMenuItem({Id:"bdisk",Order:130,Title:BX.message("IM_DESKTOP_BDISK"),Callback:function(){if(BX.desktop.diskAttachStatus()){BX.desktop.diskOpenFolder()}else{BX.desktop.windowCommand("show");BX.desktop.changeTab("disk")}}});BX.desktop.addTrayMenuItem({Id:"site",Order:140,Title:(BX.message("IM_DESKTOP_GO_SITE")||"").replace("#COUNTER#",t>0?"("+t+")":""),Callback:function(){BX.desktop.browse(BX.desktop.getCurrentUrl())}});BX.desktop.addTrayMenuItem({Id:"separator1",IsSeparator:true,Order:150});BX.desktop.addTrayMenuItem({Id:"settings",Order:160,Title:BX.message("IM_DESKTOP_SETTINGS"),Callback:function(){e.openSettings()}});BX.desktop.addTrayMenuItem({Id:"separator2",IsSeparator:true,Order:1e3});BX.desktop.addTrayMenuItem({Id:"logout",Order:1010,Title:BX.message("IM_DESKTOP_LOGOUT"),Callback:function(){BX.desktop.logout(false,"tray_menu")}})},this));BX.desktop.addCustomEvent("BXProtocolUrl",BX.delegate(function(e,t){t=t?t:{};if(t.bitrix24net&&t.bitrix24net=="Y"&&!this.BXIM.bitrix24net)return false;BX.desktop.setActiveWindow();if(e=="messenger"){if(t.dialog){this.BXIM.openMessenger(t.dialog)}else if(t.chat){this.BXIM.openMessenger("chat"+t.chat)}else{this.BXIM.openMessenger()}BX.desktop.windowCommand("show")}else if(e=="chat"&&t.id){this.BXIM.openMessenger("chat"+t.id);BX.desktop.windowCommand("show")}else if(e=="notify"){this.BXIM.openNotify();BX.desktop.windowCommand("show")}else if(e=="history"&&t.user){if(t.dialog){this.BXIM.openHistory(t.dialog)}else if(t.chat){this.BXIM.openHistory("chat"+t.chat)}BX.desktop.windowCommand("show")}else if(e=="callto"){if(t.video){this.BXIM.callTo(t.video,true)}else if(t.audio){this.BXIM.callTo(t.audio,false)}else if(t.phone){if(t.params){var s={};t.params=t.params.split("!!");var i="";var a=true;for(var n=0;n<t.params.length;n++){if(a){i=t.params[n];a=false}else{a=true;s[i]=t.params[n]}}this.webrtc.phoneCall(unescape(t.phone),s)}else{this.BXIM.phoneTo(unescape(t.phone))}}BX.desktop.windowCommand("show")}},this));BX.addCustomEvent("onPullEvent-webdav",function(e,t){BX.desktop.diskReportStorageNotification(e,t)});BX.addCustomEvent("onPullEvent-main",BX.delegate(function(e,t){if(e=="user_counter"&&t[BX.message("SITE_ID")]){if(t[BX.message("SITE_ID")]["**"]){var s=parseInt(t[BX.message("SITE_ID")]["**"]);this.notify.updateNotifyCounters({"**":s})}}},this))}};BX.IM.Desktop.prototype.run=function(){return typeof BX.desktop!="undefined"};BX.IM.Desktop.prototype.ready=function(){return typeof BX.desktop!="undefined"&&BX.desktop.ready()};BX.IM.Desktop.prototype.getCurrentUrl=function(){if(!this.run())return false;return BX.desktop.getCurrentUrl()};BX.IM.Desktop.prototype.enableInVersion=function(e){if(!this.run())return false;return BX.desktop.enableInVersion(e)};BX.IM.Desktop.prototype.addCustomEvent=function(e,t){if(!this.run())return false;BX.desktop.addCustomEvent(e,t)};BX.IM.Desktop.prototype.onCustomEvent=function(e,t,s){if(!this.run())return false;BX.desktop.addCustomEvent(e,t,s)};BX.IM.Desktop.prototype.windowCommand=function(e,t){if(!this.run())return false;if(typeof t=="undefined")BX.desktop.windowCommand(e);else BX.desktop.windowCommand(t,e)};BX.IM.Desktop.prototype.browse=function(e){if(!this.run())return false;BX.desktop.browse(e)};BX.IM.Desktop.prototype.drawOnPlaceholder=function(e){if(this.markup==null||!BX.type.isDomNode(e))return false;this.markup.innerHTML="";this.markup.appendChild(e)};BX.IM.Desktop.prototype.openNewNotify=function(e,t,s){if(!this.ready())return;if(t=="")return false;if(this.showNotifyId[e])return false;this.showNotifyId[e]=true;var i={};i[e]=this.BXIM.notify.notify[e];BXDesktopSystem.ExecuteCommand("notification.show.html",this.getHtmlPage(t,s,{notify:i},"im-notify-popup"))};BX.IM.Desktop.prototype.openNewMessage=function(e,t,s){if(!this.ready())return;if(t=="")return false;if(this.showMessageId[e])return false;this.showMessageId[e]=true;BXDesktopSystem.ExecuteCommand("notification.show.html",this.getHtmlPage(t,s,true,"im-notify-popup"))};BX.IM.Desktop.prototype.adjustSize=function(){if(!this.ready()||!this.BXIM.init||!this.BXIM.messenger||!this.BXIM.notify)return false;if(window.innerWidth<BX.desktop.minWidth||window.innerHeight<BX.desktop.minHeight)return false;var e=document.body.offsetHeight-this.initHeight;this.initHeight=document.body.offsetHeight;this.BXIM.messenger.popupMessengerBodySize=Math.max(this.BXIM.messenger.popupMessengerBodySize+e,295-(this.BXIM.messenger.popupMessengerTextareaSize-49));if(this.BXIM.messenger.popupMessengerBody!=null){this.BXIM.messenger.popupMessengerBody.style.height=this.BXIM.messenger.popupMessengerBodySize+"px";this.BXIM.messenger.redrawChatHeader()}this.BXIM.messenger.popupContactListElementsSize=Math.max(this.BXIM.messenger.popupContactListElementsSize+e,this.BXIM.messenger.popupContactListElementsSizeDefault);if(this.BXIM.messenger.popupContactListElements!=null)this.BXIM.messenger.popupContactListElements.style.height=this.BXIM.messenger.popupContactListElementsSize+"px";this.BXIM.messenger.popupMessengerFullHeight=document.body.offsetHeight;if(this.BXIM.messenger.popupMessengerExtra!=null)this.BXIM.messenger.popupMessengerExtra.style.height=this.BXIM.messenger.popupMessengerFullHeight+"px";this.BXIM.notify.popupNotifySize=Math.max(this.BXIM.notify.popupNotifySize+e,this.BXIM.notify.popupNotifySizeDefault);if(this.BXIM.notify.popupNotifyItem!=null)this.BXIM.notify.popupNotifyItem.style.height=this.BXIM.notify.popupNotifySize+"px";if(this.BXIM.webrtc.callOverlay){this.BXIM.webrtc.callOverlay.style.transition="none";this.BXIM.webrtc.callOverlay.style.width=(this.BXIM.messenger.popupMessengerExtra.style.display=="block"?this.BXIM.messenger.popupMessengerExtra.offsetWidth-1:this.BXIM.messenger.popupMessengerDialog.offsetWidth-1)+"px";this.BXIM.webrtc.callOverlay.style.height=this.BXIM.messenger.popupMessengerFullHeight-1+"px"}this.BXIM.messenger.closeMenuPopup();clearTimeout(this.BXIM.adjustSizeTimeout);this.BXIM.adjustSizeTimeout=setTimeout(BX.delegate(function(){this.BXIM.setLocalConfig("global_msz",{wz:this.BXIM.messenger.popupMessengerFullWidth,ta2:this.BXIM.messenger.popupMessengerTextareaSize,b:this.BXIM.messenger.popupMessengerBodySize,cl:this.BXIM.messenger.popupContactListSize,hi:this.BXIM.messenger.popupHistoryItemsSize,fz:this.BXIM.messenger.popupMessengerFullHeight,ez:this.BXIM.messenger.popupContactListElementsSize,nz:this.BXIM.notify.popupNotifySize,hf:this.BXIM.messenger.popupHistoryFilterVisible,dw:window.innerWidth,dh:window.innerHeight,place:"desktop"});if(this.BXIM.webrtc.callOverlay)this.BXIM.webrtc.callOverlay.style.transition=""},this),500);return true};BX.IM.Desktop.prototype.autoResize=function(e){if(!this.ready())return;BX.desktop.resize()};BX.IM.Desktop.prototype.openSettings=function(e,t,s){if(!this.ready())return false;s=s||{};if(s.minSettingsWidth)this.minSettingsWidth=s.minSettingsWidth;if(s.minSettingsHeight)this.minSettingsHeight=s.minSettingsHeight;BX.desktop.createWindow("settings",BX.delegate(function(s){s.SetProperty("clientSize",{Width:this.minSettingsWidth,Height:this.minSettingsHeight});s.SetProperty("resizable",false);s.SetProperty("title",BX.message("IM_SETTINGS"));s.ExecuteCommand("html.load",this.getHtmlPage(e,t,{}))},this))};BX.IM.Desktop.prototype.openHistory=function(e,t,s){if(!this.ready())return false;BX.desktop.createWindow("history",BX.delegate(function(i){var a={chat:{},users:{},files:{}};if(e.toString().substr(0,4)=="chat"){var n=e.substr(4);a["chat"][n]=this.messenger.chat[n];a["files"][n]=this.disk.files[n];for(var o=0;o<this.messenger.userInChat[n].length;o++)a["users"][this.messenger.userInChat[n][o]]=this.messenger.users[this.messenger.userInChat[n][o]]}else{n=this.messenger.userChat[e]?this.messenger.userChat[e]:0;a["userChat"]={};a["userChat"][e]=n;a["users"][e]=this.messenger.users[e];a["users"][this.BXIM.userId]=this.messenger.users[this.BXIM.userId];a["files"][n]=this.disk.files[n]}i.SetProperty("clientSize",{Width:this.messenger.disk.enable?this.minHistoryDiskWidth:this.minHistoryWidth,Height:this.minHistoryHeight});i.SetProperty("minClientSize",{Width:this.messenger.disk.enable?this.minHistoryDiskWidth:this.minHistoryWidth,Height:this.minHistoryHeight});i.SetProperty("resizable",false);i.ExecuteCommand("html.load",this.getHtmlPage(t,s,a));i.SetProperty("title",BX.message("IM_M_HISTORY"))},this))};BX.IM.Desktop.prototype.openCallFloatDialog=function(){if(!this.BXIM.init||!this.ready()||!this.webrtc||!this.webrtc.callActive||this.topmostWindow||this.phoneTransferEnabled)return false;if(this.webrtc.callVideo&&!this.webrtc.callStreamMain)return false;if(!this.webrtc.callOverlayTitleBlock)return false;this.openTopmostWindow("callFloatDialog",'BXIM.webrtc.callFloatDialog("'+BX.util.jsencode(this.webrtc.callOverlayTitleBlock.innerHTML)+'", "'+(this.webrtc.callVideo?this.webrtc.callOverlayVideoMain.src:"")+'", '+(this.webrtc.audioMuted?1:0)+")",{},"im-desktop-call")};BX.IM.Desktop.prototype.closeCallFloatDialog=function(){if(!this.ready()||!this.topmostWindow)return false;if(this.webrtc.callActive){if(this.webrtc.callOverlayUserId>0&&this.webrtc.callOverlayUserId==this.messenger.currentTab){this.closeTopmostWindow()}else if(this.webrtc.callOverlayChatId>0&&this.webrtc.callOverlayChatId==this.messenger.currentTab.toString().substr(4)){this.closeTopmostWindow()}}else{this.closeTopmostWindow()}};BX.IM.Desktop.prototype.openTopmostWindow=function(e,t,s,i){if(!this.ready())return false;this.closeTopmostWindow();clearTimeout(this.topmostWindowTimeout);this.topmostWindowTimeout=setTimeout(BX.delegate(function(){if(this.topmostWindow)return false;this.topmostWindow=BXDesktopSystem.ExecuteCommand("topmost.show.html",this.getHtmlPage("",t,s,i))},this),500)};BX.IM.Desktop.prototype.closeTopmostWindow=function(){clearTimeout(this.topmostWindowTimeout);clearTimeout(this.topmostWindowCloseTimeout);if(!this.topmostWindow)return false;if(this.topmostWindow.document&&this.topmostWindow.document.title.length>0)BX.desktop.windowCommand(this.topmostWindow,"hide");this.topmostWindowCloseTimeout=setTimeout(BX.delegate(function(){if(this.topmostWindow){if(this.topmostWindow.document&&this.topmostWindow.document.title.length>0){BX.desktop.windowCommand(this.topmostWindow,"close");this.topmostWindow=null}else{this.closeTopmostWindow()}}},this),300)};BX.IM.Desktop.prototype.getHtmlPage=function(e,t,s,i){if(!this.ready())return;e=e||"";t=t||"";i=i||"";var a=typeof s=="undefined"||typeof s!="object"?{}:s;s=typeof s!="undefined";if(this.htmlWrapperHead==null)this.htmlWrapperHead=document.head.outerHTML.replace(/BX\.PULL\.start\([^)]*\);/g,"");if(e!=""&&BX.type.isDomNode(e))e=e.outerHTML;if(t!=""&&BX.type.isDomNode(t))t=t.outerHTML;if(t!="")t='<script type="text/javascript">BX.ready(function(){'+t+"});</script>";var n="";if(s==true){n='<script type="text/javascript">'+"BX.ready(function() {"+"BXIM = new BX.IM(null, {"+"'init': false,"+"'settings' : "+JSON.stringify(this.BXIM.settings)+","+"'settingsView' : "+JSON.stringify(this.BXIM.settingsView)+","+"'updateStateInterval': '"+this.BXIM.updateStateInterval+"',"+"'desktop': "+this.run()+","+"'ppStatus': false,"+"'ppServerStatus': false,"+"'xmppStatus': "+this.BXIM.xmppStatus+","+"'bitrixNetworkStatus': "+this.BXIM.bitrixNetworkStatus+","+"'bitrix24Status': "+this.BXIM.bitrix24Status+","+"'bitrixIntranet': "+this.BXIM.bitrixIntranet+","+"'bitrixXmpp': "+this.BXIM.bitrixXmpp+","+"'files' : "+(a.files?JSON.stringify(a.files):"{}")+","+"'notify' : "+(a.notify?JSON.stringify(a.notify):"{}")+","+"'users' : "+(a.users?JSON.stringify(a.users):"{}")+","+"'chat' : "+(a.chat?JSON.stringify(a.chat):"{}")+","+"'userChat' : "+(a.userChat?JSON.stringify(a.userChat):"{}")+","+"'userInChat' : "+(a.userInChat?JSON.stringify(a.userInChat):"{}")+","+"'hrphoto' : "+(a.hrphoto?JSON.stringify(a.hrphoto):"{}")+","+"'phoneCrm' : "+(a.phoneCrm?JSON.stringify(a.phoneCrm):"{}")+","+"'userId': "+this.BXIM.userId+","+"'userEmail': '"+this.BXIM.userEmail+"',"+"'disk': {'enable': "+(this.disk?this.disk.enable:false)+"},"+"'path' : "+JSON.stringify(this.BXIM.path)+"});"+"});"+"</script>"}return"<!DOCTYPE html><html>"+this.htmlWrapperHead+'<body class="im-desktop im-desktop-popup '+i+'"><div id="placeholder-messanger">'+e+"</div>"+n+t+"</body></html>"};BX.IM.Desktop.prototype.onAwayAction=function(e){BX.ajax({url:this.BXIM.pathToAjax+"?IDLE&V="+this.BXIM.revision,method:"POST",dataType:"json",timeout:30,data:{IM_IDLE:"Y",IM_AJAX_CALL:"Y",IDLE:e?"Y":"N",sessid:BX.bitrix_sessid()},onsuccess:BX.delegate(function(e){if(e&&e.BITRIX_SESSID){BX.message({bitrix_sessid:e.BITRIX_SESSID})}if(e.ERROR=="AUTHORIZE_ERROR"&&this.desktop.ready()&&this.messenger.sendAjaxTry<3){this.messenger.sendAjaxTry++;BX.onCustomEvent(window,"onImError",[e.ERROR])}else if(e.ERROR=="SESSION_ERROR"&&this.messenger.sendAjaxTry<2){this.messenger.sendAjaxTry++;BX.onCustomEvent(window,"onImError",[e.ERROR,e.BITRIX_SESSID])}else{if(e.ERROR=="AUTHORIZE_ERROR"||e.ERROR=="SESSION_ERROR"){BX.onCustomEvent(window,"onImError",[e.ERROR])}}},this)})};BX.IM.Desktop.prototype.onWakeAction=function(){BX.desktop.setIconStatus("offline");BX.desktop.checkInternetConnection(function(){BX.desktop.windowReload()},BX.delegate(function(){BX.desktop.login()},this),10)};BX.IM.Desktop.prototype.onTrayAction=function(){BX.desktop.windowCommand("show");var e=this.BXIM.notify.getCounter("im_message");var t=this.BXIM.notify.getCounter("im_notify");if(e>0){if(this.BXIM.notifyOpen==true&&t>0){BX.desktop.changeTab("notify");this.BXIM.notify.openNotify(false,true);this.BXIM.messenger.popupContactListSearchInput.focus()}else{BX.desktop.changeTab("im");this.BXIM.messenger.openMessenger();this.BXIM.messenger.popupMessengerTextarea.focus()}}else if(t>0){BX.desktop.changeTab("notify");this.BXIM.notify.openNotify(false,true);this.BXIM.messenger.popupContactListSearchInput.focus()}else if(this.BXIM.messenger.popupMessengerTextarea){BX.desktop.changeTab("im");this.BXIM.messenger.popupMessengerTextarea.focus()}};BX.IM.Desktop.prototype.birthdayStatus=function(e){if(!this.ready())return false;if(typeof e!="boolean"){return this.BXIM.getLocalConfig("birthdayStatus",true)}else{this.BXIM.setLocalConfig("birthdayStatus",e);return e}};BX.IM.Desktop.prototype.changeTab=function(e){return false};BX.PopupWindowDesktop=function(){this.closeByEsc=true;this.setClosingByEsc=function(e){this.closeByEsc=e};this.close=function(){BX.desktop.windowCommand("close")};this.destroy=function(){BX.desktop.windowCommand("close")}};BX.IM.WebRTC=function(e,t){this.BXIM=e;this.screenSharing=new BX.IM.ScreenSharing(this,t);this.panel=t.panel;this.desktop=t.desktopClass;this.callToPhone=false;this.callOverlayFullScreen=false;this.callToMobile=false;this.callAspectCheckInterval;this.callAspectHorizontal=true;this.callInviteTimeout=null;this.callNotify=null;this.callAllowTimeout=null;this.callDialogAllow=null;this.callOverlay=null;this.callOverlayMinimize=null;this.callOverlayChatId=0;this.callOverlayUserId=0;this.callSelfDisabled=false;this.callOverlayPhotoSelf=null;this.callOverlayPhotoUsers={};this.callOverlayVideoUsers={};this.callOverlayVideoPhotoUsers={};this.callOverlayOptions={};this.callOverlayPhotoCompanion=null;this.callOverlayPhotoMini=null;this.callOverlayVideoMain=null;this.callOverlayVideoReserve=null;this.callOverlayVideoSelf=null;this.callOverlayProgressBlock=null;this.callOverlayStatusBlock=null;this.callOverlayButtonsBlock=null;this.phoneEnabled=t.phoneEnabled;this.phoneSipAvailable=t.phoneSipAvailable;this.phoneDeviceActive=t.phoneDeviceActive=="Y";this.phoneCallerID="";this.phoneLogin="";this.phoneServer="";this.phoneCheckBalance=false;this.phoneCallHistory={};this.phoneSDKinit=false;this.phoneMicAccess=false;this.phoneIncoming=false;this.phoneCallId="";this.phoneCallExternal=false;this.phoneCallDevice="WEBRTC";this.phoneNumber="";this.phoneNumberUser="";this.phoneNumberLast=this.BXIM.getLocalConfig("phone_last","");this.phoneParams={};this.phoneAPI=null;this.phoneDisconnectAfterCallFlag=true;this.phoneCurrentCall=null;this.phoneCrm=t.phoneCrm?t.phoneCrm:{};this.phoneMicMuted=false;this.phoneHolded=false;this.phoneRinging=0;this.phoneTransferEnabled=false;this.phoneTransferUser=0;this.phoneTransferTimeout=0;this.phoneConnectedInterval=null;this.phoneDeviceDelayTimeout=null;this.debug=false;this.popupTransferDialog=null;this.popupTransferDialogDestElements=null;this.popupTransferDialogContactListSearch=null;this.popupTransferDialogContactListElements=null;if(this.setTurnServer){this.setTurnServer({turnServer:t.turnServer||"",turnServerFirefox:t.turnServerFirefox||"",turnServerLogin:t.turnServerLogin||"",turnServerPassword:t.turnServerPassword||""})}this.defineButtons();var s=false;if(this.enabled){s=true;BX.addCustomEvent("onPullEvent-im",BX.delegate(function(t,s){if(t=="call"){this.log("Incoming",s.command,s.senderId,JSON.stringify(s));if(s.command=="join"){for(var i in s.users)this.messenger.users[i]=s.users[i];for(var i in s.hrphoto)this.messenger.hrphoto[i]=s.hrphoto[i];if(this.callInit||this.callActive){setTimeout(BX.delegate(function(){BX.ajax({url:this.BXIM.pathToCallAjax+"?CALL_BUSY&V="+this.BXIM.revision,method:"POST",dataType:"json",timeout:30,data:{IM_CALL:"Y",COMMAND:"busy",CHAT_ID:s.chatId,RECIPIENT_ID:s.senderId,VIDEO:s.video?"Y":"N",IM_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()}})},this),s.callToGroup?1e3:0)}else{if(this.desktop.ready()||!this.desktop.ready()&&!this.BXIM.desktopStatus){this.messenger.openMessenger("chat"+s.chatId);this.BXIM.repeatSound("ringtone",5e3);this.callNotifyWait(s.chatId,s.senderId,s.video,s.callToGroup,true)}if(this.desktop.ready()&&!this.BXIM.windowFocus){var a={users:{},chat:{},userInChat:{},hrphoto:{}};if(s.callToGroup){a["chat"][s.chatId]=this.messenger.chat[s.chatId];a["userInChat"][s.chatId]=this.messenger.userInChat[s.chatId]}for(var i=0;i<this.messenger.userInChat[s.chatId].length;i++){a["users"][this.messenger.userInChat[s.chatId][i]]=this.messenger.users[this.messenger.userInChat[s.chatId][i]];a["hrphoto"][this.messenger.userInChat[s.chatId][i]]=this.messenger.hrphoto[this.messenger.userInChat[s.chatId][i]]}this.desktop.openTopmostWindow("callNotifyWaitDesktop","BXIM.webrtc.callNotifyWaitDesktop("+s.chatId+",'"+s.senderId+"', "+(s.video?1:0)+", "+(s.callToGroup?1:0)+", true);",a,"im-desktop-call")}}}else if(s.command=="invite"||s.command=="invite_join"){for(var i in s.users)this.messenger.users[i]=s.users[i];for(var i in s.hrphoto)this.messenger.hrphoto[i]=s.hrphoto[i];for(var i in s.chat)this.messenger.chat[i]=s.chat[i];for(var i in s.userInChat)this.messenger.userInChat[i]=s.userInChat[i];if(this.callInit||this.callActive){if(s.command=="invite"){if(this.callChatId==s.chatId){this.callCommand(s.chatId,"busy_self");this.callOverlayClose(false)}else{setTimeout(BX.delegate(function(){BX.ajax({url:this.BXIM.pathToCallAjax+"?CALL_BUSY&V="+this.BXIM.revision,method:"POST",dataType:"json",timeout:30,data:{IM_CALL:"Y",COMMAND:"busy",CHAT_ID:s.chatId,RECIPIENT_ID:s.senderId,VIDEO:s.video?"Y":"N",IM_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()}})},this),s.callToGroup?1e3:0)}}else if(this.initiator&&this.callChatId==s.chatId){this.initiator=false;this.callDialog();BX.ajax({url:this.BXIM.pathToCallAjax+"?CALL_ANSWER&V="+this.BXIM.revision,method:"POST",dataType:"json",timeout:30,data:{IM_CALL:"Y",COMMAND:"answer",CHAT_ID:this.callChatId,CALL_TO_GROUP:this.callToGroup?"Y":"N",RECIPIENT_ID:this.callUserId,IM_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()}})}}else{if(this.desktop.ready()||!this.desktop.ready()&&!this.BXIM.desktopStatus||this.desktop.run()&&!this.desktop.ready()&&this.BXIM.desktopStatus){
this.BXIM.repeatSound("ringtone",5e3);this.callCommand(s.chatId,"wait");if(this.desktop.run())BX.desktop.changeTab("im");this.callNotifyWait(s.chatId,s.senderId,s.video,s.callToGroup);if(s.isMobile){this.callToMobile=true;BX.addClass(this.callOverlay,"bx-messenger-call-overlay-mobile")}}if(this.desktop.ready()&&!this.BXIM.isFocus("all")){var a={users:{},chat:{},userInChat:{},hrphoto:{}};if(s.callToGroup){a["chat"][s.chatId]=this.messenger.chat[s.chatId];a["userInChat"][s.chatId]=this.messenger.userInChat[s.chatId]}for(var i=0;i<this.messenger.userInChat[s.chatId].length;i++){a["users"][this.messenger.userInChat[s.chatId][i]]=this.messenger.users[this.messenger.userInChat[s.chatId][i]];a["hrphoto"][this.messenger.userInChat[s.chatId][i]]=this.messenger.hrphoto[this.messenger.userInChat[s.chatId][i]]}this.desktop.openTopmostWindow("callNotifyWaitDesktop","BXIM.webrtc.callNotifyWaitDesktop("+s.chatId+",'"+s.senderId+"', "+(s.video?1:0)+", "+(s.callToGroup?1:0)+");",a,"im-desktop-call")}}}else if(this.callInit&&this.callChatId==s.lastChatId&&s.command=="invite_user"){for(var i in s.users)this.messenger.users[i]=s.users[i];for(var i in s.hrphoto)this.messenger.hrphoto[i]=s.hrphoto[i];this.callChatId=s.chatId;this.callGroupOverlayRedraw()}else if(!this.callActive&&this.callInit&&this.callChatId==s.chatId&&s.command=="wait"){clearTimeout(this.callDialtoneTimeout);this.callDialtoneTimeout=setTimeout(BX.delegate(function(){this.BXIM.repeatSound("dialtone",5e3)},this),2e3);this.callWait(s.senderId)}else if(this.initiator&&this.callChatId==s.chatId&&s.command=="answer"){this.callDialog();if(s.isMobile){this.callToMobile=true;BX.addClass(this.callOverlay,"bx-messenger-call-overlay-mobile")}}else if(s.command=="ready"){if(this.callActive&&this.callStreamSelf==null){clearTimeout(this.callAllowTimeout);this.callAllowTimeout=setTimeout(BX.delegate(function(){this.callOverlayProgress("offline");this.callCommand(this.callChatId,"errorAccess");this.callOverlayButtons(this.buttonsOverlayClose);this.callAbort(BX.message("IM_M_CALL_ST_NO_ACCESS_3"))},this),6e4)}this.log("Apponent "+s.senderId+" ready!");this.connected[s.senderId]=true}else if(this.callActive&&this.callChatId==s.chatId&&s.command=="errorAccess"&&(!s.callToGroup||s.closeConnect)){this.callOverlayProgress("offline");this.callOverlayStatus(BX.message("IM_M_CALL_ST_NO_ACCESS_2"));this.callOverlayButtons(this.buttonsOverlayClose);this.callAbort(BX.message("IM_M_CALL_ST_NO_ACCESS_2"))}else if(this.callActive&&this.callChatId==s.chatId&&s.command=="reconnect"){clearTimeout(this.pcConnectTimeout[s.senderId]);clearTimeout(this.initPeerConnectionTimeout[s.senderId]);if(this.pc[s.senderId])this.pc[s.senderId].close();delete this.pc[s.senderId];delete this.pcStart[s.senderId];if(this.callStreamMain==this.callStreamUsers[s.senderId])this.callStreamMain=null;this.callStreamUsers[s.senderId]=null;this.initPeerConnection(s.senderId)}else if(this.callActive&&this.callChatId==s.chatId&&s.command=="signaling"){this.signalingPeerData(s.senderId,s.peer)}else if(this.callInit&&this.callChatId==s.chatId&&s.command=="waitTimeout"&&(!s.callToGroup||s.closeConnect)){this.callAbort();this.callOverlayClose()}else if(this.callInit&&this.callChatId==s.chatId&&(s.command=="busy_self"||s.command=="callToPhone")){this.callAbort();this.callOverlayClose()}else if(this.callInit&&this.callChatId==s.chatId&&s.command=="busy"&&(!s.callToGroup||s.closeConnect)){this.callOverlayProgress("offline");this.callOverlayButtons([{text:BX.message("IM_M_CALL_BTN_RECALL"),className:"bx-messenger-call-overlay-button-recall",events:{click:BX.delegate(function(){this.callInvite(s.senderId,s.video)},this)}},{text:BX.message("IM_M_CALL_BTN_HISTORY"),title:BX.message("IM_M_CALL_BTN_HISTORY_2"),showInMinimize:true,className:"bx-messenger-call-overlay-button-history",events:{click:BX.delegate(function(){this.messenger.openHistory(this.messenger.currentTab)},this)}},{text:BX.message("IM_M_CALL_BTN_CLOSE"),className:"bx-messenger-call-overlay-button-close",events:{click:BX.delegate(function(){this.callOverlayClose()},this)}}]);this.callAbort(BX.message("IM_M_CALL_ST_BUSY"))}else if(this.callInit&&this.callChatId==s.chatId&&s.command=="decline"&&(!s.callToGroup||s.closeConnect)){if(this.callInitUserId!=this.BXIM.userId||this.callActive){var n=this.callVideo;this.callOverlayStatus(BX.message("IM_M_CALL_ST_DECLINE"));this.BXIM.playSound("stop");if(n&&this.callStreamSelf!=null)this.callOverlayVideoClose();else this.callOverlayClose()}else if(this.callInitUserId==this.BXIM.userId){this.callOverlayProgress("offline");this.callOverlayButtons(this.buttonsOverlayClose);this.callAbort(BX.message("IM_M_CALL_ST_DECLINE"))}else{this.callAbort()}}else if((s.command=="decline_self"&&this.callChatId==s.chatId||s.command=="answer_self"&&!this.callActive)&&!this.callSelfDisabled){this.BXIM.stopRepeatSound("ringtone");this.BXIM.stopRepeatSound("dialtone");this.callOverlayClose(true)}else if(this.callInit&&s.callToGroup&&this.callChatId==s.chatId&&(s.command=="errorAccess"||s.command=="waitTimeout"||s.command=="busy"||s.command=="decline")){var o=this.callOverlayVideoMain.getAttribute("data-userId");if(o==s.senderId){var r=false;for(var i in this.callStreamUsers){if(i==s.senderId)continue;this.callChangeMainVideo(i);r=true;break}if(!r){this.callStreamMain=null;this.callOverlayProgress("wait");this.callOverlayStatus(BX.message(this.callToGroup?"IM_M_CALL_ST_WAIT_ACCESS_3":"IM_M_CALL_ST_WAIT_ACCESS_2"));BX.removeClass(this.callOverlay,"bx-messenger-call-overlay-call-active");BX.removeClass(e.webrtc.callOverlay,"bx-messenger-call-overlay-call-video");BX.removeClass(this.callOverlayVideoUsers[o].parentNode,"bx-messenger-call-video-block-hide")}}BX.addClass(this.callOverlayVideoUsers[s.senderId].parentNode,"bx-messenger-call-video-hide");this.connected[s.senderId]=false;this.callOverlayVideoUsers[s.senderId].src="";this.pc[s.senderId]=null;delete this.pc[s.senderId];delete this.pcStart[s.senderId];if(this.callStreamUsers[s.senderId]&&this.callStreamUsers[s.senderId].stop)this.callStreamUsers[s.senderId].stop();this.callStreamUsers[s.senderId]=null;delete this.callStreamUsers[s.senderId]}else{this.log('Command "'+s.command+'" skip (current chat: '+parseInt(this.callChatId)+"; command chat: "+parseInt(s.chatId))}}},this))}else{if(!this.BXIM.desktopStatus){this.initAudio(true);BX.addCustomEvent("onPullEvent-im",BX.delegate(function(e,t){if(t.command=="call"&&t.command=="invite"){for(var s in t.users)this.messenger.users[s]=t.users[s];for(var s in t.hrphoto)this.messenger.hrphoto[s]=t.hrphoto[s];this.callOverlayShow({toUserId:this.BXIM.userId,fromUserId:t.senderId,callToGroup:this.callToGroup,video:t.video,progress:"offline",minimize:false,status:this.desktop.ready()?BX.message("IM_M_CALL_ST_NO_WEBRTC_3"):BX.message("IM_M_CALL_ST_NO_WEBRTC_2"),buttons:[{text:BX.message("IM_M_CALL_BTN_DOWNLOAD"),className:"bx-messenger-call-overlay-button-download",events:{click:BX.delegate(function(){window.open(BX.browser.IsMac()?"http://dl.bitrix24.com/b24/bitrix24_desktop.dmg":"http://dl.bitrix24.com/b24/bitrix24_desktop.exe","desktopApp");this.callOverlayClose()},this)},hide:this.BXIM.platformName==""},{text:BX.message("IM_M_CALL_BTN_CLOSE"),className:"bx-messenger-call-overlay-button-close",events:{click:BX.delegate(function(){this.callOverlayClose()},this)}}]});this.callOverlayDeleteEvents({closeNotify:false})}},this))}}if(this.phoneEnabled&&(this.phoneDeviceActive||this.enabled)){s=true;if(this.desktop.ready()){this.phoneDisconnectAfterCallFlag=false}BX.addCustomEvent("onPullEvent-voximplant",BX.delegate(function(e,t){if(e=="invite"){if(!this.callInit&&!this.callActive&&!BX.localStorage.get("viInitedCall")){if(this.desktop.ready()||!this.desktop.ready()&&!this.BXIM.desktopStatus||this.desktop.run()&&!this.desktop.ready()&&this.BXIM.desktopStatus){if(t.CRM&&t.CRM.FOUND){this.phoneCrm=t.CRM}this.BXIM.repeatSound("ringtone",5e3);this.phoneCommand("wait",{CALL_ID:t.callId});if(this.desktop.run())BX.desktop.changeTab("im");this.phoneNotifyWait(t.chatId,t.callId,t.callerId,t.phoneNumber)}if(this.desktop.ready()&&!this.BXIM.isFocus("all")){var s={users:{},chat:{},userInChat:{},hrphoto:{},phoneCrm:t.CRM};this.desktop.openTopmostWindow("callNotifyWaitDesktop","BXIM.webrtc.phoneNotifyWaitDesktop("+t.chatId+",'"+t.callId+"', '"+t.callerId+"', '"+t.phoneNumber+"');",s,"im-desktop-call")}}}else if(e=="answer_self"){if(this.callSelfDisabled||this.phoneCallId!=t.callId)return false;this.BXIM.stopRepeatSound("ringtone");this.BXIM.stopRepeatSound("dialtone");this.callInit=false;this.phoneCallFinish();this.callAbort();this.callOverlayClose(true);this.callInit=true;this.phoneCallId=t.callId}else if(e=="timeout"){if(this.phoneCallId==t.callId){clearInterval(this.phoneConnectedInterval);BX.localStorage.remove("viInitedCall");var i=this.phoneCallExternal;this.BXIM.stopRepeatSound("ringtone");this.BXIM.stopRepeatSound("dialtone");this.callInit=false;var a=this.phoneNumber;this.phoneCallFinish();this.callAbort();if(i&&t.failedCode==486){this.callOverlayProgress("offline");this.callOverlayStatus(BX.message("IM_PHONE_ERROR_BUSY_PHONE"));this.callOverlayButtons(this.buttonsOverlayClose)}else if(i&&t.failedCode==480){this.callOverlayProgress("error");this.callOverlayStatus(BX.message("IM_PHONE_ERROR_NA_PHONE"));this.callOverlayButtons([{title:BX.message(this.phoneDeviceCall()?"IM_M_CALL_BTN_DEVICE_TITLE":"IM_M_CALL_BTN_DEVICE_OFF_TITLE"),id:"bx-messenger-call-overlay-button-device-error",className:"bx-messenger-call-overlay-button-device"+(this.phoneDeviceCall()?"":" bx-messenger-call-overlay-button-device-off"),events:{click:BX.delegate(function(){this.phoneCallFinish();this.callAbort();this.phoneDeviceCall(!this.phoneDeviceCall());this.phoneCall(a)},this)},hide:this.phoneDeviceActive&&this.enabled?false:true},{text:BX.message("IM_M_CALL_BTN_CLOSE"),className:"bx-messenger-call-overlay-button-close",events:{click:BX.delegate(function(){this.callOverlayClose()},this)}}])}else{this.callOverlayClose(false)}}}else if(e=="outgoing"){if(this.BXIM.desktopStatus&&!this.desktop.ready())return false;this.phoneCallDevice=t.callDevice=="PHONE"?"PHONE":"WEBRTC";if(this.callInit&&this.phoneNumber==t.phoneNumber){if(t.external&&this.phoneCallId==t.callIdTmp||!this.phoneCallId){this.phoneCallExternal=t.external?true:false;if(this.phoneCallExternal&&this.phoneCallDevice=="PHONE"){if(!this.phoneCallId){this.callOverlayProgress("wait");this.callOverlayStatus(BX.message("IM_M_CALL_ST_WAIT_PHONE"));if(this.desktop.ready()){BX.desktop.changeTab("im");BX.desktop.windowCommand("show");this.desktop.closeTopmostWindow()}}else{this.callOverlayProgress("connect");this.callOverlayStatus(BX.message("IM_PHONE_WAIT_ANSWER"))}}this.phoneCallId=t.callId;this.phoneCrm=t.CRM;this.callOverlayDrawCrm();if(this.callNotify)this.callNotify.adjustPosition()}}else if(!this.callInit&&this.phoneCallDevice=="PHONE"){this.phoneCallInvite(t.phoneNumber);this.phoneCallId=t.callId;this.phoneCrm=t.CRM;this.callOverlayDrawCrm();if(this.callNotify)this.callNotify.adjustPosition()}}else if(e=="start"){this.BXIM.stopRepeatSound("ringtone");if(this.phoneCallId==t.callId&&this.phoneCallDevice=="PHONE"&&this.phoneCallDevice==t.callDevice){this.phoneOnCallConnected()}else if(this.phoneCallId==t.callId&&t.callDevice=="PHONE"&&this.phoneIncoming){this.messenger.openMessenger();this.phoneCallDevice="PHONE";this.phoneOnCallConnected()}if(t.CRM){this.phoneCrm=t.CRM;this.callOverlayDrawCrm()}this.phoneNumberLast=this.phoneNumber;this.BXIM.setLocalConfig("phone_last",this.phoneNumber)}else if(e=="hold"||e=="unhold"){if(this.phoneCallId==t.callId){this.phoneHolded=e=="hold"}}else if(e=="update_crm"){if(this.phoneCallId==t.callId&&t.CRM&&t.CRM.FOUND){this.phoneCrm=t.CRM;this.callOverlayDrawCrm();if(this.callNotify)this.callNotify.adjustPosition()}}else if(e=="inviteTransfer"){if(!this.callInit&&!this.callActive){if(this.desktop.ready()||!this.desktop.ready()&&!this.BXIM.desktopStatus||this.desktop.run()&&!this.desktop.ready()&&this.BXIM.desktopStatus){if(t.CRM&&t.CRM.FOUND){this.phoneCrm=t.CRM}this.BXIM.repeatSound("ringtone",5e3);this.phoneCommand("waitTransfer",{CALL_ID:t.callId});if(this.desktop.run())BX.desktop.changeTab("im");this.phoneTransferEnabled=true;this.phoneNotifyWait(t.chatId,t.callId,t.callerId)}if(this.desktop.ready()&&!this.BXIM.isFocus("all")){var s={users:{},chat:{},userInChat:{},hrphoto:{},phoneCrm:t.CRM};this.desktop.openTopmostWindow("callNotifyWaitDesktop","BXIM.webrtc.phoneNotifyWaitDesktop("+t.chatId+",'"+t.callId+"', '"+t.callerId+"');",s,"im-desktop-call")}}}else if(e=="cancelTransfer"||e=="timeoutTransfer"){if(this.phoneCallId==t.callId&&!this.callSelfDisabled){this.callInit=false;this.BXIM.stopRepeatSound("ringtone");this.phoneCallFinish();this.callAbort();this.callOverlayClose()}}else if(e=="declineTransfer"){if(this.phoneCallId==t.callId){this.errorInviteTransfer()}}else if(e=="waitTransfer"){if(this.phoneCallId==t.callId){this.waitInviteTransfer()}}else if(e=="answerTransfer"){if(this.phoneCallId==t.callId){this.successInviteTransfer()}}else if(e=="phoneDeviceActive"){this.phoneDeviceActive=t.active=="Y"}},this))}if(s){this.initAudio();if(BX.browser.SupportLocalStorage()){BX.addCustomEvent(window,"onLocalStorageSet",BX.delegate(this.storageSet,this))}BX.garbage(function(){if(this.callInit&&!this.callActive){if(this.initiator){this.callCommand(this.callChatId,"decline",{ACTIVE:this.callActive?"Y":"N",INITIATOR:this.initiator?"Y":"N"},false);this.callAbort()}else{var e={};for(var t in this.messenger.hrphoto)e[t]=this.messenger.users[t];BX.localStorage.set("mcr2",{users:e,hrphoto:this.messenger.hrphoto,chat:this.messenger.chat,userInChat:this.messenger.userInChat,callChatId:this.callChatId,callUserId:this.callUserId,callVideo:this.callVideo,callToGroup:this.callToGroup},5)}}if(this.callActive)this.callCommand(this.callChatId,"errorAccess",{},false);this.callOverlayClose()},this)}};if(BX.inheritWebrtc)BX.inheritWebrtc(BX.IM.WebRTC);BX.IM.WebRTC.prototype.ready=function(){return this.enabled};BX.IM.WebRTC.prototype.defineButtons=function(){this.buttonsOverlayClose=[{text:BX.message("IM_M_CALL_BTN_CLOSE"),className:"bx-messenger-call-overlay-button-close",events:{click:BX.delegate(function(){this.callOverlayClose()},this)}}]};BX.IM.WebRTC.prototype.initAudio=function(e){if(e===true){this.panel.appendChild(this.BXIM.audio.error=BX.create("audio",{props:{className:"bx-messenger-audio"},children:[BX.create("source",{attrs:{src:"/bitrix/js/im/audio/video-error.ogg",type:"audio/ogg; codecs=vorbis"}}),BX.create("source",{attrs:{src:"/bitrix/js/im/audio/video-error.mp3",type:"audio/mpeg"}})]}));return false}this.panel.appendChild(this.BXIM.audio.dialtone=BX.create("audio",{props:{className:"bx-messenger-audio"},children:[BX.create("source",{attrs:{src:"/bitrix/js/im/audio/video-dialtone.ogg",type:"audio/ogg; codecs=vorbis"}}),BX.create("source",{attrs:{src:"/bitrix/js/im/audio/video-dialtone.mp3",type:"audio/mpeg"}})]}));this.panel.appendChild(this.BXIM.audio.ringtone=BX.create("audio",{props:{className:"bx-messenger-audio"},children:[BX.create("source",{attrs:{src:"/bitrix/js/im/audio/video-ringtone.ogg",type:"audio/ogg; codecs=vorbis"}}),BX.create("source",{attrs:{src:"/bitrix/js/im/audio/video-ringtone.mp3",type:"audio/mpeg"}})]}));this.panel.appendChild(this.BXIM.audio.start=BX.create("audio",{props:{className:"bx-messenger-audio"},children:[BX.create("source",{attrs:{src:"/bitrix/js/im/audio/video-start.ogg",type:"audio/ogg; codecs=vorbis"}}),BX.create("source",{attrs:{src:"/bitrix/js/im/audio/video-start.mp3",type:"audio/mpeg"}})]}));this.panel.appendChild(this.BXIM.audio.stop=BX.create("audio",{props:{className:"bx-messenger-audio"},children:[BX.create("source",{attrs:{src:"/bitrix/js/im/audio/video-stop.ogg",type:"audio/ogg; codecs=vorbis"}}),BX.create("source",{attrs:{src:"/bitrix/js/im/audio/video-stop.mp3",type:"audio/mpeg"}})]}));this.panel.appendChild(this.BXIM.audio.error=BX.create("audio",{props:{className:"bx-messenger-audio"},children:[BX.create("source",{attrs:{src:"/bitrix/js/im/audio/video-error.ogg",type:"audio/ogg; codecs=vorbis"}}),BX.create("source",{attrs:{src:"/bitrix/js/im/audio/video-error.mp3",type:"audio/mpeg"}})]}));if(typeof this.BXIM.audio.stop.play=="undefined"){this.BXIM.settings.enableSound=false}};BX.IM.WebRTC.prototype.startGetUserMedia=function(e,t){clearTimeout(this.callDialtoneTimeout);this.BXIM.stopRepeatSound("ringtone");this.BXIM.stopRepeatSound("dialtone");var s=true;clearTimeout(this.callInviteTimeout);clearTimeout(this.callDialogAllowTimeout);if(s){this.callDialogAllowTimeout=setTimeout(BX.delegate(function(){this.callDialogAllowShow()},this),1500)}this.parent.startGetUserMedia.apply(this,arguments)};BX.IM.WebRTC.prototype.onUserMediaSuccess=function(e){clearTimeout(this.callAllowTimeout);var t=this.parent.onUserMediaSuccess.apply(this,arguments);if(!t)return false;this.callOverlayProgress("online");this.callOverlayStatus(BX.message(this.callToGroup?"IM_M_CALL_ST_WAIT_ACCESS_3":"IM_M_CALL_ST_WAIT_ACCESS_2"));if(this.callDialogAllow)this.callDialogAllow.close();this.attachMediaStream(this.callOverlayVideoSelf,this.callStreamSelf);this.callOverlayVideoSelf.muted=true;if(this.callToGroup&&this.callVideo){BX.addClass(this.callOverlay,"bx-messenger-call-overlay-call-active");BX.addClass(this.callOverlay,"bx-messenger-call-overlay-call-video")}setTimeout(BX.delegate(function(){if(!this.callActive)return false;BX.addClass(this.callOverlay,"bx-messenger-call-overlay-ready")},this),500);this.callCommand(this.callChatId,"ready")};BX.IM.WebRTC.prototype.onUserMediaError=function(e){clearTimeout(this.callAllowTimeout);var t=this.parent.onUserMediaError.apply(this,arguments);if(!t)return false;if(this.callDialogAllow)this.callDialogAllow.close();if(e&&e.name=="ConstraintNotSatisfiedError"){this.startGetUserMedia(this.lastUserMediaParams["video"],this.lastUserMediaParams["audio"])}else{this.callOverlayProgress("offline");this.callCommand(this.callChatId,"errorAccess");this.callAbort(BX.message("IM_M_CALL_ST_NO_ACCESS"));this.callOverlayButtons(this.buttonsOverlayClose)}};BX.IM.WebRTC.prototype.setLocalAndSend=function(e,t){var s=this.parent.setLocalAndSend.apply(this,arguments);if(!s)return false;BX.ajax({url:this.BXIM.pathToCallAjax+"?CALL_SIGNALING&V="+this.BXIM.revision,method:"POST",dataType:"json",timeout:30,data:{IM_CALL:"Y",COMMAND:"signaling",CHAT_ID:this.callChatId,RECIPIENT_ID:e,PEER:JSON.stringify(t),IM_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()}});return true};BX.IM.WebRTC.prototype.onRemoteStreamAdded=function(e,t,s){if(s){this.attachMediaStream(this.callOverlayVideoMain,this.callStreamMain);if(this.desktop.ready())BX.desktop.onCustomEvent("bxCallChangeMainVideo",[this.callOverlayVideoMain.src]);if(!this.BXIM.windowFocus)this.desktop.openCallFloatDialog();this.callOverlayVideoMain.setAttribute("data-userId",e);this.callOverlayVideoMain.muted=false;this.callOverlayVideoMain.volume=1;BX("bx-messenger-call-overlay-button-plus").style.display="inline-block";this.callOverlayStatus(BX.message("IM_M_CALL_ST_ONLINE"));BX.addClass(this.callOverlay,"bx-messenger-call-overlay-online");BX.addClass(this.callOverlay,"bx-messenger-call-overlay-call-active");if(this.callVideo)BX.addClass(this.callOverlay,"bx-messenger-call-overlay-call-video");clearInterval(this.callAspectCheckInterval);this.callAspectCheckInterval=setInterval(BX.delegate(function(){if(this.callOverlayVideoMain.offsetWidth<this.callOverlayVideoMain.offsetHeight){if(this.callAspectHorizontal){this.callAspectHorizontal=false;BX.addClass(this.callOverlay,"bx-messenger-call-overlay-aspect-vertical")}}else{if(!this.callAspectHorizontal){this.callAspectHorizontal=true;BX.removeClass(this.callOverlay,"bx-messenger-call-overlay-aspect-vertical")}}},this),500)}if(this.callToGroup){if(!s){this.attachMediaStream(this.callOverlayVideoUsers[e],this.callStreamUsers[e]);BX.removeClass(this.callOverlayVideoUsers[e].parentNode,"bx-messenger-call-video-hide")}else{BX.addClass(this.callOverlayVideoUsers[e].parentNode,"bx-messenger-call-video-block-hide")}}if(this.initiator)this.callCommand(this.callChatId,"start",{CALL_TO_GROUP:this.callToGroup?"Y":"N",RECIPIENT_ID:e})};BX.IM.WebRTC.prototype.onRemoteStreamRemoved=function(e,t){clearInterval(this.callAspectCheckInterval);BX.removeClass(this.callOverlay,"bx-messenger-call-overlay-aspect-vertical");BX.removeClass(this.callOverlay,"bx-messenger-call-overlay-online")};BX.IM.WebRTC.prototype.onIceCandidate=function(e,t){BX.ajax({url:this.BXIM.pathToCallAjax+"?CALL_SIGNALING&V="+this.BXIM.revision,method:"POST",dataType:"json",timeout:30,data:{IM_CALL:"Y",COMMAND:"signaling",CHAT_ID:this.callChatId,RECIPIENT_ID:e,PEER:JSON.stringify(t),IM_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()}})};BX.IM.WebRTC.prototype.peerConnectionError=function(e,t){if(this.callDialogAllow)this.callDialogAllow.close();this.callOverlayProgress("offline");this.callCommand(this.callChatId,"errorAccess");this.callAbort(BX.message("IM_M_CALL_ST_CON_ERROR"));this.callOverlayButtons(this.buttonsOverlayClose)};BX.IM.WebRTC.prototype.peerConnectionGetStats=function(){if(this.detectedBrowser!="chrome")return false;if(this.callUserId<=0||!this.pc[this.callUserId]||!this.pc[this.callUserId].getStats||this.callToGroup||this.callToPhone)return false;this.pc[this.callUserId].getStats(function(e){console.log(e)})};BX.IM.WebRTC.prototype.peerConnectionReconnect=function(e){var t=this.parent.peerConnectionReconnect.apply(this,arguments);if(!t)return false;BX.ajax({url:this.BXIM.pathToCallAjax+"?CALL_RECONNECT&V="+this.BXIM.revision,method:"POST",dataType:"json",timeout:30,data:{IM_CALL:"Y",COMMAND:"reconnect",CHAT_ID:this.callChatId,RECIPIENT_ID:e,IM_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()},onsuccess:BX.delegate(function(){this.initPeerConnection(e,true)},this)});return true};BX.IM.WebRTC.prototype.callSupport=function(e,t){t=t?t:this.messenger;var s=true;if(typeof e!="undefined"){if(parseInt(e)>0)s=t.users[e]&&t.users[e].status!="guest";else s=t.userInChat[e.toString().substr(4)]&&t.userInChat[e.toString().substr(4)].length<=4}return this.BXIM.ppServerStatus&&this.enabled&&s};BX.IM.WebRTC.prototype.callInvite=function(e,t,s){if(BX.localStorage.get("viInitedCall"))return false;if(this.desktop.run()&&BX.desktop.currentTab!="im"){BX.desktop.changeTab("im")}if(!this.callSupport()){if(!this.desktop.ready()){this.BXIM.openConfirm(BX.message("IM_CALL_NO_WEBRT"),[this.BXIM.platformName==""?null:new BX.PopupWindowButton({text:BX.message("IM_M_CALL_BTN_DOWNLOAD"),className:"popup-window-button-accept",events:{click:BX.delegate(function(){window.open(BX.browser.IsMac()?"http://dl.bitrix24.com/b24/bitrix24_desktop.dmg":"http://dl.bitrix24.com/b24/bitrix24_desktop.exe","desktopApp");BX.proxy_context.popupWindow.close()},this)}}),new BX.PopupWindowButton({text:BX.message("IM_NOTIFY_CONFIRM_CLOSE"),className:"popup-window-button-decline",events:{click:function(){this.popupWindow.close()}}})])}return false}var i=false;if(parseInt(e)>0){if(this.messenger.users[e]&&this.messenger.users[e].status=="guest"){this.BXIM.openConfirm(BX.message("IM_CALL_USER_OFFLINE"));return false}else if(!this.messenger.users[e]){BX.MessengerCommon.getUserParam(e)}e=parseInt(e)}else{e=e.toString().substr(4);if(!this.messenger.userInChat[e]||this.messenger.userInChat[e].length<=1){return false}else if(!this.messenger.userInChat[e]||this.messenger.userInChat[e].length>4){this.BXIM.openConfirm(BX.message("IM_CALL_CHAT_LARGE"));return false}i=true}t=t==true;s=t===true&&s===true;if(!this.callActive&&!this.callInit&&e>0){this.initiator=true;this.callInitUserId=this.BXIM.userId;this.callInit=true;this.callActive=false;this.callUserId=i?0:e;this.callChatId=i?e:0;this.callToGroup=i;this.callGroupUsers=i?this.messenger.userInChat[e]:[];this.callVideo=t;this.callOverlayShow({toUserId:e,fromUserId:this.BXIM.userId,callToGroup:this.callToGroup,video:t,status:BX.message("IM_M_CALL_ST_CONNECT"),buttons:[{text:BX.message("IM_M_CALL_BTN_HANGUP"),className:"bx-messenger-call-overlay-button-hangup",events:{click:BX.delegate(function(){this.callSelfDisabled=true;this.callCommand(this.callChatId,"decline",{ACTIVE:this.callActive?"Y":"N",INITIATOR:this.initiator?"Y":"N"});this.callAbort();this.callOverlayClose()},this)}},{text:BX.message("IM_M_CALL_BTN_CHAT"),className:"bx-messenger-call-overlay-button-chat",showInMaximize:true,events:{click:BX.delegate(this.callOverlayToggleSize,this)}},{title:BX.message("IM_M_CALL_BTN_MAXI"),className:"bx-messenger-call-overlay-button-maxi",showInMinimize:true,events:{click:BX.delegate(this.callOverlayToggleSize,this)}}]});this.BXIM.playSound("start");BX.ajax({url:this.BXIM.pathToCallAjax+"?CALL_INVITE&V="+this.BXIM.revision,method:"POST",dataType:"json",timeout:30,data:{IM_CALL:"Y",COMMAND:"invite",CHAT_ID:e,CHAT:i?"Y":"N",VIDEO:t?"Y":"N",IM_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()},onsuccess:BX.delegate(function(e){if(e.ERROR==""){this.callChatId=e.CHAT_ID;for(var t in e.USERS)this.messenger.users[t]=e.USERS[t];for(var t in e.HR_PHOTO)this.messenger.hrphoto[t]=e.HR_PHOTO[t];if(e.CALL_ENABLED&&this.callToGroup){for(var t in e.USERS_CONNECT){this.connected[t]=true}this.initiator=false;this.callInitUserId=0;this.callInit=true;this.callActive=false;this.callUserId=0;this.callChatId=e.CHAT_ID;this.callToGroup=e.CALL_TO_GROUP;this.callGroupUsers=this.messenger.userInChat[e.CHAT_ID];this.callVideo=e.CALL_VIDEO;this.callDialog();return false}this.callOverlayUpdatePhoto();var s=this.callToGroup?"chat"+this.callChatId:this.callUserId;var i=this.callToGroup;var a=this.callVideo;this.callInviteTimeout=setTimeout(BX.delegate(function(){this.callOverlayProgress("offline");this.callOverlayButtons([{text:BX.message("IM_M_CALL_BTN_RECALL"),className:"bx-messenger-call-overlay-button-recall",events:{click:BX.delegate(function(e){if(this.phoneCount(this.messenger.phones[s])>0){this.messenger.openPopupMenu(BX.proxy_context,"callPhoneMenu",true,{userId:s,video:a})}else{this.callInvite(s,a)}BX.PreventDefault(e)},this)},hide:i},{text:BX.message("IM_M_CALL_BTN_CLOSE"),className:"bx-messenger-call-overlay-button-close",events:{click:BX.delegate(function(){this.callOverlayClose()},this)}}]);this.callCommand(this.callChatId,"errorOffline");this.callAbort(BX.message(i?"IM_M_CALL_ST_NO_WEBRTC_1":"IM_M_CALL_ST_NO_WEBRTC"))},this),3e4)}else{this.callOverlayProgress("offline");this.callCommand(this.callChatId,"errorOffline");this.callOverlayButtons(this.buttonsOverlayClose);this.callAbort(e.ERROR)}},this),onfailure:BX.delegate(function(){this.callAbort(BX.message("IM_M_CALL_ERR"));this.callOverlayClose()},this)})}};BX.IM.WebRTC.prototype.callWait=function(){if(!this.callSupport())return false;this.callOverlayStatus(BX.message(this.callToGroup?"IM_M_CALL_ST_WAIT_2":"IM_M_CALL_ST_WAIT"));clearTimeout(this.callInviteTimeout);this.callInviteTimeout=setTimeout(BX.delegate(function(){if(!this.initiator){this.callAbort();this.callOverlayClose();return false}this.callOverlayProgress("offline");var e=this.callToGroup?"chat"+this.callChatId:this.callUserId;var t=this.callVideo;var s=this.callToGroup;this.callOverlayButtons([{text:BX.message("IM_M_CALL_BTN_RECALL"),className:"bx-messenger-call-overlay-button-recall",events:{click:BX.delegate(function(s){if(this.phoneCount(this.messenger.phones[e])>0){this.messenger.openPopupMenu(BX.proxy_context,"callPhoneMenu",true,{userId:e,video:t})}else{this.callInvite(e,t)}BX.PreventDefault(s)},this)},hide:s},{text:BX.message("IM_M_CALL_BTN_CLOSE"),className:"bx-messenger-call-overlay-button-close",events:{click:BX.delegate(function(){this.callOverlayClose()},this)}}]);this.callCommand(this.callChatId,"waitTimeout");this.callAbort(BX.message(this.callToGroup?"IM_M_CALL_ST_NO_ANSWER_2":"IM_M_CALL_ST_NO_ANSWER"))},this),2e4)};BX.IM.WebRTC.prototype.callChangeMainVideo=function(e){var t=this.callOverlayVideoMain.getAttribute("data-userId");if(t==e||!this.callStreamUsers[e])return false;BX.addClass(this.callOverlayVideoMain,"bx-messenger-call-video-main-block-animation");clearTimeout(this.callChangeMainVideoTimeout);this.callChangeMainVideoTimeout=setTimeout(BX.delegate(function(){this.callOverlayVideoMain.setAttribute("data-userId",e);this.attachMediaStream(this.callOverlayVideoMain,this.callStreamUsers[e]);if(this.desktop.ready())BX.desktop.onCustomEvent("bxCallChangeMainVideo",[this.callOverlayVideoMain.src]);BX.addClass(this.callOverlayVideoUsers[e].parentNode,"bx-messenger-call-video-block-hide");BX.addClass(this.callOverlayVideoUsers[e].parentNode,"bx-messenger-call-video-hide");this.callOverlayVideoUsers[e].parentNode.setAttribute("title","");if(this.callStreamUsers[t]){this.attachMediaStream(this.callOverlayVideoUsers[t],this.callStreamUsers[t]);BX.removeClass(this.callOverlayVideoUsers[t].parentNode,"bx-messenger-call-video-hide")}this.callOverlayVideoUsers[t].parentNode.setAttribute("title",BX.message("IM_CALL_MAGNIFY"));BX.removeClass(this.callOverlayVideoUsers[t].parentNode,"bx-messenger-call-video-block-hide");BX.removeClass(this.callOverlayVideoMain,"bx-messenger-call-video-main-block-animation")},this),400)};BX.IM.WebRTC.prototype.callInviteUserToChat=function(e){if(this.callChatId<=0||this.messenger.popupChatDialogSendBlock)return false;var t="";if(e.length==0){if(this.messenger.popupChatDialog!=null)this.messenger.popupChatDialog.close();return false}if(t!=""){this.BXIM.openConfirm(t);return false}if(this.screenSharing.callInit){this.screenSharing.callDecline()}this.messenger.popupChatDialogSendBlock=true;if(this.messenger.popupChatDialog!=null)this.messenger.popupChatDialog.buttons[0].setClassName("popup-window-button-disable");BX.ajax({url:this.BXIM.pathToCallAjax+"?CALL_INVITE_USER&V="+this.BXIM.revision,method:"POST",dataType:"json",timeout:60,data:{IM_CALL:"Y",COMMAND:"invite_user",USERS:JSON.stringify(e),CHAT_ID:this.callChatId,RECIPIENT_ID:this.callUserId,IM_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()},onsuccess:BX.delegate(function(e){this.messenger.popupChatDialogSendBlock=false;if(this.messenger.popupChatDialog!=null)this.messenger.popupChatDialog.buttons[0].setClassName("popup-window-button-accept");if(e.ERROR==""){this.messenger.popupChatDialogSendBlock=false;if(this.messenger.popupChatDialog!=null)this.messenger.popupChatDialog.close()}else{this.BXIM.openConfirm(e.ERROR)}},this)})};BX.IM.WebRTC.prototype.callCommand=function(e,t,s,i){if(!this.callSupport())return false;e=parseInt(e);i=i!=false;s=typeof s=="object"?s:{};if(e>0){BX.ajax({url:this.BXIM.pathToCallAjax+"?CALL_SHARED&V="+this.BXIM.revision,method:"POST",dataType:"json",timeout:30,async:i,data:{IM_CALL:"Y",COMMAND:t,CHAT_ID:e,RECIPIENT_ID:this.callUserId,PARAMS:JSON.stringify(s),IM_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()},onsuccess:BX.delegate(function(){if(this.callDialogAllow)this.callDialogAllow.close()},this)})}};BX.IM.WebRTC.prototype.getHrPhoto=function(e){var t="";if(e=="phone")t="/bitrix/js/im/images/hidef-phone-v2.png";else if(this.messenger.hrphoto[e])t=this.messenger.hrphoto[e];else if(!this.messenger.users[e]||this.messenger.users[e].avatar==this.BXIM.pathToBlankImage)t="/bitrix/js/im/images/hidef-avatar-v2.png";else t=this.messenger.users[e].avatar;return t};BX.IM.WebRTC.prototype.callDialog=function(){if(!this.callSupport()&&this.callOverlay==null)return false;clearTimeout(this.callInviteTimeout);clearTimeout(this.callDialogAllowTimeout);if(this.callDialogAllow)this.callDialogAllow.close();this.callActive=true;this.callOverlayProgress("wait");this.callOverlayStatus(BX.message("IM_M_CALL_ST_WAIT_ACCESS"));this.callOverlayButtons([{text:BX.message("IM_M_CALL_BTN_HANGUP"),className:"bx-messenger-call-overlay-button-hangup",events:{click:BX.delegate(function(){var e=this.callVideo;this.callSelfDisabled=true;this.callCommand(this.callChatId,"decline",{ACTIVE:this.callActive?"Y":"N",INITIATOR:this.initiator?"Y":"N"});this.BXIM.playSound("stop");if(e&&this.callStreamSelf!=null)this.callOverlayVideoClose();else this.callOverlayClose()},this)}},{title:BX.message("IM_M_CHAT_TITLE"),
className:"bx-messenger-call-overlay-button-plus",events:{click:BX.delegate(function(e){if(this.messenger.userInChat[this.callChatId]&&this.messenger.userInChat[this.callChatId].length==4){this.BXIM.openConfirm(BX.message("IM_CALL_GROUP_MAX_USERS"));return false}this.messenger.openChatDialog({chatId:this.callChatId,type:"CALL_INVITE_USER",bind:BX.proxy_context,maxUsers:4});BX.PreventDefault(e)},this)},hide:true},{title:BX.message("IM_M_CALL_BTN_MIC_TITLE"),id:"bx-messenger-call-overlay-button-mic",className:"bx-messenger-call-overlay-button-mic "+(this.audioMuted?" bx-messenger-call-overlay-button-mic-off":""),events:{click:BX.delegate(function(){this.toggleAudio();var e=BX.findChildByClassName(BX.proxy_context,"bx-messenger-call-overlay-button-mic");if(e)BX.toggleClass(e,"bx-messenger-call-overlay-button-mic-off")},this)}},{title:BX.message("IM_M_CALL_BTN_SCREEN_TITLE"),id:"bx-messenger-call-overlay-button-screen",className:"bx-messenger-call-overlay-button-screen "+(this.screenSharing.connect?" bx-messenger-call-overlay-button-screen-off":""),events:{click:BX.delegate(function(){if(!this.desktop.enableInVersion(30)){this.BXIM.openConfirm({title:BX.message("IM_M_CALL_SCREEN"),message:BX.message("IM_M_CALL_SCREEN_ERROR")});return false}this.toggleScreenSharing()},this)}},{title:BX.message("IM_M_CALL_BTN_HISTORY_2"),className:"bx-messenger-call-overlay-button-history2",events:{click:BX.delegate(function(){this.messenger.openHistory(this.messenger.currentTab)},this)}},{title:BX.message("IM_M_CALL_BTN_CHAT_2"),className:"bx-messenger-call-overlay-button-chat2",showInMaximize:true,events:{click:BX.delegate(this.callOverlayToggleSize,this)}},{title:BX.message("IM_M_CALL_BTN_MAXI"),className:"bx-messenger-call-overlay-button-maxi",showInMinimize:true,events:{click:BX.delegate(this.callOverlayToggleSize,this)}},{title:BX.message("IM_M_CALL_BTN_FULL"),className:"bx-messenger-call-overlay-button-full",events:{click:BX.delegate(this.overlayEnterFullScreen,this)},hide:!this.callVideo||this.desktop.ready()}]);if(this.messenger.popupMessenger==null){this.messenger.openMessenger(this.callUserId);this.callOverlayToggleSize(false)}BX.addClass(this.callOverlay,"bx-messenger-call-overlay-maxi");BX.addClass(this.messenger.popupMessengerContent,"bx-messenger-call-maxi");BX.removeClass(this.callOverlay,"bx-messenger-call-overlay-mini");BX.removeClass(this.callOverlay,"bx-messenger-call-overlay-line");BX.addClass(this.callOverlay,"bx-messenger-call-overlay-call");if(!this.callToGroup&&this.callVideo||!this.callVideo){BX.addClass(this.callOverlay,"bx-messenger-call-overlay-call-"+(this.callVideo?"video":"audio"))}this.startGetUserMedia(this.callVideo)};BX.IM.WebRTC.prototype.toggleScreenSharing=function(){if(this.screenSharing.callInit&&this.screenSharing.initiator){this.screenSharing.callDecline()}else{this.screenSharing.callInvite()}return true};BX.IM.WebRTC.prototype.callOverlayShow=function(e){if(!e||!(e.toUserId||e.phoneNumber)||!(e.fromUserId||e.phoneNumber)||!e.buttons)return false;if(this.callOverlay!=null){this.callOverlayClose(false,true)}this.messenger.closeMenuPopup();e.video=e.video!=false;e.callToGroup=e.callToGroup==true;e.callToPhone=e.callToPhone==true;e.minimize=typeof e.minimize=="undefined"?this.messenger.popupMessenger==null:e.minimize==true;e.status=e.status?e.status:"";e.progress=e.progress?e.progress:"connect";this.callOldBeforeUnload=window.onbeforeunload;if(!e.prepare){window.onbeforeunload=function(){if(typeof BX.PULL!="undefined"&&typeof BX.PULL.tryConnectDelay=="function"){BX.PULL.tryConnectDelay()}return BX.message("IM_M_CALL_EFP")}}this.callOverlayMinimize=e.prepare?true:e.minimize;var t=null;if(this.BXIM.dialogOpen)t=this.messenger.popupMessengerBody;else if(this.BXIM.notifyOpen)t=this.messenger.popupNotifyItem;if(t){if(BX.MessengerCommon.isScrollMin(t)){setTimeout(BX.delegate(function(){BX.addClass(this.messenger.popupMessengerContent,"bx-messenger-call")},this),e.minimize?0:400)}else{BX.addClass(this.messenger.popupMessengerContent,"bx-messenger-call");t.scrollTop=t.scrollTop+50}}else{BX.addClass(this.messenger.popupMessengerContent,"bx-messenger-call")}if(!this.callOverlayMinimize)BX.addClass(this.messenger.popupMessengerContent,"bx-messenger-call-maxi");var s={width:!this.messenger.popupMessenger?"610px":(this.messenger.popupMessengerExtra.style.display=="block"?this.messenger.popupMessengerExtra.offsetWidth-1:this.messenger.popupMessengerDialog.offsetWidth-1)+"px",height:this.messenger.popupMessengerFullHeight-1+"px",marginLeft:this.messenger.popupContactListSize+"px"};if(e.phoneNumber){var i=this.callPhoneOverlayShow(e)}else{var i=e.callToGroup?this.callGroupOverlayShow(e):this.callUserOverlayShow(e)}this.callOverlay=BX.create("div",{props:{className:"bx-messenger-call-overlay "+(e.callToGroup?" bx-messenger-call-overlay-group ":"")+(this.callOverlayMinimize?"bx-messenger-call-overlay-mini":"bx-messenger-call-overlay-maxi")},style:s,children:[BX.create("div",{props:{className:"bx-messenger-call-overlay-lvl-1"},children:[BX.create("div",{props:{className:"bx-messenger-call-overlay-lvl-2"},children:[BX.create("div",{props:{className:"bx-messenger-call-video-main"},children:[BX.create("div",{props:{className:"bx-messenger-call-video-main-wrap"},children:[BX.create("div",{props:{className:"bx-messenger-call-video-main-watermark"},children:[BX.create("img",{props:{className:"bx-messenger-call-video-main-watermark-img"},attrs:{src:"/bitrix/js/im/images/watermark_"+(this.BXIM.language=="ru"?"ru":"en")+".png"}})]}),BX.create("div",{props:{className:"bx-messenger-call-video-main-cell"},children:[BX.create("div",{props:{className:"bx-messenger-call-video-main-bg"},children:[this.callOverlayVideoMain=BX.create("video",{attrs:{autoplay:true},props:{className:"bx-messenger-call-video-main-block"}}),this.callOverlayVideoReserve=BX.create("video",{attrs:{autoplay:true},props:{className:"bx-messenger-hide"}})]})]})]})]})]})]}),this.callOverlayBody=BX.create("div",{props:{className:"bx-messenger-call-overlay-body"},children:i})]});if(e.prepare){BX.addClass(this.callOverlay,"bx-messenger-call-overlay-float");BX.addClass(this.callOverlay,"bx-messenger-call-overlay-show")}else if(this.messenger.popupMessenger!=null){this.messenger.setClosingByEsc(false);BX.addClass(BX("bx-messenger-popup-messenger"),"bx-messenger-popup-messenger-dont-close");this.messenger.popupMessengerContent.insertBefore(this.callOverlay,this.messenger.popupMessengerContent.firstChild)}else if(this.callNotify!=null){BX.addClass(this.callOverlay,"bx-messenger-call-overlay-float");this.callNotify.setContent(this.callOverlay)}else{this.callNotify=new BX.PopupWindow("bx-messenger-call-notify",null,{lightShadow:true,zIndex:200,events:{onPopupClose:function(){this.destroy()},onPopupDestroy:BX.delegate(function(){BX.unbind(window,"scroll",this.popupCallNotifyEvent);this.callNotify=null},this)},content:this.callOverlay});this.callNotify.show();BX.addClass(this.callOverlay,"bx-messenger-call-overlay-float");BX.addClass(this.callOverlay,"bx-messenger-call-overlay-show");BX.addClass(this.callNotify.popupContainer.children[0],"bx-messenger-popup-window-transparent");setTimeout(BX.delegate(function(){if(this.callNotify){this.callNotify.adjustPosition()}},this),500);BX.bind(window,"scroll",this.popupCallNotifyEvent=BX.proxy(function(){this.callNotify.adjustPosition()},this))}setTimeout(BX.delegate(function(){BX.addClass(this.callOverlay,"bx-messenger-call-overlay-show")},this),100);this.callOverlayStatus(e.status);this.callOverlayButtons(e.buttons);this.callOverlayProgress(e.progress);return true};BX.IM.WebRTC.prototype.callGroupOverlayShow=function(e){this.callOverlayOptions=e;var t=e.fromUserId!=this.BXIM.userId;var s=e.fromUserId!=this.BXIM.userId?e.fromUserId:e.toUserId;var i=this.callOverlayTitle();this.callOverlayChatId=s;var a=[];var n=[];for(var o=0;o<this.messenger.userInChat[s].length;o++){var r=this.messenger.userInChat[s][o];a.push(BX.create("div",{props:{className:"bx-messenger-call-overlay-photo-left"},children:[BX.create("div",{props:{className:"bx-messenger-call-overlay-photo-block"},children:[this.callOverlayPhotoUsers[r]=BX.create("img",{props:{className:"bx-messenger-call-overlay-photo-img"},attrs:{"data-userId":r,src:this.getHrPhoto(r)}})]})]}));if(r==this.BXIM.userId)continue;n.push(BX.create("div",{props:{className:"bx-messenger-call-video-mini bx-messenger-call-video-hide"},attrs:{"data-userId":r},events:{click:BX.delegate(function(){this.callChangeMainVideo(BX.proxy_context.getAttribute("data-userId"))},this)},children:[this.callOverlayVideoUsers[r]=BX.create("video",{attrs:{autoplay:true},props:{className:"bx-messenger-call-video-mini-block"}}),BX.create("div",{props:{className:"bx-messenger-call-video-mini-photo"},children:[this.callOverlayVideoPhotoUsers[r]=BX.create("img",{props:{className:"bx-messenger-call-video-mini-photo-img"},attrs:{src:this.getHrPhoto(r)}})]})]}))}return[BX.create("div",{props:{className:"bx-messenger-call-overlay-line-maxi"},attrs:{title:BX.message("IM_M_CALL_BTN_RETURN")},children:[BX.create("div",{props:{className:"bx-messenger-call-overlay-line-maxi-block"}})]}),BX.create("div",{props:{className:"bx-messenger-call-video-users"},children:n}),BX.create("div",{props:{className:"bx-messenger-call-overlay-title"},children:[this.callOverlayTitleBlock=BX.create("div",{props:{className:"bx-messenger-call-overlay-title-block"},html:i})]}),BX.create("div",{props:{className:"bx-messenger-call-overlay-photo"},children:a}),BX.create("div",{props:{className:"bx-messenger-call-overlay-photo-progress-group"},children:[this.callOverlayProgressBlock=BX.create("div",{props:{className:"bx-messenger-call-overlay-photo-progress"}})]}),BX.create("div",{props:{className:"bx-messenger-call-overlay-status"},children:[this.callOverlayStatusBlock=BX.create("div",{props:{className:"bx-messenger-call-overlay-status-block"}})]}),BX.create("div",{props:{className:"bx-messenger-call-video-mini"},children:[this.callOverlayVideoSelf=BX.create("video",{attrs:{autoplay:true},props:{className:"bx-messenger-call-video-mini-block"}}),BX.create("div",{props:{className:"bx-messenger-call-video-mini-photo"},children:[this.callOverlayPhotoMini=BX.create("img",{props:{className:"bx-messenger-call-video-mini-photo-img"},attrs:{src:this.getHrPhoto(this.BXIM.userId)}})]})]}),BX.create("div",{props:{className:"bx-messenger-call-overlay-buttons"},children:[this.callOverlayButtonsBlock=BX.create("div",{props:{className:"bx-messenger-call-overlay-buttons-block"}})]})]};BX.IM.WebRTC.prototype.callUserOverlayShow=function(e){this.callOverlayOptions=e;var t=e.toUserId==this.BXIM.userId;var s=t?e.fromUserId:e.toUserId;var i=this.callOverlayTitle();this.callOverlayUserId=s;return[BX.create("div",{props:{className:"bx-messenger-call-overlay-line-maxi"},attrs:{title:BX.message("IM_M_CALL_BTN_RETURN")},children:[BX.create("div",{props:{className:"bx-messenger-call-overlay-line-maxi-block"}})]}),BX.create("div",{props:{className:"bx-messenger-call-overlay-title"},children:[this.callOverlayTitleBlock=BX.create("div",{props:{className:"bx-messenger-call-overlay-title-block"},html:i})]}),BX.create("div",{props:{className:"bx-messenger-call-overlay-photo"},children:[BX.create("div",{props:{className:"bx-messenger-call-overlay-photo-left"},children:[BX.create("div",{props:{className:"bx-messenger-call-overlay-photo-block"},children:[this.callOverlayPhotoCompanion=BX.create("img",{props:{className:"bx-messenger-call-overlay-photo-img"},attrs:{"data-userId":s,src:this.getHrPhoto(s)}})]})]}),this.callOverlayProgressBlock=BX.create("div",{props:{className:"bx-messenger-call-overlay-photo-progress"+(t?"":" bx-messenger-call-overlay-photo-progress-incoming")}}),BX.create("div",{props:{className:"bx-messenger-call-overlay-photo-right"},children:[BX.create("div",{props:{className:"bx-messenger-call-overlay-photo-block"},children:[this.callOverlayPhotoSelf=BX.create("img",{props:{className:"bx-messenger-call-overlay-photo-img"},attrs:{"data-userId":this.BXIM.userId,src:this.getHrPhoto(this.BXIM.userId)}})]})]})]}),BX.create("div",{props:{className:"bx-messenger-call-overlay-status"},children:[this.callOverlayStatusBlock=BX.create("div",{props:{className:"bx-messenger-call-overlay-status-block"}})]}),BX.create("div",{props:{className:"bx-messenger-call-video-mini"},children:[this.callOverlayVideoSelf=BX.create("video",{attrs:{autoplay:true},props:{className:"bx-messenger-call-video-mini-block"}}),BX.create("div",{props:{className:"bx-messenger-call-video-mini-photo"},children:[this.callOverlayPhotoMini=BX.create("img",{props:{className:"bx-messenger-call-video-mini-photo-img"},attrs:{src:this.getHrPhoto(this.BXIM.userId)}})]})]}),BX.create("div",{props:{className:"bx-messenger-call-overlay-buttons"},children:[this.callOverlayButtonsBlock=BX.create("div",{props:{className:"bx-messenger-call-overlay-buttons-block"}})]})]};BX.IM.WebRTC.prototype.callPhoneOverlayShow=function(e){this.callOverlayOptions=e;var t=e.toUserId==this.BXIM.userId;var s=t?e.fromUserId:e.toUserId;this.callToPhone=true;var i="";if(e.callTitle){i=e.phoneNumber=="hidden"?BX.message("IM_PHONE_HIDDEN_NUMBER"):e.callTitle}else{i=e.phoneNumber=="hidden"?BX.message("IM_PHONE_HIDDEN_NUMBER"):"+"+e.phoneNumber}if(this.phoneTransferEnabled){i=BX.message("IM_PHONE_CALL_TRANSFER").replace("#PHONE#",i)}else{i=BX.message(t?"IM_PHONE_CALL_VOICE_FROM":"IM_PHONE_CALL_VOICE_TO").replace("#PHONE#",i)}var a=t&&e.companyPhoneNumber?'<span class="bx-messenger-call-overlay-title-company-phone">'+BX.message("IM_PHONE_CALL_TO_PHONE").replace("#PHONE#",e.companyPhoneNumber)+"</span>":"";this.callOverlayUserId=s;return[this.callOverlayMeterGrade=BX.create("div",{attrs:{title:BX.message("IM_PHONE_GRADE")+" "+BX.message("IM_PHONE_GRADE_4")},props:{className:"bx-messenger-call-overlay-meter bx-messenger-call-overlay-meter-grade-5"},children:[BX.create("div",{props:{className:"bx-messenger-call-overlay-meter-grade"}}),this.callOverlayMeterPercent=BX.create("div",{props:{className:"bx-messenger-call-overlay-meter-percent"},html:100})]}),BX.create("div",{props:{className:"bx-messenger-call-overlay-line-maxi"},attrs:{title:BX.message("IM_M_CALL_BTN_RETURN")},children:[BX.create("div",{props:{className:"bx-messenger-call-overlay-line-maxi-block"}})]}),BX.create("div",{props:{className:"bx-messenger-call-overlay-title"},children:[this.callOverlayTitleBlock=BX.create("div",{props:{className:"bx-messenger-call-overlay-title-block"},html:i+a})]}),BX.create("div",{props:{className:"bx-messenger-call-overlay-photo"},children:[BX.create("div",{props:{className:"bx-messenger-call-overlay-photo-left"},children:[BX.create("div",{props:{className:"bx-messenger-call-overlay-photo-block"},children:[this.callOverlayPhotoCompanion=BX.create("img",{props:{className:"bx-messenger-call-overlay-photo-img"},attrs:{"data-userId":"phone",src:this.getHrPhoto("phone")}})]})]}),this.callOverlayProgressBlock=BX.create("div",{props:{className:"bx-messenger-call-overlay-photo-progress"+(t?"":" bx-messenger-call-overlay-photo-progress-incoming")}}),BX.create("div",{props:{className:"bx-messenger-call-overlay-photo-right"},children:[BX.create("div",{props:{className:"bx-messenger-call-overlay-photo-block"},children:[this.callOverlayPhotoSelf=BX.create("img",{props:{className:"bx-messenger-call-overlay-photo-img"},attrs:{"data-userId":this.BXIM.userId,src:this.getHrPhoto(this.BXIM.userId)}})]})]})]}),BX.create("div",{props:{className:"bx-messenger-call-overlay-crm-block"},children:[this.callOverlayCrmBlock=BX.create("div",{props:{className:"bx-messenger-call-overlay-crm-block-wrap"}})]}),BX.create("div",{props:{className:"bx-messenger-call-overlay-status"},children:[this.callOverlayStatusBlock=BX.create("div",{props:{className:"bx-messenger-call-overlay-status-block"}})]}),BX.create("div",{props:{className:"bx-messenger-call-video-mini"},children:[this.callOverlayVideoSelf=BX.create("video",{attrs:{autoplay:true},props:{className:"bx-messenger-call-video-mini-block"}}),BX.create("div",{props:{className:"bx-messenger-call-video-mini-photo"},children:[this.callOverlayPhotoMini=BX.create("img",{props:{className:"bx-messenger-call-video-mini-photo-img"},attrs:{src:this.getHrPhoto(this.BXIM.userId)}})]})]}),BX.create("div",{props:{className:"bx-messenger-call-overlay-buttons"},children:[this.callOverlayButtonsBlock=BX.create("div",{props:{className:"bx-messenger-call-overlay-buttons-block"}})]})]};BX.IM.WebRTC.prototype.callPhoneOverlayMeter=function(e){if(!this.phoneCurrentCall||this.phoneCurrentCall.state()!="CONNECTED")return false;var t=5;if(90<=e){t=5}else if(e>=70&&e<90){t=4}else if(e>=50&&e<70){t=3}else if(e>=20&&e<50){t=2}else if(e>=0&&e<20){t=1}var s=BX.message("IM_PHONE_GRADE_4");if(t==4)s=BX.message("IM_PHONE_GRADE_3");else if(t==3||t==2)s=BX.message("IM_PHONE_GRADE_2");else if(t==1)s=BX.message("IM_PHONE_GRADE_1");this.phoneCurrentCall.sendMessage(JSON.stringify({COMMAND:"meter",PERCENT:e,GRADE:t}));this.callOverlayMeterGrade.className="bx-messenger-call-overlay-meter bx-messenger-call-overlay-meter-grade-"+t;this.callOverlayMeterGrade.setAttribute("title",BX.message("IM_PHONE_GRADE")+" "+s);this.callOverlayMeterPercent.innerHTML=e};BX.IM.WebRTC.prototype.callGroupOverlayRedraw=function(){this.callToGroup=true;this.callGroupUsers=this.messenger.userInChat[this.callChatId];this.callOverlayUserId=0;this.callOverlayChatId=this.callChatId;this.callOverlayBody.innerHTML="";this.callOverlayOptions["callToGroup"]=this.callToGroup;this.callOverlayOptions["fromUserId"]=this.callChatId;BX.addClass(this.callOverlay,"bx-messenger-call-overlay-group");BX.adjust(this.callOverlayBody,{children:this.callGroupOverlayShow(this.callOverlayOptions)});this.callOverlayStatus(this.callOverlayOptions.status);this.callOverlayButtons(this.callOverlayOptions.buttons);this.callOverlayProgress(this.callOverlayOptions.progress);BX("bx-messenger-call-overlay-button-plus").style.display="inline-block";this.attachMediaStream(this.callOverlayVideoSelf,this.callStreamSelf);this.callOverlayVideoSelf.muted=true;if(this.messenger.currentTab!="chat"+this.callChatId){this.messenger.openMessenger("chat"+this.callChatId);this.callOverlayToggleSize(false)}var e=this.callOverlayVideoMain.getAttribute("data-userId");for(var t in this.callStreamUsers){if(!this.callStreamUsers[t]&&e==t)continue;this.attachMediaStream(this.callOverlayVideoUsers[t],this.callStreamUsers[t]);BX.removeClass(this.callOverlayVideoUsers[t].parentNode,"bx-messenger-call-video-hide")}BX.addClass(this.callOverlayVideoUsers[e].parentNode,"bx-messenger-call-video-block-hide");BX.addClass(this.callOverlayVideoUsers[e].parentNode,"bx-messenger-call-video-hide");this.callOverlayVideoUsers[e].parentNode.setAttribute("title","");return true};BX.IM.WebRTC.prototype.overlayEnterFullScreen=function(){if(this.callOverlayFullScreen){BX.removeClass(this.messenger.popupMessengerContent,"bx-messenger-call-overlay-full");if(document.cancelFullScreen)document.cancelFullScreen();else if(document.mozCancelFullScreen)document.mozCancelFullScreen();else if(document.webkitCancelFullScreen)document.webkitCancelFullScreen()}else{BX.addClass(this.messenger.popupMessengerContent,"bx-messenger-call-overlay-full");if(this.detectedBrowser=="chrome"){BX.bind(window,"webkitfullscreenchange",this.callOverlayFullScreenBind=BX.proxy(this.overlayEventFullScreen,this));this.messenger.popupMessengerContent.webkitRequestFullScreen(this.messenger.popupMessengerContent.ALLOW_KEYBOARD_INPUT)}else if(this.detectedBrowser=="firefox"){BX.bind(window,"mozfullscreenchange",this.callOverlayFullScreenBind=BX.proxy(this.overlayEventFullScreen,this));this.messenger.popupMessengerContent.mozRequestFullScreen(this.messenger.popupMessengerContent.ALLOW_KEYBOARD_INPUT)}}};BX.IM.WebRTC.prototype.overlayEventFullScreen=function(){if(this.callOverlayFullScreen){if(this.detectedBrowser=="chrome")BX.unbind(window,"webkitfullscreenchange",this.callOverlayFullScreenBind);else if(this.detectedBrowser=="firefox")BX.unbind(window,"mozfullscreenchange",this.callOverlayFullScreenBind);BX.removeClass(this.messenger.popupMessengerContent,"bx-messenger-call-overlay-full");if(BX.browser.IsChrome()){BX.addClass(this.messenger.popupMessengerContent,"bx-messenger-call-overlay-full-chrome-hack");setTimeout(BX.delegate(function(){BX.removeClass(this.messenger.popupMessengerContent,"bx-messenger-call-overlay-full-chrome-hack")},this),100)}this.callOverlayFullScreen=false}else{BX.addClass(this.messenger.popupMessengerContent,"bx-messenger-call-overlay-full");this.callOverlayFullScreen=true}this.messenger.popupMessengerBody.scrollTop=this.messenger.popupMessengerBody.scrollHeight-this.messenger.popupMessengerBody.offsetHeight};BX.IM.WebRTC.prototype.callOverlayToggleSize=function(e){if(this.callOverlay==null)return false;if(!this.ready()){this.callOverlayClose(true);return false}var t=typeof e=="boolean"?!e:this.callOverlayMinimize;var s=false;if(this.messenger.popupMessenger!=null&&!this.BXIM.dialogOpen)s=true;else if(this.messenger.popupMessenger!=null&&this.callOverlayUserId>0&&this.callOverlayUserId!=this.messenger.currentTab)s=true;else if(this.messenger.popupMessenger!=null&&this.callOverlayChatId>0&&this.callOverlayChatId!=this.messenger.currentTab.toString().substr(4))s=true;else if(this.messenger.popupMessenger!=null&&this.callOverlayUserId==0&&this.callOverlayChatId==0&&this.phoneNumber)s=true;if(t&&this.callActive)BX.addClass(this.callOverlay,"bx-messenger-call-overlay-call");else BX.removeClass(this.callOverlay,"bx-messenger-call-overlay-call");BX.unbindAll(this.callOverlay);if(t){this.callOverlayMinimize=false;BX.addClass(this.callOverlay,"bx-messenger-call-overlay-maxi");BX.addClass(this.messenger.popupMessengerContent,"bx-messenger-call-maxi");BX.removeClass(this.callOverlay,"bx-messenger-call-overlay-line");BX.removeClass(this.callOverlay,"bx-messenger-call-overlay-mini")}else{this.callOverlayMinimize=true;BX.addClass(this.callOverlay,"bx-messenger-call-overlay-mini");BX.removeClass(this.callOverlay,"bx-messenger-call-overlay-maxi");BX.removeClass(this.messenger.popupMessengerContent,"bx-messenger-call-maxi");if(s){BX.addClass(this.callOverlay,"bx-messenger-call-overlay-line");setTimeout(BX.delegate(function(){BX.bind(this.callOverlay,"click",BX.delegate(function(){if(this.BXIM.dialogOpen){if(this.callOverlayUserId>0){this.messenger.openChatFlag=false;BX.MessengerCommon.openDialog(this.callOverlayUserId,false,false)}else{this.messenger.openChatFlag=true;BX.MessengerCommon.openDialog("chat"+this.callOverlayChatId,false,false)}}else{if(this.callOverlayUserId>0){this.messenger.openChatFlag=false;this.messenger.currentTab=this.callOverlayUserId}else{this.messenger.openChatFlag=true;this.messenger.currentTab="chat"+this.callOverlayChatId}this.messenger.extraClose(true,false)}this.callOverlayToggleSize(false)},this))},this),200)}else{BX.removeClass(this.callOverlay,"bx-messenger-call-overlay-line")}if(this.BXIM.isFocus())BX.MessengerCommon.readMessage(this.messenger.currentTab);if(this.BXIM.isFocus()&&this.notify.notifyUpdateCount>0)this.notify.viewNotifyAll()}if(this.callOverlayUserId>0&&this.callOverlayUserId==this.messenger.currentTab){this.desktop.closeTopmostWindow()}else if(this.callOverlayChatId>0&&this.callOverlayChatId==this.messenger.currentTab.toString().substr(4)){this.desktop.closeTopmostWindow()}else{this.desktop.openCallFloatDialog()}if(this.callDialogAllow!=null){if(this.callDialogAllow)this.callDialogAllow.close();setTimeout(BX.delegate(function(){this.callDialogAllowShow()},this),1500)}if(this.popupTransferDialog)this.popupTransferDialog.close()};BX.IM.WebRTC.prototype.callOverlayClose=function(e,t){if(this.callOverlay==null)return false;this.audioMuted=true;this.toggleAudio(false);t=t==true;if(!t&&this.callOverlayFullScreen){if(this.detectedBrowser=="firefox"){BX.removeClass(this.messenger.popupMessengerContent,"bx-messenger-call-overlay-full");BX.remove(this.messenger.popupMessengerContent);BX.hide(this.messenger.popupMessenger.popupContainer);setTimeout(BX.delegate(function(){this.messenger.popupMessenger.destroy();this.messenger.openMessenger()},this),200)}else this.overlayEnterFullScreen()}if(this.messenger.popupMessenger!=null){var s=null;if(this.BXIM.dialogOpen)s=this.messenger.popupMessengerBody;else if(this.BXIM.notifyOpen)s=this.messenger.popupNotifyItem;if(s){if(BX.MessengerCommon.isScrollMax(s)){BX.removeClass(this.messenger.popupMessengerContent,"bx-messenger-call")}else{BX.removeClass(this.messenger.popupMessengerContent,"bx-messenger-call");s.scrollTop=s.scrollTop-50}}else{BX.removeClass(this.messenger.popupMessengerContent,"bx-messenger-call")}BX.removeClass(this.messenger.popupMessengerContent,"bx-messenger-call-maxi")}this.messenger.closeMenuPopup();e=e!=false;if(e)BX.addClass(this.callOverlay,"bx-messenger-call-overlay-hide");if(e){setTimeout(BX.delegate(function(){BX.remove(this.callOverlay);this.callOverlay=null;this.callOverlayButtonsBlock=null;this.callOverlayTitleBlock=null;this.callOverlayMeter=null;this.callOverlayStatusBlock=null;this.callOverlayProgressBlock=null;this.callOverlayMinimize=null;this.callOverlayChatId=0;this.callOverlayUserId=0;this.callOverlayPhotoSelf=null;this.callOverlayPhotoUsers={};this.callOverlayVideoUsers={};this.callOverlayVideoPhotoUsers={};this.callOverlayOptions={};this.callOverlayPhotoCompanion=null;this.callSelfDisabled=false;if(this.BXIM.isFocus())BX.MessengerCommon.readMessage(this.messenger.currentTab)},this),300)}else{BX.remove(this.callOverlay);this.callOverlay=null;this.callOverlayButtonsBlock=null;this.callOverlayStatusBlock=null;this.callOverlayProgressBlock=null;this.callOverlayMinimize=null;this.callOverlayChatId=0;this.callOverlayUserId=0;this.callOverlayPhotoSelf=null;this.callOverlayPhotoUsers={};this.callOverlayVideoUsers={};this.callOverlayVideoPhotoUsers={};this.callOverlayOptions={};this.callOverlayPhotoCompanion=null;this.callSelfDisabled=false;if(this.BXIM.isFocus())BX.MessengerCommon.readMessage(this.messenger.currentTab)}if(t){window.onbeforeunload=this.callOldBeforeUnload;this.BXIM.stopRepeatSound("ringtone");this.BXIM.stopRepeatSound("dialtone")}else{this.callOverlayDeleteEvents()}this.desktop.closeTopmostWindow()};BX.IM.WebRTC.prototype.callOverlayVideoClose=function(){this.audioMuted=true;this.toggleAudio(false);BX.style(this.callOverlayVideoMain,"height",this.callOverlayVideoMain.parentNode.offsetHeight+"px");BX.addClass(this.callOverlayVideoMain.parentNode,"bx-messenger-call-video-main-bg-start");setTimeout(BX.delegate(function(){this.callOverlayClose()},this),1700)};BX.IM.WebRTC.prototype.callAbort=function(e){this.callOverlayDeleteEvents();if(e)this.callOverlayStatus(e)};BX.IM.WebRTC.prototype.callOverlayDeleteEvents=function(e){if(!this.callSupport())return false;e=e||{};this.desktop.closeTopmostWindow();window.onbeforeunload=this.callOldBeforeUnload;var t=e.closeNotify!==false;if(t&&this.callNotify)this.callNotify.destroy();var s=null;if(this.phoneCallId){s=this.phoneCallId}else if(this.callToGroup){s="chat"+this.callChatId}else{s="user"+this.callUserId}BX.onCustomEvent(window,"onImCallEnd",{CALL_ID:s});clearInterval(this.callAspectCheckInterval);this.deleteEvents();this.callToMobile=false;this.callToPhone=false;BX.removeClass(this.callOverlay,"bx-messenger-call-overlay-call-audio");BX.removeClass(this.callOverlay,"bx-messenger-call-overlay-call-video");if(this.messenger.popupMessenger){this.messenger.popupMessenger.setClosingByEsc(true);BX.removeClass(BX("bx-messenger-popup-messenger"),"bx-messenger-popup-messenger-dont-close");this.messenger.dialogStatusRedraw()}this.phoneCallFinish();clearTimeout(this.callDialtoneTimeout);this.BXIM.stopRepeatSound("ringtone");this.BXIM.stopRepeatSound("dialtone");clearTimeout(this.callInviteTimeout);clearTimeout(this.callDialogAllowTimeout);if(this.callDialogAllow)this.callDialogAllow.close()};BX.IM.WebRTC.prototype.callOverlayProgress=function(e){if(this.callOverlay==null)return false;if(e!=this.callOverlayOptions.progress){BX.addClass(this.callOverlay,"bx-messenger-call-overlay-status-"+e);BX.removeClass(this.callOverlay,"bx-messenger-call-overlay-status-"+this.callOverlayOptions.progress)}this.callOverlayOptions.progress=e;this.callOverlayProgressBlock.innerHTML="";if(e=="connect"){this.callOverlayProgressBlock.appendChild(BX.create("div",{props:{className:"bx-messenger-call-overlay-progress"},children:[BX.create("img",{props:{className:"bx-messenger-call-overlay-progress-status bx-messenger-call-overlay-progress-status-anim-1"}}),BX.create("img",{props:{className:"bx-messenger-call-overlay-progress-status bx-messenger-call-overlay-progress-status-anim-2"}})]}))}else if(e=="online"){this.callOverlayProgressBlock.appendChild(BX.create("div",{props:{className:"bx-messenger-call-overlay-progress bx-messenger-call-overlay-progress-online"},children:[BX.create("img",{props:{className:"bx-messenger-call-overlay-progress-status bx-messenger-call-overlay-progress-status-anim-3"}})]}))}else if(e=="wait"||e=="offline"||e=="error"){if(e=="offline"){BX.removeClass(this.callOverlay,"bx-messenger-call-overlay-online");BX.removeClass(this.callOverlay,"bx-messenger-call-overlay-call");BX.removeClass(this.callOverlay,"bx-messenger-call-overlay-call-active");this.BXIM.playSound("error")}else if(e=="error"){e="offline"}this.callOverlayProgressBlock.appendChild(BX.create("div",{props:{className:"bx-messenger-call-overlay-progress bx-messenger-call-overlay-progress-"+e}}))}else{this.callOverlayOptions.progress="";BX.removeClass(this.callOverlay,"bx-messenger-call-overlay-status-"+e);return false}};BX.IM.WebRTC.prototype.callOverlayStatus=function(e){if(this.callOverlay==null||typeof e=="undefined")return false;this.callOverlayOptions.status=e;this.callOverlayStatusBlock.innerHTML=e.toString()};BX.IM.WebRTC.prototype.callOverlayTitle=function(){var e="";var t=this.callInitUserId!=this.BXIM.userId;if(this.callToPhone){e=this.callOverlayTitleBlock.innerHTML}else if(this.callToGroup){e=this.messenger.chat[this.callChatId].name;if(e.length>85)e=e.substr(0,85)+"...";e=BX.message("IM_CALL_GROUP_"+(this.callVideo?"VIDEO":"VOICE")+(t?"_FROM":"_TO")).replace("#CHAT#",e)}else{e=BX.message("IM_M_CALL_"+(this.callVideo?"VIDEO":"VOICE")+(t?"_FROM":"_TO")).replace("#USER#",this.messenger.users[this.callUserId].name)}return e};BX.IM.WebRTC.prototype.callOverlayUpdatePhoto=function(){this.callOverlayTitleBlock.innerHTML=this.callOverlayTitle();for(var e in this.callOverlayPhotoUsers){if(e=="phone")this.callOverlayPhotoUsers[e].src="/bitrix/js/im/images/hidef-phone-v2.png";else if(this.messenger.hrphoto[e])this.callOverlayPhotoUsers[e].src=this.messenger.hrphoto[e];else if(this.messenger.users[e].avatar==this.BXIM.pathToBlankImage)this.callOverlayPhotoUsers[e].src="/bitrix/js/im/images/hidef-avatar-v2.png";else this.callOverlayPhotoUsers[e].src=this.messenger.users[e].avatar}for(var e in this.callOverlayVideoPhotoUsers){if(e=="phone")this.callOverlayVideoPhotoUsers[e].src="/bitrix/js/im/images/hidef-phone-v2.png";else if(this.messenger.hrphoto[e])this.callOverlayVideoPhotoUsers[e].src=this.messenger.hrphoto[e];else if(this.messenger.users[e].avatar==this.BXIM.pathToBlankImage)this.callOverlayVideoPhotoUsers[e].src="/bitrix/js/im/images/hidef-avatar-v2.png";else this.callOverlayVideoPhotoUsers[e].src=this.messenger.users[e].avatar}if(this.callOverlayPhotoCompanion){var t=this.callOverlayPhotoCompanion.getAttribute("data-userId");if(t=="phone")this.callOverlayPhotoCompanion.src="/bitrix/js/im/images/hidef-phone-v2.png";else if(this.messenger.hrphoto[t])this.callOverlayPhotoCompanion.src=this.messenger.hrphoto[t];else if(this.messenger.users[t]&&this.messenger.users[t].avatar==this.BXIM.pathToBlankImage)this.callOverlayPhotoCompanion.src="/bitrix/js/im/images/hidef-avatar-v2.png";else if(this.messenger.users[t])this.callOverlayPhotoCompanion.src=this.messenger.users[t].avatar}if(this.callOverlayPhotoSelf){this.callOverlayPhotoSelf.src=this.getHrPhoto(this.BXIM.userId);this.callOverlayPhotoMini.src=this.callOverlayPhotoSelf.src;

}};BX.IM.WebRTC.prototype.callOverlayDrawCrm=function(){if(this.callOverlayCrmBlock&&this.phoneCrm.FOUND){this.callOverlayCrmBlock.innerHTML="";if(this.phoneCrm.FOUND=="Y"){BX.removeClass(this.callOverlay,"bx-messenger-call-overlay-mini");BX.addClass(this.callOverlay,"bx-messenger-call-overlay-maxi");BX.addClass(this.messenger.popupMessengerContent,"bx-messenger-call-maxi");BX.addClass(this.callOverlay,"bx-messenger-call-overlay-crm");BX.removeClass(this.callOverlay,"bx-messenger-call-overlay-crm-short");var e=this.phoneCrm.CONTACT&&this.phoneCrm.CONTACT.NAME?this.phoneCrm.CONTACT.NAME:"";if(this.phoneCrm.ACTIVITY_URL){e='<a href="'+this.phoneCrm.SHOW_URL+'" target="_blank" class="bx-messenger-call-crm-about-link">'+e+"</a>"}var t=BX.create("div",{props:{className:"bx-messenger-call-crm-about"},children:[BX.create("div",{props:{className:"bx-messenger-call-crm-about-block bx-messenger-call-crm-about-contact"},children:[BX.create("div",{props:{className:"bx-messenger-call-crm-about-block-header"},html:BX.message("IM_CRM_ABOUT_CONTACT")}),BX.create("div",{props:{className:"bx-messenger-call-crm-about-block-avatar"},html:this.phoneCrm.CONTACT&&this.phoneCrm.CONTACT.PHOTO?'<img src="'+this.phoneCrm.CONTACT.PHOTO+'" class="bx-messenger-call-crm-about-block-avatar-img">':""}),BX.create("div",{props:{className:"bx-messenger-call-crm-about-block-line-1"},html:e}),BX.create("div",{props:{className:"bx-messenger-call-crm-about-block-line-2"},html:this.phoneCrm.CONTACT&&this.phoneCrm.CONTACT.POST?this.phoneCrm.CONTACT.POST:""})]}),this.phoneCrm.COMPANY?BX.create("div",{props:{className:"bx-messenger-call-crm-about-block bx-messenger-call-crm-about-company"},children:[BX.create("div",{props:{className:"bx-messenger-call-crm-about-block-header"},html:BX.message("IM_CRM_ABOUT_COMPANY")}),BX.create("div",{props:{className:"bx-messenger-call-crm-about-block-line-1"},html:this.phoneCrm.COMPANY})]}):null]});var s=BX.create("div",{props:{className:"bx-messenger-call-crm-about"},children:[BX.create("div",{props:{className:"bx-messenger-call-crm-about-block bx-messenger-call-crm-about-contact"},children:this.phoneCrm.RESPONSIBILITY&&this.phoneCrm.RESPONSIBILITY.NAME?[BX.create("div",{props:{className:"bx-messenger-call-crm-about-block-header"},html:BX.message("IM_CRM_RESPONSIBILITY")}),BX.create("div",{props:{className:"bx-messenger-call-crm-about-block-avatar"},html:this.phoneCrm.RESPONSIBILITY.PHOTO?'<img src="'+this.phoneCrm.RESPONSIBILITY.PHOTO+'" class="bx-messenger-call-crm-about-block-avatar-img">':""}),BX.create("div",{props:{className:"bx-messenger-call-crm-about-block-line-1"},html:this.phoneCrm.RESPONSIBILITY.NAME?this.phoneCrm.RESPONSIBILITY.NAME:""}),BX.create("div",{props:{className:"bx-messenger-call-crm-about-block-line-2"},html:this.phoneCrm.RESPONSIBILITY.POST?this.phoneCrm.RESPONSIBILITY.POST:""})]:[]})]});var i=null;if(this.phoneCrm.ACTIVITY_URL||this.phoneCrm.INVOICE_URL||this.phoneCrm.DEAL_URL){i=BX.create("div",{props:{className:"bx-messenger-call-crm-buttons"},children:[this.phoneCrm.ACTIVITY_URL?BX.create("a",{attrs:{target:"_blank",href:this.phoneCrm.ACTIVITY_URL},props:{className:"bx-messenger-call-crm-button"},html:BX.message("IM_CRM_BTN_ACTIVITY")}):null,this.phoneCrm.DEAL_URL?BX.create("a",{attrs:{target:"_blank",href:this.phoneCrm.DEAL_URL},props:{className:"bx-messenger-call-crm-button"},html:BX.message("IM_CRM_BTN_DEAL")}):null,this.phoneCrm.INVOICE_URL?BX.create("a",{attrs:{target:"_blank",href:this.phoneCrm.INVOICE_URL},props:{className:"bx-messenger-call-crm-button"},html:BX.message("IM_CRM_BTN_INVOICE")}):null,this.phoneCrm.CURRENT_CALL_URL?BX.create("a",{attrs:{target:"_blank",href:this.phoneCrm.CURRENT_CALL_URL},props:{className:"bx-messenger-call-crm-link"},html:"+ "+BX.message("IM_CRM_BTN_CURRENT_CALL")}):null]})}var a=null;if(this.phoneCrm.ACTIVITIES&&this.phoneCrm.ACTIVITIES.length>0){crmArActivities=[];for(var n=0;n<this.phoneCrm.ACTIVITIES.length;n++){crmArActivities.push(BX.create("div",{props:{className:"bx-messenger-call-crm-activities-item"},children:[BX.create("a",{attrs:{target:"_blank",href:this.phoneCrm.ACTIVITIES[n].URL},props:{className:"bx-messenger-call-crm-activities-name"},html:this.phoneCrm.ACTIVITIES[n].TITLE}),BX.create("div",{props:{className:"bx-messenger-call-crm-activities-status"},html:(this.phoneCrm.ACTIVITIES[n].OVERDUE=="Y"?'<span class="bx-messenger-call-crm-activities-dot"></span>':"")+this.phoneCrm.ACTIVITIES[n].DATE})]}))}a=BX.create("div",{props:{className:"bx-messenger-call-crm-activities"},children:[BX.create("div",{props:{className:"bx-messenger-call-crm-activities-header"},html:BX.message("IM_CRM_ACTIVITIES")}),BX.create("div",{props:{className:"bx-messenger-call-crm-activities-items"},children:crmArActivities})]})}var o=null;if(this.phoneCrm.DEALS&&this.phoneCrm.DEALS.length>0){crmArDeals=[];for(var n=0;n<this.phoneCrm.DEALS.length;n++){crmArDeals.push(BX.create("div",{props:{className:"bx-messenger-call-crm-deals-item"},children:[BX.create("a",{attrs:{target:"_blank",href:this.phoneCrm.DEALS[n].URL},props:{className:"bx-messenger-call-crm-deals-name"},html:this.phoneCrm.DEALS[n].TITLE}),BX.create("div",{props:{className:"bx-messenger-call-crm-deals-status"},html:this.phoneCrm.DEALS[n].STAGE})]}))}o=BX.create("div",{props:{className:"bx-messenger-call-crm-deals"},children:[BX.create("div",{props:{className:"bx-messenger-call-crm-deals-header"},html:BX.message("IM_CRM_DEALS")}),BX.create("div",{props:{className:"bx-messenger-call-crm-deals-items"},children:crmArDeals})]})}var r=[];if(a&&o){r=[BX.create("div",{props:{className:"bx-messenger-call-crm-space"}}),t,a,o,BX.create("div",{props:{className:"bx-messenger-call-crm-space"}}),i]}else{if(a||o){r=[BX.create("div",{props:{className:"bx-messenger-call-crm-space"}}),t,BX.create("div",{props:{className:"bx-messenger-call-crm-space"}}),s,BX.create("div",{props:{className:"bx-messenger-call-crm-space"}}),BX.create("div",{props:{className:"bx-messenger-call-crm-space"}}),a?a:o,BX.create("div",{props:{className:"bx-messenger-call-crm-space"}}),i]}else if(!a&&!o&&i){BX.addClass(this.callOverlay,"bx-messenger-call-overlay-crm-short");this.callOverlayCrmBlock.innerHTML="";r=[BX.create("div",{props:{className:"bx-messenger-call-crm-space"}}),t,BX.create("div",{props:{className:"bx-messenger-call-crm-space"}}),s,BX.create("div",{props:{className:"bx-messenger-call-crm-space"}}),BX.create("div",{props:{className:"bx-messenger-call-crm-space"}}),i]}else{BX.addClass(this.callOverlay,"bx-messenger-call-overlay-crm-short");this.callOverlayCrmBlock.innerHTML="";r=[BX.create("div",{props:{className:"bx-messenger-call-crm-space"}}),BX.create("div",{props:{className:"bx-messenger-call-crm-space"}}),BX.create("div",{props:{className:"bx-messenger-call-crm-space"}}),t,BX.create("div",{props:{className:"bx-messenger-call-crm-space"}}),BX.create("div",{props:{className:"bx-messenger-call-crm-space"}}),BX.create("div",{props:{className:"bx-messenger-call-crm-space"}}),BX.create("div",{props:{className:"bx-messenger-call-crm-space"}}),s]}}}else if(this.phoneCrm.LEAD_URL||this.phoneCrm.CONTACT_URL){BX.removeClass(this.callOverlay,"bx-messenger-call-overlay-mini");BX.addClass(this.callOverlay,"bx-messenger-call-overlay-maxi");BX.addClass(this.messenger.popupMessengerContent,"bx-messenger-call-maxi");BX.addClass(this.callOverlay,"bx-messenger-call-overlay-crm");BX.addClass(this.callOverlay,"bx-messenger-call-overlay-crm-short");r=[BX.create("div",{props:{className:"bx-messenger-call-crm-phone-space"}}),BX.create("div",{props:{className:"bx-messenger-call-crm-phone-icon"},children:[BX.create("div",{props:{className:"bx-messenger-call-crm-phone-icon-block"}})]}),BX.create("div",{props:{className:"bx-messenger-call-crm-phone-space"}}),BX.create("div",{props:{className:"bx-messenger-call-crm-buttons bx-messenger-call-crm-buttons-center"},children:[this.phoneCrm.CONTACT_URL?BX.create("a",{attrs:{target:"_blank",href:this.phoneCrm.CONTACT_URL},props:{className:"bx-messenger-call-crm-button"},html:BX.message("IM_CRM_BTN_NEW_CONTACT")}):null,this.phoneCrm.LEAD_URL?BX.create("a",{attrs:{target:"_blank",href:this.phoneCrm.LEAD_URL},props:{className:"bx-messenger-call-crm-button"},html:BX.message("IM_CRM_BTN_NEW_LEAD")}):null]})]}BX.adjust(this.callOverlayCrmBlock,{children:r})}};BX.IM.WebRTC.prototype.callOverlayButtons=function(e){if(this.callOverlay==null)return false;this.callOverlayOptions.buttons=e;BX.cleanNode(this.callOverlayButtonsBlock);for(var t=0;t<e.length;t++){if(e[t]==null)continue;var s={};s.title=e[t].title||"";s.text=e[t].text||"";s.subtext=e[t].subtext||"";s.className=e[t].className||"";s.id=e[t].id||s.className;s.events=e[t].events||{};s.style={};var i="";if(typeof e[t].showInMinimize=="boolean")i=" bx-messenger-call-overlay-button-show-"+(e[t].showInMinimize?"mini":"maxi");else if(typeof e[t].showInMaximize=="boolean")i=" bx-messenger-call-overlay-button-show-"+(e[t].showInMaximize?"maxi":"mini");else if(typeof e[t].disabled=="boolean"&&e[t].disabled)i=" bx-messenger-call-overlay-button-disabled";if(typeof e[t].hide=="boolean"&&e[t].hide)s.style.display="none";this.callOverlayButtonsBlock.appendChild(BX.create("div",{attrs:{id:s.id,title:s.title},style:s.style,props:{className:"bx-messenger-call-overlay-button"+(s.subtext?" bx-messenger-call-overlay-button-sub":"")+i},events:s.events,html:'<span class="'+s.className+'"></span><span class="bx-messenger-call-overlay-button-text">'+s.text+(s.subtext?'<div class="bx-messenger-call-overlay-button-text-sub">'+s.subtext+"</div>":"")+"</span>"}))}};BX.IM.WebRTC.prototype.callDialogAllowShow=function(e){if(this.desktop.ready())return false;if(this.phoneMicAccess)return false;e=e!=false;if(!this.phoneAPI){if(this.callStreamSelf!=null)return false;if(e&&!this.callActive)return false}if(this.callDialogAllow)this.callDialogAllow.close();this.callDialogAllow=new BX.PopupWindow("bx-messenger-call-access",this.popupMessengerDialog,{lightShadow:true,zIndex:200,offsetTop:this.popupMessengerDialog?this.callOverlayMinimize?-20:-this.popupMessengerDialog.offsetHeight/2-100:-20,offsetLeft:this.callOverlay?this.callOverlay.offsetWidth/2-170:0,events:{onPopupClose:function(){this.destroy()},onPopupDestroy:BX.delegate(function(){this.callDialogAllow=null},this)},content:BX.create("div",{props:{className:"bx-messenger-call-dialog-allow"},children:[BX.create("div",{props:{className:"bx-messenger-call-dialog-allow-image-block"},children:[BX.create("div",{props:{className:"bx-messenger-call-dialog-allow-center"},children:[BX.create("div",{props:{className:"bx-messenger-call-dialog-allow-arrow"}})]}),BX.create("div",{props:{className:"bx-messenger-call-dialog-allow-center"},children:[BX.create("div",{props:{className:"bx-messenger-call-dialog-allow-button"},html:BX.message("IM_M_CALL_ALLOW_BTN")})]})]}),BX.create("div",{props:{className:"bx-messenger-call-dialog-allow-text"},html:BX.message("IM_M_CALL_ALLOW_TEXT")})]})});this.callDialogAllow.show()};BX.IM.WebRTC.prototype.callNotifyWait=function(e,t,s,i,a){if(!this.callSupport())return false;a=a==true;s=s==true;i=i==true;this.initiator=false;this.callInitUserId=t;this.callInit=true;this.callActive=false;this.callUserId=i?0:t;this.callChatId=e;this.callToGroup=i;this.callGroupUsers=this.messenger.userInChat[e];this.callVideo=s;this.callOverlayShow({toUserId:this.BXIM.userId,fromUserId:this.callToGroup?e:t,callToGroup:this.callToGroup,video:s,status:BX.message(this.callToGroup?"IM_M_CALL_ST_INVITE_2":"IM_M_CALL_ST_INVITE"),buttons:[{text:BX.message("IM_M_CALL_BTN_ANSWER"),className:"bx-messenger-call-overlay-button-answer",events:{click:BX.delegate(function(){this.BXIM.stopRepeatSound("ringtone");if(a){var e=this.callToGroup;var t=this.callChatId;var s=this.callUserId;var i=this.callVideo;this.callAbort();this.callOverlayClose(false);this.callInvite(e?"chat"+t:s,i)}else{this.callDialog();BX.ajax({url:this.BXIM.pathToCallAjax+"?CALL_ANSWER&V="+this.BXIM.revision,method:"POST",dataType:"json",timeout:30,data:{IM_CALL:"Y",COMMAND:"answer",CHAT_ID:this.callChatId,CALL_TO_GROUP:this.callToGroup?"Y":"N",RECIPIENT_ID:this.callUserId,IM_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()}});this.desktop.closeTopmostWindow()}},this)}},{text:BX.message("IM_M_CALL_BTN_HANGUP"),className:"bx-messenger-call-overlay-button-hangup",events:{click:BX.delegate(function(){this.BXIM.stopRepeatSound("ringtone");this.callSelfDisabled=true;this.callCommand(this.callChatId,"decline",{ACTIVE:this.callActive?"Y":"N",INITIATOR:this.initiator?"Y":"N"});this.callAbort();this.callOverlayClose()},this)}},{text:BX.message("IM_M_CALL_BTN_CHAT"),className:"bx-messenger-call-overlay-button-chat",showInMaximize:true,events:{click:BX.delegate(this.callOverlayToggleSize,this)}},{title:BX.message("IM_M_CALL_BTN_MAXI"),className:"bx-messenger-call-overlay-button-maxi",showInMinimize:true,events:{click:BX.delegate(this.callOverlayToggleSize,this)}}]});if(!this.BXIM.windowFocus&&this.BXIM.notifyManager.nativeNotifyGranted()){var n={title:BX.message("IM_PHONE_DESC"),text:BX.util.htmlspecialcharsback(this.callOverlayTitle()),icon:this.callUserId?this.messenger.users[this.callUserId].avatar:"",tag:"im-call"};n.onshow=function(){var e=this;setTimeout(function(){e.close()},5e3)};n.onclick=function(){window.focus();this.close()};this.BXIM.notifyManager.nativeNotify(n)}};BX.IM.WebRTC.prototype.callNotifyWaitDesktop=function(e,t,s,i,a){this.BXIM.ppServerStatus=true;if(!this.callSupport()||!this.desktop.ready())return false;a=a==true;s=s==true;i=i==true;this.initiator=false;this.callInitUserId=t;this.callInit=true;this.callActive=false;this.callUserId=i?0:t;this.callChatId=e;this.callToGroup=i;this.callGroupUsers=this.messenger.userInChat[e];this.callVideo=s;this.callOverlayShow({prepare:true,toUserId:this.BXIM.userId,fromUserId:this.callToGroup?e:t,callToGroup:this.callToGroup,video:s,status:BX.message(this.callToGroup?"IM_M_CALL_ST_INVITE_2":"IM_M_CALL_ST_INVITE"),buttons:[{text:BX.message("IM_M_CALL_BTN_ANSWER"),className:"bx-messenger-call-overlay-button-answer",events:{click:BX.delegate(function(){if(a)BX.desktop.onCustomEvent("main","bxCallJoin",[e,t,s,i]);else BX.desktop.onCustomEvent("main","bxCallAnswer",[e,t,s,i]);BX.desktop.windowCommand("close")},this)}},{text:BX.message("IM_M_CALL_BTN_HANGUP"),className:"bx-messenger-call-overlay-button-hangup",events:{click:BX.delegate(function(){BX.desktop.onCustomEvent("main","bxCallDecline",[]);BX.desktop.windowCommand("close")},this)}}]});this.desktop.drawOnPlaceholder(this.callOverlay);BX.desktop.setWindowPosition({X:STP_CENTER,Y:STP_VCENTER,Width:470,Height:120})};BX.IM.WebRTC.prototype.callFloatDialog=function(e,t,s){if(!this.desktop.ready())return false;this.audioMuted=s;var i=t?this.desktop.minCallVideoWidth:this.desktop.minCallWidth;var a=t?this.desktop.minCallVideoHeight:this.desktop.minCallHeight;var n={width:i+"px",height:a+"px"};this.callOverlay=BX.create("div",{props:{className:"bx-messenger-call-float"+(t?"":" bx-messenger-call-float-audio")},style:n,children:[this.callOverlayVideoMain=!t?null:BX.create("video",{attrs:{autoplay:true,src:t},props:{className:"bx-messenger-call-float-video"},events:{click:BX.delegate(function(){BX.desktop.onCustomEvent("main","bxCallOpenDialog",[])},this)}}),BX.create("div",{props:{className:"bx-messenger-call-float-buttons"},children:[BX.create("div",{props:{className:"bx-messenger-call-float-button bx-messenger-call-float-button-mic"+(this.audioMuted?" bx-messenger-call-float-button-mic-disabled":"")},events:{click:BX.delegate(function(e){this.audioMuted=!this.audioMuted;BX.desktop.onCustomEvent("main","bxCallMuteMic",[this.audioMuted]);BX.toggleClass(BX.proxy_context,"bx-messenger-call-float-button-mic-disabled");var t=BX.findChildByClassName(BX.proxy_context,"bx-messenger-call-float-button-text");t.innerHTML=BX.message("IM_M_CALL_BTN_MIC")+" "+BX.message("IM_M_CALL_BTN_MIC_"+(this.audioMuted?"OFF":"ON"));BX.PreventDefault(e)},this)},children:[BX.create("span",{props:{className:"bx-messenger-call-float-button-icon"}}),BX.create("span",{props:{className:"bx-messenger-call-float-button-text"},html:BX.message("IM_M_CALL_BTN_MIC")+" "+BX.message("IM_M_CALL_BTN_MIC_"+(this.audioMuted?"OFF":"ON"))})]}),BX.create("div",{props:{className:"bx-messenger-call-float-button bx-messenger-call-float-button-decline"},events:{click:BX.delegate(function(e){BX.desktop.onCustomEvent("main","bxCallDecline",[]);BX.desktop.windowCommand("close");BX.PreventDefault(e)},this)},children:[BX.create("span",{props:{className:"bx-messenger-call-float-button-icon"}}),BX.create("span",{props:{className:"bx-messenger-call-float-button-text"},html:BX.message("IM_M_CALL_BTN_HANGUP")})]})]})]});this.desktop.drawOnPlaceholder(this.callOverlay);BX.desktop.setWindowMinSize({Width:i,Height:a});BX.desktop.setWindowResizable(false);BX.desktop.setWindowClosable(false);BX.desktop.setWindowResizable(false);BX.desktop.setWindowTitle(BX.util.htmlspecialcharsback(BX.util.htmlspecialcharsback(e)));BX.desktop.setWindowPosition({X:STP_RIGHT,Y:STP_TOP,Width:i,Height:a,Mode:STP_FRONT});if(!BX.browser.IsMac())BX.desktop.setWindowPosition({X:STP_RIGHT,Y:STP_TOP,Width:i,Height:a,Mode:STP_FRONT});if(t){clearInterval(this.callAspectCheckInterval);this.callAspectCheckInterval=setInterval(BX.delegate(function(){if(this.callOverlayVideoMain.offsetWidth<this.callOverlayVideoMain.offsetHeight){if(this.callAspectHorizontal){this.callAspectHorizontal=false;BX.addClass(this.callOverlay,"bx-messenger-call-overlay-aspect-vertical");BX.desktop.setWindowSize({Width:this.desktop.minCallVideoHeight,Height:this.desktop.minCallVideoWidth})}}else{if(!this.callAspectHorizontal){this.callAspectHorizontal=true;BX.removeClass(this.callOverlay,"bx-messenger-call-overlay-aspect-vertical");BX.desktop.setWindowSize({Width:this.desktop.minCallVideoWidth,Height:this.desktop.minCallVideoHeight})}}},this),500)}BX.desktop.addCustomEvent("bxCallChangeMainVideo",BX.delegate(function(e){this.callOverlayVideoMain.src=e},this))};BX.IM.WebRTC.prototype.storageSet=function(e){};BX.IM.WebRTC.prototype.phoneSupport=function(){return this.phoneEnabled&&(this.phoneDeviceActive||this.ready())};BX.IM.WebRTC.prototype.phoneDeviceCall=function(e){var t=true;if(typeof e=="boolean"){this.BXIM.setLocalConfig("viDeviceCallBlock",!e);BX.localStorage.set("viDeviceCallBlock",!e,86400)}else{var s=this.BXIM.getLocalConfig("viDeviceCallBlock");t=this.phoneDeviceActive&&(s!=true||!this.ready())}return t};BX.IM.WebRTC.prototype.openKeyPad=function(e){this.phoneKeyPadPutPlusFlag=false;if(!this.phoneSupport()&&!(this.BXIM.desktopStatus&&this.BXIM.desktopVersion>=18)){if(!this.desktop.ready()){this.BXIM.openConfirm(BX.message("IM_CALL_NO_WEBRT"),[this.BXIM.platformName==""?null:new BX.PopupWindowButton({text:BX.message("IM_M_CALL_BTN_DOWNLOAD"),className:"popup-window-button-accept",events:{click:BX.delegate(function(){window.open(BX.browser.IsMac()?"http://dl.bitrix24.com/b24/bitrix24_desktop.dmg":"http://dl.bitrix24.com/b24/bitrix24_desktop.exe","desktopApp");BX.proxy_context.popupWindow.close()},this)}}),new BX.PopupWindowButton({text:BX.message("IM_NOTIFY_CONFIRM_CLOSE"),className:"popup-window-button-decline",events:{click:function(){this.popupWindow.close()}}})])}return false}if(this.callInit&&!this.callActive||this.callActive&&!this.phoneCurrentCall){if(this.desktop.run()){if(BX.desktop.lastTabTarget!="im"){BX.desktop.changeTab(this.BXIM.dialogOpen?"im":"notify")}else{BX.desktop.closeTab("im-phone")}}return false}if(this.callActive&&this.desktop.run()&&BX.hasClass(this.callOverlay,"bx-messenger-call-overlay-line")){BX.desktop.closeTab("im-phone");return false}if(this.popupKeyPad!=null){this.popupKeyPad.close();return false}if(this.messenger.popupMessenger){if(!this.callActive){if(this.desktop.run()){var t=BX("bx-desktop-tab-im-phone");var s=-105;var i=60}else{BX.addClass(this.messenger.popupContactListSearchCall,"bx-messenger-input-search-call-active");var t=this.messenger.popupContactListSearchCall;var s=5;var i=-72}}else{var t=BX("bx-messenger-call-overlay-button-keypad");var s=7;var i=this.desktop.run()?-90:-65;if(this.desktop.run())BX.desktop.closeTab("im-phone")}}else{var t=this.notify.panelButtonCall;var s=5;var i=-75}this.messenger.setClosingByEsc(false);this.popupKeyPad=new BX.PopupWindow("bx-messenger-popup-keypad",t,{lightShadow:true,offsetTop:s,offsetLeft:i,darkMode:true,closeByEsc:true,angle:{position:this.desktop.run()&&!this.callActive?"left":"top",offset:this.desktop.run()?this.callActive?120:76:92},autoHide:true,zIndex:200,events:{onPopupClose:function(){this.destroy()},onPopupDestroy:BX.delegate(function(){if(this.desktop.run()){if(BX.desktop.lastTabTarget!="im"){BX.desktop.changeTab(this.BXIM.dialogOpen?"im":"notify")}else{BX.desktop.closeTab("im-phone")}}this.popupKeyPad=null;this.messenger.setClosingByEsc(true);BX.removeClass(this.messenger.popupContactListSearchCall,"bx-messenger-input-search-call-active")},this)},content:BX.create("div",{props:{className:"bx-messenger-calc-wrap"+(this.desktop.run()?" bx-messenger-calc-wrap-desktop":"")},children:[BX.create("div",{props:{className:"bx-messenger-calc-body"},children:[this.popupKeyPadButtons=BX.create("div",{props:{className:"bx-messenger-calc-panel"},children:[this.popupKeyPadInputDelete=BX.create("span",{props:{className:"bx-messenger-calc-panel-delete"}}),this.popupKeyPadInput=BX.create("input",{attrs:{readonly:this.callActive?true:false,type:"text",value:"",placeholder:BX.message(this.callActive?"IM_PHONE_PUT_DIGIT":"IM_PHONE_PUT_NUMBER")},props:{className:"bx-messenger-calc-panel-input"}})]}),this.popupKeyPadButtons=BX.create("div",{props:{className:"bx-messenger-calc-btns-block"},children:[BX.create("span",{attrs:{"data-digit":1},props:{className:"bx-messenger-calc-btn bx-messenger-calc-btn-1"},html:'<span class="bx-messenger-calc-btn-num"></span>'}),BX.create("span",{attrs:{"data-digit":2},props:{className:"bx-messenger-calc-btn bx-messenger-calc-btn-2"},html:'<span class="bx-messenger-calc-btn-num"></span>'}),BX.create("span",{attrs:{"data-digit":3},props:{className:"bx-messenger-calc-btn bx-messenger-calc-btn-3"},html:'<span class="bx-messenger-calc-btn-num"></span>'}),BX.create("span",{attrs:{"data-digit":4},props:{className:"bx-messenger-calc-btn bx-messenger-calc-btn-4"},html:'<span class="bx-messenger-calc-btn-num"></span>'}),BX.create("span",{attrs:{"data-digit":5},props:{className:"bx-messenger-calc-btn bx-messenger-calc-btn-5"},html:'<span class="bx-messenger-calc-btn-num"></span>'}),BX.create("span",{attrs:{"data-digit":6},props:{className:"bx-messenger-calc-btn bx-messenger-calc-btn-6"},html:'<span class="bx-messenger-calc-btn-num"></span>'}),BX.create("span",{attrs:{"data-digit":7},props:{className:"bx-messenger-calc-btn bx-messenger-calc-btn-7"},html:'<span class="bx-messenger-calc-btn-num"></span>'}),BX.create("span",{attrs:{"data-digit":8},props:{className:"bx-messenger-calc-btn bx-messenger-calc-btn-8"},html:'<span class="bx-messenger-calc-btn-num"></span>'}),BX.create("span",{attrs:{"data-digit":9},props:{className:"bx-messenger-calc-btn bx-messenger-calc-btn-9"},html:'<span class="bx-messenger-calc-btn-num"></span>'}),BX.create("span",{attrs:{"data-digit":"*"},props:{className:"bx-messenger-calc-btn bx-messenger-calc-btn-10"},html:'<span class="bx-messenger-calc-btn-num"></span>'}),BX.create("span",{attrs:{"data-digit":"0"},props:{className:"bx-messenger-calc-btn bx-messenger-calc-btn-0"},html:'<span class="bx-messenger-calc-btn-num"></span>'}),BX.create("span",{attrs:{"data-digit":"#"},props:{className:"bx-messenger-calc-btn bx-messenger-calc-btn-11"},html:'<span class="bx-messenger-calc-btn-num"></span>'})]})]}),this.callActive?null:BX.create("div",{props:{className:"bx-messenger-call-btn-wrap"},children:[this.popupKeyPadCall=BX.create("span",{props:{className:"bx-messenger-call-btn"},children:[BX.create("span",{props:{className:"bx-messenger-call-btn-icon"}}),BX.create("span",{props:{className:"bx-messenger-call-btn-text"},html:BX.message("IM_PHONE_CALL")})]}),!this.phoneNumberLast?null:this.popupKeyPadRecall=BX.create("span",{props:{className:"bx-messenger-call-btn-2"},attrs:{title:BX.message("IM_M_CALL_BTN_RECALL_3")},children:[BX.create("span",{props:{className:"bx-messenger-call-btn-2-icon"}})]})]})]})});this.popupKeyPad.show();this.popupKeyPadInput.focus();BX.bind(this.popupKeyPad.popupContainer,"click",BX.PreventDefault);BX.bind(this.popupKeyPadInput,"keydown",BX.delegate(function(e){if(e.keyCode==13){this.BXIM.phoneTo(this.popupKeyPadInput.value)}else if(e.keyCode==37||e.keyCode==39||e.keyCode==8||e.keyCode==107||e.keyCode==46||e.keyCode==35||e.keyCode==36){}else if((e.keyCode==61||e.keyCode==187||e.keyCode==51||e.keyCode==56)&&e.shiftKey){}else if((e.keyCode==67||e.keyCode==86||e.keyCode==65||e.keyCode==88)&&(e.metaKey||e.ctrlKey)){}else if(e.keyCode>=48&&e.keyCode<=57&&!e.shiftKey){}else if(e.keyCode>=96&&e.keyCode<=105&&!e.shiftKey){}else{return BX.PreventDefault(e)}},this));var a=BX.delegate(function(){if(!this.callActive&&this.popupKeyPadInput.value.length>0){if(this.popupKeyPadInput.parentNode.className=="bx-messenger-calc-panel")BX.addClass(this.popupKeyPadInput.parentNode,"bx-messenger-calc-panel-active")}else{if(this.popupKeyPadInput.parentNode.className=="bx-messenger-calc-panel bx-messenger-calc-panel-active")BX.removeClass(this.popupKeyPadInput.parentNode,"bx-messenger-calc-panel-active")}this.popupKeyPadInput.focus()},this);BX.bind(this.popupKeyPadCall,"click",BX.delegate(function(e){this.BXIM.phoneTo(this.popupKeyPadInput.value)},this));BX.bind(this.popupKeyPadRecall,"click",BX.delegate(function(e){this.BXIM.phoneTo(this.phoneNumberLast)},this));BX.bind(this.popupKeyPadRecall,"mouseover",BX.delegate(function(e){this.popupKeyPadInput.setAttribute("placeholder",this.phoneNumberLast)},this));BX.bind(this.popupKeyPadRecall,"mouseout",BX.delegate(function(e){this.popupKeyPadInput.setAttribute("placeholder",BX.message("IM_PHONE_PUT_NUMBER"))},this));BX.bind(this.popupKeyPadInputDelete,"click",BX.delegate(function(e){if(this.callActive)return false;this.popupKeyPadInput.value=this.popupKeyPadInput.value.substr(0,this.popupKeyPadInput.value.length-1);a()},this));BX.bind(this.popupKeyPadInput,"keyup",a);BX.bindDelegate(this.popupKeyPadButtons,"mousedown",{className:"bx-messenger-calc-btn"},BX.delegate(function(){var e=BX.proxy_context.getAttribute("data-digit");if(e!=0)return false;this.phoneKeyPadPutPlus()},this));BX.bindDelegate(this.popupKeyPadButtons,"mouseup",{className:"bx-messenger-calc-btn"},BX.delegate(function(){var e=BX.proxy_context.getAttribute("data-digit");if(e==0){this.phoneKeyPadPutPlusEnd()}else{this.popupKeyPadInput.value=this.popupKeyPadInput.value+""+e}this.phoneSendDTMF(e);a()},this));return e?BX.PreventDefault(e):true};BX.IM.WebRTC.prototype.phoneKeyPadPutPlus=function(){this.phoneKeyPadPutPlusTimeout=setTimeout(BX.delegate(function(){this.phoneKeyPadPutPlusFlag=true;this.popupKeyPadInput.value=this.popupKeyPadInput.value+"+"},this),500)};BX.IM.WebRTC.prototype.phoneKeyPadPutPlusEnd=function(){clearTimeout(this.phoneKeyPadPutPlusTimeout);if(!this.phoneKeyPadPutPlusFlag)this.popupKeyPadInput.value=this.popupKeyPadInput.value+"0";this.phoneKeyPadPutPlusFlag=false};BX.IM.WebRTC.prototype.phoneCount=function(e){var t=0;if(typeof e==="object"){if(e.PERSONAL_MOBILE)t++;else if(e.PERSONAL_PHONE)t++;else if(e.WORK_PHONE)t++}return t};BX.IM.WebRTC.prototype.phoneCorrect=function(e){e=BX.util.trim(e+"");if(e.substr(0,2)=="+8"){e="008"+e.substr(2)}e=e.replace(/[^0-9\#\*]/g,"");if(e.substr(0,2)=="80"||e.substr(0,2)=="81"||e.substr(0,2)=="82"){}else if(e.substr(0,2)=="00"){e=e.substr(2)}else if(e.substr(0,3)=="011"){e=e.substr(3)}else if(e.substr(0,1)=="8"){e="7"+e.substr(1)}else if(e.substr(0,1)=="0"){e=e.substr(1)}return e};BX.IM.WebRTC.prototype.phoneDisconnectAfterCall=function(e){if(this.desktop.ready()){e=false}this.phoneDisconnectAfterCallFlag=e===false?false:true;return true};BX.IM.WebRTC.prototype.phoneCallInvite=function(e,t){if(this.debug)this.phoneLog(e,t);this.phoneNumberUser=BX.util.htmlspecialchars(e);e=this.phoneCorrect(e);if(typeof t!="object")t={};if(this.desktop.run()&&BX.desktop.currentTab!="im"){BX.desktop.changeTab("im")}if(this.popupKeyPad)this.popupKeyPad.close();if(!this.messenger.popupMessenger)this.messenger.openMessenger();if(!this.callActive&&!this.callInit){this.initiator=true;this.callInitUserId=this.BXIM.userId;this.callInit=true;this.callActive=false;this.callUserId=0;this.callChatId=0;this.callToGroup=0;this.callGroupUsers=[];this.phoneNumber=e;this.phoneParams=t;this.callOverlayShow({toUserId:0,phoneNumber:this.phoneNumber,callTitle:this.phoneNumberUser,fromUserId:this.BXIM.userId,callToGroup:false,callToPhone:true,video:false,status:BX.message("IM_M_CALL_ST_CONNECT"),buttons:[{text:BX.message("IM_M_CALL_BTN_HANGUP"),className:"bx-messenger-call-overlay-button-hangup",events:{click:BX.delegate(function(){this.phoneCallFinish();this.callAbort();this.callOverlayClose()},this)}},{text:BX.message("IM_M_CALL_BTN_CHAT"),className:"bx-messenger-call-overlay-button-chat",showInMaximize:true,events:{click:BX.delegate(this.callOverlayToggleSize,this)}},{title:BX.message("IM_M_CALL_BTN_MAXI"),className:"bx-messenger-call-overlay-button-maxi",showInMinimize:true,events:{click:BX.delegate(this.callOverlayToggleSize,this)}}]})}};BX.IM.WebRTC.prototype.phoneCall=function(e,t){if(BX.localStorage.get("viInitedCall"))return false;this.phoneNumberLast=e;this.BXIM.setLocalConfig("phone_last",e);if(this.debug)this.phoneLog(e,t);this.phoneNumberUser=BX.util.htmlspecialchars(e);numberOriginal=e;e=this.phoneCorrect(e);if(typeof t!="object")t={};if(e.length<=0){this.BXIM.openConfirm({title:BX.message("IM_PHONE_WRONG_NUMBER"),message:BX.message("IM_PHONE_WRONG_NUMBER_DESC")});return false}if(this.desktop.run()&&BX.desktop.currentTab!="im"){BX.desktop.changeTab("im")}if(this.popupKeyPad)this.popupKeyPad.close();if(!this.phoneSupport()){if(!this.desktop.ready()){this.BXIM.openConfirm(BX.message("IM_CALL_NO_WEBRT"),[new BX.PopupWindowButton({text:BX.message("IM_M_CALL_BTN_DOWNLOAD"),className:"popup-window-button-accept",events:{click:BX.delegate(function(){window.open(BX.browser.IsMac()?"http://dl.bitrix24.com/b24/bitrix24_desktop.dmg":"http://dl.bitrix24.com/b24/bitrix24_desktop.exe","desktopApp");BX.proxy_context.popupWindow.close()},this)}}),new BX.PopupWindowButton({text:BX.message("IM_NOTIFY_CONFIRM_CLOSE"),className:"popup-window-button-decline",events:{click:function(){this.popupWindow.close()}}})])}return false}if(!this.messenger.popupMessenger)this.messenger.openMessenger();if(!this.callActive&&!this.callInit){this.initiator=true;this.callInitUserId=this.BXIM.userId;this.callInit=true;this.callActive=false;this.callUserId=0;this.callChatId=0;this.callToGroup=0;this.phoneCallExternal=this.phoneDeviceCall();this.callGroupUsers=[];this.phoneNumber=e;this.phoneParams=t;this.callOverlayShow({toUserId:0,phoneNumber:this.phoneNumber,callTitle:this.phoneNumberUser,fromUserId:this.BXIM.userId,callToGroup:false,callToPhone:true,video:false,status:BX.message("IM_M_CALL_ST_CONNECT"),buttons:[{title:BX.message(this.phoneDeviceCall()?"IM_M_CALL_BTN_DEVICE_TITLE":"IM_M_CALL_BTN_DEVICE_OFF_TITLE"),id:"bx-messenger-call-overlay-button-device",className:"bx-messenger-call-overlay-button-device"+(this.phoneDeviceCall()?"":" bx-messenger-call-overlay-button-device-off"),events:{click:BX.delegate(function(){var e=this.phoneNumber;this.phoneCallFinish();this.callAbort();this.phoneDeviceCall(!this.phoneDeviceCall());this.phoneCall(e)},this)},hide:this.phoneDeviceActive&&this.enabled?false:true},{text:BX.message("IM_M_CALL_BTN_HANGUP"),className:"bx-messenger-call-overlay-button-hangup",events:{click:BX.delegate(function(){this.phoneCallFinish();this.callAbort();this.callOverlayClose()},this)}},{text:BX.message("IM_M_CALL_BTN_CHAT"),className:"bx-messenger-call-overlay-button-chat",showInMaximize:true,events:{click:BX.delegate(this.callOverlayToggleSize,this)}},{title:BX.message("IM_M_CALL_BTN_MAXI"),
className:"bx-messenger-call-overlay-button-maxi",showInMinimize:true,events:{click:BX.delegate(this.callOverlayToggleSize,this)}}]});this.BXIM.playSound("start");if(this.phoneCallExternal){this.phoneCommand("deviceStartCall",{NUMBER:numberOriginal.replace(/[^0-9]/g,"")})}else if(!this.phoneLogin||!this.phoneServer){this.phoneAuthorize()}else{this.phoneApiInit()}}};BX.IM.WebRTC.prototype.phoneAuthorize=function(){BX.ajax({url:this.BXIM.pathToCallAjax+"?PHONE_AUTHORIZE&V="+this.BXIM.revision,method:"POST",dataType:"json",skipAuthCheck:true,timeout:30,data:{IM_PHONE:"Y",COMMAND:"authorize",UPDATE_INFO:this.phoneCheckBalance?"Y":"N",IM_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()},onsuccess:BX.delegate(function(e){if(e&&e.BITRIX_SESSID){BX.message({bitrix_sessid:e.BITRIX_SESSID})}if(e.ERROR==""){this.messenger.sendAjaxTry=0;this.phoneCheckBalance=false;if(e.HR_PHOTO){for(var t in e.HR_PHOTO)this.messenger.hrphoto[t]=e.HR_PHOTO[t];this.callOverlayUpdatePhoto()}this.phoneLogin=e.LOGIN;this.phoneServer=e.SERVER;this.phoneCallerID=e.CALLERID;this.phoneApiInit()}else if(e.ERROR=="AUTHORIZE_ERROR"&&this.desktop.ready()&&this.messenger.sendAjaxTry<3){this.messenger.sendAjaxTry++;setTimeout(BX.delegate(function(){this.phoneAuthorize()},this),5e3);BX.onCustomEvent(window,"onImError",[e.ERROR])}else if(e.ERROR=="SESSION_ERROR"&&this.messenger.sendAjaxTry<2){this.messenger.sendAjaxTry++;setTimeout(BX.delegate(function(){this.phoneAuthorize()},this),2e3);BX.onCustomEvent(window,"onImError",[e.ERROR,e.BITRIX_SESSID])}else{this.callOverlayDeleteEvents();this.callOverlayProgress("offline");this.phoneLog("onetimekey",e.ERROR,e.CODE);if(e.ERROR=="AUTHORIZE_ERROR"||e.ERROR=="SESSION_ERROR"){BX.onCustomEvent(window,"onImError",[e.ERROR]);this.callAbort(BX.message("IM_PHONE_401"))}else{this.callAbort(e.ERROR+(this.debug?"<br />("+BX.message("IM_ERROR_CODE")+": "+e.CODE+")":""))}this.callOverlayButtons(this.buttonsOverlayClose)}},this),onfailure:BX.delegate(function(){this.phoneCallFinish();this.callAbort(BX.message("IM_M_CALL_ERR"));this.callOverlayClose()},this)})};BX.IM.WebRTC.prototype.phoneIncomingAnswer=function(){this.callSelfDisabled=true;this.phoneCommand(this.phoneTransferEnabled?"answerTransfer":"answer",{CALL_ID:this.phoneCallId});if(this.popupKeyPad)this.popupKeyPad.close();this.callOverlayButtons([{text:BX.message("IM_M_CALL_BTN_HANGUP"),className:"bx-messenger-call-overlay-button-hangup",events:{click:BX.delegate(function(){this.phoneCallFinish();this.callAbort();this.callOverlayClose()},this)}},{text:BX.message("IM_M_CALL_BTN_CHAT"),className:"bx-messenger-call-overlay-button-chat",showInMaximize:true,events:{click:BX.delegate(this.callOverlayToggleSize,this)}},{title:BX.message("IM_M_CALL_BTN_MAXI"),className:"bx-messenger-call-overlay-button-maxi",showInMinimize:true,events:{click:BX.delegate(this.callOverlayToggleSize,this)}}]);if(this.messenger.popupMessenger==null){this.messenger.openMessenger(this.callUserId);this.callOverlayToggleSize(false)}BX.addClass(this.callOverlay,"bx-messenger-call-overlay-maxi ");BX.addClass(this.messenger.popupMessengerContent,"bx-messenger-call-maxi ");BX.addClass(this.callOverlay,"bx-messenger-call-overlay-call");BX.removeClass(this.callOverlay,"bx-messenger-call-overlay-mini");BX.removeClass(this.callOverlay,"bx-messenger-call-overlay-line");BX.addClass(this.callOverlay,"bx-messenger-call-overlay-call-audio");if(!this.phoneLogin||!this.phoneServer){this.phoneAuthorize()}else{this.phoneApiInit()}};BX.IM.WebRTC.prototype.phoneApiInit=function(){if(!this.phoneSupport())return false;if(!this.phoneLogin||!this.phoneServer){this.phoneCallFinish();this.callOverlayProgress("offline");this.callAbort(BX.message("IM_PHONE_ERROR"));this.callOverlayButtons(this.buttonsOverlayClose);return false}if(this.phoneAPI){if(this.phoneSDKinit){if(this.phoneIncoming){this.phoneCommand(this.phoneTransferEnabled?"readyTransfer":"ready",{CALL_ID:this.phoneCallId})}else if(this.callInitUserId==this.BXIM.userId){this.phoneOnSDKReady()}}else{this.phoneOnSDKReady()}return true}this.phoneAPI=VoxImplant.getInstance();this.phoneAPI.addEventListener(VoxImplant.Events.SDKReady,BX.delegate(this.phoneOnSDKReady,this));this.phoneAPI.addEventListener(VoxImplant.Events.ConnectionEstablished,BX.delegate(this.phoneOnConnectionEstablished,this));this.phoneAPI.addEventListener(VoxImplant.Events.ConnectionFailed,BX.delegate(this.phoneOnConnectionFailed,this));this.phoneAPI.addEventListener(VoxImplant.Events.ConnectionClosed,BX.delegate(this.phoneOnConnectionClosed,this));this.phoneAPI.addEventListener(VoxImplant.Events.IncomingCall,BX.delegate(this.phoneOnIncomingCall,this));this.phoneAPI.addEventListener(VoxImplant.Events.AuthResult,BX.delegate(this.phoneOnAuthResult,this));this.phoneAPI.addEventListener(VoxImplant.Events.MicAccessResult,BX.delegate(this.phoneOnMicResult,this));this.phoneAPI.addEventListener(VoxImplant.Events.SourcesInfoUpdated,BX.delegate(this.phoneOnInfoUpdated,this));this.phoneAPI.addEventListener(VoxImplant.Events.NetStatsReceived,BX.delegate(this.phoneOnNetStatsReceived,this));var e=this.BXIM.language.toUpperCase();if(e=="EN")e="US";this.phoneAPI.init({useRTCOnly:true,micRequired:true,videoSupport:false,progressTone:true,progressToneCountry:e});this.phoneSDKinit=true;return true};BX.IM.WebRTC.prototype.phoneOnSDKReady=function(e){this.phoneLog("SDK ready");e=e||{};e.delay=e.delay||false;if(!e.delay&&this.phoneDeviceActive){if(!this.phoneIncoming&&!this.phoneDeviceCall()){if(this.desktop.ready()){BX.desktop.changeTab("im");BX.desktop.windowCommand("show");this.desktop.closeTopmostWindow()}this.callOverlayProgress("wait");this.callDialogAllowTimeout=setTimeout(BX.delegate(function(){this.phoneOnSDKReady({delay:true})},this),5e3);return false}}if(!this.phoneAPI.connected()){this.phoneAPI.connect();clearTimeout(this.callDialogAllowTimeout);this.callDialogAllowTimeout=setTimeout(BX.delegate(function(){this.callDialogAllowShow()},this),1500);this.callOverlayProgress("wait");this.callOverlayStatus(BX.message("IM_M_CALL_ST_WAIT_ACCESS"))}else{this.phoneLog("Connection exists");this.callOverlayProgress("connect");this.callOverlayStatus(BX.message("IM_M_CALL_ST_CONNECT"));this.phoneOnAuthResult({result:true})}};BX.IM.WebRTC.prototype.phoneOnConnectionEstablished=function(){this.phoneLog("Connection established",this.phoneAPI.connected());this.phoneAPI.requestOneTimeLoginKey(this.phoneLogin+"@"+this.phoneServer)};BX.IM.WebRTC.prototype.phoneOnConnectionFailed=function(){this.phoneLog("Connection failed")};BX.IM.WebRTC.prototype.phoneOnConnectionClosed=function(){this.phoneLog("Connection closed");this.phoneSDKinit=false};BX.IM.WebRTC.prototype.phoneOnIncomingCall=function(e){if(this.phoneCurrentCall)return false;this.phoneCurrentCall=e.call;this.phoneCurrentCall.addEventListener(VoxImplant.CallEvents.Connected,BX.delegate(this.phoneOnCallConnected,this));this.phoneCurrentCall.addEventListener(VoxImplant.CallEvents.Disconnected,BX.delegate(this.phoneOnCallDisconnected,this));this.phoneCurrentCall.addEventListener(VoxImplant.CallEvents.Failed,BX.delegate(this.phoneOnCallFailed,this));this.phoneCurrentCall.answer()};BX.IM.WebRTC.prototype.phoneOnAuthResult=function(e){if(e.result){if(this.phoneCallDevice=="PHONE")return false;this.phoneLog("Authorize result","success");if(this.phoneIncoming){this.phoneCommand(this.phoneTransferEnabled?"readyTransfer":"ready",{CALL_ID:this.phoneCallId})}else if(this.callInitUserId==this.BXIM.userId){this.phoneCreateCall()}}else if(e.code==302){BX.ajax({url:this.BXIM.pathToCallAjax+"?PHONE_ONETIMEKEY&V="+this.BXIM.revision,method:"POST",dataType:"json",timeout:30,data:{IM_PHONE:"Y",COMMAND:"onetimekey",KEY:e.key,IM_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()},onsuccess:BX.delegate(function(e){if(e.ERROR==""){this.phoneLog("auth with",this.phoneLogin+"@"+this.phoneServer);this.phoneAPI.loginWithOneTimeKey(this.phoneLogin+"@"+this.phoneServer,e.HASH)}else{this.phoneCallFinish();this.callOverlayProgress("offline");this.phoneLog("onetimekey",e.ERROR,e.CODE);if(e.CODE)this.callAbort(BX.message("IM_PHONE_ERROR_CONNECT"));else this.callAbort(e.ERROR+(this.debug?"<br />("+BX.message("IM_ERROR_CODE")+": "+e.CODE+")":""));this.callOverlayButtons(this.buttonsOverlayClose)}},this),onfailure:BX.delegate(function(){this.callAbort(BX.message("IM_M_CALL_ERR"));this.phoneCallFinish();this.callOverlayClose()},this)})}else{if(e.code==401||e.code==400||e.code==403||e.code==404){this.callAbort(BX.message("IM_PHONE_401"));this.phoneServer="";this.phoneLogin="";this.phoneCheckBalance=true;this.phoneCommand("authorize_error")}else{this.callAbort(BX.message("IM_M_CALL_ERR"))}this.callOverlayProgress("offline");this.phoneCallFinish();this.callOverlayButtons(this.buttonsOverlayClose);this.phoneLog("Authorize result","failed",e.code);this.phoneServer="";this.phoneLogin=""}};BX.IM.WebRTC.prototype.phoneOnMicResult=function(e){this.phoneMicAccess=e.result;this.phoneLog("Mic Access Allowed",e.result);clearTimeout(this.callDialogAllowTimeout);if(this.callDialogAllow)this.callDialogAllow.close();if(e.result){this.callOverlayProgress("connect");this.callOverlayStatus(BX.message("IM_M_CALL_ST_CONNECT"))}else{this.phoneCallFinish();this.callOverlayProgress("offline");this.callAbort(BX.message("IM_M_CALL_ST_NO_ACCESS"));this.callOverlayButtons(this.buttonsOverlayClose)}};BX.IM.WebRTC.prototype.phoneOnInfoUpdated=function(e){this.phoneLog("Info updated",this.phoneAPI.audioSources(),this.phoneAPI.videoSources())};BX.IM.WebRTC.prototype.phoneCreateCall=function(){this.phoneParams["CALLER_ID"]="";this.phoneLog("Call params: ",this.phoneNumber,this.phoneParams);if(!this.phoneAPI.connected()){this.phoneOnSDKReady();return false}if(false){this.phoneCurrentCall=true;this.callActive=true;this.phoneOnCallConnected();this.phoneCrm.FOUND="N";this.phoneCrm.CONTACT_URL="#";this.phoneCrm.LEAD_URL="#";this.callOverlayDrawCrm()}else{this.phoneAPI.setOperatorACDStatus("ONLINE");this.phoneCurrentCall=this.phoneAPI.call(this.phoneNumber,false,JSON.stringify(this.phoneParams));this.phoneCurrentCall.addEventListener(VoxImplant.CallEvents.Connected,BX.delegate(this.phoneOnCallConnected,this));this.phoneCurrentCall.addEventListener(VoxImplant.CallEvents.Disconnected,BX.delegate(this.phoneOnCallDisconnected,this));this.phoneCurrentCall.addEventListener(VoxImplant.CallEvents.Failed,BX.delegate(this.phoneOnCallFailed,this));this.phoneCurrentCall.addEventListener(VoxImplant.CallEvents.ProgressToneStart,BX.delegate(this.phoneOnProgressToneStart,this));this.phoneCurrentCall.addEventListener(VoxImplant.CallEvents.ProgressToneStop,BX.delegate(this.phoneOnProgressToneStop,this))}BX.ajax({url:this.BXIM.pathToCallAjax+"?PHONE_INIT&V="+this.BXIM.revision,method:"POST",dataType:"json",timeout:30,data:{IM_PHONE:"Y",COMMAND:"init",NUMBER:this.phoneNumber,NUMBER_USER:BX.util.htmlspecialcharsback(this.phoneNumberUser),IM_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()},onsuccess:BX.delegate(function(e){if(e.ERROR==""){if(!(e.HR_PHOTO.length==0)){for(var t in e.HR_PHOTO)this.messenger.hrphoto[t]=e.HR_PHOTO[t];this.callOverlayUserId=e.DIALOG_ID;this.callOverlayPhotoCompanion.setAttribute("data-userId",this.callOverlayUserId);this.callOverlayUpdatePhoto()}else{this.callOverlayChatId=e.DIALOG_ID.substr(4)}this.messenger.openMessenger(e.DIALOG_ID);this.callOverlayToggleSize(false)}},this)})};BX.IM.WebRTC.prototype.phoneOnCallConnected=function(e){this.BXIM.stopRepeatSound("ringtone",5e3);BX.localStorage.set("viInitedCall",true,5);clearInterval(this.phoneConnectedInterval);this.phoneConnectedInterval=setInterval(function(){BX.localStorage.set("viInitedCall",true,5)},5e3);this.phoneLog("Call connected",e);this.callOverlayCallConnectedButtons=[{text:BX.message("IM_M_CALL_BTN_HANGUP"),className:"bx-messenger-call-overlay-button-hangup",events:{click:BX.delegate(function(){this.phoneCallFinish();this.callAbort();this.BXIM.playSound("stop");this.callOverlayClose()},this)}},{title:BX.message("IM_M_CALL_BTN_MIC_TITLE"),id:"bx-messenger-call-overlay-button-mic",className:"bx-messenger-call-overlay-button-mic "+(this.phoneMicMuted?" bx-messenger-call-overlay-button-mic-off":""),events:{click:BX.delegate(function(){this.phoneToggleAudio();var e=BX.findChildByClassName(BX.proxy_context,"bx-messenger-call-overlay-button-mic");if(e)BX.toggleClass(e,"bx-messenger-call-overlay-button-mic-off")},this)},hide:this.phoneCallDevice=="PHONE"},{title:BX.message("IM_M_CALL_BTN_HOLD_TITLE"),id:"bx-messenger-call-overlay-button-hold",className:"bx-messenger-call-overlay-button-hold "+(this.phoneHolded?" bx-messenger-call-overlay-button-hold-on":""),events:{click:BX.delegate(function(){this.phoneToggleHold();var e=BX.findChildByClassName(BX.proxy_context,"bx-messenger-call-overlay-button-hold");if(e)BX.toggleClass(e,"bx-messenger-call-overlay-button-hold-on")},this)}},{title:BX.message("IM_M_CALL_BTN_TRANSFER"),id:"bx-messenger-call-overlay-button-transfer",className:"bx-messenger-call-overlay-button-transfer",events:{click:BX.delegate(function(e){this.openTransferDialog({bind:BX.proxy_context});BX.PreventDefault(e)},this)}},{title:BX.message("IM_PHONE_OPEN_KEYPAD"),className:"bx-messenger-call-overlay-button-keypad",events:{click:BX.delegate(function(e){this.openKeyPad(e)},this)},hide:this.phoneCallDevice=="PHONE"},{title:BX.message("IM_M_CALL_BTN_CHAT_2"),className:"bx-messenger-call-overlay-button-chat2",showInMaximize:true,events:{click:BX.delegate(this.callOverlayToggleSize,this)}},{title:BX.message("IM_M_CALL_BTN_MAXI"),className:"bx-messenger-call-overlay-button-maxi",showInMinimize:true,events:{click:BX.delegate(this.callOverlayToggleSize,this)}},{title:BX.message("IM_M_CALL_BTN_FULL"),className:"bx-messenger-call-overlay-button-full",events:{click:BX.delegate(this.overlayEnterFullScreen,this)},hide:this.desktop.ready()}];this.callOverlayButtons(this.callOverlayCallConnectedButtons);BX.addClass(this.callOverlay,"bx-messenger-call-overlay-maxi");BX.addClass(this.messenger.popupMessengerContent,"bx-messenger-call-maxi");BX.removeClass(this.callOverlay,"bx-messenger-call-overlay-mini");BX.removeClass(this.callOverlay,"bx-messenger-call-overlay-line");BX.addClass(this.callOverlay,"bx-messenger-call-overlay-call");this.callOverlayProgress("online");this.callOverlayStatus(BX.message("IM_M_CALL_ST_ONLINE"));this.callActive=true;if(!this.BXIM.windowFocus)this.desktop.openCallFloatDialog()};BX.IM.WebRTC.prototype.phoneOnCallDisconnected=function(e){this.phoneLog("Call disconnected",this.phoneCurrentCall?this.phoneCurrentCall.id():"-",this.phoneCurrentCall?this.phoneCurrentCall.state():"-");if(this.phoneCurrentCall){this.phoneCallFinish();this.callOverlayDeleteEvents();this.callOverlayClose();this.BXIM.playSound("stop")}if(this.phoneDisconnectAfterCallFlag&&this.phoneAPI&&this.phoneAPI.connected()){setTimeout(BX.delegate(function(){if(this.phoneAPI&&this.phoneAPI.connected())this.phoneAPI.disconnect()},this),500)}};BX.IM.WebRTC.prototype.phoneOnCallFailed=function(e){this.phoneLog("Call failed",e.code,e.reason);var t=BX.message("IM_PHONE_END");if(e.code==603){t=BX.message("IM_PHONE_DECLINE")}else if(e.code==380){t=BX.message("IM_PHONE_ERR_SIP_LICENSE")}else if(e.code==400){t=BX.message("IM_PHONE_ERR_LICENSE")}else if(e.code==401){t=BX.message("IM_PHONE_401")}else if(e.code==480||e.code==503){if(this.phoneNumber==911||this.phoneNumber==112){t=BX.message("IM_PHONE_NO_EMERGENCY")}else{t=BX.message("IM_PHONE_UNAVAILABLE")}}else if(e.code==484||e.code==404){if(this.phoneNumber==911||this.phoneNumber==112){t=BX.message("IM_PHONE_NO_EMERGENCY")}else{t=BX.message("IM_PHONE_INCOMPLETED")}}else if(e.code==402){t=BX.message("IM_PHONE_NO_MONEY")+(this.BXIM.bitrix24Admin?"<br />"+BX.message("IM_PHONE_PAY_URL_NEW"):"")}else if(e.code==486&&this.phoneRinging>1){t=BX.message("IM_M_CALL_ST_DECLINE")}else if(e.code==486){t=BX.message("IM_PHONE_ERROR_BUSY")}else if(e.code==403){t=BX.message("IM_PHONE_403");this.phoneServer="";this.phoneLogin="";this.phoneCheckBalance=true}this.phoneCallFinish();if(e.code==408||e.code==403){if(this.phoneAPI&&this.phoneAPI.connected()){setTimeout(BX.delegate(function(){if(this.phoneAPI&&this.phoneAPI.connected())this.phoneAPI.disconnect()},this),500)}}this.callOverlayProgress("offline");this.callAbort(t);this.callOverlayButtons(this.buttonsOverlayClose)};BX.IM.WebRTC.prototype.phoneOnProgressToneStart=function(e){if(!this.phoneCurrentCall)return false;this.phoneLog("Progress tone start",this.phoneCurrentCall.id());this.callOverlayStatus(BX.message("IM_PHONE_WAIT_ANSWER"));this.phoneRinging++};BX.IM.WebRTC.prototype.phoneOnProgressToneStop=function(e){if(!this.phoneCurrentCall)return false;this.phoneLog("Progress tone stop",this.phoneCurrentCall.id())};BX.IM.WebRTC.prototype.phoneOnNetStatsReceived=function(e){var t=100-parseInt(e.stats.packetLoss);this.callPhoneOverlayMeter(t)};BX.IM.WebRTC.prototype.phoneSendDTMF=function(e){if(!this.phoneCurrentCall)return false;this.phoneLog("Send DTMF code",this.phoneCurrentCall.id(),e);this.phoneCurrentCall.sendTone(e)};BX.IM.WebRTC.prototype.phoneToggleAudio=function(){if(!this.phoneCurrentCall)return false;if(this.phoneMicMuted){this.phoneCurrentCall.unmuteMicrophone()}else{this.phoneCurrentCall.muteMicrophone()}this.phoneMicMuted=!this.phoneMicMuted};BX.IM.WebRTC.prototype.phoneToggleHold=function(){if(!this.phoneCurrentCall&&this.phoneCallDevice=="WEBRTC")return false;if(this.phoneHolded){if(this.phoneCallDevice=="WEBRTC"){this.phoneCurrentCall.sendMessage(JSON.stringify({COMMAND:"unhold"}))}else{this.phoneCommand("unhold",{CALL_ID:this.phoneCallId})}}else{if(this.phoneCallDevice=="WEBRTC"){this.phoneCurrentCall.sendMessage(JSON.stringify({COMMAND:"hold"}))}else{this.phoneCommand("hold",{CALL_ID:this.phoneCallId})}}this.phoneHolded=!this.phoneHolded};BX.IM.WebRTC.prototype.phoneCallFinish=function(){clearInterval(this.phoneConnectedInterval);if(this.callInit&&this.phoneCallDevice=="PHONE"){this.phoneCommand("deviceHungup",{CALL_ID:this.phoneCallId})}else if(this.callInit&&this.phoneTransferEnabled&&this.phoneTransferUser==0){this.phoneCommand("declineTransfer",{CALL_ID:this.phoneCallId})}else if(this.callInit&&this.phoneIncoming){this.phoneCommand("skip",{CALL_ID:this.phoneCallId})}this.desktop.closeTopmostWindow();if(this.phoneCurrentCall){try{this.phoneCurrentCall.hangup()}catch(e){}this.phoneCurrentCall=null;this.phoneLog("Call hangup call")}else if(this.phoneDisconnectAfterCallFlag&&this.phoneAPI&&this.phoneAPI.connected()){setTimeout(BX.delegate(function(){if(this.phoneAPI&&this.phoneAPI.connected())this.phoneAPI.disconnect()},this),500)}if(this.popupKeyPad)this.popupKeyPad.close();if(this.popupTransferDialog)this.popupTransferDialog.close();this.phoneRinging=0;this.phoneIncoming=false;this.phoneCallId="";this.phoneCallExternal=false;this.phoneCallDevice="WEBRTC";this.phoneNumber="";this.phoneNumberUser="";this.phoneParams={};this.phoneCrm={};this.phoneMicMuted=false;this.phoneHolded=false;this.phoneMicAccess=false;this.phoneTransferUser=0;this.phoneTransferEnabled=false};BX.IM.WebRTC.prototype.phoneCommand=function(e,t,s){if(!this.phoneSupport())return false;s=s!=false;t=typeof t=="object"?t:{};BX.ajax({url:this.BXIM.pathToCallAjax+"?PHONE_SHARED&V="+this.BXIM.revision,method:"POST",dataType:"json",timeout:30,async:s,data:{IM_PHONE:"Y",COMMAND:e,PARAMS:JSON.stringify(t),IM_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()}})};BX.IM.WebRTC.prototype.phoneNotifyWait=function(e,t,s,i){if(this.debug)this.phoneLog("incoming call",e,t,s,i);if(!this.phoneSupport()){if(!this.desktop.ready()){this.BXIM.openConfirm(BX.message("IM_CALL_NO_WEBRT"),[new BX.PopupWindowButton({text:BX.message("IM_M_CALL_BTN_DOWNLOAD"),className:"popup-window-button-accept",events:{click:BX.delegate(function(){window.open(BX.browser.IsMac()?"http://dl.bitrix24.com/b24/bitrix24_desktop.dmg":"http://dl.bitrix24.com/b24/bitrix24_desktop.exe","desktopApp");BX.proxy_context.popupWindow.close()},this)}}),new BX.PopupWindowButton({text:BX.message("IM_NOTIFY_CONFIRM_CLOSE"),className:"popup-window-button-decline",events:{click:function(){this.popupWindow.close()}}})])}return false}this.phoneNumberUser=BX.util.htmlspecialchars(s);s=s.replace(/[^a-zA-Z0-9\.]/g,"");if(!this.callActive&&!this.callInit){this.initiator=true;this.callInitUserId=0;this.callInit=true;this.callActive=false;this.callUserId=0;this.callChatId=0;this.callToGroup=0;this.callGroupUsers=[];this.phoneIncoming=true;this.phoneCallId=t;this.phoneNumber=s;this.phoneParams={};this.callOverlayShow({toUserId:this.BXIM.userId,phoneNumber:this.phoneNumber,companyPhoneNumber:i,callTitle:this.phoneNumberUser,fromUserId:0,callToGroup:false,callToPhone:true,video:false,status:BX.message("IM_PHONE_INVITE"),buttons:[{text:BX.message("IM_PHONE_BTN_ANSWER"),className:"bx-messenger-call-overlay-button-answer",events:{click:BX.delegate(function(){this.BXIM.stopRepeatSound("ringtone");this.phoneIncomingAnswer();this.desktop.closeTopmostWindow()},this)}},{text:BX.message("IM_PHONE_BTN_BUSY"),className:"bx-messenger-call-overlay-button-hangup",events:{click:BX.delegate(function(){this.phoneCallFinish();this.callAbort();this.callOverlayClose()},this)}},{text:BX.message("IM_M_CALL_BTN_CHAT"),className:"bx-messenger-call-overlay-button-chat",showInMaximize:true,events:{click:BX.delegate(this.callOverlayToggleSize,this)}},{title:BX.message("IM_M_CALL_BTN_MAXI"),className:"bx-messenger-call-overlay-button-maxi",showInMinimize:true,events:{click:BX.delegate(this.callOverlayToggleSize,this)}}]});this.callOverlayDrawCrm();if(this.callNotify)this.callNotify.adjustPosition();if(!this.BXIM.windowFocus&&this.BXIM.notifyManager.nativeNotifyGranted()){var a={title:BX.message("IM_PHONE_DESC"),text:BX.util.htmlspecialcharsback(this.callOverlayTitle()),icon:this.callUserId?this.messenger.users[this.callUserId].avatar:"",tag:"im-call"};a.onshow=function(){var e=this;setTimeout(function(){e.close()},5e3)};a.onclick=function(){window.focus();this.close()};this.BXIM.notifyManager.nativeNotify(a)}}};BX.IM.WebRTC.prototype.phoneNotifyWaitDesktop=function(e,t,s,i){this.BXIM.ppServerStatus=true;if(!this.callSupport()||!this.desktop.ready())return false;this.phoneNumberUser=BX.util.htmlspecialchars(s);s=s.replace(/[^a-zA-Z0-9\.]/g,"");if(!this.callActive&&!this.callInit){this.initiator=true;this.callInitUserId=0;this.callInit=true;this.callActive=false;this.callUserId=0;this.callChatId=0;this.callToGroup=0;this.callGroupUsers=[];this.phoneIncoming=true;this.phoneCallId=t;this.phoneNumber=s;this.phoneParams={};this.callOverlayShow({prepare:true,toUserId:this.BXIM.userId,phoneNumber:this.phoneNumber,companyPhoneNumber:i,callTitle:this.phoneNumberUser,fromUserId:0,callToGroup:false,callToPhone:true,video:false,status:BX.message("IM_PHONE_INVITE"),buttons:[{text:BX.message("IM_PHONE_BTN_ANSWER"),className:"bx-messenger-call-overlay-button-answer",events:{click:BX.delegate(function(){BX.desktop.onCustomEvent("main","bxPhoneAnswer",[e,t,s]);BX.desktop.windowCommand("close")},this)}},{text:BX.message("IM_PHONE_BTN_BUSY"),className:"bx-messenger-call-overlay-button-hangup",events:{click:BX.delegate(function(){BX.desktop.onCustomEvent("main","bxPhoneSkip",[]);BX.desktop.windowCommand("close")},this)}}]});this.callOverlayDrawCrm();this.desktop.drawOnPlaceholder(this.callOverlay);if(this.phoneCrm&&this.phoneCrm.FOUND)BX.desktop.setWindowPosition({X:STP_CENTER,Y:STP_VCENTER,Width:609,Height:453});else BX.desktop.setWindowPosition({X:STP_CENTER,Y:STP_VCENTER,Width:470,Height:120})}};BX.IM.WebRTC.prototype.openTransferDialog=function(e){if(!this.phoneCurrentCall&&this.phoneCallDevice=="WEBRTC")return false;if(this.popupTransferDialog!=null){this.popupTransferDialog.close();return false}var t=e.bind?e.bind:null;e.maxUsers=1;this.popupTransferDialog=new BX.PopupWindow("bx-messenger-popup-transfer",t,{lightShadow:true,offsetTop:5,offsetLeft:this.desktop.run()?5:-162,autoHide:true,buttons:[new BX.PopupWindowButton({text:BX.message("IM_M_CALL_BTN_TRANSFER"),className:"popup-window-button-accept",events:{click:BX.delegate(function(){this.sendInviteTransfer()},this)}}),new BX.PopupWindowButton({text:BX.message("IM_M_CHAT_BTN_CANCEL"),events:{click:BX.delegate(function(){this.popupTransferDialog.close()},this)}})],closeByEsc:true,zIndex:200,events:{onPopupClose:function(){this.destroy()},onPopupDestroy:BX.delegate(function(){this.popupTransferDialog=null;this.popupTransferDialogContactListElements=null},this)},content:BX.create("div",{props:{className:"bx-messenger-popup-newchat-wrap"},children:[BX.create("div",{props:{className:"bx-messenger-popup-newchat-caption"},html:BX.message("IM_M_CALL_TRANSFER_TEXT")}),BX.create("div",{props:{className:"bx-messenger-popup-newchat-box bx-messenger-popup-newchat-dest bx-messenger-popup-newchat-dest-even"},children:[this.popupTransferDialogDestElements=BX.create("span",{props:{className:"bx-messenger-dest-items"}}),this.popupTransferDialogContactListSearch=BX.create("input",{props:{className:"bx-messenger-input"},attrs:{type:"text",placeholder:BX.message(this.BXIM.bitrixIntranet?"IM_M_SEARCH_PLACEHOLDER_CP":"IM_M_SEARCH_PLACEHOLDER"),value:""}})]}),this.popupTransferDialogContactListElements=BX.create("div",{props:{className:"bx-messenger-popup-newchat-box bx-messenger-popup-newchat-cl bx-messenger-recent-wrap"},children:[]})]})});BX.MessengerCommon.contactListPrepareSearch("popupTransferDialogContactListElements",this.popupTransferDialogContactListElements,this.popupTransferDialogContactListSearch.value,{viewChat:false,viewOfflineWithPhones:true});this.popupTransferDialog.setAngle({offset:this.desktop.run()?20:188});this.popupTransferDialog.show();this.popupTransferDialogContactListSearch.focus();BX.addClass(this.popupTransferDialog.popupContainer,"bx-messenger-mark");BX.bind(this.popupTransferDialog.popupContainer,"click",BX.PreventDefault);BX.bind(this.popupTransferDialogContactListSearch,"keyup",BX.delegate(function(t){if(t.keyCode==16||t.keyCode==17||t.keyCode==18||t.keyCode==20||t.keyCode==244||t.keyCode==224||t.keyCode==91)return false;if(t.keyCode==27&&this.popupTransferDialogContactListSearch.value!="")BX.MessengerCommon.preventDefault(t);if(t.keyCode==27){this.popupTransferDialogContactListSearch.value=""}if(t.keyCode==13){this.popupTransferDialogContactListSearch.value="";var s=BX.findChildByClassName(this.popupTransferDialogContactListElements,"bx-messenger-cl-item");if(s){if(this.popupTransferDialogContactListSearch.value!=""){this.popupTransferDialogContactListSearch.value=""}if(this.phoneTransferUser>0){e.maxUsers=e.maxUsers+1;if(e.maxUsers>0)BX.show(this.popupTransferDialogContactListSearch);this.phoneTransferUser=0}else{if(e.maxUsers>0){e.maxUsers=e.maxUsers-1;if(e.maxUsers<=0)BX.hide(this.popupTransferDialogContactListSearch);this.phoneTransferUser=s.getAttribute("data-userId")}}this.redrawTransferDialogDest()}}BX.MessengerCommon.contactListPrepareSearch("popupTransferDialogContactListElements",this.popupTransferDialogContactListElements,this.popupTransferDialogContactListSearch.value,{viewChat:false,viewOfflineWithPhones:true,timeout:100})},this));BX.bindDelegate(this.popupTransferDialogDestElements,"click",{className:"bx-messenger-dest-del"},BX.delegate(function(){this.phoneTransferUser=0;e.maxUsers=e.maxUsers+1;if(e.maxUsers>0)BX.show(this.popupTransferDialogContactListSearch);this.redrawTransferDialogDest()},this));BX.bindDelegate(this.popupTransferDialogContactListElements,"click",{className:"bx-messenger-cl-item"},BX.delegate(function(t){if(this.popupTransferDialogContactListSearch.value!=""){this.popupTransferDialogContactListSearch.value="";BX.MessengerCommon.contactListPrepareSearch("popupTransferDialogContactListElements",this.popupTransferDialogContactListElements,"",{viewChat:false,viewOfflineWithPhones:true})}if(this.phoneTransferUser>0){e.maxUsers=e.maxUsers+1;this.phoneTransferUser=0}else{if(e.maxUsers<=0)return false;e.maxUsers=e.maxUsers-1;this.phoneTransferUser=BX.proxy_context.getAttribute("data-userId")}if(e.maxUsers<=0)BX.hide(this.popupTransferDialogContactListSearch);else BX.show(this.popupTransferDialogContactListSearch);this.redrawTransferDialogDest();return BX.PreventDefault(t)},this))};BX.IM.WebRTC.prototype.redrawTransferDialogDest=function(){var e="";var t=0;if(this.phoneTransferUser>0){t++;e+='<span class="bx-messenger-dest-block">'+'<span class="bx-messenger-dest-text">'+this.messenger.users[this.phoneTransferUser].name+"</span>"+'<span class="bx-messenger-dest-del" data-userId="'+this.phoneTransferUser+'"></span></span>'}this.popupTransferDialogDestElements.innerHTML=e;this.popupTransferDialogDestElements.parentNode.scrollTop=this.popupTransferDialogDestElements.parentNode.offsetHeight;if(BX.util.even(t))BX.addClass(this.popupTransferDialogDestElements.parentNode,"bx-messenger-popup-newchat-dest-even");else BX.removeClass(this.popupTransferDialogDestElements.parentNode,"bx-messenger-popup-newchat-dest-even");this.popupTransferDialogContactListSearch.focus()};BX.IM.WebRTC.prototype.sendInviteTransfer=function(){if(!this.phoneCurrentCall&&this.phoneCallDevice=="WEBRTC")return false;if(this.phoneTransferUser<=0)return false;if(this.popupTransferDialog)this.popupTransferDialog.close();this.phoneTransferEnabled=true;this.callOverlayStatus(BX.message("IM_M_CALL_ST_TRANSFER"));this.callOverlayButtons([{text:BX.message("IM_M_CALL_BTN_RETURN"),className:"bx-messenger-call-overlay-button-transfer-on",events:{click:BX.delegate(this.cancelInviteTransfer,this)}},{text:BX.message("IM_M_CALL_BTN_CHAT"),className:"bx-messenger-call-overlay-button-chat",showInMaximize:true,events:{click:BX.delegate(this.callOverlayToggleSize,this)}},{title:BX.message("IM_M_CALL_BTN_MAXI"),className:"bx-messenger-call-overlay-button-maxi",showInMinimize:true,events:{click:BX.delegate(this.callOverlayToggleSize,this)}}]);if(this.phoneCallDevice=="WEBRTC"){this.phoneCurrentCall.sendMessage(JSON.stringify({COMMAND:"hold"}))}else{this.phoneCommand("hold",{CALL_ID:this.phoneCallId})}this.phoneCommand("inviteTransfer",{CALL_ID:this.phoneCallId,USER_ID:this.phoneTransferUser});clearTimeout(this.phoneTransferTimeout);this.phoneTransferTimeout=setTimeout(BX.delegate(function(){this.phoneCommand("timeoutTransfer",{CALL_ID:this.phoneCallId});this.errorInviteTransfer()},this),2e4)};BX.IM.WebRTC.prototype.cancelInviteTransfer=function(){if(!this.phoneCurrentCall&&this.phoneCallDevice=="WEBRTC")return false;if(this.phoneTransferUser<=0)return false;this.phoneTransferUser=0;this.callOverlayStatus(BX.message("IM_M_CALL_ST_ONLINE"));this.callOverlayButtons(this.callOverlayCallConnectedButtons);if(this.phoneCallDevice=="WEBRTC"){this.phoneCurrentCall.sendMessage(JSON.stringify({COMMAND:"unhold"}))}else{this.phoneCommand("unhold",{CALL_ID:this.phoneCallId})}if(this.phoneTransferEnabled)this.phoneCommand("cancelTransfer",{CALL_ID:this.phoneCallId});clearTimeout(this.phoneTransferTimeout);this.phoneTransferTimeout=null;this.phoneTransferEnabled=false};BX.IM.WebRTC.prototype.errorInviteTransfer=function(){if(this.phoneTransferUser<=0)return false;this.callOverlayStatus(BX.message("IM_M_CALL_ST_TRANSFER_1"));this.BXIM.playSound("error",true);clearTimeout(this.phoneTransferTimeout);this.phoneTransferTimeout=null;this.phoneTransferEnabled=false};BX.IM.WebRTC.prototype.successInviteTransfer=function(){if(this.phoneTransferUser<=0)return false;clearTimeout(this.phoneTransferTimeout);this.phoneTransferTimeout=null;this.phoneTransferEnabled=false;if(this.phoneCallDevice=="PHONE"){this.phoneCallFinish();this.callOverlayDeleteEvents();this.callOverlayClose();this.BXIM.playSound("stop")}};BX.IM.WebRTC.prototype.waitInviteTransfer=function(){clearTimeout(this.phoneTransferTimeout);this.phoneTransferTimeout=setTimeout(BX.delegate(function(){this.phoneCommand("timeoutTransfer",{CALL_ID:this.phoneCallId});this.errorInviteTransfer()},this),3e4)};BX.IM.WebRTC.prototype.phoneLog=function(){if(this.desktop.ready()){var e="";for(var t=0;t<arguments.length;t++){e=e+" | "+(typeof arguments[t]=="object"?JSON.stringify(arguments[t]):arguments[t])}BX.desktop.log("phone."+this.BXIM.userEmail+".log",e.substr(3));

}if(this.debug){if(console)console.log("Phone Log",JSON.stringify(arguments))}};BX.IM.ScreenSharing=function(e,t){t=t||{};this.webrtc=e;this.BXIM=this.webrtc.BXIM;this.debug=true;this.sdpConstraints={mandatory:{OfferToReceiveAudio:false,OfferToReceiveVideo:true}};this.oneway=true;this.sourceSelf=null;this.sourceApponent=null;this.callWindowBeforeUnload=null;BX.addCustomEvent("onImCallEnd",BX.delegate(function(e,t){this.callDecline(false)},this));BX.addCustomEvent("onPullEvent-im",BX.delegate(function(e,t){if(e=="screenSharing"){if(t.command=="inactive"){this.callDecline(false)}else if(!this.webrtc.callActive||this.webrtc.callUserId!=t.senderId){this.callCommand("inactive")}else{this.log("Incoming",t.command,t.senderId,JSON.stringify(t));if(t.command=="invite"){if(this.callInit){this.deleteEvents()}this.initiator=false;this.callVideo=true;this.callInit=true;this.callUserId=t.senderId;this.callInitUserId=t.senderId;this.callAnswer()}else if(t.command=="answer"&&this.initiator){this.startScreenSharing()}else if(t.command=="decline"){this.callDecline()}else if(t.command=="ready"){this.log("Apponent "+t.senderId+" ready!");this.connected[t.senderId]=true}else if(t.command=="reconnect"){clearTimeout(this.pcConnectTimeout[t.senderId]);clearTimeout(this.initPeerConnectionTimeout[t.senderId]);if(this.pc[t.senderId])this.pc[t.senderId].close();delete this.pc[t.senderId];delete this.pcStart[t.senderId];if(this.callStreamMain==this.callStreamUsers[t.senderId])this.callStreamMain=null;this.callStreamUsers[t.senderId]=null;this.initPeerConnection(t.senderId)}else if(t.command=="signaling"&&this.callActive){this.signalingPeerData(t.senderId,t.peer)}else{this.log('Command "'+t.command+'" skip')}}}},this));BX.garbage(function(){if(this.callInit){this.callCommand("decline",true)}},this)};if(BX.inheritWebrtc)BX.inheritWebrtc(BX.IM.ScreenSharing);BX.IM.ScreenSharing.prototype.startScreenSharing=function(){var e={chromeMediaSource:"screen",googLeakyBucket:true,maxWidth:2560,maxHeight:1440,minWidth:960,minHeight:540,maxFrameRate:5};this.startGetUserMedia(e,false)};BX.IM.ScreenSharing.prototype.onUserMediaSuccess=function(e){var t=this.parent.onUserMediaSuccess.apply(this,arguments);if(!t)return false;if(this.initiator){this.attachMediaStream(this.webrtc.callOverlayVideoSelf,this.callStreamSelf)}this.callCommand("ready");return true};BX.IM.ScreenSharing.prototype.onUserMediaError=function(e){var t=this.parent.onUserMediaError.apply(this,arguments);if(!t)return false;this.callDecline();return true};BX.IM.ScreenSharing.prototype.setLocalAndSend=function(e,t){var s=this.parent.setLocalAndSend.apply(this,arguments);if(!s)return false;BX.ajax({url:this.BXIM.pathToCallAjax+"?CALL_SIGNALING",method:"POST",dataType:"json",timeout:30,data:{IM_SHARING:"Y",COMMAND:"signaling",USER_ID:e,PEER:JSON.stringify(t),IM_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()}});return true};BX.IM.ScreenSharing.prototype.onRemoteStreamAdded=function(e,t,s){if(!s)return false;BX.addClass(this.webrtc.callOverlay,"bx-messenger-call-overlay-screen-sharing");this.attachMediaStream(this.webrtc.callOverlayVideoReserve,this.webrtc.callStreamMain);this.webrtc.callOverlayVideoReserve.play();this.attachMediaStream(this.webrtc.callOverlayVideoMain,this.callStreamMain);this.webrtc.callOverlayVideoMain.play();return true};BX.IM.ScreenSharing.prototype.onRemoteStreamRemoved=function(e,t){};BX.IM.ScreenSharing.prototype.onIceCandidate=function(e,t){BX.ajax({url:this.BXIM.pathToCallAjax+"?CALL_SIGNALING",method:"POST",dataType:"json",timeout:30,data:{IM_SHARING:"Y",COMMAND:"signaling",USER_ID:e,PEER:JSON.stringify(t),IM_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()}})};BX.IM.ScreenSharing.prototype.peerConnectionError=function(e,t){this.callDecline()};BX.IM.ScreenSharing.prototype.peerConnectionReconnect=function(e){var t=this.parent.peerConnectionReconnect.apply(this,arguments);if(!t)return false;BX.ajax({url:this.BXIM.pathToCallAjax+"?CALL_RECONNECT",method:"POST",dataType:"json",timeout:30,data:{IM_SHARING:"Y",COMMAND:"reconnect",USER_ID:e,IM_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()},onsuccess:BX.delegate(function(){this.initPeerConnection(e,true)},this)});return true};BX.IM.ScreenSharing.prototype.deleteEvents=function(){BX.removeClass(this.webrtc.callOverlay,"bx-messenger-call-overlay-screen-sharing");this.webrtc.callOverlayVideoReserve.src="";this.attachMediaStream(this.webrtc.callOverlayVideoSelf,this.webrtc.callStreamSelf);this.attachMediaStream(this.webrtc.callOverlayVideoMain,this.webrtc.callStreamMain);this.webrtc.callOverlayVideoMain.play();this.webrtc.callOverlayVideoSelf.play();this.parent.deleteEvents.apply(this,arguments);var e=BX.findChildByClassName(BX("bx-messenger-call-overlay-button-screen"),"bx-messenger-call-overlay-button-screen");if(e)BX.removeClass(e,"bx-messenger-call-overlay-button-screen-off");return true};BX.IM.ScreenSharing.prototype.callInvite=function(){if(this.callInit){this.deleteEvents()}this.initiator=true;this.callVideo=true;this.callInit=true;this.callActive=true;this.callUserId=this.webrtc.callUserId;this.callInitUserId=BXIM.userId;this.callCommand("invite");var e=BX.findChildByClassName(BX("bx-messenger-call-overlay-button-screen"),"bx-messenger-call-overlay-button-screen");if(e)BX.addClass(e,"bx-messenger-call-overlay-button-screen-off")};BX.IM.ScreenSharing.prototype.callAnswer=function(){this.callActive=true;this.startGetUserMedia();this.callCommand("answer")};BX.IM.ScreenSharing.prototype.callDecline=function(e){if(!this.callInit)return false;e=e===false?false:true;if(e){this.callCommand("decline")}this.deleteEvents()};BX.IM.ScreenSharing.prototype.callCommand=function(e,t){if(!this.signalingReady())return false;BX.ajax({url:this.BXIM.pathToCallAjax+"?CALL_COMMAND",method:"POST",dataType:"json",timeout:30,async:t!=false,data:{IM_SHARING:"Y",COMMAND:e,USER_ID:this.callUserId,IM_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()}})};BX.IM.DiskManager=function(e,t){this.BXIM=e;this.notify=t.notifyClass;this.desktop=t.desktopClass;this.enable=t.enable;this.lightVersion=BX.browser.IsIE8();this.formBlocked={};this.formAgents={};this.files=t.files;this.filesProgress={};this.filesMessage={};this.filesRegister={};this.fileTmpId=1;this.timeout={};BX.garbage(function(){var e={};var t=0;for(var s in this.filesMessage){e[s]=this.filesMessage[s];if(this.messenger.message[e[s]]){t=this.messenger.message[e[s]].chatId}}if(t>0){BX.ajax({url:this.BXIM.pathToFileAjax+"?FILE_TERMINATE&V="+this.BXIM.revision,method:"POST",dataType:"json",timeout:30,async:false,data:{IM_FILE_UNREGISTER:"Y",CHAT_ID:t,FILES:JSON.stringify(this.filesProgress),MESSAGES:JSON.stringify(e),IM_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()}})}},this)};BX.IM.DiskManager.prototype.drawHistoryFiles=function(e,t,s){if(!this.enable)return[];if(typeof this.files[e]=="undefined")return[];var i=[];if(typeof t!="object"){t=parseInt(t);if(typeof this.files[e][t]=="undefined")return[];i.push(t)}else{i=t}s=s||{};var a=this.desktop.ready()?"desktop":"default";var n=true;var o=[];for(var r=0;r<i.length;r++){var l=this.files[e][i[r]];if(!l)continue;if(!(l.status=="done"||l.status=="error"))continue;var p=BX.MessengerCommon.formatDate(l.date,[["tommorow","tommorow"],["today","today"],["yesterday","yesterday"],["",BX.date.convertBitrixFormat(BX.message("FORMAT_DATE"))]]);var h=BX.create("span",{props:{className:"bx-messenger-file-user"},children:[BX.create("span",{props:{className:"bx-messenger-file-author"},html:this.messenger.users[l.authorId]?this.messenger.users[l.authorId].name:l.authorName}),BX.create("span",{props:{className:"bx-messenger-file-date"},html:p})]});var c=null;if(l.type=="image"&&(l.preview||l.urlPreview[a])){if(l.urlPreview[a]){var u=BX.create("img",{attrs:{src:l.urlPreview[a]},props:{className:"bx-messenger-file-image-text"}})}else if(l.preview&&typeof l.preview!="string"){var u=l.preview}else{var u=BX.create("img",{attrs:{src:l.preview},props:{className:"bx-messenger-file-image-text"}})}if(n&&l.urlShow[a]){c=BX.create("div",{props:{className:"bx-messenger-file-preview"},children:[BX.create("span",{props:{className:"bx-messenger-file-image"},children:[BX.create("a",{attrs:{href:l.urlShow[a],target:"_blank"},props:{className:"bx-messenger-file-image-src"},children:[u]})]}),BX.create("br")]})}else{c=BX.create("div",{props:{className:"bx-messenger-file-preview"},children:[BX.create("span",{props:{className:"bx-messenger-file-image"},children:[BX.create("span",{props:{className:"bx-messenger-file-image-src"},children:[u]})]}),BX.create("br")]})}}var d=l.name;if(d.length>23){d=d.substr(0,10)+"..."+d.substr(d.length-10,d.length)}var m=BX.create("span",{attrs:{title:l.name},props:{className:"bx-messenger-file-title"},html:d});if(n&&(l.urlShow[a]||l.urlDownload[a])){m=BX.create("a",{props:{className:"bx-messenger-file-title-href"},attrs:{href:l.urlShow[a]?l.urlShow[a]:l.urlDownload[a],target:"_blank"},children:[m]})}m=BX.create("div",{props:{className:"bx-messenger-file-attrs"},children:[m,BX.create("span",{props:{className:"bx-messenger-file-size"},html:BX.UploaderUtils.getFormattedSize(l.size)}),BX.create("span",{attrs:{title:BX.message("IM_F_MENU")},props:{className:"bx-messenger-file-menu"}})]});var g=null;if(l.status=="error"){g=BX.create("span",{props:{className:"bx-messenger-file-status-error"},html:l.errorText?l.errorText:BX.message("IM_F_ERROR")})}if(i.length==1&&s.showInner=="Y"){o=[h,m,c,g]}else{o.push(BX.create("div",{attrs:{id:"im-file-history-panel-"+l.id,"data-chatId":l.chatId,"data-fileId":l.id},props:{className:"bx-messenger-file"},children:[h,m,c,g]}))}if(i.length==1&&s.getElement=="Y"){o=o[0]}}return o};BX.IM.DiskManager.prototype.chatDialogInit=function(){if(!this.messenger.popupMessengerFileFormInput||!BX.Uploader)return false;this.formAgents["imDialog"]=BX.Uploader.getInstance({id:"imDialog",allowUpload:"A",uploadMethod:"deferred",showImage:true,filesInputMultiple:true,input:this.messenger.popupMessengerFileFormInput,dropZone:this.messenger.popupMessengerBodyDialog,fields:{preview:{params:{width:212,height:119}}}});BX.addCustomEvent(this.formAgents["imDialog"].dropZone,"dragEnter",BX.delegate(function(){if(parseInt(this.messenger.popupMessengerFileFormChatId.value)<=0||this.messenger.popupMessengerFileFormInput.getAttribute("disabled"))return false;BX.style(this.messenger.popupMessengerFileDropZone,"display","block");BX.style(this.messenger.popupMessengerFileDropZone,"width",this.messenger.popupMessengerBodyDialog.offsetWidth-2+"px");BX.style(this.messenger.popupMessengerFileDropZone,"height",this.messenger.popupMessengerBodyDialog.offsetHeight-2+"px");clearTimeout(this.messenger.popupMessengerFileDropZoneTimeout);this.messenger.popupMessengerFileDropZoneTimeout=setTimeout(BX.delegate(function(){BX.addClass(this.messenger.popupMessengerFileDropZone,"bx-messenger-file-dropzone-active")},this),10)},this));BX.addCustomEvent(this.formAgents["imDialog"].dropZone,"dragLeave",BX.delegate(function(){BX.removeClass(this.messenger.popupMessengerFileDropZone,"bx-messenger-file-dropzone-active");clearTimeout(this.messenger.popupMessengerFileDropZoneTimeout);this.messenger.popupMessengerFileDropZoneTimeout=setTimeout(BX.delegate(function(){BX.style(this.messenger.popupMessengerFileDropZone,"display","none");BX.style(this.messenger.popupMessengerFileDropZone,"width",0);BX.style(this.messenger.popupMessengerFileDropZone,"height",0)},this),300)},this));BX.addCustomEvent(this.formAgents["imDialog"],"onError",BX.delegate(BX.MessengerCommon.diskChatDialogUploadError,BX.MessengerCommon));BX.addCustomEvent(this.formAgents["imDialog"],"onFileinputIsReinited",BX.delegate(function(e){if(!e&&!this.formAgents["imDialog"].fileInput)return false;this.messenger.popupMessengerFileFormInput=e?e:this.formAgents["imDialog"].fileInput;if(parseInt(this.messenger.popupMessengerFileFormChatId.value)<=0){this.messenger.popupMessengerFileFormInput.setAttribute("disabled",true)}},this));BX.addCustomEvent(this.formAgents["imDialog"],"onFileIsInited",BX.delegate(function(e,t,s){BX.MessengerCommon.diskChatDialogFileInited(e,t,s);BX.addCustomEvent(t,"onUploadStart",BX.delegate(BX.MessengerCommon.diskChatDialogFileStart,BX.MessengerCommon));BX.addCustomEvent(t,"onUploadProgress",BX.delegate(BX.MessengerCommon.diskChatDialogFileProgress,BX.MessengerCommon));BX.addCustomEvent(t,"onUploadDone",BX.delegate(BX.MessengerCommon.diskChatDialogFileDone,BX.MessengerCommon));BX.addCustomEvent(t,"onUploadError",BX.delegate(BX.MessengerCommon.diskChatDialogFileError,BX.MessengerCommon))},this));if(BX.DiskFileDialog){if(!this.flagFileDialogInited){BX.addCustomEvent(BX.DiskFileDialog,"inited",BX.proxy(this.initEventFileDialog,this))}BX.addCustomEvent(BX.DiskFileDialog,"loadItems",BX.delegate(function(e,t){if(t!="im-file-dialog")return false;BX.DiskFileDialog.target[t]=e.replace("/bitrix/tools/disk/uf.php",this.BXIM.pathToFileAjax)},this))}};BX.IM.DiskManager.prototype.saveToDisk=function(e,t,s){if(!this.files[e]||!this.files[e][t])return false;if(this.files[e][t].saveToDiskBlock)return false;s=s||{};this.files[e][t].saveToDiskBlock=true;var i=s.boxId?s.boxId:"im-file";var a=BX(i+"-"+t);var n=BX.findChildByClassName(a,"bx-messenger-file-download-disk");if(n){BX.addClass(n,"bx-messenger-file-download-block");n.innerHTML=BX.message("IM_SAVING")}else if(i=="im-file-history-panel"){n=BX.findChildByClassName(a,"bx-messenger-file-date");if(n){BX.addClass(n.parentNode.parentNode,"bx-messenger-file-download-block");n.setAttribute("data-date",n.innerHTML);n.innerHTML=BX.message("IM_SAVING")}}BX.ajax({url:this.BXIM.pathToFileAjax+"?FILE_SAVE_TO_DISK&V="+this.BXIM.revision,method:"POST",dataType:"json",timeout:30,data:{IM_FILE_SAVE_TO_DISK:"Y",CHAT_ID:e,FILE_ID:t,IM_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()},onsuccess:BX.delegate(function(s){this.files[e][t].saveToDiskBlock=false;var a=BX(i+"-"+t);var n=BX.findChildByClassName(a,"bx-messenger-file-download-disk");if(n){BX.removeClass(n,"bx-messenger-file-download-block");n.innerHTML=BX.message("IM_F_DOWNLOAD_DISK")}else if(i=="im-file-history-panel"){n=BX.findChildByClassName(a,"bx-messenger-file-date");if(n){BX.removeClass(n.parentNode.parentNode,"bx-messenger-file-download-block");n.innerHTML=n.getAttribute("data-date")}n=BX.findChildByClassName(a,"bx-messenger-file-title")}if(n&&s.ERROR!=""){this.messenger.tooltip(n,BX.message("IM_F_SAVE_OK"))}else{this.messenger.tooltip(n,BX.message("IM_F_SAVE_ERR"))}},this),onfailure:BX.delegate(function(){this.files[e][t].saveToDiskBlock=false;var s=BX(i+"-"+t);var a=BX.findChildByClassName(s,"bx-messenger-file-download-disk");if(a){BX.removeClass(a,"bx-messenger-file-download-block");a.innerHTML=BX.message("IM_F_DOWNLOAD_DISK");this.messenger.tooltip(a,BX.message("IM_F_SAVE_ERR"))}else if(i=="im-file-history-panel"){a=BX.findChildByClassName(s,"bx-messenger-file-date");if(a){BX.removeClass(a.parentNode.parentNode,"bx-messenger-file-download-block");a.innerHTML=a.getAttribute("data-date")}}},this)})};BX.IM.DiskManager.prototype.openFileDialog=function(){this.messenger.setClosingByEsc(false);BX.ajax({url:this.BXIM.pathToFileAjax+"?action=selectFile&dialogName=im-file-dialog",method:"GET",timeout:30,onsuccess:BX.delegate(function(e){if(typeof e=="object"&&e.error){this.messenger.setClosingByEsc(true)}},this),onfailure:BX.delegate(function(){this.messenger.setClosingByEsc(true)},this)})};BX.IM.DiskManager.prototype.initEventFileDialog=function(e){if(e!="im-file-dialog"||!BX.DiskFileDialog)return false;this.flagFileDialogInited=true;BX.DiskFileDialog.obCallback[e]={saveButton:BX.delegate(function(e,t,s){this.uploadFromDisk(e,t,s)},this),popupShow:BX.delegate(function(){BX.bind(BX.DiskFileDialog.popupWindow.popupContainer,"click",BX.MessengerCommon.preventDefault);this.messenger.setClosingByEsc(false)},this),popupDestroy:BX.delegate(function(){this.messenger.setClosingByEsc(true)},this)};BX.DiskFileDialog.openDialog(e)};BX.IM.DiskManager.prototype.uploadFromDisk=function(e,t,s){var i=this.messenger.popupMessengerFileFormChatId.value;if(!this.files[i])this.files[i]={};var a=[];for(var n in s){var o=n.replace("n","");this.files[i]["disk"+o]={id:"disk"+o,tempId:"disk"+o,chatId:i,date:s[n].modifyDateInt,type:"file",preview:"",name:s[n].name,size:s[n].sizeInt,status:"upload",progress:-1,authorId:this.BXIM.userId,authorName:this.messenger.users[this.BXIM.userId].name,urlPreview:"",urlShow:"",urlDownload:""};a.push("disk"+o)}var r=0;if(this.messenger.chat[i]){r="chat"+i}else{for(var l in this.messenger.userChat){if(this.messenger.userChat[l]==i){r=l;break}}}if(!r)return false;var p="tempFile"+this.fileTmpId;this.messenger.message[p]={id:p,chatId:i,senderId:this.BXIM.userId,recipientId:r,date:BX.MessengerCommon.getNowDate(),text:"",params:{FILE_ID:a}};if(!this.messenger.showMessage[r])this.messenger.showMessage[r]=[];this.messenger.showMessage[r].push(p);BX.MessengerCommon.drawMessage(r,this.messenger.message[p]);BX.MessengerCommon.drawProgessMessage(p);this.messenger.sendMessageFlag++;this.messenger.popupMessengerFileFormInput.setAttribute("disabled",true);BX.ajax({url:this.BXIM.pathToFileAjax+"?FILE_UPLOAD_FROM_DISK&V="+this.BXIM.revision,method:"POST",dataType:"json",timeout:30,data:{IM_FILE_UPLOAD_FROM_DISK:"Y",CHAT_ID:i,RECIPIENT_ID:r,MESSAGE_TMP_ID:p,FILES:JSON.stringify(a),IM_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()},onsuccess:BX.delegate(function(e){if(e.ERROR!=""){this.messenger.sendMessageFlag--;delete this.messenger.message[p];BX.MessengerCommon.drawTab(r);return false}this.messenger.sendMessageFlag--;var t=[];var s={};for(var i in e.FILES){var a=e.FILES[i];if(parseInt(a.id)>0){this.files[e.CHAT_ID][a.id]=a;delete this.files[e.CHAT_ID][i];if(BX("im-file-"+i)){BX("im-file-"+i).setAttribute("data-fileId",a.id);BX("im-file-"+i).id="im-file-"+a.id;BX.MessengerCommon.diskRedrawFile(e.CHAT_ID,a.id)}t.push(a.id)}else{this.files[e.CHAT_ID][i]["status"]="error";BX.MessengerCommon.diskRedrawFile(e.CHAT_ID,i)}}this.messenger.message[e.MESSAGE_ID]=BX.clone(this.messenger.message[e.MESSAGE_TMP_ID]);this.messenger.message[e.MESSAGE_ID]["id"]=e.MESSAGE_ID;this.messenger.message[e.MESSAGE_ID]["params"]["FILE_ID"]=t;if(this.messenger.popupMessengerLastMessage==e.MESSAGE_TMP_ID)this.messenger.popupMessengerLastMessage=e.MESSAGE_ID;delete this.messenger.message[e.MESSAGE_TMP_ID];var n=BX.util.array_search(""+e.MESSAGE_TMP_ID+"",this.messenger.showMessage[e.RECIPIENT_ID]);if(this.messenger.showMessage[e.RECIPIENT_ID][n])this.messenger.showMessage[e.RECIPIENT_ID][n]=""+e.MESSAGE_ID+"";if(BX("im-message-"+e.MESSAGE_TMP_ID)){BX("im-message-"+e.MESSAGE_TMP_ID).id="im-message-"+e.MESSAGE_ID;var o=BX.findChild(this.messenger.popupMessengerBodyWrap,{attribute:{"data-messageid":""+e.MESSAGE_TMP_ID}},true);if(o){o.setAttribute("data-messageid",""+e.MESSAGE_ID+"");if(o.getAttribute("data-blockmessageid")==""+e.MESSAGE_TMP_ID)o.setAttribute("data-blockmessageid",""+e.MESSAGE_ID+"")}else{var l=BX.findChild(this.messenger.popupMessengerBodyWrap,{attribute:{"data-blockmessageid":""+e.MESSAGE_TMP_ID}},true);if(l){l.setAttribute("data-blockmessageid",""+e.MESSAGE_ID+"")}}var h=BX.findChildByClassName(o,"bx-messenger-content-item-date");if(h)h.innerHTML=" &nbsp; "+BX.MessengerCommon.formatDate(this.messenger.message[e.MESSAGE_ID].date,BX.MessengerCommon.getDateFormatType("MESSAGE"))}BX.MessengerCommon.clearProgessMessage(e.MESSAGE_ID);if(this.messenger.history[e.RECIPIENT_ID])this.messenger.history[e.RECIPIENT_ID].push(e.MESSAGE_ID);else this.messenger.history[e.RECIPIENT_ID]=[e.MESSAGE_ID];if(BX.MessengerCommon.enableScroll(this.messenger.popupMessengerBody,this.messenger.popupMessengerBody.offsetHeight)){if(this.BXIM.animationSupport){if(this.messenger.popupMessengerBodyAnimation!=null)this.messenger.popupMessengerBodyAnimation.stop();(this.messenger.popupMessengerBodyAnimation=new BX.easing({duration:800,start:{scroll:this.messenger.popupMessengerBody.scrollTop},finish:{scroll:this.messenger.popupMessengerBody.scrollHeight-this.messenger.popupMessengerBody.offsetHeight},transition:BX.easing.makeEaseInOut(BX.easing.transitions.quart),step:BX.delegate(function(e){this.messenger.popupMessengerBody.scrollTop=e.scroll},this)})).animate()}else{this.messenger.popupMessengerBody.scrollTop=this.messenger.popupMessengerBody.scrollHeight-this.messenger.popupMessengerBody.offsetHeight}}this.messenger.popupMessengerFileFormInput.removeAttribute("disabled")},this),onfailure:BX.delegate(function(){this.messenger.sendMessageFlag--;delete this.messenger.message[p];BX.MessengerCommon.drawTab(r)},this)});this.fileTmpId++};BX.IM.DiskManager.prototype.chatAvatarInit=function(){if(!BX.Uploader)return false;if(this.messenger.popupMessengerPanelAvatarUpload2){this.formAgents["popupMessengerPanelAvatarUpload2"]=BX.Uploader.getInstance({id:"popupMessengerPanelAvatarUpload2",allowUpload:"I",uploadMethod:"immediate",showImage:false,input:this.messenger.popupMessengerPanelAvatarUpload2,dropZone:this.messenger.popupMessengerPanelAvatarUpload2.parentNode});BX.addCustomEvent(this.formAgents["popupMessengerPanelAvatarUpload2"],"onFileinputIsReinited",BX.delegate(function(e){if(!e&&!this.formAgents["popupMessengerPanelAvatarUpload2"].fileInput)return false;this.messenger.popupMessengerPanelAvatarUpload2=e?e:this.formAgents["popupMessengerPanelAvatarUpload2"].fileInput},this));BX.addCustomEvent(this.formAgents["popupMessengerPanelAvatarUpload2"],"onFileIsInited",BX.delegate(function(e,t,s){this.chatAvatarAttached(s);BX.addCustomEvent(t,"onUploadDone",BX.delegate(this.chatAvatarDone,this));BX.addCustomEvent(t,"onUploadError",BX.delegate(this.chatAvatarError,this))},this))}if(this.messenger.popupMessengerPanelAvatarUpload3){this.formAgents["popupMessengerPanelAvatarUpload3"]=BX.Uploader.getInstance({id:"popupMessengerPanelAvatarUpload3",allowUpload:"I",uploadMethod:"immediate",showImage:false,input:this.messenger.popupMessengerPanelAvatarUpload3,dropZone:this.messenger.popupMessengerPanelAvatarUpload3.parentNode});BX.addCustomEvent(this.formAgents["popupMessengerPanelAvatarUpload3"],"onFileinputIsReinited",BX.delegate(function(e){if(!e&&!this.formAgents["popupMessengerPanelAvatarUpload3"].fileInput)return false;this.messenger.popupMessengerPanelAvatarUpload3=e?e:this.formAgents["popupMessengerPanelAvatarUpload3"].fileInput},this));BX.addCustomEvent(this.formAgents["popupMessengerPanelAvatarUpload3"],"onFileIsInited",BX.delegate(function(e,t,s){this.chatAvatarAttached(s);BX.addCustomEvent(t,"onUploadDone",BX.delegate(this.chatAvatarDone,this));BX.addCustomEvent(t,"onUploadError",BX.delegate(this.chatAvatarError,this))},this))}};BX.IM.DiskManager.prototype.avatarFormIsBlocked=function(e,t,s){result=this.formBlocked[t+"_"+e]?true:false;element=this.messenger[t];if(this.messenger.currentTab=="chat"+e){if(element){if(result){element.title="";element.disabled=true}else{element.title=BX.message("IM_M_AVATAR_UPLOAD");element.removeAttribute("disabled")}}if(s){if(result){BX.addClass(s.firstChild,"bx-messenger-panel-avatar-progress-on")}else{BX.removeClass(s.firstChild,"bx-messenger-panel-avatar-progress-on")}BX.removeClass(s,"bx-messenger-panel-avatar-upload-error")}}return result};BX.IM.DiskManager.prototype.chatAvatarAttached=function(e){this.formBlocked[e.id+"_"+e.form.CHAT_ID.value]=true;this.avatarFormIsBlocked(e.form.CHAT_ID.value,e.id,e.form)};BX.IM.DiskManager.prototype.chatAvatarDone=function(e,t,s,i){this.formBlocked[s.id+"_"+t.file.chatId]=false;this.avatarFormIsBlocked(t.file.chatId,s.id,s.form);this.messenger.updateChatAvatar(t.file.chatId,t.file.chatAvatar)};BX.IM.DiskManager.prototype.chatAvatarError=function(e,t,s,i){formFields=s.streams.packages.getItem(i).data;this.formBlocked[s.id+"_"+formFields.CHAT_ID]=false;this.avatarFormIsBlocked(formFields.CHAT_ID,s.id,s.form);BX.addClass(s.form,"bx-messenger-panel-avatar-upload-error");s.fileInput.title=t.error};BX.IM.NotifyManager=function(e){this.stack=[];this.stackTimeout=null;this.stackPopup={};this.stackPopupTimeout={};this.stackPopupTimeout2={};this.stackPopupId=0;this.stackOverflow=false;this.blockNativeNotify=false;this.blockNativeNotifyTimeout=null;this.notifyShow=0;this.notifyHideTime=5e3;this.notifyHeightCurrent=10;this.notifyHeightMax=0;this.notifyGarbageTimeout=null;this.notifyAutoHide=true;this.notifyAutoHideTimeout=null;if(BX.browser.SupportLocalStorage()){BX.addCustomEvent(window,"onLocalStorageSet",BX.proxy(this.storageSet,this))}this.BXIM=e};BX.IM.NotifyManager.prototype.storageSet=function(e){if(e.key=="mnnb"){this.blockNativeNotify=true;clearTimeout(this.blockNativeNotifyTimeout);this.blockNativeNotifyTimeout=setTimeout(BX.delegate(function(){this.blockNativeNotify=false},this),1e3)}};BX.IM.NotifyManager.prototype.add=function(e){if(typeof e!="object"||!e.html)return false;if(BX.type.isDomNode(e.html))e.html=e.html.outerHTML;this.stack.push(e);if(!this.stackOverflow)this.setShowTimer(300)};BX.IM.NotifyManager.prototype.remove=function(e){delete this.stack[e]};BX.IM.NotifyManager.prototype.draw=function(){this.show()};BX.IM.NotifyManager.prototype.show=function(){this.notifyHeightMax=document.body.offsetHeight;var e=BX.GetWindowScrollPos();for(var t=0;t<this.stack.length;t++){if(typeof this.stack[t]=="undefined")continue;var s=new BX.PopupWindow("bx-im-notify-flash-"+this.stackPopupId,{top:"-1000px",left:0},{lightShadow:true,zIndex:200,events:{onPopupClose:BX.delegate(function(){BX.proxy_context.popupContainer.style.opacity=0;this.notifyShow--;this.notifyHeightCurrent-=BX.proxy_context.popupContainer.offsetHeight+10;this.stackOverflow=false;setTimeout(BX.delegate(function(){this.destroy()},BX.proxy_context),1500)},this),onPopupDestroy:BX.delegate(function(){BX.unbindAll(BX.findChildByClassName(BX.proxy_context.popupContainer,"bx-notifier-item-delete"));BX.unbindAll(BX.proxy_context.popupContainer);delete this.stackPopup[BX.proxy_context.uniquePopupId];delete this.stackPopupTimeout[BX.proxy_context.uniquePopupId];delete this.stackPopupTimeout2[BX.proxy_context.uniquePopupId]},this)},bindOnResize:false,content:BX.create("div",{props:{className:"bx-notifyManager-item"},html:this.stack[t].html})});s.notifyParams=this.stack[t];s.notifyParams.id=t;s.show();BX.onCustomEvent(window,"onNotifyManagerShow",[this.stack[t]]);s.popupContainer.style.left=document.body.offsetWidth-s.popupContainer.offsetWidth-10+"px";s.popupContainer.style.opacity=0;if(this.notifyHeightMax<this.notifyHeightCurrent+s.popupContainer.offsetHeight+10){if(this.notifyShow>0){s.destroy();this.stackOverflow=true;break}}BX.addClass(s.popupContainer,"bx-notifyManager-animation");s.popupContainer.style.opacity=1;s.popupContainer.style.top=e.scrollTop+this.notifyHeightCurrent+"px";this.notifyHeightCurrent=this.notifyHeightCurrent+s.popupContainer.offsetHeight+10;this.stackPopupId++;this.notifyShow++;this.remove(t);this.stackPopupTimeout[s.uniquePopupId]=null;BX.bind(s.popupContainer,"mouseover",BX.delegate(function(){this.clearAutoHide()},this));BX.bind(s.popupContainer,"mouseout",BX.delegate(function(){this.setAutoHide(this.notifyHideTime/2)},this));BX.bind(s.popupContainer,"contextmenu",BX.delegate(function(e){if(this.stackPopup[BX.proxy_context.id].notifyParams.tag)this.closeByTag(this.stackPopup[BX.proxy_context.id].notifyParams.tag);else this.stackPopup[BX.proxy_context.id].close();return BX.PreventDefault(e)},this));var i=BX.findChildren(s.popupContainer,{tagName:"a"},true);for(var a=0;a<i.length;a++){if(i[a].href!="#")i[a].target="_blank"}BX.bind(BX.findChildByClassName(s.popupContainer,"bx-notifier-item-delete"),"click",BX.delegate(function(e){var t=BX.proxy_context.parentNode.parentNode.parentNode.parentNode.id.replace("popup-window-content-","");if(this.stackPopup[t].notifyParams.close)this.stackPopup[t].notifyParams.close(this.stackPopup[t]);this.stackPopup[t].close();if(this.notifyAutoHide==false){this.clearAutoHide();this.setAutoHide(this.notifyHideTime/2)}return BX.PreventDefault(e)},this));BX.bindDelegate(s.popupContainer,"click",{className:"bx-notifier-item-button"},BX.delegate(function(e){var t=BX.proxy_context.getAttribute("data-id");this.BXIM.notify.confirmRequest({notifyId:t,notifyValue:BX.proxy_context.getAttribute("data-value"),notifyURL:BX.proxy_context.getAttribute("data-url"),notifyTag:this.BXIM.notify.notify[t]&&this.BXIM.notify.notify[t].tag?this.BXIM.notify.notify[t].tag:null,groupDelete:BX.proxy_context.getAttribute("data-group")!=null},true);for(var s in this.stackPopup){if(this.stackPopup[s].notifyParams.notifyId==t)this.stackPopup[s].close()}if(this.notifyAutoHide==false){this.clearAutoHide();this.setAutoHide(this.notifyHideTime/2)}return BX.PreventDefault(e)},this));if(s.notifyParams.click){s.popupContainer.style.cursor="pointer";BX.bind(s.popupContainer,"click",BX.delegate(function(e){this.notifyParams.click(this);if(this.notifyParams.notifyId!="network")return BX.PreventDefault(e)},s))}this.stackPopup[s.uniquePopupId]=s}if(this.stack.length>0){this.clearAutoHide(true);this.setAutoHide(this.notifyHideTime)}this.garbage()};BX.IM.NotifyManager.prototype.closeByTag=function(e){for(var t=0;t<this.stack.length;t++){if(typeof this.stack[t]!="undefined"&&this.stack[t].tag==e){delete this.stack[t]}}for(var t in this.stackPopup){if(this.stackPopup[t].notifyParams.tag==e)this.stackPopup[t].close()}};BX.IM.NotifyManager.prototype.setShowTimer=function(e){clearTimeout(this.stackTimeout);this.stackTimeout=setTimeout(BX.delegate(this.draw,this),e)};BX.IM.NotifyManager.prototype.setAutoHide=function(e){this.notifyAutoHide=true;clearTimeout(this.notifyAutoHideTimeout);this.notifyAutoHideTimeout=setTimeout(BX.delegate(function(){for(var t in this.stackPopupTimeout){this.stackPopupTimeout[t]=setTimeout(BX.delegate(function(){this.close()},this.stackPopup[t]),e-1e3);this.stackPopupTimeout2[t]=setTimeout(BX.delegate(function(){this.setShowTimer(300)},this),e-700)}},this),1e3)};BX.IM.NotifyManager.prototype.clearAutoHide=function(e){clearTimeout(this.notifyGarbageTimeout);this.notifyAutoHide=false;e=e==true;if(e){clearTimeout(this.stackTimeout);for(var t in this.stackPopupTimeout){clearTimeout(this.stackPopupTimeout[t]);clearTimeout(this.stackPopupTimeout2[t])}}else{clearTimeout(this.notifyAutoHideTimeout);this.notifyAutoHideTimeout=setTimeout(BX.delegate(function(){clearTimeout(this.stackTimeout);for(var e in this.stackPopupTimeout){clearTimeout(this.stackPopupTimeout[e]);clearTimeout(this.stackPopupTimeout2[e])}},this),300)}};BX.IM.NotifyManager.prototype.garbage=function(){clearTimeout(this.notifyGarbageTimeout);this.notifyGarbageTimeout=setTimeout(BX.delegate(function(){var e=[];for(var t=0;t<this.stack.length;t++){if(typeof this.stack[t]!="undefined")e.push(this.stack[t])}this.stack=e},this),1e4)};BX.IM.NotifyManager.prototype.nativeNotify=function(e,t){if(!e.title||e.title.length<=0)return false;if(this.blockNativeNotify)return false;if(!t){setTimeout(BX.delegate(function(){if(this.blockNativeNotify)return false;this.nativeNotify(e,true)},this),Math.floor(Math.random()*151)+50);return true}BX.localStorage.set("mnnb",true,1);var s=new Notification(e.title,{tag:e.tag?e.tag:"",body:e.text?e.text:"",icon:e.icon?e.icon:""});if(typeof e.onshow=="function")s.onshow=e.onshow;if(typeof e.onclick=="function")s.onclick=e.onclick;if(typeof e.onclose=="function")s.onclose=e.onclose;if(typeof e.onerror=="function")s.onerror=e.onerror;return true};BX.IM.NotifyManager.prototype.nativeNotifyShow=function(){this.show()};BX.IM.NotifyManager.prototype.nativeNotifyGranted=function(){return window.Notification&&window.Notification.permission&&window.Notification.permission.toLowerCase()=="granted"};BX.IM.NotifyManager.prototype.nativeNotifyAccessForm=function(){if(!this.BXIM.xmppStatus&&!this.BXIM.desktopStatus&&this.BXIM.settings.nativeNotify&&window.Notification&&window.Notification.permission&&window.Notification.permission.toLowerCase()=="default"){clearTimeout(this.popupMessengerDesktopTimeout);var e=BX.delegate(function(){Notification.requestPermission();BXIM.messenger.hideTopLine()},this);var t=BX.delegate(function(){this.BXIM.settings.nativeNotify=false;this.BXIM.saveSettings({nativeNotify:this.BXIM.settings.nativeNotify
});BXIM.messenger.hideTopLine()},this);BXIM.messenger.showTopLine(BX.message("IM_WN_MAC")+"<br />"+BX.message("IM_WN_TEXT"),[{title:BX.message("IM_WN_ACCEPT"),callback:e},{title:BX.message("IM_DESKTOP_INSTALL_N"),callback:t}])}else{return false}return true}})();(function(){if(BX.desktopUtils)return;BX.desktopUtils=function(){};BX.desktopUtils.prototype.goToBx=function(e){if(typeof BX.PULL!="undefined"&&typeof BX.PULL.setPrivateVar!="undefined")BX.PULL.setPrivateVar("_pullTryAfterBxLink",true);location.href=e};BX.desktopUtils.prototype.isChangedLocationToBx=function(){if(typeof BX.PULL!="undefined"&&typeof BX.PULL.setPrivateVar!="undefined")return BX.PULL.returnPrivateVar("_pullTryAfterBxLink");return false};BX.desktopUtils=new BX.desktopUtils})();(function(){if(BX.Network)return;BX.Network=function(e,t){this.BXIM=e;this.params=t||{};this.notify=t.notifyClass;this.messenger=t.messengerClass;this.desktop=t.desktopClass;this.notifyCount=0;this.messageCount=0;this.callCount=0;if(this.BXIM.init&&this.BXIM.bitrixNetworkStatus){BX.addCustomEvent("onPullEvent-b24network",BX.delegate(function(e,t){if(e=="notify"){if(t.COUNTER&&t.COUNTER.TYPE&&t.COUNTER.SUM){if(t.COUNTER.SUM=="increment")this.incrementCounter(t.COUNTER.TYPE);else this.setCounter(t.COUNTER.TYPE,t.COUNTER.SUM)}if(t.MESSAGE&&t.LINK){this.newNotify(t.MESSAGE,t.LINK)}}},this))}};BX.Network.prototype.newNotify=function(e,t,s){if(!(!this.desktop.ready()&&this.desktop.run())&&(this.BXIM.settings.status=="dnd"||!this.desktop.ready()&&this.BXIM.desktopStatus))return false;s=s!=false;var i={id:"network",type:"4",date:BX.MessengerCommon.getNowDate(),silent:"N",text:e+(t?'<br><a href="'+t+'" target="_blank">'+BX.message("IM_LINK_MORE")+"</a>":""),textNative:e,tag:"",original_tag:"",read:"",settingName:"im|default",userId:"0",userName:"",userAvatar:"",userLink:"",title:"",href:t};var a=[];var n=[];notifyHtml=this.notify.createNotify(i);if(notifyHtml!==false){a.push(notifyHtml);n.push({title:i.userName?BX.util.htmlspecialcharsback(i.userName):BX.message("IM_NOTIFY_WINDOW_NEW_TITLE"),text:BX.util.htmlspecialcharsback(i.textNative).split("<br />").join("\n").replace(/<\/?[^>]+>/gi,""),icon:i.userAvatar?i.userAvatar:"",tag:"im-network-"+i.tag})}if(a.length==0)return false;if(s)this.BXIM.playSound("reminder");if(s&&!this.BXIM.windowFocus&&this.BXIM.notifyManager.nativeNotifyGranted()){for(var o=0;o<n.length;o++){var i=n[o];i.onshow=function(){var e=this;setTimeout(function(){e.close()},15e3)};i.onclick=function(){window.focus();this.close()};this.BXIM.notifyManager.nativeNotify(i)}}if(this.BXIM.windowFocus&&this.BXIM.notifyManager.nativeNotifyGranted()){BX.localStorage.set("mnnb",true,1)}for(var o=0;o<a.length;o++){this.BXIM.notifyManager.add({html:a[o],tag:"",originalTag:"",notifyId:"network",notifyType:a[o].getAttribute("data-notifyType"),click:BX.delegate(function(e){e.close()},this),close:function(){}})}return true};BX.Network.prototype.setCounter=function(e,t){t=parseInt(t);if(t<=0)t=0;if(e=="call")this.callCount=t;else if(e=="notify")this.notifyCount=t;else if(e=="message")this.messageCount=t;this.updateCounters();return t};BX.Network.prototype.incrementCounter=function(e){if(e=="call")this.callCount++;else if(e=="notify")this.notifyCount++;else if(e=="message")this.messageCount++;this.updateCounters();return true};BX.Network.prototype.getCounter=function(e){var t=0;if(e=="call")t=this.callCount;else if(e=="notify")t=this.notifyCount;else if(e=="message")t=this.messageCount;return t};BX.Network.prototype.updateCounters=function(){var e=this.getCounters();BX.onCustomEvent(window,"onImUpdateCounterNetwork",[e]);var t="";if(e>99)t="99+";else if(e>0)t=e;if(this.notify.panelButtonNetworkCount!=null){this.notify.panelButtonNetworkCount.innerHTML=t;this.notify.adjustPosition({resize:true,timeout:500})}};BX.Network.prototype.getCounters=function(){return this.notifyCount+this.messageCount+this.callCount}})();
/* End */
;
; /* Start:"a:4:{s:4:"full";s:43:"/bitrix/js/pull/pull.min.js?145227746429859";s:6:"source";s:23:"/bitrix/js/pull/pull.js";s:3:"min";s:27:"/bitrix/js/pull/pull.min.js";s:3:"map";s:27:"/bitrix/js/pull/pull.map.js";}"*/
(function(window){if(!window.BX){if(typeof console=="object")console.log("PULL notice: bitrix core not loaded");return}if(window.BX.PULL){if(typeof console=="object")console.log("PULL notice: script is already loaded");return}var BX=window.BX,_revision=12,_updateStateVeryFastCount=0,_updateStateFastCount=0,_updateStateStep=60,_updateStateTimeout=null,_updateStateStatusTimeout=null,_updateStateSend=false,_pullTryAfterBxLink=false,_pullTryConnect=false,_pullPath=null,_pullMethod="PULL",_pullWithHeaders=true,_pullTimeConfig=0,_pullTimeConfigShared=0,_pullTimeConst=new Date(2022,2,19).toUTCString(),_pullTime=_pullTimeConst,_pullTag=1,_pullTimeout=60,_pullMid=null,_watchTag={},_watchTimeout=null,_channelID=null,_channelClearReason=0,_channelClear=null,_channelLastID=0,_channelStack={},_WS=null,_wsPath="",_wsSupport=false,_wsConnected=false,_wsTryReconnect=0,_wsError1006Count=0,_mobileMode=false,_lsSupport=false,_escStatus=false,_sendAjaxTry=0,_confirm=null,_beforeUnload=false,_pathToAjax="/bitrix/components/bitrix/pull.request/ajax.php?",_onBeforeUnload=BX.proxy(function(){_beforeUnload=true;_pullTryConnect=false;if(_WS)_WS.close(1e3,"onbeforeunload");if(BX.PULL.returnPrivateVar("_pullTryAfterBxLink")){BX.PULL.tryConnectDelay()}},this);BX.PULL=function(){};BX.PULL.start=function(e){if(typeof e!="object"){e={}}_pullTryConnect=true;_mobileMode=false;if(e.MOBILE=="Y")_mobileMode=true;_lsSupport=true;if(e.LOCAL_STORAGE=="N")_lsSupport=false;if(e.HEADERS=="N")_pullWithHeaders=false;if(_lsSupport&&BX.localStorage.get("prs")!==null){_pullTryConnect=false}_wsSupport=true;if(e.WEBSOCKET=="N")_wsSupport=false;BX.bind(window,"offline",function(){_pullTryConnect=false;if(_WS)_WS.close(1e3,"offline")});BX.bind(window,"online",function(){if(!BX.PULL.tryConnect())BX.PULL.updateState("10",true)});if(BX.browser.IsFirefox()){BX.bind(window,"keypress",function(e){if(e.keyCode==27)_escStatus=true})}if(_wsSupport&&!BX.PULL.supportWebSocket())_wsSupport=false;if(e.PATH_COMMAND){BX.PULL.setAjaxPath(e.PATH_COMMAND)}if(e.CHANNEL_ID){_channelID=e.CHANNEL_ID;_pullPath=BX.PULL.getModernPath(e);_wsPath=e.PATH_WS;_pullMethod=e.METHOD;e.CHANNEL_DT=e.CHANNEL_DT.toString().split("/");_pullTimeConfig=e.CHANNEL_DT[0];_pullTimeConfigShared=e.CHANNEL_DT[1]?e.CHANNEL_DT[1]:e.CHANNEL_DT[0];_pullTimeConfig=parseInt(_pullTimeConfig)+parseInt(BX.message("SERVER_TZ_OFFSET"))+parseInt(BX.message("USER_TZ_OFFSET"));_pullTimeConfigShared=parseInt(_pullTimeConfigShared)+parseInt(BX.message("SERVER_TZ_OFFSET"))+parseInt(BX.message("USER_TZ_OFFSET"));_channelLastID=parseInt(e.LAST_ID)}if(!BX.browser.SupportLocalStorage())_lsSupport=false;if(_lsSupport){BX.addCustomEvent(window,"onLocalStorageSet",BX.PULL.storageSet);BX.localStorage.set("pset",{CHANNEL_ID:_channelID,LAST_ID:_channelLastID,PATH:_pullPath,PATH_WS:_wsPath,TIME_LAST_GET:_pullTimeConfig,TIME_LAST_GET_SHARED:_pullTimeConfigShared,METHOD:_pullMethod},5)}BX.addCustomEvent("onImError",function(e){if(e=="AUTHORIZE_ERROR")_sendAjaxTry++});BX.addCustomEvent("onPullError",BX.delegate(function(e){if(e=="AUTHORIZE_ERROR"){_pullTryConnect=false}},this));if(BX.desktop){BX.desktop.addCustomEvent("BXLoginSuccess",function(){if(_WS)_WS.close(1e3,"desktop_login_success")})}BX.PULL.initBeforeUnload();BX.onCustomEvent(window,"onPullInit",[]);BX.PULL.expireConfig();BX.PULL.init()};BX.PULL.init=function(){BX.PULL.updateState("init");BX.PULL.updateWatch()};BX.PULL.getNowDate=function(e){var t=new Date;if(e==true)t=new Date(t.getFullYear(),t.getMonth(),t.getDate(),0,0,0);return Math.round(+t/1e3)+parseInt(BX.message("USER_TZ_OFFSET"))};BX.PULL.getDateDiff=function(e){var t=BX.message("USER_TZ_OFFSET");if(t==="")return 0;var n=BX.PULL.getNowDate()+parseInt(BX.message("SERVER_TZ_OFFSET"));var a=parseInt(e)+parseInt(BX.message("SERVER_TZ_OFFSET"));return n-a};BX.PULL.setTryAfterBxLink=function(e){_pullTryAfterBxLink=e?true:false};BX.PULL.initBeforeUnload=function(){BX.unbind(window,"beforeunload",_onBeforeUnload);BX.bind(window,"beforeunload",_onBeforeUnload)};BX.PULL.tryConnectDelay=function(){setTimeout(function(){BX.PULL.setPrivateVar("_pullTryConnect",false);BX.PULL.tryConnect();BX.PULL.setPrivateVar("_pullTryAfterBxLink",false)},1e3)};BX.PULL.expireConfig=function(){if(!_channelID)return false;clearTimeout(_channelClear);_channelClear=setTimeout(BX.PULL.expireConfig,6e4);if(_channelID&&_pullMethod!="PULL"&&_pullTimeConfig+43200<Math.round(+new Date/1e3)+parseInt(BX.message("SERVER_TZ_OFFSET"))+parseInt(BX.message("USER_TZ_OFFSET"))){_channelClearReason=1;_channelID=null;if(_WS)_WS.close(1e3,"expire_config_1")}else if(_channelID&&_pullMethod!="PULL"&&_pullTimeConfigShared+43200+(Math.floor(Math.random()*61)+10)*1e3<Math.round(+new Date/1e3)+parseInt(BX.message("SERVER_TZ_OFFSET"))+parseInt(BX.message("USER_TZ_OFFSET"))){_channelClearReason=1;_channelID=null;if(_WS)_WS.close(1e3,"expire_config_2")}};BX.PULL.tryConnect=function(){if(_pullTryConnect)return false;_pullTryConnect=true;BX.PULL.init();return true};BX.PULL.getChannelID=function(e,t,n){if(!_pullTryConnect)return false;n=n!=false;t=t==true;e=typeof e=="undefined"?"0":e;BX.ajax({url:_pathToAjax+"GET_CHANNEL&V="+_revision+"&CR="+_channelClearReason+"&CODE="+e.toUpperCase()+(_mobileMode?"&MOBILE":""),method:"POST",skipAuthCheck:true,dataType:"json",lsId:"PULL_GET_CHANNEL",lsTimeout:1,timeout:30,data:{PULL_GET_CHANNEL:"Y",SITE_ID:BX.message.SITE_ID?BX.message("SITE_ID"):"",MOBILE:_mobileMode?"Y":"N",CACHE:t?"N":"Y",PULL_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()},onsuccess:BX.delegate(function(e){_channelClearReason=0;if(n&&BX.localStorage.get("pgc")===null)BX.localStorage.set("pgc",t,1);if(typeof e=="object"&&e.ERROR==""){if(e.REVISION&&!BX.PULL.checkRevision(e.REVISION))return false;_channelID=e.CHANNEL_ID;_pullPath=BX.PULL.getModernPath(e);_wsPath=e.PATH_WS;_pullMethod=e.METHOD;var a=e.CHANNEL_DT.toString().split("/");_pullTimeConfig=a[0];_pullTimeConfigShared=a[1]?a[1]:a[0];_pullTimeConfig=parseInt(_pullTimeConfig)+parseInt(BX.message("SERVER_TZ_OFFSET"))+parseInt(BX.message("USER_TZ_OFFSET"));_pullTimeConfigShared=parseInt(_pullTimeConfigShared)+parseInt(BX.message("SERVER_TZ_OFFSET"))+parseInt(BX.message("USER_TZ_OFFSET"));_channelLastID=_pullMethod=="PULL"?e.LAST_ID:_channelLastID;e.TIME_LAST_GET=_pullTimeConfig;e.TIME_LAST_GET_SHARED=_pullTimeConfigShared;BX.PULL.updateState("11");BX.PULL.expireConfig();if(_lsSupport)BX.localStorage.set("pset",e,600)}else{_sendAjaxTry++;_channelClearReason=2;_channelID=null;clearTimeout(_updateStateStatusTimeout);BX.onCustomEvent(window,"onPullStatus",["offline"]);if(typeof e=="object"&&e.BITRIX_SESSID){BX.message({bitrix_sessid:e.BITRIX_SESSID})}if(typeof e=="object"&&e.ERROR=="SESSION_ERROR"){clearTimeout(_updateStateTimeout);_updateStateTimeout=setTimeout(function(){BX.PULL.updateState("12",true)},_sendAjaxTry<2?2e3:BX.PULL.tryConnectTimeout());BX.onCustomEvent(window,"onPullError",[e.ERROR,e.BITRIX_SESSID])}else if(typeof e=="object"&&e.ERROR=="AUTHORIZE_ERROR"){BX.onCustomEvent(window,"onPullError",[e.ERROR])}else{clearTimeout(_updateStateTimeout);_updateStateTimeout=setTimeout(function(){BX.PULL.updateState("31",true)},BX.PULL.tryConnectTimeout());BX.onCustomEvent(window,"onPullError",["NO_DATA"])}if(n&&typeof console=="object"){var l="\n========= PULL ERROR ===========\n"+"Error type: getChannel error\n"+"Error: "+e.ERROR+"\n"+"\n"+"Data array: "+JSON.stringify(e)+"\n"+"================================\n\n";console.log(l)}}},this),onfailure:BX.delegate(function(e){_sendAjaxTry++;_channelClearReason=3;_channelID=null;clearTimeout(_updateStateStatusTimeout);BX.onCustomEvent(window,"onPullStatus",["offline"]);if(e=="timeout"){clearTimeout(_updateStateTimeout);_updateStateTimeout=setTimeout(function(){BX.PULL.updateState("1")},1e4)}else{if(typeof e=="object"&&e.ERROR=="auth"){BX.onCustomEvent(window,"onPullError",["AUTHORIZE_ERROR"])}if(typeof console=="object"){var t="\n========= PULL ERROR ===========\n"+"Error type: getChannel onfailure\n"+"Error: "+e.ERROR+"\n"+"\n"+"Data array: "+JSON.stringify(e)+"\n"+"================================\n\n";console.log(t)}clearTimeout(_updateStateTimeout);_updateStateTimeout=setTimeout(function(){BX.PULL.updateState("14",true)},BX.PULL.tryConnectTimeout())}},this)})};BX.PULL.updateState=function(e,t){if(!_pullTryConnect||_updateStateSend)return false;e=typeof e=="undefined"?"":e;if(_channelID==null||_pullPath==null||_wsSupport&&_wsPath===null){clearTimeout(_updateStateTimeout);_updateStateTimeout=setTimeout(function(){if(e.length>0)BX.PULL.getChannelID(e+(_channelID==null?"-02":"-03"));else BX.PULL.getChannelID(_channelID==null?"2":"3")},Math.floor(Math.random()*151)+50)}else{if(_wsSupport&&_wsPath&&_wsPath.length>1&&_pullMethod!="PULL")BX.PULL.connectWebSocket();else BX.PULL.connectPull(t)}};BX.PULL.connectWebSocket=function(){if(!_wsSupport)return false;_updateStateSend=true;var e=_wsPath.replace("#DOMAIN#",location.hostname);var t=e+(_pullTag!=null?"&tag="+_pullTag:"")+(_pullTime!=null?"&time="+_pullTime:"")+(_pullMid!==null?"&mid="+_pullMid:"");try{_WS=new WebSocket(t)}catch(n){_wsPath=null;_updateStateSend=false;clearTimeout(_updateStateTimeout);_updateStateTimeout=setTimeout(function(){BX.PULL.updateState("33")},BX.PULL.tryConnectTimeout());return false}_WS.onopen=function(){_wsConnected=true;clearTimeout(_updateStateStatusTimeout);BX.onCustomEvent(window,"onPullStatus",["online"])};_WS.onclose=function(e){var t=typeof e.code!="undefined"?e.code:"NA";var n="";if(e.reason){try{n=JSON.parse(e.reason)}catch(a){}}var l=false;_updateStateSend=false;var o=true;if(!_wsConnected){l=true;_channelID=null;if(_wsTryReconnect==1){BX.PULL.updateState("ws-"+t+"-1")}else if(_wsTryReconnect<=3){clearTimeout(_updateStateTimeout);_updateStateTimeout=setTimeout(function(){BX.PULL.updateState("ws-"+t+"-2")},1e4)}else{if(t==1006||t==1008){BX.localStorage.set("pbws",true,172800);_wsSupport=false}clearTimeout(_updateStateTimeout);_updateStateTimeout=setTimeout(function(){BX.PULL.updateState("ws-"+t+"-3")},BX.PULL.tryConnectTimeout())}if(t==1006){if(_wsError1006Count>=5){BX.localStorage.set("pbws",true,86400);_wsSupport=false}_wsError1006Count++}}else{_wsConnected=false;if(e.wasClean&&n&&n.http_status==403){_sendAjaxTry++;_channelID=null;_channelLastID=0;_channelStack={};if(_sendAjaxTry>=5){BX.localStorage.set("pbws",true,86400);_wsSupport=false}clearTimeout(_updateStateTimeout);_updateStateTimeout=setTimeout(function(){BX.PULL.getChannelID("ws-"+t+"-6",true)},_sendAjaxTry<2?1e3:BX.PULL.tryConnectTimeout())}else{clearTimeout(_updateStateTimeout);_updateStateTimeout=setTimeout(function(){BX.PULL.updateState("ws-"+t+"-5-"+e.wasClean)},_sendAjaxTry<2&&e.wasClean===true?1e3:BX.PULL.tryConnectTimeout())}}if(_beforeUnload){_beforeUnload=false}else{BX.onCustomEvent(window,"onPullError",["RECONNECT",t]);if(typeof console=="object"){var u="\n========= PULL INFO ===========\n"+"time: "+new Date+"\n"+"type: websocket close\n"+"code: "+t+"\n"+"clean: "+(e.wasClean?"Y":"N")+"\n"+"never connect: "+(l?"Y":"N")+"\n"+"send connect request: "+(o?"Y":"N")+"\n"+(n?"reason: "+JSON.stringify(n)+"\n":"")+"\n"+"Data array: "+JSON.stringify(e)+"\n"+"================================\n\n";console.log(u)}}};_WS.onmessage=function(e){var t=0;var n=e.data.match(/#!NGINXNMS!#(.*?)#!NGINXNME!#/gm);if(n!=null){_wsTryReconnect=0;_sendAjaxTry=0;for(var a=0;a<n.length;a++){n[a]=n[a].substring(12,n[a].length-12);if(n[a].length<=0)continue;var l=BX.parseJSON(n[a]);var o=null;if(l&&l.text)o=l.text;if(o!==null&&typeof o=="object"){if(o&&o.ERROR==""){if(l.id){l.id=parseInt(l.id);l.channel=l.channel?l.channel:o.CHANNEL_ID?o.CHANNEL_ID:l.time;if(!_channelStack[""+l.channel+l.id]){_channelStack[""+l.channel+l.id]=l.id;if(_channelLastID<l.id)_channelLastID=l.id;BX.PULL.executeMessages(o.MESSAGE,{SERVER_TIME:l.time,SERVER_TIME_WEB:o.SERVER_TIME_WEB})}}}else{BX.onCustomEvent(window,"onPullStatus",["offline"]);if(typeof console=="object"){var u="\n========= PULL ERROR ===========\n"+"Error type: updateState fetch\n"+"Error: "+o.ERROR+"\n"+"\n"+"Connect CHANNEL_ID: "+_channelID+"\n"+"Connect WS_PATH: "+_wsPath+"\n"+"\n"+"Data array: "+JSON.stringify(o)+"\n"+"================================\n\n";console.log(u)}_channelClearReason=4;_channelID=null}}if(l.tag)_pullTag=l.tag;if(l.time)_pullTime=l.time;if(l.mid)_pullMid=l.mid;t++}}if(_channelID==null){if(_WS)_WS.close(1e3,"onmessage")}};_WS.onerror=function(){_wsTryReconnect++}};BX.PULL.connectPull=function(e){e=e==true?true:false;clearTimeout(_updateStateTimeout);_updateStateTimeout=setTimeout(function(){if(!_pullPath||typeof _pullPath!="string"||_pullPath.length<=32){_pullPath=null;clearTimeout(_updateStateTimeout);_updateStateTimeout=setTimeout(function(){BX.PULL.updateState("17")},1e4);return false}_updateStateStatusTimeout=setTimeout(function(){BX.onCustomEvent(window,"onPullStatus",["online"])},5e3);_updateStateSend=true;var e=[];if(_pullWithHeaders){e=[{name:"If-Modified-Since",value:_pullTime},{name:"If-None-Match",value:_pullTag}]}var t=_pullPath.replace("#DOMAIN#",location.hostname);var n=BX.ajax({url:_pullMethod=="PULL"?t:t+(_pullTag!=null?"&tag="+_pullTag:"")+(_pullTime!=null?"&time="+_pullTime:"")+(_pullMid!==null?"&mid="+_pullMid:"")+"&rnd="+ +new Date,skipAuthCheck:true,skipBxHeader:_pullMethod=="PULL"?false:true,method:_pullMethod=="PULL"?"POST":"GET",dataType:_pullMethod=="PULL"?"json":"html",timeout:_pullTimeout,headers:e,data:_pullMethod=="PULL"?{PULL_UPDATE_STATE:"Y",CHANNEL_ID:_channelID,CHANNEL_LAST_ID:_channelLastID,SITE_ID:BX.message.SITE_ID?BX.message("SITE_ID"):"",PULL_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()}:{},onsuccess:function(e){clearTimeout(_updateStateStatusTimeout);_updateStateSend=false;if(_WS)_WS.close(1e3,"ajax_onsuccess");if(_pullMethod=="PULL"&&typeof e=="object"){if(e.ERROR==""){BX.onCustomEvent(window,"onPullStatus",["online"]);_sendAjaxTry=0;BX.PULL.executeMessages(e.MESSAGE,{SERVER_TIME:(new Date).toUTCString(),SERVER_TIME_WEB:Math.round(+new Date/1e3)});if(_lsSupport)BX.localStorage.set("pus",{MESSAGE:e.MESSAGE},5)}else{clearTimeout(_updateStateStatusTimeout);BX.onCustomEvent(window,"onPullStatus",["offline"]);if(e&&e.BITRIX_SESSID){BX.message({bitrix_sessid:e.BITRIX_SESSID})}if(e.ERROR=="SESSION_ERROR"){BX.onCustomEvent(window,"onPullError",[e.ERROR,e.BITRIX_SESSID])}else{BX.onCustomEvent(window,"onPullError",[e.ERROR])}if(typeof console=="object"){var t="\n========= PULL ERROR ===========\n"+"Error type: updateState error\n"+"Error: "+e.ERROR+"\n"+"\n"+"Connect CHANNEL_ID: "+_channelID+"\n"+"Connect PULL_PATH: "+_pullPath+"\n"+"\n"+"Data array: "+JSON.stringify(e)+"\n"+"================================\n\n";console.log(t)}_channelClearReason=5;_channelID=null}if(_channelID!=null&&_lsSupport)BX.localStorage.set("pset",{CHANNEL_ID:_channelID,LAST_ID:_channelLastID,PATH:_pullPath,PATH_WS:_wsPath,TAG:_pullTag,MID:_pullMid,TIME:_pullTime,TIME_LAST_GET:_pullTimeConfig,TIME_LAST_GET_SHARED:_pullTimeConfigShared,METHOD:_pullMethod},600);BX.PULL.setUpdateStateStep()}else{if(e.length>0){var a=0;_sendAjaxTry=0;var l=e.match(/#!NGINXNMS!#(.*?)#!NGINXNME!#/gm);if(l!=null){for(var o=0;o<l.length;o++){l[o]=l[o].substring(12,l[o].length-12);if(l[o].length<=0)continue;var u=BX.parseJSON(l[o]);var e=null;if(u&&u.text)e=u.text;if(e!==null&&typeof e=="object"){if(e&&e.ERROR==""){if(u.id){u.id=parseInt(u.id);u.channel=u.channel?u.channel:e.CHANNEL_ID?e.CHANNEL_ID:u.time;if(!_channelStack[""+u.channel+u.id]){_channelStack[""+u.channel+u.id]=u.id;if(_channelLastID<u.id)_channelLastID=u.id;BX.PULL.executeMessages(e.MESSAGE,{SERVER_TIME:u.time,SERVER_TIME_WEB:e.SERVER_TIME_WEB})}}}else{if(typeof console=="object"){var t="\n========= PULL ERROR ===========\n"+"Error type: updateState fetch\n"+"Error: "+e.ERROR+"\n"+"\n"+"Connect CHANNEL_ID: "+_channelID+"\n"+"Connect PULL_PATH: "+_pullPath+"\n"+"\n"+"Data array: "+JSON.stringify(e)+"\n"+"================================\n\n";console.log(t)}_channelClearReason=6;_channelID=null;clearTimeout(_updateStateStatusTimeout);BX.onCustomEvent(window,"onPullStatus",["offline"])}}else{if(typeof console=="object"){var t="\n========= PULL ERROR ===========\n"+"Error type: updateState parse\n"+"\n"+"Connect CHANNEL_ID: "+_channelID+"\n"+"Connect PULL_PATH: "+_pullPath+"\n"+"\n"+"Data string: "+l[o]+"\n"+"================================\n\n";console.log(t)}_channelClearReason=7;_channelID=null;clearTimeout(_updateStateStatusTimeout);BX.onCustomEvent(window,"onPullStatus",["offline"])}if(u.tag)_pullTag=u.tag;if(u.time)_pullTime=u.time;if(u.mid)_pullMid=u.mid;a++}}else{if(typeof console=="object"){var t="\n========= PULL ERROR ===========\n"+"Error type: updateState error getting message\n"+"\n"+"Connect CHANNEL_ID: "+_channelID+"\n"+"Connect PULL_PATH: "+_pullPath+"\n"+"\n"+"Data string: "+e+"\n"+"================================\n\n";console.log(t)}_channelClearReason=8;_channelID=null;clearTimeout(_updateStateStatusTimeout);BX.onCustomEvent(window,"onPullStatus",["offline"])}if(a>0||n&&n.status==0){BX.PULL.updateState(a>0?"19":"20")}else{_channelClearReason=9;_channelID=null;clearTimeout(_updateStateTimeout);_updateStateTimeout=setTimeout(function(){BX.PULL.updateState("21")},1e4)}}else{if(n&&(n.status==304||n.status==0)){if(n.status==0){if(_escStatus){_escStatus=false;BX.PULL.updateState("22-3")}else{_updateStateTimeout=setTimeout(function(){BX.PULL.updateState("22-2")},3e4)}}else{try{var i=n.getResponseHeader("Expires");if(i==="Thu, 01 Jan 1973 11:11:01 GMT"){var s=n.getResponseHeader("Last-Message-Id");if(_pullMid===null&&s&&s.length>0){_pullMid=s}}}catch(r){}BX.PULL.updateState("22-1")}}else if(n&&(n.status==502||n.status==500)){clearTimeout(_updateStateStatusTimeout);BX.onCustomEvent(window,"onPullStatus",["offline"]);_sendAjaxTry++;_channelClearReason=10;_channelID=null;clearTimeout(_updateStateTimeout);_updateStateTimeout=setTimeout(function(){BX.PULL.updateState("23")},BX.PULL.tryConnectTimeout())}else{clearTimeout(_updateStateStatusTimeout);BX.onCustomEvent(window,"onPullStatus",["offline"]);_sendAjaxTry++;_channelClearReason=11;_channelID=null;var _=BX.PULL.tryConnectTimeout();var c=n&&typeof n.status!="undefined"?n.status:"NaN";clearTimeout(_updateStateTimeout);_updateStateTimeout=setTimeout(function(){BX.PULL.updateState("24-"+c+"-"+_/1e3)},_)}}}},onfailure:function(e){clearTimeout(_updateStateStatusTimeout);BX.onCustomEvent(window,"onPullStatus",["offline"]);_updateStateSend=false;_sendAjaxTry++;if(_WS)_WS.close(1e3,"ajax_onfailure");if(e=="timeout"){if(_pullMethod=="PULL")BX.PULL.setUpdateStateStep();else BX.PULL.updateState("25")}else if(n&&(n.status==403||n.status==404||n.status==400)){if(n.status==403){_channelLastID=0;_channelStack={}}_channelClearReason=12;_channelID=null;clearTimeout(_updateStateTimeout);_updateStateTimeout=setTimeout(function(){BX.PULL.getChannelID("7-"+n.status,n.status==403?true:false)},_sendAjaxTry<2?50:BX.PULL.tryConnectTimeout())}else if(n&&(n.status==500||n.status==502)){_channelClearReason=13;_channelID=null;clearTimeout(_updateStateTimeout);_updateStateTimeout=setTimeout(function(){BX.PULL.getChannelID("8-"+n.status)},_sendAjaxTry<2?50:BX.PULL.tryConnectTimeout())}else{if(typeof console=="object"){var t="\n========= PULL ERROR ===========\n"+"Error type: updateState onfailure\n"+"\n"+"Connect CHANNEL_ID: "+_channelID+"\n"+"Connect PULL_PATH: "+_pullPath+"\n"+"\n"+"Data array: "+JSON.stringify(e)+"\n"+"================================\n\n";console.log(t)}clearTimeout(_updateStateTimeout);if(_pullMethod=="PULL")_updateStateTimeout=setTimeout(BX.PULL.setUpdateStateStep,1e4);else _updateStateTimeout=setTimeout(function(){BX.PULL.updateState("26")},1e4)}}})},e?150:(_pullMethod=="PULL"?_updateStateStep:.3)*1e3)};BX.PULL.extendWatch=function(e,t){if(e.length<=0)return false;_watchTag[e]=true;if(t===true)BX.PULL.updateWatch(true)};BX.PULL.clearWatch=function(e){if(e=="undefined")_watchTag={};else if(_watchTag[e])delete _watchTag[e]};BX.PULL.updateWatch=function(e){if(!_pullTryConnect)return false;e=e==true?true:false;clearTimeout(_watchTimeout);_watchTimeout=setTimeout(function(){var e=[];for(var t in _watchTag){if(_watchTag.hasOwnProperty(t)){e.push(t)}}if(e.length>0){BX.ajax({url:_pathToAjax+"UPDATE_WATCH&V="+_revision+"",method:"POST",dataType:"json",timeout:30,lsId:"PULL_WATCH_"+location.pathname,lsTimeout:5,data:{PULL_UPDATE_WATCH:"Y",WATCH:e,SITE_ID:BX.message.SITE_ID?BX.message("SITE_ID"):"",PULL_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()},onsuccess:BX.delegate(function(){BX.localStorage.set("puw",location.pathname,5)},this)})}BX.PULL.updateWatch()},e?5e3:174e4)};BX.PULL.executeMessages=function(e,t,n){t=t===null?{SERVER_TIME:(new Date).toUTCString(),SERVER_TIME_WEB:Math.round(+new Date/1e3)}:t;n=n===false?false:true;for(var a=0;a<e.length;a++){e[a].module_id=e[a].module_id.toLowerCase();if(e[a].id){e[a].id=parseInt(e[a].id);if(_channelStack[""+_channelID+e[a].id])continue;else _channelStack[""+_channelID+e[a].id]=e[a].id;if(_channelLastID<e[a].id)_channelLastID=e[a].id}e[a].params["SERVER_TIME_WEB"]=parseInt(t.SERVER_TIME_WEB);e[a].params["SERVER_TIME"]=t.SERVER_TIME;if(e[a].module_id=="pull"){if(n){if(e[a].command=="channel_die"&&typeof e[a].params.replace=="object"){BX.PULL.updateChannelID({METHOD:_pullMethod,LAST_ID:_channelLastID,CHANNEL_ID:_channelID,CHANNEL_DT:_pullTimeConfig+"/"+e[a].params.replace.CHANNEL_DIE,PATH:_pullPath.replace(e[a].params.replace.PREV_CHANNEL_ID,e[a].params.replace.CHANNEL_ID),PATH_WS:_wsPath?_wsPath.replace(e[a].params.replace.PREV_CHANNEL_ID,e[a].params.replace.CHANNEL_ID):_wsPath})}else if(e[a].command=="channel_die"||e[a].command=="config_die"){_channelClearReason=14;_channelID=null;_pullPath=null;if(_wsPath)_wsPath=null;if(_WS)_WS.close(1e3,"config_die")}else if(e[a].command=="server_restart"){BX.PULL.tryConnectSet(0,false);BX.localStorage.set("prs",true,600);if(_WS)_WS.close(1e3,"server_restart");setTimeout(function(){BX.PULL.tryConnect()},(Math.floor(Math.random()*61)+60)*1e3+6e5)}}}else{if(!(e[a].module_id=="main"&&e[a].command=="user_counter"))BX.PULL.setUpdateStateStepCount(1,4);try{if(e[a].module_id=="online"){if(BX.PULL.getDateDiff(e[a].params["SERVER_TIME_WEB"]+parseInt(BX.message("USER_TZ_OFFSET")))<120)BX.onCustomEvent(window,"onPullOnlineEvent",[e[a].command,e[a].params],true)}else{BX.onCustomEvent(window,"onPullEvent-"+e[a].module_id,[e[a].command,e[a].params],true);BX.onCustomEvent(window,"onPullEvent",[e[a].module_id,e[a].command,e[a].params],true)}}catch(l){if(typeof console=="object"){var o="\n========= PULL ERROR ===========\n"+"Error type: onPullEvent onfailure\n"+"Error event: "+JSON.stringify(l)+"\n"+"\n"+"Message MODULE_ID: "+e[a].module_id+"\n"+"Message COMMAND: "+e[a].command+"\n"+"Message PARAMS: "+e[a].params+"\n"+"\n"+"Message array: "+JSON.stringify(e[a])+"\n"+"================================\n";console.log(o);BX.debug(l)}}}}};BX.PULL.setUpdateStateStep=function(e){var e=e==false?false:true;var t=60;if(_updateStateVeryFastCount>0){t=10;_updateStateVeryFastCount--}else if(_updateStateFastCount>0){t=20;_updateStateFastCount--}_updateStateStep=parseInt(t);BX.PULL.updateState("27");if(e&&_lsSupport)BX.localStorage.set("puss",_updateStateStep,5)};BX.PULL.setUpdateStateStepCount=function(e,t){_updateStateVeryFastCount=parseInt(e);_updateStateFastCount=parseInt(t)};BX.PULL.storageSet=function(e){if(e.key=="pus"){BX.PULL.executeMessages(e.value.MESSAGE,null,false)}else if(e.key=="pgc"){BX.PULL.getChannelID("9",e.value,false)}else if(e.key=="puss"){_updateStateStep=70;BX.PULL.updateState("28")}else if(e.key=="pset"){_channelID=e.value.CHANNEL_ID;_channelLastID=e.value.LAST_ID;_pullPath=e.value.PATH;_wsPath=e.value.PATH_WS;_pullMethod=e.value.METHOD;if(e.value.TIME)_pullTime=e.value.TIME;if(e.value.TAG)_pullTag=e.value.TAG;if(e.value.MID)_pullMid=e.value.MID;if(e.value.TIME_LAST_GET)_pullTimeConfig=e.value.TIME_LAST_GET;if(e.value.TIME_LAST_GET_SHARED)_pullTimeConfigShared=e.value.TIME_LAST_GET_SHARED;if(_channelID!=null){if(!BX.PULL.tryConnect())BX.PULL.updateState("29",true)}}else if(e.key=="puw"){if(e.value==location.pathname)BX.PULL.updateWatch()}};BX.PULL.setAjaxPath=function(e){_pathToAjax=e.indexOf("?")==-1?e+"?":e+"&"};BX.PULL.updateChannelID=function(e){if(typeof e!="object")return false;var t=e.METHOD;var n=e.CHANNEL_ID;var a=BX.PULL.getModernPath(e);var l=e.LAST_ID;var o=e.PATH_WS;if(typeof n=="undefined"||typeof a=="undefined")return false;if(n==_channelID&&a==_pullPath&&o==_wsPath)return false;_channelID=n;e.CHANNEL_DT=e.CHANNEL_DT.toString().split("/");_pullTimeConfig=e.CHANNEL_DT[0];_pullTimeConfigShared=e.CHANNEL_DT[1]?e.CHANNEL_DT[1]:e.CHANNEL_DT[0];_pullTimeConfig=parseInt(_pullTimeConfig)+parseInt(BX.message("SERVER_TZ_OFFSET"))+parseInt(BX.message("USER_TZ_OFFSET"));_pullTimeConfigShared=parseInt(_pullTimeConfigShared)+parseInt(BX.message("SERVER_TZ_OFFSET"))+parseInt(BX.message("USER_TZ_OFFSET"));_pullPath=a;_wsPath=o;_channelLastID=_pullMethod=="PULL"&&typeof l=="number"?l:_channelLastID;if(typeof t=="string")_pullMethod=t;if(_lsSupport)BX.localStorage.set("pset",{CHANNEL_ID:_channelID,LAST_ID:_channelLastID,PATH:_pullPath,PATH_WS:_wsPath,TAG:_pullTag,MID:_pullMid,TIME:_pullTime,TIME_LAST_GET:_pullTimeConfig,TIME_LAST_GET_SHARED:_pullTimeConfigShared,METHOD:_pullMethod},600);if(_WS)_WS.close(1e3,"channel_die");return true};BX.PULL.tryConnectTimeout=function(){var e=0;if(_sendAjaxTry<=2)e=15e3;else if(_sendAjaxTry>2&&_sendAjaxTry<=5)e=45e3;else if(_sendAjaxTry>5&&_sendAjaxTry<=10)e=6e5;else if(_sendAjaxTry>10){_pullTryConnect=false;e=36e5}return e};BX.PULL.tryConnectSet=function(e,t){if(typeof e=="number")_sendAjaxTry=parseInt(e);if(typeof t=="boolean")_pullTryConnect=t};BX.PULL.getPullServerStatus=function(){return _pullMethod=="PULL"?false:true};BX.PULL.capturePullEvent=function(){BX.addCustomEvent("onPullOnlineEvent",function(e,t){console.log("onPullOnlineEvent",e,t)});BX.addCustomEvent("onPullEvent",function(e,t,n){console.log("onPullEvent",e,t,n)});return'Capture "Pull Event" started.'};BX.PULL.getDebugInfo=function(){if(!console||!console.log||!JSON||!JSON.stringify)return false;var e=JSON.stringify(_watchTag);var t="\n========= PULL DEBUG ===========\n"+"Connect: "+(_updateStateSend?"Y":"N")+"\n"+"WebSocket connect: "+(_wsConnected?"Y":"N")+"\n"+"LocalStorage status: "+(_lsSupport?"Y":"N")+"\n"+"WebSocket support: "+(_wsSupport&&_wsPath.length>0?"Y":"N")+"\n"+"Queue Server: "+(_pullMethod=="PULL"?"N":"Y")+"\n"+"Try connect: "+(_pullTryConnect?"Y":"N")+"\n"+"Try number: "+_sendAjaxTry+"\n"+"\n"+"Path: "+_pullPath+"\n"+(_wsPath.length>0?"WebSocket Path: "+_wsPath+"\n":"")+"ChannelID: "+_channelID+"\n"+"ChannelDie: "+parseInt(_pullTimeConfig)+"\n"+"ChannelDieShared: "+parseInt(_pullTimeConfigShared)+"\n"+"\n"+"Last message: "+(_channelLastID>0?_channelLastID:"-")+"\n"+"Time init connect: "+_pullTimeConst+"\n"+"Time last connect: "+(_pullTime==_pullTimeConst?"-":_pullTime)+"\n"+"Watch tags: "+(e=="{}"?"-":e)+"\n"+"================================\n";return console.log(t)};BX.PULL.clearChannelId=function(e){e=e==false?false:true;_channelClearReason=15;_channelID=null;_pullPath=null;if(_wsPath)_wsPath=null;if(_WS)_WS.close(1e3,"clear_channel_id");_updateStateSend=false;clearTimeout(_updateStateTimeout);if(e)BX.PULL.updateState("30")};BX.PULL.supportWebSocket=function(){var e=false;if(typeof WebSocket!="undefined"&&!BX.localStorage.get("pbws")){if(BX.browser.IsFirefox()||BX.browser.IsChrome()||BX.browser.IsOpera()||BX.browser.IsSafari()){if(BX.browser.IsFirefox()&&navigator.userAgent.substr(navigator.userAgent.indexOf("Firefox/")+8,2)>=25)e=true;else if(BX.browser.IsChrome()&&navigator.appVersion.substr(navigator.appVersion.indexOf("Chrome/")+7,2)>=28)e=true;else if(!BX.browser.IsChrome()&&BX.browser.IsSafari()&&navigator.appVersion.substr(navigator.appVersion.indexOf("Version/")+8,1)>=6)e=true}else if(BX.browser.DetectIeVersion()>=10){e=true}}return e};BX.PULL.getModernPath=function(e){if(typeof e!="object")return"";var t="";if(typeof e.PATH!="undefined"&&typeof e.PATH_MOD!="undefined"&&e.PATH_MOD!=""){if(BX.browser.IsIE()||BX.browser.IsOpera()){t=e.PATH}else{t=e.PATH_MOD}}else if(typeof e.PATH!="undefined"){return e.PATH}return t};BX.PULL.getRevision=function(){return _revision};BX.PULL.getDebugInfoArray=function(){return{connected:_updateStateSend,websocket:_wsConnected,path:_pullPath}};BX.PULL.checkRevision=function(e){e=parseInt(e);if(typeof e=="number"&&_revision<e){BX.PULL.openConfirm(BX.message("PULL_OLD_REVISION"));_pullTryConnect=false;if(_WS)_WS.close(1e3,"check_revision");BX.onCustomEvent(window,"onPullRevisionUp",[e,this.revision]);return false}return true};BX.PULL.returnPrivateVar=function(v){return eval(v)};BX.PULL.setPrivateVar=function(va,ve){return eval(va+" = "+ve)};BX.PULL.openConfirm=function(e,t,n){if(_confirm!=null)_confirm.destroy();n=n!==false;if(typeof t=="undefined"||typeof t=="object"&&t.length<=0){t=[new BX.PopupWindowButton({text:BX.message("IM_NOTIFY_CONFIRM_CLOSE"),className:"popup-window-button-decline",events:{click:function(e){this.popupWindow.close();BX.PreventDefault(e)}}})]}_confirm=new BX.PopupWindow("bx-notifier-popup-confirm",null,{zIndex:200,autoHide:t===false,buttons:t,closeByEsc:t===false,overlay:n,events:{onPopupClose:function(){this.destroy()},onPopupDestroy:BX.delegate(function(){_confirm=null},this)},content:BX.create("div",{props:{className:t===false?" bx-messenger-confirm-without-buttons":"bx-messenger-confirm"},html:e})});_confirm.show();BX.bind(_confirm.popupContainer,"click",BX.PULL.preventDefault);BX.bind(_confirm.contentContainer,"click",BX.PreventDefault);BX.bind(_confirm.overlay.element,"click",BX.PreventDefault)};BX.PULL.closeConfirm=function(){if(_confirm!=null)_confirm.destroy()};BX.PULL.preventDefault=function(e){e=e||window.event;if(e.stopPropagation)e.stopPropagation();else e.cancelBubble=true};BX.PULL()})(window);
/* End */
;
//# sourceMappingURL=kernel_pull.map.js