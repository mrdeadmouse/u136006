<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?><?

/* @var \CMain $APPLICATION*/
$APPLICATION->SetTitle(GetMessage('MAIN_MAIL_UNSUBSCRIBE_TITLE'));

try
{
	$arTag = \Bitrix\Main\Mail\Tracking::parseSignedTag($_REQUEST['tag']);
	$arTag['IP'] = $_SERVER['REMOTE_ADDR'];

	$arResult = array();
	$arResult['FORM_URL'] = $APPLICATION->getCurPageParam("",array('success'));
	$arResult['LIST'] = \Bitrix\Main\Mail\Tracking::getSubscriptionList($arTag);
	$unsubscribeListFromForm = is_array($_POST['MAIN_MAIL_UNSUB']) ? $_POST['MAIN_MAIL_UNSUB'] : array();

	if($_SERVER['REQUEST_METHOD'] == 'POST' && array_key_exists('MAIN_MAIL_UNSUB_BUTTON', $_POST) && check_bitrix_sessid())
	{
		$arUnsubscribeList = array();
		foreach($arResult['LIST'] as $key => $unsubItem)
		{
			if(in_array($unsubItem['ID'], $unsubscribeListFromForm))
			{
				$arUnsubscribeList[] = $unsubItem['ID'];
				$arSubList[$key]['SELECTED'] = true;
			}
			else
			{
				$arResult['LIST'][$key]['SELECTED'] = false;
			}
		}

		if(!empty($arUnsubscribeList))
		{
			$arTag['FIELDS']['UNSUBSCRIBE_LIST'] = $arUnsubscribeList;
			$result = \Bitrix\Main\Mail\Tracking::unsubscribe($arTag);
			if ($result)
				$arResult['DATA_SAVED'] = 'Y';
			else
				$arResult['ERROR'] = GetMessage('MAIN_MAIL_UNSUBSCRIBE_ERROR_UNSUB');
		}
		else
			$arResult['ERROR'] = GetMessage('MAIN_MAIL_UNSUBSCRIBE_ERROR_NOT_SELECTED');
	}
}
catch (\Bitrix\Main\Security\Sign\BadSignatureException $exception)
{
	$arResult['ERROR'] = GetMessage('MAIN_MAIL_UNSUBSCRIBE_ERROR_SECURITY');
}

$this->IncludeComponentTemplate();