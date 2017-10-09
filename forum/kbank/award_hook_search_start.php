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

if ($_REQUEST['do'] == 'findawarded') {
	//find awarded post of [userid]
	$bbuserinfo = $vbulletin->userinfo;
	$vboptions = $vbulletin->options;

	$vbulletin->input->clean_array_gpc('r', array(
		'userid'	=> TYPE_UINT,
	));

	// valid user id?
	if (!$vbulletin->GPC['userid'])
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['user'], $vbulletin->options['contactuslink'])));
	}

	// get user info
	if ($user = $db->query_first("SELECT userid, username, posts FROM " . TABLE_PREFIX . "user WHERE userid = " . $vbulletin->GPC['userid']))
	{
		$searchuser =& $user['username'];
	}
	// could not find specified user
	else
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['user'], $vbulletin->options['contactuslink'])));
	}

	// #############################################################################
	// build search hash
	$query = '';
	$searchuser = $user['username'];
	$exactname = 1;
	$starteronly = 0;
	//$forumchoice = implode(',',fetch_search_forumids($foruminfo['forumid'],1));
	$forumchoice = 0; //This search do not support forumchoice!
	$childforums = 1;
	$titleonly = 0;
	$showposts = 1;
	$searchdate = 0;
	$beforeafter = 'after';
	$replyless = 0;
	$replylimit = 0;
	$searchthreadid = 0;

	$searchhash = md5(TIMENOW . "||" . $vbulletin->userinfo['userid'] . "||" . strtolower($searchuser) . "||$exactname||$starteronly||$forumchoice||$childforums||$titleonly||$showposts||$searchdate||$beforeafter||$replyless||$replylimit||$searchthreadid");

	$searchtime = microtime();

	// #############################################################################
	// query post ids in dateline DESC order...
	$orderedids = array();
	$posts = $db->query_read("
		SELECT award.postid AS postid
			,SUM(award.amount) AS points
		FROM `" . TABLE_PREFIX . "kbank_donations` AS award
		WHERE postid <> 0
			AND award.to = $user[userid]
			AND award.from = 0
		GROUP BY award.postid
		ORDER BY award.time DESC
		LIMIT " . ($vbulletin->options['maxresults'] * 2) . "
	");
	
	while ($post = $db->fetch_array($posts))
	{
		if ($post['points'] <> 0) {
			$orderedids[] = $post['postid'];
		}
	}
	unset($post);
	$db->free_result($posts);

	// did we get some results?
	if (empty($orderedids))
	{
		eval(standard_error(fetch_error('searchnoresults', $displayCommon), '', false));
	}

	// set display terms
	$display = array(
		'words' => array(),
		'highlight' => array(),
		'common' => array(),
		'users' => array($user['userid'] => $user['username']),
		'forums' => iif($display['forums'], $display['forums'], 0),
		'options' => array(
			'starteronly' => 0,
			'childforums' => 1,
			'action' => 'process'
			,'award_search_name' => $_REQUEST['do']
		)
	);

	// end search timer
	$searchtime = fetch_microtime_difference($searchtime);

	/*insert query*/
	$db->query_write("
		REPLACE INTO " . TABLE_PREFIX . "search (userid, ipaddress, personal, searchuser, forumchoice, sortby, sortorder, searchtime, showposts, orderedids, dateline, displayterms, searchhash)
		VALUES (" . $vbulletin->userinfo['userid'] . ", '" . $db->escape_string(IPADDRESS) . "', 1, '" . $db->escape_string($user['username']) . "', '" . $db->escape_string($forumchoice) . "', 'post.dateline', 'DESC', $searchtime, 1, '" . $db->escape_string(implode(',', $orderedids)) . "', " . TIMENOW . ", '" . $db->escape_string(serialize($display)) . "', '" . $db->escape_string($searchhash) . "')
	");
	$searchid = $db->insert_id();

	$vbulletin->url = "search.php?" . $vbulletin->session->vars['sessionurl'] . "searchid=$searchid";
	eval(print_standard_redirect('search'));

}

// ############################################################################
if ($_REQUEST['do'] == 'findawardedby') {
	//find awarded post given by [userid]
	$bbuserinfo = $vbulletin->userinfo;
	$vboptions = $vbulletin->options;

	$vbulletin->input->clean_array_gpc('r', array(
		'userid'	=> TYPE_UINT,
	));

	// valid user id?
	if (!$vbulletin->GPC['userid'])
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['user'], $vbulletin->options['contactuslink'])));
	}

	// get user info
	if ($user = $db->query_first("SELECT userid, username, posts FROM " . TABLE_PREFIX . "user WHERE userid = " . $vbulletin->GPC['userid']))
	{
		$searchuser =& $user['username'];
	}
	// could not find specified user
	else
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['user'], $vbulletin->options['contactuslink'])));
	}
	//require kBank Admin Permission
	if (!havePerm($vbulletin->userinfo,KBANK_PERM_ADMIN)) {
		print_no_permission();
	}

	// #############################################################################
	// build search hash
	$query = '';
	$searchuser = $user['username'];
	$exactname = 1;
	$starteronly = 0;
	//$forumchoice = implode(',',fetch_search_forumids($foruminfo['forumid'],1));
	$forumchoice = 0; //This search do not support forumchoice!
	$childforums = 1;
	$titleonly = 0;
	$showposts = 1;
	$searchdate = 0;
	$beforeafter = 'after';
	$replyless = 0;
	$replylimit = 0;
	$searchthreadid = 0;

	$searchhash = md5(TIMENOW . "||" . $vbulletin->userinfo['userid'] . "||" . strtolower($searchuser) . "||$exactname||$starteronly||$forumchoice||$childforums||$titleonly||$showposts||$searchdate||$beforeafter||$replyless||$replylimit||$searchthreadid");

	$searchtime = microtime();

	// #############################################################################
	// query post ids in dateline DESC order...
	$orderedids = array();
	$posts = $db->query_read("
		SELECT award.postid AS postid
			,SUM(award.amount) AS points
		FROM `" . TABLE_PREFIX . "kbank_donations` AS award
		WHERE postid <> 0
			AND award.from = 0
			AND award.comment LIKE '%:\"$user[userid]\";%'
		GROUP BY award.postid
		ORDER BY award.time DESC
		LIMIT " . ($vbulletin->options['maxresults'] * 2) . "
	");
	
	while ($post = $db->fetch_array($posts))
	{
		if ($post['points'] <> 0) {
			$tmp = unserialize($post['comment']);
			if ($tmp['adminid'] == $user['userid']) {
				$orderedids[] = $post['postid'];
			}
		}
	}
	unset($post);
	$db->free_result($posts);

	// did we get some results?
	if (empty($orderedids))
	{
		eval(standard_error(fetch_error('searchnoresults', $displayCommon), '', false));
	}

	// set display terms
	$display = array(
		'words' => array(),
		'highlight' => array(),
		'common' => array(),
		'users' => array($user['userid'] => $user['username']),
		'forums' => iif($display['forums'], $display['forums'], 0),
		'options' => array(
			'starteronly' => 0,
			'childforums' => 1,
			'action' => 'process'
			,'award_search_name' => $_REQUEST['do']
		)
	);

	// end search timer
	$searchtime = fetch_microtime_difference($searchtime);

	/*insert query*/
	$db->query_write("
		REPLACE INTO " . TABLE_PREFIX . "search (userid, ipaddress, personal, searchuser, forumchoice, sortby, sortorder, searchtime, showposts, orderedids, dateline, displayterms, searchhash)
		VALUES (" . $vbulletin->userinfo['userid'] . ", '" . $db->escape_string(IPADDRESS) . "', 1, '" . $db->escape_string($user['username']) . "', '" . $db->escape_string($forumchoice) . "', 'post.dateline', 'DESC', $searchtime, 1, '" . $db->escape_string(implode(',', $orderedids)) . "', " . TIMENOW . ", '" . $db->escape_string(serialize($display)) . "', '" . $db->escape_string($searchhash) . "')
	");
	$searchid = $db->insert_id();

	$vbulletin->url = "search.php?" . $vbulletin->session->vars['sessionurl'] . "searchid=$searchid";
	eval(print_standard_redirect('search'));

}

// #############################################################################
if ($_REQUEST['do'] == 'findallawarded') {
	//find all awarded post
	$bbuserinfo = $vbulletin->userinfo;
	$vboptions = $vbulletin->options;

	// #############################################################################
	// build search hash
	$query = '';
	$searchuser = $user['username'];
	$exactname = 1;
	$starteronly = 0;
	$forumchoice = implode(',',fetch_search_forumids($foruminfo['forumid'],1)); //This one does!
	$childforums = 1;
	$titleonly = 0;
	$showposts = 1;
	$searchdate = 0;
	$beforeafter = 'after';
	$replyless = 0;
	$replylimit = 0;
	$searchthreadid = 0;

	$searchhash = md5(TIMENOW . "||" . $vbulletin->userinfo['userid'] . "||" . strtolower($searchuser) . "||$exactname||$starteronly||$forumchoice||$childforums||$titleonly||$showposts||$searchdate||$beforeafter||$replyless||$replylimit||$searchthreadid");

	$searchtime = microtime();

	// #############################################################################
	// query post ids in dateline DESC order...
	$orderedids = array();
	$posts = $db->query_read("
		SELECT award.postid AS postid
			,SUM(award.amount) AS points
		FROM `" . TABLE_PREFIX . "kbank_donations` AS award
		" . iif(
			$forumchoice,
			"INNER JOIN `" . TABLE_PREFIX . "post` AS post ON (post.postid = award.postid)
			INNER JOIN `" . TABLE_PREFIX . "thread` AS thread ON (thread.threadid = post.threadid)"
			)
		. "WHERE award.postid <> 0
			AND award.from = 0
		" . iif($forumchoice,"	AND thread.forumid IN ($forumchoice)") . "
		GROUP BY award.postid
		ORDER BY award.time DESC
		LIMIT " . ($vbulletin->options['maxresults'] * 2) . "
	");
	while ($post = $db->fetch_array($posts))
	{
		if ($post['points'] <> 0) {
			$orderedids[] = $post['postid'];
		}
	}
	unset($post);
	$db->free_result($posts);

	// did we get some results?
	if (empty($orderedids))
	{
		eval(standard_error(fetch_error('searchnoresults', $displayCommon), '', false));
	}

	// set display terms
	$display = array(
		'words' => array(),
		'highlight' => array(),
		'common' => array(),
		'forums' => iif($display['forums'], $display['forums'], 0),
		'options' => array(
			'starteronly' => 0,
			'childforums' => 1,
			'action' => 'process'
			,'award_search_name' => $_REQUEST['do']
		)
	);

	// end search timer
	$searchtime = fetch_microtime_difference($searchtime);

	/*insert query*/
	$db->query_write("
		REPLACE INTO " . TABLE_PREFIX . "search (userid, ipaddress, personal, searchuser, forumchoice, sortby, sortorder, searchtime, showposts, orderedids, dateline, displayterms, searchhash)
		VALUES (" . $vbulletin->userinfo['userid'] . ", '" . $db->escape_string(IPADDRESS) . "', 1, '" . $db->escape_string($user['username']) . "', '" . $db->escape_string($forumchoice) . "', 'post.dateline', 'DESC', $searchtime, 1, '" . $db->escape_string(implode(',', $orderedids)) . "', " . TIMENOW . ", '" . $db->escape_string(serialize($display)) . "', '" . $db->escape_string($searchhash) . "')
	");
	$searchid = $db->insert_id();

	$vbulletin->url = "search.php?" . $vbulletin->session->vars['sessionurl'] . "searchid=$searchid";
	eval(print_standard_redirect('search'));

}

if ($_REQUEST['do'] == 'findthank') {
	//find sent thanks by [userid]
	$bbuserinfo = $vbulletin->userinfo;
	$vboptions = $vbulletin->options;

	$vbulletin->input->clean_array_gpc('r', array(
		'userid'	=> TYPE_UINT,
	));

	// valid user id?
	if (!$vbulletin->GPC['userid'])
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['user'], $vbulletin->options['contactuslink'])));
	}

	// get user info
	if ($user = $db->query_first("SELECT userid, username, posts FROM " . TABLE_PREFIX . "user WHERE userid = " . $vbulletin->GPC['userid']))
	{
		$searchuser =& $user['username'];
	}
	// could not find specified user
	else
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['user'], $vbulletin->options['contactuslink'])));
	}

	// #############################################################################
	// build search hash
	$query = '';
	$searchuser = $user['username'];
	$exactname = 1;
	$starteronly = 0;
	//$forumchoice = implode(',',fetch_search_forumids($foruminfo['forumid'],1));
	$forumchoice = 0; //This search do not support forumchoice!
	$childforums = 1;
	$titleonly = 0;
	$showposts = 1;
	$searchdate = 0;
	$beforeafter = 'after';
	$replyless = 0;
	$replylimit = 0;
	$searchthreadid = 0;

	$searchhash = md5(TIMENOW . "||" . $vbulletin->userinfo['userid'] . "||" . strtolower($searchuser) . "||$exactname||$starteronly||$forumchoice||$childforums||$titleonly||$showposts||$searchdate||$beforeafter||$replyless||$replylimit||$searchthreadid");

	$searchtime = microtime();

	// #############################################################################
	// query post ids in dateline DESC order...
	$orderedids = array();
	$posts = $db->query_read("
		SELECT award.postid AS postid
		FROM `" . TABLE_PREFIX . "kbank_donations` AS award
		WHERE award.postid <> 0
			AND award.from = $user[userid]
			AND award.to <> 0
		GROUP BY award.postid
		ORDER BY award.time DESC
		LIMIT " . ($vbulletin->options['maxresults'] * 2) . "
	");
	
	while ($post = $db->fetch_array($posts))
	{
		$orderedids[] = $post['postid'];
	}
	unset($post);
	$db->free_result($posts);

	// did we get some results?
	if (empty($orderedids))
	{
		eval(standard_error(fetch_error('searchnoresults', $displayCommon), '', false));
	}

	// set display terms
	$display = array(
		'words' => array(),
		'highlight' => array(),
		'common' => array(),
		'users' => array($user['userid'] => $user['username']),
		'forums' => iif($display['forums'], $display['forums'], 0),
		'options' => array(
			'starteronly' => 0,
			'childforums' => 1,
			'action' => 'process'
			,'award_search_name' => $_REQUEST['do']
		)
	);

	// end search timer
	$searchtime = fetch_microtime_difference($searchtime);

	/*insert query*/
	$db->query_write("
		REPLACE INTO " . TABLE_PREFIX . "search (userid, ipaddress, personal, searchuser, forumchoice, sortby, sortorder, searchtime, showposts, orderedids, dateline, displayterms, searchhash)
		VALUES (" . $vbulletin->userinfo['userid'] . ", '" . $db->escape_string(IPADDRESS) . "', 1, '" . $db->escape_string($user['username']) . "', '" . $db->escape_string($forumchoice) . "', 'post.dateline', 'DESC', $searchtime, 1, '" . $db->escape_string(implode(',', $orderedids)) . "', " . TIMENOW . ", '" . $db->escape_string(serialize($display)) . "', '" . $db->escape_string($searchhash) . "')
	");
	$searchid = $db->insert_id();

	$vbulletin->url = "search.php?" . $vbulletin->session->vars['sessionurl'] . "searchid=$searchid";
	eval(print_standard_redirect('search'));

}

if ($_REQUEST['do'] == 'findthanked') {
	//find received thanks of [userid]
	$bbuserinfo = $vbulletin->userinfo;
	$vboptions = $vbulletin->options;

	$vbulletin->input->clean_array_gpc('r', array(
		'userid'	=> TYPE_UINT,
	));

	// valid user id?
	if (!$vbulletin->GPC['userid'])
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['user'], $vbulletin->options['contactuslink'])));
	}

	// get user info
	if ($user = $db->query_first("SELECT userid, username, posts FROM " . TABLE_PREFIX . "user WHERE userid = " . $vbulletin->GPC['userid']))
	{
		$searchuser =& $user['username'];
	}
	// could not find specified user
	else
	{
		eval(standard_error(fetch_error('invalidid', $vbphrase['user'], $vbulletin->options['contactuslink'])));
	}

	// #############################################################################
	// build search hash
	$query = '';
	$searchuser = $user['username'];
	$exactname = 1;
	$starteronly = 0;
	//$forumchoice = implode(',',fetch_search_forumids($foruminfo['forumid'],1));
	$forumchoice = 0; //This search do not support forumchoice!
	$childforums = 1;
	$titleonly = 0;
	$showposts = 1;
	$searchdate = 0;
	$beforeafter = 'after';
	$replyless = 0;
	$replylimit = 0;
	$searchthreadid = 0;

	$searchhash = md5(TIMENOW . "||" . $vbulletin->userinfo['userid'] . "||" . strtolower($searchuser) . "||$exactname||$starteronly||$forumchoice||$childforums||$titleonly||$showposts||$searchdate||$beforeafter||$replyless||$replylimit||$searchthreadid");

	$searchtime = microtime();

	// #############################################################################
	// query post ids in dateline DESC order...
	$orderedids = array();
	$posts = $db->query_read("
		SELECT award.postid AS postid
		FROM `" . TABLE_PREFIX . "kbank_donations` AS award
		WHERE award.postid <> 0
			AND award.to = $user[userid]
			AND award.from <> 0
		GROUP BY award.postid
		ORDER BY award.time DESC
		LIMIT " . ($vbulletin->options['maxresults'] * 2) . "
	");
	
	while ($post = $db->fetch_array($posts))
	{
		$orderedids[] = $post['postid'];
	}
	unset($post);
	$db->free_result($posts);

	// did we get some results?
	if (empty($orderedids))
	{
		eval(standard_error(fetch_error('searchnoresults', $displayCommon), '', false));
	}

	// set display terms
	$display = array(
		'words' => array(),
		'highlight' => array(),
		'common' => array(),
		'users' => array($user['userid'] => $user['username']),
		'forums' => iif($display['forums'], $display['forums'], 0),
		'options' => array(
			'starteronly' => 0,
			'childforums' => 1,
			'action' => 'process'
			,'award_search_name' => $_REQUEST['do']
		)
	);

	// end search timer
	$searchtime = fetch_microtime_difference($searchtime);

	/*insert query*/
	$db->query_write("
		REPLACE INTO " . TABLE_PREFIX . "search (userid, ipaddress, personal, searchuser, forumchoice, sortby, sortorder, searchtime, showposts, orderedids, dateline, displayterms, searchhash)
		VALUES (" . $vbulletin->userinfo['userid'] . ", '" . $db->escape_string(IPADDRESS) . "', 1, '" . $db->escape_string($user['username']) . "', '" . $db->escape_string($forumchoice) . "', 'post.dateline', 'DESC', $searchtime, 1, '" . $db->escape_string(implode(',', $orderedids)) . "', " . TIMENOW . ", '" . $db->escape_string(serialize($display)) . "', '" . $db->escape_string($searchhash) . "')
	");
	$searchid = $db->insert_id();

	$vbulletin->url = "search.php?" . $vbulletin->session->vars['sessionurl'] . "searchid=$searchid";
	eval(print_standard_redirect('search'));

}

// #############################################################################
if ($_REQUEST['do'] == 'findallthanked') {
	//find all thanked post
	$bbuserinfo = $vbulletin->userinfo;
	$vboptions = $vbulletin->options;

	// #############################################################################
	// build search hash
	$query = '';
	$searchuser = $user['username'];
	$exactname = 1;
	$starteronly = 0;
	$forumchoice = implode(',',fetch_search_forumids($foruminfo['forumid'],1)); //This one does!
	$childforums = 1;
	$titleonly = 0;
	$showposts = 1;
	$searchdate = 0;
	$beforeafter = 'after';
	$replyless = 0;
	$replylimit = 0;
	$searchthreadid = 0;

	$searchhash = md5(TIMENOW . "||" . $vbulletin->userinfo['userid'] . "||" . strtolower($searchuser) . "||$exactname||$starteronly||$forumchoice||$childforums||$titleonly||$showposts||$searchdate||$beforeafter||$replyless||$replylimit||$searchthreadid");

	$searchtime = microtime();

	// #############################################################################
	// query post ids in dateline DESC order...
	$orderedids = array();
	$posts = $db->query_read("
		SELECT award.postid AS postid
		FROM `" . TABLE_PREFIX . "kbank_donations` AS award
		" . iif(
			$forumchoice,
			"INNER JOIN `" . TABLE_PREFIX . "post` AS post ON (post.postid = award.postid)
			INNER JOIN `" . TABLE_PREFIX . "thread` AS thread ON (thread.threadid = post.threadid)"
			)
		. "WHERE award.postid <> 0
			AND award.from <> 0
		" . iif($forumchoice,"	AND thread.forumid IN ($forumchoice)") . "
		GROUP BY award.postid
		ORDER BY award.time DESC
		LIMIT " . ($vbulletin->options['maxresults'] * 2) . "
	");
	while ($post = $db->fetch_array($posts))
	{
		$orderedids[] = $post['postid'];
	}
	unset($post);
	$db->free_result($posts);

	// did we get some results?
	if (empty($orderedids))
	{
		eval(standard_error(fetch_error('searchnoresults', $displayCommon), '', false));
	}

	// set display terms
	$display = array(
		'words' => array(),
		'highlight' => array(),
		'common' => array(),
		'forums' => iif($display['forums'], $display['forums'], 0),
		'options' => array(
			'starteronly' => 0,
			'childforums' => 1,
			'action' => 'process'
			,'award_search_name' => $_REQUEST['do']
		)
	);

	// end search timer
	$searchtime = fetch_microtime_difference($searchtime);

	/*insert query*/
	$db->query_write("
		REPLACE INTO " . TABLE_PREFIX . "search (userid, ipaddress, personal, searchuser, forumchoice, sortby, sortorder, searchtime, showposts, orderedids, dateline, displayterms, searchhash)
		VALUES (" . $vbulletin->userinfo['userid'] . ", '" . $db->escape_string(IPADDRESS) . "', 1, '" . $db->escape_string($user['username']) . "', '" . $db->escape_string($forumchoice) . "', 'post.dateline', 'DESC', $searchtime, 1, '" . $db->escape_string(implode(',', $orderedids)) . "', " . TIMENOW . ", '" . $db->escape_string(serialize($display)) . "', '" . $db->escape_string($searchhash) . "')
	");
	$searchid = $db->insert_id();

	$vbulletin->url = "search.php?" . $vbulletin->session->vars['sessionurl'] . "searchid=$searchid";
	eval(print_standard_redirect('search'));

}
?>