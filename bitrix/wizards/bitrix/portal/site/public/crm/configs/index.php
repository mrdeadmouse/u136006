<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/crm/configs/index.php");
$APPLICATION->SetTitle(GetMessage("CRM_TITLE"));
if (CModule::IncludeModule("crm")):
	$APPLICATION->IncludeComponent(
		'bitrix:crm.control_panel',
		'',
		array(
			'ID' => 'CONFIG',
			'ACTIVE_ITEM_ID' => ''
		),
		$component
	);
	$CrmPerms = CCrmPerms::GetCurrentUserPermissions();
	?><ul class="config-CRM"><?
	if(!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_NONE))
	{
		?><li style="color: #3F729C;height: 22px;list-style: none outside none;margin-bottom: 26px;overflow: hidden;">- <a href="<?=SITE_DIR?>crm/configs/status/" style="color: #3F729C;font-family: Arial,sans-serif;font-size: 18px;"><?=GetMessage("CRM_MENU_STATUS")?></a></li>
		<li style="color: #3F729C;height: 22px;list-style: none outside none;margin-bottom: 26px;overflow: hidden;">- <a href="<?=SITE_DIR?>crm/configs/currency/" style="color: #3F729C;font-family: Arial,sans-serif;font-size: 18px;"><?=GetMessage("CRM_MENU_CURRENCY")?></a></li>
		<li style="color: #3F729C;height: 22px;list-style: none outside none;margin-bottom: 26px;overflow: hidden;">- <a href="<?=SITE_DIR?>crm/configs/tax/" style="color: #3F729C;font-family: Arial,sans-serif;font-size: 18px;"><?=GetMessage("CRM_MENU_TAX")?></a></li>
		<li style="color: #3F729C;height: 22px;list-style: none outside none;margin-bottom: 26px;overflow: hidden;">- <a href="<?=SITE_DIR?>crm/configs/locations/" style="color: #3F729C;font-family: Arial,sans-serif;font-size: 18px;"><?=GetMessage("CRM_MENU_LOCATIONS")?></a></li>
		<li style="color: #3F729C;height: 22px;list-style: none outside none;margin-bottom: 26px;overflow: hidden;">- <a href="<?=SITE_DIR?>crm/configs/ps/" style="color: #3F729C;font-family: Arial,sans-serif;font-size: 18px;"><?=GetMessage("CRM_MENU_PS")?></a></li>
		<li style="color: #3F729C;height: 22px;list-style: none outside none;margin-bottom: 26px;overflow: hidden;">- <a href="<?=SITE_DIR?>crm/configs/perms/" style="color: #3F729C;font-family: Arial,sans-serif;font-size: 18px;"><?=GetMessage("CRM_MENU_PERMS")?></a></li>
		<li style="color: #3F729C;height: 22px;list-style: none outside none;margin-bottom: 26px;overflow: hidden;">- <a href="<?=SITE_DIR?>crm/configs/bp/" style="color: #3F729C;font-family: Arial,sans-serif;font-size: 18px;"><?=GetMessage("CRM_MENU_BP")?></a></li>
		<li style="color: #3F729C;height: 22px;list-style: none outside none;margin-bottom: 26px;overflow: hidden;">- <a href="<?=SITE_DIR?>crm/configs/fields/" style="color: #3F729C;font-family: Arial,sans-serif;font-size: 18px;"><?=GetMessage("CRM_MENU_FIELDS")?></a></li>
		<li style="color: #3F729C;height: 22px;list-style: none outside none;margin-bottom: 26px;overflow: hidden;">- <a href="<?=SITE_DIR?>crm/configs/productprops/" style="color: #3F729C;font-family: Arial,sans-serif;font-size: 18px;"><?=GetMessage("CRM_MENU_PRODUCT_PROPS")?></a></li>
		<li style="color: #3F729C;height: 22px;list-style: none outside none;margin-bottom: 26px;overflow: hidden;">- <a href="<?=SITE_DIR?>crm/configs/config/" style="color: #3F729C;font-family: Arial,sans-serif;font-size: 18px;"><?=GetMessage("CRM_MENU_CONFIG")?></a></li>
		<li style="color: #3F729C;height: 22px;list-style: none outside none;margin-bottom: 26px;overflow: hidden;">- <a href="<?=SITE_DIR?>crm/configs/sendsave/" style="color: #3F729C;font-family: Arial,sans-serif;font-size: 18px;"><?=GetMessage("CRM_MENU_SENDSAVE")?></a></li>
		<li style="color: #3F729C;height: 22px;list-style: none outside none;margin-bottom: 26px;overflow: hidden;">- <a href="<?=SITE_DIR?>crm/configs/external_sale/" style="color: #3F729C;font-family: Arial,sans-serif;font-size: 18px;"><?=GetMessage("CRM_MENU_SALE")?></a></li>
		<li style="color: #3F729C;height: 22px;list-style: none outside none;margin-bottom: 26px;overflow: hidden;">- <a href="<?=SITE_DIR?>crm/configs/measure/" style="color: #3F729C;font-family: Arial,sans-serif;font-size: 18px;"><?=GetMessage("CRM_MENU_MEASURE")?></a></li>
		<?if (LANGUAGE_ID == "ru"):?>
		<li style="color: #3F729C;height: 22px;list-style: none outside none;margin-bottom: 26px;overflow: hidden;">- <a href="<?=SITE_DIR?>crm/configs/exch1c/" style="color: #3F729C;font-family: Arial,sans-serif;font-size: 18px;"><?=GetMessage("CRM_MENU_EXCH1C")?></a></li>
		<li style="color: #3F729C;height: 22px;list-style: none outside none;margin-bottom: 26px;overflow: hidden;">- <a href="<?=SITE_DIR?>crm/configs/info/" style="color: #3F729C;font-family: Arial,sans-serif;font-size: 18px;"><?=GetMessage("CRM_MENU_INFO")?></a></li>
		<?endif?>
	<?
	}
	if($CrmPerms->IsAccessEnabled()):
		?><li style="color: #3F729C;height: 22px;list-style: none outside none;margin-bottom: 26px;overflow: hidden;">- <a href="<?=SITE_DIR?>crm/configs/mailtemplate/" style="color: #3F729C;font-family: Arial,sans-serif;font-size: 18px;"><?=GetMessage("CRM_MENU_MAILTEMPLATE")?></a></li><?
	endif;
	?></ul><?
endif;
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
?>
