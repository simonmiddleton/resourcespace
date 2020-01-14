<?php

include_once __DIR__ . "/../../include/db.php";
include_once __DIR__ . "/../../include/general.php";

// Rename existing 'My collection' collections
sql_query("UPDATE collection SET `name` = 'Default Collection' WHERE TRIM(`name`) = 'My Collection'");
echo 'Collections migrated' . PHP_EOL;

// Rename existing 'My collection' dash tiles
sql_query("UPDATE dash_tile SET `title` = 'Default Colleciton' WHERE TRIM(`title`) = 'My Collection'");
echo 'Dash Tiles migrated' . PHP_EOL;