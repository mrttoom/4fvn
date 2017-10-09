<?php

if (!isset($GLOBALS['vbulletin']->db)) {
	exit;
}

if ( !class_exists('KeyCAPTCHA_CLASS') ) {
	class KeyCAPTCHA_CLASS
	{
		var $c_kc_keyword = "accept";
		var $p_kc_visitor_ip;
		var $p_kc_session_id;
		var $p_kc_web_server_sign;
		var $p_kc_web_server_sign2;
		var $p_kc_js_code;
		var $p_kc_private_key;
		var $p_kc_userID;

		function get_web_server_sign($use_visitor_ip = 0)
		{
			return md5($this->p_kc_session_id . (($use_visitor_ip) ? ($this->p_kc_visitor_ip) :("")) . $this->p_kc_private_key);
		}

		function KeyCAPTCHA_CLASS($a_private_key='')
		{
			if ( $a_private_key != '' ){
				$set = explode("0",trim($a_private_key),2);
				if (sizeof($set)>1){  // if new type of private key
					$this->p_kc_private_key = $set[0];
					$this->p_kc_userID = (int)$set[1];
					$this->p_kc_js_code = "
						<!-- KeyCAPTCHA code (www.keycaptcha.com)-->
						<script type=\"text/javascript\">
							var s_s_c_user_id = '".$this->p_kc_userID."';
							var s_s_c_session_id = '#KC_SESSION_ID#';
							var s_s_c_captcha_field_id = 'hash';
							var s_s_c_submit_button_id = 'sbutton-#-r';
							var s_s_c_web_server_sign = '#KC_WSIGN#';
							var s_s_c_web_server_sign2 = '#KC_WSIGN2#';
						</script>
						<script type=\"text/javascript\" src=\"http://backs.keycaptcha.com/swfs/cap.js\"></script>
						<!-- end of KeyCAPTCHA code-->";
				}
			}
			$this->p_kc_session_id = uniqid() . '-3.7.0.18';
			$this->p_kc_visitor_ip = $_SERVER["REMOTE_ADDR"];
			$this->p_kc_web_server_sign = "";
			$this->p_kc_web_server_sign2 = "";
		}

		function http_get($path)
		{
			$arr = parse_url($path);
			$host = $arr['host'];
			$page = $arr['path'];
			if ( $page=='' ) {
				$page='/';
			}
			if ( isset( $arr['query'] ) ) {
				$page.='?'.$arr['query'];
			}
			$errno = 0;
			$errstr = '';
			$fp = fsockopen ($host, 80, $errno, $errstr, 30);
			if (!$fp){ return ""; }
			$request = "GET $page HTTP/1.0\r\n";
			$request .= "Host: $host\r\n";
			$request .= "Connection: close\r\n";
			$request .= "Cache-Control: no-store, no-cache\r\n";
			$request .= "Pragma: no-cache\r\n";
			$request .= "User-Agent: KeyCAPTCHA\r\n";
			$request .= "\r\n";

			fwrite ($fp,$request);
			$out = '';

			while (!feof($fp)) $out .= fgets($fp, 250);
			fclose($fp);
			$ov = explode("close\r\n\r\n", $out);

			return $ov[1];
		}

		function check_result($response)
		{
			$kc_vars = explode("|", $response);
			if ( count( $kc_vars ) < 4 )
			{
				return false;
			}
			if ($kc_vars[0] == md5($this->c_kc_keyword . $kc_vars[1] . $this->p_kc_private_key . $kc_vars[2]))
			{
				if (strpos(strtolower($kc_vars[2]), "http://") !== 0)
				{
					$kc_current_time = time();
					$kc_var_time = split('[/ :]', $kc_vars[2]);
					$kc_submit_time = gmmktime($kc_var_time[3], $kc_var_time[4], $kc_var_time[5], $kc_var_time[1], $kc_var_time[2], $kc_var_time[0]);
					if (($kc_current_time - $kc_submit_time) < 15)
					{
						return true;
					}
				}
				else
				{
					if ($this->http_get($kc_vars[2]) == "1")
					{
						return true;
					}
				}
			}
			return false;
		}

		function render_js ()
		{
			if ( isset($_SERVER['HTTPS']) && ( $_SERVER['HTTPS'] == 'on' ) )
			{
				$this->p_kc_js_code = str_replace ("http://","https://", $this->p_kc_js_code);
			}
			$this->p_kc_js_code = str_replace ("#KC_SESSION_ID#", $this->p_kc_session_id, $this->p_kc_js_code);
			$this->p_kc_js_code = str_replace ("#KC_WSIGN#", $this->get_web_server_sign(1), $this->p_kc_js_code);
			$this->p_kc_js_code = str_replace ("#KC_WSIGN2#", $this->get_web_server_sign(), $this->p_kc_js_code);
			return $this->p_kc_js_code;
		}
	}
}

