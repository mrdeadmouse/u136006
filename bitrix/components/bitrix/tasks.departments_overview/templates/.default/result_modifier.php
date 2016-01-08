<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

foreach ($arResult['DEPARTMENTS'] as &$arDepartment)
{
	$usersInSubDepsCount = 0;
	foreach ($arDepartment['USERS'] as &$arUser)
	{
		if ($arUser['USER_IN_SUBDEPS'] === 'Y')
			++$usersInSubDepsCount;
	}
	unset($arUser);

	$arDepartment['COUNT_OF_MANAGED_USERS_IN_SUBDEPS'] = $usersInSubDepsCount;
}
unset($arDepartment);
