<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class ListExportExcelComponent extends CBitrixComponent
{
	protected $arIBlock = array();

	/* Processing of input parameter */
	public function onPrepareComponentParams($params)
	{
		$this->arIBlock = CIBlock::GetArrayByID($params["IBLOCK_ID"]);
		$this->arResult["IBLOCK"] = htmlspecialcharsex($this->arIBlock);
		$this->arResult["IBLOCK_ID"] = $this->arIBlock["ID"];
		$this->arResult["GRID_ID"] = "lists_list_elements_".$this->arResult["IBLOCK_ID"];
		$this->arResult["ANY_SECTION"] = isset($_GET["list_section_id"]) && strlen($_GET["list_section_id"]) == 0;
		$this->arResult["SECTIONS"] = array();
		$this->arResult["SECTION_ID"] = false;
		$this->arResult["LIST_SECTIONS"] = array();
		if (isset($_GET["list_section_id"]))
			$sectionId = intval($_GET["list_section_id"]);
		else
			$sectionId = intval($params["SECTION_ID"]);

		$rsSections = CIBlockSection::GetList(
			array("left_margin" => "asc"),
			array(
				"IBLOCK_ID"         => $this->arIBlock["ID"],
				"GLOBAL_ACTIVE"     => "Y",
				"CHECK_PERMISSIONS" => "Y",
			)
		);
		while ($arSection = $rsSections->GetNext())
		{
			$this->arResult["SECTIONS"][$arSection["ID"]] = array(
				"ID"   => $arSection["ID"],
				"NAME" => $arSection["NAME"]
			);
			if ($arSection["ID"] == $sectionId)
			{
				$this->arResult["SECTION"] = $arSection;
				$this->arResult["SECTION_ID"] = $arSection["ID"];
			}
			$this->arResult["LIST_SECTIONS"][$arSection["ID"]] = str_repeat(" . ", $arSection["DEPTH_LEVEL"]).$arSection["NAME"];
		}
		return $params;
	}

	/* Start Component */
	public function executeComponent()
	{
		global $APPLICATION;

		$this->setFrameMode(false);

		if (!CModule::IncludeModule('lists'))
		{
			ShowError(Loc::getMessage("CC_BLL_MODULE_NOT_INSTALLED"));

			return;
		}

		$this->createDataExcel();

		$APPLICATION->RestartBuffer();
		header("Content-Type: application/vnd.ms-excel");
		header("Content-Disposition: filename=list_".$this->arIBlock["ID"].".xls");
		$this->IncludeComponentTemplate();
		$r = $APPLICATION->EndBufferContentMan();
		echo $r;
		include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
		die();
	}

	/* Create an dataArray to excel */
	protected function createDataExcel()
	{
		$obList = new CList($this->arIBlock["ID"]);
		$gridOptions = new CGridOptions($this->arResult["GRID_ID"]);
		$gridColumns = $gridOptions->GetVisibleColumns();
		$gridSort = $gridOptions->GetSorting(array("sort" => array("name" => "asc")));

		$this->arResult["ELEMENTS_HEADERS"] = array();
		$arSelect = array("ID", "IBLOCK_ID");
		$arProperties = array();

		$this->arResult["FIELDS"] = $arListFields = $obList->GetFields();
		foreach ($arListFields as $fieldId => $arField)
		{
			if (!count($gridColumns) || in_array($fieldId, $gridColumns))
			{
				if (substr($fieldId, 0, 9) == "PROPERTY_")
					$arProperties[] = $fieldId;
				else
					$arSelect[] = $fieldId;
			}

			if ($fieldId == "CREATED_BY")
				$arSelect[] = "CREATED_USER_NAME";

			if ($fieldId == "MODIFIED_BY")
				$arSelect[] = "USER_NAME";

			$this->arResult["ELEMENTS_HEADERS"][$fieldId] = array(
				"name"  => htmlspecialcharsex($arField["NAME"]),
				"default" => true,
				"sort" => $arField["MULTIPLE"] == "Y" ? "" : $fieldId,
			);
		}

		if (!count($gridColumns) || in_array("IBLOCK_SECTION_ID", $gridColumns))
		{
			$arSelect[] = "IBLOCK_SECTION_ID";
		}
		$this->arResult["ELEMENTS_HEADERS"]["IBLOCK_SECTION_ID"] = array(
			"name" => Loc::getMessage("CC_BLL_COLUMN_SECTION"),
			"default" => true,
			"sort" => false,
		);

		/* FILTER */
		$sections = array();
		foreach ($this->arResult["LIST_SECTIONS"] as $id => $name)
			$sections[$id] = $name;

		$this->arResult["FILTER"] = array(
			array(
				"id" => "list_section_id",
				"type" => "list",
				"items" => $sections,
				"filtered" => $this->arResult["SECTION_ID"] !== false,
				"filter_value" => $this->arResult["SECTION_ID"],
				"value" => $this->arResult["SECTION_ID"],
			),
		);

		$i = 1;
		$arFilterable = array();
		$arDateFilter = array();
		foreach ($arListFields as $fieldId => $arField)
		{
			if (
				$arField["TYPE"] == "ACTIVE_FROM"
				|| $arField["TYPE"] == "ACTIVE_TO"
			)
			{
				$this->arResult["FILTER"][$i] = array(
					"id" => "DATE_".$fieldId,
					"name" => htmlspecialcharsex($arField["NAME"]),
					"type" => "date",
				);
				$arFilterable["DATE_".$fieldId] = "";
				$arDateFilter["DATE_".$fieldId] = true;
			}
			elseif (
				$arField["TYPE"] == "DATE_CREATE"
				|| $arField["TYPE"] == "TIMESTAMP_X"
			)
			{
				$this->arResult["FILTER"][$i] = array(
					"id" => $fieldId,
					"name" => htmlspecialcharsex($arField["NAME"]),
					"type" => "date",
				);
				$arFilterable[$fieldId] = "";
				$arDateFilter[$fieldId] = true;
			}
			elseif (is_array($arField["PROPERTY_USER_TYPE"]) && array_key_exists("GetPublicFilterHTML", $arField["PROPERTY_USER_TYPE"]))
			{
				$this->arResult["FILTER"][$i] = array(
					"id" => $fieldId,
					"name" => htmlspecialcharsex($arField["NAME"]),
					"type" => "custom",
					"enable_settings" => false,
					"value" => call_user_func_array($arField["PROPERTY_USER_TYPE"]["GetPublicFilterHTML"], array(
						$arField,
						array(
							"VALUE" => $fieldId,
							"FORM_NAME" => "filter_".$this->arResult["GRID_ID"],
							"GRID_ID" => $this->arResult["GRID_ID"],
						),
					)),
				);
				$arFilterable[$fieldId] = "";
			}
			elseif ($arField["TYPE"] == "SORT" || $arField["TYPE"] == "N")
			{
				$this->arResult["FILTER"][$i] = array(
					"id" => $fieldId,
					"name" => htmlspecialcharsex($arField["NAME"]),
					"type" => "number",
				);
				$arFilterable[$fieldId] = "";
			}
			elseif ($arField["TYPE"] == "G")
			{
				$items = array();
				$prop_secs = CIBlockSection::GetList(array("left_margin" => "asc"), array("IBLOCK_ID" => $arField["LINK_IBLOCK_ID"]));
				while ($ar_sec = $prop_secs->Fetch())
					$items[$ar_sec["ID"]] = str_repeat(". ", $ar_sec["DEPTH_LEVEL"] - 1).$ar_sec["NAME"];

				$this->arResult["FILTER"][$i] = array(
					"id" => $fieldId,
					"name" => htmlspecialcharsex($arField["NAME"]),
					"type" => "list",
					"items" => $items,
					"params" => array("size" => 5, "multiple" => "multiple"),
					"valign" => "top",
				);
				$arFilterable[$fieldId] = "";
			}
			elseif ($arField["TYPE"] == "E")
			{
				//Should be handled in template
				$this->arResult["FILTER"][$i] = array(
					"id" => $fieldId,
					"name" => htmlspecialcharsex($arField["NAME"]),
					"type" => "E",
					"value" => $arField,
				);
				$arFilterable[$fieldId] = "";
			}
			elseif ($arField["TYPE"] == "L")
			{
				$items = array();
				$propEnums = CIBlockProperty::GetPropertyEnum($arField["ID"]);
				while ($arEnum = $propEnums->Fetch())
					$items[$arEnum["ID"]] = $arEnum["VALUE"];

				$this->arResult["FILTER"][$i] = array(
					"id" => $fieldId,
					"name" => htmlspecialcharsex($arField["NAME"]),
					"type" => "list",
					"items" => $items,
					"params" => array("size" => 5, "multiple" => "multiple"),
					"valign" => "top",
				);
				$arFilterable[$fieldId] = "";
			}
			elseif (in_array($arField["TYPE"], array("S", "S:HTML", "NAME", "DETAIL_TEXT", "PREVIEW_TEXT")))
			{
				$this->arResult["FILTER"][$i] = array(
					"id" => $fieldId,
					"name" => htmlspecialcharsex($arField["NAME"]),
				);
				$arFilterable[$fieldId] = "?";
			}
			else
			{
				$this->arResult["FILTER"][$i] = array(
					"id" => $fieldId,
					"name" => htmlspecialcharsex($arField["NAME"]),
				);
				$arFilterable[$fieldId] = "";
			}

			$i++;
		}

		$arFilter = array();
		$gridFilter = $gridOptions->GetFilter($this->arResult["FILTER"]);
		foreach ($gridFilter as $key => $value)
		{
			if (substr($key, -5) == "_from")
			{
				$op = ">=";
				$newKey = substr($key, 0, -5);
			}
			elseif (substr($key, -3) == "_to")
			{
				$op = "<=";
				$newKey = substr($key, 0, -3);
				if (array_key_exists($newKey, $arDateFilter))
				{
					if (!preg_match("/\\d\\d:\\d\\d:\\d\\d\$/", $value))
						$value .= " 23:59:59";
				}
			}
			else
			{
				$op = "";
				$newKey = $key;
			}
			if (array_key_exists($newKey, $arFilterable))
			{
				if ($op == "")
					$op = $arFilterable[$newKey];
				$arFilter[$op.$newKey] = $value;
			}
		}

		$arFilter["IBLOCK_ID"] = $this->arIBlock["ID"];
		if (!$this->arResult["ANY_SECTION"])
			$arFilter["SECTION_ID"] = $this->arResult["SECTION_ID"];

		$rsElements = CIBlockElement::GetList(
			$gridSort["sort"], $arFilter, false, false, $arSelect
		);

		$this->arResult["EXCEL_COLUMN_NAME"] = array();
		$this->arResult["EXCEL_CELL_VALUE"] = array();
		$count = 0;
		while ($obElement = $rsElements->GetNextElement())
		{
			$data = $obElement->GetFields();
			$propertyArray = $obElement->GetProperties();
			if (!empty($arProperties))
			{
				foreach ($propertyArray as $arProp)
				{
					$fieldId = "PROPERTY_".$arProp["ID"];
					if (in_array($fieldId, $arProperties))
					{
						$arField = $this->arResult["FIELDS"][$fieldId];

						if (is_array($arField["PROPERTY_USER_TYPE"]) && is_array($arField["PROPERTY_USER_TYPE"]["GetPublicViewHTML"]))
						{
							$data[$fieldId] = call_user_func_array($arField["PROPERTY_USER_TYPE"]["GetPublicViewHTML"], array(
								$arField,
								array("VALUE" => $arProp["~VALUE"]),
								array(),
							));
						}
						elseif ($arField["PROPERTY_TYPE"] == "L")
						{
							$data[$fieldId] = htmlspecialcharsex($arProp["VALUE_ENUM"]);
						}
						elseif ($arField["PROPERTY_TYPE"] == "F")
						{
							$files = is_array($arProp["VALUE"]) ? $arProp["VALUE"] : array($arProp["VALUE"]);
							foreach ($files as $file)
							{
								$value = CFile::MakeFileArray($file);
								$data[$fieldId] .= $value["name"]."\r\n";
							}
						}
						else
						{
							$data[$fieldId] = htmlspecialcharsex($arProp["VALUE"]);
						}
					}
				}
				if (!empty($data["IBLOCK_SECTION_ID"]))
				{
					if (array_key_exists($data["IBLOCK_SECTION_ID"], $this->arResult["SECTIONS"]))
					{
						$data["IBLOCK_SECTION_ID"] = $this->arResult["SECTIONS"][$data["IBLOCK_SECTION_ID"]]["NAME"];
					}
				}
				if(in_array("BIZPROC", $gridColumns))
					$data["BIZPROC"] = $this->getArrayBizproc($data);
			}

			if (isset($data["CREATED_BY"]))
				$data["CREATED_BY"] = "[".$data["CREATED_BY"]."] ".$data["CREATED_USER_NAME"];

			if (isset($data["MODIFIED_BY"]))
				$data["MODIFIED_BY"] = "[".$data["MODIFIED_BY"]."] ".$data["USER_NAME"];
			if (isset($data["ACTIVE_FROM"]))
				$data['ACTIVE_FROM'] = FormatDateFromDB($data['ACTIVE_FROM']);
			if (isset($data["ACTIVE_TO"]))
				$data['ACTIVE_TO'] = FormatDateFromDB($data['ACTIVE_TO']);
			if (isset($data["DATE_CREATE"]))
				$data['DATE_CREATE'] = FormatDateFromDB($data['DATE_CREATE']);
			if (isset($data["TIMESTAMP_X"]))
				$data['TIMESTAMP_X'] = FormatDateFromDB($data['TIMESTAMP_X']);

			foreach ($gridColumns as $position => $id)
			{
				$this->arResult["EXCEL_CELL_VALUE"][$count][$position] = $data[$id];
				$this->arResult["EXCEL_COLUMN_NAME"][$position] = $this->arResult["ELEMENTS_HEADERS"][$id]["name"];
			}
			$count++;
		}
	}

	/* Data business process */
	protected function getArrayBizproc($data = array())
	{
		$currentUserId = $GLOBALS["USER"]->GetID();

		$html = "";

		if ($this->arResult["IBLOCK"]["BIZPROC"] == "Y" && CModule::IncludeModule('bizproc'))
		{
			$this->arResult["ELEMENTS_HEADERS"]["BIZPROC"] = array(
				"name" => Loc::getMessage("CC_BLL_COLUMN_BIZPROC"),
				"default" => true,
				"sort" => false,
			);

			$arDocumentStates = CBPDocument::GetDocumentStates(
				BizprocDocument::generateDocumentComplexType($this->arParams["IBLOCK_TYPE_ID"], $this->arResult["IBLOCK_ID"]),
				BizprocDocument::getDocumentComplexId($this->arParams["IBLOCK_TYPE_ID"], $data["ID"])
			);

			$userGroups = $GLOBALS["USER"]->GetUserGroupArray();
			if ($data["~CREATED_BY"] == $currentUserId)
				$userGroups[] = "Author";

			$ii = 0;
			foreach ($arDocumentStates as $workflowId => $workflowState)
			{
				if (strlen($workflowState["TEMPLATE_NAME"]) > 0)
					$html .= "".$workflowState["TEMPLATE_NAME"].":\r\n";
				else
					$html .= "".(++$ii).":\r\n";

				$html .= "".(strlen($workflowState["STATE_TITLE"]) > 0 ? $workflowState["STATE_TITLE"] : $workflowState["STATE_NAME"])."\r\n";
			}
		}

		return $html;
	}
}