<?
IncludeModuleLangFile(__FILE__);

/**
* ����� �������� ����������� ������-������� ��� �������� ������������� API ������ ������-��������� ��� ����� ������.
*/
class CBPDocument
{
	public static function MigrateDocumentType($oldType, $newType)
	{
		$templateIds = array();
		$db = CBPWorkflowTemplateLoader::GetList(array(), array("DOCUMENT_TYPE" => $oldType), false, false, array("ID"));
		while ($ar = $db->Fetch())
			$templateIds[] = $ar["ID"];

		foreach ($templateIds as $id)
			CBPWorkflowTemplateLoader::Update($id, array("DOCUMENT_TYPE" => $newType));

		if (count($templateIds) > 0)
		{
			CBPHistoryService::MigrateDocumentType($oldType, $newType, $templateIds);
			CBPStateService::MigrateDocumentType($oldType, $newType, $templateIds);
		}
	}

	/**
	* ����� ���������� ������ ���� ������� ������� � �� ��������� ��� ������� ���������.
	* ���� ����� ��� ���������, �� ����� ���������� ������ ���� ���������� ��� ������� ��������� ������� ������� (� ��� ����� � �����������), � ��� �� �������� ������� �������, ����������� �� ���������� ��� ��������� ���������.
	* ���� ��� ��������� �� �����, �� ����� ���������� ������ �������� ������� �������, ����������� �� ���������� ��� �������� ���������.
	* ������ ����� ���:
	*	array(
	*		���_��������_������_���_������� => array(
	*			"ID" => ���_��������_������,
	*			"TEMPLATE_ID" => ���_�������_��������_������,
	*			"TEMPLATE_NAME" => ��������_�������_��������_������,
	*			"TEMPLATE_DESCRIPTION" => ��������_�������_��������_������,
	*			"TEMPLATE_PARAMETERS" => ������_����������_�������_��������_������_��_�������,
	*			"STATE_NAME" => �������_���������_��������_������,
	*			"STATE_TITLE" => ��������_��������_���������_��������_������,
	*			"STATE_MODIFIED" => ����_���������_�������_��������_������,
	*			"STATE_PARAMETERS" => ������_�������_�����������_�������_�_������_���������,
	*			"STATE_PERMISSIONS" => �����_��_��������_���_����������_�_������_���������,
	*			"WORKFLOW_STATUS" => ������_��������_������,
	*		),
	* 		. . .
	*	)
	* � ����������� �� ����, ������� ����� ��� ��� ������, ����� ����� ����� ���� �� �����������. ��� ������� �������� ������ ���� �������� ��������� ���������� �������� ��� ��������� ���������.
	* ������ ���������� ������� �������� ������ �� ������� (TEMPLATE_PARAMETERS) ����� ���:
	*	array(
	*		"param1" => array(
	*			"Name" => "�������� 1",
	*			"Description" => "",
	*			"Type" => "int",
	*			"Required" => true,
	*			"Multiple" => false,
	*			"Default" => 8,
	*			"Options" => null,
	*		),
	*		"param2" => array(
	*			"Name" => "�������� 2",
	*			"Description" => "",
	*			"Type" => "select",
	*			"Required" => false,
	*			"Multiple" => true,
	*			"Default" => "v2",
	*			"Options" => array(
	*				"v1" => "V 1",
	*				"v2" => "V 2",
	*				"v3" => "V 3",
	*				. . .
	*			),
	*		),
	*		. . .
	*	)
	* ���������� ���� ����������: int, double, string, text, select, bool, date, datetime, user.
	* ������ �������, ����������� ������� � ������ ��������� (STATE_PARAMETERS) ����� ���:
	*	array(
	*		array(
	*			"NAME" => �����������_�������,
	*			"TITLE" => ��������_������������_�������,
	*			"PERMISSION" => ������_�����_�������������_�������_���������_�������
	*		),
	*		. . .
	*	)
	* ����� �� �������� ��� ���������� � ������ ��������� (STATE_PERMISSIONS) ����� ���:
	*	array(
	*		�������� => ������_�����_�������������_�������_������������_��������,
	*		. . .
	*	)
	*
	* @param array $documentType - ��� ��������� � ���� ������� array(������, ��������, ���_���������_�_������)
	* @param mixed $documentId - ��� ��������� � ���� ������� array(������, ��������, ���_���������_�_������). ���� ����� ��������, �� null.
	* @return array - ������ ������� ������� � ��������.
	*/
	public static function GetDocumentStates($documentType, $documentId = null)
	{
		$arDocumentStates = array();

		if ($documentId != null)
			$arDocumentStates = CBPStateService::GetDocumentStates($documentId);

		$arTemplateStates = CBPWorkflowTemplateLoader::GetDocumentTypeStates(
			$documentType,
			(($documentId != null) ? CBPDocumentEventType::Edit : CBPDocumentEventType::Create)
		);

		return ($arDocumentStates + $arTemplateStates);
	}

	/**
	* ����� ��� ������� ��������� ���������� ��������� ���������� �������� ������. �������������� ������ ���������� ������� ������ GetDocumentStates.
	*
	* @param array $documentId - ��� ��������� � ���� ������� array(������, ��������, ���_���������_�_������).
	* @param string $workflowId - ��� �������� ������.
	* @return array - ������ �������� ������.
	*/
	public static function GetDocumentState($documentId, $workflowId)
	{
		$arDocumentState = CBPStateService::GetDocumentStates($documentId, $workflowId);
		return $arDocumentState;
	}

	public static function MergeDocuments($firstDocumentId, $secondDocumentId)
	{
		CBPStateService::MergeStates($firstDocumentId, $secondDocumentId);
		CBPHistoryService::MergeHistory($firstDocumentId, $secondDocumentId);
	}

