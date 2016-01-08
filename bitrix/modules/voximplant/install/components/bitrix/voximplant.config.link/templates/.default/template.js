if (!BX.VoxImplant)
	BX.VoxImplant = function() {};

BX.VoxImplant.backPhone = function() {};

BX.VoxImplant.backPhone.init = function(params)
{
	BX.VoxImplant.backPhone.inputNumber = params.number;

	BX.VoxImplant.backPhone.placeholder = params.placeholder;
	BX.VoxImplant.backPhone.number = params.number;
	BX.VoxImplant.backPhone.verified = params.verified;
	BX.VoxImplant.backPhone.verifiedUntil = params.verifiedUntil;

	BX.VoxImplant.backPhone.phoneInput = null;
	BX.VoxImplant.backPhone.codeInput = null;
	BX.VoxImplant.backPhone.codeError = null;
	BX.VoxImplant.backPhone.phoneNotice = null;
	BX.VoxImplant.backPhone.blockAjax = false;
	BX.VoxImplant.backPhone.blockVerify = false;

	BX.VoxImplant.backPhone.drawState();

	BX.ready(function(){
		BX.bind(BX('vi_link_options'), 'click', function(e){
			if (BX('vi_link_options_div').style.display == 'none')
			{
				BX.removeClass(BX(this), 'webform-button-create');
				BX('vi_link_options_div').style.display = 'block';
			}
			else
			{
				BX.addClass(BX(this), 'webform-button-create');
				BX('vi_link_options_div').style.display = 'none';
			}
			BX.PreventDefault(e);
		});
	});
};

