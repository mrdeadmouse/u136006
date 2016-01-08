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

$elementID = isset($arResult['ELEMENT']['ID']) ? $arResult['ELEMENT']['ID'] : 0;

$arResult['CRM_CUSTOM_PAGE_TITLE'] =
	$elementID > 0
	? GetMessage('CRM_DEAL_EDIT_TITLE',
		array(
			'#ID#' => $elementID,
			'#TITLE#' => isset($arResult['ELEMENT']['TITLE']) ? $arResult['ELEMENT']['TITLE'] : ''
		)
	)
	: GetMessage('CRM_DEAL_CREATE_TITLE');

$formCustomHtml = '<input type="hidden" name="deal_id" value="'.$elementID.'"/>'.$arResult['FORM_CUSTOM_HTML'];
$APPLICATION->IncludeComponent(
	'bitrix:crm.interface.form',
	'edit',
	array(
		'FORM_ID' => $arResult['FORM_ID'],
		'GRID_ID' => $arResult['GRID_ID'],
		'TABS' => $arTabs,
		'FIELD_SETS' => array($productFieldset),
		'BUTTONS' => array(
			'standard_buttons' => true,
			'back_url' => $arResult['BACK_URL'],
			'custom_html' => $formCustomHtml
		),
		'IS_NEW' => $elementID <= 0,
		'USER_FIELD_ENTITY_ID' => CCrmDeal::$sUFEntityID,
		'TITLE' => $arResult['CRM_CUSTOM_PAGE_TITLE'],
		'ENABLE_TACTILE_INTERFACE' => 'Y',
		'DATA' => $arResult['ELEMENT'],
		'SHOW_SETTINGS' => 'Y'
	)
);
?>
<script type="text/javascript">

	window.CrmProductRowSetLocation = function(){
		BX.onCustomEvent('CrmProductRowSetLocation', ['LOC_CITY']);
	}

	BX.ready(
		function()
		{
			var formID = 'form_' + '<?= $arResult['FORM_ID'] ?>';
			var form = BX(formID);

			var currencyEl = BX.findChild(form, { 'tag':'select', 'attr':{ 'name': 'CURRENCY_ID' } }, true, false);
			var opportunityEl = BX.findChild(form, { 'tag':'input', 'attr':{ 'name': 'OPPORTUNITY' } }, true, false);

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

			var el = BX("LOC_CITY_val");
			if (el)
				BX.addClass(el, "bx-crm-edit-input");
		}
	);
</script>
