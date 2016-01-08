<?
define("NO_KEEP_STATISTIC", true);
define("BX_STATISTIC_BUFFER_USED", false);
define("NO_LANG_FILES", true);
define("NOT_CHECK_PERMISSIONS", true);

$site_id = (isset($_REQUEST["site"]) && is_string($_REQUEST["site"])) ? trim($_REQUEST["site"]): "";
$site_id = substr(preg_replace("/[^a-z0-9_]/i", "", $site_id), 0, 2);

define("SITE_ID", $site_id);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/bx_root.php");

$action = (isset($_REQUEST["action"]) && is_string($_REQUEST["action"])) ? trim($_REQUEST["action"]): "";
$entity_type = (isset($_REQUEST["et"]) && is_string($_REQUEST["et"])) ? trim($_REQUEST["et"]): "";
$entity_id = isset($_REQUEST["eid"])? $_REQUEST["eid"]: "";
$cb_id = isset($_REQUEST["cb_id"])? $_REQUEST["cb_id"]: "";
$event_id = (isset($_REQUEST["evid"]) && is_string($_REQUEST["evid"])) ? trim($_REQUEST["evid"]): "";
$transport = (isset($_REQUEST["transport"]) && is_string($_REQUEST["transport"])) ? trim($_REQUEST["transport"]): "";
$entity_xml_id = (isset($_REQUEST["exmlid"]) && is_string($_REQUEST["exmlid"])) ? trim($_REQUEST["exmlid"]): "";

$lng = (isset($_REQUEST["lang"]) && is_string($_REQUEST["lang"])) ? trim($_REQUEST["lang"]): "";
$lng = substr(preg_replace("/[^a-z0-9_]/i", "", $lng), 0, 2);

$ls = isset($_REQUEST["ls"]) && !is_array($_REQUEST["ls"])? trim($_REQUEST["ls"]): "";
$ls_arr = isset($_REQUEST["ls_arr"])? $_REQUEST["ls_arr"]: "";

$st_id = (isset($_REQUEST["st_id"]) && is_string($_REQUEST["st_id"])) ? trim($_REQUEST["st_id"]): "";
$st_id = preg_replace("/[^a-z0-9_]/i", "", $st_id);

define("SITE_TEMPLATE_ID", $st_id);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$rsSite = CSite::GetByID($site_id);
if ($arSite = $rsSite->Fetch())
	define("LANGUAGE_ID", $arSite["LANGUAGE_ID"]);
else
	define("LANGUAGE_ID", "en");

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix/socialnetwork.log.entry/include.php");

__IncludeLang(dirname(__FILE__)."/lang/".$lng."/ajax.php");

if(CModule::IncludeModule("compression"))
	CCompress::Disable2048Spaces();

