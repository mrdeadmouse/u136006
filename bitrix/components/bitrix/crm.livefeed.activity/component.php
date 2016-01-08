<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
	return;

if (intval($arParams["FIELDS"]["ENTITY_ID"]) > 0)
{
	$arActivity = $arParams["ACTIVITY"];

	if ($arActivity["TYPE_ID"] == CCrmActivityType::Task)
	{
		$APPLICATION->SetAdditionalCSS('/bitrix/js/tasks/css/tasks.css');

		if (
			intval($arActivity["ASSOCIATED_ENTITY_ID"]) > 0
			&& CModule::IncludeModule("tasks")
		)
		{
			$rsTask = CTasks::GetByID($arActivity["ASSOCIATED_ENTITY_ID"], false);
			if ($arTask = $rsTask->GetNext())
			{
				$path = str_replace(array("#user_id#", "#task_id#"), array($arTask["RESPONSIBLE_ID"], $arTask["ID"]), COption::GetOptionString("tasks", "paths_task_user_entry", "/company/personal/user/#user_id#/tasks/task/view/#task_id#/", $arTask["SITE_ID"]));
				$taskHtmlTitle = (!isset($arParams["PARAMS"]) || !isset($arParams["PARAMS"]["MOBILE"]) || $arParams["PARAMS"]["MOBILE"] != "Y" ? '<a href="'.$path.'" onclick="if (taskIFramePopup.isLeftClick(event)) {taskIFramePopup.view('.$arTask["ID"].'); return false;}">'.$arTask["TITLE"].'</a>' : $arTask["TITLE"]);

				$actorUserId = null;
				$actorUserName = '';
				$actorMaleSuffix = '';
				$eventTitlePhraseSuffix = '_DEFAULT';

				if (isset($arParams['NAME_TEMPLATE']))
					$nameTemplate = $arParams['NAME_TEMPLATE'];
				else
					$nameTemplate = CSite::GetNameFormat();

				if ($arActivity["COMPLETED"] == "N")
				{
					$eventTitlePhraseSuffix = '_CREATE';
					$actorUserId = $arTask["CREATED_BY"];
				}
				else
				{
					$eventTitlePhraseSuffix = '_COMPLETE';
					$actorUserId = $arTask["CHANGED_BY"];
				}

				if ($actorUserId)
				{
					$rsUser = CUser::GetList(
						$by = 'id',
						$order = 'asc',
						array('ID_EQUAL_EXACT' => (int) $actorUserId),
						array(
							'FIELDS' => array(
								'ID',
								'NAME',
								'LAST_NAME',
								'SECOND_NAME',
								'LOGIN',
								'PERSONAL_GENDER'
							)
						)
					);

					if ($arUser = $rsUser->fetch())
					{
						if (isset($arUser['PERSONAL_GENDER']))
						{
							switch ($arUser['PERSONAL_GENDER'])
							{
								case "F":
								case "M":
									$actorMaleSuffix = '_' . $arUser['PERSONAL_GENDER'];
								break;
							}
						}

						$actorUserName = CUser::FormatName($nameTemplate, $arUser);
					}
				}

				$eventTitleTemplate = GetMessage('C_CRM_LFA_TASKS_TITLE'
					. $eventTitlePhraseSuffix . $actorMaleSuffix);

				$eventTitle = str_replace(
					array('#USER_NAME#', '#TITLE#'),
					array($actorUserName, $taskHtmlTitle),
					$eventTitleTemplate
				);

				ob_start();
				$GLOBALS['APPLICATION']->IncludeComponent(
					"bitrix:tasks.task.livefeed", 
					(isset($arParams["PARAMS"]) && isset($arParams["PARAMS"]["MOBILE"]) && $arParams["PARAMS"]["MOBILE"] == "Y" ? 'mobile' : ''), 
					array(
						"MOBILE"        => (isset($arParams["PARAMS"]) && isset($arParams["PARAMS"]["MOBILE"]) && $arParams["PARAMS"]["MOBILE"] == "Y" ? "Y" : "N"),
						"TASK"          => $arTask,
						"MESSAGE"       => $eventTitle,
						"MESSAGE_24_1"  => $eventTitle,
						"MESSAGE_24_2"  => "",
						"CHANGES_24"    => "",
						"NAME_TEMPLATE"	=> $arParams["PARAMS"]["NAME_TEMPLATE"],
						"PATH_TO_USER"	=> $arParams["PARAMS"]["PATH_TO_USER"],
						'TYPE'          => ($arActivity["COMPLETED"] == "N" ? "create" : "status"),
						'task_tmp'      => $taskHtmlTitle,
						'taskHtmlTitle' => $taskHtmlTitle,
					), 
					null, 
					array("HIDE_ICONS" => "Y")
				);

				$html_message = ob_get_contents();
				ob_end_clean();

				echo htmlspecialcharsBack($html_message);
			}
		}

		return;
	}
	else
	{
		switch ($arParams["~ACTIVITY"]["TYPE_ID"])
		{
			case CCrmActivityType::Call:
			case CCrmActivityType::Meeting:
			case CCrmActivityType::Email:
				$arParams["~ACTIVITY"]["START_END_TIME"] = $arParams["~ACTIVITY"]["START_TIME"];
				break;
		}

		try
		{
			$oFormat = new CCrmLiveFeedComponent(array(
				"FIELDS" => $arParams["~FIELDS"], 
				"PARAMS" => $arParams["~PARAMS"],
				"ACTIVITY" => $arParams["~ACTIVITY"]
			));
		}
		catch (Exception $e) 
		{
			return false;
		}

		$aFields = $oFormat->formatFields();

		$arResult["FORMAT"] = "table";
		$arResult["FIELDS_FORMATTED"] = array();

		if (!empty($aFields))
		{
			foreach($aFields as $key => $arField)
			{
				$arResult["FIELDS_FORMATTED"][$key] = $oFormat->showField($arField);
			}
		}

		$arResult["DATE_WEEK_DAY"] = FormatDate("D", MakeTimeStamp($arParams["~ACTIVITY"]["START_END_TIME"]));
		$arResult["DATE_MONTH_DAY"] = FormatDate("j", MakeTimeStamp($arParams["~ACTIVITY"]["START_END_TIME"]));
		$arResult["IS_COMPLETED"] = ($arParams["~ACTIVITY"]["COMPLETED"] == "Y");

		if (!empty($arParams["ACTIVITY"]["DESCRIPTION"]))
		{
			switch ($arParams["ACTIVITY"]["DESCRIPTION_TYPE"])
			{
				case CCrmContentType::BBCode:
					$arResult["DESCRIPTION"] = CCrmLiveFeedComponent::ParseText(htmlspecialcharsback($arParams["ACTIVITY"]["DESCRIPTION"]), array(), array());
					break;
				case CCrmContentType::Html:
					$convertedText = htmlspecialcharsback($arParams["ACTIVITY"]["DESCRIPTION"]);
					$convertedText = preg_replace('/<br\s*\/*>/i', '#TMPBR#', $convertedText);
					$convertedText = preg_replace('/<\/p>/i', '#TMPBR#', $convertedText);
					$convertedText = CTextParser::clearAllTags($convertedText);
					$convertedText = str_replace('#TMPBR#', '<br>', $convertedText);
					$arResult["DESCRIPTION"] = $convertedText;
					break;
				default:
					$arResult["DESCRIPTION"] = $arParams["ACTIVITY"]["DESCRIPTION"];
			}
		}
		else
		{
			$arResult["DESCRIPTION"] = $arParams["ACTIVITY"]["DESCRIPTION"];
		}

		if (count($arActivity["COMMUNICATIONS"]) > 1)
		{
			$arResult["COMMUNICATION_MORE_CNT"] = count($arActivity["COMMUNICATIONS"]) - 1;
			$arResult["CLIENTS_FOR_JS"] = array();

			$i = 0;
			foreach($arActivity["COMMUNICATIONS"] as $arCommunication)
			{
				$i++;
				if ($i == 1)
				{
					continue;
				}

				$arTmp = array(
					"PHOTO" => false,
					"NAME" => false,
					"URL" => false,
					"COMPANY" => false,
					"COMM" => false
				);

				if (in_array($arCommunication["ENTITY_TYPE_ID"], array(CCrmOwnerType::Company, CCrmOwnerType::Contact, CCrmOwnerType::Lead)))
				{
					if ($arCommunication["ENTITY_TYPE_ID"] == CCrmOwnerType::Contact)
					{
						$dbRes = CCrmContact::GetListEx(array(), array('=ID' => $arCommunication["ENTITY_ID"], 'CHECK_PERMISSIONS' => 'N'), false, false, array('PHOTO'));
						if (
							($arRes = $dbRes->Fetch()) 
							&& (intval($arRes["PHOTO"]) > 0)
						)
						{
							$arFileTmp = CFile::ResizeImageGet(
								$arRes["PHOTO"],
								array('width' => 21, 'height' => 21),
								BX_RESIZE_IMAGE_EXACT,
								false
							);

							if(
								is_array($arFileTmp) 
								&& isset($arFileTmp["src"])
							)
							{
								$arTmp["PHOTO"] = $arFileTmp['src'];
							}
						}
					}
					elseif ($arCommunication["ENTITY_TYPE_ID"] == CCrmOwnerType::Company)
					{
						$dbRes = CCrmCompany::GetListEx(array(), array('=ID' => $arCommunication["ENTITY_ID"], 'CHECK_PERMISSIONS' => 'N'), false, false, array('LOGO'));
						if (
							($arRes = $dbRes->Fetch()) 
							&& (intval($arRes["LOGO"]) > 0)
						)
						{
							$arFileTmp = CFile::ResizeImageGet(
								$arRes["LOGO"],
								array('width' => 21, 'height' => 21),
								BX_RESIZE_IMAGE_EXACT,
								false
							);

							if(
								is_array($arFileTmp) 
								&& isset($arFileTmp["src"])
							)
							{
								$arTmp["PHOTO"] = $arFileTmp['src'];
							}
						}
					}
				}
					

				$arTmp["NAME"] = CCrmOwnerType::GetCaption($arCommunication["ENTITY_TYPE_ID"], $arCommunication["ENTITY_ID"], false);
				$arTmp["URL"] = CCrmOwnerType::GetShowURL($arCommunication["ENTITY_TYPE_ID"], $arCommunication["ENTITY_ID"], false);

				if (in_array($arCommunication["TYPE"], array('EMAIL', 'PHONE')))
				{
					$arTmp["COMM"] = array(
						"TYPE" => $arCommunication["TYPE"],
						"VALUE" => $arCommunication["VALUE"]
					);
				}

				if (is_array($arCommunication["ENTITY_SETTINGS"]) && isset($arCommunication["ENTITY_SETTINGS"]["COMPANY_TITLE"]))
				{
					$arTmp["COMPANY"] = $arCommunication["ENTITY_SETTINGS"]["COMPANY_TITLE"];
				}
				
				$arResult["CLIENTS_FOR_JS"][] = $arTmp;
			}
		}

		$arResult["STORAGE_ELEMENTS"] = array();
		$arResult["RECORDS"] = array();
		if (
			$arActivity["TYPE_ID"] == CCrmActivityType::Call
			&& !empty($arActivity["STORAGE_ELEMENT_IDS"])
		)
		{
			$arStorageElementID = unserialize($arActivity["STORAGE_ELEMENT_IDS"]);
			if (
				is_array($arStorageElementID)
				&& !empty($arStorageElementID)
			)
			{
				$arMediaExtensions = array("flv", "mp3", "mp4", "vp6", "aac");
				foreach($arStorageElementID as $elementID)
				{
					$info = Bitrix\Crm\Integration\StorageManager::getFileInfo($elementID, $arActivity["STORAGE_TYPE_ID"], false);
					if(is_array($info) && in_array(GetFileExtension(strtolower($info["NAME"])), $arMediaExtensions))
					{
						//Hacks for flv player
						$recordUrl = CCrmUrlUtil::ToAbsoluteUrl($info["VIEW_URL"]);
						if(substr($recordUrl, -1) !== "/")
						{
							$recordUrl .= "/";
						}
						$recordUrl .= !empty($info["NAME"]) ? $info["NAME"] : "dummy.flv";
						$arResult["RECORDS"][] = array(
							"URL" =>$recordUrl,
							"NAME" => $info["NAME"]
						);
					}
					$arResult["STORAGE_ELEMENTS"][] = $info;
				}
			}
		}
	}
}

$this->IncludeComponentTemplate();

return array(
	"CACHED_CSS_PATH" => $this->getTemplate()->GetFolder()."/style.css",
	"CACHED_JS_PATH" =>  $this->getTemplate()->GetFolder()."/script.js"
);
?>