<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/crm/.left.menu_ext.php");

if (CModule::IncludeModule('crm'))
{
	$CrmPerms = new CCrmPerms($GLOBALS["USER"]->GetID());
	$arMenuCrm = Array();
	
	if (SITE_TEMPLATE_ID === "bitrix24")
		$arMenuCrm[] = Array(
			GetMessage("MENU_CRM_DESKTOP"),
			"/crm/",
			Array(),
			Array(),
			""
		);
	$arMenuCrm[] = Array(
		GetMessage("MENU_CRM_STREAM"),
		"/crm/stream/",
		Array(),
		Array(),
		""
	);
	$arMenuCrm[] = Array(
		GetMessage("MENU_CRM_ACTIVITY"),
		"/crm/activity/",
		Array(),
		Array(),
		""
	);
	if (!$CrmPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE))
	{
		$arMenuCrm[] = Array(
			GetMessage("MENU_CRM_CONTACT"),
			"/crm/contact/",
			Array(),
			Array(),
			""
		);
	}
	if (!$CrmPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE))
	{
		$arMenuCrm[] = Array(
			GetMessage("MENU_CRM_COMPANY"),
			"/crm/company/",
			Array(),
			Array(),
			""
		);
	}
	if (!$CrmPerms->HavePerm('DEAL', BX_CRM_PERM_NONE))
	{
		$arMenuCrm[] = Array(
			GetMessage("MENU_CRM_DEAL"),
			"/crm/deal/",
			Array(),
			Array(),
			""
		);
	}
	if (!$CrmPerms->HavePerm('INVOICE', BX_CRM_PERM_NONE))
	{
		$arMenuCrm[] = Array(
			GetMessage("MENU_CRM_INVOICE"),
			"/crm/invoice/",
			Array(), 
			Array(), 
		"" 
		);
	}
	if (!$CrmPerms->HavePerm('QUOTE', BX_CRM_PERM_NONE))
	{
		$arMenuCrm[] = Array(
			GetMessage("MENU_CRM_QUOTE"),
			"/crm/quote/",
			Array(),
			Array(),
			""
		);
	}
	if (!$CrmPerms->HavePerm('LEAD', BX_CRM_PERM_NONE))
	{
		$arMenuCrm[] = Array(
			GetMessage("MENU_CRM_LEAD"),
			"/crm/lead/",
			Array(),
			Array(),
			""
		);
	}

	$arMenuCrm[] = Array(
		GetMessage("MENU_CRM_PRODUCT"),
		"/crm/product/",
		Array(),
		Array(),
		""
	);

	if (!$CrmPerms->HavePerm('LEAD', BX_CRM_PERM_NONE) || !$CrmPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE) ||
		!$CrmPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE) || !$CrmPerms->HavePerm('DEAL', BX_CRM_PERM_NONE))
	{
		$arMenuCrm[] = Array(
			GetMessage("MENU_CRM_HISTORY"),
			"/crm/events/", 
			Array(), 
			Array(), 
			"" 
		);
	}
	
	if (!$CrmPerms->HavePerm('LEAD', BX_CRM_PERM_NONE) || !$CrmPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE) ||
		!$CrmPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE) || !$CrmPerms->HavePerm('DEAL', BX_CRM_PERM_NONE))
	{
		if (IsModuleInstalled('report') || SITE_TEMPLATE_ID !== "bitrix24")
			$arMenuCrm[] = Array(
				GetMessage("MENU_CRM_REPORT"),
				CModule::IncludeModule('report') ? "/crm/reports/report/" : "/crm/reports/",
				Array(),
				Array(),
				""
			);
		
		if (SITE_TEMPLATE_ID === "bitrix24")
			$arMenuCrm[] = Array(
				GetMessage("MENU_CRM_FUNNEL"),
				"/crm/reports/",
				Array(),
				Array(),
				""
			);
	}
	if ($CrmPerms->IsAccessEnabled())
	{
		$arMenuCrm[] = Array(
			GetMessage("MENU_CRM_SETTINGS"),
			"/crm/configs/",
			Array(),
			Array(),
			""
		);
	}
	$aMenuLinks = array_merge($arMenuCrm, $aMenuLinks);
}

?>