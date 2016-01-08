<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
CUtil::InitJSCore(array("popup"));

$this->SetViewTarget("topblock", 100);

$APPLICATION->IncludeComponent(
	"bitrix:socialnetwork.group.iframe.popup",
	".default",
	array(
		"PATH_TO_GROUP" => $arParams["PATH_TO_GROUP"],
		"PATH_TO_GROUP_EDIT" => htmlspecialcharsback($arResult["Urls"]["Edit"]).(strpos($arResult["Urls"]["Edit"], "?") === false ? "?" : "&")."tab=edit",
		"PATH_TO_GROUP_FEATURES" => htmlspecialcharsback($arResult["Urls"]["Edit"]).(strpos($arResult["Urls"]["Edit"], "?") === false ? "?" : "&")."tab=features",
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

?>
<script>
	BX.message({
		SGMErrorSessionWrong: '<?=GetMessageJS("SONET_SGM_T_SESSION_WRONG")?>',
		SGMErrorCurrentUserNotAuthorized: '<?=GetMessageJS("SONET_SGM_T_NOT_ATHORIZED")?>',
		SGMErrorModuleNotInstalled: '<?=GetMessageJS("SONET_SGM_T_MODULE_NOT_INSTALLED")?>',
		SGMWaitTitle: '<?=GetMessageJS("SONET_SGM_T_WAIT")?>',
		SGMSubscribeButtonHintOn: '<?=GetMessageJS("SONET_SGM_T_NOTIFY_HINT_ON")?>',
		SGMSubscribeButtonHintOff: '<?=GetMessageJS("SONET_SGM_T_NOTIFY_HINT_OFF")?>',
		SGMSubscribeButtonTitleOn: '<?=GetMessageJS("SONET_SGM_T_NOTIFY_TITLE_ON")?>',
		SGMSubscribeButtonTitleOff: '<?=GetMessageJS("SONET_SGM_T_NOTIFY_TITLE_OFF")?>'
	});
</script>

<div class="profile-menu profile-menu-group">
	<b class="r2"></b><b class="r1"></b><b class="r0"></b>
	<div class="profile-menu-inner">
		<a class="profile-menu-avatar"<?if (strlen($arResult["Group"]["IMAGE_FILE"]["src"]) > 0):?> style="background:url('<?=$arResult["Group"]["IMAGE_FILE"]["src"]?>') no-repeat center center; width: <?=$arResult["Group"]["IMAGE_FILE"]["width"]?>px; height: <?=$arResult["Group"]["IMAGE_FILE"]["height"]?>px;"<?endif;?> id="profile-menu-avatar" onclick="return OpenProfileMenuPopup(this);"></a>
		<div class="profile-menu-info">
			<div class="profile-menu-title">
				<a<?if ($arResult["CurrentUserPerms"]["UserCanViewGroup"]):?> href="<?=$arResult["Urls"]["View"]?>"<?endif;?> class="profile-menu-name" onclick="return OpenProfileMenuPopup(this);"><span class="profile-menu-name-left"></span><span class="profile-menu-name-text"><?=$arResult["Group"]["NAME"]?><?=($arResult["Group"]["IS_EXTRANET"] == "Y" ? GetMessage("SONET_UM_IS_EXTRANET") : "")?></span><span class="profile-menu-name-arrow"></span><span class="profile-menu-name-right"></span></a><?
				if($arResult["Group"]["CLOSED"] == "Y"):
					?><span class="profile-menu-description"><?=GetMessage("SONET_UM_ARCHIVE_GROUP")?></span><?
				endif;?>
			</div>
			<div class="profile-menu-items">
				<a href="<?=$arResult["Urls"]["View"]?>" class="profile-menu-item<?if ($arParams["PAGE_ID"] == "group"):?> profile-menu-item-selected<?endif?>"><span class="profile-menu-item-left"></span><span class="profile-menu-item-text"><?=GetMessage("SONET_UM_GENERAL")?></span><span class="profile-menu-item-right"></span></a><?
				foreach ($arResult["CanView"] as $key => $val)
				{
					if (!$val)
						continue;
					?><a href="<?=$arResult["Urls"][$key]?>" class="profile-menu-item<?if ($arParams["PAGE_ID"] == "group_".$key):?> profile-menu-item-selected<?endif?>"><span class="profile-menu-item-left"></span><span class="profile-menu-item-text"><?=$arResult["Title"][$key]?></span><span class="profile-menu-item-right"></span></a><?
				}
			?></div>
		</div>
	</div>
	<i class="r0"></i><i class="r1"></i><i class="r2"></i>
</div>

<div class="profile-menu-popup profile-menu-popup-group" id="profile-menu-popup">
	<div class="profile-menu-popup-header">
		<div class="profile-menu-popup-avatar"<?if (strlen($arResult["Group"]["IMAGE_FILE"]["src"]) > 0):?> style="background:url('<?=$arResult["Group"]["IMAGE_FILE"]["src"]?>') no-repeat center center; width: <?=$arResult["Group"]["IMAGE_FILE"]["width"]?>px; height: <?=$arResult["Group"]["IMAGE_FILE"]["height"]?>px;"<?endif;?>></div>
		<div class="profile-menu-popup-title">
			<div class="profile-menu-popup-name"><?=$arResult["Group"]["NAME"]?><?=($arResult["Group"]["IS_EXTRANET"] == "Y" ? GetMessage("SONET_UM_IS_EXTRANET") : "")?></div><?
			if($arResult["Group"]["CLOSED"] == "Y"):
				?><div class="profile-menu-popup-description"><?=GetMessage("SONET_UM_ARCHIVE_GROUP")?></div><?
			endif;
		?></div>
	</div>
	<div class="profile-menu-popup-items"><?
		if (!IsModuleInstalled("im") && $arResult["CurrentUserPerms"]["UserCanSpamGroup"]):
			?><div class="popup-window-hr"><i></i></div>
			<a title="<?=GetMessage("SONET_UM_SEND_MESSAGE")?>" class="profile-menu-popup-item profile-menu-popup-item-message" href="<?=$arResult["Urls"]["MessageToGroup"]?>" onclick="window.open('<?=$arResult["Urls"]["MessageToGroup"]?>', '', 'location=yes,status=no,scrollbars=yes,resizable=yes,width=750,height=550,top='+Math.floor((screen.height - 550)/2-14)+',left='+Math.floor((screen.width - 750)/2-5)); return false;"><span class="profile-menu-popup-item-left"></span><span class="profile-menu-popup-item-icon"></span><span class="profile-menu-popup-item-text"><?=GetMessage("SONET_UM_SEND_MESSAGE")?></span><span class="profile-menu-popup-item-right"></span></a><?
		endif;

		if ($arResult["CurrentUserPerms"]["UserCanModifyGroup"]):
			?><div class="popup-window-hr"><i></i></div>
			<a title="<?=GetMessage("SONET_UM_EDIT")?>" class="profile-menu-popup-item profile-menu-popup-item-groupedit" href="<?=$arResult["Urls"]["Edit"]?>" onclick="if (BX.SGCP) { BX.SGCP.ShowForm('edit', '<?=$popupName?>', event); } else { return false;}"><span class="profile-menu-popup-item-left"></span><span class="profile-menu-popup-item-icon"></span><span class="profile-menu-popup-item-text"><?=GetMessage("SONET_UM_EDIT")?></span><span class="profile-menu-popup-item-right"></span></a><?
			if (!$arResult["HideArchiveLinks"]):
				?><div class="popup-window-hr"><i></i></div>
				<a title="<?=GetMessage("SONET_UM_FEATURES")?>" class="profile-menu-popup-item profile-menu-popup-item-editfeatures" href="<?=$arResult["Urls"]["Features"]?>"><span class="profile-menu-popup-item-left"></span><span class="profile-menu-popup-item-icon"></span><span class="profile-menu-popup-item-text"><?=GetMessage("SONET_UM_FEATURES")?></span><span class="profile-menu-popup-item-right"></span></a><?
			endif;
			?><div class="popup-window-hr"><i></i></div>
			<a title="<?=GetMessage("SONET_UM_DELETE")?>" class="profile-menu-popup-item profile-menu-popup-item-groupdelete" href="<?=$arResult["Urls"]["Delete"]?>"><span class="profile-menu-popup-item-left"></span><span class="profile-menu-popup-item-icon"></span><span class="profile-menu-popup-item-text"><?=GetMessage("SONET_UM_DELETE")?></span><span class="profile-menu-popup-item-right"></span></a><?
		endif;

		if ($arResult["CurrentUserPerms"]["UserCanModerateGroup"] && $GLOBALS["USER"]->IsAuthorized()):
			?><div class="popup-window-hr"><i></i></div>
			<a title="<?=GetMessage("SONET_UM_MEMBERS")?>" class="profile-menu-popup-item profile-menu-popup-item-memberedit" href="<?=$arResult["Urls"]["GroupUsers"]?>"><span class="profile-menu-popup-item-left"></span><span class="profile-menu-popup-item-icon"></span><span class="profile-menu-popup-item-text"><?=GetMessage("SONET_UM_MEMBERS")?></span><span class="profile-menu-popup-item-right"></span></a><?
		else:
			?><div class="popup-window-hr"><i></i></div>
			<a title="<?=GetMessage("SONET_UM_MEMBERS1")?>" class="profile-menu-popup-item profile-menu-popup-item-memberview" href="<?=$arResult["Urls"]["GroupUsers"]?>"><span class="profile-menu-popup-item-left"></span><span class="profile-menu-popup-item-icon"></span><span class="profile-menu-popup-item-text"><?=GetMessage("SONET_UM_MEMBERS1")?></span><span class="profile-menu-popup-item-right"></span></a><?
		endif;

		if ($GLOBALS["USER"]->IsAuthorized()):
			if ($arResult["CurrentUserPerms"]["UserCanInitiate"] && !$arResult["HideArchiveLinks"]):
				?><div class="popup-window-hr"><i></i></div>
				<a title="<?=GetMessage("SONET_UM_INVITE")?>" class="profile-menu-popup-item profile-menu-popup-item-invite" href="<?=$arResult["Urls"]["GroupRequestSearch"]?>" onclick="if (BX.SGCP) { BX.SGCP.ShowForm('invite', '<?=$popupName?>', event); } else { return false;}"><span class="profile-menu-popup-item-left"></span><span class="profile-menu-popup-item-icon"></span><span class="profile-menu-popup-item-text"><?=GetMessage("SONET_UM_INVITE")?></span><span class="profile-menu-popup-item-right"></span></a><?
				if (!CModule::IncludeModule('extranet') || ($arResult["Group"]["OPENED"] != "Y" && !CExtranet::IsExtranetSite())):
					?><div class="popup-window-hr"><i></i></div>
					<a title="<?=GetMessage("SONET_UM_REQUESTS")?>" class="profile-menu-popup-item profile-menu-popup-item-requests" href="<?=$arResult["Urls"]["GroupRequests"]?>"><span class="profile-menu-popup-item-left"></span><span class="profile-menu-popup-item-icon"></span><span class="profile-menu-popup-item-text"><?=GetMessage("SONET_UM_REQUESTS")?></span><span class="profile-menu-popup-item-right"></span></a><?
				else:
					?><div class="popup-window-hr"><i></i></div>
					<a title="<?=GetMessage("SONET_UM_REQUESTS")?>" class="profile-menu-popup-item profile-menu-popup-item-requests" href="<?=$arResult["Urls"]["GroupRequestsOut"]?>"><span class="profile-menu-popup-item-left"></span><span class="profile-menu-popup-item-icon"></span><span class="profile-menu-popup-item-text"><?=GetMessage("SONET_UM_REQUESTS_OUT")?></span><span class="profile-menu-popup-item-right"></span></a><?
				endif;
			endif;
			if ((!$arResult["CurrentUserPerms"]["UserRole"] || ($arGadgetParams["USER_ROLE"] == SONET_ROLES_REQUEST && $arResult["CurrentUserPerms"]["InitiatedByType"] == SONET_INITIATED_BY_GROUP)) && !$arResult["HideArchiveLinks"]):
				?><div class="popup-window-hr"><i></i></div>
				<a title="<?=GetMessage("SONET_UM_JOIN")?>" class="profile-menu-popup-item profile-menu-popup-item-join" href="<?=$arResult["Urls"]["UserRequestGroup"]?>"><span class="profile-menu-popup-item-left"></span><span class="profile-menu-popup-item-icon"></span><span class="profile-menu-popup-item-text"><?=GetMessage("SONET_UM_JOIN")?></span><span class="profile-menu-popup-item-right"></span></a><?
			endif;
			if ($arResult["CurrentUserPerms"]["UserIsMember"] && !$arResult["CurrentUserPerms"]["UserIsOwner"]):
				?><div class="popup-window-hr"><i></i></div>
				<a title="<?=GetMessage("SONET_UM_LEAVE")?>" class="profile-menu-popup-item profile-menu-popup-item-leave" href="<?=$arResult["Urls"]["UserLeaveGroup"]?>"><span class="profile-menu-popup-item-left"></span><span class="profile-menu-popup-item-icon"></span><span class="profile-menu-popup-item-text"><?=GetMessage("SONET_UM_LEAVE")?></span><span class="profile-menu-popup-item-right"></span></a><?
			endif;
			if (
				!$arResult["HideArchiveLinks"]
				&& !class_exists("CSocNetSubscription")
			):
				?><div class="popup-window-hr"><i></i></div>
				<a title="<?=GetMessage("SONET_UM_SUBSCRIBE")?>" class="profile-menu-popup-item profile-menu-popup-item-subscribe" href="<?=$arResult["Urls"]["Subscribe"]?>"><span class="profile-menu-popup-item-left"></span><span class="profile-menu-popup-item-icon"></span><span class="profile-menu-popup-item-text"><?=GetMessage("SONET_UM_SUBSCRIBE")?></span><span class="profile-menu-popup-item-right"></span></a><?
			endif;
		endif;
	?></div>
</div>
<script type="text/javascript">
function OpenProfileMenuPopup(source)
{
	var offsetTop = -52;
	var offsetLeft = -10;

	var ie7 = false;
	/*@cc_on
		 @if (@_jscript_version <= 5.7)
			 ie7 = true;
		/*@end
	@*/

	if (ie7 || (document.documentMode && document.documentMode <= 7))
	{
		offsetTop = -54;
	    offsetLeft = -12;
	}

	var popup = BX.PopupWindowManager.create("profile-menu", BX("profile-menu-avatar"), {
		offsetTop : offsetTop,
		offsetLeft : offsetLeft,
		autoHide : true,
		closeIcon : true,
		content : BX("profile-menu-popup")
	});

	popup.show();


	BX.bind(popup.popupContainer, "mouseover", BX.proxy(function() {
		if (this.params._timeoutId)
		{
			clearTimeout(this.params._timeoutId);
			this.params._timeoutId = undefined;
		}

		this.show();
	}, popup));

	BX.bind(popup.popupContainer, "mouseout", BX.proxy(CloseProfileMenuPopup, popup));

	return false;
}

function CloseProfileMenuPopup(event)
{
	if (!this.params._timeoutId)
		this.params._timeoutId = setTimeout(BX.proxy(function() { this.close()}, this), 300);
}
</script><?
$this->EndViewTarget();

if (
	class_exists("CSocNetSubscription")
	&& in_array($arResult["CurrentUserPerms"]["UserRole"], array(SONET_ROLES_OWNER, SONET_ROLES_MODERATOR, SONET_ROLES_USER))
)
{
	$this->SetViewTarget("pagetitle", 20);
	?><a id="group_menu_subscribe_button" class="profile-menu-notify-btn<?=($arResult["bSubscribed"] ? " profile-menu-notify-btn-active" : "")?>" title="<?=GetMessage("SONET_SGM_T_NOTIFY_TITLE_".($arResult["bSubscribed"] ? "ON" : "OFF"))?>" href="#" onclick="__SGMSetSubscribe(<?=$arParams["GROUP_ID"]?>, event);" style="position: relative; bottom: -9px; margin-left: 10px;"></a><?
	$this->EndViewTarget();
}