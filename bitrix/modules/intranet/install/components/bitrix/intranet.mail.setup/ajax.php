<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
require_once 'helper.php';

__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/component.php');

class CIntranetMailSetupAjax
{

	public static function execute()
	{
		global $USER;

		$result = array();
		$error  = false;

		if (!CModule::IncludeModule('mail'))
			$error = GetMessage('MAIL_MODULE_NOT_INSTALLED');

		if ($error === false)
		{
			if (!is_object($USER) || !$USER->IsAuthorized())
				$error = GetMessage('INTR_MAIL_AUTH');
		}

		if ($error === false)
		{
			if (!CIntranetUtils::IsExternalMailAvailable())
				$error = GetMessage('INTR_MAIL_UNAVAILABLE');
		}

		if ($error === false)
		{
			$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : null;
			$act  = isset($_REQUEST['act']) ? $_REQUEST['act'] : null;

			switch ($page)
			{
				case 'domain':
					$result = (array) self::handleDomainAction($act, $error);
					break;
				case 'manage':
					$result = (array) self::handleManageAction($act, $error);
					break;
				default:
					$result = (array) self::handleDefaultAction($act, $error);
			}
		}

		self::returnJson(array_merge(array(
			'result' => $error === false ? 'ok' : 'error',
			'error'  => CharsetConverter::ConvertCharset($error, SITE_CHARSET, 'UTF-8')
		), $result));
	}

	private static function handleDefaultAction($act, &$error)
	{
		switch ($act)
		{
			case 'name':
				return self::executeCheckName($error);
				break;
			case 'create':
				return self::executeCreateMailbox($error);
				break;
			case 'edit':
				return self::executeEditMailbox($error);
				break;
			case 'delete':
				return self::executeDeleteMailbox($error);
				break;
			case 'check':
				return self::executeCheck($error);
				break;
			case 'password':
				return self::executeChangePassword($error);
				break;
			default:
				$error = GetMessage('INTR_MAIL_AJAX_ERROR');
		}
	}

	private static function executeCheckName(&$error)
	{
		$error    = false;
		$occupied = -1;

		$serviceId = isset($_REQUEST['SERVICE']) ? $_REQUEST['SERVICE'] : null;
		if (empty($serviceId))
			$error = GetMessage('INTR_MAIL_AJAX_ERROR');

		if ($error === false)
		{
			$services = CIntranetMailSetupHelper::getMailServices();

			if (!array_key_exists($serviceId, $services))
				$error = GetMessage('INTR_MAIL_AJAX_ERROR');
			else if (!in_array($services[$serviceId]['type'], array('controller', 'domain', 'crdomain')))
				$error = GetMessage('INTR_MAIL_AJAX_ERROR');
			else if (empty($_REQUEST['login']) || empty($_REQUEST['domain']))
				$error = GetMessage('INTR_MAIL_AJAX_ERROR');
		}

		if ($error === false)
		{
			if ($services[$serviceId]['type'] == 'controller')
			{
				$crCheckName = CControllerClient::ExecuteEvent('OnMailControllerCheckName', array(
					'DOMAIN' => $_REQUEST['domain'],
					'NAME'   => $_REQUEST['login']
				));
				if (isset($crCheckName['result']))
				{
					$occupied = (boolean) $crCheckName['result'];
				}
				else
				{
					$error = empty($crCheckName['error'])
						? GetMessage('INTR_MAIL_CONTROLLER_INVALID')
						: CMail::getErrorMessage($crCheckName['error']);
				}
			}
			else if ($services[$serviceId]['type'] == 'crdomain')
			{
				if ($services[$serviceId]['server'] != $_REQUEST['domain'])
				{
					$error = CMail::getErrorMessage(CMail::ERR_API_OP_DENIED);
				}
				else
				{
					$crCheckName = CControllerClient::ExecuteEvent('OnMailControllerCheckMemberName', array(
						'DOMAIN' => $_REQUEST['domain'],
						'NAME'   => $_REQUEST['login']
					));
					if (isset($crCheckName['result']))
					{
						$occupied = (boolean) $crCheckName['result'];
					}
					else
					{
						$error = empty($crCheckName['error'])
							? GetMessage('INTR_MAIL_CONTROLLER_INVALID')
							: CMail::getErrorMessage($crCheckName['error']);
					}
				}
			}
			else if ($services[$serviceId]['type'] == 'domain')
			{
				if ($services[$serviceId]['server'] != $_REQUEST['domain'])
				{
					$error = CMail::getErrorMessage(CMail::ERR_API_OP_DENIED);
				}
				else
				{
					$result = CMailDomain2::isUserExists(
						$services[$serviceId]['token'],
						$_REQUEST['domain'], $_REQUEST['login'],
						$error
					);

					if (is_null($result))
						$error = CMail::getErrorMessage($error);
					else
						$occupied = (boolean) $result;
				}
			}
		}

		return array('occupied' => $occupied);
	}

