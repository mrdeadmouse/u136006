<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */

namespace Bitrix\Main\Mail;

use Bitrix\Main\Config as Config;
use Bitrix\Main\IO\File;

class Mail
{
	protected $settingServerMsSmtp;
	protected $settingMailFillToEmail;
	protected $settingMailConvertMailHeader;
	protected $settingMailAddMessageId;
	protected $settingConvertNewLineUnixToWindows;
	protected $settingMailAdditionalParameters;
	protected $settingMaxFileSize;
	protected $settingAttachImages;
	protected $settingServerName;

	protected $eol;
	protected $boundary;
	protected $charset;
	protected $contentType;
	protected $messageId;
	protected $filesReplacedFromBody;
	protected $trackReadLink;
	protected $trackClickLink;
	protected $trackClickUrlParams;
	protected $bitrixDirectory;

	protected $to;
	protected $subject;
	protected $headers;
	protected $body;
	protected $additionalParameters;

	public function __construct(array $arMailParams)
	{
		if(array_key_exists('TRACK_READ', $arMailParams) && !empty($arMailParams['TRACK_READ']))
		{
			$this->trackReadLink = Tracking::getLinkRead(
				$arMailParams['TRACK_READ']['MODULE_ID'],
				$arMailParams['TRACK_READ']['FIELDS']
			);
		}
		if(array_key_exists('TRACK_CLICK', $arMailParams) && !empty($arMailParams['TRACK_CLICK']))
		{
			$this->trackClickLink = Tracking::getLinkClick(
				$arMailParams['TRACK_CLICK']['MODULE_ID'],
				$arMailParams['TRACK_CLICK']['FIELDS']
			);
			if(!empty($arMailParams['TRACK_CLICK']['URL_PARAMS']))
				$this->trackClickUrlParams = $arMailParams['TRACK_CLICK']['URL_PARAMS'];
		}

		$this->charset = $arMailParams['CHARSET'];
		$this->contentType = $arMailParams['CONTENT_TYPE'];
		$this->messageId = $arMailParams['MESSAGE_ID'];
		$this->eol = $this->getMailEol();
		$this->boundary = "----------".uniqid("");
		$this->attachment = (isset($arMailParams['ATTACHMENT']) ? $arMailParams['ATTACHMENT'] : array());

		$this->initSettings();

		$this->setTo($arMailParams['TO']);
		$this->setSubject($arMailParams['SUBJECT']);
		$this->setBody($arMailParams['BODY']);
		$this->setHeaders($arMailParams['HEADER']);
		$this->setAdditionalParameters();
	}

	/**
	 * @param array $arMailParams
	 * @return static
	 */
	public static function createInstance(array $arMailParams)
	{
		return new static($arMailParams);
	}

	/**
	 * @param $arMailParams
	 * @return bool
	 */
	public static function send($arMailParams)
	{
		$result = false;

		$event = new \Bitrix\Main\Event("main", "OnBeforeMailSend", array($arMailParams));
		$event->send();
		foreach ($event->getResults() as $eventResult)
		{
			if($eventResult->getType() == \Bitrix\Main\EventResult::ERROR)
				return false;

			$arMailParams = array_merge($arMailParams, $eventResult->getParameters());
		}

		if(defined("ONLY_EMAIL") && $arMailParams['TO'] != ONLY_EMAIL)
		{
			$result = true;
		}
		else
		{
			$mail = static::createInstance($arMailParams);

			$mailResult = bxmail(
				$mail->getTo(), $mail->getSubject(), $mail->getBody(), $mail->getHeaders(),
				$mail->getAdditionalParameters()
			);


			if($mailResult)
				$result = true;
		}

		return $result;
	}

