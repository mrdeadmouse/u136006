function showHiddenDestination(cont, el)
{
	BX.hide(el);
	BX('blog-destination-hidden-'+cont).style.display = 'inline';
}

function showMenuLinkInput(ind, url)
{
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
												value : url 
											},
											style : {
												height : pos["height"] + 'px',
												width : (pos3["width"] - 21) + 'px'
											},
											events : { click : function(e){ this.select(); BX.PreventDefault(e);} }
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
					
function showBlogPost(id, source)
{
	var el = BX.findChild(BX('blg-post-' + id), {className: 'feed-post-text-block-inner'}, true, false);
	el2 = BX.findChild(BX('blg-post-' + id), {className: 'feed-post-text-block-inner-inner'}, true, false);
	BX.remove(source);

	if(el)
	{
		var fxStart = 300;
		var fxFinish = el2.offsetHeight;
		(new BX.fx({
			time: 1.0 * (fxFinish - fxStart) / (1200-fxStart),
			step: 0.05,
			type: 'linear',
			start: fxStart,
			finish: fxFinish,
			callback: BX.delegate(__blogExpandSetHeight, el),
			callback_complete: BX.delegate(function() {
				this.style.maxHeight = 'none'; 
				BX.LazyLoad.showImages(true);
			}, el)
		})).start();
	}
}

function __blogExpandSetHeight(height)
{
	this.style.maxHeight = height + 'px';
}

function deleteBlogPost(id)
{
	url = BX.message('sonetBPDeletePath');
	url1 = url.replace('#del_post_id#', id);

	if(BX.findChild(BX('blg-post-'+id), {'attr': {id: 'form_c_del'}}, true, false))
	{
		BX.hide(BX('form_c_del'));
		BX(BX('blg-post-'+id).parentNode.parentNode).appendChild(BX('form_c_del')); // Move form
	}

	BX.ajax.get(url1, function(data){
		if(window.deletePostEr && window.deletePostEr == "Y")
		{
			var el = BX('blg-post-'+id);
			BX.findChild(el, {className: 'feed-post-cont-wrap'}, true, false).insertBefore(
				BX.create('SPAN', {
					html: data
				}),
				BX.findChild(el, {className: 'feed-user-avatar'}, true, false)
			);
		}
		else
		{
			BX('blg-post-'+id).parentNode.innerHTML = data;
		}
	});
	
	return false;
}

var waitPopupBlogImage = null;
function blogShowImagePopup(src)
{
	if(!waitPopupBlogImage)
	{
		waitPopupBlogImage = new BX.PopupWindow('blogwaitPopupBlogImage', window, {
			autoHide: true,
			lightShadow: false,
			zIndex: 2,
			content: BX.create('IMG', {props: {src: src, id: 'blgimgppp'}}),
			closeByEsc: true,
			closeIcon: true
		});
	}
	else
	{
		BX('blgimgppp').src = '/bitrix/images/1.gif';
		BX('blgimgppp').src = src;
	}

	waitPopupBlogImage.setOffset({
		offsetTop: 0,
		offsetLeft: 0
	});

	setTimeout(function(){waitPopupBlogImage.adjustPosition()}, 100);	
	waitPopupBlogImage.show();

}

function __blogPostSetFollow(log_id)
{
	var strFollowOld = (BX("log_entry_follow_" + log_id, true).getAttribute("data-follow") == "Y" ? "Y" : "N");
	var strFollowNew = (strFollowOld == "Y" ? "N" : "Y");	

	if (BX("log_entry_follow_" + log_id, true))
	{
		BX.findChild(BX("log_entry_follow_" + log_id, true), { tagName: 'a' }).innerHTML = BX.message('sonetBPFollow' + strFollowNew);
		BX("log_entry_follow_" + log_id, true).setAttribute("data-follow", strFollowNew);
	}
				
	BX.ajax({
		url: BX.message('sonetBPSetPath'),
		method: 'POST',
		dataType: 'json',
		data: {
			"log_id": log_id,
			"action": "change_follow",
			"follow": strFollowNew,
			"sessid": BX.bitrix_sessid(),
			"site": BX.message('sonetBPSiteId')
		},
		onsuccess: function(data) {
			if (
				data["SUCCESS"] != "Y"
				&& BX("log_entry_follow_" + log_id, true)
			)
			{
				BX.findChild(BX("log_entry_follow_" + log_id, true), { tagName: 'a' }).innerHTML = BX.message('sonetBPFollow' + strFollowOld);
				BX("log_entry_follow_" + log_id, true).setAttribute("data-follow", strFollowOld);
			}
		},
		onfailure: function(data) {
			if (BX("log_entry_follow_" +log_id, true))
			{
				BX.findChild(BX("log_entry_follow_" + log_id, true), { tagName: 'a' }).innerHTML = BX.message('sonetBPFollow' + strFollowOld);
				BX("log_entry_follow_" + log_id, true).setAttribute("data-follow", strFollowOld);
			}		
		}
	});
	return false;
}

