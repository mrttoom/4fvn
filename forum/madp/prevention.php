<?php
/*
|| Multiple Account Detection & Prevention 1.1.3
|| ... a modification for vBulletin 3.7.x
|| Created by Kiros (Kiros72 on vBulletin.org)
|| ... with inspiration from MPDev and randominity on vBulletin.org
*/

// remember, prevention_disallow took care of anyone that was disallowed
// so we don't have to do any insane checks in here

// bits for verbose mode
define('ERROR_ID_COUNT', 			0x0001);
define('ERROR_REPORT_PM',     		0x0002);
define('ERROR_REPORT_THREAD',    	0x0004);
define('ERROR_USERGROUP_CACHE', 	0x0008);
define('MSG_IP_RESOLVE', 			0x0010);
define('MSG_BAD_CNAME',   			0x0020);

$verbose_msgs = 0;
$break = "\r\n\r\n";
$br = "\r\n";

$ignore_users = strpos($vbulletin->options['madp_ignore_users'], ' ') === false ? explode(',', $vbulletin->options['madp_ignore_users']) : explode(',', str_replace(' ', '', $vbulletin->options['madp_ignore_users']));
$ignore_groups = strpos($vbulletin->options['madp_ignore_groups'], ' ') === false ? explode(',', $vbulletin->options['madp_ignore_groups']) : explode(',', str_replace(' ', '', $vbulletin->options['madp_ignore_groups']));
$ignore_isps = strpos($vbulletin->options['madp_ignore_isps'], ' ') === false ? explode(',', strtolower($vbulletin->options['madp_ignore_isps'])) : explode(',', str_replace(' ', '', strtolower($vbulletin->options['madp_ignore_isps'])));
$expire = !empty($vbulletin->options['madp_cookie_expire']) ? (TIMENOW + ($vbulletin->options['madp_cookie_expire'] * 86400)) : (TIMENOW + 1209600);
$iptime = (!empty($vbulletin->options['madp_prevent_ip_time']) AND $vbulletin->options['madp_prevent_ip_time'] >= 0) ? ($vbulletin->options['madp_prevent_ip_time'] * 86400) : 1209600;
$prevent_group = !empty($vbulletin->options['madp_prevent_group']) ? $vbulletin->options['madp_prevent_group'] : 4;
$banned_group = !empty($vbulletin->options['madp_banned_group']) ? $vbulletin->options['madp_banned_group'] : 8;

// Banned Account Check
$bcheck = $vbulletin->options['madp_prevent_banned'] ? true : false;

$idcount = 0;
$usercount = 0;
$idquery = null;
$ignore = false;
$ignoreip = false;
$banned = false;

// 0: no detection,  1: caught by cookie,  2: caught by IP address,  3: both
$caughtNum = 0;
$caughtUsers = null;

if (!empty($vbulletin->options['madp_cookie_name']) AND $vbulletin->options['madp_cookie_name'] != 'IDstack')
{
	$cookie = preg_replace(array('/[^A-Za-z0-9\-_]+/', '/' . COOKIE_PREFIX . '/'), '', $vbulletin->options['madp_cookie_name']); 

	if ($vbulletin->options['madp_verbose_mode'] AND $cookie != $vbulletin->options['madp_cookie_name'])
	{
		$verbose_msgs += MSG_BAD_CNAME;
	}
}

if (empty($cookie))
{
	$cookie = 'IDstack';
}

require_once(DIR . '/madp/functions_madp.php');

