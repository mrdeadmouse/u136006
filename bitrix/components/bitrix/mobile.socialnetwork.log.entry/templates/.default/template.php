<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (strlen($arResult["FatalError"]) > 0)
{
	?><span class='errortext'><?=$arResult["FatalError"]?></span><br /><br /><?
}
else
{
	if (strlen($arResult["ErrorMessage"]) > 0)
	{
		?><span class='errortext'><?=$arResult["ErrorMessage"]?></span><br /><br /><?
	}

	if (
		$arResult["Event"]
		&& is_array($arResult["Event"])
		&& !empty($arResult["Event"])
	)
	{
		$arEvent = $arResult["Event"];
		if (
			!$arParams["IS_LIST"] 
			&& CMobile::getApiVersion() < 4 
			&& CMobile::getPlatform() != "android"
		)
		{
			?><div class="post-card-wrap" id="post-card-wrap" onclick=""><?
		}

		$bUnread = $arParams["EVENT"]["IS_UNREAD"];

		$strTopic = "";
		if (
			isset($arEvent["EVENT_FORMATTED"]["DESTINATION"])
			&& is_array($arEvent["EVENT_FORMATTED"]["DESTINATION"])
			&& count($arEvent["EVENT_FORMATTED"]["DESTINATION"]) > 0
		)
		{
			if (
				array_key_exists("TITLE_24", $arEvent["EVENT_FORMATTED"])
				&& strlen($arEvent["EVENT_FORMATTED"]["TITLE_24"]) > 0
			)
			{
				$strTopic .= '<div class="post-item-top-text post-item-top-arrow'.(strlen($arEvent["EVENT_FORMATTED"]["STYLE"]) > 0 ? ' post-item-'.$arEvent["EVENT_FORMATTED"]["STYLE"] : '').'">'.$arEvent["EVENT_FORMATTED"]["TITLE_24"].' </div>';
			}

			$i = 0;
			foreach($arEvent["EVENT_FORMATTED"]["DESTINATION"] as $arDestination)
			{
				$strTopic .= ($i > 0 ? ', ' : ' ');

				if (!empty($arDestination["CRM_PREFIX"]))
				{
					$strTopic .= ' <span class="post-item-dest-crm-prefix">'.$arDestination["CRM_PREFIX"].':&nbsp;</span>';
				}

				if (strlen($arDestination["URL"]) > 0)
				{
					$strTopic .= '<a href="'.$arDestination["URL"].'" class="post-item-destination'.(strlen($arDestination["STYLE"]) > 0 ? ' post-item-dest-'.$arDestination["STYLE"] : '').'">'.$arDestination["TITLE"].'</a>';
				}
				else
				{
					$strTopic .= '<span class="post-item-destination'.(strlen($arDestination["STYLE"]) > 0 ? ' post-item-dest-'.$arDestination["STYLE"] : '').'">'.$arDestination["TITLE"].'</span>';
				}

				$i++;
			}

			if (intval($arEvent["EVENT_FORMATTED"]["DESTINATION_MORE"]) > 0)
			{
				$moreClick = " onclick=\"__MSLGetHiddenDestinations(".$arEvent["EVENT"]["ID"].", ".$arEvent["EVENT"]["USER_ID"].", this);\"";
				$strTopic .= "&nbsp;<span class=\"post-destination-more\"".$moreClick." ontouchstart=\"BX.toggleClass(this, 'post-destination-more-touch');\" ontouchend=\"BX.toggleClass(this, 'post-destination-more-touch');\">".str_replace("#COUNT#", $arEvent["EVENT_FORMATTED"]["DESTINATION_MORE"], GetMessage("MOBILE_LOG_DESTINATION_MORE"))."</span>";
			}
		}
		else
		{
			$strTopic .= (
				array_key_exists("TITLE_24", $arEvent["EVENT_FORMATTED"])
				&& strlen($arEvent["EVENT_FORMATTED"]["TITLE_24"]) > 0
					? '<div class="post-item-top-text'.(strlen($arEvent["EVENT_FORMATTED"]["STYLE"]) > 0 ? ' post-item-'.$arEvent["EVENT_FORMATTED"]["STYLE"] : '').'">'.$arEvent["EVENT_FORMATTED"]["TITLE_24"].'</div>'
					: '<div class="post-item-top-text'.(strlen($arEvent["EVENT_FORMATTED"]["STYLE"]) > 0 ? ' post-item-'.$arEvent["EVENT_FORMATTED"]["STYLE"] : '').'">'.$arEvent["EVENT_FORMATTED"]["TITLE"].'</div>'
			);
		}

		$strCreatedBy = "";
		if (
			array_key_exists("CREATED_BY", $arEvent)
			&& is_array($arEvent["CREATED_BY"])
		)
		{
			if (
				array_key_exists("TOOLTIP_FIELDS", $arEvent["CREATED_BY"])
				&& is_array($arEvent["CREATED_BY"]["TOOLTIP_FIELDS"])
			)
			{
				$strCreatedBy .= '<a class="post-item-top-title" href="'.str_replace(array("#user_id#", "#USER_ID#", "#id#", "#ID#"), $arEvent["CREATED_BY"]["TOOLTIP_FIELDS"]["ID"], $arEvent["CREATED_BY"]["TOOLTIP_FIELDS"]["PATH_TO_SONET_USER_PROFILE"]).'">'.CUser::FormatName($arParams["NAME_TEMPLATE"], $arEvent["CREATED_BY"]["TOOLTIP_FIELDS"], ($arParams["SHOW_LOGIN"] != "N" ? true : false)).'</a>';
			}
			elseif (
				array_key_exists("FORMATTED", $arEvent["CREATED_BY"])
				&& strlen($arEvent["CREATED_BY"]["FORMATTED"]) > 0
			)
			{
				$strCreatedBy .= '<div class="post-item-top-title">'.$arEvent["CREATED_BY"]["FORMATTED"].'</div>';
			}
		}
		elseif (
			in_array($arEvent["EVENT"]["EVENT_ID"], array("data", "news", "system"))
			&& array_key_exists("ENTITY", $arEvent)
		)
		{
			if (
				array_key_exists("TOOLTIP_FIELDS", $arEvent["ENTITY"])
				&& is_array($arEvent["ENTITY"]["TOOLTIP_FIELDS"])
			)
			{
				$strCreatedBy .= '<a class="post-item-top-title" href="'.str_replace(array("#user_id#", "#USER_ID#", "#id#", "#ID#"), $arEvent["ENTITY"]["TOOLTIP_FIELDS"]["ID"], $arEvent["ENTITY"]["TOOLTIP_FIELDS"]["PATH_TO_SONET_USER_PROFILE"]).'">'.CUser::FormatName($arParams["NAME_TEMPLATE"], $arEvent["ENTITY"]["TOOLTIP_FIELDS"], ($arParams["SHOW_LOGIN"] != "N" ? true : false)).'</a>';
			}
			elseif (
				array_key_exists("FORMATTED", $arEvent["ENTITY"])
				&& array_key_exists("NAME", $arEvent["ENTITY"]["FORMATTED"])
			)
			{
				$strCreatedBy .= '<div class="post-item-top-title">'.$arEvent["ENTITY"]["FORMATTED"]["NAME"].'</div>';
			}
		}

		$strDescription = "";
		if (
			array_key_exists("DESCRIPTION", $arEvent["EVENT_FORMATTED"])
			&& (
				(!is_array($arEvent["EVENT_FORMATTED"]["DESCRIPTION"]) && strlen($arEvent["EVENT_FORMATTED"]["DESCRIPTION"]) > 0)
				|| (is_array($arEvent["EVENT_FORMATTED"]["DESCRIPTION"]) && count($arEvent["EVENT_FORMATTED"]["DESCRIPTION"]) > 0)
			)
		)
		{
			$strDescription = '<div class="post-item-description'.(strlen($arEvent["EVENT_FORMATTED"]["DESCRIPTION_STYLE"]) > 0 ? ' post-item-description-'.$arEvent["EVENT_FORMATTED"]["DESCRIPTION_STYLE"].'"' : '').'">'.(is_array($arEvent["EVENT_FORMATTED"]["DESCRIPTION"]) ? '<span>'.implode('</span> <span>', $arEvent["EVENT_FORMATTED"]["DESCRIPTION"]).'</span>' : $arEvent["EVENT_FORMATTED"]["DESCRIPTION"]).'</div>';
		}

		if ($arParams["IS_LIST"])
		{
			?><script type="text/javascript">
				arLogTs.entry_<?=intval($arEvent["EVENT"]["ID"])?> = <?=intval($arResult["LAST_LOG_TS"])?>;
			</script><?
		}

		if ($arParams["IS_LIST"])
		{
			$bApiVersionIs2 = (
				CModule::IncludeModule("mobileapp") 
					? (CMobile::getApiVersion() >= 2) 
					: (intval($GLOBALS["APPLICATION"]->GetPageProperty("api_version")) >= 2)
			);

			$arOnClickParams = array(
				"path" => $arParams["PATH_TO_LOG_ENTRY_EMPTY"],
				"log_id" => intval($arEvent["EVENT"]["ID"]),
				"entry_type" => "non-blog",
				"use_follow" => ($arParams["USE_FOLLOW"] == 'N' ? 'N' : 'Y'),
				"site_id" => SITE_ID,
				"language_id" => LANGUAGE_ID,
				"datetime_format" => $arParams["DATE_TIME_FORMAT"],
				"entity_xml_id" => $arEvent["COMMENTS_PARAMS"]["ENTITY_XML_ID"],
				"focus_form" => false,
				"show_full" => in_array($arEvent["EVENT"]["EVENT_ID"], array("timeman_entry", "report", "calendar"))
			);

			if (
				isset($arEvent["EVENT"])
				&& isset($arEvent["EVENT"]["MODULE_ID"])
				&& ($arEvent["EVENT"]["MODULE_ID"] === "tasks")
				&& isset($arEvent["EVENT"]["EVENT_ID"])
				&& ($arEvent["EVENT"]["EVENT_ID"] === "tasks")
				&& isset($arEvent["EVENT"]["SOURCE_ID"])
				&& ($arEvent["EVENT"]["SOURCE_ID"] > 0)
			)
			{
				if ($bApiVersionIs2)
				{
					$strTaskPath = str_replace(
						array("__ROUTE_PAGE__", "#USER_ID#"),
						array("view", (int) $GLOBALS["USER"]->GetID()),
						$arParams["PATH_TO_TASKS_SNM_ROUTER"]
						."&TASK_ID=".(int)$arEvent["EVENT"]["SOURCE_ID"]
					);
					$strOnClick = " onclick=\"__MSLOpenLogEntry(".intval($arEvent["EVENT"]["ID"]).", '".$strTaskPath."', false, event);\"";
				}
			}
			elseif (
				isset($arEvent["EVENT"])
				&& isset($arEvent["EVENT"]["EVENT_ID"])
				&& ($arEvent["EVENT"]["EVENT_ID"] === "calendar")
				&& isset($arEvent["EVENT"]["SOURCE_ID"])
				&& ($arEvent["EVENT"]["SOURCE_ID"] > 0)
			)
			{
				if ($bApiVersionIs2)
				{
					$strEventPath = "/mobile/calendar/view_event.php?event_id=".intval($arEvent["EVENT"]["SOURCE_ID"]);
					$strOnClick = " onclick=\"__MSLOpenLogEntry(".intval($arEvent["EVENT"]["ID"]).", '".$strEventPath."', false, event);\"";
				}
			}
			else
			{
				$strOnClickParams = CUtil::PhpToJSObject($arOnClickParams);
				$strOnClick = " onclick=\"__MSLOpenLogEntryNew(".$strOnClickParams.", event);\"";
			}
		}
		else
		{
			$strOnClick = "";
		}

		$timestamp = $arEvent["LOG_DATE_TS"];

		$arFormat = Array(
			"tommorow" => "tommorow, ".GetMessage("MOBILE_LOG_COMMENT_FORMAT_TIME"),
			"today" => "today, ".GetMessage("MOBILE_LOG_COMMENT_FORMAT_TIME"),
			"yesterday" => "yesterday, ".GetMessage("MOBILE_LOG_COMMENT_FORMAT_TIME"),
			"" => (date("Y", $timestamp) == date("Y") ? GetMessage("MOBILE_LOG_COMMENT_FORMAT_DATE") : GetMessage("MOBILE_LOG_COMMENT_FORMAT_DATE_YEAR"))
		);
		$datetime_detail = FormatDate($arFormat, $timestamp);

		if (
			array_key_exists("EVENT_FORMATTED", $arEvent)
			&& array_key_exists("DATETIME_FORMATTED", $arEvent["EVENT_FORMATTED"])
			&& strlen($arEvent["EVENT_FORMATTED"]["DATETIME_FORMATTED"]) > 0
		)
		{
			$datetime_list = $arEvent["EVENT_FORMATTED"]["DATETIME_FORMATTED"];
		}
		elseif (
			array_key_exists("DATETIME_FORMATTED", $arEvent)
			&& strlen($arEvent["DATETIME_FORMATTED"]) > 0
		)
		{
			$datetime_list = $arEvent["DATETIME_FORMATTED"];
		}
		elseif ($arEvent["LOG_DATE_DAY"] == ConvertTimeStamp())
		{
			$datetime_list = $arEvent["LOG_TIME_FORMAT"];
		}
		else
		{
			$datetime_list = $arEvent["LOG_DATE_DAY"]." ".$arEvent["LOG_TIME_FORMAT"];
		}

		$bHasNoCommentsOrLikes = (
			(
				!array_key_exists("HAS_COMMENTS", $arEvent)
				|| $arEvent["HAS_COMMENTS"] != "Y"
			)
			&& (
				$arParams["SHOW_RATING"] != "Y"
				|| strlen($arEvent["RATING_TYPE_ID"]) <= 0
				|| intval($arEvent["RATING_ENTITY_ID"]) <= 0
			)
		);

		$item_class = (!$arParams["IS_LIST"] ? "post-wrap" : "lenta-item".($bUnread ? " lenta-item-new" : "")).($bHasNoCommentsOrLikes ? " post-without-informers" : "");

		?><div class="<?=($item_class)?>" id="lenta_item_<?=$arEvent["EVENT"]["ID"]?>"><?
			?><div 
				id="post_item_top_wrap_<?=$arEvent["EVENT"]["ID"]?>"
				class="post-item-top-wrap<?=($arParams["FOLLOW_DEFAULT"] == "N" && $arEvent["EVENT"]["FOLLOW"] == "Y" ? " post-item-follow" : "")?>"
			><?
				?><div class="post-item-top" id="post_item_top_<?=$arEvent["EVENT"]["ID"]?>"><?
					?><div class="avatar<?=(strlen($arEvent["EVENT_FORMATTED"]["AVATAR_STYLE"]) > 0 ? " ".$arEvent["EVENT_FORMATTED"]["AVATAR_STYLE"] : "")?>"<?=(strlen($arEvent["AVATAR_SRC"]) > 0 ? " style=\"background-image:url('".$arEvent["AVATAR_SRC"]."')\"" : "")?>></div><?
					?><div class="post-item-top-cont"><?
						?><div class="lenta-item-right-corner"><?
							if (
								!isset($arParams["USE_FAVORITES"])
								|| $arParams["USE_FAVORITES"] != "N"
							)
							{
								$bFavorites = (array_key_exists("FAVORITES", $arParams["EVENT"]) && $arParams["EVENT"]["FAVORITES"] == "Y");
								?><div id="log_entry_favorites_<?=$arEvent["EVENT"]["ID"]?>" data-favorites="<?=($bFavorites ? "Y" : "N")?>"  class="lenta-item-fav<?=($bFavorites ? " lenta-item-fav-active" : "")?>" onclick="__MSLSetFavorites(<?=$arEvent["EVENT"]["ID"]?>, this, '<?=($bFavorites ? "N" : "Y")?>'); return BX.PreventDefault(this);"></div><?
							}
							else
							{
								?><div class="lenta-item-fav-placeholder"></div><?
							}
						?></div><?
						?><?=$strCreatedBy?><?
						?><div class="post-item-top-topic"><?=$strTopic ?></div><?
						?><div class="lenta-item-time" id="datetime_block_detail" ><?=$datetime_detail?></div><?
					?></div><?
					if (strlen($strDescription) > 0)
					{
						echo $strDescription;
					}
				?></div><?

				ob_start();

				if (
					strlen($arEvent["EVENT"]["RATING_TYPE_ID"]) > 0
					&& $arEvent["EVENT"]["RATING_ENTITY_ID"] > 0
					&& $arParams["SHOW_RATING"] == "Y"
				)
				{
					$bRatingExtended = (
						CModule::IncludeModule("mobileapp") 
							? (CMobile::getApiVersion() >= 2) 
							: (intval($GLOBALS["APPLICATION"]->GetPageProperty("api_version")) >= 2)
					);

					?><span class="bx-ilike-block" id="rating_block_<?=intval($arEvent["EVENT"]["ID"])?>" data-counter="<?=intval($arEvent["RATING"]["TOTAL_VOTES"])?>" style="display: none;"><?
						$arResultVote = $APPLICATION->IncludeComponent(
							"bitrix:rating.vote",
							"mobile_like",
							Array(
								"ENTITY_TYPE_ID" => $arEvent["EVENT"]["RATING_TYPE_ID"],
								"ENTITY_ID" => $arEvent["EVENT"]["RATING_ENTITY_ID"],
								"OWNER_ID" => $arEvent["CREATED_BY"]["TOOLTIP_FIELDS"]["ID"],
								"USER_VOTE" => $arEvent["RATING"]["USER_VOTE"],
								"USER_HAS_VOTED" => $arEvent["RATING"]["USER_HAS_VOTED"],
								"TOTAL_VOTES" => $arEvent["RATING"]["TOTAL_VOTES"],
								"TOTAL_POSITIVE_VOTES" => $arEvent["RATING"]["TOTAL_POSITIVE_VOTES"],
								"TOTAL_NEGATIVE_VOTES" => $arEvent["RATING"]["TOTAL_NEGATIVE_VOTES"],
								"TOTAL_VALUE" => $arEvent["RATING"]["TOTAL_VALUE"],
								"PATH_TO_USER_PROFILE" => $arEvent["CREATED_BY"]["TOOLTIP_FIELDS"]["PATH_TO_SONET_USER_PROFILE"],
								"IS_RATING_EXTENDED" => $bRatingExtended,
								"EXTENDED" => "Y",
								"IS_LIST" => $arParams["IS_LIST"],
								"LOG_ID" => $arParams["LOG_ID"],
								"VOTE_RAND" => (!$arParams["IS_LIST"] && intval($_REQUEST["LIKE_RANDOM_ID"]) > 0 ? intval($_REQUEST["LIKE_RANDOM_ID"]) : false)
							),
							$component,
							array("HIDE_ICONS" => "Y")
						);
					?></span><?

					if ($arResultVote)
					{
						ob_start(); // rating buffer

						?><span id="rating_button_<?=intval($arEvent["EVENT"]["ID"])?>"><?
							?><div class="post-item-informers post-item-inform-likes<?=($arResultVote['USER_HAS_VOTED'] == 'Y' ? '-active' : '')?>" id="bx-ilike-button-<?=CUtil::JSEscape(htmlspecialcharsbx($arResultVote['VOTE_ID']))?>"><?

								if (
									intval($arResultVote["TOTAL_VOTES"]) > 1
									|| (
										intval($arResultVote["TOTAL_VOTES"]) == 1
										&& $arResultVote['USER_HAS_VOTED'] == "N"
									)
								)
								{
									?><div class="post-item-inform-left"><?=GetMessage("MOBILE_LOG_LIKE2_ACTION")?></div><?
									?><div class="post-item-inform-right"><span class="post-item-inform-right-text"><?=htmlspecialcharsEx($arResultVote["TOTAL_VOTES"])?></span></div><?
								}
								else
								{
									?><div class="post-item-inform-left"><?=GetMessage("MOBILE_LOG_LIKE_ACTION")?></div><?
									?><div class="post-item-inform-right" style="display: none;"><span class="post-item-inform-right-text"><?=htmlspecialcharsEx($arResultVote["TOTAL_VOTES"])?></span></div><?
								}

							?></div><?
						?></span><?

						$strRatingActionBlock = ob_get_contents();
						ob_end_clean(); // -- rating buffer
					}

					if (
						$strRatingActionBlock
						&& strlen($strRatingActionBlock) > 0
					)
					{
						?><?=$strRatingActionBlock?><?
					}

					$bRatingExtendedOpen = (
						$bRatingExtended
						&& intval($arResultVote["TOTAL_VOTES"]) > 0
					);

					if (
						$arParams["IS_LIST"]
						&& intval($arResultVote["VOTE_RAND"]) > 0
					)
					{
						?><script type="text/javascript">
							arLikeRandomID.entry_<?=intval($arEvent["EVENT"]["ID"])?> = <?=intval($arResultVote["VOTE_RAND"])?>;
						</script><?
					}
				}

				if (
					array_key_exists("HAS_COMMENTS", $arEvent)
					&& $arEvent["HAS_COMMENTS"] == "Y"
				)
				{
					$bHasComments = true;

					if ($strTaskPath)
					{
						$strOnClickCommentsForm = $strOnClickCommentsTop = ($arParams["IS_LIST"] ? " onclick=\"__MSLOpenLogEntry(".intval($arEvent["EVENT"]["ID"]).", '".$strTaskPath."', false, event);\"" : " onclick=\"__MSLDetailMoveBottom();\"");
					}
					else
					{
						$arOnClickParamsCommentsForm = $arOnClickParams;
						$arOnClickParamsCommentsForm["focus_form"] = true;
						$arOnClickParamsCommentsForm["show_full"] = in_array($arEvent["EVENT"]["EVENT_ID"], array("timeman_entry", "report", "calendar"));
						$strOnClickParamsCommentsForm = CUtil::PhpToJSObject($arOnClickParamsCommentsForm);
						$strOnClickCommentsForm = ($arParams["IS_LIST"] ? " onclick=\"__MSLOpenLogEntryNew(".$strOnClickParamsCommentsForm.", event);\"" : " onclick=\"__MSLDetailMoveBottom();\"");

						$arOnClickParamsCommentsTop = $arOnClickParams;
						$arOnClickParamsCommentsTop["focus_comments"] = true;
						$arOnClickParamsCommentsTop["show_full"] = in_array($arEvent["EVENT"]["EVENT_ID"], array("timeman_entry", "report", "calendar"));
						$strOnClickParamsCommentsTop = CUtil::PhpToJSObject($arOnClickParamsCommentsTop);
						$strOnClickCommentsTop = ($arParams["IS_LIST"] ? " onclick=\"__MSLOpenLogEntryNew(".$strOnClickParamsCommentsTop.", event);\"" : "");
					}

					?><div id="comments_control_<?=intval($arEvent["EVENT"]["ID"])?>" class="post-item-informers post-item-inform-comments"<?=$strOnClickCommentsTop?>><?
						$num_comments = intval($arParams["EVENT"]["COMMENTS_COUNT"]);
						?><div class="post-item-inform-left" id="informer_comments_text2_<?=$arEvent["EVENT"]["ID"]?>" style="display: <?=($num_comments > 0 ? "inline-block" : "none")?>;"><?
							?><?=GetMessage('MOBILE_LOG_COMMENTS_2')?><?
						?></div><?
						?><div class="post-item-inform-left" id="informer_comments_text_<?=$arEvent["EVENT"]["ID"]?>" style="display: <?=($num_comments <= 0 ? "inline-block" : "none")?>;"><?
							?><?=GetMessage('MOBILE_LOG_COMMENTS')?><?
						?></div><?

						?><div class="post-item-inform-right" id="informer_comments_<?=$arEvent["EVENT"]["ID"]?>" style="display: <?=($num_comments > 0 ? 'inline-block' : 'none')?>;"><?
							if (
								($arParams["USE_FOLLOW"] != "Y" || $arEvent["EVENT"]["FOLLOW"] == "Y")
								&& intval($arResult["NEW_COMMENTS"]) > 0
							)
							{
								?><span id="informer_comments_all_<?=$arEvent["EVENT"]["ID"]?>"><?
									$old_comments = intval(abs($num_comments - intval($arResult["NEW_COMMENTS"])));
									?><?=($old_comments > 0 ? $old_comments : '')?><?
								?></span><?
								?><span id="informer_comments_new_<?=$arEvent["EVENT"]["ID"]?>" class="post-item-inform-right-new"><?
									?><span class="post-item-inform-right-new-sign">+</span><?
									?><span class="post-item-inform-right-new-value"><?=intval($arResult["NEW_COMMENTS"])?></span><?
								?></span><?
							}
							else
							{
								?><?=$num_comments?><?
							}

						?></div><?
					?></div><?
				}
				else
				{
					$bHasComments = false;
				}

				if ($bHasComments)
				{
					?><div id="log_entry_follow_<?=intval($arEvent["EVENT"]["ID"])?>" data-follow="<?=($arEvent["EVENT"]["FOLLOW"] == "Y" ? "Y" : "N")?>" style="display: none;"></div><?
				}

				if (
					!in_array(
						$arEvent["EVENT"]["EVENT_ID"], 
						array("photo", "photo_photo", "files", "commondocs", "tasks", "timeman_entry", "report", "calendar", "crm_activity_add")
					)
				)
				{
					if ($arParams["IS_LIST"])
					{
						$arOnClickParams["show_full"] = true;
						$strOnClickParams = CUtil::PhpToJSObject($arOnClickParams);
						$strOnClickMore = ' onclick="__MSLOpenLogEntryNew('.$strOnClickParams.', event);"';
					}
					else
					{
						$strOnClickMore = ' onclick="oMSL.expandText('.intval($arEvent["EVENT"]["ID"]).');"';
					}

					?><a id="post_more_limiter_<?=intval($arEvent["EVENT"]["ID"])?>" <?=$strOnClickMore?> class="post-item-more" ontouchstart="this.classList.toggle('post-item-more-pressed')" ontouchend="this.classList.toggle('post-item-more-pressed')" style="display: none;"><?
						?><?=GetMessage("MOBILE_LOG_EXPAND")?><?
					?></a><?
				}

				$strBottomBlock = ob_get_contents();
				ob_end_clean();

				$post_item_style = (!$arParams["IS_LIST"] && $_REQUEST["show_full"] == "Y" ? "post-item-post-block-full" : "post-item-post-block");

				if (in_array($arEvent["EVENT"]["EVENT_ID"], array("photo", "photo_photo")))
				{
					include($_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix/mobile.socialnetwork.log.entry/templates/.default/photo.php");
				}
				elseif (strlen($arEvent["EVENT_FORMATTED"]["MESSAGE"]) > 0)
				{
					// body

					if (
						array_key_exists("EVENT_FORMATTED", $arEvent)
						&& array_key_exists("IS_IMPORTANT", $arEvent["EVENT_FORMATTED"])
						&& $arEvent["EVENT_FORMATTED"]["IS_IMPORTANT"]
					)
					{
						$news_item_style = (
							!$arParams["IS_LIST"]
							&& $_REQUEST["show_full"] == "Y"
								? "lenta-info-block-wrapp-full"
								: "lenta-info-block-wrapp"
						);

						?><div class="<?=$news_item_style?>"<?=$strOnClick?> id="post_block_check_cont_<?=$arEvent["EVENT"]["ID"]?>"><?
							?><div class="lenta-info-block <?=(in_array($arEvent["EVENT"]["EVENT_ID"], array("intranet_new_user", "bitrix24_new_user")) ? "lenta-block-new-employee" : "info-block-important")?>"><?
								if (in_array($arEvent["EVENT"]["EVENT_ID"], array("intranet_new_user", "bitrix24_new_user")))
								{
									echo CSocNetTextParser::closetags($arEvent["EVENT_FORMATTED"]["MESSAGE"]);
								}
								else
								{
									if (
										array_key_exists("IS_IMPORTANT", $arEvent["EVENT_FORMATTED"])
										&& $arEvent["EVENT_FORMATTED"]["IS_IMPORTANT"]
										&& array_key_exists("TITLE_24_2", $arEvent["EVENT_FORMATTED"])
										&& strlen($arEvent["EVENT_FORMATTED"]["TITLE_24_2"]) > 0
									)
									{
										?><div class="lenta-important-block-title" id="post_block_check_title_<?=$arEvent["EVENT"]["ID"]?>"><?=$arEvent["EVENT_FORMATTED"]["TITLE_24_2"]?></div><?
									}

									?><div class="lenta-important-block-text" id="post_block_check_<?=$arEvent["EVENT"]["ID"]?>"><?
										?><?=CSocNetTextParser::closetags(htmlspecialcharsback($arEvent["EVENT_FORMATTED"]["MESSAGE"]))?><?
										?><span class="lenta-block-angle"></span><?
									?></div><?
								}
							?></div><?

							?><div class="post-more-block" id="post_more_block_<?=$arEvent["EVENT"]["ID"]?>"></div><?
						?></div><?
					}
					elseif (in_array($arEvent["EVENT"]["EVENT_ID"], array("files", "commondocs")))
					{
						?><div class="post-item-post-block-full"<?=$strOnClick?>>
							<div class="post-item-attached-file-wrap">
								<div class="post-item-attached-file"><span><?=$arEvent["EVENT"]["TITLE"]?></span></div>
							</div><?
						?></div><?
					}
					elseif (in_array($arEvent["EVENT"]["EVENT_ID"], array("tasks", "timeman_entry", "report", "calendar", "crm_activity_add")))
					{
						?><div id="post_block_check_cont_<?=intval($arEvent["EVENT"]["ID"])?>" class="lenta-info-block-wrapp-full"<?=$strOnClick?>><?=$arEvent["EVENT_FORMATTED"]["MESSAGE"]?></div><?
					}
					elseif (strlen($arEvent["EVENT_FORMATTED"]["MESSAGE"]) > 0) // all other events
					{
						?><div class="<?=$post_item_style?>"<?=$strOnClick?> id="post_block_check_cont_<?=$arEvent["EVENT"]["ID"]?>"><?
							if (
								array_key_exists("TITLE_24_2", $arEvent["EVENT_FORMATTED"])
								&& strlen($arEvent["EVENT_FORMATTED"]["TITLE_24_2"]) > 0
							)
							{
								?><div class="post-text-title" id="post_text_title_<?=$arEvent["EVENT"]["ID"]?>"><?=$arEvent["EVENT_FORMATTED"]["TITLE_24_2"]?></div><?
							}
							?><div class="post-item-text" id="post_block_check_<?=$arEvent["EVENT"]["ID"]?>"><?=$arEvent["EVENT_FORMATTED"]["MESSAGE"]?></div><?

							if (
								array_key_exists("EVENT_FORMATTED", $arEvent)
								&& is_array($arEvent["EVENT_FORMATTED"]["UF"])
								&& count($arEvent["EVENT_FORMATTED"]["UF"]) > 0
							)
							{
								?><div class="post-item-attached-file-wrap" id="post_block_check_files_<?=$arEvent["EVENT"]["ID"]?>"><?
									$eventHandlerID = false;
									$eventHandlerID = AddEventHandler("main", "system.field.view.file", "__logUFfileShowMobile");
									foreach ($arEvent["EVENT_FORMATTED"]["UF"] as $FIELD_NAME => $arUserField)
									{
										if(!empty($arUserField["VALUE"]))
										{
											$APPLICATION->IncludeComponent(
												"bitrix:system.field.view",
												$arUserField["USER_TYPE"]["USER_TYPE_ID"],
												array(
													"arUserField" => $arUserField,
													"ACTION_PAGE" => str_replace("#log_id#", $arEvent["EVENT"]["ID"], $arParams["PATH_TO_LOG_ENTRY"]),
													"MOBILE" => "Y"
												),
												null,
												array("HIDE_ICONS"=>"Y")
											);
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

							?><div class="post-more-block" id="post_more_block_<?=$arEvent["EVENT"]["ID"]?>"></div><?							
						?></div><?
					}
				}

				if (strlen($strBottomBlock) > 0)
				{
					?><div id="post_item_inform_wrap" class="post-item-inform-wrap" style="display: <?=($arParams["IS_LIST"] ? 'block' : 'none')?>;"><?
						?><?=$strBottomBlock;?><?
					?></div><?
				}

				if ($bRatingExtended)
				{
					?><div 
						class="post-item-inform-wrap-tree" 
						id="<?=(!$arParams["IS_LIST"] ? 'rating-footer-wrap' : 'rating-footer-wrap_'.intval($arEvent["EVENT"]["ID"]))?>" 
						style="display: <?=(!$arParams["IS_LIST"] && $bRatingExtendedOpen ? "block" : "none")?>;"
					><?
						?><div class="post-item-inform-footer" id="rating-footer_<?=intval($arEvent["EVENT"]["ID"])?>" style="display: <?=(!$arParams["IS_LIST"] ? "block" : "none")?>;"><?
							if (
								$arResultVote
								&& CMobile::getApiVersion() >= 2
							)
							{
								$me = '<span class="post-strong">'.GetMessage("MOBILE_LOG_LIKE_ME").'</span>';

								$bHasVotes = intval($arResultVote["TOTAL_VOTES"]) > 0;
								$bOnlyYou = (
									intval($arResultVote["TOTAL_VOTES"]) == 1
									&& $arResultVote["USER_HAS_VOTED"] == "Y"
								);

								?><div class="rating-footer-you" id="bx-ilike-list-you" style="display: <?=($bOnlyYou ? "block" : "none")?>"><?=str_replace("#YOU#", $me, GetMessage("MOBILE_LOG_LIKE_YOU"))?></div><?

								$count = ($arResultVote["USER_HAS_VOTED"] == "Y" ? intval($arResultVote["TOTAL_VOTES"]) - 1 : intval($arResultVote["TOTAL_VOTES"]));
								$reminder = $count % 10;
								$users_title = GetMessage("MOBILE_LOG_LIKE_USERS_".($reminder == 1 ? "1" : "2"));

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
										GetMessage("MOBILE_LOG_LIKE_COUNT_USERS")
									).'</span>';

								?><div class="rating-footer-youothers" id="bx-ilike-list-youothers" style="display: <?=($bHasVotes && !$bOnlyYou && $arResultVote["USER_HAS_VOTED"] == "Y" ? "block" : "none")?>" onclick="RatingLike.List('<?=CUtil::JSEscape(htmlspecialcharsbx($arResultVote['VOTE_ID']))?>');"><?
									?><?=str_replace(array("#YOU#", "#COUNT_USERS#"), array($me, $count_users), GetMessage("MOBILE_LOG_LIKE_YOU_OTHERS"))?><?
								?></div><?
								?><div class="rating-footer-others" id="bx-ilike-list-others" style="display: <?=($bHasVotes && $arResultVote["USER_HAS_VOTED"] == "Y" ? "none" : "block")?>" onclick="RatingLike.List('<?=CUtil::JSEscape(htmlspecialcharsbx($arResultVote['VOTE_ID']))?>');"><?
									?><?=str_replace("#COUNT_USERS#", $count_users, GetMessage("MOBILE_LOG_LIKE_OTHERS"))?><?
								?></div><?
							}
						?></div><?
					?></div><?
				}

			?></div><? // post-item-top-wrap
		?></div><? // post-wrap / lenta-item

		?><script type="text/javascript">
			BX.ready(function() {
				oMSL.arBlockToCheck[<?=$arEvent["EVENT"]["ID"]?>] = {
					lenta_item_id: 'lenta_item_<?=$arEvent["EVENT"]["ID"]?>',
					text_block_id: 'post_block_check_<?=$arEvent["EVENT"]["ID"]?>',
					title_block_id: 'post_block_check_title_<?=$arEvent["EVENT"]["ID"]?>',
					files_block_id: 'post_block_check_files_<?=$arEvent["EVENT"]["ID"]?>',
					more_overlay_id: 'post_more_block_<?=$arEvent["EVENT"]["ID"]?>',
					more_button_id: 'post_more_limiter_<?=$arEvent["EVENT"]["ID"]?>'
				}
			});

			<?
			if (!$arParams["IS_LIST"])
			{
				$arEntityXMLID = array(
					"tasks" => "TASK",
					"forum" => "FORUM",
					"photo_photo" => "PHOTO",
					"sonet" => "SOCNET",
				);

				$entity_xml_id = (
					array_key_exists($arEvent["EVENT"]["EVENT_ID"], $arEntityXMLID) 
					&& $arEvent["EVENT"]["SOURCE_ID"] > 0
						? $arEntityXMLID[$arEvent["EVENT"]["EVENT_ID"]]."_".$arEvent["EVENT"]["SOURCE_ID"] 
						: strtoupper($arEvent["EVENT"]["EVENT_ID"])."_".$arEvent["EVENT"]["ID"]
				);

				?>
				BX.ready(function()
				{
					oMSL.InitDetail({
						commentsType: 'log',
						detailPageId: 'log_' + <?=$arEvent["EVENT"]["ID"]?>,
						logId: <?=$arEvent["EVENT"]["ID"]?>,
						entityXMLId: '<?=CUtil::JSEscape($entity_xml_id)?>',
						bUseFollow: <?=($arParams["USE_FOLLOW"] == 'N' ? 'false' : 'true')?>,
						bFollow: <?=($arParams["FOLLOW"] == 'N' ? 'false' : 'true')?>
					});
				});
				<?
			}
			?>
		</script><?

		if (
			!$arParams["IS_LIST"]
			&& array_key_exists("HAS_COMMENTS", $arEvent)
			&& $arEvent["HAS_COMMENTS"] == "Y"
		)
		{
			?><div class="post-comments-wrap" id="post-comments-wrap"><?

				if ($_REQUEST["empty_get_comments"] == "Y")
				{
					$APPLICATION->RestartBuffer();
					$strCommentsText = "";
					ob_start();
				}

				?><script>
					app.setPageID('LOG_ENTRY_<?=$arEvent["EVENT"]["ID"]?>');
					tmp_post_id = 0;
					tmp_log_id = <?=intval($arEvent["EVENT"]["ID"])?>;

					var arEntryCommentID = [];
					app.onCustomEvent('onPullExtendWatch', {'id': 'UNICOMMENT<?=$arEvent["COMMENTS_PARAMS"]["ENTITY_XML_ID"]?>'});

					app.onCustomEvent('onCommentsGet', { log_id: <?=$arEvent["EVENT"]["ID"]?>, ts: '<?=time()?>'});
					<?
					if ($_REQUEST["empty_get_comments"] != "Y")
					{
						?>
						BX.addCustomEvent("onPull", oMSL.onPullComment);
						<?
					}
					?>
				</script><?

				if (is_array($arEvent["COMMENTS"]))
				{
					$jsIds = "";
					foreach($arEvent["COMMENTS"] as $arComment)
					{
						if (
							!$bMoreShown
							&& count($arEvent["COMMENTS"]) > 0
							&& intval($arParams["EVENT"]["COMMENTS_COUNT"]) > count($arEvent["COMMENTS"])
						)
						{
							$bMoreShown = true;
							?><div 
								id="post-comment-more" 
								class="post-comments-button" 
								onclick="oMSL.showMoreComments({ lastCommentId: <?=intval($arComment["EVENT"]["ID"])?>, lastCommentTimestamp: <?=intval($arComment["LOG_DATE_TS"])?>});" 
								ontouchstart="BX.toggleClass(this, 'post-comments-button-press');" 
								ontouchend="BX.toggleClass(this, 'post-comments-button-press');"
							><?
								?><?=str_replace("#COMMENTS#", '<span id="comcntleave-all">'.$arParams["EVENT"]["COMMENTS_COUNT"].'</span>', GetMessage("MOBILE_LOG_COMMENT_BUTTON_MORE"))?><?
							?></div>
							<div id="post-comment-hidden" style="display:none; overflow:hidden;"></div><?
						}

						$strCreatedBy = "";
						if (
							array_key_exists("CREATED_BY", $arComment)
							&& is_array($arComment["CREATED_BY"])
							&& array_key_exists("FORMATTED", $arComment["CREATED_BY"])
							&& strlen($arComment["CREATED_BY"]["FORMATTED"]) > 0
						)
						{
							$strCreatedBy = $arComment["CREATED_BY"]["FORMATTED"];
						}

						$bUnread = (
							($arResult["COUNTER_TYPE"] == "**")
							&& $arComment["EVENT"]["USER_ID"] != $GLOBALS["USER"]->GetID()
							&& intval($arResult["LAST_LOG_TS"]) > 0
							&& (MakeTimeStamp($arComment["EVENT"]["LOG_DATE"]) - intval($arResult["TZ_OFFSET"])) > $arResult["LAST_LOG_TS"]
						);

						$commentNodeId = "entry-comment-".$arEvent["COMMENTS_PARAMS"]["ENTITY_XML_ID"]."-".($arComment["EVENT"]["SOURCE_ID"] > 0 ? $arComment["EVENT"]["SOURCE_ID"] : $arComment["EVENT"]["ID"]);
						
						$strBottomBlockComments = "";
						ob_start();

						if (
							strlen($arComment["EVENT"]["RATING_TYPE_ID"]) > 0
							&& $arComment["EVENT"]["RATING_ENTITY_ID"] > 0
							&& $arParams["SHOW_RATING"] == "Y"
						)
						{
							$arCommentResultVote = $APPLICATION->IncludeComponent(
								"bitrix:rating.vote",
								"mobile_comment_like",
								Array(
									"ENTITY_TYPE_ID" => $arComment["EVENT"]["RATING_TYPE_ID"],
									"ENTITY_ID" => $arComment["EVENT"]["RATING_ENTITY_ID"],
									"OWNER_ID" => intval($arComment["EVENT"]["USER_ID"]),
									"USER_VOTE" => array_key_exists($arComment["EVENT"]["RATING_ENTITY_ID"], $arResult["RATING_COMMENTS"]) ? $arResult["RATING_COMMENTS"][$arComment["EVENT"]["RATING_ENTITY_ID"]]["USER_VOTE"] : false,
									"USER_HAS_VOTED" => array_key_exists($arComment["EVENT"]["RATING_ENTITY_ID"], $arResult["RATING_COMMENTS"]) ? $arResult["RATING_COMMENTS"][$arComment["EVENT"]["RATING_ENTITY_ID"]]["USER_HAS_VOTED"] : false,
									"TOTAL_VOTES" => array_key_exists($arComment["EVENT"]["RATING_ENTITY_ID"], $arResult["RATING_COMMENTS"]) ? $arResult["RATING_COMMENTS"][$arComment["EVENT"]["RATING_ENTITY_ID"]]["TOTAL_VOTES"] : false,
									"TOTAL_POSITIVE_VOTES" => array_key_exists($arComment["EVENT"]["RATING_ENTITY_ID"], $arResult["RATING_COMMENTS"]) ? $arResult["RATING_COMMENTS"][$arComment["EVENT"]["RATING_ENTITY_ID"]]["TOTAL_POSITIVE_VOTES"] : false,
									"TOTAL_NEGATIVE_VOTES" => array_key_exists($arComment["EVENT"]["RATING_ENTITY_ID"], $arResult["RATING_COMMENTS"]) ? $arResult["RATING_COMMENTS"][$arComment["EVENT"]["RATING_ENTITY_ID"]]["TOTAL_NEGATIVE_VOTES"] : false,
									"TOTAL_VALUE" => array_key_exists($arComment["EVENT"]["RATING_ENTITY_ID"], $arResult["RATING_COMMENTS"]) ? $arResult["RATING_COMMENTS"][$arComment["EVENT"]["RATING_ENTITY_ID"]]["TOTAL_VALUE"] : false,
									"PATH_TO_USER_PROFILE" => $arParams["PATH_TO_USER"]
								),
								$component,
								array("HIDE_ICONS" => "Y")
							);
						}

						$strBottomBlockComments = ob_get_contents();
						ob_end_clean();

						?><script>
							BX.ready(function()
							{
								BX.MSL.viewImageBind('<?=$commentNodeId?>-text', { tag: 'IMG', attr: 'data-bx-image' });
								if (BX('<?=$commentNodeId?>-files'))
								{
									BX.MSL.viewImageBind('<?=$commentNodeId?>-files', { tag: 'IMG', attr: 'data-bx-image' });
								}

								if (app.enableInVersion(10))
								{
									BX.bind(BX('<?=$commentNodeId?>'), 'click', function(event)
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
												title: '<?=GetMessageJS("MOBILE_LOG_COMMENT_REPLY")?>',
												callback: function()
												{
													oMSL.replyToComment(<?=intval($arComment["EVENT"]["USER_ID"])?>, '<?=CUtil::JSEscape(htmlspecialcharsback($strCreatedBy))?>');
												}
											}
											<?
											if (
												isset($arCommentResultVote)
												&& isset($arCommentResultVote["TOTAL_POSITIVE_VOTES"])
												&& intval($arCommentResultVote["TOTAL_POSITIVE_VOTES"]) > 0
											)
											{
												?>
												,{
													title: '<?=GetMessageJS("MOBILE_LOG_COMMENT_LIKES_LIST")?>',
													callback: function()
													{
														RatingLike.List('<?=CUtil::JSEscape(htmlspecialcharsbx($arCommentResultVote['VOTE_ID']))?>');
													}
												}
												<?
											}

											if (
												isset($arComment["CAN_EDIT"]) && $arComment["CAN_EDIT"] == "Y"
												&& isset($arComment["CAN_DELETE"]) && $arComment["CAN_DELETE"] == "Y"
											)
											{
												?>
												,
												<?
											}
											if (isset($arComment["CAN_EDIT"]) && $arComment["CAN_EDIT"] == "Y")
											{
												?>
												{
													title: '<?=GetMessageJS("MOBILE_LOG_COMMENT_EDIT")?>',
													callback: function()
													{
														oMSL.editComment({
															commentId: <?=$arComment["EVENT"]["ID"]?>, 
															commentText: '<?=CUtil::JSEscape($arComment["EVENT"]["MESSAGE"])?>', 
															commentType: 'log', 
															postId: <?=intval($arEvent["EVENT"]["ID"])?>, 
															nodeId: '<?=$commentNodeId?>'
														});
													}
												}
												<?
											}
											if (
												isset($arComment["CAN_EDIT"]) && $arComment["CAN_EDIT"] == "Y"
												&& isset($arComment["CAN_DELETE"]) && $arComment["CAN_DELETE"] == "Y"
											)
											{
												?>
												,
												<?
											}
											if (
												isset($arComment["CAN_DELETE"]) && $arComment["CAN_DELETE"] == "Y"
											)
											{
												?>
												{
													title: '<?=GetMessageJS("MOBILE_LOG_COMMENT_DELETE")?>',
													callback: function()
													{
														oMSL.deleteComment({
															commentId: <?=$arComment["EVENT"]["ID"]?>, 
															commentType: 'log',
															nodeId: '<?=$commentNodeId?>'
														});
													}
												}
												<?
											}
											?>
										]);
									});
								}
							});
						</script><?

						?><div class="post-comment-block<?=($bUnread ? " post-comment-new" : "")?>" id="<?=$commentNodeId?>"><?
							?><div class="post-user-wrap"><?
								?><div class="avatar"<?=(strlen($arComment["AVATAR_SRC"]) > 0 ? " style=\"background-image:url('".$arComment["AVATAR_SRC"]."')\"" : "")?>></div><?
								?><div class="post-comment-cont"><?
									if (strlen($arComment["CREATED_BY"]["URL"]) > 0)
									{
										?><a href="<?=$arComment["CREATED_BY"]["URL"]?>" class="post-comment-author"><?=$strCreatedBy?></a><?
									}
									else
									{
										?><div class="post-comment-author"><?=$strCreatedBy?></div><?
									}

									?><div class="post-comment-time"><?
										$arFormat = Array(
											"tommorow" => "tommorow, ".GetMessage("MOBILE_LOG_COMMENT_FORMAT_TIME"),
											"today" => "today, ".GetMessage("MOBILE_LOG_COMMENT_FORMAT_TIME"),
											"yesterday" => "yesterday, ".GetMessage("MOBILE_LOG_COMMENT_FORMAT_TIME"),
											"" => (date("Y", $arComment["LOG_DATE_TS"]) == date("Y") ? GetMessage("MOBILE_LOG_COMMENT_FORMAT_DATE") : GetMessage("MOBILE_LOG_COMMENT_FORMAT_DATE_YEAR"))
										);
										?><?=FormatDate($arFormat, $arComment["LOG_DATE_TS"])?><?
									?></div><?
								?></div><?
							?></div><?

							?><div class="post-comment-text" id="<?=$commentNodeId?>-text"><?
								$message = (array_key_exists("EVENT_FORMATTED", $arComment) && array_key_exists("MESSAGE", $arComment["EVENT_FORMATTED"]) ? $arComment["EVENT_FORMATTED"]["MESSAGE"] : $arComment["EVENT"]["MESSAGE"]);
								if (strlen($message) > 0)
								{
									?><?=CSocNetTextParser::closetags(htmlspecialcharsback($message))?><?
								}
							?></div><?

							if (
								is_array($arComment["UF"])
								&& count($arComment["UF"]) > 0
							)
							{
								?><div class="post-item-attached-file-wrap" id="<?=$commentNodeId?>-files"><?
									$eventHandlerID = false;
									$eventHandlerID = AddEventHandler("main", "system.field.view.file", "__logUFfileShowMobile");
									foreach ($arComment["UF"] as $FIELD_NAME => $arUserField)
									{
										if(!empty($arUserField["VALUE"]))
										{
											$APPLICATION->IncludeComponent(
												"bitrix:system.field.view",
												$arUserField["USER_TYPE"]["USER_TYPE_ID"],
												array(
													"arUserField" => $arUserField,
													"ACTION_PAGE" => str_replace("#log_id#", $arEvent["EVENT"]["ID"], $arParams["PATH_TO_LOG_ENTRY"]),
													"MOBILE" => "Y"
												),
												null,
												array("HIDE_ICONS"=>"Y")
											);
										}
									}
									if (
										$eventHandlerID !== false 
										&& (intval($eventHandlerID) > 0)
									)
									{
										RemoveEventHandler("main", "system.field.view.file", $eventHandlerID);
									}
								?></div><?
							}

							if (strlen($strBottomBlockComments) > 0)
							{
								?><?=$strBottomBlockComments;?><? // comments rating
							}

							if (CMobile::getApiVersion() >= 10)
							{
								?><div class="post-comment-reply"><?
									?><div class="post-comment-reply-text" onclick="oMSL.replyToComment(<?=intval($arComment["EVENT"]["USER_ID"])?>, '<?=CUtil::JSEscape($strCreatedBy)?>', event);"><?
										?><?=GetMessage('MOBILE_LOG_COMMENT_REPLY')?><?
									?></div><?
								?></div><?
							}

						?></div><?
					}

					if(strlen($jsIds) > 0)
					{
						?><script>BitrixMobile.LazyLoad.registerImages([<?=$jsIds?>]);</script><?
					}
				}

				?><span id="post-comment-last-after"></span><?

				if ($_REQUEST["empty_get_comments"] == "Y")
				{
					?><script>BitrixMobile.LazyLoad.showImages();</script><?

					if (
						$GLOBALS["USER"]->IsAuthorized() 
						&& CModule::IncludeModule("pull") 
						&& CPullOptions::GetNginxStatus()
					)
					{
						CPullWatch::Add($GLOBALS["USER"]->GetID(), "UNICOMMENTS".$arEvent["COMMENTS_PARAMS"]["ENTITY_XML_ID"]);
						CPullWatch::DeferredSql(); // epilog needed ?
					}

					$strCommentsText = ob_get_contents();
					ob_end_clean();

					echo CUtil::PhpToJSObject(array(
						"TEXT" => $strCommentsText,
						"POST_NUM_COMMENTS" => intval($arParams["EVENT"]["COMMENTS_COUNT"])
					));

					die();
				}

			?></div><? // post-comments-wrap
		}
		else
		{
			?><script>
				app.setPageID('LOG_ENTRY_<?=$arEvent["EVENT"]["ID"]?>');
			</script><?

			if ($_REQUEST["empty_get_comments"] == "Y")
			{
				$APPLICATION->RestartBuffer();

				$strCommentsText = "";
				ob_start();
			
				?><script>
					app.setPageID('LOG_ENTRY_<?=$arEvent["EVENT"]["ID"]?>');
				</script><?

				$strCommentsText = ob_get_contents();
				ob_end_clean();

				echo CUtil::PhpToJSObject(array(
					"TEXT" => $strCommentsText
				));
				die();
			}
		}

		if (!$arParams["IS_LIST"] && CMobile::getApiVersion() < 4 && CMobile::getPlatform() != "android")
		{
			?></div><? // post-card-wrap
		}

		?><script>
			arCanUserComment[<?=$arParams["LOG_ID"]?>] = <?=(isset($arEvent["CAN_ADD_COMMENTS"]) && $arEvent["CAN_ADD_COMMENTS"] == "Y" ? "true" : "false")?>;
		</script><?

		if ($_REQUEST["empty_get_form"] == "Y")
			$APPLICATION->RestartBuffer();

		?><script>
			app.setPageID('LOG_ENTRY_<?=$arEvent["EVENT"]["ID"]?>');
		</script><?

		if (
			!$arParams["IS_LIST"]
			&& isset($arEvent["HAS_COMMENTS"])
			&& $arEvent["HAS_COMMENTS"] == "Y"
			&& isset($arEvent["CAN_ADD_COMMENTS"])
			&& $arEvent["CAN_ADD_COMMENTS"] == "Y"
		)
		{
			if (CMobile::getApiVersion() >= 4)
			{
				?><script>
					commentVarSiteID = '<?=CUtil::JSEscape(SITE_ID)?>';
					commentVarLanguageID = '<?=CUtil::JSEscape(LANGUAGE_ID)?>';
					commentVarLogID = <?=intval($arParams["LOG_ID"])?>;
					commentVarDateTimeFormat = '<?=CUtil::JSEscape($arParams["DATE_TIME_FORMAT"])?>';

					entryType = 'non-blog';

					oMSL.createCommentInputForm({
						placeholder: "<?=GetMessageJS("MOBILE_LOG_COMMENT_ADD_TITLE")?>",
						button_name: "<?=GetMessageJS("MOBILE_LOG_COMMENT_ADD_BUTTON_SEND")?>",
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
				?><form class="send-message-block" id="comment_send_form">
					<input type="hidden" id="comment_send_form_logid" name="sonet_log_comment_logid" value="<?=$arParams["LOG_ID"]?>">
					<textarea id="comment_send_form_comment" class="send-message-input" placeholder="<?=GetMessage("MOBILE_LOG_COMMENT_ADD_TITLE")?>"></textarea>
					<input type="button" id="comment_send_button" class="send-message-button" value="<?=GetMessage("MOBILE_LOG_COMMENT_ADD_BUTTON_SEND")?>" ontouchstart="BX.toggleClass(this, 'send-message-button-press');" ontouchend="BX.toggleClass(this, 'send-message-button-press');">
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

						var post_data = {
							'sessid': '<?=bitrix_sessid()?>',
							'site': '<?=CUtil::JSEscape(SITE_ID)?>',
							'lang': '<?=CUtil::JSEscape(LANGUAGE_ID)?>',
							'log_id': BX('comment_send_form_logid').value,
							'message': BX('comment_send_form_comment').value,
							'action': 'add_comment',
							'mobile_action': 'add_comment'
						};

						var BMAjaxWrapper = new MobileAjaxWrapper;
						BMAjaxWrapper.Wrap({
							'type': 'json',
							'method': 'POST',
							'url': BX.message('MSLSiteDir') + 'mobile/ajax.php',
							'data': post_data,
							'callback': function(post_response_data)
							{
								if (post_response_data["commentID"] != 'undefined' && parseInt(post_response_data["commentID"]) > 0)
								{
									var commentID = post_response_data["commentID"];
									get_data = {
										'sessid': '<?=bitrix_sessid()?>',
										'site': '<?=CUtil::JSEscape(SITE_ID)?>',
										'lang': '<?=CUtil::JSEscape(LANGUAGE_ID)?>',
										'cid': commentID,
										'as': <?=intval($arParams["AVATAR_SIZE_COMMENT"])?>,
										'nt': '<?=CUtil::JSEscape($arParams["NAME_TEMPLATE"])?>',
										'sl': '<?=CUtil::JSEscape($arParams["SHOW_LOGIN"])?>',
										'dtf': '<?=CUtil::JSEscape($arParams["DATE_TIME_FORMAT"])?>',
										'p_user': '<?=CUtil::JSEscape($arParams["PATH_TO_USER"])?>',
										'action': 'get_comment',
										'mobile_action': 'get_comment'
									};

									var BMAjaxWrapper = new MobileAjaxWrapper;
									BMAjaxWrapper.Wrap({
										'type': 'json',
										'method': 'POST',
										'url': BX.message('MSLSiteDir') + 'mobile/ajax.php',
										'data': get_data,
										'callback': function(get_response_data)
										{
											__MSLDisableSubmitButton(false);
											if (typeof get_response_data["arCommentFormatted"] != 'undefined')
											{
												oMSL.showNewComment({
													arComment: get_response_data["arCommentFormatted"],
													bClearForm: true
												});
											}
											BitrixMobile.Utils.resetAutoResize(BX("comment_send_form_comment"), BX("post-card-wrap"));

											var followBlock = BX('log_entry_follow_' + post_data.log_id, true);
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
										},
										'callback_failure': function() { __MSLDisableSubmitButton(false); }
									});
								}
								else { __MSLDisableSubmitButton(false); }
							},
							'callback_failure': function() { __MSLDisableSubmitButton(false); }
						});
					}
				});
				</script><?
			}
		}

		if ($_REQUEST["empty_get_form"] == "Y")
			die();
	}
}
?>