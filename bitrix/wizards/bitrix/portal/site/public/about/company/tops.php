<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/about/company/tops.php");
$APPLICATION->SetTitle(GetMessage("ABOUT_TITLE"));
?>
<p>
	<table cellspacing="0" cellpadding="5" width="650" border="0">
		<tbody>
			<tr><td valign="top"><img height="200" src="<?=GetMessage("ABOUT_TOP1_IMG", array("#SITE#" => "#SITE_DIR#"))?>" width="154" /></td><td valign="top">
			 	<?=GetMessage("ABOUT_TOP1_INFO")?>
			</td></tr>
		</tbody>
	</table>
</p>

<p>
	<table cellspacing="0" cellpadding="5" width="650" border="0">
		<tbody>
			<tr><td valign="top"><img height="200" src="<?=GetMessage("ABOUT_TOP2_IMG", array("#SITE#" => "#SITE_DIR#"))?>" width="154" /></td><td valign="top">
				<?=GetMessage("ABOUT_TOP2_INFO")?>
			</td></tr>
		</tbody>
	</table>
</p>

<p>
	<table cellspacing="0" cellpadding="5" width="650" border="0">
		<tbody>
			<tr><td valign="top"><img height="200" src="<?=GetMessage("ABOUT_TOP3_IMG", array("#SITE#" => "#SITE_DIR#"))?>" width="154" /></td><td valign="top">
				<?=GetMessage("ABOUT_TOP3_INFO")?>
			</td></tr>
		</tbody>
	</table>
</p>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>