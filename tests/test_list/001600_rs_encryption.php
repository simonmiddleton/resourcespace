<?php
if(PHP_SAPI != 'cli')
    {
    exit('This utility is command line only.');
    }

include_once(__DIR__ . '/../../include/encryption_functions.php');

$data = "Test encryption data";
$key  = "654b5005395f10488aae744b8615e007";

$encrypted_data = rsEncrypt($data, $key);
$plaintext      = rsDecrypt($encrypted_data, $key);

if($plaintext === false)
    {
    return false;
    }

if($data !== $plaintext)
    {
    return false;
    }

return true;