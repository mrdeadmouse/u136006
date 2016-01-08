<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if(!CModule::IncludeModule("lists") || !CModule::IncludeModule("iblock"))
	return;

CLists::SetPermission('lists', array(1, WIZARD_PORTAL_ADMINISTRATION_GROUP));
CLists::SetPermission('bitrix_processes', array(1, WIZARD_PORTAL_ADMINISTRATION_GROUP));

COption::SetOptionString("lists", "turnProcessesOn", "Y");
COption::SetOptionString("lists", "livefeed_url", "/services/processes/");
COption::SetOptionString("lists", "livefeed_iblock_type_id", "bitrix_processes");

\Bitrix\Lists\Importer::installProcesses(LANGUAGE_ID);

COption::SetOptionString("lists", "socnet_iblock_type_id", "lists_socnet");
CLists::EnableSocnet(true);
?>