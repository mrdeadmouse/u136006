<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule("socialnetwork"))
	return;
if (WIZARD_FIRST_INSTAL !== "Y")
{
	$APPLICATION->SetGroupRight("socialnetwork", WIZARD_PORTAL_ADMINISTRATION_GROUP, "W");
	$APPLICATION->SetGroupRight("socialnetwork", WIZARD_CREATE_GROUPS_GROUP, "K");
	$APPLICATION->SetGroupRight("socialnetwork", WIZARD_DIRECTION_GROUP, "K");
	COption::SetOptionString("socialnetwork", "GROUP_DEFAULT_RIGHT", "D");
	COption::SetOptionString("socialnetwork", "allow_frields", "N", false, WIZARD_SITE_ID);
	COption::SetOptionString("socialnetwork", "subject_path_template", WIZARD_SITE_DIR."workgroups/group/search/#subject_id#/", false, WIZARD_SITE_ID);
	COption::SetOptionString("socialnetwork", "group_path_template", WIZARD_SITE_DIR."workgroups/group/#group_id#/", false, WIZARD_SITE_ID);
	COption::SetOptionString("socialnetwork", "messages_path", WIZARD_SITE_DIR."company/personal/messages/", false, WIZARD_SITE_ID);
}

if (WIZARD_B24_TO_CP)
{	
	if (CModule::IncludeModule("iblock"))
	{
		$filesUserIBlockID = 0;
		$filesGroupIBlockID = 0;
		$calendarUserIBlockID = 0;
		$calendarGroupIBlockID = 0;
		$photoUserIBlockID = 0;
		$photoGroupIBlockID = 0;
		
		$dbRes = CIBlock::GetList(array(), array("SITE_ID" => WIZARD_SITE_ID, "CODE" => "user_files"));
		if ($arRes = $dbRes->Fetch())
			$filesUserIBlockID = $arRes["ID"];

		$dbRes = CIBlock::GetList(array(), array("SITE_ID" => WIZARD_SITE_ID, "CODE" => "group_files"));
		if ($arRes = $dbRes->Fetch())
			$filesGroupIBlockID = $arRes["ID"];

		$dbRes = CIBlock::GetList(array(), array("SITE_ID" => WIZARD_SITE_ID, "CODE" => "user_photogallery"));
		if ($arRes = $dbRes->Fetch())
			$photoUserIBlockID = $arRes["ID"];

		$dbRes = CIBlock::GetList(array(), array("SITE_ID" => WIZARD_SITE_ID, "CODE" => "group_photogallery"));
		if ($arRes = $dbRes->Fetch())
			$photoGroupIBlockID = $arRes["ID"];
			
		// tasks
		$tasksIblockId = 0;
		$tasksForumId = 0;

		$iblockCode = "intranet_tasks";
		$iblockType = "services";
		$rsIBlock = CIBlock::GetList(array(), array("XML_ID" => $iblockCode, "TYPE" => $iblockType));
		if ($arIBlock = $rsIBlock->Fetch())
		{
			$tasksIblockId = $arIBlock["ID"];
		}

	}
	if (CModule::IncludeModule("forum"))
	{
		$forumCode = "intranet_tasks";
		$dbRes = CForumNew::GetListEx(array(), array("SITE_ID" => WIZARD_SITE_ID, "XML_ID" => $forumCode));
		if ($arRes = $dbRes->Fetch())
		{
			$tasksForumId = $arRes["ID"];
		}
	}
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/_index.php", Array("TASKS_IBLOCK_ID" => $tasksIblockId));
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/desktop.php", Array("TASKS_IBLOCK_ID" => $tasksIblockId));

	$arReplace = Array(
		"BLOG_GROUP_ID" => $blogGroupID,
		"FORUM_ID" => $forumID,
		"FILES_USER_IBLOCK_ID" => $filesUserIBlockID,
		"PHOTO_USER_IBLOCK_ID" => $photoUserIBlockID,
		"PHOTO_FORUM_ID" => $photoForumID,
		"TASKS_IBLOCK_ID" => $tasksIblockId,
		"TASKS_FORUM_ID" => $tasksForumId,
	);

	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/company/personal.php", $arReplace);
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/index_b24.php", $arReplace);

	$arReplace = Array(
		"BLOG_GROUP_ID" => $blogGroupID,
		"FORUM_ID" => $forumID,
		"FILES_GROUP_IBLOCK_ID" => $filesGroupIBlockID,
		"PHOTO_GROUP_IBLOCK_ID" => $photoGroupIBlockID,
		"PHOTO_FORUM_ID" => $photoForumID,
		"TASKS_IBLOCK_ID" => $tasksIblockId,
		"TASKS_FORUM_ID" => $tasksForumId,
	);

	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/workgroups/index.php", $arReplace);
	$arReplace = Array(
		"FILES_GROUP_IBLOCK_ID" => $filesGroupIBlockID,
		"FILES_USER_IBLOCK_ID" => $filesUserIBlockID,
	);

	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/docs/index.php", $arReplace);
	$arReplace = Array(
		"FILES_GROUP_IBLOCK_ID" => $filesGroupIBlockID,
		"FILES_USER_IBLOCK_ID" => $filesUserIBlockID,
		"TASKS_FORUM_ID" => $tasksForumId,
	);
}

