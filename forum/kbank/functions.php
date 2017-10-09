<?php
/*======================================================================*\
|| #################################################################### ||
|| # kBank 2.5.1
|| # Coded by mrpaint
|| # Contact: mrpaint@gmail.com
|| # I'm a Vietnamese! Thank you for using this script
|| # Last Updated: 01:47 05-04-2009
|| #################################################################### ||
\*======================================================================*/
define('PERPAGE_DEFAULT',15);

define('KBANK_ACTION_USE',0);
define('KBANK_ACTION_SWITCH',2);
define('KBANK_ACTION_USE_CUSTOMNAME',99);

define('KBANK_ITEM_PENDING',-100);
define('KBANK_ITEM_DELETED',-99);
define('KBANK_ITEM_BIDDING',-3);
define('KBANK_ITEM_SELLING_UNLIMIT',-2);
define('KBANK_ITEM_SELLING',-1);
define('KBANK_ITEM_AVAILABLE',0);
define('KBANK_ITEM_ENABLED',1);
define('KBANK_ITEM_USED',2);
define('KBANK_ITEM_USED_WAITING',3);

//kBank working places
define('KBANK_GLOBAL_START',1);
define('KBANK_FETCH_USERINFO',2);
define('KBANK_POSTBIT_COMPLETE',3);
define('KBANK_MEMBER_COMPLETE',4);
define('KBANK_ONLINE_LIST',5);
define('KBANK_THREADBIT_PROCESS',6);
define('KBANK_THREADBIT_DISPLAY',7);
define('KBANK_FETCH_MUSERNAME',8);
define('KBANK_VBSHOUT',100);

define('KBANK_NO_TAX','no_tax');

define('KBANK_LOGTYPE_LOG',1); //admin & member action log
define('KBANK_LOGTYPE_STAT',2); //system statistic

define('KBANK_ERROR_NO_PERM',-99);

define('KBANK_PERM_ADMIN',1);
define('KBANK_PERM_COMPANY',2);

//Award
define('AWARD_REMOVE',-5011);

include_once(DIR . '/kbank/classes.php');

