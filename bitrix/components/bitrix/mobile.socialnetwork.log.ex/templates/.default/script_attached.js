function __MSLOnFeedPreInit(params)
{
	if (typeof params.arAvailableGroup != 'undefined')
	{
		window.arAvailableGroup = params.arAvailableGroup;
	}

	BX.addCustomEvent("onFrameDataReceivedBefore", function(obCache) {
		BitrixMobile.LazyLoad.clearImages();
	});

	BX.addCustomEvent("onFrameDataReceived", function(obCache) {
		window.isPullDownEnabled = false;
		window.isPullDownLocked = false;
		window.isFrameDataReceived = true;
		app.pullDownLoadingStop();
		BitrixMobile.LazyLoad.showImages(true);
		BX.localStorage.set('mobileLivefeedRefreshTS',  Math.round(new Date().getTime() / 1000), 86400*30);
	});

	BX.addCustomEvent("onFrameDataProcessed", function() {
		BitrixMobile.LazyLoad.showImages(true);
	});

	BX.addCustomEvent("onCacheDataRequestStart", function() 
	{
		setTimeout(function() {
			if (
				typeof window.isFrameDataReceived == 'undefined'
				|| !window.isFrameDataReceived
			)
			{			
				window.isPullDownLocked = true;
				app.exec("pullDownLoadingStart");
			}
		}, 1000);
	});

	BX.addCustomEvent("onFrameDataReceivedError", function() {
		app.BasicAuth({
			'success': BX.delegate(function() {
				BX.frameCache.update(true);
			}),
			'failture': BX.delegate(function() {
				window.isPullDownLocked = false;
				app.pullDownLoadingStop();
				__MSLRefreshError(true);
			})
		});
	});

	BX.addCustomEvent("onFrameDataRequestFail", function(response)
	{
		if (
			typeof response != 'undefined'
			&& typeof response.reason != 'undefined'
			&& response.reason == "bad_eval"
		)
		{
			window.isPullDownLocked = false;
			app.pullDownLoadingStop();
			__MSLRefreshError(true);
		}
		else
		{
			app.BasicAuth({
				'success': BX.delegate(function() {
					BX.frameCache.update(true);
				}),
				'failture': BX.delegate(function() {
					window.isPullDownLocked = false;
					app.pullDownLoadingStop();
					__MSLRefreshError(true);
				})
			});
		}
	});

	app.pullDown({
		'enable': true,
		'callback': function()
		{
			if (!window.isPullDownLocked)
			{
				__MSLRefresh(true);
			}
		}
	});
}

