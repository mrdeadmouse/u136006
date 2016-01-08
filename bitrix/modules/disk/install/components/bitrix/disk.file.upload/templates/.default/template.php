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
/** @var \Bitrix\Disk\Internals\BaseComponent $component */
/** @var \Bitrix\Disk\Folder $arParams["FOLDER"] */
if (!isset($arParams["INPUT_CONTAINER"]))
	return;
include_once(__DIR__."/message.php");
CJSCore::Init(array('uploader'));
//$arParams["INPUT_CONTAINER"];
//$arParams["CID"];
//$arParams["DROPZONE"];
?>
<script>
BX.ready(function() {
	BX.DiskUpload.initialize({
		bp: '<?= $arParams['STATUS_START_BIZPROC'] ?>',
		bpParameters: '<?= $arParams['BIZPROC_PARAMETERS'] ?>',
		bpParametersRequired: <?= \Bitrix\Main\Web\Json::encode($arParams['BIZPROC_PARAMETERS_REQUIRED']) ?>,
		storageId: <?= $arParams['STORAGE_ID'] ?>,
		CID : '<?=CUtil::JSEscape($arParams["CID"])?>',
		<?if (!empty($arParams["FILE_ID"])): ?>targetFileId : '<?=CUtil::JSEscape($arParams["FILE_ID"])?>',<?
		else: ?>targetFolderId : '<?=CUtil::JSEscape(($arParams["FOLDER"] ? $arParams["FOLDER"]->getId() : ''))?>',<? endif; ?>
		inputContainer : <?=$arParams["~INPUT_CONTAINER"]?>,
		urlUpload : '/bitrix/components/bitrix/disk.file.upload/ajax.php',
		dropZone : <?=(isset($arParams["~DROPZONE"]) ? $arParams["~DROPZONE"] : 'null')?>});
});
</script>
<?