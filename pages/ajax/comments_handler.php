<?php
include "../../include/db.php";

include "../../include/authenticate.php";
include "../../include/comment_functions.php";

if('POST' == $_SERVER['REQUEST_METHOD'])
    {
    if(!empty($username))
        {
        comments_submit();
        }
    }

$ref             = getval('ref', 0, true);
$collection_mode = ('' != getval('collection_mode', '') ? true : false);

comments_show($ref, $collection_mode);
