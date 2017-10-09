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
if (defined('VB_AREA') AND $vbulletin->kbank['enabled']) {
	include_once(DIR . '/kbank/functions.php');
	global $vbphrase,$kbank,$KBANK_HOOK_NAME,$ids,$kbank_done,$kbank_active_items;
	
	if (!$kbank_done) {
		if ($ids) {
			//Prepare with multi posts thread (ONE query for the whole page)
			findItemsToWork(
				explode(',',$ids)
				,false
				,false
				,array(
					'join' => "INNER JOIN `" . TABLE_PREFIX . "post` AS post ON (post.userid = items.userid)"
					,'idcheckfield' => 'post.postid'
				)
			);
		}
		$kbank_done = true;
		//Tricky way to re-create musername if item found
		if (count($kbank_active_items[$post['userid']])) {
			$post['musername'] = null;
			$post['musername'] = fetch_musername($post);
		}
	}
	
	$KBANK_HOOK_NAME = KBANK_POSTBIT_COMPLETE;
	findItemToWork($this->post['userid'],false);
	
	if ($vbulletin->kbank['postbit_elements'] & $vbulletin->kbank['bitfield']['display_elements']['kbank_show_points']) {
		$showpoints = true;
		
		if ($post["{$vbulletin->kbank['field']}"]) 
			$post["{$vbulletin->kbank['field']}"] = $post['kbank'] = vb_number_format($post["{$vbulletin->kbank['field']}"],$vbulletin->kbank['roundup']);
		
		if ($vbulletin->kbank['postbit_elements'] & $vbulletin->kbank['bitfield']['display_elements']['kbank_show_toprichest']
			AND $vbulletin->kbank_toprichest
			AND isset($vbulletin->kbank_toprichest[$post['userid']])) {
			$post['kbank_toprichest'] = construct_phrase($vbphrase['kbank_misc_toprichest_show'],$vbulletin->kbank_toprichest[$post['userid']]['pos'])/* . topChangeDisplay($vbulletin->kbank_toprichest[$post['userid']])*/;
		}
	}
	eval('$template_hook["postbit_userinfo_right_after_posts"] .= "' . fetch_template('kbank_postbit_right_after_posts') . '";');
}
?>