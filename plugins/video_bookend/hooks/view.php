<?php

function HookVideo_bookendViewAfterresourceactions (){
	global $ref,$access,$lang,$resource,$cropper_allowed_extensions,$baseurl_short,$resourcetoolsGT;


	if ($access==0 && $resource['has_image']==1)
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
