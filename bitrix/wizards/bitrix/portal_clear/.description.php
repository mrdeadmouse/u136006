<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arWizardDescription = Array(
	"NAME" => GetMessage("PORTAL_WIZARD_NAME"), 
	"DESCRIPTION" => GetMessage("PORTAL_WIZARD_DESC"), 
	"VERSION" => "1.0.0",
	"START_TYPE" => "WINDOW",
	"TEMPLATES" => Array(
		Array("SCRIPT" => "scripts/template.php", "CLASS" => "WizardTemplate")
	),

	"STEPS" => Array("WelcomeStep", "DeleteStep", "FinishStep"),
);

?>