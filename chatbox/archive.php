<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">

<head>

<link rel="stylesheet" type="text/css" href="style.css" />

<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

<script language="javascript">

function gopage(page)

{

	location="archive.php?page="+document.fcb_archiveform.pagechoose.value;

}

function gopage2(page)

{

	location="archive.php?page="+document.fcb_archiveform2.pagechoose.value;

}

</script>



<?php

error_reporting(E_ALL & ~E_NOTICE & ~8192);

include ("config.php");

include ("functions.php");



echo '<title>'.$phrase['archive'].'</title></head></body>';



// Prepare

$smilies = unserialize(file_get_contents($fcbfile['ds_smilie']));



// SHOW

$shouts = file($fcbfile['message']);

krsort($shouts);



$lines = count($shouts);



if ($_GET['page'])
{
    $page = htmlentities(strip_tags($_GET['page']));
}
else
{
    $page = 1;
}



					

$perpage = $config['archive_messageperpage'];

$stc = (($page - 1) * $perpage) + 1;

$enc = $page * $perpage;



$sizepage = ceil($lines / $perpage);



for ($i=1; $i<=$sizepage; $i++)

{

	$checkselect = '';

	if ($i == $page) $checkselect = "selected='selected'";

	$pagelist .= "<option value='$i' $checkselect>$i</option>";

}



echo '<div style="margin: 6px;"><div style="margin: 6px;">';

echo '<form name="fcb_archiveform">Page <select name="pagechoose" onchange="gopage();">';

echo $pagelist;

echo "</select> $page/$sizepage</form></div>";

echo '<table width="100%" border="0" class="tborder" cellpadding="3" cellspacing="1">';



$count = 0;

foreach($shouts as $shout)

{

	++$count;

	$shout = trim($shout);

	if ($count >= $stc AND $count <= $enc)

	{

		echo "<tr><td class='alt'>".build_message($shout)."</td></tr>";

	}

	if ($count >= $enc) break;

}

echo '</table>';

echo '<div style="margin: 6px;"><form name="fcb_archiveform2">Page <select name="pagechoose" onchange="gopage2();">';

echo $pagelist;

echo "</select> $page/$sizepage</form>";

echo '</div></div>';



?>