<?php

use function ImageBanks\autoloadProviders;

include_once dirname(__DIR__) . '/include/image_banks_functions.php';

$image_banks_loaded_providers = autoloadProviders();

/* 
Note: multi-instance Providers (e.g. ResourceSpace) will have to be selected by the user after they've 
configured at least one instance since in that case the instance represents the provider.
*/
$image_banks_selected_providers = ['Pixabay', 'Shutterstock'];