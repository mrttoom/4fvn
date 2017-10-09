<?php
header('Content-type: text/html;charset=utf-8');
$title = '4Fvn.Com Nghe Nhạc';
$dscon = 'nghe nhạc mp3 chất lượng cao trực tuyến miễn phí nhanh nhất việt nam';
$words = 'nghe nhạc, album, playlist, nhạc trẻ, hải ngoại, trữ tình, nhạc không lời';
$nhacf = 'nghe tab';
if(isset($_GET['nhacf'])&&!isset($_GET['nhacf']{99})){
    function Noo($s){$s = str_replace(array('à','á','ạ','ả','ã','â','ầ','ấ','ậ','ẩ','ẫ','ă','ằ','ắ','ặ','ẳ','ẵ'),'a',$s);$s = str_replace(array('è','é','ẹ','ẻ','ẽ','ê','ề','ế','ệ','ể','ễ'),'e',$s);$s = str_replace(array('ì','í','ị','ỉ','ĩ'),'i',$s);$s = str_replace(array('ò','ó','ọ','ỏ','õ','ô','ồ','ố','ộ','ổ','ỗ','ơ','ờ','ớ','ợ','ở','ỡ'),'o',$s);$s = str_replace(array('ù','ú','ụ','ủ','ũ','ư','ừ','ứ','ự','ử','ữ'),'u',$s);$s = str_replace(array('ỳ','ý','ỵ','ỷ','ỹ'),'y',$s);$s = str_replace('đ','d',$s);return $s;}
    function Upp($s){$n = array(' à',' á',' ạ',' ả',' ã',' â',' ầ',' ấ',' ậ',' ẩ',' ẫ',' ă',' ằ',' ắ',' ặ',' ẳ',' ẵ',' è',' é',' ẹ',' ẻ',' ẽ',' ê',' ề',' ế',' ệ',' ể',' ễ',' ì',' í',' ị',' ỉ',' ĩ',' ò',' ó',' ọ',' ỏ',' õ',' ô',' ồ',' ố',' ộ',' ổ',' ỗ',' ơ',' ờ',' ớ',' ợ',' ở',' ỡ',' ù',' ú',' ụ',' ủ',' ũ',' ư',' ừ',' ứ',' ự',' ử',' ữ',' ỳ',' ý',' ỵ',' ỷ',' ỹ',' đ',' d',' q',' w',' e',' r',' t',' y',' u',' i',' o',' p',' a',' s',' f',' g',' h',' j',' k',' l',' z',' x',' c',' v',' b',' n',' m');$l = array(' À',' Á',' Ạ',' Ả',' Ã',' Â',' Ầ',' Ấ',' Ậ',' Ẩ',' Ẫ',' Ă',' Ằ',' Ắ',' Ặ',' Ẳ',' Ẵ',' È',' É',' Ẹ',' Ẻ',' Ẽ',' Ê',' Ề',' Ế',' Ệ',' Ể',' Ễ',' Ì',' Í',' Ị',' Ỉ',' Ĩ',' Ò',' Ó',' Ọ',' Ỏ',' Õ',' Ô',' Ồ',' Ố',' Ộ',' Ổ',' Ỗ',' Ơ',' Ờ',' Ớ',' Ợ',' Ở',' Ỡ',' Ù',' Ú',' Ụ',' Ủ',' Ũ',' Ư',' Ừ',' Ứ',' Ự',' Ử',' Ữ',' Ỳ',' Ý',' Ỵ',' Ỷ',' Ỹ',' Đ',' D',' Q',' W',' E',' R',' T',' Y',' U',' I',' O',' P',' A',' S',' F',' G',' H',' J',' K',' L',' Z',' X',' C',' V',' B',' N',' M');return substr(str_replace($n,$l,' '.$s),1);}
    $nhacf = str_replace('-',' - ',$_GET['nhacf']);
    $title = Upp($nhacf).' - '.$title;
    $dewod = $nhacf.', '.Noo($nhacf);
    $dscon = $dewod.', '.$dscon;
    $words = str_replace(' - ',', ',$dewod).', '.$words;
}
?>
<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>
<html><head>



<!--------------- code by 4fvn----------->

<meta http-equiv="content-type" content="text/html;charset=utf-8" />
<meta name="Nghe Nhạc on line" content="4fvn.com" />
<title>4fvn.com nghe nhạc online</title>

