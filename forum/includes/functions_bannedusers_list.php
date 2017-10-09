<?php

/**
* Helper function for the banned users list modification.
* This takes an array of user data from the database and returns properly formatted ban information.
* 
* @param  array  $user  User information array
* @return array
*/
function bannedusers_list_row($user)
{
	global $vbulletin, $vbphrase;

	if ($user['liftdate'] == 0)
	{
		$user['banperiod'] = $vbphrase['permanent'];
		$user['banlift'] = $vbphrase['never'];
		$user['banremaining'] = $vbphrase['forever'];
	}
	else
	{
		$user['banlift'] = vbdate($vbulletin->options['dateformat'] . ', ' . $vbulletin->options['timeformat'], $user['liftdate']);
		$user['banperiod'] = ceil(($user['liftdate'] - $user['bandate']) / 86400);
		$user['banperiod'] .= ($user['banperiod'] == 1) ? " $vbphrase[day]" : " $vbphrase[days]";
		$user['banremaining'] = $user['liftdate'] - TIMENOW;
		$user['banremaining_days'] = floor($user['banremaining'] / 86400);
		$user['banremaining_hours'] = ceil(($user['banremaining'] - ($user['banremaining_days'] * 86400)) / 3600);

		if ($user['banremaining_hours'] == 24)
		{
			$user['banremaining_days'] += 1;
			$user['banremaining_hours'] = 0;
		}

		if ($user['banremaining_days'] < 0)
		{
			$user['banremaining'] = "<em>$vbphrase[will_be_lifted_soon]</em>";
		}
		else
		{
			$word['day'] = ($user['banremaining_days'] == 1) ? $vbphrase['day'] : $vbphrase['day'];
			$word['hours'] = ($user['banremaining_hours'] == 1) ? $vbphrase['hour'] : $vbphrase['hours'];
			$user['banremaining'] = "$user[banremaining_days] $word[day], $user[banremaining_hours] $word[hours]";
		}
	}

	$return = array(
		'username' => '<a href="member.php?' . $vbulletin->session->vars['sessionurl'] . "u=$user[userid]\">$user[username]</a>",
	);

	if ($user['bandate'])
	{
		$return['bannedby'] = ($user['adminid']) ? '<a href="member.php?' . $vbulletin->session->vars['sessionurl'] . "u=$user[adminid]\">$user[adminname]</a>" : $vbphrase['n_a'];
		$return['bannedon'] = vbdate($vbulletin->options['dateformat'], $user['bandate']);
	}
	else
	{
		$return['bannedby'] = $vbphrase['n_a'];
		$return['bannedon'] = $vbphrase['n_a'];
	}

	$return['banperiod'] = $user['banperiod'];
	$return['banlift'] = $user['banlift'];
	$return['banremaining'] = $user['banremaining'];
	$return['banreason'] = (!empty($user['reason']) ? $user['reason'] : $vbphrase['n_a']);

	return $return;
}

?>