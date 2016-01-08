<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sender
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sender;

use Bitrix\Main\EventResult;

class Subscription
{
	const MODULE_ID = 'sender';

	/**
	 * @param array $fields
	 * @return string
	 */
	public static function getLinkUnsub(array $fields)
	{
		return \Bitrix\Main\Mail\Tracking::getLinkUnsub(static::MODULE_ID, $fields, \Bitrix\Main\Config\Option::get('sender', 'unsub_link'));
	}

	/**
	 * @param array $arFields
	 * @return string
	 */
	public static function getLinkSub(array $fields)
	{
		$tag = \Bitrix\Main\Mail\Tracking::getSignedTag(static::MODULE_ID, $fields);
		$urlPage = \Bitrix\Main\Config\Option::get('sender', 'sub_link');
		if($urlPage == "")
		{
			$bitrixDirectory = \Bitrix\Main\Application::getInstance()->getPersonalRoot();
			$result = $bitrixDirectory.'/tools/sender_sub_confirm.php?sender_subscription=confirm&tag='.urlencode($tag);
		}
		else
		{
			$result = $urlPage.(strpos($urlPage, "?")===false ? "?" : "&").'sender_subscription=confirm&tag='.urlencode($tag);
		}

		return $result;
	}

	/**
	 * @param $arData
	 * @return mixed
	 */
	public static function onMailEventSubscriptionList($arData)
	{
		$arData['LIST'] = static::getList($arData);

		return $arData;
	}

	/**
	 * @param $arData
	 * @return EventResult
	 */
	public static function onMailEventSubscriptionEnable($arData)
	{
		$arData['SUCCESS'] = static::subscribe($arData);
		if($arData['SUCCESS'])
			$result = EventResult::SUCCESS;
		else
			$result = EventResult::ERROR;

		return new EventResult($result, $arData, static::MODULE_ID);
	}

	/**
	 * @param $arData
	 * @return EventResult
	 */
	public static function onMailEventSubscriptionDisable($arData)
	{
		$arData['SUCCESS'] = static::unsubscribe($arData);
		if($arData['SUCCESS'])
			$result = EventResult::SUCCESS;
		else
			$result = EventResult::ERROR;

		return new EventResult($result, $arData, static::MODULE_ID);
	}

	/**
	 * @param $arData
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function getList($arData)
	{
		$arMailing = array();

		if(isset($arData['TEST']) && $arData['TEST'] == 'Y')
		{
			$mailing = MailingTable::getRowById(array('ID' => $arData['MAILING_ID']));
			if($mailing)
			{
				$arMailing[] = array(
					'ID' => $mailing['ID'],
					'NAME' => $mailing['NAME'],
					'DESC' => $mailing['DESCRIPTION'],
					'SELECTED' => true,
				);
			}

			return $arMailing;
		}

		$mailingUnsub = array();
		$recipientUnsubDb = PostingUnsubTable::getList(array(
			'select' => array('MAILING_ID' => 'POSTING.MAILING_ID'),
			'filter' => array('=POSTING_RECIPIENT.EMAIL' => trim(strtolower($arData['EMAIL'])))
		));
		while($recipientUnsub = $recipientUnsubDb->fetch())
			$mailingUnsub[] = $recipientUnsub['MAILING_ID'];

		$mailingList = array();
		// all receives mailings
		$mailingDb = PostingRecipientTable::getList(array(
			'select' => array('MAILING_ID' => 'POSTING.MAILING.ID'),
			'filter' => array(
				'=EMAIL' => trim(strtolower($arData['EMAIL'])),
				'=POSTING.MAILING.ACTIVE' => 'Y',
			),
			'group' => array('MAILING_ID')
		));
		while ($mailing = $mailingDb->fetch())
		{
			$mailingList[] = $mailing['MAILING_ID'];
		}

		// all subscribed mailings
		$mailingDb = MailingSubscriptionTable::getList(array(
			'select' => array('MAILING_ID'),
			'filter' => array(
				'=CONTACT.EMAIL' => trim(strtolower($arData['EMAIL'])),
				'=MAILING.ACTIVE' => 'Y',
			)
		));
		while ($mailing = $mailingDb->fetch())
		{
			$mailingList[] = $mailing['MAILING_ID'];
		}

		$mailingList = array_unique($mailingList);
		foreach($mailingList as $mailingId)
		{
			if(!in_array($mailingId, $mailingUnsub))
			{
				$mailingDesc = MailingTable::getRowById($mailingId);
				if($mailingDesc)
				{
					$arMailing[] = array(
						'ID' => $mailingDesc['ID'],
						'NAME' => $mailingDesc['NAME'],
						'DESC' => $mailingDesc['DESCRIPTION'],
						'SELECTED' => in_array($mailingDesc['ID'], array($arData['MAILING_ID'])),
					);
				}
			}
		}

		return $arMailing;
	}

	/**
	 * @param $arData
	 * @return bool
	 */
	public static function subscribe($arData)
	{
		$sub = array('MAILING_ID' => $arData['SUBSCRIBE_LIST'], 'EMAIL' => $arData['EMAIL']);
		$subExists = PostingUnsubTable::getRowById($sub);
		if(!$subExists)
			MailingSubscriptionTable::add($sub);

		return false;
	}

