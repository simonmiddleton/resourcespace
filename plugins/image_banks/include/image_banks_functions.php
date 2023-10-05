<?php
namespace ImageBanks;

require_once 'AbstractProvider.php';
require_once 'ProviderResult.php';
require_once 'ProviderSearchResults.php';
require_once 'MultipleInstanceProviderInterface.php';
require_once 'ProviderInstanceInterface.php';
require_once 'ResourceSpaceProviderInstance.php';


/**
 * Autoload providers
 * @return array List of loaded provider names
 */
function autoloadProviders(): array
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


/**
 * Get loaded providers
 * @param array $loaded_providers
 * @return array{'providers': list<Provider>, 'errors': list<string>}
 */
function getProviders(array $loaded_providers): array
    {
    global $lang;

    $errors = $providers = [];

    foreach($loaded_providers as $loaded_provider)
        {
        // TODO: check provider IDs are unique. If not, try at most 3 times until we decide not to include it.
        $provider_class = "\ImageBanks\\$loaded_provider";

        $temp_dir_path = get_temp_dir(false, "ImageBanks-{$loaded_provider}");

        $provider = new $provider_class($lang, $temp_dir_path);

        if(!($provider instanceof Provider))
            {
            debug("[image_banks] Provider - {$loaded_provider} - MUST be an instance of Provider");
            continue;
            }

        $provider->registerConfigurationNeeds($GLOBALS);
        if ($provider instanceof MultipleInstanceProviderInterface)
            {
            $parse_errs = array_unique($provider->parseInstancesConfiguration());
            $errors = array_merge($errors, array_map(fn($E) => str_replace('%PROVIDER', $provider->getName(), $E), $parse_errs));
            }

        $providers[$provider->getId()] = $provider;
        }

    return [$providers, $errors];
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
