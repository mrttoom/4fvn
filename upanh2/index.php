<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="description" content="4fvn.com. 4fvn, teen, 9x , picasa, image hosting, free photo sharing and video sharing. Upload your photos and share them with friends and family."/>
<meta name="keywords" content="4fvn.com, 4fvn, teen, 9x , picasa, album, free image hosting, image hosting, video hosting, photo image hosting site"/>
<title>Free Upload Images To 4FVN.COM</title>
<link id="page_favicon" href="favicon.ico" rel="icon" type="image/x-icon" />
<script type="text/javascript" src="script.js"></script> 
<link rel="stylesheet" href="style.css" type="text/css" />
</head>
<body>

<center><a href="/"><img src="" border="0"></a></center>


<div id="wrapper">
<div class="block" >

<center> <b><font color="red">Up Ảnh Miễn Phí - Lưu Trữ Vĩnh Viễn<br><br>

 <a href="http://4fvn.com">Trang Chủ</a> || <a href="http://4fvn.com/forum">Diễn Đàn</a> || <a href="http:/4fvn.com/forum" target="_blank">Home</a>


</font></b></center>

</div>
	
	
</div>	
	
	
	
	
	
	
	



<div id="wrapper">
	<div class="block" ><table><tr><td  class="block" style="float:left;width:319px;height:120px;">
         <div>
            Upload ảnh từ: 
            <a href="?op=upload">Máy Tính</a> || <a href="?op=transfer">URL</a> || <a href="https://picasaweb.google.com/116825982935977895592/4fvn#" target="_blank">Thư Viện Ảnh</a>
        </div>
	
		      
        
        

            <div id="inputfile" style="height:20px;width:250px;">
                 <div style="float: left;">
                    <input type="text" style="height:23px;border:1px solid #ccc;width:170px;">
                </div>
                <div style="float: left;" id="flash_upload"></div>
            </div>
            <script>setWatermark(true);</script>
				
		<br><div>
        	Đóng dấu 4FVN.COM: 
            <input type="radio" name="watermark" id="watermark" onclick="setWatermark(true);" checked="checked"/> Có
        	<input type="radio" name="watermark" onclick="setWatermark(false);"/> <font color="red"><b>Không</b></font>
        </div>
		   
			 
		</td><td class="block" style="float:right;width:319px;height:120px;"><b>Welcome to <a href="http://4fvn.com">4FVN.COM</a></b><br><br>
		<ul><li>Up ảnh sẻ hoàn toàn <b>miễn phí</b>, không cần đăng ký.</li>
		<li>Ảnh của bạn được lưu trữ <b>vĩnh viễn</b> tại Picasa - Google.</li>
		<li>Up ảnhnh <b>nhanh</b>, gọn, tiện lợi dành cho mọi người.</li>
		<li>Không chấp nhận ảnh xxx - <b><font color="red">Don't upload XXX please !</font></b></li></ul>
		
		
		
		
		</td></tr></table>
    </div>    
    </div>  
     <div id="wrapper">
   	<div class="block">
        <div id="result"></div>
        <div id="loading"></div>
        <div id="getcode" style="display:none">
            <div>
                <a href="javascript:showcode('bbcode');">Chèn vào Forum</a> | 
                <a href="javascript:showcode('html');">Chèn vào Website</a> | Link trực tiếp: 
                <a href="javascript:showcode('none');">Fullsize</a> | 
				<a href="javascript:showcode('120');">120x</a> | 
				<a href="javascript:showcode('150');">150x</a> | 
				<a href="javascript:showcode('200');">200x</a> | 
				<a href="javascript:showcode('250');">250x</a> | 
				<a href="javascript:showcode('300');">300x</a> | 
				<a href="javascript:showcode('350');">350x</a> | 
				<a href="javascript:showcode('400');">400x</a> | 
				<a href="javascript:showcode('640');">640x</a> | 
				<a href="javascript:showcode('800');">800x</a>
             </div>
            <div><textarea id="showcode" style="height:130px;width:100%" onclick="this.select();"></textarea></div>
        </div>
     </div>   
	<div class="block">
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js"></script>
	<script language="JavaScript" src="jquery.tinycarousel.min.js"></script>
			<div class="top_img" id="slider-code">
            	<h3>Ảnh mới nhất:</h3>
            	<a href="#" class="back prev"></a>
                <div class="slide viewport" id="slide">
					<ul class="overview">
<?php
	require("./config.php");
	require_once 'Zend/Loader.php';
	Zend_Loader::loadClass('Zend_Gdata');
	Zend_Loader::loadClass('Zend_Gdata_ClientLogin');
	Zend_Loader::loadClass('Zend_Gdata_Photos');
	Zend_Loader::loadClass('Zend_Http_Client');	
	
	$serviceName = Zend_Gdata_Photos::AUTH_SERVICE_NAME;
	$client = Zend_Gdata_ClientLogin::getHttpClient($user, $pass, $serviceName);

	// update the second argument to be CompanyName-ProductName-Version
	$gp = new Zend_Gdata_Photos($client, "Google-DevelopersGuide-1.0");
	$query = $gp->newUserQuery();

	// indicate the user's feed to retrieve
	$query->setUser("default");

	// set to only return photos
	// the default kind value for a user feed is to include only albums
	$query->setKind("photo");

	$query->setMaxResults($images_in_slide);

    // we're passing null for the username, as we want to send
    // additional query parameters generated by the UserQuery class
    $userFeed = $gp->getUserFeed(null, $query);

    // because we specified 'photo' for the kind, only PhotoEntry objects 
    // will be contained in the UserFeed
    foreach ($userFeed as $photoEntry) {
		if ($photoEntry->getMediaGroup()->getThumbnail() != null) {
		  $mediaThumbnailArray = $photoEntry->getMediaGroup()->getThumbnail();
		  $firstThumbnailUrl = $mediaThumbnailArray[1]->getUrl();
		}
		$link = $photoEntry->getLink('alternate')->getHref();
		$title = $photoEntry->getTitle()->getText();
        echo <<<abc
<li><a href="$link" title="$title">

						<table><tr><td style="height: 144px; width: 144px; vertical-align: bottom; text-align: center;">
							<img style="max-height: 144px; max-width: 144px" src="$firstThumbnailUrl" alt="$title" border="0" />
						 </td></tr></table>	
						</a>
						</li>		
abc;
    }
?>
</ul>
                </div>
            
            	<a href="#" class="next"></a>
            	<div class="clear"></div>
        	</div>

		<script language="JavaScript">
		$(document).ready(function() {
			$(".overview").find("img").error(function() {
				$(this).parentsUntil(".overview").remove();
			});
			if ( $(".overview").children().length == 0 )
				$('#slider-code').remove();
			$('#slider-code').tinycarousel({ display: 1, interval:true });
		});
		</script>
	</div>	 
</div><!--/#wrapper-->
<div align="center">© 2011 - 2012 - <a href="http://4fvn.com" title="Free Images Hosting - www.4fvn.com">4FVN.COM</a> | <a href="http://picasa.google.com/web/policy.html" target="_blank">Điều khoản sử dụng</a></div>
</body>
</html>