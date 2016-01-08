<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

use Bitrix\Main\Localization\CultureTable;

//echo "WIZARD_SITE_ID=".WIZARD_SITE_ID." | ";
//echo "WIZARD_SITE_ID=".WIZARD_SITE_DIR." | ";
//echo "WIZARD_SITE_NAME=".WIZARD_SITE_NAME." | ";
//echo "WIZARD_SITE_PATH=".WIZARD_SITE_PATH." | ";
//echo "WIZARD_RELATIVE_PATH=".WIZARD_RELATIVE_PATH." | ";
//echo "WIZARD_ABSOLUTE_PATH=".WIZARD_ABSOLUTE_PATH." | ";
//echo "WIZARD_TEMPLATE_ID=".WIZARD_TEMPLATE_ID." | ";
//echo "WIZARD_TEMPLATE_RELATIVE_PATH=".WIZARD_TEMPLATE_RELATIVE_PATH." | ";
//echo "WIZARD_TEMPLATE_ABSOLUTE_PATH=".WIZARD_TEMPLATE_ABSOLUTE_PATH." | ";
//echo "WIZARD_THEME_ID=".WIZARD_THEME_ID." | ";
//echo "WIZARD_THEME_RELATIVE_PATH=".WIZARD_THEME_RELATIVE_PATH." | ";
//echo "WIZARD_THEME_ABSOLUTE_PATH=".WIZARD_THEME_ABSOLUTE_PATH." | ";
//echo "WIZARD_SERVICE_RELATIVE_PATH=".WIZARD_SERVICE_RELATIVE_PATH." | ";
//echo "WIZARD_SERVICE_ABSOLUTE_PATH=".WIZARD_SERVICE_ABSOLUTE_PATH." | ";
//echo "WIZARD_IS_RERUN=".WIZARD_IS_RERUN." | ";
//die();

if (!defined("WIZARD_SITE_ID"))
	return;

if (!defined("WIZARD_SITE_DIR"))
	return;

if (!WIZARD_FIRST_INSTAL)
{
	$rsSites = CSite::GetByID(WIZARD_SITE_ID);
	if ($arSite = $rsSites->Fetch())
	{
		if ($arSite["DIR"] !== WIZARD_SITE_DIR)
		{
			$arFields = Array(
				"DIR" => WIZARD_SITE_DIR
			);

			$obSite = new CSite();
			$obSite->Update(WIZARD_SITE_ID, $arFields);
		}
	}
}
