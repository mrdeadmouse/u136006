<?php

namespace Bitrix\Disk\Ui;


use Bitrix\Disk\AttachedObject;
use Bitrix\Disk\Document\DocumentHandler;
use Bitrix\Disk\Driver;
use Bitrix\Disk\File;
use Bitrix\Disk\Folder;
use Bitrix\Disk\BaseObject;
use Bitrix\Disk\TypeFile;
use Bitrix\Disk\Version;
use CFile;

/**
 * Class Viewer
 *
 * Helps working with core_viewer.js.
 * @package Bitrix\Disk\Ui
 */
final class Viewer
{
	/**
	 * @param $extension
	 * @return bool
	 */
	private static function isViewable($extension)
	{
		static $allowedFormat = array(
			'txt' => 'txt',
			'.txt' => 'txt',
			'pdf' => 'pdf',
			'.pdf' => 'pdf',
			'doc' => 'doc',
			'.doc' => '.doc',
			'docx' => 'docx',
			'.docx' => '.docx',
			'xls' => 'xls',
			'.xls' => '.xls',
			'xlsx' => 'xlsx',
			'.xlsx' => '.xlsx',
			'ppt' => 'ppt',
			'.ppt' => '.ppt',
			'pptx' => 'pptx',
			'.pptx' => '.pptx',
		);

		return isset($allowedFormat[$extension]) || isset($allowedFormat[strtolower($extension)]);
	}

	/**
	 * @param $extension
	 * @return bool
	 */
	private static function isOnlyViewable($extension)
	{
		static $allowedFormat = array(
			'txt' => 'txt',
			'.txt' => 'txt',
			'pdf' => 'pdf',
			'.pdf' => 'pdf',
		);

		return isset($allowedFormat[$extension]) || isset($allowedFormat[strtolower($extension)]);
	}

	/**
	 * Gets data attributes by file array to viewer..
	 * @param array $file File array (specific).
	 * @return string
	 */
	public static function getAttributesByArray(array $file)
	{
		return ""
		. " data-bx-baseElementId=\"disk-attach-{$file['ID']}\""
		. " data-bx-isFromUserLib=\"" . (empty($file['IN_PERSONAL_LIB'])? '' : 1) ."\""
		;
	}

