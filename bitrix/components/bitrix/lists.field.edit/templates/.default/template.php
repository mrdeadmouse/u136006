<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

$arToolbar = array();
if($arResult["FIELD_ID"] && $arResult["FIELD_ID"] != "NAME")
{
	$arToolbar[] = array(
		"TEXT"=>GetMessage("CT_BLFE_TOOLBAR_DELETE"),
		"TITLE"=>GetMessage("CT_BLFE_TOOLBAR_DELETE_TITLE"),
		"LINK"=>"javascript:jsDelete('".CUtil::JSEscape("form_".$arResult["FORM_ID"])."', '".GetMessage("CT_BLFE_TOOLBAR_DELETE_WARNING")."')",
		"ICON"=>"btn-delete-field",
	);
}

if(count($arToolbar))
	$arToolbar[] = array(
		"SEPARATOR"=>"Y",
	);

$arToolbar[] = array(
	"TEXT"=>GetMessage("CT_BLFE_TOOLBAR_FIELDS"),
	"TITLE"=>GetMessage("CT_BLFE_TOOLBAR_FIELDS_TITLE"),
	"LINK"=>$arResult["LIST_FIELDS_URL"],
	"ICON"=>"btn-view-fields",
);

$APPLICATION->IncludeComponent(
	"bitrix:main.interface.toolbar",
	"",
	array(
		"BUTTONS"=>$arToolbar,
	),
	$component, array("HIDE_ICONS" => "Y")
);

$arTab1Fields = array(array("id"=>"NAME","name"=>GetMessage("CT_BLFE_FIELD_NAME"),"required"=>true));

if($arResult["FIELD_ID"] == "NAME" && $arParams["IBLOCK_TYPE_ID"] == COption::GetOptionString("lists", "livefeed_iblock_type_id"))
{
	$arTab1Fields[] = array(
		"id"=>"DEFAULT_VALUE",
		"name"=>GetMessage("CT_BLFE_FIELD_DEFAULT_VALUE"),
		"type"=>"text",
		"value"=>$arResult["FORM_DATA"]["DEFAULT_VALUE"]
	);
}

if($arResult["IS_READ_ONLY"])
	$arTab1Fields[] = array(
		"id"=>"IS_REQUIRED",
		"name"=>GetMessage("CT_BLFE_FIELD_IS_REQUIRED"),
		"type"=>"custom",
		"value"=>'<input type="hidden" name="IS_REQUIRED" value="N">'.GetMessage("MAIN_NO"),
	);
elseif($arResult["CAN_BE_OPTIONAL"])
	$arTab1Fields[] = array(
		"id"=>"IS_REQUIRED",
		"name"=>GetMessage("CT_BLFE_FIELD_IS_REQUIRED"),
		"type"=>"checkbox",
	);
else
	$arTab1Fields[] = array(
		"id"=>"IS_REQUIRED",
		"name"=>GetMessage("CT_BLFE_FIELD_IS_REQUIRED"),
		"type"=>"custom",
		"value"=>'<input type="hidden" name="IS_REQUIRED" value="Y">'.GetMessage("MAIN_YES"),
	);

if($arResult["CAN_BE_MULTIPLE"])
	$arTab1Fields[] = array(
		"id"=>"MULTIPLE",
		"name"=>GetMessage("CT_BLFE_FIELD_MULTIPLE"),
		"type"=>"checkbox",
	);
else
	$arTab1Fields[] = array(
		"id"=>"MULTIPLE",
		"name"=>GetMessage("CT_BLFE_FIELD_MULTIPLE"),
		"type"=>"label",
		"value"=>GetMessage("MAIN_NO"),
	);

if ($arResult["FIELD_ID"])
{
	$arTab1Fields[] = array(
		"id"=>"TYPE",
		"name"=>GetMessage("CT_BLFE_FIELD_TYPE"),
		"type"=>"label",
		"value"=>$arResult["TYPES"][$arResult["FIELD"]["TYPE"]],
	);
}
else
{
	$arTab1Fields[] = array(
		"id"=>"TYPE",
		"name"=>GetMessage("CT_BLFE_FIELD_TYPE"),
		"type"=>"list",
		"items"=>$arResult["TYPES"],
		"params"=>array(
			'OnChange'=>'jsTypeChanged(\'form_'.$arResult["FORM_ID"].'\', this);',
		),
	);
}