	private static function executeCreateMailbox(&$error)
	{
		global $USER;

		$error = false;

		if (!check_bitrix_sessid())
			$error = GetMessage('INTR_MAIL_CSRF');

		if ($error === false)
		{
			$serviceId = isset($_REQUEST['SERVICE']) ? $_REQUEST['SERVICE'] : null;
			if (empty($serviceId))
				$error = GetMessage('INTR_MAIL_FORM_ERROR');
		}

		if ($error === false)
		{
			$services = CIntranetMailSetupHelper::getMailServices();

			if (!array_key_exists($serviceId, $services))
				$error = GetMessage('INTR_MAIL_FORM_ERROR');
		}

		if ($error === false)
		{
			$unseen = 0;

			if ($services[$serviceId]['type'] == 'controller')
			{
				if (!preg_match('/^[a-z0-9_]+(\.?[a-z0-9_-]*[a-z0-9_]+)*?$/i', $_REQUEST['login']))
					$error = CMail::getErrorMessage(CMail::ERR_API_BAD_NAME);

				if ($error === false)
				{
					$mbData = array(
						'LOGIN' => $_REQUEST['login'] . '@' . $_REQUEST['domain']
					);

					$crResponse = CControllerClient::ExecuteEvent('OnMailControllerAddUser', array(
						'DOMAIN'   => $_REQUEST['domain'],
						'NAME'     => $_REQUEST['login'],
						'PASSWORD' => $_REQUEST['password']
					));
					if (!isset($crResponse['result']))
					{
						$error = empty($crResponse['error'])
							? GetMessage('INTR_MAIL_CONTROLLER_INVALID')
							: CMail::getErrorMessage($crResponse['error']);
					}
				}
			}
			else if ($services[$serviceId]['type'] == 'crdomain')
			{
				if ($services[$serviceId]['server'] != $_REQUEST['domain'])
					$error = GetMessage('INTR_MAIL_FORM_ERROR');

				if (!$USER->isAdmin() && !$USER->canDoOperation('bitrix24_config') && $services[$serviceId]['encryption'] != 'N')
					$error = CMail::getErrorMessage(CMail::ERR_API_OP_DENIED);

				if ($error === false)
				{
					if (!preg_match('/^[a-z0-9_]+(\.?[a-z0-9_-]*[a-z0-9_]+)*?$/i', $_REQUEST['login']))
						$error = CMail::getErrorMessage(CMail::ERR_API_BAD_NAME);
				}

				if ($error === false)
				{
					$mbData = array(
						'LOGIN' => $_REQUEST['login'] . '@' . $_REQUEST['domain']
					);

					$crResponse = CControllerClient::ExecuteEvent('OnMailControllerAddMemberUser', array(
						'DOMAIN'   => $_REQUEST['domain'],
						'NAME'     => $_REQUEST['login'],
						'PASSWORD' => $_REQUEST['password']
					));
					if (!isset($crResponse['result']))
					{
						$error = empty($crResponse['error'])
							? GetMessage('INTR_MAIL_CONTROLLER_INVALID')
							: CMail::getErrorMessage($crResponse['error']);
					}
				}
			}
			else if ($services[$serviceId]['type'] == 'domain')
			{
				if ($services[$serviceId]['server'] != $_REQUEST['domain'])
					$error = GetMessage('INTR_MAIL_FORM_ERROR');

				if (!$USER->isAdmin() && !$USER->canDoOperation('bitrix24_config') && $services[$serviceId]['encryption'] != 'N')
					$error = CMail::getErrorMessage(CMail::ERR_API_OP_DENIED);

				if ($error === false)
				{
					if (!preg_match('/^[a-z0-9_]+(\.?[a-z0-9_-]*[a-z0-9_]+)*?$/i', $_REQUEST['login']))
						$error = CMail::getErrorMessage(CMail::ERR_API_BAD_NAME);
				}

				if ($error === false)
				{
					$mbData = array(
						'LOGIN' => $_REQUEST['login'] . '@' . $_REQUEST['domain']
					);

					$result = CMailDomain2::addUser(
						$services[$serviceId]['token'],
						$_REQUEST['domain'],
						$_REQUEST['login'],
						$_REQUEST['password'],
						$error
					);

					if (is_null($result))
						$error = CMail::getErrorMessage($error);
				}
			}
			else if ($services[$serviceId]['type'] == 'imap')
			{
				$mbData = array(
					'LINK'     => $services[$serviceId]['link'] ?: $_REQUEST['link'],
					'SERVER'   => $services[$serviceId]['server'] ?: $_REQUEST['server'],
					'PORT'     => $services[$serviceId]['port'] ?: $_REQUEST['port'],
					'LOGIN'    => $_REQUEST['login'],
					'PASSWORD' => $_REQUEST['password'],
					'USE_TLS'  => $services[$serviceId]['encryption'] ?: $_REQUEST['encryption']
				);
				if (!in_array($mbData['USE_TLS'], array('Y', 'S')))
					$mbData['USE_TLS'] = 'N';

				if (!$services[$serviceId]['link'])
				{
					$regExp = '/^(https?:\/\/)?((?:[a-z0-9](?:-*[a-z0-9])*\.?)+)(:[0-9]+)?(\/.*)?$/i';
					if (preg_match($regExp, trim($mbData['LINK']), $matches) && strlen($matches[2]) > 0)
					{
						$mbData['LINK'] = $matches[0];
						if (strlen($matches[1]) == 0)
							$mbData['LINK'] = 'http://' . $mbData['LINK'];
					}
					else
					{
						$error = GetMessage('INTR_MAIL_FORM_ERROR');
					}
				}

				if (!$services[$serviceId]['server'])
				{
					$regExp = '/^(?:(?:http|https|ssl|tls|imap):\/\/)?((?:[a-z0-9](?:-*[a-z0-9])*\.?)+)$/i';
					if (preg_match($regExp, trim($mbData['SERVER']), $matches) && strlen($matches[1]) > 0)
						$mbData['SERVER'] = $matches[1];
					else
						$error = GetMessage('INTR_MAIL_FORM_ERROR');
				}

				if ($error === false)
				{
					$unseen = CMailUtil::CheckImapMailbox(
						$mbData['SERVER'], $mbData['PORT'], $mbData['USE_TLS'],
						$mbData['LOGIN'], $mbData['PASSWORD'],
						$error, 30
					);
				}
			}

			if ($error === false)
			{
				if ($mailbox = CIntranetMailSetupHelper::getUserMailbox($USER->GetID()))
					CMailbox::delete($mailbox['ID']);

				$mbData = array_merge(array(
					'LID'         => SITE_ID,
					'ACTIVE'      => 'Y',
					'SERVICE_ID'  => $serviceId,
					'NAME'        => $services[$serviceId]['name'],
					'SERVER_TYPE' => $services[$serviceId]['type'],
					'USER_ID'     => $USER->GetID()
				), $mbData);

				$result = CMailbox::add($mbData);

				if ($result > 0)
				{
					CUserCounter::Set($USER->GetID(), 'mail_unseen', $unseen, SITE_ID);

					CUserOptions::SetOption('global', 'last_mail_check_'.SITE_ID, time());
					CUserOptions::SetOption('global', 'last_mail_check_success_'.SITE_ID, $unseen >= 0);
				}
				else
				{
					$error = GetMessage('INTR_MAIL_SAVE_ERROR');
				}
			}
		}
	}

