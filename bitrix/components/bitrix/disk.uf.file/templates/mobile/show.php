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
	empty($arResult['IMAGES'])
	&& empty($arResult['FILES'])
)
{
	return;
}

if (!empty($arResult['IMAGES']))
{
	?><div id="wdif-block-img-<?=$arResult['UID']?>" class="post-item-attached-img-wrap"><?
	$jsIds = "";
	foreach($arResult['IMAGES'] as $id => $file)
	{
		$id = "webdav-attached-".$id."-".randString(4);
		$jsIds .= $jsIds !== "" ? ', "'.$id.'"' : '"'.$id.'"';
		?><div class="post-item-attached-img-block"><?
			?><img 
				class="post-item-attached-img" 
				id="<?=$id?>" 
				src="<?=CMobileLazyLoad::getBase64Stub()?>" 
				data-src="<?=$file["THUMB"]["src"]?>" 
				alt="" 
				border="0" 
				data-bx-image="<?=$file["BASIC"]["src"] ?>" /><?
		?></div><?
	}
	?></div><?

	if (strlen($jsIds) > 0)
	{
		?><script>BitrixMobile.LazyLoad.registerImages([<?=$jsIds?>], typeof oMSL != 'undefined' ? oMSL.checkVisibility : false);</script><?
	}
}

if (!empty($arResult['FILES']))
{
	?><div id="wdif-block-<?=$arResult['UID']?>" class="post-item-attached-file-wrap"><?
	foreach($arResult['FILES'] as $file)
	{
		?><div id="wdif-doc-<?=$file['ID']?>" class="post-item-attached-file"><?
			if (in_array(ToLower($file["EXTENSION"]), array("exe")))
			{
				?><span title="<?=htmlspecialcharsbx($file['NAVCHAIN'])?>"><span><?=htmlspecialcharsbx($file['NAME'])?></span><span>(<?=$file['SIZE']?>)</span></span><?
			}
			else
			{
				?><a onclick="app.openDocument({'url' : '<?=$file['DOWNLOAD_URL']?>'});" href="javascript:void()" class="post-item-attached-file-link" title="<?=htmlspecialcharsbx($file['NAVCHAIN'])?>"><span><?=htmlspecialcharsbx($file['NAME'])?></span><span>(<?=$file['SIZE']?>)</span></a><?
			}
		?></div><?
	}
	?></div><?		
}
?>

