<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

CJSCore::Init("ajax", "fx");

$this->setFrameMode(true);
$this->SetViewTarget("sidebar", 50);
?>

<script type="text/javascript">BX.Intranet.Banner24.init({ url : "<?=CUtil::JSEscape($templateFolder."/ajax.php")?>"})</script>

<?if (in_array("sip", $arResult["BANNERS"])):?>
	<div class="sidebar-banner sidebar-banner-sip" id="sidebar-banner-sip">
		<div class="sidebar-banner-content">
			<div class="sidebar-banner-images"></div>
			<div class="sidebar-banner-desc">
				<div class="sidebar-banner-hint">
					<span><?=GetMessage("B24_BANNER_SIP_TITLE")?></span><br/>
					<?=GetMessage("B24_BANNER_SIP_SUBTITLE")?>
				</div>
				<a href="/settings/telephony/#sipConnector" class="sidebar-banner-button"><?=GetMessage("B24_BANNER_BUTTON_MORE")?></a>
			</div>
			<div class="sidebar-banner-close" title="<?=GetMessage("B24_BANNER_CLOSE")?>" onclick="BX.Intranet.Banner24.close('sip')"></div>
		</div>
	</div>
<?endif?>

<?if (in_array("marketplace", $arResult["BANNERS"])):?>
	<div class="sidebar-banner sidebar-banner-marketplace" id="sidebar-banner-marketplace">
		<div class="sidebar-banner-content">
			<div class="sidebar-banner-images"></div>
			<div class="sidebar-banner-desc">
				<div class="sidebar-banner-hint"><b><?=GetMessage("B24_BANNER_MARKETPLACE_TITLE")?></b><br><?=GetMessage("B24_BANNER_MARKETPLACE_SUBTITLE")?></div>
				<a target="_blank" href="/marketplace/" class="sidebar-banner-button"><?=GetMessage("B24_BANNER_BUTTON_SHOW")?></a>
			</div>
			<div class="sidebar-banner-close" title="<?=GetMessage("B24_BANNER_CLOSE")?>" onclick="BX.Intranet.Banner24.close('marketplace')"></div>
		</div>
	</div>
<?endif?>

<?if (in_array("network", $arResult["BANNERS"])):
	$logo = in_array(LANGUAGE_ID, array("ru", "ua", "en")) ? LANGUAGE_ID : "en";
?>
	<div class="sidebar-banner sidebar-banner-network" id="sidebar-banner-network">
		<div class="sidebar-banner-content">
			<div class="sidebar-banner-title">
				<img class="sidebar-banner-title-img" src="<?=$this->GetFolder()?>/images/netw_ban_logo_<?=$logo?>.png?2" width="220" height="26" alt="" />
			</div>
			<div class="sidebar-banner-images"></div>
			<div class="sidebar-banner-desc">
				<div class="sidebar-banner-hint">
					<span class="sidebar-banner-hint-title"><?=GetMessage("B24_BANNER_NETWORK_HINT_TITLE")?></span>
					<?=GetMessage("B24_BANNER_NETWORK_HINT_SUBTITLE")?>
				</div>
				<a target="_blank" href="https://www.bitrix24.net/?utm_source=b24&utm_medium=btop&utm_campaign=BITRIX24%2FBTOP" class="sidebar-banner-button"><?=GetMessage("B24_BANNER_NETWORK_START")?></a>
			</div>
			<div class="sidebar-banner-close" title="<?=GetMessage("B24_BANNER_CLOSE")?>" onclick="BX.Intranet.Banner24.close('network')"></div>
		</div>
	</div>
<?endif?>

<?if (in_array("messenger", $arResult["BANNERS"])):

	$request = Bitrix\Main\Context::getCurrent()->getRequest();
	$downloadUrl = "http://dl.bitrix24.com/b24/bitrix24_desktop.exe";
	if (stripos($request->getUserAgent(), "Macintosh") !== false)
	{
		$downloadUrl = "http://dl.bitrix24.com/b24/bitrix24_desktop.dmg";
	}
	?>
	<div class="sidebar-banner sidebar-banner-messenger" id="sidebar-banner-messenger">
		<div class="sidebar-banner-content">
			<div class="sidebar-banner-images"></div>
			<div class="sidebar-banner-desc">
				<div class="sidebar-banner-hint"><b><?=GetMessage("B24_BANNER_MESSENGER_TITLE")?></b><br><?=GetMessage("B24_BANNER_MESSENGER_SUBTITLE")?></div>
				<a href="<?=$downloadUrl?>" class="sidebar-banner-button"><?=GetMessage("B24_BANNER_BUTTON_INSTALL")?></a>
			</div>
			<div class="sidebar-banner-close" title="<?=GetMessage("B24_BANNER_CLOSE")?>" onclick="BX.Intranet.Banner24.close('messenger')"></div>
		</div>
	</div>
