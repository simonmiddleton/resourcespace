<?php
if('cli' != PHP_SAPI)
    {
    exit('This utility is command line only.');
    }

// Set up
$orig_userpermissions = $userpermissions;
ps_query("INSERT INTO tab(`name`, order_by) VALUES ('10500_Tab1', 10)");
$tab_1 = sql_insert_id();
ps_query("INSERT INTO tab(`name`, order_by) VALUES ('10500_Tab2', 20)");
$tab_2 = sql_insert_id();



// Users without "a" perm shouldn't delete tabs
$userpermissions = array_diff($userpermissions, ['a']);
$deleted_tab = delete_tabs([$tab_1]);
$userpermissions = $orig_userpermissions;
$found = ps_value('SELECT ref AS `value` FROM tab WHERE ref = ?', ['i', $tab_1], 0);
if($deleted_tab || $found === 0)
    {
    echo 'Delete tabs (not authorised) - ';
    return false;
    }


// Check you can't delete the "Default" tab (always ref #1)
$deleted_tab = delete_tabs([1]);
$found = ps_value('SELECT ref AS `value` FROM tab WHERE ref = 1', [], 0);
if($found === 0)
    {
    echo 'Prevent deleting the "Default" tab - ';
    return false;
    }


// Check delete tab functionality
$deleted_tab = delete_tabs([$tab_2]);
$found = ps_value('SELECT ref AS `value` FROM tab WHERE ref = ?', ['i', $tab_2], 0);
if($found > 0)
    {
    echo 'Delete tabs - ';
    return false;
    }



// Tear down
unset($tab_1, $deleted_tab, $found);

return true;