<?php


function HookGoogle_visionAllUploadfilesuccess($resource)
    {
    include_once __DIR__ . "/../include/google_vision_functions.php";
    google_visionProcess($resource);
    }

function HookGoogle_visionAllAfter_update_resource($resource)
	{
	include_once __DIR__ . "/../include/google_vision_functions.php";
    google_visionProcess($resource);
	}