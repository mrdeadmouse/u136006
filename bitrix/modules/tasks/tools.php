<?php
IncludeModuleLangFile(__FILE__);

class TasksException extends Exception
{
	const TE_TASK_NOT_FOUND_OR_NOT_ACCESSIBLE  = 0x000001;
	const TE_ACCESS_DENIED                     = 0x100002;
	const TE_ACTION_NOT_ALLOWED                = 0x000004;
	const TE_ACTION_FAILED_TO_BE_PROCESSED     = 0x000008;
	const TE_TRYED_DELEGATE_TO_WRONG_PERSON    = 0x000010;
	const TE_FILE_NOT_ATTACHED_TO_TASK         = 0x000020;
	const TE_UNKNOWN_ERROR                     = 0x000040;
	const TE_FILTER_MANIFEST_MISMATCH          = 0x000080;
	const TE_WRONG_ARGUMENTS                   = 0x000100;
	const TE_ITEM_NOT_FOUND_OR_NOT_ACCESSIBLE  = 0x000200;
	const TE_SQL_ERROR                         = 0x000400;

	const TE_FLAG_SERIALIZED_ERRORS_IN_MESSAGE = 0x100000;

	private static $errSymbolsMap = array(
		'TE_TASK_NOT_FOUND_OR_NOT_ACCESSIBLE'  => 0x000001,
		'TE_ACCESS_DENIED'                     => 0x100002,
		'TE_ACTION_NOT_ALLOWED'                => 0x000004,
		'TE_ACTION_FAILED_TO_BE_PROCESSED'     => 0x000008,
		'TE_TRYED_DELEGATE_TO_WRONG_PERSON'    => 0x000010,
		'TE_FILE_NOT_ATTACHED_TO_TASK'         => 0x000020,
		'TE_UNKNOWN_ERROR'                     => 0x000040,
		'TE_FILTER_MANIFEST_MISMATCH'          => 0x000080,
		'TE_WRONG_ARGUMENTS'                   => 0x000100,
		'TE_ITEM_NOT_FOUND_OR_NOT_ACCESSIBLE'  => 0x000200,
		'TE_SQL_ERROR'                         => 0x000400,

		'TE_FLAG_SERIALIZED_ERRORS_IN_MESSAGE' => 0x100000
	);


	public static function renderErrorCode($e)
	{
		$errCode    = $e->getCode();
		$strErrCode = $errCode . '/';

		if ($e instanceof TasksException)
		{
			$strErrCode .= 'TE';

			foreach (self::$errSymbolsMap as $symbol => $code)
			{
				if ($code & $errCode)
					$strErrCode .= '/' . substr($symbol, 3);
			}
		}
		elseif ($e instanceof CTaskAssertException)
			$strErrCode .= 'CTAE';
		else
			$strErrCode .= 'Unknown';

		return ($strErrCode);
	}
}


class CTasksPerHitOption
{
	public static function set($moduleId, $optionName, $value)
	{
		self::managePerHitOptions('write', $moduleId, $optionName, $value);
	}


	public static function get($moduleId, $optionName)
	{
		return (self::managePerHitOptions('read', $moduleId, $optionName));
	}


	public static function getHitTimestamp()
	{
		static $t = null;

		if ($t === null)
			$t = time();

		return ($t);
	}


	private static function managePerHitOptions($operation, $moduleId, $optionName, $value = null)
	{
		static $arOptions = array();

		$oName = $moduleId . '::' . $optionName;

		if ( ! array_key_exists($oName, $arOptions) )
			$arOptions[$oName] = null;

		$rc = null;

		if ($operation === 'read')
			$rc = $arOptions[$oName];
		elseif ($operation === 'write')
			$arOptions[$oName] = $value;
		else
			CTaskAssert::assert(false);

		return ($rc);
	}
}


function tasksFormatDate($in_date)
{
	$date = $in_date;
	$strDate = false;

	if (!is_int($in_date))
		$date = MakeTimeStamp($in_date);

	if ( ($date === false) || ($date === -1) || ($date === 0) )
		$date = MakeTimeStamp ($in_date);

	// It can be other date on server (relative to client), ...
	$bTzWasDisabled = ! CTimeZone::enabled();

	if ($bTzWasDisabled)
		CTimeZone::enable();

	$ts = time() + CTimeZone::getOffset();		// ... so shift cur timestamp to compensate it.

	if ($bTzWasDisabled)
		CTimeZone::disable();

	$curDateStrAtClient       = date('d.m.Y', $ts);
	$yesterdayDateStrAtClient = date('d.m.Y', strtotime('-1 day', $ts));


	if ($curDateStrAtClient === date('d.m.Y', $date))
	{
		$strDate = FormatDate("today", $date);
	}
	elseif ($yesterdayDateStrAtClient === date('d.m.Y', $date))
	{
		$strDate = FormatDate("yesterday", $date);
	}
	//	disabled, since it is not clear for foreigners
	//	elseif (date("Y", $now) == date("Y", $date))
	//	{
	//		$strDate = ToLower(FormatDate("j F", $date));
	//	}
	else
	{
		if (defined('FORMAT_DATE'))
			$strDate = FormatDate(CDatabase::DateFormatToPHP(FORMAT_DATE), $date);
		else
			$strDate = FormatDate("d.m.Y", $date);
	}

	return $strDate;
}


