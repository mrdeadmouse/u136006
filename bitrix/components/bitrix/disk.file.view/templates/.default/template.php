<?php
use Bitrix\Main\Localization\Loc;

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

CJSCore::Init(array('viewer', 'disk', 'disk_tabs'));
$sortBpLog = false;
if(isset($_GET['log_sort']))
{
	$sortBpLog = true;
}
?>

<div class="bx-disk-interface-toolbar-container bx-filepage">
<?
$APPLICATION->includeComponent(
	'bitrix:disk.interface.toolbar',
	'',
	array(
		'TOOLBAR_ID' => 'file_toolbar',
		'CLASS_NAME' => 'bx-filepage',
		'BUTTONS' => $arResult['TOOLBAR']['BUTTONS'],
	),
	$component,
	array(
		'HIDE_ICONS' => 'Y'
	)
);
?>
</div>

<div class="bx-disk-filepage-container">
	<div class="bx-disk-tab-container">
		<div id="<?= $component->getComponentId() ?>_header" class="disk-tabs-block">
			<span bx-disk-tab="props" class="disk-tab disk-tab-active"><?= Loc::getMessage('DISK_FILE_VIEW_TAB_PROPERTIES') ?></span>
			<span bx-disk-tab="history" class="disk-tab"><?= Loc::getMessage('DISK_FILE_VIEW_TAB_HISTORY') ?></span>
			<? if($arParams['STATUS_BIZPROC']) { ?><span bx-disk-tab="bp" class="disk-tab"><?= Loc::getMessage('DISK_FILE_VIEW_BIZPROC') ?></span><? } ?>
		</div>

		<div id="<?= $component->getComponentId() ?>_content" class="disk-tab-contents">
			<div bx-disk-tab="props" class="disk-tab-content active">
				<div class="bx-disk-filepage-section">

					<div class="bx-disk-filepage-title"><?= Loc::getMessage('DISK_FILE_VIEW_FILE_PROPERTIES') ?></div>

					<table class="bx-disk-filepage-table">
						<tr>
							<td class="bx-disk-filepage-previewblock">
							<? if($arResult['FILE']['IS_IMAGE']) { ?>
								<div class="bx-shared-preview-images" style="min-width: 255px;">
									<a href="<?= $arResult['FILE']['SHOW_FILE_URL'] ?>" target="_blank"><img src="<?= $arResult['FILE']['SHOW_PREVIEW_URL'] ?>" alt="<?= htmlspecialcharsbx($arResult['FILE']['NAME']) ?>" title="<?= htmlspecialcharsbx($arResult['FILE']['NAME']) ?>"></a>
								</div>
							<?  } else { ?>
								<div class="bx-file-icon-container-big <?= $arResult['FILE']['ICON_CLASS'] ?>">
									<div class="bx-file-icon-cover">
										<div class="bx-file-icon-corner"></div>
										<div class="bx-file-icon-corner-fix"></div>
										<div class="bx-file-icon-images"></div>
									</div>
									<div class="bx-file-icon-label"></div>
								</div>
							<?  } ?>

							</td>
							<td class="bx-disk-filepage-fileinfoblock">
								<div id="bx-disk-filepage-filename" class="bx-disk-filepage-filename"><a href="#" <?= $arResult['FILE']['VIEWER_ATTRIBUTES'] ?>><?= htmlspecialcharsbx($arResult['FILE']['NAME']) ?></a></div>

								<hr class="bx-disk-delimiter">

								<table>
									<tbody>
										<tr>
											<td class="bx-disk-filepage-fileinfo-param"><?= Loc::getMessage('DISK_FILE_VIEW_FILE_OWNER') ?>:</td>
											<td class="bx-disk-filepage-fileinfo-value">
												<a class="bx-disk-filepage-fileinfo-ownner-link" href="<?= $arResult['FILE']['CREATE_USER']['LINK'] ?>">
													<span class="bx-disk-filepage-fileinfo-ownner-avatar" style="background-image: url(<?= $arResult['FILE']['CREATE_USER']['AVA'] ?>);"></span>
													<?= htmlspecialcharsbx($arResult['FILE']['CREATE_USER']['NAME']) ?>
												</a>
											</td>
										</tr>
										<tr>
											<td class="bx-disk-filepage-fileinfo-param"><?= Loc::getMessage('DISK_FILE_VIEW_FILE_SIZE') ?>:</td>
											<td class="bx-disk-filepage-fileinfo-value"><?= CFile::formatSize($arResult['FILE']['SIZE']) ?></td>
										</tr>
										<tr>
											<td class="bx-disk-filepage-fileinfo-param"><?= Loc::getMessage('DISK_FILE_VIEW_FILE_UPDATE_TIME') ?>:</td>
											<td class="bx-disk-filepage-fileinfo-value"><?= $arResult['FILE']['UPDATE_TIME'] ?></td>
										</tr
									</tbody>
								</table>

								<hr class="bx-disk-delimiter">

								<table class="bx-disk-filepage-shared-container">
									<tr>
										<td>
											<div class="bx-disk-sidebar-shared-title"><?= Loc::getMessage('DISK_FILE_VIEW_EXTERNAL_LINK') ?></div>
										</td>
										<td>
											<div class="bx-disk-sidebar-shared-outlink-container" style="padding-bottom: 0;">
												<div class="bx-disk-sidebar-shared-outlink-switcher-container">
													<div class="bx-disk-sidebar-shared-outlink-switcher-track <?= !empty($arResult['EXTERNAL_LINK'])? 'on' : 'off'?>">
														<span id="bx-disk-sidebar-shared-outlink-label"><?= !empty($arResult['EXTERNAL_LINK'])? Loc::getMessage('DISK_FILE_VIEW_EXT_LINK_ON') : Loc::getMessage('DISK_FILE_VIEW_EXT_LINK_OFF') ?></span>
														<div id="bx-disk-sidebar-shared-outlink" class="bx-disk-sidebar-shared-outlink-switcher-point"></div>
													</div>
												</div>
												<div class="bx-disk-sidebar-shared-outlink-input-container">
													<input id="bx-disk-sidebar-shared-outlink-input" class="bx-disk-sidebar-shared-outlink-input" value="<?= $arResult['EXTERNAL_LINK']['LINK'] ?>" type="text">
												</div>
											</div>
										</td>
									</tr>
								</table>

									<a class="bx-disk-btn bx-disk-btn-big bx-disk-btn-green" href="<?= $arResult['FILE']['DOWNLOAD_URL'] ?>"><?= Loc::getMessage('DISK_FILE_VIEW_FILE_DOWNLOAD') ?></a>
									<? if(!empty($arResult['CAN_UPDATE']) && \Bitrix\Disk\TypeFile::isDocument($arResult['FILE']['NAME'])){?><a id="bx-disk-file-edit-btn" class="bx-disk-btn bx-disk-btn-big bx-disk-btn-lightgray" href=""><?= Loc::getMessage('DISK_FILE_VIEW_FILE_EDIT') ?></a><? } ?>
									<? if(!empty($arResult['CAN_UPDATE'])){?><a id="bx-disk-file-upload-btn" class="bx-disk-btn bx-disk-btn-big bx-disk-btn-lightgray" href="javascript:void(0);"><?= Loc::getMessage('DISK_FILE_VIEW_FILE_UPLOAD_VERSION') ?></a><? } ?>
							</td>
						</tr>
					</table>

				</div>
				<? if(!empty($arResult['USE_IN_ENTITIES'])){?>
				<div class="bx-disk-filepage-section">
					<div class="bx-disk-filepage-title"><?= Loc::getMessage('DISK_FILE_VIEW_USAGE') ?></div>

					<table class="bx-disk-filepage-fileusedtable">
					<? foreach($arResult['ENTITIES'] as $entity){?>
						<tr>
							<td class="bx-disk-filepage-task">
								<div class="bx-disk-filepage-used-type"><?
								if(!empty($entity['DETAIL_URL']))
								{
									echo "<a target='_blank' href=\"{$entity['DETAIL_URL']}\">" . htmlspecialcharsbx($entity['TITLE']) . "</a>";
								}
								else
								{
									echo htmlspecialcharsbx($entity['TITLE']);
								}

									?></div>
								<div class="bx-disk-filepage-used-title"><?php echo htmlspecialcharsbx($entity['DESCRIPTION']) ?></div>
							</td>
							<td class="bx-disk-filepage-usedpeople">
								<div class="bx-disk-filepage-used-people-container">
									<div class="bx-disk-filepage-used-people-type"><?php echo Loc::getMessage('DISK_FILE_VIEW_ENTITY_MEMBERS') ?></div>
									<div class="bx-disk-filepage-used-people-list">
										<ul class="bx-disk-filepage-used-people-list-ul">
											<? foreach($entity['MEMBERS'] as $member){?>
												<li>
													<? if(empty($member['LINK'])) {?>
														<span class="bx-disk-filepage-used-people-without-link" alt="<?php echo htmlspecialcharsbx($member['NAME']) ?>" title="<?php echo htmlspecialcharsbx($member['NAME']) ?>">
															<span class="bx-disk-filepage-used-people-avatar" <?= (!empty($member['AVATAR_SRC'])? "style=\"background-image: url({$member['AVATAR_SRC']});\"" : '') ?>></span>
															<?php echo htmlspecialcharsbx($member['NAME']) ?>
														</span>
													<? } else { ?>
														<a alt="<?php echo htmlspecialcharsbx($member['NAME']) ?>" title="<?php echo htmlspecialcharsbx($member['NAME']) ?>" href="<?= $member['LINK'] ?>">
															<span class="bx-disk-filepage-used-people-avatar" <?= (!empty($member['AVATAR_SRC'])? "style=\"background-image: url({$member['AVATAR_SRC']});\"" : '') ?>></span>
															<?php echo htmlspecialcharsbx($member['NAME']) ?>
														</a>
													<? } ?>
												</li>
											<? } ?>
										</ul>
										<div class="clb"></div>
										<div class="bx-disk-filepage-used-people-list-all">
		<!--										<a href="" class="bx-disk-filepage-used-people-list-all-btn">--><?php //echo Loc::getMessage('DISK_FILE_VIEW_ENTITY_MEMBERS_NEXT_PAGE') ?><!-- <span></span></a>-->
										</div>
									</div>
								</div>
							</td>
						</tr>
					<? }?>
					</table>
				</div>
				<? }?>
			</div>
			<div bx-disk-tab="history" class="disk-tab-content bx-disk-filepage-section">
				<div id="bx-disk-version-grid"></div>
			</div>
			<div bx-disk-tab="bp" class="disk-tab-content bx-disk-filepage-section">
				<div id="bx-disk-bp-content"></div>
			</div>
		</div>
	</div>
