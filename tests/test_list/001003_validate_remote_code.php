<?php
if('cli' != PHP_SAPI)
    {
    exit('This utility is command line only.');
    }


$code = '
$my_var = ($some_undefined_var == "somevalue");
';
if(validate_remote_code($code))
    {
    echo 'Code with undefined variables - ';
    return false;
    }

// TODO; do more tests;
// $no_semicolon = true
// callUndefinedFunction();

return true;