	/**
	 * Gets data attributes by object (folder or file) to viewer.
	 * @param File|Folder|BaseObject $object Target object.
	 * @param array                  $additionalParams Additional parameters 'relativePath', 'externalId', 'canUpdate', 'showStorage'.
	 * @return string
	 */
	public static function getAttributesByObject(BaseObject $object, array $additionalParams = array())
	{
		$urlManager = Driver::getInstance()->getUrlManager();

		$name = $object->getName();
		$dateTime = $object->getUpdateTime();
		if($object instanceof Folder)
		{
			$user = $object->getCreateUser();
			$dataAttributesForViewer =
				'data-bx-viewer="folder" ' .
				'data-bx-title="' . htmlspecialcharsbx($name) . '" ' .
				'data-bx-src="" ' .
				'data-bx-owner="' . htmlspecialcharsbx($user? $user->getFormattedName() : '') . '" ' .
				'data-bx-dateModify="' . htmlspecialcharsbx($dateTime) . '" '
			;
			return $dataAttributesForViewer;
		}
		if(!$object instanceof File)
		{
			return '';
		}

		if(DocumentHandler::isEditable($object->getExtension()))
		{
			$dataAttributesForViewer =
				'data-bx-viewer="iframe" ' .
				'data-bx-title="' . htmlspecialcharsbx($name) . '" ' .
				'data-bx-src="' . $urlManager->getUrlToShowFileByService($object->getId(), 'gvdrive') . '" ' .
				'data-bx-isFromUserLib="" ' .
				'data-bx-askConvert="' . (DocumentHandler::isNeedConvertExtension($object->getExtension())? '1':'') . '" ' .
				'data-bx-download="' . $urlManager->getUrlForDownloadFile($object) . '" ' .
				'data-bx-size="' . htmlspecialcharsbx(CFile::formatSize($object->getSize())) . '" ' .
				'data-bx-dateModify="' . htmlspecialcharsbx($dateTime) . '" '
			;
		}
		elseif(Viewer::isViewable($object->getExtension()))
		{
			$dataAttributesForViewer =
				'data-bx-viewer="iframe" ' .
				'data-bx-title="' . htmlspecialcharsbx($name) . '" ' .
				'data-bx-src="' . $urlManager->getUrlToShowFileByService($object->getId(), 'gvdrive') . '" ' .
				'data-bx-isFromUserLib="" ' .
				'data-bx-askConvert="0" ' .
				'data-bx-download="' . $urlManager->getUrlForDownloadFile($object) . '" ' .
				'data-bx-size="' . htmlspecialcharsbx(CFile::formatSize($object->getSize())) . '" ' .
				'data-bx-dateModify="' . htmlspecialcharsbx($dateTime) . '" '
			;
		}
		/** @noinspection PhpDynamicAsStaticMethodCallInspection */
		elseif(TypeFile::isImage($object))
		{
			$dataAttributesForViewer =
				'data-bx-viewer="image" ' .
				'data-bx-title="' . htmlspecialcharsbx($name) . '" ' .
				'data-bx-src="' . $urlManager->getUrlForDownloadFile($object) . '" ' .

				'data-bx-isFromUserLib="" ' .

				'data-bx-download="' . $urlManager->getUrlForDownloadFile($object) . '" ' .
				'data-bx-dateModify="' . htmlspecialcharsbx($dateTime) . '" '
			;
		}
		else
		{
			$user = $object->getCreateUser();
			$dataAttributesForViewer =
				'data-bx-viewer="unknown" ' .
				'data-bx-src="' . $urlManager->getUrlForDownloadFile($object) . '" ' .

				'data-bx-isFromUserLib="" ' .

				'data-bx-download="' . $urlManager->getUrlForDownloadFile($object) . '" ' .
				'data-bx-title="' . htmlspecialcharsbx($name) . '" ' .
				'data-bx-owner="' . htmlspecialcharsbx($user? $user->getFormattedName() : '') . '" ' .
				'data-bx-size="' . htmlspecialcharsbx(CFile::formatSize($object->getSize())) . '" ' .
				'data-bx-dateModify="' . htmlspecialcharsbx($dateTime) . '" '
			;
		}
		$dataAttributesForViewer .=
			" bx-attach-file-id=\"{$object->getId()}\"" .
			" data-bx-version=\"\"" .
			" data-bx-history=\"\"" .
			" data-bx-historyPage=\"\""
		;

		if(!empty($additionalParams['relativePath']))
		{
			$dataAttributesForViewer .= ' data-bx-relativePath="' . htmlspecialcharsbx($additionalParams['relativePath'] . '/' . $name) . '" ';
		}
		if(!empty($additionalParams['externalId']))
		{
			$dataAttributesForViewer .= ' data-bx-externalId="' . htmlspecialcharsbx($additionalParams['externalId']) . '" ';
		}
		if(!empty($additionalParams['canUpdate']))
		{
			$dataAttributesForViewer .= ' data-bx-edit="' . $urlManager->getUrlForStartEditFile($object->getId(), 'gdrive') . '" ';
		}
		if(!empty($additionalParams['showStorage']))
		{
			$dataAttributesForViewer .= ' data-bx-storage="' . htmlspecialcharsbx($object->getParent()->getName()) . '" ';
		}

		return $dataAttributesForViewer;
	}

