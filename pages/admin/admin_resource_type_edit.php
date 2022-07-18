<?php
/**
 * User edit form display page (part of Team Center)
 * 
 * @package ResourceSpace
 * @subpackage Pages_Team
 */
include "../../include/db.php";

include "../../include/authenticate.php"; 
include "../../lib/fontawesome/resourcespace/icon_classes.php";

if (!checkperm("a"))
	{
	exit ("Permission denied.");
	}

$ref                   = getval('ref', '', true);
$name                  = getval('name', '');
$config_options        = getval('config_options', '');
$allowed_extensions    = getval('allowed_extensions', '');
$tab                   = (int) getval('tab', 0);
$colour                = getval('colour', 0, true);
$push_metadata         = ('' != getval('push_metadata', '') ? 1 : 0);
$inherit_global_fields = ('' != getval('inherit_global_fields', '') ? 1 : 0);
$icon                  = getval('icon', '');

$restype_order_by=getval("restype_order_by","rt");
$restype_sort=getval("restype_sort","asc");

$url_params = array("ref"=>$ref,
		    "restype_order_by"=>$restype_order_by,
		    "restype_sort"=>$restype_sort);
$url=generateURL($baseurl . "/pages/admin/admin_resource_type_edit.php",$url_params);

$backurl=getval("backurl","");
if($backurl=="")
    {
    $backurl=$baseurl . "/pages/admin/admin_resource_types.php?ref=" . $ref;
    }

if (getval("save","")!="" && enforcePostRequest(false))
    {
    # Save resource type data
    log_activity(null,LOG_CODE_EDITED,$name,'resource_type','name',$ref);
    log_activity(null,LOG_CODE_EDITED,$config_options,'resource_type','config_options',$ref);
    log_activity(null,LOG_CODE_EDITED,$allowed_extensions,'resource_type','allowed_extensions',$ref);
    log_activity(null,LOG_CODE_EDITED,$tab,'resource_type','tab',$ref);

    if ($execution_lockout) {$config_options="";} # Not allowed to save PHP if execution_lockout set.

    ps_query(
        "UPDATE resource_type
            SET `name` = ?,
                config_options = ?,
                allowed_extensions = ?,
                tab = ?,
                push_metadata = ?,
                inherit_global_fields = ?,
                colour = ?,
                icon = ?
          WHERE ref = ?",
        [
        's', $name,
        's', $config_options,
        's', $allowed_extensions,
        'i', $tab ?: null,
        'i', $push_metadata,
        'i', $inherit_global_fields,
        'i', $colour,
        's', $icon,
        'i', $ref,
        ]
    );
    clear_query_cache("schema");

    redirect(generateURL($baseurl_short . "pages/admin/admin_resource_types.php",$url_params));
    }


