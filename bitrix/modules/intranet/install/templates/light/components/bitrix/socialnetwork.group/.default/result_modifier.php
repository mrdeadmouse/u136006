<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (is_array($arResult["Owner"]))
{
	if (intval($arResult["Owner"]["USER_PERSONAL_PHOTO"]) > 0)
		$arImage = CFile::ResizeImageGet($arResult["Owner"]["USER_PERSONAL_PHOTO"], array("width"=>30, "height"=>30), BX_RESIZE_IMAGE_EXACT);
	else
	{
		switch ($arResult["Owner"]["USER_PERSONAL_GENDER"])
		{
			case "M":
				$suffix = "male";
				break;
			case "F":
				$suffix = "female";
				break;
			default:
				$suffix = "unknown";
		}

		$arResult["Owner"]["USER_PERSONAL_PHOTO"] = COption::GetOptionInt("socialnetwork", "default_user_picture_".$suffix, false, SITE_ID);

		if (intval($arResult["Owner"]["USER_PERSONAL_PHOTO"]) > 0)
			$arImage = CFile::ResizeImageGet($arResult["Owner"]["USER_PERSONAL_PHOTO"], array("width"=>30, "height"=>30), BX_RESIZE_IMAGE_EXACT);
		else
			$arImage = array("src" => "/bitrix/images/socialnetwork/nopic_30x30.gif");
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
				$arImage = CFile::ResizeImageGet($arModerator["USER_PERSONAL_PHOTO"], array("width"=>30, "height"=>30), BX_RESIZE_IMAGE_EXACT);
			else
			{
				switch ($arModerator["USER_PERSONAL_GENDER"])
				{
					case "M":
						$suffix = "male";
						break;
					case "F":
						$suffix = "female";
						break;
					default:
						$suffix = "unknown";
				}

				$arModerator["USER_PERSONAL_PHOTO"] = COption::GetOptionInt("socialnetwork", "default_user_picture_".$suffix, false, SITE_ID);

				if (intval($arModerator["USER_PERSONAL_PHOTO"]) > 0)
					$arImage = CFile::ResizeImageGet($arModerator["USER_PERSONAL_PHOTO"], array("width"=>30, "height"=>30), BX_RESIZE_IMAGE_EXACT);
				else
					$arImage = array("src" => "/bitrix/images/socialnetwork/nopic_30x30.gif");
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
				$arImage = CFile::ResizeImageGet($arMember["USER_PERSONAL_PHOTO"], array("width"=>30, "height"=>30), BX_RESIZE_IMAGE_EXACT);
			else
			{
				switch ($arMember["USER_PERSONAL_GENDER"])
				{
					case "M":
						$suffix = "male";
						break;
					case "F":
						$suffix = "female";
						break;
					default:
						$suffix = "unknown";
				}

				$arMember["USER_PERSONAL_PHOTO"] = COption::GetOptionInt("socialnetwork", "default_user_picture_".$suffix, false, SITE_ID);

				if (intval($arMember["USER_PERSONAL_PHOTO"]) > 0)
					$arImage = CFile::ResizeImageGet($arMember["USER_PERSONAL_PHOTO"], array("width"=>30, "height"=>30), BX_RESIZE_IMAGE_EXACT);
				else
					$arImage = array("src" => "/bitrix/images/socialnetwork/nopic_30x30.gif");
			}
			
			$arResult["Members"]["List"][$key]["USER_PERSONAL_PHOTO_FILE"]["SRC"] = $arImage["src"];			
		}
	}
}
?>