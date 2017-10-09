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
	//query more field to display detail info
	switch ($display['options']['award_search_name']) {
		case 'findawarded':
		case 'findawardedby':
		case 'findallawarded':
			$hook_query_joins .= 
				"
					LEFT JOIN (
						SELECT 
							award.postid AS postid
							,SUM(award.amount) AS total
							,COUNT(*) AS count
						FROM `" . TABLE_PREFIX . $vbulletin->kbank['donations'] . "` AS award
						WHERE award.postid IN(" . implode(', ', $orderedids) . ")
							AND award.from = 0
						GROUP BY award.postid
					) AS awarded ON (awarded.postid = post.postid)
				";
			$hook_query_fields .= 
				",awarded.count as awardcount
				,awarded.total as awardtotal";
			break;
		case 'findthanked':
		case 'findthank':
		case 'findallthanked':
			$hook_query_joins .= 
				"
					LEFT JOIN (
						SELECT 
							thank.postid AS postid
							,SUM(thank.amount) AS total
							,COUNT(*) AS count
						FROM `" . TABLE_PREFIX . $vbulletin->kbank['donations'] . "` AS thank
						WHERE thank.postid IN(" . implode(', ', $orderedids) . ")
							AND thank.from <> 0
						GROUP BY thank.postid
					) AS thanked ON (thanked.postid = post.postid)
				";
			$hook_query_fields .= 
				",thanked.count as thankcount
				,thanked.total as thanktotal";
			break;
	} 
}
?>