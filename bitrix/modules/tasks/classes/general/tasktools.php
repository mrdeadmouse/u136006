<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2013 Bitrix
 */


/**
 * For internal use only, not public API
 * @access private
 */
class CTasksTools
{
	const CACHE_TTL_UNLIM = 32100113;	// 371+ days


	/**
	 * @access private
	 */
	public static function getOccurAsUserId()
	{
		static $key = null;

		if ($key === null)
			$key = self::setOccurAsUserId();	// get key & init

		$userId = CTasksPerHitOption::get('tasks', $key);

		return ($userId);
	}


	public static function setOccurAsUserId($userId = 'get key')
	{
		static $key = null;

		if ($key === null)
		{
			$key = 'occurAs_key:' . md5(mt_rand(1000, 999999) . '-' . mt_rand(1000, 999999));

			if ($userId !== 'get key')
				CTasksPerHitOption::set('tasks', $key, false);
		}

		if ($userId !== 'get key')
		{
			CTaskAssert::assertLaxIntegers($userId);

			$userId = (int) $userId;
			/** @noinspection PhpDynamicAsStaticMethodCallInspection */
			$rs = CUser::getById($userId);

			if ( ! ($rs && $rs->fetch()) )
				throw new TasksException('User not found', TasksException::TE_ITEM_NOT_FOUND_OR_NOT_ACCESSIBLE);

			CTasksPerHitOption::set('tasks', $key, $userId);
		}

		return ($key);
	}


	/**
	 * Not part of public API, for internal use only.
	 * @deprecated
	 * @access private
	 *
	 * @param $n
	 * @param $msgId
	 * @param bool|array $arReplace
	 * @return mixed|string
	 */
	public static function getMessagePlural($n, $msgId, $arReplace = false)
	{
		$pluralForm = self::getPluralForm($n, $returnFalseForUnknown = true);

		if ($pluralForm !== false)
			$msgId .= '_PLURAL_' . $pluralForm;
		else
			$msgId .= '_PLURAL_1';	// use by default

		return(GetMessage($msgId, $arReplace));
	}


	/**
	 * Not part of public API, for internal use only.
	 * @deprecated
	 * @access private
	 */
	public static function getPluralForm($n, $returnFalseForUnknown = false)
	{
		$n = abs((int) $n);

		if ( ! defined('LANGUAGE_ID') )
			return (false);

		// info at http://docs.translatehouse.org/projects/localization-guide/en/latest/l10n/pluralforms.html?id=l10n/pluralforms
		switch (LANGUAGE_ID)
		{
			case 'de':
			case 'en':
				$plural = (int) ($n !== 1);
			break;

			case 'ru':
			case 'ua':
				$plural = ( (($n%10 === 1) && ($n%100 !== 11)) ? 0 : ((($n%10 >= 2) && ($n%10 <= 4) && (($n%100 < 10) || ($n%100 >= 20))) ? 1 : 2) );
			break;

			default:
				if ($returnFalseForUnknown)
					$plural = false;
				else
					$plural = (int) ($n !== 1);
			break;
		}

		return ($plural);
	}


	/**
	 * Not part of public API, for internal use only.
	 * @deprecated
	 * @access private
	 */
	public static function getTimeZoneOffset($userId = false)
	{
		$bTzWasDisabled = ! CTimeZone::enabled();

		if ($bTzWasDisabled)
			CTimeZone::enable();

		if ($userId === false)
			$tzOffset = CTimeZone::getOffset();		// for current user
		else
			$tzOffset = CTimeZone::getOffset($userId);

		if ($bTzWasDisabled)
			CTimeZone::disable();

		return ($tzOffset);
	}


	public static function stripZeroTime($dateTimeStr)
	{
		global $DB;

		$ts = MakeTimeStamp($dateTimeStr);

		// if invalid date => return original string
		if ($ts < 172800)
			return ($dateTimeStr);

		$isTime = (($ts + date('Z', $ts)) % 86400 != 0);

		/** @noinspection PhpDynamicAsStaticMethodCallInspection */
		$processed = FormatDate(
			$DB->DateFormatToPhp(CSite::GetDateFormat($isTime ? 'FULL' : 'SHORT')),
			$ts
		);

		return ($processed);
	}