(function() {
	if (!!window.SBPImpPost)
		return false;
	window.SBPImpPost = function(node) {
		if (node.getAttribute("sbpimppost") == "Y")
			return false;
		this.CID = 'sbpimppost' + new Date().getTime();
		this.busy = false;

		this.node = node;
		this.btn = node.parentNode;
		this.block = node.parentNode.parentNode;

		this.postId = node.getAttribute("bx-blog-post-id");
		node.setAttribute("sbpimppost", "Y");

		BX.onCustomEvent(this.node, "onInit", [this]);
		if (this.postId > 0)
			this.onclick();

		return false;
	}
	window.SBPImpPost.prototype.onclick = function(){
		this.sendData();
	}
	window.SBPImpPost.prototype.showClick = function(){
		var start_anim = this.btn.offsetWidth,
			text = BX.message('BLOG_ALREADY_READ'),
			text_block = BX.create('span',{ props:{className:'have-read-text-block'}, html:'<i></i>' + text + '<span class="feed-imp-post-footer-comma">,</span>' });

		this.block.style.minWidth =  this.btn.offsetWidth-27 + 'px';

		var easing = new BX.easing({
			duration : 250,
			start : { width : start_anim },
			finish : { width : 1 },
			transition : BX.easing.makeEaseOut(BX.easing.transitions.quad),
			step : BX.delegate(function(state) { this.btn.style.width = state.width +'px' }, this),
			complete : BX.delegate(function(){
				this.btn.innerHTML = '';
				this.btn.appendChild(text_block);
				var width_2 = text_block.offsetWidth,
					easing_2 = new BX.easing({
						duration : 300,
						start : { width_2:0 },
						finish : { width_2:width_2 },
						transition : BX.easing.makeEaseOut(BX.easing.transitions.quad),
						step : BX.delegate(function(state){ this.btn.style.width = state.width_2 + 'px'; }, this)
					});
					easing_2.animate();
				}, this)
		});
		easing.animate();
	}
	window.SBPImpPost.prototype.wait = function(status){
		status = (status == 'show' ? 'show' : 'hide');
		if (status == 'show')
		{
			this.node.disabled = true;
			BX.adjust(this.node, {style : {position : "relative"},
				children : [
					BX.create('DIV', {
						attrs : {className: 'mpf-load-img', "mpf-load-img" : "Y"},
						style : { position: "absolute", top : 0, left : 0, width: "100%" }
					})
				]});
		}
		else
		{
			if (!!this.node.lastChild && this.node.lastChild.hasAttribute("mpf-load-img"))
			{
				BX.remove(this.node.lastChild);
			}
		}
	}
	window.SBPImpPost.prototype.sendData = function(){
		if (this.busy)
			return false;
		this.busy = true;
		window['node'] = this.node;
		window['obj'] = this;
		this.wait('show');
		var data = {
			options : [{ post_id : this.postId, name : "BLOG_POST_IMPRTNT", value : "Y"}],
			sessid : BX.bitrix_sessid()},
			url = this.node.href;

		BX.onCustomEvent(this.node, "onSend", [data]);
		data = BX.ajax.prepareData(data);
		if (data)
		{
			url += (url.indexOf('?') !== -1 ? "&" : "?") + data;
			data = '';
		}

		BX.ajax({
			'method': 'GET',
			'url': url,
			'dataType': 'json',
			'onsuccess': BX.delegate(function(data){
				this.busy = false;
				this.wait('hide');
				this.showClick();
				BX.onCustomEvent(this.node, "onUserVote", [data]);
				BX.onCustomEvent("onImportantPostRead", [this.postId, this.CID]);
			}, this),
			'onfailure': BX.delegate(function(data){ this.busy = false; this.wait('hide');}, this)
		});
	}

	top.SBPImpPostCounter = function(node, postId, params) {
		this.parentNode = node;
		this.node = BX.findChild(node, {"tagName" : "A"});
		if (!this.node)
			return false;

		BX.addCustomEvent(this.node, "onUserVote", BX.delegate(function(data){this.change(data);}, this));

		this.parentNode.SBPImpPostCounter = this;

		this.node.setAttribute("status", "ready");
		this.node.setAttribute("inumpage", 0);

		this.postId = postId;
		this.popup = null;
		this.data = [];
		BX.bind(node, "click", BX.proxy(function(){ this.get(); }, this));
		BX.bind(node, "mouseover", BX.proxy(function(e){this.init(e);}, this));
		BX.bind(node, "mouseout", BX.proxy(function(e){this.init(e);}, this));

		this.pathToUser = params['pathToUser'];
		this.nameTemplate = params['nameTemplate'];

		this.onPullEvent = BX.delegate(function(command, params){
			if (command == 'read' && !!params && params["POST_ID"] == this.postId)
			{
				if (!!params["data"])
				{
					this.change(params["data"]);
				}
			}
		}, this);
		BX.addCustomEvent("onPullEvent-socialnetwork", this.onPullEvent);
	}
	top.SBPImpPostCounter.prototype.click = function(obj) {
		obj.uController = this;
		BX.addCustomEvent(obj.node, "onUserVote", BX.proxy(this.change, this));
		BX.addCustomEvent(obj.node, "onSend", BX.proxy(function(data){
			data["PATH_TO_USER"] = this.pathToUser;
			data["NAME_TEMPLATE"] = this.nameTemplate;
			data["iNumPage"] = 0;
			data["ID"] = this.postId;
			data["post_id"] = this.postId;
			data["name"] = "BLOG_POST_IMPRTNT";
			data["value"] = "Y";
			data["return"] = "users";
		}, this));
		this.btnObj = obj;
	}

	top.SBPImpPostCounter.prototype.change = function(data) {
		if (!!data && !!data.items)
		{
			var res = false;
			this.data = [];
			for (var ii in data.items) {
				this.data.push(data.items[ii]);
			}
			if (data["StatusPage"] == "done")
				this.node.setAttribute("inumpage", "done");
			else
				this.node.setAttribute("inumpage", 1);
			BX.adjust(this.parentNode, {style : {display : "inline-block"}});
		}
		else
		{
			this.node.setAttribute("inumpage", "done");
			BX.hide(this.parentNode);
		}
		this.node.firstChild.innerHTML = data["RecordCount"];
	}
	top.SBPImpPostCounter.prototype.init = function(e) {
		if (!!this.node.timeoutOver){
			clearTimeout(this.node.timeoutOver);
			this.node.timeoutOver = false;
		}
		if (e.type == 'mouseover'){
			if (!this.node.mouseoverFunc) {
				this.node.mouseoverFunc = BX.delegate(function(){
					this.get();
					if (this.popup){
						BX.bind(
							this.popup.popupContainer,
							'mouseout',
							BX.proxy(
								function()
								{
									this.popup.timeoutOut = setTimeout(
										BX.proxy(
											function()
											{
												if (!!this.popup) {
													this.popup.close();
												}
											}, this),
										400
									);
								},
								this
							)
						);
						BX.bind(
							this.popup.popupContainer,
							'mouseover' ,
							BX.proxy(
								function()
								{
									if (this.popup.timeoutOut)
										clearTimeout(this.popup.timeoutOut);
								},
								this
							)
						);
					}
				}, this)
			}
			this.node.timeoutOver = setTimeout(this.node.mouseoverFunc, 400);
		}
	}

	top.SBPImpPostCounter.prototype.get = function() {
		if (this.node.getAttribute("inumpage") != "done")
			this.node.setAttribute("inumpage", (parseInt(this.node.getAttribute("inumpage")) + 1));
		this.show();
		if (this.data.length > 0) {
			this.make((this.node.getAttribute("inumpage") != "done"));
		}

		if (this.node.getAttribute("inumpage") != "done") {
			this.node.setAttribute("status", "busy");
			BX.ajax({
				url: "/bitrix/components/bitrix/socialnetwork.blog.blog/users.php",
				method: 'POST',
				dataType: 'json',
				data: {
					'ID' : this.postId,
					'post_id' : this.postId,
					'name' : "BLOG_POST_IMPRTNT",
					'value' : "Y",
					'iNumPage' : this.node.getAttribute("inumpage"),
					'PATH_TO_USER' : this.pathToUser,
					'NAME_TEMPLATE' : this.nameTemplate,
					'sessid': BX.bitrix_sessid()
				},
				onsuccess: BX.proxy(function(data){
					if (!!data && !!data.items)
					{
						var res = false;
						for (var ii in data.items) {
							this.data.push(data.items[ii]);
						}
						if (data.StatusPage == "done")
							this.node.setAttribute("inumpage", "done");

						this.make((this.node.getAttribute("inumpage") != "done"));
					}
					else
					{
						this.node.setAttribute("inumpage", "done");
					}
					this.node.firstChild.innerHTML = data["RecordCount"];
					this.node.setAttribute("status", "ready");
				}, this),
				onfailure: BX.proxy(function(data){ this.node.setAttribute("status", "ready"); }, this)
			});
		}
	}
	top.SBPImpPostCounter.prototype.show = function()
	{
		if (this.popup != null)
			this.popup.close();

		if (this.popup == null)
		{
			this.popup = new BX.PopupWindow('bx-vote-popup-cont-' + this.postId, this.node, {
				lightShadow : true,
				offsetTop: -2,
				offsetLeft: 3,
				autoHide: true,
				closeByEsc: true,
				bindOptions: {position: "top"},
				events : {
					onPopupClose : function() { this.destroy() },
					onPopupDestroy : BX.proxy(function() { this.popup = null; }, this)
				},
				content : BX.create("SPAN", { props: {className: "bx-ilike-wait"}})
			});

			this.popup.isNew = true;
			this.popup.show();
		}
		this.popup.setAngle({position:'bottom'});

		this.popup.bindOptions.forceBindPosition = true;
		this.popup.adjustPosition();
		this.popup.bindOptions.forceBindPosition = false;
	}
	top.SBPImpPostCounter.prototype.make = function(needToCheckData)
	{
		if (!this.popup)
			return true;
		needToCheckData = (needToCheckData === false ? false : true);

		var
			res1 = (this.popup && this.popup.contentContainer ? this.popup.contentContainer : BX('popup-window-content-bx-vote-popup-cont-' + this.postId)),
			node = false, res = false, data = this.data;
		if (this.popup.isNew)
		{
			var
				node = BX.create("SPAN", {
						props : {className : "bx-ilike-popup"},
						children : [
							BX.create("SPAN", {
								props : {className: "bx-ilike-bottom_scroll"}
							})
						]
					}
				),
				res = BX.create("SPAN", {
					props : {className : "bx-ilike-wrap-block"},
					children : [
						node
					]
				});
		}
		else
		{
			node = BX.findChild(this.popup.contentContainer, {className : "bx-ilike-popup"}, true);
		}
		if (!!node)
		{
			for (var i in data)
			{
				if (!BX.findChild(node, {tag : "A", attr : {id : ("u" + data[i]['ID'])}}, true))
				{
					node.appendChild(
						BX.create("A", {
							attrs : {id : ("u" + data[i]['ID'])},
							props: {href:data[i]['URL'], target: "_blank", className: "bx-ilike-popup-img"},
							text: "",
							children: [
								BX.create("SPAN", {
										props: {className: "bx-ilike-popup-avatar"},
										html : data[i]['PHOTO']
									}
								),
								BX.create("SPAN", {
										props: {className: "bx-ilike-popup-name"},
										html : data[i]['FULL_NAME']
									}
								)
							]
						})
					);
				}
			}
			if (needToCheckData)
			{
				BX.bind(node, 'scroll' , BX.delegate(this.popupScrollCheck, this));
			}
		}
		if (this.popup.isNew)
		{
			this.popup.isNew = false;
			if (!!res1)
			{
				try{
					res1.removeChild(res1.firstChild);
				} catch(e) {}
				res1.appendChild(res);
			}
		}
		if (this.popup != null)
		{
			this.popup.bindOptions.forceBindPosition = true;
			this.popup.adjustPosition();
			this.popup.bindOptions.forceBindPosition = false;
		}
	}

	top.SBPImpPostCounter.prototype.popupScrollCheck = function()
	{
		var res = BX.proxy_context;
		if (res.scrollTop > (res.scrollHeight - res.offsetHeight) / 1.5)
		{
			BX.unbind(res, 'scroll' , BX.delegate(this.popupScrollCheck, this));
			this.get();
		}
	}
})(window);

