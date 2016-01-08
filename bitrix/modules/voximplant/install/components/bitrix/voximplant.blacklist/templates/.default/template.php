<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$APPLICATION->SetAdditionalCSS("/bitrix/components/bitrix/voximplant.main/templates/.default/telephony.css");
?>
<div class="bx-vi-block bx-vi-options adm-workarea">
	<table cellpadding="0" cellspacing="0" border="0" class="bx-edit-tab-title" style="width: 100%; ">
		<tr>
			<td class="bx-form-title">
				<?=GetMessage('BLACKLIST_TITLE')?>
			</td>
		</tr>
	</table>
</div>
<div class="tel-set-item tel-set-item-border" style="margin-top:20px; margin-bottom: 10px;">
	<form method="POST" name="BLACKLIST_SETTINGS" action="<?=POST_FORM_ACTION_URI?>">
		<?=bitrix_sessid_post();?>
		<div class="tel-set-item-cont-block">
			<div>
				<input type="checkbox" name="BLACKLIST_AUTO" id="BLACKLIST_AUTO" value="Y" <?if ($arResult["BLACKLIST_AUTO"] == "Y"):?>checked<?endif?> style="margin: 0px;"/>
				<label for="BLACKLIST_AUTO"  class="tel-set-item-bl-label" style="font-weight:bold"><?=GetMessage("BLACKLIST_ENABLE")?></label>
			</div>
		</div>
		<div id="vi_blacklist_settings_block" style="height:40px; margin: 8px 0 6px 0;" class="tel-set-item-bl-rule">
			<?=GetMessage("BLACKLIST_TEXT1")?>
			<input type="text" name="BLACKLIST_COUNT" style="width:50px; text-align: center;" class="tel-set-inp" value="<?=$arResult["BLACKLIST_COUNT"]?>" />
			<?=GetMessage("BLACKLIST_TEXT2")?>
			<input type="text" name="BLACKLIST_TIME" style="width:50px; text-align: center;" class="tel-set-inp" value="<?=$arResult["BLACKLIST_TIME"]?>" />
			<?=GetMessage("BLACKLIST_TEXT3")?>
		</div>
		<div style="padding-bottom: 10px;">
			<?=GetMessage("BLACKLIST_ENABLE_TEXT")?>
		</div>
		<span class="webform-small-button webform-small-button-accept">
			<input type="submit" name="BLACKLIST_SETTINGS_BUTTON" class="webform-small-button-text" style="border:none" value="<?=GetMessage("BLACKLIST_SAVE")?>"/>
		</span>
	</form>
</div>
<?
if (!empty($arResult["ERROR"]))
{
	echo '<div class="tel-set-cont-error" style="margin-top: 33px">'.$arResult["ERROR"].'</div>';
}
?>
<div class="tel-set-item">
	<p style="font-weight:bold"><?=GetMessage("BLACKLIST_NUMBERS")?></p>
	<div>
		<form method="POST" name="BLACKLIST_ADD" action="<?=POST_FORM_ACTION_URI?>">
			<?=bitrix_sessid_post();?>
			<input type="text" name="BLACKLIST_NEW_NUMBER" class="tel-set-inp" value="<?if (isset($_POST["BLACKLIST_NEW_NUMBER"])) echo htmlspecialcharsbx($_POST["BLACKLIST_NEW_NUMBER"])?>"/>
			<span class="webform-small-button webform-small-button-accept">
				<input type="submit" name="BLACKLIST_ADD_BUTTON" class="webform-small-button-text" style="border:none" value="<?=GetMessage("BLACKLIST_NUMBER_ADD")?>" />
			</span>
		</form>
	</div>
	<div class="tel-bl-phone-box">
	<?if (is_array($arResult["ITEMS"]) && !empty($arResult["ITEMS"])):?>
		<?foreach($arResult["ITEMS"] as $arItem):?>
			<div class="tel-bl-phone">
				<span class="tel-bl-phone-text"><?=$arItem["PHONE_NUMBER"]?></span>
				<span class="tel-bl-phone-delete" onclick="BX.Voximplant.Blacklist.deleteNumber('<?=CUtil::JSEscape(POST_FORM_ACTION_URI)?>', '<?=CUtil::JSEscape($arItem["PHONE_NUMBER"])?>', this.parentNode);"></span>
			</div>
		<?endforeach?>
	<?endif?>
	</div>

	<div class="tel-set-item-alert" style="margin-top: 25px">
		<?=GetMessage('BLACKLIST_ABOUT_2', Array('#LINK#' => '<a href="'.CVoxImplantMain::GetPublicFolder().'detail.php?CODE=423">'.GetMessage('BLACKLIST_ABOUT_LINK').'</a>'))?>
	</div>
</div>

<script>
	BX.message({
		BLACKLIST_DELETE_ERROR: '<?=GetMessageJS('BLACKLIST_DELETE_ERROR')?>',
		BLACKLIST_DELETE_CONFIRM : '<?=GetMessageJS("BLACKLIST_DELETE_CONFIRM")?>'
	});
</script>
