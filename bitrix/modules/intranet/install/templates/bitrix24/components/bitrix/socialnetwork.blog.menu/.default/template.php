<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
if ($arResult["showAll"] == "Y")
{
	$this->SetViewTarget("pagetitle", 50);
	?><div class="sonet-log-pagetitle-block"><?
	if($arResult["show4MeAll"] == "Y" || $arResult["showAll"] == "Y")
	{
		?><a href="<?=$arResult["PATH_TO_4ME_ALL"]?>" class="pagetitle-but-wrap<?if($arResult["page"] == "all"):?> pagetitle-but-act<?endif;?>" title="<?=GetMessage("BLOG_MENU_4ME_ALL_TITLE")?>"><span class="pagetitle-but-left"></span><span class="pagetitle-but-text"><?=GetMessage("BLOG_MENU_4ME_ALL")?></span><span class="pagetitle-but-right"></span><?if($arResult["page"] == "all"):?><span class="pagetitle-but-angle"></span><?endif;?></a><?
	}
	if(strlen($arResult["PATH_TO_MINE"]) > 0)
	{
		?><a href="<?=$arResult["PATH_TO_MINE"]?>" class="pagetitle-but-wrap<?if($arResult["page"] == "mine"):?> pagetitle-but-act<?endif;?>" title="<?=GetMessage("BLOG_MENU_MINE_TITLE")?>"><span class="pagetitle-but-left"></span><span class="pagetitle-but-text"><?=GetMessage("BLOG_MENU_MINE")?></span><span class="pagetitle-but-right"></span><?if($arResult["page"] == "mine"):?><span class="pagetitle-but-angle"></span><?endif;?></a><?
	}
	if($arResult["show4Me"] == "Y")
	{
		?><a href="<?=$arResult["PATH_TO_4ME"]?>" class="pagetitle-but-wrap<?if($arResult["page"] == "forme"):?> pagetitle-but-act<?endif;?>" title="<?=GetMessage("BLOG_MENU_4ME_TITLE")?>"><span class="pagetitle-but-left"></span><span class="pagetitle-but-text"><?=GetMessage("BLOG_MENU_4ME")?></span><span class="pagetitle-but-right"></span><?if($arResult["page"] == "forme"):?><span class="pagetitle-but-angle"></span><?endif;?></a><?
	}
	if (strlen($arResult["urlToDraft"]) > 0 && IntVal($arResult["CntToDraft"]) > 0)
	{
		?><a href="<?=$arResult["urlToDraft"]?>" class="pagetitle-but-wrap<?if($arResult["page"] == "draft"):?> pagetitle-but-act<?endif;?>" title="<?=GetMessage("BLOG_MENU_DRAFT_MESSAGES_TITLE")?>"><span class="pagetitle-but-left"></span><span class="pagetitle-but-text"><?=GetMessage("BLOG_MENU_DRAFT_MESSAGES")?><span class="pagetitle-but-counter"><?=$arResult["CntToDraft"]?></span></span><span class="pagetitle-but-right"></span><?if($arResult["page"] == "draft"):?><span class="pagetitle-but-angle"></span><?endif;?></a><?
	}
	if (strlen($arResult["urlToModeration"]) > 0 && IntVal($arResult["CntToModerate"]) > 0)
	{
		?><a href="<?=$arResult["urlToModeration"]?>" class="pagetitle-but-wrap<?if($arResult["page"] == "moderation"):?> pagetitle-but-act<?endif;?>" title="<?=GetMessage("BLOG_MENU_MODERATION_MESSAGES_TITLE")?>"><span class="pagetitle-but-left"></span><span class="pagetitle-but-text"><?=GetMessage("BLOG_MENU_MODERATION_MESSAGES")?><span class="pagetitle-but-counter"><?=$arResult["CntToModerate"]?></span></span><span class="pagetitle-but-right"></span><?if($arResult["page"] == "moderation"):?><span class="pagetitle-but-angle"></span><?endif;?></a><?
	}
	?>
	</div>
	<?
	$this->EndViewTarget();
}
?>