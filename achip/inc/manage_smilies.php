<?
	if(!defined('CHIP_ROOT')) die ('Hello pro!');
?>
<a href="?admin&act=manage_smilies&do=showsmilies">Show all smilies</a> | <a href="?admin&act=manage_smilies&do=formadd">Add more smilies</a>
</div>
<?
$smfile = $root['chatdata'].'chip_smilies.txt';

if ($_GET['do'] == 'showsmilies')
{
echo '<form name="chip_smilie" action="?admin&act=manage_smilies&do=save" method="post">';
$smliesfile = file($smfile);
$count = 0;
foreach ($smliesfile as $smilies)
{
	$bit = explode(" => ", $smilies);
	echo "<div><input name='smcode[]' type='text' size='5' value='$bit[0]'> <input onkeyup='update($count);' type='text' name='smpath[]' id='path$count' size='70' value='$bit[1]'> <span id='img$count'><img src='$bit[1]'><span></div>\n";
	$count++;
}
echo '<div><input type="submit" value="Save" name="submit"></div>';
echo '</form>';
}

if ($_GET['do'] == 'save')
{
	extract($_POST);
	if ($submit)
	{
	$sizelist = sizeof($smcode);
	
	$handle = fopen($smfile,"w");
	
	for ($i = 0; $i<$sizelist; $i++)
	{
		if ($smcode[$i])
		{
			$smcode[$i] = realstring($smcode[$i]);
			$data = "$smcode[$i] => $smpath[$i]\n";
			fwrite($handle, $data);
		}
	}
	echo "Update Successfully";
	fclose($handle);
	}
	echo '<script language="javascript">';
	echo 'location = "?admin&act=manage_smilies&do=showsmilies"';
	echo '</script>';
}

if ($_GET['do'] == 'formadd')
{
	echo '<form name="chip_smilieadd" action="?admin&act=manage_smilies&do=add" method="post">';
	for ($i = 0; $i<100; $i++)
	{
		echo "<div><input name='smcode[]' type='text' size='5' value=''> <input onkeyup='update($i);' type='text' name='smpath[]' id='path$i' size='70' value=''> <span id='img$i'><span></div>\n";
	}
	echo '<div><input type="submit" value="Add" name="submit"></div>';
	echo '</form>';
}

if ($_REQUEST['do'] == 'add')
{
	extract($_POST);
	if ($submit)
	{
	$sizelist = sizeof($smcode);
	
	$handle = fopen($smfile,"a");
	
	for ($i = 0; $i<$sizelist; $i++)
	{
		if ($smcode[$i] && $smpath[$i])
		{
			$smcode[$i] = realstring($smcode[$i]);
			$data = "$smcode[$i] => $smpath[$i]\n";
			fwrite($handle, $data);
		}
	}
	echo "Add Successfully";
	fclose($handle);
	}
	echo '<script language="javascript">';
	echo 'location = "?admin&act=manage_smilies&do=showsmilies"';
	echo '</script>';
}

function realstring($text)
{
	$text = str_replace("\'", "'", $text);
	$text = str_replace('\"', '"', $text);
	$text = str_replace("\\\\", "\\", $text);
	return $text;
}
?>
	
<script language="javascript">
function update(order)
{
	newlink = document.getElementById('path'+order).value;
	document.getElementById('img'+order).innerHTML = "<img src="+newlink+">";
}
</script>
	
	</body>
	</html>