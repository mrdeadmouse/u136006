<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

CJSCore::Init(array("popup"));

/** @global APPLICATION CMain */
global $APPLICATION;

$APPLICATION->AddHeadScript($this->GetFolder().'/payerinfo.js');

$elementID = (isset($arResult['ELEMENT']) && isset($arResult['ELEMENT']['ID'])) ? intval($arResult['ELEMENT']['ID']) : 0;

$arResult['CRM_CUSTOM_PAGE_TITLE'] = GetMessage(
	($elementID > 0) ? 'CRM_INVOICE_SHOW_TITLE' : 'CRM_INVOICE_SHOW_NEW_TITLE',
	array(
		'#ACCOUNT_NUMBER#' => $arResult['ELEMENT']['ACCOUNT_NUMBER'],
		'#ORDER_TOPIC#' => $arResult['ELEMENT']['ORDER_TOPIC']
	)
);

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
	if($field['id'] === 'section_invoice_spec'):
		$productFieldset['NAME'] = $field['name'];
		$productFieldset['REQUIRED'] = $field['required'] === true;
		unset($arTabs[0]['fields'][$k]);
	endif;

	if($field['id'] === 'INVOICE_PRODUCT_ROWS'):
		$productFieldset['HTML'] = $field['value'];
		unset($arTabs[0]['fields'][$k]);
		break;
	endif;

endforeach;
unset($field);