function tasksPeriodToStr($arParams)
{
	switch ($arParams["PERIOD"])
	{
		case "daily":
			if (intval($arParams["EVERY_DAY"]) == 1)
			{
				$strRepeat = GetMessage("TASKS_EVERY_DAY");
			}
			else
			{
				$strRepeat = str_replace("#NUM#", intval($arParams["EVERY_DAY"]), GetMessage("TASKS_EVERY_N_DAY".taskMessSuffix(intval($arParams["EVERY_DAY"]))));
			}
			break;
		case "weekly":
			if (intval($arParams["EVERY_WEEK"]) == 1)
			{
				$strRepeat = GetMessage("TASKS_EVERY_WEEK");
			}
			else
			{
				$strRepeat = str_replace("#NUM#", intval($arParams["EVERY_WEEK"]), GetMessage("TASKS_EVERY_N_WEEK".taskMessSuffix(intval($arParams["EVERY_WEEK"]))));
			}
			if (sizeof($arParams["WEEK_DAYS"]))
			{
				$arDays = array();
				foreach($arParams["WEEK_DAYS"] as $day)
				{
					if ($day < 8 && $day > 0)
					{
						$arDays[] = GetMessage("TASKS_REPEAT_DAY_2_".($day - 1));
					}
				}
				if (sizeof($arDays))
				{
					$strRepeat .= str_replace("#DAYS#", implode(", ", $arDays), GetMessage("TASKS_AT_WEEK_DAYS"));
				}
			}
			break;
		case "monthly":
			if ($arParams["MONTHLY_TYPE"] == 1)
			{
				$strRepeat = str_replace("#DAY#", $arParams["MONTHLY_DAY_NUM"], GetMessage("TASKS_MONTHLY_DAY_NUM".taskMessSuffix(intval($arParams["MONTHLY_DAY_NUM"]))));
				if (intval($arParams["MONTHLY_MONTH_NUM_1"]) < 2)
				{
					$strRepeat .= " ".GetMessage("TASKS_EVERY_MONTH");
				}
				else
				{
					$strRepeat .= " ".str_replace("#NUM#", intval($arParams["MONTHLY_MONTH_NUM_1"]), GetMessage("TASKS_EVERY_N_MONTH".taskMessSuffix(intval($arParams["MONTHLY_MONTH_NUM_1"]))));
				}
			}
			else
			{
				$arParams["MONTHLY_WEEK_DAY"] = intval($arParams["MONTHLY_WEEK_DAY"]);
				if ($arParams["MONTHLY_WEEK_DAY"] < 0 || $arParams["MONTHLY_WEEK_DAY"] > 6)
				{
					$arParams["MONTHLY_WEEK_DAY"] = 0;
				}
				$arParams["MONTHLY_WEEK_DAY_NUM"] = intval($arParams["MONTHLY_WEEK_DAY_NUM"]);
				if ($arParams["MONTHLY_WEEK_DAY_NUM"] < 0 || $arParams["MONTHLY_WEEK_DAY_NUM"] > 4)
				{
					$arParams["MONTHLY_WEEK_DAY_NUM"] = 0;
				}
				$strRepeat = GetMessage("TASKS_REPEAT_DAY_NUM_".$arParams["MONTHLY_WEEK_DAY_NUM"])." ".GetMessage("TASKS_REPEAT_DAY_".$arParams["MONTHLY_WEEK_DAY"]);
				if (intval($arParams["MONTHLY_MONTH_NUM_2"]) < 2)
				{
					$strRepeat .= " ".GetMessage("TASKS_EVERY_MONTH");
				}
				else
				{
					$strRepeat .= " ".str_replace("#NUM#", intval($arParams["MONTHLY_MONTH_NUM_2"]), GetMessage("TASKS_EVERY_N_MONTH_2".taskMessSuffix(intval($arParams["MONTHLY_MONTH_NUM_2"]))));
				}
			}
			break;
		case "yearly":
			if ($arParams["YEARLY_TYPE"] == 1)
			{
				$arParams["YEARLY_MONTH_1"] = intval($arParams["YEARLY_MONTH_1"]);
				if ($arParams["YEARLY_MONTH_1"] > 11 || $arParams["YEARLY_MONTH_1"] < 0)
				{
					$arParams["YEARLY_MONTH_1"] = 0;
				}
				$strRepeat = str_replace(array("#NUM#", "#MONTH#"), array($arParams["YEARLY_DAY_NUM"], GetMessage("TASKS_REPEAT_MONTH_".$arParams["YEARLY_MONTH_1"])), GetMessage("TASKS_EVERY_N_DAY_OF_MONTH".taskMessSuffix(intval($arParams["YEARLY_DAY_NUM"]))));
			}
			else
			{
				$arParams["YEARLY_MONTH_2"] = intval($arParams["YEARLY_MONTH_2"]);
				if ($arParams["YEARLY_MONTH_2"] > 11 || $arParams["YEARLY_MONTH_2"] < 0)
				{
					$arParams["YEARLY_MONTH_2"] = 0;
				}
				$arParams["YEARLY_WEEK_DAY"] = intval($arParams["YEARLY_WEEK_DAY"]);
				if ($arParams["YEARLY_WEEK_DAY"] < 0 || $arParams["YEARLY_WEEK_DAY"] > 6)
				{
					$arParams["YEARLY_WEEK_DAY"] = 0;
				}
				$arParams["YEARLY_WEEK_DAY_NUM"] = intval($arParams["YEARLY_WEEK_DAY_NUM"]);
				if ($arParams["YEARLY_WEEK_DAY_NUM"] < 0 || $arParams["YEARLY_WEEK_DAY_NUM"] > 4)
				{
					$arParams["YEARLY_WEEK_DAY_NUM"] = 0;
				}
				$strRepeat = str_replace(array("#NUM#", "#DAY#", "#MONTH#"), array(GetMessage("TASKS_REPEAT_DAY_NUM_".$arParams["YEARLY_WEEK_DAY_NUM"]), GetMessage("TASKS_REPEAT_DAY_".$arParams["YEARLY_WEEK_DAY"]), GetMessage("TASKS_REPEAT_MONTH_".$arParams["YEARLY_MONTH_2"])), GetMessage("TASKS_AT_N_DAY_OF_MONTH".taskMessSuffix(intval($arParams["TASKS_REPEAT_DAY_NUM_"]))));
			}
			break;
	}

	return $strRepeat;
}


function taskMessSuffix($number)
{
	switch ($number)
	{
		case 2:
			return "_ND";
		case 3:
			return "_RD";
		default:
			return "_TH";
	}
}

function tasksFormatName($name, $lastName, $login, $secondName = "", $nameTemplate = "", $bEscapeSpecChars = false)
{
	if ($nameTemplate != "")
	{
		$result = CUser::FormatName($nameTemplate, array(	"NAME" 			=> $name, 
															"LAST_NAME" 	=> $lastName, 
															"SECOND_NAME" 	=> $secondName, 
															"LOGIN"			=> $login),
			true,
			$bEscapeSpecChars);

		return $result;
	}

	if ($name || $lastName)
	{
		$rc = $name.($name && $lastName ? " " : "").$lastName;
	}
	else
	{
		$rc = $login;
	}

	if ($bEscapeSpecChars)
		$rc = htmlspecialcharsbx($rc);

	return ($rc);
}

function tasksFormatNameShort($name, $lastName, $login, $secondName = "", $nameTemplate = "", $bEscapeSpecChars = false)
{
	if ($nameTemplate != "")
	{
		$result = CUser::FormatName($nameTemplate, array(	"NAME" 			=> $name, 
															"LAST_NAME" 	=> $lastName, 
															"SECOND_NAME" 	=> $secondName, 
															"LOGIN"			=> $login),
			true,
			$bEscapeSpecChars);

		return $result;
	}

	if ($name && $lastName)
	{
		if ( ! $bEscapeSpecChars )
			$rc = $lastName." ".substr(htmlspecialcharsBack($name), 0, 1).".";
		else
			$rc = $lastName." ".substr($name, 0, 1).".";
	}
	elseif ($lastName)
	{
		$rc = $lastName;
	}
	elseif ($name)
	{
		$rc = $name;
	}
	else
	{
		$rc = $login;
	}

	if ($bEscapeSpecChars)
		$rc = htmlspecialcharsbx($rc);

	return ($rc);
}


function tasksFormatHours($hours)
{
	$hoursOriginal = $hours = intval($hours);

	$hours %= 100;
	if ($hours >= 5 && $hours <= 20)
		return $hoursOriginal. " ".GetMessage("TASKS_HOURS_P");

	$hours %= 10;
	if ($hours == 1)
		return $hoursOriginal. " ".GetMessage("TASKS_HOURS_N");

	if ($hours >= 2 && $hours <= 4)
		return $hoursOriginal. " ".GetMessage("TASKS_HOURS_G");

	return $hoursOriginal. " ".GetMessage("TASKS_HOURS_P");

}


function tasksTimeCutZeros($time)
{
	if (IsAmPmMode())
	{
		return trim(substr($time, 11, 11) == "12:00:00 am" ? substr($time, 0, 10) : substr($time, 0, 22));
	}
	else
	{
		return substr($time, 11, 8) == "00:00:00" ? substr($time, 0, 10) : substr($time, 0, 16);
	}

}