BX.VoxImplant.backPhone.drawState = function(params)
{
	var inputNode = null;
	var codeNode = null;
	var buttonNode = null;
	var noticeNode = null;

	params = typeof (params) == 'object'? params: {};
	params.state = params.state? parseInt(params.state): 1;

	if (params.state == 1)
	{
		inputNode = BX.create("div", {props : { className : "tel-new-num-form" }, children: [
			BX.create("span", { props : { className : "tel-balance-phone-icon" }}),
			BX.create("a", {
				props : { attrs: { href: '#put-phone'},
				className : "tel-balance-phone-url" },
				events:
				{
					click : function(e)
					{
						BX.VoxImplant.backPhone.drawState({
							state: BX.VoxImplant.backPhone.number.length <= 0? 2: 3
						});
						return BX.PreventDefault(e);
					}
				},
				html: BX.VoxImplant.backPhone.number.length <= 0? BX.message('TELEPHONY_PUT_PHONE'): BX.message('TELEPHONY_VERIFY_PHONE')
			}),
			BX.VoxImplant.backPhone.number.length <= 0? null: BX.create("span", { props : { className : "tel-num-change-text"}, html: ' '+BX.message('TELEPHONY_OR')}),
			BX.VoxImplant.backPhone.number.length <= 0? null: BX.create("a", {
				props : { attrs: { href: '#change-phone'},
				className : "tel-balance-phone-url" },
				events:
				{
					click : function(e)
					{
						BX.VoxImplant.backPhone.removePhone();
						return BX.PreventDefault(e);
					}
				},
				html: BX.message('TELEPHONY_PUT_PHONE_AGAING')
			})
		]})
	}
	else if (params.state == 2 || params.state == 3)
	{
		inputNode = BX.create("div", {attrs: {id: 'tel-new-num-form'}, props : { className : "tel-new-num-form "+(params.state == 3? 'tel-new-num-form-disable': '')  }, children: [
			BX.create("span", { props : { className : "tel-balance-phone-icon" }}),
			BX.create("a", {
				props : { attrs: { href: '#put-phone'},
				className : "webform-small-button"},
				events:
				{
					click : params.state == 3? null: function(e)
					{
						BX.VoxImplant.backPhone.connectPhone(BX.VoxImplant.backPhone.phoneInput.value);
						return BX.PreventDefault(e);
					}
				},
				children: [
					BX.create("span", { props : { className : "webform-small-button-left" }}),
					BX.create("span", { props : { className : "webform-small-button-text" }, html: BX.message('TELEPHONY_CONFIRM')}),
					BX.create("span", { props : { className : "webform-small-button-right" }})
				]
			}),
			BX.create("div", { props : { className : "tel-new-num-inp-wrap" }, children: [
				BX.VoxImplant.backPhone.phoneInput = BX.create("input", { props : { className : "tel-new-num-inp"}, attrs: { placeholder: BX.message('TELEPHONY_EXAMPLE'), type: 'text', value: BX.VoxImplant.backPhone.inputNumber, disabled: params.state == 3}})
			]})
		]});
		if (params.state == 2)
		{
			BX.VoxImplant.backPhone.phoneNotice = noticeNode = BX.create("div", { props : { className : "tel-new-num-notice" }, html: BX.message('TELEPHONY_VERIFY_CODE')+'<br>'+BX.message('TELEPHONY_VERIFY_CODE_4')+'<br><br>'+BX.message('TELEPHONY_VERIFY_CODE_3')});
		}
		else if (params.state == 3)
		{
			BX.VoxImplant.backPhone.verifyPhone();
			BX.VoxImplant.backPhone.phoneNotice = noticeNode = BX.create("div", { props : { className : "tel-new-num-notice" }, html: BX.message('TELEPHONY_VERIFY_CODE_2')+'<br>'+BX.message('TELEPHONY_VERIFY_CODE_4')+'<br><br>'+BX.message('TELEPHONY_VERIFY_CODE_3')});
			codeNode = BX.create("div", {props : { className : "tel-new-num-pass" }, children: [
				BX.create("span", { props : { className : "tel-new-num-pass-title" }, html: BX.message('TELEPHONY_PUT_CODE')}),
				BX.create("br"),
				BX.VoxImplant.backPhone.codeInput = BX.create("input", { props : { className : "tel-new-num-inp"}, attrs: { type: 'text' }}),
				BX.VoxImplant.backPhone.codeError = BX.create("span", { props : { className : "tel-new-num-pass-error" }, html: ''}),
				BX.create("a", {
					props : { attrs: { href: '#put-code'},
					className : "webform-small-button webform-small-button-accept" },
					events:
					{
						click : function(e)
						{
							BX.VoxImplant.backPhone.activatePhone(BX.VoxImplant.backPhone.codeInput.value);
							return BX.PreventDefault(e);
						}
					},
					children: [
						BX.create("span", { props : { className : "webform-small-button-left" }}),
						BX.create("span", { props : { className : "webform-small-button-text" }, html: BX.message('TELEPHONY_JOIN')}),
						BX.create("span", { props : { className : "webform-small-button-right" }})
					]
				}),
				BX.create("br"),
				BX.create("br"),
				BX.create("br"),
				BX.create("a", {
					attrs: { href: '#put-code', style: 'margin-left: 3px; margin-right: 7px;'},
					props : { className : "webform-small-button" },
					events:
					{
						click : function(e)
						{
							BX.VoxImplant.backPhone.verifyPhone();
							return BX.PreventDefault(e);
						}
					},
					children: [
						BX.create("span", { props : { className : "webform-small-button-left" }}),
						BX.create("span", { props : { className : "webform-small-button-text" }, html: BX.message('TELEPHONY_RECALL')}),
						BX.create("span", { props : { className : "webform-small-button-right" }})
					]
				}),
				BX.create("a", {
					props : { attrs: { href: '#put-code'},
					className : "webform-small-button" },
					events:
					{
						click : function(e)
						{
							BX.VoxImplant.backPhone.removePhone();
							return BX.PreventDefault(e);
						}
					},
					children: [
						BX.create("span", { props : { className : "webform-small-button-left" }}),
						BX.create("span", { props : { className : "webform-small-button-text" }, html: BX.message('TELEPHONY_PUT_PHONE_AGAING')}),
						BX.create("span", { props : { className : "webform-small-button-right" }})
					]
				})
			]});
			buttonNode = null;
		}
	}

	var nodes = [];
	if (BX.VoxImplant.backPhone.number.length <= 0 || !BX.VoxImplant.backPhone.verified)
	{
		var inputText = null;
		if (BX.VoxImplant.backPhone.number.length <= 0)
		{
			inputText = BX.create("div", { props : { className : "tel-balance-text" }, children: [
				BX.create("span", { props : { className : "tel-balance-text-bold"}, html: BX.message('TELEPHONY_EMPTY_PHONE') }),
				BX.create("span", { html: BX.message('TELEPHONY_EMPTY_PHONE_DESC')})
			]});
		}
		else
		{
			inputText = BX.create("div", { children: [
				BX.create("div", { props : { className : "tel-num-not-conf-text"}, html: BX.message('TELEPHONY_CONFIRM_PHONE') }),
				BX.create("div", { props : { className : "tel-num-not-conf-block tel-num-block"}, html: '+'+BX.VoxImplant.backPhone.number }),
				BX.create("div", { props : { className : "tel-balance-text" }, children: [
					BX.create("strong", { html: BX.message('TELEPHONY_EMPTY_PHONE_DESC')})
				]})
			]});
		}

		nodes = [
			BX.create("div", { props : { className : "tel-new-num-block" }, children : [
				inputText,
				inputNode,
				noticeNode,
				codeNode,
				buttonNode
			]})
		];
	}
	else
	{
		nodes = [
			BX.create("div", { props : { className : "tel-balance-text" }, children: [
				BX.create("strong", { props : { className : "tel-balance-text-bold"}, html: BX.message('TELEPHONY_PHONE') })
			]}),
			BX.create("div", { props : { className : "tel-num-block"}, html: '+'+BX.VoxImplant.backPhone.number }),
			BX.create("div", { props : { className : "tel-num-change-block" }, children: [
				BX.create("span", { props : { className : "tel-num-change-text"}, html: BX.message('TELEPHONY_JOIN_TEXT')+" "}),
				BX.create("a", {
					props : { attrs: { href: '#change-phone'},
					className : "tel-num-change-link" },
					events:
					{
						click : function(e)
						{
							if (confirm(BX.message('TELEPHONY_DELETE_CONFIRM')))
							{
								BX.VoxImplant.backPhone.removePhone();
							}
							return BX.PreventDefault(e);
						}
					},
					html: BX.message('TELEPHONY_REJOIN')
				})
			]}),
			BX.create("div", { props : { className : "tel-set-item-block" }, children: [
				BX.create("span", { props : { className : "tel-num-alert-text"}, html: BX.message('TELEPHONY_CONFIRM_DATE').replace('#DATE#', '<b>'+BX.VoxImplant.backPhone.verifiedUntil+'</b>') })
			]})
		];
	}

	BX.VoxImplant.backPhone.drawOnPlaceholder(nodes);
};

