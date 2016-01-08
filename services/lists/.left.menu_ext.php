<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

global $APPLICATION;
$aMenuLinksExt=$APPLICATION->IncludeComponent("bitrix:lists.menu", "", array(
	"IBLOCK_TYPE_ID" => "lists",
	"IS_SEF" => "Y",
	"SEF_BASE_URL" => "/services/lists/",
	"SEF_LIST_BASE_URL" => "#list_id#/",
	"SEF_LIST_URL" => "#list_id#/view/#section_id#/",
	"CACHE_TYPE" => "A",
	"CACHE_TIME" => "36000000"
	),
	false,
	array(
	"HIDE_ICONS" => "N"
	)
);

if(count($aMenuLinksExt)>5)
	$aMenuLinksExt = array_slice($aMenuLinksExt, 0, 5);

$aMenuLinks = $aMenuLinksExt;
?>