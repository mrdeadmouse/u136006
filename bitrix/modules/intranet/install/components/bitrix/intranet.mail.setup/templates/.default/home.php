<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$domainAdded = false;
foreach ($arParams['SERVICES'] as $id => $settings)
{
	if ($settings['type'] != 'imap')
	{
		if ($settings['type'] == 'controller' && $settings['name'] == 'bitrix24')
		{
			$b24Settings = $settings;
		}
		else if ($settings['type'] == 'domain' || $settings['type'] == 'crdomain')
		{
			$domainSettings = $settings;
			$domainStatus = isset($arParams['DOMAIN_STATUS']['stage']) ? $arParams['DOMAIN_STATUS']['stage'] : false;
			$domainAdded  = strtolower($domainStatus) == 'added';
		}
	}
}

?>

<? $isUserAdmin = $USER->isAdmin() || $USER->canDoOperation('bitrix24_config'); ?>

<? $showB24Block = IsModuleInstalled('bitrix24') && !empty($b24Settings) && !empty($arParams['CR_DOMAINS']); ?>
<? $showDomainBlock = IsModuleInstalled('bitrix24') || !empty($domainSettings) || in_array(LANGUAGE_ID, array('ru', 'ua')); ?>

<? $b24Mailbox = !empty($b24Settings) && !empty($arParams['MAILBOX']) && $arParams['MAILBOX']['SERVICE_ID'] == $b24Settings['id']; ?>
<? $domainMailbox = !empty($domainSettings) && !empty($arParams['MAILBOX']) && $arParams['MAILBOX']['SERVICE_ID'] == $domainSettings['id']; ?>
<? $imapMailbox = !empty($arParams['MAILBOX']) && $arParams['MAILBOX']['SERVER_TYPE'] == 'imap'; ?>

<?

if ($isUserAdmin && !empty($domainSettings) && !$domainAdded)
{
	$defaultBlock = 'domain';
}
else
{
	if ($b24Mailbox || !$domainAdded && !$domainMailbox && !$imapMailbox)
		$defaultBlock = 'bitrix24';
	if (!$b24Mailbox && (!$showB24Block && $showDomainBlock || $domainAdded) && !$imapMailbox)
		$defaultBlock = 'domain';
	if ($imapMailbox || !$showB24Block && !$showDomainBlock)
		$defaultBlock = 'imap';
}

?>

