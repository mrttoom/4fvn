<?php
/*
|| Multiple Account Detection & Prevention 1.1.3
|| ... a modification for vBulletin 3.7.x
|| Created by Kiros (Kiros72 on vBulletin.org)
|| ... with inspiration from MPDev and randominity on vBulletin.org
*/

// MUST format $ids properly for 'where' clause
function get_miniusers($ids)
{
	global $vbulletin;

	$users = $vbulletin->db->query_read_slave("
		SELECT  userid, usergroupid, username, membergroupids
		FROM " . TABLE_PREFIX . "user AS user
		WHERE $ids
		ORDER BY userid
	");

	return $users;
}

function get_ip_miniusers($ipaddress, $prevuserid = 0)
{
	global $vbulletin;

	$users = $vbulletin->db->query_read_slave("
		SELECT  userid, usergroupid, username, membergroupids, joindate
		FROM " . TABLE_PREFIX . "user AS user
		WHERE ipaddress = '" . $vbulletin->db->escape_string($ipaddress) . "' AND
			ipaddress <> '' AND
			userid <> $prevuserid
		ORDER BY username
	");

	return $users;
}

function get_name($id)
{
	global $vbulletin;

	$name = $vbulletin->db->query_read_slave("
		SELECT  username
		FROM " . TABLE_PREFIX . "user AS user
		WHERE userid = {$id}
	");

	return $name;
}

function link_name_plain($name, $id)
{
	global $vbulletin;

	return htmlspecialchars_uni($name) . ' (' . $vbulletin->options['bburl'] . '/member.php?u=' . $id . ')';
}

function link_name_url($name, $id)
{
	global $vbulletin;

	return '[URL=' . $vbulletin->options['bburl'] . '/member.php?u=' . $id . ']'. htmlspecialchars_uni($name) . '[/URL]';
}
?>