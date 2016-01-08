<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (empty($arResult))
	return;

CUtil::InitJSCore(array('popup'));

$lastSelectedItem = null;
$lastSelectedIndex = -1;

foreach($arResult as $itemIdex => $arItem)
{
	if (!$arItem["SELECTED"])
		continue;

	if ($lastSelectedItem == null || strlen($arItem["LINK"]) >= strlen($lastSelectedItem["LINK"]))
	{
		$lastSelectedItem = $arItem;
		$lastSelectedIndex = $itemIdex;
	}
}

$popupId = randString();

?><span class="site-selector-menu" onclick="ShowSiteSelectorPopup(this, '<?=$popupId?>');"><span class="site-selector-menu-text"><?=($lastSelectedItem >=0 ? $arResult[$lastSelectedIndex]["TEXT"] : $arResult[0]["TEXT"] )?></span><span class="site-selector-menu-arrow"></span></span><span class="site-selector-separator"></span><?
?><div class="site-selector-popup" id="site-selector-popup-<?=$popupId?>"><div class="site-selector-popup-items"><?
foreach($arResult as $itemIdex => $arItem): ?>
<?if ($itemIdex > 0):?><div class="popup-window-hr"><i></i></div><?endif?><a class="site-selector-popup-item<?if ($itemIdex == $lastSelectedIndex):?> site-selector-popup-item-selected<?endif;?>" href="<?=$arItem["LINK"]?>"><span class="site-selector-popup-item-left"></span><span class="site-selector-popup-item-text"><?=$arItem["TEXT"]?></span><span class="site-selector-popup-item-right"></span></a>
<?endforeach;?>
</div></div>