<?php
/**
 * Closure table tree implementation
 * 
 * Tree struct and data fields are kept in separate tables
 * 
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 * 
 * @access private
 */
namespace Bitrix\Tasks\DB;

use Bitrix\Main;
use Bitrix\Main\DB;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

use Bitrix\Tasks\Util\Assert;

Loc::loadMessages(__FILE__);

abstract class Tree extends Entity\DataManager
{
	public static function link($id, $parentId)
	{
		try
		{
			return static::moveLink($id, $parentId);
		}
		catch(Tree\TargetNodeNotFoundException $e)
		{
			return static::createLink($id, $parentId);
		}
	}

	public static function unlink($id)
	{
		return static::dropLinkL($id);
	}

	/**
	 * Links one item with another. Low-level method.
	 */
	public static function createLink($id, $parentId, $behaviour = array())
	{
		$id = 			Assert::expectIntegerPositive($id, '$id');
		$parentId = 	Assert::expectIntegerNonNegative($parentId, '$parentId'); // parent id might be equal to 0
		if(!is_array($behaviour))
			$behaviour = array();

		$parentColName = static::getPARENTIDColumnName();
		$idColName = static::getIDColumnName();

		// delete broken previous links, if any
		$dbConnection = Main\HttpApplication::getConnection();
		$dbConnection->query("delete from ".static::getTableName()." where ".$idColName." = '".intval($id)."'");

		$success = true;
		$lastAddResult = null;

		// check if parent exists. if not - add it
		$item = static::getList(array('filter' => array('='.$parentColName => $parentId, '='.$idColName => $parentId), 'select' => array($idColName)))->fetch();
		if(!is_array($item))
		{
			$lastAddResult = parent::add(array($parentColName => $parentId, $idColName => $parentId));
			if(!$lastAddResult->isSuccess())
				$success = false;
		}

		$linkedWithParent = false;

		// the following part could be rewritten using just db-side insert-select

		if($success)
		{
			if($parentId > 0)
			{
				// link to all parents in the path
				$res = static::getPathToNode($parentId, array('select' => array($parentColName), 'filter' => array('!='.$parentColName => $parentId)));
				while($item = $res->fetch())
				{
					if($item[$parentColName] == $parentId)
						$linkedWithParent = true;

					$lastAddResult = parent::add(array($idColName => $id, $parentColName => $item[$parentColName]));
					if(!$lastAddResult->isSuccess())
					{
						$success = false;
						break;
					}
				}
			}
		}

		if($success)
		{
			// link to itself
			$lastAddResult = parent::add(array($idColName => $id, $parentColName => $id));
			if(!$lastAddResult->isSuccess())
			{
				$success = false;
			}
		}

		if($success && !$linkedWithParent)
		{
			// link to parent
			$lastAddResult = parent::add(array($idColName => $id, $parentColName => $parentId, 'DIRECT' => true));
			if(!$lastAddResult->isSuccess())
			{
				$success = false;
			}
		}

		return array('RESULT' => $success, 'LAST_DB_RESULT' => $lastAddResult);
	}