function tasksGetItemMenu($task, $arPaths, $site_id = SITE_ID, $bGantt = false, $top = false, $bSkipJsMenu = false)
{
	global $USER;

	$arAllowedTaskActions = array();
	if (isset($task['META:ALLOWED_ACTIONS']))
		$arAllowedTaskActions = $task['META:ALLOWED_ACTIONS'];
	elseif ($task['ID'])
	{
		$oTask = CTaskItem::getInstanceFromPool($task['ID'], $USER->getId());
		$arAllowedTaskActions = $oTask->getAllowedTaskActionsAsStrings();
		$task['META:ALLOWED_ACTIONS'] = $arAllowedTaskActions;
	}

	$viewUrl = CComponentEngine::MakePathFromTemplate($arPaths["PATH_TO_TASKS_TASK"], array("task_id" => $task["ID"], "action" => "view"));
	$editUrl = CComponentEngine::MakePathFromTemplate($arPaths["PATH_TO_TASKS_TASK"], array("task_id" => $task["ID"], "action" => "edit"));
	$copyUrl = CComponentEngine::MakePathFromTemplate($arPaths["PATH_TO_TASKS_TASK"], array("task_id" => 0, "action" => "edit"));
	$createUrl = CComponentEngine::MakePathFromTemplate($arPaths["PATH_TO_TASKS_TASK"], array("task_id" => 0, "action" => "edit"));
	$createUrl = $createUrl.(strpos($createUrl, "?") === false ? "?" : "&")."PARENT_ID=".$task["ID"];
	?>
		{
			text : "<?php echo GetMessage("TASKS_VIEW_TASK")?>",
			title : "<?php echo GetMessage("TASKS_VIEW_TASK_EX")?>",
			className : "task-menu-popup-item-view",
			href : "<?php echo CUtil::JSEscape($viewUrl)?>",
			<?php
			if ( ! $bSkipJsMenu )
			{
				?>
				onclick : function(event) {<?php if ($top):?>window.top.<?php endif?>ShowPopupTask(<?php echo $task["ID"]?>, event); this.popupWindow.close();}
				<?php
			}
			?>
		},

		<?php if ($arAllowedTaskActions['ACTION_EDIT']):?>
		{
			text : "<?php echo GetMessage("TASKS_EDIT_TASK")?>",
			title : "<?php echo GetMessage("TASKS_EDIT_TASK_EX")?>",
			className : "task-menu-popup-item-edit",
			href : "<?php echo CUtil::JSEscape($editUrl)?>",
			<?php
			if ( ! $bSkipJsMenu )
			{
				?>
				onclick : function(event) {<?php if ($top):?>window.top.<?php endif?>EditPopupTask(<?php echo $task["ID"]?>, event); this.popupWindow.close();}
				<?php
			}
			?>
		},
		<?php endif?>

		{
			text : "<?php echo GetMessage("TASKS_ADD_SUBTASK"); ?>",
			title : "<?php echo GetMessage("TASKS_ADD_SUBTASK"); ?>",
			className : "task-menu-popup-item-create",
			href : "<?php echo CUtil::JSEscape($createUrl)?>",
			<?php
			if ( ! $bSkipJsMenu )
			{
				?>
				onclick : function(event) {<?php if ($top):?>window.top.<?php endif?>AddPopupSubtask(<?php echo $task["ID"]?>, event); this.popupWindow.close();}
				<?php
			}
			?>
		},
		<?php
		if ( ! $bSkipJsMenu )
		{
			if (!$bGantt):?>
			{
				text : "<?php echo GetMessage("TASKS_ADD_QUICK_SUBTASK")?>",
				title : "<?php echo GetMessage("TASKS_ADD_QUICK_SUBTASK")?>",
				className : "task-menu-popup-item-create-quick",
				onclick : function(event) {
					ShowQuickTask(
						null,
						{
							parent: <?php echo $task["ID"];
								if ($task["GROUP_ID"])
								{
									?>,
									group:{
										id: <?php echo $task["GROUP_ID"]?>,
										title: '<?php echo CUtil::JSEscape($task["GROUP_NAME"])?>'
									}<?php
								} ?>
						}
					);
					this.popupWindow.close();
				}
			},<?php
			elseif(
				$arAllowedTaskActions['ACTION_EDIT']
				|| $arAllowedTaskActions['ACTION_CHANGE_DEADLINE']
			):?>
			{
				text : "<?php if(!$task["DEADLINE"]):?><?php echo GetMessage("TASKS_ADD_DEADLINE")?><?php else:?><?php echo GetMessage("TASKS_REMOVE_DEADLINE")?><?php endif?>",
				title : "<?php if(!$task["DEADLINE"]):?><?php echo GetMessage("TASKS_ADD_DEADLINE")?><?php else:?><?php echo GetMessage("TASKS_REMOVE_DEADLINE")?><?php endif?>",
				className : "<?php if(!$task["DEADLINE"]):?>task-menu-popup-item-add-deadline<?php else:?>task-menu-popup-item-remove-deadline<?php endif?>",
				onclick : function(event, item)
				{
					<?php if ($top):?>
					var BX = window.top.BX;
					<?php endif?>
					if (BX.hasClass(item.layout.item, "task-menu-popup-item-add-deadline"))
					{
						BX.removeClass(item.layout.item, "task-menu-popup-item-add-deadline");
						BX.addClass(item.layout.item, "task-menu-popup-item-remove-deadline");
						item.layout.text.innerHTML = "<?php echo GetMessage("TASKS_REMOVE_DEADLINE")?>";

						var deadline = BX.GanttChart.convertDateFromUTC(this.params.task.dateEnd);
						deadline.setDate(deadline.getDate() + 3);
						this.params.task.setDateDeadline(deadline);
						this.params.task.redraw();
						this.popupWindow.close();

						var data = {
							mode : "deadline",
							sessid : BX.message("bitrix_sessid"),
							id : this.params.task.id,
							deadline : tasksFormatDate(deadline)
						};
						BX.ajax.post(ajaxUrl, data);
					}
					else
					{
						BX.removeClass(item.layout.item, "task-menu-popup-item-remove-deadline");
						BX.addClass(item.layout.item, "task-menu-popup-item-add-deadline");
						item.layout.text.innerHTML = "<?php echo GetMessage("TASKS_ADD_DEADLINE")?>";
						this.params.task.setDateDeadline(null);
						this.params.task.redraw();
						this.popupWindow.close();

						var data = {
							mode : "deadline",
							sessid : BX.message("bitrix_sessid"),
							id : this.params.task.id,
							deadline : ""
						};
						BX.ajax.post(ajaxUrl, data);
					}
				}
			},<?php
			endif;
		}

		if ( ! $bSkipJsMenu )
		{
			if ($arAllowedTaskActions['ACTION_COMPLETE'])
			{
				?>{ text : "<?php echo GetMessage("TASKS_CLOSE_TASK")?>", title : "<?php echo GetMessage("TASKS_CLOSE_TASK")?>", className : "task-menu-popup-item-complete", onclick : function() { <?php if ($top):?>window.top.<?php endif?>CloseTask(<?php echo $task["ID"]?>); this.popupWindow.close();} },<?php
			}

			if ($arAllowedTaskActions['ACTION_ACCEPT'])
			{
				?>{ text : "<?php echo GetMessage("TASKS_ACCEPT_TASK")?>", title : "<?php echo GetMessage("TASKS_ACCEPT_TASK")?>", className : "task-menu-popup-item-take", onclick : function(event) { <?php if ($top):?>window.top.<?php endif?>AcceptTask(<?php echo $task["ID"]?>); this.popupWindow.close();} },<?php
			}

			if ($arAllowedTaskActions['ACTION_START'])
			{
				?>{ text : "<?php echo GetMessage("TASKS_START_TASK")?>", title : "<?php echo GetMessage("TASKS_START_TASK")?>", className : "task-menu-popup-item-begin", onclick : function(event) { <?php if ($top):?>window.top.<?php endif?>StartTask(<?php echo $task["ID"]?>); this.popupWindow.close();} },<?php
			}

			if ($arAllowedTaskActions['ACTION_PAUSE'])
			{
				?>{ text : "<?php echo GetMessage("TASKS_PAUSE_TASK")?>", title : "<?php echo GetMessage("TASKS_PAUSE_TASK")?>", className : "task-menu-popup-item-pause", onclick : function(event) { <?php if ($top):?>window.top.<?php endif?>PauseTask(<?php echo $task["ID"]?>); this.popupWindow.close();} },<?php
			}

			if ($arAllowedTaskActions['ACTION_DECLINE'])
			{
				?>{ text : "<?php echo GetMessage("TASKS_DECLINE_TASK")?>", title : "<?php echo GetMessage("TASKS_DECLINE_TASK")?>", className : "task-menu-popup-item-decline", onclick : function(event) { <?php if ($top):?>window.top.<?php endif?>DeclineTask(<?php echo $task["ID"]?>); this.popupWindow.close();} },<?php
			}

			if ($arAllowedTaskActions['ACTION_RENEW'])
			{
				?>{ text : "<?php echo GetMessage("TASKS_RENEW_TASK")?>", title : "<?php echo GetMessage("TASKS_RENEW_TASK")?>", className : "task-menu-popup-item-reopen", onclick : function(event) { <?php if ($top):?>window.top.<?php endif?>RenewTask(<?php echo $task["ID"]?>); this.popupWindow.close();} },<?php
			}

			if ($arAllowedTaskActions['ACTION_DEFER'])
			{
				?>{ text : "<?php echo GetMessage("TASKS_DEFER_TASK")?>", title : "<?php echo GetMessage("TASKS_DEFER_TASK")?>", className : "task-menu-popup-item-hold", onclick : function(event) { <?php if ($top):?>window.top.<?php endif?>DeferTask(<?php echo $task["ID"]?>); this.popupWindow.close();} },<?php
			}

			if ($arAllowedTaskActions['ACTION_APPROVE'])
			{
				?>{ text : "<?php echo GetMessage("TASKS_APPROVE_TASK")?>", title : "<?php echo GetMessage("TASKS_APPROVE_TASK")?>", className : "task-menu-popup-item-accept", onclick : function(event) { <?php if ($top):?>window.top.<?php endif?>tasksListNS.approveTask(<?php echo $task["ID"]?>); this.popupWindow.close();} },<?php
			}

			if ($arAllowedTaskActions['ACTION_DISAPPROVE'])
			{
				?>{ text : "<?php echo GetMessage("TASKS_REDO_TASK")?>", title : "<?php echo GetMessage("TASKS_REDO_TASK")?>", className : "task-menu-popup-item-remake", onclick : function(event) { <?php if ($top):?>window.top.<?php endif?>tasksListNS.disapproveTask(<?php echo $task["ID"]?>); this.popupWindow.close();} },<?php
			}
		}
		?>

		{
			text : "<?php echo GetMessage("TASKS_COPY_TASK")?>",
			title : "<?php echo GetMessage("TASKS_COPY_TASK_EX")?>",
			className : "task-menu-popup-item-copy",
			href : "<?php echo $copyUrl.(strpos($copyUrl, "?") === false ? "?" : "&")."COPY=".$task["ID"]?>",
			<?php
			if ( ! $bSkipJsMenu )
			{
				?>
				onclick : function(event) {<?php if ($top):?>window.top.<?php endif?>CopyPopupTask(<?php echo $task["ID"]?>, event); this.popupWindow.close();}
				<?php
			}
			?>
		},

		<?php
		if ( ! $bSkipJsMenu )
		{
			// Only responsible person and accomplices can add task to day plan
			// And we must be not at extranet site
			if (
				(
				$task["RESPONSIBLE_ID"] == $USER->GetID() 
				|| (
					is_array($task['ACCOMPLICES']) 
					&& in_array($USER->GetID(), $task['ACCOMPLICES'])
					)
				)
				&& (!CModule::IncludeModule('extranet') || !CExtranet::IsExtranetSite())
			)
			{
				$arTasksInPlan = CTaskPlannerMaintance::getCurrentTasksList();

				// If not in day plan already
				if (
					! (
						is_array($arTasksInPlan)
						&& in_array($task["ID"], $arTasksInPlan)
					)
				)
				{
					?>
					{
						text : "<?php echo GetMessage("TASKS_ADD_TASK_TO_TIMEMAN")?>", 
						title : "<?php echo GetMessage("TASKS_ADD_TASK_TO_TIMEMAN_EX")?>", 
						className : "task-menu-popup-item-add-to-tm", 
						onclick : function(event, item) {
							<?php
							if ($top)
								echo 'top.Add2Timeman(this, ' . (int) $task["ID"] . ')';
							else
								echo 'Add2Timeman(this, ' . (int) $task["ID"] . ')';
							?>
						}
					},<?php
				}
			}
		}
		?>

		<?php
		if ( ! $bSkipJsMenu )
		{
			if ($arAllowedTaskActions['ACTION_REMOVE'])
			{
				?>
				{
					text : "<?php echo GetMessage("TASKS_DELETE_TASK")?>", 
					title : "<?php echo GetMessage("TASKS_DELETE_TASK")?>", 
					className : "task-menu-popup-item-delete", 
					onclick : function(event)
					{
						if(confirm("<?php echo GetMessage("TASKS_DELETE_TASKS_CONFIRM")?>"))
						{
							this.menuItems = [];
							this.bindElement.onclick = function() { return (false); };
							<?php if ($top):?>top.<?php endif?>DeleteTask(<?php echo $task["ID"]?>);
						}

						this.popupWindow.close();
					}
				},<?php
			}
		}
		?>
		{}
	<?php
}


