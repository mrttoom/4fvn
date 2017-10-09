<? session_start();
if(!defined('CHIP_ROOT')) die ('Hello pro!');
if($_SESSION["login"]!='admin')
{
echo "<meta http-equiv='refresh' content='0;url=?login'>";
}
else
{
$mod=$_GET["mod"];
$act=$_GET["act"];
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title><?=$root['webtitle']?> - Cpanel</title>
<link rel="stylesheet" type="text/css" href="style.css">
<script language="javascript" src="js/him.js"></script>
</head>

<?

	switch($act){
		case "nav":
        	echo" <div id=navar>
			<div class=navar_title>Admin Control Panel</div>
			<div class=navar_link><a target=_blank href=?home>Trang chủ</a> |
			<a  target=_top href=?admin&act=logout>Thoát</a>
			</div></div>";
		break;
		case "left":
			echo'
			<div id="menu">
				<div class="title_box">Quản lý</div>
					<div class="noidung"> 
						<a href="?home" target="main">Phòng chat</a><br>
						<a href="?admin&act=site_config" target="main">Cấu hình chatbox</a><br>
						<a href="?admin&act=manage_smilies" target="main">Quản lý smilies</a><br>
						<a href="?admin&act=ban" target="main">Danh sách banned</a><br>
					</div>
				<div class="chip">chiplove.9xpro@yahoo.com</div>	
			</div>
		';
		break;
		case "main":echo"<center>Welcome to Admin Control Panel</center>";break;
		case "logout":session_destroy();echo "<meta http-equiv='refresh' content='0;url=?login'>";break;
		case "site_config":include("site_config.php"); break;
		case "manage_smilies":include("manage_smilies.php"); break;
		case "ban":include("ban.php"); break;
		
	}
	if(!$act):
?>
<frameset cols="197,*" id="list">
		<frame src="index.php?admin&act=left" name="menu" frameborder="0" noresize />
		<frameset rows="20,*">
			<frame src="index.php?admin&act=nav" frameborder="0" noresize />
			<frame src="index.php?admin&act=main" name="main" id="main" frameborder="0" noresize />
		</frameset>
	</frameset>
 <noframes>
		<body>
			<p>Your browser does not support frames. Please get one that does!</p>
		</body>
	</noframes>
<? endif;}?>