<?if(!defined("B_PROLOG_INCLUDED")||B_PROLOG_INCLUDED!==true)die();
$APPLICATION->SetPageProperty("BodyClass","calendar-addevent-page");
?>

<?
CUtil::InitJSCore(array('ajax', 'date'));
/* *********************** From To modal window **************************** */
if($arResult['GET_FROM_TO_MODE'] == 'Y'):?>

<script>
	BX.addCustomEvent("onOpenPageAfter", onPageShown);
	BX.addCustomEvent("onHidePageAfter", function(){app.hideDatePicker();});

	app.pullDown({
		enable: true,
		pulltext: '<?= GetMessage('PULL_TEXT')?>',
		downtext: '<?= GetMessage('DOWN_TEXT')?>',
		loadtext: '<?= GetMessage('LOAD_TEXT')?>',
		callback:function(){document.location.reload();}
	});

	app.addButtons({
		okButton:
		{
			type: 'right_text',
			style: 'custom',
			name: '<?= GetMessageJS('MBCAL_EDEV_OK');?>',
			callback: function()
			{
				app.onCustomEvent('onCalendarEventTimeChange',
					{
						from: window.curFrom,
						to: window.curTo,
						fullDay: window.curFullDay ? 'Y' : 'N',
						cancelRepeat: window.bCancelRepeat ? 'Y' : 'N'
					});
				app.closeController({drop: true});
			}
		}
	});

	function getTimestamp(str)
	{
		//Format: "month/day/year hour:minute"
		return Date.parse(str);
	}

	function getStrDate(timestamp)
	{
		var d = new Date(timestamp);
		//Format: "month/day/year hour:minute"
		return (d.getMonth() + 1) + '/' + d.getDate() + '/' + d.getFullYear() + ' ' + d.getHours() + ':' + d.getMinutes();
	}

	function onPageShown()
	{
		var
			format = 'M/d/y H:m',
			pFullDay = BX('mbcal-edit-full-day'),
			pFromVal = BX('mbcal-edit-from-value'),
			pToVal = BX('mbcal-edit-to-value'),
			pRepeatCont = BX('mbcal-edit-repeat-cont'),
			pRepeatVal = BX('mbcal-edit-repeat-2');

		function setDate(timestamp, pCont)
		{
			var d = new Date(timestamp);
			d.setHours(0, 0, 0, 0);

			// 1. Determine date: today, tomorrow, yesterday or full date
			var res = BX.date.format([
				["today", "today"],
				["tommorow", "tommorow"],
				["yesterday", "yesterday"],
				["" , "<?= GetMessage('MB_CAL_EVENT_DATE_FORMAT')?>"]
			], d);

			// 2 Determine time
			if (!window.curFullDay)
			{
				var timeFormat = this.bAmPm ? "<?= GetMessage('MB_CAL_EVENT_TIME_FORMAT_AMPM')?>" : "<?= GetMessage('MB_CAL_EVENT_TIME_FORMAT')?>";
				res += ' ' + BX.date.format(timeFormat, new Date(timestamp));
			}

			pCont.innerHTML = res;
		}

		function fromOnChange(value)
		{
			window.curFrom = getTimestamp(value);
			window.curTo = window.curFrom + window.curLength;
			setDate(window.curFrom, pFromVal);
			setDate(window.curTo, pToVal);
		}

		function toOnChange(value)
		{
			window.curTo = getTimestamp(value);
			window.curLength = window.curTo - window.curFrom;
			setDate(window.curTo, pToVal);
		}

		function getPickerFormat()
		{
			return window.curFullDay ? 'date' : 'datetime';
		}

		function openDateTimePicker(type, timestamp)
		{
			window.currentPickerType = type;

			app.showDatePicker({
				start_date: getStrDate(timestamp),
				format: format,
				type: getPickerFormat(),
				callback: type == 'from' ? fromOnChange : toOnChange
			});
		}

		app.getPageParams({callback: function(params)
		{
			window.bCancelRepeat = false;
			window.curFrom = params.from || new Date().getTime(); // Timestamp in ms
			window.curTo = params.to || window.curFrom + 3600000; // Timestamp in ms
			window.curLength = window.curTo - window.curFrom;
			window.repeatHtml = params.repeat_html;

			window.curFullDay = params.full_day == 'Y'; // true | false
			pFullDay.value = params.full_day; // Y | N

			setDate(window.curFrom, pFromVal);
			setDate(window.curTo, pToVal);

			// repeat
			if (!window.repeatHtml)
			{
				pRepeatCont.style.display = 'none';
			}
			else
			{
				pRepeatCont.style.display = '';
				pRepeatVal.innerHTML = window.repeatHtml;
				var cancel = BX('mbcal-edit-repeat-cancel');

				if (cancel.__bxhandler && cancel.__bxhandler.destroy && typeof cancel.__bxhandler.destroy == 'function')
					cancel.__bxhandler.destroy();
				cancel.__bxhandler = new FastButton(cancel, function(e){
					pRepeatCont.style.display = 'none';
					window.bCancelRepeat = true;
					//return false;
					return BX.PreventDefault(e);
				}, false);
			}

			openDateTimePicker('from', window.curFrom);
		}});

		BX('mbcal-edit-from').onclick = function(){openDateTimePicker('from', window.curFrom);};
		BX('mbcal-edit-to').onclick = function(){openDateTimePicker('to', window.curTo);};

		pFullDay.onchange = function()
		{
			window.curFullDay = this.value == 'Y';
			openDateTimePicker(window.currentPickerType, window.currentPickerType == 'from' ? window.curFrom : window.curTo);
			setDate(window.curFrom, pFromVal);
			setDate(window.curTo, pToVal);
		}
	}

	BX.ready(onPageShown);

