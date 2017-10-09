<?
	if(!defined('CHIP_ROOT')) die ('Hello pro!');
	$data = file($root['chatdata'].'chip_smilies.txt');
	echo '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Smilies</title>
<link rel="stylesheet" type="text/css" href="style.css">  
<script src="js/function.js" language="javascript"></script>
	<table width="100%" border="0"><tr>';
	$i=0;
	foreach($data as $value)
	{
		$i++;
		$ex = explode(' => ',$value);
		$kytu = str_replace('"','\\"',$ex[0]);
		echo '<td class="fr_2"><a href=\'javascript:addsmile("'.$kytu.'");\'><img src="'.trim($ex[1]).'" border=0></a></td>';
		if($i%5==0)echo '</tr>';
	}
	if($i%5!==0){echo '</tr>';}
	echo '</table>';
?>


