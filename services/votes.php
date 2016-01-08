<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/services/votes.php");
$APPLICATION->SetTitle(GetMessage("SERVICES_TITLE"));
?><p><?=GetMessage("SERVICES_INFO")?></p>

<?$APPLICATION->IncludeComponent(
	"bitrix:voting.list",
	"",
	Array(
		"CHANNEL_SID" => "", 
		"VOTE_FORM_TEMPLATE" => "vote_new.php?VOTE_ID=#VOTE_ID#", 
		"VOTE_RESULT_TEMPLATE" => "vote_result.php?VOTE_ID=#VOTE_ID#" 
	)
);?><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>