<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 * 
 * @global CMain $APPLICATION
 * @global CDatabase $DB
 * @global CUser $USER
 */
global $APPLICATION, $DB, $USER;

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Tasks\Template;

Loc::loadMessages(__FILE__);

class CTaskTemplates
{
	const TYPE_FOR_NEW_USER =		0x01;

	const CURRENT_SITE_ID =			'*';

	private $_errors = array();

	function GetErrors()
	{
		return $this->_errors;
	}


	function CheckFields(&$arFields, $ID = false, $arParams = array())
	{
		global $APPLICATION;

		if(intval($ID))
		{
			if(!is_array($this->currentData))
				$this->currentData = self::GetById($ID)->fetch();
		}

		////////////////////////////////////
		// deal with TYPE

		if(intval($this->currentData['ID']) && isset($arFields['TPARAM_TYPE']) && $this->currentData['TPARAM_TYPE'] != $arFields['TPARAM_TYPE'])
		{
			$this->_errors[] = array("text" => 'You can not change TYPE of an existing template', "id" => "ERROR_TASKS_BAD_TEMPLATE_PARAMETER_TYPE");
			unset($arFields['TPARAM_TYPE']); // You may not change template type of an existing template
		}

		// check allowed value
		if(isset($arFields['TPARAM_TYPE']))
		{
			if($arFields['TPARAM_TYPE'] != static::TYPE_FOR_NEW_USER) // white list here later
				$this->_errors[] = array("text" => 'Unknown template type id passed', "id" => "ERROR_TASKS_BAD_TEMPLATE_PARAMETER_TYPE");
		}

		// unset some data, if type is TYPE_FOR_NEW_USER
		if((!intval($ID) && $arFields['TPARAM_TYPE'] == CTaskTemplates::TYPE_FOR_NEW_USER) || (intval($ID) && $this->currentData['TPARAM_TYPE'] == CTaskTemplates::TYPE_FOR_NEW_USER))
		{
			$arFields['BASE_TEMPLATE_ID'] = ''; // such kind of template can not have parent template ...
			$arFields['REPLICATE_PARAMS'] = serialize(array()); // ... and replication params
			$arFields['RESPONSIBLE_ID'] = ''; // ... and responsible_id
			$arFields['RESPONSIBLES'] = serialize(array()); // ... and other responsibles
			$arFields['MULTITASK'] = 'N'; // ... and be a multitask
		}

		////////////////////////////////////
		// deal with RESPONSIBLES

		if(($ID === false && $arFields['TPARAM_TYPE'] != CTaskTemplates::TYPE_FOR_NEW_USER) || ($ID !== false && $this->currentData['TPARAM_TYPE'] != CTaskTemplates::TYPE_FOR_NEW_USER))
		{
			if(isset($arFields["RESPONSIBLE_ID"]))
			{
				/** @noinspection PhpDynamicAsStaticMethodCallInspection */
				$r = CUser::GetByID($arFields["RESPONSIBLE_ID"]);
				if (!$r->Fetch())
				{
					$this->_errors[] = array("text" => GetMessage("TASKS_BAD_RESPONSIBLE_ID_EX"), "id" => "ERROR_TASKS_BAD_RESPONSIBLE_ID_EX");
				}
			}
			else
			{
				if($ID === false)
					$this->_errors[] = array("text" => GetMessage("TASKS_BAD_RESPONSIBLE_ID"), "id" => "ERROR_TASKS_BAD_RESPONSIBLE_ID");
			}
		}

		////////////////////////////////////
		// deal with other data

		if ((is_set($arFields, "TITLE") || $ID === false) && strlen($arFields["TITLE"]) <= 0)
		{
			$this->_errors[] = array("text" => GetMessage("TASKS_BAD_TITLE"), "id" => "ERROR_BAD_TASKS_TITLE");
		}

		if (isset($arFields['BASE_TEMPLATE_ID']))
		{
			try
			{
				if(intval($arFields['BASE_TEMPLATE_ID']))
				{
					$template = static::GetList(array(), array('ID' => $arFields['BASE_TEMPLATE_ID']), false, false, array('ID'))->fetch();
					if(!is_array($template))
						$this->_errors[] = array("text" => Loc::getMessage("TASKS_TEMPLATE_BASE_TEMPLATE_ID_NOT_EXISTS"), "id" => "ERROR_TASKS_BASE_TEMPLATE_ID_NOT_EXISTS");

					// you cannot add a template with both PARENT_ID and BASE_TEMPLATE_ID set. BASE_TEMPLATE_ID has greather priority
					if(isset($arFields['PARENT_ID']))
						$arFields['PARENT_ID'] = '';

					// you cannot add REPLICATE parameters here in case of BASE_TEMPLATE_ID is set
					if(isset($arFields['REPLICATE']))
						$arFields['REPLICATE'] = serialize(array());

					$arFields['REPLICATE_PARAMS'] = serialize(array());
				}
			}
			catch(\Bitrix\Main\ArgumentException $e)
			{
				$this->_errors[] = array("text" => Loc::getMessage("TASKS_TEMPLATE_BAD_BASE_TEMPLATE_ID"), "id" => "ERROR_TASKS_BAD_BASE_TEMPLATE_ID");
			}
		}

		if (is_set($arFields, "PARENT_ID") && intval($arFields["PARENT_ID"]) > 0)
		{
			$r = CTasks::GetList(array(), array("ID" => $arFields["PARENT_ID"]));
			if (!$r->Fetch())
			{
				$this->_errors[] = array("text" => GetMessage("TASKS_BAD_PARENT_ID"), "id" => "ERROR_TASKS_BAD_PARENT_ID");
			}
		}

		if (
			isset($arFields['FILES'])
			&& isset($arParams['CHECK_RIGHTS_ON_FILES'])
			&& (
				($arParams['CHECK_RIGHTS_ON_FILES'] === true)
				|| ($arParams['CHECK_RIGHTS_ON_FILES'] === 'Y')
			)
		)
		{
			CTaskAssert::assert(
				isset($arParams['USER_ID'])
				&& CTaskAssert::isLaxIntegers($arParams['USER_ID'])
				&& ($arParams['USER_ID'] > 0)
			);

			// Is there files?
			if ($arFields['FILES'] !== false)
			{
				// There is must be serialized array
				$arFilesIds = unserialize($arFields['FILES']);

				if (is_array($arFilesIds))
				{
					$ar = CTaskFiles::checkFilesAccessibilityByUser($arFilesIds, (int) $arParams['USER_ID']);

					// If we have one file, that is not accessible, than emit error
					foreach ($arFilesIds as $fileId)
					{
						if (
							( ! isset($ar['f' . $fileId]) )
							|| ($ar['f' . $fileId] === false)
						)
						{
							$this->_errors[] = array('text' => GetMessage('TASKS_BAD_FILE_ID_EX'), 'id' => 'ERROR_TASKS_BAD_FILE_ID_EX');
						}
					}
				}
				else
				{
					$this->_errors[] = array('text' => GetMessage('TASKS_BAD_FILE_ID_EX'), 'id' => 'ERROR_TASKS_BAD_FILE_ID_EX');
				}
			}
		}

		if (!empty($this->_errors))
		{
			$e = new CAdminException($this->_errors);
			$APPLICATION->ThrowException($e);
			return false;
		}

		//Defaults
		if (is_set($arFields, "PRIORITY") && !in_array($arFields["PRIORITY"], Array(0, 1, 2)))
			$arFields["PRIORITY"] = 1;

		return true;
	}


