<?php
/*======================================================================*\
|| #################################################################### ||
|| # kBank 2.4
|| # Coded by mrpaint
|| # Contact: mrpaint@gmail.com
|| # I'm a Vietnamese! Thank you for using this script
|| # Last Updated: 03:25 29-03-2009
|| #################################################################### ||
\*======================================================================*/
if (defined('VB_AREA') AND $vbulletin->kbank['enabled']) {
	include_once(DIR . '/kbank/functions.php');

	//Setup variables, call function from init.php
	GlobalStartCode();
	$kbank_system_announces = $kbank_announces = array();

	//Need to pay tax?
	if (THIS_SCRIPT <> 'kbank' //Skip check if accessing kBank
		AND VB_AREA <> 'AdminCP' //Skip check for AdminCP section
		AND $vbulletin->kbank['useMonthlyTax']) {
		$usernextpay = calcMonthlyTaxPayTime($vbulletin->userinfo,0,1);

		if (is_member_of($vbulletin->userinfo,$vbulletin->kbank['MemberGroupIDs'])
			AND !is_member_of($vbulletin->userinfo,$vbulletin->kbank['BankRuptGroupID'])) {
			if ($usernextpay < TIMENOW) {
				//It's time to pay tax! Redirect to tax page
				$link = urlencode($_SERVER['REQUEST_URI']);
				$vbulletin->url = $vbulletin->options['bburl'] . '/' . $vbulletin->kbank['phpfile']. '?' . $vbulletin->session->vars['sessionurl'] ."do=tax&referer=$link";
				eval(print_standard_redirect(fetch_error('kbank_need_pay_tax'),0,0));
			}
			
			if ($usernextpay - max(1,$vbulletin->kbank['MonthlyTaxReminder'])*24*60*60 < TIMENOW) {
				//User have to pay tax soon, display a notice
				$remain_raw = $usernextpay - TIMENOW;
				if ($remain_raw / (60*60*24) > 1) {
					$remain = construct_phrase($vbphrase['kbank_announce_tax_day'],floor($remain_raw / (60*60*24)));
				} else if ($remain_raw / (60*60) > 1) {
					$remain = floor($remain_raw / (60*60)) . 'h';
				} else if ($remain_raw / 60 > 1) {
					$remain = floor($remain_raw / 60) . '\'';
				} else {
					$remain = $remain_raw . 's';
				}
				$kbank_system_announces[] = array(
					'url' => $vbulletin->kbank['phpfile'] . '?do=tax',
					'text' => construct_phrase($vbphrase['kbank_announce_tax'],vbdate($vbulletin->options['dateformat'],$usernextpay),$remain),
					'css' => 'color: red; font-weight: bold'
				);
			}
		}		
		if (is_member_of($vbulletin->userinfo,$vbulletin->kbank['BankRuptGroupID'])) {
			//User is Bank Rupted! Display notice
			$kbank_system_announces[] = array(
				'url' => $vbulletin->kbank['phpfile'] . '?do=tax',
				'text' => construct_phrase($vbphrase['kbank_announce_tax_bankrupt']),
				'css' => 'color: red; font-weight: bold'
			);
		}
	}
	
	//Only build navbar item for registered user - moved from global_setup_complete
	if ($vbulletin->userinfo['userid']) {
		//button
		eval('$template_hook["navbar_buttons_left"] .= "' . fetch_template('kbank_navbar_button') . '";');
	}

	//Item hook
	$KBANK_HOOK_NAME = KBANK_GLOBAL_START;
	findItemToWork($vbulletin->userinfo['userid']);
	findItemExpire($vbulletin->userinfo['userid']);
	//Item hook - complete!
}
?>