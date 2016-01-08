<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$APPLICATION->AddHeadScript("/bitrix/components/bitrix/mobile.socialnetwork.log.ex/templates/.default/script_attached.js");
$APPLICATION->AddHeadScript("/bitrix/components/bitrix/rating.vote/templates/mobile_like/script_attached.js");
$APPLICATION->AddHeadScript("/bitrix/components/bitrix/rating.vote/templates/mobile_comment_like/script_attached.js");
$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH."/log_mobile.js");
$APPLICATION->SetUniqueJS('live_feed_mobile');
$APPLICATION->SetUniqueCSS('live_feed_mobile');
CUtil::InitJSCore(array('date', 'frame_cache', 'ls', 'fx'));

if (strlen($arResult["FatalError"])>0)
{
	?><span class='errortext'><?=$arResult["FatalError"]?></span><br /><br /><?
}
else
{
	?>
	<script>
		var bGlobalReload = <?=($arResult["RELOAD"] ? "true" : "false")?>;
	</script>
	<?
	if (
		$arParams["EMPTY_PAGE"] != "Y"
		&& !$arResult["AJAX_CALL"]
		&& !$arResult["RELOAD"]
		&& intval($arParams["GROUP_ID"]) <= 0
		&& intval($arParams["LOG_ID"]) <= 0
		&& $_REQUEST["empty_get_comments"] != "Y"
	)
	{
		?>
		<script>
			__MSLOnFeedPreInit({
				arAvailableGroup: <?=CUtil::PhpToJSObject(
					!empty($arResult["arAvailableGroup"])
					&& is_array($arResult["arAvailableGroup"])
						? $arResult["arAvailableGroup"]
						: false
				)?>
			});
			BX.message({
				MSLPullDownText1: '<?=CUtil::JSEscape(GetMessage("MOBILE_LOG_NEW_PULL"))?>',
				MSLPullDownText2: '<?=CUtil::JSEscape(GetMessage("MOBILE_LOG_NEW_PULL_RELEASE"))?>',
				MSLPullDownText3: '<?=CUtil::JSEscape(GetMessage("MOBILE_LOG_NEW_PULL_LOADING"))?>'
			});
		</script>
		<?
		if (!$arParams["FILTER"])
		{
			$frame = \Bitrix\Main\Page\Frame::getInstance();
			$frame->setEnable();
			$frame->setUseAppCache();

			\Bitrix\Main\Data\AppCacheManifest::getInstance()->addAdditionalParam("page", "livefeed");
			\Bitrix\Main\Data\AppCacheManifest::getInstance()->addAdditionalParam("MobileAPIVersion", CMobile::getApiVersion());
			\Bitrix\Main\Data\AppCacheManifest::getInstance()->addAdditionalParam("MobilePlatform", CMobile::getPlatform());
			\Bitrix\Main\Data\AppCacheManifest::getInstance()->addAdditionalParam("UserID", $GLOBALS["USER"]->GetID());
			\Bitrix\Main\Data\AppCacheManifest::getInstance()->addAdditionalParam("version", "v5");

			?><div id="framecache-block-feed"><?
			$feedFrame = $this->createFrame("framecache-block-feed", false)->begin("");
			$feedFrame->setBrowserStorage(true);
		}
	}

	$event_cnt = 0;
	
	if ($arResult["RELOAD"])
	{
		$GLOBALS["APPLICATION"]->ShowAjaxHead(true, false);
		?><script>
			var bGlobalReload = true;
		</script>
		<div id="bxdynamic_feed_refresh"><?
	}

	?>
	<script>
		BX.message({
			MSLLikeUsers1: '<?=GetMessageJS("MOBILE_LOG_LIKE_USERS_1")?>',
			MSLLikeUsers2: '<?=GetMessageJS("MOBILE_LOG_LIKE_USERS_2")?>',
			MSLLike: '<?=GetMessageJS("MOBILE_LOG_LIKE")?>',
			MSLLike2: '<?=GetMessageJS("MOBILE_LOG_LIKE2")?>',
			MSLPostFormTableOk: '<?=GetMessageJS("MOBILE_LOG_POST_FORM_TABLE_OK")?>',
			MSLPostFormTableCancel: '<?=GetMessageJS("MOBILE_LOG_POST_FORM_TABLE_CANCEL")?>',
			MSLPostFormSend: '<?=GetMessageJS("MOBILE_LOG_POST_FORM_SEND")?>',
			MSLPostDestUA: '<?=GetMessageJS("MOBILE_LOG_POST_FORM_DEST_UA")?>',
			MSLGroupName: '<?=(!empty($arResult["GROUP_NAME"]) ? CUtil::JSEscape($arResult["GROUP_NAME"]) : '')?>',
			MSLIsDenyToAll: '<?=($arResult["bDenyToAll"] ? 'Y' : 'N')?>',
			MSLIsDefaultToAll: '<?=($arResult["bDefaultToAll"] ? 'Y' : 'N')?>',
			MSLPostFormPhotoCamera: '<?=GetMessageJS("MOBILE_LOG_POST_FORM_PHOTO_CAMERA")?>',
			MSLPostFormPhotoGallery: '<?=GetMessageJS("MOBILE_LOG_POST_FORM_PHOTO_GALLERY")?>',
			MSLPostFormUFCode: '<?= CUtil::JSEscape($arResult["postFormUFCode"])?>',
			MSLIsExtranetSite: '<?=($arResult["bExtranetSite"] ? 'Y' : 'N')?>'
			<?
			if (
				$arResult["bDiskInstalled"]
				|| $arResult["bWebDavInstalled"]
			)
			{
				if ($arResult["bDiskInstalled"])
				{
					?>
					, MSLbDiskInstalled: 'Y'
					<?
				}
				else
				{
					?>
					, MSLbWebDavInstalled: 'Y'
					<?
				}
				?>
				, MSLPostFormDisk: '<?=GetMessageJS("MOBILE_LOG_POST_FORM_DISK")?>'
				, MSLPostFormDiskTitle: '<?=GetMessageJS("MOBILE_LOG_POST_FORM_DISK_TITLE")?>'
				<?
			}
			?>
		});

		var initParams = {
			logID: <?=$arParams["LOG_ID"]?>,
			bAjaxCall: <?=($arResult["AJAX_CALL"] ? "true" : "false")?>,
			bReload: bGlobalReload,
			bEmptyPage: <?=($arParams["EMPTY_PAGE"] == "Y" ? "true" : "false")?>,
			bFiltered: <?=($arParams["FILTER"] ? "true" : "false")?>,
			bEmptyGetComments: <?=($_REQUEST["empty_get_comments"] == "Y" ? "true" : "false")?>,
			groupID: <?=$arParams["GROUP_ID"]?>,
			curUrl: '<?=$APPLICATION->GetCurPageParam("", array("LAST_LOG_TS", "AJAX_CALL"))?>',
			tmstmp: <?=time()?>,
			strCounterType: '<?=$arResult["COUNTER_TYPE"]?>',
			bFollowDefault: <?=($arResult["FOLLOW_DEFAULT"] != "N" ? "true" : "false")?>
		}

		__MSLOnFeedInit(initParams);

	</script>
	<?
	if (
		$arParams["LOG_ID"] <= 0
		&& $arParams["EMPTY_PAGE"] != "Y"
		&& !$arResult["AJAX_CALL"]
	)
	{
		if (
			isset($arResult["GROUP_NAME"]) 
			&& strlen($arResult["GROUP_NAME"]) > 0
		)
		{
			$pageTitle = CUtil::JSEscape($arResult["GROUP_NAME"]);
		}
		elseif ($arParams["FILTER"] == "favorites")
		{
			$pageTitle = GetMessageJS("MOBILE_LOG_FAVORITES");
		}
		elseif ($arParams["FILTER"] == "my")
		{
			$pageTitle = GetMessageJS("MOBILE_LOG_MY");
		}
		elseif ($arParams["FILTER"] == "important")
		{
			$pageTitle = GetMessageJS("MOBILE_LOG_IMPORTANT");
		}
		elseif ($arParams["FILTER"] == "work")
		{
			$pageTitle = GetMessageJS("MOBILE_LOG_WORK");
		}
		elseif ($arParams["FILTER"] == "bizproc")
		{
			$pageTitle = GetMessageJS("MOBILE_LOG_BIZPROC");
		}
		else
		{
			$pageTitle = GetMessageJS("MOBILE_LOG_TITLE");
		}

		?><script>
			var arLogTs = {};
			var arCanUserComment = {};
			var bRefreshing = false;
			var bGettingNextPage = false;
			var iPageNumber = 1;
			var nextPageXHR = null;

			BX.message({
				MSLPageId: '<?=CUtil::JSEscape(RandString(4))?>',
				MSLPageNavNum: <?=intval($arResult["PAGE_NAVNUM"])?>,
				MSLSessid: '<?=bitrix_sessid()?>',
				MSLSiteId: '<?=CUtil::JSEscape(SITE_ID)?>',
				MSLSiteDir: '<?=CUtil::JSEscape(SITE_DIR)?>',
				MSLLangId: '<?=CUtil::JSEscape(LANGUAGE_ID)?>',
				MSLDestinationLimit: '<?=intval($arParams["DESTINATION_LIMIT_SHOW"])?>',
				MSLNameTemplate: '<?=CUtil::JSEscape($arParams["NAME_TEMPLATE"])?>',
				MSLShowLogin: '<?=CUtil::JSEscape($arParams["SHOW_LOGIN"])?>',
				MSLShowRating: '<?=CUtil::JSEscape($arParams["SHOW_RATING"])?>',
				MSLNextPostMoreTitle: '<?=GetMessageJS("MOBILE_LOG_NEXT_POST_MORE")?>',
				MSLLogCounter1: '<?=GetMessageJS("MOBILE_LOG_COUNTER_1")?>',
				MSLLogCounter2: '<?=GetMessageJS("MOBILE_LOG_COUNTER_2")?>',
				MSLLogCounter3: '<?=GetMessageJS("MOBILE_LOG_COUNTER_3")?>',
				MSLAddPost: '<?=GetMessageJS("MOBILE_LOG_ADD_POST")?>',
				MSLMenuItemGroupTasks: '<?=GetMessageJS("MB_TASKS_AT_SOCNET_LOG_CPT_MENU_ITEM_LIST")?>',
				MSLMenuItemGroupFiles: '<?=GetMessageJS("MOBILE_LOG_GROUP_FILES")?>',
				MSLPathToTasksRouter: '<?=CUtil::JSEscape($arParams["PATH_TO_TASKS_SNM_ROUTER"])?>',
				MSLPathToLogEntry: '<?=CUtil::JSEscape($arParams["PATH_TO_LOG_ENTRY"])?>',
				MSLPathToUser: '<?=CUtil::JSEscape($arParams["PATH_TO_USER"])?>',
				MSLPathToGroup: '<?=CUtil::JSEscape($arParams["PATH_TO_GROUP"])?>',
				MSLPathToCrmLead: '<?=CUtil::JSEscape($arParams["PATH_TO_CRMLEAD"])?>',
				MSLPathToCrmDeal: '<?=CUtil::JSEscape($arParams["PATH_TO_CRMDEAL"])?>',
				MSLPathToCrmContact: '<?=CUtil::JSEscape($arParams["PATH_TO_CRMCONTACT"])?>',
				MSLPathToCrmCompany: '<?=CUtil::JSEscape($arParams["PATH_TO_CRMCOMPANY"])?>',
				MSLMenuItemFavorites: '<?=GetMessageJS("MOBILE_LOG_MENU_FAVORITES")?>',
				MSLMenuItemMy: '<?=GetMessageJS("MOBILE_LOG_MENU_MY")?>',
				MSLMenuItemImportant: '<?=GetMessageJS("MOBILE_LOG_MENU_IMPORTANT")?>',
				MSLMenuItemRefresh: '<?=GetMessageJS("MOBILE_LOG_MENU_REFRESH")?>',
				MSLLogTitle: '<?=$pageTitle?>'
				<?
				if ($arParams["USE_FOLLOW"] == "Y")
				{
					?>
					, MSLFollowY: '<?=GetMessageJS("MOBILE_LOG_FOLLOW_Y")?>'
					, MSLFollowN: '<?=GetMessageJS("MOBILE_LOG_FOLLOW_N")?>'
					, MSLMenuItemFollowDefaultY: '<?=GetMessageJS("MOBILE_LOG_MENU_FOLLOW_DEFAULT_Y")?>'
					, MSLMenuItemFollowDefaultN: '<?=GetMessageJS("MOBILE_LOG_MENU_FOLLOW_DEFAULT_N")?>'
					<?
				}

				if (
					IsModuleInstalled("timeman")
					|| IsModuleInstalled("tasks")
				)
				{
					?>
					, MSLMenuItemWork: '<?=GetMessageJS("MOBILE_LOG_MENU_WORK")?>'
					<?
				}

				if (
					IsModuleInstalled("bizproc")
					&& IsModuleInstalled("lists")
					&& COption::GetOptionString("lists", "turnProcessesOn", "Y") == 'Y'
				)
				{
					?>
					, MSLMenuItemBizproc: '<?=GetMessageJS("MOBILE_LOG_MENU_BIZPROC")?>'
					<?
				}
				?>
			});
		</script>
		<div class="lenta-notifier" id="lenta_notifier" onclick="__MSLRefresh(true); return false;"><?
			?><span class="lenta-notifier-arrow"></span><?
			?><span class="lenta-notifier-text"><?
				?><span id="lenta_notifier_cnt"></span>&nbsp;<span id="lenta_notifier_cnt_title"></span><?
			?></span><?
		?></div><?
		?><div class="lenta-notifier" id="lenta_notifier_2" onclick="__MSLRefresh(true); return false;"><?
			?><span class="lenta-notifier-text"><?=GetMessage("MOBILE_LOG_RELOAD_NEEDED")?></span><?
		?></div><?
		?><div class="lenta-notifier" id="lenta_refresh_error" onclick="__MSLRefreshError(false);"><?
			?><span class="lenta-notifier-text"><?=GetMessage("MOBILE_LOG_RELOAD_ERROR")?></span><?
		?></div><?
	}
	elseif ($arParams["EMPTY_PAGE"] == "Y")
	{
		?><div id="empty_comment" style="display: none;"><?
			?><div class="post-comment-block" style="position: relative;"><?
				?><div class="post-user-wrap"><?
					?><div id="empty_comment_avatar" class="avatar"<?=(strlen($arResult["EmptyComment"]["AVATAR_SRC"]) > 0 ? " style=\"background-image:url('".$arResult["EmptyComment"]["AVATAR_SRC"]."');\"" : "")?>></div><?
					?><div class="post-comment-cont"><?
						?><div class="post-comment-author"><?=$arResult["EmptyComment"]["AUTHOR_NAME"]?></div><?
						?><div class="post-comment-preview-wait"></div><?
						?><div class="post-comment-preview-undelivered"></div><?
					?></div><?
				?></div><?
				?><div id="empty_comment_text" class="post-comment-text"></div><?
			?></div><?
		?></div><?
		?><div style="display: none;" id="comment_send_button_waiter" class="send-message-button-waiter"></div>
		<script>
			BX.message({
				MSLPageId: '<?=CUtil::JSEscape(RandString(4))?>',
				MSLSessid: '<?=bitrix_sessid()?>',
				MSLSiteId: '<?=CUtil::JSEscape(SITE_ID)?>',
				MSLLangId: '<?=CUtil::JSEscape(LANGUAGE_ID)?>',
				MSLDetailPullDownText1: '<?=GetMessageJS("MOBILE_LOG_NEW_PULL")?>',
				MSLDetailPullDownText2: '<?=GetMessageJS("MOBILE_LOG_NEW_PULL_RELEASE")?>',
				MSLDetailPullDownText3: '<?=GetMessageJS("MOBILE_LOG_DETAIL_NEW_PULL_LOADING")?>',
				MSLDetailCommentsLoading: '<?=GetMessageJS("MOBILE_LOG_EMPTY_COMMENTS_LOADING")?>',
				MSLDetailCommentsFailed: '<?=GetMessageJS("MOBILE_LOG_EMPTY_COMMENTS_FAILED")?>',
				MSLDetailCommentsReload: '<?=GetMessageJS("MOBILE_LOG_EMPTY_COMMENTS_RELOAD")?>',
				MSLPathToLogEntry: '<?=CUtil::JSEscape($arParams["PATH_TO_LOG_ENTRY"])?>',
				MSLPathToUser: '<?=CUtil::JSEscape($arParams["PATH_TO_USER"])?>',
				MSLPathToGroup: '<?=CUtil::JSEscape($arParams["PATH_TO_GROUP"])?>',
				MSLPathToCrmLead: '<?=CUtil::JSEscape($arParams["PATH_TO_CRMLEAD"])?>',
				MSLPathToCrmDeal: '<?=CUtil::JSEscape($arParams["PATH_TO_CRMDEAL"])?>',
				MSLPathToCrmContact: '<?=CUtil::JSEscape($arParams["PATH_TO_CRMCONTACT"])?>',
				MSLPathToCrmCompany: '<?=CUtil::JSEscape($arParams["PATH_TO_CRMCOMPANY"])?>',
				MSLDestinationLimit: '<?=intval($arParams["DESTINATION_LIMIT_SHOW"])?>',
				MSLReply: '<?=GetMessageJS("MOBILE_LOG_EMPTY_COMMENTS_REPLY")?>',
				MSLLikesList: '<?=GetMessageJS("MOBILE_LOG_EMPTY_COMMENTS_LIKES_LIST")?>',
				MSLCommentMenuEdit: '<?=GetMessageJS("MOBILE_LOG_EMPTY_COMMENTS_EDIT")?>',
				MSLCommentMenuDelete: '<?=GetMessageJS("MOBILE_LOG_EMPTY_COMMENTS_DELETE")?>',
				MSLNameTemplate: '<?=CUtil::JSEscape($arParams["NAME_TEMPLATE"])?>',
				MSLShowLogin: '<?=CUtil::JSEscape($arParams["SHOW_LOGIN"])?>',
				MSLShowRating: '<?=CUtil::JSEscape($arParams["SHOW_RATING"])?>',
				MSLDestinationHidden1: '<?=GetMessageJS("MOBILE_LOG_DESTINATION_HIDDEN_1")?>',
				MSLDestinationHidden2: '<?=GetMessageJS("MOBILE_LOG_DESTINATION_HIDDEN_2")?>',
				MSLDestinationHidden3: '<?=GetMessageJS("MOBILE_LOG_DESTINATION_HIDDEN_3")?>',
				MSLDestinationHidden4: '<?=GetMessageJS("MOBILE_LOG_DESTINATION_HIDDEN_4")?>',
				MSLDestinationHidden5: '<?=GetMessageJS("MOBILE_LOG_DESTINATION_HIDDEN_5")?>',
				MSLDestinationHidden6: '<?=GetMessageJS("MOBILE_LOG_DESTINATION_HIDDEN_6")?>',
				MSLDestinationHidden7: '<?=GetMessageJS("MOBILE_LOG_DESTINATION_HIDDEN_7")?>',
				MSLDestinationHidden8: '<?=GetMessageJS("MOBILE_LOG_DESTINATION_HIDDEN_8")?>',
				MSLDestinationHidden9: '<?=GetMessageJS("MOBILE_LOG_DESTINATION_HIDDEN_9")?>',
				MSLDestinationHidden0: '<?=GetMessageJS("MOBILE_LOG_DESTINATION_HIDDEN_0")?>',
				MSLDeletePostDescription: '<?=GetMessageJS("MOBILE_LOG_DELETE_POST_DESCRIPTION")?>',
				MSLDeletePostButtonOk: '<?=GetMessageJS("MOBILE_LOG_DELETE_BUTTON_OK")?>',
				MSLDeletePostButtonCancel: '<?=GetMessageJS("MOBILE_LOG_DELETE_BUTTON_CANCEL")?>',
				MSLCurrentTime: '<?=time()?>',
				MSLEmptyDetailCommentFormTitle: '<?=GetMessageJS("MOBILE_LOG_EMPTY_COMMENT_ADD_TITLE")?>',
				MSLEmptyDetailCommentFormButtonTitle: '<?=GetMessageJS("MOBILE_LOG_EMPTY_COMMENT_ADD_BUTTON_SEND")?>',
				MSLLoadScriptsNeeded: '<?=(COption::GetOptionString('main', 'optimize_js_files', 'N') == 'Y' ? 'N' : 'Y')?>',
				MSLDateTimeFormat: '<?=CUtil::JSEscape(CDatabase::DateFormatToPHP(strlen($arParams["DATE_TIME_FORMAT"]) > 0 ? $arParams["DATE_TIME_FORMAT"] : FORMAT_DATETIME))?>'
			});

			BX.addCustomEvent("onPull", oMSL.onPullComment);
		</script><?
	}
	elseif (
		$arParams["LOG_ID"] > 0
		&& $_REQUEST["empty_get_comments"] != "Y"
	)
	{
		?><div style="display: none;" id="comment_send_button_waiter" class="send-message-button-waiter"></div>
		<script>
			BX.message({
				MSLPageId: '<?=CUtil::JSEscape(RandString(4))?>',
				MSLSessid: '<?=bitrix_sessid()?>',
				MSLSiteId: '<?=CUtil::JSEscape(SITE_ID)?>',
				MSLLangId: '<?=CUtil::JSEscape(LANGUAGE_ID)?>',
				MSLLogId: <?=intval($arParams["LOG_ID"])?>,
				MSLPathToUser: '<?=CUtil::JSEscape($arParams["PATH_TO_USER"])?>',
				MSLPathToGroup: '<?=CUtil::JSEscape($arParams["PATH_TO_GROUP"])?>',
				MSLPathToCrmLead: '<?=CUtil::JSEscape($arParams["PATH_TO_CRMLEAD"])?>',
				MSLPathToCrmDeal: '<?=CUtil::JSEscape($arParams["PATH_TO_CRMDEAL"])?>',
				MSLPathToCrmContact: '<?=CUtil::JSEscape($arParams["PATH_TO_CRMCONTACT"])?>',
				MSLPathToCrmCompany: '<?=CUtil::JSEscape($arParams["PATH_TO_CRMCOMPANY"])?>',
				MSLDestinationLimit: '<?=intval($arParams["DESTINATION_LIMIT_SHOW"])?>',
				MSLReply: '<?=GetMessageJS("MOBILE_LOG_EMPTY_COMMENTS_REPLY")?>',
				MSLLikesList: '<?=GetMessageJS("MOBILE_LOG_EMPTY_COMMENTS_LIKES_LIST")?>',
				MSLCommentMenuEdit: '<?=GetMessageJS("MOBILE_LOG_EMPTY_COMMENTS_EDIT")?>',
				MSLCommentMenuDelete: '<?=GetMessageJS("MOBILE_LOG_EMPTY_COMMENTS_DELETE")?>',
				MSLNameTemplate: '<?=CUtil::JSEscape($arParams["NAME_TEMPLATE"])?>',
				MSLShowLogin: '<?=CUtil::JSEscape($arParams["SHOW_LOGIN"])?>',
				MSLShowRating: '<?=CUtil::JSEscape($arParams["SHOW_RATING"])?>',
				MSLDestinationHidden1: '<?=GetMessageJS("MOBILE_LOG_DESTINATION_HIDDEN_1")?>',
				MSLDestinationHidden2: '<?=GetMessageJS("MOBILE_LOG_DESTINATION_HIDDEN_2")?>',
				MSLDestinationHidden3: '<?=GetMessageJS("MOBILE_LOG_DESTINATION_HIDDEN_3")?>',
				MSLDestinationHidden4: '<?=GetMessageJS("MOBILE_LOG_DESTINATION_HIDDEN_4")?>',
				MSLDestinationHidden5: '<?=GetMessageJS("MOBILE_LOG_DESTINATION_HIDDEN_5")?>',
				MSLDestinationHidden6: '<?=GetMessageJS("MOBILE_LOG_DESTINATION_HIDDEN_6")?>',
				MSLDestinationHidden7: '<?=GetMessageJS("MOBILE_LOG_DESTINATION_HIDDEN_7")?>',
				MSLDestinationHidden8: '<?=GetMessageJS("MOBILE_LOG_DESTINATION_HIDDEN_8")?>',
				MSLDestinationHidden9: '<?=GetMessageJS("MOBILE_LOG_DESTINATION_HIDDEN_9")?>',
				MSLDestinationHidden0: '<?=GetMessageJS("MOBILE_LOG_DESTINATION_HIDDEN_0")?>',
				MSLDateTimeFormat: '<?=CUtil::JSEscape(CDatabase::DateFormatToPHP(strlen($arParams["DATE_TIME_FORMAT"]) > 0 ? $arParams["DATE_TIME_FORMAT"] : FORMAT_DATETIME))?>'
				<?
				if ($arParams["USE_FOLLOW"] == "Y")
				{
					?>
					, MSLFollowY: '<?=GetMessageJS("MOBILE_LOG_FOLLOW_Y")?>'
					, MSLFollowN: '<?=GetMessageJS("MOBILE_LOG_FOLLOW_N")?>'
					<?
				}
				?>
			});

			BX.ready(function() { BX.bind(window, 'scroll', oMSL.onScrollDetail); });
		</script><?
	}

	if ($arParams["NEW_LOG_ID"] <= 0)
	{
		?><div class="lenta-wrapper" id="lenta_wrapper"><?
			?><div class="lenta-item post-without-informers new-post-message" id="blog-post-new-waiter" style="display: none;"><?
				?><div class="post-item-top-wrap"><?
					?><div class="new-post-waiter"></div><?
				?></div><?
			?></div><?
			?><div class="lenta-item post-without-informers new-post-message" id="blog-post-new-error" style="display: none;"><?
				?><div class="post-item-top-wrap"><div class="post-item-post-block"><?
					?><div class="post-item-text" style="text-align: center;"><?=GetMessage("MOBILE_LOG_NEW_ERROR")?></div><?
					?><div class="post-item-text" id="blog-post-new-error-text" style="text-align: center; display: none;"></div><?
				?></div></div><?
			?></div><?
			?><span id="blog-post-first-after"></span><?
	}

	if(strlen($arResult["ErrorMessage"])>0)
	{
		?><span class='errortext'><?=$arResult["ErrorMessage"]?></span><br /><br /><?
	}

	if($arResult["AJAX_CALL"])
	{
		$GLOBALS["APPLICATION"]->RestartBuffer();

		?><script>
			oMSL.arBlockToCheck = {};
		</script><?
	}

	if (
		$arParams["LOG_ID"] > 0
		|| $arParams["EMPTY_PAGE"] == "Y"
	)
	{
		?><script type="text/javascript">
			var commentVarSiteID = null;
			var commentVarLanguageID = null;
			var commentVarLogID = null;
			var commentVarAvatarSize = <?=intval($arParams["AVATAR_SIZE_COMMENT"])?>;
			var commentVarNameTemplate = '<?=CUtil::JSEscape($arParams["NAME_TEMPLATE"])?>';
			var commentVarShowLogin = '<?=CUtil::JSEscape($arParams["SHOW_LOGIN"])?>';
			var commentVarDateTimeFormat = null;
			var commentVarPathToUser = '<?=CUtil::JSEscape($arParams["PATH_TO_USER"])?>';
			var commentVarPathToBlogPost = '<?=CUtil::JSEscape($arParams["PATH_TO_USER_MICROBLOG_POST"])?>';
			var commentVarBlogPostID = null;
			var commentVarURL = null;
			var commentVarAction = null;
			var commentVarEntityTypeID = null;
			var commentVarEntityID = null;
			var commentVarRatingType = '<?=CUtil::JSEscape($arParams["RATING_TYPE"])?>';
			var tmp_post_id = 0;
			var tmp_log_id = 0;

			BX.message({
				MSLSiteDir: '<?=CUtil::JSEscape(SITE_DIR)?>',
				MSLLogEntryTitle: '<?=GetMessageJS("MOBILE_LOG_ENTRY_TITLE")?>',
				MSLSharePost: '<?=GetMessageJS("MOBILE_LOG_SHARE_POST")?>',
				MSLShareTableOk: '<?=GetMessageJS("MOBILE_LOG_SHARE_TABLE_OK")?>',
				MSLShareTableCancel: '<?=GetMessageJS("MOBILE_LOG_SHARE_TABLE_CANCEL")?>',
				MSLIsDenyToAll: '<?=($arResult["bDenyToAll"] ? 'Y' : 'N')?>',
				MSLEditPost: '<?=GetMessageJS("MOBILE_LOG_EDIT_POST")?>',
				MSLDeletePost: '<?=GetMessageJS("MOBILE_LOG_DELETE_POST")?>'
				<?
				if ($arParams["USE_FOLLOW"] == "Y")
				{
					?>
					, MSLFollowY: '<?=GetMessageJS("MOBILE_LOG_FOLLOW_Y")?>'
					, MSLFollowN: '<?=GetMessageJS("MOBILE_LOG_FOLLOW_N")?>'
					<?
				}
				?>,
				MSLTextPanelMenuPhoto: '<?=CUtil::JSEscape(GetMessage("MOBILE_LOG_TEXTPANEL_MENU_PHOTO"))?>',
				MSLTextPanelMenuGallery: '<?=CUtil::JSEscape(GetMessage("MOBILE_LOG_TEXTPANEL_MENU_GALLERY"))?>',
				MSLAjaxInterfaceFullURI: '<?=CUtil::JSEscape((CMain::IsHTTPS() ? "https" : "http")."://".$_SERVER["HTTP_HOST"].SITE_DIR.'mobile/ajax.php')?>'
			});
		</script><?
	}

	if ($arParams["EMPTY_PAGE"] == "Y")
	{
		$frame = \Bitrix\Main\Page\Frame::getInstance();
		$frame->setEnable();
		$frame->setUseAppCache();
		\Bitrix\Main\Data\AppCacheManifest::getInstance()->addAdditionalParam("page", "empty_detail");
		\Bitrix\Main\Data\AppCacheManifest::getInstance()->addAdditionalParam("MobileAPIVersion", CMobile::getApiVersion());
		\Bitrix\Main\Data\AppCacheManifest::getInstance()->addAdditionalParam("MobilePlatform", CMobile::getPlatform());
		\Bitrix\Main\Data\AppCacheManifest::getInstance()->addAdditionalParam("version", "v5");

		if (CMobile::getApiVersion() < 4 && CMobile::getPlatform() != "android")
		{
			?><div class="post-card-wrap" id="post-card-wrap" onclick=""><?
		}

		?><div class="post-wrap" id="lenta_item"><?
			?><div id="post_log_id" data-log-id="" data-ts="" style="display: none;"></div><?
			?><div id="post_item_top_wrap" class="post-item-top-wrap"><?
				?><div class="post-item-top" id="post_item_top"></div><?
				?><div class="post-item-post-block" id="post_block_check_cont"></div><?

				?><div id="post_inform_wrap_two" class="post-item-inform-wrap"><?

					// rating
					?><div id="rating_text" style="display: none;"></div><?
					?><div id="rating_button_cont" style="display: none;"><?
						?><span id="rating_button"><?
							?><div class="post-item-inform-left"><?=GetMessage("MOBILE_LOG_LIKE")?></div><?
						?></span><?
					?></div><?

					?><span id="comments_control" style="display: none;"><?
						?><div class="post-item-informers post-item-inform-comments" onclick="oMSL.setFocusOnCommentForm();"><?
							?><div class="post-item-inform-left"><?=GetMessage('MOBILE_LOG_COMMENT')?></div><?
						?></div><?
					?></span><?

					?><a id="post_more_limiter"  onclick="oMSL.expandText();" class="post-item-more" ontouchstart="this.classList.toggle('post-item-more-pressed')" ontouchend="this.classList.toggle('post-item-more-pressed')" style="display: none;"><?
						?><?=GetMessage("MOBILE_LOG_EXPAND")?><?
					?></a><?

					?><div id="log_entry_follow" class="post-item-informers <?=($arResult["FOLLOW_DEFAULT"] == 'Y' ? 'post-item-follow-default-active' : 'post-item-follow-default')?>" style="display: none;"><?
						?><div class="post-item-inform-left"></div><?
					?></div><?
				?></div><?

				$bRatingExtended = (
					CModule::IncludeModule("mobileapp") 
						? CMobile::getApiVersion() >= 2 
						: intval($GLOBALS["APPLICATION"]->GetPageProperty("api_version")) >= 2
				);

				if ($bRatingExtended)
				{
					?><div class="post-item-inform-wrap-tree" style="display: none;" id="rating-footer-wrap"><?
						?><div class="post-item-inform-footer" id="rating-footer"></div><?
					?></div><?
				}

			?></div><?
			?><div class="post-comments-wrap" id="post-comments-wrap"><span id="post-comment-last-after"></span></div><?
		?></div><?
		?><script type="text/javascript">
			var entryType = null;
			BX.ready(function()
			{
				oMSL.arBlockToCheck[0] = {
					lenta_item_id: 'lenta_item',
					text_block_id: 'post_block_check_cont',
					title_block_id: 'post_block_check_title',
					more_overlay_id: 'post_more_block',
					more_button_id: 'post_more_limiter'
				};
			});
		</script><?

		if (CMobile::getApiVersion() < 4 && CMobile::getPlatform() != "android")
		{
			?></div><? // post-card-wrap
		}

		?><div id="post-comments-form-wrap"></div><?
	}
	elseif (
		$arResult["Events"]
		&& is_array($arResult["Events"])
		&& count($arResult["Events"]) > 0
	)
	{
		?><script type="text/javascript">
			if (BX("lenta_block_empty", true))
				BX("lenta_block_empty", true).style.display = "none";
		</script><?

		foreach ($arResult["Events"] as $arEvent)
		{
			$event_cnt++;
			$ind = RandString(8);

			$bUnread = (
				$arParams["SET_LOG_COUNTER"] == "Y"
				&& $arResult["COUNTER_TYPE"] == "**"
				&& $arEvent["USER_ID"] != $GLOBALS["USER"]->GetID()
				&& intval($arResult["LAST_LOG_TS"]) > 0
				&& (MakeTimeStamp($arEvent["LOG_DATE"]) - intval($arResult["TZ_OFFSET"])) > $arResult["LAST_LOG_TS"]
			);

			if(in_array($arEvent["EVENT_ID"], array("blog_post", "blog_post_important", "blog_post_micro", "blog_comment", "blog_comment_micro")))
			{
				$arComponentParams = array(
					"PATH_TO_BLOG" => $arParams["PATH_TO_USER_BLOG"],
					"PATH_TO_POST" => $arParams["PATH_TO_USER_MICROBLOG_POST"],
					"PATH_TO_BLOG_CATEGORY" => $arParams["PATH_TO_USER_BLOG_CATEGORY"],
					"PATH_TO_POST_EDIT" => $arParams["PATH_TO_USER_BLOG_POST_EDIT"],
					"PATH_TO_USER" => $arParams["PATH_TO_USER"],
					"PATH_TO_GROUP" => $arParams["PATH_TO_GROUP"],
					"PATH_TO_SMILE" => $arParams["PATH_TO_BLOG_SMILE"],
					"PATH_TO_MESSAGES_CHAT" => $arResult["PATH_TO_MESSAGES_CHAT"],
					"PATH_TO_LOG_ENTRY" => $arParams["PATH_TO_LOG_ENTRY"],
					"PATH_TO_LOG_ENTRY_EMPTY" => $arParams["PATH_TO_LOG_ENTRY_EMPTY"],
					"SET_NAV_CHAIN" => "N",
					"SET_TITLE" => "N",
					"POST_PROPERTY" => $arParams["POST_PROPERTY"],
					"DATE_TIME_FORMAT" => $arParams["DATE_TIME_FORMAT"],
					"DATE_TIME_FORMAT_FROM_LOG" => $arParams["DATE_TIME_FORMAT"],
					"LOG_ID" => $arEvent["ID"],
					"USER_ID" => $arEvent["USER_ID"],
					"ENTITY_TYPE" => $arEvent["ENTITY_TYPE"],
					"ENTITY_ID" => $arEvent["ENTITY_ID"],
					"EVENT_ID" => $arEvent["EVENT_ID"],
					"EVENT_ID_FULLSET" => $arEvent["EVENT_ID_FULLSET"],
					"IND" => $ind,
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
					"IMAGE_MAX_WIDTH" => $arParams["IMAGE_MAX_WIDTH"],
					"IMAGE_MAX_HEIGHT" => $arParams["IMAGE_MAX_HEIGHT"],
					"ALLOW_POST_CODE" => $arParams["ALLOW_POST_CODE"],
					"ID" => $arEvent["SOURCE_ID"],
					"FROM_LOG" => "Y",
					"ADIT_MENU" => $arAditMenu,
					"IS_LIST" => (intval($arParams["LOG_ID"]) <= 0),
					"IS_UNREAD" => $bUnread,
					"IS_HIDDEN" => false,
					"LAST_LOG_TS" => ($arResult["LAST_LOG_TS"] > 0 ? $arResult["LAST_LOG_TS"] + $arResult["TZ_OFFSET"] : 0),
					"CACHE_TIME" => $arParams["CACHE_TIME"],
					"CACHE_TYPE" => $arParams["CACHE_TYPE"],
					"ALLOW_VIDEO"  => $arParams["BLOG_COMMENT_ALLOW_VIDEO"],
					"ALLOW_IMAGE_UPLOAD" => $arParams["BLOG_COMMENT_ALLOW_IMAGE_UPLOAD"],
					"USE_CUT" => $arParams["BLOG_USE_CUT"],
					"MOBILE" => "Y",
					"ATTACHED_IMAGE_MAX_WIDTH_FULL" => 640,
					"ATTACHED_IMAGE_MAX_HEIGHT_FULL" => 832,
					"RETURN_DATA" => ($arParams["LOG_ID"] > 0 ? "Y" : "N"),
					"AVATAR_SIZE_COMMENT" => $arParams["AVATAR_SIZE_COMMENT"],
					"AVATAR_SIZE" => $arParams["AVATAR_SIZE"],
					"CHECK_PERMISSIONS_DEST" => $arParams["CHECK_PERMISSIONS_DEST"],
					"COMMENTS_COUNT" => $arEvent["COMMENTS_COUNT"],
					"USE_FOLLOW" => $arParams["USE_FOLLOW"]
				);

				if ($arParams["USE_FOLLOW"] == "Y")
				{
					$arComponentParams["FOLLOW"] = $arEvent["FOLLOW"];
					$arComponentParams["FOLLOW_DEFAULT"] = $arResult["FOLLOW_DEFAULT"];
				}

				if (
					strlen($arEvent["RATING_TYPE_ID"])>0
					&& $arEvent["RATING_ENTITY_ID"] > 0
					&& $arParams["SHOW_RATING"] == "Y"
				)
				{
					$arComponentParams["RATING_ENTITY_ID"] = $arEvent["RATING_ENTITY_ID"];
				}

				if ($GLOBALS["USER"]->IsAuthorized())
				{
					$arComponentParams["FAVORITES"] = (
						array_key_exists("FAVORITES_USER_ID", $arEvent)
						&& intval($arEvent["FAVORITES_USER_ID"]) > 0
							? "Y"
							: "N"
					);			
				}

				$APPLICATION->IncludeComponent(
					"bitrix:socialnetwork.blog.post",
					"mobile",
					$arComponentParams,
					$component,
					Array("HIDE_ICONS" => "Y")
				);
			}
			else
			{
				$arComponentParams = array_merge($arParams, array(
						"LOG_ID" => $arEvent["ID"],
						"IS_LIST" => (intval($arParams["LOG_ID"]) <= 0),
						"LAST_LOG_TS" => $arResult["LAST_LOG_TS"],
						"COUNTER_TYPE" => $arResult["COUNTER_TYPE"],
						"AJAX_CALL" => $arResult["AJAX_CALL"],
						"PATH_TO_LOG_ENTRY_EMPTY" => $arParams["PATH_TO_LOG_ENTRY_EMPTY"],
						"bReload" => $arResult["bReload"],
						"IND" => $ind,
						"CURRENT_PAGE_DATE" => $arResult["CURRENT_PAGE_DATE"],
						"EVENT" => array(
							"IS_UNREAD" => $bUnread,
							"LOG_DATE" => $arEvent["LOG_DATE"],
							"COMMENTS_COUNT" => $arEvent["COMMENTS_COUNT"],
						)
					)
				);

				if ($GLOBALS["USER"]->IsAuthorized())
				{
					if ($arParams["USE_FOLLOW"] == "Y")
					{
						$arComponentParams["EVENT"]["FOLLOW"] = $arEvent["FOLLOW"];
						$arComponentParams["EVENT"]["DATE_FOLLOW"] = $arEvent["DATE_FOLLOW"];
						$arComponentParams["FOLLOW_DEFAULT"] = $arResult["FOLLOW_DEFAULT"];
					}

					$arComponentParams["EVENT"]["FAVORITES"] = (
						array_key_exists("FAVORITES_USER_ID", $arEvent)
						&& intval($arEvent["FAVORITES_USER_ID"]) > 0
							? "Y"
							: "N"
					);
				}

				if (
					strlen($arEvent["RATING_TYPE_ID"])>0
					&& $arEvent["RATING_ENTITY_ID"] > 0
					&& $arParams["SHOW_RATING"] == "Y"
				)
				{
					$arComponentParams["RATING_TYPE"] = $arParams["RATING_TYPE"];
					$arComponentParams["EVENT"]["RATING_TYPE_ID"] = $arEvent["RATING_TYPE_ID"];
					$arComponentParams["EVENT"]["RATING_ENTITY_ID"] = $arEvent["RATING_ENTITY_ID"];
				}

				$APPLICATION->IncludeComponent(
					"bitrix:mobile.socialnetwork.log.entry",
					"",
					$arComponentParams,
					$component,
					Array("HIDE_ICONS" => "Y")
				);
			}


		} // foreach ($arResult["Events"] as $arEvent)
	} // if ($arResult["Events"] && is_array($arResult["Events"]) && count($arResult["Events"]) > 0)
	elseif
	(
		$arParams["LOG_ID"] <= 0
		&& !$arResult["AJAX_CALL"]
	)
	{
		?><div class="lenta-block-empty" id="lenta_block_empty"><?=GetMessage("MOBILE_LOG_MESSAGE_EMPTY");?></div><?
	}

	if($arResult["AJAX_CALL"])
	{
		$strParams = "LAST_LOG_TS=".$arResult["LAST_LOG_TS"]."&AJAX_CALL=Y&PAGEN_".$arResult["PAGE_NAVNUM"]."=".($arResult["PAGE_NUMBER"] + 1);

		?><script type="text/javascript">
			<?
			if (
				$event_cnt > 0
				&& $event_cnt >= $arParams["PAGE_SIZE"]
			)
			{
				?>
				url_next = '<?=$APPLICATION->GetCurPageParam($strParams, array("LAST_LOG_TS", "AJAX_CALL", "RELOAD", "PAGEN_".$arResult["PAGE_NAVNUM"]));?>';
				<?
			}
			else
			{
				?>
				oMSL.initScroll(false, true);
				<?
				if ($arParams["NEW_LOG_ID"] > 0)
				{
					?>
					setTimeout(function() {
						oMSL.checkNodesHeight();
					}, 1000);
					<?
				}
			}
			?>
			BitrixMobile.LazyLoad.showImages(); // when load next page
		</script><?

		if ($arParams["NEW_LOG_ID"] <= 0)
		{
			die();
		}
	}

	if ($arParams["NEW_LOG_ID"] <= 0)
	{
		if (
			$arParams["LOG_ID"] <= 0
			&& $arParams["EMPTY_PAGE"] != "Y"
		)
		{
			if ($event_cnt >= $arParams["PAGE_SIZE"])
			{
				?><div id="next_post_more" class="next-post-more"></div><?
			}
			?></div><? // lenta-wrapper
		}

		$strParams = "LAST_LOG_TS=".$arResult["LAST_LOG_TS"]."&AJAX_CALL=Y&PAGEN_".$arResult["PAGE_NAVNUM"]."=".($arResult["PAGE_NUMBER"] + 1);
		if (
			is_array($arResult["arLogTmpID"])
			&& count($arResult["arLogTmpID"]) > 0
		)
		{
			$strParams .= "&pplogid=".implode("|", $arResult["arLogTmpID"]);
		}

		// sonet_log_content
		?><script type="text/javascript">
			var maxScroll = 0;
			var isPullDownEnabled = false;
			var isPullDownLocked = false;

			var url_next = '<?=$APPLICATION->GetCurPageParam($strParams, array("LAST_LOG_TS", "AJAX_CALL", "RELOAD", "PAGEN_".$arResult["PAGE_NAVNUM"], "pplogid"));?>';
			<?
			if (
				($arParams["LOG_ID"] > 0 || $arParams["EMPTY_PAGE"] == "Y")
				&& $_REQUEST["BOTTOM"] == "Y"
			)
			{
				?>
				__MSLDetailMoveBottom();
				<?
			}
		?>
		</script>
		<?
		if ($arResult["RELOAD"])
		{
			?></div><?
			die();
		}	

		if (isset($feedFrame))
		{
			$feedFrame->end();
			?></div><?
		}
	}
}
?>