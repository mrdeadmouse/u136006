<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
global $APPLICATION;
$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');
CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/common.js');
CJSCore::Init(array('popup', 'date'));

$ID = $arResult['ID'];
$IDLc = strtolower($ID);
$items = isset($arResult['ITEMS']) ? $arResult['ITEMS'] : array();
$activeItemID =  isset($arResult['ACTIVE_ITEM_ID']) ? $arResult['ACTIVE_ITEM_ID'] : '';
$containerID = "crm_ctrl_panel_{$IDLc}";
$wrapperID = "crm_ctrl_panel_wrap_{$IDLc}";
$itemWrapperID = "crm_ctrl_panel_items_{$IDLc}";
$itemContainerPrefix = "crm_ctrl_panel_item_{$IDLc}_";
$itemInfos = array();
$enableSearch = isset($arResult['ENABLE_SEARCH']) ? $arResult['ENABLE_SEARCH'] : true;
$searchContainerID = "crm_ctrl_panel_{$IDLc}_search";
$additionaItem = isset($arResult['ADDITIONAL_ITEM']) ? $arResult['ADDITIONAL_ITEM'] : null;
$additionalItemInfo = null;
$isFixed = isset($arResult['IS_FIXED']) ? $arResult['IS_FIXED'] : false;

$itemContainerIDs = array();
$additionalContainerID = '';
?>
<div id="<?=htmlspecialcharsbx($containerID)?>" class="crm-header">
<div id="<?=htmlspecialcharsbx($wrapperID)?>" class="crm-header-inner">
	<div id="<?=htmlspecialcharsbx($itemWrapperID)?>" class="crm-menu-wrap"><?
		foreach($items as &$item):
			$itemID = isset($item['ID']) ? $item['ID'] : '';
			$itemIDLc = strtolower($itemID);
			$isActive = $itemID === $activeItemID;
			$url = isset($item['URL']) ? $item['URL'] : '#';
			$icon = isset($item['ICON']) ? strtolower($item['ICON']) : '';
			$name = isset($item['NAME']) ? $item['NAME'] : $itemID;
			$briefName = isset($item['BRIEF_NAME']) ? $item['BRIEF_NAME'] : '';
			if($briefName === '')
				$briefName = $name;

			$title = isset($item['TITLE']) ? $item['TITLE'] : '';
			$counter = isset($item['COUNTER']) ? intval($item['COUNTER']) : 0;

			$itemInfo = array(
				'id' => $itemID,
				'name' => $name,
				'icon' => $icon,
				'isActive' => $isActive,
				'url' => $url,
				'actions' => array(),
				'childItems' => array()
			);

			$actions = isset($item['ACTIONS']) ? $item['ACTIONS'] : array();
			foreach($actions as &$action):
				$actionID = isset($action['ID']) ? $action['ID'] : '';
				if($actionID === '')
					continue;

				$itemInfo['actions'][] = array(
					'id' => $actionID,
					'url' => isset($action['URL']) ? $action['URL'] : '',
					'script' => isset($action['SCRIPT']) ? $action['SCRIPT'] : ''
				);
			endforeach;
			unset($action);

			$childItems = isset($item['CHILD_ITEMS']) ? $item['CHILD_ITEMS'] : array();
			foreach($childItems as &$childItem):
				$childItemID = isset($childItem['ID']) ? $childItem['ID'] : '';
				if($childItemID === '')
					continue;

				$itemInfo['childItems'][] = array(
					'id' => $childItemID,
					'name' => isset($childItem['NAME']) ? $childItem['NAME'] : '',
					'icon' => isset($childItem['ICON']) ? $childItem['ICON'] : '',
					'url' => isset($childItem['URL']) ? $childItem['URL'] : ''
				);
			endforeach;
			unset($childItem);

			$itemInfos[] = &$itemInfo;
			unset($itemInfo);

			$itemContainerID = "{$itemContainerPrefix}{$itemIDLc}";
			$itemContainerIDs[] = $itemContainerID;
			?><div class="crm-menu-item-wrap" id="<?=htmlspecialcharsbx($itemContainerID)?>"><a href="<?=htmlspecialcharsbx($url)?>" class="crm-menu-item<?=$icon !== '' ? ' crm-menu-'.htmlspecialcharsbx($icon) : ''?><?=$isActive ? ' crm-menu-item-active' : ''?>" title="<?=htmlspecialcharsbx($title)?>"><span class="crm-menu-icon"></span><span class="crm-menu-name"><?=htmlspecialcharsbx($briefName)?></span><?
				if ($itemID == 'STREAM'):
					?><span class="crm-menu-icon-counter crm-menu-icon-counter-grey" id="crm_menu_counter" style="display: <?=($counter > 0 ? "inline-block": "none")?>;"><?=$counter <= 99 ? $counter : '99+' ?></span><?
				elseif($counter > 0):
					?><span class="crm-menu-icon-counter"><?=$counter <= 99 ? $counter : '99+' ?></span><?
				endif;
			?></a></div><?
		endforeach;
		unset($item);
		if(is_array($additionaItem)):
			$icon = isset($additionaItem['ICON']) ? strtolower($additionaItem['ICON']) : '';
			if($icon === '')
				$icon = 'more';

			$itemID = isset($additionaItem['ID']) ? $additionaItem['ID'] : '';
			$itemIDLc = strtolower($itemID);
			$name = isset($additionaItem['NAME']) ? $additionaItem['NAME'] : $itemID;
			$title = isset($additionaItem['TITLE']) ? $additionaItem['TITLE'] : '';

			$additionalItemInfo = array(
				'id' => $itemID,
				'name' => $name,
				'icon' => $icon,
				'isActive' => false,
				'url' => '#',
				'actions' => array(),
				'childItems' => array()
			);

			$additionalContainerID = "{$itemContainerPrefix}{$itemIDLc}";
			?><div class="crm-menu-item-wrap" id="<?=htmlspecialcharsbx($additionalContainerID)?>" style="display: none;">
				<a href="#" class="crm-menu-item crm-menu-<?=htmlspecialcharsbx($icon)?>" title="<?=htmlspecialcharsbx($title)?>">
					<span class="crm-menu-icon"></span>
					<span class="crm-menu-name"><?=htmlspecialcharsbx($name)?></span>
				</a>
			</div><?
		endif;
	?></div><?
	if($enableSearch):
		$searchInputID = "crm_ctrl_panel_{$IDLc}_search_input";
	?><span id="<?=htmlspecialcharsbx($searchContainerID)?>" class="crm-search-block">
		<form class="crm-search" action="<?=htmlspecialcharsbx($arResult['SEARCH_PAGE_URL'])?>" method="get">
			<span class="crm-search-btn"></span>
			<span class="crm-search-inp-wrap"><input id="<?=htmlspecialcharsbx($searchInputID)?>" class="crm-search-inp" name="q" type="text" autocomplete="off" placeholder="<?=htmlspecialcharsbx(GetMessage('CRM_CONTROL_PANEL_SEARCH_PLACEHOLDER'))?>"/></span>
			<input type="hidden" name="where" value="crm" /><?
			$APPLICATION->IncludeComponent(
				'bitrix:search.title',
				'backend',
				array(
					'NUM_CATEGORIES' => 1,
					'CATEGORY_0_TITLE' => 'CRM',
					'CATEGORY_0' => array(0 => 'crm'),
					'USE_LANGUAGE_GUESS' => 'N',
					'PAGE' => $arResult['PATH_TO_SEARCH_PAGE'],
					'CONTAINER_ID' => $searchContainerID,
					'INPUT_ID' => $searchInputID,
					'SHOW_INPUT' => 'N'
				),
				$component,
				array('HIDE_ICONS'=>true)
			);
		?></form>
	</span>
	<?endif;?>
	<span class="crm-menu-shadow">
		<span class="crm-menu-shadow-right">
			<span class="crm-menu-shadow-center"></span>
		</span>
	</span>
	<span class="crm-menu-fixed-btn <?=$isFixed ? 'crm-lead-header-contact-btn crm-lead-header-contact-btn-pin' : 'crm-lead-header-contact-btn crm-lead-header-contact-btn-unpin'?>">
	</span>
