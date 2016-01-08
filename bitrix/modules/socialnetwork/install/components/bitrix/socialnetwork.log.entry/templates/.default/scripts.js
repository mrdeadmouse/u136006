window["__logCommentsListRedefine"] = function(ENTITY_XML_ID, node_quote_id, author_id)
{
	if (!!window["UC"] && !!window["UC"][ENTITY_XML_ID])
	{
		window["UC"][ENTITY_XML_ID].send = function() {
			this.logID = this.nav.getAttribute("bx-sonet-nav-event-id");
			this.commentID = this.nav.getAttribute("bx-sonet-nav-comment-id");
			this.commentTS = this.nav.getAttribute("bx-sonet-nav-comment-ts");
			this.entityType = this.nav.getAttribute("bx-sonet-nav-entity-type");
			this.ts = this.nav.getAttribute("bx-sonet-nav-ts");
			this.bFollow = this.nav.getAttribute("bx-sonet-nav-follow");
			this.status = "busy";
			this.pageNumber = this.nav.getAttribute("bx-sonet-nav-page-number");

			BX.addClass(this.nav, "feed-com-all-hover");
			BX.ajax({
				method: 'GET',
				url: BX.message('sonetLEGetPath') + '?' +
					BX.ajax.prepareData({
						sessid : BX.bitrix_sessid(),
						r : Math.floor(Math.random() * 1000),
						action : "get_comments",
						lang : BX.message('sonetLLangId'),
						site : BX.message('sonetLSiteId'),
						stid : BX.message('sonetLSiteTemplateId'),
						nt : BX.message('sonetLNameTemplate'),
						sl : BX.message('sonetLShowLogin'),
						dtf : BX.message('sonetLDateTimeFormat'),
						as : BX.message('sonetLAvatarSizeComment'),
						p_user : BX.message('sonetLPathToUser'),
						p_group : BX.message('sonetLPathToGroup'),
						p_dep : BX.message('sonetLPathToDepartment'),
						p_le : BX.message('sonetLEPath'),
						p_smile : BX.message('sonetLPathToSmile'),
						logid : this.logID,
						commentID : this.commentID,
						commentTS : this.commentTS,
						et : this.entityType,
						exmlid : ENTITY_XML_ID,
						PAGEN_1: this.pageNumber
					}),
				dataType: 'json',
				onsuccess: BX.proxy(function(data) {
					if (!(typeof(data) == "object" && !!data) || data[0] == '*')
					{
						BX.debug(data);
					}
					else
					{
						var
							arComments = data["arComments"],
							messageList = '';
						for (var i in arComments)
						{
							var
								anchor_id = Math.floor(Math.random()*100000) + 1,
								commF = arComments[i]["EVENT_FORMATTED"],
								comm = arComments[i],
								ratingNode = (!!window["__logBuildRating"] ? window["__logBuildRating"](comm["EVENT"], commF, anchor_id) : null),
								thisId = (!!comm["EVENT"]["SOURCE_ID"] ? comm["EVENT"]["SOURCE_ID"] : comm["EVENT"]["ID"]),
								res = {
									"ID" : thisId, // integer
									"ENTITY_XML_ID" : this.ENTITY_XML_ID, // string
									"FULL_ID" : [this.ENTITY_XML_ID, thisId],
									"NEW" : (this.bFollow && parseInt(comm["LOG_DATE_TS"]) > this.ts &&
										comm["EVENT"]["USER_ID"] != BX.message('sonetLCurrentUserID') ? "Y" : "N"), //"Y" | "N"
									"APPROVED" : "Y", //"Y" | "N"
									"POST_TIMESTAMP" : comm["LOG_DATE_TS"],
									"POST_TIME" : comm["LOG_TIME_FORMAT"],
									"POST_DATE" : comm["LOG_DATETIME_FORMAT"],
									"~POST_MESSAGE_TEXT" : commF["MESSAGE"],
									"POST_MESSAGE_TEXT" : commF["FULL_MESSAGE_CUT"],
									"URL" : {
										"LINK" : comm["URL"],
										"EDIT" : comm["URL_EDIT"],
										"DELETE" : BX.message('sonetLESetPath') + '?lang=' + BX.message('sonetLLangId') + '&action=delete_comment&delete_comment_id=' + comm["EVENT"]["ID"] + '&post_id=' + this.logID + '&site=' + BX.message('sonetLSiteId')
									},
									"AUTHOR" : {
										"ID" : comm["EVENT"]["USER_ID"],
										"NAME" : comm["CREATED_BY"]["FORMATTED"],
										"URL" : comm["CREATED_BY"]["URL"],
										"AVATAR" : comm["AVATAR_SRC"] },
									"BEFORE_ACTIONS" : (!!ratingNode ? ratingNode : ''),
									"AFTER" : commF["UF"],
									"PANELS" : comm["PANELS"]
								};
							messageList += '<div id="record-' + this.ENTITY_XML_ID +'-' + thisId + '-cover" class="feed-com-block-cover">' + fcParseTemplate({messageFields : res}) + '</div>';

							BX.onCustomEvent(window, "OnUCAddEntitiesCorrespondence", [ENTITY_XML_ID + '-' + comm["EVENT"]["SOURCE_ID"], [this.logID, comm["EVENT"]["ID"]]]);
						}
					}
					this.build({
						status : true,
						navigation : (typeof data["navigation"] !== 'undefined' && data["navigation"].length > 0 ? data["navigation"] : ''),
						navigationNextPageNum : (typeof data["navigationNextPageNum"] !== 'undefined' && parseInt(data["navigationNextPageNum"]) > 0 ? parseInt(data["navigationNextPageNum"]) : null),
						navigationCounter : (typeof data["navigationCounter"] !== 'undefined' && parseInt(data["navigationCounter"]) > 0 ? parseInt(data["navigationCounter"]) : null),
						messageList : messageList
					});
				}, this),
				onfailure: BX.delegate(function(data){ this.status = "ready"; BX.debug(data); }, this)
			});
			return false;
		}
		
		window["UC"][ENTITY_XML_ID].build = function(data)
		{
			this.status = "ready";
			this.wait("hide");
			BX.removeClass(this.nav, "feed-com-all-hover");
			if (!!data && data["status"] == true)
			{
				if (
					!!data["navigationNextPageNum"]
					&& !!data["navigationCounter"]
					&& parseInt(data["navigationNextPageNum"]) > 0
					&& parseInt(data["navigationCounter"]) > 0
				)
				{
					this.nav.setAttribute("bx-sonet-nav-page-number", parseInt(data["navigationNextPageNum"]));
					BX.findChild(this.nav, {className: 'feed-com-all-cnt'}, true, false).innerHTML = parseInt(data["navigationCounter"]);
				}
				else
				{
					BX.adjust(this.nav, {attrs : {href : "javascript:void(0);", "bx-visibility-status" : "visible"}, html : BX.message("BLOG_C_HIDE")});
					this.status = "done";
				}

				var res = (!!data["navigation"] ? BX.create('DIV', {html : data["navigation"]}) : null),
					ob = BX.processHTML(data["messageList"], false);

				var offsetHeight = this.container.offsetHeight;
				if (this.order == "ASC")
					this.container.innerHTML = this.container.innerHTML + ob.HTML;
				else
					this.container.innerHTML = ob.HTML + this.container.innerHTML;
				BX.onCustomEvent(window, "OnUCFeedChanged", [[this.ENTITY_XML_ID, this.mid]]);

				this.display('show', offsetHeight);

				BX.defer(function(){
					BX.ajax.processScripts(ob.SCRIPT);
				})();
			}
		}
	}

	if (!!window.mplCheckForQuote)
		BX.bind(BX(node_quote_id), "mouseup", function(e){ mplCheckForQuote(e, this, ENTITY_XML_ID, author_id) });
}

