<?php
// Run autocomplete macros for every resource

include "../../include/db.php";

// Allow access from UI only if authenticated and admin
if (PHP_SAPI != 'cli')
    {
    include "../../include/authenticate.php";

    if (!checkperm('a'))
        {
        http_response_code(401);
        exit($lang['error-permissiondenied']);
        }
    }

// Pass "force=true" to update resources that already have a value in their autocomplete macro field
$force_update = false;
$cli_options = (PHP_SAPI == 'cli' ? getopt('', array('force')) : array());
if(array_key_exists('force', $cli_options))
    {
    $force_update = true;
    }

$force_update = (bool) getval("force", $force_update);

$resources = ps_query("SELECT ref FROM resource WHERE ref > 0");

for ($n = 0; $n < count($resources); $n++)
    {
    $fields_updated = autocomplete_blank_fields($resources[$n]["ref"], $force_update, true);

    foreach ($fields_updated as $key => $val)
        {
        echo "Resource " . $resources[$n]["ref"] . ", Field " . $key . " = " . $val . "<br>";
        }
    }
