<?define("NOT_CHECK_PERMISSIONS", true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if ($_SERVER["REQUEST_METHOD"]=="POST" && strlen($_POST["delete_demo_banner"])>0 && $_POST["delete_demo_banner"]=="Y" && check_bitrix_sessid()  && $USER->IsAdmin())
{
	COption::SetOptionString("intranet", "wizard_show_demo_delete", "N", false, SITE_ID);
}
?>