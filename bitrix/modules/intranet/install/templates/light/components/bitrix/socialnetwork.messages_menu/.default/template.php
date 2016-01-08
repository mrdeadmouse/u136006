<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
CUtil::InitJSCore(array("popup"));

$this->SetViewTarget("sidebar", 100);
?>
<div class="sidebar-block">
	<b class="r2"></b><b class="r1"></b><b class="r0"></b>
	<div class="sidebar-block-inner">
		<div class="sidebar-block-title"><?=GetMessage("SONET_UM_MY_MESSAGES")?></div>
		<div class="sidebar-menu">
			<ul>
				<li class="<?if ($arParams["PAGE_ID"] == "messages_users"):?>selected<?endif?>"><?if ($arParams["PAGE_ID"] == "messages_users"):?><span class="sidebar-menu-arrow"></span><?endif?><a href="<?=$arResult["Urls"]["MessagesUsers"]?>"><b class="r1"></b><b class="r0"></b><span><?=GetMessage("SONET_UM_MUSERS")?></span><i class="r0"></i><i class="r1"></i></a></li>
				<li class="<?if ($arParams["PAGE_ID"] == "messages_input"):?>selected<?endif?>"><?if ($arParams["PAGE_ID"] == "messages_input"):?><span class="sidebar-menu-arrow"></span><?endif?><a href="<?=$arResult["Urls"]["MessagesInput"]?>"><b class="r1"></b><b class="r0"></b><span><?=GetMessage("SONET_UM_INPUT")?></span><i class="r0"></i><i class="r1"></i></a></li>				
				<li class="<?if ($arParams["PAGE_ID"] == "messages_output"):?>selected<?endif?>"><?if ($arParams["PAGE_ID"] == "messages_output"):?><span class="sidebar-menu-arrow"></span><?endif?><a href="<?=$arResult["Urls"]["MessagesOutput"]?>"><b class="r1"></b><b class="r0"></b><span><?=GetMessage("SONET_UM_OUTPUT")?></span><i class="r0"></i><i class="r1"></i></a></li>
				<li class="<?if ($arParams["PAGE_ID"] == "user_ban"):?>selected<?endif?>"><?if ($arParams["PAGE_ID"] == "user_ban"):?><span class="sidebar-menu-arrow"></span><?endif?><a href="<?=$arResult["Urls"]["UserBan"]?>"><b class="r1"></b><b class="r0"></b><span><?=GetMessage("SONET_UM_USER_BAN")?></span><i class="r0"></i><i class="r1"></i></a></li>
				<?if(strlen($arResult["Urls"]["BizProc"]) > 0):?>
					<li class="<?if ($arParams["PAGE_ID"] == "bizproc"):?>selected<?endif?>"><?if ($arParams["PAGE_ID"] == "bizproc"):?><span class="sidebar-menu-arrow"></span><?endif?><a href="<?=$arResult["Urls"]["BizProc"]?>"><b class="r1"></b><b class="r0"></b><span><?=GetMessage("SONET_UM_BIZPROC")?></span><i class="r0"></i><i class="r1"></i></a></li>
				<?endif;?>
			</ul>
		</div>
	</div>
	<i class="r0"></i><i class="r1"></i><i class="r2"></i>
