BX.namespace("BX.Voximplant.Numbers");

BX.Voximplant.Numbers = {
	init: function(numbers)
	{
		this.numbers = numbers ? numbers : {};
		BX.ready(function(){
			BX.bind(BX('search_btn'), 'click', function() {
				BX.submit(BX('search_form'));
				return false;
			});
			BX.bind(BX('clear_btn'), 'click', function() {
				BX('search_form').elements.FILTER.value = '';
				BX.submit(BX('search_form'));
				return false;
			});
			BX.bind(BX('option_btn'), 'click', function() {
				var node = BX.create('SPAN', {props : {className : "wait"}});
				BX.addClass(BX('option_btn'), "webform-small-button-wait webform-small-button-active");
				this.appendChild(node);
				BX.ajax({
					method: 'POST',
					url: (BX.message("VI_NUMBERS_URL") + 'option'),
					data: {sessid : BX.bitrix_sessid(), portalNumber : BX('option_form').elements.portalNumber.value},
					dataType: 'json',
					onsuccess: function()
					{
						BX.removeClass(BX('option_btn'), "webform-small-button-wait webform-small-button-active");
						BX.remove(node);
					},
					onfailure: function()
					{
						BX.removeClass(BX('option_btn'), "webform-small-button-wait webform-small-button-active");
						BX.remove(node);
					}
				});
				return false;
			});
		});
	},
	showPhoneBlock : function(show, id)
	{
		var obj = document.querySelector("[data-role='vi_phone_"+ id+ "']");
		if (obj && typeof obj == "object")
		{
			obj.style.display = (show == "Y") ? "block" : "none";
		}
	},
	getUserInfo : function(id)
	{
		BX.showWait(BX("vi_numbers_dialog_"+id));

		BX.ajax({
			method: 'POST',
			url: (BX.message("VI_NUMBERS_URL") + 'getInfo&USER_ID=' + id),
			data: {
				sessid: BX.bitrix_sessid()
			},
			dataType: 'json',
			onsuccess: function(json)
			{
				BX.closeWait();
				if (json.result == 'error')
				{
					var errorBlock = document.querySelector('[data-role="popup_error_'+id+'"]');
					if (errorBlock)
					{
						errorBlock.innerHTML = json.error;
						errorBlock.style.display = "block";
					}
				}
				else
				{
					var obj = document.querySelector("[data-role='phone_server_"+ id+ "']");
					if (typeof obj == "object")
					{
						obj.innerHTML = json.call_server;
					}

					obj = document.querySelector("[data-role='phone_login_"+ id+ "']");
					if (typeof obj == "object")
					{
						obj.innerHTML = json.phone_login;
					}
					obj = document.querySelector("[data-role='phone_password_"+ id+ "']");
					if (typeof obj == "object")
					{
						obj.innerHTML = json.phone_password;
					}
					if (BX("UF_VI_PHONE_PASSWORD_"+id))
						BX("UF_VI_PHONE_PASSWORD_"+id).value = json.phone_password;

					BX.Voximplant.Numbers.showPhoneBlock('Y', id);
				}
			}
		});
	},
	edit: function(id)
	{
		var self = this;
		var isPhoneEnable = BX("vi_phone_enable_"+id) && BX("vi_phone_enable_"+id).innerHTML == "Y" ? true : false;

		var select = [];
		for (var ii in this.numbers)
		{
			if (this.numbers.hasOwnProperty(ii))
			{
				select.push([
					'<option value="', ii ,'" ', (BX('backphone_' + id + '_value').innerHTML == ii ? 'selected' : ''),'>',
					this.numbers[ii],
					'</option>'
				].join(''));
			}
		}

		var tableChilds = [
			BX.create('tr', {
				children: [
					BX.create('td', {
						children: [
							BX.create('div', { attrs : {"data-role":"popup_error_"+id, className: "vi-numbers-error"}})
						],
						attrs: {colspan: "2",style: "padding: 0"}
					})
				]
			}),
			BX.create('tr', {
				children: [
					BX.create('td', {
						children: [
							BX.create('input', { attrs : {"type": "hidden", "name": "sessid", "value": BX.bitrix_sessid()}}),
							BX.create('input', { attrs : {"type": "hidden", "name": "USER_ID", "value": id}}),
							BX.create('span', {  attrs : {"style": "line-height: 20px; display: inline-block; padding: 4px 0px 8px;"}, html : BX('user_'+id).parentNode.innerHTML })
						],
						attrs: {colspan: "2"}
					})
				]
			}),
			BX.create('tr', {
				children: [
					BX.create('td', {
						children: [
							BX.create('label', { attrs : {"for":"innerphone_"+ id}, html: BX.message('VI_NUMBERS_GRID_CODE')+":"})
						]
					}),
					BX.create('td', {
						children: [
							BX.create('input', { attrs : {"name": "UF_PHONE_INNER", "id": "innerphone_"+ id, "value": BX('innerphone_'+id).innerHTML, className: "tel-set-inp", "style": "width:225px;"}})
						]
					})
				]
			}),
			BX.create('tr', {
				children: [
					BX.create('td', {
						children: [
							BX.create('label', { attrs : {"for":"s_backphone_"+ id}, html: BX.message('VI_NUMBERS_GRID_PHONE')+":"})
						]
					}),
					BX.create('td', {
						children: [
							BX.create('select', { attrs : {"name": "UF_VI_BACKPHONE", "id": "s_backphone_"+ id, "style": "width:225px;", className: "tel-set-inp"}, html: select.join('')})
						]
					})
				]
			})
		];

		var phoneTable = {};

		tableChilds.push(
			BX.create('tr', { children: [
					BX.create('td', { html : BX.message("VI_NUMBERS_PHONE_CONNECT")}),
					BX.create('td', { children: [
						BX.create('input', {
							attrs: {
								"type": "radio",
								"name": "UF_VI_PHONE",
								"id": "UF_VI_PHONE_ENABLE"+ id,
								"checked": isPhoneEnable ? 'checked ' : '',
								"value": "Y"
							},
							events: {
								"change" : function()
								{
									if (this.checked)
										self.getUserInfo(id);
								}
							}
						}),
						BX.create('label', {
							html : BX.message("VI_NUMBERS_PHONE_CONNECT_ON"),
							attrs: { "for": "UF_VI_PHONE_ENABLE" + id }
						}),
						BX.create('input', {
							attrs: {
								"type": "radio",
								"name": "UF_VI_PHONE",
								"id": "UF_VI_PHONE_DISABLE"+ id,
								"checked": !isPhoneEnable ? 'checked ' : '',
								"value": "N",
								"style": "margin-left: 10px;"
							},
							events: {
								"change" : function()
								{
									if (this.checked)
										BX.Voximplant.Numbers.showPhoneBlock('N', id);
								}
							}
						}),
						BX.create('label', {
							html : BX.message("VI_NUMBERS_PHONE_CONNECT_OFF"),
							attrs: { "for": "UF_VI_PHONE_DISABLE" + id }
						})
					]})

				]
			})
		);
		phoneTable = BX.create('table', {
			children:[
				BX.create('tr', {
					children: [
						BX.create('td', {
							html: BX.message("VI_NUMBERS_PHONE_CONNECT_INFO"),
							attrs: { "colspan": "2" }
						})
					]
				}),
				BX.create('tr', {
					children: [
						BX.create('td', {
							html: BX.message("VI_NUMBERS_PHONE_CONNECT_SERVER") + ":",
							attrs: {"style": "text-align: right; width: 65px;"}
						}),
						BX.create('td', {
							attrs: {"style": "font-weight: bold", "data-role": "phone_server_"+id}
						})
					]
				}),
				BX.create('tr', {
					children: [
						BX.create('td', {
							html: BX.message("VI_NUMBERS_PHONE_CONNECT_LOGIN") + ":",
							attrs: {"style": "text-align: right"}
						}),
						BX.create('td', {
							attrs: {"style": "font-weight: bold", "data-role": "phone_login_"+id}
						})
					]
				}),
				BX.create('tr', {
					children: [
						BX.create('td', {
							html: BX.message("VI_NUMBERS_PHONE_CONNECT_PASSWORD") + ":",
							attrs: {"style": "text-align: right"}
						}),
						BX.create('td', {children: [
								BX.create('span', {
									attrs: {
										"data-role": "phone_password_"+id,
										"className": "bx-vi-phone-password"
									}
								}),
								BX.create('input', {
									attrs: {
										"type": "text",
										"name": "UF_VI_PHONE_PASSWORD",
										"id": "UF_VI_PHONE_PASSWORD_"+id,
										"size": "28",
										"style": "display:none" ,
										"onchange": "this.previousSibling.innerHTML = this.value",
										"className": "bx-vi-phone-password-input"
									}
								}),
								BX.create('span', {
									attrs: {
										"className": "bx-vi-phone-icon bx-vi-phone-edit"
									},
									events: {
										"click": function()
										{
											this.previousSibling.previousSibling.style.display="none";
											this.previousSibling.style.display="inline-block";
											this.style.display="none";
											this.nextSibling.style.display="inline-block";
										}
									}
								}),
								BX.create('span', {
									attrs: {
										"className": "bx-vi-phone-icon",
										"style": "display: none; vertical-align: top;"
									},
									html: "&times;",
									events: {
										"click": function()
										{
											this.previousSibling.previousSibling.previousSibling.style.display="inline-block";
											this.previousSibling.previousSibling.style.display="none";
											this.style.display="none";
											this.previousSibling.style.display="inline-block";
										}
									}
								})
							],
							attrs: {"style": "font-weight: bold; white-space: nowrap; height: 21px;"}
						})
					]
				})
			],
			attrs: { className: "bx-vi-phone", "data-role":"vi_phone_"+id, "style": "display:none;"}
		});


		var tableObj = BX.create("form", {children:[
				BX.create('table',{
					children: tableChilds,
					attrs: {
						className: "bx-vi-numbers-table"
					}
				}),
				BX.create('div',{
					children: [phoneTable],
					attrs: {
						className: "bx-vi-numbers-table"
					}
				})
			],
			attrs:{	id: "vi_numbers_dialog_"+id}
		});

		BX.PopupWindowManager.create("VI_NUMBERS_POPUP_"+id, null, {
			autoHide: true,
			offsetLeft: 0,
			offsetTop: 0,
			overlay : true,
			draggable: {restrict:true},
			closeByEsc: true,
			titleBar: {content: BX.create("span", {html: BX.message("VI_NUMBERS_CREATE_TITLE")})},
			content: '<div style="width:460px; min-height:400px"></div>',
			events: {
				onAfterPopupShow: function()
				{
					this.setContent(tableObj);
					if (isPhoneEnable)
						self.getUserInfo(id);
				}
			},
			buttons: [
				new BX.PopupWindowButton({
					text : BX.message('VI_NUMBERS_SAVE'),
					className : "popup-window-button-accept",
					events : { click : function()
					{
						var errorBlock = document.querySelector('[data-role="popup_error_'+id+'"]');
						if (errorBlock)
						{
							errorBlock.innerHTML = "";
							errorBlock.style.display = "none";
						}

						var form = BX('vi_numbers_dialog_'+id);
						var popup = this;

						if(form)
						{
							var data_form = {};
							for(var i = 0; i< form.elements.length; i++)
							{
								if (form[i].name == "UF_VI_PHONE" && !form[i].checked)
									continue;
								data_form[form[i].name] = form[i].value;
							}

							BX.ajax({
								method: 'POST',
								url: (BX.message("VI_NUMBERS_URL") + 'edit&USER_ID=' + id),
								data: data_form,
								dataType: 'json',
								onsuccess: function(json)
								{
									if (json.result == 'error')
									{
										if (errorBlock)
										{
											errorBlock.innerHTML = json.error;
											errorBlock.style.display = "block";
										}
									}
									else
									{
										if (BX('innerphone_' + id))
											BX('innerphone_' + id).innerHTML = json.UF_PHONE_INNER;
										var res = (!!json.UF_VI_BACKPHONE && !!self.numbers[json.UF_VI_BACKPHONE] ? json.UF_VI_BACKPHONE : '');
										if (BX('backphone_' + id))
										{
											BX('backphone_' + id).innerHTML = self.numbers[res];
											BX('backphone_' + id + '_value').innerHTML = res;
										}
										if (BX('vi_phone_' + id))
										{
											BX('vi_phone_' + id).innerHTML = json.UF_VI_PHONE == "Y" ? BX.message("VI_NUMBERS_PHONE_DEVICE_ENABLE") : BX.message("VI_NUMBERS_PHONE_DEVICE_DISABLE");
											if (json.UF_VI_PHONE == "Y")
												BX.addClass(BX('vi_phone_' + id), "bx-vi-phone-enable");
											else
												BX.removeClass(BX('vi_phone_' + id), "bx-vi-phone-enable");
										}
										if(BX("vi_phone_enable_"+id))
											BX("vi_phone_enable_"+id).innerHTML = json.UF_VI_PHONE;

										popup.popupWindow.destroy();
									}
								},
								onfailure: function()
								{
									if (errorBlock)
									{
										errorBlock.innerHTML = BX.message('VI_NUMBERS_ERR_AJAX');
										errorBlock.style.display = "block";
									}
								}
							});
						}
					}}
				}),
				new BX.PopupWindowButtonLink({
					text: BX.message('VI_NUMBERS_CANCEL'),
					className: "popup-window-button-link-cancel",
					events: { click : function()
					{
						this.popupWindow.destroy();
					}}
				})
			]
		}).show();
	}
};