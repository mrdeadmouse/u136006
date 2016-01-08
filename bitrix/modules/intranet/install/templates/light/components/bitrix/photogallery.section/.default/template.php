<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (!$this->__component->__parent || strpos($this->__component->__parent->__name, "photogallery") === false):
	$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/components/bitrix/photogallery/templates/.default/style.css');
	$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/components/bitrix/photogallery/templates/.default/themes/gray/style.css');
?>

<style>
div.photo-album-avatar{
	width:<?=$arParams["ALBUM_PHOTO_SIZE"]?>px;
	height:<?=$arParams["ALBUM_PHOTO_SIZE"]?>px;}
</style>

<?
endif;

CAjax::Init();

$GLOBALS['APPLICATION']->AddHeadScript('/bitrix/js/main/utils.js');
$GLOBALS['APPLICATION']->AddHeadScript('/bitrix/components/bitrix/photogallery/templates/.default/script.js');
$GLOBALS['APPLICATION']->AddHeadScript("/bitrix/components/bitrix/photogallery.section.list/templates/.default/script.js");
$GLOBALS['APPLICATION']->AddHeadScript("/bitrix/js/main/admin_tools.js");
$GLOBALS['APPLICATION']->AddHeadScript("/bitrix/js/main/popup_menu.js");

$GLOBALS['APPLICATION']->SetAdditionalCSS("/bitrix/themes/.default/pubstyles.css");
$GLOBALS['APPLICATION']->SetAdditionalCSS("/bitrix/themes/.default/jspopup.css");
$res = $arResult["SECTION"];
if ($arParams["PERMISSION"] >= "U")
{
	$arActions = array( 
		array(
			"ICONCLASS" => "photo-control-album-edit",
			"TEXT" => GetMessage("P_SECTION_EDIT"),
			"DEFAULT" => true, 
			"ONCLICK" => "EditAlbum('".CUtil::JSEscape($arResult["SECTION"]["~EDIT_LINK"])."');"
		) 
	);

	if (!empty($arResult["SECTION"]["~EDIT_ICON_LINK"]))
	{
		$arActions[] = array(
			"ICONCLASS" => "photo-control-album-edit-icon",
			"TEXT" => GetMessage("P_SECTION_EDIT_ICON"),
			"ONCLICK" => "EditAlbum('".CUtil::JSEscape($arResult["SECTION"]["~EDIT_ICON_LINK"])."');");
	}
	if (!empty($arResult["SECTION"]["DROP_LINK"]))
	{
		$arActions[] = array("SEPARATOR" => true);
		$arActions[] = array(
			"ICONCLASS" => "photo-control-album-drop",
			"TEXT" => GetMessage("P_SECTION_DELETE"),
			"ONCLICK" => "if(confirm('".CUtil::JSEscape(GetMessage('P_SECTION_DELETE_ASK'))."')) {jsUtils.Redirect([], '".CUtil::JSEscape($arResult["SECTION"]["~DROP_LINK"])."')}; return false;");
	}
?>
<script>
if (typeof oObjectP != "object")
	var oObjectP = {};

function JCPMenu(id, oObj)
{
	if (oObj.firstChild.firstChild.bxClick == 'Y')
	{
		oObj.firstChild.firstChild.bxClick = 'N'; 
		EditAlbum(oObj.href); 
	}
	else
	{
		if (oObjectP['object'] == null || !oObjectP['object'])
			oObjectP['object'] = new PopupMenu('photo_section');
		
		oObjectP['object'].ShowMenu(oObj.parentNode, window.oObjectP['section_' + id]);
	}
}

oObjectP['section_<?=$arResult["SECTION"]["ID"]?>'] = <?=CUtil::PhpToJSObject($arActions)?>;
</script>
<?
}

?>
<?
if ($arParams["PERMISSION"] >= "U")
{
	$this->SetViewTarget("sidebar", 50);
?>
	<noindex>
	<div class="sidebar-border-block sidebar-actions-block">
		<div class="sidebar-border-block-top">
			<div class="border"></div>
			<div class="corner left"></div>
			<div class="corner right"></div>
		</div>
		<div class="sidebar-border-block-content">
			<a rel="nofollow" href="<?=$arResult["SECTION"]["UPLOAD_LINK"]?>" target="_self"><i class="sidebar-action-icon"></i><b><?= GetMessage('P_UPLOAD')?></b></a>

			<span></span>
			<a rel="nofollow" href="<?=$arResult["SECTION"]["NEW_LINK"]?>" onclick="EditAlbum('<?=CUtil::JSEscape($arResult["SECTION"]["~NEW_LINK"])?>'); return false;"><i class="sidebar-action-icon"></i><b><?= GetMessage('P_ADD_ALBUM')?></b></a>	

			<span></span>
			<a rel="nofollow" href="<?=$arResult["SECTION"]["EDIT_LINK"]?>"><i class="sidebar-action-icon"></i><b><?= GetMessage('P_SECTION_EDIT')?></b></a>

			<?if (!empty($arResult["SECTION"]["DROP_LINK"])):?>
				<span></span>
				<a rel="nofollow" href="<?=$arResult["SECTION"]["DROP_LINK"]?>" onclick="if(confirm('<?=CUtil::JSEscape(GetMessage('P_SECTION_DELETE_ASK'))?>')) {jsUtils.Redirect([], '<?=CUtil::JSEscape($arResult["SECTION"]["~DROP_LINK"])?>')}; return false;"><i class="sidebar-action-icon"></i><b><?= GetMessage('P_SECTION_DELETE')?></b></a>
			<?endif;?>			

			<?if (!empty($arResult["SECTION"]["EDIT_ICON_LINK"])):?>
				<span></span>
				<a rel="nofollow" href="<?=$arResult["SECTION"]["EDIT_ICON_LINK"]?>"><i class="sidebar-action-icon"></i><b><?= GetMessage('P_SECTION_EDIT_ICON')?></b></a>
			<?endif;?>

		</div>
		<div class="sidebar-border-block-bottom">
			<div class="border"></div>
			<div class="corner left"></div>
			<div class="corner right"></div>
		</div>
	</div>
	</noindex>
<?
	$this->EndViewTarget();
}
?>
<div style="float:left;" class="photo-album-item photo-album-<?=($res["ACTIVE"] != "Y" ? "nonactive" : "active")?> <?=(
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
	<div class="photo-album-info">
		<div class="photo-album-name" id="photo_album_name_<?=$arResult["SECTION"]["ID"]?>"><?=$arResult["SECTION"]["NAME"]?></div>
		<div class="photo-album-date"><span id="photo_album_date_<?=$arResult["SECTION"]["ID"]?>"><?=$arResult["SECTION"]["DATE"]["VALUE"]?></span></div>
		<div class="photo-album-description" id="photo_album_description_<?=$arResult["SECTION"]["ID"]?>"><?=$arResult["SECTION"]["DESCRIPTION"]?></div>
	</div>
</div>
<div class="empty-clear"></div>