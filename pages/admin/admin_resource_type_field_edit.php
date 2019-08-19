<?php
/**
 * User edit form display page (part of Team Center)
 * 
 * @package ResourceSpace
 * @subpackage Pages_Team
 */
include "../../include/db.php";
include_once "../../include/general.php";
include "../../include/authenticate.php"; 

if (!checkperm("a"))
	{
	exit ("Permission denied.");
	}

include "../../include/resource_functions.php";

$ref=getvalescaped("ref","",true);

$find=getvalescaped("find","");
$restypefilter=getvalescaped("restypefilter","",true);
$field_order_by=getvalescaped("field_order_by","ref");
$field_sort=getvalescaped("field_sort","asc");
$newfield = getval("newfield","") != "";
$ajax = getval('ajax', '');
$url_params = array("ref"=>$ref,
		    "restypefilter"=>$restypefilter,
		    "$field_order_by"=>$field_order_by,
		    "field_sort"=>$field_sort,
		    "find" =>$find);
		
$backurl=getvalescaped("backurl","");
if($backurl=="")
    {
    $backurl=$baseurl . "/pages/admin/admin_resource_type_fields.php?ref=" . urlencode($ref) . "&restypefilter=" . urlencode($restypefilter) . "&field_sort=" . urlencode($field_sort) . "&find=" . urlencode($find);
    }
else
	{
	$back_url_params=parse_url($backurl, PHP_URL_QUERY);
	# the first parameter of the back url is needed here but isn't captured
	$back_url_params=explode('&', $back_url_params);
	
	foreach($back_url_params as $param)
		{
		$param_parts=explode('=', $param);
		switch($param_parts[0])
			{
			case 'restypefilter':
				$restypefilter=$param_parts[1];
				break;
			case 'field_order_by':
				$field_order_by=$param_parts[1];
				break;
			case 'field_sort':
				$field_sort=$param_parts[1];
				break;
			case 'find':
				$find=$param_parts[1];
				break;
			}
		}
	
	}
	

$url=generateURL($baseurl . "/pages/admin/admin_resource_type_field_edit.php",$url_params);

function admin_resource_type_field_constraint($ref, $currentvalue)
	{
	global $lang;
 	
	$addconstraint=true;
	$constraint=sql_value("select field_constraint value from resource_type_field where ref='$ref'",0);
	?>
		<div class="clearerleft"></div>
	</div> <!-- end question -->
	<div class="Question">
		<label><?php echo $lang["property-field_constraint"]?></label>
		<select id="field_constraint" name="field_constraint" class="stdwidth" onchange="CentralSpacePost(this.form);">

			<option value="0" <?php if ($constraint==0) { echo " selected"; } ?>><?php echo $lang["property-field_constraint-none"]?></option>
			<option value="1" <?php if ($constraint==1) { echo " selected"; } ?>><?php echo ($currentvalue==FIELD_TYPE_TEXT_BOX_SINGLE_LINE ? $lang["property-field_constraint-number"] : $lang["property-field_constraint-singlekeyword"])?></option>
		</select>
		<?php
	}
	
