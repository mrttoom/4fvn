<?php
/*======================================================================*\
|| #################################################################### ||
|| # kBank 1.8.4b
|| # Coded by mrpaint
|| # Contact: mrpaint@gmail.com
|| # I'm a Vietnamese! Thank you for using this script
|| # Last Updated: 12:45 13-06-2008 (Friday, 13th)
|| #################################################################### ||
\*======================================================================*/
if (defined('VB_AREA') && $vbulletin->kbank['enabled']) {	
	include_once(DIR . '/kbank/functions.php');
	if ($vbulletin->kbank['AutoRemovePointWhenDelete']){
		$ids = implode(',',array_keys($postarray));
		$points = $vbulletin->db->query_read("SELECT SUM(post.kbank) AS inpost,
			post.userid AS userid
			FROM `" . TABLE_PREFIX . "post` AS post
			WHERE postid IN ($ids)
			GROUP BY post.userid");
		while ($point = $vbulletin->db->fetch_array($points)) {
			giveMoney($point['userid'],$point['inpost'],'post');
		}
	}
}
?>