<?php
	
function HookCsv_uploadAllTopnavlinksafterhome()
	{
    global $baseurl,$lang;
	if (checkperm("c"))
		{
		?><li class="HeaderLink"><a href="<?php echo $baseurl ?>/plugins/csv_upload/pages/csv_upload.php" onClick="CentralSpaceLoad(this,true);return false;"><?php echo UPLOAD_ICON . $lang["csv_upload_nav_link"]; ?></a></li>
		<?php
		}
	}
