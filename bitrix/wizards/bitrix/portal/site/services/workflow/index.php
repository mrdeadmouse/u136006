<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if (WIZARD_IS_RERUN)
	return;

if(!CModule::IncludeModule("workflow"))
	return;

$APPLICATION->SetGroupRight("workflow", WIZARD_PORTAL_ADMINISTRATION_GROUP, "W");
COption::SetOptionString("workflow", "WORKFLOW_ADMIN_GROUP_ID", WIZARD_PORTAL_ADMINISTRATION_GROUP);
?>