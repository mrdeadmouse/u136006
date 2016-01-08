<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

CUtil::InitJSCore(array("window"));

$arToolbar = array();

if (isset($arResult["LIST_COPY_ELEMENT_URL"]))
{
	if($arResult["CAN_ADD_ELEMENT"])
	{
		$arToolbar[] = array(
			"TEXT"=>GetMessage("CT_BLEE_TOOLBAR_COPY_ELEMENT"),
			"TITLE"=>GetMessage("CT_BLEE_TOOLBAR_COPY_ELEMENT_TITLE"),
			"LINK"=>htmlspecialcharsbx($arResult["LIST_COPY_ELEMENT_URL"]),
			"ICON"=>"",
		);
	}
}

if($arResult["CAN_DELETE_ELEMENT"])
{
	$arToolbar[] = array(
		"TEXT"=>$arResult["IBLOCK"]["ELEMENT_DELETE"],
		"TITLE"=>GetMessage("CT_BLEE_TOOLBAR_DELETE_TITLE"),
		"LINK"=>"javascript:jsDelete('form_".$arResult["FORM_ID"]."', '".GetMessage("CT_BLEE_TOOLBAR_DELETE_WARNING")."')",
		"ICON"=>"btn-delete-element",
	);
}

if(!empty($arToolbar))
{
	$arToolbar[] = array(
		"SEPARATOR"=>"Y",
	);
}

$arToolbar[] = array(
	"TEXT"=>$arResult["IBLOCK"]["ELEMENTS_NAME"],
	"TITLE"=>GetMessage("CT_BLEE_TOOLBAR_LIST_TITLE"),
	"LINK"=>$arResult["LIST_SECTION_URL"],
	"ICON"=>"btn-view-elements",
);

$APPLICATION->IncludeComponent(
	"bitrix:main.interface.toolbar",
	"",
	array(
		"BUTTONS"=>$arToolbar,
	),
	$component, array("HIDE_ICONS" => "Y")
);