</script>
	<div class="calendar-addevent-main-block" id='mbcal-edit-from'>
		<div class="calendar-addevent-row">
			<div class="calendar-addevent-row-container">
				<span class="calendar-addevent-row-left"><?= GetMessage('MBCAL_EDEV_FROM')?></span>
				<span id='mbcal-edit-from-value'></span>
				<div style="clear: both;"></div>
			</div>
			<div class="calendar-addevent-arrow"></div>
		</div>
	</div>
	<div class="calendar-addevent-main-block" id='mbcal-edit-to'>
		<div class="calendar-addevent-row">
			<div class="calendar-addevent-row-container">
				<span class="calendar-addevent-row-left"><?= GetMessage('MBCAL_EDEV_TO')?></span>
				<span id='mbcal-edit-to-value'></span>
				<div style="clear: both;"></div>
			</div>
			<div class="calendar-addevent-arrow"></div>
		</div>
	</div>
	<div class="calendar-addevent-main-block">
		<div class="calendar-addevent-row">
			<div class="calendar-addevent-row-container">
				<span class="calendar-addevent-row-left"><?= GetMessage('MBCAL_EDEV_ALLDAY')?></span>
				<span class="calendar-addevent-row-right">
					<select id='mbcal-edit-full-day'>
						<option value="Y"><?= GetMessage('MBCAL_EDEV_YES')?></option>
						<option value="N"><?= GetMessage('MBCAL_EDEV_NO')?></option>
					</select>
				</span>
				<div style="clear: both;"></div>
			</div>
		</div>
	</div>

	<div class="calendar-addevent-main-block" id="mbcal-edit-repeat-cont">
		<div class="calendar-addevent-row">
			<div class="calendar-addevent-row-container">
				<span class="calendar-addevent-row-left"><?= GetMessage('MBCAL_REPEAT_TITLE')?></span>
				<span class="calendar-addevent-row-right"><span id="mbcal-edit-repeat-2"></span><br><a id="mbcal-edit-repeat-cancel" href="#"><?= GetMessage('MBCAL_REPEAT_CLEAR')?></a></span>
				<div style="clear: both;"></div>
			</div>
		</div>
	</div>
<?
/* END *********************** From To modal window **************************** */
else: /*$arResult('GET_FROM_TO_MODE') == 'Y')*/?>