function __MSLOnFeedInit(params)
{
	logID = parseInt(params.logID);
	bAjaxCall = !!params.bAjaxCall;
	bReload = !!params.bReload;
	bEmptyPage = !!params.bEmptyPage;
	bFiltered = !!params.bFiltered;
	bEmptyGetComments = !!params.bEmptyGetComments;
	groupID = parseInt(params.groupID);
	curUrl = params.curUrl;
	tmstmp = parseInt(params.tmstmp);
	strCounterType = params.strCounterType;

	oMSL.bFollowDefault = !!params.bFollowDefault;

	if (!bAjaxCall)
	{
/*
		BX.ready(function()
		{
			window.onerror = function(message, url, linenumber) {
				__MSLSendError(message, url, linenumber);
			}
		});
*/	
	}

	if (
		logID <= 0
		&& !bEmptyPage
		&& !bAjaxCall
		&& !bReload
	)
	{
		if (
			typeof window.bFeedInitialized != 'undefined'
			&& window.bFeedInitialized
		)
		{
			if (!bAjaxCall)
			{
				BX.ready(function() {
					var windowSize = BX.GetWindowSize();
					maxScroll = windowSize.scrollHeight - windowSize.innerHeight - 190;
					setTimeout(function() {
						oMSL.checkNodesHeight();
					}, 1000);
				});
			}
			return;
		}
		else
		{
			window.bFeedInitialized = true;
		}

		window.arLikeRandomID = {};
		window.LiveFeedID = parseInt(Math.random() * 100000);
		
		oMSL.listPageMenuItems = [];

		if (groupID > 0)
		{
			BX.ready(function() 
			{
				if (app.enableInVersion(3))
				{
					oMSL.listPageMenuItems.push({
						name: BX.message('MSLAddPost'),
						image: "/bitrix/templates/mobile_app/images/lenta/menu/pencil.png",
						action: function()
						{
							if (app.enableInVersion(12))
							{
								app.exec('showPostForm', oMSL.showNewPostForm());
							}
							else
							{
								app.showModalDialog({
									url: (BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') : '/') + "mobile/log/new_post.php?feed_id=" + window.LiveFeedID + "&group_id=" + groupID
								});
							}
						},
						arrowFlag: false
					});

					oMSL.listPageMenuItems.push({
						name: BX.message('MSLMenuItemGroupTasks'),
						icon: 'checkbox',
						arrowFlag: true,
						action: function() {
							var path = BX.message('MSLPathToTasksRouter');
							path = path
								.replace('__ROUTE_PAGE__', 'list')
								.replace('#USER_ID#', BX.message('USER_ID'));

							app.loadPageBlank({
								url: path
							});
						}
					});

					oMSL.listPageMenuItems.push({
						name: BX.message('MSLMenuItemGroupFiles'),
						action: function(){
							app.openBXTable({
								url: (BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') : '/') + 'mobile/?mobile_action=disk_folder_list&type=group&path=/&entityId=' + groupID,
								TABLE_SETTINGS : {
									type : "files",
									useTagsInSearch : false
								}
							});
						},
						arrowFlag: true,
						icon: "file"
					});
					
					oMSL.showPageMenu('list');
				}
				else
				{
					app.addButtons({
						addPostButton: {
							type: "plus",
							style: "custom",
							callback: function(){
								app.showModalDialog({
									url: (BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') : '/') + "mobile/log/new_post.php?feed_id=" + window.LiveFeedID + "&group_id=" + groupID
								});
							}
						}
					});
				}
			});
		}
		else
		{
			BX.ready(function()
			{
				setTimeout(function() 
				{
					if (!bFiltered)
					{
						if (app.enableInVersion(3))
						{
							oMSL.listPageMenuItems.push({
								name: BX.message('MSLAddPost'),
								image: "/bitrix/templates/mobile_app/images/lenta/menu/pencil.png",
								action: function()
								{
									if (app.enableInVersion(12))
									{
										app.exec('showPostForm', oMSL.showNewPostForm());
									}
									else
									{
										app.showModalDialog({
											url: (BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') : '/') + "mobile/log/new_post.php?feed_id=" + window.LiveFeedID
										});
									}
								},
								arrowFlag: false
							});

							if (BX.message('MSLMenuItemWork'))
							{
								oMSL.listPageMenuItems.push({
									name: BX.message('MSLMenuItemWork'),
									image: "/bitrix/templates/mobile_app/images/lenta/menu/work.png",
									arrowFlag: true,
									action: function() {
										app.loadPageBlank({
											url: (BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') : '/') + "mobile/index.php?work=Y",
											cache: false,
											bx24ModernStyle: true
										});
									}
								});
							}

							oMSL.listPageMenuItems.push({
								name: BX.message('MSLMenuItemFavorites'),
								image: "/bitrix/templates/mobile_app/images/lenta/menu/favorite.png",
								arrowFlag: true,
								action: function() {
									app.loadPageBlank({
										url: (BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') : '/') + "mobile/index.php?favorites=Y",
										cache: false,
										bx24ModernStyle: true
									});
								}
							});

							oMSL.listPageMenuItems.push({
								name: BX.message('MSLMenuItemMy'),
								image: "/bitrix/templates/mobile_app/images/lenta/menu/mine.png",
								arrowFlag: true,
								action: function() {
									app.loadPageBlank({
										url: (BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') : '/') + "mobile/index.php?my=Y",
										cache: false,
										bx24ModernStyle: true
									});
								}
							});

							oMSL.listPageMenuItems.push({
								name: BX.message('MSLMenuItemImportant'),
								image: "/bitrix/templates/mobile_app/images/lenta/menu/important.png",
								arrowFlag: true,
								action: function() {
									app.loadPageBlank({
										url: (BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') : '/') + "mobile/index.php?important=Y",
										cache: false,
										bx24ModernStyle: true
									});
								}
							});

							if (BX.message('MSLMenuItemBizproc'))
							{
								oMSL.listPageMenuItems.push({
									name: BX.message('MSLMenuItemBizproc'),
									image: "/bitrix/templates/mobile_app/images/lenta/menu/workflow.png",
									arrowFlag: true,
									action: function() {
										app.loadPageBlank({
											url: (BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') : '/') + "mobile/index.php?bizproc=Y",
											cache: false,
											bx24ModernStyle: true
										});
									}
								});
							}

							oMSL.listPageMenuItems.push({
								name: BX.message('MSLMenuItemRefresh'),
								image: "/bitrix/templates/mobile_app/images/lenta/menu/n_refresh.png",
								arrowFlag: false,
								action: function() {
									oMSL.pullDownAndRefresh();
								}
							});

							if (oMSL.bUseFollow)
							{
								oMSL.listPageMenuItems.push({
									name: (
										oMSL.bFollowDefault 
											? BX.message('MSLMenuItemFollowDefaultY') 
											: BX.message('MSLMenuItemFollowDefaultN')
									),
									image: "/bitrix/templates/mobile_app/images/lenta/menu/glasses.png",
									arrowFlag: false,
									feature: 'follow',
									action: function() {
										oMSL.setFollowDefault({
											value: !oMSL.bFollowDefault
										});
									}
								});
							}

							oMSL.showPageMenu('list');
						}
						else
						{
							BX.ready(function() {
								app.addButtons({
									addPostButton:{
										type: "plus",
										style:"custom",
										callback:function(){
											app.showModalDialog({
												url: (BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') : '/') + "mobile/log/new_post.php?feed_id=" + window.LiveFeedID
											});
										}
									}
								});
							});
						}
					}
					else
					{
						if (app.enableInVersion(10))
						{
							BXMobileApp.UI.Page.TopBar.title.setText(BX.message('MSLLogTitle'));
							BXMobileApp.UI.Page.TopBar.title.setCallback("");
							BXMobileApp.UI.Page.TopBar.title.show();
						}
						else
						{
							app.removeButtons({
								position: 'right'
							});
						}
					}
				}, 1000);
			});
		}

		if (app.enableInVersion(12))
		{
			var selectedDestinations = {
				a_users: [],
				b_groups: []
			};

			oMSL.clearPostFormDestination(selectedDestinations, groupID); // to work before DBLoad

			BX.MSL.DBLoad(
				{
					onLoad: function (obResult)
					{
						selectedDestinations = {
							a_users: [],
							b_groups: []
						};

						if (
							typeof obResult.SPERM != 'undefined'
							&& typeof obResult.SPERM.U != 'undefined'
							&& obResult.SPERM.U != null
						)
						{
							for (var i = 0; i < obResult.SPERM.U.length; i++)
							{
								if (obResult.SPERM.U[i] == 'UA')
								{
									if (BX.message('MSLIsDenyToAll') != 'Y')
									{
										oMSL.addPostFormDestination(
											selectedDestinations,
											{
												type: 'UA'
											}
										);
									}
								}
								else
								{
									oMSL.addPostFormDestination(
										selectedDestinations,
										{
											type: 'U',
											id: obResult.SPERM.U[i].replace('U', ''),
											name: (
												typeof obResult.SPERM_NAME != 'undefined'
												&& typeof obResult.SPERM_NAME.U != 'undefined'
												&& typeof obResult.SPERM_NAME.U[i] != 'undefined'
												&& obResult.SPERM_NAME.U[i] != null
													? obResult.SPERM_NAME.U[i]
													: ''
											)
										}
									);
								}
							}
						}

						if (
							typeof obResult.SPERM != 'undefined'
							&& typeof obResult.SPERM.SG != 'undefined'
							&& obResult.SPERM.SG != null
						)
						{
							for (var i = 0; i < obResult.SPERM.SG.length; i++)
							{
								oMSL.addPostFormDestination(
									selectedDestinations,
									{
										type: 'SG',
										id:  obResult.SPERM.SG[i].replace('SG', ''),
										name: (
											typeof obResult.SPERM_NAME.SG[i] != 'undefined'
											&& typeof obResult.SPERM_NAME.SG != 'undefined'
											&& typeof obResult.SPERM_NAME.SG[i] != 'undefined'
											&& obResult.SPERM_NAME.SG[i] != null
												? obResult.SPERM_NAME.SG[i]
												: ''
										)
									}
								);
							}
						}

						if (
							typeof obResult.POST_MESSAGE != 'undefined'
							&& obResult.POST_MESSAGE != null
						)
						{
							oMSL.setPostFormParams({
								messageText: oMSL.unParseMentions(obResult.POST_MESSAGE)
							});
						}
					},
					onEmpty: function (obResult)
					{
						selectedDestinations = {
							a_users: [],
							b_groups: []
						};
						oMSL.clearPostFormDestination(selectedDestinations, groupID);
					}
				},
				(groupID > 0 ? groupID : false)
			);
		}

		oMSL.setPostFormParams({
			selectedRecipients: selectedDestinations
		});

		oMSL.setPostFormExtraData({
			messageUFCode: BX.message('MSLPostFormUFCode')
		});

		BX.addCustomEvent("onMPFSent", function(post_data)
		{
			oMSL.onMPFSent(post_data, groupID);
		});

		BX.addCustomEvent("onBlogPostDelete", function(params) {
			oMSL.pullDownAndRefresh();
		});

		BX.ready(function() 
		{
			if (!bFiltered)
			{
				BX.addCustomEvent("onOpenPageAfter", function ()
				{
					if (oMSL.refreshNeeded === true)
					{
						oMSL.pullDownAndRefresh();
						oMSL.refreshNeeded = false;
					}
				});

			}
			else // bFavorites
			{
				if (window.platform == "android")
				{
					BX.addCustomEvent('onOpenPageBefore', function() {
						oMSL.pullDownAndRefresh();
					});
				}
			}
		});

		BX.addCustomEvent("onStreamRefresh", function(data) {
			document.location.replace(document.location.href);
		});

		BX.addCustomEvent("onLogEntryRead", function(data) {
			__MSLLogEntryRead(data.log_id, data.ts, (data.bPull === true || data.bPull === 'YES' ? true : false));
		});

		BX.addCustomEvent("onCommentsGet", function(data) {
			if (
				typeof window.arLogTs['entry_' + data.log_id] != 'undefined'
				&& window.arLogTs['entry_' + data.log_id] != null
			)
			{
				arLogTs['entry_' + data.log_id] = data.ts;
			}
		});

		BX.addCustomEvent("onLogEntryCommentAdd", function(data) {
			oMSL.onLogEntryCommentAdd(data.log_id);
		});

		BX.addCustomEvent("onLogEntryRatingLike", function(data) {
			oMSL.onLogEntryRatingLike({
				ratingId: data.rating_id,
				voteAction: data.voteAction,
				logId: data.logId
			});
		});

		BX.addCustomEvent("onLogEntryFollow", function(data) 
		{
			oMSL.setFollow({
				logId: data.logId,
				pageId: data.pageId,
				bOnlyOn: (typeof data.bOnlyOn != 'undefined' && data.bOnlyOn == 'Y' ? true : false),
				bRunEvent: false
			});
		});

		BX.addCustomEvent("onLogEntryFavorites", function(data)
		{
			oMSL.onLogEntryFavorites(data.log_id, data.page_id);
		});

		BX.addCustomEvent("onLogEntryCommentsNumRefresh", function(data) 
		{
			oMSL.onLogEntryCommentAdd(data.log_id, data.num);
		});

		BX.addCustomEvent("onLogEntryPostUpdated", function(data) 
		{
			oMSL.onLogEntryPostUpdated(data);
		});
	}
	else if (bEmptyPage)
	{
		window.isDetailPullDownEnabled = false;

		if (
			window.platform != "android"
			&& !app.enableInVersion(4)
		)
			app.enableScroll(false);

		BX.ready(function() 
		{
			BX.addCustomEvent('onMPFSentEditStart', function(data) {
				app.showPopupLoader({text:""});
			});

			__MSLDrawDetailPage();
			BX.addCustomEvent('onOpenPageBefore', function() { __MSLDrawDetailPage(); } );
			BX.addCustomEvent('onEditedPostInserted', function(data) { 
				app.hidePopupLoader();
				oMSL.drawDetailPageText(data);
				app.onCustomEvent('onLogEntryPostUpdated', data);
			});

			BX.addCustomEvent('onEditedPostFailed', function() {
				app.hidePopupLoader();
			});

			BX.MSL.viewImageBind(
				'post_block_check_cont',
				{
					tag: 'IMG',
					attr: 'data-bx-image'
				}
			);
		});
	}
	else if (
		logID > 0
		&& !bEmptyGetComments
	)
	{
		window.isDetailPullDownEnabled = false;
		window.arCanUserComment = {};

		app.onCustomEvent('onLogEntryRead', { log_id: logID, ts: tmstmp, bPull: false });

		if (
			window.platform != "android"
			&& !app.enableInVersion(4)
		)
		{
			app.enableScroll(false);
		}
	}

	if (!bAjaxCall)
	{
		BX.ready(function()
		{
			if (
				logID <= 0
				&& !bEmptyPage
			)
			{
				var windowSize = BX.GetWindowSize();
				maxScroll = windowSize.scrollHeight - windowSize.innerHeight - 190;
				oMSL.initScroll(true);

				BX.addCustomEvent("UIApplicationDidBecomeActiveNotification", function(params) 
				{
					var networkState = navigator.network.connection.type;

					if (networkState == Connection.UNKNOWN || networkState == Connection.NONE)
					{
						app.pullDownLoadingStop();
						oMSL.initScroll(false, true);
					}
					else
					{
						__MSLPullDownInit(true, false);
						oMSL.initScroll(true, true);
					}
				});

				if (!bFiltered)
				{
					BX.addCustomEvent("onUpdateSocnetCounters", function(params) {
						oMSL.changeCounter(parseInt(params[strCounterType]));
					});

					BX.addCustomEvent("onPull-main", BX.delegate(function(data){
						if (
							data.command == 'user_counter'
							&& data.params[BX.message("MSLSiteId")] 
							&& data.params[BX.message("MSLSiteId")][strCounterType]
						)
						{
							oMSL.changeCounter(parseInt(data.params[BX.message("MSLSiteId")][strCounterType]));
						}
					}, this));
				}
			}

			setTimeout(function() {
				oMSL.checkNodesHeight();
			}, 1000);

			if (
				bEmptyPage
				|| logID > 0
			)
			{
				BX.bind(window, 'scroll', oMSL.onScrollDetail);
				BX.addCustomEvent("UIApplicationDidBecomeActiveNotification", function(params)
				{
					var networkState = navigator.network.connection.type;

					if (
						networkState == Connection.UNKNOWN
						|| networkState == Connection.NONE
					)
					{
						app.pullDownLoadingStop();
					}
					else
					{
						if (oMSL.iLastActivityDate > 0)
						{
							var iNowDate = Math.round(new Date().getTime() / 1000);
							if ((iNowDate - oMSL.iLastActivityDate) > 1740)
							{
								if (bEmptyPage)
								{
									oMSL.getComments({
										ts: oMSL.iDetailTs,
										bPullDown: false,
										obFocus: {
											form: 'NO',
											comments: 'NO'
										}
									});
								}
								else
								{
									document.location.reload(true);
								}
								// get comments
							}
						}
						__MSLDetailPullDownInit(true);
					}
				});

				__MSLDetailPullDownInit(true);
			}
			else if (logID <= 0)
			{
				__MSLPullDownInit(true);
			}

		});
	}
}

function __MSLOnFeedScroll()
{
	var windowScroll = BX.GetWindowScrollPos();
	if (
		windowScroll.scrollTop >= window.maxScroll
		&& (
			windowScroll.scrollTop > 0 // refresh patch
			|| window.maxScroll > 0
		)
		&& !window.bRefreshing
	)
	{
		BX.unbind(window, 'scroll', __MSLOnFeedScroll);

		bGettingNextPage = true;

		var BMAjaxWrapper = new MobileAjaxWrapper;
		BMAjaxWrapper.Wrap({
			'type': 'html',
			'method': 'GET',
			'url': url_next,
			'data': '',
			'processData': false,
			'callback': function(data) 
			{
				nextPageXHR = null;
				BX('lenta_wrapper').insertBefore(BX.create('DIV', {
					html: data
				}), BX('next_post_more'));

				BX.bind(window, 'scroll', __MSLOnFeedScroll);

				var obMore = BX.processHTML(data, true);
				var scripts = obMore.SCRIPT;

				if (parseInt(BX.message('MSLPageNavNum')) > 0 && parseInt(window.iPageNumber) > 0)
				{
					iPageNumber++;
					url_next = BX.util.remove_url_param(url_next, ['PAGEN_' + BX.message('MSLPageNavNum')]);
					url_next += (url_next.indexOf('?') >= 0 ? '&' : '?') + 'PAGEN_' + (parseInt(BX.message('MSLPageNavNum'))) + '=' + (iPageNumber  + 1);
				}
				BX.ajax.processScripts(scripts, true);
				bGettingNextPage = false;

				var windowSize = BX.GetWindowSize();
				maxScroll = windowSize.scrollHeight - windowSize.innerHeight - 190;

				setTimeout(function() {
					oMSL.checkNodesHeight();
				}, 1000);
			},
			'callback_failure': function() {
				nextPageXHR = null;
				bGettingNextPage = false;
			}
		});

		nextPageXHR = BMAjaxWrapper.xhr;
	}
}

function __MSLOpenLogEntry(log_id, path, bMoveBottom, event)
{
	if (
		typeof event != 'undefined' 
		&& event != null 
		&& event
		&& typeof event.target != 'undefined'
		&& event.target != null 
	)
	{
		if (
			typeof event.target.tagName != 'undefined'
			&& event.target.tagName.toLowerCase() == 'a'
		)
		{
			return false;
		}

		var anchorNode = BX.findParent(event.target, { 'tag': 'A' }, { 'tag': 'div', 'className': 'post-item-post-block' } );
		if (anchorNode)
		{
			return false;
		}
	}

	bMoveBottom = !!bMoveBottom;
	path += (bMoveBottom ? '&BOTTOM=Y' : '');

	var pathTs = (
		typeof window.arLogTs['entry_' + log_id] != 'undefined'
		&& window.arLogTs['entry_' + log_id] != null
			? '&LAST_LOG_TS=' + arLogTs['entry_' + log_id]
			: ''
	);

	var pathLikeRandomID = (
		typeof arLikeRandomID['entry_' + log_id] != 'undefined'
		&& arLikeRandomID['entry_' + log_id] != null
			? '&LIKE_RANDOM_ID=' + arLikeRandomID['entry_' + log_id]
			: ''
	);

	app.loadPageBlank({
		url: path + pathTs + pathLikeRandomID
	});
}

function __MSLOpenLogEntryNew(params, event)
{
	var log_id = params.log_id;

	var bShowFull = (typeof params.show_full != 'undefined' ? !!params.show_full : false);

	if (
		typeof params.path == 'undefined'
		|| params.path == null
		|| params.path.length <= 0
	)
	{
		return false;
	}
	else
	{
		var path = params.path;
	}

	if (
		typeof params.log_id == 'undefined'
		|| params.log_id == null
		|| parseInt(params.log_id) <= 0
	)
	{
		return false;
	}

	params.follow = (
		BX('log_entry_follow_' + params.log_id) 
			? BX('log_entry_follow_' + params.log_id).getAttribute('data-follow') 
			: 'Y'
	);

	params.feed_id = (
		typeof window.LiveFeedID != 'undefined'
			? window.LiveFeedID
			: ''
	);

	params.can_user_comment = (
		typeof arCanUserComment != 'undefined' 
		&& typeof arCanUserComment[params.log_id] != 'undefined' 
		&& arCanUserComment[params.log_id]
	);

	// block anchor click
	if (
		typeof event != 'undefined' 
		&& event != null 
		&& event
		&& typeof event.target != 'undefined'
		&& event.target != null 
	)
	{
		if (
			typeof event.target.tagName != 'undefined'
			&& event.target.tagName.toLowerCase() == 'a'
			&& !BX.hasClass(event.target, 'post-item-more')
		)
		{
			return false;
		}

		var anchorNode = BX.findParent(event.target, { 'tag': 'A' }, { 'tag': 'div', 'className': 'post-item-post-block' } );
		if (anchorNode)
		{
			return false;
		}
	}
	// -- block anchor click

	params.RandomID = (
		typeof arLikeRandomID['entry_' + params.log_id] != 'undefined'
		&& arLikeRandomID['entry_' + params.log_id] != null
			? arLikeRandomID['entry_' + params.log_id]
			: false
	);

	if (BX('post_block_check_cont_' + params.log_id))
	{
		params.detailText = BX('post_block_check_cont_' + params.log_id).innerHTML;
		params.bIsPhoto = (BX.hasClass(BX('post_block_check_cont_' + params.log_id), "post-item-post-img-block"));
		params.bIsImportant = (
			BX.hasClass(BX('post_block_check_cont_' + params.log_id), "info-block-important")
			&& BX.hasClass(BX('post_block_check_cont_' + params.log_id), "lenta-info-block")
		);
	}

	if (BX('post_more_block_' + params.log_id))
	{
		params.showMoreButton = (BX('post_more_block_' + params.log_id).style.display == 'none' ? false : true);
	}

	if (BX('post_item_top_' + params.log_id))
	{
		params.topText = BX('post_item_top_' + params.log_id).innerHTML;
	}

	if (BX('informer_comments_all_' + params.log_id))
	{
		params.commentsNumAll = BX('informer_comments_all_' + params.log_id).innerHTML;
		if (BX('informer_comments_new_' + params.log_id))
		{
			params.commentsNumNew = BX.findChild(BX('informer_comments_new_' + params.log_id), { className: 'post-item-inform-right-new-value' }, true, false).innerHTML;
		}
	}
	else if (BX('informer_comments_' + params.log_id))
	{
		params.commentsNum = BX('informer_comments_' + params.log_id).innerHTML;
	}

	if (BX('comments_control_' + params.log_id))
	{
		params.commentsControl = (BX('comments_control_' + params.log_id) ? true : false);
	}

	if (BX('rating_button_' + params.log_id))
	{
		var ratingButton = BX.findChild(BX('rating_button_' + params.log_id), { className: 'post-item-informers' }, true, false);
		if (ratingButton)
		{
			params.ratingButtonClassName = ratingButton.className;
		}
	}

	if (BX('rating_block_' + params.log_id))
	{
		params.ratingText = BX('rating_block_' + params.log_id).innerHTML;
		params.ratingCounter = parseInt(BX('rating_block_' + params.log_id).getAttribute('data-counter'));
	}

	if (BX('rating-footer_' + params.log_id))
	{
		params.ratingFooter = BX('rating-footer_' + params.log_id).innerHTML;
	}

	params.bShowFull = bShowFull;

	if (
		typeof window.arLogTs['entry_' + params.log_id] != 'undefined'
		&& window.arLogTs['entry_' + params.log_id] != null
	)
	{
		params.TS = arLogTs['entry_' + params.log_id];
	}

	params.bSetFocusOnCommentForm = (typeof params.focus_form != 'undefined' ? !!params.focus_form : false);
	params.bSetFocusOnCommentsList = (typeof params.focus_comments != 'undefined' ? !!params.focus_comments : false);

	app.loadPageBlank({
		url: path, 
		bx24ModernStyle: true,
		data: params
	});

	return (
		typeof event != 'undefined' 
		&& event != null 
		&& event
			? BX.PreventDefault(event)
			: false
	);
}

function __MSLDrawDetailPage()
{
	app.getPageParams(
		{
			callback: function(data) 
			{
				app.onCustomEvent('onLogEntryRead', {
					log_id: data.log_id, 
					ts: BX.message('MSLCurrentTime'), 
					bPull: false 
				});
				oMSL.drawDetailPage(data);
			}
		}
	);
}

function __MSLDetailMoveBottom()
{
	if (
		app.enableInVersion(4)
		|| window.platform == "android"
	)
	{
		document.body.scrollTop = document.body.scrollHeight;
	}
	else if (BX('post-card-wrap'))
	{
		BX('post-card-wrap').scrollTop = BX('post-card-wrap').scrollHeight;
	}
}

function __MSLDetailMoveTop()
{
	if (
		app.enableInVersion(4)
		|| window.platform == "android"
	)
	{
		document.body.scrollTop = 0;
	}
	else if (BX('post-card-wrap'))
	{
		BX('post-card-wrap').scrollTop = 0;
	}
}

function __MSLLogEntryRead(log_id, ts, bPull)
{
	bPull = !!bPull;

	if (
		typeof window.arLogTs['entry_' + log_id] != 'undefined'
		&& window.arLogTs['entry_' + log_id] != null
	)
	{
		arLogTs['entry_' + log_id] = ts;

		if (
			BX('informer_comments_' + log_id)
			&& BX('informer_comments_new_' + log_id)
			&& !bPull
		)
		{
			var old_value = (BX('informer_comments_all_' + log_id).innerHTML.length > 0 ? parseInt(BX('informer_comments_all_' + log_id).innerHTML) : 0);
			var val = old_value + parseInt(BX.findChild(BX('informer_comments_new_' + log_id), { 
				className: 'post-item-inform-right-new-value' 
			}, true, false).innerHTML);

			BX.remove(BX('informer_comments_new_' + log_id));
			BX.remove(BX('informer_comments_all_' + log_id));
			BX('informer_comments_' + log_id).innerHTML = val;
		}
	}
	if (BX('lenta_item_' + log_id))
	{
		BX.removeClass(BX('lenta_item_' + log_id), 'lenta-item-new');
	}
}

function __MSLShowComments(arComments)
{
	var ratingNode = null;
	var replyNode = null;
	var commentNode = null;
	var UFNode = null;
	var anchor_id = null;	
	var obParserResult = null;
	var comment_message = null;
	var comment_datetime = null;
	var avatar = null;
	var voteId = null;

	for (var i = 0; i < arComments.length; i++)
	{
		anchor_id = Math.floor(Math.random()*100000) + 1;

		comment_message = (
			arComments[i]["EVENT_FORMATTED"]
			&& arComments[i]["EVENT_FORMATTED"]['MESSAGE']
			&& arComments[i]["EVENT_FORMATTED"]['MESSAGE'].length > 0
				? arComments[i]['EVENT_FORMATTED']['MESSAGE']
				: arComments[i]['EVENT']['MESSAGE']
		);

		if (comment_message.length > 0)
		{
			avatar = (
				arComments[i]["AVATAR_SRC"] 
				&& typeof arComments[i]["AVATAR_SRC"] != 'undefined'
					? BX.create(
						'div', 
						{
							props: {
								className: 'avatar'
							}, 
							style: { 
								backgroundImage: "url('" + arComments[i]["AVATAR_SRC"] + "')",
								backgroundRepeat: "no-repeat"
							}
						}
					)
					: BX.create(
						'div', {
							props: {
								className: 'avatar'
							} 
						}
					)
			);

			comment_datetime = (
				typeof arComments[i]["EVENT_FORMATTED"] != 'undefined'
				&& typeof arComments[i]["EVENT_FORMATTED"]['DATETIME'] != 'undefined'
					? arComments[i]["EVENT_FORMATTED"]['DATETIME']
					: arComments[i]["LOG_TIME_FORMAT"]
			);

			ratingNode = (
				arComments[i]["EVENT"]["RATING_TYPE_ID"].length > 0
				&& arComments[i]["EVENT"]["RATING_ENTITY_ID"] > 0
					? oMSL.buildCommentRatingNode(arComments[i], anchor_id)
					: null
			);

			replyNode = oMSL.buildCommentReplyNode(arComments[i]);

			UFNode = (arComments[i]['EVENT_FORMATTED']['UF_FORMATTED'].length > 0
				? BX.create('div', {
						props: 
						{
							className: 'post-item-attached-file-wrap',
							id: 'entry-comment-' + oMSL.entityXMLId + '-' + arComments[i]["EVENT"]["SOURCE_ID"] + '-files'
						},
						html: arComments[i]['EVENT_FORMATTED']['UF_FORMATTED']
					})
				: null
			);

			commentNode = BX.create('div', {
				props: { 
					className: 'post-comment-block',
					id: 'entry-comment-' + oMSL.entityXMLId + '-' + arComments[i]["EVENT"]["SOURCE_ID"]
				},
				children: [
					BX.create('div', {
						props: {
							className: 'post-user-wrap'
						},
						children: [
							avatar,
							BX.create('div', {
								props: {
									className: 'post-comment-cont'
								},
								children: [
									BX.create('a', {
										props: {
											className: 'post-comment-author'
										},
										attrs: {
											href: arComments[i]["CREATED_BY"]["URL"]
										},
										html: arComments[i]["CREATED_BY"]["FORMATTED"]
									}),
									BX.create('div', {
										props: {
											className: 'post-comment-time'
										},
										html: comment_datetime
									})
								]
							})
						]
					}),
					BX.create('div', {
						props: 
						{
							className: 'post-comment-text',
							id: 'entry-comment-' + oMSL.entityXMLId + '-' + arComments[i]["EVENT"]["SOURCE_ID"] + '-text'
						},
						html: comment_message
					}),				
					UFNode,
					ratingNode,
					replyNode
				]
			});

			BX('post-comment-hidden').appendChild(commentNode);

			BX.MSL.viewImageBind('entry-comment-' + oMSL.entityXMLId + '-' + arComments[i]['EVENT']['SOURCE_ID'] + '-text', { tag: 'IMG', attr: 'data-bx-image'});
			if (UFNode != null)
			{
				BX.MSL.viewImageBind('entry-comment-' + oMSL.entityXMLId + '-' + arComments[i]['EVENT']['SOURCE_ID'] + '-files', { tag: 'IMG', attr: 'data-bx-image'});
			}

			voteId = false;

			if (ratingNode)
			{
				if (
					!window.RatingLikeComments 
					&& top.RatingLikeComments
				)
				{
					RatingLikeComments = top.RatingLikeComments;
				}

				voteId = arComments[i]["EVENT"]["RATING_TYPE_ID"] + '-' + arComments[i]["EVENT"]["RATING_ENTITY_ID"] + '-' + anchor_id;
				RatingLikeComments.Set(
					voteId,
					arComments[i]["EVENT"]["RATING_TYPE_ID"],
					arComments[i]["EVENT"]["RATING_ENTITY_ID"],
					(!arComments[i]["EVENT_FORMATTED"]["ALLOW_VOTE"]['RESULT']) ? 'N' : 'Y'
				);
			}

			if (app.enableInVersion(10))
			{
				oMSL.createCommentMenu(commentNode, arComments[i], voteId);
			}
		}
	}

	BX('post-comment-hidden').style.display = "block";
	BX('post-comment-more').style.display = "none";

	var ratingScripts = "";

	var UFScripts = "";
	var messageScripts = "";
	for (var i = 0; i < arComments.length; i++)
	{
		oMSL.parseAndExecCode(arComments[i]['EVENT_FORMATTED']['UF_FORMATTED'], 0);
		oMSL.parseAndExecCode(arComments[i]['EVENT_FORMATTED']['MESSAGE'], 0);
	}

	BitrixMobile.LazyLoad.showImages(); // when show comments
}

function __MSLDisableSubmitButton(status)
{
	var button = BX('comment_send_button');
	var waiter = BX('comment_send_button_waiter');

	if (button)
	{
		button.disabled = status;

		if (status)
		{
			BX.addClass(button, 'send-message-button-disabled');
			if (waiter)
			{
				var arPos = BX.pos(button);
				var arPosWaiter = BX.pos(waiter);
				waiter.style.top = (arPos.top + parseInt(arPos.height/2) - 10) + 'px';
				waiter.style.left = (arPos.left + parseInt(arPos.width/2) - 10) + 'px';
				waiter.style.zIndex = 10000;
				waiter.style.display = "block";
			}
		}
		else
		{
			if (waiter)
			{
				waiter.style.display = "none";
			}
			BX.removeClass(button, 'send-message-button-disabled');
		}
	}
}

function __MSLGetHiddenDestinations(log_id, author_id, bindElement)
{
	var get_data = {
		sessid: BX.message('MSLSessid'),
		site: BX.message('MSLSiteId'),
		lang: BX.message('MSLLangId'),
		dlim: BX.message('MSLDestinationLimit'),
		log_id: parseInt(log_id),
		nt: BX.message('MSLNameTemplate'),
		sl: BX.message('MSLShowLogin'),
		p_user: BX.message('MSLPathToUser'),
		p_group: BX.message('MSLPathToGroup'),
		p_crmlead: BX.message('MSLPathToCrmLead'),
		p_crmdeal: BX.message('MSLPathToCrmDeal'),
		p_crmcontact: BX.message('MSLPathToCrmContact'),
		p_crmcompany: BX.message('MSLPathToCrmCompany'),
		action: 'get_more_destination',
		mobile_action: 'get_more_destination',
		author_id: parseInt(author_id)
	};

	var BMAjaxWrapper = new MobileAjaxWrapper;
	BMAjaxWrapper.Wrap({
		'type': 'json',
		'method': 'POST',
		'url': BX.message('MSLSiteDir') + 'mobile/ajax.php',
		'data': get_data,
		'callback': function(get_response_data) 
		{
			if (typeof get_response_data["arDestinations"] != 'undefined')
			{
				var arDestinations = get_response_data["arDestinations"];
				if (typeof (arDestinations) == "object")
				{
					if (BX(bindElement))
					{
						var cont = bindElement.parentNode;
						cont.removeChild(bindElement);

						for (var i = 0; i < arDestinations.length; i++)
						{
							if (
								typeof (arDestinations[i]['TITLE']) != 'undefined' 
								&& arDestinations[i]['TITLE'].length > 0
							)
							{
								cont.appendChild(BX.create('SPAN', {
									html: ',&nbsp;'
								}));

								if (
									typeof (arDestinations[i]['CRM_PREFIX']) != 'undefined'
									&& arDestinations[i]['CRM_PREFIX'].length > 0)
								{
									cont.appendChild(BX.create('SPAN', {
										props: {
											className: 'post-item-dest-crm-prefix'
										},
										html: arDestinations[i]['CRM_PREFIX'] + ':&nbsp;'
									}));
								}

								if (
									typeof (arDestinations[i]['URL']) != 'undefined'
									&& arDestinations[i]['URL'].length > 0
								)
								{
									cont.appendChild(BX.create('A', {
										props: {
											className: 'post-item-destination' + (typeof arDestinations[i]['STYLE'] != 'undefined' && arDestinations[i]['STYLE'].length > 0 ? ' post-item-dest-'+arDestinations[i]['STYLE'] : ''),
											'href': arDestinations[i]['URL']
										},
										html: arDestinations[i]['TITLE']
									}));
								}
								else
								{
									cont.appendChild(BX.create('SPAN', {
										props: {
											className: 'post-item-destination' + (typeof arDestinations[i]['STYLE'] != 'undefined' && arDestinations[i]['STYLE'].length > 0 ? ' post-item-dest-'+arDestinations[i]['STYLE'] : '')
										},
										html: arDestinations[i]['TITLE']
									}));
								}
							}
						}

						if (
							typeof get_response_data["iDestinationsHidden"] != 'undefined'
							&& parseInt(get_response_data["iDestinationsHidden"]) > 0
						)
						{
							get_response_data["iDestinationsHidden"] = parseInt(get_response_data["iDestinationsHidden"]);
							if (
								(get_response_data["iDestinationsHidden"] % 100) > 10
								&& (get_response_data["iDestinationsHidden"] % 100) < 20
							)
								var suffix = 5;
							else
								var suffix = get_response_data["iDestinationsHidden"] % 10;

							cont.appendChild(BX.create('SPAN', {
								html: '&nbsp;' + BX.message('MSLDestinationHidden' + suffix).replace("#COUNT#", get_response_data["iDestinationsHidden"])
							}));
						}

						oMSL.checkNodesHeight();
					}
				}
			}
		},
		'callback_failure': function() { }
	});
}

function __MSLGetNewPosts()
{
}

function __MSLOnErrorClick()
{
	if (BX('blog-post-new-error'))
	{
		BX('blog-post-new-error').style.display = 'none';
		BX.unbind(BX('blog-post-new-error'), 'click', __MSLOnErrorClick);
	}
}

function __MSLRefresh(bScroll)
{
	bScroll = !!bScroll;

	if (window.bGettingNextPage)
	{
		if (window.nextPageXHR != null)
		{
			nextPageXHR.abort();
		}
	}

	BX.addClass(BX('lenta_notifier'), 'lenta-notifier-waiter');

	var refreshNeededBlock = BX("lenta_notifier_2", true);
	if (refreshNeededBlock)
	{
		BX.removeClass(refreshNeededBlock, "lenta-notifier-shown");
	}

	bRefreshing = true;

	var reload_url = document.location.href;
	reload_url = reload_url.replace("&RELOAD=Y", "").replace("RELOAD=Y&", "").replace("RELOAD=Y", "");
	reload_url += (reload_url.indexOf('?') !== -1 ? "&" : "?") + 'RELOAD=Y';

	var BMAjaxWrapper = new MobileAjaxWrapper;
	BMAjaxWrapper.Wrap({
		'type': 'html',
		'method': 'GET',
		'url': reload_url,
		'data': '',
		'processData': false,
		'callback': function(get_data)
		{
			BX.removeClass(BX('lenta_notifier'), 'lenta-notifier-waiter');

			if (typeof get_data != 'undefined')
			{
				BitrixMobile.LazyLoad.clearImages();
				BX.clearNodeCache();
				document.body.innerHTML = get_data;
				app.pullDownLoadingStop();

				var ob = BX.processHTML(document.body.innerHTML, true);
				var scripts = ob.SCRIPT;
				BX.ajax.processScripts(scripts, true);

				if (
					typeof BX.frameCache != 'undefined' 
					&& BX("bxdynamic_feed_refresh")
				)
				{
					BX.frameCache.writeCacheWithID(
						"framecache-block-feed",
						BX("bxdynamic_feed_refresh").innerHTML,
						parseInt(Math.random() * 100000),
						JSON.stringify({
							"USE_BROWSER_STORAGE": true,
							"AUTO_UPDATE": true,
							"USE_ANIMATION": false
						})
					);
				}

				setTimeout(function() {
					BitrixMobile.LazyLoad.showImages(); // when refresh
				}, 500);
				BX.localStorage.set('mobileLivefeedRefreshTS',  Math.round(new Date().getTime() / 1000), 86400*30);

				if (bScroll)
				{
					BitrixAnimation.animate({
						duration : 1000,
						start : { scroll : document.body.scrollTop },
						finish : { scroll : 0 },
						transition : BitrixAnimation.makeEaseOut(BitrixAnimation.transitions.quart),
						step : function(state)
						{
							document.body.scrollTop = state.scroll;
						},
						complete : function(){}
					});
				}
			}
			else
			{
				app.pullDownLoadingStop();
				__MSLRefreshError(true);
			}

			bRefreshing = false;
		},
		'callback_failure': function() 
		{
			BX.removeClass(BX('lenta_notifier'), 'lenta-notifier-waiter');

			app.pullDownLoadingStop();
			__MSLRefreshError(true);
			bRefreshing = false;
		}
	});
}

function __MSLPullDownInit(enable, bRefresh)
{
	if (typeof bRefresh == 'undefined')
	{
		bRefresh = true;
	}

	enable = !!enable;
	if (enable)
	{
		if (
			!window.isPullDownEnabled 
			&& bRefresh
		)
		{
			app.pullDown({
				'enable': true,
				'pulltext': BX.message('MSLPullDownText1'),
				'downtext': BX.message('MSLPullDownText2'),
				'loadtext': BX.message('MSLPullDownText3'),
				'callback': function()
				{
					if (!window.isPullDownLocked)
					{
						__MSLRefresh(true);
					}
				}
			});
		}
		isPullDownEnabled = true;
	}
	else
	{
		app.pullDown({
			'enable': false
		});
		isPullDownEnabled = false;
	}
}

function __MSLDetailPullDownInit(enable)
{
	enable = !!enable;

	if (enable)
	{
		if (!isDetailPullDownEnabled)
		{
			app.pullDown({
				'enable': true,
				'pulltext': BX.message('MSLDetailPullDownText1'),
				'downtext': BX.message('MSLDetailPullDownText2'),
				'loadtext': BX.message('MSLDetailPullDownText3'),
				'callback': function()
				{
					var bReload = true;

					if (bReload) 
					{
						var logID = null;
						var ts = null;

						if (BX('post_log_id'))
						{
							logID = parseInt(BX('post_log_id').getAttribute('data-log-id'));
							ts = parseInt(BX('post_log_id').getAttribute('data-ts'));
						}
						else if (BX('lenta_wrapper'))
						{
							var postWrap = BX.findChild(BX('lenta_wrapper'), { className: 'post-wrap' }, true, false);
							if (
								postWrap
								&& postWrap.id.length > 0
							)
							{
								var arMatch = postWrap.id.match(/^lenta_item_([\d]+)$/i);
								if (arMatch != null) 
								{
									document.location.replace(document.location.href);
									isDetailPullDownEnabled = true;
									return;
								}
							}
						}

						if (
							BX('post-comments-wrap') 
							&& (typeof logID !== 'undefined')
							&& logID != null
						)
						{

							var iconFailed = BX.findChild(BX('post-comments-wrap'), { className: 'post-comments-failed-outer' }, true, false);
							if (!!iconFailed)
							{
								BX.cleanNode(iconFailed, true);
							}

							oMSL.getComments({
								ts: ts,
								bPullDown: true,
								obFocus: {
									form: false
								}
							});
						}
					}
				}
			});
		}
		isDetailPullDownEnabled = true;
	}
	else
	{
		app.pullDown({
			'enable': false
		});
		isDetailPullDownEnabled = false;
	}
}

function __MSLShowNotifier(cnt)
{
	BX("lenta_notifier_cnt", true).innerHTML = cnt || "";

	cnt = parseInt(cnt);
	cnt_cent = cnt % 100;

	var reminder = cnt % 10;
	var suffix = '';

	if (cnt_cent >= 10 && cnt_cent < 15)
		suffix = 3;
	else if (reminder == 0)
		suffix = 3;
	else if (reminder == 1)
		suffix = 1;
	else if (reminder == 2 || reminder == 3 || reminder == 4)
		suffix = 2;
	else
		suffix = 3;

	BX("lenta_notifier_cnt_title", true).innerHTML = BX.message('MSLLogCounter' + suffix);
	BX.addClass(BX("lenta_notifier", true), "lenta-notifier-shown");
}

function __MSLRefreshError(bShow)
{
	bShow = !!bShow;
	var errorBlock = BX("lenta_refresh_error", true);
	if (parseInt(window.refreshErrorTimeout) > 0)
	{
		clearTimeout(window.refreshErrorTimeout);
	}

	if (errorBlock)
	{
		if (bShow)
		{
			BX.addClass(errorBlock, "lenta-notifier-shown");
			BX.bind(window, 'scroll', __MSLRefreshErrorScroll);
		}
		else
		{
			BX.unbind(window, 'scroll', __MSLRefreshErrorScroll);
			BX.removeClass(errorBlock, "lenta-notifier-shown");
		}
	}
	else
	{
		window.refreshErrorTimeout = setTimeout(function() {
			__MSLRefreshError(bShow);
		}, 500);
	}
}

function __MSLRefreshErrorScroll()
{
	__MSLRefreshError(false);
}

function __MSLHideNotifier()
{
	if (BX.hasClass(BX("lenta_notifier"), "lenta-notifier-shown"))
	{
		BX.removeClass(BX("lenta_notifier", true), "lenta-notifier-shown");
		setTimeout(function() {
			var mobileLivefeedRefreshTS = BX.localStorage.get('mobileLivefeedRefreshTS');
			var nowTS = Math.round(new Date().getTime() / 1000);
			if (
				parseInt(mobileLivefeedRefreshTS) > 0
				&& (nowTS - parseInt(mobileLivefeedRefreshTS)) > 5
			)
			{
				var refreshNeededBlock = BX("lenta_notifier_2", true);
				if (refreshNeededBlock)
				{
					BX.addClass(refreshNeededBlock, "lenta-notifier-shown");
				}
			}
		}, 3000);
	}
}

function __MSLSetFavorites(log_id)
{
	var favoritesBlock = BX("log_entry_favorites_" + log_id);
	
	if (!favoritesBlock)
	{
		return;
	}

	var strFavoritesOld = (favoritesBlock.getAttribute("data-favorites") == "Y" ? "Y" : "N");
	var strFavoritesNew = (strFavoritesOld == "Y" ? "N" : "Y");	

	if (strFavoritesOld == "Y")
	{
		BX.removeClass(favoritesBlock, 'lenta-item-fav-active');
	}
	else
	{
		BX.addClass(favoritesBlock, 'lenta-item-fav-active');
	}

	favoritesBlock.setAttribute("data-favorites", strFavoritesNew);

	var request_data = {
		'sessid': BX.bitrix_sessid(),
		'site': BX.message('MSLSiteId'),
		'lang': BX.message('MSLLangId'),
		'log_id': parseInt(log_id),
		'favorites': strFavoritesNew,
		'action': 'change_favorites',
		'mobile_action': 'change_favorites'
	};

	var BMAjaxWrapper = new MobileAjaxWrapper;
	BMAjaxWrapper.Wrap({
		'type': 'json',
		'method': 'POST',
		'url': BX.message('MSLSiteDir') + 'mobile/ajax.php',
		'data': request_data,
		'callback': function(response_data) 
		{
			if (response_data["SUCCESS"] == "Y")
			{
				if (strFavoritesNew == "Y")
				{
					oMSL.setFollow({
						logId: log_id,
						bOnlyOn: true,
						bRunEvent: true,
						bAjax: false
					});
				}

				app.onCustomEvent('onLogEntryFavorites', { 
					log_id: log_id,
					page_id: (BX.message('MSLPageId') != undefined ? BX.message('MSLPageId') : '')
				});
			}
		},
		'callback_failure': function() {}
	});
	return false;
}

function showHiddenDestination(cont, el)
{
	BX.hide(el);
	BX('blog-destination-hidden-'+cont).style.display = 'inline';
}

function commonNativeInputCallback(text, commentId)
{
	if (window.entryType == 'blog')
	{
		blogCommentsNativeInputCallback({
			text: text,
			oPreviewComment: null,
			commentId: commentId
		});
	}
	else if (
		window.entryType == 'non-blog' 
		&& typeof (commentId) == 'undefined'
	)
	{
		commentsNativeInputCallback({
			text: text
		});
	}
}

function blogCommentsNativeInputCallback(params)
{
	var text = (typeof params.text != 'undefined' ? BX.util.htmlspecialchars(params.text) : '');
	var oPreviewComment = (typeof params.oPreviewComment != 'undefined' ? params.oPreviewComment : null);
	var commentId = (typeof params.commentId != 'undefined' ? params.commentId : 0);
	var nodeId = (typeof params.nodeId != 'undefined' ? params.nodeId : '');
	var ufCode = (typeof params.ufCode != 'undefined' ? params.ufCode : false);
	var attachedFiles = (typeof params.attachedFiles != 'undefined' ? params.attachedFiles : false);
	var attachedFilesRaw = (typeof params.attachedFilesRaw != 'undefined' ? params.attachedFilesRaw : false);

	if (text.length == 0)
	{
		return;
	}

	var data = {
		'sessid': BX.bitrix_sessid(),
		'comment_post_id': commentVarBlogPostID,
		'act': 'add',
		'post': 'Y',
		'comment': oMSL.parseMentions(text),
		'decode': 'Y'
	};

	if (commentVarAction)
	{
		data.ACTION = commentVarAction;
	}

	if (commentVarEntityTypeID)
	{
		data.ENTITY_TYPE_ID = commentVarEntityTypeID;
	}

	if (commentVarEntityID)
	{
		data.ENTITY_ID = commentVarEntityID;
	}

	if (ufCode && attachedFiles)
	{
		data[ufCode] = attachedFiles;
	}

	if (attachedFilesRaw)
	{
		data.attachedFilesRaw = attachedFilesRaw;
	}

	if (
		typeof (commentId) != 'undefined'
		&& parseInt(commentId) > 0
	)
	{
		data.act = 'edit';
		data.edit_id = parseInt(commentId);
		nodeId = (typeof nodeId != 'undefined' ? nodeId : "");
	}

	if (
		data.act == 'add'
		&& (
			typeof (oPreviewComment) == 'undefined'
			|| oPreviewComment === null
		)
	)
	{
		bLockCommentSending = true;
		oPreviewComment = oMSL.showPreviewComment(text);
		app.clearInput();
	}
	else if (
		data.act == 'edit'
		&& BX(nodeId)
	)
	{
		oMSL.showCommentWait({
			nodeId: nodeId,
			status: true
		});

		var textBlock = BX.findChild(BX(nodeId), { className: 'post-comment-text' }, true, false);	
		if (textBlock)
		{
			textBlock.innerHTML = text.replace(/\[USER\s*=\s*(\d+)\]((?:\s|\S)*?)\[\/USER\]/ig,
				function(str, id, userName)
				{
					return userName;
				}
			);
		}

		app.clearInput();
	}

	var BMAjaxWrapper = new MobileAjaxWrapper;
	BMAjaxWrapper.Wrap({
		'type': 'json',
		'method': 'POST',
		'url': commentVarURL,
		'data': data,
		'processData' : true,
		'callback': function(ajax_response)
		{
			bLockCommentSending = false;

			if (
				typeof ajax_response == 'object'
				&& typeof ajax_response.TEXT != 'undefined'
			)
			{
				response = ajax_response.TEXT;
			}

			if (
				typeof response != 'undefined'
				&& response != "*"
				&& response.length > 0
			)
			{
				if (data.act == 'add')
				{
					oMSL.showNewComment({
						commentId: (typeof ajax_response.COMMENT_ID != 'undefined' ? parseInt(ajax_response.COMMENT_ID) : 0),
						text: response, 
						bClearForm: false,
						oPreviewComment: oPreviewComment,
						bShowImages: false
					});
					oMSL.parseAndExecCode(response);
					__MSLDetailMoveBottom();
					oMSL.setFollow({
						logId: oMSL.log_id,
						bOnlyOn: true
					});
				}
				else
				{
					oMSL.showNewComment({
						text: response, 
						bClearForm: false,
						oPreviewComment: BX(nodeId),
						bReplace: true,
						bIncrementCounters: false,
						bShowImages: false
					});
					oMSL.parseAndExecCode(response);
				}
			}
			else
			{
				oMSL.showCommentAlert({
					nodeId: (data.act == 'add' ? oPreviewComment : nodeId),
					action: data.act,
					text: text,
					commentType: 'blog',
					callback: function() {
						blogCommentsNativeInputCallback({
							text: text,
							oPreviewComment: (data.act == 'add' ? oPreviewComment : nodeId),
							commentId: (data.act == 'add' ? false : commentId)
						});
					}
				});
			}
		},
		'callback_failure': function() 
		{
			bLockCommentSending = false;
			oMSL.showCommentAlert({
				nodeId: (data.act == 'add' ? oPreviewComment : nodeId),
				action:	data.act, 
				text: text,
				commentType: 'blog',
				callback: function() {
					blogCommentsNativeInputCallback({
						text: text,
						oPreviewComment: (data.act == 'add' ? oPreviewComment : nodeId),
						commentId: (data.act == 'add' ? false : commentId)
					});
				}
			});
		}
	});
}

function commentsNativeInputCallback(params)
{
	var text = (typeof params.text != 'undefined' ? BX.util.htmlspecialchars(params.text) : '');
	var oPreviewComment = (typeof params.oPreviewComment != 'undefined' ? params.oPreviewComment : null);
	var commentId = (typeof params.commentId != 'undefined' ? params.commentId : 0);
	var nodeId = (typeof params.nodeId != 'undefined' ? params.nodeId : '');
	var ufCode = (typeof params.ufCode != 'undefined' ? params.ufCode : false);
	var attachedFiles = (typeof params.attachedFiles != 'undefined' ? params.attachedFiles : false);
	var attachedFilesRaw = (typeof params.attachedFilesRaw != 'undefined' ? params.attachedFilesRaw : false);

	if (text.length == 0)
	{
		return;
	}

	var post_data = {
		sessid: BX.bitrix_sessid(),
		site: commentVarSiteID,
		lang: commentVarLanguageID,
		log_id: commentVarLogID,
		message: oMSL.parseMentions(text),
		as: commentVarAvatarSize,
		nt: commentVarNameTemplate,
		sl: commentVarShowLogin,
		dtf: commentVarDateTimeFormat,
		p_user: commentVarPathToUser,
		rt: commentVarRatingType,
		action: 'add_comment',
		mobile_action: 'add_comment',
		sr: BX.message('MSLShowRating')
	};

	if (ufCode && attachedFiles)
	{
		post_data[ufCode] = attachedFiles;
	}

	if (attachedFilesRaw)
	{
		post_data.attachedFilesRaw = attachedFilesRaw;
	}

	if (
		typeof (commentId) != 'undefined'
		&& parseInt(commentId) > 0
	)
	{
		post_data.action = 'edit_comment';
		post_data.mobile_action = 'edit_comment';
		post_data.edit_id = parseInt(commentId);

		nodeId = (typeof nodeId != 'undefined' ? nodeId : "");
	}	

	if (
		post_data.action == 'add_comment'
		&& (
			typeof (oPreviewComment) == 'undefined'
			|| oPreviewComment === null
		)
	)
	{
		oPreviewComment = oMSL.showPreviewComment(text);
		app.clearInput();
	}
	else if (
		post_data.action == 'edit_comment'
		&& BX(nodeId)
	)
	{
		oMSL.showCommentWait({
			nodeId: nodeId,
			status: true
		});

		var textBlock = BX.findChild(BX(nodeId), { className: 'post-comment-text' }, true, false);	
		if (textBlock)
		{
			textBlock.innerHTML = text.replace(/\[USER\s*=\s*(\d+)\]((?:\s|\S)*?)\[\/USER\]/ig,
				function(str, id, userName)
				{
					return userName;
				}
			);
		}

		app.clearInput();
	}

	var BMAjaxWrapper = new MobileAjaxWrapper;
	BMAjaxWrapper.Wrap({
		type: 'json',
		method: 'POST',
		url: BX.message('MSLSiteDir') + 'mobile/ajax.php',
		data: post_data,
		callback: function(post_response_data)
		{
			if (typeof post_response_data["arCommentFormatted"] != 'undefined')
			{
				if (post_data.action == 'add_comment')
				{
					oMSL.showNewComment({
						commentId: parseInt(post_response_data["commentID"]),
						arComment: post_response_data["arCommentFormatted"],
						oPreviewComment: oPreviewComment,
						bClearForm: true
					});
					__MSLDetailMoveBottom();

					oMSL.setFollow({
						logId: post_data.log_id,
						bOnlyOn: true
					});
				}
				else
				{
					oMSL.showNewComment({
						arComment: post_response_data["arCommentFormatted"],
						oPreviewComment: BX(nodeId), 
						bIncrementCounters: false,
						bReplace: true
					});
				}
			}
			else 
			{
				oMSL.alertPreviewComment({
					nodeId: (oPreviewComment ? oPreviewComment : nodeId),
					text: text,
					commentType: 'log',
					commentId: commentId,
					action: post_data.action,
					callback: function()
					{
						commentsNativeInputCallback({
							text: text,
							oPreviewComment: oPreviewComment,
							commentId: commentId,
							nodeId: nodeId
						});
					}
				});
			}
		},
		'callback_failure': function() 
		{
			oMSL.alertPreviewComment({
				nodeId: (oPreviewComment ? oPreviewComment : nodeId),
				text: text,
				commentType: 'log',
				commentId: commentId,
				action: post_data.action,
				callback: function()
				{
					commentsNativeInputCallback({
						text: text,
						oPreviewComment: oPreviewComment,
						commentId: commentId,
						nodeId: nodeId
					});
				}
			});
		}
	});
}

__MSLSendError = function(message, url, linenumber)
{
	var error_data =  {
		'sessid': BX.bitrix_sessid(),
		'site': BX.message('MSLSiteId'),
		'lang': BX.message('MSLLangId'),
		'message': message,
		'url': url,
		'linenumber': linenumber,
		'action': 'log_error',
		'mobile_action': 'log_error'
	};

	var BMAjaxWrapper = new MobileAjaxWrapper;
	BMAjaxWrapper.Wrap({
		'type': 'json',
		'method': 'POST',
		'url': BX.message('MSLSiteDir') + 'mobile/ajax.php',
		'data': error_data,
		'callback': function(data) {}, 
		'callback_failure': function(data) {}
	});
}

__MSLSendErrorEval = function(script)
{
	BX.evalGlobal('try { ' + script + ' } catch (e) { __MSLSendError(e.message, e.name, e.number); }');
}

BitrixMSL = function ()
{
	this.scriptsAttached = [];
	this.refreshNeeded = false;
	this.counterTimeout = null;
	this.detailPageId = false;
	this.logId = false;
	this.commentsType = false;
	this.entityXMLId = '';

	this.commentLoadingFilesStack = false;
	this.commentProgressBarAnimation = false;
	this.commentProgressBarState = 0;
	
	this.sendCommentWritingList = [];
	this.sendCommentWritingListTimeout = [];

	this.commentTextCurrent = '';
	this.arMention = [];

	this.bUseFollow = (
		typeof (BX('MSLUseFollow')) != 'undefined' 
		&& BX('MSLUseFollow') == 'Y'
	);

	this.bUseFollow = true;
	this.bFollow = true;
	this.bFollowDefault = true;

	this.detailPageMenuItems = [];
	this.listPageMenuItems = [];

	this.bKeyboardCaptureEnabled = false;
	this.keyboardShown = null;

	this.arBlockToCheck = {};
	this.iLastActivityDate = null;

	this.iDetailTs = 0;

	this.newPostFormParams = {};
	this.newPostFormExtraData = {};
};

BitrixMSL.prototype.registerScripts = function(path)
{
	if (!BX.util.in_array(path, this.scriptsAttached))
	{
		this.scriptsAttached.push(path);
	}
}

BitrixMSL.prototype.loadScripts = function()
{
	for (var i = 0; i < this.scriptsAttached.length; i++)
	{
		BX.loadScript(this.scriptsAttached[i] + '?' + parseInt(Math.random() * 100000));
	}
}

BitrixMSL.prototype.pullDownAndRefresh = function()
{
	app.exec("pullDownLoadingStart");
	window.isPullDownLocked = true;
	__MSLRefresh(true);
}

BitrixMSL.prototype.shareBlogPost = function(data)
{
//	alert(JSON.stringify(data));
}

BitrixMSL.prototype.deleteBlogPost = function(data)
{
	app.confirm({
		title: BX.message('MSLDeletePost'),
		text : BX.message('MSLDeletePostDescription'),
		buttons : [ 
			BX.message('MSLDeletePostButtonOk'), 
			BX.message('MSLDeletePostButtonCancel') 
		],
		callback : function (btnNum)
		{
			if (btnNum == 1)
			{
				app.showPopupLoader({text:""});

				var BMAjaxWrapper = new MobileAjaxWrapper;
				BMAjaxWrapper.Wrap({
					'type': 'json',
					'method': 'POST',
					'url': BX.message('MSLSiteDir') + 'mobile/ajax.php',
					'data': {
						'action': 'delete_post',
						'mobile_action': 'delete_post',
						'sessid': BX.bitrix_sessid(),
						'site': BX.message('SITE_ID'),
						'lang': BX.message('LANGUAGE_ID'),
						'post_id': data.post_id
					},
					'processData': true,
					'callback': function(response_data)
					{
						app.hidePopupLoader();
						if (
							typeof response_data.SUCCESS != 'undefined'
							&& response_data.SUCCESS == 'Y'
						)
						{
							app.onCustomEvent('onBlogPostDelete', {});
							app.closeController({drop: true});
						}
					},
					'callback_failure': function() {
						app.hidePopupLoader();
					}
				});

				return false;
			}
		}
	});
}

BitrixMSL.prototype.getBlogPostData = function(post_id, callbackFunc)
{
	post_id = parseInt(post_id);
	var obResult = {};

	if (post_id > 0)
	{
		app.showPopupLoader();

		var BMAjaxWrapper = new MobileAjaxWrapper;
		BMAjaxWrapper.Wrap({
			type: 'json',
			method: 'POST',
			url: BX.message('MSLSiteDir') + 'mobile/ajax.php',
			processData: true,
			data: {
				action: 'get_blog_post_data',
				mobile_action: 'get_blog_post_data',
				sessid: BX.bitrix_sessid(),
				site: BX.message('SITE_ID'),
				lang: BX.message('LANGUAGE_ID'),
				post_id: post_id,
				nt: BX.message('MSLNameTemplate'),
				sl: BX.message('MSLShowLogin')
			},
			'processData': true,
			'callback': function(data) 
			{
				app.hidePopupLoader();

				obResult.id = post_id;

				if (
					typeof data.log_id != 'undefined'
					&& parseInt(data.log_id) > 0
				)
				{
					obResult.log_id = data.log_id;
				}

				if (
					typeof data.post_user_id != 'undefined'
					&& parseInt(data.post_user_id) > 0
				)
				{
					obResult.post_user_id = data.post_user_id;
				}

				if (typeof data.PostPerm != 'undefined')
				{
					obResult.PostPerm = data.PostPerm;
				}

				if (typeof data.PostDestination != 'undefined')
				{
					obResult.PostDestination = data.PostDestination;
				}

				if (typeof data.PostDestinationHidden != 'undefined')
				{
					obResult.PostDestinationHidden = data.PostDestinationHidden;
				}

				if (typeof data.PostDetailText != 'undefined')
				{
					obResult.PostDetailText = data.PostDetailText;
				}

				if (typeof data.PostFiles != 'undefined')
				{
					obResult.PostFiles = data.PostFiles;
				}

				if (typeof data.PostUFCode != 'undefined')
				{
					obResult.PostUFCode = data.PostUFCode;
				}

				callbackFunc(obResult);
			},
			'callback_failure': function()
			{
				app.hidePopupLoader();
			}
		});
	}
}

BitrixMSL.prototype.getCommentData = function(params, callbackFunc)
{
	var commentType = (typeof params.commentType != 'undefined' && params.commentType == 'blog' ? 'blog' : 'log');
	var commentId = (typeof params.commentId != 'undefined' ? parseInt(params.commentId) : 0);

	var obResult = {};

	if (
		commentId > 0
		&& typeof params.postId != 'undefined'
		&& parseInt(params.postId) > 0
	)
	{
		app.showPopupLoader();

		var requestData = {
			action: 'get_comment_data',
			sessid: BX.bitrix_sessid(),
			site: BX.message('SITE_ID'),
			lang: BX.message('LANGUAGE_ID')
		};

		if (commentType == 'blog')
		{
			requestData.mobile_action = 'get_blog_comment_data';
			requestData.comment_id = commentId;
			requestData.post_id = parseInt(params.postId);
		}
		else
		{
			requestData.mobile_action = 'get_log_comment_data';
			requestData.cid = commentId;
			requestData.log_id = parseInt(params.postId);
		}

		var BMAjaxWrapper = new MobileAjaxWrapper;
		BMAjaxWrapper.Wrap({
			type: 'json',
			method: 'POST',
			url: BX.message('MSLSiteDir') + 'mobile/ajax.php',
			processData: true,
			data: requestData,
			callback: function(data)
			{
				app.hidePopupLoader();

				obResult.id = commentId;

				if (typeof data.CommentCanEdit != 'undefined')
				{
					obResult.CommentCanEdit = data.CommentCanEdit;
				}

				if (typeof data.CommentDetailText != 'undefined')
				{
					obResult.CommentDetailText = data.CommentDetailText;
				}

				if (typeof data.CommentFiles != 'undefined')
				{
					obResult.CommentFiles = data.CommentFiles;
				}

				if (typeof data.CommentUFCode != 'undefined')
				{
					obResult.CommentUFCode = data.CommentUFCode;
				}

				callbackFunc(obResult);
			},
			callback_failure: function()
			{
				app.hidePopupLoader();
			}
		});
	}
}

BitrixMSL.prototype.openBlogPostPage = function(obBlogPost)
{
	if (
		typeof obBlogPost == 'object'
		&& typeof obBlogPost.log_id != 'undefined'
		&& typeof obBlogPost.PostPerm != 'undefined'
	)
	{
		__MSLOpenLogEntryNew(BX.message('MSLSiteDir') + "mobile/log/?empty=Y", { 
				log_id: parseInt(obBlogPost.log_id),
				entry_type: 'blog',
				entity_xml_id: 'BLOG_' + obBlogPost.id,
				post_perm: obBlogPost.PostPerm,
				feed_id: window.LiveFeedID,
//				destinations: {},
				post_id: obBlogPost.id,
				post_url: BX.message('MSLPathToLogEntry').replace('#log_id#', obBlogPost.log_id),
				can_user_comment: (
					typeof arCanUserComment[obBlogPost.log_id] != 'undefined' 
					&& arCanUserComment[obBlogPost.log_id]
				)
			}, 
			false, 
			false, 
			null
		);
	}
}

BitrixMSL.prototype.editBlogPost = function(data)
{
	if (app.enableInVersion(12))
	{
		this.getBlogPostData(data.post_id, function(postData)
		{
			oMSL.newPostFormParams = {};

			if (
				typeof postData.PostPerm != 'undefined'
				&& postData.PostPerm >= 'W'
			)
			{
				var selectedDestinations = {
					a_users: [],
					b_groups: []
				};

				oMSL.setPostFormExtraDataArray({
					postId: data.post_id,
					postAuthorId: postData.post_user_id,
					logId: postData.log_id
				});

				if (typeof postData.PostDetailText != 'undefined')
				{
					oMSL.setPostFormParams({
						messageText: postData.PostDetailText
					});
				}

				if (typeof postData.PostDestination != 'undefined')
				{
					for (var key in postData.PostDestination)
					{
						if (
							postData.PostDestination[key]["STYLE"] != 'undefined'
							&& postData.PostDestination[key]["STYLE"] == 'all-users'
						)
						{
							oMSL.addPostFormDestination(
								selectedDestinations,
								{
									type: 'UA'
								}
							);
						}
						else if (
							postData.PostDestination[key]["TYPE"] != 'undefined'
							&& postData.PostDestination[key]["TYPE"] == 'U'
						)
						{
							oMSL.addPostFormDestination(
								selectedDestinations,
								{
									type: 'U',
									id: postData.PostDestination[key]["ID"],
									name: BX.util.htmlspecialcharsback(postData.PostDestination[key]["TITLE"])
								}
							);
						}
						else if (
							postData.PostDestination[key]["TYPE"] != 'undefined'
							&& postData.PostDestination[key]["TYPE"] == 'SG'
						)
						{
							oMSL.addPostFormDestination(
								selectedDestinations,
								{
									type: 'SG',
									id: postData.PostDestination[key]["ID"],
									name: BX.util.htmlspecialcharsback(postData.PostDestination[key]["TITLE"])
								}
							);
						}
					}
				}

				if (typeof postData.PostDestinationHidden != 'undefined')
				{
					oMSL.setPostFormExtraData({
						hiddenRecipients: postData.PostDestinationHidden
					});
				}

				oMSL.setPostFormParams({
					selectedRecipients: selectedDestinations
				});

				if (typeof postData.PostFiles != 'undefined')
				{
					oMSL.setPostFormParams({
						messageFiles: postData.PostFiles
					});
				}

				if (typeof postData.PostUFCode != 'undefined')
				{
					oMSL.setPostFormExtraData({
						messageUFCode: postData.PostUFCode
					});
				}

				app.exec('showPostForm', oMSL.showNewPostForm());
			}
		});
	}
	else
	{
		app.showModalDialog({
			url: (BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') : '/') + "mobile/log/new_post.php?post_id=" + data.post_id + "&feed_id=" + data.feed_id
		});
	}
}

BitrixMSL.prototype.drawDetailPage = function(data)
{
	var obParserResult = null;
	var bReopen = false;

	if (BX('post_log_id'))
	{
		var existingLogID = parseInt(BX('post_log_id').getAttribute('data-log-id'));
	}

	if (
		typeof existingLogID === 'undefined'
		|| existingLogID != data.log_id
	)
	{
		app.clearInput();
	}

	if (BX('post_log_id'))
	{
		if (
			typeof existingLogID !== 'undefined'
			&& existingLogID == data.log_id
		)
		{
			bReopen = true;
		}
		else
		{
			BX('post_log_id').setAttribute('data-log-id', data.log_id);
			BX('post_log_id').setAttribute('data-ts', data.TS);
			BX.message({
				MSLLogId: data.log_id
			});
		}
	}

	var bBottom = false;

	window.entryType = data.entry_type;

	if (
		typeof data.entry_type != 'undefined'
		&& data.entry_type == 'blog'
		&& typeof data.post_id != 'undefined'
		&& parseInt(data.post_id) > 0
	)
	{
		tmp_post_id = parseInt(data.post_id);
	}

	if (
		typeof data.entity_xml_id != 'undefined'
		&& data.entity_xml_id.length > 0
	)
	{
		tmp_post_id = 0;
	}

	oMSL.InitDetail({
		commentsType: (data.entry_type == 'blog' ? 'blog' : 'log'),
		detailPageId: (data.entry_type == 'blog' ? 'blog' : 'log') + '_' + (data.entry_type == 'blog' ? data.post_id : data.log_id),
		logId: data.log_id,
		entityXMLId: data.entity_xml_id,
		bUseFollow: (typeof data.use_follow == 'undefined' || data.use_follow != 'NO'),
		bFollow: (typeof data.follow == 'undefined' || data.follow != 'N'),
		feed_id:  (typeof data.feed_id != 'undefined' ? data.feed_id : null),
		entryParams: {
			destinations: (typeof data.destinations != 'undefined' ? data.destinations : null),
			post_perm: (typeof data.post_perm != 'undefined' ? data.post_perm : null),
			post_id: (typeof data.post_id != 'undefined' ? data.post_id : null)
		},
		TS: (typeof data.TS != 'undefined' ? data.TS : null)
	});

	if (
		typeof data.commentsNumAll != 'undefined'
		|| typeof data.commentsNum != 'undefined'
	)
	{
		bBottom = true;
	}

	if (BX('comments_control'))
	{
		BX('comments_control').style.display = (
			typeof data.commentsControl != 'undefined'
			&& data.commentsControl == 'YES'
				? 'inline-block'
				: 'none'
		);
	}

	if (!bReopen)
	{
		var ratingScripts = "";
		var ratingFooterScripts = "";

		if (typeof data.ratingText != 'undefined')
		{
			if (BX('rating-footer'))
			{
				if (typeof data.ratingFooter != 'undefined')
				{
					oMSL.drawRatingFooter(data.ratingFooter);
				}
				else
				{
					BX('rating-footer').innerHTML = '';
				}

				BX('rating-footer').parentNode.style.display = (
					typeof (data.ratingCounter) != 'undefined'
					&& parseInt(data.ratingCounter) > 0
						? 'block'
						: 'none'
				);
			}

			if (
				typeof (data.ratingCounter) != 'undefined'
				&& BX('rating_button_cont')
			)
			{
				BX('rating_button_cont').setAttribute('data-counter', parseInt(data.ratingCounter));
			}

			bBottom = true;

			if (typeof data.ratingButtonClassName != 'undefined')
			{
				BX('rating_button').className = data.ratingButtonClassName;
				BX('rating_button_cont').style.display = 'inline-block';
			}
			else
			{
				BX('rating_button_cont').style.display = 'none';
			}

			BX('rating_text').innerHTML = data.ratingText;
			oMSL.parseAndExecCode(data.ratingText, 0);

			BX.message({
				RVRunEvent: 'Y'
			});
		}
		else
		{
			if (BX('rating-footer'))
			{
				if (typeof data.ratingFooter != 'undefined')
				{
					oMSL.drawRatingFooter(data.ratingFooter);
				}
				else
				{
					BX('rating-footer').innerHTML = '';
				}

				BX('rating-footer').parentNode.style.display = "none";
			}

			BX('rating_button_cont').style.display = 'none';
		}
	}

	if (BX('log_entry_follow'))
	{
		if (
			(	
				typeof data.use_follow != 'undefined'
				&& data.use_follow == 'N'
			)
			|| typeof data.follow == 'undefined' 
		)
		{
			BX.unbindAll(BX('log_entry_follow'));
			BX('log_entry_follow').style.display = 'none';
		}
		else
		{
			bBottom = true;
			BX.removeClass(BX('log_entry_follow'), (data.follow == 'N' ? 'post-item-follow-active' : 'post-item-follow'));
			BX.addClass(BX('log_entry_follow'), (data.follow == 'N' ? 'post-item-follow' : 'post-item-follow-active'));
			BX('log_entry_follow').setAttribute('data-follow', (data.follow == 'N' ? 'N' : 'Y'));
			BX.unbindAll(BX('log_entry_follow'));
//			BX('log_entry_follow').style.display = 'inline-block';
			BX.bind(BX('log_entry_follow'), 'click', function() 
			{ 
				oMSL.setFollow({
					logId: data.log_id,
					bAjax: true,
					bRunEvent: false
				});
			});
		}
	}

	if (bBottom)
	{
		BX.removeClass(BX('lenta_item'), 'post-without-informers');
	}
	else
	{
		BX.addClass(BX('lenta_item'), 'post-without-informers');
	}

	if (!bReopen)
	{
		this.drawDetailPageText(data);

		var contMore = BX.findChild(BX('post_block_check_cont'), { className: 'post-more-block' }, true, false);
		if (
			contMore
			&& BX('post_more_limiter')
		)
		{
/*		
			var contMoreButton = BX.findChild(contMore, { className: 'post-item-more' }, true, false);
			if (contMoreButton)
			{
				contMoreButton.setAttribute('onclick', 'return false;');
				BX.unbindAll(contMoreButton);
			}
*/
			if (
				typeof data.showMoreButton != 'undefined'
				&& data.showMoreButton == 'YES'
			)
			{
				BX('post_more_limiter').style.display = 'block'
				BX.bind(BX('post_more_limiter'), 'click', function() 
				{
					oMSL.expandText(data.log_id);
				});
			}
			else
			{
				BX('post_more_limiter').style.display = 'none';
			}
		}
		
		if (data.bShowFull === "YES")
		{
			BX('post_block_check_cont').className = "post-item-post-block-full";
			if (BX('post_more_block_' + data.log_id))
			{
				BX('post_more_block_' + data.log_id).style.display = "none";
			}
			if (BX('post_block_check_more_' + data.log_id))
			{
				BX('post_block_check_more_' + data.log_id).style.display = "none";
			}

			BX('post_more_limiter').style.display = 'none';
			BitrixMobile.LazyLoad.showImages(false); // when redraw detail 2
		}
		else
		{
			BX('post_block_check_cont').className = (data.bIsPhoto == "YES" ? "post-item-post-img-block" : "post-item-post-block");
		}

		if (data.bIsImportant == "YES")
		{
			BX.addClass(BX('post_block_check_cont'), "lenta-info-block");
			BX.addClass(BX('post_block_check_cont'), "info-block-important");
		}
		else
		{
			BX.removeClass(BX('post_block_check_cont'), "lenta-info-block");
			BX.removeClass(BX('post_block_check_cont'), "info-block-important");
		}

		if (
			data.bSetFocusOnCommentForm != "YES"
			&& data.bSetFocusOnCommentsList != "YES"
		)
		{
			__MSLDetailMoveTop();
		}

		if (
			BX('post-comments-form-wrap')
			&& app.enableInVersion(4)
		)
		{
			if (data.can_user_comment == "YES")
			{
				if (
					typeof data.post_id !== 'undefined'
					&& parseInt(data.post_id) > 0
				)
				{
					commentVarBlogPostID = data.post_id;
				}

				if (
					typeof data.post_url !== 'undefined'
					&& data.post_url.length > 0
				)
				{
					commentVarURL = data.post_url;
				}

				commentVarLogID = data.log_id;
				commentVarAction = false;
				commentVarEntityTypeID = false;
				commentVarEntityID = false;

				if (typeof data.site_id !== 'undefined')
				{
					commentVarSiteID = data.site_id;
				}

				if (typeof data.language_id != 'undefined')
				{
					commentVarLanguageID = data.language_id;
				}

				if (typeof data.datetime_format != 'undefined')
				{
					commentVarDateTimeFormat = data.datetime_format;
				}

				if (BX("empty_page_bottom_margin"))
				{
					BX("empty_page_bottom_margin").style.display = "block";
				}

				if (app.enableInVersion(10))
				{
					BXMobileApp.UI.Page.TextPanel.setParams({
						placeholder: BX.message("MSLEmptyDetailCommentFormTitle"),
						button_name: BX.message("MSLEmptyDetailCommentFormButtonTitle"),
						plusAction: function() { oMSL.showTextPanelMenu(); },
						useImageButton: true,
						action: function(text)
						{
							commonNativeInputCallback(text);
						},
						callback: function(eventData)
						{
							if (
								eventData.event
								&& eventData.event == "onKeyPress"
							)
							{
								oMSL.commentTextCurrent = eventData.text;
								if (BX.util.trim(eventData.text).length > 2)
								{
									oMSL.sendCommentWriting(oMSL.entityXMLId, eventData.text);
								}
							}
						}
					});
					BXMobileApp.UI.Page.TextPanel.clear();
					BXMobileApp.UI.Page.TextPanel.show();
				}
				else
				{
					app.showInput({
						placeholder: BX.message("MSLEmptyDetailCommentFormTitle"),
						button_name: BX.message("MSLEmptyDetailCommentFormButtonTitle"),
						action: function(text)
						{
							commonNativeInputCallback(text);
						}
					});
				}
			}
			else
			{
				if (BX("empty_page_bottom_margin"))
				{
					BX("empty_page_bottom_margin").style.display = "none";
				}

				app.hideInput();
			}
		}

		if (BX('post-comments-wrap'))
		{
			oMSL.getComments({
				ts: data.TS,
				bPullDown: false,
				bPullDownTop: false,
				obFocus: {
					form: data.bSetFocusOnCommentForm,
					comments: data.bSetFocusOnCommentsList
				}
			});
		}

		if (data.bSetFocusOnCommentForm == "YES")
		{
			oMSL.setFocusOnComments('form');
		}
		else if (data.bSetFocusOnCommentsList == "YES")
		{
			oMSL.setFocusOnComments('list');
		}
	}
}

BitrixMSL.prototype.drawDetailPageText = function(data)
{
	var postScripts = '';
	var obParserResult = null;

	if (BX('post_block_check_cont'))
	{
		BX('post_block_check_cont').innerHTML = '';
		BitrixMobile.LazyLoad.clearImages();

		if (typeof data.detailText !== 'undefined')
		{
			BX('post_block_check_cont').innerHTML = data.detailText;
			postScripts += oMSL.parseAndExecCode(data.detailText, 0, false, true);

			if (BX('gallery_wrap'))
			{
				BX.MSL.viewImageBind(
					'gallery_wrap',
					{
						tag: 'IMG',
						attr: 'data-bx-image'
					}
				);
			}
		}
	}

	if (
		BX('post_item_top_wrap')
		&& !this.bFollowDefault
	)
	{
		if (
			typeof data.follow != 'undefined'
			&& data.follow == 'Y'
		)
		{
			BX.addClass(BX('post_item_top_wrap'), 'post-item-follow');
		}
		else
		{
			BX.removeClass(BX('post_item_top_wrap'), 'post-item-follow');
		}
	}

	if (BX('post_item_top'))
	{
		BX('post_item_top').innerHTML = '';
		if (typeof data.topText !== 'undefined')
		{
			BX('post_item_top').innerHTML = data.topText;

			if (BX('datetime_block_list'))
			{
				BX('datetime_block_list').style.display = 'none';
			}

			if (BX('datetime_block_detail'))
			{
				BX('datetime_block_detail').style.display = 'block';
			}

			postScripts += oMSL.parseAndExecCode(data.topText, 0, false, true);
		}
	}

	setTimeout(function() 
	{
		__MSLSendErrorEval(postScripts);
		BitrixMobile.LazyLoad.showImages(); // when redraw detail
		if (
			BX.message('MSLLoadScriptsNeeded') == 'Y'
			&& typeof(oMSL) === "object"
		)
		{
			oMSL.loadScripts();
		}
	}, 0);
}

BitrixMSL.prototype.onLogEntryPostUpdated = function(data)
{
	var postScripts = '';
	var obParserResult = null;

	if (typeof data.logID !== 'undefined')
	{
		if (
			typeof data.detailText !== 'undefined'
			&& BX('post_block_check_cont_' + parseInt(data.logID))
		)
		{
			BX('post_block_check_cont_' + parseInt(data.logID)).innerHTML = data.detailText;
			postScripts += oMSL.parseAndExecCode(data.detailText, 0, false, true);
		}

		if (
			typeof data.topText !== 'undefined'
			&& BX('post_item_top_' + parseInt(data.logID))
		)
		{
			BX('post_item_top_' + parseInt(data.logID)).innerHTML = data.topText;
			postScripts += oMSL.parseAndExecCode(data.topText, 0, false, true);
		}

		setTimeout(function() 
		{
			__MSLSendErrorEval(postScripts);
			BitrixMobile.LazyLoad.showImages(); // when redraw detail
			if (
				BX.message('MSLLoadScriptsNeeded') == 'Y'
				&& typeof(oMSL) === "object"
			)
			{
				oMSL.loadScripts();
			}
		}, 0);
	}
}

BitrixMSL.prototype.changeCounter = function(cnt)
{
	if (this.counterTimeout !== null)
	{
		clearTimeout(this.counterTimeout);
		this.counterTimeout = null;
	}

	this.counterTimeout = setTimeout(function()
	{
		if (parseInt(cnt) > 0)
		{
			__MSLShowNotifier(cnt);
		}
		else
		{
			__MSLHideNotifier();
		}
		clearTimeout(oMSL.counterTimeout);
	}, 1000);
}

BitrixMSL.prototype.editComment = function(params)
{
	commentType = (typeof params.commentType == 'undefined' ? 'log' : params.commentType);
	postId = (typeof params.postId == 'undefined' ? 0 : parseInt(params.postId));
	nodeId = (typeof params.nodeId == 'undefined' ? "" : params.nodeId);

	if (
		(
			typeof (params.commentText) != 'string' 
			&& typeof (params.commentText) != 'number'
		)
		|| params.commentText.length <= 0
		|| parseInt(params.commentId) <= 0
	)
	{
		return;
	}

	if (app.enableInVersion(12))
	{
		this.getCommentData(
			{
				commentType: commentType,
				commentId: params.commentId,
				postId: postId
			},
			function(commentData)
			{
				oMSL.newPostFormParams = {};

				if (
					typeof commentData.CommentCanEdit != 'undefined'
					&& commentData.CommentCanEdit == 'Y'
				)
				{
					oMSL.setPostFormExtraDataArray({
						commentId: params.commentId,
						commentType: commentType,
						postId: postId,
						nodeId: nodeId
					});

					if (typeof commentData.CommentDetailText != 'undefined')
					{
						oMSL.setPostFormParams({
							messageText: commentData.CommentDetailText
						});
					}

					if (typeof commentData.CommentFiles != 'undefined')
					{
						oMSL.setPostFormParams({
							messageFiles: commentData.CommentFiles
						});
					}

					if (typeof commentData.CommentUFCode != 'undefined')
					{
						oMSL.setPostFormExtraData({
							messageUFCode: commentData.CommentUFCode
						});
					}

					app.exec('showPostForm', oMSL.showNewPostForm({
						entityType: 'comment'
					}));
				}
			}
		);
	}
	else
	{
		app.showModalDialog({
			url: (BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') : '/')
				+ "mobile/log/edit_comment.php?type=" + commentType
				+ "&comment_id=" + parseInt(params.commentId)
				+ "&log_id=" + postId
				+ "&node_id=" + nodeId
		});
	}
}

BitrixMSL.prototype.deleteComment = function(params)
{
	if (parseInt(params.commentId) <= 0)
	{
		return;
	}

	commentType = (typeof params.commentType == 'undefined' ? 'log' : params.commentType);
	nodeId = (typeof params.nodeId == 'undefined' ? "" : params.nodeId);

	oMSL.showCommentWait({
		nodeId: nodeId,
		status: true
	});
	BXMobileApp.UI.Page.TextPanel.clear();

	if (commentType == 'blog')
	{
		var BMAjaxWrapper = new MobileAjaxWrapper;
		BMAjaxWrapper.Wrap({
			'type': 'html',
			'method': 'GET',
			'url': commentVarURL + '&sessid=' + BX.bitrix_sessid() + '&delete_comment_id=' + params.commentId,
			'data': '',
			'callback': function(response)
			{
				bLockCommentSending = false;
				if (
					response != "*"
					&& response.length > 0
				)
				{
					oMSL.hideComment(BX(nodeId), commentType);
				}
				else
				{
					oMSL.showCommentAlert({
						nodeId: nodeId,
						action: 'delete',
						commentType: commentType,
						callback: function()
						{
							oMSL.deleteComment({
								commentId: params.commentId,
								commentType: commentType,
								nodeId: nodeId
							});
						}
					});
				}
			},
			'callback_failure': function() 
			{
				bLockCommentSending = false;
				oMSL.showCommentAlert({
					nodeId: nodeId,
					action: 'delete',
					commentType: commentType,
					callback: function()
					{
						oMSL.deleteComment({
							commentId: params.commentId,
							commentType: commentType,
							nodeId: nodeId
						});
					}
				});
			}
		});
	}
	else
	{
		var post_data = {
			'sessid': BX.bitrix_sessid(),
			'site': commentVarSiteID,
			'lang': commentVarLanguageID,
			'log_id': parseInt(oMSL.logId),
			'delete_id': params.commentId,
			'action': 'delete_comment',
			'mobile_action': 'delete_comment'
		};

		var BMAjaxWrapper = new MobileAjaxWrapper;
		BMAjaxWrapper.Wrap({
			'type': 'json',
			'method': 'POST',
			'url': BX.message('MSLSiteDir') + 'mobile/ajax.php',
			'data': post_data,
			'callback': function(post_response_data)
			{
				if (
					post_response_data["commentID"] != 'undefined'
					&& parseInt(post_response_data["commentID"]) > 0
					&& parseInt(post_response_data["commentID"]) == params.commentId
				)
				{
					oMSL.hideComment(BX(nodeId), commentType);
				}
				else
				{
					oMSL.showCommentAlert({
						nodeId: nodeId,
						action: 'delete',
						commentType: commentType,
						callback: function()
						{
							oMSL.deleteComment({
								commentId: params.commentId,
								commentType: commentType,
								nodeId: nodeId
							});
						}
					});
				}
			},
			'callback_failure': function() 
			{
				oMSL.showCommentAlert({
					nodeId: nodeId,
					action: 'delete',
					commentType: commentType,
					callback: function()
					{
						oMSL.deleteComment({
							commentId: params.commentId,
							commentType: commentType,
							nodeId: nodeId
						});
					}
				});
			}
		});
	}
}

BitrixMSL.prototype.hideComment = function(commentNode, commentType)
{
	commentType = (typeof commentType == 'undefined' ? 'log' : commentType);

	BX.cleanNode(commentNode, true);

	var log_id = (commentType == 'blog' ? BX.message('SBPClogID') : 0);
	var old_value = 0;
	var val = false;

	if (BX('informer_comments_' + log_id))
	{
		old_value = (BX('informer_comments_' + log_id).innerHTML.length > 0 ? parseInt(BX('informer_comments_' + log_id).innerHTML) : 0);
		if (old_value > 0)
		{
			val = old_value - 1;
			BX('informer_comments_' + log_id).innerHTML = (val > 0 ? val : '');
		}
	}
	else if (BX('informer_comments_common'))
	{
		old_value = (BX('informer_comments_common').innerHTML.length > 0 ? parseInt(BX('informer_comments_common').innerHTML) : 0);

		if (old_value > 0)
		{
			val = old_value - 1;
			BX('informer_comments_common').innerHTML = (val > 0 ? val : '');
		}		
	}
	else if (BX('informer_comments'))
	{
		old_value = (BX('informer_comments').innerHTML.length > 0 ? parseInt(BX('informer_comments').innerHTML) : 0);
		if (old_value > 0)
		{
			val = old_value - 1;
			BX('informer_comments').innerHTML = (val > 0 ? val : '');
		}
	}	

	if (BX('comcntleave-all'))
	{
		old_value = (BX('comcntleave-all').innerHTML.length > 0 ? parseInt(BX('comcntleave-all').innerHTML) : 0);

		if (old_value > 0)
		{
			val = old_value - 1;
			if (val > 0)
			{
				BX('comcntleave-all').innerHTML = val;
			}
			else
			{
				BX('comcntleave-all').style.dusplay = "none";
			}
		}
	}

	if (BX('comcntleave-old'))
	{
		old_value = (BX('comcntleave-old').innerHTML.length > 0 ? parseInt(BX('comcntleave-old').innerHTML) : 0);

		if (old_value > 0)
		{
			val = old_value - 1;
			if (val > 0)
			{
				BX('comcntleave-old').innerHTML = val;
			}
			else
			{
				BX('comcntleave-old').style.dusplay = "none";
			}
		}
	}

	if (val !== false)
	{
		app.onCustomEvent('onLogEntryCommentsNumRefresh', { log_id: log_id, num: val});
	}
}

BitrixMSL.prototype.createCommentInputForm = function(params)
{
	if (app.enableInVersion(10))
	{
		BXMobileApp.UI.Page.TextPanel.setParams(params);
		BXMobileApp.UI.Page.TextPanel.clear();
		BXMobileApp.UI.Page.TextPanel.show();
	}
	else
	{
		app.showInput(params);
	}
}

BitrixMSL.prototype.onPullComment = function(data)
{
	if (
		data.module_id == "unicomments"
		&& data.command == "comment"
		&& data.params["ACTION"] == "REPLY"
		&& data.params["APPROVED"] == "Y"
		&& data.params["ENTITY_XML_ID"] == oMSL.entityXMLId
	)
	{
		if(
			!BX('entry-comment-' + data.params["FULL_ID"].join('-'))
			&& !BX.util.in_array(data.params["FULL_ID"].join('-'), arEntryCommentID)
		)
		{
			arEntryCommentID[arEntryCommentID.length] = data.params["FULL_ID"].join('-');
			setTimeout(function() 
			{
				oMSL.drawPullComment(data.params) 
			}, 1500); // to draw it after adding by the same client
		}
	}
}

BitrixMSL.prototype.drawPullComment = function(params)
{
	this.showNewPullComment(params, 'entry-comment-' + params["FULL_ID"].join('-'));
	app.onCustomEvent('onLogEntryRead', {
		log_id: tmp_log_id,
		ts: params["POST_TIMESTAMP"],
		bPull: true 
	}); // just for TS
}

BitrixMSL.prototype.showNewPullComment = function(params, nodeId)
{
	if(!BX(nodeId))
	{
		var postCard  = (app.enableInVersion(4) || window.platform == "android" ? document.body : BX('post-card-wrap', true));

		params["POST_TIMESTAMP"] = parseInt(params["POST_TIMESTAMP"]) + parseInt(BX.message('USER_TZ_OFFSET')) + parseInt(BX.message('SERVER_TZ_OFFSET'));
		params["POST_DATETIME_FORMATTED"] = (BX.date.format("d F Y", params["POST_TIMESTAMP"]) == BX.date.format("d F Y") 
			? BX.date.format((BX.message("MSLDateTimeFormat").indexOf('a') >= 0 ? 'g:i a' : 'G:i'), params["POST_TIMESTAMP"], false, true)
			: BX.date.format(BX.message("MSLDateTimeFormat"), params["POST_TIMESTAMP"], false, true)
		);

		var 
			UFNode = null,
			ratingNode = null;

		if (
			typeof (params["POST_MESSAGE_TEXT_MOBILE"]) != 'undefined'
			&& params["POST_MESSAGE_TEXT_MOBILE"].length > 0
			&& params["POST_MESSAGE_TEXT_MOBILE"] != 'NO'
		)
		{
			this.parseAndExecCode(params["POST_MESSAGE_TEXT_MOBILE"]);
		}

		if (
			typeof (params["AFTER_MOBILE"]) != 'undefined'
			&& params["AFTER_MOBILE"].length > 0
			&& params["AFTER_MOBILE"] != 'NO'
		)
		{
			UFNode = BX.create('DIV', {
				props: {
					className: 'post-item-attached-file-wrap',
					id: nodeId + '-files'
				},
				html: params["AFTER_MOBILE"]
			});

			this.parseAndExecCode(params["AFTER_MOBILE"]);
		}

		if (
			typeof (params["BEFORE_ACTIONS_MOBILE"]) != 'undefined'
			&& params["BEFORE_ACTIONS_MOBILE"].length > 0
			&& params["BEFORE_ACTIONS_MOBILE"] != 'NO'
		)
		{
			ratingNode = BX.create('SPAN', {
				html: params["BEFORE_ACTIONS_MOBILE"]
			});

			this.parseAndExecCode(params["BEFORE_ACTIONS_MOBILE"]);
		}

		var replyNode = oMSL.buildCommentReplyNode({
			EVENT: {
				USER_ID: params.AUTHOR.ID
			},
			CREATED_BY: {
				FORMATTED: params.AUTHOR.NAME
			}
		});

		BX('post-comment-last-after').parentNode.insertBefore(BX.create('DIV', {
			attrs: {
				id: nodeId
			},
			props: {
				className: 'post-comment-block'
			},
			children: [
				BX.create('DIV', {
					props: {
						className: 'post-user-wrap'
					},
					children: [
						BX.create('DIV', {
							props: {
								className: 'avatar'
							},
							style: {
								backgroundImage: (
									typeof (params["AUTHOR"]["AVATAR"]) != 'undefined' 
									&& params["AUTHOR"]["AVATAR"].length > 0 && params["AUTHOR"]["AVATAR"] != 'NO' 
										? "url('" + params["AUTHOR"]["AVATAR"] + "')" 
										: ""
								)
							},
							children: []
						}),
						BX.create('DIV', {
							props: {
								className: 'post-comment-cont'
							},
							children: [
								BX.create('A', {
									attrs: {
										href: oMSL.replaceUserPath(params["AUTHOR"]["URL"])
									},
									props: {
										className: 'post-comment-author'
									},
									html: params["AUTHOR"]["NAME"]
								}),
								BX.create('DIV', {
									props: {
										className: 'post-comment-time'
									},
									html: params["POST_DATETIME_FORMATTED"]
								})
							]
						})
					]
				}),
				BX.create('DIV', {
					props: {
						className: 'post-comment-text',
						id: nodeId + '-text'
					},
					html: (
						typeof params["POST_MESSAGE_TEXT_MOBILE"] != 'undefined' && params["POST_MESSAGE_TEXT_MOBILE"].length > 0  && params["POST_MESSAGE_TEXT_MOBILE"] != 'NO' 
							? oMSL.replaceUserPath(params["POST_MESSAGE_TEXT_MOBILE"])
							: params["POST_MESSAGE_TEXT"]
					)
				}),
				UFNode,
				ratingNode,
				replyNode
			]
		}), BX('post-comment-last-after'));

		var maxScrollTop = postCard.scrollHeight - postCard.offsetHeight;

		setTimeout(function() {
			if (
				postCard.scrollTop >= (maxScrollTop - 120) 
				&& postCard
			)
			{
				BitrixAnimation.animate({
					duration : 1000,
					start : { scroll : postCard.scrollTop },
					finish : { scroll : postCard.scrollTop + 140 },
					transition : BitrixAnimation.makeEaseOut(BitrixAnimation.transitions.quart),
					step : function(state)
					{
						postCard.scrollTop = state.scroll;
					},
					complete : function(){}
				});
			}
			BX.addClass(BX(nodeId), "post-comment-new-transition"); 
		}, 0);

		setTimeout(function() {
			BitrixMobile.LazyLoad.showImages();

			BX.MSL.viewImageBind(nodeId + '-text', { tag: 'IMG', attr: 'data-bx-image' });
			if (UFNode != null)
			{
				BX.MSL.viewImageBind(nodeId + '-files', { tag: 'IMG', attr: 'data-bx-image' });
			}
		}, 500);

		// increment comment counters both in post card and LiveFeed

		var log_id = BX.message('MSLLogId');
		var old_value = 0;
		var val = 0;

		if (
			BX('informer_comments_' + log_id)
			&& !BX('informer_comments_new_' + log_id)
		)
		{
			old_value = (BX('informer_comments_' + log_id).innerHTML.length > 0 ? parseInt(BX('informer_comments_' + log_id).innerHTML) : 0);
			val = old_value + 1;
			BX('informer_comments_' + log_id).innerHTML = val;
		}
		else if (BX('informer_comments_common'))
		{
			old_value = (BX('informer_comments_common').innerHTML.length > 0 ? parseInt(BX('informer_comments_common').innerHTML) : 0);
			val = old_value + 1;
			BX('informer_comments_common').innerHTML = val;
		}

		if (BX('comcntleave-all'))
		{
			old_value = (BX('comcntleave-all').innerHTML.length > 0 ? parseInt(BX('comcntleave-all').innerHTML) : 0);
			val = old_value + 1;
			BX('comcntleave-all').innerHTML = val;
		}

		if (
			typeof (tmp_log_id) != 'undefined'
			&& parseInt(tmp_log_id) > 0
		)
		{
			app.onCustomEvent('onLogEntryCommentAdd', { log_id: tmp_log_id });
		}
	}
}

BitrixMSL.prototype.parseAndExecCode = function(text, timeout, bExec, bReturnScripts)
{
	if (
		typeof text != 'string'
		|| text.length <= 0
	)
	{
		return;
	}
	
	timeout = (typeof timeout == 'undefined' ? 500 : parseInt(timeout));
	bExec = (typeof bExec == 'undefined' ? true : !!bExec);
	bReturnScripts = !!bReturnScripts;

	var obParserResult = BX.processHTML(text);
	var parsedScripts = '';

	if (
		obParserResult != null 
		&& obParserResult.SCRIPT != null 
		&& typeof obParserResult.SCRIPT != 'undefined'
	)
	{

		for (var j = 0; j < obParserResult.SCRIPT.length; j++)
		{
			if (obParserResult.SCRIPT[j].isInternal)
			{
				parsedScripts += ';' + obParserResult.SCRIPT[j].JS;
			}
		}

		if (
			bExec 
			&& parsedScripts.length > 0
		)
		{
			setTimeout(function() {
				__MSLSendErrorEval(parsedScripts);
			}, timeout);
		}
	}

	return (bReturnScripts ? parsedScripts : false);
}

BitrixMSL.prototype.replaceUserPath = function(text)
{
	if (
		typeof text != 'string'
		|| text.length <= 0
	)
	{
		return;
	}

	if (BX('MSLIsExtranetSite') == 'Y')
	{
		text = text.replace('/mobile/users/?user_id=', '/extranet/mobile/users/?user_id=');
	}
	else
	{
		text = text.replace('/extranet/mobile/users/?user_id=', '/mobile/users/?user_id=');
	}

	text = text.replace( // anchor
		new RegExp("[\\w\/]+\/personal\/user\/(\\d+)\/\"", 'igm'), 
		(
			BX('MSLIsExtranetSite') == 'Y' 
				? '/extranet/mobile/users/?user_id=$1"' 
				: '/mobile/users/?user_id=$1"'
		)
	);

	return text;
}

BitrixMSL.prototype.createCommentMenu = function(commentNode, arComment, voteId)
{
	BX.bind(commentNode, 'click', function(event)
	{
		event = event||window.event;
		if (event.target.tagName.toUpperCase() == 'A')
		{
			return false;
		}

		var anchorNode = BX.findParent(event.target, { 'tag': 'A' }, { 'tag': 'DIV', 'className': 'post-comment-text' } );
		if (anchorNode)
		{
			return false;
		}

		var arCommentMenu = [];

		arCommentMenu.push({
			title: BX.message('MSLReply'),
			callback: function()
			{
				oMSL.replyToComment(arComment["EVENT"]["USER_ID"], BX.util.htmlspecialcharsback(arComment["CREATED_BY"]["FORMATTED"]));
			}
		});

		if (
			typeof arComment["EVENT"]["RATING_TOTAL_POSITIVE_VOTES"] != 'undefined'
			&& parseInt(arComment["EVENT"]["RATING_TOTAL_POSITIVE_VOTES"]) > 0
			&& typeof voteId != 'undefined'
			&& voteId
		)
		{
			arCommentMenu.push({
				title: BX.message('MSLLikesList'),
				callback: function()
				{
					RatingLike.List(voteId);
				}
			});
		}

		if (
			typeof arComment["EVENT_FORMATTED"] != 'undefined'
			&& typeof arComment["EVENT_FORMATTED"]["CAN_EDIT"] != 'undefined'
			&& arComment["EVENT_FORMATTED"]["CAN_EDIT"] == "Y"
		)
		{
			arCommentMenu.push({
				title: BX.message('MSLCommentMenuEdit'),
				callback: function()
				{
					oMSL.editComment({
						commentId: arComment["EVENT"]["ID"], 
						commentText: arComment["EVENT"]["MESSAGE"], 
						commentType: 'log', 
						postId: arComment["EVENT"]["LOG_ID"],
						nodeId: commentNode.id
					});
				}
			});
		}

		if (
			typeof arComment["EVENT_FORMATTED"] != 'undefined'
			&& typeof arComment["EVENT_FORMATTED"]["CAN_DELETE"] != 'undefined'
			&& arComment["EVENT_FORMATTED"]["CAN_DELETE"] == "Y"
		)
		{
			arCommentMenu.push({
				title: BX.message('MSLCommentMenuDelete'),
				callback: function()
				{
					oMSL.deleteComment({
						commentId: arComment["EVENT"]["ID"], 
						commentType: 'log', 
						nodeId: commentNode.id
					});
				}
			});
		}

		oMSL.showCommentMenu(arCommentMenu);
	});
}

BitrixMSL.prototype.showCommentMenu = function(arButtons)
{
	var action = new BXMobileApp.UI.ActionSheet({
			buttons: arButtons
		}, 
		"commentSheet"
	);
	action.show();
}

BitrixMSL.prototype.showTextPanelMenu = function()
{
	var action = new BXMobileApp.UI.ActionSheet({
			buttons: [
				{
					title: BX.message('MSLTextPanelMenuPhoto'),
					callback: function()
					{
						app.takePhoto({
							source: 1,
							correctOrientation: true,
							targetWidth: 1000,
							targetHeight: 1000,
							callback: function(fileURI)
							{
								oMSL.uploadCommentFile(fileURI);
							}
						});
					}
				},
				{
					title: BX.message('MSLTextPanelMenuGallery'),
					callback: function()
					{
						app.takePhoto({
							targetWidth: 1000,
							targetHeight: 1000,
							callback: function(fileURI)
							{
								oMSL.uploadCommentFile(fileURI);
							}
						});
					}
				}
			]
		}, 
		"textPanelSheet"
	);
	action.show();
}

BitrixMSL.prototype.InitDetail = function(params)
{
	this.commentLoadingFilesStack = [];
	this.commentsType = (typeof (params.commentsType) != 'undefined' && params.commentsType == 'blog' ? 'blog' : 'log');
	this.entityXMLId = (typeof (params.entityXMLId) != 'undefined' ? params.entityXMLId : '');
	this.bFollow = (typeof (params.bFollow) != 'undefined' && !params.bFollow ? false : true);
	this.commentTextCurrent = '';
	this.arMention = [];
	this.iDetailTs = (typeof (params.TS) != 'undefined' ? params.TS : 0);

	if (!this.bKeyboardCaptureEnabled)
	{
		app.enableCaptureKeyboard(true);
		this.bKeyboardCaptureEnabled = true;

		BX.addCustomEvent("onKeyboardWillShow", BX.delegate(function()
		{
			this.keyboardShown = true;
		}, this));

		BX.addCustomEvent("onKeyboardDidHide", BX.delegate(function()
		{
			this.keyboardShown = false;
		}, this));
	}

	if (
		typeof (params.detailPageId) != 'undefined'
		&& params.detailPageId.length > 0
		&& this.detailPageId != params.detailPageId
	)
	{
		this.detailPageId = params.detailPageId;
		if (
			typeof (params.logId) != 'undefined'
			&& parseInt(params.logId) > 0
		)
		{
			this.logId = parseInt(params.logId);
		}

		BX.addCustomEvent("onMPFCommentSent", BX.proxy(function(post_data)
		{
			if (
				post_data.detailPageId == this.detailPageId
				&& post_data.data.action == 'EDIT_COMMENT'
				&& post_data.data.text.length > 0
				&& parseInt(post_data.data.commentId) > 0
			)
			{
				if (this.commentsType == 'blog')
				{
					blogCommentsNativeInputCallback({
						text: post_data.data.text,
						oPreviewComment: null,
						commentId: post_data.data.commentId,
						nodeId: post_data.nodeId,
						ufCode: post_data.ufCode,
						attachedFiles: post_data.data[ufCode],
						attachedFilesRaw: post_data.data.attachedFilesRaw
					});
				}
				else
				{
					commentsNativeInputCallback({
						text: post_data.data.text,
						oPreviewComment: null,
						commentId: post_data.data.commentId,
						nodeId: post_data.nodeId,
						ufCode: post_data.ufCode,
						attachedFiles: post_data.data[ufCode],
						attachedFilesRaw: post_data.data.attachedFilesRaw
					});
				}
			}
		}, this));
	}

	this.detailPageMenuItems = this.buildDetailPageMenu({
		entry_type: this.commentsType,
		destinations: (typeof params.entryParams != 'undefined' && params.entryParams.destinations != 'undefined' ? params.entryParams.destinations : {}),
		post_perm: (typeof params.entryParams != 'undefined' && params.entryParams.post_perm != 'undefined' ? params.entryParams.post_perm : null),
		post_id: (typeof params.entryParams != 'undefined' && params.entryParams.post_id != 'undefined' ? params.entryParams.post_id : null),
		feed_id: (typeof params.feed_id != 'undefined' ? params.feed_id : null)
	});

	this.showPageMenu('detail');
}

BitrixMSL.prototype.showNewComment = function(params)
{
	var text = (typeof params.text != 'undefined' ? params.text : '');
	var arComment = (typeof params.arComment != 'undefined' ? params.arComment : false);
	var oPreviewComment = (typeof params.oPreviewComment != 'undefined' ? params.oPreviewComment : false);
	var bClearForm = (typeof params.bClearForm != 'undefined' ? !!params.bClearForm : false);
	var bReplace = (typeof params.bReplace != 'undefined' ? !!params.bReplace : false);
	var bIncrementCounters = (typeof params.bIncrementCounters != 'undefined' ? !!params.bIncrementCounters : true);
	var bShowImages = (typeof params.bShowImages != 'undefined' ? !!params.bShowImages : true);

	if (
		!!oPreviewComment
		&& typeof oMSL.entityXMLId != 'undefined'
		&& BX('entry-comment-' + oMSL.entityXMLId + '-' + params.commentId)
	)
	{
		BX.cleanNode(BX(params.oPreviewComment), true);
		return;
	}

	if (arComment)
	{
		if (
			!bReplace
			&& BX('entry-comment-' + oMSL.entityXMLId + '-' + arComment["SOURCE_ID"])
		)
		{
			return;
		}

		if (
			arComment["AVATAR_SRC"] 
			&& typeof arComment["AVATAR_SRC"] != 'undefined'
		)
		{
			var avatar = BX.create('DIV', { 
					props: 
					{
						className: 'avatar'
					}, 
					style: 
					{ 
						backgroundImage: "url('" + arComment["AVATAR_SRC"] + "')",
						backgroundRepeat: "no-repeat"
					}
				} 
			);
		}
		else
		{
			var avatar = BX.create(
				'DIV', { 
					props: 
					{
						className: 'avatar' 
					} 
				} 
			);
		}

		var anchor_id = Math.floor(Math.random()*100000) + 1;

		var ratingNode = (
			typeof (arComment["EVENT"]) != 'undefined'
			&& typeof (arComment["EVENT"]["RATING_TYPE_ID"]) != 'undefined'
			&& arComment["EVENT"]["RATING_TYPE_ID"].length > 0
			&& typeof (arComment["EVENT"]["RATING_ENTITY_ID"]) != 'undefined'
			&& parseInt(arComment["EVENT"]["RATING_ENTITY_ID"]) > 0
			&& typeof (arComment["EVENT"]["RATING_USER_VOTE_VALUE"]) != 'undefined'
				? oMSL.buildCommentRatingNode(arComment, anchor_id)
				: null
		);

		var replyNode = oMSL.buildCommentReplyNode({
			EVENT: {
				USER_ID: arComment.USER_ID
			},
			CREATED_BY: {
				FORMATTED: BX.util.htmlspecialcharsback(arComment.CREATED_BY.FORMATTED)
			}
		});

		UFNode = (
			typeof arComment["UF_FORMATTED"] != 'undefined'
			&& arComment["UF_FORMATTED"].length > 0
				? BX.create('div', {
						props:
						{
							className: 'post-item-attached-file-wrap',
							id: 'entry-comment-' + oMSL.entityXMLId + '-' + arComment["SOURCE_ID"] + '-files'
						},
						html: arComment['UF_FORMATTED']
					})
				: null
		);

		var newCommentNode = BX.create('DIV', {
			attrs: {
				id: 'entry-comment-' + oMSL.entityXMLId + '-' + arComment["SOURCE_ID"]
			},	
			props: { 
				className: 'post-comment-block' 
			},
			children: [
				BX.create('DIV', {
					props: 
					{
						className: 'post-user-wrap'
					},
					children: [
						avatar,
						BX.create('DIV', {
							props: 
							{
								className: 'post-comment-cont'
							},
							children: [
								BX.create('A', {
									props: {
										className: 'post-comment-author'
									},
									attrs: 
									{
										href: arComment["CREATED_BY"]["URL"] 
									},
									html: arComment["CREATED_BY"]["FORMATTED"]
								}),
								BX.create('DIV', {
									props: 
									{
										className: 'post-comment-time' 
									},
									html: arComment["LOG_TIME_FORMAT"]
								})
							]
						})
					]
				}),
				BX.create('DIV', {
					props: 
					{
						className: 'post-comment-text'
					},
					html: (
						typeof arComment["MESSAGE_FORMAT_MOBILE"] != 'undefined'
						&& arComment["MESSAGE_FORMAT_MOBILE"].length > 0
							? arComment["MESSAGE_FORMAT_MOBILE"]
							: arComment["MESSAGE_FORMAT"]
					)
				}),
				UFNode,
				ratingNode,
				replyNode
			]
		});
	}
	else
	{
		var newCommentNode = BX.create('DIV', { html: text} );
	}

	if (!!oPreviewComment)
	{
		if (bReplace)
		{
			oPreviewComment.parentNode.insertBefore(newCommentNode, oPreviewComment);
		}
		BX.cleanNode(BX(oPreviewComment), true);
	}

	if (!bReplace)
	{
		BX('post-comment-last-after').parentNode.insertBefore(newCommentNode, BX('post-comment-last-after'));
	}

	var voteId = false;

	if (
		arComment 
		&& ratingNode
	)
	{
		if (
			!window.RatingLikeComments 
			&& top.RatingLikeComments
		)
		{
			RatingLikeComments = top.RatingLikeComments;
		}

		voteId = arComment["EVENT"]["RATING_TYPE_ID"] + '-' + arComment["EVENT"]["RATING_ENTITY_ID"] + '-' + anchor_id;

		RatingLikeComments.Set(
			voteId,
			arComment["EVENT"]["RATING_TYPE_ID"],
			arComment["EVENT"]["RATING_ENTITY_ID"],
			'Y'
		);
	}

	if (
		app.enableInVersion(10)
		&& arComment
	)
	{
		var menuCommentData = {
			EVENT: {
				ID: arComment.EVENT.ID,
				LOG_ID: arComment.EVENT.LOG_ID,
				USER_ID: arComment.USER_ID,
				MESSAGE: arComment.MESSAGE,
				RATING_TOTAL_POSITIVE_VOTES: arComment.EVENT.RATING_TOTAL_POSITIVE_VOTES
			},
			EVENT_FORMATTED: {
				CAN_EDIT: arComment.CAN_EDIT,
				CAN_DELETE: arComment.CAN_DELETE
			},
			CREATED_BY: arComment.CREATED_BY
		};

		oMSL.createCommentMenu(newCommentNode, menuCommentData, voteId);
	}

	if (
		bClearForm 
		&& BX('comment_send_form_comment')
	)
	{
		BX('comment_send_form_comment').value = '';
	}

	if (bIncrementCounters)
	{
		oMSL.incrementCounters(oMSL.logId);
	}

	if (typeof arComment['UF_FORMATTED'] != 'undefined')
	{
		oMSL.parseAndExecCode(arComment['UF_FORMATTED'], 0);
	}

	if (bShowImages)
	{
		setTimeout(function()
		{
			BitrixMobile.LazyLoad.showImages();
		}, 500);
	}
}

BitrixMSL.prototype.showPreviewComment = function(text)
{
	var emptyComment = BX('empty_comment', true);
	var previewCommentID = Math.floor(Math.random()*100000) + 1;
	var lastCommentAfter = BX('post-comment-last-after');

	if (emptyComment && lastCommentAfter)
	{
		var previewComment = BX.clone(emptyComment, true);

		BX.adjust(BX(previewComment), {
			attrs: { 
				id: 'new_comment_' + previewCommentID 
			} 
		});
		var previewCommentText = BX.findChild(previewComment, { className: 'post-comment-text' }, true, false);
		previewCommentText.innerHTML = text.replace(/\n/g, "<br />");
		lastCommentAfter.parentNode.insertBefore(previewComment, lastCommentAfter);
		BX(previewComment).style.display = "block";

		// animate scrolling
		var postCard  = (app.enableInVersion(4) || window.platform == "android" ? document.body : BX('post-card-wrap', true));
		var maxScrollTop = postCard.scrollHeight - postCard.offsetHeight;
		var delta = (window.platform == "android" ? 600 : 120);

		if (
			postCard
			&& postCard.scrollTop >= (maxScrollTop - delta) 
		)
		{
			BitrixAnimation.animate({
				duration : 1000,
				start : { scroll : postCard.scrollTop },
				finish : { scroll : postCard.scrollTop + delta + 20 },
				transition : BitrixAnimation.makeEaseOut(BitrixAnimation.transitions.quart),
				step : function(state)
				{
					postCard.scrollTop = state.scroll;
				},
				complete : function(){}
			});
		}
	}

	return (!!previewComment ? BX(previewComment) : false);
}

BitrixMSL.prototype.showCommentWait = function(params)
{
	if (typeof params.nodeId != 'undefined')
	{
		var authorBlock = BX.findChild(BX(params.nodeId), { className: 'post-comment-cont' }, true, false);
		var waitBlock = false;
		var undeliveredBlock = false;

		if (authorBlock)
		{
			waitBlock = BX.findChild(authorBlock, { className: 'post-comment-preview-wait' }, true, false);
			undeliveredBlock = BX.findChild(authorBlock, { className: 'post-comment-preview-undelivered' }, true, false);
		}

		if (params.status)
		{
			if (!waitBlock)
			{
				authorBlock.appendChild(BX.create('DIV', {
					props: {
						id: params.nodeId + '-status',
						className: 'post-comment-preview-wait'
					}
				}));
			}

			if (!undeliveredBlock)
			{
				authorBlock.appendChild(BX.create('DIV', {
					props: {
						className: 'post-comment-preview-undelivered'
					}
				}));
			}
		}
	}
}

BitrixMSL.prototype.showCommentAlert = function(params)
{
	var commentType = (typeof (params.commentType) != 'undefined' && params.commentType == 'blog' ? 'blog' : 'log');
	var commentId = (typeof (params.commentId) != 'undefined' && params.commentId ? params.commentId : 0);
	var text = (typeof (params.text) != 'undefined' && params.text ? params.text : '');
	var callback = (typeof (params.callback) != 'undefined' ? params.callback : false);
	var action = (typeof (params.action) != 'undefined' ? params.action : false);

	if (typeof params.nodeId != 'undefined')
	{
		if (action == 'add')
		{
			this.alertPreviewComment({
				nodeId: params.nodeId,
				text: text,
				commentType: commentType,
				commentId: commentId,
				action: action,
				callback: callback
			});
		}
		else
		{
			var authorBlock = BX.findChild(BX(params.nodeId), { className: 'post-comment-cont' }, true, false);
			if (authorBlock)
			{
				var undeliveredBlock = BX.findChild(authorBlock, { className: 'post-comment-preview-undelivered' }, true, false);

				if (!undeliveredBlock)
				{
					authorBlock.appendChild(BX.create('DIV', {
						props: {
							id: params.nodeId + '-status',
							className: 'post-comment-preview-undelivered'
						},
						style: { display: "block" }
					}));
				}

				this.alertPreviewComment({
					nodeId: params.nodeId,
					text: text,
					commentType: commentType,
					commentId: commentId,
					action: action,
					callback: callback
				});
			}
		}
	}
}

BitrixMSL.prototype.alertPreviewComment = function(params)
{
	var commentId = (typeof (params.commentId) != 'undefined' && params.commentId ? params.commentId : 0);
	var commentType = (typeof (params.commentType) != 'undefined' && params.commentType == 'blog' ? 'blog' : 'log');
	var text = (typeof (params.text) != 'undefined' && params.text ? params.text : '');
	var callback = (typeof (params.callback) != 'undefined' ? params.callback : false);

	if (typeof params.nodeId != 'undefined')
	{
		var previewCommentWaiter = BX.findChild(BX(params.nodeId), { className: 'post-comment-preview-wait' }, true, false);
		var previewCommentUndelivered = BX.findChild(BX(params.nodeId), { className: 'post-comment-preview-undelivered' }, true, false);

		if (
			!!previewCommentWaiter 
			&& !!previewCommentUndelivered
		)
		{
			BX(previewCommentWaiter).style.display = "none";
			BX(previewCommentUndelivered).style.display = "block";

			BX.bind(BX(previewCommentUndelivered), 'click', function()
			{
				BX.unbindAll(BX(previewCommentUndelivered));
				BX(previewCommentWaiter).style.display = "block";
				BX(previewCommentUndelivered).style.display = "none";

				if (callback)
				{
					callback();
				}
			});
		}
	}
}

BitrixMSL.prototype.showCommentProgressBar = function(commentNode)
{
	if (
		typeof (commentNode) == 'undefined'
		|| !BX(commentNode)
	)
	{
		return;
	}

	BX.findChild(commentNode, { className: 'post-comment-text' }, true, false).appendChild(BX.create('DIV', {
		props: {
			id: commentNode.id + '-progressbar-cont',
			className: 'comment-loading'
		},
		style: {
			display: 'none' 
		},
		children: [
			BX.create('DIV', {
				props: {
					id: commentNode.id + '-progressbar-label',
					className: 'newpost-progress-label'
				}
			}),
			BX.create('DIV', {
				props: {
					id: commentNode.id + '-progressbar-ind',
					className: 'newpost-progress-indicator'
				}
			})
		]
	}));
	BX(commentNode.id + '-progressbar-cont').style.display = 'block';
	var loading_id = Math.floor(Math.random() * 100000) + 1;
	oMSL.commentLoadingFilesStack[oMSL.commentLoadingFilesStack.length] = loading_id;
	clearInterval(oMSL.commentProgressBarAnimation);
	oMSL.commentProgressBarAnimation = BitrixAnimation.animate({
		duration : oMSL.commentLoadingFilesStack.length * 5000,
		start: {
			width: parseInt(oMSL.commentProgressBarState / oMSL.commentLoadingFilesStack.length) + 10 
		},
		finish: {
			width: 90
		},
		transition: BitrixAnimation.makeEaseOut(BitrixAnimation.transitions.linear),
		step : function(state)
		{
			BX(commentNode.id + '-progressbar-ind').style.width = state.width + '%';
			oMSL.commentProgressBarState = state.width;
		},
		complete : function()
		{
			oMSL.commentProgressBarState = 0;
		}
	});

	return loading_id;
}

BitrixMSL.prototype.hideCommentProgressBar = function(loadingId, commentNode)
{
	if (
		typeof (commentNode) == 'undefined'
		|| !BX(commentNode)
	)
	{
		return;
	}

	var newLoadingFilesStack = [];

	for (var i = 0; i < this.commentLoadingFilesStack.length; i++)
	{
		if (this.commentLoadingFilesStack[i] != loadingId)
		{
			newLoadingFilesStack[newLoadingFilesStack.length] = this.commentLoadingFilesStack[i];
		}
	}

	this.commentLoadingFilesStack = newLoadingFilesStack;

	if (this.commentLoadingFilesStack.length == 0)
	{
		clearInterval(this.commentProgressBarAnimation);
		this.commentProgressBarState = 0;
		BX(commentNode.id + '-progressbar-ind').style.width = '100%';

		setTimeout(function() { 
			if (BX(commentNode.id + '-progressbar-cont'))
			{
				BX(commentNode.id + '-progressbar-cont').style.display = 'none'; 
			}
		}, 2000);
	}
}

BitrixMSL.prototype.setFollow = function(params)
{
	var logId = (typeof params.logId != 'undefined' ? parseInt(params.logId) : 0);
	var pageId = (typeof params.pageId != 'undefined' ? params.pageId : false);
	var bOnlyOn = (typeof params.bOnlyOn != 'undefined' ? params.bOnlyOn : false);

	if (bOnlyOn == 'NO')
	{
		bOnlyOn = false;
	}
	var bRunEvent = (typeof params.bRunEvent != 'undefined' ? params.bRunEvent : true);	
	var bAjax = (typeof params.bAjax != 'undefined' ? params.bAjax : false);	

	var followBlock = BX('log_entry_follow_' + logId);
	if (!followBlock)
	{
		followBlock = BX('log_entry_follow');
	}

	var followWrap = BX('post_item_top_wrap_' + logId);
	if (!followWrap)
	{
		followWrap = BX('post_item_top_wrap');
	}

	if (
		followBlock
		&& (
			!this.detailPageId
			|| this.detailPageId != pageId
		)
	)
	{
		var strFollowOld = (followBlock.getAttribute("data-follow") == "Y" ? "Y" : "N");
		var strFollowNew = (strFollowOld == "Y" ? "N" : "Y");

		if (
			!bOnlyOn
			|| strFollowOld == "N"
		)
		{
			BX.removeClass(followBlock, (strFollowOld == "Y" ? 'post-item-follow-active' : 'post-item-follow'));
			BX.addClass(followBlock, (strFollowOld == "Y" ? 'post-item-follow' : 'post-item-follow-active'));
			followBlock.setAttribute("data-follow", strFollowNew);
			if (bRunEvent)
			{
				app.onCustomEvent('onLogEntryFollow', { 
					logId: logId,
					pageId: (oMSL.detailPageId ? oMSL.detailPageId : ''),
					bOnlyOn: (bOnlyOn ? 'Y' : 'N')
				});
			}

			if (
				!this.bFollowDefault
				&& followWrap
			)
			{
				if (strFollowOld == "Y")
				{
					BX.removeClass(followWrap, 'post-item-follow');
				}
				else
				{
					BX.addClass(followWrap, 'post-item-follow');
				}
			}

			if (this.detailPageId)
			{
				this.bFollow = (strFollowNew == "Y");
				this.setFollowMenuItemName();
			}
		}
	}

	if (bAjax)
	{
		var post_data = {
			'sessid': BX.bitrix_sessid(),
			'site': BX.message('MSLSiteId'),
			'lang': BX.message('MSLLangId'),
			'log_id': logId,
			'follow': strFollowNew,
			'action': 'change_follow',
			'mobile_action': 'change_follow'
		};

		var BMAjaxWrapper = new MobileAjaxWrapper;
		BMAjaxWrapper.Wrap({
			'type': 'json',
			'method': 'POST',
			'url': BX.message('MSLSiteDir') + 'mobile/ajax.php',
			'data': post_data,
			'callback': function(get_response_data) 
			{
				if (get_response_data["SUCCESS"] != "Y")
				{
					if (followBlock)
					{
						BX.removeClass(followBlock, (strFollowOld == "Y" ? 'post-item-follow' : 'post-item-follow-active'));
						BX.addClass(followBlock, (strFollowOld == "Y" ? 'post-item-follow-active' : 'post-item-follow'));
						followBlock.setAttribute("data-follow", strFollowOld);
					}

					if (oMSL.detailPageId)
					{
						oMSL.bFollow = (strFollowOld == "Y");
						this.setFollowMenuItemName();
					}

					if (
						!this.bFollowDefault
						&& followWrap
					)
					{
						if (strFollowOld == "Y")
						{
							BX.addClass(followWrap, 'post-item-follow');
						}
						else
						{
							BX.removeClass(followWrap, 'post-item-follow');
						}
					}

					if (parseInt(oMSL.logId) > 0)
					{
						app.onCustomEvent('onLogEntryFollow', { 
							logId: logId,
							pageId: (oMSL.detailPageId ? oMSL.detailPageId : ''),
							bOnlyOn: (bOnlyOn ? 'Y' : 'N')							
						});
					}
				}
			},
			'callback_failure': function() {
				if (followBlock)
				{
					BX.removeClass(followBlock, (strFollowOld == "Y" ? 'post-item-follow' : 'post-item-follow-active'));
					BX.addClass(followBlock, (strFollowOld == "Y" ? 'post-item-follow-active' : 'post-item-follow'));
					followBlock.setAttribute("data-follow", strFollowOld);
				}

				if (
					!this.bFollowDefault
					&& followWrap
				)
				{
					if (strFollowOld == "Y")
					{
						BX.addClass(followWrap, 'post-item-follow');
					}
					else
					{
						BX.removeClass(followWrap, 'post-item-follow');
					}
				}

				if (oMSL.detailPageId)
				{
					oMSL.bFollow = (strFollowOld == "Y");
					this.setFollowMenuItemName();
				}
			}
		});
	}

	return false;
}

BitrixMSL.prototype.setFollowDefault = function(params)
{
	if (typeof params.value == 'undefined')
	{
		return;
	}

	var newValue = !!params.value;

	if (!oMSL.detailPageId)
	{
		oMSL.bFollowDefault = newValue;
		this.setDefaultFollowMenuItemName();
	}

	var post_data = {
		'sessid': BX.bitrix_sessid(),
		'site': BX.message('MSLSiteId'),
		'lang': BX.message('MSLLangId'),
		'value': (newValue ? 'Y' : 'N'),
		'action': 'change_follow_default',
		'mobile_action': 'change_follow_default'
	};

	app.showPopupLoader({text:""});

	var BMAjaxWrapper = new MobileAjaxWrapper;
	BMAjaxWrapper.Wrap({
		'type': 'json',
		'method': 'POST',
		'url': BX.message('MSLSiteDir') + 'mobile/ajax.php',
		'data': post_data,
		'callback': function(get_response_data) 
		{
			app.hidePopupLoader();
			if (get_response_data["SUCCESS"] == "Y")
			{
				oMSL.pullDownAndRefresh();
			}
			else
			{
				if (!oMSL.detailPageId)
				{
					oMSL.bFollowDefault = !newValue;
					this.setDefaultFollowMenuItemName();
				}
			}
			
		},
		'callback_failure': function()
		{
			app.hidePopupLoader();
			if (!oMSL.detailPageId)
			{
				oMSL.bFollowDefault = !newValue;
				this.setDefaultFollowMenuItemName();
			}
		}
	});
}

BitrixMSL.prototype.incrementCounters = function(logId)
{
	logId = parseInt(logId);
	if (logId > 0)
	{
		var old_value = 0;
		var val = 0;

		if (
			BX('informer_comments_' + logId)
			&& !BX('informer_comments_new_' + logId)
		)
		{
			old_value = (BX('informer_comments_' + logId).innerHTML.length > 0 ? parseInt(BX('informer_comments_' + logId).innerHTML) : 0);
			val = old_value + 1;
			BX('informer_comments_' + logId).innerHTML = val;
		}
		else if (BX('informer_comments_common'))
		{
			old_value = (BX('informer_comments_common').innerHTML.length > 0 ? parseInt(BX('informer_comments_common').innerHTML) : 0);
			val = old_value + 1;
			BX('informer_comments_common').innerHTML = val;
		}

		if (BX('comcntleave-all'))
		{
			old_value = (BX('comcntleave-all').innerHTML.length > 0 ? parseInt(BX('comcntleave-all').innerHTML) : 0);
			val = old_value + 1;
			BX('comcntleave-all').innerHTML = val;
		}

		app.onCustomEvent('onLogEntryCommentAdd', { log_id: logId });
	}
}

BitrixMSL.prototype.getComment = function(params)
{
	var url = '';

	if (params.commentType == 'blog')
	{
		if (
			!!params.oPreviewComment
			&& typeof oMSL.entityXMLId != 'undefined'
			&& BX('entry-comment-' + oMSL.entityXMLId + '-' + params.commentId)
		)
		{
			BX.cleanNode(BX(params.oPreviewComment), true);
			return;
		}
		url = BX.message('SBPCurlToNew').replace(/#post_id#/, params.entryId).replace(/#comment_id#/, params.commentId)
	}

	if (url.length > 0)
	{
		var BMAjaxWrapper = new MobileAjaxWrapper;
		BMAjaxWrapper.Wrap({
			'type': 'json',
			'method': 'GET',
			'url': url,
			'data': '',
			'processData': true,
			'callback': function(comment_responce)
			{
				if (typeof comment_responce.TEXT != 'undefined')
				{
					oMSL.showNewComment({
						commentType: params.commentType,
						commentId: params.commentId,
						text: comment_responce.TEXT,
						bClearForm: false, 
						oPreviewComment: params.oPreviewComment, 
						bReplace: false,
						bIncrementCounters: true,
						bShowImages: false
					});
					oMSL.parseAndExecCode(comment_responce.TEXT);
					__MSLDetailMoveBottom();

					oMSL.setFollow({
						logId: oMSL.log_id,
						bOnlyOn: true
					});
				}
				else
				{
					oMSL.showCommentAlert({
						nodeId: params.oPreviewComment,
						commentType: params.commentType,
						callback: function() {
							oMSL.getComment(params);
						}
					});
				}
			},
			'callback_failure': function() 
			{
				oMSL.showCommentAlert({
					nodeId: params.oPreviewComment,
					commentType: params.commentType,
					callback: function() {
						oMSL.getComment(params);
					}
				});
			}
		});
	}
}

BitrixMSL.prototype.uploadCommentFile = function(fileURI)
{
	var oPreviewComment = oMSL.showPreviewComment('');
	var loadingId = oMSL.showCommentProgressBar(oPreviewComment);

	function win(r)
	{
		var arResult = JSON.parse(r.response)

		if (
			typeof arResult.SUCCESS == 'undefined'
			|| arResult.SUCCESS != 'Y'
		)
		{
			fail_1try();
		}
		else
		{
			oMSL.hideCommentProgressBar(loadingId, oPreviewComment);
			if (
				oMSL.commentsType == 'blog'
				&& (
					typeof arResult.BLOG_COMMENT_ID != 'undefined'
					|| parseInt(arResult.BLOG_COMMENT_ID) > 0
				)
			)
			{
				oMSL.getComment({
					oPreviewComment: oPreviewComment,
					commentId: (oMSL.commentsType == 'blog' ? arResult.BLOG_COMMENT_ID : false),
					entryId: (oMSL.commentsType == 'blog' ? commentVarBlogPostID : false),
					commentType: oMSL.commentsType
				});
			}
			else if	(
				oMSL.commentsType == 'log'
				&& (
					typeof arResult.commentID != 'undefined'
					|| parseInt(arResult.commentID) > 0
				)
			)
			{
				oMSL.showNewComment({
					arComment: arResult["arCommentFormatted"],
					bIncrementCounters: true,
					oPreviewComment: oPreviewComment,
					bShowImages: true,
					bClearForm: false
				});
				__MSLDetailMoveBottom();

				oMSL.setFollow({
					logId: oMSL.logId,
					bOnlyOn: true
				});
			}
		}
	}

	function fail_1try(error)
	{
		app.BasicAuth({
			'success': function(auth_data) 
			{
				options.params.sessid = auth_data.sessid_md5;
				ft.upload(fileURI, BX.message('MSLAjaxInterfaceFullURI'), win, fail_2try, options);
			},
			'failture': function() 
			{
				oMSL.hideCommentProgressBar(loadingId, oPreviewComment);

				oMSL.showCommentAlert({
					nodeId: oPreviewComment,
					action: 'upload_comment_photo',
					commentType: 'blog',
					callback: function() {
						oMSL.uploadCommentFile(fileURI);
					}
				});
			}
		});
	}

	function fail_2try(error) 
	{
		oMSL.hideCommentProgressBar(loadingId, oPreviewComment);

		oMSL.showCommentAlert({
			nodeId: oPreviewComment,
			action: 'upload_comment_photo',
			commentType: 'blog',
			callback: function() {
				oMSL.uploadCommentFile(fileURI);
			}
		});
	}

	var options = new FileUploadOptions();
	options.fileKey = 'file';
	options.fileName = fileURI.substr(fileURI.lastIndexOf('/') + 1);
	options.mimeType = "image/jpeg";
	options.params = {
		mobile_action: 'file_upload_' + oMSL.commentsType,
		action: 'file_comment_upload',
		commentsType: oMSL.commentsType,
		sessid: BX.bitrix_sessid(),
		site: BX.message("MSLSiteId"),
		lang: BX.message("MSLLangId"),
		nt: BX.message('MSLNameTemplate'),
		sl: BX.message('MSLShowLogin'),
		p_user: BX.message('MSLPathToUser'),
		p_bpost: commentVarPathToBlogPost,
		as: commentVarAvatarSize,
		dtf: commentVarDateTimeFormat,
		sr: BX.message('MSLShowRating')
	};

	if (oMSL.commentsType == 'blog')
	{
		options.params.post_id = commentVarBlogPostID;
	}
	else
	{
		options.params.log_id = oMSL.logId;
	}

	options.chunkedMode = false;

	var ft = new FileTransfer();
	ft.upload(fileURI, BX.message('MSLAjaxInterfaceFullURI'), win, fail_1try, options);
}

BitrixMSL.prototype.sendCommentWriting = function(xmlId, text)
{
	xmlId = (typeof (xmlId) != 'undefined' ? xmlId : '');
	text = (typeof text != 'undefined' ? text : '');

	if (xmlId.length <= 0)
	{
		return;
	}

	if (this.sendCommentWritingList[xmlId])
	{
		return;
	}

	clearTimeout(this.sendCommentWritingListTimeout[xmlId]);
	this.sendCommentWritingList[xmlId] = true;

	var BMAjaxWrapper = new MobileAjaxWrapper;
	BMAjaxWrapper.Wrap({
		'type': 'json',
		'method': 'POST',
		'url': BX.message('MSLSiteDir') + 'mobile/ajax.php',
		'data': {
			sessid: BX.bitrix_sessid(),
			site: BX.message('MSLSiteId'),
			lang: BX.message('MSLLangId'),
			nt: BX.message('MSLNameTemplate'),
			sl: BX.message('MSLShowLogin'),
			as: commentVarAvatarSize,
			action: 'send_comment_writing',
			mobile_action: 'send_comment_writing',
			ENTITY_XML_ID: xmlId
		},
		'callback': function(response_data) {},
		'callback_failure': function(response_data) {}
	});

	this.sendCommentWritingListTimeout[xmlId] = setTimeout(BX.delegate(function()
	{
		this.endCommentWriting(xmlId);
	}, this), 30000);
}

BitrixMSL.prototype.endCommentWriting = function(xmlId)
{
	xmlId = (typeof (xmlId) != 'undefined' ? xmlId : '');

	if (xmlId.length <= 0)
	{
		return;
	}

	clearTimeout(this.sendCommentWritingListTimeout[xmlId]);
	this.sendCommentWritingList[xmlId] = false;
}

BitrixMSL.prototype.setFocusOnComments = function(type)
{
	type = (type == 'list' ? 'list' : 'form');

	if (type == 'form')
	{
		this.setFocusOnCommentForm();
		__MSLDetailMoveBottom();
	}
	else if (type == 'list')
	{
		if (
			BX('post-comments-wrap')
			&& (
				app.enableInVersion(4)
				|| window.platform == "android"
			)
		)
		{
			document.body.scrollTop = BX('post-comments-wrap').offsetTop;
		}
	}

	return false;
}

BitrixMSL.prototype.setFocusOnCommentForm = function()
{
	if (app.enableInVersion(10))
	{
		BXMobileApp.UI.Page.TextPanel.focus();
	}

	return false;
}

BitrixMSL.prototype.buildDetailPageMenu = function(data)
{
	var menuItems = [];

	if (
		data.entry_type == 'blog'
		&& app.enableInVersion(3)
	)
	{
		var arSelectedDestinations = {
			a_users: [], 
			b_groups: []
		};

		if (
			false
			&& typeof data.destinations != 'undefined'
		)
		{
			if (typeof data.destinations.U != 'undefined')
			{
				for (var key in data.destinations.U)
				{
					var objUser = data.destinations.U[key];
					if (typeof objUser.ID != 'undefined')
					{
						arSelectedDestinations.a_users.push(parseInt(objUser.ID) > 0 ? parseInt(objUser.ID) : 0);
					}
				}
			}
			
			if (typeof data.destinations.SG != 'undefined')
			{
				for (var key in data.destinations.SG)
				{
					if (parseInt(key) > 0)
					{
						arSelectedDestinations.b_groups.push(parseInt(key));
					}
				}
			}
		}

		if (
			arSelectedDestinations.a_users.length > 0
			|| arSelectedDestinations.b_groups.length > 0
		)
		{
			menuItems.push({
				name: BX.message('MSLSharePost'),
				action: function()
				{
					app.openTable({
						callback: function() { 
							oMSL.shareBlogPost(); 
						},
						url: BX.message('MSLSiteDir') + 'mobile/index.php?mobile_action=' + (BX.message('MSLIsExtranetSite') == 'Y' ? 'get_group_list' : 'get_usergroup_list'),
						markmode: true,
						multiple: true,
						return_full_mode: true,
						user_all: true,
						showtitle: true,
						modal: true,
						selected: arSelectedDestinations,
						alphabet_index: true,
						okname: BX.message('MSLShareTableOk'),
						cancelname: BX.message('MSLShareTableCancel'),
						outsection: (BX.message('MSLIsDenyToAll') != 'Y')
					});
				},
				arrowFlag: false,
				icon: "add"
			});
		}

		if (
			typeof data.post_perm != 'undefined'
			&& data.post_perm == 'W'
		)
		{
			menuItems.push({
				name: BX.message('MSLEditPost'),
				action: function() {
					oMSL.editBlogPost({
						'feed_id': data.feed_id,
						'post_id': parseInt(data.post_id)
					}); 
				},
				arrowFlag: false,
				icon: "edit"
			});

			menuItems.push({
				name: BX.message('MSLDeletePost'),
				action: function() { 
					oMSL.deleteBlogPost({
						'post_id': parseInt(data.post_id)
					}); 
				},
				arrowFlag: false,
				icon: "delete"
			});							
		}
	}

	if (oMSL.bUseFollow)
	{
		menuItems.push({
			name: (oMSL.bFollow ? BX.message('MSLFollowY') : BX.message('MSLFollowN')),
			image: "/bitrix/templates/mobile_app/images/lenta/menu/eye.png",
			action: function()
			{
				oMSL.setFollow({
					logId: oMSL.logId,
					pageId: oMSL.pageId,
					bOnlyOn: false,
					bAjax: true,
					bRunEvent: true
				});
			},
			arrowFlag: false,
			feature: 'follow'
		});
	}

	return menuItems;
}

BitrixMSL.prototype.showPageMenu = function(type)
{
	type = (type == 'detail' ? 'detail' : 'list');
	var menuItems = (type == 'detail' ? this.detailPageMenuItems : this.listPageMenuItems);
	var title = (type == 'detail' 
		? (BX.message("MSLLogEntryTitle") != null ? BX.message("MSLLogEntryTitle") : '')
		: (BX.message("MSLLogTitle") != null ? BX.message("MSLLogTitle") : '')
	);

	if (menuItems.length > 0)
	{
		app.menuCreate({
			items: menuItems
		});

		if (app.enableInVersion(10))
		{
			BXMobileApp.UI.Page.TopBar.title.setText(title);
			BXMobileApp.UI.Page.TopBar.title.setCallback(function ()
			{
				app.menuShow();
			});
			BXMobileApp.UI.Page.TopBar.title.show();
		}
		else
		{
			app.addButtons({
				menuButton:{
					type: "context-menu",
					style: "custom",
					callback: function() {
						app.menuShow();
					}
				}
			});
		}
	}
	else
	{
		if (app.enableInVersion(10))
		{
			BXMobileApp.UI.Page.TopBar.title.setText(title);
			BXMobileApp.UI.Page.TopBar.title.setCallback("");
			BXMobileApp.UI.Page.TopBar.title.show();
		}
		else
		{
			app.removeButtons({
				position: 'right'
			});
		}
	}
}

BitrixMSL.prototype.setFollowMenuItemName = function()
{
	var menuItem = false;
	for(var i = 0; i < this.detailPageMenuItems.length; i++)
	{
		menuItem = this.detailPageMenuItems[i];
		if (
			typeof menuItem.feature != 'undefined'
			&& menuItem.feature == 'follow'
		)
		{
			menuItem.name = (oMSL.bFollow ? BX.message('MSLFollowY') : BX.message('MSLFollowN'));
			this.detailPageMenuItems[i] = menuItem;
			this.showPageMenu('detail');
			break;
		}
	}
}

BitrixMSL.prototype.setDefaultFollowMenuItemName = function()
{
	var menuItem = false;
	for(var i = 0; i < this.listPageMenuItems.length; i++)
	{
		menuItem = this.listPageMenuItems[i];
		if (
			typeof menuItem.feature != 'undefined'
			&& menuItem.feature == 'follow'
		)
		{
			menuItem.name = (oMSL.bFollowDefault ? BX.message('MSLMenuItemFollowDefaultY') : BX.message('MSLMenuItemFollowDefaultN'));
			this.listPageMenuItems[i] = menuItem;
			this.showPageMenu('list');
			break;
		}
	}
}

BitrixMSL.prototype.replyToComment = function(userId, userName, event)
{
	userId = parseInt(userId);

	if (app.enableInVersion(10))
	{
		var currentText = (typeof this.commentTextCurrent != 'undefined' ? this.commentTextCurrent : '');
		this.arMention[userName] = '[USER=' + userId + ']' + userName + '[/USER]'

		currentText = currentText + ' ' + userName + ', ';
		if (typeof this.commentTextCurrent != 'undefined')
		{
			this.commentTextCurrent = currentText;
		}

		BXMobileApp.UI.Page.TextPanel.setText(currentText);
		BXMobileApp.UI.Page.TextPanel.focus();
	}

	return (
		typeof (event) != 'undefined' 
			? BX.PreventDefault(event) 
			: false
	);
}

BitrixMSL.prototype.buildCommentRatingNode = function(arComment, anchor_id)
{
	var you_like_class = (
		arComment["EVENT"]["RATING_USER_VOTE_VALUE"] > 0
			? "post-comment-likes-liked" 
			: "post-comment-likes"
	);

	var bCounterNeeded = (
		parseInt(arComment["EVENT"]["RATING_TOTAL_POSITIVE_VOTES"]) > 1
		|| (
			parseInt(arComment["EVENT"]["RATING_TOTAL_POSITIVE_VOTES"]) == 1
			&& arComment["EVENT"]["RATING_USER_VOTE_VALUE"] <= 0
		)
	);

	var ratingNode = BX.create('DIV', {
		props: {
			id: 'bx-ilike-button-' + arComment["EVENT"]["RATING_TYPE_ID"] + '-' + arComment["EVENT"]["RATING_ENTITY_ID"] + '-' + anchor_id,
			className: you_like_class
		},
		children: [
			BX.create('DIV', {
				props: {
					className: 'post-comment-likes-text'
				},
				html: (bCounterNeeded ? BX.message('MSLLike2') : BX.message('MSLLike'))
			}),
			BX.create('DIV', {
				props: {
					id: 'bx-ilike-count-' + arComment["EVENT"]["RATING_TYPE_ID"] + '-' + arComment["EVENT"]["RATING_ENTITY_ID"] + '-' + anchor_id,
					className: 'post-comment-likes-counter'
				},
				style: {
					display: (bCounterNeeded ? 'inner-block' : 'none')
				},
				events: { 
					click: function(e) {
						RatingLikeComments.List(arComment["EVENT"]["RATING_TYPE_ID"] + '-' + arComment["EVENT"]["RATING_ENTITY_ID"] + '-' + anchor_id);
						BX.PreventDefault(e);
					}
				},
				html: '' + parseInt(arComment["EVENT"]["RATING_TOTAL_POSITIVE_VOTES"]) + ''
			})
		]
	});

	return ratingNode;
}

BitrixMSL.prototype.buildCommentReplyNode = function(arComment)
{
	var replyNode = BX.create('DIV', {
		props: {
			className: 'post-comment-reply'
		},
		children: [
			BX.create('DIV', {
				props: {
					className: 'post-comment-reply-text'
				},
				events: { 
					click: function(e) {
						oMSL.replyToComment(arComment["EVENT"]["USER_ID"], arComment["CREATED_BY"]["FORMATTED"], (e || window.event));
					}
				},
				html: BX.message('MSLReply')
			})
		]
	});

	return replyNode;
}

BitrixMSL.prototype.drawRatingFooter = function(ratingFooterText)
{
	if (BX('rating-footer'))
	{
		BX('rating-footer').innerHTML = ratingFooterText;
		this.parseAndExecCode(ratingFooterText, 0);
	}
}

BitrixMSL.prototype.parseMentions = function(text)
{
	var parsedText = text;

	if (typeof this.arMention != 'undefined')
	{
		for (var userName in this.arMention) 
		{
			parsedText = parsedText.replace(new RegExp(userName, 'g'), this.arMention[userName]);
		}

		this.arMention = [];
		this.commentTextCurrent = '';
	}

	return parsedText;
}

BitrixMSL.prototype.unParseMentions = function(text)
{
	var unParsedText = text;

	unParsedText = unParsedText.replace(/\[USER\s*=\s*(\d+)\]((?:\s|\S)*?)\[\/USER\]/ig,
		function(str, id, userName)
		{
			oMSL.arMention[userName] = str;
			return userName;
		}
	);

	return unParsedText;
}

BitrixMSL.prototype.expandText = function(id)
{
	var checkBlock = (
		typeof id == 'undefined'
		|| id == null 
		|| !BX('post_block_check_cont_' + id) 
			? BX('post_block_check_cont') 
			: BX('post_block_check_cont_' + id)
	);

	if (checkBlock)
	{
		if (BX.hasClass(checkBlock, "post-item-post-block"))
		{
			BX.addClass(checkBlock, 'post-item-post-block-full');
			BX.removeClass(checkBlock, 'post-item-post-block');
		}
		else if (BX.hasClass(checkBlock, "lenta-info-block-wrapp"))
		{
			BX.addClass(checkBlock, 'lenta-info-block-wrapp-full');
			BX.removeClass(checkBlock, 'lenta-info-block-wrapp');
		}

		if (BX('post_more_block_' + id))
		{
			BX('post_more_block_' + id).style.display = "none";
		}
		else if (BX('post_more_block'))
		{
			BX('post_more_block').style.display = "none";
		}

		if (BX('post_more_limiter_' + id))
		{
			BX('post_more_limiter_' + id).style.display = "none";
		}
		else if (BX('post_more_limiter'))
		{
			BX('post_more_limiter').style.display = "none";
		}

		var arImages = BX.findChildren(checkBlock, { tagName: "img" }, true);
		var src = null;

		if (BX.type.isArray(arImages))
		{
			for (var i = 0; i < arImages.length; i++)
			{
				if (
					BX.type.isString(arImages[i].getAttribute('data-src'))
					&& arImages[i].getAttribute('data-src').length > 0
					&& !!arImages[i].id
				)
				{
					BitrixMobile.LazyLoad.registerImage(arImages[i].id);
				}
			}
			BitrixMobile.LazyLoad.showImages(false);
		}
	}
}

BitrixMSL.prototype.showEmptyCommentsBlockWaiter = function(el, enable)
{
	enable = !!enable;
	if (!BX(el))
	{
		return;
	}

	var waiterBlock = BX.findChild(BX(el), { className: 'post-comments-load-btn-wrap' }, true, false);
	if (waiterBlock)
	{
		BX.cleanNode(waiterBlock, true);
	}

	if (enable)
	{
		BX(el).appendChild(BX.create('DIV', {
			props: {
				className: 'post-comments-load-btn-wrap'
			},
			children: [
				BX.create('DIV', {
					props: {
						className: 'post-comments-loader'
					}
				}),
				BX.create('DIV', {
					props: {
						className: 'post-comments-load-text'
					},
					text: BX.message('MSLDetailCommentsLoading')
				})
			]
		}));

	}
}

BitrixMSL.prototype.showEmptyCommentsBlockFailed = function(el, ts, bPullDown, bMoveBottom)
{
	if (!BX(el))
	{
		return;
	}

	BX(el).appendChild(BX.create('DIV', {
		props: {
			className: 'post-comments-load-btn-wrap'
		},
		children: [
			BX.create('DIV', {
				props: {
					className: 'post-comments-load-text'
				},
				text: BX.message('MSLDetailCommentsFailed')
			}),
			BX.create('A', {
				props: {
					className: 'post-comments-load-btn'
				},
				events: { 
					click: function() {
						BX.cleanNode(this.parentNode, true);
						oMSL.getComments({
							ts: ts,
							bPullDown: bPullDown,
							obFocus: {
								form: false
							}
						});
					},
					touchstart: function() 
					{
						this.classList.add('post-comments-load-btn-active');
					},
					touchend: function() 
					{
						this.classList.remove('post-comments-load-btn-active');
					}
				},
				text: BX.message('MSLDetailCommentsReload')
			})
		]
	}));
}

BitrixMSL.prototype.showMoreComments = function(params)
{
	var moreButton = BX('post-comment-more');
	var url = '';
	var request_data = null;
	var lastComment = null;

	if (moreButton)
	{
		BX.addClass(moreButton, 'post-comments-button-waiter');
	}

	if (this.commentsType == 'blog')
	{
		lastComment = BX("comcntshow").value;
		var urlToMore = BX.message('SBPCurlToMore');
		url = urlToMore.replace(/#comment_id#/, lastComment);

		if (typeof params.postId != 'undefined')
		{
			url = url.replace(/#post_id#/, params.postId);
		}

		request_data = '';
	}
	else
	{
		url = BX.message('MSLSiteDir') + 'mobile/ajax.php',

		request_data = {
			sessid: BX.bitrix_sessid(),
			site: BX.message('SITE_ID'),
			lang: BX.message('LANGUAGE_ID'),
			logid: this.logId,
			as: commentVarAvatarSize,
			nt: commentVarNameTemplate,
			sl: commentVarShowLogin,
			dtf: commentVarDateTimeFormat,
			p_user: commentVarPathToUser,
			action: 'get_comments',
			mobile_action: 'get_comments'
		};

		if (typeof params.lastCommentId != 'undefined')
		{
			request_data.last_comment_id = params.lastCommentId;
		}

		if (typeof params.lastCommentTimestamp != 'undefined')
		{
			request_data.last_comment_ts = params.lastCommentTimestamp;
		}
	}

	var BMAjaxWrapper = new MobileAjaxWrapper;
	BMAjaxWrapper.Wrap({
		'type': 'json',
		'method': (this.commentsType == 'blog' ? 'GET' : 'POST'),
		'url': url,
		'data': request_data,
		'processData': true,
		'callback': function(response_data)
		{
			if (moreButton)
			{
				BX.removeClass(moreButton, 'post-comments-button-waiter');
			}

			if (oMSL.commentsType == 'blog')
			{
				if (typeof response_data.TEXT != 'undefined')
				{
					BX('blog-comment-hidden').innerHTML = response_data.TEXT + BX('blog-comment-hidden').innerHTML;
					BX('blog-comment-hidden').style.display = "block"; 
					oMSL.parseAndExecCode(response_data.TEXT);				
				}
				else if (moreButton)
				{
					BX.removeClass(moreButton, 'post-comments-button-waiter');
				}
			}
			else
			{
				if (typeof response_data.arComments != 'undefined')
				{
					__MSLShowComments(response_data.arComments);
				}
				else if (moreButton)
				{
					BX.removeClass(moreButton, 'post-comments-button-waiter');
				}
			}
		},
		'callback_failure': function(data)
		{
			if (moreButton)
			{
				BX.removeClass(moreButton, 'post-comments-button-waiter');
			}
		}
	});
}

BitrixMSL.prototype.onMPFSent = function(post_data, groupID)
{
	if (
		post_data.LiveFeedID != window.LiveFeedID
		&& (
			typeof post_data.data.post_id == 'undefined'
			|| parseInt(post_data.data.post_id) <= 0
		)
	)
	{
		return;
	}

	window.scrollTo(0,0);
	oMSL.initScroll(false);

	if (
		BX('blog-post-new-waiter')
		&& (
			typeof post_data.data.post_id == 'undefined'
			|| parseInt(post_data.data.post_id) <= 0
		)
	)
	{
		BX('blog-post-new-waiter').style.display = 'block';
	}

	post_data.data.response_type = 'json';

	var BMAjaxWrapper = new MobileAjaxWrapper;
	BMAjaxWrapper.Wrap({
		'type': 'json',
		'method': 'POST',
		'url': curUrl,
		'data': post_data.data,
		'processData' : true,
		'callback': function(post_response_data) 
		{
			if (
				(
					typeof post_data.data.post_id == 'undefined'
					|| parseInt(post_data.data.post_id) <= 0
				)
				&& (
					typeof (post_response_data.error) == "undefined"
					|| post_response_data.error.length <= 0
				)
				&& post_response_data.text.length > 0
			) // add
			{
				BX.MSL.DBDelete(groupID);
				if (app.enableInVersion(12))
				{
					var selectedDestinations = {
						a_users: [],
						b_groups: []
					};
					oMSL.clearPostFormDestination(selectedDestinations, groupID);
					oMSL.setPostFormParams({
						selectedRecipients: selectedDestinations
					});
					oMSL.setPostFormParams({
						messageText: ''
					});
				}

				if (BX('blog-post-new-waiter'))
				{
					BX('blog-post-new-waiter').style.display = 'none';
				}

				var new_post_id = 'new_post_ajax_' + Math.random();
				var new_post = BX.create('DIV', {
					props: {
						id: new_post_id
					},
					html: post_response_data.text
				});
				BX('blog-post-first-after').parentNode.insertBefore(new_post, BX('blog-post-first-after').nextSibling);

				var ob = BX(new_post_id);
				var obNew = BX.processHTML(ob.innerHTML, true);
				var scripts = obNew.SCRIPT;
				BX.ajax.processScripts(scripts, true);
				BitrixMobile.LazyLoad.showImages(); // for a new post

				__MSLOnErrorClick();
			}
			else if (
				typeof post_data.data.post_id != 'undefined'
				&& parseInt(post_data.data.post_id) > 0
				&& (
					typeof (post_response_data.error) == "undefined"
					|| post_response_data.error.length <= 0
				)
			) // edit
			{
				var new_post_id = 'new_post_ajax_' + Math.random();
				var new_post = BX.create('DIV', {
					props: {
						id: new_post_id
					},
					html: post_response_data.text
				});
				BX('blog-post-first-after').parentNode.insertBefore(new_post, BX('blog-post-first-after').nextSibling);

				if (
					typeof post_data.data.log_id != 'undefined'
					&& parseInt(post_data.data.log_id) > 0
					&& BX('post_block_check_cont_' + parseInt(post_data.data.log_id))
					&& BX('post_item_top_' + parseInt(post_data.data.log_id))
				)
				{
					app.onCustomEvent('onEditedPostInserted', {
						'detailText': BX('post_block_check_cont_' + parseInt(post_data.data.log_id)).innerHTML,
						'topText': BX('post_item_top_' + parseInt(post_data.data.log_id)).innerHTML,
						'nodeID': new_post_id,
						'logID': post_data.data.log_id
					});
				}
				BX.cleanNode(new_post, true);
				BitrixMobile.LazyLoad.showImages(); // for an edited post
			}
			else
			{
				if (
					typeof post_data.data.post_id == 'undefined'
					|| parseInt(post_data.data.post_id) <= 0
				) // only when add
				{
					BX.MSL.DBSave(post_data.data, groupID);

					if (app.enableInVersion(12))
					{
						var selectedDestinations = {
							a_users: [],
							b_groups: []
						};

						oMSL.buildSelectedDestinations(
							post_data.data,
							selectedDestinations
						);

						oMSL.setPostFormParams({
							selectedRecipients: selectedDestinations
						});

						oMSL.setPostFormParams({
							messageText: post_data.data.POST_MESSAGE
						});
					}
				}

				oMSL.showPostError(post_response_data.error);
			}

			if (BX('blog-post-new-waiter'))
			{
				BX('blog-post-new-waiter').style.display = 'none';
			}

			app.onCustomEvent('onEditedPostFailed', {});
			oMSL.initScroll(true);
		},
		'callback_failure': function() 
		{
			if (
				typeof post_data.data.post_id == 'undefined'
				|| parseInt(post_data.data.post_id) <= 0
			) // only when add
			{
				BX.MSL.DBSave(post_data.data, groupID);

				if (app.enableInVersion(12))
				{
					var selectedDestinations = {
						a_users: [],
						b_groups: []
					};

					oMSL.buildSelectedDestinations(
						post_data.data,
						selectedDestinations
					);

					oMSL.setPostFormParams({
						selectedRecipients: selectedDestinations
					});

					oMSL.setPostFormParams({
						messageText: post_data.data.POST_MESSAGE
					});
				}
			}

			oMSL.showPostError();

			if (BX('blog-post-new-waiter'))
			{
				BX('blog-post-new-waiter').style.display = 'none';
			}

			app.onCustomEvent('onEditedPostFailed', {});
			oMSL.initScroll(true);
		}
	});
}

BitrixMSL.prototype.onLogEntryFavorites = function(log_id, page_id)
{
	var favoritesBlock = BX('log_entry_favorites_' + log_id);

	if (
		favoritesBlock
		&& (
			BX.message('MSLPageId') == undefined
			|| BX.message('MSLPageId') != page_id
		)
	)
	{
		var strFavoritesOld = (favoritesBlock.getAttribute("data-favorites") == "Y" ? "Y" : "N");
		var strFavoritesNew = (strFavoritesOld == "Y" ? "N" : "Y");

		if (strFavoritesOld == "Y")
		{
			BX.removeClass(favoritesBlock, 'lenta-item-fav-active');
		}
		else
		{
			BX.addClass(favoritesBlock, 'lenta-item-fav-active');
		}

		favoritesBlock.setAttribute("data-favorites", strFavoritesNew);
	}
}

BitrixMSL.prototype.onLogEntryCommentAdd = function(log_id, iValue) // for the feed
{
	var val, old_value;

	if (typeof iValue == 'undefined')
	{
		iValue = 0;
	}

	if (
		BX('informer_comments_' + log_id)
		&& !BX('informer_comments_new_' + log_id)
	) // detail page
	{
		if (parseInt(iValue) > 0)
		{
			val = parseInt(iValue);
		}
		else
		{
			old_value = (
				BX('informer_comments_' + log_id).innerHTML.length > 0 
					? parseInt(BX('informer_comments_' + log_id).innerHTML) 
					: 0
			);
			val = old_value + 1;
		}
		BX('informer_comments_' + log_id).innerHTML = val;
		BX('informer_comments_' + log_id).style.display = 'inline-block';
		BX('informer_comments_text2_' + log_id).style.display = 'inline-block';
		BX('informer_comments_text_' + log_id).style.display = 'none';
	}

	if (BX('comcntleave-all')) // more comments
	{
		if (parseInt(iValue) > 0)
		{
			val = parseInt(iValue);
		}
		else
		{
			old_value = (BX('comcntleave-all').innerHTML.length > 0 ? parseInt(BX('comcntleave-all').innerHTML) : 0);
			val = old_value + 1;
		}
		BX('comcntleave-all').innerHTML = val;
	}
}

BitrixMSL.prototype.onLogEntryRatingLike = function(params)
{
	var rating_id = params.ratingId;
	var voteAction = params.voteAction;
	var logId = parseInt(params.logId);
	var userId = (typeof (params.userId) != 'undefined' ? parseInt(params.userId) : BX.message('USER_ID'));

	if (
		logId <= 0
		&& this.isUserCurrent(userId)
	) /* pull from the same author */
	{
		return;
	}

	var ratingBox = BX('bx-ilike-box-' + rating_id);
	var ratingButton = BX('bx-ilike-button-' + rating_id);
	if (!ratingButton)
	{
		ratingButton = BX('rating_button');
	}

	if (
		!ratingButton
		|| (
			!BX.hasClass(ratingButton, 'post-item-inform-likes')
			&& !BX.hasClass(ratingButton, 'post-item-inform-likes-active')
		)
	)
	{
		return;
	}

	var tmpNode = null;
	var old_value = 0;
	var val = 0;

	var ratingBlock = null;
	var ratingFooter = null;

	if (
		logId > 0
		&& BX('rating_block_' + logId)
	)
	{
		ratingBlock = BX('rating_block_' + logId);
	}
	else
	{
		ratingBlock = BX('rating_button_cont');
	}

	if (
		!ratingBlock
		&& ratingBox
	)
	{
		ratingBlock = BX.findParent(ratingBox, { 'tag': 'SPAN', 'className': 'bx-ilike-block' } );
	}

	if (ratingBox)
	{
		tmpNode = BX.findChild(ratingBox, {className: 'post-item-inform-right-text'}, true, false);
		if (
			tmpNode
			&& ratingBlock
		)
		{
			old_value = parseInt(ratingBlock.getAttribute('data-counter'));
			val = (voteAction == 'plus' ? (old_value + 1) : (old_value - 1));
			tmpNode.innerHTML = val;
		}

		if (this.isUserCurrent(userId))
		{
			BX.removeClass(ratingButton, (voteAction == 'plus' ? 'post-item-inform-likes' : 'post-item-inform-likes-active'));
			BX.addClass(ratingButton, (voteAction == 'plus' ? 'post-item-inform-likes-active' : 'post-item-inform-likes'));
		}

		var bFull = (
			(
				BX.hasClass(ratingButton, 'post-item-inform-likes-active')
				&& val > 1
			)
			|| (
				!BX.hasClass(ratingButton, 'post-item-inform-likes-active')
				&& val > 0
			)
		);

		tmpNode = BX.findChild(ratingButton, {className: 'post-item-inform-right'}, true, false);
		if (tmpNode)
		{
			tmpNode.innerHTML = val;
			tmpNode.style.display = (bFull ? 'inline-block' : 'none');

			tmpNode = BX.findChild(ratingButton, {className: 'post-item-inform-left'}, true, false);
			tmpNode.innerHTML = (bFull ? BX.message('MSLLike2') : BX.message('MSLLike'));
		}
	}

	if (ratingBlock)
	{
		ratingBlock.setAttribute('data-counter', parseInt(val));
	}

	if (
		logId > 0
		&& BX('rating-footer_' + logId)
	)
	{
		ratingFooter = BX('rating-footer_' + logId);
	}
	else if (ratingBlock)
	{
		ratingFooter = BX('rating-footer');
	}

	if (
		!ratingFooter
		&& ratingBlock
		&& typeof ratingBlock.id != 'undefined'
	)
	{
		var arMatch = ratingBlock.id.match(/^rating_block_([\d]+)$/i);
		if (arMatch != null)
		{
			ratingFooter = BX('rating-footer_' + arMatch[1]);
		}
	}

	if (ratingFooter)
	{
		var youNode = BX.findChild(ratingFooter, { className: 'rating-footer-you' }, true, false);
		var youAndOthersNode = BX.findChild(ratingFooter, { className: 'rating-footer-youothers' }, true, false);
		var othersNode = BX.findChild(ratingFooter, { className: 'rating-footer-others' }, true, false);

		this.recalcRatingFooter({
			obYouNode: youNode,
			obYouAndOthersNode: youAndOthersNode,
			obOthersNode: othersNode,
			bSelf: oMSL.isUserCurrent(userId),
			voteAction: voteAction,
			val: val
		});
	}

	if (
		this.isUserCurrent(userId)
		&& typeof BXRL != 'undefined'
		&& typeof BXRL[rating_id] != 'undefined'
	)
	{
		BXRL[rating_id].lastVote = (voteAction == 'plus' ? 'plus' : 'cancel');
	}
}

BitrixMSL.prototype.recalcRatingFooter = function(params)
{
	var youAndOthersNodeCount = BX.findChild(params.obYouAndOthersNode, {className: 'rating-footer-others-count'}, true, false);
	var youAndOthersNodeUsersTitle = BX.findChild(params.obYouAndOthersNode, {className: 'rating-footer-others-users-title'}, true, false);
	var othersNodeCount = BX.findChild(params.obOthersNode, {className: 'rating-footer-others-count'}, true, false);
	var othersNodeUsersTitle = BX.findChild(params.obOthersNode, {className: 'rating-footer-others-users-title'}, true, false);

	var obNodeSet = {
		obYouAndOthersNode: params.obYouAndOthersNode,
		obOthersNode: params.obOthersNode,
		obYouNode: params.obYouNode
	};

	youAndOthersNodeCount.innerHTML = (params.val - 1);
	othersNodeCount.innerHTML = params.val;
	youAndOthersNodeUsersTitle.innerHTML = this.getUsersTitleByCount(params.val - 1);
	othersNodeUsersTitle.innerHTML = this.getUsersTitleByCount(params.val);

	if (params.voteAction == 'plus')
	{
		if (params.bSelf)
		{
			if (params.val > 1)
			{
				this.showRatingFooterNode('you_and_others', obNodeSet);
			}
			else
			{
				this.showRatingFooterNode('you', obNodeSet);
			}
		}
		else
		{
			if (params.obYouNode.style.display == 'block')
			{
				this.showRatingFooterNode('you_and_others', obNodeSet);
			}
			else
			{
				this.showRatingFooterNode('others', obNodeSet);
			}
		}
	}
	else /* cancel */
	{
		if (params.val <= 0)
		{
			this.showRatingFooterNode('none', obNodeSet);
		}
		else if (params.bSelf)
		{
			this.showRatingFooterNode('others', obNodeSet);
		}
		else
		{
			if (
				params.val == 1
				&& params.obYouAndOthersNode.style.display == 'block'
			)
			{
				this.showRatingFooterNode('you', obNodeSet);
			}
		}
	}
}

BitrixMSL.prototype.showRatingFooterNode = function(code, obNodeSet)
{
	obNodeSet.obYouAndOthersNode.style.display = (code == 'you_and_others' ? 'block' : 'none');
	obNodeSet.obOthersNode.style.display = (code == 'others' ? 'block' : 'none');
	obNodeSet.obYouNode.style.display = (code == 'you' ? 'block' : 'none');

	if (
		BX('rating-footer-wrap')
		&& !BX('lenta_notifier') // not in lenta
	)
	{
		BX('rating-footer-wrap').style.display = (code == 'none' ? 'none' : 'block');
	}
}

BitrixMSL.prototype.getUsersTitleByCount = function(val)
{
	return (
		val % 10 == 1
			? BX.message('MSLLikeUsers1')
			: BX.message('MSLLikeUsers2')
	);
}

BitrixMSL.prototype.onLogCommentRatingLike = function(params)
{
	var rating_id = params.ratingId;
	var voteAction = params.voteAction;
	var userId = (typeof (params.userId) != 'undefined' ? parseInt(params.userId) : BX.message('USER_ID'));
	var counterNode = BX('bx-ilike-count-' + rating_id);

	if (!counterNode)
	{
		return;
	}

	var oldValue = parseInt(counterNode.innerHTML);
	var val = (voteAction == 'plus' ? (oldValue + 1) : (oldValue - 1));

	if (this.isUserCurrent(userId))
	{
		return;
	}

	var ratingButton = BX('bx-ilike-button-' + rating_id);
	if (
		!ratingButton
		|| (
			!BX.hasClass(ratingButton, 'post-comment-likes')
			&& !BX.hasClass(ratingButton, 'post-comment-likes-liked')
		)
	)
	{
		return;
	}

	if (counterNode)
	{
		var bFull = (
			(
				BX.hasClass(ratingButton, 'post-comment-likes-liked')
				&& val > 1
			)
			|| (
				!BX.hasClass(ratingButton, 'post-comment-likes-liked')
				&& val > 0
			)
		);

		counterNode.innerHTML = val;
		counterNode.style.display = (bFull ? 'inline-block' : 'none');

		var tmpNode = BX.findChild(ratingButton, {className: 'post-comment-likes-text'}, true, false);
		tmpNode.innerHTML = (bFull ? BX.message('MSLLike2') : BX.message('MSLLike'));
	}

}

BitrixMSL.prototype.checkVisibility = function(image)
{
	var img = image.node;

	if (BX.hasClass(document.body, "lenta-page"))
	{
		var isVisible = oMSL.checkImageOffset(img);
		if (isVisible === false)
		{
			image.status = BitrixMobile.LazyLoad.status.hidden;
		}
		return isVisible;
	}
	else if (
		!oMSL.isPostFull() 
		&& oMSL.isImageFromPost(image)
	)
	{
		return oMSL.checkImageOffset(img);
	}

	return true;
}

BitrixMSL.prototype.checkImageOffset = function(img)
{
	if (BX.hasClass(img.parentNode, "post-item-attached-img-block"))
	{
		//Attached post image
		return img.parentNode.parentNode.offsetTop < 315;
	}
	else
	{
		//Inline post image
		return img.offsetTop < 315;
	}
}

BitrixMSL.prototype.isPostFull = function()
{
	var checkBlock = (
		BX("post_block_check_cont_" + this.logId, true) 
			? BX("post_block_check_cont_" + this.logId, true) 
			: BX("post_block_check_cont", true)
	);

	return (
		BX.hasClass(checkBlock, "post-item-post-block-full")
		|| BX.hasClass(checkBlock, "lenta-info-block-wrapp-full")
	);
}

BitrixMSL.prototype.isImageFromPost = function(image)
{
	if (typeof(image.fromPost) != "undefined")
	{
		return image.fromPost;
	}

	var maxParent = 5;
	var parent = image.node;

	while (parent = parent.parentNode)
	{
		if (BX.hasClass(parent, "post-item-post-block"))
		{
			image.fromPost = true;
			return true;
		}
		if (maxParent <= 0)
		{
			image.fromPost = false;
			return false;
		}

		maxParent--;
	}
}

BitrixMSL.prototype.isUserCurrent = function(userId)
{
	return (userId == BX.message('USER_ID'));
}

BitrixMSL.prototype.checkNodesHeight = function()
{
	var blockHeight = false;

	for (var logId in this.arBlockToCheck)
	{
		nodeToCheckId = this.arBlockToCheck[logId];
		if (
			BX(nodeToCheckId.more_overlay_id)
			&& BX(nodeToCheckId.text_block_id)
		)
		{
			blockHeight = BX(nodeToCheckId.text_block_id).offsetHeight;
			if (BX(nodeToCheckId.title_block_id))
			{
				blockHeight += BX(nodeToCheckId.title_block_id).offsetHeight
			}
			if (BX(nodeToCheckId.files_block_id))
			{
				blockHeight += BX(nodeToCheckId.files_block_id).offsetHeight
			}

			if (blockHeight >= 320)
			{
				BX(nodeToCheckId.more_overlay_id).style.display = "block";
				if (BX(nodeToCheckId.lenta_item_id))
				{
					BX.removeClass(BX(nodeToCheckId.lenta_item_id), "post-without-informers");
				}

				if (BX(nodeToCheckId.more_button_id))
				{
					BX(nodeToCheckId.more_button_id).style.display = "block";
				}
			}
			else
			{
				if (BX(nodeToCheckId.more_overlay_id))
				{
					BX(nodeToCheckId.more_overlay_id).style.display = "none";
				}

				if (BX(nodeToCheckId.more_button_id))
				{
					BX(nodeToCheckId.more_button_id).style.display = "none";
				}
			}
		}
	}
}

BitrixMSL.prototype.initScroll = function(enable, process_waiter)
{
	enable = !!enable;
	process_waiter = !!process_waiter

	if (enable)
	{
		BX.unbind(window, 'scroll', __MSLOnFeedScroll);
		BX.bind(window, 'scroll', __MSLOnFeedScroll);
	}
	else
	{
		BX.unbind(window, 'scroll', __MSLOnFeedScroll);
	}

	if (
		process_waiter
		&& BX('next_post_more')
	)
	{
		BX('next_post_more').style.display = (enable ? "block" : "none");
	}
}

BitrixMSL.prototype.onScrollDetail = function()
{
	oMSL.iLastActivityDate = Math.round(new Date().getTime() / 1000);
}

BitrixMSL.prototype.getComments = function(params)
{
	var ts = params.ts;
	var bPullDown = !!params.bPullDown;

	var bMoveBottom = (
		typeof params.obFocus.form == 'undefined'
		|| params.obFocus.form == "NO"
			? "NO"
			: "YES"
	);
	var bMoveCommentsTop = (
		typeof params.obFocus.comments == 'undefined'
		|| params.obFocus.comments == "NO"
			? "NO"
			: "YES"
	);
	var logID = this.logId;

	if (!bPullDown)
	{
		if (
			typeof params.bPullDownTop == 'undefined'
			|| params.bPullDownTop
		)
		{
			BXMobileApp.UI.Page.Refresh.start();
		}

		BX.cleanNode(BX('post-comments-wrap'));
		BX('post-comments-wrap').appendChild(BX.create('SPAN', {
			props: {
				id: 'post-comment-last-after'
			}
		}));

/*		__MSLDetailPullDownInit(false);*/
	}
	oMSL.showEmptyCommentsBlockWaiter(BX('post-comments-wrap'), true);

	var BMAjaxWrapper = new MobileAjaxWrapper;
	BMAjaxWrapper.Wrap({
		'type': 'json',
		'method': 'GET',
		'url': BX.message('MSLPathToLogEntry').replace("#log_id#", logID) + "&empty_get_comments=Y" + (typeof ts != 'undefined' && ts != null ? "&LAST_LOG_TS=" + ts : ""),
		'data': '',
		'processData': true,
		'callback': function(get_data)
		{
			if (bPullDown)
			{
				app.pullDownLoadingStop();
			}
			else if(
				typeof params.bPullDownTop == 'undefined'
				|| params.bPullDownTop
			)
			{
				BXMobileApp.UI.Page.Refresh.stop();
			}

			oMSL.showEmptyCommentsBlockWaiter(BX('post-comments-wrap'), false);

			if (typeof get_data.TEXT != 'undefined')
			{
				if (bPullDown)
				{
					BX.cleanNode(BX('post-comments-wrap'));
					if (typeof get_data.POST_NUM_COMMENTS != 'undefined')
					{
						if (BX('informer_comments_common'))
						{
							BX('informer_comments_common').style.display = 'inline';
							BX('informer_comments_common').innerHTML = parseInt(get_data.POST_NUM_COMMENTS);
							if (BX('informer_comments_all'))
							{
								BX('informer_comments_all').style.display = 'none';
							}
							if (BX('informer_comments_new'))
							{
								BX('informer_comments_new').style.display = 'none';
							}
						}
						app.onCustomEvent('onLogEntryCommentsNumRefresh', {
							log_id: logID,
							num: parseInt(get_data.POST_NUM_COMMENTS)
						});
					}
				}

				BX.clearNodeCache();

				BX('post-comments-wrap').innerHTML = get_data.TEXT;

				BX('post-comments-wrap').appendChild(BX.create('SPAN', {
					props: {
						id: 'post-comment-last-after'
					}
				}));

				var ob = BX.processHTML(BX('post-comments-wrap').innerHTML, true);
				var scripts = ob.SCRIPT;
				BX.ajax.processScripts(scripts, true);

				if (!bPullDown) // redraw form
				{
					if (BX('post-comments-form-wrap'))
					{
						BX('post-comments-form-wrap').innerHTML = '';
					}

					__MSLDetailPullDownInit(true);

					if (bMoveBottom == "YES")
					{
						oMSL.setFocusOnComments('form');
					}
					else if (bMoveCommentsTop == "YES")
					{
						oMSL.setFocusOnComments('list');
					}

					if (
						BX('post-comments-form-wrap')
						&& !app.enableInVersion(4) // only for old versions
					)
					{
						var BMAjaxWrapper = new MobileAjaxWrapper;
						BMAjaxWrapper.Wrap({
							'type': 'html',
							'method': 'GET',
							'url': BX.message('MSLPathToLogEntry').replace("#log_id#", logID) + "&empty_get_form=Y",
							'data': '',
							'processData': true,
							'callback': function(get_form_data) // ts?
							{
								if (get_form_data.length > 0)
								{
									BX('post-comments-form-wrap').style.display = 'block';
									BX('post-comments-form-wrap').innerHTML = get_form_data;
								}
								else
								{
									BX('post-comments-form-wrap').style.display = 'none';
								}
							},
							'callback_failure': function() { }
						});
					}
				}

				oMSL.iLastActivityDate = Math.round(new Date().getTime() / 1000);
			}
			else
			{
				if (!bPullDown)
				{
					oMSL.showEmptyCommentsBlockWaiter(BX('post-comments-wrap'), false);
/*					__MSLDetailPullDownInit(true);*/
				}
				oMSL.showEmptyCommentsBlockFailed(BX('post-comments-wrap'), ts, bPullDown, bMoveBottom);
			}
		},
		'callback_failure': function()
		{
			if (bPullDown)
			{
				app.pullDownLoadingStop();
				bReload = false;
			}
			else
			{
/*				__MSLDetailPullDownInit(true);*/
				BXMobileApp.UI.Page.Refresh.stop();
			}
			oMSL.showEmptyCommentsBlockWaiter(BX('post-comments-wrap'), false);
			oMSL.showEmptyCommentsBlockFailed(BX('post-comments-wrap'), ts, bPullDown, bMoveBottom);
		}
	});
}

BitrixMSL.prototype.setPostFormParams = function(params)
{
	if (typeof params == 'object')
	{
		for (var key in params)
		{
			if (
				key == 'selectedRecipients'
				|| key == 'messageText'
				|| key == 'messageFiles'
			)
			{
				oMSL.newPostFormParams[key] = params[key];
			}
		}
	}
}

BitrixMSL.prototype.setPostFormExtraData = function(params)
{
	if (typeof params == 'object')
	{
		for (var key in params)
		{
			if (
				key == 'hiddenRecipients'
				|| key == 'logId'
				|| key == 'postId'
				|| key == 'postAuthorId'
				|| key == 'messageUFCode'
				|| key == 'commentId'
				|| key == 'commentType'
				|| key == 'nodeId'
			)
			{
				oMSL.newPostFormExtraData[key] = params[key];
			}
		}
	}
}

BitrixMSL.prototype.setPostFormExtraDataArray = function(oExtraData)
{
	var ob = null;

	for (var prop in oExtraData)
	{
		if (oExtraData.hasOwnProperty(prop))
		{
			ob = {};
			ob[prop] = oExtraData[prop];
			this.setPostFormExtraData(ob);
		}
	}
}

BitrixMSL.prototype.getPostFormExtraData = function()
{
	return oMSL.newPostFormExtraData;
}

BitrixMSL.prototype.showNewPostForm = function(params)
{
	var entityType = (
		typeof params != 'undefined'
		&& typeof params.entityType != 'undefined'
			? params.entityType
			: 'post'
	);

	var extraData = this.getPostFormExtraData();

	var postFormParams = {
		attachButton : this.getPostFormAttachButton(),
		mentionButton: this.getPostFormMentionButton(),
		attachFileSettings: this.getPostFormAttachFileSettings(),
		extraData: (extraData ? extraData : {}),
		smileButton: {},
		okButton: {
			callback: function(data)
			{
				if (data.text.length > 0)
				{
					var postData = oMSL.buildPostFormRequestStub({
						type: entityType,
						extraData: data.extraData,
						text: oMSL.parseMentions(data.text)
					});

					var ufCode = data.extraData.messageUFCode;
					oMSL.buildPostFormFiles(
						postData,
						data.attachedFiles,
						{
							ufCode: ufCode
						}
					);

					if (entityType == 'post')
					{
						oMSL.buildPostFormDestinations(
							postData,
							data.selectedRecipients,
							(
								typeof data.extraData != 'undefined'
								&& typeof data.extraData.hiddenRecipients != 'undefined'
									? data.extraData.hiddenRecipients
									: []
							),
							{}
						);

						app.onCustomEvent('onMPFSent', {
							data: postData,
							LiveFeedID: window.LiveFeedID
						});

						if (
							typeof postData.post_id != 'undefined'
							&& parseInt(postData.post_id) > 0
						)
						{
							app.onCustomEvent('onMPFSentEditStart', {} );
						}
					}
					else if (entityType == 'comment')
					{
						app.onCustomEvent('onMPFCommentSent', {
							data: postData,
							detailPageId: data.extraData.commentType + '_' + parseInt(data.extraData.postId),
							nodeId: data.extraData.nodeId,
							ufCode: ufCode
						});
					}
				}
			},
			name: BX.message('MSLPostFormSend')
		}
	};

	if (typeof oMSL.newPostFormParams.messageText != 'undefined')
	{
		postFormParams.message = {
			text: oMSL.newPostFormParams.messageText
		};
	}

	if (typeof oMSL.newPostFormParams.messageFiles != 'undefined')
	{
		postFormParams.attachedFiles = oMSL.newPostFormParams.messageFiles;
	}

	if (entityType == 'post')
	{
		postFormParams.recipients = {
			dataSource: oMSL.getPostFormRecipientsDataSource()
		};

		if (typeof oMSL.newPostFormParams.selectedRecipients != 'undefined')
		{
			postFormParams.recipients.selectedRecipients = oMSL.newPostFormParams.selectedRecipients;
		}
	}

	return postFormParams;
}

BitrixMSL.prototype.findDestinationCallBack = function(element, index, array)
{
	return (element.id == this.value);
}

BitrixMSL.prototype.addPostFormDestination = function(selectedDestinations, params)
{
	if (
		typeof params == 'undefined'
		|| typeof params.type == 'undefined'
	)
	{
		return;
	}

	var searchRes = null;
	if (params.type == 'UA')
	{
		searchRes = selectedDestinations.a_users.some(this.findDestinationCallBack, { value: 0 });
		if (!searchRes)
		{
			selectedDestinations.a_users.push({
				id: 0,
				name: BX.message('MSLPostDestUA'),
				bubble_background_color: "#A7F264",
				bubble_text_color: "#54901E"
			});
		}
	}
	else if (params.type == 'U')
	{
		searchRes = selectedDestinations.a_users.some(this.findDestinationCallBack, { value: params.id });
		if (!searchRes)
		{
			selectedDestinations.a_users.push({
				id: params.id,
				name: params.name,
				bubble_background_color: "#BCEDFC",
				bubble_text_color: "#1F6AB5"
			});
		}
	}
	else if (params.type == 'SG')
	{
		searchRes = selectedDestinations.b_groups.some(this.findDestinationCallBack, { value: params.id });
		if (!searchRes)
		{
			selectedDestinations.b_groups.push({
				id: params.id,
				name: params.name,
				bubble_background_color: "#FFD5D5",
				bubble_text_color: "#B54827"
			});
		}
	}
}

BitrixMSL.prototype.getPostFormAttachButton = function()
{
	var attachButtonItems = [];

	if (
		BX.message('MSLbDiskInstalled') == 'Y'
		|| BX.message('MSLbWebDavInstalled') == 'Y'
	)
	{
		var diskAttachParams = {
			id: "disk",
			name: BX.message('MSLPostFormDisk'),
			dataSource: {
				multiple: "NO",
				url: (
					BX.message('MSLbDiskInstalled') == 'Y'
						? BX.message('MSLSiteDir') + 'mobile/?mobile_action=disk_folder_list&type=user&path=%2F&entityId=' + BX.message('USER_ID')
						: BX.message('MSLSiteDir') + 'mobile/webdav/user/' + BX.message('USER_ID') + '/'
				)

			}
		};

		var tableSettings = {
			searchField: "YES",
			showtitle: "YES",
			modal: "YES",
			name: BX.message('MSLPostFormDiskTitle')
		};

		//FIXME temporary workaround
		if (platform == "ios")
		{
			diskAttachParams.dataSource.table_settings = tableSettings;
		}
		else
		{
			diskAttachParams.dataSource.TABLE_SETTINGS = tableSettings;
		}

		attachButtonItems.push(diskAttachParams);
	}

	attachButtonItems.push({
		id: "mediateka",
		name: BX.message('MSLPostFormPhotoGallery')
	});

	attachButtonItems.push({
		id: "camera",
		name: BX.message('MSLPostFormPhotoCamera')
	});

	return {
        items: attachButtonItems
	};
}

BitrixMSL.prototype.getPostFormMentionButton = function()
{
	return {
		dataSource: {
			return_full_mode: "YES",
			outsection: "NO",
			okname: BX.message('MSLPostFormTableOk'),
			cancelname: BX.message('MSLPostFormTableCancel'),
			multiple: "NO",
			alphabet_index: "YES",
			url: BX.message('MSLSiteDir') + 'mobile/index.php?mobile_action=get_user_list'
		}
	};
}

BitrixMSL.prototype.getPostFormAttachFileSettings = function()
{
	return {
		resize: [
			40,
			1,
			1,
			1000,
			1000,
			0,
			0,
			false,
			true,
			false,
			null,
			0
		],
		sendLocalFileMethod: "base64",
		saveToPhotoAlbum: true
	};
}

BitrixMSL.prototype.getPostFormRecipientsDataSource = function()
{
	return {
		return_full_mode: "YES",
		outsection: "YES",
		okname: BX.message('MSLPostFormTableOk'),
		cancelname: BX.message('MSLPostFormTableCancel'),
		multiple: "YES",
		alphabet_index: "YES",
		showtitle: "YES",
		user_all: "YES",
		url: BX.message('MSLSiteDir') + 'mobile/index.php?mobile_action=' + (BX.message('MSLIsExtranetSite') == 'Y' ? 'get_group_list' : 'get_usergroup_list')
	};
}

BitrixMSL.prototype.buildPostFormFiles = function(postData, attachedFiles, params)
{
	ufCode = params.ufCode;

	if (typeof attachedFiles != 'undefined')
	{
		for (var key in attachedFiles)
		{
			if (typeof attachedFiles[key]["base64"] != 'undefined')
			{
				if (typeof postData.attachedFilesRaw == 'undefined')
				{
					postData.attachedFilesRaw = [];
				}

				postData.attachedFilesRaw.push(attachedFiles[key]);
				delete(attachedFiles[key]);
			}
			else if (
				(typeof attachedFiles[key]["VALUE"] != 'undefined') // Android
				|| (
					typeof attachedFiles[key]["dataAttributes"] != 'undefined'
					&& typeof attachedFiles[key]["dataAttributes"]["VALUE"] != 'undefined'
				) // iOS
			)
			{
				if (typeof postData[ufCode] == 'undefined')
				{
					postData[ufCode] = [];
				}

				if (typeof attachedFiles[key]["VALUE"] != 'undefined')
				{
					postData[ufCode].push(attachedFiles[key]["VALUE"]);
				}
				else
				{
					postData[ufCode].push(attachedFiles[key]["dataAttributes"]["VALUE"]);
				}
			}
		}
	}

	if (typeof postData[ufCode] == 'undefined')
	{
		postData[ufCode] = [];
	}

	if (typeof attachedFiles == 'undefined')
	{
		attachedFiles = [];
	}

	for (var keyOld in oMSL.newPostFormParams.messageFiles) /* existing */
	{
		for (var keyNew in attachedFiles)
		{
			if (
				oMSL.newPostFormParams.messageFiles[keyOld]["id"] == attachedFiles[keyNew]["id"]
				|| oMSL.newPostFormParams.messageFiles[keyOld]["id"] == attachedFiles[keyNew]["ID"]
			)
			{
				postData[ufCode].push(oMSL.newPostFormParams.messageFiles[keyOld]["id"]);
				break;
			}
		}
	}

	if (postData[ufCode].length <= 0)
	{
		postData[ufCode].push('empty');
	}
}

BitrixMSL.prototype.buildPostFormDestinations = function(postData, selectedRecipients, hiddenRecipients, params)
{
	var prefix = null;
	var id = null;
	var name = null;

	if (typeof selectedRecipients.a_users != 'undefined')
	{
		for (var key in selectedRecipients.a_users)
		{
			prefix = 'U';
			if (typeof postData.SPERM[prefix] == 'undefined')
			{
				postData.SPERM[prefix] = [];
			}

			if (typeof postData.SPERM_NAME[prefix] == 'undefined')
			{
				postData.SPERM_NAME[prefix] = [];
			}

			id = (
				typeof selectedRecipients.a_users[key].ID != 'undefined'
					? selectedRecipients.a_users[key].ID
					: selectedRecipients.a_users[key].id
			);

			name = (
				typeof selectedRecipients.a_users[key].NAME != 'undefined'
					? selectedRecipients.a_users[key].NAME
					: selectedRecipients.a_users[key].name
			);

			postData.SPERM[prefix].push(
				id == 0
					? 'UA'
					: 'U' + id
			);

			postData.SPERM_NAME[prefix].push(name);
		}
	}

	if (typeof selectedRecipients.b_groups != 'undefined')
	{
		for (var key in selectedRecipients.b_groups)
		{
			prefix = 'SG';
			if (typeof postData.SPERM[prefix] == 'undefined')
			{
				postData.SPERM[prefix] = [];
			}

			if (typeof postData.SPERM_NAME[prefix] == 'undefined')
			{
				postData.SPERM_NAME[prefix] = [];
			}

			id = (
				typeof selectedRecipients.b_groups[key].ID != 'undefined'
					? selectedRecipients.b_groups[key].ID
					: selectedRecipients.b_groups[key].id
			);

			name = (
				typeof selectedRecipients.b_groups[key].NAME != 'undefined'
					? selectedRecipients.b_groups[key].NAME
					: selectedRecipients.b_groups[key].name
			);

			postData.SPERM[prefix].push('SG' + id);
			postData.SPERM_NAME[prefix].push(name);
		}
	}

	for (var key in hiddenRecipients)
	{
		prefix = hiddenRecipients[key]['TYPE'];
		if (typeof postData.SPERM[prefix] == 'undefined')
		{
			postData.SPERM[prefix] = [];
		}
		postData.SPERM[prefix].push(hiddenRecipients[key]['TYPE'] + hiddenRecipients[key]['ID']);
	}
}

BitrixMSL.prototype.buildSelectedDestinations = function(postData, selectedDestinations)
{
	if (
		typeof (postData.SPERM) == 'undefined'
		|| typeof (postData.SPERM_NAME) == 'undefined'
	)
	{
		return;
	}

	var arMatch = null;

	if (typeof (postData.SPERM.U) != 'undefined')
	{
		for (var key in postData.SPERM.U)
		{
			if (postData.SPERM.U[key] == 'UA')
			{
				this.addPostFormDestination(
					selectedDestinations,
					{
						type: 'UA'
					}
				);
			}
			else
			{
				arMatch = postData.SPERM.U[key].match(/^U([\d]+)$/);
				if (arMatch != null)
				{
					this.addPostFormDestination(
						selectedDestinations,
						{
							type: 'U',
							id: arMatch[1],
							name: postData.SPERM_NAME.U[key]

						}
					);
				}
			}
		}
	}

	if (typeof (postData.SPERM.SG) != 'undefined')
	{
		for (var key in postData.SPERM.SG)
		{
			arMatch = postData.SPERM.SG[key].match(/^SG([\d]+)$/);
			if (arMatch != null)
			{
				this.addPostFormDestination(
					selectedDestinations,
					{
						type: 'SG',
						id: arMatch[1],
						name: postData.SPERM_NAME.SG[key]
					}
				);
			}
		}
	}
}

BitrixMSL.prototype.buildPostFormRequestStub = function(params)
{
	var oRequest = null;

	if (params.type == 'post')
	{
		oRequest = {
			ACTION: 'ADD_POST',
			AJAX_CALL: 'Y',
			PUBLISH_STATUS: 'P',
			is_sent: 'Y',
			apply: 'Y',
			sessid: BX.bitrix_sessid(),
			POST_MESSAGE: params.text,
			decode: 'Y',
			SPERM: {},
			SPERM_NAME: {}
		};

		if (
			typeof params.extraData.postId != 'undefined'
			&& parseInt(params.extraData.postId) > 0
		)
		{
			oRequest.post_id = parseInt(params.extraData.postId);
			oRequest.post_user_id = parseInt(params.extraData.postAuthorId);

			oRequest.ACTION = 'EDIT_POST';

			if (
				typeof params.extraData.logId != 'undefined'
				&& parseInt(params.extraData.logId) > 0
			)
			{
				oRequest.log_id = parseInt(params.extraData.logId);
			}
		}
	}
	else if (
		params.type == 'comment'
		&& typeof params.extraData.commentId != 'undefined'
		&& parseInt(params.extraData.commentId) > 0
		&& typeof params.extraData.commentType != 'undefined'
		&& params.extraData.commentType.length > 0
	)
	{
		oRequest = {
			action: 'EDIT_COMMENT',
			text: oMSL.parseMentions(params.text),
			commentId: parseInt(params.extraData.commentId),
			nodeId: params.extraData.nodeId,
			sessid: BX.bitrix_sessid()
		};

		if (params.extraData.commentType == 'blog')
		{
			oRequest.comment_post_id = commentVarBlogPostID;
		}
		else
		{
		}
	}

	return oRequest;
}

BitrixMSL.prototype.showPostError = function(errorText)
{
	if (BX('blog-post-new-error'))
	{
		BX('blog-post-new-error').style.display = 'block';

		if (BX('blog-post-new-error-text'))
		{
			if (
				typeof (errorText) != 'undefined'
				&& errorText.length > 0
			)
			{
				BX('blog-post-new-error-text').style.display = 'block';
				BX('blog-post-new-error-text').innerHTML = errorText;
			}
			else
			{
				BX('blog-post-new-error-text').style.display = 'none';
			}
		}

		BX.bind(BX('blog-post-new-error'), 'click', __MSLOnErrorClick);
	}
}

BitrixMSL.prototype.clearPostFormDestination = function(selectedDestinations, groupID)
{
	if (
		typeof groupID != 'undefined'
		&& parseInt(groupID) > 0
	)
	{
		oMSL.addPostFormDestination(
			selectedDestinations,
			{
				type: 'SG',
				id:  parseInt(groupID),
				name: BX.message('MSLGroupName')
			}
		);
	}
	else if (window.arAvailableGroup !== false)
	{
		for (key in window.arAvailableGroup)
		{
			oMSL.addPostFormDestination(
				selectedDestinations,
				{
					type: 'SG',
					id:  parseInt(window.arAvailableGroup[key]['entityId']),
					name: window.arAvailableGroup[key]['name']
				}
			);
		}
	}
	else if (BX.message('MSLIsDefaultToAll') == 'Y')
	{
		oMSL.addPostFormDestination(
			selectedDestinations,
			{
				type: 'UA'
			}
		);
	}
}

oMSL = new BitrixMSL;
window.oMSL = oMSL;
