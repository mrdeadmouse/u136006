<?php
use Bitrix\Disk\Driver;
use Bitrix\Disk\Internals\DiskComponent;
use Bitrix\Disk\Internals\ExternalLinkTable;
use Bitrix\Disk\TypeFile;
use Bitrix\Disk\Ui\Icon;
use Bitrix\Disk\Ui;
use Bitrix\Disk\Uf;
use Bitrix\Disk\Version;
use Bitrix\Main\Localization\Loc;
use Bitrix\Disk\ProxyType;
use Bitrix\Main\Loader;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

Loc::loadMessages(__FILE__);

class CDiskFileViewComponent extends DiskComponent
{
	const ERROR_COULD_NOT_FIND_OBJECT  = 'DISK_FV_22001';
	const ERROR_COULD_NOT_SAVE_FILE    = 'DISK_FV_22002';
	const ERROR_COULD_NOT_FIND_VERSION = 'DISK_FV_22003';

	/** @var \Bitrix\Disk\File */
	protected $file;
	/** @var  array */
	protected $breadcrumbs;

	protected function listActions()
	{
		return array(
			'showVersion' => array(
				'method' => array('GET', 'POST'),
				'name' => 'showVersion',
				'check_csrf_token' => false,
			),
			'showBp' => array(
				'method' => array('GET', 'POST'),
				'name' => 'showBp',
				'check_csrf_token' => false,
			),
		);
	}

	protected function processBeforeAction($actionName)
	{
		parent::processBeforeAction($actionName);
		$this->findFile();

		$securityContext = $this->storage->getCurrentUserSecurityContext();
		if(!$this->file->canRead($securityContext))
		{
			$this->showAccessDenied();
			return false;
		}

		return true;
	}

	protected function prepareParams()
	{
		parent::prepareParams();

		if(!isset($this->arParams['FILE_ID']))
		{
			throw new \Bitrix\Main\ArgumentException('FILE_ID required');
		}
		$this->arParams['FILE_ID'] = (int)$this->arParams['FILE_ID'];
		if($this->arParams['FILE_ID'] <= 0)
		{
			throw new \Bitrix\Main\ArgumentException('FILE_ID < 0');
		}

		return $this;
	}

