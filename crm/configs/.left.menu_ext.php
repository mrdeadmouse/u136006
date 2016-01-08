<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/crm/configs/.left.menu_ext.php");

if (CModule::IncludeModule('crm'))
{
	GLOBAL $USER;
	$USER_ID = $USER->GetID();
	$CrmPerms = new CCrmPerms($USER_ID);
	$aMenuLinksExt = array();

	if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_NONE))
	{
		$aMenuLinksExt = array(
			Array(
				GetMessage("CRM_MENU_STATUS"),
				"/crm/configs/status/",
				Array(),
				Array(),
				""
			),
			Array(
				GetMessage("CRM_MENU_CURRENCY"),
				"/crm/configs/currency/",
				Array(),
				Array(),
				""
			),
			Array(
				GetMessage("CRM_MENU_TAX"),
				"/crm/configs/tax/",
				Array(),
				Array(),
				""
			),
			Array(
				GetMessage("CRM_MENU_LOCATIONS"),
				"/crm/configs/locations/",
				Array(),
				Array(),
				""
			),
			Array(
				GetMessage("CRM_MENU_PS"),
				"/crm/configs/ps/",
				Array(),
				Array(),
				""
			),
			Array(
				GetMessage("CRM_MENU_PERMS"),
				"/crm/configs/perms/",
				Array(),
				Array(),
				""
			),
			Array(
				GetMessage("CRM_MENU_BP"),
				"/crm/configs/bp/",
				Array(),
				Array(),
				"CModule::IncludeModule('bizproc') && CModule::IncludeModule('bizprocdesigner')"
			),
			Array(
				GetMessage("CRM_MENU_FIELDS"),
				"/crm/configs/fields/",
				Array(),
				Array(),
				""
			),
			Array(
				GetMessage("CRM_MENU_PRODUCT_PROPS"),
				"/crm/configs/productprops/",
				Array(),
				Array(),
				""
			),
			Array(
				GetMessage("CRM_MENU_CONFIG"),
				"/crm/configs/config/",
				Array(),
				Array(),
				"CModule::IncludeModule('subscribe')"
			),
			Array(
				GetMessage("CRM_MENU_SENDSAVE"),
				"/crm/configs/sendsave/",
				Array(),
				Array(),
				"CModule::IncludeModule('mail')"
			),
			Array(
				GetMessage("CRM_MENU_SALE"),
				"/crm/configs/external_sale/",
				Array(),
				Array(),
				""
			),
			Array(
			   GetMessage("CRM_MENU_MEASURE"),
			   "/crm/configs/measure/",
			   Array(),
			   Array(),
			   ""
			)
		);
		if (LANGUAGE_ID == "ru")
		{
			$aMenuLinksExt[] = Array(
				GetMessage("CRM_MENU_EXCH1C"),
				"/crm/configs/exch1c/",
				Array(),
				Array(),
				""
			);
			$aMenuLinksExt[] = Array(
				GetMessage("CRM_MENU_INFO"),
				"/crm/configs/info/",
				Array(),
				Array(),
				""
			);
		}
	}
	if ($CrmPerms->IsAccessEnabled())
	{
		$aMenuLinksExt[] = Array(
			GetMessage("CRM_MENU_MAILTEMPLATE"),
			"/crm/configs/mailtemplate/",
			Array(),
			Array(),
			""
		);
	}

	$aMenuLinks = array_merge($aMenuLinks, $aMenuLinksExt);
}

?>