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
global $vbulletin;
if (defined('VB_AREA') 
	AND $vbulletin->kbank['enabled']
	AND ($vbulletin->options['templateversion'] < '3.7.0'
		OR $this->template_name == 'memberinfo_block_statistics')) {
	include_once(DIR . '/kbank/functions.php');

	global $kbank,$userinfo;

	if ($vbulletin->kbank['profile_elements'] & $vbulletin->kbank['bitfield']['display_elements']['kbank_show_points']) {
		$showpoints = true;
		if ($vbulletin->kbank['profile_elements'] & $vbulletin->kbank['bitfield']['display_elements']['kbank_show_toprichest']
			AND $vbulletin->kbank_toprichest
			AND isset($vbulletin->kbank_toprichest[$userinfo['userid']])) {
			$toprichest = construct_phrase($vbphrase['kbank_misc_toprichest_show'],$vbulletin->kbank_toprichest[$userinfo['userid']]['pos']);
		}
	}
	if ($vbulletin->kbank['profile_elements'] & $vbulletin->kbank['bitfield']['display_elements']['kbank_show_items']) {
		$showitems = true;
		
		//search for itemtype that user can produce
		$itemtypes = $vbulletin->db->query_read("SELECT *
			FROM `" . TABLE_PREFIX . "kbank_itemtypes` AS itemtypes
			WHERE userid LIKE '%,$userinfo[userid],%'");
			
		DEVDEBUG('[kBank Item] Query database for user itemtypes');

		if ($vbulletin->db->num_rows($itemtypes)) {
			$userinfo['kbank_itemtypes'] = '';
			while ($itemtypedata = $vbulletin->db->fetch_array($itemtypes)) {
				$userinfo['kbank_itemtypes'] .= 
					'<li>'
					. "<a href=\"{$vbulletin->kbank['phpfile']}?do=shop&username=$userinfo[username]&itemtypeid=$itemtypedata[itemtypeid]\" target=\"_blank\">$itemtypedata[name]</a>"
					. '</li>';
			}
			unset($itemtypedata);
		}
		$vbulletin->db->free_result($itemtypes);
		
		//search for user items
		if ($vbulletin->userinfo['userid'] != $userinfo['userid']) {
			//skip 1 query if user is viewing his/her profile. Everything has been loaded!
			findItemToWork($userinfo['userid'],true);
		}
		
		global $kbank_active_items;		
		if (count($kbank_active_items[$userinfo['userid']])) {
			$userinfo['kbank_active_items'] = $userinfo['kbank_selling_items'] = '';
			$items2show = fetchItemFromCache($kbank_active_items[$userinfo['userid']]);
			
			foreach ($items2show as $itemtypeid => $items2show_tmp) {
				foreach ($items2show_tmp as $item) {
					if ($item['status'] == KBANK_ITEM_SELLING) {
						//Selling item
						$userinfo['kbank_selling_items'] .=
							'<li>'
							. "<a href=\"{$vbulletin->kbank['phpfile']}?do=shop&username=$userinfo[username]&itemtypeid=$itemtypeid\" target=\"_blank\">$item[name]</a>"
							. iif($item['count'] > 1," x<strong style=\"color:red\">$item[count]</strong>")
							. '</li>';
					} else {
						$userinfo['kbank_active_items'] .=
							'<li>'
							. "<a href=\"{$vbulletin->kbank['phpfile']}?do=shop&itemtypeid=$itemtypeid\" target=\"_blank\">$item[name]</a>"
							//Because kBank Admin can view inactive items, we will show item status
							. iif(havePerm($vbulletin->userinfo,KBANK_PERM_ADMIN)," <em>$item[status_str]</em>")
							. iif($item['count'] > 1," x<strong style=\"color:red\">$item[count]</strong>")
							. '</li>';
					}
				}
			}
		}
	}
	
	eval('$template_hook["profile_stats_pregeneral"] .= "' . fetch_template('kbank_profile_stats_pregeneral') . '";');
}
?>