	public static function isIntegerValued($i)
	{
		return (CTaskAssert::isLaxIntegers($i));
	}


	/**
	 *
	 * Generate v4 UUID
	 *
	 * Version 4 UUIDs are pseudo-random.
	 */
	public static function genUuid($brackets = true)
	{
		$uuid = '';

		if ($brackets)
			$uuid .= '{';

		$uuid .= sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			// 32 bits for "time_low"
			mt_rand(0, 0xffff), mt_rand(0, 0xffff),

			// 16 bits for "time_mid"
			mt_rand(0, 0xffff),

			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 4
			mt_rand(0, 0x0fff) | 0x4000,

			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			mt_rand(0, 0x3fff) | 0x8000,

			// 48 bits for "node"
			mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
		);

		if ($brackets)
			$uuid .= '}';

		return ($uuid);
	}


	public function __call($name, $arguments)
	{
		$nameHash = md5(strtolower($name));

		if (
			($nameHash === 'b6a4f4c2248041c6e78365b01996ceee')
			|| ($nameHash === 'f87703ae62fcc4f943f1a9acaa1c3348')
		)
		{
			return call_user_func_array('CTasksTools::FormatDatetimeBeauty', $arguments);
		}

		throw new Exception();
	}


	public static function __callStatic($name, $arguments)
	{
		$nameHash = md5(strtolower($name));

		if (
			($nameHash === 'b6a4f4c2248041c6e78365b01996ceee')
			|| ($nameHash === 'f87703ae62fcc4f943f1a9acaa1c3348')
		)
		{
			return call_user_func_array('CTasksTools::FormatDatetimeBeauty', $arguments);
		}

		throw new Exception();
	}


	public static function IsIphoneOrIpad()
	{
		if (
			(strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone') !== false)
			|| (strpos($_SERVER['HTTP_USER_AGENT'], 'iPad') !== false)
		)
			return (true);
		else
			return (false);
	}


	public static function FormatDatetimeBeauty($in, $arParams = array(), 
		$formatDatetimePHP = false
	)
	{
		if ($formatDatetimePHP === false)
		{
			/** @noinspection PhpDynamicAsStaticMethodCallInspection */
			$formatDatetimePHP = CDatabase::DateFormatToPHP(FORMAT_DATETIME);
		}

		if (
			defined(LANGUAGE_ID)
			&& (strcasecmp(LANGUAGE_ID, 'EN') !== 0)
			&& (strcasecmp(LANGUAGE_ID, 'DE') !== 0)
		)
		{
			return (FormatDate($formatDatetimePHP, MakeTimeStamp($in)));
		}

		$bStripCurrentYear = true;
		$bSexySmallMonthNames = true;

		if (isset($arParams['stripCurrentYear']))
			$bStripCurrentYear = (bool) $arParams['stripCurrentYear'];

		if (isset($arParams['sexySmallMonthNames']))
			$bSexySmallMonthNames = (bool) $arParams['sexySmallMonthNames'];

		if ($bSexySmallMonthNames)
		{
			// Replace month number (or long name) to short name
			$formatDatetimePHP = str_replace(array('F', 'm', 'n'), 'M', $formatDatetimePHP);

			// Replace, for example, "05.Dec" to "05 Dec"
			$formatDatetimePHP = str_replace(array('d.M', 'j.M'), array('d M', 'j M'), $formatDatetimePHP);
		}

		if (
			(strpos($formatDatetimePHP, 'A') !== false)
			|| (strpos($formatDatetimePHP, 'a') !== false)
		)
		{
			/** @noinspection PhpDynamicAsStaticMethodCallInspection */
			$formatTimePHP = CDatabase::DateFormatToPHP('H:MI T');
		}
		else
		{
			/** @noinspection PhpDynamicAsStaticMethodCallInspection */
			$formatTimePHP = CDatabase::DateFormatToPHP('HH:MI');
		}

		$bTimeStripped = false;
		$formatPHP = $formatDatetimePHP;

		// Strip time, if it's zeroed
		/** @noinspection PhpDynamicAsStaticMethodCallInspection */
		if (
			(FormatDate(CDatabase::DateFormatToPHP('HH:MI'), MakeTimeStamp($in)) === '00:00')
			|| (FormatDate(CDatabase::DateFormatToPHP('HH:MI'), MakeTimeStamp($in)) === '0:00')
		)
		{
			$bTimeStripped = true;
			$formatPHP = str_replace(
				array(
					' a', ' A', 'a', 'A',
					' g', ' G', 'g', 'G',
					' h', ' H', 'h', 'H',
					':i', ':s', 'i', 's'
				), 
				'',
				$formatPHP
			);
		}

		// For current date strip date
		if ( ! $bTimeStripped )
		{
			/** @noinspection PhpDynamicAsStaticMethodCallInspection */
			$curDate = FormatDate(CDatabase::DateFormatToPHP('Y-m-d'), MakeTimeStamp(time()));
			/** @noinspection PhpDynamicAsStaticMethodCallInspection */
			$givenDate = FormatDate(CDatabase::DateFormatToPHP('Y-m-d'), MakeTimeStamp($in));

			if ($curDate === $givenDate)
				$formatPHP = $formatTimePHP;
		}

		$formatPHPWoSeconds = str_replace(
			array(':SS', ':ss', ':S', ':s', 's', 'S'), 
			'', 
			$formatPHP
		);

		$dateTimeFormated = FormatDate($formatPHPWoSeconds, MakeTimeStamp($in));

		if (defined(LANGUAGE_ID)
			&& (strcasecmp(LANGUAGE_ID, 'EN') !== 0) 
			&& (strcasecmp(LANGUAGE_ID, 'DE') !== 0)
		)
		{
			$dateTimeFormated = ToLower($dateTimeFormated);
		}

		// strip current year
		if ($bStripCurrentYear)
		{
			$dateTimeFormated = ltrim($dateTimeFormated, '0');
			$curYear = date('Y');
			$dateTimeFormated = str_replace(
				array(
					'-' . $curYear, 
					'/' . $curYear, 
					' ' . $curYear, 
					'.' . $curYear,
					$curYear . '-', 
					$curYear . '/', 
					$curYear . ' ', 
					$curYear . '.'
				), 
				'', 
				$dateTimeFormated
			);
		}

		return ($dateTimeFormated);
	}


	/**
	 * Sanitize tasks description if sanitizer enabled in task module options
	 */
	public static function SanitizeHtmlDescriptionIfNeed($rawHtml)
	{
		static $bUseHtmlSanitizer = null;
		static $oSanitizer = null;
	
		// Init sanitizer (if we need it) only once at hit
		if ($bUseHtmlSanitizer === null)
		{
			$bSanitizeLevel = COption::GetOptionString('tasks', 'sanitize_level');
	
			if ($bSanitizeLevel >= 0)
			{
				$bUseHtmlSanitizer = true;

				if ( ! 
					in_array(
						$bSanitizeLevel, 
						array(
							CBXSanitizer::SECURE_LEVEL_HIGH,
							CBXSanitizer::SECURE_LEVEL_MIDDLE,
							CBXSanitizer::SECURE_LEVEL_LOW
						)
					)
				)
				{
					$bSanitizeLevel = CBXSanitizer::SECURE_LEVEL_HIGH;
				}
	
				$oSanitizer = new CBXSanitizer();
				$oSanitizer->SetLevel($bSanitizeLevel);
				$oSanitizer->AddTags(
					array(
						'blockquote' => array('style', 'class', 'id'),
						'colgroup'   => array('style', 'class', 'id'),
						'col'        => array('style', 'class', 'id', 'width', 'height', 'span', 'style')
					)
				);
				$oSanitizer->ApplyHtmlSpecChars(true);

				// if we don't disable this, than text such as "df 1 < 2 dasfa and 5 > 4 will be partially lost"
				$oSanitizer->DeleteSanitizedTags(false);
			}
			else
				$bUseHtmlSanitizer = false;
		}
	
		if ( ! $bUseHtmlSanitizer )
			return ($rawHtml);

		return ($oSanitizer->SanitizeHtml(htmlspecialcharsback($rawHtml)));
	}


	/**
	 * @param integer $userId
	 * @param integer $groupId
	 * @return bool true if user can access group, false otherwise
	 */
	public static function HasUserReadAccessToGroup ($userId, $groupId)
	{
		// Roles allowed for extranet user to grant access to read task in group
		static $arAllowedRoles = array(
			SONET_ROLES_MODERATOR, 
			SONET_ROLES_USER, 
			SONET_ROLES_OWNER
		);

		if ( ! CModule::IncludeModule('socialnetwork') )
			return (false);

		if ( ! (($userId > 0) && ($groupId > 0)) )
			return (false);

		if (self::IsIntranetUser($userId))
		{
			/** @noinspection PhpDynamicAsStaticMethodCallInspection */
			return (CSocNetGroup::CanUserViewGroup($userId, $groupId));
		}

		/** @noinspection PhpDynamicAsStaticMethodCallInspection */
		$userRole = CSocNetUserToGroup::GetUserRole($userId, $groupId);

		if (in_array($userRole, $arAllowedRoles))
			return (true);

		return (false);
	}


	public static function IsIntranetUser($userId)
	{
		/** @noinspection PhpDynamicAsStaticMethodCallInspection */
		if (in_array(1, CUser::GetUserGroup($userId)))
			return true;

		$rsUsers = CUser::GetList(
			$by = "id", 
			$order = "asc", 
			array("ID" => $userId), 
			array("SELECT" => array("UF_DEPARTMENT"))
		);

		if (
			($arUser = $rsUsers->Fetch())
			&& (intval($arUser["UF_DEPARTMENT"][0]) > 0)
		)
		{
			return true;
		}

		return false;
	}


	// This event handler replace path for comments to tasks to right way
	public static function FixForumCommentURL($arData)
	{
		if (
			($arData['MODULE_ID'] !== 'FORUM')
			&& ($arData['MODULE_ID'] !== 'forum')
		)
		{
			return (null);
		}

		if ($arData['ENTITY_TYPE_ID'] !== 'FORUM_POST')
			return (null);

		// $arData['PARAM1'] is FORUM_ID
		// Check that forum is of tasks
		$arTasksForums = self::ListTasksForumsAsArray();
		if ( ! in_array( (int) $arData['PARAM1'], $arTasksForums, true) )
			return (null);

		// Get tasks data
		$rsTask = CTasks::GetList(
			array(), 
			array('FORUM_TOPIC_ID' => $arData['PARAM2'])
			);
		$arTask = $rsTask->Fetch();
		if ( ! $arTask )
			return (null);

		// Prepare path
		/** @noinspection PhpDynamicAsStaticMethodCallInspection */
		$defSiteId = CSite::GetDefSite();
		$extranetSiteId = false;
		if (CModule::IncludeModule('extranet')
			&& method_exists('CExtranet', 'GetExtranetSiteID')
		)
		{
			/** @noinspection PhpDynamicAsStaticMethodCallInspection */
			$extranetSiteId = CExtranet::GetExtranetSiteID();
		}

		$arFilter = array(
			'UF_DEPARTMENT' => false,
			'ID'            => $arData['USER_ID']
			);

		$rsUser = CUser::GetList(
			$by = 'last_name', 
			$order = 'asc', 
			$arFilter, 
			array('SELECT' => array('UF_DEPARTMENT'))
			);

		$isExtranetUser = false;

		if ($arUser = $rsUser->Fetch())
			$isExtranetUser = true;

		if ($isExtranetUser && ($extranetSiteId !== false))
		{
			if ($arTask["GROUP_ID"])
			{
				$pathTemplate = str_replace(
					"#group_id#", 
					$arTask["GROUP_ID"], 
					COption::GetOptionString(
						"tasks", 
						"paths_task_group_entry", 
						"/extranet/workgroups/group/#group_id#/tasks/task/view/#task_id#/", 
						$extranetSiteId
						)
					);

				$pathTemplate = str_replace(
					"#GROUP_ID#", 
					$arTask["GROUP_ID"], 
					$pathTemplate
					);
			}
			else
			{
				$pathTemplate = COption::GetOptionString(
					"tasks", 
					"paths_task_user_entry", 
					"/extranet/contacts/personal/user/#user_id#/tasks/task/view/#task_id#/", 
					$extranetSiteId
					);
			}
		}
		else
		{
			if ($arTask["GROUP_ID"])
			{
				$pathTemplate = str_replace(
					"#group_id#", 
					$arTask["GROUP_ID"], 
					COption::GetOptionString(
						"tasks", 
						"paths_task_group_entry", 
						"/workgroups/group/#group_id#/tasks/task/view/#task_id#/", 
						$defSiteId
						)
					);

				$pathTemplate = str_replace(
					"#GROUP_ID#", 
					$arTask["GROUP_ID"], 
					$pathTemplate
					);
			}
			else
			{
				$pathTemplate = COption::GetOptionString(
					"tasks", 
					"paths_task_user_entry", 
					"/company/personal/user/#user_id#/tasks/task/view/#task_id#/", 
					$defSiteId
					);
			}
		}

		$messageUrl = CComponentEngine::MakePathFromTemplate(
			$pathTemplate, 
			array(
				"user_id" => $arData['USER_ID'], 
				"task_id" => $arTask["ID"], 
				"action"  => "view"
				)
			);
		
		if (strlen($arData['ENTITY_ID']))
		{
			if (strpos($messageUrl, "?") === false)
				$messageUrl .= "?";
			else
				$messageUrl .= "&";
			
			$messageUrl .= "MID=" . $arData['ENTITY_ID']
				. '#message' . $arData['ENTITY_ID'];
		}	

		// Replace path to correct in URL
		$arData['URL'] = $messageUrl;

		// For extranet users address taken from default site by "like/dislike" feature
		// I don't know why. So replace all links.
		foreach ($arData['LID'] as $siteId => $value)
			$arData['LID'][$siteId] = $messageUrl;

		return ($arData);
	}


	/**
	 * return bool true if current ot given user is admin.
	 */
	public static function IsAdmin($userId = null)
	{
		global $USER;
		static $arCache = array();

		$isAdmin = false;
		$loggedInUserId = null;

		if ($userId === null)
		{
			if (is_object($USER) && method_exists($USER, 'GetID'))
			{
				$loggedInUserId = (int) $USER->GetID();
				$userId = $loggedInUserId;
			}
			else
				$loggedInUserId = false;
		}

		if ($userId > 0)
		{
			if ( ! isset($arCache[$userId]) )
			{
				if ($loggedInUserId === null)
				{
					if (is_object($USER) && method_exists($USER, 'GetID'))
						$loggedInUserId = (int) $USER->GetID();
				}

				if ((int)$userId === $loggedInUserId)
					$arCache[$userId] = (bool) $USER->isAdmin();
				else
				{
					/** @noinspection PhpDynamicAsStaticMethodCallInspection */
					$ar = CUser::GetUserGroup($userId);
					if (in_array(1, $ar, true) || in_array('1', $ar, true))
						$arCache[$userId] = true;	// user is admin
					else
						$arCache[$userId] = false;	// user isn't admin
				}
			}

			$isAdmin = $arCache[$userId];
		}

		return ($isAdmin);
	}


	/**
	 * return bool true if we at Bitrix24 portal and current (or given) user is admin.
	 */
	public static function IsPortalB24Admin($userId = null)
	{
		static $isB24 = null;
		static $arCache = array();
		global $USER;

		$isPortalAdmin = false;

		// Precache IsModuleInstalled
		if ($isB24 === null)
		{
			$isB24 = false;

			if (IsModuleInstalled('bitrix24')
				&& CModule::IncludeModule('bitrix24')
			)
			{
				$isB24 = true;
			}
		}

		if ($isB24)
		{
			if ($userId === null)
			{
				if (is_object($USER) && method_exists($USER, 'GetID'))
					$userId = (int) $USER->GetID();
			}

			if ( ! isset($arCache[$userId]) )
				$arCache[$userId] = (boolean) CBitrix24::IsPortalAdmin($userId);

			$isPortalAdmin = $arCache[$userId];
		}

		return ($isPortalAdmin);
	}

	public static function GetCommanderInChief()
	{
		global $USER;

		if(is_object($USER) && method_exists($USER, 'isAdmin') && method_exists($USER, 'GetID') && $USER->isAdmin())
			return $USER->GetID();

		$user = CUser::GetList(
			($by = 'id'),
			($sort = 'asc'),
			array('GROUPS_ID' => array(1), 'ACTIVE' => 'Y'),
			array('FIELDS' => array('ID'), 'NAV_PARAMS' => array('nTopCount' => 1))
		)->fetch();

		if(is_array($user) && intval($user['ID']))
			return intval($user['ID']);

		return false;
	}

	/**
	 * For internal use only, not public API
	 * @access private
	 * @throws TasksException
	 * @return array of integers (ids of forums)
	 */
	public static function ListTasksForumsAsArray()
	{
		$arForumsIDs = array();

		try
		{
			$arForumsIDs[] = self::GetForumIdForIntranet();
		}
		catch (TasksException $e)
		{
		}

		try
		{
			$arForumsIDs[] = self::GetForumIdForExtranet();
		}
		catch (TasksException $e)
		{
		}

		if (IsModuleInstalled('forum') && CModule::IncludeModule('forum'))
		{
			$arXmlIds = array(
				'GROUPS_AND_USERS_TASKS_COMMENTS_EXTRANET',
				'intranet_tasks'
			);

			$arOrder  = array();

			foreach ($arXmlIds as $xmlId)
			{
				$arFilter = array('XML_ID' => $xmlId);

				/** @noinspection PhpDynamicAsStaticMethodCallInspection */
				$rc = CForumNew::GetList($arOrder, $arFilter);
				
				while ($arForum = $rc->Fetch())
					$arForumsIDs[] = (int) $arForum['ID'];
			}
		}

		return (array_unique($arForumsIDs));
	}


	/**
	 * For internal use only, not public API
	 * @access private
	 * @throws TasksException
	 * @return integer
	 */
	public static function GetForumIdForIntranet()
	{
		$forumId = COption::GetOptionString('tasks', 'task_forum_id', -1, $siteId = '');

		if ( ! ($forumId > 0) )
			$forumId = self::TryToDetermineForumIdForIntranet();

		return ((int) $forumId);
	}


	/**
	 * For internal use only, not public API
	 * @access private
	 * @throws TasksException
	 * @return integer
	 */
	public static function GetForumIdForExtranet()
	{
		$forumId = COption::GetOptionString('tasks', 'task_extranet_forum_id', -1, $siteId = '');

		if ( ! ($forumId > 0) )
			$forumId = self::TryToDetermineForumIdForExtranet();

		return ((int) $forumId);
	}


	/**
	 * For internal use only, not public API
	 * @access private
	 * @throws TasksException
	 * @return integer
	 */
	public static function TryToDetermineForumIdForIntranet()
	{
		$XML_ID = 'intranet_tasks';
		$forumId = self::GetForumIdByXMLID ($XML_ID);
		return ($forumId);
	}


	/**
	 * For internal use only, not public API
	 * @access private
	 * @throws TasksException
	 * @return integer
	 */
	public static function TryToDetermineForumIdForExtranet()
	{
		$XML_ID = 'GROUPS_AND_USERS_TASKS_COMMENTS_EXTRANET';
		$forumId = self::GetForumIdByXMLID ($XML_ID);
		return ($forumId);
	}


	/**
	 * @access private
	 *
	 * @param $XML_ID
	 * @throws TasksException
	 * @return integer
	 */
	protected static function GetForumIdByXMLID ($XML_ID)
	{
		if ( ! (IsModuleInstalled('forum') && CModule::IncludeModule('forum')) )
			throw new TasksException();

		$arOrder  = array();
		$arFilter = array('XML_ID' => $XML_ID);

		/** @noinspection PhpDynamicAsStaticMethodCallInspection */
		$rc = CForumNew::GetList($arOrder, $arFilter);
		$arForum = $rc->Fetch();

		if ( ! isset($arForum['ID']) )
			throw new TasksException();
		
		return ((int) $arForum['ID']);
	}


	/**
	 * This is workaround for some troubles with options
	 * 
	 * @access private
	 * @param string $siteId of option
	 * @param string $defaultValue, if option is not set
	 * 
	 * @throws TasksException
	 * @return string
	 */
	public static function GetOptionPathTaskUserEntry($siteId, $defaultValue)
	{
		return (self::GetOptionPathTaskXXXEntry('user', $siteId, $defaultValue));
	}


	/**
	 * This is workaround for some troubles with options
	 * 
	 * @access private
	 * @param string $siteId of option
	 * @param string $defaultValue, if option is not set
	 * 
	 * @throws TasksException
	 * @return string
	 */
	public static function GetOptionPathTaskGroupEntry($siteId, $defaultValue)
	{
		return (self::GetOptionPathTaskXXXEntry('group', $siteId, $defaultValue));
	}


	protected static function GetOptionPathTaskXXXEntry($nameComponent, $siteId, $defaultValue)
	{
		static $arModules = array(
			'tasks',
			'intranet',
			'extranet'
			);

		$argsCheck = is_string($nameComponent)
			&& is_string($siteId)
			&& is_string($defaultValue);

		if ( ! $argsCheck )
			throw new TasksException();

		$arOptNames = array(
			'paths_task_' . $nameComponent . '_entry',
			'path_task_' . $nameComponent . '_entry');

		// marker which means that option is not set, great confidence level
		$nullMarker = '-1';

		if ($defaultValue === $nullMarker)
			$nullMarker = '-2';

		$bDataGathered = false;
		$rc = false;
		foreach ($arModules as $moduleId)
		{
			if ($bDataGathered)
				break;

			foreach ($arOptNames as $optionName)
			{
				if ($bDataGathered)
					break;

				$rc = COption::GetOptionString($moduleId, $optionName, $nullMarker, $siteId);
				if ($rc !== $nullMarker)
				{
					$bDataGathered = true;
					break;
				}
			}
		}

		if ( ! $bDataGathered )
			$rc = $defaultValue;

		return ($rc);
	}


	public static function getRandFunction()
	{
		global $DBType;

		$dbtype = strtolower($DBType);

		switch ($dbtype)
		{
			case 'mysql':
				return ' RAND(' . rand(0, 1000000) . ') ';
			break;

			case 'mssql':
				return ' newid() ';
			break;

			case 'oracle':
				return ' DBMS_RANDOM.RANDOM() ';
			break;

			default:
				CTaskAssert::log('unknown DB type: ' . $dbtype, CTaskAssert::ELL_ERROR);
				return ' ID ';
			break;
		}
	}


	public static function getPopupOptions()
	{
		/** @noinspection PhpParamsInspection */
		return(
			CUserOptions::GetOption(
				'tasks',
				'popup_options',
				array(
					'opened_description' => 'N',
					'task_control'       => 'Y',
					'time_tracking'      => 'N'
				)
			)
		);
	}


	public static function savePopupOptions($value)
	{
		CUserOptions::SetOption(
			'tasks',
			'popup_options',
			$value
		);
	}
}
