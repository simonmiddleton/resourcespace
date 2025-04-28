<?php

include_once dirname(__FILE__, 4) . '/include/boot.php';
include_once dirname(__FILE__, 2) . '/include/faces_functions.php';

command_line_only();

// Find all faces that have not been identified.
$resources = ps_array("SELECT distinct resource value FROM resource_face WHERE (node is null or node=0) ORDER BY ref desc");

foreach ($resources as $resource) {
    faces_tag($resource);
}
