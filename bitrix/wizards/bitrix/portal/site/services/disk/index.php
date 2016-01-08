<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if(!Bitrix\Main\Loader::includeModule("disk"))
	return;

if (WIZARD_B24_TO_CP)
{
	$commonStorage = \Bitrix\Disk\Driver::getInstance()->getStorageByCommonId('shared_files_'.WIZARD_SITE_ID);
	if ($commonStorage)
	{
		$commonStorage->changeBaseUrl(WIZARD_SITE_DIR.'docs/shared/');
		$commonStorageId = $commonStorage->getId();
	}
}

$driver = \Bitrix\Disk\Driver::getInstance();
$rightsManager = $driver->getRightsManager();
$taskIdEdit = $rightsManager->getTaskIdByName($rightsManager::TASK_EDIT);
$taskIdFull = $rightsManager->getTaskIdByName($rightsManager::TASK_FULL);

$employeeCode = 'G'.WIZARD_EMPLOYEES_GROUP;
if (CModule::IncludeModule("iblock"))
{
	$rsIBlock = CIBlock::GetList(array(), array("CODE" => "departments"));
	if ($arIBlock = $rsIBlock->Fetch())
	{
		$dbUpdepartment = CIBlockSection::GetList(
			array(),
			array(
				"SECTION_ID" => 0,
				"IBLOCK_ID" => $arIBlock["ID"]
			)
		);
		if ($upDepartment = $dbUpdepartment->Fetch())
		{
			$employeeCode = "DR".$upDepartment['ID'];
		}
	}
}

//Common storage
$dbDisk = Bitrix\Disk\Storage::getList(array("filter"=>array("ENTITY_ID" => "shared_files_".WIZARD_SITE_ID)));
if (!$dbDisk->Fetch() && !WIZARD_B24_TO_CP)
{
	COption::SetOptionString('disk', 'disk_allow_autoconnect_shared_objects', 'Y');

	$commonStorage = $driver->addCommonStorage(
		array(
			'NAME' => GetMessage("COMMON_DISK"),
			'ENTITY_ID' => "shared_files_".WIZARD_SITE_ID."",
			'SITE_ID' => WIZARD_SITE_ID
		),
		array(
			array(
				'ACCESS_CODE' => $employeeCode, //Edit access for all employees
				'TASK_ID' => $taskIdEdit,
			),
			array(
				'ACCESS_CODE' => 'G'.WIZARD_DIRECTION_GROUP, //Edit access for directors
				'TASK_ID' => $taskIdEdit,
			),
			array(
				'ACCESS_CODE' => 'G'.WIZARD_PORTAL_ADMINISTRATION_GROUP, //Full access for admins
				'TASK_ID' => $taskIdFull,
			),
		)
	);

	if($commonStorage)
	{
		$commonStorage->changeBaseUrl(WIZARD_SITE_DIR."docs/shared/");
		$commonStorageId = $commonStorage->getId();

		$arDemoFiles = array(
			$_SERVER["DOCUMENT_ROOT"].WIZARD_SERVICE_RELATIVE_PATH."/demo/".LANGUAGE_ID."/presentation.pptx" => GetMessage("COMMON_FILE_NAME1").'.pptx',
			$_SERVER["DOCUMENT_ROOT"].WIZARD_SERVICE_RELATIVE_PATH."/demo/".LANGUAGE_ID."/form.docx" => GetMessage("COMMON_FILE_NAME2").".docx",
			$_SERVER["DOCUMENT_ROOT"].WIZARD_SERVICE_RELATIVE_PATH."/demo/".LANGUAGE_ID."/invoice.xls" => GetMessage("COMMON_FILE_NAME3").".xls"
		);
		foreach($arDemoFiles as $filePath => $name)
		{
			if (!file_exists($filePath))
				continue;

			//Create new file
			$arFile = CFile::MakeFileArray($filePath);
			if (is_array($arFile))
			{
				$fileModel = $commonStorage->uploadFile(
					$arFile,
					array(
						'NAME' => $name,
						'CREATED_BY' => $USER->GetID(),
					)
				);
			}
		}

		//Create new folder \Bitrix\Disk\Folder $folder
		$folder = $commonStorage->addFolder(array(
			'NAME' => GetMessage("COMMON_FOLDER_NAME1"),
			'CREATED_BY' => $USER->GetID(),
		));
		if ($folder)
		{
			$subFolder = $folder->addSubFolder(array(
				'NAME' => GetMessage("COMMON_FOLDER_NAME2"),
				'CREATED_BY' => $USER->GetID(),
			));
			$subFolder = $folder->addSubFolder(array(
				'NAME' => GetMessage("COMMON_FOLDER_NAME3"),
				'CREATED_BY' => $USER->GetID(),
			));
		}
	}
}

