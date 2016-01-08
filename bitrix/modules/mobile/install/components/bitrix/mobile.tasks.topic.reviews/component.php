<?php
//s oundex('mobile.tasks.topic.reviews component: begin work');
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (isset($arParams['JUST_SHOW_BULK_TEMPLATE']) && ($arParams['JUST_SHOW_BULK_TEMPLATE'] === 'Y'))
{
	$arResult = array();
	$this->IncludeComponentTemplate();
	return $arResult;
}

if ( ! isset($arParams['SHOW_TEMPLATE']) )
	$arParams['SHOW_TEMPLATE'] = 'Y';	// show template by default

$arParams['TASK_ID'] = (int) $arParams['TASK_ID'];

if ( ! isset($arParams['DATE_TIME_FORMAT']) || empty($arParams['DATE_TIME_FORMAT']))
	$arParams['DATE_TIME_FORMAT'] = $DB->DateFormatToPHP(CSite::GetDateFormat('FULL'));

$arParams['DATE_TIME_FORMAT'] = trim($arParams['DATE_TIME_FORMAT']);

if ( ! isset($arParams['AVATAR_SIZE']) )
	$arParams['AVATAR_SIZE'] = array('width' => 58, 'height' => 58);

if ( ! isset($arParams['COMMENT_ID']) )
	$arParams['COMMENT_ID'] = false;

if ( ! isset($arParams['ATTACH_FILES']) )
	$arParams['ATTACH_FILES'] = 'N';

if (
	! isset($arParams['DEFAULT_MESSAGES_COUNT']) 
	|| intval($arParams['DEFAULT_MESSAGES_COUNT']) <= 0
)
	$arParams['DEFAULT_MESSAGES_COUNT'] = 3;

if (isset($arParams['MESSAGES_PER_PAGE']) && ($arParams['MESSAGES_PER_PAGE'] > 0))
	$arParams['MESSAGES_PER_PAGE'] = (int) $arParams['MESSAGES_PER_PAGE'];
else
	$arParams['MESSAGES_PER_PAGE'] = (int) COption::GetOptionString('forum', 'MESSAGES_PER_PAGE', '10');

$environmentCheck = (($arParams['SHOW_TEMPLATE'] === 'Y') || ($arParams['SHOW_TEMPLATE'] === 'N'))
	&& CModule::IncludeModule('forum')
	&& CModule::IncludeModule('tasks')
	&& is_int($arParams['TASK_ID'])
	&& ($arParams['TASK_ID'] > 0)
	&& is_int($arParams['FORUM_ID'])
	&& ($arParams['FORUM_ID'] > 0)
	&& is_string($arParams['URL_TEMPLATES_PROFILE_VIEW'])
	&& strlen($arParams['URL_TEMPLATES_PROFILE_VIEW'])
	&& is_string($arParams['DATE_TIME_FORMAT'])
	&& strlen($arParams['DATE_TIME_FORMAT'])
	&& is_array($arParams['AVATAR_SIZE'])
	&& (count($arParams['AVATAR_SIZE']) === 2)
	&& isset($arParams['AVATAR_SIZE']['width'])
	&& isset($arParams['AVATAR_SIZE']['height'])
	&& isset($arParams['COMMENT_ID'])
	&& isset($arParams['ATTACH_FILES'])
	&& (($arParams['ATTACH_FILES'] === 'Y') || ($arParams['ATTACH_FILES'] === 'N'))
	;

if ( ! $environmentCheck )
	return (false);

$arParams['URL_TEMPLATES_PROFILE_VIEW'] = htmlspecialcharsbx($arParams['URL_TEMPLATES_PROFILE_VIEW']);

if ($arParams["CACHE_TYPE"] == "Y" || ($arParams["CACHE_TYPE"] == "A" 
	&& COption::GetOptionString("main", "component_cache_on", "Y") == "Y"))
{
	$arParams["CACHE_TIME"] = intval($arParams["CACHE_TIME"]);
}
else
{
	$arParams["CACHE_TIME"] = 0;
}

// activation rating
CRatingsComponentsMain::GetShowRating($arParams);

/* * ******************************************************************
	Default values
 * ****************************************************************** */