	private static function executeEditMailbox(&$error)
	{
		global $USER;

		$error = false;

		if (!check_bitrix_sessid())
			$error = GetMessage('INTR_MAIL_CSRF');

		if ($error === false)
		{
			$mailbox = CIntranetMailSetupHelper::getUserMailbox($USER->GetID());
			if (empty($mailbox) || $mailbox['SERVER_TYPE'] != 'imap')
				$error = GetMessage('INTR_MAIL_FORM_ERROR');
		}

		if ($error === false)
		{
			$serviceId = $mailbox['SERVICE_ID'];
			$services = CIntranetMailSetupHelper::getMailServices();

			if (!array_key_exists($serviceId, $services))
				$error = GetMessage('INTR_MAIL_FORM_ERROR');
		}

		if ($error === false)
		{
			$mbData = array(
				'LINK'     => $services[$serviceId]['link'] ?: $_REQUEST['link'],
				'SERVER'   => $services[$serviceId]['server'] ?: $_REQUEST['server'],
				'PORT'     => $services[$serviceId]['port'] ?: $_REQUEST['port'],
				'LOGIN'    => $_REQUEST['login'],
				'PASSWORD' => $_REQUEST['password'],
				'USE_TLS'  => $services[$serviceId]['encryption'] ?: $_REQUEST['encryption']
			);
			if (!in_array($mbData['USE_TLS'], array('Y', 'S')))
				$mbData['USE_TLS'] = 'N';

			$unseen = CMailUtil::CheckImapMailbox(
				$mbData['SERVER'], $mbData['PORT'], $mbData['USE_TLS'],
				$mbData['LOGIN'], $mbData['PASSWORD'],
				$error, 30
			);

			if ($error === false)
			{
				$result = CMailbox::update($mailbox['ID'], $mbData);

				if ($result > 0)
				{
					CUserCounter::Set($USER->GetID(), 'mail_unseen', $unseen, SITE_ID);

					CUserOptions::SetOption('global', 'last_mail_check_'.SITE_ID, time());
					CUserOptions::SetOption('global', 'last_mail_check_success_'.SITE_ID, $unseen >= 0);
				}
				else
				{
					$error = GetMessage('INTR_MAIL_SAVE_ERROR');
				}
			}
		}
	}

	private static function executeDeleteMailbox(&$error)
	{
		global $USER;

		if (!check_bitrix_sessid())
			$error = GetMessage('INTR_MAIL_CSRF');

		if ($error === false)
		{
			if ($mailbox = CIntranetMailSetupHelper::getUserMailbox($USER->GetID()))
			{
				if ($error === false)
				{
					CMailbox::delete($mailbox['ID']);

					CUserCounter::Clear($USER->GetID(), 'mail_unseen', SITE_ID);

					CUserOptions::DeleteOption('global', 'last_mail_check_'.SITE_ID);
					CUserOptions::DeleteOption('global', 'last_mail_check_success_'.SITE_ID);
				}
			}
		}
	}

	private static function executeCheck(&$error)
	{
		global $USER;

		$error  = false;
		$unseen = -1;

		if (!check_bitrix_sessid())
			$error = GetMessage('INTR_MAIL_CSRF');

		if ($error === false)
		{
			$mailbox = CIntranetMailSetupHelper::getUserMailbox($USER->GetID());
			if (empty($mailbox))
				$error = GetMessage('INTR_MAIL_AJAX_ERROR');
		}

		if ($error === false)
		{
			switch ($mailbox['SERVER_TYPE'])
			{
				case 'imap':
					$unseen = CMailUtil::CheckImapMailbox(
						$mailbox['SERVER'], $mailbox['PORT'], $mailbox['USE_TLS'],
						$mailbox['LOGIN'], $mailbox['PASSWORD'],
						$error, 30
					);
					break;
				case 'controller':
					list($login, $domain) = explode('@', $mailbox['LOGIN'], 2);
					$crCheckMailbox = CControllerClient::ExecuteEvent('OnMailControllerCheckMailbox', array(
						'DOMAIN' => $domain,
						'NAME'   => $login
					));
					if (isset($crCheckMailbox['result']))
					{
						$unseen = intval($crCheckMailbox['result']);
					}
					else
					{
						$error  = empty($crCheckMailbox['error'])
							? GetMessage('INTR_MAIL_CONTROLLER_INVALID')
							: CMail::getErrorMessage($crCheckMailbox['error']);
					}
					break;
				case 'crdomain':
					list($login, $domain) = explode('@', $mailbox['LOGIN'], 2);
					$crCheckMailbox = CControllerClient::ExecuteEvent('OnMailControllerCheckMemberMailbox', array(
						'DOMAIN' => $domain,
						'NAME'   => $login
					));
					if (isset($crCheckMailbox['result']))
					{
						$unseen = intval($crCheckMailbox['result']);
					}
					else
					{
						$error  = empty($crCheckMailbox['error'])
							? GetMessage('INTR_MAIL_CONTROLLER_INVALID')
							: CMail::getErrorMessage($crCheckMailbox['error']);
					}
					break;
				case 'domain':
					$serviceId = $mailbox['SERVICE_ID'];
					$services  = CIntranetMailSetupHelper::getMailServices();
					list($login, $domain) = explode('@', $mailbox['LOGIN'], 2);
					$result = CMailDomain2::getUnreadMessagesCount(
						$services[$serviceId]['token'],
						$domain, $login,
						$error
					);

					if (is_null($result))
						$error = CMail::getErrorMessage($error);
					else
						$unseen = intval($result);
					break;
			}

			CUserCounter::Set($USER->GetID(), 'mail_unseen', $unseen, SITE_ID);

			CUserOptions::SetOption('global', 'last_mail_check_'.SITE_ID, time());
			CUserOptions::SetOption('global', 'last_mail_check_success_'.SITE_ID, $unseen >= 0);
		}

		return array('unseen' => $unseen);
	}

