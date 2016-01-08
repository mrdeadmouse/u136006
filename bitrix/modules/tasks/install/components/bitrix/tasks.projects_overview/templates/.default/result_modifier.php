<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

foreach ($arResult['PROJECTS'] as $groupId => &$arProject)
{
	$arImage = $this->__component->initGroupImage($arProject['PATHES']['IN_WORK'], $arProject['IMAGE_ID'], 42);

	$arHeads = array();
	$arNotHeads = array();
	$arMembersForJs = array();
	foreach ($arProject['MEMBERS'] as $arMember)
	{
		$arMember['PHOTO_SRC'] = $this->__component->getUserPictureSrc(
			$arMember['PHOTO_ID'],
			$arMember['USER_GENDER'],
			17,
			17
		);

		$arMemberForJs = array(
			'ID'       => $arMember['ID'],
			'NAME'     => $arMember['FORMATTED_NAME'],
			'PHOTO'    => $this->__component->getUserPictureSrc(
				$arMember['PHOTO_ID'],
				$arMember['USER_GENDER'],
				21,
				21
			),
			'PROFILE'  => $arMember['HREF'],
			'POSITION' => $arMember['WORK_POSITION']
		);

		if (
			($arMember['IS_GROUP_OWNER'] === 'Y')
			|| ($arMember['IS_GROUP_MODERATOR'] === 'Y')
		)
		{
			$arHeads[] = $arMember;

			if ($arMember['IS_GROUP_OWNER'] === 'Y')
				$arMemberForJs['IS_HEAD'] = true;
			else
				$arMemberForJs['IS_HEAD'] = false;
		}
		else
		{
			$arNotHeads[] = $arMember;
			$arMemberForJs['IS_HEAD'] = false;
		}

		$arMembersForJs[] = CUtil::PhpToJsObject($arMemberForJs);
	}

	$arProject['IMAGE_HTML']      =  $arImage['IMG'];
	$arProject['MEMBERS_FOR_JS']  = '[' . implode(', ', $arMembersForJs) . ']';
	$arProject['HEADS']           =  $arHeads;
	$arProject['HEADS_COUNT']     =  count($arHeads);
	$arProject['NOT_HEADS_COUNT'] =  count($arNotHeads);
}
unset($arProject);

usort(
	$arResult['PROJECTS'],
	function($a, $b){
		if ($a['COUNTERS']['IN_WORK'] < $b['COUNTERS']['IN_WORK'])
			return (1);
		elseif ($a['COUNTERS']['IN_WORK'] > $b['COUNTERS']['IN_WORK'])
			return (-1);
		else
			return strcmp($a['TITLE'], $b['TITLE']);
	}
);