	function Add($arFields, $arParams = array())
	{
		global $DB, $USER_FIELD_MANAGER;

		$bCheckFilesPermissions = false;
		if (
			isset($arParams['CHECK_RIGHTS_ON_FILES'])
			&& (
				($arParams['CHECK_RIGHTS_ON_FILES'] === true)
				|| ($arParams['CHECK_RIGHTS_ON_FILES'] === 'Y')
			)
		)
		{
			CTaskAssert::assert(
				isset($arParams['USER_ID'])
				&& CTaskAssert::isLaxIntegers($arParams['USER_ID'])
				&& ($arParams['USER_ID'] > 0)
			);

			$bCheckFilesPermissions = true;
		}

		$arParamsForCheckFields = array(
			'CHECK_RIGHTS_ON_FILES' => $bCheckFilesPermissions
		);

		if (isset($arParams['USER_ID']))
			$arParamsForCheckFields['USER_ID'] = $arParams['USER_ID'];

		if ($this->CheckFields($arFields, false, $arParamsForCheckFields))
		{
			if ($USER_FIELD_MANAGER->CheckFields("TASKS_TASK_TEMPLATE", 0, $arFields, $effectiveUserId))
			{
				$arBinds = Array(
					"DESCRIPTION",
					"REPLICATE_PARAMS",
					"ACCOMPLICES",
					"AUDITORS",
					"FILES",
					"TAGS",
					"DEPENDS_ON",
					"RESPONSIBLES"
				);

				// fix for absent SITE_ID
				if((string) $arFields['SITE_ID'] == '' || $arFields['SITE_ID'] == static::CURRENT_SITE_ID)
				{
					$arFields['SITE_ID'] = SITE_ID;
				}

				$ID = $DB->Add("b_tasks_template", $arFields, $arBinds, "tasks");
				if (isset($arFields['FILES']))
					CTaskFiles::removeTemporaryStatusForFiles(unserialize($arFields['FILES']), $arParams['USER_ID']);

				$USER_FIELD_MANAGER->Update("TASKS_TASK_TEMPLATE", $ID, $arFields, $effectiveUserId);

				// periodic tasks
				if ($arFields["REPLICATE"] == "Y")
				{
					// Firstly, remove all agents for this template
					/** @noinspection PhpDynamicAsStaticMethodCallInspection */
					CAgent::RemoveAgent('CTasks::RepeatTaskByTemplateId('
						. $ID . ');', 'tasks');

					/** @noinspection PhpDynamicAsStaticMethodCallInspection */
					CAgent::RemoveAgent('CTasks::RepeatTaskByTemplateId('
						. $ID . ', 0);', 'tasks');

					/** @noinspection PhpDynamicAsStaticMethodCallInspection */
					CAgent::RemoveAgent('CTasks::RepeatTaskByTemplateId('
						. $ID . ', 1);', 'tasks');

					// Set up new agent
					if ($arFields['REPLICATE'] === 'Y')
					{
						$nextTime = CTasks::GetNextTime(unserialize($arFields['REPLICATE_PARAMS']));
						if ($nextTime)
						{
							CTimeZone::Disable();
							/** @noinspection PhpDynamicAsStaticMethodCallInspection */
							CAgent::AddAgent(
								'CTasks::RepeatTaskByTemplateId(' . $ID . ', 0);', 
								'tasks', 
								'N', 		// is periodic?
								86400, 		// interval
								$nextTime, 	// datecheck
								'Y', 		// is active?
								$nextTime	// next_exec
							);
							CTimeZone::Enable();
						}
					}
				}

				// template tree
				if (intval($arFields['BASE_TEMPLATE_ID']))
				{
					try
					{
						Template\DependencyTable::createLink($ID, intval($arFields['BASE_TEMPLATE_ID']));
					}
					catch(\Bitrix\Tasks\DB\Tree\LinkExistsException $e)
					{
					}
				}

				return $ID;
			}
		}

		return false;
	}


