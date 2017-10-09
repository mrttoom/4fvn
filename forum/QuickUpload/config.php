<?php
@set_time_limit(0);
define('DIR', dirname(__FILE__));
/**
 * Sử dụng core c-Image Uploader 3.1 - Chiplove.9xpro. Cảm ơn Chiplove.9xpro
 * Vào Picasaweb.google.com để bít thêm về picasa nhé.
 * AlbumID lấy ở link RSS trong album dạng                         
   VD: https://picasaweb.google.com/data/feed/base/user/115785904867843171583/albumid/5727813027720287729?alt=rss&kind=photo&hl=en_US 
   //5727813027720287729 là AlbumID
 * Phần albumID có thể set 1 array('id1', 'id2'); Code sẽ tự động lấy ngẫu nhiên 1 album trong số đó để upload vào.
 * Nếu ko setAlbumID thì code sẽ up vào album default của picasa 
 * Mỗi album chứa được 1000 ảnh. Mỗi account chứa đc 10000 ảnh.
*/
//Picasa
$user = "h33339999@gmail.com"; // User Picasa or google
$pass = "hungaaa@!~"; // Password
$albumId = '5701922941754341985'; // AlbumID 
//--Config
$upload['type']="jpg,png,gif,bmp,jpeg";//Phần mở rộng
$setting['maxsize']=2*1024*1024;// SizeLimit (1MB) 
$setting['FileNamePrefix']='4fvn_';//Tiền tố trước filename
//WaterMark
$setting['tempfolder']= DIR . '/tmp/';// CHMOD 0777 thư mục này nhé
$setting['WaterMark']=true;//Có sử dung WaterMark ko ?
$setting['ImageWaterMark']='logo.png';//Ảnh dùng để làm WaterMark
$setting['PosWaterMark']=9;//Vị trí Ảnh WaterMark dc đè lên
?>