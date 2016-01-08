<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();


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
				"Справочники",
				"/crm/configs/status/",
				Array(),
				Array(),
				""
			),
			Array(
				"Валюты",
				"/crm/configs/currency/",
				Array(),
				Array(),
				""
			),
			Array(
				"Налоги",
				"/crm/configs/tax/",
				Array(),
				Array(),
				""
			),
			Array(
				"Местоположения",
				"/crm/configs/locations/",
				Array(),
				Array(),
				""
			),
			Array(
				"Способы оплаты",
				"/crm/configs/ps/",
				Array(),
				Array(),
				""
			),
			Array(
				"Права доступа",
				"/crm/configs/perms/",
				Array(),
				Array(),
				""
			),
			Array(
				"Бизнес-процессы",
				"/crm/configs/bp/",
				Array(),
				Array(),
				"CModule::IncludeModule('bizproc') && CModule::IncludeModule('bizprocdesigner')"
			),
			Array(
				"Пользовательские поля",
				"/crm/configs/fields/",
				Array(),
				Array(),
				""
			),
			Array(
				"Прочее",
				"/crm/configs/config/",
				Array(),
				Array(),
				"CModule::IncludeModule('subscribe')"
			),
			Array(
				"Интеграция c почтой",
				"/crm/configs/sendsave/",
				Array(),
				Array(),
				"CModule::IncludeModule('mail')"
			),
			Array(
				"Интернет-магазины",
				"/crm/configs/external_sale/",
				Array(),
				Array(),
				""
			),
			Array(
				"Интеграция с \"1С:Предприятие\"",
				"/crm/configs/exch1c/",
				Array(),
				Array(),
				""
			),
			Array(
				"Справка",
				"/crm/configs/info/",
				Array(),
				Array(),
				""
			)
		);
	}
	if ($CrmPerms->IsAccessEnabled())
	{
		$aMenuLinksExt[] = Array(
			"Почтовые шаблоны",
			"/crm/configs/mailtemplate/",
			Array(),
			Array(),
			""
		);
	}

	$aMenuLinks = array_merge($aMenuLinks, $aMenuLinksExt);
}

?>