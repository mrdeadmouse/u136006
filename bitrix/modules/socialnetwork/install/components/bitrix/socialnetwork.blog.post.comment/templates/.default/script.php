<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if(!$arResult["CanUserComment"])
	return;

$arSmiles = array();
if(!empty($arResult["Smiles"]))
{
	foreach($arResult["Smiles"] as $arSmile)
	{
		$arSmiles[] = array(
			'name' => $arSmile["~LANG_NAME"],
			'path' => "/bitrix/images/blog/smile/".$arSmile["IMAGE"],
			'code' => str_replace("\\\\","\\",$arSmile["TYPE"]),
			'codes' => str_replace("\\\\","\\",$arSmile["TYPING"]),
			'width' => $arSmile["IMAGE_WIDTH"],
			'height' => $arSmile["IMAGE_HEIGHT"],
		);
	}
}
$rand = randString(4);
$formParams = Array(
	"FORM_ID" => "blogCommentForm".$rand,
	"SHOW_MORE" => "Y",
	"PARSER" => Array(
		"Bold", "Italic", "Underline", "Strike", "ForeColor",
		"FontList", "FontSizeList", "RemoveFormat", "Quote",
		"Code", ((!$arResult["NoCommentUrl"]) ? 'CreateLink' : ''),
		"Image", (($arResult["allowImageUpload"] == "Y") ? 'UploadImage' : ''),
		(($arResult["allowVideo"] == "Y") ? "InputVideo" : ""),
		"Table", "Justify", "InsertOrderedList",
		"InsertUnorderedList",
		"MentionUser", "SmileList", "Source"),
	"BUTTONS" => Array(
		((in_array("UF_BLOG_COMMENT_FILE", $arParams["COMMENT_PROPERTY"]) || in_array("UF_BLOG_COMMENT_DOC", $arParams["COMMENT_PROPERTY"])) ? "UploadFile" : ""),
		((!$arResult["NoCommentUrl"]) ? 'CreateLink' : ''),
		(($arResult["allowVideo"] == "Y") ? "InputVideo" : ""),
		//(($arResult["allowImageUpload"] == "Y") ? 'UploadImage' : ''),
		"Quote",
		"MentionUser"/*, "BlogTag"*/
	),
	"TEXT" => Array(
		"NAME" => "comment",
		"VALUE" => "",
		"HEIGHT" => "80px"
	),
	"DESTINATION" => Array(
		"VALUE" => $arResult["FEED_DESTINATION"],
		"SHOW" => "N",
	),
	"UPLOAD_FILE" => !empty($arResult["COMMENT_PROPERTIES"]["DATA"]["UF_BLOG_COMMENT_FILE"]) ? false :
		$arResult["COMMENT_PROPERTIES"]["DATA"]["UF_BLOG_COMMENT_DOC"],
	"UPLOAD_WEBDAV_ELEMENT" => $arResult["COMMENT_PROPERTIES"]["DATA"]["UF_BLOG_COMMENT_FILE"],
	"UPLOAD_FILE_PARAMS" => array("width" => 400, "height" => 400),
	"FILES" => Array(
		"VALUE" => array(),
		"DEL_LINK" => $arResult["urlToDelImage"],
		"SHOW" => "N",
		"POSTFIX" => "file"
	),
	"SMILES" => Array("VALUE" => $arSmiles),
	"LHE" => array(
		"documentCSS" => "body {color:#434343;}",
		"ctrlEnterHandler" => "__submit".$rand,
		"id" => "idLHE_blogCommentForm".$rand,
		"fontFamily" => "'Helvetica Neue', Helvetica, Arial, sans-serif",
		"fontSize" => "12px",
		"bInitByJS" => true,
		"height" => 80
	),
	"IS_BLOG" => true,
);
//===WebDav===
if(!array_key_exists("USER", $GLOBALS) || !$GLOBALS["USER"]->IsAuthorized())
{
	unset($formParams["UPLOAD_WEBDAV_ELEMENT"]);
	foreach($formParams["BUTTONS"] as $keyT => $valT)
	{
		if($valT == "UploadFile")
		{
			unset($formParams["BUTTONS"][$keyT]);
		}
	}
}
//===WebDav===

__sbpc_bind_post_to_form(($tmp1 = null), $formParams["FORM_ID"], ($tmp2 = null));
?>
<div style="display:none;">
	<form action="/bitrix/urlrewrite.php" <?
		?>id="<?=$formParams["FORM_ID"]?>" name="<?=$formParams["FORM_ID"]?>" <?
		?>method="POST" enctype="multipart/form-data" class="comments-form">
		<input type="hidden" name="comment_post_id" id="postId" value="" />
		<input type="hidden" name="log_id" id="logId" value="" />
		<input type="hidden" name="parentId" id="parentId" value="" />
		<input type="hidden" name="edit_id" id="edit_id" value="" />
		<input type="hidden" name="act" id="act" value="add" />
		<input type="hidden" name="as" id="as" value="<?=$arParams['AVATAR_SIZE_COMMENT']?>" />
		<input type="hidden" name="post" id="" value="Y" />
		<input type="hidden" name="blog_upload_cid" id="upload-cid" value="" />
		<?=bitrix_sessid_post();?>
