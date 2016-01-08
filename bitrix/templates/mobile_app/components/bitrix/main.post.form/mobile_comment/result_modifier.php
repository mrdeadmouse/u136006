<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$this->IncludeLangFile("result_modifier.php");

$arParams["COMMENT_TYPE"] = ($arParams["COMMENT_TYPE"] == "blog" ? "blog" : "log");
$arParams["COMMENT_ID"] = intval($arParams["COMMENT_ID"]);

if ($arParams["COMMENT_ID"] <= 0)
{
	$arResult["FatalError"] = GetMessage("MFP_COMMENT_NOT_FOUND");
}

if (!function_exists("__blogUFfileEditMobile"))
{
	function __blogUFfileEditMobile($arResult, $arParams)
	{
		$result = false;
		if (
			strpos($arParams['arUserField']['FIELD_NAME'], 'UF_BLOG_POST_DOC') === 0 
			|| strpos($arParams['arUserField']['FIELD_NAME'], 'UF_BLOG_COMMENT_DOC') === 0
		)
		{
			$componentParams = array(
				'INPUT_NAME' => $arParams["arUserField"]["FIELD_NAME"],
				'INPUT_NAME_UNSAVED' => 'FILE_NEW_TMP',
//				'INPUT_VALUE' => $arResult["VALUE"],
				'MAX_FILE_SIZE' => (intval($arParams['arUserField']['SETTINGS']['MAX_ALLOWED_SIZE']) > 0 ? $arParams['arUserField']['SETTINGS']['MAX_ALLOWED_SIZE'] : 5000000),
				'MULTIPLE' => $arParams['arUserField']['MULTIPLE'],
				'MODULE_ID' => 'uf',
				'ALLOW_UPLOAD' => 'I',
				'POST_ID' => $arParams['POST_ID']
			);

			$GLOBALS["APPLICATION"]->IncludeComponent('bitrix:mobile.file.upload', '', $componentParams, false, Array("HIDE_ICONS" => "Y"));
		}

		return true;
	}
}

if (!$arResult["FatalError"])
{
	if ($arParams["COMMENT_TYPE"] == "blog")
	{
		if (CModule::IncludeModule("blog"))
		{
			if ($arComment = CBlogComment::GetByID($arParams["COMMENT_ID"]))
			{
				$arResult["AUTHOR_ID"] = $arComment["AUTHOR_ID"];
				$arResult["POST_ID"] = $arComment["POST_ID"];
				$arResult["COMMENT_TEXT"] = htmlspecialcharsex($arComment["POST_TEXT"]);
			}
		}
	}
	elseif (CModule::IncludeModule("socialnetwork"))
	{
		$rsComment = CSocNetLogComments::GetList(
			array(),
			array(
				"ID" => $arParams["COMMENT_ID"]
			),
			false,
			false,
			array("ID", "LOG_ID", "USER_ID", "MESSAGE")
		);
		if ($arComment = $rsComment->Fetch())
		{
			$arResult["AUTHOR_ID"] = $arComment["USER_ID"];
			$arResult["POST_ID"] = $arComment["LOG_ID"];
			$arResult["COMMENT_TEXT"] = htmlspecialcharsex($arComment["MESSAGE"]);
		}
	}

	if (!$arComment)
	{
		$arResult["FatalError"] = GetMessage("MFP_COMMENT_NOT_FOUND");
	}
}

if (!$arResult["FatalError"])
{
	$arResult["diskEnabled"] = (\Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false) && CModule::includeModule('disk'));
	$bAuthor = ($arResult["AUTHOR_ID"] > 0 && $arResult["AUTHOR_ID"] == $GLOBALS["USER"]->GetId());	
	if (
		!$bAuthor 
		&& (
			$arParams["COMMENT_TYPE"] != "blog" 
			|| $GLOBALS["APPLICATION"]->GetGroupRight("blog") < "W"
		)
	)
	{
		$arResult["FatalError"] = GetMessage("MFP_COMMENT_NO_PERMISSIONS");
	}
}

?>