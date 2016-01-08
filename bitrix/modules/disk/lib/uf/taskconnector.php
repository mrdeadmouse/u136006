<?php

namespace Bitrix\Disk\Uf;

use Bitrix\Disk\Ui;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

final class TaskConnector extends StubConnector
{
	private $canRead = null;
	private $taskPostData;

	public function canRead($userId)
	{
		if($this->canRead !== null)
		{
			return $this->canRead;
		}

		$data = $this->loadTaskData($userId);
		$this->canRead = !empty($data);

		return $this->canRead;
	}

	public function canUpdate($userId)
	{
		return $this->canRead($userId);
	}

	public function getDataToShow()
	{
		$data = $this->loadTaskData($this->getUser()->getId());
		if(!$data)
		{
			return null;
		}
		return array(
			'TITLE' => Loc::getMessage('DISK_UF_TASK_CONNECTOR_TITLE', array('#ID#' => $this->entityId)),
			'DETAIL_URL' => null,
			'DESCRIPTION' => Ui\Text::killTags($data['TITLE']),
			'MEMBERS' => $this->getDestinations(),
		);
	}

	protected function getDestinations()
	{
		if($this->taskPostData === null)
		{
			return array();
		}
		$members = array();

		if(!empty($this->taskPostData['RESPONSIBLE_ID']))
		{
			$members[] = array(
				"NAME" => \CUser::formatName('#NAME# #LAST_NAME#', array(
					'NAME' => $this->taskPostData['RESPONSIBLE_NAME'],
					'LAST_NAME' => $this->taskPostData['RESPONSIBLE_LAST_NAME'],
					'SECOND_NAME' => $this->taskPostData['RESPONSIBLE_SECOND_NAME'],
					'ID' => $this->taskPostData['RESPONSIBLE_ID'],
					'LOGIN' => $this->taskPostData['RESPONSIBLE_LOGIN'],
				), true, false),
				"LINK" => \CComponentEngine::makePathFromTemplate($this->getPathToUser(), array("user_id" => $this->taskPostData['RESPONSIBLE_ID'])),
				'AVATAR_SRC' => Ui\Avatar::getPerson($this->taskPostData['RESPONSIBLE_PHOTO']),
				"IS_EXTRANET" => "N",
			);
		}
		if(!empty($this->taskPostData['CREATED_BY']))
		{
			$members[] = array(
				"NAME" => \CUser::formatName('#NAME# #LAST_NAME#', array(
					'NAME' => $this->taskPostData['CREATED_BY_NAME'],
					'LAST_NAME' => $this->taskPostData['CREATED_BY_LAST_NAME'],
					'SECOND_NAME' => $this->taskPostData['CREATED_BY_SECOND_NAME'],
					'ID' => $this->taskPostData['CREATED_BY'],
					'LOGIN' => $this->taskPostData['CREATED_BY_LOGIN'],
				), true, false),
				"LINK" => \CComponentEngine::makePathFromTemplate($this->getPathToUser(), array("user_id" => $this->taskPostData['CREATED_BY'])),
				'AVATAR_SRC' => Ui\Avatar::getPerson($this->taskPostData['CREATED_BY_PHOTO']),
				"IS_EXTRANET" => "N",
			);
		}

		return $members;
	}

	protected function loadTaskData($userId)
	{
		if($this->taskPostData === null)
		{
			try
			{
				$task = new \CTaskItem($this->entityId, $userId);
				$this->taskPostData = $task->getData(false);
			}
			catch(\TasksException $e)
			{
				return array();
			}
		}
		return $this->taskPostData;
	}

	public function addComment($authorId, array $data)
	{
		$fields = array();
		if(!empty($data['fileId']))
		{
			$fields['UF_FORUM_MESSAGE_DOC'] = array($data['fileId']);
		}
		elseif(!empty($data['versionId']))
		{
			$fields['UF_FORUM_MESSAGE_VER'] = $data['versionId'];
		}
		\CTaskComments::add($this->entityId, $authorId, $data['text'], $fields);
	}
}