$arTabElement = array();
foreach($arResult["FIELDS"] as $FIELD_ID => $arField)
{
	$show = "Y";
	if(!empty($arField["SETTINGS"]["SHOW_EDIT_FORM"]) || !empty($arField["SETTINGS"]["SHOW_ADD_FORM"]))
	{
		if($arResult["ELEMENT_ID"] > 0)
			$show = $arField["SETTINGS"]["SHOW_EDIT_FORM"];
		else
			$show = $arField["SETTINGS"]["SHOW_ADD_FORM"];
	}

	if($FIELD_ID == "ACTIVE_FROM" || $FIELD_ID == "ACTIVE_TO")
	{
		$arTabElement[] = array(
			"id" => $FIELD_ID,
			"name" => $arField["~NAME"],
			"required" => $arField["IS_REQUIRED"]=="Y"? true: false,
			"type" => "date",
			"show" => $show
		);
	}
	elseif($FIELD_ID == "PREVIEW_PICTURE" || $FIELD_ID == "DETAIL_PICTURE")
	{
		$obFile = new CListFile(
			$arResult["IBLOCK_ID"],
			$arResult["ELEMENT_FIELDS"]["IBLOCK_SECTION_ID"],
			$arResult["ELEMENT_ID"],
			$FIELD_ID,
			$arResult["FORM_DATA"][$FIELD_ID]
		);
		$obFile->SetSocnetGroup($arParams["SOCNET_GROUP_ID"]);

		$obFileControl = new CListFileControl($obFile, $FIELD_ID);

		$html = $obFileControl->GetHTML(array(
			'max_size' => 102400,
			'max_width' => 150,
			'max_height' => 150,
			'url_template' => $arParams["~LIST_FILE_URL"],
			'a_title' => GetMessage("CT_BLEE_ENLARGE"),
			'download_text' => GetMessage("CT_BLEE_DOWNLOAD"),
		));

		$arTabElement[] = array(
			"id" => $FIELD_ID,
			"name" => $arField["~NAME"],
			"required" => $arField["IS_REQUIRED"]=="Y"? true: false,
			"type" => "custom",
			"value" => $html,
			"show" => $show
		);
	}
	elseif($FIELD_ID == "PREVIEW_TEXT" || $FIELD_ID == "DETAIL_TEXT")
	{
		if($arField["SETTINGS"]["USE_EDITOR"] == "Y")
		{
			$params = array(
				"width" => "100%",
				"height" => "200px",
			);
			$match = array();
			if(preg_match('/\s*(\d+)\s*(px|%|)/', $arField["SETTINGS"]["WIDTH"], $match) && ($match[1] > 0))
			{
				$params["width"] = $match[1].$match[2];
			}
			if(preg_match('/\s*(\d+)\s*(px|%|)/', $arField["SETTINGS"]["HEIGHT"], $match) && ($match[1] > 0))
			{
				$params["height"] = $match[1].$match[2];
			}

			$html = connectionHtmlEditor($FIELD_ID, $FIELD_ID, $params, $arResult["FORM_DATA"]["~".$FIELD_ID]);

			$arTabElement[] = array(
				"id"=>$FIELD_ID,
				"name"=>$arField["~NAME"],
				"required"=>$arField["IS_REQUIRED"]=="Y"? true: false,
				"type" => "custom",
				"value" => $html,
				"show" => $show
			);

		}
		else
		{
			$params = array("style" => "");
			if(preg_match('/\s*(\d+)\s*(px|%|)/', $arField["SETTINGS"]["WIDTH"], $match) && ($match[1] > 0))
				$params["style"] .= "width:".$match[1]."px;";
			if(preg_match('/\s*(\d+)\s*(px|%|)/', $arField["SETTINGS"]["HEIGHT"], $match) && ($match[1] > 0))
				$params["style"] .= "height:".$match[1]."px;";

			$arTabElement[] = array(
				"id"=>$FIELD_ID,
				"name"=>$arField["~NAME"],
				"required"=>$arField["IS_REQUIRED"]=="Y"? true: false,
				"type" => "textarea",
				"params" => $params,
				"show" => $show
			);
		}
	}
	elseif($FIELD_ID == "DATE_CREATE" || $FIELD_ID == "TIMESTAMP_X")
	{
		if($arResult["ELEMENT_FIELDS"][$FIELD_ID])
			$arTabElement[] = array(
				"id"=>$FIELD_ID,
				"name"=>$arField["~NAME"],
				"required"=>$arField["IS_REQUIRED"]=="Y"? true: false,
				"type" => "custom",
				"value" => $arResult["ELEMENT_FIELDS"][$FIELD_ID],
				"show" => $show
			);
	}
	elseif($FIELD_ID == "CREATED_BY")
	{
		if($arResult["ELEMENT_FIELDS"]["CREATED_BY"])
			$arTabElement[] = array(
				"id"=>$FIELD_ID,
				"name"=>$arField["~NAME"],
				"required"=>$arField["IS_REQUIRED"]=="Y"? true: false,
				"type" => "custom",
				"value" => "[".$arResult["ELEMENT_FIELDS"]["CREATED_BY"]."] ".$arResult["ELEMENT_FIELDS"]["CREATED_USER_NAME"],
				"show" => $show
			);
	}
	elseif($FIELD_ID == "MODIFIED_BY")
	{
		if($arResult["ELEMENT_FIELDS"]["MODIFIED_BY"])
			$arTabElement[] = array(
				"id"=>$FIELD_ID,
				"name"=>$arField["~NAME"],
				"required"=>$arField["IS_REQUIRED"]=="Y"? true: false,
				"type" => "custom",
				"value" => "[".$arResult["ELEMENT_FIELDS"]["MODIFIED_BY"]."] ".$arResult["ELEMENT_FIELDS"]["USER_NAME"],
				"show" => $show
			);
	}
	elseif(
		is_array($arField["PROPERTY_USER_TYPE"])
		&& array_key_exists("GetPublicEditHTMLMulty", $arField["PROPERTY_USER_TYPE"])
		&& $arField["MULTIPLE"] == "Y"
	)
	{
		$html = call_user_func_array($arField["PROPERTY_USER_TYPE"]["GetPublicEditHTMLMulty"],
			array(
				$arField,
				$arResult["FORM_DATA"]["~".$FIELD_ID],
				array(
					"VALUE"=>$FIELD_ID,
					"DESCRIPTION"=>'',
					"FORM_NAME"=>"form_".$arResult["FORM_ID"],
					"MODE"=>"FORM_FILL",
				),
		));

		$arTabElement[] = array(
			"id"=>$FIELD_ID,
			"name"=>$arField["~NAME"],
			"required"=>$arField["IS_REQUIRED"]=="Y"? true: false,
			"type"=>"custom",
			"value"=>$html,
			"show" => $show
		);
	}
	elseif(is_array($arField["PROPERTY_USER_TYPE"]) && array_key_exists("GetPublicEditHTML", $arField["PROPERTY_USER_TYPE"]))
	{
		$params = array('width' => '100%','height' => '200px');
		if($arField["MULTIPLE"] == "Y")
		{
			$checkHtml = false;
			$html = '<table id="tbl'.$FIELD_ID.'">';
			foreach($arResult["FORM_DATA"]["~".$FIELD_ID] as $key => $value)
			{
				if($arField["TYPE"] == "S:HTML")
				{
					if(is_array($value['VALUE']))
						$htmlContent = $value['VALUE']['TEXT'];
					else
						$value['VALUE'] ? $htmlContent = $value['VALUE']['TEXT'] : $htmlContent = '';

					$checkHtml = true;
					$fieldIdForHtml = 'id_'.$FIELD_ID.'__'.$key.'_';
					$fieldNameForHtml = $FIELD_ID."[".$key."][VALUE]";
					$html .= '<tr><td>'.connectionHtmlEditor($fieldIdForHtml, $fieldNameForHtml, $params, $htmlContent).'</td></tr>';
				}
				else
				{
					$html .= '<tr><td>'.call_user_func_array($arField["PROPERTY_USER_TYPE"]["GetPublicEditHTML"],
							array(
								$arField,
								$value,
								array(
									"VALUE"=>$FIELD_ID."[".$key."][VALUE]",
									"DESCRIPTION"=>'',
									"FORM_NAME"=>"form_".$arResult["FORM_ID"],
									"MODE"=>"FORM_FILL",
									"COPY"=>$arResult["COPY_ID"] > 0,
								),
							)).'</td></tr>';
				}
			}
			$html .= '</table>';
			if($checkHtml)
				$html .= '<input type="button" onclick="createAdditionalHtmlEditor(\'tbl'.$FIELD_ID.'\', \''.$FIELD_ID.'\', \''.$FIELD_ID.'\');" value="'.GetMessage("CT_BLEE_ADD_BUTTON").'">';
			else
				$html .= '<input type="button" onclick="addNewTableRow(\'tbl'.$FIELD_ID.'\', 1, /'.$FIELD_ID.'\[(n)([0-9]*)\]/g, 2)" value="'.GetMessage("CT_BLEE_ADD_BUTTON").'">';

			$arTabElement[] = array(
				"id"=>$FIELD_ID,
				"name"=>$arField["~NAME"],
				"required"=>$arField["IS_REQUIRED"]=="Y"? true: false,
				"type"=>"custom",
				"value"=>$html,
				"show" => $show
			);
		}
		else
		{
			$html = '';
			foreach($arResult["FORM_DATA"]["~".$FIELD_ID] as $key => $value)
			{
				if($arField["TYPE"] == "S:HTML")
				{
					if(is_array($value['VALUE']))
						$htmlContent = $value['VALUE']['TEXT'];
					else
						$value['VALUE'] ? $htmlContent = $value['VALUE']['TEXT'] : $htmlContent = '';

					$fieldNameForHtml = $FIELD_ID."[".$key."][VALUE]";
					$html = connectionHtmlEditor($FIELD_ID, $fieldNameForHtml, $params, $htmlContent);
				}
				else
				{
					$html = call_user_func_array($arField["PROPERTY_USER_TYPE"]["GetPublicEditHTML"],
						array(
							$arField,
							$value,
							array(
								"VALUE"=>$FIELD_ID."[".$key."][VALUE]",
								"DESCRIPTION"=>'',
								"FORM_NAME"=>"form_".$arResult["FORM_ID"],
								"MODE"=>"FORM_FILL",
								"COPY"=>$arResult["COPY_ID"] > 0,
							),
						));
				}
				break;
			}

			$arTabElement[] = array(
				"id"=>$FIELD_ID,
				"name"=>$arField["~NAME"],
				"required"=>$arField["IS_REQUIRED"]=="Y"? true: false,
				"type"=>"custom",
				"value"=>$html,
				"show" => $show
			);
		}
	}
	elseif($arField["PROPERTY_TYPE"] == "N")
	{
		if($arField["MULTIPLE"] == "Y")
		{
			$html = '<table id="tbl'.$FIELD_ID.'">';
			foreach($arResult["FORM_DATA"][$FIELD_ID] as $key => $value)
				$html .= '<tr><td><input type="text" name="'.$FIELD_ID.'['.$key.'][VALUE]" value="'.$value["VALUE"].'"></td></tr>';
			$html .= '</table>';
			$html .= '<input type="button" onclick="addNewTableRow(\'tbl'.$FIELD_ID.'\', 1, /'.$FIELD_ID.'\[(n)([0-9]*)\]/g, 2)" value="'.GetMessage("CT_BLEE_ADD_BUTTON").'">';
		}
		else
		{
			foreach($arResult["FORM_DATA"][$FIELD_ID] as $key => $value)
				$html = '<input type="text" name="'.$FIELD_ID.'['.$key.'][VALUE]" value="'.$value["VALUE"].'">';
		}

		$arTabElement[] = array(
			"id"=>$FIELD_ID,
			"name"=>$arField["~NAME"],
			"required"=>$arField["IS_REQUIRED"]=="Y"? true: false,
			"type"=>"custom",
			"value"=>$html,
			"show" => $show
		);
	}
	elseif($arField["PROPERTY_TYPE"] == "S")
	{
		if($arField["MULTIPLE"] == "Y")
		{
			$html = '<table id="tbl'.$FIELD_ID.'">';
			if ($arField["ROW_COUNT"] > 1)
			{
				foreach($arResult["FORM_DATA"][$FIELD_ID] as $key => $value)
				{
					$html .= '<tr><td><textarea name="'.$FIELD_ID.'['.$key.'][VALUE]" rows="'.intval($arField["ROW_COUNT"]).'" cols="'.intval($arField["COL_COUNT"]).'">'.$value["VALUE"].'</textarea></td></tr>';
				}
			}
			else
			{
				foreach($arResult["FORM_DATA"][$FIELD_ID] as $key => $value)
				{
					$html .= '<tr><td><input type="text" name="'.$FIELD_ID.'['.$key.'][VALUE]" value="'.$value["VALUE"].'"></td></tr>';
				}
			}
			$html .= '</table>';
			$html .= '<input type="button" onclick="addNewTableRow(\'tbl'.$FIELD_ID.'\', 1, /'.$FIELD_ID.'\[(n)([0-9]*)\]/g, 2)" value="'.GetMessage("CT_BLEE_ADD_BUTTON").'">';
		}
		else
		{
			if ($arField["ROW_COUNT"] > 1)
			{
				foreach($arResult["FORM_DATA"][$FIELD_ID] as $key => $value)
				{
					$html = '<textarea name="'.$FIELD_ID.'['.$key.'][VALUE]" rows="'.intval($arField["ROW_COUNT"]).'" cols="'.intval($arField["COL_COUNT"]).'">'.$value["VALUE"].'</textarea>';
				}
			}
			else
			{
				foreach($arResult["FORM_DATA"][$FIELD_ID] as $key => $value)
				{
					$html = '<input type="text" name="'.$FIELD_ID.'['.$key.'][VALUE]" value="'.$value["VALUE"].'" size="'.intval($arField["COL_COUNT"]).'">';
				}
			}
		}

		$arTabElement[] = array(
			"id"=>$FIELD_ID,
			"name"=>$arField["~NAME"],
			"required"=>$arField["IS_REQUIRED"]=="Y"? true: false,
			"type"=>"custom",
			"value"=>$html,
			"show" => $show
		);
	}
	elseif($arField["PROPERTY_TYPE"] == "L")
	{
		$items = array("" => GetMessage("CT_BLEE_NO_VALUE"));
		$prop_enums = CIBlockProperty::GetPropertyEnum($arField["ID"]);
		while($ar_enum = $prop_enums->Fetch())
			$items[$ar_enum["ID"]] = $ar_enum["VALUE"];

		if($arField["MULTIPLE"] == "Y")
		{
			$arTabElement[] = array(
				"id"=>$FIELD_ID.'[]',
				"name"=>$arField["~NAME"],
				"required"=>$arField["IS_REQUIRED"]=="Y"? true: false,
				"type"=>'list',
				"items"=>$items,
				"value"=>$arResult["FORM_DATA"][$FIELD_ID],
				"params" => array("size"=>5, "multiple"=>"multiple"),
				"show" => $show
			);
		}
		else
		{
			$arTabElement[] = array(
				"id"=>$FIELD_ID,
				"name"=>$arField["~NAME"],
				"required"=>$arField["IS_REQUIRED"]=="Y"? true: false,
				"type"=>'list',
				"items"=>$items,
				"value"=>$arResult["FORM_DATA"][$FIELD_ID],
				"show" => $show
			);
		}
	}
	elseif($arField["PROPERTY_TYPE"] == "F")
	{
		if($arField["MULTIPLE"] == "Y")
		{
			$html = '<table id="tbl'.$FIELD_ID.'">';
			foreach($arResult["FORM_DATA"][$FIELD_ID] as $key => $value)
			{
				$html .= '<tr><td>';

				$obFile = new CListFile(
					$arResult["IBLOCK_ID"],
					$arResult["ELEMENT_FIELDS"]["IBLOCK_SECTION_ID"],
					$arResult["ELEMENT_ID"],
					$FIELD_ID,
					$value["VALUE"]
				);
				$obFile->SetSocnetGroup($arParams["SOCNET_GROUP_ID"]);

				$obFileControl = new CListFileControl($obFile, $FIELD_ID.'['.$key.'][VALUE]');

				$html .= $obFileControl->GetHTML(array(
					'max_size' => 102400,
					'max_width' => 150,
					'max_height' => 150,
					'url_template' => $arParams["~LIST_FILE_URL"],
					'a_title' => GetMessage("CT_BLEE_ENLARGE"),
					'download_text' => GetMessage("CT_BLEE_DOWNLOAD"),
				));

				$html .= '</td></tr>';
			}
			$html .= '</table>';
			$html .= '<input type="button" onclick="addNewTableRow(\'tbl'.$FIELD_ID.'\', 1, /'.$FIELD_ID.'\[(n)([0-9]*)\]/g, 2)" value="'.GetMessage("CT_BLEE_ADD_BUTTON").'">';

			$arTabElement[] = array(
				"id"=>$FIELD_ID,
				"name"=>$arField["~NAME"],
				"required"=>$arField["IS_REQUIRED"]=="Y"? true: false,
				"type"=>"custom",
				"value"=>$html,
				"show" => $show
			);
		}
		else
		{
			foreach($arResult["FORM_DATA"][$FIELD_ID] as $key => $value)
			{
				$obFile = new CListFile(
					$arResult["IBLOCK_ID"],
					$arResult["ELEMENT_FIELDS"]["IBLOCK_SECTION_ID"],
					$arResult["ELEMENT_ID"],
					$FIELD_ID,
					$value["VALUE"]
				);
				$obFile->SetSocnetGroup($arParams["SOCNET_GROUP_ID"]);

				$obFileControl = new CListFileControl($obFile, $FIELD_ID.'['.$key.'][VALUE]');

				$html = $obFileControl->GetHTML(array(
					'max_size' => 102400,
					'max_width' => 150,
					'max_height' => 150,
					'url_template' => $arParams["~LIST_FILE_URL"],
					'a_title' => GetMessage("CT_BLEE_ENLARGE"),
					'download_text' => GetMessage("CT_BLEE_DOWNLOAD"),
				));


				$arTabElement[] = array(
					"id"=>$FIELD_ID.'['.$key.'][VALUE]',
					"name"=>$arField["~NAME"],
					"required"=>$arField["IS_REQUIRED"]=="Y"? true: false,
					"type"=>"custom",
					"value"=>$html,
					"show" => $show
				);
			}
		}
	}
	elseif($arField["PROPERTY_TYPE"] == "G")
	{
		if($arField["IS_REQUIRED"]=="Y")
			$items = array();
		else
			$items = array("" => GetMessage("CT_BLEE_NO_VALUE"));

		$rsSections = CIBlockSection::GetTreeList(Array("IBLOCK_ID"=>$arField["LINK_IBLOCK_ID"]));
		while($ar = $rsSections->GetNext())
			$items[$ar["ID"]] = str_repeat(" . ", $ar["DEPTH_LEVEL"]).$ar["~NAME"];

		if($arField["MULTIPLE"] == "Y")
			$params = array("size"=>5, "multiple"=>"multiple");
		else
			$params = array();

		$arTabElement[] = array(
			"id"=>$FIELD_ID.'[]',
			"name"=>$arField["~NAME"],
			"required"=>$arField["IS_REQUIRED"]=="Y"? true: false,
			"type"=>'list',
			"items"=>$items,
			"value"=>$arResult["FORM_DATA"][$FIELD_ID],
			"params" => $params,
			"show" => $show
		);
	}
	elseif($arField["PROPERTY_TYPE"] == "E")
	{
		if($arField["IS_REQUIRED"]=="Y")
			$items = array();
		else
			$items = array("" => GetMessage("CT_BLEE_NO_VALUE"));

		$rsElements = CIBlockElement::GetList(array("NAME"=>"ASC"), array("IBLOCK_ID"=>$arField["LINK_IBLOCK_ID"]), false, false, array("ID", "NAME"));
		while($ar = $rsElements->Fetch())
			$items[$ar["ID"]] = $ar["NAME"];

		ob_start();

		$arValues = array();
		if(is_array($arResult["FORM_DATA"][$FIELD_ID]))
		{
			foreach($arResult["FORM_DATA"][$FIELD_ID] as $element_id)
				if($element_id > 0 && array_key_exists($element_id, $items))
					$arValues[] = $items[$element_id]." [".$element_id."]";
		}
		?><input type="hidden" name="<?echo $FIELD_ID?>[]" value=""><? //This will emulate empty input
		$control_id = $APPLICATION->IncludeComponent(
			"bitrix:main.lookup.input",
			"elements",
			array(
				"INPUT_NAME" => $FIELD_ID,
				"INPUT_NAME_STRING" => "inp_".$FIELD_ID,
				"INPUT_VALUE_STRING" => implode("\n", $arValues),
				"START_TEXT" => GetMessage("CT_BLEE_START_TEXT"),
				"MULTIPLE" => $arField["MULTIPLE"],
				//These params will go throught ajax call to ajax.php in template
				"IBLOCK_TYPE_ID" => $arParams["~IBLOCK_TYPE_ID"],
				"IBLOCK_ID" => $arField["LINK_IBLOCK_ID"],
				"SOCNET_GROUP_ID" => $arParams["SOCNET_GROUP_ID"],
			), $component, array("HIDE_ICONS" => "Y")
		);

		$name = $APPLICATION->IncludeComponent(
			'bitrix:main.tree.selector',
			'elements',
			array(
				"INPUT_NAME" => $FIELD_ID,
				'ONSELECT' => 'jsMLI_'.$control_id.'.AddValue',
				'MULTIPLE' => $arField["MULTIPLE"],
				'SHOW_INPUT' => 'N',
				'SHOW_BUTTON' => 'N',
				'GET_FULL_INFO' => 'Y',
				"START_TEXT" => GetMessage("CT_BLEE_START_TEXT"),
				"NO_SEARCH_RESULT_TEXT" => GetMessage("CT_BLEE_NO_SEARCH_RESULT_TEXT"),
				//These params will go throught ajax call to ajax.php in template
				"IBLOCK_TYPE_ID" => $arParams["~IBLOCK_TYPE_ID"],
				"IBLOCK_ID" => $arField["LINK_IBLOCK_ID"],
				"SOCNET_GROUP_ID" => $arParams["SOCNET_GROUP_ID"],
			), $component, array("HIDE_ICONS" => "Y")
		);
		?><a href="javascript:void(0)" onclick="<?=$name?>.SetValue([]); <?=$name?>.Show()"><?echo GetMessage('CT_BLEE_CHOOSE_ELEMENT')?></a><?

		$html = ob_get_contents();
		ob_end_clean();
		$arTabElement[] = array(
			"id"=>$FIELD_ID,
			"name"=>$arField["~NAME"],
			"required"=>$arField["IS_REQUIRED"]=="Y"? true: false,
			"type"=>'custom',
			"value"=>$html,
			"show" => $show
		);

	}
	elseif($arField["MULTIPLE"] == "Y")
	{
		$html = '<table id="tbl'.$FIELD_ID.'"><tr><td>';
		foreach($arResult["FORM_DATA"][$FIELD_ID] as $key => $value)
			$html .= '<tr><td><input type="text" name="'.$FIELD_ID.'['.$key.'][VALUE]" value="'.$value["VALUE"].'"></td></tr>';
		$html .= '</td></tr></table>';
		$html .= '<input type="button" onclick="addNewTableRow(\'tbl'.$FIELD_ID.'\', 1, /'.$FIELD_ID.'\[(n)([0-9]*)\]/g, 2)" value="'.GetMessage("CT_BLEE_ADD_BUTTON").'">';

		$arTabElement[] = array(
			"id"=>$FIELD_ID,
			"name"=>$arField["~NAME"],
			"required"=>$arField["IS_REQUIRED"]=="Y"? true: false,
			"type"=>"custom",
			"value"=>$html,
			"show" => $show
		);
	}
	elseif(is_array($arResult["FORM_DATA"][$FIELD_ID]) && array_key_exists("VALUE", $arResult["FORM_DATA"][$FIELD_ID]))
	{
		$arTabElement[] = array(
			"id"=>$FIELD_ID.'[VALUE]',
			"name"=>$arField["~NAME"],
			"required"=>$arField["IS_REQUIRED"]=="Y"? true: false,
			"type" => "text",
			"value" => $arResult["FORM_DATA"][$FIELD_ID]["VALUE"],
			"show" => $show
		);
	}
	else
	{
		$arTabElement[] = array(
			"id"=>$FIELD_ID,
			"name"=>$arField["~NAME"],
			"required"=>$arField["IS_REQUIRED"]=="Y"? true: false,
			"type" => "text",
			"show" => $show
		);
	}
}

