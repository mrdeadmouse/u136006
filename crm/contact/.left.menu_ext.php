<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/crm/contact/.left.menu_ext.php");

if (CModule::IncludeModule('crm'))
{
	$CrmPerms = new CCrmPerms($GLOBALS['USER']->GetID());
	$aMenuLinksExt = array();
	if (!$CrmPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'ADD'))
	{
		$aMenuLinksExt[] =
			Array(
				GetMessage("MENU_CRM_ADD_CONTACT"),
				'/crm/contact/edit/0/',
				Array(),
				Array(),
				''
			);
	}
	if (!$CrmPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'READ'))
	{
		$aMenuLinksExt[] =
			Array(
				GetMessage("MENU_CRM_CONTACT_LIST"),
				'/crm/contact/list/',
				Array(),
				Array(),
				''
			);
	}
	if (!$CrmPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'ADD'))
	{
		$aMenuLinksExt[] =
			Array(
				GetMessage("MENU_CRM_CONTACT_IMPORT"),
				'/crm/contact/import/',
				Array(),
				Array(),
				''
			);
	}

	$aMenuLinks = array_merge($aMenuLinks, $aMenuLinksExt);
}

?>