<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/********************************************************************
				Input params
********************************************************************/
$temp = array("STRING" => preg_replace("/[^0-9]/is", "/", $arParams["THUMBS_SIZE"]));
list($temp["WIDTH"], $temp["HEIGHT"]) = explode("/", $temp["STRING"]);
$arParams["THUMBS_SIZE"] = (intVal($temp["WIDTH"]) > 0 ? intVal($temp["WIDTH"]) : 300);
$arParams["SHOW_TAGS"] = ($arParams["SHOW_TAGS"] == "Y" ? "Y" : "N");
/********************************************************************
				/Input params
********************************************************************/
if (!$this->__component->__parent || strpos($this->__component->__parent->__name, "photogallery") === false):
	$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/components/bitrix/photogallery/templates/.default/style.css');
	$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/components/bitrix/photogallery/templates/.default/themes/gray/style.css');
endif;

CUtil::InitJSCore(array('window', 'ajax'));
$GLOBALS['APPLICATION']->AddHeadScript('/bitrix/components/bitrix/photogallery/templates/.default/script.js');
$arParams["THUMBS_SIZE"] = (intVal($temp["WIDTH"]) > 0 ? intVal($temp["WIDTH"]) : 300);
// EbK
$GLOBALS['APPLICATION']->IncludeComponent("bitrix:main.calendar", "", array("SILENT" => "Y"), $component, array("HIDE_ICONS" => "Y"));
if ($arParams["SHOW_TAGS"] == "Y" && IsModuleInstalled("search")):
	$GLOBALS['APPLICATION']->IncludeComponent("bitrix:search.tags.input", "", array("SILENT" => "Y"), $component, array("HIDE_ICONS" => "Y"));
endif;
$sTitle = htmlspecialcharsEx($arResult["ELEMENT"]["~NAME"]);
?>
<div class="photo-detail">
	<div class="photo-detail-image">
		<div class="photo-detail-photo">
			<div class="photo-detail-img">
				<a rel="nofollow" href="<?=$arResult["SLIDE_SHOW"]?>" <?
					?> id="photo_<?=$arResult["ELEMENT"]["ID"]?>">
					<?=CFile::ShowImage($arResult["ELEMENT"]["PICTURE"]["SRC"],
						$arParams["THUMBS_SIZE"],
						$arParams["THUMBS_SIZE"],
						"border=\"0\" vspace=\"0\" hspace=\"0\" ".
						"alt=\"".$sTitle."\" title=\"".$sTitle."\" ");?>
				</a>
			</div>
		</div>

<?
$ii = ($arResult["ELEMENT"]["CURRENT"]["NO"] - 1);
?>
		<div id="photo_navigation" class="photo-detail-navigation">
			<table cellpadding="5" cellspacing="0" border="0" align="center"><tr><td>
<?
	if (!empty($arResult["ELEMENTS_LIST"][$ii-1]["DETAIL_PAGE_URL"])):
?>
				<a href="<?=$arResult["ELEMENTS_LIST"][$ii-1]["DETAIL_PAGE_URL"]?>" id="photo_go_to_prev" title="<?=GetMessage("P_GO_TO_PREV")?>">
					<span><?=GetMessage("P_PREV")?></span>
				</a>
<?
	else :
?>
				<div id="photo_go_to_prev" title="<?=GetMessage("P_GO_TO_PREV")?>">
					<span><?=GetMessage("P_PREV")?></span>
				</div>
<?
	endif;
?>
			</td>
			<td nowrap="nowrap"><?=GetMessage("NO_OF_COUNT",
				array("#NO#"=>$arResult["ELEMENT"]["CURRENT"]["NO"],"#TOTAL#"=>$arResult["ELEMENT"]["CURRENT"]["COUNT"]))?></td>
			<td>
<?
	if (!empty($arResult["ELEMENTS_LIST"][$ii+1]["DETAIL_PAGE_URL"])):
?>
				<a href="<?=$arResult["ELEMENTS_LIST"][$ii+1]["DETAIL_PAGE_URL"]?>" id="photo_go_to_next" title="<?=GetMessage("P_GO_TO_NEXT")?>">
					<span><?=GetMessage("P_NEXT")?></span>
				</a>
<?
	else:
?>
				<div id="photo_go_to_next" title="<?=GetMessage("P_GO_TO_NEXT")?>">
					<span><?=GetMessage("P_NEXT")?></span>
				</div>
<?
	endif;
?>
			</td></tr></table>
		</div>
	</div>
	<div id="photo_text_description" class="photo-photo-info">
		<div class="photo-photo-name" id="photo_title"><?=$arResult["ELEMENT"]["NAME"]?></div>
		<div class="photo-photo-date" id="photo_date"><?=$arResult["ELEMENT"]["DATE_CREATE"]?></div>
		<div class="photo-photo-description" id="photo_description"><?=$arResult["ELEMENT"]["DETAIL_TEXT"]?></div>
