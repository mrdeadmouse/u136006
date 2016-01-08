<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$component = $this->__component;

$tbButtons = array(
	array(
		'TEXT' => GetMessage('CRM_CONFIGS_EXCH1C_LINK_TEXT'),
		'TITLE' => GetMessage('CRM_CONFIGS_EXCH1C_LINK_TITLE'),
		'LINK' => $arResult['BACK_URL'],
		'ICON' => 'go-back'
	)
);
if (!empty($tbButtons))
{
	$APPLICATION->IncludeComponent(
		'bitrix:main.interface.toolbar',
		'',
		array(
			'BUTTONS' => $tbButtons
		),
		$component,
		array(
			'HIDE_ICONS' => 'Y'
		)
	);
}

$arTabs[] = array(
	'id' => 'tab_catalog_import',
	'name' => GetMessage('CRM_TAB_CATALOG_IMPORT'),
	'title' => GetMessage('CRM_TAB_CATALOG_IMPORT_TITLE'),
	'icon' => '',
	'fields' => $arResult['FIELDS']['tab_catalog_import']
);
$arTabs[] = array(
	'id' => 'tab_catalog_export',
	'name' => GetMessage('CRM_TAB_CATALOG_EXPORT'),
	'title' => GetMessage('CRM_TAB_CATALOG_EXPORT_TITLE'),
	'icon' => '',
	'fields' => $arResult['FIELDS']['tab_catalog_export']
);

$customButtons = '<input type="submit" name="save" value="'.htmlspecialcharsbx(GetMessage("CRM_BUTTON_SAVE")).'" title="'.htmlspecialcharsbx(GetMessage("CRM_BUTTON_SAVE_TITLE")).'" />';
$customButtons .= '<input type="button" name="cancel" value="'.htmlspecialcharsbx(GetMessage("CRM_BUTTON_CANCEL")).'" title="'.htmlspecialcharsbx(GetMessage("CRM_BUTTON_CANCEL_TITLE")).'" onclick="window.location=\''.htmlspecialcharsbx($arResult['BACK_URL']).'\'" />';
?>

<div class="crm-config-exch1c">
<?
$APPLICATION->IncludeComponent(
	'bitrix:main.interface.form',
	'',
	array(
		'FORM_ID' => $arResult['FORM_ID'],
		'TABS' => $arTabs,
		'BUTTONS' => array(
			'standard_buttons' =>  false,
			'custom_html' => $customButtons
			//'back_url' => $arResult['BACK_URL']
		),
		'DATA' => $arResult['ELEMENT'],
		'SHOW_SETTINGS' => 'N'
	),
	$component, array('HIDE_ICONS' => 'Y')
);
?>
</div>
