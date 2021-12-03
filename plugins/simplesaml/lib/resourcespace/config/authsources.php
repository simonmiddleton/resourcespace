<?php
// Get authsources config from ResourceSpace config
global $simplesamlconfig;
if(isset($simplesamlconfig["authsources"]))
    {
    $config = $simplesamlconfig["authsources"];
    }
else
    {
    exit('No authsources configured ($simplesamlconfig["authsources"])');
    }
