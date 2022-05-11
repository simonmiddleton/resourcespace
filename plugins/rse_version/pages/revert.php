<?php
namespace RseVersion;

include '../../../include/db.php';
include '../../../include/authenticate.php'; 
include_once '../../../include/image_processing.php';
include_once '../include/rse_version_functions.php';

if(is_valid_revert_state_request())
    {
    process_revert_state_form();
    include "../../../include/header.php";
    render_revert_state_form();
    include "../../../include/footer.php";
    exit();
    }

$ref=getval("ref","");

# Load log entry
$log=ps_query("SELECT resource_log.*, rtf.ref `resource_type_field_ref`, rtf.type `resource_type_field_type` from resource_log 
        left outer join resource_type_field rtf on resource_log.resource_type_field=rtf.ref 
        where resource_log.ref=?",array("i",$ref));
if (count($log)==0) 
    {
    exit($lang["rse_version_log_not_found"]);
    }
$log=$log[0];
$resource=$log["resource"];

# Check edit permission.
if (!get_edit_access($resource))
    {
    # The user is not allowed to edit this resource or the resource doesn't exist.
    $error=$lang['error-permissiondenied'];
    error_alert($error,true, 401);
    exit();
    }

$field=$log["resource_type_field"];
$type=$log["type"];

$nodes_to_add=array();
$nodes_to_remove=array();
$node_strings_not_found=array();

$b_fixed_field=in_array($log['resource_type_field_type'],$FIXED_LIST_FIELD_TYPES);

// resolve node changes
if($b_fixed_field)
    {
    $is_cat_tree = ($log["resource_type_field_type"] == FIELD_TYPE_CATEGORY_TREE);
    $nodes_available=array();
    foreach(get_nodes($log['resource_type_field'], null, $is_cat_tree) as $available_node)
        {
        $nodes_available[$available_node['ref']]=$available_node['name'];
        }

    // all to be added
    preg_match_all('/^\s*\-\s*(.*?)$/m',$log['diff'],$matches);
    if(isset($matches[1][0]))
        {
        foreach ($matches[1] as $match)
            {
            $match=trim($match);
            $found_key=array_search($match,$nodes_available);
            if($found_key===false)
                {
                $node_strings_not_found[]=$match;
                }
            else
                {
                $nodes_to_add[]=$found_key;
                }
            }
        }

    // all to be removed
    preg_match_all('/^\s*\+\s*(.*?)$/m',$log['diff'],$matches);
    if(isset($matches[1][0]))
        {
        foreach ($matches[1] as $match)
            {
            $match=trim($match);
            $found_key=array_search($match,$nodes_available);
            if($found_key===false)
                {
                $node_strings_not_found[]=$match;
                }
            else
                {
                $nodes_to_remove[]=$found_key;
                }
            }
        }
    }

if ($type==LOG_CODE_EDITED || $type==LOG_CODE_MULTI_EDITED || $type==LOG_CODE_NODE_REVERT)
    {
    # ----------------------------- PROCESSING FOR "e" (edit) and "m" (multi edit) METADATA ROWS ---------------------------------------------

    $current=get_data_by_field($resource,$field);
    $diff=log_diff($current,$log["previous_value"]);

    # Process submit
    if (getval("revert_action","")=="revert" && enforcePostRequest(false))
        {
        if($b_fixed_field)
            {
            if(count($nodes_to_remove)>0)
                {
                delete_resource_nodes($resource,array_values($nodes_to_remove),false);
                }        
            if(count($nodes_to_add)>0)
                {
                add_resource_nodes($resource, array_values($nodes_to_add),false,false);
                }       

            # If this is a 'joined' field we need to add it to the resource column
            $joins = get_resource_table_joins();
            if(in_array($field, $joins))
                {
                // Get all options selected for this resource and field
                $resource_field_data       = get_resource_field_data($resource);
                $resource_field_data_index = array_search($field, array_column($resource_field_data, 'ref'));

                $truncated_value = NULL;
                if(
                    $resource_field_data_index !== false
                    && trim($resource_field_data[$resource_field_data_index]["value"]) != ""
                    )
                    {
                    $new_joined_field_value = $resource_field_data[$resource_field_data_index]["value"];
                    $truncated_value = truncate_join_field_value($new_joined_field_value);
                    }
 
                if (is_null($truncated_value)) 
                    {
                    ps_query("UPDATE resource SET field{$field} = NULL WHERE ref = ?",array("i",$resource));
                    }
                else
                    {
                    ps_query("UPDATE resource SET field{$field} = ? WHERE ref = ?",array("s",$truncated_value, "i",$resource));
                    }
                }
            log_node_changes($resource,$nodes_to_add,$nodes_to_remove,$lang["revert_log_note"]);
            }
        else
            {
            $errors=array();
            update_field($resource, $field, $log["previous_value"],$errors,false); # Do not log as we are doing that below.
            resource_log($resource,LOG_CODE_EDITED,$field,$lang["revert_log_note"],$current,$log["previous_value"]);
            }
        redirect("pages/view.php?ref=" . $resource);
        }
    }
elseif($type==LOG_CODE_UPLOADED)
    {
    # ----------------------------- PROCESSING FOR "u" IMAGE UPLOAD ROWS ---------------------------------------------
    
    # Process submit
    if (getval("revert_action","")=="revert" && enforcePostRequest(false))
        {
        # Perform the reversion. First this reversion itself needs to be logged and therefore 'revertable'.
        
        # Find file extension of current resource.
        $old_extension=ps_value("select file_extension value from resource where ref=?",array("i",$resource),"");
        
        # Ceate a new alternative file based on the current resource
        $alt_file=add_alternative_file($resource,'','','',$old_extension,0,'');
        $new_path = get_resource_path($resource, true, '', true, $old_extension, -1, 1, false, "", $alt_file);
        
        # Copy current file to alternative file.
        $old_path=get_resource_path($resource,true, '', true, $old_extension);
        if (file_exists($old_path))
            {
            copy($old_path,$new_path);
            }
        else
            {
            echo "Missing file: $old_path ($old_extension)";
            exit();
            }
            
        # Also copy thumbnail
        $old_thumb=get_resource_path($resource,true,'thm',true,"");
        if (file_exists($old_thumb))
            {
            $new_thumb=get_resource_path($resource, true, 'thm', true, "", -1, 1, false, "", $alt_file);
            copy($old_thumb,$new_thumb);
            }
            
        # Update log so this has a pointer.
        $log_ref=resource_log($resource,LOG_CODE_UPLOADED,0,$lang["revert_log_note"]);
        $parameters=array("i",$alt_file, "i",$log_ref);
        ps_query("update resource_log set previous_file_alt_ref=? where ref=?",$parameters);
    
        # Now perform the revert, copy and recreate previews.
        $revert_alt_ref=$log["previous_file_alt_ref"];
        $revert_ext=ps_value("select file_extension value from resource_alt_files where ref=?",array("i",$revert_alt_ref),"");
        
        $revert_path=get_resource_path($resource, true, '', true, $revert_ext, -1, 1, false, "", $revert_alt_ref);
        $current_path=get_resource_path($resource,true, '', true, $revert_ext);
        if (file_exists($revert_path))
            {
            copy($revert_path,$current_path);
            $parameters=array("i",$revert_ext, "i",$resource);
            ps_query("update resource set file_extension=? where ref=?",$parameters);
            create_previews($resource,false,$revert_ext);
            }
        else
            {
            echo "Revert fail... $revert_path not found.";exit();
            }
        redirect("pages/view.php?ref=" . $resource);
        }
    }

include "../../../include/header.php";
?>

<div class="BasicsBox">
<p><a href="<?php echo $baseurl_short ?>pages/log.php?ref=<?php echo $resource ?>" onClick="CentralSpaceLoad(this,true);return false;"><?php echo LINK_CARET_BACK ?><?php echo $lang["back"] ?></a></p>
<h1><?php echo $lang["revert"]?></h1>
<p><?php echo $lang['revertingclicktoproceed'];?></p>

<form method=post name="rse_revert_form" id="rse_revert_form" action="<?php echo $baseurl_short ?>plugins/rse_version/pages/revert.php" onSubmit="return CentralSpacePost(this,true);">
<input type="hidden" name="ref" value="<?php echo $ref ?>">
<input type="hidden" name="revert_action" value="revert">
<?php
generateFormToken("form");
if ($type==LOG_CODE_EDITED || $type==LOG_CODE_MULTI_EDITED || $type==LOG_CODE_NODE_REVERT)
    if ($b_fixed_field)
        {
        if(count($nodes_to_add)>0)
            {
?><div class="Question">
<label><?php echo $lang["revertingwilladd"]?></label>
    <div class="Fixed">
        <?php
        foreach($nodes_to_add as $node_to_add)
            {
            echo htmlspecialchars(i18n_get_translated($nodes_available[$node_to_add]));
            ?><br/>
            <?php
            }
?>    </div>
    <div class="clearerleft"> </div>
</div>
<?php       }
        if(count($nodes_to_remove)>0)
            {
?><div class="Question">
    <label><?php echo $lang["revertingwillremove"]?></label>
    <div class="Fixed">
        <?php
        foreach($nodes_to_remove as $nodes_to_remove)
            {
            echo htmlspecialchars(i18n_get_translated($nodes_available[$nodes_to_remove]));
            ?><br/>
            <?php
            }
        ?>    </div>
    <div class="clearerleft"> </div>
</div>
<?php       }
        if(count($node_strings_not_found)>0)
            {
?><div class="Question">
    <label><?php echo $lang["revertingwillignore"]?></label>
    <div class="Fixed">
        <?php
        foreach($node_strings_not_found as $node_string_not_found)
            {
            echo htmlspecialchars($node_string_not_found);
            ?><br/>
            <?php
            }
?>  </div>
    <div class="clearerleft"> </div>
    </div>
<?php       }
        }
    else
        { ?>
<div class="Question">
<label><?php echo $lang["revertingwillapply"]?></label>
<div class="Fixed"><?php echo nl2br(htmlspecialchars($diff)) ?></div>
<div class="clearerleft"> </div>
</div>
<?php   }

if ($type==LOG_CODE_UPLOADED) {
    # Fetch the thumbnail of the image
    $alt_file=$log["previous_file_alt_ref"];
    $alt_thumb=get_resource_path($resource, true, 'thm', true, "", -1, 1, false, "", $alt_file);
    if (file_exists($alt_thumb))
        { 
        $image=get_resource_path($resource, false, 'thm', true, "", -1, 1, false, "", $alt_file);
        }
    else
        {
        // If an image is not available, fetch a nopreview image based on extension   
        $alter_data = get_alternative_file($resource,$alt_file);    
        $image = $baseurl_short . 'gfx/' . get_nopreview_icon('', $alter_data['file_extension'], '');
        }?>
    <div class="Question">
    <label><?php echo $lang["revertingwillreplace"]?></label>

    <div class="Fixed">
    <img src="<?php echo $image ?>" alt="<?php echo $lang["preview"] ?>" />
    </div>
    <div class="clearerleft"> </div>
    </div>
        
    

<?php } ?>

<div class="QuestionSubmit">
    <label for="buttons"> </label>
    <input name="revert" type="submit" value="&nbsp;&nbsp;<?php echo $lang["revert"]?>&nbsp;&nbsp;" />
</div>

</form>
</div>

<?php
include "../../../include/footer.php";
?>
