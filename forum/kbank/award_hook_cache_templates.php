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
if (defined('VB_AREA') AND $vbulletin->kbank['award']['enabled']) {
	$award_globaltemplates = array();
	
	$award_globaltemplates[] = 'kbank_award_navbar_search_menu';
	
	if (in_array(THIS_SCRIPT,array('showthread','showpost','private'))) {
		$award_globaltemplates = array_merge($award_globaltemplates, array(
			'kbank_award_button',
			'kbank_award_box',
			'kbank_award_confirm',
			'kbank_award_main',
			'kbank_award_message',
			'kbank_award_postbit_right_after_posts'
		));
	}
	if (THIS_SCRIPT == 'member') {
		$award_globaltemplates[] = 'kbank_award_profile_stats_pregeneral';
	}
	
	//Prepare complete! Do the real job
	if (count($award_globaltemplates)) {
		$globaltemplates = array_merge($globaltemplates, $award_globaltemplates);
	}
}
?>