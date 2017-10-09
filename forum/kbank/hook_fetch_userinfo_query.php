<?php
/*======================================================================*\
|| #################################################################### ||
|| # kBank 2.4
|| # Coded by mrpaint
|| # Contact: mrpaint@gmail.com
|| # I'm a Vietnamese! Thank you for using this script
|| # Last Updated: 03:26 29-03-2009
|| #################################################################### ||
\*======================================================================*/
if (defined('VB_AREA') && $vbulletin->kbank['enabled']) {
	include_once(DIR . '/kbank/functions.php');
	
	$hook_query_fields .= "
		,kbank_granted.allow_count AS kbank_granted_count
		,kbank_granted.allow_userid AS kbank_granted_userid
		,kbank_granted.allow_username AS kbank_granted_username
		,kbank_granted.allow_usergroupid AS kbank_granted_usergroupid
		,kbank_granted.allow_membergroupids AS kbank_granted_membergroupids
		,kbank_granted.allow_{$vbulletin->kbank['field']} AS kbank_granted_{$vbulletin->kbank['field']}
	";
	$hook_query_joins .= "
		LEFT JOIN (
			SELECT
				kbank_granted.userid AS userid
				,COUNT(*) AS allow_count
				,GROUP_CONCAT(user.userid SEPARATOR '|') AS allow_userid
				,GROUP_CONCAT(user.username SEPARATOR '|') AS allow_username
				,GROUP_CONCAT(user.usergroupid SEPARATOR '|') AS allow_usergroupid
				,GROUP_CONCAT(user.membergroupids SEPARATOR '|') AS allow_membergroupids
				,GROUP_CONCAT(user.{$vbulletin->kbank['field']} SEPARATOR '|') AS allow_{$vbulletin->kbank['field']}
			FROM `" . TABLE_PREFIX . "kbank_granted_permission` AS kbank_granted
			INNER JOIN `" . TABLE_PREFIX . "user` AS user ON (user.userid = kbank_granted.allowid)
			WHERE kbank_granted.userid = $userid
			GROUP BY kbank_granted.userid
		) AS kbank_granted ON (kbank_granted.userid = user.userid)
	";
}
?>