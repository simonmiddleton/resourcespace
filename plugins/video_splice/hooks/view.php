<?php

function HookVideo_spliceViewAfterresourceactions()
	{
    global $videosplice_allowed_extensions,$resource,$baseurl,$urlparams,$lang;

   	if (!in_array($resource["file_extension"], $videosplice_allowed_extensions)) {return false;}
    # If user has edit access or access to manage alternative files then add the link
    if (!((get_edit_access($resource['ref'],$resource["archive"],$resource) && (checkperm("d") || checkperm("c"))) || !checkperm("A")) && $resource['ref'] > 0)
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