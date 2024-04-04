<?php

function HookGoogle_visionAllInitialise()
    {
    global $google_vision_fieldvars;
    config_register_core_fieldvars("Google vision plugin",$google_vision_fieldvars);
    }

function HookGoogle_visionAllAfterpreviewcreation($resource,$alternative)
    {
    if ($alternative === -1) {
        // Nothing to do for alternatives; Google Vision is processed for the main file only.
        include_once __DIR__ . "/../include/google_vision_functions.php";
        google_visionProcess($resource);
    } 
    }