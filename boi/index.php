<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="Keywords" content="Kênh Giải Trí"/>
<meta http-equiv="Content-Language" content="en-US"/>
<meta name="copyright" content="Copyright © 2009 by bin kenvil"/>
<meta name="abstract" content="Trao đổi kinh nghiệm IT"/>
<meta name="distribution" content="Global"/>
<meta name="robots" content="index,follow"/>
<meta http-equiv="refresh" content="1800"/>
<meta name="author" content="4fvn.com -- http://4fvn.com"/>
<meta name="RATING" content="GENERAL"/>
<title>4Fvn.Com - bói toán</title>
<link href="style.css" rel="stylesheet" type="text/css" />
</head>
<script type="text/javascript" src="jquery-1.2.6.pack.js"></script>
<script type="text/javascript" src="ddaccordion.js"></script>
<script type="text/javascript" src="webname.js"></script>
<body>
<table align="center" cellpadding="0" style="width: 850px;max-width:850px;background-color:#000;">
	<tbody><tr>
	<td colspan="2" align="center" style="font-size:20px;background:#FF9900;">
	<script>document.write(tenweb);</script></td></tr>
	<tr><td colspan="2">
</td></tr>
	<tr id="tMain" valign="top" class="main">
    	<td id="NavTd" style="width: 200px">
<div class="urbangreymenu">

<h3 class="headerbar"><a href="javascript:void()">Bói ngày sinh</a></h3>
		  <ul class="submenu">
			<?php
			$ngaysinh_id = file('./ngaysinh_id.php');
			$ngaysinh_name = file('./ngaysinh_name.php');
			foreach ($ngaysinh_id as $ngaysinh_num => $ngaysinh_ids) {
				echo '<li><a href="?boi='.$ngaysinh_id[$ngaysinh_num].'" id="'.$ngaysinh_id[$ngaysinh_num].'" class="ItemNoselected" >'.$ngaysinh_name[$ngaysinh_num].'</a></li>';
			if (end($ngaysinh_id)) {
								}
			}
			?>
		</ul>

<h3 class="headerbar"><a href="javascript:void()">Bói về họ tên</a></h3>
	<ul class="submenu">
		<?php
			$hoten_id = file('./hoten_id.php');
			$hoten_name = file('./hoten_name.php');
			foreach ($hoten_id as $hoten_num => $hoten_ids) {
				echo '<li><a href="?boi='.$hoten_id[$hoten_num].'" id="'.$hoten_id[$hoten_num].'" class="ItemNoselected" >'.$hoten_name[$hoten_num].'</a></li>';
			if (end($hoten_id)) {
								}
			}
		?>
	</ul>
<h3 class="headerbar"><a href="javascript:void()">Bói về hình dáng</a></h3>
	<ul class="submenu">
		<?php
			$hinhdang_id = file('./hinhdang_id.php');
			$hinhdang_name = file('./hinhdang_name.php');
			foreach ($hinhdang_id as $hinhdang_num => $hinhdang_ids) {
				echo '<li><a href="?boi='.$hinhdang_id[$hinhdang_num].'" id="'.$hinhdang_id[$hinhdang_num].'" class="ItemNoselected" >'.$hinhdang_name[$hinhdang_num].'</a></li>';
			if (end($hinhdang_id)) {
								}
			}
		?>
	</ul>
    
<h3 class="headerbar"><a href="javascript:void()">Bói tình yêu</a></h3>
	<ul class="submenu">
    	<?php
			$tinhyeu_id = file('./tinhyeu_id.php');
			$tinhyeu_name = file('./tinhyeu_name.php');
			foreach ($tinhyeu_id as $tinhyeu_num => $tinhyeu_ids) {
				echo '<li><a href="?boi='.$tinhyeu_id[$tinhyeu_num].'" id="'.$tinhyeu_id[$tinhyeu_num].'" class="ItemNoselected" >'.$tinhyeu_name[$tinhyeu_num].'</a></li>';
			if (end($tinhyeu_id)) {
								}
			}
		?> 
	</ul> 
<h3 class="headerbar"><a href="javascript:void()">Bói linh tinh</a></h3>
	<ul class="submenu">
    	<?php
			$linhtinh_id = file('./linhtinh_id.php');
			$linhtinh_name = file('./linhtinh_name.php');
			foreach ($linhtinh_id as $linhtinh_num => $linhtinh_ids) {
				echo '<li><a href="?boi='.$linhtinh_id[$linhtinh_num].'" id="'.$linhtinh_id[$linhtinh_num].'" class="ItemNoselected" >'.$linhtinh_name[$linhtinh_num].'</a></li>';
			if (end($linhtinh_id)) {
								}
			}
		?> 
	</ul>


</div>
<script type="text/javascript">

ddaccordion.init({
	headerclass: "headerbar", //Shared CSS class name of headers group
	contentclass: "submenu", //Shared CSS class name of contents group
	revealtype: "click", //Reveal content when user clicks or onmouseover the header? Valid value: "click" or "mouseover
	mouseoverdelay: 200, //if revealtype="mouseover", set delay in milliseconds before header expands onMouseover
	collapseprev: true, //Collapse previous content (so only one open at any time)? true/false
	defaultexpanded: [0], //index of content(s) open by default [index1, index2, etc] [] denotes no content
	onemustopen: true, //Specify whether at least one header should be open always (so never all headers closed)
	animatedefault: false, //Should contents open by default be animated into view?
	persiststate: true, //persist state of opened contents within browser session?
	toggleclass: ["", "selected"], //Two CSS classes to be applied to the header when it's collapsed and expanded, respectively ["class1", "class2"]
	togglehtml: ["", "", ""], //Additional HTML added to the header when it's collapsed and expanded, respectively  ["position", "html1", "html2"] (see docs)
	animatespeed: "normal", //speed of animation: integer in milliseconds (ie: 200), or keywords "fast", "normal", or "slow"
	oninit:function(headers, expandedindices){ //custom code to run when headers have initalized
		//do nothing
	},
	onopenclose:function(header, index, state, isuseractivated){ //custom code to run whenever a header is opened or closed
		//do nothing
	}
})

</script>
</td><td id="mainTd" style="width:650px;background-color:#333333;border: #444444 1px solid;">
<div align="center" style="width:640px;background-color:#333333;" class="div1">

<?php
$xboi = $_GET['boi'];
$xboilink='./data/'.$xboi.'.html';
if ($xboi=="") {
			require_once('./data/ns_phuongtay.html');
		echo '<script>document.getElementById("ns_phuongtay").className="Itemselected";</script>';
}else if (!file_exists($xboilink)){
		echo 'Chúng tôi không tìm thấy phần bói toán mà bạn yêu cầu ! Có thể nó không có trong hệ thống hay đã bị loại bỏ!';
}else{
			require_once($xboilink);
		echo '<script>document.getElementById("'.$xboi.'").className="Itemselected";</script>';
		}	

?>

</div>
		</td>
	</tr>
    <tr class="main"><td colspan="2"><br/>
      <p align="center">&nbsp;</p>
</td></tr>

	<tr>
   		<td colspan="2" align="center"><div id="footer" align="center" style="left: 3px; top: 0px; width: 446px" >
			Bản quyền © 2013 thuộc 4fvn.com.com - Home for everybody <br/>
			Địa chỉ Website: <a href="http://4fvn.com" target="_blank">
			http://4fvn.com</a><br/>
			<br />
		</div>
</td>
	</tr>
</tbody></table>
<div style="display:none;">
</body>

