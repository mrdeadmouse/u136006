<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
CUtil::InitJSCore(array("popup"));

if (
	!isset($arResult["User"]["ID"])
	|| (
		$USER->IsAuthorized()
		&& $arResult["User"]["ID"] == $USER->GetID()
		&& $arParams["PAGE_ID"] != "user"
	)
)
{
	return;
}

//TODO: Hack for the task list component. Remove it when Tasks fixes SetPageProperty("BodyClass").
AddEventHandler("main", "OnEpilog", "__setBodyClass");
if (!function_exists("__setBodyClass"))
{
	function __setBodyClass()
	{
		global $APPLICATION;
		$currentBodyClass = $APPLICATION->GetPageProperty("BodyClass");
		$APPLICATION->SetPageProperty("BodyClass", ($currentBodyClass ? $currentBodyClass." " : "")."subtitle-mode");
	}
}

$this->SetViewTarget("above_pagetitle", 100);
?>

<div class="profile-menu"><div class="profile-menu-inner">
	<a href="<?=$arResult["Urls"]["main"]?>" class="profile-menu-avatar<?if (!array_key_exists("IS_ONLINE", $arResult) ||! $arResult["IS_ONLINE"]):?> profile-menu-avatar-offline<?endif?>"<?if (strlen($arResult["User"]["PersonalPhotoFile"]["src"]) > 0):?> style="background:url('<?=$arResult["User"]["PersonalPhotoFile"]["src"]?>') no-repeat center center; <?endif;?>"><i></i></a>
	<div class="profile-menu-info<?=($arResult["User"]["IS_EXTRANET"] == "Y" ? " profile-menu-user-info-extranet" : "")?>">
		<a href="<?=$arResult["Urls"]["main"]?>" class="profile-menu-name"><?=$arResult["User"]["NAME_FORMATTED"]?></a><?if (array_key_exists("IS_ABSENT", $arResult) && $arResult["IS_ABSENT"]):?><span class="profile-menu-status"><?=GetMessage("SONET_UM_ABSENT")?></span><?endif;?><?if (isset($arResult["User"]["ID"])):?><span class="profile-menu-user-menu" onclick="openProfileMenuPopup(this);"></span><?endif?><?if(strlen($arResult["User"]["WORK_POSITION"]) > 0):?><span class="profile-menu-description"><?=$arResult["User"]["WORK_POSITION"]?></span><?endif?><?if(array_key_exists("IS_BIRTHDAY", $arResult) && $arResult["IS_BIRTHDAY"]):?><span
		class="profile-menu-birthday-icon" title="<?=GetMessage("SONET_UM_BIRTHDAY")?>"></span><?endif?><?if(array_key_exists("IS_HONOURED", $arResult) && $arResult["IS_HONOURED"]):?><span class="profile-menu-leaderboard-icon" title="<?=GetMessage("SONET_UM_HONOUR")?>"></span><?endif?>
	</div>
	<div id="profile-menu-filter" class="profile-menu-filter"><?
		?><a href="<?=$arResult["Urls"]["Main"]?>" class="filter-but-wrap<?if ($arParams["PAGE_ID"] == "user"):?> filter-but-act<?endif?>"><span class="filter-but-left"></span><span class="filter-but-text-block"><?=GetMessage("SONET_UM_GENERAL")?></span><span class="filter-but-right"></span></a><?
		if (is_array($arResult["CanView"]))
		{
			foreach ($arResult["CanView"] as $key => $val)
			{
				if (!$val)
					continue;
				?><a href="<?=$arResult["Urls"][$key]?>" class="filter-but-wrap<?if ($arParams["PAGE_ID"] == "user_".$key):?> filter-but-act<?endif?>"><span class="filter-but-left"></span><span class="filter-but-text-block"><?=$arResult["Title"][$key]?></span><span class="filter-but-right"></span></a><?
			}
		}
		?>
	</div>
</div></div>