	/**
	* ����� ���������� ������ �������, ������� ��������� ������������ ����� ��������� �������� ������ � ��������� ���������.
	*
	* @param int $userId - ��� ������������.
	* @param array $arGroups - ������ ����� ������������.
	* @param array $arState - ��������� �������� ������.
	* @param bool $extendedGroups.
	* @return array - ������ ������� ���� array(array("NAME" => �������, "TITLE" => ��������_�������), ...).
	*/
	public static function GetAllowableEvents($userId, $arGroups, $arState, $extendedGroups = false)
	{
		if (!is_array($arState))
			throw new Exception("arState");
		if (!is_array($arGroups))
			throw new Exception("arGroups");

		if (!$extendedGroups && !in_array("user_".$userId, $arGroups))
			$arGroups[] = "user_".$userId;
		if ($extendedGroups && !in_array("group_u".$userId, $arGroups))
			$arGroups[] = "group_u".$userId;

		if ($extendedGroups)
		{
			$arGroups = array_merge($arGroups, CBPHelper::getUserExtendedGroups($userId));
		}

		$arGroups = array_map('strtolower', $arGroups);
		$arResult = array();


		if (is_array($arState["STATE_PARAMETERS"]) && count($arState["STATE_PARAMETERS"]) > 0)
		{
			foreach ($arState["STATE_PARAMETERS"] as $arStateParameter)
			{
				$arStateParameter["PERMISSION"] = $extendedGroups?
					CBPHelper::convertToExtendedGroups($arStateParameter["PERMISSION"])
					: CBPHelper::convertToSimpleGroups($arStateParameter["PERMISSION"], true);
				$arStateParameter["PERMISSION"] = array_map('strtolower', $arStateParameter["PERMISSION"]);

				if (count($arStateParameter["PERMISSION"]) <= 0
					|| count(array_intersect($arGroups, $arStateParameter["PERMISSION"])) > 0)
				{
					$arResult[] = array(
						"NAME" => $arStateParameter["NAME"],
						"TITLE" => ((strlen($arStateParameter["TITLE"]) > 0) ? $arStateParameter["TITLE"] : $arStateParameter["NAME"]),
					);
				}
			}
		}

		return $arResult;
	}

	public static function AddDocumentToHistory($parameterDocumentId, $name, $userId)
	{
		list($moduleId, $entity, $documentType) = CBPHelper::ParseDocumentId($parameterDocumentId);

		if (strlen($moduleId) > 0)
			CModule::IncludeModule($moduleId);

		if (!class_exists($entity))
			return false;

		$runtime = CBPRuntime::GetRuntime();
		$runtime->StartRuntime();

		$historyService = $runtime->GetService("HistoryService");
		$documentService = $runtime->GetService("DocumentService");

		$userId = intval($userId);

		$historyIndex = $historyService->AddHistory(
			array(
				"DOCUMENT_ID" => $parameterDocumentId,
				"NAME" => "New",
				"DOCUMENT" => null,
				"USER_ID" => $userId,
			)
		);

		$arDocument = $documentService->GetDocumentForHistory($parameterDocumentId, $historyIndex);
		if (!is_array($arDocument))
			return false;

		$historyService->UpdateHistory(
			$historyIndex,
			array(
				"NAME" => $name,
				"DOCUMENT" => $arDocument,
			)
		);

		return $historyIndex;
	}

	/**
	* ����� ���������� ������ ��������, ������� ��������� ������������ ����� ���������, ���� �������� ��������� � ��������� ����������.
	* ���� ����� ��������� ��� �� ������ �������� ������ ���� �������� ���������, �� ������������ null.
	* ���� ������������ �� ����� ��������� �� ����� ��������, �� ������������ array().
	* ����� ������������ ������ ��������� ��� ������������ �������� � ���� array(��������, ...).
	*
	* @param int $userId - ��� ������������.
	* @param array $arGroups - ������ ����� ������������.
	* @param array $arStates - ������ ��������� ������� ������� ���������.
	* @param bool $extendedGroups - use extended groups mode
	* @return mixed - ������ ��������� �������� ��� null.
	*/
	public static function GetAllowableOperations($userId, $arGroups, $arStates, $extendedGroups = false)
	{
		if (!is_array($arStates))
			throw new Exception("arStates");
		if (!is_array($arGroups))
			throw new Exception("arGroups");

		if (!$extendedGroups && !in_array("user_".$userId, $arGroups))
			$arGroups[] = "user_".$userId;
		if ($extendedGroups && !in_array("group_u".$userId, $arGroups))
			$arGroups[] = "group_u".$userId;

		if ($extendedGroups)
		{
			$arGroups = array_merge($arGroups, CBPHelper::getUserExtendedGroups($userId));
		}
		$arGroups = array_map('strtolower', $arGroups);
		$result = null;

		foreach ($arStates as $arState)
		{
			if (is_array($arState["STATE_PERMISSIONS"]) && count($arState["STATE_PERMISSIONS"]) > 0)
			{
				if ($result == null)
					$result = array();

				foreach ($arState["STATE_PERMISSIONS"] as $operation => $arOperationGroups)
				{
					$arOperationGroups = $extendedGroups?
						CBPHelper::convertToExtendedGroups($arOperationGroups)
						: CBPHelper::convertToSimpleGroups($arOperationGroups, true);
					$arOperationGroups = array_map('strtolower', $arOperationGroups);

					if (count(array_intersect($arGroups, $arOperationGroups)) > 0)
						$result[] = strtolower($operation);


//					foreach ($arOperationGroups as $operationGroup)
//					{
//						if (is_array($operationGroup) && count($operationGroup) == 2
//							|| !is_array($operationGroup) && in_array($operationGroup, $arGroups))
//						{
//							$result[] = strtolower($operation);
//							break;
//						}
//					}
				}
			}
		}

		return $result;
	}

	/**
	* ����� ���������, ����� �� ��������� ������������ ��������� ��������� ��������, ���� �������� ��������� � ��������� ����������.
	* ���� ����� ��������� ��� �� ������ �������� ������ ���� �������� ���������, �� ������������ true.
	* ���� ������������ �� ����� ��������� ��������, �� ������������ false.
	* ����� ������������ true.
	*
	* @param string $operation - ��������.
	* @param int $userId - ��� ������������.
	* @param array $arGroups - ������ ����� ������������.
	* @param array $arStates - ������ ��������� ������� ������� ���������.
	* @return bool
	*/
	public static function CanOperate($operation, $userId, $arGroups, $arStates)
	{
		$operation = trim($operation);
		if (strlen($operation) <= 0)
			throw new Exception("operation");

		$operations = self::GetAllowableOperations($userId, $arGroups, $arStates);
		if ($operations === null)
			return true;

		return in_array($operation, $operations);
	}

	/**
	* ����� ��������� ������� ����� �� ���� ��� �������.
	*
	* @param int $workflowTemplateId - ��� ������� �������� ������.
	* @param array $documentId - ��� ��������� � ���� ������� array(������, ��������, ���_���������_�_������).
	* @param array $arParameters - ������ ���������� ������� �������� ������.
	* @param array $arErrors - ������ ������, ������� ��������� ��� ������� �������� ������ � ���� array(array("code" => ���_������, "message" => ���������, "file" => ����_�_�����), ...).
	* @return string - ��� ����������� �������� ������.
	*/
	public static function StartWorkflow($workflowTemplateId, $documentId, $arParameters, &$arErrors)
	{
		$arErrors = array();

		$runtime = CBPRuntime::GetRuntime();

		if (!is_array($arParameters))
			$arParameters = array($arParameters);
		if (!array_key_exists("TargetUser", $arParameters))
			$arParameters["TargetUser"] = "user_".intval($GLOBALS["USER"]->GetID());

		try
		{
			$wi = $runtime->CreateWorkflow($workflowTemplateId, $documentId, $arParameters);
			$wi->Start();
			return $wi->GetInstanceId();
		}
		catch (Exception $e)
		{
			$arErrors[] = array(
				"code" => $e->getCode(),
				"message" => $e->getMessage(),
				"file" => $e->getFile()." [".$e->getLine()."]"
			);
		}

		return null;
	}

