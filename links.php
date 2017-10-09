<?
foreach ($_GET as $key => $value) {
	$site .= $key."=".$value."&";
}
$site = htmlentities( substr($site, 4, -1) );
?>
<meta http-equiv="refresh" content="5; URL=<? echo $site; ?>">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Yêu cầu xác nhận trước khi chuyển tới trang đích</title>
<style>
#warning {
margin: 3% 14%;
border: 1px solid #C3C6C9;
background-color: #F3F6F9;
font-family: Verdana, Arial, Helvetica, sans-serif;
font-size: 13px;
text-align: center;
}
p {
padding: .5em 0;
}
a:link
{
color: #23497C;
}
a:visited
{
color: #23497C;
}
a:hover, a:active
{
color: #FF6633;
}
</style>

</head>
<body>
	
<div id="warning">
<p><strong>Bạn đã nhấn vào một liên kết không thuộc <font color=red><b>4fvn.com</font></b><br />
Liên kết này được cung cấp bởi người dùng và không được xác nhận là an toàn.</strong></p>
<p><strong><font color=red><b>4fvn.com</b></font> không chịu trách nhiệm về nội dung cũng như những nguy hại<br />
tới người dùng có thể gây ra bằng việc chuyển tới liên kết này.</strong></p>
<p><strong>Liên kết sẽ được tự động chuyển tới:</strong></p>
<p><? echo $site; ?> </p>
<p><strong>Sau 5 giây nếu bạn không lựa chọn!</strong></p>
<center><img src="http://4fvn.com/forum/iqh/ok.gif"></center>
	<center>
<script type="text/javascript">var _awc={widget_id:2376,h:90,w:728};</script><script type="text/javascript" src="http://s0.adnet.vn/widget.2.js"></script>
</center>
    <b></b><a rel="nofollow" href="<? echo $site; ?>">[Tôi đồng ý chuyển tới liên kết đã nhấn]</a>&nbsp;&nbsp;<a href="javascript: self.close();">[Tôi không đồng ý, hãy đóng cửa sổ này lại]</a>
    
<center>
<script type="text/javascript">var _awc={widget_id:2376,h:90,w:728};</script><script type="text/javascript" src="http://s0.adnet.vn/widget.2.js"></script>
</center>
    
	
</div>
</body>
</html>
