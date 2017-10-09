<?php
require_once("./global.php");
require_once(DIR . '/card/functions.php');
#//================= CONFIG ===================\\
$min_thanked = 20;
$thanked_per_view =  10;
#\\============================================//

 if(in_array($_REQUEST['do'],array('add','update','delete','reload','manage')))
{
$vbulletin->input->clean_array_gpc('r', array(
'postid' => TYPE_UINT
));
$postinfo = fetch_postinfo($vbulletin->GPC['postid']);
if($postinfo['userid']!=$vbulletin->userinfo['userid']) print_no_permission();
	$threadinfo = fetch_threadinfo($postinfo[threadid]);
	$foruminfo = fetch_foruminfo($threadinfo[forumid]);
} 
if($_REQUEST['do'] == 'add')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'postid'	=> TYPE_UINT,
		'cc' => TYPE_STR,
		'type' => TYPE_STR
	));
	if(!$vbulletin->userinfo['userid'] || !$vbulletin->GPC['postid']){
		echo 'Error:: Không có quyền';
		exit;
	}
	$check = $vbulletin->db->query_first("
		SELECT card, cash_req, viewperpost
		FROM " . TABLE_PREFIX . "ccinfo
		WHERE postid = " . $vbulletin->GPC['postid'] . "
		AND postuserid = " . $vbulletin->userinfo['userid']
	);
	if(!$check[card]){
		$check[viewperpost] = 1;
		$check[cash_req] = 0;
	}
	$type = $vbulletin->GPC['type'];
	$ccinfos = explode("\n", $vbulletin->GPC['cc']);
	$ccinfos = array_unique(array_remove_empty($ccinfos));
	if(empty($ccinfos)){
		echo 'Error:: Không em nào được chèn';
		exit;
	}
	if(is_array($ccinfos))
	{
		foreach($ccinfos AS $cc)
		{
			if($cc){
				if($type=="cc"){
					$cc = preg_replace('/(\d{4})\|(\d{4})\|(\d{4})\|(\d{4})/i','$1$2$3$4',$cc);
					$ccnum = GetCCnum ($cc);
					if(is_numeric($ccnum)){
						$card_detail = str_replace($ccnum, '', $cc);
						$card_detail = $ccnum."|".$card_detail;
						$check3 = $vbulletin->db->query_first("
							SELECT postid
							FROM " . TABLE_PREFIX . "ccinfo
							WHERE card LIKE '%$ccnum%'
						");						
						if($check3[postid]){
							echo 'Error:: ' . $ccnum . ' đã có trong hệ thống';
							exit;
						}	
						$vbulletin->db->query_write("
							INSERT INTO " . TABLE_PREFIX . "ccinfo(card, postid, postuserid, viewperpost, cash_req, dateline)
							VALUES('" . $vbulletin->db->escape_string($card_detail) . "', " . $vbulletin->GPC['postid'] . ", " . $vbulletin->userinfo['userid'] . ", $check[viewperpost], $check[cash_req], " . TIMENOW . ")
						");	
					}
					else {
						echo 'Error:: Sai định dạng';
						exit;
					}	
				}
				else{
					$check2 = $vbulletin->db->query_first("
						SELECT postid
						FROM " . TABLE_PREFIX . "ccinfo
						WHERE card = '" . $vbulletin->db->escape_string($cc) . "'
					");
					$check3 = $vbulletin->db->query_first("
						SELECT postid
						FROM " . TABLE_PREFIX . "ccinfo
						WHERE card LIKE '%" . $vbulletin->db->escape_string($cc) . "%'
					");						
					if($check2[postid] OR $check3[postid]){
						echo 'Error:: Trùng';
						exit;
					}					
					$vbulletin->db->query_write("
						INSERT INTO " . TABLE_PREFIX . "ccinfo(card, postid, postuserid, viewperpost, cash_req, dateline)
						VALUES('" . $vbulletin->db->escape_string($cc) . "', " . $vbulletin->GPC['postid'] . ", " . $vbulletin->userinfo['userid'] . ", $check[viewperpost], $check[cash_req], " . TIMENOW . ")
					");
				}
			}
		}
		$_REQUEST['do'] = 'reload';
	}	
}
if($_REQUEST['do'] == 'update')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'postid'	=> TYPE_UINT,
		'limit' => TYPE_UINT,
		'cash_req' => TYPE_UINT
	));
	if(!$vbulletin->userinfo['userid'] || !$vbulletin->GPC['postid']){
		echo 'Error:: Không có quyền';
		exit;
	}
	$check = $vbulletin->db->query_first("
		SELECT card
		FROM " . TABLE_PREFIX . "ccinfo
		WHERE postid = " . $vbulletin->GPC['postid'] . "
		AND postuserid = " . $vbulletin->userinfo['userid']
	);
	if(!$check[card]){
		echo 'Error:: Không tồn tại';
		exit;
	}
	$limitview = (is_numeric($vbulletin->GPC['limit'])) ? $vbulletin->GPC['limit'] : 1;
	$cash_req = (is_numeric($vbulletin->GPC['cash_req'])) ? $vbulletin->GPC['cash_req'] : 0;
	$vbulletin->db->query_write("
		UPDATE " . TABLE_PREFIX . "ccinfo
		SET viewperpost = " . $limitview . ",
			cash_req = " . $cash_req . "
		WHERE postid = " . $vbulletin->GPC['postid']
	);	
	$_REQUEST['do'] = 'reload';
}
if($_REQUEST['do'] == 'delete')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'postid'	=> TYPE_UINT,
		'cid' => TYPE_UINT
	));
	if(!$vbulletin->userinfo['userid'] || !$vbulletin->GPC['postid'] || !$vbulletin->GPC['cid']){
		echo 'Error: Không có quyền';
		exit;
	}
	$check = $vbulletin->db->query_first("
		SELECT card,vid
		FROM " . TABLE_PREFIX . "ccinfo
		WHERE postid = " . $vbulletin->GPC['postid'] . "
		AND postuserid = " . $vbulletin->userinfo['userid'] . "
		AND id = " . $vbulletin->GPC['cid']
	);
	if(!$check[card]){
		echo 'Error: Không tồn tại';
		exit;
	}
	if($check[vid]){
		echo 'Error: Không thể xoá';
		exit;
	}
	$vbulletin->db->query_write("
		DELETE FROM " . TABLE_PREFIX . "ccinfo
		WHERE postid = " . $vbulletin->GPC['postid'] . "
		AND postuserid = " . $vbulletin->userinfo['userid'] . "
		AND id = " . $vbulletin->GPC['cid']
	);
	echo 'Message: <td align="center" colspan="6">Đã bị xoá</td>';
	exit;
}
if($_REQUEST['do'] == 'manage')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'postid'	=> TYPE_UINT
	));
	if(!$vbulletin->userinfo['userid'] || !$vbulletin->GPC['postid']){
		print_no_permission();
	}
	$check = $vbulletin->db->query_first("
		SELECT card
		FROM " . TABLE_PREFIX . "ccinfo
		WHERE postid = " . $vbulletin->GPC['postid'] . "
		AND postuserid = " . $vbulletin->userinfo['userid']
	);
	$postid = $vbulletin->GPC['postid'];
	$ccviewperday = $vbulletin->options['cc_viewperday'];
	$cclimitview = 1;	
	if($check[card]){
	$q = $vbulletin->db->query_read("
		SELECT * FROM " . TABLE_PREFIX . "ccinfo
		WHERE postid = " . $vbulletin->GPC['postid']
	);

	$ccid = 0;
	while($cc = $vbulletin->db->fetch_array($q))
	{
		$cash_req = $cc[cash_req];
		$ccid++;
		$cclimitview = $cc[viewperpost];
		if(!$cc[vid]) {
			$cc[viewby] = 'None';
			$cc[status] = 'View';
			$cmed = true;
		}
		else {
			$cuserinfo = fetch_userinfo($cc[vid]);
			fetch_musername($cuserinfo);
			$cc[viewby] = $cuserinfo[musername];
			$cc[status] = 'Viewed';
			$cmed = false;
		}
		if(!$cc[vdateline]) $cc[viewat] = 'N/A';
		else {
			$date = vbdate($vbulletin->options['dateformat'], $cc[vdateline]);
			$time = vbdate($vbulletin->options['timeformat'], $cc[vdateline]);
			$cc[viewat] = "$date $time";
		}	
		eval('$ccinfo .= "' . fetch_template('cc_manage_bit') . '";');
		unset($cc);
	}
	$vbulletin->db->free_result($q);
	}
	else $cash_req = 0;
	$navbits = construct_navbits(array(
		'showthread.php?' . $vbulletin->session->vars['sessionurl'] . "p=$postid" => 'Manage Cards',
		'' => 'Cards Detail'
	));
	eval('$navbar = "' . fetch_template('navbar') . '";');	
	eval('print_output("' . fetch_template('cc_manage') . '");');	
}
if($_REQUEST['do'] == 'viewcc')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'postid'	=> TYPE_UINT,
		'cid'	=> TYPE_UINT,
		'order' => TYPE_UINT
	));	
	if(!$vbulletin->options['cc_active']){
		echo 'Error: Hệ thống đã bị khoá';
		exit;
	}	
	if(!$vbulletin->userinfo['userid']){
		echo 'Error: Bạn không có quyền';
		exit;
	}	
	if(!$vbulletin->GPC['postid']){
		echo 'Error: Invalid PostID';
		exit;
	}
	if(!$vbulletin->GPC['cid']){
		echo 'Error: Invalid CCID';
		exit;
	}
	$check = $vbulletin->db->query_first("
		SELECT vid
		FROM " . TABLE_PREFIX . "ccinfo
		WHERE id = " . $vbulletin->GPC['cid']
	);
	if($check[vid]){
		echo 'Error: Có người ăn rồi kìa';
		exit;
	}	
	$check = $vbulletin->db->query_first("
		SELECT username
		FROM " . TABLE_PREFIX . "post_thanks
		WHERE postid = " . $vbulletin->GPC['postid'] . "
		AND userid = " . $vbulletin->userinfo['userid']
	);
	if(!$check[username]){
		echo 'Error: Bấm Thanks đi rồi ăn';
		exit;
	}
	if(!checklimitdaily($vbulletin->userinfo['userid'],$vbulletin->GPC['postid'])){
		echo 'Error: Ăn nhiều quá rồi, mai tiếp nha!';
		exit;
	}
	if(!checklimitthread($vbulletin->userinfo['userid'], $vbulletin->GPC['postid'])){
		echo 'Error: Bạn đã ăn hết số lần cho phép trong bài này rồi!';
		exit;	
	}