<div class="mail-set-wrap<? if ($showB24Block xor $showDomainBlock) { ?> mail-set-wrap-small<? } ?>"<? if (!$showB24Block && !$showDomainBlock) { ?> style="max-width: 1100px; "<? } ?>>
	<div class="mail-top-title-block"<? if (!$showB24Block && !$showDomainBlock) { ?> style="margin-bottom: 20px; "<? } ?>>
		<div class="mail-top-title-icon"></div>
		<? if ($showB24Block || $showDomainBlock) { ?>
		<?=GetMessage(IsModuleInstalled('bitrix24') ? 'INTR_MAIL_DESCR_B24' : 'INTR_MAIL_DESCR_BOX'); ?>
		<? } else { ?>
		<?=GetMessage(IsModuleInstalled('bitrix24') ? 'INTR_MAIL_HINT_B24' : 'INTR_MAIL_HINT_BOX'); ?>
		<? } ?>
	</div>
	<table class="mail-block-table" id="mail-block-table"<? if (!$showB24Block && !$showDomainBlock) { ?> style="display: none; "<? } ?>>
		<tr class="mail-block-top">
			<? if ($showB24Block) { ?>
			<td class="mail-block mail-block-b24<? if ($defaultBlock == 'bitrix24') { ?> mail-block-active<? } ?>" data-block="mail-set-first">
				<div class="mail-block-title">
					<span class="mail-block-title-at">@</span>
					<span class="mail-block-title-text"><?=reset($arParams['CR_DOMAINS']); ?></span>
				</div>
				<? if ($b24Mailbox) { ?>
				<div id="b24_block_descr_mailbox" class="mail-block-text">
					<?=str_replace('#EMAIL#', $arParams['MAILBOX']['LOGIN'], GetMessage('INTR_MAIL_B24_DESCR_MAILBOX')); ?>
				</div>
				<? } ?>
				<div id="b24_block_descr_nomailbox" class="mail-block-text"<? if ($b24Mailbox) { ?> style="display: none; "<? } ?>>
					<?=str_replace('#DOMAIN#', reset($arParams['CR_DOMAINS']), GetMessage('INTR_MAIL_B24_DESCR')); ?>
				</div>
			</td>
			<td class="mail-block-space"></td>
			<? } ?>
			<? if ($showDomainBlock) { ?>
			<td class="mail-block mail-block-own<? if ($defaultBlock == 'domain') { ?> mail-block-active<? } ?>" data-block="mail-set-second">
				<div class="mail-block-title">
					<? if (empty($domainSettings) || !$isUserAdmin && !$domainAdded) { ?>
					<span class="mail-block-title-icon"></span>
					<span class="mail-block-title-text"><?=GetMessage('INTR_MAIL_DOMAIN_TITLE'); ?></span>
					<? } else { ?>
					<span class="mail-block-title-at">@</span>
					<span class="mail-block-title-text"><?=$domainSettings['server']; ?></span>
					<? } ?>
				</div>
				<? if (empty($domainSettings) || !$isUserAdmin && !$domainAdded) { ?>
				<div class="mail-block-text">
					<?=GetMessage(IsModuleInstalled('bitrix24') ? 'INTR_MAIL_DOMAIN_DESCR_B24' : 'INTR_MAIL_DOMAIN_DESCR_BOX'); ?>
				</div>
				<? } else { ?>
				<? if ($domainMailbox && $domainAdded) { ?>
				<div id="domain_block_descr_mailbox" class="mail-block-text">
					<?=str_replace('#DOMAIN#', $domainSettings['server'], GetMessage(IsModuleInstalled('bitrix24') ? 'INTR_MAIL_DOMAIN_DESCR_B24_DOMAIN' : 'INTR_MAIL_DOMAIN_DESCR_BOX_DOMAIN')); ?>
					<br/><br/>
					<?=str_replace('#EMAIL#', $arParams['MAILBOX']['LOGIN'], GetMessage('INTR_MAIL_DOMAIN_DESCR_MAILBOX')); ?>
				</div>
				<? } ?>
				<div id="domain_block_descr_nomailbox" class="mail-block-text"<? if ($domainMailbox && $domainAdded) { ?> style="display: none; "<? } ?>>
					<?=str_replace('#DOMAIN#', $domainSettings['server'], GetMessage(IsModuleInstalled('bitrix24') ? 'INTR_MAIL_DOMAIN_DESCR_B24_DOMAIN' : 'INTR_MAIL_DOMAIN_DESCR_BOX_DOMAIN')); ?>
					<br/><br/>
					<? if ($domainAdded) { ?>
					<?=GetMessage('INTR_MAIL_DOMAIN_DESCR_NOMAILBOX'); ?>
					<? } else { ?>
					<?=GetMessage('INTR_MAIL_DOMAIN_DESCR_WAIT'); ?>
					<? } ?>
				</div>
				<? } ?>
			</td>
			<td class="mail-block-space"></td>
			<? } ?>
			<td class="mail-block mail-block-imap<? if ($defaultBlock == 'imap') { ?> mail-block-active<? } ?>" data-block="mail-set-third">
				<div class="mail-block-title">
					<span class="mail-block-title-icon"></span>
					<span class="mail-block-title-text"><?=GetMessage('INTR_MAIL_IMAP_TITLE'); ?></span>
				</div>
				<? if ($imapMailbox) { ?>
				<div id="imap_block_descr_mailbox" class="mail-block-text">
					<? if (strpos($arParams['MAILBOX']['LOGIN'], '@') !== false) { ?>
					<?=str_replace('#EMAIL#', $arParams['MAILBOX']['LOGIN'], GetMessage('INTR_MAIL_IMAP_DESCR_MAILBOX')); ?>
					<? } else { ?>
					<?=GetMessage('INTR_MAIL_IMAP_DESCR_MAILBOX_UN'); ?>
					<? } ?>
				</div>
				<? } ?>
				<div id="imap_block_descr_nomailbox" class="mail-block-text" style="<? if ($imapMailbox) { ?>display: none; <? } ?><? if (!$showB24Block && !$showDomainBlock) { ?>min-height: 0; <? } ?>">
					<?=GetMessage('INTR_MAIL_IMAP_DESCR'); ?>
					<? if ($showB24Block || $showDomainBlock) { ?>
					<img class="mail-block-icon-list" src="/bitrix/components/bitrix/intranet.mail.setup/templates/.default/images/<?=GetMessage('INTR_MAIL_IMAP_DESCR_ICONS'); ?>"/>
					<img class="mail-block-icon-list-colorless" src="/bitrix/components/bitrix/intranet.mail.setup/templates/.default/images/<?=GetMessage('INTR_MAIL_IMAP_DESCR_ICONS_CL'); ?>"/>
					<? } ?>
				</div>
			</td>
		</tr>
		<tr class="mail-block-bottom">
			<? if ($showB24Block) { ?>
			<td class="mail-block<? if ($defaultBlock == 'bitrix24') { ?> mail-block-active<? } ?>"  data-block="mail-set-first">
				<div class="mail-block-footer">
					<span class="mail-block-btn" id="mail-set-first-btn"><?=(GetMessage($b24Mailbox ? 'INTR_MAIL_SERVICETYPE_SETUP' : 'INTR_MAIL_SERVICETYPE_CHOOSE')); ?></span>
				</div>
			</td>
			<td class="mail-block-space"></td>
			<? } ?>
			<? if ($showDomainBlock) { ?>
			<td class="mail-block<? if ($defaultBlock == 'domain') { ?> mail-block-active<? } ?>" data-block="mail-set-second">
				<div class="mail-block-footer">
					<span class="mail-block-btn" id="mail-set-second-btn"><?=(GetMessage($domainMailbox ? 'INTR_MAIL_SERVICETYPE_SETUP' : 'INTR_MAIL_SERVICETYPE_CHOOSE')); ?></span>
				</div>
			</td>
			<td class="mail-block-space"></td>
			<? } ?>
			<td class="mail-block<? if ($defaultBlock == 'imap') { ?> mail-block-active<? } ?>" data-block="mail-set-third">
				<div class="mail-block-footer">
					<span class="mail-block-btn" id="mail-set-third-btn"<? if (!$showB24Block && !$showDomainBlock) { ?> style="position: absolute; visibility: hidden; "<? }?>><?=(GetMessage($imapMailbox ? 'INTR_MAIL_SERVICETYPE_SETUP' : 'INTR_MAIL_SERVICETYPE_CHOOSE')); ?></span>
				</div>
			</td>
		</tr>
	</table>
	<div class="mail-set-block-wrap" id="mail-set-block-wrap"<? if (!$showB24Block && !$showDomainBlock) { ?> style="margin-top: 20px; "<? } ?>>
		<div class="mail-set-block mail-set-block-active" id="mail-set-block">
			<? if ($showB24Block) { ?>
			<div id="mail-set-first" class="mail-set-first-wrap"<? if ($defaultBlock == 'bitrix24') { ?> style="display: block;"<? } ?>>
				<div class="mail-set-first">

					<? if ($b24Mailbox) { ?>
					<div id="b24_setup_form">

						<? $lastMailCheck = CUserOptions::GetOption('global', 'last_mail_check_'.SITE_ID, null); ?>
						<? $lastMailCheckSuccess = CUserOptions::GetOption('global', 'last_mail_check_success_'.SITE_ID, null); ?>

						<div class="mail-set-title">
							<?=str_replace('#EMAIL#', $arParams['MAILBOX']['LOGIN'], GetMessage('INTR_MAIL_MAILBOX_MANAGE')); ?>
						</div>
						<div name="post-dialog-alert" class="post-dialog-alert" style="display: none; ">
							<span class="post-dialog-alert-align"></span>
							<span class="post-dialog-alert-icon"></span>
							<span name="post-dialog-alert-text" class="post-dialog-alert-text"></span>
						</div>
						<div class="mail-set-item-block-wrap">
							<div class="mail-set-item-block-name"><?=GetMessage('INTR_MAIL_MAILBOX_STATUS'); ?></div>
							<div name="status-block" class="mail-set-item-block<? if (isset($lastMailCheckSuccess) && !$lastMailCheckSuccess) { ?> post-status-error<? } ?>">
								<div class="mail-set-item-block-r">
									<span id="b24_delete_form" class="webform-button webform-button-decline">
										<span class="webform-button-left"></span><span class="webform-button-text"><?=GetMessage('INTR_MAIL_MAILBOX_DELETE'); ?></span><span class="webform-button-right"></span>
									</span>&nbsp;
								</div>
								<div class="mail-set-item-block-l">
									<span name="status-text" class="post-dialog-stat-text">
										<? if (isset($lastMailCheck) && intval($lastMailCheck) > 0) { ?>
										<?=str_replace('#DATE#', FormatDate(
											array('s' => 'sago', 'i' => 'iago', 'H' => 'Hago', 'd' => 'dago', 'm' => 'mago', 'Y' => 'Yago'),
											intval($lastMailCheck)
										), GetMessage('INTR_MAIL_CHECK_TEXT')); ?>:
										<? } else { ?>
										<?=GetMessage('INTR_MAIL_CHECK_TEXT_NA'); ?>
										<? } ?>
									</span>
									<span name="status-alert" class="post-dialog-stat-alert">
									<? if (isset($lastMailCheckSuccess)) { ?>
										<? if ($lastMailCheckSuccess) { ?>
										<?=GetMessage('INTR_MAIL_CHECK_SUCCESS'); ?>
										<? } else { ?>
										<?=GetMessage('INTR_MAIL_CHECK_ERROR'); ?>
										<? } ?>
									<? } ?>
									</span>
									<span name="status-info" class="post-dialog-stat-info" style="display: none; "></span>
									<span id="b24_check_form" class="webform-button">
										<span class="webform-button-left"></span><span class="webform-button-text"><?=GetMessage('INTR_MAIL_CHECK'); ?></span><span class="webform-button-right"></span>
									</span>
								</div>
							</div>
						</div>
						<div class="mail-set-item-block-wrap mail-set-item-block-inp">
							<div class="mail-set-item-block-name"><?=GetMessage('INTR_MAIL_MAILBOX_PASSWORD_MANAGE'); ?></div>
							<div class="mail-set-item-block">
								<form id="b24_password_form">
									<? list($login, ) = explode('@', $arParams['MAILBOX']['LOGIN'], 2); ?>
									<input name="ID" type="hidden" value="<?=$arParams['MAILBOX']['ID']; ?>" />
									<input name="login" type="hidden" value="<?=$login; ?>" />
									<?=bitrix_sessid_post(); ?>
									<div class="mail-set-item-block-r">
										<span id="b24_password_save" name="password-save" class="webform-button webform-button-accept">
											<span class="webform-button-left"></span><span class="webform-button-text"><?=GetMessage('INTR_MAIL_MAILBOX_PASSWORD_SAVE'); ?></span><span class="webform-button-right"></span>
										</span>&nbsp;
									</div>
									<div class="mail-set-item-block-l">
										<div class="mail-set-item">
											<div class="mail-set-first-label"><?=GetMessage('INTR_MAIL_MAILBOX_PASSWORD'); ?></div>
											<input name="password" class="mail-set-inp" type="password"/>
											<div name="pass-hint" class="mail-inp-description"><?=GetMessage('INTR_MAIL_INP_PASS_SHORT'); ?></div>
										</div>
										<div class="mail-set-item">
											<div class="mail-set-first-label"><?=GetMessage('INTR_MAIL_MAILBOX_PASSWORD2'); ?></div>
											<input name="password2" class="mail-set-inp" type="password"/>
											<div name="pass2-hint" class="mail-inp-description"></div>
										</div>
									</div>
								</form>
							</div>
						</div>

					</div>
					<? } ?>

					<? if (!empty($arParams['MAILBOX']) && !$b24Mailbox) { ?>
					<div id="b24_block_replace_warning">
						<div class="mail-set-item-block mail-set-item-icon">
							<span class="mail-set-item-text">
								<? if (strpos($arParams['MAILBOX']['LOGIN'], '@') !== false) { ?>
								<?=str_replace('#EMAIL#', $arParams['MAILBOX']['LOGIN'], GetMessage('INTR_MAIL_REPLACE_WARNING')); ?>
								<? } else { ?>
								<?=GetMessage('INTR_MAIL_REPLACE_WARNING_UN'); ?>
								<? } ?>
							</span>
						</div>
						<br/><br/>
					</div>
					<? } ?>

					<form<? if ($b24Mailbox) { ?> style="display: none; "<? } ?> id="b24_create_form" name="settings_form" action="<?=POST_FORM_ACTION_URI; ?>" method="POST">
						<div name="post-dialog-alert" class="post-dialog-alert" style="display: none; ">
							<span class="post-dialog-alert-align"></span>
							<span class="post-dialog-alert-icon"></span>
							<span name="post-dialog-alert-text" class="post-dialog-alert-text"></span>
						</div>
						<input type="hidden" name="act" value="create">
						<input type="hidden" name="SERVICE" value="<?=$b24Settings['id']; ?>">
						<? if (!empty($arParams['MAILBOX'])) { ?>
						<input type="hidden" name="ID" value="<?=$arParams['MAILBOX']['ID']; ?>">
						<? } ?>
						<?=bitrix_sessid_post(); ?>
						<div class="mail-set-cont">
							<div class="mail-set-cont-left">
								<div class="mail-set-item">
									<div class="mail-set-first-label"><?=GetMessage('INTR_MAIL_INP_MB_NAME'); ?></div>
									<input name="login" class="mail-set-inp" type="text" autocomplete="off" />
									<? if (count($arParams['CR_DOMAINS']) == 1) { ?>
									<input type="hidden" name="domain" value="<?=reset($arParams['CR_DOMAINS']); ?>">
									<span class="mail-set-address">@<?=reset($arParams['CR_DOMAINS']); ?></span>
									<? } else {?>
									<select name="domain" class="mail-set-address mail-set-select">
										<? foreach ($arParams['CR_DOMAINS'] as $domain) { ?>
										<option value="<?=$domain; ?>">@<?=$domain; ?></option>
										<? } ?>
									</select>
									<? } ?>
									<div name="login-hint" class="mail-inp-description"></div>
								</div>
								<div name="bad-login-hint" style="z-index: 1000; position: absolute; display: none; left: 60px; ">
									<table class="popup-window popup-window-light" cellspacing="0">
										<tr class="popup-window-top-row">
											<td class="popup-window-left-column"><div class="popup-window-left-spacer"></div></td>
											<td class="popup-window-center-column"></td>
											<td class="popup-window-right-column"><div class="popup-window-right-spacer"></div></td>
										</tr>
										<tr class="popup-window-content-row">
											<td class="popup-window-left-column"></td>
											<td class="popup-window-center-column">
												<div class="popup-window-content" id="popup-window-content-input-alert-popup">
													<div id="mail-alert-popup-cont" class="mail-alert-popup-cont" style="display: block;">
														<div class="mail-alert-popup-text"><?=GetMessage('INTR_MAIL_INP_NAME_BAD_HINT'); ?></div>
													</div>
												</div>
											</td>
											<td class="popup-window-right-column"></td>
										</tr>
										<tr class="popup-window-bottom-row">
											<td class="popup-window-left-column"></td>
											<td class="popup-window-center-column"></td>
											<td class="popup-window-right-column"></td>
										</tr>
									</table>
									<div class="popup-window-light-angly popup-window-light-angly-top" style="left: 30px; margin-left: auto;"></div>
								</div>
								<div class="mail-set-item">
									<div class="mail-set-first-label"><?=GetMessage('INTR_MAIL_INP_MB_PASS'); ?></div>
									<input name="password" class="mail-set-inp" type="password" />
									<div name="pass-hint" class="mail-inp-description"><?=GetMessage('INTR_MAIL_INP_PASS_SHORT'); ?></div>
								</div>
								<div class="mail-set-item">
									<div class="mail-set-first-label"><?=GetMessage('INTR_MAIL_INP_PASS2'); ?></div>
									<input name="password2" class="mail-set-inp" type="password" />
									<div name="pass2-hint" class="mail-inp-description"></div>
								</div>
							</div>
							<div class="mail-set-cont-right">
								<div class="mail-set-second-info"><?=GetMessage('INTR_MAIL_B24_HELP'); ?></div>
							</div>
						</div>
						<div class="mail-set-footer">
							<a id="b24_create_save" name="create-save" class="webform-button webform-button-accept" href="#">
								<span class="webform-button-left"></span><span class="webform-button-text"><?=GetMessage('INTR_MAIL_INP_SAVE'); ?></span><span class="webform-button-right"></span>
							</a>
							<span id="b24_create_clear" class="mail-set-cancel-link"><?=GetMessage('INTR_MAIL_INP_CLEAR'); ?></span>
						</div>
						<input type="submit" style="position: absolute; visibility: hidden; ">
					</form>

				</div>
			</div>
			<? } ?>
			<? if ($showDomainBlock) { ?>
			<div id="mail-set-second" class="mail-set-second-wrap"<? if ($defaultBlock == 'domain') { ?> style="display: block;"<? } ?>>
				<div class="mail-set-second">

					<? if (!$isUserAdmin && !$domainAdded) { ?>
					<div style="text-align: center; "><?=GetMessage('INTR_MAIL_NODOMAIN_USER_INFO'); ?><br/><br/><br/></div>
					<? } ?>

					<? if ($isUserAdmin && empty($domainSettings)) { ?>

					<? if (IsModuleInstalled('bitrix24')) { ?>
					<form id="domain_form" name="domain_form" action="<?=POST_FORM_ACTION_URI; ?>" method="POST">
						<div name="post-dialog-alert" class="post-dialog-alert" style="display: none; ">
							<span class="post-dialog-alert-align"></span>
							<span class="post-dialog-alert-icon"></span>
							<span name="post-dialog-alert-text" class="post-dialog-alert-text"></span>
						</div>
						<input type="hidden" name="page" value="domain">
						<input type="hidden" name="act" value="create">
						<?=bitrix_sessid_post(); ?>
						<? if (in_array(LANGUAGE_ID, array('ru', 'ua'))) { ?>
						<div id="delegate-domain-block" class="mail-set-item-block-wrap">
							<div class="mail-set-item-block">
								<div class="mail-set-radio-item">
									<input type="radio" id="select-delegate" name="connect" value="0" class="mail-set-radio-inp" checked="checked">
									<label for="select-delegate"><?=GetMessage('INTR_MAIL_DOMAIN_DELEGATE'); ?></label>
								</div>
								<div class="mail-set-domain-block">
									<div class="mail-set-domain-text"><?=GetMessage('INTR_MAIL_DOMAIN_INP_NAME'); ?></div>
									<div class="mail-set-domain-inp-wrap">
										<span class="mail-set-domain-at">@</span>
										<input class="mail-set-inp" type="text" name="domain">
									</div>
									<div class="mail-set-domain-checkbox-wrap">
										<input class="mail-set-checkbox" type="checkbox" id="domain-public" name="public" checked="checked">
										<label for="domain-public" class="mail-set-label"><?=GetMessage('INTR_MAIL_DOMAIN_PUBLIC'); ?></label>
									</div>
								</div>
							</div>
						</div>
						<div id="connect-domain-block" class="mail-set-item-block-wrap mail-set-domain-disable">
							<div class="mail-set-item-block">
								<div class="mail-set-radio-item">
									<input type="radio" id="select-connect" name="connect" value="1" class="mail-set-radio-inp">
									<label for="select-connect"><?=GetMessage('INTR_MAIL_DOMAIN_CONNECT'); ?></label>
								</div>
							</div>
						</div>
						<div id="get-domain-block" class="mail-set-item-block-wrap mail-set-domain-disable">
							<div class="mail-set-item-block">
								<div class="mail-set-radio-item">
									<input type="radio" id="select-get" name="connect" value="-1" class="mail-set-radio-inp">
									<label for="select-get"><?=($arParams['REG_DOMAIN'] ? str_replace('#DOMAIN#', $arParams['REG_DOMAIN'], GetMessage('INTR_MAIL_DOMAIN_GET2')) : GetMessage('INTR_MAIL_DOMAIN_GET')); ?></label>
								</div>
							</div>
						</div>
						<? } else { ?>
						<div class="mail-set-item-block-wrap">
							<div class="mail-set-item-block">
								<div class="mail-set-domain-text"><?=GetMessage('INTR_MAIL_DOMAIN_INP_NAME'); ?></div>
								<div class="mail-set-domain-inp-wrap">
									<span class="mail-set-domain-at">@</span>
									<input class="mail-set-inp" type="text" name="domain">
								</div>
								<div class="mail-set-domain-checkbox-wrap">
									<input class="mail-set-checkbox" type="checkbox" id="domain-public" name="public" checked="checked">
									<label for="domain-public" class="mail-set-label"><?=GetMessage('INTR_MAIL_DOMAIN_PUBLIC'); ?></label>
								</div>
							</div>
						</div>
						<? } ?>
						<div class="mail-set-footer">
							<a id="domain_create" class="webform-button webform-button-accept" href="?page=domain">
								<span class="webform-button-left"></span><span class="webform-button-text"><?=GetMessage('INTR_MAIL_INP_DOMAIN_ADD'); ?></span><span class="webform-button-right"></span>
							</a>
						</div>
						<input type="submit" style="position: absolute; visibility: hidden; ">
					</form>
					<script type="text/javascript">

						BX.bind(BX('delegate-domain-block'), 'click', function() {
							BX('select-delegate').checked = true;
							BX.removeClass(BX('delegate-domain-block'), 'mail-set-domain-disable');
							BX.addClass(BX('connect-domain-block'), 'mail-set-domain-disable');
							BX.addClass(BX('get-domain-block'), 'mail-set-domain-disable');
						});
						BX.bind(BX('connect-domain-block'), 'click', function() {
							BX('select-connect').checked = true;
							BX.addClass(BX('delegate-domain-block'), 'mail-set-domain-disable');
							BX.removeClass(BX('connect-domain-block'), 'mail-set-domain-disable');
							BX.addClass(BX('get-domain-block'), 'mail-set-domain-disable');
						});
						BX.bind(BX('get-domain-block'), 'click', function() {
							BX('select-get').checked = true;
							BX.addClass(BX('delegate-domain-block'), 'mail-set-domain-disable');
							BX.addClass(BX('connect-domain-block'), 'mail-set-domain-disable');
							BX.removeClass(BX('get-domain-block'), 'mail-set-domain-disable');
						});

						var handleDomainForm = function(e) {
							e.preventDefault ? e.preventDefault() : e.returnValue = false;

							if (<? if (in_array(LANGUAGE_ID, array('ru', 'ua'))) { ?>BX('select-delegate').checked<? } else { ?>true<? } ?>)
							{
								var form = BX('domain_form');

								var formButton = BX('domain_create');
								var alert = BX.findChild(form, {attr: {name: 'post-dialog-alert'}}, true, false);

								if (form.elements['domain'].value.length > 0)
								{
									BX.hide(alert, 'block');
									setPost.animCurrent();

									BX.addClass(formButton, 'webform-button-accept-active webform-button-wait');

									var data = {};
									for (var i = 0; i < form.elements.length; i++)
									{
										if (form.elements[i].name)
											data[form.elements[i].name] = form.elements[i].value;
									}
									BX.ajax({
										method: 'POST',
										url: '<?=$this->__component->GetPath() ; ?>/ajax.php?page=domain&act=create',
										data: data,
										dataType: 'json',
										onsuccess: function(json) {
											if (json.result != 'error')
											{
												window.location = '?page=domain#delegate';
											}
											else
											{
												BX.removeClass(formButton, 'webform-button-accept-active webform-button-wait');

												BX.removeClass(alert, 'post-dialog-alert-ok');
												BX.adjust(BX.findChild(alert, {attr: {name: 'post-dialog-alert-text'}}, true, false), {text: json.error});
												BX.show(alert, 'block');
												setPost.animCurrent();
											}
										},
										onfailure: function() {
											BX.removeClass(formButton, 'webform-button-accept-active webform-button-wait');

											BX.removeClass(alert, 'post-dialog-alert-ok');
											BX.adjust(BX.findChild(alert, {attr: {name: 'post-dialog-alert-text'}}, true, false), {text: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_FORM_ERROR')); ?>'});
											BX.show(alert, 'block');
											setPost.animCurrent();
										}
									});
								}
								else
								{
									BX.removeClass(alert, 'post-dialog-alert-ok');
									BX.adjust(BX.findChild(alert, {attr: {name: 'post-dialog-alert-text'}}, true, false), {text: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_DOMAIN_INP_NAME_EMPTY')); ?>'});
									BX.show(alert, 'block');
									setPost.animCurrent();
								}
							}
							else if (BX('select-get').checked)
							{
								<? if ($arParams['REG_DOMAIN']) { ?>
								var formButton = BX('domain_create');
								var alert = BX.findChild(BX('domain_form'), {attr: {name: 'post-dialog-alert'}}, true, false);

								BX.addClass(formButton, 'webform-button-accept-active webform-button-wait');

								BX.ajax({
									method: 'POST',
									url: '<?=$this->__component->GetPath() ; ?>/ajax.php?page=domain&act=get&domain=<?=CUtil::JSEscape($arParams['REG_DOMAIN']); ?>',
									data: '<?=bitrix_sessid_get(); ?>',
									dataType: 'json',
									onsuccess: function(json)
									{
										if (json.result == 'ok')
										{
											window.location = '?page=domain#delegate';
										}
										else
										{
											BX.removeClass(formButton, 'webform-button-accept-active webform-button-wait');

											BX.removeClass(alert, 'post-dialog-alert-ok');
											BX.adjust(BX.findChild(alert, {attr: {name: 'post-dialog-alert-text'}}, true, false), {text: json.error});
											BX.show(alert, 'block');
											setPost.animCurrent();
										}
									},
									onfailure: function()
									{
										BX.removeClass(formButton, 'webform-button-accept-active webform-button-wait');

										BX.removeClass(alert, 'post-dialog-alert-ok');
										BX.adjust(BX.findChild(alert, {attr: {name: 'post-dialog-alert-text'}}, true, false), {text: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_FORM_ERROR')); ?>'});
										BX.show(alert, 'block');
										setPost.animCurrent();
									}
								});
								<? } else { ?>
								window.location = '?page=domain#get';
								<? } ?>
							}
							else if (BX('select-connect').checked)
							{
								window.location = '?page=domain#connect';
							}

							return false;
						};

						BX.bind(BX('domain_form'), 'submit', handleDomainForm);
						BX.bind(BX('domain_create'), 'click', handleDomainForm);

					</script>
					<? } else { ?>
					<?=GetMessage('INTR_MAIL_DOMAIN_HELP'); ?>
					<br/><br/><br/>
					<div class="mail-set-footer">
						<a class="webform-button webform-button-accept" href="?page=domain">
							<span class="webform-button-left"></span><span class="webform-button-text"><?=GetMessage('INTR_MAIL_INP_DOMAIN_ADD'); ?></span><span class="webform-button-right"></span>
						</a>
					</div>
					<? } ?>

					<? } ?>

					<? if (!empty($domainSettings)) { ?>

					<? if ($isUserAdmin) { ?>
					<div class="mail-set-item-block">
						<span style="float: right; margin-right: 22px; font-size: 16px; ">
							<a style="text-decoration: underline; " href="?page=domain"><?=GetMessage('INTR_MAIL_INP_DOMAIN_EDIT'); ?></a>
							<? if ($domainAdded) { ?>
							&nbsp;&nbsp;&nbsp;&nbsp;
							<a style="text-decoration: underline; " href="?page=manage"><?=GetMessage('INTR_MAIL_INP_ADMIN_MANAGE'); ?></a>
							<? } ?>
						</span>
						<?=str_replace('#DOMAIN#', $domainSettings['server'], GetMessage($domainAdded ? 'INTR_MAIL_ADMIN_DOMAIN' : 'INTR_MAIL_ADMIN_DOMAIN_WAIT')); ?>
					</div>
					<br/><br/>
					<? } ?>

					<? if ($domainMailbox) { ?>
					<div id="domain_setup_form">

						<? $lastMailCheck = CUserOptions::GetOption('global', 'last_mail_check_'.SITE_ID, null); ?>
						<? $lastMailCheckSuccess = CUserOptions::GetOption('global', 'last_mail_check_success_'.SITE_ID, null); ?>

						<div class="mail-set-title">
							<?=str_replace('#EMAIL#', $arParams['MAILBOX']['LOGIN'], GetMessage('INTR_MAIL_MAILBOX_MANAGE')); ?>
						</div>
						<div name="post-dialog-alert" class="post-dialog-alert" style="display: none; ">
							<span class="post-dialog-alert-align"></span>
							<span class="post-dialog-alert-icon"></span>
							<span name="post-dialog-alert-text" class="post-dialog-alert-text"></span>
						</div>
						<div class="mail-set-item-block-wrap">
							<div class="mail-set-item-block-name"><?=GetMessage('INTR_MAIL_MAILBOX_STATUS'); ?></div>
							<div name="status-block" class="mail-set-item-block<? if (isset($lastMailCheckSuccess) && !$lastMailCheckSuccess) { ?> post-status-error<? } ?>">
								<div class="mail-set-item-block-r">
									<span id="domain_delete_form" class="webform-button webform-button-decline">
										<span class="webform-button-left"></span><span class="webform-button-text"><?=GetMessage('INTR_MAIL_MAILBOX_DELETE'); ?></span><span class="webform-button-right"></span>
									</span>&nbsp;
								</div>
								<div class="mail-set-item-block-l">
									<span name="status-text" class="post-dialog-stat-text">
										<? if (isset($lastMailCheck) && intval($lastMailCheck) > 0) { ?>
										<?=str_replace('#DATE#', FormatDate(
											array('s' => 'sago', 'i' => 'iago', 'H' => 'Hago', 'd' => 'dago', 'm' => 'mago', 'Y' => 'Yago'),
											intval($lastMailCheck)
										), GetMessage('INTR_MAIL_CHECK_TEXT')); ?>:
										<? } else { ?>
										<?=GetMessage('INTR_MAIL_CHECK_TEXT_NA'); ?>
										<? } ?>
									</span>

									<span name="status-alert" class="post-dialog-stat-alert">
									<? if (isset($lastMailCheckSuccess)) { ?>
										<? if ($lastMailCheckSuccess) { ?>
										<?=GetMessage('INTR_MAIL_CHECK_SUCCESS'); ?>
										<? } else { ?>
										<?=GetMessage('INTR_MAIL_CHECK_ERROR'); ?>
										<? } ?>
									<? } ?>
									</span>
									<span name="status-info" class="post-dialog-stat-info" style="display: none; "></span>

									<span id="domain_check_form" class="webform-button">
										<span class="webform-button-left"></span><span class="webform-button-text"><?=GetMessage('INTR_MAIL_CHECK'); ?></span><span class="webform-button-right"></span>
									</span>
								</div>
							</div>
						</div>

						<div class="mail-set-item-block-wrap mail-set-item-block-inp">
							<div class="mail-set-item-block-name"><?=GetMessage('INTR_MAIL_MAILBOX_PASSWORD_MANAGE'); ?></div>
							<div class="mail-set-item-block">
								<form id="domain_password_form">
									<? list($login, ) = explode('@', $arParams['MAILBOX']['LOGIN'], 2); ?>
									<input name="ID" type="hidden" value="<?=$arParams['MAILBOX']['ID']; ?>" />
									<input name="login" type="hidden" value="<?=$login; ?>" />
									<?=bitrix_sessid_post(); ?>
									<div class="mail-set-item-block-r">
										<span id="domain_password_save" name="password-save" class="webform-button webform-button-accept">
											<span class="webform-button-left"></span><span class="webform-button-text"><?=GetMessage('INTR_MAIL_MAILBOX_PASSWORD_SAVE'); ?></span><span class="webform-button-right"></span>
										</span>&nbsp;
									</div>
									<div class="mail-set-item-block-l">
										<div class="mail-set-item">
											<div class="mail-set-first-label"><?=GetMessage('INTR_MAIL_MAILBOX_PASSWORD'); ?></div>
											<input name="password" class="mail-set-inp" type="password"/>
											<div name="pass-hint" class="mail-inp-description"><?=GetMessage('INTR_MAIL_INP_PASS_SHORT'); ?></div>
										</div>
										<div class="mail-set-item">
											<div class="mail-set-first-label"><?=GetMessage('INTR_MAIL_MAILBOX_PASSWORD2'); ?></div>
											<input name="password2" class="mail-set-inp" type="password"/>
											<div name="pass2-hint" class="mail-inp-description"></div>
										</div>
									</div>
								</form>
							</div>
						</div>

					</div>
					<? } ?>

					<? if ($domainAdded && ($isUserAdmin || $domainSettings['encryption'] == 'N')) { ?>

					<? if (!empty($arParams['MAILBOX']) && !$domainMailbox) { ?>
					<div id="domain_block_replace_warning">
						<div class="mail-set-item-block mail-set-item-icon">
							<span class="mail-set-item-text">
								<? if (strpos($arParams['MAILBOX']['LOGIN'], '@') !== false) { ?>
								<?=str_replace('#EMAIL#', $arParams['MAILBOX']['LOGIN'], GetMessage('INTR_MAIL_REPLACE_WARNING')); ?>
								<? } else { ?>
								<?=GetMessage('INTR_MAIL_REPLACE_WARNING_UN'); ?>
								<? } ?>
							</span>
						</div>
						<br/><br/>
					</div>
					<? } ?>

					<form<? if ($domainMailbox) { ?> style="display: none; "<? } ?> id="domain_create_form" name="settings_form" action="<?=POST_FORM_ACTION_URI; ?>" method="POST">
						<div name="post-dialog-alert" class="post-dialog-alert" style="display: none; ">
							<span class="post-dialog-alert-align"></span>
							<span class="post-dialog-alert-icon"></span>
							<span name="post-dialog-alert-text" class="post-dialog-alert-text"></span>
						</div>
						<input type="hidden" name="act" value="create">
						<input type="hidden" name="SERVICE" value="<?=$domainSettings['id']; ?>">
						<? if (!empty($arParams['MAILBOX'])) { ?>
						<input type="hidden" name="ID" value="<?=$arParams['MAILBOX']['ID']; ?>">
						<? } ?>
						<?=bitrix_sessid_post(); ?>
						<div class="mail-set-cont">
							<div class="mail-set-cont-left">
								<div class="mail-set-item">
									<div class="mail-set-first-label"><?=GetMessage('INTR_MAIL_INP_MB_NAME'); ?></div>
									<input name="login" class="mail-set-inp" type="text" autocomplete="off" />
									<input type="hidden" name="domain" value="<?=$domainSettings['server']; ?>">
									<span class="mail-set-address">@<?=$domainSettings['server']; ?></span>
									<div name="login-hint" class="mail-inp-description"></div>
								</div>
								<div name="bad-login-hint" style="z-index: 1000; position: absolute; display: none; left: 60px; ">
									<table class="popup-window popup-window-light" cellspacing="0">
										<tr class="popup-window-top-row">
											<td class="popup-window-left-column"><div class="popup-window-left-spacer"></div></td>
											<td class="popup-window-center-column"></td>
											<td class="popup-window-right-column"><div class="popup-window-right-spacer"></div></td>
										</tr>
										<tr class="popup-window-content-row">
											<td class="popup-window-left-column"></td>
											<td class="popup-window-center-column">
												<div class="popup-window-content" id="popup-window-content-input-alert-popup">
													<div id="mail-alert-popup-cont" class="mail-alert-popup-cont" style="display: block;">
														<div class="mail-alert-popup-text"><?=GetMessage('INTR_MAIL_INP_NAME_BAD_HINT'); ?></div>
													</div>
												</div>
											</td>
											<td class="popup-window-right-column"></td>
										</tr>
										<tr class="popup-window-bottom-row">
											<td class="popup-window-left-column"></td>
											<td class="popup-window-center-column"></td>
											<td class="popup-window-right-column"></td>
										</tr>
									</table>
									<div class="popup-window-light-angly popup-window-light-angly-top" style="left: 30px; margin-left: auto;"></div>
								</div>
								<div class="mail-set-item">
									<div class="mail-set-first-label"><?=GetMessage('INTR_MAIL_INP_MB_PASS'); ?></div>
									<input name="password" class="mail-set-inp" type="password" />
									<div name="pass-hint" class="mail-inp-description"><?=GetMessage('INTR_MAIL_INP_PASS_SHORT'); ?></div>
								</div>
								<div class="mail-set-item">
									<div class="mail-set-first-label"><?=GetMessage('INTR_MAIL_INP_PASS2'); ?></div>
									<input name="password2" class="mail-set-inp" type="password" />
									<div name="pass2-hint" class="mail-inp-description"></div>
								</div>
							</div>
							<div class="mail-set-cont-right">
								<div class="mail-set-second-info">
									<? if (IsModuleInstalled('bitrix24')) { ?>
									<?=GetMessage('INTR_MAIL_DOMAIN_HELP_B24'); ?>
									<? } else { ?>
									<?=GetMessage('INTR_MAIL_DOMAIN_HELP_BOX'); ?>
									<? } ?>
								</div>
							</div>
						</div>
						<div class="mail-set-footer">
							<a id="domain_create_save" name="create-save" class="webform-button webform-button-accept" href="#">
								<span class="webform-button-left"></span><span class="webform-button-text"><?=GetMessage('INTR_MAIL_INP_SAVE'); ?></span><span class="webform-button-right"></span>
							</a>
							<span id="domain_create_clear" class="mail-set-cancel-link"><?=GetMessage('INTR_MAIL_INP_CLEAR'); ?></span>
						</div>
						<input type="submit" style="position: absolute; visibility: hidden; ">
					</form>

					<? } ?>

					<? if ($domainAdded && !$isUserAdmin && $domainSettings['encryption'] != 'N') { ?>
					<div id="domain_create_form" style="text-align: center; <? if ($domainMailbox) { ?>display: none; <? } ?>">
						<?=str_replace('#DOMAIN#', $domainSettings['server'], GetMessage('INTR_MAIL_DOMAIN_USER_INFO')); ?>
						<br/><br/><br/>
					</div>
					<? } ?>

					<? } ?>

				</div>
			</div>
			<? } ?>
			<div id="mail-set-third" class="mail-set-third-wrap"<? if ($defaultBlock == 'imap') { ?> style="display: block;"<? } ?>>
				<div class="mail-set-third">

					<? if ($imapMailbox) { ?>
					<div id="imap_setup_form" class="mail-set-imap-setup">

						<? $lastMailCheck = CUserOptions::GetOption('global', 'last_mail_check_'.SITE_ID, null); ?>
						<? $lastMailCheckSuccess = CUserOptions::GetOption('global', 'last_mail_check_success_'.SITE_ID, null); ?>

						<div class="mail-set-title">
							<? if (strpos($arParams['MAILBOX']['LOGIN'], '@') !== false) { ?>
							<?=str_replace('#EMAIL#', $arParams['MAILBOX']['LOGIN'], GetMessage('INTR_MAIL_MAILBOX_MANAGE')); ?>
							<? } else { ?>
							<?=GetMessage('INTR_MAIL_MAILBOX_MANAGE_UN'); ?>
							<? } ?>
						</div>
						<div name="post-dialog-alert" class="post-dialog-alert" style="display: none; ">
							<span class="post-dialog-alert-align"></span>
							<span class="post-dialog-alert-icon"></span>
							<span name="post-dialog-alert-text" class="post-dialog-alert-text"></span>
						</div>
						<div class="mail-set-item-block-wrap">
							<div class="mail-set-item-block-name"><?=GetMessage('INTR_MAIL_MAILBOX_STATUS'); ?></div>
							<div name="status-block" class="mail-set-item-block<? if (isset($lastMailCheckSuccess) && !$lastMailCheckSuccess) { ?> post-status-error<? } ?>">
								<div class="mail-set-item-block-r">
									<span id="imap_delete_form" class="webform-button webform-button-decline">
										<span class="webform-button-left"></span><span class="webform-button-text"><?=GetMessage('INTR_MAIL_MAILBOX_DELETE'); ?></span><span class="webform-button-right"></span>
									</span>&nbsp;
								</div>
								<div class="mail-set-item-block-l">
									<span name="status-text" class="post-dialog-stat-text">
										<? if (isset($lastMailCheck) && intval($lastMailCheck) > 0) { ?>
										<?=str_replace('#DATE#', FormatDate(
											array('s' => 'sago', 'i' => 'iago', 'H' => 'Hago', 'd' => 'dago', 'm' => 'mago', 'Y' => 'Yago'),
											intval($lastMailCheck)
										), GetMessage('INTR_MAIL_CHECK_TEXT')); ?>:
										<? } else { ?>
										<?=GetMessage('INTR_MAIL_CHECK_TEXT_NA'); ?>
										<? } ?>
									</span>

									<span name="status-alert" class="post-dialog-stat-alert">
									<? if (isset($lastMailCheckSuccess)) { ?>
										<? if ($lastMailCheckSuccess) { ?>
										<?=GetMessage('INTR_MAIL_CHECK_SUCCESS'); ?>
										<? } else { ?>
										<?=GetMessage('INTR_MAIL_CHECK_ERROR'); ?>
										<? } ?>
									<? } ?>
									</span>
									<span name="status-info" class="post-dialog-stat-info" style="display: none; "></span>

									<span id="imap_check_form" class="webform-button">
										<span class="webform-button-left"></span><span class="webform-button-text"><?=GetMessage('INTR_MAIL_CHECK'); ?></span><span class="webform-button-right"></span>
									</span>
								</div>
							</div>
						</div>

						<div id="imap_pass_block" class="mail-set-item-block-wrap">
							<div class="mail-set-item-block-name"><?=GetMessage('INTR_MAIL_MAILBOX_SETTINGS_MANAGE'); ?></div>
							<div class="mail-set-item-block">
								<form id="imap_password_form">
									<? list($login, ) = explode('@', $arParams['MAILBOX']['LOGIN'], 2); ?>
									<input name="ID" type="hidden" value="<?=$arParams['MAILBOX']['ID']; ?>" />
									<input name="login" type="hidden" value="<?=$login; ?>" />
									<?=bitrix_sessid_post(); ?>
									<div class="mail-set-item-block-r">
										<span class="webform-button" onclick="BX.hide(BX('imap_pass_block'), 'block'); BX.show(BX('edit_imap'), 'block'); setPost.animCurrent(); ">
											<span class="webform-button-left"></span><span class="webform-button-text"><?=GetMessage('INTR_MAIL_MAILBOX_SETTINGS_GO'); ?></span><span class="webform-button-right"></span>
										</span>&nbsp;
									</div>
									<div class="mail-set-item-block-inp">
										<div class="mail-set-item">
											<div class="mail-set-first-label"><?=GetMessage('INTR_MAIL_MAILBOX_PASSWORD'); ?></div>
											<input name="password" class="mail-set-inp" type="password"/>
											<div name="pass-hint" class="mail-inp-description"></div>
											<span id="imap_password_save" name="password-save" class="webform-button webform-button-accept" style="margin-left: 25px;">
												<span class="webform-button-left"></span><span class="webform-button-text"><?=GetMessage('INTR_MAIL_MAILBOX_PASSWORD_SAVE_IMAP'); ?></span><span class="webform-button-right"></span>
											</span>
										</div>
									</div>
								</form>
							</div>
						</div>

						<div id="edit_imap" name="edit-imap" class="post-dialog-wrap" style="display: none; ">
							<form>
								<? $settings = $arParams['SERVICES'][$arParams['MAILBOX']['SERVICE_ID']]; ?>
								<div name="post-dialog-alert" class="post-dialog-alert" style="display: none; ">
									<span class="post-dialog-alert-align"></span>
									<span class="post-dialog-alert-icon"></span>
									<span name="post-dialog-alert-text" class="post-dialog-alert-text"></span>
								</div>
								<input type="hidden" name="act" value="edit">
								<input type="hidden" name="SERVICE" value="<?=$settings['id']; ?>">
								<input type="hidden" name="ID" value="<?=$arParams['MAILBOX']['ID']; ?>">
								<?=bitrix_sessid_post(); ?>
								<? if (empty($settings['link'])) { ?>
								<div class="post-dialog-inp-item">
									<span class="post-dialog-inp-label"><?=GetMessage('INTR_MAIL_INP_LINK'); ?></span>
									<input id="link" name="link" type="text" class="post-dialog-inp" value="<?=$arParams['MAILBOX']['LINK']; ?>">
									<div name="link-hint" class="mail-inp-description"></div>
								</div>
								<? } ?>
								<? if (empty($settings['server'])) { ?>
								<div class="post-dialog-inp-item">
									<div class="post-dialog-inp-serv">
										<span class="post-dialog-inp-label"><?=GetMessage('INTR_MAIL_INP_SERVER'); ?></span>
										<input id="server" name="server" type="text" class="post-dialog-inp" value="<?=$arParams['MAILBOX']['SERVER']; ?>">
										<div name="server-hint" class="mail-inp-description"></div>
									</div><div class="post-dialog-inp-post">
										<span class="post-dialog-inp-label"><?=GetMessage('INTR_MAIL_INP_PORT'); ?></span>
										<input id="port" name="port" type="text" class="post-dialog-inp" value="<?=$arParams['MAILBOX']['PORT']; ?>">
									</div>
								</div>
								<? } ?>
								<? if (empty($settings['encryption'])) { ?>
								<div class="post-dialog-inp-item">
									<span class="post-dialog-inp-label"><?=GetMessage('INTR_MAIL_INP_ENCRYPT'); ?></span>
									<span class="post-dialog-inp-select-wrap">
										<select name="encryption" class="post-dialog-inp-select">
											<option value="Y"<? if ($arParams['MAILBOX']['USE_TLS'] == 'Y') { ?> selected="selected"<? } ?>><?=GetMessage('INTR_MAIL_INP_ENCRYPT_YES'); ?></option>
											<option value="S"<? if ($arParams['MAILBOX']['USE_TLS'] == 'S') { ?> selected="selected"<? } ?>><?=GetMessage('INTR_MAIL_INP_ENCRYPT_SELF'); ?></option>
											<option value="N"<? if (!in_array($arParams['MAILBOX']['USE_TLS'], array('Y', 'S'))) { ?> selected="selected"<? } ?>><?=GetMessage('INTR_MAIL_INP_ENCRYPT_NO'); ?></option>
										</select>
									</span>
								</div>
								<? } ?>
								<div class="post-dialog-inp-item">
									<span class="post-dialog-inp-label"><?=GetMessage('INTR_MAIL_INP_LOGIN'); ?></span>
									<input name="login" type="text" class="post-dialog-inp" value="<?=$arParams['MAILBOX']['LOGIN']; ?>">
									<div name="login-hint" class="mail-inp-description"></div>
								</div>
								<div class="post-dialog-inp-item">
									<span class="post-dialog-inp-label"><?=GetMessage('INTR_MAIL_INP_PASS'); ?></span>
									<input name="password" type="password" class="post-dialog-inp">
									<div name="pass-hint" class="mail-inp-description"></div>
								</div>
								<div class="post-dialog-footer">
									<a id="imap_edit_save" name="edit-save" href="#" class="webform-button webform-button-accept">
										<span class="webform-button-left"></span><span class="webform-button-text"><?=GetMessage('INTR_MAIL_INP_EDIT_SAVE'); ?></span><span class="webform-button-right"></span>
									</a>
									<span id="imap_edit_cancel" class="webform-button" onclick="BX.hide(BX('edit_imap'), 'block'); BX.show(BX('imap_pass_block'), 'block'); setPost.animCurrent(); ">
										<span class="webform-button-left"></span><span class="webform-button-text"><?=GetMessage('INTR_MAIL_INP_CANCEL'); ?></span><span class="webform-button-right"></span>
									</span>
								</div>
								<input type="submit" style="position: absolute; visibility: hidden; ">
							</form>
						</div>

					</div>
					<? } ?>

					<? $hasImap = false; ?>
					<div id="imap_icons" class="mail-set-img-wrap"<? if ($imapMailbox) { ?> style="display: none; "<? } ?>>
						<? foreach ($arParams['SERVICES'] as $id => $settings) { ?>
						<? if ($settings['type'] != 'imap') continue; ?>
						<? $hasImap = true; ?>
						<a onclick="toggleImapForm(this, <?=$id; ?>); return false; " href="#imap-<?=$id; ?>" id="imap-<?=$id; ?>-link" name="imap-link" class="mail-set-serv"<? if (strlen($settings['name']) > 15) { ?> style="font-size: 18px; "<? } ?>><?
							if ($settings['icon']) { ?><img src="<?=$settings['icon']; ?>" alt="<?=$settings['name']; ?>"/><? }
							else {?>&nbsp;<?=$settings['name']; ?>&nbsp;<? } ?></a>
						<? } ?>
					</div>

					<? if (!$hasImap) { ?>
					<div style="text-align: center; ">
						<br/><br/>
						<?=GetMessage('MAIL_SERVICES_NOT_FOUND'); ?>
						<br/><br/><br/>
					</div>
					<? } ?>

					<div id="create_imap" class="mail-set-imap-cont-wrap" style="display: none; ">

						<? if (!empty($arParams['MAILBOX']) && !$imapMailbox) { ?>
						<div id="imap_block_replace_warning">
							<br/><br/>
							<div class="mail-set-item-block mail-set-item-icon">
								<span class="mail-set-item-text">
									<? if (strpos($arParams['MAILBOX']['LOGIN'], '@') !== false) { ?>
									<?=str_replace('#EMAIL#', $arParams['MAILBOX']['LOGIN'], GetMessage('INTR_MAIL_REPLACE_WARNING')); ?>
									<? } else { ?>
									<?=GetMessage('INTR_MAIL_REPLACE_WARNING_UN'); ?>
									<? } ?>
								</span>
							</div>
						</div>
						<? } ?>

						<? foreach ($arParams['SERVICES'] as $id => $settings) { ?>
						<? if ($settings['type'] != 'imap') continue; ?>

						<div id="create_imap_<?=$id; ?>" name="create-imap" class="post-dialog-wrap" style="display: none; ">
							<form>
								<div name="post-dialog-alert" class="post-dialog-alert" style="display: none; ">
									<span class="post-dialog-alert-align"></span>
									<span class="post-dialog-alert-icon"></span>
									<span name="post-dialog-alert-text" class="post-dialog-alert-text"></span>
								</div>
								<input type="hidden" name="act" value="create">
								<input type="hidden" name="SERVICE" value="<?=$id; ?>">
								<? if (!empty($arParams['MAILBOX'])) { ?>
								<input type="hidden" name="ID" value="<?=$arParams['MAILBOX']['ID']; ?>">
								<? } ?>
								<?=bitrix_sessid_post(); ?>
								<? if (empty($settings['link'])) { ?>
								<div class="post-dialog-inp-item">
									<span class="post-dialog-inp-label"><?=GetMessage('INTR_MAIL_INP_LINK'); ?></span>
									<input id="link" name="link" type="text" class="post-dialog-inp">
									<div name="link-hint" class="mail-inp-description"></div>
								</div>
								<? } ?>
								<? if (empty($settings['server'])) { ?>
								<div class="post-dialog-inp-item">
									<div class="post-dialog-inp-serv">
										<span class="post-dialog-inp-label"><?=GetMessage('INTR_MAIL_INP_SERVER'); ?></span>
										<input id="server" name="server" type="text" class="post-dialog-inp">
										<div name="server-hint" class="mail-inp-description"></div>
									</div><div class="post-dialog-inp-post">
										<span class="post-dialog-inp-label"><?=GetMessage('INTR_MAIL_INP_PORT'); ?></span>
										<input id="port" name="port" type="text" class="post-dialog-inp">
									</div>
								</div>
								<? } ?>
								<? if (empty($settings['encryption'])) { ?>
								<div class="post-dialog-inp-item">
									<span class="post-dialog-inp-label"><?=GetMessage('INTR_MAIL_INP_ENCRYPT'); ?></span>
									<span class="post-dialog-inp-select-wrap">
										<select name="encryption" class="post-dialog-inp-select">
											<option value="Y" selected="selected"><?=GetMessage('INTR_MAIL_INP_ENCRYPT_YES'); ?></option>
											<option value="S"><?=GetMessage('INTR_MAIL_INP_ENCRYPT_SELF'); ?></option>
											<option value="N"><?=GetMessage('INTR_MAIL_INP_ENCRYPT_NO'); ?></option>
										</select>
									</span>
								</div>
								<? } ?>
								<div class="post-dialog-inp-item">
									<span class="post-dialog-inp-label"><?=GetMessage('INTR_MAIL_INP_LOGIN'); ?></span>
									<input name="login" type="text" class="post-dialog-inp">
									<div name="login-hint" class="mail-inp-description"></div>
								</div>
								<div class="post-dialog-inp-item">
									<span class="post-dialog-inp-label"><?=GetMessage('INTR_MAIL_INP_PASS'); ?></span>
									<input name="password" type="password" class="post-dialog-inp">
									<div name="pass-hint" class="mail-inp-description"></div>
								</div>
								<div class="post-dialog-footer">
									<a id="imap_<?=$id; ?>_create_save" name="create-save" href="#" class="webform-button webform-button-accept">
										<span class="webform-button-left"></span><span class="webform-button-text"><?=GetMessage('INTR_MAIL_INP_SAVE'); ?></span><span class="webform-button-right"></span>
									</a>
									<span id="imap_<?=$id; ?>_create_cancel" class="webform-button" onclick="toggleImapForm(BX('imap-<?=$id; ?>-link'), <?=$id; ?>); return false; ">
										<span class="webform-button-left"></span><span class="webform-button-text"><?=GetMessage('INTR_MAIL_INP_CANCEL'); ?></span><span class="webform-button-right"></span>
									</span>
								</div>
								<input type="submit" style="position: absolute; visibility: hidden; ">
							</form>
						</div>

						<? } ?>

						<div class="mail-set-cont-right">
							<div class="mail-set-second-info">
								<?=GetMessage('INTR_MAIL_IMAP_HELP'); ?>
							</div>
						</div>
					</div>

					<script type="text/javascript">

						function toggleImapForm(link, id)
						{
							var createImap = BX('create_imap');
							var createImapCurrent = BX('create_imap_'+id);

							if (createImap.style.display == 'none')
							{
								BX.addClass(link, 'mail-set-serv-active');

								createImap.style.display = 'block';
								createImapCurrent.style.display = 'block';
							}
							else
							{
								if (createImapCurrent.style.display == 'none')
								{
									var links = BX.findChildren(BX('imap_icons'), {attr: {name: 'imap-link'}}, true);
									for (var i in links)
										BX.removeClass(links[i], 'mail-set-serv-active');

									var forms = BX.findChildren(createImap, {attr: {name: 'create-imap'}}, true);
									for (var i in forms)
									{
										BX.onCustomEvent(forms[i], 'HideImapForm');
										forms[i].style.display = 'none';
									}

									BX.addClass(link, 'mail-set-serv-active');
									createImapCurrent.style.display = 'block';
								}
								else
								{
									BX.removeClass(link, 'mail-set-serv-active');

									BX.onCustomEvent(createImapCurrent, 'HideImapForm');
									createImap.style.display = 'none';
									createImapCurrent.style.display = 'none';
								}
							}

							setPost.anim('mail-set-third', BX('mail-set-third-btn'));
						}

						function inputPlaceholder(input, text, isFake)
						{
							var isFake = isFake == false ? false : true;

							BX.adjust(input, {attrs: {'data-placeholder': text}});

							if (input.value == '')
							{
								if (isFake)
									BX.addClass(input, 'post-dialog-inp-placeholder');
								input.value = text;
							}

							BX.bind(input, 'focus', function() {
								if (!isFake)
								{
									setTimeout(function() {
										input.select();
									}, 0);
								}
								else
								{
									if (input.value == text && BX.hasClass(input, 'post-dialog-inp-placeholder'))
										input.value = '';
								}
								BX.removeClass(input, 'post-dialog-inp-placeholder');
							});
							BX.bind(input, 'blur', function() {
								if (input.value == '')
								{
									BX.addClass(input, 'post-dialog-inp-placeholder');
									input.value = text;
								}
							});
						}

						var linkInputs = BX.findChildren(BX('create_imap'), {tag: 'input', attr: {name: 'link'}}, true);
						for (var i in linkInputs)
							inputPlaceholder(linkInputs[i], 'http://mail.example.com', true);

						var serverInputs = BX.findChildren(BX('create_imap'), {tag: 'input', attr: {name: 'server'}}, true);
						for (var i in serverInputs)
							inputPlaceholder(serverInputs[i], 'imap.example.com', true);

						var portInputs = BX.findChildren(BX('create_imap'), {tag: 'input', attr: {name: 'port'}}, true);
						for (var i in portInputs)
							inputPlaceholder(portInputs[i], '993', false);

						var portInputs = BX.findChildren(BX('edit_imap'), {tag: 'input', attr: {name: 'password'}}, true);
						for (var i in portInputs)
							inputPlaceholder(portInputs[i], '********', true);

					</script>

				</div>
			</div>
			<div id="mail-set-corner" class="mail-set-corner"<? if (!$showB24Block && !$showDomainBlock) { ?> style="display: none; " <? } ?>></div>
		</div>
	</div>
	<? if ($showB24Block || $showDomainBlock) { ?>
	<div id="mail-info-message" class="mail-info-message"<? if (!empty($arParams['MAILBOX'])) { ?> style="display: none; "<? } ?>>
		<?=GetMessage(IsModuleInstalled('bitrix24') ? 'INTR_MAIL_HINT_B24' : 'INTR_MAIL_HINT_BOX'); ?>
	</div>
	<? } ?>
