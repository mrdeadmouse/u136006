<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 */


if (!CModule::IncludeModule('report'))
	return;

class CTasksReportHelper extends CReportHelper
{
	static $PATH_TO_USER = '/company/personal/user/#user_id#/';

	private static $nRows = 0;


	public static function setPathToUser($path)
	{
		self::$PATH_TO_USER = $path;
	}


	public static function getEntityName()
	{
		return 'Bitrix\Tasks\Task';
	}


	public static function getOwnerId()
	{
		return 'TASKS';
	}


	public static function getColumnList()
	{
		IncludeModuleLangFile(__FILE__);

		return array(
			'ID',
			'TITLE',
			'DESCRIPTION_TR',
			'PRIORITY',
			'STATUS',
			'STATUS_PSEUDO',
			'STATUS_SUB' => array(
				'IS_NEW',
				'IS_OPEN',
				'IS_RUNNING',
				'IS_FINISHED',
				'IS_OVERDUE',
				'IS_MARKED',
				'IS_EFFECTIVE_PRCNT'
			),
			'ADD_IN_REPORT',
			'CREATED_DATE',
			'START_DATE_PLAN',
			'END_DATE_PLAN',
			'DURATION_PLAN_HOURS',
			'DATE_START',
			'CHANGED_DATE',
			'CLOSED_DATE',
			'DEADLINE',
			'DURATION',
			'DURATION_FOR_PERIOD',
			'ALLOW_TIME_TRACKING',
			'TIME_ESTIMATE',
			'MARK',
			'Tag:TASK.NAME',
			'GROUP' => array(
				'ID',
				'NAME'
			),
			'CREATED_BY_USER' => array(
				'ID',
				'SHORT_NAME',
				'NAME',
				'LAST_NAME',
				'WORK_POSITION'
			),
			'RESPONSIBLE' => array(
				'ID',
				'SHORT_NAME',
				'NAME',
				'LAST_NAME',
				'WORK_POSITION'
			),
			'Member:TASK_COWORKED.USER' => array(
				'ID',
				'SHORT_NAME',
				'NAME',
				'LAST_NAME',
				'WORK_POSITION'
			),
			'CHANGED_BY_USER' => array(
				'ID',
				'SHORT_NAME',
				'NAME',
				'LAST_NAME',
				'WORK_POSITION'
			),
			'STATUS_CHANGED_BY_USER' => array(
				'ID',
				'SHORT_NAME',
				'NAME',
				'LAST_NAME',
				'WORK_POSITION'
			),
			'CLOSED_BY_USER' => array(
				'ID',
				'SHORT_NAME',
				'NAME',
				'LAST_NAME',
				'WORK_POSITION'
			)
		);
	}

	public static function setRuntimeFields(\Bitrix\Main\Entity\Base $entity, $sqlTimeInterval)
	{
		$entity->addField(array(
			'data_type' => 'integer',
			'expression' => array(
				'ROUND((SELECT  SUM(CASE WHEN CREATED_DATE '.$sqlTimeInterval.' THEN SECONDS ELSE 0 END)/60 FROM b_tasks_elapsed_time WHERE TASK_ID = %s),0)',
				'ID'
			)
		), 'DURATION_FOR_PERIOD');

		$entity->addField(array(
			'data_type' => 'integer',
			'expression' => array(
				'(SELECT  SUM(CASE WHEN CREATED_DATE '.$sqlTimeInterval.' THEN SECONDS ELSE 0 END) '.
				'FROM b_tasks_elapsed_time WHERE TASK_ID = %s)',
				'ID'
			)
		), 'TIME_SPENT_IN_LOGS_FOR_PERIOD');

		$entity->addField(array(
			'data_type' => 'boolean',
			'expression' => array(
				'CASE WHEN %s '.$sqlTimeInterval.' THEN 1 ELSE 0 END',
				'CREATED_DATE'
			),
			'values' => array(0, 1)
		), 'IS_NEW');

		$entity->addField(array(
			'data_type' => 'boolean',
			'expression' => array(
				'CASE WHEN %s '.$sqlTimeInterval.' THEN 0 ELSE 1 END',
				'DATE_START'
			),
			'values' => array(0, 1)
		), 'IS_OPEN');

		$entity->addField(array(
			'data_type' => 'boolean',
			'expression' => array(
				'CASE WHEN %s '.$sqlTimeInterval.' AND %s IS NOT NULL THEN 1 ELSE 0 END',
				'CLOSED_DATE', 'CLOSED_DATE'
			),
			'values' => array(0, 1)
		), 'IS_FINISHED');
	}

	public static function getCustomColumnTypes()
	{
		return array(
			'DURATION_PLAN_HOURS' => 'float',
			'DURATION' => 'float',
			'DURATION_FOR_PERIOD' => 'float',
			'TIME_ESTIMATE' => 'float'
		);
	}


