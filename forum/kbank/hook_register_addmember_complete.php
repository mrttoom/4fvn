<?php
/*======================================================================*\
|| #################################################################### ||
|| # kBank 2.1
|| # Coded by mrpaint
|| # Contact: mrpaint@gmail.com
|| # I'm a Vietnamese! Thank you for using this script
|| # Last Updated: 21:54 16-09-2008
|| #################################################################### ||
\*======================================================================*/
if (defined('VB_AREA') && $vbulletin->kbank['enabled']) {	
	include_once(DIR . '/kbank/functions.php');
	if ($vbulletin->kbank['RegPoint1time']){
		giveMoney($vbulletin->userinfo['userid'],$vbulletin->kbank['RegPoint1time'],'register');
	}
}
?>