<? if(!defined('CHIP_ROOT')) die ('Hello pro!');
$data_banip = explode('*|*',file_get_contents($root['chatdata']."banip.txt"));
foreach($data_banip as $value)
{
	if($_SERVER['REMOTE_ADDR']==$value) die ("Your IP: ".$_SERVER['REMOTE_ADDR']." blocked by ".$root['user']." - <a href='javascript:history.go(-1)'>Go back</a>");		
}
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Đăng nhập</title>
</head>
<link rel="stylesheet" type="text/css" href="style.css">
<body>

<form action="" method="post">
	<table width="288" align="center">
  <tr>
    <td colspan="2" align="center" valign="middle" class="fr_top">Đăng ký tài khoản</td>
    </tr>
  <tr>
    <td width="132" class="fr">Nick name</td>
    <td width="144" class="fr_2"><input name="user" type="text" id="user"></td>
  </tr>
  <tr>
    <td class="fr">Mật khẩu</td>
    <td class="fr_2"><input name="pass" type="password" id="pass"></td>
  </tr>
   <tr>
    <td class="fr">Nhập lại mật khẩu</td>
    <td class="fr_2"><input name="pass2" type="password" id="pass2"></td>
  </tr>
  <tr>
    <td colspan="2" align="center" class="fr"><input name="cmd" type="submit" id="cmd" value="Đăng ký"></td>
  </tr>
</table>
</form>
<center><a href="javascript:history.go(-1)">Quay lại</a></center>
<?
if($_POST["cmd"])
{
	if(preg_match('^(\*\|\*)^',$_POST["user"]))
	{
		echo '<center>Bạn ko đc sử dụng ký tự *|* trong nick</center>';
	}
	elseif(strlen($_POST["user"])>15)
	{
		echo '<center>Nick ko đc dài quá 15 ký tự</center>';
	}
	
	elseif($_POST["pass"]!=$_POST["pass2"])
	{
		echo '<center>2 ô mật khẩu nhập ko giống nhau</center>';exit();
	}
	else
	{

		$data = file($root['chatdata'].'user.txt');
		if(strtoupper($_POST["user"])==strtoupper($root['user']))
		{
			echo '<center>Nick này có người dùng rồi</center>'; exit();
		}
		else
		{
			foreach($data as $value)
			{
				$ex = explode('*|*',$value);
				if(strtoupper($ex[0])==strtoupper($_POST["user"]))
				{
					echo '<center>Nick này có người dùng rồi</center>'; exit();
				}
			}
			if(preg_match('/('.str_replace(',','|',$chat['block']).')/i', $_POST["user"], $m))
			{
				echo '<center>Nick ko được chứa từ nhạy cảm. ('.$m[1].')';exit();
			}
			else
			{
				$f = fopen($root['chatdata']."user.txt", "a");
				$pass = md5($_POST["pass"]);
				fwrite($f,$_POST["user"]."*|*".$pass."\n");
				if(fwrite)
				{
					$_SESSION["login"]='mem';
					$_SESSION["user"]=$_POST["user"];
					echo'<center>Đã tạo thành công<br>
					Click vào <a href=?home><font color=red><b>đây</b></font></a> để quay lại chat chit</center>';
				}
				fclose($f);
			}
		}
	}
 
}
?>
</body>
</html>
