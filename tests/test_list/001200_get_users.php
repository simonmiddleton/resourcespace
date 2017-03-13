<?php
// Delete all users first
sql_query("delete from user");

$user1=new_user("user1");
$user2=new_user("user2");

$_POST['password'] = generateSecureKey();
$_POST['fullname'] = "User One";

$_POST['approved'] = "1";
$_POST['username'] = "user1";
$_POST['email'] = "userone@dummy.resourcespace.com";
$_POST['usergroup'] = "1";
save_user($user1);

unset($_POST['approved']);
$_POST['username'] = "user2";
$_POST['email'] = "usertwo@dummy.resourcespace.com";
$_POST['usergroup'] = "2";
save_user($user2);

// Get only approved users
$testusers=get_users("","","username",true,-1,1);

if(count($testusers)!=1){return false;}

return true;
