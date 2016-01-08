<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
global $APPLICATION;
if (!empty($arResult['BUTTONS']))
{
	$type = $arParams['TYPE'];
	$APPLICATION->IncludeComponent(
		'bitrix:crm.interface.toolbar',
		$type === 'list' ?  '' : 'type2',
		array(
			'TOOLBAR_ID' => $arResult['TOOLBAR_ID'],
			'BUTTONS' => $arResult['BUTTONS']
		),
		$component,
		array('HIDE_ICONS' => 'Y')
	);
}

if(isset($arResult['SONET_SUBSCRIBE']) && is_array($arResult['SONET_SUBSCRIBE'])):
	$subscribe = $arResult['SONET_SUBSCRIBE'];
?><script type="text/javascript">
BX.ready(
	function()
	{
		BX.CrmSonetSubscription.create(
			"<?=CUtil::JSEscape($subscribe['ID'])?>",
			{
				"entityType": "<?=CCrmOwnerType::DealName?>",
				"serviceUrl": "<?=CUtil::JSEscape($subscribe['SERVICE_URL'])?>",
				"actionName": "<?=CUtil::JSEscape($subscribe['ACTION_NAME'])?>"
			}
		);
	}
);
</script><?
endif;
