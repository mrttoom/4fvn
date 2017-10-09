<?
include 'inc/config.php';
include 'inc/functions.php';

$content =  file('data/content.txt');
$time_now = time();
#tính từ thời điểm hiện tại trở về trước 15 phút
$end_time = $time_now - 60*$config['onlstats']; //15 phut

$count = count($content);
for($i=$count-1;$i>=0;$i--){
	$x = explode('*|*',$content[$i]);
	$time = $x[1];
	$name = $x[2];
	if($time >= $end_time){
		$arr_onl[$name] = $name;
	}

}
#http://l.yimg.com/us.yimg.com/i/mesg/emoticons7/1.gif
$template = '<li><img src="http://img375.imageshack.us/img375/8412/onlineu.png"> {$name}</li>';
if($arr_onl){
	foreach($arr_onl as $name)
		eval('$onlines .= "'.addslashes($template).'";');
}
else
	$onlines = 'Not found online user';
echo '<ul>'.$onlines.'</ul>';
?>