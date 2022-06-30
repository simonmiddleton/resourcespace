<?php

function HookGoogle_visionAllInitialise()
    {
    global $google_vision_fieldvars;
    config_register_core_fieldvars("Google vision plugin",$google_vision_fieldvars);
    }

function HookGoogle_visionAllBeforeuploadfile($ref)
    {
    global $google_vision_restypes, $google_vision_face_detect_field, $google_vision_face_dependent_field, $enable_thumbnail_creation_on_upload, $offline_job_queue;
    
    $resource_data=get_resource_data($ref); # Load resource data (cached).
    if (!in_array($resource_data["resource_type"],$google_vision_restypes) # Not a valid resource
        ||
        $google_vision_face_detect_field == 0 || $google_vision_face_dependent_field == 0 // Facial recognition not configured
        )
        {
        return false;
        }
    
    // Need to ensure image preview is available since a field is configured to only show if faces are detected
    $enable_thumbnail_creation_on_upload = true;
    $offline_job_queue = false;
    return true;
    }

function HookGoogle_visionAllAfterpreviewcreation($resource,$alternative)
    {
    if ($alternative>0) {return true;} // Nothing to do for alternatives; Google Vision is processed for the main file only.

    include_once __DIR__ . "/../include/google_vision_functions.php";

    google_visionProcess($resource);
    }