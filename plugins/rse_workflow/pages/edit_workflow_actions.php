<?php
#
# rse_workflow actions setup page, requires System Setup permission
#

include '../../../include/db.php';
include_once '../../../include/general.php';
include '../../../include/authenticate.php'; if (!checkperm('a')) {exit ($lang['error-permissiondenied']);}	
include_once '../include/rse_workflow_functions.php';

# Retrieve list of existing defined actions 
$workflowactions = rse_workflow_get_actions();

$filterstate=getvalescaped("filterstate","all");
if ($filterstate!="all")
	{
	$a=count($workflowactions);
	for($n=0;$n<$a;$n++)
		{
		$fromactions=explode(",",$workflowactions[$n]["statusfrom"]);		
		if (!in_array($filterstate,$fromactions))
			{			
			unset($workflowactions[$n]);
			}
		}	
	}	
$delete=getvalescaped("delete","");
if ($delete!="")
	{
	# Delete action
	$deleted=rse_workflow_delete_action($delete);
	if($deleted){$noticetext=$lang['rse_workflow_action_deleted'];}
	else{$noticetext=$lang['error'];}
	}
	

	
include '../../../include/header.php';

?>
<div class="BasicsBox">
<a href="<?php echo $baseurl_short?>plugins/rse_workflow/pages/edit_workflow.php" onClick="return CentralSpaceLoad(this,true);"><?php echo LINK_CARET_BACK . $lang["rse_workflow_manage_workflow"] ?></a>
</div>
<?php

if (isset($noticetext))
	{
	echo "<div class=\"PageInformal\">" . $noticetext . "</div>";	
	}

?>
<script>
		
function deleteaction(ref)
		{
		event.preventDefault();
		event.stopPropagation();		
		
		if(confirm('<?php echo $lang["rse_workflow_confirm_action_delete"]?>'))
				{
				CentralSpaceLoad("<?php echo $baseurl?>/plugins/rse_workflow/pages/edit_workflow_actions.php?delete=" + ref, true); 		
				}
		return true;
		}
				
		
</script>


<div class="BasicsBox">
<h1><?php echo $lang['rse_workflow_manage_actions']; ?></h1>
<div class="clearerleft" ></div>

<div class="BasicsBox">
    <form method="post" name="form_filter_action" id="form_filter_action" action="<?php echo $baseurl_short?>plugins/rse_workflow/pages/edit_workflow_actions.php">
        <?php generateFormToken("form_filter_action"); ?>
		<div class="Question">
			<label for="filterstate"><?php echo $lang["rse_workflow_action_filter"]?></label>
			<div class="tickset">
				<div class="Inline">
					<select class="stdwidth" name="filterstate" id="filterstate" >
					<option value="all" <?php if($filterstate=="all"){echo " selected";}?>><?php echo $lang["all"] ?></option>
					<?php
					for ($n=-2;$n<=3;$n++)
						{
						echo "<option value=\"" . $n ."\" " . (($n==$filterstate && is_numeric($filterstate))?" selected":"") .  ">" . $lang["status" . $n] . "</option>"; 
						}
					foreach ($additional_archive_states as $additional_archive_state)
						{
						echo "<option value=\"" . $additional_archive_state . "\"" . (($additional_archive_state==$filterstate)?" selected":"") .  ">" . ((isset($lang["status" . $additional_archive_state]))?$lang["status" . $additional_archive_state]:$additional_archive_state) . "</option>";
						}	
					?>
					</select>
			 </div>
			 <div class="Inline"><input name="filtersubmit" type="submit" value="&nbsp;&nbsp;<?php echo $lang["searchbutton"]?>&nbsp;&nbsp;" onclick="preventDefault();CentralSpacePost(document.getElementById('form_filter_action'),false);"></div>
			</div>
		<div class="clearerleft"> </div>
		</div>
	</form>
</div>
<h2><?php echo $lang['rse_workflow_status_heading']; ?></h2>
<div class="BasicsBox">
<div class="Listview">
		<table border="0" cellspacing="0" cellpadding="0" class="ListviewStyle rse_workflow_table" id='rse_workflow_table'>
			<tr class="ListviewTitleStyle">
				<td>
				<?php echo $lang['rse_workflow_action_name']; ?>
				</td><td>
				<?php echo $lang['rse_workflow_action_text']; ?>
				</td><td>
				<?php echo $lang['rse_workflow_button_text']; ?>
				</td><td>				
				<?php echo $lang['rse_workflow_action_status_from']; ?>
				</td><td>
				<?php echo $lang['rse_workflow_action_status_to']; ?>
				</td><td>
				<?php echo $lang['rse_workflow_action_reference']; ?>
				</td><td>
				<?php echo $lang['tools']; ?>
				</td>
			</tr>

<?php

if (count($workflowactions)==0)
	{
	echo "<tr><td colspan='7'>" . $lang["rse_workflow_action_none_defined"] . "</td></tr>";
	}
else
	{
	foreach ($workflowactions as $workflowaction)
		{
		# Show actions relevant to this status
		if($workflowaction["ref"]==$delete){continue;}
		echo "<tr class=\"rse_workflow_link\" onclick=\"CentralSpaceLoad('" .  $baseurl . "/plugins/rse_workflow/pages/edit_action.php?ref=" . $workflowaction["ref"] . "',true);\">";
		?>
			<td><div class="ListTitle"><?php echo htmlspecialchars($workflowaction["name"]); ?></div>
			</td>			
			<td><?php echo $workflowaction["text"]; ?>
			</td>
			<td><?php echo $workflowaction["buttontext"]; ?>
			</td>
			<td><?php 
				$fromstates=explode(",",$workflowaction["statusfrom"]);
				$fromstatetext="";
				foreach ($fromstates as $fromstate)
					{
					if($fromstatetext!=""){$fromstatetext.=", ";}
					$fromstatetext.=$lang["status" . $fromstate]; 
					}
				echo $fromstatetext; 
				?>
			</td>
			<td><?php echo $lang["status" . $workflowaction["statusto"]]; ?>
			</td>
			<td>wf<?php echo $workflowaction["ref"]; ?>
			</td>
			<td>
			<div class="ListTools">
			<a href="<?php echo $baseurl?>/plugins/rse_workflow/pages/edit_workflow_actions.php?delete=<?php echo $workflowaction["ref"] ?>" class="deleteaction" onClick="deleteaction(<?php echo $workflowaction["ref"]  ?>,true);"><?php echo LINK_CARET . $lang["action-delete"]?> </a><br>
			<a href="<?php echo $baseurl?>/plugins/rse_workflow/pages/edit_action.php?ref=<?php echo $workflowaction["ref"] ?>" onclick="return CentralSpaceLoad(this,true);"><?php echo LINK_CARET . $lang["action-edit"]?> </a>
			</div>
			</td>
		</tr>
		<?php	
		}
	
	}
?>
</table>
</div>


<a href="<?php echo $baseurl_short?>plugins/rse_workflow/pages/edit_action.php?ref=new" onclick="event.preventDefault();CentralSpaceLoad(this,true);"><?php echo LINK_CARET . $lang["rse_workflow_action_new"] ?></a>


</div>
</div>
<?php

include '../../../include/footer.php';
