<?php
/*======================================================================*\
|| #################################################################### ||
|| # kBank 2.1
|| # Coded by mrpaint
|| # Contact: mrpaint@gmail.com
|| # I'm a Vietnamese! Thank you for using this script
|| # Last Updated: 21:54 16-09-2008
|| #################################################################### ||
\*======================================================================*/

// ######################## SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE);
if (!is_object($vbulletin->db))
{
	exit;
}
include_once(DIR . '/kbank/functions.php');

function kbank_log_cron_action($message = false) {
	global $nextitem, $cron_logs;
	
	$onelog = false;
	
	if ($onelog) {
		if ($message === false) {
			if (is_array($cron_logs)) {
				log_cron_action(implode('<br/>',$cron_logs),$nextitem);
			}
		} else {
			$cron_logs[] = $message;
		}
	} else {
		if ($message !== false) {
			log_cron_action($message,$nextitem);
		}
	}
}

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################
$vbulletin->db->hide_errors();

//Get statistics
$money = getStatistics(true);

$log = array(
	'type' => KBANK_LOGTYPE_STAT,
	'userid' => 0,
	'timeline' => TIMENOW,
	'text1' => '',
	'int1' => $money['member'],
	'detail' => serialize($money)
);

$vbulletin->db->query_write(fetch_query_sql($log,'kbank_logs'));

// log the cron action
kbank_log_cron_action('Cached kBank Information');

//Any KBANK_ITEM_USED_WAITING items?

$items = $vbulletin->db->query_read("
	SELECT *
	FROM `" . TABLE_PREFIX . "kbank_items` AS items
	WHERE items.status = " . KBANK_ITEM_USED_WAITING . "
		AND items.expire_time <= " . TIMENOW . "
			AND items.expire_time > 0
");

if (!$vbulletin->kbank_itemtypes) {
	$vbulletin->kbank_itemtypes = updateItemTypeCache();
}
if ($vbulletin->db->num_rows($items)) {
	$itemids = array();
	while ($itemdata = $vbulletin->db->fetch_array($items)) {
		if ($item =& newItem($itemdata['itemid'],$itemdata)) {
			if ($status = $item->doAction('work_expired')) {
				$itemids[] = "#$itemdata[itemid] ($status)";
			}
			$item->destroy();
		}
	}
	unset($itemdata);
	$vbulletin->db->free_result($items);
	
	// log the cron action
	kbank_log_cron_action('Processed KBANK_ITEM_USED_WAITING item(s): ' . implode(', ',$itemids));
}

//Clear shout box
$vbulletin->db->query_write("
	DELETE FROM `" . TABLE_PREFIX . "shout`
	WHERE s_time < " . TIMENOW . " - 24*60*60
");
// log the cron action
if ($vbulletin->db->affected_rows() > 0) {
	kbank_log_cron_action('Cleared Shoutbox (affected rows: ' . vb_number_format($vbulletin->db->affected_rows()) . ')');
}

$vbulletin->db->show_errors();

//Real Action!
kbank_log_cron_action();
?>