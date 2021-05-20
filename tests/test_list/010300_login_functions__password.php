<?php
if('cli' != PHP_SAPI)
    {
    exit('This utility is command line only.');
    }

include_once __DIR__ . '/../../include/login_functions.php';

// Set up
$password_hash = [
    'algo' => PASSWORD_BCRYPT,
    'options' => ['cost' => 5]
];

$test_user_name = 'test_010300';
$plaintext_pass = 'some Super 5ecure-password';
$pass_hash_v1 = md5('RS' . $test_user_name . $plaintext_pass);
$pass_hash_v2 = hash('sha256', $pass_hash_v1);
$pass_hash_v3 = '$2y$05$cc1ufF4.bVk4OxskEa1yHOos0Qud2U2dfGr7AlUnhOmgtkiMNGMGK';

echo PHP_EOL; # TODO: remove when done



// Hash a plain text pass
$rs_pass_hash = rs_password_hash($plaintext_pass);
if(!password_verify($plaintext_pass , $rs_pass_hash))
    {
    echo 'Hash plain text password - ';
    return false;
    }





// Tear down
unset($test_user_name, $plaintext_pass, $pass_hash_v1, $pass_hash_v2, $pass_hash_v3);

return true;