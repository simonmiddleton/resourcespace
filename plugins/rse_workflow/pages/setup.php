<?php
#
# rse_workflow setup page, requires System Setup permission
#

include '../../../include/db.php';
include '../../../include/authenticate.php'; if (!checkperm('a')) {exit ($lang['error-permissiondenied']);}
include '../../../include/header.php';

$plugin_name = 'rse_workflow';
if(!in_array($plugin_name, $plugins))
	{plugin_activate_for_setup($plugin_name);}

?>



<div class="BasicsBox">
<h1><?php echo $lang['rse_workflow_configuration']; ?></h1>
<div class="clearerleft" ></div>

<div>
<?php
echo str_replace("%%HERE","<a href=\"" . $baseurl . "\pages/team/team_home.php\" onclick=\"CentralSpaceLoad(this,true)\">here</a>",$lang['rse_workflow_introduction']);

?>

</div>
</div>
<?php

include '../../../include/footer.php';
