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
if (defined('VB_AREA') && $vbulletin->kbank['hide']['enabled']) {
	if ($vbulletin->options['threadpreview'] > 0) {
		//query 1 more pagetext to process later (strip our bbcodes)
		$hook_query_fields .= ', post.pagetext AS preview_cache';
	}
}
?>