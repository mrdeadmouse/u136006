<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); ?>

<?

/**
 * @var $USER CUser
 * @var $APPLICATION CMain
 * @var $userPerms CCrmPerms
 */
if (!CModule::IncludeModule("mobileapp"))
	die();
$APPLICATION->SetPageProperty("BodyClass", "menu-page");
$APPLICATION->AddHeadString('<link href="' . CUtil::GetAdditionalFileURL($templateFolder . "/style.css") . '" type="text / css" rel="stylesheet" />');

$bExtranet = (CModule::IncludeModule("extranet") && CExtranet::IsExtranetSite());
$diskEnabled =
	\Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false) &&
	CModule::includeModule('disk');

$initData = array(
	"lang" => array(
		"pulltext" => GetMessage("PULL_TEXT"),
		"downtext" => GetMessage("DOWN_TEXT"),
		"loadtext" => GetMessage("LOAD_TEXT")
	),
	"userId" => $GLOBALS['USER']->GetID(),
	"siteDir" => SITE_DIR,
	"canInvite" => (IsModuleInstalled("bitrix24") && $USER->CanDoOperation('bitrix24_invite')),
	"calendarFirstVisit" => (CUserOptions::GetOption("mobile", "calendar_first_visit", "Y") == "Y"),
	"profileUrl" => SITE_DIR."mobile/users/?ID=".$arResult["USER"]["ID"],
	"helpUrl" => SITE_DIR."mobile/help/"
);
?>
<div class="menu-user" id="menu-user"<? if ($arResult["USER"]["AVATAR"]): ?>style="background: url('<?= $arResult["USER"]["AVATAR"]["src"] ?>') no-repeat; background-size: cover; background-position: center;"<? endif ?>>
	<div class="menu-user-info">
		<div class="menu-user-name"><?=$arResult["USER"]["FULL_NAME"]?></div>
		<div class="menu-user-portal"><?=$arResult["HOST"]?></div>
		<div class="menu-user-login"><?=$arResult["USER"]["LOGIN"]?></div>
	</div>

	<? $showHelpIcon = CMobile::$platform == "ios" && !$bExtranet; ?>
	<div class="menu-user-actions<?if (!$showHelpIcon):?> menu-user-actions-50<?endif?>">
		<div class="menu-user-action menu-user-accounts" id="menu-user-accounts"><span><?=GetMessage("MB_MY_BITRIX24") ?></span></div>
		<?if ($showHelpIcon):?>
		<div class="menu-user-action menu-user-help" id="menu-user-help"><span><?=htmlspecialcharsbx(GetMessage('MB_HELP')) ?></span></div>
		<?endif?>
		<div class="menu-user-action menu-user-logout" id="menu-user-logout"><span><?=htmlspecialcharsbx(GetMessage('MB_EXIT')) ?></span></div>
	</div>
