<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if(!CModule::IncludeModule("iblock") || WIZARD_INSTALL_DEMO_DATA === false)
	return;

$rsIBlock = CIBlock::GetList(array(), array("CODE" => "honour", "TYPE" => "structure", "SITE_ID" => WIZARD_SITE_ID));
if (!$arIBlock = $rsIBlock->Fetch())
	return;

$honour_IBLOCK_ID = $arIBlock["ID"];
if (LANGUAGE_ID == "en")
	$login = "j.chatto";
elseif (LANGUAGE_ID == "de")
	$login = "EmmerichR";
else
	$login = "i.zinin";

$dbrUsers = CUser::GetList($o, $b, Array("LOGIN"=>$login));
if($arUser = $dbrUsers->Fetch())
{
	$from = time() - (30 * 24 * 3600);
	$to = time() + (100 * 24 * 3600);

	$arElementFields = Array(
    	"ACTIVE" => "Y",
	    "IBLOCK_ID" => $honour_IBLOCK_ID,
    	"ACTIVE_FROM" => ConvertTimeStamp($from),
    	"ACTIVE_TO" => ConvertTimeStamp($to),
    	"NAME" => GetMessage("W_IB_HONOUR_NAME"),
    	"PREVIEW_TEXT" => GetMessage("W_IB_HONOUR_PREV"),
    	"PREVIEW_TEXT_TYPE" => 'text',
    	"PROPERTY_VALUES" => Array(
            "USER" => $arUser["ID"]
        )
	);

	$el = new CIBlockElement();
	$el->Add($arElementFields);
}


if (LANGUAGE_ID == "en")
	$login = "a.wheatley";
elseif (LANGUAGE_ID == "de")
	$login = "PapenbergD";
else
	$login = "a.rusakov";

$dbrUsers = CUser::GetList($o, $b, Array("LOGIN"=>$login));
if($arUser = $dbrUsers->Fetch())
{
	$from = time() - (25 * 24 * 3600);
	$to = time() + (95 * 24 * 3600);

	$arElementFields = Array(
    	"ACTIVE" => "Y",
	    "IBLOCK_ID" => $honour_IBLOCK_ID,
    	"ACTIVE_FROM" => ConvertTimeStamp($from),
    	"ACTIVE_TO" => ConvertTimeStamp($to),
    	"NAME" => GetMessage("W_IB_HONOUR1"),
    	"PREVIEW_TEXT" => GetMessage("W_IB_HONOUR1_DESC"),
    	"PREVIEW_TEXT_TYPE" => 'text',
    	"PROPERTY_VALUES" => Array(
            "USER" => $arUser["ID"]
        )
	);

	$el = new CIBlockElement();
	$el->Add($arElementFields);
}

if (LANGUAGE_ID == "en")
	$login = "e.holman";
elseif (LANGUAGE_ID == "de")
	$login = "ClassenF";
else
	$login = "e.astafev";

$dbrUsers = CUser::GetList($o, $b, Array("LOGIN"=>$login));
if($arUser = $dbrUsers->Fetch())
{
	$from = time() - (28 * 24 * 3600);
	$to = time() + (98 * 24 * 3600);

	$arElementFields = Array(
    	"ACTIVE" => "Y",
	    "IBLOCK_ID" => $honour_IBLOCK_ID,
    	"ACTIVE_FROM" => ConvertTimeStamp($from),
    	"ACTIVE_TO" => ConvertTimeStamp($to),
    	"NAME" => GetMessage("W_IB_HONOUR2"),
    	"PREVIEW_TEXT" => GetMessage("W_IB_HONOUR2_DESC"),
    	"PREVIEW_TEXT_TYPE" => 'text',
    	"PROPERTY_VALUES" => Array(
            "USER" => $arUser["ID"]
        )
	);

	$el = new CIBlockElement();
	$el->Add($arElementFields);
}
?>