function admin_resource_type_field_option($propertyname,$propertytitle,$helptext="",$type, $currentvalue,$fieldtype)
	{
	global $ref,$lang, $baseurl_short,$FIXED_LIST_FIELD_TYPES, $TEXT_FIELD_TYPES, $daterange_edtf_support, $allfields, $newfield;
	if($propertyname=="linked_data_field")
		{
		if($fieldtype==FIELD_TYPE_DATE_RANGE && $daterange_edtf_support)
			{
			// The linked_data_field column is is only used for date range fields at present
			$propertytitle = $lang["property-field_raw_edtf"];
			}
		else
			{
			return;
			}
		}
		
	$alt_helptext=hook('rtfieldedithelptext', 'admin_resource_type_field_edit', array($propertyname));
	if($alt_helptext!==false){
	    $helptext=$alt_helptext;
	}
	
	?>
	<div class="Question" >
		<label><?php echo ($propertytitle!="")?$propertytitle:$propertyname ?></label>
		<?php
		if($propertyname=="resource_type")
			{
			global $resource_types;
			?>
            <select id="<?php echo $propertyname ?>" name="<?php echo $propertyname ?>" class="stdwidth">
            <option value="0"<?php if ($currentvalue == "0" || $currentvalue == "") { echo " selected"; } ?>><?php echo $lang["resourcetype-global_field"]; ?></option>

            <?php
              for($n=0;$n<count($resource_types);$n++){
            ?>
            <option value="<?php echo $resource_types[$n]["ref"]; ?>"<?php if ($currentvalue == $resource_types[$n]["ref"]) { echo " selected"; } ?>><?php echo i18n_get_translated($resource_types[$n]["name"]); ?></option>
            <?php
              }
            ?>

            <option value="999"<?php if ($currentvalue == "999") { echo " selected"; } ?>><?php echo $lang["resourcetype-archive_only"]; ?></option>
            </select>
			<?php
			}
		elseif($propertyname=="type")
			{
			global $field_types;
			
			// Sort  so that the display order makes some sense
			//natsort($field_types);
			?>
                <select id="<?php echo $propertyname ?>"
                        name="<?php echo $propertyname ?>"
                        class="stdwidth"
                        onchange="
                             <?php if(!$newfield)
								{?>
								newval=parseInt(this.value);
								if((jQuery.inArray(newval,fixed_list_fields) > -1) && (jQuery.inArray(current_type,text_fields) > -1))
									{
                                    jQuery('input[name=\'keywords_index\']')[0].checked = true;

									if(confirm('<?php echo $lang["admin_resource_type_field_migrate_data_prompt"] ?>'))
										{
										jQuery('#migrate_data').val('yes');
										}
									else
										{
										jQuery('#migrate_data').val('');
										}

                                    this.form.submit();
									}
								else
									{
									this.form.submit();
									}
								<?php
								}
							else
								{
								?>
								this.form.submit();
								<?php
								}
								?>
                ">
				<?php
				foreach($field_types as $field_type=>$field_type_description)
					{
					?>
					<option value="<?php echo $field_type ?>"<?php if ($currentvalue == $field_type) { echo " selected"; } ?>><?php echo $lang[$field_type_description] ; ?></option>
					<?php
					}
				?>
				</select>
            <?php
            if (in_array($currentvalue, $FIXED_LIST_FIELD_TYPES))
                {
                ?>
                <div class="clearerleft"></div>
                </div> <!-- end question -->

                <div class="Question">
                    <label><?php echo $lang['options']; ?></label>
                    <span><a href="<?php echo $baseurl_short ?>pages/admin/admin_manage_field_options.php?field=<?php echo $ref ?>" onClick="return CentralSpaceLoad(this,true);"><?php echo $lang['property-options_edit_link']; ?></a></span>
                    <div class="clearerleft"></div>

                <?php
                if(FIELD_TYPE_CATEGORY_TREE != $currentvalue)
                    {
                    ?>
                    </div>
                    <?php
                    $field_index              = array_search($ref, array_column($allfields, 'ref'));
                    $automatic_nodes_ordering = (false !== $field_index ? $allfields[$field_index]['automatic_nodes_ordering'] : 0);
                    ?>
                    <div class="Question">
                        <label><?php echo $lang['property-automatic_nodes_ordering_label']; ?></label>
                        <input type="checkbox" name="automatic_nodes_ordering" value="1"<?php if(1 == $automatic_nodes_ordering) { ?> checked="checked"<?php } ?>>
                    <?php
                    // create constraints selector
					admin_resource_type_field_constraint($ref, $currentvalue);
                    }
                }            
            elseif (in_array($currentvalue, array(FIELD_TYPE_TEXT_BOX_SINGLE_LINE)))
                { // create constraints selector
				admin_resource_type_field_constraint($ref, $currentvalue);
                }			
			}
		elseif($propertyname=="linked_data_field")
			{
			if ($fieldtype==FIELD_TYPE_DATE_RANGE && $daterange_edtf_support)
				{
				// The linked_data_field column is is only used for date range fields at present			
				// Used to store the raw EDTF string submitted
				?>
				<input name="linked_data_field" type="text" class="stdwidth" value="<?php echo htmlspecialchars($currentvalue)?>">
				<?php
				}
			}
		elseif($propertyname=="sync_field")
			{
			global $allfields, $resource_type_array;
			
			// Sort  so that the display order makes some sense
			
			?>
			  <select id="<?php echo $propertyname ?>" name="<?php echo $propertyname ?>" class="stdwidth">
				<option value="" <?php if ($currentvalue == "") { echo " selected"; } ?>><?php echo $lang["select"]; ?></option>
				<?php
				foreach($allfields as $field)
					{
					if($field["ref"]!=$ref && isset($resource_type_array[$field["resource_type"]])) // Don't show itself as an option to sync with
					    {?>
					    <option value="<?php echo $field["ref"] ?>"<?php if ($currentvalue == $field["ref"]) { echo " selected"; } ?>><?php echo i18n_get_translated($field["title"])  . "&nbsp;(" . (($field["name"]=="")?"":htmlspecialchars($field["name"]) . " - ") . i18n_get_translated($resource_type_array[$field["resource_type"]]) . ")"?></option>
					    <?php
					    }
					}
				?>				
				</select>
			<?php
			}
		elseif($type==1)
			{
			?>
			<input name="<?php echo $propertyname ?>" type="checkbox" value="1" <?php if ($currentvalue==1) { ?> checked="checked"<?php } ?>>
			<?php
			}
		elseif($type==2)
			{
			?>
			<textarea class="stdwidth" rows="5" id="<?php echo $propertyname ?>" name="<?php echo $propertyname ?>"><?php echo htmlspecialchars($currentvalue)?></textarea>
			<?php
			}
		else
			{
			?>
			<input name="<?php echo $propertyname ?>" type="text" class="stdwidth" value="<?php echo htmlspecialchars($currentvalue)?>">
			<?php
			}
		if($helptext!="")
				{
				?>
				<div class="FormHelp" style="padding:0;clear:left;" >
					<div class="FormHelpInner"><?php echo str_replace("%ref",$ref,$helptext) ?>
					</div>
				</div>
				<?php
				}
				?>
		<div class="clearerleft"> </div>
	</div>
	<?php
	}