//directors storage
$dbDisk = Bitrix\Disk\Storage::getList(array("filter"=>array("ENTITY_ID" => "directors_files_".WIZARD_SITE_ID)));
if (!$dbDisk->Fetch())
{
	$directorsStorage = $driver->addCommonStorage(array(
			'NAME' => GetMessage("DIRECTORS_STORAGE"),
			'ENTITY_ID' => "directors_files_".WIZARD_SITE_ID."",
			'SITE_ID' => WIZARD_SITE_ID
		),
		array(
			array(
				'ACCESS_CODE' => 'G'.WIZARD_DIRECTION_GROUP,
				'TASK_ID' => $taskIdEdit,
			),
			array(
				'ACCESS_CODE' => 'G'.WIZARD_PORTAL_ADMINISTRATION_GROUP,
				'TASK_ID' => $taskIdFull,
			),
		)
	);

	if ($directorsStorage)
	{
		$directorsStorage->changeBaseUrl(WIZARD_SITE_DIR."docs/manage/");
		$directorsStorageId = $directorsStorage->getId();

		if (!WIZARD_B24_TO_CP)
		{
			//Create new folder \Bitrix\Disk\Folder $folder
			$folder = $directorsStorage->addFolder(array(
				'NAME' => GetMessage("DIRECTORS_FOLDER_NAME1"),
				'CREATED_BY' => $USER->GetID(),
			));
			if ($folder)
			{
				$arDemoFiles = array(
					$_SERVER["DOCUMENT_ROOT"].WIZARD_SERVICE_RELATIVE_PATH."/demo/".LANGUAGE_ID."/gos_report.docx" => GetMessage("DIRECTORS_FILE_NAME1").'.docx'
				);
				foreach($arDemoFiles as $filePath => $name)
				{
					if (!file_exists($filePath))
						continue;

					//Create new file
					$arFile = CFile::MakeFileArray($filePath);
					if (is_array($arFile))
					{
						$fileModel = $folder->uploadFile(
							$arFile,
							array(
								'NAME' => $name,
								'CREATED_BY' => $USER->GetID(),
							)
						);
					}
				}
			}
			$folder = $directorsStorage->addFolder(array(
				'NAME' => GetMessage("DIRECTORS_FOLDER_NAME2"),
				'CREATED_BY' => $USER->GetID(),
			));
			if ($folder)
			{
				$arDemoFiles = array(
					$_SERVER["DOCUMENT_ROOT"].WIZARD_SERVICE_RELATIVE_PATH."/demo/".LANGUAGE_ID."/state_schedule.docx" => GetMessage("DIRECTORS_FILE_NAME2").'.docx',
					$_SERVER["DOCUMENT_ROOT"].WIZARD_SERVICE_RELATIVE_PATH."/demo/".LANGUAGE_ID."/employees.docx" => GetMessage("DIRECTORS_FILE_NAME3").'.docx',
				);
				foreach($arDemoFiles as $filePath => $name)
				{
					if (!file_exists($filePath))
						continue;

					//Create new file
					$arFile = CFile::MakeFileArray($filePath);
					if (is_array($arFile))
					{
						$fileModel = $folder->uploadFile(
							$arFile,
							array(
								'NAME' => $name,
								'CREATED_BY' => $USER->GetID(),
							)
						);
					}
				}
			}
		}
	}
}

