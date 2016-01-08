BX.namespace("BX.Voximplant.Blacklist");

BX.Voximplant.Blacklist = {
	deleteNumber : function(url, number, phoneNode)
	{
		if (confirm(BX.message("BLACKLIST_DELETE_CONFIRM")))
		{
			BX.ajax({
				method: 'POST',
				dataType: 'json',
				url: url,
				data: {
					sessid: BX.bitrix_sessid(),
					action: 'delete_number',
					number: number
				},
				onsuccess: function(json)
				{
					if (json.success == 'Y')
					{
						BX.remove(phoneNode);
					}
					else if (json.error)
					{
						BX.PopupWindowManager.create("voximplantBlacklist", phoneNode, {
							autoHide: false,
							offsetLeft: 0,
							offsetTop: 0,
							overlay : false,
							draggable: {restrict:true},
							closeByEsc: true,
							closeIcon: true,
							content: "<div style='padding: 7px'>" + BX.message("BLACKLIST_DELETE_ERROR") + "</div>",
							events: {
								onPopupClose: function()
								{
									this.destroy();
								}
							}
						}).show();
					}
				}
			});
		}
	}
}