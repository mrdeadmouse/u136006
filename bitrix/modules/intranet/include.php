<?
if (!CModule::IncludeModule('iblock'))
	return false;

global $DBType;

$GLOBALS['INTR_DEPARTMENTS_CACHE'] = array();
$GLOBALS['INTR_DEPARTMENTS_CACHE_VALUE'] = array();
$GLOBALS['CACHE_HONOUR'] = null;
$GLOBALS['CACHE_ABSENCE'] = null;

define('BX_INTRANET_ABSENCE_HR', 0x1);
define('BX_INTRANET_ABSENCE_PERSONAL', 0x2);
define('BX_INTRANET_ABSENCE_ALL', BX_INTRANET_ABSENCE_HR|BX_INTRANET_ABSENCE_PERSONAL);

define('BX_INTRANET_SP_MAX_ERRORS', 3);
define('BX_INTRANET_SP_LOCK_TIME', 300);
define('BX_INTRANET_SP_QUEUE_COUNT', 5);
define('BX_INTRANET_SP_QUEUE_COUNT_MANUAL', 50);
define('BX_INTRANET_SP_LOG_COUNT', 3);
define('BX_INTRANET_SP_LOG_COUNT_MANUAL', 30);
define('BX_INTRANET_SP_NUM_ROWS_AUTO', 50);
define('BX_INTRANET_SP_NUM_ROWS_MANUAL', 100);

define('BX_INTRANET_SP_UF_NAME', 'UF_SP_ID');

define('SONET_INTRANET_NEW_USER_ENTITY', 'IN');
define('SONET_INTRANET_NEW_USER_EVENT_ID', 'intranet_new_user');
define('SONET_INTRANET_NEW_USER_COMMENT_EVENT_ID', 'intranet_new_user_comment');

IncludeModuleLangFile(__FILE__);

global $DBType;

CModule::AddAutoloadClasses(
	"intranet",
	array(
		"CIntranetUtils" => "classes/general/utils.php",
		"CIntranetSearch" => "tools/search.php",
		"CIntranetSearchConverter" => "tools/search.php",
		"CIntranetSearchConverters" => "tools/search.php",
		"CIntranetNotify" => "classes/general/notify.php",
		"CIntranetTasks" => "classes/general/tasks.php",
		"CEventCalendar" => "classes/general/event_calendar.php",

		"CIntranetCalendarWS" => "classes/general/ws_calendar.php",
		"CIntranetContactsWS" => "classes/general/ws_contacts.php",
		"CIntranetTasksWS" => "classes/general/ws_tasks.php",

		"CIntranetRestService" => "classes/general/rest.php",

		"CIntranetToolbar" => "classes/general/toolbar.php",
		"CIntranetTasksDocument" => "classes/general/tasks_document.php",

		"CIntranetSharepoint" => "classes/".$DBType."/sharepoint.php",
		"CIntranetSharepointQueue" => "classes/".$DBType."/sharepoint_queue.php",
		"CIntranetSharepointLog" => "classes/".$DBType."/sharepoint_log.php",

		"CIntranetAuthProvider" => "classes/general/authproviders.php",

		"CRatingRulesIntranet" => "classes/general/rating_rules.php",
		"CRatingsComponentsIntranet" => "classes/".$DBType."/ratings_components.php",

		"CIntranetPlanner" => "classes/general/planner.php",

		"CIntranetInviteDialog" => "classes/general/invite_dialog.php",
	)
);

$GLOBALS['INTRANET_TOOLBAR'] = new CIntranetToolbar();

CJSCore::RegisterExt('intranet_structure', array(
	'js' => '/bitrix/js/intranet/structure.js',
	'lang' => '/bitrix/modules/intranet/lang/'.LANGUAGE_ID.'/js_core_intranet_structure.php',
	'rel' => array('ajax', 'popup')
));

CJSCore::RegisterExt('planner', array(
	'js' => '/bitrix/js/intranet/core_planner.js',
	'css' => '/bitrix/js/intranet/core_planner.css',
	'lang' => '/bitrix/modules/intranet/lang/'.LANGUAGE_ID.'/js_core_intranet_planner.php',
	'rel' => array('date')
));

include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/properties.php");
/*patchlimitationmutatormark1*/

class CIntranetEventHandlers
{
	public static function SPRegisterUpdatedItem($arFields)
	{
		if (CBXFeatures::IsFeatureEnabled('intranet_sharepoint'))
		{
			if (CIntranetSharepoint::$bUpdateInProgress)
				return;

			$dbRes = CIntranetSharepoint::GetByID($arFields['IBLOCK_ID']);
			if ($arRes = $dbRes->Fetch())
			{
				CIntranetSharepoint::AddToUpdateLog($arFields);
			}
		}
	}

	function UpdateActivity($arFields)
	{
		if ($arFields['RESULT'] && isset($arFields['ACTIVE']))
		{
			$dbRes = CIBlockElement::GetList(array(), array(
				'IBLOCK_ID' => COption::GetOptionInt('intranet', 'iblock_state_history'),
				'PROPERTY_USER' => $arFields['ID'],
			));
			while ($arRes = $dbRes->Fetch())
			{
				CIBlockElement::SetPropertyValues($arRes['ID'], $arRes['IBLOCK_ID'], $arFields['ACTIVE'], 'USER_ACTIVE');
			}

			if ($arFields['ACTIVE'] == 'N')
			{
				$obs = new CIBlockSection();
				$dbRes = $obs->GetList(array(), array(
					'IBLOCK_ID' => COption::GetOptionInt('intranet', 'iblock_structure'),
					'UF_HEAD' => $arFields['ID'],
				));
				while ($arSection = $dbRes->Fetch())
				{
					$obs->Update($arSection['ID'], array('UF_HEAD' => null));
				}
			}
		}
	}

