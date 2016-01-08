<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$this->SetViewTarget("pagetitle", 100);
?>
<div class="task-title-buttons"><?php
	if ($arParams['SHOW_SEARCH_FIELD'] === 'Y')
	{
		?><span
		class="task-title-button-search<?php if($arResult['F_SEARCH'] !== null):?> task-title-button-search-full<?php endif?>"><span class="task-title-button-search-left"></span><span
		class="task-title-button-search-textbox"
		><form action="<?php echo $arParams["PATH_TO_TASKS"]?>" 
			method="GET" 
			name="task-filter-title-form"
			><input class="task-title-button-search-input" 
				id="task-title-button-search-input" 
				name="F_SEARCH" 
				type="text" 
				<?php
				if ($arResult['F_SEARCH'] !== null)
				{
					?> value="<?php
						echo htmlspecialcharsbx($arResult['F_SEARCH']);
					?>"<?php
				}
				?>
			/><input type="hidden" name="VIEW" 
				value="<?php
				if ($arParams["VIEW_TYPE"] == "list")
				{
					echo 1;
				}
				elseif ($arParams["VIEW_TYPE"] == "gantt")
				{
					echo 2;
				}
				else
				{
					echo 0;
				}
				?>"
			/><input type="hidden" name="F_ADVANCED" value="Y" /><?php
			if($arResult['F_SEARCH'] !== null)
			{
				?><a href="<?php 
					echo $APPLICATION->GetCurPageParam(
						"F_CANCEL=Y",
						array("F_TITLE", "F_RESPONSIBLE", "F_CREATED_BY", 
							"F_ACCOMPLICE", "F_AUDITOR", "F_DATE_FROM", "F_DATE_TO", 
							"F_TAGS", "F_STATUS", "F_SUBORDINATE", "F_ADVANCED", "F_SEARCH"
						)
					);
					?>"
					class="task-title-button-search-reset"></a><?php
			}
			else
			{
				?><span class="task-title-button-search-icon" 
					id="task-title-button-search-icon"></span><?php
			}
		?></form></span><span class="task-title-button-search-right"></span></span><?php
	}

	if (is_object($USER) && $USER->IsAuthorized())
	{
		foreach (
			array(
				'ADD_BUTTON'        => '<a {id} {href} class="task-title-button task-title-button-create" {onclick} {title}>'
					. '<i class="task-title-button-create-icon"></i>'
					. '<span class="task-title-button-create-text">{name}'
					. '</span></a>',
				'QUICK_BUTTON'      => '<a {id} {href} class="task-title-button task-title-button-lightning" {onclick} {title}>'
					. '<i class="task-title-button-lightning-icon"></i></a>',
				'TEMPLATES_TOOLBAR' => '<a {id} {href} class="task-title-button task-title-button-templates" {onclick} {title}>'
					. '<span class="task-title-button-templates-inner"><i class="task-title-button-templates-icon"></i>'
					. '</span></a>',
				'BACK_BUTTON'       => '<a {id} {href} class="task-title-button task-title-button-back" {onclick} {title}>'
					. '<i class="task-title-button-back-icon"></i>'
					. '<span class="task-title-button-back-text">{name}'
					. '</span></a>'
			)
			as
				$elementType => $elementTemplate
		)
		{
			// Skip non-existing elements
			if ( ! isset($arParams['CUSTOM_ELEMENTS'][$elementType]) )
				continue;

			$elem = $arParams['CUSTOM_ELEMENTS'][$elementType];

			echo str_replace(
				array(
					'{id}',
					'{href}',
					'{onclick}',
					'{title}',
					'{name}'
				),
				array(
					($elem['id']      ? (' id="' . $elem['id'] . '"')            :  ''),
					($elem['url']     ? (' href="' . $elem['url'] . '"')         :  ''),
					($elem['onclick'] ? (' onclick="' . $elem['onclick'] . '"')  :  ''),
					($elem['title']   ? (' title="' . $elem['title'] . '"')      :  ''),
					($elem['name']    ? ($elem['name'])                          :  '')
				),
				$elementTemplate
			);

			if (isset($elem['html_after']))
				echo $elem['html_after'];

			if (
				isset($elem['separator_after'])
				&& ($elem['separator_after'] === 'Y')
			)
			{
				?><span class="task-title-button-separator"></span><?php
			}
		}

		if ($arParams['SHOW_ADD_TASK_BUTTON'] === 'Y')
		{
			?><a href="<?php 
				echo CComponentEngine::MakePathFromTemplate(
					$arParams["PATH_TO_TASKS_TASK"], 
					array("task_id" => 0, "action" => "edit")
				); ?>" 
				class="task-title-button task-title-button-create" 
				<?php
				if ( ! CTasksTools::IsIphoneOrIpad() )
				{
					$RESPONSIBLE_ID = (int) $USER->getId();
					
					if ($arParams['USER_ID'])
						$RESPONSIBLE_ID = (int) $arParams['USER_ID'];
					?>
					onclick="

						<?php
						if($arParams["GROUP_ID"])
						{
							?>AddQuickPopupTask(event, {GROUP_ID: <?php echo (int) $arParams["GROUP_ID"]; ?>, RESPONSIBLE_ID: <?php echo $RESPONSIBLE_ID; ?>});<?php
						}
						else
						{
							?>AddQuickPopupTask(event, {RESPONSIBLE_ID: <?php echo $RESPONSIBLE_ID; ?>});<?php
						}
						?>;"
					<?php
				}
				?>
			><i class="task-title-button-create-icon"></i><span class="task-title-button-create-text"><?php
				echo GetMessage("TASKS_ADD_TASK");
			?></span></a><?php
		}

		?><span class="task-title-button-separator"></span><?php

			if ($arParams['SHOW_QUICK_TASK_ADD'] == 'Y')
			{
				?><a href="javascript: void(0)" 
					class="task-title-button task-title-button-lightning" 
					id="task-new-item-icon" 
					onClick="ToggleQuickTask(<?php
						if (isset($arParams["GROUP"]) && $arParams["GROUP"])
						{
							?>null, {group: {id: <?php echo $arParams["GROUP"]["ID"]; 
								?>, title: '<?php 
								echo CUtil::JSEscape(
									htmlspecialcharsbx(
										htmlspecialcharsback(
											htmlspecialcharsback($arParams["GROUP"]["NAME"])
										)
									)
								); ?>'}}<?php
						}
						?>)" 
					title="<?php echo GetMessage("TASKS_ADD_QUICK_TASK"); 
				?>"><i class="task-title-button-lightning-icon"></i></a><?
			}


			if ($arParams['SHOW_TEMPLATES_TOOLBAR'] === 'Y')
			{
				?><a href="javascript: void(0)" 
					class="task-title-button task-title-button-templates" 
					onclick="return ShowTemplatesPopup(this)" 
					title="<?php echo GetMessage("TASKS_ADD_TEMPLATE_TASK")?>"
				><span class="task-title-button-templates-inner"><i class="task-title-button-templates-icon"></i></span></a><?php
			}
	}
	
	if (
		($arParams['SHOW_TASK_LIST_MODES'] == 'Y')
		|| ($arParams['SHOW_HELP_ICON'] == 'Y')
		|| (
			is_string($arParams['~ADDITIONAL_HTML'])
			&& ($arParams['~ADDITIONAL_HTML'] !== '')
		)
	)
	{
		?><span class="task-title-button-separator"></span><?php
	}

	if ($arParams['SHOW_TASK_LIST_MODES'] == 'Y')
	{
		?><span class="task-list-modes"
			><a class="task-list-mode<?php echo ($arParams["VIEW_TYPE"] == "tree" ? " task-list-mode-selected" : "")?>" href="<?php echo $APPLICATION->GetCurPageParam("VIEW=0", array("VIEW"));?>" title="<?php echo GetMessage("TASKS_TREE_LIST")?>"><i class="task-list-mode-expanded"></i></a
			><a class="task-list-mode<?php echo ($arParams["VIEW_TYPE"] == "list" ? " task-list-mode-selected" : "")?>" href="<?php echo $APPLICATION->GetCurPageParam("VIEW=1", array("VIEW"));?>" title="<?php echo GetMessage("TASKS_PLAIN_LIST")?>"><i class="task-list-mode-collapsed"></i></a
			><a class="task-list-mode<?php echo ($arParams["VIEW_TYPE"] == "gantt" ? " task-list-mode-selected" : "")?>" href="<?php echo $APPLICATION->GetCurPageParam("VIEW=2", array("VIEW"));?>" title="<?php echo GetMessage("TASKS_GANTT")?>"><i class="task-list-mode-gant"></i></a
		></span>
		<a class="task-title-button" 
			href="<?php echo $arParams["PATH_TO_REPORTS"]; ?>"
			><span class="task-title-button-create-text"><?php
				echo GetMessage("TASK_TOOLBAR_FILTER_REPORTS");
		?></span></a>
		<?php
	}

	if ($arParams['SHOW_HELP_ICON'] == 'Y')
	{
		?><span class="task-title-button task-title-button-legend" id="task-title-button-legend" onclick="ShowLegendPopup(this);" title="<?php echo GetMessage("TASKS_HELP")?>"></span><?
	}

	echo $arParams['~ADDITIONAL_HTML'];
?></div><?php

if ($arParams['SHOW_TEMPLATES_TOOLBAR'] === 'Y')
{
	?>
	<div class="task-popup-templates" id="task-popup-templates-popup-content" style="display:none;">
		<div class="task-popup-templates-title"><?php echo GetMessage("TASKS_ADD_TEMPLATE_TASK")?></div>
		<div class="popup-window-hr"><i></i></div>
		<?php if (sizeof($arParams["TEMPLATES"]) > 0):?>
			<ol class="task-popup-templates-items">
				<?php $commonUrl = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS_TASK"], array("task_id" => 0, "action" => "edit"))?>
				<?php foreach($arParams["TEMPLATES"] as $template):?>
				<?php $createUrl = $commonUrl.(strpos($commonUrl, "?") === false ? "?" : "&")."TEMPLATE=".$template["ID"];?>
				<li class="task-popup-templates-item"><a class="task-popup-templates-item-link" href="<?php echo $createUrl?>" onclick="AddPopupTemplateTask(<?php echo $template["ID"]?>, event);"><?php echo $template["TITLE"]?></a></li>
				<?php endforeach?>
			</ol>
		<?php else:?>
			<div class="task-popup-templates-empty"><?php echo GetMessage("TASKS_NO_TEMPLATES")?></div>
		<?php endif?>
		<div class="popup-window-hr"><i></i></div>
		<a class="task-popup-templates-item task-popup-templates-item-all" href="<?php echo CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS_TEMPLATES"], array())?>"><?php echo GetMessage("TASKS_TEMPLATES_LIST")?></a>
	</div>
	<?
}

