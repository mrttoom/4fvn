<?php
error_reporting(E_ALL & ~E_NOTICE & ~8192);
require_once('config.php');
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
<head>
	<meta http-equiv="Pragma" content="no-cache" />
	<meta http-equiv="Expires" content="-1" />
	<meta http-equiv="Cache-Control" content="no-cache" />
	<link rel="stylesheet" type="text/css" href="style.css" />
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<script type="text/javascript" src="script.js"></script>
</head>

<body>  


<?php
echo '<div id="progress" style="position: fixed; right: 0px; top: 0px; display: none;">',$phrase['load'],'</div>';
echo '<div id="messresult">';
require_once('message.php');
echo '</div>';
?>

<script language="Javascript">
var ajax = new sack();

function whenLoading(){
	document.getElementById('progress').style.display="inline";
}


function whenCompleted(){
	document.getElementById('messresult').innerHTML = ajax.response;
	document.getElementById('progress').style.display="none";
	<?php
	if ($config['new_at_bottom']) echo "window.scrollTo(0,99999999999);";
	?>
}

function message_refresh(){
	ajax.requestFile = 'message.php';
	ajax.onLoading = whenLoading;
	ajax.onCompletion = whenCompleted;
	ajax.runAJAX();
}
autor = setInterval("message_refresh()", 1000*<?php echo $config['autorefresh']; ?>);
<?php
	if ($config['new_at_bottom']) echo "window.scrollTo(0,99999999999);";
?>
</script>

</body>
</html>