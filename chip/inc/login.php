<? if(!defined('CHIP_ROOT')) die ('Hello pro!')?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Đăng nhập</title>
</head>
<link rel="stylesheet" type="text/css" href="style.css">
<body>

<form action="" method="post">
	<table width="257" align="center">
  <tr>
    <td colspan="2" align="center" valign="middle" class="fr_top">Login</td>
    </tr>
  <tr>
    <td width="101" class="fr">Username:</td>
    <td width="144" class="fr_2"><input name="user" type="text" id="user"></td>
  </tr>
  <tr>
    <td class="fr">Password:</td>
    <td class="fr_2"><input name="pass" type="password" id="pass"></td>
  </tr>
  <tr>
    <td colspan="2" align="center" class="fr"><input name="cmd" type="submit" id="cmd" value="Login"></td>
  </tr>
</table>
</form>
<center><a href="javascript:history.go(-1)">Quay lại</a></center>
<?
if($_POST["cmd"])
{

		$data = file($root['chatdata']."user.txt");
		$pass = md5($_POST["pass"]);
		if(strtoupper($_POST["user"])==strtoupper($root['user']) && strtoupper($_POST["pass"])==strtoupper($root['pass']))
		{
				$_SESSION["login"]='admin';
				$_SESSION["user"]=$root['user'];
				echo "<center>Chào mừng: ".$root["user"]." đã đăng nhập thành công!";
				echo "<br>Kich vào <a target='_top' href=?admin><b><font color=red>đây</font></b></a> nếu ko muốn đợi lâu!";
				echo "<script>setTimeout(\"window.location='?admin';\", 3000);</script></center>";
		}
		else
		{
			$str   = $_POST["user"].'*|*'.md5($_POST["pass"]);
			foreach($data as $value)
			{
				if(trim($value))
				{
					$ex = explode('*|*',$value);
					$str_x = $ex[0].'*|*'.$ex[1];
					if(trim($str)==trim($str_x))
					{	
						$_SESSION["login"]='mem';
						$_SESSION["user"]=$_POST["user"];
						echo "<center>Chào mừng: ".$_POST["user"]." đã đăng nhập thành công!";
						echo "<br>Kich vào <a href=?home><b><font color=red>đây</font></b></a> nếu ko muốn đợi lâu!";
						echo "<script>setTimeout(\"window.location='?home';\", 3000);</script></center>";exit();
					}
					
				}
			}
		}
		if(!$_SESSION["login"])
		{
			echo '<center>User và pass ko hợp lệ</center>';
		}
	
	
 
}
?>
</body>
</html>