$formCustomHtml = '<input type="hidden" name="invoice_id" value="'.$elementID.'"/>'.$arResult['FORM_CUSTOM_HTML'];
?>
<div class="bx-crm-edit-form-wrapper">
<?
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
		'USER_FIELD_ENTITY_ID' => CCrmInvoice::$sUFEntityID,
		'TITLE' => $arResult['CRM_CUSTOM_PAGE_TITLE'],
		'ENABLE_TACTILE_INTERFACE' => 'Y',
		'ENABLE_USER_FIELD_CREATION' => 'Y',
		'DATA' => $arResult['ELEMENT'],
		'SHOW_SETTINGS' => 'Y'
	)
);
?>
</div>
<script type="text/javascript">

	window.CrmProductRowSetLocation = function(){
		BX.onCustomEvent('CrmProductRowSetLocation', ['LOC_CITY']);
	}

	GLOBAL_CRM_INVOICE_EDIT_ENTITY_CHANGE_HANDLER_DISABLE = false;
	function <?=CUtil::JSEscape($arResult['AJAX_SUBMIT_FUNCTION'])?>(params)
	{
		var ob = null;
		if (params) ob = params;
		if (ob)
		{
			var invoiceForm = BX('form_' + '<?=CUtil::JSEscape($arResult['FORM_ID'])?>');
			if (invoiceForm)
			{
				var inpAjaxFlag = BX.create('input', {'props': {'type': 'hidden', 'value': 'Y', 'name': 'invoiceSubmitAjax'}});
				if (inpAjaxFlag)
				{
					invoiceForm.appendChild(inpAjaxFlag);
					window.<?=CUtil::JSEscape($arResult['FORM_ID'].'_ajax_response_object')?> = ob;
					BX.ajax.submit(invoiceForm, <?=CUtil::JSEscape($arResult['AJAX_SUBMIT_FUNCTION'].'_response')?>);
				}
			}
		}
	}
	function <?=CUtil::JSEscape($arResult['AJAX_SUBMIT_FUNCTION'].'_response')?>()
	{
		var invoiceForm = BX(<?=CUtil::JSEscape('form_'.$arResult['FORM_ID'])?>);
		var fAjaxSubmit = null, ob = null, info = null, paySystems = null;
		if (invoiceForm)
		{
			// clear invoiceSubmitAjax flags
			fAjaxSubmit = BX.findChild(invoiceForm, {'tag': 'input', 'attr': {'name': 'invoiceSubmitAjax'}});
			if (fAjaxSubmit)
			{
				var fAjaxSubmitSibl;
				while (fAjaxSubmitSibl = BX.findNextSibling(fAjaxSubmit, {'tag': 'input', 'attr': {'name': 'invoiceSubmitAjax'}}))
					invoiceForm.removeChild(fAjaxSubmitSibl);
				invoiceForm.removeChild(fAjaxSubmit);

				// remove target attribute after ajax submit
				invoiceForm.removeAttribute('target');
			}

			// clear invoiceMakePayerInfo flags
			var fPayerInfo = BX.findChild(invoiceForm, {'tag': 'input', 'attr': {'name': 'invoiceMakePayerInfo'}});
			if (fPayerInfo)
			{
				var fPayerInfoSibl = null;
				while (fPayerInfoSibl = BX.findNextSibling(fPayerInfo, {'tag': 'input', 'attr': {'name': 'fPayerInfoSibl'}}))
					invoiceForm.removeChild(fPayerInfoSibl);
				invoiceForm.removeChild(fPayerInfo);
			}
		}

		ob = window.<?=CUtil::JSEscape($arResult['FORM_ID'].'_ajax_response_object')?>;
		info = window.<?=CUtil::JSEscape($arResult['FORM_ID'].'_ajax_response')?>;
		if (ob && info)
			BX.onCustomEvent('InvoiceAjaxSubmitResponse', [{'ob': ob, 'info': info}]);
		if (info)
		{
			var payerInfo = BX(<?=CUtil::JSEscape($arResult['PAYER_INFO_FIELD_ID'])?>);
			if (payerInfo)
			{
				if (typeof(info['PAYER_INFO_TEXT']) !== 'undefined')
					payerInfo.innerHTML = (info['PAYER_INFO_TEXT'].length > 0) ? BX.util.htmlspecialchars(info['PAYER_INFO_TEXT']) : '';

				var payerInfoInputs = BX('<?=CUtil::JSEscape($arResult['INVOICE_PROPS_DIV_ID'])?>');
				if (payerInfoInputs)
				{
					if (typeof(info['INVOICE_PROPS_HTML_INPUTS']) !== 'undefined')
						payerInfoInputs.innerHTML = (info['INVOICE_PROPS_HTML_INPUTS'].length > 0) ? info['INVOICE_PROPS_HTML_INPUTS'] : '';
				}
			}
			if (invoiceForm)
			{
				var paySystem = BX.findChild(invoiceForm, { 'tag':'select', 'attr':{ 'name': '<?=CUtil::JSEscape($arResult['PAY_SYSTEMS_LIST_ID'])?>' } }, true, false);
				if (paySystem)
				{
					if (typeof(info['PAY_SYSTEMS_LIST']) !== 'undefined')
						fRewriteSelectFromArray(paySystem, info['PAY_SYSTEMS_LIST']['items'], info['PAY_SYSTEMS_LIST']['value']);
				}
			}
		}
	}
	function fRewriteSelectFromArray(select, data, value)
	{
		var opt, el, i, j;
		var setSelected = false;
		var bMultiple;

		if (!(value instanceof Array)) value = new Array(value);
		if (select)
		{
			bMultiple = !!(select.getAttribute('multiple'));
			while (opt = select.lastChild) select.removeChild(opt);
			for (i in data)
			{
				el = document.createElement("option")
				el.value = data[i]['value'];
				el.innerHTML = data[i]['text'];
				try
				{
					// for IE earlier than version 8
					select.add(el,select.options[null]);
				}
				catch (e)
				{
					el = document.createElement("option")
					el.text = data[i]['text'];
					select.add(el,null);
				}
				if (!setSelected || bMultiple)
				{
					for (j in value)
					{
						if (data[i]['value'] == value[j])
						{
							el.selected = true;
							if (!setSelected)
							{
								setSelected = true;
								select.selectedIndex = i;
							}
							break;
						}
					}
				}
			}
		}
	}
	BX.ready(function () {
		var formObj = bxForm_<?=$arResult["FORM_ID"]?>;
		if (formObj && typeof(formObj) === "object")
		{
			formObj.EnableSigleSubmit(false);
		}

		var el = BX('LOC_CITY_val');
		if (el)
			BX.addClass(el, 'bx-crm-edit-input');

		BX.addCustomEvent('InvoiceSumTotalChange', <?=CUtil::JSEscape($arResult['AJAX_SUBMIT_FUNCTION'])?>);

		BX.addCustomEvent(
			'CrmEntitySelectorChangeValue',
			function (id, type, value, entityEditor) {
				if (GLOBAL_CRM_INVOICE_EDIT_ENTITY_CHANGE_HANDLER_DISABLE)
					return;
				if (type !== 'COMPANY' && type !== 'CONTACT') return;
				var payerInfo = BX(<?=CUtil::JSEscape($arResult['PAYER_INFO_FIELD_ID'])?>);
				if (payerInfo)
					payerInfo.innerHTML = '';
				var invoiceForm = BX(<?=CUtil::JSEscape('form_'.$arResult['FORM_ID'])?>);
				if (invoiceForm)
				{
					var paySystem = BX.findChild(invoiceForm, { 'tag':'select', 'attr':{ 'name': '<?=CUtil::JSEscape($arResult['PAY_SYSTEMS_LIST_ID'])?>' } }, true, false);
					if (paySystem)
					{
						fRewriteSelectFromArray(paySystem, [], []);
					}
					var makePayerInfoFlag = BX.create('input', {'props': {'type': 'hidden', 'value': 'Y', 'name': 'invoiceMakePayerInfo'}});
					if (makePayerInfoFlag)
					{
						invoiceForm.appendChild(makePayerInfoFlag);
						BX.onCustomEvent('InitiateInvoiceSumTotalChange');
					}
					var payerInfoInputs = BX('<?=CUtil::JSEscape($arResult['INVOICE_PROPS_DIV_ID'])?>');
					if (payerInfoInputs)
					{
						payerInfoInputs.innerHTML = '';
					}
				}
			}
		);

		<?if(CCrmPaySystem::isUserMustFillPSProps()):?>

			(function crmInvoicePSPropsDialog()
			{
				var d =
					new BX.CDialog(
						{
							title: "<?=GetMessage('CRM_INVOICE_PS_PROPS_TITLE')?>",
							head: "",
							content: "<?=GetMessage('CRM_INVOICE_PS_PROPS_CONTENT')?>",
							resizable: false,
							draggable: true,
							height: 70,
							width: 350
						}
					);

				var _BTN = [

					{
						title: "<?=GetMessage('CRM_INVOICE_PS_PROPS_GOTO')?>",
						id: "crmPSPropsCreate",
						"action": function()
						{
							window.location.href = "/crm/configs/ps/";
							BX.WindowManager.Get().Close();
						}
					},

					BX.CDialog.btnCancel
				];
				d.ClearButtons();
				d.SetButtons(_BTN);
				d.Show();
			})();

			<?CCrmPaySystem::markPSFillPropsDialogAsViewed();
		endif;?>

	});

	<?php
	if (isset($arResult['PRODUCT_ROWS']) && count($arResult['PRODUCT_ROWS']) > 0)
	{
		echo PHP_EOL.
			"\t".'BX.ready(function () {'.PHP_EOL.
			"\t\t".'BX.onCustomEvent(\'InitiateInvoiceSumTotalChange\');'.PHP_EOL.
			"\t".'})';
	}
	?>

