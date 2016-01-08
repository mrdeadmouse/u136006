<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

//delayed function must return a string
if(empty($arResult))
	return "";

//if($arResult[count($arResult)-1]["LINK"]!="" && $arResult[count($arResult)-1]["LINK"]!=$GLOBALS["APPLICATION"]->GetCurPage(false))
	//$arResult[] = Array("TITLE"=>$GLOBALS["APPLICATION"]->GetTitle());

for($index = 0, $itemSize = count($arResult); $index < $itemSize; $index++)
{
	if ($index > 0)
		$strReturn .= '<span>&nbsp;&ndash;&nbsp;</span>';

	$title = htmlspecialcharsex($arResult[$index]["TITLE"]);

	$strReturn .= '<a href="'.$arResult[$index]["LINK"].'" title="'.$title.'">'.$title.'</a>';

}

return $strReturn;
?>
