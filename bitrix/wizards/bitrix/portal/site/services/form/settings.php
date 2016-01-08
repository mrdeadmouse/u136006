<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

COption::SetOptionString("form", "SIMPLE", "N");

/*if (WIZARD_IS_RERUN)
	return;
*/
if(!CModule::IncludeModule("form"))
	return;

$arMenuItem = 	Array(
		GetMessage("FSMENUT"), 
		WIZARD_SITE_DIR . "services/requests/", 
		Array(), 
		Array(), 
		"" 
	);

WizardServices::AddMenuItem(WIZARD_SITE_DIR . "services/.left.menu.php", $arMenuItem, WIZARD_SITE_ID, 4);
WizardServices::CopyFile(WIZARD_SERVICE_RELATIVE_PATH."/public/".LANGUAGE_ID."/requests", WIZARD_SITE_DIR . "services/requests");

//WizardServices::CopyFile(WIZARD_SERVICE_RELATIVE_PATH."/public/".LANGUAGE_ID."/resume.php", WIZARD_SITE_DIR . "about/resume.php");

CWizardUtil::ReplaceMacrosRecursive(WIZARD_SITE_PATH . "services/", Array("SITE_DIR" => WIZARD_SITE_DIR));
CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."services/requests/index.php", Array("SITE_ID" => WIZARD_SITE_ID));

COption::SetOptionString("form", "FORM_DEFAULT_PERMISSION", 10);
COption::SetOptionString("form", "GROUP_DEFAULT_RIGHT", "D");


$APPLICATION->SetGroupRight("form", WIZARD_PERSONNEL_DEPARTMENT_GROUP, "W");
$APPLICATION->SetGroupRight("form", WIZARD_PORTAL_ADMINISTRATION_GROUP, "W");

?>