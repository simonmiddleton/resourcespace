<?php

function save_proposed_changes($ref)
	{
    global $userref, $auto_order_checkbox,$multilingual_text_fields,$languages,$language, $FIXED_LIST_FIELD_TYPES, $DATE_FIELD_TYPES;

    if(!is_numeric($ref))
        {
        return false;
        }
        
    # Loop through the field data and save (if necessary)
	$errors        = array();
	$fields        = get_resource_field_data($ref, false);
	$resource_data = get_resource_data($ref);

    // All the nodes passed for editing. Some of them were already a value
    // of the fields while others have been added/ removed
    $user_set_values = getval('nodes', array());

        for ($n=0;$n<count($fields);$n++)
            {
            $new_nodes = array();

            ##### NODES #####
            if (in_array($fields[$n]['type'], $FIXED_LIST_FIELD_TYPES))
                {
                $fields[$n]['nodes'] = get_nodes($fields[$n]['ref'], null, (FIELD_TYPE_CATEGORY_TREE == $fields[$n]['type'] ? true : false));
                }

            // Fixed list fields use node IDs directly
            if(in_array($fields[$n]['type'], $FIXED_LIST_FIELD_TYPES))
                {
                $ui_selected_node_values = array();

                if(isset($user_set_values[$fields[$n]['ref']])
                    && !is_array($user_set_values[$fields[$n]['ref']])
                    && '' != $user_set_values[$fields[$n]['ref']]
                    && is_numeric($user_set_values[$fields[$n]['ref']]))
                    {
                    $ui_selected_node_values[] = $user_set_values[$fields[$n]['ref']];
                    }
                else if(isset($user_set_values[$fields[$n]['ref']])
                    && is_array($user_set_values[$fields[$n]['ref']]))
                    {
                    $ui_selected_node_values = $user_set_values[$fields[$n]['ref']];
                    }

                foreach($fields[$n]['nodes'] as $node)
                    {
                    if(in_array($node['ref'], $ui_selected_node_values))
                        {
                        $new_nodes[] = $node['ref'];
                        }
                    }
                }				
		else
				{
				if($fields[$n]['type']==FIELD_TYPE_DATE_RANGE)
					{
					# date range type
					# each value will be a node so we end up with a pair of nodes to represent the start and end dates

					$newval="";
					
					if(($date_edtf=getval("field_" . $fields[$n]["ref"] . "_edtf", false))!==false)
						{
						// We have been passed the range in EDTF format, check it is in the correct format
						$rangeregex="/^(\d{4})(-\d{2})?(-\d{2})?\/(\d{4})(-\d{2})?(-\d{2})?/";
						if(!preg_match($rangeregex,$date_edtf,$matches))
							{
							$errors[$fields[$n]["ref"]]=$lang["information-regexp_fail"] . " : " . $val;
							continue;
							}
						$rangedates = explode("/",$date_edtf);
						$rangestart=str_pad($rangedates[0],  10, "-00");
						$rangeendparts=explode("-",$rangedates[1]);
                        $rangeendyear=$rangeendparts[0];
                        $rangeendmonth=isset($rangeendparts[1])?$rangeendparts[1]:12;
                        $rangeendday=isset($rangeendparts[2])?$rangeendparts[2]:cal_days_in_month(CAL_GREGORIAN, $rangeendmonth, $rangeendyear);
						$rangeend=$rangeendyear . "-" . $rangeendmonth . "-" . $rangeendday;
                        
						$val = $rangestart . ", " . $rangeend;
						}
					else
						{
						// Range has been passed via normal inputs, construct the value from the date/time dropdowns
						$date_parts=array("start","end");
						
						foreach($date_parts as $date_part)
							{	
							$val = getval("field_" . $fields[$n]["ref"] . "_" . $date_part . "_year",false);
							if ($val !== false && intval($val)<=0) 
								{
								$val="";
								}
							elseif (($field=getval("field_" . $fields[$n]["ref"] . "_" . $date_part . "_month",""))!="") 
								{
								$val.="-" . $field;
								if (($field=getval("field_" . $fields[$n]["ref"] . "_" . $date_part . "_day",""))!="") 
									{
									$val.="-" . $field;
									}
								 else 
									{
									$val.="-00";
									}
								}
							else 
								{
								$val.="-00-00";
								}
							$newval.= ($newval!=""?", ":"") . $val;
							}
						}
						$val=$newval;
                    }
                else if ($GLOBALS['use_native_input_for_date_field'] && $fields[$n]['type'] === FIELD_TYPE_DATE)
                    {
                    $val = getval("field_{$fields[$n]['ref']}", false);
                    if($val !== false && !validateDatetime($val, 'Y-m-d'))
                        {
                        $errors[$fields[$n]["ref"]] = str_replace(
                            [' %row%', '%date%', '%field%'],
                            ['', $val, $fields[$n]['name']],
                            $GLOBALS['lang']['invalid_date_error']
                        );
                        continue;
                        }
                    }
				elseif(in_array($fields[$n]['type'], $DATE_FIELD_TYPES))
					{
                    $include_time = $fields[$n]['type'] === FIELD_TYPE_DATE_AND_OPTIONAL_TIME;
                    # date type, construct the value from the date/time dropdowns
                    $val = getval("field_" . $fields[$n]["ref"] . "-y",false);
                    if($val !== false)
                        {
                        $val = sprintf("%04d",$val);
                        if (intval($val)<=0) 
                            {
                            $val="";
                            }
                        elseif (($field=getval("field_" . $fields[$n]["ref"] . "-m",""))!="") 
                            {
                            $val.="-" . $field;
                            if (($field=getval("field_" . $fields[$n]["ref"] . "-d",""))!="") 
                                {
                                $val.="-" . $field;
                                if (($field=getval("field_" . $fields[$n]["ref"] . "-h",""))!="")
                                    {
                                    $val.=" " . $field . ":";
                                    if (($field=getval("field_" . $fields[$n]["ref"] . "-i",""))!="") 
                                        {
                                        $val.=$field;
                                        if (($field=getval("field_" . $fields[$n]["ref"] . "-s",""))!="") 
                                            {
                                            $val.=$field;
                                            } 
                                        elseif($include_time)
                                            {
                                            $val.=":00";
                                            }
                                        } 
                                    elseif($include_time)
                                        {
                                        $val.="00:00";
                                        }
                                    }
                                elseif($include_time)
                                    {
                                    $val.=" 00:00:00";
                                    }
                                }
                            else 
                                {
                                $val.="-00" . ($include_time?" 00:00:00":"");
                                }
                            }
                        else 
                            {
                            $val.="-00-00" . ($include_time?" 00:00:00":"");
                            }
                        }
                    }
				elseif ($multilingual_text_fields && ($fields[$n]["type"]==0 || $fields[$n]["type"]==1 || $fields[$n]["type"]==5))
					{
					# Construct a multilingual string from the submitted translations
					$val=getval("field_" . $fields[$n]["ref"], false);
                    if($val !== false)
                        {
                        $val="~" . $language . ":" . $val;
                        reset ($languages);
                        foreach ($languages as $langkey => $langname)
                            {
                            if ($language!=$langkey)
                                {
                                $val.="~" . $langkey . ":" . getval("multilingual_" . $n . "_" . $langkey,"");
                                }
                            }
                        }
					}
				else
					{
					# Set the value exactly as sent.
					$val=getval("field_" . $fields[$n]["ref"],false);
					} 
				# Check for regular expression match
				if (strlen(trim((string)$fields[$n]["regexp_filter"]))>=1 && strlen($val)>0)
					{
                    global $regexp_slash_replace;
					if(preg_match("#^" . str_replace($regexp_slash_replace, '\\', $fields[$n]["regexp_filter"]) . "$#",$val,$matches)<=0)
						{
						global $lang;
						debug($lang["information-regexp_fail"] . ": -" . "reg exp: " . str_replace($regexp_slash_replace, '\\', $fields[$n]["regexp_filter"]) . ". Value passed: " . $val);
						if (getval("autosave","")!="")
							{
							exit();
							}
						$errors[$fields[$n]["ref"]]=$lang["information-regexp_fail"] . " : " . $val;
						continue;
						}
					}
				$modified_val=hook("modifiedsavedfieldvalue",'',array($fields,$n,$val));
				if(!empty($modified_val)){$val=$modified_val;}
				
				$error=hook("additionalvalcheck", "all", array($fields, $fields[$n]));
				if ($error) 
					{
					global $lang;
					if (getval("autosave","")!="")
						{
						exit($error);
						}
					$errors[$fields[$n]["ref"]]=$error;
					continue;
					}
				}
					
				# Check for regular expression match
				if (strlen(trim((string)$fields[$n]["regexp_filter"]))>=1 && strlen($val)>0)
						{
                        global $regexp_slash_replace;
						if(preg_match("#^" . str_replace($regexp_slash_replace, '\\', $fields[$n]["regexp_filter"]) . "$#",$val,$matches)<=0)
								{
								global $lang;
								debug($lang["information-regexp_fail"] . ": -" . "reg exp: " . str_replace($regexp_slash_replace, '\\', $fields[$n]["regexp_filter"]) . ". Value passed: " . $val);
								if (getval("autosave","")!="")
										{
										exit();
										}
								$errors[$fields[$n]["ref"]]=$lang["information-regexp_fail"] . " : " . $val;
								continue;
								}
						}
            $error=hook("additionalvalcheck", "all", array($fields, $fields[$n]));
            if ($error) 
                {
                global $lang;               
                $errors[$fields[$n]["ref"]]=$error;
                continue;
                }

            $field_value = $fields[$n]['value'];
            if(in_array($fields[$n]['type'], $FIXED_LIST_FIELD_TYPES))
                {
                $field_value    = '';
                $val            = '';
                $resource_nodes = array();

                foreach(get_resource_nodes($ref, $fields[$n]['ref'], true) as $resource_node)
                    {
                    $resource_nodes[] = $resource_node['ref'];
                    }

                if(0 < count($resource_nodes))
                    {
                    $field_value = implode(', ', $resource_nodes);
                    }

                if(0 < count($new_nodes))
                    {
                    natsort($new_nodes);
                    $val = implode(', ', $new_nodes);
                    }
                }

            if ($val !== false && str_replace("\r\n", "\n", $field_value??"") !== str_replace("\r\n", "\n", unescape($val)))
                    {
                    if(in_array($fields[$n]['type'], $DATE_FIELD_TYPES))
                        {
                        # Check that date hasn't only changed by adding seconds value
                        if (trim((string) $field_value).":00" == trim($val))
                            {
                            continue;    
                            }
                        }
                    # This value is different from the value we have on record. 
                    # Add this to the proposed changes table for the user
                    $parameters=array("i",$ref, "i",$userref, "i",$fields[$n]['ref'], "s",$val);
                    ps_query("INSERT INTO propose_changes_data(resource, user, resource_type_field, value, date) 
                                VALUES( ?, ?, ?, ?, now() )", $parameters);
                    }            
            
            }
                
        return true;
        }
        
function get_proposed_changes($ref, $userid)
    {
    //Get all the changes proposed by a user
    $query = "SELECT d.value,
                     d.resource_type_field,
                     d.date,
                     f.*,
                     f.required AS frequired,
                     f.ref AS fref
                  FROM resource_type_field AS f
             LEFT JOIN (
                            SELECT *
                              FROM propose_changes_data
                             WHERE resource = ?
                               AND user = ?
                       ) AS d ON d.resource_type_field = f.ref AND d.resource = ?
             GROUP BY f.ref
             ORDER BY f.global DESC, f.order_by, f.ref;";
    $parameters=array("i",$ref, "i",$userid, "i",$ref);
    $changes = ps_query($query, $parameters);

    return $changes;
    }
        
function delete_proposed_changes($ref, $userid="")
	{
    $query = "DELETE FROM propose_changes_data WHERE resource = ?";
    $parameters=array("i",$ref);
    if ($userid!="")
        {
        $query.=" AND user=?";
        $parameters=array_merge($parameters,array("i",$userid));
        }
    ps_query($query, $parameters);
    }

# Allows language alternatives to be entered for free text metadata fields.
function propose_changes_display_multilingual_text_field($n, $field, $translations)
    {
    global $language, $languages, $lang;
    ?>
    <p><a href="#" class="OptionToggle" onClick="l=document.getElementById('LanguageEntry_<?php echo $n?>');if (l.style.display=='block') {l.style.display='none';this.innerHTML='<?php echo escape($lang["showtranslations"])?>';} else {l.style.display='block';this.innerHTML='<?php echo escape($lang["hidetranslations"])?>';} return false;"><?php echo htmlspecialchars($lang["showtranslations"]) ?></a></p>
    <table class="OptionTable" style="display:none;" id="LanguageEntry_<?php echo $n?>">
    <?php
    reset($languages);
    foreach ($languages as $langkey => $langname)
        {
        if ($language!=$langkey)
            {
            if (array_key_exists($langkey,$translations)) {$transval=$translations[$langkey];} else {$transval="";}
            ?>
            <tr>
            <td nowrap valign="top"><?php echo htmlspecialchars($langname)?>&nbsp;&nbsp;</td>

            <?php
            if ($field["type"]==0)
                {
                ?>
                <td><input type="text" class="stdwidth" name="multilingual_<?php echo $n?>_<?php echo $langkey?>" value="<?php echo htmlspecialchars($transval)?>"></td>
                <?php
                }
            else
                {
                ?>
                <td><textarea rows=6 cols=50 name="multilingual_<?php echo $n?>_<?php echo $langkey?>"><?php echo htmlspecialchars($transval)?></textarea></td>
                <?php
                }
            ?>
            </tr>
            <?php
            }
        }
    ?></table><?php
    }

function propose_changes_display_field($n, $field)
    {
    global $ref, $original_fields, $multilingual_text_fields,
    $is_template, $language, $lang,  $errors, $proposed_changes, $editaccess,
    $FIXED_LIST_FIELD_TYPES,$range_separator, $edit_autosave;

    # Certain edit_fields/x.php functions check for bulk edit which must be defined as false prior to rendering propose change field  
    $multiple=false;

    $edit_autosave=false;
    $name="field_" . $field["ref"];
    $value=$field["value"];
    $value=trim($value??"");
    $proposed_value="";            
    # is there a proposed value set for this field?
    foreach($proposed_changes as $proposed_change)
        {
        if($proposed_change['resource_type_field'] == $field['ref'])
            {
            $proposed_value = $proposed_change['value'];
            }
        }

    // Don't show this if user is an admin viewing proposed changes, needs to be on form so that form is still submitted with all data
    if ($editaccess && $proposed_value=="")
        {
        ?>
        <div style="display:none" >
        <?php
        }

    if ($multilingual_text_fields)
        {
        # Multilingual text fields - find all translations and display the translation for the current language.
        $translations=i18n_get_translations($value);
        if (array_key_exists($language,$translations)) {$value=$translations[$language];} else {$value="";}
        }

    ?>
    <div class="Question ProposeChangesQuestion" id="question_<?php echo $n?>">
    <div class="Label ProposeChangesLabel" ><?php echo htmlspecialchars($field["title"])?></div>

    <?php 
    # Define some Javascript for help actions (applies to all fields)
    $help_js="onBlur=\"HideHelp(" . $field["ref"] . ");return false;\" onFocus=\"ShowHelp(" . $field["ref"] . ");return false;\"";

    #hook to modify field type in special case. Returning zero (to get a standard text box) doesn't work, so return 1 for type 0, 2 for type 1, etc.
    $modified_field_type="";
    $modified_field_type=(hook("modifyfieldtype"));
    if ($modified_field_type){$field["type"]=$modified_field_type-1;}

    hook("addfieldextras");

    // ------------------------------
    // Show existing value so can edit
    $value=preg_replace("/^,/","",$field["value"]??"");
    $realvalue = $value; // Store this in case it gets changed by view processing
    if ($value!="")
            {
            # Draw this field normally.			
            ?><div class="propose_changes_current ProposeChangesCurrent"><?php display_field_data($field,true); ?></div><?php
            }                        
        else
            {
            ?><div class="propose_changes_current ProposeChangesCurrent"><?php echo htmlspecialchars($lang["propose_changes_novalue"])  ?></div>    
            <?php
            }
        if(!$editaccess && $proposed_value=="")
            {
            ?>
            <div class="propose_change_button" id="propose_change_button_<?php echo $field["ref"] ?>">
            <input type="submit" value="<?php echo escape($lang["propose_changes_buttontext"]) ?>" onClick="ShowProposeChanges(<?php echo $field["ref"] ?>);return false;" />
            </div>
            <?php
            }?>

    <div class="proposed_change proposed_change_value proposed ProposeChangesProposed" <?php if($proposed_value==""){echo "style=\"display:none;\""; } ?> id="proposed_change_<?php echo $field["ref"] ?>">
    <input type="hidden" id="propose_change_<?php echo $field["ref"] ?>" name="propose_change_<?php echo $field["ref"] ?>" value="true" <?php if($proposed_value==""){echo "disabled=\"disabled\""; } ?> />
    <?php
    # ----------------------------  Show field -----------------------------------
    // Checkif we have a proposed value for this field
    if('' != $proposed_value)
        {
        $value = $proposed_value;
        }
    else
        {
        $value = $realvalue;
        }

    $type = $field['type'];

    if('' == $type)
        {
        $type = 0;
        }

    if (!hook('replacefield', '', array($field['type'], $field['ref'], $n)))
        {
        global $auto_order_checkbox, $auto_order_checkbox_case_insensitive, $FIXED_LIST_FIELD_TYPES, $is_search;

        if(in_array($field['type'], $FIXED_LIST_FIELD_TYPES))
            {
            $name = "nodes[{$field['ref']}]";

            // Sometimes we need to pass multiple options
            if(in_array($field['type'], array(FIELD_TYPE_CHECK_BOX_LIST, FIELD_TYPE_CATEGORY_TREE)))
                {
                $name = "nodes[{$field['ref']}][]";
                }
            else if(FIELD_TYPE_DYNAMIC_KEYWORDS_LIST == $field['type'])
                {
                $name = "field_{$field['ref']}";
                }

            $selected_nodes = (trim($proposed_value) != "" ? explode(', ', $proposed_value) : array());
            if(!$editaccess && '' == $proposed_value)
                {
                $selected_nodes = get_resource_nodes($ref, $field['resource_type_field']);
                }
            }
        else if ($field["type"]==FIELD_TYPE_DATE_RANGE)
            {
            $rangedates = explode(",",$value);
            natsort($rangedates);
            $value=implode(",",$rangedates);
            }

        $is_search = false;

        include dirname(__FILE__) . "/../../../pages/edit_fields/{$type}.php";
        }
    # ----------------------------------------------------------------------------
    ?>
        </div><!-- close proposed_change_<?php echo $field["ref"] ?> -->
        <?php
        if($editaccess)
            {
            ?>     
            <div class="ProposeChangesAccept ProposeChangesAcceptDeleteColumn">
            <table>
            <tr>
            <td><input class="ProposeChangesAcceptCheckbox" type="checkbox" id="accept_change_<?php echo $field["ref"] ?>" name="accept_change_<?php echo $field["ref"] ?>" onchange="UpdateProposals(this,<?php echo $field["ref"] ?>);" checked ></input><?php echo htmlspecialchars($lang["propose_changes_accept_change"])  ?></td>
            <td>
            <input class="ProposeChangesDeleteCheckbox" type="checkbox" id="delete_change_<?php echo $field["ref"] ?>" name="delete_change_<?php echo $field["ref"] ?>" onchange="DeleteProposal(this,<?php echo $field["ref"] ?>);" ></input><?php echo htmlspecialchars($lang["action-delete"])  ?></td>
            </tr>
            </table>
            </div>
            <?php
            }

    if (trim($field["help_text"]!=""))
        {
        # Show inline help for this field.
        # For certain field types that have no obvious focus, the help always appears.
        ?>
        <div class="FormHelp" style="<?php if (!in_array($field["type"],array(2,4,6,7,10))) { ?>display:none;<?php } else { ?>clear:left;<?php } ?>" id="help_<?php echo $field["ref"]?>"><div class="FormHelpInner"><?php echo nl2br(trim(htmlspecialchars(i18n_get_translated($field["help_text"]))))?></div></div>
<?php
        }

    # If enabled, include code to produce extra fields to allow multilingual free text to be entered.
    if ($multilingual_text_fields && ($field["type"]==0 || $field["type"]==1 || $field["type"]==5))
        {
        propose_changes_display_multilingual_text_field($n, $field, $translations);
        }
    ?>
    <div class="clearerleft"> </div>
    </div><!-- end of question_<?php echo $n?> div -->
    <?php
    // Don't show this if user is an admin viewing proposed changes
    if ($editaccess && $proposed_value=="")
        {
        ?>
        </div><!-- End of hidden field -->
        <?php
        }
    }