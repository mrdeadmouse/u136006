<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!empty($arResult["COMMENT_ERROR"]))
{
	$APPLICATION->RestartBuffer();

	echo "*";
	require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');
	die();
}
elseif (intval($_GET["delete_comment_id"]) > 0)
{
	$APPLICATION->RestartBuffer();
	while(ob_end_clean()); // hack!

	echo "SUCCESS";
	require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');
	die();
}

include_once($_SERVER["DOCUMENT_ROOT"].SITE_TEMPLATE_PATH."/components/bitrix/socialnetwork.blog.post/mobile/functions.php");

if ($_REQUEST["empty_get_comments"] == "Y")
{
	if (
		$GLOBALS["USER"]->IsAuthorized() 
		&& CModule::IncludeModule("pull") 
		&& CPullOptions::GetNginxStatus()
	)
	{
		CPullWatch::Add($GLOBALS["USER"]->GetID(), "UNICOMMENTSBLOG_".$arParams["ID"]);
		CPullWatch::DeferredSql();
	}
}

if ($arResult["is_ajax_post"] == "Y")
{
	$APPLICATION->RestartBuffer();

	if ($_REQUEST["empty_get_comments"] != "Y")
	{
		$strBufferText = "";
		ob_start();
	}
}
else
{
	?><script>
	app.setPageID('BLOG_POST_<?=$arParams["ID"]?>');
	BX.message({
		SBPCurlToMore: '<?=CUtil::JSEscape($GLOBALS["APPLICATION"]->GetCurPageParam("last_comment_id=#comment_id#&comment_post_id=#post_id#&IFRAME=Y", array("last_comment_id", "comment_post_id", "IFRAME")))?>',
		SBPCurlToNew: '<?=CUtil::JSEscape($arResult["urlToNew"])?>',
		SBPClogID: <?=intval($arParams["LOG_ID"])?>
	});
	commentVarDateTimeFormat = '<?=CUtil::JSEscape($arParams["DATE_TIME_FORMAT"])?>';
	<?
	$strPullBlock = "";
	ob_start();

	if(
		CModule::IncludeModule("pull") 
		&& IntVal($arResult["userID"]) > 0
	)
	{
		?>
		var arCommentsToShow = [];
		var arEntryCommentID = [];
		var bLockCommentSending = false;

		tmp_log_id = <?=$arParams["LOG_ID"]?>;
		tmp_post_id = <?=$arParams["ID"]?>;

		app.onCustomEvent('onPullExtendWatch', {'id': 'UNICOMMENTSBLOG_<?=$arParams["ID"]?>'});
		<?
		if ($_REQUEST["empty_get_comments"] != "Y")
		{
			?>
			BX.addCustomEvent("onPull", oMSL.onPullComment);
			<?
		}
	}

	$strPullBlock = ob_get_contents();
	ob_end_clean();

	if (
		$_REQUEST["empty_get_comments"] != "Y" 
		&& strlen($strPullBlock) > 0)
	{
		?><?=$strPullBlock;?><?
	}

	?>
	</script><?
}