$cache = new CPHPCache();
$cache_path_main = str_replace(array(":", "//"), "/", "/".SITE_ID."/".$componentName);
$arError = array();
$arNote = array();
$arResult["ERROR_MESSAGE"] = '';
$arResult["OK_MESSAGE"] = '';
$arResult["MESSAGES"] = array();
$arResult["FILES"] = array();
$arResult["FORUM_TOPIC_ID"] = 0;

// TASK
if ( ! isset($arParams['TASK']) )
{
	$rs = CTasks::GetByID($arParams['TASK_ID']);
	if ($ar = $rs->Fetch())
		$arParams['TASK'] = $ar;
	else
	{
		ShowError(GetMessage('MB_TASKS_TASK_TOPIC_REVIEWS_ERR_ACCESS_DENIED_TO_FORUM'));
		return false;
	}
}

if ($arParams["TASK"])
{
	$arResult["TASK"] = $arParams["TASK"];
	$arResult["FORUM_TOPIC_ID"] = intVal($arResult["TASK"]["FORUM_TOPIC_ID"]);

	if ($arResult["FORUM_TOPIC_ID"])
	{
		$arTopic = CForumTopic::GetByID($arResult["FORUM_TOPIC_ID"]);
		if ($arTopic)
		{
			$arParams["FORUM_ID"] = $arTopic["FORUM_ID"];
		}
	}
}

$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

/* * ******************************************************************
	External permissions from tasks
 * ****************************************************************** */
// A - NO ACCESS		E - READ			I - ANSWER
// M - NEW TOPIC		Q - MODERATE	U - EDIT			Y - FULL_ACCESS


// User already have access to task, so user have access to read/create comments
$arParams['PERMISSION'] = 'M';
/* * ******************************************************************
/	External permissions from tasks
 * ****************************************************************** */

$arResult["FORUM"] = CForumNew::GetByID($arParams["FORUM_ID"]);
$arResult["ELEMENT"] = array();
$arResult["USER"] = array(
	"PERMISSION" => $arParams['PERMISSION'],
	"SHOWED_NAME" => $GLOBALS["FORUM_STATUS_NAME"]["guest"],
	"SUBSCRIBE" => array(),
	"FORUM_SUBSCRIBE" => "N", "TOPIC_SUBSCRIBE" => "N"
);

if ($USER->IsAuthorized())
{
	$tmpName = CUser::FormatName($arParams["NAME_TEMPLATE"],array(	"NAME"		 	=> $GLOBALS["USER"]->GetFirstName(), 
																	"LAST_NAME" 	=> $GLOBALS["USER"]->GetLastName(), 
																	"SECOND_NAME" 	=> $GLOBALS["USER"]->GetSecondName(), 
																	"LOGIN" 		=> $GLOBALS["USER"]->GetLogin()));

	$arResult["USER"]["SHOWED_NAME"] = trim($_SESSION["FORUM"]["SHOW_NAME"] == "Y" ? $tmpName : $GLOBALS["USER"]->GetLogin());
	$arResult["USER"]["SHOWED_NAME"] = trim(!empty($arResult["USER"]["SHOWED_NAME"]) ? $arResult["USER"]["SHOWED_NAME"] : $GLOBALS["USER"]->GetLogin());
}

$arResult["TRANSLIT"] = (LANGUAGE_ID == "ru" ? "Y" : " N");
if ($arResult["FORUM"]["ALLOW_SMILES"] == "Y")
{
	$arResult["ForumPrintSmilesList"] = ($arResult["FORUM"]["ALLOW_SMILES"] == "Y" ? ForumPrintSmilesList(3, LANGUAGE_ID, $arParams["PATH_TO_SMILE"], $arParams["CACHE_TIME"]) : "");
	$arResult["SMILES"] = CForumSmile::GetByType("S", LANGUAGE_ID);
	foreach($arResult["SMILES"] as $key=>$smile)
	{
		$arResult["SMILES"][$key]["IMAGE"] = $arParams["PATH_TO_SMILE"].$smile["IMAGE"];
		$arResult["SMILES"][$key]["DESCRIPTION"] = $arResult["SMILES"][$key]["NAME"];
		list($arResult["SMILES"][$key]["TYPING"],) = explode(" ", $smile["TYPING"]);
	}
}

