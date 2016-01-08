BX.namespace("BX.Disk");
if(!BX.Disk.pathToUser)
{
	BX.Disk = (function ()
	{
		var firstButtonInModalWindow = null;
		var entityToNewShared = {};
		var moduleTasks = {};

		var windowsWithoutManager = {};
		return	{
			pathToUser: '/company/personal/user/#user_id#/',
			isEmptyObject: function (obj)
			{
				if (obj == null) return true;
				if (obj.length && obj.length > 0)
					return false;
				if (obj.length === 0)
					return true;

				for (var key in obj) {
					if (hasOwnProperty.call(obj, key))
						return false;
				}

				return true;
			},
			_keyPress: function (e)
			{
				var key = (e || window.event).keyCode || (e || window.event).charCode;
				//enter
				if (key == 13 && firstButtonInModalWindow) {
					BX.fireEvent(firstButtonInModalWindow, 'click');
					return BX.PreventDefault(e);
				}
			},
			modalWindow: function (params)
			{
				params = params || {};
				params.title = params.title || false;
				params.bindElement = params.bindElement || null;
				params.overlay = typeof params.overlay == "undefined" ? true : params.overlay;
				params.autoHide = params.autoHide || false;
				params.closeIcon = typeof params.closeIcon == "undefined"? {right: "20px", top: "10px"} : params.closeIcon;
				params.modalId = params.modalId || 'disk_modal_window_' + (Math.random() * (200000 - 100) + 100);
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
							className: 'bx-disk-popup-title'
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
							className: 'bx-disk-popup-content ' + params.contentClassName
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
							className: 'bx-disk-popup-buttons'
						},
						children: buttons
					}));
				}

				var contentDialog = BX.create('div', {
					props: {
						className: 'bx-disk-popup-container'
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
			},

			modalWindowLoader: function (queryUrl, params, bindElement)
			{
				bindElement = bindElement || null;
				params = params || {};
				var modalId = params.id;
				var expectResponseType = params.responseType || 'html';
				var afterSuccessLoad = params.afterSuccessLoad || null;
				var onPopupClose = params.onPopupClose || null;
				var postData = params.postData || {};

				var popup = BX.PopupWindowManager.create(
					'bx_disk_' + modalId,
					bindElement,
					{
						closeIcon: true,
						offsetTop: 5,
						autoHide: true,
						lightShadow: false,
						overlay: true,
						content: BX.create('div', {
							children: [
								BX.create('div', {
										style: {
											display: 'table',
											width: '30px',
											height: '30px'
										},
										children: [
											BX.create('div', {
												style: {
													display: 'table-cell',
													verticalAlign: 'middle',
													textAlign: 'center'
												},
												children: [
													BX.create('div', {
														props: {
															className: 'bx-disk-wrap-loading-modal'
														}
													}),
													BX.create('span', {
														text: ''
													})
												]
											})
										]
									}
								)
							]
						}),
						closeByEsc: true,
						events: {
							onPopupClose: function ()
							{
								if (onPopupClose) {
									BX.delegate(onPopupClose, this)();
								}

								this.destroy();
							}
						}
					}
				);
				popup.show();

				postData['sessid'] = BX.bitrix_sessid();

				BX.ajax({
					url: queryUrl,
					method: 'POST',
					dataType: expectResponseType,
					data: postData,
					onsuccess: BX.delegate(function (data)
					{

						if (expectResponseType == 'html') {
							popup.setContent(BX.create('DIV', {html: data}));
							popup.adjustPosition();
						}
						else if(expectResponseType == 'json')
						{
							data = data || {};
						}

						afterSuccessLoad && afterSuccessLoad(data, popup);
					}, this),
					onfailure: function (data)
					{
					}
				});
			},

			addToLinkParam: function (link, name, value)
			{
				if (!link.length) {
					return '?' + name + '=' + value;
				}
				link = BX.util.remove_url_param(link, name);
				if (link.indexOf('?') != -1) {
					return link + '&' + name + '=' + value;
				}
				return link + '?' + name + '=' + value;
			},

			getFirstErrorFromResponse: function(reponse)
			{
				reponse = reponse || {};
				if(!reponse.errors)
					return '';

				return reponse.errors.shift().message;
			},

			showModalWithStatusAction: function (response, action)
			{
				response = response || {};
				if (!response.message) {
					if (response.status == 'success') {
						response.message = BX.message('DISK_JS_STATUS_ACTION_SUCCESS');
					}
					else {
						response.message = BX.message('DISK_JS_STATUS_ACTION_ERROR') + '. ' + this.getFirstErrorFromResponse(response);
					}
				}
				var messageBox = BX.create('div', {
					props: {
						className: 'bx-disk-alert'
					},
					children: [
						BX.create('span', {
							props: {
								className: 'bx-disk-aligner'
							}
						}),
						BX.create('span', {
							props: {
								className: 'bx-disk-alert-text'
							},
							text: response.message
						}),
						BX.create('div', {
							props: {
								className: 'bx-disk-alert-footer'
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
					if (!w || w.uniquePopupId != 'bx-disk-status-action') {
						return;
					}
					w.close();
					w.destroy();
				}, 3000);
				var popupConfirm = BX.PopupWindowManager.create('bx-disk-status-action', null, {
					content: messageBox,
					onPopupClose: function ()
					{
						this.destroy();
						clearTimeout(idTimeout);
					},
					autoHide: true,
					zIndex: 10200,
					className: 'bx-disk-alert-popup'
				});
				popupConfirm.show();

				BX('bx-disk-status-action').onmouseover = function (e)
				{
					clearTimeout(idTimeout);
				};

				BX('bx-disk-status-action').onmouseout = function (e)
				{
					idTimeout = setTimeout(function ()
					{
						var w = BX.PopupWindowManager.getCurrentPopup();
						if (!w || w.uniquePopupId != 'bx-disk-status-action') {
							return;
						}
						w.close();
						w.destroy();
					}, 3000);
				};
			},
			showActionModal: function (params)
			{
				var text = params.text;
				var autoHide = params.autoHide;
				var iconSrc;
				if(params.showLoaderIcon) {
					iconSrc = '/bitrix/js/main/core/images/yell-waiter.gif';
				}
				else if(params.showSuccessIcon) {
					iconSrc = '/bitrix/js/main/core/images/viewer-tick.png';
				}
				else if(!!params.icon)
				{
					iconSrc = params.icon;
				}

				var messageBox = BX.create('div', {
					props: {
						className: 'bx-disk-alert'
					},
					children: [
						BX.create('span', {
							props: {
								className: 'bx-disk-alert-icon'
							},
							children: [
								iconSrc? BX.create('img', {
									props: {
										src: iconSrc
									}
								}) : null
							]
						}),

						BX.create('span', {
							props: {
								className: 'bx-disk-aligner'
							}
						}),
						BX.create('span', {
							props: {
								className: 'bx-disk-alert-text'
							},
							text: text
						}),
						BX.create('div', {
							props: {
								className: 'bx-disk-alert-footer'
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
					if(!autoHide)
					{
						return;
					}

					var w = BX.PopupWindowManager.getCurrentPopup();
					if (!w || w.uniquePopupId != 'bx-disk-status-action') {
						return;
					}
					w.close();
					w.destroy();
				}, 3000);
				var popupConfirm = BX.PopupWindowManager.create('bx-disk-status-action', null, {
					content: messageBox,
					onPopupClose: function ()
					{
						this.destroy();
						clearTimeout(idTimeout);
					},
					autoHide: autoHide,
					zIndex: 10200,
					className: 'bx-disk-alert-popup'
				});
				popupConfirm.show();

				BX('bx-disk-status-action').onmouseover = function (e)
				{
					clearTimeout(idTimeout);
				};

				if(!autoHide)
				{
					return;
				}

				BX('bx-disk-status-action').onmouseout = function (e)
				{
					idTimeout = setTimeout(function ()
					{
						var w = BX.PopupWindowManager.getCurrentPopup();
						if (!w || w.uniquePopupId != 'bx-disk-status-action') {
							return;
						}
						w.close();
						w.destroy();
					}, 3000);
				};
			},

			storePathToUser: function (link)
			{
				if (link) {
					this.pathToUser = link;
				}
			},

			getUrlForDownloadDesktop: function ()
			{
				return (BX.browser.IsMac()? "http://dl.bitrix24.com/b24/bitrix24_desktop.dmg": "http://dl.bitrix24.com/b24/bitrix24_desktop.exe");
			},

			getDownloadDesktop: function ()
			{
				document.location.href = this.getUrlForDownloadDesktop();
			},

			deactiveBanner: function (name)
			{
				BX.userOptions.save('disk', '~banner-offer', name, true);
				BX.userOptions.send(null);
			},

			getPathToUser: function (userId)
			{
				return this.pathToUser.replace('#USER_ID#', userId).replace('#user_id#', userId);
			},

			getNumericCase: function (number, once, multi_21, multi_2_4, multi_5_20)
			{
				if (number == 1) {
					return once;
				}

				if (number < 0) {
					number = -number;
				}

				number %= 100;
				if (number >= 5 && number <= 20) {
					return multi_5_20;
				}

				number %= 10;
				if (number == 1) {
					return multi_21;
				}

				if (number >= 2 && number <= 4) {
					return multi_2_4;
				}

				return multi_5_20;
			},

			getRightLabelByTaskName: function(name){
				switch(name.toLowerCase())
				{
					case 'disk_access_read':
						return BX.message('DISK_JS_SHARING_LABEL_RIGHT_READ');
					case 'disk_access_edit':
						return BX.message('DISK_JS_SHARING_LABEL_RIGHT_EDIT');
					case 'disk_access_full':
						return BX.message('DISK_JS_SHARING_LABEL_RIGHT_FULL');
					default:
						return 'error';
				}
			},

			appendNewShared: function (params) {

				var readOnly = params.readOnly;
				var maxTaskName = params.maxTaskName || 'disk_access_full';
				var destFormName = params.destFormName;

				var entityId = params.item.id;
				var entityName = params.item.name;
				var entityAvatar = params.item.avatar;
				var type = params.type;
				var right = params.right || 'disk_access_read';

				entityToNewShared[entityId] = {
					item: params.item,
					type: params.type,
					right: right
				};

				function pseudoCompareTaskName(taskName1, taskName2)
				{
					var taskName1Pos;
					var taskName2Pos;
					switch(taskName1)
					{
						case 'disk_access_read':
							taskName1Pos = 2;
							break;
						case 'disk_access_edit':
							taskName1Pos = 3;
							break;
						case 'disk_access_full':
							taskName1Pos = 4;
							break;
						default:
							//unknown task names
							return 0;
					}
					switch(taskName2)
					{
						case 'disk_access_read':
							taskName2Pos = 2;
							break;
						case 'disk_access_edit':
							taskName2Pos = 3;
							break;
						case 'disk_access_full':
							taskName2Pos = 4;
							break;
						default:
							//unknown task names
							return 0;
					}
					if(taskName1Pos == taskName2Pos)
					{
						return 0;
					}

					return taskName1Pos > taskName2Pos? 1 : -1;
				}

				BX('bx-disk-popup-shared-people-list').appendChild(
					BX.create('tr', {
						attrs: {
							'data-dest-id': entityId
						},
						children: [
							BX.create('td', {
								props: {
									className: 'bx-disk-popup-shared-people-list-col1'
								},
								children: [
									BX.create('a', {
										props: {
											className: 'bx-disk-filepage-used-people-link'
										},
										children: [
											BX.create('span', {
												props: {
													className: 'bx-disk-filepage-used-people-avatar ' + (type != 'users'? ' group' : '')
												},
												style: {
													backgroundImage: entityAvatar? 'url(' + entityAvatar + ')' : null
												}
											}),
											entityName
										]
									})
								]
							}),
							BX.create('td', {
								props: {
									className: 'bx-disk-popup-shared-people-list-col2'
								},
								children: [
									BX.create('a', {
										props: {
											className: 'bx-disk-filepage-used-people-permission'
										},
										style: {
											cursor: 'pointer'
										},
										text: this.getRightLabelByTaskName(right),
										events: {
											click: BX.delegate(function(e){
												if(readOnly)
												{
													return BX.PreventDefault(e);
												}
												var targetElement = e.target || e.srcElement;
												BX.PopupMenu.show('disk_open_menu_with_rights', BX(targetElement), [
														(pseudoCompareTaskName(maxTaskName, 'disk_access_read') >= 0? {
															text: BX.message('DISK_JS_SHARING_LABEL_RIGHT_READ'),
															href: "#",
															onclick: BX.delegate(function (e) {
																BX.PopupMenu.destroy('disk_open_menu_with_rights');
																BX.adjust(targetElement, {text: this.getRightLabelByTaskName('disk_access_read')});

																BX.onCustomEvent('onChangeRightOfSharing', [entityId, 'disk_access_read']);

																entityToNewShared[entityId]['right'] = 'disk_access_read';

																return BX.PreventDefault(e);
															}, this)
														} : null),
														(pseudoCompareTaskName(maxTaskName, 'disk_access_edit') >= 0? {
															text: BX.message('DISK_JS_SHARING_LABEL_RIGHT_EDIT'),
															href: "#",
															onclick: BX.delegate(function (e) {
																BX.PopupMenu.destroy('disk_open_menu_with_rights');
																BX.adjust(targetElement, {text: this.getRightLabelByTaskName('disk_access_edit')});

																BX.onCustomEvent('onChangeRightOfSharing', [entityId, 'disk_access_edit']);

																entityToNewShared[entityId]['right'] = 'disk_access_edit';

																return BX.PreventDefault(e);
															}, this)
														} : null),
														(pseudoCompareTaskName(maxTaskName, 'disk_access_full') >= 0? {
															text: BX.message('DISK_JS_SHARING_LABEL_RIGHT_FULL'),
															href: "#",
															onclick: BX.delegate(function (e) {
																BX.PopupMenu.destroy('disk_open_menu_with_rights');
																BX.adjust(targetElement, {text: this.getRightLabelByTaskName('disk_access_full')});

																BX.onCustomEvent('onChangeRightOfSharing', [entityId, 'disk_access_full']);

																entityToNewShared[entityId]['right'] = 'disk_access_full';

																return BX.PreventDefault(e);
															}, this)
														} : null)
													],
													{
														angle: {
															position: 'top',
															offset: 45
														},
														autoHide: true,
														overlay: {
															opacity: 0.01
														},
														events: {
															onPopupClose: function() {BX.PopupMenu.destroy('disk_open_menu_with_rights');}
														}
													}
												);

											}, this)
										}
									})
								]
							}),
							BX.create('td', {
								props: {
									className: 'bx-disk-popup-shared-people-list-col3 tar'
								},
								children: [
									(!readOnly? BX.create('span', {
										props: {
											className: 'bx-disk-filepage-used-people-del'
										},
										events: {
											click: BX.delegate(function(e){
												BX.SocNetLogDestination.deleteItem(entityId, type, destFormName);
												var src = e.target || e.srcElement;
												BX.remove(src.parentNode.parentNode);
											}, this)
										}
									}) : null)
								]
							})
						]
					})
				);
			},

			openPopupMenuWithRights: function (e, entityId)
			{
				var items = [];
				var task;
				var targetElement = e.target || e.srcElement;

				for (var i in moduleTasks)
				{
					if(!moduleTasks.hasOwnProperty(i))
					{
						continue;
					}
					task = BX.clone(moduleTasks[i], true);
					items.push({
							task: task,
							text: task.TITLE,
							href: "#",
							onclick: function (e, item)
							{
								BX.adjust(targetElement, {text: item.task.TITLE});

								BX.onCustomEvent('onChangeRight', [entityId, item.task]);
								BX.onCustomEvent('onChangeSystemRight', [entityId, item.task]);

								BX.PopupMenu.destroy('disk_open_menu_with_rights');
								return BX.PreventDefault(e);
							}
						}
					);
				}

				BX.PopupMenu.show('disk_open_menu_with_rights', BX(targetElement), items,
					{
						angle: {
							position: 'top',
							offset: 45
						},
						autoHide: true,
						overlay: {
							opacity: 0.01
						},
						events: {
							onPopupClose: function() {BX.PopupMenu.destroy('disk_open_menu_with_rights');}
						}
					}
				);

			},

			setModuleTasks: function (newModuleTasks)
			{
				moduleTasks = newModuleTasks;
			},

			getFirstModuleTask: function ()
			{
				if(this.isEmptyObject(moduleTasks))
				{
					return {};
				}
				for (var i in moduleTasks)
				{
					if (moduleTasks.hasOwnProperty(i) && typeof(i) !== 'function')
					{
						return moduleTasks[i];
						break;
					}
				}

				return {};
			},

			appendRight: function (params) {

				var readOnly = params.readOnly;
				var detachOnly = params.detachOnly || false;
				var destFormName = params.destFormName;

				var entityId = params.item.id;
				var entityName = params.item.name;
				var entityAvatar = params.item.avatar;
				var type = params.type;
				var right = params.right || {};

				if(!right.title && right.id)
				{
					right.title = moduleTasks[right.id].TITLE;
				}
				else if(!right.title)
				{
					var first = this.getFirstModuleTask();
					right = {
						id: first.ID,
						title: first.TITLE
					};
					BX.onCustomEvent('onChangeRight', [entityId, first]);
				}

				var rightLabel = right.title;

				BX('bx-disk-popup-shared-people-list').appendChild(
					BX.create('tr', {
						attrs: {
							'data-dest-id': entityId
						},
						children: [
							BX.create('td', {
								props: {
									className: 'bx-disk-popup-shared-people-list-col1'
								},
								children: [
									BX.create('a', {
										props: {
											className: 'bx-disk-filepage-used-people-link'
										},
										children: [
											BX.create('span', {
												props: {
													className: 'bx-disk-filepage-used-people-avatar ' + (type != 'users'? ' group' : '')
												},
												style: {
													backgroundImage: entityAvatar? 'url(' + entityAvatar + ')' : null
												}
											}),
											entityName
										]
									})
								]
							}),
							BX.create('td', {
								props: {
									className: 'bx-disk-popup-shared-people-list-col2'
								},
								children: [
									BX.create('a', {
										props: {
											className: 'bx-disk-filepage-used-people-permission'
										},
										style: {
											cursor: 'pointer'
										},
										text: rightLabel,
										events: {
											click: BX.delegate(function(e){
												BX.PreventDefault(e);
												if(detachOnly)
												{
													return;
												}
												this.openPopupMenuWithRights(e, entityId);
											}, this)
										}
									})
								]
							}),
							BX.create('td', {
								props: {
									className: 'bx-disk-popup-shared-people-list-col3 tar'
								},
								children: [
									(!readOnly || detachOnly? BX.create('span', {
										props: {
											className: 'bx-disk-filepage-used-people-del'
										},
										events: {
											click: BX.delegate(function(e){
												BX.onCustomEvent('onDetachRight', [entityId]);
												if(!detachOnly)
												{
													BX.SocNetLogDestination.deleteItem(entityId, type, destFormName);
												}
												var src = e.target || e.srcElement;
												BX.remove(src.parentNode.parentNode);
											}, this)
										}
									}) : null)
								]
							})
						]
					})
				);
			},
			//system right. Todo refactor
			appendSystemRight: function (params) {
				var destFormName = params.destFormName;

				var isBitrix24 = params.isBitrix24 || false;
				var entityId = params.item.id;
				var entityName = params.item.name;
				var entityAvatar = params.item.avatar;
				var type = params.type;
				var right = params.right || {};

				var readOnly = params.readOnly;

				//todo for B24 only. Don't show user groups
				if(isBitrix24 && entityId && entityId != "G2" && entityId.search('G') == 0)
				{
					return;
				}

				if(!right.title && right.id)
				{
					right.title = moduleTasks[right.id].TITLE;
				}
				else if(!right.title)
				{
					var first = this.getFirstModuleTask();
					right = {
						id: first.ID,
						title: first.TITLE
					};
					BX.onCustomEvent('onChangeSystemRight', [entityId, first]);
				}

				var rightLabel = right.title;

				BX('bx-disk-popup-shared-people-list').appendChild(
					BX.create('tr', {
						attrs: {
							'data-dest-id': entityId
						},
						children: [
							BX.create('td', {
								props: {
									className: 'bx-disk-popup-shared-people-list-col1'
								},
								children: [
									BX.create('a', {
										props: {
											className: 'bx-disk-filepage-used-people-link'
										},
										children: [
											BX.create('span', {
												props: {
													className: 'bx-disk-filepage-used-people-avatar ' + (type != 'users'? ' group' : '')
												},
												style: {
													backgroundImage: entityAvatar? 'url(' + entityAvatar + ')' : null
												}
											}),
											entityName
										]
									})
								]
							}),
							BX.create('td', {
								props: {
									className: 'bx-disk-popup-shared-people-list-col2'
								},
								children: [
									(readOnly? BX.create('span', {
										props: {
											className: 'bx-disk-filepage-used-people-permission-read-only'
										},
										text: rightLabel
									}) :
									BX.create('a', {
										props: {
											className: 'bx-disk-filepage-used-people-permission'
										},
										text: rightLabel,
										events: {
											click: BX.delegate(function(e){
												BX.PreventDefault(e);
												this.openPopupMenuWithRights(e, entityId);
											}, this)
										}
									}))
								]
							}),
							BX.create('td', {
								props: {
									className: 'bx-disk-popup-shared-people-list-col3 tar'
								},
								children: [
									(!readOnly? BX.create('span', {
										props: {
											className: 'bx-disk-filepage-used-people-del'
										},
										events: {
											click: BX.delegate(function(e){
												BX.onCustomEvent('onDetachSystemRight', [entityId]);
												var src = e.target || e.srcElement;
												BX.remove(src.parentNode.parentNode);
											}, this)
										}
									}) : null)
								]
							})
						]
					})
				);
			},

			showSharingDetailWithoutEdit: function (params) {

				params = params || {};
				var objectId = params.object.id;
				var ajaxUrl = params.ajaxUrl;

				BX.Disk.modalWindowLoader(
					BX.Disk.addToLinkParam(ajaxUrl, 'action', 'showSharingDetail'),
					{
						id: 'folder_list_sharing_detail_object_' + objectId,
						responseType: 'json',
						postData: {
							objectId: objectId
						},
						afterSuccessLoad: BX.delegate(function(response)
						{
							if(response.status != 'success')
							{
								response.errors = response.errors || [{}];
								BX.Disk.showModalWithStatusAction({
									status: 'error',
									message: response.errors.pop().message
								})
							}

							var objectOwner = {
								name: response.owner.name,
								avatar: response.owner.avatar
							};

							BX.Disk.modalWindow({
								modalId: 'bx-disk-detail-sharing-folder',
								title: BX.message('DISK_JS_SHARING_LABEL_TITLE_MODAL_2'),
								contentClassName: '',
								contentStyle: {
									//paddingTop: '30px',
									//paddingBottom: '70px'
								},
								events: {
									onAfterPopupShow: BX.delegate(function () {

										for (var i in response.members) {
											if (!response.members.hasOwnProperty(i)) {
												continue;
											}
											BX.Disk.appendNewShared({
												destFormName: this.destFormName,
												readOnly: true,
												item: {
													id: response.members[i].entityId,
													name: response.members[i].name,
													avatar: response.members[i].avatar
												},
												type: response.members[i].type,
												right: response.members[i].right
											})

										}
									}, this),
									onPopupClose: function () {
										this.destroy();
									}
								},
								content: [
									BX.create('div', {
										props: {
											className: 'bx-disk-popup-content'
										},
										children: [
											BX.create('table', {
												props: {
													className: 'bx-disk-popup-shared-people-list'
												},
												children: [
													BX.create('thead', {
														html: '<tr>' +
															'<td class="bx-disk-popup-shared-people-list-head-col1">' + BX.message('DISK_JS_SHARING_LABEL_OWNER') + '</td>' +
														'</tr>'
													}),
													BX.create('tr', {
														html: '<tr>' +
															'<td class="bx-disk-popup-shared-people-list-col1" style="border-bottom: none;"><a class="bx-disk-filepage-used-people-link" href=""><span class="bx-disk-filepage-used-people-avatar" style="background-image: url(' + objectOwner.avatar + ');"></span>' + objectOwner.name + '</a></td>' +
														'</tr>'
													})
												]
											}),
											BX.create('table', {
												props: {
													id: 'bx-disk-popup-shared-people-list',
													className: 'bx-disk-popup-shared-people-list'
												},
												children: [
													BX.create('thead', {
														html: '<tr>' +
															'<td class="bx-disk-popup-shared-people-list-head-col1">' + BX.message('DISK_JS_SHARING_LABEL_NAME_RIGHTS_USER') + '</td>' +
															'<td class="bx-disk-popup-shared-people-list-head-col2">' + BX.message('DISK_JS_SHARING_LABEL_NAME_RIGHTS') + '</td>' +
															'<td class="bx-disk-popup-shared-people-list-head-col3"></td>' +
														'</tr>'
													})
												]
											}),
											BX.create('div', {
												html:
														'<span class="feed-add-destination-input-box" id="feed-add-post-destination-input-box">' +
															'<input autocomplete="off" type="text" value="" class="feed-add-destination-inp" id="feed-add-post-destination-input"/>' +
														'</span>'
											})
										]
									})
								],
								buttons: [
									BX.create('a', {
										text: BX.message('DISK_JS_BTN_CLOSE'),
										props: {
											className: 'bx-disk-btn bx-disk-btn-big bx-disk-btn-transparent'
										},
										events: {
											click: function () {
												BX.PopupWindowManager.getCurrentPopup().close();
											}
										}
									})
								]
							});
						}, this)
					}
				);
			},

			openWindowForSelectDocumentService: function (params) {
				var viewInUf = params.viewInUf || false;
				var current = BX.message('disk_document_service');
				var buttons = [
					BX.create('a', {
						text: BX.message('DISK_JS_BTN_SAVE'),
						props: {
							className: 'bx-disk-btn bx-disk-btn-big bx-disk-btn-green'
						},
						events: {
							click: function (e) {
								var service = BX.hasClass(BX('bx-disk-info-popup-btn-local'), 'bx-disk-info-popup-btn-active')? 'l' : 'gdrive';
								if(BX.type.isFunction(params.onSave))
								{
									params.onSave(service)
								}
								BX.PreventDefault(e);
								return false;
							}
						}
					}),
					BX.create('a', {
						text: BX.message('DISK_JS_BTN_CLOSE'),
						props: {
							className: 'bx-disk-btn bx-disk-btn-big bx-disk-btn-transparent'
						},
						events: {
							click: function (e) {
								BX.PopupWindowManager.getCurrentPopup().destroy();
								BX.PreventDefault(e);
								return false;
							}
						}
					})
				];

				var suffix = viewInUf? '' : '2';
				var lang = BX.message('LANGUAGE_ID');
				var imageSrc = '/bitrix/images/disk/disk_description' + suffix + '_en.png';
				if(lang == 'kz')
					lang = 'ru';
				switch(lang)
				{
					case 'ru':
					case 'en':
					case 'de':
					case 'ua':
						imageSrc = '/bitrix/images/disk/disk_description' + suffix + '_' + lang + '.png';
						break;
				}

				var content =
					'<div class="bx-disk-info-popup-cont-title">' +
						BX.message('DISK_JS_SERVICE_CHOICE_TITLE') +
					'</div>' +
					'<div class="bx-disk-info-popup-btn-wrap">' +
						'<span id="bx-disk-info-popup-btn-local" class="bx-disk-info-popup-btn bx-disk-info-popup-btn-local ' + ( (!current || current == 'l')? 'bx-disk-info-popup-btn-active' : '') + ' ">' +
							'<span class="bx-disk-info-popup-btn-text">' + BX.message('DISK_JS_SERVICE_LOCAL_TITLE') + '</span>' +
							'<span class="bx-disk-info-popup-btn-descript">' +
								BX.message('DISK_JS_SERVICE_LOCAL_TEXT') +
							'</span>' +
							'<span class="bx-disk-info-popup-btn-check"></span>' +
						'</span>' +
						'<span id="bx-disk-info-popup-btn-cloud" class="bx-disk-info-popup-btn bx-disk-info-popup-btn-cloud ' + ((!!current && current != 'l')? 'bx-disk-info-popup-btn-active' : '') + ' ">' +
							'<span class="bx-disk-info-popup-btn-text">' + BX.message('DISK_JS_SERVICE_CLOUD_TITLE') + '</span>' +
							'<span class="bx-disk-info-popup-btn-descript">' +
								BX.message('DISK_JS_SERVICE_CLOUD_TEXT') +
							'</span>' +
							'<span class="bx-disk-info-popup-btn-check"></span>' +
						'</span>' +
					'</div>' +
					'<div class="bx-disk-info-descript">' +
						(viewInUf? BX.message('DISK_JS_SERVICE_HELP_TEXT') : BX.message('DISK_JS_SERVICE_HELP_TEXT_2')) +
						'<img style="height: 182px;" class="bx-disk-info-descript-img" src="' + imageSrc + '" alt=""/>' +
					'</div>'
					;

				BX.Disk.modalWindow({
					modalId: 'bx-disk-select-doc-service',
					events: {
						onAfterPopupShow: function () {
							BX.bind(BX('bx-disk-info-popup-btn-cloud'), 'click', function(){
								if(BX.hasClass(this, 'bx-disk-info-popup-btn-active'))
									return;
								BX.toggleClass(this, 'bx-disk-info-popup-btn-active');
								BX.toggleClass(BX('bx-disk-info-popup-btn-local'), 'bx-disk-info-popup-btn-active');
							});
							BX.bind(BX('bx-disk-info-popup-btn-local'), 'click', function(){
								if(BX.hasClass(this, 'bx-disk-info-popup-btn-active'))
									return;
								BX.toggleClass(this, 'bx-disk-info-popup-btn-active');
								BX.toggleClass(BX('bx-disk-info-popup-btn-cloud'), 'bx-disk-info-popup-btn-active');
							});
						},
						onPopupClose: function () {
							this.destroy();
						}
					},
					title: BX.message('DISK_JS_SERVICE_CHOICE_TITLE_SMALL'),
					content: [BX.create('div', {html: content})],
					buttons: buttons
				});

			}
		}

	})();
}