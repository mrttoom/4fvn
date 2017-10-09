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
define('THIS_SCRIPT', 'kbankadmin');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('kbank');
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
include_once('./global.php');
include_once(DIR . '/kbank/functions.php');
include_once(DIR . '/includes/functions_misc.php');

// ###################### Check Permission ########################
if (!havePerm($vbulletin->userinfo,KBANK_PERM_ADMIN)) {
	print_stop_message('kbank_no_permission');
}

$processed = false;

// ########################################################################
// ######################### START HIDDEN SCRIPT ############################
// ########################################################################

// ###################### Forums Point Policy Manager ########################
if ($_GET['do'] == "policy_man") {
	$processed = true;
	print_cp_header("Forums Point Policy Manager");
	
	print_table_start();
	print_table_header('Forums Point Policy',4);
	
	$heading = array('Forum','Per Thread','Per Reply','Per Char');
	print_cells_row($heading,1);
	//Global Policy
	$cell = array();
	$cell[] = '<strong>GLOBAL</strong>';
	$cell[] = vb_number_format($vbulletin->kbank['perthread_default'],$vbulletin->kbank['roundup']);
	$cell[] = vb_number_format($vbulletin->kbank['perreply_default'],$vbulletin->kbank['roundup']);
	$cell[] = vb_number_format($vbulletin->kbank['perchar_default'],$vbulletin->kbank['roundup']);
	print_cells_row($cell);
	//Forums Policy
	foreach ($vbulletin->forumcache AS $forumid => $forum)
	{
		$forumtitle = construct_depth_mark($forum['depth'], '--', $startdepth) . ' ' . $forum['title'] . ' ' . iif(!($forum['options'] & $vbulletin->bf_misc_forumoptions['allowposting']), " ($vbphrase[forum_is_closed_for_posting])");
		$policy = getPointPolicy($forum);
		
		$cell = array();
		$cell[] = $forumtitle;
		$cell[] = $policy['kbank_perthread_str'];
		$cell[] = $policy['kbank_perreply_str'];
		$cell[] = $policy['kbank_perchar_str'];
		
		print_cells_row($cell);
	}
	print_table_footer(4);
	
	print_form_header('kbankadmin', 'do_resetpolicy');
	print_table_header('Reset Forums Point Policy');
	
	print_forum_chooser('Please select forum to reset', 'forum[]', -1, null, false, true);
	print_input_row('[New] Per Thread Value','perthread',-1);
	print_input_row('[New] Per Reply Value','perreply',-1);
	print_input_row('[New] Per Char Value','perchar',-1);
	
	print_submit_row("Reset Policy", 0);
	print_table_footer();
	
	print_cp_footer();
}