	protected function processActionDefault()
	{
		$gridId = 'file_version_list';

		$securityContext = $this->storage->getCurrentUserSecurityContext();
		$urlManager = Driver::getInstance()->getUrlManager();

		$this->getApplication()->setTitle($this->storage->getProxyType()->getTitleForCurrentUser());

		$breadcrumbs = $this->getBreadcrumbs();
		$externalLinkData = array();
		$externalLink = $this->getExternalLink();
		if($externalLink)
		{
			$externalLinkData = array(
				'LINK' => Driver::getInstance()->getUrlManager()->getShortUrlExternalLink(array(
					'hash' => $externalLink->getHash(),
					'action' => 'default',
				), true)
			);
		}

		$createdByLink = \CComponentEngine::makePathFromTemplate($this->arParams['PATH_TO_USER'], array("user_id" => $this->file->getCreatedBy()));
		$canUpdate = $this->file->canUpdate($securityContext);

		$viewFile = CComponentEngine::makePathFromTemplate($this->arParams['PATH_TO_FILE_VIEW'], array(
			'FILE_ID' => $this->file->getId(),
			'FILE_PATH' => $this->arParams['RELATIVE_PATH'],
		));

		$this->arResult = array(
			'VERSION_GRID' => array(),
			'STORAGE' => $this->storage,
			'USE_IN_ENTITIES' => false,
			'ENTITIES' => array(),
			'SHOW_USER_FIELDS' => false,
			'USER_FIELDS' => array(),
			'TOOLBAR' => array(
				'BUTTONS' => array(
					array(
						'TEXT' => Loc::getMessage('DISK_FILE_VIEW_GO_BACK_TEXT'),
						'TITLE' => Loc::getMessage('DISK_FILE_VIEW_GO_BACK_TITLE'),
						'LINK' => end($breadcrumbs),
						'ICON' => 'back',
					),
					array(
						'TEXT' => Loc::getMessage('DISK_FILE_VIEW_COPY_LINK_TEXT'),
						'TITLE' => Loc::getMessage('DISK_FILE_VIEW_COPY_LINK_TITLE'),
						'LINK' => "javascript:BX.Disk['FileViewClass_{$this->getComponentId()}'].getInternalLink();",
						'ICON' => 'copy-link',
					),
				),
			),
			'EXTERNAL_LINK' => $externalLinkData,
			'FILE' => array(
				'ID' => $this->file->getId(),
				'IS_IMAGE' => TypeFile::isImage($this->file->getName()),
				'CREATE_USER' => array(
					'LINK' => $createdByLink,
					'NAME' => $this->file->getCreateUser()->getFormattedName(),
					'AVA' => $this->file->getCreateUser()->getAvatarSrc(21 ,21),
				),
				'VIEWER_ATTRIBUTES' => Ui\Viewer::getAttributesByObject($this->file, array('canUpdate' => $canUpdate)),
				'UPDATE_TIME' => $this->file->getUpdateTime(),
				'ICON_CLASS' => Icon::getIconClassByObject($this->file),
				'NAME' => $this->file->getName(),
				'SIZE' => $this->file->getSize(),
				'IS_LINK' => $this->file->isLink(),
				'FOLDER_LIST_WEBDAV' => rtrim(end($breadcrumbs), '/') . '/' . $this->file->getName(),
				'DOWNLOAD_URL' => $urlManager->getUrlForDownloadFile($this->file),

				'SHOW_PREVIEW_URL' => \Bitrix\Disk\Driver::getInstance()->getUrlManager()->getUrlForShowFile($this->file, array('exact' => 'Y', 'width' => 255, 'height' => 255,)),
				'SHOW_FILE_URL' => \Bitrix\Disk\Driver::getInstance()->getUrlManager()->getUrlForShowFile($this->file),
			),
			'CAN_UPDATE' => $canUpdate,
			'CAN_DELETE' => $this->file->canDelete($securityContext),
			'PATH_TO_FILE_VIEW' => $viewFile,
			//'BREADCRUMBS' => $breadcrumbs,
		);

		$attachedObjects = $this->file->getAttachedObjects();
		if($attachedObjects)
		{
			$userId = $this->getUser()->getId();
			$this->arResult['USE_IN_ENTITIES'] = true;
			Uf\Connector::setPathToUser($this->arParams['PATH_TO_USER']);
			Uf\Connector::setPathToGroup($this->arParams['PATH_TO_GROUP']);
			foreach($attachedObjects as $attachedObject)
			{
				$connector = $attachedObject->getConnector();
				if(!$connector->canRead($userId))
				{
					continue;
				}
				$dataToShow = $connector->getDataToShow();
				if($dataToShow)
				{
					$this->arResult['ENTITIES'][] = $dataToShow;
				}
			}
			unset($attachedObject);
		}

		$userFieldsObject = \Bitrix\Disk\Driver::getInstance()->getUserFieldManager()->getFieldsForObject($this->file);
		foreach($userFieldsObject as $field)
		{
			if ($field['VALUE'] !== false)
			{
				$this->arResult['SHOW_USER_FIELDS'] = true;
				$this->arResult['USER_FIELDS'] = $userFieldsObject;
				break;
			}
		}
		unset($field);

		$this->arParams['STATUS_BIZPROC'] = $this->storage->isEnabledBizProc() && Loader::includeModule("bizproc");

		if($this->arParams['STATUS_BIZPROC'])
		{
			$documentData = array(
				'DISK' => array(
					'DOCUMENT_TYPE' => \Bitrix\Disk\BizProcDocument::generateDocumentComplexType($this->storage->getId()),
					'DOCUMENT_ID' => \Bitrix\Disk\BizProcDocument::getDocumentComplexId($this->file->getId()),
				),
				'WEBDAV' => array(
					'DOCUMENT_TYPE' => \Bitrix\Disk\BizProcDocumentCompatible::generateDocumentComplexType($this->storage->getId()),
					'DOCUMENT_ID' => \Bitrix\Disk\BizProcDocumentCompatible::getDocumentComplexId($this->file->getId()),
				),
			);
			$webdavFileId = $this->file->getXmlId();
			if(!empty($webdavFileId))
			{
				if (Loader::includeModule("iblock"))
				{
					if($this->storage->getProxyType() instanceof ProxyType\Group)
					{
						$iblock = CIBlockElement::getList(array(), array("ID" => $webdavFileId, 'SHOW_NEW' => 'Y'), false, false, array('ID', 'IBLOCK_ID'))->fetch();
						$entity = 'CIBlockDocumentWebdavSocnet';
					}
					else
					{
						$iblock = CIBlockElement::getList(array(), array("ID" => $webdavFileId, 'SHOW_NEW' => 'Y'), false, false, array('ID', 'IBLOCK_ID'))->fetch();
						$entity = 'CIBlockDocumentWebdav';
					}
					if(!empty($iblock))
					{
						$documentData['OLD_FILE'] = array(
							'DOCUMENT_TYPE' => array('webdav', $entity, "iblock_".$iblock['IBLOCK_ID']),
							'DOCUMENT_ID' => array('webdav', $entity, $iblock['ID']),
						);
					}
				}
			}
			$this->getAutoloadTemplateBizProc($documentData);
			if ($this->request->isPost() && intval($this->request->getPost('bizproc_index')) > 0)
			{
				$this->showBizProc($documentData);
			}
		}

		$this->includeComponentTemplate();
	}

