function __onKeyTags(event)
{
	if (!event)
		event = window.event;
	var key = (event.keyCode ? event.keyCode : (event.which ? event.which : null));
    if (key == 13)
        addTag();
}

function __MPFonAfterMFLDeleteFile(file_id)
{
	if (BX('mfu_file_id_' + file_id))
	{
		BX.remove(BX('mfu_file_id_' + file_id));

		if (BX('newpost_photo_counter'))
		{
			BX('newpost_photo_counter').value = parseInt(BX('newpost_photo_counter').value) - 1;

			if (BX('newpost_photo_counter_title') && BX('newpost_photo_counter_title').firstChild)
			{
				BX.adjust(BX('newpost_photo_counter_title').firstChild, {
					html : BX('newpost_photo_counter').value
				});
				if (parseInt(BX('newpost_photo_counter').value) <= 0)
				{
					BX('newpost_photo_counter_title').style.display = 'none';
				}
			}
		}
	}
}

function __MPFonAfterMFLDeleteElement(element_id)
{
	var node = null;
	
	if (BX('mfu_element_id_' + element_id))
	{
		node = BX('mfu_element_id_' + element_id);
	}
	else if (BX('mfu_disk_id_' + element_id))
	{
		node = BX('mfu_disk_id_' + element_id);
	}
	
	if (node)
	{
		BX.remove(node);

		if (BX('newpost_photo_counter'))
		{
			BX('newpost_photo_counter').value = parseInt(BX('newpost_photo_counter').value) - 1;

			if (BX('newpost_photo_counter_title') && BX('newpost_photo_counter_title').firstChild)
			{
				BX.adjust(BX('newpost_photo_counter_title').firstChild, {
					html : BX('newpost_photo_counter').value
				});
				if (parseInt(BX('newpost_photo_counter').value) <= 0)
					BX('newpost_photo_counter_title').style.display = 'none';
			}
		}
	}

}

// remove block
function BXfpdUnSelectCallback(item, type, search)
{
	var elements = BX.findChildren(BX('feed-add-post-destination-item'), {attribute: {'data-id': ''+item.id+''}}, true);
	if (elements != null)
	{
		for (var j = 0; j < elements.length; j++)
			BX.remove(elements[j]);
	}
	BX('feed-add-post-destination-input').value = '';
}
function BXfpdOpenDialogCallback()
{
	BX.style(BX('feed-add-post-destination-input-box'), 'display', 'inline-block');
	BX.style(BX('bx-destination-tag'), 'display', 'none');
	BX.focus(BX('feed-add-post-destination-input'));
}

function BXfpdCloseDialogCallback()
{
	if (!BX.SocNetLogDestination.isOpenSearch() && BX('feed-add-post-destination-input').value.length <= 0)
	{
		BX.style(BX('feed-add-post-destination-input-box'), 'display', 'none');
		BX.style(BX('bx-destination-tag'), 'display', 'inline-block');
		BXfpdDisableBackspace();
	}
}

