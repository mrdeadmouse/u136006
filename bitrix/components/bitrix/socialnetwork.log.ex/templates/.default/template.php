<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$this->setFrameMode(true);

if ($arResult["NEED_AUTH"] == "Y")
{
	$APPLICATION->AuthForm("");
}
elseif (strlen($arResult["FatalError"])>0)
{
	?><span class='errortext'><?=$arResult["FatalError"]?></span><br /><br /><?
}
else
{
	CUtil::InitJSCore(array("ajax", "window", "tooltip", "popup", "fx", "viewer"));
	$APPLICATION->SetUniqueJS('live_feed_v2'.($arParams["IS_CRM"] != "Y" ? "" : "_crm"));
	$APPLICATION->SetUniqueCSS('live_feed_v2'.($arParams["IS_CRM"] != "Y" ? "" : "_crm"));

	$log_content_id = "sonet_log_content_".RandString(8);
	$event_cnt = 0;

	if (!$arResult["AJAX_CALL"])
	{
		$APPLICATION->AddHeadScript("/bitrix/components/bitrix/socialnetwork.log.entry/templates/.default/scripts.js");

		if ($arParams["IS_CRM"] == "Y")
		{
			$GLOBALS['APPLICATION']->IncludeComponent(
				'bitrix:crm.activity.editor',
				'',
				array(
					'CONTAINER_ID' => '',
					'EDITOR_ID' => 'livefeed',
					'EDITOR_TYPE' => 'MIXED',
					'PREFIX' => 'crm_activity_livefeed',
					'OWNER_TYPE' => '',
					'OWNER_ID' => 0,
					'READ_ONLY' => false,
					'ENABLE_UI' => false,
					'ENABLE_TASK_ADD' => true,
					'ENABLE_CALENDAR_EVENT_ADD' => true,
					'ENABLE_EMAIL_ADD' => true,
					'ENABLE_TOOLBAR' => false,
					'EDITOR_ITEMS' => array()
				),
				null,
				array('HIDE_ICONS' => 'Y')
			);
		}

		$APPLICATION->IncludeComponent("bitrix:main.user.link",
			'',
			array(
				"AJAX_ONLY" => "Y",
				"PATH_TO_SONET_USER_PROFILE" => $arParams["~PATH_TO_USER"],
				"PATH_TO_SONET_MESSAGES_CHAT" => $arParams["~PATH_TO_MESSAGES_CHAT"],
				"DATE_TIME_FORMAT" => $arParams["DATE_TIME_FORMAT"],
				"SHOW_YEAR" => $arParams["SHOW_YEAR"],
				"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
				"SHOW_LOGIN" => $arParams["SHOW_LOGIN"],
				"PATH_TO_CONPANY_DEPARTMENT" => $arParams["~PATH_TO_CONPANY_DEPARTMENT"],
				"PATH_TO_VIDEO_CALL" => $arParams["~PATH_TO_VIDEO_CALL"],
			),
			false,
			array("HIDE_ICONS" => "Y")
		);

		if (IsModuleInstalled('tasks'))
		{
			?><?
			$GLOBALS["APPLICATION"]->IncludeComponent(
				"bitrix:tasks.iframe.popup",
				".default",
				array(
					"ON_TASK_ADDED" => "BX.DoNothing",
					"ON_TASK_CHANGED" => "BX.DoNothing",
					"ON_TASK_DELETED" => "BX.DoNothing",
				),
				null,
				array("HIDE_ICONS" => "Y")
			);
			?><?
		}

		if(
			$arParams["HIDE_EDIT_FORM"] != "Y"
			&& IntVal($arResult["MICROBLOG_USER_ID"]) > 0
			&& $USER->IsAuthorized()
		)
		{
			?><div id="sonet_log_microblog_container"><?
				$arBlogComponentParams = Array(
					"ID" => "new",
					"PATH_TO_BLOG" => $APPLICATION->GetCurPageParam("", array("WFILES")),
					"PATH_TO_POST" => $arParams["PATH_TO_USER_MICROBLOG_POST"],
					"PATH_TO_GROUP_POST" => $arParams["PATH_TO_GROUP_MICROBLOG_POST"],
					"PATH_TO_POST_EDIT" => $arParams["PATH_TO_USER_BLOG_POST_EDIT"],
					"PATH_TO_SMILE" => $arParams["PATH_TO_BLOG_SMILE"],
					"SET_TITLE" => "N",
					"GROUP_ID" => $arParams["BLOG_GROUP_ID"],
					"USER_ID" => $USER->GetID(),
					"SET_NAV_CHAIN" => "N",
					"USE_SOCNET" => "Y",
					"MICROBLOG" => "Y",
					"USE_CUT" => $arParams["BLOG_USE_CUT"],
					"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
					"CHECK_PERMISSIONS_DEST" => $arParams["CHECK_PERMISSIONS_DEST"],
					"TOP_TABS_VISIBLE" => (array_key_exists("TOP_TABS_VISIBLE", $arParams) ? $arParams["TOP_TABS_VISIBLE"] : "Y")
				);

				if ($arParams["ENTITY_TYPE"] == SONET_ENTITY_GROUP)
				{
					$arBlogComponentParams["SOCNET_GROUP_ID"] = $arParams["GROUP_ID"];
				}
				elseif ($arParams["ENTITY_TYPE"] != SONET_ENTITY_GROUP && $USER->GetID() != $arParams["CURRENT_USER_ID"])
				{
					$arBlogComponentParams["SOCNET_USER_ID"] = $arParams["CURRENT_USER_ID"];
				}

				if (isset($arParams["DISPLAY"]))
					$arBlogComponentParams["DISPLAY"] = $arParams["DISPLAY"];

				if (defined("BITRIX24_INDEX_COMPOSITE"))
				{
					$arBlogComponentParams["POST_FORM_ACTION_URI"] = "/";
				}

				?><?
				$staticHtmlCache = \Bitrix\Main\Data\StaticHtmlCache::getInstance();
				$staticHtmlCache->disableVoting();
				$APPLICATION->IncludeComponent(
					"bitrix:socialnetwork.blog.post.edit",
					"",
					$arBlogComponentParams,
					$component,
					array("HIDE_ICONS" => "Y")
				);
				$staticHtmlCache->enableVoting();
			?></div><?
		}
		elseif ($arParams["SHOW_EVENT_ID_FILTER"] == "Y")
		{
			?><div class="feed-filter-fake-cont"></div><?
		}

		if ($arParams["SHOW_EVENT_ID_FILTER"] == "Y")
		{
			if ($arParams["IS_CRM"] == "Y")
			{
				$liveFeedFilter = new CCrmLiveFeedFilter(
					array(
						'GridFormID' => '',
						'EntityTypeID' => false
					)
				);
				AddEventHandler('socialnetwork', 'OnBeforeSonetLogFilterFill', array($liveFeedFilter, 'OnBeforeSonetLogFilterFill'));
			}

			$GLOBALS["APPLICATION"]->IncludeComponent(
				"bitrix:socialnetwork.log.filter",
				(isset($arParams["FILTER_TEMPLATE"]) ? $arParams["FILTER_TEMPLATE"] : ".default"),
				array(
					"arParams" => array_merge(
						$arParams,
						array(
							"TOP_OUT" => "Y",
							"USE_TARGET" => (!isset($arParams["USE_FILTER_TARGET"]) || $arParams["USE_FILTER_TARGET"] != "N" ? "Y" : "N"),
							"TARGET_ID" => (
								isset($_REQUEST["SONET_FILTER_MODE"])
								&& $_REQUEST["SONET_FILTER_MODE"] == "AJAX"
									? ""
									: "sonet_blog_form"
							),
							"USE_SONET_GROUPS" => (!isset($arParams["IS_CRM"]) || $arParams["IS_CRM"] != "Y" ? "Y" : "N"),
							"SHOW_FOLLOW" => (isset($arParams["SHOW_FOLLOW_FILTER"]) && $arParams["SHOW_FOLLOW_FILTER"] == "N" ? "N" : "Y"),
							"USE_SMART_FILTER" => (isset($arResult["USE_SMART_FILTER"]) && $arResult["USE_SMART_FILTER"] == "Y" ? "Y" : "N"),
							"MY_GROUPS_ONLY" => (isset($arResult["MY_GROUPS_ONLY"]) && $arResult["MY_GROUPS_ONLY"] == "Y" ? "Y" : "N")
						)
					),
					"arResult" => $arResult
				),
				null,
				array("HIDE_ICONS" => "Y")
			);

			if (
				isset($_REQUEST["SONET_FILTER_MODE"])
				&& $_REQUEST["SONET_FILTER_MODE"] == "AJAX"
			)
				return;
		}

		if (defined("BITRIX24_INDEX_COMPOSITE"))
		{
			$dynamicArea = new \Bitrix\Main\Page\FrameStatic("live-feed");
			$dynamicArea->startDynamicArea();

			$stub = '
			<div class="bx-placeholder-wrap">
				<div class="bx-placeholder">
					<table class="bx-feed-curtain">
						<tr class="bx-curtain-row-0 1"><td class="bx-curtain-cell-1"></td><td class="bx-curtain-cell-2 transparent"></td><td class="bx-curtain-cell-3"></td><td class="bx-curtain-cell-4"></td><td class="bx-curtain-cell-5"></td><td class="bx-curtain-cell-6"></td><td class="bx-curtain-cell-7"></td></tr><tr class="bx-curtain-row-1 2"><td class="bx-curtain-cell-1"></td><td class="bx-curtain-cell-2 transparent"></td><td class="bx-curtain-cell-3"></td><td class="bx-curtain-cell-4 transparent"></td><td class="bx-curtain-cell-5" colspan="3"></td></tr><tr class="bx-curtain-row-2 3"><td class="bx-curtain-cell-1"></td><td class="bx-curtain-cell-2 transparent"><div class="bx-bx-curtain-avatar"></div></td><td class="bx-curtain-cell-3" colspan="5"></td></tr>
						<tr class="bx-curtain-row-1 4"><td class="bx-curtain-cell-1"></td><td class="bx-curtain-cell-2 transparent"></td><td class="bx-curtain-cell-3"></td><td class="bx-curtain-cell-4 transparent" colspan="3"></td><td class="bx-curtain-cell-7"></td></tr>
						<tr class="bx-curtain-row-2 5"><td class="bx-curtain-cell-1" colspan="7"></td></tr>
						<tr class="bx-curtain-row-1 6"><td class="bx-curtain-cell-1" colspan="3"></td><td class="bx-curtain-cell-4 transparent" colspan="3"></td><td class="bx-curtain-cell-7"></td></tr>
						<tr class="bx-curtain-row-2 7"><td class="bx-curtain-cell-1" colspan="7"></td></tr>
						<tr class="bx-curtain-row-1 8"><td class="bx-curtain-cell-1" colspan="3"></td><td class="bx-curtain-cell-4 transparent" colspan="3"></td><td class="bx-curtain-cell-7"></td></tr>
						<tr class="bx-curtain-row-2 9"><td class="bx-curtain-cell-1" colspan="7"></td></tr>
						<tr class="bx-curtain-row-1 10"><td class="bx-curtain-cell-1" colspan="3"></td><td class="bx-curtain-cell-4 transparent" colspan="2"></td><td class="bx-curtain-cell-6" colspan="2"></td></tr><tr class="bx-curtain-row-last"><td class="bx-curtain-cell-1" colspan="7"></td></tr>
					</table>
				</div>
			</div>';

			$dynamicArea->setStub('<div class="feed-new-message-informer-place"></div>'.$stub.$stub);
		}

		?><div class="feed-new-message-informer-place"><?
		if ($arParams["SHOW_REFRESH"] != "N")
		{
			?><div class="feed-new-message-inform-wrap" id="sonet_log_counter_2_wrap" style="visibility: hidden;"><?
				?><div onclick="__logRefresh('<?=CUtil::JSEscape($GLOBALS["APPLICATION"]->GetCurPageParam("logajax=Y&RELOAD=Y", array(
					"flt_created_by_id",
					"flt_group_id",
					"flt_to_user_id",
					"flt_date_datesel",
					"flt_date_days",
					"flt_date_from",
					"flt_date_to",
					"flt_date_to",
					"preset_filter_id",
					"sessid",
					"bxajaxid",
					"logajax"
				), false))?>')" id="sonet_log_counter_2_container" class="feed-new-message-informer"><?
					?><span class="feed-new-message-inf-text"><?=GetMessage("SONET_C30_COUNTER_TEXT_1")?><?
						?><span class="feed-new-message-informer-counter" id="sonet_log_counter_2"></span><?
						?><span class="feed-new-message-icon"></span><?
					?></span><?
					?><span class="feed-new-message-inf-text feed-new-message-inf-text-waiting" style="display: none;"><?
						?><span class="feed-new-message-wait-icon-small"></span><?
						?><?=GetMessage("SONET_C30_T_MORE_WAIT")?><?
					?></span><?
					?><span class="feed-new-message-inf-text feed-new-message-inf-text-reload" style="display: none;"><?
						?><?=GetMessage("SONET_C30_T_RELOAD_NEEDED")?><?
					?></span><?
				?></div><?
