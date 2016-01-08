<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
if ($arParams["ALLOW_CREATE_GROUP"] == "Y")
{
	$popupName = randString(6);
	$APPLICATION->IncludeComponent(
		"bitrix:socialnetwork.group_create.popup",
		".default",
		array(
			"NAME" => $popupName,
			"PATH_TO_GROUP_EDIT" => (strlen($arParams["PATH_TO_GROUP_CREATE"]) > 0 
				? htmlspecialcharsback($arParams["PATH_TO_GROUP_CREATE"])
				: ""
			)
		),
		null,
		array("HIDE_ICONS" => "Y")
	);

	$GLOBALS["INTRANET_TOOLBAR"]->AddButton(array(
		'HREF' => $arParams["~HREF"],
		"TEXT" => GetMessage('SONET_C36_T_CREATE'),
		'ICON' => 'create',
		'SORT' => 1000,
		'ONCLICK' => "if (BX.SGCP) { BX.SGCP.ShowForm('create', '".$popupName."', event); } else { return false; }"
	));
}
?>