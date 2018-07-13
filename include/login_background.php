<?php
include_once 'resource_functions.php';
$backimageurl = "";

$slideshow_files = get_slideshow_files_data();
foreach($slideshow_files as $slideshow_file_info)
	{
	if($backimageurl == "")
		{
		$backimageurl = "{$baseurl_short}pages/download.php?slideshow={$slideshow_file_info["ref"]}";  
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
