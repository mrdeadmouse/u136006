<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
global $APPLICATION;
$APPLICATION->IncludeComponent(
	'bitrix:main.interface.form',
	'tactile',
	array(
		'FORM_ID' => $arParams['~FORM_ID'],
		'THEME_GRID_ID' => $arParams['~GRID_ID'],
		'TABS' => $arParams['~TABS'],
		'MODE' => isset($arParams['~MODE']) ? $arParams['~MODE'] : 'EDIT',
		'FIELD_SETS' => isset($arParams['~FIELD_SETS']) ? $arParams['~FIELD_SETS'] : array(),
		'BUTTONS' => $arParams['~BUTTONS'],
		'DATA' => $arParams['~DATA'],
		'TITLE' => isset($arParams['~TITLE']) ? $arParams['~TITLE'] : '',
		'IS_NEW' => isset($arParams['~IS_NEW']) ? $arParams['~IS_NEW'] : 'Y',
		'ENABLE_USER_FIELD_CREATION' => isset($arParams['~ENABLE_USER_FIELD_CREATION']) ? $arParams['~ENABLE_USER_FIELD_CREATION'] : 'Y',
		'ENABLE_SECTION_CREATION' => isset($arParams['~ENABLE_SECTION_CREATION']) ? $arParams['~ENABLE_SECTION_CREATION'] : 'Y',
		'USER_FIELD_ENTITY_ID' => isset($arParams['~USER_FIELD_ENTITY_ID']) ? $arParams['~USER_FIELD_ENTITY_ID'] : '',
		'QUICK_PANEL' => isset($arParams['~QUICK_PANEL']) ? $arParams['~QUICK_PANEL'] : null,
		'SHOW_SETTINGS' => isset($arParams['~SHOW_SETTINGS']) ? $arParams['~SHOW_SETTINGS'] : 'Y'
	),
	$component, array('HIDE_ICONS' => 'Y')
);
?>