<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

class CBPCrmEventAddActivity
	extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array(
			'Title' => '',
			'EventType' => '',
			'EventText' => ''
		);
	}

	public function Execute()
	{
		if (!CModule::IncludeModule('crm'))
			return CBPActivityExecutionStatus::Closed;

		$rootActivity = $this->GetRootActivity();
		$documentId = $rootActivity->GetDocumentId();

		$arDocumentInfo = explode('_', $documentId['2']);

		$documentService = $this->workflow->GetService('DocumentService');

		$arEntity[$arDocumentInfo[1]] = array(
			'ENTITY_TYPE' => $arDocumentInfo[0],
			'ENTITY_ID' => (int) $arDocumentInfo[1]
		);

		$arFields = array(
			'ENTITY'  => $arEntity,
			'EVENT_ID' => $this->EventType,
			'EVENT_TEXT_1' => $this->EventText,
			'USER_ID' => 0,
		);
		$CCrmEvent = new CCrmEvent();
		if (!$CCrmEvent->Add($arFields, false))
		{
			global $APPLICATION;
			$e = $APPLICATION->GetException();
			throw new Exception($e->GetString());
		}

		return CBPActivityExecutionStatus::Closed;
	}

	public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
	{
		$arErrors = array();

		if (!array_key_exists('EventType', $arTestProperties) || strlen($arTestProperties['EventType']) <= 0)
			$arErrors[] = array('code' => 'NotExist', 'parameter' => 'EventType', 'message' => GetMessage('BPEAA_EMPTY_TYPE'));
		if (!array_key_exists('EventText', $arTestProperties) || strlen($arTestProperties['EventText']) <= 0)
			$arErrors[] = array('code' => 'NotExist', 'EventText' => 'MessageText', 'message' => GetMessage('BPEAA_EMPTY_MESSAGE'));

		return array_merge($arErrors, parent::ValidateProperties($arTestProperties, $user));
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = '')
	{
		if (!CModule::IncludeModule('crm'))
			return false;

		$runtime = CBPRuntime::GetRuntime();

		$arMap = array(
			'EventType' => 'event_type',
			'EventText' => 'event_text'
		);

		if (!is_array($arWorkflowParameters))
			$arWorkflowParameters = array();
		if (!is_array($arWorkflowVariables))
			$arWorkflowVariables = array();

		if (!is_array($arCurrentValues))
		{
			$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
			if (is_array($arCurrentActivity['Properties']))
			{
				foreach ($arMap as $k => $v)
				{
					if (array_key_exists($k, $arCurrentActivity['Properties']))
						$arCurrentValues[$arMap[$k]] = $arCurrentActivity['Properties'][$k];
					else
						$arCurrentValues[$arMap[$k]] = '';
				}
			}
			else
			{
				foreach ($arMap as $k => $v)
					$arCurrentValues[$arMap[$k]] = '';
			}
		}

		return $runtime->ExecuteResourceFile(
			__FILE__,
			'properties_dialog.php',
			array(
				'arCurrentValues' => $arCurrentValues,
				'arTypes' => CCrmStatus::GetStatusList('EVENT_TYPE'),
				'formName' => $formName
			)
		);
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$arErrors)
	{
		$arErrors = array();

		$runtime = CBPRuntime::GetRuntime();

		$arMap = array(
			'event_type' => 'EventType',
			'event_text' => 'EventText'
		);

		$arProperties = array();
		foreach ($arMap as $key => $value)
			$arProperties[$value] = $arCurrentValues[$key];

		$arErrors = self::ValidateProperties($arProperties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
		if (count($arErrors) > 0)
			return false;

		$arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$arCurrentActivity['Properties'] = $arProperties;

		return true;
	}
}
?>