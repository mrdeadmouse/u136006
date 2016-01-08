<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!empty($arResult["RECORDS"]))
{
	$runtime = CBPRuntime::GetRuntime();
	$runtime->StartRuntime();

	/** @var CBPDocumentService $documentService */
	$documentService = $runtime->GetService('DocumentService');

	foreach ($arResult["RECORDS"] as &$record)
	{
		$record['data']['DOCUMENT_ICON'] = '';
		try
		{
			$record['data']['DOCUMENT_ICON'] = $documentService->getDocumentIcon($record['data']['PARAMETERS']['DOCUMENT_ID']);
		}
		catch (Exception $e)
		{

		}
	}
}
$arResult["currentUserStatus"] = !empty($_GET['USER_STATUS'])? (int)$_GET['USER_STATUS'] : 0;