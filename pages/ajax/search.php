<?php
include_once '../../include/db.php';
include_once '../../include/general.php';
include_once '../../include/authenticate.php';

$ajax = filter_var(getvalescaped('ajax', false), FILTER_VALIDATE_BOOLEAN);
if(!$ajax)
    {
    header('HTTP/1.1 400 Bad Request');
    die('AJAX only accepted!');
    }

/* Variables that should be available for any cases below, otherwise 
   they should be put in that use case only */
$return        = array();
$search_string = getvalescaped('search_string', '');

if(filter_var(getval('generate_tags', false), FILTER_VALIDATE_BOOLEAN))
    {
    // Quoted search detected, so anything within double quotes should allow for white spaces
    if(false !== strpos($search_string, '"'))
        {
        // 
        }

// $config_separators
    echo json_encode($return);
    exit();
    }