<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
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
/** @var \Bitrix\Disk\Internals\BaseComponent $component */
use Bitrix\Main\Localization\Loc;
?>
<br />
<div class="bx-disk-aggregator-common-div">
	<ul>
		<?
		foreach($arResult["COMMON_DISK"] as $key=>$data)
		{
			if($key=='GROUP' || $key=='USER')
			{?>
				<li class="bx-disk-aggregator-list-folder">
					<img src="/bitrix/images/disk/default_folder.png" class="bx-disk-aggregator-icon-main" />
					<p class="bx-disk-aggregator-p-link" id="<?= $data["ID"] ?>"><?= htmlspecialcharsbx($data["TITLE"]) ?></p>
				</li>
			<?}
			else
			{?>
				<li class="bx-disk-aggregator-list">
					<img src="<?= $data["ICON"] ?>" class="bx-disk-aggregator-icon-main" />
					<a class="bx-disk-aggregator-a-link" href="<?= $data["URL"] ?>"><?= htmlspecialcharsbx($data["TITLE"]) ?></a>
				</li>
			<?}
		}
		?>
	</ul>
</div>

<input type="hidden" id="bx-disk-da-site-id" value="<?= SITE_ID ?>" />
<input type="hidden" id="bx-disk-da-site-dir" value="<?= SITE_DIR ?>" />

<div id='bx-disk-group-div' style="display:none;" class="bx-disk-aggregator-group-div"></div>
<div id='bx-disk-user-div' style="display:none;" class="bx-disk-aggregator-group-div"></div>

<div class="bx-disk-aggregator-description-div">
	<p><?=Loc::getMessage("DISK_AGGREGATOR_DESCRIPTION") ?></p>
	<p><?=Loc::getMessage("DISK_AGGREGATOR_NETWORK_DRIVE") ?></p>
</div>

<? $linkOnNetworkDrive = CUtil::JSescape($arResult["NETWORK_DRIVE_LINK"]); ?>

<script>
	BX(function () {
		BX.Disk['AggregatorClass_<?= $component->getComponentId() ?>'] = new BX.Disk.AggregatorClass({});
		BX.bind(BX('bx-disk-aggregator-user-link'), 'click', function()
		{
			BX.hide(BX('bx-disk-group-div'));
			BX.Disk['AggregatorClass_<?= $component->getComponentId() ?>'].getListStorage('getListStorage', 'user');
		});
		BX.bind(BX('bx-disk-aggregator-group-link'), 'click', function()
		{
			BX.hide(BX('bx-disk-user-div'));
			BX.Disk['AggregatorClass_<?= $component->getComponentId() ?>'].getListStorage('getListStorage', 'group');
		});
		BX.bind(BX('bx-disk-show-network-drive-url'), 'click', function()
		{
			BX.Disk['AggregatorClass_<?= $component->getComponentId() ?>'].showNetworkDriveConnect({link: '<?= $linkOnNetworkDrive ?>'});
		});
	});
	BX.message({
		DISK_AGGREGATOR_TITLE_NETWORK_DRIVE: '<?= GetMessageJS("DISK_AGGREGATOR_TITLE_NETWORK_DRIVE") ?>',
		DISK_AGGREGATOR_TITLE_NETWORK_DRIVE_DESCR_MODAL: '<?= GetMessageJS("DISK_AGGREGATOR_TITLE_NETWORK_DRIVE_DESCR_MODAL") ?>',
		DISK_AGGREGATOR_BTN_CLOSE: '<?= GetMessageJS("DISK_AGGREGATOR_BTN_CLOSE") ?>'
	});
</script>

<?
// set title buttons
$this->setViewTarget("pagetitle");
?>
	<div class="bx-disk-searchbox">
		<table>
			<tr>
				<td>
					<p class="bx-disk-aggregator-nd-link" id="bx-disk-show-network-drive-url"><?=Loc::getMessage("DISK_AGGREGATOR_ND") ?></p>
				</td>
			</tr>
		</table>
	</div>
<?
$this->endViewTarget();

$APPLICATION->IncludeComponent('bitrix:disk.help.network.drive','');
?>