	function UpdateActivityIBlock(&$arFields)
	{
		if ($arFields['RESULT'])
		{
			// absence
			$iblock = COption::GetOptionInt('intranet', 'iblock_absence');
			if (!$iblock)
			{
				$iblock = array();
				$dbRes = CSite::GetList($b='SORT', $o='ASC');
				while ($arRes = $dbRes->Fetch())
				{
					if ($ib = COption::GetOptionInt('intranet', 'iblock_absence', false, $arRes['ID']))
						$iblock[] = $ib;
				}
			}
			else
			{
				$iblock = array($iblock);
			}

			if (count($iblock) > 0)
			{
				foreach ($iblock as $ib)
				{
					if ($arFields['IBLOCK_ID'] == $ib)
					{
						static $PROPERTY_USER = 0;

						if ($PROPERTY_USER <= 0)
						{
							$dbRes = CIBlockProperty::GetByID('USER', $arFields['IBLOCK_ID']);
							if ($arRes = $dbRes->Fetch())
								$PROPERTY_USER = $arRes['ID'];
						}

						if ($PROPERTY_USER > 0)
						{
//							$arPropertyValue = array_values($arFields['PROPERTY_VALUES']);
//							$USER_ID = $arPropertyValue[0];
							$USER_ID = $arFields['PROPERTY_VALUES']['USER'];
							$dbRes = CUser::GetByID($USER_ID);
							if ($arUser = $dbRes->Fetch())
								CIBlockElement::SetPropertyValues($arFields['ID'], $arFields['IBLOCK_ID'], $arUser['ACTIVE'], 'USER_ACTIVE');
						}
					}
				}
			}
			// -- absence

			// news
			if (intval($arFields["IBLOCK_ID"]) > 0)
			{
				$rsIBlock = CIBlock::GetByID($arFields["IBLOCK_ID"]);
				if ($arIBlock = $rsIBlock->Fetch())
				{
					if (
						$arIBlock["IBLOCK_TYPE_ID"] == "news"
						&& CModule::IncludeModule("socialnetwork")
					)
					{
						CSocNetAllowed::GetAllowedEntityTypes();

						$dbLog = CSocNetLog::GetList(array("ID" => "DESC"), array("EVENT_ID" => "news", "SOURCE_ID" => $arFields["ID"]));
						if ($arLog = $dbLog->Fetch())
						{
							if (
								$arFields["ACTIVE"] == "Y"
								&&
								(
									strlen($arFields["PREVIEW_TEXT"]) > 0
									|| strlen($arFields["DETAIL_TEXT"]) > 0
								)
								&&
								(
									!array_key_exists("WF", $arFields)
									|| $arFields["WF"] == "N"
									|| ($arFields["WF_STATUS_ID"] == 1 && $arFields["WF_PARENT_ELEMENT_ID"] == $arFields["ID"])
								)
							)
							{
								$arSoFields = Array(
									"=LOG_DATE" => (
										strlen($arFields["ACTIVE_FROM"]) > 0
										?
											(
												MakeTimeStamp($arFields["ACTIVE_FROM"], CSite::GetDateFormat("FULL", $site_id)) > time()
												?
													$GLOBALS["DB"]->CharToDateFunction($arFields["ACTIVE_FROM"], "FULL", $site_id)
												:
													$GLOBALS["DB"]->CurrentTimeFunction()
											)
										:
											$GLOBALS["DB"]->CurrentTimeFunction()
									),
									"=LOG_UPDATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
									"TITLE" => $arFields["NAME"],
									"MESSAGE" => (
										strlen($arFields["DETAIL_TEXT"]) > 0
										? ($arFields["DETAIL_TEXT_TYPE"] == "text" ? htmlspecialcharsbx($arFields["DETAIL_TEXT"]) : $arFields["DETAIL_TEXT"])
										: ($arFields["PREVIEW_TEXT_TYPE"] == "text" ? htmlspecialcharsbx($arFields["PREVIEW_TEXT"]) : $arFields["PREVIEW_TEXT"])
									)
								);

								$logID = CSocNetLog::Update($arLog["ID"], $arSoFields);
								if (intval($logID) > 0)
								{
									$rsRights = CSocNetLogRights::GetList(array(), array("LOG_ID" => $logID));
									$arRights = $rsRights->Fetch();
									if (!$arRights)
										CSocNetLogRights::Add($logID, "G2");

									CSocNetLog::SendEvent($logID, "SONET_NEW_EVENT");
								}
							}
							else
							{
								CSocNetLog::Delete($arLog["ID"]);
							}
						}
						else
						{
							if (
								$arFields["ACTIVE"] == "Y"
								&&
								(
									strlen($arFields["PREVIEW_TEXT"]) > 0
									|| strlen($arFields["DETAIL_TEXT"]) > 0
								)
								&&
								(
									!array_key_exists("WF", $arFields)
									|| $arFields["WF"] == "N"
									|| ($arFields["WF_STATUS_ID"] == 1 && $arFields["WF_PARENT_ELEMENT_ID"] == $arFields["ID"])
								)
							)
							{
								$dbIBlock = CIBlock::GetByID($arFields["IBLOCK_ID"]);
								if($arIBlock = $dbIBlock->Fetch())
								{
									$rsSite = CIBlock::GetSite($arFields["IBLOCK_ID"]);
									if ($arSite = $rsSite->Fetch())
										$site_id = $arSite["SITE_ID"];

									$val = COption::GetOptionString("intranet", "sonet_log_news_iblock", "", $site_id);
									if (strlen($val) > 0)
									{
										$arIBCode = unserialize($val);
										if (!is_array($arIBCode) || count($arIBCode) <= 0)
											$arIBCode = array();
									}
									else
										$arIBCode = array();

									if (in_array($arIBlock["CODE"], $arIBCode))
									{
										$entity_url = str_replace(
											"#SITE_DIR#",
											$arSite["DIR"],
											$arIBlock["LIST_PAGE_URL"]
										);
										if (strpos($entity_url, "/") === 0)
											$entity_url = "/".ltrim($entity_url, "/");

										$url = str_replace(
											array("#SITE_DIR#", "#ID#", "#CODE#"),
											array($arSite["DIR"], $arFields["ID"], $arFields["CODE"]),
											$arIBlock["DETAIL_PAGE_URL"]
										);
										if (strpos($url, "/") === 0)
											$url = "/".ltrim($url, "/");

										$val = COption::GetOptionString("intranet", "sonet_log_news_iblock_forum");
										if (strlen($val) > 0)
											$arIBlockForum = unserialize($val);
										else
											$arIBlockForum = array();

										$strMessage = (
											strlen($arFields["DETAIL_TEXT"]) > 0
											? ($arFields["DETAIL_TEXT_TYPE"] == "text" ? htmlspecialcharsbx($arFields["DETAIL_TEXT"]) : $arFields["DETAIL_TEXT"])
											: ($arFields["PREVIEW_TEXT_TYPE"] == "text" ? htmlspecialcharsbx($arFields["PREVIEW_TEXT"]) : $arFields["PREVIEW_TEXT"])
										);

										$dtFormatSite = (defined("ADMIN_SECTION") && ADMIN_SECTION===true ? SITE_ID : $site_id);
										$dtValue = (
											strlen($arFields["ACTIVE_FROM"]) > 0
											?
												(
													MakeTimeStamp($arFields["ACTIVE_FROM"], CSite::GetDateFormat("FULL", $dtFormatSite)) > time()
													?
														$GLOBALS["DB"]->CharToDateFunction($arFields["ACTIVE_FROM"], "FULL", $dtFormatSite)
													:
														$GLOBALS["DB"]->CurrentTimeFunction()
												)
											:
												$GLOBALS["DB"]->CurrentTimeFunction()
										);

										$arSoFields = Array(
											"SITE_ID" => $site_id,
											"ENTITY_TYPE" => SONET_SUBSCRIBE_ENTITY_NEWS,
											"ENTITY_ID" => $arIBlock["ID"],
											"EVENT_ID" => "news",
											"USER_ID" => $GLOBALS["USER"]->GetID(),
											"=LOG_DATE" => $dtValue,
											"=LOG_UPDATE" => $dtValue,
											"TITLE_TEMPLATE" => GetMessage("INTR_SOCNET_LOG_NEWS_TITLE"),
											"TITLE" => $arFields["NAME"],
											"MESSAGE" => $strMessage,
											"TEXT_MESSAGE" => "",
											"URL"	=> $url,
											"MODULE_ID" => "intranet",
											"CALLBACK_FUNC" => false,
											"TMP_ID" => false,
											"PARAMS" => serialize(array(
												"ENTITY_NAME" => $arIBlock["NAME"],
												"ENTITY_URL" => $entity_url
											)),
											"SOURCE_ID" => $arFields["ID"],
											"ENABLE_COMMENTS" => (array_key_exists($arIBlock["ID"], $arIBlockForum) ? "Y" : "N")
										);

										$logID = CSocNetLog::Add($arSoFields, false);
										if (intval($logID) > 0)
										{
											CSocNetLog::Update($logID, array("TMP_ID" => $logID));
											CSocNetLogRights::Add($logID, "G2");
											CSocNetLog::SendEvent($logID, "SONET_NEW_EVENT");
										}
									}
								}
							}
						}

						if (
							$logID > 0
							&& strlen($arFields["ACTIVE_TO"]) > 0
						)
						{
							$agent = "CIntranetEventHandlers::DeleteLogEntry(".$arFields["ID"].");";
							CAgent::RemoveAgent($agent, "intranet");
							CAgent::AddAgent($agent, "intranet", "N", 0, $arFields["ACTIVE_TO"], "Y", $arFields["ACTIVE_TO"]);
						}
					}
				}
			}
			// --news
		}
	}

