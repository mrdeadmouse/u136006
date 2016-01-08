BX.CLBlock = function(arParams)
{
	this.arData = new Array();
	this.arData["Subscription"] = new Array();
	this.UTPopup = null;

	this.entity_type = null;
	this.entity_id = null;
	this.event_id = null;
	this.event_id_fullset = false;
	this.cb_id = null;
	this.t_val = null;
	this.ind = null;
	this.type = null;
}

BX.CLBlock.prototype.DataParser = function(str)
{
	str = str.replace(/^\s+|\s+$/g, '');
	while (str.length > 0 && str.charCodeAt(0) == 65279)
		str = str.substring(1);

	if (str.length <= 0)
		return false;

	if (str.substring(0, 1) != '{' && str.substring(0, 1) != '[' && str.substring(0, 1) != '*')
		str = '"*"';

	eval("arData = " + str);

	return arData;
}

function __logFilterShow()
{
	if (BX('bx_sl_filter').style.display == 'none')
	{
		BX('bx_sl_filter').style.display = 'block';
		BX('bx_sl_filter_hidden').style.display = 'none';
	}
	else
	{
		BX('bx_sl_filter').style.display = 'none';
		BX('bx_sl_filter_hidden').style.display = 'block';
	}
}

if (!window.XMLHttpRequest)
{
	var XMLHttpRequest = function()
	{
		try { return new ActiveXObject("MSXML3.XMLHTTP") } catch(e) {}
		try { return new ActiveXObject("MSXML2.XMLHTTP.3.0") } catch(e) {}
		try { return new ActiveXObject("MSXML2.XMLHTTP") } catch(e) {}
		try { return new ActiveXObject("Microsoft.XMLHTTP") } catch(e) {}
	}
}

var sonetLXmlHttpGet = new XMLHttpRequest();
var sonetLXmlHttpSet = new XMLHttpRequest();

var LBlock = new BX.CLBlock();

function __logOnAjaxInsertToNode(params)
{
	var arPos = false;

	if (BX('sonet_log_more_container'))
	{
		nodeTmp1 = BX.findChild(BX('sonet_log_more_container'), {'tag':'span', 'className': 'feed-new-message-inf-text'}, false);
		nodeTmp2 = BX.findChild(BX('sonet_log_more_container'), {'tag':'span', 'className': 'feed-new-message-inf-text-waiting'}, false);
		if (nodeTmp1 && nodeTmp2)
		{
			nodeTmp1.style.display = 'none';
			nodeTmp2.style.display = 'inline';
		}
		arPos = BX.pos(BX('sonet_log_more_container'));
		nodeTmp1Cap = document.body.appendChild(BX.create('div', {
			style: {
				position: 'absolute',
				width: arPos.width + 'px',
				height: arPos.height + 'px',
				top: arPos.top + 'px',
				left: arPos.left + 'px',
				zIndex: 1000
			}
		}));
	}

	if (BX('sonet_log_counter_2_container'))
	{
		nodeTmp1 = BX.findChild(BX('sonet_log_counter_2_container'), {'tag':'span', 'className': 'feed-new-message-inf-text'}, false);
		nodeTmp2 = BX.findChild(BX('sonet_log_counter_2_container'), {'tag':'span', 'className': 'feed-new-message-inf-text-waiting'}, false);

		if (nodeTmp1 && nodeTmp2)
		{
			nodeTmp1.style.display = 'none';
			nodeTmp2.style.display = 'inline';
		}
		arPos = BX.pos(BX('sonet_log_more_container'));
		nodeTmp2Cap = document.body.appendChild(BX.create('div', {
			style: {
				position: 'absolute',
				width: arPos.width + 'px',
				height: arPos.height + 'px',
				top: arPos.top + 'px',
				left: arPos.left + 'px',
				zIndex: 1000
			}
		}));
	}

	BX.unbind(BX('sonet_log_counter_2_container'), 'click', __logOnAjaxInsertToNode);
}

function sonetLClearContainerExternalNew()
{
	logAjaxMode = 'new';
}

function sonetLClearContainerExternalMore()
{
	logAjaxMode = 'more';
}

function _sonetLClearContainerExternal(mode)
{
	if (BX('sonet_log_more_container'))
	{
		nodeTmp1 = BX.findChild(BX('sonet_log_more_container'), {'tag':'span', 'className': 'feed-new-message-inf-text'}, false);
		nodeTmp2 = BX.findChild(BX('sonet_log_more_container'), {'tag':'span', 'className': 'feed-new-message-inf-text-waiting'}, false);
		if (nodeTmp1 && nodeTmp2)
		{
			nodeTmp1.style.display = 'inline';
			nodeTmp2.style.display = 'none';
		}
	}

	if (BX('sonet_log_counter_2_wrap'))
	{
		BX.removeClass(BX("sonet_log_counter_2_wrap"), "feed-new-message-informer-anim");
		BX("sonet_log_counter_2_wrap").style.visibility = "hidden";
	}

	if (BX('sonet_log_counter_2_container'))
	{
		nodeTmp1 = BX.findChild(BX('sonet_log_counter_2_container'), {'tag':'span', 'className': 'feed-new-message-inf-text'}, false);
		nodeTmp2 = BX.findChild(BX('sonet_log_counter_2_container'), {'tag':'span', 'className': 'feed-new-message-inf-text-waiting'}, false);
		nodeTmp3 = BX.findChild(BX('sonet_log_counter_2_container'), {'tag':'span', 'className': 'feed-new-message-inf-text-reload'}, false);

		if (nodeTmp1 && nodeTmp2 && nodeTmp3)
		{
			nodeTmp1.style.display = 'inline';
			nodeTmp2.style.display = 'none';
			nodeTmp3.style.display = 'none';
		}
	}
	
	if (nodeTmp1Cap && nodeTmp1Cap.parentNode)
	{
		nodeTmp1Cap.parentNode.removeChild(nodeTmp1Cap);
	}

	if (nodeTmp2Cap && nodeTmp2Cap.parentNode)
	{
		nodeTmp2Cap.parentNode.removeChild(nodeTmp2Cap);
	}

	if (BX("sonet_log_counter_preset") && logAjaxMode == 'new')
	{
		BX("sonet_log_counter_preset").style.display = "none";
	}
}

