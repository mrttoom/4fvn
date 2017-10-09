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
if (isset($display['options']['award_search_name'])) {
	$post_message = '';
	//show more info about post
	switch ($display['options']['award_search_name']) {
		case 'findawarded':
		case 'findawardedby':
		case 'findallawarded':
			$post_message = construct_phrase(
				$vbphrase['kbank_award_search_postbit_award']
				,vb_number_format($post['awardcount'])
				,iif($post['awardtotal'] > 0,'+') . vb_number_format($post['awardtotal'],$vbulletin->kbank['roundup'])
				,$vbulletin->kbank['name']
				,iif($post['awardtotal'] > 0,$vbulletin->kbank['award']['showPlusColor'],$vbulletin->kbank['award']['showSubtractColor'])
			);
			break;
		case 'findthanked':
		case 'findthank':
		case 'findallthanked':
			$post_message = construct_phrase(
				$vbphrase['kbank_award_search_postbit_thank']
				,vb_number_format($post['thankcount'])
				,iif($post['thanktotal'] > 0,'+') . vb_number_format($post['thanktotal'],$vbulletin->kbank['roundup'])
				,$vbulletin->kbank['name']
				,iif($post['thanktotal'] > 0,$vbulletin->kbank['award']['showPlusColor'],$vbulletin->kbank['award']['showSubtractColor'])
			);
			break;
	} 
	//output
	if ($post_message) {
		$post['pagetext'] .= $post_message;
	}
}
?>