<?php
/*======================================================================*\
|| #################################################################### ||
|| # kBank 2.4
|| # Coded by mrpaint
|| # Contact: mrpaint@gmail.com
|| # I'm a Vietnamese! Thank you for using this script
|| # Last Updated: 03:25 29-03-2009
|| #################################################################### ||
\*======================================================================*/
include_once(DIR . '/includes/adminfunctions.php');

function kbank_print_cp_header($title = '', $onload = '', $headinsert = '', $marginwidth = 0, $bodyattributes = '')
{
	if (VB_AREA == 'AdminCP')
	{
		//back-end
		print_cp_header($title,$onload,$headinsert,$marginwidth,$bodyattributes);
	}
	else
	{
		//font-end
		global $title_extra, $navbits;
		$title_extra = $title;
		$navbits[] = $title;
	}
}

function kbank_print_cp_footer()
{
	if (VB_AREA == 'AdminCP')
	{
		//back-end
		print_cp_footer();
	}
	else
	{
		//font-end
		//do nothing
	}
}


function kbank_print_stop_message() 
{
	global $vbulletin;
	
	$args = func_get_args();
	
	if (VB_AREA == 'AdminCP')
	{
		//back-end
		call_user_func_array('print_stop_message',$args);
	}
	else
	{
		//font-end
		$message = call_user_func_array('fetch_error',$args);
		
		if (defined('CP_REDIRECT'))
		{
			$vbulletin->url = CP_REDIRECT;
			eval(print_standard_redirect($message, false, true));
		}
		else
		{
			eval(standard_error($message));
		}
	}
}

function kbank_print_table_start($echobr = true, $width = '90%', $cellspacing = 0, $id = '')
{
	if (VB_AREA == 'AdminCP')
	{
		//back-end
		print_table_start($echobr,$width,$cellspacing,$id);
	}
	else
	{
		//front-end
		print_table_start($echobr,'100%',$cellspacing,$id);
	}
}

function kbank_print_form_header($phpscript = '', $do = '', $uploadform = false, $addtable = true, $name = 'cpform', $width = '90%', $target = '', $echobr = true, $method = 'post', $cellspacing = 0)
{
	global $vbulletin, $tableadded;
	
	if (VB_AREA == 'AdminCP')
	{
		//back-end
		print_form_header($phpscript,$do,$uploadform,$addtable,$name,$width,$target,$echobr,$method,$cellspacing);
	}
	else
	{
		//font-end
		print_form_header($phpscript,$do,$uploadform,$addtable,$name,'100%',$target,$echobr,$method,$cellspacing);
	}
}
?>