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
if (defined('VB_AREA') && $vbulletin->kbank['enabled']) {	
	include_once(DIR . '/kbank/functions.php');
	if ($vbulletin->kbank['AutoRemovePointWhenDelete']){
		$ids = implode(',',array_keys($threadarray));
		$points = $vbulletin->db->query_read("SELECT SUM(post.kbank) as inpost,
			post.userid as userid
			FROM " . TABLE_PREFIX . "post AS post 
			WHERE threadid IN ($ids)
				AND visible = 1
			GROUP BY post.userid");
		while($point = $vbulletin->db->fetch_array($points)) {
			giveMoney($point['userid'],$point['inpost'],'post');
		}
	}
}
?>