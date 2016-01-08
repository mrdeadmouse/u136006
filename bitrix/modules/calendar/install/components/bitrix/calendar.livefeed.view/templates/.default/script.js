;(function(window){
	window.ViewEventManager = function(config)
	{
		this.id = config.id;
		this.config = config;
		this.userId = BX.message('sonetLCurrentUserID');

		this.viewEventUrl = this.config.viewEventUrlTemplate;
		this.viewEventUrl = this.viewEventUrl.replace(/#user_id#/ig, this.userId);
		this.viewEventUrl = this.viewEventUrl.replace(/#event_id#/ig, this.config.eventId);

		BX.ready(BX.proxy(this.Init, this));
	};

	window.ViewEventManager.prototype = {
		Init: function()
		{
			this.pViewIconLink = BX('feed-event-view-icon-link-' + this.id);
			this.pViewLink = BX('feed-event-view-link-' + this.id);
			this.pViewLink.href = this.pViewIconLink.href = this.viewEventUrl;

			this.pFrom = BX('feed-event-view-from-' + this.id);
			this.pFrom.innerHTML = this.GetFromHtml(this.config.EVENT.DT_FROM_TS, this.config.EVENT.DT_SKIP_TIME, this.config.EVENT.DT_LENGTH);

			this.InitPopups();

			// Invite controls
			var status = null;
			if (this.config.EVENT.IS_MEETING && this.config.attendees[this.userId])
			{
				status = this.config.attendees[this.userId].STATUS;

				this.ShowUserStatus(status);

				BX.viewElementBind(
					'bx-feed-cal-view-files-' + this.id,
					{showTitle: true},
					function(node){
						return BX.type.isElementNode(node) && (node.getAttribute('data-bx-viewer') || node.getAttribute('data-bx-image'));
					}
				);
			}
			else
			{
				this.ShowUserStatus(false);
			}
		},

		InitPopups: function()
		{
			var _this = this;
			var rand = Math.round(Math.random() * 100000);

			this.pMoreAttLinkY = BX('feed-event-more-att-link-y-' + this.id);
			this.pMoreAttLinkN = BX('feed-event-more-att-link-n-' + this.id);
			this.pMoreAttPopupContY = BX('feed-event-more-attendees-y-' + this.id);
			this.pMoreAttPopupContN = BX('feed-event-more-attendees-n-' + this.id);

			if (this.pMoreAttLinkY && this.pMoreAttPopupContY)
			{
				this.pMoreAttLinkY.onclick = function()
				{
					if (!_this.popupNotifyMoreY)
					{
						_this.popupNotifyMoreY = new BX.PopupWindow('bx_event_attendees_window_y_' + _this.id + '_' + rand, _this.pMoreAttLinkY,
							{
								zIndex: 200,
								lightShadow : true,
								offsetTop: -2,
								offsetLeft: 3,
								autoHide: true,
								closeByEsc: true,
								bindOptions: {position: "top"},
								content : _this.pMoreAttPopupContY
							}
						);
						_this.popupNotifyMoreY.setAngle({});
					}
					_this.popupNotifyMoreY.show();
					_this.pMoreAttPopupContY.style.display = "block";
				}
			}

			if (this.pMoreAttLinkN && this.pMoreAttPopupContN)
			{
				this.pMoreAttLinkN.onclick = function()
				{
					if (!_this.popupNotifyMoreN)
					{
						_this.popupNotifyMoreN = new BX.PopupWindow('bx_event_attendees_window_n_' + _this.id + '_' + rand, _this.pMoreAttLinkN,
							{
								zIndex: 200,
								lightShadow : true,
								offsetTop: -2,
								offsetLeft: 3,
								autoHide: true,
								closeByEsc: true,
								bindOptions: {position: "top"},
								content : _this.pMoreAttPopupContN
							}
						);
						_this.popupNotifyMoreN.setAngle({});
					}
					_this.popupNotifyMoreN.show();
					_this.pMoreAttPopupContN.style.display = "block";
				}
			}
		},

		ShowAttendees: function(attendees, pRow, params)
		{
			pRow.style.display = attendees.length > 0 ? "" : "none";

			if (!pRow || attendees.length <= 0)
				return;

			var
				contentCell = pRow.cells[1],
				i,
				cnt = 0,
				att,
				avatarSize = this.config.AVATAR_SIZE,
				bShowAll = attendees.length <= this.config.ATTENDEES_SHOWN_COUNT_MAX,
				popupContent = '',
				attCellContent = '';

			for(i = 0; i < attendees.length; i++)
			{
				att = attendees[i];
				cnt++;
				if (!bShowAll && cnt > this.config.ATTENDEES_SHOWN_COUNT)
				{
					// Put to popup
					popupContent += '<a href="' + att.URL + '" target="_blank" class="bxcal-att-popup-img bxcal-att-popup-att-full">' +
						'<span class="bxcal-att-popup-avatar">' +
							(att.AVATAR_SRC ? '<img src="' + att.AVATAR_SRC + '" width="' + avatarSize + '" height="' + avatarSize + '" class="bxcal-att-popup-img-not-empty" />' : '') +
						'</span>' +
						'<span class="bxcal-att-popup-name">' + BX.util.htmlspecialchars(att.DISPLAY_NAME) + '</span>' +
					'</a>';
				}
				else // Display avatar
				{
					attCellContent += '<a title="' + BX.util.htmlspecialchars(att.DISPLAY_NAME) + '" href="' + att.URL + '" target="_blank" class="bxcal-att-popup-img">' +
						'<span class="bxcal-att-popup-avatar">' +
							(att.AVATAR_SRC ? '<img src="' + att.AVATAR_SRC + '" width="' + avatarSize + '" height="' + avatarSize + '" class="bxcal-att-popup-img-not-empty" />' : '') +
						'</span>' +
					'</a>';
				}
			}

			contentCell.innerHTML = attCellContent;

			if (!bShowAll && params.MORE_MESSAGE)
			{
				var prefix = params.prefix;
				contentCell.appendChild(BX.create("SPAN", {props: {id: "feed-event-more-att-link-" + prefix + "-" + this.id, className: "bxcal-more-attendees"}, text: params.MORE_MESSAGE}));
				contentCell.appendChild(BX.create("DIV", {props: {id: "feed-event-more-attendees-" + prefix + "-" + this.id, className: "bxcal-more-attendees-popup"}, style: {display: "none"}, html: popupContent}));
			}
		},

		ShowUserStatus: function(status)
		{
			var inviteCont = BX('feed-event-invite-controls-' + this.id);
			var _this = this;
			if (status)
			{
				var rand = Math.round(Math.random() * 100000);
				inviteCont.className = 'feed-cal-view-inv-controls' + ' feed-cal-view-inv-controls-' + status.toLowerCase();

				if (status == 'Y')
				{
					var linkY = BX('feed-event-stat-link-y-' + this.id);
					linkY.onclick = function()
					{
						if (!_this.popupAccepted)
						{
							_this.popupAccepted = new BX.PopupWindow('bx_event_change_win_y_' + _this.id + '_' + rand, linkY,
							{
								zIndex: 200,
								lightShadow : true,
								offsetTop: -5,
								offsetLeft: (linkY.offsetWidth || 100) + 10,
								autoHide: true,
								closeByEsc: true,
								bindOptions: {position: "top"},
								content : BX('feed-event-stat-link-popup-y-' + _this.id)
							});
							_this.popupAccepted.setAngle({});
						}
						_this.popupAccepted.show();
					};
					BX('feed-event-decline-2-' + this.id).onclick = BX.proxy(this.Decline, this);
				}
				else if (status == 'N')
				{
					var linkN = BX('feed-event-stat-link-n-' + this.id);
					linkN.onclick = function(){
						if(!_this.popupDeclined)
						{
							_this.popupDeclined = new BX.PopupWindow('bx_event_change_win_n_' + _this.id + '_' + rand, linkN,
							{
								zIndex: 200,
								lightShadow : true,
								offsetTop: -5,
								offsetLeft: (linkN.offsetWidth || 100) + 10,
								autoHide: true,
								closeByEsc: true,
								bindOptions: {position: "top"},
								content : BX('feed-event-stat-link-popup-n-' + _this.id)
							});
							_this.popupDeclined.setAngle({});
						}
						_this.popupDeclined.show();
					};
					BX('feed-event-accept-2-' + this.id).onclick = BX.proxy(this.Accept, this);
				}
				else
				{
					BX('feed-event-accept-' + this.id).onclick = BX.proxy(this.Accept, this);
					BX('feed-event-decline-' + this.id).onclick = BX.proxy(this.Decline, this);
				}
			}
			else
			{
				inviteCont.style.display = 'none';
			}
		},

		SetStatus: function(status)
		{
			var _this = this;

			if (this.popupDeclined)
				this.popupDeclined.close();

			if (this.popupAccepted)
				this.popupAccepted.close();

			BX.ajax.get(
				this.config.actionUrl,
				{
					event_feed_action: status,
					sessid: BX.bitrix_sessid(),
					event_id: this.config.eventId,
					ajax_params: this.config.AJAX_PARAMS
				},
				function(result)
				{
					setTimeout(function()
					{
						if (result.indexOf('#EVENT_FEED_RESULT_OK#') !== -1 && _this.config.EVENT.IS_MEETING)
						{
							_this.ShowUserStatus(status == 'accept' ? "Y" : "N");

							if (window.ViewEventManager.requestResult)
							{
								// Show or hide accepted row + show users
								_this.ShowAttendees(
									window.ViewEventManager.requestResult['ACCEPTED_ATTENDEES'],
									BX('feed-event-accepted-row-' + _this.id),
									window.ViewEventManager.requestResult['ACCEPTED_PARAMS']
								);

								// Show or hide declined row + show users
								_this.ShowAttendees(
									window.ViewEventManager.requestResult['DECLINED_ATTENDEES'],
									BX('feed-event-declined-row-' + _this.id),
									window.ViewEventManager.requestResult['DECLIINED_PARAMS']
								);
							}

							_this.InitPopups();
						}
					}, 150);
				}
			);
		},

		Accept: function()
		{
			return this.SetStatus('accept');
		},

		Decline: function()
		{
			return this.SetStatus('decline');
		},

		DeleteEvent: function()
		{
			if (!this.config.eventId || !confirm(this.config.EC_JS_DEL_EVENT_CONFIRM))
				return false;

			BX.ajax.get(
				this.config.actionUrl,
				{
					event_feed_action: 'delete_event',
					sessid: BX.bitrix_sessid(),
					event_id: this.config.eventId
				},
				function(result)
				{
					if (result.indexOf('#EVENT_FEED_RESULT_OK#') !== -1)
						BX.reload();
				}
			);
		},

		GetFromHtml: function(DT_FROM_TS, DT_SKIP_TIME, DT_LENGTH)
		{
			var
				from = BX.date.getBrowserTimestamp(DT_FROM_TS),
				fromDate = new Date(from),
				dateFormat = BX.date.convertBitrixFormat(BX.message('FORMAT_DATE')),
				timeFormat = BX.message('FORMAT_DATETIME'),
				timeFormat2 = BX.util.trim(timeFormat.replace(BX.message('FORMAT_DATE'), '')),
				html;

			if (timeFormat2 == dateFormat)
				timeFormat = "HH:MI";
			else
				timeFormat = timeFormat2.replace(/:SS/ig, '');
			timeFormat = BX.date.convertBitrixFormat(timeFormat);

			if (DT_SKIP_TIME == 'Y')
			{
				html = BX.date.format([
					["today", "today"],
					["tommorow", "tommorow"],
					["yesterday", "yesterday"],
					["" , dateFormat]
				], fromDate);
			}
			else
			{
				html = BX.date.format([
					["today", "today"],
					["tommorow", "tommorow"],
					["yesterday", "yesterday"],
					["" , dateFormat]
				], fromDate);

				html += ', ' + BX.date.format(timeFormat, fromDate);
			}

			return html;
		}
	};

//
//	window.__GetFromHtml = function(DT_FROM_TS, DT_SKIP_TIME)
//	{
//		var
//			from = BX.date.getBrowserTimestamp(DT_FROM_TS),
//			fromDate = new Date(from),
//			dayl = 86400,
//			dateFormat = BX.date.convertBitrixFormat(BX.message('FORMAT_DATE')),
//			timeFormat = BX.message('FORMAT_DATETIME'),
//			timeFormat2 = BX.util.trim(timeFormat.replace(dateFormat, '')),
//			html;
//
//		if (timeFormat2 == timeFormat2)
//			timeFormat = "HH:MI";
//		else
//			timeFormat = timeFormat2.replace(/:SS/ig, '');
//		timeFormat = BX.date.convertBitrixFormat(timeFormat);
//
//		if (DT_SKIP_TIME == 'Y')
//		{
//			html = BX.date.format([
//				["today", "today"],
//				["tommorow", "tommorow"],
//				["yesterday", "yesterday"],
//				["" , dateFormat]
//			], fromDate);
//		}
//		else
//		{
//			html = BX.date.format([
//				["today", "today"],
//				["tommorow", "tommorow"],
//				["yesterday", "yesterday"],
//				["" , dateFormat]
//			], fromDate);
//
//			html += ', ' + BX.date.format(timeFormat, fromDate);
//		}
//
//		return html;
//	}
})(window);


