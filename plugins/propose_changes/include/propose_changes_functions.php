<?php

function save_proposed_changes($ref)
	{
    global $userref, $auto_order_checkbox,$multilingual_text_fields,$languages,$language, $FIXED_LIST_FIELD_TYPES, $DATE_FIELD_TYPES, $range_separator;

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
            $fields[$n]['nodes'] = get_nodes($fields[$n]['ref'], null, (FIELD_TYPE_CATEGORY_TREE == $fields[$n]['type'] ? true : false));

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
					
					if(($date_edtf=getvalescaped("field_" . $fields[$n]["ref"] . "_edtf",""))!=="")
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
                        
						$val = $rangestart . "," . $rangeend;
						}
					else
						{
						// Range has been passed via normal inputs, construct the value from the date/time dropdowns
						$date_parts=array("start","end");
						
						foreach($date_parts as $date_part)
							{	
							$val = getvalescaped("field_" . $fields[$n]["ref"] . "_" . $date_part . "_year","");
							if (intval($val)<=0) 
								{
								$val="";
								}
							elseif (($field=getvalescaped("field_" . $fields[$n]["ref"] . "_" . $date_part . "_month",""))!="") 
								{
								$val.="-" . $field;
								if (($field=getvalescaped("field_" . $fields[$n]["ref"] . "_" . $date_part . "_day",""))!="") 
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
							$newval.= ($newval!=""?",":"") . $val;
							}
						}
						$val=$newval;
                    }
				elseif(in_array($fields[$n]['type'], $DATE_FIELD_TYPES))
					{
                    # date type, construct the value from the date/time dropdowns
                    $val=sprintf("%04d", getvalescaped("field_" . $fields[$n]["ref"] . "-y",""));
                    if ((int)$val<=0) 
                        {
                        $val="";
                        }
                    elseif (($field=getvalescaped("field_" . $fields[$n]["ref"] . "-m",""))!="") 
                        {
                        $val.="-" . $field;
                        if (($field=getvalescaped("field_" . $fields[$n]["ref"] . "-d",""))!="") 
                            {
                            $val.="-" . $field;
                            if (($field=getval("field_" . $fields[$n]["ref"] . "-h",""))!="")
                                {
                                $val.=" " . $field . ":";
                                if (($field=getvalescaped("field_" . $fields[$n]["ref"] . "-i",""))!="") 
                                    {
                                    $val.=$field;
									if (($field=getvalescaped("field_" . $fields[$n]["ref"] . "-s",""))!="") 
										{
										$val.=$field;
										} 
								     else 
										{
										$val.=":00";
										}
                                    } 
                                else 
                                    {
                                    $val.="00:00";
                                    }
                                }
                            else 
                                {
                                $val.=" 00:00:00";
                                }
                            }
                         else 
                            {
                            $val.="-00 00:00:00";
                            }
                        }
                    else 
                        {
                        $val.="-00-00 00:00:00";
                        }
                    }
				elseif ($multilingual_text_fields && ($fields[$n]["type"]==0 || $fields[$n]["type"]==1 || $fields[$n]["type"]==5))
					{
					# Construct a multilingual string from the submitted translations
					$val=getvalescaped("field_" . $fields[$n]["ref"],"");
					$val="~" . $language . ":" . $val;
					reset ($languages);
					foreach ($languages as $langkey => $langname)
						{
						if ($language!=$langkey)
							{
							$val.="~" . $langkey . ":" . getvalescaped("multilingual_" . $n . "_" . $langkey,"");
							}
						}
					}
				else
					{
					# Set the value exactly as sent.
					$val=getvalescaped("field_" . $fields[$n]["ref"],"");
					} 
				# Check for regular expression match
				if (trim(strlen($fields[$n]["regexp_filter"]))>=1 && strlen($val)>0)
					{
					if(preg_match("#^" . $fields[$n]["regexp_filter"] . "$#",$val,$matches)<=0)
						{
						global $lang;
						debug($lang["information-regexp_fail"] . ": -" . "reg exp: " . $fields[$n]["regexp_filter"] . ". Value passed: " . $val);
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
				if (trim(strlen($fields[$n]["regexp_filter"]))>=1 && strlen($val)>0)
						{
						if(preg_match("#^" . $fields[$n]["regexp_filter"] . "$#",$val,$matches)<=0)
								{
								global $lang;
								debug($lang["information-regexp_fail"] . ": -" . "reg exp: " . $fields[$n]["regexp_filter"] . ". Value passed: " . $val);
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
                    $val = implode(', ', $new_nodes);
                    }
                }

            if (str_replace("\r\n", "\n", $field_value) !== str_replace("\r\n", "\n", unescape($val)))
                    {
                    if(in_array($fields[$n]['type'], $DATE_FIELD_TYPES))
                        {
                        # Check that date hasn't only changed by adding seconds value
                        if (trim($field_value).":00" == trim($val))
                            {
                            continue;    
                            }
                        }
                    # This value is different from the value we have on record. 
                    # Add this to the proposed changes table for the user
                    sql_query("INSERT INTO propose_changes_data(resource, user, resource_type_field, value, date) VALUES('{$ref}','{$userref}', '{$fields[$n]['ref']}', '" . escape_check($val) . "',now())");
                    }            
            
            }
                
        return true;
        }
        
function get_proposed_changes($ref, $userid)
    {
    //Get all the changes proposed by a user
    $query = sprintf('
                SELECT d.value,
                       d.resource_type_field,
                       d.date,
                       f.*,
                       f.required AS frequired,
                       f.ref AS fref
                  FROM resource_type_field AS f
             LEFT JOIN (
                            SELECT *
                              FROM propose_changes_data
                             WHERE resource = "%1$s"
                               AND user = "%2$s"
                       ) AS d ON d.resource_type_field = f.ref AND d.resource = "%1$s"
             GROUP BY f.ref
             ORDER BY f.resource_type, f.order_by, f.ref;
        ',
        escape_check($ref),
        escape_check($userid)
    );
    $changes = sql_query($query);

    return $changes;
    }
        
function delete_proposed_changes($ref, $userid="")
	{
    sql_query("DELETE FROM propose_changes_data WHERE resource = '" . escape_check($ref)  . "'" . ($userid!="" ? "AND user='" . escape_check($userid) . "'":""));
    }
