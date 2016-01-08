<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/components/bitrix/socialnetwork.log.ex/templates/.default/style.css');
$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/components/bitrix/socialnetwork.blog.blog/templates/.default/style.css');
if (!$arResult["bFromList"])
	$APPLICATION->AddHeadScript("/bitrix/components/bitrix/socialnetwork.log.ex/templates/.default/script.js");

$ajax_page = $APPLICATION->GetCurPageParam("", array("logajax", "bxajaxid", "logout"));
CJSCore::Init(array('ajax', 'viewer', 'tooltip', 'popup'));

?><script>
	BX.message({
		sonetBPSetPath: '<?=CUtil::JSEscape("/bitrix/components/bitrix/socialnetwork.log.ex/ajax.php")?>',
		sonetBPSiteId: '<?=CUtil::JSEscape(SITE_ID)?>'
		<?
		if (!$arResult["bFromList"])
		{
			?>,
			sonetLMenuFavoritesTitleY: '<?=GetMessageJS("BLOG_POST_MENU_TITLE_FAVORITES_Y")?>',
			sonetLMenuFavoritesTitleN: '<?=GetMessageJS("BLOG_POST_MENU_TITLE_FAVORITES_N")?>',
			sonetLESetPath: '<?=CUtil::JSEscape('/bitrix/components/bitrix/socialnetwork.log.entry/ajax.php')?>',
			sonetLSessid: '<?=bitrix_sessid_get()?>',
			sonetLLangId: '<?=CUtil::JSEscape(LANGUAGE_ID)?>',
			sonetLSiteId: '<?=CUtil::JSEscape(SITE_ID)?>'
			<?
		}

		if ($arResult["canDelete"])
		{
			?>,
			sonetBPDeletePath: '<?=CUtil::JSEscape($arResult["urlToDelete"])?>'
			<?
		}
		?>
	});
</script><?

