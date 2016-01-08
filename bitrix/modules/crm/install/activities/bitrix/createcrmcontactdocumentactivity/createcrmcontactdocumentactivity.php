<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

$runtime = CBPRuntime::GetRuntime();
$runtime->IncludeActivityFile('CreateDocumentActivity');

class CBPCreateCrmContactDocumentActivity
	extends CBPCreateDocumentActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties["ContactId"] = 0;
	}

	public function Execute()
	{
		if (!CModule::IncludeModule('crm'))
			CBPActivityExecutionStatus::Closed;

		$rootActivity = $this->GetRootActivity();

		$documentId = array();
		$documentId[0] = 'crm';
		$documentId[1] = 'CCrmDocumentContact';
		$documentId[2] = 'CONTACT';

		$documentService = $this->workflow->GetService('DocumentService');
		$this->ContactId = $documentService->CreateDocument($documentId, $this->Fields);

		return CBPActivityExecutionStatus::Closed;
	}

	public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
	{
		if (!CModule::IncludeModule('crm'))
		{
			return(array('code' => 'NotLoaded', 'module'=> 'crm', 'message'=> GetMessage('BPCDA_MODULE_NOT_LOADED')));
		};

		$arErrors = array();

		$arDocumentFields = CCrmDocumentContact::GetDocumentFields('CONTACT');
		$arTestFields = isset($arTestProperties['Fields']) && is_array($arTestProperties['Fields']) ? $arTestProperties['Fields'] : array();

		$name = isset($arTestFields['NAME']) ? $arTestFields['NAME'] : '';
		if($name === '')
		{
			$arErrors[] = array('code' => 'NotExist', 'parameter' => 'NAME', 'message' => GetMessage('BPCDA_FIELD_NOT_FOUND', array('#NAME#' => $arDocumentFields['NAME']['Name'])));
		}

		$lastName = isset($arTestFields['LAST_NAME']) ? $arTestFields['LAST_NAME'] : '';
		if($lastName === '')
		{
			$arErrors[] = array('code' => 'NotExist', 'parameter' => 'LAST_NAME', 'message' => GetMessage('BPCDA_FIELD_NOT_FOUND', array('#NAME#' => $arDocumentFields['LAST_NAME']['Name'])));
		}

		return array_merge($arErrors, parent::ValidateProperties($arTestProperties, $user));
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "", $popupWindow = null)
	{
		$documentType[0] = 'crm';
		$documentType[1] = 'CCrmDocumentContact';
		$documentType[2] = 'CONTACT';
		return parent::GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues, $formName, $popupWindow);
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$arErrors)
	{
		$documentType[0] = 'crm';
		$documentType[1] = 'CCrmDocumentContact';
		$documentType[2] = 'CONTACT';
		return parent::GetPropertiesDialogValues($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues, $arErrors);
	}
}
?>