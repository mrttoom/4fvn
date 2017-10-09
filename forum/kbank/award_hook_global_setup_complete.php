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
	//load kBank Award popup search menu items
	eval('$template_hook["navbar_search_menu"] .= " ' . fetch_template('kbank_award_navbar_search_menu') . '";');
	
	//we will need javascript clientscript here
	if (in_array(THIS_SCRIPT,array('showthread','showpost')))
	{
		$headinclude .= '<script type="text/javascript" src="clientscript/kbank_award_support.js?v=' . $vbulletin->options['simpleversion'] . '"></script>';
	}
}
?>