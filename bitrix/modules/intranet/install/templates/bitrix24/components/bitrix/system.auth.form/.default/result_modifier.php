<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arParams["PATH_TO_SONET_PROFILE"] = (isset($arParams["PATH_TO_SONET_PROFILE"]) ? $arParams["PATH_TO_SONET_PROFILE"] : SITE_DIR."company/personal/user/#user_id#/");
$arParams["PATH_TO_SONET_PROFILE_EDIT"] = (isset($arParams["PATH_TO_SONET_PROFILE_EDIT"]) ? $arParams["PATH_TO_SONET_PROFILE_EDIT"] : SITE_DIR."company/personal/user/#user_id#/edit/");
$arParams["THUMBNAIL_SIZE"] = (isset($arParams["THUMBNAIL_SIZE"]) ? intval($arParams["THUMBNAIL_SIZE"]) : 42);

$arResult["USER_FULL_NAME"] = CUser::FormatName("#NAME# #LAST_NAME#", array(
	"NAME" => $USER->GetFirstName(),
	"LAST_NAME" => $USER->GetLastName(),
	"SECOND_NAME" => $USER->GetSecondName(),
	"LOGIN" => $USER->GetLogin()
));

$user_id = intval($GLOBALS["USER"]->GetID());

if(defined("BX_COMP_MANAGED_CACHE"))
	$ttl = 2592000;
else
	$ttl = 600;
$cache_id = 'user_avatar_'.$user_id;
$cache_dir = '/bx/user_avatar';
$obCache = new CPHPCache;

if($obCache->InitCache($ttl, $cache_id, $cache_dir))
{
	$arResult["USER_PERSONAL_PHOTO_SRC"] = $obCache->GetVars();
}
else
{
	if ($GLOBALS["USER"]->IsAuthorized())
	{
		if(defined("BX_COMP_MANAGED_CACHE"))
		{
			global $CACHE_MANAGER;
			$CACHE_MANAGER->StartTagCache($cache_dir);
		}

		$dbUser = CUser::GetByID($GLOBALS["USER"]->GetID());
		$arUser = $dbUser->Fetch();

		$imageFile = false;

		if (intval($arUser["PERSONAL_PHOTO"]) > 0)
		{
			$imageFile = CFile::GetFileArray($arUser["PERSONAL_PHOTO"]);
			if ($imageFile !== false)
			{
				$arFileTmp = CFile::ResizeImageGet(
					$imageFile,
					array("width" => $arParams["THUMBNAIL_SIZE"], "height" => $arParams["THUMBNAIL_SIZE"]),
					BX_RESIZE_IMAGE_EXACT,
					false
				);
				$arResult["USER_PERSONAL_PHOTO_SRC"] = $arFileTmp["src"];
			}
		}
		if(defined("BX_COMP_MANAGED_CACHE"))
		{
			$CACHE_MANAGER->RegisterTag("USER_CARD_".intval($user_id / TAGGED_user_card_size));
			$CACHE_MANAGER->EndTagCache();
		}
	}

	if($obCache->StartDataCache())
	{
		$obCache->EndDataCache($arResult["USER_PERSONAL_PHOTO_SRC"]);
	}
}

// add chache here!!!

if(
	IsModuleInstalled('bitrix24')
	&& COption::GetOptionString('bitrix24', 'network', 'N') == 'Y'
	&& CModule::IncludeModule('socialservices')
)
{
	// also check for B24Net turned on in module settings

	$dbSocservUser = CSocServAuthDB::GetList(
		array(),
		array(
			'USER_ID' => $user_id,
			"EXTERNAL_AUTH_ID" => CSocServBitrix24Net::ID
		), false, false, array("PERSONAL_WWW")
	);
	$arSocservUser = $dbSocservUser->Fetch();
	if($arSocservUser)
	{
		$arResult['B24NET_WWW'] = $arSocservUser['PERSONAL_WWW'];
	}
}

