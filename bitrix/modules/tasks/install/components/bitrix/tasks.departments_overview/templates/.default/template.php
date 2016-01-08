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
	'MARK_SECTION_MANAGE'    => 'Y',
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
	if ( ! empty($arResult['DEPARTMENTS']) )
	{
		foreach ($arResult['DEPARTMENTS'] as $departmentId => &$arDepartment)
		{
			?>
			<div class="task-direct-wrap">
				<table class="task-direct-table">
					<tr class="task-direct-title">
						<td class="task-direct-name task-direct-cell"
							><a href="<?php
								echo CComponentEngine::MakePathFromTemplate(
									$arResult["PATH_TO_COMPANY_DEPARTMENT"],
									array('ID' => $departmentId)
								);
								?>"><?php echo $arDepartment['TITLE']; ?></a></td>
						<td class="task-direct-do task-direct-cell"><span class="task-direct-title-text"><?php
							echo GetMessage('TASKS_DEPARTMENTS_OVERVIEW_RESPONSIBLES_V2');
						?></span></td>
						<td class="task-direct-help task-direct-cell"><span class="task-direct-title-text"><?php
							echo GetMessage('TASKS_DEPARTMENTS_OVERVIEW_ACCOMPLICES_V2');
						?></span></td>
						<td class="task-direct-instructed task-direct-cell"><span class="task-direct-title-text"><?php
							echo GetMessage('TASKS_DEPARTMENTS_OVERVIEW_ORIGINATORS_V2');
						?></span></td>
						<td class="task-direct-watching task-direct-cell"><span class="task-direct-title-text"><?php
							echo GetMessage('TASKS_DEPARTMENTS_OVERVIEW_AUDITORS_V2');
						?></span></td>
					</tr>
					<?php 
					$arListsModes = array('CURRENT_DEP_USERS', 'SUB_DEP_USERS');
					foreach ($arListsModes as $listMode)
					{
						$cls = 'task-direct-white-row';
						foreach ($arDepartment['USERS'] as &$arUser)
						{
							if ($listMode === 'CURRENT_DEP_USERS')
							{
								// Skip users not in current department
								if ($arUser['USER_IN_SUBDEPS'] === 'Y')
									continue;
							}
							elseif ($listMode === 'SUB_DEP_USERS')
							{
								// Skip users in current department 
								if ($arUser['USER_IN_SUBDEPS'] === 'N')
									continue;
							}
							?>
							<tr class="task-direct-responsible <?php echo $cls; ?>">
								<td class="task-direct-cell">
									<div class="task-direct-respons-block">
										<div class="task-direct-respons-img" <?php if ($arUser['PHOTO']):?> style="background: url('<?php echo $arUser['PHOTO']['CACHE']['src']; ?>') no-repeat center center; "<?php endif?>></div>
										<span class="task-direct-respons-alignment"></span><span
											class="task-direct-respons-right"><a href="<?php echo $arUser['RESPONSIBLES_TOTAL_HREF']; ?>"
											class="task-direct-respons-name"><?php
												echo $arUser['FORMATTED_NAME'];
											?></a><span
											class="task-direct-respons-post"><?php
												if ($arUser['DEPARTMENT_HEAD'] === 'Y')
												{
													?><strong><?php
														echo GetMessage('TASKS_DEPARTMENTS_OVERVIEW_DEPARTMENT_HEAD');
													?>,</strong> <?php
												}

												echo $arUser['WORK_POSITION'];
											?></span>
										</span>
									</div>
								</td>
								<td class="task-direct-cell">
									<span class="task-direct-number-block">
										<a href="<?php
											echo $arUser['RESPONSIBLES_TOTAL_HREF'];
											?>" class="task-direct-number"><?php
											echo $arUser['RESPONSIBLES_TOTAL_TASKS'];
										?></a><?php
											if ($arUser['RESPONSIBLES_NOTICED_TASKS'])
											{
												?><span class="task-direct-counter"><?php
													echo $arUser['RESPONSIBLES_NOTICED_TASKS'];
												?></span><?php
											}
										?>
									</span>
								</td>
								<td class="task-direct-cell">
									<span class="task-direct-number-block">
										<a href="<?php
											echo $arUser['ACCOMPLICES_TOTAL_HREF'];
											?>" class="task-direct-number"><?php
											echo $arUser['ACCOMPLICES_TOTAL_TASKS'];
										?></a><?php
											if ($arUser['ACCOMPLICES_NOTICED_TASKS'])
											{
												?><span class="task-direct-counter"><?php
													echo $arUser['ACCOMPLICES_NOTICED_TASKS'];
												?></span><?php
											}
										?>
									</span>
								</td>
								<td class="task-direct-cell">
									<span class="task-direct-number-block">
										<a href="<?php
											echo $arUser['ORIGINATORS_TOTAL_HREF'];
											?>" class="task-direct-number"><?php
											echo $arUser['ORIGINATORS_TOTAL_TASKS'];
										?></a><?php
											if ($arUser['ORIGINATORS_NOTICED_TASKS'])
											{
												?><span class="task-direct-counter"><?php
													echo $arUser['ORIGINATORS_NOTICED_TASKS'];
												?></span><?php
											}
										?>
									</span>
								</td>
								<td class="task-direct-cell">
									<span class="task-direct-number-block">
										<a href="<?php
											echo $arUser['AUDITORS_TOTAL_HREF'];
											?>" class="task-direct-number"><?php
											echo $arUser['AUDITORS_TOTAL_TASKS'];
										?></a><?php
											if ($arUser['AUDITORS_NOTICED_TASKS'])
											{
												?><span class="task-direct-counter"><?php
													echo $arUser['AUDITORS_NOTICED_TASKS'];
												?></span><?php
											}
										?>
									</span>
								</td>
							</tr>
							<?php

							if ($cls === 'task-direct-white-row')
								$cls = 'task-direct-grey-row';
							else
								$cls = 'task-direct-white-row';
						}
						unset($arUser);

						if ($listMode === 'CURRENT_DEP_USERS')
						{
							?>
							<tr class="task-direct-total">
								<td class="task-direct-total-title task-direct-cell"><?php
									echo GetMessage('TASKS_DEPARTMENTS_OVERVIEW_TOTAL_TASK_PER_DEPARTMENT');
								?></td>
								<td class="task-direct-cell">
									<span class="task-direct-number-block">
										<span class="task-direct-number"><?php
											echo $arDepartment['RESPONSIBLES_TOTAL_TASKS'];
										?></span><?php
											if ($arDepartment['RESPONSIBLES_NOTICED_TASKS'])
											{
												?><span class="task-direct-counter"><?php
													echo $arDepartment['RESPONSIBLES_NOTICED_TASKS'];
												?></span><?php
											}
										?>
									</span>
								</td>
								<td class="task-direct-cell">
									<span class="task-direct-number-block">
										<span class="task-direct-number"><?php
											echo $arDepartment['ACCOMPLICES_TOTAL_TASKS'];
										?></span><?php
											if ($arDepartment['ACCOMPLICES_NOTICED_TASKS'])
											{
												?><span class="task-direct-counter"><?php
													echo $arDepartment['ACCOMPLICES_NOTICED_TASKS'];
												?></span><?php
											}
										?>
									</span>
								</td>
								<td class="task-direct-cell">
									<span class="task-direct-number-block">
										<span class="task-direct-number"><?php
											echo $arDepartment['ORIGINATORS_TOTAL_TASKS'];
										?></span><?php
											if ($arDepartment['ORIGINATORS_NOTICED_TASKS'])
											{
												?><span class="task-direct-counter"><?php
													echo $arDepartment['ORIGINATORS_NOTICED_TASKS'];
												?></span><?php
											}
										?>
									</span>
								</td>
								<td class="task-direct-cell">
									<span class="task-direct-number-block">
										<span class="task-direct-number"><?php
											echo $arDepartment['AUDITORS_TOTAL_TASKS'];
										?></span><?php
											if ($arDepartment['AUDITORS_NOTICED_TASKS'])
											{
												?><span class="task-direct-counter"><?php
													echo $arDepartment['AUDITORS_NOTICED_TASKS'];
												?></span><?php
											}
										?>
									</span>
								</td>
							</tr>
							<?php

							if ($arDepartment['COUNT_OF_MANAGED_USERS_IN_SUBDEPS'] > 0)
							{
								?>
								<tr class="task-direct-total-label">
									<td class="task-direct-cell"><?php echo GetMessage('TASKS_DEPARTMENTS_OVERVIEW_USERS_IN_SUBDEPS'); ?></td>
									<td class="task-direct-cell"></td>
									<td class="task-direct-cell"></td>
									<td class="task-direct-cell"></td>
									<td class="task-direct-cell"></td>
								</tr>
								<?php
							}
							else
								break;
						}
					}

					foreach ($arDepartment['SUBDEPARTMENTS'] as &$arSubDepartment)
					{
						$arCounters = $arSubDepartment['COUNTERS'];
						?>
						<tr class="task-direct-subdepart">
							<td class="task-direct-cell">
								<span class="task-direct-subdepart-sign"></span>
								<a class="task-direct-subdepart-link" href="<?php
									echo $arSubDepartment['HREF'];
									?>"><?php
										echo $arSubDepartment['TITLE'];
								?></a>
							</td>
							<td class="task-direct-cell">
								<span class="task-direct-number-block">
									<span class="task-direct-number"><?php
										echo $arCounters['RESPONSIBLES_TOTAL_TASKS'];
									?></span><?php
										if ($arCounters['RESPONSIBLES_NOTICED_TASKS'])
										{
											?><span class="task-direct-counter"><?php
												echo $arCounters['RESPONSIBLES_NOTICED_TASKS'];
											?></span><?php
										}
									?>
								</span>
							</td>
							<td class="task-direct-cell">
								<span class="task-direct-number-block">
									<span class="task-direct-number"><?php
										echo $arCounters['ACCOMPLICES_TOTAL_TASKS'];
									?></span><?php
										if ($arCounters['ACCOMPLICES_NOTICED_TASKS'])
										{
											?><span class="task-direct-counter"><?php
												echo $arCounters['ACCOMPLICES_NOTICED_TASKS'];
											?></span><?php
										}
									?>
								</span>
							</td>
							<td class="task-direct-cell">
								<span class="task-direct-number-block">
									<span class="task-direct-number"><?php
										echo $arCounters['ORIGINATORS_TOTAL_TASKS'];
									?></span><?php
										if ($arCounters['ORIGINATORS_NOTICED_TASKS'])
										{
											?><span class="task-direct-counter"><?php
												echo $arCounters['ORIGINATORS_NOTICED_TASKS'];
											?></span><?php
										}
									?>
								</span>
							</td>
							<td class="task-direct-cell">
								<span class="task-direct-number-block">
									<span class="task-direct-number"><?php
										echo $arCounters['AUDITORS_TOTAL_TASKS'];
									?></span><?php
										if ($arCounters['AUDITORS_NOTICED_TASKS'])
										{
											?><span class="task-direct-counter"><?php
												echo $arCounters['AUDITORS_NOTICED_TASKS'];
											?></span><?php
										}
									?>
								</span>
							</td>
						</tr>
						<?php
					}
					unset($arSubDepartment);
					?>
				</table>
			</div>
			<?php
		}
		unset($arDepartment);
	}
	else
		echo GetMessage('TASKS_DEPARTMENTS_OVERVIEW_NO_DATA');
	?>
</div>