	function DeleteLogEntry($elementID)
	{
		if (
			intval($elementID) > 0
			&& CModule::IncludeModule("socialnetwork")
		)
		{
			CSocNetAllowed::GetAllowedEntityTypes();

			$rsLog = CSocNetLog::GetList(
				array(),
				array(
					"ENTITY_TYPE" => SONET_SUBSCRIBE_ENTITY_NEWS,
					"EVENT_ID" => "news",
					"SOURCE_ID" => $elementID
				),
				false,
				false,
				array("ID")
			);
			if (
				($arLog = $rsLog->Fetch())
				&& intval($arLog["ID"]) > 0
			)
			{
				CSocNetLog::Delete($arLog["ID"]);
			}

			return "";
		}
	}

/*
	RegisterModuleDependences("iblock", "OnBeforeIBlockSectionUpdate", "intranet", "CIntranetEventHandlers", "OnBeforeIBlockSectionUpdate");
	RegisterModuleDependences("iblock", "OnBeforeIBlockSectionAdd", "intranet", "CIntranetEventHandlers", "OnBeforeIBlockSectionAdd");
*/
	function OnBeforeIBlockSectionAdd($arParams)
	{
		if (
			$arParams['IBLOCK_ID'] == COption::GetOptionInt('intranet', 'iblock_structure', 0)
			&& (
				!array_key_exists("IBLOCK_SECTION_ID", $arParams)
				|| count($arParams['IBLOCK_SECTION_ID']) <= 0
				|| $arParams['IBLOCK_SECTION_ID'] <= 0
			)
		)
		{
			$dbRes = CIBlockSection::GetList(array(), array('IBLOCK_ID' => $arParams['IBLOCK_ID'], 'SECTION_ID' => 0));
			if ($dbRes->Fetch())
			{
				$GLOBALS['APPLICATION']->ThrowException(GetMessage('INTR_IBLOCK_TOP_SECTION_WARNING'));
				return false;
			}
		}
	}

	function OnBeforeIBlockSectionUpdate($arParams)
	{
		if (
			$arParams['IBLOCK_ID'] == COption::GetOptionInt('intranet', 'iblock_structure', 0)
			&& array_key_exists("IBLOCK_SECTION_ID", $arParams)
			&& (
				count($arParams['IBLOCK_SECTION_ID']) <= 0
				|| $arParams['IBLOCK_SECTION_ID'] <= 0
			)
		)
		{
			$dbRes = CIBlockSection::GetList(array(), array('IBLOCK_ID' => $arParams['IBLOCK_ID'], '!ID' => $arParams['ID'], 'SECTION_ID' => 0));
			if ($dbRes->Fetch())
			{
				$GLOBALS['APPLICATION']->ThrowException(GetMessage('INTR_IBLOCK_TOP_SECTION_WARNING'));
				return false;
			}
		}
	}

	function onAfterForumMessageAdd($ID, $arForumMessage, $arTopicInfo, $arForumInfo, $arFields)
	{
		// add log comment
		if (
			array_key_exists("ADD_TO_LOG", $arFields)
			&& $arFields["ADD_TO_LOG"] == "N"
		)
		{
			return;
		}

		if (
			array_key_exists("NEW_TOPIC", $arFields)
			&& $arFields["NEW_TOPIC"] == "Y"
		)
		{
			return;
		}

		if (
			!array_key_exists("TOPIC_INFO", $arForumMessage)
			|| !is_array($arForumMessage["TOPIC_INFO"])
			|| !array_key_exists("XML_ID", $arForumMessage["TOPIC_INFO"])
			|| empty($arForumMessage["TOPIC_INFO"]["XML_ID"])
			|| strpos($arForumMessage["TOPIC_INFO"]["XML_ID"], "IBLOCK_") !== 0
		)
		{
			return;
		}

		$val = COption::GetOptionString("intranet", "sonet_log_news_iblock_forum");
		$arIBlockForum = (strlen($val) > 0 ? unserialize($val) : array());

		if (
			CModule::IncludeModule("socialnetwork")
			&& in_array($arFields["FORUM_ID"], $arIBlockForum)
			&& array_key_exists("PARAM2", $arFields)
			&& intval($arFields["PARAM2"]) > 0
		)
		{
			CSocNetAllowed::GetAllowedEntityTypes();

			$dbRes = CSocNetLog::GetList(
				array("ID" => "DESC"),
				array(
					"EVENT_ID"	=> "news",
					"SOURCE_ID"	=> $arFields["PARAM2"] // file element id
				),
				false,
				false,
				array("ID", "ENTITY_TYPE", "ENTITY_ID", "TMP_ID")
			);

			if ($arRes = $dbRes->Fetch())
			{
				$log_id = $arRes["ID"];
				$entity_id = $arRes["ENTITY_ID"];

				$arForum = CForumNew::GetByID($arFields["FORUM_ID"]);

				$parser = new textParser(LANGUAGE_ID); // second parameter - path to smile!
				$parser->image_params["width"] = false;
				$parser->image_params["height"] = false;

				$arAllow = array(
					"HTML" => "N",
					"ANCHOR" => "N",
					"BIU" => "N",
					"IMG" => "N",
					"LIST" => "N",
					"QUOTE" => "N",
					"CODE" => "N",
					"FONT" => "N",
					"UPLOAD" => $arForum["ALLOW_UPLOAD"],
					"NL2BR" => "N",
					"VIDEO" => "N",
					"SMILES" => "N"
				);

				$arMessage = CForumMessage::GetByIDEx($ID);

				$url = CComponentEngine::MakePathFromTemplate(
					$arParams["~URL_TEMPLATES_MESSAGE"],
					array(
						"FID" => $arMessage["FORUM_ID"], 
						"TID" => $arMessage["TOPIC_ID"], 
						"MID" => $ID
					)
				);

				$arFieldsForSocnet = array(
					"ENTITY_TYPE" => SONET_SUBSCRIBE_ENTITY_NEWS,
					"ENTITY_ID" => $entity_id,
					"EVENT_ID" => "news_comment",
					"=LOG_DATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
					"MESSAGE" => $parser->convert($arFields["POST_MESSAGE"], $arAllow),
					"TEXT_MESSAGE" => $parser->convert4mail($arFields["POST_MESSAGE"]),
					"URL" => $url,
					"MODULE_ID" => false,
					"SOURCE_ID" => $ID,
					"LOG_ID" => $log_id,
					"RATING_TYPE_ID" => "FORUM_POST",
					"RATING_ENTITY_ID" => $ID
				);

				if (intVal($arMessage["AUTHOR_ID"]) > 0)
				{
					$arFieldsForSocnet["USER_ID"] = $arMessage["AUTHOR_ID"];
				}

				$comment_id = CSocNetLogComments::Add($arFieldsForSocnet, false, false);
				CSocNetLog::CounterIncrement($comment_id, false, false, "LC");
			}
		}
	}