</div>
</div>
<script type="text/javascript" bxrunfirst="true">
	(function()
		{
			if(typeof(BX.CrmControlPanelSliderInitData) === "undefined")
			{
				BX.CrmControlPanelSliderInitData = {};
			}

			var containers = <?=CUtil::PhpToJSObject($itemContainerIDs)?>;
			var lastIndex = containers.length - 1;
			if(lastIndex < 0)
			{
				return;
			}

			var additional = document.getElementById("<?=CUtil::JSEscape($additionalContainerID)?>");
			if(!additional)
			{
				return;
			}

			var first = document.getElementById(containers[0]);
			var ceiling = BX.pos(first).top;

			var borderIndex = -1;
			for(var j = lastIndex; j > 0; j--)
			{
				var current = document.getElementById(containers[j]);
				if(BX.pos(current).top <= ceiling)
				{
					borderIndex = j;
					break;
				}
			}

			if(borderIndex < 0)
			{
				borderIndex = 0;
			}

			if(borderIndex < lastIndex)
			{
				var border = document.getElementById(containers[borderIndex]);
				border.parentNode.insertBefore(additional, border);
				additional.style.display = "";
			}
			BX.CrmControlPanelSliderInitData["<?=CUtil::JSEscape($ID)?>"] = { borderingItemIndex: borderIndex };
		}
	)();
</script>
<script type="text/javascript">
	BX.ready(
			function()
			{
				var panel = BX.CrmControlPanel.create(
						"<?=CUtil::JSEscape($ID)?>",
						BX.CrmParamBag.create(
							{
								"containerId": "<?=CUtil::JSEscape($containerID)?>",
								"wrapperId": "<?=CUtil::JSEscape($wrapperID)?>",
								"itemContainerPrefix": "<?=CUtil::JSEscape($itemContainerPrefix)?>",
								"itemInfos": <?=CUtil::PhpToJSObject($itemInfos)?>,
								"additionalItemInfo": <?=is_array($additionalItemInfo) ? CUtil::PhpToJSObject($additionalItemInfo) : 'null' ?>,
								"itemWrapperId": "<?=CUtil::JSEscape($itemWrapperID)?>",
								"searchContainerId": "<?=CUtil::JSEscape($searchContainerID)?>",
								"anchorId": "<?=CUtil::JSEscape($searchContainerID)?>",
								"isFixed": <?= $isFixed ? 'true' : 'false'?>
							}
						)
				);
				BX.CrmControlPanel.setDefault(panel);
			}
	);
</script>