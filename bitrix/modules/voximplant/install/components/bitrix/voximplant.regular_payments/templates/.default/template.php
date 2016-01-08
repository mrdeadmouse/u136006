<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$APPLICATION->SetAdditionalCSS("/bitrix/components/bitrix/voximplant.main/templates/.default/telephony.css");
?>
<div class="tel-history-block tel-regular-block">
	<div class="tel-history-title"><?=GetMessage('VI_REGULAR_TITLE')?></div>

	<?if($arResult['PAID_BEFORE']['NOTICE']):?>
		<div class="tel-phones-list-notice">
			<?=GetMessage('VI_REGULAR_NO_MONEY', Array(
				'#MONEY#' => '<b>'.GetMessage('VI_REGULAR_FEE_'.$arResult['BALANCE_CURRENCY'], Array('#MONEY#' => $arResult['PAID_BEFORE']['PRICE'])).'</b>',
				'#DATE#' => '<b>'.$arResult['PAID_BEFORE']['DATE'].'</b>')
			)?>
		</div>
	<?endif;?>

	<table cellspacing="0" cellpadding="0" class="tel-phones-list">
	<tr>
		<td class="tel-phones-list-th tel-phones-list-th-first"><?=GetMessage('VI_REGULAR_TABLE_NUMBER')?></td>
		<td class="tel-phones-list-th"><?=GetMessage('VI_REGULAR_TABLE_PAID_BEFORE')?></td>
		<td class="tel-phones-list-th tel-phones-list-th-last"><?=GetMessage('VI_REGULAR_TABLE_FEE')?></td>
	</tr>
	<?foreach($arResult['NUMBERS'] as $value):?>
		<tr>
			<td class="tel-phones-list-td tel-phones-list-td-first"><span class="tel-phones-list-icon tel-phones-list-icon-<?=($value['ACTIVE'] == 'Y'? 'active': 'deactive')?>" title="<?=GetMessage('VI_REGULAR_TABLE_STATUS_'.$value['ACTIVE'])?>"></span><b><?=$value['NUMBER']?></b></td>
			<td class="tel-phones-list-td"><?=$value['PAID_BEFORE']?></td>
			<td class="tel-phones-list-td tel-phones-list-td-last"><?=GetMessage('VI_REGULAR_FEE_'.$arResult['BALANCE_CURRENCY'], Array('#MONEY#' => $value['PRICE']))?></td>
		</tr>
	<?endforeach;?>
	<tr>
		<td class="tel-phones-list-td-footer tel-phones-list-td-footer-first"></td>
		<td class="tel-phones-list-td-footer"></td>
		<td class="tel-phones-list-td-footer tel-phones-list-td-footer-last"></td>
	</tr>
	</table>

	<div class="tel-history-notice"><?=GetMessage('VI_REGULAR_NOTICE')?></div>
	<div class="tel-history-more">
		<a href="<?=CVoxImplantMain::GetPublicFolder()?>lines.php?MODE=RENT" class="tel-history-more-link"><?=GetMessage('VI_REGULAR_CONFIG_RENT')?></a>
	</div>
</div>