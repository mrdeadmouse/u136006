<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (!CModule::IncludeModule("voximplant")) die();

use Bitrix\Voximplant as VI;

$arResult["GRID_ID"] = "voximpant_statistic_detail";

/*$arResult["FILTER"] = array(
	array("id"=>"PHONE_NUMBER", "name"=>"Phone"),
	array("id"=>"CALL_DURATION", "name"=>"Time"),
	array("id"=>"CALL_START_DATE", "name"=>"Date", "type"=>"date", "default"=>"Y"),
);*/
$gridOptions = new CGridOptions($arResult["GRID_ID"]);
$gridNav = $gridOptions->GetNavParams();
$limit = $gridNav["nPageSize"];
if (!intval($limit))
	$limit = 30;

$page = ($_GET['PAGEN_1']) ? $_GET['PAGEN_1'] : 1;

$filter = array();
$filterByUser = '';
if (isset($_GET['USER_ID']) && intval($_GET['USER_ID']))
{
	$filterByUser = intval($_GET['USER_ID']);
	$filter["PORTAL_USER_ID"] = $filterByUser;

}
$filterByCode = '';
if (isset($_GET['CODE']) && preg_match('#^[[:alnum:]\-]+$#D', $_GET['CODE']))
{
	$filterByCode = $_GET['CODE'];
	$filter["CALL_FAILED_CODE"] = $filterByCode;
}

$parametrs = array(
	'order' => array('CALL_START_DATE'=>'DESC'),
	'filter' => $filter,
	'select' => array('*')
);

if (!$_GET['SHOWALL_1'])
{
	$parametrs['limit'] = $limit;
	$parametrs['offset'] = ($page-1) * $limit;
}

$data = VI\StatisticTable::getList($parametrs);
$result = new CDBResult($data);