<?endif?>

<?if (in_array("mobile", $arResult["BANNERS"])):?>
<div class="sidebar-banner sidebar-banner-mobile" id="sidebar-banner-mobile">
	<div class="sidebar-banner-content">
		<div class="sidebar-banner-images"></div>
		<div class="sidebar-banner-desc">
			<div class="sidebar-banner-hint"><b><?=GetMessage("B24_BANNER_MOBILE_TITLE")?></b><br><?=GetMessage("B24_BANNER_MOBILE_SUBTITLE")?></div>
			<a target="_blank" href="<?=($arResult["MODULE_NAME"] == "bitrix24") ? GetMessage("B24_BANNER_MOBILE_URL") : GetMessage("B24_BANNER_MOBILE_URL_INTRANET")?>" class="sidebar-banner-button"><?=GetMessage("B24_BANNER_BUTTON_INSTALL")?></a>
		</div>
		<div class="sidebar-banner-close" title="<?=GetMessage("B24_BANNER_CLOSE")?>" onclick="BX.Intranet.Banner24.close('mobile')"></div>
	</div>
</div>
<?endif?>

<?if (in_array("trial", $arResult["BANNERS"])):?>
<div class="sidebar-banner sidebar-banner-license" id="sidebar-banner-trial">
	<div class="sidebar-banner-content">
		<div class="sidebar-banner-images"></div>
		<div class="sidebar-banner-desc">
			<div class="sidebar-banner-hint"><b><?=GetMessage("B24_BANNER_TRIAL_TITLE")?></b><br><?=GetMessage("B24_BANNER_TRIAL_SUBTITLE")?></div>
			<a href="/settings/license.php" class="sidebar-banner-button"><?=GetMessage("B24_BANNER_BUTTON_TRY")?></a>
		</div>
		<div class="sidebar-banner-close" title="<?=GetMessage("B24_BANNER_CLOSE")?>" onclick="BX.Intranet.Banner24.close('trial')"></div>
	</div>
</div>
<?endif?>

<?if (in_array("trial-expired", $arResult["BANNERS"])):
$remainingDays = 30 - intval( (time() - intval(COption::GetOptionInt("bitrix24", "DEMO_START"))) / 3600 / 24 );
$bannerText = GetMessage(
	$remainingDays == 1 ? "B24_BANNER_TRIAL_EXPIRED_TITLE_1" : "B24_BANNER_TRIAL_EXPIRED_TITLE",
	array(
		"#NUM_DAYS#" => $remainingDays,
		"#DAYS#" => FormatDate("ddiff", time(), (31*24*60*60 + COption::GetOptionInt("bitrix24", "DEMO_START")))
	)
);
?>
<div class="sidebar-banner sidebar-banner-license" id="sidebar-banner-trial-expired">
	<div class="sidebar-banner-content">
		<div class="sidebar-banner-images"></div>
		<div class="sidebar-banner-desc">
			<div class="sidebar-banner-hint"><b><?=$bannerText?></b><br><?=GetMessage("B24_BANNER_TRIAL_EXPIRED_SUBTITLE")?></div>
			<a href="/settings/license.php#change-license" class="sidebar-banner-button"><?=GetMessage("B24_BANNER_BUTTON_BUY")?></a>
		</div>
		<div class="sidebar-banner-close" title="<?=GetMessage("B24_BANNER_CLOSE")?>" onclick="BX.Intranet.Banner24.close('trial-expired')"></div>
	</div>
</div>
<?endif?>

