<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 */


IncludeModuleLangFile(__FILE__);

class CTaskReminders
{
	const REMINDER_TRANSPORT_JABBER = "J";
	const REMINDER_TRANSPORT_EMAIL = "E";
	const REMINDER_TYPE_DEADLINE = "D";
	const REMINDER_TYPE_COMMON = "A";
	protected $userId = false;


	public function __construct ($arParams = array())
	{
		if (isset($arParams['USER_ID']))
			$this->userId = $arParams['USER_ID'];
	}


	function CheckFields(&$arFields,
		/** @noinspection PhpUnusedParameterInspection */ $ID = false)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		$arMsg = Array();

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

		if (!is_set($arFields, "TASK_ID"))
		{
			$arMsg[] = array("text" => GetMessage("TASKS_BAD_TASK_ID"), "id" => "ERROR_TASKS_BAD_TASK_ID");
		}
		else
		{
			if ($this->userId !== false)
			{
				/** @noinspection PhpDeprecationInspection */
				$r = CTasks::GetByID($arFields["TASK_ID"], true, array('USER_ID' => (int) $this->userId));
			}
			else
			{
				/** @noinspection PhpDeprecationInspection */
				$r = CTasks::GetByID($arFields["TASK_ID"]);
			}

			if (!$r->Fetch())
			{
				$arMsg[] = array("text" => GetMessage("TASKS_BAD_TASK_ID_EX"), "id" => "ERROR_TASKS_BAD_TASK_ID_EX");
			}
		}

		if (!is_set($arFields, "REMIND_DATE") || strlen(trim($arFields["REMIND_DATE"])) <= 0)
		{
			$arMsg[] = array("text" => GetMessage("TASKS_BAD_REMIND_DATE"), "id" => "ERROR_BAD_TASKS_REMIND_DATE");
		}

		if (!empty($arMsg))
		{
			$e = new CAdminException($arMsg);
			$APPLICATION->ThrowException($e);
			return false;
		}

		//Defaults
		if (!is_set($arFields, "TYPE") || $arFields["TYPE"] != self::REMINDER_TYPE_DEADLINE)
			$arFields["TYPE"] = self::REMINDER_TYPE_COMMON;

		if (!is_set($arFields, "TRANSPORT") || $arFields["TRANSPORT"] != self::REMINDER_TRANSPORT_JABBER)
			$arFields["TRANSPORT"] = self::REMINDER_TRANSPORT_EMAIL;

