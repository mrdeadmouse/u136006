<?
if($INCLUDE_FROM_CACHE!='Y')return false;
$datecreate = '001452277954';
$dateexpire = '001452281554';
$ser_content = 'a:2:{s:7:"CONTENT";s:0:"";s:4:"VARS";a:2:{s:2:"co";a:3:{i:0;a:3:{s:9:"CONDITION";s:33:"CSite::InDir(\'/extranet/mobile/\')";s:8:"TEMPLATE";s:10:"mobile_app";s:7:"SITE_ID";s:2:"co";}i:1;a:3:{s:9:"CONDITION";s:110:"!$GLOBALS[\'USER\']->IsAuthorized() && (!isset($_SERVER[\'REMOTE_USER\']) || strlen($_SERVER[\'REMOTE_USER\']) <= 0)";s:8:"TEMPLATE";s:5:"login";s:7:"SITE_ID";s:2:"co";}i:2;a:3:{s:9:"CONDITION";s:0:"";s:8:"TEMPLATE";s:8:"bitrix24";s:7:"SITE_ID";s:2:"co";}}s:2:"s1";a:5:{i:0;a:3:{s:9:"CONDITION";s:29:"CSite::InDir(\'/desktop_app/\')";s:8:"TEMPLATE";s:11:"desktop_app";s:7:"SITE_ID";s:2:"s1";}i:1;a:3:{s:9:"CONDITION";s:24:"CSite::InDir(\'/mobile/\')";s:8:"TEMPLATE";s:10:"mobile_app";s:7:"SITE_ID";s:2:"s1";}i:2;a:3:{s:9:"CONDITION";s:41:"CSite::InDir(\'/services/learning/course\')";s:8:"TEMPLATE";s:8:"learning";s:7:"SITE_ID";s:2:"s1";}i:3;a:3:{s:9:"CONDITION";s:64:"!$GLOBALS[\'USER\']->IsAuthorized() && $_SERVER[\'REMOTE_USER\']==\'\'";s:8:"TEMPLATE";s:5:"login";s:7:"SITE_ID";s:2:"s1";}i:4;a:3:{s:9:"CONDITION";s:0:"";s:8:"TEMPLATE";s:8:"bitrix24";s:7:"SITE_ID";s:2:"s1";}}}}';
return true;
?>