	function onAfterForumMessageDelete($ID, $arFields)
	{
		$val = COption::GetOptionString("intranet", "sonet_log_news_iblock_forum");
		if (strlen($val) > 0)
			$arIBlockForum = unserialize($val);
		else
			$arIBlockForum = array();

		if (
			CModule::IncludeModule("socialnetwork")
			&& in_array($arFields["FORUM_ID"], $arIBlockForum)
		)
		{
			$dbRes = CSocNetLogComments::GetList(
				array("ID" => "DESC"),
				array(
					"EVENT_ID" => "news_comment",
					"SOURCE_ID" => $ID
				),
				false,
				false,
				array("ID")
			);

			if ($arRes = $dbRes->Fetch())
				CSocNetLogComments::Delete($arRes["ID"]);
		}
	}

	function AddComment_News($arFields)
	{
		if (!CModule::IncludeModule("forum"))
			return false;

		if (!CModule::IncludeModule("iblock"))
			return false;

		if (!CModule::IncludeModule("socialnetwork"))
			return false;

		$dbResult = CSocNetLog::GetList(
			array("ID" => "DESC"),
			array("TMP_ID" => $arFields["LOG_ID"]),
			false,
			false,
			array("ID", "SOURCE_ID", "PARAMS")
		);

		$bFound = false;
		if ($arLog = $dbResult->Fetch())
		{
			if (intval($arLog["SOURCE_ID"]) > 0)
			{
				$arFilter = array("ID" => $arLog["SOURCE_ID"]);
				$arSelectedFields = array("IBLOCK_ID", "ID", "CREATED_BY", "NAME", "PROPERTY_FORUM_TOPIC_ID", "PROPERTY_FORUM_MESSAGE_CNT");
				$db_res = CIBlockElement::GetList(array(), $arFilter, false, false, $arSelectedFields);
				if ($db_res && $res = $db_res->GetNext())
				{
					$arElement = $res;

					$val = COption::GetOptionString("intranet", "sonet_log_news_iblock_forum");
					if (strlen($val) > 0)
						$arIBlockForum = unserialize($val);
					else
						$arIBlockForum = array();

					if (array_key_exists($arElement["IBLOCK_ID"], $arIBlockForum))
						$FORUM_ID = $arIBlockForum[$arElement["IBLOCK_ID"]];

					if (intval($FORUM_ID) > 0)
					{
						CSocNetLogTools::AddComment_Review_CheckIBlock($arElement);

						$dbMessage = CForumMessage::GetList(
							array(),
							array("PARAM2" => $arElement["ID"])
						);

						if (!$arMessage = $dbMessage->Fetch())
						{
							// Add Topic
							$TOPIC_ID = CSocNetLogTools::AddComment_Review_CreateRoot($arElement, $FORUM_ID);
							$bNewTopic = true;
						}
						else
							$TOPIC_ID = $arMessage["TOPIC_ID"];

						if(intval($TOPIC_ID) > 0)
						{
							// Add comment
							$messageID = false;

							$bError = false;
							if (CForumMessage::CanUserAddMessage($TOPIC_ID, $GLOBALS["USER"]->GetUserGroupArray(), $GLOBALS["USER"]->GetID(), false))
							{
								$bSHOW_NAME = true;
								$res = CForumUser::GetByUSER_ID($GLOBALS["USER"]->GetID());
								if ($res)
									$bSHOW_NAME = ($res["SHOW_NAME"]=="Y");

								if ($bSHOW_NAME)
									$AUTHOR_NAME = $GLOBALS["USER"]->GetFullName();

								if (strlen(Trim($AUTHOR_NAME))<=0)
									$AUTHOR_NAME = $GLOBALS["USER"]->GetLogin();

								if (strlen($AUTHOR_NAME)<=0)
									$bError = true;
							}

							if (!$bError)
							{
								$arFieldsMessage = Array(
									"POST_MESSAGE" => $arFields["TEXT_MESSAGE"],
									"USE_SMILES" => "Y",
									"APPROVED" => "Y",
									"PARAM2" => $arElement["ID"],
									"AUTHOR_NAME" => $AUTHOR_NAME,
									"AUTHOR_ID" => IntVal($GLOBALS["USER"]->GetParam("USER_ID")),
									"FORUM_ID" => $FORUM_ID,
									"TOPIC_ID" => $TOPIC_ID,
									"NEW_TOPIC" => "N",
									"GUEST_ID" => $_SESSION["SESS_GUEST_ID"],
									"ADD_TO_LOG" => "N"
								);

								$AUTHOR_IP = ForumGetRealIP();
								$AUTHOR_IP_tmp = $AUTHOR_IP;
								$AUTHOR_REAL_IP = $_SERVER['REMOTE_ADDR'];
								if (COption::GetOptionString("forum", "FORUM_GETHOSTBYADDR", "N") == "Y")
								{
									$AUTHOR_IP = @gethostbyaddr($AUTHOR_IP);
									if ($AUTHOR_IP_tmp==$AUTHOR_REAL_IP)
										$AUTHOR_REAL_IP = $AUTHOR_IP;
									else
										$AUTHOR_REAL_IP = @gethostbyaddr($AUTHOR_REAL_IP);
								}

								$arFieldsMessage["AUTHOR_IP"] = ($AUTHOR_IP!==False) ? $AUTHOR_IP : "<no address>";
								$arFieldsMessage["AUTHOR_REAL_IP"] = ($AUTHOR_REAL_IP!==False) ? $AUTHOR_REAL_IP : "<no address>";

								$messageID = CForumMessage::Add($arFieldsMessage, false);
								if (intVal($messageID)<=0)
									$bError = true;
								else
								{
									if (CModule::IncludeModule("statistic"))
									{
										$arForum = CForumNew::GetByID($FORUM_ID);
										$F_EVENT1 = $arForum["EVENT1"];
										$F_EVENT2 = $arForum["EVENT2"];
										$F_EVENT3 = $arForum["EVENT3"];
										if (strlen($F_EVENT3)<=0)
										{
											$arForumSite_tmp = CForumNew::GetSites($FORUM_ID);
											$F_EVENT3 = CForumNew::PreparePath2Message($arForumSite_tmp[SITE_ID], array("FORUM_ID"=>$FORUM_ID, "TOPIC_ID"=>$TOPIC_ID, "MESSAGE_ID"=>$messageID));
										}
										CStatistic::Set_Event($F_EVENT1, $F_EVENT2, $F_EVENT3);
									}
									CForumMessage::SendMailMessage($messageID, array(), false, "NEW_FORUM_MESSAGE");
									CSocNetLogTools::AddComment_Review_UpdateElement($arElement, $TOPIC_ID, $bNewTopic);
								}
							}
						}
					}
				}
			}
		}

		if (intval($messageID) <= 0)
			$strError = GetMessage("SONET_ADD_COMMENT_SOURCE_ERROR");

		return array(
				"SOURCE_ID"	=> $messageID,
				"RATING_TYPE_ID" => "FORUM_POST",
				"RATING_ENTITY_ID" => $messageID,
				"ERROR"		=> $strError,
				"NOTES"		=> ""
			);
	}

	function OnAfterIBlockElementDelete($arFields)
	{
		// news
		if (
			!array_key_exists("WF_STATUS_ID", $arFields)
			|| $arFields["WF_STATUS_ID"] == 1
		)
		{
			$dbIBlock = CIBlock::GetByID($arFields["IBLOCK_ID"]);
			if($arIBlock = $dbIBlock->Fetch())
			{
				$rsSite = CIBlock::GetSite($arFields["IBLOCK_ID"]);
				if ($arSite = $rsSite->Fetch())
					$site_id = $arSite["SITE_ID"];

				$val = COption::GetOptionString("intranet", "sonet_log_news_iblock", "", $site_id);
				if (strlen($val) > 0)
				{
					$arIBCode = unserialize($val);
					if (!is_array($arIBCode) || count($arIBCode) <= 0)
						$arIBCode = array();
				}
				else
					$arIBCode = array();

				if (
					in_array($arIBlock["CODE"], $arIBCode)
					&& CModule::IncludeModule("socialnetwork")
				)
				{
					CSocNetAllowed::GetAllowedEntityTypes();

					$dbRes = CSocNetLog::GetList(
						array("ID" => "DESC"),
						array(
							"ENTITY_TYPE" => SONET_SUBSCRIBE_ENTITY_NEWS,
							"EVENT_ID" => "news",
							"SOURCE_ID" => $arFields["ID"]
						),
						false,
						false,
						array("ID")
					);
					while ($arRes = $dbRes->Fetch())
						CSocNetLog::Delete($arRes["ID"]);
				}
			}
		}
		// --news
	}