// PARSER
$parser = new forumTextParser();
$parser->imageWidth = $arParams["IMAGE_SIZE"];
$parser->imageHeight = $arParams["IMAGE_SIZE"];
$parser->smiles = $arResult["SMILES"];
$parser->allow = array(
	"HTML" => $arResult["FORUM"]["ALLOW_HTML"],
	"ANCHOR" => $arResult["FORUM"]["ALLOW_ANCHOR"],
	"BIU" => $arResult["FORUM"]["ALLOW_BIU"],
	"IMG" => "Y",
	"VIDEO" => "N",
	"LIST" => $arResult["FORUM"]["ALLOW_LIST"],
	"QUOTE" => $arResult["FORUM"]["ALLOW_QUOTE"],
	"CODE" => $arResult["FORUM"]["ALLOW_CODE"],
	"FONT" => $arResult["FORUM"]["ALLOW_FONT"],
	"SMILES" => $arResult["FORUM"]["ALLOW_SMILES"],
	"UPLOAD" => $arResult["FORUM"]["ALLOW_UPLOAD"],
	"NL2BR" => $arResult["FORUM"]["ALLOW_NL2BR"],
	"TABLE" => "Y"
);
$parser->pathToUser = $arParams['URL_TEMPLATES_PROFILE_VIEW'];

if (empty($arResult["FORUM"]))
{
	ShowError(str_replace("#FORUM_ID#", $arParams["FORUM_ID"], GetMessage("MB_TASKS_TASK_TOPIC_REVIEWS_ERR_FORUM_NOT_AVAILABLE")));
	return false;
}
elseif (empty($arResult["TASK"]))
{
	ShowError(str_replace("#TASK_ID#", $arParams["TASK_ID"], GetMessage("MB_TASKS_TASK_TOPIC_REVIEWS_ERR_TASK_NOT_AVAILABLE")));
	return false;
}

/* * ******************************************************************
	Actions
 * ****************************************************************** */
ForumSetLastVisit($arParams["FORUM_ID"], 0);

if (isset($arParams['ACTION']) && ($arParams['ACTION'] === 'ADD_COMMENT'))
{
	if ( ! isset($arParams['ACTION:MESSAGE']) )
		return (false);

	$commentText = $arParams['ACTION:MESSAGE'];
	$strMsgAddComment = GetMessage('MB_TASKS_TASK_FORUM_TOPIC_REVIEWS_COMMENT_MESSAGE_ADD');
	$strMsgEditComment = GetMessage('MB_TASKS_TASK_FORUM_TOPIC_REVIEWS_COMMENT_MESSAGE_EDIT');
	$strMsgNewTask = GetMessage('MB_TASKS_TASK_FORUM_TOPIC_REVIEWS_COMMENT_SONET_NEW_TASK_MESSAGE');
	$forumTopicId = $arResult['FORUM_TOPIC_ID'];
	$forumId = $arParams['FORUM_ID'];
	$nameTemplate = $arParams['NAME_TEMPLATE'];
	$arTask = $arResult['TASK'];
	$permissions = $arParams['PERMISSION'];
	$commentId = 0;		// only if edit
	$givenUserId = $USER->GetID();
	$imageWidth = $arParams['IMAGE_SIZE'];
	$imageHeight = $arParams['IMAGE_SIZE'];
	$arSmiles = $arResult['SMILES'];
	$arForum = $arResult['FORUM'];
	$messagesPerPage = $arParams['MESSAGES_PER_PAGE'];
	$arUserGroupArray = $GLOBALS['USER']->GetUserGroupArray();
	$backPage = null;	// This is the new sheet...

	$arErrorCodes = array();
	$outForumTopicId = null;
	$outStrUrl = '';

	$rc = CTaskComments::__deprecated_Add(
		$commentText,
		$forumTopicId,
		$forumId,
		$nameTemplate,
		$arTask,
		$permissions,
		$commentId,
		$givenUserId,
		$imageWidth,
		$imageHeight,
		$arSmiles,
		$arForum,
		$messagesPerPage,
		$arUserGroupArray,
		$backPage,
		$strMsgAddComment,
		$strMsgEditComment,
		$strMsgNewTask,
		$componentName,
		$outForumTopicId,
		$arErrorCodes,
		$outStrUrl
	);
	$strURL = $outStrUrl;

	$arResult["FORUM_TOPIC_ID"] = $outForumTopicId;

	$strOKMessage = GetMessage("COMM_COMMENT_OK");

	foreach ($arErrorCodes as $v)
	{
		if (is_string($v['title']))
			$errTitle = $v['title'];
		else
		{
			switch ($v['code'])
			{
				case 'topic is not created':
					$errTitle = GetMessage('F_ERR_ADD_TOPIC');
				break;

				case 'message is not added 2':
					$errTitle = GetMessage('F_ERR_ADD_MESSAGE');
				break;

				default:
					$errTitle = '';
				break;
			}
		}

		$arError[] = array(
			'code'  => $v['code'],
			'title' => $errTitle
		);
	}

	if ( (int) $rc > 0 )
	{
		$arResult['JUST_ADDED_COMMENT_ID'] = (int) $rc;
		$arResult['TASK']['COMMENTS_COUNT'] = $arResult['TASK']['COMMENTS_COUNT'] + 1;
	}
}
/* * ******************************************************************
/	Actions
 * ****************************************************************** */

