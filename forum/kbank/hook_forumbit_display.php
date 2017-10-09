<?php
/*======================================================================*\
|| #################################################################### ||
|| # kBank 2.4
|| # Coded by mrpaint
|| # Contact: mrpaint@gmail.com
|| # I'm a Vietnamese! Thank you for using this script
|| # Last Updated: 03:26 29-03-2009
|| #################################################################### ||
\*======================================================================*/
if (defined('VB_AREA') && $vbulletin->kbank['enabled']) {
	include_once(DIR . '/kbank/functions.php');
	
	if (customize_userinfo_replaceUsername($lastpostinfo['lastposter']))
	{
		//rebuild lastpostinfo if nescessary
		eval('$forum[\'lastpostinfo\'] = "' . fetch_template('forumhome_lastpostby') . '";');
	}
}
?>