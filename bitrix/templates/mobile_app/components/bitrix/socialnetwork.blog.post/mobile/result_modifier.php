<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
$arResult["is_ajax_post"] = (intval($_REQUEST["comment_post_id"]) > 0 ? "Y" : "N");
$arResult["Post"]["IS_IMPORTANT"] = false;
if (is_array($arResult["POST_PROPERTIES"]["DATA"]) &&
	array_key_exists("UF_BLOG_POST_IMPRTNT", $arResult["POST_PROPERTIES"]["DATA"]) &&
	(intval($arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_IMPRTNT"]["VALUE"]) > 0))
{
	$arResult["Post"]["IS_IMPORTANT"] = true;
	unset($arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_IMPRTNT"]);
}
?>