</div>
<? if($arParams['STATUS_BIZPROC']) { ?>
<div style="display:none;">
	<form id="parametersFormBp">
	<div id="divStartBizProc" class="bx-disk-form-bizproc-start-div">
		<table class="bx-disk-form-bizproc-start-table">
			<col class="bx-disk-col-table-left">
			<col class="bx-disk-col-table-right">
			<? if(!empty($arResult['WORKFLOW_TEMPLATES'])) {
				if($arResult['BIZPROC_PARAMETERS']) {?>
					<tr>
						<td class="bx-disk-form-bizproc-start-td-title" colspan="2">
							<?= Loc::getMessage('DISK_FILE_VIEW_BIZPROC_LABEL_START') ?>
						</td>
					</tr>
					<tr id="errorTr">
						<td id="errorTd" class="bx-disk-form-bizproc-start-td-error" colspan="2">

						</td>
					</tr>
				<? }
				foreach($arResult['WORKFLOW_TEMPLATES'] as $workflowTemplate)
				{
					if(!empty($workflowTemplate['PARAMETERS'])) { ?>
						<tr>
							<td class="bx-disk-form-bizproc-start-td-name-bizproc" colspan="2">
								<?= $workflowTemplate['NAME'] ?>
								<input type="hidden" value="1" name="checkBp" />
								<input type="hidden" value="2" name="autoExecute" />
							</td>
						</tr>
						<?CBPDocument::StartWorkflowParametersShow($workflowTemplate['ID'], $workflowTemplate['PARAMETERS'], 'formAutoloadBizProc', false);
					}else { ?>
						<tr>
							<td class="bx-disk-form-bizproc-start-td-name-bizproc" colspan="2">
								<input type="hidden" value="1" name="checkBp" />
								<input type="hidden" value="2" name="autoExecute" />
							</td>
						</tr>
					<? }
				}
			}
			?>
		</table>
	</div>
	</form>
</div>
<? } ?>
<?$APPLICATION->IncludeComponent(
	'bitrix:disk.file.upload',
	'',
	array(
		'STORAGE' => $arResult['STORAGE'],
		'FILE_ID' => $arResult['FILE']['ID'],
		'CID' => 'FolderList',
		'INPUT_CONTAINER' => 'BX("bx-disk-file-upload-btn")',
		'DROP_ZONE' => 'BX("bx-disk-file-upload-btn")'
	),
	$component,
	array("HIDE_ICONS" => "Y")
);?>
<script type="application/javascript">
BX(function () {
	BX.Disk['FileViewClass_<?= $component->getComponentId() ?>'] = new BX.Disk.FileViewClass({
		webdavEditPath: '<?= $arResult['FILE']['FOLDER_LIST_WEBDAV'] ?>',
		object: {
			id: <?= $arResult['FILE']['ID'] ?>
		},
		tabs: {
			headerContainer: BX('<?= $component->getComponentId() ?>_header'),
			contentContainer: BX('<?= $component->getComponentId() ?>_content')
		}
	});
	BX.viewElementBind(
		'bx-disk-filepage-filename',
		{showTitle: true},
		{attr: 'data-bx-viewer'}
	);
	if('<?= $sortBpLog ?>')
	{
		BX.Disk['FileViewClass_<?= $component->getComponentId() ?>'].fixUrlForSort();
	}
});

