<?
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if(!CModule::IncludeModule('crm'))
	return ;

global $APPLICATION;

$CCrmPerms = new CCrmPerms($USER->GetID());
if (!$USER->IsAuthorized() || $CCrmPerms->HavePerm('LEAD', BX_CRM_PERM_NONE))
	return ;

$arResult = array();
$_GET['USER_ID'] = preg_replace('/^(CONTACT|COMPANY|LEAD|DEAL)_/i'.BX_UTF_PCRE_MODIFIER, '', $_GET['USER_ID']);
$iLeadId = (int) $_GET['USER_ID'];
if ($iLeadId > 0)
{
	__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

	$arParams['PATH_TO_LEAD_SHOW'] = CrmCheckPath('PATH_TO_LEAD_SHOW', $arParams['PATH_TO_LEAD_SHOW'], $APPLICATION->GetCurPage().'?lead_id=#lead_id#&show');
	$arParams['PATH_TO_LEAD_EDIT'] = CrmCheckPath('PATH_TO_LEAD_EDIT', $arParams['PATH_TO_LEAD_EDIT'], $APPLICATION->GetCurPage().'?lead_id=#lead_id#&edit');
	$arResult['STATUS_LIST'] = CCrmStatus::GetStatusListEx('STATUS');

	$obRes = CCrmLead::GetList(array(), array('ID' => $iLeadId));
	$arLead = $obRes->Fetch();
	if ($arLead == false)
		return ;
	$res = CCrmFieldMulti::GetList(array('ID' => 'asc'), array('ENTITY_ID' => 'LEAD', 'ELEMENT_ID' => $iLeadId));
	while($ar = $res->Fetch())
		if (empty($arLead[$ar['COMPLEX_ID']]))
			$arLead[$ar['COMPLEX_ID']] = CCrmFieldMulti::GetTemplateByComplex($ar['COMPLEX_ID'], $ar['VALUE']);

	$arLead['PATH_TO_LEAD_SHOW'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_SHOW'],
		array(
			'lead_id' => $iLeadId
		)
	);
	$arLead['PATH_TO_LEAD_EDIT'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_EDIT'],
		array(
			'lead_id' => $iLeadId
		)
	);

	$arLead['CONTACT_NAME'] = CUser::FormatName(
		\Bitrix\Crm\Format\PersonNameFormatter::getFormat(),
		array(
			'NAME' => $arLead['NAME'],
			'LAST_NAME' => $arLead['LAST_NAME'],
			'SECOND_NAME' => $arLead['SECOND_NAME']
		),
		true, false
	);

	$strCard = '
<div class="bx-user-info-data-cont-video  bx-user-info-fields" id="bx_user_info_data_cont_1">
	<div class="bx-user-info-data-name ">
		<a href="'.$arLead['PATH_TO_LEAD_SHOW'].'">'.htmlspecialcharsbx($arLead['TITLE']).'</a>
	</div>
	<div class="bx-user-info-data-info">';
	if (!empty($arLead['STATUS_ID']))
	{
		$strCard .= '<span class="field-name">'.GetMessage('CRM_COLUMN_STATUS').'</span>:
		<span class="fields enumeration">'.$arResult['STATUS_LIST'][$arLead['STATUS_ID']].'</span>
		<br />';
	}

	$arProductRows = CCrmLead::LoadProductRows($arLead['ID']);
	if(count($arProductRows) > 0)
	{
		$strCard .= '<span class="field-name">'.GetMessage('CRM_COLUMN_PRODUCTS').'</span>:<span class="fields enumeration">'.htmlspecialcharsbx(CCrmProductRow::RowsToString($arProductRows)).'</span><br />';
	}

	$strCard .= '<span class="field-name">'.GetMessage('CRM_COLUMN_DATE_MODIFY').'</span>:
		<span class="fields enumeration">'.FormatDate('x', MakeTimeStamp($arLead['DATE_MODIFY']), (time() + CTimeZone::GetOffset())).'</span>
		<br />
		<br />
	</div>
	<div class="bx-user-info-data-name bx-user-info-seporator">
		<nobr>'.GetMessage('CRM_SECTION_CONTACT_INFO').'</nobr>
	</div>
	<div class="bx-user-info-data-info">';
	if (!empty($arLead['CONTACT_NAME']))
	{
		$strCard .= '<span class="field-name">'.GetMessage('CRM_COLUMN_NAME').'</span>:
		<span class="fields enumeration">'.htmlspecialcharsbx($arLead['CONTACT_NAME']).'</span>
		<br />';
	}
	if (!empty($arLead['PHONE_WORK']))
	{
		$strCard .= '<span class="field-name">'.GetMessage('CRM_COLUMN_PHONE').'</span>:
		'.$arLead['PHONE_WORK'].'
		<br />';
	}
	if (!empty($arLead['EMAIL_WORK']))
	{
		$strCard .= '<span class="field-name">'.GetMessage('CRM_COLUMN_EMAIL').'</span>:
		'.$arLead['EMAIL_WORK'];
	}
	$strCard .= '</div>
</div>';
	$strPhoto = '<a href="'.$arLead['PATH_TO_LEAD_SHOW'].'" class="bx-user-info-data-photo no-photo"></a>';

	$strToolbar2 = '
<div class="bx-user-info-data-separator"></div>
<ul>
	<li class="bx-icon-show">
		<a href="'.$arLead['PATH_TO_LEAD_SHOW'].'">'.GetMessage('CRM_OPER_SHOW').'</a>
	</li>
	<li class="bx-icon bx-icon-message">
		<a href="'.$arLead['PATH_TO_LEAD_EDIT'].'" >'.GetMessage('CRM_OPER_EDIT').'</a>
	</li>
</ul>';

	$arResult = array(
		'Toolbar' => '',
		'ToolbarItems' => '',
		'Toolbar2' => $strToolbar2,
		'Card' => $strCard,
		'Card2' => $arLead,
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