	/**
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public function initSettings()
	{
		if(defined("BX_MS_SMTP") && BX_MS_SMTP===true)
			$this->settingServerMsSmtp = true;

		if(Config\Option::get("main", "fill_to_mail", "N")=="Y")
			$this->settingMailFillToEmail = true;

		if(Config\Option::get("main", "convert_mail_header", "Y")=="Y")
			$this->settingMailConvertMailHeader = true;

		if(Config\Option::get("main", "send_mid", "N")=="Y")
			$this->settingMailAddMessageId = true;

		if(Config\Option::get("main", "CONVERT_UNIX_NEWLINE_2_WINDOWS", "N")=="Y")
			$this->settingConvertNewLineUnixToWindows = true;

		if(Config\Option::get("main", "attach_images", "N")=="Y")
			$this->settingAttachImages = true;

		$this->settingServerName = Config\Option::get("main", "server_name", "");

		$this->settingMaxFileSize = intval(Config\Option::get("main", "max_file_size"));

		$this->settingMailAdditionalParameters = Config\Option::get("main", "mail_additional_parameters", "");

		$this->bitrixDirectory = \Bitrix\Main\Application::getInstance()->getPersonalRoot();
	}

	/**
	 * @param string $additionalParameters
	 */
	public function setAdditionalParameters($additionalParameters = '')
	{
		$this->additionalParameters = ($additionalParameters ? $additionalParameters : $this->settingMailAdditionalParameters);
	}


	/**
	 * @param string $bodyPart
	 * @param array $files
	 */
	public function setBody($bodyPart)
	{
		$eol = $this->eol;
		$charset = $this->charset;
		$messageId = $this->messageId;

		$body = "";
		$contentType = "text/plain";
		if($this->contentType == "html")
		{
			$contentType = "text/html";
			$bodyPart = $this->replaceImages($bodyPart);
			$bodyPart = $this->replaceHrefs($bodyPart);
			$bodyPart = $this->trackRead($bodyPart);
		}

		if($this->hasAttachment())
		{
			$body = "--" . $this->boundary . $eol;
			$body .= "Content-Type: " . $contentType . "; charset=" . $charset . $eol;
			$body .= "Content-Transfer-Encoding: 8bit" . $eol . $eol;
		}

		$body .= $bodyPart;
		if($this->settingMailAddMessageId && !empty($messageId))
		{
			$body .= ($this->contentType == "html" ? "<br><br>" : "\n\n" );
			$body .= "MID #".$messageId."\r\n";
		}

		$body = str_replace("\r\n", "\n", $body);
		if($this->settingConvertNewLineUnixToWindows)
			$body = str_replace("\n", "\r\n", $body);

		$this->body = $body.$eol;


		$this->setAttachment();

		if($this->hasAttachment())
		{
			$this->body .= "--" . $this->boundary.'--'.$eol;
		}
	}

	/**
	 * @return bool
	 */
	public function hasAttachment()
	{
		return !empty($this->attachment) || !empty($this->filesReplacedFromBody);
	}

	/**
	 *
	 */
	public function setAttachment()
	{
		$files = $this->attachment;
		if(is_array($this->filesReplacedFromBody))
			$files = array_merge($files, array_values($this->filesReplacedFromBody));

		if(count($files)>0)
		{
			$eol = $this->eol;
			$charset = $this->charset;

			$bodyPart = '';
			foreach($files as $attachment)
			{
				try
				{
					$fileContent = File::getFileContents($attachment["PATH"]);
				}
				catch (\Exception $exception)
				{
					$fileContent = '';
				}

				$attachment_name = $this->encodeSubject($attachment["NAME"], $charset);
				$bodyPart .= $eol."--".$this->boundary.$eol;
				$bodyPart .= "Content-Type: ".$attachment["CONTENT_TYPE"]."; name=\"".$attachment_name."\"".$eol;
				$bodyPart .= "Content-Transfer-Encoding: base64".$eol;
				$bodyPart .= "Content-ID: <".$attachment["ID"].">".$eol.$eol;
				$bodyPart .= chunk_split(
					base64_encode(
						$fileContent
					), 72, $eol
				);
			}

			$this->body .= $bodyPart;
		}
	}