	public static function getDefaultColumns()
	{
		return array(
			array('name' => 'TITLE'),
			array('name' => 'PRIORITY'),
			array('name' => 'RESPONSIBLE.SHORT_NAME'),
			array('name' => 'STATUS_PSEUDO')
		);
	}


	public static function getCalcVariations()
	{
		return array_merge(parent::getCalcVariations(), array(
			'IS_OVERDUE_PRCNT' => array(),
			'IS_MARKED_PRCNT' => array(),
			'IS_EFFECTIVE_PRCNT' => array(),
			'Tag:TASK.NAME' => array(
				'COUNT_DISTINCT',
				'GROUP_CONCAT'
			),
			'Member:TASK_COWORKED.USER.SHORT_NAME' => array(
				'COUNT_DISTINCT',
				'GROUP_CONCAT'
			)
		));
	}


	public static function getCompareVariations()
	{
		return array_merge(parent::getCompareVariations(), array(
			'STATUS' => array(
				'EQUAL',
				'NOT_EQUAL'
			),
			'STATUS_PSEUDO' => array(
				'EQUAL',
				'NOT_EQUAL'
			),
			'PRIORITY' => array(
				'EQUAL',
				'NOT_EQUAL'
			),
			'MARK' => array(
				'EQUAL',
				'NOT_EQUAL'
			)
		));
	}


