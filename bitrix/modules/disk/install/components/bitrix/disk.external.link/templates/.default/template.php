<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var \Bitrix\Disk\Internals\BaseComponent $component */
use Bitrix\Main\Localization\Loc;

switch(strtolower(LANGUAGE_ID))
{
	case 'en':
	case 'de':
	case 'ru':
	case 'ua':
		$langForBanner = strtolower(LANGUAGE_ID);
		break;
	default:
		$langForBanner = Loc::getDefaultLang(LANGUAGE_ID);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="windows-1251">
	<title><?= Loc::getMessage('DISK_EXT_LINK_TITLE') ?></title>
	<link rel="stylesheet" href="<?= $this->getFolder() ?>/style.css">
</head>
<body>

	<div class="bx-shared-wrap">

		<div class="bx-shared-header">
			<div class="bx-shared-logo">
				<?= Loc::getMessage('DISK_EXT_LINK_B24') ?>
			</div>
		</div>
<? if(!($arResult['PROTECTED_BY_PASSWORD']) || $arResult['VALID_PASSWORD']){ ?>
		<div class="bx-shared-body">
			<table class="bx-shared-body-container">
				<tr>
					<td class="bx-shared-body-previewblock">
					<? if($arResult['FILE']['IS_IMAGE']) { ?>
						<div class="bx-shared-preview-images">
							<a href="<?= $arResult['FILE']['SHOW_FILE_URL'] ?>" target="_blank"><img src="<?= $arResult['FILE']['SHOW_PREVIEW_URL'] ?>" alt="<?= htmlspecialcharsbx($arResult['FILE']['NAME']) ?>" title="<?= htmlspecialcharsbx($arResult['FILE']['NAME']) ?>"></a>
						</div>
					<?  } else { ?>
						<div class="bx-file-icon-container-big <?= $arResult['FILE']['ICON_CLASS'] ?>">
							<div class="bx-file-icon-cover">
								<div class="bx-file-icon-corner"></div>
								<div class="bx-file-icon-corner-fix"></div>
								<div class="bx-file-icon-images"></div>
							</div>
							<div class="bx-file-icon-label"></div>
						</div>
					<?  } ?>
					</td>
					<td class="bx-shared-body-fileinfoblock">
						<h1 class="bx-shared-body-filename"><?= htmlspecialcharsbx($arResult['FILE']['NAME']) ?></h1>
						<table>
							<tbody>
								<tr>
									<td class="bx-shared-body-fileinfo-param"><?= Loc::getMessage('DISK_EXT_LINK_FILE_SIZE') ?>:</td>
									<td class="bx-shared-body-fileinfo-value"><?= CFile::formatSize($arResult['FILE']['SIZE']) ?></td>
								</tr>
								<tr>
									<td class="bx-shared-body-fileinfo-param"><?= Loc::getMessage('DISK_EXT_LINK_FILE_UPDATE_TIME') ?>:</td>
									<td class="bx-shared-body-fileinfo-value"><?= $arResult['FILE']['UPDATE_TIME'] ?></td>
								</tr>
								<tr class="bx-shared-body-fileinfo-buttons first">
									<td colspan="2">
										<a class="bx-disk-btn bx-disk-btn-big bx-disk-btn-green" href="<?= $arResult['FILE']['DOWNLOAD_URL'] ?>"><?= Loc::getMessage('DISK_EXT_LINK_FILE_DOWNLOAD') ?></a>
									</td>
								</tr>
								<tr class="bx-shared-body-fileinfo-buttons ">
									<td colspan="2">

										<div class="bx-disk-sidebar-shared-title"><?= Loc::getMessage('DISK_EXT_LINK_FILE_COPY_LINK') ?></div>
										<div class="bx-disk-sidebar-shared-inlink-container">
											<div class="bx-disk-sidebar-shared-inlink-input-container">
												<input id="external-link-copy" class="bx-disk-sidebar-shared-inlink-input" value="<?= $arResult['FILE']['VIEW_URL'] ?>" type="text">
											</div>
										</div>
									</td>
								</tr>
							</tbody>
						</table>
					</td>
				</tr>
			</table>
		</div>
<? } elseif($arResult['PROTECTED_BY_PASSWORD']){ ?>
		<div class="bx-shared-body">
			<form id="form-pass" action="<?= $arResult['FILE']['VIEW_FULL_URL']?>" method="POST">
				<div class="bx-disk-pass-popup-wrap">
					<div class="bx-disk-popup-content">
						<div class="bx-disk-popup-content-inner">
							<div class="bx-disk-pass-popup-title"><?= Loc::getMessage('DISK_EXT_LINK_PROTECT_BY_PASSWORD') ?></div>
							<div class="bx-disk-pass-popup-title-descript"><?= Loc::getMessage('DISK_EXT_LINK_PROTECT_BY_PASSWORD_DESCR') ?></div>
							<label class="bx-disk-popup-label"><?= Loc::getMessage('DISK_EXT_LINK_LABEL_PASSWORD') ?>:</label>
							<input id="bx-disk-popup-input-pass" class="bx-disk-popup-input" name="PASSWORD" type="password">
						</div>
					</div>
					<div class="bx-disk-popup-buttons">
						<a onclick="document.getElementById('form-pass').submit();" class="bx-disk-btn bx-disk-btn-big bx-disk-btn-green"><?= Loc::getMessage('DISK_EXT_LINK_LABEL_BTN') ?></a>
					</div>
				</div>
			</form>
		</div>
<? } ?>

		<?php if(isModuleInstalled('bitrix24')) { ?>
			<div class="banner_b24" style="">
				<a target="_blank" href="<?= Loc::getMessage('DISK_EXT_LINK_B24_ADV_CREATE_LINK_HREF') ?>" class="banner-b24-link-container">
					<span class="banner-b24-link-container-cyrcle-logo <?= $langForBanner ?>"></span>
					<span class="banner-b24-link-container-cyrcle-desc"><?= Loc::getMessage('DISK_EXT_LINK_B24_ADV_TEXT') ?></span>
					<span class="banner-b24-link-container-cyrcle-title l1"><span><?= Loc::getMessage('DISK_EXT_LINK_B24_ADV_1') ?></span></span>
					<span class="banner-b24-link-container-cyrcle-title l2"><span><?= Loc::getMessage('DISK_EXT_LINK_B24_ADV_2') ?></span></span>
					<span class="banner-b24-link-container-cyrcle-title l3"><span><?= Loc::getMessage('DISK_EXT_LINK_B24_ADV_3') ?></span></span>
					<span class="banner-b24-link-container-cyrcle-title l4"><span><?= Loc::getMessage('DISK_EXT_LINK_B24_ADV_4') ?></span></span>
					<span class="banner-b24-link-container-cyrcle-title l5"><span><?= Loc::getMessage('DISK_EXT_LINK_B24_ADV_5') ?></span></span>
					<span class="banner-b24-link-container-cyrcle-title l6"><span><?= Loc::getMessage('DISK_EXT_LINK_B24_ADV_6') ?></span></span>
					<span class="banner-b24-link-container-cyrcle-button"><span><?= Loc::getMessage('DISK_EXT_LINK_B24_ADV_CREATE_LINK_TEXT') ?></span></span>
				</a>
			</div>
		<?php } ?>
	</div>
</body>
</html>