<?
define("NO_KEEP_STATISTIC", true);
define("BX_STATISTIC_BUFFER_USED", false);
define("NO_LANG_FILES", true);
define("NOT_CHECK_PERMISSIONS", true);
define("BX_PUBLIC_TOOLS", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/bx_root.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

if(CModule::IncludeModule("compression"))
	CCompress::Disable2048Spaces();

if (!CModule::IncludeModule("socialnetwork"))
{
	echo CUtil::PhpToJsObject(Array('ERROR' => 'SONET_MODULE_NOT_INSTALLED'));
	die();
}

if (!$GLOBALS["USER"]->IsAuthorized())
{
	echo CUtil::PhpToJsObject(Array("ERROR" => "CURRENT_USER_NOT_AUTH"));
	die();
}

$groupID = intval($_POST["groupID"]);
if ($groupID <= 0)
{
	echo CUtil::PhpToJsObject(Array("ERROR" => "EMPTY_GROUP_ID"));
	die();
}

if (check_bitrix_sessid())
{
	if (in_array($_POST["action"], array("set", "unset")))
	{
		$userRole = CSocNetUserToGroup::GetUserRole($GLOBALS["USER"]->GetID(), $groupID);
		if (!in_array($userRole, array(SONET_ROLES_OWNER, SONET_ROLES_MODERATOR, SONET_ROLES_USER)))
		{
			echo CUtil::PhpToJsObject(Array("ERROR" => "INCORRECT_USER_ROLE"));
			die();
		}

		if (CSocNetSubscription::Set($GLOBALS["USER"]->GetID(), "SG".$groupID, ($_POST["action"] == "set" ? "Y" : "N")))
		{
			$rsSubscription = CSocNetSubscription::GetList(
				array(),
				array(
					"USER_ID" => $GLOBALS["USER"]->GetID(), 
					"CODE" => "SG".$groupID
				)
			);
			if ($arSubscription = $rsSubscription->Fetch())
				echo CUtil::PhpToJsObject(Array(
					"SUCCESS" => "Y",
					"RESULT" => "Y"
				));
			else
				echo CUtil::PhpToJsObject(Array(
					"SUCCESS" => "Y",
					"RESULT" => "N"
				));
		}
	}
	else
		echo CUtil::PhpToJsObject(Array("ERROR" => "UNKNOWN_ACTION"));
}
else
	echo CUtil::PhpToJsObject(Array("ERROR" => "SESSION_ERROR"));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>