<script>
	app.pullDown({
		enable: true,
		pulltext: '<?= GetMessage('PULL_TEXT')?>',
		downtext: '<?= GetMessage('DOWN_TEXT')?>',
		loadtext: '<?= GetMessage('LOAD_TEXT')?>',
		callback:function(){document.location.reload();}
	});

	(function() {
		var BX = window.BX;

		function EditEventManager(data)
		{
			this.Init(data);
		}

		EditEventManager.prototype.Init = function(data)
		{
			// We trying to edit deleted event - so just go to the list
			if (data.DELETED == 'Y')
			{
				app.onCustomEvent('onCalendarEventRemoved', {event_id: data.EVENT_ID});
				app.removeTableCache('calendar_list');
				app.closeController({drop: true});
				return;
			}

			var _this = this;
			this.url = '/mobile/calendar/edit_event.php';
			//this.userId = data.USER_ID;
			this.oEvent = data.EVENT || false;
			this.bAmPm = <?= IsAmPmMode() ? 'true' : 'false'?>;
			this.arAttendees = (this.oEvent && this.oEvent.IS_MEETING) ? data.ATTENDEES : [];
			this.arAttendeeIndex = {};
			for(var i in this.arAttendees)
				if (this.arAttendees.hasOwnProperty(i))
					this.arAttendeeIndex[this.arAttendees[i].USER_ID] = i;

			this.ownerId = data.OWNER_ID;
			this.calType = data.CAL_TYPE;

			this.pForm = {
				// Title
				title: BX('mbcal-edit-title'),
				// Base params
				name: BX('mbcal-edit-name'),
				fromToCont: BX('mbcal-edit-from-to-cont'),
				from: BX('mbcal-edit-from'),
				to: BX('mbcal-edit-to'),
				repeatCont: BX('mbcal-edit-repeat-cont'),
				repeat: BX('mbcal-edit-repeat'),
				remind: BX('mbcal-edit-remind'),
				description: BX('mbcal-edit-desc'),
				location: BX('mbcal-edit-location'),
				is_private: BX('mbcal-edit-private'),
				private_notice: BX('mbcal-edit-private-notice'),
				importance: BX('mbcal-edit-imp'),
				accessibility: BX('mbcal-edit-acc'),
				attCont: BX('mbcal-edit-attendees-cont-wrap'),
				userListCont: BX('mbcal-edit-att-cont'),
				attTitle: BX('mbcal-edit-att-title'),
				addBut: BX('mbcal-edit-att-add-but'),
				delEventBut:  BX('mbcal-edit-del-event-but'),
				delEventButCont:  BX('mbcal-edit-del-but-cont'),
				section: BX('mbcal-edit-section')
			};

			this._ClearFastButton(this.pForm.fromToCont.__bxhandler);
			this.pForm.fromToCont.__bxhandler = new FastButton(this.pForm.fromToCont, BX.proxy(this.OpenFromToControl, this),
				false);

			BX.addCustomEvent('onCalendarEventTimeChange', function(data)
			{
				window.bTriggerOnPageShown = false;
				_this.fullDay = data.fullDay == 'Y';
				if (_this.fullDay)
				{
					var
						dFrom = new Date(data.from),
						dTo = new Date(data.to);

					dFrom.setHours(0);
					dFrom.setMinutes(0);
					dTo.setHours(0);
					dTo.setMinutes(0);

					data.from = dFrom.getTime();
					data.to = dTo.getTime();
				}

				_this.from = data.from;
				_this.to = data.to;

				if (data.cancelRepeat == 'Y')
					_this.repeatHtml = '';

				_this.DisplayFromToControl(_this.from, _this.to, _this.fullDay, _this.repeatHtml);
			});

			this._ClearFastButton(this.pForm.addBut.__bxhandler);
			this.pForm.addBut.__bxhandler = new FastButton(this.pForm.addBut, BX.proxy(this.OpenAttendeesControl, this), false);

			this._ClearFastButton(this.pForm.attCont.__bxhandler);
			this.pForm.attCont.__bxhandler = new FastButton(this.pForm.attCont, BX.proxy(this.AttendeesOnClick, this), false);

			this.pForm.is_private.onchange = function()
			{
				_this.pForm.private_notice.style.display = this.value == 'Y' ? "" : "none";
			};

			this._ClearFastButton(this.pForm.delEventBut.__bxhandler);
			this.pForm.delEventBut.__bxhandler = new FastButton(this.pForm.delEventBut, BX.proxy(this.DeleteEvent, this), false);

			app.addButtons({
				saveButton:
				{
					type: 'right_text',
					style: 'custom',
					name: '<?= GetMessageJS('MB_CALENDAR_SAVE_BUT');?>',
					callback: BX.proxy(this.SaveEvent, this)
				}
			});
		};

		EditEventManager.prototype._ClearFastButton = function(ob)
		{
			if (ob && ob.destroy && typeof ob.destroy == 'function')
				ob.destroy();
		};

		EditEventManager.prototype.UpdateForm = function()
		{
			if (this.oEvent) // Editing existent event
			{
				this.pForm.title.innerHTML = '<?= GetMessageJS('MBCAL_EDEV_EDIT')?>';
				this.pForm.name.value = this.oEvent.NAME;
				this.pForm.description.value = this.oEvent.DESCRIPTION;
				this.location = this.oEvent['~LOCATION'];
				this.pForm.location.value = this.oEvent['~LOCATION'];
				this.pForm.is_private.value = this.oEvent.PRIVATE_EVENT ? "Y" : "N";
				this.pForm.private_notice.style.display = this.oEvent.PRIVATE_EVENT ? "" : "none";
				this.pForm.importance.value = this.oEvent.IMPORTANCE;
				this.pForm.accessibility.value = this.oEvent.ACCESSIBILITY;

				this.from = BX.date.getBrowserTimestamp(this.oEvent.DT_FROM_TS);
				this.to = BX.date.getBrowserTimestamp(this.oEvent.DT_TO_TS);
				this.fullDay = this.oEvent.DT_SKIP_TIME == 'Y';
				this.repeatHtml = '';

				// Reminder
				if (this.oEvent.REMIND && this.oEvent.REMIND[0])
				{
					var key = this.oEvent.REMIND[0].count + '_' + this.oEvent.REMIND[0].type;

					if (!{'5_min': 1, '15_min': 1, '30_min':1, '1_hour':1, '2_hour':1, '1_day':1, '2_day':1}[key])
					{
						var text = key;
						if (this.oEvent.REMIND[0].type == 'min')
							text = '<?= GetMessageJS('MBCAL_EDEV_REM_MIN')?>';
						else if(this.oEvent.REMIND[0].type == 'hour')
							text = '<?= GetMessageJS('MBCAL_EDEV_REM_HOURS')?>';
						else if(this.oEvent.REMIND[0].type == 'day')
							text = '<?= GetMessageJS('MBCAL_EDEV_REM_DAYS')?>';
						text = text.replace('#N#', this.oEvent.REMIND[0].count);

						this.pForm.remind.options.add(new Option(text, key, true, true), 1);
					}

					this.pForm.remind.value = key;
				}
				else
				{
					this.pForm.remind.value = 0;
				}

				this.pForm.section.value = this.oEvent.SECT_ID;
				this.pForm.delEventButCont.style.display = "";

				if (this.oEvent.RRULE !== '')
					this.repeatHtml = this.GetRepeatHtml();
			}
			else
			{
				this.pForm.title.innerHTML = '<?= GetMessageJS('MBCAL_EDEV_NEW')?>';
				this.pForm.name.value = '';
				this.pForm.description.value = '';
				this.pForm.location.value = '';
				this.pForm.is_private.value = 'N';
				this.pForm.private_notice.style.display = "none";
				this.pForm.importance.value = 'normal';
				this.pForm.accessibility.value = 'busy';

				this.from = this.GetUsableDateTime(new Date());
				this.to = parseInt(this.from, 10) + 3600000 /* one hour*/;
				this.fullDay = false;

				this.pForm.remind.value = 0;

				if (this.pForm.section.options.length > 0)
					this.pForm.section.value = this.pForm.section.options[0].value;

				this.pForm.delEventButCont.style.display = "none";
				this.repeatHtml = '';
			}

			this.DisplayFromToControl(this.from, this.to, this.fullDay, this.repeatHtml);

			while (this.pForm.userListCont.firstChild != this.pForm.addBut)
				this.pForm.userListCont.removeChild(this.pForm.userListCont.firstChild);
			this.DisplayAttendeesControl(this.arAttendees);
		};

		EditEventManager.prototype.GetUsableDateTime = function(oDate)
		{
			var
				roundMin = 10,
				min = Math.ceil(oDate.getMinutes() / roundMin) * roundMin,
				hour = oDate.getHours();

			if (min == 60)
			{
				if (hour != 23)
					hour++;
				min = 0;
			}

			oDate.setHours(hour);
			oDate.setMinutes(min);
			return oDate.getTime();
		};

		EditEventManager.prototype.GetRepeatHtml = function()
		{
			if (this.oEvent.RRULE == '')
				return '';

			var repeatHTML = '', interval =  this.oEvent.RRULE.INTERVAL;
			switch (this.oEvent.RRULE.FREQ)
			{
				case 'DAILY':
					repeatHTML += '<b><?= GetMessageJS('EC_JS_EVERY_M')?> ' + interval + '<?= GetMessageJS('EC_JS_DE_DOT')?><?= GetMessageJS('EC_JS__J')?> <?= GetMessageJS('EC_JS_DAY_P')?> </b>';
					break;
				case 'WEEKLY':
					repeatHTML += '<b><?= GetMessageJS('EC_JS_EVERY_F')?> ';
					if (interval > 1)
						repeatHTML += interval + '<?= GetMessageJS('EC_JS_DE_DOT')?><?= GetMessageJS('EC_JS__U')?> ';
					repeatHTML += '<?= GetMessageJS('EC_JS_WEEK_P')?>: ';
					var n = 0;
					for (var i in this.oEvent.RRULE.BYDAY)
					{
						if(this.oEvent.RRULE.BYDAY[i])
							repeatHTML += (n++ > 0 ? ', ' : '') + this.days[this.oEvent.RRULE.BYDAY[i]];
					}
					repeatHTML += '</b>';
					break;
				case 'MONTHLY':
					var date = new Date(BX.date.getBrowserTimestamp(this.oEvent.DT_FROM_TS)).getDate();
					repeatHTML += '<b><?= GetMessageJS('EC_JS_EVERY_M')?> ';
					if (interval > 1)
						repeatHTML += interval + '<?= GetMessageJS('EC_JS_DE_DOT')?><?= GetMessageJS('EC_JS__J')?> ';
					repeatHTML += '<?= GetMessageJS('EC_JS_MONTH_P')?>, <?= GetMessageJS('EC_JS_DE_AM')?>' + date + '<?= GetMessageJS('EC_JS_DE_DOT')?><?= GetMessageJS('EC_JS_DATE_P_')?></b>';
					break;
				case 'YEARLY':
					var date = new Date(BX.date.getBrowserTimestamp(this.oEvent.DT_FROM_TS)).getDate();
					var month = new Date(BX.date.getBrowserTimestamp(this.oEvent.DT_FROM_TS)).getMonth() + 1;
					repeatHTML += '<b><?= GetMessageJS('EC_JS_EVERY_N_')?> ';
					if (interval > 1)
						repeatHTML += interval + '<?= GetMessageJS('EC_JS_DE_DOT')?><?= GetMessageJS('EC_JS__J')?> ';
					repeatHTML += '<?= GetMessageJS('EC_JS_YEAR_P')?>, <?= GetMessageJS('EC_JS_DE_AM')?>' + date + '<?= GetMessageJS('EC_JS_DE_DOT')?><?= GetMessageJS('EC_JS_DATE_P_')?> <?= GetMessageJS('EC_JS_DE_DES')?>' + month + '<?= GetMessageJS('EC_JS_DE_DOT')?><?= GetMessageJS('EC_JS_MONTH_P_')?></b>';
					break;
			}

			var dateFormat = "<?= GetMessage('MB_CAL_EVENT_DATE_FORMAT')?>";
			repeatHTML += '<br> <?= GetMessageJS('EC_JS_FROM_')?> ' + BX.date.format(dateFormat, new Date(BX.date.getBrowserTimestamp(this.oEvent['DT_FROM_TS'])));

			var to = new Date(BX.date.getBrowserTimestamp(this.oEvent.RRULE.UNTIL));
			if (to.getMonth() != 0 || to.getFullYear() != 2038)
				repeatHTML += ' <?= GetMessageJS('EC_JS_TO_')?> ' + BX.date.format(dateFormat, to);

			return repeatHTML;
		};

		EditEventManager.prototype.SaveEvent = function()
		{
			var data = {
				app_calendar_action: 'save_event',
				sessid: BX.bitrix_sessid(),
				event_id: this.oEvent.ID || 0,
				name: this.pForm.name.value || '<?= GetMessageJS('MBCAL_EDEV_NAME_DEF')?>',
				desc: this.pForm.description.value,
				from_ts: BX.date.getServerTimestamp(this.from),
				to_ts: BX.date.getServerTimestamp(this.to),
				skip_time: this.fullDay ? 'Y' : 'N',
				accessibility: this.pForm.accessibility.value,
				importance: this.pForm.importance.value,
				private_event: this.pForm.is_private.value,
				location: {OLD: '', NEW: '', CHANGED: 'N'},
				sect_id: this.pForm.section.value,
				owner_id: this.ownerId,
				cal_type: this.calType,
				attendees: []
			};

			app.showPopupLoader({text:""});

			if (this.oEvent.ID)
				data.location.OLD = this.oEvent['LOCATION'];

			if (this.oEvent.ID && BX.util.trim(this.location.toLowerCase()) == BX.util.trim(this.pForm.location.value.toLowerCase()))
			{
				data.location.NEW = this.oEvent['LOCATION'];
			}
			else
			{
				data.location.NEW = BX.util.trim(this.pForm.location.value);
				data.location.CHANGED = 'Y';
			}

			if (this.pForm.remind.value != 0)
			{
				var rem = this.pForm.remind.value.split('_');
				if(rem[0] && rem[1])
					data.remind = [{type: rem[1], count: rem[0]}]
			}

			// Attendees
			for (var i in this.arAttendees)
				data.attendees.push(this.arAttendees[i]['USER_ID']);

			if (!this.oEvent.IS_MEETING && data.attendees.length)
				data.new_meeting = 'Y';

			if (this.repeatHtml == '' && this.oEvent.RRULE)
				data.rrule = '';

			app.removeTableCache('calendar_list');

			function onSaveEvent(result)
			{
				app.hidePopupLoader();
				app.removeTableCache('calendar_list');
				app.closeController({drop: true});
			}

			BX.ajax.post(this.url, data, onSaveEvent);
		};

		EditEventManager.prototype.OpenFromToControl = function()
		{
			window.bTriggerOnPageShown = false;
			app.openNewPage(this.url + '?app_calendar_action=from_to_control', {from: this.from, to: this.to,
				full_day: this.fullDay ? 'Y' : 'N', repeat_html: this.repeatHtml});
		};

		EditEventManager.prototype.DisplayFromToControl = function(from, to, bFullDay, repeatHtml)
		{
			this.pForm.from.innerHTML = this.GetNiceDateFormat(from, !bFullDay);
			this.pForm.to.innerHTML = this.GetNiceDateFormat(to, !bFullDay);

			if (!repeatHtml)
			{
				this.pForm.repeatCont.style.display = "none";
			}
			else
			{
				this.pForm.repeatCont.style.display = "";
				this.pForm.repeat.innerHTML = repeatHtml;
			}
		};

		EditEventManager.prototype.GetNiceDateFormat = function(ts, bShowTime)
		{
			var d = new Date(ts);
			d.setHours(0, 0, 0, 0);

			// 1. Determine date: today, tomorrow, yesterday or full date
			var res = BX.date.format([
				["today", "today"],
				["tommorow", "tommorow"],
				["yesterday", "yesterday"],
				["" , "<?= GetMessage('MB_CAL_EVENT_DATE_FORMAT')?>"]
			], d);

			// 2 Determine time
			if (bShowTime)
			{
				var timeFormat = this.bAmPm ? "<?= GetMessage('MB_CAL_EVENT_TIME_FORMAT_AMPM')?>" : "<?= GetMessage('MB_CAL_EVENT_TIME_FORMAT')?>";
				res += ' ' + BX.date.format(timeFormat, new Date(ts));
			}

			return res;
		};

		EditEventManager.prototype.AttendeesOnClick = function(e)
		{
			if (this.arAttendees.length === 0)
			{
				this.OpenUserSelector();
			}
			else
			{
				var target = e.target;
				if (!target)
					return BX.PreventDefault(e);

				var
					delBut, userId,
					btn = BX.findParent(target, {className: 'calendar-addevent-participant-btn'}, this.pForm.userListCont);

				if (btn)
				{
					BX.toggleClass(btn.parentNode, 'cal-delete-btn-open');
				}
				else if(delBut = BX.findParent(target, {className: 'cal-delete-right-btn-wrap'}, this.pForm.userListCont))
				{
					userId = delBut.getAttribute('data-bx-user-id');
					if (userId)
					{
						var pRow = BX.findParent(delBut, {className: 'calendar-addevent-participant-row'}, this.pForm.userListCont);
						if (pRow)
							this.pForm.userListCont.removeChild(pRow);
						this.DeleteAttendee(userId);
						this.CheckAttendeesControl();
					}
				}
			}

			return BX.PreventDefault(e);
		};

		EditEventManager.prototype.OpenAttendeesControl = function()
		{
			this.OpenUserSelector();
		};

		EditEventManager.prototype.DeleteAttendee = function(userId)
		{
			var
				arAttendeeIndex = {},
				arAttendees = [];

			for(var i in this.arAttendees)
			{
				if (this.arAttendees.hasOwnProperty(i))
				{
					if (this.arAttendees[i].USER_ID != userId)
					{
						arAttendeeIndex[this.arAttendees[i].USER_ID] = arAttendees.length;
						arAttendees.push(this.arAttendees[i]);
					}
				}
			}

			this.arAttendees = arAttendees;
			this.arAttendeeIndex = arAttendeeIndex;
		};

		EditEventManager.prototype.CheckAttendeesControl = function()
		{
			if (this.arAttendees.length === 0)
			{
				this.pForm.attTitle.innerHTML = '<?= GetMessageJS('MBCAL_EDEV_ADD_ATTENDEES')?>';
				BX.addClass(this.pForm.attCont, 'close');
			}
			else
			{
				this.pForm.attTitle.innerHTML = '<?= GetMessageJS('MBCAL_EDEV_ADD_ATTENDEES_TITLE')?>';
				BX.removeClass(this.pForm.attCont, 'close');
			}
		};

		EditEventManager.prototype.DisplayAttendeesControl = function(attendees)
		{
			this.CheckAttendeesControl();
			for(var i = 0; i < attendees.length; i++)
				this.DisplayAttendee(attendees[i]);
		};

		EditEventManager.prototype.OpenUserSelector = function()
		{
			window.bTriggerOnPageShown = false;
			var _this = this;
			app.openTable({
				url: '/mobile/index.php?mobile_action=get_user_list',
				callback: function(data)
				{
					window.bTriggerOnPageShown = false;

					if (!data || !data.a_users || !data.a_users.length)
						return;

					var
						attendees = [], att, user, userId,
						work_position, key;
					for (key in data.a_users)
					{
						user = data.a_users[key];
						userId = user['ID'].toString();

						if (_this.arAttendeeIndex[userId] == undefined)
						{
							work_position = user['WORK_POSITION'];

							att = {
								'USER_ID' : userId,
								'DISPLAY_NAME': user['NAME'],
								'WORK_POSITION': user['WORK_POSITION']
							};

							_this.arAttendeeIndex[userId] = _this.arAttendees.length;
							attendees.push(att);
							_this.arAttendees.push(att);
						}
					}

					_this.DisplayAttendeesControl(attendees);
				},
				markmode: true,
				multiple: true,
				return_full_mode: true,
				modal: false,
				alphabet_index: true,
				outsection: false,
				okname: '<?= GetMessageJS('MBCAL_EDEV_SELECT')?>',
				cancelname: '<?= GetMessageJS('MBCAL_EDEV_CANCEL')?>'
			});
		};

		EditEventManager.prototype.DisplayAttendee = function(user)
		{
			var
				userId = parseInt(user['USER_ID'], 10),
				userName = user['DISPLAY_NAME'],
				work = user['WORK_POSITION'] || '<?= GetMessageJS("MBCAL_EDEV_NO_WORK_POS")?>',
				pRow = BX.create('DIV', {props:{className: "calendar-addevent-participant-row"}});

			pRow.innerHTML = '<div class="calendar-addevent-participant-row-name">' +
					'<div data-removable-btn="true" class="cal-delete-right-btn-wrap" data-bx-user-id="' + userId + '">' +
					'	<?= GetMessageJS('MBCAL_EDEV_REMOVE');?>' +
					'	<div class="cal-delete-right-btn-block">' +
					'		<div class="cal-delete-right-btn"><?= GetMessageJS('MBCAL_EDEV_REMOVE');?></div>' +
					'	</div>' +
					'</div>' +
					'<a href="#" class="calendar-addevent-participant-row-link">' + userName + '</a>' +
					'</div>' +
					'<div class="calendar-addevent-participant-row-post">' + work + '</div>' +
					'<div class="calendar-addevent-participant-btn"><i class="cal-delete-minus"></i>' +
					'</div>';

			this.pForm.userListCont.insertBefore(pRow, this.pForm.addBut);
		};

		EditEventManager.prototype.DeleteEvent = function(e)
		{
			var _this = this;
			app.confirm({
				title: '<?= GetMessageJS('MBCAL_EDEV_DEL_EVENT_CONFIRM_TITLE')?>',
				text : '<?= GetMessageJS('MBCAL_EDEV_DEL_EVENT_CONFIRM')?>',
				buttons : ['<?= GetMessageJS('MBCAL_EDEV_OK')?>', '<?= GetMessageJS('MBCAL_EDEV_CANCEL')?>'],
				callback : function (btnNum)
				{
					if (btnNum == 1)
					{
						//remove the event
						app.showPopupLoader({text:""});
						app.removeTableCache('calendar_list');
						var
							event_id = _this.oEvent.ID,
							data = {
								app_calendar_action: 'drop_event',
								sessid: BX.bitrix_sessid(),
								event_id: event_id
							};

						function onDeleteEvent(result)
						{
							app.hidePopupLoader();
							app.onCustomEvent('onCalendarEventRemoved',
								{
									event_id: event_id
								});
							app.removeTableCache('calendar_list');
							app.closeController({drop: true});
						}

						BX.ajax.post(_this.url, data, onDeleteEvent);
						return BX.PreventDefault(e);
					}
				}
			});
		};

		window.EditEventManager = EditEventManager;
	})();

	BX.ready(function(){
		window.mobileEditEventManager = new EditEventManager(<?= CUtil::PhpToJSObject($arResult)?>);
		window.mobileEditEventManager.UpdateForm();
	});

	BX.addCustomEvent("onOpenPageBefore", function()
	{
		if (window.bTriggerOnPageShown !== false)
		{
			window.mobileEditEventManager = new EditEventManager(<?= CUtil::PhpToJSObject($arResult)?>);
			window.mobileEditEventManager.UpdateForm();
		}
		window.bTriggerOnPageShown = true;
	});
