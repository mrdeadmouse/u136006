<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
$INPUT_ID = trim($arParams["~INPUT_ID"]);
if(strlen($INPUT_ID) <= 0)
	$INPUT_ID = "title-search-input";
$INPUT_ID = CUtil::JSEscape($INPUT_ID);

$CONTAINER_ID = trim($arParams["~CONTAINER_ID"]);
if(strlen($CONTAINER_ID) <= 0)
	$CONTAINER_ID = "title-search";
$CONTAINER_ID = CUtil::JSEscape($CONTAINER_ID);

?>

<div id="<?=$CONTAINER_ID?>">
	<form method="get" action="<?=$arResult["FORM_ACTION"]?>">
		<div id="search-button"><input type="submit" value="<?=GetMessage("CT_BST_SEARCH_BUTTON")?>" id="search-submit-button"></div>
		<div id="search-textbox"><b class="r1"></b><b class="r0"></b><span><input name="q" type="text" autocomplete="off" onblur="if (this.value=='') {this.value='<?=GetMessage("CT_BST_SEARCH_HINT")?>'; this.className='';}" onclick="if (this.value=='<?=GetMessage("CT_BST_SEARCH_HINT")?>') {this.value=''; this.className='selected';}" value="<?=GetMessage("CT_BST_SEARCH_HINT")?>" id="<?=$INPUT_ID?>" /></span><b class="r0"></b><b class="r1"></b></div>
	</form>
</div>


<script type="text/javascript">
var jsControl = new JCTitleSearch({
	//'WAIT_IMAGE': '/bitrix/themes/.default/images/wait.gif',
	'AJAX_PAGE' : '<?echo CUtil::JSEscape(POST_FORM_ACTION_URI)?>',
	'CONTAINER_ID': '<?echo $CONTAINER_ID?>',
	'INPUT_ID': '<?echo $INPUT_ID?>',
	'MIN_QUERY_LEN': 2
});
</script>
