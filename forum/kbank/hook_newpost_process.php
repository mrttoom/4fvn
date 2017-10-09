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
	$points = kbank_points($post, $foruminfo['forumid'], $type);
	if ($points != 0){
		$dataman->set('kbank', $points);
	}
}
?>