</div>

<script type="text/javascript">

	var setPost = {
		corner : BX('mail-set-corner'),
		anim_block : null,
		btn : null,
		wrap_block : BX('mail-set-block'),
		block_list : null,
		table : BX('mail-block-table'),
		active_cell_num : null,
		over_cell_num : null,

		show : function(ev)
		{
			var event = ev || window.event;
			var target = event.target || event.srcElement;
			var active_cell,
				btn;

			while(target != this)
			{
				if (target.tagName == 'TD') {
					active_cell = target;
					break;
				}
				target = target.parentNode;
			}

			if(!active_cell.hasAttribute('data-block')) return;

			if(event.type == 'mouseover'){
				setPost.block_hover(active_cell);
			}
			else if (event.type == 'mouseout'){
				setPost.block_out();
			}
			else if(event.type == 'click')
			{
				var blockID = active_cell.getAttribute('data-block');

				if(blockID == 'mail-set-first'){
					btn = BX('mail-set-first-btn')
				}else if(blockID == 'mail-set-second'){
					btn = BX('mail-set-second-btn')
				}else if(blockID == 'mail-set-third'){
					btn = BX('mail-set-third-btn')
				}
				setPost.anim(blockID, btn)
			}
		},

		animCurrent: function()
		{
			var activeCell = BX.findChild(BX('mail-block-table'), {'class': 'mail-block-active'}, true, false);
			var blockID = activeCell.getAttribute('data-block');

			switch (blockID)
			{
				case 'mail-set-first':
					btn = BX('mail-set-first-btn');
					break;
				case 'mail-set-second':
					btn = BX('mail-set-second-btn');
					break;
				case 'mail-set-third':
					btn = BX('mail-set-third-btn');
					break;
			}

			if (blockID && btn)
				setPost.anim(blockID, btn);
		},

		anim : function(blockID, btn)
		{

			this.block_list = this.wrap_block.childNodes;

			this.anim_block = BX(blockID);
			this.btn = btn;

			this.wrap_block.style.height = this.wrap_block.offsetHeight + 'px';

			for(var i = this.block_list.length-1; i>=0; i--){
				if(this.block_list[i].tagName == 'DIV' && this.block_list[i] != this.corner){
					this.block_list[i].style.display = 'none';
				}
			}

			this.anim_block.style.display = 'block';

			var corner_offset =  ((this.btn.offsetWidth/2) + BX.pos(this.btn).left) - ((this.corner.offsetWidth/2) + BX.pos(this.corner).left);
			this.corner.style.left = parseInt(BX.style(this.corner, 'left')) + corner_offset + 'px';

			this.anim_easing({
				start : {height : this.wrap_block.offsetHeight},
				finish : {height : this.anim_block.offsetHeight}
			});

			for(var i = this.table.rows.length-1; i >=0; i--){
				for(var b = this.table.rows[i].cells.length-1; b>=0; b--)
				{
					BX.removeClass(this.table.rows[i].cells[b], 'mail-block-active');

					if(this.btn.parentNode.parentNode == this.table.rows[i].cells[b]){
						this.active_cell_num = b;
					}
				}
			}

			BX.addClass(this.table.rows[0].cells[this.active_cell_num], 'mail-block-active');
			BX.addClass(this.table.rows[1].cells[this.active_cell_num], 'mail-block-active')

		},

		anim_easing : function(params){
			var _this = this;
			var easing = new BX.easing({
				duration:300,
				start : params.start,
				finish : params.finish,
				transition : BX.easing.makeEaseOut(BX.easing.transitions.linear),
				step:function(state){
					_this.wrap_block.style.height = state.height +'px';
				},
				complete:function(){}
			});

			easing.animate()
		},

		block_hover : function(cell)
		{
			var tr;
			tr = cell.parentNode;

			for(var i = tr.cells.length-1; i>=0; i--){
				if(tr.cells[i] == cell){
					this.over_cell_num = i;
				}
			}

			for(var i = this.table.rows.length-1; i >=0; i--)
			{
				BX.addClass(this.table.rows[i].cells[this.over_cell_num] ,'mail-block-hover')
			}
		},

		block_out : function()
		{
			for(var i = this.table.rows.length-1; i >=0; i--)
			{
				BX.removeClass( this.table.rows[i].cells[this.over_cell_num] ,'mail-block-hover')
			}
		}
	};

	BX.bind(BX('mail-block-table'), 'mouseover', setPost.show);
	BX.bind(BX('mail-block-table'), 'mouseout', setPost.show);
	BX.bind(BX('mail-block-table'), 'click', setPost.show);

	<? if ($defaultBlock == 'domain') { ?>
	setTimeout(function () {
		setPost.anim('mail-set-second', BX('mail-set-second-btn'));
	}, 10);
	<? } ?>
	<? if ($defaultBlock == 'imap') { ?>
	setTimeout(function () {
		setPost.anim('mail-set-third', BX('mail-set-third-btn'));
	}, 10);
	<? } ?>

