<?php
namespace ImageBanks;

require_once 'AbstractProvider.php';


function autoloadProviders()
    {
    $providers_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'providers';

    if(!file_exists($providers_path) || !is_dir($providers_path))
        {
        return array();
        }

    $loaded_providers = array();

    $files = new \DirectoryIterator($providers_path);
    foreach($files as $file)
        {
        if($file->isDot())
            {
            continue;
            }

        $filename = $file->getFilename();

        require_once $providers_path . DIRECTORY_SEPARATOR . $filename;

        $loaded_providers[] = pathinfo($filename, PATHINFO_FILENAME);
        }

    return $loaded_providers;
    }


function getProviders(array $loaded_providers)
    {
    global $lang;

    $providers = array();

    foreach($loaded_providers as $loaded_provider)
        {
        // TODO: check provider IDs are unique. If not, try at most 3 times until we decide not to include it.
        $provider_class = "\ImageBanks\\$loaded_provider";
        $providers[] = new $provider_class($lang);
        }

    return $providers;
    }