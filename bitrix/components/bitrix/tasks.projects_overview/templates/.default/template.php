<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arComponentParams = array(
	'USER_ID'                => $arParams['USER_ID'],
	'GROUP_ID'               =>  0,
	'SHOW_TASK_LIST_MODES'   => 'N',
	'SHOW_HELP_ICON'         => 'N',
	'SHOW_SEARCH_FIELD'      => 'N',
	'SHOW_TEMPLATES_TOOLBAR' => 'N',
	'SHOW_QUICK_TASK_ADD'    => 'N',
	'SHOW_ADD_TASK_BUTTON'   => 'N',
	'SHOW_FILTER_BUTTON'     => 'N',
	'SHOW_SECTIONS_BAR'      => 'Y',
	'SHOW_FILTER_BAR'        => 'N',
	'SHOW_COUNTERS_BAR'      => 'N',
	'SHOW_SECTION_PROJECTS'  => 'Y',
	'SHOW_SECTION_MANAGE'    => 'A',
	'SHOW_SECTION_COUNTERS'  => 'Y',
	'MARK_ACTIVE_ROLE'       => 'N',
	'MARK_SECTION_MANAGE'    => 'N',
	'MARK_SECTION_PROJECTS'  => 'Y',
	'SECTION_URL_PREFIX'     =>  CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS"], array())
);

if ($arParams['USER_ID'] > 0)
{
	$arComponentParams['PATH_TO_PROJECTS'] = CComponentEngine::MakePathFromTemplate(
		$arParams['PATH_TO_USER_TASKS_PROJECTS_OVERVIEW'],
		array('user_id' => $arParams['USER_ID'])
	);
}

$APPLICATION->IncludeComponent(
	'bitrix:tasks.list.controls',
	((defined('SITE_TEMPLATE_ID') && (SITE_TEMPLATE_ID === 'bitrix24')) ? 'bitrix24' : '.default'),
	$arComponentParams,
	null,
	array('HIDE_ICONS' => 'Y')
);

?>
<div style="background-color: #fff; min-width: 800px; max-width:1145px; margin: 0 auto; padding: 7px 15px 15px;">
	<?php
	if ( ! empty($arResult['PROJECTS']) )
	{
		?>
		<div class="task-direct-wrap">
			<table class="task-direct-table task-project-table">
				<tr class="task-direct-title">
					<td class="task-direct-name task-direct-cell"><?php
						echo GetMessage('TASKS_PROJECTS_WITH_MY_MEMBERSHIP');
					?></td>
					<td class="task-direct-do task-direct-cell"><span class="task-direct-title-text"><?php
						echo GetMessage('TASKS_PROJECTS_TASK_IN_WORK');
					?></span></td>
					<td class="task-direct-help task-direct-cell"><span class="task-direct-title-text"><?php
						echo GetMessage('TASKS_PROJECTS_TASK_COMPLETE');
					?></span></td>
					<td class="task-direct-instructed task-direct-cell"><span class="task-direct-title-text"><?php
						echo GetMessage('TASKS_PROJECTS_TASK_ALL');
					?></span></td>
				</tr>
				<tr class="task-direct-total">
					<td class="task-direct-total-title task-direct-cell"><?php
						echo GetMessage('TASKS_PROJECTS_SUMMARY');
					?></td>
					<td class="task-direct-cell">
						<span class="task-direct-number-block">
							<span class="task-direct-number"><?php
								echo $arResult['TOTALS']['IN_WORK'];
							?></span>
						</span>
					</td>
					<td class="task-direct-cell">
						<span class="task-direct-number-block">
							<span class="task-direct-number"><?php
								echo $arResult['TOTALS']['COMPLETE'];
							?></span>
						</span>
					</td>
					<td class="task-direct-cell">
						<span class="task-direct-number-block">
							<span class="task-direct-number"><?php
								echo $arResult['TOTALS']['ALL'];
							?></span>
						</span>
					</td>
				</tr>

				<?php
				$cls = 'task-direct-white-row';
				foreach ($arResult['PROJECTS'] as $groupId => $arProject)
				{
					if ($cls === 'task-direct-white-row')
						$cls = 'task-direct-grey-row';
					else
						$cls = 'task-direct-white-row';

					$listId = 'tasks_project_list_' . $arProject['ID'];

					?>
					<tr class="task-direct-responsible <?php echo $cls; ?>">
						<td class="task-direct-cell">
							<div class="task-direct-respons-block">
								<div class="task-direct-respons-img"><?php echo $arProject['IMAGE_HTML']; ?></div>
								<span class="task-direct-respons-alignment"></span
								><span class="task-direct-respons-right"
									><a href="<?php echo $arProject['PATHES']['IN_WORK']; ?>" class="task-direct-respons-name"><?php
										echo $arProject['TITLE'];
									?></a><span class="task-direct-respons-post"><?php
									if ($arProject['HEADS_COUNT'] > 1)
										echo GetMessage('TASKS_PROJECTS_HEADS');
									else
										echo GetMessage('TASKS_PROJECTS_HEAD');
									?></span><?php
									foreach ($arProject['HEADS'] as $arHead)
									{
										?><a href="<?php echo $arHead['HREF']; ?>" class="task-project-director" 
											title="<?php echo $arHead['FORMATTED_NAME'] ?>"
											<?php
											if ($arHead['PHOTO_SRC'])
											{
												?>style="background: url('<?php echo $arHead['PHOTO_SRC']; ?>') no-repeat center center;"<?php
											}
											?>
										></a><?php
									}
									?><span class="task-project-party"><?php
										if ($arProject['NOT_HEADS_COUNT'])
										{
											echo CTasksTools::getMessagePlural(
												$arProject['NOT_HEADS_COUNT'],
												'TASKS_PROJECTS_MEMBERS',
												array(
													'#SPAN#'  => '<span id="' . $listId . '" class="task-project-party-list">',
													'#COUNT#' =>  $arProject['NOT_HEADS_COUNT'],
													'#/SPAN#' => '</span>'
												)
											);
										}
									?></span>
									<script type="text/javascript">
									(function(){
										var x1 = new tasksProjectsOverviewNS.userPopupList(<?php echo $arProject['MEMBERS_FOR_JS']; ?>);
										BX.bind(BX('<?php echo $listId; ?>'), "click", BX.proxy(x1.showEmployees, x1));
									})();
									</script>
								</span>
							</div>
						</td>
						<td class="task-direct-cell">
							<span class="task-direct-number-block">
								<a href="<?php echo $arProject['PATHES']['IN_WORK']; ?>" class="task-direct-number"><?php
										echo $arProject['COUNTERS']['IN_WORK'];
								?></a><?php
								if ($arProject['COUNTERS']['EXPIRED'])
								{
									?><span class="task-direct-counter"><?php echo $arProject['COUNTERS']['EXPIRED']; ?></span><?php
								}
								?>
							</span>
						</td>
						<td class="task-direct-cell">
							<span class="task-direct-number-block">
								<a href="<?php echo $arProject['PATHES']['COMPLETE']; ?>" class="task-direct-number"><?php
										echo $arProject['COUNTERS']['COMPLETE'];
								?></a>
							</span>
						</td>
						<td class="task-direct-cell">
							<span class="task-direct-number-block">
								<a href="<?php echo $arProject['PATHES']['ALL']; ?>" class="task-direct-number"><?php
										echo $arProject['COUNTERS']['ALL'];
								?></a>
							</span>
						</td>
					</tr>
					<?php
				}
				?>
			</table>
		</div>
		<?php
	}
	else
		echo GetMessage('TASKS_PROJECTS_OVERVIEW_NO_DATA');
	?>
</div>
