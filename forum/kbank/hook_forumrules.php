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
if (defined('VB_AREA') AND $vbulletin->kbank['enabled']) {
	include_once(DIR . '/kbank/functions.php');
	
	global $kbank;
	
	$kbank_forumpolicy = getPointPolicy($foruminfo);
	
	if ($kbank_forumpolicy['kbank_perthread'] + $kbank_forumpolicy['kbank_perreply'] + $kbank_forumpolicy['kbank_perchar'] > 0)
	{
		//1 of them is activated
		//eval('$forumrules = "' . fetch_template('kbank_forum_policy') . '";');
		$vbulletin->templatecache['forumrules'] = str_replace(
			'$forumrules',
			$vbulletin->templatecache['forumrules'],
			$vbulletin->templatecache['kbank_forum_policy']
		);
		//echo 'here';exit;
	}
}
?>