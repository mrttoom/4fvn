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
global $vbulletin;
if (defined('VB_AREA') && $vbulletin->kbank['enabled']) {	
	include_once(DIR . '/kbank/functions.php');
	if ($vbulletin->kbank['AutoRemovePointWhenDelete']){
		$points = $vbulletin->db->query_first("
			SELECT kbank,
				userid
			FROM " . TABLE_PREFIX . "post AS `post`
			WHERE post.postid = " . intval($postid));
		giveMoney($points['userid'],$points['kbank']*(-1),'post');
	}
}
?>