$arUserType = $arResult["FIELD"]["PROPERTY_USER_TYPE"];
$arPropertyFields = array();
$USER_TYPE_SETTINGS_HTML = "";
if(is_array($arUserType))
{
	if(array_key_exists("GetSettingsHTML", $arUserType))
	{
		$USER_TYPE_SETTINGS_HTML = call_user_func_array($arUserType["GetSettingsHTML"],
			array(
				$arResult["FIELD"],
				array(
					"NAME"=>"USER_TYPE_SETTINGS",
				),
				&$arPropertyFields,
			));
	}
}

if($arResult["IS_READ_ONLY"])
{
}
elseif($arResult["FORM_DATA"]["TYPE"] == "SORT")
{
	$arTab1Fields[] = array(
		"id" => "DEFAULT_VALUE",
		"name" => GetMessage("CT_BLFE_FIELD_DEFAULT_VALUE"),
	);
}
elseif($arResult["FORM_DATA"]["TYPE"] == "S")
{
	if ($arResult["FORM_DATA"]["ROW_COUNT"] > 1)
	{
		$arTab1Fields[] = array(
			"id" => "DEFAULT_VALUE",
			"name" => GetMessage("CT_BLFE_FIELD_DEFAULT_VALUE"),
			"type" => "textarea",
			"params" => array(
				"cols" => $arResult["FORM_DATA"]["COL_COUNT"],
				"rows" => $arResult["FORM_DATA"]["ROW_COUNT"],
				"style" => "width:".$arResult["FORM_DATA"]["COL_COUNT"]."em;height:".$arResult["FORM_DATA"]["ROW_COUNT"]."em;",
			),
		);
	}
	else
	{
		$arTab1Fields[] = array(
			"id" => "DEFAULT_VALUE",
			"name" => GetMessage("CT_BLFE_FIELD_DEFAULT_VALUE"),
			"params" => array(
				"size" => $arResult["FORM_DATA"]["COL_COUNT"],
			),
		);
	}
}
elseif($arResult["FORM_DATA"]["TYPE"] == "ACTIVE_FROM")
{
	$arTab1Fields[] = array(
		"id"=>"DEFAULT_VALUE",
		"name"=>GetMessage("CT_BLFE_FIELD_DEFAULT_VALUE"),
		"type"=>"list",
		"items" => array(
			"" => GetMessage("CT_BLFE_FIELD_ACTIVE_FROM_EMPTY"),
			"=now" => GetMessage("CT_BLFE_FIELD_ACTIVE_FROM_NOW"),
			"=today" => GetMessage("CT_BLFE_FIELD_ACTIVE_FROM_TODAY"),
		),
	);
}
elseif($arResult["FORM_DATA"]["TYPE"] == "ACTIVE_TO")
{
	//TODO
}
elseif($arResult["FORM_DATA"]["TYPE"] == "PREVIEW_PICTURE")
{
	$arTab1Fields[] = array(
		"id" => "DEFAULT_VALUE[FROM_DETAIL]",
		"name" => GetMessage("CT_BLFE_FIELD_PREVIEW_PICTURE_FROM_DETAIL"),
		"type" => "checkbox",
		"value" => isset($arResult["FORM_DATA"]["DEFAULT_VALUE"]["FROM_DETAIL"]) ? $arResult["FORM_DATA"]["DEFAULT_VALUE"]["FROM_DETAIL"] : '',
	);
	$arTab1Fields[] = array(
		"id" => "DEFAULT_VALUE[DELETE_WITH_DETAIL]",
		"name" => GetMessage("CT_BLFE_FIELD_PREVIEW_PICTURE_DELETE_WITH_DETAIL"),
		"type" => "checkbox",
		"value" => isset($arResult["FORM_DATA"]["DEFAULT_VALUE"]["DELETE_WITH_DETAIL"]) ? $arResult["FORM_DATA"]["DEFAULT_VALUE"]["DELETE_WITH_DETAIL"] : '',
	);
	$arTab1Fields[] = array(
		"id" => "DEFAULT_VALUE[UPDATE_WITH_DETAIL]",
		"name" => GetMessage("CT_BLFE_FIELD_PREVIEW_PICTURE_UPDATE_WITH_DETAIL"),
		"type" => "checkbox",
		"value" => isset($arResult["FORM_DATA"]["DEFAULT_VALUE"]["UPDATE_WITH_DETAIL"]) ? $arResult["FORM_DATA"]["DEFAULT_VALUE"]["UPDATE_WITH_DETAIL"] : '',
	);
	$arTab1Fields[] = array(
		"id" => "DEFAULT_VALUE[SCALE]",
		"name" => GetMessage("CT_BLFE_FIELD_PICTURE_SCALE"),
		"type" => "checkbox",
		"value" => isset($arResult["FORM_DATA"]["DEFAULT_VALUE"]["SCALE"]) ? $arResult["FORM_DATA"]["DEFAULT_VALUE"]["SCALE"] : '',
	);
	$arTab1Fields[] = array(
		"id" => "DEFAULT_VALUE[WIDTH]",
		"name" => GetMessage("CT_BLFE_FIELD_PICTURE_WIDTH"),
		"params" => array("size" => 7),
		"value" => isset($arResult["FORM_DATA"]["DEFAULT_VALUE"]["WIDTH"]) ? $arResult["FORM_DATA"]["DEFAULT_VALUE"]["WIDTH"] : '',
	);
	$arTab1Fields[] = array(
		"id" => "DEFAULT_VALUE[HEIGHT]",
		"name" => GetMessage("CT_BLFE_FIELD_PICTURE_HEIGHT"),
		"params" => array("size" => 7),
		"value" => isset($arResult["FORM_DATA"]["DEFAULT_VALUE"]["HEIGHT"]) ? $arResult["FORM_DATA"]["DEFAULT_VALUE"]["HEIGHT"] : '',
	);
	$arTab1Fields[] = array(
		"id" => "DEFAULT_VALUE[IGNORE_ERRORS]",
		"name" => GetMessage("CT_BLFE_FIELD_PICTURE_IGNORE_ERRORS"),
		"type" => "checkbox",
		"value" => isset($arResult["FORM_DATA"]["DEFAULT_VALUE"]["IGNORE_ERRORS"]) ? $arResult["FORM_DATA"]["DEFAULT_VALUE"]["IGNORE_ERRORS"] : '',
	);
}
elseif($arResult["FORM_DATA"]["TYPE"] == "PREVIEW_TEXT" || $arResult["FORM_DATA"]["TYPE"] == "DETAIL_TEXT")
{
	$arTab1Fields[] = array(
		"id"=>"SETTINGS[USE_EDITOR]",
		"name"=>GetMessage("CT_BLFE_TEXT_USE_EDITOR"),
		"type"=>"checkbox",
		"value"=>$arResult["FORM_DATA"]["SETTINGS"]["USE_EDITOR"],
	);
	$arTab1Fields[] = array(
		"id"=>"SETTINGS[WIDTH]",
		"name"=>GetMessage("CT_BLFE_TEXT_WIDTH_NEW"),
		"params" => array("size" => 7),
		"value"=>$arResult["FORM_DATA"]["SETTINGS"]["WIDTH"] ? $arResult["FORM_DATA"]["SETTINGS"]["WIDTH"] : 600,
	);
	$arTab1Fields[] = array(
		"id"=>"SETTINGS[HEIGHT]",
		"name"=>GetMessage("CT_BLFE_TEXT_HEIGHT_NEW"),
		"params" => array("size" => 7),
		"value"=>$arResult["FORM_DATA"]["SETTINGS"]["HEIGHT"] ? $arResult["FORM_DATA"]["SETTINGS"]["HEIGHT"] : 200,
	);
	$arTab1Fields[] = array(
		"id"=>"DEFAULT_VALUE",
		"name"=>GetMessage("CT_BLFE_FIELD_DEFAULT_VALUE"),
		"type"=>"textarea",
		"rows"=>"5"
	);
}
elseif($arResult["FORM_DATA"]["TYPE"] == "DETAIL_PICTURE")
{
	$arTab1Fields[] = array(
		"id" => "DEFAULT_VALUE[SCALE]",
		"name" => GetMessage("CT_BLFE_FIELD_PICTURE_SCALE"),
		"type" => "checkbox",
		"value" => isset($arResult["FORM_DATA"]["DEFAULT_VALUE"]["SCALE"]) ? $arResult["FORM_DATA"]["DEFAULT_VALUE"]["SCALE"] : '',
	);
	$arTab1Fields[] = array(
		"id" => "DEFAULT_VALUE[WIDTH]",
		"name" => GetMessage("CT_BLFE_FIELD_PICTURE_WIDTH"),
		"params" => array("size" => 7),
		"value" => isset($arResult["FORM_DATA"]["DEFAULT_VALUE"]["WIDTH"]) ? $arResult["FORM_DATA"]["DEFAULT_VALUE"]["WIDTH"] : '',
	);
	$arTab1Fields[] = array(
		"id" => "DEFAULT_VALUE[HEIGHT]",
		"name" => GetMessage("CT_BLFE_FIELD_PICTURE_HEIGHT"),
		"params" => array("size" => 7),
		"value" => isset($arResult["FORM_DATA"]["DEFAULT_VALUE"]["HEIGHT"]) ? $arResult["FORM_DATA"]["DEFAULT_VALUE"]["HEIGHT"] : '',
	);
	$arTab1Fields[] = array(
		"id" => "DEFAULT_VALUE[IGNORE_ERRORS]",
		"name" => GetMessage("CT_BLFE_FIELD_PICTURE_IGNORE_ERRORS"),
		"type" => "checkbox",
		"value" => isset($arResult["FORM_DATA"]["DEFAULT_VALUE"]["IGNORE_ERRORS"]) ? $arResult["FORM_DATA"]["DEFAULT_VALUE"]["IGNORE_ERRORS"] : '',
	);
}
elseif(preg_match("/^(L|L:)/", $arResult["FORM_DATA"]["TYPE"]))
{
	//No default value input
}
elseif(preg_match("/^(F|F:)/", $arResult["FORM_DATA"]["TYPE"]))
{
	//No default value input
}
elseif(preg_match("/^(G|G:)/", $arResult["FORM_DATA"]["TYPE"]))
{
	$LINK = $arResult["FORM_DATA"]["LINK_IBLOCK_ID"];
	if($LINK <= 0)
		list($LINK,) = each($arResult["LINK_IBLOCKS"]);

	$items = array("" => GetMessage("CT_BLFE_NO_VALUE"));
	if ($LINK > 0)
	{
		$rsSections = CIBlockSection::GetTreeList(Array("IBLOCK_ID"=>$LINK));
		while($ar = $rsSections->Fetch())
			$items[$ar["ID"]] = str_repeat(" . ", $ar["DEPTH_LEVEL"]).$ar["NAME"];
	}

	$arTab1Fields[] = array(
		"id"=>"DEFAULT_VALUE",
		"name"=>GetMessage("CT_BLFE_FIELD_DEFAULT_VALUE"),
		"type"=>"list",
		"items"=>$items,
	);
}
elseif(preg_match("/^(E|E:)/", $arResult["FORM_DATA"]["TYPE"]))
{
	//No default value input
}
elseif(!is_array($arPropertyFields["HIDE"]) || !in_array("DEFAULT_VALUE", $arPropertyFields["HIDE"]))
{//Show default property value input if it was not cancelled by property
	if(is_array($arUserType))
	{
		if(array_key_exists("GetPublicEditHTML", $arUserType))
		{
			$html = '';
			if($arResult["FORM_DATA"]["TYPE"] == "S:HTML")
			{
				$params = array('width' => '100%','height' => '200px');
				if(is_array($arResult["FORM_DATA"]["~DEFAULT_VALUE"]))
					$htmlContent = $arResult["FORM_DATA"]["~DEFAULT_VALUE"]["TEXT"];
				else
					$arResult["FORM_DATA"]["~DEFAULT_VALUE"] ? $htmlContent = $arResult["FORM_DATA"]["~DEFAULT_VALUE"]["TEXT"] : $htmlContent = '';
				$html = connectionHtmlEditor($arResult['FIELD_ID'], $params, $htmlContent);
			}
			else
			{
				$html = call_user_func_array($arUserType["GetPublicEditHTML"],
					array(
						$arResult["FIELD"],
						array(
							"VALUE"=>$arResult["FORM_DATA"]["~DEFAULT_VALUE"],
							"DESCRIPTION"=>""
						),
						array(
							"VALUE"=>"DEFAULT_VALUE",
							"DESCRIPTION"=>"",
							"MODE" => "EDIT_FORM",
							"FORM_NAME" => "form_".$arResult["FORM_ID"],
						),
					)
				);
			}
			$arTab1Fields[] = array(
				"id"=>"DEFAULT_VALUE",
				"name"=>GetMessage("CT_BLFE_FIELD_DEFAULT_VALUE"),
				"type"=>"custom",
				"value"=> $html
			);
		}
		else
		{
			$arTab1Fields[] = array(
				"id"=>"DEFAULT_VALUE",
				"name"=>GetMessage("CT_BLFE_FIELD_DEFAULT_VALUE"),
			);
		}
	}
}

