<?php
include dirname(__FILE__) . '/../../../include/db.php';
include dirname(__FILE__) . '/../../../include/authenticate.php';

$user_group_id = getval('user_group_id', '');

$filtered_users = get_users($user_group_id);

echo json_encode($filtered_users);
exit();
?>