<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if (WIZARD_IS_RERUN)
	return;

if(!CModule::IncludeModule("search"))
	return;

$obCustomRank = new CSearchCustomRank;
$ID = $obCustomRank->Add(array(
	"SITE_ID" => WIZARD_SITE_ID,
	"MODULE_ID" => "intranet",
	"RANK" => 1,
));

?>