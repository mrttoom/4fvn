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
if (defined('VB_AREA')
	AND THIS_SCRIPT == 'kbank' //Only run within our code or phrases can not be show!
	AND $vbulletin->kbank['enabled']) {
	$hour2update = array(
		'toppost' => 24,
		'toppost24h' => 0.5,
		'toppost1day' => 24,
		'topposteachday' => 24,
		'topthread' => 24,
		'topthreadreply' => 24,
		'topthreadview' => 24,
		'toprichest' => 12,
		'toprichest_withitem' => 12,
		//'toptax' => 24,
		'topthanked' => 1,
		'topthankedamount' => 1,
		'topthank' => 1,
		'topthankamount' => 1,
		'topthankedpost' => 1,
		'topthankedpostamount' => 1,
		'topawarded' => 1,
		'topawardedamount' => 1,
		'topthankarea' => 1,
		'topawardarea' => 1,
		'topaward' => 1,
		'topawardamount' => 1,
	);
	if ($vbulletin->kbank['MonthlyTaxHard']) {
		//Only show this top list if Monthly Tax is enabled
		$hour2update['toptax'] = 24;
	}
	$kBankAdminOnly = array(
		'topthankarea',
		'topawardarea',
		'topaward',
		'topawardamount',
	);
	$name = $_GET[$vbulletin->kbank['url_varname']];
	if (!in_array($name,array_keys($hour2update))
		OR (in_array($name,$kBankAdminOnly)
		AND !havePerm($vbulletin->userinfo,KBANK_PERM_ADMIN))) {
		//There are 2 cases
		//User is trying to access non-exists top
		//User is not a kBank Admin but trying to access a kBankAdminOnly top. Access denied
		$name = null;
	}
	if ($_GET['force'] == $vbulletin->kbank['force']
		AND !havePerm($vbulletin->userinfo,KBANK_PERM_ADMIN)) {
		//User trying to force update but he/she is not kBank Admin!
		$_GET['force'] = null;
	}

	function topChanges(&$new,$old,$morecheck = false,$plus1 = true) {
		if (is_array($new) AND is_array($old)) {
			foreach ($new as $pos => $top_cache) {
				$top =& $new[$pos];
				foreach ($old as $pos_old => $top_old) {
					if ($top_old['userid'] == $top['userid']
						AND $top_old['username'] == $top['username']
						AND ($morecheck === false
						OR $top_old[$morecheck] == $top[$morecheck])) {
						$top['change'] = $pos - $pos_old;
						$top['old'] = iif($plus1,$pos_old + 1,$pos_old);
						break;
					}
				}
			}
		} else {
			for ($i = 0; $i < count($new); $i++) {
				$new[$i]['old']= $i + 1;
				$new[$i]['change']= 0;
			}
		}
	}
	
	function toparea_cmp($a,$b) {
		if ($a['forumid'] == -1) return -1;
		return iif(
			$a['total'] > $b['total']
			,-1
			,iif(
				$a['total'] < $b['total']
				,1
				,iif(
					$a['count'] > $b['count']
					,-1
					,iif(
						$a['count'] < $b['count']
						,1
						,0
					)
				)
			)
		);
	}

	//Top Poster (the greater number of posts, the higher possition)
	if ($name == 'toppost') {
		$cache = read_datastore($name);
		if ($cache['datetime'] < TIMENOW - $hour2update[$name]*60*60
			OR $_GET['force'] == $vbulletin->kbank['force']) {
			$tops_cache = $vbulletin->db->query_read("
				SELECT
					user.userid as userid
					,user.username as username
					,user.posts as postcount
				FROM `" . TABLE_PREFIX . "user` as user
				ORDER BY postcount DESC
				LIMIT 50
			");
			$tops = array();
			while ($top = $vbulletin->db->fetch_array($tops_cache)) {
				$tops[] = $top;
			}
			unset($top);
			$vbulletin->db->free_result($tops_cache);
			
			topChanges($tops,$cache['tops']);
			$cache = array(
				'datetime' => TIMENOW,
				'tops' => $tops
			);
			write_datastore($name,$cache);
		} else {
			$tops = $cache['tops'];
		}			
		
		$top_message = array();
		foreach ($tops as $top) {
			$top_message[] = 
				construct_phrase($vbphrase['kbank_misc_toppost_bit'],GetUsername($top),vb_number_format($top['postcount']))
				. topChangeDisplay($top);
		}
	}
	
	//Top post in the last 24h
	if ($name == 'toppost24h') {
		$cache = read_datastore($name);
		if ($cache['datetime'] < TIMENOW - $hour2update[$name]*60*60
			OR $_GET['force'] == $vbulletin->kbank['force']) {
			$tops_cache = $vbulletin->db->query_read("
				SELECT 
					post.userid as userid
					,post.username as username
					,count(post.postid) as postcount
				FROM `" . TABLE_PREFIX . "post` as post
				WHERE post.dateline > " . (TIMENOW - 24*60*60) . "
				GROUP BY post.userid
				ORDER BY postcount DESC
				LIMIT 50
			");
			$tops = array();
			while ($top = $vbulletin->db->fetch_array($tops_cache)) {
				$tops[] = $top;
			}
			unset($top);
			$vbulletin->db->free_result($tops_cache);
			
			topChanges($tops,$cache['tops']);
			$cache = array(
				'datetime' => TIMENOW,
				'tops' => $tops
			);
			write_datastore($name,$cache);
		} else {
			$tops = $cache['tops'];
		}

		$top_message = array();
		foreach ($tops as $top) {
			$top_message[] = construct_phrase($vbphrase['kbank_misc_toppost_bit']
				,GetUsername($top)
				,vb_number_format($top['postcount'])
			) 
				. topChangeDisplay($top);
		}
	}

	//Top 1 day post. Calculate in 1 day (server time) how many posts has been made by user then order descending (by user, by date)
	if ($name == 'toppost1day') {
		$cache = read_datastore($name);
		if ($cache['datetime'] < TIMENOW - $hour2update[$name]*60*60
			OR $_GET['force'] == $vbulletin->kbank['force']) {
			$tops_cache = $vbulletin->db->query_read("
				SELECT 
					post.userid as userid
					,post.username as username
					,round(post.dateline/60/60/24)*60*60*24 as dateonly
					,count(post.postid) as postcount
				FROM `" . TABLE_PREFIX . "post` as post
				GROUP BY post.userid, dateonly
				ORDER BY postcount DESC
				LIMIT 50
			");
			$tops = array();
			while ($top = $vbulletin->db->fetch_array($tops_cache)) {
				$tops[] = $top;
			}
			unset($top);
			$vbulletin->db->free_result($tops_cache);
			
			topChanges($tops,$cache['tops'],'postcount');
			$cache = array(
				'datetime' => TIMENOW,
				'tops' => $tops
			);
			write_datastore($name,$cache);
		} else {
			$tops = $cache['tops'];
		}

		$top_message = array();
		foreach ($tops as $top) {
			$top_message[] = construct_phrase($vbphrase['kbank_misc_toppost1day_bit']
				,GetUsername($top)
				,vb_number_format($top['postcount'])
				,vbdate($vbulletin->options['dateformat'],$top['dateonly'])
			) 
				. topChangeDisplay($top);
		}
	}
	
	//Top post/day. So simple!
	if ($name == 'topposteachday') {
		$cache = read_datastore($name);
		if ($cache['datetime'] < TIMENOW - $hour2update[$name]*60*60
			OR $_GET['force'] == $vbulletin->kbank['force']) {
			$tops_cache = $vbulletin->db->query_read("
				SELECT
					user.userid as userid
					,user.username as username
					,round((" . TIMENOW . " - user.joindate)/60/60/24) as joined
					,round(user.posts/((" . TIMENOW . " - user.joindate)/60/60/24),2) as postrate
				FROM `" . TABLE_PREFIX . "user` as user
				ORDER BY postrate DESC
				LIMIT 50
			");
			$tops = array();
			while ($top = $vbulletin->db->fetch_array($tops_cache)) {
				$tops[] = $top;
			}
			unset($top);
			$vbulletin->db->free_result($tops_cache);
			
			topChanges($tops,$cache['tops']);
			$cache = array(
				'datetime' => TIMENOW,
				'tops' => $tops
			);
			write_datastore($name,$cache);
		} else {
			$tops = $cache['tops'];
		}
		
		$top_message = array();
		foreach ($tops as $top) {
			$top_message[] = construct_phrase($vbphrase['kbank_misc_topposteachday_bit']
				,GetUsername($top)
				,$top['joined']
				,vb_number_format($top['postrate'],2))
				. topChangeDisplay($top);
		}
	}
	
	if (substr($name,0,9) == 'topthread') {
		$cache = read_datastore($name);
		if ($cache['datetime'] < TIMENOW - $hour2update[$name]*60*60
			OR $_GET['force'] == $vbulletin->kbank['force']) {
			$tops_cache = $vbulletin->db->query_read("
				SELECT
					user.userid as userid
					,user.username as username
					,COUNT(*) as threadcount
					,SUM(thread.replycount) as replycount
					,SUM(thread.views) as viewcount
				FROM `" . TABLE_PREFIX . "user` as user
				INNER JOIN `" . TABLE_PREFIX . "thread` as thread ON (thread.postuserid = user.userid)
				GROUP BY user.userid
				ORDER BY " . iif($name=='topthreadreply','replycount',iif($name=='topthreadview','viewcount','threadcount')) . " DESC
				LIMIT 50
			");
			$tops = array();
			while ($top = $vbulletin->db->fetch_array($tops_cache)) {
				$tops[] = $top;
			}
			unset($top);
			$vbulletin->db->free_result($tops_cache);
			
			topChanges($tops,$cache['tops']);
			$cache = array(
				'datetime' => TIMENOW,
				'tops' => $tops
			);
			write_datastore($name,$cache);
		} else {
			$tops = $cache['tops'];
		}			
		
		$top_message = array();
		foreach ($tops as $top) {
			$top_message[] = 
				construct_phrase($vbphrase['kbank_misc_topthread_bit']
					,GetUsername($top,'search.php?do=process&showposts=0&starteronly=1&exactname=1&searchuser=','username')
					,vb_number_format($top['threadcount'])
					,vb_number_format($top['replycount'])
					,vb_number_format($top['viewcount'])
				)
				. topChangeDisplay($top);
		}
	}
	
	//Top richest
	if ($name == 'toprichest' OR $name == 'toprichest_withitem') {
		if ($name == 'toprichest_withitem') {
			$getitem = true;
		} else {
			$getitem = false;
		}
		$company_groupids = explode(',',$vbulletin->options['kbank_company_groupids']);
		$company_groupids_str = array();
		foreach ($company_groupids as $company_groupid) {
			if ($company_groupid > 0) $company_groupids_str[] = $company_groupid;
		}
		if (count($company_groupids_str)) {
			$company_groupids_str = implode(',',$company_groupids_str);
		} else {
			$company_groupids_str = '';
		}
		
		$cache = read_datastore($name);
		if ($cache['datetime'] < TIMENOW - $hour2update[$name]*60*60
			OR $_GET['force'] == $vbulletin->kbank['force']) {
			$tops_cache = $vbulletin->db->query_read("
				SELECT
					user.userid as userid
					,user.username as username
					,user.money as money
					" . iif(
						$getitem
						,",useritem.itemcount as itemcount
							,useritem.itemtotal as itemtotal
							,(user.money + if(useritem.itemtotal > 0,useritem.itemtotal,0)) as usertotal"
						,",user.money as usertotal"
						) . "
				FROM `" . TABLE_PREFIX . "user` as user
				" . iif(
					$getitem
					,"LEFT JOIN
				(
					SELECT
						item.userid as userid
						,COUNT(item.itemid) as itemcount
						,SUM(item.price) as itemtotal
					FROM `" . TABLE_PREFIX . "kbank_items` as item
					WHERE 
						item.status > -99
						AND (item.expire_time > " . TIMENOW . "
							OR item.expire_time < 0)
					GROUP BY item.userid
				) as useritem on useritem.userid = user.userid") . "
				WHERE 1=1
				"
				. iif($company_groupids_str," AND user.usergroupid NOT IN ($company_groupids_str)")
				. "
				ORDER BY usertotal DESC
				LIMIT 50
			");
			$tops = array();
			while ($top = $vbulletin->db->fetch_array($tops_cache)) {
				$tops[] = $top;
			}
			unset($top);
			$vbulletin->db->free_result($tops_cache);
			
			topChanges($tops,$cache['tops']);
			$cache = array(
				'datetime' => TIMENOW,
				'tops' => $tops
			);
			write_datastore($name,$cache);
		} else {
			$tops = $cache['tops'];
		}
		
		$top_message = array();
		foreach ($tops as $top) {
			$top_message[] = construct_phrase(
					iif($top['itemtotal'],$vbphrase['kbank_misc_toprichest_bit_with_items'],$vbphrase['kbank_misc_toprichest_bit'])
					, GetUsername($top)
					, vb_number_format($top['money'],$vbulletin->kbank['roundup'])
					, vb_number_format($top['itemtotal'],$vbulletin->kbank['roundup'])
					, vb_number_format($top['itemcount'])
					, vb_number_format($top['usertotal'],$vbulletin->kbank['roundup'])
					, $vbulletin->kbank['name']
				) 
				. topChangeDisplay($top);
		}
	}
	
	//Top tax
	if ($name == 'toptax') {
		$cache = read_datastore($name);
		if ($cache['datetime'] < TIMENOW - $hour2update[$name]*60*60
			OR $_GET['force'] == $vbulletin->kbank['force']) {
			$tops_cache = $vbulletin->db->query_read("
				SELECT 
					user.userid AS userid
					,user.username AS username
					,tax.time AS paytime
					,tax.amount AS tax
				FROM `" . TABLE_PREFIX . "kbank_donations` AS tax
				INNER JOIN `" . TABLE_PREFIX . "user` AS user ON (user.userid = tax.from)
				WHERE 
					tax.comment LIKE 'tax_%'
					AND tax.time > " . TIMENOW . " - 30*24*60*60
				ORDER BY tax.amount DESC
				LIMIT 50
			");
			$tops = array();
			while ($top = $vbulletin->db->fetch_array($tops_cache)) {
				$tops[] = $top;
			}
			unset($top);
			$vbulletin->db->free_result($tops_cache);
			
			topChanges($tops,$cache['tops']);
			$cache = array(
				'datetime' => TIMENOW,
				'tops' => $tops
			);
			write_datastore($name,$cache);
		} else {
			$tops = $cache['tops'];
		}
		
		$top_message = array();
		foreach ($tops as $top) {
			$top_message[] = construct_phrase(
				$vbphrase['kbank_misc_toptax_bit']
				, GetUsername($top)
				, vbdate($vbulletin->options['timeformat'] . ' ' . $vbulletin->options['dateformat'],$top['paytime'])
				, vb_number_format($top['tax'],$vbulletin->kbank['roundup'])
				, $vbulletin->kbank['name'])
				. topChangeDisplay($top);
		}
	}
	
	//Top thanked (by times and by amount)
	if ($name == 'topthanked' OR $name == 'topthankedamount') {
		$cache = read_datastore($name);
		if ($cache['datetime'] < TIMENOW - $hour2update[$name]*60*60
			OR $_GET['force'] == $vbulletin->kbank['force']) {
			$tops_cache = $vbulletin->db->query_read("
				SELECT
					userid
					,username
					,{$vbulletin->kbank['award']['thankreceivedtimes']} AS count
					,{$vbulletin->kbank['award']['thankreceivedamount']} AS total
				FROM `" . TABLE_PREFIX . "user`
				WHERE {$vbulletin->kbank['award']['thankreceivedtimes']} > 0
				ORDER BY " . iif($name == 'topthanked','count','total') . " DESC, " . iif($name == 'topthanked','total','count') . " DESC
				LIMIT 50
			");
			$tops = array();
			while ($top = $vbulletin->db->fetch_array($tops_cache)) {
				$tops[] = $top;
			}
			unset($top);
			$vbulletin->db->free_result($tops_cache);
			
			topChanges($tops,$cache['tops']);
			$cache = array(
				'datetime' => TIMENOW,
				'tops' => $tops
			);
			write_datastore($name,$cache);
		} else {
			$tops = $cache['tops'];
		}			
		
		$top_message = array();
		foreach ($tops as $top) {
			$top_message[] = 
				construct_phrase($vbphrase['kbank_misc_topthanked_bit']
					,GetUsername($top,'search.php?do=findthanked&userid=')
					,vb_number_format($top['count'])
					,vb_number_format($top['total'],$vbulletin->kbank['roundup'])
					,$vbulletin->kbank['name']
				) 
				. topChangeDisplay($top);
		}
	}
	
	//Top thank (who send most thank? By times and by amount)
	if ($name == 'topthank' OR $name == 'topthankamount') {
		$cache = read_datastore($name);
		if ($cache['datetime'] < TIMENOW - $hour2update[$name]*60*60
			OR $_GET['force'] == $vbulletin->kbank['force']) {
			$tops_cache = $vbulletin->db->query_read("
				SELECT
					userid
					,username
					,{$vbulletin->kbank['award']['thanksenttimes']} AS count
					,{$vbulletin->kbank['award']['thanksentamount']} AS total
				FROM `" . TABLE_PREFIX . "user`
				WHERE {$vbulletin->kbank['award']['thanksenttimes']} > 0
				ORDER BY " . iif($name == 'topthank','count','total') . " DESC, " . iif($name == 'topthank','total','count') . " DESC
				LIMIT 50
			");
			$tops = array();
			while ($top = $vbulletin->db->fetch_array($tops_cache)) {
				$tops[] = $top;
			}
			unset($top);
			$vbulletin->db->free_result($tops_cache);
			
			topChanges($tops,$cache['tops']);
			$cache = array(
				'datetime' => TIMENOW,
				'tops' => $tops
			);
			write_datastore($name,$cache);
		} else {
			$tops = $cache['tops'];
		}			
		
		$top_message = array();
		foreach ($tops as $top) {
			$top_message[] = 
				construct_phrase($vbphrase['kbank_misc_topthank_bit']
					,GetUsername($top,'search.php?do=findthank&userid=')
					,vb_number_format($top['count'])
					,vb_number_format($top['total'],$vbulletin->kbank['roundup'])
					,$vbulletin->kbank['name']
				) 
				. topChangeDisplay($top);
		}
	}
	
	//Top thanked post. Find the most thanked post!
	if ($name == 'topthankedpost' OR $name == 'topthankedpostamount') {
		$cache = read_datastore($name);
		if ($cache['datetime'] < TIMENOW - $hour2update[$name]*60*60
			OR $_GET['force'] == $vbulletin->kbank['force']) {
			$tops_cache = $vbulletin->db->query_read("
				SELECT
					post.userid AS userid
					,post.username AS username
					,post.postid AS postid
					,COUNT(*) AS count
					,SUM(thank.amount) AS total
				FROM `" . TABLE_PREFIX . $vbulletin->kbank['donations'] . "` AS thank
				INNER JOIN `" . TABLE_PREFIX . "post` AS post ON (post.postid = thank.postid)
				WHERE 
					thank.postid <> 0
					AND thank.from <> 0
				GROUP BY thank.postid
				ORDER BY " . iif($name == 'topthankedpost','count','total') . " DESC, " . iif($name == 'topthankedpost','total','count') . " DESC
				LIMIT 50
			");
			$tops = array();
			while ($top = $vbulletin->db->fetch_array($tops_cache)) {
				$tops[] = $top;
			}
			unset($top);
			$vbulletin->db->free_result($tops_cache);
			
			topChanges($tops,$cache['tops'],'postid');
			$cache = array(
				'datetime' => TIMENOW,
				'tops' => $tops
			);
			write_datastore($name,$cache);
		} else {
			$tops = $cache['tops'];
		}			
		
		$top_message = array();
		foreach ($tops as $top) {
			$top_message[] = 
				construct_phrase($vbphrase['kbank_misc_topthankedpost_bit']
					,GetUsername($top,'search.php?do=findthanked&userid=')
					,$top['postid']
					,vb_number_format($top['count'])
					,vb_number_format($top['total'],$vbulletin->kbank['roundup'])
					,$vbulletin->kbank['name']
				) 
				. topChangeDisplay($top);
		}
	}
	
	//Top awarded (by times and by amount)
	if ($name == 'topawarded' OR $name == 'topawardedamount') {
		$cache = read_datastore($name);
		if ($cache['datetime'] < TIMENOW - $hour2update[$name]*60*60
			OR $_GET['force'] == $vbulletin->kbank['force']) {
			$tops_cache = $vbulletin->db->query_read("
				SELECT
					userid
					,username
					,{$vbulletin->kbank['award']['awardedtimes']} AS count
					,{$vbulletin->kbank['award']['awardedamount']} AS total
				FROM `" . TABLE_PREFIX . "user`
				WHERE {$vbulletin->kbank['award']['awardedtimes']} > 0
				ORDER BY " . iif($name == 'topawarded','count','total') . " DESC, " . iif($name == 'topawarded','total','count') . " DESC
				LIMIT 50
			");
			$tops = array();
			while ($top = $vbulletin->db->fetch_array($tops_cache)) {
				$tops[] = $top;
			}
			unset($top);
			$vbulletin->db->free_result($tops_cache);
			
			topChanges($tops,$cache['tops']);
			$cache = array(
				'datetime' => TIMENOW,
				'tops' => $tops
			);
			write_datastore($name,$cache);
		} else {
			$tops = $cache['tops'];
		}			
		
		$top_message = array();
		foreach ($tops as $top) {
			$top_message[] = 
				construct_phrase($vbphrase['kbank_misc_topawarded_bit']
					,GetUsername($top,'search.php?do=findawarded&userid=')
					,vb_number_format($top['count'])
					,vb_number_format($top['total'],$vbulletin->kbank['roundup'])
					,$vbulletin->kbank['name']
				) 
				. topChangeDisplay($top);
		}
	}
	
	//Top thank/award area. kBank Admin only
	if ($name == 'topthankarea' OR $name == 'topawardarea') {
		$cache = read_datastore($name);
		if ($cache['datetime'] < TIMENOW - $hour2update[$name]*60*60
			OR $_GET['force'] == $vbulletin->kbank['force']) {
			$tops_cache = $vbulletin->db->query_read("
				SELECT
					forum.forumid AS forumid
					,forum.title AS forumtitle
					,COUNT(*) AS count
					,SUM(abs(amount)) AS total
				FROM `" . TABLE_PREFIX . "{$vbulletin->kbank['donations']}` AS donation
				INNER JOIN `" . TABLE_PREFIX . "post` AS post ON (post.postid = donation.postid)
				INNER JOIN `" . TABLE_PREFIX . "thread` AS thread ON (thread.threadid = post.threadid)
				INNER JOIN `" . TABLE_PREFIX . "forum` AS forum ON (forum.forumid = thread.forumid)
				WHERE 
					donation.postid <> 0
					"
					. iif(
						$name == 'topawardarea'
						,'AND donation.from = 0'
						,'AND donation.from <> 0'
					)
					. "
				GROUP BY forumid WITH ROLLUP
			");
			$tops = array();
			while ($top = $vbulletin->db->fetch_array($tops_cache)) {
				if (!$top['forumid']) {
					$top['forumid'] = -1;
					$top['forumtitle'] = '';
				}
				$tops[$top['forumid']] = $top;
			}
			unset($top);
			$vbulletin->db->free_result($tops_cache);
			//Manual sort array (can not use ORDER BY in WITH ROLLUP query)
			usort($tops,'toparea_cmp');

			topChanges($tops,$cache['tops'],'forumid',false);
			$cache = array(
				'datetime' => TIMENOW,
				'tops' => $tops
			);
			write_datastore($name,$cache);
		} else {
			$tops = $cache['tops'];
		}			
		
		$top_message = array();
		foreach ($tops as $top) {
			if ($top['forumid'] != -1) {
				//We are not going to show the overall data
				$top['percent'] = round($top['total']/$tops[0]['total']*100,2);
				$top_message[] = 
					construct_phrase($vbphrase['kbank_misc_toparea_bit']
						,iif($name == 'topthankarea','thank','award')
						,$top['forumid']
						,$top['forumtitle']
						,vb_number_format($top['count'])
						,vb_number_format($top['total'],$vbulletin->kbank['roundup'])
						,$vbulletin->kbank['name']
						,vb_number_format($top['percent'])
					)
					. topChangeDisplay($top);
			}
		}
	}
	
	//Top give award (by times and by amount). kBank Admin Only
	if ($name == 'topaward' OR $name == 'topawardamount') {
		$cache = read_datastore($name);
		if ($cache['datetime'] < TIMENOW - $hour2update[$name]*60*60
			OR $_GET['force'] == $vbulletin->kbank['force']) {
			$query_adminid = "SUBSTR(donate.comment,LOCATE('\"',donate.comment,19) + 1,LOCATE('\"',donate.comment,LOCATE('\"',donate.comment,19) + 1) - (LOCATE('\"',comment,19) + 1))";
			$tops_cache = $vbulletin->db->query_read("
				SELECT 
					$query_adminid AS userid
					,user.username AS username
					,COUNT(*) AS count
					,SUM(ABS(donate.amount)) AS total
				FROM `" . TABLE_PREFIX . "kbank_donations` AS donate
				INNER JOIN `" . TABLE_PREFIX . "user` AS user ON (user.userid = $query_adminid)
				WHERE donate.postid <> 0
					AND donate.from = 0
				GROUP BY userid
				ORDER BY " . iif($name == 'topaward','count','total') . " DESC, " . iif($name == 'topaward','total','count') . " DESC
			");
			$tops = array();
			while ($top = $vbulletin->db->fetch_array($tops_cache)) {
				$tops[] = $top;
			}
			unset($top);
			$vbulletin->db->free_result($tops_cache);
			
			topChanges($tops,$cache['tops']);
			$cache = array(
				'datetime' => TIMENOW,
				'tops' => $tops
			);
			write_datastore($name,$cache);
		} else {
			$tops = $cache['tops'];
		}			
		
		$top_message = array();
		foreach ($tops as $top) {
			$top_message[] = 
				construct_phrase($vbphrase['kbank_misc_topaward_bit']
					,GetUsername($top,'search.php?do=findawardedby&userid=')
					,vb_number_format($top['count'])
					,vb_number_format($top['total'],$vbulletin->kbank['roundup'])
					,$vbulletin->kbank['name']
				) 
				. topChangeDisplay($top);
		}
	}
	
	//List all available top
	if ((isset($_GET[$vbulletin->kbank['url_varname']]) AND !$top_message)
		OR $_GET['do'] == $vbulletin->kbank['url_varname']) {
		$url_prefix = $vbulletin->kbank['phpfile'] . "?$session[sessionurl]top=";
		$top_message = 
			"<strong>$vbphrase[kbank_misc_top]</strong>
			<ul>";
		foreach (array_keys($hour2update) as $type) {
			if (in_array($type,$kBankAdminOnly)) {
				//This type is only for kBank Admin, skip
				continue;
			}
			$top_message .= "<li><a href=\"{$url_prefix}$type\">" . $vbphrase["kbank_misc_$type"] . "</a></li>";
		}
		if (havePerm($vbulletin->userinfo,KBANK_PERM_ADMIN)
			AND count($kBankAdminOnly)) {
			//Show kBank Admin Only Lists
			$top_message .="</ul><strong style=\"color:red\">$vbphrase[kbank_misc_top_admin]</strong><ul>";
			foreach ($kBankAdminOnly as $type) {
				$top_message .= "<li><a href=\"{$url_prefix}$type\">" . $vbphrase["kbank_misc_$type"] . "</a></li>";
			}
			$top_message .="</ul>";
		}
		$top_message .= "</ul>";
		$top_message_skip = true;
	}
	
	//Output
	if ($name) {
		if (is_array($top_message)
			AND count($top_message) > 0) {
			$top_message = 
				"<a href=\"{$vbulletin->kbank['phpfile']}?$session[sessionurl]do={$vbulletin->kbank['url_varname']}\"><strong>$vbphrase[kbank_misc_top]</strong></a> - "
				. '<strong>' . $vbphrase["kbank_misc_$name"] . '</strong><br/>'
				. iif($cache['datetime'] < TIMENOW,construct_phrase($vbphrase['kbank_misc_top_updatetime'],vbdate($vbulletin->options['timeformat'] . ' ' . $vbulletin->options['dateformat'],$cache['datetime']),vbdate($vbulletin->options['timeformat'] . ' ' . $vbulletin->options['dateformat'],$cache['datetime'] + $hour2update[$name]*60*60)) . '<br/>')
				. '<ol><li>'
				. implode('</li><li>',$top_message)
				. '</li></ol>';
		} else {
			$top_message = fetch_error('kbank_top_error');
		}
	}
	if ($top_message) {
		eval(standard_error($top_message,'',false));
	}
}
?>