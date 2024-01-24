<?php

include "../../include/db.php";


$uniqid=getval("id","");
$user=getval("user",""); // Need to get this from query string since we haven't authenticated
$usertempdir=get_temp_dir(false,"rs_" . $user . "_" . $uniqid);
$progress_file=$usertempdir . "/progress_file.txt";
//$progress_file=get_temp_dir(false,$uniqid) . "/progress_file.txt";

if (!file_exists($progress_file)){
	touch($progress_file);
}

$content= file_get_contents($progress_file);
if ($content==""){echo $lang['preparingzip'];}

else if ($content=="zipping"){
	$files=scandir($usertempdir);
	echo "Zipping ";
		foreach ($files as $file){
			if (strpos($file,".zip")!==false){
				echo formatfilesize(filesize($usertempdir."/".$file));
			} 
		} 
	}

else if ($content=="nothing_to_download"){
    echo 'nothing_to_download';
}
else {
	ob_start();echo $content;ob_flush();exit();} // echo whatever the script has placed here.
