<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
if(!empty($arResult))
{
	if(!empty($arResult["CATEGORY"]))
	{
		?>
		<noindex>
		<div class="sidebar-block">
			<b class="r2"></b><b class="r1"></b><b class="r0"></b>
			<div class="sidebar-block-inner">
			<div class="sidebar-block-title"><?=GetMessage("BLOG_BLOG_BLOGINFO_CAT")?></div>
			<div class="sonet-blog-tags-cloud" <?=$arParams["WIDTH"]?>>
				<?
				foreach($arResult["CATEGORY"] as $arCategory)
				{
					if($arCategory["SELECTED"]=="Y")
						echo "<b>";
					?><a href="<?=$arCategory["urlToCategory"]?>" title="<?GetMessage("BLOG_BLOG_BLOGINFO_CAT_VIEW")?>" style="font-size: <?=$arCategory["FONT_SIZE"]?>%;" rel="nofollow"><?=$arCategory["NAME"]?></a> <?
					if($arCategory["SELECTED"]=="Y")
							echo "</b>";
				}
			?></div>
			</div>
			<i class="r0"></i><i class="r1"></i><i class="r2"></i>
		</div>
		</noindex>
		<?
	}
}
?>