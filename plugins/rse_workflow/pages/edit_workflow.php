<?php
#
# rse_workflow setup page, requires System Setup permission
#

include '../../../include/db.php';
include_once '../../../include/general.php';
include '../../../include/authenticate.php'; if (!checkperm('a')) {exit ($lang['error-permissiondenied']);}
include '../../../include/header.php';

?>

<div class="BasicsBox"> 
  <h1><?php echo $lang["rse_workflow_configuration"]?></h1>

	<div class="VerticalNav">
	<ul>
	
	<li><a href="<?php echo $baseurl ?>/plugins/rse_workflow/pages/edit_workflow_states.php" onclick="return CentralSpaceLoad(this,true);;"><?php echo LINK_CARET . $lang["rse_workflow_manage_states"]?></a></li>
        <li><a href="<?php echo $baseurl ?>/plugins/rse_workflow/pages/edit_workflow_actions.php" onclick="return CentralSpaceLoad(this,true);"><?php echo LINK_CARET . $lang["rse_workflow_manage_actions"]?></a></li>

	</ul>
	</div>

	<p><a href="<?php echo $baseurl_short?>pages/team/team_home.php" onClick="return CentralSpaceLoad(this,true);"><?php echo LINK_CARET_BACK; ?><?php echo $lang["backtoteamhome"]?></a></p>
  </div>

<?php
include "../../../include/footer.php";
?>
