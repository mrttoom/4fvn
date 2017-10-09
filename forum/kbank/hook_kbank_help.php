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
	AND THIS_SCRIPT == 'kbank' //Only run within our code or phrases can not be show!
	AND $vbulletin->kbank['enabled']) {

	$vbulletin->input->clean_array_gpc('r', array(
		'page'          => TYPE_STR
	));
	
	if (!$vbulletin->GPC['page']) {
		//Auto load index
		$vbulletin->GPC['page'] = 'index';
	}
	
	//Special function
	function fetch_help($page) {
		$filename = DIR . "/kbank/help/{$page}.htm";
		if (file_exists($filename)) {
			$content = file_get_contents($filename);
			if (preg_match('/\<\!\-\- Page Title: "((.)+)" End Page Title\-\-\>/',$content,$matches)) {
				$title = $matches[1];
			} else {
				$title = null;
			}
			$content = str_replace(array('""','"','||'),array('||','\"','"'),$content); //prepair for later use with eval
			return array(
				'title' => $title,
				'content' => $content
			);
		} else {
			return false;
		}
	}
	
	if ($help = fetch_help($vbulletin->GPC['page'])) {
		$url_prefix = $vbulletin->kbank['phpfile'] . "?$session[sessionurl]do=help&page=";
		$image_url = 'kbank/help/';
		eval('$help_content = "' . $help['content'] . '";');
		$navbits[""] = $help['title'];
	} else {
		//can not found help entry!
		eval(standard_error(fetch_error('kbank_help_not_found')));
	}
}
?>