/* * ******************************************************************
	Input params II
 * ****************************************************************** */
/* * ************ URL *********************************************** */
if (empty($arParams["~URL_TEMPLATES_READ"]) && !empty($arResult["FORUM"]["PATH2FORUM_MESSAGE"]))
{
	$arParams["~URL_TEMPLATES_READ"] = $arResult["FORUM"]["PATH2FORUM_MESSAGE"];
}
elseif (empty($arParams["~URL_TEMPLATES_READ"]))
{
	$arParams["~URL_TEMPLATES_READ"] = $APPLICATION->GetCurPage()."?PAGE_NAME=read&FID=#FID#&TID=#TID#&MID=#MID#";
}
$arParams["~URL_TEMPLATES_READ"] = str_replace(array("#FORUM_ID#", "#TOPIC_ID#", "#MESSAGE_ID#"), array("#FID#", "#TID#", "#MID#"), $arParams["~URL_TEMPLATES_READ"]);
$arParams["URL_TEMPLATES_READ"] = htmlspecialcharsEx($arParams["~URL_TEMPLATES_READ"]);
/* * ******************************************************************
/	Input params
 * ****************************************************************** */

/** * *****************************************************************
	Data
 * ****************************************************************** */
/* * ************ 3. Get inormation about USER ********************** */
if ($GLOBALS["USER"]->IsAuthorized() && $arResult["USER"]["PERMISSION"] > "E")
{
	// USER subscribes
	$arUserSubscribe = array();
	$arFields = array("USER_ID" => $GLOBALS["USER"]->GetID(), "FORUM_ID" => $arParams["FORUM_ID"]);
	$db_res = CForumSubscribe::GetList(array(), $arFields);
	if ($db_res && $res = $db_res->Fetch())
	{
		do
		{
			$arUserSubscribe[] = $res;
		}
		while ($res = $db_res->Fetch());
	}
	$arResult["USER"]["SUBSCRIBE"] = $arUserSubscribe;
	foreach ($arUserSubscribe as $res)
	{
		if (intVal($res["TOPIC_ID"]) <= 0)
		{
			$arResult["USER"]["FORUM_SUBSCRIBE"] = "Y";
		}
		elseif (intVal($res["TOPIC_ID"]) == intVal($arResult["FORUM_TOPIC_ID"]))
		{
			$arResult["USER"]["TOPIC_SUBSCRIBE"] = "Y";
		}
	}
}

/* * ************ 4. Get message list ******************************* */

$arResult['MESSAGES_COUNT'] = $arResult['TASK']['COMMENTS_COUNT'];