BX.message({
	DISK_FILE_VIEW_FF_EXTENSION_NAME: '<?= GetMessageJS('DISK_FILE_VIEW_FF_EXTENSION_NAME') ?>',
	DISK_FILE_VIEW_FF_EXTENSION_UPDATE: '<?= GetMessageJS('DISK_FILE_VIEW_FF_EXTENSION_UPDATE', array("#NAME#" => Loc::getMessage("DISK_FILE_VIEW_FF_EXTENSION_NAME") )) ?>',
	DISK_FILE_VIEW_FF_EXTENSION_INSTALL: '<?= GetMessageJS('DISK_FILE_VIEW_FF_EXTENSION_INSTALL', array("#NAME#" => Loc::getMessage("DISK_FILE_VIEW_FF_EXTENSION_NAME") )) ?>',
	DISK_FILE_VIEW_FF_EXTENSION_TITLE: '<?= GetMessageJS('DISK_FILE_VIEW_FF_EXTENSION_TITLE') ?>',
	DISK_FILE_VIEW_FF_EXTENSION_HELP: '<?= GetMessageJS('DISK_FILE_VIEW_FF_EXTENSION_HELP') ?>',
	DISK_FILE_VIEW_FF_EXTENSION_DISABLE: '<?= GetMessageJS('DISK_FILE_VIEW_FF_EXTENSION_DISABLE') ?>',
	DISK_FILE_VIEW_BTN_INSTALL: '<?= GetMessageJS('DISK_FILE_VIEW_BTN_INSTALL') ?>',
	DISK_FILE_VIEW_BTN_UPDATE: '<?= GetMessageJS('DISK_FILE_VIEW_BTN_UPDATE') ?>',
	DISK_FILE_VIEW_BTN_INSTALL_CANCEL: '<?= GetMessageJS('DISK_FILE_VIEW_BTN_INSTALL_CANCEL') ?>',
	DISK_FILE_VIEW_BTN_OPEN: '<?= GetMessageJS('DISK_FILE_VIEW_BTN_OPEN') ?>',
	DISK_FILE_VIEW_MENU_FF_EXTENSION_TEXT: '<?= GetMessageJS('DISK_FILE_VIEW_MENU_FF_EXTENSION_TEXT') ?>',
	DISK_FILE_VIEW_MENU_FF_EXTENSION_TITLE	: '<?= GetMessageJS('DISK_FILE_VIEW_MENU_FF_EXTENSION_TITLE') ?>',
	DISK_FILE_VIEW_VERSION_DELETE_GROUP_CONFIRM: '<?=GetMessageJS("DISK_FILE_VIEW_VERSION_DELETE_GROUP_CONFIRM")?>',
	DISK_FILE_VIEW_VERSION_DELETE_VERSION_CONFIRM: '<?= GetMessageJS('DISK_FILE_VIEW_VERSION_DELETE_VERSION_CONFIRM') ?>',
	DISK_FILE_VIEW_VERSION_DELETE_VERSION_BUTTON: '<?= GetMessageJS('DISK_FILE_VIEW_VERSION_DELETE_VERSION_BUTTON') ?>',
	DISK_FILE_VIEW_VERSION_DELETE_VERSION_TITLE: '<?= GetMessageJS('DISK_FILE_VIEW_VERSION_DELETE_VERSION_TITLE') ?>',
	DISK_FILE_VIEW_VERSION_DELETE_TITLE: '<?= GetMessageJS('DISK_FILE_VIEW_VERSION_DELETE_TITLE') ?>',
	DISK_FILE_VIEW_VERSION_RESTORE_CONFIRM : '<?= GetMessageJS('DISK_FILE_VIEW_VERSION_RESTORE_CONFIRM') ?>',
	DISK_FILE_VIEW_VERSION_RESTORE_BUTTON : '<?= GetMessageJS('DISK_FILE_VIEW_VERSION_RESTORE_BUTTON') ?>',
	DISK_FILE_VIEW_VERSION_CANCEL_BUTTON : '<?= GetMessageJS('DISK_FILE_VIEW_VERSION_CANCEL_BUTTON') ?>',
	DISK_FILE_VIEW_VERSION_RESTORE_TITLE : '<?= GetMessageJS('DISK_FILE_VIEW_VERSION_RESTORE_TITLE') ?>',
	DISK_FILE_VIEW_BTN_CLOSE: '<?=GetMessageJS("DISK_FILE_VIEW_BTN_CLOSE")?>',
	DISK_FILE_VIEW_COPY_INTERNAL_LINK: '<?=GetMessageJS("DISK_FILE_VIEW_COPY_INTERNAL_LINK")?>',
	DISK_FILE_VIEW_EXT_LINK_ON: '<?=GetMessageJS("DISK_FILE_VIEW_EXT_LINK_ON")?>',
	DISK_FILE_VIEW_EXT_LINK_OFF: '<?=GetMessageJS("DISK_FILE_VIEW_EXT_LINK_OFF")?>',



	disk_revision_api: '<?= (int)\Bitrix\Disk\Configuration::getRevisionApi() ?>',
	disk_document_service: '<?= (string)\Bitrix\Disk\UserConfiguration::getDocumentServiceCode() ?>',
	wd_desktop_disk_is_installed: '<?= (bool)\Bitrix\Disk\Desktop::isDesktopDiskInstall() ?>'
});
</script>
