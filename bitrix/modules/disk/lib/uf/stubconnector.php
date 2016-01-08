<?php

namespace Bitrix\Disk\Uf;

class StubConnector extends Connector
{
	/**
	 * @inheritdoc
	 */
	public function getDataToShow()
	{
		return array();
	}

	/**
	 * @inheritdoc
	 */
	public function addComment($authorId, array $data)
	{
		return;
	}

	/**
	 * @inheritdoc
	 */
	public function canRead($userId)
	{
		$file = $this->attachedObject->getObject();
		$securityContext = $file
			->getStorage()
			->getSecurityContext($userId)
		;

		return $file->canRead($securityContext);
	}

	/**
	 * @inheritdoc
	 */
	public function canUpdate($userId)
	{
		$file = $this->attachedObject->getObject();
		$securityContext = $file
			->getStorage()
			->getSecurityContext($userId)
		;

		return $file->canUpdate($securityContext);
	}
}