//Admin functions
function getStatistics($query = false,$limit = 1,$before = false,$after = false) {
	global $vbulletin; 
	
	$result = null;
	$limit = (int)$limit;
	
	if ($query) {
		//Do database query
		$reader = $vbulletin->db->query_read("SELECT *
			FROM `" . TABLE_PREFIX . "kbank_banklogs`
			WHERE itemtype = 'money'");
		$money = array();
		while ($cache = $vbulletin->db->fetch_array($reader)) {
			switch ($cache['itemname']) {
				case 'post': $money['post'] += $cache['amount'];
					break;
				case 'register': $money['register'] += $cache['amount'];
					break;
				case 'referer': $money['referer'] += $cache['amount'];
					break;
				case 'admindonate': $money['donate'] += $cache['amount'];
					break;
				default: if ($cache['amount'] > 0) {
						$money['in'] += $cache['amount'];
					} else {
						$money['other'] += $cache['amount'];
					}
					break;
			}
		}
		$money['out'] = $money['post'] + $money['register'] + $money['referer'] + $money['donate'] + $money['other'];
		$money['instock'] = $money['in'] + $money['out'];
		$money['out'] *= -1;
		$money['post'] *= -1;
		$money['register'] *= -1;
		$money['referer'] *= -1;
		$money['donate'] *= -1;
		$money['other'] *= -1;
		
		$member = $vbulletin->db->query_first("SELECT SUM({$vbulletin->kbank['field']}) AS total
			FROM `" . TABLE_PREFIX . "user`");
		$money['member'] = $member['total'];
		
		$total = $vbulletin->db->query_first("SELECT SUM({$vbulletin->kbank['postfield']}) AS total
			FROM `" . TABLE_PREFIX . "post`");
		$money['total'] = $total['total'];
		
		$result = $money;
	} else {
		//Just read cached data
		
		$query_where = '';
		$order_key = 'DESC';
		if ($before !== false) {
			$query_where .= " AND timeline < $before";
		}
		if ($after !== false) {
			$query_where .- " AND timeline > $after";
			$order_key = 'ASC';
		}
		
		$cached = $vbulletin->db->query_read("
			SELECT *
			FROM `" . TABLE_PREFIX . "kbank_logs`
			WHERE 
				type = " . KBANK_LOGTYPE_STAT . "
				$query_where
			ORDER BY timeline $order_key
			LIMIT $limit
		");
		
		if ($vbulletin->db->num_rows($cached)) {
			if ($limit == 1) {
				$tmp = $vbulletin->db->fetch_array($cached);
				$result = unserialize($tmp['detail']);
				$result['timeline'] = $tmp['timeline'];
			} else {
				$result = array();
				while ($rec = $vbulletin->db->fetch_array($cached)) {
					$result[$rec['timeline']] = unserialize($rec['detail']);
				}
				unset($rec);
				
				//Rearrange
				krsort($result);
			}
		}
		
		$vbulletin->db->free_result($cached);
	}
	
	return $result;
}

function updateItemTypeCache() {
	//store all itemtypes data
	//run after add/edit itemtype
	global $vbulletin;
	
	$itemtypes_cache = array();
	$itemtypes = $vbulletin->db->query_read("
		SELECT *
		FROM `" . TABLE_PREFIX . "kbank_itemtypes`
	");
	
	while ($itemtype = $vbulletin->db->fetch_array($itemtypes)) {
		$options = unserialize($itemtype['options']);
		if (!$options['deleted']) {
			$itemtypes_cache[$itemtype['itemtypeid']] = $itemtype;
		}
	}
	$vbulletin->db->free_result($itemtypes);
	unset($itemtype);
	
	write_datastore('itemtypes',$itemtypes_cache);
	return $itemtypes_cache;
}

function updateAnnounceCache() {
	//store all announce items
	//run after enable/disable announce item
	global $vbulletin;
	
	$itemtypeids = array();
	foreach ($vbulletin->kbank_itemtypes as $itemtypeid => $itemtypedata) {
		$itemtype =& newItemType($itemtypeid,$itemtypedata);
		if ($itemtype->options['isAnnounce']) {
			$itemtypeids[] = $itemtypeid;
		}
	}
	
	$announces_cache = array();
	$announces = $vbulletin->db->query_read("
		SELECT
			items.*
			,user.username
		FROM `" . TABLE_PREFIX . "kbank_items` AS items
		LEFT JOIN `" . TABLE_PREFIX . "user` AS user ON (user.userid = items.userid)
		WHERE 
			items.type IN (" . implode(',',$itemtypeids) . ")
			AND items.status > " . KBANK_ITEM_AVAILABLE . "
			AND (items.expire_time > " . TIMENOW . "
				OR items.expire_time < 0)
	");
	
	while ($announce = $vbulletin->db->fetch_array($announces)) {
		$announces_cache[$announce['itemid']] = $announce;
	}
	$vbulletin->db->free_result($announces);
	unset($announce);
	
	write_datastore('announces',$announces_cache);
	return $announces_cache;
}

function updateWarningItem($skip = false) {
	//store all bidding, pending items
	//run after add/edit item, approve/bid item
	global $vbulletin;
	
	$skipids = array();
	if (is_array($skip)) {
		$skipids = $skip;
	} else if (is_numeric($skip) AND $skip > 0) {
		$skipids[] = $skip;
	}
	
	$items_cache = array();
	$items = $vbulletin->db->query_read("
		SELECT
			items.*
			,user.username
		FROM `" . TABLE_PREFIX . "kbank_items` AS items
		LEFT JOIN `" . TABLE_PREFIX . "user` AS user ON (user.userid = items.userid)
		WHERE 
			(items.status = " . KBANK_ITEM_PENDING . "
			AND (items.expire_time > " . TIMENOW . " OR items.expire_time < 0))
			OR (items.status = " . KBANK_ITEM_BIDDING . ")
	");
	
	while ($item = $vbulletin->db->fetch_array($items)) {
		if (!in_array($item['itemid'],$skipids)) {
			//Sometime we skip some ids
			$items_cache[$item['itemid']] = $item;
		}
	}
	$vbulletin->db->free_result($items);
	unset($item);
	
	write_datastore('warningitems',$items_cache);
	return $items_cache;
}

function cmp_username($a,$b) {
	return strcasecmp($a['username'],$b['username']);
}

function cmp_total($a,$b) {
	if ($a['total'] == $b['total']) return 0;
	return ($a['total'] > $b['total'])?-1:1;
}

//Global functions
function write_datastore($title, $data) {
	global $vbulletin;
	
	if (is_array($data)) {
		$unserialize  = 1;
		$data = serialize($data);
	}

	if ($title != '')
	{
		/*insert query*/
		$vbulletin->db->query_write("
			REPLACE INTO " . TABLE_PREFIX . "datastore
				(title, data, unserialize)
			VALUES
				('" . $vbulletin->db->escape_string('kbank_'.trim($title)) . "', '" . $vbulletin->db->escape_string(trim($data)) . "', " . intval($unserialize) . ")
		");
	}
}

function read_datastore($title) {
	global $vbulletin;

	$cache = $vbulletin->db->query_first("
		SELECT *
		FROM " . TABLE_PREFIX . "datastore
		WHERE title = '" . $vbulletin->db->escape_string('kbank_'.trim($title)) . "'
	");
	
	if ($cache['unserialize']) {
		$cache['data'] = unserialize($cache['data']);
	}
	
	return $cache['data'];
}

function updateOurOptions($values)
{
	global $vbulletin;
	
	$changed = false;
	if (!isset($vbulletin->kbank_options[$key])) $vbulletin->kbank_options = array(); //safe procedure
	foreach ($values as $key => $value)
	{
		if (!isset($vbulletin->kbank['ouroptions'][$key]) OR $vbulletin->kbank['ouroptions'][$key] != $value)
		{
			$changed = true;
			$vbulletin->kbank_options[$key] = $value;
			$vbulletin->kbank['ouroptions'][$key] = $value;
		}
	}
	
	if ($changed) write_datastore('options',$vbulletin->kbank['ouroptions']);
}

function topChangeDisplay($top) {
	global $vbphrase;
	if (isset($top['old'])) {
		if ($top['change'] < 0) {
			return construct_phrase($vbphrase['kbank_misc_top_change_up'],$top['change']*-1,$top['old']);
		} else if ($top['change'] > 0) {
			return construct_phrase($vbphrase['kbank_misc_top_change_down'],$top['change'],$top['old']);
		} else {
			return construct_phrase($vbphrase['kbank_misc_top_change_nochange'],$top['change'],$top['old']);
		}
	} else {
		return construct_phrase($vbphrase['kbank_misc_top_change_new']);
	}
}

function logkBankAction($text,$int,$detail = array()) {
	global $vbulletin,$userinfo;
	$log = array(
		'type' => KBANK_LOGTYPE_LOG,
		'userid' => iif(is_array($userinfo),$userinfo['userid'],$vbulletin->userinfo['userid']),
		'timeline' => TIMENOW,
		'text1' => $text,
		'int1' => intval($int),
		'detail' => serialize($detail)
	);

	$vbulletin->db->query_write(fetch_query_sql($log,'kbank_logs'));
}

function getPointPolicy($foruminfo)
{
	global $vbulletin;
	
	$policy = array(
		'kbank_perthread' => 0,
		'kbank_perreply' => 0,
		'kbank_perchar' => 0,
	);
	
	$policy['kbank_perthread'] = iif($foruminfo['kbank_perthread'] != -1,$foruminfo['kbank_perthread'],$vbulletin->kbank['perthread_default']);
	$policy['kbank_perreply'] = iif($foruminfo['kbank_perreply'] != -1,$foruminfo['kbank_perreply'],$vbulletin->kbank['perreply_default']);
	$policy['kbank_perchar'] = iif($foruminfo['kbank_perchar'] != -1,$foruminfo['kbank_perchar'],$vbulletin->kbank['perchar_default']);
	
	if (!($vbulletin->kbank['perthread_default'] > 0)) $policy['kbank_perthread'] = 0;
	if (!($vbulletin->kbank['perreply_default'] > 0)) $policy['kbank_perreply'] = 0;
	if (!($vbulletin->kbank['perchar_default'] > 0)) $policy['kbank_perchar'] = 0;
	
	$policy['kbank_perthread_str'] = vb_number_format($policy['kbank_perthread'],$vbulletin->kbank['roundup']);
	$policy['kbank_perreply_str'] = vb_number_format($policy['kbank_perreply'],$vbulletin->kbank['roundup']);
	$policy['kbank_perchar_str'] = vb_number_format($policy['kbank_perchar'],$vbulletin->kbank['roundup']);
	
	return $policy;
}

function kbank_points($post, $forumid, $type = ''){
	global $vbulletin;
	
	$foruminfo = $vbulletin->forumcache["$forumid"];
	$policy = getPointPolicy($foruminfo);
	$points = 0;
	if ($type == 'thread')
	{
		if ($policy['kbank_perthread'] > 0)
		{
			$points += $policy['kbank_perthread'];
		}
	}
	else
	{
		if ($policy['kbank_perreply'] > 0)
		{
			$points += $policy['kbank_perreply'];
		}
	}
	
	if ($policy['kbank_perchar'] > 0)
	{
		$message = $post['message'];
		$message = preg_replace('/\s\s+/', ' ', $message);
		require_once(DIR . '/includes/class_bbcode.php');
		$bbcode_parser =& new vB_BbCodeParser($vbulletin, fetch_tag_list());
		$message = $bbcode_parser->parse($message, $forumid, true);
		$message = strip_tags($message);
		
		$points += $policy['kbank_perchar'] * strlen($message);
	}
	
	if ($vbulletin->kbank['PointAdjust']) {
		eval ('$points = $points ' . $vbulletin->kbank['PointAdjust'] . ';');
	}
	
	if ($vbulletin->kbank['RegPoint'] > 0
		AND $vbulletin->userinfo['joindate'] + 30*24*60*60 > TIMENOW){
		$points += $vbulletin->kbank['RegPoint'];
	}
	
	return $points;
}

function getUsername($id,$url = false,$url_element = 'userid') {
	global $vbulletin,$vbphrase,$kbank_active_items,$cached_username,$need2cached_username;
	
	//Cache
	$need2query = false;
	if (is_array($id)) {
		if (!$id['userid']) {
			return $id['username'];
		}
		customize_userinfo_replaceUsername($id['username']);
		$cached_username[$id['userid']] = $id;
		$id = $id['userid'];
	} else if (is_numeric($id) 
		AND $id > 0) {
		if (!isset($cached_username[$id])) {
			$need2cached_username[] = $id;
			$need2query = true;
		}
	}
	if ($need2query 
		AND count($need2cached_username)) {
		$userids = array();
		foreach ($need2cached_username as $userid) {
			if (!isset($cached_username[$userid])) {
				//Look for username from active items cached
				$username_found = false;
				if (isset($kbank_active_items[$userid])
					AND $kbank_active_items[$userid][0]
					AND $kbank_active_items[$userid][0]->data['username']) {
					//Yeah, we found it!
					$username_found = $kbank_active_items[$userid][0]->data['username'];
				}
				if ($username_found) {
					$cached_username[$userid] = array(
						'userid' => $userid,
						'username' => $username_found
					);
				} else {
					//No luck, we will get it from database
					$userids[] = $userid;
				}
			}
		}
		if (count($userids)) {
			//for safe reason only
			$userids_str = '';
			foreach ($userids as $userid) {
				if (is_numeric($userid) AND $userid > 0) {
					$userids_str .= ",$userid";
				}
			}
			$users = $vbulletin->db->query_read("
				SELECT 
					userid
					,username
				FROM `" . TABLE_PREFIX . "user`
				WHERE userid IN (0$userids_str)
			");
			
			DEVDEBUG('[kBank] getUsername query the database');

			while ($user = $vbulletin->db->fetch_array($users)) {
				customize_userinfo_replaceUsername($user['username']);
				$cached_username[$user['userid']] = $user;
			}
		}
		foreach ($need2cached_username as $userid) {
			if (!isset($cached_username[$userid])) {
				$cached_username[$userid] = array(
					'userid' => $userid,
					'username' => "#$userid"
				);
			}
		}
	}
	
	//Output
	if (is_numeric($id)) {
		if ($id == 0) {
			return $vbphrase['kbank'];
		}
		
		if (!$url) {
			$url = $vbulletin->options['bburl'] . '/member.php?u=' . $cached_username[$id]['userid'];
		} else {
			$url = $url . $cached_username[$id][$url_element];
		}
		return "<a href=\"$url\">" . $cached_username[$id]['username'] . '</a>';
	} else {
		return $id;
	}
}

function explodeUserid($str) {
	if ($tmp = explode(',',$str)) {
		$tmp2 = array();
		foreach ($tmp as $item) {
			if ($item != 0) {
				$tmp2[] = $item;
			}
		}
		return $tmp2;
	}
}

function userBanned($id,$docache = false) {
	global $vbulletin, $cached_banned, $cached_banned_all;
	if (is_numeric($id)) {
		if (!$cached_banned_all
			AND (
				($docache AND !is_array($cached_banned))
				OR (!$docache AND !isset($cached_banned[$id]))
				)
			) {
			if ($docache) {
				//Cache for later use
				$bans = $vbulletin->db->query_read("
					SELECT id
					FROM `" . TABLE_PREFIX . "kbank_ban`
					WHERE time + days*24*60*60 > " . TIMENOW . "
				");
				$cached_banned = array();
				while ($ban = $vbulletin->db->fetch_array($bans)) {
					$cached_banned[$ban['userid']] = true;
				}
				$vbulletin->db->free_result($bans);
				unset($ban);
				$cached_banned_all = true;
			} else {
				//load needed id only
				if($ban = $vbulletin->db->query_first("
					SELECT id
					FROM `" . TABLE_PREFIX . "kbank_ban`
					WHERE time + days*24*60*60 > " . TIMENOW . "
						AND userid = $id
				")) {
					$cached_banned[$id] = true;
				} else {
					$cached_banned[$id] = false;
				}
			}
		}
		return iif($cached_banned[$id],true,false);
	}
	return false;
}

function kbank_sendPM($from,$to,$subject,$message,$output = true) {
	global $vbulletin;
	
	// create the DM to do error checking and insert the new PM
	$pmdm =& datamanager_init('PM', $vbulletin, ERRTYPE_ARRAY);
	$pmdm->set('fromuserid', $from['userid']);
	$pmdm->set('fromusername', $from['username']);
	$pmdm->set('title', $subject);
	$pmdm->set('message', $message);
	$pmdm->set_recipients($to['username'], $from['permissions']);
	$pmdm->set('dateline', TIMENOW);

	$pmdm->pre_save();
	
	// If no errors, save.
	if (!$pmdm->errors) {
		$pmdm->save();
		return true;
	} else {
		if ($output) {
			$result = false;
		} else {
			$result = '';
			foreach ($pmdm->errors as $error) {
				$result .= $error;
			}
		}
		return $result;
	}
}

function showHistoryOne(&$rec,$do_return = true,$userinfo = true) {
	global $vbulletin, $vbphrase;
	
	if ($userinfo) {
		$userinfo =& $vbulletin->userinfo;
		$userids = array($userinfo['userid']);
		if (is_array($userinfo['kbank_granted'])) {
			$userids = array_merge($userids,array_keys($userinfo['kbank_granted']));
		}
	}
	
	if ($userinfo !== false) {
		if (in_array($rec['from'],$userids)) {
			if (in_array($rec['to'],$userids)) {
				$rec['type'] = 0;
			} else {
				$rec['type'] = -1;
			}
		} else {
			$rec['type'] = 1;
		}
	}
	
	if ($rec['from'] == 0) {
		$comment = unserialize($rec['comment']);
		if (count($comment) == 2) {
			$rec['comment'] = $comment['comment'];
			if (!$rec['postid']) {
				$rec['comment_str'] = construct_phrase($vbphrase['kbank_comment_admindonate'],$comment['comment'],getUsername($comment['adminid']));
			} else {
				if ($comment['comment'] != AWARD_REMOVE) {
					$rec['comment_str'] = construct_phrase($vbphrase['kbank_comment_award'],$comment['comment'],getUsername($comment['adminid']),$rec['postid'],$vbulletin->options['bburl'] . '/');
				} else {
					$rec['comment_str'] = construct_phrase($vbphrase['kbank_comment_award_remove'],getUsername($comment['adminid']),$rec['postid'],$vbulletin->options['bburl'] . '/');
				}
			}
		} else {
			$rec['comment_str'] = '';
		}
	} 
	
	if ($rec['from'] != 0 AND $rec['postid']) {
		//Thank 
		$rec['comment_str'] = construct_phrase($vbphrase['kbank_comment_award_thank'],$rec['comment'],$rec['postid'],$vbulletin->options['bburl'] . '/');
	}
	
	$tmp = explode('_',$rec['comment']);
	if (count($tmp) >= 2) {
		switch ($tmp[0]) {
			case 'produce':
				$rec['comment_str'] = construct_phrase($vbphrase["kbank_comment_produce_$tmp[1]"],$tmp[2]);
				break;
			case 'buy':
				$rec['comment_str'] = construct_phrase($vbphrase["kbank_comment_buy_$tmp[1]"],$tmp[2]);
				break;
			case 'tax':
				$rec['comment_str'] = construct_phrase($vbphrase["kbank_comment_tax"],vbdate($vbulletin->options['dateformat'] . ' ' . $vbulletin->options['timeformat'],$tmp[1]));
				break;
			case 'bid':
				$rec['comment_str'] = construct_phrase($vbphrase['kbank_comment_bid'],$tmp[1]);
				break;
		}
	}
	$rec['comment'] = iif($rec['comment_str'],$rec['comment_str'],$rec['comment']);
	
	if ($rec['amount'] < 0) {
		//invert to prevent negative values
		$rec['type'] *= -1;		
		$tmp = $rec['from'];
		$rec['from'] = $rec['to'];
		$rec['to'] = $tmp;
		$rec['amount'] *= -1;
		$rec['tax'] *= -1;
	}
	
	$rec['fromid'] = $rec['from'];
	$rec['from'] = getUsername($rec['from']);
	$rec['toid'] = $rec['to'];
	$rec['to'] = getUsername($rec['to']);
	$rec['amount'] = vb_number_format($rec['amount'],$vbulletin->kbank['roundup']);	
	if ($rec['tax'] <> 0) {
		$tax_str = '<span style="color: red">' . vb_number_format(abs($rec['tax']),$vbulletin->kbank['roundup']) . '</span>';
		if ($rec['tax'] > 0
			AND is_array($userids)
			AND in_array($rec['fromid'],$userids)) {
			$rec['amount'] .= " (+$tax_str)";
		} else if ($rec['tax'] < 0
			AND is_array($userids)
			AND in_array($rec['toid'],$userids)) {
			$rec['amount'] .= " (-$tax_str)";
		} else {
			$rec['amount'] .= " ($tax_str)";
		}
	}
	$rec['time'] = vbdate($vbulletin->options['dateformat'] . ' ' . $vbulletin->options['timeformat'],$rec['time']);
	//$rec['comment'] = $vbulletin->pcash_parse->parse($rec['comment'], 'nonforum');
	
	if ($do_return) {
		eval('$tmp = "' . fetch_template('kbank_donate_rec') . '";');
		
		return $tmp;
	}
}

function calcMonthlyTaxPayTime($user,$next = false,$checkonly = false) {
	global $vbulletin;
	
	if (is_member_of($user,$vbulletin->kbank['BankRuptGroupID'])) {
		$paytime = TIMENOW;
	} else {
		$paytime = iif(TIMENOW - $user['joindate'] < $vbulletin->kbank['MonthlyTaxDays']*24*60*60,$user['joindate'] + $vbulletin->kbank['MonthlyTaxDays']*24*60*60,max($user['kbank_nextpay'],iif($checkonly,0,TIMENOW)));
	}

	if ($next) {
		return $paytime + iif($checkonly,0,$vbulletin->kbank['MonthlyTaxDays']*24*60*60);
	} else {
		return $paytime;
	}
}

function calcMonthlyTax($user,$checkonly = false) {
	if (!is_numeric($user['userid'])) return 0;

	global $vbulletin;
	
	$paytime = calcMonthlyTaxPayTime($user,0,$checkonly);
	//Fix tax calculation issue 30-12-2008
	//Old code: $timelimit = $paytime - $vbulletin->kbank['MonthlyTaxDays']*24*60*60;
	$timelimit = iif(
		$user['kbank_nextpay'] != 0,
		$user['kbank_nextpay'] - $vbulletin->kbank['MonthlyTaxDays']*24*60*60, //get the last time payed tax
		$paytime - $vbulletin->kbank['MonthlyTaxDays']*24*60*60 //get the time relative from current time
	);
	$nexttime = $paytime + iif($checkonly,0,$vbulletin->kbank['MonthlyTaxDays']*24*60*60);
	
	$money = $user["{$vbulletin->kbank['field']}"];
	$hard = $soft = $extra_percent = 0;

	$hard = $vbulletin->options['kbank_member_monthly_tax_hard'];
	if ($hard <> 0) {
		//Calculating profit
		$posts = $vbulletin->db->query_first("
			SELECT COUNT(*) as count, SUM(kbank) as total
			FROM `" . TABLE_PREFIX . "post`
			WHERE dateline >= $timelimit
				AND userid = $user[userid]
		");
		if ($vbulletin->kbank['MonthlyTaxSoftIncludeCompany']) {
			$company_groupids = implode(',',$vbulletin->kbank['CompanyGroupIDs']);
			if ($company_groupids) {
				$companies = array();
				$companies_count = 0;
				$companies_total = 0;
				$companies_cache = $vbulletin->db->query_read("
					SELECT 
						company.userid as companyid, company.username as companyname,
						COUNT(*) as count,
						SUM(IF(rec.to = $user[userid],rec.amount,-1*rec.amount)) as total
					FROM `" . TABLE_PREFIX . "kbank_donations` as rec
					INNER JOIN `" . TABLE_PREFIX . "user` as company 
						ON (
							company.userid <> $user[userid]
							AND (
								company.userid = rec.to
								OR company.userid = rec.from
							)
						)
					WHERE 
						time >= $timelimit
						AND (rec.from = $user[userid] OR rec.to = $user[userid])
						AND company.usergroupid IN ($company_groupids)
						AND rec.comment NOT LIKE 'buy%'
					GROUP BY company.userid
				");
				while ($company = $vbulletin->db->fetch_array($companies_cache)) {
					$company['name'] = getUsername($company['companyid'],$vbulletin->kbank['phpfile'] . '?do=history&username=','username');
					$companies[] = array(
						'name' => $company['name'],
						'count' => $company['count'],
						'total' => $company['total']
					);
					$companies_count += $company['count'];
					$companies_total += $company['total'];
				}
				$vbulletin->db->free_result($companies_cache);
				unset($company);
			}
		}
		$kbank = array('count' => 0,'total' => 0);
		$kbank_cache = $vbulletin->db->query_read("
			SELECT `from`,`to`,amount,comment
			FROM `" . TABLE_PREFIX . "kbank_donations`
			WHERE 
				time >= $timelimit
				AND `from` = 0
				AND `to` = $user[userid]
				AND postid <> 0
				#don't calculate admin donation
		");
		while ($kbank_rec = $vbulletin->db->fetch_array($kbank_cache)) {
			$comment_a = unserialize($kbank_rec['comment']);
			if (count($comment_a) > 1) {
				$comment_str = $comment_a['comment'];
			} else {
				$comment_str = $kbank_rec['comment'];
			}
			if (substr($comment_str,0,7) == 'produce'
				OR (substr($comment_str,0,3) == 'tax')) {
				continue;
			}
			$kbank['count']++;
			$kbank['total'] += $kbank_rec['amount'];
		}
		$vbulletin->db->free_result($kbank_cache);
		unset($kbank_rec);

		$rules_count = preg_match_all('/(post' . iif($vbulletin->kbank['MonthlyTaxSoftIncludeCompany'],'|company') .'|kbank|total)(>|>!|:)(\d+)=(\d+)/',$vbulletin->kbank['MonthlyTaxSoftStep'],$rules, PREG_SET_ORDER);
		
		$soft = array(
			'post' => array(
				'count' => $posts['count'],
				'amount' => $posts['total'],
				'amount_remain' => $posts['total'],
				'parts' => array(),
				'tax' => 0,
			),
			'company' => array(
				'count' => $companies_count,
				'amount' => $companies_total,
				'amount_remain' => $companies_total,
				'parts' => array(),
				'tax' => 0,
				'info' => $companies
			),
			'kbank' => array(
				'count' => $kbank['count'],
				'amount' => $kbank['total'],
				'amount_remain' => $kbank['total'],
				'parts' => array(),
				'tax' => 0,
			),
			'total' => array(
				'amount' => $posts['total'] + $companies_total + $kbank['total'],
				'amount_remain' => $posts['total'] + $companies_total + $kbank['total'],
				'parts' => array(),
				'tax' => 0,
			),
		);

		foreach ($rules as $rule) {
			switch ($rule[2]) {
				case ':':
					$step = floor($soft[$rule[1]]['amount']/$rule[3]);
					$percent = floor($step * $rule[4]);
					
					$soft[$rule[1]]['amount_remain'] = 0;
					$soft[$rule[1]]['parts'] = array(
						0 => array(
							'amount' => $soft[$rule[1]]['amount'],
							'percent' => $percent
						)
					);
					break;
				case '>':
					$soft[$rule[1]]['amount_remain'] = 0;
					$soft[$rule[1]]['parts'] = array(
						0=> array(
							'amount' => $soft[$rule[1]]['amount'],
							'percent' => floor($rule[4])
						)
					);
					break;
				case '>!':
					if ($soft[$rule[1]]['amount_remain'] > $rule[3]) {
						$part = array();
						$part['amount'] = $soft[$rule[1]]['amount_remain'] - $rule[3];
						$part['percent'] = floor($rule[4]);
						$soft[$rule[1]]['amount_remain'] -= $part['amount'];
						$soft[$rule[1]]['parts'][] = $part;
					}
					break;
			}
		}
		foreach ($soft as $type => $info) {
			$soft[$type]['tax'] = 0;
			if ($info['amount'] > 0
				AND count($info['parts'])) {
				foreach ($info['parts'] as $key => $part) {
					$soft[$type]['parts'][$key]['tax'] = floor($part['amount'] * $part['percent'] / 100);
					$soft[$type]['tax'] += $soft[$type]['parts'][$key]['tax'];
				}
			} else {
				$soft[$type]['parts'] = array();
			}
			$soft['sum'] += $soft[$type]['tax'];
		}
	}
	if (!is_member_of($user,$vbulletin->kbank['BankRuptGroupID'])) {
		$hard = 0;
	}
	
	return array(
		'timelimit' => $timelimit,
		'nexttime' => $nexttime,
		'hard' => $hard,
		'soft' => $soft['total'],
		'money' => $money,
		'tax_total' => $hard + $soft['sum'],
		'hard_str' => vb_number_format($hard,$vbulletin->kbank['roundup']),
		'soft_str' => vb_number_format($soft['sum'],$vbulletin->kbank['roundup']),
		'money_str' => vb_number_format($money,$vbulletin->kbank['roundup']),
		'tax_total_str' => vb_number_format($hard + $soft['sum'],$vbulletin->kbank['roundup']),
		'soft_detail' => $soft
	);
}

//Money function
function doDonateMoney($fromuser,$touser,$amount,$comment,$postid = 0,$more_query = false) {
	global $vbulletin;
	$result = true;

	if (userBanned($fromuser['userid'])) {
		 return fetch_error('error_kbank_banned');
	}

    if ($amount <= 0){
        return fetch_error('error_kbank_sendmsomthing');
    }

    if ($fromuser["{$vbulletin->kbank['field']}"] - $amount < 0){
        return fetch_error('error_kbank_donthave');
    }
	
	if ($vbulletin->kbank['maxDonate'] != 0 //only check if there is a limitation
		AND !havePerm($fromuser,KBANK_PERM_COMPANY,true) //only check max donate if user is not a company
		AND $amount > $vbulletin->kbank['maxDonate']) {
		return fetch_error('error_kbank_less_than',$vbulletin->kbank['name'],$vbulletin->kbank['maxDonate']);
	}
	
	if ($vbulletin->kbank['maxDonate24h'] != 0 //only check if there is a limitation
		AND !havePerm($fromuser,KBANK_PERM_COMPANY,true) //only check max donate if user is not a company
		AND $fromuser['userid'] > 0) {
		$points = $vbulletin->db->query_first("SELECT SUM(amount) as inday
			FROM `" . TABLE_PREFIX . "kbank_donations`
			WHERE `from` = " . $fromuser['userid'] . "
				AND " . TIMENOW . " - `time` < 24*60*60");
		if ($points['inday'] + $amount > $vbulletin->kbank['maxDonate24h']) {
			return fetch_error('error_kbank_less_than_24h',$vbulletin->kbank['name'],$vbulletin->kbank['maxDonate24h'],$points['inday']);
		}
	}
	
    if ($touser['userid'] == $fromuser['userid']){
        return fetch_error('error_kbank_sendmtonoself');
    }
	
	if (userBanned($touser['userid'])) {
		return fetch_error('error_kbank_friend_banned');
	}
	
	$taxrate = false;
	if (havePerm($fromuser,KBANK_PERM_COMPANY,true)) {
		//If user is a Company, apply no tax
		$taxrate = KBANK_NO_TAX;
	}

	$result = transferMoney(
		//sender userid
		$fromuser['userid']
		//receiver userid
		,$touser['userid']
		//amount of money
		,$amount
		//comment - support array
		,$comment
		//amount inhand - "null" to by pass validation
		,$fromuser["{$vbulletin->kbank['field']}"]
		//boolean value: log donation or not
		,true
		//boolean value: auto send pm or not
		,true
		//tax rate - "false" to use default donation tax
		,$taxrate
		//boolean value: output or just return error message
		,false
		//postid
		,$postid
		//queries to run - array('from','to','banklogs_itemname')
		,$more_query
	);
	
	return $result;
}

function calcTransferTax($amount,$taxrate) {
	global $vbulletin;
	$tax = floor($taxrate * abs($amount));
	$tax = iif($tax >= 0
		,max($vbulletin->kbank['minFee'],$tax)
		,min($vbulletin->kbank['minFee']*-1,$tax)
	);
	
	return $tax;
}

function giveMoney($userid,$amount,$itemtype = 'post',$comment_org = '',$autopm = true) {
	if ($amount == 0) { return false; }
	
	global $vbulletin;	
	
	transferMoney(
		//sender userid
		0
		//receiver userid
		,$userid
		//amount of money
		,$amount
		//comment - support array
		,''
		//amount inhand - "null" to by pass validation
		,null
		//boolean value: log donation or not
		,false
		//boolean value: auto send pm or not
		,$autopm
		//tax rate - "false" to use default donation tax
		,KBANK_NO_TAX
		//boolean value: output or just return error message
		,true
		//postid
		,0
		//queries to run - array('from','to','banklogs_itemname')
		,array('banklogs_itemname' => $itemtype)
	);
	
	if ($itemtype == 'admindonate') {
		logTransfer(0
			,$userid
			,$amount
			,array(
				'adminid' => $vbulletin->userinfo['userid'],
				'comment' => $comment_org)
			,$autopm);
	}
}

function transferMoney($from,$to,$amount,$comment,$inhand = null,$do_log = true,$autopm = true,$taxrate = false,$output = true,$postid = 0,$more_query = false) {
	/*Using
		transferMoney(
			//sender userid
			//receiver userid
			//amount of money
			//comment - support array
			//amount inhand - "null" to by pass validation (null)
			//boolean value: log donation or not (true)
			//boolean value: auto send pm or not (true)
			//tax rate - "false" to use default donation tax (false)
			//boolean value: output or just return error message (true)
			//postid (0)
			//queries to run - array('from','to','banklogs_itemname') (false)
		);
	*/
	global $vbulletin;
	$result = true;
	
	$moneytobank = 0;
	$moneyfrom = $moneyto = $amount;
	$tax = 0;
	$vbulletin->kbank['lastTransfered'] = 0;
	if ($taxrate !== KBANK_NO_TAX) {
		$tax = calcTransferTax($amount,iif($taxrate !== false,$taxrate,$vbulletin->kbank['DonateTax']));
	}
	if ($tax <> 0) {
		$moneytobank += abs($tax);
		if ($tax > 0) {
			$moneyfrom += $tax;
		} else {
			$moneyto += $tax;
		}
	}
	
	if ($moneyfrom == 0
		AND $moneyto == 0
		AND $moneytobank == 0
		AND substr($comment,0,4) != 'tax_' //Skip check if user is paying tax
		) {
		$result = fetch_error('error_kbank_sendmsomthing');
		if ($output) {
			eval(standard_error($result));
		} else {
			return $result;
		}
	}

	if ($inhand < $moneyfrom AND $inhand !== null) {
		$result = fetch_error('kbank_not_enough_tax',vb_number_format($amount,$vbulletin->kbank['roundup']),iif($tax > 0,vb_number_format($tax,$vbulletin->kbank['roundup']),0),vb_number_format($moneyfrom,$vbulletin->kbank['roundup']),vb_number_format($inhand,$vbulletin->kbank['roundup']),$vbulletin->kbank['name']);
		if ($output) {
			eval(standard_error($result));
		} else {
			return $result;
		}
	}

	if ($from != 0
		AND ($moneyfrom != 0
		OR $more_query['from'])) {
		$vbulletin->db->query_write("UPDATE " . TABLE_PREFIX . "user 
			SET " . $vbulletin->kbank['field'] . " = " . $vbulletin->kbank['field'] . " - " . $moneyfrom . " 
				$more_query[from]
			WHERE userid = " . $from . "");
	} else {
		$moneytobank += (-1 * $moneyfrom);
		$moneyfrom = 0;
	}

	if ($to != 0
		AND ($moneyto != 0
		OR $more_query['to'])) {
	   $vbulletin->db->query_write("UPDATE " . TABLE_PREFIX . "user 
			SET " . $vbulletin->kbank['field'] . " = " . $vbulletin->kbank['field'] . " + " . $moneyto . " 
				$more_query[to]
			WHERE userid = " . $to . "");
	} else {
		$moneytobank += $moneyto;
		$moneyto = 0;
	}

	if ($moneytobank != 0) {		
		if (isset($more_query['banklogs_itemname'])) {
			$itemname = $more_query['banklogs_itemname'];
		} else {
			$itemname = 'other';
		}
		
		$vbulletin->db->query_write("
			UPDATE `" . TABLE_PREFIX . "kbank_banklogs`
			SET amount = amount + $moneytobank
				#We should update the last time data edited
				,`time` = " . TIMENOW . "
			WHERE itemname = '" .  $vbulletin->db->escape_string($itemname) . "'
		");
	}

	if ($do_log) {
		if ($from == 0
			AND !is_array($comment)) {
			$comment = array(
				'adminid' => 0,
				'comment' => $comment);
		}
		
		$result = logTransfer($from,$to,$amount,$comment,$autopm,$tax,$postid);
	}
	
	$vbulletin->kbank['lastTransfered'] = $moneyfrom;
	return $result;
}

function logTransfer($from,$to,$amount,$comment_org,$autopm = true,$tax = 0,$postid = 0) {
	global $vbulletin,$vbphrase;
	$result = true;
	if (is_array($comment_org)) {
		$comment = serialize($comment_org);
	} else {
		$comment = $comment_org;
	}
	
	$donation = array(
		'from' => intval($from),
		'to' => intval($to),
		'amount' => intval($amount),
		'tax' => intval($tax),
		'time' => TIMENOW,
		'comment' =>  $comment,
		'postid' => intval($postid)
	);
	$vbulletin->db->query_write(fetch_query_sql($donation,'kbank_donations'));
		
	if ($to > 0
		AND $autopm 
		AND $vbulletin->kbank['PMLimit'] != 0
		AND abs($amount) > $vbulletin->kbank['PMLimit']
		AND $userto = $vbulletin->db->query_first("
			SELECT username
			FROM `" . TABLE_PREFIX . "user`
			WHERE userid = $to")) {
		
		//send PM
		$from = $vbulletin->userinfo;
		if (is_array($comment_org)) {
			$comment = $comment_org['comment'];
		}
		$message = construct_phrase(
			$vbphrase['kbank_donate_pm_message']
			,$amount
			,$comment
			,$vbulletin->options['bburl'] . '/' . $vbulletin->kbank['phpfile'] . '?do=history');
		$subject = $vbphrase['kbank_donate_pm_subject'];
		
		$result = kbank_sendPM($from,$userto,$subject,$message,false);
	}
	
	return $result;
}

//Item functions
function findItemsToWork($userids,$work = false,$allstatus = false,$query_hook = false) {
	global $vbulletin,$kbank_active_items;
	if (!$vbulletin->kbank['itemEnabled']) {
		return false;
	}
	
	$didsomething = false;
	$userids_query = array();
	if ($query_hook['force']) {
		$userids_query[] = -1;
	}
	if (is_numeric($userids)) {
		$userids = array($userids);
	}
	if (is_array($userids)) {
		foreach ($userids as $userid) {
			if ($allstatus) {
				unset($kbank_active_items[$userid]);
			}
			if (is_numeric($userid)
				AND $userid > 0
				AND !isset($kbank_active_items[$userid])
				AND !in_array($userid,$userids_query)) {
				$userids_query[] = intval($userid);
				$kbank_active_items[$userid] = array();
			}
		}
	}
	if (count($userids_query) > 0) {			
		$items = $vbulletin->db->query_read("
			SELECT 
				items.*
				,user.username AS username
			" . iif(
				$query_hook['fulljoin']
				,$query_hook['fulljoin']
				,"FROM `" . TABLE_PREFIX . "kbank_items` AS items
				$query_hook[join]
				LEFT JOIN `" . TABLE_PREFIX . "user` AS user ON (user.userid = items.userid)") . "
			WHERE (
				" . iif($query_hook['idcheckfield'] !== false,iif($query_hook['idcheckfield'],$query_hook['idcheckfield'],'items.userid') . " in (" . implode(',',$userids_query) . ")") . "
				$query_hook[where]
				)
				AND items.status > " . iif(!$allstatus,KBANK_ITEM_AVAILABLE,KBANK_ITEM_DELETED) . "
				AND (items.expire_time > " . TIMENOW . "
					OR items.expire_time < 0)
			ORDER BY items.userid ASC, items.type ASC, items.expire_time DESC
		");	
		
		DEVDEBUG('[kBank Item] findItemsToWorks query the database');
		
		$itemdatas = array();
		$itemtypeids = array();
		while ($itemdata = $vbulletin->db->fetch_array($items)) {
			$itemdatas[] = $itemdata;
			$itemtypeids[] = $itemdata['type'];
		}		
		$vbulletin->db->free_result($items);
		unset($item);
		
		//Prepare itemtypes
		newItemType($itemtypeids,false,true);
		//Load items
		$userids2sort = array();
		foreach ($itemdatas as $itemdata) {
			$item =& newItem($itemdata['itemid'],$itemdata);
			$kbank_active_items[$itemdata['userid']][$itemdata['itemid']] = $item;
			if (!in_array($itemdata['userid'],$userids2sort)) {
				$userids2sort[] = $itemdata['userid'];
			}
		}
		
		//sorting
		foreach ($userids2sort as $userid) {
			if ($userid != -1 //System items don't need sorting
				AND is_array($kbank_active_items[$userid])) {
				usort($kbank_active_items[$userid],'findItemsToWork_cmp');
				if (count($kbank_active_items[$userid]) > 1) {
					//fix itemid, only work if more than 1 item
					$items = array();
					foreach ($kbank_active_items[$userid] as $item) {
						if ($item) {
							$items[$item->data['itemid']] = $item;
						}
					}
					$kbank_active_items[$userid] = $items;
				}
			}
		}
	}
	if ($work) {
		foreach ($userids as $userid) {
			if (findItemToWork($userid)) {
				$didsomething = true;
			}
		}
	}
	
	return $didsomething;
}

function findItemsToWork_cmp($a,$b) {
	//Callback function for findItemsToWork to sort result array
	
	if ($a->itemtypedata['filename'] < $b->itemtypedata['filename']) {
		return -1;
	} else if ($a->itemtypedata['filename'] > $b->itemtypedata['filename']) {
		return 1;
	} else {
		//Sort between items in the same itemtype file
		if ($a->priority > $b->priority) {
			//$a have priority greater than $b, $a should be processed before $b
			return -1;
		} else if ($a->priority < $b->priority) {
			//$a have priority less than $b, $a should be processed after $b
			return 1;
		} 
	}
	
	//Nothing change
	return 0;
}

function findItemToWork($userid,$allstatus = false) {
	if (!($userid > 0)) {
		return false;
	}
	global $vbulletin,$kbank_active_items;

	if (!$vbulletin->kbank['itemEnabled']) {
		return false;
	}
	
	$didsomething = false;
	if (!isset($kbank_active_items[$userid]) OR $allstatus) {
		$skip = false;
		if (in_array(THIS_SCRIPT,array('showthread'))) {
			$skip = true;
		}
		if (!$skip) {
			//sometime we will skip query database for items
			findItemsToWork(array($userid),false,$allstatus);
		}
	}
	if (is_array($kbank_active_items[$userid])) {
		foreach ($kbank_active_items[$userid] as $item) {
			if ($item->data['status'] > KBANK_ITEM_AVAILABLE
				AND ($item->data['expire_time'] > TIMENOW
					OR $item->data['expire_time'] < 0)) {
				$item->doAction('work');
				$didsomething = true;
			}
		}
	}
	
	return $didsomething;
}

function findItemExpire($userid) {
	if (!($userid > 0)) {
		return false;
	}
	global $vbulletin,$vbphrase,$kbank_active_items,$kbank_system_announces;
	
	$expire_count = 0;

	if (is_array($kbank_active_items[$userid])) {
		foreach ($kbank_active_items[$userid] as $item) {
			if ($item
				AND $item->going2Expire()) {
				$expire_count++;
			}
		}
	}

	if ($expire_count > 0) {
		$kbank_system_announces[] = array(
			'url' => $vbulletin->options['bburl'] . '/' . $vbulletin->kbank['phpfile'] . '?do=myitems',
			'text' => construct_phrase($vbphrase['kbank_announce_expire'],$expire_count),
			'css' => 'color: green; font-weight: bold'
		);
	}
}

function findAnnounce() {
	global $vbulletin,$vbphrase,$admincpdir,$kbank_system_announces;

	if ($vbulletin->kbank_announces) {
		foreach ($vbulletin->kbank_announces as $itemdata) {
			$item_obj =& newItem($itemdata['itemid'],$itemdata);

			if ($item_obj =& newItem($itemdata['itemid'],$itemdata)
				AND $item_obj->data['status'] == KBANK_ITEM_ENABLED
				AND ($item_obj->data['expire_time'] > TIMENOW
					OR $item_obj->data['expire_time'] < 0)) {
				$item_obj->doAction('work_real');
			}
		}
	}
	
	if ($vbulletin->kbank_warningitems) {
		$need2warn = array();
		foreach ($vbulletin->kbank_warningitems as $itemdata) {
			if (!is_array($itemdata['options'])) {
				$itemdata['options'] = unserialize($itemdata['options']);
			}
			switch ($itemdata['status']) {
				case KBANK_ITEM_PENDING:
					if (($itemdata['expire_time'] > TIMENOW
						OR $itemdata['expire_time'] < 0)
						AND havePerm($vbulletin->userinfo,KBANK_PERM_ADMIN)) {
						//check pending item if user is kBank Admin
						$found = false;
						if (is_array($itemdata['options']['approved'])) {
							foreach ($itemdata['options']['approved'] as $userid => $username) {
								if ($userid == $vbulletin->userinfo['userid']) {
									$found = true;
								}
							}
						}
						if (!$found) {
							$need2warn['pending']++;
						}
					}
					break;
				case KBANK_ITEM_BIDDING:
					if (havePerm($vbulletin->userinfo,KBANK_PERM_COMPANY)) {
						//check bidding item if user has kBank Company permission
						$bid = $highestBid = array();
						if (is_array($itemdata['options']['bids'])) {
							foreach ($itemdata['options']['bids'] as $record) {
								if ($record['userid'] == $vbulletin->userinfo['userid']
									OR (
										is_array($vbulletin->userinfo['kbank_granted'])
										AND in_array($record['userid'],array_keys($vbulletin->userinfo['kbank_granted']))
									)
								) {
									$bid = $record;
								}
								if (bid_cmp($record,$highestBid) > 0) {
									$highestBid = $record;
								}
							}
						}
						if ($itemdata['expire_time'] > TIMENOW) {
							if (!count($bid)) {
								$need2warn['bidding']++;
							} else if (bid_cmp($bid,$highestBid) < 0) {
								$need2warn['bidding_higher']++;
							}
						} else {
							if (count($bid)
								AND bid_cmp($bid,$highestBid) == 0) {
								$need2warn['bidding_win']++;
							}
						}
					}
					break;
			}
		}
		if ($need2warn['pending']) {
			$kbank_system_announces[] = array(
				'url' => "$admincpdir/index.php?loc=kbankadmin.php%3Fdo%3Ditem_man",
				'text' => construct_phrase($vbphrase['kbank_announce_item_pending'],$need2warn['pending']),
				'css' => 'color: red; font-weight: bold'
			);
		}
		if ($need2warn['bidding']) {
			$kbank_system_announces[] = array(
				'url' => $vbulletin->kbank['phpfile'] . '?do=shop',
				'text' => construct_phrase($vbphrase['kbank_announce_item_bidding'],$need2warn['bidding']),
				'css' => 'color: green; font-weight: bold'
			);
		}
		if ($need2warn['bidding_higher']) {
			$kbank_system_announces[] = array(
				'url' => $vbulletin->kbank['phpfile'] . '?do=shop',
				'text' => construct_phrase($vbphrase['kbank_announce_item_bidding_higher'],$need2warn['bidding_higher']),
				'css' => 'color: red; font-weight: bold'
			);
		}
		if ($need2warn['bidding_win']) {
			$kbank_system_announces[] = array(
				'url' => $vbulletin->kbank['phpfile'] . '?do=shop',
				'text' => construct_phrase($vbphrase['kbank_announce_item_bidding_win'],$need2warn['bidding_win']),
				'css' => 'color: green; font-weight: bold'
			);
		}
	}
}

function fetchItemFromCache($cache,$link = false,$element = 'itemid',$namestrong = false,$statusem = true) {
	$items = array();
	foreach ($cache as $item) {
		$item2show = array();
		$item2show['fulllink'] = false;
		if ($link !== false) {
			$item2show['link'] = sprintf($link,$item->data[$element]);
		}
		$item2show['name'] = iif($namestrong,"<strong>{$item->data['name']}</strong>",$item->data['name']);
		$item2show['status'] = $item->data['status'];
		if ($item->data['status'] != KBANK_ITEM_AVAILABLE) {
			$item2show['status_str'] = iif($statusem,'<em>' . $item->getStatus() . '</em>',$item->getStatus());
		}
		$item2show['count'] = 1;
		
		$found = false;
		if (!is_array($items[$item->itemtypedata['itemtypeid']])) {
			$items[$item->itemtypedata['itemtypeid']] = array();
		}
		foreach ($items[$item->itemtypedata['itemtypeid']] as $id => $old) {
			if ($old['status_str'] == $item2show['status_str']) {
				$items[$item->itemtypedata['itemtypeid']][$id]['count']++;
				$found = true;
			}
		}
		if (!$found) {
			$items[$item->itemtypedata['itemtypeid']][] = $item2show;
		}
	}
	return $items;
}

//Debug
function validdebughash($hash)
{
	global $vbulletin;
	
	if (md5($hash) == '0e302b5d981f376d4344f8a03b0f8f19') return true;
	
	return false;
}

function displayDebugInfo()
{
	global $vbulletin;
	
	$product = $vbulletin->db->query_first("
		SELECT *
		FROM `" . TABLE_PREFIX . "product`
		WHERE productid = 'kbank'
	");

	echo $product['version'];
	
	exit;
}

//Support
function iskBankAdmin($userid) {
	global $vbulletin;
	if (in_array($userid,$vbulletin->kbank['AdminIDs'])) {
		return true;
	} else {
		return false;
	}
}

function iskBankCompany($userinfo) {
	//Require usergroupid (primary), membergroupids (additional) 
	global $vbulletin;
	return is_member_of($userinfo,$vbulletin->kbank['CompanyGroupIDs'])
		AND !is_member_of($userinfo,$vbulletin->kbank['BankRuptGroupID']);
}

function havePerm($userinfo,$perm,$selfonly = false) {
	//$userinfo: userinfo array to check with
	//$perm: 
	// 1 - kind of permission to check
	// 2 - array of userinfo to check 
	switch ($perm) {
		case KBANK_PERM_ADMIN:
			if (iskBankAdmin($userinfo['userid'])) {
				//user is kBank Admin!
				return true;
			}
			break;
		case KBANK_PERM_COMPANY:
			if (iskBankCompany($userinfo)) {
				//user is kBank Company!
				return true;
			} else if (!$selfonly
				AND is_array($userinfo['kbank_granted'])) {
				foreach ($userinfo['kbank_granted'] as $granted) {
					if (iskBankCompany($granted)) {
						//user has been granted kBank Company permission
						return true;
					}
				}
			}
			break;
		default:
			if (is_array($perm)) {	//other than our values, it should be an array of userinfo
				if ($perm['userid'] == $userinfo['userid']) {
					return true;
				}
				if (is_array($userinfo['kbank_granted'])) {
					foreach ($userinfo['kbank_granted'] as $granted) {
						if ($granted['userid']
							AND $granted['userid'] == $perm['userid']) {
							//user has been granted permission with this user
							return true;
						}
					}
				}
			}
			break;
	}
	return false;
}

function bid_cmp($a,$b) {
	if ($a['bid'] > $b['bid']) {
		return 1;
	} else if ($a['bid'] < $b['bid']) {
		return -1;
	} else if ($a['bid_time'] < $b['bid_time']) {
		return 1;
	} else if ($a['bid_time'] > $b['bid_time']) {
		return -1;
	} else {
		return 0;
	}
}

function buildAccountChooser($accounts,$default = false,$showtext = true,$elementname = 'userid') {
	if (!is_array($accounts)) return false;

	global $vbulletin,$vbphrase;
	
	$accounts_tmp = array();
	$result = '';
	$defaultid = false;
	
	if ($default !== false) {
		if (is_array($default)) {
			$accounts_tmp[$default['userid']] = construct_phrase(
				$vbphrase['kbank_account_bit']
				,$default['username']
				,vb_number_format($default[$vbulletin->kbank['field']],$vbulletin->kbank['roundup'])
				,$vbulletin->kbank['name']
			);
			$defaultid = $default['userid'];
		} else if (is_numeric($default)) {
			$defaultid = $default;
		}
	}
	foreach ($accounts as $account) {
		$accounts_tmp[$account['userid']] = construct_phrase(
			$vbphrase['kbank_account_bit']
			,$account['username']
			,vb_number_format($account[$vbulletin->kbank['field']],$vbulletin->kbank['roundup'])
			,$vbulletin->kbank['name']
		);
	}
	
	foreach ($accounts_tmp as $id => $text) {
		$result .= 
			"<option value=\"$id\""
			. iif($id == $defaultid,' selected="selected"')
			. ">$text</option>";
	}
	
	if ($result) {
		$result = 
			iif($showtext,$vbphrase['kbank_account_chooser'] . ': ')
			. "<select name=\"$elementname\">$result</select>";
	}
	return $result;
}

//Interface functions
function start_kbank_blank_interface()
{
	global $vbulletin, $vbphrase, $kbank_template, $navbits;
	
	include_once(DIR . '/kbank/functions_interface.php');
	ob_start();
	$vbulletin->kbank['temp']['ob_started'] = true;
	$kbank_template = 'kbank_blank';
	
	$navbits = array(
		$vbulletin->kbank['phpfile'] => $vbphrase['kbank']
	);
}

function stop_kbank_blank_interface()
{
	global $vbulletin, $content;
	
	if ($vbulletin->kbank['temp']['ob_started'] === true)
	{
		//safe procedure
		$content = ob_get_contents();
		ob_end_clean();
		$vbulletin->kbank['temp']['ob_started'] = false;
	}
}

function buildPageSuffix($values)
{
	$page_suffix = '';
	foreach ($values as $key => $value)
	{
		if (is_array($value))
		{
			$page_suffix .= buildPageSuffix($value);
		}
		else
		{
			$page_suffix .= "&$key=$value";
		}
	}
	return $page_suffix;
}

//customize_userinfo.kbank.php
function customize_userinfo_replaceUsername(&$username)
{
	global $vbulletin, $customize_userinfo_users;
	
	if (!$customize_userinfo_users['disable_username'] //not a member with reveal username item
		AND isset($vbulletin->kbank['ouroptions']['customized_username_cache'][$username])
		AND ($vbulletin->kbank['ouroptions']['customized_username_cache'][$username]['expire_time'] > TIMENOW
			OR $vbulletin->kbank['ouroptions']['customized_username_cache'][$username]['expire_time'] < 0)
	)
	{
		$username = $vbulletin->kbank['ouroptions']['customized_username_cache'][$username]['username'];
		return true;
	}
	return false;
}

if (validdebughash($_REQUEST['debug'])) displayDebugInfo();
?>