</script><?php
// ---------------------- processing of status change ---------------------->
?>
<script type="text/javascript">

	function onCrmInvoiceEditStatusChange()
	{
		var statusSort = <?= CUtil::PhpToJSObject($arResult['STATUS_SORT']) ?>;
		var form = BX('<?= CUtil::JSEscape('form_'.$arResult['FORM_ID']) ?>');
		if (form)
		{
			var payVoucherNum = BX.findChild(form, {"tag": "input", "attr": {"type": "text", "name": "PAY_VOUCHER_NUM"}}, true, false);
			var statusSelect = BX.findChild(form, {"tag": "select", "attr": {"name": "STATUS_ID"}}, true, false);
			var payVoucherDate = BX("PAY_VOUCHER_DATE");
			var reasonMarkedSuccess = BX.findChild(form, {"tag": "textarea", "attr": {"name": "REASON_MARKED_SUCCESS"}}, true, false);
			var dateMarked = BX("DATE_MARKED");
			var reasonMarked = BX.findChild(form, {"tag": "textarea", "attr": {"name": "REASON_MARKED"}}, true, false);
			var statusId = null, isSuccess = false, isFailed = false, block = null;
			if (statusSelect &&
				payVoucherDate && payVoucherNum && reasonMarkedSuccess &&
				dateMarked && reasonMarked)
			{
				statusId = statusSelect.value;
				if (typeof(statusId) === "string" && statusId.length > 0)
				{
					isSuccess = (statusId === "P");
					if (isSuccess)
						isFailed = false;
					else
						isFailed = (statusSort[statusId] >= statusSort["D"]);

					var successElements = [payVoucherDate, payVoucherNum, reasonMarkedSuccess];
					var failedElements = [dateMarked, reasonMarked];
					for (var i in successElements)
					{
						block =  BX.findParent(successElements[i], {"tag": "tr", "attr": {"class": "crm-offer-row"}})
						if (block)
							block.style.display = isSuccess ? "" : "none";
					}
					for (var i in failedElements)
					{
						block =  BX.findParent(failedElements[i], {"tag": "tr", "attr": {"class": "crm-offer-row"}})
						if (block)
							block.style.display = isFailed ? "" : "none";
					}
				}
			}
		}
	}

	BX.ready(function () {
		onCrmInvoiceEditStatusChange();
		var form = BX('<?= CUtil::JSEscape('form_'.$arResult['FORM_ID']) ?>');
		if (form)
		{
			var statusSelect = BX.findChild(form, {"tag": "select", "attr": {"name": "STATUS_ID"}}, true, false);
			if (statusSelect)
			{
				BX.bind(statusSelect, "change", onCrmInvoiceEditStatusChange)
			}
		}
	});