BX.VoxImplant.backPhone.connectPhone = function(number)
{
	if (BX.VoxImplant.backPhone.blockAjax)
		return false;

	BX.showWait();
	BX.VoxImplant.backPhone.blockAjax = true;

	if (BX('tel-new-num-form'))
	{
		BX.addClass(BX('tel-new-num-form'), 'tel-new-num-form-disable');
	}

	BX.ajax({
		url: '/bitrix/components/bitrix/voximplant.config.link/ajax.php?CONNECT',
		method: 'POST',
		dataType: 'json',
		timeout: 30,
		data: {'VI_CONNECT': 'Y', 'NUMBER': number, 'VI_AJAX_CALL' : 'Y', 'sessid': BX.bitrix_sessid()},
		onsuccess: BX.delegate(function(data)
		{
			BX.closeWait();
			BX.VoxImplant.backPhone.blockAjax = false;
			if (data.ERROR == '')
			{
				BX.VoxImplant.backPhone.inputNumber = number;
				if (data.VERIFIED)
				{
					BX.VoxImplant.backPhone.number = data.NUMBER;
					BX.VoxImplant.backPhone.verified = true;
					BX.VoxImplant.backPhone.verifiedUntil = data.VERIFIED_UNTIL;
					BX.VoxImplant.backPhone.drawState({state: 1});
				}
				else
				{
					BX.VoxImplant.backPhone.verified = false;
					BX.VoxImplant.backPhone.drawState({state: 3});
				}
			}
			else
			{
				BX.addClass(BX.VoxImplant.backPhone.phoneNotice, 'tel-new-num-notice-err');
				if (data.ERROR == 'MONEY_LOW')
				{
					BX.VoxImplant.backPhone.phoneNotice.innerHTML = BX.message('TELEPHONY_ERROR_MONEY_LOW');
					if (BX.VoxImplant.backPhone.phoneNotice.innerHTML == "")
						BX.VoxImplant.backPhone.phoneNotice.innerHTML = 'Your account is`t enough money to make a call.'; // TODO remove THIS in next version
				}
				else
				{
					BX.VoxImplant.backPhone.phoneNotice.innerHTML = BX.message('TELEPHONY_ERROR_PHONE');
				}
				BX.addClass(BX.VoxImplant.backPhone.phoneInput, 'tel-new-num-inp-err');
				BX.removeClass(BX('tel-new-num-form'), 'tel-new-num-form-disable');
			}
		}, this),
		onfailure: function(){
			BX.closeWait();
			BX.VoxImplant.backPhone.blockAjax = false;
		}

	});
};

