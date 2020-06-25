<?php
if('cli' != PHP_SAPI)
    {
    exit('This utility is command line only.');
    }

$resource_portrait = create_resource(1, 0);
sql_query("INSERT INTO resource_dimensions(resource, width, height) VALUES ('$resource_portrait', 1000, 2000)");
$search = search_special(
    "!propertiesorientation:portrait",
    "",
    48,
    "",
    "",
    "score DESC, user_rating DESC, total_hit_count DESC , field12 DESC,  r.ref DESC",
    "relevance",
    "r.ref, r.resource_type, r.archive, r.access, r.hit_count total_hit_count",
    "resource_type IN ('1','2','3','4') AND archive IN (0) AND r.ref>0",
    array(0),
    false,
    false,
    false
);
if(!is_array($search) || count($search) == 0 || $search[0]["ref"] != $resource_portrait)
    {
    echo "!propertiesorientation:portrait - ";
    return false;
    }


$resource_landscape = create_resource(1, 0);
sql_query("INSERT INTO resource_dimensions(resource, width, height) VALUES ('$resource_landscape', 3000, 1500)");
$search = search_special(
    "!propertiesorientation:landscape",
    "",
    48,
    "",
    "",
    "score DESC, user_rating DESC, total_hit_count DESC , field12 DESC,  r.ref DESC",
    "relevance",
    "r.ref, r.resource_type, r.archive, r.access, r.hit_count total_hit_count",
    "resource_type IN ('1','2','3','4') AND archive IN (0) AND r.ref>0",
    array(0),
    false,
    false,
    false
);
if(!is_array($search) || count($search) == 0 || $search[0]["ref"] != $resource_landscape)
    {
    echo "!propertiesorientation:landscape - ";
    return false;
    }


$resource_square = create_resource(1, 0);
sql_query("INSERT INTO resource_dimensions(resource, width, height) VALUES ('$resource_square', 1000, 1000)");
$search = search_special(
    "!propertiesorientation:square",
    "",
    48,
    "",
    "",
    "score DESC, user_rating DESC, total_hit_count DESC , field12 DESC,  r.ref DESC",
    "relevance",
    "r.ref, r.resource_type, r.archive, r.access, r.hit_count total_hit_count",
    "resource_type IN ('1','2','3','4') AND archive IN (0) AND r.ref>0",
    array(0),
    false,
    false,
    false
);
if(!is_array($search) || count($search) == 0 || $search[0]["ref"] != $resource_square)
    {
    echo "!propertiesorientation:square - ";
    return false;
    }


$resource_height_null = create_resource(1, 0);
sql_query("INSERT INTO resource_dimensions(resource, width, height) VALUES ('$resource_height_null', 1000, NULL)");
$search = search_special(
    "!propertiesorientation:landscape",
    "",
    48,
    "",
    "",
    "score DESC, user_rating DESC, total_hit_count DESC , field12 DESC,  r.ref DESC",
    "relevance",
    "r.ref, r.resource_type, r.archive, r.access, r.hit_count total_hit_count",
    "resource_type IN ('1','2','3','4') AND archive IN (0) AND r.ref>0",
    array(0),
    false,
    false,
    false
);
if(!is_array($search) || count($search) == 0 || $search[0]["ref"] != $resource_height_null)
    {
    echo "Invalid dimensions - ";
    return false;
    }


// Tear down
delete_resource($resource_portrait);
unset($resource_portrait);
delete_resource($resource_landscape);
unset($resource_landscape);
delete_resource($resource_square);
unset($resource_square);
delete_resource($resource_height_null);
unset($resource_height_null);
unset($search);

return true;