<?php
#
# Healthcheck.php
#
#
# Performs some basic system checks. Useful for remote monitoring of ResourceSpace installations.
#
$rs_root = dirname(__DIR__, 2);
include_once $rs_root . '/include/general_functions.php';

// Check required PHP modules. This must be done before loading db.php (ie potentially using any during the bootstrap process)
$system_info = get_system_status();
if($system_info['status'] === 'FAIL')
    {
    exit($system_info['status']);
    }

include $rs_root . '/include/db.php';

// The O and K are separate, so that if served as plain text the remote check doesn't erroneously report all well
$status_ok = 'O' . 'K';
$system_info = get_system_status();
if(in_array($system_info['status'], ['OK', 'WARNING']))
    {
    echo $status_ok;
    }
else
    {
    exit('FAIL');
    }