function tasksRenderListItem($task, $childrenCount, $arPaths, $depth = 0, 
	$plain = false, $defer = false, $site_id = SITE_ID, $updatesCount = 0, 
	$projectExpanded = true, $taskAdded = false, 
	$componentName = "bitrix:tasks.list.item", $componentTemplate = ".default", 
	$userNameTemplate = "", $arAllowedTaskActions = null, $ynIframe = 'N'
)
{
	global $APPLICATION;

	$APPLICATION->IncludeComponent(
		$componentName, $componentTemplate, array(
			"TASK" => $task,
			"CHILDREN_COUNT" => $childrenCount,
			"PATHS" => $arPaths,
			"DEPTH" => $depth,
			"PLAIN" => $plain,
			"DEFER" => $defer,
			"SITE_ID" => $site_id,
			"UPDATES_COUNT" => $updatesCount,
			"PROJECT_EXPANDED" => $projectExpanded,
			"TASK_ADDED" => $taskAdded,
			'ALLOWED_ACTIONS' => $arAllowedTaskActions,
			'IFRAME'          => $ynIframe,
			"NAME_TEMPLATE" => $userNameTemplate
		), null, array("HIDE_ICONS" => "Y")
	);
}

function templatesGetListItemActions($template, $arPaths)
{
	$addTaskUrl = CComponentEngine::MakePathFromTemplate($arPaths["PATH_TO_TASKS_TASK_ADD_BY_TEMPLATE"], array("task_id" => 0, "action" => "edit"));
	$addTaskUrl .= (strpos($addTaskUrl, "?") === false ? "?" : "&")."TEMPLATE=".$template["ID"];

	$editUrl = CComponentEngine::MakePathFromTemplate($arPaths["PATH_TO_TEMPLATES_TEMPLATE"], array("template_id" => $template["ID"], "action" => "edit"));
	$addSubTmplUrl = CComponentEngine::MakePathFromTemplate($arPaths["PATH_TO_TASKS_TASK"], array("task_id" => 0, "action" => "edit"))."?BASE_TEMPLATE=".intval($template["ID"]);
	?>

	<?if(!intval($template['BASE_TEMPLATE_ID']) && $template['TPARAM_TYPE'] != CTaskTemplates::TYPE_FOR_NEW_USER):?>
		{ text : "<?php echo GetMessage("TASKS_ADD_TEMPLATE_TASK")?>", title : "<?php echo GetMessage("TASKS_ADD_TEMPLATE_TASK")?>", className : "task-menu-popup-item-create", href : "<?php echo CUtil::JSEscape($addTaskUrl)?>" },
	<?endif?>

	<?if($template['TPARAM_TYPE'] != CTaskTemplates::TYPE_FOR_NEW_USER):?>
		{ text : "<?=GetMessage("TASKS_ADD_SUB_TEMPLATE")?>", title : "<?php echo GetMessage("TASKS_ADD_SUB_TEMPLATE")?>", className : "task-menu-popup-item-create", href : "<?=CUtil::JSEscape($addSubTmplUrl)?>" },
	<?endif?>

	{ text : "<?php echo GetMessage("TASKS_EDIT_TASK")?>", title : "<?php echo GetMessage("TASKS_EDIT_TASK")?>", className : "task-menu-popup-item-edit", href : "<?php echo CUtil::JSEscape($editUrl)?>" },
	{ text : "<?php echo GetMessage("TASKS_DELETE_TASK")?>", title : "<?php echo GetMessage("TASKS_DELETE_TASK")?>", className : "task-menu-popup-item-delete", onclick : function() { if(confirm("<?php echo GetMessage("TASKS_DELETE_TASKS_CONFIRM")?>")) this.menuItems = []; DeleteTemplate(<?php echo $template["ID"]?>); this.popupWindow.close(); } }

	<?
}