</div>
<?
$this->EndViewTarget();
?>
<?
$this->SetViewTarget("topblock", 100);
?>
<div class="profile-menu">
	<b class="r2"></b><b class="r1"></b><b class="r0"></b>
	<div class="profile-menu-inner">
		<a class="profile-menu-avatar"<?if (strlen($arResult["User"]["PersonalPhotoFile"]["src"]) > 0):?> style="background:url('<?=$arResult["User"]["PersonalPhotoFile"]["src"]?>') no-repeat center center; width: <?=$arResult["User"]["PersonalPhotoFile"]["width"]?>px; height: <?=$arResult["User"]["PersonalPhotoFile"]["height"]?>px;"<?endif;?> id="profile-menu-avatar" onclick="return OpenProfileMenuPopup(this);">
		<?
		if (array_key_exists("IS_ONLINE", $arResult) && $arResult["IS_ONLINE"]):
			?><div class="profile-menu-avatar-online"></div><?
		endif;
		if (
			(array_key_exists("IS_HONOURED", $arResult) && $arResult["IS_HONOURED"])
			&&
			(array_key_exists("IS_BIRTHDAY", $arResult) && $arResult["IS_BIRTHDAY"])
		):
			?><div class="profile-menu-birthday-medal" title="<?=GetMessage("SONET_UM_BIRTHDAY")?>. <?=GetMessage("SONET_UM_HONOUR")?>"></div><?
		elseif (array_key_exists("IS_HONOURED", $arResult) && $arResult["IS_HONOURED"]):
			?><div class="profile-menu-medal" title="<?=GetMessage("SONET_UM_HONOUR")?>"></div><?
		elseif (array_key_exists("IS_BIRTHDAY", $arResult) && $arResult["IS_BIRTHDAY"]):
			?><div class="profile-menu-birthday" title="<?=GetMessage("SONET_UM_BIRTHDAY")?>"></div><?
		endif;
		?>
		</a>
		<div class="profile-menu-info">
			<div class="profile-menu-title">
				<a<?if ($arResult["CurrentUserPerms"]["Operations"]["viewprofile"]):?> href="<?=$arResult["Urls"]["main"]?>"<?endif;?> class="profile-menu-name" onclick="return OpenProfileMenuPopup(this);"><span class="profile-menu-name-left"></span><span class="profile-menu-name-text"><?=$arResult["User"]["NAME_FORMATTED"]?></span><?
				if (array_key_exists("IS_ABSENT", $arResult) && $arResult["IS_ABSENT"]):
					?><span class="profile-menu-name-status"><?=GetMessage("SONET_UM_ABSENT")?></span><?
				endif;
				?><span class="profile-menu-name-arrow"></span><span class="profile-menu-name-right"></span></a><span class="profile-menu-description"><?=$arResult["User"]["WORK_POSITION"]?></span>
			</div>
			<div class="profile-menu-items">
				<?if ($arResult["CurrentUserPerms"]["IsCurrentUser"]):?>
					<a href="<?=$arResult["Urls"]["Log"]?>" class="profile-menu-item<?if ($arParams["PAGE_ID"] == "user_log"):?> profile-menu-item-selected<?endif?>"><span class="profile-menu-item-left"></span><span class="profile-menu-item-text"><?=GetMessage("SONET_UM_LOG")?></span><span class="profile-menu-item-right"></span></a>
				<?endif;?>
				<a href="<?=$arResult["Urls"]["Main"]?>" class="profile-menu-item"><span class="profile-menu-item-left"></span><span class="profile-menu-item-text"><?=GetMessage("SONET_UM_GENERAL")?></span><span class="profile-menu-item-right"></span></a><?
				if (CSocNetUser::IsFriendsAllowed() && $arResult["CurrentUserPerms"]["Operations"]["viewfriends"]):
					?><a href="<?=$arResult["Urls"]["Friends"]?>" class="profile-menu-item"><span class="profile-menu-item-left"></span><span class="profile-menu-item-text"><?=GetMessage("SONET_UM_FRIENDS")?></span><span class="profile-menu-item-right"></span></a><?
				endif;
				if ($arResult["CurrentUserPerms"]["Operations"]["viewgroups"]):
					?><a href="<?=$arResult["Urls"]["Groups"]?>" class="profile-menu-item"><span class="profile-menu-item-left"></span><span class="profile-menu-item-text"><?=GetMessage("SONET_UM_GROUPS")?></span><span class="profile-menu-item-right"></span></a><?
				endif;
				foreach ($arResult["CanView"] as $key => $val)
				{
					if (!$val)
						continue;
					?><a href="<?=$arResult["Urls"][$key]?>" class="profile-menu-item"><span class="profile-menu-item-left"></span><span class="profile-menu-item-text"><?=$arResult["Title"][$key]?></span><span class="profile-menu-item-right"></span></a><?
				}
				?><a href="<?=$arResult["Urls"]["MessagesUsers"]?>" class="profile-menu-item profile-menu-item-selected"><span class="profile-menu-item-left"></span><span class="profile-menu-item-text"><?=GetMessage("SONET_UM_MESSAGES")?></span><span class="profile-menu-item-right"></span></a>
			</div>
		</div>
	</div>
	<i class="r0"></i><i class="r1"></i><i class="r2"></i>
</div>

