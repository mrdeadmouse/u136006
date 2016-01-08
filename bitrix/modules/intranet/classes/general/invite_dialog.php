<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2014 Bitrix
 */

IncludeModuleLangFile(__FILE__);

class CIntranetInviteDialog
{
	public static $bSendPassword = false;

	function ShowInviteDialogLink($arParams = array())
	{
		CJSCore::Init(array('popup'));
		$arParams["MESS"] = array(
			"BX24_INVITE_TITLE_INVITE" => GetMessage("BX24_INVITE_TITLE_INVITE"),
			"BX24_INVITE_TITLE_ADD" => GetMessage("BX24_INVITE_TITLE_ADD"),
			"BX24_INVITE_BUTTON" => GetMessage("BX24_INVITE_BUTTON"),
			"BX24_CLOSE_BUTTON" => GetMessage("BX24_CLOSE_BUTTON"),
			"BX24_LOADING" => GetMessage("BX24_LOADING"),
		);
		return "B24.Bitrix24InviteDialog.ShowForm(".CUtil::PhpToJSObject($arParams).")";
	}

	public static function setSendPassword($value)
	{
		self::$bSendPassword = $value;
	}

	public static function getSendPassword()
	{
		return self::$bSendPassword;
	}

	function AddNewUser($SITE_ID, $arFields, &$strError)
	{
		$ID_ADDED = 0;

		$iDepartmentId = intval($arFields["DEPARTMENT_ID"]);
		$bExtranet = ($iDepartmentId <= 0);

		$strEmail = trim($arFields["ADD_EMAIL"]);
		$strName = trim($arFields["ADD_NAME"]);
		$strLastName = trim($arFields["ADD_LAST_NAME"]);
		$strPosition = trim($arFields["ADD_POSITION"]);
		$strPassword = self::GeneratePassword($SITE_ID, $bExtranetUser);
		self::setSendPassword($arFields["ADD_SEND_PASSWORD"] == "Y");

		if (strlen($strEmail) <= 0)
		{
			if (
				!isset($arFields["ADD_MAILBOX_ACTION"])
				|| !in_array($arFields["ADD_MAILBOX_ACTION"], array("create", "connect"))
				|| strlen($arFields['ADD_MAILBOX_USER']) <= 0
				|| strlen($arFields['ADD_MAILBOX_DOMAIN']) <= 0
			)
			{
				$strError = GetMessage("BX24_INVITE_DIALOG_ERROR_EMPTY_EMAIL");
			}
			else
			{
				// email from mailbox
				$strEmail = $arFields['ADD_MAILBOX_USER']."@".$arFields['ADD_MAILBOX_DOMAIN'];
			}
		}

		if (!$strError)
		{
			$arUser = array(
				"LOGIN" => $strEmail,
				"NAME" => $strName,
				"LAST_NAME" => $strLastName,
				"EMAIL" => $strEmail,
				"PASSWORD" => $strPassword,
				"GROUP_ID" => CIntranetInviteDialog::getUserGroups($SITE_ID, $bExtranet),
				"WORK_POSITION" => $strPosition,
				"UF_DEPARTMENT" => ($iDepartmentId > 0 ? array($iDepartmentId) : array(0))
			);

			if (!self::getSendPassword())
			{
				$arUser["CONFIRM_CODE"] = randString(8);
			}

			$obUser = new CUser;
			$ID_ADDED = $obUser->Add($arUser);

			if (!$ID_ADDED)
			{
				if($e = $GLOBALS["APPLICATION"]->GetException())
				{
					$strError = $e->GetString();
				}
				else
				{
					$strError = $obUser->LAST_ERROR;
				}
			}
			else
			{
				if (self::getSendPassword())
				{
					$db_events = GetModuleEvents("main", "OnUserInitialize", true);
					foreach($db_events as $arEvent)
					{
						ExecuteModuleEventEx($arEvent, array($ID_ADDED, $arUser));
					}
				}

				$SiteIdToSend = ($bExtranet && CModule::IncludeModule("extranet") ? CExtranet::GetExtranetSiteID() : CSite::GetDefSite());
				$rsSites = CSite::GetByID($SiteIdToSend);
				$arSite = $rsSites->Fetch();
				$serverName = (
					strlen($arSite["SERVER_NAME"]) > 0
						? $arSite["SERVER_NAME"]
						: (
							defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME) > 0
								? SITE_SERVER_NAME
								: COption::GetOptionString("main", "server_name", "")
						)
				);

				$event = new CEvent;
				if (self::getSendPassword())
				{
					$url = (CMain::IsHTTPS() ? "https" : "http")."://".$serverName.$arSite["DIR"];
					$event->SendImmediate("INTRANET_USER_ADD", $SITE_ID, array(
						"EMAIL_TO" => $arUser["EMAIL"],
						"LINK" => $url,
						"PASSWORD" => $strPassword,
						"USER_TEXT" => GetMessage("BX24_INVITE_DIALOG_INVITE_MESSAGE_TEXT")
					));
				}
				else
				{
					$dbUser = CUser::GetByID($ID_ADDED);
					$arUser = $dbUser->Fetch();

					if (IsModuleInstalled("bitrix24"))
					{
						$event->SendImmediate("BITRIX24_USER_INVITATION", $SITE_ID, array(
							"EMAIL_FROM" => $GLOBALS["USER"]->GetEmail(),
							"EMAIL_TO" => $arUser["EMAIL"],
							"LINK" => CHTTP::URN2URI("/bitrix/tools/intranet_invite_dialog.php?user_id=".$ID_ADDED."&checkword=".urlencode($arUser["CONFIRM_CODE"]), $serverName),
							"USER_TEXT" => GetMessage("BX24_INVITE_DIALOG_INVITE_MESSAGE_TEXT")
						));
					}
					else
					{
						$event->SendImmediate("INTRANET_USER_INVITATION", $SITE_ID, array(
							"EMAIL_TO" => $arUser["EMAIL"],
							"LINK" => CHTTP::URN2URI("/bitrix/tools/intranet_invite_dialog.php?user_id=".$ID_ADDED."&checkword=".urlencode($arUser["CONFIRM_CODE"]), $serverName),
							"USER_TEXT" => GetMessage("BX24_INVITE_DIALOG_INVITE_MESSAGE_TEXT")
						));
					}
				}
			}
		}

