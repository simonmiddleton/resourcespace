<?php
command_line_only();

include_once __DIR__ . "/../../include/action_functions.php";
include_once __DIR__ . "/../../include/request_functions.php";

// Save settings
$saved_timezone = date_default_timezone_get();
$saved_new_action_email_interval = $new_action_email_interval; // TODO is this required?

// Clean out old data
ps_query("TRUNCATE resource");
ps_query("TRUNCATE user");
ps_query("TRUNCATE request");

// set up environment
$new_action_email_interval = 1;
unset($resource_type_request_emails);
$send_default_notifications = false;

$debug_log=true;
$debug_log_location = "/var/log/resourcespace/debug_dev.log";
$debug_extended_info = true;


// Set up test users

// Admin user
$adminuser = new_user("actionuser005100_1");
$emailadress= "adminuser005100_1@test.resourcespace.com";
$usergroup  = 3;
$approved   = 1;
$params = ["i",$approved,"s",$emailadress,"i",$usergroup,"s",$language,"i",$adminuser];
ps_query("UPDATE user SET approved = ?,email = ?,usergroup = ?,lang = ? WHERE ref = ?",$params);

// General user
$generaluser = new_user("generaluser005100_2");
$emailadress= "actionuser005100_1@test.resourcespace.com";
$usergroup  = 2;
$approved   = 1;
$params = ["i",$approved,"s",$emailadress,"i",$usergroup,"s",$language,"i",$generaluser];
ps_query("UPDATE user SET approved = ?,email = ?,usergroup = ?,lang = ? WHERE ref = ?",$params);

// Test A - Get resource review actions - admin user
$resourcea = create_resource(1,0,$adminuser);
$resourceb = create_resource(1,-1,$adminuser);
$resourcec = create_resource(2,0,$adminuser);
$resourced = create_resource(2,-1,$adminuser);
$resourcee = create_resource(3,-1,$adminuser);
set_config_option($adminuser,"user_pref_new_action_emails", "1");
set_config_option($adminuser,"actions_resource_review", "1");
set_config_option($adminuser,"actions_notify_states", "-1");
set_config_option($adminuser,"actions_resource_types_hide", "2");
$actions = get_user_actions_recent(5,true);
if(!isset($actions[$adminuser]["resourcereview"]) || 
    !match_values(array_column($actions[$adminuser]["resourcereview"],"ref"),[$resourceb, $resourcee])
    )
    {
    echo "Test A - failed to get correct actions" . PHP_EOL;
    echo print_r($actions);
    return false;
    }
    
// Test B - Get resource review actions - standard user
$resourcef = create_resource(1,-1,$generaluser);
$resourceg = create_resource(1,-1,$generaluser);
$resourceh = create_resource(2,-1,$generaluser);
$resourcei = create_resource(2,-1,$generaluser);
$resourcej = create_resource(3,-1,$generaluser);
set_config_option($generaluser,"user_pref_new_action_emails", "1");
set_config_option($generaluser,"actions_resource_review", "1");
set_config_option($generaluser,"actions_notify_states", "-2");
set_config_option($generaluser,"actions_resource_types_hide", "2");
$actions = get_user_actions_recent(5,true);
if(isset($actions[$generaluser]["resourcereview"]))
    {
    echo "Test B - failed to get correct actions" . PHP_EOL;
    echo print_r($actions);
    return false;
    }

// Test C - move resource contributed by $generaluser back to pending submission
update_archive_status($resourcef,-2);
update_archive_status($resourceh,-2);
update_archive_status($resourcej,-2);
$actions = get_user_actions_recent(5,true);
if(!isset($actions[$generaluser]["resourcereview"]) || 
    !match_values(array_column($actions[$generaluser]["resourcereview"],"ref"),[$resourcef, $resourcej])
    )
    {
    echo "Test C - failed to get correct actions" . PHP_EOL;
    echo print_r($actions);
    return false;
    }


// Test D - check resource requests
ps_query("UPDATE resource SET access=1 WHERE ref IN (?,?)",["i",$resourcea,"i",$resourcec]);
$genuserdata = get_user($generaluser);
setup_user($genuserdata);
$col1 = create_collection($generaluser,"test_005100",1);
add_resource_to_collection($resourcea,$col1);
$request = managed_collection_request($col1,"Test request",false);
// Get actions only for admin user
$adminuserdata = get_user($adminuser);
setup_user($adminuserdata);
$actions = get_user_actions_recent(5,false);
if(!isset($actions[$adminuser]["resourcerequest"]) || 
    isset($actions[$generaluser]) ||
    !match_values(array_column($actions[$adminuser]["resourcerequest"],"ref"),[$request])
    )
    {
    echo "Test D - failed to get resourcerequest action" . PHP_EOL;
    echo print_r($actions);
    return false;
    }

// Test E - check user account request
$userrequest = new_user("newuser_005100");
ps_query("UPDATE user set approved=0 WHERE ref=?",["i",$userrequest]);
// Get actions only for admin user
$actions = get_user_actions_recent(5,false);
if(!isset($actions[$adminuser]["userrequest"]) || 
    !match_values(array_column($actions[$adminuser]["userrequest"],"ref"),[$userrequest])
    )
    {
    echo "Test E - failed to get userrequest action" . PHP_EOL;
    echo print_r($actions);
    return false;
    }

// Cleanup
$new_action_email_interval = $saved_new_action_email_interval;

return true;
