<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if (!defined("WIZARD_TEMPLATE_ID") || WIZARD_TEMPLATE_ID === "bitrix24")
	return;

$templateDir = BX_PERSONAL_ROOT."/templates/".WIZARD_TEMPLATE_ID."_".WIZARD_THEME_ID;

CopyDirFiles(
	WIZARD_THEME_ABSOLUTE_PATH,
	$_SERVER["DOCUMENT_ROOT"].$templateDir,
	$rewrite = true, 
	$recursive = true,
	$delete_after_copy = false,
	$exclude = "description.php"
);

/*
if (WIZARD_SITE_LOGO > 0)
	$success = CWizardUtil::CopyFile(WIZARD_SITE_LOGO, $templateDir."/images/logo.gif", false);
else
	$success = @unlink($_SERVER["DOCUMENT_ROOT"].$templateDir."/images/logo.gif");
*/


COption::SetOptionString("main", "wizard_".WIZARD_TEMPLATE_ID."_theme_id", WIZARD_THEME_ID);

//Color scheme for main.interface.grid/form

if (WIZARD_TEMPLATE_ID=="light")
	CUserOptions::SetOption("main.interface", "global", array("theme"=> "lightgrey"), true);
else
	CUserOptions::SetOption("main.interface", "global", array("theme"=>WIZARD_THEME_ID), true);
?>