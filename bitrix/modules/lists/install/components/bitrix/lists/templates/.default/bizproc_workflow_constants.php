<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$link = str_replace(
	array("#list_id#"),
	array($arResult["VARIABLES"]["list_id"]),
	$arResult["FOLDER"].$arResult["URL_TEMPLATES"]["bizproc_workflow_admin"]
);
$arButtons = array(
	array(
		"TEXT"=>GetMessage("CT_BL_LIST_PROCESSES"),
		"TITLE"=>GetMessage("CT_BL_LIST_PROCESSES"),
		"LINK"=>$link,
		"ICON"=>"btn-list",
	),
);
$APPLICATION->IncludeComponent(
	"bitrix:main.interface.toolbar",
	"",
	array(
		"BUTTONS" => $arButtons
	),
	$component
);

?>
	<div style="background: #eef2f4; width: 600px; padding: 5px 0px 0px 20px;">
		<?
		$APPLICATION->IncludeComponent('bitrix:bizproc.workflow.setconstants', '',
			array('ID' => $arResult['VARIABLES']['ID'], 'POPUP' => 'N'),
			$component,
			array("HIDE_ICONS" => "Y")
		);
		?>
	</div>