	/**
	* ����� ��������� ������� ������, ����������� �� ����������.
	*
	* @param array $documentType - ��� ���� ��������� � ���� ������� array(������, ��������, ���_����_���������_�_������).
	* @param int $autoExecute - ���� CBPDocumentEventType ���� ����������� (1 = CBPDocumentEventType::Create, 2 = CBPDocumentEventType::Edit).
	* @param array $documentId - ��� ��������� � ���� ������� array(������, ��������, ���_���������_�_������).
	* @param array $arParameters - ������ ���������� ������� �������� ������.
	* @param array $arErrors - ������ ������, ������� ��������� ��� ������� �������� ������ � ���� array(array("code" => ���_������, "message" => ���������, "file" => ����_�_�����), ...).
	*/
	public static function AutoStartWorkflows($documentType, $autoExecute, $documentId, $arParameters, &$arErrors)
	{
		$arErrors = array();

		$runtime = CBPRuntime::GetRuntime();

		if (!is_array($arParameters))
			$arParameters = array($arParameters);
		if (!array_key_exists("TargetUser", $arParameters))
			$arParameters["TargetUser"] =  "user_".intval($GLOBALS["USER"]->GetID());

		$arWT = CBPWorkflowTemplateLoader::SearchTemplatesByDocumentType($documentType, $autoExecute);
		foreach ($arWT as $wt)
		{
			try
			{
				$wi = $runtime->CreateWorkflow($wt["ID"], $documentId, $arParameters);
				$wi->Start();
			}
			catch (Exception $e)
			{
				$arErrors[] = array(
					"code" => $e->getCode(),
					"message" => $e->getMessage(),
					"file" => $e->getFile()." [".$e->getLine()."]"
				);
			}
		}
	}

	/**
	* ����� ���������� ������� �������� ������.
	*
	* @param string $workflowId - ��� �������� ������.
	* @param string $workflowEvent - �������.
	* @param array $arParameters - ��������� �������.
	* @param array $arErrors - ������ ������, ������� ��������� ��� �������� ������� � ���� array(array("code" => ���_������, "message" => ���������, "file" => ����_�_�����), ...).
	*/
	public static function SendExternalEvent($workflowId, $workflowEvent, $arParameters, &$arErrors)
	{
		$arErrors = array();

		try
		{
			CBPRuntime::SendExternalEvent($workflowId, $workflowEvent, $arParameters);
		}
		catch(Exception $e)
		{
			$arErrors[] = array(
				"code" => $e->getCode(),
				"message" => $e->getMessage(),
				"file" => $e->getFile()." [".$e->getLine()."]"
			);
		}
	}

	/**
	* ����� ������������� ���������� �������� ������.
	*
	* @param string $workflowId - ��� �������� ������.
	* @param array $documentId - ��� ��������� � ���� ������� array(������, ��������, ���_���������_�_������).
	* @param array $arErrors - ������ ������, ������� ��������� ��� ��������� �������� ������ � ���� array(array("code" => ���_������, "message" => ���������, "file" => ����_�_�����), ...).
	*/
	public static function TerminateWorkflow($workflowId, $documentId, &$arErrors)
	{
		$arErrors = array();

		$runtime = CBPRuntime::GetRuntime();

		try
		{
			$workflow = $runtime->GetWorkflow($workflowId);

			$d = $workflow->GetDocumentId();
			if ($d[0] != $documentId[0] || $d[1] != $documentId[1] || $d[2] != $documentId[2])
				throw new Exception(GetMessage("BPCGDOC_INVALID_WF"));

			$workflow->Terminate(null);
		}
		catch(Exception $e)
		{
			$arErrors[] = array(
				"code" => $e->getCode(),
				"message" => $e->getMessage(),
				"file" => $e->getFile()." [".$e->getLine()."]"
			);
		}
	}

	/**
	* ����� ������� ��� ��������� � ���������� ������.
	*
	* @param array $documentId - ��� ��������� � ���� ������� array(������, ��������, ���_���������_�_������).
	* @param array $arErrors - ������ ������, ������� ��������� ��� �������� � ���� array(array("code" => ���_������, "message" => ���������, "file" => ����_�_�����), ...).
	*/
	public static function OnDocumentDelete($documentId, &$arErrors)
	{
		$arErrors = array();

		$arStates = CBPStateService::GetDocumentStates($documentId);
		foreach ($arStates as $workflowId => $arState)
		{
			if (strlen($arState["ID"]) > 0 && strlen($arState["WORKFLOW_STATUS"]) > 0)
				self::TerminateWorkflow($workflowId, $documentId, $arErrors);

			CBPTrackingService::DeleteByWorkflow($workflowId);
			CBPTaskService::DeleteByWorkflow($workflowId);
		}

		CBPStateService::DeleteByDocument($documentId);
		CBPHistoryService::DeleteByDocument($documentId);
	}

	public static function PostTaskForm($arTask, $userId, $arRequest, &$arErrors, $userName = "")
	{
		$originalUserId = CBPTaskService::getOriginalTaskUserId($arTask['ID'], $userId);

		return CBPActivity::CallStaticMethod(
			$arTask["ACTIVITY"],
			"PostTaskForm",
			array(
				$arTask,
				$originalUserId,
				$arRequest,
				&$arErrors,
				$userName,
				$userId
			)
		);
	}

	public static function ShowTaskForm($arTask, $userId, $userName = "", $arRequest = null)
	{
		return CBPActivity::CallStaticMethod(
			$arTask["ACTIVITY"],
			"ShowTaskForm",
			array(
				$arTask,
				$userId,
				$userName,
				$arRequest
			)
		);
	}


