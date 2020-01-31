<?php
if('cli' != PHP_SAPI)
    {
    exit('This utility is command line only.');
    }

$expected = "'/usr/bin/madeupcmd' -d '%Y-%m-%d %H:%M:%S' -o '/path/to/file.ext'";
$actual = escape_command_args(
    "'/usr/bin/madeupcmd' -d dateFormat -o output_file",
    array(
        "dateFormat" => "%Y-%m-%d %H:%M:%S",
        "output_file" => "/path/to/file.ext"
    )
);

if($expected === $actual)
    {
    return true;
    }

return false;