<?php

namespace Bitrix\Disk\Uf;

final class BlogPostCommentConnector extends StubConnector
{
	private $canRead = null;

	/**
	 * @param $userId
	 * @return bool
	 */
	public function canRead($userId)
	{
		if(isset($this->canRead))
		{
			return $this->canRead;
		}

		$connector = BlogPostConnector::createFromBlogPostCommentConnector($this);
		$this->canRead = $connector->canRead($userId);

		return $this->canRead;
	}

	/**
	 * @param $userId
	 * @return bool
	 */
	public function canUpdate($userId)
	{
		if(isset($this->canRead))
		{
			return $this->canRead;
		}

		return $this->canRead($userId);
	}

	/**
	 * @inheritdoc
	 */
	public function canConfidenceReadInOperableEntity()
	{
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function canConfidenceUpdateInOperableEntity()
	{
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function addComment($authorId, array $data)
	{
		$connector = BlogPostConnector::createFromBlogPostCommentConnector($this);
		$connector->addComment($authorId, $data);
	}
}
