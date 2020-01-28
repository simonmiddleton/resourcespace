<?php
if('cli' != PHP_SAPI)
    {
    exit('This utility is command line only.');
    }

if(!db_use_multiple_connection_modes())
    {
    echo "INFO: System NOT configured for read-only connection mode! - ";
    return true;
    }

// Check clearing connection mode
db_set_connection_mode("read_only");
db_clear_connection_mode();
if(isset($GLOBALS["db_connection_mode"]))
    {
    return false;
    }

db_set_connection_mode("read_only");
if(db_get_connection_mode() !== "read_only")
    {
    echo "Set/ Get connection mode - ";
    return false;
    }

try
    {
    $use_error_exception = true;
    sql_query("INSERT INTO sysvars (`name`, `value`) VALUES ('test_read_only_mode', true)");
    }
catch(Exception $e)
    {
    return true;
    }

return false;