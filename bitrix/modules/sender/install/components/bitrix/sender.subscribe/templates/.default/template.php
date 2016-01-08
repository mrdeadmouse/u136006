<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */

$buttonId = rand(1,1000);
?>
<div class="bx_subscribe_container"  id="sender-subscribe">
<?
$frame = $this->createFrame("sender-subscribe", false)->begin();
?>
	<?if(isset($arResult['MESSAGE'])): CJSCore::Init(array("popup"));?>
		<div id="sender-subscribe-response-cont" style="display: none;">
			<div class="bx_subscribe_response_container">
				<table>
					<tr>
						<td style="padding-right: 40px; padding-bottom: 0px;"><img src="<?=($this->GetFolder().'/images/'.($arResult['MESSAGE']['TYPE']=='ERROR' ? 'icon-alert.png' : 'icon-ok.png'))?>" alt=""></td>
						<td>
							<div style="font-size: 22px;"><?=GetMessage('subscr_form_response_'.$arResult['MESSAGE']['TYPE'])?></div>
							<div style="font-size: 16px;"><?=htmlspecialcharsbx($arResult['MESSAGE']['TEXT'])?></div>
						</td>
					</tr>
				</table>
			</div>
		</div>
		<script>
			BX.ready(function(){
				var oPopup = BX.PopupWindowManager.create('sender_subscribe_component', window.body, {
					autoHide: true,
					offsetTop: 1,
					offsetLeft: 0,
					lightShadow: true,
					closeIcon: true,
					closeByEsc: true,
					overlay: {
						backgroundColor: 'rgba(57,60,67,0.82)', opacity: '80'
					}
				});
				oPopup.setContent(BX('sender-subscribe-response-cont'));
				oPopup.show();
			});
		</script>
	<?endif;?>

	<form method="post" action="<?=$arResult["FORM_ACTION"]?>" onsubmit="BX('bx_subscribe_btn_<?=$buttonId?>').disabled=true;">
		<?=bitrix_sessid_post()?>
		<input type="hidden" name="sender_subscription" value="add">

		<div class="bx_subscribe_input_container">
			<input type="text" name="SENDER_SUBSCRIBE_EMAIL" value="<?=$arResult["EMAIL"]?>" title="<?=GetMessage("subscr_form_email_title")?>">
		</div>
		<?if(count($arResult["RUBRICS"])>0):?>
			<div class="bx_subscribe_title_desc"><?=GetMessage("subscr_form_title_desc")?></div>
		<?endif;?>
		<?foreach($arResult["RUBRICS"] as $itemID => $itemValue):?>
		<div class="bx_subscribe_checkbox_container">
			<input type="checkbox" name="SENDER_SUBSCRIBE_RUB_ID[]" id="SENDER_SUBSCRIBE_RUB_ID_<?=$itemValue["ID"]?>" value="<?=$itemValue["ID"]?>"<?if($itemValue["CHECKED"]) echo " checked"?>>
			<label for="SENDER_SUBSCRIBE_RUB_ID_<?=$itemValue["ID"]?>"><?=htmlspecialcharsbx($itemValue["NAME"])?></label>
		</div>
		<?endforeach;?>
		<div class="bx_subscribe_submit_container">
			<button id="bx_subscribe_btn_<?=$buttonId?>"><span><?=GetMessage("subscr_form_button")?></span><span class="icon"></span></button>
		</div>
	</form>

<?
$frame->beginStub();
?>
	<?if(isset($arResult['MESSAGE'])): CJSCore::Init(array("popup"));?>
		<div id="sender-subscribe-response-cont" style="display: none;">
			<div class="bx_subscribe_response_container">
				<table>
					<tr>
						<td style="padding-right: 40px; padding-bottom: 0px;"><img src="<?=($this->GetFolder().'/images/'.($arResult['MESSAGE']['TYPE']=='ERROR' ? 'icon-alert.png' : 'icon-ok.png'))?>" alt=""></td>
						<td>
							<div style="font-size: 22px;"><?=GetMessage('subscr_form_response_'.$arResult['MESSAGE']['TYPE'])?></div>
							<div style="font-size: 16px;"><?=htmlspecialcharsbx($arResult['MESSAGE']['TEXT'])?></div>
						</td>
					</tr>
				</table>
			</div>
		</div>
		<script>
			BX.ready(function(){
				var oPopup = BX.PopupWindowManager.create('sender_subscribe_component', window.body, {
					autoHide: true,
					offsetTop: 1,
					offsetLeft: 0,
					lightShadow: true,
					closeIcon: true,
					closeByEsc: true,
					overlay: {
						backgroundColor: 'rgba(57,60,67,0.82)', opacity: '80'
					}
				});
				oPopup.setContent(BX('sender-subscribe-response-cont'));
				oPopup.show();
			});
		</script>
	<?endif;?>

	<form method="post" action="<?=$arResult["FORM_ACTION"]?>" onsubmit="BX('bx_subscribe_btn_<?=$buttonId?>').disabled=true;">
		<?=bitrix_sessid_post()?>
		<input type="hidden" name="sender_subscription" value="add">

		<div class="bx_subscribe_input_container">
			<input type="text" name="SENDER_SUBSCRIBE_EMAIL" value="" title="<?=GetMessage("subscr_form_email_title")?>">
		</div>
		<?if(count($arResult["RUBRICS"])>0):?>
			<div class="bx_subscribe_title_desc"><?=GetMessage("subscr_form_title_desc")?></div>
		<?endif;?>
		<?foreach($arResult["RUBRICS"] as $itemID => $itemValue):?>
			<div class="bx_subscribe_checkbox_container">
				<input type="checkbox" name="SENDER_SUBSCRIBE_RUB_ID[]" id="SENDER_SUBSCRIBE_RUB_ID_<?=$itemValue["ID"]?>" value="<?=$itemValue["ID"]?>">
				<label for="SENDER_SUBSCRIBE_RUB_ID_<?=$itemValue["ID"]?>"><?=htmlspecialcharsbx($itemValue["NAME"])?></label>
			</div>
		<?endforeach;?>
		<div class="bx_subscribe_submit_container">
			<button id="bx_subscribe_btn_<?=$buttonId?>"><span><?=GetMessage("subscr_form_button")?></span><span class="icon"></span></button>
		</div>
	</form>
<?
$frame->end();
?>
</div>