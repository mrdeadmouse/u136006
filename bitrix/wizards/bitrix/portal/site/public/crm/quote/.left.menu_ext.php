<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/crm/quote/.left.menu_ext.php");

if (CModule::IncludeModule('crm'))
{
	$CrmPerms = new CCrmPerms($GLOBALS['USER']->GetID());
	$aMenuLinksExt = array();
	if (!$CrmPerms->HavePerm('QUOTE', BX_CRM_PERM_NONE, 'ADD'))
	{
		$aMenuLinksExt[] =
			Array(
				GetMessage("MENU_CRM_ADD_QUOTE"),
				'#SITE_DIR#crm/quote/edit/0/',
				Array(),
				Array(),
				''
			);
	}
	if (!$CrmPerms->HavePerm('QUOTE', BX_CRM_PERM_NONE, 'READ'))
	{
		$aMenuLinksExt[] =
			Array(
				GetMessage("MENU_CRM_QUOTE_LIST"),
				'#SITE_DIR#crm/quote/list/',
				Array(),
				Array(),
				''
			);
	}

	$aMenuLinks = array_merge($aMenuLinks, $aMenuLinksExt);
}

?>