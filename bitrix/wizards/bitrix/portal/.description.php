<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if(!defined("WIZARD_DEFAULT_SITE_ID") && !empty($_REQUEST["wizardSiteID"])) 
	define("WIZARD_DEFAULT_SITE_ID", $_REQUEST["wizardSiteID"]); 

define("NON_INTRANET_EDITION", false);

$arWizardDescription = Array(
	"NAME" => GetMessage("PORTAL_WIZARD_NAME"), 
	"DESCRIPTION" => GetMessage("PORTAL_WIZARD_DESC"), 
	"VERSION" => "1.0.0",
	"START_TYPE" => "WINDOW",
	"TEMPLATES" => Array(
		Array("SCRIPT" => "scripts/template.php", "CLASS" => "WizardTemplate")
	),
	"PARENT" => "wizard_sol",
	/*"TEMPLATES" => Array(
		Array("SCRIPT" => "wizard_sol")
	),        */

	"STEPS" => Array("WelcomeStep", "SelectTemplateStep", "SelectThemeStep", "SiteSettingsStep", "LDAPSettingsStep", "LDAPGroupsStep", "DataInstallStep" ,"FinishStep"),
);

if ($_SERVER["PHP_SELF"] != "/index.php" && !NON_INTRANET_EDITION)
{
	$arWizardDescription["STEPS"] = Array("WelcomeStep", "SelectTemplateStep", "SelectThemeStep", "PortalFeaturesSettingsStep", "SocialFeaturesSettingsStep", "CommunicationsFeaturesSettingsStep", "EnterpriseFeaturesSettingsStep", "HoldingFeaturesSettingsStep", "SiteSettingsStep", "LDAPSettingsStep", "LDAPGroupsStep", "DataInstallStep" ,"FinishStep");
}
?>