	/**
	 * Gets data attributes by version to viewer.
	 * @param Version $version Target version.
	 * @param array   $additionalParams Additional parameters 'canUpdate', 'showStorage'.
	 * @return string
	 */
	public static function getAttributesByVersion(Version $version, array $additionalParams = array())
	{
		$object = $version->getObject();
		$objectId = $object->getId();
		$urlManager = Driver::getInstance()->getUrlManager();

		if(DocumentHandler::isEditable($version->getExtension()))
		{
			$dataAttributesForViewer =
				'data-bx-viewer="iframe" ' .
				'data-bx-title="' . htmlspecialcharsbx($version->getName()) . '" ' .
				'data-bx-src="' . $urlManager->getUrlToShowVersionByService($objectId, $version->getId(), 'gvdrive') . '" ' .
				'data-bx-isFromUserLib="" ' .
				'data-bx-askConvert="' . (DocumentHandler::isNeedConvertExtension($version->getExtension())? '1':'') . '" ' .
				'data-bx-download="' . $urlManager->getUrlForDownloadVersion($version) . '" ' .
				'data-bx-size="' . htmlspecialcharsbx(CFile::formatSize($version->getSize())) . '" '
			;
		}
		elseif(Viewer::isViewable($object->getExtension()))
		{
			$dataAttributesForViewer =
				'data-bx-viewer="iframe" ' .
				'data-bx-title="' . htmlspecialcharsbx($version->getName()) . '" ' .
				'data-bx-src="' . $urlManager->getUrlToShowVersionByService($objectId, $version->getId(), 'gvdrive') . '" ' .
				'data-bx-isFromUserLib="" ' .
				'data-bx-askConvert="0" ' .
				'data-bx-download="' . $urlManager->getUrlForDownloadVersion($version) . '" ' .
				'data-bx-size="' . htmlspecialcharsbx(CFile::formatSize($version->getSize())) . '" ' .
				'data-bx-dateModify="' . htmlspecialcharsbx($version->getCreateTime()) . '" '
			;
		}
		/** @noinspection PhpDynamicAsStaticMethodCallInspection */
		elseif(TypeFile::isImage($object))
		{
			$dataAttributesForViewer =
				'data-bx-viewer="image" ' .
				'data-bx-title="' . htmlspecialcharsbx($object->getName()) . '" ' .
				'data-bx-src="' . $urlManager->getUrlForDownloadVersion($version) . '" ' .

				'data-bx-isFromUserLib="" ' .

				'data-bx-download="' . $urlManager->getUrlForDownloadVersion($version) . '" '
			;
		}
		else
		{
			$dataAttributesForViewer =
				'data-bx-viewer="unknown" ' .
				'data-bx-src="' . $urlManager->getUrlForDownloadVersion($version) . '" ' .

				'data-bx-isFromUserLib="" ' .

				'data-bx-download="' . $urlManager->getUrlForDownloadVersion($version) . '" ' .
				'data-bx-title="' . htmlspecialcharsbx($object->getName()) . '" ' .
				'data-bx-owner="' . htmlspecialcharsbx($version->getCreateUser()? $version->getCreateUser()->getFormattedName() : '') . '" ' .
				'data-bx-dateModify="' . htmlspecialcharsbx($version->getCreateTime()) . '" ' .
				'data-bx-size="' . htmlspecialcharsbx(CFile::formatSize($version->getSize())) . '" '
			;
		}
		$dataAttributesForViewer .=
			" data-bx-version=\"\"" .
			" data-bx-history=\"\"" .
			" data-bx-historyPage=\"\""
		;

		if(!empty($additionalParams['canUpdate']))
		{
			$dataAttributesForViewer .= ' data-bx-edit="' . $urlManager->getUrlForStartEditVersion($objectId, $version->getId(), 'gdrive') . '" ';
		}
		if(!empty($additionalParams['showStorage']))
		{
			$dataAttributesForViewer .= ' data-bx-storage="' . htmlspecialcharsbx($object->getParent()->getName()) . '" ';
		}

		return $dataAttributesForViewer;
	}

