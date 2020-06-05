<?php

include_once __DIR__ . "/../../include/db.php";

include_once __DIR__ . "/../../include/plugin_functions.php";

// Update active plugins with new title and icon

$active_plugins = sql_array('SELECT name value FROM plugins WHERE inst_version > 0');

$plugins_dir = dirname(__FILE__) . '/../../plugins/';

foreach ($active_plugins as $plugin)
    {
    $plugin_yaml = get_plugin_yaml($plugins_dir . $plugin . '/' . $plugin . '.yaml', false);
    if ($plugin_yaml['title'] != '')
        {
        sql_query("UPDATE plugins SET title='" . escape_check($plugin_yaml['title']) . "' WHERE name = '" . escape_check($plugin) . "'");
        }
    if ($plugin_yaml['icon'] != '')
        {
        sql_query("UPDATE plugins SET icon='" . escape_check($plugin_yaml['icon']) . "' WHERE name = '" . escape_check($plugin) . "'");
        }
    }
