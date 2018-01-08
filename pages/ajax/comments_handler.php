<?php
include "../../include/db.php";
include_once "../../include/general.php";
include "../../include/authenticate.php";
include "../../include/comment_functions.php";

if('POST' == $_SERVER['REQUEST_METHOD'])
    {
    if(!empty($username))
        {
        comments_submit();
        }
    }

$ref             = getvalescaped('ref', 0, true);
$collection_mode = ('' != getvalescaped('collection_mode', '') ? true : false);

comments_show($ref, $collection_mode);
