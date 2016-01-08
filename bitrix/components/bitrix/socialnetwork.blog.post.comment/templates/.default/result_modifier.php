<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$arResult["RECORDS"] = array();
$arParams["SHOW_MINIMIZED"] = "Y";
$arParams["ENTITY_TYPE"] = "BG";
$arParams["ENTITY_XML_ID"] = "BLOG_".$arParams["ID"];
$arParams["ENTITY_ID"] = $arParams["ID"];
$tplID = 'BLOG_COMMENT_'.$entity_type.'_';
$arResult["newCount"] = $arResult["~newCount"];

if(!empty($arResult["CommentsResult"]) && is_array($arResult["CommentsResult"]))
{
	$arResult["~CommentsResult"] = $arResult["CommentsResult"] = array_reverse($arResult["CommentsResult"]);

	CPageOption::SetOptionString("main", "nav_page_in_session", "N");
	$filter = false; $commentId = 0; $repo = $arResult["CommentsResult"];
	if (!empty($_REQUEST["FILTER"]))
	{
		if (isset($_REQUEST["FILTER"]["<ID"]) && in_array($_REQUEST["FILTER"]["<ID"], $arResult["IDS"])) {
			$filter = "<ID";
			$commentId = $_REQUEST["FILTER"][$filter];
		} else if (isset($_REQUEST["FILTER"][">ID"]) && in_array($_REQUEST["FILTER"][">ID"], $arResult["IDS"])) {
			$filter = ">ID";
			$commentId = $_REQUEST["FILTER"][$filter];
		} else if (isset($_REQUEST["FILTER"]["ID"]) && in_array($_REQUEST["FILTER"]["ID"], $arResult["IDS"])) {
			$filter = "ID";
			$commentId = (!!$arResult["ajax_comment"] ? $arResult["ajax_comment"] : $_REQUEST[$arParams["COMMENT_ID_VAR"]]);
		}
	} else if ($_REQUEST[$arParams["COMMENT_ID_VAR"]] && in_array($_REQUEST[$arParams["COMMENT_ID_VAR"]], $arResult["IDS"])) {
		$filter = ">=ID";
		$commentId = $_REQUEST[$arParams["COMMENT_ID_VAR"]];
	}
	if (!!$filter)
	{
		$id = reset($arResult["IDS"]);
		$CommentsResult = array();
		while ($id > 0 && $id != $commentId)
		{
			array_unshift($CommentsResult, array_pop($arResult["CommentsResult"]));
			$id = next($arResult["IDS"]);
		}
		if ($filter == "<ID")
			$arResult["CommentsResult"] = $CommentsResult;
		else if ($filter == ">ID")
			array_unshift($CommentsResult, array_pop($arResult["CommentsResult"]));
		else if ($filter == "ID")
			$arResult["CommentsResult"] = array(array_pop($arResult["CommentsResult"]));
		else
		{
			if (count($arResult["CommentsResult"]) > $arResult["newCount"])
				$arResult["newCount"] = count($arResult["CommentsResult"]);
			$arResult["CommentsResult"] = $arResult["~CommentsResult"];
		}
	}
	if (
		!$filter 
		|| $filter == ">=ID"
	)
	{
		$arParams["PAGE_SIZE"] = $arResult["newCount"];
	}

	$arResult["NAV_RESULT"] = new CDBResult;
	$arResult["NAV_RESULT"]->InitFromArray($arResult["CommentsResult"]);
	$arResult["NAV_RESULT"]->NavStart($arParams["PAGE_SIZE"], false);
	$arResult["NAV_STRING"] = str_replace(
		array("#source_post_id#", "#post_id#", "#comment_id#", "&IFRAME=Y"),
		array($arResult["Post"]["ID"], $arResult["Post"]["ID"], 0, ""),
		$arResult["urlToMore"]);
	$todayString = ConvertTimeStamp();

	while($comment = $arResult["NAV_RESULT"]->Fetch())
	{
		if(!empty($arResult["userCache"][$comment["AUTHOR_ID"]]) && empty($arResult["userCache"][$comment["AUTHOR_ID"]]["NAME_FORMATED"]))
		{
			$arResult["userCache"][$comment["AUTHOR_ID"]]["NAME_FORMATED"] = CUser::FormatName(
				$arParams["NAME_TEMPLATE"],
				array(
					"NAME" => $arResult["userCache"][$comment["AUTHOR_ID"]]["~NAME"],
					"LAST_NAME" => $arResult["userCache"][$comment["AUTHOR_ID"]]["~LAST_NAME"],
					"SECOND_NAME" => $arResult["userCache"][$comment["AUTHOR_ID"]]["~SECOND_NAME"],
					"LOGIN" => $arResult["userCache"][$comment["AUTHOR_ID"]]["~LOGIN"],
					"NAME_LIST_FORMATTED" => "",
				),
				($arParams["SHOW_LOGIN"] != "N" ? true : false));
		}

		$res = array(
			"ID" => $comment["ID"],
			"NEW" => ($arParams["FOLLOW"] != "N" && $comment["NEW"] == "Y" ? "Y" : "N"),
			"AUTHOR" => array(
				"ID" => $comment["AUTHOR_ID"],
				"NAME" => $arResult["userCache"][$comment["AUTHOR_ID"]]["NAME_FORMATED"],
				"URL" => $arResult["userCache"][$comment["AUTHOR_ID"]]["url"],
				"E-MAIL" => $comment["AuthorEmail"],
				"AVATAR" => $arResult["userCache"][$comment["AUTHOR_ID"]]["PERSONAL_PHOTO_resized"]["src"],
				"IS_EXTRANET" => (is_array($GLOBALS["arExtranetUserID"]) && in_array($comment["AUTHOR_ID"], $GLOBALS["arExtranetUserID"]) ? "Y" : "N"),
			),
			"APPROVED" => ($comment["PUBLISH_STATUS"] == BLOG_PUBLISH_STATUS_PUBLISH ? "Y" : "N"),
			"POST_TIMESTAMP" => $comment["DATE_CREATE_TS"] + $arResult["TZ_OFFSET"],
			"POST_TIME" => $comment["DATE_CREATE_TIME"],
			"POST_DATE" => $comment["DateFormated"],
			"POST_MESSAGE_TEXT" => $comment["TextFormated"],
			"~POST_MESSAGE_TEXT" => "",
			"URL" => array(
				"LINK" => str_replace(array("##comment_id#", "#comment_id#"), array("", $comment["ID"]), $arResult["commentUrl"]),
				"EDIT" => "__blogEditComment('".$comment["ID"]."', '".$arParams["ID"]."');",
				"MODERATE" => str_replace(
					array("#source_post_id#", "#post_id#", "#comment_id#", "&".bitrix_sessid_get()),
					array($arParams["ID"], $arParams["ID"], $comment["ID"], ""),
					($comment["CAN_SHOW"] == "Y" ? $arResult["urlToShow"] : ($comment["CAN_HIDE"] == "Y" ? $arResult["urlToHide"] : ""))
				),
				"DELETE" => str_replace(
					array("#source_post_id#", "#post_id#", "#comment_id#", "&".bitrix_sessid_get()),
					array($arParams["ID"], $arParams["ID"], $comment["ID"], ""),
					$arResult["urlToDelete"]
				)
			),
			"PANELS" => array(
				"EDIT" => $comment["CAN_EDIT"],
				"MODERATE" => ($comment["CAN_SHOW"] == "Y" || $comment["CAN_HIDE"] == "Y" ? "Y" : "N"),
				"DELETE" => $comment["CAN_DELETE"],
			),
			"AFTER" => ""
		);

		if (IsModuleInstalled("mobile"))
		{
			$res["POST_MESSAGE_TEXT_MOBILE"] = (isset($comment["TextFormatedMobile"]) ? $comment["TextFormatedMobile"] : "");
			$res["BEFORE_ACTIONS_MOBILE"] = "";
			$res["AFTER_MOBILE"] = "";
		}

		$aditStyle = ($comment["AuthorIsAdmin"] == "Y" ? "blog-comment-admin" : "") .
			($comment["AuthorIsPostAuthor"] == "Y" ? "blog-comment-author" : "");
		if (!!$aditStyle)
		{
			$res["BEFORE_RECORD"] = "<div class='".$aditStyle."'>";
			$res["AFTER_RECORD"] = "</div>";
		}

		if ($arParams["SHOW_RATING"] == "Y")
		{
			ob_start();
			$GLOBALS["APPLICATION"]->IncludeComponent(
				"bitrix:rating.vote", $arParams["RATING_TYPE"],
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
			$res["BEFORE_ACTIONS"] = ob_get_clean();

			if (
				IntVal($arResult["ajax_comment"]) > 0
				&& IsModuleInstalled("mobile")
			) // for push&pull
			{
				ob_start();
				$GLOBALS["APPLICATION"]->IncludeComponent(
					"bitrix:rating.vote", "mobile_comment_".$arParams["RATING_TYPE"],
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
				$res["BEFORE_ACTIONS_MOBILE"] = ob_get_clean();
			}
		}
		if(!empty($arResult["arImages"][$comment["ID"]]))
		{
			ob_start();
			?>
			<div class="feed-com-files">
				<div class="feed-com-files-title"><?=GetMessage("BLOG_PHOTO")?></div>
				<div class="feed-com-files-cont">
					<?
					foreach($arResult["arImages"][$comment["ID"]] as $val)
					{
						?><span class="feed-com-files-photo"><img src="<?=$val["small"]?>" alt="" border="0" data-bx-image="<?=$val["full"]?>" data-bx-title="<?=$authorName?>"/></span><?
					}
					?>
				</div>
			</div>
			<?
			$res["AFTER"] = ob_get_clean();
			$res["CLASSNAME"] = "feed-com-block-uf";
			
			if (
				IntVal($arResult["ajax_comment"]) > 0
				&& IsModuleInstalled("mobile")
			) // for push&pull
			{
				ob_start();
				// render display for blog images

				?><?
				$res["AFTER_MOBILE"] = ob_get_clean();
			}
		}

		if($comment["COMMENT_PROPERTIES"]["SHOW"] == "Y")
		{
			ob_start();
			$eventHandlerID = AddEventHandler('main', 'system.field.view.file', Array('CBlogTools', 'blogUFfileShow'));
			foreach ($comment["COMMENT_PROPERTIES"]["DATA"] as $FIELD_NAME => $arPostField)
			{
				if(!empty($arPostField["VALUE"]))
				{
					$arPostField['POST_ID'] = $arParams['POST_DATA']['ID'];
					$arPostField['URL_TO_POST'] = str_replace('#source_post_id#', $arPostField['POST_ID'], $arResult['urlToPost']);
					$APPLICATION->IncludeComponent(
						"bitrix:system.field.view",
						$arPostField["USER_TYPE"]["USER_TYPE_ID"],
						array(
							"LAZYLOAD" => $arParams["LAZYLOAD"],
							"arUserField" => $arPostField
						), 
						null, 
						array("HIDE_ICONS"=>"Y")
					);
				}
			}
			if (
				$eventHandlerID !== false 
				&& intval($eventHandlerID) > 0
			)
			{
				RemoveEventHandler('main', 'system.field.view.file', $eventHandlerID);
			}
			$res["AFTER"] .= ob_get_clean();
			$res["CLASSNAME"] = "feed-com-block-uf";

			if (
				IntVal($arResult["ajax_comment"]) > 0
				&& IsModuleInstalled("mobile")
			) // for push&pull
			{
				ob_start();
				$eventHandlerID = AddEventHandler('main', 'system.field.view.file', Array('CBlogTools', 'blogUFfileShow'));
				foreach ($comment["COMMENT_PROPERTIES"]["DATA"] as $FIELD_NAME => $arPostField)
				{
					if(!empty($arPostField["VALUE"]))
					{
						$arPostField['POST_ID'] = $arParams['POST_DATA']['ID'];
						$arPostField['URL_TO_POST'] = str_replace('#source_post_id#', $arPostField['POST_ID'], $arResult['urlToPost']);
						$APPLICATION->IncludeComponent(
							"bitrix:system.field.view",
							$arPostField["USER_TYPE"]["USER_TYPE_ID"],
							array(
								"LAZYLOAD" => $arParams["LAZYLOAD"],
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
					&& intval($eventHandlerID) > 0
				)
				{
					RemoveEventHandler('main', 'system.field.view.file', $eventHandlerID);
				}
				$res["AFTER_MOBILE"] .= ob_get_clean();
			}
		}

		if($comment["CAN_EDIT"] == "Y")
		{
			ob_start();

			?><script>
				top.text<?=$comment["ID"]?> = text<?=$comment["ID"]?> = '<?=CUtil::JSEscape($comment["POST_TEXT"])?>';
				top.title<?=$comment["ID"]?> = title<?=$comment["ID"]?> = '<?=CUtil::JSEscape($comment["TITLE"])?>';
				top.arComFiles<?=$comment["ID"]?> = [];<?

				if ($comment["COMMENT_PROPERTIES"]["DATA"])
				{
					foreach($comment["COMMENT_PROPERTIES"]["DATA"] as $userField)
					{
						if (empty($userField["VALUE"]))
							continue;
						else if ($userField["USER_TYPE_ID"] == "disk_file")
						{
							?>
							top.arComDFiles<?=$comment["ID"]?> = BX.util.array_merge((top.arComDFiles<?=$comment["ID"]?> || []), <?=CUtil::PhpToJSObject($userField["VALUE"])?>);
							<?
						}
						else if ($userField["USER_TYPE_ID"] == "webdav_element")
						{
							?>
							top.arComDocs<?=$comment["ID"]?> = BX.util.array_merge((top.arComDocs<?=$comment["ID"]?> || []), <?=CUtil::PhpToJSObject($userField["VALUE"])?>);
							<?
						}
						else if ($userField["USER_TYPE_ID"] == "file")
						{
							?>
							top.arComFilesUf<?=$comment["ID"]?> = BX.util.array_merge((top.arComDocs<?=$comment["ID"]?> || []), <?=CUtil::PhpToJSObject($userField["VALUE"])?>);
							<?
						}
					}
				}

				if(!empty($comment["showedImages"]))
				{
					foreach($comment["showedImages"] as $imgId)
					{
						if(!empty($arResult["Images"][$imgId]))
						{
							?>top.arComFiles<?=$comment["ID"]?>.push({
								id : '<?=$imgId?>',
								name : '<?=CUtil::JSEscape($arResult["Images"][$imgId]["fileName"])?>',
								type: 'image',
								src: '<?=CUtil::JSEscape($arResult["Images"][$imgId]["source"]["src"])?>',
								thumbnail: '<?=CUtil::JSEscape($arResult["Images"][$imgId]["src"])?>',
								isImage: true
							});<?
						}
					}
				}
			?></script><?
			$res["AFTER"] .= ob_get_clean();
		}
		$arResult["RECORDS"][$comment["ID"]] = $res;
	}
}
?>