function __logChangeCounter(count)
{
	var bZeroCounterFromDB = (parseInt(count) <= 0);

	oCounter = {
		iCommentsRead: 0
	};
	
	BX.onCustomEvent(window, 'onSonetLogChangeCounter', [oCounter]);
	count -= oCounter.iCommentsRead;
	__logChangeCounterAnimate((parseInt(count) > 0), count, bZeroCounterFromDB);
}

function __logDecrementCounter(iDecrement)
{
	if (BX("sonet_log_counter_2"))
	{
		iDecrement = parseInt(iDecrement);
		var oldVal = parseInt(BX("sonet_log_counter_2").innerHTML);
		var newVal = oldVal - iDecrement;
		if (newVal > 0)
			BX("sonet_log_counter_2").innerHTML = newVal;
		else
			__logChangeCounterAnimate(false, 0);
	}
}

function __logChangeCounterAnimate(bShow, count, bZeroCounterFromDB)
{
	bZeroCounterFromDB = !!bZeroCounterFromDB;

	if (!!window.bLockCounterAnimate)
	{
		setTimeout(function() {
			__logChangeCounterAnimate(bShow, count)
		}, 200);
		return false;
	}

	bShow = !!bShow;
	if (bShow)
	{
		if (BX("sonet_log_counter_2"))
			BX("sonet_log_counter_2").innerHTML = count;

		if (BX("sonet_log_counter_2_wrap"))
		{
			BX("sonet_log_counter_2_wrap").style.visibility = "visible";
			BX.addClass(BX("sonet_log_counter_2_wrap"), "feed-new-message-informer-anim");
		}
	}
	else if (BX("sonet_log_counter_2_wrap"))
	{
		if (
			bZeroCounterFromDB
			&& BX.hasClass(BX("sonet_log_counter_2_wrap"), "feed-new-message-informer-anim")
		)
		{
			if (BX('sonet_log_counter_2_container'))
			{
				nodeTmp1 = BX.findChild(BX('sonet_log_counter_2_container'), {'tag':'span', 'className': 'feed-new-message-inf-text'}, false);
				nodeTmp2 = BX.findChild(BX('sonet_log_counter_2_container'), {'tag':'span', 'className': 'feed-new-message-inf-text-waiting'}, false);
				nodeTmp3 = BX.findChild(BX('sonet_log_counter_2_container'), {'tag':'span', 'className': 'feed-new-message-inf-text-reload'}, false);

				if (nodeTmp1 && nodeTmp2 && nodeTmp3)
				{
					nodeTmp1.style.display = 'none';
					nodeTmp2.style.display = 'none';
					nodeTmp3.style.display = 'inline';
				}
			}
		}
		else
			setTimeout(function() {
				BX.removeClass(BX("sonet_log_counter_2_wrap"), "feed-new-message-informer-anim");
				BX("sonet_log_counter_2_wrap").style.visibility = "hidden";
			}, 400);
	}
}

function __logChangeCounterArray(arCount)
{
	if (typeof arCount[BX.message('sonetLCounterType')] != 'undefined')
		__logChangeCounter(arCount[BX.message('sonetLCounterType')]);
}

