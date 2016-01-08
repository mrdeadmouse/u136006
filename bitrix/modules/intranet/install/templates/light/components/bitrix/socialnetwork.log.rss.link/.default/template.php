<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<?
$this->SetViewTarget("sidebar_tools_2", 100);	
?>
<div class="sidebar-links">
	<a href="<?=$arResult["PATH_TO_RSS"]?>"><i class="sidebar-action-icon sidebar-action-rss"></i><b>RSS</b></a><span></span>
</div>
<?
$this->EndViewTarget();
?>