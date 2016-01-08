<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if (WIZARD_IS_RERUN)
	return;

if(!CModule::IncludeModule("statistic"))
	return;

$APPLICATION->SetGroupRight("statistic", WIZARD_PORTAL_ADMINISTRATION_GROUP, "W");
?>