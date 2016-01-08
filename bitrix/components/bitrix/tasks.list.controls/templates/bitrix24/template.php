<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$this->SetViewTarget("pagetitle", 100);

?>
<div class="task-list-toolbar">
	<div class="task-list-toolbar-actions">
		<?php
		foreach (
			array(
				'ADD_BUTTON'        => '<a {id} {href} class="webform-small-button webform-small-button-blue task-list-toolbar-create" {onclick} {title}>'
					. '<span class="webform-small-button-left"></span>'
					. '<span class="webform-small-button-icon"></span>'
					. '<span class="webform-small-button-text">{name}</span>'
					. '<span class="webform-small-button-right"></span></a>',
				'QUICK_BUTTON'      => '<a {id} {href} class="webform-small-button webform-small-button-transparent task-list-toolbar-lightning" {onclick} {title}>'
					. '<span class="webform-small-button-left"></span><span class="webform-small-button-icon"></span>'
					. '<span class="webform-small-button-right"></span></a>',
				'TEMPLATES_TOOLBAR' => '<a {id} {href} class="webform-small-button webform-small-button-transparent task-list-toolbar-templates" {onclick} {title}>'
					. '<span class="webform-small-button-left"></span><span class="webform-small-button-icon"></span>'
					. '<span class="webform-small-button-right"></span></a>'
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

		if (
			isset($arParams['CUSTOM_ELEMENTS']['BACK_BUTTON'])
			&& isset($arParams['CUSTOM_ELEMENTS']['BACK_BUTTON']['name'])
		)
		{
			$onclick = $url = '';

			if (isset($arParams['CUSTOM_ELEMENTS']['BACK_BUTTON']['url']))
				$url = $arParams['CUSTOM_ELEMENTS']['BACK_BUTTON']['url'];

			if (isset($arParams['CUSTOM_ELEMENTS']['BACK_BUTTON']['onclick']))
				$onclick = $arParams['CUSTOM_ELEMENTS']['BACK_BUTTON']['onclick'];

			?><a
				style="margin-top: 7px;"
				<?php if ($url) echo ' href="' . $url . '"'; ?> 
				class="task-title-button task-title-button-back" 
				<?php if ($onclick) echo ' onclick="' . $onclick . '"' ?>
				><i class="task-title-button-back-icon"></i
				><span class="task-title-button-back-text"><?php
					echo $arParams['CUSTOM_ELEMENTS']['BACK_BUTTON']['name'];
			?></span></a>&nbsp;<?php
		}

		if ($arParams['SHOW_ADD_TASK_BUTTON'] === 'Y')
		{
			?>
			<a class="webform-small-button webform-small-button-blue task-list-toolbar-create" href="<?=CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS_TASK"], array("task_id" => 0, "action" => "edit"))?>" 
				onclick="<?
					$RESPONSIBLE_ID = (int) $USER->getId();
					
					if ($arParams['USER_ID'])
						$RESPONSIBLE_ID = (int) $arParams['USER_ID'];

					if($arParams["GROUP_ID"]):
						?>AddQuickPopupTask(event, {GROUP_ID: <?php echo $arParams["GROUP_ID"]; ?>, RESPONSIBLE_ID: <?php echo $RESPONSIBLE_ID; ?>});<?
					else:
						?>AddQuickPopupTask(event, {RESPONSIBLE_ID: <?php echo $RESPONSIBLE_ID; ?>})<?
					endif?>;"><span class="webform-small-button-left"></span><span class="webform-small-button-icon"></span><span class="webform-small-button-text"><?=GetMessage("TASKS_ADD_TASK")?></span><span class="webform-small-button-right"></span>
			</a><?
		}

		if ($arParams['SHOW_QUICK_TASK_ADD'] == 'Y')
		{
			if ($arParams["VIEW_TYPE"] != "gantt")
			{
				?><a class="webform-small-button webform-small-button-transparent task-list-toolbar-lightning" id="task-list-toolbar-lightning" href="" 
					onclick="BX.PreventDefault(event); createQuickTask(null, {
						user : {id: <?php echo (int) $arParams['USER_ID']; ?>}
						<?php
						if (isset($arParams["GROUP"]) && $arParams["GROUP"])
						{
							?>, group: {id: <?=$arParams["GROUP"]["ID"]?>, title: '<?=CUtil::JSEscape(htmlspecialcharsbx(htmlspecialcharsback(htmlspecialcharsback($arParams["GROUP"]["NAME"]))))?>'}<?php
						}
						?>})" title="<?=GetMessage("TASKS_ADD_QUICK_TASK")?>"><span class="webform-small-button-left"></span><span class="webform-small-button-icon"></span><span class="webform-small-button-right"></span>
				</a><?
			}
		}

		if ($arParams['SHOW_TEMPLATES_TOOLBAR'] === 'Y')
		{
			?><a class="webform-small-button webform-small-button-transparent task task-list-toolbar-templates" href="" onclick="return ShowTemplatesPopup(this)" title="<?=GetMessage("TASKS_ADD_TEMPLATE_TASK")?>"><span class="webform-small-button-left"></span><span class="webform-small-button-icon"></span><span class="webform-small-button-right"></span></a><?php
		}
		?>
	</div>
	<?php
	if ($arParams['SHOW_SEARCH_FIELD'] === 'Y')
	{
		?><div class="task-list-toolbar-search"
			><form action="<?$arParams["PATH_TO_TASKS"]?>" method="GET" name="task-filter-title-form"
				><input class="task-list-toolbar-search-input" 
					id="task-title-button-search-input" 
					name="F_SEARCH" 
					type="text"
					<?php
					if($arResult['SEARCH_STRING']):
						?> value="<? echo htmlspecialcharsbx($arResult["SEARCH_STRING"]); ?>"
					<?endif?> 
				/><input type="hidden" name="VIEW" value="<?php
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
					} ?>" 
				/><input type="hidden"  name="F_ADVANCED" value="Y" /><?php
					if(isset($_GET["F_SEARCH"]) && $_GET["F_SEARCH"]):
						?><a href="<? echo $APPLICATION->GetCurPageParam("F_CANCEL=Y", array("F_TITLE", "F_RESPONSIBLE", "F_CREATED_BY", "F_ACCOMPLICE", "F_AUDITOR", "F_DATE_FROM", "F_DATE_TO", "F_TAGS", "F_STATUS", "F_SUBORDINATE", "F_ADVANCED", "F_SEARCH"))?>" class="task-list-toolbar-search-reset"></a><?
					else:
						?><span class="task-list-toolbar-search-icon" id="task-title-button-search-icon"></span><?
					endif;
		?></form></div><?php
	}

	if ($arParams['SHOW_FILTER_ICON'] === 'Y')
	{
		?><div><?php
		if ($arParams["VIEW_TYPE"] == "gantt")
		{
			?><span class="webform-small-button task-list-toolbar-filter" onclick="showGanttFilter(this)"><span class="webform-small-button-left"></span><span class="webform-small-button-text"><?php
				echo GetMessage("TASK_TOOLBAR_FILTER_BUTTON") . $filterName;
			?></span><span class="webform-small-button-icon"></span><span class="webform-small-button-right"></span></span><?
		}
		else
		{
			?><span class="webform-small-button task-list-toolbar-filter" onclick="showTaskListFilter(this)"><span class="webform-small-button-left"></span><span class="webform-small-button-text"><?php
				echo GetMessage("TASK_TOOLBAR_FILTER_BUTTON") . $filterName;
			?></span><span class="webform-small-button-icon"></span><span class="webform-small-button-right"></span></span><?
		}
		?></div><?php
	}
	?>
</div>
<?php
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

<script type="text/javascript">
	function createQuickTask(table, params)
	{
		BX.toggleClass(BX("task-list-toolbar-lightning", true), "webform-small-button-active");
		if (BX.hasClass(BX("task-list-toolbar-lightning", true), "webform-small-button-active"))
		{
			ShowQuickTask(table, params);
		}
		else
		{
			HideQuickTask();
		}
	};

	function showGanttFilter(bindElement)
	{
		BX.toggleClass(bindElement, "webform-small-button-active");
		TaskGanttFilterPopup.show(bindElement);
	};

	function showTaskListFilter(bindElement)
	{
		BX.toggleClass(bindElement, "webform-small-button-active");
		TaskListFilterPopup.show(bindElement);
	};

	//Override original HideQuickTask
	var HideQuickTask = (function() {
		var originalFunction = HideQuickTask;
		return function() {
			originalFunction();
			BX.removeClass(BX("task-list-toolbar-lightning", true), "webform-small-button-active")
		};
	})();
</script>
