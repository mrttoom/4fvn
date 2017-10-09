<?
	if(!defined('CHIP_ROOT')) die ('Hello pro!');
	$data = file_get_contents($root['chatdata'].'config.txt');
	$ex = explode('|',$data);
?>  
<form id="form1" name="form1" method="post" action="">
  <table width="644">
    <tr class="fr_top">
      <td colspan="2">Cấu hình Chip Chatbox</td>
    </tr>
    <tr>
      <td width="125" class="fr">Tiêu đề web</td>
      <td width="503" class="fr_2"><input name="webtitle" type="text" id="webtitle" size="30" value="<?=$ex[0]?>" /> 
        Chip Chatbox</td>
    </tr>
    <tr>
      <td width="125" class="fr">Time delay</td>
      <td width="503" class="fr_2"><input name="delay" type="text" id="delay" size="30" value="<?=$ex[1]?>" /> 
        (giây) Thời gian giữa 2 lần chat</td>
    </tr>
    <tr>
      <td width="125" class="fr">Tự động refresh</td>
      <td width="503" class="fr_2"><input name="refresh" type="text" id="refresh" size="30" value="<?=$ex[2]?>" />
       (giây) Thời gian tự động load lại khung chat</td>
    </tr>
     <tr>
      <td width="125" class="fr">Lệnh xóa chatbox</td>
      <td width="503" class="fr_2"><input name="delete" type="text" id="delete" size="30" value="<?=$ex[3]?>" />
        Lệnh dành cho admin để xóa chatbox</td>
    </tr>
    
     <tr>
      <td width="125" class="fr">Từ khóa vi phạm</td>
      <td width="503" class="fr_2"><input name="block" type="text" id="block" size="30" value="<?=$ex[4]?>" />
       Loại bỏ các từ láo, ngăn cách nhau bởi dấu ,</td>
    </tr>
     <tr>
      <td width="125" class="fr">Số dòng chat</td>
      <td width="503" class="fr_2"><input name="line" type="text" id="line" size="30" value="<?=$ex[5]?>" /> 
        Số dòng hiển thị trong khung chat</td>
    </tr>
     <tr>
      <td width="125" class="fr">Khóa nick</td>
      <td width="503" class="fr_2"><input name="ban" type="text" id="ban" size="30" value="<?=$ex[6]?>" />
       x,y -  x lần sử dụng từ láo sẽ bị khóa nick y giây</td>
    </tr>
     <tr>
      <td width="125" class="fr">Lưu tối đa</td>
      <td width="503" class="fr_2"><input name="data_size" type="text" id="data_size" size="30" value="<?=$ex[7]?>" />
       (kb) Khi data nặng hơn (?) Kb thì tự động xóa </td>
    </tr>
    <tr>
      <td width="125" class="fr">Chiều rộng chatbox</td>
      <td width="503" class="fr_2"><input name="width" type="text" id="width" size="30" value="<?=$ex[8]?>" />
      Tính theo px hoặc % (600 hoặc 100%)</td>
    </tr>
    <tr>
      <td width="125" class="fr">Chiều cao chatbox</td>
      <td width="503" class="fr_2"><input name="height" type="text" id="height" size="30" value="<?=$ex[9]?>" />
       Tính theo px</td>
    </tr>
    <tr>
      <td width="125" class="fr">Lệnh ban, mở nick</td>
      <td width="503" class="fr_2"><input name="bannick" type="text" id="bannick" size="30" value="<?=$ex[10]?>" />
        (/ban,/unban)
      Lệnh dùng để cấm nick chat</td>
    </tr>
    <tr>
      <td width="125" class="fr">Lệnh ban, mở IP</td>
      <td width="503" class="fr_2"><input name="banip" type="text" id="banip" size="30" value="<?=$ex[11]?>" />
       (/banip,/unbanip) Lệnh cấm IP chat </td>
    </tr>
    <tr>
      <td width="125" class="fr">Lệnh xóa banned</td>
      <td width="503" class="fr_2"><input name="delban" type="text" id="delban" size="30" value="<?=$ex[12]?>" />
       (/del) Lệnh xóa hết danh sách đã banned /del /ban</td>
    </tr>
     <tr>
      <td colspan="2" align="center" class="fr"><input type="submit" name="do" id="do" value="Sửa" /></td>
    </tr>
  </table>
</form>
<?
	if($_POST["do"])
	{
		
		$str = $_POST["webtitle"].'|'.$_POST["delay"].'|'.$_POST["refresh"].'|'.$_POST["delete"].'|'.$_POST["block"].'|'.$_POST["line"].'|'.$_POST["ban"].'|'.$_POST["data_size"].'|'.$_POST["width"].'|'.$_POST["height"].'|'.$_POST["bannick"].'|'.$_POST["banip"].'|'.$_POST["delban"];
		$f = fopen("data/config.txt", "w");
		fwrite($f, $str);
		fclose($f);
		
		echo "<meta http-equiv='refresh' content='0; url=?admin&act=site_config'>"; 
	}
?>
