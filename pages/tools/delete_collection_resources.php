<?php

# Delete all resources in a collection

include "../../include/db.php";

command_line_only();

if (!isset($argv[1])) {
    exit("Usage: php delete_collection_resources.php [collection ID]\n");
}

$collection_ID = $argv[1];
echo "Deleting all resources in collection " . $collection_ID . "\n";
delete_resources_in_collection($collection_ID);