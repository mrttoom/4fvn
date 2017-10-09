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
include_once(DIR . '/kbank/functions.php');

function fetchAwarded($postids,$return = true,$force = false,$userid = false) {
	global $vbulletin,$awarded_cache,$need2cached_username;
	
	$postids_tmp = array();
	$postids_return = array();
	if (!isset($awarded_cache)) {
		$awarded_cache = array();
	}
	if (is_array($postids)) {
		//Is array > multiple ids
		foreach ($postids as $postid) {
			if (is_numeric($postid)
				AND $postid > 0) {
				if ($force
					OR !isset($awarded_cache[$postid])) {
					$postids_tmp[] = $postid;
					$awarded_cache[$postid] = array();
				}
				$postids_return[] = $postid;
			}
		}
	} else {
		//Can be a string or integer
		$postid = (int)$postids;
		if (is_numeric($postid)
			AND $postid > 0) {
			if ($force
				OR !isset($awarded_cache[$postid])) {
				$postids_tmp[] = $postid;
				$awarded_cache[$postid] = array();
			}
			$postids_return[] = $postid;
		}
	}
	
	if (count($postids_tmp)) {
		if ($vbulletin->kbank['award']['showDate']) {
			$date = ',  UNIX_TIMESTAMP(`thedate`) as `thedate`';
		}
		$cache = $vbulletin->db->query_read("
			SELECT 
				aw.id AS id
				,aw.postid AS postid
				,SUM(aw.amount) AS points
				,COUNT(*) AS count
				,IF(COUNT(*) > 1,GROUP_CONCAT(aw.comment),aw.comment) AS comment
				,aw.from AS userid
				,aw.to AS receiver
				" . iif($vbulletin->kbank['award']['showDate'],', aw.time AS time') . "
				" . iif($vbulletin->kbank['award']['showUsername'],', user.username AS username') . "
			FROM `" . TABLE_PREFIX . "kbank_donations` AS aw
			" . iif($vbulletin->kbank['award']['showUsername'],"LEFT JOIN `" . TABLE_PREFIX . "user` AS user
				ON (user.userid = aw.from)") . "
			WHERE aw.postid in (" . implode(',',$postids_tmp) . ")
				" . iif(is_numeric($userid),"AND aw.from = $userid") . "
			GROUP BY aw.postid,aw.from
			ORDER BY aw.time ASC;
		");
		
		DEVDEBUG('[kBank Award] fetchAwarded query the database');
		
		if (!$need2cached_username) {
			$need2cached_username = array(); //Prepair to cache username - Performance optimizing
		}
		while ($reader = $vbulletin->db->fetch_array($cache)) {
			if ($reader['points'] != 0) {
				if ($reader['userid'] == 0) {
					if ($reader['count'] == 1) {
						$tmp = unserialize($reader['comment']);
						$reader['reason'] = $tmp['comment'];
						$reader['adminid'] = $tmp['adminid'];
						$need2cached_username[] = $tmp['adminid'];
					} else {
						$reader['multi'] = array();
						$reader['reason'] = $reader['adminid'] = '-';
						$reader['time'] = NULL;
						
						@preg_match_all ('/a:2:{.*?}/',$reader['comment'],$tmp_list,PREG_SET_ORDER);
						if (is_array($tmp_list)) {
							foreach ($tmp_list as $list) {
								$tmp = unserialize($list[0]);
								$reader['multi'][] = $tmp;
								$need2cached_username[] = $tmp['adminid'];
							}
						}
					}
				} else {
					if ($reader['count'] == 1)
					{
						$reader['reason'] = $reader['comment'];
					}
					else
					{
						$tmp = explode(',',$reader['comment']);
						$reader['reason'] = $tmp[count($tmp) - 1]; //get the last comment to show up
					}
				}
				$awarded_cache[$reader['postid']][$reader['userid']] = $reader;
			}
		}
	}
	if ($return) {
		if (count($postids_return) > 1) {
			$data = array();
			foreach ($postids_tmp as $postid) {
				$data[$postid] = $awarded_cache[$postid];
			}
		} else {
			$data = $awarded_cache[$postids_return[0]];
		}
		
		return $data;
	}
}
			
function showAwardBox($postid,$force = false) {
	global $vbulletin, $vbphrase, $stylevar, $tmp_post, $postinfo;

	$records = fetchAwarded($postid,true,$force);
	if (!isset($tmp_post)) {
		$tmp_post = $postinfo;
	}
	if (!isset($tmp_post['musername'])) {
		$tmp_post['musername'] = $tmp_post['username'];
	}
	if (!count($records)) {
		return '';
	}
	
	$kbank_award_message = $kbank_award_message_member = array();
	
	foreach ($records as $data) {
		$message = '';
		
		$postid = $data['postid'];
		
		$canRemoveAwarded = $vbulletin->userinfo['canRemoveAwarded'];
		
		if ($data['points'] >= 0) {
			$color = $vbulletin->kbank['award']['showPlusColor'];
		} else {
			$color = $vbulletin->kbank['award']['showSubtractColor'];
		}
		$colorPre = "<span style=\"color:$color\">";
		$colorSuf = "</span>";
		//Parse smilies
		if ($vbulletin->kbank['award']['parseSmilies']
			AND isset($vbulletin->kbankBBCodeParser)) {
			$data['reason'] = $vbulletin->kbankBBCodeParser->parse_smilies($data['reason']);
		}
		$data['reason'] = $colorPre . $data[reason] . $colorSuf;
		
		if ($data['userid']) {
			if ($data['receiver'] == $tmp_post['userid']) {
				if (!$vbulletin->kbank['award']['showUsername']) {
					$message = construct_phrase(
						$vbphrase['kbank_award_the_message_thank']
						,$tmp_post['musername']
						,vb_number_format($data['points'],$vbulletin->kbank['roundup'])
						,$data['reason']
						,$vbulletin->kbank['name']);
				} else {
					if ($data['username']) {
						customize_userinfo_replaceUsername($data['username']);
						$username = "<a href=\"member.php?u=$data[userid]\">$data[username]</a>";
					} else {
						$username = $vbphrase['kbank_award_unknown'];
					}
					$message = construct_phrase(
						$vbphrase['kbank_award_the_message_thank_with_username']
						,$tmp_post['musername']
						,vb_number_format($data['points'],$vbulletin->kbank['roundup'])
						,$data['reason']
						,$username
						,$vbulletin->kbank['name']);		
				}
			}
		} else {
			if ($data['points'] != 0) {
				$data['points_str'] = $colorPre . iif($data['points'] > 0,'+') . vb_number_format($data['points'],$vbulletin->kbank['roundup']) . $colorSuf;
				if (count($data['multi'])) {
					$list = array();
					foreach ($data['multi'] as $rec) {
						if ($rec['comment'] != AWARD_REMOVE) {
							if ($vbulletin->kbank['award']['parseSmilies']
								AND isset($vbulletin->kbankBBCodeParser)) {
								$rec['comment'] = $vbulletin->kbankBBCodeParser->parse_smilies($rec['comment']);
							}
							$list[] = 
								iif(
									$vbulletin->kbank['award']['showUsername']
									,construct_phrase($vbphrase['kbank_award_the_message_multi_bit_with_username'],$rec['comment'],getUsername($rec['adminid']))
									,construct_phrase($vbphrase['kbank_award_the_message_multi_bit'],$rec['comment'])
							);
						} else {
							$list[] = 
								iif(
									$vbulletin->kbank['award']['showUsername']
									,construct_phrase($vbphrase['kbank_award_the_message_multi_bit_remove_with_username'],getUsername($rec['adminid']))
									,construct_phrase($vbphrase['kbank_award_the_message_multi_bit_remove'])
							);
						}
					}
					$list = implode('</li><li>',$list);
					$message = construct_phrase(
						$vbphrase['kbank_award_the_message_multi']
						,$tmp_post['musername']
						,$data['points_str']
						,$list
						,$vbulletin->kbank['name']);
				} else {
					if (!$vbulletin->kbank['award']['showUsername']) {
						$message = construct_phrase(
							$vbphrase['kbank_award_the_message']
							,$tmp_post['musername']
							,$data['points_str']
							,$data['reason']
							,$vbulletin->kbank['name']);
					} else { 
						$message = construct_phrase(
							$vbphrase['kbank_award_the_message_with_username']
							,$tmp_post['musername']
							,$data['points_str']
							,$data['reason']
							,getUsername($data['adminid'])
							,$vbulletin->kbank['name']);		
					}
				}
			}
		}

		if ($data['time'])
			$message .= ' '.construct_phrase($vbphrase['kbank_award_the_message_datetime'],vbdate($vbulletin->options['dateformat'] . ' ' . $vbulletin->options['timeformat'], $data['date']));

		if ($message) {
			if ($data['userid'] == 0) {
				$kbank_award_message[] = $message;
			} else {
				if ($vbulletin->userinfo['canRemoveAwarded'])
				{
					$message = '[<a href="showpost.php?p='. $postid . '&do=kbank_award_hide&id=' . $data['userid'] . '" id="kbank_award_hide_' . $postid . '_' . $data['userid'] .'" onclick="return kbank_award_link(' . $postid . ',\'kbank_award_hide_' . $postid . '_' . $data['userid'] .'\',this.href);">x</a>] ' . $message;
				}
			
				$kbank_award_message_member[] = $message;
			}
		}
	}
	$kbank_award_message = implode('<br/>',$kbank_award_message);
	$thankcount = count($kbank_award_message_member);
	$kbank_award_message_member = implode('<br/>',$kbank_award_message_member);
	
	if ($kbank_award_message
		OR $kbank_award_message_member) {

		eval('$kbank_award_message = " ' . fetch_template('kbank_award_message') . '";');	
		
		return $kbank_award_message;
	} else {
		return '';
	}
}
			
function ap_doHistory($postid,$points,$reason) {
	if (!is_numeric($postid) OR $postid <= 0) exit; //Error free!

	global $vbulletin, $vbphrase, $messages;
	$more_query = array(
		'to' => '',
		'from' => ''
	);
	
	//Get user's profile
	$user = $vbulletin->db->query_first("
		SELECT 
			post.userid AS userid, 
			user.username AS username,
			user.{$vbulletin->kbank['field']} AS total, 
			user.usergroupid AS usergroupid,
			user.membergroupids AS membergroupids,
			user.displaygroupid AS displaygroupid,
			user.usertitle AS usertitle,
			user.customtitle AS customtitle
		FROM `" . TABLE_PREFIX . "post` AS post
		INNER JOIN `" . TABLE_PREFIX . "user` AS user ON (user.userid = post.userid)
		WHERE postid = '$postid';");
		
	DEVDEBUG('[kBank Award] ap_doHistory query the database');

	//Check for new usergroup moving
	$newug = 0;
	foreach ($vbulletin->kbank['award']['listMove2GroupIDs'] as $rule) {
		$tmp = explode(':',$rule);
		$result_tmp = 12345;
		@eval('$result_tmp = (' . ($user['total'] + $points) . $tmp[0] . ');');
		if ($result_tmp !== 12345) {
			if ($result_tmp !== true
				AND $result_tmp !== false) {
				$result_tmp = 12345;
				@eval('$result_tmp = (' . ($user['total'] + $points) . '<' . $tmp[0] . ');');
				if ($result_tmp === 12345
					OR $result_tmp !== true
					OR $result_tmp !== false) {
					$result_tmp = false;
				}
			}
		} else {
			$result_tmp = false;
		}
		if ($result_tmp) {
			$newug = $tmp[1];
		}
	}
	if ($newug != 0
		AND !is_member_of($user,$vbulletin->kbank['award']['permCanRemoveGroupIDs'])
		AND !is_member_of($user,$newug)) {
		$more_query['to'] .= " ,usergroupid = $newug";
	} else {
		$newug = 0;
	}
	//Done with finding new usergroup
	
	//Add ban record if needed (if new group is a ban group)
	if ($newug) {
		//Find 'is ban group' groups
		$querygroups = array();
		foreach ($vbulletin->usergroupcache AS $usergroupid => $usergroup)
		{
			if (!($usergroup['genericoptions'] & $vbulletin->bf_ugp_genericoptions['isnotbannedgroup']))
			{
				$querygroups[] = $usergroupid;
			}
		}
		if(in_array($newug,$querygroups)) {
			$adminid = $vbulletin->userinfo['userid'];
			if (!$vbulletin->db->query_first("
					SELECT * FROM `" . TABLE_PREFIX . "userban`
					WHERE userid = $user[userid]
				")) {
				$vbulletin->db->query("
					INSERT INTO `" . TABLE_PREFIX . "userban`
					(userid, usergroupid, displaygroupid, usertitle, customtitle, adminid, bandate, liftdate, reason)
					VALUES (
						$user[userid]
						,$user[usergroupid]
						,$user[displaygroupid]
						,'$user[usertitle]'
						,$user[customtitle]
						,$adminid
						," . TIMENOW . "
						,0
						,'$vbphrase[kbank_award_bank_rupted]'
					);");
				DEVDEBUG('[kBank Award] ap_doHistory query the database 2 times');
			}
			$more_query['to'] .= " ,usertitle = '$vbphrase[kbank_award_bank_rupted]'";
		}
	}
	
	//Adjust counter
	$more_query['to'] .= 
		iif(
			$reason != AWARD_REMOVE
			//Awarding
			," ,{$vbulletin->kbank['award']['awardedtimes']} = {$vbulletin->kbank['award']['awardedtimes']} + 1"
			//Removing
			," ,{$vbulletin->kbank['award']['awardedtimes']} = {$vbulletin->kbank['award']['awardedtimes']} - 1"
		)
		. " ,{$vbulletin->kbank['award']['awardedamount']} = {$vbulletin->kbank['award']['awardedamount']} + $points";
	//Specified itemname for banklogs
	$more_query['banklogs'] = array(
		'itemname' => 'post'
	);
	
	//Update database
	$result = transferMoney(
		//sender userid
		0
		//receiver userid
		,$user['userid']
		//amount of money
		,$points
		//comment - support array
		,array(
			'adminid' => $vbulletin->userinfo['userid'],
			'comment' => $reason
		)
		//amount inhand - "null" to by pass validation
		,null
		//boolean value: log donation or not
		,true
		//boolean value: auto send pm or not
		,false
		//tax rate - "false" to use default donation tax
		,KBANK_NO_TAX
		//boolean value: output or just return error message
		,false
		//postid
		,$postid
		//queries to run - array('from','to','banklogs_itemname')
		,$more_query //we have a lot of work to do here!
	);
	
	//Send PM to user (if action done successfully)
	if ($result === true
		AND $vbulletin->kbank['award']['sendPM']) {
		//build title,message
		$url = $vbulletin->options['bburl'];
		if ($reason != AWARD_REMOVE) {
			$title = $vbphrase['kbank_award_PM_title'];
			$text = construct_phrase(
				$vbphrase['kbank_award_PM_text']
				,"$url/showthread.php?p=$postid"
				,$points
				,$reason
				,"$url/member.php?u={$vbulletin->userinfo['userid']}"
				,$vbulletin->userinfo['username']
				,$vbulletin->kbank['name']);
		} else {
			$title = $vbphrase['kbank_award_PM_title_deleted'];
			$text = construct_phrase($vbphrase['kbank_award_PM_text_deleted'],"$url/showthread.php?p=$postid","$url/member.php?u={$vbulletin->userinfo['userid']}",$vbulletin->userinfo['username']);
		}
		
		if ($newug != 0) {
			$text .= construct_phrase($vbphrase['kbank_award_usergroup_moved'],$newug);
			$messages[] = construct_phrase($vbphrase['kbank_award_usergroup_moved_for_admin'],$newug,$user['username']);
		}
		
		$result = kbank_sendPM($vbulletin->userinfo,$user,$title,$text,false);
	}
	
	return $result;
}

function fetchPerm($userinfo,$postinfo,$threadinfo,$force = false) {
	global $vbulletin,$vbphrase;

	$canAward = $canThank = true;
	
	if ($userinfo['userid'] == $postinfo['userid']
		OR !$vbulletin->kbank['award']['enabled']) {
		return false;
	}
	
	$records = fetchAwarded($postinfo['postid'],true,$force);	
	
	if (isset($records[$userinfo['userid']])
		AND !$vbulletin->kbank['award']['thank_multiple'])
	{
		//thank found and multiple thank is NOT allowed
		$canThank = false;
	}
	
	if (!can_moderate($threadinfo['forumid'])
		OR $postinfo['visible'] != 1) {
		$canAward = false;
	} else {		
		if (isset($records[0])
			AND $records[0]['points'] != 0) {
			$canAward = false;
		}
	}
	
	if (!$vbulletin->kbank['award']['award_enabled']) $canAward = false;
	if (!$vbulletin->kbank['award']['thank_enabled']) $canThank = false;
	
	
	return array(
		'award' => $canAward,
		'thank' => $canThank,
		'text' => iif($canAward,'award','') . iif($canThank,'thank',''),
		'phrase' => $vbphrase['kbank_award_button_' . iif($canAward,'award','') . iif($canThank,'thank','')]
	);
}

function ap_buildOptions($reasons,$other,$plusColor,$subtractColor,$plus,$subtract,$positive_only = false) {
	sort($reasons);
	$s = "";
	foreach ($reasons as $reason) {
		$part = explode(':',$reason);
		if (count($part) == 2) {
			$points = (int)$part[0];
			$reason = $part[1];
		} else {
			$reason = $part[0];
		}
		if (!is_numeric($points)) $points = 0;
		
		if ($points >= 0) {
			$color = $plusColor;
			$value = "+$points:$reason";
			$points = ($points == 0?"":" (+$points)");
			$reason = "$plus$reason$points";
		} else {
			if ($positive_only) continue;
			$color = $subtractColor;
			$value = "$points:$reason";
			$points = ($points == 0?"":" ($points)");
			$reason = "$subtract$reason$points";
		}
		if ($color)
			$style = "style=\"color: $color; font-weight: bold;\"";
		$s .= "<option value=\"$value\" $style>$reason</option>";
	}
	return "<SELECT name=\"reasons\" onchange=\"reasonsChanged();\" style=\"width: 100%;\"><option value=\"other\">$other</option>".$s."</SELECT>";
}

function ap_inLimit($points,$cur,$limit) {
	if ($limit = 0) {
		return (abs($points) + abs($cur) <= abs($limit))?true:false;
	} else {
		return true;
	}
}

function output($postid,$message = false) {
	global $vbulletin;
	if ($vbulletin->GPC['ajax']
		AND THIS_SCRIPT == 'showpost') {
		global $threadinfo,$postinfo,$stylevar;
		$box = showAwardBox($postinfo['postid'],true);
		$permAward = fetchPerm($vbulletin->userinfo,$postinfo,$threadinfo);
		$post['postid'] = $postinfo['postid'];
		$ajaxing = true; //variable used in template
		eval('$button = " ' . fetch_template('kbank_award_button') . '";');
		
		require_once(DIR . '/includes/class_xml.php');
		$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
		$xml->add_group('response');
		
		$xml->add_tag('product','kbank_award');
		$xml->add_tag('box',$box);
		$xml->add_tag('button',$button);
		if ($message) $xml->add_tag('message',$message);
		
		$xml->close_group();
		$xml->print_xml(true);
	}
	
	if (!$vbulletin->GPC['ajax']
		AND $message) {
		$vbulletin->url = "showthread.php?" . $vbulletin->session->vars['sessionurl'] ."p=$postid";
		eval(print_standard_redirect($message,false, true));
	}
	
	exit; //just in case...
}

function outputError($errors) {
	global $vbulletin;
	if (!is_array($errors)) {
		$errors = array($errors); //create array with 1 item
	}
	if ($vbulletin->GPC['ajax']) {		
		require_once(DIR . '/includes/class_xml.php');
		$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
		$xml->add_group('response');
		$html = '';
		
		$xml->add_tag('error',implode(',',$errors));
		
		$xml->close_group();
		$xml->print_xml(true);
	} else {
		 eval(standard_error(implode('<br/>',$errors)));
	}
	
	exit; //just in case...
}
?>