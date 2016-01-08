<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$APPLICATION->SetAdditionalCSS("/bitrix/components/bitrix/voximplant.main/templates/.default/telephony.css");

/*$APPLICATION->IncludeComponent(
	'bitrix:main.interface.filter',
	'',
	array(
		'GRID_ID'=>$arResult["GRID_ID"],
		'FILTER'=> $arResult["FILTER"],
		//"FILTER_PRESETS"=>$arParams["~FILTER_PRESETS"],
		'FILTER_ROWS'=>array("PHONE_NUMBER"=>1, "CALL_DURATION" => 1, "CALL_START_DATE" => 1),//$arParams['~FILTER_ROWS'],
		//'FILTER_FIELDS'=>$arResult["FILTER"],
		//'OPTIONS'=>$arParams['~OPTIONS'],
		//'FILTER_INFO'=>$arResult['FILTER_INFO'],
		//'RENDER_FILTER_INTO_VIEW'=>isset($arParams['~RENDER_FILTER_INTO_VIEW']) ? $arParams['~RENDER_FILTER_INTO_VIEW'] : '',
		//'HIDE_FILTER'=>"Y"//isset($arParams['~HIDE_FILTER']) ? $arParams['~HIDE_FILTER'] : false
	),
	$component,
	array('HIDE_ICONS'=>true)
);*/

$buttons = array(
	array(
		"TEXT"=>GetMessage("TEL_STAT_BACK"),
		"TITLE"=>GetMessage("TEL_STAT_BACK_TITLE"),
		"LINK"=> CVoxImplantMain::GetPublicFolder(),
		"ICON"=>"go-back",
	)
);

if ($_GET['USER_ID'] || $_GET['CODE'] )
{
	$buttons[] = array(
		"TEXT"=>GetMessage("TEL_STAT_FILTER_CANCEL"),
		"TITLE"=>GetMessage("TEL_STAT_FILTER_CANCEL_TITLE"),
		"LINK"=> CVoxImplantMain::GetPublicFolder().'detail.php',
		"ICON"=>"btn-unlock",
	);
}

?>
<?$APPLICATION->IncludeComponent(
	"bitrix:main.interface.toolbar",
	"",
	array("BUTTONS"=> $buttons),
	$component
);?>
<div class="tel-stat-grid-wrap">
<?$APPLICATION->IncludeComponent(
	"bitrix:main.interface.grid",
	"",
	array(
		"GRID_ID"=>$arResult["GRID_ID"],
		"HEADERS"=>$arResult["HEADERS"],
		"ROWS"=>$arResult["ELEMENTS_ROWS"],
		//"ACTIONS"=>$arActions,
		"NAV_OBJECT"=>$arResult["NAV_OBJECT"],
		//"SORT"=>$arResult["SORT"],
	//	"FILTER"=>$arResult["FILTER"],
		"FOOTER" => array(
			array("title" => GetMessage("CT_BLL_SELECTED"), "value" => $arResult["ROWS_COUNT"])
		),
		"AJAX_MODE" => "Y",
	),
	$component, array("HIDE_ICONS" => "Y")
);?>
</div>