	function Update($ID, $arFields, $arParams = array())
	{
		global $DB, $USER, $USER_FIELD_MANAGER;

		$ID = intval($ID);
		if ($ID < 1)
			return false;

		$bCheckFilesPermissions = false;
		if (
			isset($arParams['CHECK_RIGHTS_ON_FILES'])
			&& (
				($arParams['CHECK_RIGHTS_ON_FILES'] === true)
				|| ($arParams['CHECK_RIGHTS_ON_FILES'] === 'Y')
			)
		)
		{
			CTaskAssert::assert(
				isset($arParams['USER_ID'])
				&& CTaskAssert::isLaxIntegers($arParams['USER_ID'])
				&& ($arParams['USER_ID'] > 0)
			);

			$bCheckFilesPermissions = true;
		}

		$arParamsForCheckFields = array(
			'CHECK_RIGHTS_ON_FILES' => $bCheckFilesPermissions
		);

		if (isset($arParams['USER_ID']))
			$arParamsForCheckFields['USER_ID'] = $arParams['USER_ID'];

		// We need understand, does REPLICATE_PARAMS changed
		$rsCurData = self::GetByID($ID);
		$arCurData = $rsCurData->Fetch();
		$this->currentData = $arCurData;

		if(intval($arCurData['BASE_TEMPLATE_ID']) > 0) // for sub-template ...
		{
			unset($arFields['REPLICATE']); // ... you cannot set replicate params
			unset($arFields['PARENT_ID']); // ... and base task
			$isReplicateParamsChanged = false;
		}
		else
		{
			$isReplicateParamsChanged = 
				(
					isset($arFields['REPLICATE']) 
					&& 
					($arCurData['REPLICATE'] !== $arFields['REPLICATE'])
				)
				||
				(
					isset($arFields['REPLICATE_PARAMS'])
					&&
					($arCurData['REPLICATE_PARAMS'] !== $arFields['REPLICATE_PARAMS'])
				);
		}

		if ($userID === null)
			$userID = is_object($USER) ? intval($USER->GetID()) : 1;

		if ($this->CheckFields($arFields, $ID, $arParamsForCheckFields))
		{
			if ($USER_FIELD_MANAGER->CheckFields("TASKS_TASK_TEMPLATE", $ID, $arFields))
			{
				unset($arFields['ID']);

				$arBinds = Array(
					'DESCRIPTION'      => $arFields['DESCRIPTION'],
					'REPLICATE_PARAMS' => $arFields['REPLICATE_PARAMS'],
					'ACCOMPLICES'      => $arFields['ACCOMPLICES'],
					'AUDITORS'         => $arFields['AUDITORS'],
					'FILES'            => $arFields['FILES'],
					'TAGS'             => $arFields['TAGS'],
					'DEPENDS_ON'       => $arFields['DEPENDS_ON']
				);

				$strUpdate = $DB->PrepareUpdate('b_tasks_template', $arFields, 'tasks');
				if((string) $strUpdate !== '')
				{
					$strSql = "UPDATE b_tasks_template SET " . $strUpdate . " WHERE ID=" . $ID;
					$DB->QueryBind($strSql, $arBinds, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				}

				if (isset($arFields['FILES']))
					CTaskFiles::removeTemporaryStatusForFiles(unserialize($arFields['FILES']), $arParams['USER_ID']);

				$USER_FIELD_MANAGER->Update("TASKS_TASK_TEMPLATE", $ID, $arFields, $userID);

				// update template tree, if required
				if (isset($arFields['BASE_TEMPLATE_ID']))
				{
					try
					{
						Template\DependencyTable::link($ID, intval($arFields['BASE_TEMPLATE_ID']), array('CREATE_PARENT_NODE_ON_NOTFOUND' => true));
					}
					catch(\Bitrix\Tasks\DB\Tree\LinkExistsException $e)
					{
					}
				}

				if ($isReplicateParamsChanged)
				{
					// Firstly, remove all agents for this template
					/** @noinspection PhpDynamicAsStaticMethodCallInspection */
					CAgent::RemoveAgent('CTasks::RepeatTaskByTemplateId('
						. $ID . ');', 'tasks');

					/** @noinspection PhpDynamicAsStaticMethodCallInspection */
					CAgent::RemoveAgent('CTasks::RepeatTaskByTemplateId('
						. $ID . ', 0);', 'tasks');

					/** @noinspection PhpDynamicAsStaticMethodCallInspection */
					CAgent::RemoveAgent('CTasks::RepeatTaskByTemplateId('
						. $ID . ', 1);', 'tasks');

					// Set up new agent
					if ($arFields['REPLICATE'] === 'Y')
					{
						$nextTime = CTasks::GetNextTime(unserialize($arFields['REPLICATE_PARAMS']));
						if ($nextTime)
						{
							CTimeZone::Disable();
							/** @noinspection PhpDynamicAsStaticMethodCallInspection */
							CAgent::AddAgent(
								'CTasks::RepeatTaskByTemplateId(' . $ID . ', 0);', 
								'tasks', 
								'N', 		// is periodic?
								86400, 		// interval
								$nextTime, 	// datecheck
								'Y', 		// is active?
								$nextTime	// next_exec
							);
							CTimeZone::Enable();
						}
					}
				}

				return true;
			}
		}

		return false;
	}


	function Delete($ID)
	{
		global $DB, $USER_FIELD_MANAGER;

		$ID = intval($ID);

		if ($ID > 0)
		{
			$rsTemplate = CTaskTemplates::GetByID($ID);

			if ($arTemplate = $rsTemplate->Fetch())
			{
				// Remove all agents for this template
				/** @noinspection PhpDynamicAsStaticMethodCallInspection */
				CAgent::RemoveAgent('CTasks::RepeatTaskByTemplateId('
					. $ID . ');', 'tasks');

				/** @noinspection PhpDynamicAsStaticMethodCallInspection */
				CAgent::RemoveAgent('CTasks::RepeatTaskByTemplateId('
					. $ID . ', 0);', 'tasks');

				/** @noinspection PhpDynamicAsStaticMethodCallInspection */
				CAgent::RemoveAgent('CTasks::RepeatTaskByTemplateId('
					. $ID . ', 1);', 'tasks');

				if ($arTemplate["FILES"])
				{
					$arFiles = unserialize($arTemplate["FILES"]);
					if (is_array($arFiles))
					{
						$arFilesToDelete = array();
						foreach($arFiles as $file)
						{
							$rsFile = CTaskFiles::GetList(array(), array("FILE_ID" => $file));
							if (!$arFile = $rsFile->Fetch())
							{
								$arFilesToDelete[] = $file;
							}
						}
						foreach ($arFilesToDelete as $file)
						{
							CFile::Delete($file);
						}

					}
				}

				$dbConnection = \Bitrix\Main\HttpApplication::getConnection();

				$strSql = "DELETE FROM b_tasks_template WHERE ID = ".$ID;

				if ($DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__))
				{
					$USER_FIELD_MANAGER->Delete("TASKS_TASK_TEMPLATE", $ID);

					// drop children (sub-templates)
					$dbConnection->query("delete from b_tasks_template where ID in (".Template\DependencyTable::getSubTreeSql($ID).")");

					// drop tree records for this templates and it`s children
					try
					{
						Template\DependencyTable::dropLinkL($ID);
					}
					catch(\Bitrix\Tasks\DB\Tree\TargetNodeNotFoundException $e) // had no children actually
					{
					}

					return true;
				}
			}
		}

		return false;
	}


