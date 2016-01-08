<?
define("NEED_AUTH", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
if (strlen($backurl)>0) LocalRedirect($backurl);
$APPLICATION->SetTitle("Authorization");
?>
<p class="notetext"><font >You have successfully registered and authorized.</font></p>
<p><a href="<?=SITE_DIR?>">Back to home page</a></p>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>