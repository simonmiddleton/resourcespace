<?php
$wrappers = stream_get_wrappers();
$unregwrappers = array('ftp','ftps', 'phar');
foreach($unregwrappers as $unregwrapper)
    {
    if(in_array($unregwrapper,$wrappers))
        {
        stream_wrapper_unregister($unregwrapper);
        }
    }