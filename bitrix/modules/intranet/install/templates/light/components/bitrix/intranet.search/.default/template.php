<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$GLOBALS['INTRANET_TOOLBAR']->Show();

$outlook_link = 'javascript:'.CIntranetUtils::GetStsSyncURL(
	array(
		'LINK_URL' => $APPLICATION->GetCurPage()
	), 'contacts', ($arParams["EXTRANET_TYPE"] == "employees" ? true : false)
);

$arFilterValues = $APPLICATION->IncludeComponent("bitrix:intranet.structure.selector", "advanced", $arParams, $component, array('HIDE_ICONS' => 'Y'));
?>

<?if($arParams["USE_VIEW_SELECTOR"]!="N"):?>
<table class="bx-users-toolbar"><tr>
	<td><?echo GetMessage('INTR_COMP_IS_TPL_TOOLBAR_VIEW')?>:</td>
	<td><a class="bx-users-icon bx-users-view-list<?echo $arParams['CURRENT_VIEW'] == 'list' ? '-active bx-users-selected ' : ''?>" href="<?echo $APPLICATION->GetCurPageParam('current_view=list', array('current_view'));?>" title="<?echo GetMessage('INTR_COMP_IS_TPL_TOOLBAR_VIEW_LIST')?>"></a></td>
	<td><a class="bx-users-icon bx-users-view-table<?echo $arParams['CURRENT_VIEW'] == 'table' ? '-active bx-users-selected ' : ''?>" href="<?echo $APPLICATION->GetCurPageParam('current_view=table', array('current_view'));?>" title="<?echo GetMessage('INTR_COMP_IS_TPL_TOOLBAR_VIEW_TABLE')?>"></a></td>
	<td class="bx-users-toolbar-last">
	</td>
</tr></table>
<?endif?>

<?$this->SetViewTarget("sidebar_tools_2", 100);?>
<div class="sidebar-links">
		<a id="excelUserExport" href="<?=$APPLICATION->GetCurPageParam('current_view=table&excel=yes&ncc=1'.(!empty($arFilterValues[$arParams['FILTER_NAME'].'_LAST_NAME']) ? htmlspecialcharsbx('&'.$arParams['FILTER_NAME'].'_LAST_NAME='.$arFilterValues[$arParams['FILTER_NAME'].'_LAST_NAME']) : ''), array('excel', 'current_view', $arParams['FILTER_NAME'].'_LAST_NAME'))?>" onclick="javascript:void(0)" title="<?echo GetMessage('INTR_COMP_IS_TPL_TOOLBAR_EXCEL_TITLE')?>"><i class="sidebar-action-excel"></i><b><?echo GetMessage('INTR_COMP_IS_TPL_TOOLBAR_EXCEL')?></b></a><span></span>
		<a href="<?echo htmlspecialcharsbx($outlook_link);?>" title="<?echo GetMessage('INTR_COMP_IS_TPL_TOOLBAR_OUTLOOK_TITLE')?>"><i class="sidebar-action-outlook"></i><b><?echo GetMessage('INTR_COMP_IS_TPL_TOOLBAR_OUTLOOK')?></b></a>
		<a href="javascript:<?= $APPLICATION->GetPopupLink(
					Array(
						"URL"=> "/bitrix/groupdav.php?lang=".LANG."&help=Y&dialog=Y",
						//"PARAMS" => Array("width" => 450, "height" => 200)
					)
                ); ?>" title="<?echo GetMessage('INTR_COMP_IS_TPL_TOOLBAR_CARDDAV_TITLE')?>"><i class="sidebar-action-outlook"></i><b><?echo GetMessage('INTR_COMP_IS_TPL_TOOLBAR_CARDDAV')?></b></a>
</div>
<?$this->EndViewTarget();?>


<div class="bx-users-alphabet" id="bx_alph" style="visibility: visible;">
<?
$APPLICATION->IncludeComponent("bitrix:intranet.structure.selector", 'alphabet', $arParams, $component, array('HIDE_ICONS' => 'Y'));
?>
</div>
<div style="clear: right;"></div>
<?
if (($arParams['CURRENT_VIEW'] == 'list' && $arParams['LIST_VIEW'] == 'group') || ($arParams['CURRENT_VIEW'] == 'table' && $arParams['TABLE_VIEW'] == 'group_table'))
{
	$arParams['SHOW_NAV_TOP'] = 'N';
	$arParams['SHOW_NAV_BOTTOM'] = 'N';
	$arParams['USERS_PER_PAGE'] = 0;
}

$arParams['USER_PROPERTY'] =
	$arParams['CURRENT_VIEW'] == 'list'
	? (
		$arParams['LIST_VIEW'] == 'group'
		? $arParams['USER_PROPERTY_GROUP']
		: $arParams['USER_PROPERTY_LIST']
	)
	: $arParams['USER_PROPERTY_TABLE'];

//echo '<pre>'; print_r($arParams['USER_PROPERTY']); echo '</pre>';

$APPLICATION->IncludeComponent("bitrix:intranet.structure.list", ($arParams['CURRENT_VIEW'] == 'list' ? $arParams['LIST_VIEW'] : ($arParams['TABLE_VIEW']!=''?$arParams['TABLE_VIEW']:'')), $arParams, $component, array('HIDE_ICONS' => 'Y'));
?>