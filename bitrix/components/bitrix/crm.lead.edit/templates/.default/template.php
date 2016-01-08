<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

global $APPLICATION;
$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");

$arTabs = array();
$arTabs[] = array(
	'id' => 'tab_1',
	'name' => GetMessage('CRM_TAB_1'),
	'title' => GetMessage('CRM_TAB_1_TITLE'),
	'icon' => '',
	'fields'=> $arResult['FIELDS']['tab_1']
);

$productFieldset = array();
foreach($arTabs[0]['fields'] as $k => &$field):
	if($field['id'] === 'section_product_rows'):
		$productFieldset['NAME'] = $field['name'];
		unset($arTabs[0]['fields'][$k]);
	endif;

	if($field['id'] === 'PRODUCT_ROWS'):
		$productFieldset['HTML'] = $field['value'];
		unset($arTabs[0]['fields'][$k]);
		break;
	endif;

endforeach;
unset($field);

$entityTypeCategories = CCrmOwnerType::GetAllCategoryCaptions();
$elementID = isset($arResult['ELEMENT']['ID']) ? $arResult['ELEMENT']['ID'] : 0;

$arResult['CRM_CUSTOM_PAGE_TITLE'] =
	$elementID > 0
	? GetMessage('CRM_LEAD_EDIT_TITLE',
		array(
			'#ID#' => $elementID,
			'#TITLE#' => isset($arResult['ELEMENT']['TITLE']) ? $arResult['ELEMENT']['TITLE'] : ''
		)
	)
	: GetMessage('CRM_LEAD_CREATE_TITLE');

$formCustomHtml = '<input type="hidden" name="lead_id" value="'.$elementID.'"/>';
$APPLICATION->IncludeComponent(
	'bitrix:crm.interface.form',
	'edit',
	array(
		'FORM_ID' => $arResult['FORM_ID'],
		'GRID_ID' => $arResult['GRID_ID'],
		'TABS' => $arTabs,
		'EMPHASIZED_HEADERS' => array('TITLE'),
		'FIELD_SETS' => array($productFieldset),
		'BUTTONS' => array(
			'standard_buttons' => true,
			'back_url' => $arResult['BACK_URL'],
			'custom_html' => $formCustomHtml
		),
		'IS_NEW' => $elementID <= 0,
		'USER_FIELD_ENTITY_ID' => CCrmLead::$sUFEntityID,
		'TITLE' => $arResult['CRM_CUSTOM_PAGE_TITLE'],
		'ENABLE_TACTILE_INTERFACE' => 'Y',
		'DATA' => $arResult['ELEMENT'],
		'SHOW_SETTINGS' => 'Y'
	)
);

