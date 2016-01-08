<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
if(!empty($arResult))
{

	$this->SetViewTarget("sidebar_tools_2", 100);	
	?>
	<div class="sidebar-links">
		<a href="<?=$arResult[0]["url"]?>" title=""><i class="sidebar-action-icon sidebar-action-rss"></i><b><?=$arResult[0]["name"]?></b></a><span></span>
	</div>
	<?
	$this->EndViewTarget();
}
?>