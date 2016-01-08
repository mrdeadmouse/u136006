<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
if ($arResult["Modifier"]["PERMISSION"] >= "U")
{
	$this->SetViewTarget("sidebar", 50);
	?><noindex>
	<div class="sidebar-border-block sidebar-actions-block">
		<div class="sidebar-border-block-top">
			<div class="border"></div>
			<div class="corner left"></div>
			<div class="corner right"></div>
		</div>
		<div class="sidebar-border-block-content">
			<a rel="nofollow" href="<?=$arResult["GALLERY"]["LINK"]["UPLOAD"]?>"  target="_self"><i class="sidebar-action-icon"></i><b><?echo GetMessage('P_UPLOAD')?></b></a>	

			<span></span>
			<a rel="nofollow" href="<?=$arResult["GALLERY"]["LINK"]["NEW"]?>" onclick="EditAlbum('<?=CUtil::JSEscape($arResult["GALLERY"]["LINK"]["NEW"])?>'); return false;"><i class="sidebar-action-icon"></i><b><?echo GetMessage('P_ADD_ALBUM')?></b></a>

		</div>
		<div class="sidebar-border-block-bottom">
			<div class="border"></div>
			<div class="corner left"></div>
			<div class="corner right"></div>
		</div>
	</div>
	</noindex><?
	$this->EndViewTarget();
}

/********************************************************************
				Input params
********************************************************************/
$arParams["ALBUM_PHOTO_SIZE"] = intVal($arParams["ALBUM_PHOTO_SIZE"]);

/********************************************************************
				/Input params
********************************************************************/
CAjax::Init();
$GLOBALS['APPLICATION']->AddHeadScript('/bitrix/js/main/utils.js');
$GLOBALS['APPLICATION']->AddHeadScript('/bitrix/components/bitrix/photogallery/templates/.default/script.js');
$GLOBALS['APPLICATION']->AddHeadScript("/bitrix/components/bitrix/photogallery.section.list/templates/.default/script.js");
if (!$this->__component->__parent || strpos($this->__component->__parent->__name, "photogallery") === false):
	$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/components/bitrix/photogallery/templates/.default/style.css');
	$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/components/bitrix/photogallery/templates/.default/themes/gray/style.css');
?>
<style>
.photo-album-list div.photo-item-cover-block-container, 
.photo-album-list div.photo-item-cover-block-outer, 
.photo-album-list div.photo-item-cover-block-inner{
	background-color: white;
	height:<?=($arParams["ALBUM_PHOTO_SIZE"] + 16)?>px;
	width:<?=($arParams["ALBUM_PHOTO_SIZE"] + 40)?>px;}
div.photo-album-avatar{
	width:<?=$arParams["ALBUM_PHOTO_SIZE"]?>px;
	height:<?=$arParams["ALBUM_PHOTO_SIZE"]?>px;}
ul.photo-album-list div.photo-item-info-block-outside {
	width: <?=($arParams["ALBUM_PHOTO_SIZE"] + 48)?>px;}
</style>
<?
endif;

if (!function_exists("__photo_cut_long_words"))
{
	function __photo_cut_long_words($str)
	{
		$MaxStringLen = 5;
		if (strLen($str) > $MaxStringLen)
		{
			$str = preg_replace("/([^ \n\r\t\x01]{".$MaxStringLen."})/is".BX_UTF_PCRE_MODIFIER, "\\1<WBR/>&shy;", $str);
		}
		return $str;
	}

	function __photo_cut_long_words_callback($m)
	{
		return __photo_cut_long_words($m[2]);
	}
}
if (!function_exists("__photo_part_long_words"))
{
	function __photo_part_long_words($str)
	{
		$word_separator = "\s.,;:!?\#\-\*\|\[\]\(\)\{\}";
		if (strLen(trim($str)) > 5)
		{
			$str = str_replace(
				array(chr(1), chr(2), chr(3), chr(4), chr(5), chr(6), chr(7), chr(8), 
					"&amp;", "&lt;", "&gt;", "&quot;", "&nbsp;", "&copy;", "&reg;", "&trade;", 
					chr(34), chr(39)), 
				array("", "", "", "", "", "", "", "", 
					chr(1), "<", ">", chr(2), chr(3), chr(4), chr(5), chr(6), 
					chr(7), chr(8)), 
				$str
			);
			$str = preg_replace_callback(
				"/(?<=[".$word_separator."]|^)(([^".$word_separator."]+))(?=[".$word_separator."]|$)/is".BX_UTF_PCRE_MODIFIER, 
				"__photo_cut_long_words_callback", 
				$str
			);

			$str = str_replace(
				array(chr(1), "<", ">", chr(2), chr(3), chr(4), chr(5), chr(6), chr(7), chr(8), "&lt;WBR/&gt;", "&lt;WBR&gt;", "&amp;shy;"),
				array("&amp;", "&lt;", "&gt;", "&quot;", "&nbsp;", "&copy;", "&reg;", "&trade;", chr(34), chr(39), "<WBR/>", "<WBR/>", "&shy;"),
				$str);
		}
		return $str;
	}
}

if (empty($arResult["SECTIONS"])):
?>
<div class="photo-info-box photo-info-box-sections-list-empty">
	<div class="photo-info-box-inner"><?=GetMessage("P_EMPTY_DATA")?></div>
</div>
<?
	return false;
endif;