	/**
	 * @param $arData
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function unsubscribe($arData)
	{
		$result = false;

		if(isset($arData['TEST']) && $arData['TEST'] == 'Y')
			return true;

		$arPosting = null;
		if($arData['RECIPIENT_ID'])
		{
			$postingDb = PostingRecipientTable::getList(array(
				'select' => array('POSTING_ID', 'POSTING_MAILING_ID' => 'POSTING.MAILING_ID'),
				'filter' => array('ID' => $arData['RECIPIENT_ID'], 'EMAIL' => $arData['EMAIL'])
			));
			$arPosting = $postingDb->fetch();
		}

		$mailingDb = MailingTable::getList(array(
			'select' => array('ID'),
			'filter' => array(
				'ID' => $arData['UNSUBSCRIBE_LIST'],
			)
		));
		while($mailing = $mailingDb->fetch())
		{
			$unsub = null;

			if($arPosting && $arPosting['POSTING_MAILING_ID'] == $mailing['ID'])
			{
				$unsub = array(
					'POSTING_ID' => $arPosting['POSTING_ID'],
					'RECIPIENT_ID' => $arData['RECIPIENT_ID'],
				);
			}
			else
			{
				$mailingPostingDb = PostingRecipientTable::getList(array(
					'select' => array('RECIPIENT_ID' => 'ID', 'POSTING_ID'),
					'filter' => array('=POSTING.MAILING_ID' => $mailing['ID'], 'EMAIL' => $arData['EMAIL'])
				));
				if($arMailingPosting = $mailingPostingDb->fetch())
				{
					$unsub = $arMailingPosting;
				}
			}

			if(!empty($unsub))
			{
				$unsubExists = PostingUnsubTable::getRowById($unsub);
				if(!$unsubExists)
				{
					PostingUnsubTable::add($unsub);
				}

				$result = true;
			}

			$contactDb = ContactTable::getList(array(
				'select' => array('ID'),
				'filter' => array('=EMAIL' => $arData['EMAIL'])
			));
			while($contact = $contactDb->fetch())
			{
				MailingSubscriptionTable::delete(array('MAILING_ID' => $mailing['ID'], 'CONTACT_ID' => $contact['ID']));
				$result = true;
			}
		}

		return $result;
	}

	/**
	 * @param string $email
	 * @param array $mailingList
	 * @param string $siteId
	 * @return int $contactId
	 * //add subscription and returns subscription id
	 */
	public static function add($email,  array $mailingIdList)
	{
		$contactId = null;

		$email = strtolower($email);
		$contactDb = ContactTable::getList(array('filter' => array('=EMAIL' => $email)));
		if($contact = $contactDb->fetch())
		{
			$contactId = $contact['ID'];
		}
		else
		{
			$contactAddDb = ContactTable::add(array('EMAIL' => $email));
			if($contactAddDb->isSuccess())
				$contactId = $contactAddDb->getId();
		}

		if(!empty($contactId))
		{
			foreach ($mailingIdList as $mailingId)
			{
				$primary = array('MAILING_ID' => $mailingId, 'CONTACT_ID' => $contactId);
				$existSub = MailingSubscriptionTable::getRowById($primary);
				if (!$existSub) MailingSubscriptionTable::add($primary);
			}
		}
		else
		{

		}

		return $contactId;
	}

	/**
	 * @param array $params
	 * @return array
	 * // get mailing list
	 */
	public static function getMailingList($params)
	{
		$filter = array("ACTIVE" => "Y");
		if(isset($params["SITE_ID"]))
			$filter["SITE_ID"] = $params["SITE_ID"];
		if(isset($params["IS_PUBLIC"]))
			$filter["IS_PUBLIC"] = $params["IS_PUBLIC"];
		if(isset($params["ACTIVE"]))
			$filter["ACTIVE"] = $params["ACTIVE"];
		if(isset($params["ID"]))
			$filter["ID"] = $params["ID"];

		$mailingList = array();
		$mailingDb = MailingTable::getList(array(
			'select' => array('ID', 'NAME', 'DESCRIPTION', 'IS_PUBLIC'),
			'filter' => $filter,
			'order' => array('SORT' => 'ASC', 'NAME' => 'ASC'),
		));
		while($mailing = $mailingDb->fetch())
		{
			$mailingList[] = $mailing;
		}

		return $mailingList;
	}

	/**
	 * @param string $email
	 * @param array $mailingList
	 * @param string $siteId
	 * @return void
	 * //send email with url to confirmation of subscription
	 */
	public static function sendEventConfirm($email, array $mailingIdList, $siteId)
	{
		$mailingNameList = array();
		$mailingDb = MailingTable::getList(array('select' => array('NAME'), 'filter' => array('ID' => $mailingIdList)));
		while($mailing = $mailingDb->fetch())
		{
			$mailingNameList[] = $mailing['NAME'];
		}

		$subscription = array(
			'EMAIL' => $email,
			'SITE_ID' => $siteId,
			'MAILING_LIST' => $mailingIdList,
		);
		$confirmUrl = static::getLinkSub($subscription);
		$date = new \Bitrix\Main\Type\DateTime;
		$eventSendFields = array(
			"EVENT_NAME" => "SENDER_SUBSCRIBE_CONFIRM",
			"C_FIELDS" => array(
				"EMAIL" => $email,
				"DATE" => $date->toString(),
				"CONFIRM_URL" => $confirmUrl,
				"MAILING_LIST" => implode("\r\n",$mailingNameList),
			),
			"LID" => is_array($siteId)? implode(",", $siteId): $siteId,
		);
		\Bitrix\Main\Mail\Event::send($eventSendFields);
	}

}
