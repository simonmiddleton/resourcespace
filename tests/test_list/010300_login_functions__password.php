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

$plaintext_pass = 'some Super 5ecure-password';
$pass_hash_v1 = md5($plaintext_pass);
$pass_hash_v2 = hash('sha256', $pass_hash_v1);
$pass_hash_v3 = password_hash($pass_hash_v2, $password_hash['algo'], $password_hash['options']);

$sql_update_user = "UPDATE user SET `password` = '%s' WHERE ref = '%s'";

$test_10300_user = (new_user('test_10300_user') ?: get_user_by_username('test_10300_user'));
// $test_10300_user_data = get_user($test_10300_user);
// sql_query(sprintf($sql_update_user, escape_check($plaintext_pass), escape_check($test_10300_user)));
// End of set up

echo PHP_EOL; # TODO: remove when done



// Hash a plain text password
$rs_password_hash = rs_password_hash($plaintext_pass);
if(!password_verify($pass_hash_v2 , $rs_password_hash))
    {
    echo 'Hash plain text password without losing old hashing algos (v1 & v2) - ';
    return false;
    }





// user has password not hashed at all (v0 - stored in plain text in the DB)
if(!rs_password_verify($plaintext_pass, $plaintext_pass))
    {
    echo 'Verify password hash v0 (plain text) - ';
    return false;
    }


// user has password MD5 hashed (v1 - MD5 stored in the DB)
if(!rs_password_verify($plaintext_pass, $pass_hash_v1))
    {
    echo 'Verify password hash v1 (MD5) - ';
    return false;
    }


// user has password SHA256 hashed (v2 - SHA256 stored in the DB)
if(!rs_password_verify($plaintext_pass, $pass_hash_v2))
    {
    echo 'Verify password hash v2 (SHA256) - ';
    return false;
    }


// user has password hashed based on config (v3 - stored in the DB)
if(!rs_password_verify($pass_hash_v2, $pass_hash_v3))
    {
    echo 'Verify password hash v3 (plain text) - ';
    return false;
    }



todo; hash to just hash.
another function to convert between hash versions (e.g from plain text/v1/v2 to v3)























// echo json_encode(password_verify($pass_hash_v2 , $pass_hash_v3)) . PHP_EOL;


// Tear down
unset($plaintext_pass, $pass_hash_v1, $pass_hash_v2, $pass_hash_v3);

return true;