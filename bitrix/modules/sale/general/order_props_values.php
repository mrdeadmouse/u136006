<?
IncludeModuleLangFile(__FILE__);

class CAllSaleOrderPropsValue
{
	function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		if ((is_set($arFields, "ORDER_ID") || $ACTION=="ADD") && IntVal($arFields["ORDER_ID"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SKGOPV_EMPTY_ORDER_ID"), "EMPTY_ORDER_ID");
			return false;
		}
		
		if ((is_set($arFields, "ORDER_PROPS_ID") || $ACTION=="ADD") && IntVal($arFields["ORDER_PROPS_ID"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SKGOPV_EMPTY_PROP_ID"), "EMPTY_ORDER_PROPS_ID");
			return false;
		}

		if (is_set($arFields, "ORDER_ID"))
		{
			if (!($arOrder = CSaleOrder::GetByID($arFields["ORDER_ID"])))
			{
				$GLOBALS["APPLICATION"]->ThrowException(str_replace("#ID#", $arFields["ORDER_ID"], GetMessage("SKGOPV_NO_ORDER_ID")), "ERROR_NO_ORDER");
				return false;
			}
		}

		if (is_set($arFields, "ORDER_PROPS_ID"))
		{
			if (!($arOrder = CSaleOrderProps::GetByID($arFields["ORDER_PROPS_ID"])))
			{
				$GLOBALS["APPLICATION"]->ThrowException(str_replace("#ID#", $arFields["ORDER_PROPS_ID"], GetMessage("SKGOPV_NO_PROP_ID")), "ERROR_NO_PROPERY");
				return false;
			}
			
			if (is_set($arFields, "ORDER_ID"))
			{
				$arFilter = Array(
						"ORDER_ID" => $arFields["ORDER_ID"],
						"ORDER_PROPS_ID" => $arFields["ORDER_PROPS_ID"],
					);
				if(IntVal($ID) > 0)
					$arFilter["!ID"] = $ID;
				$dbP = CSaleOrderPropsValue::GetList(Array(), $arFilter);
				if($arP = $dbP->Fetch())
				{
					$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SKGOPV_DUPLICATE_PROP_ID", Array("#ID#" => $arFields["ORDER_PROPS_ID"], "#ORDER_ID#" => $arFields["ORDER_ID"])), "ERROR_DUPLICATE_PROP_ID");
					return false;
				}
			}
		}

		return True;
	}

	function GetByID($ID)
	{
		global $DB;

		$lMig = CSaleLocation::isLocationProMigrated();

		$ID = IntVal($ID);

		if(CSaleLocation::isLocationProMigrated())
		{
			$strSql =
				"SELECT V.ID, V.ORDER_ID, V.ORDER_PROPS_ID, V.NAME, ".self::getPropertyValueFieldSelectSql('V').", P.TYPE ".
				"FROM b_sale_order_props_value V ".
				"INNER JOIN b_sale_order_props P ON (V.ORDER_PROPS_ID = P.ID) ".
				self::getLocationTableJoinSql('V').
				"WHERE V.ID = ".$ID."";
		}
		else
		{
			$strSql =
				"SELECT * ".
				"FROM b_sale_order_props_value ".
				"WHERE V.ID = ".$ID."";
		}
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($res = $db_res->Fetch())
		{
			return $res;
		}
		return False;
	}

	function Update($ID, $arFields)
	{
		global $DB;
		$ID = IntVal($ID);

		if (!CSaleOrderPropsValue::CheckFields("UPDATE", $arFields, $ID))
			return false;

		// need to check here if we got CODE or ID came
		if(isset($arFields['VALUE']) && ((string) $arFields['VALUE'] != '') && CSaleLocation::isLocationProMigrated())
		{
			$propValue = self::GetByID($ID);

			if($propValue['TYPE'] == 'LOCATION')
			{
				$arFields['VALUE'] = CSaleLocation::tryTranslateIDToCode($arFields['VALUE']);
			}
		}

		$strUpdate = $DB->PrepareUpdate("b_sale_order_props_value", $arFields);
		$strSql = 
			"UPDATE b_sale_order_props_value SET ".
			"	".$strUpdate." ".
			"WHERE ID = ".$ID." ";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return $ID;
	}

	function Delete($ID)
	{
		global $DB;
		$ID = IntVal($ID);

		$strSql = "DELETE FROM b_sale_order_props_value WHERE ID = ".$ID." ";
		return $DB->Query($strSql, True);
	}

	function DeleteByOrder($orderID)
	{
		global $DB;
		$orderID = IntVal($orderID);

		$strSql = "DELETE FROM b_sale_order_props_value WHERE ORDER_ID = ".$orderID." ";
		return $DB->Query($strSql, True);
	}

	public static function getPropertyValueFieldSelectSql($tableAlias = 'PV', $propTableAlias = 'P')
	{
		$tableAlias = \Bitrix\Main\HttpApplication::getConnection()->getSqlHelper()->forSql($tableAlias);
		$propTableAlias = \Bitrix\Main\HttpApplication::getConnection()->getSqlHelper()->forSql($propTableAlias);

		if(CSaleLocation::isLocationProMigrated())
			return "
				CASE

					WHEN 
						".$propTableAlias.".TYPE = 'LOCATION'
					THEN 
						CAST(L.ID as ".\Bitrix\Sale\Location\DB\Helper::getSqlForDataType('char', 255).")

					ELSE 
						".$tableAlias.".VALUE 
				END as VALUE, ".$tableAlias.".VALUE as VALUE_ORIG";
		else
			return $tableAlias.".VALUE";
	}

	public static function getLocationTableJoinSql($tableAlias = 'PV', $propTableAlias = 'P')
	{
		$tableAlias = \Bitrix\Main\HttpApplication::getConnection()->getSqlHelper()->forSql($tableAlias);
		$propTableAlias = \Bitrix\Main\HttpApplication::getConnection()->getSqlHelper()->forSql($propTableAlias);

		if(CSaleLocation::isLocationProMigrated())
			return "LEFT JOIN b_sale_location L ON (".$propTableAlias.".TYPE = 'LOCATION' AND ".$tableAlias.".VALUE IS NOT NULL AND (".$tableAlias.".VALUE = L.CODE))";
		else
			return " ";
	}

	public static function translateLocationIDToCode($id, $orderPropId)
	{
		if(!CSaleLocation::isLocationProMigrated())
			return $id;

		$prop = CSaleOrderProps::GetByID($orderPropId);
		if(isset($prop['TYPE']) && $prop['TYPE'] == 'LOCATION')
		{
			if((string) $id === (string) intval($id)) // real ID, need to translate
			{
				return CSaleLocation::tryTranslateIDToCode($id);
			}
		}

		return $id;
	}

	public static function addPropertyValueField($tableAlias = 'V', &$arFields, &$arSelectFields)
	{
		$tableAlias = \Bitrix\Main\HttpApplication::getConnection()->getSqlHelper()->forSql($tableAlias);

		// locations kept in CODEs, but must be shown as IDs
		if(CSaleLocation::isLocationProMigrated())
		{
			$arSelectFields = array_merge(array('PROP_TYPE'), $arSelectFields); // P.TYPE should be there and go above our join

			$arFields['VALUE'] = array("FIELD" => "
				CASE 

					WHEN 
						P.TYPE = 'LOCATION'
					THEN 
						CAST(L.ID as ".\Bitrix\Sale\Location\DB\Helper::getSqlForDataType('char', 255).")

					ELSE 
						".$tableAlias.".VALUE 
				END
			", "TYPE" => "string", "FROM" => "LEFT JOIN b_sale_location L ON (P.TYPE = 'LOCATION' AND ".$tableAlias.".VALUE IS NOT NULL AND ".$tableAlias.".VALUE = L.CODE)");
			$arFields['VALUE_ORIG'] = array("FIELD" => $tableAlias.".VALUE", "TYPE" => "string");
		}
		else
		{
			$arFields['VALUE'] = array("FIELD" => $tableAlias.".VALUE", "TYPE" => "string");
		}
	}
}
?>