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
global $vbulletin;
if (defined('VB_AREA') 
	AND $vbulletin->kbank['award']['enabled']) {
	
	if (in_array(THIS_SCRIPT,$vbulletin->kbank['award']['AllowedScript'])) {
		//Our control only display in some script (predefined by AllowedScript), not everything using postbit_display_complete
		include_once(DIR . '/kbank/award_functions.php');
		global $ids,$kbank_award_done,$threadinfo,$tmp_post;
		
		$kbank_award_message = "";
		
		if (!$kbank_award_done) {
			//ONE TIME query database for ALL awarded/thanked
			if (!$ids) {
				fetchAwarded($post['postid'],false);
			} else {
				fetchAwarded(explode(',',$ids),false);	
			}
			$kbank_award_done = true; //marked as work done!
		}
		
		$tmp_post = $post; //store important info about this post
		$kbank_award_message = showAwardBox($post['postid']); //build Award/Thank messages
		$permAward = fetchPerm($vbulletin->userinfo,$post,$threadinfo); //fetch permission
		eval('$kbank_award_button = " ' . fetch_template('kbank_award_button') . '";');	//build Award/Thank button
		eval('$kbank_award_box = " ' . fetch_template('kbank_award_box') . '";'); //build Award/Thank box
		
		//Output through template_hook
		$template_hook['postbit_signature_end'] .= $kbank_award_box;
		$template_hook['postbit_controls'] .= $kbank_award_button;
	}
	
	$kbankname = $vbulletin->kbank['name'];
	if ($vbulletin->kbank['postbit_elements'] & $vbulletin->kbank['bitfield']['display_elements']['kbank_show_award']) {
		$awardedtimes = vb_number_format($post[$vbulletin->kbank['award']['awardedtimes']]);
		$awardedamount = vb_number_format($post[$vbulletin->kbank['award']['awardedamount']],$vbulletin->kbank['roundup']);
	}
	if ($vbulletin->kbank['postbit_elements'] & $vbulletin->kbank['bitfield']['display_elements']['kbank_show_thank']) {
		$thanksenttimes = vb_number_format($post[$vbulletin->kbank['award']['thanksenttimes']]);
		$thanksentamount = vb_number_format($post[$vbulletin->kbank['award']['thanksentamount']],$vbulletin->kbank['roundup']);
		$thankreceivedtimes = vb_number_format($post[$vbulletin->kbank['award']['thankreceivedtimes']]);
		$thankreceivedamount = vb_number_format($post[$vbulletin->kbank['award']['thankreceivedamount']],$vbulletin->kbank['roundup']);
	}
	
	eval('$template_hook["postbit_userinfo_right_after_posts"] .= "' . fetch_template('kbank_award_postbit_right_after_posts') . '";');
}
?>