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
if (defined('VB_AREA') 
	AND $vbulletin->kbank['hide']['enabled']) {
	
	include_once(DIR . '/kbank/hide_functions.php');
	$pagetext = $vbulletin->kBankHide->strip_bbcode($pagetext, 'editor');
}
?>