if ($_GET['do'] == "do_resetpolicy") {
	$processed = true;
	
	$vbulletin->input->clean_array_gpc('p', array(
		'forum' => TYPE_ARRAY_UINT,
		'perthread' => TYPE_INT,
		'perreply' => TYPE_INT,
		'perchar' => TYPE_INT,
	));
	
	$forumids =& $vbulletin->GPC['forum'];
	if (count($forumids) > 0)
	{
		$vbulletin->db->query("
			UPDATE `" . TABLE_PREFIX . "forum`
			SET 
				kbank_perthread = {$vbulletin->GPC['perthread']}
				,kbank_perreply = {$vbulletin->GPC['perreply']}
				,kbank_perchar = {$vbulletin->GPC['perchar']}
			WHERE forumid IN (" . implode(',',$forumids) . ")
		");
		
		build_forum_permissions();
		
		print_cp_message('Reset Forums Point Policy Operation Completed!', 'kbankadmin.php?do=policy_man');
	}
	else
	{
		print_cp_message('You haven\'t selected any forum to Reset Forums Point Policy');
	}
}

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

// ###################### Do Donate To All Members ########################
if ($_POST['do'] == "do_donate_all") {
	$processed = true;
	$vbulletin->input->clean_array_gpc('p', array(
		'amount' => TYPE_INT,
		'usergroup'    => TYPE_STR
	));
	print_cp_header("Donate To Members");

	if ($vbulletin->GPC['amount'] == 0){
        print_stop_message('kbank_sendmsomthing');
    }
	
	if($vbulletin->GPC['usergroup'] != "All"){
		if(!$db->query_first("SELECT * FROM " . TABLE_PREFIX . "usergroup where usergroupid='" . $vbulletin->GPC['usergroup'] . "'")){
			print_stop_message('kbank_invalid_usergroup');
		}
	}

	if($vbulletin->GPC['usergroup']=="All") {
		$users = $db->query_first("SELECT COUNT(userid) as count
			FROM `" . TABLE_PREFIX . "user`");
		$count = $users['count'];
		$db->query("UPDATE `" . TABLE_PREFIX . "user`
			SET " . $vbulletin->kbank['field'] . " = " . $vbulletin->kbank['field'] . " + " . $vbulletin->GPC['amount']);
		$vbulletin->db->query_write("UPDATE " . TABLE_PREFIX . "kbank_banklogs
			SET `amount` = `amount` - " . ($count*$vbulletin->GPC['amount']) . "
			WHERE itemname = 'admindonate'");
	} else {
		$reader = $db->query("SELECT userid 
			FROM `" . TABLE_PREFIX . "user`
			WHERE usergroupid = '" . $vbulletin->GPC['usergroup'] . "'");
		$members = array();
		while($cache = $db->fetch_array($reader)){
			$members[] = $cache['userid'];
		}
		$count = 0;
		if(count($members) > 0){ 
			foreach($members as $userid){
				giveMoney($userid,$vbulletin->GPC['amount'],'admindonate');
				$count++;
			}
		}
	}
	
	logkBankAction(
		'admin_donate_all',
		$vbulletin->GPC['amount'],
		array(
			'usergroup' => $vbulletin->GPC['usergroup'],
			'amount' => $vbulletin->GPC['amount']
		)
	);

	define('CP_REDIRECT', 'kbankadmin.php?do=donate_to_all');
	print_stop_message('kbank_donate_members',$count);
}

// ###################### Do Donate To Member ########################
if ($_POST['do'] == "do_donate_member") {
	$processed = true;
	$vbulletin->input->clean_array_gpc('p', array(
		'amount2'    => TYPE_INT,
		'username'    => TYPE_NOHTML,
		'comment' => TYPE_NOHTML
	));
	
	print_cp_header("Donate To Member");

	if ($vbulletin->GPC['amount2'] == 0){
        print_stop_message('kbank_sendmsomthing');
    }
	
	if (!$user = $db->query_first("SELECT userid FROM " . TABLE_PREFIX . "user WHERE username = '" . $db->escape_string($vbulletin->GPC['username']) . "'")){
		print_stop_message('kbank_sendmtonoexist');
	}

	giveMoney($user['userid'],$vbulletin->GPC['amount2'],'admindonate',$vbulletin->GPC['comment']);
	
	define('CP_REDIRECT', 'kbankadmin.php?do=donate_to_all');
	print_stop_message('kbank_donate_member',$vbulletin->GPC['username']);
}

// ###################### Bank Management ########################
if ($_GET['do'] == "bank_man") {
	$processed = true;
	
	print_cp_header("kBank Management");
	
	//get kBank Statistics
	$money = getStatistics();
	
	print_table_start();
	print_table_header('<a href="kbankadmin.php?do=stat#system">' . $vbphrase['kbank_banklogs_stat'] . '</a> ' . construct_phrase($vbphrase['kbank_misc_top_updatetime'],vbdate($vbulletin->options['timeformat'] . ' ' . $vbulletin->options['dateformat'],$money['timeline']),'-'));
	print_label_row(construct_phrase($vbphrase['kbank_banklogs_money_instock'],vb_number_format($money['total'] - $money['member']),$vbulletin->kbank['name'],vb_number_format($money['total'])));
	print_label_row(construct_phrase($vbphrase['kbank_banklogs_money_in'],vb_number_format($money['in']),$vbulletin->kbank['name']));
	print_label_row(construct_phrase($vbphrase['kbank_banklogs_money_out'],vb_number_format($money['out']),$vbulletin->kbank['name']));
	print_label_row(construct_phrase($vbphrase['kbank_banklogs_money_post'],vb_number_format($money['post']),$vbulletin->kbank['name']));
	print_label_row(construct_phrase($vbphrase['kbank_banklogs_money_register'],vb_number_format($money['register']),$vbulletin->kbank['name']));
	print_label_row(construct_phrase($vbphrase['kbank_banklogs_money_referer'],vb_number_format($money['referer']),$vbulletin->kbank['name']));
	print_label_row(construct_phrase($vbphrase['kbank_banklogs_money_donate'],vb_number_format($money['donate']),$vbulletin->kbank['name']));
	print_label_row(construct_phrase($vbphrase['kbank_banklogs_money_other'],vb_number_format($money['other']),$vbulletin->kbank['name']));
	print_label_row(construct_phrase($vbphrase['kbank_banklogs_money_member'],vb_number_format($money['member']),$vbulletin->kbank['name']));
	print_table_footer();
	
	print_form_header('kbankadmin', 'do_view_records');
	print_table_header("View Records");

	print_input_row("Days to View", 'days','7');
	print_time_row("Specific Day<dfn>Only work if \"Days to View\" is set to 1</dfn>",'day',TIMENOW,false);
	print_input_row("Username to View<dfn>Leave blank for all members</dfn>", 'username','');
	print_input_row("Records Per Page View", 'perpage',PERPAGE_DEFAULT);

	print_submit_row("View Records", 0);
	
	print_form_header('kbankadmin', 'do_view_logs');
	print_table_header("View kBank Logs");

	$logtypes = array(
		'admin' => 'View AdminCP Logs',
		'member' => 'View Member Logs'
	);
	print_select_row('Type to View', 'type', $logtypes, $selected = 'admin');
	print_input_row("Username to View<dfn>Leave blank for all members</dfn>", 'username','');
	print_input_row("Records Per Page View", 'perpage',PERPAGE_DEFAULT);

	print_submit_row("View Logs", 0);
	
	print_form_header('kbankadmin', 'do_ban');
	print_table_header('Ban Member From Actions (<a href="kbankadmin.php?' . $vbulletin->session->vars['sessionurl'] . 'do=view_banned">View banned Members</a>)');

	print_input_row("Username", 'username','');
	print_input_row("Days", 'day','');
	print_input_row("Reason", 'reason','');

	print_submit_row("Ban Member", 0);
	print_table_footer();
	
	print_cp_footer();
}

// ###################### Bank Management - Stat View ########################
if ($_GET['do'] == "stat") {
	$processed = true;
	
	print_cp_header("kBank Management - $vbphrase[kbank_banklogs_stat]");
	
	$vbulletin->input->clean_array_gpc('r', array(
        'limit' => TYPE_UINT,
		'before' => TYPE_UINT,
		'after' => TYPE_UINT
    ));
	
	$recs = getStatistics(false,iif($vbulletin->GPC['limit'],$vbulletin->GPC['limit'],PERPAGE_DEFAULT),iif($vbulletin->GPC['before'],$vbulletin->GPC['before'],false),iif($vbulletin->GPC['after'],$vbulletin->GPC['after'],false));
	
	print_table_start();
	print_table_header('<a name="system"></a>'.$vbphrase['kbank_banklogs_stat'],4);
	
	$headings = array();
	$headings[] = 'Timeline';
	$headings[] = 'Total';
	$headings[] = 'Member';
	$headings[] = 'InStock';
	print_cells_row($headings, 1);
	
	$first_timeline = $last_timeline = 0;
	if (is_array($recs)) {
		foreach ($recs as $timeline => $rec) {
			$cell = array();
			$cell[] = vbdate($vbulletin->options['timeformat'] . ' ' . $vbulletin->options['dateformat'],$timeline);
			$cell[] = vb_number_format($rec['total']);
			$cell[] = vb_number_format($rec['member']);
			$cell[] = vb_number_format($rec['total'] - $rec['member']);
			
			if (!$first_timeline) $first_timeline = $timeline;
			$last_timeline = $timeline;
			
			print_cells_row($cell);
		}
	}
	
	//Page processing (buttons)
	if ($vbulletin->GPC['after'] OR $vbulletin->GPC['before'])
	{
		$firstpage = "<input type=\"button\" class=\"button\" value=\"&laquo; " . $vbphrase['first_page'] . "\" tabindex=\"1\" onclick=\"window.location='kbankadmin.php?" . $vbulletin->session->vars['sessionurl'] . "do=stat'\">";
		$prevpage = "<input type=\"button\" class=\"button\" value=\"&lt; " . $vbphrase['prev_page'] . "\" tabindex=\"1\" onclick=\"window.location='kbankadmin.php?" . $vbulletin->session->vars['sessionurl'] . "do=stat&after=$first_timeline'\">";
	}

	if ($first_timeline != $last_timeline OR count($recs) < $limit)
	{
		$nextpage = "<input type=\"button\" class=\"button\" value=\"" . $vbphrase['next_page'] . " &gt;\" tabindex=\"1\" onclick=\"window.location='kbankadmin.php?" . $vbulletin->session->vars['sessionurl'] . "do=stat&before=$last_timeline'\">";
		$lastpage = "<input type=\"button\" class=\"button\" value=\"" . $vbphrase['last_page'] . " &raquo;\" tabindex=\"1\" onclick=\"window.location='kbankadmin.php?" . $vbulletin->session->vars['sessionurl'] . "do=stat&after=0'\">";
	}
	
	print_table_footer(4, "$firstpage $prevpage &nbsp; $nextpage $lastpage");
	
	print_cp_footer();
}

// ###################### Item Types Management ########################
if ($_GET['do'] == "type_man") {
	$processed = true;
	
	print_cp_header('Item Type Management');

	//Page processing
	$url_suffix = '';
	$sqlcond = 'WHERE 1=1';
	$vbulletin->input->clean_array_gpc('r', array(
		'perpage'    => TYPE_UINT,
		'pagenumber' => TYPE_UINT,
		'itemtypeid' => TYPE_UINT
	));

	if ($vbulletin->GPC['perpage'] < 1)
	{
		$vbulletin->GPC['perpage'] = PERPAGE_DEFAULT;
	} else {
		$url_suffix = "&perpage={$vbulletin->GPC['perpage']}";
	}

	$counter = $db->query_first("
		SELECT COUNT(*) AS total
		FROM `" . TABLE_PREFIX . "kbank_itemtypes`
		$sqlcond");
		
	$totalpages = ceil($counter['total'] / $vbulletin->GPC['perpage']);
	
	if ($vbulletin->GPC['itemtypeid']) {
		$before = $vbulletin->db->query_first("
			SELECT COUNT(*) AS total
			FROM `" . TABLE_PREFIX . "kbank_itemtypes`
			$sqlcond
				AND itemtypeid < {$vbulletin->GPC['itemtypeid']}
			ORDER BY itemtypeid
		");
		$vbulletin->GPC['pagenumber'] = floor($before['total']/$vbulletin->GPC['perpage']) + 1;
	}

	if ($vbulletin->GPC['pagenumber'] < 1)
	{
		$vbulletin->GPC['pagenumber'] = 1;
	}
	$startat = ($vbulletin->GPC['pagenumber'] - 1) * $vbulletin->GPC['perpage'];
	
	$itemtypes = $vbulletin->db->query_read("SELECT *
		FROM `" . TABLE_PREFIX . "kbank_itemtypes`
		$sqlcond
		ORDER BY itemtypeid
		LIMIT $startat,{$vbulletin->GPC['perpage']}");
		
	if ($vbulletin->db->num_rows($itemtypes)) {
		//Page processing (buttons)
		if ($vbulletin->GPC['pagenumber'] != 1)
		{
			$prv = $vbulletin->GPC['pagenumber'] - 1;
			$firstpage = "<input type=\"button\" class=\"button\" value=\"&laquo; " . $vbphrase['first_page'] . "\" tabindex=\"1\" onclick=\"window.location='kbankadmin.php?" . $vbulletin->session->vars['sessionurl'] . "do=type_man$url_suffix&page=1'\">";
			$prevpage = "<input type=\"button\" class=\"button\" value=\"&lt; " . $vbphrase['prev_page'] . "\" tabindex=\"1\" onclick=\"window.location='kbankadmin.php?" . $vbulletin->session->vars['sessionurl'] . "do=type_man$url_suffix&page=$prv'\">";
		}

		if ($vbulletin->GPC['pagenumber'] != $totalpages)
		{
			$nxt = $vbulletin->GPC['pagenumber'] + 1;
			$nextpage = "<input type=\"button\" class=\"button\" value=\"" . $vbphrase['next_page'] . " &gt;\" tabindex=\"1\" onclick=\"window.location='kbankadmin.php?" . $vbulletin->session->vars['sessionurl'] . "do=type_man$url_suffix&page=$nxt'\">";
			$lastpage = "<input type=\"button\" class=\"button\" value=\"" . $vbphrase['last_page'] . " &raquo;\" tabindex=\"1\" onclick=\"window.location='kbankadmin.php?" . $vbulletin->session->vars['sessionurl'] . "do=type_man$url_suffix&page=$totalpages'\">";
		}
	
		print_table_start();
		print_table_header("Item Type Management (Page: {$vbulletin->GPC['pagenumber']}/$totalpages)",4);
		
		$headings = array();
		$headings[] = '';
		$headings[] = 'Name';
		$headings[] = 'Detail';
		$headings[] = $vbphrase['controls'];
		print_cells_row($headings, 1);
		
		while($itemtypedata = $vbulletin->db->fetch_array($itemtypes)) {
			if ($itemtype_obj = newItemType($itemtypedata['itemtypeid'],$itemtypedata)
				AND !$itemtype_obj->deleted) {
				$itemtype_obj->getExtraInfo();
				$itemtype = $itemtype_obj->data;
				
				$cell = array();
				$cell[] = $itemtype['itemtypeid'];
				$cell[] = 
					'<div align="left">'
					. "<a name=\"itemtype$itemtype[itemtypeid]\"></a>"
					. $itemtype['shortinfo']				
					. '</div>';
				
				$cell[] = 
					'<div align="left">'
					. "Filename: $itemtype[filename]<br/>"
					. iif($itemtype['price'],"$vbphrase[kbank_item_price]: $itemtype[price_str]<br/>") 
					. iif($itemtype_obj->options['use_duration'],construct_phrase($vbphrase['kbank_item_price_use_duration'],$itemtype['duration_price_str']).'<br/>') 
					. iif($itemtype['manufactures'],construct_phrase($vbphrase['kbank_manufactures_are'],$itemtype['manufactures']).'<br/>')
					. iif($itemtype['options_processed_list'],construct_phrase($vbphrase['kbank_options_are'],$itemtype['options_processed_list']))
					. '</div>';
				$cell[] = 
					construct_link_code($vbphrase['edit'],"kbankadmin.php?do=type_update&itemtypeid=$itemtype[itemtypeid]")
					. '<br/>' . construct_link_code('Settings',"kbankadmin.php?do=settings_update&itemtypeid=$itemtype[itemtypeid]")
					. '<br/>' . construct_link_code('Add-Item',"kbankadmin.php?do=item_update&itemtypeid=$itemtype[itemtypeid]");
				
				print_cells_row($cell);
			}
		}
	
		print_table_footer(4, "$firstpage $prevpage &nbsp; $nextpage $lastpage");
		
		echo '<center>'. construct_link_code('Add Item Type','kbankadmin.php?do=type_update') . '</center>';
	} else {
		define('CP_REDIRECT','kbankadmin.php?do=type_update');
		print_stop_message('no_results_matched_your_query');
	}
	print_cp_footer();
}

if ($_GET['do'] == "type_update") {
	$vbulletin->input->clean_array_gpc('r',array(
		'itemtypeid' => TYPE_INT));
		
	if ($vbulletin->GPC['itemtypeid']) {
		$edit = true;
	}
	
	if ($edit) {
		print_cp_header('Edit Item Type');
		$itemtype_obj =& newItemType($vbulletin->GPC['itemtypeid']);
		if (!$itemtype_obj) {
			print_stop_message('kbank_no_permission');
		}
		$itemtype_obj->getExtraInfo();
		$itemtype = $itemtype_obj->data;
	} else {
		print_cp_header('Add Item Type');
	}

	$filenames = array();
	$folder = DIR . "/kbank/itemtypes";
	if (is_dir($folder)) {
		$dir_reader = opendir($folder);
		while ($file = readdir($dir_reader)) {
			$tmp = basename($file);
			$tmp = explode(".",$tmp);
			$name = $tmp[0];
			$middle = $tmp[1];
			$ext = $tmp[count($tmp) - 1];
			if (strtoupper($middle) == 'KBANK' 
				AND strtoupper($ext) == "PHP") {
				$filenames[$file] = $name;
			}
		}
	}

	print_form_header('kbankadmin', 'do_type_update');
	
	if ($edit) {
		print_table_header('Edit Item Type');
	} else {
		print_table_header('Add Item Type');
	}
	if ($edit) {
		construct_hidden_code('itemtypeid',$vbulletin->GPC['itemtypeid']);
	}

	print_input_row("Name", 'name',$itemtype['name']);
	print_select_row("Filename",'filename',$filenames,$itemtype['filename']);
	print_input_row("Description", 'description',$itemtype['description']);
	print_input_row("Price", 'price',$itemtype['price']);
	print_input_row("Manufacture(s)", 'userid',$itemtype['manufactureids_str']);
	$options = array(1 => $vbphrase['yes'], 0 => $vbphrase['no']);
	if ($edit) {
		print_radio_row('Delete Item Type','delete',$options,0);
	} else {
		print_radio_row('Add Another Item Type','addmore',$options,1);
	}

	if ($edit) {
		print_submit_row($vbphrase['save']);
	} else {
		print_submit_row($vbphrase['add'],0);
	}
	
	print_cp_footer();
}

if ($_GET['do'] == "do_type_update") {
	$vbulletin->input->clean_array_gpc('p',array(
		'itemtypeid' => TYPE_INT,
		'name' => TYPE_STR,
		'filename' => TYPE_STR,
		'description' => TYPE_NOHTML,
		'price' => TYPE_UINT,
		'userid' => TYPE_STR,
		'delete' => TYPE_INT,
		'addmore' => TYPE_INT
	));
	
	if ($vbulletin->GPC['itemtypeid']) {
		$edit = true;
		if ($vbulletin->GPC['delete']) {
			$itemtype_obj =& newItemType($vbulletin->GPC['itemtypeid']);
			if (!$itemtype_obj) {
				print_stop_message('kbank_no_permission');
			}
			$itemtype = $itemtype_obj->data;
			if ($itemtype) {
				$vbulletin->db->query_write("
					UPDATE `" . TABLE_PREFIX . "kbank_items`
					SET status = " . KBANK_ITEM_DELETED . "
					WHERE type = $itemtype[itemtypeid]");
					
				$vbulletin->db->query_write("DELETE FROM `" . TABLE_PREFIX . "kbank_itemtypes`
					WHERE itemtypeid = {$vbulletin->GPC[itemtypeid]}");
					
				logkBankAction(
					'admin_type_delete',
					$vbulletin->GPC['itemtypeid']
				);
				
				updateItemTypeCache();
					
				define('CP_REDIRECT','kbankadmin.php?do=type_man');
				print_stop_message('kbank_itemtype_deleted',$itemtype['name']);
			} else {
				print_stop_message('kbank_itemtype_delete_failed');
			}
		}
	}
	
	if (!$vbulletin->GPC['name'] || !$vbulletin->GPC['filename']) {
		print_stop_message('kbank_not_leave_blank');
	}
	
	$manufactures = explode(',',$vbulletin->GPC['userid']);
	$tmp = array(0);
	if ($vbulletin->GPC['userid'] && count($manufactures) > 0) {
		foreach ($manufactures as $manufacture) {
			if (is_numeric($manufacture) AND !in_array($manufacture,$tmp)) {
				$tmp[] = $manufacture;
			}
		}
		$tmp2 = $vbulletin->db->query_first("SELECT COUNT(userid) as countuser
			FROM `" . TABLE_PREFIX . "user`
			WHERE userid in (" . implode(',',$tmp) . ");");
		if ($tmp2['countuser'] != count($manufactures)) {
			print_stop_message('kbank_some_user_not_found');
		}
	}
	$tmp[] = 0;
	$manufactures = implode(',',$tmp);
	
	$itemtype = array(
		'name' => $vbulletin->GPC['name'],
		'filename' => $vbulletin->GPC['filename'],
		'description' => $vbulletin->GPC['description'],
		'price' => $vbulletin->GPC['price'],
		'userid' => $manufactures);
		
	if ($edit) {
		$vbulletin->db->query_write(fetch_query_sql($itemtype,'kbank_itemtypes',"WHERE itemtypeid = {$vbulletin->GPC[itemtypeid]}"));
		$id = $vbulletin->GPC['itemtypeid'];
	} else {
		$vbulletin->db->query_write(fetch_query_sql($itemtype,'kbank_itemtypes'));
		$id = $vbulletin->db->insert_id();
	}
	
	logkBankAction(
		'admin_type_update',
		$id,
		$itemtype
	);
	
	updateItemTypeCache();
	
	if ($edit) {
		define('CP_REDIRECT',"kbankadmin.php?do=type_man&itemtypeid=$id#itemtype$id");
		print_stop_message('kbank_itemtype_saved',$itemtype['name']);
	} else {
		if ($vbulletin->GPC['addmore']) {
			define('CP_REDIRECT',"kbankadmin.php?do=settings_update&itemtypeid=$id&addmore=1");
		} else {
			define('CP_REDIRECT',"kbankadmin.php?do=settings_update&itemtypeid=$id");
		}
		print_stop_message('kbank_itemtype_added',$itemtype['name']);
	}
}

if ($_GET['do'] == "settings_update") {
	$vbulletin->input->clean_array_gpc('r',array(
		'itemtypeid' => TYPE_UINT,
		'addmore' => TYPE_UINT
	));
		
	$itemtype_obj =& newItemType($vbulletin->GPC['itemtypeid']);
	if (!$itemtype_obj) {
		print_stop_message('kbank_no_permission');
	}
	$itemtype = $itemtype_obj->data;
	
	if (count($itemtype_obj->vars_use) > 0) {
		print_cp_header("Edit $itemtype[name]'s Settings");
				
		print_form_header('kbankadmin', 'do_settings_update');
		
		print_table_header("Edit $itemtype[name]'s Settings");

		construct_hidden_code('itemtypeid',$vbulletin->GPC['itemtypeid']);
		construct_hidden_code('addmore',$vbulletin->GPC['addmore']);
		
		foreach ($itemtype_obj->vars_use as $var => $info) {
			print_description_row($info['name'],0, 2, "optiontitle");
			$rowtitle = "<div class=\"smallfont\">$info[desc]</div>";
			$rowname = "options[$var]";
			$rowname2 = "options[{$var}_no2]";
			$rowvar = isset($itemtype['options'][$var])?$itemtype['options'][$var]:$info['default'];
			
			switch ($info['type']) {
				case TYPE_BOOL:
					print_radio_row($rowtitle,$rowname,array(1 => $vbphrase['yes'], 0 => $vbphrase['no']),$rowvar);
					break;
				case 'textarea':
					print_textarea_row($rowtitle,$rowname,$rowvar);
					break;
				case 'input_select':
					print_input_select_row($rowtitle,$rowname,$rowvar,$rowname2,$info['selectarray'],$rowvar);
					break;
				default:
					print_input_row($rowtitle,$rowname,$rowvar);
			}
		}
		
		//Global options for USE_DURATION itemtypes
		if ($itemtype_obj->options['use_duration']) {
			print_description_row('Duration Step Price',0, 2, "optiontitle");
			print_input_row("<div class=\"smallfont\">Enter price for each duration step</div>", "options[duration_price]",$itemtype['options']['duration_price']);
			print_description_row('Forever Duration Price',0, 2, "optiontitle");
			print_input_row("<div class=\"smallfont\">Enter price for FOREVER items<dfn>Leave blank to disable FOREVER items</dfn></div>", "options[duration_price_forever]",$itemtype['options']['duration_price_forever']);
		}
		//Image option
		print_description_row('Image URL',0, 2, "optiontitle");
		if ($itemtype['options']['image']) {
			print_label_row("Current Image","<img src=\"{$itemtype['options']['image']}\"/>");
		}
		print_input_row("<div class=\"smallfont\">Enter <strong>full</strong> URL to image<dfn>Leave blank to disable this item</dfn></div>", "options[image]",$itemtype['options']['image']);

		print_submit_row($vbphrase['save']);
		
		print_cp_footer();
	} else {
		print_stop_message('kbank_itemtype_no_settings');
	}
}

if ($_GET['do'] == "do_settings_update") {
	$vbulletin->input->clean_array_gpc('p',array(
		'itemtypeid' => TYPE_UINT,
		'addmore' => TYPE_UINT,
		'options' => TYPE_ARRAY
	));
	
	$options = $vbulletin->GPC['options'];
	$itemtype_obj =& newItemType($vbulletin->GPC['itemtypeid']);
	if (!$itemtype_obj) {
		print_stop_message('kbank_no_permission');
	}
	$itemtype = $itemtype_obj->data;
	$newoptions = $itemtype['options'];
	
	foreach ($itemtype_obj->vars_use as $var => $info) {
		$datatype = $info['type'];
		$value = $options[$var];
		if (is_string($info['type'])) {
			$datatype = $info['typereal'];
			if ($info['type'] == 'input_select') {
				if ($info['getfirst'] == 1) {
					$value = $options[$var]?$options[$var]:$options[$var . '_no2'];
				} else /*if ($info['getfirst'] == 2)*/ {
					$value = $options[$var . '_no2']?$options[$var . '_no2']:$options[$var];
				}
			}
		}
		$value = $vbulletin->input->do_clean($value,$datatype);
		if (isset($info['extrafunction'])) {
			eval($info['extrafunction']);
		}
		$newoptions[$var] = $value;
	}
	
	//Global options for USE_DURATION itemtypes
	if ($itemtype_obj->options['use_duration']) {
		//Duration price
		$value = $options['duration_price'];
		$value = $vbulletin->input->do_clean($value,TYPE_UINT);
		if (!$value) {
			print_stop_message('kbank_duration_price_invalid');
		}
		$newoptions['duration_price'] = $value;
		
		//Forever price
		$value = $options['duration_price_forever'];
		$value = $vbulletin->input->do_clean($value,TYPE_UINT);
		$newoptions['duration_price_forever'] = $value;
	}
	//Image option
	$newoptions['image'] = $vbulletin->input->do_clean($options['image'],TYPE_NOHTML);
	
	//Additional validations
	$itemtype_obj->validateSettings($newoptions);
	
	//Count for changed values
	$updated_count = 0;
	foreach ($newoptions as $key => $val) {
		if ($val != $itemtype['options'][$key]) {
			$updated_count++;
		}
	}

	if ($updated_count > 0)
	{
		$itemtype_new = array(
			'options' => serialize($newoptions)
		);
		
		$vbulletin->db->query_write(fetch_query_sql($itemtype_new,'kbank_itemtypes',"WHERE itemtypeid = {$vbulletin->GPC['itemtypeid']}"));
		
		logkBankAction(
			'admin_settings_update',
			$vbulletin->GPC['itemtypeid'],
			array(
				'itemtypeid' => $vbulletin->GPC['itemtypeid'],
				'count' => $updated_count
			)
		);
		
		updateItemTypeCache();
	}
	
	if ($vbulletin->GPC['addmore']) {
		define('CP_REDIRECT','kbankadmin.php?do=type_update');
	} else {
		define('CP_REDIRECT',"kbankadmin.php?do=type_man&itemtypeid={$vbulletin->GPC['itemtypeid']}#itemtype{$vbulletin->GPC['itemtypeid']}");
	}
	
	if ($updated_count > 0) 
	{
		print_stop_message('kbank_settings_saved',$updated_count);
	}
	else
	{
		print_stop_message('kbank_settings_saved_no_change');
	}
}

// ###################### Item Management ########################
if ($_GET['do'] == "item_man") {
	$processed = true;
	
	print_cp_header('Item Management');
	
	//Page processing
	$url_suffix = '';
	$vbulletin->input->clean_array_gpc('r', array(
		'perpage'    => TYPE_UINT,
		'pagenumber' => TYPE_UINT,
		'username' => TYPE_NOHTML,
		'date_type' => TYPE_INT,
		'date' => TYPE_ARRAY_UINT,
		'itemtypeid' => TYPE_STR,
		'itemname' => TYPE_NOHTML,
	));
	
	//Conditions
	//$sqlcond = 'WHERE status <> ' . KBANK_ITEM_DELETED;
	$sqlcond = 'WHERE 1=1';
	
	//search processing
	include(DIR . '/kbank/helper_item_search_process.php');
	
	$sqlcond .= $where_conditions;
	//search processing - complete

	if ($vbulletin->GPC['perpage'] < 1)
	{
		$vbulletin->GPC['perpage'] = PERPAGE_DEFAULT;
	} else {
		$page_suffix .= "&perpage={$vbulletin->GPC['perpage']}";
	}

	$counter = $db->query_first("
		SELECT COUNT(*) AS total
		FROM `" . TABLE_PREFIX . "kbank_items` AS items
		$sqlcond");
		
	$totalpages = ceil($counter['total'] / $vbulletin->GPC['perpage']);

	if ($vbulletin->GPC['pagenumber'] < 1)
	{
		$vbulletin->GPC['pagenumber'] = 1;
	}
	$startat = ($vbulletin->GPC['pagenumber'] - 1) * $vbulletin->GPC['perpage'];	
	
	$items = $vbulletin->db->query_read("SELECT *
		FROM `" . TABLE_PREFIX . "kbank_items` AS items
		$sqlcond
		ORDER BY itemid DESC
		LIMIT $startat, {$vbulletin->GPC['perpage']}");
		
	if ($vbulletin->db->num_rows($items)) {
		$url = "kbankadmin.php?" . $vbulletin->session->vars['sessionurl'] . "do=item_man$page_suffix&page";
	
		//Page processing (buttons)
		if ($vbulletin->GPC['pagenumber'] != 1)
		{
			$prv = $vbulletin->GPC['pagenumber'] - 1;
			$firstpage = "<input type=\"button\" class=\"button\" value=\"&laquo; " . $vbphrase['first_page'] . "\" tabindex=\"1\" onclick=\"window.location='$url=1'\">";
			$prevpage = "<input type=\"button\" class=\"button\" value=\"&lt; " . $vbphrase['prev_page'] . "\" tabindex=\"1\" onclick=\"window.location='$url=$prv'\">";
		}

		if ($vbulletin->GPC['pagenumber'] != $totalpages)
		{
			$nxt = $vbulletin->GPC['pagenumber'] + 1;
			$nextpage = "<input type=\"button\" class=\"button\" value=\"" . $vbphrase['next_page'] . " &gt;\" tabindex=\"1\" onclick=\"window.location='$url=$nxt'\">";
			$lastpage = "<input type=\"button\" class=\"button\" value=\"" . $vbphrase['last_page'] . " &raquo;\" tabindex=\"1\" onclick=\"window.location='$url=$totalpages'\">";
		}
		
		print_form_header('kbankadmin', 'item_man',false,true,'cpform','90%','',true,'get');
		
		print_table_header($vbphrase['kbank_item_man_filter']);
		
		print_input_row(construct_phrase($vbphrase['kbank_shop_search_by'],$vbphrase['kbank_item_name']),'itemname',$search['itemname']);
		print_input_row(construct_phrase($vbphrase['kbank_shop_search_by'],$vbphrase['kbank_username']),'username',$search['username']);
		$date_type = array(
			0 => $vbphrase['kbank_history_search_date_no'],
			-1 => $vbphrase['kbank_history_search_date_before'],
			1 => $vbphrase['kbank_history_search_date_after'],
		);
		print_select_row(construct_phrase($vbphrase['kbank_shop_search_by'],$vbphrase['kbank_exp']),'date_type',$date_type,$search['date_type']);
		print_time_row('','date',$search['date'],false);
		print_select_row(construct_phrase($vbphrase['kbank_shop_search_by'],$vbphrase['kbank_itemtype_name']),'itemtypeid',$itemtypes_list_raw,$search['itemtypeid']);
		
		print_submit_row($vbphrase['search']);
	
		print_table_start();
		print_table_header("Item Management (Page: {$vbulletin->GPC['pagenumber']}/$totalpages)",5);
		
		$headings = array();
		//$headings[] = 'ItemID';
		$headings[] = 'Name';
		$headings[] = 'Price';
		$headings[] = 'Owner';
		$headings[] = 'Status';
		$headings[] = $vbphrase['controls'];
		print_cells_row($headings, 1);
		
		while($itemdata = $vbulletin->db->fetch_array($items)) {
			if ($item_obj = newItem($itemdata['itemid'],$itemdata)) {
				$item_obj->getExtraInfo();
				$item = $item_obj->data;
			
				$cell = array();
				//$cell[] = $item['itemid'];
				$cell[] = $item['shortinfo'];
				$cell[] = $item['price_str'];
				$cell[] = $item['seller'];
				$cell[] = $item_obj->getStatus('<br/>');
				$cell[] =
					iif(
						$item['status'] != KBANK_ITEM_DELETED
						,iif(
						$item['status'] != KBANK_ITEM_PENDING
						//normal item status
						,construct_link_code($vbphrase['edit'],"kbankadmin.php?do=item_update&itemid=$item[itemid]")
						//pending item status
						,construct_link_code(
							iif(isset($item['options']['approved'][$vbulletin->userinfo['userid']])
							,$vbphrase['update']
							,$vbphrase['kbank_approve'])
							,"kbankadmin.php?do=item_update&itemid=$item[itemid]&approve=1")
						)
					);
				
				print_cells_row($cell);
			}
		}
	
		print_table_footer(5, "$firstpage $prevpage &nbsp; $nextpage $lastpage");
		
		echo '<center>'. construct_link_code('Add Item','kbankadmin.php?do=item_update') . '</center>';
	} else {
		define('CP_REDIRECT','kbankadmin.php?do=item_update');
		print_stop_message('no_results_matched_your_query');
	}
	print_cp_footer();
}

if ($_GET['do'] == "item_update") {
	$vbulletin->input->clean_array_gpc('r',array(
		'itemtypeid' => TYPE_UINT,
		'auction' => TYPE_UINT,
		'itemid' => TYPE_UINT,
		'approve' => TYPE_UINT
	));
	
	//Setup variables
	if ($vbulletin->GPC['itemid']) {
		$edit = true;
		
		//Create item object
		$item_obj =& newItem($vbulletin->GPC['itemid']);
		if (!$item_obj) {
			print_stop_message('kbank_no_permission');
		}
		$item = $item_obj->data;
		$itemtype_obj =& $item_obj->itemtype;
		$itemtype = $itemtype_obj->data;
		
		$vbulletin->GPC['auction'] = ($item_obj->data['status'] == KBANK_ITEM_BIDDING);
	} else if (!$vbulletin->GPC['itemtypeid']) {
		//No itemtypeid specified, display form to select
		print_cp_header('Add Item');
		
		$itemtypes = array();
		if (!isset($vbulletin->kbank_itemtypes)) {
			$vbulletin->kbank_itemtypes = updateItemTypeCache();
		}
		if (!is_array($vbulletin->kbank_itemtypes)
			OR count($vbulletin->kbank_itemtypes) == 0) {
			//No itemtype found! Redirect to add itemtype
			define('CP_REDIRECT','kbankadmin.php?do=type_update');
			print_stop_message('kbank_no_itemtype');
		}
		foreach ($vbulletin->kbank_itemtypes as $itemtype) {
			$itemtypes[$itemtype['itemtypeid']] = $itemtype['name'];
		}
		
		print_form_header('kbankadmin', 'item_update');
		print_table_header('Add Item');
		print_select_row("Item Type",'itemtypeid',$itemtypes);
		print_radio_row("Auction?",'auction',array(1 => $vbphrase['yes'],0=>$vbphrase['no']),0);
		print_submit_row($vbphrase['add'],0);
		print_cp_footer();
	} else {
		$itemtype_obj =& newItemType($vbulletin->GPC['itemtypeid']);
		if (!$itemtype_obj) {
			print_stop_message('kbank_no_permission');
		}
		$itemtype = $itemtype_obj->data;
		$item['expire_time'] = TIMENOW - $vbulletin->options['hourdiff'] + 30*24*60*60;
		$item['userid'] = 0;
	}
	if ($itemtype_obj->options['use_duration']) {
		$durations = array();
		for ($i = 1; $i <= $vbulletin->kbank['maxItemDurationStep']; $i++) {
			$days = $i * $vbulletin->kbank['ItemDurationStep'];
			$price = $i * $itemtype['options']['duration_price'] + $itemtype['price'];
			$text = construct_phrase($vbphrase['kbank_itemtype_duration_price_bit'],$days,vb_number_format($price),$vbulletin->kbank['name']);
			$durations[$days] = $text;
		}
		if ($itemtype['options']['duration_price_forever'] > 0) {
			$durations[-1] = $vbphrase['kbank_forever'] . ' - ' . vb_number_format($itemtype['options']['duration_price_forever']) . ' ' . $vbulletin->kbank['name'];
		}
	}
	//Setup variables - complete!
	
	//Work
	//Approve item
	if ($item_obj
		AND $vbulletin->GPC['approve']) {
		$item_obj->doAction('approve');
	}

	//Display input form
	if ($edit) {
		//Edit existing item
		print_cp_header('Edit Item');
		print_form_header('kbankadmin', 'do_item_update');
		print_table_header('Edit Item');
		
		construct_hidden_code('itemid',$vbulletin->GPC['itemid']);
		construct_hidden_code('item[creator]',$item['creator']);
		construct_hidden_code('item[create_time]',$item['create_time']);
		print_input_row("Name", 'item[name]',$item['name']);
		construct_hidden_code('item[type]',$itemtype['itemtypeid']);
		print_label_row("Item Type",$itemtype['name']);
		print_input_row("Description", 'item[description]',$item['description']);
		print_input_row(iif($vbulletin->GPC['auction'],"Bid","Price"), 'item[price]',$item['price']);
		if ($vbulletin->GPC['auction']) {
			construct_hidden_code('item[userid]',0);
			construct_hidden_code('auction',1);
			print_time_row("Auction Expire Time",'auction_exp',$item['expire_time']);
		} else {
			print_input_row("<strong style=\"color: red\">Owner UserID</strong>", 'item[userid]',$item['userid']);
			print_radio_row('Item NEVER expires','forever',array(1 => 'Yes', 0 => 'No'),0);
			print_time_row('Expire Date<dfn>Only work if select NO in "Item NEVER expires"</dfn>','exp',$item['expire_time'],false);
		}
		if ($durations) {
			print_select_row($vbphrase['kbank_item_duration'],'duration',$durations,$item['options']['duration']);
		}
		print_radio_row('Delete Item','delete',array(1 => 'Yes', 0 => 'No'),0);
		
		print_submit_row($vbphrase['save']);
	} else {
		//Add new item
		print_cp_header('Add Item');
		print_form_header('kbankadmin', 'do_item_update');
		print_table_header('Add Item');
		
		print_input_row("Name", 'item[name]',$item['name']);
		construct_hidden_code('item[type]',$itemtype['itemtypeid']);
		print_label_row("Item Type",$itemtype['name']);
		print_input_row("Description", 'item[description]',$item['description']);
		print_input_row(iif($vbulletin->GPC['auction'],"Bid","Price"), 'item[price]',$item['price']);
		if ($vbulletin->GPC['auction']) {
			construct_hidden_code('item[userid]',0);
			construct_hidden_code('auction',1);
			construct_hidden_code('quantity',1);
			print_time_row("Auction Expire Time",'auction_exp',$item['expire_time']);
		} else {
			print_input_row("<strong style=\"color: red\">Owner UserID</strong>", 'item[userid]',$item['userid']);
			print_input_row("<strong style=\"color: red\">Quantity</strong><dfn>Enter 0 for unlimited</dfn>", 'quantity',1);
		}
		if ($durations) {
			print_label_row($vbphrase['kbank_exp_base_duration']);
			print_select_row($vbphrase['kbank_item_duration'],'duration',$durations);
		} else {
			print_radio_row('Item NEVER expires','forever',array(1 => 'Yes', 0 => 'No'),0);
			print_time_row('Expire Date<dfn>Only work if select NO in "Item NEVER expires"</dfn>','exp',$item['expire_time'],false);
		}
		print_radio_row('Add Another Item','addmore',array(2 => 'Yes, with the same options (ItemType/Auction)',1 => 'Yes', 0 => 'No'),2);
		
		print_submit_row($vbphrase['add'],0);
	}
	
	print_cp_footer();
}

if ($_GET['do'] == "do_item_update") {
	$vbulletin->input->clean_array_gpc('p',array(
		'auction' => TYPE_UINT,
		'auction_exp' => TYPE_ARRAY,
		'itemid' => TYPE_UINT,
		'item' => TYPE_ARRAY,
		'quantity' => TYPE_UINT,
		'forever' => TYPE_UINT,
		'exp' => TYPE_ARRAY,
		'duration' => TYPE_INT,
		'delete' => TYPE_UINT,
		'addmore' => TYPE_UINT
	));
	
	$item = $vbulletin->GPC['item'];
	$item['options'] = array();
	$itemtype_obj = newItemType($item['type']);
	$itemtype = $itemtype_obj->data;
	if ($vbulletin->GPC['itemid']) {
		$edit = true;
		$item_obj =& newItem($vbulletin->GPC['itemid']);
		if (!$item_obj) {
			print_stop_message('kbank_no_permission');
		}
		$item_tmp = $item_obj->data;
		$item['options'] = $item_tmp['options'];
	
		if ($vbulletin->GPC['delete']) {
			if ($item_obj) {
				$vbulletin->db->query_write("
					UPDATE `" . TABLE_PREFIX . "kbank_items`
					SET status = " . KBANK_ITEM_DELETED . "
					WHERE itemid = {$vbulletin->GPC[itemid]}");
					
				logkBankAction(
					'admin_item_delete',
					$vbulletin->GPC['itemid']
				);
				
				//Fix auction notices issue 30-12-2008
				updateWarningItem();
					
				define('CP_REDIRECT','kbankadmin.php?do=item_man');
				print_stop_message('kbank_item_deleted',$item['name'],$vbulletin->GPC['itemid']);
			} else {
				print_stop_message('kbank_item_delete_failed');
			}
		}
		if ($item['status'] == KBANK_ITEM_DELETED) {
			print_stop_message('kbank_no_permission');
		}
		
		$vbulletin->GPC['auction'] = ($item_obj->data['status'] == KBANK_ITEM_BIDDING);
	} else {
		$item['creator'] = $vbulletin->userinfo['userid'];
		$item['create_time'] = TIMENOW;
		if ($item['userid'] == 0) {
			if ($vbulletin->GPC['auction']) {
				$item['status'] = KBANK_ITEM_BIDDING;
			} else {
				$item['status'] = KBANK_ITEM_SELLING;
			}
		} else {
			$item['status'] = KBANK_ITEM_AVAILABLE;
		}
		if ($vbulletin->kbank['requestApproval'] <= 1) {
			//Request approval require less than 1 kBank Admin to add new item. The admin who is adding this one have enough permission!
			//Nothing to do!
		} else {
			//Need more than 1 kBank Admin!
			$item['options']['approved'][$vbulletin->userinfo['userid']] = $vbulletin->userinfo['username']; //Manually approve
			$item['options']['status_pending'] = $item['status']; //Store item status to recover later
			$item['status'] = KBANK_ITEM_PENDING; //Adjust pending status
		}
	}
	
	if (!$item['name'] || !is_numeric($item['price']) || $item['price'] <= 0 ) {
		print_stop_message('kbank_not_leave_blank');
	}
	if (!$itemtype_obj->options['use_duration']) {
		if ($vbulletin->GPC['forever']) {
			$item['expire_time'] = -1;
		} else {
			$exp = $vbulletin->GPC['exp'];
			$item['expire_time'] = mktime(0,0,0 + $vbulletin->options['hourdiff'],$exp['month'],$exp['day'],$exp['year']);
		}
	} else {
		if ($vbulletin->GPC['duration'] == -1
			AND !$itemtype['options']['duration_price_forever']) {
			print_stop_message('kbank_itemtype_duration_invalid');
		}
		if ($vbulletin->GPC['duration'] > 0) {
			$expire_time = $item['create_time'] + $vbulletin->GPC['duration']*24*60*60;
		} else {
			$expire_time = -1;
		}
		if (!is_array($item['options'])) {
			$item['options'] = array();
		}
		$item['options']['duration'] = $vbulletin->GPC['duration'];
		if ($vbulletin->GPC['exp']) {
			//Edit?
			if ($vbulletin->GPC['forever']) {
				$item['expire_time'] = -1;
			} else {
				$exp = $vbulletin->GPC['exp'];
				$item['expire_time'] = mktime(0,0,0 + $vbulletin->options['hourdiff'],$exp['month'],$exp['day'],$exp['year']);
			}
		} else {
			$item['expire_time'] = $expire_time;
		}
	}
	
	//Store data for bidding item
	if ($vbulletin->GPC['auction']) {
		$auction_exp = $vbulletin->GPC['auction_exp'];
		$auction_exp = mktime($auction_exp['hour'],$auction_exp['minute'],0 + $vbulletin->options['hourdiff'],$auction_exp['month'],$auction_exp['day'],$auction_exp['year']);
		if (!isset($item['options']['expire_time_bidding'])) {
			$item['options']['expire_time_bidding'] = iif($item['expire_time'] > 0,$item['expire_time'] - $item['create_time'],$item['expire_time']);
		} else if ($itemtype_obj->options['use_duration']) {
			//expire time based on active duration
			$item['options']['expire_time_bidding'] = iif($expire_time > 0,$expire_time - $item['create_time'],$expire_time);
		}
		$item['expire_time'] = $auction_exp;
	}
	
	$id = array();
	if (is_array($item['options'])) {
		$item['options'] = serialize($item['options']);
	}
	if ($edit) {
		$vbulletin->db->query_write(fetch_query_sql($item,'kbank_items',"WHERE itemid = {$vbulletin->GPC[itemid]}"));
		$id[] = $vbulletin->GPC['itemid'];
	} else {
		if ($vbulletin->GPC['quantity'] > 0) {
			for ($i = 0; $i < $vbulletin->GPC['quantity']; $i++) {
				if ($vbulletin->GPC['quantity'] > 1) {
					$item['name'] = $vbulletin->GPC['item']['name'] . ' ' . ($i+1);
				}
				$vbulletin->db->query_write(fetch_query_sql($item,'kbank_items'));
				$id[] = $vbulletin->db->insert_id();
			}
		} else {
			if ($item['userid'] == 0) {
				$item['status'] = KBANK_ITEM_SELLING_UNLIMIT;
				$vbulletin->db->query_write(fetch_query_sql($item,'kbank_items'));
				$id[] = $vbulletin->db->insert_id();
			} else {
				print_stop_message('kbank_unlimit_to_user');
			}
		}
	}
	
	logkBankAction(
		'admin_item_update',
		count($id),
		array(
			'id' => implode(',',$id),
			'itemtypeid' => $item['type']
		)
	);
	
	updateWarningItem();
	
	if ($edit) {
		define('CP_REDIRECT','kbankadmin.php?do=item_man');
		print_stop_message('kbank_item_saved',$itemtype['name'],implode(',',$id));
	} else {
		if ($vbulletin->GPC['addmore']) {
			if ($vbulletin->GPC['addmore'] == 2) {
				define('CP_REDIRECT','kbankadmin.php?do=item_update&itemtypeid=' . $itemtype['itemtypeid'] . '&auction=' . $vbulletin->GPC['auction']);
			} else {
				define('CP_REDIRECT','kbankadmin.php?do=item_update');
			}
		} else {
			define('CP_REDIRECT','kbankadmin.php?do=item_man');
		}
		print_stop_message('kbank_item_added',$itemtype['name'],implode(',',$id));
	}
}

// ###################### View Records ########################
if ($_GET['do'] == "do_view_records") {
	$processed = true;
	
	print_cp_header('View Records');
	
	$vbulletin->input->clean_array_gpc('r', array(
		'perpage'    => TYPE_UINT,
		'pagenumber' => TYPE_UINT,
		'days'     => TYPE_UINT,
		'day'  => TYPE_ARRAY,
		'username'    => TYPE_NOHTML,
	));
	
	if ($vbulletin->GPC['perpage'] < 1)
	{
		$vbulletin->GPC['perpage'] = PERPAGE_DEFAULT;
	}
	
	$sqlcond = '';
	if ($vbulletin->GPC['username']) {
		$usernames = explode(',',$vbulletin->GPC['username']);
		$usernames_query = '';
		$userids = array();
		foreach ($usernames as $username) {
			$username = trim($username);
			if ($username) {
				if (strtolower($username) == strtolower($vbphrase['kbank'])) {
					$userids[] = 0;
				} else {
					if (!$usernames_query) {
						$usernames_query .= "'$username'";
					} else {
						$usernames_query .= ",'$username'";
					}
				}
			}
		}
		if ($usernames_query) {
			$users = $vbulletin->db->query_read("SELECT userid
				FROM `" . TABLE_PREFIX . "user`
				WHERE username IN ($usernames_query)");
			while ($user = $vbulletin->db->fetch_array($users)) {
				$userids[] = $user['userid'];
			}
		}
		if (count($userids) == 0) {
			print_stop_message('setting_validation_error_rpuserid');
		}
		if (count($userids) > 1) {
			$userids_query = implode(',',$userids);
			$sqlcond .= " AND (
					`from` IN ($userids_query)
					AND
					`to` IN ($userids_query)
				) ";
		} else {
			$userids_query = implode(',',$userids);
			$sqlcond .= " AND (`from` = $userids_query OR `to` = $userids_query) ";
		}
	}	
	if ($vbulletin->GPC['days'] == 1 && count($vbulletin->GPC['day']) == 3) {
		$day = $vbulletin->GPC['day'];
		$sqlcond .= "AND FROM_UNIXTIME(time,'%e') = $day[day]
			AND FROM_UNIXTIME(time,'%m') = $day[month]
			AND FROM_UNIXTIME(time,'%Y') = $day[year] ";
	} else if ($vbulletin->GPC['days'] != 0) {
		$sqlcond .="AND " . TIMENOW . " - time < " . $vbulletin->GPC['days'] . "*24*60*60 ";
	}
	if ($sqlcond != '') {
		$sqlcond = 'WHERE 1=1 ' . $sqlcond;
	}
		
	$counter = $db->query_first("
		SELECT COUNT(*) AS total
		FROM " . TABLE_PREFIX . "kbank_donations AS donations
		$sqlcond
	");
	$totalpages = ceil($counter['total'] / $vbulletin->GPC['perpage']);
	
	if ($vbulletin->GPC['pagenumber'] < 1)
	{
		$vbulletin->GPC['pagenumber'] = 1;
	}
	$startat = ($vbulletin->GPC['pagenumber'] - 1) * $vbulletin->GPC['perpage'];
	
	$recs = $db->query_read("
		SELECT *
		FROM `" . TABLE_PREFIX . "kbank_donations`
		$sqlcond
		ORDER BY time DESC
		LIMIT $startat, " . $vbulletin->GPC['perpage'] . "
	");
		
	if ($db->num_rows($recs)) {
		if ($vbulletin->GPC['pagenumber'] != 1)
		{
			$prv = $vbulletin->GPC['pagenumber'] - 1;
			$firstpage = "<input type=\"button\" class=\"button\" value=\"&laquo; " . $vbphrase['first_page'] . "\" tabindex=\"1\" onclick=\"window.location='kbankadmin.php?" . $vbulletin->session->vars['sessionurl'] . "do=do_view_records&username=" . $vbulletin->GPC['username'] . "&days=" . $vbulletin->GPC['days'] . "&day[day]=" . $vbulletin->GPC['day']['day'] . "&day[month]=" . $vbulletin->GPC['day']['month'] . "&day[year]=" . $vbulletin->GPC['day']['year'] . "&pp=" . $vbulletin->GPC['perpage'] . "&page=1'\">";
			$prevpage = "<input type=\"button\" class=\"button\" value=\"&lt; " . $vbphrase['prev_page'] . "\" tabindex=\"1\" onclick=\"window.location='kbankadmin.php?" . $vbulletin->session->vars['sessionurl'] . "do=do_view_records&username=" . $vbulletin->GPC['username'] . "&days=" . $vbulletin->GPC['days'] . "&day[day]=" . $vbulletin->GPC['day']['day'] . "&day[month]=" . $vbulletin->GPC['day']['month'] . "&day[year]=" . $vbulletin->GPC['day']['year'] . "&pp=" . $vbulletin->GPC['perpage'] . "&page=$prv'\">";
		}

		if ($vbulletin->GPC['pagenumber'] != $totalpages)
		{
			$nxt = $vbulletin->GPC['pagenumber'] + 1;
			$nextpage = "<input type=\"button\" class=\"button\" value=\"" . $vbphrase['next_page'] . " &gt;\" tabindex=\"1\" onclick=\"window.location='kbankadmin.php?" . $vbulletin->session->vars['sessionurl'] . "do=do_view_records&username=" . $vbulletin->GPC['username'] . "&days=" . $vbulletin->GPC['days'] . "&day[day]=" . $vbulletin->GPC['day']['day'] . "&day[month]=" . $vbulletin->GPC['day']['month'] . "&day[year]=" . $vbulletin->GPC['day']['year'] . "&pp=" . $vbulletin->GPC['perpage'] . "&page=$nxt'\">";
			$lastpage = "<input type=\"button\" class=\"button\" value=\"" . $vbphrase['last_page'] . " &raquo;\" tabindex=\"1\" onclick=\"window.location='kbankadmin.php?" . $vbulletin->session->vars['sessionurl'] . "do=do_view_records&username=" . $vbulletin->GPC['username'] . "&days=" . $vbulletin->GPC['days'] . "&day[day]=" . $vbulletin->GPC['day']['day'] . "&day[month]=" . $vbulletin->GPC['day']['month'] . "&day[year]=" . $vbulletin->GPC['day']['year'] . "&pp=" . $vbulletin->GPC['perpage'] . "&page=$totalpages'\">";
		}
		
		print_table_start();
		print_table_header("Viewing Records (Page {$vbulletin->GPC['pagenumber']} of $totalpages)",6);
		
		
		$headings = array();
		$headings[] = $vbphrase['id'];
		$headings[] = $vbphrase['kbank_from'];
		$headings[] = $vbphrase['kbank_to'];
		$headings[] = $vbphrase['kbank_amount'];
		$headings[] = $vbphrase['kbank_datetime'];
		$headings[] = $vbphrase['kbank_comment'];
		print_cells_row($headings, 1);
		
		while ($rec = $vbulletin->db->fetch_array($recs)) {
			$cell = array();
			
			showHistoryOne($rec,false,false);
			
			$cell[] = $rec['id'];
			$cell[] = $rec['from'];
			$cell[] = $rec['to'];
			$cell[] = $rec['amount'];
			$cell[] = $rec['time'];
			$cell[] = iif($rec['comment'] == '',$vbphrase['kbank_comment_none'],$rec['comment']);
			print_cells_row($cell);
		}
		
		print_table_footer(6, "$firstpage $prevpage &nbsp; $nextpage $lastpage");
	} else {
		print_stop_message('no_results_matched_your_query');
	}
	
	print_cp_footer();
}

// ###################### Ban member ########################
if ($_GET['do'] == "do_ban") {
	$processed = true;
	
	print_cp_header('Ban Member');
	
	$vbulletin->input->clean_array_gpc('r', array(
		'username'    => TYPE_NOHTML,
		'day'    => TYPE_UINT,
		'reason' => TYPE_STR
	));
	
	$reader = $vbulletin->db->query_first("SELECT userid
		FROM `" . TABLE_PREFIX . "user`
		WHERE username = '" . $vbulletin->GPC['username'] . "'");
	$userid = $reader['userid'];
	if ($userid == '') {
		print_stop_message('setting_validation_error_rpuserid');
	}
	
	
	if (userBanned($userid)) {
		define('CP_REDIRECT', 'kbankadmin.php?do=view_banned');
		print_stop_message('kbank_already_ban');
	}
	
	if ($vbulletin->GPC['day'] < 1) {
		print_stop_message('kbank_invalid_day');
	}
	
	if ($vbulletin->GPC['reason'] == '') {
		print_stop_message('kbank_invalid_reason');
	}
	
	$vbulletin->db->query_write("INSERT INTO `" . TABLE_PREFIX . "kbank_ban`
		(userid,`time`,days,reason,adminid)
		VALUES ($userid," . TIMENOW . "," . $vbulletin->GPC['day'] . ",'" . $vbulletin->GPC['reason'] . "'," . $vbulletin->userinfo['userid'] . ")");
		
	logkBankAction(
		'admin_ban',
		$userid,
		array(
			'userid' => $userid,
			'day' => $vbulletin->GPC['day']
		)
	);
		
	define('CP_REDIRECT', 'kbankadmin.php?do=view_banned');
	print_stop_message('kbank_banned');
}

// ###################### Un-Ban member ########################
if ($_GET['do'] == "un_ban") {
	$processed = true;
	
	print_cp_header('Un-Ban Member');
	
	$vbulletin->input->clean_array_gpc('r', array(
		'id'    => TYPE_UINT
	));
	
	$reader = $vbulletin->db->query_first("SELECT *
		FROM `" . TABLE_PREFIX . "kbank_ban`
		WHERE id = " . $vbulletin->GPC['id']);
		
	print_form_header('kbankadmin','do_un_ban');
	print_table_header($vbphrase['kbank_confirm_unban']);
	construct_hidden_code('id',$vbulletin->GPC['id']);
	print_label_row(construct_phrase($vbphrase['kbank_confirm_unban_mess'],getUsername($reader['userid']),getUsername($reader['adminid'])));
	print_submit_row($vbphrase['kbank_lift_ban']);
	print_table_footer();
	
	print_cp_footer();
}

// ###################### Do Un-Ban member ########################
if ($_GET['do'] == "do_un_ban") {
	$processed = true;
	
	print_cp_header('Un-Ban Member');
	
	$vbulletin->input->clean_array_gpc('p', array(
		'id'    => TYPE_UINT
	));
	
	$vbulletin->db->query_write("DELETE FROM `" . TABLE_PREFIX . "kbank_ban`
		WHERE id = " . $vbulletin->GPC['id']);
		
	logkBankAction(
		'admin_un_ban',
		$id
	);
		
	define('CP_REDIRECT', 'kbankadmin.php?do=view_banned');
	print_stop_message('kbank_unbanned');
}

// ###################### Ban member ########################
if ($_GET['do'] == "view_banned") {
	$processed = true;
	
	print_cp_header('View Banned Members');
	
	$members = $vbulletin->db->query_read("SELECT *
		FROM `" . TABLE_PREFIX . "kbank_ban`
		WHERE time + days*24*60*60 > " . TIMENOW . "
		ORDER BY time DESC");
		
	if ($db->num_rows($members)) {	
		print_form_header('kbankadmin', 'do_unban');
		print_table_header('Banned Members',6);
		
		$headings = array();
		$headings[] = $vbphrase['kbank_username'];
		$headings[] = $vbphrase['kbank_datetime'];
		$headings[] = $vbphrase['days'];
		$headings[] = $vbphrase['kbank_adminid'];
		$headings[] = $vbphrase['kbank_reason'];
		$headings[] = $vbphrase['controls'];
		print_cells_row($headings,1);
		
		while ($member = $vbulletin->db->fetch_array($members)) {
			$cell = array();
			$cell[] = getUsername($member['userid']);
			$cell[] = vbdate($vbulletin->options['dateformat'] . ' ' . $vbulletin->options['timeformat'],$member['time']);;
			$cell[] = $member['days'];
			$cell[] = getUsername($member['adminid']);
			$cell[] = $member['reason'];
			$cell[] = construct_link_code($vbphrase['kbank_lift_ban'],"kbankadmin.php?do=un_ban&id=$member[id]");
			print_cells_row($cell);
		}
		
		print_table_footer();
		
		print_cp_footer();
	} else {
		define('CP_REDIRECT', 'kbankadmin.php?do=bank_man');
		print_stop_message('kbank_no_banned');
	}
}

// ###################### View Log ########################
if ($_GET['do'] == "do_view_logs") {
	$processed = true;
	
	print_cp_header('View kBank Logs');
	
	$vbulletin->input->clean_array_gpc('r', array(
		'perpage'    => TYPE_UINT,
		'pagenumber' => TYPE_UINT,
		'type' => TYPE_NOHTML,
		'username' => TYPE_NOHTML,
		'userid' => TYPE_UINT
	));
	
	if ($vbulletin->GPC['perpage'] < 1)
	{
		$vbulletin->GPC['perpage'] = PERPAGE_DEFAULT;
	}
	
	$sqlcond = 'WHERE type = ' . KBANK_LOGTYPE_LOG;
	$suffix = '';
	switch($vbulletin->GPC['type']) {
		case 'member':
			$sqlcond .= ' AND text1 LIKE \'member_%\'';
			$suffix .= '&type=member';
			break;
		default:
			$sqlcond .= ' AND text1 LIKE \'admin_%\'';
			break;
	}
	if ($vbulletin->GPC['username']
		AND $user = $vbulletin->db->query_first("
			SELECT userid, username
			FROM `" . TABLE_PREFIX . "user`
			WHERE username = '{$vbulletin->GPC['username']}'")) {
		$sqlcond .= " AND userid = $user[userid]";
		$suffix .= "&userid=$user[userid]";
	} else if ($vbulletin->GPC['userid']) {
		$sqlcond .= " AND userid = $vbulletin->GPC['userid']";
		$suffix .= "&userid={$vbulletin->GPC['userid']}";
	}
		
	$counter = $db->query_first("
		SELECT COUNT(*) AS total
		FROM `" . TABLE_PREFIX . "kbank_logs`
		$sqlcond
	");
	$totalpages = ceil($counter['total'] / $vbulletin->GPC['perpage']);
	
	if ($vbulletin->GPC['pagenumber'] < 1)
	{
		$vbulletin->GPC['pagenumber'] = 1;
	}
	$startat = ($vbulletin->GPC['pagenumber'] - 1) * $vbulletin->GPC['perpage'];
	
	$recs = $db->query_read("
		SELECT *
		FROM `" . TABLE_PREFIX . "kbank_logs`
		$sqlcond
		ORDER BY timeline DESC
		LIMIT $startat, " . $vbulletin->GPC['perpage'] . "
	");
		
	if ($db->num_rows($recs)) {
		if ($vbulletin->GPC['pagenumber'] != 1)
		{
			$prv = $vbulletin->GPC['pagenumber'] - 1;
			$firstpage = "<input type=\"button\" class=\"button\" value=\"&laquo; " . $vbphrase['first_page'] . "\" tabindex=\"1\" onclick=\"window.location='kbankadmin.php?" . $vbulletin->session->vars['sessionurl'] . "do=do_view_logs$suffix&pp=" . $vbulletin->GPC['perpage'] . "&page=1'\">";
			$prevpage = "<input type=\"button\" class=\"button\" value=\"&lt; " . $vbphrase['prev_page'] . "\" tabindex=\"1\" onclick=\"window.location='kbankadmin.php?" . $vbulletin->session->vars['sessionurl'] . "do=do_view_logs$suffix&pp=" . $vbulletin->GPC['perpage'] . "&page=$prv'\">";
		}

		if ($vbulletin->GPC['pagenumber'] != $totalpages)
		{
			$nxt = $vbulletin->GPC['pagenumber'] + 1;
			$nextpage = "<input type=\"button\" class=\"button\" value=\"" . $vbphrase['next_page'] . " &gt;\" tabindex=\"1\" onclick=\"window.location='kbankadmin.php?" . $vbulletin->session->vars['sessionurl'] . "do=do_view_logs$suffix&pp=" . $vbulletin->GPC['perpage'] . "&page=$nxt'\">";
			$lastpage = "<input type=\"button\" class=\"button\" value=\"" . $vbphrase['last_page'] . " &raquo;\" tabindex=\"1\" onclick=\"window.location='kbankadmin.php?" . $vbulletin->session->vars['sessionurl'] . "do=do_view_logs$suffix&pp=" . $vbulletin->GPC['perpage'] . "&page=$totalpages'\">";
		}
		
		print_table_start();
		print_table_header("Viewing Logs (Page {$vbulletin->GPC['pagenumber']} of $totalpages)",5);
		
		
		$headings = array();
		$headings[] = $vbphrase['id'];
		$headings[] = $vbphrase['kbank_username'];
		$headings[] = $vbphrase['kbank_datetime'];
		$headings[] = $vbphrase['kbank_action'];
		$headings[] = $vbphrase['kbank_detail'];
		print_cells_row($headings, 1);
		
		while ($rec = $vbulletin->db->fetch_array($recs)) {
			$cell = array();
						
			$cell[] = $rec['id'];
			$cell[] = getUsername($rec['userid']);
			$cell[] = vbdate($vbulletin->options['dateformat'] . ' ' . $vbulletin->options['timeformat'],$rec['timeline']);
			$action = $detail = '';
			$action = $vbphrase["kbank_log_$rec[text1]"];
			$rec['detail'] = unserialize($rec['detail']);
			if (isset($vbphrase["kbank_log_$rec[text1]_int"])) {
				$detail = construct_phrase($vbphrase["kbank_log_$rec[text1]_int"],$rec['int1']);
			}
			if (isset($vbphrase["kbank_log_$rec[text1]_detail"])
				AND is_array($rec['detail'])) {
				$detail = call_user_func_array('construct_phrase', array_merge(array($vbphrase["kbank_log_$rec[text1]_detail"]),$rec['detail']));
			}
			$cell[] = $action;
			$cell[] = $detail?$detail:'-';
			print_cells_row($cell);
		}
		
		print_table_footer(6, "$firstpage $prevpage &nbsp; $nextpage $lastpage");
	} else {
		print_stop_message('no_results_matched_your_query');
	}
	
	print_cp_footer();
}

// ###################### Bank Management ########################
if ($_GET['do'] == 'salary') {
	$processed = true;
	
	print_cp_header("kBank Salary Center");
	
	$vbulletin->input->clean_array_gpc('p', array(
		'from' => TYPE_ARRAY,
		'to' => TYPE_ARRAY,
		'points' => TYPE_ARRAY
	));
	
	$points = array();
	if ($points_fromdb = $vbulletin->db->query_first("
		SELECT *
		FROM `" . TABLE_PREFIX . "datastore`
		WHERE title = 'kbank_salary_options'
	")) {
		$points = unserialize($points_fromdb['data']);
	}
	if ($vbulletin->GPC['points']) {
		if (count($points) > 0) {
			$found = true;
		} else {
			$found = false;
		}
		$changed = false;
		foreach ($vbulletin->GPC['points'] as $key => $val) {
			if ($points[$key] != $val) {
				$points[$key] = $vbulletin->input->do_clean($val,TYPE_UNUM);
				$changed = true;
			}
		}
		if ($changed) {
			$datastore_rec = array(
				'title' => 'kbank_salary_options'
				,'data' => serialize($points)
				,'unserialize' => 1
			);
			if ($found) {
				$vbulletin->db->query_write(fetch_query_sql($datastore_rec,'datastore',"WHERE title = 'kbank_salary_options'"));
			} else {
				$vbulletin->db->query_write(fetch_query_sql($datastore_rec,'datastore'));
			}
		}
	}
	
	if ($vbulletin->GPC['from'] AND $vbulletin->GPC['to']) {
		$vbulletin->GPC['from'] = vbmktime(
			$vbulletin->GPC['from']['hour']
			,$vbulletin->GPC['from']['minute']
			,0
			,$vbulletin->GPC['from']['month']
			,$vbulletin->GPC['from']['day']
			,$vbulletin->GPC['from']['year']);
		$vbulletin->GPC['to'] = vbmktime(
			$vbulletin->GPC['to']['hour']
			,$vbulletin->GPC['to']['minute']
			,0
			,$vbulletin->GPC['to']['month']
			,$vbulletin->GPC['to']['day']
			,$vbulletin->GPC['to']['year']);
	
		if ($vbulletin->GPC['from'] == $vbulletin->GPC['to']) {
			print_stop_message('kbank_salary_calc_samelog');
		}

		include_once(DIR . '/includes/functions_forumlist.php');
		cache_moderators();
		
		$mod_activity = array();
		foreach ($imodcache as $forumid => $forummods) {
			if ($forumid > 0) {
				foreach ($forummods as $mod) {
					$tmp =& $mod_activity[$mod['userid']];
					if (!is_array($tmp)) {
						$tmp = array(
							'userid' => $mod['userid']
							,'username' => $mod['username']
							,'forumids' => array()
							,'childs' => array()
						);
					}
					$tmp['forumids'][] = $forumid;
					$childs = explode(',',$vbulletin->forumcache[$forumid]['childlist']);
					foreach ($childs as $child) {
						if ($child > 0 //skip -1
							AND (!in_array($child,$tmp['childs']))
						) {
							$tmp['childs'][] = $child;
						}
					}
				}
			}
		}
		
		//Statistics
		if (count($mod_activity)) {
			$moduserids = implode(',',array_keys($mod_activity));
		
			//Reply & thread 
			$posts_fromdb = $vbulletin->db->query_read("
				SELECT
					thread.forumid AS forumid
					,IF(thread.firstpostid = post.postid,1,0) AS isfirstpost
					,SUM(CHAR_LENGTH(post.pagetext)) AS chars
					,COUNT(*) AS count
				FROM `" . TABLE_PREFIX . "post` AS post
				INNER JOIN `" . TABLE_PREFIX . "thread` AS thread ON (thread.threadid = post.threadid)
				WHERE post.dateline >= {$vbulletin->GPC['from']}
					AND post.dateline <= {$vbulletin->GPC['to']}
				GROUP BY forumid,isfirstpost
			");
			$forums = array();
			while ($post = $vbulletin->db->fetch_array($posts_fromdb)) {
				if ($post['isfirstpost']) {
					$forums[$post['forumid']]['thread'] += $post['count'];
					$forums['all']['thread'] += $post['count'];
				} else {
					$forums[$post['forumid']]['reply'] += $post['count'];
					$forums['all']['reply'] += $post['count'];
				}
				$forums[$post['forumid']]['chars'] += $post['chars'];
				$forums['all']['chars'] += $post['chars'];
			}
			$vbulletin->db->free_result($posts_fromdb);
			unset($post);
			
			foreach ($imodcache as $forumid => $forummods) {
				if ($forumid > 0
					AND isset($forums[$forumid])) {
					foreach (array_keys($mod_activity) AS $moduserid) {
						$tmp =& $mod_activity[$moduserid];
						if (in_array($forumid,$tmp['childs'])) {
							$tmp['forumsreply'] += $forums[$forumid]['reply'];
							$tmp['forumsthread'] += $forums[$forumid]['thread'];
							$tmp['forumschars'] += $forums[$forumid]['chars'];
							if (in_array($forumid,$tmp['forumids'])) {
								$tmp['forums'][$forumid]['forumid'] = $forumid;
								$tmp['forums'][$forumid]['reply'] = $forums[$forumid]['reply'];
								$tmp['forums'][$forumid]['thread'] = $forums[$forumid]['thread'];
								$tmp['forums'][$forumid]['chars'] += $forums[$forumid]['chars'];
							} else {
								$tmp['forumchilds']['reply'] += $forums[$forumid]['reply'];
								$tmp['forumchilds']['thread'] += $forums[$forumid]['thread'];
								$tmp['forumchilds']['chars'] += $forums[$forumid]['chars'];
							}
							if ($forums['all']['reply'] > 0) $tmp['forumsreplypercent'] = $tmp['forumsreply']/$forums['all']['reply']*100;
							if ($forums['all']['thread'] > 0) $tmp['forumsthreadpercent'] = $tmp['forumsthread']/$forums['all']['thread']*100;
						}
					}
				}
			}
		
			//Moderator logs
			$modlogs_fromdb = $vbulletin->db->query_read("
				SELECT userid, forumid, COUNT(*) AS count
				FROM `" . TABLE_PREFIX . "moderatorlog`
				WHERE dateline >= {$vbulletin->GPC['from']}
					AND dateline <= {$vbulletin->GPC['to']}
					AND userid IN ($moduserids)
				GROUP BY userid, forumid
			");
			while ($modlog = $vbulletin->db->fetch_array($modlogs_fromdb)) {
				if (in_array($modlog['forumid'],$mod_activity[$modlog['userid']]['childs'])) {
					$mod_activity[$modlog['userid']]['modlogscount'] += $modlog['count'];
					if (in_array($modlog['forumid'],$mod_activity[$modlog['userid']]['forumids'])) {
						$mod_activity[$modlog['userid']]['forums'][$modlog['forumid']]['forumid'] = $modlog['forumid'];
						$mod_activity[$modlog['userid']]['forums'][$modlog['forumid']]['modlogs'] = $modlog['count'];
					} else {
						$mod_activity[$modlog['userid']]['forumchilds']['modlogs'] += $modlog['count'];
					}
				}
			}
			$vbulletin->db->free_result($modlogs_fromdb);
			unset($modlog);
			
			//Posting
			$posts_fromdb = $vbulletin->db->query_read("
				SELECT
					post.userid AS userid
					,thread.forumid AS forumid
					,IF(thread.firstpostid = post.postid,1,0) AS isfirstpost
					,SUM(thread.replycount) AS replycount
					,SUM(thread.views) AS views
					,COUNT(*) AS count
				FROM `" . TABLE_PREFIX . "post` AS post
				INNER JOIN `" . TABLE_PREFIX . "thread` AS thread ON (thread.threadid = post.threadid)
				WHERE post.dateline >= {$vbulletin->GPC['from']}
					AND post.dateline <= {$vbulletin->GPC['to']}
					AND post.userid IN ($moduserids)
				GROUP BY userid,forumid,isfirstpost
			");
			while ($post = $vbulletin->db->fetch_array($posts_fromdb)) {
				$tmp =& $mod_activity[$post['userid']]['posting'];
				if (in_array($post['forumid'],$mod_activity[$post['userid']]['childs'])) {
					$key2 = 'childs';
				} else {
					$key2 = 'other';
				}
				if ($post['isfirstpost']) {
					$key = 'thread';
					$tmp[$key2]['threadreplycount'] += $post['replycount'];
					$tmp[$key2]['threadviews'] += $post['views'];
					$tmp['all']['threadreplycount'] += $post['replycount'];
					$tmp['all']['threadviews'] += $post['views'];
				} else {
					$key = 'reply';
				}
				$tmp[$key2][$key] += $post['count'];
				$tmp['all'][$key] += $post['count'];
			}
			$vbulletin->db->free_result($posts_fromdb);
			unset($post);
			
			//Thanking - sent
			$thanks_sent_fromdb = $vbulletin->db->query_read("
				SELECT
					thank.from AS userid
					,thread.forumid AS forumid
					,COUNT(*) AS count
					,SUM(thank.amount) AS total
				FROM `" . TABLE_PREFIX . "kbank_donations` AS thank
				INNER JOIN `" . TABLE_PREFIX . "post` AS post ON (post.postid = thank.postid)
				INNER JOIN `" . TABLE_PREFIX . "thread` AS thread ON (thread.threadid = post.threadid)
				WHERE thank.postid <> 0
					AND thank.from IN ($moduserids)
					AND thank.time >= {$vbulletin->GPC['from']}
					AND thank.time <= {$vbulletin->GPC['to']}
				GROUP BY userid,forumid
			");
			while ($thank_sent = $vbulletin->db->fetch_array($thanks_sent_fromdb)) {
				$tmp =& $mod_activity[$thank_sent['userid']]['thanks'];
				if (in_array($thank_sent['forumid'],$mod_activity[$thank_sent['userid']]['childs'])) {
					$key = 'childs';
				} else {
					$key = 'other';
				}
				$tmp[$key]['thanksent']['count'] += $thank_sent['count'];
				$tmp[$key]['thanksent']['total'] += $thank_sent['total'];
				$tmp['all']['thanksent']['count'] += $thank_sent['count'];
				$tmp['all']['thanksent']['total'] += $thank_sent['total'];
			}
			$vbulletin->db->free_result($thanks_sent_fromdb);
			unset($thank_sent);
			
			//Thanking - received
			$thanks_received_fromdb = $vbulletin->db->query_read("
				SELECT
					thank.to AS userid
					,thread.forumid AS forumid
					,COUNT(*) AS count
					,SUM(thank.amount) AS total
				FROM `" . TABLE_PREFIX . "kbank_donations` AS thank
				INNER JOIN `" . TABLE_PREFIX . "post` AS post ON (post.postid = thank.postid)
				INNER JOIN `" . TABLE_PREFIX . "thread` AS thread ON (thread.threadid = post.threadid)
				WHERE thank.postid <> 0
					AND thank.to IN ($moduserids)
					AND thank.time >= {$vbulletin->GPC['from']}
					AND thank.time <= {$vbulletin->GPC['to']}
				GROUP BY userid,forumid
			");
			while ($thank_received = $vbulletin->db->fetch_array($thanks_received_fromdb)) {
				$tmp =& $mod_activity[$thank_received['userid']]['thanks'];
				if (in_array($thank_received['forumid'],$mod_activity[$thank_received['userid']]['childs'])) {
					$key = 'childs';
				} else {
					$key = 'other';
				}
				$tmp[$key]['thankreceived']['count'] += $thank_received['count'];
				$tmp[$key]['thankreceived']['total'] += $thank_received['total'];
				$tmp['all']['thankreceived']['count'] += $thank_received['count'];
				$tmp['all']['thankreceived']['total'] += $thank_received['total'];
			}
			$vbulletin->db->free_result($thanks_received_fromdb);
			unset($thank_received);
			
			//Awarding
			$query_adminid = "SUBSTR(donate.comment,LOCATE('\"',donate.comment,19) + 1,LOCATE('\"',donate.comment,LOCATE('\"',donate.comment,19) + 1) - (LOCATE('\"',comment,19) + 1))";
			$awards_fromdb = $vbulletin->db->query_read("
				SELECT 
					$query_adminid AS userid
					,COUNT(*) AS count
					,SUM(donate.amount) AS total
				FROM `" . TABLE_PREFIX . "kbank_donations` AS donate
				WHERE donate.postid <> 0
					AND donate.from = 0
					AND donate.time >= {$vbulletin->GPC['from']}
					AND donate.time <= {$vbulletin->GPC['to']}
				GROUP BY userid,(donate.amount < 0)
			");
			while ($award = $vbulletin->db->fetch_array($awards_fromdb)) {
				if (isset($mod_activity[$award['userid']])) {
					$tmp =& $mod_activity[$award['userid']]['awards'];
					if ($award['total'] > 0) {
						$key = 'plus';
					} else {
						$key = 'subtract';
					}
					$tmp[$key]['count'] += $award['count'];
					$tmp[$key]['total'] += abs($award['total']);
					$tmp['all']['count'] += $award['count'];
					$tmp['all']['total'] += abs($award['total']);
				}
			}
			$vbulletin->db->free_result($awards_fromdb);
			unset($award);
		}
		
		//For nice viewing
		usort($mod_activity,'cmp_username');
		
		print_form_header('', '');
		print_table_header($vbphrase['moderators']);
		echo "<tr valign=\"top\">\n\t<td class=\"" . fetch_row_bgclass() . "\" colspan=\"2\">";
		echo "<div class=\"darkbg\" style=\"padding: 4px; border: 2px inset; text-align: $stylevar[left]\">";
		if (count($mod_activity)) {
			$countmods = 0;
			foreach ($mod_activity as $moderator) {
				if ($countmods++ != 0) {
					echo "\t\t</ul>\n\t\t</ul>\n\t</li>\n\t</ul>\n";
				}

				echo "\n\t<ul>\n\t<li><b><a href=\"user.php?" . $vbulletin->session->vars['sessionurl'] . "do=edit&amp;u=$moderator[userid]\">$moderator[username]</a></b>\n";
				
				echo "\n\t\t<ul>$vbphrase[forums]: \n" . 
					"\t\t" . vb_number_format($moderator['forumsreply']) . " post(s),\n" . 
					"\t\t " . vb_number_format($moderator['forumsthread']) . " thread(s),\n" . 
					"\t\t<span style=\"color:gray\">total " . vb_number_format($moderator['forumschars']) . " character(s)</span>,\n" . 
					"\t\t " . vb_number_format($moderator['modlogscount']) . " mod-action(s)\n" . 
					"\t<ul class=\"smallfont\">\n";

				//Showing post,thread,mod-action
				foreach ($moderator['forums'] as $info) {
					$str = iif($info['reply'],"\t\t\t\t" . vb_number_format($info['reply']) . " post(s),\n") . 
						iif($info['thread']
							,"\t\t\t\t" . vb_number_format($info['thread']) . " thread(s),\n") .
						iif($info['chars']
							,"\t\t\t\t<span style=\"color:gray\">total " . vb_number_format($info['chars']) . " character(s)</span>,\n") .
						iif($info['modlogs'],"\t\t\t\t" . vb_number_format($info['modlogs']) . " mod-action(s),\n");
					if (!$str) {
						$str = "\t\t\t\t-\n";
					} else {
						$str = substr($str,0,strlen($str)-2);
					}
					echo "\t\t\t<li><a href=\"../forumdisplay.php?" . $vbulletin->session->vars['sessionurl'] . "f=$info[forumid]\" target=\"_blank\">{$vbulletin->forumcache[$info[forumid]][title_clean]}</a>: \n".
						$str .
						"\t\t\t</li><br />\n";
				}
				//Showing post,thread,mod-action for childs
				if (isset($moderator['forumchilds'])) {
					$info =& $moderator['forumchilds'];
					$str = iif($info['reply'],"\t\t\t\t" . vb_number_format($info['reply']) . " post(s),\n") . 
						iif($info['thread']
							,"\t\t\t\t" . vb_number_format($info['thread']) . " thread(s),\n") .
						iif($info['chars']
							,"\t\t\t\t<span style=\"color:gray\">total " . vb_number_format($info['chars']) . " character(s)</span>,\n") .
						iif($info['modlogs'],"\t\t\t\t" . vb_number_format($info['modlogs']) . " mod-action(s),\n");
					if (!$str) {
						$str = "\t\t\t\t-\n";
					} else {
						$str = substr($str,0,strlen($str) - 2);
					}
					echo "\t\t\t<li>Childs: \n".
						$str .
						"\t\t\t</li><br />\n";
				}
				
				//Showing posting info
				echo "\t\t</ul>\n\t\t</ul>\n";
				echo "\n\t\t<ul>Posting: \n" . 
					"\t\t" . vb_number_format($moderator['posting']['all']['reply']) . " post(s),\n" . 
					"\t\t" . vb_number_format($moderator['posting']['all']['thread']) . " thread(s)\n" .
					iif($moderator['posting']['all']['thread'],"\t\t<span style=\"color:gray\">(" . vb_number_format($moderator['posting']['all']['threadreplycount']) . " reply(ies), " . vb_number_format($moderator['posting']['all']['threadviews']) . " view(s))</span>\n") .
					"\t<ul class=\"smallfont\">\n";
				if (count($moderator['posting'])) {
					ksort($moderator['posting']);
					
					foreach ($moderator['posting'] as $forumtype => $info) {
						if ($forumtype != 'all') {
							$str = iif($info['reply'],"\t\t\t\t" . vb_number_format($info['reply']) . " post(s),\n") . 
								iif($info['thread']
									,"\t\t\t\t" . vb_number_format($info['thread']) . " thread(s),\n" .
									"\t\t\t\t<span style=\"color:gray\">" . vb_number_format($info['threadreplycount']) . " reply(ies), " . vb_number_format($info['threadviews']) . " view(s)</span>,\n"
								);
							if (!$str) {
								$str = "\t\t\t\t-\n";
							} else {
								$str = substr($str,0,strlen($str) - 2);
							}
							echo "\t\t\t<li>" .
								iif($forumtype == 'childs','In moderating forum(s)','In others') . 
								": \n".
								$str .
								"\t\t\t</li><br />\n";
						}
					}
				}
				
				//Showing thanks
				if (isset($moderator['thanks'])) {
					ksort($moderator['thanks']);
					
					echo "\t\t</ul>\n\t\t</ul>\n";
					echo "\n\t\t<ul>Thanks: \n" . 
					"\t\tsent " . vb_number_format($moderator['thanks']['all']['thanksent']['count']) . " thank(s)\n" . 
					"\t\t<span style=\"color:gray\">(" . vb_number_format($moderator['thanks']['all']['thanksent']['total']) . " " . $vbulletin->kbank['name'] . ")</span>,\n" . 
					"\t\treceived " . vb_number_format($moderator['thanks']['all']['thankreceived']['count']) . " thank(s)\n" . 
					"\t\t<span style=\"color:gray\">(" . vb_number_format($moderator['thanks']['all']['thankreceived']['total']) . " " . $vbulletin->kbank['name'] . ")</span>\n" . 
					"\t<ul class=\"smallfont\">\n";
					foreach ($moderator['thanks'] as $forumtype => $info) {
						if ($forumtype != 'all') {
							$str = iif($info['thanksent']
								,"\t\t\t\tsent " . vb_number_format($info['thanksent']['count']) . " thank(s)\n" . 
								"\t\t\t\t<span style=\"color:gray\">(" . vb_number_format($info['thanksent']['total']) . " " . $vbulletin->kbank['name'] . ")</span>,\n") .
								iif($info['thankreceived']
								,"\t\t\t\treceived " . vb_number_format($info['thankreceived']['count']) . " thank(s)\n" . 
								"\t\t\t\t<span style=\"color:gray\">(" . vb_number_format($info['thankreceived']['total']) . " " . $vbulletin->kbank['name'] . ")</span>,\n");
							if (!$str) {
								$str = "\t\t\t\t-\n";
							} else {
								$str = substr($str,0,strlen($str) - 2);
							}
							echo "\t\t\t<li>" .
								iif($forumtype == 'childs','In moderating forum(s)','In others') . 
								": \n".
								$str .
								"\t\t\t</li><br />\n";
						}
					}
				}
				
				//Showing awards
				if (isset($moderator['awards'])) {
					echo "\t\t</ul>\n\t\t</ul>\n";
					echo "\n\t\t<ul>Awards: \n" . 
					"\t\t" . vb_number_format($moderator['awards']['all']['count']) . " time(s)\n" . 
					"\t\t<span style=\"color:gray\">(" . vb_number_format($moderator['awards']['all']['total']) . " " . $vbulletin->kbank['name'] . ")</span>\n" . 
					"\t<ul class=\"smallfont\">\n";
					foreach ($moderator['awards'] as $type => $info) {
						if ($type != 'all') {
							echo "\t\t\t<li>" .
								iif($type == 'plus','Give award','Take away') . 
								": \n".
								iif($info['count']
									,"\t\t\t\t" . vb_number_format($info['count']) . " time(s)\n" . 
									"\t\t\t\t<span style=\"color:gray\">(" . vb_number_format($info['total']) . " " . $vbulletin->kbank['name'] . ")</span>\n"
									,"\t\t\t\t-\n") .
								"\t\t\t</li><br />\n";
						}
					}
				}
			}
			echo "\t\t</ul>\n\t\t</ul>\n\t</li>\n\t</ul>\n";
		} else {
			echo $vbphrase['there_are_no_moderators'];
		}
		echo "</div>\n";
		echo "</td>\n</tr>\n";

		if (count($mod_activity)) {
			print_table_footer(1, $vbphrase['total'] . ": <b>" . count($mod_activity) . "</b>");
		} else {
			print_table_footer();
		}
		
		//Calculating points
		$modspoints = array();
		foreach (array_keys($mod_activity) as $key) {
			$mod =& $mod_activity[$key];
			$tmp =& $modspoints[$key];
			
			$tmp = array(
				'userid' => $mod['userid']
				,'username' => $mod['username']
			);
			
			//Points for forums' stuff
			$tmp['reply_new'] = $points['reply_new'] * $mod['forumsreply'];
			$tmp['thread_new'] = $points['thread_new'] * $mod['forumsthread'];
			if (($mod['forumsreply'] + $mod['forumsthread']) > 0) {
				$tmp['chars_new'] = $points['chars_new'] * $mod['forumschars']/($mod['forumsreply'] + $mod['forumsthread']);
			} else {
				$tmp['chars_new'] = 0;
			}
			$tmp['modlog'] = $points['modlog'] * $mod['modlogscount'];
			$tmp['forums'] = $tmp['reply_new'] + $tmp['thread_new'] + $tmp['chars_new'] + $tmp['modlog'];
			
			//Points for posting
			$tmp['reply_post'] = $points['reply_post'] * $mod['posting']['childs']['reply'];
			$tmp['thread_post'] = $points['thread_post'] * $mod['posting']['childs']['thread'];
			$tmp['threadreply_post'] = $points['threadreply_post'] * $mod['posting']['childs']['threadreplycount'];
			$tmp['threadview_post'] = $points['threadview_post'] * $mod['posting']['childs']['threadviews'];
			$tmp['reply_post_other'] = $points['reply_post_other'] * $mod['posting']['other']['reply'];
			$tmp['thread_post_other'] = $points['thread_post_other'] * $mod['posting']['other']['thread'];
			$tmp['threadreply_post_other'] = $points['threadreply_post_other'] * $mod['posting']['other']['threadreplycount'];
			$tmp['threadview_post_other'] = $points['threadview_post_other'] * $mod['posting']['other']['threadviews'];
			$tmp['posting'] = $tmp['reply_post'] + $tmp['thread_post'] + $tmp['threadreply_post'] + $tmp['threadview_post']
				+ $tmp['reply_post_other'] + $tmp['thread_post_other'] + $tmp['threadreply_post_other'] + $tmp['threadview_post_other'];
			
			//Point for thanks
			$tmp['thanksendtime'] = $points['thanksendtime'] * $mod['thanks']['childs']['thanksent']['count'];
			$tmp['thanksendamount'] = $points['thanksendamount'] * $mod['thanks']['childs']['thanksent']['total'];
			$tmp['thankreceivetime'] = $points['thankreceivetime'] * $mod['thanks']['childs']['thankreceived']['count'];
			$tmp['thankreceiveamount'] = $points['thankreceiveamount'] * $mod['thanks']['childs']['thankreceived']['total'];
			$tmp['thanksendtime_other'] = $points['thanksendtime_other'] * $mod['thanks']['other']['thanksent']['count'];
			$tmp['thanksendamount_other'] = $points['thanksendamount_other'] * $mod['thanks']['other']['thanksent']['total'];
			$tmp['thankreceivetime_other'] = $points['thankreceivetime_other'] * $mod['thanks']['other']['thankreceived']['count'];
			$tmp['thankreceiveamount_other'] = $points['thankreceiveamount_other'] * $mod['thanks']['other']['thankreceived']['total'];
			$tmp['thanks'] = $tmp['thanksendtime'] + $tmp['thanksendamount'] + $tmp['thankreceivetime'] + $tmp['thankreceiveamount']
				+ $tmp['thanksendtime_other'] + $tmp['thanksendamount_other'] + $tmp['thankreceivetime_other'] + $tmp['thankreceiveamount_other'];
			
			//Points for awards
			$tmp['awardtime'] = $points['awardtime'] * $mod['awards']['all']['count'];
			$tmp['awardamount'] = $points['awardamount'] * $mod['awards']['all']['total'];
			$tmp['awards'] = $tmp['awardtime'] + $tmp['awardamount'];
			
			$tmp['total'] = $tmp['forums'] + $tmp['posting'] + $tmp['thanks'] + $tmp['awards'];
		}
		usort($modspoints,'cmp_total');
		
		print_table_start();
		print_table_header("Points",6);		
		
		$headings = array();

		$headings[] = 'Username';
		$headings[] = 'Forums';
		$headings[] = 'Posting';
		$headings[] = 'Thanks';
		$headings[] = 'Awards';
		$headings[] = 'Total';
		print_cells_row($headings, 1);
		
		foreach ($modspoints as $modpoints) {			
			$cell = array();
			$cell[] = $modpoints['username'];
			$cell[] = 
				vb_number_format($modpoints['forums'],2)
				. iif(
					$modpoints['forums']
					, '<br/>'
					. '<span class="smallfont">'
					. iif($modpoints['reply_new'],'<span title="Point for new post">' . vb_number_format($modpoints['reply_new'],2) . '</span> ')
					. iif($modpoints['thread_new'],'<span title="Point for new thread">' . vb_number_format($modpoints['thread_new'],2) . '</span> ')
					. iif($modpoints['chars_new'],'<span title="Point for  Character/Post">' . vb_number_format($modpoints['chars_new'],2) . '</span> ')
					. iif($modpoints['modlog'],'<span title="Point for mod-action">' . vb_number_format($modpoints['modlog'],2) . '</span>')
					. '</span>'
				);			
			$cell[] = vb_number_format($modpoints['posting'],2)
				. iif(
					$modpoints['posting']
					, '<br/>'
					. '<span class="smallfont">'
					. iif($modpoints['reply_post'] + $modpoints['reply_post_other'],'<span title="Point for posting reply">' . vb_number_format($modpoints['reply_post'] + $modpoints['reply_post_other'],2) . '</span> ')
					. iif($modpoints['thread_post'] + $modpoints['threadreply_post'] + $modpoints['threadview_post'] + $modpoints['thread_post_other'] + $modpoints['threadreply_post_other'] + $modpoints['threadview_post_other']
						,'<span title="Point for posting thread">' . vb_number_format($modpoints['thread_post'] + $modpoints['threadreply_post'] + $modpoints['threadview_post'] + $modpoints['thread_post_other'] + $modpoints['threadreply_post_other'] + $modpoints['threadview_post_other'],2) . '</span>')
					. '</span>'
				);			
			$cell[] = vb_number_format($modpoints['thanks'],2)
				. iif(
					$modpoints['thanks']
					, '<br/>'
					. '<span class="smallfont">'
					. iif($modpoints['thanksendtime'] + $modpoints['thanksendamount'] + $modpoints['thanksendtime_other'] + $modpoints['thanksendamount_other']
						,'<span title="Point for sending thank">' . vb_number_format($modpoints['thanksendtime'] + $modpoints['thanksendamount'] + $modpoints['thanksendtime_other'] + $modpoints['thanksendamount_other'],2) . '</span> ')
					. iif($modpoints['thankreceivetime'] + $modpoints['thankreceiveamount'] + $modpoints['thankreceivetime_other'] + $modpoints['thankreceiveamount_other']
						,'<span title="Point for receiving thank">' . vb_number_format($modpoints['thankreceivetime'] + $modpoints['thankreceiveamount'] + $modpoints['thankreceivetime_other'] + $modpoints['thankreceiveamount_other'],2) . '</span> ')
					. '</span>'
				);
			$cell[] = vb_number_format($modpoints['awards'],2);
			$cell[] = vb_number_format($modpoints['total'],2);
			
			print_cells_row($cell);
		}
		
		print_table_footer(6);
	}
	
	print_form_header('kbankadmin','salary');
	construct_hidden_code('calculate',$vbphrase['kbank_banklogs_forum_stat_calculate']);
	print_table_header($vbphrase['kbank_banklogs_forum_stat_calculate']);
	print_time_row('Please select start time','from',iif(is_numeric($vbulletin->GPC['from']),intval($vbulletin->GPC['from']),TIMENOW - 30*24*60*60));
	print_time_row('Please select end time','to',iif(is_numeric($vbulletin->GPC['to']),intval($vbulletin->GPC['to']),TIMENOW));
	print_description_row('Point for forums\' stuff',0, 2, 'optiontitle');
	print_input_row('Point for 1 new post','points[reply_new]',$points['reply_new']);
	print_input_row('Point for 1 new thread','points[thread_new]',$points['thread_new']);
	print_input_row('Point for 1 character/post','points[chars_new]',$points['chars_new']);
	print_input_row('Point for 1 mod-action','points[modlog]',$points['modlog']);
	print_description_row('Point for posting',0, 2, 'optiontitle');
	print_input_row('Point for posting 1 post in moderating forums','points[reply_post]',$points['reply_post']);
	print_input_row('Point for posting 1 thread in moderating forums','points[thread_post]',$points['thread_post']);
	print_input_row('Point for 1 reply in thread posted in moderating forums','points[threadreply_post]',$points['threadreply_post']);
	print_input_row('Point for 1 view in thread posted in moderating forums','points[threadview_post]',$points['threadview_post']);
	print_input_row('Point for posting 1 post in other forums','points[reply_post_other]',$points['reply_post_other']);
	print_input_row('Point for posting 1 thread in other forums','points[thread_post_other]',$points['thread_post_other']);
	print_input_row('Point for 1 reply in thread posted in other forums','points[threadreply_post_other]',$points['threadreply_post_other']);
	print_input_row('Point for 1 view in thread posted in other forums','points[threadview_post_other]',$points['threadview_post_other']);
	print_description_row('Point for thanks',0, 2, 'optiontitle');
	print_input_row('Point for sending 1 thank in moderating forums','points[thanksendtime]',$points['thanksendtime']);
	print_input_row('Point for sending 1 ' . $vbulletin->kbank['name'] . ' in moderating forums','points[thanksendamount]',$points['thanksendamount']);
	print_input_row('Point for receiving 1 thank in moderating forums','points[thankreceivetime]',$points['thankreceivetime']);
	print_input_row('Point for receiving 1 ' . $vbulletin->kbank['name'] . ' in moderating forums','points[thankreceiveamount]',$points['thankreceiveamount']);
	print_input_row('Point for sending 1 thank in other forums','points[thanksendtime_other]',$points['thanksendtime_other']);
	print_input_row('Point for sending 1 ' . $vbulletin->kbank['name'] . ' in other forums','points[thanksendamount_other]',$points['thanksendamount_other']);
	print_input_row('Point for receiving 1 thank in other forums','points[thankreceivetime_other]',$points['thankreceivetime_other']);
	print_input_row('Point for receiving 1 ' . $vbulletin->kbank['name'] . ' in other forums','points[thankreceiveamount_other]',$points['thankreceiveamount_other']);
	print_description_row('Point for awards',0, 2, 'optiontitle');
	print_input_row('Point for award 1 time','points[awardtime]',$points['awardtime']);
	print_input_row('Point for award 1 ' . $vbulletin->kbank['name'],'points[awardamount]',$points['awardamount']);
	print_submit_row($vbphrase['kbank_banklogs_forum_stat_calculate']);
	print_table_footer();

	print_cp_footer();
}

// ###################### Donate To All Members ########################
if (!$processed) {
	$_GET['do'] = "donate_to_all";
}
if ($_GET['do'] == "donate_to_all") {
	$processed = true;

	print_cp_header("Donate To Members");

	$uoption="<option value='All'>All Usergroups</option>";
	$usergroups = $db->query("SELECT * FROM " . TABLE_PREFIX . "usergroup ORDER BY title");
	while ($usergroup = $db->fetch_array($usergroups)){
		$uoption.="<option value='{$usergroup['usergroupid']}'>{$usergroup['title']}</option>";
	}

	print_form_header('kbankadmin', 'do_donate_all');
	print_table_header("Donate To Members");

	print_input_row("Amount of {$vbphrase['money']} to donate", 'amount','0');
	print_label_row("Choose Usergroup<dfn>Will only donate to members within this usergroup</dfn>", '<select name="usergroup" class="bginput">'.$uoption.'</select>');

	print_submit_row("Donate {$vbphrase['money']} To Members", 0);
	
	//member
	print_form_header('kbankadmin', 'do_donate_member');
	print_table_header("Donate To Member");

	print_input_row("Amount of {$vbphrase['money']} to donate", 'amount2','0');
	print_input_row("Choose Username", 'username','');
	print_input_row("Comment", 'comment','');

	print_submit_row("Donate {$vbphrase['money']} To Member", 0);
	
	print_table_footer();
	
	print_cp_footer();
}
?>