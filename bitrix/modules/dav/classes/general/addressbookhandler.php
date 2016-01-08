<?
if (!CModule::IncludeModule("intranet"))
	return;

class CDavAddressbookHandler
	extends CDavGroupdavHandler
{
	public function __construct($groupdav, $app)
	{
		parent::__construct($groupdav, $app);
	}

	protected function GetMethodMinimumPrivilege($method)
	{
		static $arMethodMinimumPrivilegeMap = array(
			'GET' => 'DAV:read',
			'PUT' => 'DAV:write',
			'DELETE' => 'DAV:write',
		);
		return $arMethodMinimumPrivilegeMap[$method];
	}

	public function GetCollectionId($siteId, $account, $arPath)
	{
		return array($siteId);
	}

	private function GetPath($contact)
	{
		$id = (is_numeric($contact) ? $contact : $contact["ID"]);
		return $id.'.vcf';
	}

	public function GetHomeCollectionUrl($siteId, $account, $arPath)
	{
		if (is_null($siteId))
			return "";

		$url = "/".$siteId;

		if (is_null($account))		//	/addressbook/12/
		{
			if (is_null($arPath) || count($arPath) == 0)
				return "";

			return $url."/addressbook/".$arPath[0]."/";
		}

		$arAccount = CDavAccount::GetAccountById($account);

		if (is_null($arAccount))
			return "";

		return $url."/".$arAccount["CODE"]."/addressbook/";
	}

	public function CheckPrivilegesByPath($testPrivileges, $principal, $siteId, $account, $arPath)
	{
		$collectionId = $this->GetCollectionId($siteId, $account, $arPath);
		if ($collectionId == null)
			return false;

		return $this->CheckPrivileges($testPrivileges, $principal, $collectionId);
	}

	public function CheckPrivileges($testPrivileges, $principal, $collectionId)
	{
		if (is_object($principal) && ($principal instanceof CDavPrincipal))
			$principal = $principal->Id();

		if (!is_numeric($principal))
			return false;

		$principal = IntVal($principal);

		if (!is_array($collectionId))
			$collectionId = array($collectionId);
		$collectionIdNorm = implode("-", $collectionId);

		static $arCollectionPrivilegesCache = array();
		if (!isset($arCollectionPrivilegesCache[$collectionIdNorm][$principal]))
		{
			$arPriv = array();

			$arPriv = array('DAV::read');
			/*$arPrivOrig = CEventCalendar::GetUserPermissionsForCalendar($calendarId, $principal);

			if ($arPrivOrig['bAccess'])
			{
				$arPriv[] = 'urn:ietf:params:xml:ns:caldav:read-free-busy';
				if (!isset($arPrivOrig['privateStatus']) || $arPrivOrig['privateStatus'] != 'time')
					$arPriv[] = 'DAV::read';

				if (!$arPrivOrig['bReadOnly'])
				{
					$arPriv[] = 'DAV:write';
					$arPriv[] = 'DAV:bind';
					$arPriv[] = 'DAV:unbind';
					$arPriv[] = 'DAV:write-properties';
					$arPriv[] = 'DAV:write-content';
				}
			}*/

			$arCollectionPrivilegesCache[$collectionIdNorm][$principal] = CDav::PackPrivileges($arPriv);
		}

		$testPrivilegesBits = CDav::PackPrivileges($testPrivileges);

		return ($arCollectionPrivilegesCache[$collectionIdNorm][$principal] & $testPrivilegesBits) > 0;
	}

	public function GetCollectionProperties(CDavResource $resource, $siteId, $account = null, $currentApplication = null, $arPath = null, $options = 0)
	{
		$request = $this->groupdav->GetRequest();
		$currentPrincipal = $request->GetPrincipal();

		$homeUrl = $this->GetHomeCollectionUrl($siteId, $account, $arPath);

		$resource->AddProperty('addressbook-home-set', array('href', $request->GetBaseUri().$homeUrl), CDavGroupDav::CARDDAV);
		//$resource->AddProperty('calendar-home-set', array('href', $request->GetBaseUri()."/calendar/"), CDavGroupDav::CALDAV);

		if ($currentApplication == "addressbook")
		{
			$this->GetAddressbookProperties($resource, $siteId, $account, $arPath, $options);
		}
	}

	public function GetAddressbookProperties(CDavResource $resource, $siteId, $account = null, $arPath = null, $options = 0)
	{
		$resource->AddProperty('resourcetype',
			array(
				array('collection', ''),
				array('vcard-collection', '', CDavGroupDav::GROUPDAV),
				array('addressbook', '', CDavGroupDav::CARDDAV),
			)
		);
		$resource->AddProperty('component-set', 'VCARD', CDavGroupDav::GROUPDAV);

		$resource->AddProperty('supported-report-set', array(
			array('supported-report',
				array(CDavResource::MakeProp('report', array(CDavResource::MakeProp('addressbook-query', '', CDavGroupDav::CARDDAV)))),
			),
			array('supported-report',
				array(CDavResource::MakeProp('report', array(CDavResource::MakeProp('addressbook-multiget', '', CDavGroupDav::CARDDAV))))
			))
		);

		$resource->AddProperty('getctag', $this->GetCTag($siteId, $account, $arPath), CDavGroupDav::CALENDARSERVER);

		$arAccount = null;
		if ($account != null)
		{
			$arAccount = CDavAccount::GetAccountById($account);

			$resource->AddProperty('addressbook-description', $arAccount["NAME"], CDavGroupDav::CARDDAV);
		}
	}

	public function Propfind(&$arResources, $siteId, $account, $arPath, $id = null)
	{
		$collectionId = $this->GetCollectionId($siteId, $account, $arPath);
		if ($collectionId == null)
			return '404 Not Found';

		$request = $this->groupdav->GetRequest();
		$currentPrincipal = $request->GetPrincipal();

		if (!$this->CheckPrivileges('DAV:read', $currentPrincipal, $collectionId))
			return '403 Forbidden';

		$requestDocument = $request->GetXmlDocument();

		$path = CDav::CheckIfRightSlashAdded($request->GetPath());

		$bAddressData = (count($requestDocument->GetPath('/*/DAV::allprop')) > 0);
		if (!$bAddressData || $requestDocument->GetRoot()->GetXmlns() != CDavGroupDav::CARDDAV)
		{
			$arProp = $requestDocument->GetPath('/*/DAV::prop/*');
			foreach ($arProp as $prop)
			{
				if ($prop->GetTag() == 'address-data')
				{
					$bAddressData = true;
					break;
				}
			}
		}

		$arFilter = array();
		if (($id || $requestDocument->GetRoot() && $requestDocument->GetRoot()->GetTag() != 'propfind') && !$this->PrepareFilters($arFilter, $requestDocument, $id))
			return false;

		$arContacts = CDavAccount::GetAddressbookContactsList($collectionId, $arFilter);
		foreach ($arContacts as $contact)
		{
			$resource = new CDavResource($path.$this->GetPath($contact));

			$resource->AddProperty('getetag', $this->GetETag($collectionId, $contact));
			$resource->AddProperty('getcontenttype', 'text/vcard');
			$resource->AddProperty('getlastmodified', MakeTimeStamp($contact['TIMESTAMP_X']));
			$resource->AddProperty('resourcetype', '');

			if ($bAddressData)
			{
				$content = $this->GetVCardContent($contact);
				$resource->AddProperty('getcontentlength', strlen($content));
				$resource->AddProperty('address-data', $content, CDavGroupDav::CARDDAV);
			}
			else
			{
				$resource->AddProperty('getcontentlength', "");
			}

			$arResources[] = $resource;
		}

		return true;
	}

	private function PrepareFilters(&$arFilter, $requestDocument, $id)
	{
		// Filter

		if ($id)
		{
			if (is_numeric($id))
				$arFilter["ID"] = intval($id);
			else
				$arFilter['XML_ID'] = basename($id, '.vcf');
		}
		elseif ($requestDocument->GetRoot()->GetTag() == 'addressbook-multiget')
		{
			$arIds = array();
			$arXmlIds = array();

			$arProp = $requestDocument->GetPath('/addressbook-multiget/DAV::href');
			foreach ($arProp as $prop)
			{
				$parts = explode('/', $prop->GetContent());
				if (!($idTmp = basename(array_pop($parts), '.vcf')))
					continue;

				if (is_numeric($idTmp))
					$arIds[] = $idTmp;
				else
					$arXmlIds[] = $idTmp;
			}

			if ($arIds)
				$arFilter["ID"] = (count($arIds) > 1 ? $arIds : $arIds[0]);
			if ($arXmlIds)
				$arFilter["XML_ID"] = (count($arXmlIds) > 1 ? $arXmlIds : $arXmlIds[0]);

			if (is_array($arFilter['ID']))
				$arFilter['ID'] = implode('|', array_unique($arFilter['ID']));
		}

		return true;
	}

	public function GetCTag($siteId, $account, $arPath)
	{
		$collectionId = $this->GetCollectionId($siteId, $account, $arPath);
		if ($collectionId == null)
			return null;

		$label = CDavAccount::GetAddressbookModificationLabel($collectionId);
		$label = MakeTimeStamp($label);

		return 'BX:'.$label;
	}

	private function GetVCardContent(array $contact)
	{
		$arVCardContact = array(
			"TYPE" => "VCARD",
			"VERSION" => "3.0",
			"N" => $contact["LAST_NAME"].";".$contact["NAME"].";".$contact["SECOND_NAME"].";;",
			"FN" => $contact["NAME"].($contact["SECOND_NAME"] ? " ".$contact["SECOND_NAME"] : "")." ".$contact["LAST_NAME"],
			"EMAIL" => array(
				"VALUE" => $contact["EMAIL"],
				"PARAMETERS" => array("TYPE" => "INTERNET")
			),
			"REV" => date("Ymd\\THis\\Z", MakeTimeStamp($contact["TIMESTAMP_X"])),
			"UID" => $contact["ID"],
		);

		if (intval($contact["PERSONAL_BIRTHDAY"]) > 0)
			$arVCardContact["BDAY"] = date("Y-m-d", MakeTimeStamp($contact["PERSONAL_BIRTHDAY"]));

		if (strlen($contact["WORK_PHONE"]) > 0)
			$arVCardContact["TEL"][] = array(
				"VALUE" => $contact["WORK_PHONE"],
				"PARAMETERS" => array("TYPE" => "WORK")
			);
		if (strlen($contact["PERSONAL_MOBILE"]) > 0)
			$arVCardContact["TEL"][] = array(
				"VALUE" => $contact["PERSONAL_MOBILE"],
				"PARAMETERS" => array("TYPE" => "CELL")
			);
		if (strlen($contact["PERSONAL_PHONE"]) > 0)
			$arVCardContact["TEL"][] = array(
				"VALUE" => $contact["PERSONAL_PHONE"],
				"PARAMETERS" => array("TYPE" => "HOME")
			);

		if (strlen($contact["WORK_COMPANY"]) > 0)
			$arVCardContact["ORG"] = $contact["WORK_COMPANY"];
		if (strlen($contact["WORK_POSITION"]) > 0)
			$arVCardContact["TITLE"] = $contact["WORK_POSITION"];

		if (strlen($contact["WORK_WWW"]) > 0)
			$arVCardContact["URL"][] = array(
				"VALUE" => $contact["WORK_WWW"],
				"PARAMETERS" => array("TYPE" => "WORK")
			);
		if (strlen($contact["PERSONAL_WWW"]) > 0)
			$arVCardContact["URL"][] = array(
				"VALUE" => $contact["PERSONAL_WWW"],
				"PARAMETERS" => array("TYPE" => "HOME")
			);

		if (strlen($contact["PERSONAL_STREET"]) > 0)
			$arVCardContact["ADR"][] = array(
				"VALUE" => ";;".$contact["PERSONAL_STREET"].";".$contact["PERSONAL_CITY"].";".$contact["PERSONAL_STATE"].";".$contact["PERSONAL_ZIP"].";".GetCountryByID($contact["PERSONAL_COUNTRY"])."",
				"PARAMETERS" => array("TYPE" => "HOME")
			);
		if (strlen($contact["WORK_STREET"]) > 0)
			$arVCardContact["ADR"][] = array(
				"VALUE" => ";;".$contact["WORK_STREET"].";".$contact["WORK_CITY"].";".$contact["WORK_STATE"].";".$contact["WORK_ZIP"].";".GetCountryByID($contact["WORK_COUNTRY"])."",
				"PARAMETERS" => array("TYPE" => "WORK")
			);

		if (intval($contact["PERSONAL_PHOTO"]) > 0)
		{
			$arTempFile = CFile::ResizeImageGet(
				$contact["PERSONAL_PHOTO"],
				array("width" => \Bitrix\Main\Config\Option::get("dav", "vcard_image_width", 400), "height" => \Bitrix\Main\Config\Option::get("dav", "vcard_image_width", 400)),
				BX_RESIZE_IMAGE_PROPORTIONAL,
				false,
				false,
				false,
				\Bitrix\Main\Config\Option::get("dav", "vcard_image_quality", 60)
			);

			if ($arTempFile)
			{
				$cnt = file_get_contents($_SERVER["DOCUMENT_ROOT"].$arTempFile['src']);
				if (!empty($cnt))
				{
					$arImageTypes = array(
						IMAGETYPE_JPEG => 'JPEG',
						IMAGETYPE_GIF  => 'GIF',
						IMAGETYPE_PNG  => 'PNG'
					);

					$imageType = "JPEG";
					if ($imageInfo = CFile::GetImageSize($_SERVER["DOCUMENT_ROOT"].$arTempFile['src']) && isset($arImageTypes[$imageInfo[2]]))
						$imageType = $arImageTypes[$imageInfo[2]];

					$arVCardContact["PHOTO"] = array(
						"VALUE" => /*chunk_split(*/base64_encode($cnt)/*)*/,
						"PARAMETERS" => array("ENCODING" => "BASE64", "TYPE" => $imageType)
					);
				}
			}
		}

		$cal = new CDavICalendarComponent($arVCardContact);
		return $cal->Render();
	}


	protected function GetETag($collectionId, $contact)
	{
		if (!is_array($contact))
		{
			$request = $this->groupdav->GetRequest();
			if (!$this->CheckPrivileges('DAV:read', $request->GetPrincipal(), $collectionId))
				return false;

			$contact = $this->Read($collectionId, $contact);
		}

		return 'BX:'.$contact['ID'].':'.MakeTimeStamp($contact['TIMESTAMP_X']);
	}

	// return array/boolean array with entry, false if no read rights, null if $id does not exist
	public function Read($collectionId, $id)
	{
		$arContacts = CDavAccount::GetAddressbookContactsList($collectionId, array("ID" => $id));
		if (count($arContacts) <= 0)
			return null;

		$request = $this->groupdav->GetRequest();
		if (!$this->CheckPrivileges('DAV:read', $request->GetPrincipal(), $collectionId))
			return false;

		$contact = $arContacts[0];

		return $contact;
	}

	public function Get(&$arResult, $id, $siteId, $account, $arPath)
	{
		$collectionId = $this->GetCollectionId($siteId, $account, $arPath);
		if ($collectionId == null)
			return '404 Not Found';

		$request = $this->groupdav->GetRequest();

		$oldCard = $this->GetEntry('GET', $id, $collectionId);
		if (is_null($oldCard) || !is_array($oldCard))
			return $oldCard;

		$arResult['data'] = $this->groupdav->GetResponse()->Encode($this->GetVCardContent($oldCard));
		$arResult['mimetype'] = 'text/x-vcard; charset=utf-8';
		$arResult['headers'] = array('Content-Encoding: identity', 'ETag: '.$this->GetETag($collectionId, $oldCard));

		return true;
	}

	public function Put($id, $siteId, $account, $arPath)
	{
		$collectionId = $this->GetCollectionId($siteId, $account, $arPath);
		if ($collectionId == null)
			return '404 Not Found';

		$request = $this->groupdav->GetRequest();

		$oldCard = $this->GetEntry('PUT', $id, $collectionId);
		if (!is_null($oldCard) && !is_array($oldCard))
			return $oldCard;

		$charset = "utf-8";
		$arContentParameters = $request->GetContentParameters();

		if (!empty($arContentParameters['CONTENT_TYPE']))
		{
			$arContentType = explode(';', $arContentParameters['CONTENT_TYPE']);
			if (count($arContentType) > 1)
			{
				array_shift($arContentType);
				foreach ($arContentType as $attribute)
				{
					$attribute = trim($attribute);
					list($key, $value) = explode('=', $attribute);
					if (strtolower($key) == 'charset')
						$charset = strtolower($value);
				}
			}
		}

		$content = $request->GetRequestBody();
		$content = htmlspecialcharsback($content);

		if (is_array($oldCard))
			$contactId = $oldCard['ID'];
		else
			$contactId = 0;

		$cs = CDav::GetCharset($siteId);
		if (is_null($cs) || empty($cs))
			$cs = "utf-8";

		$content = $GLOBALS["APPLICATION"]->ConvertCharset($content, $charset, $cs);

		// Create record

		return "201 Created";
	}

	public function Delete($id, $siteId, $account, $arPath)
	{
		$collectionId = $this->GetCollectionId($siteId, $account, $arPath);
		if ($collectionId == null)
			return '404 Not Found';

		$request = $this->groupdav->GetRequest();

		$oldCard = $this->GetEntry('DELETE', $id, $collectionId);
		if (!is_array($oldCard))
			return $oldCard;

		// Delete

		return false;
	}

}
?>