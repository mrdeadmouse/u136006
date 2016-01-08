<?
define("STOP_STATISTICS", true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

/**
 * @global CUser $USER
 */

if(!CModule::IncludeModule("bizproc"))
	die();

if (!$USER->IsAuthorized())
	die();

$fileName = preg_replace("/[^A-Za-z0-9_.-]+/i", "", trim($_REQUEST["f"]));
$fileId = intval($_REQUEST["i"]);
$fileAction = ($_REQUEST["act"] == "v" ? "view" : "download");
$filePathHash = trim($_REQUEST["h"]);

if (strlen($fileName) <= 0 || $fileId <= 0 || strlen($fileAction) <= 0)
	die("Error1");

$bpId = !empty($_REQUEST['bp_id']) ? (int)$_REQUEST['bp_id'] : 0;
$iblockId = !empty($_REQUEST['iblock_id']) ? (int)$_REQUEST['iblock_id'] : 0;

$options = array();
if ($fileAction == "download")
{
	$options["force_download"] = true;
}

if ($bpId > 0 && $iblockId > 0 && $fileName !== '')
{
	$fields = CBPVirtualDocument::GetDocumentFields("type_".$iblockId);
	if (isset($fields[$fileName]) && $fields[$fileName]["BaseType"] == "file")
	{
		list($dbRecordsList, $dbRecordsList1) = CBPVirtualDocument::GetList(
			array(),
			array("ID" => $bpId, "IBLOCK_ID" => $iblockId, "CHECK_BP_VIRTUAL_PERMISSIONS" => "read"),
			false,
			false,
			array($fileName)
		);
		$row = $dbRecordsList->fetch();
		if (!$row)
			die("Error: BP not found");
		$files = (array)$row[$fileName];
		$key = array_search($fileId, $files);
		if ($key !== false)
		{
			set_time_limit(0);
			CFile::ViewByUser($files[$key], $options);
		}
		else
			die("Error: File not found");
	}
	die();
}
$rawName = trim($_REQUEST["f"]);
$arImg = CFile::GetFileArray($fileId);
if (!$arImg)
	die("Error2");

if (strlen($arImg["FILE_NAME"]) != strlen($rawName) || $arImg["FILE_NAME"] != $rawName)
	die("Error3");

if (strlen($arImg["SUBDIR"]) <= 0)
	die("Error4");

if (substr($arImg["SUBDIR"], 0, strlen("bizproc_wf/")) != "bizproc_wf/"
	&& (strlen($filePathHash) <= 0 || $filePathHash != md5($arImg["SUBDIR"])))
	die("Error5");

set_time_limit(0);

CFile::ViewByUser($arImg, $options);
