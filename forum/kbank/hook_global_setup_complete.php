<?php
/*======================================================================*\
|| #################################################################### ||
|| # kBank 2.4
|| # Coded by mrpaint
|| # Contact: mrpaint@gmail.com
|| # I'm a Vietnamese! Thank you for using this script
|| # Last Updated: 03:27 29-03-2009
|| #################################################################### ||
\*======================================================================*/
if (defined('VB_AREA') AND $vbulletin->kbank['enabled']) {
	include_once(DIR . '/kbank/functions.php');
	
	//announcement
	findAnnounce();
	if (count($kbank_announces) > 0 OR count($kbank_system_announces) > 0) {
		$kbank_announce_left = '';
		$kbank_announce_right = '';
		$kbank_announce_center = '';
		$kbank_announce_template = '';
		
		if (!$vbulletin->kbank['AnnounceTemplateOverwrite']) {
			//choose which template to edit
			switch (THIS_SCRIPT) {
				case 'index':
					$kbank_announce_template = 'FORUMHOME';
					break;
				case 'showthread':
					$kbank_announce_template = 'SHOWTHREAD';
					break;
				case 'forumdisplay':
					$kbank_announce_template = 'FORUMDISPLAY';
					break;
			}
		} else {
			//admin specified template to overwrite
			$kbank_announce_template = $vbulletin->kbank['AnnounceTemplateOverwrite'];
		}
		if ($kbank_announce_template) {
			//process announces
			if (count($kbank_system_announces) > 0) {
				//there are some system announces, we will get them first
				$tmp_keys = array();
				if (count($kbank_announces)) {
					//get random announce keys
					$tmp_keys = array_rand($kbank_announces,min(count($kbank_announces),$vbulletin->kbank['maxAnnounce'] - count($kbank_system_announces)));
				}
				$keys = array();
				for($i = 0; $i < count($kbank_system_announces); $i++) {
					//get system announces
					$keys[] = $i;
				}
				if (is_array($tmp_keys)) {
					foreach ($tmp_keys as $key) {
						//get normal announces
						$keys[] = count($kbank_system_announces) + $key;
					}
				} else {
					//special case with only 1 normal announces
					$keys[] = count($kbank_system_announces);
				}

				//merge with announces if needed
				if (is_array($kbank_announces)) {
					$kbank_announces = array_merge($kbank_system_announces,$kbank_announces);
				} else {
					$kbank_announces = $kbank_system_announces;
				}
			} else {
				//no system announces
				$keys = array();
				if (count($kbank_announces)) {
					//get random keys
					$keys = array_rand($kbank_announces,min(count($kbank_announces),$vbulletin->kbank['maxAnnounce']));
				}
			}
			
			if (!is_array($keys)) {
				$keys = array($keys); //only 1 announce
			}
			
			$i = 0;
			foreach ($keys as $key) {
				$announce =& $kbank_announces[$key];
				$i++;
				//choose position to place announce
				if (count($keys) > $vbulletin->kbank['maxAnnounce']/2) {
					if ($i <= count($keys)/2) {
						$varname = 'kbank_announce_left';
					} else {
						$varname = 'kbank_announce_right';
					}
				} else {
					$varname = 'kbank_announce_center';
				}
				
				eval('$' . $varname . ' .= "' . fetch_template('kbank_announcebit') . '";');
			}
			
			eval('$kbank_announce = "' . fetch_template('kbank_announce') . '";');
		}
		//output
		if ($kbank_announce) {
			if ($vbulletin->options['templateversion'] >= '3.7.0') {
				//vBulletin 3.7.0 and newer
				$ad_location['ad_navbar_below'] .= $kbank_announce;
			} else if ($vbulletin->kbank['AnnounceAfter']) {
				//stuff for vBulletin lower than 3.7.0
				$vbulletin->templatecache[$kbank_announce_template] = str_replace($vbulletin->kbank['AnnounceAfter'], $vbulletin->kbank['AnnounceAfter'] . ' $kbank_announce', $vbulletin->templatecache[$kbank_announce_template]);
				if ($kbank_announce_template == 'header') {
					eval('$header = "' . fetch_template('header') . '";');
				}
			}
		}
	}
	
	//prepare for moderator active item - Performance optimizing
	if ($vbulletin->kbank['itemEnabled']
		AND $vbulletin->options['showmoderatorcolumn']
		AND in_array(THIS_SCRIPT,array('index','forumdisplay','usercp'))) {
		switch (THIS_SCRIPT) {
			case 'index':
				if (empty($foruminfo['forumid'])) {
					// show all forums
					$forumid = -1;
				} else {
					$forumid = $foruminfo['forumid'];
				}
				break;
			case 'forumdisplay':
				$forumid = $foruminfo['forumid'];
				break;
			case 'usercp':
				$forumid = -1;
				break;
		}
		$userids2cache = array();
		$moderators_cache = $vbulletin->db->query_read("
			SELECT 
				moderator.userid AS userid
			FROM (
					SELECT 
						moderator.userid AS userid
						,CONCAT_WS(',',GROUP_CONCAT(forum.parentlist),GROUP_CONCAT(moderator.forumid)) AS parentlist
					FROM `" . TABLE_PREFIX . "moderator` AS moderator
					LEFT JOIN `" . TABLE_PREFIX . "forum` AS forum ON (forum.forumid = moderator.forumid)
					GROUP BY moderator.userid
				) AS moderator
			WHERE
				moderator.parentlist = '$forumid'
				OR moderator.parentlist LIKE '%,$forumid'
				OR moderator.parentlist LIKE '$forumid,%'
				OR moderator.parentlist LIKE '%,$forumid,%'
			GROUP BY moderator.userid
		");
		
		DEVDEBUG('[kBank] Cache moderators userid');
		
		while ($moderator = $vbulletin->db->fetch_array($moderators_cache)) {
			$userids2cache[] = $moderator['userid'];
		}
	}
	
	//Who visited code - copied with permission from Paul M
	if ($vbulletin->kbank['who']['enabled']
		AND THIS_SCRIPT == 'index' //Only run in index
		AND $vbulletin->userinfo['userid'] //Only show to registered member - Performance optimizing
		) {
		if ($vbulletin->kbank['who']['get24h'])
		{
			$cutoff = TIMENOW - 86400;
			$whodesc = $vbphrase['visited_today_24'];
		}
		else
		{
			$whodesc = $vbphrase['visited_today'];
			$tnow = date('YmdHis',TIMENOW - intval($vbulletin->options['hourdiff'])); 
			$cutoff = TIMENOW - (substr($tnow,8,2)*3600 + substr($tnow,10,2)*60 + substr($tnow,12,2)); 
		}

		unset ($whotoday);
		$todaysusers = $vbulletin->db->query_read_slave("
			SELECT userid,username,usergroupid,displaygroupid,infractiongroupid,lastactivity,options
			FROM ".TABLE_PREFIX."user FORCE INDEX (lastactivity)
			WHERE lastactivity > $cutoff
			ORDER BY username
		");

		DEVDEBUG('[kBank] Get $todaysusers');
		
		//Cache - Performance optimizing
		$users = array();
		$userids = array();
		while ($today = $vbulletin->db->fetch_array($todaysusers)) {
			$users[] = $today;
			$userids[] = $today['userid'];
		}
		if (count($userids2cache)) {
			$userids = array_merge($userids,$userids2cache);
			$userids2cache = array(); //Reset
		}
		findItemsToWork($userids,false,false);
		//Cache - complete!

		$totaltoday = 0;
		foreach ($users as $today) {
			$totaltoday += 1;
			$today['markinv'] = '';
			$today['visible'] = true ;
			if ($today['options'] & $vbulletin->bf_misc_useroptions['invisible']) 
			{
				$today['visible'] = false ;
				if (($vbulletin->userinfo['permissions']['genericpermissions'] 
					& $vbulletin->bf_ugp_genericpermissions['canseehidden']) 
					OR $today['userid'] == $vbulletin->userinfo['userid'])
				{
					$today['markinv'] = '*';
					$today['visible'] = true ;
				}
			}
			if ($today['visible']) 
			{				
				$today['wrdate'] = vbdate($vbulletin->options['timeformat'], $today['lastactivity']);
				$today['musername'] = fetch_musername($today);
				eval('$whotoday .= "' . fetch_template('who_visited_Display_Visitors_User') . '" . ", ";');
			}
		}

		if ($whotoday)
		{
			$whotoday = substr($whotoday, 0, -2);
		}
		else
		{
			$whotoday = $vbphrase['no_visitors'];
		}

		$ftotaltoday = vb_number_format($totaltoday);
		$whotitle = construct_phrase($whodesc,$ftotaltoday);

		$vbulletin->templatecache['FORUMHOME'] = str_replace(
			'<!-- end logged-in users -->'
			, '<!-- end logged-in users -->' . $vbulletin->templatecache['who_visited_Display_Visitors']
			, $vbulletin->templatecache['FORUMHOME']);
	}
	
	//Only build navbar item for registered user
	if ($vbulletin->userinfo['userid']) {
		//button
		//eval('$template_hook["navbar_buttons_left"] .= "' . fetch_template('kbank_navbar_button') . '";'); - moved to global start to work better
		
		//navbar popup menu
		$kbank_navbar_popup_menu_more = '';
		if (is_array($kbank_active_items[$vbulletin->userinfo['userid']])) {
			$items2show = fetchItemFromCache(
				$kbank_active_items[$vbulletin->userinfo['userid']]
				, $vbulletin->kbank['phpfile'] . '?do=myitems&itemid=%1$s#item%1$s'
				, 'itemid'
				, true
				, true);

			foreach ($items2show as $items2show_tmp) {
				foreach ($items2show_tmp as $item2show) {
					$useHead = false;
					$fulllink = $item2show['fulllink'];
					$link = $item2show['link'];
					$text = 
						$item2show['name']
						. iif($item2show['status_str'],' ' . $item2show['status_str'])
						. iif($item2show['count'] > 1," x<strong style=\"color:red\">$item2show[count]</strong>");
					eval('$kbank_navbar_popup_menu_more .= " ' . fetch_template('kbank_navbar_popup_menu_bit') . '";');
				}
			}
		}
		//Total Money
		$fulllink = true;
		$link = '#';
		$text = $vbulletin->kbank['name'] . ': ' . $vbulletin->userinfo['kbank'];
		eval('$kbank_navbar_popup_menu_more .= " ' . fetch_template('kbank_navbar_popup_menu_bit') . '";');
		
		($hook = vBulletinHook::fetch_hook('kbank_navbar_popup_menu')) ? eval($hook) : false;
		
		//Add menu in a new way (from vBBlog) 31-12-2008
		//Old codes:
		/*eval('$template_hook["navbar_popup_menus"] .= "' . fetch_template('kbank_navbar_popup_menu') . '";');
		$vbulletin->templatecache['navbar'] = str_replace(
			'<!-- NAVBAR POPUP MENUS -->'
			,'<!-- NAVBAR POPUP MENUS --> $template_hook[navbar_popup_menus]'
			, $vbulletin->templatecache['navbar']);*/
		eval('$header .= "' . fetch_template('kbank_navbar_popup_menu') . '";');
		//navbar popup menu - complete!
	}

	//Prepare items?
	if (count($userids2cache)) {
		findItemsToWork($userids2cache);
	}
}
?>