<?php

namespace Bitrix\Disk;

final class TypeFile
{
	const IMAGE    = 2;
	const VIDEO    = 3;
	const DOCUMENT = 4;
	const ARCHIVE  = 5;
	const SCRIPT   = 6;
	const UNKNOWN  = 7;

	/**
	 * Allowed values.
	 * @return array
	 */
	public static function getListOfValues()
	{
		return array(
			self::IMAGE,
			self::VIDEO,
			self::DOCUMENT,
			self::ARCHIVE,
			self::SCRIPT,
			self::UNKNOWN,
		);
	}

	public static function getByFile(File $file)
	{
		return self::getByExtension($file->getExtension());
	}

	public static function getByExtension($extension)
	{
		switch(strtolower($extension))
		{
			case 'jpe':
			case 'jpg':
			case 'jpeg':
			case 'png':
			case 'gif':
			case 'bmp':
				return self::IMAGE;
			case 'avi':
			case 'wmv':
			case 'mp4':
				return self::VIDEO;
			case 'doc':
			case 'docx':
			case 'ppt':
			case 'pptx':
			case 'xls':
			case 'xlsx':
			case 'txt':
			case 'odt':
				return self::DOCUMENT;
			case 'zip':
			case 'rar':
			case 'tar':
			case 'gz':
			case 'bz2':
				return self::ARCHIVE;
			case 'php':
			case 'js':
				return self::SCRIPT;
		}

		return self::UNKNOWN;
	}

	public static function getByFilename($filename)
	{
		return self::getByExtension(getFileExtension($filename));
	}

	protected static function getByFlexibleVar($file)
	{
		return $file instanceof File ?
			self::getByFile($file) : self::getByFilename($file);
	}

	public static function isImage($file)
	{
		return self::getByFlexibleVar($file) === self::IMAGE;
	}

	public static function isVideo($file)
	{
		return self::getByFlexibleVar($file) === self::VIDEO;
	}

	public static function isDocument($file)
	{
		return self::getByFlexibleVar($file) === self::DOCUMENT;
	}

	public static function isArchive($file)
	{
		return self::getByFlexibleVar($file) === self::ARCHIVE;
	}

	public static function isScript($file)
	{
		return self::getByFlexibleVar($file) === self::SCRIPT;
	}

	/**
	 * @param $mimeType
	 * @return string|null
	 */
	public static function getExtensionByMimeType($mimeType)
	{
		$mimes = static::getMimeTypeExtensionList();
		$mimes = array_flip($mimes);
		$mimeType = strtolower($mimeType);
		if (isset($mimes[$mimeType]))
		{
			return $mimes[$mimeType];
		}

		return null;
	}

	/**
	 * Get mimeType by filename (analyze extension of file.)
	 * Default type: 'application/octet-stream'
	 * @param $filename
	 * @return string
	 */
	public static function getMimeTypeByFilename($filename)
	{
		$mimes = static::getMimeTypeExtensionList();
		$extension = strtolower(getFileExtension($filename));
		if (isset($mimes[$extension]))
		{
			return $mimes[$extension];
		}

		return 'application/octet-stream';
	}

	/**
	 * @return string
	 */
	public static function getMimeTypeExtensionList()
	{
		static $mimeTypeList = array(
			'html' => 'text/html',
			'gif' => 'image/gif',
			'jpg' => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'bmp' => 'image/bmp',
			'txt' => 'text/plain',
			'xml' => 'application/xml',
			'pdf' => 'application/pdf',
			'doc' => 'application/msword',
			'docm' => 'application/vnd.ms-word.document.macroEnabled.12',
			'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'dotm' => 'application/vnd.ms-word.template.macroEnabled.12',
			'dotx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
			'potm' => 'application/vnd.ms-powerpoint.template.macroEnabled.12',
			'potx' => 'application/vnd.openxmlformats-officedocument.presentationml.template',
			'ppam' => 'application/vnd.ms-powerpoint.addin.macroEnabled.12',
			'ppsm' => 'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',
			'ppsx' => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
			'pptm' => 'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
			'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
			'xlam' => 'application/vnd.ms-excel.addin.macroEnabled.12',
			'xlsb' => 'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
			'xlsm' => 'application/vnd.ms-excel.sheet.macroEnabled.12',
			'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'xltm' => 'application/vnd.ms-excel.template.macroEnabled.12',
			'xltx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
			'rtf' => 'application/msword',
			'rar' => 'application/x-rar-compressed',
			'zip' => 'application/zip'
		);

		return $mimeTypeList;
	}

	/**
	 * @param string $mimeType
	 * @param string $filename
	 * @return string
	 */
	public static function normalizeMimeType($mimeType, $filename)
	{
		switch($mimeType)
		{
			case '':
			case 'application/zip':
			case 'application/octet-stream':
				if(self::isDocument($filename))
				{
					return self::getMimeTypeByFilename($filename);
				}
				break;
		}

		return $mimeType;
	}
} 