<div class="profile-menu-popup" id="profile-menu-popup">
	<div class="profile-menu-popup-header">
		<div class="profile-menu-popup-avatar"<?if (strlen($arResult["User"]["PersonalPhotoFile"]["src"]) > 0):?> style="background:url('<?=$arResult["User"]["PersonalPhotoFile"]["src"]?>') no-repeat center center; width: <?=$arResult["User"]["PersonalPhotoFile"]["width"]?>px; height: <?=$arResult["User"]["PersonalPhotoFile"]["height"]?>px;"<?endif;?>>
		<?
		if (
			(array_key_exists("IS_HONOURED", $arResult) && $arResult["IS_HONOURED"])
			&&
			(array_key_exists("IS_BIRTHDAY", $arResult) && $arResult["IS_BIRTHDAY"])
		):
			?><div class="profile-menu-birthday-medal" title="<?=GetMessage("SONET_UM_BIRTHDAY")?>. <?=GetMessage("SONET_UM_HONOUR")?>"></div><?
		elseif (array_key_exists("IS_HONOURED", $arResult) && $arResult["IS_HONOURED"]):
			?><div class="profile-menu-medal" title="<?=GetMessage("SONET_UM_HONOUR")?>"></div><?
		elseif (array_key_exists("IS_BIRTHDAY", $arResult) && $arResult["IS_BIRTHDAY"]):
			?><div class="profile-menu-birthday" title="<?=GetMessage("SONET_UM_BIRTHDAY")?>"></div><?
		endif;		
		?>
		</div>
		<div class="profile-menu-popup-title">
			<div class="profile-menu-popup-name"><?=$arResult["User"]["NAME_FORMATTED"]?></div>
			<div class="profile-menu-popup-description"><?=$arResult["User"]["WORK_POSITION"]?></div><?
			if (
				(array_key_exists("IS_ONLINE", $arResult) && $arResult["IS_ONLINE"])
				||
				(array_key_exists("IS_ABSENT", $arResult) && $arResult["IS_ABSENT"])
			):
				if (array_key_exists("IS_ONLINE", $arResult) && $arResult["IS_ONLINE"]):
					?><div class="profile-menu-popup-location profile-menu-popup-location-online"><?
					echo GetMessage("SONET_UM_ONLINE");
				else:
					?><div class="profile-menu-popup-location"><?
				endif;
				if (array_key_exists("IS_ABSENT", $arResult) && $arResult["IS_ABSENT"]):
					echo " <span>".GetMessage("SONET_UM_ABSENT")."</span>";
				endif;
				?></div><?
			endif;
			?>
		</div>
	</div>
	<div class="profile-menu-popup-items"><?
		if ($arResult["CurrentUserPerms"]["Operations"]["modifyuser"]):
			if ($arResult["CurrentUserPerms"]["Operations"]["modifyuser_main"]):
				?><div class="popup-window-hr"><i></i></div>
				<a title="<?=GetMessage("SONET_UM_EDIT_PROFILE")?>" class="profile-menu-popup-item profile-menu-popup-item-editprofile" href="<?=$arResult["Urls"]["Edit"]?>"><span class="profile-menu-popup-item-left"></span><span class="profile-menu-popup-item-icon"></span><span class="profile-menu-popup-item-text"><?=GetMessage("SONET_UM_EDIT_PROFILE")?></span><span class="profile-menu-popup-item-right"></span></a><?
			endif;
			if (!CModule::IncludeModule('extranet') || !CExtranet::IsExtranetSite()):
				?><div class="popup-window-hr"><i></i></div>
				<a title="<?=GetMessage("SONET_UM_EDIT_SETTINGS")?>" class="profile-menu-popup-item profile-menu-popup-item-editsettings" href="<?=$arResult["Urls"]["Settings"]?>"><span class="profile-menu-popup-item-left"></span><span class="profile-menu-popup-item-icon"></span><span class="profile-menu-popup-item-text"><?=GetMessage("SONET_UM_EDIT_SETTINGS")?></span><span class="profile-menu-popup-item-right"></span></a><?
			endif;
			?><div class="popup-window-hr"><i></i></div>
			<a title="<?=GetMessage("SONET_UM_EDIT_FEATURES")?>" class="profile-menu-popup-item profile-menu-popup-item-editfeatures" href="<?=$arResult["Urls"]["Features"]?>"><span class="profile-menu-popup-item-left"></span><span class="profile-menu-popup-item-icon"></span><span class="profile-menu-popup-item-text"><?=GetMessage("SONET_UM_EDIT_FEATURES")?></span><span class="profile-menu-popup-item-right"></span></a><?
		endif;
		
		if (
			(
				$arResult["CurrentUserPerms"]["IsCurrentUser"] 
				|| $arResult["CurrentUserPerms"]["Operations"]["viewprofile"]
			)
			&& !class_exists("CSocNetSubscription")
		):
			?><div class="popup-window-hr"><i></i></div>
			<a title="<?=GetMessage("SONET_UM_SUBSCRIBE")?>" class="profile-menu-popup-item profile-menu-popup-item-subscribe" href="<?=$arResult["Urls"]["Subscribe"]?>"><span class="profile-menu-popup-item-left"></span><span class="profile-menu-popup-item-icon"></span><span class="profile-menu-popup-item-text"><?=GetMessage("SONET_UM_SUBSCRIBE")?></span><span class="profile-menu-popup-item-right"></span></a><?
		endif;	
		?>
	</div>
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
</script>
<?
$this->EndViewTarget();
?>