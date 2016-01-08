<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
$this->IncludeLangFile("edit.php");
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
/** @var \Bitrix\Disk\Internals\BaseComponent $component */
CJSCore::Init(array('file_dialog', 'ajax', 'dd', "core", "uploader", "canvas", "disk_external_loader"));

$addClass = ((strpos($_SERVER['HTTP_USER_AGENT'], 'Mac OS') !== false) ? 'diskuf-filemacos' : '');
$mess = GetMessage('WD_FILE_LOADING');
$thumb = <<<HTML
<td class="files-name">
	<span class="files-text">
		<span class="f-wrap">
			#name#
			<span class="wd-files-icon feed-file-icon-#ext#"></span>
		</span>
	</span>
</td>
<td class="files-size">#size#</td>
<td class="files-storage">
	<span>{$mess}</span>
	<span class="feed-add-post-loading-wrap">
		<span class="feed-add-post-loading">
			<span class="feed-add-post-loading-cancel del-but" id="wdu#id#TerminateButton"></span>
		</span>
		<span class="feed-add-post-load-indicator" id="wdu#id#Progressbar" style="width:5%;">
			<span class="feed-add-post-load-number" id="wdu#id#ProgressbarText">5%</span>
		</span>
	</span>
</td>
HTML;

$uploadedFile = <<<HTML
<td class="files-name">
	<span class="files-text">
		<input type="text" value="#label#" class="files-name-edit-inp">
		<span class="f-wrap">#name#</span>
		<span class="wd-files-icon files-preview-wrap" style="display: none;">
			<span class="files-preview-border">
				<span class="files-preview-alignment">
					<img class="files-preview" src="#preview_url#" data-bx-width="#width#" data-bx-height="#height#" data-bx-src="#preview_url#" data-bx-thumb-size="{$arParams["THUMB_SIZE"]}" onload="BX.onCustomEvent('onDiskPreviewIsReady', [this, BX('disk-edit-attach#id#')]);" onerror="this.parentNode.removeChild(this);" />
				</span>
			</span>
		</span><span class="wd-files-icon feed-file-icon-#ext#"></span>
		<span class="files-name-edit-btn"></span>
	</span>
</td>
<td class="files-size">#size#</td>
<td class="files-storage">
	<div class="files-storage-block">
		<span class="files-placement">#storage#</span>
		<input id="diskuf-doc#id#" type="hidden" name="#control_name#" value="#id#" />
	</div>
</td>
HTML;
$thumb = preg_replace("/[\n\t]+/", "", $thumb);
$uploadedFile =  preg_replace("/[\n\t]+/", "", $uploadedFile);



include_once(str_replace(array("\\", "//"), "/", __DIR__."/functions.php"));
if(empty($arResult['FILES']))
{
	?><a href="javascript:void(0);" id="diskuf-selectdialogswitcher-<?=$arResult['UID']?>" class="diskuf-selectdialog-switcher" onclick="BX.onCustomEvent(this.parentNode, 'DiskLoadFormController', ['show'])"><span><?=GetMessage("WDUF_UPLOAD_DOCUMENT")?></span></a><?
}
?><div id="diskuf-selectdialog-<?=$arResult['UID']?>" class="diskuf-selectdialog bx-disk" <?if(empty($arResult['FILES'])){?> style="display:none;"<?}?>>
	<div class="diskuf-files-block"<?if(!empty($arResult['FILES'])){?> style="display:block;"<?}?>>
		<div class="diskuf-label">
			<?=GetMessage("WDUF_ATTACHMENTS")?>
			<span class="diskuf-label-icon"></span>
		</div>
		<div class="diskuf-placeholder">
			<table cellspacing="0" class="files-list">
				<tbody class="diskuf-placeholder-tbody">
