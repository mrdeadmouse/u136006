<?
if (
	isset($_GET["user_id"]) 
	&& isset($_GET["checkword"])
)
{
	define("CONFIRM_PAGE", true);
	define("NOT_CHECK_PERMISSIONS", true);
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
	$APPLICATION->SetTitle(GetMessage("BX24_INVITE_DIALOG_CONF_PAGE_TITLE"));

	$APPLICATION->IncludeComponent(
		"bitrix:system.auth.initialize",
		"",
		array(
			"CHECKWORD_VARNAME"=>"checkword",
			"USERID_VARNAME"=>"user_id",
			"AUTH_URL"=>"#SITE_DIR#auth.php",
		),
	false
	);

	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
	die();
}

define("PUBLIC_AJAX_MODE", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$bExtranetInstalled = (IsModuleInstalled("extranet") && strlen(COption::GetOptionString("extranet", "extranet_site")) > 0);

IncludeModuleLangFile(__FILE__);

?><div style="width: 542px; padding: 5px; max-height: 500px; overflow-y: auto; margin: 5px;"><?

$bLimitReached = false;
if (IsModuleInstalled("bitrix24"))
{
	$UserMaxCount = intval(COption::GetOptionString("main", "PARAM_MAX_USERS"));
	if ($UserMaxCount > 0)
	{
		$currentUserCount = 0;
		$currentUserCount = CBitrix24::ActiveUserCount();

		if ($currentUserCount >= $UserMaxCount)
		{
			$bLimitReached = true;
		}
	}
}

if ($bLimitReached)
{
	echo GetMessage("BX24_INVITE_DIALOG_INVITE_LIMIT");
}
elseif (
	(
		CModule::IncludeModule('bitrix24') 
		&& !$USER->CanDoOperation('bitrix24_invite')
	)
	|| (
		!IsModuleInstalled('bitrix24') 
		&& !$USER->CanDoOperation('edit_all_users')
	)
)
{
	echo GetMessage("BX24_INVITE_DIALOG_ACCESS_DENIED");
}
elseif (!CModule::IncludeModule('iblock'))
{
	echo GetMessage("BX24_INVITE_DIALOG_IBLOCK_MODULE");
}
elseif (!CModule::IncludeModule('intranet'))
{
	echo GetMessage("BX24_INVITE_DIALOG_INTRANET_MODULE");
}
else
{
	$SITE_ID = CSite::GetDefSite();
	$rsSite = CSite::GetList($by="sort", $order="asc", array("ID" => $SITE_ID));
	$arSite = $rsSite->GetNext();
	$SITE_DIR = $arSite["DIR"];

	$ID_INVITED = $ID_ADDED = 0;

	$arMailServices = array();
	$bDomainUsersExist = false;
	$bCreateDomainsExist = false;
	$bConnectDomainsExist = false;
	$bMailInstalled = false;

	if (CModule::IncludeModule("mail"))
	{
		$bMailInstalled = true;
		$dbService = \Bitrix\Mail\MailServicesTable::getList(array(
			'filter' => array(
				'ACTIVE' => 'Y', 
				'=SITE_ID' => $SITE_ID
			),
			'order' => array(
				'SORT' => 'ASC',
				'NAME' => 'ASC'
			)
		));

		while ($arService = $dbService->fetch())
		{
			$arMailServices[$arService['ID']] = array(
				'id' => $arService['ID'],
				'type' => $arService['SERVICE_TYPE'],
				'name' => $arService['NAME'],
				'link' => $arService['LINK'],
				'icon' => \Bitrix\Mail\MailServicesTable::getIconSrc($arService['NAME'], $arService['ICON']),
				'server' => $arService['SERVER'],
				'port' => $arService['PORT'],
				'encryption' => $arService['ENCRYPTION'],
				'token' => $arService['TOKEN']
			);

			if ($arService['SERVICE_TYPE'] == 'controller')
			{
				$crDomains = CControllerClient::ExecuteEvent('OnMailControllerGetDomains', array());
				if (
					!empty($crDomains['result']) 
					&& is_array($crDomains['result'])
				)
				{
					$arMailServices[$arService['ID']]['domains'] = $crDomains['result'];
					$bCreateDomainsExist = true;
				}

				$arMailServices[$arService['ID']]['users'] = array();
				$crUsers = CControllerClient::ExecuteEvent('OnMailControllerGetUsers', array());

				if (
					!empty($crUsers['result']) 
					&& is_array($crUsers['result'])
				)
				{
					foreach ($crUsers['result'] as $email)
					{
						list($login, $domain) = explode('@', $email, 2);

						if (empty($arMailServices[$arService['ID']]['users'][$domain]))
						{
							$arMailServices[$arService['ID']]['users'][$domain] = array();
						}
						$arMailServices[$arService['ID']]['users'][$domain][] = $login;
					}

					$rsMailbox = CMailbox::getList(
						array(
							'TIMESTAMP_X' => 'ASC'
						),
						array(
							'ACTIVE' => 'Y',
							'!USER_ID' => 0,
							'SERVICE_ID' => $arMailServices[$arService['ID']]['id']
						)
					);
					while ($arMailbox = $rsMailbox->Fetch())
					{
						list($login, $domain) = explode('@', $arMailbox['LOGIN'], 2);
						if (
							!empty($arMailServices[$arService['ID']]['users'][$domain]) 
							&& ($key = array_search($login, $arMailServices[$arService['ID']]['users'][$domain])) !== false
						)
						{
							array_splice($arMailServices[$arService['ID']]['users'][$domain], $key, 1);
						}
					}

					if (is_array($arMailServices[$arService['ID']]['users']))
					{
						foreach($arMailServices[$arService['ID']]['users'] as $domain => $arLogin)
						{
							if (empty($arLogin))
							{
								unset($arMailServices[$arService['ID']]['users'][$domain]);
							}
						}
					}

					if (
						!$bDomainUsersExist
						&& !empty($arMailServices[$arService['ID']]['users']))
					{
						$bConnectDomainsExist = true;
						$bDomainUsersExist = true;
					}
				}
			}
			else if ($arService['SERVICE_TYPE'] == 'crdomain')
			{
				$crDomains = CControllerClient::ExecuteEvent('OnMailControllerGetMemberDomains', array());
				if (
					!empty($crDomains['result']) 
					&& is_array($crDomains['result'])
				)
				{
					$arMailServices[$arService['ID']]['domains'] = $crDomains['result'];
					$bCreateDomainsExist = true;
				}				

				$arMailServices[$arService['ID']]['users'] = array();
				$crUsers = CControllerClient::ExecuteEvent('OnMailControllerGetMemberUsers', array(
					'DOMAIN' => $arService['SERVER']
				));

				if (
					!empty($crUsers['result']) 
					&& is_array($crUsers['result'])
				)
				{
					foreach ($crUsers['result'] as $login)
					{
						if (empty($arMailServices[$arService['ID']]['users'][$arService['SERVER']]))
						{
							$arMailServices[$arService['ID']]['users'][$arService['SERVER']] = array();
						}
						$arMailServices[$arService['ID']]['users'][$arService['SERVER']][] = $login;
					}

					$rsMailbox = CMailbox::getList(
						array(
							'TIMESTAMP_X' => 'ASC'
						),
						array(
							'ACTIVE' => 'Y',
							'!USER_ID' => 0,
							'SERVICE_ID' => $arMailServices[$arService['ID']]['id']
						)
					);
					while ($arMailbox = $rsMailbox->Fetch())
					{
						list($login, $domain) = explode('@', $arMailbox['LOGIN'], 2);
						if (
							!empty($arMailServices[$arService['ID']]['users'][$domain]) 
							&& ($key = array_search($login, $arMailServices[$arService['ID']]['users'][$domain])) !== false
						)
						{
							array_splice($arMailServices[$arService['ID']]['users'][$domain], $key, 1);
						}
					}
					
					if (is_array($arMailServices[$arService['ID']]['users']))
					{
						foreach($arMailServices[$arService['ID']]['users'] as $domain => $arLogin)
						{
							if (empty($arLogin))
							{
								unset($arMailServices[$arService['ID']]['users'][$domain]);
							}
						}
					}

					if (
						!$bDomainUsersExist
						&& !empty($arMailServices[$arService['ID']]['users']))
					{
						$bDomainUsersExist = true;
					}

					if (!empty($arService['SERVER']))
					{
						$bConnectDomainsExist = true;
					}					
				}
			}
			elseif ($arService['SERVICE_TYPE'] == 'domain')
			{
				$arMailServices[$arService['ID']]['users'] = CMailDomain2::getDomainUsers($arService['TOKEN'], $arService['SERVER'], $error);

				$rsMailbox = CMailbox::getList(
					array(
						'TIMESTAMP_X' => 'ASC'
					),
					array(
						'ACTIVE' => 'Y',
						'!USER_ID' => 0,
						'SERVER_TYPE' => 'domain',
						'SERVICE_ID' => $arService['ID']
					)
				);

				while ($arMailbox = $rsMailbox->fetch())
				{
					list($login, $domain) = explode('@', $arMailbox['LOGIN'], 2);
					if (($key = array_search($login, $arMailServices[$arService['ID']]['users'])) !== false)
					{
						array_splice($arMailServices[$arService['ID']]['users'], $key, 1);
					}
				}

				if (
					!$bDomainUsersExist
					&& !empty($arMailServices[$arService['ID']]['users']))
				{
					$bDomainUsersExist = true;
				}

				if (!empty($arService['SERVER']))
				{
					$bCreateDomainsExist = true;
					$bConnectDomainsExist = true;
				}
			}
		}

		$arCreateMailServicesDomains = array();
		$arConnectMailServicesDomains = array();
		$iCreateDomainsCnt = 0;
		$iConnectDomainsCnt = 0;
		$arConnectMailServicesUsers = array();
		$arMailServicesUsers = array();

		foreach ($arMailServices as $service)
		{
			if (in_array($service['type'], array('controller', 'crdomain')))
			{
				if (!empty($service['domains']))
				{
					$arCreateMailServicesDomains[$service['id']] = array();
					foreach ($service['domains'] as $domain)
					{
						if (strlen($domain) > 0)
						{
							$iCreateDomainsCnt++;
						}
						$arCreateMailServicesDomains[$service['id']][] = $domain;

						if (
							is_array($service['users'])
							&& array_key_exists($domain, $service['users'])
							&& !empty($service['users'][$domain])
						)
						{
							$arConnectMailServicesDomains[$service['id']][] = $domain;
							$arMailServicesUsers[$domain] = $service['users'][$domain];
							$iConnectDomainsCnt++;
						}
					}
				}
				elseif (strlen($service['server']) > 0)
				{
					$arCreateMailServicesDomains[$service['id']] = array($service['server']);
					$iCreateDomainsCnt++;
				}
			}
			elseif ($service['type'] == 'domain')
			{
				if (strlen($service['server']) > 0)
				{
					$iCreateDomainsCnt++;
				}

				$arCreateMailServicesDomains[$service['id']] = array($service['server']);

				if (
					is_array($service['users'])
					&& !empty($service['users'])
				)
				{
					$arConnectMailServicesDomains[$service['id']] = array($service['server']);
					$arMailServicesUsers[$service['server']] = $service['users'];
					$iConnectDomainsCnt++;
				}
			}
		}
	}

	$strError = false;
	$strWarning = false;

	if(
		$_SERVER["REQUEST_METHOD"] === "POST"
		&& check_bitrix_sessid()
	)
	{
		CUtil::JSPostUnescape();
		$strAction = trim($_POST["action"]);
		$strAction = (in_array($strAction, array("invite", "add")) ? $strAction : false);

		if ($strAction == "invite")
		{
			if ($_POST["EMAIL"] == GetMessage("BX24_INVITE_DIALOG_EMAILS_DESCR"))
			{
				$_POST["EMAIL"] = "";
			}

			if (
				intval($_POST["DEPARTMENT_ID"]) <= 0
				&& (
					!isset($_POST["SONET_GROUPS_CODE"])
					|| empty($_POST["SONET_GROUPS_CODE"])
				)
				&& $bExtranetInstalled
			)
			{
				$strError = GetMessage("BX24_INVITE_DIALOG_ERROR_EXTRANET_NO_SONET_GROUP_INVITE");
			}

			if (!$strError)
			{
				$ID_INVITED = CIntranetInviteDialog::RegisterNewUser($SITE_ID, $_POST, $arError);
				if(
					is_array($arError)
					&& count($arError) > 0
				)
				{
					$strError = "";
					$ID_INVITED = 0;

					foreach($arError as $strErrorText)
					{
						if(strlen($strErrorText) > 0)
						{
							$strError .= '<li style="list-style-position: inside;">'.$strErrorText.'</li>';
						}
					}
				}
			}
		}
		elseif(preg_match("/^reinvite_user_id_(\\d+)\$/", $_REQUEST["reinvite"], $match))
		{
			CIntranetInviteDialog::ReinviteUser($SITE_ID, $match[1]);
			require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin_js.php");
		}
		elseif(preg_match("/^reinvite_user_id_extranet_(\\d+)\$/", $_REQUEST["reinvite"], $match))
		{
			CIntranetInviteDialog::ReinviteExtranetUser($SITE_ID, $match[1]);
			require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin_js.php");
		}
		elseif($strAction == "add")
		{
			if (
				intval($_POST["DEPARTMENT_ID"]) <= 0
				&& (
					!isset($_POST["SONET_GROUPS_CODE"])
					|| empty($_POST["SONET_GROUPS_CODE"])
				)
				&& $bExtranetInstalled
			)
			{
				$strError = GetMessage("BX24_INVITE_DIALOG_ERROR_EXTRANET_NO_SONET_GROUP_ADD");
			}

			if ($bMailInstalled)
			{
				if (
					isset($_POST["ADD_MAILBOX_PASSWORD"])
					&& $_POST['ADD_MAILBOX_PASSWORD'] != $_POST['ADD_MAILBOX_PASSWORD_CONFIRM']
				)
				{
					$strError = $MESS["BX24_INVITE_DIALOG_WARNING_CREATE_MAILBOX_ERROR"]." ".GetMessage("BX24_INVITE_DIALOG_WARNING_MAILBOX_PASSWORD_CONFIRM");
				}
				else
				{
					require_once($_SERVER['DOCUMENT_ROOT']."/bitrix/components/bitrix/intranet.mail.setup/helper.php");

					if (
						isset($_POST["ADD_MAILBOX_ACTION"])
						&& $_POST["ADD_MAILBOX_ACTION"] == "create"
					)
					{
						$arMailboxResult = CIntranetMailSetupHelper::createMailbox(
							false,
							false, 
							$_POST['ADD_MAILBOX_SERVICE'],
							$_POST['ADD_MAILBOX_DOMAIN'], $_POST['ADD_MAILBOX_USER'],
							$_POST['ADD_MAILBOX_PASSWORD'],
							$strError
						);

						if ($strError)
						{
							$strError = GetMessage("BX24_INVITE_DIALOG_WARNING_CREATE_MAILBOX_ERROR")." ".$strError;
						}						
					}
				}
			}					

			if (!$strError)
			{
				$bEmptyEmail = (empty($_POST["ADD_EMAIL"]));

				$ID_ADDED = CIntranetInviteDialog::AddNewUser($SITE_ID, $_POST, $strError);

				if ($ID_ADDED)
				{
					// mailbox
					if ($bMailInstalled)
					{
						if (
							isset($_POST["ADD_MAILBOX_ACTION"])
							&& in_array($_POST["ADD_MAILBOX_ACTION"], array('create', 'connect'))
						)
						{
							$arMailboxResult = CIntranetMailSetupHelper::createMailbox(
								true,
								$ID_ADDED,
								$_POST['ADD_MAILBOX_SERVICE'],
								$_POST['ADD_MAILBOX_DOMAIN'], $_POST['ADD_MAILBOX_USER'],
								null,
								$strError
							);

							if (!$strError)
							{
								$newEmail  = $arMailboxResult;
							}
							else
							{
								CUser::Delete($ID_ADDED);
								$strError = GetMessage("BX24_INVITE_DIALOG_WARNING_CREATE_MAILBOX_ERROR")." ".$strError;
							}

							// update email?
						}
					}
				}				
			}			
		}

		if (
			!$strError
			&& ($ID_ADDED || $ID_INVITED)
		)
		{
			if ($ID_ADDED)
			{
				$arUserId = array($ID_ADDED);
			}
			else
			{
				$arUserId = $ID_INVITED;
			}
			$strError = CIntranetInviteDialog::RequestToSonetGroups($arUserId, $_POST["SONET_GROUPS_CODE"], $_POST["SONET_GROUPS_NAME"], (intval($_POST["DEPARTMENT_ID"]) <= 0));
		}

		if ($strError)
		{
			ob_end_clean();
			echo CUtil::PhpToJsObject(array(
				'ERROR' => $strError
			));
			die();
		}
		else
		{
			ob_end_clean();
			$arResult = array(
				'MESSAGE' => GetMessage("BX24_INVITE_DIALOG_".($strAction == "invite" ? "INVITED" : "ADDED"), array("#SITE_DIR#" => $SITE_DIR))
			);
			if ($strWarning)
			{
				$arResult["WARNING"] = $strWarning;
			}
			echo CUtil::PhpToJsObject($arResult);
			die();
		}		
	}

	function inviteDialogDrawTabContentHeader($action, $arStructure, $iDepartmentID, $iStructureCount, $arSonetGroups, $arSonetGroupsExtranet, $arSonetGroupsLast, $bExtranetInstalled = false)
	{
		if ($action != 'invite')
		{
			$action = 'add';
		}

		?><div id="invite-dialog-<?=$action?>-usertype-block-employee" class="invite-dialog-inv-block" style="display: block;"><?=GetMessage(
			'BX24_INVITE_DIALOG_'.($action == 'invite' ? 'INVITE' : 'ADD').'_DEPARTMENT_PATTERN',
			array(
				'#TITLE#' => ($bExtranetInstalled ? '<a href="javascript:void(0);" id="invite-dialog-'.$action.'-usertype-employee-link" class="invite-dialog-inv-link">'.GetMessage('BX24_INVITE_DIALOG_EMPLOYEE').'</a>' : GetMessage('BX24_INVITE_DIALOG_EMPLOYEE')),
				'#DEPARTMENT#' => (
					$iStructureCount > 1 
						? '<a href="javascript:void(0);" id="invite-dialog-'.$action.'-structure-link" class="invite-dialog-inv-link">'.htmlspecialcharsbx($arStructure["DATA"][$iDepartmentID > 0 ? $iDepartmentID : $arStructure["TREE"][0][0]]["NAME"]).'</a>' 
						: htmlspecialcharsbx($arStructure["DATA"][$arStructure["TREE"][0][0]]["NAME"])
				),
				'#SONETGROUP#' => '<a href="javascript:void(0);" id="invite-dialog-'.$action.'-sonetgroup-link" class="invite-dialog-inv-link">'.GetMessage('BX24_INVITE_DIALOG_SONETGROUP').'</a>'
			)
		)?></div><?
		?><input name="DEPARTMENT_ID" type="hidden" value="<?=($iDepartmentID > 0 ? $iDepartmentID : $arStructure["DATA"][$arStructure["TREE"][0][0]]["ID"])?>" id="invite-dialog-<?=$action?>-department-id"><?

		if ($bExtranetInstalled)
		{
			?><div id="invite-dialog-<?=$action?>-usertype-block-extranet" class="invite-dialog-inv-block" style="display: none;"><?
				?><?=GetMessage(
					'BX24_INVITE_DIALOG_'.($action == 'invite' ? 'INVITE' : 'ADD').'_GROUP_PATTERN',
					array(
						'#TITLE#' => '<a href="javascript:void(0);" id="invite-dialog-'.$action.'-usertype-extranet-link" class="invite-dialog-inv-link">'.GetMessage('BX24_INVITE_DIALOG_EXTRANET').'</a>'
					)
				)?><?
			?></div><?
		}

		$arUserTypeSuffix = array("");
		if ($bExtranetInstalled)
		{
			$arUserTypeSuffix[] = "-extranet";
		}
		
		foreach($arUserTypeSuffix as $userTypeSuffix)
		{
			$selectorName = randString(6);

			?><div class="feed-add-post-destination-wrap invite-dialog-sonetgroup-wrap" id="invite-dialog-<?=$action.$userTypeSuffix?>-sonetgroup-container-post" style="display: none;" data-selector-name="<?=$selectorName?>">
				<span id="invite-dialog-<?=$action.$userTypeSuffix?>-sonetgroup-item-post"></span>
				<span class="feed-add-destination-input-box" id="invite-dialog-<?=$action.$userTypeSuffix?>-sonetgroup-input-box-post">
					<input type="text" value="" class="feed-add-destination-inp" id="invite-dialog-<?=$action.$userTypeSuffix?>-sonetgroup-input-post">
				</span>
				<a href="#" class="feed-add-destination-link" id="invite-dialog-<?=$action.$userTypeSuffix?>-sonetgroup-tag-post"><?=GetMessage("BX24_INVITE_DIALOG_DEST_LINK_1")?></a><?

				?><script type="text/javascript">
					<?
					$arSonetGroupsTmp = ($userTypeSuffix == "-extranet" ? $arSonetGroupsExtranet : $arSonetGroups);
					?>

					BX.ready(function() {
						BX.SocNetLogDestination.init({
							'name' : '<?=$selectorName?>',
							'searchInput' : BX('invite-dialog-<?=$action.$userTypeSuffix?>-sonetgroup-input-post'),
							'extranetUser' :  false,
							'allowAddSocNetGroup': false,
							'bindMainPopup' : {
								'node' : BX('invite-dialog-<?=$action.$userTypeSuffix?>-sonetgroup-container-post'),
								'offsetTop' : '5px',
								'offsetLeft': '15px'
							},
							'bindSearchPopup' : {
								'node' : BX('invite-dialog-<?=$action.$userTypeSuffix?>-sonetgroup-container-post'),
								'offsetTop' : '5px',
								'offsetLeft': '15px'
							},
							'callback' : {
								'select' : BX.InviteDialog.selectCallback,
								'unSelect' : BX.InviteDialog.unSelectCallback,
								'openDialog' : BX.InviteDialog.openDialogCallback,
								'closeDialog' : BX.InviteDialog.closeDialogCallback,
								'openSearch' : BX.InviteDialog.openDialogCallback,
								'closeSearch' : BX.InviteDialog.closeDialogCallback
							},
							'items' : {
								'users' : {},
								'groups' : {},
								'sonetgroups' : <?=(empty($arSonetGroupsTmp)? '{}': CUtil::PhpToJSObject($arSonetGroupsTmp))?>,
								'department' : {},
								'departmentRelation' : {}
							},
							'itemsLast' : {
								'users' : {},
								'sonetgroups' : <?=(empty($arSonetGroupsLast)? '{}': CUtil::PhpToJSObject($arSonetGroupsLast))?>,
								'department' : {},
								'groups' : {}
							},
							'itemsSelected' : {}
						});
						BX.bind(BX('invite-dialog-<?=$action.$userTypeSuffix?>-sonetgroup-input-post'), 'keyup', BX.InviteDialog.search);
						BX.bind(BX('invite-dialog-<?=$action.$userTypeSuffix?>-sonetgroup-input-post'), 'keydown', BX.InviteDialog.searchBefore);
						BX.bind(BX('invite-dialog-<?=$action.$userTypeSuffix?>-sonetgroup-tag-post'), 'click', function(e) {
							if (BX.SocNetLogDestination.popupWindow != null)
							{
								BX.SocNetLogDestination.popupWindow.setBindElement( BX('invite-dialog-<?=$action.$userTypeSuffix?>-sonetgroup-container-post'));
							}
							BX.SocNetLogDestination.openDialog('<?=$selectorName?>');
							BX.PreventDefault(e);
						});
						BX.bind(BX('invite-dialog-<?=$action.$userTypeSuffix?>-sonetgroup-container-post'), 'click', function(e) {
							if (BX.SocNetLogDestination.popupWindow != null)
							{
								BX.SocNetLogDestination.popupWindow.setBindElement( BX('invite-dialog-<?=$action.$userTypeSuffix?>-sonetgroup-container-post'));
							}
							BX.SocNetLogDestination.openDialog('<?=$selectorName?>');
							BX.PreventDefault(e);
						});
					});
				</script>
			</div><?
		}
	}

	if (!$strError)
	{

		$GLOBALS["APPLICATION"]->ShowAjaxHead();
		CModule::IncludeModule("socialnetwork");
		CJSCore::Init(array('socnetlogdest'));
		$GLOBALS["APPLICATION"]->AddHeadScript("/bitrix/js/intranet/invite-dialog.js");
		$GLOBALS["APPLICATION"]->SetAdditionalCSS("/bitrix/components/bitrix/main.post.form/templates/.default/style.css");

		?><script type="text/javascript">

			BX.message({
				inviteDialogTitleEmployee: '<?=GetMessageJS('BX24_INVITE_DIALOG_EMPLOYEE')?>',
				inviteDialogTitleExtranet: '<?=GetMessageJS('BX24_INVITE_DIALOG_EXTRANET')?>',
				inviteDialogDestLink1: '<?=GetMessageJS('BX24_INVITE_DIALOG_DEST_LINK_1')?>',
				inviteDialogDestLink2: '<?=GetMessageJS('BX24_INVITE_DIALOG_DEST_LINK_2')?>',
				inviteDialogSubmitUrl: '<?=CUtil::JSEscape(BX_ROOT."/tools/intranet_invite_dialog.php")?>'
			});

			var inviteDialogDepartmentPopup = null;
			var inviteDialogActionPopup = null;

			onInviteDialogSectionsSelect = function(oData)
			{
				var inviteDialogStructureLink = (BX.InviteDialog.lastTab == 'invite' ? inviteDialogInviteStructureLink : inviteDialogAddStructureLink);
				var inviteDialogDepartmentIdField = BX('invite-dialog-' + BX.InviteDialog.lastTab + '-department-id');

				if (
					oData.id !== undefined
					&& oData.name !== undefined
				)
				{
					inviteDialogStructureLink.innerHTML = oData.name;
					inviteDialogDepartmentIdField.value = oData.id;

					inviteDialogDepartmentPopup.close();
				}
			}

		</script><?
	}

	$iDepartmentID = (is_array($_POST) && array_key_exists("arParams", $_POST) && is_array($_POST["arParams"]) && array_key_exists("UF_DEPARTMENT", $_POST["arParams"]) ? intval($_POST["arParams"]["UF_DEPARTMENT"]) : 0);
	$arStructure = CIntranetUtils::getSubStructure(0, ($iDepartmentID > 0 ? false : 1));
	if (!array_key_exists($iDepartmentID, $arStructure["DATA"]))
	{
		$iDepartmentID = 0;
	}

	$iStructureCount = count(CIntranetUtils::GetDeparmentsTree());

	CModule::IncludeModule('socialnetwork');

	$cacheTtl = defined("BX_COMP_MANAGED_CACHE") ? 3153600 : 3600*4;
	$cacheId = 'invite_dialog_sonetgroups_'.$SITE_ID.'_'.($bExtranetInstalled ? 'Y' : 'N').'_'.$GLOBALS["USER"]->GetID();
	$cacheDir = '/intranet/invite_dialog/'.$SITE_ID.'/'.$GLOBALS["USER"]->GetID();

	$obCache = new CPHPCache;
	if ($obCache->InitCache($cacheTtl, $cacheId, $cacheDir))
	{
		$arCacheVars = $obCache->GetVars();
		$arSonetGroups = $arCacheVars["SONET_GROUPS"];
		$arSonetGroupsExtranet = $arCacheVars["SONET_GROUPS_EXTRANET"];
	}
	else
	{
		$obCache->StartDataCache();
		$arSonetGroups = CSocNetLogDestination::GetSocnetGroup();
		$arSonetGroupsExtranet = array();

		if (
			$bExtranetInstalled 
			&& CModule::IncludeModule("extranet")
		)
		{
			$arSonetGroupsExtranet = CSocNetLogDestination::GetSocnetGroup(
				array(
					"site_id" => CExtranet::GetExtranetSiteID()
				)
			);
		}
		if(defined("BX_COMP_MANAGED_CACHE"))
		{
			$GLOBALS["CACHE_MANAGER"]->StartTagCache($cacheDir);
			foreach($arSonetGroups as $val)
			{
				$GLOBALS["CACHE_MANAGER"]->RegisterTag("sonet_group_".$val["entityId"]);
			}
			$GLOBALS["CACHE_MANAGER"]->RegisterTag("sonet_user2group_U".$GLOBALS["USER"]->GetID());
			$GLOBALS["CACHE_MANAGER"]->EndTagCache();
		}
		$obCache->EndDataCache(
			array(
				"SONET_GROUPS" => $arSonetGroups,
				"SONET_GROUPS_EXTRANET" => $arSonetGroupsExtranet
			)
		);
	}

	if (
		is_array($arSonetGroupsExtranet) 
		&& !empty($arSonetGroupsExtranet)
	)
	{
		$arExtranetGroupID = array();
		foreach($arSonetGroupsExtranet as $arSonetGroupTmp)
		{
			if (
				is_array($arSonetGroupTmp) 
				&& array_key_exists("entityId", $arSonetGroupTmp) 
				&& intval($arSonetGroupTmp["entityId"]) > 0
			)
			{
				$arExtranetGroupID[] = intval($arSonetGroupTmp["entityId"]);
			}
		}
	}

	$arSonetGroupsLast = CSocNetLogDestination::GetLastSocnetGroup();

	?><? $APPLICATION->IncludeComponent(
		"bitrix:intranet.user.selector.new", ".default", array(
			"MULTIPLE" => "N",
			"NAME" => "INVITE_DEPARTMENT",
			"VALUE" => 0,
			"POPUP" => "Y",
			"INPUT_NAME" => "UF_DEPARTMENT",
			"ON_SECTION_SELECT" => "onInviteDialogSectionsSelect",
			"SITE_ID" => $SITE_ID,
			"SHOW_STRUCTURE_ONLY" => "Y",
			"SHOW_EXTRANET_USERS" => "NONE"
		), null, array("HIDE_ICONS" => "Y")
	);
	?>
	<div class="intranet-tabs-box" id="intranet-dialog-tabs"><?
		?><div class="webform-round-corners webform-error-block" id="invite-dialog-error-block" style="display: none;">
			<div class="webform-corners-top"><div class="webform-left-corner"></div><div class="webform-right-corner"></div></div>
			<div class="webform-content" id="invite-dialog-error-content"><?=$strError?></div>
			<div class="webform-corners-bottom"><div class="webform-left-corner"></div><div class="webform-right-corner"></div></div>
		</div><?
		?><div class="intranet-tabs">
			<span class="intranet-tab<?=(in_array($strAction, array(false, "invite")) ? " intranet-tab-selected" : "")?>" id="intranet-dialog-tab-invite" data-action="invite">
				<?=GetMessage('BX24_INVITE_DIALOG_TAB_INVITE_TITLE')?>
			</span>
			<span class="intranet-tab<?=(in_array($strAction, array("add")) ? " intranet-tab-selected" : "")?>" id="intranet-dialog-tab-add" data-action="add">
				<?=GetMessage('BX24_INVITE_DIALOG_TAB_ADD_TITLE')?>
			</span>
		</div>
		<div class="intranet-tabs-hr"><i></i></div>
		<div class="intranet-tabs-content">

			<div class="intranet-tab-content<?=(in_array($strAction, array(false, "invite")) ? " intranet-tab-content-selected" : "")?>" id="intranet-dialog-tab-content-invite" data-user-type="employee"><?

				?><form method="POST" action="<?echo BX_ROOT."/tools/intranet_invite_dialog.php"?>" id="INVITE_DIALOG_FORM"><?

					inviteDialogDrawTabContentHeader('invite', $arStructure, $iDepartmentID, $iStructureCount, $arSonetGroups, $arSonetGroupsExtranet, $arSonetGroupsLast, $bExtranetInstalled);

					?><div class="invite-dialog-inv-form"><?
						?><table class="invite-dialog-inv-form-table">
							<tr>
								<td class="invite-dialog-inv-form-l" style="vertical-align: top;">
									<label for="EMAIL"><?echo GetMessage("BX24_INVITE_DIALOG_EMAIL_SHORT")?></label>
								</td>
								<td class="invite-dialog-inv-form-r">
									<textarea 
										type="text" 
										name="EMAIL" 
										id="EMAIL" 
										class="invite-dialog-inv-form-textarea"
										onblur="if(this.value == ''){BX.removeClass(this, 'invite-dialog-inv-form-textarea-active'); this.value = this.value.replace(new RegExp(/^$/), '<?=GetMessage("BX24_INVITE_DIALOG_EMAILS_DESCR")?>')}" 
										onfocus="BX.addClass(this, 'invite-dialog-inv-form-textarea-active'); this.value = this.value.replace('<?=GetMessage("BX24_INVITE_DIALOG_EMAILS_DESCR")?>', '')"
									><?=(strlen($_POST["EMAIL"]) > 0 ? htmlspecialcharsbx($_POST["EMAIL"]) : GetMessage("BX24_INVITE_DIALOG_EMAILS_DESCR"));?></textarea>
								</td>
							</tr>
						</table><?
					?></div><?

					?><div class="invite-dialog-inv-text-bold"><label for="MESSAGE_TEXT"><?echo GetMessage("BX24_INVITE_DIALOG_INVITE_MESSAGE_TITLE")?></label></div>
					<textarea type="text" name="MESSAGE_TEXT" id="MESSAGE_TEXT" class="invite-dialog-inv-form-textarea invite-dialog-inv-form-textarea-active" style="width: 500px;"><?
						if (isset($_POST["MESSAGE_TEXT"]))
						{
							echo htmlspecialcharsbx($_POST["MESSAGE_TEXT"]);
						}
						elseif ($userMessage = CUserOptions::GetOption((IsModuleInstalled("bitrix24") ? "bitrix24" : "intranet"), "invite_message_text"))
						{
							echo $userMessage;
						}
						else
						{
							echo GetMessage("BX24_INVITE_DIALOG_INVITE_MESSAGE_TEXT");
						}
					?></textarea><?

					?><?=bitrix_sessid_post()?><?
					?><input type="hidden" name="action" value="invite"><?
					?><div class="popup-window-buttons"><?
						?><span class="popup-window-button popup-window-button-accept" id="invite-dialog-invite-button-submit"><?
							?><span class="popup-window-button-left"></span><?
							?><span class="popup-window-button-text"><?=GetMessage("BX24_INVITE_DIALOG_BUTTON_INVITE")?></span><?
							?><span class="popup-window-button-right"></span><?
						?></span><?
						?><span class="popup-window-button popup-window-button-link popup-window-button-link-cancel" id="invite-dialog-invite-button-close"><?
							?><span class="popup-window-button-link-text"><?=GetMessage("BX24_INVITE_DIALOG_BUTTON_CLOSE")?></span><?
						?></span><?
					?></div><?

				?></form><?
			?></div>

			<div class="intranet-tab-content<?=(in_array($strAction, array("add")) ? " intranet-tab-content-selected" : "")?>" id="intranet-dialog-tab-content-add" data-user-type="employee"><?

				?><form method="POST" action="<?echo BX_ROOT."/tools/intranet_invite_dialog.php"?>" id="ADD_DIALOG_FORM" name="ADD_DIALOG_FORM"><?

					inviteDialogDrawTabContentHeader('add', $arStructure, $iDepartmentID, $iStructureCount, $arSonetGroups, $arSonetGroupsExtranet, $arSonetGroupsLast, $bExtranetInstalled);

					?><div class="invite-dialog-inv-form"><?
						?><table class="invite-dialog-inv-form-table">
							<tr>
								<td class="invite-dialog-inv-form-l">
									<label for="ADD_EMAIL"><?echo GetMessage("BX24_INVITE_DIALOG_ADD_EMAIL_TITLE")?></label>
								</td>
								<td class="invite-dialog-inv-form-r"><?
									?><input type="text" name="ADD_EMAIL" id="ADD_EMAIL" class="invite-dialog-inv-form-inp" value="<?echo htmlspecialcharsbx($_POST["ADD_EMAIL"])?>"><?
									if (
										!empty($arMailServices)
										&& (
											$bCreateDomainsExist 
											|| $bConnectDomainsExist 
											|| $bDomainUsersExist
										)
									)
									{
										?><div id="invite-dialog-mailbox-container" class="invite-dialog-box-info-set invite-dialog-box-info-set-inactive"><?
											?><div class="invite-dialog-box-info-block"><?
												if ($bCreateDomainsExist)
												{
													?><span id="invite-dialog-mailbox-action-create" onclick="BX.InviteDialog.onMailboxAction('create');" class="invite-dialog-box-info-btn"><?
														?><span class="invite-dialog-box-info-btn-text"><?=GetMessage('BX24_INVITE_DIALOG_MAIL_MAILBOX_ACTION_CREATE')?></span><?
													?></span><?
												}

												if (
													$bConnectDomainsExist 
													&& $bDomainUsersExist
												)
												{
													?><span class="invite-dialog-box-info-text"><?=GetMessage('BX24_INVITE_DIALOG_MAIL_MAILBOX_ACTION_OR')?></span><?
													?><span id="invite-dialog-mailbox-action-connect" onclick="BX.InviteDialog.onMailboxAction('connect');" class="invite-dialog-box-info-btn"><?
														?><span class="invite-dialog-box-info-btn-text"><?=GetMessage('BX24_INVITE_DIALOG_MAIL_MAILBOX_ACTION_CONNECT')?></span><?
													?></span><?
												}
											?></div><?
											if (
												$bCreateDomainsExist 
												|| $bConnectDomainsExist
											)
											{
												?><div id="invite-dialog-mailbox-content-create" style="display: none;"><?
													?><div class="invite-dialog-box-info-block invite-dialog-box-info-block-body"><?
														?><span class="invite-dialog-box-info-left"><?
															?><span class="invite-dialog-box-info-label"><?=GetMessage('BX24_INVITE_DIALOG_MAIL_MAILBOX_NAME')?></span><?
															?><input type="text" class="invite-dialog-inv-form-inp" id="ADD_MAILBOX_USER_create" name="ADD_MAILBOX_USER"><?
														?></span><?
														?><span class="invite-dialog-box-info-right"><?
															?><span class="invite-dialog-box-info-label"><?=GetMessage('BX24_INVITE_DIALOG_MAIL_MAILBOX_DOMAIN')?></span><?
															if ($iCreateDomainsCnt > 1)
															{
																?><select class="invite-dialog-inv-form-select" id="ADD_MAILBOX_DOMAIN_create" name="ADD_MAILBOX_DOMAIN"><?
																	foreach($arCreateMailServicesDomains as $serviceID => $arDomainsTmp)
																	{
																		if (
																			is_array($arDomainsTmp)
																			&& !empty($arDomainsTmp)
																		)
																		{
																			foreach ($arDomainsTmp as $strDomain)
																			{
																				?><option value="<?=$strDomain?>" data-service-id="<?=$serviceID?>">@<?=$strDomain?></option><?
																			}
																		}
																	}
																?></select><?
															}
															else
															{
																foreach($arCreateMailServicesDomains as $serviceID => $arDomainsTmp)
																{
																	?><input type="hidden" id="ADD_MAILBOX_SERVICE_create" name="ADD_MAILBOX_SERVICE" value="<?=$serviceID?>"><?
																	break;
																}																
																?><input type="hidden" id="ADD_MAILBOX_DOMAIN_create" name="ADD_MAILBOX_DOMAIN" value="<?=$arCreateMailServicesDomains[$serviceID][0]?>"><?
																?><div class="invite-dialog-inv-form-hidden-text">@<?=$arCreateMailServicesDomains[$serviceID][0]?></div><?
															}
														?></span><?
													?></div><?
													?><div class="invite-dialog-box-info-block invite-dialog-box-info-block-body"><?
														?><span class="invite-dialog-box-info-label"><?=GetMessage('BX24_INVITE_DIALOG_MAIL_MAILBOX_PASSWORD')?></span><?
														?><input type="password" class="invite-dialog-inv-form-inp" id="ADD_MAILBOX_PASSWORD" name="ADD_MAILBOX_PASSWORD"><?
													?></div><?
													?><div class="invite-dialog-box-info-block invite-dialog-box-info-block-body"><?
														?><span class="invite-dialog-box-info-label"><?=GetMessage('BX24_INVITE_DIALOG_MAIL_MAILBOX_PASSWORD_CONFIRM')?></span><?
														?><input type="password" class="invite-dialog-inv-form-inp" id="ADD_MAILBOX_PASSWORD_CONFIRM" name="ADD_MAILBOX_PASSWORD_CONFIRM"><?
													?></div><?
												?></div><?
											}

											if (
												$bConnectDomainsExist
												&& $bDomainUsersExist
											)
											{
												?><div id="invite-dialog-mailbox-content-connect" style="display: none;"><?
													?><div class="invite-dialog-box-info-block invite-dialog-box-info-block-body"><?
														?><span class="invite-dialog-box-info-left"><?
															?><span class="invite-dialog-box-info-label"><?=GetMessage('BX24_INVITE_DIALOG_MAIL_MAILBOX_SELECT')?></span><?
															?><script>
																var arMailServicesUsers = [];
																var arConnectMailServicesDomains = [];

																<?
																if (
																	count($arConnectMailServicesDomains) > 1
																	&& is_array($arConnectMailServicesDomains[$serviceID])
																	&& !empty($arConnectMailServicesDomains[$serviceID])
																)
																{
																	?>
																	arConnectMailServicesDomains[<?=$serviceID?>] = '<?=$arConnectMailServicesDomains[$serviceID][0]?>';
																	<?
																}
																?>
																arMailServicesUsers = [];
																<?
																foreach ($arMailServicesUsers as $domain => $arUsersTmp)
																{
																	if (
																		is_array($arUsersTmp)
																		&& !empty($arUsersTmp)
																	)
																	{
																		?>
																		arMailServicesUsers['<?=$domain?>'] = [];
																		<?
																		foreach ($arUsersTmp as $strUser)
																		{
																			?>
																			arMailServicesUsers['<?=$domain?>'].push('<?=$strUser?>');
																			<?
																		}
																	}
																}
																?>
															</script><?
															?><select class="invite-dialog-inv-form-select" id="ADD_MAILBOX_USER_connect" name="ADD_MAILBOX_USER"><?
																foreach($arMailServicesUsers as $domain => $arUsersTmp)
																{
																	if (
																		is_array($arUsersTmp)
																		&& !empty($arUsersTmp)
																	)
																	{
																		foreach ($arUsersTmp as $strUser)
																		{
																			?><option value="<?=$strUser?>" data-service-id="<?=$serviceID?>"><?=$strUser?></option><?
																		}
																	}
																	break;
																}
															?></select><?
														?></span><?
														?><span class="invite-dialog-box-info-right"><?
															?><span class="invite-dialog-box-info-label"><?=GetMessage('BX24_INVITE_DIALOG_MAIL_MAILBOX_DOMAIN')?></span><?
															if ($iConnectDomainsCnt > 1)
															{
																?><select class="invite-dialog-inv-form-select" id="ADD_MAILBOX_DOMAIN_connect" name="ADD_MAILBOX_DOMAIN" onchange="BX.InviteDialog.onMailboxServiceSelect(this);"><?
																	foreach($arConnectMailServicesDomains as $serviceID => $arDomainsTmp)
																	{
																		if (
																			is_array($arDomainsTmp)
																			&& !empty($arDomainsTmp)
																		)
																		{
																			foreach ($arDomainsTmp as $strDomain)
																			{
																				?><option value="<?=$strDomain?>" data-service-id="<?=$serviceID?>" data-domain="<?=$strDomain?>">@<?=$strDomain?></option><?
																			}
																		}
																	}
																?></select><?
															}
															else
															{
																foreach($arConnectMailServicesDomains as $serviceID => $arDomainsTmp)
																{
																	?><input type="hidden" id="ADD_MAILBOX_SERVICE_connect" name="ADD_MAILBOX_SERVICE" value="<?=$serviceID?>"><?
																	break;
																}
																?><input type="hidden" id="ADD_MAILBOX_DOMAIN_connect" name="ADD_MAILBOX_DOMAIN" value="<?=$arConnectMailServicesDomains[$serviceID][0]?>"><?
																?><div class="invite-dialog-inv-form-hidden-text">@<?=$arConnectMailServicesDomains[$serviceID][0]?></div><?
															}
														?></span><?
													?></div><?
												?></div><?
											}
											?><div class="invite-dialog-box-info-block invite-dialog-box-info-block-body"><?
												?><span class="invite-dialog-box-info-close-open invite-dialog-box-info-open" onclick="BX.InviteDialog.onMailboxRollup();"><?=GetMessage('BX24_INVITE_DIALOG_MAIL_MAILBOX_ROLLUP')?></span><?
											?></div><?
										?></div><?
										?><input type="hidden" name="ADD_MAILBOX_ACTION" id="ADD_MAILBOX_ACTION" value=""><?
									}
								?></td>
							</tr>
							<tr>
								<td class="invite-dialog-inv-form-l">
									<label for="ADD_NAME"><?echo GetMessage("BX24_INVITE_DIALOG_ADD_NAME_TITLE")?></label>
								</td>
								<td class="invite-dialog-inv-form-r">
									<input type="text" name="ADD_NAME" id="ADD_NAME" class="invite-dialog-inv-form-inp" value="<?echo htmlspecialcharsbx($_POST["ADD_NAME"])?>">
								</td>
							</tr>
							<tr>
								<td class="invite-dialog-inv-form-l">
									<label for="ADD_LAST_NAME"><?echo GetMessage("BX24_INVITE_DIALOG_ADD_LAST_NAME_TITLE")?></label>
								</td>
								<td class="invite-dialog-inv-form-r">
									<input type="text" name="ADD_LAST_NAME" id="ADD_LAST_NAME" class="invite-dialog-inv-form-inp" value="<?=htmlspecialcharsbx($_POST["ADD_LAST_NAME"])?>">
								</td>
							</tr>
							<tr class="invite-dialog-inv-form-footer">
								<td class="invite-dialog-inv-form-l">
									<label for="ADD_POSITION"><?echo GetMessage("BX24_INVITE_DIALOG_ADD_POSITION_TITLE")?></label>
								</td>
								<td class="invite-dialog-inv-form-r">
									<input type="text" name="ADD_POSITION" id="ADD_POSITION" class="invite-dialog-inv-form-inp" value="<?=htmlspecialcharsbx($_POST["ADD_POSITION"])?>">
								</td>
							</tr>
							<tr>
								<td class="invite-dialog-inv-form-l">&nbsp;</td>
								<td class="invite-dialog-inv-form-r"><?
									?><div class="invite-dialog-inv-form-checkbox-wrap"><?
										?><input type="checkbox" name="ADD_SEND_PASSWORD" id="ADD_SEND_PASSWORD" value="Y" class="invite-dialog-inv-form-checkbox"<?=($_POST["ADD_SEND_PASSWORD"] == "Y" ? " checked" : "")?><?=(empty($_POST["ADD_EMAIL"]) ? " disabled" : "")?>><?
										?><label class="invite-dialog-inv-form-checkbox-label" for="ADD_SEND_PASSWORD"><?echo GetMessage("BX24_INVITE_DIALOG_ADD_SEND_PASSWORD_TITLE")?><span id="ADD_SEND_PASSWORD_EMAIL"></span></label><?
									?></div><?
								?></td>
							</tr>
						</table><?
					?></div><?
					?><?=bitrix_sessid_post()?><?
					?><input type="hidden" name="action" value="add"><?
					?><div class="popup-window-buttons"><?
						?><span class="popup-window-button popup-window-button-accept" id="invite-dialog-add-button-submit"><?
							?><span class="popup-window-button-left"></span><?
							?><span class="popup-window-button-text"><?=GetMessage("BX24_INVITE_DIALOG_BUTTON_ADD")?></span><?
							?><span class="popup-window-button-right"></span><?
						?></span><?
						?><span class="popup-window-button popup-window-button-link popup-window-button-link-cancel" id="invite-dialog-add-button-close"><?
							?><span class="popup-window-button-link-text"><?=GetMessage("BX24_INVITE_DIALOG_BUTTON_CLOSE")?></span><?
						?></span><?
					?></div><?
				?></form><?
			?></div>

		</div>
	</div>

	<script type="text/javascript">

		var inviteDialogInviteStructureLink = BX("invite-dialog-invite-structure-link");
		var inviteDialogAddStructureLink = BX("invite-dialog-add-structure-link");

		var arTabs = BX.findChildren(BX('intranet-dialog-tabs'), {className: 'intranet-tab'}, true);
		var arTabsContent = BX.findChildren(BX('intranet-dialog-tabs'), {className: 'intranet-tab-content'}, true);

		BX.ready(function() {
			<?
			if (is_array($arExtranetGroupID))
			{
				?>
				if (typeof window['arExtranetGroupID'] == 'undefined')
				{
					window['arExtranetGroupID'] = <?=CUtil::PhpToJSObject($arExtranetGroupID)?>;
				}
				<?
			}
			?>

			BX.InviteDialog.bindInviteDialogUserTypeLink(BX("invite-dialog-invite-usertype-employee-link"), <?=($bExtranetInstalled ? 'true' : 'false')?>);
			BX.InviteDialog.bindInviteDialogUserTypeLink(BX("invite-dialog-invite-usertype-extranet-link"), <?=($bExtranetInstalled ? 'true' : 'false')?>);
			BX.InviteDialog.bindInviteDialogUserTypeLink(BX("invite-dialog-add-usertype-employee-link"), <?=($bExtranetInstalled ? 'true' : 'false')?>);
			BX.InviteDialog.bindInviteDialogUserTypeLink(BX("invite-dialog-add-usertype-extranet-link"), <?=($bExtranetInstalled ? 'true' : 'false')?>);

			BX.InviteDialog.bindInviteDialogSonetGroupLink(BX("invite-dialog-invite-sonetgroup-link"));
			BX.InviteDialog.bindInviteDialogSonetGroupLink(BX("invite-dialog-add-sonetgroup-link"));

			<?
			if ($iStructureCount > 1)
			{
				?>
				BX.InviteDialog.bindInviteDialogStructureLink(BX("invite-dialog-invite-structure-link"));
				BX.InviteDialog.bindInviteDialogStructureLink(BX("invite-dialog-add-structure-link"));
				<?
			}
			?>

			BX.InviteDialog.bindInviteDialogChangeTab(BX("intranet-dialog-tab-invite"));
			BX.InviteDialog.bindInviteDialogChangeTab(BX("intranet-dialog-tab-add"));

			BX.InviteDialog.bindInviteDialogSubmit(BX("invite-dialog-invite-button-submit"));
			BX.InviteDialog.bindInviteDialogSubmit(BX("invite-dialog-add-button-submit"));

			BX.InviteDialog.bindInviteDialogClose(BX("invite-dialog-invite-button-close"));
			BX.InviteDialog.bindInviteDialogClose(BX("invite-dialog-add-button-close"));

			BX.InviteDialog.bindSendPasswordEmail();

			BX.InviteDialog.sonetGroupSelector = BX('invite-dialog-invite-sonetgroup-container-post').getAttribute('data-selector-name');
		});

		var windowObj = (window.BX ? window: (window.top.BX ? window.top: null));
		if(windowObj)
		{
			var popup = windowObj.B24.Bitrix24InviteDialog.popup;
			<?
			if($ID_INVITED):
				?>
				popup.setTitleBar({content: windowObj.BX.create("span", {html:
					'<table width="100%"><tr><td>'
					+ windowObj.BX.message('BX24_INVITE_TITLE_INVITE')
					+ '</td></tr></table>'
				})});
				<?
			endif;
			?>
		}
	</script><?
}
?>
</div>
<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin_js.php");
?>
