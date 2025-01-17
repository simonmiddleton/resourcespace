<?php

// Get authsources config from ResourceSpace config
global $simplesamlconfig;
if (isset($simplesamlconfig['authsources'])) {
    foreach ($simplesamlconfig['authsources'] as $name => &$authsource) {
        /*
        todo: check if needed for non-www routes as the auto generated entityID should be as expected.
        Also, bear in mind that we're already meant to set the baseurlpath and so the generated entityID should just
        follow that config rendering this completely unnecessary.
        */
        if ($name !== 'admin' && !isset($authsource['entityID'])) {
            $authsource['entityID'] = sprintf(
                '%s/plugins/simplesaml/lib/%s/module.php/saml/sp/metadata.php/%s',
                $GLOBALS['baseurl'],
                $GLOBALS['simplesaml_use_www'] ? 'www' : 'public',
                $name
            );
        }
    }

    $config = $simplesamlconfig['authsources'];
} else {
    exit('No authsources configured ($simplesamlconfig["authsources"])');
}