<?
	if ($arParams["SHOW_TAGS"] == "Y")
	{
?>
		<noindex>
			<div class="photo-photo-tags" <?=(empty($arResult["ELEMENT"]["TAGS"]) ? "style='display:none;'" : "")?>>
				<label for="photo_tags"><?=GetMessage("P_TAGS")?>: </label>
				<span id="photo_tags"><?
		if (!empty($arResult["ELEMENT"]["TAGS_LIST"])):
			foreach ($arResult["ELEMENT"]["TAGS_LIST"] as $key => $val):
				?><a rel="nofollow" href="<?=$val["TAGS_URL"]?>"><?=$val["TAGS_NAME"]?></a><?
				if ($key != (count($arResult["ELEMENT"]["TAGS_LIST"]) - 1)):
				?>, <?
				endif;
			endforeach;
		else:
			?><?=$arResult["ELEMENT"]["TAGS"]?><?
		endif;
?>
				</span>
			</div>
		</noindex>
<?
	}
?>
		<div class="photo-photo-rating"><div id="photo_vote"></div></div>
		<div class="photo-controls photo-controls-photo">
			<?
			$this->SetViewTarget("sidebar", 50);
			$first = true;
			?>
			<noindex>
			<div class="sidebar-border-block sidebar-actions-block">
				<div class="sidebar-border-block-top">
					<div class="border"></div>
					<div class="corner left"></div>
					<div class="corner right"></div>
				</div>
				<div class="sidebar-border-block-content">

			<?if (!empty($arResult["SLIDE_SHOW"])):?>
				<?if (!$first):?><span></span><?endif;?><?$first = false;?>
				<a rel="nofollow" href="<?=$arResult["SLIDE_SHOW"]?>"><i class="sidebar-action-icon"></i><b><?= GetMessage('P_SLIDE_SHOW')?></b></a>
			<?endif;?>

		<?if (!empty($arResult["ELEMENT"]["REAL_PICTURE"]["SRC"])):
			$url = ($GLOBALS["APPLICATION"]->IsHTTPS() ? 'https' : 'http').'://'.
				str_replace(array(":443", "//"), array("", "/"), $_SERVER['HTTP_HOST']."/".$arResult["ELEMENT"]["REAL_PICTURE"]["SRC"]);
?>
			<?if (!$first):?><span></span><?endif;?><?$first = false;?>
			<a rel="nofollow" href="<?=$url?>"  title="<?=GetMessage("P_ORIGINAL_TITLE")?>"  onclick="ShowOriginal('<?=CUtil::JSEscape(htmlspecialcharsex($url))?>', '<?=CUtil::JSEscape($arResult["ELEMENT"]["NAME"])?>'); return false;"><i class="sidebar-action-icon"></i><b><?= GetMessage('P_ORIGINAL')?></b></a>
		<?endif;?>

		<?if (!empty($arResult["ELEMENT"]["EDIT_URL"])):?>
			<?if (!$first):?><span></span><?endif;?><?$first = false;?>
			<a rel="nofollow" href="<?=$arResult["ELEMENT"]["EDIT_URL"]?>" title="<?=GetMessage("P_EDIT_TITLE")?>" onclick="EditPhoto('<?=CUtil::JSEscape(htmlspecialcharsex($arResult["ELEMENT"]["~EDIT_URL"]))?>'); return false;" id="photo_edit"><i class="sidebar-action-icon"></i><b><?echo GetMessage('P_EDIT')?></b></a>
		<?endif;?>

		<?if (!empty($arResult["ELEMENT"]["DROP_URL"])):?>
			<?if (!$first):?><span></span><?endif;?><?$first = false;?>
			<a rel="nofollow" href="<?=$arResult["ELEMENT"]["DROP_URL"]?>" title="<?=GetMessage("P_DROP_TITLE")?>" onclick="return confirm('<?=GetMessage("P_DROP_CONFIM")?>');" id="photo_drop"><i class="sidebar-action-icon"></i><b><?echo GetMessage('P_DROP')?></b></a>
		<?endif;?>

		</div>
			<div class="sidebar-border-block-bottom">
				<div class="border"></div>
				<div class="corner left"></div>
				<div class="corner right"></div>
			</div>
		</div>
		</noindex>
		<?$this->EndViewTarget();?>
		</div>
	</div>
	<div class="empty-clear"></div>
</div>
<script>
if (!window.BXPH_MESS)
	BXPH_MESS = {};

BXPH_MESS.EditPhotoTitle = '<?= GetMessage('P_EDIT_TITLE')?>';
BXPH_MESS.UnknownError = '<?= GetMessage('P_UNKNOWN_ERROR')?>';

__photo_go_to_neighbour_link = function()
{
	if (window["BX"])
	{
		BX.bind(document, "keydown", function (e)
		{
			if(!e) e = window.event
			if(!e) return;
			if (e.ctrlKey && (e.keyCode == 37 || e.keyCode == 39))
			{
				var anchor = document.getElementById(e.keyCode == 39 ? "photo_go_to_next" : "photo_go_to_prev");
				if (anchor && anchor.tagName == "A")
					BX.reload(anchor.href);
			}
		});
		return true;
	}
	setTimeout(70, __photo_go_to_neighbour_link);
}
__photo_go_to_neighbour_link();
</script>