<?php
$on_upload = ($pagename ==  "upload_batch");
if(!hook("replaceuploadoptions")):	
if ($on_upload || (isset($ref) && $ref<0))
	{
	if($show_status_and_access_on_upload && !$on_upload){?></div><!-- end of previous collapsing section --> <?php }
	if($tabs_on_edit && !$on_upload)
		{
		?><h1><?php  echo htmlspecialchars($lang["upload-options"])  ?></h1>
		<div id="UploadOptionsSection">
		<?php
		}
	elseif(!$on_upload)
		{		
		?><h2 class="CollapsibleSectionHead"><?php  echo htmlspecialchars($lang["upload-options"])  ?></h2>
		<div class="CollapsibleSection" id="UploadOptionsSection">
		<?php
		}

    if($on_upload && $upload_then_edit && $resource_type_force_selection)
        {
        ?>
        <div id="question_resourcetype" class="Question">
            <label for="resourcetype"><?php echo "{$lang["resourcetype"]}" . " <sup>*</sup>"; ?></label>
            <select id="resourcetype"
                    class="stdwidth"
                    name="resource_type"
                    >
                <option value='' <?php ($resource_type == "") ? "selected" : ""?>><?php  echo htmlspecialchars($lang["select"]) ; ?></option>
            <?php
            $types                = get_resource_types("",false,false,true);
            $shown_resource_types = array();

            for($n = 0; $n < count($types); $n++)
                {
                // skip showing a resource type that we do not to have permission to change to (unless it is currently set to that). Applies to upload only
                if(checkperm("XU{$types[$n]['ref']}") 
                    || 
                    in_array($types[$n]['ref'], $hide_resource_types)
                    || (checkperm("XE") && !checkperm("XE-" . $types[$n]['ref']))
                    )
                    {
                    continue;
                    }

                $shown_resource_types[] = $types[$n]['ref'];

                $selected = '';
                if(isset($resource_type) && $resource_type == $types[$n]['ref'])
                    {
                    $selected = 'selected';
                    }
                ?>
                <option value="<?php echo $types[$n]['ref']; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($types[$n]["name"]); ?></option>
                <?php
                }
            ?>
            </select>
            <div class="clearerleft"></div>
        </div>
        <?php
        }

	if($on_upload || !$embedded_data_user_select)
	    {
	    if ($metadata_read)
            {
            // Show option to prevent extraction of exif data
            ?>
            <div class="Question" id="question_noexif">
            <label for="no_exif"><?php  echo htmlspecialchars($lang["no_exif"]) ?></label><input type=checkbox id="no_exif" name="no_exif" value="yes" <?php if (getval("no_exif",($metadata_read_default)?"":"no")!="") { ?>checked<?php } ?>>
            <div class="clearerleft"> </div>
            </div>
<?php
            }
        elseif ($no_metadata_read_default) 
		    {
            // Confusing, set the value to null so that metadata will be extracted
            ?>
            <input type=hidden id="no_exif" name="no_exif" value="">
<?php
            } 
	     else
            {
            // Set the value to no so that metadata will be extracted
            ?>
            <input type=hidden id="no_exif" name="no_exif" value="no">
<?php
            } 
	    }
	if($enable_related_resources && $relate_on_upload && ($on_upload || ($ref<0 && !$multiple)) && !$external_upload) # When uploading
        {
        ?>
        <div class="Question" id="question_related">
        <label for="relateonupload"><?php  echo htmlspecialchars($lang["relatedresources_onupload"]) ?></label>
        <input name="relateonupload" id="relateonupload" type="checkbox" value="1" style="margin-top:7px;" <?php if($relate_on_upload_default){echo " checked ";} ?>/> 
        <div class="clearerleft"> </div>
        </div><?php
        }

	if (getval("single","")=="" || $on_upload)
        {
        $non_col_options=0;
        # Add Resource Batch: specify default content - also ask which collection to add the resource to.
        if ($enable_add_collection_on_upload && !(isset($external_upload) && $external_upload))
            {
            $collection_add=getval("collection_add","");
            ?>
            <div class="Question <?php if(!$on_upload && isset($save_errors) && is_array($save_errors) && array_key_exists('collectionname',$save_errors)) { echo " FieldSaveError"; } ?>" id="question_collectionadd">
                <label for="collection_add"><?php  echo htmlspecialchars($lang["addtocollection"]) ?></label>
                <select name="collection_add" id="collection_add" class="stdwidth" >

                <?php if (can_create_collections() && $upload_add_to_new_collection_opt)
                    {
                    ?><option value="new" <?php if ($upload_add_to_new_collection){ ?>selected <?php }?>>(<?php echo htmlspecialchars($lang["createnewcollection"]) ?>)</option>
                    <?php
                    }
                if ($upload_do_not_add_to_new_collection_opt && !hook("remove_do_not_add_to_collection"))
                    {
                    ?><option value="false" <?php if (!$upload_add_to_new_collection || $do_not_add_to_new_collection_default || $collection_add=='false'){ ?>selected <?php }?>><?php echo htmlspecialchars($lang["batchdonotaddcollection"]) ?></option>
                    <?php
                    }                

                //If the user is attached to a collection that is not allowed to add resources to,
                //then we hide this collection from the drop down menu of the upload page
                $temp_list=get_user_collections($userref);
                $list = array();
                $hide_non_editable = array();

                for ($n=0;$n<count($temp_list);$n++)
                    {
                    if ( $temp_list[$n]['user']!=$userref && $temp_list[$n]['allow_changes'] == 0)
                        {
                        continue;
                        }
                    else
                        {
                        array_push($list,$temp_list[$n]);
                        }
                    }

                $currentfound=false;

                    // make sure it's possible to set the collection with collection_add (compact style "upload to this collection"
                    if (is_numeric($collection_add) && getval("resetform","")=="" && (!isset($save_errors) || !$save_errors))
                        {
                        # Switch to the selected collection (existing or newly created) and refresh the frame.
                        set_user_collection($userref,$collection_add);
                        refresh_collection_frame($collection_add);
                        }

                for ($n=0;$n<count($list);$n++)
                    {
                    #remove smart collections as they cannot be uploaded to.
                    if (!isset($list[$n]['savedsearch'])||(isset($list[$n]['savedsearch'])&&$list[$n]['savedsearch']==null))
                        {
                        #show only active collections if a start date is set for $active_collections 
                        if (strtotime($list[$n]['created']) > ((isset($active_collections))?strtotime($active_collections):1) || ($list[$n]['name']=="Default Collection" && $list[$n]['user']==$userref))
                            { if ($list[$n]["ref"]==$usercollection) {$currentfound=true;} 
                            ?>
                            <option value="<?php echo $list[$n]["ref"]?>" <?php if ($list[$n]['ref']==$collection_add) {?> 	selected<?php } ?>><?php echo i18n_get_collection_name($list[$n]) ?></option>
                            <?php
                            }
                        }
                    }

                if (!$currentfound)
                    {
                    # The user's current collection has not been found in their list of collections (perhaps they have selected a theme to edit). Display this as a separate item.
                    $cc=get_collection($usercollection);

                    //Check if the current collection is editable as well by checking $cc['allow_changes']
                    if(false !== $cc && collection_writeable($usercollection))
                        {
                        $currentfound = true;
                        ?>
                        <option value="<?php echo htmlspecialchars($usercollection) ?>" <?php if (is_numeric($collection_add) && $usercollection==$collection_add){?>selected <?php } ?>><?php echo i18n_get_collection_name($cc)?></option>
                        <?php
                        }
                    }
                ?>
                </select>
                <div class="clearerleft"> </div>
                <div name="collectioninfo" id="collectioninfo" style="display:none;">
                    <div name="collectionname" id="collectionname" <?php if ($upload_add_to_new_collection_opt){ ?> style="display:block;"<?php } else { ?> style="display:none;"<?php } ?>>
                    <label for="entercolname"><?php  echo htmlspecialchars($lang["collectionname"]) ?><?php if ($upload_collection_name_required){?><sup>*</sup><?php } ?></label>
                    <input type=text id="entercolname" name="entercolname" class="stdwidth" value='<?php echo htmlentities(stripslashes(getval("entercolname","")), ENT_QUOTES);?>'> 
                    </div>
                </div> <!-- end collectioninfo -->
            </div> <!-- end question_collectionadd -->

            <script>
            jQuery(document).ready(function() {
            jQuery('#collection_add').change(function (){
                if(jQuery('#collection_add').val()=='new'){
                jQuery('#collectioninfo').fadeIn();
                } 
                else {
                jQuery('#collectioninfo').fadeOut();
                }
            });
            jQuery('#collection_add').change();
            });
            </script>
            <?php
            } // end enable_add_collection_on_upload
		}
	hook("extrauploadoptions");
	}

if($on_upload)
    {
    ?>
    </div> <!-- End of Upload options -->
    <div class="BasicsBox">
    <script>
    // Add code to change URL if options change

    jQuery(document).ready(function() {        
        jQuery('#relateonupload').on('change', function ()
            {
            if(jQuery(this).is(':checked'))
                {
                relate_on_upload = true;
                }
            else
                {
                relate_on_upload = false;
                }
            });               
        });
    </script>
    <?php
    }
else if ($edit_upload_options_at_top)
    {
    ?>
    <!-- End of Upload options -->
    <?php
    }
endif; # hook replaceuploadoptions
