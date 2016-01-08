<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
include_once($_SERVER["DOCUMENT_ROOT"].SITE_TEMPLATE_PATH."/components/bitrix/socialnetwork.blog.post/mobile/functions.php");

if(!empty($arResult["Post"]))
{
	if ($_REQUEST["empty_get_comments"] == "Y")
	{
		$APPLICATION->IncludeComponent(
			"bitrix:socialnetwork.blog.post.comment",
			"mobile",
			Array(
				"PATH_TO_BLOG" => $arParams["PATH_TO_BLOG"],
				"PATH_TO_POST" => "/company/personal/user/#user_id#/blog/#post_id#/",
				"PATH_TO_POST_MOBILE" => $APPLICATION->GetCurPageParam("", array("LAST_LOG_TS", "empty_get_comments", "empty_get_form")),
				"PATH_TO_USER" => $arParams["PATH_TO_USER"],
				"PATH_TO_SMILE" => $arParams["PATH_TO_SMILE"],
				"PATH_TO_MESSAGES_CHAT" => $arParams["PATH_TO_MESSAGES_CHAT"],
				"ID" => $arResult["Post"]["ID"],
				"LOG_ID" => $arParams["LOG_ID"],
				"CACHE_TIME" => $arParams["CACHE_TIME"],
				"CACHE_TYPE" => $arParams["CACHE_TYPE"],
				"COMMENTS_COUNT" => "5",
				"DATE_TIME_FORMAT" => $arParams["DATE_TIME_FORMAT"],
				"USER_ID" => $GLOBALS["USER"]->GetID(),
				"SONET_GROUP_ID" => $arParams["SONET_GROUP_ID"],
				"NOT_USE_COMMENT_TITLE" => "Y",
				"USE_SOCNET" => "Y",
				"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
				"SHOW_LOGIN" => $arParams["SHOW_LOGIN"],
				"SHOW_YEAR" => $arParams["SHOW_YEAR"],
				"PATH_TO_CONPANY_DEPARTMENT" => $arParams["PATH_TO_CONPANY_DEPARTMENT"],
				"PATH_TO_VIDEO_CALL" => $arParams["PATH_TO_VIDEO_CALL"],
				"SHOW_RATING" => $arParams["SHOW_RATING"],
				"RATING_TYPE" => $arParams["RATING_TYPE"],
				"IMAGE_MAX_WIDTH" => $arParams["IMAGE_MAX_WIDTH"],
				"IMAGE_MAX_HEIGHT" => $arParams["IMAGE_MAX_HEIGHT"],
				"ALLOW_VIDEO"  => $arParams["ALLOW_VIDEO"],
				"ALLOW_IMAGE_UPLOAD" => $arParams["BLOG_COMMENT_ALLOW_IMAGE_UPLOAD"],
				"ALLOW_POST_CODE" => $arParams["ALLOW_POST_CODE"],
				"AJAX_POST" => "Y",
				"POST_DATA" => $arResult["PostSrc"],
				"BLOG_DATA" => $arResult["Blog"],
				"FROM_LOG" => ($arParams["IS_LIST"] ? "Y" : false),
				"bFromList" => ($arParams["IS_LIST"] ? true: false),
				"LAST_LOG_TS" => $arParams["LAST_LOG_TS"],
				"MARK_NEW_COMMENTS" => "Y",
				"AVATAR_SIZE" => $arParams["AVATAR_SIZE_COMMENT"],
				"MOBILE" => "Y",
				"ATTACHED_IMAGE_MAX_WIDTH_FULL" => 640,
				"ATTACHED_IMAGE_MAX_HEIGHT_FULL" => 832,
			),
		$component,
			array("HIDE_ICONS" => "Y")
		);

	}
	else
	{

		if (
			$_REQUEST["empty_get_form"] == "Y"
			|| !$arParams["IS_LIST"]
		)
		{
			$commentsFormBlock = "";
			ob_start();

			if(
				$_REQUEST["empty_get_form"] == "Y"
				|| IntVal($_REQUEST["comment_post_id"]) <= 0
			)
			{
				if (CMobile::getApiVersion() >= 4)
				{
					?><script>
						commentVarBlogPostID = <?=intval($arResult["Post"]["ID"])?>;
						commentVarURL = '<?=$GLOBALS["APPLICATION"]->GetCurPageParam("", array("sessid", "comment_post_id", "act", "post", "comment", "decode", "ACTION", "ENTITY_TYPE_ID", "ENTITY_ID", "empty_get_form", "empty_get_comments"))?>';
						commentVarLogID = <?=intval($arParams["LOG_ID"])?>;
						<?
						if (
							$_REQUEST["ACTION"] == "CONVERT"
							&& strlen($_REQUEST["ENTITY_TYPE_ID"]) > 0
							&& intval($_REQUEST["ENTITY_ID"]) > 0
						)
						{
							?>
							commentVarAction = 'CONVERT';
							commentVarEntityTypeID = '<?=CUtil::JSEscape($_REQUEST["ENTITY_TYPE_ID"])?>';
							commentVarEntityID = <?=intval($_REQUEST["ENTITY_ID"])?>;
							<?
						}
						else
						{
							?>
							commentVarAction = false;
							commentVarEntityTypeID = false;
							commentVarEntityID = false;
							<?
						}
						?>

						entryType = 'blog';

						oMSL.createCommentInputForm({
							placeholder: "<?=GetMessageJS("BLOG_C_ADD_TITLE")?>",
							button_name: "<?=GetMessageJS("BLOG_C_BUTTON_SEND")?>",
							useImageButton: true,
							action: function(text)
							{
								commonNativeInputCallback(text);
							}
						});
					</script><?
				}
				else
				{
					?><form class="send-message-block" id="comment_send_form" action="<?=POST_FORM_ACTION_URI?>" method="POST">
						<?=bitrix_sessid_post()?>
						<input type="hidden" name="comment_post_id" value="<?=intval($arResult["Post"]["ID"])?>">
						<input type="hidden" name="act" value="add">
						<input type="hidden" name="post" value="Y">
						<textarea id="comment_send_form_comment" class="send-message-input" placeholder="<?=GetMessage("BLOG_C_ADD_TITLE")?>"></textarea>
						<input type="button" id="comment_send_button" class="send-message-button" value="<?=GetMessage("BLOG_C_BUTTON_SEND")?>" ontouchstart="BX.toggleClass(this, 'send-message-button-press');" ontouchend="BX.toggleClass(this, 'send-message-button-press');">
					</form>
					<script>
					document.addEventListener("DOMContentLoaded", function() {
						BitrixMobile.Utils.autoResizeForm(
								document.getElementById("comment_send_form_comment"),
								document.getElementById("post-card-wrap")
						);
					}, false);

					BX.bind(BX('comment_send_button'), 'click', function(e)
					{
						if (BX('comment_send_form_comment').value.length > 0)
						{
							__MSLDisableSubmitButton(true);

							var data = {
								'sessid': '<?=bitrix_sessid()?>',
								'comment_post_id': <?=intval($arResult["Post"]["ID"])?>,
								'act': 'add',
								'post': 'Y',
								'comment': BX('comment_send_form_comment').value,
								'decode': 'Y'
								<?
								if (
									$_REQUEST["ACTION"] == "CONVERT"
									&& strlen($_REQUEST["ENTITY_TYPE_ID"]) > 0
									&& intval($_REQUEST["ENTITY_ID"]) > 0
								)
								{
									?>
									,'ACTION': 'CONVERT'
									,'ENTITY_TYPE_ID': '<?=CUtil::JSEscape($_REQUEST["ENTITY_TYPE_ID"])?>'
									,'ENTITY_ID': <?=intval($_REQUEST["ENTITY_ID"])?>
									<?
								}
								?>
							};

							var BMAjaxWrapper = new MobileAjaxWrapper;
							BMAjaxWrapper.Wrap({
								'type': 'html',
								'method': 'POST',
								'url': '<?=$GLOBALS["APPLICATION"]->GetCurPageParam("", array("sessid", "comment_post_id", "act", "post", "comment", "decode", "ACTION", "ENTITY_TYPE_ID", "ENTITY_ID"))?>',
								'data': data,
								'callback': function(response)
								{
									if (
										response != "*"
										&& response.length > 0
									)
									{
										oMSL.showNewComment({
											text: response,
											bClearForm: true,
											bShowImages: false,
											bIncrementCounters: true
										});
										BitrixMobile.Utils.resetAutoResize(BX("comment_send_form_comment"), BX("post-card-wrap"));
										__MSLDetailMoveBottom();
										__MSLDisableSubmitButton(false);

										var followBlock = BX("log_entry_follow_<?=intval($arParams["LOG_ID"])?>", true);

										if (followBlock)
										{
											var strFollowOld = (followBlock.getAttribute("data-follow") == "Y" ? "Y" : "N");
											if (strFollowOld == "N")
											{
												BX.removeClass(followBlock, 'post-item-follow');
												BX.addClass(followBlock, 'post-item-follow-active');
												followBlock.setAttribute("data-follow", "Y");
											}
										}
									}
									else
									{
										__MSLDisableSubmitButton(false);
									}
								},
								'callback_failure': function() { __MSLDisableSubmitButton(false); }
							});
						}
					});
					</script><?
				}
			}

			$commentsFormBlock = ob_get_contents();
			ob_end_clean();
		}

		if ($_REQUEST["empty_get_form"] == "Y")
		{
			$APPLICATION->RestartBuffer();
			echo $commentsFormBlock;
			die();
		}

		if (!$arParams["IS_LIST"] && CMobile::getApiVersion() < 4 && CMobile::getPlatform() != "android")
		{
			?><div class="post-card-wrap" id="post-card-wrap" onclick=""><?
		}

		$item_class = (!$arParams["IS_LIST"] ? "post-wrap" : "lenta-item".($arParams["IS_UNREAD"] ? " lenta-item-new" : ""));

		if ($arParams["IS_LIST"])
		{
			?><script type="text/javascript">
				arLogTs.entry_<?=intval($arParams["LOG_ID"])?> = <?=intval($arParams["LAST_LOG_TS"] -  CTimeZone::GetOffset())?>;
			</script><?
		}
		else
		{
			?><script>
				BX.ready(function()
				{
					BX.MSL.viewImageBind(
						'lenta_item_<?=intval($arParams["LOG_ID"])?>',
						{
							tag: 'IMG',
							attr: 'data-bx-image'
						}
					);

					oMSL.InitDetail({
						commentsType: 'blog',
						detailPageId: 'blog_' + <?=$arResult["Post"]["ID"]?>,
						logId: <?=intval($arParams["LOG_ID"])?>,
						entityXMLId: 'BLOG_<?=intval($arResult["Post"]["ID"])?>',
						bUseFollow: <?=($arParams["USE_FOLLOW"] == 'N' ? 'false' : 'true')?>,
						bFollow: <?=($arParams["FOLLOW"] == 'N' ? 'false' : 'true')?>,
						feed_id: parseInt(Math.random() * 100000),
						entryParams: {
							destinations: <?=CUtil::PhpToJSObject($arResult["Post"]["SPERM"])?>,
							post_perm: '<?=CUtil::JSEscape($arResult["PostPerm"])?>',
							post_id: <?=intval($arResult["Post"]["ID"])?>
						}
					});
				});	
			</script><?
		}

		?><div class="<?=($item_class)?>" id="lenta_item_<?=intval($arParams["LOG_ID"])?>"><?
			?><div 
				id="post_item_top_wrap_<?=intval($arParams["LOG_ID"])?>" 
				class="post-item-top-wrap<?=($arParams["FOLLOW_DEFAULT"] == "N" && $arParams["FOLLOW"] == "Y" ? " post-item-follow" : "")?>"
			><?
				?><div class="post-item-top" id="post_item_top_<?=intval($arParams["LOG_ID"])?>"><?
					$avatarId = "post_item_avatar_".intval($arParams["LOG_ID"]);
					?><div class="avatar" id="<?=$avatarId?>" <?if(strlen($arResult["arUser"]["PERSONAL_PHOTO_resized"]["src"]) > 0):?> data-src="<?=$arResult["arUser"]["PERSONAL_PHOTO_resized"]["src"]?>"<?endif?>></div><?

					if(strlen($arResult["arUser"]["PERSONAL_PHOTO_resized"]["src"]) > 0)
					{
						?><script>BitrixMobile.LazyLoad.registerImage("<?=$avatarId?>");</script><?
					}

					?><div class="post-item-top-cont"><?
						$anchor_id = $arResult["Post"]["ID"];
						$arTmpUser = array(
								"NAME" => $arResult["arUser"]["~NAME"],
								"LAST_NAME" => $arResult["arUser"]["~LAST_NAME"],
								"SECOND_NAME" => $arResult["arUser"]["~SECOND_NAME"],
								"LOGIN" => $arResult["arUser"]["~LOGIN"],
								"NAME_LIST_FORMATTED" => "",
							);
						?><a class="post-item-top-title" href="<?=$arResult["arUser"]["url"]?>"><?=CUser::FormatName($arParams["NAME_TEMPLATE"], $arTmpUser, ($arParams["SHOW_LOGIN"] != "N" ? true : false))?></a><?

						$strTopic = "";

						if(!empty($arResult["Post"]["SPERM"]))
						{
							$strTopic .= '<span class="post-item-top-arrow">'.GetMessage("BLOG_MOBILE_DESTINATION").'</span>';

							$cnt = count($arResult["Post"]["SPERM"]["U"]) + count($arResult["Post"]["SPERM"]["SG"]) + count($arResult["Post"]["SPERM"]["DR"]);
							$i = 0;

							if(!empty($arResult["Post"]["SPERM"]["U"]))
							{
								foreach($arResult["Post"]["SPERM"]["U"] as $id => $val)
								{
									$i++;
									if ($i == 4)
									{
										$more_cnt = $cnt + intval($arResult["Post"]["SPERM_HIDDEN"]) - 3;
										$suffix = (
											($more_cnt % 100) > 10 
											&& ($more_cnt % 100) < 20 
												? 5 
												: $more_cnt % 10
										);

										$moreClick = " onclick=\"showHiddenDestination('".$arResult["Post"]["ID"]."', this)\"";
										$strTopic .= "&nbsp;<span class=\"post-destination-more\"".$moreClick." ontouchstart=\"BX.toggleClass(this, 'post-destination-more-touch');\" ontouchend=\"BX.toggleClass(this, 'post-destination-more-touch');\">".GetMessage("BLOG_DESTINATION_MORE_".$suffix, Array("#NUM#" => $more_cnt))."</span><span id=\"blog-destination-hidden-".$arResult["Post"]["ID"]."\" style=\"display:none;\">";
									}

									$strTopic .= ($i != 1 ? ", " : " ").($val["NAME"] != "All" ? '<a href="'.$val["URL"].'" class="post-item-destination post-item-dest-users">'.$val["NAME"].'</a>' : '<span class="post-item-destination post-item-dest-all-users">'.GetMessage("BLOG_DESTINATION_ALL").'</span>');
								}
							}

							if(!empty($arResult["Post"]["SPERM"]["SG"]))
							{
								foreach($arResult["Post"]["SPERM"]["SG"] as $id => $val)
								{
									$i++;
									if ($i == 4)
									{
										$more_cnt = $cnt + intval($arResult["Post"]["SPERM_HIDDEN"]) - 3;
										$suffix = (
											($more_cnt % 100) > 10
											&& ($more_cnt % 100) < 20
												? 5
												: ($more_cnt % 10)
										);

										$moreClick = " onclick=\"showHiddenDestination('".$arResult["Post"]["ID"]."', this)\"";
										$strTopic .= "&nbsp;<span class=\"post-destination-more\"".$moreClick." ontouchstart=\"BX.toggleClass(this, 'post-destination-more-touch');\" ontouchend=\"BX.toggleClass(this, 'post-destination-more-touch');\">".GetMessage("BLOG_DESTINATION_MORE_".$suffix, Array("#NUM#" => $more_cnt))."</span><span id=\"blog-destination-hidden-".$arResult["Post"]["ID"]."\" style=\"display:none;\">";
									}

									$strTopic .= ($i != 1 ? ", " : " ").'<a href="'.$val["URL"].'" class="post-item-destination post-item-dest-sonetgroups">'.$val["NAME"].'</a>';
								}
							}

							if(!empty($arResult["Post"]["SPERM"]["DR"]))
							{
								foreach($arResult["Post"]["SPERM"]["DR"] as $id => $val)
								{
									$i++;
									if($i == 4)
									{
										$more_cnt = $cnt + intval($arResult["Post"]["SPERM_HIDDEN"]) - 3;
										$suffix = (
											($more_cnt % 100) > 10
											&& ($more_cnt % 100) < 20 
												? 5 
												: $more_cnt % 10
										);

										$moreClick = " onclick=\"showHiddenDestination('".$arResult["Post"]["ID"]."', this)\"";
										$strTopic .= "&nbsp;<span class=\"post-destination-more\"".$moreClick." ontouchstart=\"BX.toggleClass(this, 'post-destination-more-touch');\" ontouchend=\"BX.toggleClass(this, 'post-destination-more-touch');\">".GetMessage("BLOG_DESTINATION_MORE_".$suffix, Array("#NUM#" => $more_cnt))."</span><span id=\"blog-destination-hidden-".$arResult["Post"]["ID"]."\" style=\"display:none;\">";
									}

									$strTopic .= ($i != 1 ? ", " : " ").'<span class="post-item-destination post-item-dest-department">'.$val["NAME"].'</span>';
								}
							}

							if (
								isset($arResult["Post"]["SPERM_HIDDEN"])
								&& intval($arResult["Post"]["SPERM_HIDDEN"]) > 0
							)
							{
								$suffix = (
									($arResult["Post"]["SPERM_HIDDEN"] % 100) > 10 
									&& ($arResult["Post"]["SPERM_HIDDEN"] % 100) < 20
										? 5 
										: ($arResult["Post"]["SPERM_HIDDEN"] % 10)
									);
								$strTopic .= "&nbsp;".GetMessage("BLOG_DESTINATION_HIDDEN_".$suffix, Array("#NUM#" => intval($arResult["Post"]["SPERM_HIDDEN"])));
							}
						}

						?><div class="post-item-top-topic"><?=$strTopic ?></div><?
						
						$timestamp = MakeTimeStamp($arResult["Post"]["DATE_PUBLISH"]);
						$timeFormated = FormatDate(GetMessage("BLOG_MOBILE_FORMAT_TIME"), $timestamp);
						$dateTimeFormated = (strlen($arParams["DATE_TIME_FORMAT_FROM_LOG"]) <= 0 
							? __SMLFormatDate($timestamp)
							: FormatDate(
								(
									$arParams["DATE_TIME_FORMAT_FROM_LOG"] == "FULL" 
										? $GLOBALS["DB"]->DateFormatToPHP(str_replace(":SS", "", FORMAT_DATETIME)) 
										: $arParams["DATE_TIME_FORMAT_FROM_LOG"]
								),
								$timestamp
							)
						);

						if (
							strcasecmp(LANGUAGE_ID, 'EN') 
							!== 0 && strcasecmp(LANGUAGE_ID, 'DE') !== 0
						)
						{
							$dateTimeFormated = ToLower($dateTimeFormated);
						}

						// strip current year
						if (
							!empty($arParams["DATE_TIME_FORMAT_FROM_LOG"]) 
							&& (
								$arParams["DATE_TIME_FORMAT_FROM_LOG"] == "j F Y G:i" 
								|| $arParams["DATE_TIME_FORMAT_FROM_LOG"] == "j F Y g:i a"
							)
						)
						{
							$dateTimeFormated = ltrim($dateTimeFormated, "0");
							$curYear = date("Y");
							$dateTimeFormated = str_replace(array("-".$curYear, "/".$curYear, " ".$curYear, ".".$curYear), "", $dateTimeFormated);
						}

						$datetime_list = (ConvertTimeStamp($timestamp, "SHORT") == ConvertTimeStamp() ? $timeFormated : $dateTimeFormated);

						$arFormat = Array(
							"tommorow" => "tommorow, ".GetMessage("BLOG_MOBILE_FORMAT_TIME"),
							"today" => "today, ".GetMessage("BLOG_MOBILE_FORMAT_TIME"),
							"yesterday" => "yesterday, ".GetMessage("BLOG_MOBILE_FORMAT_TIME"),
							"" => (date("Y", $timestamp) == date("Y") ? GetMessage("BLOG_MOBILE_FORMAT_DATE") : GetMessage("BLOG_MOBILE_FORMAT_DATE_YEAR"))
						);
						$datetime_detail = FormatDate($arFormat, $timestamp);

						?><div class="lenta-item-time" id="datetime_block_detail"><?=$datetime_detail?></div><?

					?></div><?
					?><div class="lenta-item-right-corner"><?
						if (
							!isset($arParams["USE_FAVORITES"])
							|| $arParams["USE_FAVORITES"] != "N"
						)
						{
							$bFavorites = (array_key_exists("FAVORITES", $arParams) && $arParams["FAVORITES"] == "Y");
							?><div id="log_entry_favorites_<?=$arParams["LOG_ID"]?>" data-favorites="<?=($bFavorites ? "Y" : "N")?>" class="lenta-item-fav<?=($bFavorites ? " lenta-item-fav-active" : "")?>" onclick="__MSLSetFavorites(<?=$arParams["LOG_ID"]?>, this, '<?=($bFavorites ? "N" : "Y")?>'); return BX.PreventDefault(this);"></div><?
						}
						else
						{
							?><div class="lenta-item-fav-placeholder"></div><?
						}
					?></div><?
				?></div><?

				$arOnClickParams = array(
					"path" => $arParams["PATH_TO_LOG_ENTRY_EMPTY"],
					"log_id" => intval($arParams["LOG_ID"]),
					"entry_type" => "blog",
					"use_follow" => ($arParams["USE_FOLLOW"] == 'N' ? 'N' : 'Y'),
					"post_perm" => $arResult["PostPerm"],
					"destinations" => $arResult["Post"]["SPERM"],
					"post_id" => intval($arResult["Post"]["ID"]),
					"post_url" => str_replace("#log_id#", $arParams["LOG_ID"], $arParams["PATH_TO_LOG_ENTRY"]),
					"entity_xml_id" => "BLOG_".intval($arResult["Post"]["ID"]),
					"focus_form" => false,
					"show_full" => false
				);

				$strOnClickParams = CUtil::PhpToJSObject($arOnClickParams);

				$post_item_style = (!$arParams["IS_LIST"] && $_REQUEST["show_full"] == "Y" ? "post-item-post-block-full" : "post-item-post-block");
				$strOnClick = ($arParams["IS_LIST"] ? " onclick=\"__MSLOpenLogEntryNew(".$strOnClickParams.", event);\"" : "");

				if ($arParams["EVENT_ID"] == "blog_post_important")
				{
					$post_item_style .= " lenta-info-block info-block-important";
				}

				?><div class="<?=$post_item_style?>"<?=$strOnClick?> id="post_block_check_cont_<?=$arParams["LOG_ID"]?>"><?

					if($arResult["Post"]["MICRO"] != "Y")
					{
						?><div class="post-text-title<?if($arParams["EVENT_ID"]=="blog_post_important"){?> lenta-important-block-title<?}?>" id="post_text_title_<?=intval($arParams["LOG_ID"])?>"><?=$arResult["Post"]["TITLE"]?></div><?
					}

					?><div class="post-item-text<?if($arParams["EVENT_ID"]=="blog_post_important"){?> lenta-important-block-text<?}?>" id="post_block_check_<?=intval($arParams["LOG_ID"])?>"><?=$arResult["Post"]["textFormated"]?></div><?
					if(!empty($arResult["images"]))
					{
						?><div class="post-item-attached-img-wrap"><?
							$jsIds = "";
							foreach($arResult["images"] as $val)
							{
								$id = "blog-post-attached-".strtolower(randString(5));
								$jsIds .= $jsIds !== "" ? ', "'.$id.'"' : '"'.$id.'"';
								?><div class="post-item-attached-img-block"><img class="post-item-attached-img" id="<?=$id?>" src="<?=CMobileLazyLoad::getBase64Stub()?>" data-src="<?=$val["small"]?>" data-bx-image="<?=$val["full"]?>" alt="" border="0"></div><?
							}
						?></div><script>BitrixMobile.LazyLoad.registerImages([<?=$jsIds?>], oMSL.checkVisibility);</script><?
					}

					if($arResult["POST_PROPERTIES"]["SHOW"] == "Y")
					{
						?><div class="post-item-attached-file-wrap" id="post_block_check_files_<?=$arParams["LOG_ID"]?>"><?
							$eventHandlerID = false;
							$eventHandlerID = AddEventHandler('main', 'system.field.view.file', '__blogUFfileShowMobile');
							foreach ($arResult["POST_PROPERTIES"]["DATA"] as $FIELD_NAME => $arPostField)
							{
								if(!empty($arPostField["VALUE"]))
								{
									?><?$APPLICATION->IncludeComponent(
										"bitrix:system.field.view",
										$arPostField["USER_TYPE"]["USER_TYPE_ID"],
										array(
											"arUserField" => $arPostField,
											"ACTION_PAGE" => str_replace("#log_id#", $arParams["LOG_ID"], $arParams["PATH_TO_LOG_ENTRY"]),
											"MOBILE" => "Y"
										), 
										null, 
										array("HIDE_ICONS"=>"Y")
									);?><?
								}
							}
							if (
								$eventHandlerID !== false 
								&& (intval($eventHandlerID) > 0)
							)
							{
								RemoveEventHandler('main', 'system.field.view.file', $eventHandlerID);
							}
						?></div><?
					}

					if (!empty($arResult["GRATITUDE"]))
					{
						?><div class="lenta-info-block lenta-block-grat"><?
							?><div class="lenta-block-grat-medal<?=(strlen($arResult["GRATITUDE"]["TYPE"]["XML_ID"]) > 0 ? " lenta-block-grat-medal-".$arResult["GRATITUDE"]["TYPE"]["XML_ID"] : "")?>"></div><?
							?><div class="lenta-block-grat-users"><?
								$jsIds = "";
								foreach($arResult["GRATITUDE"]["USERS_FULL"] as $arGratUser)
								{
									$avatarId = "lenta-block-grat-".randString(5);
									if($arGratUser["AVATAR_SRC"])
									{
										$jsIds .= $jsIds !== "" ? ', "'.$avatarId.'"' : '"'.$avatarId.'"';
									}
									?><div class="lenta-block-grat-user">
										<div class="lenta-new-grat-avatar">
											<div class="avatar" id="<?=$avatarId?>"<?if($arGratUser["AVATAR_SRC"]):?> data-src="<?=$arGratUser["AVATAR_SRC"]?>"<?endif?>></div>
										</div>
										<div class="lenta-info-block-content">
											<div class="lenta-important-block-title"><a href="<?=$arGratUser["URL"]?>"><?=CUser::FormatName($arParams["NAME_TEMPLATE"], $arGratUser)?></a></div>
											<div class="lenta-important-block-text"><?=htmlspecialcharsbx($arGratUser["WORK_POSITION"])?></div>
										</div>
									</div><?
								}
							?></div><?
						?></div><?if(strlen($jsIds) > 0):?><script>BitrixMobile.LazyLoad.registerImages([<?=$jsIds?>]);</script><?endif?><?
					}
					
					?><div class="post-more-block" id="post_more_block_<?=$arParams["LOG_ID"]?>"></div><?

				?></div><? // post-item-post-block, post_block_check_cont_..

				if ($arResult["is_ajax_post"] != "Y")
				{
					ob_start();
				}

				if ($arParams["SHOW_RATING"] == "Y")
				{
					?><span class="bx-ilike-block" id="rating_block_<?=$arParams["LOG_ID"]?>" data-counter="<?=intval($arResult["RATING"][$arResult["Post"]["ID"]]["TOTAL_VOTES"])?>" style="display: none;"><?
						$bRatingExtended = (
							CModule::IncludeModule("mobileapp") 
								? (CMobile::getApiVersion() >= 2) 
								: (intval($GLOBALS["APPLICATION"]->GetPageProperty("api_version")) >= 2)
						);

						$arResultVote = $APPLICATION->IncludeComponent(
							"bitrix:rating.vote",
							"mobile_like",
							Array(
								"ENTITY_TYPE_ID" => "BLOG_POST",
								"ENTITY_ID" => $arResult["Post"]["ID"],
								"OWNER_ID" => $arResult["Post"]["AUTHOR_ID"],
								"USER_VOTE" => $arResult["RATING"][$arResult["Post"]["ID"]]["USER_VOTE"],
								"USER_HAS_VOTED" => $arResult["RATING"][$arResult["Post"]["ID"]]["USER_HAS_VOTED"],
								"TOTAL_VOTES" => $arResult["RATING"][$arResult["Post"]["ID"]]["TOTAL_VOTES"],
								"TOTAL_POSITIVE_VOTES" => $arResult["RATING"][$arResult["Post"]["ID"]]["TOTAL_POSITIVE_VOTES"],
								"TOTAL_NEGATIVE_VOTES" => $arResult["RATING"][$arResult["Post"]["ID"]]["TOTAL_NEGATIVE_VOTES"],
								"TOTAL_VALUE" => $arResult["RATING"][$arResult["Post"]["ID"]]["TOTAL_VALUE"],
								"PATH_TO_USER_PROFILE" => $arParams["~PATH_TO_USER"],
								"IS_RATING_EXTENDED" => $bRatingExtended,
								"EXTENDED" => "Y",
								"IS_LIST" => $arParams["IS_LIST"],
								"LOG_ID" => $arParams["LOG_ID"],
								"VOTE_RAND" => (!$arParams["IS_LIST"] && intval($_REQUEST["LIKE_RANDOM_ID"]) > 0 ? intval($_REQUEST["LIKE_RANDOM_ID"]) : false)
							),
							$component,
							array("HIDE_ICONS" => "Y")
						);

						$bRatingExtendedOpen = (
							$bRatingExtended
							&& intval($arResult["RATING"][$arResult["Post"]["ID"]]["TOTAL_VOTES"]) > 0
						);

						if (
							$arParams["IS_LIST"] == "Y"
							&& intval($arResultVote["VOTE_RAND"]) > 0
						)
						{
							?><script type="text/javascript">
								arLikeRandomID.entry_<?=intval($arParams["LOG_ID"])?> = <?=intval($arResultVote["VOTE_RAND"])?>;
							</script><?
						}
					?></span><? // rating_block

					if ($arResultVote)
					{
						ob_start(); // rating buffer

						?><span id="rating_button_<?=$arParams["LOG_ID"]?>"><?
							?><div class="post-item-informers post-item-inform-likes<?=($arResultVote['USER_HAS_VOTED'] == 'Y' ? '-active' : '')?>" id="bx-ilike-button-<?=CUtil::JSEscape(htmlspecialcharsbx($arResultVote['VOTE_ID']))?>"><?

								if (
									intval($arResultVote["TOTAL_VOTES"]) > 1
									|| (
										intval($arResultVote["TOTAL_VOTES"]) == 1
										&& $arResultVote['USER_HAS_VOTED'] == "N"
									)
								)
								{
									?><div class="post-item-inform-left"><?=GetMessage("BLOG_MOBILE_LIKE2_ACTION")?></div><?
									?><div class="post-item-inform-right"><span class="post-item-inform-right-text"><?=htmlspecialcharsEx($arResultVote["TOTAL_VOTES"])?></span></div><?
								}
								else
								{
									?><div class="post-item-inform-left"><?=GetMessage("BLOG_MOBILE_LIKE_ACTION")?></div><?
									?><div class="post-item-inform-right" style="display: none;"><span class="post-item-inform-right-text"><?=htmlspecialcharsEx($arResultVote["TOTAL_VOTES"])?></span></div><?
								}

							?></div><?
						?></span><?

						$strRatingActionBlock = ob_get_contents();
						ob_end_clean(); // -- rating buffer
					}
				}

				if (
					$strRatingActionBlock
					&& strlen($strRatingActionBlock) > 0
				)
				{
					?><?=$strRatingActionBlock?><?
				}

				if ($arResult["Post"]["ENABLE_COMMENTS"] == "Y")
				{
					$bHasComments = true;

					if ($arResult["is_ajax_post"] != "Y")
					{
						ob_start(); // inner buffer
					}

					if ($arResult["GetCommentsOnly"])
					{
						$APPLICATION->RestartBuffer();
					}

					$arCommentsResult = $APPLICATION->IncludeComponent(
						"bitrix:socialnetwork.blog.post.comment",
						"mobile",
						Array(
							"PATH_TO_BLOG" => $arParams["PATH_TO_BLOG"],
							"PATH_TO_POST" => "/company/personal/user/#user_id#/blog/#post_id#/",
							"PATH_TO_POST_MOBILE" => $APPLICATION->GetCurPageParam("", array("LAST_LOG_TS")),
							"PATH_TO_USER" => $arParams["PATH_TO_USER"],
							"PATH_TO_SMILE" => $arParams["PATH_TO_SMILE"],
							"PATH_TO_MESSAGES_CHAT" => $arParams["PATH_TO_MESSAGES_CHAT"],
							"ID" => $arResult["Post"]["ID"],
							"LOG_ID" => $arParams["LOG_ID"],
							"CACHE_TIME" => $arParams["CACHE_TIME"],
							"CACHE_TYPE" => $arParams["CACHE_TYPE"],
							"COMMENTS_COUNT" => "5",
							"DATE_TIME_FORMAT" => $arParams["DATE_TIME_FORMAT"],
							"USER_ID" => $GLOBALS["USER"]->GetID(),
							"SONET_GROUP_ID" => $arParams["SONET_GROUP_ID"],
							"NOT_USE_COMMENT_TITLE" => "Y",
							"USE_SOCNET" => "Y",
							"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
							"SHOW_LOGIN" => $arParams["SHOW_LOGIN"],
							"SHOW_YEAR" => $arParams["SHOW_YEAR"],
							"PATH_TO_CONPANY_DEPARTMENT" => $arParams["PATH_TO_CONPANY_DEPARTMENT"],
							"PATH_TO_VIDEO_CALL" => $arParams["PATH_TO_VIDEO_CALL"],
							"SHOW_RATING" => $arParams["SHOW_RATING"],
							"RATING_TYPE" => $arParams["RATING_TYPE"],
							"IMAGE_MAX_WIDTH" => $arParams["IMAGE_MAX_WIDTH"],
							"IMAGE_MAX_HEIGHT" => $arParams["IMAGE_MAX_HEIGHT"],
							"ALLOW_VIDEO"  => $arParams["ALLOW_VIDEO"],
							"ALLOW_IMAGE_UPLOAD" => $arParams["BLOG_COMMENT_ALLOW_IMAGE_UPLOAD"],
							"ALLOW_POST_CODE" => $arParams["ALLOW_POST_CODE"],
							"AJAX_POST" => "Y",
							"POST_DATA" => $arResult["PostSrc"],
							"BLOG_DATA" => $arResult["Blog"],
							"FROM_LOG" => ($arParams["IS_LIST"] ? "Y" : false),
							"bFromList" => ($arParams["IS_LIST"] ? true: false),
							"LAST_LOG_TS" => $arParams["LAST_LOG_TS"],
							"AVATAR_SIZE" => $arParams["AVATAR_SIZE_COMMENT"],
							"MOBILE" => "Y",
							"ATTACHED_IMAGE_MAX_WIDTH_FULL" => 640,
							"ATTACHED_IMAGE_MAX_HEIGHT_FULL" => 832,
						),
						$component,
						array("HIDE_ICONS" => "Y")
					);

					$strCommentsBlock = (!$arParams["IS_LIST"] ? ob_get_contents() : "");
					ob_end_clean(); // inner buffer

					if ($arResult["GetCommentsOnly"])
					{
						?><?=$strCommentsBlock?><?
						die();
					}

					if (!$arParams["IS_LIST"]) // detail, non-empty
					{
						?><div id="comments_control_<?=$arParams["LOG_ID"]?>" class="post-item-informers post-item-inform-comments"><?
							?><div class="post-item-inform-left"><?
								?><?=GetMessage('BLOG_MOBILE_COMMENTS_ACTION')?><?
							?></div><?
						?></div><?
					}
					else
					{
						$arOnClickParamsCommentsForm = $arOnClickParams;
						$arOnClickParamsCommentsForm["focus_form"] = true;
						$arOnClickParamsCommentsForm["show_full"] = false;
						$strOnClickParamsCommentsForm = CUtil::PhpToJSObject($arOnClickParamsCommentsForm);
						$strOnClickCommentsForm = ($arParams["IS_LIST"] ? " onclick=\"__MSLOpenLogEntryNew(".$strOnClickParamsCommentsForm.", event);\"" : " onclick=\"__MSLDetailMoveBottom();\"");

						$arOnClickParamsCommentsTop = $arOnClickParams;
						$arOnClickParamsCommentsTop["focus_comments"] = true;
						$arOnClickParamsCommentsTop["show_full"] = false;
						$strOnClickParamsCommentsTop = CUtil::PhpToJSObject($arOnClickParamsCommentsTop);
						$strOnClickCommentsTop = ($arParams["IS_LIST"] ? " onclick=\"__MSLOpenLogEntryNew(".$strOnClickParamsCommentsTop.", event);\"" : "");

						?><div id="comments_control_<?=$arParams["LOG_ID"]?>" class="post-item-informers post-item-inform-comments"<?=$strOnClickCommentsTop?>><?
							$num_comments = (isset($arParams["COMMENTS_COUNT"]) ? $arParams["COMMENTS_COUNT"] : intval($arResult["Post"]["NUM_COMMENTS"]));
							?><div class="post-item-inform-left" id="informer_comments_text2_<?=$arParams["LOG_ID"]?>" style="display: <?=($num_comments > 0 ? "inline-block" : "none")?>;"><?
								?><?=GetMessage('BLOG_MOBILE_COMMENTS_2')?></div><?
							?><?
							?><div class="post-item-inform-left" id="informer_comments_text_<?=$arParams["LOG_ID"]?>" style="display: <?=($num_comments <= 0 ? "inline-block" : "none")?>;"><?
								?><?=GetMessage('BLOG_MOBILE_COMMENTS')?><?
							?></div><?

							?><div class="post-item-inform-right" id="informer_comments_<?=$arParams["LOG_ID"]?>" style="display: <?=($num_comments > 0 ? 'inline-block' : 'none')?>;"><?
								if (
									(
										$arParams["USE_FOLLOW"] != "Y" 
										|| $arParams["FOLLOW"] == "Y"
									)
									&& intval($arCommentsResult["newCountWOMark"]) > 0
								)
								{
									?><span id="informer_comments_all_<?=$arParams["LOG_ID"]?>"><?
										$old_comments = intval(abs($num_comments - intval($arCommentsResult["newCountWOMark"])));
										?><?=($old_comments > 0 ? $old_comments : '')?><?
									?></span><?
									?><span id="informer_comments_new_<?=$arParams["LOG_ID"]?>" class="post-item-inform-right-new"><?
										?><span class="post-item-inform-right-new-sign">+</span><?
										?><span class="post-item-inform-right-new-value"><?=intval($arCommentsResult["newCountWOMark"])?></span><?
									?></span><?
								}
								else
								{
									?><?=$num_comments?><?
								}
							?></div><?
						?></div><?
					}
				}
				else
				{
					$bHasComments = false;
				}

				if ($bHasComments)
				{
					?><div id="log_entry_follow_<?=intval($arParams["LOG_ID"])?>" data-follow="<?=($arParams["FOLLOW"] == "Y" ? "Y" : "N")?>" style="display: none;"></div><?
				}

				if ($arParams["IS_LIST"])
				{
					$arOnClickParams["show_full"] = true;
					$strOnClickParams = CUtil::PhpToJSObject($arOnClickParams);
					$strOnClickMore = ' onclick="__MSLOpenLogEntryNew('.$strOnClickParams.', event);"';
				}
				else
				{
					$strOnClickMore = " onclick=\"oMSL.expandText(".intval($arParams["LOG_ID"]).");\"";
				}

				?><a id="post_more_limiter_<?=intval($arParams["LOG_ID"])?>" <?=$strOnClickMore?> class="post-item-more" ontouchstart="this.classList.toggle('post-item-more-pressed')" ontouchend="this.classList.toggle('post-item-more-pressed')" style="display: none;"><?
					?><?=GetMessage("BLOG_LOG_EXPAND")?><?
				?></a><?

				?><script>
					arCanUserComment[<?=$arParams["LOG_ID"]?>] = <?=($arCommentsResult && $arCommentsResult["CanUserComment"] ? "true" : "false")?>;
				</script><?

				$strBottomBlock = ob_get_contents();
				ob_end_clean(); // outer buffer

				if (strlen($strBottomBlock) > 0)
				{
					?><div id="post_item_inform_wrap" class="post-item-inform-wrap"><?
						?><?=$strBottomBlock;?><?
					?></div><?
				}

				if ($bRatingExtended)
				{
					?><div 
						class="post-item-inform-wrap-tree" 
						id="<?=(!$arParams["IS_LIST"] ? 'rating-footer-wrap' : 'rating-footer-wrap_'.intval($arParams["LOG_ID"]))?>" 
						style="display: <?=(!$arParams["IS_LIST"] && $bRatingExtendedOpen ? "block" : "none")?>;"
					><?
						?><div class="post-item-inform-footer" id="rating-footer_<?=intval($arParams["LOG_ID"])?>"><?
							if (
								$arResultVote 
								&& CMobile::getApiVersion() >= 2
							)
							{
								$me = '<span class="post-strong">'.GetMessage("BLOG_MOBILE_LIKE_ME").'</span>';

								$bHasVotes = intval($arResultVote["TOTAL_VOTES"]) > 0;
								$bOnlyYou = (
									intval($arResultVote["TOTAL_VOTES"]) == 1
									&& $arResultVote["USER_HAS_VOTED"] == "Y"
								);

								?><div class="rating-footer-you" id="bx-ilike-list-you" style="display: <?=($bOnlyYou ? "block" : "none")?>"><?=str_replace("#YOU#", $me, GetMessage("BLOG_MOBILE_LIKE_YOU"))?></div><?

								$count = ($arResultVote["USER_HAS_VOTED"] == "Y" ? intval($arResultVote["TOTAL_VOTES"]) - 1 : intval($arResultVote["TOTAL_VOTES"]));
								$reminder = $count % 10;
								$users_title = GetMessage("BLOG_MOBILE_LIKE_USERS_".($reminder == 1 ? "1" : "2"));

								$count_users = '<span class="post-strong">'.
									str_replace(
										array(
											"#COUNT#",
											"#USERS#"
										),
										array(
											'<span class="rating-footer-others-count">'.$count.'</span>',
											'<span class="rating-footer-others-users-title">'.$users_title.'</span>'
										),
										GetMessage("BLOG_MOBILE_LIKE_COUNT_USERS")
									).'</span>';

								?><div class="rating-footer-youothers" id="bx-ilike-list-youothers" style="display: <?=($bHasVotes && !$bOnlyYou && $arResultVote["USER_HAS_VOTED"] == "Y" ? "block" : "none")?>" onclick="RatingLike.List('<?=CUtil::JSEscape(htmlspecialcharsbx($arResultVote['VOTE_ID']))?>');"><?
									?><?=str_replace(array("#YOU#", "#COUNT_USERS#"), array($me, $count_users), GetMessage("BLOG_MOBILE_LIKE_YOU_OTHERS"))?><?
								?></div><?
								?><div class="rating-footer-others" id="bx-ilike-list-others" style="display: <?=($bHasVotes && $arResultVote["USER_HAS_VOTED"] == "Y" ? "none" : "block")?>" onclick="RatingLike.List('<?=CUtil::JSEscape(htmlspecialcharsbx($arResultVote['VOTE_ID']))?>');"><?
									?><?=str_replace("#COUNT_USERS#", $count_users, GetMessage("BLOG_MOBILE_LIKE_OTHERS"))?><?
								?></div><?
							}
						?></div><?
					?></div><?
				}
			?></div><? // post-item-top-wrap

			if (!$arParams["IS_LIST"])
			{
				if (
					$arResult["Post"]["ENABLE_COMMENTS"] == "Y"
					&& strlen($strCommentsBlock) > 0
				)
				{
					?><?=$strCommentsBlock?><?

					if (
						$GLOBALS["USER"]->IsAuthorized() 
						&& CModule::IncludeModule("pull") 
						&& CPullOptions::GetNginxStatus()
					)
					{
						CPullWatch::Add($GLOBALS["USER"]->GetID(), "UNICOMMENTSBLOG_".intval($arResult["Post"]["ID"]));
					}
				}			
			}

		?></div><? // post-wrap / lenta-item

		?><script type="text/javascript">
			BX.ready(function()
			{
				oMSL.arBlockToCheck[<?=$arParams["LOG_ID"]?>] = {
					lenta_item_id: 'lenta_item_<?=$arParams["LOG_ID"]?>',
					text_block_id: 'post_block_check_<?=$arParams["LOG_ID"]?>',
					title_block_id: 'post_block_check_title_<?=$arParams["LOG_ID"]?>',
					files_block_id: 'post_block_check_files_<?=$arParams["LOG_ID"]?>',
					more_overlay_id: 'post_more_block_<?=$arParams["LOG_ID"]?>',
					more_button_id: 'post_more_limiter_<?=$arParams["LOG_ID"]?>'
				};
			});
		</script><?

		if (!$arParams["IS_LIST"])
		{
			if (CMobile::getApiVersion() < 4 && CMobile::getPlatform() != "android")
			{
				?></div><? // post-card-wrap
			}

			// comments form block

			if ($arResult["GetCommentsFormOnly"])
			{
				$APPLICATION->RestartBuffer();
			}

			if ($arCommentsResult["CanUserComment"])
			{
				echo $commentsFormBlock;
			}

			if ($arResult["GetCommentsFormOnly"])
			{
				die();
			}
		}
	}
}
elseif(!$arResult["bFromList"])
{
	echo GetMessage("BLOG_BLOG_BLOG_NO_AVAIBLE_MES");
}
?>