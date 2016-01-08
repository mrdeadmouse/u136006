<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
global $APPLICATION;
?><script type="text/javascript">
	BX.ready(
		function()
		{
			BX.CrmEntityFieldSelector.messages =
			{
				"buttonAdd": "<?=GetMessageJS('CRM_MAIL_TEMPLATE_ENTITY_FIELD_SELECTOR_ADD_BTN')?>"
			}
		}
	);
</script><?
$arTabs = array();
$mainTabData = $arResult['FIELDS']['tab_1'];
$mainTabFields = array();
$enityTypeId = '';
foreach($mainTabData as &$data)
{
	$ID = isset($data['ID']) ? $data['ID'] : '';
	if($ID === '')
	{
		continue;
	}

	$f = array(
		'id' => $ID,
		'name' => isset($data['NAME']) ? $data['NAME'] : $ID,
		'value' => isset($data['VALUE']) ? $data['VALUE'] : '',
		'required' => isset($data['REQUIRED']) ? $data['REQUIRED'] : false
	);

	if($ID === 'ENTITY_TYPE_ID')
	{
		$enityTypeId = $f['value'];
	}

	switch($ID)
	{
		case 'IS_ACTIVE':
			$f['type'] = 'checkbox';
			break;
		case 'SORT':
			$f['type'] = 'text';
			$f['params'] = array('size' => 5);
			break;
		case 'TITLE':
		case 'SUBJECT':
		case 'EMAIL_FROM':
			$f['type'] = 'text';
			$f['params'] = array('size' => 50);
			break;
		case 'SCOPE':
		case 'ENTITY_TYPE_ID':
			$f['type'] = 'list';
			$f['items'] = isset($data['ALL_VALUES']) ? $data['ALL_VALUES'] : array();
			break;
		case 'BODY':
			{
				$f['type'] = 'custom';
				$f['colspan'] = true;
				CModule::IncludeModule('fileman');
				ob_start();
				$editor = new CLightHTMLEditor;
				$editor->Show(
					array(
						'id' => 'MailTemplateBodyEditor',
						'height' => '192',
						'BBCode' => true,
						'bUseFileDialogs' => false,
						'bFloatingToolbar' => false,
						'bArisingToolbar' => false,
						'bResizable' => false,
						'jsObjName' => 'oLheMailTemplateBody',
						'bInitByJS' => false,
						'bSaveOnBlur' => false,
						'content' => $f['value'],
						'inputName' => $ID,
						'toolbarConfig' => array(
							'Bold', 'Italic', 'Underline', 'Strike',
							'ForeColor','FontList', 'FontSizeList',
							'RemoveFormat', 'Quote', 'Code',
							'CreateLink', 'DeleteLink', 'Image',
							'Table', 'Justify',
							'InsertOrderedList', 'InsertUnorderedList',
							'Indent', 'Outdent',
							'Source'
						)
					)
				);
				$f['value'] = ob_get_contents();
				ob_end_clean();
			}
			break;
		default:
			$f['type'] = 'text';
	}

	$mainTabFields[] = &$f;
	if($ID === 'ENTITY_TYPE_ID')
	{
		//Add field selector control
		$selectorContainerID = $arResult['FORM_ID'].'_ENTITY_FIELD_SELECTOR';
		$selector = array(
			'id' => 'ENTITY_FIELD_SELECTOR',
			'name' => GetMessage('CRM_MAIL_TEMPLATE_ENTITY_FIELD_SELECTOR'),
			'type' => 'custom',
			'value' => '<span id="'.htmlspecialcharsbx($selectorContainerID).'"></span>'
		);
		$mainTabFields[] = &$selector;
		unset($selector);

		?><script type="text/javascript">
			BX.ready(
				function()
				{
					var form = BX('form_<?= $arResult['FORM_ID']?>');
					BX.bind(form, 'submit',
						function()
						{
							window['oLheMailTemplateBody'].SaveContent();
						}
					);

					var selector = BX.CrmEntityFieldSelector.create(
							'<?=$selectorContainerID?>',
							{
								'editorName': 'oLheMailTemplateBody',
								'map': <?=CUtil::PhpToJSObject(CCrmTemplateManager::GetAllMaps())?>
							}
					);
					selector.registerEntityTypeSelector(
						BX.findChild(
								form,
								{ 'tag': 'SELECT',  'property': { 'name': 'ENTITY_TYPE_ID' } },
								true
						)
					);
					selector.layout(BX('<?=$selectorContainerID?>'));
				}
			);
		</script><?
	}
	unset($f);
}
unset($data);

$arTabs[] = array(
	'id' => 'tab_1',
	'name' => GetMessage('CRM_MAIL_TEMPLATE_TAB_1'),
	'title' => GetMessage('CRM_MAIL_TEMPLATE_TAB_1_TITLE'),
	'icon' => '',
	'fields'=> &$mainTabFields
);
unset($mainTabFields);

CCrmGridOptions::SetTabNames($arResult['FORM_ID'], $arTabs);
$formCustomHtml = '<input type="hidden" name="element_id" value="'.htmlspecialcharsbx($arResult['ELEMENT_ID']).'"/>';
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
		'DATA' => $arResult['ELEMENT'],
		'SHOW_SETTINGS' => 'Y',
		'THEME_GRID_ID' => $arResult['GRID_ID'],
		'SHOW_FORM_TAG' => 'Y'
	),
	$component,
	array('HIDE_ICONS' => 'Y')
);
?>