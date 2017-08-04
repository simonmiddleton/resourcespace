<?php
$backimageurl = "";
    $dir = dirname(__FILE__) . "/../" . $homeanim_folder;
    $d = scandir($dir);    
	sort($d, SORT_NUMERIC);
    foreach ($d as $f) 
		{ 
		if(preg_match("/[0-9]+\.(jpg)$/",$f))
            {
            $backimageurl= $baseurl_short . $homeanim_folder . "/" . $f;  
            break;    
            }
        }
	?>
	<style>
	#UICenter {
		background-image: url('<?php echo $backimageurl; ?>');
		}
	</style>
	<div id="login_box">