function connectionHtmlEditor($fieldId, $params, $content)
{
	$html = '';
	if (CModule::includeModule('fileman'))
	{
		ob_start();
		$editor = new CHTMLEditor;
		$res = array(
			'name' => $fieldId,
			'inputName' => 'DEFAULT_VALUE',
			'id' => $fieldId,
			'width' => $params['width'],
			'height' => $params['height'],
			'content' => $content,
			'minBodyWidth' => 350,
			'normalBodyWidth' => 555,
			'bAllowPhp' => false,
			'limitPhpAccess' => false,
			'showTaskbars' => false,
			'showNodeNavi' => false,
			'beforeUnloadHandlerAllowed' => true,
			'askBeforeUnloadPage' => false,
			'bbCode' => false,
			'siteId' => SITE_ID,
			'autoResize' => true,
			'autoResizeOffset' => 40,
			'saveOnBlur' => true,
			'controlsMap' => array(
				array('id' => 'Bold',  'compact' => true, 'sort' => 80),
				array('id' => 'Italic',  'compact' => true, 'sort' => 90),
				array('id' => 'Underline',  'compact' => true, 'sort' => 100),
				array('id' => 'Strikeout',  'compact' => true, 'sort' => 110),
				array('id' => 'RemoveFormat',  'compact' => true, 'sort' => 120),
				array('id' => 'Color',  'compact' => true, 'sort' => 130),
				array('id' => 'FontSelector',  'compact' => false, 'sort' => 135),
				array('id' => 'FontSize',  'compact' => false, 'sort' => 140),
				array('separator' => true, 'compact' => false, 'sort' => 145),
				array('id' => 'OrderedList',  'compact' => true, 'sort' => 150),
				array('id' => 'UnorderedList',  'compact' => true, 'sort' => 160),
				array('id' => 'AlignList', 'compact' => false, 'sort' => 190),
				array('separator' => true, 'compact' => false, 'sort' => 200),
				array('id' => 'InsertLink',  'compact' => true, 'sort' => 210, 'wrap' => 'bx-htmleditor-'.$fieldId),
				array('id' => 'InsertImage',  'compact' => false, 'sort' => 220),
				array('id' => 'InsertVideo',  'compact' => true, 'sort' => 230, 'wrap' => 'bx-htmleditor-'.$fieldId),
				array('id' => 'InsertTable',  'compact' => false, 'sort' => 250),
				array('id' => 'Code',  'compact' => true, 'sort' => 260),
				array('id' => 'Quote',  'compact' => true, 'sort' => 270, 'wrap' => 'bx-htmleditor-'.$fieldId),
				array('id' => 'Smile',  'compact' => false, 'sort' => 280),
				array('separator' => true, 'compact' => false, 'sort' => 290),
				array('id' => 'Fullscreen',  'compact' => false, 'sort' => 310),
				array('id' => 'BbCode',  'compact' => true, 'sort' => 340),
				array('id' => 'More',  'compact' => true, 'sort' => 400)
			)
		);
		$editor->show($res);
		$html = ob_get_contents();
		ob_end_clean();
	}
	return $html;
}