if(strlen($arResult["MESSAGE"])>0)
{
	?>
	<?
}
if(strlen($arResult["ERROR_MESSAGE"])>0)
{
	?>
	<?
}
if(strlen($arResult["FATAL_MESSAGE"])>0)
{
	?>
	<?
}
elseif(!empty($arResult["CommentsResult"]) || $arResult["CanUserComment"])
{
	$commentsCnt = count($arResult["CommentsResult"]);

	if(!empty($arResult["CommentsResult"]))
	{
		$i = 0;
		$moreCommentId = IntVal($_REQUEST["last_comment_id"]);

		$commentsCnt = count($arResult["CommentsResult"]);

		$i = 0;
		$moreCommentId = IntVal($_REQUEST["last_comment_id"]);

		if (
			$commentsCnt > $arResult["newCount"] 
			&& $arResult["newCount"] > 0 
			&& $moreCommentId <= 0
		)
			array_splice($arResult["CommentsResult"], 0, ($commentsCnt - $arResult["newCount"]));

		if($moreCommentId > 0)
		{
			array_splice($arResult["CommentsResult"], -$moreCommentId);
			$prev = IntVal(count($arResult["CommentsResult"]) - $arParams["PAGE_SIZE"]);
			if($prev <= 0)
				$prev = 0;

			?><script>
				BX("comcntshow").value = <?=IntVal($commentsCnt - $prev)?>;
			<?
			if($prev > 0)
			{
				?>
				BX("comcntleave-all").innerHTML = "<?=$prev?>";
				BX("comcntleave-old").innerHTML = "<?=$prev?>";
				<?
			}
			else
			{
				?>
				BX("post-comment-more").style.display = "none";
				BX("comshowend").value = "Y";
				BX("comcntleave-old").innerHTML = "<?=$commentsCnt?>";
				BX("comcntleave-all").innerHTML = "<?=$commentsCnt?>";
				BX("comcntshow").value = 0;
				BX("blog-comment-more-old").style.display = "none";
				BX("blog-comment-more-all").style.display = "inline-block";
				<?
			}
			?>
			</script>
			<?
			if($prev > 0)
				array_splice($arResult["CommentsResult"], 0, $prev);
		}

		if ($arResult["is_ajax_post"] != "Y")
		{
			?><div class="post-comments-wrap" id="post-comments-wrap"><?
		}

		if ($_REQUEST["empty_get_comments"] == "Y")
		{
			$APPLICATION->RestartBuffer();

			$strCommentsText = "";
			ob_start();
			?><script>
				app.setPageID('BLOG_POST_<?=$arParams["ID"]?>');
				app.onCustomEvent('onCommentsGet', { log_id: <?=intval($arParams["LOG_ID"])?>, ts: '<?=time()?>'});

				BX.message({
					SBPCurlToMore: '<?=CUtil::JSEscape($GLOBALS["APPLICATION"]->GetCurPageParam("last_comment_id=#comment_id#&comment_post_id=#post_id#&IFRAME=Y", array("last_comment_id", "comment_post_id", "IFRAME", "empty_get_form", "empty_get_comments")))?>',
					SBPCurlToNew: '<?=CUtil::JSEscape($arResult["urlToNew"])?>',
					SBPClogID: <?=intval($arParams["LOG_ID"])?>
				});
				commentVarDateTimeFormat = '<?=CUtil::JSEscape($arParams["DATE_TIME_FORMAT"])?>';
				
				<?
				if (strlen($strPullBlock) > 0)
				{
					?><?=$strPullBlock;?><?
				}
			?></script><?
		}

		foreach($arResult["CommentsResult"] as $comment)
		{
			$i++;

			$arTmpUser = array(
				"NAME" => $arResult["userCache"][$comment["AUTHOR_ID"]]["~NAME"],
				"LAST_NAME" => $arResult["userCache"][$comment["AUTHOR_ID"]]["~LAST_NAME"],
				"SECOND_NAME" => $arResult["userCache"][$comment["AUTHOR_ID"]]["~SECOND_NAME"],
				"LOGIN" => $arResult["userCache"][$comment["AUTHOR_ID"]]["~LOGIN"]
			);
			$nameFormatted = CUser::FormatName($arParams["NAME_TEMPLATE"], $arTmpUser, ($arParams["SHOW_LOGIN"] != "N" ? true : false));

			if(
				$moreCommentId <= 0
				&& $i == 1 
				&& $commentsCnt > $arResult["newCount"]
			)
			{
				$adit1 = " style=\"display:none;\"";
				$adit2 = "";
				if($commentsCnt > ($arResult["newCount"] + $arParams["PAGE_SIZE"]))
				{
					$adit1 = "";
					$adit2 = " style=\"display:none;\"";
				}

				?><div 
					id="post-comment-more" 
					class="post-comments-button" 
					onclick="oMSL.showMoreComments({ postId: <?=intval($arResult["Post"]["ID"])?>});" 
					ontouchstart="BX.toggleClass(this, 'post-comments-button-press');" 
					ontouchend="BX.toggleClass(this, 'post-comments-button-press');"
				><?
					?><span id="blog-comment-more-old"<?=$adit1?>><?=str_replace("#COMMENTS#", '<span id="comcntleave-old">'.$commentsCnt.'</span>', GetMessage("BLOG_C_BUTTON_OLD"))?></span><?
					?><span id="blog-comment-more-all"<?=$adit2?>><?=str_replace("#COMMENTS#", '<span id="comcntleave-all">'.$commentsCnt.'</span>', GetMessage("BLOG_C_BUTTON_ALL"))?></span><?
				?></div><?
				?><div id="blog-comment-hidden" style="display:none; overflow:hidden;"></div><?
				?><input type="hidden" name="comcntshow" id="comcntshow" value="<?=$arResult["newCount"]?>"><?
				?><input type="hidden" name="comshowend" id="comshowend" value="N"><?
			}

			if ($comment["PUBLISH_STATUS"] != BLOG_PUBLISH_STATUS_PUBLISH)
			{
				continue;
			}

			$bUnread = (
				$comment["AUTHOR_ID"] != $GLOBALS["USER"]->GetID() 
				&& $comment["NEW"] == "Y"
			);

			$strBottomBlock = "";
			ob_start();

			if ($arParams["SHOW_RATING"] == "Y")
			{
				$arResultVote = $GLOBALS["APPLICATION"]->IncludeComponent(
					"bitrix:rating.vote", 
					"mobile_comment_".$arParams["RATING_TYPE"],
					Array(
						"ENTITY_TYPE_ID" => "BLOG_COMMENT",
						"ENTITY_ID" => $comment["ID"],
						"OWNER_ID" => $comment["AUTHOR_ID"],
						"USER_VOTE" => $arResult["RATING"][$comment["ID"]]["USER_VOTE"],
						"USER_HAS_VOTED" => $arResult["RATING"][$comment["ID"]]["USER_HAS_VOTED"],
						"TOTAL_VOTES" => $arResult["RATING"][$comment["ID"]]["TOTAL_VOTES"],
						"TOTAL_POSITIVE_VOTES" => $arResult["RATING"][$comment["ID"]]["TOTAL_POSITIVE_VOTES"],
						"TOTAL_NEGATIVE_VOTES" => $arResult["RATING"][$comment["ID"]]["TOTAL_NEGATIVE_VOTES"],
						"TOTAL_VALUE" => $arResult["RATING"][$comment["ID"]]["TOTAL_VALUE"],
						"PATH_TO_USER_PROFILE" => $arParams["~PATH_TO_USER"],
					),
					$arParams["component"],
					array("HIDE_ICONS" => "Y")
				);
			}

			$strBottomBlock = ob_get_contents();
			ob_end_clean();

			?><script>
				BX.ready(function()
				{
					BX.MSL.viewImageBind('blg-comment-text-<?=$comment["ID"]?>', { tag: 'IMG', attr: 'data-bx-image' });
					if (BX('blg-comment-images-<?=$comment["ID"]?>'))
					{
						BX.MSL.viewImageBind('blg-comment-images-<?=$comment["ID"]?>', { tag: 'IMG', attr: 'data-bx-image' });
					}
					if (BX('blg-comment-files-<?=$comment["ID"]?>'))
					{
						BX.MSL.viewImageBind('blg-comment-files-<?=$comment["ID"]?>', { tag: 'IMG', attr: 'data-bx-image' });
					}
					
					if (app.enableInVersion(10))
					{
						BX.bind(BX('entry-comment-BLOG_<?=intval($arResult["Post"]["ID"])?>-<?=$comment["ID"]?>'), 'click', function(event)
						{
							if (
								typeof oMSL.keyboardShown != 'undefined'
								&& oMSL.keyboardShown === true
							)
							{
								return false;
							}

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

							oMSL.showCommentMenu([
								{
									title: '<?=GetMessageJS("BLOG_C_REPLY")?>',
									callback: function()
									{
										oMSL.replyToComment(<?=$comment["ID"]?>, '<?=CUtil::JSEscape(htmlspecialcharsback($nameFormatted))?>');
									}
								}
								<?
								if (
									isset($arResultVote)
									&& isset($arResultVote["TOTAL_POSITIVE_VOTES"])
									&& intval($arResultVote["TOTAL_POSITIVE_VOTES"]) > 0
								)
								{
									?>
									,{
										title: '<?=GetMessageJS("BLOG_C_LIKES_LIST")?>',
										callback: function()
										{
											RatingLike.List('<?=CUtil::JSEscape(htmlspecialcharsbx($arResultVote['VOTE_ID']))?>');
										}
									}
									<?
								}

								if (
									isset($comment["CAN_EDIT"]) && $comment["CAN_EDIT"] == "Y"
									&& isset($comment["CAN_DELETE"]) && $comment["CAN_DELETE"] == "Y"
								)
								{
									?>
									,
									<?
								}
								if (isset($comment["CAN_EDIT"]) && $comment["CAN_EDIT"] == "Y")
								{
									?>
									{
										title: '<?=GetMessageJS("BPC_MES_EDIT")?>',
										callback: function()
										{
											oMSL.editComment({
												commentId: <?=$comment["ID"]?>,
												commentText: '<?=CUtil::JSEscape($comment["POST_TEXT"])?>', 
												commentType: 'blog', 
												postId: <?=intval($arParams["ID"])?>, 
												nodeId: 'entry-comment-BLOG_<?=intval($arResult["Post"]["ID"])?>-<?=$comment["ID"]?>'
											});
										}
									}
									<?
								}
								if (
									isset($comment["CAN_EDIT"]) && $comment["CAN_EDIT"] == "Y"
									&& isset($comment["CAN_DELETE"]) && $comment["CAN_DELETE"] == "Y"
								)
								{
									?>
									,
									<?
								}
								if (
									isset($comment["CAN_DELETE"]) && $comment["CAN_DELETE"] == "Y"
								)
								{
									?>
									{
										title: '<?=GetMessageJS("BPC_MES_DELETE")?>',
										callback: function()
										{
											oMSL.deleteComment({
												commentId: <?=$comment["ID"]?>, 
												commentType: 'blog', 
												nodeId: 'entry-comment-BLOG_<?=intval($arResult["Post"]["ID"])?>-<?=$comment["ID"]?>'
											});
										}
									}
									<?
								}
								?>
							]);
						});
					}
				}); // ready
			</script><?

			$avatarExists = (array_key_exists("PERSONAL_PHOTO_RESIZED", $arResult["userCache"][$comment["AUTHOR_ID"]]) && strlen($arResult["userCache"][$comment["AUTHOR_ID"]]["PERSONAL_PHOTO_RESIZED"]["SRC"]) > 0);
			?><div class="post-comment-block<?=($bUnread ? " post-comment-new" : "")?>" id="entry-comment-BLOG_<?=intval($arResult["Post"]["ID"])?>-<?=$comment["ID"]?>"<?=($arResult["ajax_comment"] == $comment["ID"] ? ' data-send="Y"' : '')?>><?
				?><div class="post-user-wrap"><?
					?><div class="avatar"<?=($avatarExists ? " style=\"background-image:url('".$arResult["userCache"][$comment["AUTHOR_ID"]]["PERSONAL_PHOTO_RESIZED"]["SRC"]."')\"" : "")?>></div><?
					?><div class="post-comment-cont"><?
						?><a href="<?=$arResult["userCache"][$comment["AUTHOR_ID"]]["url"]?>" class="post-comment-author"><?=$nameFormatted?></a><?
						?><div class="post-comment-time"><?
							$timestamp = MakeTimeStamp($comment["DATE_CREATE"]);
							$arFormat = Array(
								"tommorow" => "tommorow, ".GetMessage("BLOG_COMMENT_MOBILE_FORMAT_TIME"),
								"today" => "today, ".GetMessage("BLOG_COMMENT_MOBILE_FORMAT_TIME"),
								"yesterday" => "yesterday, ".GetMessage("BLOG_COMMENT_MOBILE_FORMAT_TIME"),
								"" => (date("Y", $timestamp) == date("Y") ? GetMessage("BLOG_COMMENT_MOBILE_FORMAT_DATE") : GetMessage("BLOG_COMMENT_MOBILE_FORMAT_DATE_YEAR"))
							);
							?><?=FormatDate($arFormat, $timestamp)?><?
						?></div><?
					?></div><?						
				?></div><?
				?><div class="post-comment-text" id="blg-comment-text-<?=$comment["ID"]?>"><?=$comment["TextFormated"]?></div><?

				if(!empty($arResult["arImages"][$comment["ID"]]))
				{
					?><div class="post-item-attached-img-wrap" id="blg-comment-images-<?=$comment["ID"]?>"><?
						$jsIds = "";
						foreach($arResult["arImages"][$comment["ID"]] as $val)
						{
							$id = "blog-comment-attached-".strtolower(randString(5));
							$jsIds .= $jsIds !== "" ? ', "'.$id.'"' : '"'.$id.'"';

							?><div class="post-item-attached-img-block" onclick="app.loadPageBlank({ url: '<?=$val["full"]?>' }); event.stopPropagation();"><img class="post-item-attached-img" id="<?=$id?>" src="<?=CMobileLazyLoad::getBase64Stub()?>" data-src="<?=$val["small"]?>" alt="" border="0"></div><?
						}
					?></div><?
					if(strlen($jsIds) > 0)
					{
						?><script>
							BitrixMobile.LazyLoad.registerImages([<?=$jsIds?>], oMSL.checkVisibility);
						</script><?
					}
				}

				if($comment["COMMENT_PROPERTIES"]["SHOW"] == "Y")
				{
					?><div class="post-item-attached-file-wrap" id="blg-comment-files-<?=$comment["ID"]?>"><?
						$eventHandlerID = false;
						$eventHandlerID = AddEventHandler('main', 'system.field.view.file', '__blogUFfileShowMobile');
						foreach ($comment["COMMENT_PROPERTIES"]["DATA"] as $FIELD_NAME => $arPostField)
						{
							if(!empty($arPostField["VALUE"]))
							{
								$APPLICATION->IncludeComponent(
									"bitrix:system.field.view", 
									$arPostField["USER_TYPE"]["USER_TYPE_ID"], 
									array(
										"arUserField" => $arPostField,
										"MOBILE" => "Y"
									),
									null,
									array("HIDE_ICONS"=>"Y")
								);
							}
						}
						if (
							$eventHandlerID !== false 
							&& (intval($eventHandlerID) > 0 )
						)
						{
							RemoveEventHandler('main', 'system.field.view.file', $eventHandlerID);
						}
					?></div><?
				}

				if (strlen($strBottomBlock) > 0)
				{
					?><?=$strBottomBlock;?><?
				}

				if (CMobile::getApiVersion() >= 10)
				{
					?><div class="post-comment-reply"><?
						?><div class="post-comment-reply-text" onclick="oMSL.replyToComment(<?=intval($comment["AUTHOR_ID"])?>, '<?=CUtil::JSEscape($nameFormatted)?>', event);"><?
							?><?=GetMessage('BLOG_C_REPLY')?><?
						?></div><?
					?></div><?
				}

			?></div><? // post-comment-block
		}

		if (
			$arResult["is_ajax_post"] == "Y" 
			|| $_REQUEST["empty_get_comments"] == "Y"
		)
		{
			?><script>
				BitrixMobile.LazyLoad.showImages();
			</script><?
		}

		if ($_REQUEST["empty_get_comments"] == "Y")
		{
			$strCommentsText = ob_get_contents();
			ob_end_clean();

			echo CUtil::PhpToJSObject(array(
				"TEXT" => $strCommentsText,
				"POST_NUM_COMMENTS" => intval($arResult["Post"]["NUM_COMMENTS"])
			));
			die();
		}

		if ($arResult["is_ajax_post"] != "Y")
		{
				?><span id="post-comment-last-after"></span><?
			?></div><? // post-comments-wrap
		}
	}
	else
	{
		if ($_REQUEST["empty_get_comments"] == "Y")
		{
			$APPLICATION->RestartBuffer();

			$strCommentsText = "";
			ob_start();
			
			?><script>
				app.setPageID('BLOG_POST_<?=$arParams["ID"]?>');

				BX.message({
					SBPCurlToMore: '<?=CUtil::JSEscape($GLOBALS["APPLICATION"]->GetCurPageParam("last_comment_id=#comment_id#&comment_post_id=#post_id#&IFRAME=Y", array("last_comment_id", "comment_post_id", "IFRAME", "empty_get_form", "empty_get_comments")))?>',
					SBPCurlToNew: '<?=CUtil::JSEscape($arResult["urlToNew"])?>',
					SBPClogID: <?=intval($arParams["LOG_ID"])?>
				});
				commentVarDateTimeFormat = '<?=CUtil::JSEscape($arParams["DATE_TIME_FORMAT"])?>';

				<?
				if (strlen($strPullBlock) > 0)
				{
					?><?=$strPullBlock;?><?
				}
			?></script><?
			
			?><script bxrunfirst="true">
				var bCanUserComment = <?=($arResult["CanUserComment"] ? "true" : "false")?>;
			</script><?

			$strCommentsText = ob_get_contents();
			ob_end_clean();

			echo CUtil::PhpToJSObject(array(
				"TEXT" => $strCommentsText
			));

			die();
		}

		?><div class="post-comments-wrap" id="post-comments-wrap"><?
			?><span id="post-comment-last-after"></span>
		</div><?
	}
}

if ($arResult["is_ajax_post"] == "Y")
{
	$strBufferText = ob_get_contents();
	ob_end_clean();

	echo CUtil::PhpToJSObject(array(
		"TEXT" => $strBufferText,
		"COMMENT_ID" => (
			isset($arResult["ajax_comment"]) 
			&& intval($arResult["ajax_comment"]) > 0
				? intval($arResult["ajax_comment"])
				: 0
		)
	));

	require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');
	die();
}
?>