	private static function executeChangePassword(&$error)
	{
		global $USER;

		$error = false;

		$password  = $_REQUEST['password'];
		$password2 = $_REQUEST['password2'];

		if (!check_bitrix_sessid())
			$error = GetMessage('INTR_MAIL_CSRF');

		if ($error === false)
		{
			$mailbox = CIntranetMailSetupHelper::getUserMailbox($USER->GetID());
			if (empty($mailbox) || !in_array($mailbox['SERVER_TYPE'], array('controller', 'domain', 'crdomain', 'imap')))
				$error = GetMessage('INTR_MAIL_FORM_ERROR');
		}

		if ($error === false)
		{
			if ($mailbox['ID'] != $_REQUEST['ID'])
				$error = GetMessage('INTR_MAIL_FORM_ERROR');
		}

		if ($error === false)
		{
			if (in_array($mailbox['SERVER_TYPE'], array('controller', 'domain', 'crdomain')) && $password != $password2)
				$error = GetMessage('INTR_MAIL_INP_PASSWORD2_BAD');
		}

		if ($error === false)
		{
			if ($mailbox['SERVER_TYPE'] == 'crdomain')
			{
				list($login, $domain) = explode('@', $mailbox['LOGIN'], 2);
				$crResponse = CControllerClient::ExecuteEvent('OnMailControllerChangeMemberPassword', array(
					'DOMAIN'   => $domain,
					'NAME'     => $login,
					'PASSWORD' => $password
				));
				if (!isset($crResponse['result']))
				{
					$error = empty($crResponse['error'])
						? GetMessage('INTR_MAIL_CONTROLLER_INVALID')
						: CMail::getErrorMessage($crResponse['error']);
				}
			}
			else if ($mailbox['SERVER_TYPE'] == 'domain')
			{
				$domainService = CIntranetMailSetupHelper::getDomainService($mailbox['SERVICE_ID']);

				list($login, $domain) = explode('@', $mailbox['LOGIN'], 2);
				$result = CMailDomain2::changePassword(
					$domainService['token'],
					$domain, $login, $password,
					$error
				);

				if (is_null($result))
					$error = CMail::getErrorMessage($error);
			}
			else if ($mailbox['SERVER_TYPE'] == 'controller')
			{
				list($login, $domain) = explode('@', $mailbox['LOGIN'], 2);
				$crResponse = CControllerClient::ExecuteEvent('OnMailControllerChangePassword', array(
					'DOMAIN'   => $domain,
					'NAME'     => $login,
					'PASSWORD' => $password
				));
				if (!isset($crResponse['result']))
				{
					$error = empty($crResponse['error'])
						? GetMessage('INTR_MAIL_CONTROLLER_INVALID')
						: CMail::getErrorMessage($crResponse['error']);
				}
			}
			else if ($mailbox['SERVER_TYPE'] == 'imap')
			{
				$unseen = CMailUtil::CheckImapMailbox(
					$mailbox['SERVER'], $mailbox['PORT'], $mailbox['USE_TLS'],
					$mailbox['LOGIN'], $password,
					$error, 30
				);

				if ($error === false)
				{
					$res = CMailbox::update($mailbox['ID'], array('PASSWORD' => $password));

					if (!$res)
						$error = GetMessage('INTR_MAIL_SAVE_ERROR');
				}
			}
		}

		return array(
			'result' => $error === false ? 'ok' : 'error',
			'error'  => CharsetConverter::ConvertCharset($error, SITE_CHARSET, 'UTF-8')
		);
	}

	private static function handleDomainAction($act, &$error)
	{
		global $USER;

		if (!$USER->isAdmin() && !$USER->canDoOperation('bitrix24_config'))
			$error = GetMessage('ACCESS_DENIED');

		if ($error === false)
		{
			switch ($act)
			{
				case 'whois':
					return self::executeDomainWhois($error);
					break;
				case 'suggest':
					return self::executeDomainSuggest($error);
					break;
				case 'initget':
					return self::executeDomainInitGet($error);
					break;
				case 'get':
					return self::executeDomainGet($error);
					break;
				case 'create':
					return self::executeDomainCreate($error);
					break;
				case 'edit':
					return self::executeDomainEdit($error);
					break;
				case 'check':
					return self::executeDomainCheck($error);
					break;
				case 'delete':
					return self::executeDomainDelete($error);
					break;
				default:
					$error = GetMessage('INTR_MAIL_AJAX_ERROR');
			}
		}
	}

	private static function executeDomainWhois(&$error)
	{
		$error = false;
		$occupied = -1;

		if (!preg_match('/^[a-z0-9][a-z0-9-]*[a-z0-9]\.ru$/i', $_REQUEST['domain']) || preg_match('/^..--/i', $_REQUEST['domain']))
			$error = GetMessage('INTR_MAIL_AJAX_ERROR');

		if ($error === false)
		{
			$crResponse = CControllerClient::ExecuteEvent('OnMailControllerWhoisDomain', array(
				'DOMAIN' => $_REQUEST['domain']
			));
			if (isset($crResponse['result']))
			{
				$occupied = (boolean) $crResponse['result'];
			}
			else
			{
				$error = empty($crResponse['error'])
					? GetMessage('INTR_MAIL_CONTROLLER_INVALID')
					: CMail::getErrorMessage($crResponse['error']);
			}
		}

		return array('occupied' => $occupied);
	}

	private static function executeDomainSuggest(&$error)
	{
		$error = false;
		$suggestions = array();

		if (!preg_match('/^[a-z0-9][a-z0-9-]*[a-z0-9]\.ru$/i', $_REQUEST['domain']) || preg_match('/^..--/i', $_REQUEST['domain']))
			$error = GetMessage('INTR_MAIL_AJAX_ERROR');

		if ($error === false)
		{
			$words = explode('-', preg_replace('/\.ru$/i', '', $_REQUEST['domain']));
			$crResponse = CControllerClient::ExecuteEvent('OnMailControllerSuggestDomain', array(
				'WORD1' => array_pop($words),
				'WORD2' => array_pop($words),
				'TLDS'  => array('ru')
			));
			if (isset($crResponse['result']) && is_array($crResponse['result']))
			{
				foreach ($crResponse['result'] as $entry)
					$suggestions[] = CharsetConverter::ConvertCharset($entry, SITE_CHARSET, 'UTF-8');
			}
			else
			{
				$error = empty($crResponse['error'])
					? GetMessage('INTR_MAIL_CONTROLLER_INVALID')
					: CMail::getErrorMessage($crResponse['error']);
			}
		}

		return array('suggestions' => $suggestions);
	}

	private static function executeDomainInitGet(&$error)
	{
		global $USER;

		CAgent::removeAgent('CIntranetUtils::notifyMailDomain("noreg", "'.SITE_ID.'", '.$USER->getId().');', 'intranet');
		CAgent::removeAgent('CIntranetUtils::notifyMailDomain("noreg", "'.SITE_ID.'", '.$USER->getId().', 1);', 'intranet');
		CAgent::addAgent('CIntranetUtils::notifyMailDomain("noreg", "'.SITE_ID.'", '.$USER->getId().');', 'intranet', 'N', 3600*24);

		return array();
	}

