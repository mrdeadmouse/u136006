<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (empty($arResult))
	return;
?>

<?foreach($arResult as $itemIdex => $arItem):?>
<?if ($itemIdex > 0):
	?>&nbsp;&nbsp;<?
endif;

if(strpos($arItem["LINK"], "#print")!==false):
	?><a href="#print" target="_blank"<?
else:
	?><a href="<?=$arItem["LINK"]?>"<?
endif;
?>><?=$arItem["TEXT"]?></a>
<?endforeach;?>
