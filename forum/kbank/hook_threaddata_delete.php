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
if (defined('VB_AREA') && $vbulletin->kbank['enabled']) {	
	include_once(DIR . '/kbank/functions.php');
	if ($vbulletin->kbank['AutoRemovePointWhenDelete']){
		$posts = $vbulletin->db->query_read("
			SELECT SUM(post.kbank) as points,
			post.userid as userid
			FROM " . TABLE_PREFIX . "post AS post 
			WHERE threadid = " . intval($threadid) . "
				AND visible = 1
			GROUP BY post.userid
		");
		while($post = $vbulletin->db->fetch_array($posts)) {
			giveMoney($post['userid'],$post['points']*(-1),'post');
		}
	}
}
?>