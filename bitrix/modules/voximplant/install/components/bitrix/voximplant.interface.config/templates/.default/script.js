BX.namespace("BX.Voximplant.Numbers");

BX.Voximplant.Interface = {
	init: function()
	{
		BX.ready(function(){
			BX.bind(BX('interface_chat_option_btn'), 'click', function() {
				var node = BX.create('SPAN', {props : {className : "wait"}});
				BX.addClass(BX('interface_chat_option_btn'), "webform-small-button-wait webform-small-button-active");
				this.appendChild(node);
				BX.ajax({
					method: 'POST',
					url: (BX.message("VI_INTERFACE_URL")),
					data: {'VI_SET_CHAT_ACTION': 'Y', ACTION : BX('interface_chat_option_form').elements.chatAction.value, sessid : BX.bitrix_sessid()},
					dataType: 'json',
					onsuccess: function()
					{
						BX.removeClass(BX('interface_chat_option_btn'), "webform-small-button-wait webform-small-button-active");
						BX.remove(node);
					},
					onfailure: function()
					{
						BX.removeClass(BX('interface_chat_option_btn'), "webform-small-button-wait webform-small-button-active");
						BX.remove(node);
					}
				});
				return false;
			});
		});
	}
};