<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
	
if(!CModule::IncludeModule("xdimport")) 
	return;		

$strWarning = "";
$bVarsFromForm = false;

$ob = new CXDILFScheme;

$rsXDILFScheme = CXDILFScheme::GetList(array(), array("TYPE" => "RSS"));
$arXDILFScheme = $rsXDILFScheme->Fetch();
	 
if (empty($arXDILFScheme))
{

	switch(LANGUAGE_ID)
	{
		case "ru":
			$host = "www.1c-bitrix.ru";
			$page = "/upload/xdimport/xdimport_rss_ru.xml";
			break;
		case "de":
			$host = "www.bitrix.de";
			$page = "/upload/xdimport/xdimport_rss_de.xml";
			break;
		default:
			$host = "www.bitrixsoft.com";
			$page = "/upload/xdimport/xdimport_rss_en.xml";
			break;
	}

	$arFields = array(
		"ACTIVE" => "Y",
		"ENABLE_COMMENTS" => "Y",
		"SORT" => "100",
		"NAME" => GetMessage("XDI_SCHEME_NAME"),
		"TYPE" => "RSS",
		"LID" => WIZARD_SITE_ID,
		"DAYS_OF_WEEK" => "1,2,3,4,5,6,7",
		"TIMES_OF_DAY" => "8:00",
		"ENTITY_TYPE" => "P",
		"EVENT_ID" => "data",
		"HOST" => $host,
		"PAGE" => $page,
		"LAST_EXECUTED" => ConvertTimeStamp(time()-86400, "FULL")
	);

	$res = $ob->Add($arFields);
	if ($res > 0)
		$res = $ob->Update($res, array("ENTITY_ID" => $res));
	
	if ($res > 0)
	{
		$obSchemeRights = new CXDILFSchemeRights();
		$obSchemeRights->Set(
			$res, 
			array("U" => array(1)), 
			array(
				"ENTITY_TYPE" => SONET_SUBSCRIBE_ENTITY_PROVIDER,
				"ENTITY_ID" => $res,
				"EVENT_ID" => "data"
			)
		);
	}

}

?>