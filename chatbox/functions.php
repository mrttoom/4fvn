<?php

########### CHECK REFER ############
function checkpost($truelinks, $url)
{
	$kt = false;
	$truelinks = explode(',' , $truelinks);
	$url = '_'.$url;
	foreach ($truelinks as $truelink)
	{
		$l1 = 'http://'.$truelink;
		$l2 = 'http://www.'.$truelink;
		if (strpos($url, $l1) == 1 || strpos($url, $l2) == 1 )
		{
			$kt = true;
		}
		if ($kt) break;
	}
	return $kt;
}

######## CHECK MD5 ##############
function check_chatbox_key($key, $userid, $username, $usergroup)
{
	global $config;
	$genkey = md5($userid.$config['chatboxkey'].$usergroup.md5($username.$config['chatboxkey']));
	if ($genkey == $key)
	{
		return true;
	}
	else
	{
		return false;
	}
}

########## BULID SMILIES ###########
function bulid_smilies()
{
	global $fcbfile;
	$smilies = file($fcbfile['smilie']);
	foreach ($smilies as $sm)
	{
		if ($sm)
		{
			$sm = explode(" => ", trim($sm));
			$sms[htmlspecialchars($sm[0])] = "<img src='$sm[1]' border='0' />";
			if (preg_match('*[a-z]*', $sm[0]))
			$sms[strtoupper(htmlspecialchars($sm[0]))] = "<img src='$sm[1]' border='0' />";
			if (preg_match('*[A-Z]*', $sm[0]))
			$sms[strtolower(htmlspecialchars($sm[0]))] = "<img src='$sm[1]' border='0' />";
		}
	}
	$handle = fopen($fcbfile['ds_smilie'], "w");
	fwrite($handle, serialize($sms));
	fclose($handle);
}

####### BBCODE NOTICE ##################
function BBCode($string)
{
	$search = array(
    '#\[b\](.*?)\[/b\]#',
    '#\[i\](.*?)\[/i\]#',
    '#\[u\](.*?)\[/u\]#',
	'#\[img\](.*?)\[/img\]#',
    '#\[color=(.*?)\](.*?)\[/color\]#',
	'#\[url=(.*?)\](.*?)\[/url\]#',
	'#\[url\](.*?)\[/url\]#'
	);
	$replace = array(
    '<b>\\1</b>',
    '<i>\\1</i>',
    '<u>\\1</u>',
	'<img src="\\1" border="0">',
    '<font color="\\1">\\2</font>',
	'<a href="\\1" target="_blank">\\2</a>',
	'<a href="\\1" target="_blank">\\2</a>'
	);
	return preg_replace($search , $replace, $string);
}

########### CHECK FLOOD #################
function is_flood($ip, $message)
{
	global $fcbfile;
	$ls = unserialize(file_get_contents($fcbfile['ds_lastshout']));
	return ($ls['ip'] == $ip  AND $ls['message'] == $message) ? true : false;
}

########## REMOVE BAD WORD ##########
function remove_bad_word($text)
{
	global $fcbfile;
	$badword = file($fcbfile['badword']);
	foreach ($badword as $cword)
	{
		$cword = trim($cword);
		$text = preg_replace("#$cword#si", str_repeat('*', strlen(utf8_decode($cword))), $text);
	}
	return $text;
}

###############################
function formattext($text, $b, $i, $u, $font, $color)
{
	if ($b == 'B*')
	{
		$text = '<b>'.$text.'</b>';
	}

	if ($i == 'I*')
	{
		$text = '<i>'.$text.'</i>';
	}
	if ($u == 'U*')
	{
		$text = '<u>'.$text.'</u>';
	}
	if ($font) $k = "face='$font'";
	if ($color) $k .= " color='$color'";
	if ($k) $text = "<font $k>".$text."</font>";
	return $text;
}

############ TIME FORMAT ##########
function fcb_date($dateline)
{
	global $config,$phrase;
	$dmess = date('d', $dateline);
	if ($dmess == date('d', time()))
	{
		$date = $phrase['today'];
	}
	else if ($dmess == date('d', strtotime("-1 day")))
	{
		$date = $phrase['yesterday'];
	}
	else
	{
		$date = date($config['dateformat'], $dateline);
	}
	$time = date($config['timeformat'], $dateline);
	return "<span class='smallfont'><span class='time'>[$date $time] </span></span> ";
}

########### PARSER LINK ################
function parser_link($text, $remove=false, $mask=false)
{
	global $phrase;
	if ($remove)
	{
		return ereg_replace('[[:alpha:]]+://[^<>[:space:]]+[[:alnum:]/]', $phrase['linkremoved'], $text);
	}
	else if ($mask)
	{
		return ereg_replace('[[:alpha:]]+://[^<>[:space:]]+[[:alnum:]/]', '<a target="_blank" href="\\0">'.$phrase['linkmask'].'</a>', $text);
	}
	else
	{
		return ereg_replace('[[:alpha:]]+://[^<>[:space:]]+[[:alnum:]/]', '<a target="_blank" href="\\0">\\0</a>', $text);
	}
}

########## FIND USERNAME ##############
function findusername($userid)
{
	global $fcbfile;
	$shoutsf = file($fcbfile['message']);
	foreach($shoutsf as $shoutlinef)
	{	
		$shoutf = split_shoutline($shoutlinef);
		if ($shoutf['userid'] == $userid)
		{
			return $shoutf['username'];
			break;
		}
	}
}

function split_shoutline($shoutline)
{
	list($shoutsplt['type'],$shoutsplt['userid'],$shoutsplt['username'],$shoutsplt['dateline'],$shoutsplt['message']) = explode(" > ",$shoutline);
	return $shoutsplt;
}