<style>
*{margin: 0;padding: 0;}
body{
    font-size: 16px;
    font-family: "Times New Roman", Georgia, serif;
}
#header{
    background: #464646;
}
#header>div{
    width: 960px;
    overflow: hidden;
    margin: 0 auto;
}
#header ul{
    overflow: hidden;
    list-style: none;
    height: 40px;
    line-height: 40px;
}
#header li{
    float: left;
    padding: 0 11px;
}
#header li:hover{
    background: #292929;
}
#header li a{
    color: white;
    text-decoration: none;
}
#nav{
    float: left;
}
#b_login{
    float: right;
}


#login{
    color: #3e3e3e;
    background: black;
    float: left;
    z-index: 9996;
}
#login form{
    background: white;
    padding: 17px;
}
#login form label{
    font-weight: bold;
}
#login form input.text{
    width: 358px;
    height: 30px;
    line-height: 30px;
    color: #777;
    padding: 0 10px;
}
#login form input[type=password]{
    margin-bottom: 5px;
}
#login p#button_l{
    overflow: hidden;
}
#login p#button_l input{
    float: right;
    margin: 0 4px;
    color: white;
    background: #25AAE1;
    border: 0;
    padding: 9px 10px;
}
#login p#button_l input:hover{
    cursor: pointer;
    background: #008eff;
}
</style>
<script>
$(function(){
    $('.inline').colorbox({inline:true});
})
</script>

<style id="jsbin-css">

</style>
</head>
<body>
<div id="header">
    <div>
        <div id="nav">
            <ul>
                <li><a href="http://www.4fvn.com/" target="_blank"> Trang chủ</a></li>
                <li><a href="http://www.4fvn.com/ps" target="_blank">Photoshop Online</a></li>
                
            </ul>

        </div>
        
    </div>

</div>


<script>

</script>









<!--------------- code by 4fvn----------->







<title><?echo $title;?></title>
<meta http-equiv='content-type' content='text/html; charset=UTF-8'/>
<meta content='<?echo $title;?>' name='title'/>
<meta content='<?echo $dscon;?>' name='description'/>
<meta content='<?echo $words;?>' name='keywords'/>
<link href='http://code.nhacf.com/favicon.ico' rel='shortcut icon' type='image/ico'/>
<link rel='stylesheet' type='text/css' href='style.css'/>
</head><body>
<table border='0' cellpadding='0' cellspacing='0'><tr valign='top'>
<td><form id='msic' lang='hôm nay,nghe gần nhất,đánh giá cao,mới nhất,tải nhiều,tìm kiếm nhiều,nghe nhiều,ưa thích,nghe hôm nay,nghe tuần này,nghe tháng này,nghe năm này'>
<span id='musics' lang='nhạc'></span><ul id='lis'></ul>
<input onclick='M.all(this.form,0)' title='Đánh dấu toàn bộ' type='button' value='Chọn Hết'/>
<input onclick='M.all(this.form,2)' title='Bỏ đánh dấu' type='button' value='Bỏ'/>
<input onclick='M.all(this.form,1)' title='Bỏ thành chọn, chọn thành bỏ' type='button' value='Đảo'/>
<input onclick='M.ato()' title='Nghe tự động những bài đã chọn' type='button' value='Nghe Tab'/>
<input onclick='M.xoa(1)' title='Xóa tab đang hiển thị' type='button' value='Xóa Tab'/>
<input onclick='M.xoa(2)' title='Xóa hết các tab' type='button' value='Xóa Hết'/>
</form>
<!-- (cột 1) -->
</td>
<td><div id='gaia'>
<input height='17px' id='search' maxlength='255' onclick='this.select()' onkeyup='M.sea(this.value)' onmouseover='this.focus()' placeholder='Tìm tên ca sĩ, bài hát' type='text'/>
<ul id='bars'>
<li><p class='ico'></p>
<ul class='c'><li style='margin-top:1px;border-top:1px solid #cfd9f7'>
<a onclick='Ae(this)'>Phương Thanh</a>,
<a onclick='Ae(this)'>Bằng Cường</a>,

<a onclick='Ae(this)'>Nguyễn Phi Hùng</a>,
<a onclick='Ae(this)'>Phạm Khánh Hưng</a>,
<a onclick='Ae(this)'>Lâm Chấn Hải</a>,
<a onclick='Ae(this)'>Châu Gia Kiệt</a>,
<a onclick='Ae(this)'>Hoàng Châu</a>,
<a onclick='Ae(this)'>Sỹ Đan</a>,
<a onclick='Ae(this)'>Hồ Thu Phương</a>,
<a onclick='Ae(this)'>Hồ Ngọc Hà</a>,
<a onclick='Ae(this)'>Hồ Quỳnh Hương</a>,

