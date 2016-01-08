<?
define("NO_KEEP_STATISTIC", true);
define("BX_STATISTIC_BUFFER_USED", false);
define("NO_LANG_FILES", true);
define("NOT_CHECK_PERMISSIONS", true);
define("BX_PUBLIC_TOOLS", true);

$site_id = isset($_REQUEST["site"]) && is_string($_REQUEST["site"]) ? trim($_REQUEST["site"]) : "";
$site_id = substr(preg_replace("/[^a-z0-9_]/i", "", $site_id), 0, 2);

define("SITE_ID", $site_id);
define("SITE_TEMPLATE_ID", "mobile_app");

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/bx_root.php");

$action = isset($_REQUEST["action"]) && is_string($_REQUEST["action"]) ? trim($_REQUEST["action"]) : "";

$lng = isset($_REQUEST["lang"]) && is_string($_REQUEST["lang"]) ? trim($_REQUEST["lang"]) : "";
$lng = substr(preg_replace("/[^a-z0-9_]/i", "", $lng), 0, 2);

$ls = isset($_REQUEST["ls"]) && is_string($_REQUEST["ls"]) ? trim($_REQUEST["ls"]) : "";
$ls_arr = isset($_REQUEST["ls_arr"])? $_REQUEST["ls_arr"]: "";

$as = isset($_REQUEST["as"]) ? intval($_REQUEST["as"]) : 58;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$rsSite = CSite::GetByID($site_id);
if ($arSite = $rsSite->Fetch())
{
	define("LANGUAGE_ID", $arSite["LANGUAGE_ID"]);
}
else
{
	define("LANGUAGE_ID", "en");
}

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix/mobile.socialnetwork.log.entry/include.php");

__IncludeLang(dirname(__FILE__)."/lang/".$lng."/ajax.php");

if(CModule::IncludeModule("compression"))
	CCompress::Disable2048Spaces();

