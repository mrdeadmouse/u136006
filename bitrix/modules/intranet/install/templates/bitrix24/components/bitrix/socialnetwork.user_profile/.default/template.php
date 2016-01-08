<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (!empty($arResult["FatalError"]))
{
	echo  $arResult["FatalError"];
	return;
}
if (empty($arResult["User"]))
	return;

global $USER;
$arUser = $arResult["User"];
if ($arUser["ACTIVITY_STATUS"] == "fired")
	$userActive = "Y";
elseif($arUser["ACTIVITY_STATUS"] == "invited")
	$userActive = "D";
else
	$userActive = "N";
?>

<div class="user-profile-block-wrap">
	<div class="user-profile-block-wrap-l">
		<table class="user-profile-img-wrap" cellspacing="0">
			<tr>
				<td><?if(is_array($arResult["User"]["PersonalPhotoFile"])):?>
						<?=$arResult["User"]["PersonalPhotoImg"]?>
					<?else:?>
						<span class="user-profile-img-default"></span>
					<?endif?>
				</td>
			</tr>
		</table>
		
		<?if (
			$arUser["ACTIVITY_STATUS"] != "fired" 
			&& $arUser["ACTIVITY_STATUS"] != "invited"
			&& $USER->GetID() != $arUser['ID']
		):?>
			<a class="webform-small-button webform-small-button-accept" href="javascript:void(0)" onclick="if (BX.IM) { BXIM.openMessenger(<?=$arUser['ID']?>); return false; } else { window.open('<?echo $url ?>', '', 'status=no,scrollbars=yes,resizable=yes,width=700,height=550,top='+Math.floor((screen.height - 550)/2-14)+',left='+Math.floor((screen.width - 700)/2-5)); return false; }"><span class="webform-small-button-left"></span><span class="webform-small-button-text"><?=GetMessage("SONET_SEND_MESSAGE")?></span><span class="webform-small-button-right"></span></a>
			<br/><br/>
			<a class="webform-small-button webform-small-button-blue webform-small-button-video" id="im-call-button" href="javascript:void(0)" onclick="if (BXIM) { BXIM.callTo(<?=$arUser['ID']?>); return false; }"><span class="webform-small-button-icon"></span><span><?=GetMessage("SONET_VIDEO_CALL")?></span></a>
			<script type="text/javascript">
				BX.ready(function(){
					if (!BXIM || !BXIM.checkCallSupport())
					{
						BX.remove(BX('im-call-button'));
					}
				});
			</script>
		<?endif;?>
	</div>
	<div class="user-profile-block-wrap-r">
		<?
		if ($arResult['CAN_EDIT_USER'] || $USER->GetID() == $arUser["ID"])
		{
		?>
		<div class="user-profile-events">
			<div class="user-profile-events-title"><?=GetMessage("SONET_ACTIONS")?></div>
			<div class="user-profile-events-cont">
				<a href="<?=CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_EDIT'], array("user_id" => $arUser["ID"]))?>" class="user-profile-events-item user-profile-edit"><i></i><?=GetMessage("SONET_EDIT_PROFILE")?></a><?
				if ($arUser["ACTIVITY_STATUS"] == "invited" && ($USER->CanDoOperation('bitrix24_invite') && CModule::IncludeModule('bitrix24') || !IsModuleInstalled("bitrix24") && $USER->CanDoOperation('edit_all_users'))):
					?><a id="link" href="javascript:void(0)" class="user-profile-events-item  user-profile-add-sub" onclick="socnetUserProfileObj.reinvite('<?=CUtil::JSEscape($arUser["ID"])?>', '<?=($arUser["IS_EXTRANET"] ? "Y" : "N")?>', this);">
						<i></i><?=GetMessage("SONET_REINVITE")?>
					</a><?
				endif;

				if ($USER->CanDoOperation("edit_all_users") && $USER->GetID() != $arUser['ID']):?>
					<a href="javascript:void(0)" onclick="socnetUserProfileObj.changeUserActivity('<?=CUtil::JSEscape($arUser["ID"])?>', '<?=CUtil::JSEscape($userActive)?>');" class="user-profile-events-item user-profile-dismiss"><i></i><?if ($arUser["ACTIVITY_STATUS"] == "invited") echo GetMessage('SONET_DELETE'); elseif ($arUser["ACTIVITY_STATUS"] == "fired") echo GetMessage('SONET_RESTORE');else echo GetMessage('SONET_DEACTIVATE');?></a>
				<?endif;?>

				<!--extranet to intranet-->
				<?if ($arUser["ACTIVITY_STATUS"] == "extranet" && IsModuleInstalled('bitrix24') && $USER->CanDoOperation("edit_all_users")):?>
					<a href="javascript:void(0)" onclick="socnetUserProfileObj.showExtranet2IntranetForm('<?=CUtil::JSEscape($arUser["ID"])?>'); return false;"  class="user-profile-events-item user-profile-add-sub"><i></i><?=GetMessage("SONET_EXTRANET_TO_INTRANET")?></a>
				<?endif;?>

				<!-- security-->
				<?if (
					isset($arResult["Urls"]["Security"])
					&& $arResult["User"]["OTP"]["IS_ENABLED"] !== "N"
					&& $USER->GetID() == $arUser['ID']
					&& !$arResult["User"]["OTP"]["IS_EXIST"]
				):?>
					<a href="<?=$arResult["Urls"]["Security"]?>" class="user-profile-events-item user-profile-security"><i></i><?=GetMessage("SONET_SECURITY")?></a>
				<?endif;?>

				<!-- passwords-->
				<?if (
					isset($arResult["Urls"]["Passwords"])
					&&  $USER->GetID() == $arUser['ID']
				):?>
					<a href="<?=$arResult["Urls"]["Passwords"]?>" class="user-profile-events-item user-profile-pass-app"><i></i><?=GetMessage("SONET_PASSWORDS")?></a>
				<?endif;?>

				<!-- codes -->
				<?if (
					isset($arResult["Urls"]["Codes"])
					&& $arResult["User"]["OTP"]["IS_ENABLED"] !== "N"
					&& $USER->GetID() == $arUser['ID']
					&& $arResult["User"]["OTP"]["ARE_RECOVERY_CODES_ENABLED"]
					&& $arResult["User"]["OTP"]["IS_ACTIVE"]
				):?>
					<a href="<?=$arResult["Urls"]["Codes"]?>" class="user-profile-events-item user-profile-codes"><i></i><?=GetMessage("SONET_OTP_CODES")?></a>
				<?endif;?>
			</div>

			<div class="user-profile-events-cont"><?
				if (
					file_exists($_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix/socialnetwork.admin.set")
					&& $arResult["SHOW_SONET_ADMIN"]
					
				):
					?><?
					$APPLICATION->IncludeComponent(
						"bitrix:socialnetwork.admin.set",
						"",
						Array(
							"PROCESS_ONLY" => "Y"
						),
						$component,
						array("HIDE_ICONS" => "Y")
					);
					?><a href="#" class="user-profile-events-item user-profile-adm-mode" onclick="__SASSetAdmin(); return false;"><i></i><?=GetMessage("SONET_SONET_ADMIN_ON")?></a><?
				endif;
			?></div>
		</div>
		<?
		}

		if(CModule::IncludeModule("socialnetwork") && CModule::IncludeModule("intranet"))
		{						
			$APPLICATION->IncludeComponent(
				"bitrix:intranet.absence.user",
				"gadget",
				array(
					"ID" => $arUser["ID"],						
				),
				false,
				Array("HIDE_ICONS"=>"Y")
			);
		}
		?>
	</div>
	<div class="user-profile-block-wrap-cont">
		<table class="user-profile-block" cellspacing="0">
			<tr>
				<td class="user-profile-block-title" colspan="2"><?=GetMessage("SONET_CONTACT_TITLE")?></td>
			</tr><?
			if (is_array($arResult["UserFieldsContact"]["DATA"]))
			{
				foreach ($arResult["UserFieldsContact"]["DATA"] as $field => $arUserField)
				{
					if (
						is_array($arUserField["VALUE"]) && count($arUserField["VALUE"]) > 0 
						|| !is_array($arUserField["VALUE"]) && StrLen($arUserField["VALUE"]) > 0
					)
					{
						?><tr>
							<td class="user-profile-nowrap"><?=$arUserField["NAME"].":"?></td>
							<td><?
								switch ($field)
								{
									case "PERSONAL_MOBILE":
									case "WORK_PHONE":
									case "PERSONAL_PNONE":
										echo $arUserField["VALUE"];
										if (CModule::IncludeModule('voximplant') && CVoxImplantMain::Enable($arResult["User"][$field]))
										{
										?>
											<span class="sonet_call_btn" onclick="BXIM.phoneTo('<?=CUtil::JSEscape($arResult["User"][$field])?>');"></span>
										<?
										}
										break;
									default:
										echo $arUserField["VALUE"];
								}
							?></td>
						</tr><?
					}
				}
			}

			if (is_array($arResult["UserPropertiesContact"]["DATA"]))
			{
				foreach ($arResult["UserPropertiesContact"]["DATA"] as $field => $arUserField)
				{
					if (
						is_array($arUserField["VALUE"]) && count($arUserField["VALUE"]) > 0 
						|| !is_array($arUserField["VALUE"]) && StrLen($arUserField["VALUE"]) > 0
					)
					{
						?><tr>
							<td class="user-profile-nowrap"><?=$arUserField["EDIT_FORM_LABEL"].":"?></td>
							<td><?
								$value = htmlspecialcharsbx($arUserField["VALUE"]);
								switch ($field)
								{
									case "UF_FACEBOOK":
									case "UF_LINKEDIN":
									case "UF_XING":
										$href = ((strpos($arUserField["VALUE"], "http") === false)? "http://" : "").htmlspecialcharsbx($arUserField["VALUE"]);?>
										<a href="<?=$href?>"><?=$value?></a>
										<?break;
									case "UF_TWITTER":?>
										<a href="http://twitter.com/<?=$value?>"><?=$value?></a><?
										break;
									case "UF_SKYPE":?>
										<a href="callto:<?=$value?>"><?=$value?></a><?
										break;
									default:
										$GLOBALS["APPLICATION"]->IncludeComponent(
											"bitrix:system.field.view", 
											$arUserField["USER_TYPE"]["USER_TYPE_ID"], 
											array("arUserField" => $arUserField, "inChain" => "N"),
											null,
											array("HIDE_ICONS"=>"Y")
										);
								}
							?></td>
						</tr><?
					}
				}
			}
			?>
<!--otp-->			
			<?
			if (
				$arResult["User"]["OTP"]["IS_ENABLED"] !== "N"
				&&
				(
					$USER->GetID() == $arResult["User"]["ID"]
					|| $USER->CanDoOperation('security_edit_user_otp')
				)
				&&
				(
					$arResult["User"]["OTP"]["IS_MANDATORY"]
					|| !$arResult["User"]["OTP"]["IS_MANDATORY"] && $arResult["User"]["OTP"]["IS_EXIST"]
				)
			)
			{
				?><tr>
					<td class="user-profile-block-title"><?=GetMessage("SONET_SECURITY")?></td>
				</tr>
				<tr>
					<td class="user-profile-nowrap"><?=GetMessage("SONET_OTP_AUTH")?></td>
					<td><?
						if ($arResult["User"]["OTP"]["IS_ACTIVE"])
						{
							?>
								<span class="user-profile-otp-on" style="margin-right: 15px"><?=GetMessage("SONET_OTP_ACTIVE")?></span>

								<?if ($USER->CanDoOperation('security_edit_user_otp') || !$arResult["User"]["OTP"]["IS_MANDATORY"]):?>
									<a class="user-profile-otp-link-blue" href="javascript:void(0)" onclick="socnetUserProfileObj.showOtpDaysPopup(this, '<?=CUtil::JSEscape($arResult["User"]["ID"])?>', 'deactivate')"><?=GetMessage("SONET_OTP_DEACTIVATE")?></a>
								<?endif?>

								<?if ($USER->GetID() == $arResult["User"]["ID"]):?>
									<a class="user-profile-otp-link-blue" href="<?=$arResult["Urls"]["Security"]?>"><?=GetMessage("SONET_OTP_CHANGE_PHONE")?></a>
								<?endif?>
							<?
						}
						elseif (
							!$arResult["User"]["OTP"]["IS_ACTIVE"]
							&& $arResult["User"]["OTP"]["IS_MANDATORY"]
						)
						{
							?><span class="user-profile-otp-off" style="margin-right: 15px"><?=($arResult["User"]["OTP"]["IS_EXIST"]) ? GetMessage("SONET_OTP_NOT_ACTIVE") : GetMessage("SONET_OTP_NOT_EXIST")?></span><?

							if ($arResult["User"]["OTP"]["IS_EXIST"])
							{
								?><a class="user-profile-otp-link-blue" href="javascript:void(0)" onclick="socnetUserProfileObj.activateUserOtp('<?=CUtil::JSEscape($arResult["User"]["ID"])?>')"><?=GetMessage("SONET_OTP_ACTIVATE")?></a><?
								if ($USER->GetID() == $arResult["User"]["ID"])
								{
									?><a class="user-profile-otp-link-blue" href="<?=$arResult["Urls"]["Security"]?>"><?=GetMessage("SONET_OTP_CHANGE_PHONE")?></a><?
								}
							}
							else
							{
								if ($USER->GetID() == $arResult["User"]["ID"])
								{
									?><a class="user-profile-otp-link-blue" href="<?=$arResult["Urls"]["Security"]?>"><?=GetMessage("SONET_OTP_SETUP")?></a><?
								}
								else
								{
									?><a class="user-profile-otp-link-blue" href="javascript:void(0)" onclick="socnetUserProfileObj.showOtpDaysPopup(this, '<?=CUtil::JSEscape($arResult["User"]["ID"])?>', 'defer')"><?
										?><?=GetMessage("SONET_OTP_PROROGUE")?><?
									?></a><?
								}
							}
							
							if ($arResult["User"]["OTP"]["NUM_LEFT_DAYS"])
							{
								?><span class="user-profile-otp-days"><?=GetMessage("SONET_OTP_LEFT_DAYS", array("#NUM#" => "<strong>".$arResult["User"]["OTP"]["NUM_LEFT_DAYS"]."</strong>"))?></span><?
							}
						}
						elseif (
							!$arResult["User"]["OTP"]["IS_ACTIVE"]
							&& $arResult["User"]["OTP"]["IS_EXIST"]
							&& !$arResult["User"]["OTP"]["IS_MANDATORY"]
						)
						{
							?><span class="user-profile-otp-off" style="margin-right: 15px"><?=GetMessage("SONET_OTP_NOT_ACTIVE")?></span>
							<a class="user-profile-otp-link-blue" href="javascript:void(0)" onclick="socnetUserProfileObj.activateUserOtp('<?=CUtil::JSEscape($arResult["User"]["ID"])?>')"><?=GetMessage("SONET_OTP_ACTIVATE")?></a><?
							if ($USER->GetID() == $arResult["User"]["ID"])
							{
								?><a class="user-profile-otp-link-blue" href="<?=$arResult["Urls"]["Security"]?>"><?=GetMessage("SONET_OTP_CHANGE_PHONE")?></a><?
							}

							if ($arResult["User"]["OTP"]["NUM_LEFT_DAYS"])
							{
								?><span class="user-profile-otp-days"><?=GetMessage("SONET_OTP_LEFT_DAYS", array("#NUM#" => "<strong>".$arResult["User"]["OTP"]["NUM_LEFT_DAYS"]."</strong>"))?></span><?
							}
						}
					?></td>
				</tr>
				<!-- passwords --><?
				if ($USER->GetID() == $arResult["User"]["ID"])
				{
					?><tr>
						<td class="user-profile-nowrap"><?=GetMessage("SONET_PASSWORDS")?></td>
						<td>
							<a href="<?=$arResult["Urls"]["Passwords"]?>"><?=GetMessage("SONET_PASSWORDS_SETTINGS")?></a>
						</td>
					</tr><?
				}
				?><!-- codes --><?
				if (
					$USER->GetID() == $arResult["User"]["ID"]
					&& $arResult["User"]["OTP"]["IS_ACTIVE"]
					&& $arResult["User"]["OTP"]["ARE_RECOVERY_CODES_ENABLED"]
				)
				{
					?><tr>
						<td class="user-profile-nowrap"><?=GetMessage("SONET_OTP_CODES")?></td>
						<td>
							<a href="<?=$arResult["Urls"]["Codes"]?>"><?=GetMessage("SONET_OTP_CODES_SHOW")?></a>
						</td>
					</tr><?
				}
				?><tr><td><br/><br/></td></tr><?
			}
			?>
<!-- // otp -->
			<tr>
				<td class="user-profile-block-title" colspan="2"><?=GetMessage("SONET_COMMON_TITLE")?></td>
			</tr><?
			if (is_array($arResult["UserFieldsMain"]["DATA"]))
			{
				foreach ($arResult["UserFieldsMain"]["DATA"] as $field => $arUserField)
				{
					if (
						is_array($arUserField["VALUE"]) && count($arUserField["VALUE"]) > 0 
						|| !is_array($arUserField["VALUE"]) && StrLen($arUserField["VALUE"]) > 0
					)
					{
						?><tr>
							<td class="user-profile-nowrap"><?=$arUserField["NAME"].":"?></td>
							<td><?=$arUserField["VALUE"];?></td>
						</tr><?
					}
				}
			}

			if (is_array($arResult["UserPropertiesMain"]["DATA"]))
			{
				foreach ($arResult["UserPropertiesMain"]["DATA"] as $field => $arUserField)
				{
					if (
						is_array($arUserField["VALUE"]) && count($arUserField["VALUE"]) > 0 
						|| !is_array($arUserField["VALUE"]) && StrLen($arUserField["VALUE"]) > 0
					)
					{
						?><tr>
							<td class="user-profile-nowrap"><?=$arUserField["EDIT_FORM_LABEL"].":"?></td>
							<td><?
								$bInChain = ($field == "UF_DEPARTMENT" ? "Y" : "N");
								$GLOBALS["APPLICATION"]->IncludeComponent(
									"bitrix:system.field.view", 
									$arUserField["USER_TYPE"]["USER_TYPE_ID"], 
									array("arUserField" => $arUserField, "inChain" => $bInChain),
									null,
									array("HIDE_ICONS"=>"Y")
								);
							?></td>
						</tr><? 										
					}
				}
			}

			if (is_array($arResult['MANAGERS']) && count($arResult['MANAGERS'])>0)
			{
				?><tr>
					<td class="user-profile-nowrap"><?=GetMessage("SONET_MANAGERS").":"?></td>
					<td><?
						$bFirst = true;
						foreach ($arResult['MANAGERS'] as $id => $sub_user)
						{
							if (!$bFirst) echo ', '; else $bFirst = false;
							$name = CUser::FormatName($arParams['NAME_TEMPLATE'], $sub_user, true, false);
							?><a class="user-profile-link" href="<?=CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER'], array("user_id" => $sub_user["ID"]))?>"><?=$name?></a><?
							if (strlen($sub_user["WORK_POSITION"]) > 0) echo " (".$sub_user["WORK_POSITION"].")";?><?
						}
					?></td>
				</tr><?						
			}

			if (is_array($arResult['SUBORDINATE']) && count($arResult['SUBORDINATE'])>0)
			{
				?><tr>
					<td class="user-profile-nowrap"><?=GetMessage("SONET_SUBORDINATE").":"?></td>
					<td><?
						$bFirst = true;
						foreach ($arResult['SUBORDINATE'] as $id => $sub_user)
						{
							if (!$bFirst) echo ', '; else $bFirst = false;
							$name = CUser::FormatName($arParams['NAME_TEMPLATE'], $sub_user, true, false);
							?><a class="user-profile-link" href="<?=CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER'], array("user_id" => $sub_user["ID"]))?>"><?=$name?></a><?
							if (strlen($sub_user["WORK_POSITION"]) > 0) echo " (".$sub_user["WORK_POSITION"].")";?><?
						}
					?></td>
				</tr><?
			}

			if($arResult["User"]["ACTIVITY_STATUS"] != "active")
			{
				?><tr class="user-profile-status">
					<td class="user-profile-nowrap"><?=GetMessage("SONET_ACTIVITY_STATUS").":"?></td>
					<td>
						<?
						if ($arResult["User"]["ACTIVITY_STATUS"] == "admin")
						{
							?><span class="employee-admin"><span class="employee-admin-left"></span><span class="employee-admin-text"><?=GetMessage("SONET_USER_".$arUser["ACTIVITY_STATUS"])?></span><span class="employee-admin-right"></span></span><?
						}
						else
						{
							?><span class="employee-dept-post employee-dept-<?=$arUser["ACTIVITY_STATUS"]?>"><?=GetMessage("SONET_USER_".$arUser["ACTIVITY_STATUS"])?></span><?
						}
					?></td>
				</tr><?
			}

			$additional = "";
			
			if (is_array($arResult["UserFieldsPersonal"]["DATA"]))
			{
				foreach ($arResult["UserFieldsPersonal"]["DATA"] as $field => $arUserField)
				{
					if (is_array($arUserField["VALUE"]) && count($arUserField["VALUE"]) > 0 || !is_array($arUserField["VALUE"]) && StrLen($arUserField["VALUE"]) > 0)
					{
						$additional .= '<tr>
							<td class="user-profile-nowrap">'.$arUserField["NAME"].':</td>
							<td>'.$arUserField["VALUE"].'</td></tr>';
					}
				}
			}

			if (is_array($arResult["UserFieldsPersonal"]["DATA"]))
			{
				foreach ($arResult["UserPropertiesPersonal"]["DATA"] as $field => $arUserField)
				{
					if (is_array($arUserField["VALUE"]) && count($arUserField["VALUE"]) > 0 || !is_array($arUserField["VALUE"]) && StrLen($arUserField["VALUE"]) > 0)
					{
						$additional .= '<tr><td class="user-profile-nowrap">'.$arUserField["EDIT_FORM_LABEL"].':</td><td>';

						ob_start();
						$GLOBALS["APPLICATION"]->IncludeComponent(
							"bitrix:system.field.view", 
							$arUserField["USER_TYPE"]["USER_TYPE_ID"], 
							array("arUserField" => $arUserField, "inChain" => $field == "UF_DEPARTMENT" ? "Y" : "N"),
							null,
							array("HIDE_ICONS"=>"Y")
						);
						$additional .= ob_get_contents();
						ob_end_clean();

						$additional .= '</td></tr>';
					}  
				}
			}

			if (is_array($arResult["Groups"]["List"]) && count($arResult["Groups"]["List"]) > 0)
			{
				$additional .= '<tr><td class="user-profile-nowrap">'.GetMessage("SONET_GROUPS").':</td><td>';
				$bFirst = true;								
				foreach ($arResult["Groups"]["List"] as $key => $group)
				{
						if (!$bFirst) 
							$additional .= ', '; 
						$bFirst = false;
						$additional .= '<a class="user-profile-link" href="'.$group["GROUP_URL"].'">'.$group["GROUP_NAME"].'</a>';					
				}			
					$additional .= '</td></tr>';
			}

			if (strlen($additional) > 0)
			{
				?><tr>
					<td class="user-profile-block-title" colspan="2"><?=GetMessage("SONET_ADDITIONAL_TITLE")?></td>
				</tr><?
				?><?=$additional?><?
			}
		?></table>
	</div>
</div><?

if ($arUser["ACTIVITY_STATUS"] == "fired")
{
	$confirmMess = GetMessageJS('SOCNET_CONFIRM_RECOVER');
}
elseif ($arUser["ACTIVITY_STATUS"] == "invited")
{
	$confirmMess = GetMessageJS('SOCNET_CONFIRM_DELETE');
}
else
{
	$confirmMess = GetMessageJS('SOCNET_CONFIRM_FIRE');
}

$arDays = array();
for($i=1; $i<=10; $i++)
{
	$arDays[$i] = FormatDate("ddiff", time()-60*60*24*$i);
}
$arDays[0] = GetMessage("SONET_OTP_NO_DAYS");

$arJSParams = array(
	"ajaxPath" => $this->GetFolder()."/ajax.php",
	"siteId" => SITE_ID,
	"otpDays" => $arDays,
	"showOtpPopup" => (isset($_GET["otp"]) && $_GET["otp"] == "Y") ? "Y" : "N",
	"otpRecoveryCodes" => $arResult["IS_OTP_RECOVERY_CODES_ENABLE"] ? "Y" : "N",
	"profileUrl" => $arResult["Urls"]["User"],
	"passwordsUrl" => $arResult["Urls"]["Passwords"],
	"codesUrl" => $arResult["Urls"]["Codes"],
);
?>
<script type="text/javascript">
	BX.message({
		USER_PROFILE_CONFIRM : "<?=$confirmMess?>",
		SONET_ERROR_DELETE : "<?=GetMessageJS("SONET_ERROR_DELETE")?>",
		BX24_TITLE : "<?=GetMessageJS("BX24_TITLE")?>",
		BX24_BUTTON : "<?=GetMessageJS("BX24_BUTTON")?>",
		BX24_CLOSE_BUTTON : "<?=GetMessageJS("BX24_CLOSE_BUTTON")?>",
		BX24_LOADING : "<?=GetMessageJS("BX24_LOADING")?>",
		BX24_EXTR_USER_ID: "<?=CUtil::JSEscape($arUser["ID"])?>",
		SONET_REINVITE_ACCESS: "<?=GetMessageJS("SONET_REINVITE_ACCESS")?>",
		SONET_OTP_SUCCESS_POPUP_TEXT : "<?=GetMessageJS('SONET_OTP_SUCCESS_POPUP_TEXT_NEW')?>",
		SONET_OTP_SUCCESS_POPUP_TEXT_RES_CODE : "<?=GetMessageJS('SONET_OTP_SUCCESS_POPUP_TEXT_RES_CODE')?>",
		SONET_OTP_SUCCESS_POPUP_TEXT2 : "<?=GetMessageJS('SONET_OTP_SUCCESS_POPUP_TEXT_NEW2')?>",
		SONET_OTP_SUCCESS_POPUP_CLOSE : "<?=GetMessageJS('SONET_OTP_SUCCESS_POPUP_CLOSE')?>",
		SONET_OTP_SUCCESS_POPUP_PASSWORDS : "<?=GetMessageJS('SONET_OTP_SUCCESS_POPUP_PASSWORDS')?>",
		SONET_OTP_CODES : "<?=GetMessageJS('SONET_OTP_CODES')?>"
	});

	var socnetUserProfileObj = new BX.Socialnetwork.User.Profile(<?=CUtil::PhpToJSObject($arJSParams)?>);
</script>
