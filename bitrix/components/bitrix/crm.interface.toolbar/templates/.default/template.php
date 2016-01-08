<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)die();
global $APPLICATION;
$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');
$toolbarID =  $arParams['TOOLBAR_ID'];

?><div class="crm-list-top-bar" id="<?=htmlspecialcharsbx($toolbarID)?>"><?

$moreItems = array();
$enableMoreButton = false;
$labelText = '';
foreach($arParams["BUTTONS"] as $item):
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
	$alignment = isset($item['ALIGNMENT']) ? strtolower($item['ALIGNMENT']) : '';

	$iconClassName = 'crm-menu-bar-btn';
	if(isset($item['HIGHLIGHT']) && $item['HIGHLIGHT'])
	{
		if($iconClassName !== '')
		{
			$iconClassName = 'crm-menu-bar-btn crm-menu-bar-btn-green';
		}
		else
		{
			$iconClassName = 'crm-menu-bar-btn crm-menu-bar-btn-green';
		}
	}

	if(isset($item['ICON']))
	{
		$iconClassName .= ' '.$item['ICON'];
	}

	if($alignment !== '')
	{
		?><span class="crm-toolbar-alignment-<?=htmlspecialcharsbx($alignment)?>"><?
	}
	$onclick = isset($item['ONCLICK']) ? $item['ONCLICK'] : '';
	?><a class="<?=$iconClassName !== '' ? htmlspecialcharsbx($iconClassName) : ''?>" href="<?=htmlspecialcharsbx($link)?>" title="<?=htmlspecialcharsbx($title)?>" <?=$onclick !== '' ? ' onclick="'.htmlspecialcharsbx($onclick).'; return false;"' : ''?>><span class="crm-toolbar-btn-icon"></span><span><?=htmlspecialcharsbx($text)?></span></a><?
	if($alignment !== '')
	{
		?></span><?
	}
endforeach;

if(!empty($moreItems)):
	?><span class="crm-toolbar-alignment-right">
		<span class="crm-setting-btn"></span>
	</span>
	<script type="text/javascript">
		BX.ready(
			function()
			{
				BX.InterfaceToolBar.create(
					"<?=CUtil::JSEscape($toolbarID)?>",
					BX.CrmParamBag.create(
						{
							"containerId": "<?=CUtil::JSEscape($toolbarID)?>",
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
?><div class="crm-toolbar-label1"><span id="<?= $toolbarID.'_label' ?>"><?=$labelText?></span></div><?
endif;
?></div>

