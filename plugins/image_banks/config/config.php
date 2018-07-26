<?php
include_once __DIR__ . '/../include/image_banks_functions.php';
$image_banks_loaded_providers = \Imagebanks\autoloadProviders();

// TODO: find a way of putting in the global scope any variable required by each provider
$pixabay_api_key = '';