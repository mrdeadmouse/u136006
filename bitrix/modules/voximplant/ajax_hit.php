<?
if($_SERVER["REQUEST_METHOD"] == "POST" && array_key_exists("IM_AJAX_CALL", $_REQUEST) && $_REQUEST["IM_AJAX_CALL"] === "Y" && $_POST['IM_PHONE'] == 'Y')
{
	if (intval($USER->GetID()) <= 0 || !(IsModuleInstalled('voximplant') && (!IsModuleInstalled('extranet') || CModule::IncludeModule('extranet') && CExtranet::IsIntranetUser())))
	{
		echo CUtil::PhpToJsObject(Array('ERROR' => 'AUTHORIZE_ERROR'));
		require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
		die();
	}

	if (check_bitrix_sessid())
	{
		IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/voximplant/ajax_hit.php');

		$chatId = intval($_POST['CHAT_ID']);
		$userId = intval($USER->GetId());

		if ($_POST['COMMAND'] == 'authorize')
		{
			$updateInfo = $_POST['UPDATE_INFO'] == 'Y';
			$ViMain = new CVoxImplantMain($userId);
			$result = $ViMain->GetAuthorizeInfo($updateInfo);
			if (!$result)
			{
				echo CUtil::PhpToJsObject(Array(
					'CODE' => $ViMain->GetError()->code,
					'ERROR' => $ViMain->GetError()->msg
				));
			}
			else
			{
				echo CUtil::PhpToJsObject(Array(
					'ACCOUNT' => $result['ACCOUNT'],
					'SERVER' => $result['SERVER'],
					'LOGIN' => $result['LOGIN'],
					'CALLERID' => $result['CALLERID'],
					'HR_PHOTO' => $result['HR_PHOTO'],
					'ERROR' => ''
				));
			}
		}
		else if ($_POST['COMMAND'] == 'onetimekey')
		{
			$ViMain = new CVoxImplantMain($userId);
			$result = $ViMain->GetOneTimeKey($_POST['KEY']);
			if (!$result)
			{
				echo CUtil::PhpToJsObject(Array(
					'CODE' => $ViMain->GetError()->code,
					'ERROR' => $ViMain->GetError()->msg
				));
			}
			else
			{
				echo CUtil::PhpToJsObject(Array(
					'HASH' => $result,
					'ERROR' => ''
				));
			}
		}
		else if ($_POST['COMMAND'] == 'authorize_error')
		{
			$ViMain = new CVoxImplantMain($userId);
			$ViMain->ClearUserInfo();
			$ViMain->ClearAccountInfo();
		}
		else if ($_POST['COMMAND'] == 'init')
		{
			$ViMain = new CVoxImplantMain($userId);
			$result = $ViMain->GetDialogInfo($_POST['NUMBER']);

			if ($result)
			{
				echo CUtil::PhpToJsObject(Array(
					'DIALOG_ID' => $result['DIALOG_ID'],
					'HR_PHOTO' => $result['HR_PHOTO'],
					'ERROR' => ''
				));
			}
			else
			{
				echo CUtil::PhpToJsObject(Array(
					'CODE' => $ViMain->GetError()->code,
					'ERROR' => $ViMain->GetError()->msg
				));
			}
		}
		else if ($_POST['COMMAND'] == 'deviceStartCall')
		{
			$_POST['PARAMS'] = CUtil::JsObjectToPhp($_POST['PARAMS']);
			if (CVoxImplantUser::GetPhoneActive($USER->GetId()))
			{
				CVoxImplantOutgoing::StartCall($USER->GetId(), $_POST['PARAMS']['NUMBER']);
			}
		}
		else if ($_POST['COMMAND'] == 'deviceHungup')
		{
			$_POST['PARAMS'] = CUtil::JsObjectToPhp($_POST['PARAMS']);
			CVoxImplantIncoming::SendCommand(Array(
				'CALL_ID' => $_POST['PARAMS']['CALL_ID'],
				'COMMAND' => CVoxImplantIncoming::RULE_HUNGUP
			));
		}
		else if ($_POST['COMMAND'] == 'wait')
		{
			$_POST['PARAMS'] = CUtil::JsObjectToPhp($_POST['PARAMS']);

			CVoxImplantIncoming::SendCommand(Array(
				'CALL_ID' => $_POST['PARAMS']['CALL_ID'],
				'COMMAND' => CVoxImplantIncoming::RULE_WAIT
			));
		}
		else if ($_POST['COMMAND'] == 'answer')
		{
			$_POST['PARAMS'] = CUtil::JsObjectToPhp($_POST['PARAMS']);

			CVoxImplantIncoming::SendCommand(Array(
				'CALL_ID' => $_POST['PARAMS']['CALL_ID'],
				'COMMAND' => CVoxImplantIncoming::RULE_WAIT
			));

			CVoxImplantIncoming::SendPullEvent(Array(
				'COMMAND' => 'answer_self',
				'USER_ID' => $userId,
				'CALL_ID' => $_POST['PARAMS']['CALL_ID'],
			));

			if (CModule::IncludeModule('im'))
				CIMStatus::SetIdle($userId, false);
		}
		else if ($_POST['COMMAND'] == 'skip')
		{
			$_POST['PARAMS'] = CUtil::JsObjectToPhp($_POST['PARAMS']);

			CVoxImplantIncoming::SendCommand(Array(
				'CALL_ID' => $_POST['PARAMS']['CALL_ID'],
				'COMMAND' => CVoxImplantIncoming::RULE_QUEUE
			));
		}
		else if ($_POST['COMMAND'] == 'start')
		{
			$_POST['PARAMS'] = CUtil::JsObjectToPhp($_POST['PARAMS']);

			CVoxImplantMain::CallStart($_POST['PARAMS']['CALL_ID'], $userId);
		}
		else if ($_POST['COMMAND'] == 'hold' || $_POST['COMMAND'] == 'unhold')
		{
			$_POST['PARAMS'] = CUtil::JsObjectToPhp($_POST['PARAMS']);
			CVoxImplantMain::CallHold($_POST['PARAMS']['CALL_ID'], $_POST['COMMAND'] == 'hold');
		}
		else if ($_POST['COMMAND'] == 'ready')
		{
			$_POST['PARAMS'] = CUtil::JsObjectToPhp($_POST['PARAMS']);

			CVoxImplantIncoming::SendCommand(Array(
				'CALL_ID' => $_POST['PARAMS']['CALL_ID'],
				'COMMAND' => CVoxImplantIncoming::RULE_USER,
				'USER_ID' => $USER->GetId(),
			));
		}
		else if ($_POST['COMMAND'] == 'inviteTransfer')
		{
			$_POST['PARAMS'] = CUtil::JsObjectToPhp($_POST['PARAMS']);
			CVoxImplantTransfer::Invite($_POST['PARAMS']['CALL_ID'], $_POST['PARAMS']['USER_ID']);
		}
		else if ($_POST['COMMAND'] == 'readyTransfer')
		{
			$_POST['PARAMS'] = CUtil::JsObjectToPhp($_POST['PARAMS']);
			CVoxImplantTransfer::Ready($_POST['PARAMS']['CALL_ID']);
		}
		else if ($_POST['COMMAND'] == 'answerTransfer')
		{
			$_POST['PARAMS'] = CUtil::JsObjectToPhp($_POST['PARAMS']);
			CVoxImplantTransfer::Answer($_POST['PARAMS']['CALL_ID']);
		}
		else if ($_POST['COMMAND'] == 'waitTransfer')
		{
			$_POST['PARAMS'] = CUtil::JsObjectToPhp($_POST['PARAMS']);
			CVoxImplantTransfer::Wait($_POST['PARAMS']['CALL_ID']);
		}
		else if ($_POST['COMMAND'] == 'declineTransfer')
		{
			$_POST['PARAMS'] = CUtil::JsObjectToPhp($_POST['PARAMS']);
			CVoxImplantTransfer::Decline($_POST['PARAMS']['CALL_ID']);
		}
		else if ($_POST['COMMAND'] == 'cancelTransfer')
		{
			$_POST['PARAMS'] = CUtil::JsObjectToPhp($_POST['PARAMS']);
			CVoxImplantTransfer::Cancel($_POST['PARAMS']['CALL_ID']);
		}
		else if ($_POST['COMMAND'] == 'timeoutTransfer')
		{
			$_POST['PARAMS'] = CUtil::JsObjectToPhp($_POST['PARAMS']);
			CVoxImplantTransfer::Timeout($_POST['PARAMS']['CALL_ID']);
		}
	}
	else
	{
		echo CUtil::PhpToJsObject(Array(
			'BITRIX_SESSID' => bitrix_sessid(),
			'ERROR' => 'SESSION_ERROR'
		));
	}
}
?>