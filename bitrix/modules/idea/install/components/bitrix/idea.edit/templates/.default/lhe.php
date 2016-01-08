<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if(!CModule::IncludeModule("fileman"))
	return;
/**
 * @var array $arParams
 * @var array $arResult
 */
?>
<script type="application/javascript">
BX.message({
	BLOG_P_IMAGE_LINK : '<?=GetMessageJS("BLOG_P_IMAGE_LINK")?>',
	FPF_VIDEO : '<?=GetMessageJS("FPF_VIDEO")?>',
	BLOG_IMAGE : '<?= GetMessageJS('BLOG_IMAGE')?>',
	BPC_IMAGE_SIZE_NOTICE : '<?= GetMessageJS('BPC_IMAGE_SIZE_NOTICE', array('#SIZE#' => DoubleVal(COption::GetOptionString("blog", "image_max_size", 1000000)/1000000)))?>',
	BLOG_P_IMAGE_UPLOAD : '<?= GetMessageJS('BLOG_P_IMAGE_UPLOAD')?>',
	BPC_VIDEO_P : '<?= GetMessageJS('BPC_VIDEO_P')?>',
	BPC_VIDEO_PATH_EXAMPLE : '<?= GetMessageJS('BPC_VIDEO_PATH_EXAMPLE')?>',
	POST_FORM_ACTION_URI : '<?=CUtil::JSEscape(POST_FORM_ACTION_URI)?>'
});
BX.Idea = (!!BX.Idea ? BX.Idea : {});
BX.Idea.obj = (!!BX.Idea.obj ? BX.Idea.obj : {});
</script>
<?
function customizeLHEForIdea($id, $addId = null)
{
	static $IDs = array();
	if ($addId !== null && is_string($addId))
	{
		$IDs[] = $addId;
		return true;
	}
	else if (!in_array($id, $IDs))
		return false;
?>
<script>
BX.Idea.obj['<?=$id?>'] = true;
(function(){
var res, func1 = function(){
		if (!!BX["Idea"] && !!BX["Idea"]["customizeEditor"])
		{
			BX["Idea"]["customizeEditor"]('<?=$id?>');
			clearInterval(res);
			return true;
		}
		return false
	};
	if (func1() !== true)
		res = setInterval(func1, 100);
})();
</script>
<?
	return true;
}
AddEventHandler("fileman", "OnIncludeLightEditorScript", "customizeLHEForIdea");

function initLHEForIdea($id, $arResult, $arParams)
{
	customizeLHEForIdea($id, $id);
	$arSmiles = array();
	if(!empty($arResult["Smiles"]))
	{
		foreach($arResult["Smiles"] as $arSmile)
		{
			$arSmiles[] = array(
				'name' => $arSmile["~LANG_NAME"],
				'path' => "/bitrix/images/blog/smile/".$arSmile["IMAGE"],
				'code' => str_replace("\\\\","\\",$arSmile["TYPE"])
			);
		}
	}
	$bbCode = true;
	if($arResult["allow_html"] == "Y" && (($arResult["PostToShow"]["DETAIL_TEXT_TYPE"] == "html" && $_REQUEST["load_editor"] != "N") || $_REQUEST["load_editor"] == "Y"))
		$bbCode = false;

	// Detect necessity of first convertion content from BB-code to HTML in editor.
	$bConvertContentFromBBCodes = !$bbCode && $_REQUEST["load_editor"] == "Y" &&
		!isset($_REQUEST['preview']) && !isset($_REQUEST['save']) && !isset($_REQUEST['apply']) && !isset($_REQUEST['draft']);
	$LHE = new CLightHTMLEditor;
?>
<script>
BX.Idea.obj['<?=$id?>'] = true;
BX.Idea['<?=$id?>Images'] = [];
<?
foreach($arResult["Images"] as $aImg)
{
	?>BX.Idea['<?=$id?>Images'].push('<?=$aImg["ID"]?>');<?
}
?>
BX.Idea['<?=$id?>Settings'] = {
	IMAGE_MAX_WIDTH : <?=intval($arParams['IMAGE_MAX_WIDTH'])?>,
	FORM_NAME : '<?=$arResult["FORM_NAME"]?>'
};

</script>

<div id="edit-post-text">
	<?$LHE->Show(array(
		'id' => $id,
		'height' => $arParams['EDITOR_DEFAULT_HEIGHT'],
		'inputId' => 'POST_MESSAGE_HTML',
		'inputName' => 'POST_MESSAGE',
		'content' => $arResult["PostToShow"]["~DETAIL_TEXT"],
		'bUseFileDialogs' => false,
		'bUseMedialib' => false,
		'toolbarConfig' => array(
			'Bold', 'Italic', 'Underline', 'Strike',
			'ForeColor','FontList', 'FontSizeList',
			'RemoveFormat',
			'Quote', 'Code', 'InsertCut',
			'CreateLink', 'DeleteLink', 'Image',
			'BlogImage', (($arResult["allowVideo"] == "Y") ? 'BlogInputVideo' : ''), 'Table',
			'InsertOrderedList',
			'InsertUnorderedList',
			'SmileList',
			'Source'
		),
		'jsObjName' => 'o'.$id,
		'arSmiles' => $arSmiles,
		'smileCountInToolbar' => $arParams['SMILES_COUNT'],
		'bSaveOnBlur' => false,
		'BBCode' => $bbCode,
		'bConvertContentFromBBCodes' => $bConvertContentFromBBCodes,
		'bQuoteFromSelection' => true, // Make quote from any text in the page
		'bResizable' => $arParams['EDITOR_RESIZABLE'],
		'bSetDefaultCodeView' => $arParams['EDITOR_CODE_DEFAULT'], // Set first view to CODE or to WYSIWYG
		'bBBParseImageSize' => true // [IMG ID=XXX WEIGHT=5 HEIGHT=6],  [IMGWEIGHT=5 HEIGHT=6]/image.gif[/IMG]
	));
	?></div><?
}
?>