// Define array of field properties containing title and associated lang help text, with a flag to indicate if it is a boolean value that we will save from POST data and boolean to indicate will be set with any 'synced' fields

// example field :-
// "name of table column"=>array(
// <language string for the friendly name of this property>,
// <lang string for the help text explaining what this property means>,
// <value to denote the field type(0=text,1=boolean,2=text area),
// < boolean value to indicate whether this is a field that is synchronised? 0=No 1=Yes > 
// )
// IMPORTANT - Make sure advanced field properties are listed after the 'partial_index' so that these will be hidden from users by default

$fieldcolumns = array(
    'title'                    => array($lang['property-title'],'',0,1),
    'resource_type'            => array($lang['property-resource_type'],'',0,0),
    'type'                     => array($lang['property-field_type'],'',0,1),
    'linked_data_field'        => array($lang['property-field_raw_edtf'],'',0,1),
    'name'                     => array($lang['property-shorthand_name'],$lang['information-shorthand_name'],0,1),
    'required'                 => array($lang['property-required'],'',1,1),
    'order_by'                 => array($lang['property-order_by'],'',0,1),
    'keywords_index'           => array($lang['property-index_this_field'],$lang['information-if_you_enable_indexing_below_and_the_field_already_contains_data-you_will_need_to_reindex_this_field'],1,1),
    'display_field'            => array($lang['property-display_field'],'',1,1),
    'advanced_search'          => array($lang['property-enable_advanced_search'],'',1,1),
    'simple_search'            => array($lang['property-enable_simple_search'],'',1,1),		
    'browse_bar'               => array($lang['field_show_in_browse_bar'],'',1,1),
    'read_only'                => array($lang['property-read_only_field'], '', 1, 1),
    'exiftool_field'           => array($lang['property-exiftool_field'],'',0,1),
    'fits_field'               => array($lang['property-fits_field'], $lang['information-fits_field'], 0, 1),
    'personal_data'            => array($lang['property-personal_data'],'',1,1),
    'use_for_similar'          => array($lang['property-use_for_find_similar_searching'],'',1,1),
    'hide_when_uploading'      => array($lang['property-hide_when_uploading'],'',1,1),
    'hide_when_restricted'     => array($lang['property-hide_when_restricted'],'',1,1),
    'help_text'                => array($lang['property-help_text'],'',2,1),
    'tooltip_text'             => array($lang['property-tooltip_text'],$lang['information-tooltip_text'],2,1),
    'tab_name'                 => array($lang['property-tab_name'],'',0,1),

    'partial_index'            => array($lang['property-enable_partial_indexing'],$lang['information-enable_partial_indexing'],1,1),
    'iptc_equiv'               => array($lang['property-iptc_equiv'],'',0,1),					
    'display_template'         => array($lang['property-display_template'],'',2,1),
    'display_condition'        => array($lang['property-display_condition'],$lang['information-display_condition'],2,1),
    'value_filter'             => array($lang['property-value_filter'],'',2,1),
    'regexp_filter'            => array($lang['property-regexp_filter'],$lang['information-regexp_filter'],2,1),
    'smart_theme_name'         => array($lang['property-smart_theme_name'],'',0,1),
    'exiftool_filter'          => array($lang['property-exiftool_filter'],'',2,1),
    'display_as_dropdown'      => array($lang['property-display_as_dropdown'],$lang['information-display_as_dropdown'],1,1),
    'external_user_access'     => array($lang['property-external_user_access'],'',1,1),
    'autocomplete_macro'       => array($lang['property-autocomplete_macro'],'',2,1),
    'omit_when_copying'        => array($lang['property-omit_when_copying'],'',1,1),
    'sync_field'               => array($lang['property-sync_with_field'],'',0,0),
    'onchange_macro'           => array($lang['property-onchange_macro'],$lang['information-onchange_macro'],2,1),
    'include_in_csv_export'    => array($lang['property-include_in_csv_export'],'',1,1)	
);