function build_message($shoutline)
{
	global $config,$phrase;
	$shout = split_shoutline($shoutline);
	$text = $shout['message'];
	if ($shout['type'] == 'chat')
	{
		$displaytime = '';
		if ($config['showtime'])
		{
			$displaytime = fcb_date($shout['dateline']);
		}
		$text = $displaytime.' '.$text;
	}
	return $text;
}
########## BUILD CHAT ##############
///////////////
function build_chat($shout)
{
	global $config,$command,$phrase,$smilies,$managegroup;
	
	$checkmecm = substr($shout['message'], 0, strlen($command['me'])+1);
	$isme = false;
	if ($config['use_me'] && $checkmecm == $command['me'].' ')
			
			{
				$isme = true;
				$shout['message'] = substr($shout['message'], strlen($command['me'])+1);
			}
			$shout['message'] = htmlspecialchars($shout['message']);
			#### parser link
			if ($config['removelink'])
			{
				if (in_array($groupid, $managegroup))
				{
					$shout['message'] = parser_link($shout['message']);
				}
				else
				{
					$shout['message'] = parser_link($shout['message'], true);
				}
			}
			else
			{
				if ($config['linkmask'])
				{
					$shout['message'] = parser_link($shout['message'], false, true);
				}
				else
				{
					$shout['message'] = parser_link($shout['message']);
				}
			}
			
			### parser smilies
			$shout['message'] = strtr($shout['message'], $smilies);
			### parser format
			$shout['message'] = formattext($shout['message'], $shout['bold'], $shout['italic'], $shout['underline'], $shout['font'], $shout['color']);

			if ($isme)
			{
				return "isme > $shout[userid] > $shout[username] > $shout[dateline] > *<a href='http://{$config['cbforumlink']}/member.php?u=$shout[userid]' target='_blank'><b>$shout[username]</b></a> $shout[message]*";
			}
			else
			{
				return "chat > $shout[userid] > $shout[username] > $shout[dateline] > <a href='http://{$config['cbforumlink']}/member.php?u=$shout[userid]' target='_blank'><b>$shout[username]</b></a>: $shout[message]";
			}
}

############## BUILD NOTICE #############
function build_notice()
{
	global $fcbfile,$smilies;
	$noticef = file_get_contents($fcbfile['notice']);
	$handle = fopen($fcbfile['ds_notice'],"w");
	if ($noticef)
	{
		$noticef = BBCode($noticef);
		$noticef = strtr($noticef, $smilies);
	}
	fwrite($handle, $noticef);
	fclose($handle);
}

function build_prune1($shout)
{
	global $config,$phrase;
	return "prune1 > $shout[userid] > $shout[username] > $shout[dateline] > <span class='smallfont'>#<b>$shout[username]</b> $phrase[prune]#</span>";
}

function build_prune2($shout)
{
	global $config,$phrase;
	$text = "prune2 > $shout[userid] > $shout[username] > $shout[dateline] > <span class='smallfont'>#<b>$shout[username]</b> $phrase[pruneusernotice] <a href='http://{$config['cbforumlink']}/member.php?u=$shout[pruneuserid]' target='_blank'>$shout[pruneusername]</a>";
	if ($shout['reason']) $text .= ". <i>$phrase[reason]: $shout[reason].</i>";
	$text .= '#</span>';
	return $text;
}
function build_ban($shout)
{
	global $config,$phrase;
	$text = "ban > $shout[userid] > $shout[username] > $shout[dateline] > <span class='smallfont'>#<b>$shout[username]</b> ";
	if (!empty($shout['banusername']))
	{
		$text .= "$phrase[banned_name] <a href='http://{$config['cbforumlink']}/member.php?u=$shout[banuserid]' target='_blank'>$shout[banusername]</a>";
	}
	else
	{
		$text .= "$phrase[banned] <a href='http://{$config['cbforumlink']}/member.php?u=$shout[banuserid]' target='_blank'>$shout[banuserid]</a>";
	}
	if ($shout['reason']) $text .= ". <i>$phrase[reason]: $shout[reason].</i>";
	$text .= '#</span>';
	return $text;
}
function build_unban($shout)
{
	global $config,$phrase;
	$text = "unban > $shout[userid] > $shout[username] > $shout[dateline] > <span class='smallfont'>#<b>$shout[username]</b> ";
	if (!empty($shout['unbanusername']))
	{
		$text .= "$phrase[unbanned_name] <a href='http://{$config['cbforumlink']}/member.php?u=$shout[unbanuserid]' target='_blank'>$shout[unbanusername]</a>";
	}
	else
	{
		$text .= "$phrase[unbanned] <a href='http://{$config['cbforumlink']}/member.php?u=$shout[unbanuserid]' target='_blank'>$shout[unbanuserid]</a>";
	}
	if ($shout['reason']) $text .= ". <i>$phrase[reason]: $shout[reason].</i>";
	$text .= '#</span>';
	return $text;
}

##############################
/*
function build_ds_message()
{
	global $config,$fcbfile;
	$handle = fopen($fcbfile['ds_message'],"w");
	$shouts = file($fcbfile['message']);
	krsort($shouts);
	$count = 0;
	foreach ($shouts as $shout)
	{
		++$count;
		$shout = trim($shout);
		fwrite($handle, '<div style="margin: 3px 0px 3px 0px;">'.build_message($shout).'</div>');
		if ($count == $config['maxmessage']) break;
	}
	fclose($handle);
}
*/
?>