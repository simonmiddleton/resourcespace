<?php

require __DIR__ . '/vendor/autoload.php';
// server.php

\TusPhp\Config::set('tusconfig.php');


$server   = new \TusPhp\Tus\Server('file'); // Either redis, file or apcu. Leave empty for file based cache.
$server->setUploadDir('/var/www_dev/filestore/tmp/tusupload/');
$response = $server->serve();
$response->send();
//exit("HERE"); // Exit from current PHP process.
exit(0); // Exit from current PHP process.