</script>


<div class="task-title" id="mbcal-edit-title"></div>
<div class="calendar-addevent-textar-wrap">
	<input id="mbcal-edit-name" type="text" class="calendar-addevent-input" placeholder="<?= GetMessage('MBCAL_EDEV_NAME')?>"/>

	<div class="calendar-event-main-block-aqua" id="mbcal-edit-from-to-cont">
		<div class="calendar-event-main-block-aqua-container">
			<div class="calendar-addevent-row">
				<div class="calendar-addevent-row-container">
					<span class="calendar-addevent-row-left"><?= GetMessage('MBCAL_EDEV_FROM')?></span>
					<span class="calendar-addevent-row-right" id="mbcal-edit-from"></span>
					<div style="clear: both;"></div>
				</div>
				<div class="calendar-addevent-row-container">
					<span class="calendar-addevent-row-left"><?= GetMessage('MBCAL_EDEV_TO')?></span>
					<span class="calendar-addevent-row-right" id="mbcal-edit-to"></span>
					<div style="clear: both;"></div>
				</div>
				<div class="calendar-addevent-row-container" id="mbcal-edit-repeat-cont">
					<span class="calendar-addevent-row-left"><?= GetMessage('MBCAL_REPEAT_TITLE')?></span>
					<span class="calendar-addevent-row-right" id="mbcal-edit-repeat"></span>
					<div style="clear: both;"></div>
				</div>
				<div class="calendar-addevent-arrow"></div>
			</div>
		</div>
	</div>

	<div class="calendar-event-main-block-aqua">
		<div class="calendar-event-main-block-aqua-container">
			<div class="calendar-addevent-row">
				<div class="calendar-addevent-row-container">
					<span class="calendar-addevent-row-left"><?= GetMessage('MBCAL_EDEV_REMIMDER')?></span>
					<span class="calendar-addevent-row-right">
						<select id="mbcal-edit-remind">
							<?
							$reminders = array(
								'0' => GetMessage('MBCAL_EDEV_REM_NO'),
								'5_min' => GetMessage('MBCAL_EDEV_REM_MIN', array('#N#' => 5)),
								'15_min' => GetMessage('MBCAL_EDEV_REM_MIN', array('#N#' => 15)),
								'30_min' => GetMessage('MBCAL_EDEV_REM_MIN', array('#N#' => 30)),
								'1_hour' => GetMessage('MBCAL_EDEV_REM_HOUR1'),
								'2_hour' => GetMessage('MBCAL_EDEV_REM_HOURS', array('#N#' => 2)),
								'1_day' => GetMessage('MBCAL_EDEV_REM_DAY'),
								'2_day' => GetMessage('MBCAL_EDEV_REM_DAYS', array('#N#' => 2))
							);
							foreach($reminders as $value => $title):?>
								<option value="<?= $value?>"><?= $title?></option>
							<?endforeach;?>
						</select>
					</span>
					<div style="clear: both;"></div>
				</div>
			</div>
		</div>
	</div>

	<textarea id="mbcal-edit-desc" class="calendar-addevent-textarea" placeholder="<?= GetMessage('MBCAL_EDEV_DESC')?>"></textarea>
