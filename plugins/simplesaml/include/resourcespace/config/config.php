<?php

// This page is included if using ResourceSpace to store SAML configuration
// Set SimpleSAML config
global $simplesamlconfig, $simplesaml_config_defaults, $baseurl, $email_from;

// Configure SimpleSAMLPHP default settings IF the logic depends on the simplesaml plugin own settings.
$simplesaml_config_defaults['baseurlpath'] = sprintf(
    '%splugins/simplesaml/lib/%s/',
    $GLOBALS['baseurl_short'],
    $GLOBALS['simplesaml_use_www'] ? 'www' : 'public'
);

$config = array();
foreach($simplesamlconfig["config"] as $option => $configvalue)
    {
    $config[$option] = $configvalue;
    }

// set defaults if not set in config
foreach($simplesaml_config_defaults as $setting => $value)
    {
    if(!isset($simplesamlconfig["config"][$setting]))
        {
        $config[$setting] = $value;
        }
    }

// if(!isset($simplesamlconfig["config"]["tempdir"]))
//     {
//     $config["tempdir"] = get_temp_dir();
//     }
// if(!isset($simplesamlconfig["config"]["technicalcontact_email"]))
//     {
//     $config["technicalcontact_email"] = $email_from;
//     }
