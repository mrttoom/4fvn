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
include_once(DIR . '/kbank/award_functions.php');

if (defined('VB_AREA') 
	AND $vbulletin->kbank['award']['enabled']
	AND substr($_REQUEST['do'],0,11) == 'kbank_award') {

	//prepair some variables
	$postid = $postinfo['postid'];
	$forumid = $threadinfo['forumid'];
	
	if ($_REQUEST['do'] == 'kbank_award_buttonClicked') {
		$permAward = fetchPerm($vbulletin->userinfo,$postinfo,$threadinfo);
		
		$kbank_award_options = ap_buildOptions(
			$vbulletin->kbank['award']['listReasons']
			,$vbphrase['kbank_award_other']
			,$vbulletin->options['kbank_award_reason_plus']
			,$vbulletin->options['kbank_award_reason_subtract']
			,$vbphrase['kbank_award_plus']
			,$vbphrase['kbank_award_subtract']
			,iif($permAward['award'],false,true)
		);
		$awardActionName = $permAward['phrase'];

		eval('print_output("' . fetch_template('kbank_award_main') . '");');
		exit;
	}
	
	if ($_REQUEST['do'] == 'kbank_award_RemoveClicked') {
		//display remove awarded form
		if (!($postid > 0) || $postid == '' || !can_moderate($forumid) || !$vbulletin->userinfo['canRemoveAwarded']) {
			//outputError($vbphrase['kbank_award_mes_noperm']);
			print_no_permission();
		}
		eval('print_output("' . fetch_template('kbank_award_confirm') . '");');
	}
	
	if ($_REQUEST['do'] == 'kbank_award_add') {
		//do award/thank
		$vbulletin->input->clean_array_gpc('p', array(
			'userid'        => TYPE_INT,
			'points'        => TYPE_INT,
			'reason'        => TYPE_STR
		));
		$points = $vbulletin->GPC['points'];
		$reason = htmlspecialchars_uni(convert_urlencoded_unicode($vbulletin->GPC['reason'])); //decode reason

		$permAward = fetchPerm($vbulletin->userinfo,$postinfo,$threadinfo);
		
		if ($vbulletin->GPC['userid'] == 0) {
			//trying to award
			if ($permAward['award']) {
				//enough permission
				if (is_numeric($points)
					AND (
							($vbulletin->userinfo['minAward'] <= $points AND $points <= $vbulletin->userinfo['maxAward']) 
							OR ($vbulletin->userinfo['maxAward'] == 0)
						)
					) {
					//valid amount range
					if ($points != 0 && $reason != "") {
						//valid amount/reason value
						$cur = $vbulletin->db->query_first("
							SELECT SUM(ABS(amount)) AS total
							FROM `" . TABLE_PREFIX . "kbank_donations`
							WHERE (time + 24*60*60) >= " . TIMENOW . "
						");
						$cur = $cur['total'];
						if (ap_inLimit($points,$cur,$maxp_24h)) {
							//check for 24h limit
							$messages = array();
							$result = ap_doHistory($postid,$points,$reason); //do our job!
							if ($result === true) {
								//everything's ok
								$messages[] = $vbphrase['kbank_award_award_Done'];
								output($postid,implode('</br>',$messages));
							} else {
								//error!
								outputError($result . ' - Everything else is ok!');
							}
						} else {
							//24h limit exceeded
							outputError(construct_phrase($vbphrase['kbank_award_mes_limit'],$cur,$maxp_24h,$vbulletin->kbank['name']));
						}
					} else {
						//invalid amount/reason value
						outputError($vbphrase['kbank_award_mes_nulled']);
					}
				} else {
					//invalid amount range - display error
					if ($vbulletin->userinfo['maxAward'] != 0) {
						outputError(construct_phrase($vbphrase['kbank_award_mes_minmax'],$vbulletin->userinfo['minAward'],$vbulletin->userinfo['maxAward'],$vbulletin->kbank['name']));
					} else {
						outputError(construct_phrase($vbphrase['kbank_award_mes_minmax_unlimited'],$vbulletin->kbank['name']));
					}
				}
			} else {
				//no permission - display error
				//outputError($vbphrase['kbank_award_mes_noperm']);
				print_no_permission();
			}
		} else if ($vbulletin->GPC['userid'] == $vbulletin->userinfo['userid']) {
			//trying to thank
			if ($permAward['thank']) {
				//enough permission
				$messages = array();
				//try to donate
				$result = doDonateMoney(
					$vbulletin->userinfo
					,$postinfo
					,$points
					,$reason
					,$postid
					,array(
						'from' => 
							" ,{$vbulletin->kbank['award']['thanksenttimes']} = {$vbulletin->kbank['award']['thanksenttimes']} + 1"
							. " ,{$vbulletin->kbank['award']['thanksentamount']} = {$vbulletin->kbank['award']['thanksentamount']} + $points"
							. iif($vbulletin->kbank['award']['addpost']," ,posts = posts + 1")
						,'to' => 
							" ,{$vbulletin->kbank['award']['thankreceivedtimes']} = {$vbulletin->kbank['award']['thankreceivedtimes']} + 1"
							. " ,{$vbulletin->kbank['award']['thankreceivedamount']} = {$vbulletin->kbank['award']['thankreceivedamount']} + $points"
					));
				
				if ($result === true) {
					//everything's ok
					$messages[] = $vbphrase['kbank_award_thank_Done'];
					output($postid,implode('</br>',$messages));
				} else {
					//error!
					outputError($result);
				}
			} else {
				//no permission
				//outputError($vbphrase['kbank_award_mes_noperm']);
				print_no_permission();
			}
		} else {
			//WTF
			//outputError($vbphrase['kbank_award_mes_noperm']);
			print_no_permission();
		}
	}
	
	if ($_REQUEST['do'] == 'kbank_award_remove') {
		//do remove
		$vbulletin->input->clean_array_gpc('p', array(
			'confirm' 			=> TYPE_UINT,
		));
		$records = fetchAwarded($postinfo['postid'],true,false,0);
		
		if ($records[0]['points'] != 0 //this post has been awarded
			AND $vbulletin->userinfo['canRemoveAwarded'] //user has permission to remove
			AND $postinfo['userid'] != $vbulletin->userinfo['userid'] //it's not his/her owned post
		) {
			//permission is ok
			if ($vbulletin->GPC['confirm'] == $postinfo['postid']) {
				//check for confirmation
				$result = ap_doHistory($postinfo['postid'],(-1)*$records[0]['points'],AWARD_REMOVE); //trying to remove
				if ($result === true) {
					//remove complete
					$messages[] = $vbphrase['kbank_award_remove_Done'];
					output($postinfo['postid'],implode('</br>',$messages));
				} else {
					outputError($vbphrase['kbank_award_mes_cantRemove']);
				}
			} else {
				//invalid confirmation
				outputError($vbphrase['kbank_award_err_confirm']);
			}
		} else {
			//no permission
			//outputError($vbphrase['kbank_award_mes_noperm']);
			print_no_permission();
		}
	}
	
	if ($_REQUEST['do'] == 'kbank_award_hide') {
		//hide thank
		$vbulletin->input->clean_array_gpc('r', array(
			'id' 			=> TYPE_UINT, //userid
		));
		$userid = $vbulletin->GPC['id'];
		$records = fetchAwarded($postinfo['postid'],true,false,$userid);
		
		if (!($userid > 0))
		{
			//outputError($vbphrase['kbank_award_mes_noperm']);
			print_no_permission();
		}
		
		if ($records[$userid]['points'] != 0 //thank found
			AND $vbulletin->userinfo['canRemoveAwarded'] //user has permission to hide
		) {
			//permission is ok
			//trying to hide thank
			$vbulletin->db->query("
				UPDATE `" . TABLE_PREFIX . $vbulletin->kbank['donations'] . "`
				SET 
					#time = " . TIMENOW . "
					postid = 0
				WHERE postid = $postinfo[postid] AND `from` = $userid
			");
			
			$affected_rows = $vbulletin->db->affected_rows();
			if ($affected_rows > 0) {
				//hide complete					
				$vbulletin->db->query("
					UPDATE `" . TABLE_PREFIX . "user`
					SET 
						{$vbulletin->kbank['award']['thanksenttimes']} = {$vbulletin->kbank['award']['thanksenttimes']} - $affected_rows
						, {$vbulletin->kbank['award']['thanksentamount']} = {$vbulletin->kbank['award']['thanksentamount']} - {$records[$userid]['points']}
					WHERE userid = $userid
				");
				$vbulletin->db->query("
					UPDATE `" . TABLE_PREFIX . "user`
					SET 
						{$vbulletin->kbank['award']['thankreceivedtimes']} = {$vbulletin->kbank['award']['thankreceivedtimes']} - $affected_rows
						, {$vbulletin->kbank['award']['thankreceivedamount']} = {$vbulletin->kbank['award']['thankreceivedamount']} - {$records[$userid]['points']}
					WHERE userid = $postinfo[userid]
				");
				
				$messages[] = $vbphrase['kbank_award_hide_Done'];
				output($postinfo['postid'],implode('</br>',$messages));
			} else {
				outputError($vbphrase['kbank_award_mes_cantHide']);
			}
		} else {
			//no permission
			//outputError($vbphrase['kbank_award_mes_noperm']);
			print_no_permission();
		}
	}
}
?>