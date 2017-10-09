<?php
/*
|| Multiple Account Detection & Prevention 1.1.3
|| ... a modification for vBulletin 3.7.x
|| Created by Kiros (Kiros72 on vBulletin.org)
|| ... with inspiration from MPDev and randominity on vBulletin.org
*/

$ignore_users = strpos($vbulletin->options['madp_ignore_users'], ' ') === false ? explode(',', $vbulletin->options['madp_ignore_users']) : explode(',', str_replace(' ', '', $vbulletin->options['madp_ignore_users']));
$ignore_groups = strpos($vbulletin->options['madp_ignore_groups'], ' ') === false ? explode(',', $vbulletin->options['madp_ignore_groups']) : explode(',', str_replace(' ', '', $vbulletin->options['madp_ignore_groups']));
$ignore_isps = strpos($vbulletin->options['madp_ignore_isps'], ' ') === false ? explode(',', strtolower($vbulletin->options['madp_ignore_isps'])) : explode(',', str_replace(' ', '', strtolower($vbulletin->options['madp_ignore_isps'])));
$banned_group = (!empty($vbulletin->options['madp_banned_group']) AND is_numeric($vbulletin->options['madp_banned_group'])) ? $vbulletin->options['madp_banned_group'] : 8;

// Banned Account Check
$bcheck = ($vbulletin->options['madp_prevention'] != 4 AND $vbulletin->options['madp_prevent_banned']) ? true : false;

$idquery = null;
$ignore = false;
$ignoreip = false;
$banned = false;

// 0: no detection,  1: caught by cookie,  2: caught by IP address,  3: both
$caughtNum = 0;

if (!empty($vbulletin->options['madp_cookie_name']) AND $vbulletin->options['madp_cookie_name'] != 'IDstack')
{
	$cookie = preg_replace(array('/[^A-Za-z0-9\-_]+/', '/' . COOKIE_PREFIX . '/'), '', $vbulletin->options['madp_cookie_name']); 
}

if (empty($cookie))
{
	$cookie = 'IDstack';
}

require_once(DIR . '/madp/functions_madp.php');

if (!empty($_COOKIE["$cookie"]))
{
	$stack = $_COOKIE["$cookie"];
	$idstack = explode(',', $stack);

	foreach ($idstack AS $uid)
	{
		if (!empty($uid) AND is_numeric($uid))
		{
			if (empty($idquery))
			{
				$idquery = "userid = {$uid}";
			}
			else
			{
				$idquery .= " OR userid = {$uid}";
			}
		}
	}

	$users = get_miniusers($idquery);

	while ($user = $vbulletin->db->fetch_array($users))
	{
		if ($vbulletin->options['madp_ignore_children'] AND (in_array($user['userid'], $ignore_users) OR is_member_of($user, $ignore_groups)))
		{
			$ignore = true;
			break;
		}

		if ($bcheck AND is_member_of($user, $banned_group))
		{
			$banned = true;
		}

		$caughtNum = 1;
	}

	if (!$ignore AND $vbulletin->options['madp_prevent_ip'] AND $vbulletin->options['madp_prevent_extend_ip'])
	{
		$resolved = @gethostbyaddr($_SERVER['REMOTE_ADDR']);

		if ($resolved != $_SERVER['REMOTE_ADDR'])
		{
			$resolved = strtolower($resolved);

			foreach ($ignore_isps AS $isp)
			{
				if (!empty($isp) AND strpos($resolved, $isp) !== false)
				{
					$ignoreip = true;
					break;
				}
			}
		}

		if (!$ignoreip)
		{
			$ipusers = get_ip_miniusers($_SERVER['REMOTE_ADDR'], 0);

			while ($user = $vbulletin->db->fetch_array($ipusers))
			{
				if (!in_array($user['userid'], $idstack) AND $user['userid'] != $vbulletin->options['madp_reporter']) // accounts not caught by cookie, not reporter ip
				{
					if ($vbulletin->options['madp_ignore_children'] AND (in_array($user['userid'], $ignore_users) OR is_member_of($user, $ignore_groups)))
					{
						$ignore = true;
						break;
					}

					if ($bcheck AND is_member_of($user, $banned_group))
					{
						$banned = true;
					}

					$caughtNum = 3;
				}
			}
		}
	}
}
else
{
	if ($vbulletin->options['madp_prevent_ip'])
	{
		$resolved = @gethostbyaddr($_SERVER['REMOTE_ADDR']);

		foreach ($ignore_isps AS $isp)
		{
			if (!empty($isp) AND strpos($resolved, $isp) !== false)
			{
				$ignoreip = true;
				break;
			}
		}

		if (!$ignoreip)
		{
			$ipusers = get_ip_miniusers($_SERVER['REMOTE_ADDR'], 0);

			while ($user = $vbulletin->db->fetch_array($ipusers))
			{
				if ($user['userid'] != $vbulletin->options['madp_reporter'])
				{
					if ($vbulletin->options['madp_ignore_children'] AND (in_array($user['userid'], $ignore_users) OR is_member_of($user, $ignore_groups)))
					{
						$ignore = true;
						break;
					}

					if ($bcheck AND is_member_of($user, $banned_group))
					{
						$banned = true;
					}
					
					$caughtNum = 2;
				}
			}
		}
	}
}

if (!$ignore AND $caughtNum > 0)
{
	// we caught multiple(s)
	if ($vbulletin->options['madp_prevention'] == 4 OR ($vbulletin->options['madp_prevention'] == 2 AND $banned) OR ($vbulletin->options['madp_prevention'] == 3 AND !$banned))
	{
		$userdata->error('madp_disallow');
	}
}
?>