<? session_start();
######################################
##		Code Chip Chat Room 		##
##			Version 2.0				##
##		Update: 10/8/2010			##
##		Code by chiplove.9xpro		##
##		Ym: chiplove.9xpro			##
######################################

define('CHIP_ROOT',true);
include 'inc/config.php';
include 'inc/functions.php';

?> 
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Chip Chat Room 2.0 - chiplove.9xpro</title>
<link href="style.css" type="text/css" rel="stylesheet" />
<script type="text/javascript" src="js/function.js"></script>
<script type="text/javascript">
function ajax_load(url,id){
	var ajax = new AJAX_Handler();
	ajax.onreadystatechange(change_);
	ajax.send(url);		
	function change_(){
		if(ajax.handler.readyState == 4 && ajax.handler.status == 200)
			document.getElementById(id).innerHTML = ajax.handler.responseText;
	}
}
function _load(value){
	if(value == 'content'){
		ajax_load('content.php','content');
		var objDiv = document.getElementById("main");
		objDiv.scrollTop = objDiv.scrollHeight;
	}
	else if(value == 'online')
		ajax_load('online.php','userlist');	
}

function _refresh(value){
	if(value == 'content')
		setTimeout("_load('content');_refresh('content');", <?=$config['refresh_content']?>000);	
	else if(value == 'online')	
		setTimeout("_load('online');_refresh('online');", <?=$config['refresh_online']?>000);
	
}
var objDiv = document.getElementById("main");
objDiv.scrollTop = objDiv.scrollHeight;

</script>
</head>

<body>
<?
switch($_GET['param']):
	case 'smilies': include 'smilies.php'; break;
	case 'sign': include 'sign.php'; break;
	case 'logout': session_destroy(); print_cp_mess('./','',0);break;
	default:
?>
<div id="wrapper">
	<div id="nav"> 
    	<div id="nav_link">
        	<a href="./">Home</a> | 
            <a href="javascript:ajax_load('?param=sign&act=up','main')">Đăng ký</a> | 
            <a href="javascript:ajax_load('?param=sign&act=in','main')">Đăng nhập</a>
        </div>
        <div id="welcome">
        <? if($_SESSION['user_login']) 
			echo 'Chào mừng '.$_SESSION['user_login'].'! <a href="?param=logout">Thoát</a>';
		?>
        </div>
    </div><!--/#nav-->
    <div id="main">
   		<div id="content">
        	Loading...
    	</div><!--/#content-->
    </div><!--/#main-->
    
    <div id="userlist">
   		Loading...
    </div><!--/#userlist-->
     
    <div id="shoutform">
   		<form name="chatform" method="post" action="javascript:check_form();">
        <div id="upstyle">
            <input id="upb" onclick="upstyle('b')" type="button" class="sbutton" style="font-weight:bold" value="B" />
            <input id="upi" onclick="upstyle('i')" type="button" class="sbutton" style="font-style:italic" value="I" />
            <input id="upu" onclick="upstyle('u')" type="button" class="sbutton" style="text-transform:uppercase" value="U" />
            <select id="upfont">
                <option value="">Default</option>
                <option value="Arial" style="font-family:Arial">Arial</option>
                <option value="Arial Black" style="font-family:Arial Black">Arial Black</option>
                <option value="Book Antiqua" style="font-family:Book Antiqua">Book Antiqua</option>
                <option value="Century Gothic" style="font-family:Century Gothic">Century Gothic</option>
                <option value="Comic Sans MS" style="font-family:Comic Sans MS">Comic Sans MS</option>
                <option value="Courier New" style="font-family:Courier New">Courier New</option>
                <option value="Impact" style="font-family:Impact">Impact</option>
                <option value="Tahoma" style="font-family:Tahoma">Tahoma</option>
                <option value="Times New Roman" style="font-family:Times New Roman">Times New Roman</option>
                <option value="Trebuchet MS" style="font-family:Trebuchet MS">Trebuchet MS</option>
                <option value="Verdana" style="font-family:Verdana">Verdana</option>
            </select>
            <select id="upcolor">
                <option value="">Default</option>
                <option style="background: Gold;" value="Gold">Gold</option>
                <option style="background: Khaki;" value="Khaki">Khaki</option>
                <option style="background: Orange;" value="Orange">Orange</option>
                <option style="background: LightPink;" value="LightPink">LightPink</option>
                <option style="background: Salmon;" value="Salmon">Salmon</option>
                <option style="background: Tomato;" value="Tomato">Tomato</option>
                <option style="background: Red;" value="Red">Red</option>
                <option style="background: Brown;" value="Brown">Brown</option>
                <option style="background: Maroon;" value="Maroon">Maroon</option>
                <option style="background: DarkGreen;" value="DarkGreen">DarkGreen</option>
                <option style="background: DarkCyan;" value="DarkCyan">DarkCyan</option>
                <option style="background: LightSeaGreen;" value="LightSeaGreen">LightSeaGreen</option>
                <option style="background: LawnGreen;" value="LawnGreen">LawnGreen</option>
                <option style="background: MediumSeaGreen;" value="MediumSeaGreen">MediumSeaGreen</option>
                <option style="background: BlueViolet;" value="BlueViolet">BlueViolet</option>
                <option style="background: Cyan;" value="Cyan">Cyan</option>
                <option style="background: Blue;" value="Blue">Blue</option>
                <option style="background: DodgerBlue;" value="DodgerBlue">DodgerBlue</option>
                <option style="background: LightSkyBlue;" value="LightSkyBlue">LightSkyBlue</option>
                <option style="background: White;" value="White">White</option>
                <option style="background: DimGray;" value="DimGray">DimGray</option>
                <option style="background: DarkGray;" value="DarkGray">DarkGray</option>
                <option style="background: Black;" value="Black">Black</option>
            </select>
            <input type="button" class="sbutton" value="Smilies" onclick="smiliepopup();" />
        </div> <!--/#upstyle-->
        <div id="upwrite">
        	<div style="float:left"><input maxlength="255" type="text" id="uptext"/></div>
            <div style="float:right"><input type="submit" id="submitform" value="Send" /></div>
            <input type="hidden" id="name" value="<?=$_SESSION['user_login']?>" />
            <input type="hidden" id="ip" value="<?=$_SERVER['REMOTE_ADDR']?>" />
        </div><!--/#upwrite-->
        </form>
    </div><!--/#shoutform-->
</div><!--/#wrapper-->
<script>
_load('content');
_load('online');
_refresh('content');
_refresh('online');
</script>
<? endswitch;?>
</body>
</html>