/*
				?><div id="sonet_log_counter_2_error" class="feed-new-message-informer" style="display: none;"><?
					?><span class="feed-new-message-inf-text" style="display: none;"><?
						?><?=GetMessage("SONET_C30_T_RELOAD_ERROR")?><?
					?></span><?
				?></div><?
*/
			?></div><?
		}
		else
		{
			?><div class="feed-new-message-inform-wrap"  id="sonet_log_counter_2_wrap" style="visibility: hidden;"></div><?
		}
		?></div><?

		?><div id="log_internal_container"><?
	}
	elseif (
		!$arResult["Events"]
		|| !is_array($arResult["Events"])
		|| count($arResult["Events"]) <= 0
	)
	{
		ob_end_clean();
		$APPLICATION->RestartBuffer();
		die();
	}
	else // AJAX_CALL
	{
		$arCSSList = $APPLICATION->sPath2css;
		$arHeadScripts = $APPLICATION->arHeadScripts;

		ob_end_clean();
		$APPLICATION->RestartBuffer();
	}

	if (!$arResult["AJAX_CALL"])
	{
		?><div class="feed-wrap"><?
	}

	?><script>
		var logAjaxMode = false;
		var nodeTmp1Cap = false;
		var nodeTmp2Cap = false;
		var next_url = false;

		<?
		if (!$arResult["AJAX_CALL"])
		{
			?>
			BX.message({
				sonetLGetPath: '<?=CUtil::JSEscape('/bitrix/components/bitrix/socialnetwork.log.ex/ajax.php')?>',
				sonetLSetPath: '<?=CUtil::JSEscape('/bitrix/components/bitrix/socialnetwork.log.ex/ajax.php')?>',
				sonetLESetPath: '<?=CUtil::JSEscape('/bitrix/components/bitrix/socialnetwork.log.entry/ajax.php')?>',
				sonetLEPath: '<?=CUtil::JSEscape($arParams["PATH_TO_LOG_ENTRY"])?>',
				sonetLSessid: '<?=bitrix_sessid_get()?>',
				sonetLLangId: '<?=CUtil::JSEscape(LANGUAGE_ID)?>',
				sonetLSiteId: '<?=CUtil::JSEscape(SITE_ID)?>',
				sonetLSiteTemplateId: '<?=CUtil::JSEscape(SITE_TEMPLATE_ID)?>',
				sonetLNoSubscriptions: '<?=GetMessageJS("SONET_C30_NO_SUBSCRIPTIONS")?>',
				sonetLInherited: '<?=GetMessageJS("SONET_C30_INHERITED")?>',
				sonetLDialogClose: '<?=GetMessageJS("SONET_C30_DIALOG_CLOSE_BUTTON")?>',
				sonetLDialogSubmit: '<?=GetMessageJS("SONET_C30_DIALOG_SUBMIT_BUTTON")?>',
				sonetLDialogCancel: '<?=GetMessageJS("SONET_C30_DIALOG_CANCEL_BUTTON")?>',
				sonetLbUseFavorites: '<?=(!isset($arParams["USE_FAVORITES"]) || $arParams["USE_FAVORITES"] != "N" ? "Y" : "N")?>',
				sonetLMenuFavoritesTitleY: '<?=GetMessageJS("SONET_C30_MENU_TITLE_FAVORITES_Y")?>',
				sonetLMenuFavoritesTitleN: '<?=GetMessageJS("SONET_C30_MENU_TITLE_FAVORITES_N")?>',
				sonetLMenuLink: '<?=GetMessageJS("SONET_C30_MENU_TITLE_LINK")?>',
				sonetLMenuHref: '<?=GetMessageJS("SONET_C30_MENU_TITLE_HREF")?>',
				sonetLMenuDelete: '<?=GetMessageJS("SONET_C30_MENU_TITLE_DELETE")?>',
				sonetLMenuDeleteConfirm: '<?=GetMessageJS("SONET_C30_MENU_TITLE_DELETE_CONFIRM")?>',
				sonetLMenuDeleteSuccess: '<?=GetMessageJS("SONET_C30_MENU_TITLE_DELETE_SUCCESS")?>',
				sonetLMenuDeleteFailure: '<?=GetMessageJS("SONET_C30_MENU_TITLE_DELETE_FAILURE")?>',
				sonetLCounterType: '<?=CUtil::JSEscape($arResult["COUNTER_TYPE"])?>',
				sonetLIsB24: '<?=(SITE_TEMPLATE_ID == "bitrix24" ? "Y" : "N")?>',
				sonetRatingType : '<?=CUtil::JSEscape($arParams["RATING_TYPE"])?>',
				sonetLErrorSessid : '<?=GetMessageJS("SONET_ERROR_SESSION")?>',
				sonetLIsCRM : '<?=CUtil::JSEscape($arParams["IS_CRM"])?>',
				sonetLCanDelete : '<?=($arResult["CAN_DELETE"] ? 'Y' : 'N')?>',
				sonetLForumID : <?=intval($arParams["FORUM_ID"])?>
			});
			<?
		}

		if (
			$arResult["AJAX_CALL"] 
			&& $arParams["SHOW_RATING"] == "Y"
		)
		{
			if ($arParams["RATING_TYPE"] == "like")
			{
				?>
				BX.loadCSS('/bitrix/components/bitrix/rating.vote/templates/<?=$arParams["RATING_TYPE"]?>/popup.css');
				<?
			}
			?>
			BX.loadCSS('/bitrix/components/bitrix/rating.vote/templates/<?=$arParams["RATING_TYPE"]?>/style.css');
			<?
		}

		if ($arResult["bReload"])
		{
			?>
			if (typeof __logOnReload === 'function')
			{
				BX.ready(function(){
					window.bRefreshed = true;
					__logOnReload(<?=intval($arResult["LOG_COUNTER"])?>);
				});
			}
			<?
		}
		elseif (
			$arParams["IS_CRM"] == "Y"
			&& !$arResult["AJAX_CALL"]
		)
		{
			?>
			if (typeof __logOnReload === 'function')
			{
				BX.ready(function(){
					__logOnReload(<?=intval($arResult["LOG_COUNTER"])?>);
				});
			}
			<?
		}

		if (
			!$arResult["AJAX_CALL"]
			|| $arResult["bReload"]
		)
		{
			?>
			BX.ready(function(){
				<?
				if ($arParams["SET_LOG_COUNTER"] != "N")
				{
					?>
					BX.onCustomEvent(window, 'onSonetLogCounterClear', [BX.message('sonetLCounterType')]);
					<?
					if (!$arResult["AJAX_CALL"])
					{
						?>
						BX.addCustomEvent("onGoUp", function() {
							var counter_wrap = BX('sonet_log_counter_2_wrap');
							if (counter_wrap)
							{
								BX.removeClass(counter_wrap, 'feed-new-message-informer-fixed');
								BX.removeClass(counter_wrap, 'feed-new-message-informer-fix-anim');
							}
						});

						BX.addCustomEvent("onPullEvent-main", BX.delegate(function(command,params){
							if (
								command == 'user_counter'
								&& params[BX.message('SITE_ID')]
								&& params[BX.message('SITE_ID')][BX.message('sonetLCounterType')]
							)
							{
								__logChangeCounter(BX.clone(params[BX.message('SITE_ID')][BX.message('sonetLCounterType')]));
							}
						}, this));

						BX.addCustomEvent(window, "onImUpdateCounter", BX.proxy(function(arCount) {
							__logChangeCounterArray(arCount);
						}, this));

						BX.addCustomEvent("onCounterDecrement", function(iDecrement) {
							__logDecrementCounter(iDecrement);
						});
						<?
					}
				}

				if (!$arResult["AJAX_CALL"])
				{
					?>
					BX.addCustomEvent('onAjaxFailure', function(status){
						if (status == 'auth')
						{
							top.location = top.location.href;
						}
					});
					<?
				}
				?>
			});

			<?
			if (!$arResult["AJAX_CALL"])
			{
				if(\Bitrix\Main\Page\Frame::isAjaxRequest())
				{
					?>setTimeout(function() { __logRecalcMoreButton(); }, 1000);<?
				}
				else
				{
					?>BX.bind(window, 'load', function() { __logRecalcMoreButton(); });<?
				}
			}
		}

		if (
			!$arResult["AJAX_CALL"]
			&& !$arResult["bReload"]
		)
		{
			?>
			BX.ready(function() {
				window.addEventListener("scroll", BX.LazyLoad.onScroll);
			});
			<?
		}
		?>

		if (typeof arMoreButtonID == 'undefined')
		{
			var arMoreButtonID = false;
		}
		arMoreButtonID = [];

		BX.ready(function()
		{
			BX.addCustomEvent(window, "onAjaxInsertToNode", function() { BX.ajax.Setup({denyShowWait: true}, true); });
			BX.bind(BX('sonet_log_counter_2_container'), 'click', sonetLClearContainerExternalNew);
			BX.bind(BX('sonet_log_counter_2_container'), 'click', __logOnAjaxInsertToNode);

			if (BX('sonet_log_more_container'))
			{
				BX.bind(BX('sonet_log_more_container'), 'click', sonetLClearContainerExternalMore);
				BX.bind(BX('sonet_log_more_container'), 'click', __logOnAjaxInsertToNode);
			}

			if (BX('sonet_log_comment_text'))
			{
				BX('sonet_log_comment_text').onkeydown = BX.eventCancelBubble;
			}

			setTimeout(function() {
				BX.LazyLoad.showImages(true);
			}, 0);
		});

		BX.addCustomEvent("onFrameDataProcessed", function() {
			BX.LazyLoad.showImages(true);
		});

	</script>
	<?
	if(strlen($arResult["ErrorMessage"]) > 0)
	{
		?><span class='errortext'><?=$arResult["ErrorMessage"]?></span><br /><br /><?
	}

	if (
		$arResult["Events"]
		&& is_array($arResult["Events"])
		&& count($arResult["Events"]) > 0
	)
	{
		?><div id="sonet_log_items"><?
		foreach ($arResult["Events"] as $arEvent)
		{
			if (!empty($arEvent))
			{
				$event_cnt++;
				$ind = RandString(8);
				$event_date_log_ts = (isset($arEvent["LOG_DATE_TS"]) ? $arEvent["LOG_DATE_TS"] : (MakeTimeStamp($arEvent["LOG_DATE"]) - intval($arResult["TZ_OFFSET"])));

				$is_unread = (
					$arParams["SHOW_UNREAD"] == "Y"
					&& ($arResult["COUNTER_TYPE"] == "**" || $arResult["COUNTER_TYPE"] == "CRM_**" || $arResult["COUNTER_TYPE"] == "blog_post")
					&& $arEvent["USER_ID"] != $GLOBALS["USER"]->GetID()
					&& intval($arResult["LAST_LOG_TS"]) > 0
					&& $event_date_log_ts > $arResult["LAST_LOG_TS"]
				);

				if(in_array($arEvent["EVENT_ID"], Array("blog_post", "blog_post_important", "blog_post_micro", "blog_comment", "blog_comment_micro")))
				{
					?><div><?
					$arAditMenu = array();

					$arComponentParams = Array(
						"PATH_TO_BLOG" => $arParams["PATH_TO_USER_BLOG"],
						"PATH_TO_POST" => $arParams["PATH_TO_USER_MICROBLOG_POST"],
						"PATH_TO_POST_IMPORTANT" => $arParams["PATH_TO_USER_BLOG_POST_IMPORTANT"],
						"PATH_TO_BLOG_CATEGORY" => $arParams["PATH_TO_USER_BLOG_CATEGORY"],
						"PATH_TO_POST_EDIT" => $arParams["PATH_TO_USER_BLOG_POST_EDIT"],
						"PATH_TO_SEARCH_TAG" => $arParams["PATH_TO_SEARCH_TAG"],
						"PATH_TO_USER" => $arParams["PATH_TO_USER"],
						"PATH_TO_GROUP" => $arParams["PATH_TO_GROUP"],
						"PATH_TO_SMILE" => $arParams["PATH_TO_BLOG_SMILE"],
						"PATH_TO_MESSAGES_CHAT" => $arParams["PATH_TO_MESSAGES_CHAT"],
						"SET_NAV_CHAIN" => "N",
						"SET_TITLE" => "N",
						"POST_PROPERTY" => $arParams["POST_PROPERTY"],
						"DATE_TIME_FORMAT" => $arParams["DATE_TIME_FORMAT"],
						"CREATED_BY_ID" => (
							array_key_exists("log_filter_submit", $_REQUEST)
							&& array_key_exists("flt_comments", $_REQUEST)
							&& $_REQUEST["flt_comments"] == "Y"
								? $arParams["CREATED_BY_ID"]
								: false
						),
						"USER_ID" => $arEvent["USER_ID"],
						"ENTITY_TYPE" => SONET_ENTITY_USER,
						"ENTITY_ID" => $arEvent["ENTITY_ID"],
						"EVENT_ID" => $arEvent["EVENT_ID"],
						"EVENT_ID_FULLSET" => $arEvent["EVENT_ID_FULLSET"],
						"IND" => $ind,
						"GROUP_ID" => $arParams["BLOG_GROUP_ID"],
						"SONET_GROUP_ID" => $arParams["GROUP_ID"],
						"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
						"SHOW_LOGIN" => $arParams["SHOW_LOGIN"],
						"SHOW_YEAR" => $arParams["SHOW_YEAR"],
						"PATH_TO_CONPANY_DEPARTMENT" => $arParams["PATH_TO_CONPANY_DEPARTMENT"],
						"PATH_TO_VIDEO_CALL" => $arParams["PATH_TO_VIDEO_CALL"],
						"USE_SHARE" => $arParams["USE_SHARE"],
						"SHARE_HIDE" => $arParams["SHARE_HIDE"],
						"SHARE_TEMPLATE" => $arParams["SHARE_TEMPLATE"],
						"SHARE_HANDLERS" => $arParams["SHARE_HANDLERS"],
						"SHARE_SHORTEN_URL_LOGIN" => $arParams["SHARE_SHORTEN_URL_LOGIN"],
						"SHARE_SHORTEN_URL_KEY" => $arParams["SHARE_SHORTEN_URL_KEY"],
						"SHOW_RATING" => $arParams["SHOW_RATING"],
						"RATING_TYPE" => $arParams["RATING_TYPE"],
						"IMAGE_MAX_WIDTH" => $arParams["BLOG_IMAGE_MAX_WIDTH"],
						"IMAGE_MAX_HEIGHT" => $arParams["BLOG_IMAGE_MAX_HEIGHT"],
						"ALLOW_POST_CODE" => $arParams["ALLOW_POST_CODE"],
						"ID" => $arEvent["SOURCE_ID"],
						"LOG_ID" => $arEvent["ID"],
						"FROM_LOG" => "Y",
						"ADIT_MENU" => $arAditMenu,
						"IS_UNREAD" => $is_unread,
						"MARK_NEW_COMMENTS" => (
							$GLOBALS["USER"]->IsAuthorized()
							&& $arResult["COUNTER_TYPE"] == "**"
							&& $arParams["SHOW_UNREAD"] == "Y"
							)
								? "Y"
								: "N",
						"IS_HIDDEN" => false,
						"LAST_LOG_TS" => ($arResult["LAST_LOG_TS"] + $arResult["TZ_OFFSET"]),
						"CACHE_TIME" => $arParams["CACHE_TIME"],
						"CACHE_TYPE" => $arParams["CACHE_TYPE"],
						"ALLOW_VIDEO" => $arParams["BLOG_COMMENT_ALLOW_VIDEO"],
						"ALLOW_IMAGE_UPLOAD" => $arParams["BLOG_COMMENT_ALLOW_IMAGE_UPLOAD"],
						"USE_CUT" => $arParams["BLOG_USE_CUT"],
						"AVATAR_SIZE_COMMON" => $arParams["AVATAR_SIZE_COMMON"],
						"AVATAR_SIZE" => $arParams["AVATAR_SIZE"],
						"AVATAR_SIZE_COMMENT" => $arParams["AVATAR_SIZE_COMMENT"],
						"LAZYLOAD" => "Y"
					);

					if ($arParams["USE_FOLLOW"] == "Y")
					{
						$arComponentParams["FOLLOW"] = $arEvent["FOLLOW"];
					}

					if ($arResult["CURRENT_PAGE_DATE"])
					{
						$arComponentParams["CURRENT_PAGE_DATE"] = $arResult["CURRENT_PAGE_DATE"];
					}

					if (
						(
							!isset($arParams["USE_FAVORITES"])
							|| $arParams["USE_FAVORITES"] != "N"
						)
						&& $GLOBALS["USER"]->IsAuthorized()
					)
					{
						$arComponentParams["FAVORITES_USER_ID"] = (array_key_exists("FAVORITES_USER_ID", $arEvent) && intval($arEvent["FAVORITES_USER_ID"]) > 0 ? intval($arEvent["FAVORITES_USER_ID"]) : 0);
					}

					$APPLICATION->IncludeComponent(
						"bitrix:socialnetwork.blog.post",
						"",
						$arComponentParams,
						$component
					);
					?></div><?
				}
				else
				{
					$arComponentParams = array_merge($arParams, array(
							"COMMENT_ID" => intval($_REQUEST["commentId"]),
							"LOG_ID" => $arEvent["ID"],
							"LAST_LOG_TS" => $arResult["LAST_LOG_TS"],
							"COUNTER_TYPE" => $arResult["COUNTER_TYPE"],
							"AJAX_CALL" => $arResult["AJAX_CALL"],
							"bReload" => $arResult["bReload"],
							"bGetComments" => $arResult["bGetComments"],
							"IND" => $ind,
							"CURRENT_PAGE_DATE" => $arResult["CURRENT_PAGE_DATE"],
							"EVENT" => array(
								"IS_UNREAD" => $is_unread
							),
							"LAZYLOAD" => "Y"
						)
					);

					if ($GLOBALS["USER"]->IsAuthorized())
					{
						if ($arParams["USE_FOLLOW"] == "Y")
						{
							$arComponentParams["EVENT"]["FOLLOW"] = $arEvent["FOLLOW"];
							$arComponentParams["EVENT"]["DATE_FOLLOW"] = $arEvent["DATE_FOLLOW"];
						}

						if (
							!isset($arParams["USE_FAVORITES"])
							|| $arParams["USE_FAVORITES"] != "N"
						)
						{
							$arComponentParams["EVENT"]["FAVORITES"] = (
								array_key_exists("FAVORITES_USER_ID", $arEvent)
								&& intval($arEvent["FAVORITES_USER_ID"]) > 0
									? "Y"
									: "N"
							);
						}
					}

					if ($arResult["CURRENT_PAGE_DATE"])
						$arComponentParams["CURRENT_PAGE_DATE"] = $arResult["CURRENT_PAGE_DATE"];

					$APPLICATION->IncludeComponent(
						"bitrix:socialnetwork.log.entry",
						"",
						$arComponentParams,
						$component
					);
				}
			}
		}
		?></div><?
	}

	if (
		$arParams["SHOW_NAV_STRING"] != "N"
		&& is_array($arResult["Events"])
	)
	{
		$strParams = "logajax=Y&PAGEN_".$arResult["PAGE_NAVNUM"]."=".($arResult["PAGE_NUMBER"] + 1);
		if (!$arResult["AJAX_CALL"])
		{
			$strParams .= "&ts=".$arResult["LAST_LOG_TS"];
		}

		if (
			is_array($arResult["arLogTmpID"])
			&& count($arResult["arLogTmpID"]) > 0
		)
		{
			$strParams .= "&pplogid=".implode("|", $arResult["arLogTmpID"]);
		}

		$next_url = CUtil::JSEscape($APPLICATION->GetCurPageParam($strParams, array("PAGEN_".$arResult["PAGE_NAVNUM"], "RELOAD", "logajax", "pplogid")));

		?><a href="<?=$next_url?>"></a><?

		if ($arResult["PAGE_NUMBER"] == 1)
		{
			?>
			<script>
				next_url = '<?=$next_url?>';
				<?
				if (!$arResult["AJAX_CALL"] || $arResult["bReload"])
				{
					?>
					__logScrollInit(true);
					<?
				}
				?>
			</script>
			<?
		}
		else
		{
			?><div class="feed-new-message-inf-wrap feed-new-message-active" id="feed-new-message-inf-wrap"><?
			?><a onclick="__logGetNextPage('<?=$next_url?>', false, this)" href="javascript:void(0);" id="sonet_log_more_container" class="feed-new-message-inf-bottom"><?
				?><span class="feed-new-message-inf-text"><?=GetMessage("SONET_C30_MORE")?><?
					?><span class="feed-new-message-icon"></span><?
				?></span><?
				?><span class="feed-new-message-inf-text feed-new-message-inf-text-waiting" style="display: none;"><?
					?><?=GetMessage("SONET_C30_T_MORE_WAIT")?><?
				?></span><?
			?></a><?
			?><div class="feed-new-message-circle"></div><?
			?></div><?
		}
	}

	if (!$arResult["AJAX_CALL"])
	{
		?></div><? // feed-wrap
		?></div><? // log_internal_container
		if (
			$arParams["SHOW_NAV_STRING"] != "N"
			&& is_array($arResult["Events"])
		)
		{
			?><div class="feed-new-message-inf-wrap feed-new-message-inf-wrap-first feed-new-message-active" id="feed-new-message-inf-wrap-first" style="display: none;"><?
				?><a href="javascript:void(0);" id="sonet_log_more_container_first" class="feed-new-message-inf-bottom"><?
					?><span class="feed-new-message-inf-text"><?
						?><?=GetMessage("SONET_C30_MORE")?><?
						?><span class="feed-new-message-icon"></span><?
					?></span><?
					?><span class="feed-new-message-inf-text feed-new-message-inf-text-waiting" style="display: none;"><?
						?><?=GetMessage("SONET_C30_T_MORE_WAIT")?><?
					?></span><?
				?></a><?
			?></div><?
		}
	}
	else
	{
		$arCSSListNew = $APPLICATION->sPath2css;
		$cnt_old = count($arCSSList);
		$cnt_new = count($arCSSListNew);

		$arCSSNew = array();

		if ($cnt_old != $cnt_new)
			for ($i = $cnt_old; $i < $cnt_new; $i++)
			{
				$css_path = $arCSSListNew[$i];
				if(strtolower(substr($css_path, 0, 7)) != 'http://' && strtolower(substr($css_path, 0, 8)) != 'https://')
				{
					if(($p = strpos($css_path, "?"))>0)
						$css_file = substr($css_path, 0, $p);
					else
						$css_file = $css_path;

					if(file_exists($_SERVER["DOCUMENT_ROOT"].$css_file))
						$arCSSNew[] = $arCSSListNew[$i];
				}
				else
					$arCSSNew[] = $arCSSListNew[$i];
			}

		$arCSSNew = array_unique($arCSSNew);

		$arHeadScriptsListNew = $APPLICATION->arHeadScripts;

		$cnt_old = count($arHeadScripts);
		$cnt_new = count($arHeadScriptsListNew);
		$arHeadScriptsNew = array();

		if ($cnt_old != $cnt_new)
		{
			for ($i = $cnt_old; $i < $cnt_new; $i++)
			{
				$arHeadScriptsNew[] = $arHeadScriptsListNew[$i];
			}
		}

		if(!$APPLICATION->oAsset->optimizeJs())
		{
			$arHeadScriptsNew = array_merge(CJSCore::GetScriptsList(), $arHeadScriptsNew);
		}

		$arAdditionalData["CSS"] = array();
		foreach($arCSSNew as $style)
			$arAdditionalData["CSS"][] = CUtil::GetAdditionalFileURL($style);

		$arAdditionalData['SCRIPTS'] = array();
		$arHeadScriptsNew = array_unique($arHeadScriptsNew);

		foreach($arHeadScriptsNew as $script)
			$arAdditionalData["SCRIPTS"][] = CUtil::GetAdditionalFileURL($script);

		$additional_data = '<script type="text/javascript" bxrunfirst="true">'."\n";
		$additional_data .= 'var arAjaxPageData = '.CUtil::PhpToJSObject($arAdditionalData).";\r\n";
		$additional_data .= 'top.BX.ajax.UpdatePageData(arAjaxPageData)'.";\r\n";
		$additional_data .= 'top.__logGetNextPageLinkEntities('.
			CUtil::PhpToJSObject($component->arResult["ENTITIES_XML_ID"]).', '.
			CUtil::PhpToJSObject($component->arResult["ENTITIES_CORRESPONDENCE"]).');';
		$additional_data .= '</script>';

		echo $additional_data;

		if ($arResult["bReload"])
		{
			$strText = ob_get_clean();
			echo CUtil::PhpToJSObject(array(
				"TEXT" => $strText
			));
		}

		if (CModule::IncludeModule("pull"))
		{
			CPullWatch::DeferredSql();
		}

		die();
	}

	CUtil::InitJSCore(array("ajax"));
	$arParams["UID"] = randString(4);
	$arParams["FORM_ID"] = "sonetCommentForm".$arParams["UID"];
	$arParams["ALLOW_VIDEO"] = ($arParams["ALLOW_VIDEO"] == "Y" ? "Y" : "N");

	$arSmiles = array();
	if(!empty($arResult["Smiles"]))
	{
		foreach($arResult["Smiles"] as $arSmile)
		{
			$arSmiles[] = array(
				'name' => $arSmile["NAME"],
				'path' => $arSmile["IMAGE"],
				'code' => str_replace("\\\\","\\",$arSmile["TYPE"]),
				'codes' => str_replace("\\\\","\\",$arSmile["TYPING"]),
				'width' => $arSmile["IMAGE_WIDTH"],
				'height' => $arSmile["IMAGE_HEIGHT"],
			);
		}
	}

	$formParams = array(
		"FORM_ID" => $arParams["FORM_ID"],
		"SHOW_MORE" => "Y",
		"PARSER" => Array(
			"Bold", "Italic", "Underline", "Strike", "ForeColor",
			"FontList", "FontSizeList", "RemoveFormat", "Quote",
			"Code", "CreateLink",
			"Image", "UploadFile",
			"InputVideo",
			"Table", "Justify", "InsertOrderedList",
			"InsertUnorderedList",
			"Source", "MentionUser", "Spoiler"),
		"BUTTONS" => Array(
			(
				(
					in_array("UF_SONET_COM_FILE", $arParams["COMMENT_PROPERTY"])
					|| in_array("UF_SONET_COM_DOC", $arParams["COMMENT_PROPERTY"])
				)
					? "UploadFile"
					: ""
			),
			"CreateLink",
			"InputVideo",
			"Quote", "MentionUser"
		),
		"TEXT" => Array(
			"NAME" => "comment",
			"VALUE" => "",
			"HEIGHT" => "80px"
		),
		"UPLOAD_FILE" => (
			is_array($arResult["COMMENT_PROPERTIES"]["DATA"]) && array_key_exists("UF_SONET_COM_DOC", $arResult["COMMENT_PROPERTIES"]["DATA"])
			? false 
			: (
				is_array($arResult["COMMENT_PROPERTIES"]["DATA"])
					? $arResult["COMMENT_PROPERTIES"]["DATA"]["UF_SONET_COM_FILE"]
					: false
			)
		),
		"UPLOAD_WEBDAV_ELEMENT" => $arResult["COMMENT_PROPERTIES"]["DATA"]["UF_SONET_COM_DOC"],
		"UPLOAD_FILE_PARAMS" => array("width" => 400, "height" => 400),
		"FILES" => Array(
			"VALUE" => array(),
			"DEL_LINK" => $arResult["urlToDelImage"],
			"SHOW" => "N"
		),
		"SMILES" => Array("VALUE" => $arSmiles),
		"LHE" => array(
			"id" => "id".$arParams["FORM_ID"],
			"documentCSS" => "body {color:#434343;}",
			"ctrlEnterHandler" => "__logSubmitCommentForm".$arParams["UID"],
			"fontFamily" => "'Helvetica Neue', Helvetica, Arial, sans-serif",
			"fontSize" => "12px",
			"bInitByJS" => true,
			"height" => 80
		)
	);

	?><div style="display: none;">
		<form action="" id="<?=$arParams["FORM_ID"]?>" name="<?=$arParams["FORM_ID"]?>" <?
			?>method="POST" enctype="multipart/form-data" target="_self" class="comments-form">
			<?=bitrix_sessid_post();?>
			<input type="hidden" name="sonet_log_comment_logid" id="sonet_log_comment_logid" value="">
			<?$APPLICATION->IncludeComponent(
				"bitrix:main.post.form",
				".default",
				$formParams,
				false,
				array(
					"HIDE_ICONS" => "Y"
				)
			);?>
			<input type="hidden" name="cuid" id="upload-cid" value="" />
		</form>
	</div>
	<script type="text/javascript">
	BX.ready(function(){
		window["__logSubmitCommentForm<?=$arParams["UID"]?>"] = function ()
		{
			if (!!window["UC"]["f<?=$arParams["FORM_ID"]?>"] && !!window["UC"]["f<?=$arParams["FORM_ID"]?>"].eventNode)
			{
				BX.onCustomEvent(window["UC"]["f<?=$arParams["FORM_ID"]?>"].eventNode, 'OnButtonClick', ['submit']);
			}
			return false;
		}

		if (!!window["FCForm"])
		{
			window["UC"]["f<?=$arParams["FORM_ID"]?>"] = new FCForm({
				entitiesId : <?=CUtil::PhpToJSObject($component->arResult["ENTITIES_XML_ID"])?>,
				formId : '<?=$arParams["FORM_ID"]?>',
				editorId : 'id<?=$arParams["FORM_ID"]?>'});

			window["UC"]["f<?=$arParams["FORM_ID"]?>"]["entitiesCorrespondence"] = <?=CUtil::PhpToJSObject($component->arResult["ENTITIES_CORRESPONDENCE"])?>;

			window.__logGetNextPageFormName = "f<?=$arParams["FORM_ID"]?>";

			if (!!window["UC"]["f<?=$arParams["FORM_ID"]?>"].eventNode)
			{
				BX.addCustomEvent(window["UC"]["f<?=$arParams["FORM_ID"]?>"].eventNode, 'OnUCFormClear', __socOnUCFormClear);
				BX.addCustomEvent(window["UC"]["f<?=$arParams["FORM_ID"]?>"].eventNode, 'OnUCFormAfterShow', __socOnUCFormAfterShow);
				BX.addCustomEvent(window["UC"]["f<?=$arParams["FORM_ID"]?>"].eventNode, 'OnUCFormSubmit', __socOnUCFormSubmit);
				BX.addCustomEvent(window["UC"]["f<?=$arParams["FORM_ID"]?>"].eventNode, 'OnUCFormResponse', __socOnUCFormResponse);
				BX.addCustomEvent(window["UC"]["f<?=$arParams["FORM_ID"]?>"].eventNode, 'OnUCFormInit', function(obj){BX.remove(BX('micro' + obj.editorName));});
			}

			BX.addCustomEvent(window, "OnBeforeSocialnetworkCommentShowedUp", function(entity) {
				if (entity != 'socialnetwork')
				{
					window["UC"]["f<?=$arParams["FORM_ID"]?>"].hide(true);
				}
			});
/*
			BX.addCustomEvent(window, 'OnUCRecordHaveDrawn', function(entityId, data, params){
				if (!!window["UC"]["f<?=$arParams["FORM_ID"]?>"].entitiesId[entityId])
				{
					if (BX('record-' + data["messageFields"]["FULL_ID"].join('-') + '-actions'))
						BX.remove(BX('record-' + data["messageFields"]["FULL_ID"].join('-') + '-actions'));
				}
			});
*/
			BX.addCustomEvent(window, 'OnUCAddEntitiesCorrespondence', function(key, arValue)
			{
				window["UC"]["f<?=$arParams["FORM_ID"]?>"]["entitiesCorrespondence"][key] = arValue;
			});

			BX.addCustomEvent(window, 'OnUCAfterRecordAdd', function(id, data, responce_data)
			{
				if (typeof responce_data.arComment != 'undefined')
				{
					window["UC"]["f<?=$arParams["FORM_ID"]?>"]["entitiesCorrespondence"][id + '-' + data.messageId[1]] = [responce_data.arComment.LOG_ID, responce_data.arComment.ID];
				}
			});

			BX.addCustomEvent(window, 'OnUCBeforeCommentWillBePulled', function(arId, data)
			{
				if (typeof data.SONET_FULL_ID != 'undefined')
				{
					window["UC"]["f<?=$arParams["FORM_ID"]?>"]["entitiesCorrespondence"][arId.join('-')] = [data.SONET_FULL_ID[0], data.SONET_FULL_ID[1]];
				}
			});
			
			BX.addCustomEvent(window, 'OnUCFeedChanged', function(data)
			{
				BX.LazyLoad.showImages(true);			
			});

			window["SLEC"] = {
				form : BX('<?=$formParams["FORM_ID"]?>'),
				actionUrl : '/bitrix/urlrewrite.php?SEF_APPLICATION_CUR_PAGE_URL=<?=str_replace("%23", "#", urlencode($arResult["urlToPost"]))?>',
				editorId : '<?=$formParams["LHE"]["id"]?>',
				jsMPFName : 'PlEditor<?=$formParams["FORM_ID"]?>'
			};
		}
	});
	</script>
	<?
	// sonet_log_content
}

if (defined("BITRIX24_INDEX_COMPOSITE"))
{
	$dynamicArea->finishDynamicArea();
}
?>