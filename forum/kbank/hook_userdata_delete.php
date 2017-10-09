<?php
/*======================================================================*\
|| #################################################################### ||
|| # kBank 1.9.3 (Security Issue: Database Query)
|| # Coded by mrpaint
|| # Contact: mrpaint@gmail.com
|| # I'm a Vietnamese! Thank you for using this script
|| # Last Updated: 19:24 24-06-2008
|| #################################################################### ||
\*======================================================================*/
global $vbulletin;
if (defined('VB_AREA')) {	
	$points = intval($this->existing["{$vbulletin->kbank['field']}"]);
	$this->dbobject->query_write("
		UPDATE " . TABLE_PREFIX . "kbank_banklogs
		SET `amount` = `amount` + $points
		WHERE itemname = 'register'
	");
}
?>