//B24 helper
if (!function_exists("__getVideoStepByUrl"))
{
	function __getVideoStepByUrl($videoSteps, $url)
	{
		$result = ($url == "/" ? $videoSteps[0]["id"] : "other");
		foreach ($videoSteps as $step)
		{
			foreach ($step["patterns"] as $pattern)
			{//echo $pattern."<br>";
				if (preg_match($pattern, $url))
				{
					$result = $step["id"];
					break 2;
				}
			}
		}

		return $result;
	}
}

if (IsModuleInstalled('bitrix24'))
{
	$lastHeroView = CUserOptions::GetOption("bitrix24", "new_helper_last", "");
	if ($lastHeroView && $lastHeroView+60*60 > time())
	{
		$arResult["SHOW_HELPER_HERO"] = "N";
	}
	else
	{
		CUserOptions::SetOption("bitrix24", "new_helper_last", time());

		$arHelperSteps = array(
			array(
				"id" => "start",
				"patterns" => array(),
			),
			array(
				"id" => "tasks",
				"patterns" => array(
					"~^".SITE_DIR."(company|contacts)/personal/user/\\d+/tasks/~",
					"~^".SITE_DIR."workgroups/group/\\d+/tasks/~"
				),
			),
			array(
				"id" => "calendar",
				"patterns" => array(
					"~^".SITE_DIR."(company|contacts)/personal/user/\\d+/calendar/~",
					"~^".SITE_DIR."workgroups/group/\\d+/calendar/~"
				),
			),
			array(
				"id" => "disk",
				"patterns" => array(
					"~^".SITE_DIR."(company|contacts)/personal/user/\\d+/disk/~",
					"~^".SITE_DIR."docs/~",
					"~^".SITE_DIR."workgroups/group/\\d+/disk/~"
				),
			),
			array(
				"id" => "profile",
				"patterns" => array(
					"~^".SITE_DIR."(company|contacts)/personal/user/\\d+/edit/$~",
					"~^".SITE_DIR."(company|contacts)/personal/user/\\d+/passwords/~",
					"~^".SITE_DIR."(company|contacts)/personal/user/\\d+/security/~",
				),
			),
			array(
				"id" => "crm",
				"patterns" => array("~^".SITE_DIR."crm/~"),
			),
			array(
				"id" => "workgroups",
				"patterns" => array("~^".SITE_DIR."workgroups/~"),
			),
			/*array(
				"id" => "company",
				"patterns" => array(
					"~^".SITE_DIR."company/meeting/~",
					"~^".SITE_DIR."company/$~",
					"~^".SITE_DIR."company/vis_structure.php~",
					"~^".SITE_DIR."company/absence.php~",
					"~^".SITE_DIR."company/lists/~",
				),
			),*/
			array(
				"id" => "marketplace",
				"patterns" => array("~^".SITE_DIR."marketplace/~"),
			),
			array(
				"id" => "telephony",
				"patterns" => array("~^".SITE_DIR."settings/telephony/([^/]*\\.php)?$~"),
			),
			/*array(
				"id" => "configs",
				"patterns" => array(
					"~^".SITE_DIR."settings/([^/]*\\.php)?$~",
				)
			),*/
			array(
				"id" => "extranet",
				"patterns" => array("~^".SITE_DIR."$~"),
			),
		);

		$currentStepId = __getVideoStepByUrl($arHelperSteps, $APPLICATION->GetCurPage());
		$arViewedSteps = CUserOptions::GetOption("bitrix24", "new_helper_views", array());

		if (!in_array("start", $arViewedSteps))
		{
			$currentStepId = "start";
		}
		$arResult["SHOW_HELPER_HERO"] = ($currentStepId != "other" && !in_array($currentStepId, $arViewedSteps)) ? "Y" : "N";

		if ($currentStepId != "other" && !in_array($currentStepId, $arViewedSteps))
		{
			$arViewedSteps[] = $currentStepId;
			CUserOptions::SetOption("bitrix24", "new_helper_views", $arViewedSteps);
		}
	}
}
?>