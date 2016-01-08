<?define("NOT_CHECK_PERMISSIONS", true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if ($_SERVER["REQUEST_METHOD"]=="POST" && isset($_POST["active"]) && check_bitrix_sessid())
{
	if (CModule::IncludeModule("socialnetwork"))
	{
		$res = false;
		$canEdit = ($USER->CanDoOperation('edit_own_profile') || $USER->IsAdmin()) ? "Y" : "N";
		$CurrentUserPerms = CSocNetUserPerms::InitUserPerms($USER->GetID(), $_POST["user_id"], CSocNetUser::IsCurrentUserModuleAdmin($_POST["site_id"], (CModule::IncludeModule("bitrix24") && CBitrix24::IsPortalAdmin($USER->GetID()) ? false : true)));

		if (
			$CurrentUserPerms["Operations"]["modifyuser_main"]
			&& $canEdit == 'Y'
			&& $_POST["user_id"] != $USER->GetID()
		)
		{
			if ($_POST["active"] == "D")
			{
				$res = $USER->Delete($_POST["user_id"]);
			}
			else
			{
				$arFields = array();
				if ($_POST["active"] == "N")
					$arFields["ACTIVE"] = "N";
				elseif ($_POST["active"] == "Y")
					$arFields["ACTIVE"] = "Y";
				$res = $USER->Update($_POST["user_id"], $arFields);
			}
		}
		if ($res)
			echo 1;
		else
			echo 0;
	}
}

if ($_SERVER["REQUEST_METHOD"]=="POST" && isset($_POST["action"]) && check_bitrix_sessid())
{
	$action = trim($_POST["action"]);
	$arJsonData = array();

	$userId = trim($_POST["userId"]);

	switch ($action)
	{
		case "deactivate":
			if (CModule::IncludeModule("security"))
			{
				$numDays = intval($_POST["numDays"]);
				$res = CSecurityUser::DeactivateUserOtp($userId, $numDays);
				if ($res)
					$arJsonData["success"] = "Y";
				else
					$arJsonData["error"] = "Y";
			}
			else
			{
				$arJsonData["error"] = "Y";
			}
			break;

		case "activate":
			if (CModule::IncludeModule("security"))
			{
				$res = CSecurityUser::ActivateUserOtp($userId);
				if ($res)
					$arJsonData["success"] = "Y";
				else
					$arJsonData["error"] = "Y";
			}
			else
			{
				$arJsonData["error"] = "Y";
			}
			break;

		case "defer":
			if (CModule::IncludeModule("security"))
			{
				$numDays = intval($_POST["numDays"]);
				$res = CSecurityUser::DeferUserOtp($userId, $numDays);
				if ($res)
					$arJsonData["success"] = "Y";
				else
					$arJsonData["error"] = "Y";
			}
			else
			{
				$arJsonData["error"] = "Y";
			}
			break;
	}

	echo \Bitrix\Main\Web\Json::encode($arJsonData);

}
?>
