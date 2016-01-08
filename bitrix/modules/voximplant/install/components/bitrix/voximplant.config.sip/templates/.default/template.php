<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$APPLICATION->SetAdditionalCSS("/bitrix/components/bitrix/voximplant.main/templates/.default/telephony.css");

CJSCore::RegisterExt('voximplant_config_sip', array(
	'js' => '/bitrix/components/bitrix/voximplant.config.sip/templates/.default/template.js',
	'lang' => '/bitrix/components/bitrix/voximplant.config.sip/templates/.default/lang/'.LANGUAGE_ID.'/template.php',
));
CJSCore::Init(array('voximplant_config_sip'));
?>
<div class="tel-set-text-block">
<?if (!$arResult['SIP_ENABLE']):?>
	<?=GetMessage('VI_CONFIG_SIP_INFO');?><br><br>
	<?if (!empty($arResult['LINK_TO_BUY'])):?>
		<?=GetMessage('VI_CONFIG_SIP_CONNECT_INFO_NEW');?><br>
		<?=GetMessage('VI_CONFIG_SIP_CONNECT_INFO_2_NEW');?><br><br>
		<div><b><?=GetMessage('VI_CONFIG_SIP_CONNECT_NOTICE_2');?></b></div>
		<div class="tel-set-inp-add-new" style="padding-left: 6px; padding-top: 4px;">
			<span class="webform-button webform-button-create" onclick="BX.VoxImplant.sip.connectModule('<?=$arResult['LINK_TO_BUY']?>')" ><span class="webform-button-left"></span><span class="webform-button-text"><?=GetMessage('VI_CONFIG_SIP_ACCEPT_3')?></span><span class="webform-button-right"></span></span>
		</div>
		<br>
	<?else:?>
		<div><?=GetMessage('VI_CONFIG_SIP_CONNECT_DISABLE');?></div><br>
	<?endif;?>
<?else:?>
	<?=GetMessage('VI_CONFIG_SIP_FIRST_STEP');?><br><br>
<?endif;?>
	<div class="tel-set-item-block tel-set-item-icon">
		<?=GetMessage('VI_CONFIG_SIP_CONNECT_DESC_NEW');?><br><br>
		<?=GetMessage('VI_CONFIG_SIP_CONFIG_INFO', Array('#LINK_START#' => '<a href="'.$arResult['LINK_TO_DOC'].'" target="_blank">', '#LINK_END#' => '</a>'));?>
	</div>
</div>
<?if (!empty($arResult['LIST_SIP_NUMBERS'])):?>
	<div id="phone-confing-sip-wrap">
		<div class="tel-set-text-block" id="phone-confing-title">
			<strong><?=GetMessage('VI_CONFIG_SIP_PHONES')?></strong>
		</div>
	<?foreach ($arResult['LIST_SIP_NUMBERS'] as $id => $config):?>
		<div class="tel-set-num-block tel-set-num-sip-block" id="phone-confing-<?=$id?>">
			<span class="tel-set-inp tel-set-inp-ready-to-use"><?=$config['PHONE_NAME']?></span>
			<a class="webform-button" href="<?=CVoxImplantMain::GetPublicFolder()?>edit.php?ID=<?=$id?>"><span class="webform-button-left"></span><span class="webform-button-text"><?=GetMessage('VI_CONFIG_SIP_CONFIGURE_2')?></span><span class="webform-button-right"></span></a>
			&nbsp;
			<span id="phone-confing-unlink-<?=$id?>" class="webform-button-ajax" onclick="BX.VoxImplant.sip.unlinkPhone(<?=$id?>)"><?=GetMessage('VI_CONFIG_SIP_DELETE_2')?></span>
		</div>
	<?endforeach;?>
	</div>
<?endif;?>
<div class="tel-set-inp-add-new" style="padding-left: 6px">
	<a class="webform-button webform-button-create"  href="#cloudPBX" id="vi_sip_cloud_options"><span class="webform-button-left"></span><span class="webform-button-text"><?=GetMessage('VI_CONFIG_SIP_CONNECT_CLOUD')?></span><span class="webform-button-right"></span></a>
	<a class="webform-button webform-button-create"  href="#officePBX" id="vi_sip_office_options"><span class="webform-button-left"></span><span class="webform-button-text"><?=GetMessage('VI_CONFIG_SIP_CONNECT_OFFICE')?></span><span class="webform-button-right"></span></a>