	function OnUserDelete($USER_ID)
	{
		$arIBlockList = array('iblock_absence', 'iblock_honour', 'iblock_state_history');

		foreach ($arIBlockList as $var_name)
		{
			$IBLOCK_ID = COption::GetOptionInt('intranet', $var_name);
			if ($IBLOCK_ID > 0)
			{
					$dbRes = CIBlockElement::GetList(array(), array(
						'IBLOCK_ID' => $IBLOCK_ID,
						'PROPERTY_USER' => $USER_ID,
					),
					false,
					false,
					array('ID', 'IBLOCK_ID')
				);

				while ($arRes = $dbRes->Fetch())
				{
					CIBlockElement::Delete($arRes['ID']);
				}
			}
		}

		if (CModule::IncludeModule('socialnetwork'))
		{
			$dbRes = CSocNetLog::GetList(array(), array(
				'ENTITY_TYPE' => SONET_INTRANET_NEW_USER_ENTITY,
				'EVENT_ID' => SONET_INTRANET_NEW_USER_EVENT_ID,
				'ENTITY_ID' => $USER_ID,
				'SOURCE_ID' => $USER_ID,
			), false, array('ID'));

			$arRes = $dbRes->Fetch();
			if ($arRes)
			{
				CSocNetLog::Delete($arRes['ID']);
			}
		}

		if (CModule::IncludeModule('iblock'))
		{
			$IBLOCK_ID = COption::GetOptionInt('intranet', 'iblock_structure');
			if ($IBLOCK_ID > 0)
			{
				$dbRes = CIBlockSection::GetList(array(), array('IBLOCK_ID' => $IBLOCK_ID, 'UF_HEAD' => $USER_ID), false, array('ID', 'IBLOCK_ID'));
				$obSection = new CIBlockSection();
				while ($arRes = $dbRes->Fetch())
				{
					$obSection->Update($arRes['ID'], array('UF_HEAD' => ''));
				}
			}
		}
	}

	function OnAfterUserInitialize($userId)
	{
		if (!IsModuleInstalled('bitrix24'))
		{
			$dbUser = CUser::GetByID($userId);
			if ($arUser = $dbUser->Fetch())
			{
				CIntranetEventHandlers::OnAfterUserAdd($arUser);
			}
		}
	}

	function OnAfterUserAdd($arUser)
	{
		if ($arUser['ID'] > 0 && is_array($arUser['UF_DEPARTMENT']) && $arUser['UF_DEPARTMENT'][0] && $arUser['ACTIVE'] == 'Y'
			&& !defined('INTR_SKIP_EVENT_ADD')
			&& ($IBLOCK_ID = COption::GetOptionInt('intranet', 'iblock_state_history', ''))
		)
		{
			static $ACCEPTED_ENUM_ID = null;

			if (null == $ACCEPTED_ENUM_ID)
			{
				$dbRes = CIBlockPropertyEnum::GetList(
					array('id' => 'asc'),
					array(
						'IBLOCK_ID' => $IBLOCK_ID,
						'CODE' => 'STATE',
						'XML_ID' => 'ACCEPTED'
					)
				);

				if ($arRes = $dbRes->Fetch())
				{
					$ACCEPTED_ENUM_ID = $arRes['ID'];
				}
			}

			$arFields = array(
				'IBLOCK_ID' => $IBLOCK_ID,
				'NAME' => GetMessage('INTR_HIRED').' - '.trim($arUser['LAST_NAME'].' '.$arUser['NAME']),
				'ACTIVE' => 'Y',
				'DATE_ACTIVE_FROM' => ConvertTimeStamp(),
				'PREVIEW_TEXT' => GetMessage('INTR_HIRED'),

				'PROPERTY_VALUES' => array(
					'USER' => $arUser['ID'],
					'DEPARTMENT' => $arUser['UF_DEPARTMENT'],
					'POST' => $arUser['WORK_POSITION'] ? $arUser['WORK_POSITION'] : $arUser['PERSONAL_PROFESSION'],
					'STATE' => $ACCEPTED_ENUM_ID
				),
			);

			$obIB = new CIBlockElement();
			$obIB->Add($arFields);

			if (!IsModuleInstalled('bitrix24'))
			{
				CIntranetNotify::NewUserMessage($arUser['ID']);
			}
		}
	}

	function OnFillSocNetAllowedSubscribeEntityTypes(&$arSocNetAllowedSubscribeEntityTypes)
	{
		define("SONET_SUBSCRIBE_ENTITY_NEWS", "N");
		$arSocNetAllowedSubscribeEntityTypes[] = SONET_SUBSCRIBE_ENTITY_NEWS;

		global $arSocNetAllowedSubscribeEntityTypesDesc;
		$arSocNetAllowedSubscribeEntityTypesDesc[SONET_SUBSCRIBE_ENTITY_NEWS] = array(
			"TITLE_LIST" => GetMessage("INTR_SOCNET_LOG_LIST_N_ALL"),
			"TITLE_ENTITY" => GetMessage("INTR_SOCNET_LOG_N"),
			"TITLE_ENTITY_XDI" => GetMessage("INTR_SOCNET_LOG_XDI_N"),
			"CLASS_DESC" => "",
			"METHOD_DESC" => "",
			"CLASS_DESC_GET" => "CIntranetUtils",
			"METHOD_DESC_GET" => "GetIBlockByID",
			"CLASS_DESC_SHOW" => "CIntranetUtils",
			"METHOD_DESC_SHOW" => "ShowIBlockByID",
			"XDIMPORT_ALLOWED" => "Y"
		);
	}

