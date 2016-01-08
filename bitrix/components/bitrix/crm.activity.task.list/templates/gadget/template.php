<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');

if (empty($arResult['TASK']))
	echo GetMessage('CRM_TASK_EMPTY');
else
{
	foreach($arResult['TASK'] as $arTask)
	{
		?>
		<div class="crm-task-element">
			<?if($arResult['ACTIVITY_ENTITY_LINK'] == 'Y'):?>
			<div class="crm-task-element-title"><span><?=GetMessage('CRM_ENTITY_'.$arTask['ENTITY_TYPE'])?></span> <a href="<?=$arTask['ENTITY_LINK']?>" id="balloon_<?=$arResult['GRID_ID']?>_T_<?=$arTask['ID']?>"><?=$arTask['ENTITY_TITLE']?></a></div>
			<?endif;?>
			<div class="crm-task-element-type"><a href="<?=$arTask['PATH_TO_TASK_SHOW']?>"><?=$arTask['TITLE']?></a></div>
			<div class="crm-task-element-name">
				<div class="crm-task-element-name-date"><?=FormatDate('x', MakeTimeStamp($arTask['CREATED_DATE']))?></div>
			</div>
		</div>
		<?
	}
	?><script type="text/javascript"><?
		foreach($arResult['TASK'] as $arEvent):
			if($arResult['ACTIVITY_ENTITY_LINK'] == 'Y' && !empty($arTask['ENTITY_ID'])):
			?>BX.tooltip('<?=$arTask['ENTITY_TYPE']?>_<?=$arTask['ENTITY_ID']?>', "balloon_<?=$arResult['GRID_ID']?>_T_<?=$arTask['ID']?>", "/bitrix/components/bitrix/crm.<?=strtolower($arTask['ENTITY_TYPE'])?>.show/card.ajax.php", "crm_balloon<?=($arTask['ENTITY_TYPE'] == 'LEAD' || $arTask['ENTITY_TYPE'] == 'DEAL' || $arTask['ENTITY_TYPE'] == 'QUOTE' ? '_no_photo': '_'.strtolower($arTask['ENTITY_TYPE']))?>", true);<?
			endif;
		endforeach;
	?></script><?
}
?>