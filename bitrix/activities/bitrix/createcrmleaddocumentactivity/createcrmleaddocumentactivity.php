<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

$runtime = CBPRuntime::GetRuntime();
$runtime->IncludeActivityFile('CreateDocumentActivity');

class CBPCreateCrmLeadDocumentActivity
	extends CBPCreateDocumentActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties["LeadId"] = 0;
	}

	public function Execute()
	{
		if (!CModule::IncludeModule('crm'))
			CBPActivityExecutionStatus::Closed;

		$rootActivity = $this->GetRootActivity();

		$documentId = array();
		$documentId[0] = 'crm';
		$documentId[1] = 'CCrmDocumentLead';
		$documentId[2] = 'LEAD';

		$documentService = $this->workflow->GetService('DocumentService');
		$this->LeadId = $documentService->CreateDocument($documentId, $this->Fields);
		return CBPActivityExecutionStatus::Closed;
	}

	public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
	{
		if (!CModule::IncludeModule('crm'))
		{
			return(array('code' => 'NotLoaded', 'module'=> 'crm', 'message'=> GetMessage('BPCDA_MODULE_NOT_LOADED')));
		};

		$arErrors = array();

		$arDocumentFields = CCrmDocumentDeal::GetDocumentFields('DEAL');

		$arTestFields = isset($arTestProperties['Fields']) && is_array($arTestProperties['Fields']) ? $arTestProperties['Fields'] : array();
		$title = isset($arTestFields['TITLE']) ? $arTestFields['TITLE'] : '';
		if($title === '')
		{
			$arErrors[] = array('code' => 'NotExist', 'parameter' => 'TITLE', 'message' => GetMessage('BPCDA_FIELD_NOT_FOUND', array('#NAME#' => $arDocumentFields['TITLE']['Name'])));
		}

		return array_merge($arErrors, parent::ValidateProperties($arTestProperties, $user));
	}


	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "", $popupWindow = null)
	{
		$documentType[0] = 'crm';
		$documentType[1] = 'CCrmDocumentLead';
		$documentType[2] = 'LEAD';
		return parent::GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues, $formName, $popupWindow);
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$arErrors)
	{
		$documentType[0] = 'crm';
		$documentType[1] = 'CCrmDocumentLead';
		$documentType[2] = 'LEAD';
		return parent::GetPropertiesDialogValues($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues, $arErrors);
	}
}
?>