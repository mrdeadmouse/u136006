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

$APPLICATION->ShowAjaxHead();

Loc::loadMessages(__DIR__ . '/template.php');
?>

<div>
	<?
	$APPLICATION->IncludeComponent(
		'bitrix:disk.interface.grid',
		'',
		array(
			'GRID_ID' => $arResult['VERSION_GRID']['ID'],
			'CURRENT_URL' => $APPLICATION->GetCurPageParam("", array("action")),
			'HEADERS' => $arResult['VERSION_GRID']['HEADERS'],
			'SORT' => $arResult['VERSION_GRID']['SORT'],
			'SORT_VARS' => $arResult['VERSION_GRID']['SORT_VARS'],
			'ROWS' => $arResult['VERSION_GRID']['ROWS'],
			'FOOTER' => array(array(
				'title' => Loc::getMessage('DISK_FILE_VIEW_VERSION_LABEL_GRID_TOTAL'),
				'value' => $arResult['VERSION_GRID']['ROWS_COUNT'],
				'id' => 'bx-disk-total-grid-item',
			)),
			'EDITABLE' => false,
		),
		$component
	);
	?>

</div>

<script type="application/javascript">
	BX(function () {

		BX.Disk['FileViewClass_<?= $component->getComponentId() ?>'] = new BX.Disk.FileViewClass({
			withoutEventBinding: true,
			object: {
				id: <?= $arResult['FILE']['ID'] ?>
			}
		});

		BX.viewElementBind(
			'<?=$arResult['VERSION_GRID']['ID']?>',
			{showTitle: true},
			{attr: 'data-bx-viewer'}
		);
	});
</script>