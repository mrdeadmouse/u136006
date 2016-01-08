<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */

use Bitrix\Main\Localization\Loc;

\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/bizproc/tools.js');
\Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/components/bitrix/bizproc.workflow.faces/templates/.default/style.css');

$randString = $component->randString();
?>

<div class="bx-bp-btn-panel">
	<p id="bx-lists-create-processes" class="webform-small-button webform-small-button-accept bp-small-button"
	   title="<?= GetMessage("CT_BLL_BUTTON_NEW_PROCESSES") ?>"
	   onclick="javascript:BX['ListsProcessesClass_<?=$randString?>'].showProcesses();">
		<?= GetMessage("CT_BLL_BUTTON_NEW_PROCESSES") ?>
	</p>
</div>

<div id="bx-lists-store_items" class="bx-lists-store-items"></div>
<input type="hidden" id="bx-lists-select-site" value="<?= SITE_DIR ?>" />
<input type="hidden" id="bx-lists-select-site-id" value="<?= SITE_ID ?>" />
<?
if (is_array($arResult["RECORDS"]))
{
	foreach ($arResult["RECORDS"] as &$record)
	{
		if (strlen($record['data']["DOCUMENT_URL"]) > 0 && strlen($record['data']["DOCUMENT_NAME"]) > 0)
		{
			$record['data']['DOCUMENT_NAME'] = '<a href="'.$record['data']["DOCUMENT_URL"].'" class="bp-folder-title-link">'.htmlspecialcharsbx($record['data']['DOCUMENT_NAME']).'</a>';
		}

		if($record['data']['DOCUMENT_STATE'])
		{
			$record['data']['COMMENTS'] = '<div class="bp-comments"><a href="#" onclick="if (BX.Bizproc.showWorkflowInfoPopup) return BX.Bizproc.showWorkflowInfoPopup(\''.$record['data']["WORKFLOW_ID"].'\')"><span class="bp-comments-icon"></span>'
				.(!empty($arResult["COMMENTS_COUNT"]['WF_'.$record['data']["WORKFLOW_ID"]]) ? (int) $arResult["COMMENTS_COUNT"]['WF_'.$record['data']["WORKFLOW_ID"]] : '0')
				.'</a></div>';

			$record['data']["NAME"] .= '<span class="bp-status"><span class="bp-status-inner"><span>'.htmlspecialcharsbx($record['data']["WORKFLOW_STATE"]).'</span></span></span>';
			ob_start();
			$APPLICATION->IncludeComponent(
				"bitrix:bizproc.workflow.faces",
				"",
				array(
					"WORKFLOW_ID" => $record['data']["WORKFLOW_ID"]
				),
				$component
			);
			$record['data']['WORKFLOW_PROGRESS'] = ob_get_contents();
			ob_end_clean();
		}
	}
}

$APPLICATION->IncludeComponent(
	'bitrix:bizproc.interface.grid',
	"",
	array(
		"GRID_ID"=>$arResult["GRID_ID"],
		"HEADERS"=>$arResult["HEADERS"],
		"SORT"=>$arResult["SORT"],
		"ROWS"=>$arResult["RECORDS"],
		"FOOTER"=>array(array("title"=>Loc::getMessage("BPWC_WLCT_TOTAL"), "value"=>$arResult["ROWS_COUNT"])),
		"ACTION_ALL_ROWS"=>true,
		"EDITABLE"=>true,
		"NAV_OBJECT"=>$arResult["NAV_RESULT"],
		"AJAX_MODE"=>"Y",
		"AJAX_OPTION_JUMP"=>"Y",
		"FILTER"=>$arResult["FILTER"],
		'ERROR_MESSAGES' => $arResult['ERRORS']
	),
	$component
);
?>

<script type="text/javascript">
	BX(function () {
		BX['ListsProcessesClass_<?= $randString?>'] = new BX.ListsProcessesClass({});
	});
</script>