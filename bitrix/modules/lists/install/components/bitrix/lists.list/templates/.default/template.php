<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

if($arResult["PROCESSES"] && $arResult["USE_COMMENTS"])
	\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/bizproc/tools.js');

$arToolbar = array();

if($arResult["CAN_ADD_ELEMENT"])
{
	$arToolbar[] = array(
		"TEXT"=>$arResult["IBLOCK"]["ELEMENT_ADD"],
		"TITLE"=>GetMessage("CT_BLL_TOOLBAR_ADD_ELEMENT_TITLE"),
		"LINK"=>$arResult["LIST_NEW_ELEMENT_URL"],
		"ICON"=>"btn-add-element",
	);
}

if($arResult["CAN_EDIT_SECTIONS"])
{
	$arToolbar[] = array(
		"TEXT"=>GetMessage("CT_BLL_TOOLBAR_EDIT_SECTION"),
		"TITLE"=>GetMessage("CT_BLL_TOOLBAR_EDIT_SECTION_TITLE"),
		"LINK"=>$arResult["LIST_SECTION_URL"],
		"ICON"=>"btn-edit-sections",
	);
}

if($arParams["CAN_EDIT"])
{
	if(count($arToolbar))
		$arToolbar[] = array("SEPARATOR" => true);

	if($arResult["IBLOCK"]["BIZPROC"] == "Y" && $arParams["CAN_EDIT_BIZPROC"])
	{
		$arToolbar[] = array(
			"TEXT"=>GetMessage("CT_BLL_TOOLBAR_BIZPROC"),
			"TITLE"=>GetMessage("CT_BLL_TOOLBAR_BIZPROC_TITLE"),
			"LINK"=>$arResult["BIZPROC_WORKFLOW_ADMIN_URL"],
			"ICON"=>"btn-list-bizproc",
		);
	}

	if($arParams["IBLOCK_TYPE_ID"] == COption::GetOptionString("lists", "livefeed_iblock_type_id"))
	{
		$text = GetMessage("CT_BLL_TOOLBAR_PROCESS");
		$title = GetMessage("CT_BLL_TOOLBAR_PROCESS_TITLE");
	}
	else
	{
		$text = GetMessage("CT_BLL_TOOLBAR_LIST");
		$title = GetMessage("CT_BLL_TOOLBAR_LIST_TITLE");
	}

	$arToolbar[] = array(
		"TEXT"=>$text,
		"TITLE"=>$title,
		"LINK"=>$arResult["LIST_EDIT_URL"],
		"ICON"=>"btn-edit-list",
	);

	$arToolbar[] = array(
		"TEXT" => GetMessage("CT_BLL_EXPORT_EXCEL"),
		"TITLE" => GetMessage("CT_BLL_EXPORT_EXCEL_TITLE"),
		"LINK" => CHTTP::urlAddParams((strpos($APPLICATION->GetCurPageParam(), "?") == false) ?
			$arResult["EXPORT_EXCEL_URL"] : $arResult["EXPORT_EXCEL_URL"].substr($APPLICATION->GetCurPageParam(),
			strpos($APPLICATION->GetCurPageParam(), "?")), array("ncc" => "y")),
		"ICON" => "btn-list-excel",
	);
}

if (IsModuleInstalled('intranet') && CBXFeatures::IsFeatureEnabled('intranet_sharepoint'))
{
	if ($arIcons = $APPLICATION->IncludeComponent('bitrix:sharepoint.link', '', array(
		'IBLOCK_ID' => $arParams['IBLOCK_ID'],
		'OUTPUT' => 'N',
	), null, array('HIDE_ICONS' => 'Y')))
	{
		if (count($arIcons['LINKS']) > 0)
		{
			$arMenu = array();
			foreach ($arIcons['LINKS'] as $link)
			{
				$arMenu[] = array(
					'TEXT' => $link['TEXT'],
					'ONCLICK' => $link['ONCLICK'],
					'ICONCLASS' => $link['ICON']
				);
			}

			$arToolbar[] = array(
				'TEXT' => 'SharePoint',
				'ICON' => 'bx-sharepoint',
				'MENU' => $arMenu,
			);
		}
	}
}

