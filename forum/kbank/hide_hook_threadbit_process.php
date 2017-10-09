<?php
/*======================================================================*\
|| #################################################################### ||
|| # kBank 1.9.2
|| # Coded by mrpaint
|| # Contact: mrpaint@gmail.com
|| # I'm a Vietnamese! Thank you for using this script
|| # Last Updated: 01:20 21-06-2008
|| #################################################################### ||
\*======================================================================*/
if (defined('VB_AREA') && $vbulletin->kbank['hide']['enabled']) {
	include_once(DIR . '/kbank/hide_functions.php');
	
	// format thread preview if there is one
	if (isset($thread['preview'])
		AND isset($thread['preview_cache'])
		AND $vbulletin->options['threadpreview'] > 0)
	{
		$thread['preview'] = $vbulletin->kBankHide->strip_bbcode($thread['preview_cache'],'postpreview');
		//vBulletin codes
		$thread['preview'] = strip_quotes($thread['preview']);
		$thread['preview'] = htmlspecialchars_uni(fetch_censored_text(fetch_trimmed_title(
			strip_bbcode($thread['preview'], false, true),
			$vbulletin->options['threadpreview']
		)));
	}
}
?>