<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
if(!empty($arResult))
{
	?>
	<div class="sidebar-block">
		<b class="r2"></b><b class="r1"></b><b class="r0"></b>
		<div class="sidebar-block-inner">
	
		<div class="sidebar-block-title"><?=GetMessage("BLOG_POPULAR_POSTS_TITLE")?></div><?

		foreach($arResult as $arPost)
		{
			if($arPost["FIRST"]!="Y")
			{
				?><div class="sidebar-block-hr"></div><?
			}
			?>
			<div class="sidebar-blog-meta"><a href="<?=$arPost["urlToPost"]?>" title="<?=GetMessage("BLOG_BLOG_M_DATE")?>"><?=$arPost["DATE_PUBLISH_FORMATED"]?></a></div>
			<div class="sidebar-blog-title"><a href="<?=$arPost["urlToPost"]?>"><?echo $arPost["TITLE"]; ?></a></div>
			<?
		}
		?>
		</div>
		<i class="r0"></i><i class="r1"></i><i class="r2"></i>
	</div>
	<?
}
?>