function __logShowPostMenu(bindElement, ind, entity_type, entity_id, event_id, fullset_event_id, user_id, log_id, bFavorites, arMenuItemsAdditional)
{
	BX.PopupMenu.destroy("post-menu-" + ind);

	var itemFavorites = null;

	if (BX.message('sonetLbUseFavorites') != 'N')
	{
		itemFavorites = { 
			text : (bFavorites ? BX.message('sonetLMenuFavoritesTitleY') : BX.message('sonetLMenuFavoritesTitleN')), 
			className : "menu-popup-no-icon", 
			onclick : function(e) { __logChangeFavorites(log_id, 'log_entry_favorites_' + log_id, (bFavorites ? 'N' : 'Y'), true); return BX.PreventDefault(e); } 
		};
	}

	var arItems = [
		(
			bindElement.getAttribute("data-log-entry-url").length > 0 ?
			{
				text : '<span id="post-menu-' + ind + '-href-text">' + BX.message("sonetLMenuHref") + '</span>',
				className : "menu-popup-no-icon feed-entry-popup-menu feed-entry-popup-menu-href",
				href : bindElement.getAttribute("data-log-entry-url")
			} : null
		),
		(
			bindElement.getAttribute("data-log-entry-url").length > 0
			? {
				text : '<span id="post-menu-' + ind + '-link-text">' + BX.message("sonetLMenuLink") + '</span>',				
				className : "menu-popup-no-icon feed-entry-popup-menu feed-entry-popup-menu-link", 
				onclick : function() {

					id = 'post-menu-' + ind + '-link',
					it = BX.proxy_context,
					height = parseInt(!!it.getAttribute("bx-height") ? it.getAttribute("bx-height") : it.offsetHeight);

					if (it.getAttribute("bx-status") != "shown")
					{
						it.setAttribute("bx-status", "shown");
						if (!BX(id) && !!BX(id + '-text'))
						{
							var
								node = BX(id + '-text'),
								pos = BX.pos(node),
								pos2 = BX.pos(node.parentNode);
								pos3 = BX.pos(BX.findParent(node, {'className': 'menu-popup-item'}, true));

							pos["height"] = pos2["height"] - 1;

							BX.adjust(it, {
								attrs : {"bx-height" : it.offsetHeight},
								style : { 
									overflow : "hidden", 
									display : 'block'
								},
								children : [
									BX.create('BR'),
									BX.create('DIV', { 
										attrs : {id : id},
										children : [
											BX.create('SPAN', {attrs : {"className" : "menu-popup-item-left"}}),
											BX.create('SPAN', {attrs : {"className" : "menu-popup-item-icon"}}),
											BX.create('SPAN', {attrs : {"className" : "menu-popup-item-text"},
												children : [
													BX.create('INPUT', {
															attrs : {
																id : id + '-input',
																type : "text",
																value : bindElement.getAttribute('data-log-entry-url') } ,
															style : {
																height : pos["height"] + 'px',
																width : (pos3["width"]-21) + 'px'
															},
															events : { click : function(e){ this.select(); BX.PreventDefault(e); } }
														}
													)
												]
											})
										]
									}),
									BX.create('SPAN', {"className" : "menu-popup-item-right"})
								]
							});
						}
						(new BX.fx({
							time: 0.2,
							step: 0.05,
							type: 'linear',
							start: height,
							finish: height * 2,
							callback: BX.delegate(function(height) {this.style.height = height + 'px';}, it)
						})).start();
						BX.fx.show(BX(id), 0.2);
						BX(id + '-input').select();
					}
					else
					{
						it.setAttribute("bx-status", "hidden");
						(new BX.fx({
							time: 0.2,
							step: 0.05,
							type: 'linear',
							start: it.offsetHeight,
							finish: height,
							callback: BX.delegate(function(height) {this.style.height = height + 'px';}, it)
						})).start();
						BX.fx.hide(BX(id), 0.2);
					}
				}
			}
			: null
		),
		itemFavorites,
		(
			BX.message('sonetLCanDelete') == 'Y' ?
			{
				text : BX.message('sonetLMenuDelete'), 
				className : "menu-popup-no-icon", 
				onclick : function(e) { 
					if (confirm(BX.message('sonetLMenuDeleteConfirm')))
					{
						__logDelete(log_id, 'log-entry-' + log_id, ind);
					}
					return BX.PreventDefault(e); 
				} 
			} : null
		)		
	];

	if (
		!!arMenuItemsAdditional
		&& BX.type.isArray(arMenuItemsAdditional)
	)
	{
		for (var i = 0; i < arMenuItemsAdditional.length; i++)
			if (typeof arMenuItemsAdditional[i].className == 'undefined')
				arMenuItemsAdditional[i].className = "menu-popup-no-icon";

		arItems = BX.util.array_merge(arItems, arMenuItemsAdditional);
	}

	var arParams = {
		offsetLeft: -14,
		offsetTop: 4,
		lightShadow: false,
		angle: {position: 'top', offset : 50},
		events : {
			onPopupShow : function(ob)
			{
				if (BX('log_entry_favorites_' + log_id))
				{
					var menuItems = BX.findChildren(ob.contentContainer, {'className' : 'menu-popup-item-text'}, true);
					if (menuItems != null)
					{
						for (var i = 0; i < menuItems.length; i++)
						{
							if (
								menuItems[i].innerHTML == BX.message('sonetLMenuFavoritesTitleY')
								|| menuItems[i].innerHTML == BX.message('sonetLMenuFavoritesTitleN')
							)
							{
								var favoritesMenuItem = menuItems[i];
								break;
							}
						}
					}

					if (favoritesMenuItem != undefined)
					{
						if (BX.hasClass(BX('log_entry_favorites_' + log_id), 'feed-post-important-switch-active'))
							BX(favoritesMenuItem).innerHTML = BX.message('sonetLMenuFavoritesTitleY');
						else
							BX(favoritesMenuItem).innerHTML = BX.message('sonetLMenuFavoritesTitleN');
					}
				}

				if (BX('post-menu-' + ind + '-link'))
				{
					var linkMenuItem = BX.findChild(ob.popupContainer, {className: 'feed-entry-popup-menu-link'}, true, false);
					if (linkMenuItem)
					{
						var height = parseInt(!!linkMenuItem.getAttribute("bx-height") ? linkMenuItem.getAttribute("bx-height") : 0);
						if (height > 0)
						{
							BX('post-menu-' + ind + '-link').style.display = "none";
							linkMenuItem.setAttribute("bx-status", "hidden");
							linkMenuItem.style.height = height + 'px';
						}
					}
				}
			}
		}
	};

	BX.PopupMenu.show("post-menu-" + ind, bindElement, arItems, arParams);
}

