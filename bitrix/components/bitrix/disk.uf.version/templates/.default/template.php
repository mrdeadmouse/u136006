<?php
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
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

Asset::getInstance()->addCss('/bitrix/components/bitrix/disk.uf.file/templates/.default/style.css');
include_once(str_replace(array("\\", "//"), "/", __DIR__."/messages.php"));
?>

<?
foreach($arResult['VERSIONS'] as $version)
{
?>
<div id="disk-attach-<?=$version['ID'] . $arResult['UID']?>" class="feed-com-files diskuf-files-entity">
	<div class="feed-com-files-title"><?= Loc::getMessage('DISK_UF_VERSION_HISTORY_FILE', array('#NUMBER#' => $version['GLOBAL_CONTENT_VERSION'])) ?></div>
	<div class="feed-com-files-cont">
		<div class="feed-com-file-wrap">
				<span class="feed-con-file-icon feed-file-icon-<?=htmlspecialcharsbx($version['EXTENSION'])?>"></span>
				<span class="feed-com-file-name-wrap">
					<a <?= ($version['FROM_EXTERNAL_SYSTEM'] && $version['CAN_UPDATE'])? 'style="color:#d9930a;"' : '' ?> target="_blank" href="<?=htmlspecialcharsbx($version['DOWNLOAD_URL'])?>"<?
						?> id="disk-attach-<?=$version['ID'] . '-' . $version['GLOBAL_CONTENT_VERSION'] ?>"<?
						?> class="feed-com-file-name" <?
						?> title="<?=htmlspecialcharsbx($version['NAME'])?>" <?
						?> data-bx-baseElementId="disk-attach-<?=$version['ID']?>"<?=
							$version['ATTRIBUTES_FOR_VIEWER']
						?> alt="<?=htmlspecialcharsbx($version['NAME'])?>"<?
					?>><?=htmlspecialcharsbx($version['NAME'])?><?
					?></a>
					<span class="feed-com-file-size"><?=$version['SIZE']?></span>
					<script type="text/javascript">
						var WDpreButtons_<?= $version['ID'] . '_' . $version['GLOBAL_CONTENT_VERSION'] ?> = [
							{text : BX.message('JS_CORE_VIEWER_VIEW_ELEMENT'), className : "bx-viewer-popup-item item-view", href : "#", onclick: function(e){
								BX.fireEvent(BX.findPreviousSibling(BX(this.bindElement), function(node){ return BX.type.isElementNode(node) && (node.getAttribute('data-bx-viewer'));}, true), 'click');
								BX.PopupMenu.currentItem.popupWindow.close();
								return BX.PreventDefault(e);
							}},
							<? if($version['EDITABLE'] && $version['CAN_UPDATE']){ ?>
							{text : BX.message('JS_CORE_VIEWER_EDIT'), className : "bx-viewer-popup-item item-edit", href : "#", onclick: function(e){
								BX.fireEvent(BX.findPreviousSibling(BX(this.bindElement), function(node){ return BX.type.isElementNode(node) && (node.getAttribute('data-bx-viewer'));}, true), 'click');top.BX.CViewer.objNowInShow.runActionByCurrentElement('forceEdit', {obElementViewer: top.BX.CViewer.objNowInShow});
								BX.PopupMenu.currentItem.popupWindow.close();
								return BX.PreventDefault(e);
							}},
							<? } ?>
							{text : BX.message('JS_CORE_VIEWER_SAVE_TO_OWN_FILES'), className : "bx-viewer-popup-item item-b24", href : "#", onclick: function(e){
								top.BX.CViewer.getWindowCopyToDisk({link: "<?=CUtil::JSUrlEscape($version['COPY_TO_ME_URL'])?>", selfViewer: false, showEdit: <?= (\Bitrix\Disk\Document\DocumentHandler::isEditable($version['NAME'])? 'true' : 'false')  ?>, title: "<?= CUtil::JSEscape($version["NAME"]) ?>"});
								BX.PopupMenu.currentItem.popupWindow.close();
								return BX.PreventDefault(e);
							}},
							<? if($version['FROM_EXTERNAL_SYSTEM'] && $version['CAN_UPDATE']){ ?>
							{text : '<?= GetMessageJS('DISK_UF_FILE_RUN_FILE_IMPORT') ?>', className : "bx-viewer-popup-item item-toload", href : "#", onclick: function(e){
								top.BX.Disk.UF.runImport({id: <?= $version['ID'] ?>, name: '<?= CUtil::JSEscape($version['NAME']) ?>'});
								BX.PopupMenu.currentItem.popupWindow.close();
								return BX.PreventDefault(e);
							}},
							<? } ?>
							{text : BX.message('JS_CORE_VIEWER_DOWNLOAD_TO_PC'), className : "bx-viewer-popup-item item-download", href : "<?=$version["DOWNLOAD_URL"]?>", onclick: function(e){BX.PopupMenu.currentItem.popupWindow.close();}},
							{text : '<?= GetMessageJS('DISK_UF_FILE_SETTINGS_DOCS') ?>', className : "bx-viewer-popup-item item-setting", href : "#", onclick: function(e){
								var viewer = new BX.CViewer({});
								viewer.openWindowForSelectDocumentService({viewInUf: true});
								BX.PopupMenu.currentItem.popupWindow.close();
								return BX.PreventDefault(e);
							}}
						];
					</script>

					<span class="feed-con-file-changes-link feed-con-file-changes-link-more" onclick="return DiskActionFileMenu('<?= $version['ID'] . '-' . $version['GLOBAL_CONTENT_VERSION'] ?>', this, WDpreButtons_<?= $version['ID'] . '_' . $version['GLOBAL_CONTENT_VERSION'] ?>); return false;"><?= Loc::getMessage('DISK_UF_VERSION_MORE_ACTIONS') ?></span>
				</span>
			</div>
	</div>
</div>
<? } ?>