if(count($arToolbar))
{
	$APPLICATION->IncludeComponent(
		"bitrix:main.interface.toolbar",
		"",
		array(
			"BUTTONS"=>$arToolbar,
		),
		$component, array("HIDE_ICONS" => "Y")
	);
}

$arActions = array();

if(!empty($arResult["ELEMENTS_CAN_DELETE"]))
{
	$arActions["delete"] = true;
}

if($arResult["ELEMENTS_CAN_EDIT"] > 0)
{
	$sections = '&nbsp;<select name="section_to_move" size="1">';
	foreach($arResult["LIST_SECTIONS"] as $id => $name)
	{
		$sections .= '<option value="'.$id.'">'.$name.'</option>';
	}
	$sections .= '</select>&nbsp;';

	$arActions["list"] = array("section" => GetMessage("CT_BLL_MOVE_TO_SECTION"));
	$arActions["custom_html"] = $sections;
}

if(empty($arActions))
	$arActions = false;

foreach($arResult["FILTER"] as $i => $arFilter)
{
	if($arFilter["type"] == "E"):
		$FIELD_ID = $arFilter["id"];
		$arField = $arFilter["value"];
		$values = CIBlockPropertyElementAutoComplete::GetValueForAutoCompleteMulti($arField, $arResult["GRID_FILTER"][$FIELD_ID]);
		ob_start();
		?><input type="hidden" name="<?echo $FIELD_ID?>" value=""><? //This will emulate empty input
		$control_id = $APPLICATION->IncludeComponent(
			"bitrix:main.lookup.input",
			"elements",
			array(
				"INPUT_NAME" => $FIELD_ID,
				"INPUT_NAME_STRING" => "inp_".$FIELD_ID,
				"INPUT_VALUE_STRING" => is_array($values)? htmlspecialcharsback(current($values)): "",
				"START_TEXT" => "",
				"MULTIPLE" => "N",
				//These params will go throught ajax call to ajax.php in template
				"IBLOCK_TYPE_ID" => $arParams["~IBLOCK_TYPE_ID"],
				"IBLOCK_ID" => $arField["LINK_IBLOCK_ID"],
				"FILTER" => "Y",
			), $component, array("HIDE_ICONS" => "Y")
		);
		$html = ob_get_contents();
		ob_end_clean();

		$arResult["FILTER"][$i]["type"] = "custom";
		$arResult["FILTER"][$i]["value"] = $html;
		$arResult["FILTER"][$i]["filtered"] = isset($_REQUEST[$FIELD_ID]) && (is_array($_REQUEST[$FIELD_ID]) || strlen($_REQUEST[$FIELD_ID]));
		$arResult["FILTER"][$i]["filter_value"] = $_REQUEST[$FIELD_ID];
		$arResult["FILTER"][$i]["enable_settings"] = false;

	endif;
}

$APPLICATION->IncludeComponent(
	"bitrix:main.interface.grid",
	"",
	array(
		"GRID_ID"=>$arResult["GRID_ID"],
		"HEADERS"=>$arResult["ELEMENTS_HEADERS"],
		"ROWS"=>$arResult["ELEMENTS_ROWS"],
		"ACTIONS"=>$arActions,
		"NAV_OBJECT"=>$arResult["NAV_OBJECT"],
		"SORT"=>$arResult["SORT"],
		"FILTER"=>$arResult["FILTER"],
		"FOOTER" => array(
			array("title" => GetMessage("CT_BLL_SELECTED"), "value" => $arResult["NAV_OBJECT"]->SelectedRowsCount())
		),
		"AJAX_MODE" => "Y",
		"AJAX_OPTION_JUMP"=>"N",
	),
	$component, array("HIDE_ICONS" => "Y")
);
?>