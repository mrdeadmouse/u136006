<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?

if ($this->__component->__parent && $this->__component->__parent->__templatePage && in_array($this->__component->__parent->__templatePage, array("user_photo", "group_photo")))
{

	if ($this->__component->__parent && $this->__component->__parent->arParams && array_key_exists("PERMISSION", $this->__component->__parent->arParams))
		$arResult["Modifier"]["PERMISSION"] = $this->__component->__parent->arParams["PERMISSION"];

	if (
		$this->__component->__parent->__templatePage == "user_photo" 
		&& $this->__component->__parent && $this->__component->__parent->arResult 
		&& array_key_exists("~PATH_TO_USER_PHOTO_SECTION_EDIT", $this->__component->__parent->arResult)
	)
		$arResult["Modifier"]["SECTION_EDIT_URL"] = $this->__component->__parent->arResult["~PATH_TO_USER_PHOTO_SECTION_EDIT"];
	elseif (
		$this->__component->__parent && $this->__component->__parent->arResult 
		&& array_key_exists("~PATH_TO_GROUP_PHOTO_SECTION_EDIT", $this->__component->__parent->arResult)
	)
		$arResult["Modifier"]["SECTION_EDIT_URL"] = $this->__component->__parent->arResult["~PATH_TO_GROUP_PHOTO_SECTION_EDIT"];		
		
	if (
		$this->__component->__parent->__templatePage == "user_photo" 	
		&& $this->__component->__parent && $this->__component->__parent->arResult 
		&& array_key_exists("~PATH_TO_USER_PHOTO_ELEMENT_UPLOAD", $this->__component->__parent->arResult)
	)
		$arResult["Modifier"]["UPLOAD_URL"] = $this->__component->__parent->arResult["~PATH_TO_USER_PHOTO_ELEMENT_UPLOAD"];
	elseif (
		$this->__component->__parent && $this->__component->__parent->arResult 
		&& array_key_exists("~PATH_TO_GROUP_PHOTO_ELEMENT_UPLOAD", $this->__component->__parent->arResult)
	)
		$arResult["Modifier"]["UPLOAD_URL"] = $this->__component->__parent->arResult["~PATH_TO_GROUP_PHOTO_ELEMENT_UPLOAD"];		

	if ($this->__component->__parent && $this->__component->__parent->arResult && array_key_exists("GALLERY", $this->__component->__parent->arResult["VARIABLES"]))
		$arResult["Modifier"]["USER_ALIAS"] = $this->__component->__parent->arResult["VARIABLES"]["GALLERY"]["CODE"];

	if ($this->__component->__parent && $this->__component->__parent->arParams && array_key_exists("PHOTO", $this->__component->__parent->arParams))
		$arResult["Modifier"]["SORT_BY"] = $this->__component->__parent->arParams["PHOTO"]["ALL"]["SECTION_SORT_BY"];
		
	if ($this->__component->__parent && $this->__component->__parent->arParams && array_key_exists("PHOTO", $this->__component->__parent->arParams))
		$arResult["Modifier"]["SORT_ORD"] = $this->__component->__parent->arParams["PHOTO"]["ALL"]["SECTION_SORT_ORD"];

	if (
		$this->__component->__parent->__templatePage == "user_photo" 	
		&& $this->__component->__parent 
		&& $this->__component->__parent->arParams 
		&& array_key_exists("PHOTO_USER_IBLOCK_ID", $this->__component->__parent->arParams)
	)
		$arResult["Modifier"]["IBLOCK_ID"] = $this->__component->__parent->arParams["PHOTO_USER_IBLOCK_ID"];
	elseif (
		$this->__component->__parent 
		&& $this->__component->__parent->arParams 
		&& array_key_exists("PHOTO_GROUP_IBLOCK_ID", $this->__component->__parent->arParams)
	)
		$arResult["Modifier"]["IBLOCK_ID"] = $this->__component->__parent->arParams["PHOTO_GROUP_IBLOCK_ID"];
		
	$arResult["GALLERY"]["LINK"]["NEW"] = $arResult['SECTION']['NEW_LINK'];
	$arResult["GALLERY"]["LINK"]["UPLOAD"] = $arResult['SECTION']['UPLOAD_LINK'];
}
?>