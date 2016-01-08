<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>


<?if ($arParams["PAGE_ID"] == "group_tasks_task" || $arParams["PAGE_ID"] == "user_tasks_task"):
	if ($arParams["ACTION"] == "view" && !$arResult["Perms"]["HideArchiveLinks"]):

		$GLOBALS["INTRANET_TOOLBAR"]->AddButton(array(
			'HREF' => $arResult["Urls"]["EditTask"],
			"TEXT" => GetMessage('INTMT_EDIT_TASK'),
			'ICON' => 'edit',
			"SORT" => 1000,
		));

	endif;
elseif ($arParams["PAGE_ID"] != "group_tasks_view" && $arParams["PAGE_ID"] != "user_tasks_view"):

	if (StrLen($arResult["Urls"]["CreateTask"]) > 0 && !$arResult["Perms"]["HideArchiveLinks"]):

		$GLOBALS["INTRANET_TOOLBAR"]->AddButton(array(
			'HREF' => $arResult["Urls"]["CreateTask"],
			"TEXT" => GetMessage('INTMT_CREATE_TASK'),
			'ICON' => 'create',
			"SORT" => 1000,
		));

	endif;

	if ($arResult["Perms"]["modify_folders"] && $arParams["PAGE_ID"] == "group_tasks" && !$arResult["Perms"]["HideArchiveLinks"]):

		$GLOBALS["INTRANET_TOOLBAR"]->AddButton(array(
			'ID' => "intask_create_folder_a",
			'HREF' => "javascript:void(0);",
			"TEXT" => GetMessage('INTMT_CREATE_FOLDER'),
			'ICON' => 'create',
			"SORT" => 1000,
		));

	endif;
endif;

?>
<?
$this->SetViewTarget("sidebar_tools_1", 100);

if (in_array($arParams["PAGE_ID"], array("group_tasks_task", "user_tasks_task", "group_tasks_view", "user_tasks_view"))):

	$first = true;
	?>
	<div class="sidebar-border-block sidebar-actions-block">
		<div class="sidebar-border-block-top">
			<div class="border"></div>
			<div class="corner left"></div>
			<div class="corner right"></div>
		</div>
		<div class="sidebar-border-block-content">
			<?if ($arParams["PAGE_ID"] == "group_tasks_task" || $arParams["PAGE_ID"] == "user_tasks_task"):?>
				<?if (!$first):?><span></span><?endif;?><?$first = false;?>
				<a href="<?= $arResult["Urls"]["TasksList"] ?>" title="<?= GetMessage("INTMT_BACK2LIST_DESCR") ?>"><i class="sidebar-action-icon sidebar-action-up"></i><b><?echo GetMessage('INTMT_BACK2LIST')?></b></a>
				<?if ($arParams["ACTION"] == "edit"):?>
					<?if (!$first):?><span></span><?endif;?><?$first = false;?>
					<a href="<?= $arResult["Urls"]["ViewTask"] ?>" title="<?= GetMessage("INTMT_VIEW_TASK_DESCR") ?>"><i class="sidebar-action-icon sidebar-action-tasks-add"></i><b><?echo GetMessage('INTMT_VIEW_TASK')?></b></a>
				<?endif;?>
			<?elseif ($arParams["PAGE_ID"] == "group_tasks_view" || $arParams["PAGE_ID"] == "user_tasks_view"):?>
				<?if (!$first):?><span></span><?endif;?><?$first = false;?>
				<a href="<?= $arResult["Urls"]["TasksList"] ?>" title="<?= GetMessage("INTMT_BACK2LIST_DESCR") ?>"><i class="sidebar-action-icon sidebar-action-up"></i><b><?echo GetMessage('INTMT_BACK2LIST')?></b></a>
			<?endif;?>
		</div>
		<div class="sidebar-border-block-bottom">
			<div class="border"></div>
			<div class="corner left"></div>
			<div class="corner right"></div>
		</div>
	</div>
<?endif;?>

<?
if (!in_array($arParams["PAGE_ID"], array("group_tasks_task", "user_tasks_task", "group_tasks_view", "user_tasks_view")) || $arResult["CurrentView"] > 0):
	?>
	<div class="sidebar-block">
		<b class="r2"></b><b class="r1"></b><b class="r0"></b>
		<div class="sidebar-block-inner">
			<?
			if (!in_array($arParams["PAGE_ID"], array("group_tasks_task", "user_tasks_task", "group_tasks_view", "user_tasks_view"))):
				?>
				<div class="sidebar-block-title"><?=GetMessage('INTMT_VIEW')?></div>
				<div class="filter-block">
					<div class="filter-field filter-field-task-view">
						<select id="task-view" class="filter-dropdown" name="user_settings_id" onchange="window.location='<?= $arResult["Urls"]["ChangeView"] ?>' + this.options[this.selectedIndex].value">
							<option value="0"><?= GetMessage("INTMT_DEFAULT") ?></option>
							<?foreach ($arResult["Views"] as $view):?>
								<option value="<?= $view["ID"] ?>"<?= (($view["ID"] == $arResult["CurrentView"]) ? " selected" : "") ?>><?= $view["TITLE"] ?></option>
							<?endforeach;?>
						</select>
					</div>
				</div>
			<?endif;?>


			<div class="sidebar-links">
				<?
				if (!in_array($arParams["PAGE_ID"], array("group_tasks_task", "user_tasks_task", "group_tasks_view", "user_tasks_view"))):
					?>
					<a href="<?=$arResult["Urls"]["CreateView"]?>"><i class="sidebar-action-view-create"></i><b><?echo GetMessage('INTMT_CREATE_VIEW')?></b></a><span></span>
					<?
				endif;
				?>
				<?
				if ($arResult["CurrentView"] > 0):
					?>
					<a href="<?=$arResult["Urls"]["EditView"]?>"><i class="sidebar-action-view-edit"></i><b><?echo GetMessage('INTMT_EDIT_VIEW')?></b></a><span></span>
					<a href="javascript:if (confirm('<?=GetMessage("INTMT_DELETE_VIEW_CONF")?>')) window.location='<?=$arResult["Urls"]["DeleteView"]?>'" title="<?=GetMessage('INTMT_DELETE_VIEW')?>"><i class="sidebar-action-view-delete"></i><b><?echo GetMessage('INTMT_DELETE_VIEW')?></b></a><span></span>
					<?
				endif;
				?>
			</div>
		</div>
		<i class="r0"></i><i class="r1"></i><i class="r2"></i>
	</div>
<?
endif;
$this->EndViewTarget();
?>
<?
$this->SetViewTarget("sidebar_tools_2", 100);
?>
<?if (!in_array($arParams["PAGE_ID"], array("group_tasks_task", "user_tasks_task", "group_tasks_view", "user_tasks_view"))):?>
	<?if ($arParams['TASK_TYPE'] != 'group' && $USER->GetID() == $arParams['OWNER_ID']):?>
		<div class="sidebar-links">
			<a href="javascript:<?echo htmlspecialcharsbx(CIntranetUtils::GetStsSyncURL(array(
				'LINK_URL' => '/'.$USER->GetID().'/',
				), 'tasks'))?>" title="<?=GetMessage('INTMT_OUTLOOK_TITLE')?>"><i class="sidebar-action-outlook"></i><b><?echo GetMessage('INTMT_OUTLOOK')?></b></a><span></span>
		</div>
	<?endif;?>
<?endif;?>
<?
$this->EndViewTarget();
?>