	private static function executeDomainGet(&$error)
	{
		global $USER;

		$error = false;

		if (!check_bitrix_sessid())
			$error = GetMessage('INTR_MAIL_CSRF');

		if ($error === false)
		{
			if (CIntranetMailSetupHelper::getDomainService())
				$error = GetMessage('INTR_MAIL_AJAX_ERROR');
		}

		if ($error === false)
		{
			if (!empty($_REQUEST['sdomain']))
				$domain = $_REQUEST['sdomain'];
			else if (!empty($_REQUEST['domain']))
				$domain = $_REQUEST['domain'];
			else
				$error = GetMessage('INTR_MAIL_FORM_ERROR');
		}

		if ($error === false)
		{
			$crDomains = CControllerClient::ExecuteEvent('OnMailControllerGetMemberDomains', array('REGISTERED' => true));
			if (!isset($crDomains['result']) || !is_array($crDomains['result']))
			{
				$error = empty($crDomains['error'])
					? GetMessage('INTR_MAIL_CONTROLLER_INVALID')
					: CMail::getErrorMessage($crDomains['error']);
			}
			else
			{
				if (empty($crDomains['result']))
				{
					if (empty($_REQUEST['eula']) || $_REQUEST['eula'] != 'Y')
						$error = GetMessage('INTR_MAIL_FORM_ERROR');
				}
				else if (strtolower(reset($crDomains['result'])) != strtolower($domain))
				{
					$error = CMail::getErrorMessage(CMail::ERR_API_OP_DENIED);
				}
			}
		}
		
		if ($error === false)
		{
			$crResponse = CControllerClient::ExecuteEvent('OnMailControllerRegDomain', array(
				'DOMAIN' => $domain,
				'IP'     => $_SERVER['REMOTE_ADDR']
			));
			if (!isset($crResponse['result']))
			{
				$error = empty($crResponse['error'])
					? GetMessage('INTR_MAIL_CONTROLLER_INVALID')
					: CMail::getErrorMessage($crResponse['error']);
			}

			if ($error === false)
			{
				$result = \Bitrix\Mail\MailServicesTable::add(array(
					'SITE_ID'      => SITE_ID,
					'ACTIVE'       => 'Y',
					'SERVICE_TYPE' => 'crdomain',
					'NAME'         => $domain,
					'SERVER'       => $domain,
					'ENCRYPTION'   => $_REQUEST['public'] == 'Y' ? 'N' : 'Y',
					'FLAGS'        => CMail::F_DOMAIN_REG
				));

				if ($result->isSuccess())
					CAgent::addAgent('CIntranetUtils::checkMailDomain('.$result->getId().', '.$USER->getId().');', 'intranet', 'N', 600);

				if (!$result->isSuccess())
					$error = GetMessage('INTR_MAIL_SAVE_ERROR');
			}
		}

		return array();
	}

	private static function executeDomainCreate(&$error)
	{
		global $USER;

		$error = false;

		if (!check_bitrix_sessid())
			$error = GetMessage('INTR_MAIL_CSRF');

		if ($error === false)
		{
			if (CIntranetMailSetupHelper::getDomainService())
				$error = GetMessage('INTR_MAIL_AJAX_ERROR');
		}

		if ($error === false)
		{
			$crResponse = CControllerClient::ExecuteEvent('OnMailControllerAddMemberDomain', array(
				'DOMAIN' => $_REQUEST['domain']
			));
			if (!isset($crResponse['result']))
			{
				$error = empty($crResponse['error'])
					? GetMessage('INTR_MAIL_CONTROLLER_INVALID')
					: CMail::getErrorMessage($crResponse['error']);
			}
			else
			{
				$result = $crResponse['result'];

				if (!is_array($result))
					$error = GetMessage('INTR_MAIL_CONTROLLER_INVALID');
				if (!isset($result['stage']) || !in_array($result['stage'], array('owner-check', 'mx-check', 'added')))
					$error = GetMessage('INTR_MAIL_CONTROLLER_INVALID');
				else if ($result['stage'] == 'owner-check' && (!isset($result['secrets']['name']) || !isset($result['secrets']['content'])))
					$error = GetMessage('INTR_MAIL_CONTROLLER_INVALID');

				if ($error === false)
				{
					$domainStage = $result['stage'];
					if ($result['stage'] == 'owner-check')
					{
						$domainSecrets = array(
							'name'    => $result['secrets']['name'],
							'content' => $result['secrets']['content']
						);
					}
				}
			}

			if ($error === false)
			{
				$result = \Bitrix\Mail\MailServicesTable::add(array(
					'SITE_ID'      => SITE_ID,
					'ACTIVE'       => 'Y',
					'SERVICE_TYPE' => 'crdomain',
					'NAME'         => $_REQUEST['domain'],
					'SERVER'       => $_REQUEST['domain'],
					'ENCRYPTION'   => $_REQUEST['public'] == 'Y' ? 'N' : 'Y'
				));

				if ($result->isSuccess())
				{
					CAgent::addAgent('CIntranetUtils::checkMailDomain('.$result->getId().', '.$USER->getId().');', 'intranet', 'N', 600);
					CAgent::addAgent('CIntranetUtils::notifyMailDomain("nocomplete", '.$result->getId().', '.$USER->getId().');', 'intranet', 'N', 3600*24*3);
				}

				if (!$result->isSuccess())
					$error = GetMessage('INTR_MAIL_SAVE_ERROR');
			}
		}

		return array(
			'stage'   => isset($domainStage) ? $domainStage : '',
			'secrets' => isset($domainSecrets) ? $domainSecrets : ''
		);
	}

