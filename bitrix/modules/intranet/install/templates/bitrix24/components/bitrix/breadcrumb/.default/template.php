<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

//delayed function must return a string
if(empty($arResult))
	return "";

//if($arResult[count($arResult)-1]["LINK"]!="" && $arResult[count($arResult)-1]["LINK"]!=$GLOBALS["APPLICATION"]->GetCurPage(false))
	//$arResult[] = Array("TITLE"=>$GLOBALS["APPLICATION"]->GetTitle());

$strReturn = '<div class="breadcrumbs"><a href="'.SITE_DIR.'" class="breadcrumbs-home"><i></i></a>';

for($index = 0, $itemSize = count($arResult); $index < $itemSize; $index++)
{
	//if ($index > 0)
		//$strReturn .= '<span>&nbsp;&ndash;&nbsp;</span>';

	$title = htmlspecialcharsex($arResult[$index]["TITLE"]);

	if ($index == ($itemSize - 1))
		$strReturn .= '<span class="breadcrumbs-item-selected" title="'.$title.'">'.$title.'</span>';
	else
		$strReturn .= '<a class="breadcrumbs-item" href="'.$arResult[$index]["LINK"].'" title="'.$title.'">'.$title.'<i></i></a>';

}

$strReturn .= '</div>';

return $strReturn;
?>