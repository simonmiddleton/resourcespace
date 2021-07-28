<?php
// Include standard configuration file
include __DIR__ . "/../../config-templates/config.php";

// Get config from ResourceSpace and override as required
global $simplesamlconfig, $simplesaml_config_defaults, $baseurl_short, $email_from;
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
// if(!isset($simplesamlconfig["config"]["baseurlpath"]))
//     {
//     $config["baseurlpath"] = $baseurl_short . 'plugins/simplesaml/lib/www/';
//     }
// if(!isset($simplesamlconfig["config"]["tempdir"]))
//     {
//     $config["tempdir"] = get_temp_dir();
//     }
// if(!isset($simplesamlconfig["config"]["technicalcontact_email"]))
//     {
//     $config["technicalcontact_email"] = $email_from;
//     }