</div>

<div class="calendar-addevent-textar-wrap">
	<input id="mbcal-edit-location" type="text" class="calendar-addevent-input" placeholder="<?= GetMessage('MBCAL_EDEV_LOCATION')?>"/>
</div>

<div class="calendar-event-main-block-aqua" id="mbcal-edit-attendees-cont-wrap">
	<div class="calendar-event-main-block-aqua-container">
		<div class="calendar-addevent-row">
			<div class="calendar-addevent-row-container">
				<div class="calendar-addevent-addmembers" id="mbcal-edit-att-title"><?= GetMessage('MBCAL_EDEV_ADD_ATTENDEES')?></div>
				<div style="clear: both;"></div>
				<div class="calendar-addevent-arrow"></div>
			</div>
			<div class="calendar-addevent-participant">
				<div class="calendar-addevent-participant-block" id="mbcal-edit-att-cont">
					<div class="calendar-addevent-participant-row calendar-addevent-participant-row-new" id="mbcal-edit-att-add-but">
						<?= GetMessage('MBCAL_EDEV_ADD_ATTENDEES')?>
						<div class="calendar-addevent-arrow"></div>
						<div class="calendar-addevent-participant-btn"><i></i></div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="calendar-event-main-block-aqua">
	<div class="calendar-event-main-block-aqua-container">
		<div class="calendar-addevent-row">
			<div class="calendar-addevent-row-container">
				<span class="calendar-addevent-row-left"><?= GetMessage('MBCAL_EDEV_PRIVATE')?></span>
					<span class="calendar-addevent-row-right">
						<select id="mbcal-edit-private">
							<option value="Y"><?= GetMessage('MBCAL_EDEV_YES')?></option>
							<option value="N"><?= GetMessage('MBCAL_EDEV_NO')?></option>
						</select>
					</span>
				<div style="clear: both;"></div>
			</div>
			<div class="calendar-addevent-text"  id="mbcal-edit-private-notice">
				<?= GetMessage('MBCAL_EDEV_PRIV_NOTICE')?>
			</div>
		</div>
	</div>