	/**
	 * @param array $headers
	 */
	public function setHeaders(array $headers)
	{
		if(strlen($headers["Reply-To"])==0)
			$headers["Reply-To"] = preg_replace("/(.*)\\<(.*)\\>/i", '$2', $headers["From"]);

		if(strlen($headers["Reply-To"])==0)
			$headers["X-Priority"] = '3 (Normal)';

		foreach($headers as $k=>$v)
		{
			$headers[$k] = trim($v, "\r\n");
			if(strlen($headers[$k])==0)
				unset($headers[$k]);
		}


		if($this->settingMailConvertMailHeader)
		{
			foreach($headers as $k=>$v)
				if($k == 'From' || $k == 'CC')
					$headers[$k] = $this->encodeHeaderFrom($v, $this->charset);
				else
					$headers[$k] = $this->encodeMimeString($v, $this->charset);
		}

		if($this->settingServerMsSmtp)
		{
			if($headers["From"]!='')
				$headers["From"] = preg_replace("/(.*)\\<(.*)\\>/i", '$2', $headers["From"]);
			if($headers["To"]!='')
				$headers["To"] = preg_replace("/(.*)\\<(.*)\\>/i", '$2', $headers["To"]);
		}

		if($this->settingMailFillToEmail && $headers["To"] != $this->to)
			$headers["To"] = $this->to;

		if($this->messageId !='')
			$headers['X-MID'] = $this->messageId;

		if($this->hasAttachment())
		{
			$headers['Content-Type'] = 'multipart/mixed; boundary="' . $this->boundary . '"';
		}
		else
		{
			$contentType = "text/plain";
			if($this->contentType == "html")
			{
				$contentType = "text/html";
			}
			$headers['Content-Type'] = $contentType . "; charset=" . $this->charset;
		}


		$header = "";
		foreach($headers as $k=>$v)
		{
			$header .= $k.': '.$v.$this->eol;
		}
		$header .= "Content-Transfer-Encoding: 8bit";

		$this->headers = $header;
	}

	/**
	 * @param string $subject
	 */
	public function setSubject($subject)
	{
		if($this->settingMailConvertMailHeader)
			$this->subject = $this->encodeSubject($subject, $this->charset);
		else
			$this->subject = $subject;
	}

	/**
	 * @param string $to
	 */
	public function setTo($to)
	{
		$resultTo = $to;

		if($this->settingMailConvertMailHeader)
			$resultTo = $this->encodeHeaderFrom($resultTo, $this->charset);

		if($this->settingServerMsSmtp)
			$resultTo = preg_replace("/(.*)\\<(.*)\\>/i", '$2', $resultTo);

		$this->to = $resultTo;
	}





	/**
	 * @return string
	 */
	public function getBody()
	{
		return $this->body;
	}

	/**
	 * @return string
	 */
	public function getHeaders()
	{
		return $this->headers;
	}

	/**
	 * @return string
	 */
	public function getMessageId()
	{
		return $this->messageId;
	}

	/**
	 * @return string
	 */
	public function getSubject()
	{
		return $this->subject;
	}

	/**
	 * @return string
	 */
	public function getTo()
	{
		return $this->to;
	}

	/**
	 * @return mixed
	 */
	public function getAdditionalParameters()
	{
		return $this->additionalParameters;
	}

	/**
	 * @return string
	 */
	public function dump()
	{
		$result = '';
		$delimeter = str_repeat('-',5);

		$result .= $delimeter."TO".$delimeter."\n".$this->getTo()."\n\n";
		$result .= $delimeter."SUBJECT".$delimeter."\n".$this->getSubject()."\n\n";
		$result .= $delimeter."HEADERS".$delimeter."\n".$this->getHeaders()."\n\n";
		$result .= $delimeter."BODY".$delimeter."\n".$this->getBody()."\n\n";
		$result .= $delimeter."ADDITIONAL PARAMETERS".$delimeter."\n".$this->getAdditionalParameters()."\n\n";

		return $result;
	}


	/**
	 * @param $str
	 * @return bool
	 */
	public static function is8Bit($str)
	{
		return preg_match("/[\\x80-\\xFF]/", $str) > 0;
	}

	/**
	 * @param $text
	 * @param $charset
	 * @return string
	 */
	public static function encodeMimeString($text, $charset)
	{
		if(!static::is8Bit($text))
			return $text;

		//$maxl = IntVal((76 - strlen($charset) + 7)*0.4);
		$res = "";
		$maxl = 40;
		$eol = static::getMailEol();
		$len = strlen($text);
		for($i=0; $i<$len; $i=$i+$maxl)
		{
			if($i>0)
				$res .= $eol."\t";
			$res .= "=?".$charset."?B?".base64_encode(substr($text, $i, $maxl))."?=";
		}
		return $res;
	}

