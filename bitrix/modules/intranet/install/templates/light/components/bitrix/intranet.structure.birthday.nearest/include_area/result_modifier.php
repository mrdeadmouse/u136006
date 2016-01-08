<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arParams['NAME_TEMPLATE'] = $arParams['NAME_TEMPLATE'] ? $arParams['NAME_TEMPLATE'] : CSite::GetNameFormat();

$cacheTtl = 604800;
$cacheID = 'bx_cp_intranet_user_info_'.$GLOBALS["USER"]->GetID().'_'.$arParams['bShowFilter'];
if ($arResult['DEPARTMENT'] > 0)
{
	$cacheID .= '_'.$arResult['DEPARTMENT'];
}

$cacheDir = SITE_ID.'/intranet/user_info/';
$obUserCache = new CPHPCache;
if($obUserCache->InitCache($cacheTtl, $cacheID, $cacheDir))
{
    $cacheData = $obUserCache->GetVars();
    $arResult['CURRENT_USER'] = $cacheData['CURRENT_USER'];
    $arResult['ONLY_MINE'] = $cacheData['ONLY_MINE'];
    unset($cacheData);
}
else
{
    global $CACHE_MANAGER;
    $CACHE_MANAGER->StartTagCache($cacheDir);
    if ($arParams['bShowFilter'])
    {
		$dbCurrentUser = CUser::GetByID($GLOBALS['USER']->GetID());
		$arResult['CURRENT_USER'] = $dbCurrentUser->Fetch();
		if ($arParams['bShowFilter'] = !!($arResult['CURRENT_USER']['UF_DEPARTMENT']))
		{
			$arResult['CURRENT_USER']['DEPARTMENT_TOP'] = CIntranetUtils::GetIBlockTopSection($arResult['CURRENT_USER']['UF_DEPARTMENT']);
			if (intval($arResult['DEPARTMENT']) == $arResult['CURRENT_USER']['DEPARTMENT_TOP'])
			{
				$arResult['ONLY_MINE'] = 'Y';
			}
		}
    }
    $CACHE_MANAGER->RegisterTag('bx_user_intranet_info_'.$arResult['CURRENT_USER']['ID']);
    $CACHE_MANAGER->EndTagCache();
}
if($obUserCache->StartDataCache())
{
    $obUserCache->EndDataCache(array(
        'CURRENT_USER' => $arResult['CURRENT_USER'],
        'ONLY_MINE' => $arResult['ONLY_MINE']
        )
    );
}

foreach ($arResult['USERS'] as $key => $arUser)
{
	if ($arUser['PERSONAL_PHOTO'])
	{
		$imageFile = CFile::GetFileArray($arUser['PERSONAL_PHOTO']);
		if ($imageFile !== false)
		{
			$arFileTmp = CFile::ResizeImageGet(
				$imageFile,
				array("width" => 42, "height" => 42),
				BX_RESIZE_IMAGE_EXACT,
				true
			);
		}

		if($arFileTmp && array_key_exists("src", $arFileTmp))
			$arUser["PERSONAL_PHOTO"] = CFile::ShowImage($arFileTmp["src"], 42, 42);
	}

	$arResult['USERS'][$key] = $arUser;
}

$arResult["arUserField"] = array();
$arRes = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("USER", 0, LANGUAGE_ID);
if (!empty($arRes))
{
	foreach ($arRes as $key => $val)
	{
		$arResult["arUserField"][$key] = $val;
	}
}
?>