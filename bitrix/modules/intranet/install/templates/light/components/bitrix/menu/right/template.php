<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (empty($arResult))
	return;
?>

<div class="sidebar-border-block sidebar-right-menu">
	<div class="sidebar-border-block-top">
		<div class="border"></div>
		<div class="corner left"></div>
		<div class="corner right"></div>
	</div>
	<div class="sidebar-border-block-content">
	<?foreach($arResult as $itemIdex => $arItem)
	{
            if($arItem["LINK"]=="/company/personal/") continue;
		if ($arItem["PERMISSION"] > "D" && $arItem["DEPTH_LEVEL"] == 1){?>
	
		<?if ($itemIdex > 0):?><span></span><?endif?><a href="<?=$arItem["LINK"]?>"<?if($arItem["SELECTED"]):?> class="selected"<?elseif($arItem["IS_PARENT"]):?> class="directory"<?endif?>><?=$arItem["TEXT"]?></a>
		
	<?	}
	}?>
	</div>
	<div class="sidebar-border-block-bottom">
		<div class="border"></div>
		<div class="corner left"></div>
		<div class="corner right"></div>
	</div>
</div>


