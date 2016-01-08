<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

if (!Loader::includeModule('bizproc'))
{
	return;
}

class BizprocDocument extends CIBlockDocument
{
	const DOCUMENT_TYPE_PREFIX = 'iblock_';
	private static $cachedTasks;

	public static function generateDocumentType($iblockId)
	{
		$iblockId = (int)$iblockId;
		return self::DOCUMENT_TYPE_PREFIX . $iblockId;
	}


	public static function generateDocumentComplexType($iblockType, $iblockId)
	{
		if($iblockType == COption::GetOptionString("lists", "livefeed_iblock_type_id"))
			return array('lists', 'BizprocDocument', self::generateDocumentType($iblockId));
		else
			return array('iblock', 'CIBlockDocument', self::generateDocumentType($iblockId));
	}

	public static function getDocumentComplexId($iblockType, $documentId)
	{
		if($iblockType == COption::GetOptionString("lists", "livefeed_iblock_type_id"))
			return array('lists', 'BizprocDocument', $documentId);
		else
			return array('iblock', 'CIBlockDocument', $documentId);
	}

	public function OnAfterIBlockElementDelete($fields)
	{
		$errors = array();
		if(Loader::includeModule('socialnetwork'))
		{
			$states = CBPStateService::getDocumentStates(array('lists', 'BizprocDocument', $fields['ID']));
			foreach ($states as $workflowId => $state)
			{
				$sourceId = CBPStateService::getWorkflowIntegerId($workflowId);
				$resultQuery = CSocNetLog::getList(
					array(),
					array('EVENT_ID' => 'lists_new_element', 'SOURCE_ID' => $sourceId),
					false,
					false,
					array('ID')
				);
				while ($log = $resultQuery->fetch())
				{
					CSocNetLog::Delete($log['ID']);
				}

			}
		}
		CBPDocument::OnDocumentDelete(array('lists', 'BizprocDocument', $fields['ID']), $errors);
	}

	public static function deleteDataIblock($iblockId)
	{
		$iblockId = intval($iblockId);
		$documentType = array('lists', 'BizprocDocument', self::generateDocumentType($iblockId));
		$errors = array();
		$templateObject = CBPWorkflowTemplateLoader::GetList(
			array('ID' => 'DESC'),
			array('DOCUMENT_TYPE' => $documentType),
			false,
			false,
			array('ID')
		);
		while($template = $templateObject->fetch())
		{
			CBPDocument::deleteWorkflowTemplate($template['ID'], $documentType, $errors);
		}
	}

	/**
	 * Method returns document icon (image source path)
	 * @param $documentId
	 * @return null|string
	 * @throws CBPArgumentNullException
	 */

	public static function getDocumentIcon($documentId)
	{
		$documentId = intval($documentId);
		if ($documentId <= 0)
			throw new CBPArgumentNullException('documentId');

		$db = CIBlockElement::GetList(
			array(),
			array('ID' => $documentId, 'SHOW_NEW'=>'Y', 'SHOW_HISTORY' => 'Y'),
			false,
			false,
			array('ID', 'IBLOCK_ID')
		);
		if ($element = $db->fetch())
		{
			$iblockPicture = CIBlock::GetArrayByID($element['IBLOCK_ID'], 'PICTURE');
			$imageFile = CFile::GetFileArray($iblockPicture);
			if(!empty($imageFile['SRC']))
				return $imageFile['SRC'];
		}

		return null;
	}

	/**
	 * @param string $documentId - document id.
	 * @return array - document fields array.
	 */
	public function GetDocument($documentId)
	{
		$documentId = intval($documentId);
		if ($documentId <= 0)
			throw new CBPArgumentNullException("documentId");

		$arResult = null;

		$dbDocumentList = CIBlockElement::GetList(
			array(),
			array("ID" => $documentId, "SHOW_NEW"=>"Y", "SHOW_HISTORY" => "Y")
		);
		if ($objDocument = $dbDocumentList->GetNextElement(false, true))
		{
			$arDocumentFields = $objDocument->GetFields();
			$arDocumentProperties = $objDocument->GetProperties();

			foreach ($arDocumentFields as $fieldKey => $fieldValue)
			{
				if (substr($fieldKey, 0, 1) == "~")
					continue;

				$arResult[$fieldKey] = $fieldValue;
				if (in_array($fieldKey, array("MODIFIED_BY", "CREATED_BY")))
				{
					$arResult[$fieldKey] = "user_".$fieldValue;
					$arResult[$fieldKey."_PRINTABLE"] = $arDocumentFields[($fieldKey == "MODIFIED_BY") ? "USER_NAME" : "CREATED_USER_NAME"];
				}
				elseif (in_array($fieldKey, array("PREVIEW_TEXT", "DETAIL_TEXT")))
				{
					if ($arDocumentFields[$fieldKey."_TYPE"] == "html")
						$arResult[$fieldKey] = HTMLToTxt($arDocumentFields["~".$fieldKey]);
				}
			}

			foreach ($arDocumentProperties as $propertyKey => $propertyValue)
			{
				if (strlen($propertyValue["USER_TYPE"]) > 0)
				{
					if ($propertyValue["USER_TYPE"] == "UserID"
						|| $propertyValue["USER_TYPE"] == "employee" && (COption::GetOptionString("bizproc", "employee_compatible_mode", "N") != "Y"))
					{
						$arPropertyValue = $propertyValue["VALUE"];
						$arPropertyKey = isset($propertyValue["VALUE_ENUM_ID"]) ? $propertyValue["VALUE_ENUM_ID"] : $propertyValue["PROPERTY_VALUE_ID"];
						if (!is_array($arPropertyValue))
						{
							$db = CUser::GetByID($arPropertyValue);
							if ($ar = $db->GetNext())
							{
								$arResult["PROPERTY_".$propertyKey] = "user_".intval($arPropertyValue);
								$arResult["PROPERTY_".$propertyKey."_PRINTABLE"] = "(".$ar["LOGIN"].")".((strlen($ar["NAME"]) > 0 || strlen($ar["LAST_NAME"]) > 0) ? " " : "").$ar["NAME"].((strlen($ar["NAME"]) > 0 && strlen($ar["LAST_NAME"]) > 0) ? " " : "").$ar["LAST_NAME"];
							}
						}
						else
						{
							for ($i = 0, $cnt = count($arPropertyValue); $i < $cnt; $i++)
							{
								$db = CUser::GetByID($arPropertyValue[$i]);
								if ($ar = $db->GetNext())
								{
									$arResult["PROPERTY_".$propertyKey][$arPropertyKey[$i]] = "user_".intval($arPropertyValue[$i]);
									$arResult["PROPERTY_".$propertyKey."_PRINTABLE"][$arPropertyKey[$i]] = "(".$ar["LOGIN"].")".((strlen($ar["NAME"]) > 0 || strlen($ar["LAST_NAME"]) > 0) ? " " : "").$ar["NAME"].((strlen($ar["NAME"]) > 0 && strlen($ar["LAST_NAME"]) > 0) ? " " : "").$ar["LAST_NAME"];
								}
							}
						}
					}
					else
					{
						$arResult["PROPERTY_".$propertyKey] = $propertyValue["VALUE"];
					}
				}
				elseif ($propertyValue["PROPERTY_TYPE"] == "L")
				{
					$arPropertyValue = $propertyValue["VALUE"];
					$arPropertyKey = ($propertyValue["VALUE_XML_ID"]);
					if (!is_array($arPropertyValue))
					{
						$arPropertyValue = array($arPropertyValue);
						$arPropertyKey = array($arPropertyKey);
					}

					for ($i = 0, $cnt = count($arPropertyValue); $i < $cnt; $i++)
						$arResult["PROPERTY_".$propertyKey][$arPropertyKey[$i]] = $arPropertyValue[$i];
				}
				elseif ($propertyValue["PROPERTY_TYPE"] == "F")
				{
					$arPropertyValue = $propertyValue["VALUE"];
					if (!is_array($arPropertyValue))
						$arPropertyValue = array($arPropertyValue);

					foreach ($arPropertyValue as $v)
					{
						$ar = CFile::GetFileArray($v);
						if ($ar)
						{
							$arResult["PROPERTY_".$propertyKey][intval($v)] = $ar["SRC"];
							$arResult["PROPERTY_".$propertyKey."_printable"][intval($v)] = "[url=/bitrix/tools/bizproc_show_file.php?f=".htmlspecialcharsbx($ar["FILE_NAME"])."&i=".$v."&h=".md5($ar["SUBDIR"])."]".htmlspecialcharsbx($ar["ORIGINAL_NAME"])."[/url]";
						}
					}
				}
				else
				{
					$arResult["PROPERTY_".$propertyKey] = $propertyValue["VALUE"];
				}
			}

			$documentFields = static::GetDocumentFields(static::GetDocumentType($documentId));
			foreach ($documentFields as $fieldKey => $field)
			{
				if (!array_key_exists($fieldKey, $arResult))
					$arResult[$fieldKey] = null;
			}
		}

		return $arResult;
	}

