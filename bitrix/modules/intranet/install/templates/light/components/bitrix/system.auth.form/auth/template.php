<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if ($arResult["FORM_TYPE"] == "login"):
	?>
	<div id="user-block" class="user-block-auth">
		<div id="user-block-inner">
			<div id="user-block-gradient">
				<?if (is_array($arResult['ERROR_MESSAGE']) && array_key_exists("MESSAGE", $arResult["ERROR_MESSAGE"]) && strlen($arResult["ERROR_MESSAGE"]["MESSAGE"]) > 0):?>
					<div class="user-block-auth-error"><?=$arResult["ERROR_MESSAGE"]["MESSAGE"];?></div>
				<?endif?>

				<form method="post" target="_top" action="<?=$arResult["AUTH_URL"]?>">
				<?if (strlen($arResult["BACKURL"]) > 0):?>
					<input type="hidden" name="backurl" value="<?=$arResult["BACKURL"]?>" />
				<?endif?>
				<?foreach ($arResult["POST"] as $key => $value):?>
					<input type="hidden" name="<?=$key?>" value="<?=$value?>" />
				<?endforeach?>
				<input type="hidden" name="AUTH_FORM" value="Y" />
				<input type="hidden" name="TYPE" value="AUTH" />
				<table cellspacing="0" id="auth-form">
					<tr>
						<td>&nbsp;</td>
						<td>
						<?if($arResult["NEW_USER_REGISTRATION"] == "Y"):?>
							<a href="<?=$arResult["AUTH_REGISTER_URL"]?>"><?=GetMessage("AUTH_REGISTER")?></a>&nbsp;&nbsp;&nbsp;
						<?endif?>
						<a title="<?=GetMessage("AUTH_FORGOT_PASSWORD_2")?>" href="<?=$arResult["AUTH_FORGOT_PASSWORD_URL"]?>">?</a></td>
					</tr>
					<tr>
						<td class="field-name"><label for="login-textbox"><?=GetMessage("AUTH_LOGIN")?>:</label></td>
						<td><input type="text" name="USER_LOGIN" id="login-textbox" class="textbox" value="<?=$arResult["USER_LOGIN"]?>" tabindex="1" /></td>
					</tr>
					<tr>
						<td class="field-name"><label for="password-textbox"><?=GetMessage("AUTH_PASSWORD")?>:</label></td>
						<td><input type="password" name="USER_PASSWORD" id="password-textbox" class="textbox" tabindex="2" /></td>
					</tr>
					<?if($arResult["AUTH_SERVICES"]):?>
					<tr>
						<td colspan="2">
							<div class="bx-auth-lbl"><?=GetMessage("socserv_as_user_form")?></div>
							<?
							$APPLICATION->IncludeComponent("bitrix:socserv.auth.form", "icons",
								array(
									"AUTH_SERVICES"=>$arResult["AUTH_SERVICES"],
									"SUFFIX"=>"form",
								),
								$component,
								array("HIDE_ICONS"=>"Y")
							);
							?>
						</td>
					</tr>
					<?endif?>
					<tr>
						<td>&nbsp;</td>
						<td><input type="submit" class="submit" name="Login" value="<?=GetMessage("AUTH_LOGIN_BUTTON")?>" tabindex="5" /><?if ($arResult["STORE_PASSWORD"] == "Y"):?>&nbsp;<input type="checkbox" name="USER_REMEMBER" class="checkbox" id="remember-checkbox" value="Y" tabindex="4" checked="checked" /><label class="remember" for="remember-checkbox"><?=GetMessage("AUTH_REMEMBER_ME")?></label><?endif?></td>
					</tr>
				</table>
				</form>
				<?if($arResult["AUTH_SERVICES"]):?>
				<?
				$APPLICATION->IncludeComponent("bitrix:socserv.auth.form", ".default",
					array(
						"AUTH_SERVICES"=>$arResult["AUTH_SERVICES"],
						"AUTH_URL"=>$arResult["AUTH_URL"],
						"POST"=>$arResult["POST"],
						"POPUP"=>"Y",
						"SUFFIX"=>"form",
					),
					$component,
					array("HIDE_ICONS"=>"Y")
				);
				?>
				<?endif?>
			</div>
		</div>
		<div id="user-block-corners"></div>
	</div>
