<?
namespace Bitrix\Tasks\DB\Tree;

class Exception							extends \Bitrix\Main\SystemException {};
class NodeNotFoundException				extends Exception {};
class TargetNodeNotFoundException		extends NodeNotFoundException {};
class ParentNodeNotFoundException		extends NodeNotFoundException {};
class LinkExistsException				extends Exception {};