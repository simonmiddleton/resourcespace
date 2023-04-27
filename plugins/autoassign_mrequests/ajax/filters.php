<?php
include dirname(__FILE__) . '/../../../include/db.php';
include dirname(__FILE__) . '/../../../include/authenticate.php';

if(!checkperm('a'))
    {
    exit($lang['error-permissiondenied']);
    }

$user_group_id = getval('user_group_id', '');
$filtered_users = get_users($user_group_id,'','u.username',true,-1,'',false,"u.ref, u.username,u.fullname, u.email");
echo json_encode($filtered_users);