<?php
/*======================================================================*\
|| #################################################################### ||
|| # kBank 2.4
|| # Coded by mrpaint
|| # Contact: mrpaint@gmail.com
|| # I'm a Vietnamese! Thank you for using this script
|| # Last Updated: 03:27 29-03-2009
|| #################################################################### ||
\*======================================================================*/
if (defined('VB_AREA') && $vbulletin->kbank['enabled']) {
	include_once(DIR . '/kbank/functions.php');
	global $KBANK_HOOK_NAME, $kbank_userinfo_tmp;
	$KBANK_HOOK_NAME = KBANK_FETCH_MUSERNAME;

	$kbank_userinfo_tmp = $user;
	$kbank_userinfo_tmp['userdisplaygroupid'] = $displaygroupid;

	if (!isset($vbulletin->userinfo)) {
		//userinfo has not been initialized! We believe fetch_musername is being called for current user
		//cache ALL user items
		$userids = array($user['userid']);
		if ($user['kbank_granted_count']) {
			$userids = array_merge($userids,explode('|',$user['kbank_granted_userid']));
		}
		findItemsToWork($userids,false,true);
	}
	
	if (findItemToWork($user['userid'])) {
		$user = $kbank_userinfo_tmp;
	}
}
?>