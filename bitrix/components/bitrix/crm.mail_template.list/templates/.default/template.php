<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)die();
global $APPLICATION;

$APPLICATION->SetAdditionalCSS('/bitrix/themes/.default/crm-entity-show.css');

$arResult['GRID_DATA'] = $arColumns = array();
foreach ($arResult['HEADERS'] as $arHead)
{
	$arColumns[$arHead['id']] = false;
}

foreach($arResult['ITEMS'] as &$item)
{
	$arActions = array();

	if($arResult['CAN_EDIT'] && $item['CAN_EDIT'])
	{
		$arActions[] =  array(
			'ICONCLASS' => 'view',
			'TITLE' => GetMessage('CRM_MAIL_TEMPLATE_EDIT_TITLE'),
			'TEXT' => GetMessage('CRM_MAIL_TEMPLATE_EDIT'),
			'ONCLICK' => 'jsUtils.Redirect([], \''.CUtil::JSEscape($item['PATH_TO_EDIT']).'\');',
			'DEFAULT' => false
		);
	}

	if ($arResult['CAN_DELETE'] && $item['CAN_DELETE'])
	{
		$arActions[] = array('SEPARATOR' => true);
		$arActions[] =  array(
			'ICONCLASS' => 'delete',
			'TITLE' => GetMessage('CRM_MAIL_TEMPLATE_DELETE_TITLE'),
			'TEXT' => GetMessage('CRM_MAIL_TEMPLATE_DELETE'),
			'ONCLICK' => 'crm_mail_template_delete_grid(\''.CUtil::JSEscape(GetMessage('CRM_MAIL_TEMPLATE_DELETE_TITLE')).'\', \''.CUtil::JSEscape(sprintf(GetMessage('CRM_MAIL_TEMPLATE_DELETE_CONFIRM'), $item['TITLE'])).'\', \''.CUtil::JSEscape(GetMessage('CRM_MAIL_TEMPLATE_DELETE')).'\', \''.CUtil::JSEscape($item['PATH_TO_DELETE']).'\')'
		);
	}

	$arResult['GRID_DATA'][] = array(
		'id' => $item['~ID'],
		'actions' => $arActions,
		'data' => $item,
		'editable' => $arResult['CAN_EDIT'] ? true : $arColumns,
		'columns' => array(
			'TITLE' => $item['CAN_EDIT'] ? '<a target="_self" href="'.htmlspecialcharsbx($item['PATH_TO_EDIT']).'">'.$item['TITLE'].'</a>' : $item['TITLE'],
			'CREATED' => FormatDate('SHORT', MakeTimeStamp($item['~CREATED'])),
			'LAST_UPDATED' => FormatDate('SHORT', MakeTimeStamp($item['~LAST_UPDATED']))
		)
	);
}
unset($item);

if($arResult['NEED_FOR_CONVERTING'])
{
	$messageViewID = $arResult['MESSAGE_VIEW_ID'];
	if($messageViewID !== '')
	{
		$this->SetViewTarget($messageViewID, 100);
	}
	?><div class="crm-view-message"><?= GetMessage('CRM_MAIL_TEMPLATE_NEED_FOR_CONVERTING', array('#URL_EXECUTE_CONVERTING#' => htmlspecialcharsbx($arResult['CONV_EXEC_URL']), '#URL_SKIP_CONVERTING#' => htmlspecialcharsbx($arResult['CONV_SKIP_URL']))) ?></div><?
	if($messageViewID !== '')
	{
		$this->EndViewTarget();
	}
}

$APPLICATION->IncludeComponent(
	'bitrix:main.interface.grid',
	'',
	array(
		'GRID_ID' => $arResult['GRID_ID'],
		'HEADERS' => $arResult['HEADERS'],
		'SORT' => $arResult['SORT'],
		'SORT_VARS' => $arResult['SORT_VARS'],
		'ROWS' => $arResult['GRID_DATA'],
		'FOOTER' =>
			array(
				array(
					'title' => GetMessage('CRM_ALL'),
					'value' => $arResult['ROWS_COUNT']
				)
			),
		'EDITABLE' => $arResult['CAN_EDIT'],
		'ACTIONS' =>
			array(
				'delete' => $arResult['CAN_DELETE'],
				'list' => array()
			),
		'ACTION_ALL_ROWS' => false,
		'NAV_OBJECT' => $arResult['ITEMS'],
		'FORM_ID' => $arResult['FORM_ID'],
		'TAB_ID' => $arResult['TAB_ID'],
		'AJAX_MODE' => 'N'
		//'FILTER' => $arResult['FILTER']
	),
	$component
);
?><script type="text/javascript">
	function crm_mail_template_delete_grid(title, message, btnTitle, path)
	{
		var d =
			new BX.CDialog(
				{
					title: title,
					head: '',
					content: message,
					resizable: false,
					draggable: true,
					height: 70,
					width: 300
				}
			);

		var _BTN = [
			{
				title: btnTitle,
				id: 'crmOk',
				'action': function ()
				{
					window.location.href = path;
					BX.WindowManager.Get().Close();
				}
			},
			BX.CDialog.btnCancel
		];
		d.ClearButtons();
		d.SetButtons(_BTN);
		d.Show();
	}
</script><?