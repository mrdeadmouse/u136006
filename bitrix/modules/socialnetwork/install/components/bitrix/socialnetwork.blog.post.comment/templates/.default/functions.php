<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (!function_exists("__sbpc_bind_post_to_form"))
{
	function __sbpc_bind_post_to_form($xml_id, $form_id_get=null, $arParams)
	{
		static $form_id = null;
		if ($form_id_get !== null)
		{
			$form_id = $form_id_get;
			return;
		}
?><script type="text/javascript">BX.ready(function(){__blogLinkEntity({'<?=CUtil::JSEscape($xml_id)?>' : ['BG', <?=$arParams["ID"]?>, '<?=$arParams["LOG_ID"]?>']}, <?if ($form_id == null) { ?> window.SBPC.form.id<? } else { ?>"<?=$form_id?>"<? } ?>);});</script><?
	}
}
?>