	/**
	 * @param $text
	 * @param $charset
	 * @return string
	 */
	public static function encodeSubject($text, $charset)
	{
		return "=?".$charset."?B?".base64_encode($text)."?=";
	}

	/**
	 * @param $text
	 * @param $charset
	 * @return string
	 */
	public static function encodeHeaderFrom($text, $charset)
	{
		$i = strlen($text);
		while($i > 0)
		{
			if(ord(substr($text, $i-1, 1))>>7)
				break;
			$i--;
		}
		if($i==0)
			return $text;
		else
			return "=?".$charset."?B?".base64_encode(substr($text, 0, $i))."?=".substr($text, $i);
	}

	/**
	 * @return string
	 */
	public static function getMailEol()
	{
		static $eol = false;
		if($eol !== false)
			return $eol;

		if(strtoupper(substr(PHP_OS,0,3)) == 'WIN')
			$eol="\r\n";
		elseif(strtoupper(substr(PHP_OS,0,3)) <> 'MAC')
			$eol="\n"; 	 //unix
		else
			$eol="\r";

		return $eol;
	}


	/**
	 * @param $matches
	 * @return string
	 * @throws \Bitrix\Main\IO\FileNotFoundException
	 */
	protected function getReplacedImageCid($matches)
	{
		$src = $matches[3];

		if($src == "")
			return $matches[0];

		if(array_key_exists($src, $this->filesReplacedFromBody))
		{
			$uid = $this->filesReplacedFromBody[$src]["ID"];
			return $matches[1].$matches[2]."cid:".$uid.$matches[4].$matches[5];
		}

		$io = \CBXVirtualIo::GetInstance();
		$filePath = $io->GetPhysicalName(\Bitrix\Main\Application::getDocumentRoot().$src);
		if(!File::isFileExists($filePath))
			return $matches[0];

		foreach($this->attachment as $attach)
		{
			if($filePath == $attach['PATH'])
			{
				return $matches[1].$matches[2]."cid:".$attach['ID'].$matches[4].$matches[5];
			}
		}

		if ($this->settingMaxFileSize > 0)
		{
			$fileIoObject = new File($filePath);
			if ($fileIoObject->getSize() > $this->settingMaxFileSize)
				return $matches[0];
		}


		$aImage = \CFile::GetImageSize($filePath, true);
		if (!is_array($aImage))
			return $matches[0];

		if (function_exists("image_type_to_mime_type"))
			$contentType = image_type_to_mime_type($aImage[2]);
		else
			$contentType = $this->imageTypeToMimeType($aImage[2]);

		$uid = uniqid(md5($src));

		$this->filesReplacedFromBody[$src] = array(
			"SRC" => $src,
			"PATH" => $filePath,
			"CONTENT_TYPE" => $contentType,
			"NAME" => bx_basename($src),
			"ID" => $uid,
		);

		return $matches[1].$matches[2]."cid:".$uid.$matches[4].$matches[5];
	}

	/**
	 * @param $matches
	 * @return string
	 */
	protected function getReplacedImageSrc($matches)
	{
		$src = $matches[3];
		if($src == "")
			return $matches[0];

		$srcTrimmed = trim($src);
		if(substr($srcTrimmed,0, 1) == "/")
		{
			$srcModified = false;
			if(count($this->attachment)>0)
			{
				$io = \CBXVirtualIo::GetInstance();
				$filePath = $io->GetPhysicalName(\Bitrix\Main\Application::getDocumentRoot().$srcTrimmed);
				foreach($this->attachment as $attach)
				{
					if($filePath == $attach['PATH'])
					{
						$src = "cid:".$attach['ID'];
						$srcModified = true;
						break;
					}
				}
			}

			if(!$srcModified)
				$src = "http://".$this->settingServerName . $srcTrimmed;
		}

		return $matches[1].$matches[2].$src.$matches[4].$matches[5];
	}

