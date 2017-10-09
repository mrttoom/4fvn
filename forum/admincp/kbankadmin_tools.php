<?php
/*======================================================================*\
|| #################################################################### ||
|| # kBank 2.5
|| # Coded by mrpaint
|| # Contact: mrpaint@gmail.com
|| # I'm a Vietnamese! Thank you for using this script
|| # Last Updated: 01:08 04-04-2009
|| #################################################################### ||
\*======================================================================*/
error_reporting(E_ALL & ~E_NOTICE);
define('THIS_SCRIPT', 'kbankadmin_tools');

// #################### PRE-CACHE TEMPLATES AND DATA ######################
$phrasegroups = array('kbank');
$specialtemplates = array();

// ########################## REQUIRE BACK-END ############################
include_once('./global.php');
include_once(DIR . '/kbank/functions.php');
include_once(DIR . '/includes/functions_misc.php');

// ###################### Check Permission ########################
if (!havePerm($vbulletin->userinfo,KBANK_PERM_ADMIN)) {
	print_stop_message('kbank_no_permission');
}

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

($hook = vBulletinHook::fetch_hook('kbankadmin_tools_main')) ? eval($hook) : false;

if ($_GET['do'] == 'list') {
	print_cp_header($vbphrase['kbank_advanced_tools']);
	print_table_start();
	print_table_header($vbphrase['kbank_advanced_tools']);
	
	$tools = array();
	($hook = vBulletinHook::fetch_hook('kbankadmin_tools_list')) ? eval($hook) : false;
	if (count($tools) == 0)
	{
		print_description_row($vbphrase['kbank_advanced_tools_no_installed']);
	}
	else
	{
		foreach ($tools as $toolcode => $toollinks)
		{
			if (is_array($toollinks))
			{
				$availabled = false;
				$toolname = iif(isset($vbphrase['kbank_' . $toolcode]),$vbphrase['kbank_' . $toolcode],strtoupper($toolcode));
				print_description_row("<strong>$toolname</strong>",0,2,'thead');
				foreach ($toollinks as $actioncode => $available)
				{
					if ($available === true)
					{
						$availabled = true;
						$actionname = iif(isset($vbphrase['kbank_' . $toolcode . '_' . $actioncode]),$vbphrase['kbank_' . $toolcode . '_' . $actioncode],strtoupper($actioncode));
						print_description_row("<a href=\"kbankadmin_tools.php?do=$actioncode\">$actionname</a>");
					}
				}
				if (!$availabled)
				{
					print_description_row($vbphrase['kbank_advanced_tool_no_availabled']);
				}
			}
		}
	}
	print_table_footer();
	print_cp_footer();
}
?>