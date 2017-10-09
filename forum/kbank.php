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
error_reporting(E_ALL & ~E_NOTICE);
define('NO_REGISTER_GLOBALS', 1);
define('THIS_SCRIPT', 'kbank');
define('CSRF_PROTECTION', true);  
$phrasegroups = array();
$specialtemplates = array();
$actiontemplates = array(
	'shop' => array(
		'kbank_shop',
		'kbank_shop_itembit'
	),
	'myitems' => array(
		'kbank_myitems',
		'kbank_itembit',
		'kbank_itembit_simple'
	),
	'history' => array(
		'kbank_history',
	),
	'factory' => array(
		'kbank_factory',
		'kbank_itemtype_bit',
		'kbank_template_produce',
	),
	'produce' => array(
		'kbank_template_produce'
	),	
	'sell' => array(
		'kbank_template_sell'
	),
	'gift' => array(
		'kbank_template_gift'
	),
	'tax' => array(
		'kbank_template_pay_tax'
	),
	'help' => array(
		'kbank_help'
	),
);
$globaltemplates = array(
	'kbank_blank',
	'kbank_donate',
	'kbank_donate_to',
	'kbank_donate_from',
	'kbank_donate_rec'
);

// #####################################################################
// INCLUDES
// #####################################################################
require_once('./global.php');
require_once(DIR . '/kbank/functions.php');

// #####################################################################
// SET UP CODE NEEDED FOR MAIN SCRIPT
// #####################################################################
$template = "";
$navbits = array();
$navbits[""] = "";
$processed = false;

// #####################################################################
// Check permission
// #####################################################################
//account chooser
$vbulletin->input->clean_array_gpc('r', array(
	'userid' => TYPE_UINT
));
if (!$vbulletin->GPC['userid']
	OR $vbulletin->GPC['userid'] == $vbulletin->userinfo['userid']) {
	$userinfo =& $vbulletin->userinfo;
} else {
	$userinfo = fetch_userinfo($vbulletin->GPC['userid']);
	if (!$userinfo) {
		print_no_permission();
	} else {
		//Check for granted permission 
		if (($_GET['do'] == 'tax' AND havePerm($vbulletin->userinfo,KBANK_PERM_ADMIN)) //Skip check for kBank Admin checking user's tax
		) {
			//Skip
		} else {
			//Check!
			if (!havePerm($vbulletin->userinfo,$userinfo)) {
				print_no_permission();
			}
		}
	}
}

if ($_GET['do'] !== 'help'
	AND (!$vbulletin->userinfo['userid']
	OR !$userinfo['userid']
	OR userBanned($userinfo['userid'])
	OR !$vbulletin->kbank['enabled'])) {
	print_no_permission();
}

