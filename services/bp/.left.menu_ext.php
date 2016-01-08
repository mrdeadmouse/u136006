<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

global $APPLICATION;

$aMenuLinksExt = array();

$i = 0;
$dbBlockList = CIBlock::GetList(
	array("SORT" => "ASC", "NAME" => "ASC"),
	array("ACTIVE" => "Y", "SITE_ID" => SITE_ID, "TYPE" => "bizproc_iblockx")
);
while ($arBlock = $dbBlockList->GetNext())
{
	$i++;
	if ($i > 6)
		break;

	$aMenuLinksExt[] = Array(
		$arBlock["NAME"], 
		"/services/bp/".$arBlock["ID"]."/", 
		Array(), 
		Array(), 
		"" 
	);
}

$aMenuLinks = $aMenuLinksExt;
?>