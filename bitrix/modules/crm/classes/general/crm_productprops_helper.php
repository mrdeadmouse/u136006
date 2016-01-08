<?php
if (!CModule::IncludeModule('iblock'))
{
	return false;
}

class CCrmProductPropsHelper
{
	public static function GetPropsTypesByOperations($userType = false, $arOperations = array())
	{
		if (!is_array($arOperations))
			$arOperations = array(strval($arOperations));

		$methodByOperation = array(
			'view' => 'GetPublicViewHTML',
			'edit' => 'GetPublicEditHTML',
			'filter' => 'GetPublicFilterHTML',
			'import' => 'GetPublicEditHTML'
		);

		$whiteListByOperation = array(
			'view' => array(),
			'edit' => array(),
			'filter' => array(),
			'import' => array(
				'S:HTML',
				'S:Date',
				'S:DateTime',
				'S:employee',
				'N:Sequence'
			)
		);

		$arUserTypeList = CIBlockProperty::GetUserType($userType);

		if (!empty($arOperations))
		{
			foreach ($arUserTypeList as $key => $item)
			{
				$skipNumber = count($arOperations);
				$skipCount = 0;
				foreach ($arOperations as $operation)
				{
					if (!isset($methodByOperation[$operation])
						|| !array_key_exists($methodByOperation[$operation], $item)
						|| (
							is_array($whiteListByOperation[$operation])
							&& count($whiteListByOperation[$operation]) > 0
							&& !in_array($item['PROPERTY_TYPE'].':'.$key, $whiteListByOperation[$operation], true)
						))
					{
						$skipCount++;
					}
				}
				if ($skipNumber <= $skipCount)
					unset($arUserTypeList[$key]);
			}
		}

		return $arUserTypeList;
	}
	public static function GetProps($catalogID, $arPropUserTypeList = array(), $arOperations = array())
	{
		if (!is_array($arOperations))
			$arOperations = array(strval($arOperations));

		$arProps = array();
		$catalogID = intval($catalogID);

		// validate operations list
		$validOperations = array(
			'view',
			'edit',
			'filter',
			'import'
		);
		$validatedOperations = array();
		foreach ($arOperations as $operationName)
		{
			if (in_array(strval($operationName), $validOperations, true))
				$validatedOperations[] = $operationName;
		}
		$arOperations = $validatedOperations;
		unset($validatedOperations, $operationName);

		if ($catalogID > 0)
		{
			$propsFilter = array(
				'IBLOCK_ID' => $catalogID,
				'ACTIVE' => 'Y',
				'CHECK_PERMISSIONS' => 'N',
				'!PROPERTY_TYPE' => 'G'
			);

			$bImport = false;
			foreach ($arOperations as $operationName)
			{
				if ($operationName === 'import')
				{
					$bImport = true;
				}
				else
				{
					$bImport = false;
					break;
				}
			}

			$dbRes = CIBlockProperty::GetList(
				array('SORT' => 'ASC', 'ID' => 'ASC'),
				$propsFilter
			);
			while ($arProp = $dbRes->Fetch())
			{
				if (
					(isset($arProp['USER_TYPE']) && !empty($arProp['USER_TYPE'])
						&& !array_key_exists($arProp['USER_TYPE'], $arPropUserTypeList))
					|| (
						$bImport
						&& (
							($arProp['PROPERTY_TYPE'] === 'E'
								&& (!isset($arProp['USER_TYPE']) || empty($arProp['USER_TYPE'])))
							|| ($arProp['PROPERTY_TYPE'] === 'E'
								&& isset($arProp['USER_TYPE']) && $arProp['USER_TYPE'] === 'EList')
						)
					)
				)
				{
					continue;
				}

				$propID = 'PROPERTY_' . $arProp['ID'];
				$arProps[$propID] = $arProp;
			}
		}

		return $arProps;
	}
	public static function ListAddFilterFields($arPropUserTypeList, $arProps, $sFormName, &$arFilter, &$arFilterable,
												&$arCustomFilter, &$arDateFilter)
	{
		$i = count($arFilter);
		foreach ($arProps as $propID => $arProp)
		{
			if (!empty($arProp['USER_TYPE']) && !array_key_exists($arProp['USER_TYPE'], $arPropUserTypeList))
				continue;

			if (!empty($arProp['USER_TYPE'])
				&& is_array($arPropUserTypeList[$arProp['USER_TYPE']]))
			{
				if (array_key_exists('GetPublicFilterHTML', $arPropUserTypeList[$arProp['USER_TYPE']]))
				{
					$arFilter[$i] = array(
						'id' => $propID,
						'name' => htmlspecialcharsex($arProp['NAME']),
						'type' => 'custom',
						'enable_settings' => false,
						'value' => call_user_func_array(
							$arPropUserTypeList[$arProp['USER_TYPE']]['GetPublicFilterHTML'],
							array(
								$arProp,
								array(
									'VALUE'=>$propID,
									'FORM_NAME'=>'filter_'.$sFormName,
									'GRID_ID' => $sFormName,
								),
							)
						),
					);
					$arFilterable[$propID] = ($arProp['PROPERTY_TYPE'] === 'S') ? '?' : '';
					if (array_key_exists('AddFilterFields', $arPropUserTypeList[$arProp['USER_TYPE']]))
						$arCustomFilter[$propID] = array(
							'callback' => $arPropUserTypeList[$arProp['USER_TYPE']]['AddFilterFields'],
							'filter' => &$arFilter[$i],
						);
				}
			}
			else if (empty($arProp['USER_TYPE']))
			{
				if ($arProp["PROPERTY_TYPE"] === "F")
				{
				}
				else if ($arProp["PROPERTY_TYPE"] === "N")
				{
					$arFilter[$i] = array(
						"id" => $propID,
						"name" => htmlspecialcharsex($arProp["NAME"]),
						"type" => "number",
					);
					$arFilterable[$propID] = "";
				}
				else if ($arProp["PROPERTY_TYPE"] === "G")
				{
					$items = array();
					$propSections = CIBlockSection::GetList(array("left_margin" => "asc"), array("IBLOCK_ID" => $arProp["LINK_IBLOCK_ID"]));
					while($arSection = $propSections->Fetch())
						$items[$arSection["ID"]] = str_repeat(". ", $arSection["DEPTH_LEVEL"]-1).$arSection["NAME"];
					unset($propSections, $arSection);

					$arFilter[$i] = array(
						"id" => $propID,
						"name" => htmlspecialcharsex($arProp["NAME"]),
						"type" => "list",
						"items" => $items,
						"params" => array("size"=>5, "multiple"=>"multiple"),
						"valign" => "top",
					);
					$arFilterable[$propID] = "";
				}
				else if ($arProp["PROPERTY_TYPE"] === "E")
				{
					//Should be handled in template
					$arFilter[$i] = array(
						"id" => $propID,
						"name" => htmlspecialcharsex($arProp["NAME"]),
						"type" => "E",
						"value" => $arProp,
					);
					$arFilterable[$propID] = "";
				}
				else if ($arProp["PROPERTY_TYPE"] === "L")
				{
					$items = array();
					$propEnums = CIBlockProperty::GetPropertyEnum($arProp["ID"]);
					while($ar_enum = $propEnums->Fetch())
						$items[$ar_enum["ID"]] = $ar_enum["VALUE"];
					unset($propEnums);

					$arFilter[$i] = array(
						"id" => $propID,
						"name" => htmlspecialcharsex($arProp["NAME"]),
						"type" => "list",
						"items" => $items,
						"params" => array("size"=>5, "multiple"=>"multiple"),
						"valign" => "top",
					);
					$arFilterable[$propID] = "";
				}
				else if ($arProp["PROPERTY_TYPE"] === 'S')
				{
					$arFilter[$i] = array(
						"id" => $propID,
						"name" => htmlspecialcharsex($arProp["NAME"]),
					);
					$arFilterable[$propID] = "?";
				}
				else
				{
					$arFilter[$i] = array(
						"id" => $propID,
						"name" => htmlspecialcharsex($arProp["NAME"]),
					);
					$arFilterable[$propID] = "";
				}
			}
			$i++;
		}
	}
	public static function ListAddHeades($arPropUserTypeList, $arProps, &$arHeaders)
	{
		foreach ($arProps as $propID => $arProp)
		{
			if (!empty($arProp['USER_TYPE']) && !array_key_exists($arProp['USER_TYPE'], $arPropUserTypeList))
				continue;

			if ((!empty($arProp['USER_TYPE'])
					&& is_array($arPropUserTypeList[$arProp['USER_TYPE']])
					&& array_key_exists('GetPublicViewHTML', $arPropUserTypeList[$arProp['USER_TYPE']]))
				|| empty($arProp['USER_TYPE']))
			{
				$arHeaders[] = array(
					'id' => $propID,
					'name' => htmlspecialcharsex($arProp['NAME']),
					'default' => false,
					'sort' => $arProp['MULTIPLE']=='Y'? '': $propID,
					'editable' => false
				);
			}
		}
	}
}