if(CModule::IncludeModule("socialnetwork"))
{
	$bCurrentUserIsAdmin = CSocNetUser::IsCurrentUserModuleAdmin();

	// write and close session to prevent lock;
	session_write_close();

	$arResult = array();

	if (!$GLOBALS["USER"]->IsAuthorized())
	{
		$arResult[0] = "*";
	}
	elseif (!check_bitrix_sessid())
	{
		$arResult[0] = "*";
	}
	elseif (in_array($action, array("add_comment", "edit_comment", "delete_comment", "file_comment_upload")))
	{
		$log_id = $_REQUEST["log_id"];
		if ($arLog = CSocNetLog::GetByID($log_id))
		{
			$log_entity_type = $arLog["ENTITY_TYPE"];
			$arListParams = (strpos($log_entity_type, "CRM") === 0 && IsModuleInstalled("crm")
				? array("IS_CRM" => "Y", "CHECK_CRM_RIGHTS" => "Y")
				: array("CHECK_RIGHTS" => "Y", "USE_SUBSCRIBE" => "N")
			);
		}
		else
		{
			$log_id = 0;
		}

		if (
			intval($log_id) <= 0
			|| !($rsLog = CSocNetLog::GetList(array(), array("ID" => $log_id), false, false, array(), $arListParams))
			|| !($arLog = $rsLog->Fetch())
		)
		{
			$arResult["strMessage"] = GetMessage("Log event not found");
		}

		if (!isset($arResult["strMessage"]))
		{
			$arEntityXMLID = array(
				"tasks" => "TASK",
				"forum" => "FORUM",
				"photo_photo" => "PHOTO",
				"sonet" => "SOCNET",
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
			else
			{
				$entity_xml_id = (
					array_key_exists($arLog["EVENT_ID"], $arEntityXMLID)
					&& $arLog["SOURCE_ID"] > 0
						? $arEntityXMLID[$arLog["EVENT_ID"]]."_".$arLog["SOURCE_ID"]
						: strtoupper($arLog["EVENT_ID"])."_".$arLog["ID"]
				);
			}

			$arCommentEvent = CSocNetLogTools::FindLogCommentEventByLogEventID($arLog["EVENT_ID"]);
			if (!$arCommentEvent)
			{
				$arResult["strMessage"] = GetMessage("Comment event not found");
			}
		}

		if (!isset($arResult["strMessage"]))
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

			if (!$bCanAddComments)
			{
				$arResult["strMessage"] = GetMessage("SONET_LOG_COMMENT_NO_PERMISSIONS");
			}
		}

		if (!isset($arResult["strMessage"]))
		{
			$editCommentID = ($_REQUEST["action"] == 'edit_comment' ? intval($_REQUEST["edit_id"]) : false);
			$deleteCommentID = ($_REQUEST["action"] == 'delete_comment' ? intval($_REQUEST["delete_id"]) : false);

			if (
				$editCommentID > 0
				|| $deleteCommentID > 0
			)
			{
				$rsComment = CSocNetLogComments::GetList(
					array(),
					array(
						"ID" => ($editCommentID > 0 ? $editCommentID : $deleteCommentID)
					),
					false,
					false,
					array("ID", "USER_ID", "EVENT_ID")
				);

				$arComment = $rsComment->Fetch();
			}

			if (
				$editCommentID > 0
				|| $deleteCommentID <= 0
			)
			{
				if ($action == "file_comment_upload")
				{
					$arFileStorage = CMobileHelper::InitFileStorage();

					if (isset($arFileStorage["ERROR_CODE"]))
					{
						$arResult["strMessage"] = (!empty($arFileStorage["ERROR_MESSAGE"]) ? $arFileStorage["ERROR_MESSAGE"] : "Cannot init storage");
					}

					if (!isset($arResult["strMessage"]))
					{
						$moduleId = "uf";

						$arFile = $_FILES["file"];
						$arFile["MODULE_ID"] = $moduleId;

						$ufCode = (
							isset($arFileStorage["DISC_FOLDER"])
							|| isset($arFileStorage["WEBDAV_DATA"])
								? "UF_SONET_COM_DOC"
								: "UF_SONET_COM_FILE"
						);

						$arPostFields = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("SONET_COMMENT", 0, LANGUAGE_ID);
						if (empty($arPostFields[$ufCode]))
						{
							$arResult["strMessage"] = "Userfield not exists";
						}
					}

					if (!isset($arResult["strMessage"]))
					{
						$pos = strpos($arFile["name"], '?');
						if ($pos !== false)
						{
							$arFile["name"] = substr($arFile["name"], 0, $pos);
						}

						$res = ''.CFile::CheckImageFile(
							$arFile,
							(
								intval($arPostFields[$ufCode]['SETTINGS']['MAX_ALLOWED_SIZE']) > 0
									? $arPostFields[$ufCode]['SETTINGS']['MAX_ALLOWED_SIZE']
									: 5000000
							),
							0,
							0
						);

						if ($res !== '')
						{
							$arResult["strMessage"] = "Incorrect file";
						}
					}

					if (!isset($arResult["strMessage"]))
					{
						$arSaveResult = CMobileHelper::SaveFile($arFile, $arFileStorage);

						if (
							!$arSaveResult
							|| !isset($arSaveResult["ID"])
						)
						{
							$arResult["strMessage"] = "Can't save file";
						}
					}

					if (!isset($arResult["strMessage"]))
					{
						if (isset($arFileStorage["DISC_FOLDER"]))
						{
							$comment_text = "[DISK FILE ID=n".$arSaveResult["ID"]."]";
						}
						elseif (isset($arFileStorage["WEBDAV_DATA"]))
						{
							$comment_text = "[DOCUMENT ID=".$arSaveResult["ID"]."]";
						}
						else
						{
							$comment_text = ".";
						}
					}
				}
				else
				{
					$arParams = array(
						"PATH_TO_USER_BLOG_POST" => $_REQUEST["p_ubp"],
						"PATH_TO_GROUP_BLOG_POST" => $_REQUEST["p_gbp"],
						"PATH_TO_USER_MICROBLOG_POST" => $_REQUEST["p_umbp"],
						"PATH_TO_GROUP_MICROBLOG_POST" => $_REQUEST["p_gmbp"],
						"BLOG_ALLOW_POST_CODE" => $_REQUEST["bapc"]
					);

					$comment_text = $_REQUEST["message"];
					CUtil::decodeURIComponent($comment_text);
					$comment_text = trim($comment_text);
				}

				if (!isset($arResult["strMessage"]))
				{
					if (strlen($comment_text) <= 0)
					{
						$arResult["strMessage"] = GetMessage("SONET_LOG_COMMENT_EMPTY");
					}
				}

				if (!isset($arResult["strMessage"]))
				{
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
						"SMILES" => "N"
					);

					if (
						$editCommentID > 0
						&& $arComment
					)
					{
						$bHasEditCallback = (
							is_array($arCommentEvent)
							&& isset($arCommentEvent["UPDATE_CALLBACK"])
							&& (
								$arCommentEvent["UPDATE_CALLBACK"] == "NO_SOURCE"
								|| is_callable($arCommentEvent["UPDATE_CALLBACK"])
							)
						);

						if (
							$bHasEditCallback
							&& $arComment["USER_ID"] == $GLOBALS["USER"]->GetId()
						)
						{
							$arFields = array(
								"MESSAGE" => $comment_text,
								"TEXT_MESSAGE" => $comment_text,
								"EVENT_ID" => $arComment["EVENT_ID"]
							);

							CSocNetLogComponent::checkEmptyUFValue('UF_SONET_COM_DOC');

							$GLOBALS["USER_FIELD_MANAGER"]->EditFormAddFields("SONET_COMMENT", $arFields);

							if (
								!empty($_POST["attachedFilesRaw"])
								&& is_array($_POST["attachedFilesRaw"])
							)
							{
								CSocNetLogComponent::saveRawFilesToUF(
									$_POST["attachedFilesRaw"],
									(
										IsModuleInstalled("webdav")
										|| IsModuleInstalled("disk")
											? "UF_SONET_COM_DOC"
											: "UF_SONET_COM_FILE"
									),
									$arFields
								);
							}

							$comment = CSocNetLogComments::Update($editCommentID, $arFields, true);
						}
					}
					else
					{
						$arFields = array(
							"ENTITY_TYPE" => $arLog["ENTITY_TYPE"],
							"ENTITY_ID" => $arLog["ENTITY_ID"],
							"EVENT_ID" => $arCommentEvent["EVENT_ID"],
							"=LOG_DATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
							"MESSAGE" => $comment_text,
							"TEXT_MESSAGE" => $comment_text,
							"URL" => $source_url,
							"MODULE_ID" => false,
							"LOG_ID" => $arLog["ID"],
							"USER_ID" => $GLOBALS["USER"]->GetID(),
							"PATH_TO_USER_BLOG_POST" => $arParams["PATH_TO_USER_BLOG_POST"],
							"PATH_TO_GROUP_BLOG_POST" => $arParams["PATH_TO_GROUP_BLOG_POST"],
							"PATH_TO_USER_MICROBLOG_POST" => $arParams["PATH_TO_USER_MICROBLOG_POST"],
							"PATH_TO_GROUP_MICROBLOG_POST" => $arParams["PATH_TO_GROUP_MICROBLOG_POST"],
							"BLOG_ALLOW_POST_CODE" => $arParams["BLOG_ALLOW_POST_CODE"],
						);

						if ($arSaveResult)
						{
							$arFields[$ufCode] = array(
								(isset($arFileStorage["DISC_FOLDER"]) ? "n".$arSaveResult["ID"] : $arSaveResult["ID"])
							);
						}
						$GLOBALS[$ufCode] = $arFields[$ufCode];
						$comment = CSocNetLogComments::Add($arFields, true, false);
						unset($GLOBALS[$ufCode]);
						CSocNetLog::CounterIncrement($comment, false, false, "LC");
					}

					if (
						!is_array($comment)
						&& intval($comment) > 0
					)
					{
						$arResult["SUCCESS"] = "Y";
						$arResult["commentID"] = $comment;
						$arResult["arCommentFormatted"] = __SLMAjaxGetComment($comment, $arParams);
						if ($arComment = CSocNetLogComments::GetByID($comment))
						{
							$strAfter = "";

							$arResult["arCommentFormatted"]["SOURCE_ID"] = ($arComment["SOURCE_ID"] > 0 ? $arComment["SOURCE_ID"] : $arComment["ID"]);

							if (
								strlen($arComment["RATING_TYPE_ID"]) > 0
								&& intval($arComment["RATING_ENTITY_ID"]) > 0
							)
							{
								$arResult["arCommentFormatted"]["EVENT"]["RATING_TYPE_ID"] = $arComment["RATING_TYPE_ID"];
								$arResult["arCommentFormatted"]["EVENT"]["RATING_ENTITY_ID"] = $arComment["RATING_ENTITY_ID"];
								$arResult["arCommentFormatted"]["EVENT"]["RATING_USER_VOTE_VALUE"] = $arComment["RATING_USER_VOTE_VALUE"];
								$arResult["arCommentFormatted"]["EVENT"]["RATING_TOTAL_POSITIVE_VOTES"] = $arComment["RATING_TOTAL_POSITIVE_VOTES"];
							}

							$arComment["UF"] = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("SONET_COMMENT", $arComment["ID"], LANGUAGE_ID);
							$arUFResult = CMobileHelper::BuildUFFields($arComment["UF"]);
							$arResult["arCommentFormatted"]["UF_FORMATTED"] = $arUFResult["AFTER_MOBILE"];

							$arResult["arCommentFormatted"]["CAN_EDIT"] = $arResult["arCommentFormatted"]["CAN_DELETE"] = (
								$arResult["arCommentFormatted"]["USER_ID"] == $GLOBALS["USER"]->GetId()
									? "Y"
									: "N"
							);

							$arResult["arCommentFormatted"]["EVENT"]["ID"] = $arComment["ID"];
							$arResult["arCommentFormatted"]["EVENT"]["LOG_ID"] = $arComment["LOG_ID"];

							if ($editCommentID <= 0)
							{
								$strAfter .= $arUFResult["AFTER"];
								$strUFMobile = $arUFResult["AFTER_MOBILE"];

								ob_start();

								?><script>
									top.text<?=$comment?> = text<?=$comment?> = '<?=CUtil::JSEscape(htmlspecialcharsBack($arComment["POST_TEXT"]))?>';
									top.title<?=$comment?> = title<?=$comment?> = '<?=(isset($arComment["TITLE"]) ? CUtil::JSEscape($arComment["TITLE"]) : '')?>';
									top.arComFiles<?=$comment?> = [];<?
								?></script><?
								$strAfter .= ob_get_clean();

								if (
									!empty($arComment["RATING_TYPE_ID"])
									&& !empty($_REQUEST["sr"])
									&& $_REQUEST["sr"] == "Y"
								)
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
									{
										$arRating = $arRatings[$arComment["ID"]];
									}

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
										"like",
										$arRatingParams,
										null,
										array("HIDE_ICONS" => "Y")
									);
									$strRating = ob_get_clean();

									ob_start();
									$GLOBALS["APPLICATION"]->IncludeComponent(
										"bitrix:rating.vote",
										"mobile_comment_like",
										$arRatingParams,
										null,
										array("HIDE_ICONS" => "Y")
									);
									$strRatingMobile = ob_get_clean();
								}

								$strPathToLogEntry = str_replace("#log_id#", $arComment["LOG_ID"], COption::GetOptionString("socialnetwork", "log_entry_page", "/company/personal/log/#log_id#/", SITE_ID));
								$strPathToLogEntryComment = $strPathToLogEntry.(strpos($strPathToLogEntry, "?") !== false ? "&" : "?")."commentID=".$comment."#".$comment;

								$arPullMessageParams = Array(
									"ID" => $comment,
									"ENTITY_XML_ID" => $entity_xml_id,
									"FULL_ID" => array(
										$entity_xml_id,
										($arComment["SOURCE_ID"] > 0 ? $arComment["SOURCE_ID"] : $comment)
									),
									"SONET_FULL_ID" => array(
										$arComment["LOG_ID"],
										$comment
									),
									"ACTION" => "REPLY",
									"APPROVED" => "Y",
									"PANELS" => array(
										"EDIT" => "N",
										"MODERATE" => "N",
										"DELETE" => "N"
									),
									"NEW" => "Y",
									"AUTHOR" => array(
										"ID" => $GLOBALS["USER"]->GetID(),
										"NAME" => $arResult["arCommentFormatted"]["CREATED_BY"]["FORMATTED"],
										"URL" => $arResult["arCommentFormatted"]["CREATED_BY"]["URL"],
										"E-MAIL" => "",
										"AVATAR" => $arResult["arCommentFormatted"]["AVATAR_SRC"],
										"IS_EXTRANET" => (is_array($GLOBALS["arExtranetUserID"]) && in_array($GLOBALS["USER"]->GetID(), $GLOBALS["arExtranetUserID"])),
									),
									"POST_TIMESTAMP" => MakeTimeStamp(array_key_exists("LOG_DATE_FORMAT", $arComment) && !empty($arComment["LOG_DATE_FORMAT"]) ? $arComment["LOG_DATE_FORMAT"] : $arComment["LOG_DATE"]),
									"POST_TIME" => $arResult["arCommentFormatted"]["LOG_TIME_FORMAT"],
									"POST_DATE" => $arResult["arCommentFormatted"]["LOG_DATE_DAY"],
									"POST_MESSAGE_TEXT" => (
										isset($arResult["arCommentFormatted"])
										&& isset($arResult["arCommentFormatted"]["MESSAGE_FORMAT"])
											? $arResult["arCommentFormatted"]["MESSAGE_FORMAT"]
											: $arComment["MESSAGE"]
									),
									"~POST_MESSAGE_TEXT" => "",
									"POST_MESSAGE_TEXT_MOBILE" => (
										isset($arResult["arCommentFormatted"])
										&& isset($arResult["arCommentFormatted"]["MESSAGE_FORMAT_MOBILE"])
											? $arResult["arCommentFormatted"]["MESSAGE_FORMAT_MOBILE"]
											: $arComment["MESSAGE"]
									),
									"URL" => array(
										"VIEW" => $strPathToLogEntryComment,
										"EDIT" => "__logEditComment('".$entity_xml_id."', '".$comment."', '".$arComment["LOG_ID"]."');",
										"DELETE" => "/bitrix/components/bitrix/socialnetwork.log.entry/ajax.php?lang=".LANGUAGE_ID."&action=delete_comment&delete_comment_id=".$comment."&post_id=".$arComment["LOG_ID"]."&site=".SITE_ID."#".$comment
									),
									"BEFORE_ACTIONS" => $strRating,
									"BEFORE_ACTIONS_MOBILE" => $strRatingMobile,
									"AFTER" => $strAfter,
									"AFTER_MOBILE" => $strUFMobile
								);

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
						is_array($comment)
						&& array_key_exists("MESSAGE", $comment)
						&& strlen($comment["MESSAGE"]) > 0
					)
					{
						$arResult["strMessage"] = $comment["MESSAGE"];
						$arResult["commentText"] = $comment_text;
					}
				}
			}
			elseif (
				$deleteCommentID > 0
				&& $arComment
			)
			{
				$bHasDeleteCallback = (
					is_array($arCommentEvent)
					&& isset($arCommentEvent["DELETE_CALLBACK"])
					&& (
						$arCommentEvent["DELETE_CALLBACK"] == "NO_SOURCE"
						|| is_callable($arCommentEvent["DELETE_CALLBACK"])
					)
				);

				if (
					$bHasDeleteCallback
					&& $arComment["USER_ID"] == $GLOBALS["USER"]->GetId()
				)
				{
					if (CSocNetLogComments::Delete($deleteCommentID, true))
					{
						$arResult["commentID"] = $deleteCommentID;
					}
					else
					{
						$arResult["strMessage"] = GetMessage("SONET_LOG_COMMENT_CANT_DELETE");
					}
				}
				else
				{
					$arResult["strMessage"] = GetMessage("SONET_LOG_COMMENT_DELETE_NO_PERMISSIONS");
				}
			}
		}
	}
	elseif ($action == "get_comment")
	{
		$arResult["arCommentFormatted"] = __SLMAjaxGetComment($_REQUEST["cid"], $arParams, true);
	}
	elseif ($action == "get_comment_data")
	{
		$log_id = (
			isset($_REQUEST["log_id"])
				? intval($_REQUEST["log_id"])
				: 0
		);

		$comment_id = (
			isset($_REQUEST["cid"])
				? intval($_REQUEST["cid"])
				: 0
		);

		if ($arLog = CSocNetLog::GetByID($log_id))
		{
			$log_entity_type = $arLog["ENTITY_TYPE"];
			$arListParams = (strpos($log_entity_type, "CRM") === 0 && IsModuleInstalled("crm")
				? array("IS_CRM" => "Y", "CHECK_CRM_RIGHTS" => "Y")
				: array("CHECK_RIGHTS" => "Y", "USE_SUBSCRIBE" => "N")
			);
		}
		else
		{
			$log_id = 0;
		}

		if (
			$log_id > 0
			&& ($rsLog = CSocNetLog::GetList(array(), array("ID" => $log_id), false, false, array(), $arListParams))
			&& ($arLog = $rsLog->Fetch())
		)
		{
			$rsComment = CSocNetLogComments::GetList(
				array(),
				array(
					"ID" => $comment_id
				),
				false,
				false,
				array("ID", "USER_ID", "MESSAGE")
			);

			if ($arComment = $rsComment->Fetch())
			{
				if($arComment["USER_ID"] == $GLOBALS["USER"]->GetId())
				{
					$arResult["CommentCanEdit"] = 'Y';
				}

				$arResult["CommentDetailText"] = htmlspecialcharsback($arComment["MESSAGE"]);

				$bDiskOrWebDavInstalled = (IsModuleInstalled('disk') || IsModuleInstalled('webdav'));

				$ufCode = (
					$bDiskOrWebDavInstalled
						? "UF_SONET_COM_DOC"
						: "UF_SONET_COM_FILE"
				);

				$arResult["CommentUFCode"] = $ufCode;

				$arResult["CommentFiles"] = CMobileHelper::getUFForPostForm(array(
					"ENTITY_TYPE" => "SONET_COMMENT",
					"ENTITY_ID" => $comment_id,
					"UF_CODE" => $ufCode,
					"IS_DISK_OR_WEBDAV_INSTALLED" => $bDiskOrWebDavInstalled
				));
			}
		}
	}
	elseif ($action == "get_comments")
	{
		$arResult["arComments"] = array();

		$log_tmp_id = $_REQUEST["logid"];
		$last_comment_id = intval($_REQUEST["last_comment_id"]);
		$last_comment_ts = intval($_REQUEST["last_comment_ts"]);

		if ($arLog = CSocNetLog::GetByID($log_tmp_id))
		{
			$log_entity_type = $arLog["ENTITY_TYPE"];
			if (
				strpos($log_entity_type, "CRM") === 0
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
			$log_tmp_id = 0;
		}

		if (
			intval($log_tmp_id) > 0
			&& ($rsLog = CSocNetLog::GetList(array(), array("ID" => $log_tmp_id), false, false, array("ID", "EVENT_ID"), $arListParams))
			&& ($arLog = $rsLog->Fetch())
		)
		{
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

			$arParams = array(
				"PATH_TO_USER" => $_REQUEST["p_user"],
				"NAME_TEMPLATE" => $_REQUEST["nt"],
				"SHOW_LOGIN" => $_REQUEST["sl"],
				"AVATAR_SIZE_COMMENT" => $as,
				"PATH_TO_SMILE" => $_REQUEST["p_smile"],
				"DATE_TIME_FORMAT" => $_REQUEST["dtf"]
			);

			$cache_time = 31536000;
			$cache = new CPHPCache;

			$arCacheID = array();
			$arKeys = array(
				"AVATAR_SIZE_COMMENT",
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
				$arCacheID[$param_key] = (array_key_exists($param_key, $arParams) ? $arParams[$param_key] : false);
			}

			$cache_id = "log_comments_".$log_tmp_id."_".md5(serialize($arCacheID))."_mobile_app_".SITE_ID."_".LANGUAGE_ID."_".FORMAT_DATETIME."_".CTimeZone::GetOffset();
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
				$arCommentsFullList = array();

				if (is_object($cache))
				{
					$cache->StartDataCache($cache_time, $cache_id, $cache_path);
				}

				if (defined("BX_COMP_MANAGED_CACHE"))
				{
					$GLOBALS["CACHE_MANAGER"]->StartTagCache($cache_path);
				}

				$arFilter = array("LOG_ID" => $log_tmp_id);

				$arSelect = array(
					"ID", "LOG_ID", "SOURCE_ID", "ENTITY_TYPE", "ENTITY_ID", "USER_ID", "EVENT_ID", "LOG_DATE", "MESSAGE", "TEXT_MESSAGE", "URL", "MODULE_ID",
					"GROUP_NAME", "GROUP_OWNER_ID", "GROUP_VISIBLE", "GROUP_OPENED", "GROUP_IMAGE_ID",
					"USER_NAME", "USER_LAST_NAME", "USER_SECOND_NAME", "USER_LOGIN", "USER_PERSONAL_PHOTO", "USER_PERSONAL_GENDER",
					"CREATED_BY_NAME", "CREATED_BY_LAST_NAME", "CREATED_BY_SECOND_NAME", "CREATED_BY_LOGIN", "CREATED_BY_PERSONAL_PHOTO", "CREATED_BY_PERSONAL_GENDER",
					"LOG_SITE_ID", "LOG_SOURCE_ID",
					"RATING_TYPE_ID", "RATING_ENTITY_ID",
					"UF_*"
				);

				$arListParams = array("USE_SUBSCRIBE" => "N");

				$arUFMeta = __SLMGetUFMeta();

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

					$arResult["arComments"][] = __SLMGetLogCommentRecord($arComments, $arParams, false);
				}

				if (is_object($cache))
				{
					$arCacheData = Array(
						"COMMENTS_FULL_LIST" => $arResult["arComments"]
					);
					$cache->EndDataCache($arCacheData);
					if(defined("BX_COMP_MANAGED_CACHE"))
						$GLOBALS["CACHE_MANAGER"]->EndTagCache();
				}
			}

			foreach ($arResult["arComments"] as $key => $arCommentTmp)
			{
				if ($key === 0)
				{
					$rating_entity_type = $arCommentTmp["EVENT"]["RATING_TYPE_ID"];
				}

				if (
					(
						$last_comment_ts > 0
						&& $arCommentTmp["LOG_DATE_TS"] >= $last_comment_ts
					)
					|| (
						$last_comment_ts <= 0
						&& $arCommentTmp["EVENT"]["ID"] >= $last_comment_id
					)

				)
				{
					unset($arResult["arComments"][$key]);
				}
				else
				{
					$arCommentID[] = $arCommentTmp["EVENT"]["RATING_ENTITY_ID"];
				}
			}

			$arRatingComments = array();
			if(
				!empty($arCommentID)
				&& strlen($rating_entity_type) > 0
			)
				$arRatingComments = CRatings::GetRatingVoteResult($rating_entity_type, $arCommentID);

			foreach($arResult["arComments"] as $key => $arCommentTmp)
			{
				if (
					array_key_exists("EVENT_FORMATTED", $arCommentTmp)
					&& array_key_exists("MESSAGE", $arCommentTmp["EVENT_FORMATTED"])
					&& strlen($arCommentTmp["EVENT_FORMATTED"]["MESSAGE"]) > 0
				)
					$arResult["arComments"][$key]["EVENT_FORMATTED"]["MESSAGE"] = htmlspecialcharsBack($arCommentTmp["EVENT_FORMATTED"]["MESSAGE"]);
				elseif (
					array_key_exists("EVENT", $arCommentTmp)
					&& array_key_exists("MESSAGE", $arCommentTmp["EVENT"])
					&& strlen($arCommentTmp["EVENT"]["MESSAGE"]) > 0
				)
					$arResult["arComments"][$key]["EVENT"]["MESSAGE"] = htmlspecialcharsBack($arCommentTmp["EVENT"]["MESSAGE"]);

				if (array_key_exists($arCommentTmp["EVENT"]["RATING_ENTITY_ID"], $arRatingComments))
				{
					$arResult["arComments"][$key]["EVENT"]["RATING_USER_VOTE_VALUE"] = $arRatingComments[$arCommentTmp["EVENT"]["RATING_ENTITY_ID"]]["USER_VOTE"];
					$arResult["arComments"][$key]["EVENT"]["RATING_USER_HAS_VOTED"] = ($arRatingComments[$arCommentTmp["EVENT"]["RATING_ENTITY_ID"]]["USER_HAS_VOTED"] == "Y" ? "Y" : "N");
					$arResult["arComments"][$key]["EVENT"]["RATING_TOTAL_POSITIVE_VOTES"] = intval($arRatingComments[$arCommentTmp["EVENT"]["RATING_ENTITY_ID"]]["TOTAL_POSITIVE_VOTES"]);
					$arResult["arComments"][$key]["EVENT"]["RATING_TOTAL_NEGATIVE_VOTES"] = intval($arRatingComments[$arCommentTmp["EVENT"]["RATING_ENTITY_ID"]]["TOTAL_NEGATIVE_VOTES"]);
					$arResult["arComments"][$key]["EVENT"]["RATING_TOTAL_VALUE"] = $arRatingComments[$arCommentTmp["EVENT"]["RATING_ENTITY_ID"]]["TOTAL_VALUE"];
					$arResult["arComments"][$key]["EVENT"]["RATING_TOTAL_VOTES"] = intval($arRatingComments[$arCommentTmp["EVENT"]["RATING_ENTITY_ID"]]["TOTAL_VOTES"]);
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

				if (
					is_array($arResult["arComments"][$key]["UF"])
					&& count($arResult["arComments"][$key]["UF"]) > 0
				)
				{
					ob_start();

					$eventHandlerID = false;
					$eventHandlerID = AddEventHandler("main", "system.field.view.file", "__logUFfileShowMobile");
					foreach ($arResult["arComments"][$key]["UF"] as $FIELD_NAME => $arUserField)
					{
						if(!empty($arUserField["VALUE"]))
						{
							$GLOBALS["APPLICATION"]->IncludeComponent(
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
					if (
						$eventHandlerID !== false
						&& intval($eventHandlerID) > 0
					)
					{
						RemoveEventHandler("main", "system.field.view.file", $eventHandlerID);
					}

					$strUFBlock = ob_get_contents();
					ob_end_clean();

					$arResult["arComments"][$key]["EVENT_FORMATTED"]["UF_FORMATTED"] = $strUFBlock;
				}

				$arResult["arComments"][$key]["EVENT_FORMATTED"]["CAN_EDIT"] = (
					$bHasEditCallback
					&& intval($arResult["arComments"][$key]["EVENT"]["USER_ID"]) > 0
					&& intval($arResult["arComments"][$key]["EVENT"]["USER_ID"]) == $GLOBALS["USER"]->GetId()
						? "Y"
						: "N"
				);

				$arResult["arComments"][$key]["EVENT_FORMATTED"]["CAN_DELETE"] = (
					$bHasDeleteCallback
					&& $arResult["arComments"][$key]["EVENT_FORMATTED"]["CAN_EDIT"] == "Y"
						? "Y"
						: "N"
				);

				$timestamp = MakeTimeStamp($arResult["arComments"][$key]["EVENT"]["LOG_DATE"]);
				$arFormat = Array(
					"tommorow" => "tommorow, ".GetMessage("SONET_LOG_COMMENT_FORMAT_TIME"),
					"today" => "today, ".GetMessage("SONET_LOG_COMMENT_FORMAT_TIME"),
					"yesterday" => "yesterday, ".GetMessage("SONET_LOG_COMMENT_FORMAT_TIME"),
					"" => (
						date("Y", $timestamp) == date("Y")
							? GetMessage("SONET_LOG_COMMENT_FORMAT_DATE")
							: GetMessage("SONET_LOG_COMMENT_FORMAT_DATE_YEAR")
					)
				);

				$arResult["arComments"][$key]["EVENT_FORMATTED"]["DATETIME"] = FormatDate($arFormat, $timestamp);
			}
		}
	}
	elseif ($action == "get_more_destination")
	{
		$arResult["arDestinations"] = false;
		$log_id = intval($_REQUEST["log_id"]);
		$author_id = intval($_REQUEST["author_id"]);
		$iDestinationLimit = intval($_REQUEST["dlim"]);

		if ($log_id > 0)
		{
			$dbRight = CSocNetLogRights::GetList(array(), array("LOG_ID" => $log_id));
			while ($arRight = $dbRight->Fetch())
			{
				$arRights[] = $arRight["GROUP_CODE"];
			}

			$arParams = array(
				"MOBILE" => "Y",
				"PATH_TO_USER" => $_REQUEST["p_user"],
				"PATH_TO_GROUP" => $_REQUEST["p_group"],
				"PATH_TO_CONPANY_DEPARTMENT" => $_REQUEST["p_dep"],
				"PATH_TO_CRMLEAD" => $_REQUEST["p_crmlead"],
				"PATH_TO_CRMDEAL" => $_REQUEST["p_crmdeal"],
				"PATH_TO_CRMCONTACT" => $_REQUEST["p_crmcontact"],
				"PATH_TO_CRMCOMPANY" => $_REQUEST["p_crmcompany"],
				"NAME_TEMPLATE" => $_REQUEST["nt"],
				"SHOW_LOGIN" => $_REQUEST["sl"],
				"DESTINATION_LIMIT" => 100,
				"CHECK_PERMISSIONS_DEST" => "N",
				"CREATED_BY" => $author_id
			);

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
						&& $arDestination["TYPE"] == "SG"
						&& !in_array(intval($arDestination["ID"]), $arGroupID)
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
	elseif (
		$action == "send_comment_writing"
		&& CModule::IncludeModule("pull")
	)
	{
		$arParams = array(
			"ENTITY_XML_ID" => $_REQUEST["ENTITY_XML_ID"],
			"NAME_TEMPLATE" => $_REQUEST["nt"],
			"SHOW_LOGIN" => $_REQUEST["sl"],
			"AVATAR_SIZE_COMMENT" => intval($as),
		);

		$rsUser = CUser::GetList(
			($by="last_name"),
			($order="asc"),
			array(
				"ID" => intval($GLOBALS["USER"]->GetId())
			),
			array("FIELDS" => array("ID", "NAME", "LAST_NAME", "SECOND_NAME", "LOGIN", "PERSONAL_GENDER", "PERSONAL_PHOTO"))
		);
		if ($arUser = $rsUser->Fetch())
		{
			$arFileTmp = CFile::ResizeImageGet(
				$arUser["PERSONAL_PHOTO"],
				array("width" => $arParams["AVATAR_SIZE_COMMENT"], "height" => $arParams["AVATAR_SIZE_COMMENT"]),
				BX_RESIZE_IMAGE_EXACT,
				false
			);

			CPullWatch::AddToStack('UNICOMMENTS'.$_REQUEST["ENTITY_XML_ID"],
				Array(
					'module_id' => 'unicomments',
					'command' => 'answer',
					'expiry' => 60,
					'params' => Array(
						"USER_ID" => $arUser["ID"],
						"ENTITY_XML_ID" => $_REQUEST["ENTITY_XML_ID"],
						"TS" => time(),
						"NAME" => CUser::FormatName($arParams["NAME_TEMPLATE"], $arUser, ($arParams["SHOW_LOGIN"] != "N" ? true : false)),
						"AVATAR" => ($arFileTmp && isset($arFileTmp['src']) ? $arFileTmp['src'] : false)
					)
				)
			);

			$arResult["SUCCESS"] = 'Y';
		}
	}

	header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
	echo \Bitrix\Main\Web\Json::encode($arResult);
}

define('PUBLIC_AJAX_MODE', true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
die();
?>