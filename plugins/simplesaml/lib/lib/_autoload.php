<?php

/**
 * This file is a backwards compatible autoloader for SimpleSAMLphp.
 * Loads the Composer autoloader.
 *
 * @package SimpleSAMLphp
 */

declare(strict_types=1);

// SSP is loaded as a separate project
if (file_exists(dirname(__FILE__, 2) . '/vendor/autoload.php')) {
    require_once dirname(__FILE__, 2) . '/vendor/autoload.php';
} elseif (file_exists(dirname(__FILE__, 2) . '/../../autoload.php')) {
    // SSP is loaded as a library.
    require_once dirname(__FILE__, 2) . '/../../autoload.php';
} else {
    throw new Exception('Unable to load Composer autoloader');
}

// Load ResourceSpace configuration if not already loaded so that all SP and IdP details can be set in ResourceSpace config
$rsconfigloaded = getenv('SIMPLESAMLPHP_RESOURCESPACE_CONFIG_LOADED');
$resourcespace_boot_file = dirname(__DIR__, 4) . '/include/boot.php';
if(!$rsconfigloaded && file_exists($resourcespace_boot_file) && !defined("SYSTEM_UPGRADE_LEVEL"))
    {
    $suppress_headers = true;
    include $resourcespace_boot_file;
    putenv('SIMPLESAMLPHP_RESOURCESPACE_CONFIG_LOADED=1');
    }

if (isset($GLOBALS['simplesaml_rsconfig']) && $GLOBALS['simplesaml_rsconfig']) {
    // Set to use the ResourceSpace files load the config and metadata into SimpleSAML
    $simplesaml_resourcespace_dir = dirname(__DIR__, 2) . '/include/resourcespace';
    putenv('SIMPLESAMLPHP_CONFIG_DIR=' . realpath("{$simplesaml_resourcespace_dir}/config/"));
    $GLOBALS['simplesamlconfig']['config']['metadatadir'] = realpath("{$simplesaml_resourcespace_dir}/metadata/");
}