	public static function setTasksUserStatus($userId, $status, $ids = array(), &$errors = array())
	{
		$filter = array(
			'USER_ID' => $userId,
			'STATUS' => CBPTaskStatus::Running,
			'USER_STATUS' => CBPTaskUserStatus::Waiting,
		);
		if ($ids && is_array($ids))
		{
			$ids = array_filter(array_map('intval', $ids));
			if ($ids)
				$filter['ID'] = $ids;
		}

		$iterator = CBPTaskService::GetList(array('ID'=>'ASC'),
			$filter,
			false,
			false,
			array('ID', 'NAME', 'WORKFLOW_ID', 'ACTIVITY', 'ACTIVITY_NAME', 'IS_INLINE'));
		while ($task = $iterator->fetch())
		{
			if ($task['IS_INLINE'] == 'Y')
			{
				$taskErrors = array();
				self::PostTaskForm($task, $userId, array('INLINE_USER_STATUS' => $status), $taskErrors);
				if (!empty($taskErrors))
					foreach ($taskErrors as $error)
						$errors[] = GetMessage('BPCGDOC_ERROR_ACTION', array('#NAME#' => $task['NAME'], '#ERROR#' => $error['message']));
			}
			else
				$errors[] = GetMessage('BPCGDOC_ERROR_TASK_IS_NOT_INLINE', array('#NAME#' => $task['NAME']));

		}
		return true;
	}

	public static function delegateTasks($fromUserId, $toUserId, $ids = array(), &$errors = array())
	{
		$filter = array(
			'USER_ID' => $fromUserId,
			'STATUS' => CBPTaskStatus::Running,
			'USER_STATUS' => CBPTaskUserStatus::Waiting
		);
		if ($ids && is_array($ids))
		{
			$ids = array_filter(array_map('intval', $ids));
			if ($ids)
				$filter['ID'] = $ids;
		}

		$iterator = CBPTaskService::GetList(array('ID'=>'ASC'), $filter, false, false, array('ID', 'NAME'));
		while ($task = $iterator->fetch())
		{
			if (!CBPTaskService::delegateTask($task['ID'], $fromUserId, $toUserId))
			{
				$errors[] = GetMessage('BPCGDOC_ERROR_DELEGATE', array('#NAME#' => $task['NAME']));
			}
		}
		return true;
	}

	public static function getTaskControls($arTask)
	{
		return CBPActivity::CallStaticMethod(
			$arTask["ACTIVITY"],
			"getTaskControls",
			array(
				$arTask
			)
		);
	}

	/**
	* ����� �������� � ��������� �������� ���������� ������� �������� ������, �������� � ����� ������ StartWorkflowParametersShow.
	*
	* @param int $templateId - ��� ������� �������� ������.
	* @param array $arWorkflowParameters - ������ ���������� ������� �������� ������.
	* @param array $arErrors - ������ ������, ������� ��������� ��� ���������� � ���� array(array("code" => ���_������, "message" => ���������, "parameter" => ��������_���������, "file" => ����_�_�����), ...).
	* @return array - ������ ���������� �������� ���������� ������� �������� ������ � ���� array(���_��������� => ��������, ...)
	*/
	public static function StartWorkflowParametersValidate($templateId, $arWorkflowParameters, $documentType, &$arErrors)
	{
		$arErrors = array();

		$templateId = intval($templateId);
		if ($templateId <= 0)
		{
			$arErrors[] = array(
				"code" => "",
				"message" => GetMessage("BPCGDOC_EMPTY_WD_ID"),
			);
			return array();
		}

		if (!isset($arWorkflowParameters) || !is_array($arWorkflowParameters))
			$arWorkflowParameters = array();

		$arWorkflowParametersValues = array();

		$arRequest = $_REQUEST;
		foreach ($_FILES as $k => $v)
		{
			if (array_key_exists("name", $v))
			{
				if (is_array($v["name"]))
				{
					$ks = array_keys($v["name"]);
					for ($i = 0, $cnt = count($ks); $i < $cnt; $i++)
					{
						$ar = array();
						foreach ($v as $k1 => $v1)
							$ar[$k1] = $v1[$ks[$i]];

						$arRequest[$k][] = $ar;
					}
				}
				else
				{
					$arRequest[$k] = $v;
				}
			}
		}

		if (count($arWorkflowParameters) > 0)
		{
			$arErrorsTmp = array();
			$ar = array();

			foreach ($arWorkflowParameters as $parameterKey => $arParameter)
				$ar[$parameterKey] = $arRequest["bizproc".$templateId."_".$parameterKey];

			$arWorkflowParametersValues = CBPWorkflowTemplateLoader::CheckWorkflowParameters(
				$arWorkflowParameters,
				$ar,
				$documentType,
				$arErrors
			);
		}

		return $arWorkflowParametersValues;
	}

	/**
	* ����� ������� ����� ����� �������� ���������� ������� �������� ������. ����������� � ���������� �������� ������� StartWorkflowParametersValidate.
	*
	* @param int $templateId - ��� ������� �������� ������.
	* @param array $arWorkflowParameters - ������ ���������� ������� �������� ������.
	* @param string $formName - �������� �����, � ������� ��������� ����� ����� ��������.
	* @param bool $bVarsFromForm - ����� false � ������ ������� �������� �����, ����� - true.
	*/
	public static function StartWorkflowParametersShow($templateId, $arWorkflowParameters, $formName, $bVarsFromForm, $documentType = null)
	{
		$templateId = intval($templateId);
		if ($templateId <= 0)
			return;

		if (!isset($arWorkflowParameters) || !is_array($arWorkflowParameters))
			$arWorkflowParameters = array();

		if (strlen($formName) <= 0)
			$formName = "start_workflow_form1";

		if ($documentType == null)
		{
			$dbResult = CBPWorkflowTemplateLoader::GetList(array(), array("ID" => $templateId), false, false, array("ID", "MODULE_ID", "ENTITY", "DOCUMENT_TYPE"));
			if ($arResult = $dbResult->Fetch())
				$documentType = $arResult["DOCUMENT_TYPE"];
		}

		$arParametersValues = array();
		$keys = array_keys($arWorkflowParameters);
		foreach ($keys as $key)
		{
			$v = ($bVarsFromForm ? $_REQUEST["bizproc".$templateId."_".$key] : $arWorkflowParameters[$key]["Default"]);
			if (!is_array($v))
			{
				$arParametersValues[$key] = $v;
			}
			else
			{
				$keys1 = array_keys($v);
				foreach ($keys1 as $key1)
					$arParametersValues[$key][$key1] = $v[$key1];
			}
		}

		$runtime = CBPRuntime::GetRuntime();
		$runtime->StartRuntime();
		$documentService = $runtime->GetService("DocumentService");

		foreach ($arWorkflowParameters as $parameterKey => $arParameter)
		{
			$parameterKeyExt = "bizproc".$templateId."_".$parameterKey;
			?><tr>
				<td align="right" width="40%" valign="top" class="field-name"><?= $arParameter["Required"] ? "<span class=\"required\">*</span> " : ""?><?= htmlspecialcharsbx($arParameter["Name"]) ?>:<?if (strlen($arParameter["Description"]) > 0) echo "<br /><small>".htmlspecialcharsbx($arParameter["Description"])."</small><br />";?></td>
				<td width="60%" valign="top"><?
			echo $documentService->GetFieldInputControl(
				$documentType,
				$arParameter,
				array("Form" => $formName, "Field" => $parameterKeyExt),
				$arParametersValues[$parameterKey],
				false,
				true
			);
			?></td></tr><?
		}
	}

