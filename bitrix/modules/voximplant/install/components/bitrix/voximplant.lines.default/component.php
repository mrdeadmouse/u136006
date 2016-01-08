<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/**
 * @var $arParams array
 * @var $arResult array
 * @var $this CBitrixComponent
 * @var $APPLICATION CMain
 * @var $USER CUser
 */

if (!CModule::IncludeModule('voximplant'))
	return;

if (!(isset($arParams['TEMPLATE_HIDE']) && $arParams['TEMPLATE_HIDE'] == 'Y'))
	$this->IncludeComponentTemplate();

return $arResult;
?>