</div>

<div class="calendar-event-main-block-aqua">
	<div class="calendar-event-main-block-aqua-container">
		<div class="calendar-addevent-row">
			<div class="calendar-addevent-row-container">
				<span class="calendar-addevent-row-left"><?= GetMessage('MBCAL_EDEV_ACC')?></span>
					<span class="calendar-addevent-row-right">
						<select id="mbcal-edit-acc">
							<option value="absent"><?= GetMessage('MBCAL_EDEV_ACC_ABSENT')?></option>
							<option value="busy"><?= GetMessage('MBCAL_EDEV_ACC_BUSY')?></option>
							<option value="quest"><?= GetMessage('MBCAL_EDEV_ACC_QUEST')?></option>
							<option value="free"><?= GetMessage('MBCAL_EDEV_ACC_FREE')?></option>
						</select>
					</span>
				<div style="clear: both;"></div>
			</div>
		</div>
	</div>
</div>

<div class="calendar-event-main-block-aqua">
	<div class="calendar-event-main-block-aqua-container">
		<div class="calendar-addevent-row">
			<div class="calendar-addevent-row-container">
				<span class="calendar-addevent-row-left"><?= GetMessage('MBCAL_EDEV_IMP')?></span>
					<span class="calendar-addevent-row-right">
						<select id="mbcal-edit-imp">
							<option value="high"><?= GetMessage('MBCAL_EDEV_IMP_HIGH')?></option>
							<option value="normal"><?= GetMessage('MBCAL_EDEV_IMP_NORMAL')?></option>
							<option value="low"><?= GetMessage('MBCAL_EDEV_IMP_LOW')?></option>
						</select>
					</span>
				<div style="clear: both;"></div>
			</div>
		</div>
	</div>
</div>

<div class="calendar-event-main-block-aqua">
	<div class="calendar-event-main-block-aqua-container">
		<div class="calendar-addevent-row">
			<div class="calendar-addevent-row-container">
				<span class="calendar-addevent-row-left"><?= GetMessage('MBCAL_EDEV_SECTION')?></span>
					<span class="calendar-addevent-row-right">
						<select id="mbcal-edit-section">
							<?foreach($arResult['SECTIONS'] as $sect):?>
								<option value="<?= $sect["ID"]?>"><?= $sect["NAME"]?></option>
							<?endforeach;?>
						</select>
					</span>
				<div style="clear: both;"></div>
			</div>
		</div>
	</div>
</div>

<div class="calendar-event-button" id="mbcal-edit-del-but-cont">
	<a id="mbcal-edit-del-event-but" href="" class="calendar denied-button" style="float: left; width: 96%!important; margin-left: 2%;"><?= GetMessage('MBCAL_EDEV_REMOVE')?></a>
	<div style="clear: both;"></div><br>
</div>

<?endif;/*$arResult('GET_FROM_TO_MODE') == 'Y')*/?>