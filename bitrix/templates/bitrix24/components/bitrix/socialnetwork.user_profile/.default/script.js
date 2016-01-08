BX.namespace("BX.Socialnetwork.User");

BX.Socialnetwork.User.Profile = (function()
{
	var Profile = function(arParams)
	{
		this.ajaxPath = "";
		this.siteId = "";
		this.languageId = "";
		this.otpDays = {};
		this.showOtpPopup = false;
		this.otpRecoveryCodes = false;
		this.profileUrl = "";
		this.passwordsUrl = "";

		if (typeof arParams === "object")
		{
			this.ajaxPath = arParams.ajaxPath;
			this.siteId = arParams.siteId;
			this.languageId = arParams.languageId;
			this.otpDays = arParams.otpDays;
			this.showOtpPopup = arParams.showOtpPopup == "Y" ? true : false;
			this.otpRecoveryCodes = arParams.otpRecoveryCodes == "Y" ? true : false;
			this.profileUrl = arParams.profileUrl;
			this.passwordsUrl = arParams.passwordsUrl;
			this.codesUrl = arParams.codesUrl;
		}

		this.init();
	};

	Profile.prototype.init = function()
	{
		if (this.showOtpPopup)
		{
			var buttons = [];

			if (this.otpRecoveryCodes)
			{
				buttons.push(new BX.PopupWindowButton({
					text : BX.message('SONET_OTP_CODES'),
					className : "popup-window-button-accept",
					events : { click : BX.proxy(function()
					{
						location.href = this.codesUrl;
					}, this)}
				}));
			}
			buttons.push(new BX.PopupWindowButton({
					text : BX.message('SONET_OTP_SUCCESS_POPUP_PASSWORDS'),
					className : "popup-window-button-accept",
					events : { click : BX.proxy(function()
					{
						location.href = this.passwordsUrl;
					}, this)}
				}),
				new BX.PopupWindowButtonLink({
					text: BX.message('SONET_OTP_SUCCESS_POPUP_CLOSE'),
					className: "popup-window-button-link-cancel",
					events: { click :  BX.proxy(function()
					{
						location.href = this.profileUrl;
					}, this)}
				})
			);

			BX.PopupWindowManager.create("securityOtpSuccessPopup", null, {
				autoHide: false,
				offsetLeft: 0,
				offsetTop: 0,
				overlay : true,
				draggable: {restrict:true},
				closeByEsc: false,
				content: '<div style="width:450px;min-height:100px; padding:15px;font-size:14px;">' + BX.message('SONET_OTP_SUCCESS_POPUP_TEXT') + (this.otpRecoveryCodes ? BX.message("SONET_OTP_SUCCESS_POPUP_TEXT_RES_CODE") : '') + '<div style="background-color: #fdfaea;padding: 10px;border-color: #e5e0c4 #f1edd7 #f9f6e4;border-style: solid;border-width: 1px;border-radius: 2px;">' + BX.message('SONET_OTP_SUCCESS_POPUP_TEXT2') + '</div></div>',
				buttons: buttons
			}).show();
		}
	};

	Profile.prototype.confirm = function()
	{
		if (confirm(BX.message("USER_PROFILE_CONFIRM")))
			return true;
		else
			return false;
	};

	Profile.prototype.changeUserActivity = function(userId, userActive)
	{
		if (!this.confirm())
			return false;

		if (!parseInt(userId) || !userActive)
			return false;

		BX.ajax.post(
			this.ajaxPath,
			{
				user_id :userId,
				active : userActive,
				sessid: BX.bitrix_sessid(),
				site_id: this.siteId
			},
			function(result)
			{
				if (parseInt(result))
				{
					window.location.reload();
				}
				else
				{
					var DeleteErrorPopup = BX.PopupWindowManager.create('delete_error', this, {
						content: '<p>'+BX("SONET_ERROR_DELETE")+'</p>',
						offsetLeft:27,
						offsetTop:7,
						autoHide:true
					});

					DeleteErrorPopup.show();
				}
			}
		);
	}

	Profile.prototype.showExtranet2IntranetForm = function(userId)
	{
		window.Bitrix24Extranet2IntranetForm = BX.PopupWindowManager.create("BXExtranet2Intranet", null, {
			autoHide: false,
			zIndex: 0,
			offsetLeft: 0,
			offsetTop: 0,
			overlay : true,
			draggable: {restrict:true},
			closeByEsc: true,
			titleBar: {content: BX.create("span", {html: BX.message('BX24_TITLE')})},
			closeIcon: { right : "12px", top : "10px"},
			buttons: [
				new BX.PopupWindowButton({
					text : BX.message('BX24_BUTTON'),
					className : "popup-window-button-accept",
					events : { click : function()
					{
						var popup = this;
						var form = BX('EXTRANET2INTRANET_FORM');

						if(form)
							BX.ajax.submit(form, BX.delegate(function(result) {
								popup.popupWindow.setContent(result);


							}));
					}}
				}),

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
						'/bitrix/tools/b24_extranet2intranet.php',
						{
							lang: BX.message('LANGUAGE_ID'),
							site_id: BX.message('SITE_ID') || '',
							USER_ID: userId
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

		Bitrix24Extranet2IntranetForm.show();
	};

	Profile.prototype.reinvite = function(userId, isExtranet, bindObj)
	{
		if (!parseInt(userId))
			return false;

		bindObj = bindObj || null;

		var reinvite = "reinvite_user_id_" + (isExtranet == "Y" ? "extranet_" : "") + userId;

		BX.ajax.post(
			'/bitrix/tools/intranet_invite_dialog.php',
			{
				lang: this.languageId,
				site_id: this.siteId,
				reinvite: reinvite,
				sessid: BX.bitrix_sessid()
			},
			BX.delegate(function(result)
			{
				var InviteAccessPopup = BX.PopupWindowManager.create('invite_access', bindObj, {
					content: '<p>'+BX.message("SONET_REINVITE_ACCESS")+'</p>',
					offsetLeft:27,
					offsetTop:7,
					autoHide:true
				});

				InviteAccessPopup.show();
			}, this)
		);
	}


	Profile.prototype.deactivateUserOtp = function(userId, numDays)
	{
		if (!parseInt(userId))
			return false;

		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.ajaxPath,
			data:
			{
				userId: userId,
				sessid: BX.bitrix_sessid(),
				numDays: numDays,
				action: "deactivate"
			},
			onsuccess: function(json)
			{
				if (json.error)
				{

				}
				else
				{
					location.reload();
				}
			}
		});
	};

	Profile.prototype.deferUserOtp = function(userId, numDays)
	{
		if (!parseInt(userId))
			return false;

		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.ajaxPath,
			data:
			{
				userId: userId,
				sessid: BX.bitrix_sessid(),
				numDays: numDays,
				action: "defer"
			},
			onsuccess: function(json)
			{
				if (json.error)
				{

				}
				else
				{
					location.reload();
				}
			}
		});
	};

	Profile.prototype.activateUserOtp = function(userId)
	{
		if (!parseInt(userId))
			return false;

		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.ajaxPath,
			data:
			{
				userId: userId,
				sessid: BX.bitrix_sessid(),
				action: "activate"
			},
			onsuccess: function(json)
			{
				if (json.error)
				{

				}
				else
				{
					location.reload();
				}
			}
		});
	};

	Profile.prototype.showOtpDaysPopup = function(bind, userId, handler)
	{
		if (!parseInt(userId))
			return false;

		handler = (handler == "defer") ? "defer" : "deactivate";
		var self = this;

		var daysObj = [];
		for (var i in this.otpDays)
		{
			daysObj.push({
				text: this.otpDays[i],
				numDays: i,
				onclick: function(event, item)
				{
					this.popupWindow.close();

					if (handler == "deactivate")
						self.deactivateUserOtp(userId, item.numDays);
					else
						self.deferUserOtp(userId, item.numDays);
				}
			});
		}

		BX.PopupMenu.show('securityOtpDaysPopup', bind, daysObj,
			{   offsetTop:10,
				offsetLeft:0
			}
		);
	};

	return Profile;
})();