?><div class="feed-wrap"><?
if(strlen($arResult["MESSAGE"])>0)
{
	?><div class="feed-add-successfully">
		<span class="feed-add-info-text"><span class="feed-add-info-icon"></span><?=$arResult["MESSAGE"]?></span>
	</div><?
}
if(strlen($arResult["ERROR_MESSAGE"])>0)
{
	?><div class="feed-add-error">
		<span class="feed-add-info-text"><span class="feed-add-info-icon"></span><?=$arResult["ERROR_MESSAGE"]?></span>
	</div><?
}
if(strlen($arResult["FATAL_MESSAGE"])>0)
{
	if(!$arResult["bFromList"])
	{
		?><div class="feed-add-error">
			<span class="feed-add-info-text"><span class="feed-add-info-icon"></span><?=$arResult["FATAL_MESSAGE"]?></span>
		</div><?
	}
}
elseif(strlen($arResult["NOTE_MESSAGE"])>0)
{
	?><div class="feed-add-successfully">
		<span class="feed-add-info-text"><span class="feed-add-info-icon"></span><?=$arResult["NOTE_MESSAGE"]?></span>
	</div><?
}
else
{
	if(!empty($arResult["Post"])>0)
	{
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
				"HTML_ID" => "user".$arResult["Post"]["ID"],
			),
			false,
			array("HIDE_ICONS" => "Y")
		);

		$className = "feed-post-block";

		if($arResult["Post"]["new"] == "Y")
		{
			$className .= " feed-post-block-new";
		}

		if ($arResult["Post"]["IS_IMPORTANT"])
		{
			$className .= " feed-imp-post";
		}

		if (
			$arResult["Post"]["HAS_TAGS"] == "Y"
			|| (
				isset($arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_FILE"])
				&& !empty($arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_FILE"]["VALUE"])
			)
			|| (
				isset($arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_DOC"])
				&& !empty($arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_DOC"]["VALUE"])
			)
		)
		{
			$className .= " feed-post-block-files";
		}

		if (
			array_key_exists("FOLLOW", $arParams)
			&& strlen($arParams["FOLLOW"]) > 0
			&& intval($arParams["LOG_ID"]) > 0
		):
			?><script>
			BX.message({
				sonetBPFollowY: '<?=GetMessageJS("BLOG_POST_FOLLOW_Y")?>',
				sonetBPFollowN: '<?=GetMessageJS("BLOG_POST_FOLLOW_N")?>'
			});
			</script><?
		endif;
		?>
		<script>
		BX.viewElementBind(
			'blg-post-img-<?=$arResult["Post"]["ID"]?>',
			{showTitle: true},
			function(node){
				return BX.type.isElementNode(node) && (node.getAttribute('data-bx-viewer') || node.getAttribute('data-bx-image'));
			}
		);

		<?
		$postDest = array();
		foreach($arResult["Post"]["SPERM"] as $type => $ar)
		{
			$typeText = "users";
			if($type == "SG")
				$typeText = "sonetgroups";
			elseif($type == "G")
				$typeText = "groups";
			elseif($type == "DR")
				$typeText = "department";

			foreach($ar as $id => $val)
			{
				if($type == "U" && IntVal($val["ID"]) <= 0)
					$postDest[] = array("id" => "UA", "name" => (IsModuleInstalled("intranet") ? GetMessage("BLOG_DESTINATION_ALL") : GetMessage("BLOG_DESTINATION_ALL_BSM")), "type" => "groups");
				else
					$postDest[] = array("id" => $type.$id, "name" => $val["NAME"], "type" => $typeText, "entityId" => $type.$id);
			}
		}
		?>
		var postDest<?=$arResult["Post"]["ID"]?> = <?=CUtil::PhpToJSObject($postDest)?>;

		</script>
		<div class="<?=$className?>" id="blg-post-<?=$arResult["Post"]["ID"]?>">
			<?
			$aditStyles = ($arResult["Post"]["hidden"] == "Y" ? " feed-hidden-post" : "");

			if (array_key_exists("USER_ID", $arParams) && intval($arParams["USER_ID"]) > 0)
				$aditStyles .= " sonet-log-item-createdby-".$arParams["USER_ID"];

			if (array_key_exists("ENTITY_TYPE", $arParams) && strlen($arParams["ENTITY_TYPE"]) > 0 && array_key_exists("ENTITY_ID", $arParams) && intval($arParams["ENTITY_ID"]) > 0 )
			{
				$aditStyles .= " sonet-log-item-where-".$arParams["ENTITY_TYPE"]."-".$arParams["ENTITY_ID"]."-all";
				if (array_key_exists("EVENT_ID", $arParams) && strlen($arParams["EVENT_ID"]) > 0)
				{
					$aditStyles .= " sonet-log-item-where-".$arParams["ENTITY_TYPE"]."-".$arParams["ENTITY_ID"]."-".str_replace("_", '-', $arParams["EVENT_ID"]);
					if (array_key_exists("EVENT_ID_FULLSET", $arParams) && strlen($arParams["EVENT_ID_FULLSET"]) > 0)
						$aditStyles .= " sonet-log-item-where-".$arParams["ENTITY_TYPE"]."-".$arParams["ENTITY_ID"]."-".str_replace("_", '-', $arParams["EVENT_ID_FULLSET"]);
				}
			}
			?>
			<div class="feed-post-cont-wrap<?=$aditStyles?>" id="blg-post-img-<?=$arResult["Post"]["ID"]?>">
				<div class="feed-user-avatar<?=(isset($arResult["arUser"]["PERSONAL_PHOTO_resized"]["src"]) && strlen($arResult["arUser"]["PERSONAL_PHOTO_resized"]["src"]) > 0 ? " feed-user-avatar-white" : "")?>"><?
					?><img src="<?=(isset($arResult["arUser"]["PERSONAL_PHOTO_resized"]["src"]) && strlen($arResult["arUser"]["PERSONAL_PHOTO_resized"]["src"]) > 0 ? $arResult["arUser"]["PERSONAL_PHOTO_resized"]["src"] : "/bitrix/images/1.gif")?>" width="<?=$arParams["AVATAR_SIZE"]?>" height="<?=$arParams["AVATAR_SIZE"]?>"><?
				?></div>
				<div class="feed-post-title-block"><?
					$anchor_id = $arResult["Post"]["ID"];
					$arTmpUser = array(
							"NAME" => $arResult["arUser"]["~NAME"],
							"LAST_NAME" => $arResult["arUser"]["~LAST_NAME"],
							"SECOND_NAME" => $arResult["arUser"]["~SECOND_NAME"],
							"LOGIN" => $arResult["arUser"]["~LOGIN"],
							"NAME_LIST_FORMATTED" => "",
						);

					if($arParams["SEO_USER"] == "Y"):
						?><noindex><?
					endif;
					?><a class="feed-post-user-name<?=(array_key_exists("isExtranet", $arResult["arUser"]) && $arResult["arUser"]["isExtranet"] ? " feed-post-user-name-extranet" : "")?>" id="bp_<?=$anchor_id?>" href="<?=$arResult["arUser"]["url"]?>" bx-post-author-id="<?=$arResult["arUser"]["ID"]?>"><?=CUser::FormatName($arParams["NAME_TEMPLATE"], $arTmpUser, ($arParams["SHOW_LOGIN"] != "N" ? true : false))?></a><?
					?><script type="text/javascript">
						BX.tooltip('<?=$arResult["arUser"]["ID"]?>', "bp_<?=$anchor_id?>", "<?=CUtil::JSEscape($ajax_page)?>");
					</script><?
					if($arParams["SEO_USER"] == "Y"):
						?></noindex><?
					endif;
					if(!empty($arResult["Post"]["SPERM"]))
					{
						?><span class="feed-add-post-destination-icon"><span style="position: absolute; left: -3000px; overflow: hidden;">&nbsp;-&gt;&nbsp;</span></span><?
						$cnt = count($arResult["Post"]["SPERM"]["U"]) + count($arResult["Post"]["SPERM"]["SG"]) + count($arResult["Post"]["SPERM"]["DR"]);
						$i = 0;
						if(!empty($arResult["Post"]["SPERM"]["U"]))
						{
							foreach($arResult["Post"]["SPERM"]["U"] as $id => $val)
							{
								$i++;
								if($i == 4)
								{
									$more_cnt = $cnt + intval($arResult["Post"]["SPERM_HIDDEN"]) - 3;
									if (
										($more_cnt % 100) > 10
										&& ($more_cnt % 100) < 20
									)
										$suffix = 5;
									else
										$suffix = $more_cnt % 10;
										
									?><a href="javascript:void(0)" onclick="showHiddenDestination('<?=$arResult["Post"]["ID"]?>', this)" class="feed-post-link-new"><?=GetMessage("BLOG_DESTINATION_MORE_".$suffix, Array("#NUM#" => $more_cnt))?></a><span id="blog-destination-hidden-<?=$arResult["Post"]["ID"]?>" style="display:none;"><?
								}
								if($i != 1)
									echo ", ";
								if($val["NAME"] != "All")
								{
									$anchor_id = $arResult["Post"]["ID"]."_".$id;
									?><a id="dest_<?=$anchor_id?>" href="<?=$val["URL"]?>" class="feed-add-post-destination-new<?=(array_key_exists("IS_EXTRANET", $val) && $val["IS_EXTRANET"] == "Y" ? " feed-add-post-destination-new-extranet" : "")?>"><?=$val["NAME"]?></a><script type="text/javascript">BX.tooltip('<?=$val["ID"]?>', "dest_<?=$anchor_id?>", "<?=CUtil::JSEscape($ajax_page)?>");</script><?
								}
								else
								{
									if (strlen($val["URL"]) > 0)
									{
										?><a href="<?=$val["URL"]?>" class="feed-add-post-destination-new"><?=(IsModuleInstalled("intranet") ? GetMessage("BLOG_DESTINATION_ALL") : GetMessage("BLOG_DESTINATION_ALL_BSM"))?></a><?
									}
									else
									{
										?><span class="feed-add-post-destination-new"><?=(IsModuleInstalled("intranet") ? GetMessage("BLOG_DESTINATION_ALL") : GetMessage("BLOG_DESTINATION_ALL_BSM"))?></span><?
									}
								}
							}
						}
						if(!empty($arResult["Post"]["SPERM"]["SG"]))
						{
							foreach($arResult["Post"]["SPERM"]["SG"] as $id => $val)
							{
								$i++;
								if($i == 4)
								{
									$more_cnt = $cnt + intval($arResult["Post"]["SPERM_HIDDEN"]) - 3;
									if (
										($more_cnt % 100) > 10
										&& ($more_cnt % 100) < 20
									)
										$suffix = 5;
									else
										$suffix = $more_cnt % 10;

									?><a href="javascript:void(0)" onclick="showHiddenDestination('<?=$arResult["Post"]["ID"]?>', this)" class="feed-post-link-new"><?=GetMessage("BLOG_DESTINATION_MORE_".$suffix, Array("#NUM#" => $more_cnt))?></a><span id="blog-destination-hidden-<?=$arResult["Post"]["ID"]?>" style="display:none;"><?
								}
								if($i != 1)
									echo ", ";
								?><a href="<?=$val["URL"]?>" class="feed-add-post-destination-new<?=(array_key_exists("IS_EXTRANET", $val) && $val["IS_EXTRANET"] == "Y" ? " feed-add-post-destination-new-extranet" : "")?>"><?=$val["NAME"]?></a><?
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
									if (
										($more_cnt % 100) > 10
										&& ($more_cnt % 100) < 20
									)
										$suffix = 5;
									else
										$suffix = $more_cnt % 10;

									?><a href="javascript:void(0)" onclick="showHiddenDestination('<?=$arResult["Post"]["ID"]?>', this)" class="feed-post-link-new"><?=GetMessage("BLOG_DESTINATION_MORE_".$suffix, Array("#NUM#" => $more_cnt))?></a><span id="blog-destination-hidden-<?=$arResult["Post"]["ID"]?>" style="display:none;"><?
								}
								if($i != 1)
									echo ", ";

								if (
									strlen($val["URL"]) > 0
									&& !$arResult["bExtranetSite"]
								)
								{
									?><a href="<?=$val["URL"]?>" class="feed-add-post-destination-new"><?=$val["NAME"]?></a><?
								}
								else
								{
									?><span class="feed-add-post-destination-new"><?=$val["NAME"]?></span><?
								}
							}
						}

						if (
							isset($arResult["Post"]["SPERM_HIDDEN"])
							&& intval($arResult["Post"]["SPERM_HIDDEN"]) > 0
						)
						{
							if (
								($arResult["Post"]["SPERM_HIDDEN"] % 100) > 10
								&& ($arResult["Post"]["SPERM_HIDDEN"] % 100) < 20
							)
								$suffix = 5;
							else
								$suffix = $arResult["Post"]["SPERM_HIDDEN"] % 10;

							?><span class="feed-add-post-destination-new">&nbsp;<?=GetMessage("BLOG_DESTINATION_HIDDEN_".$suffix, Array("#NUM#" => intval($arResult["Post"]["SPERM_HIDDEN"])))?></span><?
						}

						if($i > 3)
							echo "</span>";
					}

					if(strlen($arResult["urlToEdit"]) > 0)
					{
						?><a href="<?=$arResult["urlToEdit"]?>" title="<?=GetMessage("BLOG_BLOG_BLOG_EDIT")?>"><span class="feed-destination-edit" onclick="BX.addClass(this, 'feed-destination-edit-pressed');"></span></a>
						<?
					}

					if($arResult["Post"]["MICRO"] != "Y")
					{
						?><div class="feed-post-item"><a class="feed-post-title" href="<?=$arResult["Post"]["urlToPost"]?>"><?=$arResult["Post"]["TITLE"]?></a></div><?
					}
				?></div>
				<div class="feed-post-text-block<?=($arResult["Post"]["IS_IMPORTANT"] ? " feed-info-block" : "")?>" id="blog_post_outer_<?=$arResult["Post"]["ID"]?>">
					<div class="<?if($arResult["bFromList"]):?>feed-post-text-block-inner<?endif;?>">
						<div class="feed-post-text-block-inner-inner" id="blog_post_body_<?=$arResult["Post"]["ID"]?>"><?=$arResult["Post"]["textFormated"]?><?

						if (
							$arResult["POST_PROPERTIES"]["SHOW"] == "Y"
							&& array_key_exists("UF_BLOG_POST_VOTE", $arResult["POST_PROPERTIES"]["DATA"])
						)
						{
							$arPostField = $arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_VOTE"];
							if(!empty($arPostField["VALUE"]))
							{
								$arPostField['BLOG_DATE_PUBLISH'] = $arResult["Post"]["DATE_PUBLISH"];
								?><?$APPLICATION->IncludeComponent(
									"bitrix:system.field.view",
									$arPostField["USER_TYPE"]["USER_TYPE_ID"],
									array(
										"LAZYLOAD" => $arParams["LAZYLOAD"],
										"arUserField" => $arPostField,
										"arAddField" => array(
											"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"], 
											"PATH_TO_USER" => $arParams["~PATH_TO_USER"]
										)
									), 
									null, 
									array("HIDE_ICONS"=>"Y")
								);?><?
							}
							unset($arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_VOTE"]);
						}

						if ($arResult["Post"]["CUT"] == "Y")
						{
							?><div><a class="blog-postmore-link" href="<?=$arResult["Post"]["urlToPost"]?>"><?=GetMessage("BLOG_BLOG_BLOG_MORE")?></a></div><?
						}
						if (!empty($arResult["Post"]["IMPORTANT"]))
						{
							?><div class="feed-imp-post-footer"><?
								?><span class="feed-imp-btn-main-wrap"><?
									if ($arResult["Post"]["IMPORTANT"]["IS_READ"] == "Y")
									{
										?><span class="feed-imp-btn-wrap">
											<span class="have-read-text-block"><i></i><?=GetMessage('BLOG_ALREADY_READ')?><span class="feed-imp-post-footer-comma">,</span></span>
										</span><?
									}
									else
									{
										?><span class="feed-imp-btn-wrap"><?
											?><a href="<?=htmlspecialcharsbx($arResult["arUser"]["urlToPostImportant"])?>" <?
												?>class="webform-button webform-button-create" <?
												?>bx-blog-post-id="<?=$arResult["Post"]["ID"]?>" <?
												?>id="blog-post-readers-btn-<?=$arResult["Post"]["ID"]?>" <?
												?>onclick="new SBPImpPost(this); return false;"><?
												?><span class="webform-button-left"></span><?
												?><span class="webform-button-text"><?=GetMessage(trim("BLOG_READ_".$arResult["Post"]["IMPORTANT"]["USER"]["PERSONAL_GENDER"]))?></span><?
												?><span class="webform-button-right"></span><?
											?></a><?
										?></span><?
									}
								?></span><?
								?><span <?
									?>id="blog-post-readers-count-<?=$arResult["Post"]["ID"]?>" <?
									?>class="feed-imp-post-footer-text"<?
									if($arResult["Post"]["IMPORTANT"]["COUNT"]<=0)
									{
										?> style="display:none;"<?
									}
									?>><?=GetMessage("BLOG_USERS_ALREADY_READ")?> <a class="feed-imp-post-user-link" href="javascript:void(0);"><?
									?><span><?=$arResult["Post"]["IMPORTANT"]["COUNT"]?></span> <?=GetMessage("BLOG_READERS")?></a></span>
							</div>
							<script type="text/javascript">
								BX.ready(function(){
									var sbpimp<?=$arResult["Post"]["ID"]?> =  new SBPImpPostCounter(
										BX('blog-post-readers-count-<?=$arResult["Post"]["ID"]?>'),
										<?=$arResult["Post"]["ID"]?>, { 'pathToUser' : '<?=CUtil::JSEscape($arParams["~PATH_TO_USER"])?>', 'nameTemplate' : '<?=CUtil::JSEscape($arParams["NAME_TEMPLATE"])?>' }
									);
									BX.addCustomEvent(BX('blog-post-readers-btn-<?=$arResult["Post"]["ID"]?>'), "onInit", BX.proxy(sbpimp<?=$arResult["Post"]["ID"]?>.click, sbpimp<?=$arResult["Post"]["ID"]?>));
									BX.message({'BLOG_ALREADY_READ' : '<?=GetMessageJS('BLOG_ALREADY_READ')?>'});
								});
							</script><?
						}
						?></div>
					</div><?
					if($arResult["bFromList"]):
						?><div class="feed-post-text-more" onclick="showBlogPost('<?=$arResult["Post"]["ID"]?>', this)" id="blog_post_more_<?=$arResult["Post"]["ID"]?>"><?
							?><div class="feed-post-text-more-but"></div><?
						?></div><?
						?><script>
							if (typeof arMoreButtonID == 'undefined')
							{
								var arMoreButtonID = [];
							}
							arMoreButtonID[arMoreButtonID.length] = { 
								'outerBlockID' : 'blog_post_outer_<?=$arResult["Post"]["ID"]?>',
								'bodyBlockID' : 'blog_post_body_<?=$arResult["Post"]["ID"]?>',
								'moreButtonBlockID' : 'blog_post_more_<?=$arResult["Post"]["ID"]?>'
							};
						</script><?
					endif;
				?></div><?

				if(!empty($arResult["images"]))
				{
					?><div class="feed-com-files">
						<div class="feed-com-files-title"><?=GetMessage("BLOG_PHOTO")?></div>
						<div class="feed-com-files-cont"><?
							foreach($arResult["images"] as $val)
							{
								?><span class="feed-com-files-photo"><img src="<?=$val["small"]?>" alt="" border="0" data-bx-image="<?=$val["full"]?>" /></span><?
							}
						?></div>
					</div><?
				}

				if($arResult["POST_PROPERTIES"]["SHOW"] == "Y")
				{
					$eventHandlerID = false;
					$eventHandlerID = AddEventHandler('main', 'system.field.view.file', Array('CBlogTools', 'blogUFfileShow'));
					foreach ($arResult["POST_PROPERTIES"]["DATA"] as $FIELD_NAME => $arPostField)
					{
						if(!empty($arPostField["VALUE"]))
						{
							$arPostField['BLOG_DATE_PUBLISH'] = $arResult["Post"]["DATE_PUBLISH"];
							$arPostField['URL_TO_POST'] = $arResult["Post"]["urlToPost"];
							$arPostField['POST_ID'] = $arResult["Post"]['ID'];
							?><?$APPLICATION->IncludeComponent(
								"bitrix:system.field.view",
								$arPostField["USER_TYPE"]["USER_TYPE_ID"],
								array(
									"LAZYLOAD" => $arParams["LAZYLOAD"],
									"arUserField" => $arPostField,
									"arAddField" => array(
										"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"], 
										"PATH_TO_USER" => $arParams["~PATH_TO_USER"]
									)
								), null, array("HIDE_ICONS"=>"Y")
							);?><?
						}
					}
					if ($eventHandlerID !== false && ( intval($eventHandlerID) > 0 ))
						RemoveEventHandler('main', 'system.field.view.file', $eventHandlerID);
				}

				if(!empty($arResult["Category"]))
				{
					?><div class="feed-com-tags-block">
						<noindex>
						<div class="feed-com-files-title"><?=GetMessage("BLOG_BLOG_BLOG_CATEGORY")?></div>
						<div class="feed-com-files-cont"><?
							$i=0;
							foreach($arResult["Category"] as $v)
							{
								if($i!=0)
									echo ",";
								?> <a href="<?=$v["urlToCategory"]?>" rel="nofollow" class="feed-com-tag"><?=$v["NAME"]?></a><?
								$i++;
							}
						?></div>
						</noindex>
					</div><?
				}

				if (!empty($arResult["GRATITUDE"]))
				{
					$grat_users_count = count($arResult["GRATITUDE"]["USERS_FULL"]);

					?><div class="feed-grat-block feed-info-block<?=($grat_users_count > 4 ? " feed-grat-block-small" : " feed-grat-block-large")?>"><?

					if ($grat_users_count <= 4)
					{
						?><span class="feed-workday-left-side"><?
							?><div class="feed-grat-img<?=(is_array($arResult["GRATITUDE"]["TYPE"]) ? " feed-grat-img-".htmlspecialcharsbx($arResult["GRATITUDE"]["TYPE"]["XML_ID"]) : "")?>"></div><?
							?><div class="feed-grat-block-arrow"></div><?
							?><div class="feed-user-name-wrap-outer"><?
								foreach($arResult["GRATITUDE"]["USERS_FULL"] as $arGratUser)
								{
									$anchor_id = 'post_grat_'.$arGratUser["ID"].'_'.RandString(5);
									?><span class="feed-user-name-wrap"><?
										?><div class="feed-user-avatar<?=(isset($arGratUser['AVATAR_SRC']) && strlen($arGratUser['AVATAR_SRC']) > 0 ? " feed-user-avatar-white" : "")?>"><?
											?><img src="<?=(isset($arGratUser['AVATAR_SRC']) && strlen($arGratUser['AVATAR_SRC']) > 0 ? $arGratUser['AVATAR_SRC'] : "/bitrix/images/1.gif")?>" <?
												?>width="<?=(isset($arGratUser['AVATAR_SIZE']) && intval($arGratUser['AVATAR_SIZE']) > 0 ? intval($arGratUser['AVATAR_SIZE']) : '50')?>" <?
												?>height="<?=(isset($arGratUser['AVATAR_SIZE']) && intval($arGratUser['AVATAR_SIZE']) > 0 ? intval($arGratUser['AVATAR_SIZE']) : '50')?>"><?
										?></div><?
										?><div class="feed-user-name-wrap-inner"><?
											?><a class="feed-workday-user-name" href="<?=$arGratUser['URL']?>" id="<?=$anchor_id?>"><?=CUser::FormatName($arParams['NAME_TEMPLATE'], $arGratUser)?></a><?
											?><span class="feed-workday-user-position"><?=htmlspecialcharsbx($arGratUser['WORK_POSITION'])?></span><?
										?></div><?
									?></span><?
									?><script type="text/javascript">BX.tooltip('<?=$arGratUser['ID']?>', '<?=$anchor_id?>', '<?=$APPLICATION->GetCurPageParam("", array("logajax", "bxajaxid", "logout"));?>');</script><?
								}
							?></div><?
						?></span><?
					}
					else
					{
						?><div class="feed-grat-small-left"><?
							?><div class="feed-grat-img<?=(is_array($arResult["GRATITUDE"]["TYPE"]) ? " feed-grat-img-".htmlspecialcharsbx($arResult["GRATITUDE"]["TYPE"]["XML_ID"]) : "")?>"></div><?
							?><div class="feed-grat-block-arrow"></div><?
						?></div><?
						?><div class="feed-grat-small-block-names"><?
							foreach($arResult["GRATITUDE"]["USERS_FULL"] as $arGratUser)
							{
								$anchor_id = 'post_grat_'.$arGratUser["ID"].'_'.RandString(5);
								?><span class="feed-user-name-wrap"><?
									?><div class="feed-user-avatar"><?
										?><img src="<?=(isset($arGratUser['AVATAR_SRC']) && strlen($arGratUser['AVATAR_SRC']) > 0 ? $arGratUser['AVATAR_SRC'] : "/bitrix/images/1.gif")?>" <?
											?>width="<?=(isset($arGratUser['AVATAR_SIZE']) && intval($arGratUser['AVATAR_SIZE']) > 0 ? intval($arGratUser['AVATAR_SIZE']) : '26')?>" <?
											?>height="<?=(isset($arGratUser['AVATAR_SIZE']) && intval($arGratUser['AVATAR_SIZE']) > 0 ? intval($arGratUser['AVATAR_SIZE']) : '26')?>"><?
									?></div><?
									?><a class="feed-workday-user-name" href="<?=$arGratUser['URL']?>" id="<?=$anchor_id?>"><?=CUser::FormatName($arParams['NAME_TEMPLATE'], $arGratUser)?></a><?
									?><!--<span class="feed-workday-user-position"><?=htmlspecialcharsbx($arGratUser['WORK_POSITION'])?></span>--><?
								?></span><?
								?><script type="text/javascript">BX.tooltip('<?=$arGratUser['ID']?>', '<?=$anchor_id?>', '<?=$APPLICATION->GetCurPageParam("", array("logajax", "bxajaxid", "logout"));?>');</script><?
							}
						?></div><?
					}
					?></div><?
				}

				if($arResult["POST_PROPERTIES"]["SHOW"] == "Y")
				{
					foreach ($arResult["POST_PROPERTIES"]["DATA"] as $FIELD_NAME => $arPostField)
					{
						if(in_array($FIELD_NAME, $arParams["POST_PROPERTY_SOURCE"]) && !empty($arPostField["VALUE"]))
						{
							echo "<div><b>".$arPostField["EDIT_FORM_LABEL"].":</b>&nbsp;";
							?><?$APPLICATION->IncludeComponent(
								"bitrix:system.field.view",
								$arPostField["USER_TYPE"]["USER_TYPE_ID"],
								array(
									"arUserField" => $arPostField,
								), 
								null, 
								array("HIDE_ICONS"=>"Y")
							);?><?
							echo "</div>";
						}
					}
				}
				?><div id="blg-post-destcont-<?=$arResult["Post"]["ID"]?>"></div><?
				?><div class="feed-post-informers" id="blg-post-inform-<?=$arResult["Post"]["ID"]?>"><?

					if(!in_array($arParams["TYPE"], array("DRAFT", "MODERATION"))):
						$bHasComments = (IntVal($arResult["PostSrc"]["NUM_COMMENTS"]) > 0);
						?><span class="feed-inform-comments"><?
							?><a href="<?=$arResult["Post"]["urlToPost"]?>" id="blog-post-addc-link-<?=$arResult["Post"]["ID"]?>"<?=(!$bHasComments ? " style=\"display:none;\"" : "")?>><?=GetMessage("BLOG_COMMENTS")?></a><?
							if ($arResult["CanComment"])
							{
								?><a href="javascript:void(0);" id="blog-post-addc-add-<?=$arResult["Post"]["ID"]?>"<?=($bHasComments ? " style=\"display:none;\"" : "")?>><?=GetMessage("BLOG_COMMENTS_ADD")?></a><?
							}
						?></span><?
					endif;

					if ($arParams["SHOW_RATING"] == "Y"):
						?><span class="feed-inform-ilike"><?
						$APPLICATION->IncludeComponent(
							"bitrix:rating.vote", $arParams["RATING_TYPE"],
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
							),
							$component,
							array("HIDE_ICONS" => "Y")
						);?></span><?
					endif;

					if (
						array_key_exists("FOLLOW", $arParams)
						&& strlen($arParams["FOLLOW"]) > 0
						&& intval($arParams["LOG_ID"]) > 0
					):
						?><span class="feed-inform-follow" data-follow="<?=($arParams["FOLLOW"] == "Y" ? "Y" : "N")?>" id="log_entry_follow_<?=intval($arParams["LOG_ID"])?>" onclick="__blogPostSetFollow(<?=intval($arParams["LOG_ID"])?>)"><a href="javascript:void(0);"><?=GetMessage("BLOG_POST_FOLLOW_".($arParams["FOLLOW"] == "Y" ? "Y" : "N"))?></a></span><?
					endif;

					if (
						$GLOBALS["USER"]->IsAuthorized()
						&& !in_array($arParams["TYPE"], array("DRAFT", "MODERATION"))
						&& IntVal($arParams["LOG_ID"]) > 0
					)
					{
						$bFavorites = (intval($arParams["FAVORITES_USER_ID"]) > 0);
						$arParams["ADIT_MENU"][0] = Array(
							"text_php" => GetMessage($bFavorites ? "BLOG_POST_MENU_TITLE_FAVORITES_Y" : "BLOG_POST_MENU_TITLE_FAVORITES_N"),
							"onclick" => "function(e) { __logChangeFavorites('".$arParams["LOG_ID"]."', 'log_entry_favorites_' + ".$arParams["LOG_ID"].", '".($bFavorites ? "N" : "Y")."', true); return BX.PreventDefault(e);}"
						);
					}

					if(!in_array($arParams["TYPE"], array("DRAFT", "MODERATION")))
					{
						$serverName = (CMain::IsHTTPS() ? "https" : "http")."://".((defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME) > 0) ? SITE_SERVER_NAME : COption::GetOptionString("main", "server_name", ""));
						$arParams["ADIT_MENU"][1] = Array(
							"text_php" => GetMessage("BLOG_HREF"),
							"href" => CUtil::JSEscape($arResult["Post"]["urlToPost"]),
							"class" => "feed-entry-popup-menu-link"
						);
						$arParams["ADIT_MENU"][2] = Array(
							"text_php" => GetMessage("BLOG_LINK"),
							"span_id" => 'post-menu-'.$arParams["LOG_ID"].'-link-text',
							"onclick" => "function(e) { showMenuLinkInput('".$arParams["LOG_ID"]."', '".$serverName.CUtil::JSEscape($arResult["Post"]["urlToPost"])."'); return BX.PreventDefault(e); }",
							"class" => "feed-entry-popup-menu-link"
						);

						if($USER->IsAuthorized())
							$arParams["ADIT_MENU"][3] = Array(
								"text_php" => GetMessage("BLOG_SHARE"),
								"onclick" => "function() {showSharing(".$arResult["Post"]["ID"].", ".$arResult["Post"]["AUTHOR_ID"]."); this.popupWindow.close();}",
								);
						if(!$arResult["bFromList"] && strlen($arResult["urlToHide"]) > 0)
							$arParams["ADIT_MENU"][7] = Array(
								"text_php" => GetMessage("BLOG_MES_HIDE"),
								"onclick" => "function() { if(confirm('".GetMessage("BLOG_MES_HIDE_POST_CONFIRM")."')) window.location='".$arResult["urlToHide"]."'; this.popupWindow.close();}"
							);

						if($arResult["canDelete"] == "Y")
							$arParams["ADIT_MENU"][8] = Array(
								"text_php" => GetMessage("BLOG_BLOG_BLOG_DELETE"),
								"onclick" => "function() { if(confirm('".GetMessage("BLOG_MES_DELETE_POST_CONFIRM")."')) ".($arResult["bFromList"] ? "deleteBlogPost('".$arResult["Post"]["ID"]."');" : " window.location='".$arResult["urlToDelete"]."';")." this.popupWindow.close();}",
								);
					}
					if(strlen($arResult["urlToEdit"]) > 0)
						$arParams["ADIT_MENU"][4] = Array(
							"text_php" => GetMessage("BLOG_BLOG_BLOG_EDIT"),
							"href" => CUtil::JSEscape($arResult["urlToEdit"]),
						);

					if (!empty($arParams["ADIT_MENU"]))
					{
						ksort($arParams["ADIT_MENU"]);

						?><a href="#" onclick="
							BX.PopupMenu.destroy('blog-post-<?=$arResult["Post"]["ID"]?>');
							BX.PopupMenu.show('blog-post-<?=$arResult["Post"]["ID"]?>', this, [
								<?
								$bFirst = true;
								foreach($arParams["ADIT_MENU"] as $val)
								{
									if($bFirst)
										$bFirst = false;
									else
										echo ", ";
									echo "{text : ";
									if(strlen($val["text"]) > 0)
										echo "BX.message('".$val["text"]."')";
									else
									{
										if (strlen($val["span_id"]) > 0)
											echo "'<span id=\'".$val["span_id"]."\'>".$val["text_php"]."</span>'";
										else
											echo "'".$val["text_php"]."'";
									}
									if(strlen($val["onclick"]) > 0)
										echo ", onclick: ".$val["onclick"];
									else
										echo ", href: '".$val["href"]."'";
									echo ", className: 'blog-post-popup-menu feed-entry-popup-menu".(!empty($val["class"]) ? " ".$val["class"] : "")."'}";
								}
								?>
							],
							{
								offsetLeft: -14,
								offsetTop: 4,
								lightShadow: false,
								angle: {position: 'top', offset: 50}
								<? if (intval($arParams["LOG_ID"]) > 0)
								{
									?>
									, events : {
										onPopupShow : function(ob)
										{
											if (BX('log_entry_favorites_' + <?=$arParams["LOG_ID"]?>))
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
													if (BX.hasClass(BX('log_entry_favorites_' + <?=$arParams["LOG_ID"]?>), 'feed-post-important-switch-active'))
														BX(favoritesMenuItem).innerHTML = BX.message('sonetLMenuFavoritesTitleY');
													else
														BX(favoritesMenuItem).innerHTML = BX.message('sonetLMenuFavoritesTitleN');
												}
											}

											if (BX('post-menu-<?=$arParams["LOG_ID"]?>-link'))
											{
												var linkMenuItem = BX.findChild(ob.popupContainer, {className: 'feed-entry-popup-menu-link'}, true, false);
												if (linkMenuItem)
												{
													var height = parseInt(!!linkMenuItem.getAttribute('bx-height') ? linkMenuItem.getAttribute('bx-height') : 0);
													if (height > 0)
													{
														BX('post-menu-<?=$arParams["LOG_ID"]?>-link').style.display = 'none';
														linkMenuItem.setAttribute('bx-status', 'hidden');
														linkMenuItem.style.height = height + 'px';
													}
												}
											}
											
											
										}
									}
								<?
								}
							?>
							});
							return BX.PreventDefault(this);
						" class="feed-post-more-link"><span class="feed-post-more-text"><?=GetMessage("BLOG_POST_BUTTON_MORE")?></span><span class="feed-post-more-arrow"></span></a><?
					}

					?><span class="feed-post-time-wrap"><?
						if (ConvertTimeStamp(MakeTimeStamp($arResult["Post"]["DATE_PUBLISH"]), "SHORT") == ConvertTimeStamp())
						{
							?><a href="<?=$arResult["Post"]["urlToPost"]?>"><span class="feed-time"><?=$arResult["Post"]["DATE_PUBLISH_TIME"]?></span></a><?
						}
						else
						{
							?><a href="<?=$arResult["Post"]["urlToPost"]?>"><span class="feed-time"><?=$arResult["Post"]["DATE_PUBLISH_FORMATED"]?></span></a><?
						}
					?></span>
				</div>
			</div><?



		if(!in_array($arParams["TYPE"], array("DRAFT", "MODERATION")))
		{
			if ( empty($_REQUEST["bxajaxid"]) && empty($_REQUEST["logajax"]) ||
				($_REQUEST["RELOAD"] == "Y" && !(empty($_REQUEST["bxajaxid"]) && empty($_REQUEST["logajax"])) )
			)
			{
				include_once($_SERVER["DOCUMENT_ROOT"].$templateFolder."/destination.php");
			}
			$APPLICATION->IncludeComponent(
				"bitrix:socialnetwork.blog.post.comment",
				"",
				Array(
						"BLOG_VAR" => $arResult["ALIASES"]["blog"],
						"POST_VAR" => $arParams["POST_VAR"],
						"USER_VAR" => $arParams["USER_VAR"],
						"PAGE_VAR" => $arParams["PAGE_VAR"],
						"PATH_TO_BLOG" => $arParams["PATH_TO_BLOG"],
						"PATH_TO_POST" => $arParams["PATH_TO_POST"],
						"PATH_TO_USER" => $arParams["PATH_TO_USER"],
						"PATH_TO_SMILE" => $arParams["PATH_TO_SMILE"],
						"PATH_TO_MESSAGES_CHAT" => $arParams["PATH_TO_MESSAGES_CHAT"],
						"ID" => $arResult["Post"]["ID"],
						"CACHE_TYPE" => $arParams["CACHE_TYPE"],
						"CACHE_TIME" => $arParams["CACHE_TIME"],
						"COMMENTS_COUNT" => $arParams["COMMENTS_COUNT"],
						"DATE_TIME_FORMAT" => $arParams["DATE_TIME_FORMAT_S"],
						"USE_ASC_PAGING" => $arParams["USE_ASC_PAGING"],
						"USER_ID" => $arResult["USER_ID"],
						"GROUP_ID" => $arParams["GROUP_ID"],
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
						"ALLOW_VIDEO" => $arParams["ALLOW_VIDEO"],
						"ALLOW_IMAGE_UPLOAD" => $arParams["ALLOW_IMAGE_UPLOAD"],
						"SHOW_SPAM" => $arParams["BLOG_SHOW_SPAM"],
						"NO_URL_IN_COMMENTS" => $arParams["BLOG_NO_URL_IN_COMMENTS"],
						"NO_URL_IN_COMMENTS_AUTHORITY" => $arParams["BLOG_NO_URL_IN_COMMENTS_AUTHORITY"],
						"ALLOW_POST_CODE" => $arParams["BLOG_ALLOW_POST_CODE"],
						"AJAX_POST" => "Y",
						"POST_DATA" => $arResult["PostSrc"],
						"BLOG_DATA" => $arResult["Blog"],
						"FROM_LOG" => $arParams["FROM_LOG"],
						"bFromList" => $arResult["bFromList"],
						"LAST_LOG_TS" => $arParams["LAST_LOG_TS"],
						"MARK_NEW_COMMENTS" => $arParams["MARK_NEW_COMMENTS"],
						"AVATAR_SIZE" => $arParams["AVATAR_SIZE"],
						"AVATAR_SIZE_COMMON" => $arParams["AVATAR_SIZE_COMMON"],
						"AVATAR_SIZE_COMMENT" => $arParams["AVATAR_SIZE_COMMENT"],
						"FOLLOW" => $arParams["FOLLOW"],
						"LOG_ID" => intval($arParams["LOG_ID"]),
						"CREATED_BY_ID" => $arParams["CREATED_BY_ID"],
						"MOBILE" => $arParams["MOBILE"],
						"LAZYLOAD" => $arParams["LAZYLOAD"]
					),
				$component
			);

		}

		if (
			intval($arParams["LOG_ID"]) > 0
			&& array_key_exists("FAVORITES_USER_ID", $arParams)
		)
		{
			$bFavorites = (intval($arParams["FAVORITES_USER_ID"]) > 0);
			?><div id="log_entry_favorites_<?=intval($arParams["LOG_ID"])?>" onmousedown="__logChangeFavorites(<?=$arParams["LOG_ID"]?>, this, '<?=($bFavorites ? "N" : "Y")?>'); this.blur(); return BX.PreventDefault(this);" class="feed-post-important-switch<?=($bFavorites ? " feed-post-important-switch-active" : "")?>" title="<?=GetMessage("BLOG_POST_MENU_TITLE_FAVORITES_N")?>"></div><?
		}

		?></div><?
	}
	elseif(!$arResult["bFromList"])
		echo GetMessage("BLOG_BLOG_BLOG_NO_AVAIBLE_MES");
}
?></div>