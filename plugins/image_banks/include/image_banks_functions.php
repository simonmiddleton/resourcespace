<?php
namespace ImageBanks;

require_once 'AbstractProvider.php';
require_once 'ProviderResult.php';
require_once 'ProviderSearchResults.php';


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

        $temp_dir_path = get_temp_dir(false, "ImageBanks-{$loaded_provider}");

        $provider = new $provider_class($lang, $temp_dir_path);

        if(!($provider instanceof Provider))
            {
            $admin_notify_users = array();

            foreach(get_notification_users("SYSTEM_ADMIN") as $notify_user)
                {
                get_config_option($notify_user['ref'], 'user_pref_system_management_notifications', $send_message);

                if($send_message == false)
                    {
                    continue;
                    }

                $admin_notify_users[] = $notify_user['ref'];
                }

            message_add($admin_notify_users, "image_banks plugin: Provider - {$loaded_provider} - MUST be an instance of Provider");

            continue;
            }

        $GLOBALS = $provider->registerConfigurationNeeds($GLOBALS);

        $providers[$provider->getId()] = $provider;
        }

    return $providers;
    }

function validFileSource($file, array $loaded_providers)
    {
    $valid_source = false;

    $providers = getProviders($loaded_providers);
    foreach($providers as $provider)
        {
        $download_endpoint = $provider->getAllowedDownloadEndpoint();

        if(substr($file, 0, strlen($download_endpoint)) == $download_endpoint)
            {
            $valid_source = true;

            break;
            }

        $valid_source = false;
        }

    return $valid_source;
    }