	public function GetDocumentFields($documentType)
	{
		$iblockId = intval(substr($documentType, strlen("iblock_")));
		if ($iblockId <= 0)
			throw new CBPArgumentOutOfRangeException("documentType", $documentType);

		$arDocumentFieldTypes = self::GetDocumentFieldTypes($documentType);

		$arResult = array(
			"ID" => array(
				"Name" => GetMessage("IBD_FIELD_ID"),
				"Type" => "int",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
			),
			"TIMESTAMP_X" => array(
				"Name" => GetMessage("IBD_FIELD_TIMESTAMP_X"),
				"Type" => "datetime",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"MODIFIED_BY" => array(
				"Name" => GetMessage("IBD_FIELD_MODYFIED"),
				"Type" => "user",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"MODIFIED_BY_PRINTABLE" => array(
				"Name" => GetMessage("IBD_FIELD_MODIFIED_BY_USER_PRINTABLE"),
				"Type" => "string",
				"Filterable" => false,
				"Editable" => false,
				"Required" => false,
			),
			"DATE_CREATE" => array(
				"Name" => GetMessage("IBD_FIELD_DATE_CREATE"),
				"Type" => "datetime",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"CREATED_BY" => array(
				"Name" => GetMessage("IBD_FIELD_CREATED"),
				"Type" => "user",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
			),
			"CREATED_BY_PRINTABLE" => array(
				"Name" => GetMessage("IBD_FIELD_CREATED_BY_USER_PRINTABLE"),
				"Type" => "string",
				"Filterable" => false,
				"Editable" => false,
				"Required" => false,
			),
			"IBLOCK_ID" => array(
				"Name" => GetMessage("IBD_FIELD_IBLOCK_ID"),
				"Type" => "int",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"ACTIVE" => array(
				"Name" => GetMessage("IBD_FIELD_ACTIVE"),
				"Type" => "bool",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"BP_PUBLISHED" => array(
				"Name" => GetMessage("IBD_FIELD_BP_PUBLISHED"),
				"Type" => "bool",
				"Filterable" => false,
				"Editable" => true,
				"Required" => false,
			),
			"CODE" => array(
				"Name" => GetMessage("IBD_FIELD_CODE"),
				"Type" => "string",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"XML_ID" => array(
				"Name" => GetMessage("IBD_FIELD_XML_ID"),
				"Type" => "string",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
		);

		$arKeys = array_keys($arResult);
		foreach ($arKeys as $key)
			$arResult[$key]["Multiple"] = false;

		$dbProperties = CIBlockProperty::GetList(
			array("sort" => "asc", "name" => "asc"),
			array("IBLOCK_ID" => $iblockId, 'ACTIVE' => 'Y')
		);
		$ignoreProperty = array();
		while ($arProperty = $dbProperties->Fetch())
		{
			if (strlen(trim($arProperty["CODE"])) > 0)
			{
				$key = "PROPERTY_".$arProperty["CODE"];
				$ignoreProperty["PROPERTY_".$arProperty["ID"]] = "PROPERTY_".$arProperty["CODE"];
			}
			else
			{
				$key = "PROPERTY_".$arProperty["ID"];
				$ignoreProperty["PROPERTY_".$arProperty["ID"]] = 0;
			}

			$arResult[$key] = array(
				"Name" => $arProperty["NAME"],
				"Filterable" => ($arProperty["FILTRABLE"] == "Y"),
				"Editable" => true,
				"Required" => ($arProperty["IS_REQUIRED"] == "Y"),
				"Multiple" => ($arProperty["MULTIPLE"] == "Y"),
				"TypeReal" => $arProperty["PROPERTY_TYPE"],
			);

			if (strlen($arProperty["USER_TYPE"]) > 0)
			{
				$arResult[$key]["TypeReal"] = $arProperty["PROPERTY_TYPE"].":".$arProperty["USER_TYPE"];

				if ($arProperty["USER_TYPE"] == "UserID"
					|| $arProperty["USER_TYPE"] == "employee" && (COption::GetOptionString("bizproc", "employee_compatible_mode", "N") != "Y"))
				{
					$arResult[$key]["Type"] = "user";
					$arResult[$key."_PRINTABLE"] = array(
						"Name" => $arProperty["NAME"].GetMessage("IBD_FIELD_USERNAME_PROPERTY"),
						"Filterable" => false,
						"Editable" => false,
						"Required" => false,
						"Multiple" => ($arProperty["MULTIPLE"] == "Y"),
						"Type" => "string",
					);
					$arResult[$key]["DefaultValue"] = $arProperty["DEFAULT_VALUE"];
				}
				elseif ($arProperty["USER_TYPE"] == "DateTime")
				{
					$arResult[$key]["Type"] = "datetime";
					$arResult[$key]["DefaultValue"] = $arProperty["DEFAULT_VALUE"];
				}
				elseif ($arProperty["USER_TYPE"] == "Date")
				{
					$arResult[$key]["Type"] = "date";
					$arResult[$key]["DefaultValue"] = $arProperty["DEFAULT_VALUE"];
				}
				elseif ($arProperty["USER_TYPE"] == "EList")
				{
					$arResult[$key]["Type"] = "E:EList";
					$arResult[$key]["Options"] = $arProperty["LINK_IBLOCK_ID"];
				}
				elseif ($arProperty["USER_TYPE"] == "HTML")
				{
					$arResult[$key]["Type"] = "S:HTML";
					$arResult[$key]["DefaultValue"] = $arProperty["DEFAULT_VALUE"];
				}
				else
				{
					$arResult[$key]["Type"] = "string";
					$arResult[$key]["DefaultValue"] = $arProperty["DEFAULT_VALUE"];
				}
			}
			elseif ($arProperty["PROPERTY_TYPE"] == "L")
			{
				$arResult[$key]["Type"] = "select";

				$arResult[$key]["Options"] = array();
				$dbPropertyEnums = CIBlockProperty::GetPropertyEnum($arProperty["ID"]);
				while ($arPropertyEnum = $dbPropertyEnums->GetNext())
				{
					$arResult[$key]["Options"][$arPropertyEnum["XML_ID"]] = $arPropertyEnum["VALUE"];
					if($arPropertyEnum["DEF"] == "Y")
						$arResult[$key]["DefaultValue"] = $arPropertyEnum["VALUE"];
				}
			}
			elseif ($arProperty["PROPERTY_TYPE"] == "N")
			{
				$arResult[$key]["Type"] = "int";
				$arResult[$key]["DefaultValue"] = $arProperty["DEFAULT_VALUE"];
			}
			elseif ($arProperty["PROPERTY_TYPE"] == "F")
			{
				$arResult[$key]["Type"] = "file";
				$arResult[$key."_printable"] = array(
					"Name" => $arProperty["NAME"].GetMessage("IBD_FIELD_USERNAME_PROPERTY"),
					"Filterable" => false,
					"Editable" => false,
					"Required" => false,
					"Multiple" => ($arProperty["MULTIPLE"] == "Y"),
					"Type" => "string",
				);
			}
			elseif ($arProperty["PROPERTY_TYPE"] == "S")
			{
				$arResult[$key]["Type"] = "string";
				$arResult[$key]["DefaultValue"] = $arProperty["DEFAULT_VALUE"];
			}
			else
			{
				$arResult[$key]["Type"] = "string";
				$arResult[$key]["DefaultValue"] = $arProperty["DEFAULT_VALUE"];
			}
		}

		$arKeys = array_keys($arResult);
		foreach ($arKeys as $k)
		{
			$arResult[$k]["BaseType"] = $arDocumentFieldTypes[$arResult[$k]["Type"]]["BaseType"];
			$arResult[$k]["Complex"] = $arDocumentFieldTypes[$arResult[$k]["Type"]]["Complex"];
		}

		$list = new CList($iblockId);
		$fields = $list->getFields();
		foreach($fields as $fieldId => $field)
		{
			if(empty($field["SETTINGS"]))
				$field["SETTINGS"] = array("SHOW_ADD_FORM" => 'Y', "SHOW_EDIT_FORM"=>'Y');

			if(array_key_exists($fieldId, $ignoreProperty))
			{
				$ignoreProperty[$fieldId] ? $key = $ignoreProperty[$fieldId] : $key = $fieldId;
				$arResult[$key]["sort"] =  $field["SORT"];
				$arResult[$key]["settings"] =  $field["SETTINGS"];
				if($field["ROW_COUNT"] && $field["COL_COUNT"])
				{
					$arResult[$key]["row_count"] = $field["ROW_COUNT"];
					$arResult[$key]["col_count"] = $field["COL_COUNT"];
				}
			}
			else
			{
				if (!isset($arResult[$fieldId]))
				{
					$arResult[$fieldId] = array(
						'Name' => $field['NAME'],
						'Filterable' => false,
						'Editable' => true,
						'Required' => $field['IS_REQUIRED'],
						'Multiple' => $field['MULTIPLE'],
						'Type' => $field['TYPE'],
					);
				}
				$arResult[$fieldId]["sort"] =  $field["SORT"];
				$arResult[$fieldId]["settings"] =  $field["SETTINGS"];
				if($field["ROW_COUNT"] && $field["COL_COUNT"])
				{
					$arResult[$fieldId]["row_count"] =  $field["ROW_COUNT"];
					$arResult[$fieldId]["col_count"] =  $field["COL_COUNT"];
				}
			}
		}
		
		return $arResult;
	}

	public function AddDocumentField($documentType, $arFields)
	{
		$iblockId = intval(substr($documentType, strlen("iblock_")));
		if ($iblockId <= 0)
			throw new CBPArgumentOutOfRangeException("documentType", $documentType);

		if (substr($arFields["code"], 0, strlen("PROPERTY_")) == "PROPERTY_")
			$arFields["code"] = substr($arFields["code"], strlen("PROPERTY_"));

		$arFieldsTmp = array(
			"NAME" => $arFields["name"],
			"ACTIVE" => "Y",
			"SORT" => $arFields["sort"] ? $arFields["sort"] : 900,
			"CODE" => $arFields["code"],
			'MULTIPLE' => $arFields['multiple'] == 'Y' || (string)$arFields['multiple'] === '1' ? 'Y' : 'N',
			'IS_REQUIRED' => $arFields['required'] == 'Y' || (string)$arFields['required'] === '1' ? 'Y' : 'N',
			"IBLOCK_ID" => $iblockId,
			"FILTRABLE" => "Y",
			"SETTINGS" => $arFields["settings"] ? $arFields["settings"] : array("SHOW_ADD_FORM" => 'Y', "SHOW_EDIT_FORM"=>'Y'),
			"DEFAULT_VALUE" => $arFields['DefaultValue']
		);

		if (strpos("0123456789", substr($arFieldsTmp["CODE"], 0, 1))!==false)
			unset($arFieldsTmp["CODE"]);

		if (array_key_exists("additional_type_info", $arFields))
			$arFieldsTmp["LINK_IBLOCK_ID"] = intval($arFields["additional_type_info"]);

		if (strstr($arFields["type"], ":") !== false)
		{
			list($arFieldsTmp["TYPE"], $arFieldsTmp["USER_TYPE"]) = explode(":", $arFields["type"], 2);
			if ($arFields["type"] == "E:EList")
				$arFieldsTmp["LINK_IBLOCK_ID"] = $arFields["options"];
		}
		elseif ($arFields["type"] == "user")
		{
			$arFieldsTmp["TYPE"] = "S:employee";
			$arFieldsTmp["USER_TYPE"]= "UserID";
		}
		elseif ($arFields["type"] == "date")
		{
			$arFieldsTmp["TYPE"] = "S:Date";
			$arFieldsTmp["USER_TYPE"]= "Date";
		}
		elseif ($arFields["type"] == "datetime")
		{
			$arFieldsTmp["TYPE"] = "S:DateTime";
			$arFieldsTmp["USER_TYPE"]= "DateTime";
		}
		elseif ($arFields["type"] == "file")
		{
			$arFieldsTmp["TYPE"] = "F";
			$arFieldsTmp["USER_TYPE"]= "";
		}
		elseif ($arFields["type"] == "select")
		{
			$arFieldsTmp["TYPE"] = "L";
			$arFieldsTmp["USER_TYPE"]= false;

			if (is_array($arFields["options"]))
			{
				$i = 10;
				foreach ($arFields["options"] as $k => $v)
				{
					$def = "N";
					if($arFields['DefaultValue'] == $v)
						$def = "Y";
					$arFieldsTmp["VALUES"][] = array("XML_ID" => $k, "VALUE" => $v, "DEF" => $def, "SORT" => $i);
					$i = $i + 10;
				}
			}
			elseif (is_string($arFields["options"]) && (strlen($arFields["options"]) > 0))
			{
				$a = explode("\n", $arFields["options"]);
				$i = 10;
				foreach ($a as $v)
				{
					$v = trim(trim($v), "\r\n");
					if (!$v)
						continue;
					$v1 = $v2 = $v;
					if (substr($v, 0, 1) == "[" && strpos($v, "]") !== false)
					{
						$v1 = substr($v, 1, strpos($v, "]") - 1);
						$v2 = trim(substr($v, strpos($v, "]") + 1));
					}
					$def = "N";
					if($arFields['DefaultValue'] == $v2)
						$def = "Y";
					$arFieldsTmp["VALUES"][] = array("XML_ID" => $v1, "VALUE" => $v2, "DEF" => $def, "SORT" => $i);
					$i = $i + 10;
				}
			}
		}
		elseif($arFields["type"] == "string")
		{
			$arFieldsTmp["TYPE"] = "S";

			if($arFields["row_count"] && $arFields["col_count"])
			{
				$arFieldsTmp["ROW_COUNT"] = $arFields["row_count"];
				$arFieldsTmp["COL_COUNT"] = $arFields["col_count"];
			}
			else
			{
				$arFieldsTmp["ROW_COUNT"] = 1;
				$arFieldsTmp["COL_COUNT"] = 30;
			}
		}
		elseif($arFields["type"] == "text")
		{
			$arFieldsTmp["TYPE"] = "S";
			if($arFields["row_count"] && $arFields["col_count"])
			{
				$arFieldsTmp["ROW_COUNT"] = $arFields["row_count"];
				$arFieldsTmp["COL_COUNT"] = $arFields["col_count"];
			}
			else
			{
				$arFieldsTmp["ROW_COUNT"] = 4;
				$arFieldsTmp["COL_COUNT"] = 30;
			}
		}
		elseif($arFields["type"] == "int" || $arFields["type"] == "double")
		{
			$arFieldsTmp["TYPE"] = "N";
		}
		elseif($arFields["type"] == "bool")
		{
			$arFieldsTmp["TYPE"] = "L";
			$arFieldsTmp["VALUES"][] = array("XML_ID" => 'yes', "VALUE" => GetMessage("BPVDX_YES"), "DEF" => "N", "SORT" => 10);
			$arFieldsTmp["VALUES"][] = array("XML_ID" => 'no', "VALUE" => GetMessage("BPVDX_NO"), "DEF" => "N", "SORT" => 20);
		}
		else
		{
			$arFieldsTmp["TYPE"] = $arFields["type"];
			$arFieldsTmp["USER_TYPE"] = false;
		}

		$listObject = new CList($iblockId);
		$idField = $listObject->addField($arFieldsTmp);
		if($idField)
		{
			global $CACHE_MANAGER;
			$CACHE_MANAGER->ClearByTag("lists_list_".$iblockId);
			return $idField;
		}
		return false;
	}

	public static function onTaskChange($documentId, $taskId, $taskData, $status)
	{
		CListsLiveFeed::setMessageLiveFeed($taskData['USERS'], $documentId, $taskData['WORKFLOW_ID'], false);
		if ($status == CBPTaskChangedStatus::Delegate)
		{
			$runtime = CBPRuntime::GetRuntime();
			/**
			 * @var CBPAllStateService $stateService
			 */
			$stateService = $runtime->GetService('StateService');
			$stateService->setStatePermissions(
				$taskData['WORKFLOW_ID'],
				array('R' => array('user_'.$taskData['USERS'][0])),
				array('setMode' => CBPSetPermissionsMode::Hold, 'setScope' => CBPSetPermissionsMode::ScopeDocument)
			);
		}
	}

	public static function onWorkflowStatusChange($documentId, $workflowId, $status)
	{
		if ($status == CBPWorkflowStatus::Completed)
		{
			CListsLiveFeed::setMessageLiveFeed(array(), $documentId, $workflowId, true);
		}
	}

	public function getDocumentAdminPage($documentId)
	{
		$documentId = intval($documentId);
		if ($documentId <= 0)
			throw new CBPArgumentNullException("documentId");

		$db = CIBlockElement::getList(
			array(),
			array("ID" => $documentId, "SHOW_NEW"=>"Y", "SHOW_HISTORY" => "Y"),
			false,
			false,
			array("ID", "IBLOCK_ID", "IBLOCK_TYPE_ID", "DETAIL_PAGE_URL")
		);
		if ($ar = $db->Fetch())
		{
			return COption::GetOptionString('lists', 'livefeed_url').'?livefeed=y&list_id='.$ar["IBLOCK_ID"].'&element_id='.$documentId;
		}

		return null;
	}

	protected static function getRightsTasks()
	{
		if (self::$cachedTasks === null)
		{
			$iterator = CTask::GetList(
				array("LETTER"=>"asc"),
				array(
					"MODULE_ID" => "iblock",
					"BINDING" => "iblock"
				)
			);

			while($ar = $iterator->fetch())
			{
				self::$cachedTasks[$ar["LETTER"]] = $ar;
			}
		}
		return self::$cachedTasks;
	}

	public function GetAllowableOperations($documentType)
	{
		$iblockId = intval(substr($documentType, strlen("iblock_")));
		if ($iblockId <= 0)
			throw new CBPArgumentOutOfRangeException("documentType", $documentType);

		if (CIBlock::GetArrayByID($iblockId, "RIGHTS_MODE") === "E")
		{
			$operations = array();
			$tasks = self::getRightsTasks();

			foreach($tasks as $ar)
			{
				$key = empty($ar['LETTER']) ? $ar['ID'] : $ar['LETTER'];
				$operations[$key] = $ar['TITLE'];
			}

			return $operations;
		}
		return parent::GetAllowableOperations($documentType);
	}

	public function toInternalOperations($documentType, $permissions)
	{
		$permissions = (array) $permissions;
		$tasks = self::getRightsTasks();

		$normalized = array();
		foreach ($permissions as $key => $value)
		{
			if (isset($tasks[$key]))
				$key = $tasks[$key]['ID'];
			$normalized[$key] = $value;
		}

		return $normalized;
	}

	public function toExternalOperations($documentType, $permissions)
	{
		$permissions = (array) $permissions;
		$tasks = self::getRightsTasks();
		$letters = array();
		foreach ($tasks as $k => $t)
		{
			$letters[$t['ID']] = $k;
		}
		unset($tasks);

		$normalized = array();
		foreach ($permissions as $key => $value)
		{
			if (isset($letters[$key]))
				$key = $letters[$key];
			$normalized[$key] = $value;
		}

		return $normalized;
	}

	function CanUserOperateDocument($operation, $userId, $documentId, $arParameters = array())
	{
		$documentId = trim($documentId);
		if (strlen($documentId) <= 0)
			return false;

		if (!array_key_exists("IBlockId", $arParameters)
			&& (
				!array_key_exists("IBlockPermission", $arParameters)
				|| !array_key_exists("DocumentStates", $arParameters)
				|| !array_key_exists("IBlockRightsMode", $arParameters)
				|| array_key_exists("IBlockRightsMode", $arParameters) && ($arParameters["IBlockRightsMode"] === "E")
			)
			|| !array_key_exists("CreatedBy", $arParameters) && !array_key_exists("AllUserGroups", $arParameters))
		{
			$dbElementList = CIBlockElement::GetList(
				array(),
				array("ID" => $documentId, "SHOW_NEW" => "Y", "SHOW_HISTORY" => "Y"),
				false,
				false,
				array("ID", "IBLOCK_ID", "CREATED_BY")
			);
			$arElement = $dbElementList->Fetch();

			if (!$arElement)
				return false;

			$arParameters["IBlockId"] = $arElement["IBLOCK_ID"];
			$arParameters["CreatedBy"] = $arElement["CREATED_BY"];
		}

		if (!array_key_exists("IBlockRightsMode", $arParameters))
			$arParameters["IBlockRightsMode"] = CIBlock::GetArrayByID($arParameters["IBlockId"], "RIGHTS_MODE");

		if ($arParameters["IBlockRightsMode"] === "E")
		{
			if ($operation === CBPCanUserOperateOperation::ReadDocument)
				return CIBlockElementRights::UserHasRightTo($arParameters["IBlockId"], $documentId, "element_read");
			elseif ($operation === CBPCanUserOperateOperation::WriteDocument)
				return CIBlockElementRights::UserHasRightTo($arParameters["IBlockId"], $documentId, "element_edit");
			elseif (
				$operation === CBPCanUserOperateOperation::StartWorkflow
				|| $operation === CBPCanUserOperateOperation::ViewWorkflow
			)
			{
				if (CIBlockElementRights::UserHasRightTo($arParameters["IBlockId"], $documentId, "element_edit"))
					return true;

				if (!array_key_exists("WorkflowId", $arParameters))
					return false;

				if (!CIBlockElementRights::UserHasRightTo($arParameters["IBlockId"], $documentId, "element_read"))
					return false;

				$userId = intval($userId);
				if (!array_key_exists("AllUserGroups", $arParameters))
				{
					if (!array_key_exists("UserGroups", $arParameters))
						$arParameters["UserGroups"] = CUser::GetUserGroup($userId);

					$arParameters["AllUserGroups"] = $arParameters["UserGroups"];
					if ($userId == $arParameters["CreatedBy"])
						$arParameters["AllUserGroups"][] = "Author";
				}

				if (!array_key_exists("DocumentStates", $arParameters))
				{
					if ($operation === CBPCanUserOperateOperation::StartWorkflow)
						$arParameters["DocumentStates"] = CBPWorkflowTemplateLoader::GetDocumentTypeStates(array('lists', 'BizprocDocument', self::generateDocumentType($arParameters["IBlockId"])));
					else
						$arParameters["DocumentStates"] = CBPDocument::GetDocumentStates(
							array('lists', 'BizprocDocument', self::generateDocumentType($arParameters["IBlockId"])),
							array('lists', 'BizprocDocument', $documentId)
						);
				}

				if (array_key_exists($arParameters["WorkflowId"], $arParameters["DocumentStates"]))
					$arParameters["DocumentStates"] = array($arParameters["WorkflowId"] => $arParameters["DocumentStates"][$arParameters["WorkflowId"]]);
				else
					return false;

				$arAllowableOperations = CBPDocument::GetAllowableOperations(
					$userId,
					$arParameters["AllUserGroups"],
					$arParameters["DocumentStates"],
					true
				);

				if (!is_array($arAllowableOperations))
					return false;

				if (($operation === CBPCanUserOperateOperation::ViewWorkflow) && in_array("read", $arAllowableOperations)
					|| ($operation === CBPCanUserOperateOperation::StartWorkflow) && in_array("write", $arAllowableOperations))
					return true;

				$chop = ($operation === CBPCanUserOperateOperation::ViewWorkflow) ? "element_read" : "element_edit";

				$tasks = self::getRightsTasks();
				foreach ($arAllowableOperations as $op)
				{
					if (isset($tasks[$op]))
						$op = $tasks[$op]['ID'];
					$ar = CTask::GetOperations($op, true);
					if (in_array($chop, $ar))
						return true;
				}
			}
			elseif (
				$operation === CBPCanUserOperateOperation::CreateWorkflow
			)
			{
				return CBPDocument::CanUserOperateDocumentType(
					CBPCanUserOperateOperation::CreateWorkflow,
					$userId,
					array('lists', 'BizprocDocument', $documentId),
					$arParameters
				);
			}

			return false;
		}

		if (!array_key_exists("IBlockPermission", $arParameters))
		{
			if (CModule::IncludeModule('lists'))
				$arParameters["IBlockPermission"] = CLists::GetIBlockPermission($arParameters["IBlockId"], $userId);
			else
				$arParameters["IBlockPermission"] = CIBlock::GetPermission($arParameters["IBlockId"], $userId);
		}

		if ($arParameters["IBlockPermission"] <= "R")
			return false;
		elseif ($arParameters["IBlockPermission"] >= "W")
			return true;

		$userId = intval($userId);
		if (!array_key_exists("AllUserGroups", $arParameters))
		{
			if (!array_key_exists("UserGroups", $arParameters))
				$arParameters["UserGroups"] = CUser::GetUserGroup($userId);

			$arParameters["AllUserGroups"] = $arParameters["UserGroups"];
			if ($userId == $arParameters["CreatedBy"])
				$arParameters["AllUserGroups"][] = "Author";
		}

		if (!array_key_exists("DocumentStates", $arParameters))
		{
			$arParameters["DocumentStates"] = CBPDocument::GetDocumentStates(
				array("lists", "BizprocDocument", "iblock_".$arParameters["IBlockId"]),
				array('lists', 'BizprocDocument', $documentId)
			);
		}

		if (array_key_exists("WorkflowId", $arParameters))
		{
			if (array_key_exists($arParameters["WorkflowId"], $arParameters["DocumentStates"]))
				$arParameters["DocumentStates"] = array($arParameters["WorkflowId"] => $arParameters["DocumentStates"][$arParameters["WorkflowId"]]);
			else
				return false;
		}

		$arAllowableOperations = CBPDocument::GetAllowableOperations(
			$userId,
			$arParameters["AllUserGroups"],
			$arParameters["DocumentStates"]
		);

		if (!is_array($arAllowableOperations))
			return false;

		$r = false;
		switch ($operation)
		{
			case CBPCanUserOperateOperation::ViewWorkflow:
				$r = in_array("read", $arAllowableOperations);
				break;
			case CBPCanUserOperateOperation::StartWorkflow:
				$r = in_array("write", $arAllowableOperations);
				break;
			case CBPCanUserOperateOperation::CreateWorkflow:
				$r = false;
				break;
			case CBPCanUserOperateOperation::WriteDocument:
				$r = in_array("write", $arAllowableOperations);
				break;
			case CBPCanUserOperateOperation::ReadDocument:
				$r = in_array("read", $arAllowableOperations) || in_array("write", $arAllowableOperations);
				break;
			default:
				$r = false;
		}

		return $r;
	}

	function CanUserOperateDocumentType($operation, $userId, $documentType, $arParameters = array())
	{
		$documentType = trim($documentType);
		if (strlen($documentType) <= 0)
			return false;

		$arParameters["IBlockId"] = intval(substr($documentType, strlen("iblock_")));
		$arParameters['sectionId'] = !empty($arParameters['sectionId']) ? (int)$arParameters['sectionId'] : 0;

		if (!array_key_exists("IBlockRightsMode", $arParameters))
			$arParameters["IBlockRightsMode"] = CIBlock::GetArrayByID($arParameters["IBlockId"], "RIGHTS_MODE");

		if ($arParameters["IBlockRightsMode"] === "E")
		{
			if ($operation === CBPCanUserOperateOperation::CreateWorkflow)
				return CIBlockRights::UserHasRightTo($arParameters["IBlockId"], $arParameters["IBlockId"], "iblock_rights_edit");
			elseif ($operation === CBPCanUserOperateOperation::WriteDocument)
				return CIBlockSectionRights::UserHasRightTo($arParameters["IBlockId"], $arParameters["sectionId"], "section_element_bind");
			elseif ($operation === CBPCanUserOperateOperation::ViewWorkflow
				|| $operation === CBPCanUserOperateOperation::StartWorkflow)
			{
				if (!array_key_exists("WorkflowId", $arParameters))
					return false;

				if ($operation === CBPCanUserOperateOperation::ViewWorkflow)
					return CIBlockRights::UserHasRightTo($arParameters["IBlockId"], 0, "element_read");

				if ($operation === CBPCanUserOperateOperation::StartWorkflow)
					return CIBlockSectionRights::UserHasRightTo($arParameters["IBlockId"], $arParameters['sectionId'], "section_element_bind");


				$userId = intval($userId);
				if (!array_key_exists("AllUserGroups", $arParameters))
				{
					if (!array_key_exists("UserGroups", $arParameters))
						$arParameters["UserGroups"] = CUser::GetUserGroup($userId);

					$arParameters["AllUserGroups"] = $arParameters["UserGroups"];
					$arParameters["AllUserGroups"][] = "Author";
				}

				if (!array_key_exists("DocumentStates", $arParameters))
				{
					if ($operation === CBPCanUserOperateOperation::StartWorkflow)
						$arParameters["DocumentStates"] = CBPWorkflowTemplateLoader::GetDocumentTypeStates(array("lists", "BizprocDocument", "iblock_".$arParameters["IBlockId"]));
					else
						$arParameters["DocumentStates"] = CBPDocument::GetDocumentStates(
							array("lists", "BizprocDocument", "iblock_".$arParameters["IBlockId"]),
							null
						);
				}

				if (array_key_exists($arParameters["WorkflowId"], $arParameters["DocumentStates"]))
					$arParameters["DocumentStates"] = array($arParameters["WorkflowId"] => $arParameters["DocumentStates"][$arParameters["WorkflowId"]]);
				else
					return false;

				$arAllowableOperations = CBPDocument::GetAllowableOperations(
					$userId,
					$arParameters["AllUserGroups"],
					$arParameters["DocumentStates"],
					true
				);

				if (!is_array($arAllowableOperations))
					return false;

				if (($operation === CBPCanUserOperateOperation::ViewWorkflow) && in_array("read", $arAllowableOperations)
					|| ($operation === CBPCanUserOperateOperation::StartWorkflow) && in_array("write", $arAllowableOperations))
					return true;

				$chop = ($operation === CBPCanUserOperateOperation::ViewWorkflow) ? "element_read" : "section_element_bind";

				$tasks  = self::getRightsTasks();
				foreach ($arAllowableOperations as $op)
				{
					if (isset($tasks[$op]))
						$op = $tasks[$op]['ID'];
					$ar = CTask::GetOperations($op, true);
					if (in_array($chop, $ar))
						return true;
				}
			}

			return false;
		}

		if (!array_key_exists("IBlockPermission", $arParameters))
		{
			if(CModule::IncludeModule('lists'))
				$arParameters["IBlockPermission"] = CLists::GetIBlockPermission($arParameters["IBlockId"], $userId);
			else
				$arParameters["IBlockPermission"] = CIBlock::GetPermission($arParameters["IBlockId"], $userId);
		}

		if ($arParameters["IBlockPermission"] <= "R")
			return false;
		elseif ($arParameters["IBlockPermission"] >= "W")
			return true;

		$userId = intval($userId);
		if (!array_key_exists("AllUserGroups", $arParameters))
		{
			if (!array_key_exists("UserGroups", $arParameters))
				$arParameters["UserGroups"] = CUser::GetUserGroup($userId);

			$arParameters["AllUserGroups"] = $arParameters["UserGroups"];
			$arParameters["AllUserGroups"][] = "Author";
		}

		if (!array_key_exists("DocumentStates", $arParameters))
		{
			$arParameters["DocumentStates"] = CBPDocument::GetDocumentStates(
				array("lists", "BizprocDocument", "iblock_".$arParameters["IBlockId"]),
				null
			);
		}

		if (array_key_exists("WorkflowId", $arParameters))
		{
			if (array_key_exists($arParameters["WorkflowId"], $arParameters["DocumentStates"]))
				$arParameters["DocumentStates"] = array($arParameters["WorkflowId"] => $arParameters["DocumentStates"][$arParameters["WorkflowId"]]);
			else
				return false;
		}

		$arAllowableOperations = CBPDocument::GetAllowableOperations(
			$userId,
			$arParameters["AllUserGroups"],
			$arParameters["DocumentStates"]
		);

		if (!is_array($arAllowableOperations))
			return false;

		$r = false;
		switch ($operation)
		{
			case CBPCanUserOperateOperation::ViewWorkflow:
				$r = in_array("read", $arAllowableOperations);
				break;
			case CBPCanUserOperateOperation::StartWorkflow:
				$r = in_array("write", $arAllowableOperations);
				break;
			case CBPCanUserOperateOperation::CreateWorkflow:
				$r = in_array("write", $arAllowableOperations);
				break;
			case CBPCanUserOperateOperation::WriteDocument:
				$r = in_array("write", $arAllowableOperations);
				break;
			case CBPCanUserOperateOperation::ReadDocument:
				$r = false;
				break;
			default:
				$r = false;
		}

		return $r;
	}

	/**
	 * @param $documentType
	 * @param bool $withExtended
	 * @return array|bool
	 */

	public function GetAllowableUserGroups($documentType, $withExtended = false)
	{
		$documentType = trim($documentType);
		if (strlen($documentType) <= 0)
			return false;

		$iblockId = intval(substr($documentType, strlen("iblock_")));

		$result = array("Author" => GetMessage("IBD_DOCUMENT_AUTHOR"));

		$groupsId = array(1);
		$extendedGroupsCode = array();
		if(CIBlock::GetArrayByID($iblockId, "RIGHTS_MODE") === "E")
		{
			$rights = new CIBlockRights($iblockId);
			foreach($rights->GetGroups(/*"element_bizproc_start"*/) as $iblockGroupCode)
				if(preg_match("/^G(\\d+)\$/", $iblockGroupCode, $match))
					$groupsId[] = $match[1];
				else
					$extendedGroupsCode[] = $iblockGroupCode;
		}
		else
		{
			foreach(CIBlock::GetGroupPermissions($iblockId) as $groupId => $perm)
			{
				if ($perm > "R")
					$groupsId[] = $groupId;
			}
		}

		$groupsIterator = CGroup::GetListEx(array("NAME" => "ASC"), array("ID" => $groupsId));
		while ($group = $groupsIterator->Fetch())
			$result[$group["ID"]] = $group["NAME"];

		if ($withExtended && $extendedGroupsCode)
		{
			foreach ($extendedGroupsCode as $groupCode)
			{
				$result['group_'.$groupCode] = CBPHelper::getExtendedGroupName($groupCode);
			}
		}

		return $result;
	}

	public function SetPermissions($documentId, $workflowId, $arPermissions, $bRewrite = true)
	{
		$arPermissions = self::toInternalOperations(null, $arPermissions);
		parent::SetPermissions($documentId, $workflowId, $arPermissions, $bRewrite);
	}

	public function GetFieldInputControl($documentType, $arFieldType, $arFieldName, $fieldValue, $bAllowSelection = false, $publicMode = false)
	{
		$iblockId = intval(substr($documentType, strlen("iblock_")));
		if ($iblockId <= 0)
			throw new CBPArgumentOutOfRangeException("documentType", $documentType);

		global $APPLICATION;
		if(!$publicMode)
			$APPLICATION->showAjaxHead();

		static $arDocumentFieldTypes = array();
		if (!array_key_exists($documentType, $arDocumentFieldTypes))
			$arDocumentFieldTypes[$documentType] = self::GetDocumentFieldTypes($documentType);

		$arFieldType["BaseType"] = "string";
		$arFieldType["Complex"] = false;
		if (array_key_exists($arFieldType["Type"], $arDocumentFieldTypes[$documentType]))
		{
			$arFieldType["BaseType"] = $arDocumentFieldTypes[$documentType][$arFieldType["Type"]]["BaseType"];
			$arFieldType["Complex"] = $arDocumentFieldTypes[$documentType][$arFieldType["Type"]]["Complex"];
		}

		if (!is_array($fieldValue) || is_array($fieldValue) && CBPHelper::IsAssociativeArray($fieldValue))
			$fieldValue = array($fieldValue);

		$customMethodName = "";
		$customMethodNameMulty = "";
		if (strpos($arFieldType["Type"], ":") !== false)
		{
			$ar = CIBlockProperty::GetUserType(substr($arFieldType["Type"], 2));
			if (array_key_exists("GetPublicEditHTML", $ar))
				$customMethodName = $ar["GetPublicEditHTML"];
			if (array_key_exists("GetPublicEditHTMLMulty", $ar))
				$customMethodNameMulty = $ar["GetPublicEditHTMLMulty"];
		}

		ob_start();

		if ($arFieldType["Type"] == "select")
		{
			$fieldValueTmp = $fieldValue;
			?>
			<select id="id_<?= htmlspecialcharsbx($arFieldName["Field"]) ?>" name="<?= htmlspecialcharsbx($arFieldName["Field"]).($arFieldType["Multiple"] ? "[]" : "") ?>"<?= ($arFieldType["Multiple"] ? ' size="5" multiple' : '') ?>>
				<?
				if (!$arFieldType["Required"])
					echo '<option value="">['.GetMessage("BPCGHLP_NOT_SET").']</option>';
				foreach ($arFieldType["Options"] as $k => $v)
				{
					if (is_array($v) && count($v) == 2)
					{
						$v1 = array_values($v);
						$k = $v1[0];
						$v = $v1[1];
					}

					$ind = array_search($k, $fieldValueTmp);
					echo '<option value="'.htmlspecialcharsbx($k).'"'.($ind !== false ? ' selected' : '').'>'.htmlspecialcharsbx($v).'</option>';
					if ($ind !== false)
						unset($fieldValueTmp[$ind]);
				}
				?>
			</select>
			<?
			if ($bAllowSelection)
			{
				?>
				<br /><input type="text" id="id_<?= htmlspecialcharsbx($arFieldName["Field"]) ?>_text" name="<?= htmlspecialcharsbx($arFieldName["Field"]) ?>_text" value="<?
			if (count($fieldValueTmp) > 0)
			{
				$a = array_values($fieldValueTmp);
				echo htmlspecialcharsbx($a[0]);
			}
			?>">
				<input type="button" value="..." onclick="BPAShowSelector('id_<?= htmlspecialcharsbx($arFieldName["Field"]) ?>_text', 'select');">
			<?
			}
		}
		elseif ($arFieldType["Type"] == "user")
		{
			$fieldValue = CBPHelper::UsersArrayToString($fieldValue, null, array("lists", "BizprocDocument", $documentType));
			?><input type="text" size="40" id="id_<?= htmlspecialcharsbx($arFieldName["Field"]) ?>" name="<?= htmlspecialcharsbx($arFieldName["Field"]) ?>" value="<?= htmlspecialcharsbx($fieldValue) ?>"><input type="button" value="..." onclick="BPAShowSelector('id_<?= htmlspecialcharsbx($arFieldName["Field"]) ?>', 'user');"><?
		}
		elseif ((strpos($arFieldType["Type"], ":") !== false)
			&& $arFieldType["Multiple"]
			&& (
				is_array($customMethodNameMulty) && count($customMethodNameMulty) > 0
				|| !is_array($customMethodNameMulty) && strlen($customMethodNameMulty) > 0
			)
		)
		{
			if (!is_array($fieldValue))
				$fieldValue = array();

			if ($bAllowSelection)
			{
				$fieldValueTmp1 = array();
				$fieldValueTmp2 = array();
				foreach ($fieldValue as $v)
				{
					$vTrim = trim($v);
					if (preg_match("#^\{=[a-z0-9_]+:[a-z0-9_]+\}$#i", $vTrim) || (substr($vTrim, 0, 1) == "="))
						$fieldValueTmp1[] = $vTrim;
					else
						$fieldValueTmp2[] = $v;
				}
			}
			else
			{
				$fieldValueTmp1 = array();
				$fieldValueTmp2 = $fieldValue;
			}

			if (($arFieldType["Type"] == "S:employee") && COption::GetOptionString("bizproc", "employee_compatible_mode", "N") != "Y")
				$fieldValueTmp2 = CBPHelper::StripUserPrefix($fieldValueTmp2);

			foreach ($fieldValueTmp2 as &$fld)
				if (!isset($fld['VALUE']))
					$fld = array("VALUE" => $fld);

			if ($arFieldType["Type"] == "E:EList")
			{
				static $fl = true;
				if ($fl)
				{
					if (!empty($_SERVER['HTTP_BX_AJAX']))
						$GLOBALS["APPLICATION"]->ShowAjaxHead();
					$GLOBALS["APPLICATION"]->AddHeadScript('/bitrix/js/iblock/iblock_edit.js');
				}
				$fl = false;
			}
			echo call_user_func_array(
				$customMethodNameMulty,
				array(
					array("LINK_IBLOCK_ID" => $arFieldType["Options"]),
					$fieldValueTmp2,
					array(
						"FORM_NAME" => $arFieldName["Form"],
						"VALUE" => htmlspecialcharsbx($arFieldName["Field"])
					),
					true
				)
			);

			if ($bAllowSelection)
			{
				?>
				<br /><input type="text" id="id_<?= htmlspecialcharsbx($arFieldName["Field"]) ?>_text" name="<?= htmlspecialcharsbx($arFieldName["Field"]) ?>_text" value="<?
			if (count($fieldValueTmp1) > 0)
			{
				$a = array_values($fieldValueTmp1);
				echo htmlspecialcharsbx($a[0]);
			}
			?>">
				<input type="button" value="..." onclick="BPAShowSelector('id_<?= htmlspecialcharsbx($arFieldName["Field"]) ?>_text', 'user');">
			<?
			}
		}
		else
		{
			if (!array_key_exists("CBPVirtualDocumentCloneRowPrinted", $GLOBALS) && $arFieldType["Multiple"])
			{
				$GLOBALS["CBPVirtualDocumentCloneRowPrinted"] = 1;
				?>
				<script language="JavaScript">
					function CBPVirtualDocumentCloneRow(tableID)
					{
						var tbl = document.getElementById(tableID);
						var cnt = tbl.rows.length;
						var oRow = tbl.insertRow(cnt);
						var oCell = oRow.insertCell(0);
						var sHTML = tbl.rows[cnt - 1].cells[0].innerHTML;
						var p = 0;
						while (true)
						{
							var s = sHTML.indexOf('[n', p);
							if (s < 0)
								break;
							var e = sHTML.indexOf(']', s);
							if (e < 0)
								break;
							var n = parseInt(sHTML.substr(s + 2, e - s));
							sHTML = sHTML.substr(0, s) + '[n' + (++n) + ']' + sHTML.substr(e + 1);
							p = s + 1;
						}
						var p = 0;
						while (true)
						{
							var s = sHTML.indexOf('__n', p);
							if (s < 0)
								break;
							var e = sHTML.indexOf('_', s + 2);
							if (e < 0)
								break;
							var n = parseInt(sHTML.substr(s + 3, e - s));
							sHTML = sHTML.substr(0, s) + '__n' + (++n) + '_' + sHTML.substr(e + 1);
							p = e + 1;
						}
						oCell.innerHTML = sHTML;
						var patt = new RegExp('<' + 'script' + '>[^\000]*?<' + '\/' + 'script' + '>', 'ig');
						var code = sHTML.match(patt);
						if (code)
						{
							for (var i = 0; i < code.length; i++)
							{
								if (code[i] != '')
								{
									var s = code[i].substring(8, code[i].length - 9);
									jsUtils.EvalGlobal(s);
								}
							}
						}
					}
					function createAdditionalHtmlEditor(tableId)
					{
						var tbl = document.getElementById(tableId);
						var cnt = tbl.rows.length-1;
						var name = tableId.replace(/(?:CBPVirtualDocument_)(.*)(?:_Table)/, '$1')
						var idEditor = 'id_'+name+'__n'+cnt+'_';
						var inputNameEditor = name+'[n'+cnt+']';
						window.BXHtmlEditor.Show(
							{
								'id':idEditor,
								'inputName':inputNameEditor,
								'content':'',
								'width':'100%',
								'height':'200',
								'allowPhp':false,
								'limitPhpAccess':false,
								'templates':[],
								'templateId':'',
								'templateParams':[],
								'componentFilter':'',
								'snippets':[],
								'placeholder':'Text here...',
								'actionUrl':'/bitrix/tools/html_editor_action.php',
								'cssIframePath':'/bitrix/js/fileman/html_editor/iframe-style.css?1412693817',
								'bodyClass':'',
								'bodyId':'',
								'spellcheck_path':'/bitrix/js/fileman/html_editor/html-spell.js?v=1412693817',
								'usePspell':'N',
								'useCustomSpell':'Y',
								'bbCode':false,
								'askBeforeUnloadPage':true,
								'settingsKey':'user_settings_1',
								'showComponents':true,
								'showSnippets':true,
								'view':'wysiwyg',
								'splitVertical':false,
								'splitRatio':'1',
								'taskbarShown':false,
								'taskbarWidth':'250',
								'lastSpecialchars':false,
								'cleanEmptySpans':true,
								'lazyLoad':false,
								'showTaskbars':false,
								'showNodeNavi':false,
								'controlsMap':[
									{'id':'Bold','compact':true,'sort':'80'},
									{'id':'Italic','compact':true,'sort':'90'},
									{'id':'Underline','compact':true,'sort':'100'},
									{'id':'Strikeout','compact':true,'sort':'110'},
									{'id':'RemoveFormat','compact':true,'sort':'120'},
									{'id':'Color','compact':true,'sort':'130'},
									{'id':'FontSelector','compact':false,'sort':'135'},
									{'id':'FontSize','compact':false,'sort':'140'},
									{'separator':true,'compact':false,'sort':'145'},
									{'id':'OrderedList','compact':true,'sort':'150'},
									{'id':'UnorderedList','compact':true,'sort':'160'},
									{'id':'AlignList','compact':false,'sort':'190'},
									{'separator':true,'compact':false,'sort':'200'},
									{'id':'InsertLink','compact':true,'sort':'210','wrap':'bx-b-link-'+idEditor},
									{'id':'InsertImage','compact':false,'sort':'220'},
									{'id':'InsertVideo','compact':true,'sort':'230','wrap':'bx-b-video-'+idEditor},
									{'id':'InsertTable','compact':false,'sort':'250'},
									{'id':'Code','compact':true,'sort':'260'},
									{'id':'Quote','compact':true,'sort':'270','wrap':'bx-b-quote-'+idEditor},
									{'id':'Smile','compact':false,'sort':'280'},
									{'separator':true,'compact':false,'sort':'290'},
									{'id':'Fullscreen','compact':false,'sort':'310'},
									{'id':'BbCode','compact':true,'sort':'340'},
									{'id':'More','compact':true,'sort':'400'}],
								'autoResize':true,
								'autoResizeOffset':'40',
								'minBodyWidth':'350',
								'normalBodyWidth':'555'
							});
						var htmlEditor = BX.findChildrenByClassName(BX(tableId), 'bx-html-editor');
						for(var k in htmlEditor)
						{
							var editorId = htmlEditor[k].getAttribute('id');
							var frameArray = BX.findChildrenByClassName(BX(editorId), 'bx-editor-iframe');
							if(frameArray.length > 1)
							{
								for(var i = 0; i < frameArray.length - 1; i++)
								{
									frameArray[i].parentNode.removeChild(frameArray[i]);
								}
							}

						}
					}
				</script>
			<?
			}

			if ($arFieldType["Multiple"])
				echo '<table width="100%" border="0" cellpadding="2" cellspacing="2" id="CBPVirtualDocument_'.htmlspecialcharsbx($arFieldName["Field"]).'_Table">';

			$fieldValueTmp = $fieldValue;

			if (sizeof($fieldValue) == 0)
				$fieldValue[] = null;

			$ind = -1;
			foreach ($fieldValue as $key => $value)
			{
				$ind++;
				$fieldNameId = 'id_'.htmlspecialcharsbx($arFieldName["Field"]).'__n'.$ind.'_';
				$fieldNameName = htmlspecialcharsbx($arFieldName["Field"]).($arFieldType["Multiple"] ? "[n".$ind."]" : "");

				if ($arFieldType["Multiple"])
					echo '<tr><td>';

				if (is_array($customMethodName) && count($customMethodName) > 0 || !is_array($customMethodName) && strlen($customMethodName) > 0)
				{
					if($arFieldType["Type"] == "S:HTML")
					{
						if (Loader::includeModule("fileman"))
						{
							$editor = new \CHTMLEditor;
							$res = array_merge(
								array(
									'height' => 200,
									'minBodyWidth' => 350,
									'normalBodyWidth' => 555,
									'bAllowPhp' => false,
									'limitPhpAccess' => false,
									'showTaskbars' => false,
									'showNodeNavi' => false,
									'askBeforeUnloadPage' => true,
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
										array('id' => 'InsertLink',  'compact' => true, 'sort' => 210, 'wrap' => 'bx-b-link-'.$fieldNameId),
										array('id' => 'InsertImage',  'compact' => false, 'sort' => 220),
										array('id' => 'InsertVideo',  'compact' => true, 'sort' => 230, 'wrap' => 'bx-b-video-'.$fieldNameId),
										array('id' => 'InsertTable',  'compact' => false, 'sort' => 250),
										array('id' => 'Code',  'compact' => true, 'sort' => 260),
										array('id' => 'Quote',  'compact' => true, 'sort' => 270, 'wrap' => 'bx-b-quote-'.$fieldNameId),
										array('id' => 'Smile',  'compact' => false, 'sort' => 280),
										array('separator' => true, 'compact' => false, 'sort' => 290),
										array('id' => 'Fullscreen',  'compact' => false, 'sort' => 310),
										array('id' => 'BbCode',  'compact' => true, 'sort' => 340),
										array('id' => 'More',  'compact' => true, 'sort' => 400)
									)
								),
								array(
									'name' => $fieldNameName,
									'inputName' => $fieldNameName,
									'id' => $fieldNameId,
									'width' => '100%',
									'content' => htmlspecialcharsBack($value),
								)
							);
							$editor->show($res);
						}
						else
						{
							?><textarea rows="5" cols="40" id="<?= $fieldNameId ?>" name="<?= $fieldNameName ?>"><?= htmlspecialcharsbx($value) ?></textarea><?
						}
					}
					else
					{
						$value1 = $value;
						if ($bAllowSelection && (preg_match("#^\{=[a-z0-9_]+:[a-z0-9_]+\}$#i", trim($value1)) || substr(trim($value1), 0, 1) == "="))
							$value1 = null;
						else
							unset($fieldValueTmp[$key]);

						if (($arFieldType["Type"] == "S:employee") && COption::GetOptionString("bizproc", "employee_compatible_mode", "N") != "Y")
							$value1 = CBPHelper::StripUserPrefix($value1);

						echo call_user_func_array(
							$customMethodName,
							array(
								array("LINK_IBLOCK_ID" => $arFieldType["Options"]),
								array("VALUE" => $value1),
								array(
									"FORM_NAME" => $arFieldName["Form"],
									"VALUE" => $fieldNameName
								),
								true
							)
						);
					}
				}
				else
				{
					switch ($arFieldType["Type"])
					{
						case "int":
						case "double":
							unset($fieldValueTmp[$key]);
							?><input type="text" size="10" id="<?= $fieldNameId ?>" name="<?= $fieldNameName ?>" value="<?= htmlspecialcharsbx($value) ?>"><?
							break;
						case "file":
							if ($publicMode)
							{
								//unset($fieldValueTmp[$key]);
								?><input type="file" id="<?= $fieldNameId ?>" name="<?= $fieldNameName ?>"><?
							}
							break;
						case "bool":
							if (in_array($value, array("Y", "N")))
								unset($fieldValueTmp[$key]);
							?>
							<select id="<?= $fieldNameId ?>" name="<?= $fieldNameName ?>">
								<?
								if (!$arFieldType["Required"])
									echo '<option value="">['.GetMessage("BPCGHLP_NOT_SET").']</option>';
								?>
								<option value="Y"<?= (in_array("Y", $fieldValue) ? ' selected' : '') ?>><?= GetMessage("BPCGHLP_YES") ?></option>
								<option value="N"<?= (in_array("N", $fieldValue) ? ' selected' : '') ?>><?= GetMessage("BPCGHLP_NO") ?></option>
							</select>
							<?
							break;
						case "text":
							unset($fieldValueTmp[$key]);
							?><textarea rows="5" cols="40" id="<?= $fieldNameId ?>" name="<?= $fieldNameName ?>"><?= htmlspecialcharsbx($value) ?></textarea><?
							break;
						case "date":
						case "datetime":
							if (defined("ADMIN_SECTION") && ADMIN_SECTION)
							{
								$v = "";
								if (!preg_match("#^\{=[a-z0-9_]+:[a-z0-9_]+\}$#i", trim($value))
									&& (substr(trim($value), 0, 1) != "="))
								{
									$v = $value;
									unset($fieldValueTmp[$key]);
								}
								require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/init_admin.php");
								echo CAdminCalendar::CalendarDate($fieldNameName, $v, 19, ($arFieldType["Type"] != "date"));
							}
							else
							{
								$value1 = $value;
								if ($bAllowSelection && (preg_match("#^\{=[a-z0-9_]+:[a-z0-9_]+\}$#i", trim($value1)) || substr(trim($value1), 0, 1) == "="))
									$value1 = null;
								else
									unset($fieldValueTmp[$key]);

								if($arFieldType["Type"] == "date")
									$type = "Date";
								else
									$type = "DateTime";
								$ar = CIBlockProperty::GetUserType($type);
								echo call_user_func_array(
									$ar["GetPublicEditHTML"],
									array(
										array("LINK_IBLOCK_ID" => $arFieldType["Options"]),
										array("VALUE" => $value1),
										array(
											"FORM_NAME" => $arFieldName["Form"],
											"VALUE" => $fieldNameName
										),
										true
									)
								);
							}

							break;
						default:
							unset($fieldValueTmp[$key]);
							?><input type="text" size="40" id="<?= $fieldNameId ?>" name="<?= $fieldNameName ?>" value="<?= htmlspecialcharsbx($value) ?>"><?
					}
				}

				if ($bAllowSelection)
				{
					if (!in_array($arFieldType["Type"], array("file", "bool", "date", "datetime")) && (is_array($customMethodName) && count($customMethodName) <= 0 || !is_array($customMethodName) && strlen($customMethodName) <= 0))
					{
						?><input type="button" value="..." onclick="BPAShowSelector('<?= $fieldNameId ?>', '<?= htmlspecialcharsbx($arFieldType["BaseType"]) ?>');"><?
					}
				}

				if ($arFieldType["Multiple"])
					echo '</td></tr>';
			}

			if ($arFieldType["Multiple"])
				echo "</table>";

			if ($arFieldType["Multiple"] && $arFieldType["Type"] != "S:HTML" && (($arFieldType["Type"] != "file") || $publicMode))
			{
				echo '<input type="button" value="'.GetMessage("BPCGHLP_ADD").'" onclick="CBPVirtualDocumentCloneRow(\'CBPVirtualDocument_'.$arFieldName["Field"].'_Table\')"/><br />';
			}
			elseif($arFieldType["Multiple"] && $arFieldType["Type"] == "S:HTML")
			{
				$functionOnclick = 'CBPVirtualDocumentCloneRow(\'CBPVirtualDocument_'.$arFieldName["Field"].'_Table\');createAdditionalHtmlEditor(\'CBPVirtualDocument_'.$arFieldName["Field"].'_Table\');';
				echo '<input type="button" value="'.GetMessage("BPCGHLP_ADD").'" onclick="'.$functionOnclick.'"/><br />';
			}

			if ($bAllowSelection)
			{
				if (in_array($arFieldType["Type"], array("file", "bool", "date", "datetime")) || (is_array($customMethodName) && count($customMethodName) > 0 || !is_array($customMethodName) && strlen($customMethodName) > 0))
				{
					?>
					<input type="text" id="id_<?= htmlspecialcharsbx($arFieldName["Field"]) ?>_text" name="<?= htmlspecialcharsbx($arFieldName["Field"]) ?>_text" value="<?
					if (count($fieldValueTmp) > 0)
					{
						$a = array_values($fieldValueTmp);
						echo htmlspecialcharsbx($a[0]);
					}
					?>">
					<input type="button" value="..." onclick="BPAShowSelector('id_<?= htmlspecialcharsbx($arFieldName["Field"]) ?>_text', '<?= htmlspecialcharsbx($arFieldType["BaseType"]) ?>');">
				<?
				}
			}
		}

		$s = ob_get_contents();
		ob_end_clean();

		return $s;
	}

	public function GetFieldInputValue($documentType, $arFieldType, $arFieldName, $arRequest, &$arErrors)
	{
		$iblockId = intval(substr($documentType, strlen("iblock_")));
		if ($iblockId <= 0)
			throw new CBPArgumentOutOfRangeException("documentType", $documentType);

		$result = array();

		if ($arFieldType["Type"] == "user")
		{
			$value = $arRequest[$arFieldName["Field"]];
			if (strlen($value) > 0)
			{
				$result = CBPHelper::UsersStringToArray($value, array("lists", "BizprocDocument", $documentType), $arErrors);
				if (count($arErrors) > 0)
				{
					foreach ($arErrors as $e)
						$arErrors[] = $e;
				}
			}
			else
				$result = null;
		}
		elseif (array_key_exists($arFieldName["Field"], $arRequest) || array_key_exists($arFieldName["Field"]."_text", $arRequest))
		{
			$arValue = array();
			if (array_key_exists($arFieldName["Field"], $arRequest))
			{
				$arValue = $arRequest[$arFieldName["Field"]];
				if (!is_array($arValue) || is_array($arValue) && CBPHelper::IsAssociativeArray($arValue))
					$arValue = array($arValue);
			}
			if (array_key_exists($arFieldName["Field"]."_text", $arRequest))
				$arValue[] = $arRequest[$arFieldName["Field"]."_text"];

			foreach ($arValue as $value)
			{
				if (is_array($value) || !is_array($value) && !preg_match("#^\{=[a-z0-9_]+:[a-z0-9_]+\}$#i", trim($value)) && (substr(trim($value), 0, 1) != "="))
				{
					if ($arFieldType["Type"] == "int")
					{
						if (strlen($value) > 0)
						{
							$value = str_replace(" ", "", $value);
							if ($value."|" == intval($value)."|")
							{
								$value = intval($value);
							}
							else
							{
								$value = null;
								$arErrors[] = array(
									"code" => "ErrorValue",
									"message" => GetMessage("LISTS_BIZPROC_INVALID_INT"),
									"parameter" => $arFieldName["Field"],
								);
							}
						}
						else
						{
							$value = null;
						}
					}
					elseif ($arFieldType["Type"] == "double")
					{
						if (strlen($value) > 0)
						{
							$value = str_replace(" ", "", str_replace(",", ".", $value));
							if (is_numeric($value))
							{
								$value = doubleval($value);
							}
							else
							{
								$value = null;
								$arErrors[] = array(
									"code" => "ErrorValue",
									"message" => GetMessage("LISTS_BIZPROC_INVALID_INT"),
									"parameter" => $arFieldName["Field"],
								);
							}
						}
						else
						{
							$value = null;
						}
					}
					elseif ($arFieldType["Type"] == "select")
					{
						if (!is_array($arFieldType["Options"]) || count($arFieldType["Options"]) <= 0 || strlen($value) <= 0)
						{
							$value = null;
						}
						else
						{
							$ar = array_values($arFieldType["Options"]);
							if (is_array($ar[0]))
							{
								$b = false;
								foreach ($ar as $a)
								{
									if ($a[0] == $value)
									{
										$b = true;
										break;
									}
								}
								if (!$b)
								{
									$value = null;
									$arErrors[] = array(
										"code" => "ErrorValue",
										"message" => GetMessage("BPCGWTL_INVALID35"),
										"parameter" => $arFieldName["Field"],
									);
								}
							}
							else
							{
								if (!array_key_exists($value, $arFieldType["Options"]))
								{
									$value = null;
									$arErrors[] = array(
										"code" => "ErrorValue",
										"message" => GetMessage("BPCGWTL_INVALID35"),
										"parameter" => $arFieldName["Field"],
									);
								}
							}
						}
					}
					elseif ($arFieldType["Type"] == "bool")
					{
						if ($value !== "Y" && $value !== "N")
						{
							if ($value === true)
							{
								$value = "Y";
							}
							elseif ($value === false)
							{
								$value = "N";
							}
							elseif (strlen($value) > 0)
							{
								$value = strtolower($value);
								if (in_array($value, array("y", "yes", "true", "1")))
								{
									$value = "Y";
								}
								elseif (in_array($value, array("n", "no", "false", "0")))
								{
									$value = "N";
								}
								else
								{
									$value = null;
									$arErrors[] = array(
										"code" => "ErrorValue",
										"message" => GetMessage("BPCGWTL_INVALID45"),
										"parameter" => $arFieldName["Field"],
									);
								}
							}
							else
							{
								$value = null;
							}
						}
					}
					elseif ($arFieldType["Type"] == "file")
					{
						if (is_array($value) && array_key_exists("name", $value) && strlen($value["name"]) > 0)
						{
							if (!array_key_exists("MODULE_ID", $value) || strlen($value["MODULE_ID"]) <= 0)
								$value["MODULE_ID"] = "bizproc";

							$value = CFile::SaveFile($value, "bizproc_wf", true, true);
							if (!$value)
							{
								$value = null;
								$arErrors[] = array(
									"code" => "ErrorValue",
									"message" => GetMessage("BPCGWTL_INVALID915"),
									"parameter" => $arFieldName["Field"],
								);
							}
						}
						else
						{
							$value = null;
						}
					}
					elseif ($arFieldType["Type"] == "date")
					{
						if (strlen($value) > 0)
						{
							if(!CheckDateTime($value, FORMAT_DATE))
							{
								$value = null;
								$arErrors[] = array(
									"code" => "ErrorValue",
									"message" => GetMessage("LISTS_BIZPROC_INVALID_DATE"),
									"parameter" => $arFieldName["Field"],
								);
							}
						}
						else
						{
							$value = null;
						}

					}
					elseif ($arFieldType["Type"] == "datetime")
					{
						if (strlen($value) > 0)
						{
							$valueTemporary = array();
							$valueTemporary["VALUE"] = $value;
							$result = CIBlockPropertyDateTime::CheckFields('', $valueTemporary);
							if (!empty($result))
							{
								$message = '';
								foreach ($result as $error)
									$message .= $error;

								$value = null;
								$arErrors[] = array(
									"code" => "ErrorValue",
									"message" => $message,
									"parameter" => $arFieldName["Field"],
								);
							}
						}
						else
						{
							$value = null;
						}
					}
					elseif (strpos($arFieldType["Type"], ":") !== false && $arFieldType["Type"] != "S:HTML")
					{
						$arCustomType = CIBlockProperty::GetUserType(substr($arFieldType["Type"], 2));
						if (array_key_exists("GetLength", $arCustomType))
						{
							if (call_user_func_array(
									$arCustomType["GetLength"],
									array(
										array("LINK_IBLOCK_ID" => $arFieldType["Options"]),
										array("VALUE" => $value)
									)
								) <= 0)
							{
								$value = null;
							}
						}

						if (($value != null) && array_key_exists("CheckFields", $arCustomType))
						{
							$arErrorsTmp1 = call_user_func_array(
								$arCustomType["CheckFields"],
								array(
									array("LINK_IBLOCK_ID" => $arFieldType["Options"]),
									array("VALUE" => $value)
								)
							);
							if (count($arErrorsTmp1) > 0)
							{
								$value = null;
								foreach ($arErrorsTmp1 as $e)
									$arErrors[] = array(
										"code" => "ErrorValue",
										"message" => $e,
										"parameter" => $arFieldName["Field"],
									);
							}
						}
						elseif (!array_key_exists("GetLength", $arCustomType) && $value === '')
							$value = null;

						if (
							$value !== null &&
							$arFieldType["Type"] == "S:employee" &&
							COption::GetOptionString("bizproc", "employee_compatible_mode", "N") != "Y"
						)
						{
							$value = "user_".$value;
						}
					}
					else
					{
						if (!is_array($value) && strlen($value) <= 0)
							$value = null;
					}
				}

				if ($value !== null)
					$result[] = $value;
			}
		}

		if (!$arFieldType["Multiple"])
		{
			if (is_array($result) && count($result) > 0)
				$result = $result[0];
			else
				$result = null;
		}

		return $result;
	}

	public function GetFieldInputValuePrintable($documentType, $arFieldType, $fieldValue)
	{
		$result = $fieldValue;

		switch ($arFieldType['Type'])
		{
			case "user":
				if (!is_array($fieldValue))
					$fieldValue = array($fieldValue);

				$result = CBPHelper::UsersArrayToString($fieldValue, null, array("lists", "BizprocDocument", $documentType));
				break;

			case "bool":
				if (is_array($fieldValue))
				{
					$result = array();
					foreach ($fieldValue as $r)
						$result[] = ((strtoupper($r) != "N" && !empty($r)) ? GetMessage("BPVDX_YES") : GetMessage("BPVDX_NO"));
				}
				else
				{
					$result = ((strtoupper($fieldValue) != "N" && !empty($fieldValue)) ? GetMessage("BPVDX_YES") : GetMessage("BPVDX_NO"));
				}
				break;

			case "file":
				if (is_array($fieldValue))
				{
					$result = array();
					foreach ($fieldValue as $r)
					{
						$r = intval($r);
						$dbImg = CFile::GetByID($r);
						if ($arImg = $dbImg->Fetch())
							$result[] = "[url=/bitrix/tools/bizproc_show_file.php?f=".htmlspecialcharsbx($arImg["FILE_NAME"])."&i=".$r."&h=".md5($arImg["SUBDIR"])."]".htmlspecialcharsbx($arImg["ORIGINAL_NAME"])."[/url]";
					}
				}
				else
				{
					$fieldValue = intval($fieldValue);
					$dbImg = CFile::GetByID($fieldValue);
					if ($arImg = $dbImg->Fetch())
						$result = "[url=/bitrix/tools/bizproc_show_file.php?f=".htmlspecialcharsbx($arImg["FILE_NAME"])."&i=".$fieldValue."&h=".md5($arImg["SUBDIR"])."]".htmlspecialcharsbx($arImg["ORIGINAL_NAME"])."[/url]";
				}
				break;

			case "select":
				if (is_array($arFieldType["Options"]))
				{
					if (is_array($fieldValue))
					{
						$result = array();
						foreach ($fieldValue as $r)
						{
							if (array_key_exists($r, $arFieldType["Options"]))
								$result[] = $arFieldType["Options"][$r];
						}
					}
					else
					{
						if (array_key_exists($fieldValue, $arFieldType["Options"]))
							$result = $arFieldType["Options"][$fieldValue];
					}
				}
				break;
		}

		if (strpos($arFieldType['Type'], ":") !== false)
		{
			if ($arFieldType["Type"] == "S:employee")
				$fieldValue = CBPHelper::StripUserPrefix($fieldValue);

			$arCustomType = CIBlockProperty::GetUserType(substr($arFieldType['Type'], 2));
			if (array_key_exists("GetPublicViewHTML", $arCustomType))
			{
				if (is_array($fieldValue) && !CBPHelper::IsAssociativeArray($fieldValue))
				{
					$result = array();
					foreach ($fieldValue as $value)
					{
						$r = call_user_func_array(
							$arCustomType["GetPublicViewHTML"],
							array(
								array("LINK_IBLOCK_ID" => $arFieldType["Options"]),
								array("VALUE" => $value),
								""
							)
						);

						$result[] = HTMLToTxt($r);
					}
				}
				else
				{
					$result = call_user_func_array(
						$arCustomType["GetPublicViewHTML"],
						array(
							array("LINK_IBLOCK_ID" => $arFieldType["Options"]),
							array("VALUE" => $fieldValue),
							""
						)
					);

					$result = HTMLToTxt($result);
				}
			}
		}

		return $result;
	}

	public function UnlockDocument($documentId, $workflowId)
	{
		global $DB;

		$strSql = "
			SELECT * FROM b_iblock_element_lock
			WHERE IBLOCK_ELEMENT_ID = ".intval($documentId)."
		";
		$z = $DB->Query($strSql, false, "FILE: ".__FILE__."<br>LINE: ".__LINE__);
		if($z->Fetch())
		{
			$strSql = "
				DELETE FROM b_iblock_element_lock
				WHERE IBLOCK_ELEMENT_ID = ".intval($documentId)."
				AND (LOCKED_BY = '".$DB->ForSQL($workflowId, 32)."' OR '".$DB->ForSQL($workflowId, 32)."' = '')
			";
			$z = $DB->Query($strSql, false, "FILE: ".__FILE__."<br>LINE: ".__LINE__);
			$result = $z->AffectedRowsCount();
		}
		else
		{//Success unlock when there is no locks at all
			$result = 1;
		}

		if ($result > 0)
		{
			foreach (GetModuleEvents("iblock", "CIBlockDocument_OnUnlockDocument", true) as $arEvent)
			{
				ExecuteModuleEventEx($arEvent, array(array("lists", "BizprocDocument", $documentId)));
			}
		}

		return $result > 0;
	}

	/**
	 * ћетод публикует документ. “о есть делает его доступным в публичной части сайта.
	 *
	 * @param string $documentId - код документа.
	 */
	public function PublishDocument($documentId)
	{
		global $DB;
		$ID = intval($documentId);

		$db_element = CIBlockElement::GetList(array(), array("ID"=>$ID, "SHOW_HISTORY"=>"Y"), false, false,
			array(
				"ID",
				"TIMESTAMP_X",
				"MODIFIED_BY",
				"DATE_CREATE",
				"CREATED_BY",
				"IBLOCK_ID",
				"ACTIVE",
				"ACTIVE_FROM",
				"ACTIVE_TO",
				"SORT",
				"NAME",
				"PREVIEW_PICTURE",
				"PREVIEW_TEXT",
				"PREVIEW_TEXT_TYPE",
				"DETAIL_PICTURE",
				"DETAIL_TEXT",
				"DETAIL_TEXT_TYPE",
				"WF_STATUS_ID",
				"WF_PARENT_ELEMENT_ID",
				"WF_NEW",
				"WF_COMMENTS",
				"IN_SECTIONS",
				"CODE",
				"TAGS",
				"XML_ID",
				"TMP_ID",
			)
		);
		if($ar_element = $db_element->Fetch())
		{
			$PARENT_ID = intval($ar_element["WF_PARENT_ELEMENT_ID"]);
			if($PARENT_ID)
			{
				// TODO: ≈сли в документе $documentId поле WF_PARENT_ELEMENT_ID не NULL, то при публикации нужно перенести данные
				// (скопировать документ) из документа $documentId в документ WF_PARENT_ELEMENT_ID,
				$obElement = new CIBlockElement;
				$ar_element["WF_PARENT_ELEMENT_ID"] = false;

				if($ar_element["PREVIEW_PICTURE"])
					$ar_element["PREVIEW_PICTURE"] = CFile::MakeFileArray($ar_element["PREVIEW_PICTURE"]);
				else
					$ar_element["PREVIEW_PICTURE"] = array("tmp_name" => "", "del" => "Y");

				if($ar_element["DETAIL_PICTURE"])
					$ar_element["DETAIL_PICTURE"] = CFile::MakeFileArray($ar_element["DETAIL_PICTURE"]);
				else
					$ar_element["DETAIL_PICTURE"] = array("tmp_name" => "", "del" => "Y");

				$ar_element["IBLOCK_SECTION"] = array();
				if($ar_element["IN_SECTIONS"] == "Y")
				{
					$rsSections = CIBlockElement::GetElementGroups($ar_element["ID"], true, array('ID', 'IBLOCK_ELEMENT_ID'));
					while($arSection = $rsSections->Fetch())
						$ar_element["IBLOCK_SECTION"][] = $arSection["ID"];
				}

				$ar_element["PROPERTY_VALUES"] = array();
				$arProps = &$ar_element["PROPERTY_VALUES"];

				//Delete old files
				$rsProps = CIBlockElement::GetProperty($ar_element["IBLOCK_ID"], $PARENT_ID, array("value_id" => "asc"), array("PROPERTY_TYPE" => "F", "EMPTY" => "N"));
				while($arProp = $rsProps->Fetch())
				{
					if(!array_key_exists($arProp["ID"], $arProps))
						$arProps[$arProp["ID"]] = array();
					$arProps[$arProp["ID"]][$arProp["PROPERTY_VALUE_ID"]] = array(
						"VALUE" => array("tmp_name" => "", "del" => "Y"),
						"DESCRIPTION" => false,
					);
				}

				//Add new proiperty values
				$rsProps = CIBlockElement::GetProperty($ar_element["IBLOCK_ID"], $ar_element["ID"], array("value_id" => "asc"));
				$i = 0;
				while($arProp = $rsProps->Fetch())
				{
					$i++;
					if(!array_key_exists($arProp["ID"], $arProps))
						$arProps[$arProp["ID"]] = array();

					if($arProp["PROPERTY_VALUE_ID"])
					{
						if($arProp["PROPERTY_TYPE"] == "F")
							$arProps[$arProp["ID"]]["n".$i] = array(
								"VALUE" => CFile::MakeFileArray($arProp["VALUE"]),
								"DESCRIPTION" => $arProp["DESCRIPTION"],
							);
						else
							$arProps[$arProp["ID"]]["n".$i] = array(
								"VALUE" => $arProp["VALUE"],
								"DESCRIPTION" => $arProp["DESCRIPTION"],
							);
					}
				}

				$obElement->Update($PARENT_ID, $ar_element);
				// вызвать CBPDocument::MergeDocuments(WF_PARENT_ELEMENT_ID, $documentId) дл€ переноса состо€ний и истории Ѕѕ,
				CBPDocument::MergeDocuments(
					array("lists", "BizprocDocument", $PARENT_ID),
					array("lists", "BizprocDocument", $documentId)
				);
				// грохнуть документ $documentId,
				CIBlockElement::Delete($ID);
				// опубликовать документ WF_PARENT_ELEMENT_ID
				CIBlockElement::WF_CleanUpHistoryCopies($PARENT_ID, 0);
				$strSql = "update b_iblock_element set WF_STATUS_ID='1', WF_NEW=NULL WHERE ID=".$PARENT_ID." AND WF_PARENT_ELEMENT_ID IS NULL";
				$DB->Query($strSql, false, "FILE: ".__FILE__."<br>LINE: ".__LINE__);
				CIBlockElement::UpdateSearch($PARENT_ID);
				return $PARENT_ID;
			}
			else
			{
				// ≈сли WF_PARENT_ELEMENT_ID равно NULL, то все как раньше.
				CIBlockElement::WF_CleanUpHistoryCopies($ID, 0);
				$strSql = "update b_iblock_element set WF_STATUS_ID='1', WF_NEW=NULL WHERE ID=".$ID." AND WF_PARENT_ELEMENT_ID IS NULL";
				$DB->Query($strSql, false, "FILE: ".__FILE__."<br>LINE: ".__LINE__);
				CIBlockElement::UpdateSearch($ID);
				return $ID;
			}
		}
		return false;
	}

	/**
	 * Method return array with all information about document. Array used for method RecoverDocumentFromHistory.
	 *
	 * @param string $documentId - document id.
	 * @return array - document information array.
	 */
	public function GetDocumentForHistory($documentId, $historyIndex)
	{
		$documentId = intval($documentId);
		if ($documentId <= 0)
			throw new CBPArgumentNullException("documentId");

		$arResult = null;

		$dbDocumentList = CIBlockElement::GetList(
			array(),
			array("ID" => $documentId, "SHOW_NEW"=>"Y", "SHOW_HISTORY" => "Y")
		);
		if ($objDocument = $dbDocumentList->GetNextElement())
		{
			$arDocumentFields = $objDocument->GetFields();
			$arDocumentProperties = $objDocument->GetProperties();

			$arResult["NAME"] = $arDocumentFields["~NAME"];

			$arResult["FIELDS"] = array();
			foreach ($arDocumentFields as $fieldKey => $fieldValue)
			{
				if ($fieldKey == "~PREVIEW_PICTURE" || $fieldKey == "~DETAIL_PICTURE")
				{
					$arResult["FIELDS"][substr($fieldKey, 1)] = CBPDocument::PrepareFileForHistory(
						array("lists", "BizprocDocument", $documentId),
						$fieldValue,
						$historyIndex
					);
				}
				elseif (substr($fieldKey, 0, 1) == "~")
				{
					$arResult["FIELDS"][substr($fieldKey, 1)] = $fieldValue;
				}
			}

			$arResult["PROPERTIES"] = array();
			foreach ($arDocumentProperties as $propertyKey => $propertyValue)
			{
				if (strlen($propertyValue["USER_TYPE"]) > 0)
				{
					$arResult["PROPERTIES"][$propertyKey] = array(
						"VALUE" => $propertyValue["VALUE"],
						"DESCRIPTION" => $propertyValue["DESCRIPTION"]
					);
				}
				elseif ($propertyValue["PROPERTY_TYPE"] == "L")
				{
					$arResult["PROPERTIES"][$propertyKey] = array(
						"VALUE" => $propertyValue["VALUE_ENUM_ID"],
						"DESCRIPTION" => $propertyValue["DESCRIPTION"]
					);
				}
				elseif ($propertyValue["PROPERTY_TYPE"] == "F")
				{
					$arResult["PROPERTIES"][$propertyKey] = array(
						"VALUE" => CBPDocument::PrepareFileForHistory(
							array("lists", "BizprocDocument", $documentId),
							$propertyValue["VALUE"],
							$historyIndex
						),
						"DESCRIPTION" => $propertyValue["DESCRIPTION"]
					);
				}
				else
				{
					$arResult["PROPERTIES"][$propertyKey] = array(
						"VALUE" => $propertyValue["VALUE"],
						"DESCRIPTION" => $propertyValue["DESCRIPTION"]
					);
				}
			}
		}

		return $arResult;
	}
}