	function OnFillSocNetLogEvents(&$arSocNetLogEvents)
	{
		$arSocNetLogEvents["news"] = array(
			"ENTITIES" =>	array(
				SONET_SUBSCRIBE_ENTITY_NEWS => array(
					"TITLE" => GetMessage("INTR_SOCNET_LOG_NEWS"),
					"TITLE_SETTINGS" => GetMessage("INTR_SOCNET_LOG_NEWS_SETTINGS"),
					"TITLE_SETTINGS_1" => GetMessage("INTR_SOCNET_LOG_NEWS_SETTINGS_1"),
					"TITLE_SETTINGS_2" => GetMessage("INTR_SOCNET_LOG_NEWS_SETTINGS_2"),
				),
			),
			"CLASS_FORMAT" => "CIntranetEventHandlers",
			"METHOD_FORMAT" => "FormatEvent_News",
			"FULL_SET" => array("news", "news_comment"),
			"COMMENT_EVENT" => array(
				"EVENT_ID" => "news_comment",
				"CLASS_FORMAT" => "CIntranetEventHandlers",
				"METHOD_FORMAT" => "FormatComment_News",
				"ADD_CALLBACK" => array("CIntranetEventHandlers", "AddComment_News"),
				"UPDATE_CALLBACK" => array("CSocNetLogTools", "UpdateComment_Forum"),
				"DELETE_CALLBACK" => array("CSocNetLogTools", "DeleteComment_Forum"),
			),
			"XDIMPORT_ALLOWED" => "Y"
		);
	}

/* clear cache handlers */

/*
RegisterModuleDependences('main', 'onUserDelete', 'intranet', 'CIntranetEventHandlers', 'ClearAllUsersCache');
RegisterModuleDependences('main', 'onAfterUserAdd', 'intranet', 'CIntranetEventHandlers', 'ClearAllUsersCache');

clear single user cache if it's deleted and clear whole users cache
*/
	function ClearAllUsersCache($ID = false)
	{
		if (!defined('BX_COMP_MANAGED_CACHE') || !BX_COMP_MANAGED_CACHE) return true;

		global $CACHE_MANAGER;
		if ($ID && !is_array($ID)) $CACHE_MANAGER->ClearByTag("intranet_user_".$ID);
		$CACHE_MANAGER->ClearByTag("intranet_users");
		return true;
	}

/*
RegisterModuleDependences('main', 'onBeforeUserUpdate', 'intranet', 'CIntranetEventHandlers', 'ClearSingleUserCache');

clear single user cache and clear all users cache in case of change user's activity
*/
/*
TODO: what do we should check in case of user's departments change? variant: if they're changed - use both $CACHE_MANAGER->ClearByTag('iblock_id_'.$old_dept) and $CACHE_MANAGER->ClearByTag('iblock_id_'.$new_dept)
*/
	function ClearSingleUserCache($arFields)
	{
		if (!defined('BX_COMP_MANAGED_CACHE') || !BX_COMP_MANAGED_CACHE) return true;

		global $CACHE_MANAGER;

		$dbRes = CUser::GetList(
			$by="id", $order="asc",
			array("ID_EQUAL_EXACT" => intval($arFields['ID'])),
			array('SELECT' => array('UF_DEPARTMENT'))
		);

		$arRecacheFields = array('ACTIVE', 'LAST_NAME');

		$bRecache = false;
		if ($arOldFields = $dbRes->Fetch())
		{
			if (
				isset($arFields['PERSONAL_BIRTHDAY'])
				&& $arOldFields['PERSONAL_BIRTHDAY'] != $arFields['PERSONAL_BIRTHDAY']
			)
				$CACHE_MANAGER->ClearByTag("intranet_birthday");

			if (
				isset($arFields['UF_DEPARTMENT'])
				&& is_array($arFields['UF_DEPARTMENT'])
				&& $arFields['UF_DEPARTMENT'] != $arOldFields['UF_DEPARTMENT']
			)
			{
				if (!is_array($arOldFields['UF_DEPARTMENT']))
					$arOldFields['UF_DEPARTMENT'] = array();

				$arDepts = array_diff($arFields['UF_DEPARTMENT'], $arOldFields['UF_DEPARTMENT']);
				if(count($arDepts) > 0)
				{
					$CACHE_MANAGER->ClearByTag('intranet_department_structure');

					foreach ($arDepts as $dpt)
					{
						$CACHE_MANAGER->ClearByTag('intranet_department_'.$dpt);
					}
				}
			}

			foreach ($arRecacheFields as $fld)
			{
				if (isset($arFields[$fld]) && $arOldFields[$fld] != $arFields[$fld])
				{
					$bRecache = true;
					break;
				}
			}
		}

		if (\CHTMLPagesCache::IsOn())
		{
			\Bitrix\Intranet\Composite\CacheProvider::deleteUserCache(intval($arFields['ID']));
		}

		if ($bRecache)
			CIntranetEventHandlers::ClearAllUsersCache($arFields['ID']);
		else
			$CACHE_MANAGER->ClearByTag("intranet_user_".$arFields['ID']);

		return true;
	}

/*
RegisterModuleDependences('iblock', 'OnAfterIBlockSectionUpdate', 'intranet', 'CIntranetEventHandlers', 'ClearDepartmentCache');
*/
	function ClearDepartmentCache($arFields)
	{
		if (!defined('BX_COMP_MANAGED_CACHE') || !BX_COMP_MANAGED_CACHE) return true;

		if (COption::GetOptionString('intranet', 'iblock_structure', '') == $arFields['IBLOCK_ID'])
		{
			$GLOBALS['CACHE_MANAGER']->ClearByTag('intranet_department_'.$arFields['ID']);
		}
	}

/*
RegisterModuleDependences('main', 'OnBeforeProlog', 'intranet', 'CIntranetEventHandlers', 'OnCreatePanel');
*/
	function OnCreatePanel()
	{
		if(defined("ADMIN_SECTION") && ADMIN_SECTION == true)
			return;

		if($GLOBALS["USER"]->IsAdmin())
		{
			$hint = GetMessage('INTR_SET_BUT_HINT');
			$arMenu = Array(
				Array(
					"ACTION" => "jsUtils.Redirect([], '".CUtil::JSEscape("/bitrix/admin/wizard_install.php?lang=".LANGUAGE_ID."&wizardName=bitrix:portal&wizardSiteID=".SITE_ID."&".bitrix_sessid_get())."');",
					"ICON" => "wizard",
					"TITLE" => GetMessage('INTR_SET_WIZ_TITLE'),
					"TEXT" => GetMessage('INTR_SET_WIZ_TEXT'),
					"DEFAULT" => true,
				),
			);

			if(IsModuleInstalled('extranet'))
			{
				$hint .= GetMessage('INTR_SET_BUT_HINT_EXTRANET');
				$arMenu[] = Array(
					"ACTION" => "jsUtils.Redirect([], '".CUtil::JSEscape("/bitrix/admin/wizard_install.php?lang=".LANGUAGE_ID."&wizardName=bitrix:extranet&".bitrix_sessid_get())."');",
					"ICON" => "wizard",
					"TITLE" => GetMessage('INTR_SET_EXT_TITLE'),
					"TEXT" => GetMessage('INTR_SET_EXT_TEXT'),
				);
			}
			if(COption::GetOptionString("main", "wizard_clear_exec", "N", SITE_ID) <> "Y")
			{
				$hint .= GetMessage('INTR_SET_BUT_HINT_CLEARING');
				$arMenu[] = Array(
					"ACTION" => "jsUtils.Redirect([], '".CUtil::JSEscape("/bitrix/admin/wizard_install.php?lang=".LANGUAGE_ID."&wizardSiteID=".SITE_ID."&wizardName=bitrix:portal_clear&".bitrix_sessid_get())."');",
					"ICON" => "wizard-clear",
					"TITLE" => GetMessage('INTR_SET_CLEAN_TITLE'),
					"TEXT" => GetMessage('INTR_SET_CLEAN_TEXT'),
				);
			}

			$arButton = array(
				"HREF" => "/bitrix/admin/wizard_install.php?lang=".LANGUAGE_ID."&wizardName=bitrix:portal&wizardSiteID=".SITE_ID."&".bitrix_sessid_get(),
				"ID" => "portal_wizard",
				"ICON" => "bx-panel-site-wizard-icon",
				"ALT" => GetMessage('INTR_SET_BUT_TITLE'),
				"TEXT" => GetMessage('INTR_SET_BUT_TEXT'),
				"MAIN_SORT" => 2500,
				"TYPE" => "BIG",
				"SORT" => 10,
				"MENU" => (count($arMenu) > 1? $arMenu : array()),
				"HINT" => array(
					"TITLE" => str_replace('#BR#', ' ', GetMessage('INTR_SET_BUT_TEXT')),
					"TEXT" => $hint
				)
			);

			/*
			$HINT_ID = "PORTAL_WIZARD_INSTALL";
			$hintOptions = CUtil::GetPopupOptions($HINT_ID);

			if($hintOptions['display'] != 'off')
			{
				$arButton['HINT']['ID'] = $HINT_ID;
			}*/

			$GLOBALS["APPLICATION"]->AddPanelButton($arButton);
		}
	}