window["__logBuildRating"] = function(comm, commFormat, anchor_id) {
	var ratingNode = '';
		anchor_id = (!!anchor_id ? anchor_id : (Math.floor(Math.random()*100000) + 1));
	if ( BX.message("sonetLShowRating") == 'Y' &&
		!!comm["RATING_TYPE_ID"] > 0 && comm["RATING_ENTITY_ID"] > 0 &&
		(BX.message("sonetLRatingType") == "like" && !!window["RatingLike"] || BX.message("sonetLRatingType") == "standart_text" && !!window["Rating"]))
	{
		if (BX.message("sonetLRatingType") == "like")
		{
			var
				you_like_class = (comm["RATING_USER_VOTE_VALUE"] > 0) ? " bx-you-like" : "",
				you_like_text = (comm["RATING_USER_VOTE_VALUE"] > 0) ? BX.message("sonetLTextLikeN") : BX.message("sonetLTextLikeY"),
				vote_text = null;
			if (!!commFormat["ALLOW_VOTE"] &&
				!!commFormat["ALLOW_VOTE"]["RESULT"])
				vote_text = BX.create('span', {
					props: {
						'className': 'bx-ilike-text'
					},
					html: you_like_text
				});

			ratingNode = BX.create('span', {
				attrs : {
					id : 'sonet-rating-' + comm["RATING_TYPE_ID"] + '-' + comm["RATING_ENTITY_ID"] + '-' + anchor_id
				},
				props: {
					'className': 'sonet-log-comment-like rating_vote_text'
				},
				children: [
					BX.create('span', {
						props: {
							'className': 'ilike-light'
						},
						children: [
							BX.create('span', {
								props: {
									'id': 'bx-ilike-button-' + comm["RATING_TYPE_ID"] + '-' + comm["RATING_ENTITY_ID"] + '-' + anchor_id,
									'className': 'bx-ilike-button'
								},
								children: [
									BX.create('span', {
										props: {
											'className': 'bx-ilike-right-wrap' + you_like_class
										},
										children: [
											BX.create('span', {
												props: {
													'className': 'bx-ilike-right'
												},
												html: comm["RATING_TOTAL_POSITIVE_VOTES"]
											})
										]
									}),
									BX.create('span', {
										props: {
											'className': 'bx-ilike-left-wrap'
										},
										children: [
											vote_text
										]
									})
								]
							}),
							BX.create('span', {
								props: {
									'id': 'bx-ilike-popup-cont-' + comm["RATING_TYPE_ID"] + '-' + comm["RATING_ENTITY_ID"] + '-' + anchor_id,
									'className': 'bx-ilike-wrap-block'
								},
								style: {
									'display': 'none'
								},
								children: [
									BX.create('span', {
										props: {
											'className': 'bx-ilike-popup'
										},
										children: [
											BX.create('span', {
												props: {
													'className': 'bx-ilike-wait'
												}
											})
										]
									})
								]
							})
						]
					})
				]
			});
		}
		else if (BX.message("sonetLRatingType") == "standart_text")
		{
			ratingNode = BX.create('span', {
				attrs : {
					id : 'sonet-rating-' + comm["RATING_TYPE_ID"] + '-' + comm["RATING_ENTITY_ID"] + '-' + anchor_id
				},
				props: {
					'className': 'sonet-log-comment-like rating_vote_text'
				},
				children: [
					BX.create('span', {
						props: {
							'className': 'bx-rating' + (!commFormat["ALLOW_VOTE"]['RESULT'] ? ' bx-rating-disabled' : '') + (comm["RATING_USER_VOTE_VALUE"] != 0 ? ' bx-rating-active' : ''),
							'id': 'bx-rating-' + comm["RATING_TYPE_ID"] + '-' + comm["RATING_ENTITY_ID"] + '-' + anchor_id,
							'title': (!commFormat["ALLOW_VOTE"]['RESULT'] ? commFormat["ERROR_MSG"] : '')
						},
						children: [
							BX.create('span', {
								props: {
									'className': 'bx-rating-absolute'
								},
								children: [
									BX.create('span', {
										props: {
											'className': 'bx-rating-question'
										},
										html: (!commFormat["ALLOW_VOTE"]['RESULT'] ? BX.message("sonetLTextDenied") : BX.message("sonetLTextAvailable"))
									}),
									BX.create('span', {
										props: {
											'className': 'bx-rating-yes ' +  (comm["RATING_USER_VOTE_VALUE"] > 0 ? '  bx-rating-yes-active' : ''),
											'title': (comm["RATING_USER_VOTE_VALUE"] > 0 ? BX.message("sonetLTextCancel") : BX.message("sonetLTextPlus"))
										},
										children: [
											BX.create('a', {
												props: {
													'className': 'bx-rating-yes-count',
													'href': '#like'
												},
												html: ""+parseInt(comm["RATING_TOTAL_POSITIVE_VOTES"])
											}),
											BX.create('a', {
												props: {
													'className': 'bx-rating-yes-text',
													'href': '#like'
												},
												html: BX.message("sonetLTextRatingY")
											})
										]
									}),
									BX.create('span', {
										props: {
											'className': 'bx-rating-separator'
										},
										html: '/'
									}),
									BX.create('span', {
										props: {
											'className': 'bx-rating-no ' +  (comm["RATING_USER_VOTE_VALUE"] < 0 ? '  bx-rating-no-active' : ''),
											'title': (comm["RATING_USER_VOTE_VALUE"] < 0 ? BX.message("sonetLTextCancel") : BX.message("sonetLTextMinus"))
										},
										children: [
											BX.create('a', {
												props: {
													'className': 'bx-rating-no-count',
													'href': '#dislike'
												},
												html: ""+parseInt(comm["RATING_TOTAL_NEGATIVE_VOTES"])
											}),
											BX.create('a', {
												props: {
													'className': 'bx-rating-no-text',
													'href': '#dislike'
												},
												html: BX.message("sonetLTextRatingN")
											})
										]
									})
								]
							})
						]
					}),
					BX.create('span', {
						props: {
							'id': 'bx-rating-popup-cont-' + comm["RATING_TYPE_ID"] + '-' + comm["RATING_ENTITY_ID"] + '-' + anchor_id + '-plus'
						},
						style: {
							'display': 'none'
						},
						children: [
							BX.create('span', {
								props: {
									'className': 'bx-ilike-popup  bx-rating-popup'
								},
								children: [
									BX.create('span', {
										props: {
											'className': 'bx-ilike-wait'
										}
									})
								]
							})
						]
					}),
					BX.create('span', {
						props: {
							'id': 'bx-rating-popup-cont-' + comm["RATING_TYPE_ID"] + '-' + comm["RATING_ENTITY_ID"] + '-' + anchor_id + '-minus'
						},
						style: {
							'display': 'none'
						},
						children: [
							BX.create('span', {
								props: {
									'className': 'bx-ilike-popup  bx-rating-popup'
								},
								children: [
									BX.create('span', {
										props: {
											'className': 'bx-ilike-wait'
										}
									})
								]
							})
						]
					})
				]
			});
		}
	}
	if (!!ratingNode)
	{
		ratingNode = BX.create('span', { children : [ ratingNode ] } );
		ratingNode = ratingNode.innerHTML +
			'<script>window["#OBJ#"].Set("#ID#", "#RATING_TYPE_ID#", #RATING_ENTITY_ID#, "#ALLOW_VOTE#", BX.message("sonetLCurrentUserID"), #TEMPLATE#, "light", BX.message("sonetLPathToUser"));</script>'.
			replace("#OBJ#", (BX.message("sonetLRatingType") == "like" ? "RatingLike" : "Rating")).
			replace("#ID#", comm["RATING_TYPE_ID"] + '-' + comm["RATING_ENTITY_ID"] + '-' + anchor_id).
			replace("#RATING_TYPE_ID#", comm["RATING_TYPE_ID"]).
			replace("#RATING_ENTITY_ID#", comm["RATING_ENTITY_ID"]).
			replace("#ALLOW_VOTE#", (!!commFormat["ALLOW_VOTE"] && !!commFormat["ALLOW_VOTE"]['RESULT'] ? 'Y' : 'N')).
			replace("#TEMPLATE#", (BX.message("sonetLRatingType") == "like" ?
				'{LIKE_Y:BX.message("sonetLTextLikeN"),LIKE_N:BX.message("sonetLTextLikeY"),LIKE_D:BX.message("sonetLTextLikeD")}' :
				'{PLUS:BX.message("sonetLTextPlus"),MINUS:BX.message("sonetLTextMinus"),CANCEL:BX.message("sonetLTextCancel")}'));

	}
	return ratingNode;
}
window["__logShowCommentForm"] = function(xmlId)
{
	if (!!window["UC"][xmlId])
		window["UC"][xmlId].reply();
}

