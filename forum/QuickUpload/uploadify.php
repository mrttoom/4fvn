<?php
include 'config.php';
include 'scripts/function.php';
include 'inc/class_image_uploader.php';
if (!empty($_FILES)) {
	$file = $_FILES['Filedata'];
	$imagePath = $setting['tempfolder'] . $setting['FileNamePrefix'].basename(convert_filename($file['name']));
	$isUpload = TRUE;
	
	if(check_filetype(laydinhdang($imagePath)))
	{
		if($file['size']<$setting['maxsize'])
		{
			if(@move_uploaded_file($file['tmp_name'], $imagePath))
			{
				if($setting['WaterMark'])
					imageWaterMark($imagePath,$setting['PosWaterMark'],$setting['ImageWaterMark']);
				$uploader = c_Image_Uploader::factory('picasa');
				$uploader->login($user, $pass);
				$uploader->setAlbumID($albumId);
				if(!$imagePath)
					echo 'loi|Mising an image';
				$url = $uploader->upload($imagePath);
				if(@file_exists($imagePath))
					@unlink($imagePath);
				if($isUpload)
					echo $url;
				else
					echo 'loi|'.$url;
			}
			else
			{
				echo "loi|Không chuyển đc file tạm trên server.";
			}
		}
		else
		{
			echo "loi|File: {$file["name"]} Có Size là ".doidungluong($file["size"])." \nGiới hạn chỉ là ".doidungluong($setting['maxsize'])."";
		}
	}
	else
	{
		echo "loi|File: {$file["name"]} không cho phép upload (".laydinhdang($imagePath).")";
	}
}
?>