<?php

// ######################## SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE);

// ##################### DEFINE IMPORTANT CONSTANTS #######################
define('THIS_SCRIPT', 'banned');
define('CSRF_PROTECTION', false);
define('CSRF_SKIP_LIST', '');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array('banning', 'cpuser', 'cpglobal');

// get special data templates from the datastore
$specialtemplates = array();

// pre-cache templates used by all actions
$globaltemplates = array(
	'bannedusers',
	'bannedusers_bit'
);

// pre-cache templates used by specific actions
$actiontemplates = array();

// ########################## REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/functions_bannedusers_list.php');

if (empty($_REQUEST['do']) OR !in_array($_REQUEST['do'], array('perm', 'temp')))
{
	$_REQUEST['do'] = 'perm';
}

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################
// Permission to view?
if (!$vbulletin->userinfo['userid'] OR !($permissions['forumpermissions'] & $vbulletin->bf_ugp_forumpermissions['canview']))
{
	print_no_permission();
}

// #######################################################################
// Enabled?
if (!$vbulletin->options['bannedusers_enabled'])
{
	eval(standard_error(fetch_error('bannedusers_notenabled')));
}

$perpage = $vbulletin->input->clean_gpc('r', 'perpage', TYPE_UINT);
$pagenumber = $vbulletin->input->clean_gpc('r', 'pagenumber', TYPE_UINT);
$mode = $vbulletin->input->clean_gpc('r', 'do', TYPE_NOHTML);

$querygroups = array();

foreach ($vbulletin->usergroupcache AS $usergroupid => $usergroup)
{
	if (!($usergroup['genericoptions'] & $vbulletin->bf_ugp_genericoptions['isnotbannedgroup']))
	{
		$querygroups["$usergroupid"] = $usergroup['title'];
	}
}

// #######################################################################
// Banned member?
if (is_member_of($vbulletin->userinfo, implode(',', array_keys($querygroups))))
{
	print_no_permission();
}

// #######################################################################
// Are we looking for temp. banned or perm. banned?
switch ($mode)
{
	case 'temp':
		$andsql = 'userban.liftdate <> 0';
		$orderbysql = 'userban.liftdate ASC, user.username';
		break;
	case 'perm':
	default:
		$andsql = '(userban.liftdate = 0 OR userban.liftdate = NULL)';
		$orderbysql = 'user.username';
		break;
}

// #######################################################################
// Let's pull all info
$bannedcount = $db->query_first("
	SELECT COUNT(*) AS count
	FROM " . TABLE_PREFIX . "user AS user
	LEFT JOIN " . TABLE_PREFIX . "userban AS userban ON(userban.userid = user.userid)
	LEFT JOIN " . TABLE_PREFIX . "user AS adminuser ON(adminuser.userid = userban.adminid)
	WHERE user.usergroupid IN(" . implode(',', array_keys($querygroups)) . ")
		AND $andsql
");

if ($bannedcount['count'])
{
	// Begin pagination
	sanitize_pageresults($bannedcount['count'], $pagenumber, $perpage, 100, $vbulletin->options['bannedusers_perpage']);

	// Default lower and upper limit variables
	$limitlower = ($pagenumber - 1) * $perpage + 1;
	$limitupper = $pagenumber * $perpage;

	if ($limitupper > $bannedcount['count'])
	{
		// Too many for upper limit
		$limitupper = $bannedcount['count'];

		if ($limitlower > $bannedcount['count'])
		{
			// Too many for lower limit
			$limitlower = $bannedcount['count'] - $perpage;
		}
	}

	if ($limitlower <= 0)
	{
		// Can't have negative or null lower limit
		$limitlower = 1;
	}

	$bannedusers = $db->query_read("
		SELECT user.userid, user.username, user.usergroupid AS busergroupid,
			userban.usergroupid AS ousergroupid,
			IF(userban.displaygroupid = 0, userban.usergroupid, userban.displaygroupid) AS odisplaygroupid,
			bandate, liftdate, reason,
			adminuser.userid AS adminid, adminuser.username AS adminname
		FROM " . TABLE_PREFIX . "user AS user
		INNER JOIN " . TABLE_PREFIX . "userban AS userban ON(userban.userid = user.userid)
		LEFT JOIN " . TABLE_PREFIX . "user AS adminuser ON(adminuser.userid = userban.adminid)
		WHERE user.usergroupid IN(" . implode(',', array_keys($querygroups)) . ")
			AND $andsql
		ORDER BY $orderbysql
		LIMIT " . ($limitlower - 1) . ", $perpage
	");

	$counter = 0;
	$groups = implode(', ', $querygroups);

	while ($banneduser = $db->fetch_array($bannedusers) AND $counter++ < $perpage)
	{
		$baninfo = bannedusers_list_row($banneduser);
		eval('$bannedusers_bit .= "' . fetch_template('bannedusers_bit') . '";');
	}

	$db->free_result($bannedusers);

	$pagenav = construct_page_nav($pagenumber, $perpage, $bannedcount['count'], 'banned.php?' . $vbulletin->session->vars['sessionurl'] . "do=$mode");
}
else
{
	eval('$bannedusers_bit .= "' . fetch_template("bannedusers_bit_{$mode}none") . '";');
}

unset($bannedcount, $querygroups);

// #######################################################################
// And we're done, spit out the HTML
$navbits = array('' => $vbphrase['banned_users']);
$navbits = construct_navbits($navbits);
eval('$navbar = "' . fetch_template('navbar') . '";');
eval('print_output("' . fetch_template('bannedusers') . '");');

?>