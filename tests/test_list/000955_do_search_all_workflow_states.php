<?php
command_line_only();

// Check for searching using $search_all_workflow_states=false;

// Set a few options  that could affect test
$saved_userref = $userref;
$savedpermissions = $userpermissions;
$pending_review_visible_to_all = false;
$search_all_workflow_states = false;
$pending_submission_searchable_to_all=false;
$notify_user_contributed_submitted = false;

// Get baseline to compare in case pre-existing data changes
$results = do_search('');
$basecount = is_array($results) ? count($results) : 0;

// Get search all baseline to compare in case pre-existing data changes
$search_all_workflow_states = true;
$results = do_search('');
$allcount = is_array($results) ? count($results) : 0;
$search_all_workflow_states = false;


// SUBTEST A
// Add a new resource in pending archive state and check not returned
$userref = 999;
$resourcea = create_resource(1,1);
$userref = 1;
$userpermissions = array("s","g","f*");
$results = do_search('');
if(is_array($results) && count($results) != $basecount)
    {
    echo "ERROR - SUBTEST A\n";
    return false;
    }

// SUBTEST B
$search_all_workflow_states = true;
$results = do_search('');
if(count($results) != $allcount + 1)
    {
    echo "ERROR - SUBTEST B\n";
    return false;
    }

// Update new total
$allcount++;

// SUBTEST C
// Test $pending_review_visible_to_all. Add a new resource in pending review state and check not returned
$userref = 999;
$userpermissions = $savedpermissions;
$resourceb = create_resource(1,-1);
$userref = 1;
$userpermissions = array("s","g","f*");
$results = do_search('');
if(is_array($results) && count($results) != $allcount)
    {
    echo "ERROR - SUBTEST C\n";
    return false;
    }
    
// SUBTEST D
$pending_review_visible_to_all = true;
$results = do_search('');
if(count($results) != $allcount + 1)
    {
    echo "ERROR - SUBTEST D\n";
    return false;
    }

$allcount++;

// SUBTEST E
// Test $pending_submission_searchable_to_all. Add a new resource in pending submission state and check it is not returned
$userref=999;
$userpermissions = $savedpermissions;
$resourceb=create_resource(1,-2);
$userref=1;
$userpermissions = array("s","g","f*");
$results = do_search('');
if(count($results) != $allcount)
    {
    echo "ERROR - SUBTEST E\n";
    return false;
    }
   
// SUBTEST F 
$pending_submission_searchable_to_all = true;
// Now resource b should be returned
$results = do_search('');
if(count($results) != $allcount + 1)
    {
    echo "ERROR - SUBTEST F\n";
    return false;
    }
    
$allcount++;

// Reset to standard settings
$userref = $saved_userref;
$userpermissions = $savedpermissions;
$pending_review_visible_to_all=false;
$pending_submission_searchable_to_all=false;
$search_all_workflow_states=false;
$notify_user_contributed_submitted = true;

return true;
