<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
global $APPLICATION;
$APPLICATION->AddHeadScript('/bitrix/js/crm/interface_form.js');
CUtil::InitJSCore();

$arTabs = array();
$arTabs[] = array(
	'id' => 'tab_1',
	'name' => GetMessage('CRM_TAB_1'),
	'title' => GetMessage('CRM_TAB_1_TITLE'),
	'icon' => '',
	'fields'=> $arResult['FIELDS']['tab_1']
);
$arTabs[] = array(
	'id' => 'tab_2',
	'name' => GetMessage('CRM_TAB_2'),
	'title' => GetMessage('CRM_TAB_2_TITLE'),
	'icon' => '',
	'fields'=> $arResult['FIELDS']['tab_2'],
);
$arTabs[] = array(
	'id' => 'tab_3',
	'name' => GetMessage('CRM_TAB_DUP_CONTROL'),
	'title' => GetMessage('CRM_TAB_DUP_CONTROL_TITLE'),
	'icon' => '',
	'fields'=> $arResult['FIELDS']['tab_3'],
);
$arTabs[] = array(
	'id' => 'tab_4',
	'name' => GetMessage('CRM_TAB_3'),
	'title' => GetMessage('CRM_TAB_3_TITLE'),
	'icon' => '',
	'fields'=> $arResult['FIELDS']['tab_4'],
);

$customButtons = '';
if ($arResult['STEP'] == 2 || $arResult['STEP'] == 3)
	$customButtons .= '<input type="submit" name="previous" value="'.GetMessage("CRM_IMPORT_PREVIOUS_STEP").'" title="'.GetMessage("CRM_IMPORT_PREVIOUS_STEP_TITLE").'" />';
if ($arResult['STEP'] == 4)
{
	$customButtons .= '<input type="submit" name="next" value="'.GetMessage("CRM_IMPORT_DONE").'" title="'.GetMessage("CRM_IMPORT_DONE_TITLE").'" disabled="true" id="crm_import_done"/>';
	$customButtons .= '<input type="submit" name="previous" value="'.GetMessage("CRM_IMPORT_AGAIN").'" title="'.GetMessage("CRM_IMPORT_AGAIN_TITLE").'" hidden="true" id="crm_import_again" style="margin-left: 10px"/>';
}
else
	$customButtons .= '<input type="submit" name="next" value="'.GetMessage("CRM_IMPORT_NEXT_STEP").'" title="'.GetMessage("CRM_IMPORT_NEXT_STEP_TITLE").'" />';
if ($arResult['STEP'] < 4)
	$customButtons .= '&nbsp;&nbsp;<input type="submit" name="cancel" value="'.GetMessage("CRM_IMPORT_CANCEL").'" title="'.GetMessage("CRM_IMPORT_CANCEL_TITLE").'" />';

$customButtons .= '<input type="hidden" name="step" value="'.$arResult['STEP'].'"  />';

$arUserSearchFields = array();
foreach($arTabs as &$tab)
{
	if($tab['id'] !== 'tab_1')
	{
		continue;
	}

	foreach($tab['fields'] as &$field)
	{
		$type = isset($field['type']) ? $field['type'] : '';
		if($type !== 'intranet_user_search')
		{
			continue;
		}

		$value = isset($field['value']) ? $field['value'] : '';
		$params = isset($field['componentParams']) ? $field['componentParams'] : array();
		if($value !== '')
		{
			$dbUsers = CUser::GetList($by = 'ID', $order = 'ASC', array('ID'=> $value), array('FIELDS' => array('ID', 'LOGIN', 'NAME', 'LAST_NAME', 'SECOND_NAME')));
			if($user = $dbUsers->Fetch())
			{
				$params['USER'] = $user;
			}
		}
		$arUserSearchFields[] = $params;

		$searchInputName = isset($params['SEARCH_INPUT_NAME']) ? $params['SEARCH_INPUT_NAME'] : 'search_user_name';
		$inputName = isset($params['INPUT_NAME']) ? $params['INPUT_NAME'] : 'user_id';

		ob_start();
		$APPLICATION->IncludeComponent(
			'bitrix:intranet.user.selector.new',
			'',
			array(
				'MULTIPLE' => 'N',
				'NAME' => isset($params['NAME']) ? $params['NAME'] : '',
				'INPUT_NAME' => $searchInputName,
				'NAME_TEMPLATE' => isset($params['NAME_TEMPLATE']) ? $params['NAME_TEMPLATE'] : CSite::GetNameFormat(false),
				'POPUP' => 'Y',
				'SITE_ID' => SITE_ID
			),
			null,
			array('HIDE_ICONS' => 'Y')
		);

		$userSelectorHtml = ob_get_contents();
		ob_end_clean();

		unset($field['componentParams']);
		$field['type'] = 'custom';
		$field['value'] = '<input type="text" class="bx-crm-edit-input" name="'.$searchInputName.'"><input type="hidden" name="'.$inputName.'" value="'.$value.'">';
		$field['value'] .= $userSelectorHtml;
	}
	unset($field);
}
unset($tab);

$APPLICATION->IncludeComponent(
	'bitrix:main.interface.form',
	'',
	array(
		'FORM_ID' => $arResult['FORM_ID'],
		'TABS' => $arTabs,
		'BUTTONS' => array(
			'standard_buttons' =>  false,
			'custom_html' => $customButtons,
		),
		'DATA' => array(),
		'SHOW_SETTINGS' => 'N'
	),
	$component, array('HIDE_ICONS' => 'Y')
);

if(!empty($arUserSearchFields)):
?><script type="text/javascript">
	BX.ready(
		function()
		{<?
			foreach($arUserSearchFields as &$arField):
				$arUserData = array();
				if(isset($arField['USER'])):
					$nameFormat = isset($arField['NAME_TEMPLATE']) ? $arField['NAME_TEMPLATE'] : '';
					if($nameFormat === '')
						$nameFormat = CSite::GetNameFormat(false);
					$arUserData['id'] = $arField['USER']['ID'];
					$arUserData['name'] = CUser::FormatName($nameFormat, $arField['USER'], true, false);
				endif;
			?>BX.CrmUserSearchField.create(
				'<?=$arField['NAME']?>',
				document.getElementsByName('<?=$arField['SEARCH_INPUT_NAME']?>')[0],
				document.getElementsByName('<?=$arField['INPUT_NAME']?>')[0],
				'<?=$arField['NAME']?>',
				<?= CUtil::PhpToJSObject($arUserData)?>
			);<?
			endforeach;
			unset($arField);
		?>}
	);
</script><?
endif;
?>
<script type="text/javascript">
	BX.ready(
		function()
		{
			crmImportStep(<?=$arResult['STEP']?>, '<?=$arResult['FORM_ID']?>');
			BX.remove(BX('bxForm_<?=$arResult['FORM_ID']?>_expand_link'));

			BX.CrmFileImportConfig.create(
				"crm_company_file_import_config",
				{
					dupControlTypes: <?=CUtil::PhpToJSObject($arResult['DUP_CONTROL_TYPES'])?>,
					dupControlPrefix: "<?=$arResult['DUP_CONTROL_PREFIX']?>",
					dupControlTypeDescrId: "<?=$arResult['DUP_CONTROL_TYPE_DESCR_ID']?>"
				}
			);
		}
	);
</script>