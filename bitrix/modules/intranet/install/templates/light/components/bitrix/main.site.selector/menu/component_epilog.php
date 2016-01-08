<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
$arMenu = Array();
foreach ($arResult["SITES"] as $key => $arSite)
{
	$path = $arSite["DIR"];
	if(is_array($arSite['DOMAINS']) && strlen($arSite['DOMAINS'][0]) > 0 || strlen($arSite['DOMAINS']) > 0):
		$path = "http://";
		$path .= (is_array($arSite["DOMAINS"]) ? $arSite["DOMAINS"][0] : $arSite["DOMAINS"]);
		$path .= $arSite["DIR"];
	endif;
	$arMenu[] = Array(
			$arSite["NAME"], 
			$path,
			Array(), 
			Array(), 
			"" 
		);
}
$GLOBALS["arMenuSites"] = $arMenu;
?>