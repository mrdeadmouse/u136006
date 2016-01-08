<?
$MESS["DAV_HELP_NAME"] = "DAV Module";
$MESS["DAV_HELP_TEXT"] = "The DAV module provides for synchronization of the calendars and contacts between the portal and any software and hardware that support CalDAV and/or CardDAV protocols, for example iPhone and iPad. Software support is provided by Mozilla Sunbird, eM Client and some other software applications.<br><br>
<ul>
	<li><b><a href=\"#caldav\">Connect using CalDav</a></b>
	<ul>
		<li><a href=\"#caldavipad\">Connect iPhone</a></li>
		<li><a href=\"#carddavsunbird\">Connect Mozilla Sunbird</a></li>
	</ul>
	</li>
	<li><b><a href=\"#carddav\">Connect using CardDav</a></b></li>
</ul>

<br><br>

<h3><a name=\"caldav\"></a>Connect using CalDav</h3>

<h4><a name=\"caldavipad\"></a>Connect iPhone</h4>

To set up your Apple device to support CalDAV:
<ol>
<li>Click <b>Settings</b> and select <b>Mail, Contacts, Calendars>Accounts</b>.</li>
<li>Click <b>Add Account</b>.</li>
<li>Select <b>Other</b> &gt; <b>Add CalDAV Account</b>.</li>
<li>Specify this website address as server (#SERVER#). Use your login and password.</li>
<li>Use Basic Authorization.</li>
<li>To specify the port number, save the account and open it for editing again.</li>
</ol>

Your calendars will appear in the \"Calendar\" application.<br>
To connect other users' calendars, use links:<br>
<i>#SERVER#/bitrix/groupdav.php/site_ID/user_name/calendar/</i><br>
and<br>
<i>#SERVER#/bitrix/groupdav.php/site_ID/group_ID/calendar/</i><br>

<br><br>

<h4><a name=\"carddavsunbird\"></a>Connect Mozilla Sunbird</h4>

To configure Mozilla Sunbird for use with CalDAV:
<ol>
<li>Run Sunbird and select <b>File &gt; New Calendar</b>.</li>
<li>Select <b>On the Network</b> and click <b>Next</b>.</li>
<li>Select <b>CalDAV</b> format.</li>
<li>In the <b>Location</b> field, enter:<br>
<i>#SERVER#/bitrix/groupdav.php/site_ID/user_name/calendar/calendar_ID/</i><br>
or<br>
<i>#SERVER#/bitrix/groupdav.php/site_ID/group_ID/calendar/calendar_ID/</i><br>
and click <b>Next</b>.</li>
<li>Give your calendar a name and select a colour for it.</li>
<li>Enter your user name and password.</li>
</ol>

<br><br>

<h3><a name=\"carddav\"></a>Connect using CardDav</h3>

To set up your Apple device to support CardDAV:
<ol>
<li>Click <b>Settings</b> and select <b>Mail, Contacts, Calendars>Accounts</b>.</li>
<li>Click <b>Add Account</b>.</li>
<li>Select <b>Other</b> &gt; <b>Add CardDAV Account</b>.</li>
<li>Specify this website address as server (#SERVER#). Use your login and password.</li>
<li>Use Basic Authorization.</li>
<li>To specify the port number, save the account and open it for editing again.</li>
</ol>

Your calendars will appear in the \"Contacts\" application.<br>";
?>