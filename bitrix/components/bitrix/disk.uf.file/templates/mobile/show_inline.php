<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
if (
	sizeof($arResult['FILES']) <= 0
)
{
	return;
}

$jsIds = "";
foreach ($arResult['FILES'] as $id => $file)
{
	if (array_key_exists("IMAGE", $file))
	{
		$nodeId = "webdav-inline-".$id."-".randString(4);
		$jsIds .= $jsIds !== "" ? ', "'.$nodeId.'"' : '"'.$nodeId.'"';
		?><img src="<?=CMobileLazyLoad::getBase64Stub()?>" <?
			?> border="0" <?
			?> data-preview-src="<?=$file["SMALL"]["src"]?>" <?
			?> data-src="<?=$file["INLINE"]['src']?>" <? // inline
			?> title="<?=htmlspecialcharsbx($file['NAME'])?>" <?
			?> alt="<?=htmlspecialcharsbx($file['NAME'])?>" <?
			?> data-bx-image="<?=$file["BASIC"]["src"]?>" <? // gallery
			?> width="<?=round($file["INLINE"]["width"]/2)?>" <?
			?> height="<?=round($file["INLINE"]["height"]/2)?>" <?
			?> id="<?=$nodeId?>" /><?
	}
	else
	{
		?><a onclick="app.openDocument({'url' : '<?=$file['DOWNLOAD_URL']?>'});" href="javascript:void()" <?
			?>id="wdif-doc-<?=$file['ID']?>" <?
			?>title="<?=htmlspecialcharsbx($file['NAVCHAIN'])?>" <?
			?>alt="<?=htmlspecialcharsbx($file['NAME'])?>" class="feed-com-file-wrap"><?
			?><span class="feed-com-file-icon feed-file-icon-<?=htmlspecialcharsbx($file['EXTENSION'])?>"></span><?
			?><span class="feed-com-file-name"><?=htmlspecialcharsbx($file['NAME'])?></span><?
			?><span class="feed-com-file-size">(<?=$file['SIZE']?>)</span><?
		?></a><?
	}
}

if (strlen($jsIds) > 0)
{
	?><script>BitrixMobile.LazyLoad.registerImages([<?=$jsIds?>], typeof oMSL != 'undefined' ? oMSL.checkVisibility : false);</script><?
}