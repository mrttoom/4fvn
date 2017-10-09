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
	$kbank_globaltemplates = array();
	
	if ($vbulletin->userinfo['userid']) {
		//load template for navbar button
		$kbank_globaltemplates = array_merge($kbank_globaltemplates, array(
			'kbank_navbar_button',
			'kbank_navbar_popup_menu',
			'kbank_navbar_popup_menu_bit'
		));
	}
	//load template page independent
	$kbank_globaltemplates = array_merge($kbank_globaltemplates, array(
		//announce
		'kbank_announce',
		'kbank_announcebit',
		//forum policy
		'kbank_forum_policy',
	));
	if (THIS_SCRIPT == 'member') {
		//load template for member profile info
		$kbank_globaltemplates[] = 'kbank_profile_stats_pregeneral';
	}
	if (in_array(THIS_SCRIPT,array('showthread','showpost','private'))) {
		//load template for poster info (postbit)
		$kbank_globaltemplates[] = 'kbank_postbit_right_after_posts';
	}
	//Who visited
	if (THIS_SCRIPT == 'index') {
		//load template for Who visited list
		$kbank_globaltemplates = array_merge($kbank_globaltemplates, array(
			'who_visited_Display_Visitors',
			'who_visited_Display_Visitors_User'
		));
	}
	
	//Prepare complete! Do the real job
	if (count($kbank_globaltemplates)) {
		$globaltemplates = array_merge($globaltemplates, $kbank_globaltemplates);
	}
}
?>