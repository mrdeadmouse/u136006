<?php
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
use Bitrix\Main\Localization\Loc;
?>

<?
CJSCore::Init(array('disk'));
?>
<div class="bx-disk-interface-toolbar-container" style="max-height: 60px; overflow: hidden;">

	<?
	$APPLICATION->IncludeComponent(
		'bitrix:disk.breadcrumbs',
		'',
		array(
			'BREADCRUMBS_ROOT' => $arResult['STORAGE'],
			'BREADCRUMBS' => array(),
		)
	);
	?>

	<div style="clear: both;"></div>
</div>
<div class="bx-disk-interface-filelist">
	<?
	$APPLICATION->IncludeComponent(
		'bitrix:disk.interface.grid',
		'',
		array(
			'MODE' => $arResult['GRID']['MODE'],
			'GRID_ID' => $arResult['GRID']['ID'],
			'HEADERS' => $arResult['GRID']['HEADERS'],
			'SORT' => $arResult['GRID']['SORT'],
			'SORT_VARS' => $arResult['GRID']['SORT_VARS'],
			'ROWS' => $arResult['GRID']['ROWS'],
			'FOOTER' => array_merge(array(
				array(
					'title' => Loc::getMessage('DISK_EXTERNAL_LINK_LIST_LABEL_GRID_TOTAL'),
					'value' => $arResult['GRID']['ROWS_COUNT'],
					'id' => 'bx-disk-total-grid-item',
				),
			), $arResult['GRID']['FOOTER']),
			'EDITABLE' => false,
			'ALLOW_EDIT' => true,
			'ALLOW_INLINE_EDIT' => false,
			'ACTION_ALL_ROWS' => true,
			'ACTIONS' => array(
				'delete' => true,
			),
		),
		$component
	);
	?>
</div>
<script type="application/javascript">
BX(function () {
	BX.Disk.storePathToUser('<?= CUtil::JSUrlEscape($arParams['PATH_TO_USER']) ?>');
	BX.Disk['ExternalLinkListClass_<?= $component->getComponentId() ?>'] = new BX.Disk.ExternalLinkListClass({
		grid: bxGrid_<?= $arResult['GRID']['ID'] ?>,
		gridGroupActionButton: 'folder-list-action-all-btn',
		gridShowTreeButton: 'folder-list-action-show-tree'
	});
});
BX.message({
	DISK_EXTERNAL_LINK_LIST_DELETE_TITLE: '<?=GetMessageJS("DISK_EXTERNAL_LINK_LIST_DELETE_TITLE")?>',
	DISK_EXTERNAL_LINK_LIST_CANCEL_DELETE_BUTTON: '<?=GetMessageJS("DISK_EXTERNAL_LINK_LIST_CANCEL_DELETE_BUTTON")?>',
	DISK_EXTERNAL_LINK_LIST_DELETE_GROUP_CONFIRM: '<?=GetMessageJS("DISK_EXTERNAL_LINK_LIST_DELETE_GROUP_CONFIRM")?>',
	DISK_EXTERNAL_LINK_LIST_SELECTED_OBJECT_1: '<?= GetMessageJS('DISK_EXTERNAL_LINK_LIST_SELECTED_OBJECT_1') ?>',
	DISK_EXTERNAL_LINK_LIST_SELECTED_OBJECT_21: '<?= GetMessageJS('DISK_EXTERNAL_LINK_LIST_SELECTED_OBJECT_21') ?>',
	DISK_EXTERNAL_LINK_LIST_SELECTED_OBJECT_2_4: '<?= GetMessageJS('DISK_EXTERNAL_LINK_LIST_SELECTED_OBJECT_2_4') ?>',
	DISK_EXTERNAL_LINK_LIST_SELECTED_OBJECT_5_20: '<?= GetMessageJS('DISK_EXTERNAL_LINK_LIST_SELECTED_OBJECT_5_20') ?>',
	DISK_EXTERNAL_LINK_LIST_DELETE_BUTTON: '<?=GetMessageJS("DISK_EXTERNAL_LINK_LIST_DELETE_BUTTON")?>'
});


</script>