<?
foreach ($arResult['FILES'] as $file)
{
	if (array_key_exists("IMAGE", $file))
	{
		CFile::ScaleImage(
			$file["IMAGE"]["WIDTH"],
			$file["IMAGE"]["HEIGHT"],
			array("width" => $arParams["THUMB_SIZE"], "height" => $arParams["THUMB_SIZE"]),
			BX_RESIZE_IMAGE_PROPORTIONAL,
			$bNeedCreatePicture,
			$arSourceSize,
			$arDestinationSize
		);
		$file["width"] = $arDestinationSize["width"];
		$file["height"] = $arDestinationSize["height"];
	}
?>
				<tr class="wd-inline-file" id="disk-edit-attach<?=$file['ID']?>" bx-attach-file-id="<?=\Bitrix\Disk\Uf\FileUserType::NEW_FILE_PREFIX?><?=$file['FILE_ID']?>"<?
					if ($file['XML_ID']): ?> bx-attach-xml-id="<?=$file['XML_ID']?>"<?endif;?>><?
					$f = str_replace(array("#control_name#", "#CONTROL_NAME#"), $arResult['controlName'], $uploadedFile);
					$uploadedFile = str_replace('<span class="files-name-edit-btn"></span>', '', $uploadedFile);
					foreach ($file as $k => $v)
					{
						$f = str_replace(array("#".strtoupper($k)."#", "#".strtolower($k)."#"), $v, $f);
					}
					?><?=$f?>
				</tr>
<?
}  // foreach
?>
			</tbody>
		</table>
		<? if(!empty($arResult['DISK_ATTACHED_OBJECT_ALLOW_EDIT'])) { ?>
		<div class="feed-add-post-files-activity">
			<div class="feed-add-post-files-activity-item">
				<input name="<?= $arResult['INPUT_NAME_OBJECT_ALLOW_EDIT'] ?>" <?= (empty($arResult['SHARE_EDIT_ON_OBJECT_UF'])? '' : 'checked="checked"') ?> value="1" type="checkbox" id="diskuf-edit-rigths-doc" class="feed-add-post-files-activity-checkbox"><label class="feed-add-post-files-activity-label" for="diskuf-edit-rigths-doc"><?= GetMessage('WDUF_FILE_EDIT_BY_DESTINATION_USERS'); ?></label>
			</div>
		</div>
		<? } ?>
		</div>
	</div>
	<div class="diskuf-extended">
		<input type="hidden" name="<?=htmlspecialcharsbx($arResult['controlName'])?>" value="" />
		<div class="diskuf-extended-overlay">
			<div class="diskuf-extended-overlay-inner">
				<span class="diskuf-extended-overlay-icon"></span>
				<span class="diskuf-extended-overlay-text"><?=GetMessage("WDUF_SELECT_ATTACHMENTS")?><span><?=GetMessage("WDUF_DROP_ATTACHMENTS")?></span></span>
			</div>
		</div>
		<?= DiskRenderTable(!empty($arResult['CLOUD_DOCUMENT']), $addClass, $arResult['DEFAULT_DOCUMENT_SERVICE_EDIT_NAME']); ?>
	</div>
	<div class="diskuf-simple">
		<input type="hidden" name="<?=htmlspecialcharsbx($arResult['controlName'])?>" value="" />
		<?= DiskRenderTable(!empty($arResult['CLOUD_DOCUMENT']), $addClass, $arResult['DEFAULT_DOCUMENT_SERVICE_EDIT_NAME']); ?>
	</div>
<script type="text/javascript">
BX.ready(function(){
	BX.Disk.UF.add({
		UID : '<?=$arResult['UID']?>',
		controlName : '<?= CUtil::JSEscape($arResult['controlName'])?>',
		urlSelect : '<?=CUtil::JSEscape('/bitrix/tools/disk/uf.php?action=selectFile&SITE_ID=' . SITE_ID)?>',
		urlRenameFile : '<?=CUtil::JSEscape('/bitrix/tools/disk/uf.php?action=renameFile')?>',
		urlUpload : '<?= CUtil::JSUrlEscape($arResult['UPLOAD_FILE_URL']) ?>',
		thumbSize : '<?=$arParams["THUMB_SIZE"]?>'
	});
});
BX.message({
	DISK_FOLDER_TOOLBAR_LABEL_LOCAL_BDISK_EDIT: '<?= CUtil::JSEscape(\Bitrix\Disk\Document\LocalDocumentController::getName()) ?>'
});
</script>
<script type="text/javascript">
BX.message({
	'wd_desktop_disk_is_installed': '<?= (bool)\Bitrix\Disk\Desktop::isDesktopDiskInstall() ?>'
});
</script>

</div>
