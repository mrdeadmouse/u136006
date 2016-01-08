<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

CModule::IncludeModule('tasks');

$GLOBALS['APPLICATION']->SetPageProperty('BodyClass', 'task-filter-page');

if ( ! function_exists('tasks_jdfk84rgnse_funcRenderPresetsTree') )
{
	function tasks_jdfk84rgnse_funcRenderPresetsTree($arPresetsTree, $curFilterId, $deep = 0)
	{
		$str = '';

		foreach ($arPresetsTree as $presetId => $arPresetData)
		{
			$class = '';

			if ($presetId === $curFilterId)
				$class .= " tasks-filter-active-preset ";

			$lineId = 'tasks_filter_preset_' . (int) $presetId;

			$str .= '<div id="' . $lineId . '" class="task-filter-row ' . $class . '" '
				. 'onclick="__MB_TASKS_TASK_FILTER_SwitchFilter(' . (int) $presetId . ')">' 
				. str_repeat('&nbsp;', $deep * 6)
				. htmlspecialcharsbx($arPresetData['Name']) ;
				
			$str .= '</div>';

			if (isset($arPresetData['#Children'])
				&& count($arPresetData['#Children'])
			)
			{
				$str .= tasks_jdfk84rgnse_funcRenderPresetsTree(
					$arPresetData['#Children'],
					$curFilterId,
					$deep + 1
				);
			}
		}

		return ($str);
	}
}

?>
<script>
	BX.message({
		MB_TASKS_TASK_FILTER_USER_SELECTOR_BTN_SELECT: '<?php echo GetMessageJS('MB_TASKS_TASK_FILTER_USER_SELECTOR_BTN_SELECT'); ?>',
		MB_TASKS_TASK_FILTER_USER_SELECTOR_BTN_CANCEL: '<?php echo GetMessageJS('MB_TASKS_TASK_FILTER_USER_SELECTOR_BTN_CANCEL'); ?>',
		MB_TASKS_TASK_FILTER_TEXT_OR: '<?php echo GetMessageJS('MB_TASKS_TASK_FILTER_TEXT_OR'); ?>',
		MB_TASKS_TASK_FILTER_TEXT_AND: '<?php echo GetMessageJS('MB_TASKS_TASK_FILTER_TEXT_AND'); ?>'
	});

	if ( ! window.MBTasks )
		MBTasks = { lastTimeUIApplicationDidBecomeActiveNotification: 0 };

	if ( ! window.MBTasks.CPT )
		MBTasks.CPT = {};

	MBTasks.sessid = '<?php echo bitrix_sessid(); ?>';
	MBTasks.site = '<?php echo CUtil::JSEscape(SITE_ID); ?>';
	MBTasks.lang = '<?php echo CUtil::JSEscape(LANGUAGE_ID); ?>';
	MBTasks.userId = <?php echo (int) $arResult['USER_ID']; ?>;
	MBTasks.user_path_template = '<?php echo CUtil::JSEscape($arParams['PATH_TEMPLATE_TO_USER_PROFILE']); ?>';

	MBTasks.CPT.Filter = {
		counter: 0,
		stack: []
	};

	document.addEventListener("DOMContentLoaded", function() {
		new FastButton(
			BX('tasks-filter-list'),
			function(event) {
				event.target.click();
			}
		);
	}, false);
</script>
<?php
echo '<input type="hidden" id="tasks-filter-current" value="' . (int) $arResult['CURRENT_PRESET_ID'] . '">';
?>
<div class="task-title"><?php
	echo GetMessage('MB_TASKS_TASK_FILTER_TITLE');
?></div>
<div id="tasks-filter-list" class="task-filter-block" onclick="">
	<?php

	// Render filter tree
	echo tasks_jdfk84rgnse_funcRenderPresetsTree($arResult['PRESETS_TREE'], $arResult['CURRENT_PRESET_ID']);
?>
</div>
<?php
