<?php

function adobe_link_genkey($user,$resource)
    {
    global $scramble_key;
    $remote_ip = get_ip();
    return hash('sha256',$user . date('Ymd') . $scramble_key . $resource . $remote_ip);
    }