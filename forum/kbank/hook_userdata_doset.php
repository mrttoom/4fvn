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
if (defined('VB_AREA')
	AND $this->registry->kbank['award']['addpost']
	AND VB_AREA == 'AdminCP'
	AND IN_CONTROL_PANEL === true
	AND strtolower(basename($_SERVER['SCRIPT_NAME'])) == 'misc.php'
	AND $_REQUEST['do'] == 'updateposts'
	AND $fieldname == 'posts'
	AND isset($this->existing[$this->registry->kbank['award']['thanksenttimes']])) {	
	$value += $this->existing[$this->registry->kbank['award']['thanksenttimes']];
}
?>