function templatesRenderListItem($template, $arPaths, $depth = 0, $plain = false, $defer = false, $nameTemplate = "")
{
	global $USER;

	$anchor_id = RandString(8);

	$addUrl = CComponentEngine::MakePathFromTemplate($arPaths["PATH_TO_TASKS_TASK"], array("task_id" => 0, "action" => "edit"));
	$addUrl .= (strpos($addUrl, "?") === false ? "?" : "&")."TEMPLATE=".$template["ID"];
	$editUrl = CComponentEngine::MakePathFromTemplate($arPaths["PATH_TO_TEMPLATES_TEMPLATE"], array("template_id" => $template["ID"], "action" => "edit"));
	?>
	<script type="text/javascript"<?php echo $defer ? "  defer=\"defer\"" : ""?>>
		tasksMenuPopup[<?php echo $template["ID"]?>] = [
			<?templatesGetListItemActions($template, $arPaths)?>
		];
		BX.tooltip(<?php echo $template["CREATED_BY"]?>, "anchor_created_<?php echo $anchor_id?>", "");
		BX.tooltip(<?php echo $template["RESPONSIBLE_ID"]?>, "anchor_responsible_<?php echo $anchor_id?>", "");
	</script>
	<tr class="task-list-item task-depth-<?php echo $depth?>" id="template-<?php echo $template["ID"]?>" ondblclick="jsUtils.Redirect([], '<?php echo CUtil::JSEscape(CComponentEngine::MakePathFromTemplate($arPaths["PATH_TO_TEMPLATES_TEMPLATE"], array("template_id" => $template["ID"], "action" => "edit")))?>');" title="<?php echo GetMessage("TASKS_DOUBLE_CLICK")?>">
		<td class="task-title-column">
			<div class="task-title-container">
				<div class="task-title-info">
					<?php if ($template["MULTITASK"] == "Y"):?><span class="task-title-multiple" title="<?php echo GetMessage("TASKS_MULTITASK")?>"></span><?php endif?><a href="<?php echo CComponentEngine::MakePathFromTemplate($arPaths["PATH_TO_TEMPLATES_TEMPLATE"], array("template_id" => $template["ID"], "action" => "edit"))?>" class="task-title-link" title=""><?php echo $template["TITLE"]?></a>
				</div>
			</div>
		</td>
		<td class="task-menu-column"><a href="javascript: void(0)" class="task-menu-button" onclick="return ShowMenuPopup(<?php echo $template["ID"]?>, this);" title="<?php echo GetMessage("TASKS_MENU")?>"><i class="task-menu-button-icon"></i></a></td>
		<td class="task-flag-column">&nbsp;</td>
		<td class="task-priority-column">
			<i class="task-priority-icon task-priority-<?php if ($template["PRIORITY"] == 0):?>low<?php elseif ($template["PRIORITY"] == 2):?>high<?php else:?>medium<?php endif?>" title="<?php echo GetMessage("TASKS_PRIORITY")?>: <?php echo GetMessage("TASKS_PRIORITY_".$template["PRIORITY"])?>"></i>
		</td>
		<td class="task-deadline-column"><?php if ($template["DEADLINE"]):?><span class="task-deadline-datetime"><span class="task-deadline-date"><?php echo tasksFormatDate($template["DEADLINE"])?></span></span><?php if(date("H:i", strtotime($template["DEADLINE"])) != "00:00"):?> <span class="task-deadline-time"><?php echo date("H:i", strtotime($template["DEADLINE"]))?></span><?php endif?><?php else:?>&nbsp;<?php endif?></td>
		<td class="task-responsible-column"><a class="task-responsible-link" href="<?php echo CComponentEngine::MakePathFromTemplate($arPaths["PATH_TO_USER_PROFILE"], array("user_id" => $template["RESPONSIBLE_ID"]))?>" id="anchor_responsible_<?php echo $anchor_id?>"><?php echo tasksFormatNameShort($template["RESPONSIBLE_NAME"], $template["RESPONSIBLE_LAST_NAME"], $template["RESPONSIBLE_LOGIN"], $template["RESPONSIBLE_SECOND_NAME"], $nameTemplate)?></a></td>
		<td class="task-director-column"><a class="task-director-link" href="<?php echo CComponentEngine::MakePathFromTemplate($arPaths["PATH_TO_USER_PROFILE"], array("user_id" => $template["CREATED_BY"]))?>" id="anchor_created_<?php echo $anchor_id?>"><?php echo tasksFormatNameShort($template["CREATED_BY_NAME"], $template["CREATED_BY_LAST_NAME"], $template["CREATED_BY_LOGIN"], $template["CREATED_BY_SECOND_NAME"], $nameTemplate)?></a></td>
		<td class="task-grade-column">&nbsp;</td>
		<td class="task-complete-column">&nbsp;</td>
	</tr>
	<?php
}


