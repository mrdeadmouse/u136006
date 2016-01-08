<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
?>
<?
$formName = 'FILTER_'.$arParams['FILTER_NAME'].'_adv';

$this->SetViewTarget("sidebar", 100);
?>
<?if ($arResult['CURRENT_USER']['DEPARTMENT_TOP']):?>
<script type="text/javascript">
function BXChangeFilterTop_adv(ob)
{
	if (ob.checked) 
	{
		var obFld = document.forms['<?=$formName?>']['<?=$arParams['FILTER_NAME']?>_UF_DEPARTMENT<?=$arParams['FILTER_DEPARTMENT_SINGLE'] == 'Y' ? '' : '[]'?>'];
		if (obFld)
			obFld.value = <?=intval($arResult['CURRENT_USER']['DEPARTMENT_TOP'])?>;
		
	}
}
</script>
<?endif;?>
<form name="<?=$formName?>" action="<?=$arParams['LIST_URL']?>">
<input type="hidden" name="current_filter" value="adv" />
<?
if ($arResult['FILTER_VALUES'][$arParams['FILTER_NAME'].'_LAST_NAME']):
?>
<input type="hidden" name="<?=$arParams['FILTER_NAME']?>_LAST_NAME" value="<?=htmlspecialcharsbx($arResult['FILTER_VALUES'][$arParams['FILTER_NAME'].'_LAST_NAME'])?>" />
<?
endif;
?>
<div class="sidebar-block">
	<b class="r2"></b><b class="r1"></b><b class="r0"></b>
	<div class="sidebar-block-inner">
		<div class="sidebar-block-title"><?=GetMessage('INTR_COMP_IS_TPL_SECH')?></div>
		<div class="filter-block">
			<div class="filter-field filter-field-user-fio">
				<label class="filter-field-title" for="user-fio"><?echo GetMessage('INTR_ISS_PARAM_FIO')?></label>
				<input class="filter-textbox" type="text" id="user-fio" name="<?=$arParams['FILTER_NAME']?>_FIO" value="<?=$arResult['FILTER_VALUES'][$arParams['FILTER_NAME'].'_FIO']?>" />
			</div>
			<div class="filter-field filter-field-user-email">
				<label class="filter-field-title" for="user-email"><?echo GetMessage('INTR_ISS_PARAM_EMAIL')?></label>
				<input class="filter-textbox" type="text" id="user-email" name="<?=$arParams['FILTER_NAME']?>_EMAIL" value="<?=$arResult['FILTER_VALUES'][$arParams['FILTER_NAME'].'_EMAIL']?>" />
			</div>
			<div class="filter-field filter-field-user-phone">
				<label class="filter-field-title" for="user-phone"><?echo GetMessage('INTR_ISS_PARAM_PHONE')?></label>
				<input class="filter-textbox" type="text" id="user-phone" name="<?=$arParams['FILTER_NAME']?>_PHONE" value="<?=$arResult['FILTER_VALUES'][$arParams['FILTER_NAME'].'_PHONE']?>" />
			</div><?
			if (!CModule::IncludeModule("extranet") || !CExtranet::IsExtranetSite())
			{
				?><div class="filter-field filter-field-user-phone-inner">
					<label class="filter-field-title" for="user-phone-inner"><?echo GetMessage('INTR_ISS_PARAM_PHONE_INNER')?></label>
					<input class="filter-textbox" type="text" id="user-phone-inner" name="<?=$arParams['FILTER_NAME']?>_UF_PHONE_INNER" value="<?=$arResult['FILTER_VALUES'][$arParams['FILTER_NAME'].'_UF_PHONE_INNER']?>" />
				</div>			
				<div class="filter-field filter-field-user-department">
					<label class="filter-field-title" for="user-department"><?echo GetMessage('INTR_ISS_PARAM_DEPARTMENT')?></label>
					<?CIntranetUtils::ShowDepartmentFilter($arResult['UF_DEPARTMENT_field'], $arResult['bVarsFromForm']);?>				
				</div><?
			}
			?><div class="filter-field filter-field-user-keywords">
				<label class="filter-field-title" for="user-keywords"><?echo GetMessage('INTR_ISS_PARAM_KEYWORDS')?></label>
				<input class="filter-textbox" type="text" name="<?=$arParams['FILTER_NAME']?>_KEYWORDS" value="<?=$arResult['FILTER_VALUES'][$arParams['FILTER_NAME'].'_KEYWORDS']?>" />
			</div>
			<div class="filter-field filter-field-user-checkbox">
				<?if ($arResult['CURRENT_USER']['DEPARTMENT_TOP']):?>
					<input class="filter-checkbox" type="checkbox" id="only_mine_office"  onclick="BXChangeFilterTop_adv(this)" <?echo $arResult['FILTER_VALUES'][$arParams['FILTER_NAME'].'_UF_DEPARTMENT'] == $arResult['CURRENT_USER']['DEPARTMENT_TOP'] || $arResult['FILTER_VALUES'][$arParams['FILTER_NAME'].'_UF_DEPARTMENT'] == array($arResult['CURRENT_USER']['DEPARTMENT_TOP']) ? 'checked="checked"' : ''?> />&nbsp;<label for="only_mine_office"><?=GetMessage('INTR_COMP_IS_TPL_MY_OFFICE')?></label><br />
				<?endif;?>
				<?$APPLICATION->IncludeComponent("bitrix:intranet.structure.selector", 'online', $arParams, $component->__parent, array('HIDE_ICONS' => 'Y'));?>
			</div>
			<div class="filter-field-buttons">
				<input type="hidden" name="set_filter_<?=$arParams['FILTER_NAME']?>" value="Y" /> 
				<input type="submit" name="set_filter_<?=$arParams['FILTER_NAME']?>" value="<?=GetMessage('INTR_ISS_BUTTON_SUBMIT')?>" class="filter-submit" />&nbsp;&nbsp;<input type="submit" name="del_filter_<?=$arParams['FILTER_NAME']?>" value="<?=GetMessage('INTR_ISS_BUTTON_CANCEL')?>" class="filter-submit" />
			</div>
		</div>
	</div>
	<i class="r0"></i><i class="r1"></i><i class="r2"></i>
</div>
</form>
<?
$this->EndViewTarget();
?>