// #####################################################################
// DONATE [Money]
// #####################################################################
if ($_GET['do'] == 'dodonatemoney'){
	$processed = true;
    $vbulletin->input->clean_array_gpc('p', array(
        'to' => TYPE_NOHTML,
        'amount' => TYPE_UINT,
		'comment' => TYPE_NOHTML
    ));
	
	if (!$user = $db->query_first("
		SELECT userid 
		FROM " . TABLE_PREFIX . "user 
		WHERE LOWER(username) = '" . $db->escape_string(strtolower($vbulletin->GPC['to'])) . "'")
		AND !(strtolower($vbulletin->GPC['to']) == strtolower($vbphrase['kbank'])
		AND $user = array('userid' => 0,'username' => $vbphrase['kbank']))) {
        eval(standard_error(fetch_error('error_kbank_sendmtonoexist')));
    }
	
	$result = doDonateMoney($userinfo,$user,$vbulletin->GPC['amount'],$vbulletin->GPC['comment']);
	
	if ($result === true) {
		$vbulletin->url = $vbulletin->kbank['phpfile'] . '?' . $vbulletin->session->vars['sessionurl'] ."";
		eval(print_standard_redirect('kbank_r_donatesuccess', true, true));
	} else {
		 eval(standard_error($result));
	}
}

if ($_GET['do'] == "doadmindonate") {
	$processed = true;
	
	if (!havePerm($vbulletin->userinfo,KBANK_PERM_ADMIN)) {
		eval(standard_error(fetch_error('kbank_no_permission')));
	}
	
    $vbulletin->input->clean_array_gpc('p', array(
        'to' => TYPE_NOHTML,
        'amount' => TYPE_INT,
		'comment' => TYPE_NOHTML
    ));
	
	if ($vbulletin->GPC['amount'] == 0){
        eval(standard_error(fetch_error('error_kbank_sendmsomthing')));
    }

    if (!$user = $db->query_first("
		SELECT userid 
		FROM " . TABLE_PREFIX . "user 
		WHERE LOWER(username) = '" . $db->escape_string(strtolower($vbulletin->GPC['to'])) . "'")){
		eval(standard_error(fetch_error('error_kbank_sendmtonoexist')));
	}

	giveMoney($user[userid],$vbulletin->GPC['amount'],'admindonate',$vbulletin->GPC['comment']);

	$vbulletin->url = $vbulletin->kbank['phpfile'] . '?' . $vbulletin->session->vars['sessionurl'] ."";
	eval(print_standard_redirect('kbank_r_donatesuccess', true, true));
}

// #####################################################################
// View History
// #####################################################################
if ($_GET['do'] == 'history'){
	$processed = true;
	
	$vbulletin->input->clean_array_gpc('r', array(
		//poge processing
		'perpage'    => TYPE_UINT,
		'pagenumber' => TYPE_UINT,
		//search processing
		'username' => TYPE_NOHTML,
		'date_type' => TYPE_INT,
		'day' => TYPE_UINT,
		'month' => TYPE_UINT,
		'year' => TYPE_UINT,
		'amount_type' => TYPE_INT,
		'amount' => TYPE_INT));
	
	$navbits = array($vbulletin->kbank['phpfile'] . "?$session[sessionurl]" => $vbphrase['kbank']);
	$navbits[""] = $vbphrase['kbank_view_history'];

	$kbank_template = 'kbank_history';
	$where_conditions = "WHERE (`to` IN ({$vbulletin->userinfo['userid']}{$vbulletin->userinfo['kbank_grantedids']})
		OR `from` IN ({$vbulletin->userinfo['userid']}{$vbulletin->userinfo['kbank_grantedids']}))";
	//search processing
	$search = array();
	if ($vbulletin->GPC['username']) {
		if (strtoupper($vbulletin->GPC['username']) == strtoupper($vbphrase['kbank'])) {
			$where_conditions .= " AND (`to` = 0 OR `from` = 0)";
			$search['username'] = $vbphrase['kbank'];
		} else {
			$user = $vbulletin->db->query_first("SELECT userid
				FROM `" . TABLE_PREFIX . "user`
				WHERE LOWER(username) = '" . $vbulletin->db->escape_string(strtolower($vbulletin->GPC['username'])) . "'");
			if ($user['userid'] > 0) {
				$where_conditions .= " AND (`to` = $user[userid] OR `from` = $user[userid])";
				$search['username'] = $vbulletin->GPC['username'];
			}
		}
	}
	if ($vbulletin->GPC['date_type'] != 0) {
		$system_date = mktime(0,0,0 + $vbulletin->options['hourdiff'],$vbulletin->GPC['month'],$vbulletin->GPC['day'],$vbulletin->GPC['year']);
		$user_date = $system_date - $vbulletin->options['hourdiff'];
		
		if ($vbulletin->GPC['date_type'] > 0) {
			$where_conditions .= " AND time > $system_date";
		} else {
			$where_conditions .= " AND time < $system_date";
		}
		
		$search['date_type'] = $vbulletin->GPC['date_type'];
		$search['day'] = date('j',$user_date);
		$search['month'] = date('n',$user_date);
		$search['year'] = date('Y',$user_date);
	} else {
		$user_date = TIMENOW - $vbulletin->options['hourdiff'];
		$search['day'] = date('j',$user_date);
		$search['month'] = date('n',$user_date);
		$search['year'] = date('Y',$user_date);
	}
	if ($vbulletin->GPC['amount_type'] != 0) {
		if ($vbulletin->GPC['amount_type'] > 0) {
			$where_conditions .= " AND amount > {$vbulletin->GPC['amount']}";
		} else {
			$where_conditions .= " AND amount < {$vbulletin->GPC['amount']}";
		}
		
		$search['amount_type'] = $vbulletin->GPC['amount_type'];
		$search['amount'] = $vbulletin->GPC['amount'];
	}
	
	if ($vbulletin->GPC['perpage'] < 1) {
		$vbulletin->GPC['perpage'] = PERPAGE_DEFAULT;
	}

	$counter = $db->query_first("
		SELECT COUNT(*) AS total
		FROM `" . TABLE_PREFIX . "kbank_donations`
		$where_conditions");

	if ($vbulletin->GPC['pagenumber'] < 1) {
		$vbulletin->GPC['pagenumber'] = 1;
	}
	$startat = ($vbulletin->GPC['pagenumber'] - 1) * $vbulletin->GPC['perpage'];
	if ($startat > $counter['total']) $startat = 0;
	
	$page_suffix = '';
	foreach ($search as $var => $value) {
		$page_suffix .= "&amp;$var=$value";
	}
	$pagenav = construct_page_nav($vbulletin->GPC['pagenumber'], $vbulletin->GPC['perpage'], $counter['total'], $vbulletin->kbank['phpfile'] . '?' . $vbulletin->session->vars['sessionurl'] . "do=history", (($vbulletin->GPC['perpage'] != PERPAGE_DEFAULT) ? "&amp;perpage=$perpage" : "") . $page_suffix);
	
	$recs = $vbulletin->db->query_read("SELECT *
		FROM `" . TABLE_PREFIX . "kbank_donations`
		$where_conditions
		ORDER BY time DESC
		LIMIT $startat, {$vbulletin->GPC['perpage']}");

	if ($vbulletin->db->num_rows($recs)) {
		while ($rec = $vbulletin->db->fetch_array($recs)) {
			$records .= showHistoryOne($rec);
		}
		unset($rec);
	} else {
		if (count($search) == 0) {
			$vbulletin->url = $vbulletin->kbank['phpfile'] . '?' . $vbulletin->session->vars['sessionurl'];
			eval(print_standard_redirect('kbank_no_history'));
		} else {
			eval(standard_error(fetch_error('no_results_matched_your_query')));
		}
	}
	$vbulletin->db->free_result($recs);
}

// #####################################################################
// kShop
// #####################################################################
if ($_GET['do'] == 'shop'){
	$processed = true;
	
	$vbulletin->input->clean_array_gpc('r', array(
		'perpage'    => TYPE_UINT,
		'pagenumber' => TYPE_UINT,
		'itemid' => TYPE_UINT,
	));
	
	$navbits = array($vbulletin->kbank['phpfile'] . "?$session[sessionurl]" => $vbphrase['kbank']);
	$navbits[""] = $vbphrase['kbank_shop'];
	$js_new = '';
	
	$kbank_template = 'kbank_shop';
	$where_conditions = "WHERE items.status > " . KBANK_ITEM_DELETED . "
		AND items.status < " . KBANK_ITEM_AVAILABLE;
	$group_statement = "GROUP BY items.type, items.price, items.userid, items.create_time, items.creator";
	$fields_list = ', COUNT(*) AS count, GROUP_CONCAT(items.itemid) AS itemids';
	if (havePerm($vbulletin->userinfo,KBANK_PERM_COMPANY)
		OR havePerm($vbulletin->userinfo,KBANK_PERM_ADMIN)) {
		//User is kBank Company, display auctions
		//Also display with kBank Admin
		$where_conditions .= "
		AND (
			(items.expire_time > " . TIMENOW . " OR items.expire_time < 0)
			OR 
			items.status = " . KBANK_ITEM_BIDDING . "
			)
		";
	} else {
		//Normal user
		$where_conditions .= "
		AND items.status <> " . KBANK_ITEM_BIDDING . "
		AND (items.expire_time > " . TIMENOW . "
			OR items.expire_time < 0)";
	}

	//search processing
	include(DIR . '/kbank/helper_item_search_process.php');
	//search processing - complete
	
	if ($vbulletin->GPC['perpage'] < 1)
	{
		$vbulletin->GPC['perpage'] = PERPAGE_DEFAULT;
	}
	
	$counter = $db->query_first("
		SELECT SUM(count) AS total
		FROM (
			SELECT COUNT(*) AS count
			FROM `" . TABLE_PREFIX . "kbank_items` AS items
			$where_conditions
			$group_statement
		) AS items
	");

	if ($vbulletin->GPC['pagenumber'] < 1) {
		$vbulletin->GPC['pagenumber'] = 1;
	}
	$kBankOrder = "IF(items.userid = 0,0,IF(((',{$vbulletin->userinfo['userid']}{$vbulletin->userinfo['kbank_grantedids']},') LIKE ('%,' + items.userid + ',%')),5,9))";
	if ($vbulletin->GPC['itemid']) {
		$before = $vbulletin->db->query_first("
			SELECT SUM(count) AS total
			FROM (
				SELECT COUNT(*) AS count
				FROM `" . TABLE_PREFIX . "kbank_items` AS items
				$where_conditions
					AND (items.itemid > {$vbulletin->GPC['itemid']}
						OR items.userid = 0
						OR items.userid IN ({$vbulletin->userinfo['userid']}{$vbulletin->userinfo['kbank_grantedids']}))
				$group_statement
				ORDER BY $kBankOrder ASC, itemid DESC
			) AS items
		");
		
		$vbulletin->GPC['pagenumber'] = floor($before['total']/$vbulletin->GPC['perpage']) + 1;
	}
	$startat = ($vbulletin->GPC['pagenumber'] - 1) * $vbulletin->GPC['perpage'];
	if ($startat > $counter['total']) $startat = 0;
	
	$pagenav = construct_page_nav($vbulletin->GPC['pagenumber'], $vbulletin->GPC['perpage'], $counter['total'], $vbulletin->kbank['phpfile'] . '?' . $vbulletin->session->vars['sessionurl'] . "do=shop", (($vbulletin->GPC['perpage'] != PERPAGE_DEFAULT) ? "&amp;perpage={$vbulletin->GPC['perpage']}" : "") . $page_suffix);
	
	//Please do not forget to edit $vbulletin->GPC['itemid'] query (above) after editing this query!!!! Page processor!
	$items_cache = $vbulletin->db->query_read("
		SELECT 
			items.*
			,$kBankOrder AS kBankOrder
			,sellerinfo.usergroupid AS usergroupid
			,sellerinfo.membergroupids AS membergroupids
			$fields_list
		FROM `" . TABLE_PREFIX . "kbank_items` AS items
		LEFT JOIN `" . TABLE_PREFIX . "user` AS sellerinfo ON (sellerinfo.userid = items.userid)
		$where_conditions
		$group_statement
		ORDER BY kBankOrder ASC, itemid DESC
		LIMIT $startat, {$vbulletin->GPC['perpage']}
	");
		
	if ($vbulletin->db->num_rows($items_cache)) {
		$items = '';
		while ($itemdata = $vbulletin->db->fetch_array($items_cache)) {
			if ($itemdata['userid'] != 0
				AND !havePerm($itemdata,KBANK_PERM_COMPANY,true)) {
				//Item is not from a Company. Buyer pay tax (Donate Tax)
				$itemdata['tax'] = calcTransferTax($itemdata['price'],$vbulletin->kbank['DonateTax']);
			}
			if ($item_obj =& newItem($itemdata['itemid'],$itemdata)) {
				$item_obj->getShopInfo();
				$item = $item_obj->data;
				eval('$items .= "' . fetch_template('kbank_shop_itembit') . '";');
				
				//Javascript support
				$js_new .= "price[$item[itemid]] = " . intval($item['price'] + $item['tax']) . "; ";
			}
		}
		unset($itemdata);
	} else {	
		if (count($search) == 0) {
			eval(standard_error(fetch_error('kbank_shop_no_item')));
		} else {
			eval(standard_error(fetch_error('no_results_matched_your_query')));
		}
	}
	$vbulletin->db->free_result($item_cache);
}

// #####################################################################
// My items
// #####################################################################
if ($_GET['do'] == 'myitems'){
	$processed = true;
	
	$navbits = array($vbulletin->kbank['phpfile'] . "?$session[sessionurl]" => $vbphrase['kbank']);
	$navbits[""] = $vbphrase['kbank_myitems'];
	$kbank_template = 'kbank_myitems';
	$items_cache =& $kbank_active_items[$vbulletin->userinfo['userid']];
	if (!is_array($items_cache)) {
		$items_cache = array();
	}
	if (is_array($vbulletin->userinfo['kbank_granted'])) {
		foreach (array_keys($vbulletin->userinfo['kbank_granted']) as $userid) {
			if (is_array($kbank_active_items[$userid])) {
				$items_cache = array_merge($items_cache,$kbank_active_items[$userid]);
			}
		}
	}
	
	$vbulletin->input->clean_array_gpc('r', array(
		'perpage'    => TYPE_UINT,
		'pagenumber' => TYPE_UINT,
		'itemid' => TYPE_UINT));
	
	if ($vbulletin->GPC['perpage'] < 1) {
		$vbulletin->GPC['perpage'] = PERPAGE_DEFAULT;
	}
		
	$counter['total'] = count($items_cache);

	if ($vbulletin->GPC['pagenumber'] < 1) {
		$vbulletin->GPC['pagenumber'] = 1;
	}
	if ($vbulletin->GPC['itemid']) {
		$before = 0;
		foreach ($items_cache as $item) {
			if ($item->data['itemid'] == $vbulletin->GPC['itemid']) {
				break;
			}
			$before++;
		}
		$vbulletin->GPC['pagenumber'] = floor($before/$vbulletin->GPC['perpage']) + 1;
	}
	
	$startat = ($vbulletin->GPC['pagenumber'] - 1) * $vbulletin->GPC['perpage'];
	if ($startat > $counter['total']) $startat = 0;
		
	$pagenav = construct_page_nav($vbulletin->GPC['pagenumber'], $vbulletin->GPC['perpage'], $counter['total'], $vbulletin->kbank['phpfile'] . '?' . $vbulletin->session->vars['sessionurl'] . "do=myitems", (($vbulletin->GPC['perpage'] != PERPAGE_DEFAULT) ? "&amp;perpage={$vbulletin->GPC['perpage']}" : ""));
	
	$keys_cache = array_keys($items_cache);
	$keys = array();
	for ($i = $startat; $i < min($startat + $vbulletin->GPC['perpage'],$counter['total']);$i++) {
		$keys[] = $keys_cache[$i];
	}

	if (count($keys)) {
		$items = '';
		foreach ($keys as $key) {
			$item_obj =& $items_cache[$key];
			$item = $item_obj->data;
			
			switch ($item['status']) {
				case KBANK_ITEM_SELLING:
				case KBANK_ITEM_AVAILABLE:
				case KBANK_ITEM_ENABLED:
					$itembit_right_column = '';
					$items .= $item_obj->showItem();
					break;
				case KBANK_ITEM_USED:
				case KBANK_ITEM_USED_WAITING:
					$items .= $item_obj->showItemActivated();
					break;
			}
		}
	} else {
		eval(standard_error(fetch_error('kbank_myitems_no_item')));
	}
}

// #####################################################################
// Buy, bid & Sell
// #####################################################################
if ($_GET['do'] == 'buy'){
	$vbulletin->input->clean_array_gpc('r', array(
		'itemid'    => TYPE_UINT));
	
	//Redirect to multitask
	$_GET['do'] = 'multitask';
	if (!is_array($buy_ids)) {
		$buy_ids = array();
	}
	$buy_ids[] = $vbulletin->GPC['itemid'];
}

if ($_GET['do'] == 'bid'){
	$processed = true;
	
	$vbulletin->input->clean_array_gpc('r', array(
		'itemid'    => TYPE_UINT));
		
	$item_obj =& newItem($vbulletin->GPC['itemid']);
	if (!$item_obj) {
		print_no_permission();
	}
	$item_obj->getExtraInfo();
	$item = $item_obj->data;
	
	//Simplely display an input form, can be easier!
	eval('$tmp = "' . fetch_template('kbank_template_bid') . '";');
	eval(standard_error($tmp,'',false));
}

if ($_GET['do'] == 'sell'){
	$processed = true;
	$navbits = array($vbulletin->kbank['phpfile'] . "?$session[sessionurl]" => $vbphrase['kbank'],
		$vbulletin->kbank['phpfile'] . "?$session[sessionurl]do=myitems" => $vbphrase['kbank_myitems']);
	$navbits[""] = $vbphrase['kbank_sell'];
	
	$vbulletin->input->clean_array_gpc('r', array(
		'itemid'    => TYPE_UINT));
		
	$item_obj =& newItem($vbulletin->GPC['itemid']);
	if (!$item_obj) {
		print_no_permission();
	}
	$item = $item_obj->data;
	
	if ($item['options']['receiver']) {
		$userids = implode(',',$item['options']['receiver']);
		$users = $vbulletin->db->query_read("
			SELECT userid,username
			FROM `" . TABLE_PREFIX . "user`
			WHERE userid IN ($userids)
		");
		$item['receiver'] = array();
		while ($user = $vbulletin->db->fetch_array($users)) {
			$item['receiver'][] = $user['username'];
		}
		$item['receiver'] = implode(',',$item['receiver']);
		unset($user);
		$vbulletin->db->free_result($users);
	}
		
	eval('$tmp = "' . fetch_template('kbank_template_sell') . '";');
	eval(standard_error($tmp,'',false));
}

if ($_GET['do'] == 'do_sell'){
	$processed = true;
	
	$vbulletin->input->clean_array_gpc('p', array(
		'itemid'    => TYPE_UINT,
		'description' => TYPE_NOHTML,
		'receiver' => TYPE_NOHTML,
		'price' => TYPE_UINT,
		));
		
	
}

if ($_GET['do'] == 'stop_sell'){	
	$vbulletin->input->clean_array_gpc('r', array(
		'itemid'    => TYPE_UINT));
	
	//Redirect to multitask
	$_GET['do'] = 'multitask';
	if (!is_array($stop_sell_ids)) {
		$stop_sell_ids = array();
	}
	$stop_sell_ids[] = $vbulletin->GPC['itemid'];
}

if ($_GET['do'] == 'gift'){
	$processed = true;
	$navbits = array($vbulletin->kbank['phpfile'] . "?$session[sessionurl]" => $vbphrase['kbank'],
		$vbulletin->kbank['phpfile'] . "?$session[sessionurl]do=myitems" => $vbphrase['kbank_myitems']);
	$navbits[""] = $vbphrase['kbank_gift'];
	
	$vbulletin->input->clean_array_gpc('r', array(
		'itemid'    => TYPE_UINT));	
		
	$item_obj =& newItem($vbulletin->GPC['itemid']);
	if (!$item_obj) {
		print_no_permission();
	}
	$item = $item_obj->data;
		
	eval('$tmp = "' . fetch_template('kbank_template_gift') . '";');
	eval(standard_error($tmp,'',false));
}

if ($_GET['do'] == 'do_gift'){
	$processed = true;
	
	$vbulletin->input->clean_array_gpc('p', array(
		'itemid'    => TYPE_UINT,
		'username' => TYPE_NOHTML,
		'message' => TYPE_NOHTML,
		'anonymous' => TYPE_UINT,
		));
		
	if (!$vbulletin->GPC['message']) {
		eval(standard_error(fetch_error('kbank_gift_message_empty')));
	}
		
	$item_obj =& newItem($vbulletin->GPC['itemid']);
	if (!$item_obj) {
		print_no_permission();
	}
	$item = $item_obj->data;
		
	if (!$item_obj->ready2Enable()) {
		print_no_permission();
	}
	
	$receiver = $vbulletin->db->query_first("SELECT userid, username	
		FROM `" . TABLE_PREFIX . "user`
		WHERE LOWER(username) = '" . $vbulletin->db->escape_string(strtolower($vbulletin->GPC['username'])) . "'");
	
	if (!$receiver) {
		eval(standard_error(fetch_error('error_kbank_sendmtonoexist')));
	}
	
	//send PM
	$myitems_links = $vbulletin->options['bburl'] . '/' . $vbulletin->kbank['phpfile'] . '?do=myitems';
	if ($vbulletin->GPC['anonymous']) {
		$from = array(
			'userid' => 1,
			'username' => $vbphrase['kbank'],
			'permissions' => array(
				'pmsendmax' => 5
			)
		);
		$message = construct_phrase($vbphrase['kbank_gift_pm_message_anonymous'],$item['name'],$vbulletin->GPC['message'],$myitems_links);
	} else {
		$from = $userinfo; //there are some problems here?
		$message = construct_phrase($vbphrase['kbank_gift_pm_message'],$item['name'],$vbulletin->GPC['message'],$myitems_links,$from['username']);
	}
	$to = $receiver;
	$subject = $vbphrase['kbank_gift_pm_subject'];
	
	$item_new = array(
		'status' => KBANK_ITEM_AVAILABLE,
		'userid' => $receiver['userid'],
	);
	$vbulletin->db->query_write(fetch_query_sql($item_new,'kbank_items',"WHERE itemid = $item[itemid]"));
	
	logkBankAction(
		"member_gift",
		$item['itemid'],
		array(
			'itemid' => $item['itemid'],
			'receiver' => $receiver['userid']
		)
	);
	
	$item_obj->doAction('gift');
	
	kbank_sendPM($from,$to,$subject,$message);
	
	$vbulletin->url = $vbulletin->kbank['phpfile'] . '?' . $vbulletin->session->vars['sessionurl'] ."do=myitems";
	eval(print_standard_redirect('kbank_gift_successful',true,true));
}

// #####################################################################
// Use/Enable/Disable
// #####################################################################
if (in_array($_GET['do'],array('use','enable','disable','do_use','do_enable','do_disable'))){
	$processed = true;
	
	$vbulletin->input->clean_array_gpc('r', array(
		'itemid'    => TYPE_UINT));

	$item_obj =& newItem($vbulletin->GPC['itemid']);
	if (!$item_obj
		OR $item_obj->data['userid'] != $vbulletin->userinfo['userid'] //Item Action can be done only by owner!
	) {
		print_no_permission();
	}
	$item =& $item_obj->data;
		
	if ($_GET['do'] == 'use'
		OR substr($_GET['do'],0,3) == 'do_') {
		$action = iif($_GET['do'] == 'use','use',substr($_GET['do'],3-strlen($_GET['do'])));
		logkBankAction(
			"member_item_action",
			$vbulletin->GPC['itemid'],
			array(
				'action' => ucfirst($action),
				'itemid' => $vbulletin->GPC['itemid']
			)
		);
	} else {
		$action = $_GET['do'];
	}

	$vbulletin->url = false;
	if ($item_obj->doAction($_GET['do'])) {	
		if ($vbulletin->url === false) {
			//redirect if no url specified
			$vbulletin->url = $vbulletin->kbank['phpfile'] . '?' . $vbulletin->session->vars['sessionurl'] ."do=myitems&itemid=$item[itemid]#item$item[itemid]";
		}
		eval(print_standard_redirect("kbank_{$action}_successful",true,true));
	} else {
		eval(standard_error(fetch_error('kbank_item_cant_used')));
	}
}

// #####################################################################
// Factory
// #####################################################################
if ($_GET['do'] == 'factory'){
	$processed = true;
	
	$navbits = array($vbulletin->kbank['phpfile'] . "?$session[sessionurl]" => $vbphrase['kbank']);
	$navbits[""] = $vbphrase['kbank_factory'];
	$kbank_template = 'kbank_factory';
	
	$query_where = '';
	if (is_array($vbulletin->userinfo['kbank_granted'])) {
		foreach ($vbulletin->userinfo['kbank_granted'] as $granted) {
			$query_where .= "OR userid LIKE '%,{$granted['userid']},%'";
		}
	}
	
	$itemtypes = $vbulletin->db->query_read("SELECT *
		FROM `" . TABLE_PREFIX . "kbank_itemtypes` AS itemtypes
		WHERE userid LIKE '%,{$vbulletin->userinfo['userid']},%'
			$query_where");

	if ($vbulletin->db->num_rows($itemtypes)) {
		$itemtypes_list = '';
		while ($itemtypedata = $vbulletin->db->fetch_array($itemtypes)) {
			if ($itemtype_obj =& newItemType($itemtypedata['itemtypeid'],$itemtypedata)) {
				$itemtype_obj->getExtraInfo();
				$itemtype = $itemtype_obj->data;
				
				eval('$itemtypes_list .= "' . fetch_template('kbank_itemtype_bit') . '";');
			}
		}
	} else {
		eval(standard_error(fetch_error('kbank_factory_no_technology')));
	}
	$vbulletin->db->free_result($itemtypes);
}

if ($_GET['do'] == 'produce'){
	$processed = true;
	$navbits = array($vbulletin->kbank['phpfile'] . "?$session[sessionurl]" => $vbphrase['kbank'],
		$vbulletin->kbank['phpfile'] . "?$session[sessionurl]do=myitems" => $vbphrase['kbank_myitems']);
	$navbits[""] = $vbphrase['kbank_produre'];
	
	$vbulletin->input->clean_array_gpc('r', array(
		'itemtypeid'    => TYPE_UINT));
		
	$itemtype_obj =& newItemType($vbulletin->GPC['itemtypeid']);
	if (!$itemtype_obj) {
		print_no_permission();
	}
	$itemtype_obj->getExtraInfo();
	$itemtype = $itemtype_obj->data;
		
	$exp = array(
		'day' =>  date('j',TIMENOW + 30*24*60*60 - $vbulletin->options['hourdiff']),
		'month' =>  date('n',TIMENOW + 30*24*60*60 - $vbulletin->options['hourdiff']),
		'year' =>  date('Y',TIMENOW + 30*24*60*60 - $vbulletin->options['hourdiff'])
	);
	
	$duration_picker = '';
	if ($itemtype_obj->options['use_duration']) {
		for ($i = 1; $i <= $vbulletin->kbank['maxItemDurationStep']; $i++) {
			$days = $i * $vbulletin->kbank['ItemDurationStep'];
			$price = $i * $itemtype['options']['duration_price'] + $itemtype['price'];
			$text = construct_phrase($vbphrase['kbank_itemtype_duration_price_bit'],$days,vb_number_format($price,$vbulletin->kbank['roundup']),$vbulletin->kbank['name']);
			$duration_picker .= "<option value=\"$days\">$text</option>";
		}
	}
	
	$manufactures = array();
	if (is_array($itemtype['manufactureids'])) {
		foreach ($itemtype['manufactureids'] as $manufactureid) {
			if ($manufactureid == $vbulletin->userinfo['userid']) {
				$manufactures[] = $vbulletin->userinfo;
			} else if (is_array($vbulletin->userinfo['kbank_granted'])) {
				foreach ($vbulletin->userinfo['kbank_granted'] as $granted) {
					if ($granted['userid'] == $manufactureid) {
						$manufactures[] = $granted;
					}
				}
			}
		}
	}
	if (count($manufactures) > 1) {
		//more than 1 available
		$account_info = buildAccountChooser($manufactures);
	}

	eval('$tmp = "' . fetch_template('kbank_template_produce') . '";');
	eval(standard_error($tmp,'',false));
}

if ($_GET['do'] == 'do_produce'){
	$processed = true;
	
	$vbulletin->input->clean_array_gpc('p', array(
		'itemtypeid'    => TYPE_UINT,
		'item' => TYPE_ARRAY,
		'receiver' => TYPE_NOHTML,
		'quantity' => TYPE_UINT,
		'exp' => TYPE_ARRAY,
		'duration' => TYPE_UINT,
		));
		
	$itemtype_obj =& newItemType($vbulletin->GPC['itemtypeid']);
	if (!$itemtype_obj) {
		print_no_permission();
	}
	$itemtype_obj->getExtraInfo();
	$itemtype = $itemtype_obj->data;
	
	if (!in_array($userinfo['userid'],$itemtype['manufactureids'])) {
		print_no_permission();
	}
	
	$item = $vbulletin->GPC['item'];
	$item['type'] = $vbulletin->GPC['itemtypeid'];
	$item['userid'] = $userinfo['userid'];
	$item['creator'] = $userinfo['userid'];
	$item['create_time'] = TIMENOW;
	$item['status'] = KBANK_ITEM_SELLING;
	
	if ($itemtype_obj->options['use_duration']) {
		$step = $vbulletin->GPC['duration']/$vbulletin->kbank['ItemDurationStep'];
		if ($step < 1 
			OR $step > $vbulletin->kbank['maxItemDurationStep']
			OR floor($step) < $step
			OR ceil($step) > $step) {
			eval(standard_error(fetch_error('kbank_duration_price_invalid')));
		} else {
			$item['options']['duration'] = $vbulletin->GPC['duration'];
		}
	}
	
	if ($vbulletin->GPC['receiver']) {
		$item['options']['receiver'] = array();
		$receivers = explode(',',$vbulletin->GPC['receiver']);
		$receivers_str = array();
		foreach ($receivers as $receiver) {
			$receivers_str[] = "'" . $vbulletin->db->escape_string(strtolower($receiver)) . "'";
		}
		if (count($receivers_str)) {
			$receivers_str = implode(',',$receivers_str);
			$receivers = $vbulletin->db->query_read("
				SELECT userid,username
				FROM `" . TABLE_PREFIX . "user` 
				WHERE LOWER(username) IN ($receivers_str)
					AND userid NOT IN ({$vbulletin->userinfo['userid']}{$vbulletin->userinfo['kbank_grantedids']})");
			while ($receiver = $vbulletin->db->fetch_array($receivers)) {
				$item['options']['receiver'][] = $receiver['userid'];
			}
			$vbulletin->db->free_result($receivers);
			unset($receiver);
		}
		if (!count($item['options']['receiver'])) {
			eval(standard_error(fetch_error('kbank_receiver_invalid')));
		}
	}
	
	if ($vbulletin->GPC['quantity'] <= 0
		OR !$item['name'] 
		OR !is_numeric($item['price']) 
		OR $item['price'] <= 0 ) {
		eval(standard_error(fetch_error('kbank_not_leave_blank')));
	}
		
	if (!$itemtype_obj->options['use_duration']) {
		$exp = $vbulletin->GPC['exp'];
		$item['expire_time'] = mktime(0,0,0 + $vbulletin->options['hourdiff'],$exp['month'],$exp['day'],$exp['year']);
	} else {
		$item['expire_time'] = TIMENOW + $step * $vbulletin->kbank['ItemDurationStep'] * 24*60*60;
	}
	
	$itemprice = $itemtype['price'] + $step * $itemtype['options']['duration_price'];
	
	if ($itemprice * $vbulletin->GPC['quantity'] > $userinfo[$vbulletin->kbank['field']]) {
		eval(standard_error(fetch_error('kbank_produce_not_enough_money',$userinfo[$vbulletin->kbank['field']],vb_number_format($itemprice,$vbulletin->kbank['roundup']),$vbulletin->GPC['quantity'],vb_number_format($itemprice * $vbulletin->GPC['quantity'],$vbulletin->kbank['roundup']),$vbulletin->kbank['name'])));
	}

	transferMoney(
		//sender userid
		$userinfo['userid']
		//receiver userid
		,0
		//amount of money
		,$itemprice * $vbulletin->GPC['quantity']
		//comment - support array
		,"produce_itemtype_$itemtype[itemtypeid]"
		//amount inhand - "null" to by pass validation
		,$userinfo[$vbulletin->kbank['field']]
		//boolean value: log donation or not
		,true
		//boolean value: auto send pm or not
		,false
		//tax rate - "false" to use default donation tax
		,KBANK_NO_TAX
		//boolean value: output or just return error message
		,true
		//postid
		,0
		//queries to run - array('from','to','banklogs_itemname')
		,array('banklogs_itemname' => 'items')
	);
	
	$id = array();
	if ($vbulletin->GPC['quantity'] > 0) {
		if (is_array($item['options'])) {
			$item['options'] = serialize($item['options']);
		}
		for ($i = 0; $i < $vbulletin->GPC['quantity']; $i++) {
			if ($vbulletin->GPC['quantity'] > 1) {
				$item['name'] = $vbulletin->GPC['item']['name'] . ' ' . ($i+1);
			}
			$vbulletin->db->query_write(fetch_query_sql($item,'kbank_items'));
			$id[] = $vbulletin->db->insert_id();
		}
	} else {
		eval(standard_error(fetch_error('kbank_produce_something')));
	}
	
	logkBankAction(
		"member_produce",
		count($id),
		array(
			'id' => implode(',',$id),
			'itemtypeid' => $item['type'],
			'price' => $itemprice
		)
	);
	
	$vbulletin->url = $vbulletin->kbank['phpfile'] . '?' . $vbulletin->session->vars['sessionurl'] ."do=shop&username={$userinfo['username']}";
	eval(print_standard_redirect('kbank_produce_successful',true,true));
}

// #####################################################################
// Tax
// #####################################################################
if ($_GET['do'] == 'tax'){
	$processed = true;
	
	$vbulletin->input->clean_array_gpc('r', array(
		'referer'    => TYPE_STR,
		'userid'	=> TYPE_UINT
		));
	
	$check_userid = false;
	//Calculate tax by internal function
	if ($userinfo['userid'] != $vbulletin->userinfo['userid']
		AND havePerm($vbulletin->userinfo,KBANK_PERM_ADMIN)) {
		$check_userid = $userinfo['userid'];
	}

	$tax = calcMonthlyTax($userinfo,1);

	if ($tax['tax_total'] <> 0
		OR $userinfo['userid'] != $vbulletin->userinfo['userid'] //This is not self-checking
		OR $tax['nexttime'] > TIMENOW //It's a pre-check
		OR $tax['tax_total'] > $tax['money']
		) {
		//Prepaire
		if ($tax['tax_total'] > $tax['money']) {
			$tax['color'] = 'red';
		} else {
			$tax['color'] = 'green';
		}
		
		$soft_detail = '';
		$soft_elements = array('total','post','company','kbank');
		
		foreach ($soft_elements as $type) {
			$e =& $tax['soft_detail'][$type];

			//if ($e['tax']) {
				$e_str = '';
				$more_info = '';
				$more_detail = '';
				if (count($e['info'])) {
					foreach ($e['info'] as $info) {
						$more_info .= '<li>'
							. construct_phrase($vbphrase["kbank_soft_detail_{$type}_info"]
								,$info['name']
								,vb_number_format($info['count'])
								,vb_number_format(abs($info['total']),$vbulletin->kbank['roundup'])
								,$vbulletin->kbank['name']
								,iif($info['total'] > 0,$vbphrase['kbank_soft_receive_from'],$vbphrase['kbank_soft_send_to'])
							)
							. '</li>';
					}
				}
				if (count($e['parts']) == 1) {
					$e_str .= construct_phrase($vbphrase["kbank_soft_detail_$type"]
							,vb_number_format($e['count'])
							,vb_number_format($e['parts'][0]['amount'],$vbulletin->kbank['roundup'])
							,vb_number_format($e['parts'][0]['percent'])
							,vb_number_format($e['tax'],$vbulletin->kbank['roundup'])
							,$vbulletin->kbank['name']
						);
				} else if (count($e['parts']) > 1) {
					foreach ($e['parts'] as $part) {
						$more_detail .= '<li>' 
							. construct_phrase($vbphrase["kbank_soft_more_detail"]
								,vb_number_format($part['amount'],$vbulletin->kbank['roundup'])
								,vb_number_format($part['percent'])
								,vb_number_format($part['tax'],$vbulletin->kbank['roundup'])
								,$vbulletin->kbank['name']
							)
							. '</li>';
					}
					$e_str .= construct_phrase($vbphrase["kbank_soft_detail_{$type}_with_more_detail"]
							,vb_number_format($e['count'])
							,vb_number_format($e['amount'],$vbulletin->kbank['roundup'])
							,vb_number_format($e['tax'],$vbulletin->kbank['roundup'])
							,$vbulletin->kbank['name']
						);
				} else if ($more_detail
					OR $more_info
					OR ($tax['soft_detail']['total']['tax'] > 0
					AND $e['amount'] <> 0)) {
					$e_str .= construct_phrase($vbphrase["kbank_soft_detail_{$type}_no_tax"]
						,vb_number_format($e['count'])
						,vb_number_format(abs($e['amount']),$vbulletin->kbank['roundup'])
						,$vbulletin->kbank['name']
						,iif($e['amount'] > 0,$vbphrase['kbank_soft_receive_from'],$vbphrase['kbank_soft_send_to'])
					);
				}
				if ($more_detail OR $more_info) {
					$e_str .=  ". $vbphrase[kbank_detail]:<ul>"
						. $more_detail
						. $more_info
						. '</ul>';
				}
				if ($e_str) {
					$soft_detail .= '<li>' . $e_str . '</li>';
				}
				
			//}
		}
		//Let's pay tax!
		
		if ($check_userid
			OR (!is_member_of($userinfo,$vbulletin->kbank['MemberGroupIDs'])
			AND !is_member_of($userinfo,$vbulletin->kbank['BankRuptGroupID'])
			OR $tax['nexttime'] > TIMENOW)) {
			$cant_pay_tax = true;
			$usernextpay = vbdate($vbulletin->options['dateformat'] . ' ' . $vbulletin->options['timeformat'],$tax['nexttime']);
		}

		eval('$tmp = "' . fetch_template('kbank_template_pay_tax') . '";');
		eval(standard_error($tmp,'',false));
	} else {
		//Tax = 0
		//Redirect to 'do_tax'
		$_GET['do'] = 'do_tax';
		$autodirected = true;
	}
}

if ($_GET['do'] == 'do_tax'){
	$processed = true;
	
	if ((!is_member_of($vbulletin->userinfo,$vbulletin->kbank['MemberGroupIDs'])
		AND !is_member_of($vbulletin->userinfo,$vbulletin->kbank['BankRuptGroupID'])
		OR calcMonthlyTaxPayTime($vbulletin->userinfo,'next',1) > TIMENOW)) {
		eval(standard_error(fetch_error('kbank_tax_not_require')));
	}
	
	if (!$autodirected) {
		$vbulletin->input->clean_array_gpc('p', array(
			'referer'    => TYPE_STR,
			'choice' => TYPE_STR
			));
		
		$tax = calcMonthlyTax($vbulletin->userinfo);
	} else {
		//Autodirect from 'tax'
		$vbulletin->GPC['choice'] = 'pay';
	}
	$vbulletin->url = $vbulletin->GPC['referer'];
	
	switch ($vbulletin->GPC['choice']) {
		case 'pay':
			transferMoney(
				//sender userid
				$vbulletin->userinfo['userid']
				//receiver userid
				,0
				//amount of money
				,$tax['tax_total']
				//comment - support array
				,'tax_' . TIMENOW
				//amount inhand - "null" to by pass validation
				,null
				//boolean value: log donation or not
				,true
				//boolean value: auto send pm or not
				,false
				//tax rate - "false" to use default donation tax
				,KBANK_NO_TAX
				//boolean value: output or just return error message
				,true
				//postid
				,0
				//queries to run - array('from','to','banklogs_itemname')
				,array('banklogs_itemname' => 'tax')
			);
			$user_new = array();
			if (is_member_of($vbulletin->userinfo,$vbulletin->kbank['BankRuptGroupID'])){
				if ($ban_record = $vbulletin->db->query_first("
					SELECT * FROM `" . TABLE_PREFIX . "userban`
					WHERE userid = adminid
						AND userid = {$vbulletin->userinfo['userid']}
						#AND reason = 'kbank_bankrupt_tax'")) {
				
					$user_new = array_merge($user_new,array(
						'usergroupid' => $ban_record['usergroupid'],
						'displaygroupid' => $ban_record['displaygroupid'],
						'usertitle' => $ban_record['usertitle'],
						'customtitle' => $ban_record['customtitle']
					));	
					$vbulletin->db->query_write("
						DELETE FROM `" . TABLE_PREFIX . "userban`
						WHERE userid = {$vbulletin->userinfo['userid']}
					");
				} else {
					$user_new['usergroupid'] = $vbulletin->kbank['NormalGroupID'];
					
					$usergroup = $vbulletin->usergroupcache["$user_new[usergroupid]"];
					if (!$usergroup['usertitle'])
					{
						$gettitle = $db->query_first("
							SELECT title
							FROM " . TABLE_PREFIX . "usertitle
							WHERE minposts <= {$vbulletin->userinfo['posts']}
							ORDER BY minposts DESC
						");
						$user_new['usertitle'] = $gettitle['title'];
					}
					else
					{
						$user_new['usertitle'] = $usergroup['usertitle'];
					}
				}
			}

			$user_new['kbank_nextpay'] = calcMonthlyTaxPayTime($vbulletin->userinfo,'next');
			$vbulletin->db->query_write(fetch_query_sql($user_new,'user',"WHERE userid = {$vbulletin->userinfo['userid']}"));
			break;
		case 'bankrupt':
			$newug = $vbulletin->kbank['BankRuptGroupID'];
			if ($newug != 0 
				AND $vbulletin->userinfo['usergroupid'] != $newug) {
				$set_query[] = "usergroupid = $newug";
			} else {
				$newug = 0;
			}			
			
			if ($newug) {
				//Add ban record
				$ban_record = array(
					'userid' => $vbulletin->userinfo['userid'],
					'usergroupid' => $vbulletin->userinfo['usergroupid'],
					'displaygroupid' => $vbulletin->userinfo['displaygroupid'],
					'usertitle' => $vbulletin->userinfo['usertitle'],
					'customtitle' => $vbulletin->userinfo['customtitle'],
					'adminid' => $vbulletin->userinfo['userid'],
					'bandate' => TIMENOW,
					'liftdate' => 0,
					'reason' => 'kbank_bankrupt_tax'
				);
				$vbulletin->db->query_write(fetch_query_sql($ban_record,'userban'));
				
				//Edit user info
				$user_new = array(
					'usergroupid' => $newug
				);
				
				$usergroup = $vbulletin->usergroupcache["$user_new[usergroupid]"];
				if (!$usergroup['usertitle'])
				{
					$gettitle = $db->query_first("
						SELECT title
						FROM " . TABLE_PREFIX . "usertitle
						WHERE minposts <= {$vbulletin->userinfo['posts']}
						ORDER BY minposts DESC
					");
					$user_new['usertitle'] = $gettitle['title'];
				}
				else
				{
					$user_new['usertitle'] = $usergroup['usertitle'];
				}
				
				$vbulletin->db->query_write(fetch_query_sql($user_new,'user',"WHERE userid = {$vbulletin->userinfo['userid']}"));
				
				logkBankAction(
					"member_bankrupt",
					0,
					array(
						'tax' => $tax['tax_total'],
						'inhand' => $vbulletin->userinfo["{$vbulletin->kbank['field']}"]
					)
				);
				
				if ($newug //User has been moved!
					AND $vbulletin->kbank['bankruptAnnounceThreadID'] > 0) {
					//Posting a reply automatically
					require_once(DIR . '/includes/functions_newpost.php');
					if ($threadinfo = verify_id('thread', $vbulletin->kbank['bankruptAnnounceThreadID'], 0, 1)
						AND $foruminfo = fetch_foruminfo($threadinfo['forumid'])
						AND $forumperms = fetch_permissions($foruminfo['forumid'])) {
						
						$newpost = array();
						$newpost['title'] = construct_phrase($vbphrase['kbank_post_bankrupt_title'],$vbulletin->userinfo['username']);
						if (!$newpost['title']) {	
							$newpost['title'] = $vbulletin->userinfo['username'] . ' has been Bank Rupted';
						}
						$newpost['message']	= construct_phrase(
							$vbphrase['kbank_post_bankrupt_message']
							,$vbulletin->userinfo['username']
							,vb_number_format($tax['tax_total'],$vbulletin->kbank['roundup'])
							,vb_number_format($vbulletin->userinfo["{$vbulletin->kbank['field']}"],$vbulletin->kbank['roundup'])
							,$vbulletin->kbank['name']
							,$vbulletin->options['bburl'] . '/' . $vbulletin->kbank['phpfile'] . '?to=' . $vbulletin->userinfo['username']
						);
						if (!$newpost['message']) {
							$newpost['message'] = 
								"Help me please!\r\n"
								. "I can't pay my Monthly Tax :(\r\n"
								. "It is " . vb_number_format($tax['tax_total'],$vbulletin->kbank['roundup']) . ' ' . $vbulletin->kbank['name'] . "."
								. "I only have " . vb_number_format($vbulletin->userinfo["{$vbulletin->kbank['field']}"],$vbulletin->kbank['roundup']) . ' ' . $vbulletin->kbank['name'] . ".\r\n"
								. "Somebody help me please?....\r\n\r\nClick [url=" . $vbulletin->options['bburl'] . '/' . $vbulletin->kbank['phpfile'] . '?to=' . $vbulletin->userinfo['username'] . "]here[/url] to donate or click thank under my post. Thank you very much!\r\n\r\n"
								. '[b][color="red"]Note: This message has been posted automatically[/color][/b]';
						}
						$newpost['iconid'] = $vbulletin->GPC['iconid'];
						$newpost['parseurl'] = ($foruminfo['allowbbcode'] AND $vbulletin->GPC['parseurl']);
						$newpost['signature'] = 0;
						$newpost['preview'] = false;
						$newpost['poststarttime'] = TIMENOW;
						$newpost['posthash'] = md5($newpost['poststarttime'] . $vbulletin->userinfo['userid'] . $vbulletin->userinfo['salt']);
						
						//Try to post new reply but do not display error (if exists)
						build_new_post('reply', $foruminfo, $threadinfo, false, $newpost, $errors);
					}
				}
			}
			break;
		case 'logout':
			$vbulletin->url = 'login.php?do=logout';
			break;
	}	
	
	eval(print_standard_redirect(fetch_error('kbank_tax_successful'),0,1));
}

if ($_GET['do'] == 'multitask'){
	$processed = true;
	
	$ours = array(
		'buy'    => TYPE_ARRAY,
		'sell' => TYPE_ARRAY,
		'stop_sell'    => TYPE_ARRAY,
		'bid' => TYPE_ARRAY
	);
	$vbulletin->input->clean_array_gpc('p',$ours);
	
	$itemids = array();
	foreach (array_keys($ours) as $type) {
		foreach (array_keys($vbulletin->GPC[$type]) as $itemid) {
			if (is_numeric($itemid)
				AND $itemid > 0) {
				eval("\${$type}_ids[] = $itemid;");
			}
		}
		eval('if (is_array($' . $type . '_ids)) {
			$itemids = array_merge($itemids,$' . $type . '_ids);
		} else {
			$' . $type . '_ids = array();
		}');
		eval('$' . $type . '_done = array();');
	}

	//Cache items
	newItem($itemids);
	//Setup variables
	$errors = array();
	$need2update = array();
	
	//Buy items
	foreach ($buy_ids as $itemid) {
		$item_obj =& newItem($itemid);
		if (!$item_obj) {
			$errors[$itemid][] = KBANK_ERROR_NO_PERM;
			continue;
		}
		$item = $item_obj->data;

		if (havePerm($vbulletin->userinfo,$item) //Buy his/her owned item - WTF?
			OR $item['status'] == KBANK_ITEM_DELETED //Buy deleted item
			OR ($item['status'] != KBANK_ITEM_BIDDING AND $item['expire_time'] < TIMENOW AND $item['expire_time'] > 0) //Not bidding but expired
			OR ($item['status'] == KBANK_ITEM_BIDDING AND $item['expire_time'] > TIMENOW) //Bidding but not expired
			OR !$item_obj->canBuy($userinfo['userid']) //Do not have permission to buy
			) {
			$errors[$itemid][] = KBANK_ERROR_NO_PERM;
			continue;
		}
		
		if ($item['status'] >= KBANK_ITEM_AVAILABLE) {
			$errors[$itemid][] = fetch_error('kbank_buy_item_bought');
			continue;
		}
		
		if ($item['status'] == KBANK_ITEM_BIDDING) {
			if ($item_obj->doAction('bid_expired')	//To ensure bidding expired and all job done!
				AND $item_obj->canBuy($userinfo['userid']) //Check for permission to buy (highest bid or not)
			) {
				//Everything look fine! Process with normal buy procedure
				if (count($item_obj->data['options']['receiver']) == 1) {
					//Return paid - on time and there is a winner!
					$highestBid = $item_obj->highestBid();
					$paid = 0;
					foreach ($item_obj->data['options']['bids'] as $record) {
						if ($record['userid'] == $userinfo['userid']) {
							$paid += $record['paid'];
						}
					}
					$item_obj->data['price'] -= $paid; 
				}
				
				$item = $item_obj->data; //Just for safe
			} else {
				$errors[$itemid][] = KBANK_ERROR_NO_PERM;
				continue;
			}
			$need2update['warningitem'] = true;
		}
		
		$taxrate = false;
		if ($item['userid'] == 0 //Buying from kBank
		) {
			$taxrate = KBANK_NO_TAX;
		} else if ($item['userid'] 
			AND $to = $vbulletin->db->query_first("
				SELECT username, usergroupid, membergroupids
				FROM `" . TABLE_PREFIX . "user`
				WHERE userid = $item[userid]
			")
			AND havePerm($to,KBANK_PERM_COMPANY,true)) {
			//Seller is a Company, apply Item Tax
			$taxrate = $vbulletin->kbank['ItemTax'];
			//If not, apply tax as normal Donate Tax
		}
		
		$result = transferMoney(
			//sender userid
			$userinfo['userid']
			//receiver userid
			,$item['userid']
			//amount of money
			,$item['price']
			//comment - support array
			,"buy_item_$item[itemid]"
			//amount inhand - "null" to by pass validation
			,$userinfo[$vbulletin->kbank['field']]
			//boolean value: log donation or not
			,true
			//boolean value: auto send pm or not
			,false
			//tax rate - "false" to use default donation tax
			,$taxrate
			//boolean value: output or just return error message
			,false
			//postid
			,0
			//queries to run - array('from','to','banklogs_itemname')
			,array('banklogs_itemname' => iif($item['userid'] == 0,'items','other'))
		);
		
		if ($result === true //if Transfer Money is okie!
		) {
			if ($to) {
				//send PM
				$myitems_links = $vbulletin->options['bburl'] . '/' . $vbulletin->kbank['phpfile'] . '?do=myitems';
				$from = $userinfo;
				$message = construct_phrase($vbphrase['kbank_buy_pm_message'],$item['name'],$item['price'],$userinfo['username'],$myitems_link);
				$subject = $vbphrase['kbank_buy_pm_subject'];
				
				$pm_result = kbank_sendPM($from,$to,$subject,$message,false);
				if ($pm_result !== true) {
					$errors[$itemid][] = $pm_result;
				}
			}
		} else {
			$errors[$itemid][] = $result;
			continue;
		}
		
		//Update total for later use
		$userinfo[$vbulletin->kbank['field']] -= $vbulletin->kbank['lastTransfered'];
		
		if ($item['status'] == KBANK_ITEM_SELLING) {			
			//$item_new_tmp =& newItem($item['itemid'],$item);

			$item_new = array(
				'expire_time' => $item_obj->data['expire_time'] //We always need this field!
			);
			$item_obj->doAction('buy');		
			foreach ($item_obj->data as $key => $val) {
				if ($val != $item[$key]) {
					$item_new[$key] = $val;
				}
			}
			
			$vbulletin->db->query_write(fetch_query_sql($item_new,'kbank_items',"WHERE itemid = $item[itemid]"));
			$new_itemid = $item['itemid'];
			$seller = $item['userid'];
		} else if ($item['status'] == KBANK_ITEM_SELLING_UNLIMIT) {
			$item_new_tmp = newItem(0,$item);
			
			$item_new_tmp->doAction('buy');
			$item_new = $item_new_tmp->data;
			
			//New item instead of update old
			$item_new['itemid'] = null;
			//Remove non-database field
			unset($item_new['username']);
			
			$vbulletin->db->query_write(fetch_query_sql($item_new,'kbank_items'));
			$new_itemid = $vbulletin->db->insert_id();
			$seller = 0;
			
			//Update counter
			$item_new = array(
				'options' => $item['options']
			);
			$item_new['options']['sold_counter']++;
			if (is_array($item_new['options'])) {
				$item_new['options'] = serialize($item_new['options']);
			}
			$vbulletin->db->query_write(fetch_query_sql($item_new,'kbank_items',"WHERE itemid = $item[itemid]"));
		}

		logkBankAction(
			"member_buy",
			$itemid,
			array(
				'itemid' => $itemid,
				'seller' => $seller
			)
		);
		
		$buy_done[$itemid] = array(
			'itemid' => $new_itemid,
			'seller' => $seller,
			'name' => $item['name']
		);
	}
	
	//Sell items
	foreach ($sell_ids as $itemid) {
		$sell_count++;
		$sell = $vbulletin->GPC['sell'][$itemid];
		$sell['price'] = intval($sell['price']);
		$sell['description'] = $vbulletin->input->do_clean($sell['description'],TYPE_NOHTML);
		$sell['receiver'] = $vbulletin->input->do_clean($sell['receiver'],TYPE_NOHTML);
		
		//skip item with price = 0
		if ($sell['price'] == 0) {
			if (count($itemids) == 1) {
				//get here from sell template
				$errors[$itemid][] = fetch_error('kbank_sell_price_invalid');
			} 
			continue;
		}
		
		$item_obj =& newItem($itemid);
		if (!$item_obj
			OR !$item_obj->ready2Enable()) {
			$errors[$itemid][] = KBANK_ERROR_NO_PERM;
			continue;
		}
		$item = $item_obj->data;
		
		//get real company status of item owner
		$iskBankCompany = false;
		if ($item['userid'] == $vbulletin->userinfo['userid']) {
			$iskBankCompany = havePerm($vbulletin->userinfo,KBANK_PERM_COMPANY,true);
		} else if (is_array($vbulletin->userinfo['kbank_granted'])) {
			$tmp_userinfo = $vbulletin->userinfo['kbank_granted'][$item['userid']];
			$iskBankCompany = havePerm($tmp_userinfo,KBANK_PERM_COMPANY,true);
		}
		
		if ($vbulletin->kbank['maxItemPriceRate'] //There is a limitation for item price! We will verify....
			AND !$iskBankCompany //Only apply to non-company
			AND $realprice = $item_obj->getRealPrice() //Get the based-price
			AND $sell['price'] > $realprice*$vbulletin->kbank['maxItemPriceRate']) {
			$errors[$itemid][] = fetch_error('kbank_sell_price_max',vb_number_format($sell['price'],$vbulletin->kbank['roundup']),vb_number_format($realprice*$vbulletin->kbank['maxItemPriceRate'],$vbulletin->kbank['roundup']),$vbulletin->kbank['name']);
			continue;
		}
		
		if ($sell['receiver'] 
			OR (is_array($item['options']['receiver']) 
				AND count($item['options']['receiver']))
			) {
			$item['options']['receiver'] = array();
			$receivers = explode(',',$sell['receiver']);
			$receivers_str = array();
			foreach ($receivers as $receiver) {
				$recever = trim($receiver);
				if ($receiver) {
					$receivers_str[] = "'" . $vbulletin->db->escape_string($receiver) . "'";
				}
			}
			if ($input_count = count($receivers_str)) {
				$receivers_str = implode(',',$receivers_str);
				$receivers = $vbulletin->db->query_read("
					SELECT userid,username
					FROM `" . TABLE_PREFIX . "user` 
					WHERE username IN ($receivers_str)
						AND userid NOT IN ({$vbulletin->userinfo['userid']}{$vbulletin->userinfo['kbank_grantedids']})");
				while ($receiver = $vbulletin->db->fetch_array($receivers)) {
					$item['options']['receiver'][] = $receiver['userid'];
				}
				$vbulletin->db->free_result($receivers);
				unset($receiver);
			}
			if (count($item['options']['receiver']) < $input_count) {
				$errors[$itemid][] = fetch_error('kbank_receiver_invalid');
				continue;
			}
		}
		
		$item_new = array(
			'status' => KBANK_ITEM_SELLING,
			'price' => $sell['price'],
			'options' => serialize($item['options'])
		);
		if ($sell['description']) {
			$item_new['description'] = $sell['description'];
		}
		$vbulletin->db->query_write(fetch_query_sql($item_new,'kbank_items',"WHERE itemid = $item[itemid]"));
		
		$item_obj->doAction('sell');
		
		$sell_done[$itemid] = array(
			'itemid' => $itemid,
			'name' => $item['name']
		);
	}
	
	//Stop sell items
	foreach ($stop_sell_ids as $itemid) {
		$item_obj =& newItem($itemid);
		if (!$item_obj) {
			$errors[$itemid][] = KBANK_ERROR_NO_PERM;
			continue;
		}

		$item = $item_obj->data;
			
		if (!havePerm($vbulletin->userinfo,$item)
			OR $item['status'] != KBANK_ITEM_SELLING) {
			$errors[$itemid][] = KBANK_ERROR_NO_PERM;
			continue;
		}
		
		$item_new = array(
			'status' => KBANK_ITEM_AVAILABLE
		);
		$vbulletin->db->query_write(fetch_query_sql($item_new,'kbank_items',"WHERE itemid = $item[itemid]"));
		
		$stop_sell_done[$itemid] = array(
			'itemid' => $itemid,
			'name' => $item['name']
		);
	}
	
	//Place bid for items
	foreach ($bid_ids as $itemid) {
		if ($vbulletin->GPC['bid'][$itemid] == 0) {
			if (count($itemids) == 1) {
				//get here from sell template
				$errors[$itemid][] = fetch_error('kbank_bid_invalid');
			} 
			continue;
		}
	
		$item_obj =& newItem($itemid);
		if (!$item_obj) {
			$errors[$itemid][] = KBANK_ERROR_NO_PERM;
			continue;
		}
		$item = $item_obj->data;
			
		if ($item['status'] != KBANK_ITEM_BIDDING) {
			$errors[$itemid][] = KBANK_ERROR_NO_PERM;
			continue;
		}
		
		$return = $item_obj->doAction('bid');
		
		if ($return === true) {
			$bid_done[$itemid] = array(
				'itemid' => $itemid,
				'name' => $item['name']
			);
			$need2update['warningitem'] = true;
		} else {
			$errors[$itemid][] = $return;
		}
	}
	
	if ($need2update['warningitem']) {
		updateWarningItem();
	}

	//Process output
	$processed = array();
	foreach (array_keys($ours) as $type) {
		eval('$check = is_array($' . $type . '_done);');
		if ($check) {
			eval('$processed = array_merge($processed,array_keys($' . $type . '_done));');
		}
	}
	foreach ($errors as $itemid => $error_message) {
		$processed[] = $itemid;
	}
	array_unique($processed);

	if (count($processed) == 1) {
		if (count($errors[$processed[0]])) {
			//There is(are) error(s)
			$error_message = array();
			foreach ($errors[$processed[0]] as $error) {
				if ($error === KBANK_ERROR_NO_PERM) {
					print_no_permission();
				} else {
					$error_message[] = $error;
				}
			}
			eval(standard_error(implode('<br/>',$error_message)));
		} else {
			//No error!
			if (count($buy_done) == 1) {
				//Buy 1 item
				$tmp = array_values($buy_done);
				$itemid = $tmp[0]['itemid'];
				$vbulletin->url = $vbulletin->kbank['phpfile'] . '?' . $vbulletin->session->vars['sessionurl'] ."do=myitems&itemid=$itemid#item$itemid";
				eval(print_standard_redirect('kbank_buy_successful',true,true));
			} else if (count($sell_done) == 1) {
				//Sell 1 item
				$tmp = array_values($sell_done);
				$itemid = $tmp[0]['itemid'];
				$vbulletin->url = $vbulletin->kbank['phpfile'] . '?' . $vbulletin->session->vars['sessionurl'] ."do=myitems&itemid=$itemid#item$itemid";
				eval(print_standard_redirect('kbank_sell_successful',true,true));
			} else if (count($stop_sell_done) == 1) {
				//Stop sell 1 item
				$tmp = array_values($stop_sell_done);
				$itemid = $tmp[0]['itemid'];
				$vbulletin->url = $vbulletin->kbank['phpfile'] . '?' . $vbulletin->session->vars['sessionurl'] ."do=myitems&itemid=$itemid#item$itemid";
				eval(print_standard_redirect('kbank_stop_sell_successful',true,true));
			} else if (count($bid_done) == 1) {
				//Place bid for 1 item
				$tmp = array_values($bid_done);
				$itemid = $tmp[0]['itemid'];
				$vbulletin->url = $vbulletin->kbank['phpfile'] . '?' . $vbulletin->session->vars['sessionurl'] ."do=shop&itemid=$itemid#item$itemid";
				eval(print_standard_redirect('kbank_place_bid_successful',true,true));
			}
		}
	} else {
		$message = array();
		foreach ($processed as $itemid) {
			$item = null;
			$actiontype = '';
			$error_message = array();
			
			foreach (array_keys($ours) as $type) {
				eval('$check = in_array($itemid,$' . $type . '_ids) AND isset($' . $type . '_done[$itemid]);');
				if ($check) {
					$actiontype = $type;
					eval('$item =& $' . $type . '_done[$itemid];');
				}
			}

			if ($item
				OR (
					$item == null
					AND isset($errors[$itemid])
				)
			) {
				$item_obj =& newItem($itemid);
				$item = $item_obj->data;
				
				if (isset($errors[$itemid])) {
					$errors_tmp =& $errors[$itemid];
					foreach ($errors_tmp as $error) {
						if ($error == KBANK_ERROR_NO_PERM) {
							$error_message[] = $vbphrase['kbank_multitask_error_no_perm'];
						} else {
							$error_message[] = $error;
						}
					}
				}
				
				$message_tmp = 
					'<strong>'
					. $vbphrase['kbank_' . $actiontype]
					. '</strong> "<em>'
					. $item['name']
					. '</em>"';
				
				if (count($error_message)) {
					$message_tmp .= 
						' ('
						. $vbphrase['kbank_multitask_error']
						. ')'
						. '<ul><li>' 
						. implode('</li><li>',$error_message) 
						. '</li></ul>';
				} else {
					$message_tmp .= ' - ' . $vbphrase['kbank_multitask_done'];
				}
				$message[] = $message_tmp;
			} else {
				//WTF?
			}
		}
		if (count($message)) {
			//$vbulletin->url = $vbulletin->kbank['phpfile'] . '?' . $vbulletin->session->vars['sessionurl'] ."do=myitems";
			$vbulletin->url = $_SERVER['HTTP_REFERER'];
			eval(print_standard_redirect('<div style="text-align:left">' . implode('<br/>',$message) . '</div>',false,true));
		} else {
			eval(standard_error(fetch_error('kbank_no_action')));
		}
	}
}

// #####################################################################
// Top list
// #####################################################################
if (isset($_GET[$vbulletin->kbank['url_varname']]) 
	OR ($_GET['do'] == $vbulletin->kbank['url_varname'])) {
	$processed = true;
	
	//Available Top Lists
	include_once(DIR . '/kbank/hook_kbank_tops.php');
}

// #####################################################################
// Help
// #####################################################################
if ($_GET['do'] == 'help') {
	$processed = true;
	
	$navbits = array($vbulletin->kbank['phpfile'] . "?$session[sessionurl]" => $vbphrase['kbank']);
	$navbits[$vbulletin->kbank['phpfile'] . "?$session[sessionurl]do=help"] = $vbphrase['kbank_misc_help'];
	$kbank_template = 'kbank_help';
	
	include_once(DIR . '/kbank/hook_kbank_help.php');
}

// #####################################################################
// Grant permission
// #####################################################################
if ($_GET['do'] == 'grant') {
	$processed = true;
	
	$vbulletin->input->clean_array_gpc('p', array(
		'remove' => TYPE_ARRAY,
		'add' => TYPE_ARRAY,
	));
	
	//Prepaire variables
	$alloweds = array();
	$allowedids = array();
	$alloweds_cache = $vbulletin->db->query_read("
		SELECT 
			allowed.grantid AS grantid
			,user.userid AS userid
			,user.username AS username
		FROM `" . TABLE_PREFIX . "kbank_granted_permission` as allowed
		INNER JOIN `" . TABLE_PREFIX . "user` AS `user` ON (user.userid = allowed.userid)
		WHERE allowed.allowid = {$vbulletin->userinfo['userid']}
	");
	if ($vbulletin->db->num_rows($alloweds_cache)) {
		while ($allowed = $vbulletin->db->fetch_array($alloweds_cache)) {
			$alloweds[$allowed['grantid']] = $allowed;
			$allowedids[] = $allowed['userid'];
		}
	}
	$vbulletin->db->free_result($alloweds_cache);
	unset($allowed);
	
	//remove
	$removeids = array();
	$removecount = 0;
	foreach (array_keys($vbulletin->GPC['remove']) as $removeid) {
		if ($removeid > 0
			AND isset($alloweds[$removeid])) {
			$removeids[] = $removeid;
		}
	}
	if (count($removeids)) {
		$vbulletin->db->query_write("
			DELETE FROM `" . TABLE_PREFIX . "kbank_granted_permission`
			WHERE grantid IN (" . implode(',',$removeids) . ")
		");
		$removecount = $vbulletin->db->affected_rows();
	}

	//Add new
	$addids = array();
	$addcount = 0;
	foreach ($vbulletin->GPC['add'] as $add) {
		$add['userid'] = $vbulletin->input->do_clean($add['userid'],TYPE_UINT);
		$add['username'] = $vbulletin->input->do_clean($add['username'],TYPE_NOHTML);
		if ($add['userid']
			AND $add['userid'] != $vbulletin->userinfo['userid'] //self-add? 
			AND !isset($allowedids[$add['userid']])
			AND !in_array($add['userid'],$addids)) {
			$addids[] = $add['userid'];
		}
		if ($add['username']
			AND $useradd = $vbulletin->db->query_first("
				SELECT userid, username
				FROM `" . TABLE_PREFIX . "user`
				WHERE LOWER(username) = '" . $vbulletin->db->escape_string(strtolower($add['username'])) . "'
			") //do database query
			AND $useradd['userid'] != $vbulletin->userinfo['userid'] //self-add? 
			AND !isset($allowedids[$useradd['userid']])
			AND !in_array($useradd['userid'],$addids)
		) {
			$addids[] = $useradd['userid'];
		}
	}
	array_unique($addids);
	if ($addids) {
		$query = '';
		foreach ($addids as $addid) {
			if ($query) {
				$query .= ","; 
			}
			$query .= "($addid,{$vbulletin->userinfo['userid']}," . TIMENOW . ")";
		}
		$query = "INSERT INTO `" . TABLE_PREFIX . "kbank_granted_permission`
			(userid,allowid,timeline)
			VALUES
			" . $query;
		$vbulletin->db->query_write($query);
		$addcount = $vbulletin->db->affected_rows();
	}
	
	if ($removecount OR $addcount) {
		$vbulletin->url = $_SERVER['HTTP_REFERER'];
		$message = array();
		if ($removecount) {
			$message[] = fetch_error('kbank_grant_removed',$removecount);
		}
		if ($addcount) {
			$message[] = fetch_error('kbank_grant_added',$addcount);
		}
		eval(print_standard_redirect(implode('<br/>',$message),false,true));
	} else {
		eval(standard_error(fetch_error('kbank_no_action')));
	}
}

($hook = vBulletinHook::fetch_hook('kbank_main')) ? eval($hook) : false;

// #####################################################################
// MAIN
// #####################################################################
if (!$processed) {
	$_GET['do'] = "donate";
}
if ($_GET['do'] == "donate") {
	$processed = true;

	$navbits = array("{$vbulletin->kbank['phpfile']}?$session[sessionurl]" => $vbphrase['kbank']);
	$navbits[""] = $vbphrase['kbank_account_management'];
	$to = $_GET['to'];
	
	//load latest history
	$cache = $db->query("select * from " . TABLE_PREFIX . "kbank_donations
		WHERE `to` IN ({$vbulletin->userinfo['userid']}{$vbulletin->userinfo['kbank_grantedids']})
			OR `from` IN ({$vbulletin->userinfo['userid']}{$vbulletin->userinfo['kbank_grantedids']})
		ORDER BY time DESC
		LIMIT 10");

	$recs_processed = '';
	while ($rec = $db->fetch_array($cache)) {
		$recs_processed .= showHistoryOne($rec);
	}
	$db->free_result($cache);
	unset($rec);
	//load latest history - complete!
	
	//load granted permission
	$granted_list = '';
	$granteds = array(
		$vbulletin->userinfo['userid'] => array(
			//self
			'userid' => $vbulletin->userinfo['userid']
			,'username' => $vbulletin->userinfo['username'] . ' (' . $vbphrase['kbank_yourself'] . '!)'
			,'usergroupid' => $vbulletin->userinfo['usergroupid']
			,'membergroupids' => $vbulletin->userinfo['membergroupids']
			,$vbulletin->kbank['field'] => $vbulletin->userinfo[$vbulletin->kbank['field']]
		)
	);
	if (is_array($vbulletin->userinfo['kbank_granted'])) {
		$granteds = array_merge($granteds,$vbulletin->userinfo['kbank_granted']);
	}
	//prepair output
	foreach ($granteds as $granted) {
		$permission_detail = array();
		if (!userBanned($granted['userid'],true)) {
			$permission_detail[] = $vbphrase['kbank_user'];
		}
		if (havePerm($granted,KBANK_PERM_ADMIN,true)) {
			$permission_detail[] = $vbphrase['kbank_admin_perm'];
		}
		if (havePerm($granted,KBANK_PERM_COMPANY,true)) {
			$permission_detail[] = $vbphrase['kbank_company'];
		}
		$permission_detail = implode('<br/>',$permission_detail);
		$granted_list .= 
			"
				<tr class=\"alt1\" align=\"center\">
					<td>$granted[userid]</td>
					<td>$granted[username]</td>
					<td>$permission_detail</td>
				</tr>
			";
	}
	//load granted permission - completed!
	
	//load allowed permission
	$allowed_list = '';
	$alloweds = $vbulletin->db->query_read("
		SELECT 
			allowed.grantid AS grantid
			,user.userid AS userid
			,user.username AS username
		FROM `" . TABLE_PREFIX . "kbank_granted_permission` as allowed
		INNER JOIN `" . TABLE_PREFIX . "user` AS `user` ON (user.userid = allowed.userid)
		WHERE allowed.allowid = {$vbulletin->userinfo['userid']}
	");
	if ($vbulletin->db->num_rows($alloweds)) {
		while ($allowed = $vbulletin->db->fetch_array($alloweds)) {
			$allowed_list .= "
				<tr class=\"alt1\" align=\"center\">
					<td>$allowed[userid]</td>
					<td>$allowed[username]</td>
					<td>
						<input type=\"checkbox\" id=\"remove_$allowed[grantid]\" name=\"remove[$allowed[grantid]]\" title=\"$vbphrase[remove]\"/>
						<label for=\"remove_$allowed[grantid]\">$vbphrase[remove]</label>
					</td>
				</tr>
			";
		}
	}
	$vbulletin->db->free_result($alloweds);
	unset($allowed);
	//load allowed permission - completed!
}

// #####################################################################
// END, FINISH TEMPLATES
// #####################################################################

//Account chooser
if (!($account_info = buildAccountChooser($vbulletin->userinfo['kbank_granted'],$vbulletin->userinfo))) {
	//if can't build chooser, display info message
	$account_info = construct_phrase($vbphrase['kbank_your_total'],$vbulletin->kbank['name'],vb_number_format($vbulletin->userinfo[$vbulletin->kbank['field']],$vbulletin->kbank['roundup']));
}

$navbits = construct_navbits($navbits);
eval('$navbar = "' . fetch_template('navbar') . '";'); 
eval('print_output("' . fetch_template(($kbank_template?$kbank_template:'kbank_donate')) . '");');
?>