<?if (in_array("webinar", $arResult["BANNERS"])):?>
	<div class="sidebar-banner sidebar-banner-webinars" id="sidebar-banner-webinar">
		<div class="sidebar-banner-content">
			<div class="sidebar-banner-images"></div>
			<div class="sidebar-banner-desc">
				<div class="sidebar-banner-hint">
					<?=GetMessage("B24_BANNER_WEBINAR_TITLE")?>
					<span class="sidebar-banner-description"><?=GetMessage("B24_BANNER_WEBINAR_SUBTITLE")?></span>
				</div>
				<a target="_blank" href="<?=GetMessage("B24_BANNER_WEBINAR_URL")?>" class="sidebar-banner-button"><?=GetMessage("B24_BANNER_BUTTON_SEE")?></a>
			</div>
			<div class="sidebar-banner-close" title="<?=GetMessage("B24_BANNER_CLOSE")?>" onclick="BX.Intranet.Banner24.close('webinar')"></div>
		</div>
	</div>
<?endif?>

<?if (in_array("newyear", $arResult["BANNERS"])):?>
	<div class="sidebar-banner sidebar-banner-ny" id="sidebar-banner-newyear">
		<div class="sidebar-banner-content">
			<div class="sidebar-banner-images"></div>
			<div class="sidebar-banner-desc">
				<div class="sidebar-banner-hint">
					<?=GetMessage("B24_BANNER_NEWYEAR_TITLE")?> <br/>
					<span style="font-size: 16px;"><?=GetMessage("B24_BANNER_NEWYEAR_SUBTITLE")?></span>
					<span style="font-size: 32px; line-height: 32px;">2015!</span>
				</div>
				<a class="sidebar-banner-button" href="<?=GetMessage("B24_BANNER_NEWYEAR_LINK")?>" target="_blank"><?=GetMessage("B24_BANNER_NEWYEAR_BUTTON")?></a>
			</div>
			<div title="<?=GetMessage("B24_BANNER_CLOSE")?>" onclick="BX.Intranet.Banner24.close('newyear')" class="sidebar-banner-close"></div>
		</div>
	</div>
<?endif?>

<?if (in_array("runewyear", $arResult["BANNERS"])):?>
	<div class="sidebar-banner sidebar-banner-ny sidebar-banner-ru" id="sidebar-banner-runewyear">
		<div class="sidebar-banner-content">
			<div class="sidebar-banner-images"></div>
				<div class="sidebar-banner-desc">
					<div class="sidebar-banner-hint">
						<?=GetMessage("B24_BANNER_RUNEWYEAR_TEXT")?>
				</div>
			</div>
			<div title="<?=GetMessage("B24_BANNER_CLOSE")?>" onclick="BX.Intranet.Banner24.close('runewyear')" class="sidebar-banner-close"></div>
		</div>
	</div>
<?endif?>

<?if (in_array("mail", $arResult["BANNERS"])):?>
	<div class="sidebar-banner sidebar-banner-present" id="sidebar-banner-mail">
		<div class="sidebar-banner-content">
			<div class="sidebar-banner-images"></div>
			<div class="sidebar-banner-desc">
				<div class="sidebar-banner-hint">
					<?=GetMessage("B24_BANNER_MAIL_TITLE")?>
					<span class="sidebar-banner-description"><?=GetMessage("B24_BANNER_MAIL_SUBTITLE")?></span>
				</div>
				<a href="<?=SITE_DIR."company/personal/mail/?page=home"?>" class="sidebar-banner-button"><?=GetMessage("B24_BANNER_BUTTON_CONNECT")?></a>
			</div>
			<div class="sidebar-banner-close" title="<?=GetMessage("B24_BANNER_CLOSE")?>" onclick="BX.Intranet.Banner24.close('mail')"></div>
		</div>
	</div>
<?endif?>

<?if (in_array("prices", $arResult["BANNERS"])):?>
	<div class="sidebar-banner sidebar-banner-disk" id="sidebar-banner-prices">
		<div class="sidebar-banner-content">
			<div class="sidebar-banner-images"></div>
			<div class="sidebar-banner-desc">
				<div class="sidebar-banner-hint">
					<span><?=GetMessage("B24_BANNER_DISK_TITLE")?></span>
					<?=GetMessage("B24_BANNER_DISK_SUBTITLE")?>
				</div>
				<a target="_blank" href="<?=GetMessage("B24_BANNER_DISK_LINK")?>" class="sidebar-banner-button"><?=GetMessage("B24_BANNER_BUTTON_SHOW")?></a>
			</div>
			<div class="sidebar-banner-close" title="<?=GetMessage("B24_BANNER_CLOSE")?>" onclick="BX.Intranet.Banner24.close('prices')"></div>
		</div>
	</div>
<?endif?>

