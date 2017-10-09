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
	$KBANK_HOOK_NAME = KBANK_MEMBER_COMPLETE;
	
	if ($userinfo['money']) 
		$userinfo['money'] = $userinfo['kbank'] = vb_number_format($userinfo['money'],$vbulletin->kbank['roundup']);
	
	findItemToWork($userinfo['userid']);

	//vBB older than 3.7 support (no template_hook)
	if ($vbulletin->options['templateversion'] < '3.7.0') {
		include_once(DIR . '/kbank/hook_member_profileblock_fetch_unwrapped.php');
	}
}
?>