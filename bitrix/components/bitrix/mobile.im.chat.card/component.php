<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (isset($_REQUEST['AJAX_CALL']) && $_REQUEST['AJAX_CALL'] == 'Y')
	return;

if (intval($USER->GetID()) <= 0)
	return;

if (!CModule::IncludeModule('im'))
	return;

$chatId = intval($_GET['chat_id']);
if ($chatId <= 0)
	return;

$arChat = CIMChat::GetChatData(array(
	'ID' => $chatId,
	'USE_CACHE' => 'N'
));
$arResult['CHAT_ID'] = $chatId;
$arResult['CHAT'] = $arChat['chat'];
$arResult['USERS'] = Array();

if (!empty($arChat['userInChat'][$chatId]))
{
	$ar = CIMContactList::GetUserData(array(
		'ID' => $arChat['userInChat'][$chatId],
		'DEPARTMENT' => 'N',
		'USE_CACHE' => 'Y'
	));
	$arResult['USERS'] = $ar['users'];
}
if (!$arResult['USERS'][$USER->GetId()])
	return;

$GLOBALS["APPLICATION"]->SetPageProperty("Viewport", "user-scalable=no, initial-scale=1.0, maximum-scale=1.0, width=290");
$GLOBALS["APPLICATION"]->SetPageProperty("BodyClass", "chat-profile-page");

if (!(isset($arParams['TEMPLATE_HIDE']) && $arParams['TEMPLATE_HIDE'] == 'Y'))
	$this->IncludeComponentTemplate();

return $arResult;

?>