	function GetEntity_News($arFields, $bMail)
	{
		$arEntity = array();

		$arEventParams = unserialize(strlen($arFields["~PARAMS"]) > 0 ? $arFields["~PARAMS"] : $arFields["PARAMS"]);

		if (intval($arFields["ENTITY_ID"]) > 0)
		{
			if (
				is_array($arEventParams)
				&& count($arEventParams) > 0
				&& array_key_exists("ENTITY_NAME", $arEventParams)
				&& strlen($arEventParams["ENTITY_NAME"]) > 0
			)
			{
				if (
					!$bMail
					&& array_key_exists("ENTITY_URL", $arEventParams)
					&& strlen($arEventParams["ENTITY_URL"]) > 0
				)
				{
					$arSocNetAllowedSubscribeEntityTypesDesc = CSocNetAllowed::GetAllowedEntityTypesDesc();
					$arEntity["FORMATTED"]["TYPE_NAME"] = $arSocNetAllowedSubscribeEntityTypesDesc[$arFields["ENTITY_TYPE"]]["TITLE_ENTITY"];
					$arEntity["FORMATTED"]["URL"] = $arEventParams["ENTITY_URL"];
					$arEntity["FORMATTED"]["NAME"] = $arEventParams["ENTITY_NAME"];
				}
				elseif(!$bMail)
					$arEntity["FORMATTED"]["NAME"] = $arEventParams["ENTITY_NAME"];
				else
				{
					$arEntity["FORMATTED"] = $arEventParams["ENTITY_NAME"];
					$arEntity["TYPE_MAIL"] = GetMessage("INTR_SOCNET_LOG_ENTITY_MAIL");
				}
			}
		}

		return $arEntity;
	}

	function FormatEvent_News($arFields, $arParams, $bMail = false)
	{
		$GLOBALS["APPLICATION"]->SetAdditionalCSS("/bitrix/themes/.default/intranet_sonet_log.css");

		$arResult = array(
			"EVENT" => $arFields,
			"ENTITY" => CIntranetEventHandlers::GetEntity_News($arFields, $bMail),
			"URL" => "",
			"CACHED_CSS_PATH" => "/bitrix/themes/.default/intranet_sonet_log.css"
		);

		if (!CModule::IncludeModule("socialnetwork"))
			return $arResult;

		$title = "";
		if (strlen($arFields["TITLE_TEMPLATE"]) > 0)
		{

			if (!$bMail && strlen($arFields["URL"]) > 0)
				$title_tmp = '<a href="'.$arFields["URL"].'">'.$arFields["TITLE"].'</a>';
			else
				$title_tmp = $arFields["TITLE"];

			$title = str_replace(
				array("#TITLE#", "#ENTITY#"),
				array($title_tmp, ($bMail ? $arResult["ENTITY"]["FORMATTED"] : $arResult["ENTITY"]["FORMATTED"]["NAME"])),
				($bMail ? GetMessage("INTR_SOCNET_LOG_NEWS_TITLE_MAIL") : GetMessage("INTR_SOCNET_LOG_NEWS_TITLE"))
			);
		}
		else
			$title_tmp = "";

		$url = false;

		if (
			strlen($arFields["URL"]) > 0
			&& strlen($arFields["SITE_ID"]) > 0
		)
		{
			if (substr($arFields["URL"], 0, 1) === "/")
			{
				$rsSites = CSite::GetByID($arFields["SITE_ID"]);
				$arSite = $rsSites->Fetch();

				if (strlen($arSite["SERVER_NAME"]) > 0)
					$server_name = $arSite["SERVER_NAME"];
				else
					$server_name = COption::GetOptionString("main", "server_name", $GLOBALS["SERVER_NAME"]);

				$protocol = (CMain::IsHTTPS() ? "https" : "http");
				$url = $protocol."://".$server_name.$arFields["URL"];
			}
			else
			{
				$url = $arFields["URL"];
			}
		}

		$arResult["EVENT_FORMATTED"] = array(
			"TITLE" => $title,
			"MESSAGE" => ($bMail ? CSocNetTextParser::killAllTags($arFields["MESSAGE"]) : $arFields["MESSAGE"]),
			"IS_IMPORTANT" => true,
			"TITLE_24" => GetMessage("INTR_SONET_LOG_DATA_TITLE_IMPORTANT_24"),
			"TITLE_24_2" => $arFields["TITLE"],
			"STYLE" => "imp-post",
		);

		if ($arParams["MOBILE"] == "Y")
		{
			$arResult["EVENT_FORMATTED"]["STYLE"] = "item-top-text-important";
			$arResult["EVENT_FORMATTED"]["AVATAR_STYLE"] = "avatar-info";
		}
		else
			$arResult["EVENT_FORMATTED"]["STYLE"] = "info";

		if (strlen($url) > 0)
			$arResult["EVENT_FORMATTED"]["URL"] = $url;

		if (!$bMail)
		{
			if (
				intval($arFields["SOURCE_ID"]) > 0
				&& CModule::IncludeModule("iblock")
			)
			{
				$rsIBlockElement = CIBlockElement::GetList(
					array(),
					array("ID" => $arFields["SOURCE_ID"]),
					false,
					false,
					array("ID", "DETAIL_TEXT")
				);
				if ($arIBlockElement = $rsIBlockElement->GetNext())
				{
					$arResult["EVENT_FORMATTED"]["MESSAGE"] = $arIBlockElement["DETAIL_TEXT"];
				}
			}

			if (
				$arParams["MOBILE"] != "Y"
				&& $arParams["NEW_TEMPLATE"] != "Y"
			)
			{
				$parserLog = new logTextParser(false, $arParams["PATH_TO_SMILE"]);
				$arAllow = array("HTML" => "Y", "ANCHOR" => "Y", "BIU" => "Y", "IMG" => "Y", "QUOTE" => "Y", "CODE" => "Y", "FONT" => "Y", "LIST" => "Y", "SMILES" => "Y", "NL2BR" => "N", "MULTIPLE_BR" => "N", "VIDEO" => "Y", "LOG_VIDEO" => "N");
				$arResult["EVENT_FORMATTED"]["SHORT_MESSAGE"] = $parserLog->html_cut(
					$parserLog->convert(htmlspecialcharsback(str_replace("#CUT#",	"", $arResult["EVENT_FORMATTED"]["MESSAGE"])), array(), $arAllow),
					1000
				);
				$arResult["EVENT_FORMATTED"]["IS_MESSAGE_SHORT"] = CSocNetLogTools::FormatEvent_IsMessageShort($arResult["EVENT_FORMATTED"]["MESSAGE"], $arResult["EVENT_FORMATTED"]["SHORT_MESSAGE"]);
			}

			if ($arParams["MOBILE"] != "Y")
			{
				$rsRight = CSocNetLogRights::GetList(array(), array("LOG_ID" => $arFields["ID"]));
				$arRights = array();
				while ($arRight = $rsRight->Fetch())
					$arRights[] = $arRight["GROUP_CODE"];
				$arResult["EVENT_FORMATTED"]["DESTINATION"] = CSocNetLogTools::FormatDestinationFromRights($arRights, $arParams);
			}
		}

		$arResult["HAS_COMMENTS"] = (intval($arFields["SOURCE_ID"]) > 0 ? "Y" : "N");

		return $arResult;
	}

