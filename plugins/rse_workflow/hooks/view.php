<?php

function HookRse_workflowViewPageevaluation()
    {
    include_once (dirname(__file__) . "/../include/rse_workflow_functions.php");
    global $lang;
    global $ref;
    global $resource;
    global $baseurl;
    global $search;
    global $offset;
    global $order_by;
    global $archive;
    global $sort;
    global $k;
    global $applicationname;
    global $userref;
    
    # Retrieve list of existing defined actions 
    $workflowactions = rse_workflow_get_actions();
    //$validactions=array();
      
    foreach ($workflowactions as $workflowaction)
        {
        if(getvalescaped("rse_workflow_action_" . $workflowaction["ref"],"")!="" && enforcePostRequest(false))
            {
            // Check if resource status has already been changed between form being loaded and submitted
            $resource_status_check_name = "resource_status_check_" . $workflowaction["ref"];
            $resource_status_check = getval($resource_status_check_name,"");
			if($resource_status_check != "" && $resource_status_check != $resource["archive"])
				{
				$errors["status"] = $lang["status"] . ': ' . $lang["save-conflict-error"];
				echo "<div class=\"PageInformal\">" . $lang["error"] . ": " . $lang["status"] . " - " . $lang["save-conflict-error"] . "</div>";
				}
			else
				{
                $validstates = explode(',', $workflowaction['statusfrom']);
                $edit_access = get_edit_access($ref,$resource['archive'], '', $resource);
    
                if('' != $k || ($resource["lock_user"] > 0 && $resource["lock_user"] != $userref))
                    {
                    $edit_access = 0;
                    }
                    
                if(
                    in_array($resource['archive'], $validstates)
                    && (
                            (
                                $edit_access
                                && checkperm("e{$workflowaction['statusto']}")
                            )
                            || checkperm("wf{$workflowaction['ref']}")
                       )
                    )
                    {
                    update_archive_status($ref, $workflowaction["statusto"],$resource["archive"]);
                    hook("rse_wf_archivechange","",array($ref,$resource["archive"],$workflowaction["statusto"]));
                                                
                    if (checkperm("z" . $workflowaction["statusto"]))
                        {
                        ?>
                        <script type="text/javascript">
                        styledalert('<?php echo $lang["rse_workflow_saved"] . "','" . $lang["status" . $workflowaction["statusto"]];?>');
                        document.location.href="<?php echo $baseurl ?>/pages/search.php?ref=<?php echo urlencode($ref)?>&search=<?php echo urlencode($search)?>&offset=<?php echo urlencode($offset)?>&order_by=<?php echo urlencode($order_by)?>&sort=<?php echo urlencode($sort)?>&archive=<?php echo urlencode($archive)?>";
                        </script><?php
                        exit();
                        }
                    else
                        { 
                        echo "<div class=\"PageInformal\">" . $lang["rse_workflow_saved"] . " " . $lang["status" . $workflowaction["statusto"]] . "</div>";
                        $resource["archive"]=$workflowaction["statusto"];
                        }
                    } 
                }
            }
        }
    }

