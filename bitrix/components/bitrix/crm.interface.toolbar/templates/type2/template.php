<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)die();
global $APPLICATION;
$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');
$toolbarID =  $arParams['TOOLBAR_ID'];
$prefix =  $toolbarID.'_';
?><div class="bx-crm-view-menu" id="<?=htmlspecialcharsbx($toolbarID)?>"><?

$moreItems = array();
$enableMoreButton = false;
$labelText = '';
foreach($arParams['BUTTONS'] as $k => $item):
	if ($item['LABEL'] === true)
	{
		$labelText = isset($item['TEXT']) ? $item['TEXT'] : '';
		continue;
	}
	if(!$enableMoreButton && isset($item['NEWBAR']) && $item['NEWBAR'] === true):
		$enableMoreButton = true;
		continue;
	endif;

	if($enableMoreButton):
		$moreItems[] = $item;
		continue;
	endif;

	$link = isset($item['LINK']) ? $item['LINK'] : '#';
	$text = isset($item['TEXT']) ? $item['TEXT'] : '';
	$title = isset($item['TITLE']) ? $item['TITLE'] : '';
	$type = isset($item['TYPE']) ? $item['TYPE'] : 'context';
	$code = isset($item['CODE']) ? $item['CODE'] : '';
	$visible = isset($item['VISIBLE']) ? (bool)$item['VISIBLE'] : true;
	$target = isset($item['TARGET']) ? $item['TARGET'] : '';

	$iconBtnClassName = '';
	if (isset($item['ICON']))
	{
		$iconBtnClassName = 'crm-'.$item['ICON'];
	}

	$onclick = isset($item['ONCLICK']) ? $item['ONCLICK'] : '';
	if ($type == 'toolbar-split-left')
	{
		$item_tmp = reset($item['LINKS']);
		?><span class="crm-toolbar-btn-split crm-toolbar-btn-left <?=$iconBtnClassName; ?>"
			<? if ($code !== '') { ?> id="<?=htmlspecialcharsbx("{$prefix}{$code}"); ?>"<? } ?>
			<? if (!$visible) { ?> style="display: none;"<? } ?>>
			<span class="crm-toolbar-btn-split-l"
				title="<?=(isset($item_tmp['TITLE']) ? htmlspecialcharsbx($item_tmp['TITLE']) : ''); ?>"
				<? if (isset($item_tmp['ONCLICK'])) { ?> onclick="<?=htmlspecialcharsbx($item_tmp['ONCLICK']); ?>; return false;"<? } ?>>
				<span class="crm-toolbar-btn-split-bg"><span class="crm-toolbar-btn-icon"></span><?
					echo (isset($item_tmp['TEXT']) ? htmlspecialcharsbx($item_tmp['TEXT']) : '');
				?></span>
			</span><span class="crm-toolbar-btn-split-r" onclick="btnMenu_<?=$k; ?>.ShowMenu(this);">
			<span class="crm-toolbar-btn-split-bg"></span></span>
		</span>
		<script>
			var btnMenu_<?=$k; ?> = new PopupMenu('bxBtnMenu_<?=$k; ?>', 1010);
			btnMenu_<?=$k; ?>.SetItems([
				<? foreach ($item['LINKS'] as $v) { ?>
				{
					'DEFAULT': <?=(isset($v['DEFAULT']) && $v['DEFAULT'] ? 'true' : 'false'); ?>,
					'DISABLED': <?=(isset($v['DISABLED']) && $v['DISABLED'] ? 'true' : 'false'); ?>,
					'ICONCLASS': "<?=(isset($v['ICONCLASS']) ? htmlspecialcharsbx($v['ICONCLASS']) : ''); ?>",
					'ONCLICK': "<?=(isset($v['ONCLICK']) ? $v['ONCLICK'] : ''); ?>; return false;",
					'TEXT': "<?=(isset($v['TEXT']) ? htmlspecialcharsbx($v['TEXT']) : ''); ?>",
					'TITLE': "<?=(isset($v['TITLE']) ? htmlspecialcharsbx($v['TITLE']) : ''); ?>"
				},
				<? } ?>
			]);
		</script><?
	}
	else if ($type == 'toolbar-left')
	{
		?><a class="crm-toolbar-btn crm-toolbar-btn-left <?=$iconBtnClassName; ?>"
			<? if ($code !== '') { ?> id="<?=htmlspecialcharsbx("{$prefix}{$code}"); ?>"<? } ?>
			href="<?=htmlspecialcharsbx($link)?>"
			<? if($target !== '') { ?> target="<?=$target?>"<? } ?>
			title="<?=htmlspecialcharsbx($title)?>"
			<? if ($onclick !== '') { ?> onclick="<?=htmlspecialcharsbx($onclick); ?>; return false;"<? } ?>
			<? if (!$visible) { ?> style="display: none;"<? } ?>>
			<span class="crm-toolbar-btn-icon"></span><span><?=htmlspecialcharsbx($text); ?></span></a><?
	}
	else
	{
		?><a class="bx-context-button <?=$iconBtnClassName; ?>"
			<? if ($code !== '') { ?> id="<?=htmlspecialcharsbx("{$prefix}{$code}"); ?>"<? } ?>
			href="<?=htmlspecialcharsbx($link)?>"
			<? if($target !== '') { ?> target="<?=$target?>"<? } ?>
			title="<?=htmlspecialcharsbx($title)?>"
			<? if ($onclick !== '') { ?> onclick="<?=htmlspecialcharsbx($onclick); ?>; return false;"<? } ?>
			<? if (!$visible) { ?> style="display: none;"<? } ?>>
			<span class="bx-context-button-icon"></span><span><?=htmlspecialcharsbx($text); ?></span></a><?
	}

endforeach;
if(!empty($moreItems)):
	?><a class="bx-context-button crm-btn-more" href="#">
		<span class="bx-context-button-icon"></span>
		<span><?=htmlspecialcharsbx(GetMessage('CRM_INTERFACE_TOOLBAR_BTN_MORE'))?></span>
	</a>
	<script type="text/javascript">
		BX.ready(
			function()
			{
				BX.InterfaceToolBar.create(
					"<?=CUtil::JSEscape($toolbarID)?>",
					BX.CrmParamBag.create(
						{
							"containerId": "<?=CUtil::JSEscape($toolbarID)?>",
							"prefix": "<?=CUtil::JSEscape($prefix)?>",
							"moreButtonClassName": "crm-btn-more",
							"items": <?=CUtil::PhpToJSObject($moreItems)?>
						}
					)
				);
			}
		);
	</script>
<?
endif;
if ($labelText != ''):
?><div class="crm-toolbar-label2"><span id="<?= $toolbarID.'_label' ?>"><?=htmlspecialcharsbx($labelText)?></span></div><?
endif;
?></div>