var waitTimeout = null;
var waitDiv = null;
var	waitPopup = null;
var waitTime = 500;


function __logEventExpand(node)
{
	if (BX(node))
	{
		BX(node).style.display = "none";

		var tmpNode = BX.findParent(BX(node), {'tag': 'div', 'className': 'feed-post-text-block'});
		if (tmpNode)
		{
			var contentContrainer = BX.findChild(tmpNode, {'tag': 'div', 'className': 'feed-post-text-block-inner'}, true);
			var contentNode = BX.findChild(tmpNode, {'tag': 'div', 'className': 'feed-post-text-block-inner-inner'}, true);

			if (contentContrainer && contentNode)
			{
				fxStart = 300;
				fxFinish = contentNode.offsetHeight;

				(new BX.fx({
					time: 1.0 * (contentNode.offsetHeight - fxStart) / (1200 - fxStart),
					step: 0.05,
					type: 'linear',
					start: fxStart,
					finish: fxFinish,
					callback: BX.delegate(__logEventExpandSetHeight, contentContrainer),
					callback_complete: BX.delegate(function()
					{
						contentContrainer.style.maxHeight = 'none';
						BX.LazyLoad.showImages(true);
					})
				})).start();
			}
		}
	}
}

function __logCommentExpand(node)
{
	if (!BX.type.isDomNode(node))
		node = BX.proxy_context;

	if (BX(node))
	{
		var topContrainer = BX.findParent(BX(node), {'tag': 'div', 'className': 'feed-com-text'});
		if (topContrainer)
		{
			BX.remove(node);
			var contentContrainer = BX.findChild(topContrainer, {'tag': 'div', 'className': 'feed-com-text-inner'}, true);
			var contentNode = BX.findChild(topContrainer, {'tag': 'div', 'className': 'feed-com-text-inner-inner'}, true);

			if (contentNode && contentContrainer)
			{
				fxStart = 200;
				fxFinish = contentNode.offsetHeight;

				var time = 1.0 * (fxFinish - fxStart) / (2000 - fxStart);
				if(time < 0.3)
					time = 0.3;
				if(time > 0.8)
					time = 0.8;

				(new BX.fx({
					time: time,
					step: 0.05,
					type: 'linear',
					start: fxStart,
					finish: fxFinish,
					callback: BX.delegate(__logEventExpandSetHeight, contentContrainer),
					callback_complete: BX.delegate(function()
					{
						contentContrainer.style.maxHeight = 'none';
					})
				})).start();
			}
		}
	}
}

