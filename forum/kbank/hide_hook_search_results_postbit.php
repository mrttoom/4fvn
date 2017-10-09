<?php
/*======================================================================*\
|| #################################################################### ||
|| # kBank 1.9.4
|| # Coded by mrpaint
|| # Contact: mrpaint@gmail.com
|| # I'm a Vietnamese! Thank you for using this script
|| # Last Updated: 21:07 25-06-2008
|| #################################################################### ||
\*======================================================================*/
if (defined('VB_AREA') && $vbulletin->kbank['hide']['enabled']) {
	if (isset($post['pagetext_cache'])) {
		include_once(DIR . '/kbank/hide_functions.php');
		$post['pagetext'] = $vbulletin->kBankHide->strip_bbcode($post['pagetext_cache'],'postpreview');
		//vBulletin codes
		$post['pagetext'] = preg_replace('#\[quote(=(&quot;|"|\'|)??.*\\2)?\](((?>[^\[]*?|(?R)|.))*)\[/quote\]#siUe', "process_quote_removal('\\3', \$display['highlight'])", $post['pagetext']);
		$post['pagetext'] = htmlspecialchars_uni(fetch_censored_text(trim(fetch_trimmed_title(strip_bbcode($post['pagetext'], 1), 200))));
	}
}
?>