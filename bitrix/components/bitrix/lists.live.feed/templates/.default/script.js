BX.LiveFeedClass = (function ()
{
	var LiveFeedClass = function (parameters)
	{
		this.ajaxUrl = '/bitrix/components/bitrix/lists.live.feed/ajax.php';
		this.socnetGroupId = parameters.socnetGroupId;
		this.randomString = parameters.randomString;
		this.listData = parameters.listData;

		var _this = this;
		BX.addCustomEvent('onDisplayClaimLiveFeed', function(iblock) {
			_this.init(iblock);
		});

		if(this.listData)
		{
			var iblock = [
				this.listData.ID,
				this.listData.NAME,
				this.listData.DESCRIPTION,
				this.listData.PICTURE,
				this.listData.CODE
			];
			window.SBPETabs.changePostFormTab('lists', iblock);
		}
	};

	LiveFeedClass.prototype.init = function (iblock)
	{
		if(iblock instanceof Array)
		{
			var iblockId = iblock[0],
				iblockName = iblock[1],
				iblockDescription = iblock[2],
				iblockPicture = iblock[3],
				iblockCode = iblock[4];

			this.setPicture(iblockPicture);
			this.setTitle(iblockName);
			this.getList(iblockId, iblockDescription, iblockCode);
			this.isConstantsTuned(iblockId);
		}
	};

	LiveFeedClass.prototype.isConstantsTuned = function (iblockId)
	{
		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.addToLinkParam(this.ajaxUrl, 'action', 'isConstantsTuned'),
			data: {
				iblockId: iblockId,
				sessid: BX.bitrix_sessid()
			},
			onsuccess: BX.delegate(function (result)
			{
				if(result.status == 'success')
				{
					BX('bx-lists-template-id').value = result.templateId;
					if(result.admin === true)
					{
						this.setResponsible();
					}
					else if(result.admin === false)
					{
						this.notifyAdmin();
						BX('bx-lists-cjeck-notify-admin').value = 1;
					}
				}
				else
				{
					result.errors = result.errors || [{}];
					this.showModalWithStatusAction({
						status: 'error',
						message: result.errors.pop().message
					})
				}
			}, this)
		});
	};

	LiveFeedClass.prototype.setPicture = function (iblockPicture)
	{
		BX('bx-lists-table-td-title-img').innerHTML = iblockPicture;
	};

	LiveFeedClass.prototype.setTitle = function (iblockName)
	{
		BX('bx-lists-table-td-title').innerHTML = BX.util.htmlspecialchars(iblockName);
		BX('bx-lists-title-notify-admin-popup').value = BX.util.htmlspecialchars(iblockName);
	};

	LiveFeedClass.prototype.getList = function (iblockId, iblockDescription, iblockCode)
	{
		var lists = BX.findChildrenByClassName(BX('bx-lists-store-lists'), 'bx-lists-input-list');
		for (var i = 0; i < lists.length; i++)
		{
			if(lists[i].value == iblockId)
			{
				this.show(BX('bx-lists-div-list-'+lists[i].value));
			}
			else
			{
				this.hide(BX('bx-lists-div-list-'+lists[i].value));
			}
		}

		BX('bx-lists-selected-list').value = iblockId;

		if(BX('bx-lists-input-list-'+iblockId))
		{
			return;
		}

		BX.ajax({
			url: this.addToLinkParam(this.ajaxUrl, 'action', 'getList'),
			method: 'POST',
			dataType: 'html',
			data: {
				iblockId: iblockId,
				iblockDescription: iblockDescription,
				iblockCode: iblockCode,
				socnetGroupId: this.socnetGroupId,
				randomString: this.randomString,
				sessid: BX.bitrix_sessid()
			},
			onsuccess: BX.delegate(function (data)
			{
				BX('bx-lists-store-lists').appendChild(
					BX.create('input', {
						props: {
							id: 'bx-lists-input-list-'+iblockId,
							className: 'bx-lists-input-list'
						},
						attrs: {
							type: 'hidden',
							value: iblockId
						}
					})
				);
				BX('bx-lists-total-div-id').appendChild(
					BX.create('div', {
						props: {
							id: 'bx-lists-div-list-'+iblockId,
							className: 'bx-lists-div-list'
						},
						attrs: {
							style: 'display: block;'
						}
					})
				);
				BX.adjust(BX('bx-lists-div-list-'+iblockId), {
					html: data
				});
			}, this)
		});
		BX.unbindAll(BX('blog-submit-button-save'));
		BX('blog-submit-button-save').setAttribute('onclick','BX["LiveFeedClass_'+this.randomString+'"].submitForm();');
	};

	LiveFeedClass.prototype.removeElement = function (elem)
	{
		return elem.parentNode ? elem.parentNode.removeChild(elem) : elem;
	};

	LiveFeedClass.prototype.addToLinkParam = function (link, name, value)
	{
		if (!link.length) {
			return '?' + name + '=' + value;
		}
		link = BX.util.remove_url_param(link, name);
		if (link.indexOf('?') != -1) {
			return link + '&' + name + '=' + value;
		}
		return link + '?' + name + '=' + value;
	};

	LiveFeedClass.prototype.showModalWithStatusAction = function (response, action)
	{
		response = response || {};
		if (!response.message) {
			if (response.status == 'success') {
				response.message = BX.message('LISTS_JS_STATUS_ACTION_SUCCESS');
			}
			else {
				response.message = BX.message('LISTS_JS_STATUS_ACTION_ERROR') + '. ' + this.getFirstErrorFromResponse(response);
			}
		}
		var messageBox = BX.create('div', {
			props: {
				className: 'bx-lists-alert'
			},
			children: [
				BX.create('span', {
					props: {
						className: 'bx-lists-aligner'
					}
				}),
				BX.create('span', {
					props: {
						className: 'bx-lists-alert-text'
					},
					text: response.message
				}),
				BX.create('div', {
					props: {
						className: 'bx-lists-alert-footer'
					}
				})
			]
		});

		var currentPopup = BX.PopupWindowManager.getCurrentPopup();
		if(currentPopup)
		{
			currentPopup.destroy();
		}

		var idTimeout = setTimeout(function ()
		{
			var w = BX.PopupWindowManager.getCurrentPopup();
			if (!w || w.uniquePopupId != 'bx-lists-status-action') {
				return;
			}
			w.close();
			w.destroy();
		}, 3500);
		var popupConfirm = BX.PopupWindowManager.create('bx-lists-status-action', null, {
			content: messageBox,
			onPopupClose: function ()
			{
				this.destroy();
				clearTimeout(idTimeout);
			},
			autoHide: true,
			zIndex: 2000,
			className: 'bx-lists-alert-popup'
		});
		popupConfirm.show();

		BX('bx-lists-status-action').onmouseover = function (e)
		{
			clearTimeout(idTimeout);
		};

		BX('bx-lists-status-action').onmouseout = function (e)
		{
			idTimeout = setTimeout(function ()
			{
				var w = BX.PopupWindowManager.getCurrentPopup();
				if (!w || w.uniquePopupId != 'bx-lists-status-action') {
					return;
				}
				w.close();
				w.destroy();
			}, 3500);
		};
	};

	LiveFeedClass.prototype.addNewTableRow = function(tableID, col_count, regexp, rindex)
	{
		var tbl = document.getElementById(tableID);
		var cnt = tbl.rows.length;
		var oRow = tbl.insertRow(cnt);

		for(var i=0;i<col_count;i++)
		{
			var oCell = oRow.insertCell(i);
			var html = tbl.rows[cnt-1].cells[i].innerHTML;
			oCell.innerHTML = html.replace(regexp,
				function(html)
				{
					return html.replace('[n'+arguments[rindex]+']', '[n'+(1+parseInt(arguments[rindex]))+']');
				}
			);
		}
	};

	LiveFeedClass.prototype.getNameInputFile = function()
	{
		var wrappers = document.getElementsByClassName('bx-lists-input-file');
		for (var i = 0; i < wrappers.length; i++)
		{
			var inputs = wrappers[i].getElementsByTagName('input');
			for (var j = 0; j < inputs.length; j++)
			{
				inputs[j].onchange = getName;
			}
		}
	};

	LiveFeedClass.prototype.createAdditionalHtmlEditor = function(tableId, fieldId, formId)
	{
		var tbl = document.getElementById(tableId);
		var cnt = tbl.rows.length;
		var oRow = tbl.insertRow(cnt);
		var oCell = oRow.insertCell(0);
		var sHTML = tbl.rows[cnt - 1].cells[0].innerHTML;
		var p = 0;
		while (true)
		{
			var s = sHTML.indexOf('[n', p);
			if (s < 0)
				break;
			var e = sHTML.indexOf(']', s);
			if (e < 0)
				break;
			var n = parseInt(sHTML.substr(s + 2, e - s));
			sHTML = sHTML.substr(0, s) + '[n' + (++n) + ']' + sHTML.substr(e + 1);
			p = s + 1;
		}
		var p = 0;
		while (true)
		{
			var s = sHTML.indexOf('__n', p);
			if (s < 0)
				break;
			var e = sHTML.indexOf('_', s + 2);
			if (e < 0)
				break;
			var n = parseInt(sHTML.substr(s + 3, e - s));
			sHTML = sHTML.substr(0, s) + '__n' + (++n) + '_' + sHTML.substr(e + 1);
			p = e + 1;
		}
		oCell.innerHTML = sHTML;

		var idEditor = 'id_'+fieldId+'__n'+cnt+'_';
		var fieldIdName = fieldId+'[n'+cnt+'][VALUE]';
		window.BXHtmlEditor.Show(
		{
			'id':idEditor,
			'inputName':fieldIdName,
			'name' : fieldIdName,
			'content':'',
			'width':'100%',
			'height':'200',
			'allowPhp':false,
			'limitPhpAccess':false,
			'templates':[],
			'templateId':'',
			'templateParams':[],
			'componentFilter':'',
			'snippets':[],
			'placeholder':'Text here...',
			'actionUrl':'/bitrix/tools/html_editor_action.php',
			'cssIframePath':'/bitrix/js/fileman/html_editor/iframe-style.css?1412693817',
			'bodyClass':'',
			'bodyId':'',
			'spellcheck_path':'/bitrix/js/fileman/html_editor/html-spell.js?v=1412693817',
			'usePspell':'N',
			'useCustomSpell':'Y',
			'bbCode': false,
			'askBeforeUnloadPage':false,
			'settingsKey':'user_settings_1',
			'showComponents':true,
			'showSnippets':true,
			'view':'wysiwyg',
			'splitVertical':false,
			'splitRatio':'1',
			'taskbarShown':false,
			'taskbarWidth':'250',
			'lastSpecialchars':false,
			'cleanEmptySpans':true,
			'lazyLoad':false,
			'showTaskbars':false,
			'showNodeNavi':false,
			'controlsMap':[
				{'id':'Bold','compact':true,'sort':'80'},
				{'id':'Italic','compact':true,'sort':'90'},
				{'id':'Underline','compact':true,'sort':'100'},
				{'id':'Strikeout','compact':true,'sort':'110'},
				{'id':'RemoveFormat','compact':true,'sort':'120'},
				{'id':'Color','compact':true,'sort':'130'},
				{'id':'FontSelector','compact':false,'sort':'135'},
				{'id':'FontSize','compact':false,'sort':'140'},
				{'separator':true,'compact':false,'sort':'145'},
				{'id':'OrderedList','compact':true,'sort':'150'},
				{'id':'UnorderedList','compact':true,'sort':'160'},
				{'id':'AlignList','compact':false,'sort':'190'},
				{'separator':true,'compact':false,'sort':'200'},
				{'id':'InsertLink','compact':true,'sort':'210','wrap':'bx-htmleditor-'+formId},
				{'id':'InsertImage','compact':false,'sort':'220'},
				{'id':'InsertVideo','compact':true,'sort':'230','wrap':'bx-htmleditor-'+formId},
				{'id':'InsertTable','compact':false,'sort':'250'},
				{'id':'Code','compact':true,'sort':'260'},
				{'id':'Quote','compact':true,'sort':'270','wrap':'bx-htmleditor-'+formId},
				{'id':'Smile','compact':false,'sort':'280'},
				{'separator':true,'compact':false,'sort':'290'},
				{'id':'Fullscreen','compact':false,'sort':'310'},
				{'id':'BbCode','compact':true,'sort':'340'},
				{'id':'More','compact':true,'sort':'400'}],
			'autoResize':true,
			'autoResizeOffset':'40',
			'minBodyWidth':'350',
			'normalBodyWidth':'555'
		});
		var htmlEditor = BX.findChildrenByClassName(BX(tableId), 'bx-html-editor');
		for(var k in htmlEditor)
		{
			var editorId = htmlEditor[k].getAttribute('id');
			var frameArray = BX.findChildrenByClassName(BX(editorId), 'bx-editor-iframe');
			if(frameArray.length > 1)
			{
				for(var i = 0; i < frameArray.length - 1; i++)
				{
					frameArray[i].parentNode.removeChild(frameArray[i]);
				}
			}

		}
	};

	LiveFeedClass.prototype.createSettingsDropdown = function (e) {

		BX.PreventDefault(e);
		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.addToLinkParam(this.ajaxUrl, 'action', 'createSettingsDropdown'),
			data: {
				iblockId: BX('bx-lists-selected-list').value,
				randomString: this.randomString,
				sessid: BX.bitrix_sessid()
			},
			onsuccess: BX.delegate(function (result)
			{
				if(result.status == 'success')
				{
					var menu = BX.PopupMenu.getMenuById('settings-lists');
					if(menu && menu.popupWindow)
					{
						if(menu.popupWindow.isShown())
						{
							BX.PopupMenu.destroy('settings-lists');
							return;
						}
					}
					BX.PopupMenu.show('settings-lists',BX('bx-lists-settings-btn'),result.settingsDropdown,
					{
						autoHide : true,
						offsetTop: 0,
						offsetLeft: 0,
						angle: { offset: 15 },
						events:
						{
							onPopupClose : function(){}
						}
					});
				}
				else
				{
					result.errors = result.errors || [{}];
					this.showModalWithStatusAction({
						status: 'error',
						message: result.errors.pop().message
					})
				}
			}, this)
		});
	};

	LiveFeedClass.prototype.setDelegateResponsible = function ()
	{
		if(BX.PopupWindowManager.getCurrentPopup())
		{
			BX.PopupWindowManager.getCurrentPopup().close();
		}

		var hide = this.hide,
			addToLinkParam = this.addToLinkParam,
			showModalWithStatusAction = this.showModalWithStatusAction,
			ajaxUrl = this.ajaxUrl;

		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.addToLinkParam(this.ajaxUrl, 'action', 'checkDelegateResponsible'),
			data: {
				iblockId: BX('bx-lists-selected-list').value,
				sessid: BX.bitrix_sessid()
			},
			onsuccess: BX.delegate(function (result)
			{
				if(result.status == 'success')
				{
					this.show(BX('feed-add-lists-right'));
					this.modalWindow({
						modalId: 'bx-lists-popup',
						title: BX.message("LISTS_SELECT_STAFF_SET_RIGHT"),
						overlay: false,
						autoHide: true,
						contentStyle: {
							width: '600px',
							paddingTop: '10px',
							paddingBottom: '10px'
						},
						content: [BX('feed-add-lists-right')],
						events : {
							onPopupClose : function() {
								hide(BX('feed-add-lists-right'));
								BX('bx-lists-total-div-id').appendChild(BX('feed-add-lists-right'));
								this.destroy();
							},
							onAfterPopupShow : function(popup) {
								var title = BX.findChild(popup.contentContainer, {class: 'bx-lists-popup-title'}, true);
								if (title)
								{
									title.style.cursor = "move";
									BX.bind(title, "mousedown", BX.proxy(popup._startDrag, popup));
								}
								BX.PopupMenu.destroy('settings-lists');
							}
						},
						buttons: [
							BX.create('a', {
								text : BX.message("LISTS_SAVE_BUTTON_SET_RIGHT"),
								props: {
									className: 'webform-small-button webform-small-button-accept'
								},
								events : {
									click : BX.delegate(function (e) {
										var selectSpan = BX.findChildrenByClassName(BX('feed-add-post-lists-item'), 'feed-add-post-lists'),
											selectUsers = [];
										for(var i = 0; i < selectSpan.length; i++)
										{
											selectUsers.push(selectSpan[i].getAttribute('data-id'));
										}
										BX.ajax({
											method: 'POST',
											dataType: 'json',
											url: addToLinkParam(ajaxUrl, 'action', 'setDelegateResponsible'),
											data: {
												iblockId: BX('bx-lists-selected-list').value,
												selectUsers: selectUsers,
												sessid: BX.bitrix_sessid()
											},
											onsuccess: function (result) {
												if(result.status == 'success')
												{
													BX.PopupWindowManager.getCurrentPopup().close();
													showModalWithStatusAction({
														status: 'success',
														message: result.message
													})
												}
												else
												{
													BX.PopupWindowManager.getCurrentPopup().close();
													result.errors = result.errors || [{}];
													showModalWithStatusAction({
														status: 'error',
														message: result.errors.pop().message
													})
												}
											}
										});
									}, this)
								}
							}),
							BX.create('a', {
								text : BX.message("LISTS_CANCEL_BUTTON_SET_RIGHT"),
								props: {
									className: 'webform-small-button webform-button-cancel'
								},
								events : {
									click : BX.delegate(function (e) {
										BX.PopupWindowManager.getCurrentPopup().close();
									}, this)
								}
							})
						]
					});
					for(var k in result.listUser)
					{
						var selected = BX.findChildrenByClassName(BX('feed-add-post-lists-item'), 'feed-add-post-lists');
						for(var i in selected)
						{
							if(result.listUser[k].id == selected[i].getAttribute('data-id'))
							{
								delete result.listUser[k];
							}
						}
						BXfpListsSelectCallback(result.listUser[k]);
					}
				}
				else
				{
					result.errors = result.errors || [{}];
					this.showModalWithStatusAction({
						status: 'error',
						message: result.errors.pop().message
					})
				}
			}, this)
		});
	};

	LiveFeedClass.prototype.jumpSettingProcess = function ()
	{
		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.addToLinkParam(this.ajaxUrl, 'action', 'checkPermissions'),
			data: {
				iblockId: BX('bx-lists-selected-list').value,
				sessid: BX.bitrix_sessid()
			},
			onsuccess: BX.delegate(function (result)
			{
				if(result.status == 'success')
				{
					document.location.href = BX('bx-lists-lists-page').value+BX('bx-lists-selected-list').value+'/edit/';
				}
				else
				{
					result.errors = result.errors || [{}];
					this.showModalWithStatusAction({
						status: 'error',
						message: result.errors.pop().message
					})
				}
			}, this)
		});
	};

	LiveFeedClass.prototype.jumpProcessDesigner = function ()
	{
		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.addToLinkParam(this.ajaxUrl, 'action', 'getBizprocTemplateId'),
			data: {
				iblockId: BX('bx-lists-selected-list').value,
				sessid: BX.bitrix_sessid()
			},
			onsuccess: BX.delegate(function (result)
			{
				if(result.status == 'success')
				{
					document.location.href = BX('bx-lists-lists-page').value+BX('bx-lists-selected-list').value+'/bp_edit/'+result.templateId+'/';
				}
				else
				{
					result.errors = result.errors || [{}];
					this.showModalWithStatusAction({
						status: 'error',
						message: result.errors.pop().message
					})
				}
			}, this)
		});
	};

	LiveFeedClass.prototype.notify = function (userId)
	{
		BX('bx-lists-notify-button-'+userId).setAttribute('onclick','');
		var siteDir = '/', siteId = null;
		if(BX('bx-lists-select-site-dir'))
		{
			siteDir = BX('bx-lists-select-site-dir').value;
		}
		if(BX('bx-lists-select-site-id'))
		{
			siteId = BX('bx-lists-select-site-id').value;
		}
		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.addToLinkParam(this.ajaxUrl, 'action', 'notifyAdmin'),
			data: {
				iblockId: BX('bx-lists-selected-list').value,
				iblockName: BX('bx-lists-title-notify-admin-popup').value,
				userId: userId,
				siteDir: siteDir,
				siteId: siteId,
				sessid: BX.bitrix_sessid()
			},
			onsuccess: BX.delegate(function (result)
			{
				if(result.status == 'success')
				{
					this.removeElement(BX('bx-lists-notify-button-'+userId));
					BX('bx-lists-notify-success-'+userId).innerHTML = result.message;
				}
				else
				{
					BX('bx-lists-notify-button-'+userId).setAttribute('onclick','BX["LiveFeedClass_'+this.randomString+'"].notify('+userId+')');
					result.errors = result.errors || [{}];
					this.showModalWithStatusAction({
						status: 'error',
						message: result.errors.pop().message
					})
				}
			}, this)
		});
	};

	LiveFeedClass.prototype.notifyAdmin = function ()
	{
		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.addToLinkParam(this.ajaxUrl, 'action', 'getListAdmin'),
			data: {
				iblockId: BX('bx-lists-selected-list').value,
				sessid: BX.bitrix_sessid()
			},
			onsuccess: BX.delegate(function (result)
			{
				if(result.status == 'success')
				{
					var html = '<span class="bp-question"><span>!</span>'
						+BX.message('LISTS_NOTIFY_ADMIN_TITLE_WHY').replace('#NAME_PROCESSES#', BX('bx-lists-title-notify-admin-popup').value)+'</span>';
					html += '<p>'+BX.message('LISTS_NOTIFY_ADMIN_TEXT_ONE').replace('#NAME_PROCESSES#', BX('bx-lists-title-notify-admin-popup').value)+'</p>';
					html += '<p>'+BX.message('LISTS_NOTIFY_ADMIN_TEXT_TWO').replace('#NAME_PROCESSES#', BX('bx-lists-title-notify-admin-popup').value)+'</p>';
					html += '<span class="bp-question-title">'+BX.message('LISTS_NOTIFY_ADMIN_MESSAGE')+'</span>';
					for(var k in result.listAdmin)
					{
						var img ='';
						if(result.listAdmin[k].img)
						{
							img = '<img src="'+result.listAdmin[k].img+'" alt="">';
						}
						html += '<div class="bp-question-item"><a href="#" class="bp-question-item-avatar"><span class="bp-question-item-avatar-inner">'+img +
						'</span></a><span class="bp-question-item-info"><span>'+result.listAdmin[k].name+'</span></span>' +
							'<span id="bx-lists-notify-success-'+result.listAdmin[k].id+'" class="bx-lists-notify-success"></span>'+
						'<a id="bx-lists-notify-button-'+result.listAdmin[k].id+'" href="#" onclick=\'BX["LiveFeedClass_'+this.randomString+'"].notify('+result.listAdmin[k].id+');\' class="webform-small-button bp-small-button webform-small-button-blue">' +
						''+BX.message('LISTS_NOTIFY_ADMIN_MESSAGE_BUTTON')+'</a></div>';
					}

					BX('bx-lists-notify-admin-popup-content').innerHTML = html;

					this.modalWindow({
						modalId: 'bx-lists-popup',
						title: BX('bx-lists-title-notify-admin-popup').value,
						overlay: false,
						contentStyle: {
							width: '600px',
							paddingTop: '10px',
							paddingBottom: '10px'
						},
						content: [BX('bx-lists-notify-admin-popup-content')],
						events : {
							onPopupClose : function() {
								BX('bx-lists-notify-admin-popup').appendChild(BX('bx-lists-notify-admin-popup-content'));
								this.destroy();
							},
							onAfterPopupShow : function(popup) {
								var title = BX.findChild(popup.contentContainer, {class: 'bx-lists-popup-title'}, true);
								if (title)
								{
									title.style.cursor = "move";
									BX.bind(title, "mousedown", BX.proxy(popup._startDrag, popup));
								}
								BX.PopupMenu.destroy('settings-lists');
							}
						},
						buttons: [
							BX.create('a', {
								text : BX.message("LISTS_CANCEL_BUTTON_CLOSE"),
								props: {
									className: 'webform-small-button webform-button-cancel'
								},
								events : {
									click : BX.delegate(function (e) {
										BX.PopupWindowManager.getCurrentPopup().close();
									}, this)
								}
							})
						]
					});
				}
				else
				{
					result.errors = result.errors || [{}];
					this.showModalWithStatusAction({
						status: 'error',
						message: result.errors.pop().message
					})
				}
			}, this)
		});
	};

	LiveFeedClass.prototype.setResponsible = function ()
	{
		if(BX.PopupWindowManager.getCurrentPopup())
		{
			BX.PopupWindowManager.getCurrentPopup().close();
		}

		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.addToLinkParam(this.ajaxUrl, 'action', 'checkPermissions'),
			data: {
				iblockId: BX('bx-lists-selected-list').value,
				sessid: BX.bitrix_sessid()
			},
			onsuccess: BX.delegate(function (result)
			{
				if(result.status == 'success')
				{
					BX.ajax({
						url: this.addToLinkParam(this.ajaxUrl, 'action', 'setResponsible'),
						method: 'POST',
						dataType: 'html',
						data: {
							iblockId: BX('bx-lists-selected-list').value,
							sessid: BX.bitrix_sessid()
						},
						onsuccess: BX.delegate(function (data)
						{
							BX.adjust(BX('bx-lists-set-responsible-content'), {
								html: data
							});
						}, this)
					});

					this.modalWindow({
						modalId: 'bx-lists-popup',
						title: BX.message("LISTS_SELECT_STAFF_SET_RESPONSIBLE"),
						overlay: false,
						contentStyle: {
							width: '600px',
							paddingTop: '10px',
							paddingBottom: '10px'
						},
						content: [BX('bx-lists-set-responsible-content')],
						events : {
							onPopupClose : function() {
								BX('bx-lists-set-responsible').appendChild(BX('bx-lists-set-responsible-content'));
								this.destroy();
							},
							onAfterPopupShow : function(popup) {
								var title = BX.findChild(popup.contentContainer, {class: 'bx-lists-popup-title'}, true);
								if (title)
								{
									title.style.cursor = "move";
									BX.bind(title, "mousedown", BX.proxy(popup._startDrag, popup));
								}
								BX.PopupMenu.destroy('settings-lists');
							}
						},
						buttons: [
							BX.create('a', {
								text : BX.message("LISTS_SAVE_BUTTON_SET_RIGHT"),
								props: {
									className: 'webform-small-button webform-small-button-accept'
								},
								events : {
									click : BX.delegate(function (e)
									{
										var form = BX.findChild(BX('bx-lists-set-responsible-content'), {tag: 'FORM'}, true);
										if (form)
											form.onsubmit(form, e);
									})
								}
							}),
							BX.create('a', {
								text : BX.message("LISTS_CANCEL_BUTTON_SET_RIGHT"),
								props: {
									className: 'webform-small-button webform-button-cancel'
								},
								events : {
									click : BX.delegate(function (e) {
										BX.PopupWindowManager.getCurrentPopup().close();
									}, this)
								}
							})
						]
					});
				}
				else
				{
					if(BX('bx-lists-cjeck-notify-admin').value)
					{
						this.notifyAdmin();
					}
					else
					{
						result.errors = result.errors || [{}];
						this.showModalWithStatusAction({
							status: 'error',
							message: result.errors.pop().message
						})
					}
				}
			}, this)
		});
	};

	LiveFeedClass.prototype.show = function (el)
	{
		if (this.getRealDisplay(el) != 'none')
			return;

		var old = el.getAttribute("displayOld");
		el.style.display = old || "";

		if (this.getRealDisplay(el) === "none" ) {
			var nodeName = el.nodeName, body = document.body, display;

			if (displayCache[nodeName]) {
				display = displayCache[nodeName];
			} else {
				var testElem = document.createElement(nodeName);
				body.appendChild(testElem);
				display = this.getRealDisplay(testElem);

				if (display === "none" ) {
					display = "block";
				}

				body.removeChild(testElem);
				displayCache[nodeName] = display;
			}

			el.setAttribute('displayOld', display);
			el.style.display = display;
		}
	};

	LiveFeedClass.prototype.hide = function (el)
	{
		if (!el.getAttribute('displayOld'))
		{
			el.setAttribute("displayOld", el.style.display);
		}
		el.style.display = "none";
	};

	LiveFeedClass.prototype.getRealDisplay = function (elem) {
		if (elem.currentStyle) {
			return elem.currentStyle.display;
		} else if (window.getComputedStyle) {
			var computedStyle = window.getComputedStyle(elem, null );
			return computedStyle.getPropertyValue('display');
		}
	};

	LiveFeedClass.prototype.modalWindow = function (params)
	{
		params = params || {};
		params.title = params.title || false;
		params.bindElement = params.bindElement || null;
		params.overlay = typeof params.overlay == "undefined" ? true : params.overlay;
		params.autoHide = params.autoHide || false;
		params.closeIcon = typeof params.closeIcon == "undefined"? {right: "20px", top: "10px"} : params.closeIcon;
		params.modalId = params.modalId || 'lists_modal_window_' + (Math.random() * (200000 - 100) + 100);
		params.withoutContentWrap = typeof params.withoutContentWrap == "undefined" ? false : params.withoutContentWrap;
		params.contentClassName = params.contentClassName || '';
		params.contentStyle = params.contentStyle || {};
		params.content = params.content || [];
		params.buttons = params.buttons || false;
		params.events = params.events || {};
		params.withoutWindowManager = !!params.withoutWindowManager || false;

		var contentDialogChildren = [];
		if (params.title) {
			contentDialogChildren.push(BX.create('div', {
				props: {
					className: 'bx-lists-popup-title'
				},
				text: params.title
			}));
		}
		if (params.withoutContentWrap) {
			contentDialogChildren = contentDialogChildren.concat(params.content);
		}
		else {
			contentDialogChildren.push(BX.create('div', {
				props: {
					className: 'bx-lists-popup-content ' + params.contentClassName
				},
				style: params.contentStyle,
				children: params.content
			}));
		}
		var buttons = [];
		if (params.buttons) {
			for (var i in params.buttons) {
				if (!params.buttons.hasOwnProperty(i)) {
					continue;
				}
				if (i > 0) {
					buttons.push(BX.create('SPAN', {html: '&nbsp;'}));
				}
				buttons.push(params.buttons[i]);
			}

			contentDialogChildren.push(BX.create('div', {
				props: {
					className: 'bx-lists-popup-buttons'
				},
				children: buttons
			}));
		}

		var contentDialog = BX.create('div', {
			props: {
				className: 'bx-lists-popup-container'
			},
			children: contentDialogChildren
		});

		params.events.onPopupShow = BX.delegate(function () {
			if (buttons.length) {
				firstButtonInModalWindow = buttons[0];
				BX.bind(document, 'keydown', BX.proxy(this._keyPress, this));
			}

			if(params.events.onPopupShow)
				BX.delegate(params.events.onPopupShow, BX.proxy_context);
		}, this);
		var closePopup = params.events.onPopupClose;
		params.events.onPopupClose = BX.delegate(function () {

			firstButtonInModalWindow = null;
			try
			{
				BX.unbind(document, 'keydown', BX.proxy(this._keypress, this));
			}
			catch (e) { }

			if(closePopup)
			{
				BX.delegate(closePopup, BX.proxy_context)();
			}

			if(params.withoutWindowManager)
			{
				delete windowsWithoutManager[params.modalId];
			}

			BX.proxy_context.destroy();
		}, this);

		var modalWindow;
		if(params.withoutWindowManager)
		{
			if(!!windowsWithoutManager[params.modalId])
			{
				return windowsWithoutManager[params.modalId]
			}
			modalWindow = new BX.PopupWindow(params.modalId, params.bindElement, {
				content: contentDialog,
				closeByEsc: true,
				closeIcon: params.closeIcon,
				autoHide: params.autoHide,
				overlay: params.overlay,
				events: params.events,
				buttons: [],
				zIndex : isNaN(params["zIndex"]) ? 0 : params.zIndex
			});
			windowsWithoutManager[params.modalId] = modalWindow;
		}
		else
		{
			modalWindow = BX.PopupWindowManager.create(params.modalId, params.bindElement, {
				content: contentDialog,
				closeByEsc: true,
				closeIcon: params.closeIcon,
				autoHide: params.autoHide,
				overlay: params.overlay,
				events: params.events,
				buttons: [],
				zIndex : isNaN(params["zIndex"]) ? 0 : params.zIndex
			});

		}

		modalWindow.show();

		return modalWindow;
	};

	LiveFeedClass.prototype.submitForm = function()
	{
		if(this.getRealDisplay(BX('feed-add-post-content-lists')) == 'none')
			BX.bind(BX('blog-submit-button-save'), 'click', submitBlogPostForm());

		BX('blog-submit-button-save').setAttribute('onclick','');
		BX.addClass(BX('blog-submit-button-save'), 'feed-add-button-load');
		var lists = BX.findChildrenByClassName(BX('bx-lists-store-lists'), 'bx-lists-input-list');
		for (var i = 0; i < lists.length; i++)
		{
			if(lists[i].value != BX('bx-lists-selected-list').value)
			{
				this.removeElement(BX('bx-lists-div-list-'+lists[i].value));
				this.removeElement(BX('bx-lists-input-list-'+lists[i].value));
			}
		}

		BX.ajax.submitAjax(BX('blogPostForm'), {
			method : "POST",
			url: this.addToLinkParam(this.ajaxUrl, 'action', 'checkDataElementCreation'),
			processData : true,
			onsuccess: BX.delegate(function (result)
			{
				result = BX.parseJSON(result, {});
				if(result.status == 'success')
				{
					BX.bind(BX('blog-submit-button-save'), 'click', submitBlogPostForm());
				}
				else
				{
					BX.removeClass(BX('blog-submit-button-save'), 'feed-add-button-load');
					BX('bx-lists-block-errors').innerHTML = result.errors.pop().message;
					this.show(BX('bx-lists-block-errors'));
					BX('blog-submit-button-save').setAttribute('onclick','BX["LiveFeedClass_'+this.randomString+'"].submitForm();');
				}
			}, this)
		});
	};

	LiveFeedClass.prototype.errorPopup = function (message)
	{
		this.showModalWithStatusAction({
			status: 'error',
			message: message
		})
	};

	return LiveFeedClass;

})();