</div>
<div class="menu-items" id="menu-items">

	<div class="menu-separator"><?= GetMessage("MB_SEC_FAVORITE"); ?></div>

	<div class="menu-section">

		<div class="menu-item" id="main_feed" data-url="<?=SITE_DIR?>mobile/" data-bx24ModernStyle="Y" data-pageid="main_feed">
			<div class="menu-item-inner menu-icon-lenta"><?= GetMessage("MB_LIVE_FEED"); ?></div>
			<div class="menu-item-counter" id="menu-counter-live-feed"><span class="menu-item-counter-value"></span><span class="menu-item-counter-plus"></span></div>
		</div>
		<div class="menu-item" id="tasks_list" data-url="<?=SITE_DIR?>mobile/tasks/snmrouter/?routePage=list" data-pageid="tasks_list">
			<div class="menu-item-inner menu-icon-tasks"><?= GetMessage("MB_TASKS_MAIN_MENU_ITEM"); ?></div>
			<div class="menu-item-counter" id="menu-counter-tasks_total"><span class="menu-item-counter-value"></span><span class="menu-item-counter-plus"></span></div>
		</div><?
		if (!$bExtranet)
		{
			?><div class="menu-item" id="bp_list" data-bx24ModernStyle="Y" data-url="<?=SITE_DIR?>mobile/bp/?USER_STATUS=0" data-pageid="bp_list">
				<div class="menu-item-inner menu-icon-bizproc"><?= GetMessage("MB_BP_MAIN_MENU_ITEM"); ?></div>
				<div class="menu-item-counter" id="menu-counter-bp_tasks"><span class="menu-item-counter-value"></span><span class="menu-item-counter-plus"></span></div>
			</div>
			<div class="menu-item" id="calendar_list"
				onclick="MobileMenu.calendarList(<?= $GLOBALS['USER']->GetID(); ?>);">
				<div class="menu-item-inner menu-icon-calendar">
					<?= GetMessage("MB_CALENDAR_LIST"); ?>
				</div>
			</div><?
		}
		?><? if($diskEnabled)
		{
			?><div class="menu-item" id="doc_user"
				onclick="MobileMenu.diskList({type: 'user', entityId: <? echo $GLOBALS['USER']->GetID(); ?>}, '/');">
				<div class="menu-item-inner menu-icon-disk">
					<?= GetMessage("MB_CURRENT_USER_FILES_MAIN_MENU_ITEM_NEW"); ?>
				</div>
			</div><?
		}
		else
		{
			?><div class="menu-item" id="doc_user"
				onclick="MobileMenu.webdavList('user/<? echo $GLOBALS['USER']->GetID(); ?>/');">
				<div class="menu-item-inner menu-icon-disk">
					<?= GetMessage("MB_CURRENT_USER_FILES_MAIN_MENU_ITEM_NEW"); ?>
				</div>
			</div><?
		}
		?>
		<div class="menu-item" onclick="MobileMenu.userList();">
			<div class="menu-item-inner menu-icon-employees">
				<?= GetMessage($bExtranet ? "MB_CONTACTS" : "MB_COMPANY"); ?>
			</div>
		</div><?
		if (!$bExtranet)
		{
			if($diskEnabled)
			{
				?><div class="menu-item" id="doc_shared"
					onclick="MobileMenu.diskList({type: 'common', entityId: 'shared_files_s1'}, '/');">
					<div class="menu-item-inner menu-icon-files">
						<?= GetMessage("MB_SHARED_FILES_MAIN_MENU_ITEM_NEW"); ?>
					</div>
				</div><?
			}
			else
			{
				?><div class="menu-item" id="doc_shared"
					onclick="MobileMenu.webdavList('shared/');">
					<div class="menu-item-inner menu-icon-files">
						<?= GetMessage("MB_SHARED_FILES_MAIN_MENU_ITEM_NEW"); ?>
					</div>
				</div><?
			}
		}
	?></div>
	<?if (
		!$bExtranet
		&& IsModuleInstalled('crm')
		&& CModule::IncludeModule('crm')
		&& CCrmPerms::IsAccessEnabled()
	)
	{
		$userPerms = CCrmPerms::GetCurrentUserPermissions();?>
		<div class="menu-separator">CRM</div>
		<div class="menu-section">
			<div class="menu-item" id="crm_activity_list"
				data-url="/mobile/crm/activity/list.php"
				data-pageid="crm_activity_list">
				<div class="menu-item-inner menu-icon-mybusiness">
					<?= htmlspecialcharsbx(GetMessage('MB_CRM_ACTIVITY')) ?>
				</div>
			</div>
			<? if (!$userPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'READ')): ?>
				<div class="menu-item" id="crm_contact_list"
					data-url="/mobile/crm/contact/list.php"
					data-pageid="crm_contact_list">
					<div class="menu-item-inner menu-icon-contacts">
						<?= htmlspecialcharsbx(GetMessage('MB_CRM_CONTACT')) ?>
					</div>
				</div>
			<? endif; ?>
			<? if (!$userPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE, 'READ')): ?>
				<div class="menu-item" id="crm_company_list"
					data-url="/mobile/crm/company/list.php"
					data-pageid="crm_company_list">
					<div class="menu-item-inner menu-icon-company">
						<?= htmlspecialcharsbx(GetMessage('MB_CRM_COMPANY')) ?>
					</div>
				</div>
			<? endif; ?>
			<? if (!$userPerms->HavePerm('DEAL', BX_CRM_PERM_NONE, 'READ')): ?>
				<div class="menu-item" id="crm_deal_list"
					data-url="/mobile/crm/deal/list.php"
					data-pageid="crm_deal_list">
					<div class="menu-item-inner menu-icon-deals">
						<?= htmlspecialcharsbx(GetMessage('MB_CRM_DEAL')) ?>
					</div>
				</div>
			<? endif; ?>
			<? if (!$userPerms->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'READ')): ?>
				<div class="menu-item" id="crm_invoice_list"
					data-url="/mobile/crm/invoice/list.php"
					data-pageid="crm_invoice_list">
					<div class="menu-item-inner menu-icon-invoice">
						<?= htmlspecialcharsbx(GetMessage('MB_CRM_INVOICE')) ?>
					</div>
				</div>
			<? endif; ?>
			<? if (!$userPerms->HavePerm('LEAD', BX_CRM_PERM_NONE, 'READ')): ?>
				<div class="menu-item" id="crm_lead_list"
					data-url="/mobile/crm/lead/list.php"
					data-pageid="crm_lead_list">
					<div class="menu-item-inner menu-icon-leads">
						<?= htmlspecialcharsbx(GetMessage('MB_CRM_LEAD')) ?>
					</div>

				</div>
			<? endif; ?>
			<? if ($userPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ')): ?>
				<div class="menu-item" id="crm_product_list"
					data-url="/mobile/crm/product/list.php"
					data-pageid="crm_product_list">
					<div class="menu-item-inner menu-icon-products">
						<?= htmlspecialcharsbx(GetMessage('MB_CRM_PRODUCT')) ?>
					</div>
				</div>
			<? endif; ?>
		</div><?
	}

	if (is_array($arResult["GROUP_MENU"]) && count($arResult["GROUP_MENU"]) > 0):
		?>
		<div class="menu-separator"><?= GetMessage("MB_SEC_GROUPS"); ?></div>
		<div class="menu-section menu-section-groups"><?
		foreach ($arResult["GROUP_MENU"] as $key => $value):
			?>
			<div class="menu-item" data-bx24ModernStyle="Y" data-url="<?= $value[1] ?>"><div class="menu-item-inner"><?= $value[0] ?></div></div><?
		endforeach;
		?></div><?
	endif;

	if (
		!$bExtranet
		&& is_array($arResult["EXTRANET_MENU"])
		&& count($arResult["EXTRANET_MENU"]) > 0
	)
	{
		?><div class="menu-separator"><?= GetMessage("MB_SEC_EXTRANET"); ?></div>
		<div class="menu-section menu-section-groups"><?
		foreach ($arResult["EXTRANET_MENU"] as $key => $value):
			?>
			<div class="menu-item" data-bx24ModernStyle="Y" data-url="<?= $value[1] ?>"><div class="menu-item-inner"><?= $value[0] ?></div></div><?
		endforeach;
		?></div><?
	}
	?>

</div>

<script type="text/javascript">

	BX.ready(function ()
	{
		MobileMenu.MenuSettings.setSettings(<?=CUtil::PhpToJsObject($initData)?>);
		MobileMenu.init(null);
	});

</script>