	/**
	 * Moves subtree. Low-level method.
	 */
	public static function moveLink($id, $parentId, $behaviour = array('CREATE_PARENT_NODE_ON_NOTFOUND' => true))
	{
		$id = 				Assert::expectIntegerPositive($id, '$id');
		$parentId = 		Assert::expectIntegerNonNegative($parentId, '$parentId'); // 0 allowed - means "detach into a separate branch"
		if(!is_array($behaviour))
			$behaviour = array();
		if(!isset($behaviour['CREATE_PARENT_NODE_ON_NOTFOUND']))
			$behaviour['CREATE_PARENT_NODE_ON_NOTFOUND'] = true;

		$parentColName = 	static::getPARENTIDColumnName();
		$idColName = 		static::getIDColumnName();

		if(!static::checkNodeExists($id))
			throw new Tree\TargetNodeNotFoundException('Node not found: '.$id);

		$dbConnection = Main\HttpApplication::getConnection();

		if($parentId > 0)
		{
			if(!static::checkNodeExists($parentId))
			{
				if($behaviour['CREATE_PARENT_NODE_ON_NOTFOUND'])
				{
					if(!static::add(array($parentColName => $parentId, $idColName => $parentId))->isSuccess())
						throw new Tree\Exception('Can not create node: '.$parentId);
				}
				else
					throw new Tree\ParentNodeNotFoundException('Node not found: '.$parentId);
			}

			if(static::checkLinkExists($id, $parentId))
				throw new Tree\LinkExistsException('Link already exists between '.$id.' and '.$parentId.'(p)');

			$check = $dbConnection->query("select ".$idColName." from ".static::getTableName()." where ".$parentColName." = '".intval($id)."' and ".$idColName." = '".intval($parentId)."'")->fetch();
			if(is_array($check))
				throw new Main\ArgumentException('Can not move tree inside itself');
		}

		// detach subtree
		$dbConnection->query("delete 
			from ".static::getTableName()." 
			where 
				".$idColName." in (
					".Helper::getTemporaryTableSubQuerySql(static::getSubTreeSql($id), $idColName)."
				) 
				and
				".$parentColName." in (
					".Helper::getTemporaryTableSubQuerySql(static::getPathToNodeSql($id), $parentColName)."
				)
				and 
				".$idColName." != ".$parentColName./*exclude links to selves*/"
				and
				".$parentColName." != '".intval($id)./*exclude link to root node of a tree being detached*/"'
		");

		if($parentId > 0)
		{
			// reattach subtree to other path
			$res = static::getPathToNode($parentId, array('select' => array('ID' => $parentColName)));
			while($item = $res->fetch())
			{
				$dbConnection->query(
					"insert into ".static::getTableName()." 
						
						(".$parentColName.", ".$idColName.", DIRECT) 
						
						select 
							'".$item['ID']."',
							T.".$idColName.",

							".(
								$item['ID'] == $parentId
								?
								"
								CASE 
									WHEN 
										T.".$idColName." = '".$id."'
									THEN
										'1'
									ELSE
										'0'
								END
								"
								:
								"'0'"
							)."
						from 
							".static::getTableName()." T 
						where 
							".$parentColName." = '".intval($id)."'"
				);
			}
		}

		return true;
	}