if (WIZARD_INSTALL_DEMO_DATA || WIZARD_FIRST_INSTAL !== "Y")
{

	$arGroupSubjects = array();
	$arGroupSubjectsId = array();

	for ($i = 0; $i < 5; $i++)
	{
		$arGroupSubjects[$i] = array(
			"SITE_ID" => WIZARD_SITE_ID,
			"NAME" => GetMessage("SONET_GROUP_SUBJECT_".$i),
		);
		$arGroupSubjectsId[$i] = 0;
	}

	$errorMessage = "";
	foreach ($arGroupSubjects as $ind => $arGroupSubject)
	{
		$rsSocNetGroupSubject = CSocNetGroupSubject::GetList(array(), $arGroupSubject);

		$idTmp = false;
		if ($arSocNetGroupSubject = $rsSocNetGroupSubject->Fetch())
		{
			$arGroupSubjectsId[$ind] = $arSocNetGroupSubject["ID"];
		}
		else
		{
			$idTmp = CSocNetGroupSubject::Add($arGroupSubject);
			if ($idTmp)
			{
				$arGroupSubjectsId[$ind] = IntVal($idTmp);
			}
			else
			{
				if ($e = $GLOBALS["APPLICATION"]->GetException())
					$errorMessage .= $e->GetString();
			}
		}
	}
	if (StrLen($errorMessage) <= 0)
	{
		$pathToImages = WIZARD_SERVICE_ABSOLUTE_PATH."/images/";

		$arGroupsId = array(0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 7 => 0, 8 => 0, 9 => 0, 10 => 0);
		$arGroups = array(
			0 => array(
				"SITE_ID" => WIZARD_SITE_ID,
				"NAME" => GetMessage("SONET_GROUP_NAME_0"),
				"DESCRIPTION" => GetMessage("SONET_GROUP_DESCRIPTION_0"),
				"=DATE_CREATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
				"=DATE_UPDATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
				"ACTIVE" => "Y",
				"VISIBLE" => "Y",
				"OPENED" => "N",
				"SUBJECT_ID" => $arGroupSubjectsId[1],
				"OWNER_ID" => 1,
				"KEYWORDS" => GetMessage("SONET_GROUP_KEYWORDS_0"),
				"IMAGE_ID" => array(
					"name" => "0.jpg",
					"type" => "image/jpeg",
					"tmp_name" => $pathToImages."/0.jpg",
					"error" => "0",
					"size" => @filesize($pathToImages."/0.jpg"),
					"MODULE_ID" => "socialnetwork"
				),
				"NUMBER_OF_MEMBERS" => 1,
				"INITIATE_PERMS" => "E",
				"SPAM_PERMS" => "N",
				"=DATE_ACTIVITY" => $GLOBALS["DB"]->CurrentTimeFunction(),
			),
			1 => array(
				"SITE_ID" => WIZARD_SITE_ID,
				"NAME" => GetMessage("SONET_GROUP_NAME_1"),
				"DESCRIPTION" => GetMessage("SONET_GROUP_DESCRIPTION_1"),
				"=DATE_CREATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
				"=DATE_UPDATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
				"ACTIVE" => "Y",
				"VISIBLE" => "Y",
				"OPENED" => "N",
				"SUBJECT_ID" => $arGroupSubjectsId[0],
				"OWNER_ID" => 1,
				"KEYWORDS" => GetMessage("SONET_GROUP_KEYWORDS_1"),
				"IMAGE_ID" => array(
					"name" => "1.jpg",
					"type" => "image/jpeg",
					"tmp_name" => $pathToImages."/1.jpg",
					"error" => "0",
					"size" => @filesize($pathToImages."/1.jpg"),
					"MODULE_ID" => "socialnetwork"
				),
				"NUMBER_OF_MEMBERS" => 1,
				"INITIATE_PERMS" => "E",
				"SPAM_PERMS" => "N",
				"=DATE_ACTIVITY" => $GLOBALS["DB"]->CurrentTimeFunction(),
			),
			2 => array(
				"SITE_ID" => WIZARD_SITE_ID,
				"NAME" => GetMessage("SONET_GROUP_NAME_2"),
				"DESCRIPTION" => GetMessage("SONET_GROUP_DESCRIPTION_2"),
				"=DATE_CREATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
				"=DATE_UPDATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
				"ACTIVE" => "Y",
				"VISIBLE" => "Y",
				"OPENED" => "N",
				"SUBJECT_ID" => $arGroupSubjectsId[0],
				"OWNER_ID" => 1,
				"KEYWORDS" => GetMessage("SONET_GROUP_KEYWORDS_2"),
				"IMAGE_ID" => array(
					"name" => "2.jpg",
					"type" => "image/jpeg",
					"tmp_name" => $pathToImages."/2.jpg",
					"error" => "0",
					"size" => @filesize($pathToImages."/2.jpg"),
					"MODULE_ID" => "socialnetwork"
				),
				"NUMBER_OF_MEMBERS" => 1,
				"SPAM_PERMS" => "N",
				"INITIATE_PERMS" => "E",
				"=DATE_ACTIVITY" => $GLOBALS["DB"]->CurrentTimeFunction(),
			),
			3 => array(
				"SITE_ID" => WIZARD_SITE_ID,
				"NAME" => GetMessage("SONET_GROUP_NAME_3"),
				"DESCRIPTION" => GetMessage("SONET_GROUP_DESCRIPTION_3"),
				"=DATE_CREATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
				"=DATE_UPDATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
				"ACTIVE" => "Y",
				"VISIBLE" => "Y",
				"OPENED" => "Y",
				"SUBJECT_ID" => $arGroupSubjectsId[4],
				"OWNER_ID" => 1,
				"KEYWORDS" => GetMessage("SONET_GROUP_KEYWORDS_3"),
				"IMAGE_ID" => array(
					"name" => "3.jpg",
					"type" => "image/jpeg",
					"tmp_name" => $pathToImages."/3.jpg",
					"error" => "0",
					"size" => @filesize($pathToImages."/3.jpg"),
					"MODULE_ID" => "socialnetwork"
				),
				"NUMBER_OF_MEMBERS" => 1,
				"SPAM_PERMS" => "N",
				"INITIATE_PERMS" => "K",
				"=DATE_ACTIVITY" => $GLOBALS["DB"]->CurrentTimeFunction(),
			),
			4 => array(
				"SITE_ID" => WIZARD_SITE_ID,
				"NAME" => GetMessage("SONET_GROUP_NAME_4"),
				"DESCRIPTION" => GetMessage("SONET_GROUP_DESCRIPTION_4"),
				"=DATE_CREATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
				"=DATE_UPDATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
				"ACTIVE" => "Y",
				"VISIBLE" => "Y",
				"OPENED" => "N",
				"SUBJECT_ID" => $arGroupSubjectsId[2],
				"OWNER_ID" => 1,
				"KEYWORDS" => GetMessage("SONET_GROUP_KEYWORDS_4"),
				"IMAGE_ID" => array(
					"name" => "4.jpg",
					"type" => "image/jpeg",
					"tmp_name" => $pathToImages."/4.jpg",
					"error" => "0",
					"size" => @filesize($pathToImages."/4.jpg"),
					"MODULE_ID" => "socialnetwork"
				),
				"NUMBER_OF_MEMBERS" => 1,
				"SPAM_PERMS" => "N",
				"INITIATE_PERMS" => "E",
				"=DATE_ACTIVITY" => $GLOBALS["DB"]->CurrentTimeFunction(),
			),
			5 => array(
				"SITE_ID" => WIZARD_SITE_ID,
				"NAME" => GetMessage("SONET_GROUP_NAME_5"),
				"DESCRIPTION" => GetMessage("SONET_GROUP_DESCRIPTION_5"),
				"=DATE_CREATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
				"=DATE_UPDATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
				"ACTIVE" => "Y",
				"VISIBLE" => "Y",
				"OPENED" => "N",
				"SUBJECT_ID" => $arGroupSubjectsId[2],
				"OWNER_ID" => 1,
				"KEYWORDS" => GetMessage("SONET_GROUP_KEYWORDS_5"),
				"IMAGE_ID" => array(
					"name" => "5.jpg",
					"type" => "image/jpeg",
					"tmp_name" => $pathToImages."/5.jpg",
					"error" => "0",
					"size" => @filesize($pathToImages."/5.jpg"),
					"MODULE_ID" => "socialnetwork"
				),
				"NUMBER_OF_MEMBERS" => 1,
				"INITIATE_PERMS" => "E",
				"SPAM_PERMS" => "N",
				"=DATE_ACTIVITY" => $GLOBALS["DB"]->CurrentTimeFunction(),
			),
			6 => array(
				"SITE_ID" => WIZARD_SITE_ID,
				"NAME" => GetMessage("SONET_GROUP_NAME_6"),
				"DESCRIPTION" => GetMessage("SONET_GROUP_DESCRIPTION_6"),
				"=DATE_CREATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
				"=DATE_UPDATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
				"ACTIVE" => "Y",
				"VISIBLE" => "Y",
				"OPENED" => "Y",
				"SUBJECT_ID" => $arGroupSubjectsId[4],
				"OWNER_ID" => 1,
				"KEYWORDS" => GetMessage("SONET_GROUP_KEYWORDS_6"),
				"IMAGE_ID" => array(
					"name" => "6.jpg",
					"type" => "image/jpeg",
					"tmp_name" => $pathToImages."/6.jpg",
					"error" => "0",
					"size" => @filesize($pathToImages."/6.jpg"),
					"MODULE_ID" => "socialnetwork"
				),
				"NUMBER_OF_MEMBERS" => 1,
				"SPAM_PERMS" => "N",
				"INITIATE_PERMS" => "K",
				"=DATE_ACTIVITY" => $GLOBALS["DB"]->CurrentTimeFunction(),
			),
			7 => array(
				"SITE_ID" => WIZARD_SITE_ID,
				"NAME" => GetMessage("SONET_GROUP_NAME_7"),
				"DESCRIPTION" => GetMessage("SONET_GROUP_DESCRIPTION_7"),
				"=DATE_CREATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
				"=DATE_UPDATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
				"ACTIVE" => "Y",
				"VISIBLE" => "Y",
				"OPENED" => "N",
				"SUBJECT_ID" => $arGroupSubjectsId[1],
				"OWNER_ID" => 1,
				"KEYWORDS" => GetMessage("SONET_GROUP_KEYWORDS_7"),
				"IMAGE_ID" => array(
					"name" => "7.jpg",
					"type" => "image/jpeg",
					"tmp_name" => $pathToImages."/7.jpg",
					"error" => "0",
					"size" => @filesize($pathToImages."/7.jpg"),
					"MODULE_ID" => "socialnetwork"
				),
				"NUMBER_OF_MEMBERS" => 1,
				"INITIATE_PERMS" => "E",
				"SPAM_PERMS" => "N",
				"=DATE_ACTIVITY" => $GLOBALS["DB"]->CurrentTimeFunction(),
			),
			8 => array(
				"SITE_ID" => WIZARD_SITE_ID,
				"NAME" => GetMessage("SONET_GROUP_NAME_8"),
				"DESCRIPTION" => GetMessage("SONET_GROUP_DESCRIPTION_8"),
				"=DATE_CREATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
				"=DATE_UPDATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
				"ACTIVE" => "Y",
				"VISIBLE" => "N",
				"OPENED" => "N",
				"SUBJECT_ID" => $arGroupSubjectsId[3],
				"OWNER_ID" => 1,
				"KEYWORDS" => GetMessage("SONET_GROUP_KEYWORDS_8"),
				"NUMBER_OF_MEMBERS" => 1,
				"INITIATE_PERMS" => "A",
				"SPAM_PERMS" => "N",
				"=DATE_ACTIVITY" => $GLOBALS["DB"]->CurrentTimeFunction(),
			),
			9 => array(
				"SITE_ID" => WIZARD_SITE_ID,
				"NAME" => GetMessage("SONET_GROUP_NAME_9"),
				"DESCRIPTION" => GetMessage("SONET_GROUP_DESCRIPTION_9"),
				"=DATE_CREATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
				"=DATE_UPDATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
				"ACTIVE" => "Y",
				"VISIBLE" => "N",
				"OPENED" => "N",
				"SUBJECT_ID" => $arGroupSubjectsId[3],
				"OWNER_ID" => 1,
				"KEYWORDS" => GetMessage("SONET_GROUP_KEYWORDS_9"),
				"NUMBER_OF_MEMBERS" => 1,
				"INITIATE_PERMS" => "A",
				"SPAM_PERMS" => "N",
				"=DATE_ACTIVITY" => $GLOBALS["DB"]->CurrentTimeFunction(),
			),
			10 => array(
				"SITE_ID" => WIZARD_SITE_ID,
				"NAME" => GetMessage("SONET_GROUP_NAME_10"),
				"DESCRIPTION" => GetMessage("SONET_GROUP_DESCRIPTION_10"),
				"=DATE_CREATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
				"=DATE_UPDATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
				"ACTIVE" => "Y",
				"VISIBLE" => "N",
				"OPENED" => "N",
				"SUBJECT_ID" => $arGroupSubjectsId[3],
				"OWNER_ID" => 1,
				"KEYWORDS" => GetMessage("SONET_GROUP_KEYWORDS_10"),
				"NUMBER_OF_MEMBERS" => 1,
				"INITIATE_PERMS" => "A",
				"SPAM_PERMS" => "N",
				"=DATE_ACTIVITY" => $GLOBALS["DB"]->CurrentTimeFunction(),
			),
		);

		foreach ($arGroups as $ind => $arGroup)
		{
			$dbSubject = CSocNetGroup::GetList(
				array(),
				array(
					"NAME" => $arGroup["NAME"],
					"SITE_ID" => WIZARD_SITE_ID
				)
			);
			if (!$dbSubject->Fetch())
			{
				$idTmp = CSocNetGroup::Add($arGroup);
				if ($idTmp)
				{
					if (CModule::IncludeModule("disk"))
					{
						\Bitrix\Disk\Driver::getInstance()->addGroupStorage($idTmp);
					}
					$arGroupsId[$ind] = IntVal($idTmp);
				}
				else
				{
					if ($e = $GLOBALS["APPLICATION"]->GetException())
						$errorMessage .= $e->GetString();
				}
			}
		}
	}

	if (StrLen($errorMessage) <= 0)
	{
		foreach ($arGroupsId as $ind => $val)
		{
			CSocNetUserToGroup::Add(
				array(
					"USER_ID" => 1,
					"GROUP_ID" => $val,
					"ROLE" => "A",
					"=DATE_CREATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
					"=DATE_UPDATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
					"INITIATED_BY_TYPE" => SONET_INITIATED_BY_USER,
					"INITIATED_BY_USER_ID" => 1,
					"MESSAGE" => false,
				)
			);
		}
	}

	if (StrLen($errorMessage) <= 0)
	{
		// set EUV vor news
		$dbResult = CSocNetEventUserView::GetList(
						array("ENTITY_ID" => "ASC"),
						array(
							"ENTITY_TYPE" => "N",
						)
		);
		$arResult = $dbResult->Fetch();
		if (!$arResult)
		{
			CSocNetEventUserView::Add(
							array(
								"ENTITY_TYPE" => "N",
								"ENTITY_ID" => 0,
								"EVENT_ID" => "news",
								"USER_ID" => 0,
								"USER_ANONYMOUS" => "N"
							)
			);

			CSocNetEventUserView::Add(
							array(
								"ENTITY_TYPE" => "N",
								"ENTITY_ID" => 0,
								"EVENT_ID" => "news_comment",
								"USER_ID" => 0,
								"USER_ANONYMOUS" => "N"
							)
			);
		}

		$blogGroupID = 0;
		if (CModule::IncludeModule("blog"))
		{
			$dbRes = CBlogGroup::GetList(array("ID" => "DESC"), array("SITE_ID" => WIZARD_SITE_ID));
			if ($arRes = $dbRes->Fetch())
				$blogGroupID = $arRes["ID"];
		}

		$forumID = 0;
		$photoForumID = 0;
		if (CModule::IncludeModule("forum"))
		{
			$dbRes = CForumNew::GetListEx(array(), array("SITE_ID" => WIZARD_SITE_ID, "XML_ID" => "USERS_AND_GROUPS"));
			if ($arRes = $dbRes->Fetch())
				$forumID = $arRes["ID"];

			$dbRes = CForumNew::GetListEx(array(), array("XML_ID" => "PHOTOGALLERY_COMMENTS"));
			if ($arRes = $dbRes->Fetch())
				$photoForumID = $arRes["ID"];
		}

		$filesUserIBlockID = 0;
		$filesGroupIBlockID = 0;
		$calendarUserIBlockID = 0;
		$calendarGroupIBlockID = 0;
		$photoUserIBlockID = 0;
		$photoGroupIBlockID = 0;

		if (CModule::IncludeModule("iblock"))
		{
			$dbRes = CIBlock::GetList(array(), array("SITE_ID" => WIZARD_SITE_ID, "CODE" => "user_files"));
			if ($arRes = $dbRes->Fetch())
				$filesUserIBlockID = $arRes["ID"];


			$dbRes = CIBlock::GetList(array(), array("SITE_ID" => WIZARD_SITE_ID, "CODE" => "group_files_".WIZARD_SITE_ID));
			if ($arRes = $dbRes->Fetch())
				$filesGroupIBlockID = $arRes["ID"];

			$dbRes = CIBlock::GetList(array(), array("SITE_ID" => WIZARD_SITE_ID, "CODE" => "calendar_employees"));
			if ($arRes = $dbRes->Fetch())
				$calendarUserIBlockID = $arRes["ID"];

			$dbRes = CIBlock::GetList(array(), array("SITE_ID" => WIZARD_SITE_ID, "CODE" => "calendar_groups_".WIZARD_SITE_ID));
			if ($arRes = $dbRes->Fetch())
				$calendarGroupIBlockID = $arRes["ID"];

			$dbRes = CIBlock::GetList(array(), array("SITE_ID" => WIZARD_SITE_ID, "CODE" => "user_photogallery"));
			if ($arRes = $dbRes->Fetch())
				$photoUserIBlockID = $arRes["ID"];

			$dbRes = CIBlock::GetList(array(), array("SITE_ID" => WIZARD_SITE_ID, "CODE" => "group_photogallery_".WIZARD_SITE_ID));
			if ($arRes = $dbRes->Fetch())
				$photoGroupIBlockID = $arRes["ID"];

		}

		// tasks 2.0
		$arTasks = array(
			array(
				"CREATED_BY" => 1,
				"RESPONSIBLE_ID" => 1,
				"PRIORITY" => 1,
				"STATUS" => 2,
				"TITLE" => GetMessage("SONET_TASK_TITLE_1"),
				"DESCRIPTION" => GetMessage("SONET_TASK_DESCRIPTION_1"),
				"SITE_ID" => WIZARD_SITE_ID,
				"XML_ID" => md5(GetMessage("SONET_TASK_TITLE_1").GetMessage("SONET_TASK_DESCRIPTION_1").WIZARD_SITE_ID)
			),
			array(
				"CREATED_BY" => 1,
				"RESPONSIBLE_ID" => 1,
				"PRIORITY" => 1,
				"STATUS" => 2,
				"TITLE" => GetMessage("SONET_TASK_TITLE_2"),
				"DESCRIPTION" => GetMessage("SONET_TASK_DESCRIPTION_2"),
				"SITE_ID" => WIZARD_SITE_ID,
				"XML_ID" => md5(GetMessage("SONET_TASK_TITLE_2").GetMessage("SONET_TASK_DESCRIPTION_2").WIZARD_SITE_ID)
			)
		);
		if (CModule::IncludeModule("tasks"))
		{
			foreach($arTasks as $task)
			{
				$obTask = new CTasks();
				$strSql = "SELECT ID FROM b_tasks WHERE XML_ID = '".$task["XML_ID"]."'";
				$rsTask = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				if ($oldTask = $rsTask->Fetch())
				{
					$obTask->Update($oldTask["ID"], $task);
				}
				else
				{
					$obTask->Add($task);
				}
			}
		}
		// tasks
		$tasksIblockId = 0;
		$tasksForumId = 0;
		if (CModule::IncludeModule("iblock"))
		{
			$iblockCode = "intranet_tasks";
			$iblockType = "services";

			$rsIBlock = CIBlock::GetList(array(), array("XML_ID" => $iblockCode, "TYPE" => $iblockType));

			if ($arIBlock = $rsIBlock->Fetch())
			{
				$tasksIblockId = $arIBlock["ID"];
			}

			if($tasksIblockId == 0)
			{
				$tasksIblockId = WizardServices::ImportIBlockFromXML(
					WIZARD_SERVICE_RELATIVE_PATH."/xml/".LANGUAGE_ID."/tasks.xml",
					$iblockCode,
					$iblockType,
					WIZARD_SITE_ID,
					Array(
						"1" => "X",
						"2" => "R",
						WIZARD_PORTAL_ADMINISTRATION_GROUP => "X",
					)
				);

				$iblock = new CIBlock;
				$arFields = Array(
					"CODE" => $iblockCode,
					"XML_ID" => $iblockCode,
				);

				$iblock->Update($tasksIblockId, $arFields);
			}
			else
			{
				$arSites = array();
				$db_res = CIBlock::GetSite($tasksIblockId);
				while ($res = $db_res->Fetch())
					$arSites[] = $res["LID"];
				if (!in_array(WIZARD_SITE_ID, $arSites))
				{
					$arSites[] = WIZARD_SITE_ID;
					$iblock = new CIBlock;
					$iblock->Update($tasksIblockId, array("LID" => $arSites));
				}
			}
		}
		if (CModule::IncludeModule("forum"))
		{
			$forumCode = "intranet_tasks";
			$dbRes = CForumNew::GetListEx(array(), array("SITE_ID" => WIZARD_SITE_ID, "XML_ID" => $forumCode));
			if ($arRes = $dbRes->Fetch())
			{
				$tasksForumId = $arRes["ID"];
			}
			else
			{
				$arGroupID = Array(
					"GENERAL" => 0,
					"COMMENTS" => 0,
					"HIDDEN" => 0,
				);
				$dbExistsGroup = CForumGroup::GetListEx(array(), array("LID" => LANGUAGE_ID));
				while ($arExistsGroup = $dbExistsGroup->Fetch())
				{
					foreach ($arGroupID as $xmlID => $ID)
					{
						if ($arExistsGroup["NAME"] == GetMessage($xmlID."_GROUP_NAME") )
							$arGroupID[$xmlID] = $arExistsGroup["ID"];
					}
				}

				$arFields = array(
					"XML_ID" => $forumCode,
					"NAME" => "Intranet Tasks",
					"DESCRIPTION" => false,
					"SORT" => 1,
					"ACTIVE" => "Y",
					"ALLOW_HTML" => "N",
					"ALLOW_ANCHOR" => "Y",
					"ALLOW_BIU" => "Y",
					"ALLOW_IMG" => "Y",
					"ALLOW_LIST" => "Y",
					"ALLOW_QUOTE" => "Y",
					"ALLOW_CODE" => "Y",
					"ALLOW_FONT" => "Y",
					"ALLOW_SMILES" => "Y",
					"ALLOW_UPLOAD" => "A",
					"ALLOW_NL2BR" => "N",
					"MODERATION" => "N",
					"ALLOW_MOVE_TOPIC" => "Y",
					"ORDER_BY" => "P",
					"DEDUPLICATION" => "N",
					"ORDER_DIRECTION" => "DESC",
					"LID" => LANGUAGE_ID,
					"PATH2FORUM_MESSAGE" => "",
					"ALLOW_UPLOAD_EXT" => "",
					"ASK_GUEST_EMAIL" => "N",
					"USE_CAPTCHA" => "N",
					"SITES" => Array(
						WIZARD_SITE_ID => WIZARD_SITE_DIR."community/forum/messages/forum#FORUM_ID#/topic#TOPIC_ID#/message#MESSAGE_ID#/#message#MESSAGE_ID#",
					),
					"EVENT1" => "forum",
					"EVENT2" => "message",
					"EVENT3" => "",
					"GROUP_ID" => Array(
						"2" => "E",
						WIZARD_PORTAL_ADMINISTRATION_GROUP => "Y",
						WIZARD_EMPLOYEES_GROUP => "M",
						WIZARD_PERSONNEL_DEPARTMENT_GROUP => "M",
					),
					"FORUM_GROUP_ID" => $arGroupID["HIDDEN"],
				);

				$tasksForumId = CForumNew::Add($arFields);
			}
		}

		CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/_index.php", Array("TASKS_IBLOCK_ID" => $tasksIblockId));
		CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/desktop.php", Array("TASKS_IBLOCK_ID" => $tasksIblockId));

		$arReplace = Array(
			"BLOG_GROUP_ID" => $blogGroupID,
			"FORUM_ID" => $forumID,
			"FILES_USER_IBLOCK_ID" => $filesUserIBlockID,
			"CALENDAR_USER_IBLOCK_ID" => $calendarUserIBlockID,
			"PHOTO_USER_IBLOCK_ID" => $photoUserIBlockID,
			"PHOTO_FORUM_ID" => $photoForumID,
			"TASKS_IBLOCK_ID" => $tasksIblockId,
			"TASKS_FORUM_ID" => $tasksForumId,
		);

		CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/company/personal.php", $arReplace);
		CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/index_b24.php", $arReplace);
		CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/index.php", $arReplace);

		$arReplace = Array(
			"BLOG_GROUP_ID" => $blogGroupID,
			"FORUM_ID" => $forumID,
			"FILES_GROUP_IBLOCK_ID" => $filesGroupIBlockID,
			"CALENDAR_IBLOCK_ID" => $calendarGroupIBlockID,
			"PHOTO_GROUP_IBLOCK_ID" => $photoGroupIBlockID,
			"PHOTO_FORUM_ID" => $photoForumID,
			"TASKS_IBLOCK_ID" => $tasksIblockId,
			"TASKS_FORUM_ID" => $tasksForumId,
		);

		CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/workgroups/index.php", $arReplace);
		$arReplace = Array(
			"FILES_GROUP_IBLOCK_ID" => $filesGroupIBlockID,
			"FILES_USER_IBLOCK_ID" => $filesUserIBlockID,
		);

		CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/docs/index.php", $arReplace);
		$arReplace = Array(
			"FILES_GROUP_IBLOCK_ID" => $filesGroupIBlockID,
			"FILES_USER_IBLOCK_ID" => $filesUserIBlockID,
			"TASKS_FORUM_ID" => $tasksForumId,
		);

		CWizardUtil::ReplaceMacrosRecursive(WIZARD_SITE_PATH."/m/", $arReplace);

	}
}
?>