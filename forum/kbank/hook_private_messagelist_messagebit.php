<?php
/*======================================================================*\
|| #################################################################### ||
|| # kBank 2.4
|| # Coded by mrpaint
|| # Contact: mrpaint@gmail.com
|| # I'm a Vietnamese! Thank you for using this script
|| # Last Updated: 03:27 29-03-2009
|| #################################################################### ||
\*======================================================================*/
if (defined('VB_AREA') 
	&& $vbulletin->kbank['enabled']
	&& $vbulletin->GPC['folderid'] != -1 //we are not in sent items
	) {
	include_once(DIR . '/kbank/functions.php');
	
	if (customize_userinfo_replaceUsername($username))
	{
		//rebuild userbit with username replaced
		eval('$userbit = "' . fetch_template('pm_messagelistbit_user') . '";');
	}
}
?>