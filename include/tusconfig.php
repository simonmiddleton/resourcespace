<?php
global $scramble_key, $usersession;
$cachepath = get_temp_dir() . DIRECTORY_SEPARATOR . "tus" . DIRECTORY_SEPARATOR . md5($scramble_key . $usersession) . DIRECTORY_SEPARATOR;
return [
    /**
     * File cache configs.
     */
    'file' => [
        'dir' => $cachepath,
        'name' => 'tus_php.server.cache',
    ],
];