	/**
	 * Gets data attributes by attached object to viewer.
	 * @param AttachedObject $attachedObject Target attached object.
	 * @param array          $additionalParams Additional parameters 'relativePath', 'externalId', 'canUpdate', 'canFakeUpdate', 'showStorage', 'version'.
	 * @return string
	 */
	public static function getAttributesByAttachedObject(AttachedObject $attachedObject, array $additionalParams = array())
	{
		$urlManager = Driver::getInstance()->getUrlManager();

		$version = $object = null;
		if($attachedObject->isSpecificVersion())
		{
			$version = $attachedObject->getVersion();
			if(!$version)
			{
				return '';
			}
			$name = $version->getName();
			$extension = $version->getExtension();
			$size = $version->getSize();
			$updateTime  = $version->getCreateTime();
		}
		else
		{
			$object = $attachedObject->getObject();
			if(!$object)
			{
				return '';
			}

			$name = $object->getName();
			$extension = $object->getExtension();
			$size = $object->getSize();
			$updateTime  = $object->getUpdateTime();
		}

		if(DocumentHandler::isEditable($extension))
		{
			$dataAttributesForViewer =
				'data-bx-viewer="iframe" ' .
				'data-bx-title="' . htmlspecialcharsbx($name) . '" ' .
				'data-bx-src="' . $urlManager->getUrlToShowAttachedFileByService($attachedObject->getId(), 'gvdrive') . '" ' .
				'data-bx-isFromUserLib="" ' .
				'data-bx-askConvert="' . (DocumentHandler::isNeedConvertExtension($extension)? '1':'') . '" ' .
				'data-bx-download="' . $urlManager->getUrlUfController('download', array('attachedId' => $attachedObject->getId())) . '" ' .
				'data-bx-size="' . htmlspecialcharsbx(CFile::formatSize($size)) . '" '
			;
		}
		elseif(Viewer::isViewable($extension))
		{
			$dataAttributesForViewer =
				'data-bx-viewer="iframe" ' .
				'data-bx-title="' . htmlspecialcharsbx($name) . '" ' .
				'data-bx-src="' . $urlManager->getUrlToShowAttachedFileByService($attachedObject->getId(), 'gvdrive') . '" ' .
				'data-bx-isFromUserLib="" ' .
				'data-bx-askConvert="0" ' .
				'data-bx-download="' . $urlManager->getUrlUfController('download', array('attachedId' => $attachedObject->getId())) . '" ' .
				'data-bx-size="' . htmlspecialcharsbx(CFile::formatSize($size)) . '" ' .
				'data-bx-dateModify="' . htmlspecialcharsbx($updateTime) . '" '
			;
		}
		elseif(TypeFile::isImage(($name)))
		{
			$dataAttributesForViewer =
				'data-bx-viewer="image" ' .
				'data-bx-title="' . htmlspecialcharsbx($name) . '" ' .
				'data-bx-src="' . $urlManager->getUrlUfController('download', array('attachedId' => $attachedObject->getId())) . '" ' .

				'data-bx-isFromUserLib="" ' .

				'data-bx-download="' . $urlManager->getUrlUfController('download', array('attachedId' => $attachedObject->getId())) . '" '
			;
		}
		else
		{
			$user = $version? $version->getCreateUser() : $object->getCreateUser();
			$formattedName = $user? $user->getFormattedName() : '';

			$dataAttributesForViewer =
				'data-bx-viewer="unknown" ' .
				'data-bx-src="' . $urlManager->getUrlUfController('download', array('attachedId' => $attachedObject->getId())) . '" ' .

				'data-bx-isFromUserLib="" ' .

				'data-bx-download="' . $urlManager->getUrlUfController('download', array('attachedId' => $attachedObject->getId())) . '" ' .
				'data-bx-title="' . htmlspecialcharsbx($name) . '" ' .
				'data-bx-owner="' . htmlspecialcharsbx($formattedName) . '" ' .
				'data-bx-dateModify="' . htmlspecialcharsbx($updateTime) . '" ' .
				'data-bx-size="' . htmlspecialcharsbx(CFile::formatSize($size)) . '" '
			;
		}
		$dataAttributesForViewer .=
			" data-bx-history=\"\"" .
			" data-bx-historyPage=\"\""
		;
		if($object)
		{
			$dataAttributesForViewer .= " bx-attach-file-id=\"{$object->getId()}\"";
		}

		if(!empty($additionalParams['relativePath']))
		{
			$dataAttributesForViewer .= ' data-bx-relativePath="' . htmlspecialcharsbx($additionalParams['relativePath'] . '/' . $name) . '" ';
		}
		if(!empty($additionalParams['externalId']))
		{
			$dataAttributesForViewer .= ' data-bx-externalId="' . htmlspecialcharsbx($additionalParams['externalId']) . '" ';
		}
		if(!empty($additionalParams['canUpdate']))
		{
			$dataAttributesForViewer .= ' data-bx-edit="' . $urlManager->getUrlToStartEditUfFileByService($attachedObject->getId(), 'gdrive') . '" ';
		}
		if(!empty($additionalParams['canFakeUpdate']))
		{
			$dataAttributesForViewer .= ' data-bx-fakeEdit="' . $urlManager->getUrlToStartEditUfFileByService($attachedObject->getId(), 'gdrive') . '" ';
		}

		if(!empty($additionalParams['showStorage']) && $object)
		{
			$dataAttributesForViewer .= ' data-bx-storage="' . htmlspecialcharsbx($object->getParent()->getName()) . '" ';
		}
		if(!empty($additionalParams['version']))
		{
			$dataAttributesForViewer .= ' data-bx-version="' . htmlspecialcharsbx($additionalParams['version']) . '" ';
		}

		return $dataAttributesForViewer;
	}
}