	/**
	 * @param $arOrder
	 * @param $arFilter
	 * @param array $arNavParams
	 * @param array $arParams
	 * @param array $arSelect
	 * @return bool|CDBResult
	 *
	 * @global $DB CDatabase
	 * @global $DBType string
	 */
	public static function GetList($arOrder, $arFilter, $arNavParams = array(), $arParams = array(), $arSelect = array())
	{
		global $DB, $DBType, $USER_FIELD_MANAGER;

		$arSqlSearch = CTaskTemplates::GetFilter($arFilter, $arParams);

		// check permissions
		if (isset($arParams['USER_ID']))
		{
			$executiveUserId = (int) $arParams['USER_ID'];

			$isAdmin = CTasksTools::IsAdmin($executiveUserId)
				|| CTasksTools::IsPortalB24Admin($executiveUserId);

			if ( ! $isAdmin )
			{
				$sql = 'TT.CREATED_BY = ' . (int) $executiveUserId;

				if ($executiveUserId && ($arDepsIDs = CTasks::GetSubordinateDeps($executiveUserId)))
				{
					if (!is_array($arDepsIDs))
						$arDepsIDs = array(intval($arDepsIDs));

					/** @noinspection PhpDynamicAsStaticMethodCallInspection */
					$rsDepartmentField = CUserTypeEntity::GetList(array(), array("ENTITY_ID" => "USER", "FIELD_NAME" => "UF_DEPARTMENT"));
					$cntOfDepartments = count($arDepsIDs);
					if ($cntOfDepartments && $arDepartmentField = $rsDepartmentField->Fetch())
					{
						if (
							(strtolower($DBType) === 'oracle')
							&& ($valuesLimit = 1000)
							&& ($cntOfDepartments > $valuesLimit)
						)
						{
							$arConstraints = array();
							$sliceIndex = 0;
							while ($sliceIndex < $cntOfDepartments)
							{
								$arConstraints[] = 'BUF1.VALUE_INT IN (' 
									. implode(',', array_slice($arDepsIDs, $sliceIndex, $valuesLimit))
									. ')';

								$sliceIndex += $valuesLimit;
							}

							$strConstraint = '(' . implode(' OR ', $arConstraints) . ')';
						}
						else
							$strConstraint = "BUF1.VALUE_INT IN (" . implode(",", $arDepsIDs) . ")";

						$sql .= "
							OR EXISTS (
								SELECT
									'x'
								FROM
									b_utm_user BUF1
								WHERE
									BUF1.FIELD_ID = " . $arDepartmentField["ID"] . "
								AND BUF1.VALUE_ID = TT.CREATED_BY
								AND " . $strConstraint . "
							) ";
					}
				}

				$arSqlSearch[] = ' (' . $sql . ') ';
			}
		}

		$obUserFieldsSql = new CUserTypeSQL();
		$obUserFieldsSql->SetEntity("TASKS_TASK_TEMPLATE", "TT.ID");
		$obUserFieldsSql->SetSelect($arSelect);
		$obUserFieldsSql->SetFilter($arFilter);
		$obUserFieldsSql->SetOrder($arOrder);

		$r = $obUserFieldsSql->GetFilter();
		if (strlen($r) > 0)
		{
			$arSqlSearch[] = "(".$r.")";
		}

		$arFields = array(

			// task fields
			'ID' => 						array('FIELD' => 'TT.ID', 'DEFAULT' => true),
			'TITLE' => 						array('FIELD' => 'TT.TITLE', 'DEFAULT' => true),
			'DESCRIPTION' => 				array('FIELD' => 'TT.DESCRIPTION', 'DEFAULT' => true),
			'DESCRIPTION_IN_BBCODE' => 		array('FIELD' => 'TT.DESCRIPTION_IN_BBCODE', 'DEFAULT' => true),
			'PRIORITY' => 					array('FIELD' => 'TT.PRIORITY', 'DEFAULT' => true),
			'STATUS' => 					array('FIELD' => 'TT.STATUS', 'DEFAULT' => true),
			'RESPONSIBLE_ID' => 			array('FIELD' => 'TT.RESPONSIBLE_ID', 'DEFAULT' => true),
			'DEADLINE_AFTER' => 			array('FIELD' => 'TT.DEADLINE_AFTER', 'DEFAULT' => true),
			'REPLICATE' => 					array('FIELD' => 'TT.REPLICATE', 'DEFAULT' => true),
			'REPLICATE_PARAMS' => 			array('FIELD' => 'TT.REPLICATE_PARAMS', 'DEFAULT' => true),
			'CREATED_BY' => 				array('FIELD' => 'TT.CREATED_BY', 'DEFAULT' => true),
			'XML_ID' => 					array('FIELD' => 'TT.XML_ID', 'DEFAULT' => true),
			'ALLOW_CHANGE_DEADLINE' => 		array('FIELD' => 'TT.ALLOW_CHANGE_DEADLINE', 'DEFAULT' => true),
			'ALLOW_TIME_TRACKING'   => 		array('FIELD' => 'TT.ALLOW_TIME_TRACKING', 'DEFAULT' => true),
			'TASK_CONTROL' => 				array('FIELD' => 'TT.TASK_CONTROL', 'DEFAULT' => true),
			'ADD_IN_REPORT' => 				array('FIELD' => 'TT.ADD_IN_REPORT', 'DEFAULT' => true),
			'GROUP_ID' => 					array('FIELD' => 'TT.GROUP_ID', 'DEFAULT' => true),
			'PARENT_ID' => 					array('FIELD' => 'TT.PARENT_ID', 'DEFAULT' => true),
			'MULTITASK' => 					array('FIELD' => 'TT.MULTITASK', 'DEFAULT' => true),
			'SITE_ID' => 					array('FIELD' => 'TT.SITE_ID', 'DEFAULT' => true),
			'ACCOMPLICES' => 				array('FIELD' => 'TT.ACCOMPLICES', 'DEFAULT' => true),
			'AUDITORS' => 					array('FIELD' => 'TT.AUDITORS', 'DEFAULT' => true),
			'RESPONSIBLES' => 				array('FIELD' => 'TT.RESPONSIBLES', 'DEFAULT' => true),
			'FILES' => 						array('FIELD' => 'TT.FILES', 'DEFAULT' => true),
			'TAGS' => 						array('FIELD' => 'TT.TAGS', 'DEFAULT' => true),
			'DEPENDS_ON' => 				array('FIELD' => 'TT.DEPENDS_ON', 'DEFAULT' => true),

			// template parameters
			'TASK_ID' => 					array('FIELD' => 'TT.TASK_ID', 'DEFAULT' => true),
			'TPARAM_TYPE' => 				array('FIELD' => 'TT.TPARAM_TYPE', 'DEFAULT' => true),

			// virtual
			'BASE_TEMPLATE_ID' => 			array('FIELD' => 'CASE WHEN TDD.'.Template\DependencyTable::getPARENTIDColumnName().' IS NULL THEN 0 ELSE TDD.'.Template\DependencyTable::getPARENTIDColumnName().' END', 'DEFAULT' => false),
			'TEMPLATE_CHILDREN_COUNT' => 	array('FIELD' => 'CASE WHEN TEMPLATE_CHILDREN_COUNT IS NULL THEN 0 ELSE TEMPLATE_CHILDREN_COUNT END', 'DEFAULT' => false),

			// additional
			'CREATED_BY_NAME' => 			array('FIELD' => 'CU.NAME', 'DEFAULT' => true, 'ALWAYS' => true),
			'CREATED_BY_LAST_NAME' => 		array('FIELD' => 'CU.LAST_NAME ', 'DEFAULT' => true, 'ALWAYS' => true),
			'CREATED_BY_SECOND_NAME' =>		array('FIELD' => 'CU.SECOND_NAME', 'DEFAULT' => true, 'ALWAYS' => true),
			'CREATED_BY_LOGIN' => 			array('FIELD' => 'CU.LOGIN', 'DEFAULT' => true, 'ALWAYS' => true),
			'CREATED_BY_WORK_POSITION' => 	array('FIELD' => 'CU.WORK_POSITION', 'DEFAULT' => true, 'ALWAYS' => true),
			'CREATED_BY_PHOTO' => 			array('FIELD' => 'CU.PERSONAL_PHOTO', 'DEFAULT' => true, 'ALWAYS' => true),
			'RESPONSIBLE_NAME' => 			array('FIELD' => 'RU.NAME', 'DEFAULT' => true, 'ALWAYS' => true),
			'RESPONSIBLE_LAST_NAME' => 		array('FIELD' => 'RU.LAST_NAME', 'DEFAULT' => true, 'ALWAYS' => true),
			'RESPONSIBLE_SECOND_NAME' => 	array('FIELD' => 'RU.SECOND_NAME', 'DEFAULT' => true, 'ALWAYS' => true),
			'RESPONSIBLE_LOGIN' => 			array('FIELD' => 'RU.LOGIN', 'DEFAULT' => true, 'ALWAYS' => true),
			'RESPONSIBLE_WORK_POSITION' => 	array('FIELD' => 'RU.WORK_POSITION', 'DEFAULT' => true, 'ALWAYS' => true),
			'RESPONSIBLE_PHOTO' => 			array('FIELD' => 'RU.PERSONAL_PHOTO', 'DEFAULT' => true, 'ALWAYS' => true),
		);

		$filterByBaseTemplate =		false;
		$selectBaseTemplateId =		false;
		$useChildrenCount =			false;

		if (!is_array($arSelect))
			$arSelect = array();

		$defaultSelect = array();
		$alwaysSelect = array();
		foreach($arFields as $field => $rule)
		{
			if($rule['DEFAULT'])
				$defaultSelect[] = $field;
			if($rule['ALWAYS'])
				$alwaysSelect[] = $field;
		}

		if (count($arSelect) <= 0)
			$arSelect = $defaultSelect;
		elseif(in_array("*", $arSelect))
			$arSelect = array_diff(array_merge($defaultSelect, $arSelect)/*all plus smth*/, array("*")/*minus star*/);

		$arSelect = array_merge($arSelect, $alwaysSelect);

		$selectBaseTemplateId = in_array('BASE_TEMPLATE_ID', $arSelect);
		$useChildrenCount = in_array('TEMPLATE_CHILDREN_COUNT', $arSelect);

		if (!is_array($arOrder))
			$arOrder = array();

		foreach($arOrder as $field => $direction)
		{
			if($field == 'BASE_TEMPLATE_ID')
			{
				$selectBaseTemplateId = true;
			}
			if($field == 'TEMPLATE_CHILDREN_COUNT')
			{
				$useChildrenCount = true;
			}
		}

		if (!is_array($arFilter))
			$arFilter = array();

		if (!is_array($arParams))
			$arParams = array();

		foreach($arFilter as $key => $value)
		{
			$keyParsed = CTasks::MkOperationFilter($key);
			if($keyParsed['FIELD'] == 'BASE_TEMPLATE_ID')
			{
				$filterByBaseTemplate = true;
			}
			if($keyParsed['FIELD'] == 'TEMPLATE_CHILDREN_COUNT')
			{
				$useChildrenCount = true;
			}
		}

		$includeSubtree = 	$arParams['INCLUDE_TEMPLATE_SUBTREE'] === true || $arParams['INCLUDE_TEMPLATE_SUBTREE'] === 'Y';
		$excludeSubtree = 	$arParams['EXCLUDE_TEMPLATE_SUBTREE'] === true || $arParams['EXCLUDE_TEMPLATE_SUBTREE'] === 'Y';

		$treeJoin = '';
		if($excludeSubtree)
		{
			$treeJoin = "";
		}
		else
		{
			$treeJoin = "LEFT JOIN ".Template\DependencyTable::getTableName()." TD on TT.ID = TD.TEMPLATE_ID".($includeSubtree ? "" : " AND TD.DIRECT = '1'");
		}

		$temporalTableName = \Bitrix\Tasks\DB\Helper::getTemporaryTableNameSql();

		$strFrom = "FROM
				b_tasks_template TT

			".$treeJoin."

			".($selectBaseTemplateId ? "
			LEFT JOIN
				".Template\DependencyTable::getTableName()." TDD ON TT.ID = TDD.TEMPLATE_ID AND TDD.DIRECT = '1'
			" : "
			")."

			".($useChildrenCount ? "
				LEFT JOIN (
					SELECT TTI.ID, COUNT(TDDC.TEMPLATE_ID) AS TEMPLATE_CHILDREN_COUNT
					from
						b_tasks_template TTI
						INNER JOIN ".Template\DependencyTable::getTableName()." TDDC ON TTI.ID = TDDC.PARENT_TEMPLATE_ID AND TDDC.DIRECT = '1'
					GROUP BY TTI.ID
				) ".$temporalTableName." on ".$temporalTableName.".ID = TT.ID
			" : "
			")."

			LEFT JOIN
				b_user CU ON CU.ID = TT.CREATED_BY
			LEFT JOIN
				b_user RU ON RU.ID = TT.RESPONSIBLE_ID
			
			".$obUserFieldsSql->GetJoin("TT.ID")."

			" . (
					sizeof($arSqlSearch)
					? "WHERE " . implode(" AND ", $arSqlSearch)
					: ""
			) . " ";

