<?php
// Do the include and authorization checking ritual
include '../../../include/db.php';
include_once '../../../include/general.php';
include '../../../include/authenticate.php'; if (!checkperm('a')) {exit ($lang['error-permissiondenied']);}


$resource=52;

include __DIR__ . "/../include/google_vision_functions.php";

google_visionProcess($resource);