BX.VoxImplant.backPhone.verifyPhone = function()
{
	if (BX.VoxImplant.backPhone.blockAjax)
		return false;

	if (BX.VoxImplant.backPhone.blockVerify)
	{
		alert(BX.message('TELEPHONY_VERIFY_ALERT'));
		return true;
	}
	setTimeout(function(){
		BX.VoxImplant.backPhone.blockVerify = false;
	}, 60000);
	BX.VoxImplant.backPhone.blockVerify = true;

	BX.showWait();
	BX.VoxImplant.backPhone.blockAjax = true;
	BX.ajax({
		url: '/bitrix/components/bitrix/voximplant.config.link/ajax.php?VERIFY',
		method: 'POST',
		dataType: 'json',
		timeout: 30,
		data: {'VI_VERIFY': 'Y', 'VI_AJAX_CALL' : 'Y', 'sessid': BX.bitrix_sessid()},
		onsuccess: function(data){
			BX.closeWait();
			BX.VoxImplant.backPhone.blockAjax = false;
			if (data.ERROR == '185' || data.ERROR == '183')
			{
				alert(BX.message('TELEPHONY_ERROR_BLOCK'));
				BX.VoxImplant.backPhone.removePhone();
			}
		},
		onfailure: function(){
			BX.closeWait();
			BX.VoxImplant.backPhone.blockVerify = false;
			BX.VoxImplant.backPhone.blockAjax = false;
		}
	});
};

BX.VoxImplant.backPhone.activatePhone = function(code)
{
	if (BX.VoxImplant.backPhone.blockAjax)
		return false;

	BX.showWait();
	BX.VoxImplant.backPhone.blockAjax = true;
	BX.ajax({
		url: '/bitrix/components/bitrix/voximplant.config.link/ajax.php?ACTIVATE',
		method: 'POST',
		dataType: 'json',
		timeout: 30,
		data: {'VI_ACTIVATE': 'Y', 'CODE': code, 'VI_AJAX_CALL' : 'Y', 'sessid': BX.bitrix_sessid()},
		onsuccess: BX.delegate(function(data)
		{
			BX.VoxImplant.backPhone.blockVerify = false;
			BX.closeWait();
			BX.VoxImplant.backPhone.blockAjax = false;
			if (data.ERROR == '')
			{
				BX.VoxImplant.backPhone.number = data.NUMBER;
				BX.VoxImplant.backPhone.verified = true;
				BX.VoxImplant.backPhone.verifiedUntil = data.VERIFIED_UNTIL;
				BX.VoxImplant.backPhone.drawState({state: 1});
			}
			else
			{
				BX.VoxImplant.backPhone.codeError.innerHTML = BX.message('TELEPHONY_WRONG_CODE');
				BX.addClass(BX.VoxImplant.backPhone.codeInput, 'tel-new-num-inp-err');
			}
		}, this),
		onfailure: function(){
			BX.closeWait();
			BX.VoxImplant.backPhone.blockVerify = false;
			BX.VoxImplant.backPhone.blockAjax = false;
		}
	});
};

BX.VoxImplant.backPhone.removePhone = function()
{
	if (BX.VoxImplant.backPhone.blockAjax)
		return false;

	BX.VoxImplant.backPhone.blockVerify = false;

	BX.showWait();
	BX.VoxImplant.backPhone.blockAjax = true;
	BX.ajax({
		url: '/bitrix/components/bitrix/voximplant.config.link/ajax.php?REMOVE',
		method: 'POST',
		dataType: 'json',
		timeout: 30,
		data: {'VI_REMOVE': 'Y', 'VI_AJAX_CALL' : 'Y', 'sessid': BX.bitrix_sessid()},
		onsuccess: BX.delegate(function(data)
		{
			BX.closeWait();
			BX.VoxImplant.backPhone.blockAjax = false;
			if (data.ERROR == '')
			{
				BX.VoxImplant.backPhone.number = '';
				BX.VoxImplant.backPhone.verified = false;
				BX.VoxImplant.backPhone.drawState({state: 1});
			}
			else
			{
				alert(BX.message('TELEPHONY_ERROR_REMOVE'));
			}
		}, this),
		onfailure: function(){
			BX.closeWait();
			BX.VoxImplant.backPhone.blockAjax = false;
		}
	});
};

BX.VoxImplant.backPhone.drawOnPlaceholder = function(children)
{
	BX.VoxImplant.backPhone.placeholder.innerHTML = '';
	BX.adjust(BX.VoxImplant.backPhone.placeholder, {children: children});
};