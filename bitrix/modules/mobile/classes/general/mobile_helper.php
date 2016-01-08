<?
class CMobileHelper
{
	public static function InitFileStorage()
	{
		static $bInited = false;

		$arResult = array();

		if (!$bInited)
		{
			$bDiskEnabled = (
				\Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false) 
				&& CModule::includeModule('disk')
			);

			if ($bDiskEnabled)
			{
				$storage = \Bitrix\Disk\Driver::getInstance()->getStorageByUserId($GLOBALS["USER"]->GetID());
				if (!$storage)
				{
					$arResult = array(
						"ERROR_CODE" => "NO_DISC_STORAGE",
						"ERROR_MESSAGE" => "No disk storage"
					);
				}
				else
				{
					$folder = $storage->getFolderForUploadedFiles($GLOBALS["USER"]->GetID());
					if (!$folder)
					{
						$arResult = array(
							"ERROR_CODE" => "NO_DISC_FOLDER",
							"ERROR_MESSAGE" => "No disk folder"
						);
					}
					else
					{
						$arResult = array(
							"DISC_STORAGE" => $storage,
							"DISC_FOLDER" => $folder
						);
					}
				}
			}
			elseif (CModule::IncludeModule("webdav"))
			{
				$data = CWebDavIblock::getRootSectionDataForUser($GLOBALS["USER"]->GetID());
				if (is_array($data))
				{
					$ob = new CWebDavIblock($data["IBLOCK_ID"], "", array(
						"ROOT_SECTION_ID" => $data["SECTION_ID"],
						"DOCUMENT_TYPE" => array("webdav", 'CIBlockDocumentWebdavSocnet', 'iblock_'.$data['SECTION_ID'].'_user_'.intval($GLOBALS["USER"]->GetID()))
					));		
				}

				if (!$ob)
				{
					$arResult = array(
						"ERROR_CODE" => "NO_WEBDAV_SECTION",
						"ERROR_MESSAGE" => "No webdav section"
					);
				}
				else
				{
					$arResult = array(
						"WEBDAV_DATA" => $data,
						"WEBDAV_IBLOCK_OBJECT" => $ob
					);
				}
			}

			$bInited = true;
		}
		