	function FormatComment_News($arFields, $arParams, $bMail = false, $arLog = array())
	{
		$arResult = array(
				"EVENT_FORMATTED"	=> array(),
			);

		if (!CModule::IncludeModule("socialnetwork"))
			return $arResult;

		if ($bMail)
		{
			$arResult["CREATED_BY"] = CSocNetLogTools::FormatEvent_GetCreatedBy($arFields, $arParams, $bMail);
			$arResult["ENTITY"] = CIntranetEventHandlers::GetEntity_News($arLog, $bMail);
		}
		elseif($arParams["USE_COMMENT"] != "Y")
		{
			$arLog["ENTITY_ID"] = $arFields["ENTITY_ID"];
			$arLog["ENTITY_TYPE"] = $arFields["ENTITY_TYPE"];
			$arResult["ENTITY"] = CIntranetEventHandlers::GetEntity_News($arLog, false);
		}

		if (
			!$bMail
			&& array_key_exists("URL", $arLog)
			&& strlen($arLog["URL"]) > 0
		)
			$news_tmp = '<a href="'.$arLog["URL"].'">'.$arLog["TITLE"].'</a>';
		else
			$news_tmp = $arLog["TITLE"];

		$title_tmp = ($bMail ? GetMessage("INTR_SOCNET_LOG_NEWS_COMMENT_TITLE_MAIL") : GetMessage("INTR_SOCNET_LOG_NEWS_COMMENT_TITLE"));

		$title = str_replace(
			array("#TITLE#", "#ENTITY#", "#CREATED_BY#"),
			array($news_tmp, $arResult["ENTITY"]["FORMATTED"], ($bMail ? $arResult["CREATED_BY"]["FORMATTED"] : "")),
			$title_tmp
		);

		$arResult["EVENT_FORMATTED"] = array(
			"TITLE" => ($bMail || $arParams["USE_COMMENT"] != "Y" ? $title : ""),
			"MESSAGE" => ($bMail ? $arFields["TEXT_MESSAGE"] : $arFields["MESSAGE"])
		);

		if ($bMail)
		{
			$url = CSocNetLogTools::FormatEvent_GetURL($arLog);
			if (strlen($url) > 0)
				$arResult["EVENT_FORMATTED"]["URL"] = $url;
		}
		else
		{
			static $parserLog = false;
			if (CModule::IncludeModule("forum"))
			{
				if (!$parserLog)
					$parserLog = new forumTextParser(LANGUAGE_ID);

				$arAllow = array(
					"HTML" => "N",
					"ALIGN" => "Y",
					"ANCHOR" => "Y", "BIU" => "Y",
					"IMG" => "Y", "QUOTE" => "Y",
					"CODE" => "Y", "FONT" => "Y",
					"LIST" => "Y", "SMILES" => "Y",
					"NL2BR" => "Y", "VIDEO" => "Y",
					"LOG_VIDEO" => "N", "SHORT_ANCHOR" => "Y",
					"USERFIELDS" => $arFields["UF"],
					"USER" => "Y"
				);

				$parserLog->pathToUser = $parserLog->userPath = $arParams["PATH_TO_USER"];
				$parserLog->arUserfields = $arFields["UF"];
				$parserLog->bMobile = ($arParams["MOBILE"] == "Y");
				$arResult["EVENT_FORMATTED"]["MESSAGE"] = htmlspecialcharsbx($parserLog->convert(htmlspecialcharsback($arResult["EVENT_FORMATTED"]["MESSAGE"]), $arAllow));
			}
			else
			{
				if (!$parserLog)
					$parserLog = new logTextParser(false, $arParams["PATH_TO_SMILE"]);

				$arAllow = array(
					"HTML" => "Y", "ANCHOR" => "Y", "BIU" => "Y",
					"IMG" => "Y", "LOG_IMG" => "N",
					"QUOTE" => "Y", "LOG_QUOTE" => "N",
					"CODE" => "Y", "LOG_CODE" => "N",
					"FONT" => "Y", "LOG_FONT" => "N",
					"LIST" => "Y",
					"SMILES" => "Y",
					"NL2BR" => "Y",
					"MULTIPLE_BR" => "N",
					"VIDEO" => "Y", "LOG_VIDEO" => "N"
				);

				$arResult["EVENT_FORMATTED"]["MESSAGE"] = htmlspecialcharsbx($parserLog->convert(htmlspecialcharsback($arResult["EVENT_FORMATTED"]["MESSAGE"]), array(), $arAllow));
			}

			if (
				$arParams["MOBILE"] != "Y"
				&& $arParams["NEW_TEMPLATE"] != "Y"
			)
			{
				if (CModule::IncludeModule("forum"))
					$arResult["EVENT_FORMATTED"]["SHORT_MESSAGE"] = $parserLog->html_cut(
						$parserLog->convert(htmlspecialcharsback($arResult["EVENT_FORMATTED"]["MESSAGE"]), $arAllow),
						500
					);
				else
					$arResult["EVENT_FORMATTED"]["SHORT_MESSAGE"] = $parserLog->html_cut(
						$parserLog->convert(htmlspecialcharsback($arResult["EVENT_FORMATTED"]["MESSAGE"]), array(), $arAllow),
						500
					);

				$arResult["EVENT_FORMATTED"]["IS_MESSAGE_SHORT"] = CSocNetLogTools::FormatEvent_IsMessageShort($arResult["EVENT_FORMATTED"]["MESSAGE"], $arResult["EVENT_FORMATTED"]["SHORT_MESSAGE"]);
			}
		}

		return $arResult;
	}

	public static function OnIBlockModuleUnInstall()
	{
		$GLOBALS['APPLICATION']->ThrowException(GetMessage('INTR_IBLOCK_REQUIRED'));

		return false;
	}

	function OnAfterUserAuthorize($arParams)
	{
		unset($_SESSION["OTP_ADMIN_INFO"]);
		unset($_SESSION["OTP_EMPLOYEES_INFO"]);
		unset($_SESSION["OTP_MANDATORY_INFO"]);

		if (!empty($arParams["user_fields"]["CONFIRM_CODE"]))
		{
			$user = new CUser();
			$user->Update($arParams["user_fields"]["ID"], array("CONFIRM_CODE" => ""));
		}
	}
}

if(!defined("ADMIN_SECTION") || ADMIN_SECTION !== true)
	$GLOBALS["APPLICATION"]->SetAdditionalCSS("/bitrix/js/intranet/intranet-common.css");

//AddEventHandler("main", "OnBeforeProlog", "BMRedirectToMobile", 50);
function BMRedirectToMobile()
{
	if(defined("SITE_ID") && $GLOBALS["APPLICATION"]->GetCurPage(true) == SITE_DIR."index.php" && COption::GetOptionString("main", "wizard_mobile_installed", "N", SITE_ID) == "Y")
	{
		if($_REQUEST["SITE_FULL_VERSION"] == "Y")
			$_SESSION["SESS_MOBILE_USE_FULL_VERSION_".SITE_ID] = "Y";

		if($_SESSION["SESS_MOBILE_USE_FULL_VERSION_".SITE_ID] != "Y" && (preg_match('/android|avantgo|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od|ad)|iris|kindle|lge |maemo|midp|mmp|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i',$_SERVER['HTTP_USER_AGENT'])
			||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|e\-|e\/|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(di|rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|xda(\-|2|g)|yas\-|your|zeto|zte\-/i',substr($_SERVER['HTTP_USER_AGENT'],0,4))))
		{
			LocalRedirect(SITE_DIR."m/");
			die();
		}
	}
}
?>