function BXfpdCloseSearchCallback()
{
	if (!BX.SocNetLogDestination.isOpenSearch() && BX('feed-add-post-destination-input').value.length > 0)
	{
		BX.style(BX('feed-add-post-destination-input-box'), 'display', 'none');
		BX.style(BX('bx-destination-tag'), 'display', 'inline-block');
		BX('feed-add-post-destination-input').value = '';
		BXfpdDisableBackspace();
	}

}
function BXfpdDisableBackspace(event)
{
	if (BX.SocNetLogDestination.backspaceDisable || BX.SocNetLogDestination.backspaceDisable != null)
		BX.unbind(window, 'keydown', BX.SocNetLogDestination.backspaceDisable);

	BX.bind(window, 'keydown', BX.SocNetLogDestination.backspaceDisable = function(event){
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

function CustomizeLightEditorForBlog(editorId)
{
	BX.addCustomEvent(window, 'LHE_OnInit', function(pLEditor){
		if (pLEditor.id == editorId)
		{
			BX.bind(pLEditor.pEditorDocument, 'keyup', function(){pLEditor.SaveSelectionRange(); return true;});
			BX.bind(pLEditor.pEditorDocument, 'touchend', function(){pLEditor.SaveSelectionRange(); return true;});
		}
	});

	BX.addCustomEvent(window, 'LHE_OnBeforeParsersInit', function(pLEditor){
		if (pLEditor.id == editorId)
			pLEditor.AddParser({
				name: 'bloguser',
				obj: {
					Parse: function(sName, sContent, pLEditor)
					{
						sContent = sContent.replace(/\[USER\s*=\s*(\d+)\]((?:\s|\S)*?)\[\/USER\]/ig, function(str, id, name)
						{
							var
								id = parseInt(id),
								name = BX.util.trim(name);

							return '<span id="' + pLEditor.SetBxTag(false, {tag: "bloguser", params: {value : id}}) + '" style="color: #2067B0; border-bottom: 1px dashed #2067B0;">' + name + '</span>';
						});
						return sContent;
					},
					UnParse: function(bxTag, pNode, pLEditor)
					{
						if (bxTag.tag == 'bloguser')
						{
							var name = '';
							for (var i = 0; i < pNode.arNodes.length; i++)
								name += pLEditor._RecursiveGetHTML(pNode.arNodes[i]);
							name = BX.util.trim(name);
							return "[USER=" + bxTag.params.value + "]" + name +"[/USER]";
						}
						return "";
					}
				}
			});
	});
}

BitrixMPF = function ()
{
	this.uri = '';
	this.uriSession = '';
	this.destinationUri = '';
	this.mentionUri = '';
	this.arLoadingFilesStack = [];
	this.progressbarId = null;
	this.progressbarState = 0;
	this.liveFeedId = null;
	this.arMention = [];
	this.postId = 0;
};

BitrixMPF.prototype.Init = function(params)
{
	this.uri = (typeof (params.uri) != 'undefined' ? params.uri : '');
	this.uriSession = (typeof (params.uriSession) != 'undefined' ? params.uriSession : '');
	this.liveFeedId = (typeof (params.liveFeedId) != 'undefined' ? params.liveFeedId : null);
	this.destinationUri = (typeof (params.destinationUri) != 'undefined' ? params.destinationUri : '');
	this.mentionUri = (typeof (params.mentionUri) != 'undefined' ? params.mentionUri : '');
	this.formId = (typeof (params.formId) != 'undefined' ? params.formId : '');
	this.postId = (typeof (params.postId) != 'undefined' ? parseInt(params.postId) : 0);

	var keyboard = BX("newpost-keyboard");
	var controls = BX("newpost-controls");
	var textarea = BX("POST_MESSAGE");
	var postPanel = BX("newpost-panel");

	if (parseInt(this.postId) > 0)
	{
		textarea.value = oMPF.unParseMentions(textarea.value);
	}

	BX.bind(keyboard, 'click', function(e)
	{
		if (!BX.hasClass(this, "newpost-keyboard-pressed"))
		{
			textarea.focus();
		}
	});

	var form = BX(this.formId);
	if (window.platform == "android")
	{
		form.style.height = BX.GetWindowInnerSize().innerHeight + "px";
		form.style.position = "relative";
	}

	BX.addCustomEvent("onKeyboardDidShow", function() 
	{
		if (window.platform == "android")
		{
			BX.hide(keyboard);
			BX.hide(controls);

			var keyboardHeight = parseInt(BX.style(form, "height")) - BX.GetWindowInnerSize().innerHeight;
			postPanel.style.bottom = keyboardHeight + "px";
			textarea.style.bottom = keyboardHeight + postPanel.offsetHeight + "px";
/*
			setTimeout(function() {
				BX.addClass(keyboard, "newpost-keyboard-pressed");
			}, 0);
*/
		}
		else
		{
			BX.addClass(keyboard, "newpost-keyboard-pressed");
		}
	});

	BX.addCustomEvent("onKeyboardDidHide", function() 
	{
		if (window.platform == "android")
		{
			BX.show(keyboard);
			BX.show(controls);

			postPanel.style.cssText = "";
			textarea.style.cssText = "";
/*
			setTimeout(function() {
				BX.removeClass(keyboard, "newpost-keyboard-pressed");
			}, 0);
*/
		}
		else
		{
			BX.removeClass(keyboard, "newpost-keyboard-pressed");
		}
	});
	
	ReadyDevice(function() 
	{
		app.enableCaptureKeyboard(true);

		if (window.platform != "android")
		{
			app.enableScroll(false);
		}
	});

	BX.bind(BX('feed-add-post-destination-button'), 'click', function(e)
	{
		oMPF.openDestinationTable();
		BX.PreventDefault(e);
	});
	
	BX.bind(BX('feed-add-post-mention'), 'click', function(e)
	{
		oMPF.openMentionTable();
		BX.PreventDefault(e)
	});

	app.addButtons({
		sendButton: {
			type: "right_text",
			name: BX.message('MPF_SEND'),
			position: "right",
			style: "custom",
			callback: function()
			{
				var post_id = false;

				if (BX('post_id'))
				{
					post_id = BX('post_id').value;
				}

				var data = {
					'ACTION': (post_id ? 'EDIT_POST' : 'ADD_POST'),
					'AJAX_CALL': 'Y',
					'PUBLISH_STATUS': 'P',
					'is_sent': 'Y',
					'apply': 'Y',
					'sessid': BX.bitrix_sessid(),
					'POST_MESSAGE': oMPF.parseMentions(BX('POST_MESSAGE').value),
					'newpost_photo_counter': BX('newpost_photo_counter').value,
					'decode': 'Y'
				};

				if (post_id)
				{
					data.post_id = post_id;
					if (BX('post_user_id'))
					{
						data.post_user_id = parseInt(BX('post_user_id').value);
					}
				}

				if (BX('log_id'))
				{
					data.log_id = BX('log_id').value;
				}

				var varName = '';

				var arSPermInput = BX.findChildren(
					BX('feed-add-post-destination-container'), 
					{
						'tag': 'input', 
						'attr': {
							'type': 'hidden'
						} 
					}, 
					true
				);
				if (arSPermInput != null)
				{
					for (var i = 0; i < arSPermInput.length; i++)
					{
						varName = arSPermInput[i].name.replace(/[\[\]]{2}$/g,"");
						if (
							typeof data[varName] == 'undefined' 
							|| data[varName] == null
						)
						{
							data[varName] = [];
						}
						data[varName][data[varName].length] = arSPermInput[i].value;
					}
				}

				var arSPermInputHidden = BX.findChildren(
					BX('feed-add-post-destination-hidden-container'), 
					{
						'tag': 'input', 
						'attr': 
						{
							'type': 'hidden'
						} 
					}, 
					true
				);
				if (arSPermInputHidden != null)
				{
					for (var i = 0; i < arSPermInputHidden.length; i++)
					{
						varName = arSPermInputHidden[i].name.replace(/[\[\]]{2}$/g,"");
						if (data[varName] == 'undefined' || data[varName] == null)
							data[varName] = [];
						data[varName][data[varName].length] = arSPermInputHidden[i].value;
					}
				}						

				var arAttachedFile = BX.findChildren(BX('mfu_file_container'), {'tag': 'input', 'attr': {'type': 'hidden'} }, true);
				if (arAttachedFile != null)
				{
					for (var j = 0; j < arAttachedFile.length; j++)
					{
						varName = arAttachedFile[j].name.replace(/[\[\]]{2}$/g,"");
						if (data[varName] == 'undefined' || data[varName] == null)
							data[varName] = [];
						data[varName][data[varName].length] = arAttachedFile[j].value;
					}
				}

				if (BX('POST_MESSAGE').value.length > 0)
				{
					app.onCustomEvent('onMPFSent', {
						data: data,
						LiveFeedID: oMPF.liveFeedId
					});

					if (post_id)
					{
						app.onCustomEvent('onMPFSentEditStart', {} );
					}
					app.closeModalDialog({});
				}
			}
		},
		cancelButton: {
			type: "right_text",
			name: BX.message('MPF_CANCEL'),
			position: "left",
			style: "custom",
			action: "DISMISS"
		}
	});
}

BitrixMPF.prototype.takePhoto = function(params)
{
	var type = (typeof params.type != 'undefined' && params.type == 'camera' ? 'camera' : 'gallery');

	app.takePhoto({
		source: (type == 'camera' ? 1 : 0),
		correctOrientation: (type == 'camera' ? true : false),
		targetWidth: 1000,
		targetHeight: 1000,
		callback: function(fileURI)
		{
			var loading_id = oMPF.showProgressBar();

			function win(r)
			{
				if (decodeURIComponent(r.response) == '{"status":"failed"}')
				{
					fail_1try();
				}
				else
				{
					oMPF.hideProgressBar(loading_id);
					if (r.response.indexOf('file_id:') === 0)
					{
						__MFUCallback({ 'fileID': parseInt(r.response.substr(8)) }, loading_id);
					}
					else if (r.response.indexOf('element_id:') === 0)
					{
						__MFUCallback({ 'elementID': parseInt(r.response.substr(11)) }, loading_id);
					}
					else if (r.response.indexOf('disk_id:') === 0)
					{
						__MFUCallback({ 'diskID': parseInt(r.response.substr(8)) }, loading_id);
					}
				}
			}

			function fail_1try(error)
			{
				app.BasicAuth({
					'success': function(auth_data) 
					{
						var uri = oMPF.uri;
						uri += ((uri.indexOf("?") > 0) ? "&" : "?") + 'sessid=' + auth_data.sessid_md5;

						ft.upload(fileURI, uri, win, fail_2try, options);
					},
					'failture': function() { 
						oMPF.hideProgressBar(loading_id); 
					}
				});
			}					

			function fail_2try(error) 
			{
				oMPF.hideProgressBar(loading_id);
			}

			var options = new FileUploadOptions();
			options.fileKey = "file";
			options.fileName = fileURI.substr(fileURI.lastIndexOf('/') + 1);
			if (options.fileName.indexOf('?') > 0)
				options.fileName = options.fileName.substr(0, options.fileName.indexOf('?'));

			options.mimeType = "image/jpeg";
			var params = {};
			options.params = params;
			options.chunkedMode = false;

			var ft = new FileTransfer();
			var uri = oMPF.uriSession;

			ft.upload(fileURI, uri, win, fail_1try, options);
		}
	});
}

BitrixMPF.prototype.showProgressBar = function()
{
	if (BX('newpost_progressbar_cont'))
	{
		BX('newpost_progressbar_cont').style.display = 'block';

		var loading_id = Math.floor(Math.random() * 100000) + 1;
		this.arLoadingFilesStack[this.arLoadingFilesStack.length] = loading_id;
		this.setProgressBarText();
		clearInterval(this.progressbarId);

		this.progressbarId = BitrixAnimation.animate({
			duration: oMPF.arLoadingFilesStack.length * 5000,
			start: {
				width: parseInt(oMPF.progressbarState / oMPF.arLoadingFilesStack.length) + 10 
			},
			finish: {
				width: 90
			},
			transition: BitrixAnimation.makeEaseOut(BitrixAnimation.transitions.linear),
			step: function(state)
			{
				BX('newpost_progressbar_ind').style.width = state.width + '%';
				oMPF.progressbarState = state.width;
			},
			complete: function() 
			{
				oMPF.progressbarState = 0;
			}
		});
	}

	return loading_id;
}

BitrixMPF.prototype.hideProgressBar = function(loading_id)
{
	var newLoadingFilesStack = [];

	for (var i = 0; i < this.arLoadingFilesStack.length; i++)
	{
		if (this.arLoadingFilesStack[i] != loading_id)
		{
			newLoadingFilesStack[newLoadingFilesStack.length] = this.arLoadingFilesStack[i];
		}
	}

	this.arLoadingFilesStack = newLoadingFilesStack;

	if (this.arLoadingFilesStack.length == 0)
	{
		clearInterval(this.progressbarId);
		this.progressbarState = 0;

		BX('newpost_progressbar_ind').style.width = '100%';
		setTimeout(function() {
			oMPF.setProgressBarText();
			BX('newpost_progressbar_cont').style.display = 'none'; 
		}, 2000);
	}
	else
	{
		this.setProgressBarText();
	}
}

BitrixMPF.prototype.setProgressBarText = function()
{
	if (BX('newpost_progressbar_label'))
	{
		BX('newpost_progressbar_label').innerHTML = (this.arLoadingFilesStack.length <= 0 
			? '' 
			: BX.message('MFULoadingTitle' + (this.arLoadingFilesStack.length == 1 
				? '1' 
				: (this.arLoadingFilesStack.length + ''))
			).replace("#COUNT#", this.arLoadingFilesStack.length)
		);
	}
}

BitrixMPF.prototype.openDestinationTable = function()
{
	var destinations = BX.findChildren(
		BX('feed-add-post-destination-container'), 
		{
			'tag': 'input', 
			'attr': {
				'type': 'hidden', 
				'data-type': 'code'
			} 
		}, 
		true
	);

	var destination_name = false;
	var destination_value = false;
	var arSelectedDestinations = {
		a_users: [], 
		b_groups: [] 
	};

	if (destinations != null)
	{
		for (var j = 0; j < destinations.length; j++)
		{
			destination_name = destinations[j].name;
			destination_value = destinations[j].value;

			if (destination_value == 'UA')
			{
				arSelectedDestinations.a_users[arSelectedDestinations.a_users.length] = 0;
			}
			else if (destination_name.substr(6,1) == 'U')
			{
				arSelectedDestinations.a_users[arSelectedDestinations.a_users.length] = parseInt(destination_value.substr(1));
			}
			else if (destination_name.substr(6,2) == 'SG')
			{
				arSelectedDestinations.b_groups[arSelectedDestinations.b_groups.length] = parseInt(destination_value.substr(2));
			}
		}
	}

	var tableParams = {
		callback: this.callbackDestinationSelect,
		url: this.destinationUri,
		markmode: true,
		multiple: true,
		return_full_mode: true,
		user_all: true,
		showtitle: true,
		modal: true,
		selected: arSelectedDestinations,
		alphabet_index: true,
		okname: BX.message('MPF_TABLE_OK'),
		cancelname: BX.message('MPF_TABLE_CANCEL')
	};

	if (BX.message('DENY_TOALL') == 'Y')
	{
		tableParams.outsection = false;
	}

	app.openTable(tableParams);
}

BitrixMPF.prototype.openMentionTable = function()
{
	app.openTable({
		callback: oMPF.callbackMentionSelect,
		url: oMPF.mentionUri,
		markmode: true,
		multiple: false,
		return_full_mode: true,
		modal: true,
		alphabet_index: true,
		outsection: false,
		okname: BX.message('MPF_TABLE_OK'),
		cancelname: BX.message('MPF_TABLE_CANCEL')
	});
}

BitrixMPF.prototype.callbackDestinationSelect = function(data)
{
	var prefix = '';
	data.users = data.a_users;
	data.groups = data.b_groups;

	if (
		(
			typeof data.users != 'undefined' 
			&& data.users.length > 0
		)
		|| (
			typeof data.groups != 'undefined' 
			&& data.groups.length > 0
		)
	)
	{
		var elements = BX.findChildren(BX('feed-add-post-destination-container'), {
			tagName: 'span', 
			className: 'newpost-addressee'
		}, false);

		if (elements != null)
		{
			for (var j = 0; j < elements.length; j++)
			{
				if (elements[j].getAttribute('data-entity-type') != 'department')
				{
					BX.remove(elements[j]);
				}
			}
		}

		elements = BX.findChildren(BX('feed-add-post-destination-container'), {
			tagName: 'input', 
			'attr': {
				'type': 'hidden'
			}
		}, false);

		if (elements != null)
		{
			for (var j = 0; j < elements.length; j++)
			{
				if (elements[j].getAttribute('data-entity-type') != 'department')
				{
					BX.remove(elements[j]);
				}
			}
		}

		if (
			typeof data.users != 'undefined' 
			&& data.users.length > 0
		)
		{
			for (var j = 0; j < data.users.length; j++)
			{
				prefix = (data.users[j].ID == 0 ? 'UA' : 'U');

				BX('feed-add-post-destination-container').appendChild(
					BX.create("span", { 
						props: {
							className: (data.users[j].ID == 0 ? 'newpost-addressee' : 'newpost-addressee newpost-addressee-people')
						},
						attrs : { 
							'data-id' : 'U' + (data.users[j].ID == 0 ? 'A' : data.users[j].ID),
							'data-entity-type': 'users'
						}, 
						children: [
							BX.create("SPAN", { 
								props: {
									className: 'newpost-addressee-delete'
								},
								events: {
									click: function(event) {
										return oMPF.deleteDestinationItem(event);
									}
								}
							}),
							BX.create("input", { 
								attrs : { 
									'type': 'hidden',
									'data-type': 'code',
									'name': 'SPERM[' + prefix + '][]', 'value' : 'U' + (data.users[j].ID == 0 ? 'A' : data.users[j].ID) 
								}
							}),
							BX.create("input", { 
								attrs : { 
									'type': 'hidden', 
									'name': 'SPERM_NAME[' + prefix + '][]', 'value' : (data.users[j].ID == 0 ? 'UA' : data.users[j].NAME) 
								}
							}),
							BX.create("span", {
								html: data.users[j].NAME
							})
						]
					})
				);
			}
		}

		if (
			typeof data.groups != 'undefined' 
			&& data.groups.length > 0
		)
		{
			prefix = 'SG';
			for (var j = 0; j < data.groups.length; j++)
			{
				BX('feed-add-post-destination-container').appendChild(
					BX.create("span", { 
						props: {
							className: 'newpost-addressee newpost-addressee-group'
						}, 
						attrs : { 
							'data-id' : 'SG' + data.groups[j].ID,
							'data-entity-type': 'sonetgroups'
						}, 
						children: [
							BX.create("SPAN", { 
								props : {
									className: 'newpost-addressee-delete'
								},
								events: {
									click: function(event) {
										return oMPF.deleteDestinationItem(event);
									}
								}
							}),
							BX.create("INPUT", { 
								attrs : { 
									'type': 'hidden', 
									'data-type': 'code', 
									'name': 'SPERM[' + prefix +'][]', 'value' : 'SG' + data.groups[j].ID
								}
							}),
							BX.create("INPUT", { 
								attrs : { 
									'type': 'hidden', 
									'name': 'SPERM_NAME[' + prefix + '][]', 'value' : BX.util.htmlspecialchars(data.groups[j].NAME)
								}
							}),
							BX.create("SPAN", {
								html: BX.util.htmlspecialchars(data.groups[j].NAME)
							})
						]
					})
				);
			}
		}
	}
}

BitrixMPF.prototype.callbackMentionSelect = function(data)
{
	data.users = data.a_users;
	if (
		typeof data.users != 'undefined' 
		&& data.users.length > 0
	)
	{
		var userName = BX.util.htmlspecialcharsback(data.users[0].NAME);
		oMPF.arMention['' + userName + ''] = '[USER=' + data.users[0].ID + ']' + userName + '[/USER]';
		BX('POST_MESSAGE').value += ' ' + userName + ', ';
	}
}

BitrixMPF.prototype.parseMentions = function(text)
{
	var parsedText = text;

	if (typeof oMPF.arMention != 'undefined')
	{
		for (var userName in oMPF.arMention) 
		{
			parsedText = parsedText.replace(new RegExp(userName, 'g'), oMPF.arMention[userName]);
		}
	}

	return parsedText;
}

BitrixMPF.prototype.unParseMentions = function(text)
{
	var unParsedText = text;

	unParsedText = unParsedText.replace(/\[USER\s*=\s*(\d+)\]((?:\s|\S)*?)\[\/USER\]/ig,
		function(str, id, userName)
		{
			oMPF.arMention[userName] = str;
			return userName;
		}
	);

	return unParsedText;
}

BitrixMPF.prototype.initDestination = function(item, type)
{
	BX.cleanNode('feed-add-post-destination-container', false);
	this.addDestinationItem(item, type);
}

BitrixMPF.prototype.initDestinationEx = function(arItems, arItemsHidden)
{
	var prefix = '';
	if (typeof (arItemsHidden) == 'undefined')
	{
		arItemsHidden = [];
	}

	BX.cleanNode('feed-add-post-destination-container', false);
	BX.cleanNode('feed-add-post-destination-hidden-container', false);

	for (var j = 0; j < arItems.length; j++)
	{
		this.addDestinationItem(arItems[j].item, arItems[j].type);
	}
	
	for (var j = 0; j < arItemsHidden.length; j++)
	{
		prefix = this.getDestinationPrefix(arItemsHidden[j].type);	

		BX('feed-add-post-destination-hidden-container').appendChild(
			BX.create("input", { 
				attrs: { 
					'type': 'hidden', 
					'data-type': 'code', 
					'name': 'SPERM[' + prefix + '][]', 'value' : arItemsHidden[j].item.id 
				}
			})
		);
	}
}

BitrixMPF.prototype.addDestinationItem = function(item, type)
{
	var prefix = this.getDestinationPrefix(type);
	var itemClassName = 'newpost-addressee';
	
	if (prefix == 'U')
	{
		itemClassName += ' newpost-addressee-people';
	}
	else if (prefix == 'SG')
	{
		itemClassName += ' newpost-addressee-group';
	}

	BX('feed-add-post-destination-container').appendChild(
		BX.create("SPAN", { 
			props: {
				className: itemClassName
			}, 
			attrs : { 
				'data-id' : item.id, 
				'data-entity-type': type
			}, 
			children: [
				BX.create("SPAN", {
					props: {
						className: 'newpost-addressee-delete'
					},
					events: {
						click: function(event) {
							return oMPF.deleteDestinationItem(event);
						}
					}
				}),
				BX.create("INPUT", { 
					attrs: { 
						'type': 'hidden', 
						'data-type': 'code', 
						'name': 'SPERM[' + prefix + '][]', 'value' : item.id 
					}
				}),
				BX.create("INPUT", { 
					attrs: { 
						'type': 'hidden', 
						'name': 'SPERM_NAME[' + prefix + '][]', 'value' : item.name 
					}
				}),
				BX.create("SPAN", {
					html: item.name
				})
			]
		})
	);
}

BitrixMPF.prototype.deleteDestinationItem = function(event)
{
	event = event||window.event;
	var itemNode = BX.findParent(event.target, { className: 'newpost-addressee' }, BX('feed-add-post-destination-container'));
	if (itemNode)
	{
		BX.cleanNode(itemNode, true);
	}

	return BX.PreventDefault(event);
}

BitrixMPF.prototype.getDestinationPrefix = function(type)
{
	var prefix = 'S';

	if (type == 'sonetgroups')
	{
		prefix = 'SG';
	}
	else if (type == 'groups')
	{
		prefix = 'UA';
	}
	else if (type == 'users')
	{
		prefix = 'U';
	}
	else if (type == 'department')
	{
		prefix = 'DR';
	}

	return prefix;
}

oMPF = new BitrixMPF;
window.oMPF = oMPF;