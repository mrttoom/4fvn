<?php
/*======================================================================*\
|| #################################################################### ||
|| # kBank 2.0
|| # Coded by mrpaint
|| # Contact: mrpaint@gmail.com
|| # I'm a Vietnamese! Thank you for using this script
|| # Last Updated: 02 00 15-07-2008
|| #################################################################### ||
\*======================================================================*/
if (defined('VB_AREA') && $vbulletin->kbank['enabled']) {	
	include_once(DIR . '/kbank/functions.php');
	giveMoney($vbulletin->userinfo['userid'],$points);
	//referer
	if ($vbulletin->kbank['ReferPoint'] > 0
		AND $vbulletin->userinfo['referrerid'] //user has been refered
		AND $vbulletin->userinfo['joindate'] > TIMENOW - 30*24*60*60 //join in last 30 days
		) {
		giveMoney($vbulletin->userinfo['referrerid'],$vbulletin->kbank['ReferPoint'],'referer');
	}
}
?>