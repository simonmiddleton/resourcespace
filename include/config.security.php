<?php
$wrappers = stream_get_wrappers();
$unregwrappers = array('ftp','ftps');
foreach($unregwrappers as $unregwrapper)
    {
    if(in_array($unregwrapper,$wrappers))
        {
        stream_wrapper_unregister($unregwrapper);
        }
    }