<?php

function HookVideo_spliceViewAfterresourceactions()
	{
    global $videosplice_allowed_extensions,$resource,$baseurl,$urlparams,$lang;

   	if (!in_array($resource["file_extension"], $videosplice_allowed_extensions)) {return false;}

    if (!resource_download_allowed($resource['ref'], "scr", $resource['resource_type']) )
        {
        return false;
        }
	?>

	<li><a href="<?php echo generateurl($baseurl . "/plugins/video_splice/pages/trim.php", $urlparams);?>" onclick="return ModalLoad(this, true);">
	<?php echo "<i class='fa fa-fw fa-scissors'></i>&nbsp;" . $lang["action-trim"]?>
	</a></li>
	<?php
	}
?>