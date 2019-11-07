<?php
namespace ResourceConnect;

function generate_access_key($scramble_key)
    {
    if(trim($scramble_key) === "")
        {
        trigger_error("scramble_key blank");
        }

    return md5("resourceconnect{$scramble_key}");
    }

function generate_k_value($username, $resource_ref, $scramble_key)
    {
    return "{$username}-" . substr(md5(generate_access_key($scramble_key) . $resource_ref), 0, 10);
    }