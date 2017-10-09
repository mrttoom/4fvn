<?php
/*======================================================================*\
|| #################################################################### ||
|| # kBank 2.0
|| # Coded by mrpaint
|| # Contact: mrpaint@gmail.com
|| # I'm a Vietnamese! Thank you for using this script
|| # Last Updated: 02 00 15-07-2008
|| #################################################################### ||
\*======================================================================*/
if (defined('VB_AREA') 
	AND $vbulletin->kbank['hide']['enabled']) {
	
	include_once(DIR . '/kbank/hide_functions.php');
	$post['pagetext_simp'] = $vbulletin->kBankHide->strip_bbcode($post['pagetext'],'archive');
	//vBulletin codes
	$post['pagetext_simp'] = strip_bbcode($post['pagetext_simp']);
	if ($vbulletin->options['wordwrap'] != 0) {
		$post['pagetext_simp'] = fetch_word_wrapped_string($post['pagetext_simp']);
	}
	$post['pagetext_simp'] = fetch_censored_text($post['pagetext_simp']);
}
?>