if (!empty($vbulletin->userinfo['userid']))
{
	if (!empty($_COOKIE["$cookie"]))
	{
		$stack = $_COOKIE["$cookie"];
		$idstack = explode(',', $stack);

		$stack .= ",{$vbulletin->userinfo['userid']},";

		// new user, just check ignored groups
		if (!is_member_of($vbulletin->userinfo, $ignore_groups))
		{
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

					$idcount++;
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

				if ($vbulletin->options['madp_report_list'])
				{
					$caughtUsers .= '[*]';
				}

				if ($vbulletin->options['madp_report_url'])
				{
					$caughtUsers .= link_name_url($user['username'], $user['userid']) . $br;
				}
				else
				{
					$caughtUsers .= link_name_plain($user['username'], $user['userid']) . $br;
				}

				$caughtNum = 1;
				$usercount++;
			}

			if ($vbulletin->options['madp_verbose_mode'] AND $usercount != $idcount)
			{
				$verbose_msgs += ERROR_ID_COUNT;
			}
		}
		else
		{
			$ignore = true;
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
			else if ($vbulletin->options['madp_verbose_mode'])
			{
				$verbose_msgs += MSG_IP_RESOLVE;
			}

			if (!$ignoreip)
			{
				$ipusers = get_ip_miniusers($_SERVER['REMOTE_ADDR'], $vbulletin->userinfo['userid']);

				while ($user = $vbulletin->db->fetch_array($ipusers))
				{
					if (!in_array($user['userid'], $idstack) AND $user['userid'] != $vbulletin->options['madp_reporter']) // accounts not caught by cookie, not reporter ip
					{
						if ($iptime == 0 OR ($iptime > 0 AND TIMENOW - $user['joindate'] <= $iptime))
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

							if ($vbulletin->options['madp_report_list'])
							{
								$caughtUsers .= '[*]';
							}

							if ($vbulletin->options['madp_report_url'])
							{
								$caughtUsers .= link_name_url($user['username'], $user['userid']) . " " . $vbphrase['madp_prevent_ipmatch'] . $br;
							}
							else
							{
								$caughtUsers .= link_name_plain($user['username'], $user['userid']) . " " . $vbphrase['madp_prevent_ipmatch'] . $br;
							}

							$caughtNum = 3;
							$usercount++;
						}
					}
				}
			}
		}
	}
	else
	{
		$stack = ",{$vbulletin->userinfo['userid']},";

		if (!is_member_of($vbulletin->userinfo, $ignore_groups))
		{
			if ($vbulletin->options['madp_prevent_ip'])
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
				else if ($vbulletin->options['madp_verbose_mode'])
				{
					$verbose_msgs += MSG_IP_RESOLVE;
				}

				if (!$ignoreip)
				{
					$ipusers = get_ip_miniusers($_SERVER['REMOTE_ADDR'], $vbulletin->userinfo['userid']);

					while ($user = $vbulletin->db->fetch_array($ipusers))
					{
						if ($user['userid'] != $vbulletin->options['madp_reporter'])
						{
							if ($iptime == 0 OR ($iptime > 0 AND TIMENOW - $user['joindate'] <= $iptime))
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

								if ($vbulletin->options['madp_report_list'])
								{
									$caughtUsers .= '[*]';
								}

								if ($vbulletin->options['madp_report_url'])
								{
									$caughtUsers .= link_name_url($user['username'], $user['userid']) . $br;
								}
								else
								{
									$caughtUsers .= link_name_plain($user['username'], $user['userid']) . $br;
								}
								
								$caughtNum = 2;
							}
						}
					}
				}
			}
		}
		else
		{
			$ignore = true;
		}
	}

	// now that the registrant is a real user, set (or reset) the cookie
	setcookie($cookie, $stack, $expire, $vbulletin->options['cookiepath'], $vbulletin->options['cookiedomain']);

	if (!$ignore AND $caughtNum > 0)
	{
		$banned_set = isset($vbulletin->usergroupcache["{$banned_group}"]);
		$prevent_set = isset($vbulletin->usergroupcache["{$prevent_group}"]);

		if (!$vbulletin->options['madp_silent_mode'])
		{
			// we caught multiple(s)
			$user = $vbulletin->userinfo;

			$prevuserdm =& datamanager_init('User', $vbulletin, ERRTYPE_SILENT);
			$prevuserdm->set_existing($user);
			if ($banned AND $banned_set)
			{
				// insert into userban table for record-keeping
				$vbulletin->db->query_write("
					INSERT INTO " . TABLE_PREFIX . "userban
					(userid, usergroupid, displaygroupid, customtitle, usertitle, adminid, bandate, liftdate, reason)
					VALUES
					($user[userid], $user[usergroupid], $user[displaygroupid], $user[customtitle], '" . $vbulletin->db->escape_string($user['usertitle']) . "', " . $vbulletin->options['madp_reporter'] . ", " . TIMENOW . ", 0, '" . $vbulletin->db->escape_string($vbphrase['madp_reban_reason']) . "')
				");

				$prevuserdm->set('usergroupid', $banned_group);
				$prevuserdm->set('displaygroupid', 0);

				if ($vbulletin->usergroupcache["{$banned_group}"]['usertitle'] != '')
				{
					$prevuserdm->set('usertitle', $vbulletin->usergroupcache["{$banned_group}"]['usertitle']);
					$prevuserdm->set('customtitle', 0);
				}

				$peventmsg = $vbphrase['madp_message_rebanned'];
			}
			else if ($prevent_set)
			{
				$prevuserdm->set('usergroupid', $prevent_group);
				$prevuserdm->set('displaygroupid', 0);

				if ($vbulletin->usergroupcache["{$prevent_group}"]['usertitle'] != '')
				{
					$prevuserdm->set('usertitle', $vbulletin->usergroupcache["{$prevent_group}"]['usertitle']);
					$prevuserdm->set('customtitle', 0);
				}

				$peventmsg = $vbphrase['madp_message_prevented'];
			}
			else
			{
				$peventmsg = $vbphrase['madp_message_error'];
				$verbose_msgs += ERROR_USERGROUP_CACHE;
			}

			$prevuserdm->save();
			unset($prevuserdm);
		}
		else
		{
			$peventmsg = $vbphrase['madp_message_silent'];
		}

		if (!empty($vbulletin->options['madp_reporter']))
		{
			if ($vbulletin->options['madp_report_list'])
			{
				$details = '[LIST=1]' . $caughtUsers . '[/LIST]';
			}
			else
			{
				$details = $caughtUsers;
			}

			$propername = htmlspecialchars_uni($vbulletin->userinfo['username']);

			if ($vbulletin->options['madp_report_url'])
			{
				$userlink = link_name_url($propername, $vbulletin->userinfo['userid']);
			}
			else
			{
				$userlink = link_name_plain($propername, $vbulletin->userinfo['userid']);
			}

			if ($caughtNum == 1 OR $caughtNum == 3)
			{
				$subject = construct_phrase($vbphrase['madp_reg_subject'], $propername);
				$message = construct_phrase($vbphrase['madp_reg_message'], $userlink, $details, $peventmsg);
			}
			else
			{
				$subject = construct_phrase($vbphrase['madp_regip_subject'], $propername);
				$message = construct_phrase($vbphrase['madp_regip_message'], $userlink, $details, $peventmsg, $_SERVER['REMOTE_ADDR']);
			}

			$reporter = $vbulletin->db->fetch_array(get_name($vbulletin->options['madp_reporter']));

			$sendpm = (!empty($reporter) AND $vbulletin->options['madp_send_pm'] AND $vbulletin->options['madp_pm_recipients']);
			$postthread = (!empty($reporter) AND $vbulletin->options['madp_post_thread'] AND $vbulletin->options['madp_thread_forum']);

			if ($vbulletin->options['madp_verbose_mode'])
			{
				$vmsg = null;

				if (!$sendpm AND $vbulletin->options['madp_send_pm'])
				{
					$verbose_msgs += ERROR_REPORT_PM;
				}

				if (!$postthread AND $vbulletin->options['madp_post_thread'])
				{
					$verbose_msgs += ERROR_REPORT_THREAD;
				}

				$message .= $break;

				if ($vbulletin->options['madp_report_code'])
				{
					$message .= '[CODE]';
				}

				$message .= $vbphrase['madp_verbose_start'] . $break;

				switch($vbulletin->options['madp_prevention'])
				{
					case 0: $method = $vbphrase['madp_prevent_none'];
						break;

					case 1: $method = $vbphrase['madp_prevent_move'];
						break;

					case 2: $method = $vbphrase['madp_prevent_move_normals'];
						break;

					case 3: $method = $vbphrase['madp_prevent_move_banned'];
						break;

					case 4: $method = $vbphrase['madp_prevent_disallow'];
						break;
				}

				$banned_found = $banned_set ? $vbphrase['madp_found'] : $vbphrase['madp_notfound'];
				$prevent_found = $prevent_set ? $vbphrase['madp_found'] : $vbphrase['madp_notfound'];

				$message .= construct_phrase($vbphrase['madp_verbose_usergroups'], $method, $prevent_group, $prevent_found, $banned_group, $banned_found);

				$uids = null;

				if (!empty($idstack))
				{
					foreach ($idstack AS $uid)
					{
						if (!empty($uid) AND is_numeric($uid))
						{
							if (empty($uids))
							{
								$uids = $uid;
							}
							else
							{
								$uids .= ', ' . $uid;
							}
						}
					}
				}

				if (empty($_COOKIE["$cookie"]))
				{
					$message .= $break . construct_phrase($vbphrase['madp_verbose_cookie_bad'], $cookie);
				}
				else
				{
					$message .= $break . construct_phrase($vbphrase['madp_verbose_cookie_good'], $cookie, $_COOKIE["$cookie"], $uids);
				}

				switch ($caughtNum)
				{
					case 1:
						$message .= $break . $vbphrase['madp_verbose_caught_cookie'];
						break;

					case 2:
						$message .= $break . $vbphrase['madp_verbose_caught_ip'];
						break;

					case 3:
						$message .= $break . $vbphrase['madp_verbose_caught_cookieip'];
						break;

					default:
						$message .= $break . construct_phrase($vbphrase['madp_verbose_caught_error'], $caughtNum);
				}

				$message .= $break . $vbphrase['madp_verbose_messages'];

				if ($verbose_msgs & ERROR_ID_COUNT)
				{
					$vmsg .= $vbphrase['madp_verbose_msg_id'] . $br;
				}

				if ($verbose_msgs & ERROR_REPORT_PM)
				{
					$vmsg .= $vbphrase['madp_verbose_msg_pm'] . $br;
				}

				if ($verbose_msgs & ERROR_REPORT_THREAD)
				{
					$vmsg .= $vbphrase['madp_verbose_msg_thread'] . $br;
				}

				if ($verbose_msgs & ERROR_USERGROUP_CACHE)
				{
					$vmsg .= $vbphrase['madp_verbose_msg_cache'] . $br;
				}

				if ($verbose_msgs & MSG_IP_RESOLVE)
				{
					$vmsg .= $vbphrase['madp_verbose_msg_ip'] . $br;
				}

				if ($verbose_msgs & MSG_BAD_CNAME)
				{
					$vmsg .= $vbphrase['madp_verbose_msg_cname'] . $br;
				}

				if (!empty($vmsg))
				{
					$message .= $break . $vmsg . $br . construct_phrase($vbphrase['madp_verbose_bits'], decbin($verbose_msgs));;
				}
				else
				{
					$message .= $break . construct_phrase($vbphrase['madp_verbose_bits'], decbin($verbose_msgs));
				}

				if ($vbulletin->options['madp_report_code'])
				{
					$message .= '[/CODE]';
				}
			}

			if ($sendpm)
			{
				$blank = array();

				$pmdm =& datamanager_init('PM', $vbulletin, ERRTYPE_SILENT);
				$pmdm->set('fromuserid', $vbulletin->options['madp_reporter']);
				$pmdm->set('fromusername', $reporter['username']);
				$pmdm->set_recipients($vbulletin->options['madp_pm_recipients'], $blank);
				$pmdm->set_info('reciept', false);
				$pmdm->set_info('savecopy', false);
				$pmdm->set('title', $subject);
				$pmdm->set('message', $message);
				$pmdm->set('dateline', TIMENOW);
				$pmdm->set_info('is_automated', true);
				$pmdm->save();
				unset($pmdm);
			}

			if ($postthread)
			{
				// references
				$yes = true;
				$no = false;
				$zeroip = "0.0.0.0";

				$threaddm =& datamanager_init('Thread_FirstPost', $vbulletin, ERRTYPE_SILENT, 'threadpost');
				$threaddm->do_set('forumid', $vbulletin->options['madp_thread_forum']);
				$threaddm->do_set('userid', $vbulletin->options['madp_reporter']);
				$threaddm->do_set('username', $reporter['username']);
				$threaddm->do_set('title', $subject);
				$threaddm->do_set('pagetext', $message);
				$threaddm->do_set('allowsmilie', $no);
				$threaddm->do_set('visible', $yes);
				$threaddm->do_set('ipaddress', $zeroip);
				$threaddm->set_info('is_automated', true);
				$threaddm->save();
				unset($threaddm);

				require_once(DIR . '/includes/functions_databuild.php');
				build_forum_counters($vbulletin->options['madp_thread_forum']);
			}
		}
	}
}
?>