function __logEventExpandSetHeight(height)
{
	this.style.maxHeight = height + 'px';
}

function __logShowHiddenDestination(log_id, created_by_id, bindElement)
{

	var sonetLXmlHttpSet6 = new XMLHttpRequest();

	sonetLXmlHttpSet6.open("POST", BX.message('sonetLESetPath'), true);
	sonetLXmlHttpSet6.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

	sonetLXmlHttpSet6.onreadystatechange = function()
	{
		if(sonetLXmlHttpSet6.readyState == 4)
		{
			if(sonetLXmlHttpSet6.status == 200)
			{
				var data = LBlock.DataParser(sonetLXmlHttpSet6.responseText);
				if (typeof(data) == "object")
				{
					if (data[0] == '*')
					{
						if (sonetLErrorDiv != null)
						{
							sonetLErrorDiv.style.display = "block";
							sonetLErrorDiv.innerHTML = sonetLXmlHttpSet6.responseText;
						}
						return;
					}
					sonetLXmlHttpSet6.abort();
					var arDestinations = data["arDestinations"];
					
					if (typeof (arDestinations) == "object")
					{
						if (BX(bindElement))
						{
							var cont = bindElement.parentNode;
							cont.removeChild(bindElement);
							var url = '';

							for (var i = 0; i < arDestinations.length; i++)
							{
								if (typeof (arDestinations[i]['TITLE']) != 'undefined' && arDestinations[i]['TITLE'].length > 0)
								{
									cont.appendChild(BX.create('SPAN', {
										html: ',&nbsp;'
									}));

									if (typeof (arDestinations[i]['CRM_PREFIX']) != 'undefined' && arDestinations[i]['CRM_PREFIX'].length > 0)
									{
										cont.appendChild(BX.create('SPAN', {
											props: {
												className: 'feed-add-post-destination-prefix'
											},
											html: arDestinations[i]['CRM_PREFIX'] + ':&nbsp;'
										}));
									}
								
									if (typeof (arDestinations[i]['URL']) != 'undefined' && arDestinations[i]['URL'].length > 0)
									{
										cont.appendChild(BX.create('A', {
											props: {
												className: 'feed-add-post-destination-new' + (typeof (arDestinations[i]['IS_EXTRANET']) != 'undefined' && arDestinations[i]['IS_EXTRANET'] == 'Y' ? ' feed-post-user-name-extranet' : ''),
												'href': arDestinations[i]['URL']
											},
											html: arDestinations[i]['TITLE']
										}));
									}
									else
									{
										cont.appendChild(BX.create('SPAN', {
											props: {
												className: 'feed-add-post-destination-new' + (typeof (arDestinations[i]['IS_EXTRANET']) != 'undefined' && arDestinations[i]['IS_EXTRANET'] == 'Y' ? ' feed-post-user-name-extranet' : '')
											},
											html: arDestinations[i]['TITLE']
										}));
									}
								}
							}

							if (
								data["iDestinationsHidden"] != 'undefined'
								&& parseInt(data["iDestinationsHidden"]) > 0
							)
							{
								data["iDestinationsHidden"] = parseInt(data["iDestinationsHidden"]);
								if (
									(data["iDestinationsHidden"] % 100) > 10
									&& (data["iDestinationsHidden"] % 100) < 20
								)
									var suffix = 5;
								else
									var suffix = data["iDestinationsHidden"] % 10;

								cont.appendChild(BX.create('SPAN', {
									html: '&nbsp;' + BX.message('sonetLDestinationHidden' + suffix).replace("#COUNT#", data["iDestinationsHidden"])
								}));
							}
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

	sonetLXmlHttpSet6.send("r=" + Math.floor(Math.random() * 1000)
		+ "&" + BX.message('sonetLSessid')
		+ "&site=" + BX.util.urlencode(BX.message('SITE_ID'))
		+ "&nt=" + BX.util.urlencode(BX.message('sonetLNameTemplate'))
		+ "&log_id=" + encodeURIComponent(log_id)
		+ (created_by_id ? "&created_by_id=" + encodeURIComponent(created_by_id) : "")
		+ "&p_user=" + BX.util.urlencode(BX.message('sonetLPathToUser'))
		+ "&p_group=" + BX.util.urlencode(BX.message('sonetLPathToGroup'))
		+ "&p_dep=" + BX.util.urlencode(BX.message('sonetLPathToDepartment'))
		+ "&dlim=" + BX.util.urlencode(BX.message('sonetLDestinationLimit'))
		+ "&action=get_more_destination"
	);

}

function __logSetFollow(log_id)
{
	var strFollowOld = (BX("log_entry_follow_" + log_id, true).getAttribute("data-follow") == "Y" ? "Y" : "N");
	var strFollowNew = (strFollowOld == "Y" ? "N" : "Y");	

	if (BX("log_entry_follow_" + log_id, true))
	{
		BX.findChild(BX("log_entry_follow_" + log_id, true), { tagName: 'a' }).innerHTML = BX.message('sonetLFollow' + strFollowNew);
		BX("log_entry_follow_" + log_id, true).setAttribute("data-follow", strFollowNew);
	}
				
	BX.ajax({
		url: BX.message('sonetLSetPath'),
		method: 'POST',
		dataType: 'json',
		data: {
			"log_id": log_id,
			"action": "change_follow",
			"follow": strFollowNew,
			"sessid": BX.bitrix_sessid(),
			"site": BX.message('sonetLSiteId')
		},
		onsuccess: function(data) {
			if (
				data["SUCCESS"] != "Y"
				&& BX("log_entry_follow_" + log_id, true)
			)
			{
				BX.findChild(BX("log_entry_follow_" + log_id, true), { tagName: 'a' }).innerHTML = BX.message('sonetLFollow' + strFollowOld);
				BX("log_entry_follow_" + log_id, true).setAttribute("data-follow", strFollowOld);
			}
		},
		onfailure: function(data) {
			if (BX("log_entry_follow_" +log_id, true))
			{
				BX.findChild(BX("log_entry_follow_" + log_id, true), { tagName: 'a' }).innerHTML = BX.message('sonetLFollow' + strFollowOld);
				BX("log_entry_follow_" + log_id, true).setAttribute("data-follow", strFollowOld);
			}		
		}
	});
	return false;
}

function __logRefreshEntry(params)
{
	var entryNode = (params.node !== undefined ? BX(params.node) : false)
	var logId = (params.logId !== undefined ? parseInt(params.logId) : 0)

	if (
		!entryNode
		|| logId <= 0
		|| BX.message('sonetLEPath') === undefined
	)
	{
		return;
	}

	BX.ajax({
		url: BX.message('sonetLEPath').replace("#log_id#", logId),
		method: 'POST',
		dataType: 'json',
		data: {
			"log_id": logId,
			"action": "get_entry"
		},
		onsuccess: function(data) {
			if (data["ENTRY_HTML"] !== undefined)
			{
				BX.cleanNode(entryNode);
				entryNode.innerHTML = data["ENTRY_HTML"];
				var ob = BX.processHTML(entryNode.innerHTML, true);
				var scripts = ob.SCRIPT;
				BX.ajax.processScripts(scripts, true);
			}
		},
		onfailure: function(data) {}
	});
	return false;
}

window.__logEditComment = function(entityXmlId, key, postId)
{
	BX.ajax({
		url: BX.message('sonetLESetPath'),
		method: 'POST',
		dataType: 'json',
		data: {
			"comment_id": key,
			"post_id": postId,
			"site" : BX.message('sonetLSiteId'),
			"action": "get_comment_src",
			"sessid": BX.bitrix_sessid()
		},
		onsuccess: function(data) 
		{
			if (
				typeof data.message != 'undefined'
				&& typeof data.sourceId != 'undefined'
			)
			{
				var eventData = {
					messageBBCode : data.message,
					messageFields : { 
						arFiles : (typeof top["arLogComFiles" + key] != 'undefined' ? top["arLogComFiles" + key] : [])
					}
				};
				if (top["arLogComDocsType" + key] == "webdav_element" && typeof top["arLogComDocs" + key] != 'undefined')
					eventData["messageFields"]["arDocs"] = top["arLogComDocs" + key];
				if (top["arLogComDocsType" + key] == "disk_file" &&  typeof top["arLogComDocs" + key] != 'undefined')
					eventData["messageFields"]["arDFiles"] = top["arLogComDocs" + key];

				BX.onCustomEvent(window, 'OnUCAfterRecordEdit', [entityXmlId, data.sourceId, eventData, 'EDIT']);
			}
		},
		onfailure: function(data) {}
	});
	

};