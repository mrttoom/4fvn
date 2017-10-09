<?php
/*======================================================================*\
|| #################################################################### ||
|| # kBank 2.4
|| # Coded by mrpaint
|| # Contact: mrpaint@gmail.com
|| # I'm a Vietnamese! Thank you for using this script
|| # Last Updated: 03:25 29-03-2009
|| #################################################################### ||
\*======================================================================*/
if (defined('VB_AREA') && $vbulletin->kbank['enabled']) {
	include_once(DIR . '/kbank/functions.php');
	
	if ($user["{$vbulletin->kbank['field']}"]) {
		$user['kbank'] = vb_number_format($user["{$vbulletin->kbank['field']}"],$vbulletin->kbank['roundup']);
	}
	//prepair for some templates display
	$user['iskBankAdmin'] = iskBankAdmin($user['userid']);
	
	$user['kbank_grantedids_a'] = array($user['userid']);
	if ($user['kbank_granted_count']) {
		$user['kbank_granted'] = array();
		$user['kbank_grantedids'] = '';
		$granted_userid = explode('|',$user['kbank_granted_userid']);
		$granted_username = explode('|',$user['kbank_granted_username']);
		$granted_usergroupid = explode('|',$user['kbank_granted_usergroupid']);
		$granted_membergroupids = explode('|',$user['kbank_granted_membergroupids']);
		$granted_money = explode('|',$user["kbank_granted_{$vbulletin->kbank['field']}"]);
		for ($i = 0; $i < $user['kbank_granted_count']; $i++) {
			$user['kbank_granted'][$granted_userid[$i]] = array(
				'userid' => $granted_userid[$i],
				'username' => $granted_username[$i],
				'usergroupid' => $granted_usergroupid[$i],
				'membergroupids' => $granted_membergroupids[$i],
				$vbulletin->kbank['field'] => $granted_money[$i],
			);
			$user['kbank_grantedids'] .= ',' . $granted_userid[$i];
			$user['kbank_grantedids_a'][] = $granted_userid[$i];
		}
	}

	global $KBANK_HOOK_NAME;
	$KBANK_HOOK_NAME = KBANK_FETCH_USERINFO;

	findItemToWork($userid);
}
?>