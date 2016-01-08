<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$this->setFrameMode(true);

if(empty($arResult))
	return;

$this->SetViewTarget("sidebar", 250);
?>

<div class="sidebar-widget sidebar-widget-popular">
	<div class="sidebar-widget-top">
		<div class="sidebar-widget-top-title"><?=GetMessage("BLOG_WIDGET_TITLE")?></div>
	</div>
	<?
	$i = 0;
	foreach($arResult as $arPost):
	//$maxLength = 60;
	//$title = strlen($arPost["~TITLE"]) > $maxLength ? substr($arPost["~TITLE"], 0, $maxLength)."..." : $arPost["~TITLE"];
	//$title = htmlspecialcharsbx($title);
	?>
	<a href="<?=$arPost["urlToPost"]?>" class="sidebar-widget-item<?if(++$i == count($arResult)):?> widget-last-item<?endif?>">
		<span class="user-avatar" <?if (isset($arPost["AVATAR_file"]["src"])):?>style="background:url('<?=$arPost["AVATAR_file"]["src"]?>') no-repeat center;"<?endif?>></span>
		<span class="sidebar-user-info">
			<span class="user-post-name"><?=$arPost["AuthorName"]?></span>
			<span class="user-post-title"><?=$arPost["TITLE"]?></span>
		</span>
	</a>
	<?endforeach?>
</div>