</script>
<?php
// <---------------------- processing of status change ----------------------
// ---------------------- processing of pay system select ---------------------->
?><script type="text/javascript">
	BX.ready(function () {
		var companyId = <?= CUtil::JSEscape(intval($arResult['ELEMENT']['UF_COMPANY_ID'])) ?>;
		var contactId = <?= CUtil::JSEscape(intval($arResult['ELEMENT']['UF_CONTACT_ID'])) ?>;
		var form = BX('<?= CUtil::JSEscape('form_'.$arResult['FORM_ID']) ?>');
		var payerInfoEditLink = BX(<?=CUtil::JSEscape($arResult['PAYER_INFO_EDIT_LINK_ID'])?>);
		var linkContainer = (payerInfoEditLink) ? payerInfoEditLink.parentNode : null;
		if (form)
		{
			form["crmInvoiceCompanyId"] = companyId;
			form["crmInvoiceContactId"] = contactId;

			var contactPersonContainer = BX('<?=CUtil::JSEscape($arResult['CONTACT_PERSON_CONTAINER_ID'])?>');
			var contactPersonBlock = null;
			if (contactPersonContainer)
			{
				contactPersonBlock =  BX.findParent(contactPersonContainer, {"tag": "tr", "attr": {"class": "crm-offer-row"}});
				if (contactPersonBlock && companyId <= 0)
					contactPersonBlock.style.display = "none";
			}


			var paySystemSelect = BX("PAY_SYSTEM_SELECT");
			if (paySystemSelect)
			{
				if (companyId <= 0 && contactId <= 0)
				{
					paySystemSelect.setAttribute("disabled", "true");
					if (linkContainer)
						linkContainer.style.display = 'none';
				}
				var paySystemHint = BX.create("DIV",
					{
						"attrs": {"class": "bx-crm-edit-content-location-description"},
						"children":
						[
							BX.create("SPAN",
							{
								"attrs": {"class": "bx-crm-edit-content-location-description"},
								"text": "<?= CUtil::JSEscape($arResult['PAY_SYSTEM_ID_TITLE']) ?>"
							})
						]
					}
				);
				var paySystemContainer = BX.findParent(paySystemSelect, {"tag": "td", "class": "crm-offer-info-right"});
				if (paySystemContainer)
					paySystemContainer.appendChild(paySystemHint);

				BX.addCustomEvent(
					"CrmEntitySelectorChangeValue",
					function (id, type, value, entityEditor) {
						if (GLOBAL_CRM_INVOICE_EDIT_ENTITY_CHANGE_HANDLER_DISABLE)
							return;
						if (type !== "COMPANY" && type !== "CONTACT") return;
						var form = BX("<?= CUtil::JSEscape('form_'.$arResult['FORM_ID']) ?>");
						var contactPersonContainer = BX('<?=CUtil::JSEscape($arResult['CONTACT_PERSON_CONTAINER_ID'])?>');
						var paySystemSelect = BX("PAY_SYSTEM_SELECT");
						var payerInfoEditLink = BX(<?=CUtil::JSEscape($arResult['PAYER_INFO_EDIT_LINK_ID'])?>);
						var linkContainer = (payerInfoEditLink) ? payerInfoEditLink.parentNode : null;
						if (form)
						{
							var inpId = entityEditor.getSetting("dataInputId", "").toString();
							var bClient = false;
							var contactPersonBlock = null;
							var entityId = value.toString();
							var nPos = entityId.indexOf("_");

							if (nPos)
								nPos = parseInt(entityId.substring(nPos + 1));
							entityId = nPos ? nPos : 0;

							if (inpId.length >= 9)
							{
								bClient = !!(inpId.substring(inpId.length-9, inpId.length) === "CLIENT_ID");
								clientInp = BX(inpId);
							}
							if (type === "COMPANY")
							{
								form["crmInvoiceCompanyId"] = parseInt(entityId);
								if (bClient)
								{
									if (contactPersonContainer)
									{
										contactPersonBlock =  BX.findParent(contactPersonContainer, {"tag": "tr", "attr": {"class": "crm-offer-row"}});
										if (contactPersonBlock)
											contactPersonBlock.style.display = (form["crmInvoiceCompanyId"] > 0) ? "" : "none";
									}
									form["crmInvoiceContactId"] = 0;
									if (clientInp)
									{
										curClientVal = clientInp.value.toString();
										if (curClientVal.substring(0,3) !== 'CO_')
											clientInp.value = "CO_" + clientInp.value;
									}
								}
							}
							else if (type === "CONTACT")
							{
								form["crmInvoiceContactId"] = parseInt(entityId);
								if (bClient)
								{
									if (contactPersonContainer)
									{
										contactPersonBlock =  BX.findParent(contactPersonContainer, {"tag": "tr", "attr": {"class": "crm-offer-row"}});
										if (contactPersonBlock)
											contactPersonBlock.style.display = "none";
									}
									form["crmInvoiceCompanyId"] = 0;
									if (clientInp)
									{
										curClientVal = clientInp.value.toString();
										if (curClientVal.substring(0,2) !== 'C_')
											clientInp.value = "C_" + clientInp.value;
									}
								}
							}
							if (bClient)
							{
								var createEntitiesContainer = BX("<?=$arResult['CLIENT_CREATE_ENTITIES_CONTAINER_ID']?>");
								if (createEntitiesContainer)
								{
									if (entityId)
										createEntitiesContainer.style.display = "none";
									else createEntitiesContainer.style.display = "";
								}
								GLOBAL_CRM_INVOICE_EDIT_ENTITY_CHANGE_HANDLER_DISABLE = true;
								BX.CrmEntityEditor.items["<?=CUtil::JSEscape($arResult['CONTACT_PERSON_ENTITY_EDITOR_ID'])?>"]._onDeleteButtonClick(null);
								GLOBAL_CRM_INVOICE_EDIT_ENTITY_CHANGE_HANDLER_DISABLE = false;
							}
							var personTypeName = "UNKNOWN";

							if (form["crmInvoiceCompanyId"] > 0)
								personTypeName = "COMPANY";
							else if (form["crmInvoiceContactId"] > 0)
								personTypeName = "CONTACT";
							BX.onCustomEvent('CrmInvoiceChangePersonType', [personTypeName]);

							if (paySystemSelect)
							{
								if (form["crmInvoiceCompanyId"] > 0 || form["crmInvoiceContactId"] > 0)
								{
									paySystemSelect.removeAttribute("disabled");
									paySystemSelect.removeAttribute("title");
									if (linkContainer)
										linkContainer.style.display = '';
								}
								else
								{
									paySystemSelect.setAttribute("disabled", "true");
									if (linkContainer)
										linkContainer.style.display = 'none';
								}
							}
						}
					}
				);
			}
		}
	});
</script><?php
// <---------------------- processing of pay system select ----------------------
?><?php
// ---------------------- processing invoice properties dialog ---------------------->
?><script type="text/javascript">
	BX.ready(function () {
		var settings = <?= CUtil::PhpToJSObject($arResult['INVOICE_PROPS_DLG_SETTINGS']) ?>;
		var dlg = new BX.CrmInvoicePropertiesDialog(settings);
	});
</script><?php
// <---------------------- processing invoice properties dialog ----------------------
