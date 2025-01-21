<?php

// This page is included if using ResourceSpace to store SAML configuration
// Set SimpleSAML config
global $simplesamlconfig, $simplesaml_config_defaults;

// Configure SimpleSAMLPHP default settings because the logic depends on the simplesaml plugins' own settings.
// Point to the canonical URL (source: https://simplesamlphp.org/docs/stable/simplesamlphp-install.html)
$simplesaml_config_defaults['baseurlpath'] = sprintf(
    '%s/plugins/simplesaml/lib/%s/',
    $GLOBALS['baseurl'],
    $GLOBALS['simplesaml_use_www'] ? 'www' : 'public'
);

/** @var array $config */
require dirname(__DIR__, 3) . '/lib/config/config.php.dist';

foreach ($simplesamlconfig['config'] as $option => $configvalue) {
    // Plain-text admin-passwords are no longer allowed to be used in SSP (since v2.3) but ResourceSpace existing config
    // may use it so convert it now on the fly (let it use the best algorithm available)
    if ($option === 'auth.adminpassword' && is_null(password_get_info($configvalue)['algo'])) {
        $hasher = new Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher(4, 65536, null, null);
        $configvalue = $hasher->hash($configvalue);
    }

    $config[$option] = $configvalue;
}

foreach($simplesaml_config_defaults as $setting => $value)
    {
    if(!isset($simplesamlconfig["config"][$setting]))
        {
        $config[$setting] = $value;
        }
    }
