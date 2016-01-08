<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Настройки");
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
?>
<ul class="config-CRM">
<?if(!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_NONE)):?>
	<li style="color: #3F729C;height: 22px;list-style: none outside none;margin-bottom: 26px;overflow: hidden;">- <a href="<?=SITE_DIR?>crm/configs/status/" style="color: #3F729C;font-family: Arial,sans-serif;font-size: 18px;">Справочники</a></li>
	<li style="color: #3F729C;height: 22px;list-style: none outside none;margin-bottom: 26px;overflow: hidden;">- <a href="<?=SITE_DIR?>crm/configs/currency/" style="color: #3F729C;font-family: Arial,sans-serif;font-size: 18px;">Валюты</a></li>
	<li style="color: #3F729C;height: 22px;list-style: none outside none;margin-bottom: 26px;overflow: hidden;">- <a href="<?=SITE_DIR?>crm/configs/tax/" style="color: #3F729C;font-family: Arial,sans-serif;font-size: 18px;">Налоги</a></li>
	<li style="color: #3F729C;height: 22px;list-style: none outside none;margin-bottom: 26px;overflow: hidden;">- <a href="<?=SITE_DIR?>crm/configs/locations/" style="color: #3F729C;font-family: Arial,sans-serif;font-size: 18px;">Местоположения</a></li>
	<li style="color: #3F729C;height: 22px;list-style: none outside none;margin-bottom: 26px;overflow: hidden;">- <a href="<?=SITE_DIR?>crm/configs/ps/" style="color: #3F729C;font-family: Arial,sans-serif;font-size: 18px;">Способы оплаты</a></li>
	<li style="color: #3F729C;height: 22px;list-style: none outside none;margin-bottom: 26px;overflow: hidden;">- <a href="<?=SITE_DIR?>crm/configs/perms/" style="color: #3F729C;font-family: Arial,sans-serif;font-size: 18px;">Права доступа</a></li>
	<li style="color: #3F729C;height: 22px;list-style: none outside none;margin-bottom: 26px;overflow: hidden;">- <a href="<?=SITE_DIR?>crm/configs/bp/" style="color: #3F729C;font-family: Arial,sans-serif;font-size: 18px;">Бизнес-процессы</a></li>
	<li style="color: #3F729C;height: 22px;list-style: none outside none;margin-bottom: 26px;overflow: hidden;">- <a href="<?=SITE_DIR?>crm/configs/fields/" style="color: #3F729C;font-family: Arial,sans-serif;font-size: 18px;">Пользовательские поля</a></li>
	<li style="color: #3F729C;height: 22px;list-style: none outside none;margin-bottom: 26px;overflow: hidden;">- <a href="<?=SITE_DIR?>crm/configs/config/" style="color: #3F729C;font-family: Arial,sans-serif;font-size: 18px;">Прочее</a></li>
	<li style="color: #3F729C;height: 22px;list-style: none outside none;margin-bottom: 26px;overflow: hidden;">- <a href="<?=SITE_DIR?>crm/configs/sendsave/" style="color: #3F729C;font-family: Arial,sans-serif;font-size: 18px;">Интеграция с почтой</a></li>
	<li style="color: #3F729C;height: 22px;list-style: none outside none;margin-bottom: 26px;overflow: hidden;">- <a href="<?=SITE_DIR?>crm/configs/external_sale/" style="color: #3F729C;font-family: Arial,sans-serif;font-size: 18px;">Интернет-магазины</a></li>
	<li style="color: #3F729C;height: 22px;list-style: none outside none;margin-bottom: 26px;overflow: hidden;">- <a href="<?=SITE_DIR?>crm/configs/exch1c/" style="color: #3F729C;font-family: Arial,sans-serif;font-size: 18px;">Интеграция с &quot;1С:Предприятие&quot;</a></li>
	<li style="color: #3F729C;height: 22px;list-style: none outside none;margin-bottom: 26px;overflow: hidden;">- <a href="<?=SITE_DIR?>crm/configs/info/" style="color: #3F729C;font-family: Arial,sans-serif;font-size: 18px;">Справка</a></li>
<?endif?>
<?if($CrmPerms->IsAccessEnabled()):?>
	<li style="color: #3F729C;height: 22px;list-style: none outside none;margin-bottom: 26px;overflow: hidden;">- <a href="<?=SITE_DIR?>crm/configs/mailtemplate/" style="color: #3F729C;font-family: Arial,sans-serif;font-size: 18px;">Почтовые шаблоны</a></li>
<?endif;?>
</ul>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>