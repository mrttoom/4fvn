<?php
error_reporting(E_ALL & ~E_NOTICE & ~8192);

require_once("config.php");

if ($_REQUEST['do'] == 'logout')
{
	setcookie("password", "", time()-3600);
	header("Location: ?do=home");
}

if ($_POST['submit_login'])
{
	setcookie("password", $_POST['pass']);
	header("Location: ?do=home");
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<link rel="stylesheet" type="text/css" href="style.css" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<style type="text/css" id="vbulletin_css">
body
{
	background: #E1E1E2;
	color: #000000;
	font: 10pt verdana, geneva, lucida, 'lucida grande', arial, helvetica, sans-serif;
	margin: 10px 10px 10px 10px;
	padding: 0px;
}
</style>
<title>ChangUonDyU - FCB - Tools</title>
</head>

<?php
if ($_COOKIE["password"] != $config['password_tools'])
{
	if (isset($_COOKIE["password"]) AND $_COOKIE["password"] != $config['password_tools'])
	{	
		echo '<center><font color="red"><b>Sai password</b></font></center>';
	}
	echo '<body><form action="" method="post"><center>Password: <input type="password" name="pass"><input type="submit" name="submit_login" value="Login"></center></form></body></html>';
	exit; 
}
?>

<body>
<div align="center">
<div style="width: 868px;" align="left">
<br /><br />

<div align="center"><b>
	<a href="?do=home">Trang chủ</a> | <a href="?do=showsmilies">Tất cả smilie</a> | <a href="?do=formadd">Thêm smilie</a> | <a href="?do=banmanage">Thành viên cấm chat</a> | <a href="?do=notice">Thông báo</a> | <a href="?do=badword">Từ cấm</a></b>
</div>
<div align="center">
	<b><a href="?do=editsmfile">Sửa nhanh file smilie</a></b> | <a href="?do=logout">Thoát</a>
	<br /><br /><br />
</div>
<?php

########### MANAGE ###############
require_once("functions.php");

if (empty($_REQUEST['do'])) $_REQUEST['do'] = 'home';

if ($_REQUEST['do'] == 'home')
{
	echo "<center>";
	echo "<br /><br />";
	echo "<b>Extra File Chatbox Tools version 3.6.0</b><br />";
	echo "by ChangUonDyU</center>";
}

########## SMILIES ############
if ($_REQUEST['do'] == 'showsmilies')
{
	echo '<form name="cfc_smilief" action="?do=savesmilie" method="post">';
	echo '<table width="100%" cellpadding="3" cellspacing="1" border="0" class="tborder">';
	$smliesfile = file($fcbfile['smilie']);
	$count = 0;
	$i = 0;
	foreach ($smliesfile as $smilies)
	{
		$bit = explode(" => ", $smilies);
		if (sizeof($bit) == 2)
		{
		$i++;
		$alt = 1;
		if ($i % 2 == 0) $alt = 2;
		echo "<tr><td class='alt$alt' nowrap='nowrap'><input class='bginput' name='smcode[]' type='text' size='20' value='$bit[0]'> <input class='bginput' onkeyup='update($count);' type='text' name='smpath[]' id='path$count' size='100' value='$bit[1]'></td><td class='alt$alt'><span id='img$count'><img src='$bit[1]'><span></td></tr>";
		$count++;
		}
	}
	echo '</table>';
	echo '<div style="margin: 6px;" align="center"><input type="submit" value="Save" name="submit" class="button"></div>';
	echo '</form>';
}
if ($_REQUEST['do'] == 'savesmilie')
{
	extract($_POST);
	if ($submit)
	{
		$sizelist = sizeof($smcode);
		$handle = fopen($fcbfile['smilie'],"w");
		for ($i = 0; $i<$sizelist; $i++)
		{
			if ($smcode[$i])
			{
				if ($config['strip_slash']) $smcode[$i] = stripslashes($smcode[$i]);
				$data = "$smcode[$i] => $smpath[$i]\n";
				fwrite($handle, $data);
			}
		}
		bulid_smilies();
		echo "Update Successfully";
		fclose($handle);
	}
	echo '<script language="javascript">';
	echo 'location = "?do=showsmilies"';
	echo '</script>';
}
if ($_REQUEST['do'] == 'formadd')
{
	echo '<table width="100%" cellpadding="6" cellspacing="1" border="0" class="tborder"><tr><td class="alt2">';
	echo '<form name="smilieaddpath" action="?do=getsmiliepath" method="post">';
	echo '<div>Nhap dia chi thu muc chua smilie</div>';
	echo '<input class="bginput" type="text" size="50" value="path/to/smilies" name="path">';
	echo '<div style="margin: 6px;"><input class="button" type="submit" value="Get smilies" name="submit"></div>';
	echo '</form>';
	echo "</td></tr><tr><td class='alt1'>";
	echo "The^m bie^u? tuong le?";
	echo '<form name="smilieaddeach" action="?do=addsmilie" method="post">';
	for ($i = 0; $i<20; $i++)
	{
		echo "<div><input name='smcode[]' type='text' class='bginput' size='5' value=''> <input onkeyup='update($i);' type='text' class='bginput' name='smpath[]' id='path$i' size='100' value=''> <span id='img$i'><span></div>\n";
	}
	echo '<div style="margin: 6px;"><input class="button" type="submit" value="Add" name="submit"></div>';
	echo '</form></td></tr></table>';
}
if ($_REQUEST['do'] == 'addsmilie')
{
	extract($_POST);
	if ($submit)
	{
		$sizelist = sizeof($smcode);
		$handle = fopen($fcbfile['smilie'],"a");
			for ($i = 0; $i<$sizelist; $i++)
		{
			if ($smcode[$i] && $smpath[$i])
			{
				if ($config['strip_slash']) $smcode[$i] = stripslashes($smcode[$i]);
				$data = "$smcode[$i] => $smpath[$i]\n";
				fwrite($handle, $data);
			}
		}
		echo "Add Successfully";
		fclose($handle);
		bulid_smilies();
	}
	
	echo '<script language="javascript">';
	echo 'location = "?do=showsmilies"';
	echo '</script>';
}
if ($_REQUEST['do'] == 'getsmiliepath')
{
	extract($_POST);
	if ($submit)
	{
	$i = 0;
	echo '<form name="smilieaddeach" action="?do=addsmilie" method="post">';
	echo '<table width="100%" cellpadding="3" cellspacing="1" border="0" class="tborder">';
		if ($handle = opendir($path))
		{
			while (false !== ($file = readdir($handle)))
			{
				$i++;
				$alt = 1;
				if ($i % 2 == 0) $alt = 2;
				$filetype = strtoupper(substr($file, -3));
				$filename = substr($file, 0, -4);
				if ($filetype == 'JPG' OR $filetype == 'GIF' OR $filetype == 'BMP' OR $filetype == 'PNG')
				{
					echo "<input type='hidden' name='smpath[]' value='http://".$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF'])."/$path/$file'>";
					echo "<tr><td class='alt$alt' nowrap='nowrap'><input name='smcode[]' type='text' class='bginput' size='20' value=':$filename:'> <input disabled='disabled' type='text' size='100' value='http://".$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF'])."/$path/$file'></td><td class='alt$alt'><img src='$path/$file'></td></tr>";
				}
			}
			closedir($handle);
		}
	echo '</table>';
	echo '<div style="margin: 6px;" align="center"><input type="submit" value="Add" name="submit" class="button"></div>';
	echo '</form>';
	}
}

if ($_REQUEST['do'] == 'editsmfile')
{
	echo '<table width="100%" cellpadding="6" cellspacing="1" border="0" class="tborder"><tr><td class="alt1">';
	echo '<form name="cfc_smfile" action="?do=savesmfile" method="post">';
	echo '<textarea rows="20" cols="100" name="smcontent" class="bginput">'.file_get_contents($fcbfile['smilie']).'</textarea>';
	echo '<div style="margin: 6px;" align="center"><input type="submit" value="Save" name="submit" class="button"></div>';
	echo '</form></td></tr></table>';	
}
if ($_REQUEST['do'] == 'savesmfile')
{
	extract($_POST);
	if ($submit)
	{
		$handle = fopen($fcbfile['smilie'],"w");
		if ($config['strip_slash']) $smcontent = stripslashes($smcontent);
		fwrite($handle, $smcontent);
		echo "Update Successfully";
		fclose($handle);
		bulid_smilies();
	}
	echo '<script language="javascript">';
	echo 'location = "?do=editsmfile"';
	echo '</script>';
}

########## BANNED USER ##############
if ($_REQUEST['do'] == 'banmanage')
{
	echo '<table width="100%" cellpadding="6" cellspacing="1" border="0" class="tborder"><tr><td class="alt1">';
	echo '<form name="cfc_banuser" action="?do=saveban" method="post">';
	echo "<div>User ID | Ghi chu' (username, ly do....v.v)</div>";
	$banneds = unserialize(file_get_contents($fcbfile['ds_banned']));
	if ($banneds)
	foreach ($banneds as $key => $message)
	{
		echo "<div><input name='userid[]' type='text' class='bginput' size='5' value='$key'> <input type='text' class='bginput' name='message[]' size='100' value='$message'></div>\n";
	}
	for ($i = 1; $i <=5; $i++)
	{
		echo "<div><input name='userid[]' type='text' class='bginput' size='5' value=''> <input type='text' class='bginput' name='message[]' size='100' value=''></div>\n";
	}
	echo '<div style="margin: 6px;" align="center"><input type="submit" class="button" value="Save" name="submit"></div>';
	echo '</form>';
	echo '</td></tr></table>';
}
if ($_REQUEST['do'] == 'saveban')
{
	extract($_POST);
	if ($submit)
	{
	$sizelist = sizeof($userid);
	$handle = fopen($fcbfile['ds_banned'],"w");
	for ($i = 0; $i<$sizelist; $i++)
	{
		if ($userid[$i])
		{
			if ($config['strip_slash']) $message[$i] = stripslashes($message[$i]);
			$banneds[$userid[$i]] =  $message[$i];
		}
	}
	fwrite($handle, serialize($banneds));
	fclose($handle);
	echo "Update Successfully";
	}
	echo '<script language="javascript">';
	echo 'location = "?do=banmanage"';
	echo '</script>';
}

######## NOTICE ###########
if ($_REQUEST['do'] == 'notice')
{
	echo '<table width="100%" cellpadding="6" cellspacing="1" border="0" class="tborder"><tr><td class="alt1">';
	echo '<div>Sua Thong Bao</div><form name="cfc_notice" action="?do=savenotice" method="post">';
	echo '<textarea rows="10" cols="100" name="noticemess" class="bginput">'.file_get_contents($fcbfile['notice']).'</textarea>';
	echo '<div style="margin: 6px;" align="center"><input type="submit" value="Save" class="button" name="submit"></div>';
	echo '</form></td></tr></table>';	
}
if ($_REQUEST['do'] == 'savenotice')
{
	extract($_POST);
	if ($submit)
	{
		$handle = fopen($fcbfile['notice'],"w");
		if ($config['strip_slash']) $noticemess = stripslashes($noticemess);
		fwrite($handle, $noticemess);
		fclose($handle);
		$smilies = unserialize(file_get_contents($fcbfile['ds_smilie']));
		build_notice();
		echo "Update Successfully";
	}
	echo '<script language="javascript">';
	echo 'location = "?do=notice"';
	echo '</script>';
}

######## BAD WORD ###########
if ($_REQUEST['do'] == 'badword')
{
	echo '<table width="100%" cellpadding="6" cellspacing="1" border="0" class="tborder"><tr><td class="alt1">';
	echo "Ban nhap cac tu+` ca^m', mo^j~ tu` nam tre^n 1 dong`";
	echo '<form name="cfc_badword" action="?do=savebadword" method="post">';
	echo '<textarea rows="10" cols="100" name="badword" class="bginput">'.file_get_contents($fcbfile['badword']).'</textarea>';
	echo '<div style="margin: 6px;" align="center"><input type="submit" value="Save" name="submit" class="button"></div>';
	echo '</form></td></tr></table>';	
}
if ($_REQUEST['do'] == 'savebadword')
{
	extract($_POST);
	if ($submit)
	{
	$handle = fopen($fcbfile['badword'],"w");
	if ($config['strip_slash']) $badword = stripslashes($badword);
	fwrite($handle, $badword);
	echo "Update Successfully";
	fclose($handle);
	}
	echo '<script language="javascript">';
	echo 'location = "?do=badword"';
	echo '</script>';
}

?>


<script language="javascript">
function update(order)
{
	newlink = document.getElementById('path'+order).value;
	document.getElementById('img'+order).innerHTML = "<img src="+newlink+">";
}
</script>

</div>
</div>
</body>
</html>