		foreach ($arOrder as $by => $order)
		{
			$by = strtolower($by);
			$order = strtolower($order);
			if ($order != "asc")
				$order = "desc";

			if ($by == "task")
				$arSqlOrder[] = " TT ".$order." ";
			elseif ($by == "title")
				$arSqlOrder[] = " TT.TITLE ".$order." ";
			elseif ($by == "depends_on")
				$arSqlOrder[] = " TT.DEPENDS_ON ".$order." ";
			elseif ($by == "rand")
				$arSqlOrder[] = CTasksTools::getRandFunction();
			elseif ($by === 'responsible_last_name')
				$arSqlOrder[] = " RU.LAST_NAME ".$order." ";
			elseif ($by === 'tparam_type')
				$arSqlOrder[] = " TT.TPARAM_TYPE ".$order." ";
			elseif ($by === 'template_children_count')
				$arSqlOrder[] = " TEMPLATE_CHILDREN_COUNT ".$order." ";
			elseif ($by === 'base_template_id')
				$arSqlOrder[] = " BASE_TEMPLATE_ID ".$order." ";
			elseif(substr($by, 0, 3) === 'uf_')
			{
				if ($s = $obUserFieldsSql->GetOrder($by))
					$arSqlOrder[$by] = " ".$s." ".$order." ";
			}
			else
			{
				$arSqlOrder[] = " TT.ID ".$order." ";
				$by = "id";
			}

			if (
				($by !== 'rand')
				&& ( ! in_array(strtoupper($by), $arSelect) )
			)
			{
				$arSelect[] = strtoupper($by);
			}
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

		if (!in_array("ID", $arSelect))
			$arSelect[] = "ID";

		$arSqlSelect = array();
		foreach ($arSelect as $field)
		{
			$field = strtoupper($field);
			if (array_key_exists($field, $arFields))
			{
				$arSqlSelect[$field] = \Bitrix\Tasks\DB\Helper::wrapColumnWithFunction($arFields[$field]['FIELD'], $arFields[$field]['WRAP'])." AS ".$field;
			}
		}

		if (!sizeof($arSqlSelect))
			$arSqlSelect = "TT.ID AS ID";
		else
			$arSqlSelect = implode(",\n", $arSqlSelect);

		$ufSelect = $obUserFieldsSql->GetSelect();
		if(strlen($ufSelect))
		{
			$arSqlSelect .= $ufSelect;
		}

		$strSql = "
			SELECT 
				".$arSqlSelect."
				" . $strFrom."
				". $strSqlOrder;

		if (isset($arNavParams["NAV_PARAMS"]) && is_array($arNavParams["NAV_PARAMS"]))
		{
			$nTopCount = (int) $arNavParams['NAV_PARAMS']['nTopCount'];

			if ($nTopCount > 0)
			{
				$strSql = $DB->TopSql($strSql, $nTopCount);
				$res = $DB->Query($strSql, false, "File: " . __FILE__ . "<br>Line: " . __LINE__);

				$res->SetUserFields($USER_FIELD_MANAGER->GetUserFields("TASKS_TASK_TEMPLATE"));
			}
			else
			{
				$res_cnt = $DB->Query("SELECT COUNT(TT.ID) as C " . $strFrom);
				$res_cnt = $res_cnt->Fetch();
				$res = new CDBResult();
				$res->SetUserFields($USER_FIELD_MANAGER->GetUserFields("TASKS_TASK_TEMPLATE"));
				$res->NavQuery($strSql, $res_cnt["C"], $arNavParams["NAV_PARAMS"]);
			}
		}
		else
		{
			$res = $DB->Query($strSql, false, "File: " . __FILE__ . "<br>Line: " . __LINE__);
			$res->SetUserFields($USER_FIELD_MANAGER->GetUserFields("TASKS_TASK_TEMPLATE"));
		}

		return ($res);
	}


