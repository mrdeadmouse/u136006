<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CBPCreateWorkGroup
	extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array(
			"GroupName" => "",
			"OwnerId" => "",
			'Users' => "",
			"GroupId" => null
		);
	}

	public function Execute()
	{
		if (!CModule::IncludeModule("socialnetwork"))
			return CBPActivityExecutionStatus::Closed;

		$rootActivity = $this->GetRootActivity();
		$documentId = $rootActivity->GetDocumentId();

		$ownerId = CBPHelper::ExtractUsers($this->OwnerId, $documentId, true);
		$users = array_unique(CBPHelper::ExtractUsers($this->Users, $documentId, false));

		$dbSubjects = CSocNetGroupSubject::GetList(
			array("SORT"=>"ASC", "NAME" => "ASC"),
			array("SITE_ID" => SITE_ID),
			false,
			false,
			array("ID")
		);
		$row = $dbSubjects->fetch();
		if (!$row)
		{
			$this->WriteToTrackingService(GetMessage("BPCWG_ERROR_SUBJECT_ID"));
			return CBPActivityExecutionStatus::Closed;
		}

		$subjectId = $row['ID'];
		unset($dbSubjects, $row);

		$options = array(
			"SITE_ID" => SITE_ID,
			"NAME" => $this->GroupName,
			"VISIBLE" => "Y",
			"OPENED" => "N",
			"CLOSED" => "N",
			"SUBJECT_ID" => $subjectId,
			"INITIATE_PERMS" => SONET_ROLES_OWNER,
			"SPAM_PERMS" => SONET_ROLES_USER,
		);

		$groupId = CSocNetGroup::CreateGroup($ownerId, $options);
		if (!$groupId)
		{
			$this->WriteToTrackingService(GetMessage("BPCWG_ERROR_CREATE_GROUP"));
			return CBPActivityExecutionStatus::Closed;
		}

		$this->GroupId = $groupId;

		foreach ($users AS $user)
		{
			if ($user == $ownerId)
				continue;
			CSocNetUserToGroup::Add(
				array(
					"USER_ID" => $user,
					"GROUP_ID" => $groupId,
					"ROLE" => SONET_ROLES_USER,
					"=DATE_CREATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
					"=DATE_UPDATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
					"INITIATED_BY_TYPE" => SONET_INITIATED_BY_GROUP,
					"INITIATED_BY_USER_ID" => $ownerId,
					"MESSAGE" => false,
				)
			);
		}

		return CBPActivityExecutionStatus::Closed;
	}

	public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
	{
		$arErrors = array();
		if (!array_key_exists("GroupName", $arTestProperties) || strlen($arTestProperties["GroupName"]) <= 0)
			$arErrors[] = array("code" => "NotExist", "parameter" => "GroupName", "message" => GetMessage("BPCWG_EMPTY_GROUP_NAME"));
		if (!array_key_exists("OwnerId", $arTestProperties) || count($arTestProperties["OwnerId"]) <= 0)
			$arErrors[] = array("code" => "NotExist", "parameter" => "OwnerId", "message" => GetMessage("BPCWG_EMPTY_OWNER"));
		if (!array_key_exists("Users", $arTestProperties) || count($arTestProperties["Users"]) <= 0)
			$arErrors[] = array("code" => "NotExist", "parameter" => "Users", "message" => GetMessage("BPCWG_EMPTY_USERS"));

		return array_merge($arErrors, parent::ValidateProperties($arTestProperties, $user));
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "")
	{
		$runtime = CBPRuntime::GetRuntime();

		$arMap = array(
			"GroupName" => "group_name",
			"OwnerId" => "owner_id",
			"Users" => 'users'
		);

		if (!is_array($arCurrentValues))
		{
			$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
			if (is_array($arCurrentActivity["Properties"]))
			{
				foreach ($arMap as $k => $v)
				{
					if (array_key_exists($k, $arCurrentActivity["Properties"]))
					{
						if ($k == "OwnerId" || $k == "Users")
							$arCurrentValues[$arMap[$k]] = CBPHelper::UsersArrayToString($arCurrentActivity["Properties"][$k], $arWorkflowTemplate, $documentType);
						else
							$arCurrentValues[$arMap[$k]] = $arCurrentActivity["Properties"][$k];
					}
					else
					{
						$arCurrentValues[$arMap[$k]] = "";
					}
				}
			}
			else
			{
				foreach ($arMap as $k => $v)
					$arCurrentValues[$arMap[$k]] = "";
			}
		}

		return $runtime->ExecuteResourceFile(
			__FILE__,
			"properties_dialog.php",
			array(
				"arCurrentValues" => $arCurrentValues,
				"formName" => $formName,
			)
		);
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$arErrors)
	{
		$arErrors = array();

		$arMap = array(
			"group_name" => "GroupName",
			"owner_id" => "OwnerId",
			"users" => "Users"
		);

		$arProperties = array();
		foreach ($arMap as $key => $value)
		{
			if ($key == "owner_id" || $key == "users")
				continue;
			$arProperties[$value] = $arCurrentValues[$key];
		}

		$arProperties["OwnerId"] = CBPHelper::UsersStringToArray($arCurrentValues["owner_id"], $documentType, $arErrors);
		if (count($arErrors) > 0)
			return false;

		$arProperties["Users"] = CBPHelper::UsersStringToArray($arCurrentValues["users"], $documentType, $arErrors);
		if (count($arErrors) > 0)
			return false;

		$arErrors = self::ValidateProperties($arProperties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
		if (count($arErrors) > 0)
			return false;

		$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$arCurrentActivity["Properties"] = $arProperties;

		return true;
	}
}
?>