	protected function processActionShowVersion()
	{
		$gridId = 'file_version_list';
		$this->getApplication()->setTitle($this->storage->getProxyType()->getTitleForCurrentUser());

		$this->arResult = array(
			'VERSION_GRID' => $this->getVersionGridData($gridId),
			'FILE' => array(
				'ID' => $this->file->getId(),
			)
		);

		$this->includeComponentTemplate('version');

		$this->end();
	}

	protected function processActionShowBp()
	{
		$this->getApplication()->setTitle($this->storage->getProxyType()->getTitleForCurrentUser());

		$viewFile = CComponentEngine::makePathFromTemplate($this->arParams['PATH_TO_FILE_VIEW'], array(
			'FILE_ID' => $this->file->getId(),
			'FILE_PATH' => $this->arParams['RELATIVE_PATH'],
		));

		$urlStartBizproc = \CComponentEngine::makePathFromTemplate($this->arParams['PATH_TO_DISK_START_BIZPROC'],array("ELEMENT_ID" => $this->file->getId()));
		$urlStartBizproc .= "?back_url=".urlencode($this->getApplication()->getCurPage());
		$urlStartBizproc .= (strpos($urlStartBizproc, "?") === false ? "?" : "&").'workflow_template_id=0&'.bitrix_sessid_get();

		$this->arResult = array(
			'STORAGE' => $this->storage,
			'FILE' => array(
				'ID' => $this->file->getId(),
			),
			'PATH_TO_FILE_VIEW' => $viewFile,
			'PATH_TO_START_BIZPROC' => $urlStartBizproc,
			'STORAGE_ID' => 'STORAGE_'.$this->storage->getId(),
		);

		$this->arParams['STATUS_BIZPROC'] = $this->storage->isEnabledBizProc() && Loader::includeModule("bizproc");

		if($this->arParams['STATUS_BIZPROC'])
		{
			$documentData = array(
				'DISK' => array(
					'DOCUMENT_TYPE' => \Bitrix\Disk\BizProcDocument::generateDocumentComplexType($this->storage->getId()),
					'DOCUMENT_ID' => \Bitrix\Disk\BizProcDocument::getDocumentComplexId($this->file->getId()),
				),
				'WEBDAV' => array(
					'DOCUMENT_TYPE' => \Bitrix\Disk\BizProcDocumentCompatible::generateDocumentComplexType($this->storage->getId()),
					'DOCUMENT_ID' => \Bitrix\Disk\BizProcDocumentCompatible::getDocumentComplexId($this->file->getId()),
				),
			);
			$webdavFileId = $this->file->getXmlId();
			if(!empty($webdavFileId))
			{
				if (Loader::includeModule("iblock"))
				{
					if($this->storage->getProxyType() instanceof ProxyType\Group)
					{
						$iblock = CIBlockElement::getList(array(), array("ID" => $webdavFileId, 'SHOW_NEW' => 'Y'), false, false, array('ID', 'IBLOCK_ID'))->fetch();
						$entity = 'CIBlockDocumentWebdavSocnet';
					}
					else
					{
						$iblock = CIBlockElement::getList(array(), array("ID" => $webdavFileId, 'SHOW_NEW' => 'Y'), false, false, array('ID', 'IBLOCK_ID'))->fetch();
						$entity = 'CIBlockDocumentWebdav';
					}
					if(!empty($iblock))
					{
						$documentData['OLD_FILE'] = array(
							'DOCUMENT_TYPE' => array('webdav', $entity, "iblock_".$iblock['IBLOCK_ID']),
							'DOCUMENT_ID' => array('webdav', $entity, $iblock['ID']),
						);
					}
				}
			}
			$this->showBizProc($documentData);
		}


		$this->includeComponentTemplate('bp');

		$this->end();
	}

