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
db_clear_connection_mode();


// echo PHP_EOL . '############'. PHP_EOL;

// During a DB transaction, the connection mode should be forced to RW automatically by db_*_transaction functions
db_begin_transaction('test_111');
if(db_get_connection_mode() !== 'read_write')
    {
    echo 'db_begin_transaction() not forcing RW connection - ';
    return false;
    }

$new_ref = create_resource(1, 0);
$resource_data = get_resource_data($new_ref, false);
db_end_transaction('test_111');

if($resource_data === false)
    {
    echo 'Force RW during DB transaction - ';
    return false;
    }


// Teardown
unset($new_ref, $resource_data);

return true;