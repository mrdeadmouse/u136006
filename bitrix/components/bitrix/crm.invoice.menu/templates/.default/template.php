<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
global $APPLICATION;

if (!empty($arResult['BUTTONS']))
{
	$type = $arParams['TYPE'];
	$APPLICATION->IncludeComponent(
		'bitrix:crm.interface.toolbar',
		$type === 'list' ?  '' : 'type2',
		array(
			'TOOLBAR_ID' => 'crm_invoice_toolbar',
			'BUTTONS' => $arResult['BUTTONS']
		),
		$component,
		array('HIDE_ICONS' => 'Y')
	);
}