<script type="text/javascript">
function openProfileMenuPopup(bindElement)
{
	BX.addClass(bindElement, "profile-menu-user-active");

	var menu = [];

	<?if ($arResult["CAN_MESSAGE"] && $arResult["User"]["ACTIVE"] != "N"):?>
		menu.push(
			{
				text : "<?=GetMessage("SONET_UM_SEND_MESSAGE")?>",
				className : "profile-menu-message",
				onclick : function() {
					this.popupWindow.close();
					BXIM.openMessenger(<?=$arResult["User"]["ID"]?>);
				}
			}
		);
	<?endif;

	if ($arResult["CAN_MESSAGE_HISTORY"]):?>
		menu.push(
			{ 
				text : "<?=GetMessage("SONET_UM_MESSAGE_HISTORY")?>", 
				className : "profile-menu-history", 
				onclick : function() { 
					this.popupWindow.close(); 
					BXIM.openHistory(<?=$arResult["User"]["ID"]?>);
				} 
			}
		);
	<?endif;

	if ($arResult["CurrentUserPerms"]["Operations"]["modifyuser"])
	{
		if ($arResult["CurrentUserPerms"]["Operations"]["modifyuser_main"])
		{
			?>
			menu.push(
				{ 
					text : "<?=GetMessage("SONET_UM_EDIT_PROFILE")?>", 
					title: "<?=GetMessage("SONET_UM_EDIT_PROFILE")?>", 
					className : "profile-menu-profiledit", 
					href: "<?=CUtil::JSUrlEscape($arResult["Urls"]["Edit"])?>"
				}
			);
			<?
		}
		?>
		menu.push(
			{ 
				text : "<?=GetMessage("SONET_UM_REQUESTS")?>", 
				title: "<?=GetMessage("SONET_UM_REQUESTS")?>", 
				className : "profile-menu-requests", 
				href: "<?=CUtil::JSUrlEscape($arResult["Urls"]["UserRequests"])?>"
			}
		);
		<?
	}

	if (
		($arResult["CurrentUserPerms"]["IsCurrentUser"] || $arResult["CurrentUserPerms"]["Operations"]["viewprofile"])
		&& !class_exists("CSocNetSubscription")
	):
		?>
		menu.push(
			{ 
				text : "<?=GetMessage("SONET_UM_SUBSCRIBE")?>", 
				title: "<?=GetMessage("SONET_UM_SUBSCRIBE")?>", 
				className : "profile-menu-subscribe", 
				href: "<?=CUtil::JSUrlEscape($arResult["Urls"]["Subscribe"])?>"
			}
		);
	<?endif;

	if (IsModuleInstalled("bitrix24") && $arResult["CurrentUserPerms"]["Operations"]["modifyuser"]):?>
		menu.push(
			{
				text : "<?=GetMessage("SONET_TELEPHONY_HISTORY")?>",
				title: "<?=GetMessage("SONET_TELEPHONY_HISTORY")?>",
				className : "profile-menu-requests",
				href: "/settings/telephony/detail.php?USER_ID=<?=$arResult["User"]["ID"]?>"
			}
		);
	<?endif;

	/*if ($arResult["CurrentUserPerms"]["Operations"]["videocall"] && $arParams['PATH_TO_VIDEO_CALL']):
		?>
		menu.push(
			{
				text : "<?=GetMessage("SONET_UM_VIDEO_CALL")?>",
				className : "profile-menu-videocall",
				onclick : function() {
					window.open('<?echo $arResult["Urls"]["VideoCall"] ?>', '', 'status=no,scrollbars=yes,resizable=yes,width=1000,height=600,top='+Math.floor((screen.height - 600)/2-14)+',left='+Math.floor((screen.width - 1000)/2-5));
					return false;
				}
			}
		);
		<?
	endif; */
	?>
	if (menu.length > 0)
	{
		BX.PopupMenu.show("user-menu-profile", bindElement, menu, {
			offsetTop: 5,
			offsetLeft : 12,
			angle : true,
			events : {
				onPopupClose : function() {
					BX.removeClass(this.bindElement, "profile-menu-user-active");
				}
			}
		});
	}
}
</script>
<?$this->EndViewTarget();?>