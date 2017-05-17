<?php
/**
* Initialize facial recognition functionality.
* 
* IMPORTANT: only one field can be setup for the annotation side and it also MUST be a dynamic keywords list
* 
* @uses sql_value()
* 
* @return boolean
*/
function initFacialRecognition()
    {
    global $facial_recognition_tag_field, $facial_recognition_face_recognizer_models_location, $annotate_enabled,
           $annotate_fields;

    if(!is_numeric($facial_recognition_tag_field) || 0 >= $facial_recognition_tag_field)
        {
        return false;
        }

    if(!file_exists($facial_recognition_face_recognizer_models_location))
        {
        return false;
        }

    $facial_recognition_rtf_type = sql_value(
        "SELECT `type` AS `value`
           FROM resource_type_field
          WHERE ref = '" . escape_check($facial_recognition_tag_field) . "'
        ",
        null);

    if(FIELD_TYPE_DYNAMIC_KEYWORDS_LIST != $facial_recognition_rtf_type)
        {
        return false;
        }

    $annotate_enabled = true;
    $annotate_fields  = array($facial_recognition_tag_field);

    return true;
    }
