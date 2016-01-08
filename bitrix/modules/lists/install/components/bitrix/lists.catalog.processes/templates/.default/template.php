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
?>

<div id="bx-lists-lcp-total-div">
	<div class="bx-lists-lcp-button-block">
		<p
			id="bx-lists-lcp-install-processes"
			onclick="javascript:BX['CatalogProcessesClass_<?= $arResult['RAND_STRING']?>'].installProcesses(this);"
			class="webform-small-button webform-small-button-accept"
			title="<?= GetMessage("LISTS_LCP_TEMPLATE_BUTTON_INSTALL") ?>"
			>
			<?= GetMessage("LISTS_LCP_TEMPLATE_BUTTON_INSTALL") ?>
		</p>
		<a
			id="bx-lists-lcp-transition-processes"
			href="<?= $arResult['LISTS_URL'] ?>"
			class="webform-small-button webform-small-button-cancel"
			title="<?= GetMessage("LISTS_LCP_TEMPLATE_TRANSITION_PROCESSES") ?>"
			>
			<?= GetMessage("LISTS_LCP_TEMPLATE_TRANSITION_PROCESSES") ?>
		</a>
	</div>

	<div class="bx-lists-lcp-description-page"><?= Loc::getMessage('LISTS_LCP_TEMPLATE_DESCRIPTION_PAGE') ?></div>

	<? if(!empty($arResult['SYSTEM_PROCESSES'])): ?>
		<table class="bx-lists-lcp-table-processes">
			<caption><?= Loc::getMessage('LISTS_LCP_TEMPLATE_TITLE_SYSTEM_PROCESSES') ?></caption>
			<? foreach ($arResult['SYSTEM_PROCESSES'] as $process): ?>
				<? (isset($process['PICK_OUT'])) ? $pickOut = 'not allocated' : $pickOut = 'allocate' ?>
				<tr
					id="<?= $pickOut ?>"
					data-pick-out="<?= $pickOut ?>"
					data-file="<?= htmlspecialcharsbx($process['FILE_PATH']) ?>"
					onmousedown="BX['CatalogProcessesClass_<?= $arResult['RAND_STRING']?>'].mousedown(this);"
					onmouseover="BX['CatalogProcessesClass_<?= $arResult['RAND_STRING']?>'].mouseover(this);"
					onmouseout="BX['CatalogProcessesClass_<?= $arResult['RAND_STRING']?>'].mouseout(this);"
				>
					<td>
						<div class="bx-lists-lcp-table-td-div">
							<p class="bx-lists-lcp-table-td-name"><?= htmlspecialcharsbx($process['NAME']) ?></p>
							<p class="bx-lists-lcp-table-td-description"><?= htmlspecialcharsbx($process['DESCRIPTION']) ?></p>
						</div>
					</td>
				</tr>
			<? endforeach; ?>
		</table>
	<? endif; ?>
	<? if(!empty($arResult['USER_PROCESSES'])): ?>
		<table class="bx-lists-lcp-table-processes">
			<caption><?= Loc::getMessage('LISTS_LCP_TEMPLATE_TITLE_USER_PROCESSES') ?></caption>
			<? foreach ($arResult['USER_PROCESSES'] as $process): ?>
				<? (isset($process['PICK_OUT'])) ? $pickOut = 'not allocated' : $pickOut = 'allocate' ?>
				<tr
					id="<?= $pickOut ?>"
					data-pick-out="<?= $pickOut ?>"
					data-file="<?= htmlspecialcharsbx($process['FILE_PATH']) ?>"
					onmousedown="BX['CatalogProcessesClass_<?= $arResult['RAND_STRING']?>'].mousedown(this);"
					onmouseover="BX['CatalogProcessesClass_<?= $arResult['RAND_STRING']?>'].mouseover(this);"
					onmouseout="BX['CatalogProcessesClass_<?= $arResult['RAND_STRING']?>'].mouseout(this);"
					>
					<td>
						<div class="bx-lists-lcp-table-td-div">
							<p class="bx-lists-lcp-table-td-name"><?= htmlspecialcharsbx($process['NAME']) ?></p>
							<p class="bx-lists-lcp-table-td-description"><?= htmlspecialcharsbx($process['DESCRIPTION']) ?></p>
						</div>
					</td>
				</tr>
			<? endforeach; ?>
		</table>
	<? endif; ?>

	<? if(count($arResult['USER_PROCESSES']) > 10): ?>
		<div class="bx-lists-lcp-button-block">
			<p
				id="bx-lists-lcp-install-processes"
				onclick="javascript:BX['CatalogProcessesClass_<?= $arResult['RAND_STRING']?>'].installProcesses(this);"
				class="webform-small-button webform-small-button-accept"
				title="<?= GetMessage("LISTS_LCP_TEMPLATE_BUTTON_INSTALL") ?>"
			>
				<?= GetMessage("LISTS_LCP_TEMPLATE_BUTTON_INSTALL") ?>
			</p>
		</div>
	<? endif; ?>
</div>

<input type="hidden" id="bx-lists-lcp-site-id" value="<?= SITE_ID ?>">
<input type="hidden" id="bx-lists-lcp-lists-url" value="<?= $arResult['LISTS_URL'] ?>">

<script type="text/javascript">
	BX(function () {
		BX['CatalogProcessesClass_<?= $arResult['RAND_STRING']?>'] = new BX.CatalogProcessesClass({
			randomString: '<?= $arResult['RAND_STRING'] ?>'
		});
	});

	BX.message({
		LISTS_LCP_TEMPLATE_STATUS_ACTION_SUCCESS: '<?= GetMessageJS('LISTS_LCP_TEMPLATE_STATUS_ACTION_SUCCESS') ?>',
		LISTS_LCP_TEMPLATE_STATUS_ACTION_ERROR: '<?= GetMessageJS('LISTS_LCP_TEMPLATE_STATUS_ACTION_ERROR') ?>',
		LISTS_LCP_TEMPLATE_PROCESS_INSTALLED: '<?= GetMessageJS('LISTS_LCP_TEMPLATE_PROCESS_INSTALLED') ?>'
	});
</script>