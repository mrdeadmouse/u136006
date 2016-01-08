<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php"); 

$url = substr($_SERVER['REQUEST_URI'], strlen('/bitrix/tools/ws_contacts/'));

/*
if (preg_match('/\/Attachments\/([\d]*)\/ContactPicture\.jpg/i', $url, $matches))
{
	$dbUser = CUser::GetById($matches[1]);
	if (($arUser = $dbUser->Fetch()) && $arUser['PERSONAL_PHOTO'] > 0)
	{
		Header('HTTP/1.0 200 OK');
		Header('Content-Type: image/jpg');
		
		readfile($_SERVER['DOCUMENT_ROOT'].CFile::GetPath($arUser['PERSONAL_PHOTO']));
		die();
	}
}
*/

$matches = array();
if (preg_match_all("/[\w]+.aspx\?ID=([\d]+)(.*)/i", $url, $matches))
{
	if ($matches[1][0])
	{
		$url = str_replace('#USER_ID#', intval($matches[1][0]), COption::GetOptionString('intranet', 'path_user', '/company/personal/user/#USER_ID#/'));
	}
	else
	{
		$url = str_replace($matches[0][0], '', $url);
	}
}

$url = str_replace('.php/', '.php', $url);
if (substr($url, 0, 1) != '/') $url = '/'.$url;

LocalRedirect($url);
die();
?>