$this->EndViewTarget();

$this->SetViewTarget("task_menu", 200);

if (is_object($USER) && $USER->IsAuthorized())
{
	if ($arParams['SHOW_TEMPLATES_TOOLBAR'] === 'Y')
	{
		?>
		<div class="task-popup-templates" id="task-popup-templates-popup-content" style="display:none;">
			<div class="task-popup-templates-title"><? echo GetMessage("TASKS_ADD_TEMPLATE_TASK")?></div>
			<div class="popup-window-hr"><i></i></div>
			<? if (sizeof($arParams["TEMPLATES"]) > 0):?>
			<ol class="task-popup-templates-items">
				<? $commonUrl = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS_TASK"], array("task_id" => 0, "action" => "edit"))?>
				<? foreach($arParams["TEMPLATES"] as $template):?>
				<? $createUrl = $commonUrl.(strpos($commonUrl, "?") === false ? "?" : "&")."TEMPLATE=".$template["ID"];?>
				<li class="task-popup-templates-item"><a class="task-popup-templates-item-link" href="<? echo $createUrl?>" onclick="AddPopupTemplateTask(<? echo $template["ID"]?>, event);"><? echo $template["TITLE"]?></a></li>
				<? endforeach?>
			</ol>
			<? else:?>
			<div class="task-popup-templates-empty"><? echo GetMessage("TASKS_NO_TEMPLATES")?></div>
			<? endif?>
			<div class="popup-window-hr"><i></i></div>
			<a class="task-popup-templates-item task-popup-templates-item-all" href="<?=CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS_TEMPLATES"], array())?>"><? echo GetMessage("TASKS_TEMPLATES_LIST")?></a>
		</div>
		<?php
	}
}

if (
	($arParams['SHOW_SECTIONS_BAR'] === 'Y')
	|| ($arParams['SHOW_FILTER_BAR'] === 'Y')
	|| ($arParams['SHOW_COUNTERS_BAR'] === 'Y')
)
{
	require_once($_SERVER["DOCUMENT_ROOT"] . $templateFolder . '/topnav.php');
}

$this->EndViewTarget();?>
