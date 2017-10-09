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
if (defined('VB_AREA') 
	AND strtolower(get_class($this)) == strtolower('vB_BbCodeParser_PlainText') //only run inside PlainText
	AND $this->registry->kbank['hide']['enabled']) {
	include_once(DIR . '/kbank/hide_functions.php');

	$text = $this->registry->kBankHide->strip_bbcode($text,'rss');
}
?>