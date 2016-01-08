<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');

if (empty($arResult['CONTACT']))
	echo GetMessage('CRM_DATA_EMPTY');
else
{
	foreach($arResult['CONTACT'] as $arContact)
	{
		$arContact['TITLE'] = CUser::FormatName(
			\Bitrix\Crm\Format\PersonNameFormatter::getFormat(),
			array(
				'NAME' => $arContact['NAME'],
				'LAST_NAME' => $arContact['LAST_NAME'],
				'SECOND_NAME' => $arContact['SECOND_NAME']
			),
			true, false
		);
		?>
		<div class="crm-contact-element">
			<div class="crm-contact-element-date"><?=FormatDate('x', MakeTimeStamp($arContact['DATE_CREATE']), (time() + CTimeZone::GetOffset()))?></div>
			<div class="crm-contact-element-title"><a href="<?=$arContact['PATH_TO_CONTACT_SHOW']?>" id="balloon_<?=$arResult['GADGET_ID']?>_C_<?=$arContact['ID']?>" title="<?=$arContact['TITLE']?>"><?=$arContact['TITLE']?></a></div>
			<div class="crm-contact-element-status"><?=GetMessage('CRM_COLUMN_CONTACT_TYPE')?>: <span><?=$arResult['TYPE_LIST'][$arContact['TYPE_ID']]?></span></div>
		</div>
		<script type="text/javascript">BX.tooltip('CONTACT_<?=$arContact['~ID']?>', "balloon_<?=$arResult['GADGET_ID']?>_C_<?=$arContact['ID']?>", "/bitrix/components/bitrix/crm.contact.show/card.ajax.php", "crm_balloon_contact", true);</script>
		<?
	}
}
?>