	private static function executeDomainCheck(&$error)
	{
		$error = false;

		if (!check_bitrix_sessid())
			$error = GetMessage('INTR_MAIL_CSRF');

		if ($error === false)
		{
			$domainService = CIntranetMailSetupHelper::getDomainService();
			if (empty($domainService) || !in_array($domainService['type'], array('crdomain')))
				$error = GetMessage('INTR_MAIL_AJAX_ERROR');
		}

		if ($error === false)
		{
			$crResponse = CControllerClient::ExecuteEvent('OnMailControllerCheckMemberDomain', array(
				'DOMAIN' => $domainService['server']
			));
			if (!isset($crResponse['result']))
			{
				$error = empty($crResponse['error'])
					? GetMessage('INTR_MAIL_CONTROLLER_INVALID')
					: CMail::getErrorMessage($crResponse['error']);
			}
			else
			{
				$result = $crResponse['result'];

				if (!is_array($result))
					$error = GetMessage('INTR_MAIL_CONTROLLER_INVALID');
				if (!isset($result['stage']) || !in_array($result['stage'], array('owner-check', 'mx-check', 'added')))
					$error = GetMessage('INTR_MAIL_CONTROLLER_INVALID');
				else if ($result['stage'] == 'owner-check' && (!isset($result['secrets']['name']) || !isset($result['secrets']['content'])))
					$error = GetMessage('INTR_MAIL_CONTROLLER_INVALID');

				if ($error === false)
				{
					$domainLastCheck = $result['last_check'];
					$domainNextCheck = strtotime($result['next_check']) > time() ? $result['next_check'] : null;
					$domainStage = $result['stage'];
					$domainSecrets = array(
						'name'    => $result['secrets']['name'],
						'content' => $result['secrets']['content']
					);
				}
			}
		}

		return array(
			'last_check' => isset($domainLastCheck) ? CharsetConverter::ConvertCharset(FormatDate(
				array('s' => 'sago', 'i' => 'iago', 'H' => 'Hago', 'd' => 'dago', 'm' => 'mago', 'Y' => 'Yago'),
				strtotime($domainLastCheck)
			), SITE_CHARSET, 'UTF-8') : '',
			'next_check' => isset($domainNextCheck) ? CharsetConverter::ConvertCharset(FormatDate(
				array('s' => 'sdiff', 'i' => 'idiff', 'H' => 'Hdiff', 'd' => 'ddiff', 'm' => 'mdiff', 'Y' => 'Ydiff'),
				time() - (strtotime($domainNextCheck) - time())
			), SITE_CHARSET, 'UTF-8') : '',
			'stage'   => isset($domainStage) ? $domainStage : '',
			'secrets' => isset($domainSecrets) ? $domainSecrets : ''
		);
	}

/*
	private static function executeDomainDelete(&$error)
	{
		global $USER;

		$error = false;

		if (!check_bitrix_sessid())
			$error = GetMessage('INTR_MAIL_CSRF');

		if ($error === false)
		{
			$domainService = CIntranetMailSetupHelper::getDomainService();
			if (empty($domainService) || !in_array($domainService['type'], array('crdomain')))
				$error = GetMessage('INTR_MAIL_AJAX_ERROR');
		}

		if ($error === false)
		{
			$crResponse = CControllerClient::ExecuteEvent('OnMailControllerDeleteMemberDomain', array(
				'DOMAIN' => $_REQUEST['domain']
			));
			if (!isset($crResponse['result']))
			{
				$error = empty($crResponse['error'])
					? GetMessage('INTR_MAIL_CONTROLLER_INVALID')
					: CMail::getErrorMessage($crResponse['error']);
			}

			if ($error === false)
			{
				$result = \Bitrix\Mail\MailServicesTable::delete($domainService['ID']);

				//if (!$result->isSuccess())
				//	$error = GetMessage('INTR_MAIL_SAVE_ERROR');
			}
		}
	}
*/

	private static function handleManageAction($act, &$error)
	{
		global $USER;

		if (!$USER->isAdmin() && !$USER->canDoOperation('bitrix24_config'))
			$error = GetMessage('ACCESS_DENIED');

		if ($error === false)
		{
			switch ($act)
			{
				case 'create':
					return self::executeManageCreateMailbox($error);
					break;
				case 'password':
					return self::executeManageChangePassword($error);
					break;
				case 'delete':
					return self::executeManageDeleteMailbox($error);
					break;
				default:
					$error = GetMessage('INTR_MAIL_AJAX_ERROR');
			}
		}
	}

