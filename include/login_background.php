<?php
$backimageurl = "";

$slideshow_files = get_slideshow_files_data();
foreach($slideshow_files as $slideshow_image => $slideshow_file_info)
	{
	if($backimageurl == "" && file_exists($slideshow_file_info['file_path']))
		{
		$backimageurl = "{$baseurl_short}pages/download.php?slideshow={$slideshow_image}";  
		continue;
		}	
	}

?>
<style>
#UICenter {
	background-image: url('<?php echo $backimageurl; ?>');
	}
</style>
<div id="login_box">
