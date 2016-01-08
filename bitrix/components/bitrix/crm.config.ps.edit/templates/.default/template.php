<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

global $APPLICATION;
$arTabs = array();
$arTabs[] = array(
	'id' => 'tab_props',
	'name' => GetMessage('CRM_TAB_1'),
	'title' => GetMessage('CRM_TAB_1_TITLE'),
	'icon' => '',
	'fields'=> $arResult['FIELDS']['tab_props']
);

CCrmGridOptions::SetTabNames($arResult['FORM_ID'], $arTabs);

$formCustomHtml = '<input type="hidden" name="ps_id" value="'.$arResult['PS_ID'].'"/>'."\n".
	'<input type="hidden" name="PS_ACTION_FIELDS_LIST" value="'.$arResult['ACTION_FIELDS_LIST'].'"/>';
$APPLICATION->IncludeComponent(
	'bitrix:main.interface.form',
	'',
	array(
		'FORM_ID' => $arResult['FORM_ID'],
		'TABS' => $arTabs,
		'BUTTONS' => array(
			'standard_buttons' => true,
			'back_url' => $arResult['BACK_URL'],
			'custom_html' => $formCustomHtml
		),
		'DATA' => $arResult['PAY_SYSTEM'],
		'SHOW_SETTINGS' => 'Y',
		'THEME_GRID_ID' => $arResult['GRID_ID'],
		'SHOW_FORM_TAG' => 'Y'
	),
	$component,
	array('HIDE_ICONS' => 'Y')
);

$typeValuesTmpl = '<select name="TYPE_#FIELD_ID#" id="TYPE_#FIELD_ID#">'.
					'<option value="">'.GetMessage("CRM_PS_TYPES_OTHER").'</option>'.
					//'<option value="USER">'.GetMessage("CRM_PS_TYPES_USER").'</option>'.
					'<option value="ORDER">'.GetMessage("CRM_PS_TYPES_ORDER").'</option>'.
					'<option value="PROPERTY">'.GetMessage("CRM_PS_TYPES_PROPERTY").'</option>'.
					'</select>&nbsp;'.
					'<select name="VALUE1_#FIELD_ID#" id="VALUE1_#FIELD_ID#"></select>'.
					'<input type="text" value="" name="VALUE2_#FIELD_ID#" id="VALUE2_#FIELD_ID#" size="40">';

$fileValuesTmpl = '<select name="TYPE_#FIELD_ID#" id="TYPE_#FIELD_ID#" style="display: none;">'.
					'<option selected value="FILE"></option>'.
					'</select>&nbsp;'.
					'<input type="file" name="VALUE1_#FIELD_ID#" id="VALUE1_#FIELD_ID#" size="40">'.
					'<span id="#FIELD_ID#_preview"><br><img id="#FIELD_ID#_preview_img" >'.
					'<br><input type="checkbox" name="#FIELD_ID#_del" value="Y" id="#FIELD_ID#_del" >'.
					'<label for="#FIELD_ID#_del">' . GetMessage("CRM_PS_DEL_FILE") . '</label></span>';

$selectValuesTmpl = '<select name="TYPE_#FIELD_ID#" id="TYPE_#FIELD_ID#" style="display: none;">'.
					'<option selected value="SELECT"></option>'.
					'</select>&nbsp;'.
					'<select name="VALUE1_#FIELD_ID#" id="VALUE1_#FIELD_ID#"></select>';
?>

<script type="text/javascript">

	BX.message({
		CRM_PS_SHOW_FIELDS: "<?=GetMessage("CRM_PS_SHOW_FIELDS")?>",
		CRM_PS_HIDE_FIELDS: "<?=GetMessage("CRM_PS_HIDE_FIELDS")?>"
	});

	BX.crmPaySys.init({
		orderProps: <?=CUtil::PhpToJsObject(CCrmPaySystem::getOrderPropsList())?>,
		orderFields: <?=CUtil::PhpToJsObject(CCrmPaySystem::getOrderFieldsList())?>,
		userProps: <?=CUtil::PhpToJsObject(CCrmPaySystem::getUserPropsList())?>,
		userFields: <?=CUtil::PhpToJsObject($arResult['USER_FIELDS'])?>,
		formId: "form_<?=$arResult['FORM_ID']?>",
		simpleMode: <?=($arResult['SIMPLE_MODE'] ? true : false)?>,
		url: "<?=$componentPath?>"
	});

	BX.crmPSPersonType.init();
	BX.crmPSPropType.init();
	BX.crmPSActionFile.init({
		arFields : {
			<?=CUtil::PhpToJsObject($arResult['ACTION_FILE'])?>: <?=CUtil::PhpToJsObject($arResult['PS_ACT_FIELDS'])?>
		},
		arFieldsList : {
			<?=CUtil::PhpToJsObject($arResult['ACTION_FILE'])?>: <?=CUtil::PhpToJsObject($arResult['ACTION_FIELDS_LIST'])?>
		},
		typeValuesTmpl: "<?=CUtil::JSEscape($typeValuesTmpl)?>",
		fileValuesTmpl: "<?=CUtil::JSEscape($fileValuesTmpl)?>",
		selectValuesTmpl: "<?=CUtil::JSEscape($selectValuesTmpl)?>"
	});

</script>