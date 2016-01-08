<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/components/bitrix/voting.current/templates/.userfield/style.css');
$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/components/bitrix/rating.vote/templates/like/popup.css');
$GLOBALS['APPLICATION']->AddHeadScript('/bitrix/components/bitrix/voting.current/templates/.userfield/script.js');

CJSCore::Init(array('popup', 'ajax', 'dd'));

$uid = $this->params["uid"];
$controller = $this->params["controller"];
$lastVote = intval($this->params["lastVote"]);
$form = (!!$this->params["form"] ? $this->params["form"] : '');
?>
<script type="text/javascript">
if (typeof(oMSL) == "object")
{
	oMSL.registerScripts('<?=CUtil::JSEscape($this->GetTemplate()->GetFolder()."/script.js")?>');
}
BX.ready(
	function(){
		if (! <?=$controller?>.loaded) {
			<?=$controller?>.loaded = true;
			BVote<?=$uid?> = new BVotedUser({
				'CID' : '<?=$uid?>',
				'controller': <?=$controller?>,
				'urlTemplate' : "<?=CUtil::JSEscape($arParams["~PATH_TO_USER"]);?>",
				'nameTemplate' : "<?=CUtil::JSEscape($arParams["~NAME_TEMPLATE"]);?>",
				'url' : "<?=POST_FORM_ACTION_URI?>",
				'form' : "<?=CUtil::JSEscape($form)?>",
				'voteId' : <?=$arParams["VOTE_ID"]?>,
				'startCheck' : <?=$lastVote?>
			});
		}
	}
);
</script>
<?if ($_REQUEST["VOTE_ID"] == $arParams["VOTE_ID"] && $_REQUEST["AJAX_POST"] == "Y" && check_bitrix_sessid()):
	$res = ob_get_clean();
	$APPLICATION->RestartBuffer();
	echo $res;
	die();
endif;
?>
</div>