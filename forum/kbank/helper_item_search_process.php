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
	/*item listing search process
	Input:
		TYPE_UINT $vbulletin->GPC['username'] 
		TYPE_UINT $vbulletin->GPC['date_type']
		TYPE_ARRAY_UINT $vbulletin->GPC['date']
		TYPE_STR $vbulletin->GPC['itemtypeid']
		TYPE_NOHTML $vbulletin->GPC['itemname']
	Output:
		TYPE_STR $where_conditions (appends string)
		TYPE_STR $page_suffix
		TYPE_ARRAY $search
		TYPE_ARRAY $itemtypes_list
		TYPE_ARRAY $itemtypes_list_raw
	*/	
	
	$vbulletin->input->clean_array_gpc('r', array(
		'username' => TYPE_NOHTML,
		'date_type' => TYPE_INT,
		'date' => TYPE_ARRAY_UINT,
		'itemtypeid' => TYPE_STR,
		'itemname' => TYPE_NOHTML,
	));
	
	$search = array();
	if ($vbulletin->GPC['username']) {
		$usernames_tmp = explode(',',$vbulletin->GPC['username']);
		$usernames_query = array();
		$usernames = array();
		$userids = array();
		foreach ($usernames_tmp as $username) {
			$username = trim($username);
			if (strtoupper($username) == strtoupper($vbphrase['kbank'])) {
				$userids[] = 0;
				$usernames[] = $vbphrase['kbank'];
			}
			if ($username) {
				$usernames_query[] = "'" . $vbulletin->db->escape_string(strtolower($username)) . "'";
			}
		}
		if (count($usernames_query) > 0) {
			$users_tmp = $vbulletin->db->query_read("SELECT userid,username
				FROM `" . TABLE_PREFIX . "user`
				WHERE LOWER(username) in (" . implode(',',$usernames_query) . ")");
			while ($user = $vbulletin->db->fetch_array($users_tmp)) {
				$userids[] = $user['userid'];
				$usernames[] = $user['username'];
			}
			$vbulletin->db->free_result($users_tmp);
			unset($user);
		}
		if (count($userids) > 0 AND count($usernames) > 0) {
			$where_conditions .= " AND items.userid in (" . implode(',',$userids) . ")";
			$search['username'] = implode(',',$usernames);
		}
	}
	if ($vbulletin->GPC['date_type'] != 0) {
		$system_date = mktime(0,0,0 + $vbulletin->options['hourdiff'],$vbulletin->GPC['date']['month'],$vbulletin->GPC['date']['day'],$vbulletin->GPC['date']['year']);
		
		if ($vbulletin->GPC['date_type'] > 0) {
			$where_conditions .= " AND (items.expire_time > $system_date OR items.expire_time < 0)";
		} else {
			$where_conditions .= " AND items.expire_time < $system_date";
		}
		
		$search['date_type'] = $vbulletin->GPC['date_type'];
		$search['date'] = array(
			'day' => vbdate('j',$system_date),
			'month' => vbdate('n',$system_date),
			'year' => vbdate('Y',$system_date),
		);
	} else {
		$system_date = TIMENOW;
		$search['date'] = array(
			'day' => vbdate('j',$system_date),
			'month' => vbdate('n',$system_date),
			'year' => vbdate('Y',$system_date),
		);
	}
	if ($vbulletin->GPC['itemtypeid']) {
		$tmp = explode(',',$vbulletin->GPC['itemtypeid']);
		$ids = array();
		foreach ($tmp as $id) if (is_numeric($id) && $id > 0) $ids[] = $id;
		
		if (count($ids) > 0)
		{
			$vbulletin->GPC['itemtypeid'] = implode(',',$ids);
			$where_conditions .= " AND items.type IN ({$vbulletin->GPC['itemtypeid']})";	
			$search['itemtypeid'] = $vbulletin->GPC['itemtypeid'];
		}
	}
	if ($vbulletin->GPC['itemname']) {
		$vbulletin->GPC['itemname'] = $vbulletin->db->escape_string($vbulletin->GPC['itemname']);
		$where_conditions .= " AND items.name LIKE '%{$vbulletin->GPC['itemname']}%'";	
		$search['itemname'] = $vbulletin->GPC['itemname'];
	}
	
	$page_suffix = buildPageSuffix($search);

	$itemtypeids = array();
	$itemtypes_list = '';
	$itemtypes_list_raw = array();
	$itemtypeids[$vbphrase['please_select_one']] = array('');
	if (is_array($vbulletin->kbank_itemtypes))
		foreach ($vbulletin->kbank_itemtypes as $itemtype)
		{
			$itemtypeids[$itemtype['filename']][] = $itemtype['itemtypeid'];
		}
	foreach ($itemtypeids AS $filename => $ids)
	{
		$value = implode(',',$ids);
		$name = ucwords(str_replace('_',' ',basename($filename,'.kbank.php')));

		$itemtypes_list .= "<option value=\"$value\"" . (in_array($search['itemtypeid'],$ids)?' selected="selected"':'') . ">$name</option>";
		$itemtypes_list_raw[$value] = $name;
	}
?>