<?php
/*======================================================================*\
|| #################################################################### ||
|| # kBank 2.0
|| # Coded by mrpaint
|| # Contact: mrpaint@gmail.com
|| # I'm a Vietnamese! Thank you for using this script
|| # Last Updated: 02 00 15-07-2008
|| #################################################################### ||
\*======================================================================*/
if (defined('VB_AREA') && $vbulletin->kbank['enabled']) {
	include_once(DIR . '/kbank/functions.php');
	
	if ($_REQUEST['do'] == 'chooser') {
		//add our input form
		print_form_header('misc', 'updateawardthank');
		print_table_header($vbphrase['kbank_admin_update_awardthank'], 2, 0, 'awardthank');
		print_input_row($vbphrase['number_of_users_to_process_per_cycle'], 'perpage', 500);
		print_submit_row($vbphrase['kbank_admin_update_awardthank']);
	}
	
	// ###################### Start update post counts ################
	if ($_REQUEST['do'] == 'updateawardthank')
	{
		if (empty($vbulletin->GPC['perpage']))
		{
			$vbulletin->GPC['perpage'] = 500;
		}

		$finishat = $vbulletin->GPC['startat'] + $vbulletin->GPC['perpage'];

		echo '<p>' . $vbphrase['kbank_admin_updating_awardthank'] . '</p>';

		$users = $db->query_read("
			SELECT *
			FROM " . TABLE_PREFIX . "user
			WHERE userid >= " . $vbulletin->GPC['startat'] . "
			ORDER BY userid
			LIMIT " . $vbulletin->GPC['perpage']
		);

		$finishat = $vbulletin->GPC['startat'];

		while ($user = $db->fetch_array($users))
		{
			$award = $db->query_first("
				SELECT 
					COUNT(*) AS count
					,SUM(abs(awarded.amount)) AS total
				FROM (
					SELECT 
						postid
						,SUM(amount) AS amount
					FROM `" . TABLE_PREFIX . "kbank_donations`
					WHERE postid <> 0
						AND `from` = 0
						AND `to` = $user[userid]
					GROUP BY postid
				) AS awarded
				WHERE awarded.amount <> 0
			");
			$thanks = $db->query_read("
				SELECT 
					IF(`from` = $user[userid],'sent','received') AS `type`
					,COUNT(*) AS count
					,SUM(abs(amount)) AS total
				FROM `" . TABLE_PREFIX . "kbank_donations`
				WHERE 
					postid <> 0
					AND (
						(`from` = $user[userid])
						OR (`from` <> 0 AND `to` = $user[userid])
					)
				GROUP BY `type`
			");

			$userdm =& datamanager_init('User', $vbulletin, ERRTYPE_CP);
			$userdm->set_existing($user);
			if ($award) {
				$userdm->set($vbulletin->kbank['award']['awardedtimes'], $award['count']);
				$userdm->set($vbulletin->kbank['award']['awardedamount'], $award['total']);
			}
			while ($thank = $db->fetch_array($thanks)) {
				$userdm->set('kbank_thank' . $thank['type'] . 'times', $thank['count']);
				$userdm->set('kbank_thank' . $thank['type'] . 'amount', $thank['total']);
			}
			$userdm->save();
			unset($userdm);
			$db->free_result($thanks);

			echo construct_phrase($vbphrase['processing_x'], $user['userid']) . "<br />\n";
			vbflush();

			$finishat = ($user['userid'] > $finishat ? $user['userid'] : $finishat);
		}

		$finishat++;

		if ($checkmore = $db->query_first("SELECT userid FROM " . TABLE_PREFIX . "user WHERE userid >= $finishat LIMIT 1"))
		{
			print_cp_redirect("misc.php?" . $vbulletin->session->vars['sessionurl'] . "do=updateawardthank&startat=$finishat&pp=" . $vbulletin->GPC['perpage']);
			echo "<p><a href=\"misc.php?" . $vbulletin->session->vars['sessionurl'] . "do=updateawardthank&amp;startat=$finishat&amp;pp=" . $vbulletin->GPC['perpage'] . "\">" . $vbphrase['click_here_to_continue_processing'] . "</a></p>";
		}
		else
		{
			define('CP_REDIRECT', 'misc.php');
			print_stop_message('kbank_admin_updated_awardthank_successfully');
		}
	}
}
?>