	public static function GetCount($includeSubtemplates = false)
	{
		global $DB, $USER;

		if (intval($USER->GetID()))
		{
			$strSql = "
				SELECT COUNT(*) AS CNT 
					FROM b_tasks_template TT

					".(!$includeSubtemplates ? "
					LEFT JOIN
						".Template\DependencyTable::getTableName()." TDD ON TT.ID = TDD.TEMPLATE_ID AND TDD.DIRECT = '1'"
					: "")."

				WHERE 
					CREATED_BY = ".intval($USER->GetID())."

					".(!$includeSubtemplates ? " AND TDD.".Template\DependencyTable::getPARENTIDColumnName()." IS NULL" : "");

			if ($dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__))
			{
				if ($arRes = $dbRes->Fetch())
				{
					return $arRes["CNT"];
				}
			}
		}

		return 0;
	}


	function GetFilter($arFilter, $arParams)
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
				case "CREATED_BY":
				case "TASK_ID":
				case "TPARAM_TYPE":
				case "ID":
					$arSqlSearch[] = CTasks::FilterCreate("TT.".$key, $val, "number", $bFullJoin, $cOperationType);
					break;

				case "RESPONSIBLE":
					$arSqlSearch[] = CTasks::FilterCreate("TT.RESPONSIBLE_ID", $val, "number", $bFullJoin, $cOperationType);
					break;

				case "TITLE":
					$arSqlSearch[] = CTasks::FilterCreate("TT.".$key, $val, "string", $bFullJoin, $cOperationType);
					break;

				case "PRIORITY":
					$arSqlSearch[] = CTasks::FilterCreate("TT.".$key, $val, "string_equal", $bFullJoin, $cOperationType);
					break;

				/*
				case "TEMPLATE_CHILDREN_COUNT":
					$arSqlSearch[] = CTasks::FilterCreate($key, $val, "number", $bFullJoin, $cOperationType);
					break;
				*/

				case "BASE_TEMPLATE_ID":

					$parentColumnName = 	Template\DependencyTable::getPARENTIDColumnName();
					$columnName = 			Template\DependencyTable::getIDColumnName();
					$cOperationType = 		'I'; // force to "identical" for this field, in any case

					$val = (string) $val;
					if($val === '' || $val === '0')
						$val = false;

					//$includeSubtree = 		$arParams['INCLUDE_TEMPLATE_SUBTREE'] === true || $arParams['INCLUDE_TEMPLATE_SUBTREE'] === 'Y';
					$excludeSubtree = 	$arParams['EXCLUDE_TEMPLATE_SUBTREE'] === true || $arParams['EXCLUDE_TEMPLATE_SUBTREE'] === 'Y';

					if($excludeSubtree)
					{
						$arSqlSearch[] = "TT.ID NOT IN (SELECT ".$columnName." FROM ".Template\DependencyTable::getTableName()." WHERE ".$parentColumnName." = '".intval($val)."')";
					}
					else
					{
						$arSqlSearch[] = '('.($val ? "TD.".$parentColumnName." = '".intval($val)."'" : "TD.".$parentColumnName." = '0' OR TD.".$parentColumnName." IS NULL").')';
						//$arSqlSearch[] = CTasks::FilterCreate("TD.".Template\DependencyTable::getPARENTIDColumnName(), $val, "number", $bFullJoin, $cOperationType);
					}

					break;
			}
		}

		return $arSqlSearch;
	}


	function GetByID($ID, $arParams = array())
	{
		if(!intval($ID))
		{
			$dbResult = new CDBResult();
			$dbResult->InitFromArray(array());
			return $dbResult;
		}

		return CTaskTemplates::GetList(Array(), Array("ID" => $ID), array(), $arParams, array('*', 'UF_*'));
	}
}