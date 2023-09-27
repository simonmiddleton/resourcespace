<?php
$image_banks_plugin_root = dirname(__DIR__);
include_once "{$image_banks_plugin_root}/include/image_banks_functions.php";
$image_banks_loaded_providers = \ImageBanks\autoloadProviders();

$image_banks_available_providers = [];