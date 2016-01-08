<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 */

class CTaskMembers
{
	function CheckFields(&$arFields, /** @noinspection PhpUnusedParameterInspection */ $ID = false)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		$arMsg = array();

		if (!is_set($arFields, "TASK_ID"))
		{
			$arMsg[] = array("text" => GetMessage("TASKS_BAD_TASK_ID"), "id" => "ERROR_TASKS_BAD_TASK_ID");
		}
		else
		{
			/** @noinspection PhpDeprecationInspection */
			$r = CTasks::GetByID($arFields["TASK_ID"], false);
			if (!$r->Fetch())
			{
				$arMsg[] = array("text" => GetMessage("TASKS_BAD_TASK_ID_EX"), "id" => "ERROR_TASKS_BAD_TASK_ID_EX");
			}
		}

		if (!is_set($arFields, "USER_ID"))
		{
			$arMsg[] = array("text" => GetMessage("TASKS_BAD_USER_ID"), "id" => "ERROR_TASKS_BAD_USER_ID");
		}
		else
		{
			/** @noinspection PhpDynamicAsStaticMethodCallInspection */
			$r = CUser::GetByID($arFields["USER_ID"]);
			if (!$r->Fetch())
			{
				$arMsg[] = array("text" => GetMessage("TASKS_BAD_USER_ID_EX"), "id" => "ERROR_TASKS_BAD_USER_ID_EX");
			}
		}

		if (!empty($arMsg))
		{
			$e = new CAdminException($arMsg);
			$APPLICATION->ThrowException($e);
			return false;
		}

		//Defaults
		if (!is_set($arFields, "TYPE") || !in_array($arFields["TYPE"], Array("A", "U")))
			$arFields["TYPE"] = "A";

		return true;
	}


	function Add($arFields)
	{
		global $DB;

		if ($this->CheckFields($arFields))
		{
			$arFields["ID"] = 1;
			$ID = $DB->Add("b_tasks_member", $arFields, Array(), "tasks");

			return $ID;
		}

		return false;
	}


	function GetFilter($arFilter)
	{
		if (!is_array($arFilter))
			$arFilter = Array();

		$arSqlSearch = Array();

		foreach ($arFilter as $key => $val)
		{
			$res = CTasks::MkOperationFilter($key);
			$key = $res["FIELD"];
			$cOperationType = $res["OPERATION"];

			$key = strtoupper($key);

			switch ($key)
			{
				case "TASK_ID":
				case "USER_ID":
					$arSqlSearch[] = CTasks::FilterCreate("TM.".$key, $val, "number", $bFullJoin, $cOperationType);
					break;

				case "TYPE":
					$arSqlSearch[] = CTasks::FilterCreate("TM.".$key, $val, "string_equal", $bFullJoin, $cOperationType);
					break;
			}
		}

		return $arSqlSearch;
	}


	public static function GetList($arOrder, $arFilter)
	{
		global $DB;

		$arSqlSearch = array_filter(CTaskMembers::GetFilter($arFilter));

		$strSqlSearch = "";
		$arSqlSearchCnt = count($arSqlSearch);
		for ($i = 0; $i < $arSqlSearchCnt; $i++)
			if (strlen($arSqlSearch[$i]) > 0)
				$strSqlSearch .= " AND ".$arSqlSearch[$i]." ";

		$strSql = "
			SELECT
				TM.*
			FROM
				b_tasks_member TM
			".(sizeof($arSqlSearch) ? "WHERE ".implode(" AND ", $arSqlSearch) : "")."
		";

		if (!is_array($arOrder))
			$arOrder = Array();

		foreach ($arOrder as $by => $order)
		{
			$by    = strtolower($by);
			$order = strtolower($order);

			if ($order != "asc")
				$order = "desc";

			if (($by === 'task') || ($by === 'task_id'))
				$arSqlOrder[] = " TM.TASK_ID ".$order." ";
			elseif (($by === 'user') || ($by === 'user_id'))
				$arSqlOrder[] = " TM.USER_ID ".$order." ";
			elseif ($by === 'type')
				$arSqlOrder[] = " TM.TYPE ".$order." ";
			elseif ($by == "rand")
				$arSqlOrder[] = CTasksTools::getRandFunction();
		}

		$strSqlOrder = "";
		DelDuplicateSort($arSqlOrder);
		$arSqlOrderCnt = count($arSqlOrder);
		for ($i = 0; $i < $arSqlOrderCnt; $i++)
		{
			if ($i == 0)
				$strSqlOrder = " ORDER BY ";
			else
				$strSqlOrder .= ",";

			$strSqlOrder .= $arSqlOrder[$i];
		}

		$strSql .= $strSqlOrder;

		return $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}


	function DeleteByUserID($USER_ID)
	{
		global $DB;

		$USER_ID = intval($USER_ID);
		$strSql = "DELETE FROM b_tasks_member WHERE USER_ID = ".$USER_ID;
		return $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}


	function DeleteByTaskID($TASK_ID, $TYPE = null)
	{
		global $DB;

		$TASK_ID = intval($TASK_ID);
		$strSql = "DELETE FROM b_tasks_member WHERE TASK_ID = ".$TASK_ID;
		if ($TYPE != null && in_array($TYPE, array("A", "U")))
		{
			$strSql .= " AND TYPE = '".$TYPE."'";
		}
		return $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}
}