function tasksRenderJSON(
	$arTask, $childrenCount, $arPaths, $bParent = false, $bGant = false, 
	$top = false, $nameTemplate = "", $arAdditionalFields = array(), $bSkipJsMenu = false
)
{
	global $USER;

	$arAllowedTaskActions = array();
	if (isset($arTask['META:ALLOWED_ACTIONS']))
		$arAllowedTaskActions = $arTask['META:ALLOWED_ACTIONS'];
	elseif ($arTask['ID'])
	{
		$oTask = CTaskItem::getInstanceFromPool($arTask['ID'], $USER->getId());
		$arAllowedTaskActions = $oTask->getAllowedTaskActionsAsStrings();
		$arTask['META:ALLOWED_ACTIONS'] = $arAllowedTaskActions;
	}

	$runningTaskId = $runningTaskTimer = null;
	if ($arTask['ALLOW_TIME_TRACKING'] === 'Y')
	{
		$oTimer           = CTaskTimerManager::getInstance($USER->getId());
		$runningTaskData  = $oTimer->getRunningTask(false);
		$runningTaskId    = $runningTaskData['TASK_ID'];
		$runningTaskTimer = time() - $runningTaskData['TIMER_STARTED_AT'];
	}

	?>
	{
		id : <?php echo $arTask["ID"]?>,
		name : "<?php echo CUtil::JSEscape($arTask["TITLE"])?>",
		<?php if ($arTask["GROUP_ID"]):?>
			projectId : <?php echo $arTask["GROUP_ID"]?>,
			projectName : '<?php echo CUtil::JSEscape($arTask['GROUP_NAME'])?>',
		<?php endif?>
		status : "<?php echo tasksStatus2String($arTask["STATUS"])?>",
		realStatus : "<?php echo $arTask["REAL_STATUS"]?>",
		url: '<?php echo CUtil::JSEscape(CComponentEngine::MakePathFromTemplate($arPaths["PATH_TO_TASKS_TASK"], array("task_id" => $arTask["ID"], "action" => "view")));?>',
		details: window.top.onDetails,
		priority : <?php echo $arTask["PRIORITY"]?>,
		mark : <?php echo !$arTask["MARK"] ? "null" : "'".$arTask["MARK"]."'"?>,
		responsible: '<?php echo CUtil::JSEscape(tasksFormatNameShort($arTask["RESPONSIBLE_NAME"], $arTask["RESPONSIBLE_LAST_NAME"], $arTask["RESPONSIBLE_LOGIN"], $arTask["RESPONSIBLE_SECOND_NAME"], $nameTemplate))?>',
		director: '<?php echo CUtil::JSEscape(tasksFormatNameShort($arTask["CREATED_BY_NAME"], $arTask["CREATED_BY_LAST_NAME"], $arTask["CREATED_BY_LOGIN"], $arTask["CREATED_BY_SECOND_NAME"], $nameTemplate))?>',
		responsibleId : <?php echo $arTask["RESPONSIBLE_ID"]?>,
		directorId : <?php echo $arTask["CREATED_BY"]?>,
		responsible_name: '<?php echo CUtil::JSEscape($arTask["RESPONSIBLE_NAME"]); ?>',
		responsible_second_name: '<?php echo CUtil::JSEscape($arTask["RESPONSIBLE_SECOND_NAME"]); ?>',
		responsible_last_name: '<?php echo CUtil::JSEscape($arTask["RESPONSIBLE_LAST_NAME"]); ?>',
		responsible_login: '<?php echo CUtil::JSEscape($arTask["RESPONSIBLE_LOGIN"]); ?>',
		director_name: '<?php echo CUtil::JSEscape($arTask["CREATED_BY_NAME"]); ?>',
		director_second_name: '<?php echo CUtil::JSEscape($arTask["CREATED_BY_SECOND_NAME"]); ?>',
		director_last_name: '<?php echo CUtil::JSEscape($arTask["CREATED_BY_LAST_NAME"]); ?>',
		director_login: '<?php echo CUtil::JSEscape($arTask["CREATED_BY_LOGIN"]); ?>',
		dateCreated : <?php tasksJSDateObject($arTask["CREATED_DATE"], $top)?>,

		<?php if ($arTask["START_DATE_PLAN"]):?>dateStart : <?php tasksJSDateObject($arTask["START_DATE_PLAN"], $top)?>,<?php else:?>dateStart: null,<?php endif?>

		<?php if ($arTask["END_DATE_PLAN"]):?>dateEnd : <?php tasksJSDateObject($arTask["END_DATE_PLAN"], $top)?>,<?php else:?>dateEnd: null,<?php endif?>

		<?php if ($arTask["DATE_START"]):?>dateStarted: <?php tasksJSDateObject($arTask["DATE_START"], $top)?>,<?php endif?>

		dateCompleted : <?php if ($arTask["CLOSED_DATE"]):?><?php tasksJSDateObject($arTask["CLOSED_DATE"], $top)?><?php else:?>null<?php endif?>,

		<?php if ($arTask["DEADLINE"]):?>dateDeadline : <?php tasksJSDateObject($arTask["DEADLINE"], $top)?>,<?php else:?>dateDeadline: null,<?php endif?>

		canEditPlanDates : <?php if ($arAllowedTaskActions['ACTION_EDIT']):?>true<?php else:?>false<?php endif?>,

		<?php if ($arTask["PARENT_ID"] && $bParent):?>
			parentTaskId : <?php echo $arTask["PARENT_ID"]?>,
		<?php endif?>

		<?php
			if (sizeof($arTask["FILES"])):
				$i = 0;
		?>
			files: [
				<?php
					foreach($arTask["FILES"] as $file):
						$i++;
				?>
				{ name : '<?php echo CUtil::JSEscape($file["ORIGINAL_NAME"])?>', url : '/bitrix/components/bitrix/tasks.task.detail/show_file.php?fid=<?php echo $file["ID"]?>', size : '<?php echo CUtil::JSEscape(CFile::FormatSize($file["FILE_SIZE"]))?>' }<?php if ($i != sizeof($arTask["FILES"])):?>,<?php endif?>
				<?php endforeach?>
			],
		<?php endif?>

		<?php
		if (count($arTask['ACCOMPLICES']) > 0)
		{
			$i = 0;
			echo 'accomplices: [';
			foreach($arTask['ACCOMPLICES'] as $ACCOMPLICE_ID)
			{
				if ($i++)
					echo ',';

				echo '{ id: ' . (int) $ACCOMPLICE_ID . ' }';
			}
			echo '], ';
		}
		?>

		<?php
		if (count($arTask['AUDITORS']) > 0)
		{
			$i = 0;
			echo 'auditors: [';
			foreach($arTask['AUDITORS'] as $AUDITOR_ID)
			{
				if ($i++)
					echo ',';

				echo '{ id: ' . (int) $AUDITOR_ID . ' }';
			}
			echo '], ';
		}
		?>

		isSubordinate: <?php echo $arTask["SUBORDINATE"] == "Y" ? "true" : "false"?>,
		isInReport: <?php echo $arTask["ADD_IN_REPORT"] == "Y" ? "true" : "false"?>,
		hasChildren : <?php
			if (((int) $childrenCount) > 0)
				echo 'true';
			else
				echo 'false';
		?>,
		childrenCount : <?php echo (int) $childrenCount; ?>,
		canEditDealine : <?php 
			if ($arAllowedTaskActions['ACTION_EDIT'] || $arAllowedTaskActions['ACTION_CHANGE_DEADLINE'])
				echo 'true';
			else
				echo 'false';
		?>,
		canStartTimeTracking : <?php if ($arAllowedTaskActions['ACTION_START_TIME_TRACKING']):?>true<?php else:?>false<?php endif?>,
		ALLOW_TIME_TRACKING : <?php 
			if (isset($arTask['ALLOW_TIME_TRACKING']) && ($arTask['ALLOW_TIME_TRACKING'] === 'Y'))
				echo 'true';
			else
				echo 'false';
		?>,
		TIMER_RUN_TIME : <?php if ($runningTaskId == $arTask['ID']) echo (int) $runningTaskTimer; else echo 'false'; ?>,
		TIME_SPENT_IN_LOGS : <?php echo (int) $arTask['TIME_SPENT_IN_LOGS']; ?>,
		TIME_ESTIMATE : <?php echo (int) $arTask['TIME_ESTIMATE']; ?>,
		IS_TASK_TRACKING_NOW : <?php if ($runningTaskId == $arTask['ID']) echo 'true'; else echo 'false'; ?>,
		menuItems: [<?php tasksGetItemMenu($arTask, $arPaths, SITE_ID, $bGant, $top, $bSkipJsMenu)?>]

		<?php
		foreach ($arAdditionalFields as $key => $value)
			echo ', ' . $key . ' : ' . $value . "\n";
		?>
	}
<?php
}


function tasksJSDateObject($date, $top = false)
{
	$ts = MakeTimeStamp($date);
	?>
	new <?php if ($top):?>top.<?php endif?>Date(<?php 
		echo date("Y", $ts); ?>, <?php
		echo date("n", $ts) - 1; ?>, <?php 
		echo date("j", $ts); ?>, <?php 
		echo date("G", $ts); ?>, <?php 
		echo (date("i", $ts) + 0); ?>, <?php 
		echo (date("s", $ts) + 0); ?>)
	<?php
}


function tasksStatus2String($status)
{
	$arMap = array(
		CTasks::METASTATE_EXPIRED          => 'overdue',
		CTasks::METASTATE_VIRGIN_NEW       => 'new',
		CTasks::STATE_NEW                  => 'new',
		CTasks::STATE_PENDING              => 'accepted',
		CTasks::STATE_IN_PROGRESS          => 'in-progress',
		CTasks::STATE_SUPPOSEDLY_COMPLETED => 'waiting',
		CTasks::STATE_COMPLETED            => 'completed',
		CTasks::STATE_DEFERRED             => 'delayed',
		CTasks::STATE_DECLINED             => 'declined'
	);

	$strStatus = "";
	if (isset($arMap[$status]))
		$strStatus = $arMap[$status];

	return $strStatus;
}


