<?php
if('cli' != PHP_SAPI)
    {
    exit('This utility is command line only.');
    }

// legacy code that used to do it
// if (strlen($password)!=64)
//     {
//     $password_hash=hash('sha256', md5("RS" . $username . $password));
//     }


// Set up
$password_hash_options = ['cost' => 5];

$user_name = 'test_010300';
$plain_pass = 'somevalue';
$pass_hash_v1 = md5("RS" . $user_name . $plain_pass);
$pass_hash_v2 = hash('sha256', $pass_hash_v1);

if(mb_strlen($pass_hash_v2) <= 64)
    {
    // 
    }

echo password_hash($plain_pass, PASSWORD_BCRYPT, $password_hash_options) . PHP_EOL;
echo json_encode(password_verify ($plain_pass , '$2y$05$mJX2AtOrg59I81r.R.7sL.8xnm70pt9bMJYMMDcPRJI4lOImOn7aO')) . PHP_EOL;



// Tear down
unset($user_name, $plain_pass, $pass_hash_v1, $pass_hash_v2);

return true;