</div>
<div class="tel-set-main-wrap tel-set-main-wrap-white tel-connect-pbx" id="vi_sip_cloud_options_div" style="display: none; margin-top: 15px;">
	<div class="tel-set-inner-wrap">
		<table class="tel-set-sip-table">
			<tr>
				<td class="tel-set-sip-td-l"><?=GetMessage('VI_CONFIG_SIP_OUT_NC')?></td>
				<td class="tel-set-sip-td-r">
					<div class="tel-set-sip-inp-wrap">
						<input class="tel-set-inp" type="text" id="vi_sip_cloud_title"/>
						<br/>
						<span class="tel-set-sip-description"><?=GetMessage('VI_CONFIG_SIP_OUT_NC_DESC')?></span>
					</div>
				</td>
			</tr>
			<tr>
				<td class="tel-set-sip-td-l"><?=GetMessage('VI_CONFIG_SIP_OUT_SERVER')?></td>
				<td class="tel-set-sip-td-r">
					<div class="tel-set-sip-inp-wrap">
						<input class="tel-set-inp" type="text" id="vi_sip_cloud_server"/>
						<br/>
						<span class="tel-set-sip-description"><?=GetMessage('VI_CONFIG_SIP_OUT_SERVER_DESC_2')?></span>
					</div>
				</td>
			</tr>
			<tr>
				<td class="tel-set-sip-td-l"><?=GetMessage('VI_CONFIG_SIP_OUT_LOGIN')?></td>
				<td class="tel-set-sip-td-r">
					<div class="tel-set-sip-inp-wrap">
						<input class="tel-set-inp" type="text" id="vi_sip_cloud_login"/>
						<br/>
						<span class="tel-set-sip-description"><?=GetMessage('VI_CONFIG_SIP_OUT_LOGIN_DESC_2')?></span>
					</div>
				</td>
			</tr>
			<tr>
				<td class="tel-set-sip-td-l"><?=GetMessage('VI_CONFIG_SIP_OUT_PASSWORD')?></td>
				<td class="tel-set-sip-td-r"><input class="tel-set-inp" type="text" id="vi_sip_cloud_password"/></td>
			</tr>
			<tr>
				<td class="tel-set-sip-td-l"></td>
				<td class="tel-set-sip-td-r">
					<div class="webform-button webform-button-create" id="vi_sip_cloud_add"><span class="webform-button-left"></span><span class="webform-button-text"><?=GetMessage('VI_CONFIG_SIP_CONNECT_FIRST')?></span><span class="webform-button-right"></span></div>
				</td>
			</tr>
		</table>
	</div>
</div>
<div class="tel-set-main-wrap tel-set-main-wrap-white" id="vi_sip_office_options_div" style="display: none; margin-top: 15px;">
	<div class="tel-set-inner-wrap">
		<table class="tel-set-sip-table">
			<tr>
				<td class="tel-set-sip-td-l"><?=GetMessage('VI_CONFIG_SIP_OUT_NC')?></td>
				<td class="tel-set-sip-td-r">
					<div class="tel-set-sip-inp-wrap">
						<input class="tel-set-inp" type="text" id="vi_sip_office_title"/>
						<br/>
						<span class="tel-set-sip-description"><?=GetMessage('VI_CONFIG_SIP_OUT_NC_DESC')?></span>
					</div>
				</td>
			</tr>
			<tr>
				<td class="tel-set-sip-td-l"><?=GetMessage('VI_CONFIG_SIP_OUT_SERVER')?></td>
				<td class="tel-set-sip-td-r">
					<div class="tel-set-sip-inp-wrap">
						<input class="tel-set-inp" type="text" id="vi_sip_office_server"/>
						<br/>
						<span class="tel-set-sip-description"><?=GetMessage('VI_CONFIG_SIP_OUT_SERVER_DESC')?></span>
					</div>
				</td>
			</tr>
			<tr>
				<td class="tel-set-sip-td-l"><?=GetMessage('VI_CONFIG_SIP_OUT_LOGIN')?></td>
				<td class="tel-set-sip-td-r">
					<div class="tel-set-sip-inp-wrap">
						<input class="tel-set-inp" type="text" id="vi_sip_office_login"/>
						<br/>
						<span class="tel-set-sip-description"><?=GetMessage('VI_CONFIG_SIP_OUT_LOGIN_DESC')?></span>
					</div>
				</td>
			</tr>
			<tr>
				<td class="tel-set-sip-td-l"><?=GetMessage('VI_CONFIG_SIP_OUT_PASSWORD')?></td>
				<td class="tel-set-sip-td-r"><input class="tel-set-inp" type="text" id="vi_sip_office_password"/></td>
			</tr>
			<tr>
				<td class="tel-set-sip-td-l"></td>
				<td class="tel-set-sip-td-r">
					<div class="webform-button webform-button-create" id="vi_sip_office_add"><span class="webform-button-left"></span><span class="webform-button-text"><?=GetMessage('VI_CONFIG_SIP_CONNECT_FIRST')?></span><span class="webform-button-right"></span></div>
				</td>
			</tr>
		</table>
	</div>
</div>

<script type="text/javascript">
	BX.VoxImplant.sip.init({
		'publicFolder': '<?=CVoxImplantMain::GetPublicFolder()?>'
	})
</script>