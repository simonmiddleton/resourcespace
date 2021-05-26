<?php
if('cli' != PHP_SAPI)
    {
    exit('This utility is command line only.');
    }

include_once __DIR__ . '/../../include/login_functions.php';

// Set up
$password_hash_info = [
    'algo' => PASSWORD_BCRYPT,
    'options' => ['cost' => 5]
];

$test_user_name = 'test_10300_user';
$plaintext_pass = 'some Super 5ecure-password';
$RS_madeup_pass = "RS{$test_user_name}{$plaintext_pass}";
$pass_data = ['username' => $test_user_name];

$pass_hash_v1 = md5($RS_madeup_pass);
$pass_hash_v2 = hash('sha256', $pass_hash_v1);
$pass_hash_v3 = rs_password_hash($RS_madeup_pass);
$pass_hmac_v3 = hash_hmac('sha256', $RS_madeup_pass, $scramble_key);
// End of set up



// Hash a plain text password
$rs_password_hash = rs_password_hash($plaintext_pass);
$rs_password_hmac = hash_hmac('sha256', $plaintext_pass, $scramble_key);
if(!($rs_password_hash !== false && password_verify($rs_password_hmac , $rs_password_hash)))
    {
    echo 'Hash plain text password - ';
    return false;
    }


// User password is not hashed at all (v0 - stored in plain text in the DB)
if(!rs_password_verify($plaintext_pass, $plaintext_pass, $pass_data))
    {
    echo 'Verify password hash v0 (plain text) - ';
    return false;
    }


// User password is MD5 hashed (v1 - MD5 stored in the DB)
if(!rs_password_verify($plaintext_pass, $pass_hash_v1, $pass_data))
    {
    echo 'Verify password hash v1 (MD5) - ';
    return false;
    }


// User password is SHA256 hashed (v2 - SHA256 stored in the DB)
if(!rs_password_verify($plaintext_pass, $pass_hash_v2, $pass_data))
    {
    echo 'Verify password hash v2 (SHA256) - ';
    return false;
    }


// User password is hashed based on config (v3 - stored in the DB)
if(!rs_password_verify($plaintext_pass, $pass_hash_v3, $pass_data))
    {
    echo 'Verify password hash v3 - ';
    return false;
    }


// User provided a bad password
if(rs_password_verify('some bad password', $pass_hash_v3, $pass_data))
    {
    echo 'Verify bad password hash - ';
    return false;
    }



// Tear down
unset($test_user_name, $plaintext_pass, $RS_madeup_pass, $pass_hash_v1, $pass_hash_v2, $pass_hash_v3, $pass_hmac_v3);

return true;