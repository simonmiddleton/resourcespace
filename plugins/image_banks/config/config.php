<?php
include_once __DIR__ . '/../include/image_banks_functions.php';
$image_banks_loaded_providers = \ImageBanks\autoloadProviders();

// TODO: register each provider's config needs (low priority)
$pixabay_api_key = '';