<?
if(empty($arResult["User"]))
{
?>
	<div class="blog-comment-field blog-comment-field-user">
		<div class="blog-comment-field blog-comment-field-author"><div class="blog-comment-field-text"><?
			?><label for="user_name"><?=GetMessage("B_B_MS_NAME")?></label><?
			?><span class="blog-required-field">*</span></div><span><?
			?><input maxlength="255" size="30" tabindex="3" type="text" name="user_name" id="user_name" value="<?=htmlspecialcharsEx($_SESSION["blog_user_name"])?>"></span></div>
		<div class="blog-comment-field-user-sep">&nbsp;</div>
		<div class="blog-comment-field blog-comment-field-email"><div class="blog-comment-field-text"><label for="">E-mail</label></div><span><input maxlength="255" size="30" tabindex="4" type="text" name="user_email" id="user_email" value="<?=htmlspecialcharsEx($_SESSION["blog_user_email"])?>"></span></div>
		<div class="blog-clear-float"></div>
	</div>
<?
}
?>
	<div id="blog-post-autosave-hidden" <?/*?>style="display:none;"<?*/?>></div>
	<?$APPLICATION->IncludeComponent("bitrix:main.post.form", "", $formParams, false, Array("HIDE_ICONS" => "Y"));?>
<?
if($arResult["use_captcha"]===true)
{
?>
	<div class="blog-comment-field blog-comment-field-captcha">
		<div class="blog-comment-field-captcha-label">
			<label for="captcha_word"><?=GetMessage("B_B_MS_CAPTCHA_SYM")?></label><span class="blog-required-field">*</span><br>
			<input type="hidden" name="captcha_code" id="captcha_code" value="<?=$arResult["CaptchaCode"]?>">
			<input type="text" size="30" name="captcha_word" id="captcha_word" value=""  tabindex="7">
		</div>
		<div class="blog-comment-field-captcha-image"><div id="div_captcha"></div></div>
	</div>
	<div id="captcha_del">
	<script>
		<!--
		var cc;
		if(document.cookie.indexOf('<?=session_name()?>=') == -1)
			cc = Math.random();
		else
			cc ='<?=$arResult["CaptchaCode"]?>';

		document.write('<img src="/bitrix/tools/captcha.php?captcha_code='+cc+'" width="180" height="40" id="captcha" style="display:none;">');
		document.getElementById('captcha_code').value = cc;
		//-->
	</script>
	</div>
<?
}
?>
</form>
</div>
<script>
BX.ready(function(){
	window["UC"] = (!!window["UC"] ? window["UC"] : {});
	window["UC"]["f<?=$formParams["FORM_ID"]?>"] = new FCForm({
		entitiesId : {},
		formId : '<?=$formParams["FORM_ID"]?>',
		editorId : '<?=$formParams["LHE"]["id"]?>',
		editorName : '<?=$formParams["LHE"]["jsObjName"]?>'
	});

	window["__submit<?=$rand?>"] = function ()
	{
		if (!!window["UC"]["f<?=$formParams["FORM_ID"]?>"] && !!window["UC"]["f<?=$formParams["FORM_ID"]?>"].eventNode)
		{
			BX.onCustomEvent(window["UC"]["f<?=$formParams["FORM_ID"]?>"].eventNode, 'OnButtonClick', ['submit']);
		}
		return false;
	}

	if (!!window["UC"]["f<?=$formParams["FORM_ID"]?>"].eventNode)
	{
		BX.addCustomEvent(window["UC"]["f<?=$formParams["FORM_ID"]?>"].eventNode, 'OnUCFormClear', __blogOnUCFormClear);
		BX.addCustomEvent(window["UC"]["f<?=$formParams["FORM_ID"]?>"].eventNode, 'OnUCFormAfterShow', __blogOnUCFormAfterShow);
		BX.addCustomEvent(window["UC"]["f<?=$formParams["FORM_ID"]?>"].eventNode, 'OnUCFormSubmit', __blogOnUCFormSubmit);
	}
	BX.addCustomEvent(window, "OnBeforeSocialnetworkCommentShowedUp", function(entity){ if (entity == 'socialnetwork') { window["UC"]["f<?=$formParams["FORM_ID"]?>"].hide(true); } } );

	window["SBPC"] = {
		form : BX('<?=$formParams["FORM_ID"]?>'),
		actionUrl : '/bitrix/urlrewrite.php?SEF_APPLICATION_CUR_PAGE_URL=<?=str_replace("%23", "#", urlencode($arResult["urlToPost"]))?>',
		editorId : '<?=$formParams["LHE"]["id"]?>',

		jsMPFName : 'PlEditor<?=$formParams["FORM_ID"]?>'
	};
});
</script>