	protected function findFile()
	{
		$this->file = \Bitrix\Disk\File::loadById($this->arParams['FILE_ID'], array('REAL_OBJECT', 'CREATE_USER'));

		if(!$this->file)
		{
			throw new \Bitrix\Main\SystemException("Invalid file.");
		}
		return $this;
	}

	private function processGridActions($gridId)
	{
		$postAction = 'action_button_'.$gridId;
		if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST[$postAction]) && check_bitrix_sessid())
		{
			if($_POST[$postAction] == 'delete')
			{
				if(empty($_POST['ID']))
				{
					return;
				}
				if(!$this->file->canDelete($this->file->getStorage()->getCurrentUserSecurityContext()) || !$this->file->canRestore($this->file->getStorage()->getCurrentUserSecurityContext()))
				{
					return;
				}
				foreach($_POST['ID'] as $targetId)
				{
					/** @var Version $version */
					$version = Version::loadById($targetId);
					if(!$version)
					{
						continue;
					}
					$version->delete();
				}
			}
		}
	}

	private function getVersionGridData($gridId)
	{
		$grid = array(
			'ID' => $gridId,
		);

		$gridOptions = new CGridOptions($grid['ID']);
		$gridSort = $gridOptions->getSorting(array(
			'sort' => array('ID' => 'desc'),
			'vars' => array('by' => 'by', 'order' => 'order')
		));

		$grid['SORT'] = $gridSort['sort'];
		$grid['SORT_VARS'] = $gridSort['vars'];

		$this->arResult['ITEMS'] = $this->file->getVersions(array(
			'with' => array('CREATE_USER'),
			'order' => $gridSort['sort'],
		));

		$urlManager = \Bitrix\Disk\Driver::getInstance()->getUrlManager();
		$rows = array();
		foreach ($this->arResult['ITEMS'] as $version)
		{
			/** @var $version Version */
			$objectArray = $version->toArray();
			$actions = array(
				array(
					"ICONCLASS" => "download",
					"TEXT" => Loc::getMessage('DISK_FILE_VIEW_HISTORY_ACT_DOWNLOAD'),
					"DEFAULT" => true,
					"ONCLICK" => "jsUtils.Redirect(arguments, '" . $urlManager->getUrlForDownloadVersion($version) . "')",
				),
			);
			$securityContext = $this->storage->getCurrentUserSecurityContext();
			if($this->file->canRestore($securityContext))
			{
				$actions[] = array(
					"ICONCLASS" => "restore",
					"TEXT" => Loc::getMessage('DISK_FILE_VIEW_HISTORY_ACT_RESTORE'),
					"DEFAULT" => true,
					"ONCLICK" =>
						"BX.Disk['FileViewClass_{$this->getComponentId()}'].openConfirmRestore({
							object: {
								id: {$this->file->getId()},
								name: '{$this->file->getName()}'
							},
							version: {
								id: {$version->getId()}
							}
						})",
				);
			}
			if(
				$this->file->canRestore($securityContext) &&
				$this->file->canDelete($securityContext)
			)
			{
				$actions[] = array(
					"ICONCLASS" => "delete",
					"TEXT" => Loc::getMessage('DISK_FILE_VIEW_HISTORY_ACT_DELETE'),
					"DEFAULT" => true,
					"ONCLICK" =>
						"BX.Disk['FileViewClass_{$this->getComponentId()}'].openConfirmDeleteVersion({
							object: {
								id: {$this->file->getId()},
								name: '{$this->file->getName()}'
							},
							version: {
								id: {$version->getId()}
							}
						})",
				);
			}

			$createdByLink = \CComponentEngine::makePathFromTemplate($this->arParams['PATH_TO_USER'], array("user_id" => $version->getCreatedBy()));

			$dataAttributesForViewer = Ui\Viewer::getAttributesByVersion($version);
			$rows[] = array(
				'data' => $objectArray,
				'columns' => array(
					'FORMATTED_SIZE' => CFile::formatSize($version->getSize()),
					'NAME' => "<a href='' {$dataAttributesForViewer}>" . $version->getName() . "</a>",
					'CREATE_USER' => "
						<div class=\"bx-disk-user-link\"><span class=\"bx-disk-filepage-fileinfo-ownner-avatar\" style=\"background-image: url({$version->getCreateUser()->getAvatarSrc()});\"></span><a target='_blank' href=\"{$createdByLink}\" id=\"\">" . htmlspecialcharsbx($version->getCreateUser()->getFormattedName()) . "</a></div>
					",
					'CREATE_TIME_VERSION' => $version->getCreateTime(),
					'CREATE_TIME_FILE' => $version->getObjectCreateTime(),
					'UPDATE_TIME_FILE' => $version->getObjectUpdateTime(),
				),
				'actions' => $actions,
			);
		}
		unset($version);

		$grid['ROWS'] = $rows;
		$grid['ROWS_COUNT'] = count($rows);
		$grid['HEADERS'] = array(
			array(
				'id' => 'CREATE_USER',
				'name' => Loc::getMessage('DISK_FILE_VIEW_VERSION_COLUMN_CREATE_USER_2'),
				'default' => true,
			),
			array(
				'id' => 'NAME',
				'name' => Loc::getMessage('DISK_FILE_VIEW_VERSION_COLUMN_NAME'),
				'default' => true,
			),
			array(
				'id' => 'CREATE_TIME_VERSION',
				'name' => Loc::getMessage('DISK_FILE_VIEW_VERSION_COLUMN_CREATE_TIME_2'),
				'default' => true,
			),
			array(
				'id' => 'FORMATTED_SIZE',
				'name' => Loc::getMessage('DISK_FILE_VIEW_VERSION_COLUMN_FORMATTED_SIZE'),
				'default' => true,
			),
			array(
				'id' => 'ID',
				'name' => 'ID',
				'default' => false,
				'show_checkbox' => true,
			),
		);

		return $grid;
	}

	/**
	 * @return \Bitrix\Disk\ExternalLink|null
	 */
	protected function getExternalLink()
	{
		$extLinks = $this->file->getExternalLinks(array(
			'filter' => array(
				'OBJECT_ID' => $this->file->getId(),
				'CREATED_BY' => $this->getUser()->getId(),
				'TYPE' => ExternalLinkTable::TYPE_MANUAL,
				'IS_EXPIRED' => false,
			),
			'limit' => 1,
		));

		return array_pop($extLinks);
	}

	protected function getBreadcrumbs()
	{
		$crumbs = array();

		$parts = explode('/', trim($this->arParams['RELATIVE_PATH'], '/'));
		array_pop($parts);//last element is file.
		if(empty($parts))
		{
			$parts[] = '';
		}
		foreach ($parts as $i => $part)
		{
			$crumbs[] = CComponentEngine::makePathFromTemplate($this->arParams['PATH_TO_FOLDER_LIST'], array(
					'PATH' => implode('/', (array_slice($parts, 0, $i + 1))),
				));
		}
		unset($i, $part);

		return $crumbs;
	}

	protected function showBizProc($documentData)
	{
		$this->arResult['BIZPROC_PERMISSION'] = array();
		$this->arResult['BIZPROC_PERMISSION']['START'] = CBPDocument::canUserOperateDocument(
			CBPCanUserOperateOperation::StartWorkflow,
			$this->getUser()->getId(),
			$documentData['DISK']['DOCUMENT_ID']
		);
		$this->arResult['BIZPROC_PERMISSION']['VIEW'] = CBPDocument::canUserOperateDocument(
			CBPCanUserOperateOperation::ViewWorkflow,
			$this->getUser()->getId(),
			$documentData['DISK']['DOCUMENT_ID']
		);
		$this->arResult['BIZPROC_PERMISSION']['STOP'] = $this->arResult['BIZPROC_PERMISSION']['START'];
		$this->arResult['BIZPROC_PERMISSION']['DROP'] = CBPDocument::canUserOperateDocument(
			CBPCanUserOperateOperation::CreateWorkflow,
			$this->getUser()->getId(),
			$documentData['DISK']['DOCUMENT_ID']
		);

		foreach($documentData as $nameModuleId => $data)
		{
			$temporary[$nameModuleId] = CBPDocument::getDocumentStates($data['DOCUMENT_TYPE'], $data['DOCUMENT_ID']);
		}
		if(isset($temporary['OLD_FILE']))
		{
			$allBizProcArray = array_merge($temporary['DISK'], $temporary['WEBDAV'], $temporary['OLD_FILE']);
		}
		else
		{
			$allBizProcArray = array_merge($temporary['DISK'], $temporary['WEBDAV']);
		}
		if(!empty($allBizProcArray))
		{
			$userGroup = $this->getUser()->getUserGroupArray();
			$userGroup[]= 'author';
			if ($this->request->isPost() && intval($this->request->getPost('bizproc_index')) > 0)
			{
				$bizProcWorkflowId = array();
				$bizprocIndex = intval($this->request->getPost('bizproc_index'));
				for ($i = 1; $i <= $bizprocIndex; $i++)
				{
					$bpId = trim($this->request->getPost("bizproc_id_".$i));
					$bpTemplateId = intval($this->request->getPost("bizproc_template_id_".$i));
					$bpEvent = trim($this->request->getPost("bizproc_event_".$i));
					if (strlen($bpId) > 0)
					{
						if (!array_key_exists($bpId, $allBizProcArray))
							continue;
					}
					else
					{
						if (!array_key_exists($bpTemplateId, $allBizProcArray))
							continue;
						$bpId = $bizProcWorkflowId[$bpTemplateId];
					}
					if (strlen($bpEvent) > 0)
					{
						$errors = array();
						CBPDocument::sendExternalEvent(
							$bpId,
							$bpEvent,
							array("Groups" => $userGroup, "User" => $this->getUser()->getId()),
							$errors
						);
					}
					else
					{
						$errors = array();
						foreach($allBizProcArray as $idBizProc => $bizProcArray)
						{
							if($idBizProc == $bpId)
							{
								CBPDocument::TerminateWorkflow($bpId,$bizProcArray['DOCUMENT_ID'],$errors);
							}
						}
					}
					if (!empty($errors))
					{
						foreach ($errors as $error)
						{
							$this->arResult['ERROR_MESSAGE'] = $error['message'];
						}
					}
					else
					{
						LocalRedirect($this->arResult['PATH_TO_FILE_VIEW']."#tab-bp");
					}
				}
			}
			$this->arResult['BIZPROC_LIST'] = array();
			$count = 1;
			foreach($allBizProcArray as $idBizProc => $bizProcArray)
			{
				if(intVal($bizProcArray["WORKFLOW_STATUS"]) < 0 || $idBizProc <= 0)
				{
					continue;
				}
				else if(!CBPDocument::canUserOperateDocument(
					CBPCanUserOperateOperation::ViewWorkflow,
					$this->getUser()->getId(),
					$documentData['DISK']['DOCUMENT_ID'],
					array(
						"DocumentStates" => $bizProcArray,
						"WorkflowId" => $bizProcArray["ID"] > 0 ? $bizProcArray["ID"] : $bizProcArray["TEMPLATE_ID"]
					)))
				{
					continue;
				}

				$groups = CBPDocument::getAllowableUserGroups($documentData['DISK']['DOCUMENT_TYPE']);
				foreach ($groups as $key => $val)
					$groups[strtolower($key)] = $val;

				$users = array();
				$dmpWorkflow = CBPTrackingService::getList(
					array("ID" => "DESC"),
					array("WORKFLOW_ID" => $idBizProc, "TYPE" => array(CBPTrackingType::Report, CBPTrackingType::Custom, CBPTrackingType::FaultActivity)),
					false,
					array("nTopCount" => 5),
					array("ID", "TYPE", "MODIFIED", "ACTION_NOTE", "ACTION_TITLE", "ACTION_NAME", "EXECUTION_STATUS", "EXECUTION_RESULT")
				);

				while ($track = $dmpWorkflow->getNext())
				{
					$messageTemplate = "";
					switch ($track["TYPE"])
					{
						case 1:
							$messageTemplate = Loc::getMessage("DISK_FILE_VIEW_BPABL_TYPE_1");
							break;
						case 2:
							$messageTemplate = Loc::getMessage("DISK_FILE_VIEW_BPABL_TYPE_2");
							break;
						case 3:
							$messageTemplate = Loc::getMessage("DISK_FILE_VIEW_BPABL_TYPE_3");
							break;
						case 4:
							$messageTemplate = Loc::getMessage("DISK_FILE_VIEW_BPABL_TYPE_4");
							break;
						case 5:
							$messageTemplate = Loc::getMessage("DISK_FILE_VIEW_BPABL_TYPE_5");
							break;
						default:
							$messageTemplate = Loc::getMessage("DISK_FILE_VIEW_BPABL_TYPE_6");
					}

					$name = (strlen($track["ACTION_TITLE"]) > 0 ? $track["ACTION_TITLE"] : $track["ACTION_NAME"]);
					switch ($track["EXECUTION_STATUS"])
					{
						case CBPActivityExecutionStatus::Initialized:
							$status = Loc::getMessage("DISK_FILE_VIEW_BPABL_STATUS_1");
							break;
						case CBPActivityExecutionStatus::Executing:
							$status = Loc::getMessage("DISK_FILE_VIEW_BPABL_STATUS_2");
							break;
						case CBPActivityExecutionStatus::Canceling:
							$status = Loc::getMessage("DISK_FILE_VIEW_BPABL_STATUS_3");
							break;
						case CBPActivityExecutionStatus::Closed:
							$status = Loc::getMessage("DISK_FILE_VIEW_BPABL_STATUS_4");
							break;
						case CBPActivityExecutionStatus::Faulting:
							$status = Loc::getMessage("DISK_FILE_VIEW_BPABL_STATUS_5");
							break;
						default:
							$status = Loc::getMessage("DISK_FILE_VIEW_BPABL_STATUS_6");
					}
					switch ($track["EXECUTION_RESULT"])
					{
						case CBPActivityExecutionResult::None:
							$result = Loc::getMessage("DISK_FILE_VIEW_BPABL_RES_1");
							break;
						case CBPActivityExecutionResult::Succeeded:
							$result = Loc::getMessage("DISK_FILE_VIEW_BPABL_RES_2");
							break;
						case CBPActivityExecutionResult::Canceled:
							$result = Loc::getMessage("DISK_FILE_VIEW_BPABL_RES_3");
							break;
						case CBPActivityExecutionResult::Faulted:
							$result = Loc::getMessage("DISK_FILE_VIEW_BPABL_RES_4");
							break;
						case CBPActivityExecutionResult::Uninitialized:
							$result = Loc::getMessage("DISK_FILE_VIEW_BPABL_RES_5");
							break;
						default:
							$result = Loc::getMessage("DISK_FILE_VIEW_BPABL_RES_6");
					}

					$note = ((strlen($track["ACTION_NOTE"]) > 0) ? ": ".$track["ACTION_NOTE"] : "");
					$pattern = array("#ACTIVITY#", "#STATUS#", "#RESULT#", "#NOTE#");
					$replaceArray = array($name, $status, $result, $note);
					if (!empty($track["ACTION_NAME"]) && !empty($track["ACTION_TITLE"]))
					{
						$pattern[] = $track["ACTION_NAME"];
						$replaceArray[] = $track["ACTION_TITLE"];
					}
					$messageTemplate = str_replace(
						$pattern,
						$replaceArray,
						$messageTemplate);

					if (preg_match_all("/(?<=\{\=user\:)([^\}]+)(?=\})/is", $messageTemplate, $arMatches))
					{
						$pattern = array(); $replacement = array();
						foreach ($arMatches[0] as $user)
						{
							$user = preg_quote($user);
							if (in_array("/\{\=user\:".$user."\}/is", $pattern))
								continue;
							$replace = "";
							if (array_key_exists(strtolower($user), $groups))
								$replace = $groups[strtolower($user)];
							elseif (array_key_exists(strtoupper($user), $groups))
								$replace = $groups[strtoupper($user)];
							else
							{
								$id = intVal(str_replace("user_", "", $user));
								if (!array_key_exists($id, $users)):
									$dbRes = \CUser::getByID($id);
									$users[$id] = false;
									if ($dbRes && $arUser = $dbRes->getNext()):
										$name = \CUser::formatName($this->arParams["NAME_TEMPLATE"], $arUser);
										$arUser["FULL_NAME"] = (empty($name) ? $arUser["LOGIN"] : $name);
										$users[$id] = $arUser;
									endif;
								endif;
								if (!empty($users[$id]))
									$replace = "<a href=\"".
										\CComponentEngine::makePathFromTemplate('/company/personal/user/#USER_ID#/', array("USER_ID" => $id))."\">".
										$users[$id]["FULL_NAME"]."</a>";
							}

							if (!empty($replace))
							{
								$pattern[] = "/\{\=user\:".$user."\}/is";
								$pattern[] = "/\{\=user\:user\_".$user."\}/is";
								$replacement[] = $replace;
								$replacement[] = $replace;
							}
						}
						$messageTemplate = preg_replace($pattern, $replacement, $messageTemplate);
					}

					$this->arResult['BIZPROC_LIST'][$count]['DUMP_WORKFLOW'][] = $messageTemplate;
				}

				$tasks = CBPDocument::getUserTasksForWorkflow($this->getUser()->getId(), $idBizProc);
				$events = CBPDocument::getAllowableEvents($this->getUser()->getId(), $userGroup, $bizProcArray);
				if(!empty($tasks))
				{
					foreach($tasks as $task)
					{
						$urlTaskBizproc = \CComponentEngine::makePathFromTemplate($this->arParams['PATH_TO_DISK_TASK'],array("ID" => $task['ID']));
						$urlTaskBizproc .= "?back_url=".urlencode($this->getApplication()->getCurPage())."&file=".$this->file->getName();
						$this->arResult['BIZPROC_LIST'][$count]['TASK']['URL'] = $urlTaskBizproc;
						$this->arResult['BIZPROC_LIST'][$count]['TASK']['TASK_ID'] = $task['ID'];
						$this->arResult['BIZPROC_LIST'][$count]['TASK']['TASK_NAME'] = $task['NAME'];
					}

				}
				$this->arResult['BIZPROC_LIST'][$count]['ID'] = $bizProcArray['ID'];
				$this->arResult['BIZPROC_LIST'][$count]['WORKFLOW_STATUS'] = $bizProcArray["WORKFLOW_STATUS"];
				$this->arResult['BIZPROC_LIST'][$count]['TEMPLATE_ID'] = $bizProcArray['TEMPLATE_ID'];
				$this->arResult['BIZPROC_LIST'][$count]['TEMPLATE_NAME'] = $bizProcArray['TEMPLATE_NAME'];
				$this->arResult['BIZPROC_LIST'][$count]['STATE_MODIFIED'] = $bizProcArray['STATE_MODIFIED'];
				$this->arResult['BIZPROC_LIST'][$count]['STATE_TITLE'] = $bizProcArray['STATE_TITLE'];
				$this->arResult['BIZPROC_LIST'][$count]['STATE_NAME'] = $bizProcArray['STATE_NAME'];
				$this->arResult['BIZPROC_LIST'][$count]['EVENTS'] = $events;
				$count++;
			}
		}
	}

	protected function getAutoloadTemplateBizProc($documentData)
	{
		$this->arResult['WORKFLOW_TEMPLATES'] = array();
		$this->arResult['BIZPROC_PARAMETERS'] = false;
		foreach($documentData as $nameModule => $data)
		{
			$workflowTemplateObject = CBPWorkflowTemplateLoader::getList(
				array(),
				array("DOCUMENT_TYPE" => $data["DOCUMENT_TYPE"], "AUTO_EXECUTE" => CBPDocumentEventType::Edit, "ACTIVE" => "Y"),
				false,
				false,
				array("ID", "NAME", "DESCRIPTION", "PARAMETERS")
			);
			while ($workflowTemplate = $workflowTemplateObject->getNext())
			{
				if(!empty($workflowTemplate['PARAMETERS']))
				{
					$this->arResult['BIZPROC_PARAMETERS'] = true;
				}
				$this->arResult['WORKFLOW_TEMPLATES'][$workflowTemplate['ID']]['ID'] = $workflowTemplate['ID'];
				$this->arResult['WORKFLOW_TEMPLATES'][$workflowTemplate['ID']]['NAME'] = $workflowTemplate['NAME'];
				$this->arResult['WORKFLOW_TEMPLATES'][$workflowTemplate['ID']]['PARAMETERS'] = $workflowTemplate['PARAMETERS'];
			}
		}
	}
}