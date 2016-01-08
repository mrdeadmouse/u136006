<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
if(strlen($arResult["FatalError"])>0)
{
	?><span class='errortext'><?=$arResult["FatalError"]?></span><br /><br /><?
}
else
{
	CUtil::InitJSCore(array("ajax", "tooltip"));

	if(strlen($arResult["ErrorMessage"])>0)
	{
		?><span class='errortext'><?=$arResult["ErrorMessage"]?></span><br /><br /><?
	}
	?><?
	$APPLICATION->IncludeComponent(
		"bitrix:socialnetwork.group.iframe.popup",
		".default",
		array(
			"PATH_TO_GROUP" => $arParams["PATH_TO_GROUP"],
			"PATH_TO_GROUP_EDIT" => htmlspecialcharsback($arResult["Urls"]["Edit"]).(strpos($arResult["Urls"]["Edit"], "?") === false ? "?" : "&")."tab=edit",
			"PATH_TO_GROUP_INVITE" => htmlspecialcharsback($arResult["Urls"]["Edit"]).(strpos($arResult["Urls"]["Edit"], "?") === false ? "?" : "&")."tab=invite",
			"ON_GROUP_ADDED" => "BX.DoNothing",
			"ON_GROUP_CHANGED" => "BX.DoNothing",
			"ON_GROUP_DELETED" => "BX.DoNothing"
		),
		null,
		array("HIDE_ICONS" => "Y")
	);

	$popupName = randString(6);
	$APPLICATION->IncludeComponent(
		"bitrix:socialnetwork.group_create.popup",
		".default",
		array(
			"NAME" => $popupName,
			"PATH_TO_GROUP_EDIT" => (strlen($arResult["Urls"]["Edit"]) > 0
				? htmlspecialcharsback($arResult["Urls"]["Edit"])
				: ""
			),
			"GROUP_NAME" => $arResult["Group"]["NAME"]
		),
		null,
		array("HIDE_ICONS" => "Y")
	);

	$ajax_page = $APPLICATION->GetCurPageParam("", array("bxajaxid", "logout"));

	if (!defined('SG_MUL_INCLUDED')):
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

		define('SG_MUL_INCLUDED', 1);
	endif;
	?><?
	if (!$arResult["bUserCanRequestGroup"] && !isset($arResult["bShowRequestSentMessage"]))
	{
		$this->SetViewTarget("pagetitle", 50);
		?><span class="bx-group-pagetitle-link" onclick="ToggleDescription();">
			<span id="bx_group_pagetitle_link_open" style="display: <?=($arResult["bDescriptionOpen"] == "Y" ? "inline-block" : "none")?>;">
				<?=GetMessage("SONET_C6_HIDE_DESCRIPTION")?>
				<span class="bx-group-pagetitle-link-arrow-open"></span>
			</span>
			<span id="bx_group_pagetitle_link_closed" style="display: <?=($arResult["bDescriptionOpen"] == "Y" ? "none" : "inline-block")?>;">
				<?=GetMessage("SONET_C6_SHOW_DESCRIPTION")?>
				<span class="bx-group-pagetitle-link-arrow-closed"></span>
			</span>
		</span><?
		$this->EndViewTarget();
	}
	?>

	<?
	if ($arResult["bShowRequestSentMessage"] == "U"):
	?>
		<div class="bx-group-join-request-sent">
			<?=GetMessage("SONET_C6_ACT_JOIN_REQUEST_SENT")?>
		</div>
	<?
	elseif ($arResult["bShowRequestSentMessage"] == "G"):

		global $USER;
		$url = str_replace("#user_id#", $USER->GetID(), COption::GetOptionString("socialnetwork", "user_request_page", 
			(IsModuleInstalled("intranet")) ? "/company/personal/user/#user_id#/requests/" : "/club/user/#user_id#/requests/", SITE_ID));
	?>
		<div class="bx-group-join-request-sent">
			<?=str_replace("#LINK#", $url, GetMessage("SONET_C6_ACT_JOIN_REQUEST_SENT_BY_GROUP"))?>
		</div>
	<?
	else:
	?>
		<table width="100%" cellspacing="0" id="bx_group_description"<?=($arResult["bDescriptionOpen"] == "Y" ? '' : ' class="bx-group-description-hide-table"')?>>
		<tr>
			<td valign="top">
				<table width="100%" cellspacing="0"><?
					if($arResult["Group"]["CLOSED"] == "Y"):
						?><tr>
							<td colspan="2" class="bx-group-description"><b><?=GetMessage("SONET_C39_ARCHIVE_GROUP")?></b></td>
						</tr><?
					endif;
					?><tr><?
						if(count($arResult["Subjects"]) > 1 && strlen($arResult["Group"]["SUBJECT_NAME"]) > 0):
							?><td class="bx-group-description-left-col"><?=GetMessage("SONET_C6_TOPIC")?>:</td>
							<td class="bx-group-description"><?=$arResult["Group"]["SUBJECT_NAME"]?></td><?
						else:
							?><td colspan="2"></td><?
						endif;
					?></tr><?
					if(strlen($arResult["Group"]["DESCRIPTION"]) > 0):
						?><tr class="ext-header-center-row">
							<td class="bx-group-description-left-col"><?=GetMessage("SONET_C6_DESCR")?>:</td>
							<td class="bx-group-description"><?=nl2br($arResult["Group"]["DESCRIPTION"])?></td>
						</tr><?
					endif;
					if ($arResult["GroupProperties"]["SHOW"] == "Y"):
						foreach ($arResult["GroupProperties"]["DATA"] as $fieldName => $arUserField):
							if (is_array($arUserField["VALUE"]) && count($arUserField["VALUE"]) > 0 || !is_array($arUserField["VALUE"]) && StrLen($arUserField["VALUE"]) > 0):
								?><tr class="ext-header-center-row">
									<td class="bx-group-description-left-col"><?=$arUserField["EDIT_FORM_LABEL"]?>:</td>
									<td class="bx-group-description"><?
									$GLOBALS["APPLICATION"]->IncludeComponent(
										"bitrix:system.field.view",
										$arUserField["USER_TYPE"]["USER_TYPE_ID"],
										array("arUserField" => $arUserField),
										null,
										array("HIDE_ICONS"=>"Y")
									);
									?></td>
								</tr><?
							endif;
						endforeach;
					endif;
					?><tr>
						<td class="bx-group-description-left-col"><?=GetMessage("SONET_C6_TYPE")?>:</td>
						<td class="bx-group-description">
							<?=($arResult["Group"]["OPENED"] == "Y" ? GetMessage("SONET_C6_TYPE_O1") : GetMessage("SONET_C6_TYPE_O2"))?><br \>
							<?=($arResult["Group"]["VISIBLE"] == "Y" ? GetMessage("SONET_C6_TYPE_V1") : GetMessage("SONET_C6_TYPE_V2"))?><?
							if ($arResult["bUserCanRequestGroup"]):
								?><div style="margin-top: 10px;"><a title="<?=GetMessage("SONET_C6_ACT_JOIN")?>" class="webform-small-button webform-small-button-accept" href="<?=$arResult["Urls"]["UserRequestGroup"]?>">
									<span class="webform-small-button-left"></span><span class="webform-small-button-text"><?=GetMessage("SONET_C6_ACT_JOIN")?></span><span class="webform-small-button-right"></span>
								</a></div><?
							endif;
						?></td>
					</tr>
				</table>
			</td>
			<td valign="top" class="bx-group-photo"><?=htmlspecialcharsback($arResult["Group"]["IMAGE_ID_IMG"])?></td>
		</tr>
		</table>
	<?
	endif;
	?>

	<h1 class="sonet-group-pagetitle"><?=GetMessage("SONET_C6_LIVEFEED")?><?$APPLICATION->ShowViewContent("pagetitle_log")?></h1>
	<div class="sonet-group-pagetitle-underline"></div>
	<div class="sonet-group-log">
	<div id="log_external_container"></div><?
		$APPLICATION->IncludeComponent(
			"bitrix:socialnetwork.log.ex",
			"",
			Array(
				"ENTITY_TYPE" => "",
				"GROUP_ID" => $arParams["GROUP_ID"],
				"USER_VAR" => $arParams["VARIABLE_ALIASES"]["user_id"],
				"GROUP_VAR" => $arParams["VARIABLE_ALIASES"]["group_id"],
				"PATH_TO_USER" => $arParams["PATH_TO_USER"],
				"PATH_TO_GROUP" => $arParams["PATH_TO_GROUP"],
				"SET_TITLE" => "N",
				"AUTH" => "Y",
				"SET_NAV_CHAIN" => "N",
				"PATH_TO_MESSAGES_CHAT" => $arParams["PM_URL"],
				"PATH_TO_VIDEO_CALL" => $arParams["PATH_TO_VIDEO_CALL"],
				"PATH_TO_CONPANY_DEPARTMENT" => $arParams["PATH_TO_CONPANY_DEPARTMENT"],
				"PATH_TO_GROUP_PHOTO_SECTION" => $arParams["PARENT_COMPONENT_RESULT"]["PATH_TO_GROUP_PHOTO_SECTION"],
				"PATH_TO_SEARCH_TAG" => $arParams["PATH_TO_SEARCH_TAG"],
				"DATE_TIME_FORMAT" => $arParams["DATE_TIME_FORMAT"],
				"SHOW_YEAR" => $arParams["SHOW_YEAR"],
				"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
				"SHOW_LOGIN" => $arParams["SHOW_LOGIN"],
				"SUBSCRIBE_ONLY" => "N",
				"SHOW_EVENT_ID_FILTER" => "Y",
				"SHOW_FOLLOW_FILTER" => "N",
				"USE_COMMENTS" => "Y",
				"PHOTO_THUMBNAIL_SIZE" => "48",
				"PAGE_ISDESC" => "N",
				"AJAX_MODE" => "N",
				"AJAX_OPTION_SHADOW" => "N",
				"AJAX_OPTION_HISTORY" => "N",
				"AJAX_OPTION_JUMP" => "N",
				"AJAX_OPTION_STYLE" => "Y",
				"CONTAINER_ID" => "log_external_container",
				"PAGE_SIZE" => 10,
				"SHOW_RATING" => $arParams["SHOW_RATING"],
				"RATING_TYPE" => $arParams["RATING_TYPE"],
				"PAGETITLE_TARGET" => "pagetitle_log",
				"SHOW_SETTINGS_LINK" => "Y",
				"AVATAR_SIZE" => $arParams["LOG_THUMBNAIL_SIZE"],
				"AVATAR_SIZE_COMMENT" => $arParams["LOG_COMMENT_THUMBNAIL_SIZE"],
				"NEW_TEMPLATE" => $arParams["LOG_NEW_TEMPLATE"],
				"SET_LOG_CACHE" => "Y",
			),
			$component,
			array("HIDE_ICONS"=>"Y")
		);
	?></div><?
	$this->SetViewTarget("sidebar", 50);
	?><div class="bx-group-sidebar-block">
		<b class="r2"></b>
		<b class="r1"></b>
		<b class="r0"></b>
		<div class="bx-group-sidebar-block-inner"><?
			if ($arResult["Owner"])
			{
				$tooltip_id = randString(8);
				$arUserTmp = array(
					"ID" => $arResult["Owner"]["USER_ID"],
					"NAME" => htmlspecialcharsback($arResult["Owner"]["USER_NAME"]),
					"LAST_NAME" => htmlspecialcharsback($arResult["Owner"]["USER_LAST_NAME"]),
					"SECOND_NAME" => htmlspecialcharsback($arResult["Owner"]["USER_SECOND_NAME"]),
					"LOGIN" => htmlspecialcharsback($arResult["Owner"]["USER_LOGIN"])
				);
				?><div class="bx-group-users">
					<b class="r2"></b>
					<b class="r1"></b>
					<b class="r0"></b>
					<div class="bx-group-users-inner">
						<div class="bx-group-users-title"><span class="bx-group-owner"><?=GetMessage("SONET_C6_OWNER")?></span></div>
						<div class="bx-group-users-list">
							<div class="bx-group-user">
								<a href="<?=htmlspecialcharsback($arResult["Owner"]["USER_PROFILE_URL"])?>" class="bx-group-user-avatar" style="background:url('<?=$arResult["Owner"]["USER_PERSONAL_PHOTO_FILE"]["SRC"]?>') no-repeat center center;"></a>
								<div class="bx-group-user-info">
									<div class="bx-group-user-name"><a id="anchor_<?=$tooltip_id?>" href="<?=htmlspecialcharsback($arResult["Owner"]["USER_PROFILE_URL"])?>"><?=CUser::FormatName(str_replace(array("#NOBR#", "#/NOBR#"), array("", ""), $arParams["NAME_TEMPLATE"]), $arUserTmp, $arParams["SHOW_LOGIN"] != "N")?></a></div>
									<script type="text/javascript">
										BX.tooltip(<?=$arUserTmp["ID"]?>, "anchor_<?=$tooltip_id?>", "<?=CUtil::JSEscape($ajax_page)?>");
									</script>
									<div class="bx-group-user-position"><?
									if (IsModuleInstalled("intranet") && strlen($arResult["Owner"]["USER_WORK_POSITION"]) > 0):
										?><?=$arResult["Owner"]["USER_WORK_POSITION"]?><?
									elseif ($arResult["Owner"]["USER_IS_EXTRANET"] == "Y"):
										?><?=GetMessage("SONET_C6_USER_IS_EXTRANET")?><?
									else:
										?>&nbsp;<?
									endif;
									?></div>
								</div>
							</div>
						</div>
					</div>
					<b class="r0"></b>
					<b class="r1"></b>
					<b class="r2"></b>
				</div><?
			}
			?><table cellspacing="0" class="bx-group-layout">
				<tr>
					<td class="bx-group-layout-column bx-group-layout-left-column"><?=GetMessage("SONET_C6_CREATED")?>:</td>
					<td class="bx-group-layout-column bx-group-layout-right-column"><?=$arResult["Group"]["DATE_CREATE"]?></td>
				</tr>
				<tr>
					<td class="bx-group-layout-column bx-group-layout-left-column"><?=GetMessage("SONET_C6_NMEM")?>:</td>
					<td class="bx-group-layout-column bx-group-layout-right-column"><?=$arResult["Group"]["NUMBER_OF_MEMBERS"]?></td>
				</tr>
				<tr>
					<td class="bx-group-layout-column bx-group-layout-left-column"><?=GetMessage("SONET_C6_TYPE")?>:</td>
					<td class="bx-group-layout-column bx-group-layout-right-column"><?
					if ($arResult["Group"]["OPENED"] == "Y" && $arResult["Group"]["VISIBLE"] == "Y")
						echo GetMessage("SONET_C6_TYPE_O1_V1");
					elseif ($arResult["Group"]["OPENED"] == "Y" && $arResult["Group"]["VISIBLE"] == "N")
						echo GetMessage("SONET_C6_TYPE_O1_V2");
					elseif ($arResult["Group"]["OPENED"] == "N" && $arResult["Group"]["VISIBLE"] == "Y")
						echo GetMessage("SONET_C6_TYPE_O2_V1");
					elseif ($arResult["Group"]["OPENED"] == "N" && $arResult["Group"]["VISIBLE"] == "N")
						echo GetMessage("SONET_C6_TYPE_O2_V2");
					?></td>
				</tr>
			</table><?
			if ($arResult["Moderators"]["List"])
			{
				?><div class="bx-group-users bx-group-moderator">
					<b class="r2"></b>
					<b class="r1"></b>
					<b class="r0"></b>
					<div class="bx-group-users-inner">
						<div class="bx-group-users-title"><span><?=GetMessage("SONET_C6_ACT_MOD1")?></span><?
						if ($arResult["CurrentUserPerms"]["UserCanModifyGroup"] && $GLOBALS["USER"]->IsAuthorized() && !$arResult["HideArchiveLinks"]):
							?><a class="webform-field-action-link" href="<?=htmlspecialcharsback($arResult["Urls"]["GroupMods"])?>"><?=GetMessage("SONET_C6_ACT_MOD")?></a><?
						endif;
						?></div>
						<div class="bx-group-users-list"><?
						foreach ($arResult["Moderators"]["List"] as $friend)
						{
							$tooltip_id = randString(8);
							$arUserTmp = array(
								"ID" => $friend["USER_ID"],
								"NAME" => htmlspecialcharsback($friend["USER_NAME"]),
								"LAST_NAME" => htmlspecialcharsback($friend["USER_LAST_NAME"]),
								"SECOND_NAME" => htmlspecialcharsback($friend["USER_SECOND_NAME"]),
								"LOGIN" => htmlspecialcharsback($friend["USER_LOGIN"])
							);
							?><div class="bx-group-user">
								<a href="<?=htmlspecialcharsback($friend["USER_PROFILE_URL"])?>" class="bx-group-user-avatar" style="background:url('<?=$friend["USER_PERSONAL_PHOTO_FILE"]["SRC"]?>') no-repeat center center;"></a>
								<div class="bx-group-user-info">
									<div class="bx-group-user-name"><a id="anchor_<?=$tooltip_id?>" href="<?=htmlspecialcharsback($friend["USER_PROFILE_URL"])?>"><?=CUser::FormatName(str_replace(array("#NOBR#", "#/NOBR#"), array("", ""), $arParams["NAME_TEMPLATE"]), $arUserTmp, $arParams["SHOW_LOGIN"] != "N")?></a></div>
									<script type="text/javascript">
										BX.tooltip(<?=$arUserTmp["ID"]?>, "anchor_<?=$tooltip_id?>", "<?=CUtil::JSEscape($ajax_page)?>");
									</script>
									<div class="bx-group-user-position"><?
									if (IsModuleInstalled("intranet") && strlen($friend["USER_WORK_POSITION"]) > 0):
										?><?=$friend["USER_WORK_POSITION"]?><?
									elseif ($friend["USER_IS_EXTRANET"] == "Y"):
										?><?=GetMessage("SONET_C6_USER_IS_EXTRANET")?><?
									else:
										?>&nbsp;<?
									endif;
									?></div>
								</div>
							</div><?
						}
						?></div>
					</div>
					<b class="r0"></b>
					<b class="r1"></b>
					<b class="r2"></b>
				</div><?
				if (count($arResult["Moderators"]["List"]) > $arParams["ITEMS_COUNT"])
				{
					?><div class="bx-group-users refuse">
						<div class="bx-group-users-border"></div>
						<div class="bx-group-users-inner">
							<div class="bx-group-more-members"><a href="<?=htmlspecialcharsback($arResult["Urls"]["GroupMods"])?>" class="bx-group-members-text"><?=GetMessage("SONET_C6_MODERATORS_REST")?> (<?=$arResult["Group"]["NUMBER_OF_MODERATORS"]?>)</a></div>
						</div>
						<b class="r0"></b>
						<b class="r1"></b>
						<b class="r2"></b>
					</div><?
				}
			}

			if ($arResult["Members"]["List"])
			{
				?><div class="bx-group-users bx-group-member">
					<b class="r2"></b>
					<b class="r1"></b>
					<b class="r0"></b>
					<div class="bx-group-users-inner">
						<div class="bx-group-users-title"><span><?=GetMessage("SONET_C6_ACT_USER1")?></span><?
						if ($GLOBALS["USER"]->IsAuthorized() && $arResult["CurrentUserPerms"]["UserCanInitiate"] && !$arResult["HideArchiveLinks"]):
							?><a class="webform-field-action-link" href="<?=htmlspecialcharsback($arResult["Urls"]["GroupRequestSearch"])?>" onclick="if (BX.SGCP) { BX.SGCP.ShowForm('invite', '<?=$popupName?>', event); } else { return false;}"><?=GetMessage("SONET_C6_ACT_REQU")?></a><?
						endif;
						?></div>
						<div class="bx-group-users-list"><?
						foreach ($arResult["Members"]["List"] as $friend)
						{
							$tooltip_id = randString(8);
							$arUserTmp = array(
								"ID" => $friend["USER_ID"],
								"NAME" => htmlspecialcharsback($friend["USER_NAME"]),
								"LAST_NAME" => htmlspecialcharsback($friend["USER_LAST_NAME"]),
								"SECOND_NAME" => htmlspecialcharsback($friend["USER_SECOND_NAME"]),
								"LOGIN" => htmlspecialcharsback($friend["USER_LOGIN"])
							);
							?><div class="bx-group-user">
								<a href="<?=htmlspecialcharsback($friend["USER_PROFILE_URL"])?>" class="bx-group-user-avatar" style="background:url('<?=$friend["USER_PERSONAL_PHOTO_FILE"]["SRC"]?>') no-repeat center center;"></a>
								<div class="bx-group-user-info">
									<div class="bx-group-user-name"><a id="anchor_<?=$tooltip_id?>" href="<?=htmlspecialcharsback($friend["USER_PROFILE_URL"])?>"><?=CUser::FormatName(str_replace(array("#NOBR#", "#/NOBR#"), array("", ""), $arParams["NAME_TEMPLATE"]), $arUserTmp, $arParams["SHOW_LOGIN"] != "N")?></a></div>
									<script type="text/javascript">
										BX.tooltip(<?=$arUserTmp["ID"]?>, "anchor_<?=$tooltip_id?>", "<?=CUtil::JSEscape($ajax_page)?>");
									</script>
									<div class="bx-group-user-position"><?
									if (IsModuleInstalled("intranet") && strlen($friend["USER_WORK_POSITION"]) > 0):
										?><?=$friend["USER_WORK_POSITION"]?><?
									elseif ($friend["USER_IS_EXTRANET"] == "Y"):
										?><?=GetMessage("SONET_C6_USER_IS_EXTRANET")?><?
									else:
										?>&nbsp;<?
									endif;
									?></div>
								</div>
							</div><?
						}

						if (intval($arResult["Group"]["NUMBER_OF_MEMBERS"]) > $arParams["ITEMS_COUNT"])
						{
							?><div class="bx-group-bord"></div><?
						}
						?></div>
					</div>
				</div><?

				if (intval($arResult["Group"]["NUMBER_OF_MEMBERS"]) > $arParams["ITEMS_COUNT"])
				{
					?><div class="bx-group-users refuse">
						<div class="bx-group-users-border"></div>
						<div class="bx-group-users-inner">
							<div class="bx-group-more-members"><a href="<?=htmlspecialcharsback($arResult["Urls"]["GroupUsers"])?>" class="bx-group-members-text"><?=GetMessage("SONET_C6_MEMBERS_REST")?> (<?=$arResult["Group"]["NUMBER_OF_MEMBERS"]?>)</a></div>
						</div>
						<b class="r0"></b>
						<b class="r1"></b>
						<b class="r2"></b>
					</div><?
				}
			}
		?></div>
		<i class="r0"></i>
		<i class="r1"></i>
		<i class="r2"></i>
	</div><?

	ob_start();

	global $arContentFilter;
	$arContentFilter = array(
		"!ITEM_ID" => "G".$arParams["GROUP_ID"],
		"PARAMS" => array("socnet_group" => $arParams["GROUP_ID"])
	);

	$tags_cnt = $GLOBALS["APPLICATION"]->IncludeComponent(
		"bitrix:search.tags.cloud",
		"",
		Array(
			"PAGE_ELEMENTS" => $arParams["SEARCH_TAGS_PAGE_ELEMENTS"],
			"PERIOD" => $arParams["SEARCH_TAGS_PERIOD"],
			"URL_SEARCH" => CComponentEngine::MakePathFromTemplate($arParams["~PATH_TO_GROUP_CONTENT_SEARCH"], array("group_id" => $arParams["GROUP_ID"])),
			"FONT_MAX" => $arParams["SEARCH_TAGS_FONT_MAX"],
			"FONT_MIN" => $arParams["SEARCH_TAGS_FONT_MIN"],
			"COLOR_NEW" => $arParams["SEARCH_TAGS_COLOR_NEW"],
			"COLOR_OLD" => $arParams["SEARCH_TAGS_COLOR_NEW"],
			"WIDTH" => "100%",
			"SORT" => "NAME",
			"CACHE_TIME" => $arParams["CACHE_TIME"],
			"CACHE_TYPE" => $arParams["CACHE_TYPE"],
			"FILTER_NAME" => "arContentFilter"
		),
		false,
		array("HIDE_ICONS" => "Y")
	);

	if ($tags_cnt > 0)
		$tags_cloud = ob_get_contents();

	ob_end_clean();

	if (strlen($tags_cloud) > 0)
	{
		?><div class="bx-group-sidebar-block" style="margin-top: 10px;">
			<b class="r2"></b>
			<b class="r1"></b>
			<b class="r0"></b>
				<div class="bx-group-sidebar-block-inner"><?=$tags_cloud?></div>
			<i class="r0"></i>
			<i class="r1"></i>
			<i class="r2"></i>
		</div><?
	}
	?><?
	$this->EndViewTarget();
	?><?
}
?>