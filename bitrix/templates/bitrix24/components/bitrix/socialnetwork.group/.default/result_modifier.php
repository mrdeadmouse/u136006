<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (is_array($arResult["Owner"]))
{
	if (intval($arResult["Owner"]["USER_PERSONAL_PHOTO"]) > 0)
	{
		$arImage = CFile::ResizeImageGet(
			$arResult["Owner"]["USER_PERSONAL_PHOTO"], 
			array("width" => 58, "height" => 58), 
			BX_RESIZE_IMAGE_EXACT
		);
	}
	else
	{
		$arImage = array("src" => "");
	}
	
	$arResult["Owner"]["USER_PERSONAL_PHOTO_FILE"]["SRC"] = $arImage["src"];
}

if (is_array($arResult["Moderators"]["List"]))
{
	foreach($arResult["Moderators"]["List"] as $key => $arModerator)
	{
		if (is_array($arModerator))
		{
			if (intval($arModerator["USER_PERSONAL_PHOTO"]) > 0)
			{
				$arImage = CFile::ResizeImageGet(
					$arModerator["USER_PERSONAL_PHOTO"], 
					array("width" => 58, "height" => 58), 
					BX_RESIZE_IMAGE_EXACT
				);
			}
			else
			{
				$arImage = array("src" => "");
			}
			
			$arResult["Moderators"]["List"][$key]["USER_PERSONAL_PHOTO_FILE"]["SRC"] = $arImage["src"];			
		}
	}
}

if (is_array($arResult["Members"]["List"]))
{
	foreach($arResult["Members"]["List"] as $key => $arMember)
	{
		if (is_array($arMember))
		{
			if (intval($arMember["USER_PERSONAL_PHOTO"]) > 0)
			{
				$arImage = CFile::ResizeImageGet(
					$arMember["USER_PERSONAL_PHOTO"], 
					array("width" => 58, "height" => 58), 
					BX_RESIZE_IMAGE_EXACT
				);
			}
			else
			{
				$arImage = array("src" => "");
			}
			
			$arResult["Members"]["List"][$key]["USER_PERSONAL_PHOTO_FILE"]["SRC"] = $arImage["src"];			
		}
	}
}
?>