$custom_html = "";

if(preg_match("/^(G|G:)/", $arResult["FORM_DATA"]["TYPE"]))
{
	$arTab1Fields[] = array(
		"id"=>"LINK_IBLOCK_ID",
		"name"=>GetMessage("CT_BLFE_FIELD_SECTION_LINK_IBLOCK_ID"),
		"type"=>"list",
		"items"=>$arResult["LINK_IBLOCKS"],
		"params"=>array('OnChange' => 'jsTypeChanged(\'form_'.$arResult["FORM_ID"].'\', this);'),
	);
	$custom_html .= '<input type="hidden" name="TYPE" value="'.$arResult["FORM_DATA"]["TYPE"].'">';
}
elseif(preg_match("/^(E|E:)/", $arResult["FORM_DATA"]["TYPE"]))
{
	$arTab1Fields[] = array(
		"id"=>"LINK_IBLOCK_ID",
		"name"=>GetMessage("CT_BLFE_FIELD_ELEMENT_LINK_IBLOCK_ID"),
		"type"=>"list",
		"items"=>$arResult["LINK_IBLOCKS"],
	);
	$custom_html .= '<input type="hidden" name="TYPE" value="'.$arResult["FORM_DATA"]["TYPE"].'">';
}
elseif($arResult["FORM_DATA"]["TYPE"] === "S")
{
	$arTab1Fields[] = array(
		"id"=>"INPUT_SIZE",
		"name"=>GetMessage("CT_BLFE_FIELD_INPUT_SIZE"),
		"type"=>"custom",
		"value"=>'<input type="text" size="2" maxlength="10" name="ROW_COUNT" value="'.intval($arResult["FORM_DATA"]["ROW_COUNT"]).'"> x <input type="text" size="2" maxlength="10" name="COL_COUNT" value="'.intval($arResult["FORM_DATA"]["COL_COUNT"]).'">',
	);
}
elseif(isset($arResult["FORM_DATA"]["LINK_IBLOCK_ID"]) && $arResult["FORM_DATA"]["LINK_IBLOCK_ID"] > 0)
{
	$custom_html .= '<input type="hidden" name="LINK_IBLOCK_ID" value="'.$arResult["FORM_DATA"]["LINK_IBLOCK_ID"].'">';
}

