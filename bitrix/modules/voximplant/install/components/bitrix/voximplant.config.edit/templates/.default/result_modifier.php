<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$arResult["ITEM"] = htmlspecialcharsEx($arResult["ITEM"]);
if (CModule::IncludeModule("socialnetwork"))
{
	$arStructure = CSocNetLogDestination::GetStucture(array("LAZY_LOAD" => true));
	$arParams["DESTINATION"] = array(
		"LAST" => array("USERS" => CSocNetLogDestination::GetLastUser()),
		"DEPARTMENT" => $arStructure['department'],
		"SELECTED" => array("USERS" => array_keys($arResult["QUEUE"]))
	);
	if (CModule::IncludeModule('extranet') && !CExtranet::IsIntranetUser())
	{
		$arParams["DESTINATION"]['EXTRANET_USER'] = 'Y';
		$arParams["DESTINATION"]['USERS'] = CSocNetLogDestination::GetExtranetUser();
	}
	else
	{
		$arDestUser = array_keys($arResult["QUEUE"]);
		foreach ($arParams["DESTINATION"]['LAST']['USERS'] as $value)
			$arDestUser[] = str_replace('U', '', $value);
		$arParams["DESTINATION"]['EXTRANET_USER'] = 'N';
		$arParams["DESTINATION"]['USERS'] = CSocNetLogDestination::GetUsers(Array('id' => $arDestUser));
	}
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_REQUEST['mfi_mode']))
{
	if ($_REQUEST['mfi_mode'] == "upload")
	{
		function __MPF_ImageResizeHandler(&$arCustomFile, $params = array(), $result = array())
		{
			static $arParams = array();
			if (!empty($params))
				$arParams = $params;
			static $arResult = array();
			if (!empty($result))
				$arResult = $result;
			$fileIdForDelete = 0;
			$arFields = array();
			foreach(array("MELODY_WELCOME", "MELODY_WAIT", "MELODY_HOLD", "MELODY_VOICEMAIL") as $controlID => $inputName)
			{
				if ($_REQUEST["controlID"] == "voximplant".$controlID)
				{
					$fileIdForDelete = $arResult["ITEM"][$inputName];
					$arFields = array(
						$inputName => $arCustomFile["fileID"]
					);
					break;
				}
			}
			if (!empty($arFields))
			{
				$arFile = CFile::GetFileArray($arCustomFile['fileID']);
				$arCustomFile["fileURL"] = CHTTP::URN2URI($arFile["SRC"]);

				Bitrix\Voximplant\ConfigTable::update($arParams["ID"], $arFields);
				CFile::Delete($fileIdForDelete);
			}
		}
		__MPF_ImageResizeHandler(($res = null), $arParams);
		AddEventHandler('main',  "main.file.input.upload", '__MPF_ImageResizeHandler');
	}
	elseif ($_POST['mfi_mode'] == 'delete' && $_POST["fileID"] > 0)
	{
		$arFields = array();
		foreach(array("MELODY_WELCOME", "MELODY_WAIT", "MELODY_HOLD", "MELODY_VOICEMAIL") as $controlID => $inputName)
		{
			if ($_REQUEST["controlID"] == "voximplant".$controlID && $arResult["ITEM"][$inputName] == $_POST["fileID"])
			{
				$arFields = array(
					$inputName => 0
				);
				break;
			}
		}
		if (!empty($arFields))
		{
			Bitrix\Voximplant\ConfigTable::update($arParams["ID"], $arFields);
		}
	}
}
?>