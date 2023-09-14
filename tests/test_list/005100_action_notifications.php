<?php
command_line_only();

include_once __DIR__ . "/../../include/action_functions.php";

// Save settings
$saved_timezone = date_default_timezone_get();
$saved_new_action_email_interval = $new_action_email_interval; // TODO is this required?

// Set up test users
// Admin user, USA west coast timezone, Spanish
$adminuser = new_user("actionuser005100_1");
$language   = "es";
$emailadress= "adminuser005100_1@test.resourcespace.com";
$usergroup  = 3;
$approved   = 1;
$params = ["i",$approved,"s",$emailadress,"i",$usergroup,"s",$language,"i",$adminuser];
ps_query("UPDATE user SET approved = ?,email = ?,usergroup = ?,lang = ? WHERE ref = ?",$params);
set_config_option($adminuser, "user_local_timezone", "America/Los_Angeles");

// General user, UK timezone, British english
$generaluser = new_user("generaluser005100_2");
$language   = "en";
$emailadress= "actionuser005100_1@test.resourcespace.com";
$usergroup  = 2;
$approved   = 1;
$params = ["i",$approved,"s",$emailadress,"i",$usergroup,"s",$language,"i",$generaluser];
ps_query("UPDATE user SET approved = ?,email = ?,usergroup = ?,lang = ? WHERE ref = ?",$params);
set_config_option($generaluser, "user_local_timezone", "Europe/London");

$use_cases = [
    [
    'name' => 'Get resource review actions - admin user',
    'user' => $adminuser,
    'resources' => [
        [1,0,$adminuser],
        [1,-1,$adminuser],
        [2,0,$adminuser],
        [2,-1,$adminuser],
        [3,-1,$adminuser],
        ],

    'preferences' => [
        [$adminuser,"user_pref_new_action_emails", "1"],
        [$adminuser,"actions_resource_review", "1"],
        [$adminuser,"actions_notify_states", "-1"],
        [$adminuser,"user_pref_new_action_emails", "1"],
        [$adminuser,"actions_resource_types_hide", "2"],
        ],
    'expectedcount' => ["resourcereview","2"],
    'expected' => [
        [$adminuser,'resourcereview',5],
        [$adminuser,'resourcereview',2],
        ],
    ],
    [
    'name' => 'Get resource review actions - general user',
    'user' => $generaluser,
    // [type,archive)]
    'resources' => [
        [1,-1,$generaluser],
        [1,-1,$generaluser],
        [2,-1,$generaluser],
        [2,-1,$generaluser],
        [3,-1,$generaluser],
        ],
    
    'preferences' => [
        [$generaluser,"user_pref_new_action_emails", "1"],
        [$adminuser,"actions_resource_review", "1"],
        [$generaluser,"actions_notify_states", "-2"],
        [$generaluser,"user_pref_new_action_emails", "1"],
        [$generaluser,"actions_resource_types_hide", "2"],
        ],
    'expectedcount' => ["resourcereview","0"],
    'expected' => [],
    ],

];

$new_action_email_interval = 1;
foreach($use_cases as $use_case)
    {
    if(isset($use_case["resources"]))
        {
        foreach($use_case["resources"] as $resource)
            {
            create_resource($resource[0],$resource[1], $resource[2]);
            }
        }
    if(isset($use_case["preferences"]))
        {
        foreach($use_case["preferences"] as $preference)
            {
            set_config_option($preference[0],$preference[1], $preference[2]);
            }
        }

    // Perform test 
    $actions = get_user_actions_recent(5,true);

    if(isset($actions[$use_case["user"]][$use_case["expectedcount"][0]]) 
        && $use_case["expectedcount"][1] != count($actions[$use_case["user"]][$use_case["expectedcount"][0]])
        )
        {
        echo $use_case["name"] . ": failed to get correct action count for user " . $use_case["user"] . ". Expected: " . $use_case["expectedcount"][1] . ", returned: " . (isset($actions[$use_case["user"]][$use_case["expectedcount"][0]]) ? count($actions[$use_case["user"]][$use_case["expectedcount"][0]]) : 0) . " ";
        echo print_r($actions);
        return false;
        }
    foreach($use_case["expected"] as $expected)
        {
        if(!isset($actions[$expected[0]])
            || !isset($actions[$expected[0]][$expected[1]])
            || !in_array($expected[2],array_column($actions[$expected[0]][$expected[1]],"ref"))
            )
            {
            echo $use_case["name"] . ": failed to get correct actions for resource " . $expected[2] . " ";
            return false;
            }
        }
    }


// Cleanup
date_default_timezone_set($saved_timezone);
$new_action_email_interval = $saved_new_action_email_interval;

return true;
