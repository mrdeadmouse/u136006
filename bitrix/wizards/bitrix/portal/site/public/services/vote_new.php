<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/services/vote_new.php");
$APPLICATION->SetTitle(GetMessage("SERVICES_TITLE"));
?><?$APPLICATION->IncludeComponent("bitrix:voting.form", ".default", Array(
	"VOTE_ID"	=>	$_REQUEST["VOTE_ID"],
	"VOTE_RESULT_TEMPLATE"	=>	"vote_result.php?VOTE_ID=#VOTE_ID#",
	"CACHE_TYPE"	=>	"N",
	"CACHE_TIME"	=>	"3600"
	)
);?><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>