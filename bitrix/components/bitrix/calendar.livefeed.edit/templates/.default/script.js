;(function(window){

	//
	window.EditEventManager = function(config)
	{
		this.config = config;
		this.id = this.config.id;
		this.bAMPM = this.config.bAMPM;
		//this.bPanelShowed = true;
		this.bFullDay = false;
		this.bReminder = false;
		this.bAdditional = false;

		var _this = this;

		BX.addCustomEvent('onCalendarLiveFeedShown', function()
		{
			_this.Init();

			_this.defaultValues = {
				remind: {count: 15, type: 'min'}
			};

			_this.config.arEvent = _this.HandleEvent(_this.config.arEvent);
			_this.ShowFormData(_this.config.arEvent);
		});
	};

	window.EditEventManager.prototype = {
		Init: function()
		{
			var _this = this;
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

			this.pEventName = BX('feed-cal-event-name' + this.id);
			this.pForm = this.pEventName.form;
			this.pLocation = BX('event-location' + this.id);
			this.pImportance = BX('event-importance' + this.id);
			this.pAccessibility = BX('event-accessibility' + this.id);
			this.pSection = BX('event-section' + this.id);
			this.pRemCount = BX('event-remind_count' + this.id);
			this.pRemType = BX('event-remind_type' + this.id);

			// Control events
			this.pFullDay.onclick = BX.proxy(this.FullDay, this);
			this.pReminder.onclick = BX.proxy(this.Reminder, this);

			BX.bind(this.pForm, 'submit', BX.proxy(this.OnSubmit, this));
			// *************** Init events ***************

			BX("feed-cal-additional-show").onclick = BX("feed-cal-additional-hide").onclick = BX.proxy(this.ShowAdditionalParams, this);

			this.InitDateTimeControls();

			var oEditor = window["BXHtmlEditor"].Get(this.config.editorId);
			if (oEditor && oEditor.IsShown())
			{
				this.CustomizeHtmlEditor(oEditor);
			}
			else
			{
				BX.addCustomEvent(window["BXHtmlEditor"], 'OnEditorCreated', function(editor)
				{
					if (editor.id == _this.config.editorId)
					{
						_this.CustomizeHtmlEditor(editor);
					}
				});
			}

			// repeat
			this.pRepeat = BX('event-repeat' + this.id);
			this.pRepeatDetails = BX('event-repeat-details' + this.id);
			this.RepeatDiapTo = BX('event-repeat-to' + this.id);
			this.RepeatDiapToValue = BX('event-repeat-to-value' + this.id);

			this.pRepeat.onchange = function()
			{
				var value = this.value;
				_this.pRepeatDetails.className = "feed-cal-repeat-details feed-cal-repeat-details-" + value.toLowerCase();
			};
			this.pRepeat.onchange();

			this.RepeatDiapTo.onclick = function(){
				BX.calendar({node: this, field: this, bTime: false});
				BX.focus(this);
			};
			this.RepeatDiapTo.onfocus = function()
			{
				if (!this.value || this.value == _this.config.message.NoLimits)
					this.title = this.value = '';
				this.style.color = '#000000';
			};
			this.RepeatDiapTo.onblur = this.RepeatDiapTo.onchange = function()
			{
				if (this.value && this.value != _this.config.message.NoLimits)
				{
					var until = BX.parseDate(this.value);
					if (until && until.getTime)
						_this.RepeatDiapToValue.value = BX.date.getServerTimestamp(until.getTime());
					this.style.color = '#000000';
					this.title = '';
					return;
				}
				this.title = this.value = _this.config.message.NoLimits;
				this.style.color = '#C0C0C0';
			};
			this.RepeatDiapTo.onchange();

			this.eventNode = BX('div' + this.config.editorId);
			if (this.eventNode)
			{
				BX.onCustomEvent(this.eventNode, 'OnShowLHE', ['justShow']);
			}
		},

		CustomizeHtmlEditor: function(editor)
		{
			if (editor.toolbar.controls && editor.toolbar.controls.spoiler)
			{
				BX.remove(editor.toolbar.controls.spoiler.pCont);
			}
		},

		InitDateTimeControls: function()
		{
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
			this.pFromTime.parentNode.onclick = this.pFromTime.onclick = window['bxShowClock_' + 'feed_cal_event_from_time' + this.id];
			this.pToTime.parentNode.onclick = this.pToTime.onclick = window['bxShowClock_' + 'feed_cal_event_to_time' + this.id];

			this.pFromTime.onchange = function()
			{
				var fromTime, toTime;
				if (_this.pToTime.value == "")
				{
					if(BX.util.trim(_this.pFromDate.value) == BX.util.trim(_this.pToDate.value) && BX.util.trim(_this.pToDate.value) != '')
					{
						fromTime = _this.ParseTime(this.value);
						if (fromTime.h >= 23)
						{
							_this.pToTime.value = formatTimeByNum(0, fromTime.m, _this.bAMPM);
							var date = BX.parseDate(_this.pFromDate.value);
							if (date)
							{
								date.setDate(date.getDate() + 1);
								_this.pToDate.value = bxFormatDate(date.getDate(), date.getMonth() + 1, date.getFullYear());
							}
						}
						else
						{
							_this.pToTime.value = formatTimeByNum(parseInt(fromTime.h, 10) + 1, fromTime.m, _this.bAMPM);
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
							prevFromTime = _this.ParseTime(_this._FromTimeValue);

						fromTime = _this.ParseTime(_this.pFromTime.value);
						toTime = _this.ParseTime(_this.pToTime.value);

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
							_this.pToTime.value = formatTimeByNum(T.getHours(), T.getMinutes(), _this.bAMPM);
						}
					}
				}

				_this._FromTimeValue = _this.pFromTime.value;
			};
		},

		OnSubmit: function()
		{

			// Datetime limits
			var fd = BX.parseDate(this.pFromDate.value);
			var td = BX.parseDate(this.pToDate.value);

			if (!fd)
				fd = getUsableDateTime(new Date().getTime()).oDate;

			if (this.pFromTime.value == '' && this.pToTime.value == '')
				this.pFullDay.checked = true;

			if (this.pFullDay.checked)
				this.pFromTime.value = this.pToTime.value = '';

			var fromTime = this.ParseTime(this.pFromTime.value);
			fd.setHours(fromTime.h);
			fd.setMinutes(fromTime.m);
			var
				to,
				from = BX.date.getServerTimestamp(fd.getTime());

			if (td)
			{
				var toTime = this.ParseTime(this.pToTime.value);
				td.setHours(toTime.h);
				td.setMinutes(toTime.m);
				to = BX.date.getServerTimestamp(td.getTime());

				if (from == to && toTime.h == 0 && toTime.m == 0)
				{
					fd.setHours(0);
					fd.setMinutes(0);
					td.setHours(0);
					td.setMinutes(0);

					from = BX.date.getServerTimestamp(fd.getTime());
					to = BX.date.getServerTimestamp(td.getTime());
				}
			}

			this.pFromTs.value = from;
			this.pToTs.value = to;
		},

		HandleEvent: function(oEvent)
		{
			if(oEvent)
			{
				oEvent.DT_FROM_TS = BX.date.getBrowserTimestamp(oEvent.DT_FROM_TS);
				oEvent.DT_TO_TS = BX.date.getBrowserTimestamp(oEvent.DT_TO_TS);

				if (oEvent.DT_FROM_TS > oEvent.DT_TO_TS)
					oEvent.DT_FROM_TS = oEvent.DT_TO_TS;

				if ((oEvent.RRULE && oEvent.RRULE.FREQ && oEvent.RRULE.FREQ != 'NONE'))
				{
					oEvent['~DT_FROM_TS'] = BX.date.getBrowserTimestamp(oEvent['~DT_FROM_TS']);
					oEvent['~DT_TO_TS'] = BX.date.getBrowserTimestamp(oEvent['~DT_TO_TS']);

					if (oEvent.RRULE && oEvent.RRULE.UNTIL)
						oEvent.RRULE.UNTIL = BX.date.getBrowserTimestamp(oEvent.RRULE.UNTIL);
				}
			}
			return oEvent;
		},

		ShowFormData: function(oEvent)
		{
			var bNew = false;
			if (!oEvent || !oEvent.ID)
			{
				bNew = true;
				oEvent = {};
			}

			// Name
			this.pEventName.value = oEvent.NAME || '';

			// From / To
			var fd, td;
			if (oEvent.DT_FROM_TS || oEvent.DT_TO_TS)
			{
				if (!(oEvent.RRULE && oEvent.RRULE.FREQ && oEvent.RRULE.FREQ != 'NONE'))
				{
					fd = bxGetDateFromTS(oEvent.DT_FROM_TS);
					td = bxGetDateFromTS(oEvent.DT_TO_TS);
				}
				else
				{
					fd = bxGetDateFromTS(oEvent['~DT_FROM_TS']);
					td = bxGetDateFromTS(oEvent['~DT_TO_TS']);
				}
			}
			else
			{
				fd = getUsableDateTime(new Date().getTime());
				td = getUsableDateTime(new Date().getTime() + 3600000 /* one hour*/);
			}

			if (fd)
			{
				this._FromDateValue = this.pFromDate.value = bxFormatDate(fd.date, fd.month, fd.year);
				this._FromTimeValue = this.pFromTime.value = fd.bTime ? formatTimeByNum(fd.hour, fd.min, this.bAMPM) : '';
			}
			else
			{
				this._FromDateValue = this._FromTimeValue = this.pFromDate.value = this.pFromTime.value = '';
			}

			if (td)
			{
				this.pToDate.value = bxFormatDate(td.date, td.month, td.year);
				this.pToTime.value = td.bTime ? formatTimeByNum(td.hour, td.min, this.bAMPM) : '';
			}
			else
			{
				this.pToDate.value = this.pToTime.value = '';
			}

			this.pFullDay.checked = oEvent.DT_SKIP_TIME == "Y";
			this.FullDay(false, oEvent.DT_SKIP_TIME !== "Y");

			if (bNew)
			{
				this.pLocation.value = '';
				this.pImportance.value = 'normal';
				this.pAccessibility.value = 'busy';
				if (this.pSection.options && this.pSection.options.length > 0)
					this.pSection.value = this.pSection.options[0].value;

				this.pReminder.checked = !!this.defaultValues.remind;
				this.pRemCount.value = (this.defaultValues.remind && this.defaultValues.remind.count) || '15';
				this.pRemType.value = (this.defaultValues.remind && this.defaultValues.remind.type) || 'min';
			}
			else
			{
				this.pLocation.value = oEvent.LOCATION;
				this.pImportance.value = oEvent.IMPORTANCE;
				this.pAccessibility.value = oEvent.ACCESSIBILITY;
				this.pSection.value = oEvent.SECT_ID;


				// Remind
				this.pReminder.checked = oEvent.REMIND && oEvent.REMIND[0];
				this.pRemCount.value = oEvent.REMIND[0].count;
				this.pRemType.value = oEvent.REMIND[0].type;
			}
			this.Reminder(false, true);

			var _this = this;
			setTimeout(function()
			{
				BX.focus(_this.pEventName);
			}, 100);
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

			this.pReminderCont.className = value ? 'feed-event-reminder' : 'feed-event-reminder-collapsed';

			this.bReminder = value;
		},

		ShowAdditionalParams: function()
		{
			var value = !this.bAdditional;
			if (!this.pAdditionalCont)
				this.pAdditionalCont = BX("feed-cal-additional");

			if (value)
				BX.removeClass(this.pAdditionalCont, 'feed-event-additional-hidden');
			else
				BX.addClass(this.pAdditionalCont, 'feed-event-additional-hidden');

			this.bAdditional = value;
		},

		ParseTime: function(str)
		{
			var h, m, arTime;
			str = BX.util.trim(str);
			str = str.toLowerCase();

			if (this.bAMPM)
			{
				var ampm = 'pm';
				if (str.indexOf('am') != -1)
					ampm = 'am';

				str = str.replace(/[^\d:]/ig, '');
				arTime = str.split(':');
				h = parseInt(arTime[0] || 0, 10);
				m = parseInt(arTime[1] || 0, 10);

				if (h == 12)
				{
					if (ampm == 'am')
						h = 0;
					else
						h = 12;
				}
				else if (h != 0)
				{
					if (ampm == 'pm' && h < 12)
					{
						h += 12;
					}
				}
			}
			else
			{
				arTime = str.split(':');
				h = arTime[0] || 0;
				m = arTime[1] || 0;

				if (h.toString().length > 2)
					h = parseInt(h.toString().substr(0, 2));
				m = parseInt(m);
			}

			if (isNaN(h) || h > 24)
				h = 0;
			if (isNaN(m) || m > 60)
				m = 0;

			return {h: h, m: m};
		}
	};

	// Calbacks for destination
	window.BXEvDestSetLinkName = function(name)
	{
		if (BX.SocNetLogDestination.getSelectedCount(name) <= 0)
			BX('feed-event-dest-add-link').innerHTML = BX.message("BX_FPD_LINK_1");
		else
			BX('feed-event-dest-add-link').innerHTML = BX.message("BX_FPD_LINK_2");
	};

	window.BXEvDestSelectCallback = function(item, type, search)
	{
		var
			type1 = type,
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

		BX('feed-event-dest-item').appendChild(
			BX.create("span", { attrs : { 'data-id' : item.id }, props : { className : "feed-event-destination feed-event-destination-"+type1 }, children: [
				BX.create("input", { attrs : { 'type' : 'hidden', 'name' : 'EVENT_PERM[' + prefix + '][]', 'value' : item.id }}),
				BX.create("span", { props : { 'className' : "feed-event-destination-text" }, html : item.name}),
				BX.create("span", { props : { 'className' : "feed-event-del-but"}, events : {'click' : function(e){BX.SocNetLogDestination.deleteItem(item.id, type, destinationFormName);BX.PreventDefault(e)}, 'mouseover' : function(){BX.addClass(this.parentNode, 'feed-event-destination-hover')}, 'mouseout' : function(){BX.removeClass(this.parentNode, 'feed-event-destination-hover')}}})
			]})
		);

		BX('feed-event-dest-input').value = '';
		BXEvDestSetLinkName(destinationFormName);
	};

	// remove block
	window.BXEvDestUnSelectCallback = function(item, type, search)
	{
		var elements = BX.findChildren(BX('feed-event-dest-item'), {attribute: {'data-id': ''+item.id+''}}, true);
		if (elements != null)
		{
			for (var j = 0; j < elements.length; j++)
				BX.remove(elements[j]);
		}
		BX('feed-event-dest-input').value = '';
		BXEvDestSetLinkName(destinationFormName);
	};
	window.BXEvDestOpenDialogCallback = function()
	{
		BX.style(BX('feed-event-dest-input-box'), 'display', 'inline-block');
		BX.style(BX('feed-event-dest-add-link'), 'display', 'none');
		BX.focus(BX('feed-event-dest-input'));
	};

	window.BXEvDestCloseDialogCallback = function()
	{
		if (!BX.SocNetLogDestination.isOpenSearch() && BX('feed-event-dest-input').value.length <= 0)
		{
			BX.style(BX('feed-event-dest-input-box'), 'display', 'none');
			BX.style(BX('feed-event-dest-add-link'), 'display', 'inline-block');
			BXEvDestDisableBackspace();
		}
	};

	window.BXEvDestCloseSearchCallback = function()
	{
		if (!BX.SocNetLogDestination.isOpenSearch() && BX('feed-event-dest-input').value.length > 0)
		{
			BX.style(BX('feed-event-dest-input-box'), 'display', 'none');
			BX.style(BX('feed-event-dest-add-link'), 'display', 'inline-block');
			BX('feed-event-dest-input').value = '';
			BXEvDestDisableBackspace();
		}

	};
	window.BXEvDestDisableBackspace = function()
	{
		if (BX.SocNetLogDestination.backspaceDisable || BX.SocNetLogDestination.backspaceDisable != null)
			BX.unbind(window, 'keydown', BX.SocNetLogDestination.backspaceDisable);

		BX.bind(window, 'keydown', BX.SocNetLogDestination.backspaceDisable = function(e)
		{
			if (e.keyCode == 8)
			{
				BX.PreventDefault(e);
				return false;
			}
		});
		setTimeout(function()
		{
			BX.unbind(window, 'keydown', BX.SocNetLogDestination.backspaceDisable);
			BX.SocNetLogDestination.backspaceDisable = null;
		}, 5000);
	};

	window.BXEvDestSearchBefore = function(event)
	{
		if (event.keyCode == 8 && BX('feed-event-dest-input').value.length <= 0)
		{
			BX.SocNetLogDestination.sendEvent = false;
			BX.SocNetLogDestination.deleteLastItem(destinationFormName);
		}

		return true;
	};
	window.BXEvDestSearch = function(event)
	{
		if (event.keyCode == 16 || event.keyCode == 17 || event.keyCode == 18 || event.keyCode == 20 || event.keyCode == 244 || event.keyCode == 224 || event.keyCode == 91)
			return false;

		if (event.keyCode == 13)
		{
			BX.SocNetLogDestination.selectFirstSearchItem(destinationFormName);
			return true;
		}
		if (event.keyCode == 27)
		{
			BX('feed-event-dest-input').value = '';
			BX.style(BX('feed-event-dest-add-link'), 'display', 'inline');
		}
		else
		{
			BX.SocNetLogDestination.search(BX('feed-event-dest-input').value, true, destinationFormName);
		}

		if (!BX.SocNetLogDestination.isOpenDialog() && BX('feed-event-dest-input').value.length <= 0)
		{
			BX.SocNetLogDestination.openDialog(destinationFormName);
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
	};

	function bxFormatDate(d, m, y)
	{
		var str = BX.message("FORMAT_DATE");

		str = str.replace(/YY(YY)?/ig, y);
		str = str.replace(/MMMM/ig, BX.message('MONTH_' + this.Number(m)));
		str = str.replace(/MM/ig, zeroInt(m));
		str = str.replace(/M/ig, BX.message('MON_' + this.Number(m)));
		str = str.replace(/DD/ig, zeroInt(d));

		return str;
	}

	function zeroInt(x)
	{
		x = parseInt(x, 10);
		if (isNaN(x))
			x = 0;
		return x < 10 ? '0' + x.toString() : x.toString();
	}

	function bxGetDateFromTS(ts, getObject)
	{
		var oDate = new Date(ts);
		if (!getObject)
		{
			var
				ho = oDate.getHours() || 0,
				mi = oDate.getMinutes() || 0;

			oDate = {
				date: oDate.getDate(),
				month: oDate.getMonth() + 1,
				year: oDate.getFullYear(),
				bTime: !!(ho || mi),
				oDate: oDate
			};

			if (oDate.bTime)
			{
				oDate.hour = ho;
				oDate.min = mi;
			}
		}

		return oDate;
	}

	function getUsableDateTime(timestamp, roundMin)
	{
		var date = bxGetDateFromTS(timestamp);
		if (!roundMin)
			roundMin = 10;

		date.min = Math.ceil(date.min / roundMin) * roundMin;

		if (date.min == 60)
		{
			if (date.hour == 23)
				date.bTime = false;
			else
				date.hour++;
			date.min = 0;
		}

		date.oDate.setHours(date.hour);
		date.oDate.setMinutes(date.min);
		return date;
	}

	function formatTimeByNum(h, m, bAMPM)
	{
		var res = '';
		if (m == undefined)
			m = '00';
		else
		{
			m = parseInt(m, 10);
			if (isNaN(m))
				m = '00';
			else
			{
				if (m > 59)
					m = 59;
				m = (m < 10) ? '0' + m.toString() : m.toString();
			}
		}

		h = parseInt(h, 10);
		if (h > 24)
			h = 24;
		if (isNaN(h))
			h = 0;

		if (bAMPM)
		{
			var ampm = 'am';

			if (h == 0)
			{
				h = 12;
			}
			else if (h == 12)
			{
				ampm = 'pm';
			}
			else if (h > 12)
			{
				ampm = 'pm';
				h -= 12;
			}

			res = h.toString() + ':' + m.toString() + ' ' + ampm;
		}
		else
		{
			res = ((h < 10) ? '0' : '') + h.toString() + ':' + m.toString();
		}
		return res;
	}

})(window);