# Remove some items if $execution_lockout is set to prevent code execution
if ($execution_lockout)
	{
	unset($fieldcolumns["autocomplete_macro"]);
	unset($fieldcolumns["exiftool_filter"]);
	unset($fieldcolumns["value_filter"]);
	unset($fieldcolumns["onchange_macro"]);
	}

$modify_resource_type_field_columns=hook("modifyresourcetypefieldcolumns","",array($fieldcolumns));
if($modify_resource_type_field_columns!=''){
        $fieldcolumns=$modify_resource_type_field_columns;
}

$type_change = false;

if(getval("save","")!="" && getval("delete","")=="" && enforcePostRequest(false))
	{
	# Save field config
	$sync_field = getvalescaped("sync_field",0);
	$existingfield = get_resource_type_field($ref);
	
	foreach ($fieldcolumns as $column=>$column_detail)		
		{
		if ($column_detail[2]==1)
			{
			$val=getval($column,"0") ? "1" : "0";
			}		
		else
			{
			$val=escape_check(trim(getval($column,"")));			
			
			if($column == "type" && $val != $existingfield["type"] && getval("migrate_data","") != "")
				{
				// Need to migrate field data
				$migrate_data = true;				
				}
			
			// Set shortname if not already set or invalid
			if($column=="name" && ($val=="" || in_array($val,array("basicday","basicmonth","basicyear"))))
                {
                $val="field" . $ref;
                }
			}
		if (isset($sql))
			{
			$sql.=",";
			}
		else
			{
			$sql="update resource_type_field set ";
			}		
		
		$sql.="{$column}=" . (($val=="")?"NULL":"'{$val}'");
		log_activity(null,LOG_CODE_EDITED,$val,'resource_type_field',$column,$ref);

		// Add SQL to update synced fields if field is marked as a sync field
		if ($sync_field!="" && $sync_field>0 && $column_detail[3]==1)
			{
			if (isset($syncsql))
				{
				$syncsql.=",";
				}
			else
				{
				$syncsql="update resource_type_field set ";
				}
			$syncsql.="{$column}=" . (($val=="")?"NULL":"'{$val}'");
			}
		}
	// add field_constraint sql
	if (getvalescaped("field_constraint","")!=""){$sql.=",field_constraint='".getvalescaped("field_constraint",0)."'";}

    // Add automatic nodes ordering if set (available only for fixed list fields - except category trees)
    $sql .= ", automatic_nodes_ordering = '" . (1 == getval('automatic_nodes_ordering', 0, true) ? 1 : 0) . "'";

    $sql .= " WHERE ref = '{$ref}'";

	sql_query($sql);
	if($sync_field!="" && $sync_field>0)
		{
		$syncsql.=" where ref='$sync_field' or sync_field='$ref'";
		sql_query($syncsql);
		}
	
	hook('afterresourcetypefieldeditsave');
	
	$saved_text=$lang["saved"];
	//redirect($backurl);
	}