window.BXfpdPostSelectCallback = function(item, type, search)
{
	if(!BX.findChild(BX('feed-add-post-destination-item-post'), { attr : { 'data-id' : item.id }}, false, false))
	{
		var type1 = type;
		prefix = 'S';
		if (type == 'sonetgroups')
			prefix = 'SG';
		else if (type == 'groups')
		{
			prefix = 'UA';
			type1 = 'all-users';
		}
		else if (type == 'users')
			prefix = 'U';
		else if (type == 'department')
			prefix = 'DR';

		var stl = (type == 'sonetgroups' && typeof window['arExtranetGroupID'] != 'undefined' && BX.util.in_array(item.entityId, window['arExtranetGroupID']) ? ' feed-add-post-destination-extranet' : '');

		BX('feed-add-post-destination-item-post').appendChild(BX.create("span", { 
			attrs : { 
				'data-id' : item.id 
			}, 
			props : { 
				className : "feed-add-post-destination feed-add-post-destination-"+type1+stl
			}, 
			children: [
				BX.create("input", { attrs : { 'type' : 'hidden', 'name' : 'SPERM['+prefix+'][]', 'value' : item.id }}),
				BX.create("span", { props : { 'className' : "feed-add-post-destination-text" }, html : item.name}),
				BX.create("span", { 
					props : { 
						'className' : "feed-add-post-del-but"
					}, 
					events : {
						'click' : function(e){
							BX.SocNetLogDestination.deleteItem(item.id, type, BXSocNetLogDestinationFormNamePost);
							BX.PreventDefault(e);
						}, 
						'mouseover' : function(){
							BX.addClass(this.parentNode, 'feed-add-post-destination-hover');
						}, 
						'mouseout' : function(){
							BX.removeClass(this.parentNode, 'feed-add-post-destination-hover');
						}
					}
				})
			]}));
	}

	BX('feed-add-post-destination-input-post').value = '';

	BX.SocNetLogDestination.BXfpSetLinkName({
		formName: window.BXSocNetLogDestinationFormNamePost,
		tagInputName: 'bx-destination-tag-post',
		tagLink1: BX.message('BX_FPD_LINK_1'),
		tagLink2: BX.message('BX_FPD_LINK_2')
	});
}

