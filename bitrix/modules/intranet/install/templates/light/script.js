function SetPrintCSS(isPrint)
{
	var link;

	if (document.getElementsByTagName)
		link = document.getElementsByTagName('link');
	else if (document.all)
		link = document.all.tags('link');
	else
		return;

	for (var index=0; index < link.length; index++)
	{
		if (!link[index].title || link[index].title != 'print')
			continue;

		if (isPrint)
		{
			link[index].disabled = false;
			link[index].rel = "stylesheet";
		}
		else
		{
			link[index].disabled = true;
			link[index].rel = "alternate stylesheet";
		}
	}
}

function BackToDesignMode()
{
	if (document.all)
		window.location.href = window.location.href.replace('#print','');
	else
		SetPrintCSS(false);

	return false;
}

if (document.location.hash == '#print')
	SetPrintCSS(true);