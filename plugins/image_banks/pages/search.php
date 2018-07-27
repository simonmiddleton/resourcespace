<?php
$rs_root = dirname(dirname(dirname(__DIR__)));
include "{$rs_root}/include/db.php";
include_once "{$rs_root}/include/general.php";
include "{$rs_root}/include/authenticate.php";

$search                 = getval('search', '');
$image_bank_provider_id = getval("image_bank_provider_id", 0, true);

if($image_bank_provider_id == 0)
    {
    trigger_error($lang["image_banks_provider_id_required"]);
    }

$providers = \Imagebanks\getProviders($image_banks_loaded_providers);

if(!array_key_exists($image_bank_provider_id, $providers))
    {
    trigger_error($lang["image_banks_provider_not_found"]);
    }

$provider = $providers[$image_bank_provider_id];
