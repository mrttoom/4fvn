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
if (defined('VB_AREA') AND $vbulletin->kbank['enabled']) {	
	if ($vbulletin->kbank['AutoRecalcPointWhenEdit']){
		$postinfo['kbank'] = 0;
		if (!empty($postinfo) && !empty($foruminfo) && is_array($postinfo) && is_array($foruminfo)){
			include_once(DIR . '/kbank/functions.php');
						
			$oldpoints = $postinfo['kbank'];		
			$newpoints = kbank_points($postinfo, $foruminfo['forumid'], $type); //calculating new points

			if ($oldpoints != $newpoints){
				$givepoints = $newpoints - $oldpoints;
				$postinfo['kbank'] = $newpost;
				$dataman->set('kbank', $newpoints);
			}
		}	
	}
}
?>