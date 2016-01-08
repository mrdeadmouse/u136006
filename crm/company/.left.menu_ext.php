<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/crm/company/.left.menu_ext.php");

if (CModule::IncludeModule('crm'))
{
	$CrmPerms = new CCrmPerms($GLOBALS['USER']->GetID());
	$aMenuLinksExt = array();
	if (!$CrmPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE, 'ADD'))
	{
		$aMenuLinksExt[] =
			Array(
				GetMessage("MENU_CRM_COMPANY_ADD"),
				'/crm/company/edit/0/',
				Array(),
				Array(),
				''
			);
	}
	if (!$CrmPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE, 'READ'))
	{
		$aMenuLinksExt[] =
			Array(
				GetMessage("MENU_CRM_COMPANY_LIST"),
				'/crm/company/list/',
				Array(),
				Array(),
				''
			);
	}
	if (!$CrmPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE, 'ADD'))
	{
		$aMenuLinksExt[] =
			Array(
				GetMessage("MENU_CRM_COMPANY_IMPORT"),
				'/crm/company/import/',
				Array(),
				Array(),
				''
			);
	}

	$aMenuLinks = array_merge($aMenuLinks, $aMenuLinksExt);
}

?>