function tasksServerName($server_name = false)
{
	if (!$server_name)
	{
		if (defined("SITE_SERVER_NAME") && SITE_SERVER_NAME)
		{
			$server_name = SITE_SERVER_NAME;
		}
		else
		{
			$server_name = COption::GetOptionString("main", "server_name", $_SERVER['HTTP_HOST']);
		}
	}

	if (
		(substr(strtolower($server_name), 0, 8) !== 'https://')
		&& (substr(strtolower($server_name), 0, 7) !== 'http://')
	)
	{
		if (CMain::IsHTTPS())
			$server_name = 'https://' . $server_name;
		else
			$server_name = 'http://' . $server_name;
	}

	$server_name_wo_protocol = str_replace(
		array('http://', 'https://', 'HTTP://', 'HTTPS://'), 	// Yeah, I know: 'hTtpS://', ...
		array('', '', '', ''), 
		$server_name
	);

	// Cutoff all what is after '/' (include '/' itself)
	$slashPos = strpos($server_name_wo_protocol, '/');
	if ($slashPos >= 1)
	{
		$length = $slashPos;
		$server_name_wo_protocol = substr(0, $length);
	}

	$isServerPortAlreadyGiven = false;
	if (strpos($server_name_wo_protocol, ':') !== false)
		$isServerPortAlreadyGiven = true;

	$server_port = '';

	if (
		( ! $isServerPortAlreadyGiven )
		&& (strlen($_SERVER['SERVER_PORT']) > 0)
		&& ($_SERVER['SERVER_PORT'] != '80')
		&& ($_SERVER['SERVER_PORT'] != '443')
	)
	{
		$server_port = ':' . $_SERVER['SERVER_PORT'];
	}

	if ( ! $isServerPortAlreadyGiven )
		$server_name .= $server_port;

	return ($server_name);
}


function tasksGetLastSelected($arManagers, $bSubordinateOnly = false, $nameTemplate = "")
{
	static $arLastUsers;
	global $USER;

	if (!isset($arLastUsers))
	{
		$arSubDeps = CTasks::GetSubordinateDeps();

		if (!class_exists('CUserOptions'))
			include_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/main/classes/".$GLOBALS['DBType']."/favorites.php");

		$arLastSelected = CUserOptions::GetOption("tasks", "user_search", array());
		if (is_array($arLastSelected) && strlen($arLastSelected['last_selected']) > 0)
			$arLastSelected = array_unique(explode(',', $arLastSelected['last_selected']));
		else
			$arLastSelected = false;

		if (is_array($arLastSelected))
		{
			$currentUser = array_search($USER->GetID(), $arLastSelected);
			if ($currentUser !== false)
			{
				unset($arLastSelected[$currentUser]);
			}
			array_unshift($arLastSelected, $USER->GetID());
		}
		else
		{
			$arLastSelected[] = $USER->GetID();
		}

		$arFilter = array('ACTIVE' => 'Y');
		if ($bSubordinateOnly)
		{
			$arFilter["UF_DEPARTMENT"] = $arSubDeps;
		}
		else
		{
			$arFilter['!UF_DEPARTMENT'] = false;
		}
		$arFilter['ID'] = is_array($arLastSelected) ? implode('|', array_slice($arLastSelected, 0, 10)) : '-1';
		$dbRes = CUser::GetList($by = 'last_name', $order = 'asc', $arFilter, array('SELECT' => array('UF_DEPARTMENT')));
		$arLastUsers = array();
		while ($arRes = $dbRes->GetNext())
		{
			$arPhoto = array('IMG' => '');

			if (!$arRes['PERSONAL_PHOTO'])
			{
				switch ($arRes['PERSONAL_GENDER'])
				{
					case "M":
						$suffix = "male";
						break;
					case "F":
						$suffix = "female";
						break;
					default:
						$suffix = "unknown";
				}
				$arRes['PERSONAL_PHOTO'] = COption::GetOptionInt("socialnetwork", "default_user_picture_".$suffix, false, isset($arParams['SITE_ID']) ? $arParams['SITE_ID'] : SITE_ID);
			}

			if ($arRes['PERSONAL_PHOTO'] > 0)
				$arPhoto = CIntranetUtils::InitImage($arRes['PERSONAL_PHOTO'], 30, 0, BX_RESIZE_IMAGE_EXACT);

			$arLastUsers[$arRes['ID']] = array(
				'ID' => $arRes['ID'],
				'NAME' => CUser::FormatName(empty($nameTemplate) ? CSite::GetNameFormat() : $nameTemplate, $arRes, true, false),
				'LOGIN' => $arRes['LOGIN'],
				'EMAIL' => $arRes['EMAIL'],
				'WORK_POSITION' => htmlspecialcharsBack($arRes['WORK_POSITION'] ? $arRes['WORK_POSITION'] : $arRes['PERSONAL_PROFESSION']),
				'PHOTO' => isset($arPhoto['CACHE']['src']) ? $arPhoto['CACHE']['src'] : "",
				'HEAD' => false,
				'SUBORDINATE' => is_array($arSubDeps) && is_array($arRes['UF_DEPARTMENT']) && array_intersect($arRes['UF_DEPARTMENT'], $arSubDeps) ? 'Y' : 'N',
				'SUPERORDINATE' => in_array($arRes["ID"], $arManagers) ? 'Y' : 'N'
			);
		}
	}

	return $arLastUsers;
}


define("TASKS_FILTER_SESSION_INDEX", "FILTER");


function tasksGetFilter($fieldName)
{
	if (isset($_GET[$fieldName]))
	{
		$_SESSION[TASKS_FILTER_SESSION_INDEX][$fieldName] = $_GET[$fieldName];
	}

	return $_SESSION[TASKS_FILTER_SESSION_INDEX][$fieldName];
}


