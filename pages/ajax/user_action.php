<?php
include_once('../../include/db.php');
include_once('../../include/general.php');
include_once('../../include/authenticate.php');
include_once('../../include/resource_functions.php');
include_once('../../include/search_functions.php');
include_once('../../include/collections_functions.php');

// Generic endpoint that can be used for ajax calls
$action = getvalescaped('action','');
$return = array();
$return['status'] = 400; // set to default 

switch ($action)
    {
    case 'submitpending':
        $pending_items=do_search("!contributions" . $userref,"","",-2,-1,"desc",false,0,false,false,"",false,false,true);
        
        // If using '$pending_submission_prompt_review and have added to collection, only submit these resources
        $collection_add = getvalescaped('collection_add', 0, true);
        $collection_resources = is_numeric($collection_add)?get_collection_resources($collection_add):array();  
  
        $submit = array();
        $submitstates = array();
        
        for ($r=0;$r<count($pending_items);$r++)
              {
              if($collection_add == 0 || in_array($pending_items[$r]["ref"],$collection_resources))
                {
                // Add this resource to the array of resources to submit
                $submit[] = $pending_items[$r]["ref"];
                $submitstates[] = -2; // Needed so that from state is logged correctly by update_archive_status
                }
              }
        if(count($submit) > 0)
            {
            // Submit all the resources
            update_archive_status($submit, -1,$submitstates, $collection_add);
            }
        
        $return['status'] = 200;
        break;

    default:
        $return['message'] = $lang["error_generic"] ;
        break;
    }         
http_response_code($return['status']);
header('Content-type: application/json');
echo json_encode($return);
exit();

