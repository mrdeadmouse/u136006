<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if(strlen(rtrim($_SERVER["DOCUMENT_ROOT"], "/")) <= strlen(rtrim(WIZARD_SITE_PATH, '/')))
	CopyDirFiles(
		WIZARD_ABSOLUTE_PATH."/site/public/".LANGUAGE_ID."/bitrix/",
		WIZARD_SITE_PATH."/bitrix/",
		$rewrite = false,
		$recursive = true,
		$exclude = "urlrewrite.php"
	);
?>