?><script type="text/javascript">
	BX.ready(
		function()
		{
			var formID = "form_" + "<?=CUtil::JSEscape($arResult['FORM_ID'])?>";
			var form = BX(formID);

			var currencyEl = BX.findChild(form, { "tag": "select", "attr": { "name": "CURRENCY_ID" } }, true, false);
			var opportunityEl = BX.findChild(form, { "tag":"input", "attr": { "name": "OPPORTUNITY" } }, true, false);

			var prodEditor = BX.CrmProductEditor.getDefault();
			if(opportunityEl)
			{
				opportunityEl.disabled = prodEditor.getProductCount() > 0;

				BX.addCustomEvent(
					prodEditor,
					'productAdd',
					function(params)
					{
						opportunityEl.disabled = prodEditor.getProductCount() > 0;
					}
				);

				BX.addCustomEvent(
					prodEditor,
					'productRemove',
					function(params)
					{
						opportunityEl.disabled = prodEditor.getProductCount() > 0;
					}
				);

				BX.addCustomEvent(
					prodEditor,
					'sumTotalChange',
					function(ttl)
					{
						opportunityEl.value = ttl;
					}
				);

				if(currencyEl)
				{
					BX.bind(
						currencyEl,
						'change',
						function()
						{
							var currencyId = currencyEl.value;
							var prevCurrencyId = prodEditor.getCurrencyId();

							prodEditor.setCurrencyId(currencyId);

							var oportunity = opportunityEl.value.length > 0 ? parseFloat(opportunityEl.value) : 0;
							if(isNaN(oportunity))
							{
								oportunity = 0;
							}

							if(prodEditor.getProductCount() == 0 && oportunity !== 0)
							{
								prodEditor.convertMoney(
									parseFloat(opportunityEl.value),
									prevCurrencyId,
									currencyId,
									function(sum)
									{
										opportunityEl.value = sum;
									}
								);
							}
						}
					);
				}
			}
			<?if($arResult['DUPLICATE_CONTROL']['ENABLED']):?>
			BX.CrmDuplicateSummaryPopup.messages =
			{
				title: "<?=GetMessageJS("CRM_LEAD_EDIT_DUP_CTRL_SHORT_SUMMARY_TITLE")?>"
			};

			BX.CrmDuplicateWarningDialog.messages =
			{
				title: "<?=GetMessageJS("CRM_LEAD_EDIT_DUP_CTRL_WARNING_DLG_TITLE")?>",
				acceptButtonTitle: "<?=GetMessageJS("CRM_LEAD_EDIT_DUP_CTRL_WARNING_ACCEPT_BTN_TITLE")?>",
				cancelButtonTitle: "<?=GetMessageJS("CRM_LEAD_EDIT_DUP_CTRL_WARNING_CANCEL_BTN_TITLE")?>"
			};

			BX.CrmEntityType.categoryCaptions =
			{
				"<?=CCrmOwnerType::LeadName?>": "<?=$entityTypeCategories[CCrmOwnerType::Lead]?>",
				"<?=CCrmOwnerType::ContactName?>": "<?=$entityTypeCategories[CCrmOwnerType::Contact]?>",
				"<?=CCrmOwnerType::CompanyName?>": "<?=$entityTypeCategories[CCrmOwnerType::Company]?>",
				"<?=CCrmOwnerType::DealName?>": "<?=$entityTypeCategories[CCrmOwnerType::Deal]?>",
				"<?=CCrmOwnerType::InvoiceName?>": "<?=$entityTypeCategories[CCrmOwnerType::Invoice]?>"
			};
			//DUPLICATE CONTROL
			var dupController = BX.CrmDupController.create(
				(formID.toLowerCase() + "_dup"),
				{
					"serviceUrl": "/bitrix/components/bitrix/crm.lead.edit/ajax.php?&<?=bitrix_sessid_get()?>",
					"entityTypeName": "<?=CUtil::JSEscape(CCrmOwnerType::LeadName)?>",
					"form": formID,
					"submits":
					[
						"<?=CUtil::JSEscape($arResult['FORM_ID'])?>_saveAndView",
						"<?=CUtil::JSEscape($arResult['FORM_ID'])?>_saveAndAdd"
					],
					"groups":
					{
						"fullName":
						{
							"groupType": "fullName",
							"groupSummaryTitle": "<?=GetMessageJS("CRM_LEAD_EDIT_DUP_CTRL_FULL_NAME_SUMMARY_TITLE")?>",
							"name": "<?=CUtil::JSEscape($arResult['DUPLICATE_CONTROL']['NAME_ID'])?>",
							"nameCaption": "<?=CUtil::JSEscape($arResult['DUPLICATE_CONTROL']['NAME_CAPTION_ID'])?>",
							"secondName": "<?=CUtil::JSEscape($arResult['DUPLICATE_CONTROL']['SECOND_NAME_ID'])?>",
							"secondNameCaption": "<?=CUtil::JSEscape($arResult['DUPLICATE_CONTROL']['SECOND_NAME_CAPTION_ID'])?>",
							"lastName": "<?=CUtil::JSEscape($arResult['DUPLICATE_CONTROL']['LAST_NAME_ID'])?>",
							"lastNameCaption": "<?=CUtil::JSEscape($arResult['DUPLICATE_CONTROL']['LAST_NAME_CAPTION_ID'])?>"
						},
						"email":
						{
							"groupType": "communication",
							"groupSummaryTitle": "<?=GetMessageJS("CRM_LEAD_EDIT_DUP_CTRL_EMAIL_SUMMARY_TITLE")?>",
							"communicationType": "EMAIL",
							"editorId": "<?=CUtil::JSEscape($arResult['DUPLICATE_CONTROL']['EMAIL_EDITOR_ID'])?>",
							"editorCaption": "<?=CUtil::JSEscape($arResult['DUPLICATE_CONTROL']['EMAIL_EDITOR_CAPTION_ID'])?>"
						},
						"phone":
						{
							"groupType": "communication",
							"groupSummaryTitle": "<?=GetMessageJS("CRM_LEAD_EDIT_DUP_CTRL_PHONE_SUMMARY_TITLE")?>",
							"communicationType": "PHONE",
							"editorId": "<?=CUtil::JSEscape($arResult['DUPLICATE_CONTROL']['PHONE_EDITOR_ID'])?>",
							"editorCaption": "<?=CUtil::JSEscape($arResult['DUPLICATE_CONTROL']['PHONE_EDITOR_CAPTION_ID'])?>"
						},
						"companyTitle":
						{
							"groupType": "single",
							"groupSummaryTitle": "<?=GetMessageJS("CRM_LEAD_EDIT_DUP_CTRL_COMPANY_TTL_SUMMARY_TITLE")?>",
							"parameterName": "COMPANY_TITLE",
							"element": "<?=CUtil::JSEscape($arResult['DUPLICATE_CONTROL']['COMPANY_TITLE_ID'])?>",
							"elementCaption": "<?=CUtil::JSEscape($arResult['DUPLICATE_CONTROL']['COMPANY_TITLE_CAPTION_ID'])?>"
						}
					}
				}
			);
			<?endif;?>
		}
	);
</script>