	public static function buildHTMLSelectTreePopup($tree, $withReferencesChoose = false, $level = 0)
	{
		//return parent::buildHTMLSelectTreePopup($tree, $withReferencesChoose, $level);

		/* remove it when PHP 5.3 available */
		$indent = str_repeat('&nbsp;', ($level+1)*2);

		$html = '';

		$i = 0;

		foreach($tree as $treeElem)
		{
			$isLastElem = (++$i == count($tree));

			//list($fieldDefinition, $field, $branch) = $treeElem;
			$fieldDefinition = $treeElem['fieldName'];
			$branch = $treeElem['branch'];

			/** @var Bitrix\Main\Entity\Field[] $treeElem */
			$fieldType = $treeElem['field'] ? self::getFieldDataType($treeElem['field']) : null;

			if (empty($branch))
			{
				// single field
				$htmlElem = self::buildSelectTreePopupElelemnt($treeElem['humanTitle'], $treeElem['fullHumanTitle'], $fieldDefinition, $fieldType);

				if ($isLastElem && $level > 0)
				{
					$htmlElem = str_replace(
						'<div class="reports-add-popup-item">',
						'<div class="reports-add-popup-item reports-add-popup-item-last">',
						$htmlElem
					);

				}

				$html .= $htmlElem;
			}
			else
			{
				// add branch

				$scalarTypes = array('integer', 'string', 'boolean', 'datetime', 'enum');
				if ($withReferencesChoose &&
					(in_array($fieldType, $scalarTypes) || empty($fieldType))
				)
				{
					// ignore virtual branches (without references)
					continue;
				}

				$html .= sprintf('<div class="reports-add-popup-item reports-add-popup-it-node">
					<span class="reports-add-popup-arrow"></span><span
						class="reports-add-popup-it-text">%s</span>
				</div>', $treeElem['humanTitle']);

				$html .= '<div class="reports-add-popup-it-children">';

				// add self
				if ($withReferencesChoose)
				{
					// replace by static:: when php 5.3 available
					$html .= self::buildSelectTreePopupElelemnt(GetMessage('REPORT_CHOOSE').'...', $treeElem['humanTitle'], $fieldDefinition, $fieldType);
				}

				// replace by static:: when php 5.3 available
				$html .= self::buildHTMLSelectTreePopup($branch, $withReferencesChoose, $level+1);

				$html .= '</div>';
			}
		}

		return $html;
		/* \remove it */
	}


	/* remove it when PHP 5.3 available */
	public static function buildSelectTreePopupElelemnt($humanTitle, $fullHumanTitle, $fieldDefinition, $fieldType)
	{
		// replace by static:: when php 5.3 available
		$grcFields = self::getGrcColumns();

		$htmlCheckbox = sprintf(
			'<input type="checkbox" name="%s" title="%s" fieldType="%s" isGrc="%s" class="reports-add-popup-checkbox" />',
			htmlspecialcharsbx($fieldDefinition), htmlspecialcharsbx($fullHumanTitle), htmlspecialcharsbx($fieldType),
			(int) in_array($fieldDefinition, $grcFields)
		);

		$htmlElem = sprintf('<div class="reports-add-popup-item">
			<span class="reports-add-pop-left-bord"></span><span
			class="reports-add-popup-checkbox-block">
				%s
			</span><span class="reports-add-popup-it-text">%s</span>
		</div>', $htmlCheckbox, $humanTitle);

		return $htmlElem;
	}
	/* \remove it */


	public static function beforeViewDataQuery(&$select, &$filter, &$group, &$order, &$limit, &$options, &$runtime)
	{
		parent::beforeViewDataQuery($select, $filter, $group, $order, $limit, $options, $runtime);

		global $USER, $DB, $DBType;

		$permFilter = array(
			'LOGIC' => 'OR'
		);

		// owner permission
		if (isset($_GET['select_my_tasks']) ||
			(!isset($_GET['select_my_tasks']) && !isset($_GET['select_depts_tasks']) && !isset($_GET['select_group_tasks']))
		)
		{
			$runtime['IS_TASK_COWORKER'] = array(
				'data_type' => 'integer',
				'expression' => array("(CASE WHEN EXISTS("
					."SELECT 'x' FROM b_tasks_member TM "
					."WHERE TM.TASK_ID = ".$DB->escL.((ToUpper($DBType) === "ORACLE") ? "TASKS_TASK" : "tasks_task").$DB->escR.".ID AND TM.USER_ID = ".$USER->GetID()." AND TM.TYPE = 'A'"
				.") THEN 1 ELSE 0 END)")
			);

			$permFilter[] = array(
				'LOGIC' => 'OR',
				'=RESPONSIBLE_ID' => $USER->GetID(),
				'=IS_TASK_COWORKER' => 1
			);
		}

		// own departments permission
		if (isset($_GET['select_depts_tasks']))
		{
			$permFilterDepts = array(
				'LOGIC' => 'OR',
				'=CREATED_BY' => $USER->GetID()
			);

			$deptsPermSql = CTasks::GetSubordinateSql('__ULTRAUNIQUEPREFIX__');

			if (strlen($deptsPermSql))
			{
				$deptsPermSql = "EXISTS(".$deptsPermSql.")";
				$deptsPermSql = str_replace('__ULTRAUNIQUEPREFIX__T.', $DB->escL.((ToUpper($DBType) === "ORACLE") ? "TASKS_TASK" : "tasks_task").$DB->escR.'.', $deptsPermSql);
				$deptsPermSql = str_replace('__ULTRAUNIQUEPREFIX__', '', $deptsPermSql);

				$runtime['IS_SUBORDINATED_TASK'] = array(
					'data_type' => 'integer',
					'expression' => array("(CASE WHEN ".$deptsPermSql." THEN 1 ELSE 0 END)")
				);

				$permFilterDepts[] = array(
					'!RESPONSIBLE_ID' => $USER->GetID(),
					'=IS_SUBORDINATED_TASK' => 1
				);
			}

			$permFilter[] = $permFilterDepts;
		}

		// group permission
		if (isset($_GET['select_group_tasks']))
		{
			$allowedGroups = CTasks::GetAllowedGroups();
			$permFilter[] = array('=GROUP_ID' => $allowedGroups);
		}

		// re-aggregate aggregated subquery in DURATION for mssql
		if (\Bitrix\Main\Application::getConnection() instanceof \Bitrix\Main\DB\MssqlConnection)
		{
			foreach ($select as $k => $v)
			{
				if (substr($k, -9) == '_DURATION')
				{
					// we have aggregated duration
					$subQuery = new \Bitrix\Main\Entity\Query(\Bitrix\Tasks\ElapsedTimeTable::getEntity());
					$subQuery->addSelect('TASK_ID');
					$subQuery->addSelect(new \Bitrix\Main\Entity\ExpressionField(
						'DURATION', 'ROUND(SUM(%s)/60, 0)', 'SECONDS'
					));

					$subEntity = \Bitrix\Main\Entity\Base::getInstanceByQuery($subQuery);

					// make reference
					$subReferenceName = $k.'_REF';
					$runtime[$subReferenceName] = array(
						'data_type' => $subEntity,
						'reference' => array('=this.ID' => 'ref.TASK_ID')
					);

					// rewrite aggregated duration (put it in the end, after refence)
					$runtimeField = $runtime[$k];
					unset($runtime[$k]);

					$runtimeField['expression'][1] = $subReferenceName.'.DURATION';
					$runtime[$k] = $runtimeField;
				}
				else if (substr($k, -20) == '_DURATION_FOR_PERIOD' && isset($options['SQL_TIME_INTERVAL']))
				{
					// we have aggregated DURATION_FOR_PERIOD field
					$subQuery = new \Bitrix\Main\Entity\Query(\Bitrix\Tasks\ElapsedTimeTable::getEntity());
					$subQuery->addSelect('TASK_ID');
					$subQuery->addSelect(new \Bitrix\Main\Entity\ExpressionField(
						'DURATION_FOR_PERIOD',
						'ROUND((SUM(CASE WHEN CREATED_DATE '.$options['SQL_TIME_INTERVAL'].' THEN %s ELSE 0 END)/60),0)',
						'SECONDS'
					));

					$subEntity = \Bitrix\Main\Entity\Base::getInstanceByQuery($subQuery);

					// make reference
					$subReferenceName = $k.'_REF';
					$runtime[$subReferenceName] = array(
						'data_type' => $subEntity,
						'reference' => array('=this.ID' => 'ref.TASK_ID')
					);

					// rewrite aggregated duration (put it in the end, after refence)
					$runtimeField = $runtime[$k];
					unset($runtime[$k]);

					$runtimeField['expression'][1] = $subReferenceName.'.DURATION_FOR_PERIOD';
					$runtime[$k] = $runtimeField;
				}
			}
		}

		// concat permissions with common filter
		$filter[] = $permFilter;
	}


	/* remove it when PHP 5.3 available */
	public static function formatResults(&$rows, &$columnInfo, $total, &$customChartData = null)
	{
		foreach ($rows as $rowNum => &$row)
		{
			foreach ($row as $k => &$v)
			{
				if (!array_key_exists($k, $columnInfo))
				{
					continue;
				}

				$cInfo = $columnInfo[$k];

				if (is_array($v))
				{
					foreach ($v as $subk => &$subv)
					{
						$customChartValue = is_null($customChartData) ? null : array();
						self::formatResultValue($k, $subv, $row, $cInfo, $total, $customChartValue);
						if (is_array($customChartValue)
							&& isset($customChartValue['exist']) && $customChartValue['exist'] = true)
						{
							if (!isset($customChartData[$rowNum]))
								$customChartData[$rowNum] = array();
							if (!isset($customChartData[$rowNum][$k]))
								$customChartData[$rowNum][$k] = array();
							$customChartData[$rowNum][$k]['multiple'] = true;
							if (!isset($customChartData[$rowNum][$k][$subk]))
								$customChartData[$rowNum][$k][$subk] = array();
							$customChartData[$rowNum][$k][$subk]['type'] = $customChartValue['type'];
							$customChartData[$rowNum][$k][$subk]['value'] = $customChartValue['value'];
						}
					}
				}
				else
				{
					$customChartValue = is_null($customChartData) ? null : array();
					self::formatResultValue($k, $v, $row, $cInfo, $total, $customChartValue);
					if (is_array($customChartValue)
						&& isset($customChartValue['exist']) && $customChartValue['exist'] = true)
					{
						if (!isset($customChartData[$rowNum]))
							$customChartData[$rowNum] = array();
						if (!isset($customChartData[$rowNum][$k]))
							$customChartData[$rowNum][$k] = array();
						$customChartData[$rowNum][$k]['multiple'] = false;
						if (!isset($customChartData[$rowNum][$k][0]))
							$customChartData[$rowNum][$k][0] = array();
						$customChartData[$rowNum][$k][0]['type'] = $customChartValue['type'];
						$customChartData[$rowNum][$k][0]['value'] = $customChartValue['value'];
					}
				}
			}
			self::$nRows++;
		}

		unset($row, $v, $subv);
	}
	/* \remove it */


	public static function formatResultValue($k, &$v, &$row, &$cInfo, $total, &$customChartValue = null)
	{
		$field = $cInfo['field'];
		$bChartValue = false;
		$chartValueType = null;
		$chartValue = null;

		if ($k == 'STATUS' || $k == 'STATUS_PSEUDO' || $k == 'PRIORITY')
		{
			if (empty($cInfo['aggr']) || $cInfo['aggr'] !== 'COUNT_DISTINCT')
			{
				$v = htmlspecialcharsbx(GetMessage($field->getLangCode().'_VALUE_'.$v));
			}
		}
		elseif (strpos($k, 'DURATION_PLAN_HOURS') !== false && !strlen($cInfo['prcnt']))
		{
			$bChartValue = true;
			$chartValueType = 'float';
			$chartValue = 0.0;
			if (!empty($v))
			{
				$days = floor($v/24);
				$hours = $v - $days*24;
				$v = '';

				if (!empty($days))
				{
					$chartValue += floatval($days * 24);
					$v .= $days.GetMessage('TASKS_REPORT_DURATION_DAYS');
				}

				if (!empty($hours))
				{
					$chartValue += floatval($hours);
					if (!empty($days)) $v .= ' ';
					$v .= $hours.GetMessage('TASKS_REPORT_DURATION_HOURS');
				}

				$chartValue = round($chartValue, 2);
			}
		}
		elseif (strpos($k, 'DURATION') !== false && !strlen($cInfo['prcnt']))
		{
			$hours = floor($v/60);
			$minutes = date('i', ($v % 60)*60);
			$v = $hours.':'.$minutes;

			$bChartValue = true;
			$chartValueType = 'float';
			$chartValue = round(floatval($hours) + (floatval($minutes)/60), 2);
		}
		elseif (strpos($k, 'TIME_ESTIMATE') !== false && !strlen($cInfo['prcnt']))
		{
			$hours = floor($v/3600);
			$minutes = date('i', $v % 3600);
			$v = $hours.':'.$minutes;

			$bChartValue = true;
			$chartValueType = 'float';
			$chartValue = round(floatval($hours) + (floatval($minutes)/60), 2);
		}
		elseif ($k == 'MARK' && empty($cInfo['aggr']))
		{
			$v = GetMessage($field->getLangCode().'_VALUE_'.$v);
			if (empty($v))
			{
				$v = GetMessage($field->getLangCode().'_VALUE_NONE');
			}
		}
		elseif ($k == 'DESCRIPTION_TR')
		{
			$v = htmlspecialcharsbx(str_replace("\x0D", ' ', str_replace("\x0A", ' ', PrepareTxtForEmail(strip_tags($v)))));
		}
		else
		{
			parent::formatResultValue($k, $v, $row, $cInfo, $total);
		}

		if ($bChartValue && is_array($customChartValue))
		{
			$customChartValue['exist'] = true;
			$customChartValue['type'] = $chartValueType;
			$customChartValue['value'] = $chartValue;
		}
	}


	public static function formatResultsTotal(&$total, &$columnInfo, &$customChartTotal = null)
	{
		parent::formatResultsTotal($total, $columnInfo);

		foreach ($total as $k => $v)
		{
			// remove prefix TOTAL_
			$original_k = substr($k, 6);

			$cInfo = $columnInfo[$original_k];

			if (strpos($k, 'DURATION_PLAN_HOURS') !== false && !strlen($cInfo['prcnt']))
			{
				if (!empty($v))
				{
					$days = floor($v/24);
					$hours = $v - $days*24;
					$v = '';
					if (!empty($days)) $v .= $days.GetMessage('TASKS_REPORT_DURATION_DAYS');
					if (!empty($hours))
					{
						if (!empty($days)) $v .= ' ';
						$v .= $hours.GetMessage('TASKS_REPORT_DURATION_HOURS');
					}
					$total[$k] = $v;
				}
			}
			elseif (strpos($k, 'DURATION') !== false && !strlen($cInfo['prcnt']))
			{
				$hours = floor($v/60);
				$minutes = date('i', ($v % 60)*60);
				$total[$k] = $hours.':'.$minutes;
			}
			elseif (strpos($k, 'TIME_ESTIMATE') !== false && !strlen($cInfo['prcnt']))
			{
				$hours = floor($v/3600);
				$minutes = date('i', $v % 3600);
				$total[$k] = $hours.':'.$minutes;
			}
			elseif ((strpos($k, 'IS_EFFECTIVE_PRCNT') !== false
					|| strpos($k, 'IS_OVERDUE_PRCNT') !== false
					|| strpos($k, 'IS_MARKED_PRCNT') !== false) && $cInfo['prcnt'] !== 'self_column')
			{
				if (self::$nRows > 0 && substr($v, 0, 2) !== '--')
					$total[$k] = round(doubleval($v) / self::$nRows, 2).'%';
			}
		}
	}


	public static function getPeriodFilter($date_from, $date_to)
	{
		$filter = array('LOGIC' => 'AND');

		if (!is_null($date_from) && !is_null($date_to))
		{
			$filter[] = array(
				'LOGIC' => 'OR',
				array(
					'LOGIC' => 'AND',
					'>=CREATED_DATE' => $date_from,
					'<=CREATED_DATE' => $date_to
				),
				array(
					'LOGIC' => 'AND',
					'>=CLOSED_DATE' => $date_from,
					'<=CLOSED_DATE' => $date_to
				),
				array(
					'LOGIC' => 'AND',
					'<CREATED_DATE' => $date_from,
					array(
						'LOGIC' => 'OR',
						'>CLOSED_DATE' => $date_to,
						'=CLOSED_DATE' => ''
					)
				)
			);
		}
		else if (!is_null($date_from))
		{
			$filter[] = array(
				'LOGIC' => 'OR',
				'>=CREATED_DATE' => $date_from,
				'>=CLOSED_DATE' => $date_from,
				'=CLOSED_DATE' => ''
			);
		}
		else if (!is_null($date_to))
		{
			$filter[] = array(
				'LOGIC' => 'OR',
				'<=CREATED_DATE' => $date_to,
				'<=CLOSED_DATE' => $date_to
			);
		}

		// hide deleted tasks
		$filter[] = array('=ZOMBIE' => 'N');

		return $filter;
	}


	public static function getDefaultElemHref($elem, $fList)
	{
		$href = null;

		if (empty($elem['aggr']) || $elem['aggr'] == 'GROUP_CONCAT')
		{
			$field = $fList[$elem['name']];
			$pathToUser = self::$PATH_TO_USER;
			$pathToUser = str_replace('#user_id#/', '', $pathToUser);

			if ($field->getEntity()->getName() == 'Task' && $elem['name'] == 'TITLE')
			{
				$href = array('pattern' => $pathToUser.'#RESPONSIBLE_ID#/tasks/task/view/#ID#/');
			}
			elseif ($field->getEntity()->getName() == 'User')
			{
				if ($elem['name'] == 'CREATED_BY_USER.SHORT_NAME')
				{
					$href = array('pattern' => $pathToUser.'#CREATED_BY#/');
				}
				elseif ($elem['name'] == 'RESPONSIBLE.SHORT_NAME')
				{
					$href = array('pattern' => $pathToUser.'#RESPONSIBLE_ID#/');
				}
				elseif ($elem['name'] == 'Member:TASK_COWORKED.USER.SHORT_NAME')
				{
					$href = array('pattern' => $pathToUser.'#Member:TASK_COWORKED.USER.ID#/');
				}
				elseif ($elem['name'] == 'CHANGED_BY_USER.SHORT_NAME')
				{
					$href = array('pattern' => $pathToUser.'#CHANGED_BY#/');
				}
				elseif ($elem['name'] == 'STATUS_CHANGED_BY_USER.SHORT_NAME')
				{
					$href = array('pattern' => $pathToUser.'#STATUS_CHANGED_BY#/');
				}
				elseif ($elem['name'] == 'CLOSED_BY_USER.SHORT_NAME')
				{
					$href = array('pattern' => $pathToUser.'#CLOSED_BY#/');
				}
			}
			elseif ($field->getEntity()->getName() == 'Group' && $elem['name'] == 'GROUP.NAME')
			{
				$href = array('pattern' => '/workgroups/group/#GROUP_ID#/');
			}
		}

		return $href;
	}


	public static function getDefaultReports()
	{
		IncludeModuleLangFile(__FILE__);

		$reports = array(
			'11.0.1' => array(
				array(
					'title' => GetMessage('TASKS_REPORT_DEFAULT_2'),
					'mark_default' => 2,
					'settings' => unserialize('a:6:{s:6:"entity";s:5:"Tasks";s:6:"period";a:2:{s:4:"type";s:5:"month";s:5:"value";N;}s:6:"select";a:4:{i:0;a:1:{s:4:"name";s:22:"RESPONSIBLE.SHORT_NAME";}i:1;a:1:{s:4:"name";s:10:"GROUP.NAME";}i:2;a:2:{s:4:"name";s:8:"DURATION";s:4:"aggr";s:3:"SUM";}i:3;a:2:{s:4:"name";s:10:"IS_RUNNING";s:4:"aggr";s:3:"SUM";}}s:6:"filter";a:1:{i:0;a:2:{i:0;a:5:{s:4:"type";s:5:"field";s:4:"name";s:5:"GROUP";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}s:5:"LOGIC";s:3:"AND";}}s:4:"sort";i:0;s:5:"limit";N;}')
				),
				array(
					'title' => GetMessage('TASKS_REPORT_DEFAULT_3'),
					'mark_default' => 3,
					'settings' => unserialize('a:6:{s:6:"entity";s:5:"Tasks";s:6:"period";a:2:{s:4:"type";s:5:"month";s:5:"value";N;}s:6:"select";a:8:{i:0;a:1:{s:4:"name";s:22:"RESPONSIBLE.SHORT_NAME";}i:1;a:1:{s:4:"name";s:5:"TITLE";}i:2;a:1:{s:4:"name";s:13:"STATUS_PSEUDO";}i:3;a:1:{s:4:"name";s:8:"PRIORITY";}i:4;a:1:{s:4:"name";s:12:"CREATED_DATE";}i:5;a:1:{s:4:"name";s:10:"DATE_START";}i:6;a:1:{s:4:"name";s:11:"CLOSED_DATE";}i:7;a:1:{s:4:"name";s:8:"DEADLINE";}}s:6:"filter";a:1:{i:0;a:3:{i:0;a:5:{s:4:"type";s:5:"field";s:4:"name";s:11:"RESPONSIBLE";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:1;a:5:{s:4:"type";s:5:"field";s:4:"name";s:5:"GROUP";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}s:5:"LOGIC";s:3:"AND";}}s:4:"sort";i:0;s:5:"limit";N;}')
				)
			),
			'11.0.3' => array(
				array(
					'title' => GetMessage('TASKS_REPORT_DEFAULT_4'),
					'mark_default' => 4,
					'settings' => unserialize('a:6:{s:6:"entity";s:5:"Tasks";s:6:"period";a:2:{s:4:"type";s:5:"month";s:5:"value";N;}s:6:"select";a:7:{i:0;a:2:{s:4:"name";s:22:"RESPONSIBLE.SHORT_NAME";s:5:"alias";s:9:"SSSSSSSSS";}i:1;a:3:{s:4:"name";s:6:"IS_NEW";s:5:"alias";s:5:"SSSSS";s:4:"aggr";s:3:"SUM";}i:2;a:3:{s:4:"name";s:10:"IS_RUNNING";s:5:"alias";s:8:"SSSSSSSS";s:4:"aggr";s:3:"SUM";}i:3;a:3:{s:4:"name";s:11:"IS_FINISHED";s:5:"alias";s:9:"SSSSSSSSS";s:4:"aggr";s:3:"SUM";}i:4;a:4:{s:4:"name";s:10:"IS_OVERDUE";s:5:"alias";s:10:"SSSSSSSSSS";s:4:"aggr";s:3:"SUM";s:5:"prcnt";s:1:"2";}i:5;a:4:{s:4:"name";s:9:"IS_MARKED";s:5:"alias";s:7:"SSSSSSS";s:4:"aggr";s:3:"SUM";s:5:"prcnt";s:1:"2";}i:6;a:2:{s:4:"name";s:18:"IS_EFFECTIVE_PRCNT";s:5:"alias";s:13:"SSSSSSSSSSSSS";}}s:6:"filter";a:1:{i:0;a:4:{i:0;a:5:{s:4:"type";s:5:"field";s:4:"name";s:11:"RESPONSIBLE";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:1;a:5:{s:4:"type";s:5:"field";s:4:"name";s:5:"GROUP";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:2;a:5:{s:4:"type";s:5:"field";s:4:"name";s:13:"ADD_IN_REPORT";s:7:"compare";s:5:"EQUAL";s:5:"value";s:4:"true";s:10:"changeable";s:1:"1";}s:5:"LOGIC";s:3:"AND";}}s:4:"sort";i:0;s:5:"limit";N;}')
				)
			),
			'11.0.8' => array(
				array(
					'title' => GetMessage('TASKS_REPORT_DEFAULT_5'),
					'mark_default' => 5,
					'settings' => unserialize('a:6:{s:6:"entity";s:5:"Tasks";s:6:"period";a:2:{s:4:"type";s:9:"month_ago";s:5:"value";N;}s:6:"select";a:6:{i:0;a:1:{s:4:"name";s:5:"TITLE";}i:2;a:1:{s:4:"name";s:22:"RESPONSIBLE.SHORT_NAME";}i:7;a:1:{s:4:"name";s:8:"PRIORITY";}i:3;a:1:{s:4:"name";s:13:"STATUS_PSEUDO";}i:5;a:1:{s:4:"name";s:8:"DURATION";}i:6;a:1:{s:4:"name";s:4:"MARK";}}s:6:"filter";a:1:{i:0;a:5:{i:0;a:5:{s:4:"type";s:5:"field";s:4:"name";s:11:"RESPONSIBLE";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:1;a:5:{s:4:"type";s:5:"field";s:4:"name";s:8:"PRIORITY";s:7:"compare";s:5:"EQUAL";s:5:"value";s:1:"1";s:10:"changeable";s:1:"1";}i:2;a:5:{s:4:"type";s:5:"field";s:4:"name";s:13:"STATUS_PSEUDO";s:7:"compare";s:5:"EQUAL";s:5:"value";s:1:"5";s:10:"changeable";s:1:"1";}i:3;a:5:{s:4:"type";s:5:"field";s:4:"name";s:5:"GROUP";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}s:5:"LOGIC";s:3:"AND";}}s:4:"sort";i:0;s:5:"limit";N;}')
				)
			),
			'14.0.10' => array(
				array(
					'title' => GetMessage('TASKS_REPORT_DEFAULT_6'),
					'description' => GetMessage('TASKS_REPORT_DEFAULT_6_DESCR'),
					'mark_default' => 6,
					'settings' => unserialize('a:9:{s:6:"entity";s:17:"Bitrix\Tasks\Task";s:6:"period";a:2:{s:4:"type";s:5:"month";s:5:"value";N;}s:6:"select";a:8:{i:0;a:1:{s:4:"name";s:5:"TITLE";}i:3;a:1:{s:4:"name";s:13:"STATUS_PSEUDO";}i:10;a:1:{s:4:"name";s:13:"TIME_ESTIMATE";}i:15;a:1:{s:4:"name";s:19:"DURATION_FOR_PERIOD";}i:5;a:1:{s:4:"name";s:8:"DURATION";}i:7;a:1:{s:4:"name";s:8:"DEADLINE";}i:6;a:1:{s:4:"name";s:11:"CLOSED_DATE";}i:8;a:1:{s:4:"name";s:22:"RESPONSIBLE.SHORT_NAME";}}s:6:"filter";a:2:{i:0;a:5:{i:0;a:5:{s:4:"type";s:5:"field";s:4:"name";s:5:"GROUP";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:1;a:5:{s:4:"type";s:5:"field";s:4:"name";s:13:"STATUS_PSEUDO";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:2;a:5:{s:4:"type";s:5:"field";s:4:"name";s:11:"RESPONSIBLE";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:3;a:2:{s:4:"type";s:6:"filter";s:4:"name";s:1:"1";}s:5:"LOGIC";s:3:"AND";}i:1;a:3:{i:0;a:5:{s:4:"type";s:5:"field";s:4:"name";s:19:"ALLOW_TIME_TRACKING";s:7:"compare";s:5:"EQUAL";s:5:"value";s:4:"true";s:10:"changeable";s:1:"0";}i:1;a:5:{s:4:"type";s:5:"field";s:4:"name";s:8:"DURATION";s:7:"compare";s:7:"GREATER";s:5:"value";s:1:"0";s:10:"changeable";s:1:"0";}s:5:"LOGIC";s:2:"OR";}}s:4:"sort";i:0;s:9:"sort_type";s:3:"ASC";s:5:"limit";N;s:12:"red_neg_vals";b:0;s:13:"grouping_mode";b:0;}')
				),
				array(
					'title' => GetMessage('TASKS_REPORT_DEFAULT_7'),
					'description' => GetMessage('TASKS_REPORT_DEFAULT_7_DESCR'),
					'mark_default' => 7,
					'settings' => unserialize('a:10:{s:6:"entity";s:17:"Bitrix\Tasks\Task";s:6:"period";a:2:{s:4:"type";s:5:"month";s:5:"value";N;}s:6:"select";a:5:{i:2;a:1:{s:4:"name";s:22:"RESPONSIBLE.SHORT_NAME";}i:4;a:3:{s:4:"name";s:2:"ID";s:5:"alias";s:0:"";s:4:"aggr";s:14:"COUNT_DISTINCT";}i:10;a:3:{s:4:"name";s:13:"TIME_ESTIMATE";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";}i:8;a:3:{s:4:"name";s:19:"DURATION_FOR_PERIOD";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";}i:6;a:3:{s:4:"name";s:8:"DURATION";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";}}s:6:"filter";a:2:{i:0;a:4:{i:0;a:5:{s:4:"type";s:5:"field";s:4:"name";s:5:"GROUP";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:1;a:5:{s:4:"type";s:5:"field";s:4:"name";s:13:"STATUS_PSEUDO";s:7:"compare";s:5:"EQUAL";s:5:"value";s:1:"5";s:10:"changeable";s:1:"1";}i:2;a:2:{s:4:"type";s:6:"filter";s:4:"name";s:1:"1";}s:5:"LOGIC";s:3:"AND";}i:1;a:3:{i:0;a:5:{s:4:"type";s:5:"field";s:4:"name";s:19:"ALLOW_TIME_TRACKING";s:7:"compare";s:5:"EQUAL";s:5:"value";s:4:"true";s:10:"changeable";s:1:"0";}i:1;a:5:{s:4:"type";s:5:"field";s:4:"name";s:8:"DURATION";s:7:"compare";s:7:"GREATER";s:5:"value";s:1:"0";s:10:"changeable";s:1:"0";}s:5:"LOGIC";s:2:"OR";}}s:4:"sort";i:8;s:9:"sort_type";s:4:"DESC";s:5:"limit";N;s:12:"red_neg_vals";b:0;s:13:"grouping_mode";b:0;s:5:"chart";a:4:{s:7:"display";b:1;s:4:"type";s:3:"bar";s:8:"x_column";i:2;s:9:"y_columns";a:2:{i:0;i:10;i:1;i:6;}}}')
				)
			)
		);

		foreach ($reports as $version => &$vreports)
		{
			foreach ($vreports as $num => &$report)
			{
				if ($version === '11.0.3' && $num === 0)
				{
					$report['settings']['select'][0]['alias'] = GetMessage('TASKS_REPORT_EFF_EMPLOYEE');
					$report['settings']['select'][1]['alias'] = GetMessage('TASKS_REPORT_EFF_NEW');
					$report['settings']['select'][2]['alias'] = GetMessage('TASKS_REPORT_EFF_OPEN');
					$report['settings']['select'][3]['alias'] = GetMessage('TASKS_REPORT_EFF_CLOSED');
					$report['settings']['select'][4]['alias'] = GetMessage('TASKS_REPORT_EFF_OVERDUE');
					$report['settings']['select'][5]['alias'] = GetMessage('TASKS_REPORT_EFF_MARKED');
					$report['settings']['select'][6]['alias'] = GetMessage('TASKS_REPORT_EFF_EFFICIENCY');
				}
				else if ($version === '14.0.10' && $report['mark_default'] === 7)
				{
					$report['settings']['select'][4]['alias'] = GetMessage('TASKS_REPORT_DEFAULT_7_ALIAS_4');
					$report['settings']['select'][6]['alias'] = GetMessage('TASKS_REPORT_DEFAULT_7_ALIAS_6');
					$report['settings']['select'][8]['alias'] = GetMessage('TASKS_REPORT_DEFAULT_7_ALIAS_8');
					$report['settings']['select'][10]['alias'] = GetMessage('TASKS_REPORT_DEFAULT_7_ALIAS_10');
				}

				// remove reports, which not work in MSSQL
				/*global $DBType;
				if (ToUpper($DBType) === 'MSSQL')
				{
					if (($version === '11.0.1' && $report['mark_default'] === 2)
						|| ($version === '14.0.10' && $report['mark_default'] === 7))
					{
						unset($vreports[$num]);
					}
				}*/
			}
		}

		return $reports;
	}


	public static function getFirstVersion()
	{
		return '11.0.1';
	}


	public static function getCurrentVersion()
	{
		$arModuleVersion = array();
		include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/tasks/install/version.php");
		return $arModuleVersion['VERSION'];
	}
}

