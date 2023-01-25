<?php

function HookOpenai_gptAllModifyresourcetypefieldcolumns($fieldcolumns)
    {
    global $lang;
    $addcolumns = [
        'openai_gpt_prompt'        => array($lang['property-openai_gpt_prompt'],'',2,0),
        'openai_gpt_output_field'  => array($lang['property-openai_gpt_output_field'],'',0,0),
        ];
    return array_merge($fieldcolumns,$addcolumns);
    }

function HookOpenai_gptAdmin_resource_type_field_editAdmin_field_replace_question($ref,$column,$column_detail,$fielddata)
    {
    global $lang;
    if(!in_array($column,["openai_gpt_output_field","openai_gpt_prompt"]))
        {
        return false;
        }
    
    $currentvalue = $fielddata[$column];
    if($column=="openai_gpt_output_field")
        {
        $valid_ai_field_types = [
            FIELD_TYPE_RADIO_BUTTONS,
            FIELD_TYPE_CHECK_BOX_LIST,
            FIELD_TYPE_DROP_DOWN_LIST,
            FIELD_TYPE_DYNAMIC_KEYWORDS_LIST,
            FIELD_TYPE_TEXT_BOX_SINGLE_LINE,
            FIELD_TYPE_TEXT_BOX_MULTI_LINE,
            FIELD_TYPE_TEXT_BOX_LARGE_MULTI_LINE,
            FIELD_TYPE_WARNING_MESSAGE,
            FIELD_TYPE_TEXT_BOX_FORMATTED_AND_CKEDITOR,
        ];

        $fields = get_resource_type_fields("","title","asc","",$valid_ai_field_types);
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
            <textarea class="stdwidth" rows="5" id="field_edit_<?php echo htmlspecialchars((string) $column_detail[0]); ?>" name="<?php echo htmlspecialchars((string) $column); ?>"><?php echo htmlspecialchars((string) $currentvalue)?></textarea>
        </div>		
        <?php
        return true;
        }
    return false;
    }

// function HookOpenai_gptAllUpdate_field($resource, $field, $value)
//     {
//     $validfield = false;

//     $resdata = get_resource_data($resource);
//     foreach(tms_link_get_modules_mappings() as $module_uid => $module)
//         {
//         if(!in_array($resdata['resource_type'], $module['applicable_resource_types']))
//             {
//             continue;
//             }

//         $tms_object_id = intval($value);
//         debug("tms_link: updating resource id #" . $resource);

//         $module_name = $module['module_name'];
//         $tmsdata = tms_link_get_tms_data($resource, $tms_object_id,'', $module_name);

//         // if call to tms_link_get_tms_data() does not return an array, error has occurred
//         if (!is_array($tmsdata))
//             {
//             return $tmsdata;  // return error message
//             }

//         if(!array_key_exists($module_name, $tmsdata))
//             {
//             continue;
//             }

//         foreach($module['tms_rs_mappings'] as $tms_rs_mapping)
//             {
//             if($tms_rs_mapping['rs_field'] > 0 && $module['rs_uid_field'] != $tms_rs_mapping['rs_field'] && isset($tmsdata[$module_name][$tms_rs_mapping['tms_column']]))
//                 {
//                 debug("tms_link: updating field '{$field}' with data from column '{$tms_rs_mapping['tms_column']}' for resource id #{$resource}");

//                 update_field($resource, $tms_rs_mapping['rs_field'], $tmsdata[$module_name][$tms_rs_mapping['tms_column']]);
//                 }
//             }
//         }

//     tms_link_check_preview($resource);

//     return true;
//     }

