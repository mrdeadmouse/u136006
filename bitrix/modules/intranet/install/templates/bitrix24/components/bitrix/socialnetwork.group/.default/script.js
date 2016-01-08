;(function(){

function ToggleDescription()
{
	if (BX('bx_group_description'))
	{
		BX.toggleClass(BX('bx_group_description'), 'bx-group-description-hide-table');

		var val = 'Y';

		if (BX('bx_group_pagetitle_link_open') && BX('bx_group_pagetitle_link_closed'))
		{
			if (BX('bx_group_pagetitle_link_open').style.display == 'inline-block')
			{
				BX('bx_group_pagetitle_link_open').style.display = 'none';
				BX('bx_group_pagetitle_link_closed').style.display = 'inline-block';
				val = 'N';
			}
			else
			{
				BX('bx_group_pagetitle_link_closed').style.display = 'none';
				BX('bx_group_pagetitle_link_open').style.display = 'inline-block';
				val = 'Y';
			}
		}

		BX.userOptions.save('socialnetwork', 'sonet_group_description', 'state', val, false);
	}

	return false;
		
}

function isLeftClick(event)
{
	if (!event.which && event.button !== undefined)
	{
		if (event.button & 1)
			event.which = 1;
		else if (event.button & 4)
			event.which = 2;
		else if (event.button & 2)
			event.which = 3;
		else
			event.which = 0;
	}

	return event.which == 1 || (event.which == 0 && BX.browser.IsIE());
};

if (!!BX.SG)
{
	return;
}

BX.SG =
{
	bInit: false,
	popup: null,
	params: {}
}

BX.SG.Init = function(obParams)
{
	if (BX.SG.bInit)
	{
		return;
	}

	if (obParams)
	{
		BX.SG.params = obParams;

		if (obParams.pathToInvite)
		{
			BX.SG.pathToInvite = obParams.pathToInvite + (obParams.pathToInvite.indexOf("?") == -1 ? "?" : "&") + "IFRAME=Y&SONET=Y";
		}
	}

	BX.message(obParams['MESS']);

	BX.SG.bInit = true;

	BX.addCustomEvent('onSonetIframeCancelClick', function() {
		if (BX.SG.popup != null)
		{
			BX.SG.popup.destroy();
		}
	});

	BX.addCustomEvent('onSonetIframeSuccess', function() {
		if (BX.SG.popup != null)
		{
			BX.SG.popup.destroy();
		}
	});
}
BX.SG.ShowForm = function(action, event)
{
	if (BX.SG.popup)
	{
		BX.SG.popup.destroy();
	}

	var actionURL = null;
	var popupTitle = '';

	switch (action)
	{
		case 'invite':
			actionURL = BX.SG.pathToInvite;
			popupTitle = BX.message('SONET_SG_T_DO_INVITE');
			break;
		default:
			actionURL = null;
	}

	if (actionURL)
	{
		BX.SG.popup = BX.PopupWindowManager.create("BXSG", null, {
			autoHide: false,
			zIndex: 0,
			offsetLeft: 0,
			offsetTop: 0,
			overlay:true,
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
					this.setContent('<div style="width:450px;height:230px">' + BX.message('SONET_SG_LOADING') + '</div>');

					BX.ajax.post(
						actionURL,
						{
							lang: BX.message('LANGUAGE_ID'),
							site_id: BX.message('SITE_ID') || '',
							arParams: BX.SG.params
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
					BX.SG.onPopupClose();
				}
			}
		});
			
		BX.SG.popup.params.zIndex = (BX.WindowManager? BX.WindowManager.GetZIndex() : 0);
		BX.SG.popup.show();
	}

	BX.PreventDefault(event);
};

BX.SG.onPopupClose = function()
{
}

})();