/**
* KeyCAPTCHA for vBulletin
*/
class vB_HumanVerify_KeyCAPTCHA extends vB_HumanVerify_Abstract {
	
	function vB_HumanVerify_KeyCAPTCHA(&$registry) {
		parent::vB_HumanVerify_Abstract($registry);
	}

	function verify_token($input) {
		global $vbulletin;
				
		$kc_o = new KeyCAPTCHA_CLASS( $vbulletin->options['keycaptcha_privatekey'] );
		
		$kc_field = ( isset( $_POST['hash'] ) ) ? $_POST['hash'] : $_POST['humanverify']['hash']; 
		
		if (!$kc_o->check_result($kc_field)) {
		    $this->error = 'keycaptcha_wrong_solution';
			return false;
		}
		return true;
	}
	
	function output_token($var_prefix = 'humanverify') {
		global $vbulletin;
		
		$kc_o = new KeyCAPTCHA_CLASS( $vbulletin->options['keycaptcha_privatekey']);
		$vn = explode('.',$vbulletin->versionnumber);

		if ( $vn[0] == '3' ) {
			$tmpl = '<fieldset class="fieldset"><legend>##task##</legend>&nbsp;<br><input type=hidden value="1" id="humanverify" name="humanverify" /><input type=hidden value="1" id="hash" name="hash" />##keycaptchacode##'.
				 '<noscript>You should turn on JavaScript on your browser. After that please reload the page.'.
				 'Otherwise you won&#039;t be able to post any information on this site.</noscript>&nbsp;<br></fieldset>';
		} else {
	$SQL = "select count(`styleid`) as cnt from `".TABLE_PREFIX."style` where (`title` like '%Mobile%') and styleid =".intval($vbulletin->userinfo["styleid"]);
	$result = $vbulletin->db->query_read($SQL);
	$r = $vbulletin->db->fetch_array($result);
	if ($r['cnt']==1){
		$tmpl = '<h3 class="blocksubhead">##task##</h3>&nbsp;
			<input type=hidden value="1" id="humanverify" name="humanverify" />
			<input type=hidden value="1" id="hash" name="hash" />##keycaptchacode##'.
			'<noscript>You should turn on JavaScript on your browser. After that please reload the page.'.
			'Otherwise you won&#039;t be able to post any information on this site.</noscript><br/>';
	}else {
		$tmpl = '</div><h3 class="blocksubhead">##task##</h3>&nbsp;<br>
			<div class="section"><input type=hidden value="1" id="humanverify" name="humanverify" /><input type=hidden value="1" id="hash" name="hash" />##keycaptchacode##'.
			'<noscript>You should turn on JavaScript on your browser. After that please reload the page.'.
			'Otherwise you won&#039;t be able to post any information on this site.</noscript></div><div>&nbsp;<br>';
	}
}

		$kc_js = $kc_o->render_js();
		if ( $vbulletin->options['keycaptcha_onerror'] != '' ) {
			$kc_js = str_replace($kc_o->p_kc_session_id."';", $kc_o->p_kc_session_id."';document.s_s_c_onerroralert='".str_replace ("'",'"',$vbulletin->options['keycaptcha_onerror'])."';", $kc_js);
		}

		$stout = str_replace( '##keycaptchacode##', $kc_js, $tmpl );
		$stout = str_replace( '##task##', $vbulletin->options['keycaptcha_task'], $stout );
		return $stout;
	}	
}

?>
