BitrixMPFComment = function ()
{
	this.mentionUri = '';
	this.formId = '';
	this.arMention = [];
	this.commentId = 0;
	this.detailPageId = '';
	this.nodeId = '';
};

BitrixMPFComment.prototype.Init = function(params)
{
	this.mentionUri = (typeof (params.mentionUri) != 'undefined' ? params.mentionUri : '');
	this.formId = (typeof (params.formId) != 'undefined' ? params.formId : '');
	this.commentId = (typeof (params.commentId) != 'undefined' ? parseInt(params.commentId) : 0);
	this.detailPageId = (typeof (params.detailPageId) != 'undefined' ? params.detailPageId : '');
	this.nodeId = (typeof (params.nodeId) != 'undefined' ? params.nodeId : '');

	var textarea = BX("COMMENT_TEXT");
	var postPanel = BX("newpost-panel");

	textarea.value = oMPFComment.unParseMentions(textarea.value);

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
			var keyboardHeight = parseInt(BX.style(form, "height")) - BX.GetWindowInnerSize().innerHeight;
			postPanel.style.bottom = keyboardHeight + "px";
			textarea.style.bottom = keyboardHeight + postPanel.offsetHeight + "px";
		}
	});

	BX.addCustomEvent("onKeyboardDidHide", function() 
	{
		if (window.platform == "android")
		{
			postPanel.style.cssText = "";
			textarea.style.cssText = "";
		}
	});
	
	ReadyDevice(function() 
	{
		app.enableCaptureKeyboard(true);

		if (window.platform != "android")
		{
			app.enableScroll(false);
		}

		textarea.focus();
	});

	BX.bind(BX('feed-add-post-mention'), 'click', function(e)
	{
		oMPFComment.openMentionTable();
		BX.PreventDefault(e)
	});

	app.addButtons({
		sendButton: {
			type: "right_text",
			name: BX.message('MPF_COMMENT_SEND'),
			position: "right",
			style: "custom",
			callback: function()
			{
				if (BX('COMMENT_TEXT').value.length > 0)
				{
					var data = {
						'action': 'EDIT_COMMENT',
						'sessid': BX.bitrix_sessid(),
						'commentId': oMPFComment.commentId,
						'text': oMPFComment.parseMentions(BX('COMMENT_TEXT').value),
						'decode': 'Y'
					};

					app.onCustomEvent('onMPFCommentSent', { 
						'data': data, 
						'detailPageId': oMPFComment.detailPageId,
						'nodeId': oMPFComment.nodeId
					});

					app.closeModalDialog({});
				}
			}
		},
		cancelButton: {
			type: "right_text",
			name: BX.message('MPF_COMMENT_CANCEL'),
			position: "left",
			style: "custom",
			action: "DISMISS"
		}
	});
}

BitrixMPFComment.prototype.openMentionTable = function()
{
	app.openTable({
		callback: oMPFComment.callbackMentionSelect,
		url: oMPFComment.mentionUri,
		markmode: true,
		multiple: false,
		return_full_mode: true,
		modal: true,
		alphabet_index: true,
		outsection: false,
		okname: BX.message('MPF_COMMENT_TABLE_OK'),
		cancelname: BX.message('MPF_COMMENT_TABLE_CANCEL')
	});
}

BitrixMPFComment.prototype.callbackMentionSelect = function(data)
{
	data.users = data.a_users;
	if (
		typeof data.users != 'undefined' 
		&& data.users.length > 0
	)
	{
		var userName = BX.util.htmlspecialcharsback(data.users[0].NAME);
		oMPFComment.arMention['' + userName + ''] = '[USER=' + data.users[0].ID + ']' + userName + '[/USER]';
		BX('COMMENT_TEXT').value += ' ' + userName + ', ';
	}
}

BitrixMPFComment.prototype.parseMentions = function(text)
{
	var parsedText = text;

	if (typeof oMPFComment.arMention != 'undefined')
	{
		for (var userName in oMPFComment.arMention) 
		{
			parsedText = parsedText.replace(new RegExp(userName, 'g'), oMPFComment.arMention[userName]);
		}
	}

	return parsedText;
}

BitrixMPFComment.prototype.unParseMentions = function(text)
{
	var unParsedText = text;

	unParsedText = unParsedText.replace(/\[USER\s*=\s*(\d+)\]((?:\s|\S)*?)\[\/USER\]/ig,
		function(str, id, userName)
		{
			oMPFComment.arMention[userName] = str;
			return userName;
		}
	);

	return unParsedText;
}

oMPFComment = new BitrixMPFComment;
window.oMPFComment = oMPFComment;