		return $ID_ADDED;
	}

	function RegisterNewUser($SITE_ID, $arFields, &$arError)
	{
		$arCreatedUserId = array();
		$arEmailToRegister = array();
		$arEmailToReinvite = array();
		$arEmailExist = array();
		$bExtranetUser = false;
		$bExtranetInstalled = (IsModuleInstalled("extranet") && strlen(COption::GetOptionString("extranet", "extranet_site")) > 0);

		if ($arFields["EMAIL"] <> '')
		{
			$arEmailOriginal = preg_split("/[\n\r\t\\,;\\ ]+/", trim($arFields["EMAIL"]));

			$errorEmails = array();
			$arEmail = array();
			foreach($arEmailOriginal as $addr)
			{
				if(strlen($addr) > 0 && check_email($addr))
				{
					$arEmail[] = $addr;
				}
				else
				{
					$errorEmails[] = $addr;
				}
			}
			if (count($arEmailOriginal) > count($arEmail))
			{
				$arError = array(GetMessage("BX24_INVITE_DIALOG_EMAIL_ERROR").implode("<br/>", $errorEmails));
				return false;
			}

			foreach($arEmail as $email)
			{
				$arFilter = array(
					"=EMAIL"=>$email
				);

				$rsUser = CUser::GetList(
					($by="id"),
					($order="asc"),
					$arFilter,
					array(
						"FIELDS" => array("ID", "LAST_LOGIN", "CONFIRM_CODE"),
						"SELECT" => array("UF_DEPARTMENT")
					)
				);
				$bFound = false;
				while ($arUser = $rsUser->GetNext())
				{
					$bFound = true;

					if (
						$arUser["CONFIRM_CODE"] != ""
						&& (
							!$bExtranetInstalled
							|| ( // both intranet
								isset($arFields["DEPARTMENT_ID"])
								&& intval($arFields["DEPARTMENT_ID"]) > 0
								&& isset($arUser["UF_DEPARTMENT"])
								&& (
									(
										is_array($arUser["UF_DEPARTMENT"])
										&& intval($arUser["UF_DEPARTMENT"][0]) > 0
									)
									|| (
										!is_array($arUser["UF_DEPARTMENT"])
										&& intval($arUser["UF_DEPARTMENT"]) <= 0
									)
								)
							)
							||
							(	// both extranet
								(
									!isset($arFields["DEPARTMENT_ID"])
									|| intval($arFields["DEPARTMENT_ID"]) <= 0
								)
								&& (
									!isset($arUser["UF_DEPARTMENT"])
									|| (
										is_array($arUser["UF_DEPARTMENT"])
										&& intval($arUser["UF_DEPARTMENT"][0]) <= 0
									)
									|| (
										!is_array($arUser["UF_DEPARTMENT"])
										&& intval($arUser["UF_DEPARTMENT"]) <= 0
									)
								)
							)
						)
					)
					{
						$arEmailToReinvite[] = array(
							"EMAIL" => $email,
							"REINVITE" => true,
							"ID" => $arUser["ID"],
							"CONFIRM_CODE" => $arUser["CONFIRM_CODE"],
							"UF_DEPARTMENT" => $arUser["UF_DEPARTMENT"]
						);
					}
					else
					{
						$arEmailExist[] = $email;
					}
				}

				if (!$bFound )
				{
					$arEmailToRegister[] = array(
						"EMAIL" => $email,
						"REINVITE" => false
					);
				}
			}
		}

		$moduleID = (IsModuleInstalled("bitrix24")? "bitrix24" : "intranet");
		$messageText = (isset($arFields["MESSAGE_TEXT"])? htmlspecialcharsbx($arFields["MESSAGE_TEXT"]) : GetMessage("BX24_INVITE_DIALOG_INVITE_MESSAGE_TEXT"));
		if (isset($arFields["MESSAGE_TEXT"]))
		{
			CUserOptions::SetOption($moduleID, "invite_message_text", $arFields["MESSAGE_TEXT"]);
		}

		if (
			count($arEmailToRegister) <= 0
			&& count($arEmailToReinvite) <= 0
		)
		{
			$arError = array(GetMessage(!empty($arEmailExist) ? "BX24_INVITE_DIALOG_USER_EXIST_ERROR" : "BX24_INVITE_DIALOG_ERROR_EMPTY_EMAIL_LIST"));
			return false;
		}

		//reinvite users
		foreach ($arEmailToReinvite as $userData)
		{
			self::InviteUser($userData, $messageText, $SITE_ID);
		}

		//register users
		if (!empty($arEmailToRegister))
		{
			if (
				IsModuleInstalled("bitrix24")
				&& !self::checkUsersCount(count($arEmailToRegister))
			)
			{
				$arError = array(GetMessage("BX24_INVITE_DIALOG_MAX_COUNT_ERROR"));
				return false;
			}

			if (isset($arFields["DEPARTMENT_ID"]))
			{
				$arFields["UF_DEPARTMENT"] = $arFields["DEPARTMENT_ID"];
			}

			if (
				!(
					isset($arFields["UF_DEPARTMENT"])
					&& intval($arFields["UF_DEPARTMENT"]) > 0
				)
			)
			{
				if (!$bExtranetInstalled)
				{
					$rsIBlock = CIBlock::GetList(array(), array("CODE" => "departments"));
					$arIBlock = $rsIBlock->Fetch();
					$iblockID = $arIBlock["ID"];

					$db_up_department = CIBlockSection::GetList(
						array(),
						array(
							"SECTION_ID" => 0,
							"IBLOCK_ID" => $iblockID
						)
					);
					if ($ar_up_department = $db_up_department->Fetch())
					{
						$arFields["UF_DEPARTMENT"] = $ar_up_department['ID'];
					}
				}
				else
				{
					$bExtranetUser = true;
				}
			}

			$arGroups = self::getUserGroups($SITE_ID, $bExtranetUser);

			foreach ($arEmailToRegister as $userData)
			{
				$userData["CONFIRM_CODE"] = randString(8);
				$userData["GROUP_ID"] = $arGroups;
				$userData["UF_DEPARTMENT"] = $arFields["UF_DEPARTMENT"];
				$ID = self::RegisterUser($userData, $SITE_ID);

				if(is_array($ID))
				{
					$arError = $ID;
					return false;
				}
				else
				{
					$arCreatedUserId[] = $ID;
					$userData['ID'] = $ID;
					self::InviteUser($userData, $messageText, $SITE_ID);
				}
			}
		}

		if (!empty($arEmailExist))
		{
			$arError = array(GetMessage("BX24_INVITE_DIALOG_USER_EXIST_ERROR"));
			return false;
		}
		else
		{
			return $arCreatedUserId;
		}
	}

	public static function getUserGroups($SITE_ID, $bExtranetUser = false)
	{
		$arGroups = array();

		if (
			$bExtranetUser
			&& CModule::IncludeModule("extranet")
		)
		{
			$extranetGroupID = CExtranet::GetExtranetUserGroupID();
			if (intval($extranetGroupID) > 0)
			{
				$arGroups[] = $extranetGroupID;
			}
		}
		else
		{
			$rsGroups = CGroup::GetList(
				$o="",
				$b="",
				array(
					"STRING_ID" => "EMPLOYEES_".$SITE_ID
				)
			);
			while($arGroup = $rsGroups->Fetch())
			{
				$arGroups[] = $arGroup["ID"];
			}
		}

		return $arGroups;
	}

	public static function checkUsersCount($cnt)
	{
		if (CModule::IncludeModule("bitrix24"))
		{
			$UserMaxCount = intval(COption::GetOptionString("main", "PARAM_MAX_USERS"));
			$currentUserCount = CBitrix24::ActiveUserCount();
			return $UserMaxCount <= 0 || $cnt <= $UserMaxCount - $currentUserCount;
		}
		return false;
	}

	public static function RegisterUser($userData, $SITE_ID = SITE_ID)
	{
		$bExtranetUser = (!isset($userData['UF_DEPARTMENT']) || empty($userData['UF_DEPARTMENT']));
		$strPassword = self::GeneratePassword($SITE_ID, $bExtranetUser);

		$arUser = array(
			"LOGIN" => $userData["EMAIL"],
			"EMAIL" => $userData["EMAIL"],
			"UF_DEPARTMENT" => (intval($userData["UF_DEPARTMENT"]) > 0 ? array($userData["UF_DEPARTMENT"]) : array(0)),
			"PASSWORD" => $strPassword,
			"CONFIRM_CODE" => $userData['CONFIRM_CODE'],
			"GROUP_ID" => $userData['GROUP_ID'],
		);

		$obUser = new CUser;
		$res = $obUser->Add($arUser);
		return ($res? $res : preg_split("/<br>/", $obUser->LAST_ERROR));
	}

	public static function InviteUser($arUser, $messageText, $SITE_ID)
	{
		global $USER;

		$bExtranet = (
			!isset($arUser["UF_DEPARTMENT"])
			|| (
				is_array($arUser["UF_DEPARTMENT"])
				&& intval($arUser["UF_DEPARTMENT"][0]) <= 0
			)
			|| (
				!is_array($arUser["UF_DEPARTMENT"])
				&& intval($arUser["UF_DEPARTMENT"]) <= 0
			)
		);

		$SiteIdToSend = ($bExtranet && CModule::IncludeModule("extranet") ? CExtranet::GetExtranetSiteID() : CSite::GetDefSite());
		$rsSites = CSite::GetByID($SiteIdToSend);
		$arSite = $rsSites->Fetch();
		$serverName = (
			strlen($arSite["SERVER_NAME"]) > 0
				? $arSite["SERVER_NAME"]
				: (
					defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME) > 0
						? SITE_SERVER_NAME
						: COption::GetOptionString("main", "server_name", "")
				)
		);

		$event = new CEvent;
		if (IsModuleInstalled("bitrix24"))
		{
			$event->SendImmediate("BITRIX24_USER_INVITATION", $SITE_ID, array(
				"EMAIL_FROM" => $USER->GetEmail(),
				"EMAIL_TO" => $arUser["EMAIL"],
				"LINK" => CHTTP::URN2URI("/bitrix/tools/intranet_invite_dialog.php?user_id=".$arUser['ID']."&checkword=".urlencode($arUser["CONFIRM_CODE"]), $serverName),
				"USER_TEXT" => $messageText,
			));
		}
		else
		{
			$event->SendImmediate("INTRANET_USER_INVITATION", $SITE_ID, array(
				"EMAIL_TO" => $arUser["EMAIL"],
				"LINK" => CHTTP::URN2URI("/bitrix/tools/intranet_invite_dialog.php?user_id=".$arUser['ID']."&checkword=".urlencode($arUser["CONFIRM_CODE"]), $serverName),
				"USER_TEXT" => $messageText,
			));
		}
	}

	function ReinviteUser($SITE_ID, $USER_ID)
	{
		$USER_ID = intval($USER_ID);

		$rsUser = CUser::GetList(
			$o="ID",
			$b="DESC",
			array("ID_EQUAL_EXACT" => $USER_ID),
			array("SELECT" => array("UF_DEPARTMENT"))
		);
		if($arUser = $rsUser->Fetch())
		{
			$moduleID = (IsModuleInstalled("bitrix24") ? "bitrix24" : "intranet");
			$messageText = (($userMessageText = CUserOptions::GetOption($moduleID, "invite_message_text")) ? htmlspecialcharsbx($userMessageText) : GetMessage("BX24_INVITE_DIALOG_INVITE_MESSAGE_TEXT"));
			self::InviteUser($arUser, $messageText, $SITE_ID);
			return true;
		}
		return false;
	}

	function ReinviteExtranetUser($SITE_ID, $USER_ID)
	{
		$USER_ID = intval($USER_ID);

		$rsUser = CUser::GetList(
			$o="ID",
			$b="DESC",
			array("ID_EQUAL_EXACT" => $USER_ID)
		);
		if($arUser = $rsUser->Fetch())
		{
			$event = new CEvent;
			$arFields = Array(
				"USER_ID"	=>	$USER_ID,
				"CHECKWORD"	=>	$arUser["CONFIRM_CODE"],
				"EMAIL"	=>	$arUser["EMAIL"]
			);
			$event->Send("EXTRANET_INVITATION", $SITE_ID, $arFields);
			return true;
		}
		return false;
	}

	public static function RequestToSonetGroups($arUserId, $arGroupCode, $arGroupName, $bExtranetUser = false)
	{
		$arGroupToAdd = array();
		$strError = false;

		if (!is_array($arUserId))
		{
			$arUserId = array($arUserId);
		}

		if (
			is_array($arGroupCode)
			&& !empty($arGroupCode)
			&& CModule::IncludeModule("socialnetwork")
		)
		{
			foreach($arGroupCode as $group_code)
			{
				if(
					$bExtranetUser
					&& preg_match('/^(SGN\d+)$/', $group_code, $match)
					&& is_array($arGroupName)
					&& isset($arGroupName[$match[1]])
					&& strlen($arGroupName[$match[1]]) > 0
					&& CModule::IncludeModule("extranet")
					&& (
						CSocNetUser::IsCurrentUserModuleAdmin(SITE_ID, false)
						|| $GLOBALS["APPLICATION"]->GetGroupRight("socialnetwork", false, "Y", "Y", array(CExtranet::GetExtranetSiteID(), false)) >= "K"
					)
				)
				{
					// check and create group, for extranet only

					$dbSubjects = CSocNetGroupSubject::GetList(
						array("SORT"=>"ASC", "NAME" => "ASC"),
						array("SITE_ID" => CExtranet::GetExtranetSiteID()),
						false,
						false,
						array("ID")
					);
					if ($arSubject = $dbSubjects->GetNext())
					{
						$arSocNetGroupFields = array(
							"NAME" => $arGroupName[$match[1]],
							"DESCRIPTION" => "",
							"VISIBLE" => "N",
							"OPENED" => "N",
							"CLOSED" => "N",
							"SUBJECT_ID" => $arSubject["ID"],
							"INITIATE_PERMS" => "E",
							"SPAM_PERMS" => "K",
							"SITE_ID" => array($SITE_ID, CExtranet::GetExtranetSiteID())
						);

						if ($group_id = CSocNetGroup::CreateGroup(
							$GLOBALS["USER"]->GetID(),
							$arSocNetGroupFields,
							false
						))
						{
							$arGroupToAdd[] = $group_id;
						}
						elseif ($e = $GLOBALS["APPLICATION"]->GetException())
						{
							$strError = $e->GetString();
						}
					}
				}
				elseif(preg_match('/^SG(\d+)$/', $group_code, $match))
				{
					$group_id = $match[1];
					if (
						($arGroup = CSocNetGroup::GetByID($group_id))
						&& ($arCurrentUserPerms = CSocNetUserToGroup::InitUserPerms($GLOBALS["USER"]->GetID(), $arGroup, CSocNetUser::IsCurrentUserModuleAdmin(SITE_ID, false)))
						&& $arCurrentUserPerms["UserCanInitiate"]
						&& $arGroup["CLOSED"] != "Y"
					)
					{
						$arGroupToAdd[] = $group_id;
					}
				}
			}

			if (!$strError)
			{
				foreach($arGroupToAdd as $group_id)
				{
					foreach($arUserId as $user_id)
					{
						if (!CSocNetUserToGroup::SendRequestToJoinGroup($GLOBALS["USER"]->GetID(), $user_id, $group_id, "", false))
						{
							if ($e = $GLOBALS["APPLICATION"]->GetException())
							{
								$strError .= $e->GetString();
							}
						}
					}
				}
			}
		}

		return $strError;
	}

	public static function OnAfterUserAuthorize($arParams)
	{
		if (
			isset($arParams['update'])
			&& $arParams['update'] === false
		)
		{
			return false;
		}

		if ($arParams['user_fields']['ID'] <= 0)
		{
			return false;
		}

		if (
			array_key_exists('CONFIRM_CODE', $arParams['user_fields'])
			&& strlen(trim($arParams['user_fields']['CONFIRM_CODE'])) >= 0
			&& CModule::IncludeModule("socialnetwork")
		)
		{
			$dbRelation = CSocNetUserToGroup::GetList(
				array(),
				array(
					"USER_ID" => $arParams['user_fields']['ID'],
					"ROLE" => SONET_ROLES_REQUEST,
					"INITIATED_BY_TYPE" => SONET_INITIATED_BY_GROUP
				),
				false,
				false,
				array("ID", "GROUP_ID")
			);
			while ($arRelation = $dbRelation->Fetch())
			{
				if (CSocNetUserToGroup::UserConfirmRequestToBeMember($arParams['user_fields']['ID'], $arRelation["ID"], false))
				{
					if (defined("BX_COMP_MANAGED_CACHE"))
					{
						$GLOBALS["CACHE_MANAGER"]->ClearByTag("sonet_user2group_G".$arRelation["GROUP_ID"]);
						$GLOBALS["CACHE_MANAGER"]->ClearByTag("sonet_user2group_U".$arParams['user_fields']['ID']);
					}

					if (CModule::IncludeModule("im"))
					{
						CIMNotify::DeleteByTag("SOCNET|INVITE_GROUP|".$arParams['user_fields']['ID']."|".intval($arRelation["ID"]));
					}
				}
			}
		}
	}

	private function GeneratePassword($SITE_ID, $bExtranetUser)
	{
		global $USER;

		$arGroupID = self::getUserGroups($SITE_ID, $bExtranetUser = false);
		$arPolicy = $USER->GetGroupPolicy($arGroupID);

		$password_min_length = intval($arPolicy["PASSWORD_LENGTH"]);
		if($password_min_length <= 0)
		{
			$password_min_length = 6;
		}

		$password_chars = array(
			"abcdefghijklnmopqrstuvwxyz",
			"ABCDEFGHIJKLNMOPQRSTUVWXYZ",
			"0123456789",
		);

		if($arPolicy["PASSWORD_PUNCTUATION"] === "Y")
		{
			$password_chars[] = ",.<>/?;:'\"[]{}\\|`~!@#\$%^&*()-_+=";
		}

		$password = randString($password_min_length, $password_chars);

		return $password;
	}
}
