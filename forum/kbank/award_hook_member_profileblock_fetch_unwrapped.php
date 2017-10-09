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
	AND $vbulletin->kbank['award']['enabled']
	AND ($vbulletin->options['templateversion'] < '3.7.0' //Support for older version of vBulletin
		OR $this->template_name == 'memberinfo_block_statistics' //Only run inside statistics block
		)
	) {

	if ($vbulletin->kbank['profile_elements'] & $vbulletin->kbank['bitfield']['display_elements']['kbank_show_award']) {
		$received_award['count'] = vb_number_format($userinfo[$vbulletin->kbank['award']['awardedtimes']]);
		$received_award['total'] = vb_number_format($userinfo[$vbulletin->kbank['award']['awardedamount']],$vbulletin->kbank['roundup']) . " {$vbulletin->kbank['name']}";
	}
	if ($vbulletin->kbank['profile_elements'] & $vbulletin->kbank['bitfield']['display_elements']['kbank_show_thank']) {
		$received_thank['count'] = vb_number_format($userinfo[$vbulletin->kbank['award']['thankreceivedtimes']]);
		$received_thank['total'] = vb_number_format($userinfo[$vbulletin->kbank['award']['thankreceivedamount']],$vbulletin->kbank['roundup']) . " {$vbulletin->kbank['name']}";
		$sent_thank['count'] = vb_number_format($userinfo[$vbulletin->kbank['award']['thanksenttimes']]);
		$sent_thank['total'] = vb_number_format($userinfo[$vbulletin->kbank['award']['thanksentamount']],$vbulletin->kbank['roundup']) . " {$vbulletin->kbank['name']}";
	}

	eval('$template_hook["profile_stats_pregeneral"] .= "' . fetch_template('kbank_award_profile_stats_pregeneral') . '";');
}
?>