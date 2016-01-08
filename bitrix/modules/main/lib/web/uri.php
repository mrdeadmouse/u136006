<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2014 Bitrix
 */
namespace Bitrix\Main\Web;

class Uri
{
	protected $scheme;
	protected $host;
	protected $port;
	protected $user;
	protected $pass;
	protected $path;
	protected $query;
	protected $pathQuery;
	protected $fragment;

	public function __construct($url)
	{
		if(strpos($url, "/") === 0)
		{
			//we don't support "current scheme" e.g. "//host/path"
			$url = "/".ltrim($url, "/");
		}

		$parsedUrl = parse_url($url);

		if($parsedUrl !== false)
		{
			$this->scheme = (isset($parsedUrl["scheme"])? strtolower($parsedUrl["scheme"]) : "http");
			$this->host = $parsedUrl["host"];
			if(isset($parsedUrl["port"]))
			{
				$this->port = $parsedUrl["port"];
			}
			else
			{
				$this->port = ($this->scheme == "https"? 443 : 80);
			}
			$this->user = $parsedUrl["user"];
			$this->pass = $parsedUrl["pass"];
			$this->path = ((isset($parsedUrl["path"])? $parsedUrl["path"] : "/"));
			$this->query = $parsedUrl["query"];
			$this->pathQuery = $this->path;
			if($this->query <> "")
			{
				$this->pathQuery .= '?'.$this->query;
			}
			$this->fragment = $parsedUrl["fragment"];
		}
	}

	public function getUrl()
	{
		$url = "";
		if($this->host <> '')
		{
			$url .= $this->scheme."://".$this->host;

			if(($this->scheme == "http" && $this->port <> 80) || ($this->scheme == "https" && $this->port <> 443))
			{
				$url .= ":".$this->port;
			}
		}

		$url .= $this->pathQuery;

		return $url;
	}

	public function getFragment()
	{
		return $this->fragment;
	}

	public function getHost()
	{
		return $this->host;
	}

	public function getPass()
	{
		return $this->pass;
	}

	public function getPath()
	{
		return $this->path;
	}

	public function getPathQuery()
	{
		return $this->pathQuery;
	}

	public function getPort()
	{
		return $this->port;
	}

	public function getQuery()
	{
		return $this->query;
	}

	public function getScheme()
	{
		return $this->scheme;
	}

	public function getUser()
	{
		return $this->user;
	}
}
