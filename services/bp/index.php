<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/services/bp/index.php");
$APPLICATION->SetTitle(GetMessage("SERVICES_TITLE"));?>
<?$APPLICATION->IncludeComponent("bitrix:bizproc.wizards", ".default", array(
	"IBLOCK_TYPE" => "bizproc_iblockx",
	"ADMIN_ACCESS" => array(
		0 => "1",
	),
	"SEF_MODE" => "Y",
	"SEF_FOLDER" => "/services/bp/",
	"AJAX_MODE" => "N",
	"AJAX_OPTION_SHADOW" => "Y",
	"AJAX_OPTION_JUMP" => "N",
	"AJAX_OPTION_STYLE" => "Y",
	"AJAX_OPTION_HISTORY" => "N",
	"SET_TITLE" => "Y",
	"SET_NAV_CHAIN" => "Y",
	"SKIP_BLOCK" => "N",
	"AJAX_OPTION_ADDITIONAL" => "",
	"SEF_URL_TEMPLATES" => array(
		"index" => "index.php",
		"new" => "new.php",
		"list" => "#block_id#/",
		"start" => "#block_id#/start.php",
		"task" => "#block_id#/task-#task_id#.php",
		"bp" => "#block_id#/bp.php",
		"setvar" => "#block_id#/setvar.php",
	)
	),
	false
);?> <?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>