function ToggleDescription()
{
	if (BX('bx_group_description'))
	{
		BX.toggleClass(BX('bx_group_description'), 'bx-group-description-hide-table');

		var val = 'Y';

		if (BX('bx_group_pagetitle_link_open') && BX('bx_group_pagetitle_link_closed'))
		{
			if (BX('bx_group_pagetitle_link_open').style.display == 'inline-block')
			{
				BX('bx_group_pagetitle_link_open').style.display = 'none';
				BX('bx_group_pagetitle_link_closed').style.display = 'inline-block';
				val = 'N';
			}
			else
			{
				BX('bx_group_pagetitle_link_closed').style.display = 'none';
				BX('bx_group_pagetitle_link_open').style.display = 'inline-block';
				val = 'Y';
			}
		}

		BX.userOptions.save('socialnetwork', 'sonet_group_description', 'state', val, false);
	}

	return false;
		
}