	public static function AddShowParameterInit($module, $type, $document_type, $entity = "")
	{
		CUtil::InitJSCore(array("window", "ajax"));
?>
<script src="/bitrix/js/bizproc/bizproc.js"></script>
<script>
if (typeof window['BPAShowSelector'] === 'undefined')
{
	function BPAShowSelector(id, type, mode, arCurValues)
	{
		<?if($type=="only_users"):?>
		var def_mode = "only_users";
		<?else:?>
		var def_mode = "";
		<?endif?>

		if (!mode)
			mode = def_mode;

		/* if (type == 'xuser')
		{
			BX.Access.Init({other:{disabled:true}});
			BX.Access.ShowForm({
				callback: function (obSelected)
				{
					var result = [];
					for (var provider in obSelected)
					{
						if (obSelected.hasOwnProperty(provider))
						{
							for (var varId in obSelected[provider])
							{
								if (obSelected[provider].hasOwnProperty(varId))
								{
									result.push(BX.Access.GetProviderName(provider) + ' ' + obSelected[provider][varId].name + ' [' + varId + ']');
								}
							}
						}
					}
					if (result)
					{
						var el = BX(id), v = el.value;
						if (v)
							v += '; ';
						el.value = v + result.join('; ');
					}
				}
			});
		}
		else */
		if (mode == "only_users")
		{
			BX.WindowManager.setStartZIndex(1150);
			(new BX.CDialog({
				'content_url': '/bitrix/admin/<?=htmlspecialcharsbx($module)?>_bizproc_selector.php?mode=public&bxpublic=Y&lang=<?=LANGUAGE_ID?>&entity=<?=htmlspecialcharsbx($entity)?>',
				'content_post': {
					'document_type': '<?=CUtil::JSEscape($document_type)?>',
					'fieldName': id,
					'fieldType': type,
					'only_users': 'Y',
					'sessid': '<?= bitrix_sessid() ?>'
				},
				'height': 400,
				'width': 485
			})).Show();
		}
		else
		{
			var workflowTemplateNameCur = workflowTemplateName;
			var workflowTemplateDescriptionCur = workflowTemplateDescription;
			var workflowTemplateAutostartCur = workflowTemplateAutostart;
			var arWorkflowParametersCur = arWorkflowParameters;
			var arWorkflowVariablesCur = arWorkflowVariables;
			var arWorkflowConstantsCur = arWorkflowConstants;
			var arWorkflowTemplateCur = Array(rootActivity.Serialize());

			if (arCurValues)
			{
				if (arCurValues['workflowTemplateName'])
					workflowTemplateNameCur = arCurValues['workflowTemplateName'];
				if (arCurValues['workflowTemplateDescription'])
					workflowTemplateDescriptionCur = arCurValues['workflowTemplateDescription'];
				if (arCurValues['workflowTemplateAutostart'])
					workflowTemplateAutostartCur = arCurValues['workflowTemplateAutostart'];
				if (arCurValues['arWorkflowParameters'])
					arWorkflowParametersCur = arCurValues['arWorkflowParameters'];
				if (arCurValues['arWorkflowVariables'])
					arWorkflowVariablesCur = arCurValues['arWorkflowVariables'];
				if (arCurValues['arWorkflowConstants'])
					arWorkflowConstantsCur = arCurValues['arWorkflowConstants'];
				if (arCurValues['arWorkflowTemplate'])
					arWorkflowTemplateCur = arCurValues['arWorkflowTemplate'];
			}

			var p = {
				'document_type': '<?=CUtil::JSEscape($document_type)?>',
				'fieldName': id,
				'fieldType': type,
				'workflowTemplateName': workflowTemplateNameCur,
				'workflowTemplateDescription': workflowTemplateDescriptionCur,
				'workflowTemplateAutostart': workflowTemplateAutostartCur,
				'sessid': '<?= bitrix_sessid() ?>'
			};

			JSToPHPHidd(p, arWorkflowParametersCur, 'arWorkflowParameters');
			JSToPHPHidd(p, arWorkflowVariablesCur, 'arWorkflowVariables');
			JSToPHPHidd(p, arWorkflowConstantsCur, 'arWorkflowConstants');
			JSToPHPHidd(p, arWorkflowTemplateCur, 'arWorkflowTemplate');

			(new BX.CDialog({
				'content_url': '/bitrix/admin/<?=htmlspecialcharsbx($module)?>_bizproc_selector.php?mode=public&bxpublic=Y&lang=<?=LANGUAGE_ID?>&entity=<?=htmlspecialcharsbx($entity)?>',
				'content_post': p,
				'height': 425,
				'width': 485
			})).Show();
		}
	}
}
</script>
<?
	}