		return true;
	}


	function Add($arFields)
	{
		/** @global CDatabase $DB */
		global $DB;
		if ($this->CheckFields($arFields))
		{
			$arFields["ID"] = 1;
			$ID = $DB->Add("b_tasks_reminder", $arFields, Array(), "tasks");

			foreach(GetModuleEvents('tasks', 'OnTaskReminderAdd', true) as $arEvent)
			{
				ExecuteModuleEventEx($arEvent, array($ID, $arFields));
			}

			return $ID;
		}

		return false;
	}


	function GetFilter($arFilter)
	{
		global $DB;

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
					$arSqlSearch[] = CTasks::FilterCreate("TR.".$key, $val, "number", $bFullJoin, $cOperationType);
					break;

				case "REMIND_DATE":
					$arSqlSearch[] = CTasks::FilterCreate("TR.".$key, $DB->CharToDateFunction($val, "SHORT"), "date", $bFullJoin, $cOperationType);
					break;
			}
		}

		return $arSqlSearch;
	}


	function GetList($arOrder, $arFilter)
	{
		/** @global CDatabase $DB */
		global $DB;

		$arSqlSearch = CTaskReminders::GetFilter($arFilter);

		$strSql = "
			SELECT
				TR.*,
				".$DB->DateToCharFunction("TR.REMIND_DATE", "SHORT")." AS REMIND_DATE
			FROM
				b_tasks_reminder TR
			".(sizeof($arSqlSearch) ? "WHERE ".implode(" AND ", $arSqlSearch) : "")."
		";

		if (!is_array($arOrder))
			$arOrder = Array();

		foreach ($arOrder as $by => $order)
		{
			$by = strtolower($by);
			$order = strtolower($order);
			if ($order != "asc")
				$order = "desc";

			if ($by == "task")
				$arSqlOrder[] = " TR.TASK_ID ".$order." ";
			elseif ($by == "user")
				$arSqlOrder[] = " TR.USER_ID ".$order." ";
			elseif ($by == "date")
				$arSqlOrder[] = " TR.REMIND_DATE ".$order." ";
			elseif ($by == "rand")
				$arSqlOrder[] = CTasksTools::getRandFunction();
			else
				$arSqlOrder[] = " TR.TASK_ID ".$order." ";
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


	public static function DeleteByDate($REMIND_DATE)
	{
		return self::Delete(array("REMIND_DATE" => $REMIND_DATE));
	}


	public static function DeleteByTaskID($TASK_ID)
	{
		return self::Delete(array("TASK_ID" => (int) $TASK_ID));
	}


	public static function DeleteByUserID($USER_ID)
	{
		return self::Delete(array("USER_ID" => (int) $USER_ID));
	}


	function Delete($arFilter)
	{
		global $DB;

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
					$arSqlSearch[] = CTasks::FilterCreate($key, $val, "number", $bFullJoin, $cOperationType);
					break;

				case "REMIND_DATE":
					$arSqlSearch[] = CTasks::FilterCreate($key, $DB->CharToDateFunction($val, "SHORT"), "date", $bFullJoin, $cOperationType);
					break;

				case 'TYPE':
					$arSqlSearch[] = CTasks::FilterCreate($key, $val, 'string_equal', $bFullJoin, $cOperationType);
					break;

				case 'TRANSPORT':
					$arSqlSearch[] = CTasks::FilterCreate($key, $val, 'string_equal', $bFullJoin, $cOperationType);
					break;
			}
		}

		$strSqlSearch = "";
		$arSqlSearchCnt = count($arSqlSearch);
		for ($i = 0; $i < $arSqlSearchCnt; $i++)
			if (strlen($arSqlSearch[$i]) > 0)
				$strSqlSearch .= " AND ".$arSqlSearch[$i]." ";

		if (sizeof($arSqlSearch))
		{
			$strSql = "
				DELETE FROM
					b_tasks_reminder
				WHERE ".implode(" AND ", $arSqlSearch)."
			";

			return $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return false;
	}


	function SendAgent()
	{
		global $DB;

		/** @noinspection PhpDynamicAsStaticMethodCallInspection */
		$arFilter = array(
			"<REMIND_DATE" => date(
			$DB->DateFormatToPHP(CSite::GetDateFormat("FULL")),
			strtotime("tomorrow")
			)
		);

		$rsReminders = CTaskReminders::GetList(array("date" => "asc"), $arFilter);

		while ($arReminder = $rsReminders->Fetch())
		{
			$rsTask = CTasks::GetByID($arReminder["TASK_ID"], false);

			if ($arTask = $rsTask->Fetch())
			{
				// remind about not closed tasks only
				if ($arTask['CLOSED_DATE'] === NULL)
				{
					/** @noinspection PhpDynamicAsStaticMethodCallInspection */
					$rsUser = CUser::GetByID($arReminder["USER_ID"]);
					if ($arUser = $rsUser->Fetch())
					{
						$arTask["PATH_TO_TASK"] = CTaskNotifications::GetNotificationPath($arUser, $arTask["ID"]);

						$arFilterForSendedRemind = array_merge(
							$arFilter,
							array(
								'TASK_ID'   => $arReminder['TASK_ID'],
								'USER_ID'   => $arReminder['USER_ID'],
								'TRANSPORT' => $arReminder['TRANSPORT'],
								'TYPE'      => $arReminder['TYPE']
							)
						);

						CTaskReminders::Delete($arFilterForSendedRemind);

						if (
							$arReminder["TRANSPORT"] == self::REMINDER_TRANSPORT_EMAIL 
							|| !CModule::IncludeModule("socialnetwork") 
							|| !CTaskReminders::__SendJabberReminder($arUser["ID"], $arTask)
						)
						{
							CTaskReminders::__SendEmailReminder($arUser["EMAIL"], $arTask);
						}
					}
				}
			}
		}

		// Some older items can still exists (for removed users, etc.)
		CTaskReminders::Delete($arFilter);

		return "CTaskReminders::SendAgent();";
	}


	private function __SendJabberReminder($USER_ID, $arTask)
	{
		if (IsModuleInstalled("im") && CModule::IncludeModule("im"))
		{
			$arMessageFields = array(
				"TO_USER_ID" => $USER_ID,
				"FROM_USER_ID" => $arTask["CREATED_BY"], 
				"NOTIFY_TYPE" => IM_NOTIFY_FROM, 
				"NOTIFY_MODULE" => "tasks", 
				"NOTIFY_EVENT" => "reminder", 
				"NOTIFY_MESSAGE" => str_replace(
					array("#TASK_TITLE#", "#PATH_TO_TASK#"), 
					array('[URL='.$arTask["PATH_TO_TASK"].']'.$arTask["TITLE"].'[/URL]', ''), 
					GetMessage("TASKS_REMINDER")
				),
				"NOTIFY_MESSAGE_OUT" => str_replace(
					array("#TASK_TITLE#", "#PATH_TO_TASK#"), 
					array($arTask["TITLE"], '( '.$arTask["PATH_TO_TASK"].' )'), 
					GetMessage("TASKS_REMINDER")
				)
			);

			return CIMNotify::Add($arMessageFields);
		}

		return false;
	}


	private function __SendEmailReminder($USER_EMAIL, $arTask)
	{
		$arEventFields = array(
			"PATH_TO_TASK" => $arTask["PATH_TO_TASK"],
			"TASK_TITLE" => $arTask["TITLE"],
			"EMAIL_TO" => $USER_EMAIL,
		);
		/** @noinspection PhpDynamicAsStaticMethodCallInspection */
		CEvent::Send("TASK_REMINDER", $arTask["SITE_ID"], $arEventFields, "N");
	}
}