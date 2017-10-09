<?php
/*======================================================================*\
|| #################################################################### ||
|| # kBank 1.9.2
|| # Coded by mrpaint
|| # Contact: mrpaint@gmail.com
|| # I'm a Vietnamese! Thank you for using this script
|| # Last Updated: 01:20 21-06-2008
|| #################################################################### ||
\*======================================================================*/
if (defined('VB_AREA') && $vbulletin->kbank['award']['enabled']) {
	//vBB older than 3.7 support (no template_hook)
	if ($vbulletin->options['templateversion'] < '3.7.0') {
		include_once(DIR . '/kbank/award_hook_member_profileblock_fetch_unwrapped.php');
	}
}
?>