function HookRse_workflowViewRenderbeforeresourcedetails()
    {
    include_once (dirname(__file__) . "/../include/rse_workflow_functions.php");

    global $lang, $ref, $resource, $baseurl_short, $search, $offset, $order_by, $archive, $sort, $edit_access, $curpos,
           $userref, $k, $internal_share_access;

    if(!empty($resource["lock_user"]) && $resource["lock_user"] != 0 && $resource["lock_user"] != $userref)
        {
        return false;
        }

    if($k != "" && $internal_share_access === false)
        {
        return false;
        }

    $validactions = rse_workflow_get_valid_actions(rse_workflow_get_actions(), false);

    if(count($validactions)>0)
        {?>
        <div class="RecordDownload" id="ResourceWorkflowActions">
        <div class="RecordDownloadSpace" >
        <h2><?php echo $lang["rse_workflow_actions_heading"]?></h2>
        <p><?php echo $lang['rse_workflow_user_info']; ?></p>
        <script type="text/javascript">
        function open_notes(action_ref) {
            var workflow_action = jQuery('#rse_workflow_action_' + action_ref);
            var more_link = jQuery('#more_link_' + action_ref);

            // Populate textarea with any text there may already be present
            var more_text_hidden = jQuery('#more_workflow_action_' + action_ref).val();

            more_link.after('<textarea id="more_for_workflow_action_' + action_ref 
                + '" name="more_for_workflow_action_' + action_ref 
                + '" style="width: 100%; resize: none;" rows="6">' + more_text_hidden + '</textarea>');
            more_link.after('<p id="notes_for_workflow_action_' + action_ref + '"><?php echo $lang["rse_workflow_more_notes_title"]; ?></p>');

            more_link.text('<?php echo $lang["rse_workflow_link_close"]; ?>');
            more_link.attr('onClick', 'close_notes(' + action_ref + ');');

            // Bind the input textarea 'more_for_workflow_action' value to the hidden 'more_workflow_action' field
            jQuery('#more_for_workflow_action_' + action_ref).keyup(function (event) {
                var notes = this.value;
                jQuery('#more_workflow_action_' + action_ref).val(notes);
            });
        }

        function close_notes(action_ref) {

            var more_link = jQuery('#more_link_' + action_ref);
            var notes_title = jQuery('#notes_for_workflow_action_' + action_ref);
            var notes_textarea = jQuery('#more_for_workflow_action_' + action_ref);

            // Remove Notes title and textarea from DOM:
            notes_title.remove();
            notes_textarea.remove();

            more_link.text('<?php echo $lang["rse_workflow_link_open"]; ?>');
            more_link.attr('onClick', 'open_notes(' + action_ref + ');');

        }
        </script>
        <table cellpadding="0" cellspacing="0" id="ResourceWorkflowTable">
            <tbody>
            <?php
         
        foreach($validactions as $validaction)
            {
                $show_more_link = false;
                if(!empty($validaction['more_notes_flag']) && $validaction['more_notes_flag'] == 1) {
                    $show_more_link = true;
                }
            ?>
             <tr class="DownloadDBlend">
                <td><?php echo i18n_get_translated($validaction["text"]); if($show_more_link) { ?><a href="#" id="more_link_<?php echo $validaction["ref"]; ?>" onClick="open_notes(<?php echo $validaction["ref"]; ?>);" style="float: right;"><?php echo $lang['rse_workflow_link_open']; ?></a><?php } ?></td>
                <td>
					<form action="<?php echo $baseurl_short?>pages/view.php?ref=<?php echo urlencode($ref)?>&search=<?php echo urlencode($search)?>&offset=<?php echo urlencode($offset)?>&order_by=<?php echo urlencode($order_by)?>&sort=<?php echo urlencode($sort)?>&archive=<?php echo urlencode($archive)?>&curpos=<?php echo urlencode($curpos)?>&workflowaction=<?php echo urlencode($validaction["ref"])?>" 
                          id="resource_<?php echo $ref; ?>_workflowaction<?php echo $validaction['ref']; ?>">
					<input id='resource_status_checksum_<?php echo $validaction["ref"] ?>' name='resource_status_check_<?php echo $validaction["ref"] ?>' type='hidden' value='<?php echo $resource["archive"]; ?>'>
					<input type="hidden" name="rse_workflow_action_<?php echo $validaction["ref"] ?>" id="rse_workflow_action_<?php echo $validaction["ref"] ?>" value="true" >
					<input type="hidden" name="more_workflow_action_<?php echo $validaction["ref"] ?>" id="more_workflow_action_<?php echo $validaction["ref"] ?>" value="" >       
					<input type="submit" name="rse_workflow_action_submit_<?php echo $validaction["ref"] ?>" id="rse_workflow_action_submit_<?php echo $validaction["ref"]?>" value="&nbsp;<?php echo i18n_get_translated($validaction["buttontext"]) ?>&nbsp;" onClick="return CentralSpacePost(document.getElementById('resource_<?php echo $ref; ?>_workflowaction<?php echo $validaction['ref']; ?>'), true);" >
					<?php
                    generateFormToken("resource_{$ref}_workflowaction{$validaction['ref']}");
                    hook("rse_wf_formend","",array($resource["archive"],$validaction["statusto"]));
                    ?>
				</form>
				</td>
            </tr>                               
            
            
            
            <?php
            }?>
        </tbody></table>
        </div><!-- End of RecordDownloadSpace-->
        </div> <!-- End of RecordDownload-->
        <?php
        }
    }
    
function HookRse_workflowViewReplacetitleprefix($state)
    {
    global $lang,$additional_archive_states;

    if ($state<=3) {return false;} # For custom states only.

    $name=sql_value("select name value from archive_states where code='$state'","");
    
    ?><span class="ResourceTitleWorkflow<?php echo $state ?>"><?php echo i18n_get_translated($name) ?>:</span>&nbsp;<?php
    return true;
    }
    
    
