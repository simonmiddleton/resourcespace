<?php


function HookGoogle_visionAllUploadfilesuccess($resource)
    {
    include_once __DIR__ . "/../include/google_vision_functions.php";
    google_visionProcess($resource);
    }
