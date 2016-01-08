<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
global $APPLICATION;

if(isset($arParams['~ENABLE_TACTILE_INTERFACE']) && strtoupper($arParams['~ENABLE_TACTILE_INTERFACE']) === 'Y')
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.interface.form.tactile',
		'',
		array(
			'IS_NEW' => isset($arParams['~IS_NEW']) ? $arParams['~IS_NEW'] : 'Y',
			'MODE'=> 'EDIT',
			'TITLE' => isset($arParams['~TITLE']) ? $arParams['~TITLE'] : '',
			'FORM_ID' => $arParams['~FORM_ID'],
			'DATA' => $arParams['~DATA'],
			'TABS' => $arParams['~TABS'],
			'BUTTONS' => $arParams['~BUTTONS'],
			'FIELD_SETS' => isset($arParams['~FIELD_SETS']) ? $arParams['~FIELD_SETS'] : array(),
			'ENABLE_USER_FIELD_CREATION' => isset($arParams['~ENABLE_USER_FIELD_CREATION']) ? $arParams['~ENABLE_USER_FIELD_CREATION'] : 'Y',
			'USER_FIELD_ENTITY_ID' => isset($arParams['~USER_FIELD_ENTITY_ID']) ? $arParams['~USER_FIELD_ENTITY_ID'] : '',
			'SHOW_SETTINGS' => 'Y'
		),
		$component, array('HIDE_ICONS' => 'Y')
	);
}
else
{
	$APPLICATION->IncludeComponent(
		'bitrix:main.interface.form',
		'crm.edit',
		array(
			'FORM_ID' => $arParams['~FORM_ID'],
			'THEME_GRID_ID' => $arParams['~GRID_ID'],
			'TABS' => $arParams['~TABS'],
			'EMPHASIZED_HEADERS' => $arParams['~EMPHASIZED_HEADERS'],
			'FIELD_SETS' => isset($arParams['~FIELD_SETS']) ? $arParams['~FIELD_SETS'] : array(),
			'BUTTONS' => $arParams['~BUTTONS'],
			'DATA' => $arParams['~DATA'],
			'TITLE' => isset($arParams['~TITLE']) ? $arParams['~TITLE'] : '',
			'IS_NEW' => isset($arParams['~IS_NEW']) ? $arParams['~IS_NEW'] : 'Y',
			'USER_FIELD_ENTITY_ID' => isset($arParams['~USER_FIELD_ENTITY_ID']) ? $arParams['~USER_FIELD_ENTITY_ID'] : '',
			'SHOW_SETTINGS' => isset($arParams['~SHOW_SETTINGS']) ? $arParams['~SHOW_SETTINGS'] : 'Y'
		),
		$component, array('HIDE_ICONS' => 'Y')
	);
}
?>