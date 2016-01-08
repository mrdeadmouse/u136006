<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
global $APPLICATION;
$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-gadget.css");

if (empty($arResult['LEAD']))
	echo GetMessage('CRM_DATA_EMPTY');
else
{
	foreach($arResult['LEAD'] as &$arLead)
	{
		echo '<div class="crm-gadg-block">';
		echo '<div class="crm-gadg-title">';
		$linkID = "ballon_{$arResult['GADGET_ID']}_D_{$arLead['~ID']}";
		echo '<a id="', $linkID, '" href="', $arLead['PATH_TO_LEAD_SHOW'], '" class="crm-gadg-link" title="', $arLead['TITLE'], '">', $arLead['TITLE'], '</a>';
		echo '<script type="text/javascript">BX.tooltip("LEAD_', $arLead['~ID'], '", "', $linkID,'", "/bitrix/components/bitrix/crm.lead.show/card.ajax.php", "crm_balloon_no_photo", true);</script>';
		echo '</div>';

		echo '<div class="crm-gadg-stage">';
		echo '<span class="crm-gadg-stage-left">', htmlspecialcharsbx(GetMessage('CRM_COLUMN_STATUS')), ':<span>';
		echo '<span class="crm-gadg-stage-right">', $arLead['LEAD_STATUS_NAME'], '<span>';
		echo '</div>';

		$name = isset($arLead['LEAD_FORMATTED_NAME']) ? $arLead['LEAD_FORMATTED_NAME'] : '';
		$post = isset($arLead['POST']) ? $arLead['POST'] : '';
		$companyTitle = isset($arLead['COMPANY_TITLE']) ? $arLead['COMPANY_TITLE'] : '';

		if($name !== '' || $post !== '' || $companyTitle !== '')
		{
			echo '<div class="crm-gadg-description">';
			if($name !== '')
			{
				echo $name;
			}

			if($post !== '')
			{
				echo '<span class="crm-gadg-description-grey">';
				if($name !== '')
				{
					echo ' (', $post, ')';
				}
				else
				{
					echo $post;
				}
				echo '</span>';
			}

			if($companyTitle !== '')
			{
				if($name !== '' || $post !== '')
				{
					echo ', ';
				}

				echo $companyTitle;
			}

			echo '</div>';
		}

		echo '</div>';
	}
	unset($arLead);
}
?>