<?else:

	$isNTLM = false;
	if (COption::GetOptionString("ldap", "use_ntlm", "N") == "Y")
	{
		$ntlm_varname = trim(COption::GetOptionString("ldap", "ntlm_varname", "REMOTE_USER"));
		if (array_key_exists($ntlm_varname, $_SERVER) && strlen($_SERVER[$ntlm_varname]) > 0)
			$isNTLM = true;
	}

	$params = DeleteParam(array("logout", "login", "back_url_pub"));
	$logoutUrl = $APPLICATION->GetCurPage()."?logout=yes".htmlspecialcharsbx($params == ""? "":"&".$params);

	$userIndicators = "";

	$arResult['EXTERNAL_MAIL_CNT'] = intval($arResult['EXTERNAL_MAIL_CNT']);
	if (strlen($arResult['urlToExternalMailbox']) > 0 && $arResult['EXTERNAL_MAIL_CNT'])
	{
		$userIndicators .= sprintf(
			'<a id="menu_extmail_counter" class="user-indicator user-indicator-mail" href="%s" target="_blank" title="%s" %s><span class="user-indicator-icon"></span><span class="user-indicator-text">%s</span></a>',
			$arResult['urlToExternalMailbox'], GetMessage('AUTH_EXTMAIL'),
			$arResult['EXTERNAL_MAIL_CNT'] > 0 ? '' : 'style="display: none; "',
			$arResult['EXTERNAL_MAIL_CNT']
		);
		$userIndicators .= sprintf(
			'<a id="menu_extmail_warning" class="user-indicator user-indicator-mail" href="%s" title="%s" %s><span class="user-indicator-icon"></span><span class="user-indicator-warning"></span></a>',
			$arResult["urlToMailboxSetup"], GetMessage('AUTH_EXTMAIL_ERR'),
			$arResult['EXTERNAL_MAIL_CNT'] < 0 ? '' : 'style="display: none; "'
		);
	}

	$arResult['EXCHANGE_CNT'] = intval($arResult['EXCHANGE_CNT']);
	if (strlen($arResult['urlToExchangeBox']) > 0 && $arResult['EXCHANGE_CNT'] > 0)
	{
		$userIndicators .= sprintf(
			'<a class="user-indicator user-indicator-mail" href="%s" title="%s" target="_blank"><span class="user-indicator-icon"></span><span class="user-indicator-text">%s</span></a>',
			$arResult['urlToExchangeBox'], GetMessage('AUTH_EXCHANGE'), $arResult['EXCHANGE_CNT']
		);
	}
	
	if (!IsModuleInstalled("im") && CBXFeatures::IsFeatureEnabled('WebMessenger'))
	{
		$arRes = $APPLICATION->IncludeComponent("bitrix:socialnetwork.events_dyn", "", Array(
				"PATH_TO_USER"				=>	$arParams["PATH_TO_SONET_PROFILE"],
				"PATH_TO_GROUP"				=>	$arParams["PATH_TO_SONET_GROUP"],
				"PATH_TO_MESSAGES"			=>	$arParams["PATH_TO_SONET_MESSAGES"],
				"PATH_TO_MESSAGE_FORM"		=>	$arParams["PATH_TO_SONET_MESSAGE_FORM"],
				"PATH_TO_MESSAGE_FORM_MESS"	=>	$arParams["PATH_TO_SONET_MESSAGE_FORM_MESS"],
				"PATH_TO_MESSAGES_CHAT"		=>	$arParams["PATH_TO_SONET_MESSAGES_CHAT"],
				"JAVASCRIPT_ONLY" => "Y",
				"PATH_TO_SMILE"	=>	"/bitrix/images/socialnetwork/smile/",
				"MESSAGE_VAR"	=>	"message_id",
				"PAGE_VAR"	=>	"page",
				"USER_VAR"	=>	"user_id"
			),
			false,
			array("HIDE_ICONS" => "Y")
		);
	}
	
	if (is_array($arRes) && array_key_exists("arResult", $arRes) && array_key_exists("ITEMS_TOTAL", $arRes["arResult"]) && intval($arRes["arResult"]["ITEMS_TOTAL"]) > 0)
		$userIndicators .= '<a class="user-indicator user-indicator-messages" href="'.$arResult["urlToOwnMessages"].'" title="'.GetMessage("AUTH_NEW_MESSAGES").'"><span class="user-indicator-icon"></span><span class="user-indicator-text">'.intval($arRes["arResult"]["ITEMS_TOTAL"]).'</span></a>';
	
	if(strlen($arResult["urlToOwnBizProc"]) > 0 && array_key_exists("SHOW_BIZPROC", $arResult) && $arResult["SHOW_BIZPROC"] && intval($arResult["BZP_CNT"]) > 0)
		$userIndicators .= '<a class="user-indicator user-indicator-activities" href="'.$arResult["urlToOwnBizProc"].'" title="'.GetMessage("AUTH_BZP").'"><span class="user-indicator-icon"></span><span class="user-indicator-text">'.intval($arResult["BZP_CNT"]).'</span></a>';
	
	$userIndicators .= '<a style="visibility: '.(intval($arResult["LOG_ITEMS_TOTAL"]) > 0 ? "visible" : "hidden").';" class="user-indicator user-indicator-updates" href="'.$arResult["urlToOwnLog"].'" title="'.GetMessage("AUTH_LOG").'"><span class="user-indicator-icon"></span><span class="user-indicator-text" id="menu-counter-live-feed">'.intval($arResult["LOG_ITEMS_TOTAL"]).'</span></a>';

	$ustat = $APPLICATION->IncludeComponent("bitrix:intranet.ustat.status", "lite");
	$userIndicators .= '<span class="user-indicator user-indicator-pulse" id="user-indicator-pulse" title="'.GetMessage('INTRANET_USTAT_WIDGET_TITLE').'"><span class="user-indicator-icon"></span><span class="user-indicator-text"><span title="'.GetMessage('INTRANET_USTAT_WIDGET_ACTIVITY_HELP').'">'.$ustat['ACTIVITY'].' /</span><span title="'.GetMessage('INTRANET_USTAT_WIDGET_INVOLVEMENT_HELP').'"> '.$ustat['INVOLVEMENT'].'%</span></span></span>';

	if (strlen($userIndicators) > 0)
		$APPLICATION->AddViewContent("user-indicators", '<span class="user-indicators">'.$userIndicators.'</span>');
