<?php
if('cli' != PHP_SAPI)
    {
    exit('This utility is command line only.');
    }


// Set up test
// --- Set some tracking for the current user
set_sysvar("track_var_{$userref}", 'test_1002_var1, test_1002_var2');
set_sysvar("track_var_{$userref}_duration", '5');
set_sysvar("track_var_{$userref}_start_datetime", date('Y-m-d H:i:s'));
// --- Set some tracking for a made up user
$made_up_user = 2001;
set_sysvar("track_var_{$made_up_user}", 'foo, bar,test_1002_var1, test_1002_var2, , baz,');
set_sysvar("track_var_{$made_up_user}_duration", '5');
set_sysvar("track_var_{$made_up_user}_start_datetime", '2021-04-30 08:10:00');


// Test current user should get 2 vars back (as it was set up)
if(get_tracked_vars($userref) !== ['test_1002_var1', 'test_1002_var2'])
    {
    echo 'get_tracked_vars(userref) - ';
    return false;
    }

// Test that missing values from the CSV of variables are being removed from the list
if(get_tracked_vars($made_up_user) !== ['foo', 'bar', 'test_1002_var1', 'test_1002_var2', 'baz'])
    {
    echo 'get_tracked_vars filters out missing vars - ';
    return false;
    }

// Testing getting all users' tracked variables
if(get_tracked_vars(0) !== ['test_1002_var1', 'test_1002_var2', 'foo', 'bar', 'baz'])
    {
    echo 'Get list of all tracked variables - ';
    return false;
    }


// Test if system can recognise tracking is still active
if(!is_tracking_vars_active($userref))
    {
    echo 'Detect active tracking session - ';
    return false;
    }


// Test if system can recognise tracking is NOT active anymore
if(is_tracking_vars_active($made_up_user))
    {
    echo 'Detect expired tracking session - ';
    return false;
    }


// Test clearing tracked variables information for users
clear_tracking_vars_info([$userref, $made_up_user]);
if(!empty(get_tracked_vars(0)))
    {
    echo 'Clear tracking variables information - ';
    return false;
    }



// Tear down
unset($made_up_user);

return true;