//Check min thank-----------------------------------------------------------------------------------
	$checkmin = $vbulletin->db->query_first("
		SELECT username,post_thanks_thanked_times AS thanked
		FROM " . TABLE_PREFIX . "user
		WHERE userid = " . $vbulletin->userinfo['userid']
	);
	if($checkmin[thanked] < $min_thanked ){
			echo 'Error: Bạn chưa đủ thanked để xem';
			exit;	
		}
//----------------------------------------------------------------------------------------------------------

	if(!checkcredits($vbulletin->GPC['postid'],$vbulletin->userinfo["{$vbulletin->options['cc_cash_field']}"])){
		echo 'Error: You dont have enought cash to View resource';
		exit;		
	}
	$vbulletin->db->query_write("
		UPDATE " . TABLE_PREFIX . "ccinfo
		SET vid = " . $vbulletin->userinfo['userid'] . ",
			vdateline = " . TIMENOW . "
		WHERE id = " . $vbulletin->GPC['cid']
	);

// Trừ thank khi view.-------------------------------------------------------------------------------
	$vbulletin->db->query_write("
		UPDATE " . TABLE_PREFIX . "user
		SET post_thanks_thanked_times = post_thanks_thanked_times - " . $thanked_per_view. "
		WHERE userid = " . $vbulletin->userinfo['userid']
	);