		return $arResult;
	}

	public static function SaveFile($arFile, $arFileStorage)
	{
		$arResult = array();

		if (empty($arFile))
		{
			$arResult = array(
				"ERROR_CODE" => "EMPTY_FILE",
				"ERROR_MESSAGE" => "File is empty"
			);
		}

		if (!empty($arFileStorage["DISC_FOLDER"]))
		{
			$file = $arFileStorage["DISC_FOLDER"]->uploadFile(
				$arFile, 
				array(
					'NAME' => $arFile["name"], 
					'CREATED_BY' => $GLOBALS["USER"]->GetID()
				), 
				array(), 
				true
			);

			$arResult["ID"] = $file->getId();
		}
		elseif (
			!empty($arFileStorage["WEBDAV_DATA"])
			&& !empty($arFileStorage["WEBDAV_IBLOCK_OBJECT"])
		)
		{
			$dropTargetID = $arFileStorage["WEBDAV_IBLOCK_OBJECT"]->GetMetaID("DROPPED");
			$arParent = $arFileStorage["WEBDAV_IBLOCK_OBJECT"]->GetObject(array("section_id" => $dropTargetID));
			if (!$arParent["not_found"])
			{
				$path = $arFileStorage["WEBDAV_IBLOCK_OBJECT"]->_get_path($arParent["item_id"], false);
				$tmpName = str_replace(array(":", ".", "/", "\\"), "_", ConvertTimeStamp(time(), "FULL"));
				$tmpOptions = array("path" => str_replace("//", "/", $path."/".$tmpName)); 
				$arParent = $arFileStorage["WEBDAV_IBLOCK_OBJECT"]->GetObject($tmpOptions);
				if ($arParent["not_found"])
				{
					$rMKCOL = $arFileStorage["WEBDAV_IBLOCK_OBJECT"]->MKCOL($tmpOptions);
					if (intval($rMKCOL) == 201)
					{
						$arFileStorage["WEBDAV_DATA"]["SECTION_ID"] = $arFileStorage["WEBDAV_IBLOCK_OBJECT"]->arParams["changed_element_id"];
					}
				}
				else
				{
					$arFileStorage["WEBDAV_DATA"]["SECTION_ID"] = $arParent['item_id'];
					if (!$arFileStorage["WEBDAV_IBLOCK_OBJECT"]->CheckUniqueName($tmpName, $arFileStorage["WEBDAV_DATA"]["SECTION_ID"], $tmpRes))
					{
						$path = $arFileStorage["WEBDAV_IBLOCK_OBJECT"]->_get_path($arFileStorage["WEBDAV_DATA"]["SECTION_ID"], false);
						$tmpName = randString(6);
						$tmpOptions = array("path" => str_replace("//", "/", $path."/".$tmpName)); 
						$rMKCOL = $arFileStorage["WEBDAV_IBLOCK_OBJECT"]->MKCOL($tmpOptions);
						if (intval($rMKCOL) == 201)
						{
							$arFileStorage["WEBDAV_DATA"]["SECTION_ID"] = $arFileStorage["WEBDAV_IBLOCK_OBJECT"]->arParams["changed_element_id"];
						}
					}
				}
			}

			$options = array(
				"new" => true, 
				'dropped' => true,
				"arFile" => $arFile, 
				"arDocumentStates" => false,
				"arUserGroups" => array_merge($arFileStorage["WEBDAV_IBLOCK_OBJECT"]->USER["GROUPS"], array("Author")),
				"FILE_NAME" => $arFile["name"],
				"IBLOCK_ID" => $arFileStorage["WEBDAV_DATA"]["IBLOCK_ID"],
				"IBLOCK_SECTION_ID" => $arFileStorage["WEBDAV_DATA"]["SECTION_ID"],
				"USER_FIELDS" => array()
			); 

			$GLOBALS['USER_FIELD_MANAGER']->EditFormAddFields($arFileStorage["WEBDAV_IBLOCK_OBJECT"]->GetUfEntity(), $options['USER_FIELDS']);

			$GLOBALS["DB"]->StartTransaction();

			if (!$arFileStorage["WEBDAV_IBLOCK_OBJECT"]->put_commit($options))
			{
				$arResult = array(
					"ERROR_CODE" => "error_put",
					"ERROR_MESSAGE" => $arFileStorage["WEBDAV_IBLOCK_OBJECT"]->LAST_ERROR
				);
				$GLOBALS["DB"]->Rollback();
			}
			else
			{
				$GLOBALS["DB"]->Commit();
				$arResult["ID"] = $options['ELEMENT_ID'];
			}
		}
		else
		{
			$arResult["ID"] = CFile::SaveFile($arFile, $arFile["MODULE_ID"]);
		}

		return $arResult;
	}

	public static function SendPullComment($type, $arFields)
	{
		if (!CModule::IncludeModule("pull"))
		{
			return;
		}

		if ($type == "blog")
		{
			$arCommentParams = Array(
				"ID" => $arFields["COMMENT_ID"],
				"ENTITY_XML_ID" => "BLOG_".$arFields["POST_ID"],
				"FULL_ID" => array(
					"BLOG_".$arFields["POST_ID"],
					$arFields["COMMENT_ID"]
				),
				"ACTION" => "REPLY",
				"APPROVED" => "Y",
				"PANELS" => array(
					"EDIT" => "N",
					"MODERATE" => "N",
					"DELETE" => "N"
				),
				"NEW" => "Y",
				"AUTHOR" => array(
					"ID" => $GLOBALS["USER"]->GetID(),
					"NAME" => $arFields["arAuthor"]["NAME_FORMATED"],
					"URL" => $arFields["arAuthor"]["url"],
					"E-MAIL" => $arFields["arComment"]["AuthorEmail"],
					"AVATAR" => $arFields["arAuthor"]["PERSONAL_PHOTO_resized"]["src"],
					"IS_EXTRANET" => (is_array($GLOBALS["arExtranetUserID"]) && in_array($GLOBALS["USER"]->GetID(), $GLOBALS["arExtranetUserID"])),
				),
				"POST_TIMESTAMP" => $arFields["arComment"]["DATE_CREATE_TS"],
				"POST_TIME" => $arFields["arComment"]["DATE_CREATE_TIME"],
				"POST_DATE" => $arFields["arComment"]["DateFormated"],
				"POST_MESSAGE_TEXT" => $arFields["arComment"]["TextFormated"],
				"POST_MESSAGE_TEXT_MOBILE" => $arFields["arComment"]["TextFormatedMobile"],
				"URL" => array(
					"LINK" => str_replace(
						array("##comment_id#", "#comment_id#"), 
						array("", $arFields["COMMENT_ID"]), 
						$arFields["arUrl"]["LINK"]
					),
					"EDIT" => "__blogEditComment('".$arFields["COMMENT_ID"]."', '".$arFields["POST_ID"]."');",
					"MODERATE" => str_replace(
						array("#source_post_id#", "#post_id#", "#comment_id#", "&".bitrix_sessid_get()),
						array($arFields["POST_ID"], $arFields["POST_ID"], $arFields["COMMENT_ID"], ""),
						($arFields["arComment"]["CAN_SHOW"] == "Y" 
							? $arFields["arUrl"]["SHOW"]
							: ($arFields["arComment"]["CAN_HIDE"] == "Y" 
								? $arFields["arUrl"]["HIDE"]
								: ""
							)
						)
					),
					"DELETE" => str_replace(
						array("#source_post_id#", "#post_id#", "#comment_id#", "&".bitrix_sessid_get()),
						array($arFields["POST_ID"], $arFields["POST_ID"], $arFields["COMMENT_ID"], ""),
						$arFields["arUrl"]["DELETE"]
					)
				),
				"AFTER" => "",
				"BEFORE_ACTIONS_MOBILE" => "",
				"AFTER_MOBILE" => ""
			);

			if ($arFields["SHOW_RATING"] == "Y")
			{
				ob_start();
				$GLOBALS["APPLICATION"]->IncludeComponent(
					"bitrix:rating.vote", $arFields["RATING_TYPE"],
					Array(
						"ENTITY_TYPE_ID" => "BLOG_COMMENT",
						"ENTITY_ID" => $arFields["arComment"]["ID"],
						"OWNER_ID" => $arFields["arComment"]["AUTHOR_ID"],
						"USER_VOTE" => $arFields["arRating"][$arFields["arComment"]["ID"]]["USER_VOTE"],
						"USER_HAS_VOTED" => $arFields["arRating"][$arFields["arComment"]["ID"]]["USER_HAS_VOTED"],
						"TOTAL_VOTES" => $arFields["arRating"][$arFields["arComment"]["ID"]]["TOTAL_VOTES"],
						"TOTAL_POSITIVE_VOTES" => $arFields["arRating"][$arFields["arComment"]["ID"]]["TOTAL_POSITIVE_VOTES"],
						"TOTAL_NEGATIVE_VOTES" => $arFields["arRating"][$arFields["arComment"]["ID"]]["TOTAL_NEGATIVE_VOTES"],
						"TOTAL_VALUE" => $arFields["arRating"][$arFields["arComment"]["ID"]]["TOTAL_VALUE"],
						"PATH_TO_USER_PROFILE" => $arFields["arUrl"]["USER"]
					),
					false,
					array("HIDE_ICONS" => "Y")
				);
				$arCommentParams["BEFORE_ACTIONS"] = ob_get_clean();

				ob_start();
				$GLOBALS["APPLICATION"]->IncludeComponent(
					"bitrix:rating.vote", "mobile_comment_".$arFields["RATING_TYPE"],
					Array(
						"ENTITY_TYPE_ID" => "BLOG_COMMENT",
						"ENTITY_ID" => $arFields["arComment"]["ID"],
						"OWNER_ID" => $arFields["arComment"]["AUTHOR_ID"],
						"USER_VOTE" => $arFields["arRating"][$arFields["arComment"]["ID"]]["USER_VOTE"],
						"USER_HAS_VOTED" => $arFields["arRating"][$arFields["arComment"]["ID"]]["USER_HAS_VOTED"],
						"TOTAL_VOTES" => $arFields["arRating"][$arFields["arComment"]["ID"]]["TOTAL_VOTES"],
						"TOTAL_POSITIVE_VOTES" => $arFields["arRating"][$arFields["arComment"]["ID"]]["TOTAL_POSITIVE_VOTES"],
						"TOTAL_NEGATIVE_VOTES" => $arFields["arRating"][$arFields["arComment"]["ID"]]["TOTAL_NEGATIVE_VOTES"],
						"TOTAL_VALUE" => $arFields["arRating"][$arFields["arComment"]["ID"]]["TOTAL_VALUE"],
						"PATH_TO_USER_PROFILE" => $arFields["arUrl"]["USER"]
					),
					false,
					array("HIDE_ICONS" => "Y")
				);
				$arCommentParams["BEFORE_ACTIONS_MOBILE"] = ob_get_clean();
			}

			$arComment["UF"] = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("BLOG_COMMENT", $arFields["arComment"]["ID"], LANGUAGE_ID);
			$arUFResult = self::BuildUFFields($arComment["UF"]);
			$arCommentParams["AFTER"] .= $arUFResult["AFTER"];
			$arCommentParams["AFTER_MOBILE"] .= $arUFResult["AFTER_MOBILE"];

			if($arFields["arComment"]["CAN_EDIT"] == "Y")
			{
				ob_start();

				?><script>
					top.text<?=$arFields["arComment"]["ID"]?> = text<?=$arFields["arComment"]["ID"]?> = '<?=CUtil::JSEscape(htmlspecialcharsBack($arFields["arComment"]["POST_TEXT"]))?>';
					top.title<?=$arFields["arComment"]["ID"]?> = title<?=$arFields["arComment"]["ID"]?> = '<?=(isset($arFields["arComment"]["TITLE"]) ? CUtil::JSEscape($arFields["arComment"]["TITLE"]) : '')?>';
					top.arComFiles<?=$arFields["arComment"]["ID"]?> = [];<?
				?></script><?
				$arCommentParams["AFTER"] .= ob_get_clean();			
			}

			CPullWatch::AddToStack('UNICOMMENTSBLOG_'.$arFields["POST_ID"],
				array(
					'module_id' => 'unicomments',
					'command' => 'comment',
					'params' => $arCommentParams
				)
			);
		}
	}

	public static function BuildUFFields($arUF)
	{
		$arResult = array(
			"AFTER" => "",
			"AFTER_MOBILE" => ""
		);

		if (
			is_array($arUF) 
			&& count($arUF) > 0
		)
		{
			ob_start();

			$eventHandlerID = false;
			$eventHandlerID = AddEventHandler("main", "system.field.view.file", Array("CSocNetLogTools", "logUFfileShow"));

			foreach ($arUF as $FIELD_NAME => $arUserField)
			{
				if(!empty($arUserField["VALUE"]))
				{
					$GLOBALS["APPLICATION"]->IncludeComponent(
						"bitrix:system.field.view",
						$arUserField["USER_TYPE"]["USER_TYPE_ID"],
						array(
							"arUserField" => $arUserField,
							"MOBILE" => "Y"
						), 
						null, 
						array("HIDE_ICONS"=>"Y")
					);
				}
			}
			if (
				$eventHandlerID !== false 
				&& intval($eventHandlerID) > 0
			)
			{
				RemoveEventHandler('main', 'system.field.view.file', $eventHandlerID);
			}

			$arResult["AFTER_MOBILE"] = ob_get_clean();

			ob_start();

			$eventHandlerID = false;
			$eventHandlerID = AddEventHandler("main", "system.field.view.file", Array("CSocNetLogTools", "logUFfileShow"));

			foreach ($arUF as $FIELD_NAME => $arUserField)
			{
				if(!empty($arUserField["VALUE"]))
				{
					$GLOBALS["APPLICATION"]->IncludeComponent(
						"bitrix:system.field.view",
						$arUserField["USER_TYPE"]["USER_TYPE_ID"],
						array(
							"arUserField" => $arUserField
						), 
						null, 
						array("HIDE_ICONS"=>"Y")
					);
				}
			}
			if (
				$eventHandlerID !== false 
				&& intval($eventHandlerID) > 0
			)
			{
				RemoveEventHandler('main', 'system.field.view.file', $eventHandlerID);
			}

			$arResult["AFTER"] .= ob_get_clean();
		}

		return $arResult;
	}

	public static function getUFForPostForm($arParams)
	{
		$arFileData = array();

		$arUF = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields($arParams["ENTITY_TYPE"], $arParams["ENTITY_ID"], LANGUAGE_ID);
		$ufCode = $arParams["UF_CODE"];

		if (
			!empty($arUF[$ufCode])
			&& !empty($arUF[$ufCode]["VALUE"])
		)
		{
			if ($arParams["IS_DISK_OR_WEBDAV_INSTALLED"])
			{
				if (
					\Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false)
					&& CModule::IncludeModule('disk')
				)
				{
					$userFieldManager = \Bitrix\Disk\Driver::getInstance()->getUserFieldManager();
					$urlManager = \Bitrix\Disk\Driver::getInstance()->getUrlManager();
					$userFieldManager->loadBatchAttachedObject($arUF[$ufCode]["VALUE"]);

					foreach($arUF[$ufCode]["VALUE"] as $attachedId)
					{
						$attachedObject = $userFieldManager->getAttachedObjectById($attachedId);
						if($attachedObject)
						{
							$file = $attachedObject->getObject();

							$fileName = $file->getName();

							$fileUrl = $urlManager->getUrlUfController('download', array('attachedId' => $attachedId));
							$fileUrl = str_replace("/bitrix/tools/disk/uf.php", SITE_DIR."mobile/ajax.php", $fileUrl);
							$fileUrl = $fileUrl.(strpos($fileUrl, "?") === false ? "?" : "&")."mobile_action=disk_uf_view&filename=".$fileName;

							if (
								\Bitrix\Disk\TypeFile::isImage($file)
								&& ($realFile = $file->getFile())
							)
							{
								$previewImageUrl = $urlManager->getUrlUfController(
									'show',
									array(
										'attachedId' => $attachedId,
										'width' => 144,
										'height' => 144,
										'exact' => 'Y',
										'signature' => \Bitrix\Disk\Security\ParameterSigner::getImageSignature($attachedId, 144, 144)
									)
								);
							}
							else
							{
								$previewImageUrl = false;
							}

							$icon = CMobileHelper::mobileDiskGetIconByFilename($fileName);
							$iconUrl = CComponentEngine::makePathFromTemplate('/bitrix/components/bitrix/mobile.disk.file.detail/images/'.$icon);

							$fileFata = array(
								'type' => $file->getExtension(),
								'ufCode' => $ufCode,
								'id' => $attachedId,
								'extension' => $file->getExtension(),
								'name' => $fileName,
								'url' => $fileUrl,
								'iconUrl' => $iconUrl
							);

							if ($previewImageUrl)
							{
								$fileFata['previewImageUrl'] = CHTTP::URN2URI($previewImageUrl);
							}

							$arFileData[] = $fileFata;
						}
					}
				}
				else // webdav
				{
					$data = CWebDavIblock::getRootSectionDataForUser($GLOBALS["USER"]->GetID());
					if (is_array($data))
					{
						$ibe = new CIBlockElement();
						$dbWDFile = $ibe->GetList(
							array(),
							array(
								'ID' => $arUF[$ufCode]["VALUE"],
								'IBLOCK_ID' => $data["IBLOCK_ID"]
							),
							false,
							false,
							array('ID', 'IBLOCK_ID', 'PROPERTY_FILE')
						);
						while ($arWDFile = $dbWDFile->Fetch())
						{
							if ($arFile = CFile::GetFileArray($arWDFile["PROPERTY_FILE_VALUE"]))
							{
								if (CFile::IsImage($arFile["FILE_NAME"], $arFile["CONTENT_TYPE"]))
								{
									$imageResized = CFile::ResizeImageGet(
										$arFile["ID"],
										array(
											"width" => 144,
											"height" => 144
										),
										BX_RESIZE_IMAGE_EXACT,
										false,
										true
									);
									$previewImageUrl = $imageResized["src"];
								}
								else
								{
									$previewImageUrl = false;
								}

								$fileExtension = GetFileExtension($arFile["FILE_NAME"]);

								$fileData = array(
									'type' => $fileExtension,
									'ufCode' => $ufCode,
									'id' => $arWDFile["ID"],
									'extension' => $fileExtension,
									'name' => $arFile["FILE_NAME"],
									'url' => $arFile["SRC"],
								);

								if ($previewImageUrl)
								{
									$fileData['previewImageUrl'] = CHTTP::URN2URI($previewImageUrl);
								}

								$arFileData[] = $fileData;
							}
						}
					}
				}
			}
			else // get just files
			{
				$dbRes = CFile::GetList(
					array(),
					array(
						"@ID" => implode(",", $arUF[$ufCode]["VALUE"])
					)
				);

				while ($arFile = $dbRes->GetNext())
				{
					if (CFile::IsImage($arFile["FILE_NAME"], $arFile["CONTENT_TYPE"]))
					{
						$imageResized = CFile::ResizeImageGet(
							$arFile["ID"],
							array(
								"width" => 144,
								"height" => 144
							),
							BX_RESIZE_IMAGE_EXACT,
							false,
							true
						);
						$previewImageUrl = $imageResized["src"];
					}
					else
					{
						$previewImageUrl = false;
					}

					$fileExtension = GetFileExtension($arFile["FILE_NAME"]);

					$fileData = array(
						'type' => $fileExtension,
						'ufCode' => $ufCode,
						'id' => $arFile["ID"],
						'extension' => $fileExtension,
						'name' => $arFile["FILE_NAME"],
						'downloadUrl' => $arFile["SRC"],
					);

					if ($previewImageUrl)
					{
						$fileData['previewImageUrl'] = CHTTP::URN2URI($previewImageUrl);
					}

					$arFileData[] = $fileData;
				}
			}
		}

		return $arFileData;
	}

	public static function mobileDiskGetIconByFilename($name)
	{
		if(CFile::isImage($name))
		{
			return 'img.png';
		}
		$icons = array(
			'pdf' => 'pdf.png',
			'doc' => 'doc.png',
			'docx' => 'doc.png',
			'ppt' => 'ppt.png',
			'pptx' => 'ppt.png',
			'rar' => 'rar.png',
			'xls' => 'xls.png',
			'xlsx' => 'xls.png',
			'zip' => 'zip.png',
		);
		$ext = strtolower(getFileExtension($name));

		return isset($icons[$ext]) ? $icons[$ext] : 'blank.png';
	}

	public static function getDeviceResizeWidth()
	{
		$max_dimension = false;

		if (CMobile::getInstance()->getApiVersion() > 1)
		{
			$max_dimension = max(array(intval(CMobile::getInstance()->getDevicewidth()), intval(CMobile::getInstance()->getDeviceheight())));

			if ($max_dimension < 650)
			{
				$max_dimension = 650;
			}
			elseif ($max_dimension < 1300)
			{
				$max_dimension = 1300;
			}
			else
			{
				$max_dimension = 2050;
			}
		}

		return $max_dimension;
	}
}
?>