$confirm_delete=false;	
if (getval("delete","")!="" && enforcePostRequest($ajax))
	{	
	$confirmdelete=getvalescaped("confirmdelete","");
	# Check for resources of this  type
	$affected_resources=sql_array("select distinct resource value from
								  (
								  select resource from resource_data where resource>0 and resource_type_field='$ref'
								  UNION
								  select resource from resource_node where resource>0 and node in (select ref from node where resource_type_field='$ref')
								  ) all_resources
								  
								  ",0);
	
	$affected_resources_count=count($affected_resources);
	if($affected_resources_count==0 || $confirmdelete!="")
	    {	    
	     // Delete the resource type field
	    sql_query("delete from resource_type_field where ref='$ref'");
		log_activity(null,LOG_CODE_DELETED,null,'resource_type_field',null,$ref);

	    //Remove all data	    
	    sql_query("delete from resource_data where resource_type_field='$ref'");
	    //Remove all keywords	    
	    sql_query("delete from resource_keyword where resource_type_field='$ref'");
	    hook("after_delete_resource_type_field");

        if($ajax)
            {
            echo json_encode(
                array(
                    'deleted' => $ref
                )
            );
            exit();
            }

        redirect(generateURL($baseurl . "/pages/admin/admin_resource_type_fields.php",$url_params,array("ref"=>"","deleted"=>urlencode($ref))));
	    }
        else
	    {	    
	    // User needs to confirm deletion as data will be lost
	    $error_text=str_replace("%%AFFECTEDRESOURCES%%",$affected_resources_count,$lang["admin_delete_field_confirm"]);
		$error_text.="<br /><a target=\"_blank\" href=\"" . $baseurl  . "/pages/search.php?search=!hasdata" . $ref . "\">" . $lang["show_resources"] . "</a>";
	    
	    $confirm_delete=true;
	    }
	}
	
# Fetch  data
$allfields=get_resource_type_fields();
$resource_types=sql_query("select ref, name from resource_type");
foreach($resource_types as $resource_type)
	{
	$resource_type_array[$resource_type["ref"]]=$resource_type["name"];
	}
$resource_type_array[0]=$lang["resourcetype-global_field"];
$resource_type_array[999]=$lang["resourcetype-archive_only"];
$fielddata=get_resource_type_field($ref);

include "../../include/header.php";
?>
<script>
var fixed_list_fields = [<?php echo implode(",",$FIXED_LIST_FIELD_TYPES) ?>];
var text_fields       = [<?php echo implode(",",$TEXT_FIELD_TYPES) ?>];
var current_type      = <?php echo ('' != $fielddata['type'] ? $fielddata['type'] : 0); ?>;

<?php if (isset($migrate_data))
	{
	?>
	jQuery(document).ready(function()
		{
		window.location.href = '<?php echo $baseurl ?>/pages/tools/migrate_data_to_fixed.php?field=<?php echo $ref ?>';
		});
	<?php
	}
?>
</script>
<div class="BasicsBox">
    
    <p>
	
	<a href="<?php echo $backurl ?>" onClick="return CentralSpaceLoad(this,true);"><?php echo LINK_CARET_BACK ?><?php echo $lang["back"]?></a>
    </p>
    
    <h1><?php echo $lang["admin_resource_type_field"] . ": " . i18n_get_translated($fielddata["title"]);render_help_link('resourceadmin/configure-metadata-field');?></h1>
	

 

<form method="post" class="FormWide" action="<?php echo $baseurl_short?>pages/admin/admin_resource_type_field_edit.php?ref=<?php echo $fielddata["ref"] . "&restypefilter=" . $restypefilter . "&field_order_by=" . $field_order_by . "&field_sort=" . $field_sort ."&find=" . urlencode($find); ?>" onSubmit="return CentralSpacePost(this,true);">
    <?php generateFormToken("admin_resource_type_field_edit"); ?>
<input type="hidden" name="ref" value="<?php echo urlencode($ref) ?>">

<input type="hidden" name="newfield" value="<?php echo ($newfield)?"TRUE":""; ?>">


<?php
if (isset($error_text)) { ?><div class="PageInformal"><?php echo $error_text?></div><?php }
if (isset($saved_text)) { ?><div class="PageInformal"><span class="fa fa-fw fa-check"></span>&nbsp;<?php echo $saved_text?></div> <?php }


if($confirm_delete)
    {
    ?>
    <input name="confirmdelete" id="confirmdelete" type="hidden" value="">
    <div class="textcenter">
	<input name="delete" type="button" value="&nbsp;&nbsp;<?php echo $lang["action-delete"]?>&nbsp;&nbsp;" onClick="jQuery('#delete').val('yes');jQuery('#confirmdelete').val('yes');this.form.submit();" />
	<input type="button" class="button" onClick="CentralSpaceLoad('<?php generateURL($baseurl_short . "/pages/admin/admin_resource_type_field_edit.php",$url_params,array("ref"=>"")); ?>',true);return false;" value="&nbsp;&nbsp;<?php echo $lang["cancel"] ?>&nbsp;&nbsp;" >
    </div>
     <?php	
    }
else
    {
    ?>
    <div class="Question"><label><?php echo $lang["property-field_id"] ?></label>
	<div class="Fixed"><?php echo  $fielddata["ref"] ?></div>
	<div class="clearerleft"> </div>
    </div>
    
    <?php
    
    foreach ($fieldcolumns as $column=>$column_detail)		
	    {
	    if ($column=="partial_index") // Start the hidden advanced section here
			{?>
			<h2 id="showhiddenfields" class="CollapsibleSectionHead collapsed" ><?php echo $lang["admin_advanced_field_properties"] ?></h2>
			<div class="CollapsibleSection" id="admin_hidden_field_properties" >	 
			<?php
			}
	    admin_resource_type_field_option($column,$column_detail[0],$column_detail[1],$column_detail[2],$fielddata[$column],$fielddata["type"]);
	    }
    ?>
    
    </div><!-- End of hidden advanced section -->
    
    
    <div class="QuestionSubmit">
    <label for="buttons"> </label>			
    <input name="save" type="submit" value="&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $lang["save"]?>&nbsp;&nbsp;&nbsp;&nbsp;" />&nbsp;&nbsp;
    <input type="button" class="button" onClick="CentralSpaceLoad('<?php echo $baseurl . "/pages/admin/admin_copy_field.php?ref=" . $ref . "&backurl=" . $url ?>',true);return false;" value="&nbsp;&nbsp;<?php echo $lang["copy-field"] ?>&nbsp;&nbsp;" >
    <input name="migrate_data" id="migrate_data" type="hidden" value="">
    <input name="delete" type="button" value="&nbsp;&nbsp;<?php echo $lang["action-delete"]?>&nbsp;&nbsp;" onClick="if(confirm('<?php echo $lang["confirm-deletion"] ?>')){jQuery('#delete').val('yes');this.form.submit();}else{jQuery('#delete').val('');}" />

    </div>
    <?php
    }?>

<input type="hidden" name="save" id="save" value="yes"/>
<input type="hidden" name="delete" id="delete" value=""/>
</form>


</div><!-- End of Basics Box -->

<script>
   registerCollapsibleSections();
</script>



<?php


include "../../include/footer.php";