$countQuery = new Bitrix\Main\Entity\Query(VI\StatisticTable::getEntity());
$countQuery->addSelect(new \Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(1)'));
$countQuery->setFilter($filter);
$totalCount = $countQuery->setLimit(null)->setOffset(null)->exec()->fetch();
$totalCount = intval($totalCount['CNT']);
if (!$_GET['SHOWALL_1'])
{
	$totalPage = ceil($totalCount/$limit);
	$result->NavStart($limit);
}
else
{
	$totalPage = 1;
	$result->NavStart();
}
$result->NavRecordCount = $totalCount;
$result->NavPageCount = $totalPage;
$result->NavPageNomer = $page;

$arResult["NAV_OBJECT"] = $result;
$arResult["ROWS_COUNT"] = $result->SelectedRowsCount();

$arResult["ELEMENTS_ROWS"] = array();

$arAvailableStatuses = array(
	"200",
	"304",
	"603",
	"404",
	"486",
	"503",
	"480",
	"402",
	"1",
	"2",
	"3",
);

$bModuleCatalog = false;
if (CModule::IncludeModule("catalog"))
{
	$bModuleCatalog = true;
}

$arUserIds = array();
$portalNumbers = CVoxImplantConfig::GetPortalNumbers();
while($data = $result->fetch())
{
	$data = CVoxImplantHistory::PrepereData($data);

	if ($data["PORTAL_USER_ID"] > 0 && !in_array($data["PORTAL_USER_ID"], $arUserIds))
		$arUserIds[] = $data["PORTAL_USER_ID"];

	if (in_array($data["CALL_FAILED_CODE"], Array(1,2,3,409)))
		$data["CALL_FAILED_REASON"] = GetMessage("TELEPHONY_STATUS_".$data["CALL_FAILED_CODE"]);

	if (isset($portalNumbers[$data["PORTAL_NUMBER"]]))
	{
		$data["PORTAL_NUMBER"] = $portalNumbers[$data["PORTAL_NUMBER"]];
	}
	else if (substr($data["PORTAL_NUMBER"], 0, 3) == 'sip')
	{
		$data["PORTAL_NUMBER"] = GetMessage("TELEPHONY_PORTAL_PHONE_SIP_OFFICE", Array('#ID#' => substr($data["PORTAL_NUMBER"], 3)));
	}
	else if (substr($data["PORTAL_NUMBER"], 0, 3) == 'reg')
	{
		$data["PORTAL_NUMBER"] = GetMessage("TELEPHONY_PORTAL_PHONE_SIP_CLOUD", Array('#ID#' => substr($data["PORTAL_NUMBER"], 3)));
	}
	else if (strlen($data["PORTAL_NUMBER"]) <= 0)
	{
		$data["PORTAL_NUMBER"] = GetMessage("TELEPHONY_PORTAL_PHONE_EMPTY");
	}

	if ($data["PORTAL_USER_ID"] == 0 && strlen($data["PHONE_NUMBER"]) <= 0)
	{
		$data["CALL_DURATION_TEXT"] = '';
		$data["INCOMING_TEXT"] = '';
	}

	$arResult["ELEMENTS_ROWS"][] = array("data" => $data, "columns" => array());
}


$arUsers = array();
if (!empty($arUserIds))
{
	$dbUser = CUser::GetList($by="", $order="", array("ID" => implode($arUserIds, " | ")), array("FIELDS" => array("ID", "NAME", "LAST_NAME", "SECOND_NAME", "LOGIN", "PERSONAL_PHOTO")));
	while($arUser = $dbUser->Fetch())
	{
		$arUsers[$arUser["ID"]]["FIO"] =  CUser::FormatName("#NAME# #LAST_NAME#", array(
			"NAME" => $arUser["NAME"],
			"LAST_NAME" => $arUser["LAST_NAME"],
			"SECOND_NAME" => $arUser["SECOND_NAME"],
			"LOGIN" => $arUser["LOGIN"]
		));

		if (intval($arUser["PERSONAL_PHOTO"]) > 0)
		{
			$imageFile = CFile::GetFileArray($arUser["PERSONAL_PHOTO"]);
			if ($imageFile !== false)
			{
				$arFileTmp = CFile::ResizeImageGet(
					$imageFile,
					array("width" => "30", "height" => "30"),
					BX_RESIZE_IMAGE_EXACT,
					false
				);
				$arUsers[$arUser["ID"]]["PHOTO"] = $arFileTmp["src"];
			}
		}
	}
}

foreach($arResult["ELEMENTS_ROWS"] as $key => $arRow)
{
	if ($arRow["data"]["PORTAL_USER_ID"])
	{
		$userHtml = "<span class='tel-stat-user-img user-avatar'";
		if ($arUsers[$arRow["data"]["PORTAL_USER_ID"]]["PHOTO"])
			$userHtml.= "style=\"background: url('".$arUsers[$arRow["data"]["PORTAL_USER_ID"]]["PHOTO"]."') no-repeat center;\"";
		$userHtml.= "></span><a href='?USER_ID=".$arRow["data"]["PORTAL_USER_ID"].($filterByCode? '&CODE='.$filterByCode :'')."'>".$arUsers[$arRow["data"]["PORTAL_USER_ID"]]["FIO"]."</a>";
	}
	else
	{
		$userHtml = "<span class='tel-stat-user-img user-avatar'></span> &mdash;";
	}

	if (strlen($arRow["data"]["PHONE_NUMBER"]) <= 0)
	{
		$userHtml = GetMessage('TELEPHONY_BILLING');
	}
	else
	{
		$userHtml = '<span class="tel-stat-icon tel-stat-icon-'.$arRow["data"]["CALL_ICON"].'"></span><span style="white-space: nowrap">'.$userHtml.'</span>';
	}

	$arResult["ELEMENTS_ROWS"][$key]["columns"]["USER_NAME"] = $userHtml;
	$arResult["ELEMENTS_ROWS"][$key]["columns"]["LOG"] = $arRow["data"]["CALL_LOG"]? '<a href="'.$arRow["data"]["CALL_LOG"].'" target="_blank" class="tel-player-download"></a>':'-';
	$arResult["ELEMENTS_ROWS"][$key]["columns"]["CALL_FAILED_REASON"] = '<a href="?CODE='.$arRow["data"]["CALL_FAILED_CODE"].($filterByUser? '&USER_ID='.$filterByUser :'').'">'.$arRow["data"]["CALL_FAILED_REASON"].'</a>';

	if (strlen($arRow["data"]["CALL_RECORD_HREF"]) > 0)
	{
		ob_start();
		$APPLICATION->IncludeComponent(
			"bitrix:player",
			"",
			Array(
				"PLAYER_TYPE" => "flv",
				"CHECK_FILE" => "N",
				"USE_PLAYLIST" => "N",
				"PATH" => $arRow["data"]["CALL_RECORD_HREF"],
				"WIDTH" => 250,
				"HEIGHT" => 24,
				"PREVIEW" => false,
				"LOGO" => false,
				"FULLSCREEN" => "N",
				"SKIN_PATH" => "/bitrix/components/bitrix/player/mediaplayer/skins",
				"SKIN" => "",
				"CONTROLBAR" => "bottom",
				"WMODE" => "transparent",
				"WMODE_WMV" => "windowless",
				"HIDE_MENU" => "N",
				"SHOW_CONTROLS" => "N",
				"SHOW_STOP" => "Y",
				"SHOW_DIGITS" => "Y",
				"CONTROLS_BGCOLOR" => "FFFFFF",
				"CONTROLS_COLOR" => "000000",
				"CONTROLS_OVER_COLOR" => "000000",
				"SCREEN_COLOR" => "000000",
				"AUTOSTART" => "N",
				"REPEAT" => "N",
				"VOLUME" => "90",
				"DISPLAY_CLICK" => "play",
				"MUTE" => "N",
				"HIGH_QUALITY" => "N",
				"ADVANCED_MODE_SETTINGS" => "Y",
				"BUFFER_LENGTH" => "10",
				"DOWNLOAD_LINK" => false,
				"DOWNLOAD_LINK_TARGET" => "_self",
				"ALLOW_SWF" => "N",
				"ADDITIONAL_PARAMS" => array(
					'LOGO' => false,
					'NUM' => false,
					'HEIGHT_CORRECT' => false,
				),
				"PLAYER_ID" => "bitrix_vi_record_".$arRow["data"]["ID"]
			),
			false,
			Array("HIDE_ICONS" => "Y")
		);
		$recordHtml = '<div class="tel-player">'.ob_get_contents().'</div>';
		ob_end_clean();

		$recordHtml .= '<a href="'.$arRow["data"]["CALL_RECORD_HREF"].'" target="_blank" class="tel-player-download"></a>';

		$arResult["ELEMENTS_ROWS"][$key]["columns"]["RECORD"] = '<span style="white-space: nowrap">'.$recordHtml.'</span>';
	}
	else
	{
		$arResult["ELEMENTS_ROWS"][$key]["columns"]["RECORD"] = '-';
	}
}

$arResult["HEADERS"] = array(
	array("id"=>"USER_NAME", "name"=>GetMessage("TELEPHONY_HEADER_USER"), "default"=>true, "editable"=>false),
	array("id"=>"PORTAL_NUMBER", "name"=>GetMessage("TELEPHONY_HEADER_PORTAL_PHONE"), "default"=>false, "editable"=>false),
	array("id"=>"PHONE_NUMBER", "name"=>GetMessage("TELEPHONY_HEADER_PHONE"), "default"=>true, "editable"=>false),
	array("id"=>"INCOMING_TEXT", "name"=>GetMessage("TELEPHONY_HEADER_INCOMING"), "default"=>true, "editable"=>false),
	array("id"=>"CALL_DURATION_TEXT", "name"=>GetMessage("TELEPHONY_HEADER_DURATION"), "default"=>true, "editable"=>false),
	array("id"=>"CALL_START_DATE", "name"=>GetMessage("TELEPHONY_HEADER_START_DATE"), "default"=>true, "editable"=>false),
	array("id"=>"CALL_FAILED_REASON", "name"=>GetMessage("TELEPHONY_HEADER_STATUS"), "default"=>true, "editable"=>false),
	array("id"=>"COST_TEXT", "name"=>GetMessage("TELEPHONY_HEADER_COST"), "default"=>true, "editable"=>false),
	array("id"=>"RECORD", "name"=>GetMessage("TELEPHONY_HEADER_RECORD"), "default"=>true, "editable"=>false),
	array("id"=>"LOG", "name"=>GetMessage("TELEPHONY_HEADER_LOG"), "default"=>true, "editable"=>false),
);

$this->IncludeComponentTemplate();
?>