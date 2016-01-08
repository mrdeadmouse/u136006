<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$APPLICATION->SetAdditionalCSS("/bitrix/components/bitrix/voximplant.main/templates/.default/telephony.css");

$APPLICATION->IncludeComponent("bitrix:voximplant.lines.default", "", array());

$APPLICATION->IncludeComponent("bitrix:voximplant.interface.config", "", array());

$APPLICATION->IncludeComponent("bitrix:voximplant.blacklist", "", array());

$APPLICATION->IncludeComponent("bitrix:voximplant.documents", "", array());
?>