function tasksPredefinedFilters($userID, $roleFilterSuffix = "")
{
	return array(
		"ROLE" => array(
			array("TITLE" => GetMessage("TASKS_FILTER_MY".$roleFilterSuffix), "FILTER" => array("DOER" => $userID), "CLASS" => "inbox", "COUNT" => "-", "STATUS_FILTER" => 0),
			array("TITLE" => GetMessage("TASKS_FILTER_RESPONSIBLE".$roleFilterSuffix), "FILTER" => array("RESPONSIBLE_ID" => $userID), "CLASS" => "my-responsibility", "COUNT" => "-", "STATUS_FILTER" => 0),
			array("TITLE" => GetMessage("TASKS_FILTER_ACCOMPLICE".$roleFilterSuffix), "FILTER" => array("ACCOMPLICE" => $userID), "CLASS" => "my-complicity", "COUNT" => "-", "STATUS_FILTER" => 0),
			array("TITLE" => GetMessage("TASKS_FILTER_IN_REPORT".$roleFilterSuffix), "FILTER" => array("RESPONSIBLE_ID" => $userID, "ADD_IN_REPORT" => "Y"), "CLASS" => "my-report", "COUNT" => "-", "STATUS_FILTER" => 0),
			array("TITLE" => GetMessage("TASKS_FILTER_CREATOR".$roleFilterSuffix), "FILTER" => array("CREATED_BY" => $userID), "CLASS" => "outbox", "COUNT" => "-", "STATUS_FILTER" => 1),
			array("TITLE" => GetMessage("TASKS_FILTER_FOR_REPORT".$roleFilterSuffix), "FILTER" => array("CREATED_BY" => $userID, "ADD_IN_REPORT" => "Y"), "CLASS" => "my-report", "COUNT" => "-", "STATUS_FILTER" => 1),
			array("TITLE" => GetMessage("TASKS_FILTER_AUDITOR".$roleFilterSuffix), "FILTER" => array("AUDITOR" => $userID), "CLASS" => "under-control", "COUNT" => "-", "STATUS_FILTER" => 0),
			array("TITLE" => GetMessage("TASKS_FILTER_ALL"), "FILTER" => array("MEMBER" => $userID), "CLASS" => "anybox", "COUNT" => "-", "STATUS_FILTER" => 0)
		),
		"STATUS" => array(
			array(
				array("TITLE" => GetMessage("TASKS_FILTER_ACTIVE"), "FILTER" => array("STATUS" => array(-2, -1, 1, 2, 3)), "CLASS" => "open", "COUNT" => "-"),
				array("TITLE" => GetMessage("TASKS_FILTER_NEW"), "FILTER" => array("STATUS" => array(-2, 1)), "CLASS" => "new", "COUNT" => "-"),
				array("TITLE" => GetMessage("TASKS_FILTER_IN_PROGRESS"), "FILTER" => array("STATUS" => 3), "CLASS" => "in-progress", "COUNT" => "-"),
				array("TITLE" => GetMessage("TASKS_FILTER_ACCEPTED"), "FILTER" => array("STATUS" => 2), "CLASS" => "accepted", "COUNT" => "-"),
				array("TITLE" => GetMessage("TASKS_FILTER_OVERDUE"), "FILTER" => array("STATUS" => -1), "CLASS" => "overdue", "COUNT" => "-"),
				array("TITLE" => GetMessage("TASKS_FILTER_DELAYED"), "FILTER" => array("STATUS" => 6), "CLASS" => "delayed", "COUNT" => "-"),
				array("TITLE" => GetMessage("TASKS_FILTER_CLOSED"), "FILTER" => array("STATUS" => array(4, 5)), "CLASS" => "completed", "COUNT" => "-"),
				array("TITLE" => GetMessage("TASKS_FILTER_ALL"), "FILTER" => array(), "CLASS" => "any", "COUNT" => "-")
			),
			array(
				array("TITLE" => GetMessage("TASKS_FILTER_ACTIVE"), "FILTER" => array("STATUS" => array(-1, 1, 2, 3, 4, 7)), "CLASS" => "open", "COUNT" => "-"),
				array("TITLE" => GetMessage("TASKS_FILTER_NOT_ACCEPTED"), "FILTER" => array("STATUS" => 1), "CLASS" => "new", "COUNT" => "-"),
				array("TITLE" => GetMessage("TASKS_FILTER_IN_CONTROL"), "FILTER" => array("STATUS" => array(4, 7)), "CLASS" => "waiting", "COUNT" => "-"),
				array("TITLE" => GetMessage("TASKS_FILTER_IN_PROGRESS"), "FILTER" => array("STATUS" => 3), "CLASS" => "in-progress", "COUNT" => "-"),
				array("TITLE" => GetMessage("TASKS_FILTER_ACCEPTED"), "FILTER" => array("STATUS" => 2), "CLASS" => "accepted", "COUNT" => "-"),
				array("TITLE" => GetMessage("TASKS_FILTER_OVERDUE"), "FILTER" => array("STATUS" => -1), "CLASS" => "overdue", "COUNT" => "-"),
				array("TITLE" => GetMessage("TASKS_FILTER_DELAYED"), "FILTER" => array("STATUS" => 6), "CLASS" => "delayed", "COUNT" => "-"),
				array("TITLE" => GetMessage("TASKS_FILTER_CLOSED"), "FILTER" => array("STATUS" => array(4, 5)), "CLASS" => "completed", "COUNT" => "-"),
				array("TITLE" => GetMessage("TASKS_FILTER_ALL"), "FILTER" => array(), "CLASS" => "any", "COUNT" => "-")
			)
		)
	);
}


function ShowInFrame(&$component, $bShowError = false, $errText = '')
{
	global $APPLICATION;

	$APPLICATION->RestartBuffer();
	?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?=LANGUAGE_ID?>" lang="<?=LANGUAGE_ID?>">
		<head><?php
			$APPLICATION->ShowHead();
			$APPLICATION->AddHeadString('
				<style>
				body {background: #fff !important; text-align: left !important; color: #000 !important;}
				div.bx-core-dialog-overlay {opacity: 0 !important; -moz-opacity: 0 !important; -khtml-opacity: 0 !important; filter:progid:DXImageTransform.Microsoft.Alpha(opacity=0) !important;}
				div#tasks-content-outer {padding: 15px;}
				.task-comment-content{ font-family:Verdana, sans-serif; padding-top:6px; word-wrap: break-word; width: 620px; overflow: hidden;}
				.task-detail-description { font-size:13px; color:#222; padding: 0 0 5px; word-wrap: break-word; width: 585px; overflow: hidden;}
				</style>
			', false, true);
		?></head>
		<body class="<?$APPLICATION->ShowProperty("BodyClass");?>" onload="if (window.top.BX.TasksIFrameInst) window.top.BX.TasksIFrameInst.onTaskLoaded();">
			<div id="tasks-content-outer">
				<table cellpadding="0" cellspading="0" width="100%">
					<tr>
						<td valign="top"><?php
							if ($bShowError)
							{
								?><div id="task-reminder-link"><?php
									ShowError($errText);
								?></div><?php
							}
							else
								$component->IncludeComponentTemplate();
						?></td>
						<?php if (strlen($APPLICATION->GetViewContent("sidebar_tools_1"))):?>
							<td width="10"></td>
							<td valign="top" width="230"><?php $APPLICATION->ShowViewContent("sidebar_tools_1")?></td>
						<?php endif?>
					</tr>
				</table>
			</div>
		</body>
	</html><?
	require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');
	die();
}


function __checkForum($forumID)
{
	if (!($settingsForumID = COption::GetOptionString("tasks", "task_forum_id")))
	{
		if ( (int) $forumID > 0 )
			COption::SetOptionString("tasks", "task_forum_id", intval($forumID));
	}

	if (IsModuleInstalled('extranet'))
	{
		if (-1 === COption::GetOptionString('tasks', 'task_extranet_forum_id', -1, $siteId = ''))
		{
			try
			{
				$extranetForumID = CTasksTools::GetForumIdForExtranet();
				COption::SetOptionString('tasks', 'task_extranet_forum_id', $extranetForumID, '', $siteId = '');
			}
			catch (TasksException $e)
			{
				COption::SetOptionString('tasks', 'task_extranet_forum_id', (int) $forumID, '', $siteId = '');
			}
		}
	}

	if (CModule::IncludeModule("forum") && $forumID && COption::GetOptionString("tasks", "forum_checked", false))
	{
		$arGroups = array();
		$rs = CGroup::GetList($order = 'id', $by = 'asc', array());
		while($ar = $rs->Fetch())
			$arGroups[$ar['ID']] = 'A';

		CForumNew::Update($forumID, array("GROUP_ID"=>$arGroups, "INDEXATION" => "Y"));
		COption::RemoveOption("tasks", "forum_checked");
	}
}


/**
 * This function is deprecated. See CTaskFiles::removeTemporaryFile()
 * 
 * @deprecated
 */
function deleteUploadedFiles($arFileIDs)
{
	$arFileIDs = (array) $arFileIDs;
	foreach($arFileIDs as $fileID)
	{
		$key = array_search(intval($fileID), $_SESSION["TASKS_UPLOADED_FILES"]);
		if ($key !== false)
		{
			unset($_SESSION["TASKS_UPLOADED_FILES"][$key]);
		}
	}
}


/**
 * This function is deprecated. See CTaskFiles::saveFileTemporary()
 * 
 * @deprecated
 */
function addUploadedFiles($arFileIDs)
{
	$arFileIDs = (array) $arFileIDs;
	if (!is_array($_SESSION["TASKS_UPLOADED_FILES"]))
		$_SESSION["TASKS_UPLOADED_FILES"] = array();
	$_SESSION["TASKS_UPLOADED_FILES"] = array_merge($_SESSION["TASKS_UPLOADED_FILES"], $arFileIDs);
}


/**
 * This function is deprecated.
 * 
 * @deprecated
 */
function cleanupUploadedFiles()
{
	if (isset($_SESSION["TASKS_UPLOADED_FILES"]) && is_array($_SESSION["TASKS_UPLOADED_FILES"]))
	{
		foreach($_SESSION["TASKS_UPLOADED_FILES"] as $fileID)
		{
			CFile::Delete($fileID);
		}
		$_SESSION["TASKS_UPLOADED_FILES"] = array();
	}
}


if ( ! function_exists('tasksFormatFileSize') )
{
	function tasksFormatFileSize($in)
	{
		return(CFile::FormatSize($in));
	}
}
