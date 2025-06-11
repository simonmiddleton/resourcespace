<?php

include_once dirname(__FILE__, 4) . '/include/boot.php';
include_once dirname(__FILE__, 2) . '/include/faces_functions.php';

command_line_only();

// Get all resources that haven't had faces processed yet
$resources = ps_array("SELECT ref value FROM resource WHERE (faces_processed is null or faces_processed=0) ORDER BY ref desc");

foreach ($resources as $resource) {
    faces_detect($resource);
}