//sales storage
$dbDisk = Bitrix\Disk\Storage::getList(array("filter"=>array("ENTITY_ID" => "sales_files_".WIZARD_SITE_ID)));
if (!$dbDisk->Fetch())
{
	$salesStorage = $driver->addCommonStorage(array(
			'NAME' => GetMessage("SALES_STORAGE"),
			'ENTITY_ID' => "sales_files_".WIZARD_SITE_ID."",
			'SITE_ID' => WIZARD_SITE_ID
		),
		array(
			array(
				'ACCESS_CODE' => 'G'.WIZARD_DIRECTION_GROUP,
				'TASK_ID' => $taskIdEdit,
			),
			array(
				'ACCESS_CODE' => 'G'.WIZARD_MARKETING_AND_SALES_GROUP,
				'TASK_ID' => $taskIdEdit,
			),
			array(
				'ACCESS_CODE' => 'G'.WIZARD_PORTAL_ADMINISTRATION_GROUP,
				'TASK_ID' => $taskIdFull,
			),
		)
	);

	if ($salesStorage)
	{
		$salesStorage->changeBaseUrl(WIZARD_SITE_DIR."docs/sale/");
		$salesStorageId = $salesStorage->getId();

		if (!WIZARD_B24_TO_CP)
		{
			//Create new folder \Bitrix\Disk\Folder $folder
			$folder = $salesStorage->addFolder(array(
				'NAME' => GetMessage("SALES_FOLDER_NAME1"),
				'CREATED_BY' => $USER->GetID(),
			));
			if ($folder)
			{
				$arDemoFiles = array(
					$_SERVER["DOCUMENT_ROOT"].WIZARD_SERVICE_RELATIVE_PATH."/demo/".LANGUAGE_ID."/market_plan.docx" => GetMessage("SALES_FILE_NAME1").'.docx',
					$_SERVER["DOCUMENT_ROOT"].WIZARD_SERVICE_RELATIVE_PATH."/demo/".LANGUAGE_ID."/logo.gif" => GetMessage("SALES_FILE_NAME2").".gif",
				);
				foreach($arDemoFiles as $filePath => $name)
				{
					if (!file_exists($filePath))
						continue;

					//Create new file
					$arFile = CFile::MakeFileArray($filePath);
					if (is_array($arFile))
					{
						$fileModel = $folder->uploadFile(
							$arFile,
							array(
								'NAME' => $name,
								'CREATED_BY' => $USER->GetID(),
							)
						);
					}
				}
			}
			$folder = $salesStorage->addFolder(array(
				'NAME' => GetMessage("SALES_FOLDER_NAME2"),
				'CREATED_BY' => $USER->GetID(),
			));
			if ($folder)
			{
				$arDemoFiles = array(
					$_SERVER["DOCUMENT_ROOT"].WIZARD_SERVICE_RELATIVE_PATH."/demo/".LANGUAGE_ID."/commercial_offer.docx" => GetMessage("SALES_FILE_NAME3").'.docx',
				);
				foreach($arDemoFiles as $filePath => $name)
				{
					if (!file_exists($filePath))
						continue;

					//Create new file
					$arFile = CFile::MakeFileArray($filePath);
					if (is_array($arFile))
					{
						$fileModel = $folder->uploadFile(
							$arFile,
							array(
								'NAME' => $name,
								'CREATED_BY' => $USER->GetID(),
							)
						);
					}
				}
			}
		}
	}
}
if ($directorsStorageId)
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/docs/manage/index.php", Array("MANAGE_STORAGE_ID" => $directorsStorageId));
if ($commonStorageId)
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/docs/shared/index.php", Array("SHARED_STORAGE_ID" => $commonStorageId));
if ($salesStorageId)
	CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/docs/sale/index.php", Array("SALE_STORAGE_ID" => $salesStorageId));
?>