<?php

function HookVideo_spliceViewAfterresourceactions()
	{
    global $videosplice_allowed_extensions,$resource,$baseurl,$urlparams,$lang;

   	if (!in_array($resource["file_extension"], $videosplice_allowed_extensions)) {return false;}
    
    # Allow the user to access the link if they have permission to perform one or more of the actions on the trim page. i.e. create new resources, manage alternative files or download the resource
    if (resource_download_allowed($resource['ref'], "scr", $resource['resource_type']) || !checkperm('A') || checkperm('c') || checkperm('d'))
        {
        ?>
        <li><a href="<?php echo generateurl($baseurl . "/plugins/video_splice/pages/trim.php", $urlparams);?>" onclick="return ModalLoad(this, true);">
        <?php echo "<i class='fa fa-fw fa-scissors'></i>&nbsp;" . $lang["action-trim"]?>
        </a></li>
        <?php
        }
	}
?>