<?
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');


require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if(!CModule::IncludeModule('crm'))
	return ;

global $APPLICATION;

$CCrmPerms = new CCrmPerms($USER->GetID());
if (!$USER->IsAuthorized() || $CCrmPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE))
	return ;

$arResult = array();
$_GET['USER_ID'] = preg_replace('/^(CONTACT|COMPANY|LEAD|DEAL)_/i'.BX_UTF_PCRE_MODIFIER, '', $_GET['USER_ID']);
$iContactId = (int) $_GET['USER_ID'];
if ($iContactId > 0)
{
	__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

	$arParams['PATH_TO_CONTACT_SHOW'] = CrmCheckPath('PATH_TO_CONTACT_SHOW', $arParams['PATH_TO_CONTACT_SHOW'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&show');
	$arParams['PATH_TO_CONTACT_EDIT'] = CrmCheckPath('PATH_TO_CONTACT_EDIT', $arParams['PATH_TO_CONTACT_EDIT'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&edit');
	$arParams['PATH_TO_COMPANY_SHOW'] = CrmCheckPath('PATH_TO_COMPANY_SHOW', $arParams['PATH_TO_COMPANY_SHOW'], $APPLICATION->GetCurPage().'?company_id=#company_id#&show');
	$arResult['TYPE_LIST'] = CCrmStatus::GetStatusListEx('CONTACT_TYPE');

	$obRes = CCrmContact::GetList(array(), array('ID' => $iContactId));
	$arContact = $obRes->Fetch();
	if ($arContact == false)
		return ;
	$res = CCrmFieldMulti::GetList(array('ID' => 'asc'), array('ENTITY_ID' => 'CONTACT', 'ELEMENT_ID' => $iContactId));
	while($ar = $res->Fetch())
		if (empty($arContact[$ar['COMPLEX_ID']]))
			$arContact[$ar['COMPLEX_ID']] = CCrmFieldMulti::GetTemplateByComplex($ar['COMPLEX_ID'], $ar['VALUE']);

	$arContact['PATH_TO_CONTACT_SHOW'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_SHOW'],
		array(
			'contact_id' => $iContactId
		)
	);
	$arContact['PATH_TO_CONTACT_EDIT'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_EDIT'],
		array(
			'contact_id' => $iContactId
		)
	);

	$arContact['PATH_TO_COMPANY_SHOW'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_SHOW'],
		array(
			'company_id' => $arContact['COMPANY_ID']
		)
	);

	$strCard = '
<div class="bx-user-info-data-cont-video  bx-user-info-fields" id="bx_user_info_data_cont_1">
	<div class="bx-user-info-data-name ">
		<a href="'.$arContact['PATH_TO_CONTACT_SHOW'].'">'.htmlspecialcharsbx($arContact['FULL_NAME']).'</a>
	</div>
	<div class="bx-user-info-data-info">';
	if (!empty($arContact['TYPE_ID']))
	{
		$strCard .= '<span class="field-name">'.GetMessage('CRM_COLUMN_TYPE').'</span>:
		<span class="fields enumeration">'.$arResult['TYPE_LIST'][$arContact['TYPE_ID']].'</span>
		<br />';
	}
	$strCard .= '<span class="field-name">'.GetMessage('CRM_COLUMN_DATE_MODIFY').'</span>:
		<span class="fields enumeration">'.FormatDate('x', MakeTimeStamp($arContact['DATE_MODIFY']), (time() + CTimeZone::GetOffset())).'</span>
		<br />
		<br />
	</div>
	<div class="bx-user-info-data-name bx-user-info-seporator">
		<nobr>'.GetMessage('CRM_SECTION_CONTACT_INFO').'</nobr>
	</div>
	<div class="bx-user-info-data-info">';
	if (!empty($arContact['PHONE_WORK']))
	{
		$strCard .= '<span class="field-name">'.GetMessage('CRM_COLUMN_PHONE').'</span>:
		'.$arContact['PHONE_WORK'].'
		<br />';
	}
	if (!empty($arContact['EMAIL_WORK']))
	{
		$strCard .= '<span class="field-name">'.GetMessage('CRM_COLUMN_EMAIL').'</span>:
		'.$arContact['EMAIL_WORK'].'
		<br />';
	}
	$strCard .= '<br />';
	if (!empty($arContact['COMPANY_TITLE']))
	{
		$strCard .= '<span class="field-name">'.GetMessage('CRM_COLUMN_COMPANY_TITLE').'</span>:
		<a href="'.$arContact['PATH_TO_COMPANY_SHOW'].'">'.$arContact['COMPANY_TITLE'].'</a>
		<br /> ';
	}
	$strCard .= '</div>
</div>';

	if (!empty($arContact['PHOTO']))
	{
		$imageFile = CFile::GetFileArray($arContact['PHOTO']);
		if ($imageFile !== false)
		{
			$arFileTmp = CFile::ResizeImageGet(
				$imageFile,
				array('width' => 102, 'height' => 104),
				BX_RESIZE_IMAGE_PROPORTIONAL,
				false
			);
			$imageImg = CFile::ShowImage($arFileTmp['src'], 102, 104, "border='0'", '');
		}
		if (strlen($imageImg)>0)
			$strPhoto = '<a href="'.$arContact['PATH_TO_CONTACT_SHOW'].'" class="bx-user-info-data-photo">'.$imageImg.'</a>';
		else
			$strPhoto = '<a href="'.$arContact['PATH_TO_CONTACT_SHOW'].'" class="bx-user-info-data-photo no-photo"></a>';
	}
	else
		$strPhoto = '<a href="'.$arContact['PATH_TO_CONTACT_SHOW'].'" class="bx-user-info-data-photo no-photo"></a>';

	$strToolbar2 = '
<div class="bx-user-info-data-separator"></div>
<ul>
	<li class="bx-icon-show">
		<a href="'.$arContact['PATH_TO_CONTACT_SHOW'].'">'.GetMessage('CRM_OPER_SHOW').'</a>
	</li>
	<li class="bx-icon bx-icon-message">
		<a href="'.$arContact['PATH_TO_CONTACT_EDIT'].'" >'.GetMessage('CRM_OPER_EDIT').'</a>
	</li>
</ul>';

	$arResult = array(
		'Toolbar' => '',
		'ToolbarItems' => '',
		'Toolbar2' => $strToolbar2,
		'Card' => $strCard,
		'Photo' => $strPhoto
	);
}

$APPLICATION->RestartBuffer();
Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
echo CUtil::PhpToJsObject(array('RESULT' => $arResult));
if(!defined('PUBLIC_AJAX_MODE'))
{
	define('PUBLIC_AJAX_MODE', true);
}
include($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_after.php");
die();

?>
