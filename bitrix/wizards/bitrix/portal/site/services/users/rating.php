<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();
	
//	if (WIZARD_FIRST_INSTAL || WIZARD_INSTALL_DEMO_DATA)
//		$DB->Query("UPDATE b_user SET LAST_LOGIN = ".$DB->GetNowFunction());
		
	// set start position
	if ($bSetDefaultValue)
	{
		$arParams = array();
		$arParams["DEFAULT_CONFIG_NEW_USER"] = "Y";
		CRatings::SetAuthorityDefaultValue($arParams);
	}
		
	// recount ratings
	$rsData = CRatings::GetList(array('ID'=>'ASC'), array());
	while($arRes = $rsData->Fetch())
	{
		CRatings::Calculate($arRes['ID'], true);
	}

?>