function connectionHtmlEditor($fieldId, $fieldNameForHtml, $params, $content)
{
	$html = '';
	if (CModule::includeModule('fileman'))
	{
		ob_start();
		$editor = new CHTMLEditor;
		$res = array(
			'name' => $fieldNameForHtml,
			'inputName' => $fieldNameForHtml,
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

$arTabSection = array(
	array(
		"id"=>"IBLOCK_SECTION_ID",
		"name"=>$arResult["IBLOCK"]["SECTIONS_NAME"],
		"type"=>'list',
		"items"=>$arResult["LIST_SECTIONS"],
		"params"=>array("size"=>15),
	),
);

$arTabs = array(
	array("id"=>"tab_el", "name"=>$arResult["IBLOCK"]["ELEMENT_NAME"], "icon"=>"", "fields"=>$arTabElement),
	array("id"=>"tab_se", "name"=>$arResult["IBLOCK"]["SECTION_NAME"], "icon"=>"", "fields"=>$arTabSection),
);

$custom_html = "";

if(CModule::IncludeModule("bizproc") && ($arResult["IBLOCK"]["BIZPROC"] != "N"))
{
	$arCurrentUserGroups = $GLOBALS["USER"]->GetUserGroupArray();
	if(!$arResult["ELEMENT_FIELDS"] || $arResult["ELEMENT_FIELDS"]["CREATED_BY"] == $GLOBALS["USER"]->GetID())
	{
		$arCurrentUserGroups[] = "Author";
	}

	$DOCUMENT_TYPE = "iblock_".$arResult["IBLOCK_ID"];
	CBPDocument::AddShowParameterInit("iblock", "only_users", $DOCUMENT_TYPE);

	$arTab2Fields = array();
	$arTab2Fields[] = array(
		"id" => "BIZPROC_WF_STATUS",
		"name" => GetMessage("CT_BLEE_BIZPROC_PUBLISHED"),
		"type" => "label",
		"value" => $arResult["ELEMENT_FIELDS"]["BP_PUBLISHED"]=="Y"? GetMessage("MAIN_YES"): GetMessage("MAIN_NO")
	);

	$bizProcIndex = 0;
	$arDocumentStates = CBPDocument::GetDocumentStates(
		BizprocDocument::generateDocumentComplexType($arParams["IBLOCK_TYPE_ID"], $arResult["IBLOCK_ID"]),
		($arResult["ELEMENT_ID"] > 0) ? BizprocDocument::getDocumentComplexId(
			$arParams["IBLOCK_TYPE_ID"], $arResult["ELEMENT_ID"]) : null,
		"Y"
	);

	$custom_html .= '<input type="hidden" name="stop_bizproc" id="stop_bizproc" value="">';

	$runtime = CBPRuntime::GetRuntime();
	$runtime->StartRuntime();
	$documentService = $runtime->GetService("DocumentService");

	foreach ($arDocumentStates as $arDocumentState)
	{
		$bizProcIndex++;

		if ($arResult["ELEMENT_ID"] > 0)
		{
			$canViewWorkflow = CBPDocument::CanUserOperateDocument(
				CBPCanUserOperateOperation::ViewWorkflow,
				$GLOBALS["USER"]->GetID(),
				BizprocDocument::getDocumentComplexId($arParams["IBLOCK_TYPE_ID"], $arResult["ELEMENT_ID"]),
				array("AllUserGroups" => $arCurrentUserGroups, "DocumentStates" => $arDocumentStates, "WorkflowId" => $arDocumentState["ID"])
			);
		}
		else
		{
			$canViewWorkflow = CBPDocument::CanUserOperateDocumentType(
				CBPCanUserOperateOperation::StartWorkflow,
				$GLOBALS["USER"]->GetID(),
				BizprocDocument::generateDocumentComplexType($arParams["IBLOCK_TYPE_ID"], $arResult["IBLOCK_ID"]),
				array("sectionId"=> intval($arResult["SECTION_ID"]), "AllUserGroups" => $arCurrentUserGroups, "DocumentStates" => $arDocumentStates, "WorkflowId" => $arDocumentState["ID"])
			);
		}

		if($canViewWorkflow)
		{
			$arTab2Fields[] = array(
				"id" => "BIZPROC_TITLE".$bizProcIndex,
				"name" => $arDocumentState["TEMPLATE_NAME"],
				"type" => "section",
			);

			if(
				$arParams["IBLOCK_TYPE_ID"] != COption::GetOptionString("lists", "livefeed_iblock_type_id") &&
				strlen($arDocumentState["ID"]) &&
				CIBlockElementRights::UserHasRightTo($arResult["IBLOCK_ID"], $arResult["ELEMENT_ID"], "element_rights_edit") &&
				strlen($arDocumentState["WORKFLOW_STATUS"])
			)
			{
				$arTab2Fields[] = array(
					"id" => "BIZPROC_STOP".$bizProcIndex,
					"name" => GetMessage("CT_BLEE_BIZPROC_STOP_LABEL"),
					"type" => "label",
					"value" => '<a href="javascript:jsStopBP(\''.CUtil::JSEscape('form_'.$arResult["FORM_ID"]).'\', \''.CUtil::JSEscape($arDocumentState["ID"]).'\');">'.GetMessage("CT_BLEE_BIZPROC_STOP").'</a>',
				);
			}

			$arTab2Fields[] = array(
				"id" => "BIZPROC_NAME".$bizProcIndex,
				"name" => GetMessage("CT_BLEE_BIZPROC_NAME"),
				"type" => "label",
				"value" => $arDocumentState["TEMPLATE_NAME"],
			);

			if($arDocumentState["TEMPLATE_DESCRIPTION"]!='')
				$arTab2Fields[] = array(
					"id" => "BIZPROC_DESC".$bizProcIndex,
					"name" => GetMessage("CT_BLEE_BIZPROC_DESC"),
					"type" => "label",
					"value" => $arDocumentState["TEMPLATE_DESCRIPTION"],
				);

			if(strlen($arDocumentState["STATE_MODIFIED"]))
				$arTab2Fields[] = array(
					"id" => "BIZPROC_DATE".$bizProcIndex,
					"name" => GetMessage("CT_BLEE_BIZPROC_DATE"),
					"type" => "label",
					"value" => $arDocumentState["STATE_MODIFIED"],
				);

			if(strlen($arDocumentState["STATE_NAME"]))
			{
				$url = str_replace(
					array("#list_id#", "#document_state_id#", "#group_id#"),
					array($arResult["IBLOCK_ID"], $arDocumentState["ID"], $arParams["SOCNET_GROUP_ID"]),
					$arParams["~BIZPROC_LOG_URL"]
				);

				if(strlen($arDocumentState["ID"]))
				{
					$arTab2Fields[] = array(
						"id" => "BIZPROC_STATE".$bizProcIndex,
						"name" => GetMessage("CT_BLEE_BIZPROC_STATE"),
						"type" => "label",
						"value" => '<a href="'.htmlspecialcharsbx($url).'">'.(strlen($arDocumentState["STATE_TITLE"])? $arDocumentState["STATE_TITLE"] : $arDocumentState["STATE_NAME"]).'</a>',
					);

					$canDeleteWorkflow = false;
					if($arParams["IBLOCK_TYPE_ID"] != COption::GetOptionString("lists", "livefeed_iblock_type_id"))
					{
						$canDeleteWorkflow = CBPDocument::CanUserOperateDocumentType(
							CBPCanUserOperateOperation::CreateWorkflow,
							$GLOBALS["USER"]->GetID(),
							BizprocDocument::getDocumentComplexId($arParams["IBLOCK_TYPE_ID"], $arResult["ELEMENT_ID"]),
							array("UserGroups" => $arCurrentUserGroups)
						);
					}

					if ($canDeleteWorkflow)
					{
						$back_url = CHTTP::urlAddParams(
							$APPLICATION->GetCurPageParam("", array("lists_element_edit_active_tab")),
							array("lists_element_edit_active_tab" => "tab_bp")
						);
						$url = CHTTP::urlAddParams(str_replace(
								array("#list_id#", "#section_id#", "#element_id#", "#group_id#"),
								array($arResult["IBLOCK_ID"], intval($arResult["SECTION_ID"]), $arResult["ELEMENT_ID"], $arParams["SOCNET_GROUP_ID"]),
								$arParams["~BIZPROC_WORKFLOW_DELETE_URL"]
							),
							array("id" => $arDocumentState["ID"], "back_url" => $back_url, "sessid" => bitrix_sessid()),
							array("skip_empty" => true, "encode" => true)
						);
						$arTab2Fields[] = array(
							"id" => "BIZPROC_DELETE".$bizProcIndex,
							"name" => GetMessage("CT_BLEE_BIZPROC_DELETE_LABEL"),
							"type" => "label",
							"value" => '<a href="'.htmlspecialcharsbx($url).'">'.GetMessage("CT_BLEE_BIZPROC_DELETE").'</a>',
						);
					}
				}
				else
				{
					$arTab2Fields[] = array(
						"id" => "BIZPROC_STATE".$bizProcIndex,
						"name" => GetMessage("CT_BLEE_BIZPROC_STATE"),
						"type" => "label",
						"value" => (strlen($arDocumentState["STATE_TITLE"])? $arDocumentState["STATE_TITLE"] : $arDocumentState["STATE_NAME"]),
					);
				}
			}

			$templateId = intval($arDocumentState["TEMPLATE_ID"]);
			$arWorkflowParameters = $arDocumentState["TEMPLATE_PARAMETERS"];
			if(!is_array($arWorkflowParameters))
				$arWorkflowParameters = array();
			$formName = $arResult["form_id"];
			$bVarsFromForm = $arResult["VARS_FROM_FORM"];
			if(strlen($arDocumentState["ID"]) <= 0 && $templateId > 0)
			{
				$arParametersValues = array();
				$keys = array_keys($arWorkflowParameters);
				foreach ($keys as $key)
				{
					$v = ($bVarsFromForm ? $_REQUEST["bizproc".$templateId."_".$key] : $arWorkflowParameters[$key]["Default"]);
					if (!is_array($v))
					{
						$arParametersValues[$key] = htmlspecialcharsbx($v);
					}
					else
					{
						$keys1 = array_keys($v);
						foreach ($keys1 as $key1)
							$arParametersValues[$key][$key1] = htmlspecialcharsbx($v[$key1]);
					}
				}

				foreach ($arWorkflowParameters as $parameterKey => $arParameter)
				{
					$parameterKeyExt = "bizproc".$templateId."_".$parameterKey;

					$html = $documentService->GetFieldInputControl(
						BizprocDocument::generateDocumentComplexType($arParams["IBLOCK_TYPE_ID"], $arResult["IBLOCK_ID"]),
						$arParameter,
						array("Form" => "start_workflow_form1", "Field" => $parameterKeyExt),
						$arParametersValues[$parameterKey],
						false,
						true
					);

					$arTab2Fields[] = array(
						"id" => $parameterKeyExt.$bizProcIndex,
						"required" => $arParameter["Required"],
						"name" => $arParameter["Name"],
						"title" => $arParameter["Description"],
						"type" => "label",
						"value" => $html,
					);
				}
			}

			$arEvents = CBPDocument::GetAllowableEvents($GLOBALS["USER"]->GetID(), $arCurrentUserGroups, $arDocumentState);
			if(count($arEvents))
			{
				$html = '';
				$html .= '<input type="hidden" name="bizproc_id_'.$bizProcIndex.'" value="'.$arDocumentState["ID"].'">';
				$html .= '<input type="hidden" name="bizproc_template_id_'.$bizProcIndex.'" value="'.$arDocumentState["TEMPLATE_ID"].'">';
				$html .= '<select name="bizproc_event_'.$bizProcIndex.'">';
				$html .= '<option value="">'.GetMessage("CT_BLEE_BIZPROC_RUN_CMD_NO").'</option>';
				foreach ($arEvents as $e)
				{
					$html .= '<option value="'.htmlspecialcharsbx($e["NAME"]).'"'.($_REQUEST["bizproc_event_".$bizProcIndex] == $e["NAME"]? " selected": "").'>'.htmlspecialcharsbx($e["TITLE"]).'</option>';
				}
				$html .='</select>';

				$arTab2Fields[] = array(
					"id" => "BIZPROC_RUN_CMD".$bizProcIndex,
					"name" => GetMessage("CT_BLEE_BIZPROC_RUN_CMD"),
					"type" => "label",
					"value" => $html,
				);
			}

			if(strlen($arDocumentState["ID"]))
			{
				$arTasks = CBPDocument::GetUserTasksForWorkflow($GLOBALS["USER"]->GetID(), $arDocumentState["ID"]);
				if(count($arTasks) > 0)
				{
					$html = '';
					foreach($arTasks as $arTask)
					{
						$back_url = CHTTP::urlAddParams(
							$APPLICATION->GetCurPageParam("", array("lists_element_edit_active_tab")),
							array("lists_element_edit_active_tab" => "tab_bp")
						);

						$url = CHTTP::urlAddParams(str_replace(
								array("#list_id#", "#section_id#", "#element_id#", "#task_id#", "#group_id#"),
								array($arResult["IBLOCK_ID"], intval($arResult["SECTION_ID"]), $arResult["ELEMENT_ID"], $arTask["ID"], $arParams["SOCNET_GROUP_ID"]),
								$arParams["~BIZPROC_TASK_URL"]
							),
							array("back_url" => $back_url),
							array("skip_empty" => true, "encode" => true)
						);

						$html .= '<a href="'.htmlspecialcharsbx($url).'" title="'.strip_tags($arTask["DESCRIPTION"]).'">'.$arTask["NAME"].'</a><br />';
					}

					$arTab2Fields[] = array(
						"id" => "BIZPROC_TASKS".$bizProcIndex,
						"name" => GetMessage("CT_BLEE_BIZPROC_TASKS"),
						"type" => "label",
						"value" => $html,
					);
				}
			}
		}
	}

	if(!$bizProcIndex)
	{
		$arTab2Fields[] = array(
			"id" => "BIZPROC_NO",
			"name" => GetMessage("CT_BLEE_BIZPROC_NA_LABEL"),
			"type" => "label",
			"value" => GetMessage("CT_BLEE_BIZPROC_NA")
		);
	}

	$custom_html .= '<input type="hidden" name="bizproc_index" value="'.$bizProcIndex.'">';

	if($arResult["ELEMENT_ID"])
	{
		$bStartWorkflowPermission = CBPDocument::CanUserOperateDocument(
			CBPCanUserOperateOperation::StartWorkflow,
			$USER->GetID(),
			BizprocDocument::getDocumentComplexId($arParams["IBLOCK_TYPE_ID"], $arResult["ELEMENT_ID"]),
			array("AllUserGroups" => $arCurrentUserGroups, "DocumentStates" => $arDocumentStates, "WorkflowId" => $arDocumentState["TEMPLATE_ID"])
		);
		if($bStartWorkflowPermission)
		{
			$arTab2Fields[] = array(
				"id" => "BIZPROC_NEW",
				"name" => GetMessage("CT_BLEE_BIZPROC_NEW"),
				"type" => "section",
			);

			$back_url = CHTTP::urlAddParams(
				$APPLICATION->GetCurPageParam("", array("lists_element_edit_active_tab")),
				array("lists_element_edit_active_tab" => "tab_bp")
			);

			$url = CHTTP::urlAddParams(str_replace(
					array("#list_id#", "#section_id#", "#element_id#", "#group_id#"),
					array($arResult["IBLOCK_ID"], intval($arResult["SECTION_ID"]), $arResult["ELEMENT_ID"], $arParams["SOCNET_GROUP_ID"]),
					$arParams["~BIZPROC_WORKFLOW_START_URL"]
				),
				array("back_url" => $back_url, "sessid" => bitrix_sessid()),
				array("skip_empty" => true, "encode" => true)
			);

			$arTab2Fields[] = array(
				"id" => "BIZPROC_NEW_START",
				"name" => GetMessage("CT_BLEE_BIZPROC_START"),
				"type" => "custom",
				"colspan" => true,
				"value" => '<a href="'.htmlspecialcharsbx($url).'">'.GetMessage("CT_BLEE_BIZPROC_START").'</a>',
			);
		}
	}

	$arTabs[] = array("id"=>"tab_bp", "name"=>GetMessage("CT_BLEE_BIZPROC_TAB"), "icon"=>"", "fields"=>$arTab2Fields);
}

if(isset($arResult["RIGHTS"]))
{
	ob_start();
	IBlockShowRights(
		/*$entity_type=*/'element',
		/*$iblock_id=*/$arResult["IBLOCK_ID"],
		/*$id=*/$arResult["ELEMENT_ID"],
		/*$section_title=*/"",
		/*$variable_name=*/"RIGHTS",
		/*$arPossibleRights=*/$arResult["TASKS"],
		/*$arActualRights=*/$arResult["RIGHTS"],
		/*$bDefault=*/true,
		/*$bForceInherited=*/$arResult["ELEMENT_ID"] <= 0,
		/*$arSelected=*/$arResult["SELECTED"],
		/*$arHighLight=*/$arResult["HIGHLIGHT"]
	);
	$rights_html = ob_get_contents();
	ob_end_clean();

	$rights_fields = array(
		array(
			"id"=>"RIGHTS",
			"name"=>GetMessage("CT_BLEE_ACCESS_RIGHTS"),
			"type"=>"custom",
			"colspan"=>true,
			"value"=>$rights_html,
		),
	);
	$arTabs[] = array(
		"id"=>"tab_rights",
		"name"=>GetMessage("CT_BLEE_TAB_ACCESS"),
		"icon"=>"",
		"fields"=>$rights_fields,
	);
}

$custom_html .= '<input type="hidden" name="action" id="action" value="">';
if(!$arParams["CAN_EDIT"])
	$custom_html .= '<input type="button" value="'.GetMessage("CT_BLEE_FORM_CANCEL").'" name="cancel" onclick="window.location=\''.htmlspecialcharsbx(CUtil::addslashes($arResult["~LIST_SECTION_URL"])).'\'" title="'.GetMessage("CT_BLEE_FORM_CANCEL_TITLE").'" />';


$APPLICATION->IncludeComponent(
	"bitrix:main.interface.form",
	"",
	array(
		"FORM_ID"=>$arResult["FORM_ID"],
		"TABS"=>$arTabs,
		"BUTTONS"=>array(
			"standard_buttons" => $arParams["CAN_EDIT"],
			"back_url"=>$arResult["~LIST_SECTION_URL"],
			"custom_html"=>$custom_html,
		),
		"DATA"=>$arResult["FORM_DATA"],
		"SHOW_SETTINGS"=>"N",
		"THEME_GRID_ID"=>$arResult["GRID_ID"],
	),
	$component, array("HIDE_ICONS" => "Y")
);
?>