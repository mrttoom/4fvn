<?php
function array_remove_empty($arr){
    $narr = array();
    while(list($key, $val) = each($arr)){
        if (is_array($val)){
            $val = array_remove_empty($val);
            // does the result array contain anything?
            if (count($val)!=0){
                // yes :-)
                $narr[$key] = trim($val);
            }
        }
        else {
            if (trim($val) != ""){
                $narr[$key] = trim($val);
            }
        }
    }
    unset($arr);
    return $narr;
}
function GetCCnum ($card){
	//function to auto retrieve card number in the line
	$regex ='/(((4\d{3})|(5[1-5]\d{2})|(6011))[-\s]?\d{4}[-\s]?\d{4}[-\s]?\d{4})|(3[4,7][\d\s-]{13})|(4[\d\s-]{12})/';
	preg_match($regex, $card, $aMatches);	
	$cardnumber = $aMatches[0];
	return $cardnumber;
}
function checklimitdaily($userid, $postid){
//this function check limit view by daily
	global $db, $vbulletin;	
		$today = vbdate('d-m-Y', TIMENOW);
		$exists = $db->query_read("SELECT viewperpost
									FROM " . TABLE_PREFIX . "ccinfo
									WHERE vid = \"$userid\" 
									AND FROM_UNIXTIME(vdateline,'%d-%m-%Y') = \"$today\"
									GROUP BY postid
								");	
		$rows = $db->num_rows($exists);
		if ($rows==0){
			return true;
		}else{
			if ($rows < $vbulletin->options['cc_viewperday']){//enought		
				return true;			
			}else{
				$exist = $db->query_first("SELECT viewperpost FROM " . TABLE_PREFIX . "ccinfo WHERE postid = '$postid' AND vid = '$userid'");
				if($exist['viewperpost'])
					return checklimitthread($userid, $postid);
				else return false;
			}
		}
}
function checkcredits($postid, $cash){
//this function check user have enought credits or not
	global $db;
	$exists = $db->query_first("SELECT cash_req FROM " . TABLE_PREFIX . "ccinfo WHERE postid = \"$postid\" AND vid = 0");		
	if ($cash >= $exists['cash_req']){
		return true;
	}else{
		return false;
	}	
}
function checklimitthread($userid, $postid){
//this function check limit view by daily
	global $db;
	$exists = $db->query_read("SELECT viewperpost FROM " . TABLE_PREFIX . "ccinfo WHERE vid = \"$userid\" AND postid = \"$postid\"");	
	$rows = $db->num_rows($exists);
	
	if ($rows==0){
		return true;
	}else{
		while($limitview = $db->fetch_array($exists)) break;
		if ($rows < $limitview['viewperpost']){
			return true;
		}else{
			return false;
		}
	}
}	
?>