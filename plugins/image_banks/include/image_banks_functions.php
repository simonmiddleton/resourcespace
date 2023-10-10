<?php

namespace ImageBanks;

require_once 'AbstractProvider.php';
require_once 'NoProvider.php';
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

/**
 * List providers' (instance) name.
 * @return array Returns an array where the key is the Providers' (or its instance) ID and the value is its name
 */
function listProviderInstanceNames(Provider $provider): array
    {
    if ($provider instanceof MultipleInstanceProviderInterface)
        {
        $instance_names = [];
        $provider_instances = $provider->getAllInstances();
        foreach ($provider_instances as $id => $instance)
            {
            $instance_names[$id] = sprintf('%s - %s', $provider->getName(), $instance->getName());
            }
        return $instance_names;
        }

    return [$provider->getId() => $provider->getName()];
    }

/**
 * Check if a Provider (or its instance) is selected as active from the plugins' setup page.
 * @param string $provider A providers' (or instance) name
 */
function isProviderActive(string $provider): bool
    {
    return in_array($provider, $GLOBALS['image_banks_selected_providers']);
    }

/**
 * Convert a list of providers to a list of providers (or their instances) that have all dependencies and are currently
 * active.
 *
 * @param list<Provider> $providers
 * @return array Returns an array where the key is the Providers' (or its instance) ID and the value is its name
 */
function providersCheckedAndActive(array $providers): array
    {
    $providers_select_list = [];
    foreach($providers as $provider)
        {
        if ($provider->checkDependencies() === [])
            {
            $providers_select_list += listProviderInstanceNames($provider);
            }
        }
    return array_filter($providers_select_list, 'ImageBanks\isProviderActive');
    }

/**
 * Helper function to generate an instance ID based on its Provider ID.
 */ 
function createProviderInstanceId(Provider $provider): callable
    {
    return fn(int $id) => computeProviderBaseInstanceId($provider) + $id;
    }

/** Helper function to compute a Providers' base ID (for multi-instance) */
function computeProviderBaseInstanceId(Provider $provider): int
    {
    if ($provider instanceof MultipleInstanceProviderInterface)
        {
        return $provider->getId() * IMAGE_BANKS_MAX_INSTANCE_COUNT; 
        }
    return $provider->getId(); 
    }

/**
 * Get a Provider (or its instance if multi-instance supported).
 *
 * @param array<Provider&MultipleInstanceProviderInterface> $providers
 * @param int $selected ID for the selected Provider (or its instance)
 */
function getProviderSelectInstance(array $providers, int $selected): Provider
    {
    // Normal providers
    if (isset($providers[$selected]))
        {
        return $providers[$selected];
        }

    // Multi-instance providers
    /** @var Provider&MultipleInstanceProviderInterface $provider */
    foreach ($providers as $provider_id => $provider)
        {
        if (!($provider instanceof MultipleInstanceProviderInterface))
            {
            continue;
            }

        $base_id = computeProviderBaseInstanceId($provider);
        $instance_id = $selected - $base_id;
        if ($instance_id >= 0 && $instance_id < IMAGE_BANKS_MAX_INSTANCE_COUNT)
            {
            $provider = $providers[$provider_id];
            return $provider->selectSystemInstance($instance_id);
            }
        }

    return new NoProvider($GLOBALS['lang'], get_temp_dir(false, 'ImageBanks-NoProvider'));
    }