	private static function executeManageCreateMailbox(&$error)
	{
		$domainUsers = array('vacant' => array(), 'occupied' => array());
		$error = false;

		if (!check_bitrix_sessid())
			$error = GetMessage('INTR_MAIL_CSRF');

		if ($error === false)
		{
			if (!isset($_REQUEST['create']))
			{
				$error = GetMessage('INTR_MAIL_FORM_ERROR');
			}
			else
			{
				$exists = $_REQUEST['create'] == 0;
				$userId = $_REQUEST['USER_ID'];

				if ($exists)
				{
					$serviceId = $_REQUEST['sservice'];
					$domain    = $_REQUEST['sdomain'];
					$login     = $_REQUEST['suser'];
				}
				else
				{
					$serviceId = $_REQUEST['cservice'];
					$domain    = $_REQUEST['cdomain'];
					$login     = $_REQUEST['cuser'];
					$password  = $_REQUEST['password'];
					$password2 = $_REQUEST['password2'];
				}
			}
		}

		if ($error === false)
		{
			if (intval($userId))
			{
				$dbUser = CUser::getList(
					$by = 'ID', $order = 'ASC',
					array('ID_EQUAL_EXACT' => intval($userId)),
					array('FIELDS' => 'ID')
				);
				if (!$dbUser->fetch())
					$error = GetMessage('INTR_MAIL_FORM_ERROR');
			}
		}

		if ($error === false)
		{
			$services = CIntranetMailSetupHelper::getMailServices();
			if (empty($services[$serviceId]) || !in_array($services[$serviceId]['type'], array('controller', 'domain', 'crdomain')))
			{
				$error = GetMessage('INTR_MAIL_FORM_ERROR');
			}
			else
			{
				$service = $services[$serviceId];

				if ($service['type'] == 'controller')
				{
					$crDomains = CControllerClient::ExecuteEvent('OnMailControllerGetDomains', array());
					$arDomains = empty($crDomains['result']) ? array() : $crDomains['result'];
					if (!is_array($arDomains) || !in_array($domain, $arDomains))
						$error = CMail::getErrorMessage(CMail::ERR_API_OP_DENIED);
				}
				else if ($service['type'] == 'crdomain')
				{
					$crDomains = CControllerClient::ExecuteEvent('OnMailControllerGetMemberDomains', array());
					$arDomains = empty($crDomains['result']) ? array() : $crDomains['result'];
					if (!is_array($arDomains) || !in_array($domain, $arDomains))
						$error = CMail::getErrorMessage(CMail::ERR_API_OP_DENIED);
				}
				else if ($service['type'] == 'domain')
				{
					if ($service['server'] != $domain)
						$error = GetMessage('INTR_MAIL_FORM_ERROR');
				}
			}
		}

		if ($error === false)
		{
			if (!$exists && $password != $password2)
				$error = GetMessage('INTR_MAIL_INP_PASSWORD2_BAD');
		}

		if ($error === false)
		{
			if ($service['type'] == 'controller')
			{
				$crCheckName = CControllerClient::ExecuteEvent('OnMailControllerCheckName', array(
					'DOMAIN' => $domain,
					'NAME'   => $login
				));
				if (isset($crCheckName['result']))
				{
					$isExistsNow = (boolean) $crCheckName['result'];
				}
				else
				{
					$error = empty($crCheckName['error'])
						? GetMessage('INTR_MAIL_CONTROLLER_INVALID')
						: CMail::getErrorMessage($crCheckName['error']);
				}
			}
			else if ($service['type'] == 'crdomain')
			{
				$crCheckName = CControllerClient::ExecuteEvent('OnMailControllerCheckMemberName', array(
					'DOMAIN' => $domain,
					'NAME'   => $login
				));
				if (isset($crCheckName['result']))
				{
					$isExistsNow = (boolean) $crCheckName['result'];
				}
				else
				{
					$error = empty($crCheckName['error'])
						? GetMessage('INTR_MAIL_CONTROLLER_INVALID')
						: CMail::getErrorMessage($crCheckName['error']);
				}
			}
			else if ($service['type'] == 'domain')
			{
				$isExistsNow = CMailDomain2::isUserExists($service['token'], $domain, $login, $error);
				if (is_null($isExistsNow))
					$error = CMail::getErrorMessage($error);
			}

			if ($error === false)
			{
				if ($exists)
				{
					if ($isExistsNow == false)
						$error = CMail::getErrorMessage(CMail::ERR_API_USER_NOTFOUND);

					if ($error === false)
					{
						if ($service['type'] == 'controller')
						{
							$crCheckMailbox = CControllerClient::ExecuteEvent('OnMailControllerCheckMailbox', array(
								'DOMAIN' => $domain,
								'NAME'   => $login
							));
							if (!isset($crCheckMailbox['result']))
							{
								$error  = empty($crCheckMailbox['error'])
									? GetMessage('INTR_MAIL_CONTROLLER_INVALID')
									: CMail::getErrorMessage($crCheckMailbox['error']);
							}
						}
					}

					if ($error === false)
					{
						$dbMailbox = CMailbox::getList(
							array(
								'TIMESTAMP_X' => 'ASC'
							),
							array(
								'ACTIVE'   => 'Y',
								'!USER_ID' => intval($userId),
								'=LOGIN'   => $login . '@' . $domain
							)
						);
						if (($mailbox = $dbMailbox->fetch()) && $mailbox['USER_ID'])
							$error = GetMessage('INTR_MAIL_MAILBOX_OCCUPIED');
					}
				}
				else
				{
					if ($isExistsNow == true)
						$error = CMail::getErrorMessage(CMail::ERR_API_NAME_OCCUPIED);

					if ($error === false)
					{
						if (!preg_match('/^[a-z0-9_]+(\.?[a-z0-9_-]*[a-z0-9_]+)*?$/i', $login))
							$error = CMail::getErrorMessage(CMail::ERR_API_BAD_NAME);
					}

					if ($error === false)
					{
						if ($service['type'] == 'controller')
						{
							$crResponse = CControllerClient::ExecuteEvent('OnMailControllerAddUser', array(
								'DOMAIN'   => $domain,
								'NAME'     => $login,
								'PASSWORD' => $password
							));
							if (!isset($crResponse['result']))
							{
								$error = empty($crResponse['error'])
									? GetMessage('INTR_MAIL_CONTROLLER_INVALID')
									: CMail::getErrorMessage($crResponse['error']);
							}
						}
						else if ($service['type'] == 'crdomain')
						{
							$crResponse = CControllerClient::ExecuteEvent('OnMailControllerAddMemberUser', array(
								'DOMAIN'   => $domain,
								'NAME'     => $login,
								'PASSWORD' => $password
							));
							if (!isset($crResponse['result']))
							{
								$error = empty($crResponse['error'])
									? GetMessage('INTR_MAIL_CONTROLLER_INVALID')
									: CMail::getErrorMessage($crResponse['error']);
							}
						}
						else if ($service['type'] == 'domain')
						{
							$result = CMailDomain2::addUser(
								$service['token'],
								$domain, $login, $password,
								$error
							);

							if (is_null($result))
								$error = CMail::getErrorMessage($error);
						}

						if ($error === false)
						{
							if (empty($domainUsers['vacant'][$service['id']]))
								$domainUsers['vacant'][$service['id']] = array();
							if (empty($domainUsers['vacant'][$service['id']][$domain]))
								$domainUsers['vacant'][$service['id']][$domain] = array();
							$domainUsers['vacant'][$service['id']][$domain][] = $login;
						}
					}
				}

				if ($error === false && $userId)
				{
					$mailbox = CIntranetMailSetupHelper::getUserMailbox($userId);
					if (!empty($mailbox))
					{
						$res = CMailbox::delete($mailbox['ID']);
						if (in_array($mailbox['SERVER_TYPE'], array('domain', 'controller', 'crdomain')) && $res)
						{
							list($login_tmp, $domain_tmp) = explode('@', $mailbox['LOGIN'], 2);
							if (empty($domainUsers['vacant'][$mailbox['SERVICE_ID']]))
								$domainUsers['vacant'][$mailbox['SERVICE_ID']] = array();
							if (empty($domainUsers['vacant'][$mailbox['SERVICE_ID']][$domain_tmp]))
								$domainUsers['vacant'][$mailbox['SERVICE_ID']][$domain_tmp] = array();
							$domainUsers['vacant'][$mailbox['SERVICE_ID']][$domain_tmp][] = $login_tmp;
						}
					}

					$arFields = array(
						'LID'         => SITE_ID,
						'ACTIVE'      => 'Y',
						'SERVICE_ID'  => $serviceId,
						'NAME'        => $service['name'],
						'LOGIN'       => $login . '@' . $domain,
						'SERVER_TYPE' => $service['type'],
						'USER_ID'     => intval($userId)
					);

					$res = CMailbox::add($arFields);
					if (!$res)
					{
						$error = GetMessage('INTR_MAIL_SAVE_ERROR');
					}
					else
					{
						if (!empty($domainUsers['vacant'][$serviceId][$domain]))
						{
							if ($key = array_search($login, $domainUsers['vacant'][$serviceId][$domain]))
								array_splice($domainUsers['vacant'][$serviceId][$domain], $key, 1);
						}
						if (empty($domainUsers['occupied'][$serviceId]))
							$domainUsers['occupied'][$serviceId] = array();
						if (empty($domainUsers['occupied'][$serviceId][$domain]))
							$domainUsers['occupied'][$serviceId][$domain] = array();
						$domainUsers['occupied'][$serviceId][$domain][] = $login;
					}
				}
			}
		}

		if ($error === false)
		{
			$email  = $login . '@' . $domain;
			$create = '<a href="#" onclick="mb.create('.intval($userId).'); return false; ">'.GetMessage('INTR_MAIL_MANAGE_CHANGE').'</a>';
			$create .= '<br><a href="#" onclick="mb.changePassword('.intval($userId).'); return false; ">'.GetMessage('INTR_MAIL_MANAGE_PASSWORD').'</a>';
			$delete  = '<a href="#" onclick="mb.remove('.intval($userId).'); return false; ">'.GetMessage('INTR_MAIL_MANAGE_DELETE').'</a>';
		}

		return array(
			'users'  => $domainUsers,
			'email'  => isset($email) ? $email : '',
			'create' => isset($create) ? CharsetConverter::ConvertCharset($create, SITE_CHARSET, 'UTF-8')  : '',
			'delete' => isset($delete) ? CharsetConverter::ConvertCharset($delete, SITE_CHARSET, 'UTF-8') : ''
		);
	}

