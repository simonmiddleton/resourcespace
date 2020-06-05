<?php
include_once __DIR__ . "/../../include/db.php";

// Remove tiles that are no longer supported by the system.
sql_query("delete from dash_tile where title in ('themes','mycollections','helpandadvice')");

