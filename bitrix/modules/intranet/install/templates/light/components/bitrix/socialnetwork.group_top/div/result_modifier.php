<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (is_array($arResult['Groups']))
{
	foreach ($arResult['Groups'] as $key => $arGroup)
	{
		if ($arGroup['IMAGE_ID'])
		{
			$imageFile = CFile::GetFileArray($arGroup['IMAGE_ID']);
			if ($imageFile !== false)
				$arFileTmp = CFile::ResizeImageGet(
					$imageFile,
					array("width" => 42, "height" => 42),
					BX_RESIZE_IMAGE_EXACT,
					true
				);

			if($arFileTmp && array_key_exists("src", $arFileTmp))
				$arGroup["IMAGE_IMG"] = CFile::ShowImage($arFileTmp["src"], 42, 42, ' title="'.$arGroup["NAME"].'" alt="'.$arGroup["NAME"].'" ', $arGroup["GROUP_URL"]);
		}
		else
			$arGroup["IMAGE_IMG"] = '<a href="'.$arGroup["GROUP_URL"].'"><img src="/bitrix/templates/'.SITE_TEMPLATE_ID.'/components/bitrix/socialnetwork.group_top/div/images/nopic-group-42x42.gif" width="42" height="42" border="0" title="'.$arGroup["NAME"].'" alt="'.$arGroup["NAME"].'"></a>';

		$arResult['Groups'][$key] = $arGroup;
	}
}
?>