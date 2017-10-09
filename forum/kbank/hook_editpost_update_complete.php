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
if (defined('VB_AREA') AND $vbulletin->kbank['enabled']) {	
	if ($vbulletin->kbank['AutoRecalcPointWhenEdit']){
		if (!empty($postinfo)
			AND $givepoints
			AND is_array($postinfo) 
			AND ($postinfo['kbank'] != 0)) {
			include_once(DIR . '/kbank/functions.php');
			giveMoney($postinfo['userid'],$givepoints,'post');
		}
	}
}
?>