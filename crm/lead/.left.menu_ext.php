<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/crm/lead/.left.menu_ext.php");

if (CModule::IncludeModule('crm'))
{
	$CrmPerms = new CCrmPerms($GLOBALS["USER"]->GetID());
	$aMenuLinksExt = array();
	if (!$CrmPerms->HavePerm('LEAD', BX_CRM_PERM_NONE, 'ADD'))
	{
		$aMenuLinksExt[] =
			Array(
				GetMessage("MENU_CRM_ADD_LEAD"),
				"/crm/lead/edit/0/",
				Array(),
				Array(),
				""
			);
	}
	if (!$CrmPerms->HavePerm('LEAD', BX_CRM_PERM_NONE, 'READ'))
	{
		$aMenuLinksExt[] =
			Array(
				GetMessage("MENU_CRM_LEAD_LIST"),
				"/crm/lead/list/",
				Array(),
				Array(),
				""
			);
	}
	if (!$CrmPerms->HavePerm('LEAD', BX_CRM_PERM_NONE, 'ADD'))
	{
		$aMenuLinksExt[] =
			Array(
				GetMessage("MENU_CRM_LEAD_IMPORT"),
				"/crm/lead/import/",
				Array(),
				Array(),
				""
			);
	}

	$aMenuLinks = array_merge($aMenuLinks, $aMenuLinksExt);
}
?>