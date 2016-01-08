;(function(){
	if (BX.Forum && BX.Forum.transliterate)
		return;
	BX.Forum = (BX.Forum ? BX.Forum : {});
	var repo = {};

	BX.Forum.transliterate = function(node)
	{
		node.onblur = function(){ clearInterval(node.bxfInterval); };
		node.bxfInterval = setInterval(function(){
			if (node.value != node.bxValue)
			{
				node.bxValue = node.value;
				BX.translit(node.value, {
					'max_len' : 70,
					'change_case' : 'L',
					'replace_space' : '-',
					'replace_other' : '',
					'delete_repeat_replace' : true,
					'use_google' : true,
					'callback' : function(result){ node.nextSibling.value = result; }
				});
			}
		}, 500);
	};
	/**
	 * @return boolean
	 */
	BX.Forum.AddTags = function(a)
	{
		if (a && a.parentNode)
		{
			var
				div = a.parentNode.parentNode.previousSibling,
				switcher = a.parentNode.parentNode;
			BX.show(div);
			BX.remove(a.parentNode);
			if (switcher.innerHTML === '')
				BX.remove(switcher);

			var inputs = div.getElementsByTagName("INPUT");
			for (var i = 0 ; i < inputs.length ; i++ )
			{
				if (inputs[i].type.toUpperCase() == "TEXT")
				{
					BX.Forum.CorrectTags(inputs[i]);
					inputs[i].focus();
					break;
				}
			}
		}
		return false;
	};

	BX.Forum.CorrectTags = function(oObj)
	{
		if (BX('TAGS_div_frame'))
			BX('TAGS_div_frame').id = oObj.id + "_div_frame";
	};


	var
		fTextToNode = function (text)
		{
			var tmpdiv = BX.create('div');
			tmpdiv.innerHTML = text;
			if (tmpdiv.childNodes.length > 0)
				return tmpdiv.childNodes[0];
			else
				return null;
		},
		PostFormAjaxStatus = function (status)
		{
			var arNote = BX.findChild(document, { className : 'forum-note-box'} , true, true), i;
			if (arNote)
			{
				for (i = 0; i < arNote.length; i++)
				{
					BX.remove(arNote[i]);
				}
			}

			var arMsgBox = BX.findChildren(document, { className : 'forum-block-container' } , true);
			if (!arMsgBox || arMsgBox.length < 1) return;
			var msgBox = arMsgBox[arMsgBox.length - 1];

			if (status.length < 1) return;

			var statusDIV = fTextToNode(status);
			if (!statusDIV) return;

			var beforeDivs = [ 'forum-info-box', 'forum-header-box', 'forum-reply-form' ];
			var tmp = msgBox;
			while ((tmp = tmp.nextSibling) && !!tmp)
			{
				if (tmp.nodeType == 1)
				{
					var insert = false;
					for (i in beforeDivs)
					{
						if (beforeDivs.hasOwnProperty(i) && BX.hasClass(tmp, beforeDivs[i]))
						{
							insert = true;
							break;
						}
					}
					if (insert)
					{
						tmp.parentNode.insertBefore(statusDIV, tmp);
						break;
					}
				}
			}
		},
		PostFormAjaxNavigation = function(navString, pageNumber)
		{
			var navDIV = fTextToNode(navString), i;
			if (!navDIV) return;
			var navPlaceholders = BX.findChildren(document, { className : 'forum-navigation-box' } , true);
			if (!navPlaceholders) return;
			for (i = 0; i < navPlaceholders.length; i++)
				navPlaceholders[i].innerHTML = navDIV.innerHTML;
			window["oForum"]["page_number"] = pageNumber;
		},
		PostFormAjaxMsgStart = function(msg)
		{
			var msgNode = fTextToNode(msg);
			if (!msgNode) return;
			var navPlaceholder = BX.findChild(document, { className : 'forum-navigation-box' }, true);
			if (!navPlaceholder) return;
			navPlaceholder.parentNode.insertBefore(msgNode, navPlaceholder);
		},
		fReplaceOrInsertNode = function(sourceNode, targetNode, parentTargetNode, beforeTargetNode)
		{
			var nextNode = null;

			if (!BX.type.isDomNode(parentTargetNode)) return false;

			if (!BX.type.isDomNode(sourceNode) && !BX.type.isArray(sourceNode) && sourceNode.length > 0)
				if (! (sourceNode = fTextToNode(sourceNode))) return false;

			if (BX.type.isDomNode(targetNode)) // replace
			{
				nextNode = targetNode.nextSibling;
				targetNode.parentNode.removeChild(targetNode);
			}

			if (!nextNode)
				nextNode = BX.findChild(parentTargetNode, beforeTargetNode, true);

			if (nextNode)
			{
				nextNode.parentNode.insertBefore(sourceNode, nextNode);
			} else {
				parentTargetNode.appendChild(sourceNode);
			}

			return true;
		},
		fRunScripts = function(msg)
		{
			var ob = BX.processHTML(msg, true);
			BX.ajax.processScripts(ob.SCRIPT, true);
		},
		PostFormAjaxResponse = function(response, postform)
		{
			postform['BXFormSubmit_save'] = null;
			var result = window.forumAjaxPostTmp;
			if (typeof result == 'undefined')
			{
				BX.reload();
				return;
			}

			var arForumlist = BX.findChildren(document, {className: 'forum-block-inner'}, true);
			if (! arForumlist || arForumlist.length <1)
				BX.reload();
			var node, forumlist = arForumlist[arForumlist.length-1];
//			forumlist = (!!formlist ? formlist : forumlist);

			if (result.status)
			{
				if (result["allMessages"])
				{
					if (! result.message) return;

					var listparent = forumlist.parentNode;
					BX.remove(forumlist);
					listparent.innerHTML += result.message;

					if (!!result.navigation && !!result.pageNumber)
					{
						PostFormAjaxNavigation(result.navigation, result.pageNumber);
					}
					if (!!result["messageStart"])
					{
						PostFormAjaxMsgStart(result["messageStart"]);
					}
					ClearForumPostForm(postform);
					fRunScripts(result.message);
				}
				else if (typeof result.message != 'undefined')
				{
					var allMessages = BX.findChildren(forumlist, {tagName: 'table', className: 'forum-post-table'}, true);
					if (allMessages.length > 0)
					{
						var lastMessage = allMessages[allMessages.length - 1],
							footerActions = BX.findChild(lastMessage, { tagName : 'tfoot' }, true);
						if (footerActions)
							BX.remove(footerActions);
					}
					forumlist.innerHTML += result.message;
					ClearForumPostForm(postform);
					fRunScripts(result.message);
				}
				else if (result["previewMessage"])
				{
					var previewDIV = BX.findChild(document, {className: 'forum-preview'}, true),
						previewParent = BX.findChild(document, {className : 'forum_post_form'}, true).parentNode;
					fReplaceOrInsertNode(result["previewMessage"], previewDIV, previewParent, {className : 'forum_post_form'});

					PostFormAjaxStatus('');
					fRunScripts(result["previewMessage"]);
				}

				if (!!result["messageID"])
					if ((node = BX('message'+result["messageID"])) && !!node)
						BX.scrollToNode(node);
			}

			var arr = postform.getElementsByTagName("input");
			for (var i=0; i < arr.length; i++)
			{
				var butt = arr[i];
				if (butt.getAttribute("type") == "submit")
					butt.disabled = false;
			}

			BX.remove(BX.findChild(postform, { 'attr' : { 'name' : 'pageNumber' }}, true));

			if (result["statusMessage"])
				PostFormAjaxStatus(result["statusMessage"]);
		},
		ClearForumPostForm = function(form)
		{
			window.LHEPostForm.reinitDataBefore('POST_MESSAGE');
			var editor = LHEPostForm.getEditor('POST_MESSAGE'), node, handler = LHEPostForm.getHandler('POST_MESSAGE');
			if (editor)
			{
				editor.CheckAndReInit('');
				for (var i in handler.arFiles)
				{
					if (handler.arFiles.hasOwnProperty(i))
					{
						if ((node = BX('file-doc'+handler.arFiles[i]["id"])) && !!node)
						{
							BX.remove(node);
							BX.hide(BX('wd-doc'+handler.arFiles[i]["id"]));
							BX.remove(BX('filetoupload' + handler.arFiles[i]["id"]));
						}
					}
				}
			}

			if (!BX.type.isDomNode(form)) return;

			if ((node = BX.findChild(document, {'className' : 'forum-preview'}, true)) && !!node)
				BX.remove(node);

			var attachNodes = BX.findChild(form, {'tagName' : 'TR', 'className':"error-load"}, true, true),
				attachNode = null;
			if (attachNodes)
				while ((attachNode = attachNodes.pop()) && !!attachNode)
					BX.hide(attachNode);

			var captchaIMAGE = null,
				captchaHIDDEN = BX.findChild(form, {attr : {'name': 'captcha_code'}}, true),
				captchaINPUT = BX.findChild(form, {attr: {'name':'captcha_word'}}, true),
				captchaDIV = BX.findChild(form, {'className':'forum-reply-field-captcha-image'}, true);

			if (captchaDIV)
				captchaIMAGE = BX.findChild(captchaDIV, {'tag':'img'});
			if (captchaHIDDEN && captchaINPUT && captchaIMAGE)
			{
				captchaINPUT.value = '';
				BX.ajax.getCaptcha(function(result) {
					captchaHIDDEN.value = result["captcha_sid"];
					captchaIMAGE.src = '/bitrix/tools/captcha.php?captcha_code='+result["captcha_sid"];
				});
			}
		};

	BX.Forum.SetForumAjaxPostTmp = function(text)
	{
		window.forumAjaxPostTmp = text;
	};
	/**
	 * @return {boolean}
	 */
	BX.Forum.ValidateForm = function(form, ajax_post)
	{
		if (form['BXFormSubmit_save']) return true; // ValidateForm may be run by BX.submit one more time
		var editor = (window["BXHtmlEditor"] ? window["BXHtmlEditor"].Get('POST_MESSAGE') : false);
		if (typeof form != "object" || !form["POST_MESSAGE"] || !editor)
			return false;
		if (typeof window["oForum"] == 'undefined')
			window["oForum"] = {};
		editor.SaveContent();
		var
			errors = "",
			Message = editor.GetContent(),
			MessageLength = Message.length,
			MessageMax = 64000;
		if (form.TITLE && (form.TITLE.value.length <= 0 ))
			errors += BX.message('no_topic_name');
		if (MessageLength <= 0)
			errors += BX.message('no_message');
		else if (MessageLength > MessageMax)
			errors += BX.message('max_len').replace(/#MAX_LENGTH#/gi, MessageMax).replace(/#LENGTH#/gi, MessageLength);

		if (errors !== "")
		{
			alert(errors);
			return false;
		}

		if (form['FILES[]'])
		{
			var
				oEls = [],
				oEl = BX.type.isDomNode(form['FILES[]']) ? form['FILES[]'] : form['FILES[]'][0],
				ii = BX.type.isDomNode(form['FILES[]']) ? false : 0;
			do
			{
				if (! BX('filetoupload' + oEl.value))
				{
					oEls.push(
						BX.adjust(
							BX.clone(oEl),
							{attrs : {name : 'FILES_TO_UPLOAD[]', id : ('filetoupload' + oEl.value)}}
						)
					);
				}
				oEl = (ii === false ? false : (ii <  form['FILES[]'].length ? form['FILES[]'][ii++] : false));
			} while (!!oEl);
			while (oEls.length > 0)
				form.appendChild(oEls.pop());
		}

		var arr = form.getElementsByTagName("input");
		for (var i=0; i < arr.length; i++)
		{
			var butt = arr[i];
			if (butt.getAttribute("type") == "submit")
				butt.disabled = true;
		}

		if (ajax_post == 'Y')
		{
			var postform = form;
			if (typeof window["oForum"] != 'undefined' && typeof window["oForum"]["page_number"] != 'undefined')
			{
				var pageNumberInput = BX.findChild(postform, {attr : {name : 'pageNumber'}});
				if (!pageNumberInput)
				{
					pageNumberInput = BX.create("input", {props : {type : "hidden", name : 'pageNumber'}});
					pageNumberInput.value = window["oForum"]["page_number"];
					postform.appendChild(pageNumberInput);
				} else {
					pageNumberInput.value = window["oForum"]["page_number"];
				}
			}
			setTimeout(function() { BX.ajax.submit(postform, function(response) {PostFormAjaxResponse(response, postform);}); }, 50);
			return false;
		}
		return true;
	};

	BX.Forum.ShowLastEditReason = function (checked, div)
	{
		if (div && checked)
			BX.show(div);
		else if (div)
			BX.hide(div);
	};
	/**
	 * @return boolean;
	 */
	BX.Forum.ShowVote = function(oObj)
	{
		var switcher = oObj.parentNode.parentNode;
		BX.remove(oObj.parentNode);
		if (switcher.innerHTML === '')
			BX.remove(switcher);
		BX.show(BX('vote_params'));
		return false;
	};
	window.vote_remove_answer = function(obj)
	{
		if (typeof obj != "object" || obj === null)
			return false;
		vote_add_answer(obj.parentNode.parentNode.parentNode, true);
		var
			answer = obj.parentNode.parentNode.firstChild,
			regexp = /ANS_(\d+)__(\d+)_/i,
			number = regexp.exec(answer.parentNode.id),
			q = parseInt(number[1]),
			a = parseInt(number[2]);
		if (answer.value !== '' && !confirm(BX.message('vote_drop_answer_confirm')))
			return false;

		if (answer.form['ANSWER_DEL[' + q + '][' + a+ ']'])
			answer.form['ANSWER_DEL[' + q + '][' + a+ ']'].value = "Y";

		answer.parentNode.parentNode.removeChild(answer.parentNode);
		return false;
	};
	/**
	 * @return boolean
	 */
	window.vote_add_answer = function(obj, bFromRemoveAnswerFunction)
	{
		if (!obj || typeof obj != "object")
			return false;
		var
			ol = (bFromRemoveAnswerFunction !== true ? obj.parentNode.parentNode : obj),
			regexp = ol.lastChild.previousSibling ? /ANS_(\d+)__(\d+)_/i : /addA(\d+)/i,
			number = regexp.exec(ol.lastChild.previousSibling ? ol.lastChild.previousSibling.id : obj.name),
			q = parseInt(number[1]),
			a = parseInt(number[2]);
		if (!window["__fqan" + q])
			window["__fqan" + q] = a + 1;
		if (bFromRemoveAnswerFunction !== true)
		{
			a = window["__fqan" + q]++;
			var answer = BX.create('DIV', {'html' : window["arVoteParams"]['template_answer'].replace(/#Q#/g, q).replace(/#A#/g, a)});
			ol.insertBefore(answer.firstChild, ol.lastChild);
		}
		return false;
	};
	/**
	 * @return boolean
	 */
	window.vote_remove_question = function(anchor)
	{
		if (typeof anchor != "object" || anchor === null)
			return false;
		var
			question = anchor.parentNode.previousSibling,
			q = parseInt(question.id.replace("QUESTION_", ""));
		if (question.value !== '' && !confirm(BX.message('vote_drop_question_confirm')))
			return false;
		if (question.form['QUESTION_DEL[' + q + ']'])
			question.form['QUESTION_DEL[' + q + ']'].value = "Y";
		question.parentNode.parentNode.parentNode.removeChild(question.parentNode.parentNode);
		return false;
	};
	/**
	 * @return boolean
	 */
	window.vote_add_question = function(oObj, iQuestion)
	{
		if (!window["__fqn"])
			window["__fqn"] = parseInt(iQuestion) + 1;
		iQuestion = window["__fqn"]++;

		var question = BX.create('DIV', {'html' : window["arVoteParams"]['template_question'].replace(/#Q#/g, iQuestion)});
		oObj.parentNode.insertBefore(question.firstChild, oObj);
		return false;
	};

	window.quoteMessageEx = function(mid)
	{
		var editor = (window["BXHtmlEditor"] ? window["BXHtmlEditor"].Get('POST_MESSAGE') : false), selection = "";
		if (!(editor && editor.toolbar.controls.Quote))
			return false;

		var range = editor.selection.GetRange(editor.selection.GetSelection(document));
		if (range && !range.collapsed)
		{
			var tmpDiv = BX.create('DIV', {html: range.toHtml()});
			editor.GetIframeDoc();
			selection = editor.util.GetTextContentEx(tmpDiv);
			BX.remove(tmpDiv);
		}
		if (selection !== "")
			BX.DoNothing();
		else if (mid > 0)
			selection = (BX(('message_text_' + mid), true) ? BX(('message_text_' + mid), true).innerHTML : '');
		else if (mid.length > 0)
			selection = mid;

		selection = selection.replace(/[\n|\r]*<br(\s)*(\/)*>/gi, "\n");

		if (selection !== "")
		{
			// Video
			var videoWMV = function(str, p1)
			{
				var result = ' ',
					rWmv = /showWMVPlayer.*?bx_wmv_player.*?file:[\s'"]*([^"']*).*?width:[\s'"]*([^"']*).*?height:[\s'"]*([^'"]*).*?/gi,
					res = rWmv.exec(p1);
				if (res)
					result = "[VIDEO WIDTH="+res[2]+" HEIGHT="+res[3]+"]"+res[1]+"[/VIDEO]";
				if (result == ' ')
				{
					var rFlv = /bxPlayerOnload[\s\S]*?[\s'"]*file[\s'"]*:[\s'"]*([^"']*)[\s\S]*?[\s'"]*height[\s'"]*:[\s'"]*([^"']*)[\s\S]*?[\s'"]*width[\s'"]*:[\s'"]*([^"']*)/gi;
					res = rFlv.exec(p1);
					if (res)
						result = "[VIDEO WIDTH="+res[3]+" HEIGHT="+res[2]+"]"+res[1]+"[/VIDEO]";
				}
				return result;
			};

			selection = selection.replace(/<script[^>]*>/gi, '\001').replace(/<\/script[^>]*>/gi, '\002');
			selection = selection.replace(/\001([^\002]*)\002/gi, videoWMV);
			selection = selection.replace(/<noscript[^>]*>/gi, '\003').replace(/<\/noscript[^>]*>/gi, '\004');
			selection = selection.replace(/\003([^\004]*)\004/gi, " ");

			// Quote & Code & Table
			selection = selection.replace(/<table class=["]*forum-quote["]*>[^<]*<thead>[^<]*<tr>[^<]*<th>([^<]+)<\/th><\/tr><\/thead>[^<]*<tbody>[^<]*<tr>[^<]*<td>/gi, "\001");
			selection = selection.replace(/<table class=["]*forum-code["]*>[^<]*<thead>[^<]*<tr>[^<]*<th>([^<]+)<\/th><\/tr><\/thead>[^<]*<tbody>[^<]*<tr>[^<]*<td>/gi, "\002");
			selection = selection.replace(/<table class=["]*data-table["]*>[^<]*<tbody>/gi, "\004");
			selection = selection.replace(/<\/td>[^<]*<\/tr>(<\/tbody>)*<\/table>/gi, "\003");
			selection = selection.replace(/[\r|\n]{2,}([\001|\002])/gi, "\n$1");

			var ii = 0;
			while(ii++ < 50 && (selection.search(/\002([^\002\003]*)\003/gi) >= 0 || selection.search(/\001([^\001\003]*)\003/gi) >= 0))
			{
				selection = selection.replace(/\002([^\002\003]*)\003/gi, "[CODE]$1[/CODE]").replace(/\001([^\001\003]*)\003/gi, "[QUOTE]$1[/QUOTE]");
			}

			var regexReplaceTableTag = function(s, tag, replacement)
			{
				var re_match = new RegExp("\004([^\004\003]*)("+tag+")([^\004\003]*)\003", "i");
				var re_replace = new RegExp("((?:\004)(?:[^\004\003]*))("+tag+")((?:[^\004\003]*)(?:\003))", "i");
				var ij = 0;
				while((ij++ < 300) && (s.search(re_match) >= 0))
					s = s.replace(re_replace, "$1"+replacement+"$3");
				return s;
			};

			ii = 0;
			while(ii++ < 10 && (selection.search(/\004([^\004\003]*)\003/gi) >= 0))
			{
				selection = regexReplaceTableTag(selection, "<tr>", "[TR]");
				selection = regexReplaceTableTag(selection, "<\/tr>", "[/TR]");
				selection = regexReplaceTableTag(selection, "<td>", "[TD]");
				selection = regexReplaceTableTag(selection, "<\/td>", "[/TD]");
				selection = selection.replace(/\004([^\004\003]*)\003/gi, "[TABLE]$1[/TD][/TR][/TABLE]");
			}

			selection = selection.replace(/[\001\002\003\004]/gi, "");

			// Smiles
			if (BX.browser.IsIE())
				selection = selection.replace(/<img(?:(?:\s+alt\s*=\s*"?smile([^"\s]+)"?)|(?:\s+\w+\s*=\s*[^\s>]*))*>/gi, "$1");
			else
				selection = selection.replace(/<img(.*?)alt=["]*smile([^"\s]+)["]*[^>]*>/gi, "$2");

			selection = selection.replace(/<img(.+?)data-code="(.+?)"(.+?)>/gi, "$2");

			// Hrefs
			selection = selection.replace(/<a[^>]+href=["]([^"]+)"[^>]+>([^<]+)<\/a>/gi, "[URL=$1]$2[/URL]").
				replace(/<a[^>]+href=[']([^']+)'[^>]+>([^<]+)<\/a>/gi, "[URL=$1]$2[/URL]").
				replace(/<[^>]+>/gi, " ").replace(/&lt;/gi, "<").replace(/&gt;/gi, ">").replace(/&quot;/gi, "\"").
				replace(/(smile(?=[:;8]))/g, "").
				replace(/&shy;/gi, "").
				replace(/&nbsp;/gi, " ");

			if (!!editor && !!selection)
			{
				var author;
				if (mid > 0) {
					if (BX(('message_block_' + mid), true) && BX(('message_block_' + mid), true).hasAttribute("bx-author-name")) {
						author = {
							name : BX(('message_block_' + mid), true).getAttribute("bx-author-name"),
							id : BX(('message_block_' + mid), true).getAttribute("bx-author-id")
						}
					}
				}

				if (editor.GetViewMode() == 'code' && editor.bbCode)  // BB Codes
				{
					if (!author)
						author = '';
					else if (author.id > 0)
						author = "[USER=" + author.id + "]" + author.name + "[/USER]";
					else
						author = author.name;
					author = (author !== '' ? (author + BX.message("MPL_HAVE_WRITTEN") + '\n') : '');
					selection = author + selection;
				}
				else if (editor.GetViewMode() == 'wysiwyg') // WYSIWYG
				{
					if (!author)
						author = '';
					else if (author.id > 0)
						author = '<span id="' + editor.SetBxTag(false, {'tag': "postuser", 'params': {'value' : author.id}}) +
							'" style="color: #2067B0; border-bottom: 1px dashed #2067B0;">' + author.name.replace(/</gi, '&lt;').replace(/>/gi, '&gt;') + '</span>';
					else
						author = '<span>' + author.name.replace(/</gi, '&lt;').replace(/>/gi, '&gt;') + '</span>';
					selection = (author !== '' ? (author + BX.message("MPL_HAVE_WRITTEN") + '<br>') : '') + editor.ParseContentFromBbCode(selection);
				}

				editor.action.actions.quote.setExternalSelection(selection);
				editor.action.Exec('quote');
			}
		}
		return false;
	};
	/**
	 * @return boolean
	 */
	window.reply2author = function(mid)
	{
		var author = '';
		if (mid > 0 && BX(('message_block_' + mid), true) && BX(('message_block_' + mid), true).hasAttribute("bx-author-name")) {
			author = {
				name : BX(('message_block_' + mid), true).getAttribute("bx-author-name"),
				id : BX(('message_block_' + mid), true).getAttribute("bx-author-id")
			}
		}
		var editor = (window["BXHtmlEditor"] ? window["BXHtmlEditor"].Get('POST_MESSAGE') : false);
		if (!!editor && !!author) {
			if (editor.GetViewMode() == 'code' && editor.bbCode)  // BB Codes
			{
				author = (author.id > 0 ? "[USER=" + author.id + "]" + author.name + "[/USER]" : author.name);
				editor.textareaView.WrapWith("", ", ", author);
			}
			else if (editor.GetViewMode() == 'wysiwyg') // WYSIWYG
			{
				author = (author.id > 0 ?
					('<span id="' + editor.SetBxTag(false, {'tag': "postuser", 'params': {'value' : author.id}}) +
						'" style="color: #2067B0; border-bottom: 1px dashed #2067B0;">' +
						author.name.replace(/</gi, '&lt;').replace(/>/gi, '&gt;') + '</span>'
					) : ('<span>' + author.name.replace(/</gi, '&lt;').replace(/>/gi, '&gt;') + '</span>'));
				editor.InsertHtml(author + ', ');
			}
			editor.Focus();
			BX.defer(editor.Focus, editor)();
		}
		return false;
	};
	BX.addCustomEvent(window, 'OnEditorInitedBefore', function(editor)
	{
		if (editor.id !== "POST_MESSAGE" || BX.message('LANGUAGE_ID') !== 'ru')
			return
		editor.AddButton({
			id : 'translit',
			name : 'Translit',
			iconClassName : 'bxhtmled-button-translit',
			disabledForTextarea : false,
			toolbarSort : 205,
			handler : function()
			{
				var translit = function(textbody)
					{
						if (typeof editor.bTranslited == 'undefined')
							editor.bTranslited = false;

						var arStack = [], i = 0;

						function bPushTag(str, p1/*, offset, s*/)
						{
							arStack.push(p1);
							return "\001";
						}

						function bPopTag(/*str, p1, offset, s*/)
						{
							return arStack.shift();
						}

						var r = new RegExp("(\\[[^\\]]*\\])", 'gi');
						textbody = textbody.replace(r, bPushTag);

						if ( editor.bTranslited == false)
						{
							for (i=0; i<capitEngLettersReg.length; i++) textbody = textbody.replace(capitEngLettersReg[i], capitRusLetters[i]);
							for (i=0; i<smallEngLettersReg.length; i++) textbody = textbody.replace(smallEngLettersReg[i], smallRusLetters[i]);
							editor.bTranslited = true;
						}
						else
						{
							for (i=0; i<capitRusLetters.length; i++) textbody = textbody.replace(capitRusLettersReg[i], capitEngLetters[i]);
							for (i=0; i<smallRusLetters.length; i++) textbody = textbody.replace(smallRusLettersReg[i], smallEngLetters[i]);
							editor.bTranslited = false;
						}

						textbody = textbody.replace(new RegExp("\001", "g"), bPopTag);

						return textbody;
					};

				editor.SaveContent();
				var content = translit(editor.GetContent());
				BX.defer(function()
				{
					editor.SetContent(content);
				})();
			}
		});
	});
	BX.addCustomEvent(window, 'OnEditorInitedAfter', function(editor)
	{
		if (!repo[editor.id])
			return;
		var params = repo[editor.id];

		editor.insertImageAfterUpload = true;

		BX.bind(BX('post_message_hidden'), "focus", function(){ editor.Focus();} );
		var formID = params["formID"],
			form = document.forms[formID],
			__ctrl_enter = function(e, bNeedSubmit)
			{
				if (!BX.Forum.ValidateForm(form, params['ajaxPost']))
				{
					if (e) BX.PreventDefault(e);
					return false;
				}
				if (bNeedSubmit !== false)
					BX.submit(form);
				return true;
			};
		BX.bind(form, "submit", function(e){__ctrl_enter(e, false);});
		BX.addCustomEvent(editor, 'OnCtrlEnter', __ctrl_enter);
		if (params["captcha"] == "Y")
		{
			var captchaParams = {
				'image' : null,
				'hidden' : BX.findChild(form, {attr : {'name': 'captcha_code'}}, true),
				'input' : BX.findChild(form, {attr: {'name':'captcha_word'}}, true),
				'div' : BX.findChild(form, {'className':'forum-reply-field-captcha'}, true)
			};
			if (captchaParams.div)
				captchaParams.image = BX.findChild(captchaParams.div, {'tag':'img'}, true);
			var oCaptcha = new Captcha(captchaParams);
			setTimeout(function() {
				BX.bind(BX('forum-refresh-captcha'), 'click', BX.proxy(oCaptcha.Update, oCaptcha));
			}, 200);
			if (params["bVarsFromForm"] == "Y")
				oCaptcha.Show();
		}
		if (params["autosave"] == "Y")
			new AutoSave(params);
	});
	BX.Forum.Init = function(editorId, params)
	{
		if (params)
			repo[editorId] = params;
	};

	/**
	 * @return boolean
	 */
	var Captcha = function(params)
	{
		if (params == null)
			return false;
		this.div = params.div || null;
		this.input = params.input || null;
		this.image = params.image || null;
		this.hidden = params.hidden || null;
		if ( ! (
			this.div &&
			this.input &&
			this.image &&
			this.hidden
		)) return false;

		setTimeout(BX.proxy(this.Bind, this), 200);
		return true;
	};
	Captcha.prototype = {
		BindEvents : function()
		{
			var editor = LHEPostForm.getEditor('POST_MESSAGE');
			BX.bind(LHEPostForm.getEditor('POST_MESSAGE'), 'OnContentChanged', BX.proxy(this.Show, this));
		},
		Bind : function()
		{
			var editor = LHEPostForm.getEditor('POST_MESSAGE');
			this.BindEvents();
			editor.forumFormCaptcha = this;
			editor.ffcSetEditorContent = editor.SetContent;
			editor.SetEditorContent = function(sContent)
			{
				var result = this.ffcSetEditorContent(sContent);
				this.forumFormCaptcha.BindEvents();
				return result;
			};
			if (editor.GetContent().length > 0)
				this.Show();
		},
		Show : function()
		{
			function _checkDisplay(ob)
			{
				var d = ob.style.display || BX.style(ob, 'display');
				return (d != 'none');
			}

			if (! _checkDisplay(this.div))
			{
				BX.show(this.div);
				this.Update();
			}
		},
		UpdateControls : function(data)
		{
			this.input.value = '';
			this.hidden.value = data["captcha_sid"];
			this.image.src = '/bitrix/tools/captcha.php?captcha_code='+data["captcha_sid"];
		},
		Update : function()
		{
			BX.ajax.getCaptcha(BX.proxy(this.UpdateControls, this));
		}
	};
	var AutoSave = function (params)
	{
		this.form = BX(params['formID']);
		if (!this.form)
			return;
		var form = this.form,
			recoverNotify = null,
			auto_lnk = BX.create('A', {
				'attrs': {'href': 'javascript:void(0)'},
				'props': {
					'className': 'postFormAutosave bx-core-autosave bx-core-autosave-ready',
					'title': BX.message('AUTOSAVE_T')
				}
			}),
			formHeaders = BX.findChild(form, {'className': /forum-reply-header|reviews-reply-header|comments-reply-header/ }, true, true);
		if (typeof formHeaders == 'undefined' || formHeaders === null || formHeaders.length < 1)
			return;
		var formHeader = (formHeaders[formHeaders.length-1] || form);
		formHeader.insertBefore(auto_lnk, formHeader.children[0]);

		var bindLHEEvents = function(_ob)
		{
			if (BX('TITLE'))
				BX.bind(BX('TITLE'), 'keydown', BX.proxy(_ob.Init, _ob));
			if (BX('DESCRIPTION'))
				BX.bind(BX('DESCRIPTION'), 'keydown', BX.proxy(_ob.Init, _ob));
		};

		BX.addCustomEvent(form, 'onAutoSavePrepare', function (ob, h) {
			ob.DISABLE_STANDARD_NOTIFY = true;
			BX.bind(auto_lnk, 'click', BX.proxy(ob.Save, ob));
			_ob=ob;
			setTimeout(function() { bindLHEEvents(_ob) },1500);
		});

		BX.addCustomEvent(form, 'onAutoSave', function() {
			BX.removeClass(auto_lnk,'bx-core-autosave-edited');
			BX.removeClass(auto_lnk,'bx-core-autosave-ready');
			BX.addClass(auto_lnk,'bx-core-autosave-saving');
		});

		BX.addCustomEvent(form, 'onAutoSaveFinished', function(ob, t) {
			t = parseInt(t);
			if (!isNaN(t))
			{
				setTimeout(function() {
					BX.removeClass(auto_lnk,'bx-core-autosave-saving');
					BX.addClass(auto_lnk,'bx-core-autosave-ready');
				}, 1000);
				auto_lnk.title = BX.message('AUTOSAVE_L').replace('#DATE#', BX.formatDate(new Date(t * 1000)));
			}
		});

		BX.addCustomEvent(form, 'onAutoSaveInit', function() {
			BX.removeClass(auto_lnk,'bx-core-autosave-ready');
			BX.addClass(auto_lnk,'bx-core-autosave-edited');
		});

		BX.addCustomEvent(form, 'onAutoSaveRestoreFound', function(ob, data)
		{
			if (form.children[1].className == "forum-notify-bar") return;

			_ob = ob;

			recoverNotify = BX.create('DIV', {
				'props': {
					'className': 'forum-notify-bar'
				},
				'children': [
					BX.create('DIV', {
						'props': { 'className': 'forum-notify-close' },
						'children': [
							BX.create('A', {
								'events':{
									'click': function() {
										if (!! recoverNotify)
											BX.remove(recoverNotify);
										return false;
									}
								}
							})
						]
					}),
					BX.create('DIV', {
						'props': { 'className': 'forum-notify-text' },
						'children': [
							BX.create('SPAN', { 'text': BX.message('recover_message') }),
							BX.create('A', {
								'attrs': {'href': 'javascript:void(0)'},
								'props': {'className': "postFormAutorestore"},
								'text': BX.message('AUTOSAVE_R'),
								'events':{
									'click': function() { _ob.Restore(); return false;}
								}
							})
						]
					})
				]
			});

			form.insertBefore(recoverNotify, form.children[1]);
		});

		BX.addCustomEvent(form, 'onAutoSaveRestore', function(ob, data) {
			bindLHEEvents(ob);
		});

		BX.addCustomEvent(form, 'onAutoSaveRestoreFinished', function(ob, data) {
			if (!! recoverNotify)
				BX.remove(recoverNotify);
		});
	};
})();