<a onclick='Ae(this)'>Tuấn hưng</a>,
<a onclick='Ae(this)'>Ngô Kiến Huy</a>,
<a onclick='Ae(this)'>Lê Hiếu</a>,
<a onclick='Ae(this)'>Đức Tuấn</a>,
<a onclick='Ae(this)'>Cao Thái Sơn</a>,
<a onclick='Ae(this)'>Phan Đinh Tùng</a>,
<a onclick='Ae(this)'>Hiền Thục</a>,
<a onclick='Ae(this)'>Uyên Trang</a>,
<a onclick='Ae(this)'>Thanh Thảo</a>,

<a onclick='Ae(this)'>Lam Trường</a>,
<a onclick='Ae(this)'>Đan Trường</a>,
<a onclick='Ae(this)'>Cẩm Ly</a>,
<a onclick='Ae(this)'>Mỹ Tâm</a>,
<a onclick='Ae(this)'>Quốc Đại</a>,
<a onclick='Ae(this)'>Minh Thư</a>,
<a onclick='Ae(this)'>Lý Hải</a>,
<a onclick='Ae(this)'>Khánh Phương</a>,
<a onclick='Ae(this)'>Tần Khánh</a>,

<a onclick='Ae(this)'>Thu Thủy</a>,
<a onclick='Ae(this)'>Dương 565</a>,
<a onclick='Ae(this)'>Ưng Hoàng Phúc</a>,
<a onclick='Ae(this)'>Vân Quang Long</a>,
<a onclick='Ae(this)'>Dương Thái Long</a>,
<a onclick='Ae(this)'>Nguyễn Thắng</a>,
<a onclick='Ae(this)'>Wanbi Tuấn Anh</a>,
<a onclick='Ae(this)'>Tống Gia Vỹ</a>,
<a onclick='Ae(this)'>Hoàng Nhật Minh</a>,

<a onclick='Ae(this)'>Khổng Tú Quỳnh</a>,
<a onclick='Ae(this)'>Vy Oanh</a>,
<a onclick='Ae(this)'>Vy Thúy Vân</a>,
<a onclick='Ae(this)'>Chu Bin</a>,
<a onclick='Ae(this)'>Minh Hằng</a>,
<a onclick='Ae(this)'>Minh Thuận</a>,
<a onclick='Ae(this)'>Minh Tuyết</a>,
<a onclick='Ae(this)'>Phương Nghi</a>,
<a onclick='Ae(this)'>Quỳnh Nga</a>,

<a onclick='Ae(this)'>Huyền Thoại</a>,
<a onclick='Ae(this)'>Bảo Thy</a>,
<a onclick='Ae(this)'>Tóc Tiên</a>,
<a onclick='Ae(this)'>Mắt Ngọc</a>,
<a onclick='Ae(this)'>Năm Dòng Kẻ</a>,
<a onclick='Ae(this)'>The Men</a>,
<a onclick='Ae(this)'>Yến Nhi</a>,
<a onclick='Ae(this)'>Uyên Trang</a>,
<a onclick='Ae(this)'>Trúc Linh</a>,

<a onclick='Ae(this)'>Phạm Quỳnh Anh</a>,
<a onclick='Ae(this)'>Phước Thịnh</a>,
<a onclick='Ae(this)'>Đàm Vĩnh Hưng</a>,
<a onclick='Ae(this)'>Lương Bích Hữu</a>,
<a onclick='Ae(this)'>Lương Gia Huy</a>,
<a onclick='Ae(this)'>Nhật Tinh Anh</a>,
<a onclick='Ae(this)'>Nhật Kim Anh</a>,
<a onclick='Ae(this)'>Minh Hằng</a>,
<a onclick='Ae(this)'>M4u</a>,

<a onclick='Ae(this)'>Thủy Tiên</a>,
<a onclick='Ae(this)'>Nam Cường</a>,
<a onclick='Ae(this)'>Thùy Chi</a>,
<a onclick='Ae(this)'>Akira Phan</a>,
<a onclick='Ae(this)'>Vĩnh Thuyên Kim</a>,
<a onclick='Ae(this)'>Hải Băng</a>
</li><li>
<a onclick='Ae(this)'>Chế Linh</a>,
<a onclick='Ae(this)'>Thu Phương</a>,

