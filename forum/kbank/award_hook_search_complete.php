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
if (isset($display['options']['award_search_name'])) {
	$phrasename_org = 'posts_made_by';
	$phrasename = false;
	//change default vbphrase to our phrase for correctly display
	switch ($display['options']['award_search_name']) {
		case 'findawarded':
			$phrasename = 'kbank_award_search_user';
			break;
		case 'findawardedby':
			$phrasename = 'kbank_award_search_user_by';
			break;
		case 'findthanked':
			$phrasename = 'kbank_award_search_user_thank';
			break;
		case 'findthank':
			$phrasename = 'kbank_award_search_user_thank_by';
			break;
		case 'findallawarded':
			$phrasename_org = 'search'; //also change the Search button in navbar +_+ Hope to know another solution
			$phrasename = 'kbank_award_search';
			break;
		case 'findallthanked':
			$phrasename_org = 'search'; //also change the Search button in navbar +_+ Hope to know another solution
			$phrasename = 'kbank_award_search_thank';
			break;
	}

	$vbphrase[$phrasename_org] = trim(construct_phrase($vbphrase[$phrasename],'')); //The tricky way to use our phrase instead of search default phrase
}
?>