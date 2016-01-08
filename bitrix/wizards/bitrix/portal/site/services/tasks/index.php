<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if(!CModule::IncludeModule("tasks"))
	return;

if (WIZARD_FIRST_INSTAL !== "Y")
{
	COption::SetOptionString("tasks", "paths_task_user", WIZARD_SITE_DIR."company/personal/user/#user_id#/tasks/", false, WIZARD_SITE_ID);
	COption::SetOptionString("tasks", "paths_task_user_entry",  WIZARD_SITE_DIR."company/personal/user/#user_id#/tasks/task/view/#task_id#/", false, WIZARD_SITE_ID);
	COption::SetOptionString("tasks", "paths_task_user_edit", WIZARD_SITE_DIR."company/personal/user/#user_id#/tasks/task/edit/#task_id#/", false, WIZARD_SITE_ID);
	COption::SetOptionString("tasks", "paths_task_user_action", WIZARD_SITE_DIR."company/personal/user/#user_id#/tasks/task/#action#/#task_id#/", false, WIZARD_SITE_ID);
	COption::SetOptionString("tasks", "paths_task_group",  WIZARD_SITE_DIR."workgroups/group/#group_id#/tasks/", false, WIZARD_SITE_ID);
	COption::SetOptionString("tasks", "paths_task_group_entry",  WIZARD_SITE_DIR."workgroups/group/#group_id#/tasks/task/view/#task_id#/", false, WIZARD_SITE_ID);
	COption::SetOptionString("tasks", "paths_task_group_edit", WIZARD_SITE_DIR."workgroups/group/#group_id#/tasks/task/edit/#task_id#/", false, WIZARD_SITE_ID);
	COption::SetOptionString("tasks", "paths_task_group_action", WIZARD_SITE_DIR."workgroups/group/#group_id#/tasks/task/#action#/#task_id#/", false, WIZARD_SITE_ID);
}
?>