window.BXfpdPostClear = function()
{
	var elements = BX.findChildren(BX('feed-add-post-destination-item-post'), {className : 'feed-add-post-destination'}, true);
	if (elements != null)
	{
		for (var j = 0; j < elements.length; j++)
		{
			BX.remove(elements[j]);
		}
	}
	BX('feed-add-post-destination-input-post').value = '';

	BX.SocNetLogDestination.BXfpSetLinkName({
		formName: window.BXSocNetLogDestinationFormNamePost,
		tagInputName: 'bx-destination-tag-post',
		tagLink1: BX.message('BX_FPD_LINK_1'),
		tagLink2: BX.message('BX_FPD_LINK_2')
	});
}

window.showSharing = function(postId, userId)
{
	BXfpdPostClear();
	BX('sharePostId').value = postId;
	BX('shareUserId').value = userId;

	BX.SocNetLogDestination.obItemsSelected[BXSocNetLogDestinationFormNamePost] = {};
	if(window["postDest"+postId])
	{
		for (var i = 0; i < window["postDest"+postId].length; i++) 
		{
			if(BX.SocNetLogDestination.obItemsSelected[BXSocNetLogDestinationFormNamePost])
			{
				BX.SocNetLogDestination.obItemsSelected[BXSocNetLogDestinationFormNamePost][window["postDest"+postId][i].id] = window["postDest"+postId][i].type;
			}

			if(!BX.SocNetLogDestination.obItems[BXSocNetLogDestinationFormNamePost][window["postDest"+postId][i].type][window["postDest"+postId][i].id])
			{
				BX.SocNetLogDestination.obItems[BXSocNetLogDestinationFormNamePost][window["postDest"+postId][i].type][window["postDest"+postId][i].id] = {
					avatar: '', entityId: window["postDest"+postId][i].entityId, id: window["postDest"+postId][i].id, name: window["postDest"+postId][i].name
				};
			}
		};

		if(BXSocNetLogDestinationFormNamePost)
			BX.SocNetLogDestination.reInit(BXSocNetLogDestinationFormNamePost);

		var elements = BX.findChildren(BX('feed-add-post-destination-item-post'), {className : 'feed-add-post-destination'}, true);
		if (elements != null)
		{
			for (var j = 0; j < elements.length; j++)
			{
				BX.addClass(elements[j], 'feed-add-post-destination-undelete');
				BX.remove(elements[j].lastChild);
			}
		}

		var destForm = BX('destination-sharing');

		if (BX('blg-post-destcont-'+postId))
		{
			BX('blg-post-destcont-'+postId).appendChild(destForm);
		}

		destForm.style.height = 0;
		destForm.style.opacity = 0;
		destForm.style.overflow = 'hidden';
		destForm.style.display = 'inline-block';

		(new BX.easing({
			duration : 500,
			start : { opacity : 0, height : 0},
			finish : { opacity: 100, height : destForm.scrollHeight-40},
			transition : BX.easing.makeEaseOut(BX.easing.transitions.quad),
			step : function(state){
				destForm.style.height = state.height + "px";
				destForm.style.opacity = state.opacity / 100;
			},
			complete : function(){
				destForm.style.cssText = '';
			}
		})).animate();
	}
}