	/**
	 * Breaks link between nodes. Low-level method.
	 */
	public static function dropLinkL($id, $parentId = false, $behaviour = array('CHILDREN' => 'unlink'))
	{
		$id = Assert::expectIntegerPositive($id, '$id');

		if($parentId !== false) // parent id === false means that all links with all parents will be broken
			$parentId = Assert::expectIntegerPositive($parentId, '$parentId');

		if(!is_array($behaviour))
			$behaviour = array();
		if(!isset($behaviour['CHILDREN']))
			$behaviour['CHILDREN'] = 'unlink';

		if(!static::checkNodeExists($id))
			throw new Tree\TargetNodeNotFoundException('Node not found: '.$id);

		$parentColName = static::getPARENTIDColumnName();
		$idColName = static::getIDColumnName();

		$dbConnection = Main\HttpApplication::getConnection();

		if($behaviour['CHILDREN'] == 'unlink')
		{
			$dbConnection->query("delete from ".static::getTableName()." where 
				".$idColName." in (
					".Helper::getTemporaryTableSubQuerySql("select ".$idColName." from ".static::getTableName()." where ".$parentColName." = '".intval($id)."'", $idColName)."
				)");
		}
		elseif($behaviour['CHILDREN'] == 'relocate')
		{
			throw new Main\NotImplementedException();
		}
	}

	public static function getPathToNode($id, $parameters = array()) // $behaviour = array('SHOW_LEAF' => true)
	{
		$id = Assert::expectIntegerPositive($id, '$id');

		if(!is_array($parameters))
			$parameters = array();

		$parameters['filter']['='.static::getIDColumnName()] = $id;

		return static::getList($parameters);
	}

	// returns an sql that selects all children of a particular node
	public static function getPathToNodeSql($id)
	{
		$id = Assert::expectIntegerPositive($id, '$id');

		$parentColName = 	static::getPARENTIDColumnName();
		$idColName = 		static::getIDColumnName();

		return "select ".$parentColName." from ".static::getTableName()." where ".$idColName." = '".intval($id)."'";
	}

	public static function getSubTree($id, $parameters)
	{
		$id = Assert::expectIntegerPositive($id, '$id');

		$parameters['filter']['='.static::getPARENTIDColumnName()] = $id;

		return self::getList($parameters);
	}

	// returns an sql that selects all children of a particular node
	public static function getSubTreeSql($id)
	{
		$id = Assert::expectIntegerPositive($id, '$id');

		$parentColName = 	static::getPARENTIDColumnName();
		$idColName = 		static::getIDColumnName();

		return "select ".$idColName." from ".static::getTableName()." where ".$parentColName." = '".intval($id)."'";
	}

	protected static function checkNodeExists($id)
	{
		$parentColName = static::getPARENTIDColumnName();
		$idColName = static::getIDColumnName();

		$id = intval($id);
		if(!$id)
			return false;

		$item = Main\HttpApplication::getConnection()->query("select ".$idColName." from ".static::getTableName()." where ".$idColName." = '".$id."' and ".$parentColName." = '".$id."'")->fetch();
		return is_array($item);
	}

	protected static function checkLinkExists($id, $parentId)
	{
		$parentColName = static::getPARENTIDColumnName();
		$idColName = static::getIDColumnName();

		$id = intval($id);
		$parentId = intval($parentId);
		if(!$id || !$parentId)
			return false;

		$item = Main\HttpApplication::getConnection()->query("select ".$idColName." from ".static::getTableName()." where ".$idColName." = '".$id."' and ".$parentColName." = '".$parentId."' and DIRECT = '1'")->fetch();
		return is_array($item);
	}

	public static function getIDColumnName()
	{
		return 'ID';
	}

	public static function getPARENTIDColumnName()
	{
		return 'PARENT_ID';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap($entityName)
	{
		return array(

			'DIRECT' => array(
				'data_type' => 'boolean',
			),

			// parent node
			'PARENT_NODE' => array(
				'data_type' => $entityName,
				'reference' => array(
					'=this.'.static::getIDColumnName() => 'ref.'.static::getIDColumnName(),
					'=ref.DIRECT' => array('?', '1')
				),
				'join_type' => 'inner'
			),

			// all parent nodes (path to root node)
			'PARENT_NODES' => array(
				'data_type' => $entityName,
				'reference' => array(
					'=this.'.static::getIDColumnName() => 'ref.'.static::getIDColumnName()
				),
				'join_type' => 'inner'
			),

			// all subtree
			'CHILD_NODES' => array(
				'data_type' => $entityName,
				'reference' => array(
					'=this.'.static::getIDColumnName() => 'ref.'.static::getPARENTIDColumnName()
				),
				'join_type' => 'inner'
			),

			// only direct ancestors
			'CHILD_NODES_DIRECT' => array(
				'data_type' => $entityName,
				'reference' => array(
					'=this.'.static::getIDColumnName() => 'ref.'.static::getPARENTIDColumnName(),
					'=ref.DIRECT' => array('?', '1')
				),
				'join_type' => 'inner'
			),
		);
	}

	/*
	public static function checkNodeIsParentOfNode($id, $childPrimary, $behaviour = array('CHECK_DIRECT' => false))
	{
	}

	public static function getPathToMultipleNodes($nodeInfo = array(), $parameters = array(), $behaviour = array('SHOW_LEAF' => true))
	{
	}

	public static function getDeepestCommonParent($nodeInfo = array(), $parameters = array())
	{
	}

	public static function getChildren($primary, $parameters = array())
	{
	}
	
	public static function getSubTree($primary, $parameters = array())
	{
	}
	public static function getParentTree($primary, $parameters = array(), $behaviour = array('SHOW_CHILDREN' => true, 'START_FROM' => false))
	{
		return self::getList($parameters);
	}

	/////////////////////////
	/// PROTECTED
	/////////////////////////

	// in-deep tree walk, could be usefull with callable to call on each node found
	protected final static function walkTreeInDeep()
	{
	}
	*/
}