?>
<ul class="photo-items-list photo-album-list"><?
	foreach($arResult["SECTIONS"] as $res)
	{
		$res["NAME"] = __photo_part_long_words($res["NAME"]);

		?><li class="photo-album-item photo-album-<?=($res["ACTIVE"] != "Y" ? "nonactive" : "active")?> <?=(
			!empty($res["PASSWORD"]) ? "photo-album-password" : "")?>" id="photo_album_info_<?=$res["ID"]?>" <?
			if ($res["ACTIVE"] != "Y" || !empty($res["PASSWORD"]))
			{
				$sTitle = GetMessage("P_ALBUM_IS_NOT_ACTIVE"); 
				if ($res["ACTIVE"] != "Y" && !empty($res["PASSWORD"]))
					$sTitle = GetMessage("P_ALBUM_IS_NOT_ACTIVE_AND_PASSWORDED"); 
				elseif (!empty($res["PASSWORD"]))
					$sTitle = GetMessage("P_ALBUM_IS_PASSWORDED"); 
				?> title="<?=$sTitle?>" <?
			}
			?>>
			<div class="photo-item-cover-block-outside">
				<div class="photo-item-cover-block-container">
					<div class="photo-item-cover-block-outer">
						<div class="photo-item-cover-block-inner">
							<div class="photo-item-cover-block-inside"><?
								?><div class="photo-item-cover photo-album-avatar <?=(empty($res["DETAIL_PICTURE"]["SRC"])? "photo-album-avatar-empty" : "")?>" <?
									?> id="photo_album_cover_<?=$res["ID"]?>" <?
									?> title="<?=htmlspecialcharsbx($res["~NAME"])?>" <?
									if (!empty($res["DETAIL_PICTURE"]["SRC"])):
										?> style="background-image:url('<?=$res["DETAIL_PICTURE"]["SRC"]?>');" <?
									endif;
									if ($arParams["PERMISSION"] >= "W"):
										?> onmouseover="this.firstChild.style.display='block';" <?
									else: 
										?> onclick="jsUtils.Redirect([], '<?=CUtil::JSEscape(htmlspecialcharsbx($res["~LINK"]))?>');" <?
									endif;
									?>><?
									if ($arParams["PERMISSION"] >= "W")
									{
										?><div class="photo-album-menu" onmouseout="this.style.display='none';" style="display:none;" <?
											?> onclick="jsUtils.Redirect([], '<?=CUtil::JSEscape(htmlspecialcharsbx($res["~LINK"]))?>');"><?
											?><div class="photo-album-menu-substrate<?=(empty($res["EDIT_ICON_LINK"]) ? " photo-album-menu-substrate-half" : "")?>"></div><?
											?><div class="photo-album-menu-controls"><?
												?><a rel="nofollow" href="<?=$res["EDIT_LINK"]?>" <?
													?> class="photo-control-edit photo-control-album-edit" <?
													?> onclick="jsUtils.PreventDefault(event); EditAlbum('<?= CUtil::JSEscape(htmlspecialcharsbx($res["EDIT_LINK"]))?>'); return false;" <?
													?> title="<?=GetMessage("P_SECTION_EDIT_TITLE")?>"><span><?=GetMessage("P_SECTION_EDIT")?></span></a><?
												if (!empty($res["EDIT_ICON_LINK"])):
												?><a rel="nofollow" href="<?=$res["EDIT_ICON_LINK"]?>" <?
													?>class="photo-control-edit photo-control-album-edit-icon" <?
													?> onclick="jsUtils.PreventDefault(event); EditAlbum('<?= CUtil::JSEscape(htmlspecialcharsbx($res["EDIT_ICON_LINK"]))?>'); return false;" <?
													?> title="<?=GetMessage("P_EDIT_ICON_TITLE")?>"><span><?=GetMessage("P_EDIT_ICON")?></span></a><?
												endif;
												?><a rel="nofollow" href="<?= $res["DROP_LINK"]?>" <?
													?> class="photo-control-drop photo-control-album-drop" <?
													?> onclick="jsUtils.PreventDefault(event); if (confirm('<?=GetMessage('P_SECTION_DELETE_ASK')?>')) {DropAlbum(this.href);} return false;" <?
													?> title="<?=GetMessage("P_SECTION_DELETE_TITLE")?>"><span><?=GetMessage("P_SECTION_DELETE")?></span></a><?
																	
											?></div><?
										?></div><?
									}
									?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="photo-item-info-block-outside">
				<div class="photo-item-info-block-container">
					<div class="photo-item-info-block-outer">
						<div class="photo-item-info-block-inner">
							<div class="photo-album-photos-top"><?=$res["ELEMENTS_CNT"]?> <?=GetMessage("P_PHOTOS")?></div>
							<div class="photo-album-name">
								<a href="<?=$res["LINK"]?>" id="photo_album_name_<?=$res["ID"]?>" title="<?=htmlspecialcharsbx($res["~NAME"])?>" <?
									?> onmouseover="__photo_check_name_length(event, this);"><?=$res["NAME"]?></a>
							</div>
							<div class="photo-album-description" id="photo_album_description_<?=$res["ID"]?>"><?=$res["DESCRIPTION"]?></div>
							<div class="photo-album-date"><span id="photo_album_date_<?=$res["ID"]?>"><?=$res["DATE"]?></span></div>
							<div class="photo-album-photos"><?=$res["ELEMENTS_CNT"]?> <?=GetMessage("P_PHOTOS")?></div>
						</div>
					</div>
				</div>
			</div>
		</li><?
	}
?></ul>
<div class="empty-clear"></div><?
if (!empty($arResult["NAV_STRING"]))
{
	?><div class="photo-navigation photo-navigation-bottom"><?=$arResult["NAV_STRING"]?></div><?
}
?>