$arTab1Fields[] = array("id"=>"SORT", "name"=>GetMessage("CT_BLFE_FIELD_SORT"), "params"=>array("size"=>5));

$checkedAdd = true;
$checkedEdit = true;

if($arResult["FIELD_ID"])
{
	if($arResult["FORM_DATA"]["SETTINGS"]["SHOW_ADD_FORM"] == "N")
		$checkedAdd = false;
	if($arResult["FORM_DATA"]["SETTINGS"]["SHOW_EDIT_FORM"] == "N")
		$checkedEdit = false;
}
$params = array();

$params["id"] = "bx-lists-show-add-form";
$arTab1Fields[] = array(
	"id"=>"SETTINGS[SHOW_ADD_FORM]",
	"name"=>GetMessage("CT_BLFE_FIELD_SHOW_ADD_FORM"),
	"type"=>"checkbox",
	"value" => $checkedAdd,
	"params"=>$params
);

$params["id"] = "bx-lists-show-edit-form";
$arTab1Fields[] = array(
	"id"=>"SETTINGS[SHOW_EDIT_FORM]",
	"name"=>GetMessage("CT_BLFE_FIELD_SHOW_EDIT_FORM"),
	"type"=>"checkbox",
	"value" => $checkedEdit,
	"params"=>$params
);

