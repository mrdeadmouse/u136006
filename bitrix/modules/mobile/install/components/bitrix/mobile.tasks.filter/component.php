<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
?>
<script>
	app.pullDown({
		enable:   true,
		pulltext: '<?php echo GetMessageJS('MB_TASKS_TASKS_FILTER_PULLDOWN_PULL'); ?>',
		downtext: '<?php echo GetMessageJS('MB_TASKS_TASKS_FILTER_PULLDOWN_DOWN'); ?>',
		loadtext: '<?php echo GetMessageJS('MB_TASKS_TASKS_FILTER_PULLDOWN_LOADING'); ?>',
		action:   'RELOAD',
		callback: function() { app.reload(); }
	});
</script>
<?php

$arResult = array();

$environmentCheck = isset($GLOBALS['APPLICATION']) 
	&& is_object($GLOBALS['APPLICATION'])
	&& isset($GLOBALS['USER']) 
	&& is_object($GLOBALS['USER'])
	&& isset($arParams)
	&& is_array($arParams)
	&& CModule::IncludeModule('tasks');

try
{
	CTaskAssert::assert($environmentCheck);
	unset ($environmentCheck);
	$arResult['USER_ID'] = (int) $_GET['USER_ID'];
	$oFilter = CTaskFilterCtrl::GetInstance($arResult['USER_ID']);
	$arResult['PRESETS_TREE'] = $oFilter->ListFilterPresets($bTreeMode = true);
	$arResult['CURRENT_PRESET_ID'] = $oFilter->GetSelectedFilterPresetId();
}
catch (Exception $e)
{
	return (false);
}

$this->IncludeComponentTemplate();

return $arResult;
