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
$pass_hash_v3 = password_hash($plaintext_pass, $password_hash['algo'], $password_hash['options']);
// End of set up



// Hash a plain text password
if(!password_verify($plaintext_pass , rs_password_hash($plaintext_pass)))
    {
    echo 'Hash plain text password - ';
    return false;
    }


// User password is not hashed at all (v0 - stored in plain text in the DB)
if(!rs_password_verify($plaintext_pass, $plaintext_pass))
    {
    echo 'Verify password hash v0 (plain text) - ';
    return false;
    }


// User password is MD5 hashed (v1 - MD5 stored in the DB)
if(!rs_password_verify($plaintext_pass, $pass_hash_v1))
    {
    echo 'Verify password hash v1 (MD5) - ';
    return false;
    }


// User password is SHA256 hashed (v2 - SHA256 stored in the DB)
if(!rs_password_verify($plaintext_pass, $pass_hash_v2))
    {
    echo 'Verify password hash v2 (SHA256) - ';
    return false;
    }


// User password is hashed based on config (v3 - stored in the DB)
if(!rs_password_verify($plaintext_pass, $pass_hash_v3))
    {
    echo 'Verify password hash v3 - ';
    return false;
    }


// User provided a bad password
if(rs_password_verify('some bad password', $pass_hash_v3))
    {
    echo 'Verify bad password hash - ';
    return false;
    }

/*
# This test might not be required as we should only need this check in one place - perform_login()
$use_cases = [
    [
        'msg' => 'v0 (plain text)',
        'pass' => $plaintext_pass,
        'hash' => $plaintext_pass,
    ],
    [
        'msg' => 'v1 (MD5)',
        'pass' => $plaintext_pass,
        'hash' => $pass_hash_v1,
    ],
    [
        'msg' => 'v2 (SHA256)',
        'pass' => $plaintext_pass,
        'hash' => $pass_hash_v2,
    ],
];
foreach($use_cases as $use_case)
    {
    if(!password_verify($plaintext_pass, rs_password_needs_rehash($use_case['pass'], $use_case['hash'])))
        {
        echo sprintf('Password hash %s needs rehash - ', $use_case['msg']);
        return false;
        }
    }
echo 'plaintext_pass = ' . json_encode(password_needs_rehash($plaintext_pass, $password_hash['algo'], $password_hash['options'])) . PHP_EOL;
echo 'pass_hash_v1 = ' . json_encode(password_needs_rehash($pass_hash_v1, $password_hash['algo'], $password_hash['options'])) . PHP_EOL;
echo 'pass_hash_v2 = ' . json_encode(password_needs_rehash($pass_hash_v2, $password_hash['algo'], $password_hash['options'])) . PHP_EOL;
echo 'pass_hash_v3 = ' . json_encode(password_needs_rehash($pass_hash_v3, $password_hash['algo'], $password_hash['options'])) . PHP_EOL;
echo 'pass_hash_custom = ' . json_encode(password_needs_rehash($pass_hash_v3, $password_hash['algo'], ['cost' => 12])) . PHP_EOL;
*/


// Tear down
unset($plaintext_pass, $pass_hash_v1, $pass_hash_v2, $pass_hash_v3);

return true;