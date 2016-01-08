<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

$randString = $component->randString();
$claim = false;
$title = GetMessage("CT_BLL_TOOLBAR_ADD_TITLE_LIST");
if($arParams["IBLOCK_TYPE_ID"] == COption::GetOptionString("lists", "livefeed_iblock_type_id"))
{
	$title = GetMessage("CT_BLL_TOOLBAR_ADD_TITLE_PROCESS");
	$claim = true;
}
if($arParams['CAN_EDIT']): ?>
	<div class="bx-bp-btn-panel">
		<a href="<?= $arResult["LIST_EDIT_URL"] ?>"
		   class="webform-small-button webform-small-button-accept bp-small-button"
		   title="<?= $title ?>"
		>
			<?= GetMessage("CT_BLL_TOOLBAR_ADD_NEW") ?>
		</a>
		<? if($claim && $arParams['CAN_EDIT']): ?>
			<a
				href="<?= $arParams["CATALOG_PROCESSES_URL"] ?>"
				class="webform-small-button webform-small-button-cancel"
				title="<?= GetMessage("CT_BLL_TOOLBAR_TRANSITION_PROCESSES") ?>"
			>
				<?= GetMessage("CT_BLL_TOOLBAR_TRANSITION_PROCESSES") ?>
			</a>
		<? endif; ?>
		<? if($arParams["IBLOCK_TYPE_ID"] != "lists" && $arParams["IBLOCK_TYPE_ID"] != "lists_socnet" && empty($arResult["ITEMS"])): ?>
			<p id="bx-lists-default-processes" onclick="javascript:BX['LiveFeedShowClass_<?=$randString?>'].createDefaultProcesses();" class="webform-small-button webform-small-button-cancel bp-small-button" title="<?= GetMessage("CT_BLL_TOOLBAR_ADD_DEFAULT") ?>">
				<?= GetMessage("CT_BLL_TOOLBAR_ADD_DEFAULT") ?>
			</p>
		<? endif; ?>
		<input type="hidden" id="bx-lists-select-site" value="<?= SITE_ID ?>" />
	</div>
<? endif; ?>
<? foreach($arResult["ITEMS"] as $item): ?>
	<div class="bp-bx-application">
		<a href="<?= $item["LIST_URL"]?>">
			<span class="bp-bx-application-icon"><?= $item["IMAGE"] ?></span>
		<span class="bp-bx-application-title-wrapper">
			<span class="bp-bx-application-title">
				<span class="bx-lists-application-link">
					<span><?= $item["NAME"] ?></span></span>
			</span>
		</span>
		</a>
		<? if($claim && $arParams['CAN_EDIT']): ?>
			<span class="bp-bx-application-check">
				<input type="checkbox" value="" id="bx-lists-show-live-feed-<?= $item['ID'] ?>" <?= $item['SHOW_LIVE_FEED'] ? 'checked' : '' ?>
					   onclick="javascript:BX['LiveFeedShowClass_<?=$randString?>'].showLiveFeed(<?= $item['ID'] ?>);">
				<label for="bx-lists-show-live-feed-<?= $item['ID'] ?>"><?= GetMessage("CT_BLL_TOOLBAR_SHOW_LIVE_FEED") ?></label>
			</span>
		<? endif; ?>
	</div>
<? endforeach; ?>

<script type="text/javascript">
	BX(function () {
		BX['LiveFeedShowClass_<?= $randString?>'] = new BX.LiveFeedShowClass({
			randomString: '<?= $randString ?>'
		});
	});
</script>