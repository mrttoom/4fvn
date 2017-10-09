<? if(!defined('CHIP_ROOT')) die ('Hello pro!');?>
<h3>List banned</h3>
<a href="?admin&act=ban&do=nick">Show all nick</a> | <a href="?admin&act=ban&do=ip">Show all IP</a>
<br>
<?

	if($_GET["do"]=='nick')
	{
		$data = file_get_contents($root['chatdata'].'ban.txt');
		if(!trim($data)){echo'Chưa có nick nào bị ban';}
		$data = explode('*|*',$data);
		echo '<form action="?admin&act=ban&do=nick" method="post">';
		foreach($data as $value)
		{
			if(trim($value))
			echo '<div style="padding:3px;"><input name="value[]" size="20" value="'.$value.'" type="text"></div>';
		}
		echo'<div><input type="submit" value="Save" name="edit"></div></form>';
		if($_POST["edit"])
		{
			for($i=0;$i<count($_POST["value"]);$i++)
			{
				if(trim($_POST["value"][$i]))
				{
					$str .= $_POST["value"][$i].'*|*';
				}
			}
			$f = fopen($root['chatdata'].'ban.txt','w');
			fwrite($f,$str);
			fclose($f);
			echo "Đã sửa xong<meta http-equiv='refresh' content='0; url=?admin&act=ban&do=nick'>"; 
		}
	}
	
	if($_GET["do"]=='ip')
	{
		$data = file_get_contents($root['chatdata'].'banip.txt');
		if(!trim($data)){echo'Chưa có IP nào bị ban';}
		$data = explode('*|*',$data);
		echo '<form action="?admin&act=ban&do=ip" method="post">';
		foreach($data as $value)
		{
			if(trim($value))
			echo '<div style="padding:3px;"><input name="value[]" size="20" value="'.$value.'" type="text"></div>';
		}
		echo'<div><input type="submit" value="Save" name="edit"></div></form>';
		if($_POST["edit"])
		{
			for($i=0;$i<count($_POST["value"]);$i++)
			{
				if(trim($_POST["value"][$i]))
				{
					$str .= $_POST["value"][$i].'*|*';
				}
			}
			$f = fopen($root['chatdata'].'banip.txt','w');
			fwrite($f,$str);
			fclose($f);
			echo "Đã sửa xong<meta http-equiv='refresh' content='0; url=?admin&act=ban&do=ip'>"; 
		}
	}
	
	
?>


