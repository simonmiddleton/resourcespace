<?php
include_once __DIR__ . '/../include/openai_gpt_functions.php';

global $valid_ai_field_types;


/**
 * Add to array of field column data on metadata field editing page
 *
 * @param array     $fieldcolumns   Existing array of columns
 * 
 * @return array    Updated array of columns
 * 
 */
function HookOpenai_gptAllModifyresourcetypefieldcolumns($fieldcolumns)
    {
    global $lang, $valid_ai_field_types, $ref;
    
    $fielddata=get_resource_type_field($ref);
    if(in_array($fielddata["type"],$valid_ai_field_types))
        {
        $addcolumns = [
            'openai_gpt_prompt'        => array($lang['property-openai_gpt_prompt'],'',2,0),
            'openai_gpt_input_field'   => array($lang['property-openai_gpt_input_field'],'',0,0),
            ];    
        return array_merge($fieldcolumns,$addcolumns);
        }
    return false;
    }

/**
 * Alter rendering of the new columns on the metadata field editing page
 *
 * @param int           $ref            Ref of the metadata field being edited
 * @param string        $column         Name of table column for which input is being rendered
 * @param array         $column_detail  Array of metadata field rendering data from the edit page
 * @param array         $fielddata      Array of metadata field information from get_resource_type_field()
 * 
 * @return bool         Is standard display rendering being overridden?
 * 
 */
function HookOpenai_gptAdmin_resource_type_field_editAdmin_field_replace_question($ref,$column,$column_detail,$fielddata)
    {
    global $lang;
    if(!in_array($column,["openai_gpt_input_field","openai_gpt_prompt"]))
        {
        return false;
        }
    
    $currentvalue = $fielddata[$column];
    if($column=="openai_gpt_input_field")
        {
        $fields = get_resource_type_fields();
        ?>
        <div class="Question" >
		    <label><?php echo htmlspecialchars((string) $column_detail[0]); ?></label>
            <select id="field_edit_<?php echo escape_quoted_data((string) $column); ?>" name="<?php echo escape_quoted_data((string) $column); ?>" class="stdwidth">
            <option value="" <?php if ($currentvalue == "") { echo " selected"; } ?>><?php echo $lang["select"]; ?></option>
            <?php
            foreach($fields as $field)
                {
                if($field["ref"]!=$ref) // Don't show itself as an option
                    {?>
                    <option value="<?php echo (int)$field["ref"] ?>"<?php if ($currentvalue == $field["ref"]) { echo " selected"; } ?>><?php echo escape_quoted_data(i18n_get_translated($field["title"]))  . "&nbsp;(" . (($field["name"]=="")?"":htmlspecialchars((string) $field["name"]))  . ")" ?></option>
                    <?php
                    }
                }
            ?>				
            </select>
        </div>
        <?php
        return true;
        }
    elseif($column=="openai_gpt_prompt")
        {
        ?>
        <div class="Question" >
		    <label><?php echo htmlspecialchars((string) $column_detail[0]); ?></label>
            <textarea class="stdwidth" rows="3" id="field_edit_<?php echo htmlspecialchars((string) $column_detail[0]); ?>" name="<?php echo htmlspecialchars((string) $column); ?>"><?php echo htmlspecialchars((string) $currentvalue)?></textarea>
        </div>		
        <?php
        return true;
        }
    return false;
    }

/**
 * Hook into update_field() to process value changes
 *
 * @param int       $resource       Resource ID
 * @param int       $field          Metadata field ref
 * @param string    $value          New field value (comma separated for nodes)
 * @param string    $existing       Existing field value
 * @param array     $fieldinfo      Array of metadata field information from get_resource_type_field()
 * @param array     $newnodes       Array of new nodes that have been set
 * @param array     $newvalues      Array of new text values that have been set
 * 
 * @return bool
 * 
 */
function HookOpenai_gptAllUpdate_field($resource, $field, $value, $existing, $fieldinfo,$newnodes,$newvalues)
    {
    global $valid_ai_field_types;
    // Is this field referenced by other fields?
    $targetfields = openai_gpt_get_dependent_fields($field);
    foreach($targetfields as $targetfield)
        {
        // Create array of new string values that will be passed to the API
        $source_values = [];
        if(count($newvalues) > 0)
            {
            $source_values = $newvalues;
            }
        elseif(count($newnodes) > 0)
            {
            get_nodes_by_refs($newnodes);
            $source_values = array_column($newnodes,"name");            
            }
        else
            {
            $source_values[] = $value;
            }

        // $source_field = get_resource_field_data($resource,$targetfield);
        if(!in_array($targetfield["type"],$valid_ai_field_types) || count($source_values) == 0)
            {
            return false;
            }
        // Use this field's value to update the dependent field
        $updated = openai_gpt_update_field($resource,$targetfield,$source_values);
        return $updated;
        }
    return false;
    }
    
/**
 *  Hook into save_resource_data() and save_resource_data_multi() to process value changes
 *
 * @param int|array     $r                      Resource ID or array of resource IDs
 * @param mixed         $all_nodes_to_add       Passed from hook, unused
 * @param mixed         $all_nodes_to_remove    Passed from hook, unused
 * @param mixed         $autosave_field         Passed from hook, unused
 * @param mixed         $fields                 Array of edited field data
 * @param mixed         $updated_resources      Array of resources & fields that have been updated
 *                                              with resources as the top level key and field IDs as subkeys
 * 
 * @return bool
 * 
 */
function HookOpenai_gptAllAftersaveresourcedata($r, $all_nodes_to_add, $all_nodes_to_remove,$autosave_field, $fields,$updated_resources)
    {
    if(!(is_int_loose($r) || is_array($r)))
        {
        return false;
        }
    
    $refs = (is_array($r) ? $r : [$r]);
    debug("openai_gpt Aftersaveresourcedata - resources to update:  " . implode(",",$refs));
    $success=false;
    // Check if any configured fields have been edited
    foreach($fields as $field)
        {
        $targetfields = openai_gpt_get_dependent_fields($field["ref"]);
        foreach($targetfields as $targetfield)
            {
            debug("openai_gpt aftersaveresourcedata - processing field #" . $targetfield["ref"] . " (" . $targetfield["title"] . ")");
            foreach($refs as $ref)
                {
                // Has the value been updated?
                if(isset($updated_resources[$ref][$field["ref"]]) && count($updated_resources[$ref][$field["ref"]]) > 0)
                    {
                    if(count($updated_resources[$ref][$field["ref"]]) == 1 && trim($updated_resources[$ref][$field["ref"]][0]) == "")
                        {
                        // Empty value - clear the target field
                        debug("openai_gpt - no value set for resource # " . $ref . ", field #" . $field["ref"] . " " . $field["name"] . ", clearing target field #" . $targetfield["ref"]);
                        $updated =  update_field($ref,$targetfield["ref"],"");
                        }
                    else
                        {
                        $updated = openai_gpt_update_field($ref,$targetfield,$updated_resources[$ref][$field["ref"]]);
                        }
                    if($updated)
                        {
                        $success=true;
                        }
                    }
                }
            }
        }
    return $success;
    }
