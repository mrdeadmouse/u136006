<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$formName = $formName = 'FILTER_'.$arParams['FILTER_NAME'].'_simple';
?>
<form name="<?echo $formName?>" action="<?=$arParams['LIST_URL']?>" class="bx-selector-form filter-form">
<input type="hidden" name="current_view" value="<?=htmlspecialcharsbx($arParams['CURRENT_VIEW'])?>" />
<input type="hidden" name="current_filter" value="simple" />
<?
if ($arResult['FILTER_VALUES'][$arParams['FILTER_NAME'].'_LAST_NAME']):
?>
<input type="hidden" name="<?=$arParams['FILTER_NAME']?>_LAST_NAME" value="<?=htmlspecialcharsbx($arResult['FILTER_VALUES'][$arParams['FILTER_NAME'].'_LAST_NAME'])?>" />
<?
endif;
?>
<table class="bx-selector-table filter-table">
<tbody>
<?
if (!CModule::IncludeModule('extranet') || !CExtranet::IsExtranetSite() || $arParams["EXTRANET_TYPE"] == "employees"):
?>
<tr>
	<td class="bx-filter-caption"><?echo GetMessage('ISS_TPL_SIMPLE_FILTER_DEPARTMENT')?>: </td>
	<td><?
	CIntranetUtils::ShowDepartmentFilter($arResult['UF_DEPARTMENT_field'], $arResult['bVarsFromForm']);
?>
</td>
</tr><?
endif; 
?>
<tr>
	<td class="bx-filter-caption"><?echo GetMessage('ISS_TPL_SIMPLE_FILTER_FIO')?>: </td>
	<td><input type="text" name="<?=$arParams['FILTER_NAME']?>_FIO" value="<?=$arResult['FILTER_VALUES'][$arParams['FILTER_NAME'].'_FIO']?>" /></td>
</tr>
</tbody>
<tfoot>
<tr>
	<td colspan="2">
		<input type="hidden" name="set_filter_<?=$arParams['FILTER_NAME']?>" value="Y" /> 
		<input type="submit" name="set_filter_<?=$arParams['FILTER_NAME']?>" value="<?echo GetMessage('ISS_TPL_FILTER_SUBMIT')?>" class="bx-submit-btn" /> 
		<input type="submit" name="del_filter_<?=$arParams['FILTER_NAME']?>" value="<?echo GetMessage('ISS_TPL_FILTER_CANCEL')?>" class="bx-reset-btn" />
	</td>
</tr>
</tfoot>
</table>
</form>