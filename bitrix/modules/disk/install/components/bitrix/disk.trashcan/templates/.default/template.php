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
?>

<?
CJSCore::Init(array('viewer',));
?>

<div class="bx-disk-interface-toolbar-container" style="max-height: 60px; overflow: hidden;">
	<?
	$APPLICATION->IncludeComponent(
		'bitrix:disk.breadcrumbs',
		'',
		array(
			'BREADCRUMBS_ROOT' => $arResult['BREADCRUMBS_ROOT'],
			'BREADCRUMBS' => $arResult['BREADCRUMBS'],
			'SHOW_ONLY_DELETED' => true,
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
		'GRID_ID' => $arResult['GRID']['ID'],
		'HEADERS' => $arResult['GRID']['HEADERS'],
		'SORT' => $arResult['GRID']['SORT'],
		'SORT_VARS' => $arResult['GRID']['SORT_VARS'],
		'ROWS' => $arResult['GRID']['ROWS'],
		'FOOTER' => array_merge(array(
			array(
				'title' => \Bitrix\Main\Localization\Loc::getMessage('DISK_TRASHCAN_LABEL_GRID_TOTAL'),
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
	BX.Disk['TrashCanClass_<?= $component->getComponentId() ?>'] = new BX.Disk.TrashCanClass({
		rootObject : {
			id: <?= $arResult['FOLDER']['ID'] ?>
		},
		grid: bxGrid_<?= $arResult['GRID']['ID'] ?>,
		gridGroupActionButton: 'folder-list-action-all-btn',
		gridShowTreeButton: 'folder-list-action-show-tree'
	});
});
	
BX.message({
	DISK_TRASHCAN_TRASH_EMPTY_FOLDER_CONFIRM: '<?=GetMessageJS("DISK_TRASHCAN_TRASH_EMPTY_FOLDER_CONFIRM")?>',
	DISK_TRASHCAN_TRASH_EMPTY_BUTTON: '<?=GetMessageJS("DISK_TRASHCAN_TRASH_EMPTY_BUTTON")?>',
	DISK_TRASHCAN_TRASH_DELETE_DESTROY_FILE_CONFIRM: '<?=GetMessageJS("DISK_TRASHCAN_TRASH_DELETE_DESTROY_FILE_CONFIRM")?>',
	DISK_TRASHCAN_TRASH_DELETE_DESTROY_FOLDER_CONFIRM: '<?=GetMessageJS("DISK_TRASHCAN_TRASH_DELETE_DESTROY_FOLDER_CONFIRM")?>',
	DISK_TRASHCAN_TRASH_RESTORE_FILE_CONFIRM: '<?=GetMessageJS("DISK_TRASHCAN_TRASH_RESTORE_FILE_CONFIRM")?>',
	DISK_TRASHCAN_TRASH_RESTORE_FOLDER_CONFIRM: '<?=GetMessageJS("DISK_TRASHCAN_TRASH_RESTORE_FOLDER_CONFIRM")?>',
	DISK_TRASHCAN_TRASH_DELETE_FOLDER_CONFIRM: '<?=GetMessageJS("DISK_TRASHCAN_TRASH_DELETE_FOLDER_CONFIRM")?>',
	DISK_TRASHCAN_TRASH_DELETE_FILE_CONFIRM: '<?=GetMessageJS("DISK_TRASHCAN_TRASH_DELETE_FILE_CONFIRM")?>',
	DISK_TRASHCAN_TRASH_DELETE_GROUP_CONFIRM: '<?=GetMessageJS("DISK_TRASHCAN_TRASH_DELETE_GROUP_CONFIRM")?>',
	DISK_TRASHCAN_TRASH_RESTORE_GROUP_CONFIRM: '<?=GetMessageJS("DISK_TRASHCAN_TRASH_RESTORE_GROUP_CONFIRM")?>',
	DISK_TRASHCAN_TRASH_RESTORE_BUTTON: '<?=GetMessageJS("DISK_TRASHCAN_TRASH_RESTORE_BUTTON")?>',
	DISK_TRASHCAN_TRASH_DESTROY_BUTTON: '<?=GetMessageJS("DISK_TRASHCAN_TRASH_DESTROY_BUTTON")?>',
	DISK_TRASHCAN_TRASH_DELETE_BUTTON: '<?=GetMessageJS("DISK_TRASHCAN_TRASH_DELETE_BUTTON")?>',
	DISK_TRASHCAN_TRASH_CANCEL_DELETE_BUTTON: '<?=GetMessageJS("DISK_TRASHCAN_TRASH_CANCEL_DELETE_BUTTON")?>',
	DISK_TRASHCAN_TRASH_CANCEL_STOP_BUTTON: '<?=GetMessageJS("DISK_TRASHCAN_TRASH_CANCEL_STOP_BUTTON")?>',
	DISK_TRASHCAN_TRASH_DELETE_TITLE: '<?=GetMessageJS("DISK_TRASHCAN_TRASH_DELETE_TITLE")?>',
	DISK_TRASHCAN_TRASH_RESTORE_TITLE: '<?=GetMessageJS("DISK_TRASHCAN_TRASH_RESTORE_TITLE")?>',
	DISK_TRASHCAN_TRASH_EMPTY_TITLE: '<?=GetMessageJS("DISK_TRASHCAN_TRASH_EMPTY_TITLE")?>',
	DISK_TRASHCAN_TRASH_COUNT_ELEMENTS: '<?=GetMessageJS("DISK_TRASHCAN_TRASH_COUNT_ELEMENTS")?>',
	DISK_TRASHCAN_TITLE_MODAL_MOVE_TO: '<?=GetMessageJS("DISK_TRASHCAN_TITLE_MODAL_MOVE_TO")?>',
	DISK_TRASHCAN_TITLE_MODAL_COPY_TO: '<?=GetMessageJS("DISK_TRASHCAN_TITLE_MODAL_COPY_TO")?>',
	DISK_TRASHCAN_TITLE_MODAL_MANY_COPY_TO: '<?=GetMessageJS("DISK_TRASHCAN_TITLE_MODAL_MANY_COPY_TO")?>',
	DISK_TRASHCAN_TITLE_SIDEBAR_MANY_RESTORE_BUTTON: '<?=GetMessageJS("DISK_TRASHCAN_TITLE_SIDEBAR_MANY_RESTORE_BUTTON")?>',
	DISK_TRASHCAN_TITLE_SIDEBAR_MANY_DELETE_BUTTON: '<?=GetMessageJS("DISK_TRASHCAN_TITLE_SIDEBAR_MANY_DELETE_BUTTON")?>',
	DISK_TRASHCAN_TITLE_MODAL_MOVE_TO_BUTTON: '<?=GetMessageJS("DISK_TRASHCAN_TITLE_MODAL_MOVE_TO_BUTTON")?>',
	DISK_TRASHCAN_TITLE_MODAL_COPY_TO_BUTTON: '<?=GetMessageJS("DISK_TRASHCAN_TITLE_MODAL_COPY_TO_BUTTON")?>',
	DISK_TRASHCAN_TITLE_GRID_TOOLBAR_COPY_BUTTON: '<?=GetMessageJS("DISK_TRASHCAN_TITLE_GRID_TOOLBAR_COPY_BUTTON")?>',
	DISK_TRASHCAN_TITLE_GRID_TOOLBAR_MOVE_BUTTON: '<?=GetMessageJS("DISK_TRASHCAN_TITLE_GRID_TOOLBAR_MOVE_BUTTON")?>',
	DISK_TRASHCAN_SELECTED_OBJECT_1: '<?= GetMessageJS('DISK_TRASHCAN_SELECTED_OBJECT_1') ?>',
	DISK_TRASHCAN_SELECTED_OBJECT_21: '<?= GetMessageJS('DISK_TRASHCAN_SELECTED_OBJECT_21') ?>',
	DISK_TRASHCAN_SELECTED_OBJECT_2_4: '<?= GetMessageJS('DISK_TRASHCAN_SELECTED_OBJECT_2_4') ?>',
	DISK_TRASHCAN_SELECTED_OBJECT_5_20: '<?= GetMessageJS('DISK_TRASHCAN_SELECTED_OBJECT_5_20') ?>'
});	

</script>