function __logCommentFormAutogrow(el)
{
	var placeNodeoffsetHeightOld = 0;

	if (el && BX.type.isDomNode(el))
		var textarea = el;
	else
	{
		var textarea = BX.proxy_context;
		var event = el || window.event;

		if ((event.keyCode == 13 || event.keyCode == 10) && event.ctrlKey)
			__logCommentAdd();
	}

	var placeNode = BX.findParent(textarea, {'className': 'sonet-log-comment-form-place'});
	if (BX(placeNode))
		placeNodeoffsetHeightOld = BX(placeNode).offsetHeight;

	var linesCount = 0;
	var lines = textarea.value.split('\n');

	for (var i=lines.length-1; i>=0; --i)
		linesCount += Math.floor((lines[i].length / CommentFormColsDefault) + 1);

	if (linesCount >= CommentFormRowsDefault)
		textarea.rows = linesCount + 1;
	else
		textarea.rows = CommentFormRowsDefault;
}

function __logGetNextPage(more_url, bFirst, oNode)
{
	if (oLF.bLoadStarted)
	{
		return false;
	}

	oLF.bLoadStarted = true;

	window.bLockCounterAnimate = true;
	bFirst = !!bFirst;

	if (
		!bFirst 
		&& BX('feed-new-message-inf-wrap')
	)
	{
		BX('feed-new-message-inf-wrap').classList.toggle('feed-new-message-anim');
	}

	var data = { method: "GET", url: more_url };
	BX.onCustomEvent("SonetLogBeforeGetNextPage", [ data ]);
	if(BX.type.isNotEmptyString(data.url))
	{
		more_url = data.url;
	}

	BX.ajax({
		url: more_url,
		method: 'GET',
		dataType: 'html',
		data: { },
		onsuccess: function(data)
		{
			oLF.bLoadStarted = false;
			if (
				!bFirst 
				&& typeof oNode != 'undefined'
				&& oNode
				&& oNode.parentNode
			)
			{
				BX.cleanNode(oNode.parentNode, true);
			}

			window.bLockCounterAnimate = false;

			if (data.length > 0)
			{
				var content_block_id = 'content_block_' + (Math.floor(Math.random() * 1000));
				BX('log_internal_container').appendChild(BX.create('DIV', {
					props: { 
						id: content_block_id,
						className: 'feed-wrap' 
					}, 
					style: {
						display: (bFirst ? 'none' : 'block')
					},
					html: data
				}));
				_sonetLClearContainerExternal(false);

				if (bFirst)
				{
					BX('feed-new-message-inf-wrap-first').style.display = 'block';
					var f = function() {
						if (BX(content_block_id))
						{
							BX(content_block_id).style.display = 'block';
						}
						BX.unbind(BX('sonet_log_more_container_first'), 'click', f);
						BX('feed-new-message-inf-wrap-first').style.display = 'none';
						__logRecalcMoreButton();
					};
					BX.bind(BX('sonet_log_more_container_first'), 'click', f);
				}
				else
				{
					setTimeout(function() { __logRecalcMoreButton(); }, 1000);
				}
			}
		},
		onfailure: function(data) 
		{
			oLF.bLoadStarted = false;
			if (
				!bFirst 
				&& BX('feed-new-message-inf-wrap')
			)
			{
				BX('feed-new-message-inf-wrap').classList.toggle('feed-new-message-anim');
			}

			window.bLockCounterAnimate = false;
			_sonetLClearContainerExternal(false);
		}
	});

	return false;
}
function __logGetNextPageLinkEntities(entities, correspondences)
{
	if (!!window.__logGetNextPageFormName && !!entities && !!correspondences &&
		!!window["UC"] && !!window["UC"][window.__logGetNextPageFormName] &&
		!!window["UC"][window.__logGetNextPageFormName].linkEntity)
	{
		window["UC"][window.__logGetNextPageFormName].linkEntity(entities);
		for (var ii in correspondences)
		{
			if (!!ii && !!correspondences[ii])
				window["UC"][window.__logGetNextPageFormName]["entitiesCorrespondence"][ii] = correspondences[ii];
		}
	}
}

function __logRefresh(refresh_url)
{
	if (oLF.bLoadStarted)
	{
		return;
	}

	var counterWrap = BX("sonet_log_counter_2_wrap", true);
	oLF.bLoadStarted = true;

	if (counterWrap)
	{
		var nodeTmp3 = BX.findChild(counterWrap, {'tag':'span', 'className': 'feed-new-message-inf-text-reload'}, true);
		if (nodeTmp3)
		{
			nodeTmp3.style.display = 'none';
		}
	}

	window.bLockCounterAnimate = true;

	BX.ajax({
		url: refresh_url,
		method: 'GET',
		dataType: 'json',
		data: { },
		onsuccess: function(data)
		{
			oLF.bLoadStarted = false;

			if (
				typeof data != 'undefined'
				&& typeof (data.TEXT) != 'undefined'
				&& (data.TEXT.length > 0)
			)
			{
				window.bLockCounterAnimate = false;
				BX.cleanNode('log_internal_container', false);
				BX('log_internal_container').appendChild(
					BX.create('DIV', {
						props: { 
							className: 'feed-wrap' 
						}, 
						html: data.TEXT
					})
				);

				var ob = BX.processHTML(data.TEXT, true);
				var scripts = ob.SCRIPT;
				setTimeout(function() {
					BX.ajax.processScripts(scripts, true);
				}, 500);

				_sonetLClearContainerExternal(false);
				window.bStopTrackNextPage = false;

				if (BX('feed-new-message-inf-wrap-first'))
				{
					BX('feed-new-message-inf-wrap-first').style.display = 'none';
				}

				if (typeof arCommentsMoreButtonID != 'undefined')
				{
					arCommentsMoreButtonID = [];
				}

				if (
					counterWrap
					&& BX.hasClass(counterWrap, "feed-new-message-informer-fixed")
				)
				{
					var upBtn = BX("feed-up-btn-wrap", true);
					if (upBtn)
					{
						upBtn.style.display = "none";
						BX.removeClass(upBtn, 'feed-up-btn-wrap-anim');
					}

					var windowScroll = BX.GetWindowScrollPos();

					(new BX.easing({
						duration : 500,
						start : { scroll : windowScroll.scrollTop },
						finish : { scroll : 0 },
						transition : BX.easing.makeEaseOut(BX.easing.transitions.quart),
						step : function(state){
							window.scrollTo(0, state.scroll);
						},
						complete: function() {
							if (upBtn)
								upBtn.style.display = "block";
							BX.onCustomEvent(window, 'onGoUp');
						}
					})).animate();
				}
			}
			else
			{
				oLF.showRefreshError();
			}
		},
		onfailure: function(data) 
		{
			oLF.bLoadStarted = false;
			oLF.showRefreshError();
		}
	});

	return false;
}