//----------------------------------------------------------------------------------------------------------

	$cc = $vbulletin->db->query_first("
		SELECT *
		FROM " . TABLE_PREFIX . "ccinfo
		WHERE id = " . $vbulletin->GPC['cid']
	);
	$userinfo = fetch_userinfo($cc[vid]);
	fetch_musername($userinfo);
	$date = vbdate($vbulletin->options['dateformat'], $cc[vdateline]);
	$time = vbdate($vbulletin->options['timeformat'], $cc[vdateline]);
	
	echo <<<cc
Message: <td width="5%" align="center">{$vbulletin->GPC['order']}</td>
<td width="45%"><font color="red">$cc[card]</font></td>
<td width="15%" align="center">$cc[cash_req]</td>
<td width="15%" align="center">$userinfo[musername]</td>
<td width="15%" align="center">$date $time</td>
<td width="15%" align="center"><img src="card/Viewed.gif" border="0"/></td>
cc;
	exit;
}
if($_REQUEST['do'] == 'reload')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'postid'	=> TYPE_UINT
	));
	if(!$vbulletin->userinfo['userid'] || !$vbulletin->GPC['postid']){
		echo 'Error:: No Permission';
		exit;
	}
	$check = $vbulletin->db->query_first("
		SELECT card
		FROM " . TABLE_PREFIX . "ccinfo
		WHERE postid = " . $vbulletin->GPC['postid'] . "
		AND postuserid = " . $vbulletin->userinfo['userid']
	);
	if(!$check[card]) {
		echo 'Error:: No Card Found';
		exit;
	}	
	$q = $vbulletin->db->query_read("
		SELECT * FROM " . TABLE_PREFIX . "ccinfo
		WHERE postid = " . $vbulletin->GPC['postid'] . "
	");	
	$ccid = 0;
	$postid = $vbulletin->GPC['postid'];
	$ccviewperday = $vbulletin->options['cc_viewperday'];
	$cclimitview = 0;
	while($cc = $vbulletin->db->fetch_array($q))
	{
		$cash_req = $cc[cash_req];
		$ccid++;
		if(!$cclimitview) $cclimitview = $cc[viewperpost];
		if(!$cc[vid]) {
			$cc[viewby] = 'None';
			$cc[status] = 'View';
			$cmed = true;
		}
		else {
			$cuserinfo = fetch_userinfo($cc[vid]);
			fetch_musername($cuserinfo);
			$cc[viewby] = $cuserinfo[musername];
			$cc[status] = 'Viewed';
			$cmed = false;
		}
		if(!$cc[vdateline]) $cc[viewat] = 'N/A';
		else {
			$date = vbdate($vbulletin->options['dateformat'], $cc[vdateline]);
			$time = vbdate($vbulletin->options['timeformat'], $cc[vdateline]);
			$cc[viewat] = "$date $time";
		}	
		eval('$ccinfo .= "' . fetch_template('cc_manage_bit') . '";');
		unset($cc);
	}
	$vbulletin->db->free_result($q);
	echo <<<cc
Message:: <div align="center"><h4>[ Tối đa: $cclimitview | Xem trong ngày: $ccviewperday ]</h4></div>
<table cellspacing="0" cellpadding="0" border="1" align="center" width="100%">
<tr>
<td height="25px" width="5%" align="center" class="thead">STT</td>
<td width="45%" align="center" class="thead">Thông tin đầy đủ</td>
<td width="15%" align="center" class="thead">Credits</td>
<td width="15%" align="center" class="thead">Xem bởi</td>
<td width="15%" align="center" class="thead">Xem lúc</td>
<td width="15%" align="center" class="thead">Manager</td>
</tr>
$ccinfo
</table>
<br />
<div align="center"><input type="button" onclick="gotothread()" value="Quay lại bài viết" /></div>
cc;
		exit;
}
?>