	/**
	 * @param $text
	 * @return mixed
	 */
	public function replaceImages($text)
	{
		$replaceImageFunction = 'getReplacedImageSrc';
		if($this->settingAttachImages)
			$replaceImageFunction = 'getReplacedImageCid';

		$this->filesReplacedFromBody = array();
		$textReplaced = preg_replace_callback(
			"/(<img\\s[^>]*?(?<=\\s)src\\s*=\\s*)([\"']?)(.*?)(\\2)(\\s.+?>|\\s*>)/is",
			array($this, $replaceImageFunction),
			$text
		);
		if($textReplaced !== null) $text = $textReplaced;

		$textReplaced = preg_replace_callback(
			"/(background-image\\s*:\\s*url\\s*\\()([\"']?)(.*?)(\\2)(\\s*\\);)/is",
			array($this, $replaceImageFunction),
			$text
		);
		if($textReplaced !== null) $text = $textReplaced;

		$textReplaced = preg_replace_callback(
			"/(<td\\s[^>]*?(?<=\\s)background\\s*=\\s*)([\"']?)(.*?)(\\2)(\\s.+?>|\\s*>)/is",
			array($this, $replaceImageFunction),
			$text
		);
		if($textReplaced !== null) $text = $textReplaced;

		$textReplaced = preg_replace_callback(
			"/(<table\\s[^>]*?(?<=\\s)background\\s*=\\s*)([\"']?)(.*?)(\\2)(\\s.+?>|\\s*>)/is",
			array($this, $replaceImageFunction),
			$text
		);
		if($textReplaced !== null) $text = $textReplaced;

		return $text;
	}

	/**
	 * @param $text
	 * @return mixed
	 */
	public function replaceHrefs($text)
	{
		if($this->settingServerName != '')
		{
			$pcre_pattern = "/(<a\\s[^>]*?(?<=\\s)href\\s*=\\s*)([\"'])(\\/.*?|http:\\/\\/.*?|https:\\/\\/.*?)(\\2)(\\s.+?>|\\s*>)/is";
			$text = preg_replace_callback(
				$pcre_pattern,
				array($this, 'trackClick'),
				$text
			);
		}

		return $text;
	}

	/**
	 * @param $html
	 * @return string
	 */
	private function trackRead($html)
	{
		if($this->trackReadLink)
		{
			$html .= '<img src="'."http://".$this->settingServerName.$this->trackReadLink.'" border="0" height="1" width="1" alt="Read" />';
		}

		return $html;
	}

	/**
	 * @param $matches
	 * @return string
	 */
	public function trackClick($matches)
	{
		$href = $matches[3];
		if ($href == "")
		{
			return $matches[0];
		}

		if(substr($href, 0, 2) == '//')
		{
			$href = 'http:' . $href;
		}

		if(substr($href, 0, 1) == '/')
		{
			$href = "http://" . $this->settingServerName . $href;
		}

		if($this->trackClickLink)
		{
			if($this->trackClickUrlParams)
			{
				$hrefAddParam = '';
				foreach($this->trackClickUrlParams as $k => $v)
					$hrefAddParam .= '&'.htmlspecialcharsbx($k).'='.htmlspecialcharsbx($v);

				$parsedHref = explode("#", $href);
				$parsedHref[0] .= (strpos($parsedHref[0], '?') === false ? '?' : '&') . substr($hrefAddParam, 1);
				$href = implode("#", $parsedHref);
			}
			$href = "http://" . $this->settingServerName . $this->trackClickLink . '&url=' . urlencode($href);
		}

		return $matches[1].$matches[2].$href.$matches[4].$matches[5];
	}

	/**
	 * @param $type
	 * @return string
	 */
	protected function imageTypeToMimeType($type)
	{
		$aTypes = array(
			1 => "image/gif",
			2 => "image/jpeg",
			3 => "image/png",
			4 => "application/x-shockwave-flash",
			5 => "image/psd",
			6 => "image/bmp",
			7 => "image/tiff",
			8 => "image/tiff",
			9 => "application/octet-stream",
			10 => "image/jp2",
			11 => "application/octet-stream",
			12 => "application/octet-stream",
			13 => "application/x-shockwave-flash",
			14 => "image/iff",
			15 => "image/vnd.wap.wbmp",
			16 => "image/xbm",
		);
		if(!empty($aTypes[$type]))
			return $aTypes[$type];
		else
			return "application/octet-stream";
	}
}