function __logChangeFavorites(log_id, node, newState, bFromMenu)
{
	if (
		!log_id
		|| !BX(node)
	)
	{
		return;
	}

	if (!!bFromMenu)
	{
		var menuItem = BX.proxy_context;
		if (!BX.hasClass(BX(menuItem), 'menu-popup-item-text'))
		{
			menuItem = BX.findChild(BX(menuItem), {'className': 'menu-popup-item-text'}, true);
		}
	}

	var nodeToAdjust = (
		BX.hasClass(BX(node), 'feed-post-important-switch')
			? BX(node)
			: BX.findChild(BX(node), { 'className': 'feed-post-important-switch' })
	);

	if (newState != undefined)
	{
		if (newState == "Y")
		{
			BX.addClass(BX(nodeToAdjust), "feed-post-important-switch-active");
			BX(nodeToAdjust).title = BX.message('sonetLMenuFavoritesTitleY');
			if (typeof menuItem != 'undefined')
			{
				BX(menuItem).innerHTML = BX.message('sonetLMenuFavoritesTitleY');
			}
		}
		else
		{
			BX.removeClass(BX(nodeToAdjust), "feed-post-important-switch-active");
			BX(nodeToAdjust).title = BX.message('sonetLMenuFavoritesTitleN');
			if (typeof menuItem != 'undefined')
			{
				BX(menuItem).innerHTML = BX.message('sonetLMenuFavoritesTitleN');
			}
		}
	}

	var sonetLXmlHttpSet5 = new XMLHttpRequest();

	sonetLXmlHttpSet5.open("POST", BX.message('sonetLESetPath'), true);
	sonetLXmlHttpSet5.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

	sonetLXmlHttpSet5.onreadystatechange = function()
	{
		if(sonetLXmlHttpSet5.readyState == 4)
		{
			if(sonetLXmlHttpSet5.status == 200)
			{
				var data = LBlock.DataParser(sonetLXmlHttpSet5.responseText);
				if (typeof(data) == "object")
				{
					if (data[0] == '*')
					{
						if (sonetLErrorDiv != null)
						{
							sonetLErrorDiv.style.display = "block";
							sonetLErrorDiv.innerHTML = sonetLXmlHttpSet5.responseText;
						}
						return;
					}
					sonetLXmlHttpSet5.abort();

					var strMessage = '';

					if (
						data["bResult"] != undefined 
						&& (
							data["bResult"] == "Y" 
							|| data["bResult"] == "N"
						)
					)
					{
						if (data["bResult"] == "Y")
						{
							BX.addClass(BX(nodeToAdjust), "feed-post-important-switch-active");
							BX(nodeToAdjust).title = BX.message('sonetLMenuFavoritesTitleY');
							if (menuItem != undefined)
								BX(menuItem).innerHTML = BX.message('sonetLMenuFavoritesTitleY');
						}
						else
						{
							BX.removeClass(BX(nodeToAdjust), "feed-post-important-switch-active");
							BX(nodeToAdjust).title = BX.message('sonetLMenuFavoritesTitleN');
							if (menuItem != undefined)
								BX(menuItem).innerHTML = BX.message('sonetLMenuFavoritesTitleN');
						}
					}
				}
			}
			else
			{
				// error!
			}
		}
	}

	sonetLXmlHttpSet5.send("r=" + Math.floor(Math.random() * 1000)
		+ "&" + BX.message('sonetLSessid')
		+ "&site=" + BX.util.urlencode(BX.message('sonetLSiteId'))
		+ "&log_id=" + encodeURIComponent(log_id)
		+ "&action=change_favorites"
	);
}

function __logDelete(log_id, node, ind)
{
	if (!log_id)
	{
		return;
	}

	if (!BX(node))
	{
		return;
	}

	BX.ajax({
		url: BX.message('sonetLESetPath'),
		method: 'POST',
		dataType: 'json',
		data: {
			sessid : BX.bitrix_sessid(),
			site : BX.message('sonetLSiteId'),
			log_id : log_id,
			action : 'delete'
		},
		onsuccess: function(data) {
			if (
				data.bResult != undefined 
				&& (data.bResult == "Y")
			)
			{
				if (typeof ind != 'undefined')
				{
					BX.PopupMenu.destroy("post-menu-" + ind);
				}
				__logDeleteSuccess(BX(node));
			}
			else
			{
				__logDeleteFailure(BX(node));
			}
		},
		onfailure: function(data) {
			__logDeleteFailure(BX(node));
		}
	});
}

