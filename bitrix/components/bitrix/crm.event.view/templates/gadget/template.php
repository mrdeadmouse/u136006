<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (empty($arResult['EVENT']))
	echo GetMessage('CRM_EVENT_EMPTY');
else
{
	$APPLICATION->IncludeComponent('bitrix:main.user.link',
		'',
		array(
			'AJAX_ONLY' => 'Y',
		),
		false,
		array('HIDE_ICONS' => 'Y')
	);
	foreach($arResult['EVENT'] as $arEvent)
	{
		?>
		<div class="crm-event-element">
			<?if($arResult['EVENT_ENTITY_LINK'] == 'Y'):?>
			<div class="crm-event-element-title"><span><?=GetMessage('CRM_EVENT_ENTITY_'.$arEvent['ENTITY_TYPE'])?></span> <a href="<?=$arEvent['ENTITY_LINK']?>" id="balloon_<?=$arResult['GRID_ID']?>_I_<?=$arEvent['ID']?>"><?=$arEvent['ENTITY_TITLE']?></a></div>
			<?endif;?>
			<div class="crm-event-element-type"><?=$arEvent['EVENT_NAME']?></div>
			<div class="crm-event-element-name">
				<div class="crm-event-element-name-date"><?=FormatDate('x', MakeTimeStamp($arEvent['DATE_CREATE']), (time() + CTimeZone::GetOffset()))?></div>
				<div class="crm-event-element-name-author"><a href="<?=$arEvent['CREATED_BY_LINK']?>" id="balloon_<?=$arResult['GRID_ID']?>_<?=$arEvent['ID']?>"><?=$arEvent['CREATED_BY_FULL_NAME']?></a></div>
			</div>
		</div>
		<?
	}
	?><script type="text/javascript"><?
		foreach($arResult['EVENT'] as $arEvent):
			if ($arEvent['CREATED_BY_ID'] > 0):
			?>BX.tooltip(<?=$arEvent['CREATED_BY_ID']?>, "balloon_<?=$arResult['GRID_ID']?>_<?=$arEvent['ID']?>", "");<?
			endif;
			if($arResult['EVENT_ENTITY_LINK'] == 'Y'):
			?>BX.tooltip('<?=$arEvent['ENTITY_TYPE']?>_<?=$arEvent['ENTITY_ID']?>', "balloon_<?=$arResult['GRID_ID']?>_I_<?=$arEvent['ID']?>", "/bitrix/components/bitrix/crm.<?=strtolower($arEvent['ENTITY_TYPE'])?>.show/card.ajax.php", "crm_balloon<?=($arEvent['ENTITY_TYPE'] == 'LEAD' || $arEvent['ENTITY_TYPE'] == 'DEAL' || $arEvent['ENTITY_TYPE'] == 'QUOTE' ? '_no_photo': '_'.strtolower($arEvent['ENTITY_TYPE']))?>", true);<?
			endif;
		endforeach;
	?></script><?
}
?>



