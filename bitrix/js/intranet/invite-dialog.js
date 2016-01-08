;(function(){

if (!!BX.InviteDialog)
{
	return;
}

BX.InviteDialog =
{
	bInit: false,
	popup: null,
	arParams: {},
	lastTab: 'invite',
	lastUserTypeSuffix: '',
	sonetGroupSelector: null
}

BX.InviteDialog.Init = function(arParams)
{
	if(arParams)
	{
		BX.InviteDialog.arParams = arParams;
	}

	if(BX.InviteDialog.bInit)
	{
		return;
	}

	BX.InviteDialog.bInit = true;
}

BX.InviteDialog.selectCallback = function(item, type, search)
{
	if(!BX.findChild(BX('invite-dialog-' + BX.InviteDialog.lastTab + BX.InviteDialog.lastUserTypeSuffix + '-sonetgroup-item-post'), { attr : { 'data-id' : item.id }}, false, false))
	{
		BX('invite-dialog-' + BX.InviteDialog.lastTab + BX.InviteDialog.lastUserTypeSuffix + '-sonetgroup-item-post').appendChild(
			BX.create("span", { 
				attrs : { 
					'data-id' : item.id 
				}, 
				props : { 
					className : "feed-add-post-destination feed-add-post-destination-sonetgroups" + (typeof window['arExtranetGroupID'] != 'undefined' && BX.util.in_array(item.entityId, window['arExtranetGroupID']) ? ' feed-add-post-destination-extranet' : '') 
				}, 
				children: [
					BX.create("input", { 
						attrs : { 
							'type' : 'hidden', 
							'name' : 'SONET_GROUPS[]', 
							'value' : item.id 
						}
					}),
					BX.create("input", { 
						attrs : { 
							'type' : 'hidden', 
							'name' : 'SONET_GROUPS_NAME[' + item.id + ']', 
							'value' : item.name 
						}
					}),
					BX.create("span", { 
						props : { 
							'className' : "feed-add-post-destination-text" 
						}, 
						html : item.name
					}),
					BX.create("span", { 
						props : { 
							'className' : "feed-add-post-del-but"
						}, 
						events : {
							'click' : function(e){
								BX.SocNetLogDestination.deleteItem(item.id, 'sonetgroups', BX.InviteDialog.sonetGroupSelector);
								BX.PreventDefault(e);
							}, 
							'mouseover' : function(){
								BX.addClass(this.parentNode, 'feed-add-post-destination-hover');
							}, 
							'mouseout' : function(){
								BX.removeClass(this.parentNode, 'feed-add-post-destination-hover');
							}
						}
					})
				]
			})
		);
	}

	BX('invite-dialog-' + BX.InviteDialog.lastTab + BX.InviteDialog.lastUserTypeSuffix + '-sonetgroup-input-post').value = '';
	BX.InviteDialog.setLinkName(BX.InviteDialog.sonetGroupSelector);
}

BX.InviteDialog.unSelectCallback = function(item, type, search)
{
	var elements = BX.findChildren(BX('invite-dialog-' + BX.InviteDialog.lastTab + BX.InviteDialog.lastUserTypeSuffix + '-sonetgroup-item-post'), {attribute: {'data-id': ''+item.id+''}}, true);
	if (elements != null)
	{
		for (var j = 0; j < elements.length; j++)
		{
			BX.remove(elements[j]);
		}
	}
	BX('invite-dialog-' + BX.InviteDialog.lastTab + BX.InviteDialog.lastUserTypeSuffix + '-sonetgroup-input-post').value = '';
	BX.InviteDialog.setLinkName(BX.InviteDialog.sonetGroupSelector);
}

BX.InviteDialog.openDialogCallback = function()
{
	BX.PopupWindow.setOptions({
		'popupZindex': 2100
	});
	BX.style(BX('invite-dialog-' + BX.InviteDialog.lastTab + BX.InviteDialog.lastUserTypeSuffix + '-sonetgroup-input-box-post'), 'display', 'inline-block');
	BX.style(BX('invite-dialog-' + BX.InviteDialog.lastTab + BX.InviteDialog.lastUserTypeSuffix + '-sonetgroup-tag-post'), 'display', 'none');
	BX.focus(BX('invite-dialog-' + BX.InviteDialog.lastTab + BX.InviteDialog.lastUserTypeSuffix + '-sonetgroup-input-post'));
}

BX.InviteDialog.closeDialogCallback = function()
{
	if (
		!BX.SocNetLogDestination.isOpenSearch() 
		&& BX('invite-dialog-' + BX.InviteDialog.lastTab + BX.InviteDialog.lastUserTypeSuffix + '-sonetgroup-input-post').value.length <= 0
	)
	{
		BX.style(BX('invite-dialog-' + BX.InviteDialog.lastTab + BX.InviteDialog.lastUserTypeSuffix + '-sonetgroup-input-box-post'), 'display', 'none');
		BX.style(BX('invite-dialog-' + BX.InviteDialog.lastTab + BX.InviteDialog.lastUserTypeSuffix + '-sonetgroup-tag-post'), 'display', 'inline-block');
		BX.InviteDialog.disableBackspace();
	}
}

BX.InviteDialog.searchBefore = function(event)
{
	if (
		event.keyCode == 8 
		&& BX('invite-dialog-' + BX.InviteDialog.lastTab + BX.InviteDialog.lastUserTypeSuffix + '-sonetgroup-input-post').value.length <= 0
	)
	{
		BX.SocNetLogDestination.sendEvent = false;
		BX.SocNetLogDestination.deleteLastItem(BX.InviteDialog.sonetGroupSelector);
	}

	return true;
}

BX.InviteDialog.search = function(event)
{
	if (
		event.keyCode == 16 
		|| event.keyCode == 17 
		|| event.keyCode == 18 
		|| event.keyCode == 20 
		|| event.keyCode == 244 
		|| event.keyCode == 224 
		|| event.keyCode == 91
	)
	{
		return false;
	}

	if (
		BX.SocNetLogDestination.createSonetGroupTimeout !== undefined
		&& BX.SocNetLogDestination.createSonetGroupTimeout != null
	)
	{
		clearTimeout(BX.SocNetLogDestination.createSonetGroupTimeout);
	}

	if (event.keyCode == 13)
	{
		BX.SocNetLogDestination.selectFirstSearchItem(BX.InviteDialog.sonetGroupSelector);
		return true;
	}
	if (event.keyCode == 27)
	{
		BX('invite-dialog-' + BX.InviteDialog.lastTab + BX.InviteDialog.lastUserTypeSuffix + '-sonetgroup-input-post').value = '';
		BX.style(BX('invite-dialog-' + BX.InviteDialog.lastTab + BX.InviteDialog.lastUserTypeSuffix + '-sonetgroup-tag-post'), 'display', 'inline');
	}
	else
	{
		BX.SocNetLogDestination.search(
			BX('invite-dialog-' + BX.InviteDialog.lastTab + BX.InviteDialog.lastUserTypeSuffix + '-sonetgroup-input-post').value, 
			false, 
			BX.InviteDialog.sonetGroupSelector
		);
	}

	if (
		!BX.SocNetLogDestination.isOpenDialog() 
		&& BX('invite-dialog-' + BX.InviteDialog.lastTab + BX.InviteDialog.lastUserTypeSuffix + '-sonetgroup-input-post').value.length <= 0
	)
	{
		BX.SocNetLogDestination.openDialog(BX.InviteDialog.sonetGroupSelector);
	}
	else
	{
		if (
			BX.SocNetLogDestination.sendEvent 
			&& BX.SocNetLogDestination.isOpenDialog()
		)
		{
			BX.SocNetLogDestination.closeDialog();
		}
	}
	if (event.keyCode == 8)
	{
		BX.SocNetLogDestination.sendEvent = true;
	}
	return true;
}

BX.InviteDialog.disableBackspace = function(event)
{
	if (
		BX.SocNetLogDestination.backspaceDisable 
		|| BX.SocNetLogDestination.backspaceDisable != null
	)
	{
		BX.unbind(window, 'keydown', BX.SocNetLogDestination.backspaceDisable);
	}

	BX.bind(window, 'keydown', BX.SocNetLogDestination.backspaceDisable = function(event) {
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

BX.InviteDialog.setLinkName = function(name)
{
	if (BX.SocNetLogDestination.getSelectedCount(name) <= 0)
	{
		BX('invite-dialog-' + BX.InviteDialog.lastTab + BX.InviteDialog.lastUserTypeSuffix + '-sonetgroup-tag-post').innerHTML = BX.message("inviteDialogDestLink1");
	}
	else
	{
		BX('invite-dialog-' + BX.InviteDialog.lastTab + BX.InviteDialog.lastUserTypeSuffix + '-sonetgroup-tag-post').innerHTML = BX.message("inviteDialogDestLink2");
	}
}

BX.InviteDialog.showMessage = function(strMessageText, strWarningText)
{
	if (BX('invite-dialog-error-block'))
	{
		BX('invite-dialog-error-block').style.display = "none";
	}

	if (BX('intranet-dialog-tabs'))
	{
		if (
			typeof strWarningText != 'undefined'
			&& strWarningText
			&& strWarningText.length > 0
		)
		{
			BX('intranet-dialog-tabs').parentNode.appendChild(BX.create("div", { 
				props : {
					className : 'webform-round-corners webform-error-block'
				},
				attrs: {
					id : 'invite-dialog-error-block'
				},
				style : {
					'margin-top' : '10px'
				},
				children : [
					BX.create("div", { 
						props : {
							className : 'webform-corners-top'
						},
						children : [
							BX.create("div", { 
								props : {
									className : 'webform-left-corner'
								}
							}),
							BX.create("div", { 
								props : {
									className : 'webform-right-corner'
								}
							})
						]
					}),
					BX.create("div", { 
						props : {
							className : 'webform-content'
						},
						attrs : {
							id : 'invite-dialog-error-content'
						},
						html: strWarningText
					}),
					BX.create("div", { 
						props : {
							className : 'webform-corners-bottom'
						},
						children : [
							BX.create("div", { 
								props : {
									className : 'webform-left-corner'
								}
							}),
							BX.create("div", { 
								props : {
									className : 'webform-right-corner'
								}
							})
						]
					})
				]
			}));
		}

		BX('intranet-dialog-tabs').parentNode.appendChild(BX.create("div", { 
			props : {
				className : 'invite-dialog-inv-success-block'
			}, 
			html : strMessageText
		}));
		BX.cleanNode(BX('intranet-dialog-tabs'), true);
	}
}

BX.InviteDialog.showError = function(strErrorText)
{
	if (BX('invite-dialog-error-block'))
	{
		BX('invite-dialog-error-block').style.display = "block";
		if (BX('invite-dialog-error-content'))
		{
			BX('invite-dialog-error-content').innerHTML = strErrorText;
		}
	}
}

BX.InviteDialog.bindInviteDialogStructureLink = function(oBlock)
{
	if (
		oBlock === undefined
		|| oBlock == null
	)
	{
		return;
	}

	BX.bind(oBlock, "click", function(e)
	{
		if(!e) e = window.event;

		if (inviteDialogDepartmentPopup === null)
		{
			inviteDialogDepartmentPopup = new BX.PopupWindow("invite-dialog-department-popup", oBlock, {
				offsetTop : 1,
				autoHide : true,
				angle : {position: 'top', offset : 50},
				content : BX("INVITE_DEPARTMENT_selector_content"),
				zIndex : 1200,
				buttons : [ ]
			});
		}

		if (inviteDialogDepartmentPopup.popupContainer.style.display != "block")
		{
			inviteDialogDepartmentPopup.setBindElement(BX('invite-dialog-' + BX.InviteDialog.lastTab + '-structure-link'));
			inviteDialogDepartmentPopup.show();
		}

		BX.PopupMenu.destroy('invite-dialog-usertype-popup');

		if (BX.SocNetLogDestination.popupWindow != null)
		{
			BX.SocNetLogDestination.popupWindow.close();
		}

		if (BX.SocNetLogDestination.popupSearchWindow != null)
		{
			BX.SocNetLogDestination.popupSearchWindow.close();
		}

		if (BX.SocNetLogDestination.createSocNetGroupWindow != null)
		{
			BX.SocNetLogDestination.createSocNetGroupWindow.close();
		}

		return BX.PreventDefault(e);
	});
}

BX.InviteDialog.bindInviteDialogSonetGroupLink = function(oBlock)
{
	if (
		oBlock === undefined
		|| oBlock == null
	)
	{
		return;
	}

	BX.bind(oBlock, "click", function(e)
	{
		var sonetGroupBlock = BX('invite-dialog-' + BX.InviteDialog.lastTab + BX.InviteDialog.lastUserTypeSuffix + '-sonetgroup-container-post');
		if (sonetGroupBlock)
		{
			sonetGroupBlock.style.display = 'block';
		}

		if (BX('invite-dialog-' + BX.InviteDialog.lastTab + BX.InviteDialog.lastUserTypeSuffix + '-sonetgroup-container-post'))
		{
			var dialogName = BX('invite-dialog-' + BX.InviteDialog.lastTab + BX.InviteDialog.lastUserTypeSuffix + '-sonetgroup-container-post').getAttribute('data-selector-name');

			if (
				dialogName !== undefined 
				&& dialogName.length > 0
			)
			{
				BX.SocNetLogDestination.obElementBindMainPopup[dialogName].node = BX('invite-dialog-' + BX.InviteDialog.lastTab + BX.InviteDialog.lastUserTypeSuffix + '-sonetgroup-container-post');
				BX.SocNetLogDestination.obElementBindSearchPopup[dialogName].node = BX('invite-dialog-' + BX.InviteDialog.lastTab + BX.InviteDialog.lastUserTypeSuffix + '-sonetgroup-container-post');
				BX.SocNetLogDestination.openDialog(dialogName);

				BX.PopupMenu.destroy('invite-dialog-usertype-popup');
				if (inviteDialogDepartmentPopup != null)
				{
					inviteDialogDepartmentPopup.close();
				}

				BX.PreventDefault(e);	
			}
		}
	});
}

BX.InviteDialog.onInviteDialogUserTypeSelect = function(userType)
{
	if (userType != 'extranet')
	{
		userType = 'employee';
	}

	BX.InviteDialog.lastUserTypeSuffix = (userType == 'employee' ? '' : '-extranet');
	BX.InviteDialog.sonetGroupSelector = BX('invite-dialog-' + BX.InviteDialog.lastTab + BX.InviteDialog.lastUserTypeSuffix + '-sonetgroup-container-post').getAttribute('data-selector-name');

	BX('invite-dialog-' + BX.InviteDialog.lastTab + '-usertype-block-employee').style.display = (userType == 'employee' ? 'block' : 'none');
	if (BX('invite-dialog-' + BX.InviteDialog.lastTab + '-usertype-block-extranet'))
	{
		BX('invite-dialog-' + BX.InviteDialog.lastTab + '-usertype-block-extranet').style.display = (userType == 'employee' ? 'none' : 'block');
	}

	if (userType == 'extranet')
	{
		BX('invite-dialog-' + BX.InviteDialog.lastTab + '-extranet-sonetgroup-container-post').style.display = 'block';
		BX('invite-dialog-' + BX.InviteDialog.lastTab + '-sonetgroup-container-post').style.display = 'none';
		BX.SocNetLogDestination.obAllowAddSocNetGroup[BX('invite-dialog-' + BX.InviteDialog.lastTab + BX.InviteDialog.lastUserTypeSuffix + '-sonetgroup-container-post').getAttribute('data-selector-name')] = true;
	}
	else
	{
		BX('invite-dialog-' + BX.InviteDialog.lastTab + '-sonetgroup-container-post').style.display = 'block';
		BX('invite-dialog-' + BX.InviteDialog.lastTab + '-extranet-sonetgroup-container-post').style.display = 'none';
	}

	if (BX('intranet-dialog-tab-content-' + BX.InviteDialog.lastTab))
	{
		BX('intranet-dialog-tab-content-' + BX.InviteDialog.lastTab).setAttribute('data-user-type', userType);
	}

	BX.PopupMenu.destroy('invite-dialog-usertype-popup');

	if (
		BX.InviteDialog.lastTab == 'add'
		&& BX('invite-dialog-mailbox-container')
	)
	{
		BX('invite-dialog-mailbox-container').style.display = (userType == 'extranet' ? 'none' : 'block');
	}
}
			
BX.InviteDialog.bindInviteDialogUserTypeLink = function(oBlock, bExtranetInstalled)
{
	bExtranetInstalled = !!bExtranetInstalled;

	if (
		oBlock === undefined
		|| oBlock == null
	)
	{
		return;
	}

	BX.bind(oBlock, "click", function(e)
	{
		BX.PopupMenu.destroy('invite-dialog-usertype-popup');

		var arItems = [
			{
				text : BX.message('inviteDialogTitleEmployee'),
				id : 'invite-dialog-usertype-popup-employee-title',
				className : 'menu-popup-no-icon',
				onclick: function() { BX.InviteDialog.onInviteDialogUserTypeSelect('employee'); }
			}
		];

		if (bExtranetInstalled)
		{
			arItems.push({
				text : BX.message('inviteDialogTitleExtranet'),
				id : 'invite-dialog-usertype-popup-extranet-title',
				className : 'menu-popup-no-icon',
				onclick: function() { BX.InviteDialog.onInviteDialogUserTypeSelect('extranet'); }
			});
		}

		var arParams = {
			offsetLeft: -14,
			offsetTop: 4,
			zIndex: 1200,
			lightShadow: false,
			angle: {position: 'top', offset : 50},
			events : {
				onPopupShow : function(ob)
				{

				}
			}
		};
		BX.PopupMenu.show("invite-dialog-usertype-popup", oBlock, arItems, arParams);
	});
}

BX.InviteDialog.bindInviteDialogChangeTab = function(oBlock)
{
	if (
		oBlock === undefined
		|| oBlock == null
	)
	{
		return;
	}
	BX.bind(oBlock, "click", function(e)
	{
		if(!e) e = window.event;
		var action = oBlock.getAttribute('data-action');
		if (action.length > 0)
		{
			BX.InviteDialog.lastTab = action;

			for (var i = 0; i < arTabs.length; i++)
			{
				if (arTabs[i].id == 'intranet-dialog-tab-' + BX.InviteDialog.lastTab)
				{
					BX.addClass(arTabs[i], 'intranet-tab-selected');
				}
				else
				{
					BX.removeClass(arTabs[i], 'intranet-tab-selected');
				}
			}

			for (var i = 0; i < arTabsContent.length; i++)
			{
				if (arTabsContent[i].id == 'intranet-dialog-tab-content-' + BX.InviteDialog.lastTab)
				{
					BX.addClass(arTabsContent[i], 'intranet-tab-content-selected');
				}
				else
				{
					BX.removeClass(arTabsContent[i], 'intranet-tab-content-selected');
				}
			}

			BX.InviteDialog.sonetGroupSelector = BX('invite-dialog-' + action + BX.InviteDialog.lastUserTypeSuffix + '-sonetgroup-container-post').getAttribute('data-selector-name');

			if (BX.SocNetLogDestination.popupWindow != null)
			{
				BX.SocNetLogDestination.popupWindow.close();
			}

			if (BX.SocNetLogDestination.popupSearchWindow != null)
			{
				BX.SocNetLogDestination.popupSearchWindow.close();
			}
			
			if (BX.SocNetLogDestination.createSocNetGroupWindow != null)
			{
				BX.SocNetLogDestination.createSocNetGroupWindow.close();
			}

			if (inviteDialogDepartmentPopup != null)
			{
				inviteDialogDepartmentPopup.close();
			}

			BX.PopupMenu.destroy('invite-dialog-usertype-popup');

			var windowObj = (window.BX ? window: (window.top.BX ? window.top: null));
			if(windowObj)
			{
				windowObj.B24.Bitrix24InviteDialog.popup.setTitleBar({content: windowObj.BX.create("span", {
					html: windowObj.BX.message('BX24_INVITE_TITLE_' + (action == 'invite' ? 'INVITE' : 'ADD'))
				})});
			}
		}

		return BX.PreventDefault(e);
	});
}

BX.InviteDialog.getEmail1 = function()
{
	var res = "";
	if (BX("ADD_EMAIL"))
	{
		res = BX("ADD_EMAIL").value;
	}

	return res;
}

BX.InviteDialog.getEmail2 = function()
{
	var res = "";

	if (
		BX("ADD_MAILBOX_ACTION")
		&& BX("ADD_MAILBOX_ACTION").value == "connect"
		&& BX("ADD_MAILBOX_USER_connect")
	)
	{
		var email = BX("ADD_MAILBOX_USER_connect").options[BX("ADD_MAILBOX_USER_connect").selectedIndex].value;
		var serviceID = BX("ADD_MAILBOX_USER_connect").options[BX("ADD_MAILBOX_USER_connect").selectedIndex].getAttribute('data-service-id');
		if (
			typeof serviceID != 'undefined'
			&& parseInt(serviceID) > 0
			&& typeof arConnectMailServicesDomains[serviceID] != 'undefined'
		)
		{
			res = email + '@' + arConnectMailServicesDomains[serviceID];
		}
	}

	return res;
}

BX.InviteDialog.setEmail2 = function(strEmail1, strEmail2)
{
	if (strEmail2.length > 0)
	{
		if (strEmail1.length <= 0)
		{
			BX("ADD_SEND_PASSWORD").disabled = false;
			BX("ADD_SEND_PASSWORD_EMAIL").innerHTML = "<br>(" + strEmail2 + ")";
		}
	}
	else
	{
		if (strEmail1.length <= 0)
		{
			BX("ADD_SEND_PASSWORD_EMAIL").innerHTML = "";
			BX("ADD_SEND_PASSWORD").checked = false;
			BX("ADD_SEND_PASSWORD").disabled = true;
		}
		else
		{
			BX("ADD_SEND_PASSWORD").disabled = false;
			BX("ADD_SEND_PASSWORD_EMAIL").innerHTML = "<br>(" + strEmail1 + ")";
		}
	}
}

BX.InviteDialog.setEmail1 = function(strEmail1, strEmail2)
{
	if (strEmail1.length > 0)
	{
		BX("ADD_SEND_PASSWORD_EMAIL").innerHTML = "<br>(" + strEmail1 + ")";
		BX("ADD_SEND_PASSWORD").disabled = false;
	}
	else
	{
		if (strEmail2.length > 0)
		{
			BX("ADD_SEND_PASSWORD").disabled = false;
			BX("ADD_SEND_PASSWORD_EMAIL").innerHTML = "<br>(" + strEmail2 + ")";
		}
		else
		{
			BX("ADD_SEND_PASSWORD_EMAIL").innerHTML = "";
			BX("ADD_SEND_PASSWORD").checked = false;
			BX("ADD_SEND_PASSWORD").disabled = true;
		}
	}
}

BX.InviteDialog.bindSendPasswordEmail = function()
{
	if (
		BX("ADD_SEND_PASSWORD_EMAIL")
		&& BX("ADD_SEND_PASSWORD")
	)
	{
		if (BX("ADD_EMAIL"))
		{
			BX.bind(BX("ADD_EMAIL"), "keyup", function()
				{
					var strEmail1 = BX.InviteDialog.getEmail1();
					var strEmail2 = BX.InviteDialog.getEmail2();
					BX.InviteDialog.setEmail1(strEmail1, strEmail2);
				}
			);
		}

		if (BX("ADD_MAILBOX_USER_connect"))
		{
			BX.bind(BX("ADD_MAILBOX_USER_connect"), "change", function()
				{
					var strEmail1 = BX.InviteDialog.getEmail1();
					var strEmail2 = BX.InviteDialog.getEmail2();
					BX.InviteDialog.setEmail2(strEmail1, strEmail2);
				}
			);
		}
	}
}

BX.InviteDialog.bindInviteDialogSubmit = function(oBlock)
{
	if (
		oBlock === undefined
		|| oBlock == null
	)
	{
		return;
	}

	BX.bind(oBlock, "click", function(e)
	{

		if(!e) e = window.event;

		if (BX.SocNetLogDestination.popupWindow != null)
		{
			BX.SocNetLogDestination.popupWindow.close();
		}

		if (BX.SocNetLogDestination.popupSearchWindow != null)
		{
			BX.SocNetLogDestination.popupSearchWindow.close();
		}

		if (BX.SocNetLogDestination.createSocNetGroupWindow != null)
		{
			BX.SocNetLogDestination.createSocNetGroupWindow.close();
		}

		BX.PopupMenu.destroy('invite-dialog-usertype-popup');

		var obRequestData = null;
		var arSonetGroupsInput = [];
		var arSonetGroupsCode = [];
		var arSonetGroupsName = [];

		switch (oBlock.id)
		{
			case "invite-dialog-invite-button-submit":

				if (typeof document.forms.INVITE_DIALOG_FORM["SONET_GROUPS[]"] != 'undefined')
				{
					if (typeof document.forms.INVITE_DIALOG_FORM["SONET_GROUPS[]"].value == 'undefined')
					{
						arSonetGroupsInput = document.forms.INVITE_DIALOG_FORM["SONET_GROUPS[]"];
					}
					else
					{
						arSonetGroupsInput = [
							document.forms.INVITE_DIALOG_FORM["SONET_GROUPS[]"]
						];
					}
				}

				for (var j = 0, len = arSonetGroupsInput.length; j < len; j++)
				{
					if (
						arSonetGroupsInput[j] !== undefined
						&& arSonetGroupsInput[j].value.length > 0
					)
					{
						if (typeof document.forms.INVITE_DIALOG_FORM["SONET_GROUPS_NAME[" + arSonetGroupsInput[j].value + "]"].value != 'undefined')
						{
							arSonetGroupsName[arSonetGroupsInput[j].value] = document.forms.INVITE_DIALOG_FORM["SONET_GROUPS_NAME[" + arSonetGroupsInput[j].value + "]"].value
							arSonetGroupsCode.push(arSonetGroupsInput[j].value);
						}
					}
				}

				obRequestData = {
					"action": "invite",
					"EMAIL": document.forms.INVITE_DIALOG_FORM["EMAIL"].value,
					'MESSAGE_TEXT': document.forms.INVITE_DIALOG_FORM["MESSAGE_TEXT"].value,
					"DEPARTMENT_ID": (BX('intranet-dialog-tab-content-invite').getAttribute('data-user-type') == 'extranet' ? 0 : document.forms.INVITE_DIALOG_FORM["DEPARTMENT_ID"].value),
					"sessid": BX.bitrix_sessid()
				};

				if (arSonetGroupsCode.length > 0)
				{
					obRequestData.SONET_GROUPS_CODE = arSonetGroupsCode;
				}

				if (arSonetGroupsName.length > 0)
				{
					obRequestData.SONET_GROUPS_NAME = arSonetGroupsName;
				}

				break;

			case "invite-dialog-add-button-submit":

				if (typeof document.forms.ADD_DIALOG_FORM["SONET_GROUPS[]"] != 'undefined')
				{
					if (typeof document.forms.ADD_DIALOG_FORM["SONET_GROUPS[]"].value == 'undefined')
					{
						arSonetGroupsInput = document.forms.ADD_DIALOG_FORM["SONET_GROUPS[]"];
					}
					else
					{
						arSonetGroupsInput = [
							document.forms.ADD_DIALOG_FORM["SONET_GROUPS[]"]
						];
					}
				}

				for (var j = 0, len = arSonetGroupsInput.length; j < len; j++)
				{
					if (
						arSonetGroupsInput[j] !== undefined
						&& arSonetGroupsInput[j].value.length > 0
					)
					{
						if (typeof document.forms.ADD_DIALOG_FORM["SONET_GROUPS_NAME[" + arSonetGroupsInput[j].value + "]"].value != 'undefined')
						{
							arSonetGroupsName[arSonetGroupsInput[j].value] = document.forms.ADD_DIALOG_FORM["SONET_GROUPS_NAME[" + arSonetGroupsInput[j].value + "]"].value
							arSonetGroupsCode.push(arSonetGroupsInput[j].value);
						}
					}
				}

				obRequestData = {
					"action": "add",
					"ADD_EMAIL": document.forms.ADD_DIALOG_FORM["ADD_EMAIL"].value,
					"ADD_NAME": document.forms.ADD_DIALOG_FORM["ADD_NAME"].value,
					"ADD_LAST_NAME": document.forms.ADD_DIALOG_FORM["ADD_LAST_NAME"].value,
					"ADD_POSITION": document.forms.ADD_DIALOG_FORM["ADD_POSITION"].value,
					"ADD_SEND_PASSWORD": (
						!!document.forms.ADD_DIALOG_FORM["ADD_SEND_PASSWORD"].checked 
							? document.forms.ADD_DIALOG_FORM["ADD_SEND_PASSWORD"].value 
							: "N"
					),
					"DEPARTMENT_ID": (BX('intranet-dialog-tab-content-add').getAttribute('data-user-type') == 'extranet' ? 0 : document.forms.ADD_DIALOG_FORM["DEPARTMENT_ID"].value),
					"sessid": BX.bitrix_sessid()
				};
				
				if (arSonetGroupsCode.length > 0)
				{
					obRequestData.SONET_GROUPS_CODE = arSonetGroupsCode;
				}

				if (arSonetGroupsName.length > 0)
				{
					obRequestData.SONET_GROUPS_NAME = arSonetGroupsName;
				}

				if (
					BX('ADD_MAILBOX_ACTION') 
					&& BX.util.in_array(BX('ADD_MAILBOX_ACTION').value, ['create', 'connect'])
				)
				{
					obRequestData.ADD_MAILBOX_ACTION = BX('ADD_MAILBOX_ACTION').value;

					if (BX('ADD_MAILBOX_ACTION').value == 'create')
					{
						obRequestData.ADD_MAILBOX_PASSWORD = BX('ADD_MAILBOX_PASSWORD').value;
						obRequestData.ADD_MAILBOX_PASSWORD_CONFIRM = BX('ADD_MAILBOX_PASSWORD_CONFIRM').value;
						obRequestData.ADD_MAILBOX_DOMAIN = BX('ADD_MAILBOX_DOMAIN_create').value;
						obRequestData.ADD_MAILBOX_USER = BX('ADD_MAILBOX_USER_create').value;

						if (typeof BX('ADD_MAILBOX_DOMAIN_create').options != 'undefined')
						{
							for (var i = 0; i < BX('ADD_MAILBOX_DOMAIN_create').options.length; i++)
							{
								if (BX('ADD_MAILBOX_DOMAIN_create').options[i].selected)
								{
									obRequestData.ADD_MAILBOX_SERVICE = BX('ADD_MAILBOX_DOMAIN_create').options[i].getAttribute('data-service-id');
									break;
								}
							}
						}
						else
						{
							obRequestData.ADD_MAILBOX_SERVICE = BX('ADD_MAILBOX_SERVICE_create').value;
						}
					}
					else if (BX('ADD_MAILBOX_ACTION').value == 'connect')
					{
						obRequestData.ADD_MAILBOX_USER = BX('ADD_MAILBOX_USER_connect').value;
						obRequestData.ADD_MAILBOX_DOMAIN = BX('ADD_MAILBOX_DOMAIN_connect').value;

						for (var i = 0; i < BX('ADD_MAILBOX_USER_connect').options.length; i++)
						{
							if (BX('ADD_MAILBOX_USER_connect').options[i].selected)
							{
								obRequestData.ADD_MAILBOX_SERVICE = BX('ADD_MAILBOX_USER_connect').options[i].getAttribute('data-service-id');
								break;
							}
						}
					}
				}

				break;
		}

		if (obRequestData)
		{
			BX.InviteDialog.disableSubmitButton(true, oBlock);

			BX.ajax({
				url: BX.message('inviteDialogSubmitUrl'),
				method: 'POST',
				dataType: 'json',
				data: obRequestData,
				onsuccess: function(obResponsedata) {
					BX.InviteDialog.disableSubmitButton(false, oBlock);
					if (
						obResponsedata["ERROR"] !== undefined
						&& obResponsedata["ERROR"].length > 0
					)
					{
						BX.InviteDialog.showError(obResponsedata["ERROR"]);
					}
					else if (
						obResponsedata["MESSAGE"] !== undefined
						&& obResponsedata["MESSAGE"].length > 0
					)
					{
						BX.InviteDialog.showMessage(obResponsedata["MESSAGE"], (obResponsedata["WARNING"] !== undefined && obResponsedata["WARNING"].length > 0 ? obResponsedata["WARNING"] : false));
					}
				},
				onfailure: function(obResponsedata) {
					BX.InviteDialog.disableSubmitButton(false, oBlock);
					BX.InviteDialog.showError(obResponsedata["ERROR"]);
				}
			});
		}

		return BX.PreventDefault(e);
	});
}

BX.InviteDialog.bindInviteDialogClose = function(oBlock)
{
	if (
		oBlock === undefined
		|| oBlock == null
	)
	{
		return;
	}

	BX.bind(oBlock, "click", function(e)
	{
		if(!e) e = window.event;
		BX.InviteDialog.onInviteDialogClose(true);
		return BX.PreventDefault(e);
	});
}

BX.InviteDialog.onInviteDialogClose = function(bCloseDialog)
{
	bCloseDialog = !!bCloseDialog;

	if (BX.SocNetLogDestination.popupWindow != null)
	{
		BX.SocNetLogDestination.popupWindow.close();
	}

	if (BX.SocNetLogDestination.popupSearchWindow != null)
	{
		BX.SocNetLogDestination.popupSearchWindow.close();
	}

	if (BX.SocNetLogDestination.createSocNetGroupWindow != null)
	{
		BX.SocNetLogDestination.createSocNetGroupWindow.close();
	}

	if (inviteDialogDepartmentPopup != null)
	{
		inviteDialogDepartmentPopup.destroy();
	}

	if (
		bCloseDialog 
		&& B24.Bitrix24InviteDialog.popup != null
	)
	{
		B24.Bitrix24InviteDialog.popup.close();
	}

	BX.InviteDialog.lastTab = 'invite';
}

BX.InviteDialog.onMailboxAction = function(action)
{
	if (action != 'connect')
	{
		action = 'create';
	}

	var oldAction = (action == 'connect' ? 'create' : 'connect');

	if (BX('invite-dialog-mailbox-container'))
	{
		BX.removeClass(BX('invite-dialog-mailbox-container'), 'invite-dialog-box-info-set-inactive');
	}

	if (BX('invite-dialog-mailbox-content-' + action))
	{
		BX('invite-dialog-mailbox-content-' + action).style.display = 'block';
	}

	if (BX('invite-dialog-mailbox-content-' + oldAction))
	{
		BX('invite-dialog-mailbox-content-' + oldAction).style.display = 'none';
	}

	if (BX('invite-dialog-mailbox-action-' + action))
	{
		BX.addClass(BX('invite-dialog-mailbox-action-' + action), 'invite-dialog-box-info-btn-active');
	}

	if (BX('invite-dialog-mailbox-action-' + oldAction))
	{
		BX.removeClass(BX('invite-dialog-mailbox-action-' + oldAction), 'invite-dialog-box-info-btn-active');
	}
	
	if (BX('ADD_MAILBOX_ACTION'))
	{
		BX('ADD_MAILBOX_ACTION').value = action;
	}

	var strEmail1 = BX.InviteDialog.getEmail1();
	var strEmail2 = (action == 'connect' ? BX.InviteDialog.getEmail2() : "");
	BX.InviteDialog.setEmail2(strEmail1, strEmail2);
}

BX.InviteDialog.onMailboxRollup = function()
{
	if (BX('invite-dialog-mailbox-container'))
	{
		BX.addClass(BX('invite-dialog-mailbox-container'), 'invite-dialog-box-info-set-inactive');
	}
	
	if (BX('invite-dialog-mailbox-action-create'))
	{
		BX.removeClass(BX('invite-dialog-mailbox-action-create'), 'invite-dialog-box-info-btn-active');
	}

	if (BX('invite-dialog-mailbox-action-connect'))
	{
		BX.removeClass(BX('invite-dialog-mailbox-action-connect'), 'invite-dialog-box-info-btn-active');
	}

	if (BX('ADD_MAILBOX_ACTION'))
	{
		BX('ADD_MAILBOX_ACTION').value = '';
	}

	var strEmail1 = BX.InviteDialog.getEmail1();
	var strEmail2 = "";
	BX.InviteDialog.setEmail2(strEmail1, strEmail2);
}

BX.InviteDialog.onMailboxServiceSelect = function(obSelect)
{
	if (obSelect)
	{
		var serviceID = obSelect.options[obSelect.selectedIndex].getAttribute('data-service-id');
		var domain = obSelect.options[obSelect.selectedIndex].getAttribute('data-domain');

		if (BX('ADD_MAILBOX_USER_connect'))
		{
			BX.cleanNode(BX('ADD_MAILBOX_USER_connect'));
		}

		if (
			domain.length > 0
			&& (typeof arMailServicesUsers[domain] != 'undefined')
		)
		{
			for (var i = 0; i < arMailServicesUsers[domain].length; i++)
			{
				BX('ADD_MAILBOX_USER_connect').appendChild(
					BX.create('OPTION', {
						'props': {
							'value': arMailServicesUsers[domain][i]
						},
						'attrs': {
							'data-service-id': serviceID
						},
						'text': arMailServicesUsers[domain][i]
					})
				);
			}
		}
	}
}

BX.InviteDialog.disableSubmitButton = function(bDisable, oButton)
{
	bDisable = !!bDisable;

	if (oButton)
 	{
		if (bDisable)
		{
			BX.addClass(oButton, "popup-window-button-disabled");
			oButton.style.cursor = 'auto';
		}
		else
		{
			BX.removeClass(oButton, "popup-window-button-disabled");
			oButton.style.cursor = 'pointer';
		}
	}
}

})();