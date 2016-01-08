<?php

namespace Bitrix\Disk;


final class CrumbStorage
{
	/** @var  Driver */
	private static $instance;
	/** @var array  */
	private $crumbsByObjectId = array();

	protected function __construct()
	{}

	private function __clone()
	{}

	/**
	 * Returns Singleton of CrumbStorage.
	 * @return CrumbStorage
	 */
	public static function getInstance()
	{
		if (!isset(self::$instance))
		{
			self::$instance = new CrumbStorage;
		}

		return self::$instance;
	}

	/**
	 * Get list of crumbs by object.
	 * @param BaseObject $object BaseObject.
	 * @param bool   $includeSelf Append name of object.
	 * @return array
	 */
	public function getByObject(BaseObject $object, $includeSelf = false)
	{
		if(!isset($this->crumbsByObjectId[$object->getId()]))
		{
			$this->calculateCrumb($object);
		}
		if($includeSelf)
		{
			return $this->crumbsByObjectId[$object->getId()];
		}

		return array_slice($this->crumbsByObjectId[$object->getId()], 0, -1);
	}

	protected function calculateCrumb(BaseObject $object)
	{
		$parentId = $object->getParentId();
		if(!$parentId)
		{
			$this->crumbsByObjectId[$object->getId()] = array($object->getName());
			return $this->crumbsByObjectId[$object->getId()];
		}

		if(isset($this->crumbsByObjectId[$parentId]))
		{
			$this->crumbsByObjectId[$object->getId()] = $this->crumbsByObjectId[$parentId];
			$this->crumbsByObjectId[$object->getId()][] = $object->getName();

			return $this->crumbsByObjectId[$object->getId()];
		}

		$storage = $object->getStorage();
		$fake = Driver::getInstance()->getFakeSecurityContext();

		$this->crumbsByObjectId[$object->getId()] = array();
		foreach($object->getParents($fake, array('select' => array('ID', 'NAME', 'TYPE')), SORT_DESC) as $parent)
		{
			if($parent->getId() == $storage->getRootObjectId())
			{
				continue;
			}
			$this->crumbsByObjectId[$object->getId()][] = $parent->getName();
		}
		unset($parent);

		$this->crumbsByObjectId[$parentId] = $this->crumbsByObjectId[$object->getId()];
		$this->crumbsByObjectId[$object->getId()][] = $object->getName();

		return $this->crumbsByObjectId[$object->getId()];
	}
}