$arTabs = array(
	array("id"=>"tab1", "name"=>GetMessage("CT_BLFE_TAB_EDIT"), "title"=>GetMessage("CT_BLFE_TAB_EDIT_TITLE"), "icon"=>"", "fields"=>$arTab1Fields),
);

//List properties
if(is_array($arResult["LIST"]))
{
	if(preg_match("/^(L|L:)/", $arResult["FORM_DATA"]["TYPE"]))
	{
		$sort = 10;
		$html = '<div id="divTable"><table id="tblLIST" width="100%" class="tableList">';
		foreach($arResult["LIST"] as $arEnum)
		{
			$html .= '
				<tr>
				<td style="display:none;"></td>
				<td align="center" class="sort-td" title="'.GetMessage("CT_BLFE_SORT_TITLE").'"></td>
				<td class="tdInput">
					<input type="hidden" name="LIST['.$arEnum["ID"].'][SORT]" value="'.$sort.'" class="sort-input">
					<input type="text" size="35" name="LIST['.$arEnum["ID"].'][VALUE]" value="'.$arEnum["VALUE"].'" class="value-input">
				</td>
				<td align="center" class="delete-action"><div class="delete-action" onclick="delete_item(this);" title="'.GetMessage("CT_BLFE_DELETE_TITLE").'"></div></td>
				</tr>
			';
			$sort += 10;
		}

		$html .= '</table></div>';
		$html .= '<input type="button" value="'.GetMessage("CT_BLFE_LIST_ITEM_ADD").'" onClick="addNewTableRow(\'tblLIST\', /LIST\[(n)([0-9]*)\]/g, 2)">';

		$html .= '
			<br><br>
			<a class="href-action" href="javascript:void(0)" onclick="toggle_input(\'import\'); return false;">'.GetMessage("CT_BLFE_ENUM_IMPORT").'</a>
			<div id="import" style="'.(strlen($arResult["FORM_DATA"]["LIST_TEXT_VALUES"]) > 0? '': 'display:none; ').'width:100%">
				<p>'.GetMessage("CT_BLFE_ENUM_IMPORT_HINT").'</p>
				<textarea name="LIST_TEXT_VALUES" id="LIST_TEXT_VALUES" style="width:100%" rows="20">'.htmlspecialcharsex($arResult["FORM_DATA"]["LIST_TEXT_VALUES"]).'</textarea>
			</div>
		';

		$html .= '
			<br><br>
			<a class="href-action" href="javascript:void(0)" onclick="toggle_input(\'defaults\'); return false;">'.($arResult["FORM_DATA"]["MULTIPLE"] == "Y"? GetMessage("CT_BLFE_ENUM_DEFAULTS"): GetMessage("CT_BLFE_ENUM_DEFAULT")).'</a>
			<div id="defaults" style="'.(strlen($arResult["FORM_DATA"]["LIST_TEXT_VALUES"]) > 0? '': 'display:none; ').'width:100%">
			<br>
		';

		if($arResult["FORM_DATA"]["MULTIPLE"] == "Y")
			$html .= '<select multiple name="LIST_DEF[]" id="LIST_DEF" size="10">';
		else
			$html .= '<select name="LIST_DEF[]" id="LIST_DEF" size="1">';

		if($arResult["FORM_DATA"]["IS_REQIRED"] != "Y")
			$html .= '<option value=""'.(count($arResult["LIST_DEF"])==0? ' selected': '').'>'.GetMessage("CT_BLFE_ENUM_NO_DEFAULT").'</option>';

		foreach($arResult["LIST"] as $arEnum)
			$html .= '<option value="'.$arEnum["ID"].'"'.(isset($arResult["LIST_DEF"][$arEnum["ID"]])? ' selected': '').'>'.$arEnum["VALUE"].'</option>';

		$html .= '
				</select>
			</div>
		';

		$arTabs[] = array(
			"id"=>"tab2",
			"name"=>GetMessage("CT_BLFE_TAB_LIST"),
			"title"=>GetMessage("CT_BLFE_TAB_LIST_TITLE"),
			"icon"=>"",
			"fields"=>array(
				array(
					"id" => "LIST",
					"colspan" => true,
					"type" => "custom",
					"value" => $html,
				),
			),
		);
		?>
		<script>
			BX.ready(function ()
			{
				var table = BX('divTable');
				dragTable(table.getElementsByTagName('table')[0], {
					start: function (table, el, index)
					{
					},
					stop: function (table, el, indexBefore, index)
					{
						enumerationValues(table);
					}
				});
			});
		</script>
		<?
	}
	else
	{
		foreach($arResult["LIST"] as $arEnum)
		{
			$custom_html .= '<input type="hidden" name="LIST['.$arEnum["ID"].'][SORT]" value="'.$arEnum["SORT"].'">'
				.'<input type="hidden" name="LIST['.$arEnum["ID"].'][VALUE]" value="'.$arEnum["VALUE"].'">';
		}
	}
}

$custom_html .= '<input type="hidden" name="action" id="action" value="">';

$APPLICATION->IncludeComponent(
	"bitrix:main.interface.form",
	"",
	array(
		"FORM_ID"=>$arResult["FORM_ID"],
		"TABS"=>$arTabs,
		"BUTTONS"=>array("back_url"=>$arResult["~LIST_FIELDS_URL"], "custom_html"=>$custom_html),
		"DATA"=>$arResult["FORM_DATA"],
		"SHOW_SETTINGS"=>"N",
		"THEME_GRID_ID"=>$arResult["GRID_ID"],
	),
	$component, array("HIDE_ICONS" => "Y")
);
?>