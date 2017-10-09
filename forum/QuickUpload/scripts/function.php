<?php
//WaterMark
function imageWaterMark($groundImage,$waterPos=9,$waterImage){
    $water_info = getimagesize($waterImage); 
    $w    = $water_info[0]; 
    $h    = $water_info[1];
    $water_im = imagecreatefrompng($waterImage);   
    imageAlphaBlending($water_im, false);
    imageSaveAlpha($water_im, true);
    if(!empty($groundImage) && file_exists($groundImage)) 
    {
        $ground_info = getimagesize($groundImage);
        $ground_w    = $ground_info[0]; 
        $ground_h    = $ground_info[1];
        switch($ground_info[2]) 
        { 
            case 1:$ground_im = imagecreatefromgif($groundImage);break; 
            case 2:$ground_im = imagecreatefromjpeg($groundImage);break; 
            case 3:$ground_im = imagecreatefrompng($groundImage);break; 
            default:die($formatMsg); 
        } 
    } 
    if( ($ground_w<$w) || ($ground_h<$h) ){return;} 
    switch($waterPos) 
    { 
        case 0: 
           	$posX = rand(0,($ground_w - $w)); 
            $posY = rand(0,($ground_h - $h)); 
            break; 
        case 1: 
            $posX = 0; 
            $posY = 0; 
            break; 
        case 2: 
            $posX = ($ground_w - $w) / 2; 
            $posY = 0; 
            break; 
        case 3: 
            $posX = $ground_w - $w; 
            $posY = 0; 
            break; 
        case 4: 
            $posX = 0; 
            $posY = ($ground_h - $h) / 2; 
            break; 
        case 5: 
            $posX = ($ground_w - $w) / 2; 
            $posY = ($ground_h - $h) / 2; 
            break; 
        case 6: 
            $posX = $ground_w - $w; 
            $posY = ($ground_h - $h) / 2; 
            break; 
        case 7:
            $posX = 0; 
            $posY = $ground_h - $h; 
            break; 
        case 8: 
            $posX = ($ground_w - $w) / 2; 
            $posY = $ground_h - $h; 
            break; 
        case 9: 
            $posX = $ground_w - $w; 
            $posY = $ground_h - $h; 
            break; 
        default: 
            $posX = rand(0,($ground_w - $w)); 
            $posY = rand(0,($ground_h - $h)); 
            break;     
    } 
    imagealphablending($ground_im, true);     
    imagecopy($ground_im, $water_im, $posX, $posY, 0, 0, $w,$h);    
    unlink($groundImage);
	ImageJpeg($ground_im,$groundImage);
    if(isset($water_info)) unset($water_info); 
    if(isset($water_im)) imagedestroy($water_im); 
    unset($ground_info); 
    imagedestroy($ground_im); 
} 
//Chuyen chuoi UTF-8 sang ascii va thay khoang trang bang -
function convert_filename($str) {
	$chars = array(	
		'a'	=>	array('ấ','ầ','ẩ','ẫ','ậ','Ấ','Ầ','Ẩ','Ẫ','Ậ','ắ','ằ','ẳ','ẵ','ặ','Ắ','Ằ','Ẳ','Ẵ','Ặ','á','à','ả','ã','ạ','â','ă','Á','À','Ả','Ã','Ạ','Â','Ă'),
		'e' =>	array('ế','ề','ể','ễ','ệ','Ế','Ề','Ể','Ễ','Ệ','é','è','ẻ','ẽ','ẹ','ê','É','È','Ẻ','Ẽ','Ẹ','Ê'),
		'i'	=>	array('í','ì','ỉ','ĩ','ị','Í','Ì','Ỉ','Ĩ','Ị'),
		'o'	=>	array('ố','ồ','ổ','ỗ','ộ','Ố','Ồ','Ổ','Ô','Ộ','ớ','ờ','ở','ỡ','ợ','Ớ','Ờ','Ở','Ỡ','Ợ','ó','ò','ỏ','õ','ọ','ô','ơ','Ó','Ò','Ỏ','Õ','Ọ','Ô','Ơ'),
		'u'	=>	array('ứ','ừ','ử','ữ','ự','Ứ','Ừ','Ử','Ữ','Ự','ú','ù','ủ','ũ','ụ','ư','Ú','Ù','Ủ','Ũ','Ụ','Ư'),
		'y'	=>	array('ý','ỳ','ỷ','ỹ','ỵ','Ý','Ỳ','Ỷ','Ỹ','Ỵ'),
		'd'	=>	array('đ','Đ'),
	);
	foreach ($chars as $key => $arr) 
	foreach ($arr as $val)
	$str = str_replace($val,$key,$str);
	while (strpos($str,"  ")!==false)
		$str = str_replace("  "," ",$str);
		
	$str = str_replace(" ","-",$str);
	return $str;

}
//Lay dinh dang file
function laydinhdang($str) {
	$i = strrpos($str,".");
	if (!$i) { return ""; }
	$l = strlen($str) - $i;
	$ext = substr($str,$i+1,$l);
	return strtolower($ext);
}
//Kiem tra ten file trung lap
function check_filename($filename)
{
	global $upload;
	$n=1;
	$filename2=$filename;
	while(file_exists($filename2))
		{
			$filename2=substr($filename,0,(strlen($filename)-strlen(laydinhdang($filename))-1))."($n).".laydinhdang($filename);
			$n++;
		}
	return $filename2;	
}
//Kiem tra co cho phep up hay ko
function check_filetype($fileExt)
{
	global $upload;
	$arr=explode(",",$upload['type']);

	if(in_array($fileExt,$arr))
		return true;
	return false;
}
function doidungluong ($dungluong)
{
	if ($dungluong>=(1024*1024*1024))
		$dungluong=round((double)$dungluong/(1024*1024*1024),2)." GB";
	else if ($dungluong>=1024*1024)
		$dungluong=round((double)$dungluong/(1024*1024),2)." MB";
	elseif ($dungluong>=1024)
		$dungluong=round((double)$dungluong/1024,2)." KB";	
	else
		$dungluong=$dungluong." Byte";
	return $dungluong;
}

?>