window.closeSharing = function()
{
	var destForm = BX('destination-sharing');

	if (BX('sharePostSubmitButton'))
	{
		BX.removeClass(BX('sharePostSubmitButton'), 'feed-add-button-load');
	}

	(new BX.easing({
		duration : 500,
		start : { opacity: 100, height : destForm.scrollHeight-40},
		finish : { opacity : 0, height : 0},
		transition : BX.easing.makeEaseOut(BX.easing.transitions.quad),
		step : function(state){
			destForm.style.height = state.height + "px";
			destForm.style.opacity = state.opacity / 100;
		},
		complete : function(){
			BX.hide(destForm);
		}
	})).animate();
}

window.sharingPost = function()
{
	var postId = BX('sharePostId').value;
	var userId = BX('shareUserId').value;
	var shareForm = BX('blogShare');
	var actUrl = socBPDest.shareUrl.replace(/#post_id#/, postId).replace(/#user_id#/, userId);

	if (BX('sharePostSubmitButton'))
	{
		BX.addClass(BX('sharePostSubmitButton'), 'feed-add-button-load');
	}

	var elements = BX.findChildren(BX('feed-add-post-destination-item-post'), {className : 'feed-add-post-destination'}, true);
	if (elements != null)
	{
		var hiddenDest = BX('blog-destination-hidden-'+postId);
		if(!hiddenDest)
		{
			var el = BX.findChildren(BX('blg-post-img-'+postId), {className : 'feed-add-post-destination-new'}, true);
			var lastDest = el[el.length-1];
		}

		for (var j = 0; j < elements.length; j++)
		{
			if(!BX.hasClass(elements[j], 'feed-add-post-destination-undelete'))
			{
				var name = BX.findChild(elements[j], {className: 'feed-add-post-destination-text' }, false, false).innerHTML;
				var obj = BX.findChild(elements[j], {tag: 'input' }, false, false);
				var id = obj.value;

				var type;
				if(obj.name == "SPERM[SG][]")
					type = 'sonetgroups';
				else if(obj.name == "SPERM[DR][]")
					type = 'department';
				else if(obj.name == "SPERM[G][]")
					type = 'groups';
				else if(obj.name == "SPERM[U][]")
					type = 'users';
				else if(obj.name == "SPERM[UA][]")
					type = 'groups';

				if (type.length > 0)
				{
					window["postDest" + postId].push({
						id: id, 
						name: name, 
						type: type
					});
					var destText = BX.create("span", { children: [
								BX.create("span", {html : ', '}),
								BX.create("a", {
									props: {
										'className': "feed-add-post-destination-new"
									},
									href: '', 
									html : name
								})
								]});
					if(hiddenDest)
					{
						hiddenDest.appendChild(destText);
					}
					else if(lastDest)
					{
						BX(lastDest.parentNode).insertBefore(destText, lastDest.nextSibling);
					}
				}
			}
		}
	}

	shareForm.action = actUrl;
	shareForm.target = '';

	var i, s = "";
	var n = shareForm.elements.length;

	var delim = '';
	for(i=0; i<n; i++)
	{
		if (s != '') delim = '&';
		var el = shareForm.elements[i];
		if (el.disabled)
			continue;

		switch(el.type.toLowerCase())
		{
			case 'text':
			case 'hidden':
				s += delim + el.name + '=' + BX.util.urlencode(el.value);
				break;
			default:
				break;
		}
	}
	s += "&save=Y"

	BX.ajax({
		'method': 'POST',
		'dataType': 'html',
		'url': actUrl,
		'data': s,
		'async': true,
		'processData': false

	});
	closeSharing();
}