<?php
namespace Bitrix\Crm;
use Bitrix\Main;
class PhaseSemantics
{
	const UNDEFINED = '';
	const PROCESS = 'P';
	const SUCCESS = 'S';
	const FAILURE = 'F';
	//const APOLOGY = 'A';
	private static $messagesLoaded = false;
	private static $descriptions = null;
	/**
	* @return boolean
	*/
	public static function isDefined($semanticID)
	{
		if(!is_string($semanticID))
		{
			return false;
		}

		$semanticID = strtoupper($semanticID);
		return $semanticID === self::PROCESS
			|| $semanticID === self::SUCCESS
			|| $semanticID === self::FAILURE;
	}
	/**
	* @return array Array of strings
	*/
	public static function getProcessSemantis()
	{
		return array(self::PROCESS);
	}
	/**
	* @return array Array of strings
	*/
	public static function getFinalSemantis()
	{
		return array(self::SUCCESS, self::FAILURE);
	}
	/**
	* @return boolean
	*/
	public static function isFinal($semanticID)
	{
		if(!is_string($semanticID))
		{
			return false;
		}

		$semanticID = strtoupper($semanticID);
		return $semanticID === self::SUCCESS || $semanticID === self::FAILURE;
	}
	/**
	* @return boolean
	*/
	public static function isLost($semanticID)
	{
		if(!is_string($semanticID))
		{
			return false;
		}

		$semanticID = strtoupper($semanticID);
		return $semanticID === self::FAILURE;
	}
	/**
	* @return array Array of strings
	*/
	public static function getAllDescriptions()
	{
		if(!self::$descriptions)
		{
			self::includeModuleFile();

			self::$descriptions = array(
				self::UNDEFINED => GetMessage('CRM_PHASE_SEMANTICS_UNDEFINED'),
				self::PROCESS => GetMessage('CRM_PHASE_SEMANTICS_PROCESS'),
				self::SUCCESS => GetMessage('CRM_PHASE_SEMANTICS_SUCCESS'),
				self::FAILURE => GetMessage('CRM_PHASE_SEMANTICS_FAILURE')
			);
		}
		return self::$descriptions;
	}
	/**
	* @return void
	*/
	protected static function includeModuleFile()
	{
		if(self::$messagesLoaded)
		{
			return;
		}

		Main\Localization\Loc::loadMessages(__FILE__);
		self::$messagesLoaded = true;
	}
}