?>
	<script type="text/javascript">

		function ToggleUserMenu()
		{
			var userBlock = BX("user-block", true);
			if (BX.hasClass(userBlock, "user-block-auth"))
				return;

			var topMenu = BX("top-menu", true);
			if (!topMenu)
				return;
			var topMenuWidth = topMenu.offsetWidth;
			var userBlockLeft = userBlock.offsetLeft;

			if (topMenuWidth > userBlockLeft)
				BX.addClass(userBlock, "user-block-collapsed");
			else
				BX.removeClass(userBlock, "user-block-collapsed");
		}


		function GetWindowWidth()
		{
			var width = 0;
			if (self.innerHeight)
				width = window.innerWidth;
			else if (document.documentElement && document.documentElement.clientWidth)
				width = document.documentElement.clientWidth;
			else if (document.body) // other Explorers
				width = document.body.clientWidth;

			return width;
		}

		BX.ready(function() {
			ToggleUserMenu();
			BX.bind(window, "resize", ToggleUserMenu);

			BX.addCustomEvent(window, "onImUpdateCounter", function(counters)
			{
				if (!counters)
					return;

					var counter = BX("menu-counter-live-feed");
					if (!counter)
						return;

					if (counters["**"] > 0)
					{
						counter.innerHTML = counters["**"] > 50 ? "50+" : counters["**"];
						counter.parentNode.style.visibility = "visible";						
					}
					else
						counter.parentNode.style.visibility = "hidden";
			});

		});

		if (GetWindowWidth() > 1100 )
			document.write('<div id="user-block" onmouseover="BX.removeClass(this, \'user-block-collapsed\')" onmouseout="ToggleUserMenu()">');
		else
			document.write('<div id="user-block" class="user-block-collapsed" onmouseover="BX.removeClass(this, \'user-block-collapsed\')" onmouseout="ToggleUserMenu()">');

	</script>
		<div id="user-block-inner">
			<div id="user-block-gradient">
				<div id="user-info">
					<a id="user-info-avatar" <?if (strlen($arResult["urlToOwnProfile"]) > 0):?>href="<?=$arResult["urlToOwnProfile"]?>"<?endif;?> <?if (strlen($arResult["USER_PERSONAL_PHOTO_SRC"]) > 0):?>style="background-image: url('<?=$arResult["USER_PERSONAL_PHOTO_SRC"]?>'); background-repeat: no-repeat; background-position: center center;"<?endif;?>></a>
					<span id="user-name"><?=$arResult["USER_NAME"]?></span>
					<? if (strlen($arResult["urlToMyPortal"]) > 0):
						?><a id="user-desktop" href="<?=$arResult["urlToMyPortal"]?>"><?=GetMessage("AUTH_MP")?></a><?
					endif;?>
					<?if ($isNTLM === false):?><a id="user-logout" href="<?=$logoutUrl?>"><?=GetMessage("AUTH_LOGOUT")?></a><?endif?>
				</div>
				<table id="user-menu" cellspacing="0">
					<tr>
						<td class="left-column">
							<ul class="user-menu-items">

							<? if (strlen($arResult["urlToOwnLog"]) > 0):?>
								<li class="user-menu-item"><a class="user-menu-item-link" href="<?=$arResult["urlToOwnLog"]?>"><span class="user-menu-item-left"></span><span class="user-menu-item-icon"></span><span class="user-menu-item-text"><?=GetMessage("AUTH_LOG")?></span><span class="user-menu-item-right"></span></a></li>
							<?endif;?>
								
							<? if (strlen($arResult["urlToOwnCalendar"]) > 0):
								?><li class="user-menu-item"><a class="user-menu-item-link" href="<?=$arResult["urlToOwnCalendar"]?>"><span class="user-menu-item-left"></span><span class="user-menu-item-icon"></span><span class="user-menu-item-text"><?=GetMessage("AUTH_CALENDAR")?></span><span class="user-menu-item-right"></span></a></li><?
							endif;?>

							<? if (strlen($arResult["urlToOwnGroups"]) > 0):
								?><li class="user-menu-item"><a class="user-menu-item-link" href="<?=$arResult["urlToOwnGroups"]?>"><span class="user-menu-item-left"></span><span class="user-menu-item-icon"></span><span class="user-menu-item-text"><?=GetMessage("AUTH_GROUPS")?></span><span class="user-menu-item-right"></span></a></li><?
							endif;?>

							<? if (strlen($arResult["urlToOwnBlog"]) > 0):
								?><li class="user-menu-item"><a class="user-menu-item-link" href="<?=$arResult["urlToOwnBlog"]?>"><span class="user-menu-item-left"></span><span class="user-menu-item-icon"></span><span class="user-menu-item-text"><?=GetMessage("AUTH_NEW_MESSAGES")?></span><span class="user-menu-item-right"></span></a></li><?
							endif;?>

							<? if (strlen($arResult["urlToOwnTasks"]) > 0):
								?><li class="user-menu-item"><a class="user-menu-item-link" href="<?=$arResult["urlToOwnTasks"]?>"><span class="user-menu-item-left"></span><span class="user-menu-item-icon"></span><span class="user-menu-item-text"><?=GetMessage("AUTH_TASKS")?></span><span class="user-menu-item-right"></span></a></li><?
							endif;?>
							</ul>
						</td>

						<td class="right-column">
							<ul class="user-menu-items">

							<? if (strlen($arResult["urlToOwnProfile"]) > 0):?>
								<li class="user-menu-item"><a class="user-menu-item-link" href="<?=$arResult["urlToOwnProfile"]?>"><span class="user-menu-item-left"></span><span class="user-menu-item-icon"></span><span class="user-menu-item-text"><?=GetMessage("AUTH_PERSONAL_PAGE")?></span><span class="user-menu-item-right"></span></a></li>
							<?endif;?>
							
							<? if (strlen($arResult["urlToOwnMicroBlog"]) > 0):
								?><li class="user-menu-item"><a class="user-menu-item-link" href="<?=$arResult["urlToOwnMicroBlog"]?>"><span class="user-menu-item-left"></span><span class="user-menu-item-icon"></span><span class="user-menu-item-text"><?=GetMessage("AUTH_MICROBLOG")?></span><span class="user-menu-item-right"></span></a></li><?
							endif;?>

							<? if (strlen($arResult["urlToOwnFiles"]) > 0):
								?><li class="user-menu-item"><a class="user-menu-item-link" href="<?=$arResult["urlToOwnFiles"]?>"><span class="user-menu-item-left"></span><span class="user-menu-item-icon"></span><span class="user-menu-item-text"><?=GetMessage("AUTH_FILES")?></span><span class="user-menu-item-right"></span></a></li><?
							endif;?>

							<? if (strlen($arResult["urlToOwnPhoto"]) > 0):
								?><li class="user-menu-item"><a class="user-menu-item-link" href="<?=$arResult["urlToOwnPhoto"]?>"><span class="user-menu-item-left"></span><span class="user-menu-item-icon"></span><span class="user-menu-item-text"><?=GetMessage("AUTH_PHOTO")?></span><span class="user-menu-item-right"></span></a></li><?
							endif;?>

							</ul>
						</td>
					</tr>
				</table>
				
				<div id="user-info-menu"><span class="user-info-menu-text"><?=GetMessage("AUTH_USER_MENU")?></span><span class="user-info-menu-arrow"></span></div>
			</div>
		</div>
		<div id="user-block-corners"></div>
	</div>
<?endif?>