$confirm_delete = false;
$confirm_move_associated_rtf = false;
if(getval("delete", "") != "" && enforcePostRequest(false))
    {
    $targettype=getval("targettype","");
    $prereq_action = getval("prereq_action", "");
    $affectedresources=ps_array("select ref value from resource where resource_type=? and ref>0",array("i",$ref),0);
    $affected_rtfs = get_resource_type_fields(array($ref), "ref", "asc", "", array(), true);
    if(count($affectedresources)>0 && $targettype=="")
        {
        //User needs to confirm a new resource type
        $confirm_delete=true;
        }
    else if(count($affected_rtfs) > 0 && $targettype == "")
        {
        $confirm_move_associated_rtf = true;
        }
    else
        {
        //If we have a target type, move the current resources to the new resource type
        if($targettype!="" && $targettype!=$ref)
            {
            if($prereq_action == "move_affected_resources")
                {
                foreach($affectedresources as $affectedresource)
                    {
                    update_resource_type($affectedresource,$targettype);
                    }
                }

            if($prereq_action == "move_affected_rtfs")
                {
                foreach($affected_rtfs as $affected_rtf)
                    {
                    ps_query("UPDATE resource_type_field SET resource_type = ? 
                               WHERE ref = ?",array("i",$targettype, "i",$affected_rtf['ref']));
                    clear_query_cache("schema");
                    }
                }
            }

        $affectedresources = ps_array("SELECT ref AS value FROM resource WHERE resource_type = ? AND ref > 0",array("i",$ref),0);
        $affected_rtfs = get_resource_type_fields(array($ref), "ref", "asc", "", array(), true);
        if(count($affectedresources) === 0 && count($affected_rtfs) === 0)
            {
            ps_query("DELETE from resource_type where ref=?",array("i",$ref));
            clear_query_cache("schema");
            redirect(generateURL($baseurl_short . "pages/admin/admin_resource_types.php",$url_params));
            }
        }
    }
$actions_required = ($confirm_delete || $confirm_move_associated_rtf);

# Fetch data
$restypedata=ps_query(
    "SELECT ref, name, order_by, config_options, allowed_extensions, tab, push_metadata, inherit_global_fields, colour, icon
       FROM resource_type
      WHERE ref = ?
    ORDER BY `name`",
    array("i",$ref),
    "schema"
);
if (count($restypedata)==0) {exit("Resource type not found.");} // Should arrive here unless someone has an old/incorrect URL.
$restypedata=$restypedata[0];

$inherit_global_fields_checked = ((bool) $restypedata['inherit_global_fields'] ? 'checked' : '');

include "../../include/header.php";
?>
<script src="<?php echo $baseurl_short ?>lib/chosen/chosen.jquery.min.js" type="text/javascript"></script>
<link rel="stylesheet" href="<?php echo $baseurl_short ?>lib/chosen/chosen.min.css">

<div class="BasicsBox">
<h1><?php echo htmlspecialchars(i18n_get_translated($restypedata["name"])); ?></h1>
<?php
$links_trail = array(
    array(
        'title' => $lang["systemsetup"],
        'href'  => $baseurl_short . "pages/admin/admin_home.php",
		'menu' =>  true
    ),
    array(
        'title' => $lang["resource_types_manage"],
        'href'  => $backurl
    ),
    array(
        'title' => htmlspecialchars(i18n_get_translated($restypedata["name"])),
        'help'  => "resourceadmin/resource-types"
    )
);

renderBreadcrumbs($links_trail);
?>
<?php if (isset($error_text)) { ?><div class="FormError"><?php echo $error_text?></div><?php } ?>
<?php if (isset($saved_text)) { ?><div class="PageInfoMessage"><?php echo $saved_text?></div><?php } ?>

<form method=post action="<?php echo $baseurl_short?>pages/admin/admin_resource_type_edit.php?ref=<?php echo urlencode($ref) ?>&backurl=<?php echo urlencode ($url) ?>">
<?php
generateFormToken("admin_resource_type_edit");

if($actions_required)
    {
    ?>
    <div class="PageInfoMessage">
    <?php
    if($confirm_delete)
        {
        echo str_replace("%%RESOURCECOUNT%%",count($affectedresources),$lang["resource_type_delete_confirmation"]) . "<br />";
        ?>
        <input type="hidden" name="prereq_action" value="move_affected_resources">
        <?php
        }
    else if($confirm_move_associated_rtf)
        {
        echo str_replace("%COUNT", count($affected_rtfs), $lang["resource_type_delete_assoc_rtf_confirm"]) . "<br>";
        ?>
        <input type="hidden" name="prereq_action" value="move_affected_rtfs">
        <?php
        }
    
    echo $lang["resource_type_delete_select_new"];
    ?>
    </div>
    <?php
    
    $destrestypes=$resource_types= ps_query("SELECT ref, name FROM resource_type
	                                          WHERE ref<>?
	                                          ORDER BY name asc",array("i",$ref));
    ?>
    <div class="Question">  
    <label for="targettype"><?php echo $lang["resourcetype"]; ?></label>    
    <div class="tickset">
      <div class="Inline"><select name="targettype" id="targettype" >
        <option value="" selected ><?php echo $lang["select"]; ?></option>
	<?php
    if($confirm_move_associated_rtf)
        {
        ?>
        <option value="0"><?php echo $lang["resourcetype-global_field"]; ?></option>
        <?php
        }
	  for($n=0;$n<count($destrestypes);$n++){
	?>
		<option value="<?php echo $destrestypes[$n]["ref"]; ?>"><?php echo htmlspecialchars(i18n_get_translated($destrestypes[$n]["name"])); ?></option>
	<?php
	  }
	?>
        </select>
      </div>
    </div>
	<div class="clearerleft"> </div>
    </div>
    <div class="QuestionSubmit">
        <label for="buttons"> </label>			
        <input name="cancel" type="submit" value="&nbsp;&nbsp;<?php echo $lang["cancel"]?>&nbsp;&nbsp;" />
        <input name="delete" type="submit" value="&nbsp;&nbsp;<?php echo $lang["action-delete"]?>&nbsp;&nbsp;" onClick="return confirm('<?php echo $lang["confirm-deletion"]?>');"/>
    </div>
    <?php
    exit();	
    }
else
    {
?> 
    
    <input type=hidden name=ref value="<?php echo urlencode($ref) ?>">
    
    <div class="Question"><label><?php echo $lang["property-reference"]?></label>
	<div class="Fixed"><?php echo  $restypedata["ref"] ?></div>
	<div class="clearerleft"> </div>
    </div>
    
    <div class="Question">
	<label><?php echo $lang["property-name"]?></label>
	<input name="name" type="text" class="stdwidth" value="<?php echo htmlspecialchars($restypedata["name"])?>" />
	<div class="clearerleft"> </div>
    </div>

    <?php
    render_fa_icon_selector($lang["property-icon"],"icon",($restypedata['icon'] ?? ""));
    ?>
    
    <div class="Question">
	<label><?php echo $lang["property-allowed_extensions"]?></label>
	<input name="allowed_extensions" type="text" class="stdwidth" value="<?php echo htmlspecialchars($restypedata["allowed_extensions"])?>" />
	
	<div class="FormHelp" style="padding:0;clear:left;" >
	    <div class="FormHelpInner"><?php echo $lang["information-allowed_extensions"] ?>
	    </div>
	</div>    
	<div class="clearerleft"> </div>    
    </div>
    
    <?php if (!$execution_lockout) { ?>
    <div class="Question">
	<label><?php echo $lang["property-override_config_options"] ?></label>
	<textarea name="config_options" class="stdwidth" rows=5 cols=50><?php echo htmlspecialchars($restypedata["config_options"])?></textarea>
	<div class="FormHelp" style="padding:0;clear:left;" >
	    <div class="FormHelpInner"><?php echo $lang["information-resource_type_config_override"] ?>
	    </div>
	</div>
	<div class="clearerleft"> </div>
    </div>
    <?php }

    render_dropdown_question($lang['property-tab_name'], 'tab', get_tab_name_options(), $restypedata['tab']);

    $MARKER_COLORS[-1] = $lang["select"];
    ksort($MARKER_COLORS);
    render_dropdown_question($lang['resource_type_marker_colour'],"colour",$MARKER_COLORS,$restypedata["colour"],'',array("input_class"=>"stdwidth"));
    ?>
    
        <div class="Question">
    <label><?php echo $lang["property-push_metadata"]?></label>
    <input name="push_metadata" type="checkbox" value="yes" <?php if ($restypedata["push_metadata"]==1) { echo "checked"; } ?> />
    <div class="FormHelp" style="padding:0;clear:left;" >
        <div class="FormHelpInner"><?php echo $lang["information-push_metadata"] ?>
        </div>
    </div>
    <div class="clearerleft"> </div>
    </div>

    <div class="Question">
        <label><?php echo $lang['property-inherit_global_fields']; ?></label>
        <input name="inherit_global_fields" type="checkbox" value="yes" <?php echo $inherit_global_fields_checked; ?> />
        <div class="FormHelp" style="padding:0;clear:left;" >
            <div class="FormHelpInner"><?php echo $lang['information-inherit_global_fields']; ?></div>
        </div>
        <div class="clearerleft"></div>
    </div>
    
    <div class="QuestionSubmit">
    <label for="buttons"> </label>			
    <input name="save" type="submit" value="&nbsp;&nbsp;<?php echo $lang["save"]?>&nbsp;&nbsp;" />
    <input name="delete" type="submit" value="&nbsp;&nbsp;<?php echo $lang["action-delete"]?>&nbsp;&nbsp;" onClick="return confirm('<?php echo $lang["confirm-deletion"]?>');"/>
    </div>
    <?php
    } // End of normal page (not confirm deletion)
    ?>

</form>
</div><!-- End of Basics Box -->
<?php
include "../../include/footer.php";
?>
