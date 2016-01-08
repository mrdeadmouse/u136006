<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$GLOBALS["LEFT_MENU_COUNTERS"] = is_array($arResult["COUNTERS"]) ? $arResult["COUNTERS"] : Array();

?><span class="header-informers-wrap" id="im-container">
	<span id="im-informer-messages" title="<?=GetMessage('IM_MESSENGER_OPEN_MESSENGER_CP');?>" class="header-informers header-informer-messages" onclick="B24.showMessagePopup(this)"></span><span onclick="B24.showNotifyPopup(this)" title="<?=GetMessage("IM_MESSENGER_OPEN_NOTIFY");?>" id="im-informer-events" class="header-informers header-informer-events"></span>
	<?if (COption::GetOptionString('bitrix24', 'network', 'N') == 'Y'):
		$networkLink = "https://www.bitrix24.net/?user_lang=".LANGUAGE_ID."&utm_source=b24&utm_medium=itop&utm_campaign=BITRIX24%2FITOP";
	?>
		<span id="b24network-informer-events" class="header-informers header-informer-network" onclick="window.open('<?=$networkLink?>','_blank');"></span>
	<?endif?>
</span>
<?$this->SetViewTarget("im")?>
<div id="bx-notifier-panel" class="bx-notifier-panel">
	<span class="bx-notifier-panel-left"></span><span class="bx-notifier-panel-center"><span class="bx-notifier-drag">
	</span><span class="bx-notifier-indicators"><a href="javascript:void(0)" class="bx-notifier-indicator bx-notifier-call" title="<?=GetMessage('IM_MESSENGER_OPEN_CALL2');?>"><span class="bx-notifier-indicator-text"></span><span class="bx-notifier-indicator-icon"></span><span class="bx-notifier-indicator-count">0</span>
		</a><a href="javascript:void(0)" class="bx-notifier-indicator bx-notifier-message" title="<?=GetMessage('IM_MESSENGER_OPEN_MESSENGER_CP');?>"><span class="bx-notifier-indicator-text"></span><span class="bx-notifier-indicator-icon"></span><span class="bx-notifier-indicator-count">0</span>
		</a><a href="javascript:void(0)" class="bx-notifier-indicator bx-notifier-notify" title="<?=GetMessage('IM_MESSENGER_OPEN_NOTIFY');?>"><span class="bx-notifier-indicator-text"></span><span class="bx-notifier-indicator-icon"></span><span class="bx-notifier-indicator-count">0</span>
		</a><a class="bx-notifier-indicator bx-notifier-mail" href="#mail" title="<?=GetMessage('IM_MESSENGER_OPEN_EMAIL');?>"><span class="bx-notifier-indicator-icon"></span><span class="bx-notifier-indicator-count">0</span>
		</a><a class="bx-notifier-indicator bx-notifier-network" href="#network" title="<?=GetMessage('IM_MESSENGER_OPEN_NETWORK');?>"><span class="bx-notifier-indicator-icon"></span><span class="bx-notifier-indicator-count">0</span>
		</a></span>
	</span><span class="bx-notifier-panel-right"></span>
</div>
<?$this->EndViewTarget()?>

<?$frame = $this->createFrame("im")->begin("");?>
<script type="text/javascript"><?=CIMMessenger::GetTemplateJS(Array(), $arResult)?></script>
<?$frame->end()?>