if(CModule::IncludeModule("socialnetwork"))
{
	$bCurrentUserIsAdmin = CSocNetUser::IsCurrentUserModuleAdmin();

	// write and close session to prevent lock;
	session_write_close();

	$arResult = array();

	if (in_array($action, array("add_comment", "get_comment", "get_comments", "get_more_destination")))
	{
		CSocNetTools::InitGlobalExtranetArrays();
	}

	if (!$GLOBALS["USER"]->IsAuthorized())
	{
		$arResult[0] = "*";
	}
	elseif (!check_bitrix_sessid())
	{
		$arResult[0] = "*";
	}
	elseif ($action == "add_comment")
	{
		$log_id = $_REQUEST["log_id"];

		$cuid = (isset($_REQUEST["cuid"]) && is_string($_REQUEST["cuid"])) ? trim($_REQUEST["cuid"]): "";
		$cuid = preg_replace("/[^a-z0-9]/i", "", $cuid);

		if ($arLog = CSocNetLog::GetByID($log_id))
		{
			if (
				strpos($arLog["ENTITY_TYPE"], "CRM") === 0
				&& 
				(
					!in_array($arLog["EVENT_ID"], array("crm_lead_message", "crm_deal_message", "crm_company_message", "crm_contact_message", "crm_activity_add"))
					|| (isset($_REQUEST["crm"]) && $_REQUEST["crm"] == "Y")
				)
				&& IsModuleInstalled("crm")
			)
			{
				$arListParams = array("IS_CRM" => "Y", "CHECK_CRM_RIGHTS" => "Y");
			}
			else
			{
				$arListParams = array("CHECK_RIGHTS" => "Y", "USE_SUBSCRIBE" => "N");
			}
		}
		else
		{
			$log_id = 0;
		}

		if (
			intval($log_id) > 0
			&& ($rsLog = CSocNetLog::GetList(array(), array("ID" => $log_id), false, false, array(), $arListParams))
			&& ($arLog = $rsLog->Fetch())
		)
		{
			$arCommentEvent = CSocNetLogTools::FindLogCommentEventByLogEventID($arLog["EVENT_ID"]);
			if ($arCommentEvent)
			{
				$feature = CSocNetLogTools::FindFeatureByEventID($arCommentEvent["EVENT_ID"]);

				if (
					array_key_exists("OPERATION_ADD", $arCommentEvent) 
					&& $arCommentEvent["OPERATION_ADD"] == "log_rights"
				)
				{
					$bCanAddComments = CSocNetLogRights::CheckForUser($log_id, $GLOBALS["USER"]->GetID());
				}
				elseif (
					$feature 
					&& array_key_exists("OPERATION_ADD", $arCommentEvent) 
					&& strlen($arCommentEvent["OPERATION_ADD"]) > 0
				)
				{
					$bCanAddComments = CSocNetFeaturesPerms::CanPerformOperation(
						$GLOBALS["USER"]->GetID(), 
						$arLog["ENTITY_TYPE"], 
						$arLog["ENTITY_ID"], 
						($feature == "microblog" ? "blog" : $feature), 
						$arCommentEvent["OPERATION_ADD"], 
						$bCurrentUserIsAdmin
					);
				}
				else
				{
					$bCanAddComments = true;
				}
				
				if ($bCanAddComments)
				{
					$arCommentParams = $_REQUEST["id"];
					if (is_array($arCommentParams) && isset($arCommentParams[1]) && intval($arCommentParams[1]) > 0)
					{
						$editCommentSourceID = intval($arCommentParams[1]);
					}
				}

				if ($bCanAddComments)
				{
					// add source object and get source_id, $source_url
					$arParams = array(
						"PATH_TO_SMILE" => $_REQUEST["p_smile"],
						"PATH_TO_USER_BLOG_POST" => $_REQUEST["p_ubp"],
						"PATH_TO_GROUP_BLOG_POST" => $_REQUEST["p_gbp"],
						"PATH_TO_USER_MICROBLOG_POST" => $_REQUEST["p_umbp"],
						"PATH_TO_GROUP_MICROBLOG_POST" => $_REQUEST["p_gmbp"],
						"BLOG_ALLOW_POST_CODE" => $_REQUEST["bapc"]
					);
//					$parser = new logTextParser(LANGUAGE_ID, $arParams["PATH_TO_SMILE"]);

					$comment_text = $_REQUEST["message"];
					CUtil::decodeURIComponent($comment_text);
					$comment_text = Trim($comment_text);
					if (strlen($comment_text) > 0)
					{
						$arSearchParams = array();

						if($arCommentEvent["EVENT_ID"] == "forum")
						{
							$arSearchParams["FORUM_ID"] = intval($_REQUEST["f_id"]);
							$arSearchParams["PATH_TO_GROUP_FORUM_MESSAGE"] = (
								$arLog["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP 
									? str_replace(
										"#GROUPS_PATH#", 
										COption::GetOptionString("socialnetwork", "workgroups_page", false, $site_id),
										$arLog["URL"]
									) 
									: ""
							);
							$arSearchParams["PATH_TO_USER_FORUM_MESSAGE"] = (
								$arLog["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_USER 
									? $arLog["URL"] 
									: ""
							);
						}
						elseif ($arCommentEvent["EVENT_ID"] == "files_comment")
						{
							if (strlen($arLog["PARAMS"]) > 0)
							{
								$files_forum_id = 0;
								$arLogParams = explode("&", htmlspecialcharsback($arLog["PARAMS"]));
								foreach($arLogParams as $prm)
								{
									list($k, $v) = explode("=", $prm);
									if ($k == "forum_id")
									{
										$files_forum_id = $v;
										break;
									}
								}
							}
							$arSearchParams["FILES_FORUM_ID"] = $files_forum_id;
							$arSearchParams["PATH_TO_GROUP_FILES_ELEMENT"] = (
								$arLog["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP 
									? str_replace(
										"#GROUPS_PATH#", 
										COption::GetOptionString("socialnetwork", "workgroups_page", false, $site_id),
										$arLog["URL"]
									) 
									: ""
							);
							$arSearchParams["PATH_TO_USER_FILES_ELEMENT"] = (
								$arLog["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_USER 
									? $arLog["URL"] 
									: ""
							);
						}
						elseif($arCommentEvent["EVENT_ID"] == "photo_comment")
						{
							if (strlen($arLog["PARAMS"]) > 0)
							{
								$photo_forum_id = 0;
								$arLogParams = unserialize(htmlspecialcharsback($arLog["PARAMS"]));
								if (
									is_array($arLogParams)
									&& array_key_exists("FORUM_ID", $arLogParams)
									&& intval($arLogParams["FORUM_ID"]) > 0
								)
								{
									$photo_forum_id = $arLogParams["FORUM_ID"];
								}
							}
							$arSearchParams["PHOTO_FORUM_ID"] = $photo_forum_id;
							$arSearchParams["PATH_TO_GROUP_PHOTO_ELEMENT"] = (
								$arLog["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP 
									? str_replace(
										"#GROUPS_PATH#",
										COption::GetOptionString("socialnetwork", "workgroups_page", false, $site_id),
										$arLog["URL"]
									) 
									: ""
							);
							$arSearchParams["PATH_TO_USER_PHOTO_ELEMENT"] = (
								$arLog["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_USER 
									? $arLog["URL"]
									: ""
							);
						}
						elseif($arCommentEvent["EVENT_ID"] == "wiki_comment")
						{
							$arSearchParams["PATH_TO_GROUP_WIKI_POST_COMMENT"] = (
								$arLog["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP 
									? COption::GetOptionString("socialnetwork", "workgroups_page", false, $site_id)."group/#group_id#/wiki/#wiki_name#/?MID=#message_id##message#message_id#"
									: ""
							);
						}
						elseif($arCommentEvent["EVENT_ID"] == "tasks_comment")
						{
							if (CModule::IncludeModule('tasks'))
							{
								$tasksForumId = intval(CTasksTools::getForumIdForIntranet());
								if ($tasksForumId > 0)
								{
									$arSearchParams["TASK_FORUM_ID"] = $tasksForumId;
									$arSearchParams["PATH_TO_GROUP_TASK_ELEMENT"] = (
										$arLog["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP 
											? COption::GetOptionString("socialnetwork", "workgroups_page", false, $site_id)."group/#group_id#/tasks/task/view/#task_id#/"
											: ""
									);
									$arSearchParams["PATH_TO_USER_TASK_ELEMENT"] = (
										$arLog["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_USER 
											? COption::GetOptionString("socialnetwork", "user_page", false, $site_id)."user/#user_id#/tasks/task/view/#task_id#/"
											: ""
									);
								}
							}
						}
						elseif($arCommentEvent["EVENT_ID"] == "calendar_comment")
						{
							$arSearchParams["PATH_TO_GROUP_CALENDAR_ELEMENT"] = (
								$arLog["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP 
									? COption::GetOptionString("socialnetwork", "workgroups_page", false, $site_id)."group/#group_id#/calendar/?EVENT_ID=#element_id#"
									: ""
							);
						}
						elseif($arCommentEvent["EVENT_ID"] == "lists_new_element_comment")
						{
							$arSearchParams["PATH_TO_WORKFLOW"] = "/services/processes/#list_id#/bp_log/#workflow_id#/";
						}

						global $bxSocNetSearch;
						if (
							!empty($arSearchParams)
							&& !is_object($bxSocNetSearch)
						)
						{
							$bxSocNetSearch = new CSocNetSearch(
								($arLog["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_USER ? $arLog["ENTITY_ID"] : false), 
								($arLog["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP ? $arLog["ENTITY_ID"] : false),
								$arSearchParams
							);
							AddEventHandler("search", "BeforeIndex", Array($bxSocNetSearch, "BeforeIndex"));
						}

						$arAllow = array(
							"HTML" => "N",
							"ANCHOR" => "Y",
							"LOG_ANCHOR" => "N",
							"BIU" => "N",
							"IMG" => "N",
							"LIST" => "N",
							"QUOTE" => "N",
							"CODE" => "N",
							"FONT" => "N",
							"UPLOAD" => $arForum["ALLOW_UPLOAD"],
							"NL2BR" => "N",
							"SMILES" => "N",
							"VIDEO" => "N",
							"USER" => "N",
							"ALIGN" => "N"
						);

						if ($editCommentSourceID > 0)
						{
							$arFields = array(
								"EVENT_ID" => $arCommentEvent["EVENT_ID"],
//								"MESSAGE" => $parser->convert($comment_text, array(), $arAllow),
								"MESSAGE" => $comment_text,
								"TEXT_MESSAGE" => $comment_text,
								"BLOG_ALLOW_POST_CODE" => $arParams["BLOG_ALLOW_POST_CODE"]
							);
						}
						else
						{
							$arFields = array(
								"ENTITY_TYPE" => $arLog["ENTITY_TYPE"],
								"ENTITY_ID" => $arLog["ENTITY_ID"],
								"EVENT_ID" => $arCommentEvent["EVENT_ID"],
								"=LOG_DATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
//								"MESSAGE" => $parser->convert($comment_text, array(), $arAllow),
								"MESSAGE" => $comment_text,
								"TEXT_MESSAGE" => $comment_text,
								"MODULE_ID" => false,
								"LOG_ID" => $arLog["ID"],
								"USER_ID" => $GLOBALS["USER"]->GetID(),
								"PATH_TO_USER_BLOG_POST" => $arParams["PATH_TO_USER_BLOG_POST"],
								"PATH_TO_GROUP_BLOG_POST" => $arParams["PATH_TO_GROUP_BLOG_POST"],
								"PATH_TO_USER_MICROBLOG_POST" => $arParams["PATH_TO_USER_MICROBLOG_POST"],
								"PATH_TO_GROUP_MICROBLOG_POST" => $arParams["PATH_TO_GROUP_MICROBLOG_POST"],
								"BLOG_ALLOW_POST_CODE" => $arParams["BLOG_ALLOW_POST_CODE"]
							);
						}

						$GLOBALS["USER_FIELD_MANAGER"]->EditFormAddFields("SONET_COMMENT", $arFields);

						if (
							array_key_exists("UF_SONET_COM_FILE", $arFields)
							&& !empty($arFields["UF_SONET_COM_FILE"])
						)
						{

							if (is_array($arFields["UF_SONET_COM_FILE"]))
							{
								foreach($arFields["UF_SONET_COM_FILE"] as $key => $fileID)
								{
									if (
										!$cuid
										|| !array_key_exists("MFI_UPLOADED_FILES_".$cuid, $_SESSION)
										|| !in_array($fileID, $_SESSION["MFI_UPLOADED_FILES_".$cuid])
									)
									{
										unset($arFields["UF_SONET_COM_FILE"][$key]);
									}
								}
							}
							else
							{
								if (
									!$cuid
									|| !array_key_exists("MFI_UPLOADED_FILES_".$cuid, $_SESSION)
									|| !in_array($arFields["UF_SONET_COM_FILE"], $_SESSION["MFI_UPLOADED_FILES_".$cuid])
								)
								{
									unset($arFields["UF_SONET_COM_FILE"]);
								}
							}
						}

						if ($editCommentSourceID > 0)
						{
							if (
								isset($arCommentEvent["ADD_CALLBACK"])
								&& is_callable($arCommentEvent["ADD_CALLBACK"])
							)
							{
								$rsRes = CSocNetLogComments::GetList(
									array(),
									array(
										"EVENT_ID" => $arCommentEvent["EVENT_ID"],
										"SOURCE_ID" => $editCommentSourceID
									),
									false,
									false,
									array("ID", "USER_ID", "LOG_ID")
								);
								if ($arRes = $rsRes->Fetch())
								{
									$update_id = $arRes["ID"];
									$update_log_id = $arRes["LOG_ID"];
									$update_user_id = $arRes["USER_ID"];
								}
							}

							if (intval($update_id) <= 0)
							{
								$rsRes = CSocNetLogComments::GetList(
									array(),
									array(
										"ID" => $editCommentSourceID
									),
									false,
									false,
									array("ID", "USER_ID", "LOG_ID")
								);
								if ($arRes = $rsRes->Fetch())
								{
									$update_id = $arRes["ID"];								
									$update_log_id = $arRes["LOG_ID"];
									$update_user_id = $arRes["USER_ID"];
								}
							}

							if (intval($update_id) > 0)
							{
								if (
									CSocNetUser::IsCurrentUserModuleAdmin()
									|| $update_user_id == $GLOBALS["USER"]->GetID()
								)
								{
									if (IsModuleInstalled("intranet"))
									{
										$bAllowUpdate = true;
									}
									else
									{
										$rsResCheck = CSocNetLogComments::GetList(
											array("ID" => "DESC"),
											array(
												"LOG_ID" => $update_log_id
											),
											false,
											false,
											array("ID")
										);
										if (
											($arResCheck = $rsResCheck->Fetch())
											&& ($arResCheck["ID"] == $update_id)
										)
										{
											$bAllowUpdate = true;
										}
									}
								}
							}

							if ($bAllowUpdate)
							{
								$commentIdres = CSocNetLogComments::Update($update_id, $arFields, true);
							}
							else
							{
								$commentIdres = array(
									"MESSAGE" => GetMessage("SONET_LOG_COMMENT_NO_PERMISSIONS_UPDATE")
								);
							}
						}
						else
						{
							$commentIdres = CSocNetLogComments::Add($arFields, true, false);
						}

						if (
							!is_array($commentIdres) 
							&& intval($commentIdres) > 0
						)
						{
							$db_events = GetModuleEvents("socialnetwork", "OnAfterSocNetLogEntryCommentAdd");
							while ($arEvent = $db_events->Fetch())
							{
								ExecuteModuleEventEx($arEvent, array($arLog));
							}

							$db_events = GetModuleEvents("socialnetwork", "OnBeforeSocNetLogCommentCounterIncrement");
							while ($arEvent = $db_events->Fetch())
							{
								if (ExecuteModuleEventEx($arEvent, array($arLog))===false)
								{
									$bSkipCounterIncrement = true;
									break;
								}
							}

							if (!$bSkipCounterIncrement)
							{
								CSocNetLog::CounterIncrement(
									$commentIdres, 
									false, 
									false, 
									"LC",
									CSocNetLogRights::CheckForUserAll($arLog["ID"])
								);
							}
							$arResult["commentID"] = $commentIdres;

							$arResult["hasEditCallback"] = (
								is_array($arCommentEvent)
								&& isset($arCommentEvent["UPDATE_CALLBACK"])
								&& (
									$arCommentEvent["UPDATE_CALLBACK"] == "NO_SOURCE"
									|| is_callable($arCommentEvent["UPDATE_CALLBACK"])
								)
									? "Y"
									: "N"
							);

							$arResult["hasDeleteCallback"] = (
								is_array($arCommentEvent)
								&& isset($arCommentEvent["DELETE_CALLBACK"])
								&& (
									$arCommentEvent["DELETE_CALLBACK"] == "NO_SOURCE"
									|| is_callable($arCommentEvent["DELETE_CALLBACK"])
								)
									? "Y"
									: "N"
							);

							if ($arComment = CSocNetLogComments::GetByID($arResult["commentID"]))
							{
								foreach (GetModuleEvents("socialnetwork", "OnAfterSonetLogEntryAddComment", true) as $arModuleEvent) // send notification
								{
									ExecuteModuleEventEx($arModuleEvent, array($arComment));
								}

								$arResult["arComment"] = $arComment;
								foreach($arResult["arComment"] as $key => $value)
								{
									if (strpos($key, "~") === 0)
									{
										unset($arResult["arComment"][$key]);
									}
								}

								$arResult["arComment"]["RATING_USER_HAS_VOTED"] = "N";

								$arResult["sourceID"] = $arComment["SOURCE_ID"];
								$arResult["timestamp"] = MakeTimeStamp(array_key_exists("LOG_DATE_FORMAT", $arComment) ? $arComment["LOG_DATE_FORMAT"] : $arComment["LOG_DATE"]);

								$arComment["UF"] = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("SONET_COMMENT", $arComment["ID"], LANGUAGE_ID);
								
								if (
									array_key_exists("UF_SONET_COM_DOC", $arComment["UF"])
									&& array_key_exists("VALUE", $arComment["UF"]["UF_SONET_COM_DOC"])
									&& is_array($arComment["UF"]["UF_SONET_COM_DOC"]["VALUE"])
									&& count($arComment["UF"]["UF_SONET_COM_DOC"]["VALUE"]) > 0
									&& $arCommentEvent["EVENT_ID"] != "tasks_comment"
								)
								{
									$arRights = array();
									$dbRight = CSocNetLogRights::GetList(array(), array("LOG_ID" => $arLog["ID"]));
									while ($arRight = $dbRight->Fetch())
										$arRights[] = $arRight["GROUP_CODE"];

									CSocNetLogTools::SetUFRights($arComment["UF"]["UF_SONET_COM_DOC"]["VALUE"], $arRights);
								}

								$dateFormated = FormatDate(
									$GLOBALS['DB']->DateFormatToPHP(FORMAT_DATE),
									$arResult["timestamp"]
								);

								$timeFormat = (isset($_REQUEST["dtf"]) ? $_REQUEST["dtf"] : CSite::GetTimeFormat());

								$timeFormated = FormatDateFromDB(
									(
										array_key_exists("LOG_DATE_FORMAT", $arComment) 
											? $arComment["LOG_DATE_FORMAT"] 
											: $arComment["LOG_DATE"]
									),
									(
										stripos($timeFormat, 'a') 
										|| (
											$timeFormat == 'FULL' 
											&& (strpos(FORMAT_DATETIME, 'T')!==false || strpos(FORMAT_DATETIME, 'TT')!==false)
										) !== false 
											? (strpos(FORMAT_DATETIME, 'TT')!==false ? 'H:MI TT' : 'H:MI T') 
											: 'HH:MI'
									)
								);

								if (intval($arComment["USER_ID"]) > 0)
								{
									$arParams = array(
										"PATH_TO_USER" => $_REQUEST["p_user"],
										"NAME_TEMPLATE" => $_REQUEST["nt"],
										"SHOW_LOGIN" => $_REQUEST["sl"],
										"AVATAR_SIZE" => $_REQUEST["as"],
										"PATH_TO_SMILE" => $_REQUEST["p_smile"]
									);

									$arUser = array(
										"ID" => $arComment["USER_ID"],
										"NAME" => $arComment["~CREATED_BY_NAME"],
										"LAST_NAME" => $arComment["~CREATED_BY_LAST_NAME"],
										"SECOND_NAME" => $arComment["~CREATED_BY_SECOND_NAME"],
										"LOGIN" => $arComment["~CREATED_BY_LOGIN"],
										"PERSONAL_PHOTO" => $arComment["~CREATED_BY_PERSONAL_PHOTO"],
										"PERSONAL_GENDER" => $arComment["~CREATED_BY_PERSONAL_GENDER"],
									);
									$bUseLogin = $arParams["SHOW_LOGIN"] != "N" ? true : false;
									$arCreatedBy = array(
										"FORMATTED" => CUser::FormatName($arParams["NAME_TEMPLATE"], $arUser, $bUseLogin),
										"URL" => CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER"], array("user_id" => $arComment["USER_ID"], "id" => $arComment["USER_ID"]))
									);

								}
								else
									$arCreatedBy = array("FORMATTED" => GetMessage("SONET_C73_CREATED_BY_ANONYMOUS"));

								$arTmpCommentEvent = array(
									"LOG_DATE" => $arComment["LOG_DATE"],
									"LOG_DATE_FORMAT" => $arComment["LOG_DATE_FORMAT"],
									"LOG_DATE_DAY" => ConvertTimeStamp(MakeTimeStamp($arComment["LOG_DATE"]), "SHORT"),
									"LOG_TIME_FORMAT" => $timeFormated,
									"MESSAGE" => $arComment["MESSAGE"],
									"MESSAGE_FORMAT" => $arComment["~MESSAGE"],
									"CREATED_BY" => $arCreatedBy,
									"AVATAR_SRC" => CSocNetLogTools::FormatEvent_CreateAvatar($arUser, $arParams, ""),
									"USER_ID" => $arComment["USER_ID"]
								);

								$arEventTmp = CSocNetLogTools::FindLogCommentEventByID($arComment["EVENT_ID"]);
								if (
									$arEventTmp
									&& array_key_exists("CLASS_FORMAT", $arEventTmp)
									&& array_key_exists("METHOD_FORMAT", $arEventTmp)
								)
								{
									$arFIELDS_FORMATTED = call_user_func(array($arEventTmp["CLASS_FORMAT"], $arEventTmp["METHOD_FORMAT"]), $arComment, $arParams);
									$arTmpCommentEvent["MESSAGE_FORMAT"] = htmlspecialcharsback($arFIELDS_FORMATTED["EVENT_FORMATTED"]["MESSAGE"]);
								}
								else
								{
									$arTmpCommentEvent["MESSAGE_FORMAT"] = $arComment["MESSAGE"];
								}

								if (IsModuleInstalled("mobile"))
								{
									if (
										$arEventTmp
										&& array_key_exists("CLASS_FORMAT", $arEventTmp)
										&& array_key_exists("METHOD_FORMAT", $arEventTmp)
									)
									{
										$arFIELDS_FORMATTED = call_user_func(
											array($arEventTmp["CLASS_FORMAT"], $arEventTmp["METHOD_FORMAT"]), 
											$arComment, 
											array_merge(
												$arParams, 
												array(
													"MOBILE" => "Y"
												)
											)
										);
										$strMessageMobile = htmlspecialcharsback($arFIELDS_FORMATTED["EVENT_FORMATTED"]["MESSAGE"]);
									}
									else
									{
										$strMessageMobile = $arComment["MESSAGE"];
									}
								}

								if (
									is_array($arComment["UF"]) 
									&& count($arComment["UF"]) > 0
								)
								{
									ob_start();

									$eventHandlerID = false;
									$eventHandlerID = AddEventHandler("main", "system.field.view.file", Array("CSocNetLogTools", "logUFfileShow"));

									foreach ($arComment["UF"] as $FIELD_NAME => $arUserField)
									{
										if(!empty($arUserField["VALUE"]))
										{
											$APPLICATION->IncludeComponent(
												"bitrix:system.field.view",
												$arUserField["USER_TYPE"]["USER_TYPE_ID"],
												array("arUserField" => $arUserField), 
												null, 
												array("HIDE_ICONS"=>"Y")
											);
										}
									}
									if ($eventHandlerID !== false && ( intval($eventHandlerID) > 0 ))
									{
										RemoveEventHandler('main', 'system.field.view.file', $eventHandlerID);
									}

									if (
										isset($arComment["UF"]["UF_SONET_COM_FILE"])
										&& !empty($arComment["UF"]["UF_SONET_COM_FILE"]["VALUE"])
									)
									{
										?><script>
											top.arLogComFiles<?=$arComment["ID"]?> = <?=CUtil::PhpToJSObject($arComment["UF"]["UF_SONET_COM_FILE"]["VALUE"])?>;
										</script><?
									}

									if (
										isset($arComment["UF"]["UF_SONET_COM_DOC"])
										&& !empty($arComment["UF"]["UF_SONET_COM_DOC"]["VALUE"])
									)
									{
										?><script>
											top.arLogComDocsType<?=$arComment["ID"]?> = '<?=$arComment["UF"]["UF_SONET_COM_DOC"]["USER_TYPE_ID"]?>';
											top.arLogComDocs<?=$arComment["ID"]?> = <?=CUtil::PhpToJSObject($arComment["UF"]["UF_SONET_COM_DOC"]["VALUE"])?>;
										</script><?
									}

									$arTmpCommentEvent["UF"] = ob_get_clean();
									
									if (IsModuleInstalled("mobile"))
									{
										ob_start();

										$eventHandlerID = false;
										$eventHandlerID = AddEventHandler("main", "system.field.view.file", Array("CSocNetLogTools", "logUFfileShow"));

										foreach ($arComment["UF"] as $FIELD_NAME => $arUserField)
										{
											if(!empty($arUserField["VALUE"]))
											{
												$APPLICATION->IncludeComponent(
													"bitrix:system.field.view",
													$arUserField["USER_TYPE"]["USER_TYPE_ID"],
													array(
														"arUserField" => $arUserField,
														"MOBILE" => "Y"
													), 
													null, 
													array("HIDE_ICONS"=>"Y")
												);
											}
										}
										if ($eventHandlerID !== false && ( intval($eventHandlerID) > 0 ))
										{
											RemoveEventHandler('main', 'system.field.view.file', $eventHandlerID);
										}

										$strUFMobile = ob_get_clean();
									}
								}
								$arTmpCommentEvent["RATING"] = "";
								if (!empty($arComment["RATING_TYPE_ID"]))
								{
									$arTmpCommentEvent["ALLOW_VOTE"] = CRatings::CheckAllowVote(
										array(
											"ENTITY_TYPE_ID" => $arComment["RATING_TYPE_ID"],
											"OWNER_ID" => $arComment["USER_ID"]
										)
									);
									if (!empty($_REQUEST["RATING_TYPE"]))
									{
										$arRating = array(
											"USER_VOTE" => 0,
											"USER_HAS_VOTED" => 'N',
											"TOTAL_VOTES" => 0,
											"TOTAL_POSITIVE_VOTES" => 0,
											"TOTAL_NEGATIVE_VOTES" => 0,
											"TOTAL_VALUE" => 0 );

										$arRatings = CRatings::GetRatingVoteResult($arComment["RATING_TYPE_ID"], $arComment["RATING_ENTITY_ID"]);
										if ($arRatings && $arRatings[$arComment["ID"]])
											$arRating = $arRatings[$arComment["ID"]];

										$arRatingParams = array_merge(Array(
											"ENTITY_TYPE_ID" => $arComment["RATING_TYPE_ID"],
											"ENTITY_ID" => $arComment["RATING_ENTITY_ID"],
											"OWNER_ID" => $arComment["USER_ID"],
											"PATH_TO_USER_PROFILE" =>$arParams["PATH_TO_USER"],
											"AJAX_MODE" => "N"), $arRating
										);

										ob_start();
										$GLOBALS["APPLICATION"]->IncludeComponent(
											"bitrix:rating.vote",
											$_REQUEST["RATING_TYPE"],
											$arRatingParams,
											null,
											array("HIDE_ICONS" => "Y")
										);
										$arTmpCommentEvent["RATING"] = ob_get_clean();

										if (IsModuleInstalled("mobile"))
										{
											ob_start();
											$GLOBALS["APPLICATION"]->IncludeComponent(
												"bitrix:rating.vote", 
												"mobile_comment_".$_REQUEST["RATING_TYPE"],
												$arRatingParams,
												null,
												array("HIDE_ICONS" => "Y")
											);
											$strRatingMobile = ob_get_clean();
										}
									}
								}
								$arResult["arCommentFormatted"] = $arTmpCommentEvent;

								if (
									$_REQUEST["pull"] == "Y" 
									&& CModule::IncludeModule("pull") 
									&& CPullOptions::GetNginxStatus()
								)
								{
									$entities_xml_id = array(
//										"blog_post" => array("BLOG", "BG"),
										"tasks" => array("TASK", "TK", "FORUM|COMMENT"),
										"forum" => array("FORUM", "FM", "FORUM|COMMENT"),
										"photo_photo" => array("PHOTO", "PH", "FORUM|COMMENT"),
										"sonet" => array("SOCNET", "SC", ""),
										"calendar" => array("EVENT", "EV", ""),
										"lists_new_element" => array("WF", "WF", "FORUM|COMMENT"),
									);

									if (
										$arLog["ENTITY_TYPE"] == "CRMACTIVITY"
										&& CModule::IncludeModule('crm')
										&& ($arActivity = CCrmActivity::GetByID($arLog["ENTITY_ID"], false))
										&& ($arActivity["TYPE_ID"] == CCrmActivityType::Task)
									)
									{
										$entity_xml_id = "TASK_".$arActivity["ASSOCIATED_ENTITY_ID"];
									}
									elseif (
										$arLog["ENTITY_TYPE"] == "WF"
										&& $arLog["SOURCE_ID"] > 0
										&& CModule::IncludeModule('bizproc')
										&& ($workflowId = \CBPStateService::getWorkflowByIntegerId($arLog["SOURCE_ID"]))
									)
									{
										$entity_xml_id = "WF_".$workflowId;
									}
									elseif (
										array_key_exists($arLog["EVENT_ID"], $entities_xml_id) 
										&& $arLog["SOURCE_ID"] > 0
									)
									{
										$entity_xml_id = $entities_xml_id[$arLog["EVENT_ID"]][0]."_".$arLog["SOURCE_ID"];
									}
									else
									{
										$entity_xml_id = strtoupper($arLog["EVENT_ID"])."_".$arLog["ID"];
									}

									$commentId = (!!$arComment["SOURCE_ID"] ? $arComment["SOURCE_ID"] : $arComment["ID"]);

									$arPullMessageParams = array(
										"ID" => $commentId,
										"ENTITY_XML_ID" => $entity_xml_id,
										"FULL_ID" => array($entity_xml_id, $commentId),
										"NEW" => "Y",
										"APPROVED" => "Y",
										"POST_TIMESTAMP" => ( $arResult["timestamp"] - CTimeZone::GetOffset() ),
										"POST_TIME" => $arTmpCommentEvent["LOG_TIME_FORMAT"],
										"POST_DATE" => $arTmpCommentEvent["LOG_TIME_FORMAT"],
										"~POST_MESSAGE_TEXT" => $arComment["~MESSAGE"],
										"POST_MESSAGE_TEXT" => $arTmpCommentEvent["MESSAGE_FORMAT"],
										"URL" => array(
											"LINK" => $arComment["URL"]
										),
										"AUTHOR" => array(
											"ID" => $arComment["USER_ID"],
											"NAME" => $arCreatedBy["FORMATTED"],
											"URL" => $arCreatedBy["URL"],
											"AVATAR" => $arTmpCommentEvent["AVATAR_SRC"]
										),
										"BEFORE_ACTIONS" => $arTmpCommentEvent["RATING"],
										"AFTER" => $arTmpCommentEvent["UF"],
										"ACTION" => "REPLY"
									);

									if (IsModuleInstalled("mobile"))
									{
										$arPullMessageParams["POST_MESSAGE_TEXT_MOBILE"] = $strMessageMobile;
										$arPullMessageParams["AFTER_MOBILE"] = $strUFMobile;
										$arPullMessageParams["BEFORE_ACTIONS_MOBILE"] = $strRatingMobile;
									}

									CPullWatch::AddToStack('UNICOMMENTS'.$entity_xml_id,
										Array(
											'module_id' => 'unicomments',
											'command' => 'comment',
											'params' => $arPullMessageParams
										)
									);
								}
							}
						}
						elseif (
							is_array($commentIdres) 
							&& array_key_exists("MESSAGE", $commentIdres) 
							&& strlen($commentIdres["MESSAGE"]) > 0
						)
						{
							$arResult["strMessage"] = $commentIdres["MESSAGE"];
							$arResult["commentText"] = $comment_text;
						}
					}
					else
						$arResult["strMessage"] = GetMessage("SONET_LOG_COMMENT_EMPTY");
				}
				else
					$arResult["strMessage"] = GetMessage("SONET_LOG_COMMENT_NO_PERMISSIONS");
			}
		}
	}
	elseif ($action == "get_comment")
	{
		$comment_id = $_REQUEST["cid"];

		if ($arComment = CSocNetLogComments::GetByID($comment_id))
		{
			if (
				strpos($arComment["ENTITY_TYPE"], "CRM") === 0
				&& IsModuleInstalled("crm")
			)
			{
				$arListParams = array("IS_CRM" => "Y", "CHECK_CRM_RIGHTS" => "Y");
			}
			else
			{
				$arListParams = array("CHECK_RIGHTS" => "Y", "USE_SUBSCRIBE" => "N");
			}

			if (
				intval($arComment["LOG_ID"]) > 0
				&& ($rsLog = CSocNetLog::GetList(array(), array("ID" => $arComment["LOG_ID"]), false, false, array("ID", "EVENT_ID"), $arListParams))
				&& ($arLog = $rsLog->Fetch())
			)
			{
				$arResult["arComment"] = $arComment;

				$dateFormated = FormatDate(
					$GLOBALS['DB']->DateFormatToPHP(FORMAT_DATE),
					MakeTimeStamp(array_key_exists("LOG_DATE_FORMAT", $arComment) ? $arComment["LOG_DATE_FORMAT"] : $arComment["LOG_DATE"])
				);

				$timeFormat = (isset($_REQUEST["dtf"]) ? $_REQUEST["dtf"] : CSite::GetTimeFormat());

				$timeFormated = FormatDateFromDB(
					(
						array_key_exists("LOG_DATE_FORMAT", $arComment) 
							? $arComment["LOG_DATE_FORMAT"] 
							: $arComment["LOG_DATE"]
					),
					(
						stripos($timeFormat, 'a') 
						|| (
							$timeFormat == 'FULL' 
							&& (strpos(FORMAT_DATETIME, 'T')!==false || strpos(FORMAT_DATETIME, 'TT')!==false)
						) !== false 
							? (strpos(FORMAT_DATETIME, 'TT')!==false ? 'H:MI TT' : 'H:MI T')
							: 'HH:MI'
					)
				);

				if (intval($arComment["USER_ID"]) > 0)
				{
					$arParams = array(
						"PATH_TO_USER" => $_REQUEST["p_user"],
						"NAME_TEMPLATE" => $_REQUEST["nt"],
						"SHOW_LOGIN" => $_REQUEST["sl"],
						"AVATAR_SIZE" => $_REQUEST["as"],
						"PATH_TO_SMILE" => $_REQUEST["p_smile"]
					);

					$arUser = array(
						"ID" => $arComment["USER_ID"],
						"NAME" => $arComment["~CREATED_BY_NAME"],
						"LAST_NAME" => $arComment["~CREATED_BY_LAST_NAME"],
						"SECOND_NAME" => $arComment["~CREATED_BY_SECOND_NAME"],
						"LOGIN" => $arComment["~CREATED_BY_LOGIN"],
						"PERSONAL_PHOTO" => $arComment["~CREATED_BY_PERSONAL_PHOTO"],
						"PERSONAL_GENDER" => $arComment["~CREATED_BY_PERSONAL_GENDER"],
					);
					$bUseLogin = $arParams["SHOW_LOGIN"] != "N" ? true : false;
					$arCreatedBy = array(
						"FORMATTED" => CUser::FormatName($arParams["NAME_TEMPLATE"], $arUser, $bUseLogin),
						"URL" => CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER"], array("user_id" => $arComment["USER_ID"], "id" => $arComment["USER_ID"]))
					);

				}
				else
					$arCreatedBy = array("FORMATTED" => GetMessage("SONET_C73_CREATED_BY_ANONYMOUS"));

				$arTmpCommentEvent = array(
					"LOG_DATE" => $arComment["LOG_DATE"],
					"LOG_DATE_FORMAT" => $arComment["LOG_DATE_FORMAT"],
					"LOG_DATE_DAY" => ConvertTimeStamp(MakeTimeStamp($arComment["LOG_DATE"]), "SHORT"),
					"LOG_TIME_FORMAT" => $timeFormated,
					"MESSAGE" => $arComment["MESSAGE"],
					"MESSAGE_FORMAT" => $arComment["~MESSAGE"],
					"CREATED_BY" => $arCreatedBy,
					"AVATAR_SRC" => CSocNetLogTools::FormatEvent_CreateAvatar($arUser, $arParams, ""),
					"USER_ID" => $arComment["USER_ID"]
				);

				$arEventTmp = CSocNetLogTools::FindLogCommentEventByID($arComment["EVENT_ID"]);
				if (
					$arEventTmp
					&& array_key_exists("CLASS_FORMAT", $arEventTmp)
					&& array_key_exists("METHOD_FORMAT", $arEventTmp)
				)
				{
					$arFIELDS_FORMATTED = call_user_func(array($arEventTmp["CLASS_FORMAT"], $arEventTmp["METHOD_FORMAT"]), $arComment, $arParams);
					$arTmpCommentEvent["MESSAGE_FORMAT"] = htmlspecialcharsback($arFIELDS_FORMATTED["EVENT_FORMATTED"]["MESSAGE"]);
				}

				$arResult["arCommentFormatted"] = $arTmpCommentEvent;
			}
		}
	}
	elseif ($action == "get_comments")
	{
		$arResult["arComments"] = array();

		$log_tmp_id = $_REQUEST["logid"];
		$log_entity_type = $entity_type;

		$arListParams = (strpos($log_entity_type, "CRM") === 0 && IsModuleInstalled("crm") ? array("IS_CRM" => "Y", "CHECK_CRM_RIGHTS" => "Y") : array("CHECK_RIGHTS" => "Y", "USE_SUBSCRIBE" => "N"));

		if (
			intval($log_tmp_id) > 0
			&& ($rsLog = CSocNetLog::GetList(array(), array("ID" => $log_tmp_id), false, false, array("ID", "EVENT_ID"), $arListParams))
			&& ($arLog = $rsLog->Fetch())
		)
		{
			$arParams = array(
				"PATH_TO_USER" => $_REQUEST["p_user"],
				"PATH_TO_GROUP" => $_REQUEST["p_group"],
				"PATH_TO_CONPANY_DEPARTMENT" => $_REQUEST["p_dep"],
				"PATH_TO_LOG_ENTRY" => $_REQUEST["p_le"],
				"NAME_TEMPLATE" => $_REQUEST["nt"],
				"NAME_TEMPLATE_WO_NOBR" => str_replace(array("#NOBR#", "#/NOBR#"), array("", ""), $_REQUEST["nt"]),
				"SHOW_LOGIN" => $_REQUEST["sl"],
				"DATE_TIME_FORMAT" => (isset($_REQUEST["dtf"]) ? $_REQUEST["dtf"] : CSite::GetTimeFormat()),
				"AVATAR_SIZE" => $_REQUEST["as"],
				"PATH_TO_SMILE" => $_REQUEST["p_smile"]
			);

			$cache_time = 31536000;

			$cache = new CPHPCache;

			$arCacheID = array();
			$arKeys = array(
				"AVATAR_SIZE",
				"NAME_TEMPLATE",
				"NAME_TEMPLATE_WO_NOBR",
				"SHOW_LOGIN",
				"DATE_TIME_FORMAT",
				"PATH_TO_USER",
				"PATH_TO_GROUP",
				"PATH_TO_CONPANY_DEPARTMENT"
			);
			foreach($arKeys as $param_key)
			{
				$arCacheID[$param_key] = (array_key_exists($param_key, $arParams) ? $arParams[$param_key] : false) ;
			}
			$cache_id = "log_comments_".$log_tmp_id."_".md5(serialize($arCacheID))."_".SITE_TEMPLATE_ID."_".SITE_ID."_".LANGUAGE_ID."_".FORMAT_DATETIME."_".CTimeZone::GetOffset();
			$cache_path = "/sonet/log/".intval(intval($log_tmp_id) / 1000)."/".$log_tmp_id."/comments/";

			if (
				is_object($cache)
				&& $cache->InitCache($cache_time, $cache_id, $cache_path)
			)
			{
				$arCacheVars = $cache->GetVars();
				$arResult["arComments"] = $arCacheVars["COMMENTS_FULL_LIST"];
			}
			else
			{
				if (is_object($cache))
				{
					$cache->StartDataCache($cache_time, $cache_id, $cache_path);
				}

				if (defined("BX_COMP_MANAGED_CACHE"))
				{
					$GLOBALS["CACHE_MANAGER"]->StartTagCache($cache_path);
				}

				$arFilter = array("LOG_ID" => $log_tmp_id);
				$arListParams = array("USE_SUBSCRIBE" => "N");

				$arSelect = array(
					"ID", "LOG_ID", "SOURCE_ID", "ENTITY_TYPE", "ENTITY_ID", "USER_ID", "EVENT_ID", "LOG_DATE", "MESSAGE", "TEXT_MESSAGE", "URL", "MODULE_ID",
					"GROUP_NAME", "GROUP_OWNER_ID", "GROUP_VISIBLE", "GROUP_OPENED", "GROUP_IMAGE_ID",
					"USER_NAME", "USER_LAST_NAME", "USER_SECOND_NAME", "USER_LOGIN", "USER_PERSONAL_PHOTO", "USER_PERSONAL_GENDER",
					"CREATED_BY_NAME", "CREATED_BY_LAST_NAME", "CREATED_BY_SECOND_NAME", "CREATED_BY_LOGIN", "CREATED_BY_PERSONAL_PHOTO", "CREATED_BY_PERSONAL_GENDER",
					"LOG_SITE_ID", "LOG_SOURCE_ID",
					"RATING_TYPE_ID", "RATING_ENTITY_ID",
					"UF_*"
				);

				$arUFMeta = __SLGetUFMeta();

				$dbComments = CSocNetLogComments::GetList(
					array("LOG_DATE" => "ASC"),
					$arFilter,
					false,
					false,
					$arSelect,
					$arListParams
				);

				while($arComments = $dbComments->GetNext())
				{
					if (defined("BX_COMP_MANAGED_CACHE"))
					{
						$GLOBALS["CACHE_MANAGER"]->RegisterTag("USER_NAME_".intval($arComments["USER_ID"]));
					}

					$arComments["UF"] = $arUFMeta;
					foreach($arUFMeta as $field_name => $arUF)
					{
						if (array_key_exists($field_name, $arComments))
						{
							$arComments["UF"][$field_name]["VALUE"] = $arComments[$field_name];
							$arComments["UF"][$field_name]["ENTITY_VALUE_ID"] = $arComments["ID"];
						}
					}

					$arResult["arComments"][$arComments["ID"]] = __SLEGetLogCommentRecord($arComments, $arParams, false);
				}

				if (is_object($cache))
				{
					$arCacheData = Array(
						"COMMENTS_FULL_LIST" => $arResult["arComments"]
					);
					$cache->EndDataCache($arCacheData);
					if(defined("BX_COMP_MANAGED_CACHE"))
					{
						$GLOBALS["CACHE_MANAGER"]->EndTagCache();
					}
				}
			}

			$cntOriginal = count($arResult["arComments"]);

			if (
				intval($_REQUEST["commentID"]) > 0
				|| intval($_REQUEST["commentTS"]) > 0
			)
			{
				foreach($arResult["arComments"] as $key => $res)
				{
					if (
						(
							intval($_REQUEST["commentTS"]) > 0
							&& $res["LOG_DATE_TS"] >= $_REQUEST["commentTS"]
						)
						|| (
							intval($_REQUEST["commentTS"]) <= 0
							&& $key >= $_REQUEST["commentID"]
						)
					)
					{
						unset($arResult["arComments"][$key]);
					}
				}

				if (
					isset($_REQUEST["PAGEN_1"]) 
					&& intval($_REQUEST["PAGEN_1"]) > 0
				)
				{
					$cntHidden = count($arResult["arComments"]) - (intval($_REQUEST["PAGEN_1"]) * 20);
					if ($cntHidden > 0)
					{
						$arResult["navigationNextPageNum"] = intval($_REQUEST["PAGEN_1"]) + 1;
						$arResult["navigationCounter"] = $cntHidden;
					}

					$arResult["arComments"] = array_reverse($arResult["arComments"], true);

					$rsNavResult = new CDBResult;
					$rsNavResult->InitFromArray($arResult["arComments"]);
					$rsNavResult->NavStart(20, false);

					$arTmp = $arResult["arComments"];
					$arResult["arComments"] = array();

					while($arNavResult = $rsNavResult->Fetch())
					{
						$arResult["arComments"][$arNavResult["EVENT"]["ID"]] = $arNavResult;
					}

					$arResult["arComments"] = array_reverse($arResult["arComments"], true);
				}
			}

			$rating_entity_type = null;

			$arTmp = $arResult["arComments"];
			$arResult["arComments"] = array();

			foreach ($arTmp as $key => $arCommentTmp)
			{
				if ($rating_entity_type === null)
				{
					$rating_entity_type = (!!$arCommentTmp["EVENT"]["RATING_TYPE_ID"] ? $arCommentTmp["EVENT"]["RATING_TYPE_ID"] : false);
				}

				$arCommentID[] = $arCommentTmp["EVENT"]["RATING_ENTITY_ID"];
				$arResult["arComments"][] = $arCommentTmp;
			}
			$arRatingComments = array();
			if(
				!empty($arCommentID)
				&& strlen($rating_entity_type) > 0
			)
			{
				$arRatingComments = CRatings::GetRatingVoteResult($rating_entity_type, $arCommentID);
			}

			if (!IsModuleInstalled("intranet"))
			{
				$rsResCheck = CSocNetLogComments::GetList(
					array("ID" => "DESC"),
					array(
						"LOG_ID" => $log_tmp_id,
					),
					false,
					false,
					array("ID")
				);
				if ($arResCheck = $rsResCheck->Fetch())
				{
					$last_comment_id = $arResCheck["ID"];
				}
			}

			$arCommentEvent = CSocNetLogTools::FindLogCommentEventByLogEventID($arLog["EVENT_ID"]);

			$bHasEditCallback = (
				is_array($arCommentEvent)
				&& isset($arCommentEvent["UPDATE_CALLBACK"])
				&& (
					$arCommentEvent["UPDATE_CALLBACK"] == "NO_SOURCE"
					|| is_callable($arCommentEvent["UPDATE_CALLBACK"])
				)
			);

			$bHasDeleteCallback = (
				is_array($arCommentEvent)
				&& isset($arCommentEvent["DELETE_CALLBACK"])
				&& (
					$arCommentEvent["DELETE_CALLBACK"] == "NO_SOURCE"
					|| is_callable($arCommentEvent["DELETE_CALLBACK"])
				)
			);

			foreach($arResult["arComments"] as $key => $arCommentTmp)
			{
				if (
					is_array($arCommentTmp["UF"]) 
					&& count($arCommentTmp["UF"]) > 0
				)
				{
					ob_start();

					$eventHandlerID = false;
					$eventHandlerID = AddEventHandler("main", "system.field.view.file", Array("CSocNetLogTools", "logUFfileShow"));

					foreach ($arCommentTmp["UF"] as $FIELD_NAME => $arUserField)
					{
						if(!empty($arUserField["VALUE"]))
						{
							$APPLICATION->IncludeComponent(
								"bitrix:system.field.view",
								$arUserField["USER_TYPE"]["USER_TYPE_ID"],
								array("arUserField" => $arUserField), 
								null, 
								array("HIDE_ICONS"=>"Y")
							);
						}
					}
					if (
						$eventHandlerID !== false 
						&& intval($eventHandlerID) > 0
					)
					{
						RemoveEventHandler('main', 'system.field.view.file', $eventHandlerID);
					}

					if (
						isset($arCommentTmp["UF"]["UF_SONET_COM_FILE"])
						&& !empty($arCommentTmp["UF"]["UF_SONET_COM_FILE"]["VALUE"])
					)
					{
						?><script>
							top.arLogComFiles<?=$arCommentTmp["EVENT"]["ID"]?> = <?=CUtil::PhpToJSObject($arCommentTmp["UF"]["UF_SONET_COM_FILE"]["VALUE"])?>;
						</script><?
					}

					if (
						isset($arCommentTmp["UF"]["UF_SONET_COM_DOC"])
						&& !empty($arCommentTmp["UF"]["UF_SONET_COM_DOC"]["VALUE"])
					)
					{
						?><script>
							top.arLogComDocsType<?=$arCommentTmp["EVENT"]["ID"]?> = '<?=$arCommentTmp["UF"]["UF_SONET_COM_DOC"]["USER_TYPE_ID"]?>';
							top.arLogComDocs<?=$arCommentTmp["EVENT"]["ID"]?> = <?=CUtil::PhpToJSObject($arCommentTmp["UF"]["UF_SONET_COM_DOC"]["VALUE"])?>;
						</script><?
					}

					$arResult["arComments"][$key]["EVENT_FORMATTED"]["UF"] = ob_get_clean();
				}

				if (array_key_exists($arCommentTmp["EVENT"]["RATING_ENTITY_ID"], $arRatingComments))
				{
					$arResult["arComments"][$key]["EVENT"]["RATING_USER_VOTE_VALUE"] = (isset($arRatingComments[$arCommentTmp["EVENT"]["RATING_ENTITY_ID"]]["USER_VOTE"]) ? $arRatingComments[$arCommentTmp["EVENT"]["RATING_ENTITY_ID"]]["USER_VOTE"] : 0);
					$arResult["arComments"][$key]["EVENT"]["RATING_USER_HAS_VOTED"] = (isset($arRatingComments[$arCommentTmp["EVENT"]["RATING_ENTITY_ID"]]["USER_HAS_VOTED"]) ? $arRatingComments[$arCommentTmp["EVENT"]["RATING_ENTITY_ID"]]["USER_HAS_VOTED"] : "N");
					$arResult["arComments"][$key]["EVENT"]["RATING_TOTAL_POSITIVE_VOTES"] = (isset($arRatingComments[$arCommentTmp["EVENT"]["RATING_ENTITY_ID"]]["TOTAL_POSITIVE_VOTES"]) ? $arRatingComments[$arCommentTmp["EVENT"]["RATING_ENTITY_ID"]]["TOTAL_POSITIVE_VOTES"] : 0);
					$arResult["arComments"][$key]["EVENT"]["RATING_TOTAL_NEGATIVE_VOTES"] = (isset($arRatingComments[$arCommentTmp["EVENT"]["RATING_ENTITY_ID"]]["TOTAL_NEGATIVE_VOTES"]) ? $arRatingComments[$arCommentTmp["EVENT"]["RATING_ENTITY_ID"]]["TOTAL_NEGATIVE_VOTES"] : 0);
					$arResult["arComments"][$key]["EVENT"]["RATING_TOTAL_VALUE"] = (isset($arRatingComments[$arCommentTmp["EVENT"]["RATING_ENTITY_ID"]]["TOTAL_VALUE"]) ? $arRatingComments[$arCommentTmp["EVENT"]["RATING_ENTITY_ID"]]["TOTAL_VALUE"] : 0);
					$arResult["arComments"][$key]["EVENT"]["RATING_TOTAL_VOTES"] = (isset($arRatingComments[$arCommentTmp["EVENT"]["RATING_ENTITY_ID"]]["TOTAL_VOTES"]) ? $arRatingComments[$arCommentTmp["EVENT"]["RATING_ENTITY_ID"]]["TOTAL_VOTES"] : 0);
				}
				else
				{
					$arResult["arComments"][$key]["EVENT"]["RATING_USER_VOTE_VALUE"] = 0;
					$arResult["arComments"][$key]["EVENT"]["RATING_USER_HAS_VOTED"] = "N";
					$arResult["arComments"][$key]["EVENT"]["RATING_TOTAL_POSITIVE_VOTES"] = 0;
					$arResult["arComments"][$key]["EVENT"]["RATING_TOTAL_NEGATIVE_VOTES"] = 0;
					$arResult["arComments"][$key]["EVENT"]["RATING_TOTAL_VALUE"] = 0;
					$arResult["arComments"][$key]["EVENT"]["RATING_TOTAL_VOTES"] = 0;
				}
				
				if (strlen($rating_entity_type) > 0)
				{
					$arResult["arComments"][$key]["EVENT_FORMATTED"]["ALLOW_VOTE"] = CRatings::CheckAllowVote(
						array(
							"ENTITY_TYPE_ID" => $rating_entity_type,
							"OWNER_ID" => $arResult["arComments"][$key]["EVENT"]["USER_ID"]
						)
					);
				}

				$canEdit = (
					CSocNetUser::IsCurrentUserModuleAdmin()
					|| (
						$arResult["arComments"][$key]["EVENT"]["USER_ID"] == $GLOBALS["USER"]->GetID()
						&& (
							IsModuleInstalled("intranet")
							|| ($arResult["arComments"][$key]["EVENT"]["ID"] == $last_comment_id)
						)
					)
					? "Y"
					: "N"
				);

				$arResult["arComments"][$key]["PANELS"] = array(
					"EDIT" => ($canEdit == "Y" && $bHasEditCallback ? "Y" : "N"),
					"MODERATE" => "N",
					"DELETE" => ($canEdit == "Y" && $bHasDeleteCallback ? "Y" : "N")
				);

				$arResult["arComments"][$key]["URL"] = (
					isset($arResult["arComments"][$key]["EVENT"]["URL"]) 
					&& strlen($arResult["arComments"][$key]["EVENT"]["URL"]) > 0 
						? $arResult["arComments"][$key]["EVENT"]["URL"] 
						: (
							isset($arParams["PATH_TO_LOG_ENTRY"]) && strlen($arParams["PATH_TO_LOG_ENTRY"]) > 0 
								? CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_LOG_ENTRY"], array("log_id" => $log_tmp_id))."?commentId=".$arResult["arComments"][$key]["EVENT"]["ID"] 
								: ""
						)
				);
				$arResult["arComments"][$key]["URL_EDIT"] = "__logEditComment('".$entity_xml_id."', '".$arResult["arComments"][$key]["EVENT"]["ID"]."', '".$log_tmp_id."');";
			}
		}
	}
	elseif (
		$action == "change_favorites" 
		&& $GLOBALS["USER"]->IsAuthorized()
	)
	{
		$log_id = intval($_REQUEST["log_id"]);
		if ($arLog = CSocNetLog::GetByID($log_id))
		{
			$strRes = CSocNetLogFavorites::Change($GLOBALS["USER"]->GetID(), $log_id);

			if ($strRes)
			{
				if ($strRes == "Y")
					CSocNetLogFollow::Set(
						$GLOBALS["USER"]->GetID(), 
						"L".$log_id, 
						"Y",
						$arLog["LOG_UPDATE"]
					);
				$arResult["bResult"] = $strRes;
			}
			else
			{
				if($e = $GLOBALS["APPLICATION"]->GetException())
					$arResult["strMessage"] = $e->GetString();
				else
					$arResult["strMessage"] = GetMessage("SONET_LOG_FAVORITES_CANNOT_CHANGE");
				$arResult["bResult"] = "E";
			}
		}
		else
		{
			$arResult["strMessage"] = GetMessage("SONET_LOG_FAVORITES_INCORRECT_LOG_ID");
			$arResult["bResult"] = "E";
		}
	}
	elseif (
		$action == "delete" 
		&& CSocNetUser::IsCurrentUserModuleAdmin(SITE_ID, false)
	)
	{
		$log_id = intval($_REQUEST["log_id"]);
		if ($log_id > 0)
		{
			$arResult["bResult"] = (CSocNetLog::Delete($log_id) ? "Y" : "N");
		}
	}
	elseif ($action == "get_more_destination")
	{
		$isExtranetInstalled = (CModule::IncludeModule("extranet") ? "Y" : "N");
		$isExtranetSite = ($isExtranetInstalled == "Y" && CExtranet::IsExtranetSite() ? "Y" : "N");
		$isExtranetUser = ($isExtranetInstalled == "Y" && !CExtranet::IsIntranetUser() ? "Y" : "N");
		$isExtranetAdmin = ($isExtranetInstalled == "Y" && CExtranet::IsExtranetAdmin() ? "Y" : "N");

		if ($isExtranetUser == "Y")
		{
			$arUserIdVisible = CExtranet::GetMyGroupsUsersSimple(SITE_ID);
		}
		elseif (
			$isExtranetInstalled == "Y"
			&& $isExtranetUser != "Y"
			&& $isExtranetAdmin != "Y"
		)
		{
			if (
				$isExtranetAdmin == "Y"
				&& $bCurrentUserIsAdmin
			)
			{
				$arAvailableExtranetUserID = CExtranet::GetMyGroupsUsers(SITE_ID);				
			}
			else
			{
				$arAvailableExtranetUserID = CExtranet::GetMyGroupsUsersSimple(CExtranet::GetExtranetSiteID());
			}
		}

		$arResult["arDestinations"] = false;
		$log_id = intval($_REQUEST["log_id"]);
		$created_by_id = intval($_REQUEST["created_by_id"]);
		$iDestinationLimit = intval($_REQUEST["dlim"]);

		if ($log_id > 0)
		{
			$arRights = array();
			$db_events = GetModuleEvents("socialnetwork", "OnBeforeSocNetLogEntryGetRights");
			while ($arEvent = $db_events->Fetch())
			{
				if (ExecuteModuleEventEx(
						$arEvent, 
						array(
							array("LOG_ID" => $log_id),
							&$arRights
						)
					) === false
				)
				{
					$bSkipGetRights = true;
					break;
				}
			}
			if (!$bSkipGetRights)
			{
				$dbRight = CSocNetLogRights::GetList(array(), array("LOG_ID" => $log_id));
				while ($arRight = $dbRight->Fetch())
				{
					$arRights[] = $arRight["GROUP_CODE"];
				}
			}

			$arParams = array(
				"PATH_TO_USER" => $_REQUEST["p_user"],
				"PATH_TO_GROUP" => $_REQUEST["p_group"],
				"PATH_TO_CONPANY_DEPARTMENT" => $_REQUEST["p_dep"],
				"NAME_TEMPLATE" => $_REQUEST["nt"],
				"SHOW_LOGIN" => $_REQUEST["sl"],
				"DESTINATION_LIMIT" => 100,
				"CHECK_PERMISSIONS_DEST" => "N"
			);

			if ($created_by_id > 0)
				$arParams["CREATED_BY"] = $created_by_id;

			$arDestinations = CSocNetLogTools::FormatDestinationFromRights($arRights, $arParams, $iMoreCount);

			if (is_array($arDestinations))
			{
				$iDestinationsHidden = 0;

				$arGroupID = CSocNetLogTools::GetAvailableGroups();

				foreach($arDestinations as $key => $arDestination)
				{
					if (
						array_key_exists("TYPE", $arDestination)
						&& array_key_exists("ID", $arDestination)
						&& (
							(
								$arDestination["TYPE"] == "SG"
								&& !in_array(intval($arDestination["ID"]), $arGroupID)
							)
							|| (
								in_array($arDestination["TYPE"], array("CRMCOMPANY", "CRMLEAD", "CRMCONTACT", "CRMDEAL"))
								&& CModule::IncludeModule("crm")
								&& !CCrmAuthorizationHelper::CheckReadPermission(CCrmLiveFeedEntity::ResolveEntityTypeID($arDestination["TYPE"]), $arDestination["ID"])
							)
							|| (
								in_array($arDestination["TYPE"], array("DR", "D"))
								&& $isExtranetUser == "Y"
							)
							|| (
								$arDestination["TYPE"] == "U"
								&& isset($arUserIdVisible)
								&& is_array($arUserIdVisible)
								&& !in_array(intval($arDestination["ID"]), $arUserIdVisible)
							)
							|| (
								$arDestination["TYPE"] == "U"
								&& isset($arDestination["IS_EXTRANET"])
								&& $arDestination["IS_EXTRANET"] == "Y"
								&& isset($arAvailableExtranetUserID)
								&& is_array($arAvailableExtranetUserID)
								&& !in_array(intval($arDestination["ID"]), $arAvailableExtranetUserID)
							)
						)
					)
					{
						unset($arDestinations[$key]);
						$iDestinationsHidden++;
					}
				}

				$arResult["arDestinations"] = array_slice($arDestinations, $iDestinationLimit);
				$arResult["iDestinationsHidden"] = $iDestinationsHidden;
			}
		}
	}
	elseif ($action == "get_comment_src")
	{
		$arResult = false;
		$comment_id = intval($_REQUEST["comment_id"]);
		$post_id = intval($_REQUEST["post_id"]);

		if (
			$comment_id > 0 
			&& $post_id > 0
		)
		{
			if (IsModuleInstalled("intranet"))
			{
				$arOrder = array();
				$arFilter = array(
					"ID" => $comment_id
				);
			}
			else
			{
				$arOrder = array("ID" => "DESC");
				$arFilter = array(
					"LOG_ID" => $post_id
				);
			}

			$dbRes = CSocNetLogComments::GetList(
				$arOrder,
				$arFilter,
				false,
				false,
				array("ID", "EVENT_ID", "MESSAGE", "USER_ID", "SOURCE_ID")
			);
			if (
				($arRes = $dbRes->Fetch())
				&& ($arRes["EVENT_ID"] != "blog_comment")
				&& ($arRes["ID"] == $comment_id)
				&& (
					CSocNetUser::IsCurrentUserModuleAdmin() 
					|| $arRes["USER_ID"] == $GLOBALS["USER"]->GetID()
				)
			)
			{
				$arResult["message"] = str_replace("<br />", "\n", $arRes["MESSAGE"]);
				$arResult["sourceId"] = (intval($arRes["SOURCE_ID"]) > 0 ? intval($arRes["SOURCE_ID"]) : intval($arRes["ID"]));
			}
		}
	}	
	elseif ($action == "delete_comment")
	{
		$arResult = false;
		$comment_id = intval($_REQUEST["delete_comment_id"]);
		$post_id = intval($_REQUEST["post_id"]);

		if (
			$comment_id > 0 
			&& $post_id > 0
		)
		{
			if (IsModuleInstalled("intranet"))
			{
				$arOrder = array();
				$arFilter = array(
					"ID" => $comment_id
				);
			}
			else
			{
				$arOrder = array("ID" => "DESC");
				$arFilter = array(
					"LOG_ID" => $post_id
				);
			}

			$dbRes = CSocNetLogComments::GetList(
				$arOrder,
				$arFilter,
				false,
				false,
				array("ID", "EVENT_ID", "MESSAGE", "USER_ID", "SOURCE_ID")
			);

			if (
				($arRes = $dbRes->Fetch())
				&& ($arRes["EVENT_ID"] != "blog_comment")
				&& ($arRes["ID"] == $comment_id)
				&& (
					CSocNetUser::IsCurrentUserModuleAdmin() 
					|| $arRes["USER_ID"] == $GLOBALS["USER"]->GetID()
				)
			)
			{
				$bSuccess = CSocNetLogComments::Delete($arRes["ID"], true);

				if (!$bSuccess)
				{
					if ($e = $GLOBALS["APPLICATION"]->GetException())
					{
						$errorMessage = $e->GetString();
					}
				}
			}
		}

		$arResult = array(
			'errorMessage' => ($bSuccess ? '' : ($errorMessage && strlen($errorMessage) > 0 ? $errorMessage : '')),
			'okMessage' =>  ($bSuccess ? GetMessage('SONET_LOG_COMMENT_DELETED') : ''),
			'status' => ($bSuccess ? true : false),
			'message' => '',
			'messageBBCode' => '',
			'messageId' => array(
				$_REQUEST['ENTITY_XML_ID'], 
				($arRes ? (intval($arRes["SOURCE_ID"]) > 0 ? intval($arRes["SOURCE_ID"]) : intval($arRes["ID"])) : 0)
			),
			'messageFields' => ''			
		);
	}

	header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
	echo CUtil::PhpToJSObject($arResult);
}

define('PUBLIC_AJAX_MODE', true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>