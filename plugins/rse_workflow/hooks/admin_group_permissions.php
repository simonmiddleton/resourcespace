<?php
function HookRse_workflowAdmin_group_permissionsAdditionalperms()
    {
    include_once (dirname(__file__) . "/../include/rse_workflow_functions.php");
    global $lang;    
    # ------------ Edit access to workflow actions
    $workflowactions = rse_workflow_get_actions();
    
	?>
	<tr class="ListviewTitleStyle">
	  <td colspan=3 class="permheader"><?php echo $lang["rse_workflow_actions_heading"] ?></td>
	</tr>
	<?php
    foreach ($workflowactions as $workflowaction)
        {
         DrawOption("wf" . $workflowaction["ref"], $lang["rse_workflow_access"] . " " . htmlspecialchars($workflowaction["name"]));
        }        
    }
