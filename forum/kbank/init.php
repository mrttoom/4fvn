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
if ($vbulletin->options['points']
	AND !in_array(THIS_SCRIPT,array('vbshout'))) {
	global $vbulletin;
	
	//load our variables
	$kbank = array(
		'enabled' => ($vbulletin->options['points'] == 1),
		'topEnabled' => true,
		'phpfile' => iif($vbulletin->options['kbank_phpfile'],$vbulletin->options['kbank_phpfile'],'kbank.php'),
		'name' => $vbulletin->options['kbankn'],
		'roundup' => max(0,$vbulletin->options['kbank_roundup']),
		'field' => ($vbulletin->options['kbankf']?$vbulletin->options['kbankf']:'money'),
		'perchar_default' => max(0,$vbulletin->options['kbank_perchar_default']),
		'perreply_default' => max(0,$vbulletin->options['kbank_perreply_default']),
		'perthread_default' => max(0,$vbulletin->options['kbank_perthread_default']),
		'AutoRemovePointWhenDelete' => ($vbulletin->options['pnwd'] == 1),
		'AutoRecalcPointWhenEdit' => ($vbulletin->options['pcwe'] == 1),
		'RegPoint1time' => $vbulletin->options['ppreg1time'],
		'RegPoint' => $vbulletin->options['ppregp'],
		'ReferPoint' => $vbulletin->options['ppreferp'],
		'maxDonate' => $vbulletin->options['kbank_max_donate'],
		'maxDonate24h' => $vbulletin->options['kbank_max_donate_24h'],
		'PMLimit' => $vbulletin->options['kbank_donate_limit_pm'],
		'minFee' => $vbulletin->options['kbank_fee_min'],
		'DonateTax' => $vbulletin->options['kbank_tax'],
		'PointAdjust' => $vbulletin->options['kbank_adjust_points'],
		'AdminIDs' => explode(',',$vbulletin->options['kbank_adminids']),
		'NormalGroupID' => max(0,$vbulletin->options['kbank_normal_member_groupid']),
		'BankRuptGroupID' => max(0,$vbulletin->options['kbank_bankrupt_groupid']),
		'MemberGroupIDs' => explode(',',$vbulletin->options['kbank_member_groupids']),
		'CompanyGroupIDs' => explode(',',$vbulletin->options['kbank_company_groupids']),
		'BankGroupIDs' => explode(',',$vbulletin->options['kbank_bank_groupids']),
		'useMonthlyTax' => ($vbulletin->options['kbank_member_monthly_tax_hard'] > 0),
		'MonthlyTaxDays' => 30,
		'MonthlyTaxHard' => $vbulletin->options['kbank_member_monthly_tax_hard'],
		'MonthlyTaxSoftStep' => $vbulletin->options['kbank_member_monthly_tax_soft_step'],
		'MonthlyTaxSoftIncludeCompany' => true,
		'MonthlyTaxReminder' => $vbulletin->options['kbank_member_monthly_tax_reminder_before'],
		'bankruptAnnounceThreadID' => $vbulletin->options['kbank_member_monthly_tax_announce_threadid'],
		//Item
		'itemEnabled' => ($vbulletin->options['points'] == 1) AND ($vbulletin->options['kbank_item_on_off'] == 1),
		'announceEnabled' => ($vbulletin->options['points'] == 1) AND ($vbulletin->options['kbank_item_on_off'] == 1) AND ($vbulletin->options['kbank_announce_max'] > 0),
		'maxAnnounce' => $vbulletin->options['kbank_announce_max'],
		'AnnounceAfter' => $vbulletin->options['kbank_announce_after'],
		'AnnounceTemplateOverwrite' => $vbulletin->options['kbank_announce_overwrite'],
		'ItemTax' => $vbulletin->options['kbank_item_tax'],
		'ItemBidFee' => max(0,$vbulletin->options['kbank_item_bid_fee']),
		'ItemDurationStep' => max(1,$vbulletin->options['kbank_item_duration_step']),
		'maxItemDurationStep' => $vbulletin->options['kbank_item_duration_step_max'],
		'maxItemPriceRate' => $vbulletin->options['kbank_item_price_max'],
		'requestApproval' => max(0,$vbulletin->options['kbank_item_add_approval_count']),
		'maxLastBids' => -1,
		'BidWinnerBuyAfter' => iif($vbulletin->options['kbank_item_bid_winner_buy_hours'],$vbulletin->options['kbank_item_bid_winner_buy_hours']*60*60,false),
		'bidStep' => max(10,$vbulletin->options['kbank_item_bid_step']),
		//Award
		'award' => array(
			'enabled' => (
				($vbulletin->options['points'] == 1) 
				AND (
					$vbulletin->options['kbank_award_on_off'] == 1 
					OR $vbulletin->options['kbank_award_thank_on_off']
					)
				),
			'award_enabled' => ($vbulletin->options['kbank_award_on_off'] == 1),
			'thank_enabled' => ($vbulletin->options['kbank_award_thank_on_off'] == 1),
			'thank_multiple' => ($vbulletin->options['kbank_award_thank_multiple'] == 1),
			'showDate' => ($vbulletin->options['kbank_award_date_on_off'] == 1),
			'showUsername' => ($vbulletin->options['kbank_award_username_on_off'] == 1),
			'showPlusColor' => $vbulletin->options['kbank_award_reason_plus'],
			'showSubtractColor' => $vbulletin->options['kbank_award_reason_subtract'],
			'sendPM' => ($vbulletin->options['kbank_award_PM_on_off'] == 1),
			'parseSmilies' => ($vbulletin->options['kbank_award_reason_smilies'] == 1),
			
			'listReasons' => explode("\r\n",$vbulletin->options['kbank_award_reason_list']),
			'listMove2GroupIDs' => explode("\r\n",$vbulletin->options['kbank_award_move_usergroup']),
			
			'permCanRemoveGroupIDs' => explode(',',$vbulletin->options['kbank_award_can_remove']),
			'permMaxAward' => explode("\r\n",$vbulletin->options['kbank_award_max']),
			'permMaxAward24h' => explode("\r\n",$vbulletin->options['kbank_award_max_24h']),
			
			'addpost' => ($vbulletin->options['kbank_award_thank_addpost'] == 1),
			//Constant
			'AllowedScript' => array(
				'showthread', //User viewing thread
				'showpost', //User viewing post
				'editpost', //User editing post
				'ajax', //Not sure about this! ^o^
				'newreply' //User quick reply
			),
			//Table fields
			'awardedtimes' => 'kbank_awardedtimes',
			'awardedamount' => 'kbank_awardedamount',
			'thanksenttimes' => 'kbank_thanksenttimes',
			'thanksentamount' => 'kbank_thanksentamount',
			'thankreceivedtimes' => 'kbank_thankreceivedtimes',
			'thankreceivedamount' => 'kbank_thankreceivedamount',
		),
		//Hide
		'hide' => array(
			'enabled' => ($vbulletin->options['points'] == 1) AND ($vbulletin->options['kbank_hide_on_off'] == 1),
			'shortcut' => 'HIDE-THANKS',
			'thanksMax' => max(0,$vbulletin->options['kbank_hide_thank_max']),
		),
		//Who visited
		'who' => array(
			'enabled' => ($vbulletin->options['points'] == 1) AND ($vbulletin->options['who_visited_on_off'] == 1),
			'get24h' => true,
		),
		//Constant
		'donations' => 'kbank_donations', //Table name
		'postfield' => 'kbank', //Table fields
		//Bitfield
		'postbit_elements' => $vbulletin->options['kbank_display_elements_postbit'],
		'profile_elements' => $vbulletin->options['kbank_display_elements_profile'],
		'bitfield' => array(
			'display_elements' => array(
				'kbank_show_points' => 1
				,'kbank_show_toprichest' => 2
				,'kbank_show_items' => 4
				,'kbank_show_award' => 8
				,'kbank_show_thank' => 16
			)
		),
		//Available Top Lists
		'url_varname' => 'top',
		'force' => 'doitnow', //Force Update command for Administrator
		//options - default values
		'ouroptions' => array(),
		//temp variable
		'temp' => array(),
	);
	$vbulletin->kbank = &$kbank;
	//load our variables - complete!
	
	if ($vbulletin->kbank['enabled']) {
		//fetch datastore
		if ($vbulletin->kbank['itemEnabled']
			OR VB_AREA == 'AdminCP') {
			$datastore_fetch[] = "'kbank_itemtypes'"; //load all itemtypes
			$datastore_fetch[] = "'kbank_warningitems'"; //load all warning items (bidding, pending)
		}
		if (
			($vbulletin->kbank['postbit_elements'] & $vbulletin->kbank['bitfield']['display_elements']['kbank_show_toprichest']
			AND in_array(THIS_SCRIPT,array('showthread','showpost','private')))
			OR ($vbulletin->kbank['profile_elements'] & $vbulletin->kbank['bitfield']['display_elements']['kbank_show_toprichest']
			AND in_array(THIS_SCRIPT,array('member')))
		) {
			$datastore_fetch[] = "'kbank_toprichest'"; //load top richest
		}
		if ($vbulletin->kbank['announceEnabled']) {
			$datastore_fetch[] = "'kbank_announces'"; //load announces
		}
		$datastore_fetch[] = "'kbank_options'"; //load options - added in 2.3
		//fetch datastore

		//load our phrasegroups
		switch (THIS_SCRIPT) {
			case 'kbank': 
				$phrasegroups[] = 'kbank';
				break;
			case 'showpost': 
				if (substr($_REQUEST['do'],0,11) == 'kbank_award') {
					$phrasegroups[] = 'kbank_award';
				}
				break;
			case 'kbankadmin':
				$phrasegroups[] = 'kbank';
				$phrasegroups[] = 'kbank_award';
				$phrasegroups[] = 'kbank_hide';
				break;
		}
		//load our phrasegroup - complete!
	}
	
	($hook = vBulletinHook::fetch_hook('kbank_init')) ? eval($hook) : false;
	
	function GlobalStartCode() {
		//some of init procedure require more info (userinfo) and they should be done inside this function
		global $vbulletin;
		
		//Disable items system
		if (!$vbulletin->userinfo['userid']) {
			//Guest!
			if (THIS_SCRIPT != 'cron') {
				//skip action if we are in cronjob
				$vbulletin->kbank['itemEnabled'] = false;
				$vbulletin->kbank['announceEnabled'] = false;
			}
		}
		//Check for itemtype rebuild - sometime it's missing....
		if ($vbulletin->kbank['itemEnabled']
			AND !$vbulletin->kbank_itemtypes) {
			//$vbulletin->kbank_itemtypes = updateItemTypeCache();
			//too dangerous! skip!
		}
		//Check for announces cache
		if ($vbulletin->kbank['announceEnabled']
			AND !$vbulletin->kbank_announces) {
			//$vbulletin->kbank_announces = updateAnnounceCache();
			//too dangerous! skip!
		}
		//Top Richest
		if (isset($vbulletin->kbank_toprichest)
			AND is_array($vbulletin->kbank_toprichest)) {
			$topByUser = array();
			foreach ($vbulletin->kbank_toprichest['tops'] as $pos => $top) {
				$topByUser[$top['userid']] = array(
					'pos' => $pos + 1,
					'change' => $top['change'],
					'old' => $top['old']
				);
			}
			$vbulletin->kbank_toprichest = $topByUser;
		}
		//Our options
		if (isset($vbulletin->kbank_options) AND is_array($vbulletin->kbank_options))
		{
			foreach ($vbulletin->kbank_options as $key => $value)
			{
				$vbulletin->kbank['ouroptions'][$key] = $value;
			}
		}
		
		//Award
		$rules = $vbulletin->kbank['award']['permMaxAward'];
		$max = false;
		foreach ($rules as $rule) {
			if ($max === false) {
				$max = (int)$rule;
			} else {
				$parts = explode(':',$rule);
				if (count($parts) == 2) {
					if (is_member_of($vbulletin->userinfo,$parts[0])) {
						$max = (int)$parts[1];
					}
				}
			}
		}
		$vbulletin->userinfo['maxAward'] = abs($max);
		$vbulletin->userinfo['minAward'] = (-1)*abs($max);
		
		if (is_member_of($vbulletin->userinfo,$vbulletin->kbank['award']['permCanRemoveGroupIDs'])) {
			$vbulletin->userinfo['canRemoveAwarded'] = true;
		} else {
			$vbulletin->userinfo['canRemoveAwarded'] = false;
		}
		
		//BBCode Parser
		if (!isset($vbulletin->kbankBBCodeParser)) {
			include_once(DIR . '/includes/class_bbcode.php');
			$vbulletin->kbankBBCodeParser =& new vB_BbCodeParser(
				$vbulletin, 
				array(
					'no_option' => array(
						'b' => array(
							'html' => '<b>%1$s</b>',
							'strip_empty' => true
						),
						'i' => array(
							'html' => '<i>%1$s</i>',
							'strip_empty' => true
						),
						'u' => array(
							'html' => '<u>%1$s</u>',
							'strip_empty' => true
						),
					)
				),
				false //Skip custom tags
			);
		}
		
	}
}
?>