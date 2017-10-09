<?php
/*
|| Multiple Account Detection & Prevention 1.1.3
|| ... a modification for vBulletin 3.7.x
|| Created by Kiros (Kiros72 on vBulletin.org)
|| ... with inspiration from MPDev and randominity on vBulletin.org
*/

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
$expire = (!empty($vbulletin->options['madp_cookie_expire']) AND is_numeric($vbulletin->options['madp_cookie_expire'])) ? (TIMENOW + ($vbulletin->options['madp_cookie_expire'] * 86400)) : (TIMENOW + 1209600);

$idcount = 0;
$usercount = 0;
$idquery = null;
$ignore = false;
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

global $vbphrase;
require_once(DIR . '/madp/functions_madp.php');

if (!empty($vbulletin->userinfo['userid']))
{
	if (!empty($_COOKIE["$cookie"]))
	{
		$stack = $_COOKIE["$cookie"];

		if (strpos($stack, ",{$vbulletin->userinfo['userid']},") === false)
		{
			// if it's a new detection
			$idstack = explode(',', $stack);

			$stack .= ",{$vbulletin->userinfo['userid']},";
			setcookie($cookie, $stack, $expire, $vbulletin->options['cookiepath'], $vbulletin->options['cookiedomain']);

			if (!in_array($vbulletin->userinfo['userid'], $ignore_users) AND !is_member_of($vbulletin->userinfo, $ignore_groups))
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

					$usercount++;
				}

				if (!$ignore AND $usercount > 0)
				{
					if ($vbulletin->options['madp_verbose_mode'] AND $usercount != $idcount)
					{
						$verbose_msgs += ERROR_ID_COUNT;
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

						$subject = construct_phrase($vbphrase['madp_login_subject'], $propername);
						$message = construct_phrase($vbphrase['madp_login_message'], $userlink, $details);

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

							$message .= $vbphrase['madp_verbose_start'];

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

							$message .= $break . $vbphrase['madp_verbose_caught_cookie'];

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
		}
		else if ($vbulltin->$options['madp_cookie_refresh'])
		{
			setcookie($cookie, $stack, $expire, $vbulletin->options['cookiepath'], $vbulletin->options['cookiedomain']);
		}
	}
	else
	{
		setcookie($cookie, ",{$vbulletin->userinfo['userid']},", $expire, $vbulletin->options['cookiepath'], $vbulletin->options['cookiedomain']);
	}
}
?>