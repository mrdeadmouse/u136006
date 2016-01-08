<?php
$ajaxActions = Array(
	"checkout"=>array(
		"json"=>true,
		"file"=> $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/mobile.data/actions/checkout.php",
		"no_check_auth"=>true
	),
	"service"=>array(
		"json"=>true,
		"file"=> $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/mobile.data/actions/service.php",
		"no_check_auth"=>true
	),
	"list"=>array(
		"json"=>true,
		"file"=> $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/mobile.data/actions/service.php",
		"no_check_auth"=>true
	),
	"get_captcha"=>array(
		"json"=>false,
		"file"=> $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/mobile.data/actions/captcha.php",
		"no_check_auth"=>true
	),
	"save_device_token"=>array(
		"json" => true,
		"needBitrixSessid"=>true,
		"file"=> $_SERVER["DOCUMENT_ROOT"] ."/bitrix/components/bitrix/mobile.data/actions/save_device_token.php"
	),
	"get_user_list" => array(
		"json" => true,
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/mobile.data/actions/users_groups.php"
	),
	"get_group_list"=> array(
		"json" => true,
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/mobile.data/actions/users_groups.php"
	),
	"get_usergroup_list"=> array(
		"json" => true,
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/mobile.data/actions/users_groups.php"
	),
	'get_subordinated_user_list'=> array(
		"json" => true,
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/mobile.data/actions/users_subordinates.php"
	),
	"get_likes"=> array(
		"json" => true,
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/mobile.data/actions/get_likes.php"
	),
	"logout"=> array(
		"file" => ""
	),
	"calendar"=> array(
		"json" => true,
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/mobile.data/actions/calendar.php"
	),
	"calendar_livefeed_view"=> array(
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/calendar.livefeed.view/action.php"
	),
	"like"=> array(
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/rating.vote/vote.ajax.php"
	),
	"pull"=> array(
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/pull.request/ajax.php",
	),
	"im"=> array(
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/im.messenger/im.ajax.php",
	),
	"im_files"=> array(
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/im.messenger/".($_REQUEST["fileType"] == 'show'? 'show.file.php': 'download.file.php'),
		"json"=>false
	),
	"calls"=> array(
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/im.messenger/call.ajax.php",
	),
	"task_router"=> array(
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/mobile.tasks.detail/ajax.php",
	),
	"task_get_group"=> array(
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/mobile.tasks.edit/ajax.php",
	),
	"change_follow"=> array(
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/mobile.socialnetwork.log.ex/ajax.php"
	),
	"change_follow_default"=> array(
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/mobile.socialnetwork.log.ex/ajax.php"
	),
	"change_favorites"=> array(
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/mobile.socialnetwork.log.ex/ajax.php"
	),
	"log_error"=> array(
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/mobile.socialnetwork.log.ex/ajax.php"
	),
	"get_more_destination"=> array(
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/mobile.socialnetwork.log.entry/ajax.php"
	),
	"add_comment"=> array(
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/mobile.socialnetwork.log.entry/ajax.php"
	),
	"edit_comment"=> array(
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/mobile.socialnetwork.log.entry/ajax.php"
	),
	"delete_comment"=> array(
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/mobile.socialnetwork.log.entry/ajax.php"
	),
	"get_comment"=> array(
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/mobile.socialnetwork.log.entry/ajax.php"
	),
	"get_comments"=> array(
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/mobile.socialnetwork.log.entry/ajax.php"
	),
	"delete_post"=> array(
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/templates/mobile_app/components/bitrix/socialnetwork.blog.post/mobile/ajax.php"
	),
	"get_blog_post_data"=> array(
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/templates/mobile_app/components/bitrix/socialnetwork.blog.post/mobile/ajax.php"
	),
	"get_blog_comment_data"=> array(
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/templates/mobile_app/components/bitrix/socialnetwork.blog.post/mobile/ajax.php"
	),
	"get_log_comment_data"=> array(
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/mobile.socialnetwork.log.entry/ajax.php"
	),
	"delete_file"=> array(
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/mobile.file.list/ajax.php"
	),
	"delete_file_element"=> array(
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/mobile.file.list/ajax.php"
	),
	"crm_activity_edit"=> array(
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/mobile.crm.activity.edit/ajax.php"
	),
	"crm_contact_edit"=> array(
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/mobile.crm.contact.edit/ajax.php"
	),
	"crm_lead_edit"=> array(
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/mobile.crm.lead.edit/ajax.php"
	),
	"crm_product_row_edit"=> array(
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/mobile.crm.product_row.edit/ajax.php"
	),
	"crm_company_edit"=> array(
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/mobile.crm.company.edit/ajax.php"
	),
	"crm_config_user_email"=> array(
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/mobile.crm.config.user_email/ajax.php"
	),
	"crm_deal_edit"=> array(
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/mobile.crm.deal.edit/ajax.php"
	),
	"crm_deal_list"=> array(
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/mobile.crm.deal.list/ajax.php"
	),
	"crm_invoice_edit"=> array(
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/mobile.crm.invoice.edit/ajax.php"
	),
	"crm_location_list"=> array(
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/mobile.crm.location.list/ajax.php"
	),
	"crm_product_list"=> array(
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/mobile.crm.product.list/ajax.php"
	),
	"disk_folder_list"=> array(
		"json" => true,
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/mobile.data/actions/disk_folder_list.php",
	),
	"disk_uf_view"=> array(
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/tools/disk/uf.php",
	),
	"disk_download_file"=> array(
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/services/disk/index.php",
	),
	"blog_image"=> array(
		"json" => false,
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/blog/show_file.php"
	),
	"calendar_livefeed"=> array(
		"json" => false,
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/calendar.livefeed.view/action.php"
	),
	"file_upload_log"=> array(
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/mobile.socialnetwork.log.entry/ajax.php"
	),
	"file_upload_blog"=> array(
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/templates/mobile_app/components/bitrix/socialnetwork.blog.post/mobile/ajax.php"
	),
	"send_comment_writing"=> array(
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/mobile.socialnetwork.log.entry/ajax.php"
	),
	"bp_make_action"=> array(
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/templates/mobile_app/components/bitrix/bizproc.task/mobile/ajax.php"
	),
	"bp_livefeed_action"=> array(
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/components/bitrix/bizproc.workflow.livefeed/ajax.php"
	),
	"bp_do_task"=> array(
		"file" => $_SERVER["DOCUMENT_ROOT"] . "/bitrix/tools/bizproc_do_task_ajax.php"
	),
);

return $ajaxActions;