$pageNo = 0;
if ($arResult["FORUM_TOPIC_ID"] > 0)
{
	$arMessages = array();
	$ar_cache_id = array(
		'mobile',
		$arParams["FORUM_ID"], 
		$arParams["TASK_ID"], 
		$arResult["FORUM_TOPIC_ID"], 
		$arParams["DEFAULT_MESSAGES_COUNT"], 
		$arParams["DATE_TIME_FORMAT"], 
		$arParams["NAME_TEMPLATE"], 
		$arResult['FORUM']['LAST_POST_DATE'],
		$arResult['TASK']['COMMENTS_COUNT'],
		$arParams['COMMENT_ID'],
		$arParams['ATTACH_FILES'], time()
	);

	$cache_id = "forum_message_".serialize($ar_cache_id);
	if(($tzOffset = CTimeZone::GetOffset()) <> 0)
		$cache_id .= "_".$tzOffset;

	$cache_path = $cache_path_main;
	if ($arParams["CACHE_TIME"] > 0 && $cache->InitCache($arParams["CACHE_TIME"], $cache_id, $cache_path))
	{
		$res = $cache->GetVars();
		if (is_array($res["arMessages"]))
			$arMessages = $res["arMessages"];
	}

	if (empty($arMessages))
	{
		$arMessagesIds = array();
		$arMessagesTmp = array();

		$arOrder = array('ID' => 'DESC');

		$arFilter = array(
			'FORUM_ID'    => $arParams['FORUM_ID'], 
			'TOPIC_ID'    => $arResult['FORUM_TOPIC_ID'],
			'!PARAM1'     => 'TK',	// skip initial topic system message
			'APPROVED'    => 'Y'
		);

		// Requested some comment?
		// Or if added comment - get only it.
		if (
			($arParams['COMMENT_ID'] > 0) 
			|| (
				isset($arResult['JUST_ADDED_COMMENT_ID']) 
				&& ($arResult['JUST_ADDED_COMMENT_ID'] > 0)
			)
		)
		{
			if ($arParams['COMMENT_ID'] > 0)
				$arFilter['ID'] = (int) $arParams['COMMENT_ID'];
			else
				$arFilter['ID'] = (int) $arResult['JUST_ADDED_COMMENT_ID'];

			$db_res = CForumMessage::GetListEx(
				$arOrder, 
				$arFilter, 
				false, 
				0
			);

			if ($ar = $db_res->Fetch())
			{
				$ar['META:UNREAD'] = false;
				$arMessagesTmp[] = $ar;
				$arMessagesIds[] = $ar['ID'];
			}
		}
		else	// get list of comments
		{
			$arFilterRead = $arFilter;

			// Firstly, get unread messages
			if ($arParams['TASK_LAST_VIEWED_DATE'])
			{
				$arFilterUnread = $arFilter;
				$arFilterUnread['>=POST_DATE'] = $arParams['TASK_LAST_VIEWED_DATE'];
				$arFilterRead['<POST_DATE'] = $arParams['TASK_LAST_VIEWED_DATE'];

				$db_res = CForumMessage::GetListEx(
					$arOrder, 
					$arFilterUnread, 
					false, 
					0
				);

				while ($ar = $db_res->Fetch())
				{
					$ar['META:UNREAD'] = true;
					$arMessagesTmp[] = $ar;
					$arMessagesIds[] = $ar['ID'];
				}
			}
			$msgsCount = count($arMessagesTmp);

			// If messages not enough, than get additionally already read messages
			if ($msgsCount < $arParams['DEFAULT_MESSAGES_COUNT'])
			{
				$notEnoughtMessages = $arParams['DEFAULT_MESSAGES_COUNT'] - $msgsCount;

				$db_res = CForumMessage::GetListEx(
					$arOrder, 
					$arFilterRead, 
					false, 
					$notEnoughtMessages
				);

				while ($ar = $db_res->Fetch())
				{
					$ar['META:UNREAD'] = false;
					$arMessagesTmp[] = $ar;
					$arMessagesIds[] = $ar['ID'];
				}
			}
		}

		$arMessagesTmp = array_reverse($arMessagesTmp);

		// Prepare USER FIELDS data
		$arResult['UFS'] = array();
		$arResult['USER_FIELDS'] = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("FORUM_MESSAGE", 0, LANGUAGE_ID);
		if (!empty($arMessagesTmp))
		{
			$arFilter = array(
				"FORUM_ID" => $arParams["FORUM_ID"],
				"TOPIC_ID" => $arResult["FORUM_TOPIC_ID"],
				'!PARAM1'  => 'TK',	// skip initial topic system message
				'APPROVED' => 'Y',
				">ID" => intVal(min($arMessagesIds)) - 1,
				"<ID" => intVal(max($arMessagesIds)) + 1
			);

			$db_res = CForumMessage::GetList(array("ID" => "ASC"), $arFilter, false, 0, array("SELECT" => array_keys($arResult['USER_FIELDS'])));
			if ($db_res && ($res = $db_res->Fetch()))
			{
				do
				{
					$arResult['UFS'][$res["ID"]] = array_intersect_key($res, $arResult['USER_FIELDS']);
				}
				while ($res = $db_res->Fetch());
			}
		}

		$arAvatars = array();
		if ($db_res)
		{
			$arNeededUsersIds = array();
			// At first lap collect users' ids for optimal request

			foreach ($arMessagesTmp as $res)
			{
				if ($res['AUTHOR_ID'] >= 1)
					$arNeededUsersIds[] = (int) $res['AUTHOR_ID'];
			}

			// Fetch users data
			$rsNeededUsersData = CUser::GetList(
				$passByReference1 = 'id', 	// order by
				$passByReference2 = 'asc', 	// order direction
				$passByReference3 = array(		// filter
					'ID' => implode('|', $arNeededUsersIds)
				),
				array(
					'FIELDS' => array('ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN', 'PERSONAL_PHOTO',)
				)
			);

			while ($arTmpUserData = $rsNeededUsersData->Fetch())
				$arNeededUsersData[$arTmpUserData['ID']] = $arTmpUserData;

			unset($arTmpUserData);
		
			foreach ($arMessagesTmp as $res)
			{
				/*				 * ************ Message info ************************************** */
				// data
				$res["~POST_DATE"] = $res["POST_DATE"];
				$res["~EDIT_DATE"] = $res["EDIT_DATE"];
				$res["POST_DATE"] = CForumFormat::DateFormat($arParams["DATE_TIME_FORMAT"], MakeTimeStamp($res["POST_DATE"], CSite::GetDateFormat()));
				$res["EDIT_DATE"] = CForumFormat::DateFormat($arParams["DATE_TIME_FORMAT"], MakeTimeStamp($res["EDIT_DATE"], CSite::GetDateFormat()));
				// text
				$res["~POST_MESSAGE_TEXT"] = (COption::GetOptionString("forum", "FILTER", "Y") == "Y" ? $res["POST_MESSAGE_FILTER"] : $res["POST_MESSAGE"]);

				if (array_key_exists($res["ID"], $arResult["UFS"]))
					$parser->arUserfields = $res["UF"] = $arResult["UFS"][$res["ID"]];
				else
					$parser->arUserfields = $res["UF"] = array();

				$res['POST_MESSAGE_TEXT'] = $parser->convert($res['~POST_MESSAGE_TEXT']);

				if (is_array($res["UF"]))
				{
					ob_start();
					foreach ($res["UF"] as $arPostField)
					{
						if(!empty($arPostField["VALUE"]))
						{
							echo '&nbsp;<br>&nbsp;';
							$GLOBALS["APPLICATION"]->IncludeComponent(
								"bitrix:system.field.view", 
								$arPostField["USER_TYPE"]["USER_TYPE_ID"],
								array(
									"arUserField" => $arPostField,
									"MOBILE" => "Y"
								), 
								null, 
								array("HIDE_ICONS"=>"Y")
							);
						}
					}
					?>
					<script>
						BX.ready(function(){
							__MB_TASKS_TASK_TOPIC_REVIEWS_viewImageBind(
								'tasks-comment-block-<?php echo $res['ID']; ?>',
								{
									tag: 'IMG',
									attr: 'data-bx-image'
								}
							);
						});
					</script>
					<?php
					$res["POST_MESSAGE_TEXT"] .= '<br>' . ob_get_clean();
				}

				// attach
				$res["ATTACH_IMG"] = "";
				$res["FILES"] = array();
				$res["~ATTACH_FILE"] = array();
				$res["ATTACH_FILE"] = array();
				/*				 * ************ Message info/************************************** */
				/*				 * ************ Author info *************************************** */
				$res["AUTHOR_ID"] = intVal($res["AUTHOR_ID"]);
				$res["AUTHOR_URL"] = "";
				if (!empty($arParams["URL_TEMPLATES_PROFILE_VIEW"]))
				{
					$res["AUTHOR_URL"] = CComponentEngine::MakePathFromTemplate($arParams["URL_TEMPLATES_PROFILE_VIEW"], array("user_id" => $res["AUTHOR_ID"]));
				}
				if (!isset($arAvatars[$res["AUTHOR_ID"]]))
				{
					$arAvatars[$res["AUTHOR_ID"]] = false;

					if (
						isset($arNeededUsersData[$res["AUTHOR_ID"]]["PERSONAL_PHOTO"])
						&& (intval($arNeededUsersData[$res["AUTHOR_ID"]]["PERSONAL_PHOTO"]) > 0)
					)
					{
						$imageFile = CFile::GetFileArray($arNeededUsersData[$res["AUTHOR_ID"]]["PERSONAL_PHOTO"]);
						if ($imageFile !== false)
						{
							$arFileTmp = CFile::ResizeImageGet(
								$imageFile, 
								array(
									"width"  => $arParams['AVATAR_SIZE']['width'], 
									"height" => $arParams['AVATAR_SIZE']['height']
								), 
								BX_RESIZE_IMAGE_EXACT, 
								false
							);
							$arAvatars[$res["AUTHOR_ID"]] = $arFileTmp["src"];
						}
					}
				}
				$res["AUTHOR_PHOTO"] = $arAvatars[$res["AUTHOR_ID"]];
				/************** Author info/*************************************** */
				// For quote JS
				$res["FOR_JS"]["AUTHOR_NAME"] = Cutil::JSEscape($res["AUTHOR_NAME"]);
				$res["FOR_JS"]["POST_MESSAGE_TEXT"] = Cutil::JSEscape(htmlspecialcharsbx($res["POST_MESSAGE_TEXT"]));
				$res["FOR_JS"]["POST_MESSAGE"] = Cutil::JSEscape(htmlspecialcharsbx($res["POST_MESSAGE"]));

				// Forum store name of author permamently
				// When name of author changes => in comments we see old name
				// So, we just get dynamically name on every request (except cache)
				$res['AUTHOR_DYNAMIC_NAME_AS_ARRAY'] = false;

				if (isset($arNeededUsersData[$res['AUTHOR_ID']]))
				{
					$arDynName = $arNeededUsersData[$res['AUTHOR_ID']];

					$res['AUTHOR_DYNAMIC_NAME_AS_ARRAY'] = array(
						'LOGIN'       => $arDynName['LOGIN'],
						'NAME'        => $arDynName['NAME'],
						'SECOND_NAME' => $arDynName['SECOND_NAME'],
						'LAST_NAME'   => $arDynName['LAST_NAME']
					);
				}
				else
					$res['AUTHOR_DYNAMIC_NAME_AS_ARRAY'] = array();

				$res["FOR_JS"]["AUTHOR_DYNAMIC_NAME"] = Cutil::JSEscape(
					tasksFormatName(
						$res['AUTHOR_DYNAMIC_NAME_AS_ARRAY']['NAME'], 
						$res['AUTHOR_DYNAMIC_NAME_AS_ARRAY']['LAST_NAME'], 
						$res['AUTHOR_DYNAMIC_NAME_AS_ARRAY']['LOGIN'], 
						$res['AUTHOR_DYNAMIC_NAME_AS_ARRAY']['SECOND_NAME'], 
						$arParams['NAME_TEMPLATE'],
						true	// escape special chars
					)
				);

				// Format data for templates/ajax
				$res['META:FORMATTED_DATA'] = array();

				$res['META:FORMATTED_DATA']['AUTHOR_NAME'] = CUser::FormatName(
					$arParams['NAME_TEMPLATE'], 
					array(
						'NAME'        => $res['AUTHOR_DYNAMIC_NAME_AS_ARRAY']['NAME'], 
						'LAST_NAME'   => $res['AUTHOR_DYNAMIC_NAME_AS_ARRAY']['LAST_NAME'], 
						'SECOND_NAME' => $res['AUTHOR_DYNAMIC_NAME_AS_ARRAY']['SECOND_NAME'], 
						'LOGIN'       => $res['AUTHOR_DYNAMIC_NAME_AS_ARRAY']['LOGIN'], 
						),
					true,
					true	// use htmlspecialcharsbx
				);

				$res['META:FORMATTED_DATA']['AUTHOR_URL'] = str_replace(
					array('#USER_ID#', '#user_id#'),
					(int) $res['AUTHOR_ID'],
					$arParams['URL_TEMPLATES_PROFILE_VIEW']
				);

				$res['META:FORMATTED_DATA']['DATETIME_SEXY'] = CTasksTools::FormatDatetimeBeauty(
					$res['~POST_DATE'], 
					array(), 		// params
					$arParams['DATE_TIME_FORMAT']
				);

				$arMessages[$res['ID']] = $res;
			}
		}

		/*		 * ************ Attach files ************************************** */
		if ($arParams['ATTACH_FILES'] === 'Y')
		{
			if (!empty($arMessages))
			{
				$res = array_keys($arMessages);
				$arFilter = array("FORUM_ID" => $arParams["FORUM_ID"], "TOPIC_ID" => $arResult["FORUM_TOPIC_ID"], "APPROVED" => "Y", ">MESSAGE_ID" => intVal(min($res)) - 1, "<MESSAGE_ID" => intVal(max($res)) + 1);
				$db_files = CForumFiles::GetList(array("MESSAGE_ID" => "ASC"), $arFilter);
				if ($db_files && $res = $db_files->Fetch())
				{
					do
					{
						$res["SRC"] = CFile::GetFileSRC($res);
						if ($arMessages[$res["MESSAGE_ID"]]["~ATTACH_IMG"] == $res["FILE_ID"])
						{
							// attach for custom
							$arMessages[$res["MESSAGE_ID"]]["~ATTACH_FILE"] = $res;
							$arMessages[$res["MESSAGE_ID"]]["ATTACH_IMG"] = CFile::ShowFile($res["FILE_ID"], 0, $arParams["IMAGE_SIZE"], $arParams["IMAGE_SIZE"], true, "border=0", false);
							$arMessages[$res["MESSAGE_ID"]]["ATTACH_FILE"] = $arMessages[$res["MESSAGE_ID"]]["ATTACH_IMG"];
						}
						$arMessages[$res["MESSAGE_ID"]]["FILES"][$res["FILE_ID"]] = $res;
						$arResult["FILES"][$res["FILE_ID"]] = $res;
					}
					while ($res = $db_files->Fetch());
				}
			}
		}

		/*		 * ************ Message List/************************************** */
		if ($arParams["CACHE_TIME"] > 0)
		{
			$cache->StartDataCache($arParams["CACHE_TIME"], $cache_id, $cache_path);
			$cache->EndDataCache(array(
				"arMessages" => $arMessages
			));
		}
	}

	/************** Rating ****************************************/
	if ($arParams["SHOW_RATING"] == "Y")
	{
		$arMessageIDs = array_keys($arMessages);
		$arRatings = CRatings::GetRatingVoteResult('FORUM_POST', $arMessageIDs);

		foreach ($arMessages as $postId => $res)
		{
			if (isset($arRatings[$postId]))
				$arMessages[$postId]['RATING'] = $arRatings[$postId];

			if ( ! isset($arMessages[$postId]['RATING']) )
			{
				$arMessages[$postId]['RATING'] = array(
					'USER_VOTE'            => 0,
					'USER_HAS_VOTED'       => 'N',
					'TOTAL_VOTES'          => 0,
					'TOTAL_POSITIVE_VOTES' => 0,
					'TOTAL_NEGATIVE_VOTES' => 0,
					'TOTAL_VALUE'          => 0
				);
			}

			$arMessages[$postId]['RATING']['ENTITY_TYPE_ID']       = 'FORUM_POST';
			$arMessages[$postId]['RATING']['ENTITY_ID']            = $postId;
			$arMessages[$postId]['RATING']['OWNER_ID']             = $res['AUTHOR_ID'];
			$arMessages[$postId]['RATING']['PATH_TO_USER_PROFILE'] = $arParams['URL_TEMPLATES_PROFILE_VIEW'];

			$arMessages[$postId]['META:ALLOW_VOTE_RATING'] = CRatings::CheckAllowVote(
				array(
					'ENTITY_TYPE_ID' => $arMessages[$postId]['RATING']['ENTITY_TYPE_ID'],
					'OWNER_ID'       => $arMessages[$postId]['RATING']['OWNER_ID']
				)
			);
		}
	}

	$arResult["MESSAGES"] = $arMessages;

	// Link to forum
	$arResult["read"] = CComponentEngine::MakePathFromTemplate(
		$arParams["URL_TEMPLATES_READ"], 
		array(
			"FID" => $arParams["FORUM_ID"], 
			"TID" => $arResult["FORUM_TOPIC_ID"], 
			"MID" => "s", 
			"PARAM1" => "IB", 
			"PARAM2" => $arParams["ELEMENT_ID"]
		)
	);
}

if ($arParams['SHOW_TEMPLATE'] === 'Y')
	$this->IncludeComponentTemplate();

return ($arResult);
