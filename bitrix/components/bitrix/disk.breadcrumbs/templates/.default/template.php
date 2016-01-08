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
<?php
array_unshift($arResult['BREADCRUMBS'], $arResult['BREADCRUMBS_ROOT']);
$showedItems = array_splice($arResult['BREADCRUMBS'], -3, 3);
$collapsedItems = $arResult['BREADCRUMBS'];
$preparedToJs = array();
foreach($collapsedItems as $crumb)
{
	$preparedToJs[] = array(
		'title' => $crumb['NAME'],
		'text' => $crumb['NAME'],
		'href' => $component->encodeUrn($crumb['LINK']),
	);
}
unset($item);
?>
<div id="<?= $arResult['BREADCRUMBS_ID'] ?>" class="bx-disk-interface-bread-crumbs ovh">
		<? if(!empty($collapsedItems)){?><span class="bx-disk-interface-bread-crumbs-item-container-arrow" id="root_dots_<?= $arResult['BREADCRUMBS_ID'] ?>"></span><? } ?>
		<? foreach($showedItems as $i => $crumb){ ?>
		<span class="bx-disk-interface-bread-crumbs-item-container" data-objectParentPath="<?= $component->encodeUrn($crumb['LINK']) ?>" data-isRoot="<?= $crumb['ID'] == $arResult['BREADCRUMBS_ROOT']['ID'] ?>" data-objectId="<?= $crumb['ID'] ?>" data-objectName="<?= $crumb['NAME'] ?>">
			<? if(count($showedItems) != ($i+1)){?>
			<span class="popup-control">
				<a href="" class="popup-current">
					<span class="icon-arrow"></span>
				</a>
			</span>
			<? } ?>
			<a href="<?= $component->encodeUrn($crumb['LINK']) ?>" class="bx-disk-interface-bread-crumbs-item-link" title="<?= $crumb['NAME'] ?>" alt="<?= $crumb['NAME'] ?>">
				<span class="bx-disk-interface-bread-crumbs-item-current"><?= $crumb['NAME'] ?></span>
			</a>
			<span class="clb"></span>
		</span>
		<? } ?>
</div>

<script type="application/javascript">
	BX.ready(function () {
		new BX.Disk.BreadcrumbsClass({
			containerId: '<?= $arResult['BREADCRUMBS_ID'] ?>',
			collapsedCrumbs: <?= \Bitrix\Main\Web\Json::encode($preparedToJs) ?>,
			showOnlyDeleted: <?= (int)$arResult['SHOW_ONLY_DELETED'] ?>
		});
	});
</script>