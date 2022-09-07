<?php
if('cli' != PHP_SAPI)
    {
    header('HTTP/1.1 401 Unauthorized');
    exit('Command line execution only');
    }

include dirname(__FILE__) . "/../../include/db.php";

resign_all_code();