	public static function ShowParameterField($type, $name, $values, $arParams = Array())
	{
		/*
		"string" => "������",
		"text" => "������������� �����",
		"int" => "����� �����",
		"double" => "�����",
		"select" => "������",
		"bool" => "��/���",
		"date" => "����",
		"datetime" => "����/�����",
		"user" => "������������",
		*/
		if(strlen($arParams['id'])>0)
			$id = $arParams['id'];
		else
			$id = md5(uniqid());

		if($type == "text")
		{
			$s = '<table cellpadding="0" cellspacing="0" border="0"><tr><td valign="top"><textarea ';
			$s .= 'rows="'.($arParams['rows']>0?intval($arParams['rows']):5).'" ';
			$s .= 'cols="'.($arParams['cols']>0?intval($arParams['cols']):50).'" ';
			$s .= 'name="'.htmlspecialcharsbx($name).'" ';
			$s .= 'id="'.htmlspecialcharsbx($id).'" ';
			$s .= '>'.htmlspecialcharsbx($values);
			$s .= '</textarea></td>';
			$s .= '<td valign="top" style="padding-left:4px"><input type="button" value="..." onclick="BPAShowSelector(\''.Cutil::JSEscape(htmlspecialcharsbx($id)).'\', \''.Cutil::JSEscape($type).'\');"></td></tr></table>';
		}
		elseif($type == "user")
		{
			$s = '<table cellpadding="0" cellspacing="0" border="0"><tr><td valign="top"><textarea onkeydown="if(event.keyCode==45)BPAShowSelector(\''.Cutil::JSEscape(htmlspecialcharsbx($id)).'\', \''.Cutil::JSEscape($type).'\');" ';
			$s .= 'rows="'.($arParams['rows']>0?intval($arParams['rows']):3).'" ';
			$s .= 'cols="'.($arParams['cols']>0?intval($arParams['cols']):45).'" ';
			$s .= 'name="'.htmlspecialcharsbx($name).'" ';
			$s .= 'id="'.htmlspecialcharsbx($id).'">'.htmlspecialcharsbx($values).'</textarea>';
			$s .= '</td><td valign="top" style="padding-left:4px"><input type="button" value="..." title="'.GetMessage("BIZPROC_AS_SEL_FIELD_BUTTON").' (Insert)'.'" onclick="BPAShowSelector(\''.Cutil::JSEscape(htmlspecialcharsbx($id)).'\', \''.Cutil::JSEscape($type).'\');"></td></tr></table>';
		}
		/* elseif($type == "xuser")
		{
			$s = '<table cellpadding="0" cellspacing="0" border="0"><tr><td valign="top"><textarea onkeydown="if(event.keyCode==45)BPAShowSelector(\''.Cutil::JSEscape(htmlspecialcharsbx($id)).'\', \''.Cutil::JSEscape($type).'\');" ';
			$s .= 'rows="'.($arParams['rows']>0?intval($arParams['rows']):3).'" ';
			$s .= 'cols="'.($arParams['cols']>0?intval($arParams['cols']):45).'" ';
			$s .= 'name="'.htmlspecialcharsbx($name).'" ';
			$s .= 'id="'.htmlspecialcharsbx($id).'">'.htmlspecialcharsbx($values).'</textarea>';
			$s .= '</td><td valign="top" style="padding-left:4px"><input type="button" value="..." title="'.GetMessage("BIZPROC_AS_SEL_FIELD_BUTTON").' (Insert)'.'" onclick="BPAShowSelector(\''.Cutil::JSEscape(htmlspecialcharsbx($id)).'\', \'user\');">
			<input type="button" value="[X]" title="'.GetMessage("BIZPROC_AS_SEL_FIELD_BUTTON").' (Insert)'.'" onclick="BPAShowSelector(\''.Cutil::JSEscape(htmlspecialcharsbx($id)).'\', \''.Cutil::JSEscape($type).'\');"></td></tr></table>';
		} */
		elseif($type == "bool")
		{
			$s = '<select name="'.htmlspecialcharsbx($name).'"><option value=""></option><option value="Y"'.($values=='Y'?' selected':'').'>'.GetMessage('MAIN_YES').'</option><option value="N"'.($values=='N'?' selected':'').'>'.GetMessage('MAIN_NO').'</option>';
			$s .= '<input type="text" ';
			$s .= 'size="20" ';
			$s .= 'name="'.htmlspecialcharsbx($name).'_X" ';
			$s .= 'id="'.htmlspecialcharsbx($id).'" ';
			$s .= 'value="'.($values=="Y" || $values=="N"?"":htmlspecialcharsbx($values)).'"> ';
			$s .= '<input type="button" value="..." onclick="BPAShowSelector(\''.Cutil::JSEscape(htmlspecialcharsbx($id)).'\', \''.Cutil::JSEscape($type).'\');">';
		}
		else
		{
			$s = '<input type="text" ';
			$s .= 'size="'.($arParams['size']>0?intval($arParams['size']):70).'" ';
			$s .= 'name="'.htmlspecialcharsbx($name).'" ';
			$s .= 'id="'.htmlspecialcharsbx($id).'" ';
			$s .= 'value="'.htmlspecialcharsbx($values).'"> ';
			$s .= '<input type="button" value="..." onclick="BPAShowSelector(\''.Cutil::JSEscape(htmlspecialcharsbx($id)).'\', \''.Cutil::JSEscape($type).'\');">';
		}

		return $s;
	}

	public static function _ReplaceTaskURL($str, $documentType)
	{
		$chttp = new CHTTP();
		$baseHref = $chttp->URN2URI('');

		return str_replace(
			Array('#HTTP_HOST#', '#TASK_URL#', '#BASE_HREF#'),
			Array($_SERVER['HTTP_HOST'], ($documentType[0]=="iblock"?"/bitrix/admin/bizproc_task.php?workflow_id={=Workflow:id}":"/company/personal/bizproc/{=Workflow:id}/"), $baseHref),
			$str
			);
	}

