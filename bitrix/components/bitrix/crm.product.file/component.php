<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponent $this */
/** @var array $arParams */
/** @var array $arResult */
/** @var string $componentName */
/** @var string $componentPath */
/** @var string $componentTemplate */
/** @var string $parentComponentName */
/** @var string $parentComponentPath */
/** @var string $parentComponentTemplate */
$this->setFrameMode(false);

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage("CRM_PRODUCT_FILE_CRM_MODULE_NOT_INSTALLED"));
	return;
}

if (!CModule::IncludeModule('iblock'))
{
	ShowError(GetMessage("CRM_PRODUCT_FILE_IBLOCK_MODULE_NOT_INSTALLED"));
	return;
}

$arParams['PATH_TO_PRODUCT_FILE'] = CrmCheckPath(
	'PATH_TO_PRODUCT_FILE', $arParams['PATH_TO_PRODUCT_FILE'],
	$APPLICATION->GetCurPage().'?product_id=#product_id#&field_id=#field_id#&file_id=#file_id#&file'
);

$IBLOCK_ID = is_array($arParams["~CATALOG_ID"])? 0: intval($arParams["~CATALOG_ID"]);
$ELEMENT_ID = is_array($arParams["~PRODUCT_ID"])? 0: intval($arParams["~PRODUCT_ID"]);
/*$SECTION_ID = is_array($arParams["~SECTION_ID"])? 0: intval($arParams["~SECTION_ID"]);*/

/*$lists_perm = CListPermissions::CheckAccess(
	$USER,
	$arParams["~IBLOCK_TYPE_ID"],
	$IBLOCK_ID,
	$arParams["~SOCNET_GROUP_ID"]
);*/

if(!CCrmSecurityHelper::IsAuthorized())
{
	ShowError(GetMessage('CRM_PRODUCT_FILE_PERMISSION_DENIED'));
	return;
}

$CrmPerms = CCrmPerms::GetCurrentUserPermissions();
if (!(CCrmPerms::IsAccessEnabled($CrmPerms) && $CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ')))
{
	ShowError(GetMessage('CRM_PRODUCT_FILE_PERMISSION_DENIED'));
	return;
}

if (!CCrmProductFile::CheckFieldId($IBLOCK_ID, $arParams["FIELD_ID"]))
{
	ShowError(GetMessage("CRM_PRODUCT_FILE_UNKNOWN_ERROR"));
	return;
}
/*else if ($lists_perm < 0)
{
	switch ($lists_perm)
	{
		case CListPermissions::WRONG_IBLOCK_TYPE:
			ShowError(GetMessage("CRM_PRODUCT_FILE_WRONG_IBLOCK_TYPE"));
			return;
		case CListPermissions::WRONG_IBLOCK:
			ShowError(GetMessage("CRM_PRODUCT_FILE_WRONG_IBLOCK"));
			return;
		case CListPermissions::LISTS_FOR_SONET_GROUP_DISABLED:
			ShowError(GetMessage("CRM_PRODUCT_FILE_LISTS_FOR_SONET_GROUP_DISABLED"));
			return;
		default:
			ShowError(GetMessage("CRM_PRODUCT_FILE_UNKNOWN_ERROR"));
			return;
	}
}
else if (
	$ELEMENT_ID > 0
	&& $lists_perm <= CListPermissions::CAN_READ
	&& !CIBlockElementRights::UserHasRightTo($IBLOCK_ID, $ELEMENT_ID, "element_read")
)
{
	ShowError(GetMessage("CRM_PRODUCT_FILE_ACCESS_DENIED"));
	return;
}
else if (
	$SECTION_ID > 0
	&& $lists_perm <= CListPermissions::CAN_READ
	&& !CIBlockSectionRights::UserHasRightTo($IBLOCK_ID, $SECTION_ID, "section_read")
)
{
	ShowError(GetMessage("CRM_PRODUCT_FILE_ACCESS_DENIED"));
	return;
}*/

$arIBlock = CIBlock::GetArrayByID(intval($arParams["~CATALOG_ID"]));

$arResult["FILES"] = array();
$arResult["ELEMENT"] = false;
/*$arResult["SECTION"] = false;*/

if ($ELEMENT_ID > 0)
{
	$rsElement = CIBlockElement::GetList(
		array(),
		array(
			"CATALOG_ID" => $arIBlock["ID"],
			"=ID" => $ELEMENT_ID,
			"CHECK_PERMISSIONS" => "N",
		),
		false,
		false,
		array("ID", $arParams["FIELD_ID"])
	);
	while ($ar = $rsElement->GetNext())
	{
		if (isset($ar[$arParams["FIELD_ID"]]))
		{
			$arResult["FILES"][] = $ar[$arParams["FIELD_ID"]];
		}
		else if (isset($ar[$arParams["FIELD_ID"]."_VALUE"]))
		{
			if (is_array($ar[$arParams["FIELD_ID"]."_VALUE"]))
				$arResult["FILES"] = array_merge($arResult["FILES"], $ar[$arParams["FIELD_ID"]."_VALUE"]);
			else
				$arResult["FILES"][] = $ar[$arParams["FIELD_ID"]."_VALUE"];
		}
		$arResult["ELEMENT"] = $ar;
	}
}
/*else if ($SECTION_ID > 0)
{
	$rsSection = CIBlockSection::GetList(
		array(),
		array(
			"CATALOG_ID" => $arIBlock["ID"],
			"=ID" => $SECTION_ID,
			"GLOBAL_ACTIVE"=>"Y",
			"CHECK_PERMISSIONS" => "N",
		),
		false,
		array("ID", $arParams["FIELD_ID"])
	);
	while ($ar = $rsSection->GetNext())
	{
		if (isset($ar[$arParams["FIELD_ID"]]))
		{
			$arResult["FILES"][] = $ar[$arParams["FIELD_ID"]];
		}
		$arResult["SECTION"] = $ar;
	}
}*/

if (!in_array($arParams["FILE_ID"], $arResult["FILES"]))
{
	ShowError(GetMessage("CRM_PRODUCT_FILE_WRONG_FILE"));
}
else
{
	$arFile = CFile::GetFileArray($arParams["FILE_ID"]);
	if (is_array($arFile))
	{
		$bForceDownload = isset($_REQUEST["download"]) && $_REQUEST["download"] === "y";

		CFile::ViewByUser($arParams["FILE_ID"], array(
			"content_type" => $arFile["CONTENT_TYPE"],
			"force_download" => $bForceDownload,
		));
	}
}
?>
