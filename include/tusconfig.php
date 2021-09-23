<?php
global $scramble_key, $session_hash;
$cachepath = get_temp_dir() . DIRECTORY_SEPARATOR . "tus" . DIRECTORY_SEPARATOR . md5($scramble_key . $session_hash) . DIRECTORY_SEPARATOR;
return [
    /**
     * File cache configs.
     */
    'file' => [
        'dir' => $cachepath,
        'name' => 'tus_php.server.cache',
    ],
];
