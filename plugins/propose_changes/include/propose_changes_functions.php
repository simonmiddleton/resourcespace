<?php

function save_proposed_changes($ref)
	{
    global $userref, $auto_order_checkbox,$multilingual_text_fields,$languages,$language, $FIXED_LIST_FIELD_TYPES;

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
            ##### END OF NODES #####

            if ($fields[$n]["type"]==4 || $fields[$n]["type"]==6 || $fields[$n]["type"]==10)
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
                                                    } 
                                            else 
                                                    {
                                                            $val.="00";
                                                    }
                                            }
                                    }
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
            $ref,
            $userid
        );
        $changes = sql_query($query);

        return $changes;  
        }
