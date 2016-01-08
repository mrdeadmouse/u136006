<?php

namespace Bitrix\Main\UI;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Security\Sign\Signer;
use \Bitrix\Main\Security\Sign\BadSignatureException;
use Bitrix\Main\UI\Uploader\Status;
use \Bitrix\Main\UI\Uploader\Uploader;
use \Bitrix\Main\UI\Uploader\Error;

Loc::loadMessages(__FILE__);
class FileInputReceiver
{
	protected $signature;
	protected $status = array();
	protected $id = "unknown";
	protected $uploader;

	const STATUS_SUCCESS      = 'success';
	const STATUS_DENIED       = 'denied';
	const STATUS_ERROR        = 'error';
	const STATUS_NEED_AUTH    = 'need_auth';
	const STATUS_INVALID_SIGN = 'invalid_sign';

	public function setSignature($signature)
	{
		$this->signature = $signature;
		return $this;
	}

	protected function getAgent()
	{
		if (!($this->uploader instanceof Uploader))
		{
			$this->uploader = new Uploader(array(), "get");
			$this->uploader->setHandler("onFileIsUploaded", array($this, "handleFile"));
		}
		return $this->uploader;
	}

	protected function check()
	{
		$this->status = new Status();
		if(!$GLOBALS["USER"]->IsAuthorized())
		{
			$this->status = new Error(self::STATUS_DENIED, Loc::getMessage("BXU_AccessDenied_Authorize"));
		}
		else
		{
			try
			{
				$sign = new Signer;
				$params = unserialize(base64_decode($sign->unsign($this->signature, "fileinput")));
				$this->id = $params["id"];
				$this->getAgent()->setParams($params);
			}
			catch (BadSignatureException $e)
			{
				$this->status = new Error(self::STATUS_INVALID_SIGN, Loc::getMessage("BXU_AccessDenied_SignBetter"));
			}
		}
		return !($this->status instanceof Error);
	}

	public static function sign($params = array())
	{
		$sign = new Signer();
		return $sign->sign(base64_encode(serialize($params)), "fileinput");
	}

	protected static function handleFileByPath($hash, &$file)
	{
		$key = "default";
		$path = $file["files"][$key]["tmp_name"];
		$file["path"] = substr($path, 0, (strlen($path) - strlen($key))).$file["name"];
		if ((!file_exists($file["path"]) || @unlink($file["path"])) && @rename($file["files"]["default"]["tmp_name"], $file["path"]))
			$file["files"][$key]["tmp_name"] = $file["path"];
		else
			$file["path"] = $path;
		if (strpos($file["path"], $_SERVER["DOCUMENT_ROOT"]) === 0)
			$file["path"] = str_replace("//", "/", "/".substr($file["path"], strlen($_SERVER["DOCUMENT_ROOT"])));

		$file["files"][$key]["url"] = $file["path"];

		$file["uploadId"] = $file["path"];
		$file["type"] = $path = $file["files"][$key]["type"];
		return true;
	}

	protected static function handleFileByHash($hash, &$file)
	{
		$file["uploadId"] = $hash;
		return true;
	}

	public function handleFile($hash, &$file)
	{
		if ($this->id == "path")
		{
			return self::handleFileByPath($hash, $file);
		}
		return self::handleFileByHash($hash, $file);
	}

	public function exec()
	{
		if (!$this->check())
			$this->getAgent()->showError($this->status);
		$this->getAgent()->checkPost();
	}
}
?>