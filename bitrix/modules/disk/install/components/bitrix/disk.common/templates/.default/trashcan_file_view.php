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

$arResult['PATH_TO_FILE_VIEW'] = $arResult['PATH_TO_TRASHCAN_FILE_VIEW'];
$APPLICATION->IncludeComponent(
	'bitrix:disk.file.view',
	'',
	array_merge(array_intersect_key($arResult, array(
		'STORAGE' => true,
		'PATH_TO_FOLDER_LIST' => true,
		'PATH_TO_FILE_VIEW' => true,
	)), array(
		'STORAGE' => $arResult['STORAGE'],
		'FILE_ID' => $arResult['VARIABLES']['FILE_ID'],
		'RELATIVE_PATH' => $arResult['VARIABLES']['RELATIVE_PATH'],
	)),
	$component
);?>