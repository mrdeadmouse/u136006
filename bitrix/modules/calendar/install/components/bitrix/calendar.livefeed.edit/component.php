<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!isset($arParams["CALENDAR_TYPE"]))
	$arParams["CALENDAR_TYPE"] = 'user';

$curUserId = $USER->IsAuthorized() ? $USER->GetID() : '';
$id = 'cal_'.$this->randString(4);
if(!CModule::IncludeModule("calendar") || !class_exists("CCalendar"))
	return ShowError(GetMessage("EC_CALENDAR_MODULE_NOT_INSTALLED"));

// Userfields
global $USER_FIELD_MANAGER;

$arResult['ID'] = $id;
$arParams["FORM_ID"] = (!empty($arParams["FORM_ID"]) ? $arParams["FORM_ID"] : "blogPostForm");
$arParams["JS_OBJECT_NAME"] = 'oCalEditor'.$id;
$arParams['EDITOR_HEIGHT'] = 120;
$arParams['EVENT_ID'] = 0; // Only for new events
$arParams['OWNER_TYPE'] = 'user';
$arParams['CUR_USER'] = $USER->GetId();
$arResult['USER_FIELDS'] = $USER_FIELD_MANAGER->GetUserFields("CALENDAR_EVENT", $arParams['EVENT_ID'], LANGUAGE_ID);

// Webdaw upload file UF
$arParams["UPLOAD_WEBDAV_ELEMENT"] = $arResult['USER_FIELDS']['UF_WEBDAV_CAL_EVENT'];

$arParams['SECTIONS'] = CCalendar::GetSectionList(array(
	'CAL_TYPE' => $arParams['OWNER_TYPE'],
	'OWNER_ID' => $arParams['CUR_USER']
));

if (empty($arParams['SECTIONS']))
{
	$defCalendar = CCalendarSect::CreateDefault(array(
		'type' => $arParams['OWNER_TYPE'],
		'ownerId' => $arParams['CUR_USER']
	));
	$arParams['SECTIONS'][] = $defCalendar;
	CCalendar::SetCurUserMeetingSection($defCalendar['ID']);
}

$arParams['EVENT'] = CCalendarEvent::GetById($arParams['EVENT_ID']);

$arParams["DESTINATION"] = (is_array($arParams["DESTINATION"]) && IsModuleInstalled("socialnetwork") ? $arParams["DESTINATION"] : array());
$arParams["DESTINATION"] = (array_key_exists("VALUE", $arParams["DESTINATION"]) ? $arParams["DESTINATION"]["VALUE"] : $arParams["DESTINATION"]);

if (is_array($arParams["DESTINATION"]['USERS']))
{
	$users = array();
	foreach ($arParams["DESTINATION"]['USERS'] as $key => $entry)
	{
		if ($entry['isExtranet'] == 'N')
			$users[$key] = $entry;
	}
	$arParams["DESTINATION"]['USERS'] = $users;
}

// Empty destination for new events
if (!$arParams['EVENT_ID'])
	$arParams["DESTINATION"]["SELECTED"] = array();

$this->IncludeComponentTemplate();

?>