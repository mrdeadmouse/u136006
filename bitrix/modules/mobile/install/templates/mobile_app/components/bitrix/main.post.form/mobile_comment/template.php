<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
// $APPLICATION->SetPageProperty("BodyClass", "newpost-page");
$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH."/components/bitrix/main.post.form/mobile_comment/script_attached.js");
$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH."/log_mobile.js");

if (!$arResult["FatalError"])
{
	?><div class="newpost-panel-top newpost-panel-top-comment"><?
		?><div class="attach-dog-button" id="feed-add-post-mention"></div><?
	?></div><?

	?><form action="" id="<?=$arParams["FORM_ID"]?>" name="<?=$arParams["FORM_ID"]?>" method="POST" enctype="multipart/form-data"><?
		?><?=bitrix_sessid_post();?><?
		?><textarea name="COMMENT_TEXT" class="newpost-textarea" id="COMMENT_TEXT" cols="30" rows="10" placeholder="<?=GetMessage("MFP_COMMENT_TEXTAREA_TITLE")?>"><?=($arResult["COMMENT_TEXT"])?></textarea><?
	?></form><?

	?><script type="text/javascript">

		document.addEventListener("deviceready", function()
		{
			BX.message({
				'MPF_COMMENT_TABLE_OK': '<?=GetMessageJS("MPF_COMMENT_TABLE_OK")?>',
				'MPF_COMMENT_TABLE_CANCEL': '<?=GetMessageJS("MPF_COMMENT_TABLE_CANCEL")?>',
				'MPF_COMMENT_SEND': '<?=GetMessageJS("MPF_COMMENT_SEND")?>',
				'MPF_COMMENT_CANCEL': '<?=GetMessageJS("MPF_COMMENT_CANCEL")?>'
			});

			oMPFComment.Init({
				mentionUri: '<?=SITE_DIR?>mobile/index.php?mobile_action=get_user_list',
				formId: '<?=CUtil::JSEscape($arParams["FORM_ID"])?>',
				commentId: <?=intval($arParams["COMMENT_ID"])?>,
				detailPageId: '<?=CUtil::JSEscape($arParams["COMMENT_TYPE"]."_".$arResult["POST_ID"])?>',
				nodeId: '<?=CUtil::JSEscape($arParams["NODE_ID"])?>'
			});

		}, false);
	</script><?
}
else
{
	echo $arResult["FatalError"];
}
?>