<a onclick='Ae(this)'>Quang Dũng</a>,
<a onclick='Ae(this)'>Phi Nhung</a>,
<a onclick='Ae(this)'>Lệ Thu</a>,
<a onclick='Ae(this)'>Lệ Quyên</a>,
<a onclick='Ae(this)'>Trần Thu Hà</a>,
<a onclick='Ae(this)'>Vĩnh Trinh</a>,
<a onclick='Ae(this)'>Lưu Bích</a>,
<a onclick='Ae(this)'>Quang Lý</a>,
<a onclick='Ae(this)'>Quang Lê</a>,

<a onclick='Ae(this)'>Vân Khánh</a>,
<a onclick='Ae(this)'>Elvis Phương</a>,
<a onclick='Ae(this)'>Quỳnh Lan</a>,
<a onclick='Ae(this)'>Dương Ngọc Thái</a>,
<a onclick='Ae(this)'>Ngọc Ánh</a>,
<a onclick='Ae(this)'>Ngọc Sơn</a>,
<a onclick='Ae(this)'>Hương Giang</a>,
<a onclick='Ae(this)'>Trọng Tấn</a>,
<a onclick='Ae(this)'>Sỹ Phú</a>,

<a onclick='Ae(this)'>Thu Minh</a>,
<a onclick='Ae(this)'>Hồng Nhung</a>,
<a onclick='Ae(this)'>Thùy Dương</a>,
<a onclick='Ae(this)'>Xuân Phú</a>,
<a onclick='Ae(this)'>Khánh Ly</a>,
<a onclick='Ae(this)'>Bảo Yến</a>,
<a onclick='Ae(this)'>Thanh Thúy</a>,
<a onclick='Ae(this)'>Mỹ Lệ</a>
</li>
<li><div id='obj'></div></li>
</ul></li>
<li><div id='tif'>
<a id='f' lang='F,R' onclick='M.buf(this)'>R</a>
<a id='a' onclick='M.tif(this)'>Tất Cả</a>
<a id='d' onclick='M.tif(this)'>Ngày</a>
<a id='w' onclick='M.tif(this)'>Tuần</a>
<a id='m' onclick='M.tif(this)'>Tháng</a>
<a id='y' onclick='M.tif(this)'>Năm</a>
<a id='n' onclick='M.tif(this)'>New Top</a>
</div></li>
</ul>
<fieldset><legend><?echo $nhacf;?><form id='tab' lang='Cộng thêm danh sách mới.' name='tap'></form></legend>
<div id='nhac' lang='Đang nghe,Thể hiện,Sáng tác,chưa rõ,Tắt,Tiếp »,Good,Bad'></div>
<ul id='tabs'></ul><ul class='page' id='pms'></ul><ul id='menu'><li><a onclick='M.sow(1,0)'>Nghe Gần Đây</a><ul class='b'><li class='b'><a onclick='M.sow(-1,0)'>Nhạc Trẻ</a></li><li><a onclick='M.sow(-2,0)'>Nhạc Trữ Tình</a></li><li><a onclick='M.sow(-3,0)'>Nhạc Tiền Chiến</a></li><li><a onclick='M.sow(-4,0)'>Nhạc Cách Mạng</a></li><li><a onclick='M.sow(-5,0)'>Nhạc Hòa Tấu</a></li><li><a onclick='M.sow(-6,0)'>Nhạc Nước Ngoài</a></li><li class='b'><a onclick='M.sow(-7,0)'>Nhạc Vũ Trường</a></li><li><a onclick='M.sow(-8,0)'>Nhạc Dance</a></li><li><a onclick='M.sow(-9,0)'>Nhạc Hip Hop</a></li><li><a onclick='M.sow(-10,0)'>Nhạc Ráp</a></li><li><a onclick='M.sow(-11,0)'>Nhạc Pop</a></li><li><a onclick='M.sow(-12,0)'>Nhạc Hàn Quốc</a></li><li class='b'><a onclick='M.sow(-13,0)'>Nhạc Hoa</a></li><li><a onclick='M.sow(-14,0)'>Nhạc Quê</a></li><li><a onclick='M.sow(-15,0)'>Nhạc Huế</a></li><li><a onclick='M.sow(-16,0)'>Nhạc Trịnh</a></li><li><a onclick='M.sow(-17,0)'>Nhạc Chế</a></li><li><a onclick='M.sow(-18,0)'>Nhạc Hài</a></li><li class='b'><a onclick='M.sow(-19,0)'>Nhạc Phim</a></li><li><a onclick='M.sow(-20,0)'>Nhạc Xuân</a></li><li><a onclick='M.sow(-21,0)'>Nhạc Noel</a></li><li><a onclick='M.sow(-23,0)'>Nhạc Thiếu Nhi</a></li><li><a onclick='M.sow(-22,0)'>Nhạc Khác</a></li><li><a onclick='M.sow(-0.1,0)'>Nhạc Tổng Hợp</a></li></ul></li><li><a onclick='M.sow(2,0)'>Đánh Giá Cao</a></li><li><a onclick='M.sow(3,0)'>Mới Nhất</a></li><li><a onclick='M.sow(5,0)'>Tìm Kiếm Nhiều</a></li><li><a onclick='M.sow(6,0)'>Lượt Nghe</a></li><li><a onclick='M.sow(7,0)'>Ưa Thích</a></li></ul>
</fieldset>
<!-- thêm fieldset - legend (cột 2) -->
<fieldset><legend id='totals' lang='music'></legend><span id='total' lang='Tắt,Good,Bad'></span><div id='toal'></div><ul class='page' id='tms'></ul><ul id='menu'><li><a onclick='M.tol(1,0)'>Nghe Gần Đây</a></li><li><a onclick='M.tol(2,0)'>Đánh Giá Cao</a></li><li><a onclick='M.tol(3,0)'>Mới Nhất</a></li><li><a onclick='M.tol(5,0)'>Tìm Kiếm Nhiều</a></li><li><a onclick='M.tol(6,0)'>Lượt Nghe</a></li><li><a onclick='M.tol(7,0)'>Ưa Thích</a></li></ul></fieldset>
<fieldset><legend id='videos' lang='video'></legend><span id='video' lang='Tắt,Good,Bad'></span><div id='vdeo'></div><ul class='page' id='vms'></ul><ul id='menu'><li><a onclick='M.deo(1,0)'>Nghe Gần Đây</a></li><li><a onclick='M.deo(2,0)'>Đánh Giá Cao</a></li><li><a onclick='M.deo(3,0)'>Mới Nhất</a></li><li><a onclick='M.deo(5,0)'>Tìm Kiếm Nhiều</a></li><li><a onclick='M.deo(6,0)'>Lượt Nghe</a></li><li><a onclick='M.deo(7,0)'>Ưa Thích</a></li></ul></fieldset>
<fieldset><legend id='albums' lang='album'></legend><span id='album' lang='Tắt,Good,Bad'></span><div id='abum'></div><ul class='page' id='ams'></ul><ul id='menu'><li><a onclick='M.bum(1,0)'>Nghe Gần Đây</a></li><li><a onclick='M.bum(2,0)'>Đánh Giá Cao</a></li><li><a onclick='M.bum(3,0)'>Mới Nhất</a></li><li><a onclick='M.bum(5,0)'>Tìm Kiếm Nhiều</a></li><li><a onclick='M.bum(6,0)'>Lượt Nghe</a></li><li><a onclick='M.bum(7,0)'>Ưa Thích</a></li></ul></fieldset>
<fieldset><legend>Cộng Album & Video - <a href='http://www.youtube.com/watch_popup?v=fskcIKAfKeU&amp;vq=hd720' style='font-size:10px;letter-spacing:0px' target='_blank'>xem demo</a></legend><div id='view'></div>
<table class='add'><form name='add'><tr>
<td>Tên:</td><td><input id='ten' maxlength='75' name='ten' onkeyup='M.ten(this)' onmouseover='this.focus()' placeholder='Điền tên bài hát, album, video' style='width:279px' type='text' value=''/></td>
<td>Mã Nhúng:</td><td><input id='code' name='code' onclick='this.select()' onkeyup='M.ten(this)' onmouseover='this.focus()' placeholder='Dán mã embed, link youtube' style='width:227px' type='text' value=''/></td>
<td><input disabled='true' id='button' onclick='M.add(this)' type='button' value='Thêm'/></td></tr></form></table>
</fieldset>
</div></td>
<td><!-- (cột 3) --></td>
</tr><tr><td><div class='footer section' id='footer'></div><!-- (chân) --></td></tr></table>
<script id='codef' src='http://nhacf.googlecode.com/files/forever.js?host=p&solo=r&show=m'></script>
</body></html>