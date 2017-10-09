<?php
/*
|| Multiple Account Detection & Prevention 1.1.3
|| ... a modification for vBulletin 3.7.x
|| Created by Kiros (Kiros72 on vBulletin.org)
|| ... with inspiration from MPDev and randominity on vBulletin.org
*/

$expire = !empty($vbulletin->options['madp_cookie_expire']) ? (TIMENOW + ($vbulletin->options['madp_cookie_expire'] * 86400)) : (TIMENOW + 1209600);

if (!empty($vbulletin->options['madp_cookie_name']) AND $vbulletin->options['madp_cookie_name'] != 'IDstack')
{
	$cookie = preg_replace(array('/[^A-Za-z0-9\-_]+/', '/' . COOKIE_PREFIX . '/'), '', $vbulletin->options['madp_cookie_name']); 
}

if (empty($cookie))
{
	$cookie = 'IDstack';
}

if (!empty($vbulletin->userinfo['userid']))
{
	if (!empty($_COOKIE["$cookie"]))
	{
		$stack = $_COOKIE["$cookie"];

		if ($vbulletin->options['madp_cookie_reset'] AND !$vbulletin->options['madp_detection'] AND !$vbulletin->options['madp_prevention'])
		{
			$weekago = TIMENOW - 604801;
			setcookie($cookie, '000', $weekago, $vbulletin->options['cookiepath'], $vbulletin->options['cookiedomain']);
		}
		else if (strpos($stack, ",{$vbulletin->userinfo['userid']},") === false)
		{
			// if it's a new detection
			$stack .= ",{$vbulletin->userinfo['userid']},";
			setcookie($cookie, $stack, $expire, $vbulletin->options['cookiepath'], $vbulletin->options['cookiedomain']);
		}
		else if ($vbulltin->$options['madp_cookie_refresh'])
		{
			setcookie($cookie, $stack, $expire, $vbulletin->options['cookiepath'], $vbulletin->options['cookiedomain']);
		}
	}
	else
	{
		setcookie($cookie, ",{$vbulletin->userinfo['userid']},", $expire, $vbulletin->options['cookiepath'], $vbulletin->options['cookiedomain']);
	}
}
?>