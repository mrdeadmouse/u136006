<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
	$this->IncludeLangFile("form.php");
?><div class="bx-vote-question-block"><?
	$params = $APPLICATION->IncludeComponent(
		"bitrix:voting.form",
		".default",
		Array(
			"VOTE_ID" => $arResult["VOTE_ID"],
			"VOTE_ASK_CAPTCHA" => $arParams["VOTE_ASK_CAPTCHA"],
			"PERMISSION" => $arParams["PERMISSION"],
			"VOTE_RESULT_TEMPLATE" => $arResult["VOTE_RESULT_TEMPLATE"],
			"ADDITIONAL_CACHE_ID" => $arResult["ADDITIONAL_CACHE_ID"],
			"CACHE_TIME" => $arParams["CACHE_TIME"],
			"CACHE_TYPE" => $arParams["CACHE_TYPE"],
		),
		($this->__component->__parent ? $this->__component->__parent : $component)
	);
?></div><?
	$this->__component->params = $params;
/*?><div class="bx-vote-bottom-block"><?*/
	?><a href="javascript:void(0);" class="post-item-more bx-vote-button" id="lets_vote<?=$arResult["VOTE_ID"]?>" <?
		?>onclick="voteSendForm(this, BX('<?=$params["form"]?>'), '<?=$params["uid"]?>');"><?=GetMessage("VOTE_SUBMIT_BUTTON")?></a><?
	?><a class="post-item-more bx-vote-button" href="<?=(strlen($arParams["ACTION_PAGE"]) > 0 ? $arParams["ACTION_PAGE"] : $APPLICATION->GetCurPageParam("view_result=Y",
		array("VOTE_ID","VOTING_OK","VOTE_SUCCESSFULL", "view_result", "view_form", "sessid", "AJAX_RESULT", "AJAX_POST", "VOTE_ID")))?>" id="lets_view<?=$arResult["VOTE_ID"]?>" <?
		?> onclick="return voteGetResult(<?=$params["controller"]?>, '<?=$params["uid"]?>', this)"><?=GetMessage("VOTE_SUBMIT_RESULTS")?></a><?
/*?></div><?*/?>
