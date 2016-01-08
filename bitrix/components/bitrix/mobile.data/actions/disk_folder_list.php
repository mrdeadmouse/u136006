<?
use Bitrix\Disk\Driver;
use Bitrix\Disk\File;
use Bitrix\Disk\Folder;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\Encoding;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

if(!CModule::IncludeModule("disk"))
{
	return array();
}
if(empty($_REQUEST['entityId']) || empty($_REQUEST['type'])  || empty($_REQUEST['path']) )
{
	$data = array('status' => 'not_found');

	return $data;
}

function mobileDiskPrepareForJson($string)
{
	if(!Application::getInstance()->isUtfMode())
	{
		return Encoding::convertEncodingArray($string, SITE_CHARSET, 'UTF-8');
	}
	return $string;
}

CUtil::JSPostUnescape();
$data = array();
$type = $_REQUEST['type'];
$path = urldecode($_REQUEST['path']);

if($type == 'user')
{
	$entityId = (int)$_REQUEST['entityId'];
	$storage = Driver::getInstance()->getStorageByUserId($entityId);
	if(!$storage)
	{
		$data = array('status' => 'not_found');

		return $data;
	}
}
elseif($type == 'common')
{
	$entityId = $_REQUEST['entityId'];
	$storage = Driver::getInstance()->getStorageByCommonId($entityId);
	if(!$storage)
	{
		$data = array('status' => 'not_found');

		return $data;
	}
}
elseif($type == 'group')
{
	$entityId = (int)$_REQUEST['entityId'];
	$storage = Driver::getInstance()->getStorageByGroupId($entityId);
	if(!$storage)
	{
		$data = array('status' => 'not_found');

		return $data;
	}
}
else
{
	$data = array('status' => 'not_found');

	return $data;
}
$urlManager = Driver::getInstance()->getUrlManager();
$currentFolderId = $urlManager->resolveFolderIdFromPath($storage, $path);
/** @var Folder $folder */
$folder = Folder::loadById($currentFolderId);
if(!$folder)
{
	$data = array('status' => 'not_found');

	return $data;
}

$securityContext = $storage->getCurrentUserSecurityContext();
$items = array();
$countFolders = $countFiles = 0;
foreach($folder->getChildren($securityContext) as $item)
{
	/** @var File|Folder $item */
	$isFolder = $item instanceof Folder;
	if($isFolder)
	{
		$icon = CMobileHelper::mobileDiskGetIconByFilename($item->getName());
		$items[] = array(
			'NAME' => mobileDiskPrepareForJson($item->getName()),
			'TABLE_URL' => SITE_DIR . 'mobile/index.php?' .
					'&mobile_action=' . 'disk_folder_list'.
					'&path=' . (mobileDiskPrepareForJson($path . '/' . $item->getName())).
					'&entityId=' . $entityId.
					'&type=' . $type
			,
			'IMAGE' => CComponentEngine::makePathFromTemplate('/bitrix/components/bitrix/mobile.disk.file.detail/images/folder.png'),
			'TABLE_SETTINGS' => array(
				'type' => 'files',
				'useTagsInSearch' => 'NO',
			),
		);
		$countFolders++;
	}
	else
	{
		$icon = CMobileHelper::mobileDiskGetIconByFilename($item->getName());
		$items[] = array(
			'ID' => $item->getId(),
			'VALUE' => \Bitrix\Disk\Uf\FileUserType::NEW_FILE_PREFIX.$item->getId(),
			'NAME' => mobileDiskPrepareForJson($item->getName()),
			'URL' => array(
				'URL' => SITE_DIR . "mobile/ajax.php?mobile_action=disk_download_file&action=downloadFile&fileId={$item->getId()}&filename=" . mobileDiskPrepareForJson($item->getName()),
				'EXTERNAL' => 'YES',
			),
//			'ACCESSORY_URL' => array(
//				'URL' => CComponentEngine::makePathFromTemplate("#SITE_DIR#mobile/disk/file_detail.php?objectId={$item->getId()}"),
//				'EXTERNAL' => 'NO',
//			),
			'IMAGE' => CComponentEngine::makePathFromTemplate('/bitrix/components/bitrix/mobile.disk.file.detail/images/' . $icon),
			'TAGS' => mobileDiskPrepareForJson(\CFile::FormatSize($item->getSize()) . ' ' . $item->getCreateTime()),
		);
		$countFiles++;
	}
}
unset($item);

$data = array(
	"data" => $items,
	"TABLE_SETTINGS" => array(
		'footer' => mobileDiskPrepareForJson(Loc::getMessage('MD_DISK_TABLE_FOLDERS_FILES', array(
			'#FOLDERS#' => $countFolders,
			'#FILES#' => $countFiles,
		))),
	),
);

return $data;
