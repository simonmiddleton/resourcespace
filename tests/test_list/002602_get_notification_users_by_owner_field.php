<?php
command_line_only();

$webroot = dirname(__DIR__, 2);
include_once("{$webroot}/include/request_functions.php");


// Set up
// Create a metadata field for the owner 
$owner_rtf = create_resource_type_field('test_2602 owner_field', 0, FIELD_TYPE_DROP_DOWN_LIST, 'test_2602_owner_field', false);
if($owner_rtf === false)
    {
    echo 'Setting up the test: $owner_rtf - ';
    return false;
    }
$GLOBALS['owner_field'] = $owner_rtf;

// Create Options for the owner_field
$owner_A = set_node(null, $owner_rtf, 'Test 2602: Owner - Admins', null, 10);
$owner_SA = set_node(null, $owner_rtf, 'Test 2602: Owner - Super Admins', null, 20);
$owner_Others = set_node(null, $owner_rtf, 'Test 2602: Owner - Others', null, 30);
foreach([$owner_A, $owner_SA, $owner_Others] as $node_ref)
    {
    if($node_ref === false)
        {
        echo 'Setting up the test: node options for the $owner_rtf - ';
        return false;
        }
    }

// Re-use existing user groups for this test:-
// [1] => Administrators
// [3] => Super Admin
// [6] => Restricted User - Requests Managed
// print_r(array_column(ps_query('SELECT ref, `name` FROM usergroup'), 'name', 'ref'));die;
$GLOBALS['owner_field_mappings'] = [
    $owner_A => 1,
    $owner_SA => 3,
    $owner_Others => 6,
];

// Create users
$users_data = [
    ['test_2602_A', 'Test 2602: User A', 1],
    ['test_2602_SA', 'Test 2602: User SA', 3],
    ['test_2602_Others', 'Test 2602: User Others', 6],
    ['test_2602_gen_1', 'Test 2602: User gen_1', 2],
    ['test_2602_gen_2', 'Test 2602: User gen_2', 2],
];
$users_list = [];
foreach($users_data as $user_details)
    {
    $user_2602 = new_user($user_details[0], $user_details[2]) ?: get_user_by_username($user_details[0]);
    
    // Save details (email most important)
    $_POST['username'] = $user_details[0];
    $_POST['email'] = "{$user_details[0]}@integration-test.resourcespace.com";
    $_POST['fullname'] = $user_details[1];
    $_POST['usergroup'] = $user_details[2];
    $_POST['password'] = 'test_2602';
    $_POST['approved'] = "1";
    if(!save_user($user_2602))
        {
        echo 'Setting up the test: users - ';
        return false;
        }

    unset($GLOBALS['udata_cache']);
    $users_list[] = get_user($user_2602);
    }


// Create a pool of resources
$vars_suffixes = ['A', 'SA', 'Others', 'none1', 'none2'];
foreach($vars_suffixes as $suffix)
    {
    $var_name = "resource_owned_by_{$suffix}";
    $node_var_name = "owner_{$suffix}";

    // Create resource
    $$var_name = create_resource(1, 0);

    // Quick update of owner field to something valid (when set)
    if(
        // failed to create resource
        $$var_name === false
        // failed to associate owner field value
        || (isset($$node_var_name) && !add_resource_nodes($$var_name, [$$node_var_name], false, false))
    )
        {
        echo 'Setting up the test: resources - ';
        return false;
        }
    }

$build_expected_list_of_users = function(array $usernames) {
    foreach($usernames as $username)
        {
        $result[get_user_by_username($username)] = "{$username}@integration-test.resourcespace.com";
        }
    return $result;
};
// End of Set up





$test_2602_ucs = [
    [
        'name' => 'Notify users owned by A',
        'input' => [
            'users' => get_users(0, 'test_2602_%'),
            'resources' => [$resource_owned_by_A, $resource_owned_by_none1],
        ],
        'expected' => $build_expected_list_of_users(['test_2602_A']),
    ],
];
foreach($test_2602_ucs as $uc)
    {
    $result = get_notification_users_by_owner_field($uc['input']['users'], $uc['input']['resources']);
    // echo "<pre>";print_r($uc['input']['users']);echo "</pre>";
    // echo "<pre>";print_r($uc['input']['resources']);echo "</pre>";
    echo "<pre>";print_r($uc['expected']);echo "</pre>";
    echo "<pre>";print_r($result);echo "</pre>";
    // die("Process stopped in file " . __FILE__ . " at line " . __LINE__);
    if($uc['expected'] !== $result)
        {
        echo "Use case: {$uc['name']} - ";
        return false;
        }
    }



// Tear down
unset($test_2602_ucs, $GLOBALS['owner_field'], $GLOBALS['owner_field_mappings']);

return true;