	public static function AddDefaultWorkflowTemplates($documentType, $additionalModuleId = null)
	{
		if (!empty($additionalModuleId))
		{
			$additionalModuleId = preg_replace("/[^a-z0-9_.]/i", "", $additionalModuleId);
			$arModule = array($additionalModuleId, $documentType[0], 'bizproc');
		}
		else
		{
			$arModule = array($documentType[0], 'bizproc');
		}

		$bIn = false;
		foreach ($arModule as $sModule)
		{
			if (file_exists($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'.$sModule.'/templates'))
			{
				if($handle = opendir($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'.$sModule.'/templates'))
				{
					$bIn = true;
					while(false !== ($file = readdir($handle)))
					{
						if(!is_file($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'.$sModule.'/templates/'.$file))
							continue;
						$arFields = false;
						include($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/'.$sModule.'/templates/'.$file);
						if(is_array($arFields))
						{
							/*
							 * If DOCUMENT_TYPE not defined, use current documentType
							 * Overwise check if DOCUMENT_TYPE equals to current documentType
							 */
							if (!array_key_exists("DOCUMENT_TYPE", $arFields))
								$arFields["DOCUMENT_TYPE"] = $documentType;
							elseif($arFields["DOCUMENT_TYPE"] != $documentType)
								continue;

							$arFields["SYSTEM_CODE"] = $file;
							if(is_object($GLOBALS['USER']))
								$arFields["USER_ID"] = $GLOBALS['USER']->GetID();
							$arFields["MODIFIER_USER"] = new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser);
							try
							{
								CBPWorkflowTemplateLoader::Add($arFields);
							}
							catch (Exception $e)
							{
							}
						}
					}
					closedir($handle);
				}
			}
			if ($bIn)
				break;
		}
	}

	/**
	* ����� ���������� ������ �������� ������� ������� ��� ������� ���� ���������.
	* ������������ ������ ����� ���:
	*	array(
	*		array(
	*			"ID" => ���_�������,
	*			"NAME" => ��������_�������,
	*			"DESCRIPTION" => ��������_�������,
	*			"MODIFIED" => ����_���������_�������,
	*			"USER_ID" => ���_������������_�����������_������,
	*			"USER_NAME" => ���_������������_�����������_������,
	*			"AUTO_EXECUTE" => ����_��������������_CBPDocumentEventType,
	*			"AUTO_EXECUTE_TEXT" => �����_��������������,
	*		),
	*		. . .
	*	)
	*
	* @param array $documentType - ��� ���� ��������� � ���� ������� array(������, ��������, ���_����_���������_�_������).
	* @return array - ������ �������� ������� �������.
	*/
	public static function GetWorkflowTemplatesForDocumentType($documentType)
	{
		$arResult = array();

		$dbWorkflowTemplate = CBPWorkflowTemplateLoader::GetList(
			array(),
			array("DOCUMENT_TYPE" => $documentType, "ACTIVE"=>"Y"),
			false,
			false,
			array("ID", "NAME", "DESCRIPTION", "MODIFIED", "USER_ID", "AUTO_EXECUTE", "USER_NAME", "USER_LAST_NAME", "USER_LOGIN", "USER_SECOND_NAME")
		);
		while ($arWorkflowTemplate = $dbWorkflowTemplate->GetNext())
		{
			$arWorkflowTemplate["USER"] = "(".$arWorkflowTemplate["USER_LOGIN"].")".((strlen($arWorkflowTemplate["USER_NAME"]) > 0 || strlen($arWorkflowTemplate["USER_LAST_NAME"]) > 0) ? " " : "").CUser::FormatName(COption::GetOptionString("bizproc", "name_template", CSite::GetNameFormat(false), SITE_ID), array("NAME" => $arWorkflowTemplate["USER_NAME"], "LAST_NAME" => $arWorkflowTemplate["USER_LAST_NAME"], "SECOND_NAME" => $arWorkflowTemplate["USER_SECOND_NAME"]));

			$arWorkflowTemplate["AUTO_EXECUTE_TEXT"] = "";

			if ($arWorkflowTemplate["AUTO_EXECUTE"] == CBPDocumentEventType::None)
				$arWorkflowTemplate["AUTO_EXECUTE_TEXT"] .= GetMessage("BPCGDOC_AUTO_EXECUTE_NONE");

			if (($arWorkflowTemplate["AUTO_EXECUTE"] & CBPDocumentEventType::Create) != 0)
			{
				if (strlen($arWorkflowTemplate["AUTO_EXECUTE_TEXT"]) > 0)
					$arWorkflowTemplate["AUTO_EXECUTE_TEXT"] .= ", ";
				$arWorkflowTemplate["AUTO_EXECUTE_TEXT"] .= GetMessage("BPCGDOC_AUTO_EXECUTE_CREATE");
			}

			if (($arWorkflowTemplate["AUTO_EXECUTE"] & CBPDocumentEventType::Edit) != 0)
			{
				if (strlen($arWorkflowTemplate["AUTO_EXECUTE_TEXT"]) > 0)
					$arWorkflowTemplate["AUTO_EXECUTE_TEXT"] .= ", ";
				$arWorkflowTemplate["AUTO_EXECUTE_TEXT"] .= GetMessage("BPCGDOC_AUTO_EXECUTE_EDIT");
			}

			if (($arWorkflowTemplate["AUTO_EXECUTE"] & CBPDocumentEventType::Delete) != 0)
			{
				if (strlen($arWorkflowTemplate["AUTO_EXECUTE_TEXT"]) > 0)
					$arWorkflowTemplate["AUTO_EXECUTE_TEXT"] .= ", ";
				$arWorkflowTemplate["AUTO_EXECUTE_TEXT"] .= GetMessage("BPCGDOC_AUTO_EXECUTE_DELETE");
			}

			$arResult[] = $arWorkflowTemplate;
		}

		return $arResult;
	}

	public static function GetNumberOfWorkflowTemplatesForDocumentType($documentType)
	{
		$n = CBPWorkflowTemplateLoader::GetList(
			array(),
			array("DOCUMENT_TYPE" => $documentType, "ACTIVE"=>"Y"),
			array()
		);
		return $n;
	}

	/**
	* ����� ������� ������ �������� ������.
	*
	* @param int $id - ��� ������� �������� ������.
	* @param array $documentType - ��� ���� ��������� � ���� ������� array(������, ��������, ���_����_���������_�_������).
	* @param array $arErrors - ������ ������, ������� ��������� ��� ���������� � ���� array(array("code" => ���_������, "message" => ���������, "file" => ����_�_�����), ...).
	*/
	public static function DeleteWorkflowTemplate($id, $documentType, &$arErrors)
	{
		$arErrors = array();

		$dbTemplates = CBPWorkflowTemplateLoader::GetList(
			array(),
			array("ID" => $id, "DOCUMENT_TYPE" => $documentType),
			false,
			false,
			array("ID")
		);
		$arTemplate = $dbTemplates->Fetch();
		if (!$arTemplate)
		{
			$arErrors[] = array(
				"code" => 0,
				"message" => str_replace("#ID#", $id, GetMessage("BPCGDOC_INVALID_WF_ID")),
				"file" => ""
			);
			return;
		}

		try
		{
			CBPWorkflowTemplateLoader::Delete($id);
		}
		catch (Exception $e)
		{
			$arErrors[] = array(
				"code" => $e->getCode(),
				"message" => $e->getMessage(),
				"file" => $e->getFile()." [".$e->getLine()."]"
			);
		}
	}

	/**
	* ����� �������� ��������� ������� �������� ������.
	*
	* @param int $id - ��� ������� �������� ������.
	* @param array $documentType - ��� ���� ��������� � ���� ������� array(������, ��������, ���_����_���������_�_������).
	* @param array $arFields - ������ ����� �������� ���������� ������� �������� ������.
	* @param array $arErrors - ������ ������, ������� ��������� ��� ���������� � ���� array(array("code" => ���_������, "message" => ���������, "file" => ����_�_�����), ...).
	*/
	public static function UpdateWorkflowTemplate($id, $documentType, $arFields, &$arErrors)
	{
		$arErrors = array();

		$dbTemplates = CBPWorkflowTemplateLoader::GetList(
			array(),
			array("ID" => $id, "DOCUMENT_TYPE" => $documentType),
			false,
			false,
			array("ID")
		);
		$arTemplate = $dbTemplates->Fetch();
		if (!$arTemplate)
		{
			$arErrors[] = array(
				"code" => 0,
				"message" => str_replace("#ID#", $id, GetMessage("BPCGDOC_INVALID_WF_ID")),
				"file" => ""
			);
			return;
		}

		try
		{
			CBPWorkflowTemplateLoader::Update($id, $arFields);
		}
		catch (Exception $e)
		{
			$arErrors[] = array(
				"code" => $e->getCode(),
				"message" => $e->getMessage(),
				"file" => $e->getFile()." [".$e->getLine()."]"
			);
		}
	}

	/**
	* ����� ��������� ����� ��������� � �������� ���������, ����� �� ������������ ��������� ��������� �������� � ����������.
	*
	* @param int $operation - �������� �� CBPCanUserOperateOperation
	* @param int $userId - ��� ������������
	* @param array $parameterDocumentId - ��� ��������� � ���� ������� array(������, ��������, ���_���������_�_������).
	* @param array $arParameters - ������������� ������ ��������������� ����������. ������������ ��� ����, ����� �� ������������ ������ �� ����������� ��������, ������� ��� �������� �� ������ ������ ������. ������������ �������� ����� ������� DocumentStates - ������ ��������� ������� ������� ������� ���������, WorkflowId - ��� �������� ������ (���� ��������� ��������� �������� �� ����� ������� ������). ������ ����� ���� �������� ������� ������������� �������.
	* @return bool
	*/
	public static function CanUserOperateDocument($operation, $userId, $parameterDocumentId, $arParameters = array())
	{
		list($moduleId, $entity, $documentId) = CBPHelper::ParseDocumentId($parameterDocumentId);

		if (strlen($moduleId) > 0)
			CModule::IncludeModule($moduleId);

		if (class_exists($entity))
			return call_user_func_array(array($entity, "CanUserOperateDocument"), array($operation, $userId, $documentId, $arParameters));

		return false;
	}

	/**
	* ����� ��������� ����� ��������� � �������� ���� ���������, ����� �� ������������ ��������� ��������� �������� � ����������� ������� ����.
	*
	* @param int $operation - �������� �� CBPCanUserOperateOperation
	* @param int $userId - ��� ������������
	* @param array $parameterDocumentType - ��� ���� ��������� � ���� ������� array(������, ��������, ���_����_���������_�_������).
	* @param array $arParameters - ������������� ������ ��������������� ����������. ������������ ��� ����, ����� �� ������������ ������ �� ����������� ��������, ������� ��� �������� �� ������ ������ ������. ������������ �������� ����� ������� DocumentStates - ������ ��������� ������� ������� ������� ���������, WorkflowId - ��� �������� ������ (���� ��������� ��������� �������� �� ����� ������� ������). ������ ����� ���� �������� ������� ������������� �������.
	* @return bool
	*/
	public static function CanUserOperateDocumentType($operation, $userId, $parameterDocumentType, $arParameters = array())
	{
		list($moduleId, $entity, $documentType) = CBPHelper::ParseDocumentId($parameterDocumentType);

		if (strlen($moduleId) > 0)
			CModule::IncludeModule($moduleId);

		if (class_exists($entity))
			return call_user_func_array(array($entity, "CanUserOperateDocumentType"), array($operation, $userId, $documentType, $arParameters));

		return false;
	}

	/**
	* ����� �� ���� ��������� ���������� ������ �� �������� ��������� � ���������������� �����.
	*
	* @param array $parameterDocumentId - ��� ��������� � ���� ������� array(������, ��������, ���_���������_�_������).
	* @return string - ������ �� �������� ��������� � ���������������� �����.
	*/
	public static function GetDocumentAdminPage($parameterDocumentId)
	{
		list($moduleId, $entity, $documentId) = CBPHelper::ParseDocumentId($parameterDocumentId);

		if (strlen($moduleId) > 0)
			CModule::IncludeModule($moduleId);

		if (class_exists($entity))
			return call_user_func_array(array($entity, "GetDocumentAdminPage"), array($documentId));

		return "";
	}

	/**
	* ����� ���������� ������ ������� ��� ������� ������������ � ������ ������� ������.
	* ������������ ������ ����� ���:
	*	array(
	*		array(
	*			"ID" => ���_�������,
	*			"NAME" => ��������_�������,
	*			"DESCRIPTION" => ��������_�������,
	*		),
	*		. . .
	*	)
	*
	* @param int $userId - ��� ������������.
	* @param string $workflowId - ��� �������� ������.
	* @return array - ������ �������.
	*/
	public static function GetUserTasksForWorkflow($userId, $workflowId)
	{
		$userId = intval($userId);
		if ($userId <= 0)
			return array();

		$workflowId = trim($workflowId);
		if (strlen($workflowId) <= 0)
			return array();

		$arResult = array();

		$dbTask = CBPTaskService::GetList(
			array(),
			array("WORKFLOW_ID" => $workflowId, "USER_ID" => $userId, 'STATUS' => CBPTaskStatus::Running),
			false,
			false,
			array("ID", "WORKFLOW_ID", "NAME", "DESCRIPTION")
		);
		while ($arTask = $dbTask->GetNext())
			$arResult[] = $arTask;

		return $arResult;
	}

	public static function PrepareFileForHistory($documentId, $fileId, $historyIndex)
	{
		return CBPHistoryService::PrepareFileForHistory($documentId, $fileId, $historyIndex);
	}

	public static function IsAdmin()
	{
		global $APPLICATION;
		return ($APPLICATION->GetGroupRight("bizproc") >= "W");
	}

	public static function GetDocumentFromHistory($historyId, &$arErrors)
	{
		$arErrors = array();

		try
		{
			$historyId = intval($historyId);
			if ($historyId <= 0)
				throw new CBPArgumentNullException("historyId");

			return CBPHistoryService::GetById($historyId);
		}
		catch (Exception $e)
		{
			$arErrors[] = array(
				"code" => $e->getCode(),
				"message" => $e->getMessage(),
				"file" => $e->getFile()." [".$e->getLine()."]"
			);
		}
	}

	public static function GetAllowableUserGroups($parameterDocumentType)
	{
		list($moduleId, $entity, $documentType) = CBPHelper::ParseDocumentId($parameterDocumentType);

		if (strlen($moduleId) > 0)
			CModule::IncludeModule($moduleId);

		if (class_exists($entity))
		{
			$result = call_user_func_array(array($entity, "GetAllowableUserGroups"), array($documentType));
			$result1 = array();
			foreach ($result as $key => $value)
				$result1[strtolower($key)] = $value;
			return $result1;
		}

		return array();
	}

	public static function IsExpression($value)
	{
		return is_string($value) && (preg_match('/^\s*=/', $value) === 1 || preg_match('/^\{=[a-z0-9_]+:[a-z0-9_]+\}$/i', $value) === 1);
	}
}
?>
