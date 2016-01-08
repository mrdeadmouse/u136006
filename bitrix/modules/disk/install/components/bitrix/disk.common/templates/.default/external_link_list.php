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

$folder = \Bitrix\Disk\Folder::loadById($arResult['VARIABLES']['FOLDER_ID']);
?><div class="bx-disk-container posr" id="bx-disk-container">
	<table style="width: 100%;" cellpadding="0" cellspacing="0">
		<tr>
			<td>
				<div class="bx-disk-interface-toolbar-container">
					<?php
					$APPLICATION->IncludeComponent(
						'bitrix:disk.folder.toolbar',
						'',
						array(
							'STORAGE' => $arResult['STORAGE'],
							'FOLDER' => $arResult['STORAGE']->getRootObject(),

							'URL_TO_TRASHCAN_LIST' => CComponentEngine::makePathFromTemplate($arResult['PATH_TO_TRASHCAN_LIST'], array('TRASH_PATH' => '')),
							'URL_TO_FOLDER_LIST' => CComponentEngine::makePathFromTemplate($arResult['PATH_TO_FOLDER_LIST'], array('PATH' => '')),
							'PATH_TO_FOLDER_LIST' => $arResult['PATH_TO_FOLDER_LIST'],
							'PATH_TO_FILE_VIEW' => $arResult['PATH_TO_FILE_VIEW'],
							'PATH_TO_TRASHCAN_LIST' => $arResult['PATH_TO_TRASHCAN_LIST'],
							'PATH_TO_EXTERNAL_LINK_LIST' => $arResult['PATH_TO_EXTERNAL_LINK_LIST'],

							'MODE' => 'external_link_list'
						),
						$component
					);
					?>
				</div>
				<?php
				$APPLICATION->IncludeComponent(
					'bitrix:disk.external.link.list',
					'',
					array_merge(array_intersect_key($arResult, array(
						'STORAGE' => true,
						'PATH_TO_FOLDER_LIST' => true,
						'PATH_TO_FILE_VIEW' => true,
					)), array(
					)),
					$component
				);?>
			</td>
			<td class="bx-disk-table-sidebar-cell" style="">
				<div id="bx_disk_empty_select_section" class="bx-disk-sidebar-section">
					<div class="bx-disk-info-panel">
						<div class="bx-disk-info-panel-relative tac">
							<div class="bx-disk-info-panel-icon-empty"><br></div>
							<div class="bx-disk-info-panel-empty-text">
								<?= Loc::getMessage('DISK_VIEW_SMALL_DETAIL_SIDEBAR') ?>
							</div>
						</div>
					</div>
				</div>

				<div id="disk_info_panel"></div>
			</td>
		</tr>
	</table>
</div>

