;(function(){

if (!!BX.SGCP)
{
	return;
}

BX.SGCP =
{
	bInit: {},
	popup: null,
	params: {},
	pathToCreate: {},
	pathToEdit: {},
	pathToInvite: {}
}

BX.SGCP.Init = function(obParams)
{
	if (obParams)
	{
		if (
			!obParams.NAME
			|| obParams.NAME.length <= 0
		)
		{
			return;
		}

		if (BX.SGCP.bInit[obParams.NAME])
		{
			return;
		}

		BX.SGCP.params[obParams.NAME] = obParams;

		BX.SGCP.pathToCreate[obParams.NAME] = (obParams.pathToCreate ? obParams.pathToCreate + (obParams.pathToCreate.indexOf("?") == -1 ? "?" : "&") + "IFRAME=Y&POPUP=Y&SONET=Y" : "");
		BX.SGCP.pathToEdit[obParams.NAME] = (obParams.pathToEdit ? obParams.pathToEdit + (obParams.pathToEdit.indexOf("?") == -1 ? "?" : "&") + "IFRAME=Y&POPUP=Y&SONET=Y" : "");
		BX.SGCP.pathToInvite[obParams.NAME] = (obParams.pathToInvite ? obParams.pathToInvite + (obParams.pathToInvite.indexOf("?") == -1 ? "?" : "&") + "IFRAME=Y&POPUP=Y&SONET=Y" : "");

		BX.message(obParams['MESS']);

		BX.SGCP.bInit[obParams.NAME] = true;

		BX.addCustomEvent('onSonetIframeCancelClick', function() {
			BX.SGCP.destroyPopup();
		});

		BX.addCustomEvent('onSonetIframeSuccess', function() {
			BX.SGCP.destroyPopup();
		});
	}

	return;
}

BX.SGCP.ShowForm = function(action, popupName, event)
{
	if (
		typeof popupName === 'undefined'
		|| popupName.length <= 0
	)
	{
		return BX.PreventDefault(event);
	}

	if (BX.SGCP.popup)
	{
		BX.SGCP.popup.destroy();
	}

	var actionURL = null;
	var popupTitle = '';

	switch (action)
	{
		case 'create':
			actionURL = BX.SGCP.pathToCreate[popupName];
			popupTitle = BX.message('SONET_SGCP_T_DO_CREATE_' + popupName);
			break;
		case 'edit':
			actionURL = BX.SGCP.pathToEdit[popupName];
			popupTitle = BX.message('SONET_SGCP_T_DO_EDIT_' + popupName);
			break;
		case 'invite':
			actionURL = BX.SGCP.pathToInvite[popupName];
			popupTitle = BX.message('SONET_SGCP_T_DO_INVITE_' + popupName);
			break;
		default:
			actionURL = null;
	}

	if (
		actionURL 
		&& actionURL.length > 0
	)
	{
		BX.SGCP.popup = new BX.PopupWindow("BXSGCP", null, {
			autoHide: false,
			zIndex: 0,
			offsetLeft: 0,
			offsetTop: 0,
			overlay: true,
			lightShadow: true,
			draggable: {
				restrict:true
			},
			closeByEsc: true,
			titleBar: {
				content: BX.create("span", {
					html: popupTitle
				})
			},
			closeIcon: { 
				right : "12px", 
				top : "10px"
			},
			buttons: [],
			content: '<div style="width:450px;height:230px"></div>',
			events: {
				onAfterPopupShow: function()
				{
					this.setContent('<div style="width:450px;height:230px">' + BX.message('SONET_SGCP_LOADING_' + popupName) + '</div>');

					BX.ajax.post(
						actionURL,
						{
							lang: BX.message('LANGUAGE_ID'),
							site_id: BX.message('SITE_ID') || '',
							arParams: BX.SGCP.params[popupName]
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
					BX.SGCP.onPopupClose();
				}
			}
		});
			
		BX.SGCP.popup.params.zIndex = (BX.WindowManager? BX.WindowManager.GetZIndex() : 0);
		BX.SGCP.popup.show();
	}

	BX.PreventDefault(event);
};

BX.SGCP.onPopupClose = function()
{
	if (BX.SocNetLogDestination.popupWindow != null)
	{
		BX.SocNetLogDestination.popupWindow.close();
	}

	if (BX.SocNetLogDestination.popupSearchWindow != null)
	{
		BX.SocNetLogDestination.popupSearchWindow.close();
	}
}

BX.SGCP.destroyPopup = function()
{
	BX.SGCP.onPopupClose();

	if (BX.SGCP.popup != null)
	{
		BX.SGCP.popup.destroy();
	}
}

})();