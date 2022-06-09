<?php
if('cli' != PHP_SAPI)
    {
    exit('This utility is command line only.');
    }

// Set up
$orig_userpermissions = $userpermissions;
$tab_1 = create_tab(['name' => '10501_Tab1']);
$tab_2 = create_tab(['name' => '10501_Tab2']);



// Users without permission to manage tabs shouldn't be able to delete them
$userpermissions = array_diff($userpermissions, ['a']);
$deleted_tab = delete_tabs([$tab_1]);
$userpermissions = $orig_userpermissions;
$found = ps_value('SELECT ref AS `value` FROM tab WHERE ref = ?', ['i', $tab_1], 0);
if($deleted_tab || $found === 0)
    {
    echo 'Delete tabs (unauthorised) - ';
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
$deleted_tab = delete_tabs([$tab_1, $tab_2]);
$found = ps_value('SELECT ref AS `value` FROM tab WHERE ref = ?', ['i', $tab_2], 0);
if($deleted_tab && $found > 0)
    {
    echo 'Delete tabs - ';
    return false;
    }



// Tear down
unset($tab_1, $tab_2, $deleted_tab, $found);

return true;