	private static function executeManageChangePassword(&$error)
	{
		$error = false;

		$userId    = $_REQUEST['USER_ID'];
		$password  = $_REQUEST['password'];
		$password2 = $_REQUEST['password2'];

		if (!check_bitrix_sessid())
			$error = GetMessage('INTR_MAIL_CSRF');

		if ($error === false)
		{
			$mailbox = CIntranetMailSetupHelper::getUserMailbox($userId);
			if (empty($mailbox) || !in_array($mailbox['SERVER_TYPE'], array('controller', 'domain', 'crdomain')))
				$error = GetMessage('INTR_MAIL_FORM_ERROR');
		}

		if ($error === false)
		{
			if ($password != $password2)
				$error = GetMessage('INTR_MAIL_INP_PASSWORD2_BAD');
		}

		if ($error === false)
		{
			list($login, $domain) = explode('@', $mailbox['LOGIN'], 2);

			if ($mailbox['SERVER_TYPE'] == 'domain')
			{
				$domainService = CIntranetMailSetupHelper::getDomainService($mailbox['SERVICE_ID']);

				$result = CMailDomain2::changePassword(
					$domainService['token'],
					$domain, $login, $password,
					$error
				);

				if (is_null($result))
					$error = CMail::getErrorMessage($error);
			}
			else if ($mailbox['SERVER_TYPE'] == 'crdomain')
			{
				$crResponse = CControllerClient::ExecuteEvent('OnMailControllerChangeMemberPassword', array(
					'DOMAIN'   => $domain,
					'NAME'     => $login,
					'PASSWORD' => $password
				));
				if (!isset($crResponse['result']))
				{
					$error = empty($crResponse['error'])
						? GetMessage('INTR_MAIL_CONTROLLER_INVALID')
						: CMail::getErrorMessage($crResponse['error']);
				}
			}
			else if ($mailbox['SERVER_TYPE'] == 'controller')
			{
				$crResponse = CControllerClient::ExecuteEvent('OnMailControllerChangePassword', array(
					'DOMAIN'   => $domain,
					'NAME'     => $login,
					'PASSWORD' => $password
				));
				if (!isset($crResponse['result']))
				{
					$error = empty($crResponse['error'])
						? GetMessage('INTR_MAIL_CONTROLLER_INVALID')
						: CMail::getErrorMessage($crResponse['error']);
				}
			}
		}

		return array(
			'result' => $error === false ? 'ok' : 'error',
			'error'  => CharsetConverter::ConvertCharset($error, SITE_CHARSET, 'UTF-8')
		);
	}

	private static function executeManageDeleteMailbox(&$error)
	{
		$error = false;

		$userId = $_REQUEST['USER_ID'];

		if (!check_bitrix_sessid())
			$error = GetMessage('INTR_MAIL_CSRF');

		if ($error === false)
		{
			$mailbox = CIntranetMailSetupHelper::getUserMailbox($userId);
			if (empty($mailbox) || !in_array($mailbox['SERVER_TYPE'], array('controller', 'domain', 'crdomain')))
				$error = GetMessage('INTR_MAIL_AJAX_ERROR');
		}

		if ($error === false)
		{
			CMailbox::delete($mailbox['ID']);

			list($login, $domain) = explode('@', $mailbox['LOGIN'], 2);

			if ($mailbox['SERVER_TYPE'] == 'domain')
			{
				$domainService = CIntranetMailSetupHelper::getDomainService($mailbox['SERVICE_ID']);

				CMailDomain2::deleteUser($domainService['token'], $domain, $login);
			}
			else if ($mailbox['SERVER_TYPE'] == 'crdomain')
			{
				$crResponse = CControllerClient::ExecuteEvent('OnMailControllerDeleteMemberUser', array(
					'DOMAIN' => $domain,
					'NAME'   => $login
				));
				if (!isset($crResponse['result']))
				{
					$error = empty($crResponse['error'])
						? GetMessage('INTR_MAIL_CONTROLLER_INVALID')
						: CMail::getErrorMessage($crResponse['error']);
				}
			}
			else if ($mailbox['SERVER_TYPE'] == 'controller')
			{
				$crResponse = CControllerClient::ExecuteEvent('OnMailControllerDeleteUser', array(
					'DOMAIN' => $domain,
					'NAME'   => $login
				));
				if (!isset($crResponse['result']))
				{
					$error = empty($crResponse['error'])
						? GetMessage('INTR_MAIL_CONTROLLER_INVALID')
						: CMail::getErrorMessage($crResponse['error']);
				}
			}

			CUserCounter::Clear($userId, 'mail_unseen', $mailbox['LID']);

			CUserOptions::DeleteOption('global', 'last_mail_check_'.$mailbox['LID']);
			CUserOptions::DeleteOption('global', 'last_mail_check_success_'.$mailbox['LID']);
		}

		if ($error === false)
			$create = '<a href="#" onclick="mb.create('.intval($userId).'); return false; ">'.GetMessage('INTR_MAIL_MANAGE_CREATE').'</a>';

		return array(
			'result' => $error === false ? 'ok' : 'error',
			'create' => isset($create) ? CharsetConverter::ConvertCharset($create, SITE_CHARSET, 'UTF-8')  : '',
			'error'  => CharsetConverter::ConvertCharset($error, SITE_CHARSET, 'UTF-8')
		);
	}

	private static function returnJson($data)
	{
		global $APPLICATION;

		$APPLICATION->RestartBuffer();

		header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
		echo json_encode($data);
		die;
	}

}

CIntranetMailSetupAjax::execute();
