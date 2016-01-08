<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$APPLICATION->SetAdditionalCSS("/bitrix/components/bitrix/voximplant.main/templates/.default/telephony.css");

CJSCore::RegisterExt('voximplant_config_link', array(
	'js' => '/bitrix/components/bitrix/voximplant.config.link/templates/.default/template.js',
	'lang' => '/bitrix/components/bitrix/voximplant.config.link/templates/.default/lang/'.LANGUAGE_ID.'/template.php',
));
CJSCore::Init(array('voximplant_config_link'));
?>

<div id="backphone-placeholder"></div>
<script type="text/javascript">
	BX.VoxImplant.backPhone.init({
		'placeholder': BX('backphone-placeholder'),
		'number': "<?=$arResult['PHONE_NUMBER']?>",
		'verified': "<?=$arResult['VERIFIED']?>",
		'verifiedUntil': "<?=$arResult['VERIFIED_UNTIL']?>"
	})
</script>
<br>
<a class="webform-button webform-button-create webform-button-for-link" href="#options" id="vi_link_options"><span class="webform-button-left"></span><span class="webform-button-text"><?=GetMessage("TELEPHONY_NUMBER_CONFIG")?></span><span class="webform-button-right"></span></a>
<div id="vi_link_options_div" style="display: none; padding-top: 15px">
	<form method="POST" id="vi_link_form2">
		<input type="hidden" name="vi_link_test" value="true" />
	</form>
	<form method="POST" id="vi_link_form">
	<input type="hidden" name="MODE" value="LINK" />
	<input type="hidden" name="vi_link_form" value="true" />
	<?=bitrix_sessid_post()?>
	<div class="tel-set-main-wrap tel-set-main-wrap-white">
		<div class="tel-set-inner-wrap">
			<div class="tel-set-cont-title-link tel-set-cont-title"><?=GetMessage("TELEPHONY_CONFIG")?></div>
			<div class="tel-set-item">
				<div class="tel-set-item-num">
					<input type="checkbox" id="vi_link_check_crm" name="vi_link_check_crm" <?=($arResult['LINK_CHECK_CRM']? 'checked': '')?> class="tel-set-checkbox"/>
					<span class="tel-set-item-num-text">1.</span>
				</div>
				<div class="tel-set-item-cont-block">
					<label for="vi_link_check_crm" class="tel-set-cont-item-title">
						<?=GetMessage("TELEPHONY_CONFIG_CHECK_CRM")?>
					</label>
				</div>
			</div>
			<div class="tel-set-item">
				<div class="tel-set-item-num">
					<input type="checkbox" id="vi_link_call_record" name="vi_link_call_record" <?=($arResult['LINK_CALL_RECORD']? 'checked': '')?> class="tel-set-checkbox"/>
					<span class="tel-set-item-num-text">2.</span>
				</div>
				<div class="tel-set-item-cont-block">
					<label for="vi_link_call_record" class="tel-set-cont-item-title">
						<?=GetMessage("TELEPHONY_CONFIG_RECORD")?>
						<span class="tel-set-cont-item-title-description"><?=GetMessage("TELEPHONY_CONFIG_RECORD_TITLE")?></span>
					</label>
					<div class="tel-set-item-cont">
						<div class="tel-set-item-alert">
							<?=GetMessage("TELEPHONY_CONFIG_RECORD_WARN")?>
						</div>
					</div>
				</div>
			</div>
			<a class="webform-button webform-button-create" href="#save-options" onclick="BX('vi_link_form').submit(); return false;"><span class="webform-button-left"></span><span class="webform-button-text"><?=GetMessage("TELEPHONY_SAVE")?></span><span class="webform-button-right"></span></a>
			<br>
			<br>
		</div>
	</div>
	</form>
</div>