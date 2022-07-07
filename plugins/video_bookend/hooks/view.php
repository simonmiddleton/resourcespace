<?php

function HookVideo_bookendViewAfterresourceactions (){
	global $ref,$access,$lang,$resource,$cropper_allowed_extensions,$baseurl_short,$resourcetoolsGT;

	$original_file_path=get_resource_path($ref,true,"",false,$resource['file_extension']);
	$original_file_mime_type = mime_content_type($original_file_path);
	if ($access==0 && $resource['has_image']==1 && substr($original_file_mime_type,0,6)=="video/")
        {
		?>
		<li><a onClick='return CentralSpaceLoad(this,true);' href='<?php echo $baseurl_short;?>plugins/video_bookend/pages/bookend.php?ref=<?php echo $ref?>'>
		<?php echo "<i class='fa fa-arrows-h'></i>&nbsp;" .$lang['bookend'];?>
		</a></li>
		<?php
		return true;
        }

}

?>
