<?php

include_once __DIR__ . "/../../include/plugin_functions.php";

// Update active plugins with new title and icon

$active_plugins = ps_array('SELECT name value FROM plugins WHERE inst_version > 0',[]);

$plugins_dir = dirname(__FILE__) . '/../../plugins/';

foreach ($active_plugins as $plugin)
    {
    $plugin_yaml = get_plugin_yaml($plugins_dir . $plugin . '/' . $plugin . '.yaml', false);
    if ($plugin_yaml['title'] != '')
        {
        ps_query("UPDATE plugins SET title = ? WHERE name = ?",["s",$plugin_yaml['title'],"s",$plugin]);
        }
    if ($plugin_yaml['icon'] != '')
        {
        ps_query("UPDATE plugins SET icon = ? WHERE name = ?",["s",$plugin_yaml['icon'],"s",$plugin]);
        }
    }
