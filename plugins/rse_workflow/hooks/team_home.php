<?php

function HookRse_workflowTeam_homeCustomteamfunction()
	{
	global $baseurl;
        global $lang;
	
    if (checkperm("a"))
		{
		
		?>
		<li><a href="<?php echo $baseurl ?>/plugins/rse_workflow/pages/edit_workflow.php" onclick="return CentralSpaceLoad(this,true);"><i class="fa fa-fw fa-check-square-o"></i><br /><?php echo $lang["rse_workflow_manage_workflow"]?></a></li>
		<?php
		}
		?>
	<?php
	}




