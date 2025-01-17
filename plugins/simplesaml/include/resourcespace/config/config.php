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
foreach ($simplesamlconfig['config'] as $option => $configvalue) {
    // Plain-text admin-passwords are no longer allowed to be used in SSP (since v2.3) but ResourceSpace existing config
    // may use it so convert it now on the fly
    if ($option === 'auth.adminpassword' && is_null(password_get_info($configvalue)['algo'])) {
        $hasher = new Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher(
            4, // time cost
            65536, // memory cost
            null, // cost
            PASSWORD_BCRYPT,
        );
        $configvalue = $hasher->hash($configvalue);
    }

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