function __logDeleteSuccess(node)
{
	if (
		typeof node == 'undefined' 
		|| !node
		|| !BX(node)
	)
	{
		return;
	}

	(new BX.fx({
		time: 0.5,
		step: 0.05,
		type: 'linear',
		start: BX(node).offsetHeight,
		finish: 60,
		callback: BX.delegate(function(height) { 
			this.style.height = height + 'px';
		}, BX(node)),
		callback_start: BX.delegate(function() { 
			this.style.overflow = 'hidden';
			this.style.minHeight = 0;
		}, BX(node)),
		callback_complete: BX.delegate(function() {
			this.style.marginBottom = 0;
			BX.cleanNode(this);
			this.appendChild(BX.create('DIV', {
				props: {
					'className': 'feed-add-successfully'
				},
				style: {
					'marginLeft': '17px',
					'marginRight': '17px',
					'marginTop': '10px'
				},
				children: [
					BX.create('span', {
						props: {
							'className': 'feed-add-info-text'
						},
						children: [
							BX.create('span', {
								props: {
									'className': 'feed-add-info-icon'
								}
							}),
							BX.create('span', {
								html: BX.message('sonetLMenuDeleteSuccess')
							})
						]
					})
				]
			}));
		}, BX(node))
	})).start();
}

function __logDeleteFailure(node)
{
	if (
		typeof node == 'undefined' 
		|| !node
		|| !BX(node)
	)
	{
		return;
	}

	node.insertBefore(BX.create('DIV', {
		props: {
			'className': 'feed-add-error'
		},
		style: {
			'marginLeft': '84px',
			'marginRight': '37px',
			'marginTop': '18px',
			'marginBottom': '4px'
		},
		children: [
			BX.create('span', {
				props: {
					'className': 'feed-add-info-text'
				},
				children: [
					BX.create('span', {
						props: {
							'className': 'feed-add-info-icon'
						}
					}),
					BX.create('span', {
						html: BX.message('sonetLMenuDeleteFailure')
					})
				]
			})
		]
	}), node.firstChild);
}

function __logOnFeedScroll()
{
	// Live Feed Paging
	if (
		window.feedScrollLock == undefined 
		|| window.feedScrollLock === false
	)
	{
		window.feedScrollLock = true;

		setTimeout(function() {
			window.feedScrollLock = false;
		}, 100);

		var windowSize = BX.GetWindowSize();

		if (
			window.bStopTrackNextPage === undefined
			|| window.bStopTrackNextPage == false
		)
		{
			var maxScroll = parseInt(windowSize.scrollHeight / 2);

			if ((windowSize.scrollHeight - windowSize.innerHeight) < maxScroll)
			{
				maxScroll = 1;
			}

			if (windowSize.scrollTop >= maxScroll && next_url)
			{
				window.bStopTrackNextPage = true;
				__logGetNextPage(next_url, true);
			}
		}
	}

	//Live Feed New Message Block
	var counterWrap = BX("sonet_log_counter_2_wrap", true);
	if (counterWrap)
	{
		var top = counterWrap.parentNode.getBoundingClientRect().top;
		if (top <= 0)
		{
			BX.addClass(counterWrap, "feed-new-message-informer-fixed");
			setTimeout(function() {
				if (BX.hasClass(counterWrap, "feed-new-message-informer-fixed"))
				{
					BX.addClass(counterWrap, "feed-new-message-informer-fix-anim");
				}
			}, 100);
		}
		else
		{
			BX.removeClass(counterWrap, "feed-new-message-informer-fixed feed-new-message-informer-fix-anim");
		}
	}
}

function __logScrollInit(enable)
{
	if (!!enable)
	{
		BX.unbind(window, 'scroll', __logOnFeedScroll);
		BX.bind(window, 'scroll', __logOnFeedScroll);
	}
	else
	{
		BX.unbind(window, 'scroll', __logOnFeedScroll);
	}
}

function __logRecalcMoreButton()
{
	if (typeof arMoreButtonID != 'undefined')
	{
		var arPos = false;
		var arPosOuter = false;
		var obOuter = false;
		var obInner = false;

		for (var i = 0; i < arMoreButtonID.length; i++)
		{
			arPos = BX.pos(BX(arMoreButtonID[i].bodyBlockID));
			if (arPos.height < 280)
			{
				BX(arMoreButtonID[i].moreButtonBlockID).style.display = "none";
			}

			if (typeof arMoreButtonID[i].outerBlockID != 'undefined')
			{
				obOuter = BX(arMoreButtonID[i].outerBlockID);
				if (obOuter)
				{
					arPosOuter = BX.pos(obOuter);
					if (arPosOuter.width < arPos.width)
					{
						obInner = BX.findChild(obOuter, {'tag':'div', 'className': 'feed-post-text-block-inner'}, false);
						obInner.style.overflowX = 'scroll'
					}
				}
			}
		}
	}

	if (typeof arCommentsMoreButtonID != 'undefined')
	{
		var arPos = false;
		for (var i = 0; i < arCommentsMoreButtonID.length; i++)
		{
			arPos = BX.pos(BX(arCommentsMoreButtonID[i].bodyBlockID));
			if (arPos.height < 202)
			{
				BX(arCommentsMoreButtonID[i].moreButtonBlockID).style.display = "none";
			}
		}
	}
}

