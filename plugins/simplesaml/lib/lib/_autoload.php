<?php

/**
 * This file is a backwards compatible autoloader for SimpleSAMLphp.
 * Loads the Composer autoloader.
 *
 * @author Olav Morken, UNINETT AS.
 * @package SimpleSAMLphp
 */

declare(strict_types=1);

// SSP is loaded as a separate project
if (file_exists(dirname(dirname(__FILE__)) . '/vendor/autoload.php')) {
    require_once dirname(dirname(__FILE__)) . '/vendor/autoload.php';
} else {
    // SSP is loaded as a library
    if (file_exists(dirname(dirname(__FILE__)) . '/../../autoload.php')) {
        require_once dirname(dirname(__FILE__)) . '/../../autoload.php';
    } else {
        throw new Exception('Unable to load Composer autoloader');
    }
}

# Load ResourceSpace configuration if not already loaded so that all SP and IdP details can be set in ResourceSpace config
$rsconfigloaded = getenv('SIMPLESAMLPHP_RESOURCESPACE_CONFIG_LOADED');

if(!$rsconfigloaded && file_exists(__DIR__ .'/../../../../include/db.php') && !defined("SYSTEM_UPGRADE_LEVEL"))
    {
    $suppress_headers = true;
    include __DIR__ . '/../../../../include/db.php';
    putenv('SIMPLESAMLPHP_RESOURCESPACE_CONFIG_LOADED=1');
    }

global $simplesaml_rsconfig, $simplesamlconfig;

if(isset($simplesaml_rsconfig) && $simplesaml_rsconfig)
    {
    $rsconfigdir = realpath(__DIR__ . '/../resourcespace/config/');
    $rsmetadir = realpath(__DIR__ . '/../resourcespace/metadata/');
    // Set to use the ResourceSpace files load the config and metadata into SimpleSAML
    putenv('SIMPLESAMLPHP_CONFIG_DIR=' . $rsconfigdir);
    $simplesamlconfig["config"]["metadatadir"] = $rsmetadir;
    }