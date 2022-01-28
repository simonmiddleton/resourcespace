<?php
// Count existing users first
$existingusers=get_users("","","username",true,-1,1);
$usercount = count($existingusers);

$user1=new_user("user1");
$user2=new_user("user2");
$user3=new_user("user3");
$user4=new_user("user4");

$_POST['password'] = generateSecureKey();
$_POST['fullname'] = "User One";

$_POST['approved'] = "1";
$_POST['username'] = "user1";
$_POST['email'] = "userone@dummy.resourcespace.com";
$_POST['usergroup'] = "1";
save_user($user1);

$_POST['approved'] = "1";
$_POST['username'] = "user2";
$_POST['email'] = "usertwo@dummy.resourcespace.com";
$_POST['usergroup'] = "2";
save_user($user2);

unset($_POST['approved']);
$_POST['username'] = "user3";
$_POST['email'] = "userthree@dummy.resourcespace.com";
$_POST['usergroup'] = "2";
save_user($user3);

$_POST['approved'] = "1";
$_POST['username'] = "user4";
$_POST['email'] = "userfour@dummy.resourcespace.com";
$_POST['usergroup'] = "2";
save_user($user4);

// Get only approved users
$approved_users=get_users("","","username",true,-1,1);

if(count($approved_users) != $usercount + 3){return false;}

// Get all users
$all_users=get_users("","","username");

if(count($all_users) != count($approved_users) + 1){return false;}

// Get users in group 1
$group1_users=get_users(1,"","username");

// Get users in group 2
$group2_users=get_users("2","","username");

// Get users in groups 1 and 2
$group1_plus_group2_users=get_users("1,2","","username");

if( count($group1_plus_group2_users) != (count($group1_users) + count($group2_users)) ) {return false;}

return true;