window.__socOnUCFormClear = function(obj) {
	LHEPostForm.reinitDataBefore(obj.editorId);
};
window.__socOnUCFormAfterShow = function(obj, text, data)
{
	data = (!!data ? data : {});

	var eId = obj.entitiesCorrespondence[obj.id.join('-')][0], id = obj.entitiesCorrespondence[obj.id.join('-')][1];
	BX.show(BX('feed_comments_block_' + eId));
	BX.onCustomEvent(window, "OnBeforeSocialnetworkCommentShowedUp", ['socialnetwork']);
	obj.form.action = obj.url.replace(/\#eId\#/, eId).replace(/\#id\#/, id);

	var
		post_data = {
			ENTITY_XML_ID : obj.id[0],
			ENTITY_TYPE : obj.entitiesId[obj.id[0]][0],
			ENTITY_ID : obj.entitiesId[obj.id[0]][1],
			parentId : obj.id[1],
			comment_post_id : obj.entitiesId[obj.id[0]][1],
			edit_id : obj.id[1],
			act : (obj.id[1] > 0 ? 'edit' : 'add'),
			logId : obj.entitiesId[obj.id[0]][2]
		};
	for (var ii in post_data)
	{
		if (!obj.form[ii])
		{
			obj.form.appendChild(BX.create('INPUT', {attrs : {name : ii, type: "hidden"}}));
		}
		obj.form[ii].value = post_data[ii];
	}
	__socOnLightEditorShow(text, data);
};
window.__socOnUCFormSubmit =  function(obj, post_data) {
	post_data["r"] = Math.floor(Math.random() * 1000);
	post_data["sessid"] = BX.bitrix_sessid();
	post_data["log_id"] = obj.entitiesCorrespondence[obj.id.join('-')][0];
	post_data["p_smile"] = BX.message('sonetLPathToSmile');
	post_data["p_ubp"] = BX.message('sonetLPathToUserBlogPost');
	post_data["p_gbp"] = BX.message('sonetLPathToGroupBlogPost');
	post_data["p_umbp"] = BX.message('sonetLPathToUserMicroblogPost');
	post_data["p_gmbp"] = BX.message('sonetLPathToGroupMicroblogPost');
	post_data["p_user"] = BX.message('sonetLPathToUser');
	post_data["f_id"] = BX.message('sonetLForumID');
	post_data["bapc"] = BX.message('sonetLBlogAllowPostCode');
	post_data["site"] = BX.message('sonetLSiteId');
	post_data["lang"] = BX.message('sonetLLangId');
	post_data["nt"] = BX.message('sonetLNameTemplate');
	post_data["sl"] = BX.message('sonetLShowLogin');
	post_data["as"] = BX.message('sonetLAvatarSizeComment');
	post_data["dtf"] = BX.message('sonetLDateTimeFormat');
	post_data["message"] = post_data["REVIEW_TEXT"];
	post_data["action"] = 'add_comment'
	post_data["RATING_TYPE"] = BX.message("sonetRatingType");
	post_data["pull"] = "Y";
	post_data["crm"] = BX.message('sonetLIsCRM');
	obj.form["bx-action"] = obj.form.action;
	obj.form.action = BX.message('sonetLESetPath');
};
window.__socOnUCFormResponse = function(obj, data)
{
	obj.form.action = obj.form["bx-action"];
	var return_data = {errorMessage : data},
		eId = obj.entitiesCorrespondence[obj.id.join('-')][0],
		res = {};

	if (!(!!data && typeof data == "object"))
	{}
	else if (data[0] == '*')
	{
		return_data = {errorMessage : BX.message("sonetLErrorSessid")};
	}
	else
	{
		if (!(data["commentID"] > 0) || !!data["strMessage"]) {
			return_data['errorMessage'] = data["strMessage"];
		} else {
			var
				arComment = data["arCommentFormatted"],
				arComm = data["arComment"],
				ratingNode = (!!window["__logBuildRating"] ? window["__logBuildRating"](data["arComment"], data["arCommentFormatted"]) : null),
				thisId = (!!arComm["SOURCE_ID"] ? arComm["SOURCE_ID"] : arComm["ID"]),
				res = {
					"ID" : thisId, // integer
					"ENTITY_XML_ID" : obj.id[0], // string
					"FULL_ID" : [obj.id[0], thisId],
					"NEW" : "N", //"Y" | "N"
					"APPROVED" : "Y", //"Y" | "N"
					"POST_TIMESTAMP" : data["timestamp"] - BX.message('USER_TZ_OFFSET'),
					"POST_TIME" : arComment["LOG_TIME_FORMAT"],
					"POST_DATE" : arComment["LOG_TIME_FORMAT"],
					"~POST_MESSAGE_TEXT" : arComment["MESSAGE"],
					"POST_MESSAGE_TEXT" : arComment["MESSAGE_FORMAT"],
					"PANELS" : {
						"MODERATE" : false
					},
					"URL" : {
						"LINK" : (arComm["URL"].length > 0 ? arComm["URL"] :  BX.message('sonetLEPath').replace("#log_id#", arComm["LOG_ID"]) + '?commentId=' + arComm["ID"] + '#com' + (parseInt(arComm["SOURCE_ID"]) > 0 ? arComm["SOURCE_ID"] : arComm["ID"]))
					},
					"AUTHOR" : {
						"ID" : arComment["USER_ID"],
						"NAME" : arComment["CREATED_BY"]["FORMATTED"],
						"URL" : arComment["CREATED_BY"]["URL"],
						"AVATAR" : arComment["AVATAR_SRC"] },
					"BEFORE_ACTIONS" : (!!ratingNode ? ratingNode : ''),
					"AFTER" : arComment["UF"]
				};

				if (
					typeof (data["hasEditCallback"]) != 'undefined'
					&& data["hasEditCallback"] == "Y"
				)
				{
					res["PANELS"]["EDIT"] = "Y";
					res["URL"]["EDIT"] = "__logEditComment('" + obj.id[0] + "', '" + arComm["ID"] + "', '" + arComm["LOG_ID"] + "');";
				}

				if (
					typeof (data["hasDeleteCallback"]) != 'undefined'
					&& data["hasDeleteCallback"] == "Y"
				)
				{
					res["PANELS"]["DELETE"] = "Y";
					res["URL"]["DELETE"] = BX.message('sonetLESetPath') + '?lang=' + BX.message('sonetLLangId') + '&action=delete_comment&delete_comment_id=' + arComm["ID"] + '&post_id=' + arComm["LOG_ID"] + '&site=' + BX.message('sonetLSiteId');
				}

			return_data = {
				'errorMessage' : '',
				'okMessage' : '',
				'status' : true,
				'message' : '',
				'messageCode' : arComment["MESSAGE"],
				'messageId' : [obj.id[0], thisId],
				'~message' : '',
				'messageFields' : res
			};
		}


		var node = BX("log_entry_follow_" + eId, true),
			strFollowOld = (!!node ? (node.getAttribute("data-follow") == "Y" ? "Y" : "N") : false);
		if (strFollowOld == "N")
		{
			BX.findChild(node, { tagName: 'a' }).innerHTML = BX.message('sonetLFollowY');
			node.setAttribute("data-follow", "Y");
		}

		var node = BX("feed-comments-all-cnt-" + eId, true),
			val = (!!node ? (node.innerHTML.length > 0 ? parseInt(node.innerHTML) : 0) : false);
		if (val !== false)
			node.innerHTML = (val + 1);
	}

	obj.OnUCFormResponseData = return_data;
}

window.__socOnLightEditorShow = function(content, data){
	var res = {};
	if (data["arFiles"])
	{
		var tmp2 = {}, name, size;
		for (var ij = 0; ij < data["arFiles"].length; ij++)
		{
			name = BX.findChild(BX('wdif-doc-' + data["arFiles"][ij]), {className : "feed-com-file-name"}, true);
			size = BX.findChild(BX('wdif-doc-' + data["arFiles"][ij]), {className : "feed-con-file-size"}, true);

			tmp2['F' + ij] = {
				FILE_ID : data["arFiles"][ij],
				FILE_NAME : (name ? name.innerHTML : "noname"),
				FILE_SIZE : (size ? size.innerHTML : "unknown"),
				CONTENT_TYPE : "notimage/xyz"};
		}
		res["UF_SONET_COM_DOC"] = {
			USER_TYPE_ID : "file",
			FIELD_NAME : "UF_SONET_COM_FILE[]",
			VALUE : tmp2};
	}
	if (data["arDocs"])
		res["UF_SONET_COM_FILE"] = {
			USER_TYPE_ID : "webdav_element",
			FIELD_NAME : "UF_SONET_COM_DOC[]",
			VALUE : BX.clone(data["arDocs"])};
	if (data["arDFiles"])
		res["UF_SONET_COM_FILE"] = {
			USER_TYPE_ID : "disk_file",
			FIELD_NAME : "UF_SONET_COM_DOC[]",
			VALUE : BX.clone(data["arDFiles"])};
	LHEPostForm.reinitData(SLEC.editorId, content, res);
}

BitrixLF = function ()
{
	this.bLoadStarted = false;
};

BitrixLF.prototype.showRefreshError = function()
{
	window.bLockCounterAnimate = false;
	_sonetLClearContainerExternal(false);
/*
	var f = function() 
	{
		if (BX('sonet_log_counter_2_wrap'))
		{
			BX.unbind(BX('sonet_log_counter_2_error'), 'click', f);
			BX('sonet_log_counter_2_error').style.display = 'none';
			BX('sonet_log_counter_2_container').style.display = 'block';
			BX.removeClass(BX("sonet_log_counter_2_wrap"), "feed-new-message-informer-anim");
		}		
	};

	if (BX('sonet_log_counter_2_wrap'))
	{
		BX.addClass(BX("sonet_log_counter_2_wrap"), "feed-new-message-informer-anim");
		BX.bind(BX('sonet_log_counter_2_error'), 'click', f);
		BX('sonet_log_counter_2_container').style.display = 'none';
		BX('sonet_log_counter_2_error').style.display = 'block';
	}
*/
}

BitrixLF.prototype.LazyLoadCheckVisibility = function(image) // to check if expanded or not
{
	var img = image.node;

	var textType = 'comment';

	var textBlock = BX.findParent(img, {'className': 'feed-com-text'});
	if (!textBlock)
	{
		textType = 'post';
		textBlock = BX.findParent(img, {'className': 'feed-post-text-block'});
	}

	if (textBlock)
	{
		var moreBlock = BX.findChild(textBlock, {'tag':'div', 'className': 'feed-post-text-more'}, false)
		if (
			moreBlock 
			&& moreBlock.style.display != 'none'
		)
		{
			return img.parentNode.parentNode.offsetTop < (textType == 'comment' ? 220 : 270);
		}
	}

	return true;
}

oLF = new BitrixLF;
window.oLF = oLF;