</script>

<script type="text/javascript">

	(function() {

		var hasMailbox = <?=(empty($arParams['MAILBOX']) ? 'false' : 'true'); ?>;

		function CreateMailboxForm(form, loginMinLength)
		{
			var self = this;

			var form = form;
			var loginMinLength = typeof loginMinLength == 'undefined' ? 1 : loginMinLength;

			var loginCont = form.elements['login'].parentNode;
			var loginHint = BX.findChild(loginCont, {attr: {name: 'login-hint'}}, true, false);
			var badLoginHint = BX.findChild(form, {attr: {name: 'bad-login-hint'}}, true, false);

			var passCont = form.elements['password'].parentNode;
			var passHint = BX.findChild(passCont, {attr: {name: 'pass-hint'}}, true, false);
			var pass2Cont = form.elements['password2'].parentNode;
			var pass2Hint = BX.findChild(pass2Cont, {attr: {name: 'pass2-hint'}}, true, false);

			var cnTimeout = false;
			var cnAjax    = false;
			var cnResults = {};

			var lastKey = false;
			var nameWasFilled = false;
			this.checkName = function(e)
			{
				var data = {
					SERVICE: form.elements['SERVICE'].value,
					login: form.elements['login'].value,
					domain: form.elements['domain'].value
				};
				var key = data.SERVICE+'/'+data.login+'/'+data.domain;

				if (key == lastKey && typeof cnAjax == 'object')
					return;
				lastKey = key;

				cnTimeout = clearTimeout(cnTimeout);
				if (typeof cnAjax == 'object')
				{
					cnAjax.abort();
					cnAjax = false;
				}

				BX.removeClass(loginCont, 'mail-set-error');
				BX.removeClass(loginCont, 'mail-set-ok');
				BX.cleanNode(loginHint);
				BX.hide(badLoginHint, 'block');

				if (data.login.length > 0 && !data.login.match(/^[a-z0-9_]+(\.?[a-z0-9_-]+)*\.?$/i))
				{
					nameWasFilled = true;

					BX.addClass(loginCont, 'mail-set-error');
					BX.adjust(loginHint, {text: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_INP_NAME_BAD')); ?>'});

					if (typeof e == 'object' && BX.util.in_array(e.type, ['focus', 'keyup']))
						BX.show(badLoginHint, 'block');

					return;
				}

				if (data.login.length >= loginMinLength)
				{
					nameWasFilled = true;

					if (data.login.length > 30)
					{
						BX.addClass(loginCont, 'mail-set-error');
						BX.adjust(loginHint, {text: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_INP_NAME_LONG')); ?>'});

						return;
					}
				}
				else
				{
					if (typeof e == 'object' && BX.util.in_array(e.type, ['focus', 'keyup']) && !nameWasFilled);
					else
					{
						nameWasFilled = true;

						BX.addClass(loginCont, 'mail-set-error');
						BX.adjust(loginHint, {
							text: data.login.length == 0
								? '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_INP_NAME_EMPTY')); ?>'
								: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_INP_NAME_SHORT')); ?>'
						});
					}

					return;
				}

				var handleResponse = function(json)
				{
					BX.removeClass(form.elements['login'], 'mail-set-inp-wait');

					if (typeof json == 'undefined')
						return;

					if (json.result != 'error')
					{
						BX.addClass(loginCont, json.occupied ? 'mail-set-error' : 'mail-set-ok');
						BX.adjust(loginHint, {text: json.occupied ? '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_INP_NAME_OCCUPIED')); ?>' : '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_INP_NAME_FREE')); ?>'});
					}

					if (typeof cnResults[key] == 'undefined')
						cnResults[key] = json;
				};
				if (typeof cnResults[key] == 'undefined')
				{
					cnTimeout = setTimeout(function() {

						if (!data.login.match(/^[a-z0-9_]+(\.?[a-z0-9_-]*[a-z0-9_]+)*$/i))
						{
							BX.addClass(loginCont, 'mail-set-error');
							BX.adjust(loginHint, {text: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_INP_NAME_BAD')); ?>'});

							if (typeof e == 'object' && BX.util.in_array(e.type, ['focus', 'keyup']))
								BX.show(badLoginHint, 'block');

							return;
						}

						BX.addClass(form.elements['login'], 'mail-set-inp-wait');

						cnAjax = BX.ajax({
							method: 'POST',
							url: '<?=$this->__component->GetPath() ; ?>/ajax.php?page=home&act=name',
							data: data,
							dataType: 'json',
							onsuccess: handleResponse,
							onfailure: function() {
								BX.removeClass(form.elements['login'], 'mail-set-inp-wait');
							}
						});
					}, typeof e == 'object' && e.type == 'keyup' ? 400 : 0);
				}
				else
				{
					handleResponse(cnResults[key]);
				}
			};

			this.checkPassword = function(e)
			{
				if (!form.elements['password'].value.match(/^[\x21\x23-\x26\x28-\x2E\x30-\x3B\x40-\x5A\x5E\x5F\x61-\x7A]*$/))
				{
					BX.removeClass(passCont, 'mail-set-ok');
					BX.addClass(passCont, 'mail-set-error');
					BX.adjust(passHint, {text: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_INP_PASS_BAD')); ?>'});
				}
				else if (form.elements['password'].value.length < 6)
				{
					if (typeof e == 'object' && e.type == 'keyup')
					{
						if (!BX.hasClass(passCont, 'mail-set-ok') && !BX.hasClass(passCont, 'mail-set-error'))
							return;
					}

					BX.removeClass(passCont, 'mail-set-ok');
					BX.addClass(passCont, 'mail-set-error');
					BX.adjust(passHint, {
						text: form.elements['password'].value.length == 0
							? '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_INP_PASS_EMPTY')); ?>'
							: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_INP_PASS_SHORT')); ?>'
					});
				}
				else if (form.elements['password'].value.length > 20)
				{
					BX.removeClass(passCont, 'mail-set-ok');
					BX.addClass(passCont, 'mail-set-error');
					BX.adjust(passHint, {text: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_INP_PASS_LONG')); ?>'});
				}
				else if (form.elements['password'].value == form.elements['login'].value)
				{
					BX.removeClass(passCont, 'mail-set-ok');
					BX.addClass(passCont, 'mail-set-error');
					BX.adjust(passHint, {text: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_INP_PASS_LIKELOGIN')); ?>'});
				}
				else
				{
					BX.removeClass(passCont, 'mail-set-error');
					BX.addClass(passCont, 'mail-set-ok');
					BX.cleanNode(passHint);
				}

				if (form.elements['password2'].value.length > 0)
					self.checkPassword2(e);
			};

			this.checkPassword2 = function(e)
			{
				var pass  = form.elements['password'].value;
				var pass2 = form.elements['password2'].value;

				if (pass2.length == 0 || pass2 != pass)
				{
					var error = '';

					if (pass2.length == 0)
					{
						if (typeof e == 'object' && e.type == 'keyup')
						{
							if (!BX.hasClass(pass2Cont, 'mail-set-ok') && !BX.hasClass(pass2Cont, 'mail-set-error'))
								return;
						}

						error = '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_INP_PASS2_EMPTY')); ?>';
					}
					else
					{
						error = pass.substr(0, pass2.length) == pass2
							? '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_INP_PASS2_SHORT')); ?>'
							: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_INP_PASS2_DIFF')); ?>';
					}

					BX.removeClass(pass2Cont, 'mail-set-ok');
					BX.addClass(pass2Cont, 'mail-set-error');
					BX.adjust(pass2Hint, {text: error});
				}
				else
				{
					BX.removeClass(pass2Cont, 'mail-set-error');
					BX.addClass(pass2Cont, 'mail-set-ok');
					BX.cleanNode(pass2Hint);
				}
			};

			this.checkAndSubmit = function(e)
			{
				BX.hide(BX.findChild(form, {attr: {name: 'post-dialog-alert'}}, true, false), 'block');
				setPost.animCurrent();

				self.checkName(e);
				self.checkPassword(e);
				self.checkPassword2(e);

				if (BX.hasClass(loginCont, 'mail-set-ok') && !BX.hasClass(passCont, 'mail-set-error') && !BX.hasClass(pass2Cont, 'mail-set-error'))
					self.submit();
			};

			this.clean = function(e)
			{
				nameWasFilled = false;

				form.elements['login'].value = '';
				form.elements['password'].value = '';
				form.elements['password2'].value = '';

				BX.removeClass(loginCont, 'mail-set-ok');
				BX.removeClass(loginCont, 'mail-set-error');
				BX.removeClass(passCont, 'mail-set-ok');
				BX.removeClass(passCont, 'mail-set-error');
				BX.removeClass(pass2Cont, 'mail-set-ok');
				BX.removeClass(pass2Cont, 'mail-set-error');

				BX.cleanNode(loginHint);
				BX.adjust(passHint, {text: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_INP_PASS_SHORT')); ?>'});
				BX.cleanNode(pass2Hint);
			};

			var prSubmit = false;
			var replacePopup = false;
			this.submit = function()
			{
				if (prSubmit)
					return false;

				var doSubmit = function()
				{
					prSubmit = true;

					var formButton = BX.findChild(form, {attr: {name: 'create-save'}}, true, false);
					var alert = BX.findChild(form, {attr: {name: 'post-dialog-alert'}}, true, false);

					BX.addClass(formButton, 'webform-button-accept-active webform-button-wait');

					var data = {};
					for (var i = 0; i < form.elements.length; i++)
					{
						if (form.elements[i].name)
							data[form.elements[i].name] = form.elements[i].value;
					}
					BX.ajax({
						method: 'POST',
						url: '<?=$this->__component->GetPath() ; ?>/ajax.php?page=home&act=create',
						data: data,
						dataType: 'json',
						onsuccess: function(json) {
							if (json.result != 'error')
							{
								window.location = '?page=success';
							}
							else
							{
								BX.removeClass(formButton, 'webform-button-accept-active webform-button-wait');

								BX.removeClass(alert, 'post-dialog-alert-ok');
								BX.adjust(BX.findChild(alert, {attr: {name: 'post-dialog-alert-text'}}, true, false), {text: json.error});
								BX.show(alert, 'block');
								setPost.animCurrent();

								prSubmit = false;
							}
						},
						onfailure: function() {
							BX.removeClass(formButton, 'webform-button-accept-active webform-button-wait');

							BX.removeClass(alert, 'post-dialog-alert-ok');
							BX.adjust(BX.findChild(alert, {attr: {name: 'post-dialog-alert-text'}}, true, false), {text: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_FORM_ERROR')); ?>'});
							BX.show(alert, 'block');
							setPost.animCurrent();

							prSubmit = false;
						}
					});
				};

				if (hasMailbox)
				{
					if (replacePopup === false)
					{
						replacePopup = new BX.PopupWindow('replace-mailbox', null, {
							closeIcon: {'margin-right': '3px', 'margin-top': '13px'},
							closeByEsc: true,
							overlay: true,
							lightShadow: true,
							titleBar: {content: BX.create('span', {
								attrs: {className: 'mail-alert-top-popup-title'},
								text: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_REMOVE_CONFIRM')); ?>'
							})},
							content: BX.create('div', {
								attrs: {className: 'mail-alert-popup-del-box mail-alert-popup-del-text'},
								<? if (strpos($arParams['MAILBOX']['LOGIN'], '@') !== false) { ?>
								html: '<?=str_replace('#EMAIL#', $arParams['MAILBOX']['LOGIN'], CUtil::JSEscape(GetMessage('INTR_MAIL_REPLACE_WARNING'))); ?>'
								<? } else { ?>
								html: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_REPLACE_WARNING_UN')); ?>'
								<? } ?>
							}),
							buttons: [
								new BX.PopupWindowButton({
									className: 'popup-window-button-decline',
									text: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_MAILBOX_DELETE_SHORT')); ?>',
									events: {
										click: function() {
											this.popupWindow.close();

											doSubmit();
										}
									}
								}),
								new BX.PopupWindowButtonLink({
									text: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_INP_CANCEL')); ?>',
									className: 'popup-window-button-link-cancel',
									events: {
										click: function() {
											this.popupWindow.close();
										}
									}
								})
							]
						});
					}

					replacePopup.show();
				}
				else
				{
					doSubmit();
				}
			};
		}

		if (BX('b24_create_form'))
		{
			var b24CreateForm = BX('b24_create_form');
			var b24CmbForm = new CreateMailboxForm(b24CreateForm, 3);

			BX.bind(b24CreateForm.elements['login'], 'keyup', b24CmbForm.checkName);
			BX.bind(b24CreateForm.elements['login'], 'focus', b24CmbForm.checkName);
			BX.bind(b24CreateForm.elements['login'], 'blur', b24CmbForm.checkName);
			BX.bind(b24CreateForm.elements['domain'], 'change', b24CmbForm.checkName);

			BX.bind(b24CreateForm.elements['password'], 'keyup', b24CmbForm.checkPassword);
			BX.bind(b24CreateForm.elements['password'], 'blur', b24CmbForm.checkPassword);

			BX.bind(b24CreateForm.elements['password2'], 'keyup', b24CmbForm.checkPassword2);
			BX.bind(b24CreateForm.elements['password2'], 'blur', b24CmbForm.checkPassword2);

			BX.bind(b24CreateForm, 'submit', function(e) {
				e.preventDefault ? e.preventDefault() : e.returnValue = false;
				b24CmbForm.checkAndSubmit(e);
				return false;
			});
			BX.bind(BX('b24_create_save'), 'click', function(e) {
				e.preventDefault ? e.preventDefault() : e.returnValue = false;
				b24CmbForm.checkAndSubmit(e);
				return false;
			});

			BX.bind(BX('b24_create_clear'), 'click', b24CmbForm.clean);
		}

		if (BX('domain_create_form') && BX('domain_create_save'))
		{
			var domainCreateForm = BX('domain_create_form');
			var domainCmbForm = new CreateMailboxForm(domainCreateForm);

			BX.bind(domainCreateForm.elements['login'], 'keyup', domainCmbForm.checkName);
			BX.bind(domainCreateForm.elements['login'], 'focus', domainCmbForm.checkName);
			BX.bind(domainCreateForm.elements['login'], 'blur', domainCmbForm.checkName);
			BX.bind(domainCreateForm.elements['domain'], 'change', domainCmbForm.checkName);

			BX.bind(domainCreateForm.elements['password'], 'keyup', domainCmbForm.checkPassword);
			BX.bind(domainCreateForm.elements['password'], 'blur', domainCmbForm.checkPassword);

			BX.bind(domainCreateForm.elements['password2'], 'keyup', domainCmbForm.checkPassword2);
			BX.bind(domainCreateForm.elements['password2'], 'blur', domainCmbForm.checkPassword2);

			BX.bind(domainCreateForm, 'submit', function(e) {
				e.preventDefault ? e.preventDefault() : e.returnValue = false;
				domainCmbForm.checkAndSubmit(e);
				return false;
			});
			BX.bind(BX('domain_create_save'), 'click', function(e) {
				e.preventDefault ? e.preventDefault() : e.returnValue = false;
				domainCmbForm.checkAndSubmit(e);
				return false;
			});

			BX.bind(BX('domain_create_clear'), 'click', domainCmbForm.clean);
		}

		function EditMailboxForm(createForm, setupForm, deleteForm, checkForm, passwordForm, editForm)
		{
			var self = this;

			var setupForm    = setupForm;
			var createForm   = createForm;

			var deleteForm   = deleteForm;
			var checkForm    = checkForm;
			var passwordForm = passwordForm;
			var editForm     = editForm;

			var passCont = passwordForm.elements['password'].parentNode;
			var passHint = BX.findChild(passCont, {attr: {name: 'pass-hint'}}, true, false);

			if (passwordForm.elements['password2'])
			{
				var pass2Cont = passwordForm.elements['password2'].parentNode;
				var pass2Hint = BX.findChild(pass2Cont, {attr: {name: 'pass2-hint'}}, true, false);
			}

			this.status = function(e)
			{
				var alert = BX.findChild(setupForm, {attr: {name: 'post-dialog-alert'}}, true, false);

				BX.hide(alert, 'block');
				setPost.animCurrent();

				BX.addClass(checkForm, 'webform-button-active webform-button-wait');

				BX.ajax({
					method: 'POST',
					url: '<?=$this->__component->GetPath() ; ?>/ajax.php?page=home&act=check',
					data: '<?=bitrix_sessid_get(); ?>',
					dataType: 'json',
					onsuccess: function(json) {

						BX.removeClass(checkForm, 'webform-button-active webform-button-wait');

						var statusBlock = BX.findChild(setupForm, {attr: {name: 'status-block'}}, true, false);
						var statusText = BX.findChild(setupForm, {attr: {name: 'status-text'}}, true, false);
						var statusAlert = BX.findChild(setupForm, {attr: {name: 'status-alert'}}, true, false);
						var statusInfo = BX.findChild(setupForm, {attr: {name: 'status-info'}}, true, false);
						
						statusText.innerHTML = '<?=str_replace(
							'#DATE#',
							CUtil::JSEscape(GetMessage('INTR_MAIL_CHECK_JUST_NOW')),
							CUtil::JSEscape(GetMessage('INTR_MAIL_CHECK_TEXT'))
						); ?>:';

						if (json.result == 'ok')
						{
							BX.removeClass(statusBlock, 'post-status-error');
							BX.adjust(statusAlert, {text: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_CHECK_SUCCESS')); ?>'});
							BX.adjust(statusInfo, {style: {display: 'none'}});
						}
						else
						{
							BX.addClass(statusBlock, 'post-status-error');
							BX.adjust(statusAlert, {text: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_CHECK_ERROR')); ?>'});
							BX.adjust(statusInfo, {
								props: {title: json.error},
								style: {display: 'inline-block'}
							});
						}
					},
					onfailure: function() {
						BX.removeClass(checkForm, 'webform-button-active webform-button-wait');

						BX.removeClass(alert, 'post-dialog-alert-ok');
						BX.adjust(BX.findChild(alert, {attr: {name: 'post-dialog-alert-text'}}, true, false), {text: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_AJAX_ERROR')); ?>'});
						BX.show(alert, 'block');
						setPost.animCurrent();
					}
				});
			};

			var deletePopup = false;
			this.delete = function(e)
			{
				var alert = BX.findChild(setupForm, {attr: {name: 'post-dialog-alert'}}, true, false);

				BX.hide(alert, 'block');
				setPost.animCurrent();

				if (deletePopup === false)
				{
					deletePopup = new BX.PopupWindow('delete-mailbox', null, {
						closeIcon: {'margin-right': '3px', 'margin-top': '13px'},
						closeByEsc: true,
						overlay: true,
						lightShadow: true,
						titleBar: {content: BX.create('span', {
							attrs: {className: 'mail-alert-top-popup-title'},
							text: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_REMOVE_CONFIRM')); ?>'
						})},
						content: BX.create('div', {
							attrs: {className: 'mail-alert-popup-del-box mail-alert-popup-del-text'},
							html: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_REMOVE_CONFIRM_TEXT')); ?>'
						}),
						buttons: [
							new BX.PopupWindowButton({
								className: 'popup-window-button-decline',
								text: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_MAILBOX_DELETE_SHORT')); ?>',
								events: {
									click: function() {
										this.popupWindow.close();

										BX.addClass(deleteForm, 'webform-button-decline-active webform-button-wait');

										BX.ajax({
											method: 'POST',
											url: '<?=$this->__component->GetPath() ; ?>/ajax.php?page=home&act=delete',
											data: '<?=bitrix_sessid_get(); ?>',
											dataType: 'json',
											onsuccess: function(json) {
												BX.removeClass(deleteForm, 'webform-button-decline-active webform-button-wait');
												if (json.result != 'error')
												{
													hasMailbox = false;

													if (BX('b24_block_descr_mailbox'))
													{
														BX.hide(BX('b24_block_descr_mailbox'), 'block');
														BX.show(BX('b24_block_descr_nomailbox'), 'block');
													}
													if (BX('domain_block_descr_mailbox'))
													{
														BX.hide(BX('domain_block_descr_mailbox'), 'block');
														BX.show(BX('domain_block_descr_nomailbox'), 'block');
													}
													if (BX('imap_block_descr_mailbox'))
													{
														BX.hide(BX('imap_block_descr_mailbox'), 'block');
														BX.show(BX('imap_block_descr_nomailbox'), 'block');
													}

													if (BX('b24_block_replace_warning'))
														BX.hide(BX('b24_block_replace_warning'), 'block');
													if (BX('domain_block_replace_warning'))
														BX.hide(BX('domain_block_replace_warning'), 'block');
													if (BX('imap_block_replace_warning'))
														BX.hide(BX('imap_block_replace_warning'), 'block');

													BX.show(BX('mail-info-message'), 'block');

													BX.hide(setupForm, 'block');
													BX.show(createForm, 'block');
													setPost.animCurrent();
												}
												else
												{
													BX.removeClass(alert, 'post-dialog-alert-ok');
													BX.adjust(BX.findChild(alert, {attr: {name: 'post-dialog-alert-text'}}, true, false), {text: json.error});
													BX.show(alert, 'block');
													setPost.animCurrent();
												}
											},
											onfailure: function() {
												BX.removeClass(deleteForm, 'webform-button-decline-active webform-button-wait');

												BX.removeClass(alert, 'post-dialog-alert-ok');
												BX.adjust(BX.findChild(alert, {attr: {name: 'post-dialog-alert-text'}}, true, false), {text: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_AJAX_ERROR')); ?>'});
												BX.show(alert, 'block');
												setPost.animCurrent();
											}
										});
									}
								}
							}),
							new BX.PopupWindowButtonLink({
								text: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_INP_CANCEL')); ?>',
								className: 'popup-window-button-link-cancel',
								events: {
									click: function() {
										this.popupWindow.close();
									}
								}
							})
						]
					});
				}

				deletePopup.show();
			};

			this.checkPassword = function(e)
			{
				if (!passwordForm.elements['password'].value.match(/^[\x21\x23-\x26\x28-\x2E\x30-\x3B\x40-\x5A\x5E\x5F\x61-\x7A]*$/))
				{
					BX.removeClass(passCont, 'mail-set-ok');
					BX.addClass(passCont, 'mail-set-error');
					BX.adjust(passHint, {text: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_INP_PASS_BAD')); ?>'});
				}
				else if (passwordForm.elements['password'].value.length < 6)
				{
					if (typeof e == 'object' && e.type == 'keyup')
					{
						if (!BX.hasClass(passCont, 'mail-set-ok') && !BX.hasClass(passCont, 'mail-set-error'))
							return;
					}

					BX.removeClass(passCont, 'mail-set-ok');
					BX.addClass(passCont, 'mail-set-error');
					BX.adjust(passHint, {
						text: passwordForm.elements['password'].value.length == 0
							? '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_INP_PASS_EMPTY')); ?>'
							: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_INP_PASS_SHORT')); ?>'
					});
				}
				else if (passwordForm.elements['password'].value.length > 20)
				{
					BX.removeClass(passCont, 'mail-set-ok');
					BX.addClass(passCont, 'mail-set-error');
					BX.adjust(passHint, {text: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_INP_PASS_LONG')); ?>'});
				}
				else if (passwordForm.elements['password'].value == passwordForm.elements['login'].value)
				{
					BX.removeClass(passCont, 'mail-set-ok');
					BX.addClass(passCont, 'mail-set-error');
					BX.adjust(passHint, {text: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_INP_PASS_LIKELOGIN')); ?>'});
				}
				else
				{
					BX.removeClass(passCont, 'mail-set-error');
					BX.addClass(passCont, 'mail-set-ok');
					BX.cleanNode(passHint);
				}

				if (passwordForm.elements['password2'].value.length > 0)
					self.checkPassword2(e);
			};

			this.checkPassword2 = function(e)
			{
				var pass  = passwordForm.elements['password'].value;
				var pass2 = passwordForm.elements['password2'].value;

				if (pass2.length == 0 || pass2 != pass)
				{
					var error = '';

					if (pass2.length == 0)
					{
						if (typeof e == 'object' && e.type == 'keyup')
						{
							if (!BX.hasClass(pass2Cont, 'mail-set-ok') && !BX.hasClass(pass2Cont, 'mail-set-error'))
								return;
						}

						error = '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_INP_PASS2_EMPTY')); ?>';
					}
					else
					{
						error = pass.substr(0, pass2.length) == pass2
							? '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_INP_PASS2_SHORT')); ?>'
							: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_INP_PASS2_DIFF')); ?>';
					}

					BX.removeClass(pass2Cont, 'mail-set-ok');
					BX.addClass(pass2Cont, 'mail-set-error');
					BX.adjust(pass2Hint, {text: error});
				}
				else
				{
					BX.removeClass(pass2Cont, 'mail-set-error');
					BX.addClass(pass2Cont, 'mail-set-ok');
					BX.cleanNode(pass2Hint);
				}
			};

			this.checkImapPassword = function(e)
			{
				if (passwordForm.elements['password'].value.length > 0)
				{
					BX.removeClass(passCont, 'mail-set-error');
					BX.addClass(passCont, 'mail-set-ok');
					BX.cleanNode(passHint);
				}
				else
				{
					if (typeof e == 'object' && e.type == 'keyup')
					{
						if (!BX.hasClass(passCont, 'mail-set-ok') && !BX.hasClass(passCont, 'mail-set-error'))
							return;
					}

					BX.removeClass(passCont, 'mail-set-ok');
					BX.addClass(passCont, 'mail-set-error');
					BX.adjust(passHint, {text: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_INP_PASS_EMPTY')); ?>'});
				}
			};

			this.checkPasswordForm = function(e)
			{
				BX.hide(BX.findChild(setupForm, {attr: {name: 'post-dialog-alert'}}, true, false), 'block');
				setPost.animCurrent();

				self.checkPassword(e);
				self.checkPassword2(e);

				return !BX.hasClass(passCont, 'mail-set-error')
					&& !BX.hasClass(pass2Cont, 'mail-set-error');
			};

			this.checkImapPasswordForm = function(e)
			{
				BX.hide(BX.findChild(setupForm, {attr: {name: 'post-dialog-alert'}}, true, false), 'block');
				setPost.animCurrent();

				self.checkImapPassword(e);

				return !BX.hasClass(passCont, 'mail-set-error');
			};

			this.cleanPassword = function(e)
			{
				passwordForm.elements['password'].value = '';
				if (passwordForm.elements['password2'])
					passwordForm.elements['password2'].value = '';

				BX.removeClass(passCont, 'mail-set-ok');
				BX.removeClass(passCont, 'mail-set-error');

				if (passwordForm.elements['password2'])
				{
					BX.removeClass(pass2Cont, 'mail-set-ok');
					BX.removeClass(pass2Cont, 'mail-set-error');
				}

				if (passwordForm.elements['password2'])
				{
					BX.adjust(passHint, {text: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_INP_PASS_SHORT')); ?>'});
					BX.cleanNode(pass2Hint);
				}
				else
				{
					BX.cleanNode(passHint);
				}
			};

			this.submitPassword = function()
			{
				var formButton = BX.findChild(passwordForm, {attr: {name: 'password-save'}}, true, false);
				var alert = BX.findChild(setupForm, {attr: {name: 'post-dialog-alert'}}, true, false);

				BX.addClass(formButton, 'webform-button-accept-active webform-button-wait');

				var data = {};
				for (var i = 0; i < passwordForm.elements.length; i++)
				{
					if (passwordForm.elements[i].name)
						data[passwordForm.elements[i].name] = passwordForm.elements[i].value;
				}
				BX.ajax({
					method: 'POST',
					url: '<?=$this->__component->GetPath() ; ?>/ajax.php?page=home&act=password',
					data: data,
					dataType: 'json',
					onsuccess: function(json) {
						BX.removeClass(formButton, 'webform-button-accept-active webform-button-wait');

						if (json.result != 'error')
						{
							self.cleanPassword();

							BX.addClass(alert, 'post-dialog-alert-ok');
							BX.adjust(BX.findChild(alert, {attr: {name: 'post-dialog-alert-text'}}, true, false), {text: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_MAILBOX_PASSWORD_SUCCESS')); ?>'});
							BX.show(alert, 'block');
							setPost.animCurrent();
						}
						else
						{
							BX.removeClass(alert, 'post-dialog-alert-ok');
							BX.adjust(BX.findChild(alert, {attr: {name: 'post-dialog-alert-text'}}, true, false), {text: json.error});
							BX.show(alert, 'block');
							setPost.animCurrent();
						}
					},
					onfailure: function() {
						BX.removeClass(formButton, 'webform-button-accept-active webform-button-wait');

						BX.removeClass(alert, 'post-dialog-alert-ok');
						BX.adjust(BX.findChild(alert, {attr: {name: 'post-dialog-alert-text'}}, true, false), {text: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_AJAX_ERROR')); ?>'});
						BX.show(alert, 'block');
						setPost.animCurrent();
					}
				});
			};
		}

		if (BX('b24_setup_form'))
		{
			var b24CreateForm   = BX('b24_create_form');
			var b24SetupForm    = BX('b24_setup_form');
			var b24DeleteForm   = BX('b24_delete_form');
			var b24CheckForm    = BX('b24_check_form');
			var b24PasswordForm = BX('b24_password_form');

			var b24EmbForm = new EditMailboxForm(b24CreateForm, b24SetupForm, b24DeleteForm, b24CheckForm, b24PasswordForm);

			BX.bind(b24PasswordForm.elements['password'], 'keyup', b24EmbForm.checkPassword);
			BX.bind(b24PasswordForm.elements['password'], 'blur', b24EmbForm.checkPassword);

			BX.bind(b24PasswordForm.elements['password2'], 'keyup', b24EmbForm.checkPassword2);
			BX.bind(b24PasswordForm.elements['password2'], 'blur', b24EmbForm.checkPassword2);

			BX.bind(b24DeleteForm, 'click', b24EmbForm.delete);
			BX.bind(b24CheckForm, 'click', b24EmbForm.status);

			BX.bind(b24PasswordForm, 'submit', function(e) {
				e.preventDefault ? e.preventDefault() : e.returnValue = false;
				if (b24EmbForm.checkPasswordForm(e))
					b24EmbForm.submitPassword();
				return false;
			});
			BX.bind(BX('b24_password_save'), 'click', function(e) {
				e.preventDefault ? e.preventDefault() : e.returnValue = false;
				if (b24EmbForm.checkPasswordForm(e))
					b24EmbForm.submitPassword();
				return false;
			});
		}

		if (BX('domain_setup_form'))
		{
			var domainCreateForm   = BX('domain_create_form');
			var domainSetupForm    = BX('domain_setup_form');
			var domainDeleteForm   = BX('domain_delete_form');
			var domainCheckForm    = BX('domain_check_form');
			var domainPasswordForm = BX('domain_password_form');

			var domainEmbForm = new EditMailboxForm(domainCreateForm, domainSetupForm, domainDeleteForm, domainCheckForm, domainPasswordForm);

			BX.bind(domainPasswordForm.elements['password'], 'keyup', domainEmbForm.checkPassword);
			BX.bind(domainPasswordForm.elements['password'], 'blur', domainEmbForm.checkPassword);

			BX.bind(domainPasswordForm.elements['password2'], 'keyup', domainEmbForm.checkPassword2);
			BX.bind(domainPasswordForm.elements['password2'], 'blur', domainEmbForm.checkPassword2);

			BX.bind(domainDeleteForm, 'click', domainEmbForm.delete);
			BX.bind(domainCheckForm, 'click', domainEmbForm.status);

			BX.bind(domainPasswordForm, 'submit', function(e) {
				e.preventDefault ? e.preventDefault() : e.returnValue = false;
				if (domainEmbForm.checkPasswordForm(e))
					domainEmbForm.submitPassword();
				return false;
			});
			BX.bind(BX('domain_password_save'), 'click', function(e) {
				e.preventDefault ? e.preventDefault() : e.returnValue = false;
				if (domainEmbForm.checkPasswordForm(e))
					domainEmbForm.submitPassword();
				return false;
			});
		}

		if (BX('imap_setup_form'))
		{
			var imapCreateForm   = BX('imap_icons');
			var imapSetupForm    = BX('imap_setup_form');
			var imapDeleteForm   = BX('imap_delete_form');
			var imapCheckForm    = BX('imap_check_form');
			var imapPasswordForm = BX('imap_password_form');

			var imapEmbForm = new EditMailboxForm(imapCreateForm, imapSetupForm, imapDeleteForm, imapCheckForm, imapPasswordForm);

			BX.bind(imapPasswordForm.elements['password'], 'keyup', imapEmbForm.checkImapPassword);
			BX.bind(imapPasswordForm.elements['password'], 'blur', imapEmbForm.checkImapPassword);

			BX.bind(imapDeleteForm, 'click', imapEmbForm.delete);
			BX.bind(imapCheckForm, 'click', imapEmbForm.status);

			BX.bind(imapPasswordForm, 'submit', function(e) {
				e.preventDefault ? e.preventDefault() : e.returnValue = false;
				if (imapEmbForm.checkImapPasswordForm(e))
					imapEmbForm.submitPassword();
				return false;
			});
			BX.bind(BX('imap_password_save'), 'click', function(e) {
				e.preventDefault ? e.preventDefault() : e.returnValue = false;
				if (imapEmbForm.checkImapPasswordForm(e))
					imapEmbForm.submitPassword();
				return false;
			});
		}

		function CreateImapMailboxForm(form)
		{
			var self = this;

			var form = form;

			if (form.elements['link'])
			{
				var linkCont = form.elements['link'].parentNode;
				var linkHint = BX.findChild(linkCont, {attr: {name: 'link-hint'}}, true, false);
			}

			if (form.elements['server'])
			{
				var serverCont = form.elements['server'].parentNode;
				var portCont = form.elements['port'].parentNode;
				var serverHint = BX.findChild(serverCont, {attr: {name: 'server-hint'}}, true, false);
			}

			var loginCont = form.elements['login'].parentNode;
			var loginHint = BX.findChild(loginCont, {attr: {name: 'login-hint'}}, true, false);

			var passCont = form.elements['password'].parentNode;
			var passHint = BX.findChild(passCont, {attr: {name: 'pass-hint'}}, true, false);

			this.checkLink = function(e)
			{
				if (form.elements['link'].value.length > 0 && form.elements['link'].value != form.elements['link'].getAttribute('data-placeholder'))
				{
					if (form.elements['link'].value.match(/^https?:\/\/([a-z0-9](-*[a-z0-9])*\.?)+(:[0-9]+)?(\/.*)?$/i))
					{
						BX.removeClass(linkCont, 'post-dialog-inp-error');
						BX.addClass(linkCont, 'post-dialog-inp-confirm');
						BX.cleanNode(linkHint);
					}
					else
					{
						if (typeof e == 'object' && e.type == 'keyup')
						{
							if ('http://'.indexOf(form.elements['link'].value) == 0 || 'https://'.indexOf(form.elements['link'].value) == 0)
							{
								if (!BX.hasClass(linkCont, 'post-dialog-inp-confirm') && !BX.hasClass(linkCont, 'post-dialog-inp-error'))
									return;
							}
						}

						BX.removeClass(linkCont, 'post-dialog-inp-confirm');
						BX.addClass(linkCont, 'post-dialog-inp-error');
						BX.adjust(linkHint, {text: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_INP_LINK_BAD')); ?>'});
					}
				}
				else
				{
					if (typeof e == 'object' && e.type == 'keyup')
					{
						if (!BX.hasClass(linkCont, 'post-dialog-inp-confirm') && !BX.hasClass(linkCont, 'post-dialog-inp-error'))
							return;
					}

					BX.removeClass(linkCont, 'post-dialog-inp-confirm');
					BX.addClass(linkCont, 'post-dialog-inp-error');
					BX.adjust(linkHint, {text: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_INP_LINK_EMPTY')); ?>'});
				}
			};

			this.checkServer = function(e)
			{
				if (form.elements['server'].value.length > 0 && form.elements['server'].value != form.elements['server'].getAttribute('data-placeholder'))
				{
					if (form.elements['server'].value.match(/^([a-z0-9](-*[a-z0-9])*\.?)+$/i))
					{
						BX.removeClass(serverCont, 'post-dialog-inp-error');
						BX.addClass(serverCont, 'post-dialog-inp-confirm');
						BX.cleanNode(serverHint);
					}
					else
					{
						BX.removeClass(serverCont, 'post-dialog-inp-confirm');
						BX.addClass(serverCont, 'post-dialog-inp-error');
						BX.adjust(serverHint, {text: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_INP_SERVER_BAD')); ?>'});
					}
				}
				else
				{
					if (typeof e == 'object' && e.type == 'keyup')
					{
						if (!BX.hasClass(serverCont, 'post-dialog-inp-confirm') && !BX.hasClass(serverCont, 'post-dialog-inp-error'))
							return;
					}

					BX.removeClass(serverCont, 'post-dialog-inp-confirm');
					BX.addClass(serverCont, 'post-dialog-inp-error');
					BX.adjust(serverHint, {text: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_INP_SERVER_EMPTY')); ?>'});
				}
			};

			this.checkPort = function(e)
			{
				var input = form.elements['port'];
				if (input.value.match(/^[0-9]+$/) && input.value > 0 && input.value < 65536 && !BX.hasClass(input, 'post-dialog-inp-placeholder'))
				{
					BX.removeClass(portCont, 'post-dialog-inp-error');
					BX.addClass(portCont, 'post-dialog-inp-confirm');
				}
				else
				{
					BX.removeClass(portCont, 'post-dialog-inp-confirm');
					BX.addClass(portCont, 'post-dialog-inp-error');
				}
			};

			this.checkName = function(e)
			{
				if (form.elements['login'].value.length > 0)
				{
					BX.removeClass(loginCont, 'post-dialog-inp-error');
					BX.addClass(loginCont, 'post-dialog-inp-confirm');
					BX.cleanNode(loginHint);
				}
				else
				{
					if (typeof e == 'object' && e.type == 'keyup')
					{
						if (!BX.hasClass(loginCont, 'post-dialog-inp-confirm') && !BX.hasClass(loginCont, 'post-dialog-inp-error'))
							return;
					}

					BX.removeClass(loginCont, 'post-dialog-inp-confirm');
					BX.addClass(loginCont, 'post-dialog-inp-error');
					BX.adjust(loginHint, {text: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_INP_LOGIN_EMPTY')); ?>'});
				}
			};

			this.checkPassword = function(e)
			{
				var input = form.elements['password'];
				if (input.value.length > 0 || hasMailbox)
				{
					BX.removeClass(passCont, 'post-dialog-inp-error');
					BX.addClass(passCont, 'post-dialog-inp-confirm');
					BX.cleanNode(passHint);
				}
				else
				{
					if (typeof e == 'object' && e.type == 'keyup')
					{
						if (!BX.hasClass(passCont, 'post-dialog-inp-confirm') && !BX.hasClass(passCont, 'post-dialog-inp-error'))
							return;
					}

					BX.removeClass(passCont, 'post-dialog-inp-confirm');
					BX.addClass(passCont, 'post-dialog-inp-error');
					BX.adjust(passHint, {text: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_INP_PASS_EMPTY')); ?>'});
				}
			};

			this.check = function(e)
			{
				BX.hide(BX.findChild(form, {attr: {name: 'post-dialog-alert'}}, true, false), 'block');
				setPost.animCurrent();

				if (form.elements['link'])
					self.checkLink(e);
				if (form.elements['server'])
				{
					self.checkServer(e);
					self.checkPort(e);
				}
				self.checkName(e);
				self.checkPassword(e);

				return !(form.elements['link'] && BX.hasClass(linkCont, 'post-dialog-inp-error'))
					&& !(form.elements['server'] && (BX.hasClass(serverCont, 'post-dialog-inp-error') || BX.hasClass(portCont, 'post-dialog-inp-error')))
					&& !BX.hasClass(loginCont, 'post-dialog-inp-error')
					&& !BX.hasClass(passCont, 'post-dialog-inp-error');
			};

			this.clean = function(e)
			{
				if (form.elements['link'])
				{
					BX.addClass(form.elements['link'], 'post-dialog-inp-placeholder');
					form.elements['link'].value = form.elements['link'].getAttribute('data-placeholder');
				}
				if (form.elements['server'])
				{
					BX.addClass(form.elements['server'], 'post-dialog-inp-placeholder');
					BX.addClass(form.elements['port'], 'post-dialog-inp-placeholder');
					form.elements['server'].value = form.elements['server'].getAttribute('data-placeholder');
					form.elements['port'].value = form.elements['port'].getAttribute('data-placeholder');
				}
				form.elements['login'].value = '';
				form.elements['password'].value = '';

				if (form.elements['link'])
					BX.removeClass(linkCont, 'post-dialog-inp-confirm post-dialog-inp-error');
				if (form.elements['server'])
				{
					BX.removeClass(serverCont, 'post-dialog-inp-confirm post-dialog-inp-error');
					BX.removeClass(portCont, 'post-dialog-inp-confirm post-dialog-inp-error');
				}
				BX.removeClass(loginCont, 'post-dialog-inp-confirm post-dialog-inp-error');
				BX.removeClass(passCont, 'post-dialog-inp-confirm post-dialog-inp-error');

				if (form.elements['link'])
					BX.cleanNode(linkHint);
				if (form.elements['server'])
					BX.cleanNode(serverHint);
				BX.cleanNode(loginHint);
				BX.cleanNode(passHint);
			};

			var replacePopup = false;
			this.submit = function(act)
			{
				if (typeof act == 'undefined')
					act = 'create';

				var doSubmit = function()
				{
					var formButton = BX.findChild(form, {attr: {name: act+'-save'}}, true, false);
					var alert = BX.findChild(form, {attr: {name: 'post-dialog-alert'}}, true, false);

					BX.addClass(formButton, 'webform-button-accept-active webform-button-wait');

					var data = {};
					for (var i = 0; i < form.elements.length; i++)
					{
						if (form.elements[i].name && !BX.hasClass(form.elements[i], 'post-dialog-inp-placeholder'))
							data[form.elements[i].name] = form.elements[i].value;
					}
					BX.ajax({
						method: 'POST',
						url: '<?=$this->__component->GetPath() ; ?>/ajax.php?page=home&act='+act,
						data: data,
						dataType: 'json',
						onsuccess: function(json) {
							if (json.result != 'error')
							{
								if (act == 'create')
								{
									window.location = '?page=success';
								}
								else
								{
									BX.removeClass(formButton, 'webform-button-accept-active webform-button-wait');

									//self.cleanPassword();

									BX.addClass(alert, 'post-dialog-alert-ok');
									BX.adjust(BX.findChild(alert, {attr: {name: 'post-dialog-alert-text'}}, true, false), {text: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_MAILBOX_EDIT_SUCCESS')); ?>'});
									BX.show(alert, 'block');
									setPost.animCurrent();
								}
							}
							else
							{
								BX.removeClass(formButton, 'webform-button-accept-active webform-button-wait');

								BX.removeClass(alert, 'post-dialog-alert-ok');
								BX.adjust(BX.findChild(alert, {attr: {name: 'post-dialog-alert-text'}}, true, false), {text: json.error});
								BX.show(alert, 'block');
								setPost.animCurrent();
							}
						},
						onfailure: function() {
							BX.removeClass(formButton, 'webform-button-accept-active webform-button-wait');

							BX.removeClass(alert, 'post-dialog-alert-ok');
							BX.adjust(BX.findChild(alert, {attr: {name: 'post-dialog-alert-text'}}, true, false), {text: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_FORM_ERROR')); ?>'});
							BX.show(alert, 'block');
							setPost.animCurrent();
						}
					});
				}

				if (act == 'create' && hasMailbox)
				{
					if (replacePopup === false)
					{
						replacePopup = new BX.PopupWindow('replace-mailbox', null, {
							closeIcon: {'margin-right': '3px', 'margin-top': '13px'},
							closeByEsc: true,
							overlay: true,
							lightShadow: true,
							titleBar: {content: BX.create('span', {
								attrs: {className: 'mail-alert-top-popup-title'},
								text: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_REMOVE_CONFIRM')); ?>'
							})},
							content: BX.create('div', {
								attrs: {className: 'mail-alert-popup-del-box mail-alert-popup-del-text'},
								<? if (strpos($arParams['MAILBOX']['LOGIN'], '@') !== false) { ?>
								html: '<?=str_replace('#EMAIL#', $arParams['MAILBOX']['LOGIN'], CUtil::JSEscape(GetMessage('INTR_MAIL_REPLACE_WARNING'))); ?>'
								<? } else { ?>
								html: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_REPLACE_WARNING_UN')); ?>'
								<? } ?>
							}),
							buttons: [
								new BX.PopupWindowButton({
									className: 'popup-window-button-decline',
									text: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_MAILBOX_DELETE_SHORT')); ?>',
									events: {
										click: function() {
											this.popupWindow.close();

											doSubmit();
										}
									}
								}),
								new BX.PopupWindowButtonLink({
									text: '<?=CUtil::JSEscape(GetMessage('INTR_MAIL_INP_CANCEL')); ?>',
									className: 'popup-window-button-link-cancel',
									events: {
										click: function() {
											this.popupWindow.close();
										}
									}
								})
							]
						});
					}

					replacePopup.show();
				}
				else
				{
					doSubmit();
				}
			};
		}

		if (BX('create_imap'))
		{
			var forms = BX.findChildren(BX('create_imap'), {attr: {name: 'create-imap'}}, true, true);
			var imapForms = {};
			var imapCmbForms = {};
			for (var i in forms)
			{
				imapForms[i] = BX.findChild(forms[i], {tag: 'form'}, true, false);
				imapCmbForms[i] = new CreateImapMailboxForm(imapForms[i]);

				if (imapForms[i].elements['link'])
				{
					BX.bind(imapForms[i].elements['link'], 'keyup', imapCmbForms[i].checkLink);
					BX.bind(imapForms[i].elements['link'], 'blur', imapCmbForms[i].checkLink);
				}

				if (imapForms[i].elements['server'])
				{
					BX.bind(imapForms[i].elements['server'], 'keyup', imapCmbForms[i].checkServer);
					BX.bind(imapForms[i].elements['server'], 'blur', imapCmbForms[i].checkServer);

					BX.bind(imapForms[i].elements['port'], 'keyup', imapCmbForms[i].checkPort);
					BX.bind(imapForms[i].elements['port'], 'blur', imapCmbForms[i].checkPort);
				}

				BX.bind(imapForms[i].elements['login'], 'keyup', imapCmbForms[i].checkName);
				BX.bind(imapForms[i].elements['login'], 'blur', imapCmbForms[i].checkName);

				BX.bind(imapForms[i].elements['password'], 'keyup', imapCmbForms[i].checkPassword);
				BX.bind(imapForms[i].elements['password'], 'blur', imapCmbForms[i].checkPassword);

				(function(i) {
					BX.bind(imapForms[i], 'submit', function(e) {
						e.preventDefault ? e.preventDefault() : e.returnValue = false;
						if (imapCmbForms[i].check(e))
							imapCmbForms[i].submit();
						return false;
					});
					BX.bind(BX.findChild(imapForms[i], {attr: {name: 'create-save'}}, true, false), 'click', function(e) {
						e.preventDefault ? e.preventDefault() : e.returnValue = false;
						if (imapCmbForms[i].check(e))
							imapCmbForms[i].submit();
						return false;
					});
				})(i);

				BX.addCustomEvent(forms[i], 'HideImapForm', imapCmbForms[i].clean);
			}
		}

		if (BX('edit_imap'))
		{
			var imapForm = BX.findChild(BX('edit_imap'), {tag: 'form'}, true, false);
			var imapCmbForm = new CreateImapMailboxForm(imapForm);

			if (imapForm.elements['link'])
			{
				BX.bind(imapForm.elements['link'], 'keyup', imapCmbForm.checkLink);
				BX.bind(imapForm.elements['link'], 'blur', imapCmbForm.checkLink);
			}

			if (imapForm.elements['server'])
			{
				BX.bind(imapForm.elements['server'], 'keyup', imapCmbForm.checkServer);
				BX.bind(imapForm.elements['server'], 'blur', imapCmbForm.checkServer);

				BX.bind(imapForm.elements['port'], 'keyup', imapCmbForm.checkPort);
				BX.bind(imapForm.elements['port'], 'blur', imapCmbForm.checkPort);
			}

			BX.bind(imapForm.elements['login'], 'keyup', imapCmbForm.checkName);
			BX.bind(imapForm.elements['login'], 'blur', imapCmbForm.checkName);

			BX.bind(imapForm.elements['password'], 'keyup', imapCmbForm.checkPassword);
			BX.bind(imapForm.elements['password'], 'blur', imapCmbForm.checkPassword);

			(function(i) {
				BX.bind(imapForm, 'submit', function(e) {
					e.preventDefault ? e.preventDefault() : e.returnValue = false;
					if (imapCmbForm.check(e))
						imapCmbForm.submit('edit');
					return false;
				});
				BX.bind(BX.findChild(imapForm, {attr: {name: 'edit-save'}}, true, false), 'click', function(e) {
					e.preventDefault ? e.preventDefault() : e.returnValue = false;
					if (imapCmbForm.check(e))
						imapCmbForm.submit('edit');
					return false;
				});
			})(i);

			//BX.addCustomEvent(forms[i], 'HideImapForm', imapCmbForms[i].clean);
		}

	})();

</script>
