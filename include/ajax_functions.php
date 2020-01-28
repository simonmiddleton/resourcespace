<?php
/**
* Returns a standard AJAX response for unauthorised access
* 
* The function will return a 401 HTTP status code.
* 
* @return void
*/
function ajax_permission_denied()
    {
    $return['error'] = array(
        'status' => 401,
        'title'  => $GLOBALS["lang"]["unauthorized"],
        'detail' => $GLOBALS["lang"]['error-permissiondenied']);

    http_response_code(401);
    echo json_encode($return);
    exit();
    }