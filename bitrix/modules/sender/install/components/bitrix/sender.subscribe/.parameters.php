<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentParameters = array(
	"GROUPS" => array(
	),
	"PARAMETERS" => array(
		"AJAX_MODE" => array(),
		"USE_PERSONALIZATION" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("CP_BSF_USE_PERSONALIZATION"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y",
		),
		"CONFIRMATION" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("CP_BSF_USE_CONFIRMATION"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y",
		),
		"SET_TITLE" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("CP_BSF_SET_TITLE"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y",
		),
		"SHOW_HIDDEN" => array(
			"PARENT" => "DATA_SOURCE",
			"NAME" => GetMessage("CP_BSF_SHOW_HIDDEN"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "N",
			),
		"CACHE_TIME" => array("DEFAULT"=>3600),
	),
);
?>
