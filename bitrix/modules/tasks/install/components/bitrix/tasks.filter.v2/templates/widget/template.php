<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

$this->setFrameMode(true);
$this->SetViewTarget("sidebar", 200);

?>

<div class="sidebar-widget sidebar-widget-tasks">
	<div class="sidebar-widget-top">
		<div class="sidebar-widget-top-title"><?=GetMessage("TASKS_FILTER_TITLE")?></div>
		<div class="plus-icon" onclick="BX.Tasks.lwPopup.showCreateForm();"></div>
	</div>
	<div class="sidebar-widget-item-wrap">
	<?
	if ($arResult['USE_ROLE_FILTER'] === 'Y')
	{
		foreach($arResult["ROLES_LIST"] as $roleCodename => $roleData)
		{
			?>
			<a class="task-item" href="<?php echo $roleData['HREF']; ?>"
				><span class="task-item-text"><?php echo $roleData['TITLE'];
				?></span
				><span class="task-item-index-wrap"><span class="task-item-index" <?php if ($arResult['HIDE_COUNTERS']) echo ' style="display:none;" '; ?>><?php
						if ($roleData['CNT_ALL'] > 99)
							echo '99+';
						else
							echo $roleData['CNT_ALL'];
					?></span><span class="task-item-counter"><?
					if ( ! $arResult['HIDE_COUNTERS'] )
					{
						if ($roleData['CNT_NOTIFY'] > 99)
							echo '99+';
						else
							echo (int) $roleData['CNT_NOTIFY'];
					}
					?></span></span>
			</a>
			<?php
		}
	}
	else
	{
		foreach($arResult["PRESETS_LIST"] as $key=>$filter)
		{
			if ($key >= 0)
				continue;

			?>
			<a href="<?= $arParams["PATH_TO_TASKS"]."?F_FILTER_SWITCH_PRESET=".$key?>"
				class="task-item <?if ($filter["#DEPTH"] > 1):?>task-filter-item-sublevel task-filter-item-sublevel_<?echo ($filter["#DEPTH"]-1); endif; ?>"
				><span class="task-item-text"><?
					echo $filter["Name"];
				?></span
				><span class="task-item-index" <?if ($arResult['HIDE_COUNTERS']) echo ' style="display:none;" '; ?>><?
					if ( ! $arResult['HIDE_COUNTERS'] )
						echo $arResult["COUNTS"][$key];
				?></span></a>
			<?
		}
	}
	?>
	</div>
</div>
